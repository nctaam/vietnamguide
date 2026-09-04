<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

require_once __DIR__ . '/comparison-rollout-lib.php';

const VG_COMPARISON_APPROVAL_KEY_ID = 'comparison-approval-2026-01';
const VG_COMPARISON_APPROVAL_KEY_ENVIRONMENT = 'VG_COMPARISON_APPROVAL_SECRET_2026_01';

function vg_comparison_approve_fail(string $message, int $code = 1): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit($code);
}

function vg_comparison_approve_options(array $arguments): array
{
    $options = [];
    foreach (array_slice($arguments, 1) as $argument) {
        if (!is_string($argument) || !str_starts_with($argument, '--') || !str_contains($argument, '=')) {
            vg_comparison_approve_fail('Every signer argument must use --name=value.');
        }
        [$name, $value] = explode('=', substr($argument, 2), 2);
        if ($name === '' || array_key_exists($name, $options)) {
            vg_comparison_approve_fail('Signer arguments must be unique and named.');
        }
        $options[$name] = $value;
    }
    return $options;
}

function vg_comparison_approve_path_inside(string $path, string $root): bool
{
    $path = rtrim(str_replace('\\', '/', $path), '/');
    $root = rtrim(str_replace('\\', '/', $root), '/');
    return $path === $root || str_starts_with($path . '/', $root . '/');
}

function vg_comparison_approve_fail_if_link_component(string $path): void
{
    $cursor = $path;
    while ($cursor !== dirname($cursor)) {
        if (is_link($cursor)) {
            vg_comparison_approve_fail('The output path must not contain symlink components.');
        }
        $cursor = dirname($cursor);
    }
}

function vg_comparison_approve_cleanup(string $path, string $reason): never
{
    clearstatcache(true, $path);
    if (file_exists($path) || is_link($path)) {
        @unlink($path);
        clearstatcache(true, $path);
    }
    if (file_exists($path) || is_link($path)) {
        vg_comparison_approve_fail('Approval artifact cleanup failed after ' . $reason . '.');
    }
    vg_comparison_approve_fail($reason);
}

function vg_comparison_approve_test_failure(string $stage): bool
{
    return getenv('VG_COMPARISON_APPROVAL_TEST_FAILURE') === $stage;
}

function vg_comparison_approve_revalidate_directory(string $directory): void
{
    clearstatcache(true, $directory);
    $resolved = realpath($directory);
    if ($resolved === false || $resolved !== $directory || is_link($directory)) {
        vg_comparison_approve_fail('The canonical output directory changed during signing.');
    }
}

function vg_comparison_approve_require_posix_directory(string $directory): array
{
    if (PHP_OS_FAMILY === 'Windows' || !function_exists('fchmod') || !function_exists('posix_geteuid')) {
        vg_comparison_approve_fail('Approval artifacts require a POSIX platform with verifiable Unix 0600 permissions.');
    }
    $stat = @lstat($directory);
    if (!is_array($stat) || (($stat['mode'] ?? 0) & 0170000) !== 0040000 || is_link($directory)
        || ($stat['uid'] ?? -1) !== posix_geteuid() || (($stat['mode'] ?? 0) & 0777) !== 0700) {
        vg_comparison_approve_fail('The canonical output directory must be owned by the effective user with mode 0700.');
    }
    return $stat;
}

function vg_comparison_approve_active_user(array $identity): object
{
    if (!defined('WP_CLI') || WP_CLI !== true || !function_exists('get_userdata') || !function_exists('get_current_user_id')) {
        vg_comparison_approve_fail('Run the signer through WP-CLI after WordPress is loaded.');
    }
    $user = get_userdata((int) ($identity['wp_user_id'] ?? 0));
    if (!is_object($user) || (class_exists('WP_User') && !$user instanceof WP_User)) {
        vg_comparison_approve_fail('The registered WordPress user does not exist.');
    }
    if (!_vg_comparison_wordpress_identity_active($identity)) {
        vg_comparison_approve_fail('The registered WordPress user is not active or does not match the registry.');
    }
    $currentUserId = get_current_user_id();
    if (!is_int($currentUserId) || $currentUserId < 1 || $currentUserId !== (int) $user->ID) {
        vg_comparison_approve_fail('The current WordPress user must exactly match the signing identity.');
    }
    return $user;
}

function vg_comparison_approve_main(array $arguments): void
{
    $options = vg_comparison_approve_options($arguments);
    $required = ['registry', 'registry-hash', 'identity-id', 'role', 'manifest-hash', 'change-ids', 'change-reason', 'output'];
    foreach ($required as $name) {
        if (!isset($options[$name]) || $options[$name] === '') {
            vg_comparison_approve_fail("Missing required --{$name}=... argument.");
        }
    }

    $repoRoot = realpath(dirname(__DIR__));
    $webRoot = defined('ABSPATH') ? realpath(ABSPATH) : false;
    $outputPath = $options['output'];
    if (!str_starts_with($outputPath, '/') && preg_match('/^[A-Za-z]:[\\\\\/]/', $outputPath) !== 1) {
        vg_comparison_approve_fail('The output path must be absolute.');
    }
    $outputDirectory = realpath(dirname($outputPath));
    if ($outputDirectory === false) {
        vg_comparison_approve_fail('The output directory must already exist.');
    }
    vg_comparison_approve_fail_if_link_component(dirname($outputPath));
    $resolvedOutput = $outputDirectory . DIRECTORY_SEPARATOR . basename($outputPath);
    if (is_link($resolvedOutput) || file_exists($resolvedOutput)) {
        vg_comparison_approve_fail('The output path must be a new regular file in a non-symlink directory.');
    }
    $expectedRegistryPath = realpath(__DIR__ . '/comparison-rollout/identities.json');
    $registryPath = realpath($options['registry']);
    if ($expectedRegistryPath === false || $registryPath === false || $registryPath !== $expectedRegistryPath || is_link($options['registry'])) {
        vg_comparison_approve_fail('The identity registry must be the exact reviewed production registry.');
    }
    if (($repoRoot !== false && vg_comparison_approve_path_inside($resolvedOutput, $repoRoot))
        || ($webRoot !== false && vg_comparison_approve_path_inside($resolvedOutput, $webRoot))) {
        vg_comparison_approve_fail('The approval artifact must be outside the repository and web root.');
    }

    $registryText = @file_get_contents($registryPath);
    if (!is_string($registryText)) {
        vg_comparison_approve_fail('The identity registry could not be read.');
    }
    if (preg_match('/^[a-f0-9]{64}$/D', $options['registry-hash']) !== 1 || !hash_equals($options['registry-hash'], hash('sha256', $registryText))) {
        vg_comparison_approve_fail('The reviewed identity registry hash does not match.');
    }
    try {
        $registry = json_decode($registryText, true, 64, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        vg_comparison_approve_fail('The identity registry is not valid JSON.');
    }
    if (!is_array($registry)) {
        vg_comparison_approve_fail('The identity registry is invalid.');
    }
    $lookup = _vg_comparison_find_identity($registry, $options['identity-id']);
    if (($lookup['ok'] ?? false) !== true) {
        vg_comparison_approve_fail('The signing identity is missing or duplicated.');
    }
    $identity = $lookup['identity'];
    if (!in_array($options['role'], $identity['roles'] ?? [], true)) {
        vg_comparison_approve_fail('The identity is not authorized for the requested approval role.');
    }
    vg_comparison_approve_active_user($identity);

    $outputDirectoryStat = vg_comparison_approve_require_posix_directory($outputDirectory);

    $changeIds = array_values(array_filter(explode(',', $options['change-ids']), static fn (string $item): bool => $item !== ''));
    sort($changeIds, SORT_STRING);
    if (!_vg_comparison_sorted_unique_change_ids($changeIds)) {
        vg_comparison_approve_fail('Change IDs must be nonempty, unique stable IDs.');
    }
    $keyId = VG_COMPARISON_APPROVAL_KEY_ID;
    $environment = VG_COMPARISON_APPROVAL_KEY_ENVIRONMENT;
    if (_vg_comparison_key_environment($keyId) !== $environment) {
        vg_comparison_approve_fail('The dedicated approval key mapping is inconsistent.');
    }
    $secret = $environment === null ? false : getenv($environment);
    if (!is_string($secret) || $secret === '' || in_array($secret, _vg_comparison_wordpress_secret_values(), true)) {
        vg_comparison_approve_fail('The dedicated approval key is unavailable or unsafe.');
    }
    $timestamp = gmdate('Y-m-d\\TH:i:s\\Z');
    $artifact = [
        'artifact_version' => '1',
        'manifest_hash' => $options['manifest-hash'],
        'identity_id' => $identity['identity_id'],
        'wp_user_id' => (int) $identity['wp_user_id'],
        'role' => $options['role'],
        'timestamp_utc' => $timestamp,
        'change_ids' => $changeIds,
        'change_reason' => $options['change-reason'],
        'key_id' => $keyId,
    ];
    if (!_vg_comparison_approval_shape_valid($artifact + ['hmac_sha256' => str_repeat('0', 64)])) {
        vg_comparison_approve_fail('The requested approval payload is invalid.');
    }
    $artifact['hmac_sha256'] = hash_hmac('sha256', vg_comparison_canonical_json($artifact), $secret);
    $encoded = vg_comparison_canonical_json($artifact) . PHP_EOL;

    $oldUmask = umask(0077);
    $handle = null;
    $created = false;
    try {
        vg_comparison_approve_revalidate_directory($outputDirectory);
        $handle = fopen($resolvedOutput, 'x');
        if ($handle === false) {
            vg_comparison_approve_fail('The output file already exists or cannot be created.');
        }
        $created = true;
        vg_comparison_approve_revalidate_directory($outputDirectory);
        $currentDirectoryStat = vg_comparison_approve_require_posix_directory($outputDirectory);
        if (($currentDirectoryStat['dev'] ?? null) !== ($outputDirectoryStat['dev'] ?? null)
            || ($currentDirectoryStat['ino'] ?? null) !== ($outputDirectoryStat['ino'] ?? null)) {
            fclose($handle);
            $handle = null;
            vg_comparison_approve_cleanup($resolvedOutput, 'The canonical output directory identity changed during signing');
        }
        $handleStat = fstat($handle);
        $pathStat = @lstat($resolvedOutput);
        if (!is_array($handleStat) || !is_array($pathStat) || (($pathStat['mode'] ?? 0) & 0170000) !== 0100000
            || (isset($handleStat['dev'], $handleStat['ino'], $pathStat['dev'], $pathStat['ino'])
                && ($handleStat['dev'] !== $pathStat['dev'] || $handleStat['ino'] !== $pathStat['ino']))) {
            fclose($handle);
            $handle = null;
            vg_comparison_approve_cleanup($resolvedOutput, 'The created approval artifact did not match its open handle');
        }
        $written = vg_comparison_approve_test_failure('write') ? false : fwrite($handle, $encoded);
        if ($written !== strlen($encoded) || vg_comparison_approve_test_failure('flush') || !fflush($handle)) {
            fclose($handle);
            $handle = null;
            vg_comparison_approve_cleanup($resolvedOutput, 'The approval artifact could not be written completely');
        }
        if (function_exists('fsync') && !fsync($handle)) {
            fclose($handle);
            $handle = null;
            vg_comparison_approve_cleanup($resolvedOutput, 'The approval artifact could not be synchronized');
        }
        if (vg_comparison_approve_test_failure('chmod') || !fchmod($handle, 0600)) {
            fclose($handle);
            $handle = null;
            vg_comparison_approve_cleanup($resolvedOutput, 'The approval artifact permissions could not be restricted to 0600');
        }
        clearstatcache(true, $resolvedOutput);
        $handleStat = fstat($handle);
        $pathStat = @lstat($resolvedOutput);
        $currentDirectoryStat = vg_comparison_approve_require_posix_directory($outputDirectory);
        if (!is_array($handleStat) || !is_array($pathStat) || (($handleStat['mode'] ?? 0) & 0170000) !== 0100000
            || (($handleStat['mode'] ?? 0) & 0777) !== 0600 || (($pathStat['mode'] ?? 0) & 0777) !== 0600
            || ($handleStat['dev'] ?? null) !== ($pathStat['dev'] ?? null) || ($handleStat['ino'] ?? null) !== ($pathStat['ino'] ?? null)
            || ($currentDirectoryStat['dev'] ?? null) !== ($outputDirectoryStat['dev'] ?? null)
            || ($currentDirectoryStat['ino'] ?? null) !== ($outputDirectoryStat['ino'] ?? null)) {
            fclose($handle);
            $handle = null;
            vg_comparison_approve_cleanup($resolvedOutput, 'The open approval artifact permissions or identity could not be verified');
        }
        $verifiedHandleStat = $handleStat;
        fclose($handle);
        $handle = null;
        clearstatcache(true, $resolvedOutput);
        $finalStat = @lstat($resolvedOutput);
        if (!is_array($finalStat) || (($finalStat['mode'] ?? 0) & 0170000) !== 0100000 || (($finalStat['mode'] ?? 0) & 0777) !== 0600
            || ($finalStat['dev'] ?? null) !== ($verifiedHandleStat['dev'] ?? null) || ($finalStat['ino'] ?? null) !== ($verifiedHandleStat['ino'] ?? null)) {
            vg_comparison_approve_cleanup($resolvedOutput, 'The final approval artifact was not a regular file');
        }
        $created = false;
    } finally {
        if (is_resource($handle)) {
            fclose($handle);
        }
        if ($created) {
            clearstatcache(true, $resolvedOutput);
            if (file_exists($resolvedOutput) || is_link($resolvedOutput)) {
                @unlink($resolvedOutput);
                clearstatcache(true, $resolvedOutput);
            }
            if (file_exists($resolvedOutput) || is_link($resolvedOutput)) {
                fwrite(STDERR, "Approval artifact cleanup failed.\n");
            }
        }
        umask($oldUmask);
    }
    fwrite(STDOUT, 'Approval artifact created.' . PHP_EOL);
}

vg_comparison_approve_main($argv);

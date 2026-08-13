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

function vg_comparison_approve_active_user(array $identity): object
{
    if (!function_exists('get_userdata')) {
        vg_comparison_approve_fail('Run the signer through WP-CLI after WordPress is loaded.');
    }
    $user = get_userdata((int) ($identity['wp_user_id'] ?? 0));
    if (!is_object($user) || (class_exists('WP_User') && !$user instanceof WP_User)) {
        vg_comparison_approve_fail('The registered WordPress user does not exist.');
    }
    if ((int) ($user->ID ?? 0) !== (int) $identity['wp_user_id']
        || (int) ($user->user_status ?? 1) !== 0
        || !empty($user->spam)
        || !empty($user->deleted)
        || !is_array($user->roles ?? null)
        || $user->roles === []
        || (string) ($user->display_name ?? '') !== (string) ($identity['display_name'] ?? '')) {
        vg_comparison_approve_fail('The registered WordPress user is not active or does not match the registry.');
    }
    if (function_exists('get_current_user_id') && get_current_user_id() !== (int) $user->ID) {
        vg_comparison_approve_fail('The current WordPress user must exactly match the signing identity.');
    }
    return $user;
}

function vg_comparison_approve_main(array $arguments): void
{
    $options = vg_comparison_approve_options($arguments);
    $required = ['registry', 'identity-id', 'role', 'manifest-hash', 'change-ids', 'change-reason', 'output'];
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
    $resolvedOutput = $outputDirectory . DIRECTORY_SEPARATOR . basename($outputPath);
    $registryPath = realpath($options['registry']);
    if ($registryPath === false || ($repoRoot !== false && !vg_comparison_approve_path_inside($registryPath, $repoRoot))) {
        vg_comparison_approve_fail('The identity registry must be the reviewed registry inside this repository.');
    }
    if (($repoRoot !== false && vg_comparison_approve_path_inside($resolvedOutput, $repoRoot))
        || ($webRoot !== false && vg_comparison_approve_path_inside($resolvedOutput, $webRoot))) {
        vg_comparison_approve_fail('The approval artifact must be outside the repository and web root.');
    }

    $registryText = @file_get_contents($registryPath);
    if (!is_string($registryText)) {
        vg_comparison_approve_fail('The identity registry could not be read.');
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
    try {
        $handle = fopen($outputPath, 'x');
        if ($handle === false) {
            vg_comparison_approve_fail('The output file already exists or cannot be created.');
        }
        $written = fwrite($handle, $encoded);
        if ($written !== strlen($encoded) || !fflush($handle)) {
            fclose($handle);
            vg_comparison_approve_fail('The approval artifact could not be written completely.');
        }
        fclose($handle);
        if (!chmod($outputPath, 0600)) {
            vg_comparison_approve_fail('The approval artifact permissions could not be restricted to 0600.');
        }
    } finally {
        umask($oldUmask);
    }
    fwrite(STDOUT, 'Approval artifact created.' . PHP_EOL);
}

vg_comparison_approve_main($argv);

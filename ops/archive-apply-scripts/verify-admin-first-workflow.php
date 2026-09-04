<?php
/**
 * Verify the VietnamGuide WordPress Admin-first workflow without changing data.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-admin-first-workflow.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_admin_first_cli_available(): bool
{
    return defined('WP_CLI') && WP_CLI;
}

function vg_verify_admin_first_finish(array $failures, array $notes): void
{
    foreach ($notes as $note) {
        if (vg_verify_admin_first_cli_available()) {
            WP_CLI::log($note);
        } else {
            echo $note . PHP_EOL;
        }
    }

    if ($failures !== []) {
        foreach ($failures as $failure) {
            if (vg_verify_admin_first_cli_available()) {
                WP_CLI::warning($failure);
            } else {
                echo 'Warning: ' . $failure . PHP_EOL;
            }
        }

        if (vg_verify_admin_first_cli_available()) {
            WP_CLI::error('Admin-first workflow verification failed.');
        }

        echo 'Admin-first workflow verification failed.' . PHP_EOL;
        exit(1);
    }

    if (vg_verify_admin_first_cli_available()) {
        WP_CLI::success('Admin-first workflow verification passed.');
        return;
    }

    echo 'Admin-first workflow verification passed.' . PHP_EOL;
}

function vg_verify_admin_first_post_label(WP_Post $post): string
{
    $permalink = get_permalink($post);
    $path = is_string($permalink) ? (string) parse_url($permalink, PHP_URL_PATH) : '';
    $path = trim($path, '/');

    if ($path === '') {
        $path = (string) $post->post_name;
    }

    return "{$post->post_type} #{$post->ID} /{$path}/";
}

$failures = [];
$notes = [];

foreach (
    [
        'vg_admin_first_meta_key',
        'vg_admin_first_post_is_admin_owned',
        'vg_admin_first_post_content_has_manual_drift',
        'vg_admin_first_meta_has_manual_drift',
        'vg_admin_first_protect_post_writes',
        'vg_admin_first_protect_post_meta',
    ] as $required_function
) {
    if (! function_exists($required_function)) {
        $failures[] = "Missing required Admin-first function: {$required_function}";
    }
}

if (! defined('VG_ADMIN_FIRST_META_KEYS')) {
    $failures[] = 'Missing VG_ADMIN_FIRST_META_KEYS constant.';
}

if (! has_filter('wp_insert_post_data', 'vg_admin_first_protect_post_writes')) {
    $failures[] = 'Missing wp_insert_post_data Admin-first write guard filter.';
}

if (! has_filter('update_post_metadata', 'vg_admin_first_protect_post_meta')) {
    $failures[] = 'Missing update_post_metadata Admin-first metadata guard filter.';
}

$posts = get_posts(
    [
        'post_type'      => ['page', 'post'],
        'post_status'    => ['publish', 'draft', 'future', 'pending', 'private'],
        'posts_per_page' => -1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ]
);

$admin_owned = [];
$automation_content_baselines = 0;
$automation_meta_baselines = 0;
$owner_without_lock = [];
$lock_without_owner = [];

foreach ($posts as $post) {
    if (! $post instanceof WP_Post) {
        continue;
    }

    $post_id = (int) $post->ID;
    $owner = trim((string) get_post_meta($post_id, 'vg_content_owner', true));
    $lock = trim((string) get_post_meta($post_id, 'vg_automation_lock', true));
    $content_hash = trim((string) get_post_meta($post_id, '_vg_last_automation_content_hash', true));

    if ($owner === 'wp_admin' || $lock === 'locked') {
        $admin_owned[] = vg_verify_admin_first_post_label($post);
    }

    if ($content_hash !== '') {
        $automation_content_baselines++;
    }

    $meta_hashes = get_post_meta($post_id);

    foreach (array_keys($meta_hashes) as $meta_key) {
        if (str_starts_with((string) $meta_key, '_vg_last_automation_meta_hash_')) {
            $automation_meta_baselines++;
        }
    }

    if ($owner === 'wp_admin' && $lock !== 'locked') {
        $owner_without_lock[] = vg_verify_admin_first_post_label($post);
    }

    if ($owner !== 'wp_admin' && $lock === 'locked') {
        $lock_without_owner[] = vg_verify_admin_first_post_label($post);
    }
}

if ($owner_without_lock !== []) {
    $failures[] = 'Content owner is wp_admin but automation lock is not locked: ' . implode('; ', $owner_without_lock);
}

if ($lock_without_owner !== []) {
    $failures[] = 'Automation lock is locked but content owner is not wp_admin: ' . implode('; ', $lock_without_owner);
}

$notes[] = 'Admin-first locked content: ' . count($admin_owned) . ' item(s).';
$notes[] = 'Automation content baselines: ' . $automation_content_baselines . ' item(s).';
$notes[] = 'Automation protected meta baselines: ' . $automation_meta_baselines . ' key baseline(s).';

if ($admin_owned !== []) {
    $notes[] = 'Locked items: ' . implode('; ', array_slice($admin_owned, 0, 20)) . (count($admin_owned) > 20 ? '; ...' : '');
}

vg_verify_admin_first_finish($failures, $notes);

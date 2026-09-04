<?php
/**
 * Mark selected VietnamGuide pages/posts as WordPress Admin-owned.
 *
 * Safe dry-run:
 * VG_ADMIN_FIRST_INCLUDE_PATHS=plan/vietnam-travel-guide wp eval-file ops/mark-wordpress-admin-owned-content.php --allow-root
 *
 * Apply to selected content:
 * VG_ADMIN_FIRST_INCLUDE_PATHS=plan/vietnam-travel-guide VG_ADMIN_FIRST_MARK_APPLY=1 wp eval-file ops/mark-wordpress-admin-owned-content.php --allow-root
 *
 * Apply to all non-trash pages/posts:
 * VG_ADMIN_FIRST_MARK_ALL=1 VG_ADMIN_FIRST_MARK_APPLY=1 wp eval-file ops/mark-wordpress-admin-owned-content.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_admin_first_marker_env_truthy(string $name): bool
{
    $value = getenv($name);

    if (! is_string($value)) {
        return false;
    }

    return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
}

function vg_admin_first_marker_csv_env(string $name, array $default = []): array
{
    $value = getenv($name);

    if (! is_string($value) || trim($value) === '') {
        return $default;
    }

    $items = [];

    foreach (explode(',', $value) as $item) {
        $item = trim($item);

        if ($item === '') {
            continue;
        }

        $items[] = $item;
    }

    return $items;
}

function vg_admin_first_marker_normalize_path(string $path): string
{
    $path = trim($path);
    $path = preg_replace('#^https?://[^/]+#i', '', $path) ?? $path;
    $path = strtok($path, '?') ?: $path;

    return trim($path, '/');
}

function vg_admin_first_marker_post_path(WP_Post $post): string
{
    if ($post->post_type === 'page') {
        $path = get_page_uri($post);

        if (is_string($path) && trim($path) !== '') {
            return trim($path, '/');
        }
    }

    $permalink = get_permalink($post);
    $path = is_string($permalink) ? (string) parse_url($permalink, PHP_URL_PATH) : '';
    $path = vg_admin_first_marker_normalize_path($path);

    return $path !== '' ? $path : (string) $post->post_name;
}

function vg_admin_first_marker_post_matches_scope(WP_Post $post, array $include_lookup, bool $mark_all): bool
{
    if ($mark_all) {
        return true;
    }

    $post_id = (string) $post->ID;
    $path = vg_admin_first_marker_post_path($post);
    $slug = (string) $post->post_name;

    return isset($include_lookup[$post_id])
        || isset($include_lookup[$path])
        || isset($include_lookup[$slug]);
}

$apply = vg_admin_first_marker_env_truthy('VG_ADMIN_FIRST_MARK_APPLY');
$mark_all = vg_admin_first_marker_env_truthy('VG_ADMIN_FIRST_MARK_ALL');
$post_types = vg_admin_first_marker_csv_env('VG_ADMIN_FIRST_POST_TYPES', ['page', 'post']);
$post_statuses = vg_admin_first_marker_csv_env('VG_ADMIN_FIRST_STATUSES', ['publish', 'draft', 'future', 'pending', 'private']);
$include_paths = vg_admin_first_marker_csv_env('VG_ADMIN_FIRST_INCLUDE_PATHS');
$include_lookup = [];

foreach ($include_paths as $include_path) {
    $normalized = vg_admin_first_marker_normalize_path($include_path);

    if ($normalized !== '') {
        $include_lookup[$normalized] = true;
    }
}

if (! $mark_all && $include_lookup === []) {
    WP_CLI::error('No scope provided. Set VG_ADMIN_FIRST_INCLUDE_PATHS=path-a,path-b or VG_ADMIN_FIRST_MARK_ALL=1. Default mode is dry-run unless VG_ADMIN_FIRST_MARK_APPLY=1 is also set.');
}

$posts = get_posts(
    [
        'post_type'      => $post_types,
        'post_status'    => $post_statuses,
        'posts_per_page' => -1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'fields'         => 'all',
    ]
);

$matched = [];

foreach ($posts as $post) {
    if (! $post instanceof WP_Post || ! vg_admin_first_marker_post_matches_scope($post, $include_lookup, $mark_all)) {
        continue;
    }

    $matched[] = $post;
}

if ($matched === []) {
    WP_CLI::error('No matching pages/posts found for the requested Admin-first scope.');
}

$today = wp_date('F j, Y');

foreach ($matched as $post) {
    $post_id = (int) $post->ID;
    $label = $post->post_type . ' #' . $post_id . ' /' . vg_admin_first_marker_post_path($post) . '/';

    if (! $apply) {
        WP_CLI::log('[dry-run] Would mark ' . $label . ' as WordPress Admin-owned and automation-locked.');
        continue;
    }

    update_post_meta($post_id, 'vg_content_owner', 'wp_admin');
    update_post_meta($post_id, 'vg_automation_lock', 'locked');
    update_post_meta($post_id, 'vg_last_manual_review', $today);

    $existing_notes = trim((string) get_post_meta($post_id, 'vg_admin_first_notes', true));
    $note = 'Admin-first ownership applied by WP-CLI on ' . $today . '. Future protected content, Rank Math, and EEAT edits should be made in WordPress Admin unless an explicit backup-and-override run is approved.';

    if ($existing_notes === '') {
        update_post_meta($post_id, 'vg_admin_first_notes', $note);
    }

    WP_CLI::log('Marked ' . $label . ' as WordPress Admin-owned and automation-locked.');
}

$mode = $apply ? 'apply' : 'dry-run';
WP_CLI::success('Admin-first marker completed in ' . $mode . ' mode for ' . count($matched) . ' item(s).');

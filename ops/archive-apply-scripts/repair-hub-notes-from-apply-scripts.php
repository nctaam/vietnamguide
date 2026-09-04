<?php
/**
 * Restore verifier-required hub-note blocks from apply scripts.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/repair-hub-notes-from-apply-scripts.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_repair_hub_notes_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_repair_hub_notes_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_repair_hub_notes_required_markers(string $verifier_path): array
{
    $source = file_get_contents($verifier_path);

    if (! is_string($source) || trim($source) === '') {
        vg_repair_hub_notes_fail("Could not read verifier: {$verifier_path}");
    }

    preg_match_all('/vg-[a-z0-9-]+-hub-note:v\d+/i', $source, $matches);
    $markers = array_values(array_unique($matches[0] ?? []));
    sort($markers);

    if ($markers === []) {
        vg_repair_hub_notes_fail('No verifier-required hub-note markers were found.');
    }

    return $markers;
}

function vg_repair_hub_notes_target_path(string $marker): ?string
{
    if (str_contains($marker, '-plan-hub-note:')) {
        return 'plan';
    }

    if (str_contains($marker, '-destinations-hub-note:')) {
        return 'destinations';
    }

    if (str_contains($marker, '-compare-hub-note:') || str_contains($marker, '-comparison-hub-note:')) {
        return 'compare';
    }

    if (str_contains($marker, '-costs-hub-note:')) {
        return 'costs';
    }

    if (str_contains($marker, '-itineraries-hub-note:') || str_starts_with($marker, 'vg-itinerary-')) {
        return 'itineraries';
    }

    return null;
}

function vg_repair_hub_notes_extract_block(string $apply_source, string $marker): ?string
{
    $comment = '<!-- ' . $marker . ' -->';
    $pattern = '/' . preg_quote($comment, '/') . '.*?(<!-- wp:group\b.*?<!-- \/wp:group -->)/s';

    if (! preg_match($pattern, $apply_source, $match)) {
        return null;
    }

    $block = trim((string) $match[1]);

    return $block === '' ? null : $block;
}

function vg_repair_hub_notes_internal_path_from_href(string $href): ?string
{
    $href = trim(html_entity_decode($href, ENT_QUOTES, 'UTF-8'));

    if ($href === '' || str_starts_with($href, '#') || preg_match('/^(mailto|tel):/i', $href)) {
        return null;
    }

    if (str_starts_with($href, '/')) {
        $path = parse_url($href, PHP_URL_PATH);
    } elseif (preg_match('/^https?:\/\//i', $href)) {
        $site_host = parse_url(home_url('/'), PHP_URL_HOST);
        $href_host = parse_url($href, PHP_URL_HOST);

        if (! $site_host || ! $href_host || strcasecmp($site_host, $href_host) !== 0) {
            return null;
        }

        $path = parse_url($href, PHP_URL_PATH);
    } else {
        return null;
    }

    if (! is_string($path)) {
        return null;
    }

    $path = trim($path, '/');

    return $path === '' ? 'home' : $path;
}

function vg_repair_hub_notes_assert_internal_links_published(string $label, string $block): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $block, $matches);
    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_repair_hub_notes_internal_path_from_href((string) $href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        $page = $path === 'home'
            ? get_post((int) get_option('page_on_front'))
            : get_page_by_path($path, OBJECT, 'page');

        if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
            vg_repair_hub_notes_fail("{$label} links to an unpublished internal path: /{$path}/");
        }
    }
}

function vg_repair_hub_notes_upsert(WP_Post $hub, string $marker, string $block): string
{
    $comment = '<!-- ' . $marker . ' -->';
    $marked_block = $comment . PHP_EOL . trim($block);
    $content = (string) $hub->post_content;
    $action = 'added';

    if (str_contains($content, $comment)) {
        $updated = preg_replace(
            '/' . preg_quote($comment, '/') . '\s*<!-- wp:group\b.*?<!-- \/wp:group -->/s',
            $marked_block,
            $content,
            1,
            $count
        );

        if (! is_string($updated) || $count !== 1) {
            vg_repair_hub_notes_fail("Could not replace hub note: {$marker}");
        }

        $action = $updated === $content ? 'unchanged' : 'replaced';
    } else {
        $updated = rtrim($content) . "\n\n" . $marked_block;
    }

    if ($updated === $content) {
        return $action;
    }

    $result = wp_update_post(
        [
            'ID'           => (int) $hub->ID,
            'post_content' => $updated,
        ],
        true
    );

    if (is_wp_error($result)) {
        vg_repair_hub_notes_fail("Could not update hub {$hub->post_name}: " . $result->get_error_message());
    }

    return $action;
}

$ops_dir = __DIR__;
$required_markers = vg_repair_hub_notes_required_markers($ops_dir . '/verify-eeat-content.php');
$apply_paths = glob($ops_dir . '/apply-*.php') ?: [];
sort($apply_paths);

$found_blocks = [];

foreach ($required_markers as $marker) {
    foreach ($apply_paths as $apply_path) {
        $source = file_get_contents($apply_path);

        if (! is_string($source)) {
            continue;
        }

        $block = vg_repair_hub_notes_extract_block($source, $marker);

        if ($block !== null) {
            $found_blocks[$marker] = [
                'block'  => $block,
                'source' => basename($apply_path),
            ];
            break;
        }
    }
}

$missing = array_values(array_diff($required_markers, array_keys($found_blocks)));

if ($missing !== []) {
    vg_repair_hub_notes_fail('Could not locate hub-note blocks for verifier-required markers: ' . implode(', ', $missing));
}

$updated = 0;
$by_target = [];

foreach ($found_blocks as $marker => $record) {
    $target_path = vg_repair_hub_notes_target_path($marker);

    if ($target_path === null) {
        vg_repair_hub_notes_fail("Could not map hub target for marker: {$marker}");
    }

    $hub = get_page_by_path($target_path, OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_repair_hub_notes_fail("Hub page is not published: {$target_path}");
    }

    $block = (string) $record['block'];
    vg_repair_hub_notes_assert_internal_links_published($marker, $block);
    $action = vg_repair_hub_notes_upsert($hub, $marker, $block);
    $by_target[$target_path] = ($by_target[$target_path] ?? 0) + 1;

    if ($action !== 'unchanged') {
        $updated++;
    }

    vg_repair_hub_notes_log("{$action}: {$marker} from {$record['source']} -> /{$target_path}/");
}

foreach ($by_target as $target => $count) {
    clean_post_cache(get_page_by_path($target, OBJECT, 'page'));
    vg_repair_hub_notes_log("Checked {$count} verifier-required hub notes for /{$target}/.");
}

vg_repair_hub_notes_log("Hub-note repair complete. Updated {$updated} notes.");

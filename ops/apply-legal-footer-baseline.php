<?php
/**
 * Apply launch-safe legal baseline and footer navigation.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-legal-footer-baseline.php --allow-root
 *
 * To overwrite an already-reviewed Privacy Policy baseline:
 * VG_FORCE_PRIVACY_BASELINE=1 wp eval-file ops/apply-legal-footer-baseline.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_ops_env_flag(string $name): bool
{
    $value = getenv($name);

    if (! is_string($value)) {
        return false;
    }

    return in_array(strtolower($value), ['1', 'true', 'yes', 'force'], true);
}

function vg_ops_url_path_key(string $url): string
{
    $path = wp_parse_url($url, PHP_URL_PATH);

    if (! is_string($path) || '' === $path) {
        $path = $url;
    }

    $path = '/' . ltrim($path, '/');

    return '/' === $path ? '/' : untrailingslashit($path);
}

function vg_ops_should_overwrite_privacy(string $current_content): bool
{
    $trimmed_content = trim($current_content);

    return vg_ops_env_flag('VG_FORCE_PRIVACY_BASELINE')
        || '' === $trimmed_content
        || str_contains($current_content, 'Suggested text');
}

function vg_ops_update_missing_post_meta(int $post_id, string $meta_key, string $meta_value): void
{
    $current_value = trim((string) get_post_meta($post_id, $meta_key, true));

    if ('' === $current_value) {
        update_post_meta($post_id, $meta_key, $meta_value);
    }
}

function vg_ops_ensure_page_id(string $path, string $title): int
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if ($page instanceof WP_Post) {
        return (int) $page->ID;
    }

    $page_id = wp_insert_post(
        [
            'post_title'   => $title,
            'post_name'    => $path,
            'post_type'    => 'page',
            'post_status'  => 'draft',
            'post_content' => '',
        ],
        true
    );

    if (is_wp_error($page_id)) {
        vg_ops_fail("Could not create page {$path}: " . $page_id->get_error_message());
    }

    vg_ops_log("Created page: {$path}");

    return (int) $page_id;
}

function vg_ops_ensure_menu(string $name, string $location, array $items): void
{
    $menu = wp_get_nav_menu_object($name);

    if (! $menu) {
        $menu_id = wp_create_nav_menu($name);

        if (is_wp_error($menu_id)) {
            vg_ops_fail("Could not create menu {$name}: " . $menu_id->get_error_message());
        }
    } else {
        $menu_id = (int) $menu->term_id;
    }

    $existing_items = wp_get_nav_menu_items($menu_id) ?: [];
    $existing_paths = [];

    foreach ($existing_items as $item) {
        $existing_paths[vg_ops_url_path_key((string) $item->url)] = true;
    }

    $position = count($existing_items);

    foreach ($items as $item) {
        $url = home_url($item['path']);
        $path_key = vg_ops_url_path_key($item['path']);

        if (isset($existing_paths[$path_key])) {
            continue;
        }

        $position++;
        $item_id = wp_update_nav_menu_item(
            $menu_id,
            0,
            [
                'menu-item-title'  => $item['title'],
                'menu-item-url'    => $url,
                'menu-item-status' => 'publish',
                'menu-item-type'   => 'custom',
                'menu-item-position' => $position,
            ]
        );

        if (is_wp_error($item_id)) {
            vg_ops_fail("Could not add menu item {$item['title']} to {$name}: " . $item_id->get_error_message());
        }
    }

    $locations = get_nav_menu_locations();
    $locations[$location] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);

    vg_ops_log("Ensured menu: {$name}");
}

$privacy_id = vg_ops_ensure_page_id('privacy-policy', 'Privacy Policy');
$privacy_post = get_post($privacy_id);

if (! $privacy_post instanceof WP_Post) {
    vg_ops_fail("Privacy Policy page could not be loaded: {$privacy_id}");
}

$privacy_marker = '<!-- vg-privacy-baseline:v1 -->';

$privacy_content_body = <<<'HTML'
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Last updated: July 13, 2026</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"vg-hub-lede"} -->
<p class="vg-hub-lede">VietnamGuide.net is an editorial travel planning website for international visitors to Vietnam. This policy explains the information the site may collect, how it may be used, and the choices readers can make.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Information we collect</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>When you visit the site, standard server logs may record information such as your IP address, browser, device type, referring page, requested pages, and visit time. If you contact us or subscribe to a future newsletter, we may collect the information you choose to provide, such as your email address and message content.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Cookies and analytics</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>The site may use essential cookies for WordPress functionality, security, caching, and logged-in administrator sessions. Analytics or measurement tools may be added to understand aggregate traffic, popular pages, and technical performance. Where possible, these tools should be configured to minimize unnecessary personal data collection.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Affiliate links and outbound services</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Some links may lead to third-party booking platforms, travel providers, affiliate partners, embedded content, maps, videos, or tools. Those services may collect information under their own privacy policies. VietnamGuide.net is not responsible for the privacy practices of third-party websites.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">How information is used</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list vg-feature-list">
<li>To keep the website secure, stable, and accessible.</li>
<li>To understand which guides and planning topics are useful to readers.</li>
<li>To respond to voluntary messages or newsletter requests.</li>
<li>To maintain editorial, legal, and operational records where needed.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Comments, accounts, and uploads</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Public comments and visitor account registration are currently disabled for regular readers. If those features are enabled in the future, this policy should be updated before launch of that feature.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Data retention and choices</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Operational logs and messages may be retained for security, troubleshooting, and business records. Readers may contact VietnamGuide.net to ask about personal information they have voluntarily provided. Some records may need to be retained where required for security, legal, or administrative reasons.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Contact</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>For privacy questions, contact <a href="mailto:contact@vietnamguide.net">contact@vietnamguide.net</a>.</p>
<!-- /wp:paragraph -->
HTML;

$privacy_content = $privacy_marker . "\n" . $privacy_content_body;
$privacy_baseline_hash = hash('sha256', $privacy_content);
$should_overwrite_privacy = vg_ops_should_overwrite_privacy((string) $privacy_post->post_content);

$privacy_update = [
    'ID'             => $privacy_id,
    'post_title'     => 'Privacy Policy',
    'post_name'      => 'privacy-policy',
    'post_status'    => 'publish',
    'comment_status' => 'closed',
    'ping_status'    => 'closed',
];

if ($should_overwrite_privacy) {
    $privacy_update['post_content'] = $privacy_content;
    $privacy_update['post_excerpt'] = 'Privacy Policy for VietnamGuide.net, covering site logs, cookies, analytics, affiliate links, contact choices, and reader data requests.';
}

$result = wp_update_post($privacy_update, true);

if (is_wp_error($result)) {
    vg_ops_fail('Could not publish Privacy Policy: ' . $result->get_error_message());
}

if ($should_overwrite_privacy) {
    update_post_meta($privacy_id, 'rank_math_title', 'Privacy Policy');
    update_post_meta($privacy_id, 'rank_math_description', 'Privacy Policy for VietnamGuide.net, covering site logs, cookies, analytics, affiliate links, contact choices, and reader data requests.');
    update_post_meta($privacy_id, 'rank_math_focus_keyword', 'privacy policy');
    update_post_meta($privacy_id, '_vg_privacy_baseline_hash', $privacy_baseline_hash);
    update_post_meta($privacy_id, '_vg_privacy_baseline_version', 'v1');
} else {
    vg_ops_update_missing_post_meta($privacy_id, 'rank_math_title', 'Privacy Policy');
    vg_ops_update_missing_post_meta($privacy_id, 'rank_math_description', 'Privacy Policy for VietnamGuide.net, covering site logs, cookies, analytics, affiliate links, contact choices, and reader data requests.');
    vg_ops_update_missing_post_meta($privacy_id, 'rank_math_focus_keyword', 'privacy policy');
    vg_ops_log('Preserved existing Privacy Policy content and non-empty Rank Math metadata.');
}

update_option('wp_page_for_privacy_policy', $privacy_id);

vg_ops_ensure_menu('Footer Plan', 'footer_plan', [
    ['title' => 'Planning hub', 'path' => '/plan/'],
    ['title' => 'Itineraries', 'path' => '/itineraries/'],
    ['title' => 'Costs', 'path' => '/costs/'],
    ['title' => 'Newsletter', 'path' => '/newsletter/'],
]);

vg_ops_ensure_menu('Footer Destinations', 'footer_destinations', [
    ['title' => 'Destinations', 'path' => '/destinations/'],
    ['title' => 'Compare places', 'path' => '/compare/'],
]);

vg_ops_ensure_menu('Footer Legal', 'footer_legal', [
    ['title' => 'Privacy Policy', 'path' => '/privacy-policy/'],
    ['title' => 'Affiliate Disclosure', 'path' => '/affiliate-disclosure/'],
]);

flush_rewrite_rules(false);
vg_ops_log(($should_overwrite_privacy ? 'Published privacy policy baseline' : 'Ensured privacy policy page') . ": {$privacy_id}");
vg_ops_log('Flushed rewrite rules.');

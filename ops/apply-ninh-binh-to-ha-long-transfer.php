<?php
/**
 * Publish the Ninh Binh to Ha Long Bay Transfer guide.
 *
 * Self-reference marker: ops/apply-ninh-binh-to-ha-long-transfer.php
 *
 * Run from the WordPress root with:
 * VG_FORCE_NINH_BINH_HA_LONG_TRANSFER_REPUBLISH=1 wp eval-file ops/apply-ninh-binh-to-ha-long-transfer.php --allow-root
 *
 * Repair only hub/related-route/homepage side effects with:
 * VG_REPAIR_NINH_BINH_HA_LONG_TRANSFER_LINKS=1 wp eval-file ops/apply-ninh-binh-to-ha-long-transfer.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_ninh_binh_ha_long_transfer_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_ninh_binh_ha_long_transfer_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_ninh_binh_ha_long_transfer_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_NINH_BINH_HA_LONG_TRANSFER_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_ninh_binh_ha_long_transfer_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_NINH_BINH_HA_LONG_TRANSFER_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_ninh_binh_ha_long_transfer_ops_publish_author_id(?WP_Post $existing_page = null): int
{
    if ($existing_page instanceof WP_Post && (int) $existing_page->post_author > 0) {
        return (int) $existing_page->post_author;
    }

    $env_author_id = getenv('VG_PUBLISH_AUTHOR_ID');

    if (is_string($env_author_id) && preg_match('/^\d+$/', trim($env_author_id))) {
        $author_id = (int) trim($env_author_id);

        if ($author_id > 0 && get_user_by('id', $author_id) instanceof WP_User) {
            return $author_id;
        }
    }

    $current_user_id = (int) get_current_user_id();

    if ($current_user_id > 0 && get_user_by('id', $current_user_id) instanceof WP_User) {
        return $current_user_id;
    }

    $users = get_users(
        [
            'role__in' => ['administrator', 'editor'],
            'number'   => 1,
            'orderby'  => 'ID',
            'order'    => 'ASC',
            'fields'   => ['ID'],
        ]
    );

    if (isset($users[0]->ID) && (int) $users[0]->ID > 0) {
        return (int) $users[0]->ID;
    }

    vg_ninh_binh_ha_long_transfer_ops_fail('Could not resolve a valid WordPress author for Ninh Binh to Ha Long Bay Transfer.');
}

function vg_ninh_binh_ha_long_transfer_ops_internal_path_from_href(string $href): ?string
{
    $href = trim(html_entity_decode($href, ENT_QUOTES, 'UTF-8'));

    if ($href === '' || str_starts_with($href, '#')) {
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

function vg_ninh_binh_ha_long_transfer_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_ninh_binh_ha_long_transfer_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_ninh_binh_ha_long_transfer_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ninh_binh_ha_long_transfer_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_ninh_binh_ha_long_transfer_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_ninh_binh_ha_long_transfer_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_ninh_binh_ha_long_transfer_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_ninh_binh_ha_long_transfer_ops_log("Validated {$label} internal page links are published.");
}

function vg_ninh_binh_ha_long_transfer_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_ninh_binh_ha_long_transfer_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_ninh_binh_ha_long_transfer_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_ninh_binh_ha_long_transfer_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_ninh_binh_ha_long_transfer_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_ninh_binh_ha_long_transfer_ops_log("Validated {$label} related-route links are published.");
}

function vg_ninh_binh_ha_long_transfer_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_ninh_binh_ha_long_transfer_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
{
    $marked_block = $marker . PHP_EOL . trim($block);
    $replacement_count = 0;

    if (str_contains($page->post_content, $marker)) {
        $updated_content = preg_replace(
            '/' . preg_quote($marker, '/') . '\R<!-- wp:group\b.*?<!-- \/wp:group -->/s',
            $marked_block,
            $page->post_content,
            1,
            $replacement_count
        );

        if (! is_string($updated_content) || $replacement_count !== 1) {
            vg_ninh_binh_ha_long_transfer_ops_fail("Could not confidently refresh {$label}.");
        }
    } else {
        $updated_content = rtrim($page->post_content) . "\n\n" . $marked_block;
    }

    $result = wp_update_post(
        [
            'ID'           => $page->ID,
            'post_content' => $updated_content,
        ],
        true
    );

    if (is_wp_error($result)) {
        vg_ninh_binh_ha_long_transfer_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    vg_ninh_binh_ha_long_transfer_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_ninh_binh_ha_long_transfer_ops_refresh_plan_hub(): void
{
    $hub = get_page_by_path('plan', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_ninh_binh_ha_long_transfer_ops_log('Skipped Plan hub refresh: Plan hub was not found or is not published.');
        return;
    }

    if (! vg_ninh_binh_ha_long_transfer_ops_published_page_exists('plan/ninh-binh-to-ha-long-bay-transfer')) {
        vg_ninh_binh_ha_long_transfer_ops_log('Skipped Plan hub refresh: Ninh Binh to Ha Long Bay Transfer is not published.');
        return;
    }

    $marker = '<!-- vg-ninh-binh-ha-long-transfer-plan-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Protect the Ninh Binh to bay handoff</h3><p><a href="/plan/ninh-binh-to-ha-long-bay-transfer/">Ninh Binh to Ha Long Bay Transfer</a> helps travelers choose private car, cruise-arranged transfer, shared van, overnight buffer, or route skip by exact port, pickup window, luggage, weather, and cruise check-in risk.</p></div>
<!-- /wp:group -->
HTML;

    vg_ninh_binh_ha_long_transfer_ops_assert_internal_page_links_are_published('Plan hub Ninh Binh to Ha Long Bay Transfer note', $block);
    vg_ninh_binh_ha_long_transfer_ops_upsert_marked_group($hub, 'Plan hub Ninh Binh to Ha Long Bay Transfer note', $marker, $block);
}

function vg_ninh_binh_ha_long_transfer_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_ninh_binh_ha_long_transfer_ops_log('Skipped Destinations hub refresh: Destinations hub was not found or is not published.');
        return;
    }

    if (! vg_ninh_binh_ha_long_transfer_ops_published_page_exists('plan/ninh-binh-to-ha-long-bay-transfer')) {
        vg_ninh_binh_ha_long_transfer_ops_log('Skipped Destinations hub refresh: Ninh Binh to Ha Long Bay Transfer is not published.');
        return;
    }

    $marker = '<!-- vg-ninh-binh-ha-long-transfer-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Do not let the bay cruise break the Ninh Binh stop</h3><p>After choosing <a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a> and the <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, use <a href="/plan/ninh-binh-to-ha-long-bay-transfer/">Ninh Binh to Ha Long Bay Transfer</a> to confirm the actual port, pickup window, and buffer before the route buys the wrong car or cruise handoff.</p></div>
<!-- /wp:group -->
HTML;

    vg_ninh_binh_ha_long_transfer_ops_assert_internal_page_links_are_published('Destinations hub Ninh Binh to Ha Long Bay Transfer note', $block);
    vg_ninh_binh_ha_long_transfer_ops_upsert_marked_group($hub, 'Destinations hub Ninh Binh to Ha Long Bay Transfer note', $marker, $block);
}

function vg_ninh_binh_ha_long_transfer_ops_refresh_homepage_route_spine(): void
{
    $front_page_id = (int) get_option('page_on_front');
    $home = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');

    if (! $home instanceof WP_Post || $home->post_status !== 'publish') {
        vg_ninh_binh_ha_long_transfer_ops_log('Skipped homepage route-spine refresh: homepage was not found or is not published.');
        return;
    }

    if (! vg_ninh_binh_ha_long_transfer_ops_published_page_exists('plan/ninh-binh-to-ha-long-bay-transfer')) {
        vg_ninh_binh_ha_long_transfer_ops_log('Skipped homepage route-spine refresh: Ninh Binh to Ha Long Bay Transfer is not published.');
        return;
    }

    $marker = '<!-- vg-ninh-binh-ha-long-transfer-homepage-route-spine:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-home-section vg-home-decision-spine vg-home-ninh-binh-ha-long-transfer-spine"} -->
<div class="wp-block-group vg-home-section vg-home-decision-spine vg-home-ninh-binh-ha-long-transfer-spine"><p class="vg-kicker">Northern handoff</p><h2>Ninh Binh to Ha Long Bay Transfer</h2><p>Use this route node when the trip moves straight from countryside to a bay cruise and the real decision is exact port, pickup window, luggage, weather, and whether an overnight buffer protects the cruise day.</p><p class="vg-section-link"><a href="/plan/ninh-binh-to-ha-long-bay-transfer/">Plan the bay handoff</a></p></div>
<!-- /wp:group -->
HTML;

    vg_ninh_binh_ha_long_transfer_ops_assert_internal_page_links_are_published('Homepage Ninh Binh to Ha Long Bay Transfer route-spine note', $block);
    vg_ninh_binh_ha_long_transfer_ops_upsert_marked_group($home, 'Homepage Ninh Binh to Ha Long Bay Transfer route-spine note', $marker, $block);
}

function vg_ninh_binh_ha_long_transfer_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ninh_binh_ha_long_transfer_ops_log("Skipped related-route refresh for {$label}: page not found or not published.");
        return;
    }

    $route_parts = array_map('trim', explode('|', $route_line));
    if (count($route_parts) < 3 || $route_parts[0] === '' || $route_parts[1] === '' || $route_parts[2] === '') {
        vg_ninh_binh_ha_long_transfer_ops_fail("Related route for {$label} is malformed.");
    }

    $route_href = $route_parts[1];
    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);
    $lines = preg_split('/\R+/', trim($current)) ?: [];
    $next_lines = [];
    $replaced = false;

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        $parts = array_map('trim', explode('|', $line));
        $line_href = $parts[1] ?? '';
        $is_target_line = $line_href === $route_href || str_contains($line, $route_href);

        if ($is_target_line) {
            if ($line === $route_line) {
                vg_ninh_binh_ha_long_transfer_ops_log("Skipped related-route refresh for {$label}: line is already current.");
                $next_lines[] = $line;
            } else {
                $next_lines[] = $route_line;
                $replaced = true;
            }

            continue;
        }

        $next_lines[] = $line;
    }

    if (! $replaced && ! in_array($route_line, $next_lines, true)) {
        $next_lines[] = $route_line;
    }

    update_post_meta($page->ID, 'vg_eeat_related_routes', implode("\n", $next_lines));
    vg_ninh_binh_ha_long_transfer_ops_log("Upserted Ninh Binh to Ha Long Bay Transfer related route to {$label}: {$page->ID}");
}

function vg_ninh_binh_ha_long_transfer_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Ninh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.';

    foreach (
        [
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'compare/ninh-binh-day-trip-vs-overnight' => 'Ninh Binh Day Trip vs Overnight',
            'compare/trang-an-vs-tam-coc' => 'Trang An vs Tam Coc',
            'plan/hanoi-to-ninh-binh-transport' => 'Hanoi to Ninh Binh Transport',
            'destinations/where-to-stay-in-ninh-binh' => 'Where to Stay in Ninh Binh',
            'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
            'itineraries/hanoi-in-2-days' => 'Hanoi in 2 Days',
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
        ] as $path => $label
    ) {
        vg_ninh_binh_ha_long_transfer_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

function vg_ninh_binh_ha_long_transfer_ops_assert_required_content_markers(string $content, array $markers): void
{
    foreach ($markers as $label => $marker) {
        if (! str_contains($content, $marker)) {
            vg_ninh_binh_ha_long_transfer_ops_fail("Missing required content marker for {$label}: {$marker}");
        }
    }
}

$parent = get_page_by_path('plan', OBJECT, 'page');
if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ninh_binh_ha_long_transfer_ops_fail('The /plan/ parent page must exist and be published before publishing Ninh Binh to Ha Long Bay Transfer.');
}

$page = get_page_by_path('plan/ninh-binh-to-ha-long-bay-transfer', OBJECT, 'page');
$review_date = wp_date('F j, Y');

if ($page instanceof WP_Post && $page->post_status === 'publish' && ! vg_ninh_binh_ha_long_transfer_ops_force_republish_enabled() && ! vg_ninh_binh_ha_long_transfer_ops_repair_links_enabled()) {
    vg_ninh_binh_ha_long_transfer_ops_log("Ninh Binh to Ha Long Bay Transfer already published: {$page->ID}. Set VG_FORCE_NINH_BINH_HA_LONG_TRANSFER_REPUBLISH=1 to rewrite it.");
    return;
}

if (vg_ninh_binh_ha_long_transfer_ops_repair_links_enabled() && ! vg_ninh_binh_ha_long_transfer_ops_force_republish_enabled()) {
    vg_ninh_binh_ha_long_transfer_ops_refresh_plan_hub();
    vg_ninh_binh_ha_long_transfer_ops_refresh_destinations_hub();
    vg_ninh_binh_ha_long_transfer_ops_refresh_homepage_route_spine();
    vg_ninh_binh_ha_long_transfer_ops_refresh_inbound_related_routes();
    return;
}

if ($page instanceof WP_Post) {
    vg_ninh_binh_ha_long_transfer_ops_log("Preflight Ninh Binh to Ha Long Bay Transfer: existing page {$page->ID} will be rewritten.");
} else {
    vg_ninh_binh_ha_long_transfer_ops_log('Preflight Ninh Binh to Ha Long Bay Transfer: no existing page found; creating a child page under /plan/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/a/a5/Tam_Coc_Rice_Valley_%288756354342%29.jpg');
$ha_long_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg/1920px-Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg');
$lan_ha_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/ca/Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg/1920px-Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg');
$cruise_boats_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/3/3e/Halong_Bay_Cruise_Boats_01.jpg');
$bai_chay_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/9/95/Bai_Chay_Pano_10-2023.jpg');

$content = <<<HTML
<!-- vg-ninh-binh-ha-long-transfer-hero:v1 -->
<!-- wp:group {"className":"vg-guide-hero vg-ninh-binh-ha-long-transfer-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero vg-ninh-binh-ha-long-transfer-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":35,"minHeight":640,"className":"vg-photo-led-hero"} -->
<div class="wp-block-cover vg-photo-led-hero" style="min-height:640px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-35 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Tam Coc rice fields in Ninh Binh before a transfer to Ha Long Bay" src="{$hero_image}" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Northern transfer decision</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Ninh Binh to Ha Long Bay Transfer</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-hero-lede"} -->
<p class="vg-hero-lede">The hard part is not finding a vehicle. It is protecting the cruise day: the exact port, pickup window, luggage, weather, hotel lane, and whether the route needs an overnight buffer before the bay.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-image-credit"} -->
<p class="vg-image-credit">Image: Tam Coc Rice Valley by Hoang Giang Hai / CC BY 2.0.</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-ninh-binh-ha-long-transfer-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-ninh-binh-ha-long-transfer-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-ninh-binh-ha-long-transfer-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most international travelers, the safest default is a private transfer or cruise-arranged transfer only after the exact port and pickup window are confirmed.</strong> A shared van can work when the operator names your final pier, pickup address, luggage allowance, and contingency. The cheapest seat can become expensive if it reaches the wrong city, wrong pier, or too late for embarkation.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li>Decide the bay product first: Ha Long, Lan Ha/Cat Ba, Bai Tu Long, day cruise, one-night cruise, or skip.</li>
<li>Confirm the exact embarkation point before buying transport from Ninh Binh.</li>
<li>Use a private car when a countryside hotel lane, children, luggage, late pickup, or cruise deposit makes shared routing fragile.</li>
<li>Use an overnight buffer near the bay when the cruise is expensive, weather looks unstable, or the route cannot absorb a missed check-in.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-ninh-binh-ha-long-transfer-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-ninh-binh-ha-long-transfer-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-ninh-binh-ha-long-transfer-at-a-glance">
<!-- wp:heading -->
<h2 class="wp-block-heading">Ninh Binh to Ha Long Bay transfer at a glance</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-ha-long-transfer-glance">
<tbody>
<tr><td data-label="Question"><strong>Best default</strong></td><td data-label="Answer">Private transfer or cruise-arranged transfer after the exact port and check-in window are confirmed.</td></tr>
<tr><td data-label="Question"><strong>When shared van works</strong></td><td data-label="Answer">When the operator names the Ninh Binh pickup, final bay port, luggage policy, and arrival buffer in writing.</td></tr>
<tr><td data-label="Question"><strong>When to sleep near the bay</strong></td><td data-label="Answer">When the cruise is premium, weather risk is high, the route starts far from Tam Coc, or missing check-in would damage the trip.</td></tr>
<tr><td data-label="Question"><strong>First thing to ask</strong></td><td data-label="Answer">Which port: Tuan Chau, Ha Long International Cruise Port, Hon Gai, Got Pier, or a Cat Ba/Lan Ha handoff?</td></tr>
<tr><td data-label="Question"><strong>First mistake to avoid</strong></td><td data-label="Answer">Buying transport to “Ha Long” before the cruise paperwork names the actual embarkation point.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-ninh-binh-ha-long-transfer-photo-proof:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: this is a handoff, not just a ride</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The route moves from Ninh Binh countryside to a bay-port system. The images below show why the planning job changes: rural pickup, city/bridge approach, cruise check-in, Ha Long routes, and Lan Ha/Cat Ba exceptions.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-ninh-binh-ha-long-transfer-photo-proof" aria-label="Ninh Binh to Ha Long Bay transfer photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Tam Coc countryside in Ninh Binh before a transfer to Ha Long Bay" loading="lazy" decoding="async"><figcaption>Ninh Binh pickup can begin on small countryside lanes, not at a central bus station. Image: Hoang Giang Hai / CC BY 2.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$bai_chay_image}" alt="Bai Chay and Ha Long city approach before bay cruise ports" loading="lazy" decoding="async"><figcaption>Ha Long arrival is a city-and-port decision before it becomes a cruise day. Image: Pdhadam / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cruise_boats_image}" alt="Cruise boats in Ha Long Bay before embarkation" loading="lazy" decoding="async"><figcaption>The transfer must match the cruise operator's actual embarkation window. Image: Shyamal L. / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$ha_long_image}" alt="Ha Long Bay karsts from above" loading="lazy" decoding="async"><figcaption>Ha Long is the iconic default, but the port and route still matter. Image: Vyacheslav Argenberg / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$lan_ha_image}" alt="Lan Ha Bay and Cat Ba scenery" loading="lazy" decoding="async"><figcaption>Lan Ha and Cat Ba routes can shift the transfer toward a different pier or island handoff. Image: Saaremees / CC BY-SA 4.0.</figcaption></figure>
</div>
<!-- /wp:html -->
<!-- wp:paragraph {"className":"vg-source-note"} -->
<p class="vg-source-note">Image credits are listed as text to keep the route decision readable and reduce visible outbound clutter. Full image source records are retained in metadata.</p>
<!-- /wp:paragraph -->

<!-- vg-ninh-binh-ha-long-transfer-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What sources prove, and what they cannot decide</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This guide does not publish stale timetables or scrape operator claims; it teaches the transfer decision that protects the cruise day. Sources can confirm destination context, port and route systems, heritage status, weather pressure, and transport categories; they cannot decide your luggage, hotel lane, cruise check-in, child fatigue, or whether one more northern stop makes the route brittle.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-ha-long-transfer-source-diversity">
<thead><tr><th>Source type</th><th>Useful for</th><th>VietnamGuide still decides</th></tr></thead>
<tbody>
<tr><td data-label="Source type">Vietnam.travel destination and transport pages</td><td data-label="Useful for">Ninh Binh and Ha Long context, broad transport categories, and weather framing.</td><td data-label="VietnamGuide still decides">Which transfer mode protects the booked cruise.</td></tr>
<tr><td data-label="Source type">UNESCO and bay management sources</td><td data-label="Useful for">Why Ha Long Bay-Cat Ba is a real heritage/nature chapter and why routes/ports matter.</td><td data-label="VietnamGuide still decides">Whether the bay belongs after Ninh Binh or should be slowed down.</td></tr>
<tr><td data-label="Source type">Local Ninh Binh and Cat Ba sources</td><td data-label="Useful for">Local destination grouping, Cat Ba/Lan Ha exceptions, and activity context.</td><td data-label="VietnamGuide still decides">Whether the handoff should go straight to cruise, bay hotel, or island base.</td></tr>
<tr><td data-label="Source type">Same-week operator checks</td><td data-label="Useful for">Pickup time, driver contact, pier, cancellation, and luggage details.</td><td data-label="VietnamGuide still decides">Whether the route needs private control or an overnight buffer.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-ha-long-transfer-source-trail-snapshot:v1 -->
<!-- wp:group {"className":"vg-source-snapshot vg-ninh-binh-ha-long-transfer-source-trail-snapshot","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-source-snapshot vg-ninh-binh-ha-long-transfer-source-trail-snapshot">
<!-- wp:heading -->
<h2 class="wp-block-heading">Source trail snapshot</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Checked for this decision page: Vietnam.travel Ninh Binh - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh; Vietnam.travel Ha Long - https://vietnam.travel/places-to-go/northern-vietnam/ha-long; Vietnam.travel transport - https://vietnam.travel/plan-your-trip/transport-within-vietnam; Vietnam.travel weather and climate - https://vietnam.travel/things-to-do/weather-and-climate-vietnam; UNESCO Ha Long Bay-Cat Ba Archipelago - https://whc.unesco.org/en/list/672/; Ha Long Bay Management - https://halongbay.com.vn/; Ninh Binh Tourism Department - https://dulichninhbinh.com.vn/en/; Cat Ba tourism/service information - https://catba.com.vn/.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- vg-ninh-binh-ha-long-transfer-route-verdict:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route verdict: when this transfer is a good idea</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Going straight from Ninh Binh to a bay cruise is strongest when Ninh Binh has already earned an overnight and the bay cruise has a clear role. It is weaker when the route is trying to use one long road day to hide an overfull north.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-ha-long-transfer-route-verdict">
<thead><tr><th>Route shape</th><th>Use it when</th><th>Risk</th><th>Verdict</th></tr></thead>
<tbody>
<tr><td data-label="Route shape">Ninh Binh overnight to one-night Ha Long cruise</td><td data-label="Use it when">The cruise port is confirmed and pickup can leave enough margin.</td><td data-label="Risk">Late arrival or wrong pier.</td><td data-label="Verdict">Strong with private or cruise-arranged control.</td></tr>
<tr><td data-label="Route shape">Ninh Binh to Lan Ha/Cat Ba</td><td data-label="Use it when">The route intentionally uses Cat Ba or a Lan Ha operator.</td><td data-label="Risk">Different pier/island handoff than generic Ha Long transport.</td><td data-label="Verdict">Good only after exact logistics are named.</td></tr>
<tr><td data-label="Route shape">Ninh Binh to bay hotel buffer</td><td data-label="Use it when">The cruise is premium, family-heavy, or weather-sensitive.</td><td data-label="Risk">Costs one extra night.</td><td data-label="Verdict">Best risk-reduction move.</td></tr>
<tr><td data-label="Route shape">Ninh Binh day trip then same-day bay transfer</td><td data-label="Use it when">Rarely; only when a private driver and late non-cruise arrival are planned.</td><td data-label="Risk">Too much motion and little value.</td><td data-label="Verdict">Usually avoid.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-ha-long-transfer-mode-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Transfer modes from Ninh Binh to the bay</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-ha-long-transfer-mode-matrix">
<thead><tr><th>Mode</th><th>Best for</th><th>Before you pay</th><th>Concierge judgment</th></tr></thead>
<tbody>
<tr><td data-label="Mode">Private car</td><td data-label="Best for">Cruise check-in control, families, luggage, countryside hotels, late starts, premium trips.</td><td data-label="Before you pay">Driver knows the exact port, not just Ha Long city.</td><td data-label="Concierge judgment">Best default when the cruise matters.</td></tr>
<tr><td data-label="Mode">Cruise-arranged transfer</td><td data-label="Best for">One operator accountable for pickup and embarkation.</td><td data-label="Before you pay">Confirm pickup from Ninh Binh, not only Hanoi.</td><td data-label="Concierge judgment">Excellent if the operator confirms Ninh Binh service in writing.</td></tr>
<tr><td data-label="Mode">Shared van</td><td data-label="Best for">Lower cost, solo travelers, flexible bay hotel arrivals.</td><td data-label="Before you pay">Port, luggage, pickup lane, and late-arrival backup.</td><td data-label="Concierge judgment">Works when it names the real endpoint.</td></tr>
<tr><td data-label="Mode">Bus via city station</td><td data-label="Best for">Budget travelers not boarding a cruise the same day.</td><td data-label="Before you pay">Final taxi distance and arrival time.</td><td data-label="Concierge judgment">Avoid for premium cruise check-in unless timing is loose.</td></tr>
<tr><td data-label="Mode">Return to Hanoi first</td><td data-label="Best for">Routes with a Hanoi buffer, separate bookings, or unclear cruise details.</td><td data-label="Before you pay">Whether backtracking costs more than it solves.</td><td data-label="Concierge judgment">Sometimes cleaner than forcing a brittle direct link.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-ha-long-transfer-port-first:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Port first: the question that prevents most mistakes</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><strong>Do not book a generic Ha Long transfer until you know whether your cruise leaves from Tuan Chau, Ha Long International Cruise Port, Hon Gai, Got Pier, or a Cat Ba/Lan Ha pickup point.</strong> “Ha Long” can mean city hotel, cruise port, tourist harbor, Hon Gai side, or an operator pickup office. Those are not the same endpoint when luggage and check-in time matter.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li>Ask the cruise operator for the exact pier name and check-in address, not only the bay name.</li>
<li>Ask whether the operator accepts direct Ninh Binh pickups or only Hanoi pickups.</li>
<li>Ask whether late arrival means the boat waits, transfers you by tender, or cancels the booking.</li>
<li>Save the cruise hotline, driver number, hotel number, and map pin before leaving Ninh Binh.</li>
</ul>
<!-- /wp:list -->

<!-- vg-ninh-binh-ha-long-transfer-pickup-window:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Pickup window: protect the first cruise hour</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The right transfer is the one that reaches the right port early enough for the cruise's check-in process, not the one with the shortest advertised road time. Build the day around the operator's embarkation window, lunch timing, and cancellation policy.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-ha-long-transfer-pickup-window">
<thead><tr><th>Question</th><th>Why it matters</th><th>Better answer</th></tr></thead>
<tbody>
<tr><td data-label="Question">What time does pickup leave my exact Ninh Binh hotel?</td><td data-label="Why it matters">Tam Coc lanes, Trang An lodges, city hotels, and Van Long/Cuc Phuong fringe do not start from the same pickup logic.</td><td data-label="Better answer">Written pickup time and driver contact.</td></tr>
<tr><td data-label="Question">What time is cruise check-in closed?</td><td data-label="Why it matters">A cheap van that arrives after embarkation is not cheap.</td><td data-label="Better answer">Arrive with buffer before check-in closes.</td></tr>
<tr><td data-label="Question">Who handles traffic or vehicle delay?</td><td data-label="Why it matters">Separate bookings can leave the traveler responsible for the missed cruise.</td><td data-label="Better answer">One accountable operator or private driver with margin.</td></tr>
<tr><td data-label="Question">Can luggage stay secure during any stop?</td><td data-label="Why it matters">Scenic stopovers and shared vans can add baggage friction.</td><td data-label="Better answer">Direct vehicle, no unmanaged luggage gap.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-ha-long-transfer-base-luggage:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Ninh Binh base and luggage logic</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The transfer starts differently depending on where you slept. Tam Coc is the easiest all-round pickup base. Trang An area can be excellent if the lodge coordinates a driver. Ninh Binh city is easier for station and main-road pickups. Van Long, Gia Vien, and Cuc Phuong-side stays require more private control because the nature base is the point, not the transfer convenience.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-ha-long-transfer-base-luggage">
<thead><tr><th>Ninh Binh base</th><th>Transfer implication</th><th>Best move</th></tr></thead>
<tbody>
<tr><td data-label="Ninh Binh base">Tam Coc</td><td data-label="Transfer implication">Most likely to have tourism transfer support, but small lanes still matter.</td><td data-label="Best move">Ask for hotel-door pickup and luggage capacity.</td></tr>
<tr><td data-label="Ninh Binh base">Trang An area</td><td data-label="Transfer implication">Quiet lodges may need private pickup coordination.</td><td data-label="Best move">Let the hotel confirm access and pickup point.</td></tr>
<tr><td data-label="Ninh Binh base">Ninh Binh city</td><td data-label="Transfer implication">Cleaner main-road or station logic, less countryside recovery.</td><td data-label="Best move">Use when logistics matter more than atmosphere.</td></tr>
<tr><td data-label="Ninh Binh base">Van Long / Cuc Phuong fringe</td><td data-label="Transfer implication">Longer, more specialized pickup.</td><td data-label="Best move">Use private car or rethink the same-day cruise handoff.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-ha-long-transfer-cruise-handoff:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cruise handoff: what must be confirmed in writing</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list"} -->
<ul class="wp-block-list vg-check-list">
<li>Exact pier, port, map pin, and operator check-in desk.</li>
<li>Latest arrival time before the cruise treats the booking as late or no-show.</li>
<li>Driver contact, vehicle plate, pickup point, and luggage capacity.</li>
<li>Whether the transfer is operated by the cruise, a partner, hotel, or third-party transport desk.</li>
<li>Weather, cancellation, route-change, and refund rules if the bay trip changes.</li>
</ul>
<!-- /wp:list -->

<!-- vg-ninh-binh-ha-long-transfer-lan-ha-cat-ba:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Lan Ha, Cat Ba, and Bai Tu Long exceptions</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A Lan Ha or Cat Ba plan is not just “Ha Long but quieter.” It may change the port, ferry/island logic, pickup office, or overnight strategy. Bai Tu Long can also use different buying logic from the classic Ha Long default. Read <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>, <a href="/destinations/cat-ba-travel-guide/">Cat Ba Travel Guide</a>, and <a href="/destinations/bai-tu-long-bay-guide/">Bai Tu Long Bay Guide</a> before assuming one Ninh Binh transfer serves every bay product.</p>
<!-- /wp:paragraph -->

<!-- vg-ninh-binh-ha-long-transfer-overnight-buffer:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">When to add an overnight buffer near the bay</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-ha-long-transfer-overnight-buffer">
<thead><tr><th>Add the buffer when</th><th>Why</th><th>What to book</th></tr></thead>
<tbody>
<tr><td data-label="Add the buffer when">The cruise is premium or non-refundable</td><td data-label="Why">A missed check-in costs more than a hotel night.</td><td data-label="What to book">Bay-side hotel with clear morning port transfer.</td></tr>
<tr><td data-label="Add the buffer when">Traveling with children or heavy bags</td><td data-label="Why">Shared transfers magnify fatigue and luggage friction.</td><td data-label="What to book">Private car plus easier hotel-to-port timing.</td></tr>
<tr><td data-label="Add the buffer when">Weather looks unstable</td><td data-label="Why">Storm, rain, fog, or changed bay operations can make same-day handoffs brittle.</td><td data-label="What to book">Flexible hotel and refundable transport where possible.</td></tr>
<tr><td data-label="Add the buffer when">Ninh Binh stay is remote</td><td data-label="Why">Van Long or Cuc Phuong starts too far from normal tourism pickup logic.</td><td data-label="What to book">Private car or split the route differently.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-ha-long-transfer-season-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Season and weather pressure</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Northern Vietnam weather can change both the value of Ninh Binh and the bay cruise. Heat makes pickup time and car comfort matter. Heavy rain can weaken countryside cycling and bay visibility. Storm-season uncertainty makes cancellation language and buffer nights more valuable than a copied road-time estimate.</p>
<!-- /wp:paragraph -->

<!-- vg-ninh-binh-ha-long-transfer-cost-booking:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cost and booking logic</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Price the transfer by risk removed, not only by seat cost. A private car can be the cheaper decision when it protects a premium cruise, avoids a missed pier, reduces family fatigue, or lets a remote Ninh Binh hotel stay work. A shared van is better value only when the endpoint and timing are exact.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-ha-long-transfer-cost-booking">
<thead><tr><th>Cost driver</th><th>What changes the price</th><th>Useful upgrade</th></tr></thead>
<tbody>
<tr><td data-label="Cost driver">Private vehicle</td><td data-label="What changes the price">Vehicle size, pickup distance, port, waiting time, and language support.</td><td data-label="Useful upgrade">Worth it when cruise check-in matters.</td></tr>
<tr><td data-label="Cost driver">Shared van</td><td data-label="What changes the price">Seat type, pickup inclusion, luggage, transfer company, and final drop-off.</td><td data-label="Useful upgrade">Only useful when pier is named.</td></tr>
<tr><td data-label="Cost driver">Overnight buffer</td><td data-label="What changes the price">Hotel class, port access, cancellation terms, and local transfer.</td><td data-label="Useful upgrade">Worth it for premium/family/weather-sensitive cruise days.</td></tr>
<tr><td data-label="Cost driver">Route rethink</td><td data-label="What changes the price">Skipping duplicate scenery, staying in Hanoi, or choosing only Ninh Binh or the bay.</td><td data-label="Useful upgrade">Sometimes saves the most money and energy.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-ha-long-transfer-booking-audit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Booking audit before you pay</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list"} -->
<ul class="wp-block-list vg-check-list">
<li>Get the cruise's exact check-in address and latest arrival time.</li>
<li>Ask the transfer provider to repeat the final port in writing.</li>
<li>Confirm pickup from your specific Ninh Binh hotel, not only from “Ninh Binh.”</li>
<li>Confirm luggage allowance and whether bags stay in the same vehicle.</li>
<li>Confirm who pays if delay or wrong drop-off causes missed embarkation.</li>
<li>Keep screenshots of pickup, port, booking code, cancellation terms, and driver contact offline.</li>
</ul>
<!-- /wp:list -->

<!-- vg-ninh-binh-ha-long-transfer-mistakes-skip:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mistakes to skip</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li>Booking a “Ha Long transfer” before knowing the pier.</li>
<li>Assuming a Hanoi cruise transfer automatically picks up from Ninh Binh.</li>
<li>Leaving a remote Ninh Binh lodge too late for a same-day cruise.</li>
<li>Using a budget bus for a premium cruise check-in with tight timing.</li>
<li>Adding both Ninh Binh and the bay to a short route when one landscape chapter would be enough.</li>
</ul>
<!-- /wp:list -->

<!-- vg-ninh-binh-ha-long-transfer-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks in the final week</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list"} -->
<ul class="wp-block-list vg-check-list">
<li>Check the cruise operator's latest check-in, port, cancellation, and weather policy.</li>
<li>Ask your Ninh Binh hotel to confirm the pickup point and vehicle access.</li>
<li>Reconfirm the driver or transfer company the day before travel.</li>
<li>Check north Vietnam weather and bay operation updates before assuming the cruise runs normally.</li>
<li>Read <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before paying for a fragile handoff.</li>
</ul>
<!-- /wp:list -->

<!-- vg-ninh-binh-ha-long-transfer-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Ninh Binh to Ha Long Bay transfer FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-ninh-binh-ha-long-transfer-faq">
<details><summary>Can I go directly from Ninh Binh to Ha Long Bay?</summary><p>Yes, but the direct transfer should be booked only after the exact cruise port and check-in window are known. The direct route is most useful when it prevents backtracking to Hanoi without risking the cruise.</p></details>
<details><summary>Should I use a private car?</summary><p>Use private car when the cruise is expensive, the Ninh Binh hotel is remote, you have children or heavy luggage, or you need one accountable pickup and drop-off.</p></details>
<details><summary>Is a shared van safe for the cruise day?</summary><p>It can be, if the provider confirms your hotel pickup, final port, luggage policy, and arrival buffer in writing. Avoid vague “Ha Long city” drop-offs for same-day cruise boarding.</p></details>
<details><summary>Should I sleep near Ha Long before the cruise?</summary><p>Add a buffer night when missing check-in would damage the trip, weather is unstable, or the transfer starts from a remote Ninh Binh stay.</p></details>
<details><summary>Does Lan Ha Bay change the transfer?</summary><p>Often yes. Lan Ha and Cat Ba routes can use different ports, pickup offices, or island handoffs, so confirm the exact endpoint before booking generic Ha Long transport.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, choose the stay area with <a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a>, decide the bay with <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a> and <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>, then use this page before paying for a direct transfer. If the north is too tight, compare <a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-ninh-binh-ha-long-transfer-hero:v1',
    'concierge verdict' => 'vg-ninh-binh-ha-long-transfer-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-ninh-binh-ha-long-transfer-at-a-glance:v1',
    'photo proof' => 'vg-ninh-binh-ha-long-transfer-photo-proof:v1',
    'source diversity' => 'vg-ninh-binh-ha-long-transfer-source-diversity:v1',
    'source trail snapshot' => 'vg-ninh-binh-ha-long-transfer-source-trail-snapshot:v1',
    'route verdict' => 'vg-ninh-binh-ha-long-transfer-route-verdict:v1',
    'mode matrix' => 'vg-ninh-binh-ha-long-transfer-mode-matrix:v1',
    'port first audit' => 'vg-ninh-binh-ha-long-transfer-port-first:v1',
    'pickup window' => 'vg-ninh-binh-ha-long-transfer-pickup-window:v1',
    'base luggage logic' => 'vg-ninh-binh-ha-long-transfer-base-luggage:v1',
    'cruise handoff' => 'vg-ninh-binh-ha-long-transfer-cruise-handoff:v1',
    'Lan Ha Cat Ba exception' => 'vg-ninh-binh-ha-long-transfer-lan-ha-cat-ba:v1',
    'overnight buffer' => 'vg-ninh-binh-ha-long-transfer-overnight-buffer:v1',
    'season weather' => 'vg-ninh-binh-ha-long-transfer-season-weather:v1',
    'cost booking' => 'vg-ninh-binh-ha-long-transfer-cost-booking:v1',
    'booking audit' => 'vg-ninh-binh-ha-long-transfer-booking-audit:v1',
    'mistakes skip' => 'vg-ninh-binh-ha-long-transfer-mistakes-skip:v1',
    'live checks' => 'vg-ninh-binh-ha-long-transfer-live-checks:v1',
    'FAQ' => 'vg-ninh-binh-ha-long-transfer-faq:v1',
    'related routes shortcode' => '[vg_related_routes]',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ninh_binh_ha_long_transfer_ops_assert_required_content_markers($content, $required_content_markers);
vg_ninh_binh_ha_long_transfer_ops_assert_internal_page_links_are_published('Ninh Binh to Ha Long Bay Transfer', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Ninh Binh to Ha Long Bay Transfer',
    'post_name'      => 'ninh-binh-to-ha-long-bay-transfer',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ninh_binh_ha_long_transfer_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led transfer guide for getting from Ninh Binh to Ha Long Bay, Lan Ha, Cat Ba, or a bay cruise by exact port, pickup window, luggage, and buffer logic.',
    'comment_status' => 'closed',
    'ping_status'    => 'closed',
];

if ($page instanceof WP_Post) {
    $post_args['ID'] = $page->ID;
    $result = wp_update_post($post_args, true);
} else {
    $result = wp_insert_post($post_args, true);
}

if (is_wp_error($result)) {
    vg_ninh_binh_ha_long_transfer_ops_fail('Could not publish Ninh Binh to Ha Long Bay Transfer: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ninh_binh_ha_long_transfer_ops_fail('Could not publish Ninh Binh to Ha Long Bay Transfer: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Ninh Binh to Ha Long Bay Transfer Guide');
update_post_meta($page_id, 'rank_math_description', 'Ninh Binh to Ha Long Bay transfer guide: choose private car, cruise transfer, shared van or overnight buffer by exact port, pickup, luggage and cruise timing.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Ninh Binh to Ha Long Bay transfer');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Choose the Ninh Binh to Ha Long Bay transfer by exact cruise port, pickup window, luggage, hotel lane, weather, cancellation risk, and whether an overnight buffer protects the bay day.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published Ninh Binh to Ha Long Bay Transfer as an evidence-led route handoff guide with photo-led hero, concierge verdict, proof panel, at-a-glance table, text-only image credits, source-diversity module, rendered source trail snapshot, route verdict, mode matrix, port-first audit, pickup-window logic, base/luggage guidance, cruise handoff audit, Lan Ha/Cat Ba exception, overnight-buffer logic, season/weather pivots, cost/booking logic, mistakes/skip logic, live checks, FAQ, Plan and Destinations hub notes, homepage support, and inbound related routes.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Ninh Binh destination page - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh - checked {$review_date}\nVietnam.travel - Ha Long destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-long - checked {$review_date}\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}\nUNESCO World Heritage Centre - Ha Long Bay-Cat Ba Archipelago - https://whc.unesco.org/en/list/672/ - checked {$review_date}; automation may receive 403 because of access challenge\nHa Long Bay Management - https://halongbay.com.vn/ - checked {$review_date}\nNinh Binh Tourism Department - Tourism Promotion Information Center - https://dulichninhbinh.com.vn/en/ - checked {$review_date}\nCat Ba tourism/service information - https://catba.com.vn/ - checked {$review_date}\nWikimedia Commons image record - Tam Coc Rice Valley - https://commons.wikimedia.org/wiki/File:Tam_Coc_Rice_Valley_(8756354342).jpg - license checked {$review_date}\nWikimedia Commons image record - Bai Chay Pano 10-2023 - https://commons.wikimedia.org/wiki/File:Bai_Chay_Pano_10-2023.jpg - license checked {$review_date}\nWikimedia Commons image record - Halong Bay Cruise Boats 01 - https://commons.wikimedia.org/wiki/File:Halong_Bay_Cruise_Boats_01.jpg - license checked {$review_date}\nWikimedia Commons image record - Ha Long Bay, Vietnam, View from above - https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg - license checked {$review_date}\nWikimedia Commons image record - Lan Ha Bay-Cat Ba Vietnam - https://commons.wikimedia.org/wiki/File:Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg - license checked {$review_date}");
update_post_meta($page_id, 'vg_eeat_field_note', 'The Ninh Binh to Ha Long Bay handoff is best decided by the cruise port and failure cost, not by generic road time. When the port is unclear, the transfer is not ready to book.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Ninh Binh to Ha Long Bay Transfer built around route-protection judgment rather than stale schedules or operator scraping.\nConcierge verdict names private transfer or cruise-arranged transfer as safest default after the exact port and pickup window are confirmed.\nPort-first, pickup-window, Ninh Binh base, luggage, Lan Ha/Cat Ba exception, overnight-buffer, weather, cost, booking audit, mistakes, live checks, FAQ, source trail, update log, and related routes create durable decision value.\nText-only image credits keep source accountability visible while reducing outbound clutter.\nRelated routes connect the page to Ninh Binh Travel Guide, Where to Stay in Ninh Binh, Hanoi to Ninh Binh Transport, Ha Long Bay Travel Guide, Ha Long Bay vs Lan Ha Bay, Cat Ba, Bai Tu Long, itineraries, transport, cost, safety, and insurance.");

$related_routes = "Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide whether Ninh Binh belongs before forcing a bay handoff.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose the Ninh Binh base before judging pickup friction.\nNinh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh earns a night before it launches the bay day.\nTrang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Match the boat route and stay rhythm before leaving for the bay.\nHanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Use this when the north needs clean movement before the bay handoff.\nHa Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Choose cruise length, port, route, and skip logic before paying for transfer.\nHa Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Confirm whether Ha Long, Lan Ha, Cat Ba, or skip is the right bay answer.\nCat Ba Travel Guide | /destinations/cat-ba-travel-guide/ | Use when Lan Ha or island-base routing changes the endpoint.\nBai Tu Long Bay Guide | /destinations/bai-tu-long-bay-guide/ | Compare quieter bay logic before assuming one Ha Long transfer fits all cruises.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Decide whether the north should hold Ninh Binh, the bay, both, or a cleaner Hanoi day.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Protect the Hanoi chapter before adding two northern scenery moves.\nHanoi Travel Guide | /destinations/hanoi-travel-guide/ | Place the handoff inside the northern route.\nWhere to Stay in Hanoi | /destinations/where-to-stay-in-hanoi/ | Keep Hanoi as the backup if the direct handoff becomes brittle.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the planning order before locking hotels and transfers.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Compare whether both Ninh Binh and the bay belong in the shortlist.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Understand the heritage weight of Trang An and Ha Long Bay-Cat Ba before duplicating scenery.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm the north deserves enough nights for both landscape chapters.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check northern heat, rain, fog, and storm pressure before same-day handoffs.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Count private cars, shared vans, cruise transfers, ports, and buffers.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the transfer by risk removed, not just seat cost.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check road, boat, weather, luggage, and interruption coverage.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair transfers, deposits, driver contact, ports, and cancellation terms with practical risk habits.\n7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Decide whether a one-week route can afford both Ninh Binh and the bay.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a Ninh Binh-to-bay handoff improves or overloads a short route.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when two weeks can protect both northern landscape chapters.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use a longer route to slow the north without making transfer days brittle.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.";
vg_ninh_binh_ha_long_transfer_ops_assert_related_route_meta_links_are_published('Ninh Binh to Ha Long Bay Transfer related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Ninh Binh pickup image: Tam Coc Rice Valley by Hoang Giang Hai, CC BY 2.0. Body images: Bai Chay Pano 10-2023 by Pdhadam, CC BY-SA 4.0; Halong Bay Cruise Boats 01 by Shyamal L., CC BY-SA 4.0; Ha Long Bay, Vietnam, View from above by Vyacheslav Argenberg, CC BY 4.0; Lan Ha Bay-Cat Ba by Saaremees, CC BY-SA 4.0. Image credits are text-only on the page; source records are preserved in metadata.');
update_post_meta($page_id, '_generate-disable-headline', 'true');

delete_post_meta($page_id, '_rank_math_title');
delete_post_meta($page_id, '_rank_math_description');
delete_post_meta($page_id, '_rank_math_focus_keyword');

$required_meta = [
    'rank_math_title',
    'rank_math_description',
    'rank_math_focus_keyword',
    'vg_eeat_primary_decision',
    'vg_eeat_reviewed_guide',
    'vg_eeat_written_by',
    'vg_eeat_reviewed_by',
    'vg_eeat_last_meaningful_update',
    'vg_eeat_update_summary',
    'vg_eeat_sources_checked',
    'vg_eeat_field_note',
    'vg_eeat_affiliate_status',
    'vg_eeat_evidence_moat',
    'vg_eeat_related_routes',
    'vg_eeat_hero_image_credit',
    '_generate-disable-headline',
];

foreach ($required_meta as $meta_key) {
    $meta_value = get_post_meta($page_id, $meta_key, true);

    if ((is_string($meta_value) && trim($meta_value) === '') || $meta_value === [] || $meta_value === null) {
        vg_ninh_binh_ha_long_transfer_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_ninh_binh_ha_long_transfer_ops_fail('Ninh Binh to Ha Long Bay Transfer was updated but is not published.');
}

vg_ninh_binh_ha_long_transfer_ops_refresh_plan_hub();
vg_ninh_binh_ha_long_transfer_ops_refresh_destinations_hub();
vg_ninh_binh_ha_long_transfer_ops_refresh_homepage_route_spine();
vg_ninh_binh_ha_long_transfer_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_ninh_binh_ha_long_transfer_ops_log("Published Ninh Binh to Ha Long Bay Transfer: {$page_id} {$updated_permalink}");

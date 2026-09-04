<?php
/**
 * Publish the Best Day Trips from Hanoi guide.
 *
 * Self-reference marker: ops/apply-best-day-trips-hanoi-guide.php
 *
 * Run from the WordPress root with:
 * VG_FORCE_HANOI_DAY_TRIPS_REPUBLISH=1 wp eval-file ops/apply-best-day-trips-hanoi-guide.php --allow-root
 *
 * Repair only hub/related-route/homepage side effects with:
 * VG_REPAIR_HANOI_DAY_TRIPS_LINKS=1 wp eval-file ops/apply-best-day-trips-hanoi-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_hanoi_day_trips_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_hanoi_day_trips_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_hanoi_day_trips_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_HANOI_DAY_TRIPS_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_hanoi_day_trips_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_HANOI_DAY_TRIPS_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_hanoi_day_trips_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_hanoi_day_trips_ops_fail('Could not resolve a valid WordPress author for Best Day Trips from Hanoi.');
}

function vg_hanoi_day_trips_ops_internal_path_from_href(string $href): ?string
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

function vg_hanoi_day_trips_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_hanoi_day_trips_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_hanoi_day_trips_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_day_trips_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_hanoi_day_trips_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_hanoi_day_trips_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_hanoi_day_trips_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_hanoi_day_trips_ops_log("Validated {$label} internal page links are published.");
}

function vg_hanoi_day_trips_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_hanoi_day_trips_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_hanoi_day_trips_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_hanoi_day_trips_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_hanoi_day_trips_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_hanoi_day_trips_ops_log("Validated {$label} related-route links are published.");
}

function vg_hanoi_day_trips_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_hanoi_day_trips_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
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
            vg_hanoi_day_trips_ops_fail("Could not confidently refresh {$label}.");
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
        vg_hanoi_day_trips_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_hanoi_day_trips_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_hanoi_day_trips_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_hanoi_day_trips_ops_homepage(): ?WP_Post
{
    $front_page_id = (int) get_option('page_on_front');
    $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        $page = get_page_by_path('home', OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        return null;
    }

    return $page;
}

function vg_hanoi_day_trips_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_hanoi_day_trips_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_hanoi_day_trips_ops_published_page_exists('destinations/best-day-trips-from-hanoi')) {
        vg_hanoi_day_trips_ops_log('Skipped Destinations hub refresh: Best Day Trips from Hanoi is not published.');
        return;
    }

    $marker = '<!-- vg-hanoi-day-trips-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose Hanoi day trips by route job</h3><p>The <a href="/destinations/best-day-trips-from-hanoi/">Best Day Trips from Hanoi</a> guide compares Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, and the stay-in-Hanoi fallback by transfer friction, season, booking quality, and whether the day should become an overnight.</p></div>
<!-- /wp:group -->
HTML;

    vg_hanoi_day_trips_ops_assert_internal_page_links_are_published('Destinations hub Hanoi day trips note', $block);
    vg_hanoi_day_trips_ops_upsert_marked_group($hub, 'Destinations hub Hanoi day trips note', $marker, $block);
}

function vg_hanoi_day_trips_ops_refresh_homepage_route_spine(): void
{
    $home = vg_hanoi_day_trips_ops_homepage();

    if (! $home instanceof WP_Post) {
        vg_hanoi_day_trips_ops_log('Skipped homepage route-spine refresh: homepage was not found or is not published.');
        return;
    }

    if (! vg_hanoi_day_trips_ops_published_page_exists('destinations/best-day-trips-from-hanoi')) {
        vg_hanoi_day_trips_ops_log('Skipped homepage route-spine refresh: Best Day Trips from Hanoi is not published.');
        return;
    }

    $marker = '<!-- vg-hanoi-day-trips-homepage-route-spine:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-home-section vg-home-decision-spine vg-home-hanoi-day-trips-spine"} -->
<div class="wp-block-group vg-home-section vg-home-decision-spine vg-home-hanoi-day-trips-spine"><p class="vg-kicker">Northern excursion decision</p><h2>Best Day Trips from Hanoi</h2><p>Use Best Day Trips from Hanoi when the north needs one outside day, not another generic attraction list. The guide compares Ninh Binh, bay day trips, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, and the stay-in-Hanoi fallback before transfer time steals the route.</p><p class="vg-section-link"><a href="/destinations/best-day-trips-from-hanoi/">Choose the Hanoi day trip</a></p></div>
<!-- /wp:group -->
HTML;

    vg_hanoi_day_trips_ops_assert_internal_page_links_are_published('Homepage Hanoi day trips route-spine note', $block);
    vg_hanoi_day_trips_ops_upsert_marked_group($home, 'Homepage Hanoi day trips route-spine note', $marker, $block);
}

function vg_hanoi_day_trips_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_day_trips_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_hanoi_day_trips_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
    $route_parts = array_map('trim', explode('|', $route_line));
    $route_href = $route_parts[1];

    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);
    $lines = preg_split('/\R+/', trim($current)) ?: [];
    $next_lines = [];
    $found_route = false;
    $changed_route = false;

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        $parts = array_map('trim', explode('|', $line));
        $line_href = count($parts) >= 2 ? $parts[1] : '';
        $is_target_line = $line_href === $route_href || str_contains($line, $route_href);

        if (! $is_target_line) {
            $next_lines[] = $line;
            continue;
        }

        if ($found_route) {
            $changed_route = true;
            continue;
        }

        $found_route = true;

        if ($line !== $route_line) {
            $next_lines[] = $route_line;
            $changed_route = true;
        } else {
            $next_lines[] = $line;
        }
    }

    if (! $found_route) {
        $next_lines[] = $route_line;
        $changed_route = true;
    }

    if (! $changed_route) {
        vg_hanoi_day_trips_ops_log("Skipped related-route refresh for {$label}: Hanoi day trips line is already current.");
        return;
    }

    update_post_meta($page->ID, 'vg_eeat_related_routes', implode("\n", $next_lines));

    vg_hanoi_day_trips_ops_log("Upserted Best Day Trips from Hanoi related route to {$label}: {$page->ID}");
}

function vg_hanoi_day_trips_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Best Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.';

    foreach (
        [
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ] as $path => $label
    ) {
        vg_hanoi_day_trips_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

function vg_hanoi_day_trips_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_hanoi_day_trips_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_hanoi_day_trips_ops_fail('Could not find published /destinations/ parent page.');
}

$page = get_page_by_path('destinations/best-day-trips-from-hanoi', OBJECT, 'page');

if (vg_hanoi_day_trips_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_day_trips_ops_fail('Repair mode requires Best Day Trips from Hanoi to already be published.');
    }

    vg_hanoi_day_trips_ops_refresh_destinations_hub();
    vg_hanoi_day_trips_ops_refresh_homepage_route_spine();
    vg_hanoi_day_trips_ops_refresh_inbound_related_routes();
    vg_hanoi_day_trips_ops_log("Repaired Best Day Trips from Hanoi side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_hanoi_day_trips_ops_force_republish_enabled()) {
    vg_hanoi_day_trips_ops_fail('Best Day Trips from Hanoi is not a draft. Set VG_FORCE_HANOI_DAY_TRIPS_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_hanoi_day_trips_ops_log("Preflight Best Day Trips from Hanoi: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_hanoi_day_trips_ops_log('Preflight Best Day Trips from Hanoi: no existing page found; creating a child page under /destinations/.');
}

$review_date = 'July 24, 2026';
$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$trang_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');
$ha_long_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg/1920px-Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg');
$bat_trang_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/6/6e/Bat_Trang_pottery_and_ceramics_village_in_2016_20.jpg');
$perfume_pagoda_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/b/b7/VN_Chua_Huong5_tango7174.jpg');
$ba_vi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/a/a3/Ba-Vi2-Vietnam.jpg');

$content = <<<HTML
<!-- vg-hanoi-day-trips-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover vg-hanoi-day-trips-hero"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover vg-hanoi-day-trips-hero" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hoan Kiem Lake in central Hanoi before a northern Vietnam day trip" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed Hanoi excursion decision guide - Updated {$review_date}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Best Day Trips from Hanoi</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Use this guide when Hanoi has one open day and every option sounds essential. The real decision is whether the route needs inland karst scenery, a bay preview, a craft half-day, a village history day, a pilgrimage journey, a forest escape, or simply a better day inside the capital.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: the best Hanoi day trip is not the longest one. It is the one that adds a missing route chapter without stealing sleep, transfer margin, or the next morning.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<p class="vg-image-credit">Image: Alex 69200 vx / CC BY-SA 4.0. Full license and source details are recorded in the source trail.</p>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-hanoi-day-trips-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-hanoi-day-trips-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-hanoi-day-trips-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most first-time international travelers, Ninh Binh is the best full-day trip from Hanoi if there is one protected middle day and no same-night flight, train, or cruise handoff. Ha Long or Lan Ha is better as an overnight cruise for most routes. Bat Trang is the best half-day. Duong Lam, Perfume Pagoda, and Ba Vi are specialist choices, not default add-ons.</strong></p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-hanoi-day-trips-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-hanoi-day-trips-shortlist">
<li><strong>Best overall default:</strong> Ninh Binh when Hanoi has three or more nights and the north needs karst scenery without buying a cruise.</li>
<li><strong>Best premium upgrade:</strong> turn Ha Long or Lan Ha into an overnight, then buy a better operator, route map, cabin, and weather policy.</li>
<li><strong>Best light day:</strong> Bat Trang when the route needs culture and craft texture but cannot absorb another long transfer.</li>
<li><strong>Best skip rule:</strong> if the outside day makes Hanoi thinner than two real city blocks, stay in Hanoi and improve the capital chapter.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-hanoi-day-trips-at-a-glance:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Fast Answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-day-trips-glance">
<thead><tr><th>Traveler question</th><th>Best answer</th><th>Why</th><th>Skip first</th></tr></thead>
<tbody>
<tr><td data-label="Traveler question">I have one open Hanoi day</td><td data-label="Best answer">Ninh Binh</td><td data-label="Why">The inland karst payoff is strong and pairs naturally with Hanoi.</td><td data-label="Skip first">Bay day trip unless logistics are unusually clean.</td></tr>
<tr><td data-label="Traveler question">I hate long road days</td><td data-label="Best answer">Bat Trang or stay in Hanoi</td><td data-label="Why">The day still gains texture without turning into transport.</td><td data-label="Skip first">Perfume Pagoda, bay day trip, and Ba Vi in bad weather.</td></tr>
<tr><td data-label="Traveler question">I want the bay</td><td data-label="Best answer">Overnight Ha Long or Lan Ha</td><td data-label="Why">A day trip can work, but the route usually pays too much transfer cost for too little calm water time.</td><td data-label="Skip first">A vague cruise with no route map or return window.</td></tr>
<tr><td data-label="Traveler question">I want culture, not scenery</td><td data-label="Best answer">Bat Trang or Duong Lam</td><td data-label="Why">They add craft, architecture, village rhythm, and slower context.</td><td data-label="Skip first">A long mixed tour that treats culture as a shopping stop.</td></tr>
<tr><td data-label="Traveler question">Hanoi itself feels rushed</td><td data-label="Best answer">Stay in Hanoi</td><td data-label="Why">A better capital day beats a famous outside day that leaves the city hollow.</td><td data-label="Skip first">Any excursion that starts before recovery is protected.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-day-trips-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo Proof: What Each Day Actually Buys</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-hanoi-day-trips-photo-grid">
<figure class="vg-guide-photo"><img src="{$trang_an_image}" alt="Trang An boat landscape in Ninh Binh near Hanoi" loading="lazy" decoding="async"><figcaption>Ninh Binh is the strongest inland landscape day when the route can protect an early start and unhurried return. Image: Jakub Halun / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$ha_long_image}" alt="Ha Long Bay limestone karsts from above" loading="lazy" decoding="async"><figcaption>The bay is iconic, but same-day use must be judged by route map, operator, port, and deck time. Image: Vyacheslav Argenberg / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$bat_trang_image}" alt="Bat Trang pottery and ceramics village near Hanoi" loading="lazy" decoding="async"><figcaption>Bat Trang is a craft and half-day answer, not a substitute for a full northern scenery chapter. Image: Vuong Tri Binh / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$perfume_pagoda_image}" alt="Boat and mountain scenery near Perfume Pagoda outside Hanoi" loading="lazy" decoding="async"><figcaption>Perfume Pagoda is strongest when the journey itself matters: boat, pilgrimage rhythm, cave temple, and patience. Image: Tango7174 / CC BY 3.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$ba_vi_image}" alt="Ba Vi mountain scenery near Hanoi" loading="lazy" decoding="async"><figcaption>Ba Vi is a weather-sensitive forest and cooler-air day, better with flexible expectations than a rigid sightseeing checklist. Image: Steven C. Price / CC BY-SA 4.0.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-hanoi-day-trips-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How We Use Sources</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-day-trips-source-diversity">
<thead><tr><th>Source layer</th><th>What it can prove</th><th>What VietnamGuide still judges</th></tr></thead>
<tbody>
<tr><td data-label="Source layer">Official tourism</td><td data-label="What it can prove">Which day trips are promoted, destination context, and broad experience framing.</td><td data-label="What VietnamGuide still judges">Whether the day is actually worth the transfer inside a short international route.</td></tr>
<tr><td data-label="Source layer">Heritage and destination records</td><td data-label="What it can prove">UNESCO status, landscape significance, conservation framing, and durable place context.</td><td data-label="What VietnamGuide still judges">Whether a same-day version gives enough experience to justify the cost.</td></tr>
<tr><td data-label="Source layer">Weather and transport sources</td><td data-label="What it can prove">Climate posture, forecast checks, and transport categories.</td><td data-label="What VietnamGuide still judges">How much buffer a traveler should keep before flights, trains, cruises, and mountain or boat days.</td></tr>
<tr><td data-label="Source layer">Image and license records</td><td data-label="What it can prove">Actual visual texture and reuse rights for public images.</td><td data-label="What VietnamGuide still judges">Whether the photo represents a route decision rather than decorating the page.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-day-trips-decision-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best Hanoi Day Trips by Route Job</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-day-trips-decision-grid">
<thead><tr><th>Choice</th><th>Best for</th><th>Watch out for</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Choice">Stay in Hanoi</td><td data-label="Best for">Two-night stays, late arrivals, food-first travelers, first-time orientation, poor weather, and departure buffers.</td><td data-label="Watch out for">Fear of missing out when the city itself is still underbuilt.</td><td data-label="VietnamGuide verdict">Staying in Hanoi is the correct day-trip decision when the outside option only adds road time to an already thin capital chapter.</td></tr>
<tr><td data-label="Choice">Ninh Binh</td><td data-label="Best for">First full-day excursion, inland scenery, boat landscape, temples, viewpoints, and north-only routes.</td><td data-label="Watch out for">Trying to include every stop, weak lunch timing, midday heat, and a same-night hard transfer.</td><td data-label="VietnamGuide verdict">Ninh Binh is the strongest default day trip from Hanoi only when it has a calm pickup, one boat landscape, one viewpoint or temple, and no rushed same-night transfer afterward.</td></tr>
<tr><td data-label="Choice">Ha Long or Lan Ha</td><td data-label="Best for">Travelers who cannot spare an overnight but still want a bay preview.</td><td data-label="Watch out for">Long road hours, vague cruise routing, bad weather terms, crowded check-in, and thin deck time.</td><td data-label="VietnamGuide verdict">Ha Long or Lan Ha as a day trip is a premium logistics product first and a scenery product second.</td></tr>
<tr><td data-label="Choice">Bat Trang</td><td data-label="Best for">Half-day craft, ceramics, shopping with context, family-friendly activity, and a lighter Hanoi day.</td><td data-label="Watch out for">Tours that reduce the village to a showroom stop.</td><td data-label="VietnamGuide verdict">Bat Trang is not a replacement for Ninh Binh or the bay; it is the half-day craft answer when Hanoi needs texture without a punishing transfer.</td></tr>
<tr><td data-label="Choice">Duong Lam</td><td data-label="Best for">Village architecture, rural history, slower cultural context, repeat Hanoi visitors, and travelers who dislike crowds.</td><td data-label="Watch out for">Expecting dramatic scenery or an entertainment-style village day.</td><td data-label="VietnamGuide verdict">Duong Lam works when rural architecture and slow cultural context matter more than headline scenery.</td></tr>
<tr><td data-label="Choice">Perfume Pagoda</td><td data-label="Best for">Pilgrimage route, boat approach, cave-temple journey, spiritual context, and travelers comfortable with a long ceremonial day.</td><td data-label="Watch out for">Festival crowds, heat, slippery steps, weak weather, and treating the day as just another temple stop.</td><td data-label="VietnamGuide verdict">Perfume Pagoda is worth choosing only when the boat, pilgrimage rhythm, and cave-temple journey are the point.</td></tr>
<tr><td data-label="Choice">Ba Vi</td><td data-label="Best for">Cooler air, forest, soft hiking, families with a private driver, and a nature break close to Hanoi.</td><td data-label="Watch out for">Poor visibility, rain, thin transport planning, and expecting guaranteed mountain views.</td><td data-label="VietnamGuide verdict">Ba Vi is a weather-sensitive nature day; choose it for cooler air, forest, and hiking, not for a guaranteed postcard view.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-day-trips-quick-chooser:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Quick Chooser</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-day-trips-quick-chooser">
<thead><tr><th>Situation</th><th>Choose</th><th>Reason</th></tr></thead>
<tbody>
<tr><td data-label="Situation">Two Hanoi nights and no protected city day</td><td data-label="Choose">Stay in Hanoi</td><td data-label="Reason">The capital is still the missing chapter.</td></tr>
<tr><td data-label="Situation">Three Hanoi nights with one clean middle day</td><td data-label="Choose">Ninh Binh</td><td data-label="Reason">It adds the strongest landscape contrast without requiring a cruise overnight.</td></tr>
<tr><td data-label="Situation">Four or more Hanoi nights and a high bay priority</td><td data-label="Choose">Overnight Ha Long or Lan Ha</td><td data-label="Reason">The bay needs protected time to feel premium rather than rushed.</td></tr>
<tr><td data-label="Situation">Late start, children, or jet lag</td><td data-label="Choose">Bat Trang or stay in Hanoi</td><td data-label="Reason">A shorter day protects energy and avoids punishing transfer math.</td></tr>
<tr><td data-label="Situation">Repeat visitor seeking quieter culture</td><td data-label="Choose">Duong Lam</td><td data-label="Reason">The payoff is cultural texture, not headline sightseeing.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-day-trips-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route Fit by Trip Length</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-day-trips-route-fit">
<thead><tr><th>Route length</th><th>Best use of Hanoi day trips</th><th>What to avoid</th></tr></thead>
<tbody>
<tr><td data-label="Route length">7 days in Vietnam</td><td data-label="Best use">Stay north-focused: Hanoi, Ninh Binh, one bay decision, and a departure buffer.</td><td data-label="What to avoid">Adding both a bay day trip and a long Ninh Binh day if Hanoi itself gets only fragments.</td></tr>
<tr><td data-label="Route length">10 days in Vietnam</td><td data-label="Best use">Choose one Hanoi day trip, then decide whether the bay or Central Vietnam deserves the next high-friction move.</td><td data-label="What to avoid">Treating day trips as free because they do not require a hotel change.</td></tr>
<tr><td data-label="Route length">14 days in Vietnam</td><td data-label="Best use">Ninh Binh can be overnight, and the bay can become a better cruise rather than a same-day rush.</td><td data-label="What to avoid">Using Hanoi as the staging point for every famous northern stop.</td></tr>
<tr><td data-label="Route length">21 days in Vietnam</td><td data-label="Best use">Upgrade the best day trips into real chapters only where the route gains depth.</td><td data-label="What to avoid">Collecting Ninh Binh, bay, villages, mountains, and Central Vietnam without recovery days.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-day-trips-day-placement:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where to Place the Day Trip</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-day-trips-day-placement">
<thead><tr><th>Placement</th><th>Use it for</th><th>Risk</th><th>Better correction</th></tr></thead>
<tbody>
<tr><td data-label="Placement">Arrival day</td><td data-label="Use it for">Only a soft Hanoi food, lake, or Bat Trang-style light day after an early arrival.</td><td data-label="Risk">Jet lag, phone setup, hotel check-in, and weather decisions are not stable yet.</td><td data-label="Better correction">Protect arrival recovery and move the excursion later.</td></tr>
<tr><td data-label="Placement">Middle full day</td><td data-label="Use it for">Ninh Binh, bay day trip, Perfume Pagoda, Ba Vi, or Duong Lam.</td><td data-label="Risk">Still needs a clear return plan before dinner or the next morning.</td><td data-label="Better correction">Use the middle full day, not arrival day or departure day, for the longest excursions.</td></tr>
<tr><td data-label="Placement">Departure day</td><td data-label="Use it for">Very short Hanoi blocks only.</td><td data-label="Risk">Traffic, weather, delays, luggage, and airport pressure turn a day trip into a gamble.</td><td data-label="Better correction">Stay inside Hanoi and build a clean airport buffer.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-day-trips-transfer-value-scorecard:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Transfer-to-Experience Scorecard</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the transfer-to-experience ratio as a hard filter: a long day must buy a different route chapter, not just a famous name and a tired return.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-day-trips-transfer-value-scorecard">
<thead><tr><th>Trip</th><th>Transfer-to-experience ratio</th><th>When it earns the day</th><th>When it should become overnight or be skipped</th></tr></thead>
<tbody>
<tr><td data-label="Trip">Ninh Binh</td><td data-label="Transfer-to-experience ratio">Strong when the day keeps one boat route plus one major land stop.</td><td data-label="When it earns the day">Three-night Hanoi stay, early pickup, realistic return, no same-night train or flight.</td><td data-label="When it should become overnight or be skipped">Upgrade overnight when you want both Trang An/Tam Coc and slower countryside evenings.</td></tr>
<tr><td data-label="Trip">Ha Long or Lan Ha</td><td data-label="Transfer-to-experience ratio">Weak-to-moderate unless the operator gives real deck time and clean routing.</td><td data-label="When it earns the day">Only when no overnight can fit and the cruise route is specific.</td><td data-label="When it should become overnight or be skipped">Upgrade to overnight when the bay is a priority; skip when the same route already has Ninh Binh plus Cat Ba.</td></tr>
<tr><td data-label="Trip">Bat Trang</td><td data-label="Transfer-to-experience ratio">Excellent for a half-day.</td><td data-label="When it earns the day">Short Hanoi stay, family activity, craft interest, weak weather, or a late start.</td><td data-label="When it should become overnight or be skipped">Do not overbuild it into a full-day shopping route.</td></tr>
<tr><td data-label="Trip">Duong Lam</td><td data-label="Transfer-to-experience ratio">Good only if guided context turns architecture and local rhythm into meaning.</td><td data-label="When it earns the day">Repeat Hanoi visitors or slow-culture trips.</td><td data-label="When it should become overnight or be skipped">Skip if the traveler mainly wants scenery or famous icons.</td></tr>
<tr><td data-label="Trip">Perfume Pagoda</td><td data-label="Transfer-to-experience ratio">Demanding because road, boat, walking, and crowds can all matter.</td><td data-label="When it earns the day">Pilgrimage interest, spiritual context, and a patient travel style.</td><td data-label="When it should become overnight or be skipped">Skip in bad weather, high crowd pressure, or mobility-sensitive trips.</td></tr>
<tr><td data-label="Trip">Ba Vi</td><td data-label="Transfer-to-experience ratio">Weather-dependent.</td><td data-label="When it earns the day">Cooler-air escape, private car, family nature day, or flexible hiking plan.</td><td data-label="When it should become overnight or be skipped">Skip when forecast visibility, rain, or road comfort is poor.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-day-trips-overnight-upgrade:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Day Trip vs Overnight Logic</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-day-trips-overnight-upgrade">
<thead><tr><th>Destination</th><th>Good as a day trip when...</th><th>Upgrade to overnight when...</th><th>Skip when...</th></tr></thead>
<tbody>
<tr><td data-label="Destination">Ninh Binh</td><td data-label="Good as a day trip when...">The route needs one scenery day and Hanoi stays the northern anchor.</td><td data-label="Upgrade to overnight when...">You want sunrise, slower countryside, both boat and cycling time, or less road pressure.</td><td data-label="Skip when...">The route already has Ha Long, Cat Ba, and no Hanoi buffer.</td></tr>
<tr><td data-label="Destination">Ha Long or Lan Ha</td><td data-label="Good as a day trip when...">A bay preview is the only possible version and the operator is specific.</td><td data-label="Upgrade to overnight when...">The bay is a major reason for visiting northern Vietnam.</td><td data-label="Skip when...">The day cruise makes the route pay too much for too little calm water time.</td></tr>
<tr><td data-label="Destination">Bat Trang</td><td data-label="Good as a day trip when...">You need a half-day craft and culture block.</td><td data-label="Upgrade to overnight when...">Almost never for a first-trip route.</td><td data-label="Skip when...">It only appears as a filler shopping stop.</td></tr>
<tr><td data-label="Destination">Duong Lam</td><td data-label="Good as a day trip when...">Village history is the point and a guide can add context.</td><td data-label="Upgrade to overnight when...">Only for niche slow-travel or photography routes.</td><td data-label="Skip when...">You need high visual payoff fast.</td></tr>
<tr><td data-label="Destination">Perfume Pagoda</td><td data-label="Good as a day trip when...">The boat and pilgrimage rhythm are the reason.</td><td data-label="Upgrade to overnight when...">Rarely for international first trips.</td><td data-label="Skip when...">Crowds, rain, mobility, or heat would make the journey the wrong kind of hard.</td></tr>
<tr><td data-label="Destination">Ba Vi</td><td data-label="Good as a day trip when...">Weather is favorable and the route needs forest or cooler air.</td><td data-label="Upgrade to overnight when...">A resort or family retreat is the actual goal.</td><td data-label="Skip when...">Visibility is poor or the route already has stronger mountain/nature time.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-day-trips-booking-mode:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Booking Mode: Group Tour, Private Driver, or Guide</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-day-trips-booking-mode">
<thead><tr><th>Mode</th><th>Best fit</th><th>Weak fit</th><th>What to confirm</th></tr></thead>
<tbody>
<tr><td data-label="Mode">Group tour</td><td data-label="Best fit">Ninh Binh and simple bay day trips when budget matters and the route can accept fixed timing.</td><td data-label="Weak fit">Travelers who need control, quiet, fewer shopping stops, or a reliable return buffer.</td><td data-label="What to confirm">Pickup zone, exact stops, lunch plan, return window, and cancellation terms.</td></tr>
<tr><td data-label="Mode">Private driver</td><td data-label="Best fit">Ba Vi, Duong Lam, family days, comfort-sensitive travelers, and routes with luggage or timing risk.</td><td data-label="Weak fit">Context-heavy days where transport-only leaves the place underexplained.</td><td data-label="What to confirm">Vehicle standard, driver wait time, tolls, parking, route flexibility, and return buffer.</td></tr>
<tr><td data-label="Mode">Private guide</td><td data-label="Best fit">Duong Lam, Perfume Pagoda, Bat Trang craft context, and travelers who value interpretation over simple transfer.</td><td data-label="Weak fit">Scenery-first travelers who mostly need transport and time control.</td><td data-label="What to confirm">Guide language, site knowledge, pacing, entrance inclusions, and whether shopping is optional.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-day-trips-operator-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Premium Operator Checks</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-day-trips-operator-checks"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-day-trips-operator-checks">
<li>A premium operator answer should name the pickup zone, vehicle standard, guide language, ticket inclusions, lunch plan, cancellation terms, and realistic return window.</li>
<li>Ask whether Hanoi pickup is hotel-door, Old Quarter limited, or meeting-point only, especially if staying in West Lake, Ba Dinh, or a calmer French Quarter pocket.</li>
<li>Reject any itinerary that hides the real order of stops. Ninh Binh should not pretend every cave, temple, boat route, viewpoint, and bike loop fits comfortably in one short winter day.</li>
<li>A tour that promises Ninh Binh plus Ha Long Bay in one day is selling geography compression, not quality.</li>
<li>For bay day trips, ask for the port, sailing area, approximate time on water, lunch format, weather policy, and backup return plan before comparing price.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-day-trips-comfort-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Family, Comfort, and Accessibility Fit</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-day-trips-comfort-fit">
<thead><tr><th>Traveler type</th><th>Best default</th><th>Use caution with</th><th>Why</th></tr></thead>
<tbody>
<tr><td data-label="Traveler type">Families with younger children</td><td data-label="Best default">Bat Trang, Ba Vi with private car, or Ninh Binh with a simplified route.</td><td data-label="Use caution with">Long bay day trips and Perfume Pagoda in heat or crowds.</td><td data-label="Why">A good family day needs bathrooms, flexible meals, shorter transfers, and a clean return.</td></tr>
<tr><td data-label="Traveler type">Older travelers</td><td data-label="Best default">Private Ninh Binh, Bat Trang, Duong Lam with guide, or stay in Hanoi.</td><td data-label="Use caution with">Steep viewpoints, slippery cave steps, crowded boats, and rushed group tours.</td><td data-label="Why">The route should protect pacing, shade, stairs, and a realistic lunch/rest window.</td></tr>
<tr><td data-label="Traveler type">Heat-sensitive travelers</td><td data-label="Best default">Bat Trang, Ba Vi in favorable weather, or an indoor-heavy Hanoi day.</td><td data-label="Use caution with">Midday Ninh Binh viewpoints, Perfume Pagoda walking, and exposed bay docks.</td><td data-label="Why">The north can still be draining when the itinerary is built around open-air queues.</td></tr>
<tr><td data-label="Traveler type">Premium travelers</td><td data-label="Best default">Private Ninh Binh or overnight bay cruise.</td><td data-label="Use caution with">Cheap group products that solve transport but not timing, crowding, or interpretation.</td><td data-label="Why">Premium value comes from control and context, not from adding more stops.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-day-trips-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to Skip First</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-day-trips-skip-logic">
<thead><tr><th>If the route has...</th><th>Protect this</th><th>Skip first</th><th>Reason</th></tr></thead>
<tbody>
<tr><td data-label="If the route has...">Two Hanoi nights</td><td data-label="Protect this">One real city day, food confidence, and a clean transfer onward.</td><td data-label="Skip first">All long outside day trips.</td><td data-label="Reason">Hanoi should not become only a hotel and pickup point.</td></tr>
<tr><td data-label="If the route has...">Ninh Binh overnight already</td><td data-label="Protect this">Slow countryside time.</td><td data-label="Skip first">Ninh Binh day trip from Hanoi.</td><td data-label="Reason">Do not duplicate the same landscape chapter.</td></tr>
<tr><td data-label="If the route has...">A bay overnight already</td><td data-label="Protect this">Cruise quality and weather margin.</td><td data-label="Skip first">Bay day trip from Hanoi.</td><td data-label="Reason">A day cruise becomes redundant and transfer-heavy.</td></tr>
<tr><td data-label="If the route has...">Ha Giang, Sapa, or Pu Luong later</td><td data-label="Protect this">Mountain or countryside energy.</td><td data-label="Skip first">Ba Vi unless it solves a specific family/rest need.</td><td data-label="Reason">The later chapter usually has stronger nature payoff.</td></tr>
<tr><td data-label="If the route has...">A departure-night flight</td><td data-label="Protect this">Luggage, airport, dinner, and transfer buffer.</td><td data-label="Skip first">Any distant day trip.</td><td data-label="Reason">The downside is much larger than the extra sightseeing value.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-day-trips-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live Checks Before Booking</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-day-trips-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-day-trips-live-checks">
<li>Use <a href="https://vietnam.travel/things-to-do/5-hanoi-day-trips" target="_blank" rel="noopener">Vietnam.travel's Hanoi day trips overview</a>, <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>, and <a href="/destinations/best-things-to-do-in-hanoi/">Best Things to Do in Hanoi</a> before treating an outside day as compulsory.</li>
<li>Use <a href="https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh" target="_blank" rel="noopener">Vietnam.travel Ninh Binh</a>, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, and your operator's exact stop order before paying for a full-day inland scenery route.</li>
<li>Use <a href="https://vietnam.travel/places-to-go/northern-vietnam/ha-long" target="_blank" rel="noopener">Vietnam.travel Ha Long</a>, <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, and <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a> before choosing a same-day cruise.</li>
<li>Use <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, and same-week forecasts before boat, bay, mountain, or viewpoint-heavy plans.</li>
<li>Use <a href="https://vietnamairport.vn/en/noi-bai-airport" target="_blank" rel="noopener">Noi Bai airport</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before placing a long day trip near a flight, train, cruise pickup, or travel-insurance-sensitive activity.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-day-trips-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best Day Trips from Hanoi FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-hanoi-day-trips-faq">
<details><summary>What is the best day trip from Hanoi?</summary><p>Ninh Binh is the best default full-day trip for most first-time travelers because it adds a strong inland landscape chapter without requiring a cruise overnight. It is still not automatic: if Hanoi itself is rushed, stay in the city.</p></details>
<details><summary>Can I visit Ha Long Bay as a day trip from Hanoi?</summary><p>Yes, but it is usually a logistics-heavy product. Choose it only when the operator is clear about route map, port, time on water, return window, and weather policy. If the bay matters, an overnight is usually better.</p></details>
<details><summary>Should I do Ninh Binh or Ha Long Bay from Hanoi?</summary><p>Choose Ninh Binh when you want the strongest day-trip value. Choose Ha Long or Lan Ha when the bay is the priority and you can protect the transfer or upgrade to overnight. Do not choose both as rushed same-day products.</p></details>
<details><summary>Is Bat Trang worth visiting?</summary><p>Yes when you want a lighter craft, ceramics, family, or culture half-day. It is weak when sold as a filler shopping stop or when you expect a substitute for Ninh Binh, the bay, or a mountain chapter.</p></details>
<details><summary>Is Perfume Pagoda worth a day trip?</summary><p>Only when the journey is the reason: boat approach, pilgrimage rhythm, cave-temple setting, and spiritual context. It is not the best default for travelers who mainly want scenery with low friction.</p></details>
<details><summary>Is Ba Vi a good Hanoi day trip?</summary><p>Ba Vi can be good for cooler air, forest, and a softer nature day, especially with a private driver. It is weather-sensitive, so check forecast and visibility before making it the only major outside day.</p></details>
<details><summary>Can I do a day trip from Hanoi before my flight?</summary><p>Do not use a distant day trip before a flight unless the flight is late, the plan is private, and the return buffer is conservative. A final Hanoi food, lake, museum, or craft block is safer.</p></details>
<details><summary>Should I book a group tour or private driver?</summary><p>Use a group tour when the route is simple and fixed timing is acceptable. Use a private driver when comfort, family pacing, luggage, weather flexibility, or return control matters. Use a guide when the value is interpretation, not transport.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where This Guide Fits in the Planning Chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then choose Hanoi's route role with <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>, <a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a>, and <a href="/compare/old-quarter-vs-french-quarter-vs-west-lake/">Old Quarter vs French Quarter vs West Lake</a>. Use <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>, <a href="/destinations/cat-ba-travel-guide/">Cat Ba Travel Guide</a>, and <a href="/destinations/bai-tu-long-bay-guide/">Bai Tu Long Bay Guide</a> before paying for a long northern day. Check <a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>, <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before placing a long day trip near a fragile transfer.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-hanoi-day-trips-hero:v1',
    'concierge verdict' => 'vg-hanoi-day-trips-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-hanoi-day-trips-at-a-glance:v1',
    'photo grid' => 'vg-hanoi-day-trips-photo-grid:v1',
    'source diversity' => 'vg-hanoi-day-trips-source-diversity:v1',
    'decision grid' => 'vg-hanoi-day-trips-decision-grid:v1',
    'quick chooser' => 'vg-hanoi-day-trips-quick-chooser:v1',
    'route fit' => 'vg-hanoi-day-trips-route-fit:v1',
    'day placement' => 'vg-hanoi-day-trips-day-placement:v1',
    'transfer value scorecard' => 'vg-hanoi-day-trips-transfer-value-scorecard:v1',
    'day trip vs overnight' => 'vg-hanoi-day-trips-overnight-upgrade:v1',
    'booking mode' => 'vg-hanoi-day-trips-booking-mode:v1',
    'operator checks' => 'vg-hanoi-day-trips-operator-checks:v1',
    'comfort fit' => 'vg-hanoi-day-trips-comfort-fit:v1',
    'skip logic' => 'vg-hanoi-day-trips-skip-logic:v1',
    'live checks' => 'vg-hanoi-day-trips-live-checks:v1',
    'FAQ' => 'vg-hanoi-day-trips-faq:v1',
    'related routes shortcode' => '[vg_related_routes]',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_hanoi_day_trips_ops_assert_required_content_markers($content, $required_content_markers);
vg_hanoi_day_trips_ops_assert_internal_page_links_are_published('Best Day Trips from Hanoi guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Best Day Trips from Hanoi',
    'post_name'      => 'best-day-trips-from-hanoi',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_hanoi_day_trips_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led guide to the best day trips from Hanoi: Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, and stay-in-Hanoi logic.',
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
    vg_hanoi_day_trips_ops_fail('Could not publish Best Day Trips from Hanoi: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_hanoi_day_trips_ops_fail('Could not publish Best Day Trips from Hanoi: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Best Day Trips from Hanoi: Ninh Binh, Ha Long or Stay?');
update_post_meta($page_id, 'rank_math_description', 'Best day trips from Hanoi, compared by route job: Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, timing, weather, and skip logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'best day trips from Hanoi');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Choose whether a Hanoi stay should use one outside day for Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or stay in Hanoi based on route job, transfer friction, season, booking quality, and overnight logic.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published Best Day Trips from Hanoi with a photo-led hero, concierge verdict, editorial proof panel, fast answer table, licensed real photo proof, source-diversity table, decision grid for staying in Hanoi, Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, and Ba Vi, quick chooser, route-fit table, day-placement logic, transfer-to-experience scorecard, day-trip versus overnight matrix, booking-mode matrix, operator due diligence, family and comfort fit, skip logic, live checks, FAQ, Destinations hub note, homepage route-spine note, inbound related routes, source trail, and update log.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - 5 Hanoi day trips - https://vietnam.travel/things-to-do/5-hanoi-day-trips - checked {$review_date}\nVietnam.travel - Ninh Binh destination page - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh - checked {$review_date}\nVietnam.travel - Ha Long destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-long - checked {$review_date}\nUNESCO World Heritage Centre - Ha Long Bay - https://whc.unesco.org/en/list/672/ - checked {$review_date}\nVietnam.travel - Full-day trip to Bat Trang pottery village - https://vietnam.travel/things-to-do/full-day-trip-bat-trang-pottery-village - checked {$review_date}\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}\nNational Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked {$review_date}\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}\nNoi Bai International Airport - https://vietnamairport.vn/en/noi-bai-airport - checked {$review_date}\nOfficial Vietnam e-visa portal - https://evisa.gov.vn/ - checked {$review_date}\nWikimedia Commons image record - Hanoi Hoan Kiem Lake - https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg - license checked {$review_date}\nWikimedia Commons image record - Trang An Landscape Complex, Ninh Binh Province - https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg - license checked {$review_date}\nWikimedia Commons image record - Ha Long Bay, Vietnam, View from above - https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg - license checked {$review_date}\nWikimedia Commons image record - Bat Trang pottery and ceramics village in 2016 20 - https://commons.wikimedia.org/wiki/File:Bat_Trang_pottery_and_ceramics_village_in_2016_20.jpg - license checked {$review_date}\nWikimedia Commons image record - VN Chua Huong5 tango7174 - https://commons.wikimedia.org/wiki/File:VN_Chua_Huong5_tango7174.jpg - license checked {$review_date}\nWikimedia Commons image record - Ba-Vi2-Vietnam - https://commons.wikimedia.org/wiki/File:Ba-Vi2-Vietnam.jpg - license checked {$review_date}");
update_post_meta($page_id, 'vg_eeat_field_note', 'A Hanoi day trip should be selected by the job it performs in the route. Ninh Binh is the strongest full-day default, Ha Long or Lan Ha usually deserves an overnight if the bay matters, Bat Trang solves a lighter craft day, Duong Lam solves slow village context, Perfume Pagoda solves pilgrimage journey, Ba Vi solves flexible forest/cooler-air relief, and staying in Hanoi often beats a famous but rushed outside day.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Best Day Trips from Hanoi built as an excursion decision guide rather than a generic listicle.\nConcierge verdict starts with the stay-in-Hanoi and overnight-upgrade decisions before ranking outside trips.\nFast answer table and quick chooser convert traveler situations into decisions instead of repeating attractions.\nLicensed real photo proof covers Hanoi, Trang An/Ninh Binh, Ha Long Bay, Bat Trang, Perfume Pagoda, and Ba Vi.\nSource-diversity panel separates official tourism, UNESCO, weather, transport, airport, e-visa, and image-license sources from VietnamGuide editorial judgment.\nDecision grid compares staying in Hanoi, Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, and Ba Vi by route job and skip conditions.\nRoute-fit table maps 7, 10, 14, and 21-day Vietnam trips to realistic day-trip use.\nDay placement, transfer-to-experience ratio, overnight-upgrade logic, booking mode, operator checks, comfort fit, and skip logic help readers reject weak same-day plans before comparing prices.\nLive checks keep official sources visible without scattering linkouts across the body.\nRelated routes, source trail, update log, author/editor accountability, and explicit affiliate status keep the guide reviewable and durable.");

$related_routes = "Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Decide how Hanoi should anchor the north before adding an outside day.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nWhere to Stay in Hanoi | /destinations/where-to-stay-in-hanoi/ | Choose the base that makes pickup, sleep, first-night recovery, and return logistics easier.\nOld Quarter vs French Quarter vs West Lake | /compare/old-quarter-vs-french-quarter-vs-west-lake/ | Refine the sleep zone before paying for day-trip pickup convenience or calmer return nights.\nBest Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Protect a real capital day before outsourcing the itinerary to long transfers.\nNinh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide whether Ninh Binh should be a day trip, overnight countryside stop, or skip.\nHa Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Use when the bay may deserve an overnight cruise instead of a same-day preview.\nHa Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Compare bay choices before buying a cruise route, port, and weather policy.\nCat Ba Travel Guide | /destinations/cat-ba-travel-guide/ | Use when Cat Ba island-base logic may replace or improve a cruise plan.\nBai Tu Long Bay Guide | /destinations/bai-tu-long-bay-guide/ | Consider quieter bay logic only after Hanoi, Ninh Binh, and core bay needs are clear.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before adding day trips.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether northern Vietnam should carry the trip before adding outside days.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check weather pressure before boat, bay, mountain, or exposed-viewpoint days.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Count pickups, private cars, shuttles, cruise transfers, trains, and airport buffers.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the real cost of private timing, guide context, tickets, meals, and lost Hanoi time.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Prepare cash, cards, deposits, tips, and backup before leaving Hanoi.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, pickup contact, operator messaging, and hotel coordination alive on day trips.\nVietnam E-Visa | /plan/vietnam-evisa/ | Recheck official entry timing before building Hanoi around arrival assumptions.\n7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Decide whether a one-week route should stay north-focused and choose only one hard highlight.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Hanoi can support Ninh Binh, the bay, and Central Vietnam without rushing.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when two weeks can turn a day trip into a better overnight chapter.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use three weeks to choose deeper northern extensions instead of collecting every outside day.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check road, boat, weather, medical, and trip-interruption coverage before booking.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair tours, taxis, deposits, cash, phone handling, and road days with practical risk habits.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.";
vg_hanoi_day_trips_ops_assert_related_route_meta_links_are_published('Best Day Trips from Hanoi related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero image: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0. Body images: Trang An Landscape Complex, Ninh Binh Province by Jakub Halun, CC BY 4.0; Ha Long Bay, Vietnam, View from above by Vyacheslav Argenberg, CC BY 4.0; Bat Trang pottery and ceramics village in 2016 20 by Vuong Tri Binh, CC BY-SA 4.0; VN Chua Huong5 tango7174 by Tango7174, CC BY 3.0; Ba-Vi2-Vietnam by Steven C. Price, CC BY-SA 4.0.');
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
        vg_hanoi_day_trips_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_hanoi_day_trips_ops_fail('Best Day Trips from Hanoi was updated but is not published.');
}

vg_hanoi_day_trips_ops_refresh_destinations_hub();
vg_hanoi_day_trips_ops_refresh_homepage_route_spine();
vg_hanoi_day_trips_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_hanoi_day_trips_ops_log("Published Best Day Trips from Hanoi: {$page_id} {$updated_permalink}");

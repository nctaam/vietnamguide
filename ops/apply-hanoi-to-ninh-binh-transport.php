<?php
/**
 * Publish the Hanoi to Ninh Binh Transport guide.
 *
 * Self-reference marker: ops/apply-hanoi-to-ninh-binh-transport.php
 *
 * Run from the WordPress root with:
 * VG_FORCE_HANOI_NINH_BINH_TRANSPORT_REPUBLISH=1 wp eval-file ops/apply-hanoi-to-ninh-binh-transport.php --allow-root
 *
 * Repair only hub/related-route/homepage side effects with:
 * VG_REPAIR_HANOI_NINH_BINH_TRANSPORT_LINKS=1 wp eval-file ops/apply-hanoi-to-ninh-binh-transport.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_hanoi_ninh_binh_transport_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_hanoi_ninh_binh_transport_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_hanoi_ninh_binh_transport_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_HANOI_NINH_BINH_TRANSPORT_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_hanoi_ninh_binh_transport_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_HANOI_NINH_BINH_TRANSPORT_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_hanoi_ninh_binh_transport_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_hanoi_ninh_binh_transport_ops_fail('Could not resolve a valid WordPress author for Hanoi to Ninh Binh Transport.');
}

function vg_hanoi_ninh_binh_transport_ops_internal_path_from_href(string $href): ?string
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

function vg_hanoi_ninh_binh_transport_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_hanoi_ninh_binh_transport_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_hanoi_ninh_binh_transport_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_ninh_binh_transport_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_hanoi_ninh_binh_transport_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_hanoi_ninh_binh_transport_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_hanoi_ninh_binh_transport_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_hanoi_ninh_binh_transport_ops_log("Validated {$label} internal page links are published.");
}

function vg_hanoi_ninh_binh_transport_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_hanoi_ninh_binh_transport_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_hanoi_ninh_binh_transport_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_hanoi_ninh_binh_transport_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_hanoi_ninh_binh_transport_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_hanoi_ninh_binh_transport_ops_log("Validated {$label} related-route links are published.");
}

function vg_hanoi_ninh_binh_transport_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_hanoi_ninh_binh_transport_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
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
            vg_hanoi_ninh_binh_transport_ops_fail("Could not confidently refresh {$label}.");
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
        vg_hanoi_ninh_binh_transport_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_hanoi_ninh_binh_transport_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_hanoi_ninh_binh_transport_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_hanoi_ninh_binh_transport_ops_homepage(): ?WP_Post
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

function vg_hanoi_ninh_binh_transport_ops_refresh_plan_hub(): void
{
    $hub = get_page_by_path('plan', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_hanoi_ninh_binh_transport_ops_log('Skipped Plan hub refresh: plan page was not found or is not published.');
        return;
    }

    if (! vg_hanoi_ninh_binh_transport_ops_published_page_exists('plan/hanoi-to-ninh-binh-transport')) {
        vg_hanoi_ninh_binh_transport_ops_log('Skipped Plan hub refresh: Hanoi to Ninh Binh Transport is not published.');
        return;
    }

    $marker = '<!-- vg-hanoi-ninh-binh-transport-plan-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Make the Hanoi to Ninh Binh transfer a route decision</h3><p><a href="/plan/hanoi-to-ninh-binh-transport/">Hanoi to Ninh Binh Transport</a> helps travelers choose train, limousine van, private car, day tour, or onward transfer by drop-off, luggage, hotel base, timing, and next-route fragility.</p></div>
<!-- /wp:group -->
HTML;

    vg_hanoi_ninh_binh_transport_ops_assert_internal_page_links_are_published('Plan hub Hanoi to Ninh Binh transport note', $block);
    vg_hanoi_ninh_binh_transport_ops_upsert_marked_group($hub, 'Plan hub Hanoi to Ninh Binh transport note', $marker, $block);
}

function vg_hanoi_ninh_binh_transport_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_hanoi_ninh_binh_transport_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_hanoi_ninh_binh_transport_ops_published_page_exists('plan/hanoi-to-ninh-binh-transport')) {
        vg_hanoi_ninh_binh_transport_ops_log('Skipped Destinations hub refresh: Hanoi to Ninh Binh Transport is not published.');
        return;
    }

    $marker = '<!-- vg-hanoi-ninh-binh-transport-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Connect Hanoi and Ninh Binh without wasting the best hours</h3><p>After choosing the <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a> or <a href="/compare/ninh-binh-day-trip-vs-overnight/">Ninh Binh Day Trip vs Overnight</a>, use <a href="/plan/hanoi-to-ninh-binh-transport/">Hanoi to Ninh Binh Transport</a> to decide whether rail, van, private car, tour transport, or onward transfer actually fits the base.</p></div>
<!-- /wp:group -->
HTML;

    vg_hanoi_ninh_binh_transport_ops_assert_internal_page_links_are_published('Destinations hub Hanoi to Ninh Binh transport note', $block);
    vg_hanoi_ninh_binh_transport_ops_upsert_marked_group($hub, 'Destinations hub Hanoi to Ninh Binh transport note', $marker, $block);
}

function vg_hanoi_ninh_binh_transport_ops_refresh_homepage_route_spine(): void
{
    $home = vg_hanoi_ninh_binh_transport_ops_homepage();

    if (! $home instanceof WP_Post) {
        vg_hanoi_ninh_binh_transport_ops_log('Skipped homepage route-spine refresh: homepage was not found or is not published.');
        return;
    }

    if (! vg_hanoi_ninh_binh_transport_ops_published_page_exists('plan/hanoi-to-ninh-binh-transport')) {
        vg_hanoi_ninh_binh_transport_ops_log('Skipped homepage route-spine refresh: Hanoi to Ninh Binh Transport is not published.');
        return;
    }

    $marker = '<!-- vg-hanoi-ninh-binh-transport-homepage-route-spine:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-home-section vg-home-decision-spine vg-home-hanoi-ninh-binh-transport-spine"} -->
<div class="wp-block-group vg-home-section vg-home-decision-spine vg-home-hanoi-ninh-binh-transport-spine"><p class="vg-kicker">Northern transfer decision</p><h2>Hanoi to Ninh Binh Transport</h2><p>Use this guide once Ninh Binh is in the route and the question becomes train, limousine van, private car, day tour, or onward handoff. The right answer depends on final drop-off, luggage, sleep, weather, and whether tomorrow morning is already fragile.</p><p class="vg-section-link"><a href="/plan/hanoi-to-ninh-binh-transport/">Choose the Ninh Binh transfer</a></p></div>
<!-- /wp:group -->
HTML;

    vg_hanoi_ninh_binh_transport_ops_assert_internal_page_links_are_published('Homepage Hanoi to Ninh Binh transport route-spine note', $block);
    vg_hanoi_ninh_binh_transport_ops_upsert_marked_group($home, 'Homepage Hanoi to Ninh Binh transport route-spine note', $marker, $block);
}

function vg_hanoi_ninh_binh_transport_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_ninh_binh_transport_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_hanoi_ninh_binh_transport_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_hanoi_ninh_binh_transport_ops_log("Skipped related-route refresh for {$label}: Hanoi to Ninh Binh Transport line is already current.");
        return;
    }

    update_post_meta($page->ID, 'vg_eeat_related_routes', implode("\n", $next_lines));

    vg_hanoi_ninh_binh_transport_ops_log("Upserted Hanoi to Ninh Binh Transport related route to {$label}: {$page->ID}");
}

function vg_hanoi_ninh_binh_transport_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.';

    foreach (
        [
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'compare/ninh-binh-day-trip-vs-overnight' => 'Ninh Binh Day Trip vs Overnight',
            'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'itineraries/hanoi-in-2-days' => 'Hanoi in 2 Days',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
        ] as $path => $label
    ) {
        vg_hanoi_ninh_binh_transport_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

function vg_hanoi_ninh_binh_transport_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_hanoi_ninh_binh_transport_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

$parent = get_page_by_path('plan', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_hanoi_ninh_binh_transport_ops_fail('Could not find published /plan/ parent page.');
}

$page = get_page_by_path('plan/hanoi-to-ninh-binh-transport', OBJECT, 'page');

if (vg_hanoi_ninh_binh_transport_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_ninh_binh_transport_ops_fail('Repair mode requires Hanoi to Ninh Binh Transport to already be published.');
    }

    vg_hanoi_ninh_binh_transport_ops_refresh_plan_hub();
    vg_hanoi_ninh_binh_transport_ops_refresh_destinations_hub();
    vg_hanoi_ninh_binh_transport_ops_refresh_homepage_route_spine();
    vg_hanoi_ninh_binh_transport_ops_refresh_inbound_related_routes();
    vg_hanoi_ninh_binh_transport_ops_log("Repaired Hanoi to Ninh Binh Transport side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_hanoi_ninh_binh_transport_ops_force_republish_enabled()) {
    vg_hanoi_ninh_binh_transport_ops_fail('Hanoi to Ninh Binh Transport is not a draft. Set VG_FORCE_HANOI_NINH_BINH_TRANSPORT_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_hanoi_ninh_binh_transport_ops_log("Preflight Hanoi to Ninh Binh Transport: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_hanoi_ninh_binh_transport_ops_log('Preflight Hanoi to Ninh Binh Transport: no existing page found; creating a child page under /plan/.');
}

$review_date = 'July 24, 2026';
$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/95/Hanoi_Railway_Station_20130725.jpg/1920px-Hanoi_Railway_Station_20130725.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi_Railway_Station_20130725.jpg');
$trang_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');
$trang_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg');
$tam_coc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Tam_Coc_Ninh_Binh_%2829079%29.jpg/1920px-Tam_Coc_Ninh_Binh_%2829079%29.jpg');
$tam_coc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Tam_Coc_Ninh_Binh_(29079).jpg');
$airport_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/56/Noi_Bai_International_Airport_T2_Waiting_Area.jpg/1920px-Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$airport_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg');

$content = <<<HTML
<!-- vg-hanoi-ninh-binh-transport-hero:v1 -->
<!-- wp:cover {"url":"{$hero_image}","alt":"Hanoi Railway Station before a transfer to Ninh Binh","dimRatio":58,"minHeight":620,"className":"vg-guide-hero-cover vg-hanoi-ninh-binh-transport-hero"} -->
<div class="wp-block-cover vg-guide-hero-cover vg-hanoi-ninh-binh-transport-hero" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-60 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hanoi Railway Station before a transfer to Ninh Binh" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Northern transfer guide</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Hanoi to Ninh Binh Transport</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Choose the Hanoi to Ninh Binh transfer after the route question is settled. This guide compares train, limousine van, private car, day tour, and onward transfer by final drop-off, luggage, base choice, weather, and what tomorrow morning still needs to protect.</p>
<!-- /wp:paragraph -->
<p class="vg-image-credit">Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Alancrh / CC BY-SA 3.0</a>.</p>
</div></div>
<!-- /wp:cover -->

<!-- vg-hanoi-ninh-binh-transport-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-hanoi-ninh-binh-transport-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-hanoi-ninh-binh-transport-concierge-verdict">
<!-- wp:heading -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most independent international travelers, the best default is a limousine van only when the exact drop-off works; otherwise choose train for rail-centered plans or private car when control protects the day.</strong> Private car is the premium answer when luggage, children, a late arrival, a countryside hotel, or an onward bay/airport handoff would make shared transport brittle. Train is a clean choice when Ninh Binh station is useful, not when the real destination is a Tam Coc lane without a final-transfer plan.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list"} -->
<ul class="wp-block-list vg-check-list">
<li><strong>Best value default:</strong> limousine van from Hanoi to Tam Coc, Trang An area, or Ninh Binh city when pickup/drop-off is written clearly.</li>
<li><strong>Best premium default:</strong> private car when time, luggage, hotel lane, children, rain, or a next transfer matters more than the fare.</li>
<li><strong>Best rail answer:</strong> train when you want station certainty, prefer public transport, or are connecting onward by rail.</li>
<li><strong>Best non-transfer answer:</strong> day tour only when returning to Hanoi and not changing hotels.</li>
<li><strong>First cut:</strong> skip any option that hides pickup zone, drop-off point, luggage terms, waiting policy, or realistic arrival time.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-hanoi-ninh-binh-transport-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-hanoi-ninh-binh-transport-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-hanoi-ninh-binh-transport-at-a-glance">
<!-- wp:heading -->
<h2 class="wp-block-heading">Fast answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table">
<thead><tr><th>Traveler situation</th><th>Best first answer</th><th>Why it works</th><th>Live check</th></tr></thead>
<tbody>
<tr><td data-label="Traveler situation">One night in Tam Coc or Trang An</td><td data-label="Best first answer">Limousine van or private car</td><td data-label="Why it works">Door-to-area transport protects hotel handoff better than station-only thinking.</td><td data-label="Live check">Exact drop-off, luggage, hotel lane, pickup zone.</td></tr>
<tr><td data-label="Traveler situation">Budget traveler staying near Ninh Binh city</td><td data-label="Best first answer">Train</td><td data-label="Why it works">Station arrival is useful when the city is the base or onward rail matters.</td><td data-label="Live check">dsvn.vn train number, seat class, station, name rules.</td></tr>
<tr><td data-label="Traveler situation">Family, heavy bags, premium short route</td><td data-label="Best first answer">Private car</td><td data-label="Why it works">Control is worth more than mode purity when tomorrow is important.</td><td data-label="Live check">Pickup time, stops, waiting, child seats, luggage.</td></tr>
<tr><td data-label="Traveler situation">Hanoi day trip only</td><td data-label="Best first answer">Day tour or private day car</td><td data-label="Why it works">Return logic, tickets, lunch, and route order matter more than one-way transport.</td><td data-label="Live check">Stop order, return hour, cancellation, group size.</td></tr>
<tr><td data-label="Traveler situation">Ninh Binh to bay or airport next</td><td data-label="Best first answer">Private or operator-confirmed onward transfer</td><td data-label="Why it works">The second leg can break the route if treated as an afterthought.</td><td data-label="Live check">Actual pickup location, arrival buffer, backup plan.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-hanoi-ninh-binh-transport-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what the transfer is really solving</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The transport choice is not only about the two cities. It is about how cleanly the traveler moves from Hanoi streets or stations into a Ninh Binh base that may sit outside the city grid.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-hanoi-ninh-binh-transport-photo-grid">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Hanoi Railway Station as a rail starting point for Ninh Binh" loading="lazy" decoding="async"><figcaption>Rail is strongest when station certainty matters more than door-to-door convenience. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Alancrh / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$trang_an_image}" alt="Trang An limestone landscape in Ninh Binh" loading="lazy" decoding="async"><figcaption>Trang An rewards arrival planning because the best hours are not the same as the easiest transfer hours. Image: <a href="{$trang_an_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$tam_coc_image}" alt="Tam Coc river and limestone scenery near Ninh Binh hotels" loading="lazy" decoding="async"><figcaption>Tam Coc works best when the transfer reaches the base, not just a city station. Image: <a href="{$tam_coc_credit_url}" target="_blank" rel="license noopener">Andre Hospers / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$airport_image}" alt="Noi Bai International Airport terminal for travelers connecting onward after Hanoi or Ninh Binh" loading="lazy" decoding="async"><figcaption>Airport pressure changes the answer when Ninh Binh is placed close to arrival or departure. Image: <a href="{$airport_credit_url}" target="_blank" rel="license noopener">Sky 269 / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-hanoi-ninh-binh-transport-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Source discipline: what we can and cannot prove</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Transport pages become spammy when they pretend stale schedules are universal advice. Sources can confirm official destination context, rail portals, and tourism framing; they cannot decide your luggage, sleep debt, hotel lane, or tomorrow morning.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-ninh-binh-transport-source-diversity">
<thead><tr><th>Source layer</th><th>Use it for</th><th>Do not overuse it for</th></tr></thead>
<tbody>
<tr><td data-label="Source layer">Vietnam.travel Ninh Binh</td><td data-label="Use it for">Official destination framing and broad access modes from Hanoi.</td><td data-label="Do not overuse it for">Your exact hotel drop-off or same-week operator timing.</td></tr>
<tr><td data-label="Source layer">Vietnam Railways and dsvn.vn</td><td data-label="Use it for">Current rail search, train numbers, seat classes, station and ticket checks.</td><td data-label="Do not overuse it for">Tam Coc/Trang An final transfer quality.</td></tr>
<tr><td data-label="Source layer">Ninh Binh Tourism Department</td><td data-label="Use it for">Local destination context, attractions, and tourism notices.</td><td data-label="Do not overuse it for">Private operator reliability claims.</td></tr>
<tr><td data-label="Source layer">VietnamGuide judgment</td><td data-label="Use it for">Door-to-door fit, route fragility, luggage, family comfort, and when to upgrade.</td><td data-label="Do not overuse it for">Unverified live schedules or prices.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-ninh-binh-transport-source-trail-snapshot:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Source trail snapshot</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This rendered Hanoi to Ninh Binh transport source trail snapshot keeps the evidence visible without turning the article into a link directory.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-source-snapshot">
<thead><tr><th>Checked source</th><th>Snapshot value</th><th>How it shapes this page</th></tr></thead>
<tbody>
<tr><td data-label="Checked source">https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh</td><td data-label="Snapshot value">Official Ninh Binh page frames Hanoi access by bus, luxury van, private car, and train.</td><td data-label="How it shapes this page">Mode comparison is legitimate, but final recommendation still depends on drop-off.</td></tr>
<tr><td data-label="Checked source">https://vietnam.travel/plan-your-trip/transport-within-vietnam</td><td data-label="Snapshot value">Official transport context supports choosing mode by distance, comfort, and route fit.</td><td data-label="How it shapes this page">The page compares door-to-door outcomes, not just ticket names.</td></tr>
<tr><td data-label="Checked source">https://dsvn.vn/ and https://vr.com.vn/en</td><td data-label="Snapshot value">Official railway portals are the live-check layer for trains.</td><td data-label="How it shapes this page">Train advice stays evergreen and avoids stale timetable copying.</td></tr>
<tr><td data-label="Checked source">https://dulichninhbinh.com.vn/en/ and https://whc.unesco.org/en/list/1438/</td><td data-label="Snapshot value">Local tourism and UNESCO context explain why Ninh Binh is more than a transfer endpoint.</td><td data-label="How it shapes this page">The guide protects the best landscape hours after arrival.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-ninh-binh-transport-mode-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mode verdict matrix</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-ninh-binh-transport-mode-matrix">
<thead><tr><th>Mode</th><th>Choose it when</th><th>Avoid it when</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Mode">Limousine van</td><td data-label="Choose it when">The pickup zone and final drop-off match your hotel base.</td><td data-label="Avoid it when">The operator only says Ninh Binh without naming station, Tam Coc, Trang An, or hotel drop-off.</td><td data-label="VietnamGuide verdict">Best value for many travelers, fragile when details are vague.</td></tr>
<tr><td data-label="Mode">Train</td><td data-label="Choose it when">Rail rhythm, station certainty, or onward rail is part of the plan.</td><td data-label="Avoid it when">You will still need a stressful last-mile transfer with luggage.</td><td data-label="VietnamGuide verdict">Good logistics tool, not automatically the most convenient option.</td></tr>
<tr><td data-label="Mode">Private car</td><td data-label="Choose it when">Control protects a premium short stay, family comfort, weather, or onward timing.</td><td data-label="Avoid it when">You will add unnecessary stops just to justify the cost.</td><td data-label="VietnamGuide verdict">Best comfort choice when the route is valuable or fragile.</td></tr>
<tr><td data-label="Mode">Day tour transport</td><td data-label="Choose it when">You return to Hanoi and want tickets, lunch, and route order bundled.</td><td data-label="Avoid it when">You are moving hotels or need control over the countryside evening.</td><td data-label="VietnamGuide verdict">Useful product, weak substitute for overnight transport.</td></tr>
<tr><td data-label="Mode">Motorbike or scooter</td><td data-label="Choose it when">Rarely: only if licensed, insured, experienced, sober, and weather-aware.</td><td data-label="Avoid it when">You are carrying luggage, riding in rain, or using it to save a transfer.</td><td data-label="VietnamGuide verdict">Not recommended for most international travelers.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-ninh-binh-transport-door-to-door:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The door-to-door equation</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The wrong comparison is train time versus van time. The useful comparison is hotel door, pickup wait, departure point, travel time, arrival point, final transfer, check-in, and the quality of the first useful Ninh Binh hour.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-ninh-binh-transport-door-to-door">
<thead><tr><th>Hidden piece</th><th>Why it matters</th><th>What to ask before paying</th></tr></thead>
<tbody>
<tr><td data-label="Hidden piece">Hanoi pickup zone</td><td data-label="Why it matters">Old Quarter, French Quarter, West Lake, and airport-side hotels do not create the same morning.</td><td data-label="What to ask before paying">Is pickup at my hotel, a meeting point, or a route-side stop?</td></tr>
<tr><td data-label="Hidden piece">Final drop-off</td><td data-label="Why it matters">Ninh Binh city, Tam Coc, Trang An, and rural lodges can be meaningfully different arrivals.</td><td data-label="What to ask before paying">Will I be left at my hotel, town center, station, or a transfer office?</td></tr>
<tr><td data-label="Hidden piece">Luggage handling</td><td data-label="Why it matters">A compact van is easy until oversized bags, rain, or split drop-offs enter the day.</td><td data-label="What to ask before paying">What is included, what costs extra, and where is luggage stored?</td></tr>
<tr><td data-label="Hidden piece">Arrival quality</td><td data-label="Why it matters">A cheap arrival that kills the boat, cycling, or countryside evening is expensive in route value.</td><td data-label="What to ask before paying">What useful hour do I gain after check-in?</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-ninh-binh-transport-departure-arrival:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Departure and arrival map</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Hanoi to Ninh Binh is a short enough move that the last mile can matter more than the main vehicle. Decide the base before deciding the mode.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-ninh-binh-transport-departure-arrival">
<thead><tr><th>Base plan</th><th>Best transport fit</th><th>Reason</th><th>Risk to manage</th></tr></thead>
<tbody>
<tr><td data-label="Base plan">Tam Coc base</td><td data-label="Best transport fit">Van or private car</td><td data-label="Reason">The route should reach the village or hotel, not only the city.</td><td data-label="Risk to manage">Vague drop-off promises and late arrival after dark.</td></tr>
<tr><td data-label="Base plan">Trang An countryside</td><td data-label="Best transport fit">Private car, careful van, or hotel-arranged pickup</td><td data-label="Reason">Atmospheric lodges can sit away from easy shared drop-offs.</td><td data-label="Risk to manage">Last-mile taxi availability and poor weather.</td></tr>
<tr><td data-label="Base plan">Ninh Binh city</td><td data-label="Best transport fit">Train or van</td><td data-label="Reason">City logistics make station and town arrival easier.</td><td data-label="Risk to manage">Less countryside feel outside the door.</td></tr>
<tr><td data-label="Base plan">Day trip returning to Hanoi</td><td data-label="Best transport fit">Day tour or private day car</td><td data-label="Reason">The route order matters more than independent one-way movement.</td><td data-label="Risk to manage">Too many stops and a late return before tomorrow's transfer.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-ninh-binh-transport-train-guide:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Train: best when station logic is real</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Train is the cleanest public-transport choice when Hanoi station departure and Ninh Binh station arrival both help the route. It is weaker when the traveler is actually staying in Tam Coc or Trang An and has not planned the final transfer.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-ninh-binh-transport-train-guide"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-ninh-binh-transport-train-guide">
<li>Use dsvn.vn or Vietnam Railways close to travel for live train choices, seat classes, ticket name rules, and any timetable change.</li>
<li>Choose rail when you want predictable stations, fewer pickup calls, or an onward rail route later in the trip.</li>
<li>Do not choose rail just because it sounds more local if the last mile will be wet, late, confusing, or luggage-heavy.</li>
<li>Build in time for station access in Hanoi and a separate transfer from Ninh Binh station to Tam Coc, Trang An, or the hotel.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-ninh-binh-transport-limousine-van:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Limousine van: best value when the drop-off is specific</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Limousine van is often the best balance for independent travelers because it can connect hotel areas and Ninh Binh bases with less friction than station-only travel. The quality difference is in the details, not the word limousine.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-ninh-binh-transport-limousine-van">
<thead><tr><th>Ask this</th><th>Green answer</th><th>Red flag</th></tr></thead>
<tbody>
<tr><td data-label="Ask this">Where is Hanoi pickup?</td><td data-label="Green answer">Named hotel area, meeting point, or written pickup address.</td><td data-label="Red flag">"Old Quarter" without exact confirmation.</td></tr>
<tr><td data-label="Ask this">Where is Ninh Binh drop-off?</td><td data-label="Green answer">Hotel, Tam Coc office, Trang An area, Ninh Binh city, or station named clearly.</td><td data-label="Red flag">"Ninh Binh" as if it were one compact point.</td></tr>
<tr><td data-label="Ask this">What happens to luggage?</td><td data-label="Green answer">Included bag policy and storage location explained.</td><td data-label="Red flag">No luggage terms for a hotel-change day.</td></tr>
<tr><td data-label="Ask this">What is the arrival buffer?</td><td data-label="Green answer">Enough time for check-in before the next activity.</td><td data-label="Red flag">Arrival timed to a boat slot with no margin.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-ninh-binh-transport-private-car:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Private car: premium when control changes the day</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Private car earns the spend when it protects the best Ninh Binh hours or prevents a fragile handoff. It is not automatically better than train or van; it is better when control, rest, and sequence matter.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-ninh-binh-transport-private-car"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-ninh-binh-transport-private-car">
<li>Use it for families, older travelers, heavy luggage, late arrival, hotel-to-hotel movement, bad weather, or a tight onward leg.</li>
<li>Confirm vehicle size, pickup time, waiting policy, tolls, child seats, and whether attraction stops change the quoted price.</li>
<li>Keep the plan restrained: the car should buy better timing, not a bloated stop list.</li>
<li>Pair private car with <a href="/compare/ninh-binh-day-trip-vs-overnight/">Ninh Binh Day Trip vs Overnight</a> if the extra control might justify one protected night.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-ninh-binh-transport-day-tour-onward:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Day tour, overnight move, or onward transfer?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A day tour is not a substitute for transport to an overnight base. It solves a different job: return to Hanoi with a fixed route, tickets, lunch, and a guide or driver bundled into one long outside day.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-ninh-binh-transport-day-tour-onward">
<thead><tr><th>Route job</th><th>Use</th><th>Avoid</th></tr></thead>
<tbody>
<tr><td data-label="Route job">Return to Hanoi tonight</td><td data-label="Use">Day tour or private day car with a realistic stop order.</td><td data-label="Avoid">Independent one-way transport unless you enjoy solving return logistics late.</td></tr>
<tr><td data-label="Route job">Sleep in Ninh Binh tonight</td><td data-label="Use">Van, train plus transfer, or private car to the actual base.</td><td data-label="Avoid">Tours that return to Hanoi after you wanted a countryside evening.</td></tr>
<tr><td data-label="Route job">Move onward to bay, airport, or Central Vietnam</td><td data-label="Use">Operator-confirmed onward pickup, private car, or rail if the station works.</td><td data-label="Avoid">Assuming every Hanoi-based tour can drop you neatly into the next leg.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-ninh-binh-transport-base-luggage:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Base and luggage logic</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Transport should be chosen after the sleep base. A Tam Coc guesthouse, Trang An lodge, Ninh Binh city hotel, and Hanoi day-trip pickup each create a different luggage problem.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-ninh-binh-transport-base-luggage">
<thead><tr><th>Traveler load</th><th>Best move</th><th>Reason</th></tr></thead>
<tbody>
<tr><td data-label="Traveler load">One carry-on, city hotel</td><td data-label="Best move">Train or van</td><td data-label="Reason">The last mile is easy enough to keep options open.</td></tr>
<tr><td data-label="Traveler load">Two big bags, countryside stay</td><td data-label="Best move">Private car or hotel-confirmed van</td><td data-label="Reason">The drop-off matters more than the headline fare.</td></tr>
<tr><td data-label="Traveler load">Children or older travelers</td><td data-label="Best move">Private car or carefully vetted van</td><td data-label="Reason">Bathroom stops, door proximity, weather, and waiting time become quality issues.</td></tr>
<tr><td data-label="Traveler load">Backpacker base near station</td><td data-label="Best move">Train</td><td data-label="Reason">Station arrival may be the cleanest and cheapest practical handoff.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-ninh-binh-transport-sample-routing:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Sample routing decisions</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-ninh-binh-transport-sample-routing">
<thead><tr><th>Route shape</th><th>Transport choice</th><th>What it protects</th></tr></thead>
<tbody>
<tr><td data-label="Route shape">Hanoi 3 nights, Ninh Binh day trip</td><td data-label="Transport choice">Day tour or private day car</td><td data-label="What it protects">A clean return to the same hotel and one focused outside day.</td></tr>
<tr><td data-label="Route shape">Hanoi to Tam Coc for one night</td><td data-label="Transport choice">Limousine van with hotel drop-off or private car</td><td data-label="What it protects">Countryside evening and a calmer Trang An or Tam Coc morning.</td></tr>
<tr><td data-label="Route shape">Hanoi to Ninh Binh city, then train south</td><td data-label="Transport choice">Train</td><td data-label="What it protects">Station familiarity and an easier onward rail plan.</td></tr>
<tr><td data-label="Route shape">Ninh Binh before Ha Long/Lan Ha</td><td data-label="Transport choice">Private or operator-confirmed onward transfer</td><td data-label="What it protects">The bay pickup and cruise check-in rather than only the Ninh Binh arrival.</td></tr>
<tr><td data-label="Route shape">Airport arrival then immediate Ninh Binh</td><td data-label="Transport choice">Private car after a serious fatigue check</td><td data-label="What it protects">A controlled first transfer, but only if the flight arrival is not too fragile.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-ninh-binh-transport-booking-audit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Before booking: the audit</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-ninh-binh-transport-booking-audit"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-ninh-binh-transport-booking-audit">
<li>Write the actual origin and destination: not just Hanoi and Ninh Binh, but hotel or station to hotel, office, station, boat dock, or onward pickup.</li>
<li>Check whether the transfer arrives early enough to make the first Ninh Binh hour useful.</li>
<li>Ask how changes, delays, no-shows, luggage, child seats, tolls, and waiting time are handled.</li>
<li>Do not trust copied train times, van pickups, or tour return windows without a same-week live check.</li>
<li>Keep payment, phone data, and hotel contact ready using <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a> and <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-ninh-binh-transport-failure-modes:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Failure modes that make the route feel cheap</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-ninh-binh-transport-failure-modes">
<thead><tr><th>Mistake</th><th>Why it hurts</th><th>Better correction</th></tr></thead>
<tbody>
<tr><td data-label="Mistake">Buying the lowest fare before choosing the base</td><td data-label="Why it hurts">The final transfer can erase the savings.</td><td data-label="Better correction">Pick Tam Coc, Trang An, or city first, then choose mode.</td></tr>
<tr><td data-label="Mistake">Assuming the train solves Ninh Binh</td><td data-label="Why it hurts">The station is not the same as the countryside hotel.</td><td data-label="Better correction">Add the last-mile plan before booking rail.</td></tr>
<tr><td data-label="Mistake">Treating limousine as a quality guarantee</td><td data-label="Why it hurts">The word may hide unclear pickup, shared delays, or vague drop-off.</td><td data-label="Better correction">Judge written details and recent operator behavior.</td></tr>
<tr><td data-label="Mistake">Forcing a day tour into an overnight move</td><td data-label="Why it hurts">The product is designed to return to Hanoi, not protect a new base.</td><td data-label="Better correction">Book one-way movement or private transfer intentionally.</td></tr>
<tr><td data-label="Mistake">Placing Ninh Binh before a fragile cruise or flight</td><td data-label="Why it hurts">One delay can cost the most expensive next booking.</td><td data-label="Better correction">Upgrade control or add a buffer night.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-ninh-binh-transport-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before travel</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-ninh-binh-transport-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-ninh-binh-transport-live-checks">
<li>Use dsvn.vn or Vietnam Railways close to departure before treating a train time, seat class, or ticket rule as fixed.</li>
<li>Use Vietnam.travel Ninh Binh and Transport Within Vietnam for official broad framing, then confirm private operator details directly.</li>
<li>Check Ninh Binh Tourism Department and local notices for destination context before planning attraction timing around a transfer.</li>
<li>Check same-week weather before relying on a late outdoor arrival, cycling handoff, Hang Mua climb, or tight boat route.</li>
<li>Use <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/compare/ninh-binh-day-trip-vs-overnight/">Ninh Binh Day Trip vs Overnight</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before final payment.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-ninh-binh-transport-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hanoi to Ninh Binh transport FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-hanoi-ninh-binh-transport-faq">
<details><summary>What is the best way to get from Hanoi to Ninh Binh?</summary><p>For many travelers, a limousine van is the best value if the exact drop-off works. Choose train when station logic fits. Choose private car when luggage, family comfort, hotel location, late timing, or onward transfers make control more valuable.</p></details>
<details><summary>Is the train from Hanoi to Ninh Binh worth it?</summary><p>Yes when Hanoi Railway Station and Ninh Binh station are convenient, or when you prefer public rail. It is weaker if you still need a separate late or luggage-heavy transfer to Tam Coc or Trang An.</p></details>
<details><summary>Should I book a limousine van?</summary><p>Book it when pickup zone, final drop-off, luggage rules, and arrival time are written clearly. Do not book based on the word limousine alone.</p></details>
<details><summary>When is private car worth it?</summary><p>Use private car when the route is short but valuable: family travel, premium hotels, heavy bags, early/late movement, rain, older travelers, or a tight onward bay, airport, or train handoff.</p></details>
<details><summary>Can a day tour replace transport to Ninh Binh?</summary><p>No. A day tour is built for returning to Hanoi. If you are sleeping in Ninh Binh, book one-way transport or private transfer to the actual base.</p></details>
<details><summary>Can I go from Hanoi airport straight to Ninh Binh?</summary><p>You can, but only do it when the arrival hour, visa/immigration margin, luggage, fatigue, and hotel check-in all work. For most first-time arrivals, a calmer Hanoi first night is safer.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a> to decide the destination role, then use <a href="/compare/ninh-binh-day-trip-vs-overnight/">Ninh Binh Day Trip vs Overnight</a> to choose pacing. Use <a href="/destinations/best-day-trips-from-hanoi/">Best Day Trips from Hanoi</a>, <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>, <a href="/itineraries/hanoi-in-2-days/">Hanoi in 2 Days</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before placing the transfer near a bay cruise, airport, or southbound move.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-hanoi-ninh-binh-transport-hero:v1',
    'concierge verdict' => 'vg-hanoi-ninh-binh-transport-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-hanoi-ninh-binh-transport-at-a-glance:v1',
    'photo grid' => 'vg-hanoi-ninh-binh-transport-photo-grid:v1',
    'source diversity' => 'vg-hanoi-ninh-binh-transport-source-diversity:v1',
    'source trail snapshot' => 'vg-hanoi-ninh-binh-transport-source-trail-snapshot:v1',
    'mode matrix' => 'vg-hanoi-ninh-binh-transport-mode-matrix:v1',
    'door to door' => 'vg-hanoi-ninh-binh-transport-door-to-door:v1',
    'departure arrival' => 'vg-hanoi-ninh-binh-transport-departure-arrival:v1',
    'train guide' => 'vg-hanoi-ninh-binh-transport-train-guide:v1',
    'limousine van' => 'vg-hanoi-ninh-binh-transport-limousine-van:v1',
    'private car' => 'vg-hanoi-ninh-binh-transport-private-car:v1',
    'day tour onward' => 'vg-hanoi-ninh-binh-transport-day-tour-onward:v1',
    'base luggage' => 'vg-hanoi-ninh-binh-transport-base-luggage:v1',
    'sample routing' => 'vg-hanoi-ninh-binh-transport-sample-routing:v1',
    'booking audit' => 'vg-hanoi-ninh-binh-transport-booking-audit:v1',
    'failure modes' => 'vg-hanoi-ninh-binh-transport-failure-modes:v1',
    'live checks' => 'vg-hanoi-ninh-binh-transport-live-checks:v1',
    'FAQ' => 'vg-hanoi-ninh-binh-transport-faq:v1',
    'related routes shortcode' => '[vg_related_routes]',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_hanoi_ninh_binh_transport_ops_assert_required_content_markers($content, $required_content_markers);
vg_hanoi_ninh_binh_transport_ops_assert_internal_page_links_are_published('Hanoi to Ninh Binh Transport guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Hanoi to Ninh Binh Transport',
    'post_name'      => 'hanoi-to-ninh-binh-transport',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_hanoi_ninh_binh_transport_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led guide to Hanoi to Ninh Binh transport by train, limousine van, private car, day tour, or onward transfer.',
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
    vg_hanoi_ninh_binh_transport_ops_fail('Could not publish Hanoi to Ninh Binh Transport: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_hanoi_ninh_binh_transport_ops_fail('Could not publish Hanoi to Ninh Binh Transport: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Hanoi to Ninh Binh Transport: Train, Van or Car?');
update_post_meta($page_id, 'rank_math_description', 'How to get from Hanoi to Ninh Binh: compare train, limousine van, private car, day tour and onward transfers by drop-off, luggage, timing and route fit.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Hanoi to Ninh Binh transport');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Choose Hanoi to Ninh Binh transport by final drop-off, luggage, hotel base, timing, weather, and next-route fragility instead of comparing only ticket price or timetable duration.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published Hanoi to Ninh Binh Transport with a photo-led hero, concierge verdict, proof panel, at-a-glance table, licensed photo proof, source-diversity panel, rendered source trail snapshot, mode matrix, door-to-door equation, departure/arrival base logic, train guide, limousine van audit, private-car guidance, day-tour/onward-transfer logic, luggage/base table, sample routing, booking audit, failure modes, live checks, FAQ, Plan and Destinations hub notes, homepage support, and inbound related routes.');

$sources_checked = implode(
    "\n",
    [
        "Vietnam.travel - Ninh Binh destination page - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh - checked {$review_date}; official destination page frames Hanoi access by bus, luxury van, private car, and train",
        "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}",
        "Vietnam Railways - official railway website - https://vr.com.vn/en - checked {$review_date}",
        "Vietnam Railways - official online railway ticketing portal - https://dsvn.vn/ - checked {$review_date}; live train times, train numbers, seat classes, and ticket details must be checked close to travel",
        "Vietnam Railways - timetable and fare lookup portal - https://k.vnticketonline.vn/ - checked {$review_date}; live HNO-to-NBI searches should be rerun for the exact travel date rather than copied into evergreen content",
        "Ninh Binh Tourism Department - Tourism Promotion Information Center - https://dulichninhbinh.com.vn/en/ - checked {$review_date}",
        "Ninh Binh Tourism Department - transport category - https://dulichninhbinh.com.vn/cat/1504 - checked {$review_date}; local tourism portal groups bus station, railway station, taxi, and limousine transport support resources",
        "Ninh Binh Tourism Department - Ninh Binh railway station listing - https://dulichninhbinh.com.vn/item/1240 - checked {$review_date}; local listing identifies Ga xe lua Ninh Binh on Ngo Gia Tu Street, Hoa Lu ward, Ninh Binh",
        "Ninh Binh Tourism Department - Ninh Binh bus station listing - https://dulichninhbinh.com.vn/item/1238 - checked {$review_date}; local listing identifies Ninh Binh bus station at 207 Le Dai Hanh, Hoa Lu ward, Ninh Binh",
        "Noi Bai International Airport - public transportation - https://noibaiairport.vn/en/public-transportations-nid1.html - checked {$review_date}; relevant for budget travelers connecting airport bus to Hanoi Railway Station before rail",
        "UNESCO World Heritage Centre - Trang An Landscape Complex - https://whc.unesco.org/en/list/1438/ - checked {$review_date}",
        "Wikimedia Commons image record - Hanoi Railway Station - https://commons.wikimedia.org/wiki/File:Hanoi_Railway_Station_20130725.jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Trang An Landscape Complex, Ninh Binh Province - https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Tam Coc Ninh Binh - https://commons.wikimedia.org/wiki/File:Tam_Coc_Ninh_Binh_(29079).jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Noi Bai International Airport T2 Waiting Area - https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg - license checked {$review_date}",
    ]
);
update_post_meta($page_id, 'vg_eeat_sources_checked', $sources_checked);
update_post_meta($page_id, 'vg_eeat_field_note', 'Hanoi to Ninh Binh transport is a door-to-door decision. The right mode changes with the actual Hanoi pickup, Ninh Binh base, luggage, children, weather, arrival fatigue, and whether the next route leg is already fragile.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Hanoi to Ninh Binh Transport built as a decision tool rather than a recycled transport list.\nConcierge verdict separates limousine van, train, private car, day tour, onward transfer, and scooter logic by route job.\nAt-a-glance table maps traveler situations to the best mode and live checks.\nPhoto-led proof uses licensed Hanoi station, Trang An, Tam Coc, and Noi Bai images with visible credits to explain transfer realities.\nSource-diversity and rendered source snapshot distinguish official broad facts from VietnamGuide door-to-door judgment.\nMode matrix, door-to-door equation, departure/arrival map, train guide, van audit, private-car logic, day-tour/onward-transfer module, base/luggage logic, sample routing, booking audit, failure modes, live checks, and FAQ provide practical decision value beyond rewritten search results.\nThe page avoids stale timetable copying and explicitly requires live checks for trains, vans, pickups, weather, and operator terms.\nRelated routes connect the guide to Ninh Binh destination, day-trip versus overnight, Hanoi planning, transport, cost, safety, insurance, SIM, money, and itinerary-length decisions.");

$related_routes = "Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide the destination role, base, boat route, season, skip logic, and transport fit before choosing the Hanoi transfer.\nNinh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether the transfer is a same-day return, one-night move, two-night slow base, or clean skip.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Use when Ninh Binh might remain a Hanoi day trip rather than a hotel-change move.\nHanoi Travel Guide | /destinations/hanoi-travel-guide/ | Place the transfer inside Hanoi's northern route role and first-city pacing.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Protect a tight Hanoi stay before moving to Ninh Binh or the bay.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Compare rail, car, bus, airport transfer, and onward movement across the full route.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before fixing northern transfer details.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm the north deserves enough nights before adding Ninh Binh movement.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the real cost difference between train, van, private car, day tour, and lost buffer time.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Prepare cash, cards, deposits, and backup payment before pickup or station transfer.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, hotel messaging, operator contact, and pickup matching available during the transfer.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair private cars, vans, station taxis, luggage, and payments with practical risk habits.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check road, scooter, weather, missed-connection, and interruption coverage before travel.\nHa Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Use when Ninh Binh is placed near a bay cruise or northern scenery handoff.\nHa Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Confirm the bay choice before relying on a Ninh Binh-to-bay transfer.\n7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Decide whether a one-week route can afford the Ninh Binh transfer at all.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Ninh Binh improves a tight first route or makes it brittle.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when a protected Ninh Binh night improves the north.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use a longer route to slow the transfer instead of collecting extra stops.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.";
vg_hanoi_ninh_binh_transport_ops_assert_related_route_meta_links_are_published('Hanoi to Ninh Binh Transport related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and rail image: Hanoi Railway Station by Alancrh, CC BY-SA 3.0. Body images: Trang An Landscape Complex, Ninh Binh Province by Jakub Halun, CC BY 4.0; Tam Coc by Andre Hospers, CC BY 4.0; Noi Bai International Airport T2 waiting area by Sky 269, CC BY-SA 4.0.');
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
        vg_hanoi_ninh_binh_transport_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_hanoi_ninh_binh_transport_ops_fail('Hanoi to Ninh Binh Transport was updated but is not published.');
}

vg_hanoi_ninh_binh_transport_ops_refresh_plan_hub();
vg_hanoi_ninh_binh_transport_ops_refresh_destinations_hub();
vg_hanoi_ninh_binh_transport_ops_refresh_homepage_route_spine();
vg_hanoi_ninh_binh_transport_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_hanoi_ninh_binh_transport_ops_log("Published Hanoi to Ninh Binh Transport: {$page_id} {$updated_permalink}");

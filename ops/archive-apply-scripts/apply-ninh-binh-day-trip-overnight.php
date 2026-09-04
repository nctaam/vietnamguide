<?php
/**
 * Publish the Ninh Binh Day Trip vs Overnight comparison guide.
 *
 * Self-reference marker: ops/apply-ninh-binh-day-trip-overnight.php
 *
 * Run from the WordPress root with:
 * VG_FORCE_NINH_BINH_DAY_TRIP_OVERNIGHT_REPUBLISH=1 wp eval-file ops/apply-ninh-binh-day-trip-overnight.php --allow-root
 *
 * Repair only hub/related-route/homepage side effects with:
 * VG_REPAIR_NINH_BINH_DAY_TRIP_OVERNIGHT_LINKS=1 wp eval-file ops/apply-ninh-binh-day-trip-overnight.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_ninh_binh_day_trip_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_ninh_binh_day_trip_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_ninh_binh_day_trip_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_NINH_BINH_DAY_TRIP_OVERNIGHT_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_ninh_binh_day_trip_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_NINH_BINH_DAY_TRIP_OVERNIGHT_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_ninh_binh_day_trip_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_ninh_binh_day_trip_ops_fail('Could not resolve a valid WordPress author for Ninh Binh Day Trip vs Overnight.');
}

function vg_ninh_binh_day_trip_ops_internal_path_from_href(string $href): ?string
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

function vg_ninh_binh_day_trip_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_ninh_binh_day_trip_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_ninh_binh_day_trip_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ninh_binh_day_trip_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_ninh_binh_day_trip_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_ninh_binh_day_trip_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_ninh_binh_day_trip_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_ninh_binh_day_trip_ops_log("Validated {$label} internal page links are published.");
}

function vg_ninh_binh_day_trip_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_ninh_binh_day_trip_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_ninh_binh_day_trip_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_ninh_binh_day_trip_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_ninh_binh_day_trip_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_ninh_binh_day_trip_ops_log("Validated {$label} related-route links are published.");
}

function vg_ninh_binh_day_trip_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_ninh_binh_day_trip_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
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
            vg_ninh_binh_day_trip_ops_fail("Could not confidently refresh {$label}.");
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
        vg_ninh_binh_day_trip_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ninh_binh_day_trip_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_ninh_binh_day_trip_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_ninh_binh_day_trip_ops_homepage(): ?WP_Post
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

function vg_ninh_binh_day_trip_ops_refresh_compare_hub(): void
{
    $hub = get_page_by_path('compare', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_ninh_binh_day_trip_ops_log('Skipped Compare hub refresh: compare page was not found or is not published.');
        return;
    }

    if (! vg_ninh_binh_day_trip_ops_published_page_exists('compare/ninh-binh-day-trip-vs-overnight')) {
        vg_ninh_binh_day_trip_ops_log('Skipped Compare hub refresh: Ninh Binh Day Trip vs Overnight is not published.');
        return;
    }

    $marker = '<!-- vg-ninh-binh-day-trip-overnight-compare-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Decide if Ninh Binh needs a night</h3><p><a href="/compare/ninh-binh-day-trip-vs-overnight/">Ninh Binh Day Trip vs Overnight</a> compares the Hanoi day trip, one-night countryside stop, two-night slow base, and skip decision by transfer pressure, morning control, Trang An versus Tam Coc, cost, weather, and next-route fragility.</p></div>
<!-- /wp:group -->
HTML;

    vg_ninh_binh_day_trip_ops_assert_internal_page_links_are_published('Compare hub Ninh Binh day trip vs overnight note', $block);
    vg_ninh_binh_day_trip_ops_upsert_marked_group($hub, 'Compare hub Ninh Binh day trip vs overnight note', $marker, $block);
}

function vg_ninh_binh_day_trip_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_ninh_binh_day_trip_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_ninh_binh_day_trip_ops_published_page_exists('compare/ninh-binh-day-trip-vs-overnight')) {
        vg_ninh_binh_day_trip_ops_log('Skipped Destinations hub refresh: Ninh Binh Day Trip vs Overnight is not published.');
        return;
    }

    $marker = '<!-- vg-ninh-binh-day-trip-overnight-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Turn the Ninh Binh stop into a pacing decision</h3><p>Use <a href="/compare/ninh-binh-day-trip-vs-overnight/">Ninh Binh Day Trip vs Overnight</a> after the <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a> when the booking question is no longer what to see, but whether the north needs one long day, one protected night, two slower nights, or a clean skip.</p></div>
<!-- /wp:group -->
HTML;

    vg_ninh_binh_day_trip_ops_assert_internal_page_links_are_published('Destinations hub Ninh Binh day trip vs overnight note', $block);
    vg_ninh_binh_day_trip_ops_upsert_marked_group($hub, 'Destinations hub Ninh Binh day trip vs overnight note', $marker, $block);
}

function vg_ninh_binh_day_trip_ops_refresh_homepage_route_spine(): void
{
    $home = vg_ninh_binh_day_trip_ops_homepage();

    if (! $home instanceof WP_Post) {
        vg_ninh_binh_day_trip_ops_log('Skipped homepage route-spine refresh: homepage was not found or is not published.');
        return;
    }

    if (! vg_ninh_binh_day_trip_ops_published_page_exists('compare/ninh-binh-day-trip-vs-overnight')) {
        vg_ninh_binh_day_trip_ops_log('Skipped homepage route-spine refresh: Ninh Binh Day Trip vs Overnight is not published.');
        return;
    }

    $marker = '<!-- vg-ninh-binh-day-trip-overnight-homepage-route-spine:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-home-section vg-home-decision-spine vg-home-ninh-binh-day-trip-overnight-spine"} -->
<div class="wp-block-group vg-home-section vg-home-decision-spine vg-home-ninh-binh-day-trip-overnight-spine"><p class="vg-kicker">Ninh Binh pacing decision</p><h2>Ninh Binh Day Trip vs Overnight</h2><p>Use this comparison when the northern route already includes Hanoi, Ninh Binh, and maybe the bay, but the real booking question is whether morning control and softer countryside time are worth one extra night.</p><p class="vg-section-link"><a href="/compare/ninh-binh-day-trip-vs-overnight/">Compare day trip and overnight</a></p></div>
<!-- /wp:group -->
HTML;

    vg_ninh_binh_day_trip_ops_assert_internal_page_links_are_published('Homepage Ninh Binh day trip vs overnight route-spine note', $block);
    vg_ninh_binh_day_trip_ops_upsert_marked_group($home, 'Homepage Ninh Binh day trip vs overnight route-spine note', $marker, $block);
}

function vg_ninh_binh_day_trip_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ninh_binh_day_trip_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_ninh_binh_day_trip_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_ninh_binh_day_trip_ops_log("Skipped related-route refresh for {$label}: Ninh Binh Day Trip vs Overnight line is already current.");
        return;
    }

    update_post_meta($page->ID, 'vg_eeat_related_routes', implode("\n", $next_lines));

    vg_ninh_binh_day_trip_ops_log("Upserted Ninh Binh Day Trip vs Overnight related route to {$label}: {$page->ID}");
}

function vg_ninh_binh_day_trip_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should be a Hanoi day trip, one-night countryside stop, two-night slow base, or skip by transfer pressure, morning control, weather, cost, and next-route fragility.';

    foreach (
        [
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
            'itineraries/hanoi-in-2-days' => 'Hanoi in 2 Days',
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
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
        vg_ninh_binh_day_trip_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

function vg_ninh_binh_day_trip_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_ninh_binh_day_trip_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

$parent = get_page_by_path('compare', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ninh_binh_day_trip_ops_fail('Could not find published /compare/ parent page.');
}

$page = get_page_by_path('compare/ninh-binh-day-trip-vs-overnight', OBJECT, 'page');

if (vg_ninh_binh_day_trip_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ninh_binh_day_trip_ops_fail('Repair mode requires Ninh Binh Day Trip vs Overnight to already be published.');
    }

    vg_ninh_binh_day_trip_ops_refresh_compare_hub();
    vg_ninh_binh_day_trip_ops_refresh_destinations_hub();
    vg_ninh_binh_day_trip_ops_refresh_homepage_route_spine();
    vg_ninh_binh_day_trip_ops_refresh_inbound_related_routes();
    vg_ninh_binh_day_trip_ops_log("Repaired Ninh Binh Day Trip vs Overnight side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_ninh_binh_day_trip_ops_force_republish_enabled()) {
    vg_ninh_binh_day_trip_ops_fail('Ninh Binh Day Trip vs Overnight is not a draft. Set VG_FORCE_NINH_BINH_DAY_TRIP_OVERNIGHT_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_ninh_binh_day_trip_ops_log("Preflight Ninh Binh Day Trip vs Overnight: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_ninh_binh_day_trip_ops_log('Preflight Ninh Binh Day Trip vs Overnight: no existing page found; creating a child page under /compare/.');
}

$review_date = 'July 24, 2026';
$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg');
$tam_coc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Tam_Coc_Ninh_Binh_%2829079%29.jpg/1920px-Tam_Coc_Ninh_Binh_%2829079%29.jpg');
$tam_coc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Tam_Coc_Ninh_Binh_(29079).jpg');
$mua_cave_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f4/Mua_Cave%2C_Ninh_Binh%2C_Vietnam%2C_20240202_0926_4964.jpg/1920px-Mua_Cave%2C_Ninh_Binh%2C_Vietnam%2C_20240202_0926_4964.jpg');
$mua_cave_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Mua_Cave,_Ninh_Binh,_Vietnam,_20240202_0926_4964.jpg');
$van_long_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/4/41/Van_Long_Nature_Reserve_Riet_Grotten_Kalksteen_Ninh_Binh_%2894589%29.jpg/1920px-Van_Long_Nature_Reserve_Riet_Grotten_Kalksteen_Ninh_Binh_%2894589%29.jpg');
$van_long_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Van_Long_Nature_Reserve_Riet_Grotten_Kalksteen_Ninh_Binh_(94589).jpg');
$cuc_phuong_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/98/Forest_in_Cuc_Phuong_National_Park_%2815706323528%29.jpg/1920px-Forest_in_Cuc_Phuong_National_Park_%2815706323528%29.jpg');
$cuc_phuong_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Forest_in_Cuc_Phuong_National_Park_(15706323528).jpg');

$content = <<<HTML
<!-- vg-ninh-binh-day-trip-overnight-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":56,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover vg-ninh-binh-day-trip-overnight-hero"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover vg-ninh-binh-day-trip-overnight-hero" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Boats moving through limestone scenery at Trang An in Ninh Binh for a day trip versus overnight decision" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Ninh Binh comparison guide - Updated {$review_date}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Ninh Binh Day Trip vs Overnight</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">This is the booking decision behind almost every first northern Vietnam route: should Ninh Binh be one long day from Hanoi, one protected countryside night, two slow nights, or a clean skip? The scenery is famous, but the better question is whether the transfer, heat, boat timing, and next morning let the stop improve the trip.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: the Ninh Binh mistake is not visiting quickly. The mistake is paying for a famous landscape while designing a day that gives you no good hour to actually feel it.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-ninh-binh-day-trip-overnight-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-ninh-binh-day-trip-overnight-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-ninh-binh-day-trip-overnight-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most first-time international travelers, Ninh Binh is better as one overnight than as a Hanoi day trip.</strong> A day trip is valid when Ninh Binh is the only countryside slot and the next morning is not fragile. The overnight premium is not another attraction; it is morning control, heat avoidance, softer evenings, and fewer transfer compromises.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-ninh-binh-day-trip-overnight-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-ninh-binh-day-trip-overnight-shortlist">
<li><strong>Choose a day trip</strong> when the route is short, Hanoi stays protected, and you only need one boat landscape plus one light supporting stop.</li>
<li><strong>Choose one night</strong> when the north has 10 to 14 days, you want a calmer first morning, or the next move would otherwise feel brittle.</li>
<li><strong>Choose two nights</strong> for photography, families, slow travel, Cuc Phuong, Van Long, or when Ninh Binh is the quiet center of the northern chapter.</li>
<li><strong>Skip Ninh Binh</strong> when Hanoi and the bay already consume the north or when the route has no protected morning left.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-ninh-binh-day-trip-overnight-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-ninh-binh-day-trip-overnight-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-ninh-binh-day-trip-overnight-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fastest useful answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-day-trip-overnight-glance">
<tbody>
<tr><td data-label="Question"><strong>Best default</strong></td><td data-label="Answer">One night: arrive from Hanoi, sleep near Tam Coc or Trang An, protect a morning boat or countryside block, then move onward without treating Ninh Binh like a transfer errand.</td></tr>
<tr><td data-label="Question"><strong>Best day-trip use</strong></td><td data-label="Answer">A focused Trang An or Tam Coc route plus one supporting stop, with no same-night bay, flight, or long train pressure afterward.</td></tr>
<tr><td data-label="Question"><strong>Best two-night use</strong></td><td data-label="Answer">When the second night changes the rhythm: dawn/late light, cycling, Van Long, Cuc Phuong, family pacing, or weather fallback.</td></tr>
<tr><td data-label="Question"><strong>First thing to cut</strong></td><td data-label="Answer">The second boat route. Doing both Trang An and Tam Coc is useful only when comparison is the point, not when completion anxiety is driving the day.</td></tr>
<tr><td data-label="Question"><strong>Danger sign</strong></td><td data-label="Answer">Any plan that sells Ninh Binh, a viewpoint, multiple temples, a bay decision, and a late transfer as if distance has no cost.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-ninh-binh-day-trip-overnight-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what the extra night actually buys</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The photographs are not decoration. They show the difference between seeing Ninh Binh and designing enough time for the landscape to work: water routes, village rhythm, viewpoints, wetlands, and forest are different jobs, not one expandable checklist.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-ninh-binh-day-trip-overnight-photo-grid" aria-label="Ninh Binh day trip versus overnight photography">
<figure class="vg-guide-photo"><img src="{$tam_coc_image}" alt="Tam Coc river and limestone scenery in Ninh Binh" loading="lazy" decoding="async"><figcaption>Tam Coc is strongest when the base itself adds countryside rhythm, not only a boat slot. Image: <a href="{$tam_coc_credit_url}" target="_blank" rel="license noopener">Andre Hospers / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mua_cave_image}" alt="Mua Cave stairs and limestone viewpoint in Ninh Binh" loading="lazy" decoding="async"><figcaption>Hang Mua rewards timing and weather. It is optional when a day trip is already hot, crowded, or transfer-heavy. Image: <a href="{$mua_cave_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$van_long_image}" alt="Van Long Nature Reserve wetlands and limestone scenery in Ninh Binh" loading="lazy" decoding="async"><figcaption>Van Long is a reason to slow down, not a tidy add-on to a Hanoi day trip. Image: <a href="{$van_long_credit_url}" target="_blank" rel="license noopener">Andre Hospers / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cuc_phuong_image}" alt="Forest inside Cuc Phuong National Park near Ninh Binh" loading="lazy" decoding="async"><figcaption>Cuc Phuong needs a real nature window. It is usually a two-night argument, not a final box after Trang An. Image: <a href="{$cuc_phuong_credit_url}" target="_blank" rel="license noopener">hds / CC BY 2.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-ninh-binh-day-trip-overnight-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What sources can prove, and what judgment must decide</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Official pages can confirm the destination frame, heritage status, attraction context, ticket notices, weather patterns, and transport categories. They cannot decide your jet lag, hotel lane, group patience, heat tolerance, or whether tomorrow morning is already spoken for.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-day-trip-overnight-source-diversity">
<thead><tr><th>Source type</th><th>Useful for</th><th>What VietnamGuide still decides</th></tr></thead>
<tbody>
<tr><td data-label="Source type">Vietnam.travel destination pages</td><td data-label="Useful for">Ninh Binh positioning, northern Vietnam context, Hanoi access modes, cycling, and key landscape anchors.</td><td data-label="What VietnamGuide still decides">Whether those anchors deserve a day trip, overnight, or skip in a specific first-trip route.</td></tr>
<tr><td data-label="Source type">UNESCO Trang An listing</td><td data-label="Useful for">Confirming the mixed cultural and natural heritage importance behind Trang An.</td><td data-label="What VietnamGuide still decides">Whether heritage value should outrank fatigue, heat, or the need for a quieter morning.</td></tr>
<tr><td data-label="Source type">Ninh Binh Tourism Department</td><td data-label="Useful for">Local tourism notices, destination information, and ticket-price snapshots such as dulichninhbinh.com.vn/en/printer/1801.</td><td data-label="What VietnamGuide still decides">Which ticketed stops deserve the limited hours rather than becoming a paid checklist.</td></tr>
<tr><td data-label="Source type">Weather and transport sources</td><td data-label="Useful for">Season pressure, same-week rain or heat risk, trains, vans, private cars, and route timing.</td><td data-label="What VietnamGuide still decides">How much schedule margin a traveler should protect before and after Ninh Binh.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-day-trip-overnight-source-trail-snapshot:v1 -->
<!-- wp:group {"className":"vg-source-snapshot vg-ninh-binh-day-trip-overnight-source-trail-snapshot","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-source-snapshot vg-ninh-binh-day-trip-overnight-source-trail-snapshot">
<!-- wp:heading -->
<h2 class="wp-block-heading">Source trail snapshot</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Checked for this decision page: Vietnam.travel Ninh Binh destination page - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh; Vietnam.travel Northern Vietnam - https://vietnam.travel/places-to-go/northern-vietnam; UNESCO Trang An Landscape Complex - https://whc.unesco.org/en/list/1438/; Ninh Binh Tourism Department - https://dulichninhbinh.com.vn/en/ and https://dulichninhbinh.com.vn/en/printer/1801; Vietnam.travel weather and climate - https://vietnam.travel/things-to-do/weather-and-climate-vietnam; Vietnam.travel transport - https://vietnam.travel/plan-your-trip/transport-within-vietnam; Vietnam Railways - https://dsvn.vn/.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- vg-ninh-binh-day-trip-overnight-decision-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Day trip, one night, two nights, or skip</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-day-trip-overnight-decision-matrix">
<thead><tr><th>Choice</th><th>Best when</th><th>What you get</th><th>What you sacrifice</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Choice">Hanoi day trip</td><td data-label="Best when">The trip is short, Hanoi still has protected time, and Ninh Binh is the only countryside slot.</td><td data-label="What you get">One strong landscape impression without changing hotels.</td><td data-label="What you sacrifice">Early/late countryside rhythm, softer dinner, flexible weather pivot, and a less rushed next morning.</td><td data-label="VietnamGuide verdict">Acceptable, not ideal.</td></tr>
<tr><td data-label="Choice">One night</td><td data-label="Best when">You have 10 to 14 days, want a calmer northern chapter, or are connecting onward with luggage.</td><td data-label="What you get">Morning control, heat avoidance, cleaner Trang An or Tam Coc timing, and a base that feels like countryside.</td><td data-label="What you sacrifice">One hotel change and a night that might otherwise go to Hanoi or the bay.</td><td data-label="VietnamGuide verdict">Best default.</td></tr>
<tr><td data-label="Choice">Two nights</td><td data-label="Best when">Photography, family pace, Cuc Phuong, Van Long, cycling, or a slower premium trip are the point.</td><td data-label="What you get">A real countryside chapter with weather margin and fewer forced choices.</td><td data-label="What you sacrifice">Central Vietnam, bay, mountain, or beach time if the trip is short.</td><td data-label="VietnamGuide verdict">Excellent only with a reason.</td></tr>
<tr><td data-label="Choice">Skip</td><td data-label="Best when">The north already has Hanoi plus a serious bay or mountain chapter and the calendar is tight.</td><td data-label="What you get">A cleaner route, fewer transfers, and better energy for the stops you keep.</td><td data-label="What you sacrifice">Inland karst scenery and countryside contrast.</td><td data-label="VietnamGuide verdict">Better than a weak add-on.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-day-trip-overnight-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route fit by trip length</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-day-trip-overnight-route-fit">
<thead><tr><th>Trip length</th><th>Best Ninh Binh use</th><th>Risk</th><th>Planning chain</th></tr></thead>
<tbody>
<tr><td data-label="Trip length">7 days</td><td data-label="Best Ninh Binh use">Day trip or one night only if the whole route stays north-focused.</td><td data-label="Risk">Trying to add Hanoi, Ninh Binh, the bay, Hoi An, and HCMC.</td><td data-label="Planning chain"><a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a></td></tr>
<tr><td data-label="Trip length">10 days</td><td data-label="Best Ninh Binh use">One night is often the cleanest answer if the bay is also simplified.</td><td data-label="Risk">Ninh Binh becomes the third rushed northern product before Central Vietnam.</td><td data-label="Planning chain"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Trip length">14 days</td><td data-label="Best Ninh Binh use">One protected night works well; two nights work if the north is intentionally slow.</td><td data-label="Risk">Keeping every famous stop and pretending two weeks has no transfer cost.</td><td data-label="Planning chain"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Trip length">21 days</td><td data-label="Best Ninh Binh use">One or two nights can become a proper northern countryside chapter.</td><td data-label="Risk">Using extra days for extra logistics instead of better pacing.</td><td data-label="Planning chain"><a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-day-trip-overnight-sample-schedules:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Sample schedules that do not overpromise</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-day-trip-overnight-sample-schedules">
<thead><tr><th>Plan</th><th>Use this shape</th><th>Keep it strong by</th><th>Do not add</th></tr></thead>
<tbody>
<tr><td data-label="Plan">Focused day trip</td><td data-label="Use this shape">Hanoi pickup, one boat route, one compact supporting stop, Hanoi return.</td><td data-label="Keep it strong by">Choosing Trang An or Tam Coc before adding Hang Mua or temples.</td><td data-label="Do not add">Same-day bay logic, two boat routes, or a late onward transfer.</td></tr>
<tr><td data-label="Plan">One-night default</td><td data-label="Use this shape">Hanoi to Ninh Binh, countryside base, late-day soft arrival, next-morning boat or viewpoint, onward move.</td><td data-label="Keep it strong by">Protecting a morning and one slow meal.</td><td data-label="Do not add">Cuc Phuong unless you are staying longer.</td></tr>
<tr><td data-label="Plan">Two-night slow base</td><td data-label="Use this shape">Arrival day, full countryside day, weather/family/nature fallback, clean exit.</td><td data-label="Keep it strong by">Using the second night for a different rhythm.</td><td data-label="Do not add">Both Trang An and Tam Coc just to compare if everyone is tired.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-day-trip-overnight-transfer-pressure:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The transfer-pressure test</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ninh Binh is close enough to Hanoi to sell as easy and far enough to damage a fragile route. The question is not whether a transfer exists. The question is whether the transfer lands on the right side of the day.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-day-trip-overnight-transfer-pressure">
<thead><tr><th>Pressure point</th><th>Day trip answer</th><th>Overnight answer</th><th>Verdict</th></tr></thead>
<tbody>
<tr><td data-label="Pressure point">Hotel pickup</td><td data-label="Day trip answer">Convenient if the pickup zone is central and the return is not late.</td><td data-label="Overnight answer">Less fragile because luggage and hotel timing can be planned directly.</td><td data-label="Verdict">Overnight wins when the hotel lane or family pace is uncertain.</td></tr>
<tr><td data-label="Pressure point">Next morning</td><td data-label="Day trip answer">Weak if tomorrow already has airport, cruise, train, or long drive pressure.</td><td data-label="Overnight answer">Strong if it creates a clean morning before the next move.</td><td data-label="Verdict">Do not borrow energy from tomorrow.</td></tr>
<tr><td data-label="Pressure point">Weather</td><td data-label="Day trip answer">A single bad window can flatten the day.</td><td data-label="Overnight answer">Adds more choice around rain, heat, or haze.</td><td data-label="Verdict">Extra night buys optionality.</td></tr>
<tr><td data-label="Pressure point">Route repetition</td><td data-label="Day trip answer">Works if the bay is skipped or simplified.</td><td data-label="Overnight answer">Works if the bay still has a different job.</td><td data-label="Verdict">Do not combine Ninh Binh and a same-day bay product to rescue an overfull route.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-day-trip-overnight-trang-an-tam-coc:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Trang An or Tam Coc for each choice</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Choose Trang An when the page needs UNESCO-grade landscape certainty; choose Tam Coc when countryside texture, cycling, and softer base rhythm matter more. Both can be beautiful. Doing both is not automatically better.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-day-trip-overnight-trang-an-tam-coc">
<thead><tr><th>Scenario</th><th>Better anchor</th><th>Why</th><th>Watch out</th></tr></thead>
<tbody>
<tr><td data-label="Scenario">One focused day trip</td><td data-label="Better anchor">Trang An</td><td data-label="Why">The strongest single-anchor answer when you need landscape confidence and heritage logic.</td><td data-label="Watch out">Do not weaken it by adding too many land stops.</td></tr>
<tr><td data-label="Scenario">One-night countryside stop</td><td data-label="Better anchor">Tam Coc or Trang An area</td><td data-label="Why">The base, evening, and morning matter as much as the boat route.</td><td data-label="Watch out">A hotel too far from the rhythm can make the overnight feel logistical.</td></tr>
<tr><td data-label="Scenario">Two slow nights</td><td data-label="Better anchor">Split by mood, not completion</td><td data-label="Why">Use one water route, then add cycling, Van Long, Cuc Phuong, or a viewpoint if it changes the day.</td><td data-label="Watch out">Two boat routes can blur together if the second is only a checklist item.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-day-trip-overnight-base-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where to sleep if you choose overnight</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-day-trip-overnight-base-logic">
<thead><tr><th>Base</th><th>Best for</th><th>Trade-off</th><th>Verdict</th></tr></thead>
<tbody>
<tr><td data-label="Base">Tam Coc</td><td data-label="Best for">First-timers who want restaurants, cycling, countryside lanes, and easy soft evenings.</td><td data-label="Trade-off">More visitor infrastructure and less pure quiet.</td><td data-label="Verdict">Best default feel.</td></tr>
<tr><td data-label="Base">Trang An area</td><td data-label="Best for">Landscape-first stays, quieter accommodation, and proximity to the main heritage anchor.</td><td data-label="Trade-off">Less casual evening choice.</td><td data-label="Verdict">Best for atmosphere and morning control.</td></tr>
<tr><td data-label="Base">Ninh Binh city</td><td data-label="Best for">Train access, budget practicality, late arrival, or early onward rail.</td><td data-label="Trade-off">Less countryside outside the door.</td><td data-label="Verdict">Best logistics base, not best romance.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-day-trip-overnight-weather-crowd-pivots:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Weather, heat, and crowd pivots</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-day-trip-overnight-weather-crowd-pivots">
<thead><tr><th>Condition</th><th>Day trip pivot</th><th>Overnight pivot</th><th>Decision</th></tr></thead>
<tbody>
<tr><td data-label="Condition">Hot exposed day</td><td data-label="Day trip pivot">Boat route first, viewpoint optional, lighter temple stop.</td><td data-label="Overnight pivot">Move exposed pieces to early or late hours.</td><td data-label="Decision">Overnight improves comfort most.</td></tr>
<tr><td data-label="Condition">Rain or low visibility</td><td data-label="Day trip pivot">Reduce Hang Mua expectations and keep one main water route.</td><td data-label="Overnight pivot">Hold a second window and use food, base, or culture when weather turns.</td><td data-label="Decision">Overnight buys flexibility.</td></tr>
<tr><td data-label="Condition">Weekend or holiday pressure</td><td data-label="Day trip pivot">Avoid overpacked group itineraries and verify pickup/return windows.</td><td data-label="Overnight pivot">Use early morning before day-trip pressure arrives.</td><td data-label="Decision">Overnight can turn crowds into a timing problem, not a trip problem.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-day-trip-overnight-cost-comfort:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where the extra money actually goes</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-day-trip-overnight-cost-comfort">
<thead><tr><th>Cost item</th><th>Day trip</th><th>Overnight</th><th>Upgrade logic</th></tr></thead>
<tbody>
<tr><td data-label="Cost item">Transport</td><td data-label="Day trip">One packaged round trip or private car day.</td><td data-label="Overnight">Separate transfer legs, train/van/private car, or onward route.</td><td data-label="Upgrade logic">Pay more when control protects tomorrow.</td></tr>
<tr><td data-label="Cost item">Hotel</td><td data-label="Day trip">No extra hotel, but Hanoi night may still be paid.</td><td data-label="Overnight">One countryside hotel night.</td><td data-label="Upgrade logic">The room buys morning and evening, not just a bed.</td></tr>
<tr><td data-label="Cost item">Guide or private driver</td><td data-label="Day trip">Useful when timing and inclusions are clear.</td><td data-label="Overnight">Useful for families, luggage, heat, and flexible sequencing.</td><td data-label="Upgrade logic">Buy judgment, not a longer stop list.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-day-trip-overnight-mistakes-skip:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mistakes that make Ninh Binh feel worse than it is</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-day-trip-overnight-mistakes-skip">
<thead><tr><th>Mistake</th><th>Why it hurts</th><th>Correction</th></tr></thead>
<tbody>
<tr><td data-label="Mistake">Booking the cheapest packed day tour</td><td data-label="Why it hurts">The route may maximize stops while minimizing the good hours.</td><td data-label="Correction">Choose one landscape anchor, one support stop, and a realistic return.</td></tr>
<tr><td data-label="Mistake">Treating overnight as permission to add everything</td><td data-label="Why it hurts">The extra night disappears into more transfers and tickets.</td><td data-label="Correction">Use the night for morning control, not itinerary inflation.</td></tr>
<tr><td data-label="Mistake">Adding Ninh Binh before the bay decision is clear</td><td data-label="Why it hurts">The north can become limestone repetition plus transfer fatigue.</td><td data-label="Correction">Compare with <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a> before paying deposits.</td></tr>
<tr><td data-label="Mistake">Forcing Hang Mua in bad conditions</td><td data-label="Why it hurts">Wet steps, heat, haze, or crowding can turn a high-impact viewpoint into a poor trade.</td><td data-label="Correction">Make Hang Mua optional and protect the boat route first.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-day-trip-overnight-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before booking</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-ninh-binh-day-trip-overnight-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-ninh-binh-day-trip-overnight-live-checks">
<li>Check Vietnam.travel and Ninh Binh Tourism Department for current destination framing, attraction notices, and ticket information before treating any stop as fixed.</li>
<li>Check Vietnam Railways, transfer operators, or your hotel for the actual Hanoi to Ninh Binh arrival and drop-off logic before choosing Tam Coc, Trang An, or Ninh Binh city.</li>
<li>Check same-week weather before locking Hang Mua, cycling, Van Long, or any exposed midday plan.</li>
<li>Read <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before using scooters, boats, rural roads, or tight onward transfers.</li>
</ul>
<!-- /wp:list -->

<!-- vg-ninh-binh-day-trip-overnight-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Ninh Binh day trip vs overnight FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-ninh-binh-day-trip-overnight-faq">
<details><summary>Is a Ninh Binh day trip from Hanoi worth it?</summary><p>Yes, when the route is short and the day is focused. It is weakest when the plan tries to include too many stops, returns late, and then asks you to move again early the next morning.</p></details>
<details><summary>Is one night in Ninh Binh enough?</summary><p>For most first-time visitors, yes. One night buys the main value: a calmer morning, better boat timing, softer countryside evening, and less pressure on Hanoi.</p></details>
<details><summary>Are two nights in Ninh Binh too much?</summary><p>Two nights are valuable only when they buy a different rhythm, not when they become a bigger checklist. Use them for photography, family pacing, Van Long, Cuc Phuong, cycling, or weather flexibility.</p></details>
<details><summary>Should I choose Trang An or Tam Coc?</summary><p>Choose Trang An for the strongest single landscape and UNESCO-linked logic. Choose Tam Coc when the base atmosphere, rice-field texture, and cycling matter more than one flagship route.</p></details>
<details><summary>Can I do Ninh Binh and Ha Long Bay from Hanoi in one day?</summary><p>Do not combine Ninh Binh and a same-day bay product to rescue an overfull route. If both matter, slow down or cut another stop.</p></details>
<details><summary>Where should I stay overnight in Ninh Binh?</summary><p>Tam Coc is the best default feel, Trang An area is strongest for atmosphere and morning control, and Ninh Binh city is best when rail or budget practicality matters more than countryside outside the door.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, then compare Hanoi excursion pressure in <a href="/destinations/best-day-trips-from-hanoi/">Best Day Trips from Hanoi</a>. Use <a href="/itineraries/hanoi-in-2-days/">Hanoi in 2 Days</a>, <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, and <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a> before deciding whether the north has space for both inland and bay scenery.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-ninh-binh-day-trip-overnight-hero:v1',
    'concierge verdict' => 'vg-ninh-binh-day-trip-overnight-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-ninh-binh-day-trip-overnight-at-a-glance:v1',
    'photo grid' => 'vg-ninh-binh-day-trip-overnight-photo-grid:v1',
    'source diversity' => 'vg-ninh-binh-day-trip-overnight-source-diversity:v1',
    'source trail snapshot' => 'vg-ninh-binh-day-trip-overnight-source-trail-snapshot:v1',
    'decision matrix' => 'vg-ninh-binh-day-trip-overnight-decision-matrix:v1',
    'route fit' => 'vg-ninh-binh-day-trip-overnight-route-fit:v1',
    'sample schedules' => 'vg-ninh-binh-day-trip-overnight-sample-schedules:v1',
    'transfer pressure' => 'vg-ninh-binh-day-trip-overnight-transfer-pressure:v1',
    'Trang An Tam Coc decision' => 'vg-ninh-binh-day-trip-overnight-trang-an-tam-coc:v1',
    'hotel base logic' => 'vg-ninh-binh-day-trip-overnight-base-logic:v1',
    'weather crowd pivots' => 'vg-ninh-binh-day-trip-overnight-weather-crowd-pivots:v1',
    'cost comfort' => 'vg-ninh-binh-day-trip-overnight-cost-comfort:v1',
    'mistakes skip' => 'vg-ninh-binh-day-trip-overnight-mistakes-skip:v1',
    'live checks' => 'vg-ninh-binh-day-trip-overnight-live-checks:v1',
    'FAQ' => 'vg-ninh-binh-day-trip-overnight-faq:v1',
    'related routes shortcode' => '[vg_related_routes]',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ninh_binh_day_trip_ops_assert_required_content_markers($content, $required_content_markers);
vg_ninh_binh_day_trip_ops_assert_internal_page_links_are_published('Ninh Binh Day Trip vs Overnight', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Ninh Binh Day Trip vs Overnight',
    'post_name'      => 'ninh-binh-day-trip-vs-overnight',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ninh_binh_day_trip_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led comparison for international travelers deciding whether Ninh Binh should be a Hanoi day trip, one-night countryside stop, two-night slow base, or skip.',
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
    vg_ninh_binh_day_trip_ops_fail('Could not publish Ninh Binh Day Trip vs Overnight: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ninh_binh_day_trip_ops_fail('Could not publish Ninh Binh Day Trip vs Overnight: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Ninh Binh Day Trip vs Overnight: Which Is Better?');
update_post_meta($page_id, 'rank_math_description', 'Should Ninh Binh be a Hanoi day trip or overnight? Evidence-led verdict by route length, transfer pressure, Trang An vs Tam Coc, weather, cost, hotels, and skip logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Ninh Binh day trip vs overnight');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Ninh Binh should be a Hanoi day trip, one-night countryside stop, two-night slow base, or skip before booking transfers, hotels, boat routes, and bay logistics.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published Ninh Binh Day Trip vs Overnight as a decision-led comparison with a photo hero, concierge verdict, proof panel, at-a-glance table, licensed photo proof, source-diversity module, rendered source trail snapshot, day-trip versus one-night versus two-night versus skip matrix, route-fit table, sample schedules, transfer-pressure test, Trang An versus Tam Coc decision, base logic, weather/crowd pivots, cost/comfort matrix, mistakes/skip logic, live checks, FAQ, Compare and Destinations hub notes, homepage support, and inbound related routes.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Ninh Binh destination page - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh - checked {$review_date}\nVietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked {$review_date}\nUNESCO World Heritage Centre - Trang An Landscape Complex - https://whc.unesco.org/en/list/1438/ - checked {$review_date}\nNinh Binh Tourism Department - Tourism Promotion Information Center - https://dulichninhbinh.com.vn/en/ - checked {$review_date}\nNinh Binh Tourism Department - Ticket price list of tourist attractions in Ninh Binh 2025 - https://dulichninhbinh.com.vn/en/printer/1801 - checked {$review_date}\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}\nVietnam Railways - Official online railway portal - https://dsvn.vn/ - checked {$review_date}\nWikimedia Commons image record - Trang An Landscape Complex, Ninh Binh Province - https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg - license checked {$review_date}\nWikimedia Commons image record - Tam Coc Ninh Binh - https://commons.wikimedia.org/wiki/File:Tam_Coc_Ninh_Binh_(29079).jpg - license checked {$review_date}\nWikimedia Commons image record - Mua Cave, Ninh Binh - https://commons.wikimedia.org/wiki/File:Mua_Cave,_Ninh_Binh,_Vietnam,_20240202_0926_4964.jpg - license checked {$review_date}\nWikimedia Commons image record - Van Long Nature Reserve - https://commons.wikimedia.org/wiki/File:Van_Long_Nature_Reserve_Riet_Grotten_Kalksteen_Ninh_Binh_(94589).jpg - license checked {$review_date}\nWikimedia Commons image record - Cuc Phuong National Park forest - https://commons.wikimedia.org/wiki/File:Forest_in_Cuc_Phuong_National_Park_(15706323528).jpg - license checked {$review_date}");
update_post_meta($page_id, 'vg_eeat_field_note', 'Ninh Binh is usually best as one overnight because the value is not an extra attraction. The value is morning control, heat avoidance, softer countryside rhythm, and a route that does not borrow energy from tomorrow.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Ninh Binh Day Trip vs Overnight built around a high-intent booking decision rather than a generic attraction rewrite.\nConcierge verdict names the default one-night answer and the valid day-trip exception.\nAt-a-glance table defines best default, day-trip use, two-night use, first cut, and danger sign.\nPhoto-led proof explains what the overnight buys: base rhythm, optional viewpoint timing, wetland depth, and forest/nature windows.\nSource-diversity module separates official facts from VietnamGuide judgment and explicitly limits what sources can decide.\nRendered source trail snapshot keeps official and image-license evidence visible without overloading the body with linkouts.\nDecision matrix, route-fit table, sample schedules, transfer-pressure test, Trang An versus Tam Coc choice, base logic, weather/crowd pivots, cost/comfort, mistakes, live checks, and FAQ provide practical decision value beyond search-result summaries.\nRelated routes connect the page to Ninh Binh Travel Guide, Best Day Trips from Hanoi, Hanoi in 2 Days, Hanoi Travel Guide, 7/10/14/21-day itineraries, bay guides, transport, cost, insurance, and safety.");

$related_routes = "Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Use the full destination guide after the day-trip versus overnight choice is clear.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Compare whether Ninh Binh should remain a Hanoi day trip or become a protected overnight.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Keep Hanoi's short city chapter from being consumed by an outside day.\nHanoi Travel Guide | /destinations/hanoi-travel-guide/ | Decide how Hanoi anchors the north before moving to Ninh Binh or the bay.\nWhere to Stay in Hanoi | /destinations/where-to-stay-in-hanoi/ | Choose the Hanoi base that makes pickup, sleep, and launch logistics easier.\nBest Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Protect a real capital day before exporting time to Ninh Binh.\nHa Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Decide whether bay time competes with or complements Ninh Binh.\nHa Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Check bay route pressure before combining inland and cruise scenery.\nCat Ba Travel Guide | /destinations/cat-ba-travel-guide/ | Use when an island base may change the northern route after Ninh Binh.\nBai Tu Long Bay Guide | /destinations/bai-tu-long-bay-guide/ | Compare quieter bay logic only after Ninh Binh and core bay needs are clear.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before adding northern landscape stops.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Decide whether Ninh Binh belongs in the destination shortlist.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Understand Trang An as heritage value, not just a scenery label.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm the north deserves enough nights before adding Ninh Binh.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check heat, rain, and northern season pressure before locking boat or viewpoint timing.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Choose train, van, private car, and onward movement before booking the base.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the real difference between a day tour and a protected overnight.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check road, boat, cycling, scooter, weather, and rural-transfer risk before travel.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair deposits, transfers, scooters, boats, and rural movement with practical risk habits.\n7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Decide whether a one-week route can afford Ninh Binh at all.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether one Ninh Binh night improves or weakens a tight first route.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when Ninh Binh should become a protected northern chapter.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use a longer route to slow Ninh Binh without sacrificing the rest of Vietnam.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.";
vg_ninh_binh_day_trip_ops_assert_related_route_meta_links_are_published('Ninh Binh Day Trip vs Overnight related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero image: Trang An Landscape Complex, Ninh Binh Province by Jakub Halun, CC BY 4.0. Body images: Tam Coc by Andre Hospers, CC BY 4.0; Mua Cave by Jakub Halun, CC BY 4.0; Van Long Nature Reserve by Andre Hospers, CC BY 4.0; Cuc Phuong National Park forest by hds, CC BY 2.0.');
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
        vg_ninh_binh_day_trip_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_ninh_binh_day_trip_ops_fail('Ninh Binh Day Trip vs Overnight was updated but is not published.');
}

vg_ninh_binh_day_trip_ops_refresh_compare_hub();
vg_ninh_binh_day_trip_ops_refresh_destinations_hub();
vg_ninh_binh_day_trip_ops_refresh_homepage_route_spine();
vg_ninh_binh_day_trip_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_ninh_binh_day_trip_ops_log("Published Ninh Binh Day Trip vs Overnight: {$page_id} {$updated_permalink}");

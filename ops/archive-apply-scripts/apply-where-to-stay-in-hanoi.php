<?php
/**
 * Publish the Where to Stay in Hanoi guide.
 *
 * Self-reference marker: ops/apply-where-to-stay-in-hanoi.php
 *
 * Run from the WordPress root with:
 * VG_FORCE_HANOI_STAYS_GUIDE_REPUBLISH=1 wp eval-file ops/apply-where-to-stay-in-hanoi.php --allow-root
 *
 * Optional side-effect repair only:
 * VG_REPAIR_HANOI_STAYS_GUIDE_LINKS=1 wp eval-file ops/apply-where-to-stay-in-hanoi.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_hanoi_stays_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_hanoi_stays_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_hanoi_stays_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_HANOI_STAYS_GUIDE_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_hanoi_stays_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_HANOI_STAYS_GUIDE_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_hanoi_stays_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_hanoi_stays_ops_fail('Could not resolve a valid WordPress author for the Where to Stay in Hanoi guide.');
}

function vg_hanoi_stays_ops_internal_path_from_href(string $href): ?string
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

function vg_hanoi_stays_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_hanoi_stays_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_hanoi_stays_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_stays_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_hanoi_stays_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_hanoi_stays_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_hanoi_stays_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_hanoi_stays_ops_log("Validated {$label} internal page links are published.");
}

function vg_hanoi_stays_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_hanoi_stays_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_hanoi_stays_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_hanoi_stays_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_hanoi_stays_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_hanoi_stays_ops_log("Validated {$label} related-route links are published.");
}

function vg_hanoi_stays_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_hanoi_stays_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
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
            vg_hanoi_stays_ops_fail("Could not confidently refresh {$label}.");
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
        vg_hanoi_stays_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_hanoi_stays_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_hanoi_stays_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_hanoi_stays_ops_homepage(): ?WP_Post
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

function vg_hanoi_stays_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_hanoi_stays_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_hanoi_stays_ops_published_page_exists('destinations/where-to-stay-in-hanoi')) {
        vg_hanoi_stays_ops_log('Skipped Destinations hub refresh: Where to Stay in Hanoi is not published.');
        return;
    }

    $marker = '<!-- vg-hanoi-stays-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose the Hanoi base before choosing the hotel</h3><p>The <a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a> guide compares Hoan Kiem, the Old Quarter edge, French Quarter/south Hoan Kiem, Ba Dinh, Tay Ho, and Noi Bai airport-side nights by first-night ease, sleep, day-trip pickup, transfer risk, and traveler fit.</p></div>
<!-- /wp:group -->
HTML;

    vg_hanoi_stays_ops_assert_internal_page_links_are_published('Destinations hub Hanoi stays note', $block);
    vg_hanoi_stays_ops_upsert_marked_group($hub, 'Destinations hub Hanoi stays note', $marker, $block);
}

function vg_hanoi_stays_ops_refresh_homepage_route_spine(): void
{
    $home = vg_hanoi_stays_ops_homepage();

    if (! $home instanceof WP_Post) {
        vg_hanoi_stays_ops_log('Skipped homepage route-spine refresh: homepage was not found or is not published.');
        return;
    }

    if (! vg_hanoi_stays_ops_published_page_exists('destinations/where-to-stay-in-hanoi')) {
        vg_hanoi_stays_ops_log('Skipped homepage route-spine refresh: Where to Stay in Hanoi is not published.');
        return;
    }

    $marker = '<!-- vg-hanoi-stays-homepage-route-spine:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-home-section vg-home-decision-spine vg-home-hanoi-stays-spine"} -->
<div class="wp-block-group vg-home-section vg-home-decision-spine vg-home-hanoi-stays-spine"><p class="vg-kicker">Northern base decision</p><h2>Where to Stay in Hanoi</h2><p>Use the Hanoi stay-area guide before locking a room. It separates Hoan Kiem and Old Quarter first-time ease, French Quarter calmer premium central, Ba Dinh civic-history sleep, Tay Ho longer-stay rhythm, and Noi Bai airport-side nights for real flight risk.</p><p class="vg-section-link"><a href="/destinations/where-to-stay-in-hanoi/">Choose the Hanoi base</a></p></div>
<!-- /wp:group -->
HTML;

    vg_hanoi_stays_ops_assert_internal_page_links_are_published('Homepage Hanoi stays route-spine note', $block);
    vg_hanoi_stays_ops_upsert_marked_group($home, 'Homepage Hanoi stays route-spine note', $marker, $block);
}

function vg_hanoi_stays_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_stays_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_hanoi_stays_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_hanoi_stays_ops_log("Skipped related-route refresh for {$label}: Hanoi stays line is already current.");
        return;
    }

    update_post_meta($page->ID, 'vg_eeat_related_routes', implode("\n", $next_lines));

    vg_hanoi_stays_ops_log("Upserted Where to Stay in Hanoi related route to {$label}: {$page->ID}");
}

function vg_hanoi_stays_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Where to Stay in Hanoi | /destinations/where-to-stay-in-hanoi/ | Choose Hoan Kiem, the Old Quarter edge, French Quarter, Ba Dinh, Tay Ho, or Noi Bai by first-night ease, sleep, pickup, transfer, and flight-risk job.';

    foreach (
        [
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
        ] as $path => $label
    ) {
        vg_hanoi_stays_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

function vg_hanoi_stays_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_hanoi_stays_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_hanoi_stays_ops_fail('Could not find published /destinations/ parent page.');
}

$page = get_page_by_path('destinations/where-to-stay-in-hanoi', OBJECT, 'page');

if (vg_hanoi_stays_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_stays_ops_fail('Repair mode requires Where to Stay in Hanoi to already be published.');
    }

    vg_hanoi_stays_ops_refresh_destinations_hub();
    vg_hanoi_stays_ops_refresh_homepage_route_spine();
    vg_hanoi_stays_ops_refresh_inbound_related_routes();
    vg_hanoi_stays_ops_log("Repaired Where to Stay in Hanoi side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_hanoi_stays_ops_force_republish_enabled()) {
    vg_hanoi_stays_ops_fail('Where to Stay in Hanoi is not a draft. Set VG_FORCE_HANOI_STAYS_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_hanoi_stays_ops_log("Preflight Where to Stay in Hanoi: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_hanoi_stays_ops_log('Preflight Where to Stay in Hanoi: no existing page found; creating a child page under /destinations/.');
}

$review_date = 'July 24, 2026';
$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg');
$old_quarter_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/Cho_Dong_Xuan%2C_Old_Quarter%2C_Hanoi%2C_Vietnam_%285245806573%29.jpg/1920px-Cho_Dong_Xuan%2C_Old_Quarter%2C_Hanoi%2C_Vietnam_%285245806573%29.jpg');
$old_quarter_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cho_Dong_Xuan,_Old_Quarter,_Hanoi,_Vietnam_(5245806573).jpg');
$temple_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/Main_gate_of_the_Temple_of_Literature%2C_Hanoi%2C_Vietnam%2C_20240123_0929_3068.jpg/1920px-Main_gate_of_the_Temple_of_Literature%2C_Hanoi%2C_Vietnam%2C_20240123_0929_3068.jpg');
$temple_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Main_gate_of_the_Temple_of_Literature,_Hanoi,_Vietnam,_20240123_0929_3068.jpg');
$thang_long_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0f/Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg/1920px-Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg');
$thang_long_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg');
$noi_bai_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/38/Noi_Bai_International_Airport_Terminal_2_Night_View.JPG/1920px-Noi_Bai_International_Airport_Terminal_2_Night_View.JPG');
$noi_bai_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_Terminal_2_Night_View.JPG');
$long_bien_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/c/c5/Long_Bien_Bridge.jpg');
$long_bien_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Long_Bien_Bridge.jpg');

$content = <<<HTML
<!-- vg-hanoi-stays-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover vg-hanoi-stays-hero"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover vg-hanoi-stays-hero" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hoan Kiem Lake in central Hanoi, Vietnam" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed stay-area decision guide - Updated {$review_date}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Where to Stay in Hanoi</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Hanoi hotel choice is a route decision before it is a room decision. Choose the area by what the first night must protect: arrival recovery, Old Quarter walking, calmer central sleep, history days, cafe-paced longer stays, northern pickup logistics, or a fragile Noi Bai flight.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"className":"vg-guide-hero-actions"} -->
<div class="wp-block-buttons vg-guide-hero-actions"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/destinations/hanoi-travel-guide/">Start with the Hanoi guide</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- wp:paragraph {"className":"vg-image-credit"} -->
<p class="vg-image-credit"><a href="{$hero_credit_url}" target="_blank" rel="license noopener">Alex 69200 vx / CC BY-SA 4.0</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-hanoi-stays-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-hanoi-stays-verdict vg-hanoi-stays-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-hanoi-stays-verdict vg-hanoi-stays-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Most first-time travelers should stay on the Hoan Kiem or calmer Old Quarter edge.</strong> That base solves arrival orientation, food, walking, ride-hailing, and most pickup conversations with the least friction. Choose French Quarter or south Hoan Kiem when you want a calmer premium central base; Ba Dinh when civic history and sleep matter more than late-night wandering; Tay Ho when the trip is longer, family-paced, or cafe-led; and Noi Bai airport-side only when flight risk is the actual job.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>The main mistake is booking a beautiful room before naming the job of the first night. If the first morning is Ninh Binh, Ha Long, Cat Ba, Bai Tu Long, or an early domestic flight, pickup clarity and transfer margin beat hotel ranking.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-hanoi-stays-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-hanoi-stays-shortlist">
<li><strong>Best first-time default:</strong> Hoan Kiem or the calmer Old Quarter edge when the route has two or three Hanoi nights.</li>
<li><strong>Best calmer central base:</strong> French Quarter or south Hoan Kiem when comfort, cars, and quieter nights matter.</li>
<li><strong>Best civic/history base:</strong> Ba Dinh when monuments, museums, wider roads, and sleep are more useful than late-night food density.</li>
<li><strong>Best long-stay/family pivot:</strong> Tay Ho when apartments, cafes, space, and slower rhythm matter more than classic first-time proximity.</li>
<li><strong>Best airport buffer:</strong> Noi Bai only for late arrivals, early flights, separate tickets, or a fragile onward chain.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-hanoi-stays-at-a-glance:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hanoi stay areas at a glance</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-stays-at-a-glance">
<thead><tr><th>Trip job</th><th>Best base</th><th>Why it works</th><th>Watch-out</th></tr></thead>
<tbody>
<tr><td data-label="Trip job">First Hanoi stay</td><td data-label="Best base">Hoan Kiem or Old Quarter edge</td><td data-label="Why it works">Shortest learning curve for food, lake walks, taxis, tours, and the first real evening.</td><td data-label="Watch-out">Avoid the loudest beer-street blocks if sleep matters.</td></tr>
<tr><td data-label="Trip job">Calmer central premium</td><td data-label="Best base">French Quarter or south Hoan Kiem</td><td data-label="Why it works">Still central, but generally broader streets, easier cars, and better hotel-service rhythm.</td><td data-label="Watch-out">Slightly less dense for late-night Old Quarter food walks.</td></tr>
<tr><td data-label="Trip job">History, government quarter, quieter nights</td><td data-label="Best base">Ba Dinh</td><td data-label="Why it works">Good for Ho Chi Minh Mausoleum area, Temple of Literature, Thang Long, and sleep.</td><td data-label="Watch-out">Less spontaneous food energy than Hoan Kiem.</td></tr>
<tr><td data-label="Trip job">Longer stay, families, remote work pace</td><td data-label="Best base">West Lake / Tay Ho</td><td data-label="Why it works">More space, apartments, cafes, lake walks, and slower evenings.</td><td data-label="Watch-out">More taxi time to the classic first-time core and some pickup points.</td></tr>
<tr><td data-label="Trip job">Late arrival, early flight, separate-ticket risk</td><td data-label="Best base">Noi Bai airport-side</td><td data-label="Why it works">Protects the flight when the transfer is the whole point of the night.</td><td data-label="Watch-out">Poor choice for a real Hanoi evening.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-stays-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof for the stay decision</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-hanoi-stays-photo-grid">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Hoan Kiem Lake in central Hanoi" loading="lazy" decoding="async"><figcaption>Hoan Kiem is the easiest first-night orientation base because the lake, food streets, taxis, and first walks are close. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Alex 69200 vx / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$old_quarter_image}" alt="Dong Xuan Market in Hanoi Old Quarter" loading="lazy" decoding="async"><figcaption>The Old Quarter is valuable for food and pickup density, but block-by-block noise matters. Image: <a href="{$old_quarter_credit_url}" target="_blank" rel="license noopener">yeowatzup / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$temple_image}" alt="Temple of Literature gate in Hanoi" loading="lazy" decoding="async"><figcaption>Ba Dinh and nearby civic-history areas fit travelers who want quieter nights and serious capital-history days. Image: <a href="{$temple_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$thang_long_image}" alt="Imperial Citadel of Thang Long in Hanoi" loading="lazy" decoding="async"><figcaption>Thang Long context makes Ba Dinh a real stay-area choice, not just a daytime taxi stop. Image: <a href="{$thang_long_credit_url}" target="_blank" rel="license noopener">katiebordner / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$long_bien_image}" alt="Long Bien Bridge in Hanoi" loading="lazy" decoding="async"><figcaption>Longer Hanoi stays reward slower local rhythm after the core first-time checklist is handled. Image: <a href="{$long_bien_credit_url}" target="_blank" rel="license noopener">TheRollo76 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$noi_bai_image}" alt="Noi Bai International Airport Terminal 2 at night" loading="lazy" decoding="async"><figcaption>Noi Bai-side hotels are tools for flight risk, not substitutes for staying in Hanoi. Image: <a href="{$noi_bai_credit_url}" target="_blank" rel="license noopener">Christakis Mina / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-hanoi-stays-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What sources can and cannot prove</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Official sources can frame Hanoi, the airport, weather, and heritage status. They cannot tell you whether a karaoke bar faces your room, whether your tour operator will pick up from Tay Ho, or whether your arrival night needs food, sleep, or a transfer buffer.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-stays-source-diversity">
<thead><tr><th>Source job</th><th>Useful reference</th><th>Traveler judgment still needed</th></tr></thead>
<tbody>
<tr><td data-label="Source job">Destination frame</td><td data-label="Useful reference"><a href="https://vietnam.travel/places-to-go/northern-vietnam/ha-noi" target="_blank" rel="noopener">Vietnam.travel Ha Noi</a></td><td data-label="Traveler judgment still needed">Whether Hanoi is a real city chapter or only the northern launch pad.</td></tr>
<tr><td data-label="Source job">Airport context</td><td data-label="Useful reference"><a href="https://vietnamairport.vn/en/noi-bai-airport" target="_blank" rel="noopener">Noi Bai airport</a></td><td data-label="Traveler judgment still needed">Whether late arrival, early departure, separate tickets, or weather make airport-side rational.</td></tr>
<tr><td data-label="Source job">Weather and comfort</td><td data-label="Useful reference"><a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a></td><td data-label="Traveler judgment still needed">How much walking, humidity, rain, cold snaps, and air quality your party can absorb.</td></tr>
<tr><td data-label="Source job">Heritage route weight</td><td data-label="Useful reference"><a href="https://whc.unesco.org/en/list/1328/" target="_blank" rel="noopener">UNESCO Thang Long</a></td><td data-label="Traveler judgment still needed">Whether Ba Dinh history should shape the hotel base or remain a planned daytime stop.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-stays-area-verdict:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Area verdicts by base</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-stays-area-verdict">
<thead><tr><th>Area</th><th>Best for</th><th>Why choose it</th><th>Do not choose it when</th></tr></thead>
<tbody>
<tr><td data-label="Area">Hoan Kiem / Old Quarter edge</td><td data-label="Best for">First-timers, two-night stays, food walkers, pickup simplicity.</td><td data-label="Why choose it">The city explains itself quickly here: lake orientation, food streets, museums, tour desks, and ride-hailing all sit close.</td><td data-label="Do not choose it when">The exact block is inside nightlife noise or your party needs elevator-heavy, quiet-hotel recovery.</td></tr>
<tr><td data-label="Area">French Quarter / south Hoan Kiem</td><td data-label="Best for">Calmer premium central stays, couples, older travelers, business-service needs.</td><td data-label="Why choose it">You stay central without placing every night inside Old Quarter density, and cars usually feel easier.</td><td data-label="Do not choose it when">Your trip depends on instant Old Quarter street-food wandering every evening.</td></tr>
<tr><td data-label="Area">Ba Dinh</td><td data-label="Best for">History-heavy days, lighter sleepers, families who want calmer streets.</td><td data-label="Why choose it">It works for Ho Chi Minh Mausoleum area, Temple of Literature, Thang Long, and quieter civic rhythm.</td><td data-label="Do not choose it when">You have only one social Hanoi night and want the city outside the door.</td></tr>
<tr><td data-label="Area">West Lake / Tay Ho</td><td data-label="Best for">Longer stays, families, cafe pace, apartments, repeat visitors.</td><td data-label="Why choose it">Bigger rooms, lake walks, restaurants, and slower rhythm make Hanoi feel livable.</td><td data-label="Do not choose it when">You have one or two nights and need simple Old Quarter pickup or first-time sightseeing efficiency.</td></tr>
<tr><td data-label="Area">Noi Bai airport-side</td><td data-label="Best for">Late arrivals, early flights, separate-ticket risk, weather-sensitive onward plans.</td><td data-label="Why choose it">It removes one uncertain city transfer from a fragile flight chain.</td><td data-label="Do not choose it when">You still have a usable evening or morning in Hanoi.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-stays-first-time-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best area by first-night and trip length</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-stays-first-time-fit">
<thead><tr><th>Hanoi time</th><th>Recommended base</th><th>First-night logic</th><th>Booking posture</th></tr></thead>
<tbody>
<tr><td data-label="Hanoi time">Late arrival, no city time</td><td data-label="Recommended base">Noi Bai airport-side or a pre-booked central transfer</td><td data-label="First-night logic">If the next morning is a flight or long road transfer, protect sleep and luggage movement.</td><td data-label="Booking posture">Choose airport-side only when the transfer risk is worth losing Hanoi atmosphere.</td></tr>
<tr><td data-label="Hanoi time">One night</td><td data-label="Recommended base">Hoan Kiem or French Quarter</td><td data-label="First-night logic">You need one easy meal, one walk, one simple pickup, and no cross-city experiment.</td><td data-label="Booking posture">Pay for quiet room notes, elevator access, and confirmed late check-in.</td></tr>
<tr><td data-label="Hanoi time">Two or three nights</td><td data-label="Recommended base">Hoan Kiem / Old Quarter edge</td><td data-label="First-night logic">This is the best first-time default for lake, food, attractions, and northern pickup conversations.</td><td data-label="Booking posture">Avoid the loudest nightlife lanes; choose edge blocks with clear vehicle access.</td></tr>
<tr><td data-label="Hanoi time">Four or more nights</td><td data-label="Recommended base">Hoan Kiem first or Tay Ho/Ba Dinh split</td><td data-label="First-night logic">Start central, then move if family space, cafes, quieter sleep, or apartment living matters.</td><td data-label="Booking posture">A split stay can work only if luggage movement does not steal the trip.</td></tr>
<tr><td data-label="Hanoi time">Returning after bay or Ninh Binh</td><td data-label="Recommended base">French Quarter, Ba Dinh, or airport-side by departure time</td><td data-label="First-night logic">The final Hanoi night should make the next transfer boring.</td><td data-label="Booking posture">Choose central for a real final dinner; choose airport-side for fragile morning flights.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-stays-noise-map:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hanoi noise map for sleep-sensitive travelers</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-stays-noise-map">
<thead><tr><th>Noise risk</th><th>Where it appears</th><th>Sleep strategy</th><th>Best replacement</th></tr></thead>
<tbody>
<tr><td data-label="Noise risk">Nightlife, street bars, late food traffic</td><td data-label="Where it appears">Core Old Quarter blocks and beer-street-adjacent lanes.</td><td data-label="Sleep strategy">Read recent room-specific reviews for street-facing windows, music, and construction.</td><td data-label="Best replacement">Old Quarter edge, Hoan Kiem lake edge, or French Quarter.</td></tr>
<tr><td data-label="Noise risk">Traffic and horns</td><td data-label="Where it appears">Busy lake roads, large intersections, and hotel entrances used for pickups.</td><td data-label="Sleep strategy">Ask for higher floors or rear rooms before paying.</td><td data-label="Best replacement">South Hoan Kiem, Ba Dinh side streets, or Tay Ho away from main roads.</td></tr>
<tr><td data-label="Noise risk">Construction and neighborhood work</td><td data-label="Where it appears">Any fast-changing central block.</td><td data-label="Sleep strategy">Use same-month reviews, not just average scores.</td><td data-label="Best replacement">Better-managed central hotels with responsive desks.</td></tr>
<tr><td data-label="Noise risk">Airport-road practical noise</td><td data-label="Where it appears">Noi Bai-side budget blocks and highway-facing rooms.</td><td data-label="Sleep strategy">Choose airport-side for transfer control, not ambiance.</td><td data-label="Best replacement">Central Hanoi if the flight is not fragile.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-stays-airport-buffer:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Noi Bai airport buffer logic</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Noi Bai is far enough from central Hanoi that airport-side hotels can be sensible, but only for a narrow job. Use them for a late international arrival, an early departure, separate tickets, a same-day domestic connection, a weather-sensitive mountain or bay plan, or a traveler who cannot absorb a late city transfer. If the evening still has real Hanoi value, central usually wins.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-stays-airport-buffer">
<thead><tr><th>Scenario</th><th>Base call</th><th>Reason</th><th>Live check</th></tr></thead>
<tbody>
<tr><td data-label="Scenario">Arrive after 22:00 with children or older travelers</td><td data-label="Base call">Airport-side or pre-arranged central transfer</td><td data-label="Reason">The first night is recovery and logistics, not sightseeing.</td><td data-label="Live check">Confirm hotel desk hours and pickup instructions.</td></tr>
<tr><td data-label="Scenario">Early flight before 08:00</td><td data-label="Base call">Airport-side is defensible</td><td data-label="Reason">One missed alarm or traffic delay can cost more than the room.</td><td data-label="Live check">Confirm terminal, transfer time, and domestic/international check-in margin.</td></tr>
<tr><td data-label="Scenario">Normal morning departure</td><td data-label="Base call">Stay central</td><td data-label="Reason">A final Hanoi dinner is usually worth more than sleeping beside the airport.</td><td data-label="Live check">Book a car and leave a conservative buffer.</td></tr>
<tr><td data-label="Scenario">Separate-ticket connection</td><td data-label="Base call">Airport-side or extra night</td><td data-label="Reason">Delay protection matters more than neighborhood charm.</td><td data-label="Live check">Recheck baggage, immigration, weather, and airline schedule changes.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-stays-day-trip-pickup:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Pickup and transfer fit</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Most Hanoi stay mistakes show up at pickup time. Before paying, ask the operator whether they collect from your exact hotel, a nearby meeting point, or only central Old Quarter/Hoan Kiem addresses. This matters most before Ninh Binh, Ha Long, Cat Ba, Bai Tu Long, Sapa, and early flights.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-stays-day-trip-pickup">
<thead><tr><th>Next move</th><th>Best base</th><th>Pickup language to use</th><th>Fallback</th></tr></thead>
<tbody>
<tr><td data-label="Next move">Ninh Binh day trip or overnight</td><td data-label="Best base">Hoan Kiem / Old Quarter edge</td><td data-label="Pickup language to use">Ask whether the van collects at the hotel door or a listed meeting point.</td><td data-label="Fallback"><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh</a> overnight if the day feels too rushed.</td></tr>
<tr><td data-label="Next move">Ha Long, Bai Tu Long, or Lan Ha cruise</td><td data-label="Best base">Hoan Kiem, French Quarter, or confirmed cruise pickup zone</td><td data-label="Pickup language to use">Ask for pickup district, luggage rules, and return drop-off before booking the hotel.</td><td data-label="Fallback"><a href="/destinations/ha-long-bay-travel-guide/">Ha Long</a>, <a href="/destinations/bai-tu-long-bay-guide/">Bai Tu Long</a>, or <a href="/destinations/cat-ba-travel-guide/">Cat Ba</a> based on the route job.</td></tr>
<tr><td data-label="Next move">City-history day</td><td data-label="Best base">Ba Dinh, French Quarter, or Hoan Kiem</td><td data-label="Pickup language to use">Use ride-hailing and opening times instead of depending on a tour pickup.</td><td data-label="Fallback"><a href="/destinations/best-things-to-do-in-hanoi/">Best Things to Do in Hanoi</a> for priority order.</td></tr>
<tr><td data-label="Next move">Airport, train, or southbound flight</td><td data-label="Best base">French Quarter, Hoan Kiem, Ba Dinh, or Noi Bai by departure risk</td><td data-label="Pickup language to use">Ask the hotel to quote a car, pickup time, terminal, and luggage buffer.</td><td data-label="Fallback"><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before making the transfer too tight.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-stays-traveler-profiles:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best Hanoi base by traveler profile</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-stays-traveler-profiles">
<thead><tr><th>Traveler profile</th><th>Best base</th><th>Why</th><th>Book for</th></tr></thead>
<tbody>
<tr><td data-label="Traveler profile">First-time couple or solo traveler</td><td data-label="Best base">Hoan Kiem / Old Quarter edge</td><td data-label="Why">The city is easiest to read on foot, with food and lake orientation close.</td><td data-label="Book for">Quiet room, clear address, and walkable dinner.</td></tr>
<tr><td data-label="Traveler profile">Family with children</td><td data-label="Best base">French Quarter, south Hoan Kiem, or Tay Ho</td><td data-label="Why">Better car access, larger rooms, and calmer evenings can beat maximum density.</td><td data-label="Book for">Elevator, breakfast, connecting rooms, and reliable taxis.</td></tr>
<tr><td data-label="Traveler profile">Older travelers or light sleepers</td><td data-label="Best base">French Quarter, Ba Dinh, or quiet Hoan Kiem edge</td><td data-label="Why">Central enough for easy days, but less exposed to nightlife.</td><td data-label="Book for">Rear room, lift, short lobby-to-car path, and responsive front desk.</td></tr>
<tr><td data-label="Traveler profile">Longer-stay traveler</td><td data-label="Best base">Tay Ho, then Hoan Kiem when needed</td><td data-label="Why">Apartment supply, cafes, space, and slower routines matter over a week.</td><td data-label="Book for">Laundry, workspace, kitchen, and predictable ride-hailing.</td></tr>
<tr><td data-label="Traveler profile">Luxury or business traveler</td><td data-label="Best base">French Quarter / south Hoan Kiem</td><td data-label="Why">Premium hotel service stays central without full Old Quarter intensity.</td><td data-label="Book for">Car access, breakfast, concierge desk, and quiet sleep.</td></tr>
<tr><td data-label="Traveler profile">Flight-risk traveler</td><td data-label="Best base">Noi Bai airport-side</td><td data-label="Why">The stay is about protecting the transfer chain.</td><td data-label="Book for">Terminal transfer, late check-in, early breakfast or breakfast box, and cancellation terms.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-stays-cost-booking:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cost and booking checks before paying</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-stays-cost-booking"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-stays-cost-booking">
<li>Price the hotel against taxi time, not only room rate. Tay Ho can be great value for a long stay and weak value for a two-night first visit.</li>
<li>Use refundable or changeable bookings when the first night follows an international arrival, bay cruise, or domestic flight.</li>
<li>Read the lowest recent reviews for noise, construction, mold, lift failures, and pickup confusion. Average score is too blunt for Hanoi stay choice.</li>
<li>For Old Quarter rooms, confirm whether taxis can reach the door or whether the last meters require walking with luggage.</li>
<li>For family and premium stays, pay for breakfast, elevator access, room size, and front-desk competence before chasing a distant bargain.</li>
<li>Avoid affiliate-style ranking logic. The best Hanoi hotel is the one that makes the next morning simple.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-stays-safety-comfort:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Safety and comfort posture</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-stays-safety-comfort">
<thead><tr><th>Comfort issue</th><th>Where it matters</th><th>Practical response</th></tr></thead>
<tbody>
<tr><td data-label="Comfort issue">Street crossing and scooters</td><td data-label="Where it matters">Old Quarter and Hoan Kiem walks.</td><td data-label="Practical response">Choose a base that reduces unnecessary road crossings on arrival night.</td></tr>
<tr><td data-label="Comfort issue">Phone and bag handling</td><td data-label="Where it matters">Crowded food streets, markets, lake edges, and pickup points.</td><td data-label="Practical response">Use a crossbody bag, avoid curbside phone handling, and set ride-hailing before stepping into traffic.</td></tr>
<tr><td data-label="Comfort issue">Weather, humidity, cold snaps, air quality</td><td data-label="Where it matters">Long walking days, families, older travelers, and shoulder-season routes.</td><td data-label="Practical response">Keep indoor backups and avoid booking a far-out base that depends on perfect walking weather.</td></tr>
<tr><td data-label="Comfort issue">Luggage and stairs</td><td data-label="Where it matters">Old buildings, boutique rooms, and narrow Old Quarter lanes.</td><td data-label="Practical response">Confirm elevator, room floor, taxi access, and luggage storage before paying.</td></tr>
<tr><td data-label="Comfort issue">Late-night return</td><td data-label="Where it matters">Solo travelers, families, and quieter areas after dinner.</td><td data-label="Practical response">Choose a known pickup point, keep data active, and do not make the final walk complicated.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-stays-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before locking the hotel</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-stays-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-stays-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/northern-vietnam/ha-noi" target="_blank" rel="noopener">Vietnam.travel Ha Noi</a>, <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>, and <a href="/destinations/best-things-to-do-in-hanoi/">Best Things to Do in Hanoi</a> before deciding whether the city deserves one night, two nights, or a deeper base.</li>
<li>Check <a href="https://vietnamairport.vn/en/noi-bai-airport" target="_blank" rel="noopener">Noi Bai airport</a>, airline times, hotel transfer terms, <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, and <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a> before making the first night fragile.</li>
<li>Compare <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, and same-week forecasts before depending on long walks, bay transfers, or mountain movement.</li>
<li>Use <a href="https://whc.unesco.org/en/list/1328/" target="_blank" rel="noopener">UNESCO Thang Long</a>, official opening information, and <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before building a Ba Dinh-heavy day around fixed hours.</li>
<li>Before paying, read <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-stays-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where to stay in Hanoi FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-hanoi-stays-faq">
<details><summary>What is the best area to stay in Hanoi for the first time?</summary><p>Hoan Kiem or the calmer Old Quarter edge is the best first-time default. It keeps food, lake walks, ride-hailing, tour pickups, and Hanoi orientation simple without forcing a cross-city commute on the first night.</p></details>
<details><summary>Should I stay in the Old Quarter?</summary><p>Stay in or near the Old Quarter if you want food density, walking energy, and pickup convenience. Avoid the loudest nightlife blocks if sleep matters before Ninh Binh, Ha Long, Cat Ba, Bai Tu Long, or an early flight.</p></details>
<details><summary>Is the French Quarter better than the Old Quarter?</summary><p>The French Quarter and south Hoan Kiem are better for calmer premium central stays, easier cars, and quieter nights. The Old Quarter is better for dense street life and the easiest first-time food wandering.</p></details>
<details><summary>Is Tay Ho a good area for tourists?</summary><p>Tay Ho is good for longer stays, families, apartments, cafes, and repeat visitors. It is not the best default for a short first visit because the classic Hanoi core and many pickup conversations sit closer to Hoan Kiem.</p></details>
<details><summary>Should I stay near Noi Bai Airport?</summary><p>Stay near Noi Bai only when flight risk is the job: late arrival, early departure, separate tickets, or a fragile onward chain. If you still have a useful Hanoi evening, central usually gives better trip value.</p></details>
<details><summary>Where should I stay before a Ninh Binh or Ha Long pickup?</summary><p>Hoan Kiem or the Old Quarter edge is usually easiest, but the operator's exact pickup policy wins. Ask whether pickup is hotel-door, district-limited, or meeting-point only before booking the room.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>, then use <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a>, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh</a>, <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay</a>, <a href="/destinations/cat-ba-travel-guide/">Cat Ba</a>, and <a href="/destinations/bai-tu-long-bay-guide/">Bai Tu Long Bay</a> before making the base carry too many jobs. If the real choice is already narrowed to central energy, calmer premium central, or lake-side space, use <a href="/compare/old-quarter-vs-french-quarter-vs-west-lake/">Old Quarter vs French Quarter vs West Lake</a> before booking.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-hanoi-stays-hero:v1',
    'concierge verdict' => 'vg-hanoi-stays-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-hanoi-stays-at-a-glance:v1',
    'photo grid' => 'vg-hanoi-stays-photo-grid:v1',
    'source diversity' => 'vg-hanoi-stays-source-diversity:v1',
    'area verdict' => 'vg-hanoi-stays-area-verdict:v1',
    'first-time fit' => 'vg-hanoi-stays-first-time-fit:v1',
    'noise map' => 'vg-hanoi-stays-noise-map:v1',
    'airport buffer' => 'vg-hanoi-stays-airport-buffer:v1',
    'day-trip pickup' => 'vg-hanoi-stays-day-trip-pickup:v1',
    'traveler profiles' => 'vg-hanoi-stays-traveler-profiles:v1',
    'cost booking' => 'vg-hanoi-stays-cost-booking:v1',
    'safety comfort' => 'vg-hanoi-stays-safety-comfort:v1',
    'live checks' => 'vg-hanoi-stays-live-checks:v1',
    'FAQ' => 'vg-hanoi-stays-faq:v1',
    'related routes shortcode' => '[vg_related_routes]',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_hanoi_stays_ops_assert_required_content_markers($content, $required_content_markers);
vg_hanoi_stays_ops_assert_internal_page_links_are_published('Where to Stay in Hanoi guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Where to Stay in Hanoi',
    'post_name'      => 'where-to-stay-in-hanoi',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_hanoi_stays_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led guide to where to stay in Hanoi: Hoan Kiem, Old Quarter edge, French Quarter, Ba Dinh, Tay Ho, and Noi Bai by trip job.',
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
    vg_hanoi_stays_ops_fail('Could not publish Where to Stay in Hanoi: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_hanoi_stays_ops_fail('Could not publish Where to Stay in Hanoi: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Where to Stay in Hanoi: Best Areas for First-Timers');
update_post_meta($page_id, 'rank_math_description', 'Where to stay in Hanoi: choose Hoan Kiem, Old Quarter edge, French Quarter, Ba Dinh, Tay Ho, or Noi Bai by first-night and pickup logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Where to Stay in Hanoi');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Choose the best Hanoi stay area by trip job: first-time Hoan Kiem ease, calmer premium central French Quarter, quieter Ba Dinh history, longer-stay Tay Ho rhythm, or Noi Bai flight-risk buffer.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published Where to Stay in Hanoi with a concierge verdict, fast area answer, licensed photo proof, source-diversity panel, area verdict table, first-time fit by nights, noise map, airport buffer logic, day-trip pickup checks, traveler profile matrix, cost and booking checks, safety/comfort posture, live checks, FAQ, Destinations hub note, homepage route-spine support, inbound related routes, source trail, and update log.');

$sources_checked = implode(
    "\n",
    [
        "Vietnam.travel - Ha Noi destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-noi - checked {$review_date}",
        "Vietnam.travel - Explore Old Quarter your way - https://vietnam.travel/things-to-do/explore-old-quarter-your-way - checked {$review_date}",
        "Vietnam.travel - Comfort meet culture Hanoi luxury hotels - https://vietnam.travel/things-to-do/comfort-meet-culture-hanoi-luxury-hotels - checked {$review_date}",
        "Vietnam.travel - Vietnamese history primer - https://vietnam.travel/things-to-do/vietnamese-history-primer - checked {$review_date}",
        "Vietnam.travel - 11 must-see attractions Ha Noi - https://vietnam.travel/things-to-do/11-must-see-attractions-ha-noi - checked {$review_date}",
        "Noi Bai International Airport - https://vietnamairport.vn/en/noi-bai-airport - checked {$review_date}",
        "National Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked {$review_date}",
        "UNESCO World Heritage Centre - Central Sector of the Imperial Citadel of Thang Long - Ha Noi - https://whc.unesco.org/en/list/1328/ - checked {$review_date}",
        "Wikimedia Commons image record - Hanoi Hoan Kiem Lake - https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Cho Dong Xuan, Old Quarter, Hanoi - https://commons.wikimedia.org/wiki/File:Cho_Dong_Xuan,_Old_Quarter,_Hanoi,_Vietnam_(5245806573).jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Main gate of the Temple of Literature, Hanoi - https://commons.wikimedia.org/wiki/File:Main_gate_of_the_Temple_of_Literature,_Hanoi,_Vietnam,_20240123_0929_3068.jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Central Sector of the Imperial Citadel of Thang Long - Hanoi - https://commons.wikimedia.org/wiki/File:Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Noi Bai International Airport Terminal 2 Night View - https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_Terminal_2_Night_View.JPG - license checked {$review_date}",
        "Wikimedia Commons image record - Long Bien Bridge - https://commons.wikimedia.org/wiki/File:Long_Bien_Bridge.jpg - license checked {$review_date}",
    ]
);
update_post_meta($page_id, 'vg_eeat_sources_checked', $sources_checked);
update_post_meta($page_id, 'vg_eeat_field_note', 'Stay-area choice in Hanoi should be made before hotel comparison. Hoan Kiem and the Old Quarter edge solve first-time friction, French Quarter solves calmer premium central access, Ba Dinh solves civic-history sleep, Tay Ho solves longer-stay and family rhythm, and Noi Bai solves flight risk only.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Hanoi stay guide built as a base-selection decision tool rather than a hotel ranking or affiliate list.\nConcierge verdict separates Hoan Kiem/Old Quarter first-time default, French Quarter/south Hoan Kiem calmer premium central, Ba Dinh civic-history sleep, Tay Ho longer-stay/family/cafe rhythm, and Noi Bai airport-side flight-risk use.\nFast answer table resolves first-timer, calmer stay, history base, family/long-stay, and airport-control questions.\nLicensed photo proof shows Hoan Kiem, Old Quarter/Dong Xuan, Temple of Literature, Thang Long, Long Bien, and Noi Bai.\nSource-diversity panel separates official destination, airport, weather, and heritage sources from traveler judgment about exact blocks.\nArea verdict table chooses by trip job, not hotel photos.\nFirst-time fit table changes the base recommendation by arrival time, night count, return night, and transfer job.\nNoise map protects sleep before Ninh Binh, Ha Long, Cat Ba, Bai Tu Long, city-history days, and flights.\nAirport buffer logic prevents overusing Noi Bai hotels when central Hanoi still has trip value.\nDay-trip pickup matrix ties hotel area to Ninh Binh, bay cruises, city history, airport movement, and train/flight transfers.\nTraveler profile, booking checks, safety/comfort posture, FAQ, source trail, update log, and related routes keep the guide durable without resorting to stale hotel rankings.");

$related_routes = "Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Start with Hanoi's route role, night count, airport arrival logic, and northern launch plan before choosing the exact stay area.\nOld Quarter vs French Quarter vs West Lake | /compare/old-quarter-vs-french-quarter-vs-west-lake/ | Compare Hanoi's main sleep zones by walking energy, calmer premium central access, lake-side space, pickup clarity, and cost trade-offs.\nBest Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Narrow the attraction list after choosing the base that makes the first night and first morning simple.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose the outside day only after the hotel base can support pickup clarity, return comfort, and the next transfer.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Put Hanoi stay choice inside the full first-trip planning order.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Decide whether Hanoi, Ninh Binh, bay, mountains, or Central Vietnam belong in the destination set.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether northern Vietnam should lead the trip before overloading Hanoi transfers.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check north weather, heat, rain, cold, and bay or mountain timing before locking the area.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Count Noi Bai arrival, day-trip pickups, trains, cruise transfers, and onward flights.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Compare hotel area, airport transfer, taxi time, room quality, and private movement before paying.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Prepare arrival cash, cards, deposits, and ATM habits before Hanoi check-in.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, ride-hailing, hotel contact, pickup coordination, and airport movement from becoming avoidable friction.\nVietnam E-Visa | /plan/vietnam-evisa/ | Recheck official entry timing before building Hanoi around arrival assumptions.\n7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Decide whether a one-week route should stay north-focused and use Hanoi as the working base.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Hanoi can support Ninh Binh, the bay, and Central Vietnam without rushing.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when two weeks can protect Hanoi, one northern side trip, and a central chapter.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use three weeks to choose deeper Hanoi, bay, mountains, and central or southern movement.\nNinh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide whether the first Hanoi side move should be a day trip or overnight countryside.\nHa Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Choose bay timing after Hanoi arrival and cruise pickup logic are clear.\nCat Ba Travel Guide | /destinations/cat-ba-travel-guide/ | Use when the bay chapter needs island-base logic instead of a simple cruise pickup.\nBai Tu Long Bay Guide | /destinations/bai-tu-long-bay-guide/ | Compare quieter bay choices after the core Hanoi route and transfer plan are protected.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check trip interruption, road transfers, heat or cold, and mountain or bay coverage.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair Hanoi traffic, taxis, markets, phone handling, night movement, and airport arrivals with practical risk habits.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.";
vg_hanoi_stays_ops_assert_related_route_meta_links_are_published('Where to Stay in Hanoi related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Hoan Kiem image: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0. Body images: Cho Dong Xuan, Old Quarter, Hanoi by yeowatzup, CC BY 2.0; Temple of Literature by Jakub Halun, CC BY 4.0; Central Sector of the Imperial Citadel of Thang Long by katiebordner, CC BY 2.0; Noi Bai International Airport Terminal 2 Night View by Christakis Mina, CC BY-SA 4.0; Long Bien Bridge by TheRollo76, CC BY-SA 4.0.');
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
        vg_hanoi_stays_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_hanoi_stays_ops_fail('Where to Stay in Hanoi was updated but is not published.');
}

vg_hanoi_stays_ops_refresh_destinations_hub();
vg_hanoi_stays_ops_refresh_homepage_route_spine();
vg_hanoi_stays_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_hanoi_stays_ops_log("Published Where to Stay in Hanoi: {$page_id} {$updated_permalink}");

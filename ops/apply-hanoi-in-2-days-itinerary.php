<?php
/**
 * Publish the Hanoi in 2 Days itinerary support guide.
 *
 * Self-reference marker: ops/apply-hanoi-in-2-days-itinerary.php
 *
 * Run from the WordPress root with:
 * VG_FORCE_HANOI_2_DAYS_REPUBLISH=1 wp eval-file ops/apply-hanoi-in-2-days-itinerary.php --allow-root
 *
 * Repair only hub/related-route/homepage side effects with:
 * VG_REPAIR_HANOI_2_DAYS_LINKS=1 wp eval-file ops/apply-hanoi-in-2-days-itinerary.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_hanoi_two_days_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_hanoi_two_days_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_hanoi_two_days_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_HANOI_2_DAYS_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_hanoi_two_days_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_HANOI_2_DAYS_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_hanoi_two_days_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_hanoi_two_days_ops_fail('Could not resolve a valid WordPress author for Hanoi in 2 Days.');
}

function vg_hanoi_two_days_ops_internal_path_from_href(string $href): ?string
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

function vg_hanoi_two_days_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_hanoi_two_days_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_hanoi_two_days_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_two_days_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_hanoi_two_days_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_hanoi_two_days_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_hanoi_two_days_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_hanoi_two_days_ops_log("Validated {$label} internal page links are published.");
}

function vg_hanoi_two_days_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_hanoi_two_days_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_hanoi_two_days_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_hanoi_two_days_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_hanoi_two_days_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_hanoi_two_days_ops_log("Validated {$label} related-route links are published.");
}

function vg_hanoi_two_days_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_hanoi_two_days_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
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
            vg_hanoi_two_days_ops_fail("Could not confidently refresh {$label}.");
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
        vg_hanoi_two_days_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_hanoi_two_days_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_hanoi_two_days_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_hanoi_two_days_ops_homepage(): ?WP_Post
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

function vg_hanoi_two_days_ops_refresh_itineraries_hub(): void
{
    $hub = get_page_by_path('itineraries', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_hanoi_two_days_ops_log('Skipped Itineraries hub refresh: itineraries page was not found or is not published.');
        return;
    }

    if (! vg_hanoi_two_days_ops_published_page_exists('itineraries/hanoi-in-2-days')) {
        vg_hanoi_two_days_ops_log('Skipped Itineraries hub refresh: Hanoi in 2 Days is not published.');
        return;
    }

    $marker = '<!-- vg-hanoi-2-days-itineraries-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use two Hanoi days as a pacing test</h3><p>The <a href="/itineraries/hanoi-in-2-days/">Hanoi in 2 Days</a> itinerary helps travelers protect arrival recovery, one serious culture block, one food-led evening, and the next transfer instead of turning the capital into a rushed checklist.</p></div>
<!-- /wp:group -->
HTML;

    vg_hanoi_two_days_ops_assert_internal_page_links_are_published('Itineraries hub Hanoi in 2 Days note', $block);
    vg_hanoi_two_days_ops_upsert_marked_group($hub, 'Itineraries hub Hanoi in 2 Days note', $marker, $block);
}

function vg_hanoi_two_days_ops_refresh_homepage_route_spine(): void
{
    $home = vg_hanoi_two_days_ops_homepage();

    if (! $home instanceof WP_Post) {
        vg_hanoi_two_days_ops_log('Skipped homepage route-spine refresh: homepage was not found or is not published.');
        return;
    }

    if (! vg_hanoi_two_days_ops_published_page_exists('itineraries/hanoi-in-2-days')) {
        vg_hanoi_two_days_ops_log('Skipped homepage route-spine refresh: Hanoi in 2 Days is not published.');
        return;
    }

    $marker = '<!-- vg-hanoi-2-days-homepage-route-spine:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-home-section vg-home-decision-spine vg-home-hanoi-2-days-spine"} -->
<div class="wp-block-group vg-home-section vg-home-decision-spine vg-home-hanoi-2-days-spine"><p class="vg-kicker">Hanoi short-stay itinerary</p><h2>Hanoi in 2 Days</h2><p>Use the two-day itinerary when Hanoi must feel complete without exhausting the next transfer. It protects arrival energy, one food-led evening, one serious culture block, one flexible weather pivot, and a calmer exit.</p><p class="vg-section-link"><a href="/itineraries/hanoi-in-2-days/">Plan two days in Hanoi</a></p></div>
<!-- /wp:group -->
HTML;

    vg_hanoi_two_days_ops_assert_internal_page_links_are_published('Homepage Hanoi in 2 Days route-spine note', $block);
    vg_hanoi_two_days_ops_upsert_marked_group($home, 'Homepage Hanoi in 2 Days route-spine note', $marker, $block);
}

function vg_hanoi_two_days_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_two_days_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_hanoi_two_days_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_hanoi_two_days_ops_log("Skipped related-route refresh for {$label}: Hanoi in 2 Days line is already current.");
        return;
    }

    update_post_meta($page->ID, 'vg_eeat_related_routes', implode("\n", $next_lines));

    vg_hanoi_two_days_ops_log("Upserted Hanoi in 2 Days related route to {$label}: {$page->ID}");
}

function vg_hanoi_two_days_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Hanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.';

    foreach (
        [
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
            'compare/old-quarter-vs-french-quarter-vs-west-lake' => 'Old Quarter vs French Quarter vs West Lake',
            'plan/hanoi-airport-to-old-quarter' => 'Hanoi Airport to Old Quarter',
            'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
        ] as $path => $label
    ) {
        vg_hanoi_two_days_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

function vg_hanoi_two_days_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_hanoi_two_days_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

$parent = get_page_by_path('itineraries', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_hanoi_two_days_ops_fail('Could not find published /itineraries/ parent page.');
}

$page = get_page_by_path('itineraries/hanoi-in-2-days', OBJECT, 'page');

if (vg_hanoi_two_days_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_two_days_ops_fail('Repair mode requires Hanoi in 2 Days to already be published.');
    }

    vg_hanoi_two_days_ops_refresh_itineraries_hub();
    vg_hanoi_two_days_ops_refresh_homepage_route_spine();
    vg_hanoi_two_days_ops_refresh_inbound_related_routes();
    vg_hanoi_two_days_ops_log("Repaired Hanoi in 2 Days side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_hanoi_two_days_ops_force_republish_enabled()) {
    vg_hanoi_two_days_ops_fail('Hanoi in 2 Days is not a draft. Set VG_FORCE_HANOI_2_DAYS_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_hanoi_two_days_ops_log("Preflight Hanoi in 2 Days: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_hanoi_two_days_ops_log('Preflight Hanoi in 2 Days: no existing page found; creating a child page under /itineraries/.');
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
$long_bien_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/c/c5/Long_Bien_Bridge.jpg');
$long_bien_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Long_Bien_Bridge.jpg');

$content = <<<HTML
<!-- vg-hanoi-2-days-hero:v1 -->
<!-- wp:cover {"url":"{$hero_image}","alt":"Hoan Kiem Lake in central Hanoi for a two-day itinerary","dimRatio":58,"minHeight":620,"className":"vg-guide-hero-cover vg-hanoi-2-days-hero"} -->
<div class="wp-block-cover vg-guide-hero-cover vg-hanoi-2-days-hero" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-60 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hoan Kiem Lake in central Hanoi for a two-day itinerary" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Hanoi short-stay itinerary - Updated {$review_date}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Hanoi in 2 Days</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">A two-day Hanoi itinerary should make the capital feel coherent without weakening the next transfer. Use this plan to protect arrival recovery, Hoan Kiem and Old Quarter orientation, one food-led evening, one serious culture block, weather pivots, and the energy needed for Ninh Binh, the bay, mountains, or Central Vietnam.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: two days is not enough for every Hanoi highlight. It is enough for one confident city arc if you stop treating the first 48 hours as a sightseeing debt.</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- vg-hanoi-2-days-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-hanoi-2-days-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-hanoi-2-days-concierge-verdict">
<!-- wp:heading -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most first-time travelers, the best two-day Hanoi plan is arrival recovery plus Hoan Kiem and Old Quarter on Day 1, then one culture block, one museum or cafe/rain pivot, and a lighter evening before the next transfer on Day 2.</strong> Add a day trip only if Hanoi already has two calm city blocks and the next morning is not fragile.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><strong>Best default base:</strong> Hoan Kiem, Old Quarter edge, or a calmer French Quarter pocket.</li>
<li><strong>Best first evening:</strong> nearby dinner, short lake or Old Quarter walk, and sleep rather than a prepaid tour after a late arrival.</li>
<li><strong>Best culture choice:</strong> Temple of Literature for a clean first stop, Thang Long for heritage depth, or Vietnamese Women's Museum when rain or heat pushes the day indoors.</li>
<li><strong>Best skip rule:</strong> skip Train Street, duplicate markets, and a long day trip before you cut sleep, food confidence, or the next transfer buffer.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-hanoi-2-days-at-a-glance:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Fast answer for 48 hours in Hanoi</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-2-days-at-a-glance">
<thead><tr><th>Traveler state</th><th>Best two-day shape</th><th>Protect</th><th>Cut first</th></tr></thead>
<tbody>
<tr><td data-label="Traveler state">Late arrival or long-haul fatigue</td><td data-label="Best two-day shape">Day 1 becomes recovery, nearby food, and orientation; Day 2 carries the culture block.</td><td data-label="Protect">Sleep, phone setup, cash/card readiness, and a simple first morning.</td><td data-label="Cut first">Paid night tour and any early day trip.</td></tr>
<tr><td data-label="Traveler state">Two full city days</td><td data-label="Best two-day shape">Day 1 lake/Old Quarter/food; Day 2 Temple or Thang Long plus museum/cafe pivot.</td><td data-label="Protect">One slower block so Hanoi has atmosphere, not only stops.</td><td data-label="Cut first">A second heavy heritage stop.</td></tr>
<tr><td data-label="Traveler state">Day trip temptation</td><td data-label="Best two-day shape">Keep the day trip only if it replaces Day 2 cleanly and does not damage transfer energy.</td><td data-label="Protect">Pickup clarity, return hour, packing, and next-day departure.</td><td data-label="Cut first">Same-day bay preview and rushed distant excursions.</td></tr>
<tr><td data-label="Traveler state">Rain, heat, or family pace</td><td data-label="Best two-day shape">Early walk, indoor story, food/cafe breaks, shorter taxi hops.</td><td data-label="Protect">Attention span, shade, restrooms, and predictable meals.</td><td data-label="Cut first">Long unstructured midday wandering.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-2-days-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what the two days are balancing</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The images are not decoration. They show the competing jobs inside a short Hanoi stay: orientation, street rhythm, one readable culture stop, deeper heritage, and a slow final pause.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-hanoi-2-days-photo-grid">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Hoan Kiem Lake in central Hanoi" loading="lazy" decoding="async"><figcaption>Hoan Kiem gives the first day a calm geographic center. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Alex 69200 vx / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$old_quarter_image}" alt="Dong Xuan Market in Hanoi Old Quarter" loading="lazy" decoding="async"><figcaption>The Old Quarter earns time through food, markets, and street rhythm, not a sprint through pins. Image: <a href="{$old_quarter_credit_url}" target="_blank" rel="license noopener">yeowatzup / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$temple_image}" alt="Main gate of the Temple of Literature in Hanoi" loading="lazy" decoding="async"><figcaption>The Temple of Literature is often the cleanest single culture stop for a short first visit. Image: <a href="{$temple_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$thang_long_image}" alt="Imperial Citadel of Thang Long in Hanoi" loading="lazy" decoding="async"><figcaption>Thang Long belongs when capital history is the point of the day, not when it is a rushed add-on. Image: <a href="{$thang_long_credit_url}" target="_blank" rel="license noopener">katiebordner / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$long_bien_image}" alt="Long Bien Bridge in Hanoi" loading="lazy" decoding="async"><figcaption>A two-day visit still needs one slow looking moment before the route moves on. Image: <a href="{$long_bien_credit_url}" target="_blank" rel="license noopener">TheRollo76 / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-hanoi-2-days-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How the evidence is used</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Official destination, heritage, museum, airport, weather, and image-license sources can support the frame. They cannot decide your jet lag, hotel lane, family pace, or whether a famous stop is worth weakening tomorrow's transfer. Those judgments are editorial, dated, and kept visible in the verdict.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-2-days-source-diversity">
<thead><tr><th>Evidence layer</th><th>Use in this guide</th><th>Editorial limit</th></tr></thead>
<tbody>
<tr><td data-label="Evidence layer">Official destination source</td><td data-label="Use in this guide">Frames Hanoi as a northern Vietnam city chapter.</td><td data-label="Editorial limit">Does not tell you whether two days should include a day trip.</td></tr>
<tr><td data-label="Evidence layer">Heritage and museum sources</td><td data-label="Use in this guide">Support Thang Long, Temple of Literature, and Women's Museum as durable choices.</td><td data-label="Editorial limit">Opening rules and temporary closures still need same-week checks.</td></tr>
<tr><td data-label="Evidence layer">Weather and airport sources</td><td data-label="Use in this guide">Keep arrival timing, heat, rain, and next-transfer risk inside the itinerary.</td><td data-label="Editorial limit">They cannot predict your actual queue, app pickup, or energy after a flight.</td></tr>
<tr><td data-label="Evidence layer">Licensed photo records</td><td data-label="Use in this guide">Show the real route jobs behind the two-day sequence.</td><td data-label="Editorial limit">Photos do not prove current crowding, renovation, or access conditions.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-2-days-source-trail-snapshot:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Source trail snapshot</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The main evidence trail is visible here as text so readers can audit the guide without turning the article into a list of outbound links. Full source and image-license records are also stored in the source trail metadata and reviewed on each meaningful update.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-2-days-source-trail-snapshot">
<thead><tr><th>Source checked</th><th>Record used</th><th>Why it matters for two days</th></tr></thead>
<tbody>
<tr><td data-label="Source checked">Vietnam.travel Ha Noi destination page</td><td data-label="Record used">https://vietnam.travel/places-to-go/northern-vietnam/ha-noi</td><td data-label="Why it matters for two days">Frames Hanoi as the northern city chapter before Ninh Binh, the bay, mountains, or Central Vietnam compete for time.</td></tr>
<tr><td data-label="Source checked">National Centre for Hydro-Meteorological Forecasting</td><td data-label="Record used">https://www.nchmf.gov.vn/kttv/en-US/1/index.html</td><td data-label="Why it matters for two days">Keeps rain, heat, cold, and storm checks inside the plan instead of treating walking blocks as fixed.</td></tr>
<tr><td data-label="Source checked">Noi Bai International Airport</td><td data-label="Record used">https://vietnamairport.vn/en/noi-bai-airport</td><td data-label="Why it matters for two days">Turns arrival hour and luggage reality into a pacing decision, not a footnote.</td></tr>
<tr><td data-label="Source checked">UNESCO Thang Long and official Hanoi culture sites</td><td data-label="Record used">https://whc.unesco.org/en/list/1328/ plus hoangthanhthanglong.vn, vanmieu.gov.vn, and baotangphunu.org.vn</td><td data-label="Why it matters for two days">Supports one serious culture choice without pretending every heritage or museum stop fits.</td></tr>
<tr><td data-label="Source checked">Wikimedia Commons image records</td><td data-label="Record used">Hanoi-lac-hoan-kiem.jpg, Cho_Dong_Xuan..., Temple of Literature, Thang Long, and Long_Bien_Bridge.jpg</td><td data-label="Why it matters for two days">Keeps photo proof licensed and tied to route judgment, not decorative filler.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-2-days-sequence:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The best 2-day Hanoi sequence</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-2-days-sequence">
<thead><tr><th>Block</th><th>Plan</th><th>Why it is there</th><th>Do not overload it with</th></tr></thead>
<tbody>
<tr><td data-label="Block">Arrival / first morning</td><td data-label="Plan">Settle near Hoan Kiem, solve phone/cash, do a short orientation walk, eat close to the hotel.</td><td data-label="Why it is there">Confidence beats coverage on the first Hanoi block.</td><td data-label="Do not overload it with">A long museum, cross-city taxi chain, or far food tour after a late flight.</td></tr>
<tr><td data-label="Block">Day 1 afternoon</td><td data-label="Plan">Old Quarter texture, market/cafe pause, or a single nearby story-led stop.</td><td data-label="Why it is there">This is where Hanoi becomes readable instead of noisy.</td><td data-label="Do not overload it with">A second market plus a viral photo detour.</td></tr>
<tr><td data-label="Block">Day 1 evening</td><td data-label="Plan">Food-led evening within easy return distance.</td><td data-label="Why it is there">Food is the highest-value cultural entry for a short stay.</td><td data-label="Do not overload it with">Late drinking before an early pickup.</td></tr>
<tr><td data-label="Block">Day 2 morning</td><td data-label="Plan">Temple of Literature, Thang Long, or Women's Museum by weather and interest.</td><td data-label="Why it is there">One serious culture block gives the city depth.</td><td data-label="Do not overload it with">Two major heritage stops without time to read.</td></tr>
<tr><td data-label="Block">Day 2 afternoon / evening</td><td data-label="Plan">Coffee, lake, light shopping, packing, pickup confirmation, or a short bridge/walk block.</td><td data-label="Why it is there">The final block protects tomorrow's route.</td><td data-label="Do not overload it with">A distant excursion returning late.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-2-days-arrival-pivot:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">If your flight lands late, change Day 1</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A late Noi Bai arrival should make the first evening smaller. Use <a href="/plan/hanoi-airport-to-old-quarter/">Hanoi Airport to Old Quarter</a> before deciding whether the night can hold anything beyond hotel, food, and sleep.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-2-days-arrival-pivot">
<thead><tr><th>Arrival condition</th><th>Use this plan</th><th>Move to Day 2</th><th>Why</th></tr></thead>
<tbody>
<tr><td data-label="Arrival condition">Before mid-afternoon, rested</td><td data-label="Use this plan">Orientation walk, cafe, Old Quarter food evening.</td><td data-label="Move to Day 2">The serious culture block.</td><td data-label="Why">You can spend energy without borrowing from tomorrow.</td></tr>
<tr><td data-label="Arrival condition">Evening, long-haul, checked luggage</td><td data-label="Use this plan">Hotel pickup or verified ride, nearby dinner, early night.</td><td data-label="Move to Day 2">Lake walk and culture stop.</td><td data-label="Why">A calm first transfer is worth more than one extra stop.</td></tr>
<tr><td data-label="Arrival condition">Delayed flight or storm/rain pressure</td><td data-label="Use this plan">Recovery-only night and indoor Day 2 option.</td><td data-label="Move to Day 2">Food route and museum/cafe pivot.</td><td data-label="Why">The itinerary stays useful even when the first evening collapses.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-2-days-stay-area-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where to stay for this exact itinerary</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Read <a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a> for the full base-selection guide. For only two days, the hotel area must reduce friction more than it adds novelty.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-2-days-stay-area-fit">
<thead><tr><th>Area</th><th>Works best when</th><th>Two-day risk</th><th>Verdict</th></tr></thead>
<tbody>
<tr><td data-label="Area">Hoan Kiem / Old Quarter edge</td><td data-label="Works best when">You want first-time walking, food, cafes, and easy pickup.</td><td data-label="Two-day risk">Noise by exact block.</td><td data-label="Verdict">Best default for most two-day first visits.</td></tr>
<tr><td data-label="Area">French Quarter / south Hoan Kiem</td><td data-label="Works best when">You want calmer premium central access and better sleep.</td><td data-label="Two-day risk">A little less street density outside the door.</td><td data-label="Verdict">Best comfort upgrade without leaving the center.</td></tr>
<tr><td data-label="Area">Ba Dinh</td><td data-label="Works best when">The culture block is civic/history-heavy.</td><td data-label="Two-day risk">Less spontaneous food energy.</td><td data-label="Verdict">Useful for focused history travelers, not the default short-stay answer.</td></tr>
<tr><td data-label="Area">West Lake</td><td data-label="Works best when">You have family, apartment, business, or longer-stay needs.</td><td data-label="Two-day risk">Too many rides for classic first Hanoi.</td><td data-label="Verdict">Usually better after the first visit or for longer stays.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-2-days-day-one-plan:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Day 1: orientation, food, and not doing too much</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The first day should teach the city. Start with Hoan Kiem, step into the Old Quarter when energy is good, pause before the streets become noise, and let dinner do cultural work.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-2-days-day-one-plan">
<thead><tr><th>Time block</th><th>Best plan</th><th>Upgrade</th><th>Fallback</th></tr></thead>
<tbody>
<tr><td data-label="Time block">Morning or arrival reset</td><td data-label="Best plan">Hoan Kiem loop, coffee, hotel-neighborhood orientation.</td><td data-label="Upgrade">Guide for first-hour context if you dislike self-navigation.</td><td data-label="Fallback">Sleep and make the lake the afternoon reset.</td></tr>
<tr><td data-label="Time block">Midday</td><td data-label="Best plan">Old Quarter lanes, Dong Xuan/market texture, short shopping list.</td><td data-label="Upgrade">Food-focused local guide instead of a landmark sprint.</td><td data-label="Fallback">Cafe/museum if heat or rain makes wandering weak.</td></tr>
<tr><td data-label="Time block">Evening</td><td data-label="Best plan">Food-led walk close to the hotel and an easy return.</td><td data-label="Upgrade">Small-group food tour when it lowers ordering friction.</td><td data-label="Fallback">One strong nearby meal and early sleep before Day 2.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-2-days-day-two-plan:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Day 2: one culture block, one softer block, then protect the exit</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Day 2 is where two-day Hanoi either becomes memorable or turns into a checklist. Choose one serious culture block and then leave space for food, cafe, lake, packing, or pickup confirmation.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-2-days-day-two-plan">
<thead><tr><th>Culture choice</th><th>Choose it when</th><th>Pair it with</th><th>Skip if</th></tr></thead>
<tbody>
<tr><td data-label="Culture choice">Temple of Literature</td><td data-label="Choose it when">You want the cleanest first cultural stop.</td><td data-label="Pair it with">Cafe, light lunch, and a slower Old Quarter or lake return.</td><td data-label="Skip if">You need deeper political/imperial history.</td></tr>
<tr><td data-label="Culture choice">Thang Long Imperial Citadel</td><td data-label="Choose it when">UNESCO and capital history matter to the trip.</td><td data-label="Pair it with">A guide or time to read, then a calmer afternoon.</td><td data-label="Skip if">You would only rush through it for the name.</td></tr>
<tr><td data-label="Culture choice">Vietnamese Women's Museum</td><td data-label="Choose it when">Rain, heat, family context, social history, or indoor value matters.</td><td data-label="Pair it with">French Quarter/Hoan Kiem food and coffee.</td><td data-label="Skip if">You strongly prefer outdoor heritage and weather is kind.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-2-days-weather-pacing-pivots:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Weather and pacing pivots</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-2-days-weather-pacing-pivots">
<thead><tr><th>If Hanoi feels...</th><th>Move value earlier</th><th>Move indoors or later</th><th>Protect</th></tr></thead>
<tbody>
<tr><td data-label="If Hanoi feels...">Hot</td><td data-label="Move value earlier">Lake walk, market texture, taxi-supported culture stop.</td><td data-label="Move indoors or later">Museum, cafe, food evening.</td><td data-label="Protect">Midday shade and attention span.</td></tr>
<tr><td data-label="If Hanoi feels...">Rainy</td><td data-label="Move value earlier">Nearby breakfast and short covered movement.</td><td data-label="Move indoors or later">Women's Museum, coffee, food route.</td><td data-label="Protect">Dry shoes and tomorrow's transfer mood.</td></tr>
<tr><td data-label="If Hanoi feels...">Overcrowded</td><td data-label="Move value earlier">Earlier Hoan Kiem and Temple timing.</td><td data-label="Move indoors or later">Cafe/rest block, quieter French Quarter edge.</td><td data-label="Protect">One unhurried meal instead of another crowded stop.</td></tr>
<tr><td data-label="If Hanoi feels...">Jet-lagged</td><td data-label="Move value earlier">One short walk and one useful meal.</td><td data-label="Move indoors or later">The serious culture stop.</td><td data-label="Protect">Sleep before a pickup day.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-2-days-day-trip-pressure-test:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Should a day trip replace Day 2?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use <a href="/destinations/best-day-trips-from-hanoi/">Best Day Trips from Hanoi</a> when the outside day is a real decision. In a two-day Hanoi window, a day trip should replace a city chapter only when it solves the route better than Hanoi itself.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-2-days-day-trip-pressure-test">
<thead><tr><th>Outside choice</th><th>Keep it only when</th><th>Why it can hurt two days</th><th>Better move</th></tr></thead>
<tbody>
<tr><td data-label="Outside choice">Ninh Binh day trip</td><td data-label="Keep it only when">This is the only countryside slot and tomorrow is not a fragile transfer.</td><td data-label="Why it can hurt two days">It can make Hanoi itself feel like hotel logistics.</td><td data-label="Better move">Overnight Ninh Binh on a longer route.</td></tr>
<tr><td data-label="Outside choice">Ha Long / Lan Ha day preview</td><td data-label="Keep it only when">You accept a long transport day for a limited bay sample.</td><td data-label="Why it can hurt two days">Same-day bay movement is heavy and often weaker than an overnight cruise.</td><td data-label="Better move">Protect a proper bay chapter or skip.</td></tr>
<tr><td data-label="Outside choice">Craft village or short guided layer</td><td data-label="Keep it only when">It returns early and gives context without eating the city.</td><td data-label="Why it can hurt two days">Even short extras can fragment meals and rest.</td><td data-label="Better move">Use only after the core city blocks are protected.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-2-days-budget-comfort:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where to spend more in a two-day Hanoi plan</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-2-days-budget-comfort">
<thead><tr><th>Decision</th><th>Budget version</th><th>Worth upgrading when</th><th>Why</th></tr></thead>
<tbody>
<tr><td data-label="Decision">Airport transfer</td><td data-label="Budget version">Airport bus if daylight, light luggage, and central walk are simple.</td><td data-label="Worth upgrading when">Late arrival, family, checked bags, or low phone confidence.</td><td data-label="Why">It protects the first night and Day 1.</td></tr>
<tr><td data-label="Decision">Hotel area</td><td data-label="Budget version">Cheaper room farther from the core.</td><td data-label="Worth upgrading when">You only have two days and pickup/walking time matters.</td><td data-label="Why">Location can buy more value than one extra paid attraction.</td></tr>
<tr><td data-label="Decision">Food or context guide</td><td data-label="Budget version">Self-led food crawl.</td><td data-label="Worth upgrading when">You want ordering confidence or a short, high-value first evening.</td><td data-label="Why">A good guide can reduce friction without adding another landmark.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-2-days-mistakes-skip:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mistakes to avoid with two days in Hanoi</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-2-days-mistakes-skip">
<thead><tr><th>Mistake</th><th>Why it hurts</th><th>Correction</th></tr></thead>
<tbody>
<tr><td data-label="Mistake">Treating two days as a full attraction inventory</td><td data-label="Why it hurts">The city becomes tiring before it becomes meaningful.</td><td data-label="Correction">Choose one culture block and one food-led evening.</td></tr>
<tr><td data-label="Mistake">Booking a day trip because Hanoi has two nights</td><td data-label="Why it hurts">Two nights can still mean only one useful city day after arrival.</td><td data-label="Correction">Count the real arrival hour and next departure before committing.</td></tr>
<tr><td data-label="Mistake">Staying in the wrong area for a small saving</td><td data-label="Why it hurts">Taxi and walking friction can consume the exact time you are trying to save.</td><td data-label="Correction">Choose the base by first-night recovery and pickup clarity.</td></tr>
<tr><td data-label="Mistake">Ignoring weather until the morning itself</td><td data-label="Why it hurts">Rain or heat can break a walking-heavy short stay.</td><td data-label="Correction">Hold one indoor culture option and one cafe/food pivot.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-2-days-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before you lock it</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-2-days-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-2-days-live-checks">
<li>Check the Hanoi Travel Guide and Where to Stay in Hanoi before the hotel area decides your first walk, food evening, and pickup morning.</li>
<li>Check Hanoi Airport to Old Quarter before assuming arrival night can hold sightseeing.</li>
<li>Check official attraction pages for current opening rules before making Temple of Literature, Thang Long, or the Women's Museum the anchor.</li>
<li>Check Best Time to Visit Vietnam and same-week weather before relying on a walking-heavy day.</li>
<li>Check Best Day Trips from Hanoi only after the two city blocks are protected.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-2-days-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hanoi in 2 Days FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-hanoi-2-days-faq">
<details><summary>Is two days enough in Hanoi?</summary><p>Two days is enough for a strong first Hanoi chapter if you stay central, keep the plan selective, and do not add a heavy day trip by default. It is not enough for every museum, heritage site, market, food tour, and northern excursion.</p></details>
<details><summary>What should I do first in Hanoi?</summary><p>Most visitors should start with hotel check-in, Hoan Kiem orientation, an Old Quarter or food-led block, and a realistic sleep window. The first win is confidence, not coverage.</p></details>
<details><summary>Should I visit Ninh Binh or Ha Long Bay if I only have two days in Hanoi?</summary><p>Usually no, unless that outside day is the main reason you are in the north and Hanoi itself can shrink without regret. A day trip inside a two-day stay often makes the city feel like logistics rather than a chapter.</p></details>
<details><summary>Which culture stop is best for a short Hanoi stay?</summary><p>Choose Temple of Literature for the cleanest first cultural stop, Thang Long when UNESCO and capital history matter, or the Vietnamese Women's Museum when weather, family context, or indoor story value matters more.</p></details>
<details><summary>Where should I stay for two days in Hanoi?</summary><p>Hoan Kiem, the Old Quarter edge, or a calmer French Quarter pocket are the best defaults. West Lake and Ba Dinh can work for specific comfort, family, business, or history needs, but they are less efficient for a classic first two-day visit.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>, then choose the base with <a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a> and arrival plan with <a href="/plan/hanoi-airport-to-old-quarter/">Hanoi Airport to Old Quarter</a>. Use <a href="/destinations/best-things-to-do-in-hanoi/">Best Things to Do in Hanoi</a> for attraction selection and <a href="/destinations/best-day-trips-from-hanoi/">Best Day Trips from Hanoi</a> only when the second day might leave the city.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-hanoi-2-days-hero:v1',
    'concierge verdict' => 'vg-hanoi-2-days-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-hanoi-2-days-at-a-glance:v1',
    'photo grid' => 'vg-hanoi-2-days-photo-grid:v1',
    'source diversity' => 'vg-hanoi-2-days-source-diversity:v1',
    'source trail snapshot' => 'vg-hanoi-2-days-source-trail-snapshot:v1',
    'sequence' => 'vg-hanoi-2-days-sequence:v1',
    'arrival pivot' => 'vg-hanoi-2-days-arrival-pivot:v1',
    'stay area fit' => 'vg-hanoi-2-days-stay-area-fit:v1',
    'day one plan' => 'vg-hanoi-2-days-day-one-plan:v1',
    'day two plan' => 'vg-hanoi-2-days-day-two-plan:v1',
    'weather pivots' => 'vg-hanoi-2-days-weather-pacing-pivots:v1',
    'day trip pressure' => 'vg-hanoi-2-days-day-trip-pressure-test:v1',
    'budget comfort' => 'vg-hanoi-2-days-budget-comfort:v1',
    'mistakes skip' => 'vg-hanoi-2-days-mistakes-skip:v1',
    'live checks' => 'vg-hanoi-2-days-live-checks:v1',
    'FAQ' => 'vg-hanoi-2-days-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_hanoi_two_days_ops_assert_required_content_markers($content, $required_content_markers);
vg_hanoi_two_days_ops_assert_internal_page_links_are_published('Hanoi in 2 Days', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Hanoi in 2 Days',
    'post_name'      => 'hanoi-in-2-days',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_hanoi_two_days_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led two-day Hanoi itinerary for international travelers balancing arrival recovery, food, culture, weather pivots, hotel area, day-trip temptation, and next-transfer energy.',
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
    vg_hanoi_two_days_ops_fail('Could not publish Hanoi in 2 Days: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_hanoi_two_days_ops_fail('Could not publish Hanoi in 2 Days: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Hanoi in 2 Days: A Calm First-Time Itinerary');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Hanoi in 2 Days itinerary: arrival recovery, Hoan Kiem, Old Quarter food, culture stops, weather pivots, where to stay, and day-trip skip logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Hanoi in 2 Days');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Build a tight two-day Hanoi stay without exhausting the next transfer or duplicating the broader Hanoi Travel Guide and Best Things to Do in Hanoi pages.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published Hanoi in 2 Days as an itinerary-support page with a concierge verdict, 48-hour sequence, arrival pivot, stay-area fit, day-one and day-two plans, weather pivots, day-trip pressure test, budget/comfort choices, mistakes/skip logic, source trail, update log, homepage and Itineraries hub support, and related-route mesh.');
$sources_checked = implode(
    "\n",
    [
        "Vietnam.travel - Ha Noi destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-noi - checked {$review_date}",
        "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked {$review_date}",
        "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}",
        "National Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked {$review_date}",
        "Noi Bai International Airport - https://vietnamairport.vn/en/noi-bai-airport - checked {$review_date}",
        "UNESCO World Heritage Centre - Central Sector of the Imperial Citadel of Thang Long - Ha Noi - https://whc.unesco.org/en/list/1328/ - checked {$review_date}",
        "Imperial Citadel of Thang Long official site - https://hoangthanhthanglong.vn/en/ - checked {$review_date}",
        "Temple of Literature official site - https://vanmieu.gov.vn/en/ - checked {$review_date}",
        "Vietnamese Women's Museum official site - https://baotangphunu.org.vn/en - checked {$review_date}",
        "Wikimedia Commons image record - Hanoi Hoan Kiem Lake - https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Cho Dong Xuan, Old Quarter, Hanoi - https://commons.wikimedia.org/wiki/File:Cho_Dong_Xuan,_Old_Quarter,_Hanoi,_Vietnam_(5245806573).jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Main gate of the Temple of Literature, Hanoi - https://commons.wikimedia.org/wiki/File:Main_gate_of_the_Temple_of_Literature,_Hanoi,_Vietnam,_20240123_0929_3068.jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Central Sector of the Imperial Citadel of Thang Long - Hanoi - https://commons.wikimedia.org/wiki/File:Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Long Bien Bridge - https://commons.wikimedia.org/wiki/File:Long_Bien_Bridge.jpg - license checked {$review_date}",
    ]
);
update_post_meta($page_id, 'vg_eeat_sources_checked', $sources_checked);
update_post_meta($page_id, 'vg_eeat_field_note', 'Two days in Hanoi should solve orientation, food confidence, one meaningful culture block, and next-transfer readiness. It should not become a compressed list of every Hanoi attraction.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Hanoi in 2 Days built as a short-stay itinerary support page, not a generic attraction list or duplicate Hanoi pillar.\nConcierge verdict names the exact two-day sequence and what to cut first.\nFast-answer table separates late arrival, two full days, day-trip temptation, and family/weather pressure.\nPhoto proof uses licensed Hanoi images to explain orientation, street rhythm, culture, heritage depth, and slow exit pacing.\nSource-diversity module states what official destination, heritage, museum, weather, airport, and image-license sources can and cannot prove.\n48-hour sequence protects food, culture, weather pivots, and next-transfer energy.\nArrival pivot ties the itinerary to Hanoi Airport to Old Quarter rather than assuming every first night is equal.\nStay-area, Day 1, Day 2, weather/pacing, day-trip pressure, budget/comfort, mistakes, FAQ, source trail, update log, homepage, hub, and related routes make the page durable and anti-spam.");
$related_routes = "Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use this first if you still need Hanoi's route role, night count, and northern launch logic.\nBest Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Use this after the two-day shape is clear and the attraction shortlist needs more detail.\nWhere to Stay in Hanoi | /destinations/where-to-stay-in-hanoi/ | Choose the base that makes the first walk, food evening, sleep, and pickup morning easier.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Keep arrival night realistic before deciding whether Day 1 can hold more than recovery.\nOld Quarter vs French Quarter vs West Lake | /compare/old-quarter-vs-french-quarter-vs-west-lake/ | Compare sleep zone, walking radius, and calmer central access before booking a short stay.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Use this only if the second day might leave Hanoi for Ninh Binh, the bay, villages, or a stay-in-city fallback.\n7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Put the two-day Hanoi plan inside a one-week north-focused route.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Hanoi, Ninh Binh, the bay, and Central Vietnam can all fit without rushing.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use this if two Hanoi days need more margin around northern and central movement.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use a longer route to turn Hanoi into a real chapter rather than a staging stop.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check heat, rain, cold, and northern weather before relying on long walking blocks.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Count airport transfers, early pickups, trains, cruise transfers, and onward flights before scheduling Day 2.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Decide where upgrades buy time: hotel area, arrival transfer, food guide, or private movement.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, ride-hailing, hotel contact, and pickup coordination reliable during the short stay.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Prepare arrival cash, cards, and backup payment before the first food or transfer decision.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair taxi/app matching, night movement, phone handling, and market/crowd habits with a calm first visit.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check road transfer, weather, trip interruption, and medical backup posture before a tight route.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.";
vg_hanoi_two_days_ops_assert_related_route_meta_links_are_published('Hanoi in 2 Days related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Hoan Kiem image: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0. Body images: Cho Dong Xuan, Old Quarter, Hanoi by yeowatzup, CC BY 2.0; Temple of Literature by Jakub Halun, CC BY 4.0; Central Sector of the Imperial Citadel of Thang Long by katiebordner, CC BY 2.0; Long Bien Bridge by TheRollo76, CC BY-SA 4.0.');
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
        vg_hanoi_two_days_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_hanoi_two_days_ops_fail('Hanoi in 2 Days was updated but is not published.');
}

vg_hanoi_two_days_ops_refresh_itineraries_hub();
vg_hanoi_two_days_ops_refresh_homepage_route_spine();
vg_hanoi_two_days_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_hanoi_two_days_ops_log("Published Hanoi in 2 Days: {$page_id} {$updated_permalink}");

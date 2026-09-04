<?php
/**
 * Publish the Mui Ne vs Nha Trang comparison guide.
 *
 * Run from the WordPress root with:
 * VG_FORCE_MUI_NE_NHA_TRANG_COMPARISON_REPUBLISH=1 wp eval-file ops/apply-mui-ne-vs-nha-trang-comparison-guide.php --allow-root
 *
 * Repair only hub/related-route side effects with:
 * VG_REPAIR_MUI_NE_NHA_TRANG_COMPARISON_LINKS=1 wp eval-file ops/apply-mui-ne-vs-nha-trang-comparison-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_mn_nt_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_mn_nt_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_mn_nt_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_MUI_NE_NHA_TRANG_COMPARISON_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_mn_nt_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_MUI_NE_NHA_TRANG_COMPARISON_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_mn_nt_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_mn_nt_ops_fail('Could not resolve a valid WordPress author for the Mui Ne vs Nha Trang guide.');
}

function vg_mn_nt_ops_internal_path_from_href(string $href): ?string
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

function vg_mn_nt_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_mn_nt_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    $failures = [];

    foreach (array_keys($paths) as $path) {
        if ($path === 'home') {
            $front_page_id = (int) get_option('page_on_front');
            $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
        } else {
            $page = get_page_by_path($path, OBJECT, 'page');
        }

        if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
            $failures[] = "{$label} links to unpublished page path: /{$path}/";
        }
    }

    if ($failures !== []) {
        vg_mn_nt_ops_fail(implode(PHP_EOL, $failures));
    }

    vg_mn_nt_ops_log("Validated {$label} internal page links are published.");
}

function vg_mn_nt_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_mn_nt_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_mn_nt_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_mn_nt_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_mn_nt_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_mn_nt_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_mn_nt_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_mn_nt_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_mn_nt_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_mn_nt_ops_log("Validated {$label} related-route links are published.");
}

function vg_mn_nt_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_mn_nt_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_mn_nt_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

function vg_mn_nt_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
{
    $marked_block = $marker . "\n" . $block;

    if (str_contains($page->post_content, $marked_block)) {
        vg_mn_nt_ops_log("Skipped {$label}: current block already present.");
        return;
    }

    if (str_contains($page->post_content, $marker)) {
        $updated_content = preg_replace(
            '/' . preg_quote($marker, '/') . '\R<!-- wp:group\b.*?<!-- \/wp:group -->/s',
            $marked_block,
            $page->post_content,
            1,
            $replacement_count
        );

        if (! is_string($updated_content) || $replacement_count !== 1) {
            vg_mn_nt_ops_fail("Could not confidently refresh {$label}.");
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
        vg_mn_nt_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_mn_nt_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_mn_nt_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_mn_nt_ops_refresh_compare_hub(): void
{
    $hub = get_page_by_path('compare', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_mn_nt_ops_log('Skipped Compare hub refresh: compare page was not found or is not published.');
        return;
    }

    if (! vg_mn_nt_ops_published_page_exists('compare/mui-ne-vs-nha-trang')) {
        vg_mn_nt_ops_log('Skipped Compare hub refresh: Mui Ne vs Nha Trang guide is not published.');
        return;
    }

    $marker = '<!-- vg-mui-ne-nha-trang-compare-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Compare wind-sport coast with city beach before choosing Nha Trang by default</h3><p>The <a href="/compare/mui-ne-vs-nha-trang/">Mui Ne vs Nha Trang</a> guide helps travelers decide whether the route needs a sport-first Phan Thiet/Mui Ne beach stop, a more urban Nha Trang base, or no south-central beach detour at all.</p></div>
<!-- /wp:group -->
HTML;

    vg_mn_nt_ops_assert_internal_page_links_are_published('Compare hub Mui Ne/Nha Trang note', $block);
    vg_mn_nt_ops_upsert_marked_group($hub, 'Compare hub Mui Ne/Nha Trang note', $marker, $block);
}

function vg_mn_nt_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_mn_nt_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_mn_nt_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_mn_nt_ops_log("Skipped related-route refresh for {$label}: Mui Ne vs Nha Trang line is already current.");
        return;
    }

    $next = implode("\n", $next_lines);
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_mn_nt_ops_log("Upserted Mui Ne vs Nha Trang related route to {$label}: {$page->ID}");
}

function vg_mn_nt_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Mui Ne vs Nha Trang | /compare/mui-ne-vs-nha-trang/ | Choose wind-sport Phan Thiet/Mui Ne beach time or active Nha Trang city-beach routing before booking hotels, transfers, boat days, or a south-central coast detour.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
            'destinations/nha-trang-travel-guide' => 'Nha Trang Travel Guide',
            'compare/phu-quoc-vs-nha-trang' => 'Phu Quoc vs Nha Trang guide',
            'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM/eSIM in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ] as $path => $label
    ) {
        vg_mn_nt_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('compare', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_mn_nt_ops_fail('Published parent page not found: compare');
}

$page = get_page_by_path('compare/mui-ne-vs-nha-trang', OBJECT, 'page');

if (vg_mn_nt_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_mn_nt_ops_fail('Repair mode requires the Mui Ne vs Nha Trang guide to already be published.');
    }

    vg_mn_nt_ops_refresh_compare_hub();
    vg_mn_nt_ops_refresh_inbound_related_routes();
    vg_mn_nt_ops_log("Repaired Mui Ne vs Nha Trang side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_mn_nt_ops_force_republish_enabled()) {
    vg_mn_nt_ops_fail('Mui Ne vs Nha Trang guide is not a draft. Set VG_FORCE_MUI_NE_NHA_TRANG_COMPARISON_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_mn_nt_ops_log("Preflight Mui Ne vs Nha Trang: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_mn_nt_ops_log('Preflight Mui Ne vs Nha Trang: no existing page found; creating a child page under /compare/.');
}

$mui_ne_kitesurf_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/2d/Vietnam%2C_Mui_Ne_beach%2C_Kitesurfing_on_the_beach.jpg/1920px-Vietnam%2C_Mui_Ne_beach%2C_Kitesurfing_on_the_beach.jpg');
$mui_ne_kitesurf_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Mui_Ne_beach,_Kitesurfing_on_the_beach.jpg');
$mui_ne_dunes_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/e/ec/Vietnam%2C_Mui_Ne_sand_dunes.jpg/1280px-Vietnam%2C_Mui_Ne_sand_dunes.jpg');
$mui_ne_dunes_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Mui_Ne_sand_dunes.jpg');
$mui_ne_fairy_stream_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6d/Mui_Ne_Fairy_Stream.jpg/1280px-Mui_Ne_Fairy_Stream.jpg');
$mui_ne_fairy_stream_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Mui_Ne_Fairy_Stream.jpg');
$mui_ne_fishing_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/56/Mui_Ne_fishing_village.jpg/1280px-Mui_Ne_fishing_village.jpg');
$mui_ne_fishing_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Mui_Ne_fishing_village.jpg');
$nha_trang_beach_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Nha_Trang_Beach_3.jpg/1920px-Nha_Trang_Beach_3.jpg');
$nha_trang_beach_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Nha_Trang_Beach_3.jpg');
$nha_trang_coast_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Panorama_of_V%E1%BB%8Bnh_Nha_Trang_coastline.jpg/1920px-Panorama_of_V%E1%BB%8Bnh_Nha_Trang_coastline.jpg');
$nha_trang_coast_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Panorama_of_V%E1%BB%8Bnh_Nha_Trang_coastline.jpg');

$content = <<<HTML
<!-- vg-mui-ne-nha-trang-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$mui_ne_kitesurf_image}","dimRatio":55,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Kitesurfing on Mui Ne beach in Vietnam for a Mui Ne vs Nha Trang comparison guide" src="{$mui_ne_kitesurf_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed comparison guide - Updated July 20, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Mui Ne vs Nha Trang</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Mui Ne and Nha Trang both sit in Vietnam's beach-planning conversation, but they answer different questions. Mui Ne is the wind-sport, dunes, fishing-village, and open-sand coast choice. Nha Trang is the active city-beach base with Cam Ranh access, more restaurants, more hotels, and easier day-to-day movement.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: this is not a prettier-beach ranking. It is a decision about whether the route needs a sport-first Phan Thiet/Mui Ne coast stop, a more urban Nha Trang beach base, or fewer south-central coast nights.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$mui_ne_kitesurf_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-mui-ne-nha-trang-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-mui-ne-nha-trang-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-mui-ne-nha-trang-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Choose Mui Ne when wind sports, dunes, a road/rail-friendly coastal reset from Ho Chi Minh City, or a sport-first beach mood is the point. Choose Nha Trang when you want a fuller city-beach base with airport access, seafood, hotel range, cultural stops, and optional bay or boat days.</strong> If the beach does not solve a route problem, do not add either just because south-central Vietnam looks empty on the map.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-mui-ne-nha-trang-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-mui-ne-nha-trang-shortlist">
<li><strong>Best wind-sport answer:</strong> Mui Ne, when kitesurfing, open sand, and live wind/operator checks are central to the trip.</li>
<li><strong>Best active city-beach answer:</strong> Nha Trang, when the route needs restaurants, taxis, hotel choice, Cam Ranh access, and culture close to the beach.</li>
<li><strong>Best south-from-Ho-Chi-Minh-City logic:</strong> Mui Ne can work when the coast is a deliberate road or rail chapter, not a rushed detour.</li>
<li><strong>Best route discipline:</strong> pick one beach job, protect transfer buffers, and skip both if the beach only decorates an already full itinerary.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-mui-ne-nha-trang-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-mui-ne-nha-trang-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-mui-ne-nha-trang-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fast useful answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-mui-ne-nha-trang-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Which is better for most first-time visitors?</strong></td><td data-label="Answer">Nha Trang, if the traveler wants a fuller city-beach stop. Mui Ne, only when the route specifically wants wind sports, dunes, or a Phan Thiet coast reset.</td></tr>
<tr><td data-label="Question"><strong>Which is better for beach sports?</strong></td><td data-label="Answer">Mui Ne, if wind and operator setup match the sport. Treat it as a live-condition decision, not a generic beach promise.</td></tr>
<tr><td data-label="Question"><strong>Which is easier for logistics?</strong></td><td data-label="Answer">Nha Trang is easier by air because Cam Ranh serves the area. Mui Ne can be simpler from Ho Chi Minh City only when road or rail timing fits the route.</td></tr>
<tr><td data-label="Question"><strong>Which has more city texture?</strong></td><td data-label="Answer">Nha Trang. Mui Ne is more about coast, dunes, fishing-village edges, sport setup, and resort-strip decisions.</td></tr>
<tr><td data-label="Question"><strong>Main mistake?</strong></td><td data-label="Answer">Comparing a sport-first coast with a city beach as if both were the same kind of resort destination.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-mui-ne-nha-trang-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what changes between the choices</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The photos are used as route evidence. Mui Ne's value is movement across wind, sand, fishing-village scenes, and coastal road time. Nha Trang's value is a denser city-beach base with services and day choices close together.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-mui-ne-nha-trang-photo-grid" aria-label="Mui Ne and Nha Trang comparison photography">
<figure class="vg-guide-photo"><img src="{$mui_ne_kitesurf_image}" alt="Kitesurfing at Mui Ne beach in Vietnam" loading="lazy" decoding="async"><figcaption>Mui Ne makes the most sense when wind sports are part of the brief. Image: <a href="{$mui_ne_kitesurf_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mui_ne_dunes_image}" alt="Sand dunes near Mui Ne in Vietnam" loading="lazy" decoding="async"><figcaption>The dunes turn Mui Ne into more than a beach strip, but they also make it an outing-based coast. Image: <a href="{$mui_ne_dunes_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mui_ne_fairy_stream_image}" alt="Fairy Stream in Mui Ne, Vietnam" loading="lazy" decoding="async"><figcaption>Fairy Stream supports Mui Ne as a soft landscape stop, not only a resort night. Image: <a href="{$mui_ne_fairy_stream_credit_url}" target="_blank" rel="license noopener">Ddubbert / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mui_ne_fishing_image}" alt="Mui Ne fishing village on the coast" loading="lazy" decoding="async"><figcaption>Fishing-village texture is part of the coast's appeal, but stay area still decides daily convenience. Image: <a href="{$mui_ne_fishing_credit_url}" target="_blank" rel="license noopener">Michel Coutty / Public domain</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$nha_trang_beach_image}" alt="Nha Trang Beach with city backdrop in Vietnam" loading="lazy" decoding="async"><figcaption>Nha Trang is the stronger answer when beach days should stay urban and active. Image: <a href="{$nha_trang_beach_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$nha_trang_coast_image}" alt="Panorama of the Nha Trang coastline and bay" loading="lazy" decoding="async"><figcaption>Nha Trang's route value is the coastal-city spread, not only the sand. Image: <a href="{$nha_trang_coast_credit_url}" target="_blank" rel="license noopener">Tony24986 / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-mui-ne-nha-trang-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Source diversity: what each source can actually prove</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This comparison separates durable destination judgment from booking-time checks. Official tourism pages can frame the places; weather, rail, airport, entry, and image-license sources answer narrower questions.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-mui-ne-nha-trang-source-diversity">
<thead><tr><th>Source job</th><th>Primary use</th><th>How to read it</th></tr></thead>
<tbody>
<tr><td data-label="Source job">Official destination framing</td><td data-label="Primary use"><a href="https://vietnam.travel/places-to-go/southern-vietnam/binh-thuan" target="_blank" rel="noopener">Vietnam.travel Binh Thuan</a>, <a href="https://vietnam.travel/node/708" target="_blank" rel="noopener">Vietnam.travel Mui Ne</a>, and <a href="https://vietnam.travel/places-to-go/central-vietnam/nha-trang" target="_blank" rel="noopener">Vietnam.travel Nha Trang</a>.</td><td data-label="How to read it">Good for evergreen orientation and major experience themes; not enough to decide route value alone.</td></tr>
<tr><td data-label="Source job">Weather and wind posture</td><td data-label="Primary use">Vietnam.travel weather/climate plus the National Centre for Hydro-Meteorological Forecasting.</td><td data-label="How to read it">Use seasonal framing early, then recheck live wind, rain, sea, and operator conditions close to travel.</td></tr>
<tr><td data-label="Source job">Transport reality</td><td data-label="Primary use">Vietnam.travel transport, Vietnam Railways schedule checks, and Cam Ranh airport for Nha Trang access.</td><td data-label="How to read it">Useful for route logic; exact trains, cars, flights, luggage, and arrival buffers are booking-time checks.</td></tr>
<tr><td data-label="Source job">Entry and connection risk</td><td data-label="Primary use">Official Vietnam e-visa portal and the site's e-visa guide.</td><td data-label="How to read it">Do not build domestic coast connections before entry dates, passport data, and international arrival margins are clean.</td></tr>
<tr><td data-label="Source job">Image proof</td><td data-label="Primary use">Wikimedia Commons image records for Mui Ne kitesurfing, dunes, Fairy Stream, fishing village, Nha Trang Beach, and the Nha Trang coastline.</td><td data-label="How to read it">Images show place character. They do not prove current wind, beach quality, crowding, water safety, or service conditions.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-mui-ne-nha-trang-decision-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mui Ne vs Nha Trang decision matrix</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this as a buying filter before booking the coast. The better choice is the one that improves the route after transfer time, weather risk, and daily rhythm are counted.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-mui-ne-nha-trang-decision-matrix">
<thead><tr><th>Decision</th><th>Choose Mui Ne when...</th><th>Choose Nha Trang when...</th><th>Skip both when...</th></tr></thead>
<tbody>
<tr><td data-label="Decision">Trip job</td><td data-label="Choose Mui Ne when...">The trip needs wind sports, dunes, coastal road time, or a quieter open-sand mood.</td><td data-label="Choose Nha Trang when...">The trip needs a city beach with restaurants, hotels, services, culture stops, and optional boat days.</td><td data-label="Skip both when...">The beach is only there because the route looks too inland.</td></tr>
<tr><td data-label="Decision">Route shape</td><td data-label="Choose Mui Ne when...">Ho Chi Minh City and a south-central road or rail chapter already make Phan Thiet/Mui Ne logical.</td><td data-label="Choose Nha Trang when...">Cam Ranh access or a south-central coast base simplifies the route more than a road-heavy detour.</td><td data-label="Skip both when...">A short route already has Hanoi, one northern landscape, central Vietnam, and no slack.</td></tr>
<tr><td data-label="Decision">Traveler mood</td><td data-label="Choose Mui Ne when...">You want open coast, active wind, sand-dune outings, a slower resort strip, and fewer city decisions.</td><td data-label="Choose Nha Trang when...">You want food, nightlife, taxis, city services, beach hotels, and more daily pivots.</td><td data-label="Skip both when...">You actually want heritage, mountains, Mekong, or an island resort finish.</td></tr>
<tr><td data-label="Decision">Premium value</td><td data-label="Choose Mui Ne when...">Premium means space, sport setup, quiet resort choice, and a coast that feels removed from big-city texture.</td><td data-label="Choose Nha Trang when...">Premium means better room choice, good food access, city convenience, and optional activity without isolation.</td><td data-label="Skip both when...">The premium budget would protect a better guide, cruise, hotel, or route margin elsewhere.</td></tr>
<tr><td data-label="Decision">Failure mode</td><td data-label="Choose Mui Ne when...">You have checked transfer time, wind season, operator quality, and stay area.</td><td data-label="Choose Nha Trang when...">You have checked Cam Ranh transfer, beach segment, boat-day expectations, and hotel location.</td><td data-label="Skip both when...">The plan relies on vague beach imagery and no cancellation flexibility.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-mui-ne-nha-trang-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route fit: where each beach belongs</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Mui Ne competes with south-from-Ho-Chi-Minh-City time, central-coast stops, and any beach that needs sport conditions. Nha Trang competes with Da Nang, Hoi An, Quy Nhon, Phu Quoc, and the question of whether the route needs a city beach at all.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-mui-ne-nha-trang-route-fit">
<thead><tr><th>Route context</th><th>Better move</th><th>What to protect</th><th>Related guide</th></tr></thead>
<tbody>
<tr><td data-label="Route context">10 days in Vietnam</td><td data-label="Better move">Usually skip both unless the trip is intentionally coast-led.</td><td data-label="What to protect">North, one central chapter, and calm departure logic.</td><td data-label="Related guide"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Route context">14 days in Vietnam</td><td data-label="Better move">Choose one beach job: Mui Ne for sport/coast reset or Nha Trang for active city beach.</td><td data-label="What to protect">Avoid adding both unless the whole route is south-central coast focused.</td><td data-label="Related guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Route context">21 days in Vietnam</td><td data-label="Better move">Use longer timing to add a deliberate beach chapter, not just another transfer.</td><td data-label="What to protect">Route margin and recovery days.</td><td data-label="Related guide"><a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a></td></tr>
<tr><td data-label="Route context">Beach still undecided</td><td data-label="Better move">Start with the beach cluster guide before comparing two south-central options.</td><td data-label="What to protect">Season, transport, and whether beach time belongs at all.</td><td data-label="Related guide"><a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a></td></tr>
<tr><td data-label="Route context">City beach versus island recovery</td><td data-label="Better move">Use Nha Trang or Phu Quoc if the real question is city-beach energy versus island resort rest.</td><td data-label="What to protect">Airport/ferry logic and hotel-area clarity.</td><td data-label="Related guide"><a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-mui-ne-nha-trang-season-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Season, weather, and wind posture</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Do not use one Vietnam beach season for this choice. Mui Ne becomes especially sensitive to wind-sport expectations and open-beach conditions. Nha Trang is a south-central city-beach decision where weather, sea conditions, and boat days still need live checks.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-mui-ne-nha-trang-season-weather">
<thead><tr><th>Season question</th><th>Mui Ne posture</th><th>Nha Trang posture</th><th>Booking move</th></tr></thead>
<tbody>
<tr><td data-label="Season question">Wind sports</td><td data-label="Mui Ne posture">Only strong when wind, lesson/operator setup, and safety conditions match the sport.</td><td data-label="Nha Trang posture">Not the main reason to choose the city; treat boat or bay activity as weather-sensitive extras.</td><td data-label="Booking move">Ask operators for current conditions, safety rules, and cancellation terms.</td></tr>
<tr><td data-label="Season question">Beach relaxation</td><td data-label="Mui Ne posture">Works when open coast and resort strip mood are the goal, but swimming quality can be condition-sensitive.</td><td data-label="Nha Trang posture">Works better when you want beach plus food, taxis, and city services.</td><td data-label="Booking move">Choose stay area by the daily rhythm you want, not only by the destination name.</td></tr>
<tr><td data-label="Season question">Rain or rough sea risk</td><td data-label="Mui Ne posture">Road/rail access may still run while beach value changes.</td><td data-label="Nha Trang posture">Cam Ranh flights may still run while boat days and exposed beach plans change.</td><td data-label="Booking move">Use NCHMF and operator checks close to travel.</td></tr>
<tr><td data-label="Season question">High-demand dates</td><td data-label="Mui Ne posture">Exact resort strip and sport-school availability matter.</td><td data-label="Nha Trang posture">City hotel supply helps, but beachfront and quiet-resort edge pricing can jump.</td><td data-label="Booking move">Pay for location, cancellation, and transfer clarity, not only a pretty room photo.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-mui-ne-nha-trang-logistics:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Logistics: road, rail, airport, and daily movement</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The transport tax is different. Mui Ne asks whether a Phan Thiet/Mui Ne coast leg is worth the road or rail time. Nha Trang asks whether Cam Ranh airport access and city-beach convenience beat the extra air/transfer step.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-mui-ne-nha-trang-logistics">
<thead><tr><th>Logistics item</th><th>Mui Ne check</th><th>Nha Trang check</th><th>Why it matters</th></tr></thead>
<tbody>
<tr><td data-label="Logistics item">Arrival mode</td><td data-label="Mui Ne check">Confirm road or rail timing from Ho Chi Minh City, station/town transfer, and late-arrival risk.</td><td data-label="Nha Trang check">Confirm Cam Ranh flight, airport transfer, hotel area, and late-arrival taxi plan.</td><td data-label="Why it matters">The coast starts only after the arrival edge is absorbed.</td></tr>
<tr><td data-label="Logistics item">Daily movement</td><td data-label="Mui Ne check">Stay area can make meals, sport schools, dunes, and Fairy Stream easy or fragmented.</td><td data-label="Nha Trang check">City taxis, restaurants, and beach hotels make daily pivots easier, but hotel position still matters.</td><td data-label="Why it matters">A beach name does not explain the real day-to-day rhythm.</td></tr>
<tr><td data-label="Logistics item">Activity dependency</td><td data-label="Mui Ne check">Kitesurfing, dunes, and outings depend on operator quality, weather, and transport.</td><td data-label="Nha Trang check">Boat days, bay trips, Po Nagar, seafood, and city nights create more optionality.</td><td data-label="Why it matters">The fewer the backup options, the more live checks matter.</td></tr>
<tr><td data-label="Logistics item">Exit buffer</td><td data-label="Mui Ne check">Protect time before flights or long onward transfers from Ho Chi Minh City or another hub.</td><td data-label="Nha Trang check">Protect Cam Ranh transfer time and avoid fragile same-day domestic/international handoffs.</td><td data-label="Why it matters">Beach value collapses when the exit day becomes a stress test.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-mui-ne-nha-trang-cost-booking:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cost and booking checks</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Compare total route cost, not just room rates. Mui Ne can hide cost in transfer time and sport setup. Nha Trang can hide cost in airport transfer, beachfront location, and activity add-ons.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-mui-ne-nha-trang-cost-booking"} -->
<ul class="wp-block-list vg-check-list vg-mui-ne-nha-trang-cost-booking">
<li>For Mui Ne, price the road or rail leg, local transfer, stay area, sport lessons or rental, dunes/Fairy Stream transport, meals, and cancellation terms.</li>
<li>For Nha Trang, price the Cam Ranh transfer, beachfront versus city location, boat-day extras, seafood/activity budget, and late-arrival taxi plan.</li>
<li>Do not compare a sport-focused Mui Ne stay against a beachfront Nha Trang hotel and call it a pure beach comparison.</li>
<li>If either option forces an extra domestic flight, road leg, or weak one-night stop, count the lost half-day as part of the beach cost.</li>
<li>Use <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>, and <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a> before making beach nights non-refundable.</li>
</ul>
<!-- /wp:list -->

<!-- vg-mui-ne-nha-trang-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A premium route is often a shorter route with better decisions. This is especially true for beach add-ons, where the wrong stop can cost more time than it returns.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-mui-ne-nha-trang-skip-logic">
<thead><tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr></thead>
<tbody>
<tr><td data-label="If you want...">A classic first Vietnam route</td><td data-label="Protect this">Hanoi, one northern landscape, a central chapter, and clean departure logic.</td><td data-label="Skip first">A south-central beach detour that adds weak transfer days.</td><td data-label="Only add back when...">The beach solves rest, sport, season, or route shape better than another core stop.</td></tr>
<tr><td data-label="If you want...">Wind sports</td><td data-label="Protect this">Enough nights and operator/weather flexibility for the sport to actually happen.</td><td data-label="Skip first">Nha Trang, if wind sport is the reason for the beach chapter.</td><td data-label="Only add back when...">The city-beach benefits matter more than the sport.</td></tr>
<tr><td data-label="If you want...">Food and city movement</td><td data-label="Protect this">Restaurants, taxis, beach hotels, activity choice, and easy bad-weather pivots.</td><td data-label="Skip first">Mui Ne, if the stay area creates too much meal or transport friction.</td><td data-label="Only add back when...">Open coast, dunes, and sport setup are the actual point.</td></tr>
<tr><td data-label="If you want...">Quiet premium</td><td data-label="Protect this">Exact hotel area, low-friction meals, and enough downtime to justify the detour.</td><td data-label="Skip first">Assuming either place is automatically quiet.</td><td data-label="Only add back when...">The hotel, season, and transport plan support the mood.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-mui-ne-nha-trang-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before you book</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-mui-ne-nha-trang-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-mui-ne-nha-trang-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/southern-vietnam/binh-thuan" target="_blank" rel="noopener">Vietnam.travel Binh Thuan</a> and <a href="https://vietnam.travel/node/708" target="_blank" rel="noopener">Vietnam.travel Mui Ne</a> for official Mui Ne/Phan Thiet coast orientation.</li>
<li>Use <a href="https://vietnam.travel/places-to-go/central-vietnam/nha-trang" target="_blank" rel="noopener">Vietnam.travel Nha Trang</a>, the <a href="https://dulich.khanhhoa.gov.vn/en" target="_blank" rel="noopener">Khanh Hoa Tourism Portal</a>, and <a href="https://www.camranh.aero/en" target="_blank" rel="noopener">Cam Ranh International Airport</a> before treating Nha Trang as an easy city-beach add-on.</li>
<li>Use <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> plus the <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">National Centre for Hydro-Meteorological Forecasting</a> before beach, wind-sport, ferry, or boat-day commitments.</li>
<li>Use <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport</a> and <a href="https://dsvn.vn/" target="_blank" rel="noopener">Vietnam Railways schedule checks</a> before choosing Mui Ne for route convenience.</li>
<li>Use the <a href="https://evisa.gov.vn/" target="_blank" rel="noopener">official Vietnam e-visa portal</a> and <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a> before building domestic beach connections around international arrival assumptions.</li>
<li>Compare <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, <a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a>, <a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a>, and <a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a> before locking a south-central beach chapter.</li>
</ul>
<!-- /wp:list -->

<!-- vg-mui-ne-nha-trang-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mui Ne vs Nha Trang FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-mui-ne-nha-trang-faq">
<details><summary>Is Mui Ne better than Nha Trang?</summary><p>Mui Ne is better when wind sports, dunes, and a lower-density coastal reset are the reason for the stop. Nha Trang is better when the route needs a fuller city beach with restaurants, hotel range, airport access, and optional cultural or boat-day plans.</p></details>
<details><summary>Which is better for first-time visitors to Vietnam?</summary><p>Nha Trang is usually easier to justify for first-time visitors who specifically want an active beach city. Mui Ne should be chosen when its wind-sport or Phan Thiet coast job is clear. Many first trips should skip both if time is tight.</p></details>
<details><summary>Is Mui Ne good for kitesurfing?</summary><p>Mui Ne is one of the stronger Vietnam beach choices when kitesurfing or wind sports are the point, but the decision depends on live wind, operator quality, safety standards, lessons, insurance, and cancellation terms.</p></details>
<details><summary>Which is easier to reach?</summary><p>Nha Trang is easier by air because Cam Ranh serves the area. Mui Ne is more of a road or rail coast decision from Ho Chi Minh City or a wider southern route, so transfer time should be counted before booking.</p></details>
<details><summary>Should I visit both Mui Ne and Nha Trang?</summary><p>Most travelers should not visit both on a first or tight route. They can overlap as beach add-ons while solving different jobs. Add both only when the whole route is intentionally coast-led and has enough nights for transfer recovery.</p></details>
<details><summary>What is the biggest booking mistake?</summary><p>The biggest mistake is choosing by beach photos before choosing the beach's job. Decide first whether you need wind sport, open coast, city food, airport access, resort rest, or fewer stops.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then use <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a> to decide whether beach time belongs in the route. Use <a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a> for the active city-beach base, <a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a> when the beach fork is island resort versus city beach, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a> for route shape, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a> for season, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> for movement, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before making the coast non-refundable.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-mui-ne-nha-trang-hero:v1',
    'concierge verdict' => 'vg-mui-ne-nha-trang-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-mui-ne-nha-trang-at-a-glance:v1',
    'photo grid' => 'vg-mui-ne-nha-trang-photo-grid:v1',
    'source diversity' => 'vg-mui-ne-nha-trang-source-diversity:v1',
    'decision matrix' => 'vg-mui-ne-nha-trang-decision-matrix:v1',
    'route fit' => 'vg-mui-ne-nha-trang-route-fit:v1',
    'season weather' => 'vg-mui-ne-nha-trang-season-weather:v1',
    'logistics' => 'vg-mui-ne-nha-trang-logistics:v1',
    'cost booking' => 'vg-mui-ne-nha-trang-cost-booking:v1',
    'skip logic' => 'vg-mui-ne-nha-trang-skip-logic:v1',
    'live checks' => 'vg-mui-ne-nha-trang-live-checks:v1',
    'FAQ' => 'vg-mui-ne-nha-trang-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_mn_nt_ops_assert_required_content_markers($content, $required_content_markers);
vg_mn_nt_ops_assert_internal_page_links_are_published('Mui Ne vs Nha Trang guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Mui Ne vs Nha Trang',
    'post_name'      => 'mui-ne-vs-nha-trang',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_mn_nt_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Mui Ne vs Nha Trang comparison for international travelers choosing wind-sport Phan Thiet/Mui Ne coast time or active Nha Trang city-beach routing by route fit, weather, logistics, and cost.',
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
    vg_mn_nt_ops_fail('Could not publish Mui Ne vs Nha Trang guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_mn_nt_ops_fail('Could not publish Mui Ne vs Nha Trang guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Mui Ne vs Nha Trang: Which Beach Base Is Better?');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Mui Ne vs Nha Trang comparison: choose wind-sport Mui Ne coast or active Nha Trang city beach by route fit, weather, logistics, cost, and skip logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Mui Ne vs Nha Trang');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Mui Ne wind-sport/open-sand coast time or Nha Trang city-beach routing is the stronger south-central Vietnam beach chapter before booking hotels, transfers, boat days, or sport lessons.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 20, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Mui Ne vs Nha Trang comparison guide with a concierge verdict, at-a-glance decision table, licensed real photo proof, source-diversity panel, decision matrix, route-fit table, season/weather table, logistics table, cost and booking checks, skip logic, live checks, FAQ, Compare hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Binh Thuan - https://vietnam.travel/places-to-go/southern-vietnam/binh-thuan - checked July 20, 2026\nVietnam.travel - Mui Ne - https://vietnam.travel/node/708 - checked July 20, 2026\nVietnam.travel - Nha Trang - https://vietnam.travel/places-to-go/central-vietnam/nha-trang - checked July 20, 2026\nKhanh Hoa Tourism Portal - English home - https://dulich.khanhhoa.gov.vn/en - checked July 20, 2026\nCam Ranh International Airport - https://www.camranh.aero/en - checked July 20, 2026; live flight and airport-service checks required close to travel\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 20, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 20, 2026\nVietnam Railways schedule portal - https://dsvn.vn/ - checked July 20, 2026; live train and transfer checks required close to travel\nOfficial Vietnam e-visa portal - https://evisa.gov.vn/ - checked July 20, 2026\nNational Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked July 20, 2026; live weather, wind, and marine checks required close to travel\nWikimedia Commons image record - Vietnam, Mui Ne beach, Kitesurfing on the beach - https://commons.wikimedia.org/wiki/File:Vietnam,_Mui_Ne_beach,_Kitesurfing_on_the_beach.jpg - license checked July 20, 2026\nWikimedia Commons image record - Vietnam, Mui Ne sand dunes - https://commons.wikimedia.org/wiki/File:Vietnam,_Mui_Ne_sand_dunes.jpg - license checked July 20, 2026\nWikimedia Commons image record - Mui Ne Fairy Stream - https://commons.wikimedia.org/wiki/File:Mui_Ne_Fairy_Stream.jpg - license checked July 20, 2026\nWikimedia Commons image record - Mui Ne fishing village - https://commons.wikimedia.org/wiki/File:Mui_Ne_fishing_village.jpg - license checked July 20, 2026\nWikimedia Commons image record - Nha Trang Beach 3 - https://commons.wikimedia.org/wiki/File:Nha_Trang_Beach_3.jpg - license checked July 20, 2026\nWikimedia Commons image record - Panorama of Vinh Nha Trang coastline - https://commons.wikimedia.org/wiki/File:Panorama_of_V%E1%BB%8Bnh_Nha_Trang_coastline.jpg - license checked July 20, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Mui Ne vs Nha Trang is a route fork: sport-first/open-sand coast versus active city-beach mobility. The stronger choice is the one that improves the trip after wind, weather, transfer time, stay area, activity dependency, and lost itinerary slack are counted.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Comparison guide built around a real south-central coast decision rather than a generic destination-versus-destination listicle.\nConcierge verdict separates Mui Ne wind-sport/open-coast value from Nha Trang active city-beach mobility.\nAt-a-glance table answers first-useful questions before booking.\nLicensed real photo proof for Mui Ne kitesurfing, dunes, Fairy Stream, fishing village, Nha Trang Beach, and Nha Trang coastline.\nSource-diversity panel explains what official tourism, weather, rail, transport, airport, entry, and image-license sources can and cannot prove.\nDecision matrix covers route job, route shape, traveler mood, premium value, and failure mode.\nRoute-fit table links the choice to 10, 14, 21-day, beach, city-beach, and regional planning pages.\nSeason/weather table prevents treating wind sports and city beach plans as one generic beach season.\nLogistics table counts road, rail, Cam Ranh airport, stay area, activities, and exit buffers.\nCost and booking checks separate room rate from transfer, sport, boat-day, and cancellation risk.\nSkip logic protects overfull itineraries from decorative beach add-ons.\nVisible source trail, related routes, and update log.");
$related_routes = "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before choosing any beach chapter.\nBest Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ | Decide whether the trip needs a beach chapter before choosing Mui Ne or Nha Trang.\nNha Trang Travel Guide | /destinations/nha-trang-travel-guide/ | Use this when the active city-beach base is still the stronger answer.\nPhu Quoc vs Nha Trang | /compare/phu-quoc-vs-nha-trang/ | Compare city-beach Nha Trang against island resort recovery before assuming Nha Trang is the beach fork.\nDa Nang Travel Guide | /destinations/da-nang-travel-guide/ | Compare airport-and-beach city base logic before choosing a south-central coast detour.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Decide whether the route should include south-central beach time at all.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check regional weather, wind, and storm pressure before locking beach or sport days.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Count road, rail, Cam Ranh transfer time, luggage, and departure buffers before booking.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget beach hotels, road or airport transfers, sport lessons, boat days, cancellation terms, and route friction.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Plan VND cash, cards, deposits, sport operators, taxis, and backup payment before beach travel.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, ride-hailing, operator messages, and hotel coordination from becoming a preventable beach-day problem.\nVietnam E-Visa | /plan/vietnam-evisa/ | Recheck entry timing before building domestic coast connections around international flights.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a short route should add Mui Ne or Nha Trang, or cut the beach first.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide whether one south-central beach chapter earns its place in a two-week route.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use longer timing to decide whether beach time should be sport coast, city beach, or skipped.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check water, sport, scooter, transfer, interruption, and medical coverage before beach travel.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair taxis, sport operators, deposits, nightlife, boat activities, and hotel transfers with practical risk checks.\nMui Ne vs Nha Trang | /compare/mui-ne-vs-nha-trang/ | Choose wind-sport/open-sand coast time or active city-beach routing before booking south-central beach nights.";
vg_mn_nt_ops_assert_related_route_meta_links_are_published('Mui Ne vs Nha Trang related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Mui Ne kitesurfing image: Vietnam, Mui Ne beach, Kitesurfing on the beach by Vyacheslav Argenberg, CC BY 4.0. Body images: Mui Ne sand dunes by Vyacheslav Argenberg, CC BY 4.0; Mui Ne Fairy Stream by Ddubbert, CC BY-SA 3.0; Mui Ne fishing village by Michel Coutty, Public domain; Nha Trang Beach 3 by Christophe95, CC BY-SA 4.0; Panorama of Vinh Nha Trang coastline by Tony24986, CC BY-SA 4.0.');
update_post_meta($page_id, '_generate-disable-headline', 'true');

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
        vg_mn_nt_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_mn_nt_ops_fail('Mui Ne vs Nha Trang guide was updated but is not published.');
}

vg_mn_nt_ops_refresh_compare_hub();
vg_mn_nt_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_mn_nt_ops_log("Published Mui Ne vs Nha Trang guide: {$page_id} {$updated_permalink}");

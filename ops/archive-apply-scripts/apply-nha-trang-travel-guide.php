<?php
/**
 * Publish the Nha Trang Travel Guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * VG_FORCE_NHA_TRANG_GUIDE_REPUBLISH=1 wp eval-file ops/apply-nha-trang-travel-guide.php --allow-root
 *
 * Repair only hub/related-route side effects with:
 * VG_REPAIR_NHA_TRANG_GUIDE_LINKS=1 wp eval-file ops/apply-nha-trang-travel-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_nha_trang_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_nha_trang_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_nha_trang_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_NHA_TRANG_GUIDE_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_nha_trang_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_NHA_TRANG_GUIDE_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_nha_trang_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_nha_trang_ops_fail('Could not resolve a valid WordPress author for the Nha Trang Travel Guide.');
}

function vg_nha_trang_ops_internal_path_from_href(string $href): ?string
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

function vg_nha_trang_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_nha_trang_ops_internal_path_from_href($href);

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
        vg_nha_trang_ops_fail(implode(PHP_EOL, $failures));
    }

    vg_nha_trang_ops_log("Validated {$label} internal page links are published.");
}

function vg_nha_trang_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_nha_trang_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_nha_trang_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_nha_trang_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_nha_trang_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_nha_trang_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_nha_trang_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_nha_trang_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_nha_trang_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_nha_trang_ops_log("Validated {$label} related-route links are published.");
}

function vg_nha_trang_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_nha_trang_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_nha_trang_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

function vg_nha_trang_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
{
    $marked_block = $marker . "\n" . $block;

    if (str_contains($page->post_content, $marked_block)) {
        vg_nha_trang_ops_log("Skipped {$label}: current block already present.");
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
            vg_nha_trang_ops_fail("Could not confidently refresh {$label}.");
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
        vg_nha_trang_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_nha_trang_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_nha_trang_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_nha_trang_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_nha_trang_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_nha_trang_ops_published_page_exists('destinations/nha-trang-travel-guide')) {
        vg_nha_trang_ops_log('Skipped Destinations hub refresh: Nha Trang Travel Guide is not published.');
        return;
    }

    $marker = '<!-- vg-nha-trang-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use Nha Trang when beach time should stay urban and active</h3><p>The <a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a> helps travelers decide when the city beach, Cam Ranh access, seafood, Po Nagar, island or bay day trips, and hotel-area trade-offs make Nha Trang a better route answer than an island resort finish.</p></div>
<!-- /wp:group -->
HTML;

    vg_nha_trang_ops_assert_internal_page_links_are_published('Destinations hub Nha Trang note', $block);
    vg_nha_trang_ops_upsert_marked_group($hub, 'Destinations hub Nha Trang note', $marker, $block);
}

function vg_nha_trang_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_nha_trang_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_nha_trang_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_nha_trang_ops_log("Skipped related-route refresh for {$label}: Nha Trang Travel Guide line is already current.");
        return;
    }

    $next = implode("\n", $next_lines);
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_nha_trang_ops_log("Upserted Nha Trang Travel Guide related route to {$label}: {$page->ID}");
}

function vg_nha_trang_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Nha Trang Travel Guide | /destinations/nha-trang-travel-guide/ | Decide whether Nha Trang should be the active city-beach and Cam Ranh access base before booking beach hotels, transfers, boat days, or a south-central coast detour.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
            'destinations/best-islands-in-vietnam' => 'Best Islands in Vietnam guide',
            'destinations/phu-quoc-travel-guide' => 'Phu Quoc Travel Guide',
            'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
            'compare/phu-quoc-vs-nha-trang' => 'Phu Quoc vs Nha Trang guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ] as $path => $label
    ) {
        vg_nha_trang_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_nha_trang_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/nha-trang-travel-guide', OBJECT, 'page');

if (vg_nha_trang_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_nha_trang_ops_fail('Repair mode requires the Nha Trang Travel Guide to already be published.');
    }

    vg_nha_trang_ops_refresh_destinations_hub();
    vg_nha_trang_ops_refresh_inbound_related_routes();
    vg_nha_trang_ops_log("Repaired Nha Trang Travel Guide side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_nha_trang_ops_force_republish_enabled()) {
    vg_nha_trang_ops_fail('Nha Trang Travel Guide is not a draft. Set VG_FORCE_NHA_TRANG_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_nha_trang_ops_log("Preflight Nha Trang Travel Guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_nha_trang_ops_log('Preflight Nha Trang Travel Guide: no existing page found; creating a child page under /destinations/.');
}

$nha_trang_beach_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Nha_Trang_Beach_3.jpg/1920px-Nha_Trang_Beach_3.jpg');
$nha_trang_beach_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Nha_Trang_Beach_3.jpg');
$coastline_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Panorama_of_V%E1%BB%8Bnh_Nha_Trang_coastline.jpg/1920px-Panorama_of_V%E1%BB%8Bnh_Nha_Trang_coastline.jpg');
$coastline_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Panorama_of_V%E1%BB%8Bnh_Nha_Trang_coastline.jpg');
$po_nagar_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d7/Po_Nagar_Nha_Trang_Vietnam.JPG/1280px-Po_Nagar_Nha_Trang_Vietnam.JPG');
$po_nagar_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Po_Nagar_Nha_Trang_Vietnam.JPG');
$long_son_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/8d/Long_Son_Pagoda_1.jpg/1280px-Long_Son_Pagoda_1.jpg');
$long_son_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Long_Son_Pagoda_1.jpg');
$hon_mun_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/5a/Hon_Mun_island_%28H%C3%B2n_Mun%29%2C_Cam_Ranh%2C_Nha_Trang%2C_Vi%E1%BB%87t_Nam_20140518_105634_%28taken_with_Samsung_Galaxy_Note_3%29.jpg/1280px-Hon_Mun_island_%28H%C3%B2n_Mun%29%2C_Cam_Ranh%2C_Nha_Trang%2C_Vi%E1%BB%87t_Nam_20140518_105634_%28taken_with_Samsung_Galaxy_Note_3%29.jpg');
$hon_mun_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hon_Mun_island_(H%C3%B2n_Mun),_Cam_Ranh,_Nha_Trang,_Vi%E1%BB%87t_Nam_20140518_105634_(taken_with_Samsung_Galaxy_Note_3).jpg');
$cam_ranh_terminal_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f7/Cam_Ranh_International_Airport_Terminal_2.jpg/1280px-Cam_Ranh_International_Airport_Terminal_2.jpg');
$cam_ranh_terminal_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cam_Ranh_International_Airport_Terminal_2.jpg');
$cathedral_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/8f/Nha_Trang_Cathedral.jpg/1280px-Nha_Trang_Cathedral.jpg');
$cathedral_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Nha_Trang_Cathedral.jpg');

$content = <<<HTML
<!-- vg-nha-trang-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$nha_trang_beach_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Nha Trang Beach with the coastal city behind it in Vietnam" src="{$nha_trang_beach_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 20, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Nha Trang Travel Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Nha Trang is Vietnam's active city-beach answer: beachfront hotels, seafood, boat-day options, Po Nagar, Long Son, nightlife, and Cam Ranh airport access in one coastal base. It earns the nights when beach time should stay urban, mobile, and easy to connect.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: treat Nha Trang as a route tool, not just a beach name. The useful question is whether the city beach, airport transfer, bay trips, and hotel area make the route easier than Phu Quoc, Da Nang, Hoi An, Con Dao, or skipping beach time.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$nha_trang_beach_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-nha-trang-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-nha-trang-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-nha-trang-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Choose Nha Trang when beach time should stay close to a real city: restaurants, simple taxis, active nights, cultural stops, day-trip choices, and Cam Ranh airport access.</strong> Do not choose it when you want quiet island recovery, remote luxury, or a beach chapter that should erase the city.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-nha-trang-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-nha-trang-shortlist">
<li><strong>Best fit:</strong> 2 to 4 nights as an active coastal base, beach reset, food stop, or Cam Ranh-linked route chapter.</li>
<li><strong>Best route pairing:</strong> south-central or central-to-south routes where an island detour would add too much friction.</li>
<li><strong>Best hotel logic:</strong> choose the exact beach strip, Tran Phu access, quieter north, or Cam Ranh/Bai Dai resort context by trip job.</li>
<li><strong>Best premium use:</strong> buy location clarity, sea view, quiet room position, airport transfer confidence, and cancellation flexibility.</li>
<li><strong>Skip first when:</strong> the route needs silence, nature-first luxury, or only one more famous beach photo.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-nha-trang-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-nha-trang-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-nha-trang-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fast answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-nha-trang-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Is Nha Trang worth visiting?</strong></td><td data-label="Answer">Yes when the beach should come with food, movement, airport access, and optional bay or cultural stops. It is weaker when the trip needs a quiet island finish.</td></tr>
<tr><td data-label="Question"><strong>How many nights?</strong></td><td data-label="Answer">Two nights works as a fast beach-city stop. Three or four nights is better if you want boat days, Po Nagar, food, and recovery without rushing.</td></tr>
<tr><td data-label="Question"><strong>Where should first-timers stay?</strong></td><td data-label="Answer">Most first-timers should start near the main beach/Tran Phu corridor, then choose quieter north or Cam Ranh/Bai Dai only when the hotel itself is the point.</td></tr>
<tr><td data-label="Question"><strong>What is the main mistake?</strong></td><td data-label="Answer">Treating Cam Ranh airport as if it were inside the city, or booking a beach hotel without checking exact beach access, road noise, room position, and transfer timing.</td></tr>
<tr><td data-label="Question"><strong>Best comparison?</strong></td><td data-label="Answer">Use <a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a> if the route is choosing between island recovery and city-beach mobility.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-nha-trang-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what Nha Trang changes in a route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The images are used as planning evidence. Nha Trang is not one beach scene; it is a city beach, bay, airport, cultural, and hotel-area decision compressed into one route chapter.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-nha-trang-photo-grid" aria-label="Nha Trang travel guide photography">
<figure class="vg-guide-photo"><img src="{$nha_trang_beach_image}" alt="Nha Trang Beach with city hotels behind the sand" loading="lazy" decoding="async"><figcaption>The main beach is an urban beach, not a quiet island escape. Image: <a href="{$nha_trang_beach_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$coastline_image}" alt="Panorama of Nha Trang coastline and bay" loading="lazy" decoding="async"><figcaption>The coastline spread makes hotel location and transfer logic matter. Image: <a href="{$coastline_credit_url}" target="_blank" rel="license noopener">Tony24986 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$po_nagar_image}" alt="Po Nagar Cham towers in Nha Trang" loading="lazy" decoding="async"><figcaption>Po Nagar is the cultural counterweight that keeps the stay from becoming only sand. Image: <a href="{$po_nagar_credit_url}" target="_blank" rel="license noopener">Tervlugt / CC BY 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$long_son_image}" alt="Long Son Pagoda in Nha Trang" loading="lazy" decoding="async"><figcaption>Long Son adds a low-friction city stop when the beach day needs structure. Image: <a href="{$long_son_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hon_mun_image}" alt="Hon Mun island near Nha Trang" loading="lazy" decoding="async"><figcaption>Bay and island days need live marine, operator, and weather checks, not brochure momentum. Image: <a href="{$hon_mun_credit_url}" target="_blank" rel="license noopener">Nguyen Hung Vu / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cam_ranh_terminal_image}" alt="Cam Ranh International Airport terminal serving Nha Trang" loading="lazy" decoding="async"><figcaption>Cam Ranh access is useful, but transfer time belongs in the real itinerary. Image: <a href="{$cam_ranh_terminal_credit_url}" target="_blank" rel="license noopener">Ed Crystal / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cathedral_image}" alt="Nha Trang Cathedral" loading="lazy" decoding="async"><figcaption>City texture is part of the Nha Trang value proposition when the route wants more than a resort. Image: <a href="{$cathedral_credit_url}" target="_blank" rel="license noopener">Vinhtantran / CC BY-SA 3.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-nha-trang-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Source diversity: what the sources can actually prove</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-nha-trang-source-diversity">
<thead><tr><th>Source job</th><th>Primary use</th><th>How to read it</th></tr></thead>
<tbody>
<tr><td data-label="Source job">Official destination framing</td><td data-label="Primary use"><a href="https://vietnam.travel/places-to-go/central-vietnam/nha-trang" target="_blank" rel="noopener">Vietnam.travel Nha Trang</a> and Khanh Hoa tourism context.</td><td data-label="How to read it">Useful for evergreen orientation, not a substitute for route judgment.</td></tr>
<tr><td data-label="Source job">Local tourism context</td><td data-label="Primary use"><a href="https://dulich.khanhhoa.gov.vn/en" target="_blank" rel="noopener">Khanh Hoa Tourism Portal</a> and the Nha Trang city page.</td><td data-label="How to read it">Good for local context, accommodation and place framing; event and service details need live checks.</td></tr>
<tr><td data-label="Source job">Airport and movement</td><td data-label="Primary use"><a href="https://www.camranh.aero/en" target="_blank" rel="noopener">Cam Ranh International Airport</a> plus <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport</a>.</td><td data-label="How to read it">Airport access is a reason to choose Nha Trang only after transfer time, arrival hour, and baggage are counted.</td></tr>
<tr><td data-label="Source job">Weather and marine risk</td><td data-label="Primary use"><a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather</a> and <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a>.</td><td data-label="How to read it">Use for planning posture; final boat, beach, and storm decisions need close-in forecasts.</td></tr>
<tr><td data-label="Source job">Protected bay context</td><td data-label="Primary use"><a href="https://en.nbca.gov.vn/khu-du-tru-thien-nhien-vinh-nha-trang-khanh-hoa/" target="_blank" rel="noopener">Nha Trang Bay Nature Reserve</a> context for the marine protected area, Hon Mun, coral ecosystems, and conservation pressure.</td><td data-label="How to read it">Use it to understand why boat, snorkeling, and island claims need live environmental, operator, and rule checks.</td></tr>
<tr><td data-label="Source job">Image proof</td><td data-label="Primary use">Wikimedia Commons image records for the beach, coastline, Po Nagar, Long Son, Hon Mun, Cam Ranh, and cathedral.</td><td data-label="How to read it">Images show visible place character; they do not prove current crowding, water clarity, service quality, or weather.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-nha-trang-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">When Nha Trang fits a Vietnam itinerary</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-nha-trang-route-fit">
<thead><tr><th>Route context</th><th>Use Nha Trang when...</th><th>Protect this</th><th>Better alternative</th></tr></thead>
<tbody>
<tr><td data-label="Route context">10 days</td><td data-label="Use Nha Trang when...">The trip is south/central leaning and needs one active beach stop, not a full island detour.</td><td data-label="Protect this">Two or three nights, Cam Ranh transfer, and one rest block.</td><td data-label="Better alternative"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a> may still cut the beach first.</td></tr>
<tr><td data-label="Route context">14 days</td><td data-label="Use Nha Trang when...">The route can hold a south-central coast chapter without weakening Hoi An, Hue, Ninh Binh, or the bay.</td><td data-label="Protect this">Beach time plus one city/culture block.</td><td data-label="Better alternative"><a href="/destinations/da-nang-travel-guide/">Da Nang</a> if central coast logistics are cleaner.</td></tr>
<tr><td data-label="Route context">21 days</td><td data-label="Use Nha Trang when...">A longer route wants an active coast base between central Vietnam and the south.</td><td data-label="Protect this">A real reason for Nha Trang beyond another beach photo.</td><td data-label="Better alternative"><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc</a> for resort recovery.</td></tr>
<tr><td data-label="Route context">Beach fork</td><td data-label="Use Nha Trang when...">The beach should stay urban, food-led, and active.</td><td data-label="Protect this">Hotel area, transfer cost, and boat-day expectations.</td><td data-label="Better alternative"><a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a> for the full fork.</td></tr>
<tr><td data-label="Route context">Family trip</td><td data-label="Use Nha Trang when...">City services, resort facilities, short taxis, and activity choice matter more than silence.</td><td data-label="Protect this">Room location, pool quality, beach crossing, and easy dinners.</td><td data-label="Better alternative"><a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a> if the whole beach chapter is still uncertain.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-nha-trang-where-to-stay:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where to stay in Nha Trang</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-nha-trang-where-to-stay">
<thead><tr><th>Area</th><th>Best for</th><th>Watch for</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Area">Tran Phu / main beach</td><td data-label="Best for">First-timers, walkable dinners, beach access, city energy, and simple taxis.</td><td data-label="Watch for">Road noise, beach crossing, room direction, and whether the hotel is really beachfront.</td><td data-label="VietnamGuide verdict">Default first base.</td></tr>
<tr><td data-label="Area">North of the center</td><td data-label="Best for">A calmer hotel posture while staying near the city.</td><td data-label="Watch for">Fewer walkable choices and whether taxis become part of every meal.</td><td data-label="VietnamGuide verdict">Good when exact hotel quality is strong.</td></tr>
<tr><td data-label="Area">Hon Tre / resort island context</td><td data-label="Best for">Families or travelers who want a contained resort/activity chapter.</td><td data-label="Watch for">Isolation, package logic, ferry/cable access, and whether the city still matters.</td><td data-label="VietnamGuide verdict">Choose deliberately, not by default.</td></tr>
<tr><td data-label="Area">Cam Ranh / Bai Dai</td><td data-label="Best for">Fly-and-flop resort stays, airport convenience, beach resort focus, or late arrival recovery.</td><td data-label="Watch for">Distance from Nha Trang city, food isolation, transfer cost, and resort dependency.</td><td data-label="VietnamGuide verdict">A different trip job from city Nha Trang.</td></tr>
<tr><td data-label="Area">Station / city inland</td><td data-label="Best for">Budget control, short transit, or travelers prioritizing food and city movement over sea views.</td><td data-label="Watch for">Heat, walking comfort, and whether you are paying with beach time.</td><td data-label="VietnamGuide verdict">Useful only when the budget or transit logic is clear.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-nha-trang-beach-areas:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Nha Trang beach and activity areas</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-nha-trang-beach-areas">
<thead><tr><th>Choice</th><th>What it changes</th><th>Best use</th><th>Live check</th></tr></thead>
<tbody>
<tr><td data-label="Choice">Main Nha Trang Beach</td><td data-label="What it changes">Keeps beach, food, and hotels close together.</td><td data-label="Best use">First base and low-friction beach days.</td><td data-label="Live check">Beach segment, surf/season, room noise, and road crossing.</td></tr>
<tr><td data-label="Choice">Po Nagar + river/city loop</td><td data-label="What it changes">Adds culture and city texture without a full day trip.</td><td data-label="Best use">Half-day structure around beach time.</td><td data-label="Live check">Opening hours, heat, respectful dress, and taxi timing.</td></tr>
<tr><td data-label="Choice">Hon Mun / bay boat day</td><td data-label="What it changes">Turns Nha Trang into an active marine chapter.</td><td data-label="Best use">Travelers who want boat time and accept weather/operator variability.</td><td data-label="Live check">Marine conditions, conservation rules, operator reviews, and cancellation terms.</td></tr>
<tr><td data-label="Choice">Long Son / Cathedral</td><td data-label="What it changes">Gives the city day a land-based alternative to the beach.</td><td data-label="Best use">Short cultural texture without leaving Nha Trang.</td><td data-label="Live check">Heat, stairs, opening hours, and modest clothing expectations.</td></tr>
<tr><td data-label="Choice">Cam Ranh / Bai Dai resort strip</td><td data-label="What it changes">Makes the airport and resort the trip job.</td><td data-label="Best use">Late arrivals, early departures, resort-led stays, or family recovery.</td><td data-label="Live check">Transfer time to city, meal access, beach condition, and cancellation policy.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-nha-trang-priority-map:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Nha Trang priority map</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Pick the job first, then select days. A Nha Trang stay gets thin when every attraction is added without deciding whether the trip needs beach, city, resort, or marine activity.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-nha-trang-priority-map">
<thead><tr><th>Trip job</th><th>Prioritize</th><th>Skip first</th><th>Why</th></tr></thead>
<tbody>
<tr><td data-label="Trip job">Beach-city reset</td><td data-label="Prioritize">Main beach hotel, seafood, one cultural stop, and one unplanned half-day.</td><td data-label="Skip first">Long boat days or distant excursions.</td><td data-label="Why">The city-beach value is convenience.</td></tr>
<tr><td data-label="Trip job">Active coast stay</td><td data-label="Prioritize">Boat day, Po Nagar, food, and a walkable beach base.</td><td data-label="Skip first">Remote resort isolation.</td><td data-label="Why">Activity is the reason to choose Nha Trang over Phu Quoc.</td></tr>
<tr><td data-label="Trip job">Family resort chapter</td><td data-label="Prioritize">Pool, easy meals, room comfort, short transfers, and weather flexibility.</td><td data-label="Skip first">Overloaded city days.</td><td data-label="Why">Families pay for friction reduction.</td></tr>
<tr><td data-label="Trip job">Premium but not isolated</td><td data-label="Prioritize">High-quality beachfront hotel, quiet room position, transfer support, and curated dinners.</td><td data-label="Skip first">Comparing only star ratings.</td><td data-label="Why">Exact location and service matter more than generic luxury labels.</td></tr>
<tr><td data-label="Trip job">Route filler</td><td data-label="Prioritize">Reconsidering the stop.</td><td data-label="Skip first">Nha Trang itself.</td><td data-label="Why">A beach city should solve a route problem, not fill a map gap.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-nha-trang-season-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best time and weather posture</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Nha Trang sits on Vietnam's south-central coast, so it should not be planned with the same assumptions as Hanoi, Hoi An, Phu Quoc, or the Mekong. Use the broad season to choose posture, then check current forecasts and marine conditions before boat days.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-nha-trang-season-weather">
<thead><tr><th>Timing</th><th>Planning posture</th><th>What to book</th><th>What to recheck</th></tr></thead>
<tbody>
<tr><td data-label="Timing">January to August posture</td><td data-label="Planning posture">Stronger case for beach, boat, and city movement.</td><td data-label="What to book">Better beach hotel, flexible boat day, and a calmer airport transfer.</td><td data-label="What to recheck">Heat, sea state, wind, and operator conditions.</td></tr>
<tr><td data-label="Timing">September to December rainy season</td><td data-label="Planning posture">Keep Nha Trang flexible and avoid building the whole route around one boat day.</td><td data-label="What to book">Refundable hotel terms, backup city plans, and a hotel that still works if the beach day fades.</td><td data-label="What to recheck">Rain, swell, and bay visibility.</td></tr>
<tr><td data-label="Timing">Any month with a boat-day plan</td><td data-label="Planning posture">Treat the boat day as conditional, not as the reason the whole route succeeds.</td><td data-label="What to book">Operator with clear pickup, route, conservation, weather, and cancellation terms.</td><td data-label="What to recheck">NCHMF alerts, marine-protection rules, and same-week forecast.</td></tr>
<tr><td data-label="Timing">Late arrival or early departure</td><td data-label="Planning posture">Cam Ranh may make Nha Trang convenient even when beach time is secondary.</td><td data-label="What to book">Airport transfer and a sensible first/last night area.</td><td data-label="What to recheck">Flight time, transfer duration, and hotel check-in policy.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-nha-trang-transport-logistics:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cam Ranh, trains, and local movement</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-nha-trang-transport-logistics">
<thead><tr><th>Movement</th><th>Use it when...</th><th>Hidden friction</th><th>Live check</th></tr></thead>
<tbody>
<tr><td data-label="Movement">Fly via Cam Ranh</td><td data-label="Use it when...">The route needs a clean beach-city chapter with airport access.</td><td data-label="Hidden friction">Airport is outside the city; late flights can turn arrival into a taxi-and-check-in day.</td><td data-label="Live check">Flight time, luggage, transfer price, and hotel location.</td></tr>
<tr><td data-label="Movement">Train to Nha Trang</td><td data-label="Use it when...">The route is coast-led or you value a lower-airport-friction move.</td><td data-label="Hidden friction">Schedule fit, sleep quality, and arrival hour.</td><td data-label="Live check">Seat/berth class, station transfer, and backup plan.</td></tr>
<tr><td data-label="Movement">Private transfer</td><td data-label="Use it when...">You are linking Cam Ranh, resort strip, city hotel, or nearby route segments.</td><td data-label="Hidden friction">Driver quality, night driving, luggage, and route timing.</td><td data-label="Live check">Written pickup point, vehicle size, tolls, and cancellation terms.</td></tr>
<tr><td data-label="Movement">City taxis / ride-hailing</td><td data-label="Use it when...">You stay near the beach/city and want flexible meals and short stops.</td><td data-label="Hidden friction">Peak times, rain, language, and exact hotel entrance.</td><td data-label="Live check">App availability, hotel help, and cash backup.</td></tr>
<tr><td data-label="Movement">Boat day</td><td data-label="Use it when...">The day itself is the point, not a box to tick.</td><td data-label="Hidden friction">Weather, conservation rules, operator quality, seasickness, and cancellation.</td><td data-label="Live check">Marine forecast, inclusions, safety gear, pickup logistics, and refund rules.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-nha-trang-cost-booking:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cost and booking checks that matter</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-nha-trang-cost-booking"} -->
<ul class="wp-block-list vg-check-list vg-nha-trang-cost-booking">
<li>Compare the total city-beach cost: room, sea view, airport transfer, taxis, boat day, meals, and cancellation terms.</li>
<li>Do not compare a Cam Ranh resort stay against a Tran Phu city hotel and call it a simple Nha Trang price comparison; they solve different trip jobs.</li>
<li>Check whether the hotel is actually across from the beach, behind a busy road, inland, or in a resort strip that changes daily movement.</li>
<li>Book boat or island days with flexible expectations; water clarity, sea state, operator quality, and conservation rules are not evergreen constants.</li>
<li>Protect arrival and departure days if flights use Cam Ranh at awkward hours.</li>
<li>Use <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>, <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before paying deposits, relying on informal beach/activity quotes, or assuming Cam Ranh arrival communication will be easy.</li>
</ul>
<!-- /wp:list -->

<!-- vg-nha-trang-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first in Nha Trang</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-nha-trang-skip-logic">
<thead><tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr></thead>
<tbody>
<tr><td data-label="If you want...">A calm beach reset</td><td data-label="Protect this">Hotel comfort, beach access, food, and flexible mornings.</td><td data-label="Skip first">Packed boat days, distant excursions, and nightlife pressure.</td><td data-label="Only add back when...">The stop has enough nights to recover.</td></tr>
<tr><td data-label="If you want...">An active city beach</td><td data-label="Protect this">Food, taxis, Po Nagar, beach time, and one structured day.</td><td data-label="Skip first">Remote resort strips.</td><td data-label="Only add back when...">The resort itself is the reason for the stay.</td></tr>
<tr><td data-label="If you want...">Quiet premium</td><td data-label="Protect this">Lower density and fewer daily decisions.</td><td data-label="Skip first">Nha Trang city center.</td><td data-label="Only add back when...">You deliberately choose Cam Ranh/Bai Dai or a quiet hotel area.</td></tr>
<tr><td data-label="If you want...">A strong first Vietnam route</td><td data-label="Protect this">Hanoi, one northern landscape, central Vietnam, and honest transfer days.</td><td data-label="Skip first">Nha Trang if beach time is decorative.</td><td data-label="Only add back when...">The beach chapter has a clear job.</td></tr>
<tr><td data-label="If you want...">Island resort recovery</td><td data-label="Protect this">Rest, resort, and fewer city decisions.</td><td data-label="Skip first">Nha Trang.</td><td data-label="Only add back when...">You actually want city energy and activity choice.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-nha-trang-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Official checks before locking Nha Trang</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-nha-trang-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-nha-trang-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/central-vietnam/nha-trang" target="_blank" rel="noopener">Vietnam.travel Nha Trang</a>, the <a href="https://dulich.khanhhoa.gov.vn/en" target="_blank" rel="noopener">Khanh Hoa Tourism Portal</a>, and the <a href="https://dulich.khanhhoa.gov.vn/en/introduce/administrative-units/nha-trang-city.html" target="_blank" rel="noopener">Nha Trang city page</a> for official destination orientation.</li>
<li>Use <a href="https://www.camranh.aero/en" target="_blank" rel="noopener">Cam Ranh International Airport</a>, <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport</a>, and <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before treating Nha Trang as an easy beach add-on.</li>
<li>Use <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> plus <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a> before locking beach, boat, or airport-buffer assumptions.</li>
<li>Use the <a href="https://evisa.gov.vn/" target="_blank" rel="noopener">official Vietnam e-visa portal</a> and <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a> before building domestic beach connections around international arrival assumptions.</li>
<li>Compare <a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a>, <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, and <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a> before treating Nha Trang as the default beach answer.</li>
</ul>
<!-- /wp:list -->

<!-- vg-nha-trang-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Nha Trang Travel Guide FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-nha-trang-faq">
<details><summary>Is Nha Trang worth visiting for first-time visitors?</summary><p>Yes when the route needs an active city beach, food, airport access, and optional bay or cultural stops. It is not automatic on short first trips where another transfer weakens Hanoi, Ninh Binh, Ha Long/Lan Ha, Hoi An, or Hue.</p></details>
<details><summary>How many days do you need in Nha Trang?</summary><p>Two nights is the fast minimum. Three or four nights is better if you want Nha Trang to feel like more than an airport-linked beach stop.</p></details>
<details><summary>Is Nha Trang better than Phu Quoc?</summary><p>Nha Trang is better when the beach should stay urban, active, food-led, and connected to Cam Ranh. Phu Quoc is better when the route needs island resort recovery.</p></details>
<details><summary>Where should I stay in Nha Trang?</summary><p>Most first-timers should start near the main beach and Tran Phu corridor. Choose quieter north, Hon Tre, or Cam Ranh/Bai Dai only when the exact hotel and trip job justify less city access.</p></details>
<details><summary>Is Cam Ranh airport close to Nha Trang?</summary><p>It is the airport that serves Nha Trang, but it is not inside the city. Count the transfer, arrival hour, luggage, and hotel location before calling the beach stop easy.</p></details>
<details><summary>Should I book a Nha Trang boat or island day?</summary><p>Only if the day itself is the point. Check weather, marine conditions, operator quality, conservation rules, pickup logistics, and cancellation terms close to travel.</p></details>
<details><summary>Is Nha Trang a quiet beach destination?</summary><p>Usually not in the city center. It can be calmer with the right hotel area or Cam Ranh/Bai Dai resort choice, but Nha Trang's strongest identity is active city beach rather than remote quiet.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then compare beach value in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, island value in <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, and the beach fork in <a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a>. Use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding a south-central beach chapter.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-nha-trang-hero:v1',
    'concierge verdict' => 'vg-nha-trang-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-nha-trang-at-a-glance:v1',
    'photo grid' => 'vg-nha-trang-photo-grid:v1',
    'source diversity' => 'vg-nha-trang-source-diversity:v1',
    'route fit' => 'vg-nha-trang-route-fit:v1',
    'where to stay' => 'vg-nha-trang-where-to-stay:v1',
    'beach areas' => 'vg-nha-trang-beach-areas:v1',
    'priority map' => 'vg-nha-trang-priority-map:v1',
    'season weather' => 'vg-nha-trang-season-weather:v1',
    'transport logistics' => 'vg-nha-trang-transport-logistics:v1',
    'cost booking' => 'vg-nha-trang-cost-booking:v1',
    'skip logic' => 'vg-nha-trang-skip-logic:v1',
    'live checks' => 'vg-nha-trang-live-checks:v1',
    'FAQ' => 'vg-nha-trang-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_nha_trang_ops_assert_required_content_markers($content, $required_content_markers);
vg_nha_trang_ops_assert_internal_page_links_are_published('Nha Trang Travel Guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Nha Trang Travel Guide',
    'post_name'      => 'nha-trang-travel-guide',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_nha_trang_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Nha Trang Travel Guide for international travelers deciding whether Vietnam city-beach time fits the route by Cam Ranh access, hotel area, beach strip, boat days, food, culture, costs, and skip logic.',
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
    vg_nha_trang_ops_fail('Could not publish Nha Trang Travel Guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_nha_trang_ops_fail('Could not publish Nha Trang Travel Guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Nha Trang Travel Guide: Beach, Food, Cam Ranh and Islands');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Nha Trang travel guide: decide if Vietnam city-beach time fits your route, where to stay, Cam Ranh transfers, boat days, costs, and skip logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Nha Trang travel guide');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Nha Trang should be the active city-beach and Cam Ranh access base before booking beach hotels, transfers, boat days, or a south-central coast detour.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 20, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Nha Trang Travel Guide with a concierge verdict, at-a-glance decision table, licensed real photo proof, source-diversity panel, route-fit table, stay-area table, beach/activity map, priority map, season/weather table with January-August and September-December posture, Cam Ranh and transport checks, cost/money/SIM booking checks, Nha Trang Bay marine-protection context, skip logic, live checks, FAQ, Destinations hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Nha Trang - https://vietnam.travel/places-to-go/central-vietnam/nha-trang - checked July 20, 2026\nKhanh Hoa Tourism Portal - English home - https://dulich.khanhhoa.gov.vn/en - checked July 20, 2026\nKhanh Hoa Tourism Portal - Nha Trang city - https://dulich.khanhhoa.gov.vn/en/introduce/administrative-units/nha-trang-city.html - checked July 20, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 20, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 20, 2026\nCam Ranh International Airport - https://www.camranh.aero/en - checked July 20, 2026; live flight and airport-service checks required close to travel\nNational information Website on nature conservation and biodiversity - Nha Trang Bay Nature Reserve - https://en.nbca.gov.vn/khu-du-tru-thien-nhien-vinh-nha-trang-khanh-hoa/ - checked July 20, 2026; marine-condition and operator checks required close to travel\nOfficial Vietnam e-visa portal - https://evisa.gov.vn/ - checked July 20, 2026\nNational Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked July 20, 2026; live weather and marine checks required close to travel\nWikimedia Commons image record - Nha Trang Beach 3 - https://commons.wikimedia.org/wiki/File:Nha_Trang_Beach_3.jpg - license checked July 20, 2026\nWikimedia Commons image record - Panorama of Vinh Nha Trang coastline - https://commons.wikimedia.org/wiki/File:Panorama_of_V%E1%BB%8Bnh_Nha_Trang_coastline.jpg - license checked July 20, 2026\nWikimedia Commons image record - Po Nagar Nha Trang Vietnam - https://commons.wikimedia.org/wiki/File:Po_Nagar_Nha_Trang_Vietnam.JPG - license checked July 20, 2026\nWikimedia Commons image record - Long Son Pagoda 1 - https://commons.wikimedia.org/wiki/File:Long_Son_Pagoda_1.jpg - license checked July 20, 2026\nWikimedia Commons image record - Hon Mun island - https://commons.wikimedia.org/wiki/File:Hon_Mun_island_(H%C3%B2n_Mun),_Cam_Ranh,_Nha_Trang,_Vi%E1%BB%87t_Nam_20140518_105634_(taken_with_Samsung_Galaxy_Note_3).jpg - license checked July 20, 2026\nWikimedia Commons image record - Cam Ranh International Airport Terminal 2 - https://commons.wikimedia.org/wiki/File:Cam_Ranh_International_Airport_Terminal_2.jpg - license checked July 20, 2026\nWikimedia Commons image record - Nha Trang Cathedral - https://commons.wikimedia.org/wiki/File:Nha_Trang_Cathedral.jpg - license checked July 20, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Nha Trang is strongest when it solves a beach-city route problem: Cam Ranh access, food, activity choice, and coastal movement. It is weakest when the trip wants quiet island recovery or when beach time is only decorative.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Nha Trang guide built around city-beach route value rather than generic things-to-do copy.\nConcierge verdict separates active city beach from quiet island recovery.\nAt-a-glance table answers worth, nights, stay area, Cam Ranh friction, and comparison logic.\nLicensed real photo proof for Nha Trang Beach, coastline, Po Nagar, Long Son, Hon Mun, Cam Ranh airport, and Nha Trang Cathedral.\nSource-diversity panel explains what official tourism, local tourism, airport, weather, transport, entry, Nha Trang Bay marine-protection, and image-license sources can and cannot prove.\nRoute-fit table links Nha Trang to 10, 14, 21-day, beach-fork, and family route contexts.\nWhere-to-stay table separates Tran Phu, north city, Hon Tre, Cam Ranh/Bai Dai, and inland/transit choices.\nBeach and activity table prevents one-note beach copy by tying each area to what it changes in the route.\nPriority map forces trip-job choice before adding attractions.\nSeason/weather table keeps south-central coast timing separate from national assumptions and flags Vietnam.travel's January-August and September-December posture.\nTransport/logistics table counts Cam Ranh airport transfer, train, private transfer, city taxis, and boat days.\nCost and booking checks cover room position, transfer cost, boat-day variability, hotel area, and cancellation.\nSkip logic protects quiet-premium, short first-trip, and decorative-beach cases.\nVisible source trail, related routes, and update log.");
$related_routes = "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before adding Nha Trang as a beach-city route chapter.\nBest Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ | Decide whether beach time belongs in the route before choosing Nha Trang, Da Nang, Phu Quoc, Con Dao, or skipping the coast.\nPhu Quoc vs Nha Trang | /compare/phu-quoc-vs-nha-trang/ | Compare active city-beach routing against island resort recovery before paying for beach flights, transfers, and hotels.\nPhu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Use this when the beach chapter should become island resort recovery instead of a city-beach stay.\nBest Islands in Vietnam | /destinations/best-islands-in-vietnam/ | Compare Nha Trang against island choices when the route might need more nature, quiet, or ferry/flight discipline.\nDa Nang Travel Guide | /destinations/da-nang-travel-guide/ | Compare central-coast airport-and-beach base logic before choosing Nha Trang as the city-beach answer.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Decide whether the route should include a south-central coast chapter at all.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check south-central beach timing, weather, and storm pressure before locking hotel or boat days.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Count Cam Ranh transfer time, train/flight options, taxis, luggage, and departure buffers before booking.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget beach hotels, airport transfers, taxis, boat days, cancellation terms, and resort-strip isolation.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Plan VND cash, cards, ATM access, deposits, and backup payment before relying on informal beach, taxi, or activity quotes.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep Cam Ranh pickup, ride-hailing, hotel messaging, maps, and boat-day coordination from becoming a preventable arrival problem.\nVietnam E-Visa | /plan/vietnam-evisa/ | Recheck entry timing before building domestic beach connections around international flights.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a short route should add Nha Trang or cut the beach first.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide whether one beach-city chapter earns its place in a two-week route.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use longer timing to decide whether Nha Trang should be an active coast base or a skipped detour.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check water, boat, scooter, transfer, interruption, and medical coverage before beach travel.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair airport taxis, beach deposits, boat activities, nightlife, and hotel transfers with practical risk checks.";
vg_nha_trang_ops_assert_related_route_meta_links_are_published('Nha Trang Travel Guide related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Nha Trang Beach image: Nha Trang Beach 3 by Christophe95, CC BY-SA 4.0. Body images: Panorama of Vinh Nha Trang coastline by Tony24986, CC BY-SA 4.0; Po Nagar Nha Trang Vietnam by Tervlugt, CC BY 3.0; Long Son Pagoda 1 by Christophe95, CC BY-SA 4.0; Hon Mun island by Nguyen Hung Vu, CC BY 2.0; Cam Ranh International Airport Terminal 2 by Ed Crystal, CC BY-SA 4.0; Nha Trang Cathedral by Vinhtantran, CC BY-SA 3.0.');
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
        vg_nha_trang_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_nha_trang_ops_fail('Nha Trang Travel Guide was updated but is not published.');
}

vg_nha_trang_ops_refresh_destinations_hub();
vg_nha_trang_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_nha_trang_ops_log("Published Nha Trang Travel Guide: {$page_id} {$updated_permalink}");

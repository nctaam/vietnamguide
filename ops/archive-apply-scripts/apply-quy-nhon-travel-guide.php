<?php
/**
 * Publish the Quy Nhon Travel Guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * VG_FORCE_QUY_NHON_GUIDE_REPUBLISH=1 wp eval-file ops/apply-quy-nhon-travel-guide.php --allow-root
 *
 * Repair only hub/related-route side effects with:
 * VG_REPAIR_QUY_NHON_GUIDE_LINKS=1 wp eval-file ops/apply-quy-nhon-travel-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_quy_nhon_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_quy_nhon_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_quy_nhon_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_QUY_NHON_GUIDE_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_quy_nhon_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_QUY_NHON_GUIDE_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_quy_nhon_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_quy_nhon_ops_fail('Could not resolve a valid WordPress author for the Quy Nhon Travel Guide.');
}

function vg_quy_nhon_ops_internal_path_from_href(string $href): ?string
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

function vg_quy_nhon_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_quy_nhon_ops_internal_path_from_href($href);

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
        vg_quy_nhon_ops_fail(implode(PHP_EOL, $failures));
    }

    vg_quy_nhon_ops_log("Validated {$label} internal page links are published.");
}

function vg_quy_nhon_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_quy_nhon_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_quy_nhon_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_quy_nhon_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_quy_nhon_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_quy_nhon_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_quy_nhon_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_quy_nhon_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_quy_nhon_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_quy_nhon_ops_log("Validated {$label} related-route links are published.");
}

function vg_quy_nhon_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_quy_nhon_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_quy_nhon_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

function vg_quy_nhon_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
{
    $marked_block = $marker . "\n" . $block;

    if (str_contains($page->post_content, $marked_block)) {
        vg_quy_nhon_ops_log("Skipped {$label}: current block already present.");
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
            vg_quy_nhon_ops_fail("Could not confidently refresh {$label}.");
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
        vg_quy_nhon_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_quy_nhon_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_quy_nhon_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_quy_nhon_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_quy_nhon_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_quy_nhon_ops_published_page_exists('destinations/quy-nhon-travel-guide')) {
        vg_quy_nhon_ops_log('Skipped Destinations hub refresh: Quy Nhon Travel Guide is not published.');
        return;
    }

    $marker = '<!-- vg-quy-nhon-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use Quy Nhon when the coast should feel quieter without becoming remote</h3><p>The <a href="/destinations/quy-nhon-travel-guide/">Quy Nhon Travel Guide</a> helps travelers decide whether Ky Co, Eo Gio, a low-key city beach, Cham tower context, and Phu Cat airport access make a better mainland-coast chapter than Nha Trang, Mui Ne, Da Nang, or an island detour.</p></div>
<!-- /wp:group -->
HTML;

    vg_quy_nhon_ops_assert_internal_page_links_are_published('Destinations hub Quy Nhon note', $block);
    vg_quy_nhon_ops_upsert_marked_group($hub, 'Destinations hub Quy Nhon note', $marker, $block);
}

function vg_quy_nhon_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_quy_nhon_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_quy_nhon_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_quy_nhon_ops_log("Skipped related-route refresh for {$label}: Quy Nhon Travel Guide line is already current.");
        return;
    }

    $next = implode("\n", $next_lines);
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_quy_nhon_ops_log("Upserted Quy Nhon Travel Guide related route to {$label}: {$page->ID}");
}

function vg_quy_nhon_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Quy Nhon Travel Guide | /destinations/quy-nhon-travel-guide/ | Decide whether Quy Nhon should be the quiet mainland-coast chapter before booking Ky Co, Eo Gio, Phu Cat transfers, or another south-central beach stop.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
            'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
            'destinations/nha-trang-travel-guide' => 'Nha Trang Travel Guide',
            'compare/mui-ne-vs-nha-trang' => 'Mui Ne vs Nha Trang guide',
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
        vg_quy_nhon_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_quy_nhon_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/quy-nhon-travel-guide', OBJECT, 'page');

if (vg_quy_nhon_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_quy_nhon_ops_fail('Repair mode requires the Quy Nhon Travel Guide to already be published.');
    }

    vg_quy_nhon_ops_refresh_destinations_hub();
    vg_quy_nhon_ops_refresh_inbound_related_routes();
    vg_quy_nhon_ops_log("Repaired Quy Nhon Travel Guide side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_quy_nhon_ops_force_republish_enabled()) {
    vg_quy_nhon_ops_fail('Quy Nhon Travel Guide is not a draft. Set VG_FORCE_QUY_NHON_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_quy_nhon_ops_log("Preflight Quy Nhon Travel Guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_quy_nhon_ops_log('Preflight Quy Nhon Travel Guide: no existing page found; creating a child page under /destinations/.');
}

$ky_co_hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f5/Ky_Co_-_Nhon_Ly_-_Quy_Nhon_-_Binh_Dinh_-_Viet_Nam.jpg/1280px-Ky_Co_-_Nhon_Ly_-_Quy_Nhon_-_Binh_Dinh_-_Viet_Nam.jpg');
$ky_co_hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ky_Co_-_Nhon_Ly_-_Quy_Nhon_-_Binh_Dinh_-_Viet_Nam.jpg');
$ky_co_secondary_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6d/Ky_Co_beach%2C_Quy_Nhon_city%2C_Binh_Dinh_province%2C_Vietnam.jpg/1280px-Ky_Co_beach%2C_Quy_Nhon_city%2C_Binh_Dinh_province%2C_Vietnam.jpg');
$ky_co_secondary_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ky_Co_beach,_Quy_Nhon_city,_Binh_Dinh_province,_Vietnam.jpg');
$eo_gio_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/52/Eo_Gi%C3%B3_-_Nh%C6%A1n_L%C3%BD.jpg/1280px-Eo_Gi%C3%B3_-_Nh%C6%A1n_L%C3%BD.jpg');
$eo_gio_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Eo_Gi%C3%B3_-_Nh%C6%A1n_L%C3%BD.jpg');
$quy_nhon_promenade_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/05/Quy_Nhon_Beach_Promenade.jpg/1280px-Quy_Nhon_Beach_Promenade.jpg');
$quy_nhon_promenade_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Quy_Nhon_Beach_Promenade.jpg');
$thap_doi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/8e/0040323_Thap_Doi_Cham_Hindu_complex%2C_Quy_Nhon%2C_Binh_Dinh_Vietnam_185.jpg/1280px-0040323_Thap_Doi_Cham_Hindu_complex%2C_Quy_Nhon%2C_Binh_Dinh_Vietnam_185.jpg');
$thap_doi_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:0040323_Thap_Doi_Cham_Hindu_complex,_Quy_Nhon,_Binh_Dinh_Vietnam_185.jpg');
$thi_nai_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/32/Qui_Nhon_and_Thi_Nai_Bridge_2007-10-03.jpg/1280px-Qui_Nhon_and_Thi_Nai_Bridge_2007-10-03.jpg');
$thi_nai_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Qui_Nhon_and_Thi_Nai_Bridge_2007-10-03.jpg');
$phu_cat_airport_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/4/44/PhuCatAirport_newterminal.jpg');
$phu_cat_airport_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:PhuCatAirport_newterminal.jpg');

$content = <<<HTML
<!-- vg-quy-nhon-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$ky_co_hero_image}","dimRatio":52,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Ky Co beach near Quy Nhon in Binh Dinh, Vietnam" src="{$ky_co_hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 20, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Quy Nhon Travel Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Quy Nhon is Vietnam's quieter mainland-coast answer: a low-key city beach, Ky Co and Eo Gio day trips, Cham tower context, seafood, and Phu Cat airport access without the scale or noise of the country's bigger resort centers.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: choose Quy Nhon for route value, not for a "hidden gem" label. The useful question is whether a calmer mainland coast beats Nha Trang city beach, Mui Ne wind-sport coast, Da Nang convenience, or a true island detour.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$ky_co_hero_credit_url}" target="_blank" rel="license noopener">Boconganh Phan / Public domain</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-quy-nhon-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-quy-nhon-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-quy-nhon-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Choose Quy Nhon when you want a quieter mainland beach chapter with enough city comfort, airport access, and day-trip structure to avoid full island logistics.</strong> Do not choose it when you need heavy nightlife, an easy international resort bubble, guaranteed still-water beach days, or a stop that works without checking Ky Co, Eo Gio, weather, and transfers close to travel.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-quy-nhon-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-quy-nhon-shortlist">
<li><strong>Best fit:</strong> 2 to 4 nights for travelers who want a lower-density coast after central Vietnam or before/after Nha Trang.</li>
<li><strong>Best route pairing:</strong> central-to-south routes where Da Nang/Hoi An already had heritage time and the next coast should feel calmer.</li>
<li><strong>Best hotel logic:</strong> stay in the city/beachfront core for food and movement; use outer beaches only when the exact property is the reason.</li>
<li><strong>Best premium use:</strong> buy a better view, quiet room position, reliable airport transfer, and flexible day-trip terms rather than a longer checklist.</li>
<li><strong>Skip first when:</strong> the trip is short, the central coast already solved beach time, or Ky Co/Eo Gio are the only reason and the forecast is weak.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-quy-nhon-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-quy-nhon-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-quy-nhon-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fast answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-quy-nhon-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Is Quy Nhon worth visiting?</strong></td><td data-label="Answer">Yes when the route needs a quieter mainland coast, local-feeling city base, Ky Co or Eo Gio day, seafood, and fewer big-resort assumptions. It is weaker as a first beach pick if you only have one quick coast stop.</td></tr>
<tr><td data-label="Question"><strong>How many nights?</strong></td><td data-label="Answer">Two nights is the fast minimum. Three nights is the better default if you want one city beach day, one Ky Co/Eo Gio day, and one flexible food or weather buffer.</td></tr>
<tr><td data-label="Question"><strong>Where should first-timers stay?</strong></td><td data-label="Answer">Start in the Quy Nhon city beachfront core. Move outside the city only when the hotel, beach, or resort quiet is more important than easy meals and taxis.</td></tr>
<tr><td data-label="Question"><strong>Best comparison?</strong></td><td data-label="Answer">Compare with Nha Trang if you want active city beach, Mui Ne if wind sports matter, Da Nang if convenience leads, and Phu Quoc or Con Dao if the trip needs a real island chapter.</td></tr>
<tr><td data-label="Question"><strong>Main mistake?</strong></td><td data-label="Answer">Calling Quy Nhon "quiet" without checking where the hotel is, how Ky Co/Eo Gio access works, whether Phu Cat flights fit, and what the weather is doing that week.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-quy-nhon-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what Quy Nhon actually adds</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Quy Nhon is not a generic beach synonym. The useful proof is the mix: clear-water day trips, rocky cape scenery, a real city promenade, Cham heritage, a long lagoon/peninsula approach, and an airport that can simplify or limit the route depending on flight times.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-quy-nhon-photo-grid" aria-label="Quy Nhon travel guide photography">
<figure class="vg-guide-photo"><img src="{$ky_co_secondary_image}" alt="Ky Co beach near Quy Nhon city in Binh Dinh province" loading="lazy" decoding="async"><figcaption>Ky Co is the postcard reason people notice Quy Nhon, but access, fees, sea state, and the exact day plan should still be checked close to travel. Image: <a href="{$ky_co_secondary_credit_url}" target="_blank" rel="license noopener">Le Ho Bac / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$eo_gio_image}" alt="Eo Gio coastal cliffs near Nhon Ly and Quy Nhon" loading="lazy" decoding="async"><figcaption>Eo Gio is a cape-and-viewpoint decision, not a full beach day by itself. Pair it with realistic heat, wind, and transport expectations. Image: <a href="{$eo_gio_credit_url}" target="_blank" rel="license noopener">Hung Ho Ba / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$quy_nhon_promenade_image}" alt="Quy Nhon beachfront promenade and city beach" loading="lazy" decoding="async"><figcaption>The city beach is the practical base: food, walks, taxis, and hotel choice make Quy Nhon easier than a remote-beach-only plan. Image: <a href="{$quy_nhon_promenade_credit_url}" target="_blank" rel="license noopener">VeeWin / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$thap_doi_image}" alt="Thap Doi Cham tower complex in Quy Nhon" loading="lazy" decoding="async"><figcaption>Thap Doi gives Quy Nhon a cultural stop beyond sand, useful when the route needs a lighter heritage layer without adding another city. Image: <a href="{$thap_doi_credit_url}" target="_blank" rel="license noopener">Ms Sarah Welch / CC0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$thi_nai_image}" alt="Quy Nhon and Thi Nai Bridge across the lagoon" loading="lazy" decoding="async"><figcaption>Thi Nai and the Phuong Mai side explain why day-trip timing and drivers matter: the best coast is not always beside the hotel. Image: <a href="{$thi_nai_credit_url}" target="_blank" rel="license noopener">Swaminathan / Teofilo / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$phu_cat_airport_image}" alt="Phu Cat Airport terminal serving Quy Nhon" loading="lazy" decoding="async"><figcaption>Phu Cat airport can make Quy Nhon easy, but schedules and transfer timing are live checks, not evergreen assumptions. Image: <a href="{$phu_cat_airport_credit_url}" target="_blank" rel="license noopener">Uranus2808 / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-quy-nhon-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Source trail: what each source can and cannot prove</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>For Quy Nhon, source discipline matters because many online pages repeat the same "hidden beach" language while the actual decision depends on access, weather, and where you sleep. Use each source for the job it can actually do.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-quy-nhon-source-diversity">
<thead><tr><th>Source type</th><th>Use it for</th><th>Do not use it for</th></tr></thead>
<tbody>
<tr><td data-label="Source type">Vietnam.travel destination source</td><td data-label="Use it for">National tourism orientation to Quy Nhon and why it belongs in the central/south-central coast conversation.</td><td data-label="Do not use it for">Live opening, access, weather, or operator claims.</td></tr>
<tr><td data-label="Source type">Quy Nhon / Binh Dinh official tourism pages</td><td data-label="Use it for">Ky Co, Eo Gio, Quy Nhon beach, Thap Doi, climate baseline, and official attraction context.</td><td data-label="Do not use it for">Assuming every road, boat, fee, crowd, or tour condition is unchanged on travel day.</td></tr>
<tr><td data-label="Source type">ACV Phu Cat airport</td><td data-label="Use it for">Airport identification and official airport context.</td><td data-label="Do not use it for">Assuming your preferred domestic flight, baggage, delay, or transfer is guaranteed.</td></tr>
<tr><td data-label="Source type">Weather and transport sources</td><td data-label="Use it for">Season posture, live weather checks, sea-state caution, and the transfer logic that makes or breaks Ky Co/Eo Gio days.</td><td data-label="Do not use it for">Promising perfect beach weather in any month.</td></tr>
<tr><td data-label="Source type">Image-license records</td><td data-label="Use it for">Visible proof that the guide uses real places and traceable image rights.</td><td data-label="Do not use it for">Current crowding, water clarity, construction, or operator quality.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-quy-nhon-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where Quy Nhon fits in a Vietnam route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Quy Nhon usually works best when the route already has enough classic highlights and needs a calmer coast without adding a far island. It is less useful when the trip still lacks Hanoi, Ninh Binh, Ha Long/Lan Ha, Hoi An, Hue, or a clear departure strategy.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-quy-nhon-route-fit">
<thead><tr><th>Route shape</th><th>Quy Nhon answer</th><th>Risk</th><th>Better next check</th></tr></thead>
<tbody>
<tr><td data-label="Route shape">7 days in Vietnam</td><td data-label="Quy Nhon answer">Usually skip unless the whole trip is central/south-central coast focused.</td><td data-label="Risk">A quiet beach stop can cost too many transfer hours on a first trip.</td><td data-label="Better next check"><a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">10 days in Vietnam</td><td data-label="Quy Nhon answer">Add only if the route intentionally skips a bigger city or island.</td><td data-label="Risk">The stop becomes a nice-looking detour rather than a route improvement.</td><td data-label="Better next check"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">14 days in Vietnam</td><td data-label="Quy Nhon answer">Can work as the quieter coast chapter between central Vietnam and the south.</td><td data-label="Risk">Too many coastal bases: Da Nang, Hoi An beach, Nha Trang, Mui Ne, Quy Nhon, and Phu Quoc compete for the same trip job.</td><td data-label="Better next check"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">21 days in Vietnam</td><td data-label="Quy Nhon answer">Best fit when extra time buys a slower south-central arc rather than more flights.</td><td data-label="Risk">Collecting beaches instead of protecting weather and recovery days.</td><td data-label="Better next check"><a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">Central coast already strong</td><td data-label="Quy Nhon answer">Use it only if Da Nang/Hoi An did not already solve beach time.</td><td data-label="Risk">Duplicating the same coast mood with a weaker logistics reason.</td><td data-label="Better next check"><a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a></td></tr>
<tr><td data-label="Route shape">South-central beach fork</td><td data-label="Quy Nhon answer">Use Quy Nhon when the coast should be quieter and more local-feeling than Nha Trang or Mui Ne.</td><td data-label="Risk">Expecting Nha Trang-level activity or Mui Ne-level sport conditions.</td><td data-label="Better next check"><a href="/compare/mui-ne-vs-nha-trang/">Mui Ne vs Nha Trang</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-quy-nhon-where-to-stay:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where to stay in Quy Nhon</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>For most international travelers, Quy Nhon is easier when the sleeping base is simple. A beautiful outer beach can be a poor first choice if every dinner, pickup, and bad-weather backup becomes a driver negotiation.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-quy-nhon-where-to-stay">
<thead><tr><th>Stay area</th><th>Best for</th><th>Trade-off</th><th>Booking check</th></tr></thead>
<tbody>
<tr><td data-label="Stay area">City beachfront core</td><td data-label="Best for">First-timers, food, walks, taxis, lower friction, and a balanced beach/city stay.</td><td data-label="Trade-off">Less remote and less resort-like than the outer coast.</td><td data-label="Booking check">Exact beach access, sea-view angle, road noise, breakfast, and walking distance to dinner.</td></tr>
<tr><td data-label="Stay area">Quieter city edges</td><td data-label="Best for">Travelers who still want city convenience but prefer less evening noise.</td><td data-label="Trade-off">Some addresses look close on a map but still need taxis in heat or rain.</td><td data-label="Booking check">Taxi availability, nearby meals, and whether the hotel has real beach value.</td></tr>
<tr><td data-label="Stay area">Nhon Ly / Ky Co / Eo Gio side</td><td data-label="Best for">Travelers who want the cape and beach day to shape the stay.</td><td data-label="Trade-off">Fewer easy city-food backups and more dependence on road, boat, fee, and weather conditions.</td><td data-label="Booking check">Access route, inclusions, last return, cancellation, and whether the property still works if the sea day changes.</td></tr>
<tr><td data-label="Stay area">Outer resort beach</td><td data-label="Best for">Couples, families, and premium downtime when the hotel is the point.</td><td data-label="Trade-off">You may lose the practical, local-feeling part of Quy Nhon.</td><td data-label="Booking check">Dining cost, transfer cost, beach condition, construction nearby, and resort cancellation terms.</td></tr>
<tr><td data-label="Stay area">Airport or transit night</td><td data-label="Best for">Awkward Phu Cat arrivals/departures or routes linking by private car.</td><td data-label="Trade-off">Weak destination value if it replaces a real coast night.</td><td data-label="Booking check">Flight time, transfer quote, pickup instructions, and next-day plan.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-quy-nhon-priority-map:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to prioritize in Quy Nhon</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Do not build the stop from a list of attractions. Pick the job first, then choose the day plan that supports it.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-quy-nhon-priority-map">
<thead><tr><th>Trip job</th><th>Prioritize</th><th>Protect</th><th>Only add if...</th></tr></thead>
<tbody>
<tr><td data-label="Trip job">Quiet mainland beach</td><td data-label="Prioritize">City beach, better hotel position, slow mornings, seafood, and one easy viewpoint.</td><td data-label="Protect">Low friction and enough time to rest.</td><td data-label="Only add if...">Ky Co or Eo Gio conditions are good enough to improve the day.</td></tr>
<tr><td data-label="Trip job">Ky Co and Eo Gio day</td><td data-label="Prioritize">Driver/boat clarity, early start, heat management, and flexible expectations.</td><td data-label="Protect">Weather and access checks.</td><td data-label="Only add if...">Fees, sea state, pickup, and return timing are clear.</td></tr>
<tr><td data-label="Trip job">Light culture layer</td><td data-label="Prioritize">Thap Doi, Cham context, and a slower city walk.</td><td data-label="Protect">A real reason beyond another beach photo.</td><td data-label="Only add if...">The route does not already have too much central heritage pressure.</td></tr>
<tr><td data-label="Trip job">South-central route pause</td><td data-label="Prioritize">Beachfront base, Phu Cat transfer, one scenic day, and fewer hotel changes.</td><td data-label="Protect">Pacing between Da Nang/Hoi An and Nha Trang or Ho Chi Minh City.</td><td data-label="Only add if...">It replaces a weaker coast stop instead of stacking more.</td></tr>
<tr><td data-label="Trip job">Premium quiet</td><td data-label="Prioritize">Resort quality, room view, pool, food quality, airport transfer, and cancellation terms.</td><td data-label="Protect">The quiet you are paying for.</td><td data-label="Only add if...">The property is strong enough to carry bad-weather time.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-quy-nhon-season-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best time and weather posture</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Quy Nhon sits on the south-central coast, so do not copy weather advice from Hanoi, Hoi An, Phu Quoc, or the Mekong. Use broad season posture for planning, then check live weather and sea conditions before Ky Co, Eo Gio, boat, or cape-heavy days.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-quy-nhon-season-weather">
<thead><tr><th>Timing</th><th>Planning posture</th><th>What to book</th><th>What to recheck</th></tr></thead>
<tbody>
<tr><td data-label="Timing">March to August posture</td><td data-label="Planning posture">Stronger case for beach, cape, and city movement.</td><td data-label="What to book">A better beach hotel, one flexible Ky Co/Eo Gio day, and a clear airport transfer.</td><td data-label="What to recheck">Heat, sun exposure, wind, sea state, and same-week forecast.</td></tr>
<tr><td data-label="Timing">September to November caution</td><td data-label="Planning posture">Keep the stop flexible and avoid making Ky Co or Eo Gio the only reason the route works.</td><td data-label="What to book">Refundable hotels, backup city/food plans, and fewer non-refundable day trips.</td><td data-label="What to recheck">Rain, storms, road/cape access, and beach safety.</td></tr>
<tr><td data-label="Timing">December to February mixed coast</td><td data-label="Planning posture">Can be pleasant for city and food, but do not promise perfect swim or boat conditions.</td><td data-label="What to book">Hotel comfort and flexible day plans.</td><td data-label="What to recheck">Temperature, wind, rain, and whether beach expectations are realistic.</td></tr>
<tr><td data-label="Timing">Any Ky Co or Eo Gio day</td><td data-label="Planning posture">Treat the day as condition-dependent, not guaranteed.</td><td data-label="What to book">Driver/operator with clear pickup, access, inclusions, and cancellation terms.</td><td data-label="What to recheck">Official tourism page, weather, sea state, fees, and hotel advice close to travel.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-quy-nhon-transport-logistics:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Phu Cat airport, train links, and local movement</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Quy Nhon's biggest hidden cost is not always the hotel. It is the route handoff: flight timing, airport distance, driver quality, train station transfer, and whether day trips sit on the city side or across the Phuong Mai/Nhon Ly side.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-quy-nhon-transport-logistics">
<thead><tr><th>Movement</th><th>Use it when...</th><th>Hidden friction</th><th>Live check</th></tr></thead>
<tbody>
<tr><td data-label="Movement">Fly via Phu Cat</td><td data-label="Use it when...">The route needs a clean coast stop without a long road transfer from Da Nang, Nha Trang, or Ho Chi Minh City.</td><td data-label="Hidden friction">Airport is outside the city; flight frequency, arrival hour, and transfer timing shape the day.</td><td data-label="Live check">ACV airport page, airline schedule, luggage, transfer quote, and hotel pickup instructions.</td></tr>
<tr><td data-label="Movement">Train via Dieu Tri / Quy Nhon connection</td><td data-label="Use it when...">The route is coast-led and schedule fit is better than flying.</td><td data-label="Hidden friction">Station transfer, arrival hour, ticket class, and whether the train actually saves energy.</td><td data-label="Live check">Vietnam Railways ticketing, station transfer, luggage, and backup plan.</td></tr>
<tr><td data-label="Movement">Private car from central/south-central coast</td><td data-label="Use it when...">You are linking Hoi An/Da Nang, Quy Nhon, Nha Trang, or Mui Ne with stops and baggage control.</td><td data-label="Hidden friction">Long driving time, heat, route fatigue, and driver quality.</td><td data-label="Live check">Door-to-door duration, stops, vehicle size, tolls, insurance, and cancellation.</td></tr>
<tr><td data-label="Movement">City taxis / ride-hailing</td><td data-label="Use it when...">You stay in the beachfront core and want simple meals, beach walks, and short city stops.</td><td data-label="Hidden friction">Peak demand, rain, language, and exact hotel pickup point.</td><td data-label="Live check">App availability, hotel help, and cash backup.</td></tr>
<tr><td data-label="Movement">Ky Co / Eo Gio day logistics</td><td data-label="Use it when...">The day itself is the reason to be in Quy Nhon.</td><td data-label="Hidden friction">Road/boat access, fees, weather, heat, return timing, and operator clarity.</td><td data-label="Live check">Official attraction pages, hotel advice, day-trip inclusions, sea state, and refund terms.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-quy-nhon-cost-booking:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cost and booking checks that matter</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-quy-nhon-cost-booking"} -->
<ul class="wp-block-list vg-check-list vg-quy-nhon-cost-booking">
<li>Compare total coast cost: room, airport transfer, taxis, Ky Co/Eo Gio day, meals, private driver, cancellation terms, and the route stop Quy Nhon replaces.</li>
<li>Do not compare a city beachfront hotel with an outer resort as if they solve the same trip job; one buys movement, the other buys quiet.</li>
<li>Check exact beach access and road position. A sea-view room can still sit behind traffic, construction, or a less useful beach stretch.</li>
<li>Ask how Ky Co and Eo Gio access works now: pickup, road or boat, entrance fees, inclusions, lunch, sea conditions, return time, and weather policy.</li>
<li>Protect arrival and departure days if Phu Cat flights land late, depart early, or connect poorly with the rest of the route.</li>
<li>Use <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>, <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before paying deposits or relying on informal transfer and day-trip quotes.</li>
</ul>
<!-- /wp:list -->

<!-- vg-quy-nhon-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first in Quy Nhon</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-quy-nhon-skip-logic">
<thead><tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr></thead>
<tbody>
<tr><td data-label="If you want...">A calm beach reset</td><td data-label="Protect this">Hotel comfort, city beach access, meals, and low-friction days.</td><td data-label="Skip first">Packed day-trip lists and distant stops.</td><td data-label="Only add back when...">The forecast, driver, and route timing make the day easy.</td></tr>
<tr><td data-label="If you want...">Ky Co and Eo Gio</td><td data-label="Protect this">One clear day with early start, heat management, and flexible return.</td><td data-label="Skip first">Extra attractions added only because the driver offers them.</td><td data-label="Only add back when...">You still have enough energy and daylight.</td></tr>
<tr><td data-label="If you want...">A strong first Vietnam route</td><td data-label="Protect this">Hanoi, one northern landscape, central Vietnam, and honest transfer days.</td><td data-label="Skip first">Quy Nhon if beach time is decorative.</td><td data-label="Only add back when...">The route has enough nights and the quiet coast is the point.</td></tr>
<tr><td data-label="If you want...">Active city beach</td><td data-label="Protect this">Restaurants, nightlife, activities, and easy movement.</td><td data-label="Skip first">Quy Nhon as the main beach city.</td><td data-label="Only add back when...">You deliberately prefer quieter city rhythm over Nha Trang energy.</td></tr>
<tr><td data-label="If you want...">Island resort recovery</td><td data-label="Protect this">Full resort downtime and fewer daily decisions.</td><td data-label="Skip first">Quy Nhon city.</td><td data-label="Only add back when...">A specific outer resort beats Phu Quoc, Con Dao, or a central-coast hotel for your dates.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-quy-nhon-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Official checks before locking Quy Nhon</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-quy-nhon-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-quy-nhon-live-checks">
<li>Use <a href="https://vietnam.travel/node/1453" target="_blank" rel="noopener">Vietnam.travel Quy Nhon</a> plus the <a href="https://dulichquynhon.binhdinh.gov.vn/en/introduction" target="_blank" rel="noopener">official Quy Nhon / Binh Dinh tourism introduction</a> for destination orientation and climate context.</li>
<li>Use the official attraction pages for <a href="https://dulichquynhon.binhdinh.gov.vn/en/baikyco" target="_blank" rel="noopener">Ky Co</a>, <a href="https://dulichquynhon.binhdinh.gov.vn/en/eogiolandscape" target="_blank" rel="noopener">Eo Gio</a>, <a href="https://dulichquynhon.binhdinh.gov.vn/en/bienquynhon" target="_blank" rel="noopener">Quy Nhon beach</a>, and <a href="https://dulichquynhon.binhdinh.gov.vn/vi/thapdoiquynhon" target="_blank" rel="noopener">Thap Doi</a>; recheck access, fees, tours, and weather close to travel.</li>
<li>Use <a href="https://acv.vn/en/uih" target="_blank" rel="noopener">ACV Phu Cat Airport</a>, <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport</a>, and <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before treating Quy Nhon as an easy add-on.</li>
<li>Use <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> plus <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a> before locking beach, cape, boat, road, or airport-buffer assumptions.</li>
<li>Use the <a href="https://evisa.gov.vn/" target="_blank" rel="noopener">official Vietnam e-visa portal</a> and <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a> before building domestic coast connections around international arrival assumptions.</li>
<li>Compare <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, <a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a>, <a href="/compare/mui-ne-vs-nha-trang/">Mui Ne vs Nha Trang</a>, and <a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a> before treating Quy Nhon as the default coast answer.</li>
</ul>
<!-- /wp:list -->

<!-- vg-quy-nhon-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Quy Nhon Travel Guide FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-quy-nhon-faq">
<details><summary>Is Quy Nhon worth visiting for first-time visitors?</summary><p>Yes if the route has enough time for a quieter mainland-coast chapter. It is strongest for travelers who want city comfort, beach access, Ky Co/Eo Gio, and fewer resort-center crowds. It is not automatic on short first trips.</p></details>
<details><summary>How many days do you need in Quy Nhon?</summary><p>Two nights is the fast minimum. Three nights is better because it gives you one city beach day, one Ky Co or Eo Gio day, and one buffer for weather, food, or slower movement.</p></details>
<details><summary>Is Quy Nhon better than Nha Trang?</summary><p>Quy Nhon is better when you want quieter mainland coast, lower-density city rhythm, and a more local-feeling stop. Nha Trang is better when you want active city beach, more nightlife, more boat-day infrastructure, and Cam Ranh access.</p></details>
<details><summary>Is Quy Nhon better than Da Nang?</summary><p>Da Nang is easier for most first-time central Vietnam routes because the airport, My Khe beach, Hoi An, Hue, and hotel range fit together cleanly. Quy Nhon is better when you want a calmer south-central coast chapter and have enough time to justify the extra routing.</p></details>
<details><summary>Where should I stay in Quy Nhon?</summary><p>Most first-timers should start in the city beachfront core. Choose Nhon Ly, Ky Co/Eo Gio side, or an outer resort only when the exact beach, property, and daily movement trade-offs are clear.</p></details>
<details><summary>Can you visit Ky Co and Eo Gio in one day?</summary><p>Often yes, but treat it as a live-check day. Confirm pickup, road or boat access, fees, inclusions, weather, heat, sea state, and return timing before paying.</p></details>
<details><summary>What is the best time to visit Quy Nhon?</summary><p>Broadly, March to August usually gives the stronger beach posture, while September to November needs more caution. Even in good months, check live forecasts and sea conditions before beach, boat, or cape-heavy days.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then test coast value in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>. Compare Quy Nhon against <a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a>, <a href="/compare/mui-ne-vs-nha-trang/">Mui Ne vs Nha Trang</a>, <a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a>, and <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>. Use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding a south-central coast chapter.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-quy-nhon-hero:v1',
    'concierge verdict' => 'vg-quy-nhon-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-quy-nhon-at-a-glance:v1',
    'photo grid' => 'vg-quy-nhon-photo-grid:v1',
    'source diversity' => 'vg-quy-nhon-source-diversity:v1',
    'route fit' => 'vg-quy-nhon-route-fit:v1',
    'where to stay' => 'vg-quy-nhon-where-to-stay:v1',
    'priority map' => 'vg-quy-nhon-priority-map:v1',
    'season weather' => 'vg-quy-nhon-season-weather:v1',
    'transport logistics' => 'vg-quy-nhon-transport-logistics:v1',
    'cost booking' => 'vg-quy-nhon-cost-booking:v1',
    'skip logic' => 'vg-quy-nhon-skip-logic:v1',
    'live checks' => 'vg-quy-nhon-live-checks:v1',
    'FAQ' => 'vg-quy-nhon-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_quy_nhon_ops_assert_required_content_markers($content, $required_content_markers);
vg_quy_nhon_ops_assert_internal_page_links_are_published('Quy Nhon Travel Guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Quy Nhon Travel Guide',
    'post_name'      => 'quy-nhon-travel-guide',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_quy_nhon_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Quy Nhon Travel Guide for international travelers deciding whether a quiet mainland-coast chapter fits the route by Ky Co, Eo Gio, stay area, Phu Cat access, season, costs, and skip logic.',
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
    vg_quy_nhon_ops_fail('Could not publish Quy Nhon Travel Guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_quy_nhon_ops_fail('Could not publish Quy Nhon Travel Guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Quy Nhon Travel Guide: Quiet Beaches, Ky Co, Eo Gio and Routes');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Quy Nhon travel guide: decide if Vietnam quiet mainland-coast time fits your route, where to stay, Ky Co, Eo Gio, Phu Cat transfers, costs, and skip logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Quy Nhon travel guide');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Quy Nhon should be the quiet mainland-coast chapter before booking Ky Co, Eo Gio, city beach hotels, Phu Cat transfers, or another south-central beach stop.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 20, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Quy Nhon Travel Guide with a concierge verdict, at-a-glance decision table, licensed real photo proof, source-diversity panel, route-fit table, stay-area table, priority map, season/weather posture, Phu Cat and transport checks, cost/money/SIM booking checks, skip logic, live official checks, FAQ, Destinations hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Quy Nhon - https://vietnam.travel/node/1453 - checked July 20, 2026\nOfficial Quy Nhon / Binh Dinh tourism introduction - https://dulichquynhon.binhdinh.gov.vn/en/introduction - checked July 20, 2026; official climate and destination context\nOfficial Quy Nhon / Binh Dinh tourism - Ky Co - https://dulichquynhon.binhdinh.gov.vn/en/baikyco - checked July 20, 2026; access, fees, and sea conditions require live checks close to travel\nOfficial Quy Nhon / Binh Dinh tourism - Eo Gio Landscape - https://dulichquynhon.binhdinh.gov.vn/en/eogiolandscape - checked July 20, 2026; access and weather require live checks close to travel\nOfficial Quy Nhon / Binh Dinh tourism - Quy Nhon Beach - https://dulichquynhon.binhdinh.gov.vn/en/bienquynhon - checked July 20, 2026\nOfficial Quy Nhon / Binh Dinh tourism - Thap Doi Quy Nhon - https://dulichquynhon.binhdinh.gov.vn/vi/thapdoiquynhon - checked July 20, 2026; Vietnamese-language official source used for heritage context\nACV - Phu Cat Airport - https://acv.vn/en/uih - checked July 20, 2026; live flight schedule and transfer checks required close to travel\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 20, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 20, 2026\nVietnam Railways official railway website - https://vr.com.vn/en - checked July 20, 2026\nVietnam Railways official ticketing and fare lookup - https://dsvn.vn/ - checked July 20, 2026\nOfficial Vietnam e-visa portal - https://evisa.gov.vn/ - checked July 20, 2026\nNational Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked July 20, 2026; live weather and marine checks required close to travel\nWikimedia Commons image record - Ky Co - Nhon Ly - Quy Nhon - Binh Dinh - Viet Nam - https://commons.wikimedia.org/wiki/File:Ky_Co_-_Nhon_Ly_-_Quy_Nhon_-_Binh_Dinh_-_Viet_Nam.jpg - license checked July 20, 2026\nWikimedia Commons image record - Ky Co beach, Quy Nhon city, Binh Dinh province, Vietnam - https://commons.wikimedia.org/wiki/File:Ky_Co_beach,_Quy_Nhon_city,_Binh_Dinh_province,_Vietnam.jpg - license checked July 20, 2026\nWikimedia Commons image record - Eo Gio - Nhon Ly - https://commons.wikimedia.org/wiki/File:Eo_Gi%C3%B3_-_Nh%C6%A1n_L%C3%BD.jpg - license checked July 20, 2026\nWikimedia Commons image record - Quy Nhon Beach Promenade - https://commons.wikimedia.org/wiki/File:Quy_Nhon_Beach_Promenade.jpg - license checked July 20, 2026\nWikimedia Commons image record - Thap Doi Cham Hindu complex, Quy Nhon, Binh Dinh Vietnam 185 - https://commons.wikimedia.org/wiki/File:0040323_Thap_Doi_Cham_Hindu_complex,_Quy_Nhon,_Binh_Dinh_Vietnam_185.jpg - license checked July 20, 2026\nWikimedia Commons image record - Qui Nhon and Thi Nai Bridge 2007-10-03 - https://commons.wikimedia.org/wiki/File:Qui_Nhon_and_Thi_Nai_Bridge_2007-10-03.jpg - license checked July 20, 2026\nWikimedia Commons image record - PhuCatAirport newterminal - https://commons.wikimedia.org/wiki/File:PhuCatAirport_newterminal.jpg - license checked July 20, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Quy Nhon is strongest when it solves a route problem: quieter mainland coast, lower-density city rhythm, Ky Co/Eo Gio access, and a rest stop that does not require island logistics. It is weakest when added to a tight route because a quiet beach sounds desirable.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Quy Nhon guide built around quiet mainland-coast route value rather than hidden-gem or generic beach copy.\nConcierge verdict separates quiet coast, city beach practicality, Ky Co/Eo Gio day-trip dependency, Phu Cat logistics, and skip cases.\nAt-a-glance table answers worth, nights, stay area, comparison logic, and main booking mistake.\nLicensed real photo proof for Ky Co, Eo Gio, city beachfront, Thap Doi, Thi Nai/Phuong Mai approach, and Phu Cat airport.\nSource-diversity panel explains what national tourism, local tourism, airport, weather, transport, rail, e-visa, and image-license sources can and cannot prove.\nRoute-fit table links Quy Nhon to 7, 10, 14, 21-day, central-coast, and south-central beach-fork contexts.\nWhere-to-stay table separates city beachfront, quieter city edges, Nhon Ly/Ky Co/Eo Gio side, outer resort beach, and transit stays by trip job.\nPriority map prevents attraction-list behavior by tying each day to a route job.\nSeason/weather table keeps south-central coast timing separate from national assumptions and flags condition-dependent beach/cape days.\nTransport/logistics table counts Phu Cat airport, rail, private car, city taxis, and Ky Co/Eo Gio day logistics.\nCost and booking checks cover hotel position, transfers, day-trip access, fees, cancellation, and payment/connectivity backups.\nSkip logic protects tight first trips, wrong beach expectations, and duplicate coast stops.\nVisible source trail, related routes, and update log.");
$related_routes = "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before adding Quy Nhon as a quiet mainland-coast chapter.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Compare Quy Nhon against the broader destination shortlist before adding another coast stop.\nBest Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ | Decide whether beach time belongs in the route before choosing Quy Nhon, Nha Trang, Da Nang, Mui Ne, Phu Quoc, or Con Dao.\nNha Trang Travel Guide | /destinations/nha-trang-travel-guide/ | Compare active city-beach routing against Quy Nhon's quieter mainland-coast value.\nMui Ne vs Nha Trang | /compare/mui-ne-vs-nha-trang/ | Use this when the beach fork is sport-first coast versus active city beach before deciding whether Quy Nhon is the quieter third answer.\nPhu Quoc vs Nha Trang | /compare/phu-quoc-vs-nha-trang/ | Compare island resort recovery with active city beach before using Quy Nhon as a lower-friction mainland alternative.\nDa Nang Travel Guide | /destinations/da-nang-travel-guide/ | Compare airport-and-beach convenience before routing farther down the coast.\nDa Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Check whether central Vietnam already solves beach and base logic before adding Quy Nhon.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Decide whether the route should include a south-central coast chapter at all.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check south-central coast timing, weather, and storm pressure before locking hotel or cape days.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Count Phu Cat airport transfers, rail options, private drivers, and Ky Co/Eo Gio logistics before booking.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget beach hotels, airport transfers, drivers, day trips, cancellation terms, and route replacement cost.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Plan VND cash, cards, deposits, and backup payment before relying on informal day-trip or taxi quotes.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep Phu Cat pickup, hotel messaging, maps, ride-hailing, and day-trip coordination from becoming preventable friction.\nVietnam E-Visa | /plan/vietnam-evisa/ | Recheck entry timing before building domestic coast connections around international flights.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a short route should add Quy Nhon or cut beach time first.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide whether two weeks can protect a quiet mainland-coast chapter without weakening the route.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use longer timing to decide whether Quy Nhon should slow the south-central coast instead of adding another flight.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check water, scooter, heat, transfer, interruption, and medical coverage before coast travel.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair airport taxis, beach deposits, day-trip quotes, scooters, and cancellation terms with practical risk checks.";
vg_quy_nhon_ops_assert_related_route_meta_links_are_published('Quy Nhon Travel Guide related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero image: Ky Co - Nhon Ly - Quy Nhon - Binh Dinh - Viet Nam by Boconganh Phan, Public domain. Body images: Ky Co beach by Le Ho Bac, CC BY-SA 4.0; Eo Gio - Nhon Ly by Hung Ho Ba, CC BY 2.0; Quy Nhon Beach Promenade by VeeWin, CC BY-SA 4.0; Thap Doi Cham Hindu complex by Ms Sarah Welch, CC0; Qui Nhon and Thi Nai Bridge by Swaminathan / Teofilo, CC BY 2.0; PhuCatAirport newterminal by Uranus2808, CC BY-SA 4.0.');
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
        vg_quy_nhon_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_quy_nhon_ops_fail('Quy Nhon Travel Guide was updated but is not published.');
}

vg_quy_nhon_ops_refresh_destinations_hub();
vg_quy_nhon_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_quy_nhon_ops_log("Published Quy Nhon Travel Guide: {$page_id} {$updated_permalink}");

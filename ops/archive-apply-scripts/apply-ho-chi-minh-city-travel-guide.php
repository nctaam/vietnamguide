<?php
/**
 * Publish the Ho Chi Minh City Travel Guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * VG_FORCE_HCMC_GUIDE_REPUBLISH=1 wp eval-file ops/apply-ho-chi-minh-city-travel-guide.php --allow-root
 *
 * Repair only hub/related-route side effects with:
 * VG_REPAIR_HCMC_GUIDE_LINKS=1 wp eval-file ops/apply-ho-chi-minh-city-travel-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_hcmc_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_hcmc_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_hcmc_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_HCMC_GUIDE_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_hcmc_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_HCMC_GUIDE_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_hcmc_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_hcmc_ops_fail('Could not resolve a valid WordPress author for the Ho Chi Minh City Travel Guide.');
}

function vg_hcmc_ops_internal_path_from_href(string $href): ?string
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

function vg_hcmc_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_hcmc_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_hcmc_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hcmc_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_hcmc_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_hcmc_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_hcmc_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_hcmc_ops_log("Validated {$label} internal page links are published.");
}

function vg_hcmc_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_hcmc_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_hcmc_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_hcmc_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_hcmc_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_hcmc_ops_log("Validated {$label} related-route links are published.");
}

function vg_hcmc_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_hcmc_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
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
            vg_hcmc_ops_fail("Could not confidently refresh {$label}.");
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
        vg_hcmc_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_hcmc_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_hcmc_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_hcmc_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_hcmc_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_hcmc_ops_published_page_exists('destinations/ho-chi-minh-city-travel-guide')) {
        vg_hcmc_ops_log('Skipped Destinations hub refresh: Ho Chi Minh City Travel Guide is not published.');
        return;
    }

    $marker = '<!-- vg-hcmc-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use Ho Chi Minh City when the south needs its own chapter</h3><p>The <a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a> helps travelers decide whether HCMC should be a real southern base with districts, food, museums, Tan Son Nhat access, Cu Chi or Mekong logic, and enough nights to avoid becoming a token airport stop.</p></div>
<!-- /wp:group -->
HTML;

    vg_hcmc_ops_assert_internal_page_links_are_published('Destinations hub HCMC note', $block);
    vg_hcmc_ops_upsert_marked_group($hub, 'Destinations hub HCMC note', $marker, $block);
}

function vg_hcmc_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hcmc_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_hcmc_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_hcmc_ops_log("Skipped related-route refresh for {$label}: HCMC line is already current.");
        return;
    }

    update_post_meta($page->ID, 'vg_eeat_related_routes', implode("\n", $next_lines));

    vg_hcmc_ops_log("Upserted Ho Chi Minh City Travel Guide related route to {$label}: {$page->ID}");
}

function vg_hcmc_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Ho Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Decide whether the city should anchor the south as a real chapter with district choice, airport access, food, museums, and optional Mekong or Cu Chi side trips.';

    foreach (
        [
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
            'destinations/phu-quoc-travel-guide' => 'Phu Quoc Travel Guide',
            'destinations/con-dao-travel-guide' => 'Con Dao Travel Guide',
        ] as $path => $label
    ) {
        vg_hcmc_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

function vg_hcmc_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_hcmc_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_hcmc_ops_fail('Could not find published /destinations/ parent page.');
}

$page = get_page_by_path('destinations/ho-chi-minh-city-travel-guide', OBJECT, 'page');

if (vg_hcmc_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hcmc_ops_fail('Repair mode requires the Ho Chi Minh City Travel Guide to already be published.');
    }

    vg_hcmc_ops_refresh_destinations_hub();
    vg_hcmc_ops_refresh_inbound_related_routes();
    vg_hcmc_ops_log("Repaired Ho Chi Minh City Travel Guide side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_hcmc_ops_force_republish_enabled()) {
    vg_hcmc_ops_fail('Ho Chi Minh City Travel Guide is not a draft. Set VG_FORCE_HCMC_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_hcmc_ops_log("Preflight Ho Chi Minh City Travel Guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_hcmc_ops_log('Preflight Ho Chi Minh City Travel Guide: no existing page found; creating a child page under /destinations/.');
}

$review_date = 'July 22, 2026';
$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1280px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg');
$market_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/c8/Ben_Thanh_Market%2C_2023_%2803%29.jpg/1280px-Ben_Thanh_Market%2C_2023_%2803%29.jpg');
$market_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ben_Thanh_Market,_2023_(03).jpg');
$post_office_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/7/78/Ho_Chi_Minh_City%2C_Central_Post_Office%2C_2020-01_CN-01.jpg/1280px-Ho_Chi_Minh_City%2C_Central_Post_Office%2C_2020-01_CN-01.jpg');
$post_office_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_Central_Post_Office,_2020-01_CN-01.jpg');
$war_museum_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/4/47/War_Remnants_Museum%2C_HCMC%2C_front.JPG/1280px-War_Remnants_Museum%2C_HCMC%2C_front.JPG');
$war_museum_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:War_Remnants_Museum,_HCMC,_front.JPG');
$reunification_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/Reunification_Palace%2C_Ho_Chi_Minh_City%2C_Vietnam.jpg/1280px-Reunification_Palace%2C_Ho_Chi_Minh_City%2C_Vietnam.jpg');
$reunification_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Reunification_Palace,_Ho_Chi_Minh_City,_Vietnam.jpg');
$cu_chi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f0/Cu_Chi_Tunnels%2C_Ho_Chi_Minh_City%2C_Vietnam_%2849579798986%29.jpg/1280px-Cu_Chi_Tunnels%2C_Ho_Chi_Minh_City%2C_Vietnam_%2849579798986%29.jpg');
$cu_chi_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cu_Chi_Tunnels,_Ho_Chi_Minh_City,_Vietnam_(49579798986).jpg');
$mekong_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1280px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg');
$mekong_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg');

$content = <<<HTML
<!-- vg-hcmc-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Ho Chi Minh City Hall and Nguyen Hue walking street at night" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated {$review_date}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Ho Chi Minh City Travel Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Ho Chi Minh City is Vietnam's southern-city decision: airport access, food, markets, modern districts, war-history museums, French-era architecture, nightlife, and optional Cu Chi or Mekong logic. It is strongest when the south becomes a real chapter, not when HCMC is only a final flight night.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: build HCMC around a base, not a checklist. The best version protects one central district, one serious history block, one food/market layer, and enough margin for Tan Son Nhat, Cu Chi, Mekong, or a southern island connection.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-hcmc-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-hcmc-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-hcmc-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Choose Ho Chi Minh City when the route needs southern energy, food, museums, markets, strong airport access, and a launch point for the Mekong, Cu Chi, Phu Quoc, or Con Dao.</strong> Skip or compress it when a short first trip already has Hanoi, Ninh Binh, bay scenery, and central Vietnam protected.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-hcmc-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-hcmc-shortlist">
<li><strong>Best fit:</strong> 2 to 4 nights for a real southern city chapter, open-jaw route, food/history focus, or Mekong/island gateway.</li>
<li><strong>Best first base:</strong> District 1 or the immediate central edge when first-time logistics, restaurants, sights, and airport movement matter.</li>
<li><strong>Best premium use:</strong> buy a quieter room, walkable district, airport transfer certainty, and a guided history or food block rather than more rushed attractions.</li>
<li><strong>Best skip rule:</strong> do not add HCMC only to say the route covered north, central, and south.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-hcmc-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-hcmc-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-hcmc-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fast answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hcmc-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Is Ho Chi Minh City worth visiting?</strong></td><td data-label="Answer">Yes when the south needs food, energy, history, markets, nightlife, business-hotel comfort, or a Mekong/island gateway. No when it is only a token final night.</td></tr>
<tr><td data-label="Question"><strong>How many nights?</strong></td><td data-label="Answer">Two nights works as a fast city chapter. Three nights is the best first answer. Four nights fits food, museums, Cu Chi or Mekong, and a calmer airport buffer.</td></tr>
<tr><td data-label="Question"><strong>Where should first-timers stay?</strong></td><td data-label="Answer">District 1 or the nearby central edge is the cleanest default. Choose Thao Dien, District 3, or river/business areas only when the exact trip job justifies less sight density.</td></tr>
<tr><td data-label="Question"><strong>What is the main mistake?</strong></td><td data-label="Answer">Planning HCMC as a single airport night after a full north-and-central route, then trying to add museums, food, markets, Cu Chi, and the Mekong anyway.</td></tr>
<tr><td data-label="Question"><strong>What should be checked live?</strong></td><td data-label="Answer">Airport transfer timing, museum opening hours, weather/heat, day-trip operator terms, e-visa entry timing, phone data, and cash/card fallback.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-hcmc-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what HCMC changes in the route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The images are planning evidence. HCMC is not one sight; it is a city-base, history, market, airport, day-trip, and southern-extension decision.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-hcmc-photo-grid" aria-label="Ho Chi Minh City travel guide photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Ho Chi Minh City Hall and Nguyen Hue walking street at night" loading="lazy" decoding="async"><figcaption>Nguyen Hue and the central core show why HCMC works as a city chapter, not just an airport. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$market_image}" alt="Ben Thanh Market in Ho Chi Minh City" loading="lazy" decoding="async"><figcaption>Markets add value when cash, phone, and price habits are simple. Image: <a href="{$market_credit_url}" target="_blank" rel="license noopener">Bahnfrend / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$post_office_image}" alt="Saigon Central Post Office in Ho Chi Minh City" loading="lazy" decoding="async"><figcaption>The central heritage walk is compact enough to pair with food and museum time. Image: <a href="{$post_office_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$war_museum_image}" alt="War Remnants Museum exterior in Ho Chi Minh City" loading="lazy" decoding="async"><figcaption>War-history sites deserve a protected, serious block rather than a rushed checklist. Image: <a href="{$war_museum_credit_url}" target="_blank" rel="license noopener">Prenn / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$reunification_image}" alt="Reunification Palace in Ho Chi Minh City" loading="lazy" decoding="async"><figcaption>Reunification Palace helps explain why HCMC is a history city as well as a food city. Image: <a href="{$reunification_credit_url}" target="_blank" rel="license noopener">Eustaquio Santimano / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cu_chi_image}" alt="Cu Chi Tunnels near Ho Chi Minh City" loading="lazy" decoding="async"><figcaption>Cu Chi should be a deliberate history day, not an automatic add-on after every city stay. Image: <a href="{$cu_chi_credit_url}" target="_blank" rel="license noopener">flowcomm / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mekong_image}" alt="Small boat on a river in the Mekong Delta" loading="lazy" decoding="async"><figcaption>The Mekong should replace a weaker add-on, not hide inside departure day. Image: <a href="{$mekong_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-hcmc-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Source diversity: what each source can prove</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hcmc-source-diversity">
<thead><tr><th>Source job</th><th>Primary use</th><th>How to read it</th></tr></thead>
<tbody>
<tr><td data-label="Source job">Official destination frame</td><td data-label="Primary use"><a href="https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city" target="_blank" rel="noopener">Vietnam.travel Ho Chi Minh City</a> and <a href="https://visithcmc.vn" target="_blank" rel="noopener">Visit HCMC</a>.</td><td data-label="How to read it">Good for orientation and official visitor framing; not enough for choosing your exact hotel or day sequence.</td></tr>
<tr><td data-label="Source job">Airport and arrival movement</td><td data-label="Primary use"><a href="https://vietnamairport.vn/en/tan-son-nhat-airport" target="_blank" rel="noopener">ACV Tan Son Nhat</a>, <a href="https://acv.vn/en/airports/tan-son-nhat-international-airport" target="_blank" rel="noopener">ACV airport profile</a>, and <a href="https://www.vietnamairlines.com/ca/en/plan-book/travel/travel-guide/airport-ho-chi-minh-to-city" target="_blank" rel="noopener">Vietnam Airlines airport-to-city guide</a>.</td><td data-label="How to read it">Airport convenience is useful only after terminal, arrival hour, luggage, hotel area, and transfer backup are counted.</td></tr>
<tr><td data-label="Source job">Weather and route season</td><td data-label="Primary use"><a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather</a> and <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a>.</td><td data-label="How to read it">Use broad dry/rainy posture for planning, then check heat, rain, and storm warnings near travel.</td></tr>
<tr><td data-label="Source job">Entry and domestic movement</td><td data-label="Primary use"><a href="https://evisa.gov.vn/" target="_blank" rel="noopener">Official Vietnam e-visa portal</a>, <a href="https://vietnam.travel/plan-your-trip/getting-vietnam" target="_blank" rel="noopener">getting to Vietnam</a>, and <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">transport within Vietnam</a>.</td><td data-label="How to read it">Use these before connecting international arrival, city time, Mekong, Phu Quoc, Con Dao, or onward flights.</td></tr>
<tr><td data-label="Source job">Image proof</td><td data-label="Primary use">Wikimedia Commons records for City Hall, Ben Thanh, Central Post Office, War Remnants Museum, Reunification Palace, Cu Chi, and Mekong visuals.</td><td data-label="How to read it">Images show place character; they do not prove current crowd, museum hours, traffic, water level, or operator quality.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hcmc-districts:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where to stay in Ho Chi Minh City</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Pick the base by friction. The wrong district can turn a good city into taxis, heat, and admin; the right one makes food, museums, airport access, and day trips easier to sequence.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hcmc-districts">
<thead><tr><th>Area</th><th>Best for</th><th>Watch for</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Area">District 1 core</td><td data-label="Best for">First-timers, short stays, sights, restaurants, walking loops, and easier ride-hailing.</td><td data-label="Watch for">Noise, nightlife streets, room position, and whether the hotel is central or only marketed as central.</td><td data-label="VietnamGuide verdict">Default first base.</td></tr>
<tr><td data-label="Area">District 3 / central edge</td><td data-label="Best for">Quieter city feel, cafes, museums, good local movement, and lower nightlife pressure.</td><td data-label="Watch for">More taxis for some classic sights and exact walking comfort in heat or rain.</td><td data-label="VietnamGuide verdict">Best upgrade when you want central but calmer.</td></tr>
<tr><td data-label="Area">Riverside / business hotel zone</td><td data-label="Best for">Premium stays, views, business comfort, polished service, and easier airport-transfer handling.</td><td data-label="Watch for">Price, sterile evenings, and whether the neighborhood fits your food plan.</td><td data-label="VietnamGuide verdict">Good when service and sleep quality matter.</td></tr>
<tr><td data-label="Area">Thao Dien / expat east</td><td data-label="Best for">Longer stays, restaurants, cafes, apartments, families, and travelers who have already seen the city core.</td><td data-label="Watch for">Less classic sightseeing density and more transport time.</td><td data-label="VietnamGuide verdict">Useful for repeat or slower stays, not the default first answer.</td></tr>
<tr><td data-label="Area">Airport-side practical night</td><td data-label="Best for">Late arrival, early departure, separate-ticket protection, or one-night reset.</td><td data-label="Watch for">It rarely gives a meaningful HCMC chapter.</td><td data-label="VietnamGuide verdict">Use only when flight timing is the point.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hcmc-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">When HCMC fits a Vietnam itinerary</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hcmc-route-fit">
<thead><tr><th>Route context</th><th>Use HCMC when...</th><th>Protect this</th><th>Cut first</th></tr></thead>
<tbody>
<tr><td data-label="Route context">7 days</td><td data-label="Use HCMC when...">The trip is south-first: city, food, Mekong, and maybe one slower southern module.</td><td data-label="Protect this">One region and one base logic.</td><td data-label="Cut first">A token Hanoi or central flight hop.</td></tr>
<tr><td data-label="Route context">10 days</td><td data-label="Use HCMC when...">Open-jaw flights save a real day or the south is a personal priority.</td><td data-label="Protect this">Two HCMC nights or a clean arrival/departure role.</td><td data-label="Cut first">Mekong, Phu Quoc, or another region if HCMC is only decorative.</td></tr>
<tr><td data-label="Route context">14 days</td><td data-label="Use HCMC when...">The route can hold north, central, and a real southern chapter.</td><td data-label="Protect this">Three nights or two nights plus one targeted extension.</td><td data-label="Cut first">A second beach/island module that weakens the city.</td></tr>
<tr><td data-label="Route context">21 days</td><td data-label="Use HCMC when...">The full-country route needs a food/history city chapter and a gateway to Mekong, Phu Quoc, or Con Dao.</td><td data-label="Protect this">Exit buffer and one southern extension only.</td><td data-label="Cut first">Stacking Mekong, Cu Chi, Phu Quoc, Con Dao, and Nha Trang without margin.</td></tr>
<tr><td data-label="Route context">South-only trip</td><td data-label="Use HCMC when...">The city anchors food, history, arrival, and river/island movement.</td><td data-label="Protect this">District choice and a slower city rhythm.</td><td data-label="Cut first">North/central token stops.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hcmc-priority-map:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to prioritize first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>HCMC has more good options than most first trips can use. Start with the city job, then add blocks that support it.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hcmc-priority-map">
<thead><tr><th>Trip job</th><th>Prioritize</th><th>Skip first</th><th>Why</th></tr></thead>
<tbody>
<tr><td data-label="Trip job">First southern city chapter</td><td data-label="Prioritize">Central base, Nguyen Hue, Central Post Office, one museum/history block, one food or market block.</td><td data-label="Skip first">Both Cu Chi and Mekong if nights are short.</td><td data-label="Why">The city needs protected time before excursions.</td></tr>
<tr><td data-label="Trip job">Food and markets</td><td data-label="Prioritize">District 1/3 base, guided food block, Ben Thanh or neighborhood market, cafe time, and walking/ride-hail ease.</td><td data-label="Skip first">Distant history excursions on the same day.</td><td data-label="Why">Food gets better when the day is not over-scheduled.</td></tr>
<tr><td data-label="Trip job">History-heavy stay</td><td data-label="Prioritize">War Remnants Museum, Reunification Palace, Central Post Office, and a slower guide-led context block.</td><td data-label="Skip first">Nightlife-heavy evening before museum day.</td><td data-label="Why">Serious sites deserve energy and pacing.</td></tr>
<tr><td data-label="Trip job">Mekong or island gateway</td><td data-label="Prioritize">Airport transfer, luggage plan, one city night, and a clean onward move.</td><td data-label="Skip first">Extra city sights that risk the departure chain.</td><td data-label="Why">The gateway job is logistics quality.</td></tr>
<tr><td data-label="Trip job">Premium city comfort</td><td data-label="Prioritize">Quiet room, strong breakfast, airport support, concierge help, spa/rest block, and one curated dinner.</td><td data-label="Skip first">Hotel hopping or too many external transfers.</td><td data-label="Why">Premium value is fewer preventable decisions.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hcmc-day-trips:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cu Chi, Mekong, or stay in the city?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The strongest HCMC plan does not automatically include both Cu Chi and the Mekong. Choose the extension that changes the trip, then protect the transfer and recovery.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hcmc-day-trips">
<thead><tr><th>Choice</th><th>Best use</th><th>Hidden friction</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Choice">Stay in the city</td><td data-label="Best use">Two-night HCMC stays, food/history focus, short first trips, or travelers arriving tired.</td><td data-label="Hidden friction">FOMO can make the city feel incomplete, but this is often the better route.</td><td data-label="VietnamGuide verdict">Best default before adding excursions.</td></tr>
<tr><td data-label="Choice">Cu Chi Tunnels</td><td data-label="Best use">Travelers who want a history-heavy day and can handle the road time.</td><td data-label="Hidden friction">Transfer time, heat, crowds, emotional tone, and whether the guide gives context.</td><td data-label="VietnamGuide verdict">Add when history is the reason, not because it appears on every list.</td></tr>
<tr><td data-label="Choice">Mekong day trip</td><td data-label="Best use">Travelers who need a soft river contrast but cannot spend a night in the Delta.</td><td data-label="Hidden friction">Long road time and tours that compress river life into staged stops.</td><td data-label="VietnamGuide verdict">Acceptable as a taste; better as an overnight when the south is the point.</td></tr>
<tr><td data-label="Choice">Mekong overnight</td><td data-label="Best use">South-first routes, photographers, food travelers, and anyone wanting the Delta to feel real.</td><td data-label="Hidden friction">Hotel choice, early starts, transfer sequence, and return-to-airport timing.</td><td data-label="VietnamGuide verdict">The stronger Mekong answer when the calendar allows.</td></tr>
<tr><td data-label="Choice">Phu Quoc or Con Dao extension</td><td data-label="Best use">Longer south-ending routes that need winter sun, resort recovery, quiet nature, or island closure.</td><td data-label="Hidden friction">Flight/ferry timing, weather, hotel area, and departure buffer.</td><td data-label="VietnamGuide verdict">Use only after HCMC and route length earn the extra move.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hcmc-season-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best time and weather posture</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Southern Vietnam is usually easier to plan than the north or central coast in the broad dry-season window, but HCMC still asks for heat, rain, and day-trip timing discipline. Weather should change the rhythm more than the whole city decision.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hcmc-season-weather">
<thead><tr><th>Timing</th><th>Planning posture</th><th>Best use</th><th>Live check</th></tr></thead>
<tbody>
<tr><td data-label="Timing">December to April bias</td><td data-label="Planning posture">Stronger dry-season case for HCMC, Mekong, and southern islands.</td><td data-label="Best use">Food walks, museums, markets, river/island extensions, and open-jaw routes.</td><td data-label="Live check">Heat, air quality, crowds, and exact excursion terms.</td></tr>
<tr><td data-label="Timing">May to November rain posture</td><td data-label="Planning posture">Still workable, but keep indoor backup and flexible day-trip timing.</td><td data-label="Best use">Museums, cafes, food, central-base stays, and rain-aware transfers.</td><td data-label="Live check">Rain bands, local flooding, return traffic, and airport buffers.</td></tr>
<tr><td data-label="Timing">Any month with Cu Chi or Mekong</td><td data-label="Planning posture">Treat the day trip as transport-heavy and weather-sensitive.</td><td data-label="Best use">Guided context, early start, and realistic return plans.</td><td data-label="Live check">Pickup point, road time, inclusions, cancellation terms, and heat/rain posture.</td></tr>
<tr><td data-label="Timing">Departure day</td><td data-label="Planning posture">Do not gamble with a distant morning excursion before an international flight.</td><td data-label="Best use">Central meals, hotel late checkout, airport transfer, and buffer.</td><td data-label="Live check">Terminal, traffic, luggage, airline check-in, and separate tickets.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hcmc-transport-logistics:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Tan Son Nhat, city movement, and onward travel</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hcmc-transport-logistics">
<thead><tr><th>Movement</th><th>Use it when...</th><th>Hidden friction</th><th>Live check</th></tr></thead>
<tbody>
<tr><td data-label="Movement">Airport transfer</td><td data-label="Use it when...">Every HCMC route starts or ends through Tan Son Nhat.</td><td data-label="Hidden friction">Terminal, pickup point, traffic, luggage, late arrival, and hotel area.</td><td data-label="Live check">ACV airport page, airline terminal, app pickup, hotel transfer, and cash/card backup.</td></tr>
<tr><td data-label="Movement">Ride-hailing / taxi</td><td data-label="Use it when...">Central sights, meals, heat, rain, or tired walking make short rides sensible.</td><td data-label="Hidden friction">Surge, wrong pickup side, language, phone battery, and traffic loops.</td><td data-label="Live check">SIM/eSIM, hotel address in Vietnamese, plate matching, and payment method.</td></tr>
<tr><td data-label="Movement">Walking loops</td><td data-label="Use it when...">District 1/3 base keeps the plan compact.</td><td data-label="Hidden friction">Heat, rain, road crossings, phone handling, and fatigue.</td><td data-label="Live check">Weather, shade, water, and whether the next sight truly belongs.</td></tr>
<tr><td data-label="Movement">Metro or public transport</td><td data-label="Use it when...">The live route, station, and ticket setup genuinely match your hotel and destination.</td><td data-label="Hidden friction">Station access, luggage, ticket learning curve, and last-mile taxi needs.</td><td data-label="Live check">Current operating route, station entrances, service hours, and hotel advice.</td></tr>
<tr><td data-label="Movement">Onward flight, ferry, or car</td><td data-label="Use it when...">HCMC launches Mekong, Phu Quoc, Con Dao, Nha Trang, or departure flights.</td><td data-label="Hidden friction">Separate tickets, airport re-entry, island weather, ferry timing, and long road returns.</td><td data-label="Live check">Transport Within Vietnam, weather, operator terms, and final-night buffer.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hcmc-cost-booking:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cost and booking checks that matter</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-hcmc-cost-booking"} -->
<ul class="wp-block-list vg-check-list vg-hcmc-cost-booking">
<li>Price HCMC as a city base: room location, airport transfer, ride-hailing, guided food/history block, day-trip transport, and one buffer meal or late checkout.</li>
<li>Do not compare a District 1 hotel, airport-side hotel, Thao Dien apartment, and riverside premium stay as if they solve the same trip job.</li>
<li>Keep cash/card backup for markets, small vendors, taxis, tips, and day-trip balances; use <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a> before arrival.</li>
<li>Protect phone data before leaving the airport or hotel; HCMC friction rises quickly when ride-hailing, maps, and hotel contact fail.</li>
<li>Use <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>, and <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a> before paying deposits or stacking late transfers.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hcmc-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first in HCMC</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hcmc-skip-logic">
<thead><tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr></thead>
<tbody>
<tr><td data-label="If you want...">A calm first HCMC chapter</td><td data-label="Protect this">Central base, one history block, one food block, and an unpressured evening.</td><td data-label="Skip first">Cu Chi and Mekong on back-to-back days.</td><td data-label="Only add back when...">You have three or four nights.</td></tr>
<tr><td data-label="If you want...">A strong 10-day Vietnam route</td><td data-label="Protect this">North and central anchors first.</td><td data-label="Skip first">HCMC if it is only one late airport night.</td><td data-label="Only add back when...">Open-jaw flights or personal priorities make the south real.</td></tr>
<tr><td data-label="If you want...">Food and museums</td><td data-label="Protect this">Energy, air-conditioned breaks, guide context, and walking/ride-hail balance.</td><td data-label="Skip first">Long day trips that leave the city thin.</td><td data-label="Only add back when...">The city has already had protected time.</td></tr>
<tr><td data-label="If you want...">Mekong meaning</td><td data-label="Protect this">Overnight or a carefully chosen day, not a departure-day rush.</td><td data-label="Skip first">A staged-feeling day trip squeezed before a flight.</td><td data-label="Only add back when...">The route can absorb road time and early starts.</td></tr>
<tr><td data-label="If you want...">Southern island finish</td><td data-label="Protect this">Flight/ferry buffer, hotel area, weather, and a relaxed final connection.</td><td data-label="Skip first">Extra city sights that risk the exit chain.</td><td data-label="Only add back when...">The island move is already protected.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hcmc-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Official checks before locking HCMC</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-hcmc-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-hcmc-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city" target="_blank" rel="noopener">Vietnam.travel Ho Chi Minh City</a> and <a href="https://visithcmc.vn" target="_blank" rel="noopener">Visit HCMC</a> for official destination framing before choosing the city role.</li>
<li>Use <a href="https://vietnamairport.vn/en/tan-son-nhat-airport" target="_blank" rel="noopener">ACV Tan Son Nhat</a>, <a href="https://acv.vn/en/airports/tan-son-nhat-international-airport" target="_blank" rel="noopener">ACV airport profile</a>, <a href="https://www.vietnamairlines.com/ca/en/plan-book/travel/travel-guide/airport-ho-chi-minh-to-city" target="_blank" rel="noopener">Vietnam Airlines airport-to-city guide</a>, and <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before treating HCMC as an easy arrival or exit.</li>
<li>Use <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a>, <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a>, and <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a> before locking Mekong, Cu Chi, or rain-sensitive city movement.</li>
<li>Use <a href="https://evisa.gov.vn/" target="_blank" rel="noopener">the official Vietnam e-visa portal</a>, <a href="https://vietnam.travel/plan-your-trip/getting-vietnam" target="_blank" rel="noopener">getting to Vietnam</a>, and <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a> before building HCMC around international arrival assumptions.</li>
<li>Compare <a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a>, and <a href="/destinations/con-dao-travel-guide/">Con Dao Travel Guide</a> before adding a southern chapter.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hcmc-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Ho Chi Minh City Travel Guide FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-hcmc-faq">
<details><summary>Is Ho Chi Minh City worth visiting for first-time visitors?</summary><p>Yes when the route has room for southern food, history, markets, nightlife, and airport or Mekong logic. It is weaker when added as a single final night after an already full north-and-central trip.</p></details>
<details><summary>How many days do you need in Ho Chi Minh City?</summary><p>Two nights is the fast minimum. Three nights is the best first answer. Four nights works when you want both the city and one serious day trip without rushing the airport chain.</p></details>
<details><summary>Where should I stay in HCMC?</summary><p>Most first-time visitors should stay in District 1 or the immediate central edge. District 3 can feel calmer. Airport-side hotels should be used only when flight timing is the main reason.</p></details>
<details><summary>Should I visit Cu Chi Tunnels from HCMC?</summary><p>Only when the history day is a real priority and you can handle the road time, heat, and emotional tone. It is not automatic for short HCMC stays.</p></details>
<details><summary>Is the Mekong Delta a day trip from Ho Chi Minh City?</summary><p>It can be a day trip, but the better Mekong experience often needs an overnight. Use a day trip only when the route cannot protect a deeper southern river chapter.</p></details>
<details><summary>Is Ho Chi Minh City better at the start or end of a Vietnam route?</summary><p>Either can work. It is strongest at the start when southern weather or flights lead the trip, and strongest at the end when it connects cleanly to Mekong, Phu Quoc, Con Dao, or an international departure.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then decide whether the south belongs through <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a>. Use <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>, <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>, <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a>, and <a href="/destinations/con-dao-travel-guide/">Con Dao Travel Guide</a> before building a south-ending route.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-hcmc-hero:v1',
    'concierge verdict' => 'vg-hcmc-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-hcmc-at-a-glance:v1',
    'photo grid' => 'vg-hcmc-photo-grid:v1',
    'source diversity' => 'vg-hcmc-source-diversity:v1',
    'districts' => 'vg-hcmc-districts:v1',
    'route fit' => 'vg-hcmc-route-fit:v1',
    'priority map' => 'vg-hcmc-priority-map:v1',
    'day trips' => 'vg-hcmc-day-trips:v1',
    'season weather' => 'vg-hcmc-season-weather:v1',
    'transport logistics' => 'vg-hcmc-transport-logistics:v1',
    'cost booking' => 'vg-hcmc-cost-booking:v1',
    'skip logic' => 'vg-hcmc-skip-logic:v1',
    'live checks' => 'vg-hcmc-live-checks:v1',
    'FAQ' => 'vg-hcmc-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_hcmc_ops_assert_required_content_markers($content, $required_content_markers);
vg_hcmc_ops_assert_internal_page_links_are_published('Ho Chi Minh City Travel Guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Ho Chi Minh City Travel Guide',
    'post_name'      => 'ho-chi-minh-city-travel-guide',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_hcmc_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Ho Chi Minh City Travel Guide for international travelers deciding whether HCMC should be a real southern chapter, district base, food/history city stay, Mekong or Cu Chi gateway, airport stop, or skip.',
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
    vg_hcmc_ops_fail('Could not publish Ho Chi Minh City Travel Guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_hcmc_ops_fail('Could not publish Ho Chi Minh City Travel Guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Ho Chi Minh City Travel Guide: Districts, Food, Museums and Mekong Fit');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Ho Chi Minh City travel guide: decide nights, districts, food, museums, Tan Son Nhat airport, Cu Chi, Mekong, season, costs, and skip logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Ho Chi Minh City travel guide');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Ho Chi Minh City should be a real southern Vietnam chapter, quick airport stop, food/history base, Cu Chi or Mekong gateway, island connector, or skip.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Ho Chi Minh City Travel Guide with a concierge verdict, at-a-glance decision table, licensed real photo proof, source-diversity panel, district/base guide, route-fit table, priority map, Cu Chi versus Mekong versus city decision table, season/weather posture, Tan Son Nhat and local movement logistics, cost/booking checks, skip logic, live official checks, FAQ, Destinations hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Ho Chi Minh City destination page - https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city - checked {$review_date}\nVisit HCMC official tourism portal - https://visithcmc.vn - checked {$review_date}\nACV - Tan Son Nhat Airport - https://vietnamairport.vn/en/tan-son-nhat-airport - checked {$review_date}\nACV - Tan Son Nhat International Airport profile - https://acv.vn/en/airports/tan-son-nhat-international-airport - checked {$review_date}\nVietnam Airlines - Ho Chi Minh City airport to city guide - https://www.vietnamairlines.com/ca/en/plan-book/travel/travel-guide/airport-ho-chi-minh-to-city - checked {$review_date}\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}\nNational Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked {$review_date}\nVietnam.travel - Getting to Vietnam - https://vietnam.travel/plan-your-trip/getting-vietnam - checked {$review_date}\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}\nOfficial Vietnam e-visa portal - https://evisa.gov.vn/ - checked {$review_date}\nWikimedia Commons image record - Ho Chi Minh City Hall - https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg - license checked {$review_date}\nWikimedia Commons image record - Ben Thanh Market, 2023 (03) - https://commons.wikimedia.org/wiki/File:Ben_Thanh_Market,_2023_(03).jpg - license checked {$review_date}\nWikimedia Commons image record - Ho Chi Minh City Central Post Office - https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_Central_Post_Office,_2020-01_CN-01.jpg - license checked {$review_date}\nWikimedia Commons image record - War Remnants Museum, HCMC, front - https://commons.wikimedia.org/wiki/File:War_Remnants_Museum,_HCMC,_front.JPG - license checked {$review_date}\nWikimedia Commons image record - Reunification Palace, Ho Chi Minh City, Vietnam - https://commons.wikimedia.org/wiki/File:Reunification_Palace,_Ho_Chi_Minh_City,_Vietnam.jpg - license checked {$review_date}\nWikimedia Commons image record - Cu Chi Tunnels, Ho Chi Minh City, Vietnam - https://commons.wikimedia.org/wiki/File:Cu_Chi_Tunnels,_Ho_Chi_Minh_City,_Vietnam_(49579798986).jpg - license checked {$review_date}\nWikimedia Commons image record - Vietnam, Phong Dien, Mekong Delta, River - https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg - license checked {$review_date}");
update_post_meta($page_id, 'vg_eeat_field_note', 'Ho Chi Minh City should be planned by the job it does in the route: central city chapter, food and history base, airport gateway, Mekong/Cu Chi launch point, or southern island connector. It is weakest as a token final night.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Ho Chi Minh City guide built as a southern-base decision guide rather than a generic attractions list.\nConcierge verdict separates real southern chapter, airport stop, food/history base, Cu Chi, Mekong, and island gateway uses.\nAt-a-glance table answers worth, nights, first district, main mistake, and live checks.\nLicensed real photo proof for Nguyen Hue/City Hall, Ben Thanh, Central Post Office, War Remnants Museum, Reunification Palace, Cu Chi, and Mekong route contrast.\nSource-diversity panel explains what Vietnam.travel, Visit HCMC, ACV, Vietnam Airlines, weather, transport, e-visa, and image-license records can and cannot prove.\nDistrict table separates District 1, District 3, riverside/business, Thao Dien, and airport-side stays by trip job.\nRoute-fit table links HCMC to 7, 10, 14, 21-day, and south-only route contexts.\nPriority map protects central city, food, history, gateway, and premium-comfort use cases.\nCu Chi versus Mekong versus stay-in-city table prevents automatic tour stacking.\nSeason/weather table keeps southern dry/rain posture, heat, rain, and departure-day margin visible.\nTransport/logistics table counts Tan Son Nhat, ride-hailing, walking loops, metro/public transport, and onward flights/ferries/cars.\nCost and booking checks cover hotel area, transfers, phone data, money, safety, insurance, and hidden day-trip friction.\nVisible source trail, related routes, and update log.");
$related_routes = "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before deciding whether HCMC should anchor the south.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Decide whether HCMC belongs in the destination set before adding Mekong, Phu Quoc, or Con Dao.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether southern Vietnam should be a real chapter or only an exit airport.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check southern dry/rain posture, heat, and route season before locking HCMC, Mekong, or island movement.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Count Tan Son Nhat, city transfers, ride-hailing, day-trip road time, and onward flights or ferries.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price district choice, airport transfer, food/history blocks, day trips, and a realistic buffer.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Prepare cash, cards, market payments, taxi backup, and ATM habits before city arrival.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, ride-hailing, hotel contact, airport pickup, and day-trip messaging from becoming preventable friction.\nVietnam E-Visa | /plan/vietnam-evisa/ | Recheck official entry timing before building HCMC around international arrival assumptions.\n7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Decide whether a short route should stay south-only instead of forcing three regions.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether HCMC has enough time to be more than a token airport stop.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when two weeks can support a real southern chapter after north and central Vietnam.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use three weeks to give HCMC, Mekong, or an island extension enough margin.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check heat, road transfers, medical care, trip interruption, and onward-island coverage.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair markets, taxis, nightlife, phone handling, road transfers, and airport arrival with practical risk habits.\nPhu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Decide whether a southern island finish after HCMC actually improves the route.\nCon Dao Travel Guide | /destinations/con-dao-travel-guide/ | Compare HCMC as the gateway to a quieter premium southern island chapter.";
vg_hcmc_ops_assert_related_route_meta_links_are_published('Ho Chi Minh City Travel Guide related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Nguyen Hue/City Hall image: Ho Chi Minh City, City Hall, 2020-01 CN-02 by Steffen Schmitz, CC BY-SA 4.0. Body images: Ben Thanh Market, 2023 (03) by Bahnfrend, CC BY-SA 4.0; Ho Chi Minh City Central Post Office by Steffen Schmitz, CC BY-SA 4.0; War Remnants Museum, HCMC, front by Prenn, CC BY-SA 3.0; Reunification Palace by Eustaquio Santimano, CC BY 2.0; Cu Chi Tunnels by flowcomm, CC BY 2.0; Vietnam, Phong Dien, Mekong Delta, River by Vyacheslav Argenberg, CC BY 4.0.');
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
        vg_hcmc_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_hcmc_ops_fail('Ho Chi Minh City Travel Guide was updated but is not published.');
}

vg_hcmc_ops_refresh_destinations_hub();
vg_hcmc_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_hcmc_ops_log("Published Ho Chi Minh City Travel Guide: {$page_id} {$updated_permalink}");

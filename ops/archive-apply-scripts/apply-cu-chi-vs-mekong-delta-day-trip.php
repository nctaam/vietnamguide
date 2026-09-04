<?php
/**
 * Publish the Cu Chi Tunnels vs Mekong Delta Day Trip comparison guide.
 *
 * Run from the WordPress root with:
 * VG_FORCE_CU_CHI_MEKONG_COMPARISON_REPUBLISH=1 wp eval-file ops/apply-cu-chi-vs-mekong-delta-day-trip.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_cu_chi_mekong_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_cu_chi_mekong_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_cu_chi_mekong_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_CU_CHI_MEKONG_COMPARISON_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_cu_chi_mekong_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_cu_chi_mekong_ops_fail('Could not resolve a valid WordPress author for the Cu Chi Tunnels vs Mekong Delta Day Trip guide.');
}

function vg_cu_chi_mekong_ops_internal_path_from_href(string $href): ?string
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

function vg_cu_chi_mekong_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_cu_chi_mekong_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_cu_chi_mekong_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_cu_chi_mekong_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_cu_chi_mekong_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_cu_chi_mekong_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_cu_chi_mekong_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_cu_chi_mekong_ops_log("Validated {$label} internal page links are published.");
}

function vg_cu_chi_mekong_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_cu_chi_mekong_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_cu_chi_mekong_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_cu_chi_mekong_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_cu_chi_mekong_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_cu_chi_mekong_ops_log("Validated {$label} related-route links are published.");
}

function vg_cu_chi_mekong_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_cu_chi_mekong_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_cu_chi_mekong_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

function vg_cu_chi_mekong_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
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
            vg_cu_chi_mekong_ops_fail("Could not confidently refresh {$label}.");
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
        vg_cu_chi_mekong_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_cu_chi_mekong_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_cu_chi_mekong_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_cu_chi_mekong_ops_refresh_compare_hub(): void
{
    $hub = get_page_by_path('compare', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_cu_chi_mekong_ops_log('Skipped Compare hub refresh: compare page was not found or is not published.');
        return;
    }

    if (! vg_cu_chi_mekong_ops_published_page_exists('compare/cu-chi-tunnels-vs-mekong-delta-day-trip')) {
        vg_cu_chi_mekong_ops_log('Skipped Compare hub refresh: Cu Chi vs Mekong comparison is not published.');
        return;
    }

    $marker = '<!-- vg-cu-chi-mekong-compare-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose Cu Chi or Mekong by route job, not fame</h3><p>The <a href="/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/">Cu Chi Tunnels vs Mekong Delta Day Trip</a> guide helps travelers decide whether one spare HCMC day should become a history stop, a river taste, an overnight Delta upgrade, or a protected city day.</p></div>
<!-- /wp:group -->
HTML;

    vg_cu_chi_mekong_ops_assert_internal_page_links_are_published('Compare hub Cu Chi/Mekong note', $block);
    vg_cu_chi_mekong_ops_upsert_marked_group($hub, 'Compare hub Cu Chi/Mekong note', $marker, $block);
}

function vg_cu_chi_mekong_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_cu_chi_mekong_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_cu_chi_mekong_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);

    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);

    if (str_contains($current, '/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/')) {
        vg_cu_chi_mekong_ops_log("Skipped related-route refresh for {$label}: Cu Chi vs Mekong comparison is already present.");
        return;
    }

    $next = trim($current) === '' ? $route_line : rtrim($current) . "\n" . $route_line;
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_cu_chi_mekong_ops_log("Added Cu Chi vs Mekong comparison related route to {$label}: {$page->ID}");
}

function vg_cu_chi_mekong_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Cu Chi Tunnels vs Mekong Delta Day Trip | /compare/cu-chi-tunnels-vs-mekong-delta-day-trip/ | Decide whether one HCMC spare day should become a history stop, river taste, overnight Delta upgrade, or protected city day.';

    foreach (
        [
            'destinations/best-day-trips-from-ho-chi-minh-city' => 'Best Day Trips from Ho Chi Minh City',
            'destinations/mekong-delta-travel-guide' => 'Mekong Delta Travel Guide',
            'destinations/ho-chi-minh-city-travel-guide' => 'Ho Chi Minh City Travel Guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ] as $path => $label
    ) {
        vg_cu_chi_mekong_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('compare', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_cu_chi_mekong_ops_fail('Published parent page not found: compare');
}

$page = get_page_by_path('compare/cu-chi-tunnels-vs-mekong-delta-day-trip', OBJECT, 'page');

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_cu_chi_mekong_ops_force_republish_enabled()) {
    vg_cu_chi_mekong_ops_fail('Cu Chi Tunnels vs Mekong Delta Day Trip is not a draft. Set VG_FORCE_CU_CHI_MEKONG_COMPARISON_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_cu_chi_mekong_ops_log("Preflight Cu Chi vs Mekong comparison: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_cu_chi_mekong_ops_log('Preflight Cu Chi vs Mekong comparison: no existing page found; creating a child page under /compare/.');
}

$review_date = 'July 23, 2026';
$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/c8/Cu_Chi_Tunnels_Vietnam_war.jpg/1280px-Cu_Chi_Tunnels_Vietnam_war.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cu_Chi_Tunnels_Vietnam_war.jpg');
$mekong_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1280px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg');
$mekong_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg');
$hcmc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1280px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg');
$hcmc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg');

$content = <<<HTML
<!-- vg-cu-chi-mekong-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero vg-cu-chi-mekong-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero vg-cu-chi-mekong-hero">
<div class="vg-guide-hero__media"><img src="{$hero_image}" alt="Cu Chi Tunnels Vietnam War display near Ho Chi Minh City" loading="eager" decoding="async"><p class="vg-image-credit">Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Andre Hospers / CC BY-SA 4.0</a>.</p></div>
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed comparison guide - Updated {$review_date}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Cu Chi Tunnels vs Mekong Delta Day Trip</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Use this comparison when Ho Chi Minh City has one spare day and the choice has collapsed into two famous names. Cu Chi is a history-context decision. The Mekong is a river-rhythm decision. The right choice is the one that matches your route pace, not the bigger name.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- vg-cu-chi-mekong-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-cu-chi-mekong-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-cu-chi-mekong-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Choose Cu Chi when history is the reason for the day. Choose the Mekong Delta when river rhythm is the reason for the day. Do not combine them in one day unless you knowingly accept a compressed compromise.</strong> If the route already has one strong river chapter, choose Cu Chi or stay in HCMC instead of compressing both.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Use Cu Chi when history is the reason for the day. Use the Mekong Delta when river rhythm is the reason for the day.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-cu-chi-mekong-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-cu-chi-mekong-shortlist">
<li><strong>Best lower-energy choice:</strong> Cu Chi, if the operator provides real interpretation and the traveler can handle heat, crowds, and confined-space context.</li>
<li><strong>Best atmosphere choice:</strong> Mekong, if the day is about boats, canals, lunch rhythm, and southern river texture rather than a token last stop.</li>
<li><strong>Best premium move:</strong> make the Delta overnight when Cai Rang, Can Tho, Ben Tre, Chau Doc, or slow river time is the reason.</li>
<li><strong>Best skip rule:</strong> keep the day inside Ho Chi Minh City when arrival fatigue, flight timing, heat, or route overload would make either trip thin.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-cu-chi-mekong-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-cu-chi-mekong-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-cu-chi-mekong-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Decision in 30 seconds</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cu-chi-mekong-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Which is better for most first-time visitors?</strong></td><td data-label="Answer">Cu Chi if history is a real priority; Mekong if river scenery and slower contrast matter more.</td></tr>
<tr><td data-label="Question"><strong>Which costs less energy?</strong></td><td data-label="Answer">If you only have one spare day, Cu Chi usually costs less energy than the Mekong Delta.</td></tr>
<tr><td data-label="Question"><strong>Can I do both in one day?</strong></td><td data-label="Answer">Combining Cu Chi and Mekong is a compromise route, not the best version of either stop.</td></tr>
<tr><td data-label="Question"><strong>Which is more operator-dependent?</strong></td><td data-label="Answer">Operator quality matters more on Mekong because pickup timing, boat sequence, and lunch handling shape the day.</td></tr>
<tr><td data-label="Question"><strong>What if neither feels right?</strong></td><td data-label="Answer">If neither fits, keep the day in Ho Chi Minh City or make the delta an overnight instead of forcing a rushed tour.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-cu-chi-mekong-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: history stop, river day, or city day?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The photos should clarify the decision. If the route needs interpreted wartime context, Cu Chi has the clearer job. If it needs water, boats, lunch rhythm, and southern river texture, the Mekong has the clearer job. If it needs neither, HCMC may be the stronger day.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-cu-chi-mekong-photo-grid" aria-label="Cu Chi Tunnels and Mekong Delta day trip comparison photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Cu Chi Tunnels Vietnam War display near Ho Chi Minh City" loading="lazy" decoding="async"><figcaption>Cu Chi is a short, high-friction history stop, not a full-day anchor. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Andre Hospers / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mekong_image}" alt="Small boat on a river in the Mekong Delta near Phong Dien" loading="lazy" decoding="async"><figcaption>Mekong is the better choice when the river is the point, not just the transfer. Image: <a href="{$mekong_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall and Nguyen Hue walking street at night" loading="lazy" decoding="async"><figcaption>Staying in HCMC is not failure when the city still needs food, museums, arrival recovery, or airport control. Image: <a href="{$hcmc_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-cu-chi-mekong-comparison-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cu Chi vs Mekong Delta comparison matrix</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the route job. A famous place is not useful unless it solves the day you actually have.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cu-chi-mekong-comparison-matrix">
<thead><tr><th>Decision factor</th><th>Cu Chi Tunnels</th><th>Mekong Delta day trip</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Decision factor">Route job</td><td data-label="Cu Chi Tunnels">History context from HCMC.</td><td data-label="Mekong Delta day trip">River, boat, canal, lunch, and village texture.</td><td data-label="VietnamGuide verdict">Choose by missing chapter, not name recognition.</td></tr>
<tr><td data-label="Decision factor">Time and energy</td><td data-label="Cu Chi Tunnels">Shorter but intense; heat, crowds, and tight spaces matter.</td><td data-label="Mekong Delta day trip">Longer and looser; road time and staged stops can dilute the day.</td><td data-label="VietnamGuide verdict">Cu Chi usually fits a single spare day more cleanly.</td></tr>
<tr><td data-label="Decision factor">Operator dependence</td><td data-label="Cu Chi Tunnels">Guide context decides whether the site feels meaningful or shallow.</td><td data-label="Mekong Delta day trip">Pickup, boat sequence, lunch, shopping stops, and return timing decide quality.</td><td data-label="VietnamGuide verdict">Mekong needs stronger logistics clarity before payment.</td></tr>
<tr><td data-label="Decision factor">Emotional tone</td><td data-label="Cu Chi Tunnels">More serious, historical, and potentially uncomfortable.</td><td data-label="Mekong Delta day trip">More scenic, social, and atmosphere-led when not rushed.</td><td data-label="VietnamGuide verdict">Pick the mood you actually want for the HCMC chapter.</td></tr>
<tr><td data-label="Decision factor">Best alternative</td><td data-label="Cu Chi Tunnels">Stay city if history fatigue, heat, or tight spaces are a bad fit.</td><td data-label="Mekong Delta day trip">Upgrade to overnight if river rhythm is the main reason.</td><td data-label="VietnamGuide verdict">Neither should be forced before a flight or after a heavy transfer run.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-cu-chi-mekong-transfer-ratio:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Time and energy tradeoff</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Both trips spend travel energy outside the city. The premium question is whether the experience gained is stronger than the HCMC time lost.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cu-chi-mekong-transfer-ratio">
<thead><tr><th>Scenario</th><th>Better choice</th><th>Why</th><th>Watch-out</th></tr></thead>
<tbody>
<tr><td data-label="Scenario">One spare day and only two or three HCMC nights</td><td data-label="Better choice">Cu Chi or stay city.</td><td data-label="Why">Less total transfer pressure than a weak Mekong sample.</td><td data-label="Watch-out">Do not buy a tunnel stop without guide context.</td></tr>
<tr><td data-label="Scenario">The route lacks river scenery entirely</td><td data-label="Better choice">Mekong, but only as a taste.</td><td data-label="Why">River contrast can make southern Vietnam feel different from HCMC.</td><td data-label="Watch-out">A day trip cannot replace Can Tho, Ben Tre, or a real Delta night.</td></tr>
<tr><td data-label="Scenario">Same-night flight, train, or fragile onward transfer</td><td data-label="Better choice">Stay in HCMC.</td><td data-label="Why">The city gives luggage control and airport buffer.</td><td data-label="Watch-out">A road or boat day is a poor departure-day gamble.</td></tr>
<tr><td data-label="Scenario">Family, older traveler, or heat-sensitive traveler</td><td data-label="Better choice">Private Cu Chi, private city day, or no excursion.</td><td data-label="Why">Recovery cost matters more than collecting famous names.</td><td data-label="Watch-out">Long combo tours compress rest exactly when rest matters.</td></tr>
<tr><td data-label="Scenario">South-first itinerary with enough nights</td><td data-label="Better choice">Overnight Mekong plus optional Cu Chi only if history matters.</td><td data-label="Why">The Delta becomes a real chapter instead of a staged sample.</td><td data-label="Watch-out">Do not duplicate a proper river chapter with another rushed river day.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-cu-chi-mekong-booking-mode:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Operator quality check</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>For Cu Chi, pay for interpretation. For Mekong, pay for sequencing. The cheapest product is not cheap if it turns the day into pickup drift, hidden shopping, and a late return.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cu-chi-mekong-booking-mode">
<thead><tr><th>Question before booking</th><th>Strong Cu Chi answer</th><th>Strong Mekong answer</th><th>Red flag</th></tr></thead>
<tbody>
<tr><td data-label="Question before booking">Who explains the experience?</td><td data-label="Strong Cu Chi answer">Guide language, site context, timing, and opt-out choices are clear.</td><td data-label="Strong Mekong answer">Guide explains route, boat segments, lunch plan, and what is staged.</td><td data-label="Red flag">Driver-only service sold as a guided experience.</td></tr>
<tr><td data-label="Question before booking">How is the day sequenced?</td><td data-label="Strong Cu Chi answer">Early start, focused site time, and simple return.</td><td data-label="Strong Mekong answer">Pickup, road segment, boat timing, meal/rest, and return range are written.</td><td data-label="Red flag">Every vague stop becomes a must-see local experience.</td></tr>
<tr><td data-label="Question before booking">What happens to lunch and rest?</td><td data-label="Strong Cu Chi answer">Simple meal or return plan that does not pad the day.</td><td data-label="Strong Mekong answer">Lunch timing, dietary handling, drinks, and non-shopping rest are named.</td><td data-label="Red flag">Lunch is only described as local restaurant.</td></tr>
<tr><td data-label="Question before booking">Can the route flex?</td><td data-label="Strong Cu Chi answer">The guide can cut filler stops if pickup slips.</td><td data-label="Strong Mekong answer">Boat, rain, shopping, and return-time changes have a policy.</td><td data-label="Red flag">Precise return promise with no traffic or weather language.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-cu-chi-mekong-context-filter:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Choose by context, not checklist</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This is where the comparison becomes honest. Cu Chi and Mekong can both be worthwhile, but each becomes weak when the traveler buys the wrong context.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cu-chi-mekong-context-filter">
<thead><tr><th>If your real priority is...</th><th>Choose</th><th>Skip or upgrade</th></tr></thead>
<tbody>
<tr><td data-label="If your real priority is...">Vietnam War history and difficult context</td><td data-label="Choose">Cu Chi with a real guide.</td><td data-label="Skip or upgrade">Skip if the product treats the tunnels as photo throughput.</td></tr>
<tr><td data-label="If your real priority is...">Water, boats, canals, and softer southern scenery</td><td data-label="Choose">Mekong, but accept it as a taste from HCMC.</td><td data-label="Skip or upgrade">Upgrade to overnight when river rhythm is the reason.</td></tr>
<tr><td data-label="If your real priority is...">A relaxed final HCMC day</td><td data-label="Choose">Stay in Ho Chi Minh City.</td><td data-label="Skip or upgrade">Skip both if airport, packing, sleep, or food time matters more.</td></tr>
<tr><td data-label="If your real priority is...">Seeing both famous names</td><td data-label="Choose">Neither as a premium default.</td><td data-label="Skip or upgrade">Combining both is a compromise route; choose one stronger version instead.</td></tr>
<tr><td data-label="If your real priority is...">Family comfort or lower friction</td><td data-label="Choose">Private timing or a city day.</td><td data-label="Skip or upgrade">Avoid long combo tours with vague rest stops.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-cu-chi-mekong-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Source and live checks before payment</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-cu-chi-mekong-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-cu-chi-mekong-live-checks">
<li>Use <a href="https://map3d.visithcmc.vn/?startscene=scene_1_1_2_dia-dao-cu-chi_(1)" target="_blank" rel="noopener">Visit HCMC 3D Cu Chi</a> and <a href="https://vietnam.travel/things-to-do/7-must-see-attractions-hcmc" target="_blank" rel="noopener">Vietnam.travel HCMC attractions</a> for official Cu Chi visitor framing.</li>
<li>Use <a href="https://vietnam.travel/places-to-go/mekong-delta" target="_blank" rel="noopener">Vietnam.travel Mekong Delta</a> and <a href="https://vietnam.travel/things-to-do/day-tripping-in-the-mekong-delta" target="_blank" rel="noopener">Vietnam.travel day-tripping in the Mekong Delta</a> for river-day framing before accepting a rushed sample.</li>
<li>Use <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a>, <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before paying non-refundable deposits.</li>
</ul>
<!-- /wp:list -->

<!-- vg-cu-chi-mekong-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cu Chi vs Mekong FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-cu-chi-mekong-faq">
<details><summary>Should I choose Cu Chi or the Mekong Delta from Ho Chi Minh City?</summary><p>Choose Cu Chi if history is the purpose of the day. Choose the Mekong if the route needs river atmosphere. If neither solves a missing chapter, stay in HCMC.</p></details>
<details><summary>Can I do Cu Chi and the Mekong Delta in one day?</summary><p>You can find tours that sell both, but that does not make it the best choice. Combining Cu Chi and Mekong is a compromise route, not the best version of either stop.</p></details>
<details><summary>Is Cu Chi worth it if I am not very interested in war history?</summary><p>Usually not as a priority. Cu Chi becomes meaningful through context; without that interest, a city food, museum, architecture, or rest day may be stronger.</p></details>
<details><summary>Is a Mekong Delta day trip enough?</summary><p>It is enough only as a taste. If the river is one of the reasons you came to southern Vietnam, use <a href="/destinations/mekong-delta-travel-guide/">Mekong Delta Travel Guide</a> and consider an overnight.</p></details>
<details><summary>What if neither Cu Chi nor Mekong feels right?</summary><p>Keep the day in Ho Chi Minh City, choose Can Gio for nature if weather and routing fit, or save the energy for a stronger onward chapter. A skipped day trip can be the premium decision.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with <a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a> and <a href="/destinations/best-day-trips-from-ho-chi-minh-city/">Best Day Trips from Ho Chi Minh City</a>. Use <a href="/destinations/mekong-delta-travel-guide/">Mekong Delta Travel Guide</a> when the river day may deserve a night. Use <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before stacking southern day trips.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-cu-chi-mekong-hero:v1',
    'concierge verdict' => 'vg-cu-chi-mekong-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-cu-chi-mekong-at-a-glance:v1',
    'photo grid' => 'vg-cu-chi-mekong-photo-grid:v1',
    'comparison matrix' => 'vg-cu-chi-mekong-comparison-matrix:v1',
    'transfer ratio' => 'vg-cu-chi-mekong-transfer-ratio:v1',
    'booking mode' => 'vg-cu-chi-mekong-booking-mode:v1',
    'context filter' => 'vg-cu-chi-mekong-context-filter:v1',
    'live checks' => 'vg-cu-chi-mekong-live-checks:v1',
    'FAQ' => 'vg-cu-chi-mekong-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_cu_chi_mekong_ops_assert_required_content_markers($content, $required_content_markers);
vg_cu_chi_mekong_ops_assert_internal_page_links_are_published('Cu Chi Tunnels vs Mekong Delta Day Trip guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Cu Chi Tunnels vs Mekong Delta Day Trip',
    'post_name'      => 'cu-chi-tunnels-vs-mekong-delta-day-trip',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_cu_chi_mekong_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Cu Chi Tunnels vs Mekong Delta day trip comparison for international travelers choosing history, river rhythm, route energy, operator quality, and whether to combine, skip, or overnight.',
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
    vg_cu_chi_mekong_ops_fail('Could not publish Cu Chi Tunnels vs Mekong Delta Day Trip: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_cu_chi_mekong_ops_fail('Could not publish Cu Chi Tunnels vs Mekong Delta Day Trip: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Cu Chi Tunnels vs Mekong Delta Day Trip: Which Is Better?');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Cu Chi vs Mekong Delta day trip comparison from Ho Chi Minh City: choose history, river rhythm, operator quality, route energy, or skip/overnight.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Cu Chi Tunnels vs Mekong Delta Day Trip');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether one spare Ho Chi Minh City day should become a Cu Chi history stop, a Mekong river taste, an overnight Delta upgrade, or a protected city day.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published Cu Chi Tunnels vs Mekong Delta Day Trip with a concierge verdict, 30-second decision table, licensed photo proof, comparison matrix, time/energy tradeoff, operator quality check, context filter, live official checks, FAQ, Compare hub note, inbound related routes, source trail, and update log.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Visit HCMC 3D Cu Chi source - https://map3d.visithcmc.vn/?startscene=scene_1_1_2_dia-dao-cu-chi_(1) - checked {$review_date}\nVietnam.travel - 7 must-see attractions in HCMC / Cu Chi context - https://vietnam.travel/things-to-do/7-must-see-attractions-hcmc - checked {$review_date}\nVietnam.travel - Mekong Delta destination frame - https://vietnam.travel/places-to-go/mekong-delta - checked {$review_date}\nVietnam.travel - Day-tripping in the Mekong Delta - https://vietnam.travel/things-to-do/day-tripping-in-the-mekong-delta - checked {$review_date}\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}\nNational Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked {$review_date}\nWikimedia Commons image record - Cu Chi Tunnels Vietnam war - https://commons.wikimedia.org/wiki/File:Cu_Chi_Tunnels_Vietnam_war.jpg - license checked {$review_date}\nWikimedia Commons image record - Vietnam, Phong Dien, Mekong Delta, River - https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg - license checked {$review_date}\nWikimedia Commons image record - Ho Chi Minh City Hall - https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg - license checked {$review_date}");
update_post_meta($page_id, 'vg_eeat_field_note', 'Cu Chi and Mekong are both valid only when they do a clear route job. Cu Chi buys interpreted history with moderate transfer pressure. Mekong buys river atmosphere with heavier sequencing risk. Combining them usually buys compression.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Comparison guide built around a real HCMC spare-day decision rather than a generic attraction ranking.\nConcierge verdict separates Cu Chi history context from Mekong river rhythm and warns against one-day combination compression.\n30-second table answers choice, energy, combination, operator dependence, and neither-fits questions.\nLicensed photo proof shows Cu Chi, Mekong, and HCMC alternatives with visible credits.\nComparison matrix covers route job, time/energy, operator dependence, emotional tone, and best alternatives.\nTime and energy table protects flights, families, short stays, and south-first routes from road-heavy filler.\nOperator quality check separates Cu Chi guide context from Mekong sequencing, lunch, boat, and return timing.\nContext filter and FAQ make the stronger alternative visible: stay in HCMC or upgrade the Delta overnight.\nVisible source trail, update log, related routes, official source checks, and image-license transparency.");
$related_routes = "Best Day Trips from Ho Chi Minh City | /destinations/best-day-trips-from-ho-chi-minh-city/ | Compare Cu Chi and Mekong against Can Gio, Tay Ninh, Vung Tau, and staying in the city.\nHo Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Decide whether HCMC has enough protected city time before any excursion leaves the base.\nMekong Delta Travel Guide | /destinations/mekong-delta-travel-guide/ | Use when a Mekong taste should become an overnight Can Tho, Ben Tre, Chau Doc, or river chapter.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before adding southern day trips.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether the south deserves a real chapter or only an exit airport.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check heat, rain, and southern weather before booking road or river days.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Count HCMC pickups, road time, boat pieces, and airport buffers before paying deposits.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price guide context, private timing, inclusions, and lost city time before booking.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether short routes should skip one or both southern day trips.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when a two-week route can support HCMC plus one southern excursion.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check heat, road days, boat segments, medical access, and interruption coverage.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair tours, taxis, deposits, cash, phone handling, and road days with practical risk habits.";
vg_cu_chi_mekong_ops_assert_related_route_meta_links_are_published('Cu Chi vs Mekong guide related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero image: Cu Chi Tunnels Vietnam war by Andre Hospers, CC BY-SA 4.0. Body images: Vietnam, Phong Dien, Mekong Delta, River by Vyacheslav Argenberg, CC BY 4.0; Ho Chi Minh City Hall by Steffen Schmitz, CC BY-SA 4.0.');
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
        vg_cu_chi_mekong_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_cu_chi_mekong_ops_fail('Cu Chi Tunnels vs Mekong Delta Day Trip was updated but is not published.');
}

vg_cu_chi_mekong_ops_refresh_compare_hub();
vg_cu_chi_mekong_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_cu_chi_mekong_ops_log("Published Cu Chi Tunnels vs Mekong Delta Day Trip: {$page_id} {$updated_permalink}");

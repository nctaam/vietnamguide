<?php
/**
 * Publish the Hanoi Airport to Old Quarter guide.
 *
 * Self-reference marker: ops/apply-hanoi-airport-to-old-quarter.php
 *
 * Run from the WordPress root with:
 * VG_FORCE_HANOI_AIRPORT_TRANSFER_REPUBLISH=1 wp eval-file ops/apply-hanoi-airport-to-old-quarter.php --allow-root
 *
 * Repair only hub/related-route/homepage side effects with:
 * VG_REPAIR_HANOI_AIRPORT_TRANSFER_LINKS=1 wp eval-file ops/apply-hanoi-airport-to-old-quarter.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_hanoi_airport_transfer_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_hanoi_airport_transfer_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_hanoi_airport_transfer_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_HANOI_AIRPORT_TRANSFER_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_hanoi_airport_transfer_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_HANOI_AIRPORT_TRANSFER_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_hanoi_airport_transfer_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_hanoi_airport_transfer_ops_fail('Could not resolve a valid WordPress author for Hanoi Airport to Old Quarter.');
}

function vg_hanoi_airport_transfer_ops_internal_path_from_href(string $href): ?string
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

function vg_hanoi_airport_transfer_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_hanoi_airport_transfer_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_hanoi_airport_transfer_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_airport_transfer_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_hanoi_airport_transfer_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_hanoi_airport_transfer_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_hanoi_airport_transfer_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_hanoi_airport_transfer_ops_log("Validated {$label} internal page links are published.");
}

function vg_hanoi_airport_transfer_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_hanoi_airport_transfer_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_hanoi_airport_transfer_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_hanoi_airport_transfer_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_hanoi_airport_transfer_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_hanoi_airport_transfer_ops_log("Validated {$label} related-route links are published.");
}

function vg_hanoi_airport_transfer_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_hanoi_airport_transfer_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
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
            vg_hanoi_airport_transfer_ops_fail("Could not confidently refresh {$label}.");
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
        vg_hanoi_airport_transfer_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_hanoi_airport_transfer_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_hanoi_airport_transfer_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_hanoi_airport_transfer_ops_homepage(): ?WP_Post
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

function vg_hanoi_airport_transfer_ops_refresh_plan_hub(): void
{
    $hub = get_page_by_path('plan', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_hanoi_airport_transfer_ops_log('Skipped Plan hub refresh: plan page was not found or is not published.');
        return;
    }

    if (! vg_hanoi_airport_transfer_ops_published_page_exists('plan/hanoi-airport-to-old-quarter')) {
        vg_hanoi_airport_transfer_ops_log('Skipped Plan hub refresh: Hanoi Airport to Old Quarter is not published.');
        return;
    }

    $marker = '<!-- vg-hanoi-airport-transfer-plan-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Make Noi Bai arrival a calm first decision</h3><p>The <a href="/plan/hanoi-airport-to-old-quarter/">Hanoi Airport to Old Quarter</a> guide helps travelers choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, family comfort, and first-night risk.</p></div>
<!-- /wp:group -->
HTML;

    vg_hanoi_airport_transfer_ops_assert_internal_page_links_are_published('Plan hub Hanoi airport transfer note', $block);
    vg_hanoi_airport_transfer_ops_upsert_marked_group($hub, 'Plan hub Hanoi airport transfer note', $marker, $block);
}

function vg_hanoi_airport_transfer_ops_refresh_homepage_route_spine(): void
{
    $home = vg_hanoi_airport_transfer_ops_homepage();

    if (! $home instanceof WP_Post) {
        vg_hanoi_airport_transfer_ops_log('Skipped homepage route-spine refresh: homepage was not found or is not published.');
        return;
    }

    if (! vg_hanoi_airport_transfer_ops_published_page_exists('plan/hanoi-airport-to-old-quarter')) {
        vg_hanoi_airport_transfer_ops_log('Skipped homepage route-spine refresh: Hanoi Airport to Old Quarter is not published.');
        return;
    }

    $marker = '<!-- vg-hanoi-airport-transfer-homepage-route-spine:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-home-section vg-home-decision-spine vg-home-hanoi-airport-transfer-spine"} -->
<div class="wp-block-group vg-home-section vg-home-decision-spine vg-home-hanoi-airport-transfer-spine"><p class="vg-kicker">Hanoi arrival decision</p><h2>Hanoi Airport to Old Quarter</h2><p>Use the transfer guide before the first night becomes improvised. It compares hotel pickup, verified taxi, ride-hailing, airport bus, and public bus by arrival hour, luggage, phone data, fare control, and Old Quarter walking distance.</p><p class="vg-section-link"><a href="/plan/hanoi-airport-to-old-quarter/">Plan the Hanoi airport transfer</a></p></div>
<!-- /wp:group -->
HTML;

    vg_hanoi_airport_transfer_ops_assert_internal_page_links_are_published('Homepage Hanoi airport transfer route-spine note', $block);
    vg_hanoi_airport_transfer_ops_upsert_marked_group($home, 'Homepage Hanoi airport transfer route-spine note', $marker, $block);
}

function vg_hanoi_airport_transfer_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_airport_transfer_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_hanoi_airport_transfer_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_hanoi_airport_transfer_ops_log("Skipped related-route refresh for {$label}: Hanoi airport transfer line is already current.");
        return;
    }

    update_post_meta($page->ID, 'vg_eeat_related_routes', implode("\n", $next_lines));

    vg_hanoi_airport_transfer_ops_log("Upserted Hanoi Airport to Old Quarter related route to {$label}: {$page->ID}");
}

function vg_hanoi_airport_transfer_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Hanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.';

    foreach (
        [
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
            'compare/old-quarter-vs-french-quarter-vs-west-lake' => 'Old Quarter vs French Quarter vs West Lake',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
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
        vg_hanoi_airport_transfer_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

function vg_hanoi_airport_transfer_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_hanoi_airport_transfer_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

$parent = get_page_by_path('plan', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_hanoi_airport_transfer_ops_fail('Could not find published /plan/ parent page.');
}

$page = get_page_by_path('plan/hanoi-airport-to-old-quarter', OBJECT, 'page');

if (vg_hanoi_airport_transfer_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_airport_transfer_ops_fail('Repair mode requires Hanoi Airport to Old Quarter to already be published.');
    }

    vg_hanoi_airport_transfer_ops_refresh_plan_hub();
    vg_hanoi_airport_transfer_ops_refresh_homepage_route_spine();
    vg_hanoi_airport_transfer_ops_refresh_inbound_related_routes();
    vg_hanoi_airport_transfer_ops_log("Repaired Hanoi Airport to Old Quarter side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_hanoi_airport_transfer_ops_force_republish_enabled()) {
    vg_hanoi_airport_transfer_ops_fail('Hanoi Airport to Old Quarter is not a draft. Set VG_FORCE_HANOI_AIRPORT_TRANSFER_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_hanoi_airport_transfer_ops_log("Preflight Hanoi Airport to Old Quarter: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_hanoi_airport_transfer_ops_log('Preflight Hanoi Airport to Old Quarter: no existing page found; creating a child page under /plan/.');
}

$review_date = 'July 24, 2026';
$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/56/Noi_Bai_International_Airport_T2_Waiting_Area.jpg/1920px-Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$hanoi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$hanoi_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg');
$old_quarter_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/Cho_Dong_Xuan%2C_Old_Quarter%2C_Hanoi%2C_Vietnam_%285245806573%29.jpg/1920px-Cho_Dong_Xuan%2C_Old_Quarter%2C_Hanoi%2C_Vietnam_%285245806573%29.jpg');
$old_quarter_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cho_Dong_Xuan,_Old_Quarter,_Hanoi,_Vietnam_(5245806573).jpg');

$content = <<<HTML
<!-- vg-hanoi-airport-transfer-hero:v1 -->
<!-- wp:cover {"url":"{$hero_image}","alt":"Noi Bai International Airport Terminal 2 waiting area in Hanoi","dimRatio":60,"minHeight":620,"className":"vg-guide-hero-cover vg-hanoi-airport-transfer-hero"} -->
<div class="wp-block-cover vg-guide-hero-cover vg-hanoi-airport-transfer-hero" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-60 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Noi Bai International Airport Terminal 2 waiting area in Hanoi" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Hanoi arrival guide</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Hanoi Airport to Old Quarter</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">A calm Noi Bai transfer is the first decision of the Hanoi route. This guide helps international arrivals choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, budget, and the exact Old Quarter walking distance.</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- vg-hanoi-airport-transfer-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-hanoi-airport-transfer-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-hanoi-airport-transfer-concierge-verdict">
<!-- wp:heading -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most first-time international arrivals, the best default is a prearranged hotel pickup or a verified app/official airport taxi after dark, and the airport bus only when you land rested, light, and central.</strong> Route 86 is useful when the savings matter and your luggage, phone data, and walking distance are all under control. A late arrival is not the moment to test every cheap option.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><strong>Best first-time default:</strong> hotel pickup if you land late, travel with family, carry checked bags, or dislike negotiating while tired.</li>
<li><strong>Best flexible default:</strong> verified airport taxi queue or app ride when you can match the plate, watch the route, and keep the hotel address ready.</li>
<li><strong>Best budget choice:</strong> airport bus only when your arrival stack is simple: daylight, light luggage, working phone, central stop, and short final walk.</li>
<li><strong>Worst move:</strong> Do not make your first Hanoi decision at the curb.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-hanoi-airport-transfer-at-a-glance:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Fast answer before you land</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-airport-transfer-at-a-glance">
<thead><tr><th>Arrival situation</th><th>Best default</th><th>Why</th><th>What to verify</th></tr></thead>
<tbody>
<tr><td data-label="Arrival situation">Long-haul, late, or first Vietnam night</td><td data-label="Best default">Hotel pickup or verified taxi/app ride</td><td data-label="Why">The airport-to-hotel handoff matters more than small savings.</td><td data-label="What to verify">Driver name, meeting point, license plate, hotel address, and price basis.</td></tr>
<tr><td data-label="Arrival situation">Daylight, carry-on only, central hotel</td><td data-label="Best default">Airport bus or app ride</td><td data-label="Why">The bus can be excellent value when the last walk is easy.</td><td data-label="What to verify">Route stop, luggage space, final walk, and payment method.</td></tr>
<tr><td data-label="Arrival situation">Family, older traveler, or premium route</td><td data-label="Best default">Prearranged car</td><td data-label="Why">Families and first-time long-haul travelers should buy certainty before savings.</td><td data-label="What to verify">Car size, child needs, wait policy, luggage, and emergency contact.</td></tr>
<tr><td data-label="Arrival situation">Solo budget traveler</td><td data-label="Best default">Airport bus if conditions are simple</td><td data-label="Why">Savings are real only when the final walk and phone setup are easy.</td><td data-label="What to verify">Cash, stop name, route direction, daylight, and hostel/hotel reception hours.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-airport-transfer-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what the transfer is actually solving</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-hanoi-airport-transfer-photo-grid">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Noi Bai International Airport T2 waiting area" loading="lazy" decoding="async"><figcaption>Noi Bai arrival is where phone data, cash, luggage, and the first transfer collide. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Sky 269 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hanoi_image}" alt="Hoan Kiem Lake in central Hanoi" loading="lazy" decoding="async"><figcaption>Hoan Kiem is the cleanest first-night target because the lake, food streets, and taxis are close. Image: <a href="{$hanoi_credit_url}" target="_blank" rel="license noopener">Alex 69200 vx / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$old_quarter_image}" alt="Dong Xuan Market in Hanoi Old Quarter" loading="lazy" decoding="async"><figcaption>The Old Quarter is convenient, but the final lane, noise level, and walking distance can change the best transfer. Image: <a href="{$old_quarter_credit_url}" target="_blank" rel="license noopener">yeowatzup / CC BY 2.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-hanoi-airport-transfer-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How to read the evidence</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Official airport pages can confirm transport categories, route numbers, service conditions, and taxi operating posture. They cannot tell you whether your flight will be delayed, whether your phone will work, whether your hotel lane is easy for cars, or whether rain will turn a short walk into a bad first impression.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>For taxi context, the airport states taxi services run from the first flight to the final flight of the day and that serving vehicles must meet operational conditions such as meter, seal, payment, cleanliness, and safety requirements.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-airport-transfer-source-diversity">
<thead><tr><th>Source type</th><th>Use it for</th><th>Do not use it for</th></tr></thead>
<tbody>
<tr><td data-label="Source type">Noi Bai airport transport page</td><td data-label="Use it for">Taxi service rules, mini bus context, bus route numbers, and listed airport bus fares.</td><td data-label="Do not use it for">Guaranteeing the best option for your exact arrival hour.</td></tr>
<tr><td data-label="Source type">Airline or official tourism transport guides</td><td data-label="Use it for">General city-transfer posture and traveler transport context.</td><td data-label="Do not use it for">Live app pricing, driver availability, or your hotel's street access.</td></tr>
<tr><td data-label="Source type">Weather and live checks</td><td data-label="Use it for">Rain, storm, heat, or arrival-day disruption risk.</td><td data-label="Do not use it for">A promise that traffic will be smooth.</td></tr>
<tr><td data-label="Source type">VietnamGuide judgment</td><td data-label="Use it for">Putting arrival hour, luggage, phone data, traveler confidence, and Old Quarter block choice into one decision.</td><td data-label="Do not use it for">Replacing live confirmation at the airport, app, or hotel.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-airport-transfer-verdict-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Choose the transfer by trip job</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-airport-transfer-verdict-matrix">
<thead><tr><th>Option</th><th>Best for</th><th>Watch out for</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Option">Hotel pickup</td><td data-label="Best for">Late arrivals, families, premium trips, nervous first-timers, and difficult hotel lanes.</td><td data-label="Watch out for">Overpaying without a named driver, waiting policy, or clear meeting point.</td><td data-label="VietnamGuide verdict">Best certainty buy when the first night matters.</td></tr>
<tr><td data-label="Option">Official airport taxi queue</td><td data-label="Best for">Travelers who want a car now and can keep the hotel address ready.</td><td data-label="Watch out for">Unsolicited approaches, unclear fare basis, and not checking the vehicle/company.</td><td data-label="VietnamGuide verdict">Good default when chosen from the airport system, not from a random offer.</td></tr>
<tr><td data-label="Option">Ride-hailing app</td><td data-label="Best for">Travelers with working data, app confidence, and a clear pickup point.</td><td data-label="Watch out for">Plate mismatch, pickup confusion, surge pricing, and luggage fit.</td><td data-label="VietnamGuide verdict">Flexible, but only after the phone works.</td></tr>
<tr><td data-label="Option">Airport bus</td><td data-label="Best for">Daylight, budget, light luggage, and hotels near a practical stop.</td><td data-label="Watch out for">Final walk, rain, peak traffic, and reception closing times.</td><td data-label="VietnamGuide verdict">Excellent value when the last kilometer is easy.</td></tr>
<tr><td data-label="Option">Public bus routes</td><td data-label="Best for">Very budget travelers who already know the route and can absorb friction.</td><td data-label="Watch out for">Transfers, luggage, language, and wrong-stop risk after a flight.</td><td data-label="VietnamGuide verdict">Useful, but rarely the best first-night default for international arrivals.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-airport-transfer-arrival-flow:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The calm arrival flow</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-airport-transfer-arrival-flow">
<thead><tr><th>Step</th><th>Decision</th><th>Why it matters</th></tr></thead>
<tbody>
<tr><td data-label="Step">1. Before landing</td><td data-label="Decision">Save hotel address in English and Vietnamese, plus phone number and map pin.</td><td data-label="Why it matters">The first driver conversation should not depend on airport Wi-Fi.</td></tr>
<tr><td data-label="Step">2. Before exiting arrivals</td><td data-label="Decision">Activate data, withdraw small cash if needed, and confirm the transfer channel.</td><td data-label="Why it matters">The arrival stack is easier before luggage, crowds, and curb pressure build.</td></tr>
<tr><td data-label="Step">3. At pickup</td><td data-label="Decision">Match the driver name, plate, app, hotel sign, or official queue.</td><td data-label="Why it matters">The right car is more important than a fast car.</td></tr>
<tr><td data-label="Step">4. On the road</td><td data-label="Decision">Watch the map politely, keep luggage close, and avoid changing hotel plans mid-ride.</td><td data-label="Why it matters">Most problems come from improvising while tired.</td></tr>
<tr><td data-label="Step">5. At the hotel lane</td><td data-label="Decision">If the lane is narrow, ask the hotel where cars normally stop.</td><td data-label="Why it matters">Some Old Quarter hotels require a short final walk from a practical drop-off point.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-airport-transfer-option-scorecard:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Transfer option scorecard</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Think in total arrival cost: money, fatigue, wrong-pickup risk, final walk, and whether the first Hanoi evening still has value.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-airport-transfer-option-scorecard">
<thead><tr><th>Option</th><th>Friction</th><th>Control</th><th>Best upgrade trigger</th></tr></thead>
<tbody>
<tr><td data-label="Option">Hotel pickup</td><td data-label="Friction">Low if confirmed</td><td data-label="Control">High</td><td data-label="Best upgrade trigger">Late flight, children, checked luggage, premium short stay.</td></tr>
<tr><td data-label="Option">Verified taxi/app</td><td data-label="Friction">Medium</td><td data-label="Control">Medium-high</td><td data-label="Best upgrade trigger">Working data and clear pickup point.</td></tr>
<tr><td data-label="Option">Airport bus</td><td data-label="Friction">Medium-high</td><td data-label="Control">Medium</td><td data-label="Best upgrade trigger">Daylight, central stop, light luggage, flexible check-in.</td></tr>
<tr><td data-label="Option">Public bus with transfer</td><td data-label="Friction">High</td><td data-label="Control">Low-medium</td><td data-label="Best upgrade trigger">Only when budget is the main job and you are comfortable with local transit.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-airport-transfer-late-arrivals:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Late arrivals: buy certainty first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Late-night Hanoi arrival changes the decision. Even when cheaper options exist, the value of a clear driver, confirmed hotel access, working phone, and no curb negotiation rises sharply after a long flight.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-airport-transfer-late-arrivals"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-airport-transfer-late-arrivals">
<li>Ask the hotel for the exact pickup meeting point, driver name, wait policy, and what happens if immigration is slow.</li>
<li>Keep the hotel phone number and address offline; do not depend on one messaging app or airport Wi-Fi.</li>
<li>Confirm whether the car can reach the hotel door or must stop at the edge of an Old Quarter lane.</li>
<li>If arrival is near midnight, prioritize a central hotel with 24-hour reception over a cheaper but unclear lane.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-airport-transfer-bus-guide:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">When the airport bus is the right answer</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The bus is not a budget badge; it is a fit question. Use it when daylight, luggage, phone data, and final walking distance all make sense. Noi Bai public transport page lists Route No.86 at 50,000 VND/trip, while several other public routes are listed at lower fares but may not solve the Old Quarter handoff as cleanly.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-airport-transfer-bus-guide">
<thead><tr><th>Bus question</th><th>Good sign</th><th>Bad sign</th></tr></thead>
<tbody>
<tr><td data-label="Bus question">Do you know the stop?</td><td data-label="Good sign">Your hotel is near Hoan Kiem, the Old Quarter edge, or an easy taxi hop from the stop.</td><td data-label="Bad sign">The hotel lane needs a confusing walk with luggage.</td></tr>
<tr><td data-label="Bus question">Is the arrival hour forgiving?</td><td data-label="Good sign">Daylight or early evening, reception open, no tight dinner/tour plan.</td><td data-label="Bad sign">Late night, rain, heavy jet lag, or early pickup the next morning.</td></tr>
<tr><td data-label="Bus question">Is your luggage simple?</td><td data-label="Good sign">Carry-on or one easy bag.</td><td data-label="Bad sign">Multiple checked bags, stroller, sports gear, or family luggage.</td></tr>
<tr><td data-label="Bus question">Can you recover if wrong?</td><td data-label="Good sign">Working data, cash, and enough time to switch to taxi.</td><td data-label="Bad sign">No data, no VND, low battery, or a closing hotel desk.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-airport-transfer-taxi-app-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Taxi and app checks that prevent the usual mistakes</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-airport-transfer-taxi-app-checks"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-airport-transfer-taxi-app-checks">
<li>Ignore unsolicited arrivals-hall approaches and choose the queue, the app, or the hotel name on a written confirmation.</li>
<li>For apps, match the plate and driver details before entering, then share the ride or keep the map visible.</li>
<li>For taxis, confirm the fare basis before leaving the airport area; keep small cash and card backup separate.</li>
<li>Keep your hotel address offline. A screenshot in Vietnamese often solves more than another spoken explanation.</li>
<li>Do not accept a mid-route hotel switch, shopping stop, or "your hotel is closed" story without calling the hotel yourself.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-airport-transfer-hotel-pickup:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hotel pickup: what premium actually buys</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A hotel pickup is worth paying for when it reduces uncertainty, not merely because it feels fancy. The best version gives a named driver, clear meeting point, flight-delay policy, final lane plan, and hotel accountability if something goes wrong.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-airport-transfer-hotel-pickup">
<thead><tr><th>Ask before paying</th><th>Good answer</th><th>Weak answer</th></tr></thead>
<tbody>
<tr><td data-label="Ask before paying">Where will the driver wait?</td><td data-label="Good answer">Named gate/column/arrival point plus hotel sign or driver name.</td><td data-label="Weak answer">"Outside" or "driver will call" only.</td></tr>
<tr><td data-label="Ask before paying">What if immigration is slow?</td><td data-label="Good answer">Clear wait time and extra-fee policy.</td><td data-label="Weak answer">No policy until you arrive.</td></tr>
<tr><td data-label="Ask before paying">Can the car reach the hotel?</td><td data-label="Good answer">Hotel explains the closest legal drop-off and luggage help if needed.</td><td data-label="Weak answer">No lane/access awareness.</td></tr>
<tr><td data-label="Ask before paying">What car size?</td><td data-label="Good answer">Vehicle size matches passengers and luggage.</td><td data-label="Weak answer">Small sedan for family luggage.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-airport-transfer-cost-buffer:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cost buffer: spend where friction is highest</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The cheapest transfer is only best when it leaves the first night intact. A modest arrival buffer often buys more value than one extra attraction because it protects sleep, phone setup, cash setup, and the first morning.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-airport-transfer-cost-buffer">
<thead><tr><th>Budget choice</th><th>When to keep it cheap</th><th>When to upgrade</th></tr></thead>
<tbody>
<tr><td data-label="Budget choice">Bus</td><td data-label="When to keep it cheap">Daylight, light luggage, central stop, no fragile first-night plan.</td><td data-label="When to upgrade">Rain, late arrival, family bags, tight dinner, or confusing lane.</td></tr>
<tr><td data-label="Budget choice">App/taxi</td><td data-label="When to keep it cheap">You can verify the vehicle, route, and fare basis calmly.</td><td data-label="When to upgrade">Phone uncertainty, queue chaos, or poor sleep before a next-day transfer.</td></tr>
<tr><td data-label="Budget choice">Hotel pickup</td><td data-label="When to keep it cheap">Skip it when you land midday, have data, and the hotel is easy.</td><td data-label="When to upgrade">Use it when the first-night handoff is the highest-risk part of the route.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-airport-transfer-safety-scams:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Safety and scam avoidance without paranoia</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Hanoi airport transfers are manageable when you reduce ambiguity early. The goal is not fear; it is to keep the first decision boring enough that the trip starts well.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-airport-transfer-safety-scams"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-airport-transfer-safety-scams">
<li>Use official queues, verified app details, or a hotel-confirmed pickup rather than a persuasive person in the arrivals hall.</li>
<li>Do not hand over your phone, passport, or all cash to solve a driver conversation.</li>
<li>Keep one small VND amount for arrival, one card backup, and the hotel address screenshot separate.</li>
<li>Call or message the hotel directly if someone claims your booking, street, or hotel entrance has changed.</li>
<li>Use <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>, <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>, and <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a> before arrival if you are a first-time visitor.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-airport-transfer-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before you fly</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-airport-transfer-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-airport-transfer-live-checks">
<li>Check <a href="https://noibaiairport.vn/en/public-transportations-nid1.html" target="_blank" rel="noopener">Noi Bai airport public transportation</a> before relying on bus routes, listed fares, taxi posture, or airport-side service notes.</li>
<li>Use the Vietnam Airlines Hanoi airport-to-city guide in the source trail and your airline arrival time before planning the first night too tightly.</li>
<li>Use Vietnam.travel transport in the source trail and <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> for the wider Vietnam movement context.</li>
<li>Use <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a> and <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a> if heavy rain, storm disruption, or heat will change the final walk.</li>
<li>Use <a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a> and <a href="/compare/old-quarter-vs-french-quarter-vs-west-lake/">Old Quarter vs French Quarter vs West Lake</a> before choosing a hotel lane that makes the transfer harder than it needs to be.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-airport-transfer-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hanoi airport to Old Quarter FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-hanoi-airport-transfer-faq">
<details><summary>What is the best way to get from Hanoi airport to the Old Quarter?</summary><p>For most first-time international arrivals, use hotel pickup, a verified airport taxi, or a ride-hailing app if your phone works. Use the airport bus when you land in daylight, carry light luggage, and your hotel is close to a practical stop.</p></details>
<details><summary>Is the airport bus from Noi Bai worth it?</summary><p>Yes when the final walk is easy and you are not arriving late or tired. The value disappears quickly if you need to drag luggage through rain, traffic, or a confusing Old Quarter lane.</p></details>
<details><summary>Can I use Grab or another ride-hailing app at Noi Bai?</summary><p>Often yes, but treat it as a process: activate data, match plate and driver, confirm the pickup point, and keep the hotel address ready. If the app pickup feels confusing after a long flight, use a verified taxi or hotel car.</p></details>
<details><summary>Should I prebook a hotel transfer?</summary><p>Prebook when you arrive late, travel with children, carry heavy luggage, or have a premium short stay. Skip it when you land midday, have working data, and your hotel is easy to reach.</p></details>
<details><summary>How long does Hanoi airport to Old Quarter take?</summary><p>Plan roughly an hour door to door in normal conditions and longer in peak traffic, heavy rain, or with a difficult final lane. Do not schedule a fixed dinner, tour, or train too close to arrival.</p></details>
<details><summary>Is it safe to take a taxi from Hanoi airport?</summary><p>Yes when you use official queues, verified apps, or hotel-confirmed drivers. The avoidable risk is accepting a random approach, unclear fare, or unverified car when tired.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then use <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>, <a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a>, <a href="/compare/old-quarter-vs-french-quarter-vs-west-lake/">Old Quarter vs French Quarter vs West Lake</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>, <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before turning the first night into an improvised transfer.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-hanoi-airport-transfer-hero:v1',
    'concierge verdict' => 'vg-hanoi-airport-transfer-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-hanoi-airport-transfer-at-a-glance:v1',
    'photo grid' => 'vg-hanoi-airport-transfer-photo-grid:v1',
    'source diversity' => 'vg-hanoi-airport-transfer-source-diversity:v1',
    'verdict matrix' => 'vg-hanoi-airport-transfer-verdict-matrix:v1',
    'arrival flow' => 'vg-hanoi-airport-transfer-arrival-flow:v1',
    'option scorecard' => 'vg-hanoi-airport-transfer-option-scorecard:v1',
    'late arrivals' => 'vg-hanoi-airport-transfer-late-arrivals:v1',
    'bus guide' => 'vg-hanoi-airport-transfer-bus-guide:v1',
    'taxi app checks' => 'vg-hanoi-airport-transfer-taxi-app-checks:v1',
    'hotel pickup' => 'vg-hanoi-airport-transfer-hotel-pickup:v1',
    'cost buffer' => 'vg-hanoi-airport-transfer-cost-buffer:v1',
    'safety scams' => 'vg-hanoi-airport-transfer-safety-scams:v1',
    'live checks' => 'vg-hanoi-airport-transfer-live-checks:v1',
    'FAQ' => 'vg-hanoi-airport-transfer-faq:v1',
    'related routes shortcode' => '[vg_related_routes]',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_hanoi_airport_transfer_ops_assert_required_content_markers($content, $required_content_markers);
vg_hanoi_airport_transfer_ops_assert_internal_page_links_are_published('Hanoi Airport to Old Quarter guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Hanoi Airport to Old Quarter',
    'post_name'      => 'hanoi-airport-to-old-quarter',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_hanoi_airport_transfer_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led guide to getting from Hanoi airport to the Old Quarter by hotel pickup, taxi, app ride, airport bus, or public bus.',
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
    vg_hanoi_airport_transfer_ops_fail('Could not publish Hanoi Airport to Old Quarter: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_hanoi_airport_transfer_ops_fail('Could not publish Hanoi Airport to Old Quarter: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Hanoi Airport to Old Quarter: Taxi, Bus and Arrival Plan');
update_post_meta($page_id, 'rank_math_description', 'How to get from Hanoi airport to Old Quarter: choose hotel pickup, taxi, app ride, airport bus, or public bus by arrival hour and luggage.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Hanoi airport to Old Quarter');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Choose the Noi Bai to Old Quarter transfer by arrival hour, luggage, phone data, traveler confidence, hotel lane, and first-night risk rather than by the lowest visible fare.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published Hanoi Airport to Old Quarter with a concierge verdict, fast answer table, licensed arrival photography, source-diversity panel, transfer verdict matrix, calm arrival flow, option scorecard, late-arrival rules, bus guide, taxi/app checks, hotel-pickup audit, cost buffer, safety/scam posture, live checks, FAQ, Plan hub note, homepage route-spine support, inbound related routes, source trail, and update log.');

$sources_checked = implode(
    "\n",
    [
        "Noi Bai International Airport - public transportation - https://noibaiairport.vn/en/public-transportations-nid1.html - checked {$review_date}; source lists taxi service posture, bus routes, and Route No.86 at 50,000 VND/trip",
        "Noi Bai International Airport - airport profile - https://vietnamairport.vn/en/noi-bai-airport - checked {$review_date}",
        "Vietnam Airlines - Hanoi airport to Hanoi city guide - https://www.vietnamairlines.com/us/en/plan-book/travel/travel-guide/hanoi-airport-to-hanoi-city - checked {$review_date}",
        "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}",
        "National Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked {$review_date}; live weather checks required close to arrival",
        "Official Vietnam e-visa portal - https://evisa.gov.vn/ - checked {$review_date}",
        "Wikimedia Commons image record - Noi Bai International Airport T2 Waiting Area - https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Hanoi Hoan Kiem Lake - https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Cho Dong Xuan, Old Quarter, Hanoi - https://commons.wikimedia.org/wiki/File:Cho_Dong_Xuan,_Old_Quarter,_Hanoi,_Vietnam_(5245806573).jpg - license checked {$review_date}",
    ]
);
update_post_meta($page_id, 'vg_eeat_sources_checked', $sources_checked);
update_post_meta($page_id, 'vg_eeat_field_note', 'Noi Bai to Old Quarter transfer choice should be made before landing. The strongest answer changes with arrival time, luggage, phone data, hotel lane access, traveler fatigue, children, rain, and whether the first night needs recovery or a usable Hanoi evening.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Airport-transfer guide built as a practical arrival-decision tool rather than a generic taxi/bus list.\nConcierge verdict separates hotel pickup, verified taxi/app ride, airport bus, and public bus by traveler state.\nFast answer table ties transfer choice to long-haul arrivals, daylight arrivals, family travel, budget travel, and reception timing.\nLicensed photo proof shows Noi Bai airport, Hoan Kiem, and Old Quarter context with visible credits.\nSource-diversity panel separates official airport/transport evidence from judgment about hotel lanes, fatigue, and final walks.\nVerdict matrix and scorecard make the transfer decision by friction, control, and first-night value rather than one stale fare.\nArrival flow turns phone, cash, address, pickup, and hotel-lane details into a repeatable checklist.\nLate-arrival, bus, taxi/app, hotel-pickup, cost-buffer, and safety modules provide original judgment beyond rewritten search results.\nLive checks, FAQ, source trail, update log, and related routes keep the page durable while warning users to verify fares, routes, apps, and weather close to travel.");

$related_routes = "Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Place the airport transfer inside Hanoi's route role, night count, first-day recovery, and northern launch plan.\nWhere to Stay in Hanoi | /destinations/where-to-stay-in-hanoi/ | Choose the hotel area that makes the airport-to-hotel handoff and first morning simple.\nOld Quarter vs French Quarter vs West Lake | /compare/old-quarter-vs-french-quarter-vs-west-lake/ | Compare the central sleep zones when the final transfer lane, walking distance, or first-night noise will change the arrival plan.\nBest Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Decide what the first evening or first full day can realistically hold after arrival.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Avoid placing Ninh Binh, the bay, or another long outside day too close to a fragile arrival or departure transfer.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Put the airport transfer beside flights, trains, buses, ferries, and private cars across the route.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Prepare arrival cash, cards, ATM habits, and backup payment before choosing taxi, bus, or hotel pickup.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, ride-hailing, hotel contact, and pickup matching available before leaving the arrivals hall.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair airport arrivals, taxi/app matching, luggage, and first-night movement with calm risk habits.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget the arrival transfer as friction control, not only as a line-item fare.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check rain, storm, heat, and seasonal disruption before relying on long walks or tight first-night plans.\nVietnam E-Visa | /plan/vietnam-evisa/ | Recheck official entry timing before assuming the arrival hour and first-night transfer will be simple.\n7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Protect the first Hanoi night when a one-week northern route has little room for mistakes.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Keep the first transfer clean before Hanoi, Ninh Binh, the bay, and Central Vietnam start competing for time.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use two weeks to protect the arrival handoff before wider country movement.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use a longer route to buy calmer arrival and departure margins instead of extra friction.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before turning airport arrival into a last-minute decision.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether Hanoi is the right northern entry before locking first-night logistics.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check interruption, missed connection, road transfer, and medical backup posture before travel.";
vg_hanoi_airport_transfer_ops_assert_related_route_meta_links_are_published('Hanoi Airport to Old Quarter related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and arrival image: Noi Bai International Airport T2 waiting area by Sky 269, CC BY-SA 4.0. Body images: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0; Cho Dong Xuan, Old Quarter, Hanoi by yeowatzup, CC BY 2.0.');
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
        vg_hanoi_airport_transfer_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_hanoi_airport_transfer_ops_fail('Hanoi Airport to Old Quarter was updated but is not published.');
}

vg_hanoi_airport_transfer_ops_refresh_plan_hub();
vg_hanoi_airport_transfer_ops_refresh_homepage_route_spine();
vg_hanoi_airport_transfer_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_hanoi_airport_transfer_ops_log("Published Hanoi Airport to Old Quarter: {$page_id} {$updated_permalink}");

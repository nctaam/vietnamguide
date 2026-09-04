<?php
/**
 * Publish the Vietnam Travel Guide pillar using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-vietnam-travel-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_TRAVEL_GUIDE_REPUBLISH');

    if (! is_string($value)) {
        return false;
    }

    return trim($value) === '1';
}

function vg_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_ops_fail('Could not resolve a valid WordPress author for the Vietnam Travel Guide.');
}

function vg_ops_internal_path_from_href(string $href): ?string
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

function vg_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_ops_internal_path_from_href($href);

        if ($path === null) {
            continue;
        }

        $paths[$path] = true;
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
        vg_ops_fail(implode(PHP_EOL, $failures));
    }

    vg_ops_log("Validated {$label} internal page links are published.");
}

function vg_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_ops_refresh_plan_hub(): void
{
    $hub = get_page_by_path('plan', OBJECT, 'page');

    if (! $hub instanceof WP_Post) {
        vg_ops_log('Skipped Plan hub refresh: plan page was not found.');
        return;
    }

    if ($hub->post_status !== 'publish') {
        vg_ops_log('Skipped Plan hub refresh: plan page is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('plan/vietnam-travel-guide')) {
        vg_ops_log('Skipped Plan hub refresh: Vietnam Travel Guide is not published.');
        return;
    }

    $marker = '<!-- vg-travel-guide-plan-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Start with the planning order</h3><p>For a first Vietnam trip, use the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> as the main orientation page, then move into visa, timing, cost, and itinerary decisions.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Plan hub travel guide note', $block);

    $marked_block = $marker . "\n" . $block;

    if (str_contains($hub->post_content, $marked_block)) {
        vg_ops_log('Skipped Plan hub refresh: current travel guide note already present.');
        return;
    }

    if (str_contains($hub->post_content, $marker)) {
        $updated_content = preg_replace(
            '/' . preg_quote($marker, '/') . '\R<!-- wp:group\b.*?<!-- \/wp:group -->/s',
            $marked_block,
            $hub->post_content,
            1,
            $replacement_count
        );

        if (! is_string($updated_content) || $replacement_count !== 1) {
            vg_ops_fail('Could not confidently refresh the Plan hub travel guide note.');
        }
    } else {
        $updated_content = rtrim($hub->post_content) . "\n\n" . $marked_block;
    }

    $result = wp_update_post(
        [
            'ID'           => $hub->ID,
            'post_content' => $updated_content,
        ],
        true
    );

    if (is_wp_error($result)) {
        vg_ops_fail('Could not refresh Plan hub note: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Plan hub note: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Plan hub travel guide note: {$hub->ID}");
}

$parent = get_page_by_path('plan', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: plan');
}

$page = get_page_by_path('plan/vietnam-travel-guide', OBJECT, 'page');
$allow_republish = vg_ops_force_republish_enabled();

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('Vietnam Travel Guide is not a draft. Set VG_FORCE_TRAVEL_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    $permalink = get_permalink($page);
    vg_ops_log("Preflight Vietnam Travel Guide: {$page->ID} {$page->post_status} {$permalink}");
} else {
    vg_ops_log('Preflight Vietnam Travel Guide: no existing page found; creating a child page under /plan/.');
}

$guide_hero_image = esc_url(set_url_scheme(get_stylesheet_directory_uri(), 'https') . '/assets/images/ha-long-bay-vietnam-hero.jpg');
$hanoi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$hanoi_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg');
$trang_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');
$trang_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg');
$hue_citadel_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg/1920px-Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$hue_citadel_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$hoi_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hoi_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg');
$hcmc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg');
$hcmc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg');
$hai_van_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1920px-Vietnam%2C_Hai-Van-Pass.jpg');
$hai_van_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg');

$content = <<<HTML
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$guide_hero_image}","dimRatio":55,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Limestone islands in Ha Long Bay, Vietnam, used as a Vietnam travel planning hero image" src="{$guide_hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed pillar guide - Updated July 16, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Vietnam Travel Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">A strong Vietnam trip is planned in the right order: entry rules, regional season, route length, budget, money, connectivity, health, transfers, then bookings. This guide is the long-term starting point for international visitors who want fewer rushed decisions and more practical confidence.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: Vietnam gets easier when you stop asking what to see first and start asking what must be true for the trip to work.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>
<!-- /wp:html -->
</div>
</div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Plan Vietnam as a sequence of constraints, not a list of famous places.</strong> For most first trips, confirm entry permission, choose the right weather window for your route, keep the trip to two strong regions if time is short, price the route honestly, and leave transfer buffer before high-value experiences.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><strong>Best for:</strong> first-time visitors, couples, families, and premium travelers who want a clean planning order before comparing destinations.</li>
<li><strong>Avoid if:</strong> you are already solving a narrow question such as one city, one border gate, or one hotel area.</li>
<li><strong>Verify before booking:</strong> entry rules, regional weather, route length, money logistics, domestic transport, hotel cancellation terms, and the realistic cost of every intercity move.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-travel-guide-regional-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Vietnam by chapter, not by checklist</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A strong first trip treats Vietnam as chapters with different jobs. Hanoi and the north set context and landscape, central Vietnam slows the middle with food and heritage, and Ho Chi Minh City only earns its place when the south becomes a real chapter rather than a rushed exit.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-travel-guide-regional-photo-grid" aria-label="Vietnam route chapter photography">
<figure class="vg-guide-photo"><img src="{$hanoi_image}" alt="Hoan Kiem Lake in central Hanoi, Vietnam" loading="lazy" decoding="async"><figcaption>Hanoi is the best place to start when the trip needs northern context before countryside, bay, or mountain decisions. Image: <a href="{$hanoi_credit_url}" target="_blank" rel="license noopener">Alex 69200 vx / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$trang_an_image}" alt="Boats and limestone scenery at Trang An in Ninh Binh, Vietnam" loading="lazy" decoding="async"><figcaption>Ninh Binh adds landscape value when it calms the route; it becomes weaker when it is squeezed into an already fragile transfer chain. Image: <a href="{$trang_an_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hue_citadel_image}" alt="Historic architecture inside the Hue Citadel in central Vietnam" loading="lazy" decoding="async"><figcaption>Hue gives central Vietnam historical weight, but it needs protected time; a rushed lunch stop rarely delivers the value travelers expect. Image: <a href="{$hue_citadel_credit_url}" target="_blank" rel="license noopener">CEphoto, Uwe Aranas / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hoi_an_image}" alt="Evening street scene in Hoi An Ancient Town, Vietnam" loading="lazy" decoding="async"><figcaption>Hoi An is strongest as the slower middle of a route, where food, walking, countryside, tailoring, and weather flexibility can breathe. Image: <a href="{$hoi_an_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall and Nguyen Hue walking street at night, Vietnam" loading="lazy" decoding="async"><figcaption>Ho Chi Minh City works best when the route has room for southern history, food, city energy, and Mekong logic, not just a departure airport. Image: <a href="{$hcmc_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-travel-guide-transfer-feature:v1 -->
<!-- wp:html -->
<div class="vg-guide-photo-feature vg-travel-guide-transfer-feature">
<figure><img src="{$hai_van_image}" alt="Mountain and coastal view from Hai Van Pass between Hue and Da Nang, Vietnam" loading="lazy" decoding="async"><figcaption>Hai Van Pass can turn a transfer into a route chapter when weather, timing, luggage, and stops are planned. Image: <a href="{$hai_van_credit_url}" target="_blank" rel="license noopener">Wolkenkratzer / CC BY-SA 4.0</a>.</figcaption></figure>
<div class="vg-guide-photo-copy"><p class="vg-kicker">Transfer value</p><h2>Some transfers are part of the experience. Most are just pressure.</h2><p>Before adding a stop, ask whether the movement creates meaning: a scenic pass, a better flight path, a calmer hotel base, or a stronger cultural chapter. If it only adds another check-in, the route is paying time for a name.</p><p>Premium Vietnam planning usually means fewer weak moves, not more expensive ones. A private transfer, domestic flight, cruise pickup, or train only earns its place when it protects the experience around it.</p></div>
</div>
<!-- /wp:html -->

<!-- vg-travel-guide-planning-flow:v1 -->
<!-- wp:group {"className":"vg-travel-guide-flow","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-travel-guide-flow">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Planning flow</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The five decisions that make the rest easier</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this sequence before comparing hotels, tours, or day-by-day details. Each step removes a different kind of planning noise.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<ol class="vg-travel-flow-list" aria-label="Vietnam trip planning order">
<li><span>01</span><strong>Entry</strong><p>Confirm visa route, passport details, arrival/departure logic, and border assumptions before money hardens.</p></li>
<li><span>02</span><strong>Season</strong><p>Match the month to the regions you will actually visit, especially beaches, mountains, cruises, and central Vietnam rain risk.</p></li>
<li><span>03</span><strong>Length</strong><p>Decide how many regions your nights can support. Ten days and fourteen days are different route problems.</p></li>
<li><span>04</span><strong>Cost</strong><p>Price the route by movement, comfort, cruise/tour choices, peak dates, and buffer, not a single daily average.</p></li>
<li><span>05</span><strong>Trade-offs</strong><p>Choose what to skip. Vietnam rewards one strong middle chapter more than an impressive-looking map.</p></li>
</ol>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Plan in this order</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Most weak Vietnam itineraries fail because the planning order is wrong. Travelers start with a famous route, then discover the visa, weather, flight routing, or transfer time does not support it. Use this order instead.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-travel-guide-planning-order">
<thead>
<tr><th>Planning step</th><th>Decision to make</th><th>Why it comes first</th><th>Best next guide</th></tr>
</thead>
<tbody>
<tr><td data-label="Planning step">1. Entry rules</td><td data-label="Decision to make">Confirm whether you need an e-visa, visa exemption, or another entry route.</td><td data-label="Why it comes first">Entry permission controls flight timing, border choice, and booking risk.</td><td data-label="Best next guide"><a href="/plan/vietnam-evisa/">Vietnam E-Visa guide</a></td></tr>
<tr><td data-label="Planning step">2. Season and route climate</td><td data-label="Decision to make">Match the route to the month, not the country to one weather claim.</td><td data-label="Why it comes first">Northern, central, and southern Vietnam can behave differently in the same month.</td><td data-label="Best next guide"><a href="/plan/best-time-to-visit-vietnam/">Best Time guide</a></td></tr>
<tr><td data-label="Planning step">3. Route length</td><td data-label="Decision to make">Choose how many regions you can travel well.</td><td data-label="Why it comes first">Too many bases turn the trip into check-ins, airports, and recovery time.</td><td data-label="Best next guide"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Planning step">4. Budget shape</td><td data-label="Decision to make">Price the route as land-only before comparing hotels or tours.</td><td data-label="Why it comes first">Route, comfort, season, and transfer choices move the budget more than one daily number.</td><td data-label="Best next guide"><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a></td></tr>
<tr><td data-label="Planning step">5. Destination trade-offs</td><td data-label="Decision to make">Decide what to skip before adding more places.</td><td data-label="Why it comes first">Vietnam rewards focus. Similar stops often compete for the same energy and weather window.</td><td data-label="Best next guide"><a href="/compare/">Compare routes and destinations</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Decision map for first-time visitors</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This map keeps the guide useful over time. It does not pretend one route fits everyone; it shows which decision changes the rest of the plan.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-travel-guide-decision-map">
<thead>
<tr><th>If your priority is...</th><th>Start here</th><th>Watch out for</th><th>VietnamGuide direction</th></tr>
</thead>
<tbody>
<tr><td data-label="If your priority is...">A first trip that feels complete</td><td data-label="Start here">Hanoi, Ninh Binh, a bay cruise, Hue or Hoi An, and Da Nang departure if flights allow.</td><td data-label="Watch out for">Adding Ho Chi Minh City and Mekong without enough days.</td><td data-label="VietnamGuide direction">Use the 10-day north-plus-central route as the baseline, then add only if you have time.</td></tr>
<tr><td data-label="If your priority is...">Food and heritage</td><td data-label="Start here">Hanoi, Hue, Hoi An, and central Vietnam countryside.</td><td data-label="Watch out for">Treating beach weather as guaranteed.</td><td data-label="VietnamGuide direction">Favor fewer bases and better context walks or guides.</td></tr>
<tr><td data-label="If your priority is...">Landscapes</td><td data-label="Start here">Northern Vietnam first: Hanoi, Ninh Binh, Lan Ha or Ha Long Bay, and one mountain/countryside extension if time allows.</td><td data-label="Watch out for">Forcing remote mountains into a cross-country itinerary.</td><td data-label="VietnamGuide direction">Keep the trip north-focused or add central Vietnam only with enough days.</td></tr>
<tr><td data-label="If your priority is...">A premium trip</td><td data-label="Start here">Better hotel locations, stronger guides, fewer transfers, and one excellent cruise or cultural experience.</td><td data-label="Watch out for">Using budget to add more cities instead of improving timing.</td><td data-label="VietnamGuide direction">Premium Vietnam usually means fewer dead hours, not more stops.</td></tr>
<tr><td data-label="If your priority is...">Family or lower-stress travel</td><td data-label="Start here">Da Nang, Hoi An, Hue, and one northern or southern extension only if transfers are comfortable.</td><td data-label="Watch out for">A new hotel every night.</td><td data-label="VietnamGuide direction">Reduce bases, pay for easier transfers, and keep one flexible half-day before departure.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">What stays useful, what must be checked live</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The safest evergreen Vietnam advice separates durable planning logic from details that change. This page gives the durable logic. For live rules, prices, routes, and alerts, use official sources before money is committed.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-travel-guide-evergreen-checks">
<thead>
<tr><th>Planning area</th><th>Long-term signal</th><th>Live source habit</th><th>Booking risk if skipped</th></tr>
</thead>
<tbody>
<tr><td data-label="Planning area">Regions and weather</td><td data-label="Long-term signal">Vietnam should be planned by northern, central, and southern conditions, not one national weather sentence.</td><td data-label="Live source habit">Use <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel</a> for official climate context and live forecasts or warnings when weather affects the route.</td><td data-label="Booking risk if skipped">Beach, mountain, cruise, and transfer days can fail for different reasons in the same month.</td></tr>
<tr><td data-label="Planning area">Visa and entry</td><td data-label="Long-term signal">Entry rules come before flights, route shape, and non-refundable hotels.</td><td data-label="Live source habit">Start with the official e-visa system at <a href="https://evisa.gov.vn/" target="_blank" rel="noopener">evisa.gov.vn</a> or <a href="https://thithucdientu.gov.vn/" target="_blank" rel="noopener">thithucdientu.gov.vn</a>, then cross-check the detailed e-visa guide.</td><td data-label="Booking risk if skipped">A small passport, date, entry-type, or border-gate mismatch can disrupt the whole trip.</td></tr>
<tr><td data-label="Planning area">Money and payments</td><td data-label="Long-term signal">Vietnam uses Vietnamese dong, and cash remains useful for small local purchases even when cards work in larger businesses.</td><td data-label="Live source habit">Avoid fixed exchange-rate shortcuts; check current card, ATM, fee, and cash needs close to departure. For currency basics, use the State Bank of Vietnam and Vietnam.travel payment guidance.</td><td data-label="Booking risk if skipped">A route that looks cheap can become stressful if cash, card acceptance, and local transport are not planned.</td></tr>
<tr><td data-label="Planning area">Trains and intercity movement</td><td data-label="Long-term signal">Vietnam rewards realistic transfer time more than ambitious city counting.</td><td data-label="Live source habit">For rail decisions, verify trains through Vietnam Railways and <a href="https://dsvn.vn/" target="_blank" rel="noopener">dsvn.vn</a> instead of copying old schedules or prices.</td><td data-label="Booking risk if skipped">One optimistic train, flight, or road transfer can damage a short itinerary.</td></tr>
<tr><td data-label="Planning area">Safety and health</td><td data-label="Long-term signal">Most first-time risks are practical: petty theft, heat, mosquitoes, traffic, food and water illness, and weak medical preparation.</td><td data-label="Live source habit">Use official health and safety guidance, travel insurance terms, and current weather or route alerts. Save local emergency numbers and hotel contacts offline.</td><td data-label="Booking risk if skipped">Small preventable issues can consume the buffer that should protect high-value days.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Route lenses that stay useful</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam changes by season, flight market, and traveler style, but these route lenses hold up. Pick one lens before you open hotel tabs.</p>
<!-- /wp:paragraph -->
<!-- vg-travel-guide-route-family:v1 -->
<!-- wp:html -->
<div class="vg-travel-route-family" aria-label="Vietnam route family comparison">
<article><p class="vg-route-family-label">Default first trip</p><h3>North plus central</h3><p>Best when the route needs Hanoi, Ninh Binh, bay scenery, Hue or Hoi An, and a clean departure without chasing every region.</p><strong>Protects pace</strong></article>
<article><p class="vg-route-family-label">Full-country arc</p><h3>North to south sampler</h3><p>Best with open-jaw flights and enough nights for Ho Chi Minh City to become a southern chapter rather than a late add-on.</p><strong>Costs more movement</strong></article>
<article><p class="vg-route-family-label">Comfort route</p><h3>Central slow route</h3><p>Best for food, heritage, families, couples, and travelers who would rather improve the middle than collect more airport time.</p><strong>Buys calm</strong></article>
<article><p class="vg-route-family-label">Landscape route</p><h3>North scenery route</h3><p>Best when limestone, rice valleys, cooler mountain air, and countryside stays matter more than seeing the whole country.</p><strong>Chooses depth</strong></article>
</div>
<!-- /wp:html -->
<!-- wp:html -->
<table class="vg-decision-table vg-travel-guide-route-lenses">
<thead>
<tr><th>Route lens</th><th>Best for</th><th>Core trade-off</th><th>Use it when</th></tr>
</thead>
<tbody>
<tr><td data-label="Route lens">North plus central</td><td data-label="Best for">Most first-time visitors with 9-12 days.</td><td data-label="Core trade-off">Skips the south to protect pacing.</td><td data-label="Use it when">You want Hanoi, countryside, bay scenery, Hue or Hoi An, and a cleaner departure.</td></tr>
<tr><td data-label="Route lens">North-to-south sampler</td><td data-label="Best for">Open-jaw flights and travelers who accept a faster trip.</td><td data-label="Core trade-off">More airport and transfer time.</td><td data-label="Use it when">Flights into Hanoi and out of Ho Chi Minh City save a real day.</td></tr>
<tr><td data-label="Route lens">Central Vietnam slow route</td><td data-label="Best for">Food, heritage, families, couples, and calmer hotels.</td><td data-label="Core trade-off">Less dramatic northern scenery.</td><td data-label="Use it when">You value pace, comfort, and fewer weather/transfer surprises.</td></tr>
<tr><td data-label="Route lens">North scenery route</td><td data-label="Best for">Landscape-first travelers.</td><td data-label="Core trade-off">Cuts central and southern Vietnam.</td><td data-label="Use it when">You prefer depth over seeing the whole country in one trip.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Official checks before committing money</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This is the anti-spam discipline behind the guide: do not trust a page because it sounds fluent. Trust the planning chain only when it points you to the right source at the right moment.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-travel-guide-official-checks"} -->
<ul class="wp-block-list vg-check-list vg-travel-guide-official-checks">
<li>Entry: verify the current official e-visa domain, application status, entry type, and border gate before paying for tight flights or hotels.</li>
<li>Weather: check the region and route month, then use live warnings when cruises, ferries, mountains, beaches, or road transfers matter.</li>
<li>Money: plan around Vietnamese dong, realistic cash/card use, ATM access, card fees, and a buffer instead of a copied exchange-rate example.</li>
<li>Transport: verify domestic flights, trains, airport transfers, and long road moves on official or operator sources close to travel.</li>
<li>Health and safety: prepare travel insurance, medication, heat and mosquito precautions, and offline contacts before remote or high-value days.</li>
<li>Public holidays: treat Tet and holiday periods as planning constraints because transport, hotels, and local services can behave differently.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">What this guide deliberately does not do</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A long-term Vietnam guide should not pretend to be live infrastructure. We avoid copied schedules, fixed exchange-rate examples, complete visa-exemption lists, and universal daily budgets unless they are actively dated and reviewed. The goal is to help you make better decisions, then send you to official or live sources for details that should not be frozen into evergreen prose.</p>
<!-- /wp:paragraph -->

<!-- vg-travel-guide-mistakes:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">First-trip mistakes worth avoiding</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>These are the errors that make otherwise good Vietnam plans feel thin, rushed, or strangely expensive.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-faq-list vg-travel-guide-mistakes">
<details><summary>Should a first Vietnam trip start in the north or south?</summary><p>Start where your flights, season, and route shape make the trip easiest. For many first-time visitors, Hanoi is the cleaner starting point because northern landscapes and central Vietnam can form a strong first-trip spine. Ho Chi Minh City is a better start when the south is a real chapter, southern weather is the priority, or flight pricing makes the route cleaner.</p></details>
<details><summary>How many regions should I plan on a first trip?</summary><p>With 8-10 full days, two regions usually beat three. With about two weeks, three regions can work if flights are open-jaw and hotel changes are controlled. With less than a week, choose one region and make it feel intentional.</p></details>
<details><summary>What should I skip when the itinerary is too full?</summary><p>Cut the stop that adds the most movement for the least contrast. That often means dropping a one-night city, a fragile day trip, a remote mountain add-on, or a southern extension that is only there because it appears on generic lists.</p></details>
<details><summary>What must be checked live before paying?</summary><p>Check entry rules, regional weather, domestic flight or train times, cruise transfer terms, hotel cancellation rules, public holiday pressure, and payment/cash logistics. Evergreen advice can structure the decision; live sources should confirm the moving parts.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">First-trip checklist</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this checklist before paying for non-refundable hotels, domestic flights, cruises, private guides, or long transfers.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-travel-guide-first-trip-checklist"} -->
<ul class="wp-block-list vg-check-list vg-travel-guide-first-trip-checklist">
<li>Confirm entry rules and passport details before locking flight dates or border crossings.</li>
<li>Check the month against the exact route, especially if central Vietnam beaches or northern mountains matter.</li>
<li>Choose a route length before choosing hotels. The number of bases controls the trip more than star rating.</li>
<li>Price the route land-only: rooms, intercity transport, tours/cruise, food, local transport, visa/admin, and buffer.</li>
<li>Use official rail, weather, currency, health, airport, and visa sources for live logistics rather than copied forum numbers.</li>
<li>Leave one flexible half-day before international departure or any high-value guided experience.</li>
<li>After the route is realistic, use <a href="/destinations/">Destinations</a> and <a href="/compare/">Compare</a> to decide what to skip.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">How this guide connects the site</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this page as the starting point. Then use the <a href="/plan/vietnam-evisa/">Vietnam E-Visa guide</a> for entry rules, the <a href="/plan/best-time-to-visit-vietnam/">Best Time guide</a> for route-season fit, the <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam guide</a> for pacing, the <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost guide</a> for budget shape, and <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a> before phone-based arrival logistics matter.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

vg_ops_assert_internal_page_links_are_published('Vietnam Travel Guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Vietnam Travel Guide',
    'post_name'      => 'vietnam-travel-guide',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Vietnam travel guide for international visitors, with planning order, decision map, route lenses, first-trip checklist, and source trail.',
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
    vg_ops_fail('Could not publish Vietnam Travel Guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Vietnam Travel Guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Vietnam Travel Guide: First Trip Planning Order');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Vietnam travel guide for international visitors, with planning order, route decisions, visa, weather, cost, itinerary, photos, and first-trip checklist.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Vietnam travel guide');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Plan a first Vietnam trip in the right order before choosing destinations, hotels, tours, or transfers.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 16, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Expanded the Vietnam Travel Guide pillar with regional route photography, transfer-value judgment, a five-step planning flow, route-family comparison, first-trip mistake checks, and a direct connectivity route to the SIM/eSIM guide.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Plan your trip - https://vietnam.travel/plan-your-trip - checked July 16, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 16, 2026\nVietnam.travel - Currency and payments in Vietnam - https://vietnam.travel/things-to-do/currency-and-payments-vietnam - checked July 16, 2026\nVietnam.travel - Health and safety - https://vietnam.travel/plan-your-trip/health-safety - checked July 16, 2026\nVietnam National Electronic Visa system - official e-visa portal - https://evisa.gov.vn/ - checked July 16, 2026\nVietnam National Electronic Visa system - alternate official domain - https://thithucdientu.gov.vn/ - checked July 16, 2026\nMinistry of Public Security public service procedure - e-visa procedure reference - https://dichvucong.bocongan.gov.vn/bocongan/bothutuc/tthc?matt=26277 - checked July 16, 2026\nVietnam Railways - official railway website - https://vr.com.vn/en - checked July 16, 2026\nVietnam Railways - official rail fare lookup and booking reference - https://dsvn.vn/ - checked July 16, 2026\nState Bank of Vietnam - Vietnamese Currency - https://sbv.gov.vn/en/vietnamese-currency - checked July 16, 2026\nNational Center for Hydro-Meteorological Forecasting - live weather and warnings reference - https://nchmf.gov.vn/KttvsiteE/en-US/2/index.html - checked July 16, 2026\nWikimedia Commons image record - Hanoi Hoan Kiem Lake - https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg - license checked July 16, 2026\nWikimedia Commons image record - Trang An Landscape Complex, Ninh Binh Province - https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg - license checked July 16, 2026\nWikimedia Commons image record - Hue Vietnam Citadel - https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg - license checked July 16, 2026\nWikimedia Commons image record - Hoi An Ancient Town - https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg - license checked July 16, 2026\nWikimedia Commons image record - Ho Chi Minh City Hall - https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg - license checked July 16, 2026\nWikimedia Commons image record - Vietnam, Hai-Van-Pass - https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg - license checked July 16, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Vietnam gets easier when you stop asking what to see first and start asking what must be true for the trip to work.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Long-term planning order that connects entry, regional season, route length, budget, money, connectivity, transport, safety, and destination trade-offs.\nPhoto-led regional chapter framework that shows why north, central, and south should be planned as route chapters rather than a checklist.\nTransfer-value feature that separates meaningful scenic or logistical movement from weak extra hotel changes.\nDecision map that gives original editorial judgment by traveler priority.\nFive-step planning flow that turns the pillar into a practical decision sequence.\nEvergreen-versus-live-check framework that avoids freezing stale visa, weather, transport, exchange-rate, connectivity, and holiday details into the guide.\nRoute family visual module and route lenses that avoid pretending one itinerary fits all visitors.\nMistake checks for north-versus-south starts, number of regions, what to skip, and live verification before payment.\nOfficial-check checklist focused on booking risk and source discipline.\nFirst-trip checklist focused on decision order, live verification, and buffer.\nRelated decision chain for entry, weather, cost, money, and connectivity next steps.\nInternal links to the site's reviewed E-Visa, Best Time, 10 Days, Travel Cost, Money, and SIM/eSIM guides.\nLicensed route photography with visible credit links.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Vietnam E-Visa Guide | /plan/vietnam-evisa/ | Confirm entry permission before non-refundable bookings.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Match the planning order to regional weather.\nBest Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Decide how much capital time belongs before the north starts moving.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Test whether the chosen route fits the budget.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Set payment and cash rules after the route shape is known.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Choose arrival connectivity before maps, ride-hailing, and local contact details matter.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero: Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0. Body images: Hanoi by Alex 69200 vx, CC BY-SA 4.0; Trang An by Jakub Halun, CC BY 4.0; Hue by CEphoto, Uwe Aranas, CC BY-SA 3.0; Hoi An and Ho Chi Minh City by Steffen Schmitz, CC BY-SA 4.0; Hai Van Pass by Wolkenkratzer, CC BY-SA 4.0.');
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
        vg_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_ops_fail('Vietnam Travel Guide was updated but is not published.');
}

vg_ops_refresh_plan_hub();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Vietnam Travel Guide: {$page_id} {$updated_permalink}");

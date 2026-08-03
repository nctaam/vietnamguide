<?php
/**
 * Publish the Transport Within Vietnam guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-transport-within-vietnam-guide.php --allow-root
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
    $value = getenv('VG_FORCE_TRANSPORT_GUIDE_REPUBLISH');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the Transport Within Vietnam guide.');
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

function vg_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
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

    if (! vg_ops_published_page_exists('plan/transport-within-vietnam')) {
        vg_ops_log('Skipped Plan hub refresh: Transport Within Vietnam guide is not published.');
        return;
    }

    $marker = '<!-- vg-transport-plan-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose transport by route pressure</h3><p>The <a href="/plan/transport-within-vietnam/">Transport Within Vietnam guide</a> helps travelers decide when to fly, take the train, hire a private car, use a sleeper bus, or keep airport and ferry details flexible.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Plan hub transport note', $block);

    $marked_block = $marker . "\n" . $block;

    if (str_contains($hub->post_content, $marked_block)) {
        vg_ops_log('Skipped Plan hub refresh: current transport note already present.');
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
            vg_ops_fail('Could not confidently refresh the Plan hub transport note.');
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
        vg_ops_fail('Could not refresh Plan hub transport note: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Plan hub transport note: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Plan hub transport note: {$hub->ID}");
}

$parent = get_page_by_path('plan', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: plan');
}

$page = get_page_by_path('plan/transport-within-vietnam', OBJECT, 'page');
$allow_republish = vg_ops_force_republish_enabled();

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('Transport Within Vietnam guide is not a draft. Set VG_FORCE_TRANSPORT_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    $permalink = get_permalink($page);
    vg_ops_log("Preflight Transport Within Vietnam guide: {$page->ID} {$page->post_status} {$permalink}");
} else {
    vg_ops_log('Preflight Transport Within Vietnam guide: no existing page found; creating a child page under /plan/.');
}

$guide_hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1920px-Vietnam%2C_Hai-Van-Pass.jpg');
$hai_van_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg');
$railway_station_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/95/Hanoi_Railway_Station_20130725.jpg/1920px-Hanoi_Railway_Station_20130725.jpg');
$railway_station_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi_Railway_Station_20130725.jpg');
$airport_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/56/Noi_Bai_International_Airport_T2_Waiting_Area.jpg/1920px-Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$airport_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$bay_image = esc_url(set_url_scheme(get_stylesheet_directory_uri(), 'https') . '/assets/images/ha-long-bay-vietnam-hero.jpg');
$bay_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg');

$content = <<<HTML
<!-- wp:group {"align":"full","className":"vg-guide-hero vg-transport-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero vg-transport-hero">
<!-- wp:cover {"url":"{$guide_hero_image}","dimRatio":55,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hai Van Pass coastal road between Hue and Da Nang, Vietnam, used as transport route proof" src="{$guide_hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed transport guide - Updated July 17, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Transport Within Vietnam</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">The best way to move within Vietnam is not one mode. It is the mode that protects the route: fly when distance would eat the trip, use trains when the journey is part of the experience, hire private cars when flexibility changes the day, and keep buses or ferries for routes where they genuinely fit.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: judge every transfer door to door, not by the ticket time alone.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hai_van_credit_url}" target="_blank" rel="license noopener">Wolkenkratzer / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->
<!-- vg-transport-hero:v1 -->

<!-- wp:group {"className":"vg-concierge-verdict vg-transport-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-transport-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For long north-south moves, domestic flights usually buy back the most usable time.</strong> For Hanoi-Da Nang or Hue-Da Nang style corridors, trains and private cars can make the transfer feel like part of the trip. Sleeper buses are a budget or specific-corridor answer, not the default premium recommendation.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><strong>Fly:</strong> when the alternative would consume a full day or a poor overnight and the route has limited nights.</li>
<li><strong>Take the train:</strong> when scenery, overnight rhythm, or city-center stations add value and you accept slower movement.</li>
<li><strong>Use a private car or transfer:</strong> when stops, luggage, children, late arrivals, or a scenic handoff make flexibility worth paying for.</li>
<li><strong>Keep live details separate:</strong> schedules, fares, ferry weather, baggage rules, and airport pickup points should be checked close to booking.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- vg-transport-editorial-proof:v1 -->
<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-transport-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-transport-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-transport-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Choose the mode by what the route needs</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table">
<thead>
<tr><th>Route problem</th><th>Best first answer</th><th>Why</th><th>What to live-check</th></tr>
</thead>
<tbody>
<tr><td data-label="Route problem">Hanoi to Ho Chi Minh City, or north to far south</td><td data-label="Best first answer">Domestic flight</td><td data-label="Why">The overland journey is memorable for some travelers but too expensive in time for most short trips.</td><td data-label="What to live-check">Baggage, airport transfer time, delay risk, and separate-ticket connection buffer.</td></tr>
<tr><td data-label="Route problem">Hanoi to Da Nang or Hue, with an overnight available</td><td data-label="Best first answer">Train or flight</td><td data-label="Why">Train can be a route-style choice; flight is cleaner when the itinerary is already tight.</td><td data-label="What to live-check">Cabin class, arrival time, station transfer, and weather around onward plans.</td></tr>
<tr><td data-label="Route problem">Hue, Hai Van Pass, Da Nang, Hoi An</td><td data-label="Best first answer">Private car or train plus transfer</td><td data-label="Why">The central corridor can turn movement into scenery, food, and a calmer handoff.</td><td data-label="What to live-check">Stops, luggage, road timing, driver terms, and poor-weather alternatives.</td></tr>
<tr><td data-label="Route problem">Mekong, islands, or water crossings</td><td data-label="Best first answer">Boat or ferry where geography requires it</td><td data-label="Why">Water transport is a route tool, not a romance claim; it belongs where the map demands it.</td><td data-label="What to live-check">Weather, last departure, port transfer, luggage, seasickness, and cancellation rules.</td></tr>
<tr><td data-label="Route problem">Budget corridor with flexible time</td><td data-label="Best first answer">Sleeper bus only after comparison</td><td data-label="Why">It can save money on specific routes, but comfort, safety, and arrival fatigue are real costs.</td><td data-label="What to live-check">Operator reputation, pickup point, arrival time, seat type, and holiday demand.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-transport-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Transport proof: the route changes the answer</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>These images are included because each one proves a different transport decision. Airports, railway stations, scenic road passes, and river routes do not solve the same planning problem.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-transport-photo-grid" aria-label="Vietnam transport route proof photography">
<figure class="vg-guide-photo"><img src="{$airport_image}" alt="Noi Bai International Airport Terminal 2 waiting area in Hanoi" loading="lazy" decoding="async"><figcaption>Domestic flights are often the cleanest answer when distance would steal a full travel day. Image: <a href="{$airport_credit_url}" target="_blank" rel="license noopener">Sky 269 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$railway_station_image}" alt="Hanoi Railway Station in Vietnam" loading="lazy" decoding="async"><figcaption>Trains earn their place when the route benefits from city-center stations, overnight pacing, or a scenic chapter. Image: <a href="{$railway_station_credit_url}" target="_blank" rel="license noopener">Alancrh / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$guide_hero_image}" alt="Hai Van Pass road and sea view in central Vietnam" loading="lazy" decoding="async"><figcaption>Private cars can be justified in central Vietnam when stops and timing make the transfer part of the experience. Image: <a href="{$hai_van_credit_url}" target="_blank" rel="license noopener">Wolkenkratzer / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$bay_image}" alt="Cruise boats among limestone karsts in Ha Long Bay, Vietnam" loading="lazy" decoding="async"><figcaption>Boats and ferries are essential for some bay, island, and river routes, but weather and last-departure risk need live checks. Image: <a href="{$bay_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-transport-route-proof:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The transport decision in one sentence</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Choose the mode that turns the least valuable hours into the best route outcome. A cheap transfer that ruins the next day is not cheap; a flight that forces a bad airport day is not automatically efficient; and a scenic road or train only deserves the time when it improves the trip.</p>
<!-- /wp:paragraph -->

<!-- vg-transport-corridor-table:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Corridor matrix: what to use on common Vietnam routes</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this as the evergreen route judgment, then verify live schedules and fares before payment. The goal is not to freeze every timetable into the guide; it is to avoid choosing the wrong mode for the trip shape.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-transport-corridor-table">
<thead>
<tr><th>Corridor</th><th>Default premium answer</th><th>Good alternative</th><th>Use caution with</th><th>Editorial reason</th></tr>
</thead>
<tbody>
<tr><td data-label="Corridor">Hanoi - Ho Chi Minh City</td><td data-label="Default premium answer">Fly.</td><td data-label="Good alternative">Multi-stop train only when the rail journey is the trip.</td><td data-label="Use caution with">Single-shot bus or train on a short first trip.</td><td data-label="Editorial reason">This is a country-length move; most travelers need usable days more than endurance.</td></tr>
<tr><td data-label="Corridor">Hanoi - Da Nang or Hue</td><td data-label="Default premium answer">Fly if pace is tight; train if the overnight or scenery is intentional.</td><td data-label="Good alternative">Split the route with Ninh Binh or Phong Nha only when nights allow.</td><td data-label="Use caution with">Late arrivals before a same-day tour or cruise.</td><td data-label="Editorial reason">The mode should serve the route, not a generic idea of authenticity.</td></tr>
<tr><td data-label="Corridor">Hue - Da Nang - Hoi An</td><td data-label="Default premium answer">Private car via Hai Van Pass when stops matter.</td><td data-label="Good alternative">Train Hue-Da Nang plus a separate Hoi An transfer.</td><td data-label="Use caution with">Cheapest shared shuttle if luggage, weather, or timing matters.</td><td data-label="Editorial reason">This is one of the few transfers that can become a route highlight.</td></tr>
<tr><td data-label="Corridor">Da Nang or Hoi An - Ho Chi Minh City</td><td data-label="Default premium answer">Fly.</td><td data-label="Good alternative">Train only for travelers intentionally slowing the central-south leg.</td><td data-label="Use caution with">Sleeper bus unless budget is the main constraint.</td><td data-label="Editorial reason">The flight usually protects enough time to justify airport friction.</td></tr>
<tr><td data-label="Corridor">Ho Chi Minh City - Mekong - islands</td><td data-label="Default premium answer">Private car, guided transfer, flight, ferry, or boat depending on final island or river plan.</td><td data-label="Good alternative">Bus-plus-boat where time and luggage are flexible.</td><td data-label="Use caution with">Last ferry of the day and separate tight connections.</td><td data-label="Editorial reason">Water and port logistics are the route, so build buffer around them.</td></tr>
<tr><td data-label="Corridor">Airport - first hotel</td><td data-label="Default premium answer">Prebook or use official taxi/ride-hailing after late arrivals, families, or long-haul flights.</td><td data-label="Good alternative">Airport bus when luggage is light and arrival timing is easy.</td><td data-label="Use caution with">Unlicensed drivers and unclear pickup points.</td><td data-label="Editorial reason">Arrival friction sets the tone; spend a little to avoid the first avoidable mistake.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-transport-mode-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Transport mode fit guidance</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Each mode has a job. The mistake is asking one mode to solve every route. Use this table when your itinerary starts to feel overbuilt.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-transport-mode-matrix">
<thead>
<tr><th>Mode</th><th>Choose it when</th><th>Avoid it when</th><th>Premium use case</th><th>Live check</th></tr>
</thead>
<tbody>
<tr><td data-label="Mode">Domestic flight</td><td data-label="Choose it when">The road or rail alternative would cost most of a day or weaken the next experience.</td><td data-label="Avoid it when">Airport time is longer than the route value or a scenic transfer would be the point.</td><td data-label="Premium use case">North-south moves, central-to-south handoffs, or final-night positioning.</td><td data-label="Live check">Baggage, airport transfer, delay history, and connection buffer.</td></tr>
<tr><td data-label="Mode">Train</td><td data-label="Choose it when">The station locations, overnight rhythm, or scenery add value.</td><td data-label="Avoid it when">A tired arrival would damage a short itinerary.</td><td data-label="Premium use case">Soft sleeper, four-berth cabin, or scenic central segment selected deliberately.</td><td data-label="Live check">dsvn.vn timing, berth class, ticket name, station, and refund/change rules.</td></tr>
<tr><td data-label="Mode">Private car or transfer</td><td data-label="Choose it when">Flexibility, stops, luggage, children, mobility, or weather protection matters.</td><td data-label="Avoid it when">It only replaces an easy public option without improving the day.</td><td data-label="Premium use case">Hai Van Pass, airport arrivals, Hoi An-Hue, Mekong transfers, or complex family days.</td><td data-label="Live check">Pickup point, vehicle size, included stops, tolls, child seats, and cancellation terms.</td></tr>
<tr><td data-label="Mode">Sleeper bus</td><td data-label="Choose it when">Budget is the priority and the corridor has a reputable operator and tolerable arrival time.</td><td data-label="Avoid it when">Comfort, privacy, sleep, or next-day energy is important.</td><td data-label="Premium use case">Rare; use it as a deliberate savings choice, not a default.</td><td data-label="Live check">Seat type, reviews, pickup/drop-off, holiday booking, and arrival hour.</td></tr>
<tr><td data-label="Mode">Ferry or boat</td><td data-label="Choose it when">The route requires water: islands, Mekong, bays, or river days.</td><td data-label="Avoid it when">Weather, seasickness, or tight onward connections make the day fragile.</td><td data-label="Premium use case">Island chapter, private river day, or well-buffered bay/Delta experience.</td><td data-label="Live check">Weather, last departure, port transfer, luggage, safety policy, and cancellation rules.</td></tr>
<tr><td data-label="Mode">Airport transfer</td><td data-label="Choose it when">Arrival is late, luggage is heavy, or the traveler is tired after a long-haul flight.</td><td data-label="Avoid it when">You have light luggage, daylight, and a straightforward airport bus.</td><td data-label="Premium use case">First-night calm, family arrival, separate-ticket connection, or final-night protection.</td><td data-label="Live check">Official pickup point, driver contact, flight tracking, tolls, and waiting time.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-transport-prebook-flex:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to prebook and what to keep flexible</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The right booking order protects scarce or fragile pieces without turning every ordinary day into a non-refundable commitment.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-transport-prebook-flex">
<thead>
<tr><th>Transport item</th><th>Prebook when</th><th>Keep flexible when</th><th>What to record</th></tr>
</thead>
<tbody>
<tr><td data-label="Transport item">Domestic flights</td><td data-label="Prebook when">The route depends on a specific flight, holiday period, or final international connection.</td><td data-label="Keep flexible when">There are many daily options and the route may change after weather or hotel checks.</td><td data-label="What to record">Baggage allowance, airport, terminal, transfer time, and cancellation/change terms.</td></tr>
<tr><td data-label="Transport item">Train sleeper berths</td><td data-label="Prebook when">You need a specific cabin class, holiday date, or overnight route.</td><td data-label="Keep flexible when">A same-day soft seat on a short scenic segment would be enough.</td><td data-label="What to record">Train number, departure station, berth type, passenger name, ID requirement, and refund policy.</td></tr>
<tr><td data-label="Transport item">Private cars and airport transfers</td><td data-label="Prebook when">Arrival is late, group is large, luggage is heavy, or a scenic route needs stops.</td><td data-label="Keep flexible when">You are in a compact city with easy ride-hailing and no fixed handoff.</td><td data-label="What to record">Driver contact, vehicle size, pickup pin, included stops, tolls, waiting time, and cash/card terms.</td></tr>
<tr><td data-label="Transport item">Sleeper buses</td><td data-label="Prebook when">You are traveling during holidays or need a specific operator/seat.</td><td data-label="Keep flexible when">The bus is only a backup to a better flight or train.</td><td data-label="What to record">Pickup point, drop-off point, seat type, luggage rules, and real arrival hour.</td></tr>
<tr><td data-label="Transport item">Ferries and boats</td><td data-label="Prebook when">There are limited departures, island accommodation depends on arrival, or weather windows are narrow.</td><td data-label="Keep flexible when">The water trip is an optional day tour with multiple operators and refundable terms.</td><td data-label="What to record">Port, departure time, sea-condition policy, luggage, seasickness plan, and last return.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-transport-booking-audit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Before-booking live-check audit</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Run this audit before paying for non-refundable flights, train berths, private transfers, ferries, cruises, or hotel nights tied to a fragile transfer.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-transport-booking-audit"} -->
<ul class="wp-block-list vg-check-list vg-transport-booking-audit">
<li>Map the full transfer door to door: hotel checkout, taxi, station or airport, waiting time, boarding, arrival, luggage, and final transfer.</li>
<li>Check official or primary sources for rail, airport, ferry, weather, baggage, and public-holiday details before final payment.</li>
<li>Confirm whether the next day needs energy. A bad overnight transfer can cost more than a daytime flight.</li>
<li>Protect the first and last nights. Long-haul arrival and international departure are the worst places to test a fragile transfer.</li>
<li>Make weather-sensitive water or mountain plans refundable where possible, especially around ferries, boats, bay cruises, and scenic passes.</li>
<li>Save offline copies of tickets, pickup instructions, operator phone numbers, hotel address, and the local-language destination.</li>
<li>Budget for the hidden costs: airport transfers, tolls, baggage, meals, early check-in, late checkout, and a taxi after an awkward arrival.</li>
</ul>
<!-- /wp:list -->

<!-- vg-transport-mistakes:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Transport mistakes that waste time or money</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Most Vietnam transport mistakes are not dramatic. They are small optimistic assumptions that compound: too many bases, weak arrival transfers, cheap overnight movement, or no buffer before the one day that matters.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-transport-mistakes">
<thead>
<tr><th>Mistake</th><th>Why it happens</th><th>Better move</th><th>What it protects</th></tr>
</thead>
<tbody>
<tr><td data-label="Mistake">Comparing only ticket time</td><td data-label="Why it happens">A one-hour flight looks simple and a train looks slow.</td><td data-label="Better move">Compare door-to-door hours and the quality of the next day.</td><td data-label="What it protects">Energy, hotel timing, and realistic pacing.</td></tr>
<tr><td data-label="Mistake">Using sleeper buses as the default</td><td data-label="Why it happens">They look cheap and seem to save a hotel night.</td><td data-label="Better move">Use them only when the operator, seat, pickup, and arrival time still make sense.</td><td data-label="What it protects">Sleep, safety margin, and the next day's experience.</td></tr>
<tr><td data-label="Mistake">Making every transfer private</td><td data-label="Why it happens">Premium is mistaken for never using public or shared transport.</td><td data-label="Better move">Spend on private support where it changes the day; use trains or flights when they solve the route better.</td><td data-label="What it protects">Budget and authenticity without sacrificing comfort.</td></tr>
<tr><td data-label="Mistake">Forgetting the airport transfer</td><td data-label="Why it happens">The intercity ticket gets booked, but the first and last 30 kilometers are ignored.</td><td data-label="Better move">Plan arrival and departure transfers as part of the same decision.</td><td data-label="What it protects">Late-night calm, families, luggage, and connection buffer.</td></tr>
<tr><td data-label="Mistake">Trusting old ferry or train details</td><td data-label="Why it happens">Copied schedules circulate long after conditions change.</td><td data-label="Better move">Use evergreen mode guidance, then live-check official or operator sources close to travel.</td><td data-label="What it protects">Island plans, rail seats, weather pivots, and refunds.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-transport-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Short answers for common Vietnam transport decisions</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-transport-faq">
<details><summary>Should I fly or take the train from Hanoi to Da Nang?</summary><p>Fly if the itinerary is tight or the next day matters. Take the train when the overnight rhythm, city-center station, or scenic journey is part of the route. Check dsvn.vn for current train options before treating rail as the answer.</p></details>
<details><summary>Are domestic flights the best way to travel Vietnam?</summary><p>They are often the best efficiency choice for long north-south moves, especially on 10-day or 14-day routes. They are not automatically better for short central Vietnam corridors where a train or private car can add value.</p></details>
<details><summary>Are sleeper buses worth it in Vietnam?</summary><p>Sometimes, but mostly as a budget or specific-corridor option. They can save money, but comfort, pickup points, safety margin, and next-day fatigue should be priced into the decision.</p></details>
<details><summary>When should I prebook airport transfers?</summary><p>Prebook when arriving late, traveling with family, carrying heavy luggage, connecting on separate tickets, or heading straight to a distant hotel. Keep it flexible when you have daylight, light luggage, and a simple city arrival.</p></details>
<details><summary>How should I think about ferries and boats?</summary><p>Use them where geography requires them: islands, bays, and river routes. Treat weather, last departure, port transfers, seasickness, luggage, and cancellation policy as live details rather than evergreen promises.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> for the full planning order, then use <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a> before weather-sensitive routes, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a> or <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a> to test pacing, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying for high-friction transfers.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-transport-hero:v1',
    'concierge verdict' => 'vg-transport-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at-a-glance table' => 'vg-transport-at-a-glance:v1',
    'photo grid' => 'vg-transport-photo-grid:v1',
    'route proof' => 'vg-transport-route-proof:v1',
    'corridor table' => 'vg-transport-corridor-table:v1',
    'mode matrix' => 'vg-transport-mode-matrix:v1',
    'prebook/flexible table' => 'vg-transport-prebook-flex:v1',
    'booking audit' => 'vg-transport-booking-audit:v1',
    'mistakes' => 'vg-transport-mistakes:v1',
    'FAQ' => 'vg-transport-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('Transport Within Vietnam guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Transport Within Vietnam',
    'post_name'      => 'transport-within-vietnam',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led guide to choosing transport within Vietnam by route, transfer pressure, domestic flights, trains, private cars, buses, ferries, airport transfers, and live checks.',
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
    vg_ops_fail('Could not publish Transport Within Vietnam guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Transport Within Vietnam guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Transport Within Vietnam: Flights, Trains, Cars and Buses');
update_post_meta($page_id, 'rank_math_description', 'Choose transport within Vietnam by route: domestic flights, trains, private cars, sleeper buses, ferries, airport transfers, prebooking, and live checks.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'transport within Vietnam');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Choose the right Vietnam transport mode by route length, transfer pressure, weather sensitivity, luggage, comfort, budget, and booking flexibility.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 17, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Initial Transport Within Vietnam guide publish with route-oriented verdict, decision table, corridor matrix, transport-mode fit guidance, photo proof, prebook-versus-flexible guidance, live-check audit, failure modes, FAQ, source trail, and update log.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 17, 2026\nVietnam.travel - A traveller's guide to Vietnam's airports - https://vietnam.travel/things-to-do/travellers-guide-vietnams-airports - checked July 17, 2026\nVietnam Railways - official railway website - https://vr.com.vn/en - checked July 17, 2026\nVietnam Railways - official rail ticketing and fare lookup - https://dsvn.vn/ - checked July 17, 2026\nNoi Bai International Airport - transport to/from the airport - https://noibaiairport.vn/en/public-transportations-nid1.html - checked July 17, 2026\nVietnam Airlines - Noi Bai Airport to Hanoi Old Quarter transport overview - https://www.vietnamairlines.com/en-th/distance-from-hanoi-airport-to-old-quarter - checked July 17, 2026\nVietnam.travel - Phu Quy island transport context - https://vietnam.travel/things-to-do/phu-quy-vietnam-island-destination - checked July 17, 2026\nVietnam.travel - Nam Du island transport context - https://vietnam.travel/things-to-do/nam-du-do-your-own-beach-trip - checked July 17, 2026\nWikimedia Commons image record - Hanoi Railway Station - https://commons.wikimedia.org/wiki/File:Hanoi_Railway_Station_20130725.jpg - license checked July 17, 2026\nWikimedia Commons image record - Noi Bai International Airport T2 Waiting Area - https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg - license checked July 17, 2026\nWikimedia Commons image record - Vietnam, Hai-Van-Pass - https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg - license checked July 17, 2026\nWikimedia Commons image record - Ha Long Bay, Vietnam, view from above - https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg - license checked July 17, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Judge every Vietnam transfer door to door, not by the ticket time alone.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Decision-first transport framework that chooses mode by route pressure rather than defaulting to one answer.\nConcierge verdict that positions domestic flights as the efficiency choice for long north-south moves, trains as route-style choices, private cars as flexibility tools, sleeper buses as budget/specific-corridor options, ferries as geography-dependent, and airport transfers as arrival-risk protection.\nAt-a-glance mode table for common route problems and live checks.\nPhoto-led proof grid for airports, railway stations, Hai Van Pass, and bay/water movement using licensed photography with visible credits.\nCorridor matrix for Hanoi-Ho Chi Minh City, Hanoi-Da Nang/Hue, Hue-Da Nang-Hoi An, central-south, Mekong/islands, and airport-first-hotel handoffs.\nMode-fit guidance for domestic flight, train, private car, sleeper bus, ferry/boat, and airport transfer decisions.\nPrebook-versus-flexible table that separates scarce or fragile transport from ordinary local movement.\nBefore-booking live-check audit for door-to-door timing, official sources, weather, tickets, pickup details, and hidden transfer costs.\nFailure-mode table and FAQ that catch ticket-time, sleeper bus, airport transfer, ferry, and stale-schedule mistakes before payment.\nOfficial tourism, airport, rail, airport-transfer, island-route, and image-license source checks.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Use this first if you still need the full planning order.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Use this before weather-sensitive roads, ferries, islands, bay cruises, and scenic transfers.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether the route has too many transfers for a short first trip.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price flights, trains, cars, ferries, and transfer buffers before booking.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and central road image: Hai Van Pass by Wolkenkratzer, CC BY-SA 4.0. Body images: Hanoi Railway Station by Alancrh, CC BY-SA 3.0; Noi Bai International Airport T2 waiting area by Sky 269, CC BY-SA 4.0; Ha Long Bay boats by Vyacheslav Argenberg, CC BY 4.0.');
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
    vg_ops_fail('Transport Within Vietnam guide was updated but is not published.');
}

vg_ops_refresh_plan_hub();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Transport Within Vietnam guide: {$page_id} {$updated_permalink}");

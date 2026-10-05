<?php
/**
 * Publish the 10 Days in Vietnam itinerary guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-10-days-itinerary-guide.php --allow-root
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
    $value = getenv('VG_FORCE_10_DAY_ITINERARY_REPUBLISH');

    if (! is_string($value)) {
        return false;
    }

    return trim($value) === '1';
}

function vg_ops_get_itineraries_parent(): WP_Post
{
    $parent = get_page_by_path('itineraries', OBJECT, 'page');

    if (! $parent instanceof WP_Post) {
        vg_ops_fail('Itineraries hub not found: itineraries');
    }

    return $parent;
}

function vg_ops_get_or_create_10_day_page(WP_Post $parent): WP_Post
{
    $page = get_page_by_path('itineraries/10-days-in-vietnam', OBJECT, 'page');

    if (! $page instanceof WP_Post) {
        $page = get_page_by_path('10-days-in-vietnam', OBJECT, 'page');
    }

    if ($page instanceof WP_Post) {
        return $page;
    }

    $created_id = wp_insert_post(
        [
            'post_title'     => '10 Days in Vietnam',
            'post_name'      => '10-days-in-vietnam',
            'post_status'    => 'draft',
            'post_type'      => 'page',
            'post_parent'    => (int) $parent->ID,
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        ],
        true
    );

    if (is_wp_error($created_id)) {
        vg_ops_fail('Could not create 10 Days in Vietnam draft: ' . $created_id->get_error_message());
    }

    if ((int) $created_id <= 0) {
        vg_ops_fail('Could not create 10 Days in Vietnam draft: WordPress returned an empty post ID.');
    }

    $created_page = get_post((int) $created_id);

    if (! $created_page instanceof WP_Post) {
        vg_ops_fail('Could not load the 10 Days in Vietnam draft after creation.');
    }

    vg_ops_log("Created 10 Days in Vietnam draft: {$created_page->ID}");

    return $created_page;
}

function vg_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

function vg_ops_refresh_itineraries_hub(): void
{
    $hub = get_page_by_path('itineraries', OBJECT, 'page');

    if (! $hub instanceof WP_Post) {
        vg_ops_log('Skipped Itineraries hub refresh: itineraries page was not found.');
        return;
    }

    if ($hub->post_status !== 'publish') {
        vg_ops_log('Skipped Itineraries hub refresh: itineraries page is not published.');
        return;
    }

    $note_marker = '<!-- vg-itineraries-10-day-note:v1 -->';

    if (str_contains($hub->post_content, $note_marker)) {
        vg_ops_log('Skipped Itineraries hub refresh: 10-day note already present.');
        return;
    }

    $note = $note_marker . "\n" . <<<'HTML'
<!-- wp:group {"className":"vg-hub-note vg-itineraries-10-day-note"} -->
<div class="wp-block-group vg-hub-note vg-itineraries-10-day-note"><h3>Start with the 10-day route</h3><p>The dedicated <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam guide</a> now gives the route-first recommendation, alternatives, and booking checks for travelers who cannot add more nights.</p></div>
<!-- /wp:group -->

HTML;

    $next_action = '<!-- wp:group {"className":"vg-next-action"} -->';

    if (str_contains($hub->post_content, $next_action)) {
        $updated_content = str_replace($next_action, $note . $next_action, $hub->post_content, $replacement_count);
    } else {
        $updated_content = rtrim((string) $hub->post_content) . "\n\n" . $note;
        $replacement_count = 1;
    }

    if ($replacement_count < 1) {
        vg_ops_log('Skipped Itineraries hub refresh: insertion point was not found.');
        return;
    }

    $result = wp_update_post(
        [
            'ID'           => $hub->ID,
            'post_content' => $updated_content,
        ],
        true
    );

    if (is_wp_error($result)) {
        vg_ops_fail('Could not refresh Itineraries hub note: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Itineraries hub note: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Itineraries hub guide note: {$hub->ID}");
}

$parent = vg_ops_get_itineraries_parent();
$page = vg_ops_get_or_create_10_day_page($parent);
$allow_republish = vg_ops_force_republish_enabled();
$permalink = get_permalink($page);

if ($page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('10 Days in Vietnam guide is not a draft. Set VG_FORCE_10_DAY_ITINERARY_REPUBLISH=1 to overwrite existing published content.');
}

vg_ops_log("Preflight 10 Days in Vietnam guide: {$page->ID} {$page->post_status} {$permalink}");

$content = <<<'HTML'
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"dimRatio":50,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed itinerary guide - Updated July 15, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">10 Days in Vietnam</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">The best 10-day Vietnam itinerary is not the route with the most famous names. It is the route where every transfer earns its place, weather risk is acknowledged, and you skip enough to enjoy what remains.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Route-first recommendation: most first-time visitors should choose a North + Central Vietnam route, then add the south only if flights and pace make it genuinely easy.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-concierge-verdict vg-itinerary-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-itinerary-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For 10 days, make North + Central Vietnam the default.</strong> Hanoi, Ninh Binh, one bay overnight, Hoi An or Da Nang, and an optional Hue day give first-time travelers the strongest mix without turning the trip into airport management.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><strong>Best default:</strong> Hanoi - Ninh Binh - Lan Ha or Ha Long - Da Nang/Hoi An - optional Hue.</li>
<li><strong>Choose the full north-to-south route only if:</strong> your international flights are open-jaw and you accept faster pacing.</li>
<li><strong>Skip with confidence:</strong> Sapa, Ha Giang, Phong Nha, Mekong, and Phu Quoc do not all belong in one 10-day first trip.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-itinerary-editorial-proof","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-itinerary-editorial-proof">
<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->
</div>
<!-- /wp:group -->

<!-- wp:heading {"className":"vg-itinerary-route-first"} -->
<h2 class="wp-block-heading vg-itinerary-route-first">The route-first recommendation</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ten days is enough for a memorable Vietnam first trip, but not enough for every headline stop. The editorial choice is to protect the route spine: two northern anchors, one bay experience, and one central Vietnam base. This gives food, culture, limestone scenery, old-town atmosphere, and coast without forcing every day to solve a transfer problem.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>If your heart is set on Ho Chi Minh City, Mekong, or Phu Quoc, build a south-first itinerary instead. Do not simply bolt the south onto the North + Central route unless you remove something else.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Choose the right 10-day route</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-route-matrix">
<thead>
<tr><th>Route option</th><th>Best for</th><th>What it includes</th><th>What it leaves out</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Route option">North + Central default</td><td data-label="Best for">First-time visitors who want the strongest mix with fewer weak transfer days.</td><td data-label="What it includes">Hanoi, Ninh Binh, Lan Ha or Ha Long, Da Nang/Hoi An, and optional Hue.</td><td data-label="What it leaves out">Ho Chi Minh City, Mekong, Phu Quoc, Sapa, Ha Giang, and Phong Nha.</td><td data-label="VietnamGuide verdict">Best overall 10-day recommendation for most travelers.</td></tr>
<tr><td data-label="Route option">Classic north-to-south sprint</td><td data-label="Best for">Travelers with open-jaw flights, high transfer tolerance, and a strong reason to see both Hanoi and Ho Chi Minh City.</td><td data-label="What it includes">Hanoi, one northern landscape or bay stop, Hoi An, Ho Chi Minh City, and maybe a Mekong day.</td><td data-label="What it leaves out">Real downtime, deeper central Vietnam, most mountain areas, and flexible weather recovery.</td><td data-label="VietnamGuide verdict">Valid, but not the premium default. Use it only after cutting one major stop.</td></tr>
<tr><td data-label="Route option">Northern landscape route</td><td data-label="Best for">Cooler-season travelers, photographers, and people who prefer scenery over beaches.</td><td data-label="What it includes">Hanoi, Ninh Binh, Lan Ha or Ha Long, Pu Luong or Mai Chau, or a careful Ha Giang module.</td><td data-label="What it leaves out">Hoi An, Hue, Da Nang, Ho Chi Minh City, Mekong, and beaches.</td><td data-label="VietnamGuide verdict">Excellent if you accept mountain-road logistics and skip the countrywide checklist.</td></tr>
<tr><td data-label="Route option">South + island route</td><td data-label="Best for">Winter sun, families who want fewer moves, or travelers arriving and leaving through Ho Chi Minh City.</td><td data-label="What it includes">Ho Chi Minh City, Mekong, Phu Quoc or Con Dao, and one slower city or river day.</td><td data-label="What it leaves out">Hanoi, Ninh Binh, Ha Long/Lan Ha, Hoi An, and Hue.</td><td data-label="VietnamGuide verdict">Strong seasonal alternative when beach time matters more than the classic first-trip spread.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Day-by-day plan for the default route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This is a route spine, not a fixed sales itinerary. Use it to protect pacing, then adjust flights, hotel nights, and day trips around your actual arrival time.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-timeline vg-itinerary-day-plan">
<div class="vg-timeline-item"><div class="vg-day">Day 1</div><div><h3>Arrive in Hanoi</h3><p>Stay central, eat close to the hotel, and keep the evening unambitious. The first decision is recovery, not sightseeing volume.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 2</div><div><h3>Hanoi food, history, and old-quarter rhythm</h3><p>Choose one guided food walk or one deeper cultural visit. Leave space for coffee, lake time, and the city pace that makes Hanoi worth the first two nights.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 3</div><div><h3>Ninh Binh</h3><p>Move early and sleep in or near Ninh Binh if you want a calmer landscape day. A day trip works, but an overnight usually feels more premium than a rushed return.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 4</div><div><h3>Lan Ha or Ha Long overnight</h3><p>Choose one bay, not both. The cruise is the high-friction planning item, so check pickup point, cabin type, weather policy, and return timing before paying.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 5</div><div><h3>Return from the bay and move to Central Vietnam</h3><p>Make this a logistics day. Fly to Da Nang when the timing is clean, then sleep in Hoi An or Da Nang instead of adding a second activity.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 6</div><div><h3>Hoi An at walking pace</h3><p>Use the morning for the old town, food, tailoring, craft, or a countryside ride. Avoid stacking every nearby attraction into one day.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 7</div><div><h3>Hoi An, beach, or My Son</h3><p>Pick one emphasis: old-town depth, beach recovery, a food/craft guide, or My Son. The best version of this day has one clear purpose.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 8</div><div><h3>Hue or a central-coast slow day</h3><p>Choose Hue if imperial history matters and the weather is cooperative. Stay put in Hoi An or Da Nang if the trip needs rest more than another transfer.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 9</div><div><h3>Buffer and departure positioning</h3><p>Use this day to absorb weather, cruise changes, a late domestic flight, or a separate-ticket international connection. Premium planning means protecting the final night.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 10</div><div><h3>Depart</h3><p>Leave from Da Nang, Hanoi, or Ho Chi Minh City only after confirming the real connection. If the long-haul flight is separate, position the night before.</p></div></div>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Alternatives by traveler type</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-alternatives">
<thead>
<tr><th>Traveler priority</th><th>Best adjustment</th><th>What to remove</th><th>Why it works</th></tr>
</thead>
<tbody>
<tr><td data-label="Traveler priority">Food and cities</td><td data-label="Best adjustment">Keep Hanoi and add Ho Chi Minh City, but reduce northern landscapes to one choice.</td><td data-label="What to remove">Either Ninh Binh or the bay cruise.</td><td data-label="Why it works">You gain city contrast without pretending every landscape stop still fits.</td></tr>
<tr><td data-label="Traveler priority">Families</td><td data-label="Best adjustment">Use fewer bases: Hanoi, one landscape stop, and Hoi An/Da Nang.</td><td data-label="What to remove">Hue or any late-night flight day.</td><td data-label="Why it works">Room changes, luggage, and tired evenings cost more than the map suggests.</td></tr>
<tr><td data-label="Traveler priority">Premium/private travel</td><td data-label="Best adjustment">Spend on smarter transfers, better cruise screening, and one excellent guide.</td><td data-label="What to remove">One-night novelty stops.</td><td data-label="Why it works">The premium upgrade is time protection, not more pins.</td></tr>
<tr><td data-label="Traveler priority">Beach-first trip</td><td data-label="Best adjustment">Move south or central depending on season and flight routing.</td><td data-label="What to remove">Northern mountain add-ons.</td><td data-label="Why it works">Beach weather and flight practicality should drive the route.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Swap and skip decisions</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The fastest way to improve a 10-day Vietnam itinerary is to make clean exclusions. A strong skip is not a failure; it is what lets the rest of the trip breathe.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-swap-skip">
<thead>
<tr><th>Decision</th><th>Keep</th><th>Swap or skip</th><th>Editorial reason</th></tr>
</thead>
<tbody>
<tr><td data-label="Decision">Bay experience</td><td data-label="Keep">One overnight in Lan Ha or Ha Long.</td><td data-label="Swap or skip">Do not add both bays; skip the cruise if weather, budget, or motion sensitivity makes it fragile.</td><td data-label="Editorial reason">The bay is memorable, but it is also the planning item most affected by weather and pickup logistics.</td></tr>
<tr><td data-label="Decision">Northern landscapes</td><td data-label="Keep">Ninh Binh for lower transfer effort and high scenic value.</td><td data-label="Swap or skip">Swap for Pu Luong, Mai Chau, Sapa, or Ha Giang only if you remove central Vietnam.</td><td data-label="Editorial reason">Mountain routes deserve more nights than a checklist itinerary gives them.</td></tr>
<tr><td data-label="Decision">Central Vietnam</td><td data-label="Keep">Hoi An or Da Nang as the central base.</td><td data-label="Swap or skip">Skip Hue if the route is already tight, or make Hue the main history day instead of adding another excursion.</td><td data-label="Editorial reason">Central Vietnam is better when it feels settled, not when it becomes three one-night stops.</td></tr>
<tr><td data-label="Decision">Southern Vietnam</td><td data-label="Keep">Ho Chi Minh City only when flights make the north-to-south route efficient.</td><td data-label="Swap or skip">Skip Mekong or Phu Quoc on the default route; make them the anchor of a south-first alternative.</td><td data-label="Editorial reason">The south is not an afterthought. It needs its own route logic.</td></tr>
<tr><td data-label="Decision">Extra domestic flight</td><td data-label="Keep">One clean domestic hop when it replaces a poor overland transfer.</td><td data-label="Swap or skip">Skip flights that create airport days without adding a better destination outcome.</td><td data-label="Editorial reason">A cheap flight can still be expensive in lost time and fragility.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Seasonal adjustments</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam weather is regional. Do not choose a 10-day route from one national weather sentence; choose the route, then check each region close to booking.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-seasonal-adjustments">
<thead>
<tr><th>Seasonal window</th><th>Route adjustment</th><th>What to verify</th></tr>
</thead>
<tbody>
<tr><td data-label="Seasonal window">December-February</td><td data-label="Route adjustment">Expect cooler or mistier northern days. Keep the bay plan flexible and consider the south if warmth is the main goal.</td><td data-label="What to verify">Bay visibility, northern cold spells, beach expectations, and late-arrival transfers.</td></tr>
<tr><td data-label="Seasonal window">March-April</td><td data-label="Route adjustment">Best default window for many North + Central first trips. Book core hotels and cruises earlier.</td><td data-label="What to verify">Holiday demand, cruise availability, central-coast hotel terms, and domestic flight timing.</td></tr>
<tr><td data-label="Seasonal window">May-August</td><td data-label="Route adjustment">Build in heat management. Use mornings well, reduce midday touring, and keep the route less ambitious.</td><td data-label="What to verify">Heat, afternoon rain patterns, family pacing, and cancellation terms for weather-sensitive activities.</td></tr>
<tr><td data-label="Seasonal window">September-November</td><td data-label="Route adjustment">Northern Vietnam can be appealing, but central-coast rain and storm risk need extra attention. Consider a northern or south-first route if Hoi An is fragile.</td><td data-label="What to verify">Central Vietnam forecasts, storm warnings, cruise disruption risk, and backup hotel nights.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Before-booking checks</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Run these checks before paying for anything non-refundable. They are intentionally practical because the best itinerary on paper can fail at the handoff between flights, hotels, weather, and transfer windows.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-itinerary-booking-checks"} -->
<ul class="wp-block-list vg-check-list vg-itinerary-booking-checks">
<li>Confirm international arrival and departure cities before choosing the final route order.</li>
<li>Check passport, visa or entry requirements, and airline document rules using official sources.</li>
<li>Verify regional weather and warnings for Hanoi, the bay, Ninh Binh, and Central Vietnam separately.</li>
<li>Read cruise pickup, weather, cancellation, cabin, and transfer rules before booking the bay night.</li>
<li>Compare domestic flight timing door to door, including luggage, airport transfer, and late-arrival risk.</li>
<li>Use refundable hotel rates around the most weather-sensitive or connection-sensitive nights.</li>
<li>Keep at least one buffer decision: a slower day, a flexible hotel night, or an earlier positioning flight.</li>
<li>If using separate tickets, sleep in the departure city the night before the long-haul flight.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">What this itinerary does not try to do</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This guide does not pretend 10 days can responsibly include every famous Vietnam stop. It also does not lock exact hotel, tour, rail, or flight prices into evergreen advice. Use the structure to decide the route, then live-check the practical parts before final payment.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where to go next</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Return to the <a href="/itineraries/">Itineraries hub</a> when choosing between route families, then use the <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam guide</a> before locking weather-sensitive stops.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"vg-itinerary-source-trail","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-itinerary-source-trail">
<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-itinerary-update-log","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-itinerary-update-log">
<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
</div>
<!-- /wp:group -->
HTML;

$required_content_markers = [
    'editorial proof panel' => 'vg-itinerary-editorial-proof',
    'editorial proof shortcode pattern' => 'vietnamguide/editorial-proof-panel',
    'source trail' => 'vg-itinerary-source-trail',
    'source trail shortcode pattern' => 'vietnamguide/source-trail',
    'update log' => 'vg-itinerary-update-log',
    'update log shortcode pattern' => 'vietnamguide/update-log',
    'concierge verdict' => 'vg-itinerary-concierge-verdict',
    'route-first recommendation' => 'vg-itinerary-route-first',
    'route matrix' => 'vg-itinerary-route-matrix',
    'day-by-day plan' => 'vg-itinerary-day-plan',
    'alternative routes' => 'vg-itinerary-alternatives',
    'swap/skip decisions' => 'vg-itinerary-swap-skip',
    'seasonal adjustments' => 'vg-itinerary-seasonal-adjustments',
    'before-booking checks' => 'vg-itinerary-booking-checks',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);

$result = wp_update_post(
    [
        'ID'             => $page->ID,
        'post_title'     => '10 Days in Vietnam',
        'post_name'      => '10-days-in-vietnam',
        'post_parent'    => (int) $parent->ID,
        'post_status'    => 'publish',
        'post_content'   => $content,
        'post_excerpt'   => 'Premium route-first 10-day Vietnam itinerary guide with the default North + Central route, alternatives, swap and skip decisions, seasonal adjustments, booking checks, source trail, and update log.',
        'comment_status' => 'closed',
        'ping_status'    => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_ops_fail('Could not publish 10 Days in Vietnam guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish 10 Days in Vietnam guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', '10 Days in Vietnam: Route-First Itinerary Guide');
update_post_meta($page_id, 'rank_math_description', 'Premium 10-day Vietnam itinerary with a route-first recommendation, alternatives, day-by-day plan, swap and skip decisions, seasonal adjustments, booking checks, source trail, and update log.');
update_post_meta($page_id, 'rank_math_focus_keyword', '10 day Vietnam itinerary');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Choose the strongest 10-day Vietnam route by pace, season, arrival city, and transfer tolerance.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 15, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Initial premium itinerary guide publish with route-first recommendation, alternative route matrix, day-by-day plan, swap and skip decisions, seasonal adjustments, booking checks, source trail, and update log.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 15, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 15, 2026\nNational Centre for Hydro-Meteorological Forecasting - English forecast and warning pages - https://nchmf.gov.vn/KttvsiteE/en-US/2/index.html - checked July 15, 2026\nWorld Meteorological Organization WWIS - Viet Nam official city forecasts - https://worldweather.wmo.int/en/country.html?countryCode=82 - checked July 15, 2026\nVietnam Railways - official VNR site and online ticketing entry point - https://vr.com.vn/en - checked July 15, 2026\nVietnam Immigration Department - national e-visa portal - https://evisa.gov.vn/ - checked July 15, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'A 10-day Vietnam route should be designed around pace and transfer risk first; live prices and conditions still need date-specific checks before payment.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Route-first recommendation that favors North + Central Vietnam over a thin countrywide checklist.\nRoute matrix that separates default, north-to-south, northern landscape, and south + island alternatives.\nDay-by-day route spine with explicit buffer and departure-positioning guidance.\nSwap and skip decisions that explain what to leave out and why.\nSeasonal adjustment table and before-booking checks grounded in official weather, transport, rail, and entry-source review.\nVisible source trail and update log.");
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
    '_generate-disable-headline',
];

foreach ($required_meta as $meta_key) {
    $meta_value = get_post_meta($page_id, $meta_key, true);

    if ((is_string($meta_value) && trim($meta_value) === '') || $meta_value === [] || $meta_value === null) {
        vg_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_ops_fail('10 Days in Vietnam guide was updated but is not published.');
}

vg_ops_refresh_itineraries_hub();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published 10 Days in Vietnam guide: {$page_id} {$updated_permalink}");

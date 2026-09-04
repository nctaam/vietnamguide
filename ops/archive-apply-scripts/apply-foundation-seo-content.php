<?php
/**
 * Apply launch-safe foundation content, metadata, and low-risk SEO defaults.
 *
 * Run from the WordPress root with:
 * wp eval-file /tmp/apply-foundation-seo-content.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_ops_warn(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::warning($message);
        return;
    }

    echo 'Warning: ' . $message . PHP_EOL;
}

function vg_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_ops_update_page(string $path, array $args): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post) {
        vg_ops_fail("Required page not found: {$path}");
    }

    $result = wp_update_post(
        [
            'ID'             => $page->ID,
            'post_content'   => $args['content'],
            'post_excerpt'   => $args['excerpt'] ?? '',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        ],
        true
    );

    if (is_wp_error($result)) {
        vg_ops_fail("Could not update {$path}: " . $result->get_error_message());
    }

    foreach (($args['meta'] ?? []) as $key => $value) {
        $normalized_key = strpos($key, '_rank_math_') === 0 ? substr($key, 1) : $key;

        update_post_meta($page->ID, $normalized_key, $value);

        if ($normalized_key !== $key) {
            delete_post_meta($page->ID, $key);
        }
    }

    vg_ops_log("Updated page: {$path} ({$page->ID})");
}

$pages = [
    'plan' => [
        'excerpt' => 'A practical Vietnam travel planning hub for international visitors, covering entry checks, route design, costs, transport, SIM choices, safety, and timing.',
        'meta'    => [
            'rank_math_title'         => 'Vietnam Travel Planning Guide for International Visitors',
            'rank_math_description'   => 'Plan a smarter Vietnam trip with practical guidance on entry checks, route design, weather, money, SIM cards, transport, safety, and booking order.',
            'rank_math_focus_keyword' => 'Vietnam travel planning',
        ],
        'content' => <<<'HTML'
<!-- wp:group {"className":"vg-hub-hero"} -->
<div class="wp-block-group vg-hub-hero">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Plan first, book second</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-hub-lede"} -->
<p class="vg-hub-lede">Vietnam is easy to love, but it rewards travelers who make a few decisions in the right order. Use this planning hub to shape your route, check entry requirements, understand costs, choose transport, and avoid building a trip that looks good on a map but feels rushed on the ground.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-hub-section"} -->
<div class="wp-block-group vg-hub-section">
<!-- wp:heading -->
<h2 class="wp-block-heading">The decisions that change your trip</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with constraints before inspiration. The right itinerary depends on your arrival airport, visa or entry situation, weather tolerance, travel pace, and how many intercity moves you want to make.</p>
<!-- /wp:paragraph -->
<!-- wp:group {"className":"vg-hub-grid"} -->
<div class="wp-block-group vg-hub-grid">
<!-- wp:group {"className":"vg-hub-card"} -->
<div class="wp-block-group vg-hub-card"><h3>Entry and timing</h3><p>Confirm current entry rules, passport validity, airline requirements, and public holidays before committing to flights or hotels.</p></div>
<!-- /wp:group -->
<!-- wp:group {"className":"vg-hub-card"} -->
<div class="wp-block-group vg-hub-card"><h3>Route shape</h3><p>Choose north to south, south to north, or a focused regional route. Fewer bases often means a better first trip.</p><p><a href="/itineraries/">Compare itinerary styles</a></p></div>
<!-- /wp:group -->
<!-- wp:group {"className":"vg-hub-card"} -->
<div class="wp-block-group vg-hub-card"><h3>Budget and comfort</h3><p>Separate daily spend from big-ticket decisions such as domestic flights, cruises, private transfers, and boutique hotels.</p><p><a href="/costs/">Open the cost hub</a></p></div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-hub-section"} -->
<div class="wp-block-group vg-hub-section">
<!-- wp:heading -->
<h2 class="wp-block-heading">A better planning order</h2>
<!-- /wp:heading -->
<!-- wp:list {"ordered":true,"className":"vg-check-list"} -->
<ol class="wp-block-list vg-check-list">
<li>Pick your arrival and departure cities before choosing every stop.</li>
<li>Decide whether the trip is about culture, food, landscape, beaches, family ease, or premium downtime.</li>
<li>Choose a route length that leaves buffer days for weather, transfers, and rest.</li>
<li>Price the major movement days first: flights, trains, cars, airport transfers, and tours.</li>
<li>Check official sources again before final payment for entry rules, closures, and transport changes.</li>
</ol>
<!-- /wp:list -->
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Editorial rule</h3><p>VietnamGuide keeps regulation-sensitive topics, such as visa and entry details, in draft until they can be reviewed against official sources. Hub pages stay evergreen and link only to public pages that are ready for readers.</p></div>
<!-- /wp:group -->

<!-- vg-hanoi-ninh-binh-transport-plan-hub-note:v1 -->
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Make the Hanoi to Ninh Binh transfer a route decision</h3><p><a href="/plan/hanoi-to-ninh-binh-transport/">Hanoi to Ninh Binh Transport</a> helps travelers choose train, limousine van, private car, day tour, or onward transfer by drop-off, luggage, hotel base, timing, and next-route fragility.</p></div>
<!-- /wp:group -->

<!-- vg-ninh-binh-ha-long-transfer-plan-hub-note:v1 -->
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Protect the Ninh Binh to bay handoff</h3><p><a href="/plan/ninh-binh-to-ha-long-bay-transfer/">Ninh Binh to Ha Long Bay Transfer</a> helps travelers choose private car, cruise-arranged transfer, shared van, overnight buffer, or route skip by exact port, pickup window, luggage, weather, and cruise check-in risk.</p></div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-next-action"} -->
<div class="wp-block-group vg-next-action"><div><h2 class="wp-block-heading">Next: choose your route.</h2><p>A strong Vietnam plan starts with realistic movement, not a long list of places.</p></div><p><a href="/itineraries/">View itineraries</a></p></div>
<!-- /wp:group -->
HTML
    ],
    'destinations' => [
        'excerpt' => 'A decision-first Vietnam destinations hub for choosing where to go by region, travel style, season, and route efficiency.',
        'meta'    => [
            'rank_math_title'         => 'Vietnam Destinations Guide for International Travelers',
            'rank_math_description'   => 'Choose where to go in Vietnam with practical destination guidance by region, travel style, weather, route efficiency, and first-trip priorities.',
            'rank_math_focus_keyword' => 'Vietnam destinations',
        ],
        'content' => <<<'HTML'
<!-- wp:group {"className":"vg-hub-hero"} -->
<div class="wp-block-group vg-hub-hero"><p class="vg-kicker">Choose places with intent</p><p class="vg-hub-lede">Vietnam is not one destination. It is a long country with very different climates, city rhythms, coastlines, mountain roads, food cultures, and transfer times. This hub helps you choose places that fit the trip you actually want, not just the places everyone lists.</p></div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-hub-section"} -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">Think by region first</h2>
<p>For international travelers, the simplest way to avoid overplanning is to choose a regional emphasis before choosing hotels and tours.</p>
<div class="wp-block-group vg-hub-grid">
<div class="wp-block-group vg-hub-card"><h3>Northern Vietnam</h3><p>Best for Hanoi culture, limestone landscapes, mountain scenery, cooler seasons, and routes that reward slower movement.</p></div>
<div class="wp-block-group vg-hub-card"><h3>Central Vietnam</h3><p>Best for heritage towns, food, beaches, resort stays, and a balanced first-trip pace with fewer long transfers.</p></div>
<div class="wp-block-group vg-hub-card"><h3>Southern Vietnam</h3><p>Best for Ho Chi Minh City energy, river life, food depth, beach extensions, and easier regional flight connections.</p></div>
</div>
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-hub-section"} -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">How to make the destination decision</h2>
<ul class="wp-block-list vg-feature-list">
<li>Choose no more than one major transfer every two or three days unless you enjoy a fast pace.</li>
<li>Pair a city base with a landscape or coast base so the trip has contrast.</li>
<li>Check weather by region, not by the country as a whole.</li>
<li>Use comparisons when two places solve the same job in your itinerary.</li>
<li>Leave one flexible day before important international flights or cruises.</li>
</ul>
<div class="wp-block-group vg-hub-note"><h3>Good destination pages answer trade-offs</h3><p>The best travel planning content does not only say what is beautiful. It explains who should go, who should skip it, what the transfer costs in time, and what to pair it with.</p></div>
</div>
<!-- /wp:group -->

<!-- vg-ninh-binh-day-trip-overnight-destinations-hub-note:v1 -->
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Turn the Ninh Binh stop into a pacing decision</h3><p>Use <a href="/compare/ninh-binh-day-trip-vs-overnight/">Ninh Binh Day Trip vs Overnight</a> after the <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a> when the booking question is no longer what to see, but whether the north needs one long day, one protected night, two slower nights, or a clean skip.</p></div>
<!-- /wp:group -->

<!-- vg-trang-an-tam-coc-destinations-hub-note:v1 -->
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Make the Ninh Binh boat choice before adding more stops</h3><p>Use <a href="/compare/trang-an-vs-tam-coc/">Trang An vs Tam Coc</a> after the <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a> when the question is no longer whether Ninh Binh belongs, but which water route actually improves the day.</p></div>
<!-- /wp:group -->

<!-- vg-tam-coc-destinations-hub-note:v1 -->
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use Tam Coc as a real base, not a postcard stop</h3><p><a href="/destinations/tam-coc-travel-guide/">Tam Coc Travel Guide</a> helps travelers decide whether Tam Coc should be the soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before another northern landscape stop gets added.</p></div>
<!-- /wp:group -->

<!-- vg-hanoi-ninh-binh-transport-destinations-hub-note:v1 -->
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Connect Hanoi and Ninh Binh without wasting the best hours</h3><p>After choosing the <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a> or <a href="/compare/ninh-binh-day-trip-vs-overnight/">Ninh Binh Day Trip vs Overnight</a>, use <a href="/plan/hanoi-to-ninh-binh-transport/">Hanoi to Ninh Binh Transport</a> to decide whether rail, van, private car, tour transport, or onward transfer actually fits the base.</p></div>
<!-- /wp:group -->

<!-- vg-ninh-binh-stays-destinations-hub-note:v1 -->
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose the Ninh Binh base by route job</h3><p>Use <a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a> after the day-trip versus overnight decision when Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays would change pickup timing, sleep, dinner choice, luggage, and the next handoff.</p></div>
<!-- /wp:group -->

<!-- vg-ninh-binh-ha-long-transfer-destinations-hub-note:v1 -->
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Do not let the bay cruise break the Ninh Binh stop</h3><p>After choosing <a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a> and the <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, use <a href="/plan/ninh-binh-to-ha-long-bay-transfer/">Ninh Binh to Ha Long Bay Transfer</a> to confirm the actual port, pickup window, and buffer before the route buys the wrong car or cruise handoff.</p></div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-next-action"} -->
<div class="wp-block-group vg-next-action"><div><h2 class="wp-block-heading">Next: compare similar choices.</h2><p>Use clear trade-offs before you add another stop to the route.</p></div><p><a href="/compare/">Open comparisons</a></p></div>
<!-- /wp:group -->
HTML
    ],
    'itineraries' => [
        'excerpt' => 'Vietnam itinerary guidance for 7-day, 10-day, 14-day, and slower routes, focused on realistic pacing for international travelers.',
        'meta'    => [
            'rank_math_title'         => 'Vietnam Itineraries: 7, 10, 14 Day and Slower Routes',
            'rank_math_description'   => 'Build a realistic Vietnam itinerary with route guidance for 7-day, 10-day, 14-day, and slower trips, including pace, transfer, and destination trade-offs.',
            'rank_math_focus_keyword' => 'Vietnam itinerary',
        ],
        'content' => <<<'HTML'
<!-- wp:group {"className":"vg-hub-hero"} -->
<div class="wp-block-group vg-hub-hero"><p class="vg-kicker">Route by pace</p><p class="vg-hub-lede">A good Vietnam itinerary is not the one with the most pins. It is the one where each transfer earns its place. Use this hub to choose a route length, understand what to leave out, and build a trip that still feels like travel rather than logistics.</p></div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-hub-section"} -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">Choose the itinerary family</h2>
<div class="wp-block-group vg-hub-grid">
<div class="wp-block-group vg-hub-card"><h3>7 days</h3><p>Best as a focused regional trip. Pick one main region and avoid crossing the whole country.</p></div>
<div class="wp-block-group vg-hub-card"><h3>10 days</h3><p>Enough for a classic first route if you accept a few efficient transfers and avoid too many one-night stays.</p></div>
<div class="wp-block-group vg-hub-card"><h3>14 days or more</h3><p>Best for adding contrast, slower meals, weather buffer, and a premium cruise, beach, or mountain extension.</p></div>
</div>
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-hub-section"} -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">Build the route in this order</h2>
<ol class="wp-block-list vg-check-list">
<li>Start with international flight cities and any fixed-date experiences.</li>
<li>Choose two or three anchor bases before adding day trips.</li>
<li>Map transfer time door to door, not just flight or train time.</li>
<li>Keep one slower day after long-haul arrival and before departure.</li>
<li>Price the route before polishing hotel choices.</li>
</ol>
<div class="wp-block-group vg-hub-note"><h3>Premium does not always mean more stops</h3><p>For Vietnam, premium often means better timing, fewer wasted transfer hours, stronger guides, and enough margin to enjoy the places you chose.</p></div>
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-next-action"} -->
<div class="wp-block-group vg-next-action"><div><h2 class="wp-block-heading">Next: estimate the trip cost.</h2><p>Route, comfort level, and transfer choices shape the budget more than daily spending alone.</p></div><p><a href="/costs/">Open cost planning</a></p></div>
<!-- /wp:group -->
HTML
    ],
    'compare' => [
        'excerpt' => 'Vietnam destination and itinerary comparisons for travelers choosing between similar places, routes, bays, beaches, cities, and trip styles.',
        'meta'    => [
            'rank_math_title'         => 'Compare Vietnam Destinations, Routes and Trip Styles',
            'rank_math_description'   => 'Compare Vietnam destinations and route choices with clear trade-offs for timing, scenery, comfort, transfer effort, and first-trip value.',
            'rank_math_focus_keyword' => 'compare Vietnam destinations',
        ],
        'content' => <<<'HTML'
<!-- wp:group {"className":"vg-hub-hero"} -->
<div class="wp-block-group vg-hub-hero"><p class="vg-kicker">Decide with trade-offs</p><p class="vg-hub-lede">Many Vietnam planning mistakes come from treating similar places as interchangeable or trying to include both. This hub is for the moments when you need to choose one bay, one beach, one city base, one region, or one travel style.</p></div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-hub-section"} -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">Comparison topics that matter</h2>
<div class="wp-block-group vg-hub-grid vg-hub-grid-two">
<div class="wp-block-group vg-hub-card"><h3>Place vs place</h3><p>Use when two destinations offer similar scenery or trip value and you need to choose based on logistics, crowd tolerance, or season.</p></div>
<div class="wp-block-group vg-hub-card"><h3>Route vs route</h3><p>Use when the question is not whether a place is good, but whether it belongs in this version of your itinerary.</p></div>
<div class="wp-block-group vg-hub-card"><h3>Comfort vs cost</h3><p>Use when deciding between private transfers, domestic flights, cruises, guides, or a more flexible hotel plan.</p></div>
<div class="wp-block-group vg-hub-card"><h3>First trip vs return trip</h3><p>Some places are excellent but better after you understand Vietnam's pace, distances, and climate patterns.</p></div>
</div>
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-hub-section"} -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">The VietnamGuide comparison framework</h2>
<ul class="wp-block-list vg-feature-list">
<li>Who should choose option A, and who should choose option B?</li>
<li>How much transfer effort does each choice add?</li>
<li>Which option is more weather-sensitive?</li>
<li>Which option is better for a first trip, families, couples, or premium travelers?</li>
<li>What would you lose by skipping it?</li>
</ul>
</div>
<!-- /wp:group -->

<!-- vg-ninh-binh-day-trip-overnight-compare-hub-note:v1 -->
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Decide if Ninh Binh needs a night</h3><p><a href="/compare/ninh-binh-day-trip-vs-overnight/">Ninh Binh Day Trip vs Overnight</a> compares the Hanoi day trip, one-night countryside stop, two-night slow base, and skip decision by transfer pressure, morning control, Trang An versus Tam Coc, cost, weather, and next-route fragility.</p></div>
<!-- /wp:group -->

<!-- vg-trang-an-tam-coc-compare-hub-note:v1 -->
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose the right Ninh Binh boat trip</h3><p><a href="/compare/trang-an-vs-tam-coc/">Trang An vs Tam Coc</a> separates the high-confidence UNESCO boat route from the softer Tam Coc base rhythm by scenery, crowd timing, boat length, family comfort, photography, hotel logic, and day-trip versus overnight value.</p></div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-next-action"} -->
<div class="wp-block-group vg-next-action"><div><h2 class="wp-block-heading">Next: choose the places.</h2><p>Use comparisons to make the destination list shorter and stronger.</p></div><p><a href="/destinations/">Open destinations</a></p></div>
<!-- /wp:group -->
HTML
    ],
    'costs' => [
        'excerpt' => 'A Vietnam travel cost planning hub for international visitors, focused on budget drivers, comfort choices, transfer costs, and trip planning method.',
        'meta'    => [
            'rank_math_title'         => 'Vietnam Travel Cost Planning for International Visitors',
            'rank_math_description'   => 'Plan Vietnam travel costs with a practical budget framework for hotels, transport, food, tours, transfers, buffer days, and comfort choices.',
            'rank_math_focus_keyword' => 'Vietnam travel cost',
        ],
        'content' => <<<'HTML'
<!-- wp:group {"className":"vg-hub-hero"} -->
<div class="wp-block-group vg-hub-hero"><p class="vg-kicker">Budget the decisions</p><p class="vg-hub-lede">Vietnam can work for many budgets, but the final cost depends less on a generic daily number and more on route design, comfort level, transport choices, season, and how much private support you want. This hub gives you a clean way to estimate before booking.</p></div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-hub-section"} -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">The biggest cost drivers</h2>
<div class="wp-block-group vg-hub-grid">
<div class="wp-block-group vg-hub-card"><h3>Hotels and location</h3><p>Central hotels, heritage properties, beach resorts, and premium rooms can change the trip total more than meals or local taxis.</p></div>
<div class="wp-block-group vg-hub-card"><h3>Intercity movement</h3><p>Domestic flights, trains, private cars, luggage handling, and airport transfers should be estimated before daily spending.</p></div>
<div class="wp-block-group vg-hub-card"><h3>Tours and guided days</h3><p>Private guides, cruises, food tours, and countryside days can be worth the spend when they solve logistics or unlock better context.</p></div>
</div>
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-hub-section"} -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">A practical budget method</h2>
<ol class="wp-block-list vg-check-list">
<li>Price hotels by city and season before setting a total daily target.</li>
<li>Add every intercity transfer as a separate line item.</li>
<li>Decide which experiences deserve private support and which can stay simple.</li>
<li>Keep a buffer for weather changes, late flights, better rooms, and rest days.</li>
<li>Review the route again if the cost feels high. Too many moves often create hidden expense.</li>
</ol>
<div class="wp-block-group vg-hub-note"><h3>Why exact numbers wait</h3><p>Public price ranges should be dated and reviewed often because exchange rates, hotel demand, and transport pricing move. Until the dedicated cost guide is ready, this hub focuses on the budget structure that stays useful.</p></div>
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-next-action"} -->
<div class="wp-block-group vg-next-action"><div><h2 class="wp-block-heading">Next: refine the itinerary.</h2><p>The cheapest route is not always the best route, but the best route should make its costs visible.</p></div><p><a href="/itineraries/">Return to itineraries</a></p></div>
<!-- /wp:group -->
HTML
    ],
    'newsletter' => [
        'excerpt' => 'VietnamGuide newsletter page for practical first-trip planning notes, route ideas, and editorial updates for international travelers.',
        'meta'    => [
            'rank_math_title'         => 'VietnamGuide Newsletter for Smarter Vietnam Travel Planning',
            'rank_math_description'   => 'Follow VietnamGuide for concise Vietnam travel planning updates, route ideas, practical checklists, and destination decision notes.',
            'rank_math_focus_keyword' => 'Vietnam travel newsletter',
        ],
        'content' => <<<'HTML'
<!-- wp:group {"className":"vg-hub-hero"} -->
<div class="wp-block-group vg-hub-hero"><p class="vg-kicker">Vietnam planning notes</p><p class="vg-hub-lede">VietnamGuide is being built as a practical editorial resource for international travelers. The newsletter will focus on route decisions, planning order, destination trade-offs, cost clarity, and newly reviewed guides.</p></div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-hub-section"} -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">What readers should expect</h2>
<div class="wp-block-group vg-hub-grid">
<div class="wp-block-group vg-hub-card"><h3>First-trip planning</h3><p>Short guidance on routes, timing, transport, and the decisions to make before booking.</p></div>
<div class="wp-block-group vg-hub-card"><h3>Destination choices</h3><p>Clear notes on which places fit which trip style, and what to skip when time is tight.</p></div>
<div class="wp-block-group vg-hub-card"><h3>Editorial updates</h3><p>Newly reviewed guides, source checks, and planning frameworks as the site expands.</p></div>
</div>
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-next-action"} -->
<div class="wp-block-group vg-next-action"><div><h2 class="wp-block-heading">Start with the planning hub.</h2><p>The email capture will be connected after the provider and consent copy are finalized.</p></div><p><a href="/plan/">Open planning guides</a></p></div>
<!-- /wp:group -->
HTML
    ],
    'affiliate-disclosure' => [
        'excerpt' => 'VietnamGuide.net affiliate disclosure explaining editorial independence, possible commissions, and reader-first recommendation standards.',
        'meta'    => [
            'rank_math_title'         => 'Affiliate Disclosure',
            'rank_math_description'   => 'VietnamGuide.net affiliate disclosure: some links may earn commissions at no extra cost to readers, while recommendations remain editorially selected.',
            'rank_math_focus_keyword' => 'affiliate disclosure',
        ],
        'content' => <<<'HTML'
<!-- wp:group {"className":"vg-hub-hero"} -->
<div class="wp-block-group vg-hub-hero"><p class="vg-kicker">Reader-first disclosure</p><p class="vg-hub-lede">VietnamGuide.net may earn a commission when readers book or buy through some links, at no extra cost to them. Editorial usefulness comes first: a recommendation should still make sense even when there is no affiliate relationship.</p></div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-hub-section"} -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">How affiliate links are handled</h2>
<ul class="wp-block-list vg-feature-list">
<li>Affiliate relationships may include hotels, tours, transport, travel products, insurance, or booking platforms.</li>
<li>Sponsored or affiliate links should be labeled in the code with appropriate search engine attributes.</li>
<li>VietnamGuide aims to separate editorial judgment from commission potential.</li>
<li>Readers should compare current prices, cancellation rules, and official terms before booking.</li>
</ul>
<div class="wp-block-group vg-hub-note"><h3>Editorial standard</h3><p>The goal is to help travelers make better Vietnam decisions. If a product, route, or service is not a good fit for the reader, it should not be recommended only because it can generate revenue.</p></div>
</div>
<!-- /wp:group -->
HTML
    ],
];

foreach ($pages as $path => $args) {
    vg_ops_update_page($path, $args);
}

$home = get_page_by_path('home', OBJECT, 'page');
if ($home instanceof WP_Post) {
    update_post_meta($home->ID, 'rank_math_title', 'Vietnam Travel Guide for International Visitors');
    update_post_meta($home->ID, 'rank_math_description', 'Plan a smarter Vietnam trip with premium destination, itinerary, cost, comparison, and practical travel guides for international visitors.');
    update_post_meta($home->ID, 'rank_math_focus_keyword', 'Vietnam travel guide');
    delete_post_meta($home->ID, '_rank_math_title');
    delete_post_meta($home->ID, '_rank_math_description');
    delete_post_meta($home->ID, '_rank_math_focus_keyword');
    wp_update_post(
        [
            'ID'             => $home->ID,
            'post_excerpt'   => 'Premium practical Vietnam travel planning for international visitors.',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        ]
    );
    vg_ops_log("Updated homepage metadata: {$home->ID}");
}

$title_options = get_option('rank-math-options-titles', []);
if (! is_array($title_options)) {
    $title_options = [];
}

$title_options = array_merge(
    $title_options,
    [
        'knowledgegraph_type'     => 'company',
        'knowledgegraph_name'     => 'VietnamGuide.net',
        'website_name'            => 'VietnamGuide.net',
        'homepage_title'          => 'Vietnam Travel Guide for International Visitors',
        'homepage_description'    => 'Plan a smarter Vietnam trip with premium destination, itinerary, cost, comparison, and practical travel guides for international visitors.',
        'disable_author_archives' => 'on',
        'noindex_search'          => 'on',
        'tax_post_tag_custom_robots'    => 'on',
        'tax_post_tag_robots'     => ['noindex'],
        'tax_post_format_custom_robots' => 'on',
        'tax_post_format_robots'  => ['noindex'],
        'author_robots'           => ['noindex'],
        'date_archive_robots'     => ['noindex'],
    ]
);
update_option('rank-math-options-titles', $title_options);
vg_ops_log('Updated Rank Math titles/options.');

$general_options = get_option('rank-math-options-general', []);
if (! is_array($general_options)) {
    $general_options = [];
}

$general_options['breadcrumbs'] = 'on';
update_option('rank-math-options-general', $general_options);
vg_ops_log('Enabled Rank Math breadcrumbs setting.');

update_option('blogname', 'VietnamGuide.net');
update_option('blogdescription', 'Premium practical Vietnam travel planning for international visitors.');
update_option('WPLANG', '');
update_option('default_comment_status', 'closed');
update_option('default_ping_status', 'closed');

$all_pages = get_posts(
    [
        'post_type'      => 'page',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]
);

foreach ($all_pages as $page_id) {
    wp_update_post(
        [
            'ID'             => (int) $page_id,
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        ]
    );
}
vg_ops_log('Closed comments and pings on all pages.');

flush_rewrite_rules(false);
vg_ops_log('Flushed rewrite rules.');

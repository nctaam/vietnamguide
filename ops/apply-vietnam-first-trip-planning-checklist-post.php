<?php
/**
 * Expand the Vietnam First Trip Planning Checklist post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-vietnam-first-trip-planning-checklist-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_first_trip_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_first_trip_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
}

if (! vg_first_trip_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_first_trip_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_first_trip_post_find_by_slug(string $slug): ?WP_Post
{
    $posts = get_posts(
        [
            'post_type' => 'post',
            'post_status' => ['draft', 'pending', 'private', 'future', 'publish'],
            'name' => $slug,
            'posts_per_page' => 1,
        ]
    );

    return $posts[0] ?? null;
}

function vg_first_trip_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_first_trip_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'vietnam-first-trip-planning-checklist';
$post = vg_first_trip_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_first_trip_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_first_trip_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;

$ha_long_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg/1920px-Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg';
$trang_an_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg';
$hoi_an_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1280px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg';
$hcmc_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg';

$content = <<<HTML
<!-- vg-first-trip-checklist-hero:v1 -->
<!-- wp:html -->
<section class="vg-guide-hero vg-first-trip-checklist-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">First-trip planning</p>
<p class="vg-guide-lede">A first Vietnam trip gets easier when you decide the hard things before booking: route shape, season risk, entry checks, transfer pressure, first-night location, and what to skip.</p>
</div>
<figure class="vg-guide-hero-image"><img src="{$ha_long_image}" alt="Ha Long Bay limestone islands seen from above in northern Vietnam" loading="eager" decoding="async"><figcaption>Use famous places as route chapters, not checklist trophies. Image: Vyacheslav Argenberg / CC BY 4.0.</figcaption></figure>
</section>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-first-trip-checklist-verdict:v1 -->
<!-- wp:html -->
<aside class="vg-concierge-verdict vg-first-trip-checklist-verdict" aria-label="Vietnam first trip verdict">
<p class="vg-kicker">VietnamGuide verdict</p>
<h2>Choose your route before choosing every place.</h2>
<p>Most first-time Vietnam mistakes come from booking famous stops in the wrong order. Start with arrival and departure airports, decide whether the trip is north-focused, central-focused, south-focused, or open-jaw, then add destinations only when they improve the route rather than simply making the map look fuller.</p>
<ul>
<li><strong>7 days:</strong> choose one region and do it well.</li>
<li><strong>10 days:</strong> use two strong regions by default.</li>
<li><strong>14 days:</strong> add a third chapter only when transfer days still leave rest.</li>
<li><strong>21 days:</strong> build depth, not a race through every famous name.</li>
</ul>
</aside>
<!-- /wp:html -->

<!-- wp:paragraph -->
<p>This checklist is for international travelers planning a first Vietnam trip before flights, hotels, cruises, or tours are locked. It does not try to answer every micro-question. It gives you the sequence that prevents the expensive mistakes: picking too many bases, ignoring regional weather, underestimating domestic transfers, arriving into the wrong city for your first night, or paying for a beautiful tour that makes the route brittle.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Visible external links are intentionally avoided in the article body. Source checks are recorded in the source trail, while the main reading path sends you to VietnamGuide planning pages that already interpret the official information for travelers.</p>
<!-- /wp:paragraph -->

<!-- vg-first-trip-checklist-planning-order:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The 12 decisions to make before booking</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-first-trip-checklist-planning-order">
<thead><tr><th>Order</th><th>Decision</th><th>Why it matters</th><th>Do this before paying</th></tr></thead>
<tbody>
<tr><td data-label="Order">01</td><td data-label="Decision">Entry and passport checks</td><td data-label="Why it matters">Visa, exemption, passport, airline, and arrival rules can change. A cheap flight is not useful if the entry assumption is wrong.</td><td data-label="Do this before paying">Check the official e-visa/immigration source for your nationality, stay length, entry point, and multiple-entry needs.</td></tr>
<tr><td data-label="Order">02</td><td data-label="Decision">Arrival and departure cities</td><td data-label="Why it matters">Open-jaw flights can save more value than squeezing another city into the route.</td><td data-label="Do this before paying">Compare Hanoi-in/Da Nang-out, Hanoi-in/HCMC-out, and single-city return routing.</td></tr>
<tr><td data-label="Order">03</td><td data-label="Decision">True days on the ground</td><td data-label="Why it matters">A 10-day holiday can be only 8 usable days after long-haul arrival and departure timing.</td><td data-label="Do this before paying">Count sleep nights and usable mornings, not calendar dates.</td></tr>
<tr><td data-label="Order">04</td><td data-label="Decision">Region count</td><td data-label="Why it matters">Vietnam is long. Each extra region costs airport, road, train, or recovery time.</td><td data-label="Do this before paying">Choose one, two, or three regions before naming every destination.</td></tr>
<tr><td data-label="Order">05</td><td data-label="Decision">Season and weather risk</td><td data-label="Why it matters">There is no single Vietnam weather answer. North, central, south, mountains, caves, and beaches behave differently.</td><td data-label="Do this before paying">Use the month to choose route bias, then check current forecasts and operator policies later.</td></tr>
<tr><td data-label="Order">06</td><td data-label="Decision">Big-ticket anchors</td><td data-label="Why it matters">Cruises, domestic flights, premium hotels, private transfers, and specialist tours shape the budget more than cheap meals do.</td><td data-label="Do this before paying">List the expensive anchors before estimating daily spend.</td></tr>
<tr><td data-label="Order">07</td><td data-label="Decision">Hard transfer days</td><td data-label="Why it matters">The most fragile day is usually the handoff between regions or from countryside to cruise/airport.</td><td data-label="Do this before paying">Name the hardest transfer and add buffer before booking non-refundable pieces.</td></tr>
<tr><td data-label="Order">08</td><td data-label="Decision">First-night location</td><td data-label="Why it matters">Arrival night sets the tone. A bad first base creates avoidable fatigue and taxi stress.</td><td data-label="Do this before paying">Choose a central, easy-arrival hotel before optimizing for charm or savings.</td></tr>
<tr><td data-label="Order">09</td><td data-label="Decision">Health and insurance checks</td><td data-label="Why it matters">Motorbike exclusions, evacuation cover, pre-existing conditions, and travel clinic advice matter before adventure or remote plans.</td><td data-label="Do this before paying">Read policy exclusions and consult a qualified clinician for personal medical advice.</td></tr>
<tr><td data-label="Order">10</td><td data-label="Decision">Money, SIM, and arrival setup</td><td data-label="Why it matters">Cash, card acceptance, ATM fees, eSIM/SIM setup, and airport transfer choices affect the first hour.</td><td data-label="Do this before paying">Plan the landing sequence: immigration, luggage, cash/SIM if needed, transport, hotel.</td></tr>
<tr><td data-label="Order">11</td><td data-label="Decision">What to skip</td><td data-label="Why it matters">The skipped stop often protects the trip more than the added stop improves it.</td><td data-label="Do this before paying">Write a cut list before the itinerary is emotionally fixed.</td></tr>
<tr><td data-label="Order">12</td><td data-label="Decision">Booking order</td><td data-label="Why it matters">Some choices should wait until the route is stable, while others need early reservation.</td><td data-label="Do this before paying">Book in layers: flights, route anchors, first/last nights, then flexible add-ons.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-first-trip-checklist-route-shape:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Choose the route shape first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>First-time travelers often start with a list: Hanoi, Ha Long Bay, Ninh Binh, Hue, Hoi An, Da Nang, Ho Chi Minh City, Mekong Delta, Phu Quoc, maybe Sapa. The list is not the problem. The problem is pretending each name costs only the day you spend there. Every base change has hidden cost: packing, checkout, pickup timing, weather margin, transfer fatigue, and a new hotel decision.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-first-trip-checklist-route-shape">
<figure class="vg-guide-photo"><img src="{$trang_an_image}" alt="Boat and limestone scenery in Trang An, Ninh Binh, Vietnam" loading="lazy" decoding="async"><figcaption>Ninh Binh is strongest when it has protected timing, not when it is squeezed between Hanoi and a bay cruise. Image: Jakub Halun / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hoi_an_image}" alt="Hoi An Ancient Town street and yellow buildings in central Vietnam" loading="lazy" decoding="async"><figcaption>Central Vietnam rewards slower pacing because Hoi An, Hue, Da Nang, food, coast, and heritage compete for the same days. Image: Steffen Schmitz / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall and Nguyen Hue walking street at night" loading="lazy" decoding="async"><figcaption>Ho Chi Minh City works best as a real southern chapter, not as a late-arrival stamp before departure. Image: Steffen Schmitz / CC BY-SA 4.0.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- wp:html -->
<table class="vg-decision-table vg-first-trip-route-table">
<thead><tr><th>Trip length</th><th>Better default</th><th>What to avoid</th><th>Where to go next</th></tr></thead>
<tbody>
<tr><td data-label="Trip length">7 days</td><td data-label="Better default">One region: north scenery, central food/heritage/coast, or south city/river/beach.</td><td data-label="What to avoid">A full-country route with three airports and no recovery time.</td><td data-label="Where to go next"><a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a></td></tr>
<tr><td data-label="Trip length">10 days</td><td data-label="Better default">Two strong regions, usually north plus central for a balanced first trip.</td><td data-label="What to avoid">Adding the south unless flights or personal priority make it a real chapter.</td><td data-label="Where to go next"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Trip length">14 days</td><td data-label="Better default">Two to three regions with one protected slower block.</td><td data-label="What to avoid">Mistaking two weeks for unlimited movement.</td><td data-label="Where to go next"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Trip length">21 days</td><td data-label="Better default">A full route with depth: north, central, south, and one specialist extension.</td><td data-label="What to avoid">Changing bases so often that the long trip still feels thin.</td><td data-label="Where to go next"><a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Entry checks: keep this boring and official</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Entry rules are not the place to rely on memory, old forum comments, or a travel blog summary. Before booking, check the official e-visa and immigration information for your passport, entry point, trip length, and whether you need single or multiple entry. Then check again before final payment and before departure.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>The practical rule is simple: decide the immigration assumption before you design the route. A traveler who plans to leave Vietnam and return, cross a land border, or stay close to the maximum permitted duration needs a different check than a traveler flying into Hanoi for ten days and leaving from Da Nang.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Weather: do not plan Vietnam as one climate</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam's weather varies by region and season. A good month for central-coast beach time may not be the same as the best month for northern mountain views or southern river travel. Use season as a route-shaping tool, not as a single yes/no answer.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-at-a-glance vg-first-trip-weather-check">
<h2>Weather decision shortcuts</h2>
<ul>
<li><strong>North-heavy route:</strong> check cool-season comfort, mist, mountain road risk, and bay visibility.</li>
<li><strong>Central-heavy route:</strong> check heat, rain, storm exposure, and whether beach time is actually a priority.</li>
<li><strong>South-heavy route:</strong> check heat, wet-season rhythm, river travel, and island weather if adding Phu Quoc or Con Dao.</li>
<li><strong>Mixed route:</strong> build a plan that can survive one weak weather chapter without collapsing.</li>
</ul>
</div>
<!-- /wp:html -->

<!-- vg-first-trip-checklist-booking-order:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Book in layers, not all at once</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The safest booking order is not the same for every traveler, but the logic is consistent. Lock the pieces that define the trip, protect the first and last nights, then keep enough flexibility for weather, energy, and better information.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-first-trip-booking-order">
<thead><tr><th>Booking layer</th><th>Book earlier</th><th>Keep flexible</th><th>Why</th></tr></thead>
<tbody>
<tr><td data-label="Booking layer">International flights</td><td data-label="Book earlier">Open-jaw flights when they save a major backtrack.</td><td data-label="Keep flexible">Exact day-by-day sightseeing.</td><td data-label="Why">Flights determine route shape more than any individual attraction.</td></tr>
<tr><td data-label="Booking layer">First and last nights</td><td data-label="Book earlier">Easy-arrival and departure-safe hotels.</td><td data-label="Keep flexible">Middle nights if the route is still changing.</td><td data-label="Why">Arrival fatigue and departure risk are predictable.</td></tr>
<tr><td data-label="Booking layer">Route anchors</td><td data-label="Book earlier">Cruise, premium hotel, specialist guide, domestic flight, or remote transfer that controls the route.</td><td data-label="Keep flexible">Generic city tours and second-choice add-ons.</td><td data-label="Why">Anchors can sell out or force the sequence.</td></tr>
<tr><td data-label="Booking layer">Daily activities</td><td data-label="Book earlier">Small-group tours with limited capacity or clear cancellation terms.</td><td data-label="Keep flexible">Weather-sensitive walks, beach days, shopping, cafes, and low-stakes museums.</td><td data-label="Why">A first trip needs recovery margin.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-first-trip-checklist-arrival:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Your first hour in Vietnam</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The first hour does not need to be clever. It needs to be calm. Do not turn arrival into a shopping session, a bargaining test, or a city crossing with low battery and no plan.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<ol class="vg-travel-flow-list vg-first-trip-arrival-list" aria-label="First hour in Vietnam checklist">
<li><span>01</span><strong>Immigration and luggage</strong><p>Keep documents easy to access and avoid making the first decision while tired.</p></li>
<li><span>02</span><strong>Connectivity</strong><p>Use eSIM if already active, or buy a SIM only if it is convenient and transparent. It is fine to wait until the hotel if your transfer is arranged.</p></li>
<li><span>03</span><strong>Cash and cards</strong><p>Get a practical amount of cash, not your whole trip budget. Keep cards and backup cash separated.</p></li>
<li><span>04</span><strong>Transport</strong><p>Use an arranged transfer, official airport taxi counter, ride-hailing pickup you understand, or hotel pickup when arrival timing is awkward.</p></li>
<li><span>05</span><strong>First night</strong><p>Check in, eat close by, sleep. Do not schedule a must-do tour on arrival night.</p></li>
</ol>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">The anti-spam rule for your own itinerary</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A good first Vietnam itinerary is not the one with the most famous names. It is the one where every stop has a job. Hanoi can be the arrival, food, and culture chapter. Ninh Binh can be countryside and limestone scenery. A bay cruise can be the water-and-karst chapter. Central Vietnam can be heritage, food, coast, and a gentler finish. Ho Chi Minh City can be southern energy, history, and food depth. Phu Quoc can be a beach finish. But when every place tries to do every job, the route becomes tiring.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Before booking, write one sentence for each stop: "This place is in the route because..." If the sentence is only "because it is famous," keep questioning it.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this checklist fits next</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this checklist before detailed itinerary planning. Once the route shape is clear, move into the main country guide, route length guide, weather guide, and cost guide.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-related-routes vg-first-trip-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a><span class="vg-related-route-note">Start here for the country-level planning frame.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a><span class="vg-related-route-note">Choose region emphasis before choosing every stop.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Check season risk by region.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a><span class="vg-related-route-note">Price the major movement days before estimating daily spend.</span></li>
</ol>
</div>
<!-- /wp:html -->

<!-- vg-first-trip-checklist-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">First Vietnam trip FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-first-trip-checklist-faq">
<h3>How many places should I visit on a first Vietnam trip?</h3>
<p>For 7 days, choose one region. For 10 days, choose two strong regions. For 14 days, two or three regions can work if the transfer days are honest. For 21 days, add depth rather than adding every possible stop.</p>
<h3>Should I fly into Hanoi or Ho Chi Minh City?</h3>
<p>Choose by route shape and flight logic. Hanoi is often better for a north-plus-central first trip. Ho Chi Minh City is better when the south is a real chapter or when flights make a north-to-south route cleaner.</p>
<h3>Is it worth paying more for open-jaw flights?</h3>
<p>Often, yes. If arriving in one city and leaving from another removes a domestic backtrack, the time and stress saved can outweigh a modest fare difference.</p>
<h3>What should I avoid booking too early?</h3>
<p>Avoid locking generic day tours, weather-sensitive activities, and too many middle nights before the route shape is stable. Book anchors early, but keep low-stakes pieces flexible.</p>
<h3>What is the biggest first-trip mistake?</h3>
<p>Adding a destination without naming the transfer cost. The map looks better than the day feels.</p>
</div>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[vg_source_trail]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[vg_update_log]
<!-- /wp:shortcode -->
HTML;

$required_markers = [
    'vg-first-trip-checklist-hero:v1',
    'vg-first-trip-checklist-verdict:v1',
    'vg-first-trip-checklist-planning-order:v1',
    'vg-first-trip-checklist-route-shape:v1',
    'vg-first-trip-checklist-booking-order:v1',
    'vg-first-trip-checklist-arrival:v1',
    'vg-first-trip-checklist-faq:v1',
    '[vg_editorial_proof]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_first_trip_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Vietnam First Trip Planning Checklist: What to Decide Before Booking',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'A decision-first Vietnam planning checklist for international visitors choosing entry checks, route shape, weather risk, booking order, arrival setup, and what to skip before paying.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_first_trip_post_fail('Could not update first-trip checklist post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Vietnam First Trip Planning Checklist');
update_post_meta($post_id, 'rank_math_description', 'Plan a first Vietnam trip before booking with entry checks, route shape, weather risk, cost anchors, arrival setup, and what to skip.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Vietnam first trip planning checklist');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Decide the route, entry, season, transfer, budget, and booking order assumptions before paying for a first Vietnam trip.');
update_post_meta($post_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($post_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($post_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($post_id, 'vg_eeat_last_meaningful_update', 'July 25, 2026');
update_post_meta($post_id, 'vg_eeat_update_summary', 'Expanded the native WordPress post brief into a complete draft checklist with route-shape logic, booking order, arrival sequence, source trail, image proof, FAQ, and internal next steps. The post remains draft for manual WordPress Admin review before publishing.');
update_post_meta($post_id, 'vg_eeat_sources_checked', "Vietnam National Electronic Visa system - official e-visa portal for outside Viet Nam foreigners - https://evisa.gov.vn/e-visa/foreigners - checked July 25, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 25, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 25, 2026\nVietnam.travel - Health and safety - https://vietnam.travel/plan-your-trip/health-safety - checked July 25, 2026\nCDC Travelers Health - Vietnam - https://wwwnc.cdc.gov/travel/destinations/traveler/none/vietnam - checked July 25, 2026\nWikimedia Commons image record - Ha Long Bay, Vietnam, View from above - https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg - license checked July 25, 2026\nWikimedia Commons image record - Trang An Landscape Complex, Ninh Binh Province - https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg - license checked July 25, 2026\nWikimedia Commons image record - Hoi An Ancient Town - https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg - license checked July 25, 2026\nWikimedia Commons image record - Ho Chi Minh City Hall - https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg - license checked July 25, 2026");
update_post_meta($post_id, 'vg_eeat_field_note', 'This checklist intentionally starts with route decisions rather than a list of attractions. The goal is to prevent booking mistakes before they become expensive.');
update_post_meta($post_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($post_id, 'vg_eeat_evidence_moat', "Decision-first planning sequence instead of generic tips.\nEntry checks framed as official-source work, not memory.\nTrip-length route matrix for 7, 10, 14, and 21 days.\nBooking-order logic that separates anchors from flexible activities.\nArrival first-hour sequence for money, SIM, transport, and first night.\nPhoto-led route examples with text-only Wikimedia credits.\nLow visible outbound link clutter, with source trail kept separate from the reading path.");
update_post_meta($post_id, 'vg_eeat_related_routes', "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Use the country-level guide after the first-trip checklist.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Choose the region emphasis before choosing every stop.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check season and weather risk by region.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Turn the checklist into a practical two-region route.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the major movement and comfort decisions before estimating daily spend.\nVietnam E-Visa | /plan/vietnam-evisa/ | Check entry assumptions before locking flights and route shape.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Check transfer friction before adding another base.");
update_post_meta($post_id, 'vg_eeat_hero_image_credit', 'Hero image: Ha Long Bay by Vyacheslav Argenberg, CC BY 4.0. Body images: Trang An by Jakub Halun, CC BY 4.0; Hoi An by Steffen Schmitz, CC BY-SA 4.0; Ho Chi Minh City Hall by Steffen Schmitz, CC BY-SA 4.0.');
update_post_meta($post_id, 'vg_content_owner', 'wp_admin');
update_post_meta($post_id, 'vg_automation_lock', 'locked');
update_post_meta($post_id, 'vg_last_manual_review', 'July 25, 2026');
update_post_meta($post_id, 'vg_admin_first_notes', 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until manual editor previews mobile, adds/adjusts images if desired, and confirms Rank Math before publishing.');

wp_set_object_terms($post_id, vg_first_trip_post_term_ids('category', ['travel-planning', 'practicalities']), 'category', false);
wp_set_object_terms($post_id, vg_first_trip_post_term_ids('post_tag', ['first-time-vietnam', 'international-travelers', 'route-planning', 'anti-spam-evergreen']), 'post_tag', false);

WP_CLI::success("Expanded first-trip checklist post to complete draft: {$post_id}");

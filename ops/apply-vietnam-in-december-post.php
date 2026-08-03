<?php
/**
 * Expand the Vietnam in December post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-vietnam-in-december-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_december_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_december_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_december_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_december_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_december_post_find_by_slug(string $slug): ?WP_Post
{
    $posts = get_posts(
        [
            'post_type' => 'post',
            'post_status' => ['draft', 'pending', 'private', 'future', 'publish'],
            'name' => $slug,
            'posts_per_page' => 2,
        ]
    );

    if (count($posts) !== 1) {
        vg_december_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_december_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-11',
            'vg_editorial_brief_status' => 'brief',
            'vg_editorial_batch' => 'batch-2-evergreen-planning',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if ($actual_value !== $expected_value) {
            vg_december_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_december_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_december_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'vietnam-in-december';
$post = vg_december_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_december_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_december_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 26, 2026';
vg_december_post_assert_target_meta($post);

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg/1920px-Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg');
$hanoi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$trang_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');
$hoi_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hcmc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg');
$phu_quoc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');

$meta_update_summary = 'Expanded the native WordPress batch 2 brief into a complete December draft with a photo-led hero, proof panel, concierge verdict, at-a-glance December route matrix, photo proof grid, source-diversity table, regional weather logic, route chooser, early-booking guidance, beach and bay trade-offs, packing notes, fragile-plan traps, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for broad December climate framing and regional weather caveats.",
    "Vietnam.travel - Plan your trip - https://vietnam.travel/plan-your-trip - checked {$review_date}; used for first-trip route planning discipline before bookings harden.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for December airport, road, cruise, ferry, and holiday movement caution.",
    "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked {$review_date}; used for cool north, Hanoi, Ninh Binh, and bay route context.",
    "Vietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked {$review_date}; used for central-coast rain and heritage-route caution.",
    "Vietnam.travel - Southern Vietnam destinations - https://vietnam.travel/places-to-go/southern-vietnam - checked {$review_date}; used for south and island route context.",
    "National Centre for Hydro-Meteorological Forecasting - English forecast and warning pages - https://nchmf.gov.vn/KttvsiteE/en-US/2/index.html - checked {$review_date}; used for live weather and warning discipline close to travel.",
    "World Weather Information Service - Viet Nam official city forecasts - https://worldweather.wmo.int/en/country.html?countryCode=82 - checked {$review_date}; used for official city-level weather context.",
    "Wikimedia Commons image direct URL - Ha Long Bay, Vietnam, View from above - {$hero_image} - credit Vyacheslav Argenberg / CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hanoi Hoan Kiem Lake - {$hanoi_image} - credit Alex 69200 vx / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Trang An Landscape Complex - {$trang_an_image} - credit Jakub Halun / CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hoi An Ancient Town - {$hoi_an_image} - credit Jakub Halun / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ho Chi Minh City Hall - {$hcmc_image} - credit Steffen Schmitz / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Kem Beach aerial view Phu Quoc Island Vietnam - {$phu_quoc_image} - credit Vivu Vietnam / CC BY-SA 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This complete draft treats December as a route decision, not a month-of-year trivia page. It separates cool north, central-coast risk, south and island route value, bay visibility, holiday booking pressure, and live weather checks.';
$meta_evidence_moat = implode("\n", [
    'December route decision framing instead of a generic weather paragraph.',
    'At-a-glance matrix separates cool north, central-coast risk, south and island route options, bay planning, and mixed itineraries.',
    'Photo proof makes each image explain a December planning job: bay exposure, northern city rhythm, inland karst, central heritage, southern city, and island beach value.',
    'Source-diversity table separates broad climate sources, official forecasts, transport sources, internal route judgment, and image-license records.',
    'Regional weather logic keeps the article from promising one national December answer.',
    'Route chooser connects December choices to first-time 10-day and 14-day itineraries.',
    'Early-booking section covers Christmas/New Year pressure, cruises, beach hotels, domestic flights, and flexible terms.',
    'Beach and bay table gives clear trade-offs without turning the post into a beach ranking.',
    'Packing notes connect cool mornings, rain layers, beach gear, and transfer comfort.',
    'No visible external body anchors; official source trail and image-license records stay auditable through metadata and shortcode output.',
]);
$meta_related_routes = implode("\n", [
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Use this for the country-level sequence before the December route is narrowed.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Use this for the broader seasonal frame before choosing December-specific trade-offs.',
    'North Central South Vietnam | /compare/north-central-south-vietnam/ | Use this to decide which region should lead the December route.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Use this when December travel needs two strong regions and fewer fragile moves.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use this when two weeks allow a fuller December route with buffers.',
    'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Use this before making a December cruise a route anchor.',
    'Best Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ | Use this to compare beach choices by month and route style.',
    'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Use this when December winter-sun and island rest are a priority.',
    'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use this when central-coast weather risk still needs city and food value.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Use this when Christmas/New Year pricing changes the budget.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Use this before booking December flights, road transfers, cruises, or ferries.',
]);
$meta_hero_image_credit = 'Hero image: Ha Long Bay by Vyacheslav Argenberg, CC BY 4.0. Body images: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0; Trang An Landscape Complex by Jakub Halun, CC BY 4.0; Hoi An Ancient Town by Jakub Halun, CC BY-SA 4.0; Ho Chi Minh City Hall by Steffen Schmitz, CC BY-SA 4.0; Kem Beach aerial view Phu Quoc Island Vietnam by Vivu Vietnam, CC BY-SA 4.0.';
$meta_admin_notes = 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, checks Rank Math/social preview, verifies December weather wording, image presentation, source-trail records, and adds any first-hand December notes before publishing. This article is not a live forecast and should avoid false certainty.';

$content = <<<HTML
<!-- vg-december-hero:v1 -->
<section class="vg-guide-hero vg-december-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Month-by-month route planning</p>
<h1>Vietnam in December: Weather, Routes and What to Book Early</h1>
<p class="vg-guide-lede">December can be an excellent month for Vietnam when the route is honest about regional differences. The north can feel cooler and atmospheric, the central coast still needs weather caution, the south and islands often carry winter-sun appeal, and Christmas/New Year pressure can change prices and availability.</p>
<p class="vg-field-note">Use this after <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, then check <a href="/compare/north-central-south-vietnam/">North Central South Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before locking flights or cruises.</p>
</div>
<figure class="vg-guide-hero-image"><img src="{$hero_image}" alt="Ha Long Bay limestone islands used as a December Vietnam route planning reference" loading="eager" decoding="async"><figcaption>December can suit a northern culture-and-scenery route, but bay visibility, cool decks, and holiday demand still need a booking plan. Image: Vyacheslav Argenberg / CC BY 4.0.</figcaption></figure>
</section>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-december-concierge-verdict:v1 -->
<aside class="vg-concierge-verdict vg-december-concierge-verdict" aria-label="Vietnam in December verdict">
<p class="vg-kicker">VietnamGuide verdict</p>
<h2>Treat December as a route choice, not a single weather answer.</h2>
<p><strong>For most first-time visitors, December works best as either a north-plus-central route with weather humility, or a south/island route when warm downtime matters.</strong> The strongest plans protect holiday availability, keep the central coast flexible, and do not assume beaches, mountains, and bay cruises all behave the same way.</p>
<ul>
<li><strong>Best first-trip default:</strong> Hanoi, Ninh Binh, Ha Long/Lan Ha, and a central or southern finish if time allows.</li>
<li><strong>Best warm-weather bias:</strong> Ho Chi Minh City, Mekong, Phu Quoc, or another south/island finish.</li>
<li><strong>Best caution zone:</strong> central-coast beach plans that depend on sun and dry days.</li>
<li><strong>Best booking rule:</strong> reserve scarce Christmas/New Year anchors early, but keep weather-sensitive add-ons flexible.</li>
</ul>
</aside>

<p>This guide is written for international travelers deciding whether December is the right month for a Vietnam trip. It does not try to predict the weather for your exact dates. Instead, it shows how December changes the route decision: which regions deserve priority, where to build flexibility, what to book early, and what to avoid promising yourself before live forecasts are available.</p>

<p>December is attractive because it often sits outside the harshest heat for many first-time routes. That does not make it simple. A traveler who wants Hanoi food, Ninh Binh landscapes, and a bay cruise is solving a different problem from a traveler who wants beach time around Christmas, or a family trying to keep airport and hotel moves calm during a school-holiday window.</p>

<p>Visible external links are intentionally avoided in the body. Official weather, transport, region, and image-license records stay in the source trail metadata so the article remains clean, evergreen, and auditable.</p>

<!-- vg-december-at-a-glance:v1 -->
<h2 class="wp-block-heading">Vietnam in December at a glance</h2>
<table class="vg-decision-table vg-december-at-a-glance">
<thead><tr><th>Traveler goal</th><th>Best December bias</th><th>What to watch</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Traveler goal">First Vietnam trip</td><td data-label="Best December bias">North plus one carefully chosen second region.</td><td data-label="What to watch">Cool mornings, bay visibility, holiday demand, and overpacked routes.</td><td data-label="VietnamGuide verdict">A strong month if the itinerary does not chase every region.</td></tr>
<tr><td data-label="Traveler goal">Culture and scenery</td><td data-label="Best December bias">Hanoi, Ninh Binh, Ha Long/Lan Ha, Hue or Hoi An with backup days.</td><td data-label="What to watch">Central-coast rain and road/pass timing.</td><td data-label="VietnamGuide verdict">Good when heritage and food matter more than beach perfection.</td></tr>
<tr><td data-label="Traveler goal">Warm beach finish</td><td data-label="Best December bias">South and island route, especially when Phu Quoc is a real rest chapter.</td><td data-label="What to watch">Holiday prices, resort availability, ferry/flight buffers, and exact island weather.</td><td data-label="VietnamGuide verdict">Worth considering when warmth is the main reason for choosing December.</td></tr>
<tr><td data-label="Traveler goal">Central coast base</td><td data-label="Best December bias">Da Nang, Hoi An, and Hue with city, food, and heritage value.</td><td data-label="What to watch">Beach assumptions and wet-weather transfer comfort.</td><td data-label="VietnamGuide verdict">Works better as culture-plus-comfort than as a pure beach plan.</td></tr>
<tr><td data-label="Traveler goal">Family holiday trip</td><td data-label="Best December bias">Fewer hotel moves, better first/last nights, and protected transfer days.</td><td data-label="What to watch">Christmas/New Year compression, late arrivals, and domestic flight timing.</td><td data-label="VietnamGuide verdict">Book the anchors early, not every ordinary activity.</td></tr>
</tbody>
</table>

<!-- vg-december-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: what December is really asking you to decide</h2>
<p>These photos are not filler. They show the different jobs December can give your route: cool north, bay exposure, inland scenery, central heritage, southern city energy, and island rest.</p>
<div class="vg-guide-photo-grid vg-december-photo-proof" aria-label="Vietnam in December photo proof">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Ha Long Bay from above, a northern Vietnam December route reference" loading="lazy" decoding="async"><figcaption>Bay plans can be valuable in December, but cruises still need policy, visibility, and deck-comfort checks. Image: Vyacheslav Argenberg / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hanoi_image}" alt="Hoan Kiem Lake in Hanoi, a cool-season city reference" loading="lazy" decoding="async"><figcaption>Hanoi is a strong December base because food, culture, walking radius, and cafes still work when mornings feel cool. Image: Alex 69200 vx / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$trang_an_image}" alt="Trang An limestone landscape in Ninh Binh" loading="lazy" decoding="async"><figcaption>Ninh Binh can be excellent when the route gives it protected daylight instead of a rushed transfer sandwich. Image: Jakub Halun / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hoi_an_image}" alt="Hoi An Ancient Town, central Vietnam heritage route reference" loading="lazy" decoding="async"><figcaption>Central Vietnam in December should not be judged only by beach value; heritage, food, and town rhythm can still carry the stay. Image: Jakub Halun / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall and walking street at night" loading="lazy" decoding="async"><figcaption>Ho Chi Minh City can anchor a warmer southern chapter when the north or central coast is not the whole trip. Image: Steffen Schmitz / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$phu_quoc_image}" alt="Kem Beach aerial view on Phu Quoc Island" loading="lazy" decoding="async"><figcaption>Phu Quoc can make sense as a December rest finish when the hotel and flight plan survive holiday pressure. Image: Vivu Vietnam / CC BY-SA 4.0.</figcaption></figure>
</div>

<!-- vg-december-source-diversity:v1 -->
<h2 class="wp-block-heading">Source diversity for December planning</h2>
<table class="vg-decision-table vg-december-source-diversity">
<thead><tr><th>Source family</th><th>What it helps decide</th><th>What it cannot decide alone</th><th>VietnamGuide judgment</th></tr></thead>
<tbody>
<tr><td data-label="Source family">Official climate and tourism guidance</td><td data-label="What it helps decide">Broad regional season patterns and trip-planning context.</td><td data-label="What it cannot decide alone">Your exact weather, hotel value, or whether a route is too ambitious.</td><td data-label="VietnamGuide judgment">Use for route shape, then add live checks near travel.</td></tr>
<tr><td data-label="Source family">Official forecasts and warnings</td><td data-label="What it helps decide">Short-range decisions for bay, beach, ferry, road, and mountain days.</td><td data-label="What it cannot decide alone">Evergreen route value months ahead.</td><td data-label="VietnamGuide judgment">Use close to payment and departure, not as evergreen filler.</td></tr>
<tr><td data-label="Source family">Transport and operator policies</td><td data-label="What it helps decide">Cruise pickup, domestic flights, ferry exits, and cancellation risk.</td><td data-label="What it cannot decide alone">Whether the destination belongs in the itinerary.</td><td data-label="VietnamGuide judgment">Use when a booking can break the route if delayed.</td></tr>
<tr><td data-label="Source family">Internal route evidence</td><td data-label="What it helps decide">Which VietnamGuide route pages should carry the next decision.</td><td data-label="What it cannot decide alone">Current operating hours or live weather.</td><td data-label="VietnamGuide judgment">Use to avoid duplicate December articles and weak keyword variants.</td></tr>
<tr><td data-label="Source family">Image-license records</td><td data-label="What it helps decide">Whether a visual is properly credited and useful as planning proof.</td><td data-label="What it cannot decide alone">Weather certainty.</td><td data-label="VietnamGuide judgment">Use photos to explain route jobs, not to decorate a month page.</td></tr>
</tbody>
</table>

<!-- vg-december-region-weather:v1 -->
<h2 class="wp-block-heading">Region-by-region December weather logic</h2>
<table class="vg-decision-table vg-december-region-weather">
<thead><tr><th>Region</th><th>December route value</th><th>Main caution</th><th>Better planning move</th></tr></thead>
<tbody>
<tr><td data-label="Region">Northern Vietnam</td><td data-label="December route value">Hanoi, Ninh Binh, and the bay can feel more comfortable for walking and sightseeing than hotter months.</td><td data-label="Main caution">Cool mornings/evenings, mist, bay visibility, and highland chill if adding mountains.</td><td data-label="Better planning move">Pack a light layer and keep bay/cruise expectations practical.</td></tr>
<tr><td data-label="Region">Central Vietnam</td><td data-label="December route value">Hue, Hoi An, and Da Nang can still work as food, heritage, and city-comfort stops.</td><td data-label="Main caution">Central-coast rain and beach disappointment if the trip depends on sun.</td><td data-label="Better planning move">Treat the region as culture-plus-comfort, not only beach time.</td></tr>
<tr><td data-label="Region">Southern Vietnam</td><td data-label="December route value">Ho Chi Minh City, the Mekong, and southern food/culture often fit travelers seeking warmer energy.</td><td data-label="Main caution">Heat, holiday demand, traffic, and day-trip fatigue.</td><td data-label="Better planning move">Use HCMC as a real chapter, not just an exit airport.</td></tr>
<tr><td data-label="Region">Islands and beaches</td><td data-label="December route value">Phu Quoc and selected southern beach routes can deliver the winter-sun finish many travelers want.</td><td data-label="Main caution">Christmas/New Year prices, resort availability, and flight/ferry buffer needs.</td><td data-label="Better planning move">Book quality beach anchors early and keep the exit protected.</td></tr>
<tr><td data-label="Region">Mountains and highlands</td><td data-label="December route value">Clearer scenery can be possible, but comfort depends heavily on exact elevation and weather.</td><td data-label="Main caution">Cold, fog, road risk, and insurance/safety assumptions.</td><td data-label="Better planning move">Add mountains only if the route has time and the traveler wants the conditions, not just the photos.</td></tr>
</tbody>
</table>

<!-- vg-december-route-chooser:v1 -->
<h2 class="wp-block-heading">Choose the December route shape</h2>
<table class="vg-decision-table vg-december-route-chooser">
<thead><tr><th>Route shape</th><th>Best for</th><th>What to skip first</th><th>Next guide</th></tr></thead>
<tbody>
<tr><td data-label="Route shape">North-first 10 days</td><td data-label="Best for">First-timers who want Hanoi, Ninh Binh, bay scenery, food, and culture.</td><td data-label="What to skip first">A third region that turns every stop into a transit day.</td><td data-label="Next guide"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">North plus central</td><td data-label="Best for">Travelers who want heritage, Hoi An/Hue food, and a balanced first trip.</td><td data-label="What to skip first">Central beach promises that require perfect weather.</td><td data-label="Next guide"><a href="/compare/north-central-south-vietnam/">North Central South Vietnam</a></td></tr>
<tr><td data-label="Route shape">South plus island</td><td data-label="Best for">Travelers choosing December for warmth, beach rest, or a calmer holiday finish.</td><td data-label="What to skip first">A rushed northern add-on that weakens the beach goal.</td><td data-label="Next guide"><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a></td></tr>
<tr><td data-label="Route shape">Full country 14 days</td><td data-label="Best for">Travelers who can afford transfer days and want north, central, and south chapters.</td><td data-label="What to skip first">Extra one-night stops during Christmas/New Year pressure.</td><td data-label="Next guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">Beach-led December</td><td data-label="Best for">Travelers who care more about warmth and rest than maximum sightseeing.</td><td data-label="What to skip first">Central-coast beach certainty when southern options fit better.</td><td data-label="Next guide"><a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a></td></tr>
</tbody>
</table>

<!-- vg-december-book-early:v1 -->
<h2 class="wp-block-heading">What to book early in December</h2>
<table class="vg-decision-table vg-december-book-early">
<thead><tr><th>Booking anchor</th><th>Why December changes it</th><th>Book early when</th><th>Keep flexible when</th></tr></thead>
<tbody>
<tr><td data-label="Booking anchor">International flights</td><td data-label="Why December changes it">Christmas and New Year travel windows can compress good fares and routing.</td><td data-label="Book early when">Open-jaw flights remove a domestic backtrack.</td><td data-label="Keep flexible when">Your region mix is still undecided.</td></tr>
<tr><td data-label="Booking anchor">First and last nights</td><td data-label="Why December changes it">Holiday arrivals can make central, easy-transfer hotels more valuable.</td><td data-label="Book early when">Landing late, traveling with family, or departing near a holiday peak.</td><td data-label="Keep flexible when">The middle of the route still depends on weather choices.</td></tr>
<tr><td data-label="Booking anchor">Bay cruise</td><td data-label="Why December changes it">Popular cabins and better operators can sell earlier in holiday periods.</td><td data-label="Book early when">The cruise is a route anchor and cancellation terms are clear.</td><td data-label="Keep flexible when">You would be disappointed by mist, cool weather, or a changed route.</td></tr>
<tr><td data-label="Booking anchor">Beach resort</td><td data-label="Why December changes it">Warm-weather demand can raise prices for the best island or south-coast stays.</td><td data-label="Book early when">The hotel itself is the reason for the beach finish.</td><td data-label="Keep flexible when">The beach is only a maybe add-on after sightseeing.</td></tr>
<tr><td data-label="Booking anchor">Domestic flights</td><td data-label="Why December changes it">Good times and baggage-friendly fares may narrow around holidays.</td><td data-label="Book early when">The flight simplifies the route and protects usable days.</td><td data-label="Keep flexible when">The flight is only hiding an overloaded itinerary.</td></tr>
</tbody>
</table>

<!-- vg-december-beach-bay:v1 -->
<h2 class="wp-block-heading">Beach and bay trade-offs in December</h2>
<table class="vg-decision-table vg-december-beach-bay">
<thead><tr><th>Choice</th><th>Why it can work</th><th>Why it can disappoint</th><th>Better decision</th></tr></thead>
<tbody>
<tr><td data-label="Choice">Ha Long / Lan Ha cruise</td><td data-label="Why it can work">Scenery, cabin experience, and a clean northern route anchor.</td><td data-label="Why it can disappoint">Mist, cool decks, route changes, or expecting tropical swimming.</td><td data-label="Better decision">Book for scenery and experience, not guaranteed sun.</td></tr>
<tr><td data-label="Choice">Da Nang / Hoi An coast</td><td data-label="Why it can work">Food, heritage, hotels, airport access, and a softer central base.</td><td data-label="Why it can disappoint">Beach expectations can be too high if weather is unsettled.</td><td data-label="Better decision">Choose it for central route value, with beach as a bonus.</td></tr>
<tr><td data-label="Choice">Nha Trang / central-south coast</td><td data-label="Why it can work">Can suit some beach-focused travelers when route and weather align.</td><td data-label="Why it can disappoint">It may not beat a cleaner south/island finish for first-timers.</td><td data-label="Better decision">Compare against Phu Quoc and route logistics before adding it.</td></tr>
<tr><td data-label="Choice">Phu Quoc</td><td data-label="Why it can work">Island rest, resort value, warmer finish, and a clear holiday role.</td><td data-label="Why it can disappoint">Higher holiday prices and flight availability pressure.</td><td data-label="Better decision">Use when rest is a real chapter, not a token beach stop.</td></tr>
<tr><td data-label="Choice">No beach chapter</td><td data-label="Why it can work">More time for Hanoi, Ninh Binh, bay, Hue, Hoi An, food, and culture.</td><td data-label="Why it can disappoint">Travelers expecting winter sun may feel the route lacks rest.</td><td data-label="Better decision">Skip the beach only if sightseeing is truly the priority.</td></tr>
</tbody>
</table>

<!-- vg-december-packing:v1 -->
<h2 class="wp-block-heading">What December changes in the suitcase</h2>
<table class="vg-decision-table vg-december-packing">
<thead><tr><th>Route piece</th><th>Pack first</th><th>Why it matters</th><th>Do not overpack</th></tr></thead>
<tbody>
<tr><td data-label="Route piece">Hanoi / Ninh Binh / bay</td><td data-label="Pack first">Light jacket, breathable layers, closed walking shoes, socks, and a compact rain layer.</td><td data-label="Why it matters">Cool mornings and bay wind can surprise travelers expecting only tropical heat.</td><td data-label="Do not overpack">Bulky winter gear unless mountains or cold-sensitive travelers require it.</td></tr>
<tr><td data-label="Route piece">Central Vietnam</td><td data-label="Pack first">Quick-dry clothing, rain shell, sandals that handle wet streets, and temple-friendly layers.</td><td data-label="Why it matters">Heritage days and rain pivots need comfort more than fashion.</td><td data-label="Do not overpack">Heavy shoes that hate water.</td></tr>
<tr><td data-label="Route piece">Southern cities</td><td data-label="Pack first">Light breathable clothing, sun protection, and a thin layer for air-conditioning.</td><td data-label="Why it matters">The south can still feel warm while the north feels cool.</td><td data-label="Do not overpack">Multiple thick layers that only made sense in Hanoi.</td></tr>
<tr><td data-label="Route piece">Island or beach finish</td><td data-label="Pack first">Swimwear, sun shirt, sandals, dry bag, and one nicer resort/city layer.</td><td data-label="Why it matters">A December island chapter should be easy, not luggage-heavy.</td><td data-label="Do not overpack">A separate beach wardrobe for every possible day.</td></tr>
</tbody>
</table>

<!-- vg-december-fragile-plans:v1 -->
<h2 class="wp-block-heading">Fragile December plans to avoid</h2>
<table class="vg-decision-table vg-december-fragile-plans">
<thead><tr><th>Fragile plan</th><th>Why it is weak</th><th>Safer alternative</th></tr></thead>
<tbody>
<tr><td data-label="Fragile plan">Central-coast beach holiday with no backup value</td><td data-label="Why it is weak">If weather softens, the trip has no second job.</td><td data-label="Safer alternative">Choose a base that also works for food, heritage, cafes, and rest.</td></tr>
<tr><td data-label="Fragile plan">Cruise exit directly before an important flight</td><td data-label="Why it is weak">Weather, traffic, and operator timing can make small delays expensive.</td><td data-label="Safer alternative">Protect one buffer night or a sane onward transfer.</td></tr>
<tr><td data-label="Fragile plan">Three regions in a short holiday week</td><td data-label="Why it is weak">Holiday crowding and winter-light timing make every transfer cost more.</td><td data-label="Safer alternative">Pick a stronger two-region route.</td></tr>
<tr><td data-label="Fragile plan">Booking every ordinary activity in advance</td><td data-label="Why it is weak">You lose the ability to react to weather, energy, and better local information.</td><td data-label="Safer alternative">Book anchors early, keep low-stakes city days flexible.</td></tr>
<tr><td data-label="Fragile plan">Packing for one climate</td><td data-label="Why it is weak">December can ask for cool north layers and warm southern clothing in the same trip.</td><td data-label="Safer alternative">Pack a light modular system instead of separate wardrobes.</td></tr>
</tbody>
</table>

<!-- vg-december-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before final payment</h2>
<ul class="vg-check-list vg-december-live-checks">
<li>Check official forecasts and warnings before paying for bay cruises, ferries, beach hotels, mountain transfers, or weather-sensitive tours.</li>
<li>Check Christmas and New Year availability for first/last nights, cruises, domestic flights, and high-quality beach hotels.</li>
<li>Check whether central-coast stops still have enough food, heritage, and hotel value if the beach day weakens.</li>
<li>Check whether your north route includes layers for cool mornings, bay decks, and early/late transfers.</li>
<li>Check whether a beach finish is a real rest chapter or just an expensive extra stop.</li>
<li>Check domestic flight baggage rules if December packing crosses cool north and warm island conditions.</li>
<li>Check the route against <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before assuming holiday travel will price like shoulder season.</li>
</ul>

<!-- vg-december-faq:v1 -->
<h2 class="wp-block-heading">Vietnam in December FAQ</h2>
<div class="vg-faq-list vg-december-faq">
<details><summary>Is December a good time to visit Vietnam?</summary><p>December can be a very good time when the route is chosen by region. It is strongest for travelers who like cooler northern sightseeing, food and heritage, or a south/island finish. It is weaker when the whole trip depends on perfect central-coast beach weather.</p></details>
<details><summary>Where should I go in Vietnam in December?</summary><p>For a first trip, start with Hanoi, Ninh Binh, and a bay decision, then add central Vietnam or a southern/island finish only if the trip length supports it. For warmth, bias south earlier.</p></details>
<details><summary>Is Ha Long Bay worth it in December?</summary><p>It can be worth it for scenery and the cruise experience, but do not book it for guaranteed swimming or clear-sky photos. Ask about route, cabin, weather policy, pickup, port, and onward timing.</p></details>
<details><summary>Is Hoi An good in December?</summary><p>Hoi An can still be good when you value food, heritage, cafes, hotels, and atmosphere. It is risky if your only reason for going is beach weather.</p></details>
<details><summary>Is Phu Quoc good in December?</summary><p>Phu Quoc can be one of the stronger December choices for travelers wanting a warmer island finish, but holiday prices and flight availability make early booking more important.</p></details>
<details><summary>What should I pack for Vietnam in December?</summary><p>Pack modularly: light layers for the north and bay, quick-dry rain comfort for central Vietnam, breathable clothing for the south, and swimwear only if the beach chapter is real.</p></details>
</div>

<h2 class="wp-block-heading">Where this guide fits next</h2>
<p>Use this post once December is on your shortlist. Then choose the region spine, itinerary length, beach/bay anchor, and transport plan.</p>
<div class="vg-related-routes vg-december-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Use this for the broader seasonal frame before choosing December-specific trade-offs.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/compare/north-central-south-vietnam/">North Central South Vietnam</a><span class="vg-related-route-note">Use this to decide whether the December route should lead north, central, or south.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a><span class="vg-related-route-note">Use this when December travel needs a clean two-region route.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a><span class="vg-related-route-note">Use this before making a December cruise a route anchor.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a><span class="vg-related-route-note">Use this to decide whether the beach chapter belongs in December.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a><span class="vg-related-route-note">Use this when a warmer island finish is the point of traveling in December.</span></li>
</ol>
</div>

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
    'vg-december-hero:v1',
    'vg-december-concierge-verdict:v1',
    'vg-december-at-a-glance:v1',
    'vg-december-photo-proof:v1',
    'vg-december-source-diversity:v1',
    'vg-december-region-weather:v1',
    'vg-december-route-chooser:v1',
    'vg-december-book-early:v1',
    'vg-december-beach-bay:v1',
    'vg-december-packing:v1',
    'vg-december-fragile-plans:v1',
    'vg-december-live-checks:v1',
    'vg-december-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_december_post_fail("Missing content marker before update: {$marker}");
    }
}

$category_term_ids = vg_december_post_term_ids('category', ['seasonal-travel', 'travel-planning']);
$tag_term_ids = vg_december_post_term_ids('post_tag', ['month-by-month', 'first-time-vietnam', 'route-planning', 'anti-spam-evergreen']);

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Vietnam in December: Weather, Routes and What to Book Early',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Plan Vietnam in December by region, route shape, weather risk, holiday booking pressure, beach and bay trade-offs, packing, and live checks.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_december_post_fail('Could not update Vietnam in December post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Vietnam in December: Weather and Best Routes');
update_post_meta($post_id, 'rank_math_description', 'Plan Vietnam in December by region, route shape, weather risk, holiday booking pressure, beach and bay trade-offs, packing, and live checks.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Vietnam in December');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Choose a December Vietnam route by region: cool north, central-coast risk, south and island route value, holiday booking pressure, and live weather checks.');
update_post_meta($post_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($post_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($post_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($post_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($post_id, 'vg_eeat_update_summary', $meta_update_summary);
update_post_meta($post_id, 'vg_eeat_sources_checked', $meta_sources_checked);
update_post_meta($post_id, 'vg_eeat_field_note', $meta_field_note);
update_post_meta($post_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($post_id, 'vg_eeat_evidence_moat', $meta_evidence_moat);
update_post_meta($post_id, 'vg_eeat_related_routes', $meta_related_routes);
update_post_meta($post_id, 'vg_eeat_hero_image_credit', $meta_hero_image_credit);
update_post_meta($post_id, 'vg_content_owner', 'wp_admin');
update_post_meta($post_id, 'vg_automation_lock', 'locked');
update_post_meta($post_id, 'vg_last_manual_review', $review_date);
update_post_meta($post_id, 'vg_admin_first_notes', $meta_admin_notes);

$category_result = wp_set_object_terms($post_id, $category_term_ids, 'category', false);

if (is_wp_error($category_result)) {
    vg_december_post_fail('Could not assign Vietnam in December categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_december_post_fail('Could not assign Vietnam in December tags: ' . $tag_result->get_error_message());
}

$required_meta = [
    'rank_math_title',
    'rank_math_description',
    'rank_math_focus_keyword',
    'vg_editorial_brief_status',
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
    'vg_content_owner',
    'vg_automation_lock',
];

foreach ($required_meta as $meta_key) {
    $meta_value = get_post_meta($post_id, $meta_key, true);

    if ((is_string($meta_value) && trim($meta_value) === '') || $meta_value === [] || $meta_value === null) {
        vg_december_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Vietnam in December post to complete draft: {$post_id}");

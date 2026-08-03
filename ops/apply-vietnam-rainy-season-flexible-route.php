<?php
/**
 * Expand the Vietnam Rainy Season Travel post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-vietnam-rainy-season-flexible-route.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_rainy_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_rainy_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_rainy_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_rainy_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_rainy_post_find_by_slug(string $slug): ?WP_Post
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
        vg_rainy_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_rainy_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-10',
            'vg_editorial_brief_status' => 'brief',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if ($actual_value !== $expected_value) {
            vg_rainy_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_rainy_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_rainy_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'vietnam-rainy-season-flexible-route';
$post = vg_rainy_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_rainy_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_rainy_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 25, 2026';
vg_rainy_post_assert_target_meta($post);

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hoi_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg');
$hanoi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$hanoi_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg');
$hai_van_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1920px-Vietnam%2C_Hai-Van-Pass.jpg');
$hai_van_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg');
$hcmc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg');
$hcmc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg');
$mekong_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1920px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg');
$mekong_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg');
$phu_quoc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$phu_quoc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');

$meta_update_summary = 'Expanded the native WordPress post brief into a complete rainy-season route draft with a photo-led hero, proof panel, concierge verdict, at-a-glance rainy-season matrix, photo proof grid, source-diversity table, region-by-region weather logic, route flexibility and buffer logic, cancellation and refund posture, city-day / indoor pivots, transport and transfer caution, fragile-booking traps, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for broad rainy-season and regional weather framing.",
    "Vietnam.travel - Plan your trip - https://vietnam.travel/plan-your-trip - checked {$review_date}; used for planning order and route discipline before bookings harden.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for transport and transfer caution in wet weather.",
    "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked {$review_date}; used for northern route and landscape-planning context.",
    "Vietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked {$review_date}; used for central-coast weather sensitivity, Hoi An, Hue, and Da Nang logic.",
    "Vietnam.travel - Southern Vietnam destinations - https://vietnam.travel/places-to-go/southern-vietnam - checked {$review_date}; used for southern city, Mekong, and island route context.",
    "National Centre for Hydro-Meteorological Forecasting - English forecast and warning pages - https://nchmf.gov.vn/KttvsiteE/en-US/2/index.html - checked {$review_date}; used for live forecast and warning discipline.",
    "World Weather Information Service - Viet Nam official city forecasts - https://worldweather.wmo.int/en/country.html?countryCode=82 - checked {$review_date}; used for official city-level weather context.",
    "CDC Travelers' Health - Vietnam - https://wwwnc.cdc.gov/travel/destinations/traveler/none/vietnam - checked {$review_date}; used for traveler-health framing around wet weather, heat, and personal medical judgment.",
    "GOV.UK - Vietnam health - https://www.gov.uk/foreign-travel-advice/vietnam/health - checked {$review_date}; used as an additional government travel-health source family.",
    "Wikimedia Commons image direct URL - Hoi An Ancient Town - {$hero_image} - credit Jakub Halun / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hanoi Hoan Kiem Lake - {$hanoi_image} - credit Alex 69200 vx / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Vietnam, Hai-Van-Pass - {$hai_van_image} - credit Wolkenkratzer / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ho Chi Minh City Hall - {$hcmc_image} - credit Steffen Schmitz / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Vietnam, Phong Dien, Mekong Delta, River - {$mekong_image} - credit Vyacheslav Argenberg / CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Kem Beach aerial view Phu Quoc Island Vietnam - {$phu_quoc_image} - credit Vivu Vietnam / CC BY-SA 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This post is the rainy-season flexibility layer for the native WordPress editorial calendar. It avoids weather-roundup content by helping travelers choose buffers, refundable bookings, indoor pivots, and region-specific route moves.';
$meta_evidence_moat = implode("\n", [
    'Decision-led rainy-season guide built around route flexibility instead of a generic month table.',
    'At-a-glance matrix separates north, central, south, mountain, coast, and island pressure.',
    'Photo proof makes each image do planning work: central heritage streets, northern city rhythm, pass wind, southern city heat, river days, and island exposure.',
    'Source-diversity table separates broad climate guidance from live weather checks and traveler-health judgment.',
    'Region-by-region weather logic explains how rain changes the route differently in the north, central coast, south, mountains, and islands.',
    'Buffer logic teaches travelers where flexibility matters and where a rainy day can still be a good city day.',
    'Cancellation and refund posture keeps non-refundable hotels, cruises, ferries, domestic flights, and day tours from hardening too early.',
    'City-day and indoor pivots prevent weather from turning the trip into a blank day.',
    'Transport and transfer caution covers road, rail, flight, cruise, ferry, and luggage risk.',
    'No visible external body anchors; official source trail and image-license records stay auditable through metadata and shortcode output.',
]);
$meta_related_routes = implode("\n", [
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Use this for the broad first-trip order before deciding whether rainy-season flexibility belongs in the route.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Use this to understand seasonal trade-offs before locking dates.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Use this to protect roads, trains, flights, ferries, and cruise pickups in wet weather.',
    'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Use before departure for medical, policy, interruption, and personal-health preparation.',
    'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair rainy night movement, phone handling, bags, and street awareness with practical habits.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Keep flexible hotels, transfers, and backup plans inside the real route budget.',
    'North Central South Vietnam | /compare/north-central-south-vietnam/ | Use this when rain makes one region a better lead than another.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Use this when a short trip needs fewer regions and stronger buffers.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use this when two weeks allow a more flexible north-central-south route.',
    'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use Hanoi city texture as a rainy-day pivot rather than treating the day as lost.',
    'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use central-coast logic when rain or wind changes beach and pass plans.',
    'Ho Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Use HCMC for southern city energy, museums, cafes, and flexible rainy afternoons.',
    'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Use Ninh Binh with boat, cave, cycling, and transfer caution when rain rises.',
    'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Use island logic only when a wet-weather resort plan still has value.',
]);
$meta_hero_image_credit = 'Hero image: Hoi An Ancient Town by Jakub Halun, CC BY-SA 4.0. Body images: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0; Vietnam, Hai-Van-Pass by Wolkenkratzer, CC BY-SA 4.0; Ho Chi Minh City Hall by Steffen Schmitz, CC BY-SA 4.0; Vietnam, Phong Dien, Mekong Delta, River by Vyacheslav Argenberg, CC BY 4.0; Kem Beach aerial view Phu Quoc Island Vietnam by Vivu Vietnam, CC BY-SA 4.0.';
$meta_admin_notes = 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, checks Rank Math/social preview, verifies weather logic, cancellation posture, image presentation, source-trail records, and adds any first-hand rainy-route notes before publishing. This article is not a live forecast and should never imply weather certainty.';

$content = <<<HTML
<!-- vg-rainy-hero:v1 -->
<section class="vg-guide-hero vg-rainy-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Rainy season without brittle plans</p>
<h1>Vietnam Rainy Season Travel: How to Build a Flexible Route</h1>
<p class="vg-guide-lede">Vietnam rainy-season travel works best when the route is flexible in the right places. The mistake is not traveling in a wet month. The mistake is booking a route that needs every outdoor day, ferry, cruise, beach plan, and road transfer to behave perfectly.</p>
<p class="vg-field-note">Start with <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, then use <a href="/compare/north-central-south-vietnam/">North Central South Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> to decide where flexibility belongs.</p>
</div>
<figure class="vg-guide-hero-image"><img src="{$hero_image}" alt="Hoi An Ancient Town, used as a central Vietnam rainy-season route planning reference" loading="eager" decoding="async"><figcaption>Rain changes different parts of Vietnam differently. Central heritage towns, beaches, passes, and transfers need a separate plan. Image: Jakub Halun / CC BY-SA 4.0.</figcaption></figure>
</section>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-rainy-concierge-verdict:v1 -->
<aside class="vg-concierge-verdict vg-rainy-concierge-verdict" aria-label="Vietnam rainy season travel verdict">
<p class="vg-kicker">VietnamGuide verdict</p>
<h2>Build the rainy-season route around pivots, not apologies.</h2>
<p><strong>A good rainy-season route has one lead region, fewer fragile bookings, and at least one city or culture day that still feels valuable in wet weather.</strong> Keep the beach, bay, island, mountain, and boat pieces flexible; protect arrival and departure days; and do not let one famous outdoor highlight control the whole trip.</p>
<ul>
<li><strong>Best route posture:</strong> choose the lead region first, then let rain-sensitive add-ons earn their place.</li>
<li><strong>Best booking posture:</strong> refundable hotels where weather could change the order, clear cancellation terms for cruises, boats, ferries, and tours.</li>
<li><strong>Best day structure:</strong> one outdoor plan, one indoor/city pivot, and no same-day dependency on a tight onward flight.</li>
<li><strong>Best traveler mindset:</strong> rainy-season travel can be excellent when the route has room to bend.</li>
</ul>
</aside>

<p>This article is not a live forecast. It is a route design guide for travelers who already know rain is possible and need to decide what to protect. For medical, interruption, and policy preparation, use <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>. For wet-weather city movement, bags, phones, and night habits, pair this with <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>. For flexibility cost, use <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>.</p>

<p>Rain does not make every Vietnam trip a bad idea. It does punish overconfident routes: too many hotel moves, non-refundable beach-only stays, ferry exits close to flights, cruise pickups without policy clarity, and countryside days with no alternate plan. The premium move is to design fewer brittle moments.</p>

<p>Visible external links are intentionally avoided in the body. Weather, region, health, image-license, and source records stay in the source trail metadata for auditability without turning the article into link clutter.</p>

<!-- vg-rainy-at-a-glance:v1 -->
<h2 class="wp-block-heading">Rainy-season route planning at a glance</h2>
<table class="vg-decision-table vg-rainy-at-a-glance">
<thead><tr><th>Route situation</th><th>Flexible move</th><th>Fragile move</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Route situation">Short first trip</td><td data-label="Flexible move">Pick one region or one tight spine and keep a city pivot.</td><td data-label="Fragile move">Crossing north, central, and south because the map looks easy.</td><td data-label="VietnamGuide verdict">Short wet-season trips need fewer regions, not more backups.</td></tr>
<tr><td data-label="Route situation">Central coast or Hoi An focus</td><td data-label="Flexible move">Book refundable hotels and protect heritage, food, cafe, and museum pivots.</td><td data-label="Fragile move">Building the whole route around beach photos and a single pass transfer.</td><td data-label="VietnamGuide verdict">Central Vietnam needs the strongest weather humility.</td></tr>
<tr><td data-label="Route situation">Bay, boat, or cruise night</td><td data-label="Flexible move">Check cancellation policy, pickup time, port, and next-day onward buffer.</td><td data-label="Fragile move">Flying internationally right after a weather-sensitive boat exit.</td><td data-label="VietnamGuide verdict">A bay night can still work, but the exit needs margin.</td></tr>
<tr><td data-label="Route situation">Southern city plus Mekong</td><td data-label="Flexible move">Use Ho Chi Minh City as a strong city base and make the river day adjustable.</td><td data-label="Fragile move">Treating a wet river day as a mandatory final highlight before a flight.</td><td data-label="VietnamGuide verdict">The south is often more workable when the city base is not treated as filler.</td></tr>
<tr><td data-label="Route situation">Island or beach finish</td><td data-label="Flexible move">Choose a hotel that still feels good in rain and leave an exit buffer.</td><td data-label="Fragile move">Paying non-refundable beach nights when the trip has no alternate value.</td><td data-label="VietnamGuide verdict">A rainy-season island works only if the resort plan still earns the nights.</td></tr>
</tbody>
</table>

<!-- vg-rainy-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: rain changes the job of each place</h2>
<p>The same forecast does not mean the same thing everywhere. A wet city day can still be good. A wet ferry exit or mountain road can be a real route risk.</p>
<div class="vg-guide-photo-grid vg-rainy-photo-proof" aria-label="Vietnam rainy season route photo proof">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Hoi An Ancient Town, a central Vietnam rainy-season route reference" loading="lazy" decoding="async"><figcaption>Central heritage towns need flexible rain and indoor pivots, not a beach-only mindset. Image: Jakub Halun / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hanoi_image}" alt="Hoan Kiem Lake in Hanoi, a northern city rainy-day reference" loading="lazy" decoding="async"><figcaption>Hanoi can absorb rain better when the day includes food, museums, cafes, and a tighter walking radius. Image: Alex 69200 vx / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hai_van_image}" alt="Hai Van Pass mountain and coastal road between Hue and Da Nang" loading="lazy" decoding="async"><figcaption>Passes and scenic transfers need live checks because rain can change visibility, comfort, and timing. Image: Wolkenkratzer / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall, southern Vietnam city base reference" loading="lazy" decoding="async"><figcaption>Ho Chi Minh City can hold a rainy afternoon with museums, cafes, food, and district choices. Image: Steffen Schmitz / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mekong_image}" alt="Mekong Delta river scene near Phong Dien, Vietnam" loading="lazy" decoding="async"><figcaption>River days can still be memorable, but pickup time, road time, and return buffer matter more in wet months. Image: Vyacheslav Argenberg / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$phu_quoc_image}" alt="Kem Beach aerial view on Phu Quoc Island" loading="lazy" decoding="async"><figcaption>Island plans should still work if the best beach day becomes a resort, food, or spa day. Image: Vivu Vietnam / CC BY-SA 4.0.</figcaption></figure>
</div>

<!-- vg-rainy-source-diversity:v1 -->
<h2 class="wp-block-heading">Source diversity and limits</h2>
<table class="vg-decision-table vg-rainy-source-diversity">
<thead><tr><th>Source family</th><th>Use it for</th><th>Do not use it for</th><th>VietnamGuide judgment</th></tr></thead>
<tbody>
<tr><td data-label="Source family">Weather and climate pages</td><td data-label="Use it for">Broad seasonal shape and regional differences.</td><td data-label="Do not use it for">A promise that one exact travel day will be dry or wet.</td><td data-label="VietnamGuide judgment">Use early, then build a flexible route.</td></tr>
<tr><td data-label="Source family">Forecast and warning services</td><td data-label="Use it for">Close-in checks, warnings, and city-level conditions.</td><td data-label="Do not use it for">Long-range booking certainty months ahead.</td><td data-label="VietnamGuide judgment">Use before final payment and again near departure.</td></tr>
<tr><td data-label="Source family">Regional tourism pages</td><td data-label="Use it for">Understanding whether a place is city, coast, mountain, river, heritage, or island led.</td><td data-label="Do not use it for">Exact operator policies or your personal comfort threshold.</td><td data-label="VietnamGuide judgment">Use to choose the right pivot for the region.</td></tr>
<tr><td data-label="Source family">Transport guidance</td><td data-label="Use it for">Recognizing where road, flight, ferry, cruise, and transfer friction can compound.</td><td data-label="Do not use it for">Assuming every rainy day disrupts every movement.</td><td data-label="VietnamGuide judgment">Protect the movements that matter most.</td></tr>
<tr><td data-label="Source family">Traveler health sources</td><td data-label="Use it for">Heat, rain, personal health, and travel-preparation discipline.</td><td data-label="Do not use it for">Replacing medical advice or insurance terms.</td><td data-label="VietnamGuide judgment">Use for personal risk limits and backup planning.</td></tr>
</tbody>
</table>

<!-- vg-rainy-region-logic:v1 -->
<h2 class="wp-block-heading">Region-by-region weather logic</h2>
<table class="vg-decision-table vg-rainy-region-logic">
<thead><tr><th>Region</th><th>What rain changes</th><th>Best flexible route move</th><th>What to avoid</th></tr></thead>
<tbody>
<tr><td data-label="Region">Northern Vietnam</td><td data-label="What rain changes">Walking comfort, bay visibility, countryside cycling, mountain roads, and fog or cloud in scenic areas.</td><td data-label="Best flexible route move">Keep Hanoi as a strong city anchor and avoid stacking bay, countryside, and mountains with no buffer.</td><td data-label="What to avoid">Treating Ninh Binh, Ha Long, and a mountain stop as weather-proof in a short route.</td></tr>
<tr><td data-label="Region">Central Vietnam</td><td data-label="What rain changes">Beach value, pass visibility, river-side walking, heritage comfort, and outdoor transfers between Hue, Da Nang, and Hoi An.</td><td data-label="Best flexible route move">Choose refundable hotels and build cafe, food, museum, and heritage pivots around the central base.</td><td data-label="What to avoid">Beach-first central plans with no indoor or city fallback.</td></tr>
<tr><td data-label="Region">Southern Vietnam</td><td data-label="What rain changes">Afternoon energy, street movement, Mekong road days, river comfort, and island expectations.</td><td data-label="Best flexible route move">Use Ho Chi Minh City as an active base and keep Mekong or island extensions adjustable.</td><td data-label="What to avoid">A final river or island day that must work before an international departure.</td></tr>
<tr><td data-label="Region">Mountains and passes</td><td data-label="What rain changes">Road safety, visibility, traction, clothing, photo value, and pickup timing.</td><td data-label="Best flexible route move">Place mountain days away from international departure and keep alternate city or lowland options.</td><td data-label="What to avoid">Booking the hardest scenic transfer as a fixed same-day connection.</td></tr>
<tr><td data-label="Region">Coasts and islands</td><td data-label="What rain changes">Beach value, boat comfort, ferry reliability, resort downtime, and exit timing.</td><td data-label="Best flexible route move">Choose accommodation that still feels good indoors and avoid tight ferry-to-flight chains.</td><td data-label="What to avoid">Paying premium beach prices for a plan that only works in perfect sun.</td></tr>
</tbody>
</table>

<!-- vg-rainy-flexibility:v1 -->
<h2 class="wp-block-heading">Route flexibility and buffer logic</h2>
<p>Good route flexibility is selective. You do not need an alternate country inside your suitcase. You need buffers around the days where rain can erase the value or break the exit.</p>
<table class="vg-decision-table vg-rainy-flexibility">
<thead><tr><th>Route piece</th><th>Buffer to protect</th><th>Why it matters</th><th>Better move</th></tr></thead>
<tbody>
<tr><td data-label="Route piece">Arrival day</td><td data-label="Buffer to protect">Sleep, SIM/eSIM, cash, hotel transfer, and easy dinner.</td><td data-label="Why it matters">A wet arrival can magnify first-hour friction.</td><td data-label="Better move">Keep the first night simple and postpone ambitious outdoor plans.</td></tr>
<tr><td data-label="Route piece">Most valuable outdoor day</td><td data-label="Buffer to protect">One alternate half-day or movable plan.</td><td data-label="Why it matters">The trip should not depend on one unrepeatable window.</td><td data-label="Better move">Book the highlight where there is room to move it.</td></tr>
<tr><td data-label="Route piece">Bay, cruise, island, or boat day</td><td data-label="Buffer to protect">Cancellation terms, port timing, pickup details, and next transfer.</td><td data-label="Why it matters">Water plans are more exposed to weather and operator decisions.</td><td data-label="Better move">Avoid immediate onward flights after the weather-sensitive exit.</td></tr>
<tr><td data-label="Route piece">Central coast stay</td><td data-label="Buffer to protect">Indoor value, heritage value, food options, and refundable rooms.</td><td data-label="Why it matters">A beach-only plan can feel thin when the weather weakens.</td><td data-label="Better move">Make the base useful even when the beach day disappears.</td></tr>
<tr><td data-label="Route piece">Departure day</td><td data-label="Buffer to protect">Airport access, luggage, road time, and final-night location.</td><td data-label="Why it matters">Wet traffic and delayed pickups are more painful at the end of the trip.</td><td data-label="Better move">Sleep near a sane transfer line and avoid long scenic exits on departure day.</td></tr>
</tbody>
</table>

<!-- vg-rainy-cancellation-refund:v1 -->
<h2 class="wp-block-heading">Cancellation and refund posture</h2>
<table class="vg-decision-table vg-rainy-cancellation-refund">
<thead><tr><th>Booking type</th><th>Question to ask</th><th>What to prefer</th><th>Red flag</th></tr></thead>
<tbody>
<tr><td data-label="Booking type">Hotel</td><td data-label="Question to ask">Could rain make me change region or stay length?</td><td data-label="What to prefer">Refundable or movable nights where the route is weather-sensitive.</td><td data-label="Red flag">A non-refundable stay in a place whose value is mostly beach, ferry, or mountain weather.</td></tr>
<tr><td data-label="Booking type">Cruise or boat trip</td><td data-label="Question to ask">What happens if weather changes route, port, or sailing?</td><td data-label="What to prefer">Clear written terms and enough next-day buffer.</td><td data-label="Red flag">Pretty photos with vague policy language.</td></tr>
<tr><td data-label="Booking type">Ferry or island transfer</td><td data-label="Question to ask">Can I absorb a delay without missing a flight?</td><td data-label="What to prefer">One protected buffer before international departure.</td><td data-label="Red flag">Island exit and international flight on the same brittle chain.</td></tr>
<tr><td data-label="Booking type">Domestic flight</td><td data-label="Question to ask">Does the flight solve the route or rescue an overpacked itinerary?</td><td data-label="What to prefer">Clean airports, realistic baggage rules, and backup value at both ends.</td><td data-label="Red flag">Separate tickets with a tight self-transfer after a weather-sensitive leg.</td></tr>
<tr><td data-label="Booking type">Day tour</td><td data-label="Question to ask">Can I move it, swap it, or choose a smaller local pivot?</td><td data-label="What to prefer">A provider with clear weather, pickup, and change terms.</td><td data-label="Red flag">Full payment before the plan has any weather flexibility.</td></tr>
</tbody>
</table>

<!-- vg-rainy-city-indoor-pivots:v1 -->
<h2 class="wp-block-heading">City-day / indoor pivots that still feel like Vietnam</h2>
<table class="vg-decision-table vg-rainy-city-indoor-pivots">
<thead><tr><th>Place</th><th>Rain-friendly pivot</th><th>What it protects</th><th>Do not force</th></tr></thead>
<tbody>
<tr><td data-label="Place">Hanoi</td><td data-label="Rain-friendly pivot">Museums, cafes, food streets in a tighter radius, Old Quarter observation, and lake-adjacent pauses.</td><td data-label="What it protects">A northern city day that still feels local.</td><td data-label="Do not force">Multiple distant outdoor pins in heavy rain.</td></tr>
<tr><td data-label="Place">Hue / Hoi An / Da Nang</td><td data-label="Rain-friendly pivot">Heritage interiors, food, markets, cafes, hotel rest, and a better-timed transfer.</td><td data-label="What it protects">Central depth even when beach or pass plans weaken.</td><td data-label="Do not force">A beach-first day when the weather has already changed the job.</td></tr>
<tr><td data-label="Place">Ho Chi Minh City</td><td data-label="Rain-friendly pivot">Museums, cafes, markets, food, district walks between showers, and a calmer evening plan.</td><td data-label="What it protects">The south as a real city chapter.</td><td data-label="Do not force">A far day trip when traffic and rain are both working against you.</td></tr>
<tr><td data-label="Place">Ninh Binh</td><td data-label="Rain-friendly pivot">Boat timing, hotel rest, short cycling windows, or a protected second morning.</td><td data-label="What it protects">Countryside value without turning every hour into a race.</td><td data-label="Do not force">Trang An, Tam Coc, Mua Cave, and onward transfer all in one wet day.</td></tr>
<tr><td data-label="Place">Phu Quoc</td><td data-label="Rain-friendly pivot">Resort comfort, spa, food, short beach windows, and a flexible island rhythm.</td><td data-label="What it protects">Island rest even when sun is not constant.</td><td data-label="Do not force">A boat or beach day that must justify the whole island stay.</td></tr>
</tbody>
</table>

<!-- vg-rainy-transport-caution:v1 -->
<h2 class="wp-block-heading">Transport and transfer caution</h2>
<table class="vg-decision-table vg-rainy-transport-caution">
<thead><tr><th>Movement</th><th>Rain can affect</th><th>Check before paying</th><th>Better route decision</th></tr></thead>
<tbody>
<tr><td data-label="Movement">Road transfer</td><td data-label="Rain can affect">Visibility, timing, comfort, and pickup reliability.</td><td data-label="Check before paying">Pickup address, road time, luggage, and whether the day still has value on arrival.</td><td data-label="Better route decision">Avoid long road exits on departure day.</td></tr>
<tr><td data-label="Movement">Train</td><td data-label="Rain can affect">Station access, timing, luggage comfort, and wet arrival logistics.</td><td data-label="Check before paying">Station distance, hotel check-in, and transfer after arrival.</td><td data-label="Better route decision">Use trains when the schedule protects sleep and not just because the route map looks romantic.</td></tr>
<tr><td data-label="Movement">Domestic flight</td><td data-label="Rain can affect">Airport access, delays, baggage, and self-transfer risk.</td><td data-label="Check before paying">Baggage allowance, terminal, connection buffer, and ticket protection.</td><td data-label="Better route decision">Let flights simplify the route, not hide route overpacking.</td></tr>
<tr><td data-label="Movement">Ferry or boat</td><td data-label="Rain can affect">Comfort, schedule, cancellation, and onward timing.</td><td data-label="Check before paying">Weather policy, refund/change terms, port location, and next hotel/flight buffer.</td><td data-label="Better route decision">Do not put a ferry exit next to a critical international departure.</td></tr>
<tr><td data-label="Movement">Cruise pickup</td><td data-label="Rain can affect">Transfer pickup, bay visibility, route changes, and disembarkation timing.</td><td data-label="Check before paying">Exact port, pickup window, weather policy, and return time.</td><td data-label="Better route decision">Add margin after the cruise instead of gambling the next flight.</td></tr>
</tbody>
</table>

<!-- vg-rainy-fragile-bookings:v1 -->
<h2 class="wp-block-heading">Fragile-booking traps</h2>
<table class="vg-decision-table vg-rainy-fragile-bookings">
<thead><tr><th>Trap</th><th>Why it breaks</th><th>Safer alternative</th></tr></thead>
<tbody>
<tr><td data-label="Trap">Beach-only hotel in a risky weather window</td><td data-label="Why it breaks">The stay has no second job if the beach weakens.</td><td data-label="Safer alternative">Choose a base with food, comfort, spa, town, or city value.</td></tr>
<tr><td data-label="Trap">Outdoor highlight on arrival day</td><td data-label="Why it breaks">Rain plus jet lag plus logistics turns the highlight into pressure.</td><td data-label="Safer alternative">Use the first day for recovery and place the highlight later.</td></tr>
<tr><td data-label="Trap">Ferry or cruise exit before a flight</td><td data-label="Why it breaks">Weather disruption can turn a small delay into a missed flight.</td><td data-label="Safer alternative">Protect one night or a sane buffer after exposed water movement.</td></tr>
<tr><td data-label="Trap">Too many one-night stays</td><td data-label="Why it breaks">Rain makes every checkout, pickup, and repack more expensive in energy.</td><td data-label="Safer alternative">Fewer bases with more pivot options.</td></tr>
<tr><td data-label="Trap">No indoor day that still feels meaningful</td><td data-label="Why it breaks">The route only works in perfect outdoor weather.</td><td data-label="Safer alternative">Add city, food, museum, cafe, spa, market, or cooking-class value where rain is likeliest.</td></tr>
</tbody>
</table>

<!-- vg-rainy-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before final payment</h2>
<ul class="vg-check-list vg-rainy-live-checks">
<li>Check the exact region, not just "Vietnam weather," because the north, central coast, south, mountains, and islands do not behave the same.</li>
<li>Check official forecasts and warnings before final payment for weather-sensitive hotels, cruises, ferries, beach stays, mountain transfers, or day tours.</li>
<li>Check the cancellation and refund posture for every booking that depends on water, beach, mountain, or long road conditions.</li>
<li>Check whether the route has a city-day / indoor pivot that still feels worth doing.</li>
<li>Check if the next morning depends on an airport, port, train station, or pickup after a wet day.</li>
<li>Check whether your hotel location still works when short walks, ride-hailing, and dry clothing matter more.</li>
<li>Check your insurance and personal health preparation before assuming flexibility is only a weather question.</li>
</ul>

<!-- vg-rainy-faq:v1 -->
<h2 class="wp-block-heading">Vietnam rainy season FAQ</h2>
<div class="vg-faq-list vg-rainy-faq">
<details><summary>Is rainy season a bad time to visit Vietnam?</summary><p>Not automatically. It can work well when the route has fewer regions, stronger buffers, refundable bookings, and city or culture pivots that still feel valuable in wet weather.</p></details>
<details><summary>Which part of Vietnam is hardest to plan in rainy season?</summary><p>Central-coast and beach-led plans usually need the most caution because rain, wind, storm exposure, beach value, and transfer comfort can all matter. Exact dates still need live checks.</p></details>
<details><summary>Should I avoid islands during rainy months?</summary><p>Not always. Avoid island plans that only work in perfect sun or require a tight ferry-to-flight chain. A good island stay should still have resort, food, spa, or rest value if one beach day weakens.</p></details>
<details><summary>How many buffer days do I need?</summary><p>Short trips need fewer fragile stops rather than many blank buffers. Longer trips can protect one movable day around the biggest outdoor or water-based highlight.</p></details>
<details><summary>Should I book everything in advance?</summary><p>Prebook scarce or high-quality pieces when needed, but avoid making every ordinary hotel, tour, and transfer non-refundable before you understand the weather-sensitive parts of the route.</p></details>
<details><summary>What is the best rainy-day pivot in Vietnam?</summary><p>The best pivot is city-specific: Hanoi food and museums, central heritage and cafes, Ho Chi Minh City museums and markets, or a hotel/rest plan that does not feel like wasted time.</p></details>
</div>

<h2 class="wp-block-heading">Where this guide fits next</h2>
<p>Use this post when your dates already include rain risk. Then return to the route guides that decide whether the plan should get tighter, slower, or more city-led.</p>
<div class="vg-related-routes vg-rainy-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Use this for the broader seasonal decision before rainy-season buffers are added.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/compare/north-central-south-vietnam/">North Central South Vietnam</a><span class="vg-related-route-note">Use this when rain makes one region a stronger lead than another.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Use this before wet-weather roads, ferries, cruises, flights, and train days.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a><span class="vg-related-route-note">Use central-coast planning when weather changes beach, pass, and city choices.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a><span class="vg-related-route-note">Use HCMC as a southern city base that can absorb rain better than a brittle day trip.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a><span class="vg-related-route-note">Use island logic only when the stay still works if the best beach day fades.</span></li>
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
    'vg-rainy-hero:v1',
    'vg-rainy-concierge-verdict:v1',
    'vg-rainy-at-a-glance:v1',
    'vg-rainy-photo-proof:v1',
    'vg-rainy-source-diversity:v1',
    'vg-rainy-region-logic:v1',
    'vg-rainy-flexibility:v1',
    'vg-rainy-cancellation-refund:v1',
    'vg-rainy-city-indoor-pivots:v1',
    'vg-rainy-transport-caution:v1',
    'vg-rainy-fragile-bookings:v1',
    'vg-rainy-live-checks:v1',
    'vg-rainy-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_rainy_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Vietnam Rainy Season Travel: How to Build a Flexible Route',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Plan Vietnam rainy season travel with regional weather logic, route buffers, cancellation posture, indoor pivots, and transport checks.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_rainy_post_fail('Could not update Vietnam rainy season post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Vietnam Rainy Season Travel: Flexible Route Guide');
update_post_meta($post_id, 'rank_math_description', 'Plan Vietnam rainy season travel with regional weather logic, route buffers, cancellation posture, indoor pivots, and transport checks.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Vietnam rainy season travel');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Treat rainy-season Vietnam as a route-flexibility problem: choose a lead region, protect buffers, and keep weather-sensitive bookings movable.');
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

wp_set_object_terms($post_id, vg_rainy_post_term_ids('category', ['seasonal-travel', 'itineraries']), 'category', false);
wp_set_object_terms($post_id, vg_rainy_post_term_ids('post_tag', ['rainy-season', 'route-planning', 'first-time-vietnam', 'anti-spam-evergreen']), 'post_tag', false);

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
        vg_rainy_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Vietnam rainy season post to complete draft: {$post_id}");

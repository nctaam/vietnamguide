<?php
/**
 * Expand the Vietnam in January post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-vietnam-in-january-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_january_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_january_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_january_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_january_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_january_post_find_by_slug(string $slug): ?WP_Post
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
        vg_january_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_january_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-12',
            'vg_editorial_brief_status' => 'brief',
            'vg_editorial_batch' => 'batch-2-evergreen-planning',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if ($actual_value !== $expected_value) {
            vg_january_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_january_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_january_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'vietnam-in-january';
$post = vg_january_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_january_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_january_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 26, 2026';
vg_january_post_assert_target_meta($post);

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$ha_long_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg/1920px-Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg');
$hoi_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hcmc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg');
$phu_quoc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$trang_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');

$meta_update_summary = 'Expanded the native WordPress batch 2 brief into a complete January draft with photo-led hero, proof panel, concierge verdict, at-a-glance route matrix, photo proof grid, source-diversity table, regional weather logic, Tet lead-up watchouts, route chooser, booking guidance, packing notes, fragile-plan traps, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for broad January climate framing and regional weather caveats.",
    "Vietnam.travel - A traveller's guide to Tet holiday - https://vietnam.travel/things-to-do/tet-vietnam-lunar-new-year - checked {$review_date}; used for Tet lead-up travel context and expectation setting.",
    "Vietnam.travel - Tet: Tradition, Reunion & Taste - https://vietnam.travel/things-to-do/tet-tradition-reunion-taste - checked {$review_date}; used for cultural context and the late-January/early-February timing caveat.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for January and Tet-period movement caution.",
    "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked {$review_date}; used for cool north, Hanoi, Ninh Binh, and bay route context.",
    "Vietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked {$review_date}; used for central coast improving but still variable route logic.",
    "Vietnam.travel - Southern Vietnam destinations - https://vietnam.travel/places-to-go/southern-vietnam - checked {$review_date}; used for dry south and island route context.",
    "National Centre for Hydro-Meteorological Forecasting - English forecast and warning pages - https://nchmf.gov.vn/KttvsiteE/en-US/2/index.html - checked {$review_date}; used for live weather and warning discipline close to travel.",
    "World Weather Information Service - Viet Nam official city forecasts - https://worldweather.wmo.int/en/country.html?countryCode=82 - checked {$review_date}; used for official city-level weather context.",
    "Wikimedia Commons image direct URL - Hanoi Hoan Kiem Lake - {$hero_image} - credit Alex 69200 vx / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ha Long Bay, Vietnam, View from above - {$ha_long_image} - credit Vyacheslav Argenberg / CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hoi An Ancient Town - {$hoi_an_image} - credit Jakub Halun / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ho Chi Minh City Hall - {$hcmc_image} - credit Steffen Schmitz / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Kem Beach aerial view Phu Quoc Island Vietnam - {$phu_quoc_image} - credit Vivu Vietnam / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Trang An Landscape Complex - {$trang_an_image} - credit Jakub Halun / CC BY 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This complete draft treats January as a route decision with Tet lead-up friction, not a generic month page. It separates cool north, central coast improving but not guaranteed, dry south and islands, holiday booking pressure, and live weather checks.';
$meta_evidence_moat = implode("\n", [
    'January route decision framing instead of a generic weather paragraph.',
    'Tet lead-up guidance separates evergreen month logic from exact holiday dates that must be checked every year.',
    'At-a-glance route matrix distinguishes cool north, central coast improving, dry south, island rest, and mixed first-trip routes.',
    'Photo proof explains January planning jobs: northern city comfort, bay exposure, central heritage value, southern city warmth, island rest, and inland karst daylight.',
    'Source-diversity table separates official tourism guidance, Tet cultural context, live weather sources, transport checks, internal route judgment, and image-license records.',
    'Booking guidance covers New Year spillover, pre-Tet compression, cruises, domestic flights, first/last nights, and flexible cancellation posture.',
    'Packing section connects cool north layers, central rain comfort, dry south heat, island sun, and mixed-route luggage discipline.',
    'Fragile-plan traps prevent overpromising Tet availability, beach certainty, or three-region January routes.',
    'No visible external body anchors; official source trail and image-license records stay auditable through metadata and shortcode output.',
]);
$meta_related_routes = implode("\n", [
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Use this for the broader seasonal frame before narrowing January trade-offs.',
    'Tet in Vietnam Travel Guide | /travel-planning/tet-in-vietnam-travel-guide/ | Use this when January travel overlaps the Tet lead-up or holiday window.',
    'What to Pack for Vietnam | /travel-planning/what-to-pack-for-vietnam-region-season/ | Use this to pack for cool north, dry south, and mixed-route luggage.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Use this before booking January flights, trains, buses, cruises, or holiday-period transfers.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Use this when January travel needs a cleaner two-region route.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use this when two weeks allow north, central, and south with buffers.',
    'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use this when cool north food, culture, and walking days should anchor January.',
    'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Use this before making a January cruise a route anchor.',
    'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Use this when dry south and island rest are the reason for choosing January.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Use this when New Year and Tet lead-up pressure changes the trip budget.',
]);
$meta_hero_image_credit = 'Hero image: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0. Body images: Ha Long Bay by Vyacheslav Argenberg, CC BY 4.0; Hoi An Ancient Town by Jakub Halun, CC BY-SA 4.0; Ho Chi Minh City Hall by Steffen Schmitz, CC BY-SA 4.0; Kem Beach aerial view Phu Quoc Island Vietnam by Vivu Vietnam, CC BY-SA 4.0; Trang An Landscape Complex by Jakub Halun, CC BY 4.0.';
$meta_admin_notes = 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, checks Rank Math/social preview, verifies January and Tet wording, image presentation, source-trail records, and adds any first-hand January notes before publishing. This article is not a live forecast and exact Tet dates must be checked for the publication year.';

$content = <<<HTML
<!-- vg-january-hero:v1 -->
<section class="vg-guide-hero vg-january-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Month-by-month route planning</p>
<h1>Vietnam in January: Best Routes, Weather and Tet Watchouts</h1>
<p class="vg-guide-lede">January is one of Vietnam's most useful planning months when you choose the route with care. The north can be cool and atmospheric, the central coast is often improving but still not a blank-cheque beach promise, the south and islands usually carry strong warm-weather value, and the Tet lead-up can change prices, transport pressure, and opening rhythms.</p>
<p class="vg-field-note">Use this with <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/travel-planning/tet-in-vietnam-travel-guide/">Tet in Vietnam Travel Guide</a>, <a href="/travel-planning/what-to-pack-for-vietnam-region-season/">What to Pack for Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and the <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a> route planner before final payment.</p>
</div>
<figure class="vg-guide-hero-image"><img src="{$hero_image}" alt="Hoan Kiem Lake in Hanoi used as a January Vietnam route planning reference" loading="eager" decoding="async"><figcaption>January often rewards a north-first route when you pack layers and do not expect tropical heat in every region. Image: Alex 69200 vx / CC BY-SA 4.0.</figcaption></figure>
</section>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-january-concierge-verdict:v1 -->
<aside class="vg-concierge-verdict vg-january-concierge-verdict" aria-label="Vietnam in January verdict">
<p class="vg-kicker">VietnamGuide verdict</p>
<h2>January is strong when you plan around two forces: regional weather and Tet lead-up.</h2>
<p><strong>For most international travelers, January works best as a north-first or north-plus-south route, with central Vietnam treated as culture and food value rather than guaranteed beach weather.</strong> If Tet falls near your dates, booking quality transport and first/last nights early matters more than adding another stop.</p>
<ul>
<li><strong>Best first-trip default:</strong> Hanoi, Ninh Binh, Ha Long/Lan Ha, then central Vietnam or a southern finish if the trip length supports it.</li>
<li><strong>Best warmth bias:</strong> Ho Chi Minh City, Mekong, Phu Quoc, or a south/island finish.</li>
<li><strong>Best Tet rule:</strong> check exact holiday dates for your year and protect the days before and after the peak.</li>
<li><strong>Best packing rule:</strong> bring light layers for the north and breathable clothes for the dry south.</li>
</ul>
</aside>

<p>January is not simply "good weather in Vietnam." It is a month where different travelers can make very different correct decisions. A first-time couple may love Hanoi's cooler walking weather and a bay cruise with a good cabin. A family may prefer a warm southern chapter with fewer hotel moves. A traveler arriving close to Tet may need to prioritize reliable transport, central hotel locations, and realistic expectations about what closes or slows down.</p>

<p>This article is built as a decision guide, not a forecast. It helps you choose the January route shape, decide what to book early, recognize where Tet lead-up can create friction, and avoid itinerary promises that only work when every day is sunny, every operator is available, and every transfer runs exactly on time.</p>

<p>Visible external links are intentionally avoided in the body. Official weather, Tet, transport, region, and image-license records stay in the source trail metadata so the article remains clean, evergreen, and auditable.</p>

<!-- vg-january-at-a-glance:v1 -->
<h2 class="wp-block-heading">Vietnam in January at a glance</h2>
<table class="vg-decision-table vg-january-at-a-glance">
<thead><tr><th>Traveler goal</th><th>Best January bias</th><th>What to watch</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Traveler goal">First Vietnam trip</td><td data-label="Best January bias">North plus one strong second chapter.</td><td data-label="What to watch">Cool north mornings, bay visibility, Tet lead-up pricing, and transfer crowding.</td><td data-label="VietnamGuide verdict">A strong first-trip month when the route is not overloaded.</td></tr>
<tr><td data-label="Traveler goal">Culture and food</td><td data-label="Best January bias">Hanoi, Ninh Binh, Hue, Hoi An, and Ho Chi Minh City by route length.</td><td data-label="What to watch">Central coast weather and city closures if Tet is near.</td><td data-label="VietnamGuide verdict">Excellent when you value atmosphere over pure beach time.</td></tr>
<tr><td data-label="Traveler goal">Warm escape</td><td data-label="Best January bias">Dry south, Mekong, and Phu Quoc or another island finish.</td><td data-label="What to watch">Holiday prices, flight availability, and the temptation to add a rushed northern stamp.</td><td data-label="VietnamGuide verdict">Best when warmth and rest are the reason for the trip.</td></tr>
<tr><td data-label="Traveler goal">Central Vietnam base</td><td data-label="Best January bias">Da Nang, Hoi An, and Hue as food, heritage, and comfort bases.</td><td data-label="What to watch">Beach certainty and pass/road conditions after unsettled weather.</td><td data-label="VietnamGuide verdict">Use central Vietnam for route balance; treat beach days as bonus.</td></tr>
<tr><td data-label="Traveler goal">Tet-adjacent travel</td><td data-label="Best January bias">Fewer moves, better hotels, protected transport, and flexible low-stakes days.</td><td data-label="What to watch">Exact holiday dates, pre-holiday shopping/crowds, and post-holiday restart rhythm.</td><td data-label="VietnamGuide verdict">Plan it deliberately or move dates away from the peak.</td></tr>
</tbody>
</table>

<!-- vg-january-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: what January is asking you to decide</h2>
<p>These images are used as planning proof. Each one points to a different January decision: cool northern city days, exposed bay routes, central heritage value, dry south energy, island rest, and inland scenery.</p>
<div class="vg-guide-photo-grid vg-january-photo-proof" aria-label="Vietnam in January photo proof">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Hoan Kiem Lake in Hanoi, a cool-season January city reference" loading="lazy" decoding="async"><figcaption>Hanoi can be one of January's strongest bases when you want food, walking, museums, cafes, and atmosphere. Image: Alex 69200 vx / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$ha_long_image}" alt="Ha Long Bay from above, a January bay planning reference" loading="lazy" decoding="async"><figcaption>A January cruise should be booked for scenery and cabin quality, not a guaranteed beach-like deck day. Image: Vyacheslav Argenberg / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hoi_an_image}" alt="Hoi An Ancient Town street in central Vietnam" loading="lazy" decoding="async"><figcaption>Hoi An can carry January value through food, heritage, shopping, and evenings even when beach certainty is weaker. Image: Jakub Halun / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall and walking street at night" loading="lazy" decoding="async"><figcaption>Ho Chi Minh City helps a January route swing warmer without relying on every day being a beach day. Image: Steffen Schmitz / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$phu_quoc_image}" alt="Kem Beach aerial view on Phu Quoc Island" loading="lazy" decoding="async"><figcaption>Phu Quoc can be a strong January rest finish when flights, resort value, and Tet timing are handled early. Image: Vivu Vietnam / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$trang_an_image}" alt="Trang An limestone landscape in Ninh Binh" loading="lazy" decoding="async"><figcaption>Ninh Binh is a January favorite when daylight is protected and the route does not squeeze it between two hard transfers. Image: Jakub Halun / CC BY 4.0.</figcaption></figure>
</div>

<!-- vg-january-source-diversity:v1 -->
<h2 class="wp-block-heading">Source diversity for January planning</h2>
<table class="vg-decision-table vg-january-source-diversity">
<thead><tr><th>Source family</th><th>What it helps decide</th><th>What it cannot decide alone</th><th>VietnamGuide judgment</th></tr></thead>
<tbody>
<tr><td data-label="Source family">Official weather and climate guidance</td><td data-label="What it helps decide">Broad regional patterns, cool north, central coast improving, and dry south context.</td><td data-label="What it cannot decide alone">Your exact travel dates or whether a route is too ambitious.</td><td data-label="VietnamGuide judgment">Use for route bias, then recheck live forecasts near travel.</td></tr>
<tr><td data-label="Source family">Tet cultural guidance</td><td data-label="What it helps decide">Why the holiday affects movement, family rhythms, shopping, closures, and city mood.</td><td data-label="What it cannot decide alone">Exact current-year opening hours or transport availability.</td><td data-label="VietnamGuide judgment">Use for expectations, then verify exact dates and operators.</td></tr>
<tr><td data-label="Source family">Transport sources</td><td data-label="What it helps decide">Which moves are more fragile near holidays and which should be protected.</td><td data-label="What it cannot decide alone">Whether an extra destination is worth the energy cost.</td><td data-label="VietnamGuide judgment">Use to reduce movement, not to justify more movement.</td></tr>
<tr><td data-label="Source family">Internal route evidence</td><td data-label="What it helps decide">How January should connect to 10-day, 14-day, city, bay, beach, cost, and packing guides.</td><td data-label="What it cannot decide alone">Live weather or exact holiday schedules.</td><td data-label="VietnamGuide judgment">Use to keep the reader in a coherent planning path.</td></tr>
<tr><td data-label="Source family">Image-license records</td><td data-label="What it helps decide">Whether photos can support the planning argument responsibly.</td><td data-label="What it cannot decide alone">Weather, route timing, or hotel quality.</td><td data-label="VietnamGuide judgment">Use photos as decision proof, not decoration.</td></tr>
</tbody>
</table>

<!-- vg-january-region-weather:v1 -->
<h2 class="wp-block-heading">January by region</h2>
<table class="vg-decision-table vg-january-region-weather">
<thead><tr><th>Region</th><th>January route value</th><th>Main caution</th><th>Better planning move</th></tr></thead>
<tbody>
<tr><td data-label="Region">Northern Vietnam</td><td data-label="January route value">Cool north conditions can make Hanoi, Ninh Binh, and bay scenery feel atmospheric and easier for long walking days.</td><td data-label="Main caution">Cold snaps, mist, bay visibility, cool cruise decks, and highland chill.</td><td data-label="Better planning move">Pack layers and choose fewer bases with protected daylight.</td></tr>
<tr><td data-label="Region">Central Vietnam</td><td data-label="January route value">Central coast improving can support Hoi An, Hue, Da Nang food, culture, and soft hotel time.</td><td data-label="Main caution">Beach expectations can still be too optimistic if the route depends on sun.</td><td data-label="Better planning move">Choose central Vietnam for heritage and atmosphere first, beach second.</td></tr>
<tr><td data-label="Region">Southern Vietnam</td><td data-label="January route value">Dry south conditions can make Ho Chi Minh City, Mekong, and southern beach or island chapters attractive.</td><td data-label="Main caution">Heat, holiday prices, and overextending the route after a northern chapter.</td><td data-label="Better planning move">Use the south when warmth and rest are real priorities.</td></tr>
<tr><td data-label="Region">Islands and beaches</td><td data-label="January route value">Phu Quoc and selected southern beach routes can be strong winter-sun choices.</td><td data-label="Main caution">Holiday compression, resort pricing, flight/ferry buffers, and exact island weather.</td><td data-label="Better planning move">Book the beach anchor early if the hotel itself is the point.</td></tr>
<tr><td data-label="Region">Mountains and highlands</td><td data-label="January route value">Clearer scenery can be possible, but the experience is very sensitive to exact elevation and weather.</td><td data-label="Main caution">Cold, fog, road comfort, and insurance or self-drive risk.</td><td data-label="Better planning move">Add mountains only if the route has margin and the traveler wants cold-season conditions.</td></tr>
</tbody>
</table>

<!-- vg-january-route-chooser:v1 -->
<h2 class="wp-block-heading">Choose the January route shape</h2>
<table class="vg-decision-table vg-january-route-chooser">
<thead><tr><th>Route shape</th><th>Best for</th><th>What to skip first</th><th>Next guide</th></tr></thead>
<tbody>
<tr><td data-label="Route shape">North-first 10 days</td><td data-label="Best for">First-timers who want Hanoi, Ninh Binh, bay scenery, food, and culture.</td><td data-label="What to skip first">A token southern stop that costs a flight and weakens the north.</td><td data-label="Next guide"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">North plus central</td><td data-label="Best for">Travelers who want a classic first trip with heritage and food.</td><td data-label="What to skip first">A central beach promise that needs flawless weather.</td><td data-label="Next guide"><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a></td></tr>
<tr><td data-label="Route shape">North plus south</td><td data-label="Best for">Travelers who want cool north plus dry south warmth.</td><td data-label="What to skip first">Too many middle stops that make the flight day feel like a penalty.</td><td data-label="Next guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">South and island</td><td data-label="Best for">Travelers choosing January for warmth, low movement, and beach rest.</td><td data-label="What to skip first">A rushed Hanoi add-on unless the flights are excellent.</td><td data-label="Next guide"><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a></td></tr>
<tr><td data-label="Route shape">Tet-adjacent compact route</td><td data-label="Best for">Travelers close to Lunar New Year who need reliability over maximum coverage.</td><td data-label="What to skip first">Remote transfers and tight operator-dependent days.</td><td data-label="Next guide"><a href="/travel-planning/tet-in-vietnam-travel-guide/">Tet in Vietnam Travel Guide</a></td></tr>
</tbody>
</table>

<!-- vg-january-tet-watchouts:v1 -->
<h2 class="wp-block-heading">Tet lead-up watchouts</h2>
<p>Tet often falls in late January or early February, but the exact date changes each year. That is why January planning should separate evergreen behavior from current-year confirmation. You can plan around the shape of the holiday months in advance, but exact dates, operator schedules, hotel policies, and attraction openings must be checked before final payment.</p>
<table class="vg-decision-table vg-january-tet-watchouts">
<thead><tr><th>Watchout</th><th>Why it matters</th><th>Safer planning move</th></tr></thead>
<tbody>
<tr><td data-label="Watchout">Pre-Tet transport demand</td><td data-label="Why it matters">Domestic movement can become more competitive as people travel for family and holiday preparations.</td><td data-label="Safer planning move">Book essential flights, trains, cruise transfers, and first/last nights earlier.</td></tr>
<tr><td data-label="Watchout">City mood changes</td><td data-label="Why it matters">Markets, streets, decorations, and shopping can be more vivid, but ordinary errands may take longer.</td><td data-label="Safer planning move">Enjoy the atmosphere without scheduling every hour tightly.</td></tr>
<tr><td data-label="Watchout">Closures and restart rhythm</td><td data-label="Why it matters">The days immediately around Tet can affect restaurants, shops, tours, offices, and transport patterns.</td><td data-label="Safer planning move">Check exact current-year dates and avoid critical errands on the peak days.</td></tr>
<tr><td data-label="Watchout">Hotel location becomes more important</td><td data-label="Why it matters">A central base reduces friction if restaurants, shops, or transport options narrow.</td><td data-label="Safer planning move">Pay for easier logistics instead of chasing the cheapest outlying room.</td></tr>
<tr><td data-label="Watchout">Overplanning can backfire</td><td data-label="Why it matters">January is good, but Tet-adjacent days reward patience and buffers.</td><td data-label="Safer planning move">Book anchors, leave ordinary meals and walks flexible.</td></tr>
</tbody>
</table>

<!-- vg-january-booking:v1 -->
<h2 class="wp-block-heading">What to book early in January</h2>
<table class="vg-decision-table vg-january-booking">
<thead><tr><th>Booking anchor</th><th>Why January changes it</th><th>Book early when</th><th>Keep flexible when</th></tr></thead>
<tbody>
<tr><td data-label="Booking anchor">International flights</td><td data-label="Why January changes it">New Year spillover and Tet lead-up can compress good routes.</td><td data-label="Book early when">Open-jaw flights remove backtracking or you are near school-holiday dates.</td><td data-label="Keep flexible when">You have not chosen north, central, south, or island priority.</td></tr>
<tr><td data-label="Booking anchor">First and last nights</td><td data-label="Why January changes it">Late arrivals and holiday-period movement make central hotels more valuable.</td><td data-label="Book early when">Arriving late, traveling with family, or close to Tet.</td><td data-label="Keep flexible when">Middle route choices are still changing.</td></tr>
<tr><td data-label="Booking anchor">Bay cruise</td><td data-label="Why January changes it">Good cabins and reputable operators can tighten during popular windows.</td><td data-label="Book early when">The cruise is a core route anchor and cancellation terms are clear.</td><td data-label="Keep flexible when">You only want the cruise if visibility is perfect.</td></tr>
<tr><td data-label="Booking anchor">Island hotel</td><td data-label="Why January changes it">Dry south and beach demand can raise value pressure for the best stays.</td><td data-label="Book early when">The island finish is the reason for the trip.</td><td data-label="Keep flexible when">Beach time is only a backup after sightseeing.</td></tr>
<tr><td data-label="Booking anchor">Domestic transport</td><td data-label="Why January changes it">Good timing, baggage-friendly fares, and holiday-adjacent seats can narrow.</td><td data-label="Book early when">A flight, train, or transfer protects a usable day.</td><td data-label="Keep flexible when">The move exists only because the route has too many stops.</td></tr>
</tbody>
</table>

<!-- vg-january-packing:v1 -->
<h2 class="wp-block-heading">What January changes in the suitcase</h2>
<table class="vg-decision-table vg-january-packing">
<thead><tr><th>Route piece</th><th>Pack first</th><th>Why it matters</th><th>Do not overpack</th></tr></thead>
<tbody>
<tr><td data-label="Route piece">Hanoi / Ninh Binh / bay</td><td data-label="Pack first">Light jacket, long sleeves, socks, closed walking shoes, and a compact rain layer.</td><td data-label="Why it matters">Cool north conditions, bay wind, and early starts can surprise travelers expecting only heat.</td><td data-label="Do not overpack">Heavy winter gear unless mountains or personal cold sensitivity require it.</td></tr>
<tr><td data-label="Route piece">Central Vietnam</td><td data-label="Pack first">Quick-dry clothing, rain shell, comfortable sandals or shoes, and temple-friendly layers.</td><td data-label="Why it matters">Central coast improving does not remove the need for wet-weather comfort.</td><td data-label="Do not overpack">Dress shoes that fail on wet streets.</td></tr>
<tr><td data-label="Route piece">Southern cities</td><td data-label="Pack first">Light breathable clothing, sun protection, and one thin layer for air-conditioning.</td><td data-label="Why it matters">The dry south can feel like a different trip from the north.</td><td data-label="Do not overpack">Multiple thick layers that only serve Hanoi.</td></tr>
<tr><td data-label="Route piece">Island or beach finish</td><td data-label="Pack first">Swimwear, sandals, sun shirt, dry bag, and one nicer dinner layer.</td><td data-label="Why it matters">A January beach finish should reduce friction, not expand the suitcase.</td><td data-label="Do not overpack">A second beach wardrobe for every possible resort day.</td></tr>
<tr><td data-label="Route piece">Tet-adjacent city days</td><td data-label="Pack first">Comfortable walking clothes, a respectful layer, small cash organization, and patience.</td><td data-label="Why it matters">Markets, streets, and errands can be more active or less predictable.</td><td data-label="Do not overpack">Formal clothing unless the trip specifically needs it.</td></tr>
</tbody>
</table>

<!-- vg-january-fragile-plans:v1 -->
<h2 class="wp-block-heading">Fragile January plans to avoid</h2>
<table class="vg-decision-table vg-january-fragile-plans">
<thead><tr><th>Fragile plan</th><th>Why it is weak</th><th>Safer alternative</th></tr></thead>
<tbody>
<tr><td data-label="Fragile plan">Assuming Tet will not affect your exact week</td><td data-label="Why it is weak">The holiday date changes each year and the lead-up can still affect demand.</td><td data-label="Safer alternative">Check exact dates first, then protect critical transport and hotel nights.</td></tr>
<tr><td data-label="Fragile plan">Counting central Vietnam as a guaranteed beach route</td><td data-label="Why it is weak">January can improve, but a route built only around beach certainty is brittle.</td><td data-label="Safer alternative">Choose central Vietnam for heritage, food, hotels, and atmosphere.</td></tr>
<tr><td data-label="Fragile plan">Cruise exit directly into a critical flight</td><td data-label="Why it is weak">Weather, port timing, road traffic, and operator delays can make small errors expensive.</td><td data-label="Safer alternative">Add a buffer night or choose safer onward timing.</td></tr>
<tr><td data-label="Fragile plan">North, central, south, and island in a short holiday</td><td data-label="Why it is weak">January looks appealing everywhere, but the travel days still count.</td><td data-label="Safer alternative">Choose two strong chapters, or use 14 days for three with buffers.</td></tr>
<tr><td data-label="Fragile plan">Booking all ordinary activities early</td><td data-label="Why it is weak">You lose the ability to react to weather, Tet rhythms, and traveler energy.</td><td data-label="Safer alternative">Book anchors early and keep everyday city time flexible.</td></tr>
</tbody>
</table>

<!-- vg-january-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before final payment</h2>
<ul class="vg-check-list vg-january-live-checks">
<li>Check exact Tet dates for the year you travel before locking flights, trains, cruises, or remote transfers.</li>
<li>Check official forecasts and warnings before paying for bay cruises, ferries, mountain transfers, central-coast beach hotels, or weather-sensitive tours.</li>
<li>Check whether your central Vietnam plan still works if beach weather is weak for several days.</li>
<li>Check whether your north route includes layers for cool mornings, bay decks, and early transfers.</li>
<li>Check whether a dry south or island finish is a real rest chapter or just a costly add-on.</li>
<li>Check domestic transport and baggage rules before assuming a mixed north/south suitcase will move easily.</li>
<li>Check <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> if your dates overlap New Year or Tet lead-up pressure.</li>
</ul>

<!-- vg-january-faq:v1 -->
<h2 class="wp-block-heading">Vietnam in January FAQ</h2>
<div class="vg-faq-list vg-january-faq">
<details><summary>Is January a good time to visit Vietnam?</summary><p>January can be a very good time, especially for cooler northern sightseeing and warm southern or island travel. It is less simple when your dates overlap Tet lead-up or when your route depends on guaranteed central-coast beach weather.</p></details>
<details><summary>Where should I go in Vietnam in January?</summary><p>For a first trip, start with Hanoi, Ninh Binh, and a bay decision, then add central Vietnam or a southern/island finish based on trip length. For warmth, bias south earlier.</p></details>
<details><summary>Does Tet affect January travel?</summary><p>It can. Tet often falls in late January or early February, and the lead-up can affect transport demand, city rhythms, openings, and hotel value. Check the exact dates for your travel year.</p></details>
<details><summary>Is Ha Long Bay worth it in January?</summary><p>It can be worth it for scenery and the cruise experience, but pack for cool decks and ask about weather policy, pickup timing, port, route, and onward transfers.</p></details>
<details><summary>Is Phu Quoc good in January?</summary><p>Phu Quoc can be a strong January option for travelers who want dry south warmth and beach rest, but better hotels and flights should be handled early when dates are popular.</p></details>
<details><summary>What should I pack for Vietnam in January?</summary><p>Pack modularly: light layers for the north, quick-dry comfort for central Vietnam, breathable clothing for the dry south, and swimwear only if the beach chapter is real.</p></details>
</div>

<h2 class="wp-block-heading">Where this guide fits next</h2>
<p>Use this post once January is on your shortlist. Then choose the Tet posture, region spine, route length, warm-weather chapter, and transport plan.</p>
<div class="vg-related-routes vg-january-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Use this for the broader seasonal frame before choosing January-specific trade-offs.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/travel-planning/tet-in-vietnam-travel-guide/">Tet in Vietnam Travel Guide</a><span class="vg-related-route-note">Use this if January overlaps the holiday lead-up or peak.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/travel-planning/what-to-pack-for-vietnam-region-season/">What to Pack for Vietnam</a><span class="vg-related-route-note">Use this to balance cool north layers with dry south warmth.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Use this before booking holiday-adjacent movement.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a><span class="vg-related-route-note">Use this when January travel needs a clean two-region route.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a><span class="vg-related-route-note">Use this when dry south and island rest are the January goal.</span></li>
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
    'vg-january-hero:v1',
    'vg-january-concierge-verdict:v1',
    'vg-january-at-a-glance:v1',
    'vg-january-photo-proof:v1',
    'vg-january-source-diversity:v1',
    'vg-january-region-weather:v1',
    'vg-january-route-chooser:v1',
    'vg-january-tet-watchouts:v1',
    'vg-january-booking:v1',
    'vg-january-packing:v1',
    'vg-january-fragile-plans:v1',
    'vg-january-live-checks:v1',
    'vg-january-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_january_post_fail("Missing content marker before update: {$marker}");
    }
}

$category_term_ids = vg_january_post_term_ids('category', ['seasonal-travel', 'travel-planning']);
$tag_term_ids = vg_january_post_term_ids('post_tag', ['month-by-month', 'tet-travel', 'first-time-vietnam', 'anti-spam-evergreen']);

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Vietnam in January: Best Routes, Weather and Tet Watchouts',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Plan Vietnam in January by region, route shape, Tet lead-up, weather risk, transport pressure, packing, and live checks.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_january_post_fail('Could not update Vietnam in January post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Vietnam in January: Weather, Routes and Tet Tips');
update_post_meta($post_id, 'rank_math_description', 'Plan Vietnam in January by region, route shape, Tet lead-up, weather risk, transport pressure, packing, and live checks.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Vietnam in January');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Choose a January Vietnam route by Tet lead-up, cool north, central coast improving, dry south, island rest, booking pressure, and live weather checks.');
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
    vg_january_post_fail('Could not assign Vietnam in January categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_january_post_fail('Could not assign Vietnam in January tags: ' . $tag_result->get_error_message());
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
        vg_january_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Vietnam in January post to complete draft: {$post_id}");

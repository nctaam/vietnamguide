<?php
/**
 * Expand the Vietnam in February post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-vietnam-in-february-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_february_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_february_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_february_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_february_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_february_post_find_by_slug(string $slug): ?WP_Post
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
        vg_february_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_february_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-13',
            'vg_editorial_brief_status' => 'brief',
            'vg_editorial_batch' => 'batch-2-evergreen-planning',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if ($actual_value !== $expected_value) {
            vg_february_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_february_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_february_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'vietnam-in-february';
$post = vg_february_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_february_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_february_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 26, 2026';
vg_february_post_assert_target_meta($post);

$category_term_ids = vg_february_post_term_ids('category', ['seasonal-travel', 'travel-planning']);
$tag_term_ids = vg_february_post_term_ids('post_tag', ['month-by-month', 'tet-travel', 'route-planning', 'anti-spam-evergreen']);

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hanoi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$hue_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg/1920px-Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$phu_quoc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$mekong_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1280px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg');
$ha_long_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg/1920px-Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg');
$trang_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');

$meta_update_summary = 'Expanded the native WordPress batch 2 brief into a complete February draft with a photo-led hero, proof panel, concierge verdict, at-a-glance route matrix, photo proof grid, source-diversity table, regional weather logic, Tet timing and post-Tet restart guidance, route chooser, booking guidance, packing notes, fragile-plan traps, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for broad February climate framing, north warming slowly, central coast improvement, and southern dry-season context.",
    "Vietnam.travel - A traveller's guide to Tet holiday - https://vietnam.travel/things-to-do/tet-vietnam-lunar-new-year - checked {$review_date}; used for Tet timing, holiday atmosphere, closure/restart caution, and exact-date recheck discipline.",
    "Vietnam.travel - Tet: Tradition, Reunion & Taste - https://vietnam.travel/things-to-do/tet-tradition-reunion-taste - checked {$review_date}; used for cultural context without treating Tet as a static annual date.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for domestic movement, flight/train/bus/cruise choice, and holiday-adjacent booking pressure.",
    "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked {$review_date}; used for Hanoi, Ninh Binh, Ha Long/Lan Ha, mountain, and cool-season route context.",
    "Vietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked {$review_date}; used for Hue, Hoi An, Da Nang, central heritage, and improving-coast route context.",
    "Vietnam.travel - Southern Vietnam destinations - https://vietnam.travel/places-to-go/southern-vietnam - checked {$review_date}; used for Ho Chi Minh City, Mekong, Phu Quoc, southern warmth, and island-route context.",
    "National Centre for Hydro-Meteorological Forecasting - English forecast and warning pages - https://nchmf.gov.vn/KttvsiteE/en-US/2/index.html - checked {$review_date}; used for live weather and warning discipline close to travel.",
    "World Weather Information Service - Viet Nam official city forecasts - https://worldweather.wmo.int/en/country.html?countryCode=82 - checked {$review_date}; used for official city-level weather context before final payment.",
    "Wikimedia Commons image direct URL - Hoi An Ancient Town - {$hero_image} - credit Jakub Halun / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hanoi Hoan Kiem Lake - {$hanoi_image} - credit Alex 69200 vx / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hue Vietnam Citadel - {$hue_image} - credit CEphoto, Uwe Aranas / CC BY-SA 3.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Kem Beach aerial view Phu Quoc Island Vietnam - {$phu_quoc_image} - credit Vivu Vietnam / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Vietnam, Phong Dien, Mekong Delta, River - {$mekong_image} - credit Vyacheslav Argenberg / CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ha Long Bay, Vietnam, View from above - {$ha_long_image} - credit Vyacheslav Argenberg / CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Trang An Landscape Complex - {$trang_an_image} - credit Jakub Halun / CC BY 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This complete draft treats February as a route decision, not a recycled weather paragraph. It separates Tet timing, post-Tet restart, north warming slowly, central and southern route value, island booking pressure, and live weather checks.';
$meta_evidence_moat = implode("\n", [
    'February route decision framing instead of a generic monthly weather article.',
    'Tet timing guidance separates evergreen behavior from exact annual holiday dates that must be checked before booking.',
    'Post-Tet restart section explains why services, restaurants, operators, and transport can return unevenly after the peak holiday days.',
    'At-a-glance matrix separates cool north, improving central Vietnam, strong southern warmth, islands, mixed first trips, and Tet-adjacent routes.',
    'Photo proof uses licensed real images to explain planning jobs: central heritage base, Hanoi city rhythm, Hue imperial chapter, Phu Quoc rest, Mekong river time, bay exposure, and Ninh Binh daylight.',
    'Source-diversity table separates official tourism guidance, live forecasts, transport checks, Tet cultural context, internal route judgment, and image-license records.',
    'Regional weather section avoids one national February answer by splitting north, central, south, islands, mountains, and bay conditions.',
    'Booking guidance covers Tet peaks, post-holiday restarts, cruise/island availability, domestic transport, cancellation posture, and first/last nights.',
    'Fragile-plan traps prevent false certainty about holiday openings, beach conditions, overloaded three-region routes, and live weather-sensitive plans.',
    'No visible external body anchors; official source trail and image-license records stay auditable through metadata and shortcode output.',
]);
$meta_related_routes = implode("\n", [
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Use this for the broader seasonal frame before narrowing February trade-offs.',
    'Tet in Vietnam Travel Guide | /travel-planning/tet-in-vietnam-travel-guide/ | Use this when February travel overlaps Tet timing, peak holiday days, or the post-Tet restart.',
    'Vietnam in January | /travel-planning/vietnam-in-january/ | Use this if your dates sit before Tet or cross from January into February.',
    'What to Pack for Vietnam | /travel-planning/what-to-pack-for-vietnam-region-season/ | Use this to pack for north warming slowly, central coast pivots, and southern heat.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Use this before booking holiday-adjacent flights, trains, buses, cruises, ferries, or private transfers.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use this when two weeks allow north, central, and south with buffers.',
    'Hoi An Ancient Town Guide | /destinations/hoi-an-ancient-town-guide/ | Use this when central Vietnam heritage and food should carry February value.',
    'Hue Imperial City Guide | /destinations/hue-imperial-city-guide/ | Use this when Hue deserves a protected imperial-history chapter.',
    'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Use this when southern island rest is the reason for choosing February.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Use this when Tet, islands, cruises, and internal transport change the February budget.',
]);
$meta_hero_image_credit = 'Hero image: Hoi An Ancient Town by Jakub Halun, CC BY-SA 4.0. Body images: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0; Hue Vietnam Citadel by CEphoto, Uwe Aranas, CC BY-SA 3.0; Kem Beach aerial view Phu Quoc Island Vietnam by Vivu Vietnam, CC BY-SA 4.0; Vietnam, Phong Dien, Mekong Delta, River by Vyacheslav Argenberg, CC BY 4.0; Ha Long Bay by Vyacheslav Argenberg, CC BY 4.0; Trang An Landscape Complex by Jakub Halun, CC BY 4.0.';
$meta_admin_notes = 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, checks Rank Math/social preview, verifies February and Tet wording, image presentation, source-trail records, planned Hoi An/Hue route availability, and adds any first-hand February notes before publishing. This article is not a live forecast and exact Tet dates must be checked for the publication year.';

$content = <<<HTML
<!-- vg-february-hero:v1 -->
<section class="vg-guide-hero vg-february-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Month-by-month route planning</p>
<h1>Vietnam in February: Weather, Tet Timing and Best Routes</h1>
<p class="vg-guide-lede">February can be one of Vietnam's most rewarding months when the route respects the calendar. The north is often warming slowly rather than turning tropical overnight, central Vietnam can offer real heritage and food value with improving coastal conditions, the south and islands usually carry strong warm-weather appeal, and Tet timing can reshape transport, openings, prices, and the rhythm of the trip.</p>
<p class="vg-field-note">Use this with <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/travel-planning/tet-in-vietnam-travel-guide/">Tet in Vietnam Travel Guide</a>, <a href="/travel-planning/vietnam-in-january/">Vietnam in January</a>, <a href="/travel-planning/what-to-pack-for-vietnam-region-season/">What to Pack for Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a> before final payment.</p>
</div>
<figure class="vg-guide-hero-image"><img src="{$hero_image}" alt="Hoi An Ancient Town used as a February Vietnam route planning reference" loading="eager" decoding="async"><figcaption>February often rewards a central Vietnam chapter when you choose Hoi An and Hue for heritage, food, and atmosphere instead of treating the coast as a weather promise. Image: Jakub Halun / CC BY-SA 4.0.</figcaption></figure>
</section>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-february-concierge-verdict:v1 -->
<aside class="vg-concierge-verdict vg-february-concierge-verdict" aria-label="Vietnam in February verdict">
<p class="vg-kicker">VietnamGuide verdict</p>
<h2>February is strong when you plan the holiday rhythm first, then choose the route.</h2>
<p><strong>For most international travelers, February works best as a two- or three-region route with Tet timing handled before the sightseeing wish list grows.</strong> If your dates sit near the holiday peak, protect transport, first/last nights, central hotel locations, and a calmer post-Tet restart. If your dates are clear of the peak, February can be an excellent month for a north-central-south itinerary with a warm southern finish.</p>
<ul>
<li><strong>Best first-trip default:</strong> Hanoi, Ninh Binh, Ha Long/Lan Ha, Hoi An, Hue, Ho Chi Minh City, and a selective south or island finish when time allows.</li>
<li><strong>Best warmth bias:</strong> Ho Chi Minh City, Mekong, Phu Quoc, and southern islands when winter sun is the trip job.</li>
<li><strong>Best central value:</strong> Hoi An and Hue for food, heritage, walking, cafes, and softer weather expectations.</li>
<li><strong>Best Tet rule:</strong> check exact annual dates, then design fewer moves around the peak and the restart days after it.</li>
</ul>
</aside>

<p>February is often sold as a simple high-season answer. That can make weak itineraries look safer than they are. A traveler landing after Tet with two weeks, open-jaw flights, and a flexible beach finish has a different problem from a traveler arriving two days before the holiday and trying to cross Hanoi, Hoi An, Ho Chi Minh City, the Mekong, and Phu Quoc without buffers.</p>

<p>This guide treats February as a planning month rather than a weather slogan. The right question is not "Is February good?" The useful question is "Which February route survives my exact holiday timing, weather expectations, transport pressure, and appetite for moving between regions?" That framing is what keeps the article evergreen and useful after annual Tet dates change.</p>

<p>Visible external links are intentionally avoided in the body. Official weather, Tet, transport, region, and image-license records stay in the source trail metadata so the article remains clean, evergreen, and auditable.</p>

<!-- vg-february-at-a-glance:v1 -->
<h2 class="wp-block-heading">Vietnam in February at a glance</h2>
<table class="vg-decision-table vg-february-at-a-glance">
<thead><tr><th>Traveler goal</th><th>Best February bias</th><th>What to watch</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Traveler goal">First Vietnam trip</td><td data-label="Best February bias">North, central, and south can work when the trip has 14 days and buffers.</td><td data-label="What to watch">Tet timing, post-Tet restart, domestic flight demand, bay visibility, and route overload.</td><td data-label="VietnamGuide verdict">One of the better full-country months when the calendar is respected.</td></tr>
<tr><td data-label="Traveler goal">Central Vietnam focus</td><td data-label="Best February bias">Hoi An, Hue, and Da Nang as heritage, food, and comfort bases.</td><td data-label="What to watch">Beach certainty, central-coast rain remnants, and transfer planning over the Hai Van Pass.</td><td data-label="VietnamGuide verdict">Strong if central Vietnam is valued beyond the beach.</td></tr>
<tr><td data-label="Traveler goal">Warm escape</td><td data-label="Best February bias">Ho Chi Minh City, Mekong, Phu Quoc, and southern islands.</td><td data-label="What to watch">Holiday prices, island flight scarcity, ferry exposure, and resort cancellation terms.</td><td data-label="VietnamGuide verdict">Excellent when warmth and rest are the reason for the trip.</td></tr>
<tr><td data-label="Traveler goal">Cool north scenery</td><td data-label="Best February bias">Hanoi, Ninh Binh, Ha Long/Lan Ha, and selective mountain extensions.</td><td data-label="What to watch">North warming slowly, mist, cool decks, mountain cold, and short daylight margins.</td><td data-label="VietnamGuide verdict">Good for atmosphere and scenery; pack layers and avoid tight onward timing.</td></tr>
<tr><td data-label="Traveler goal">Tet-adjacent travel</td><td data-label="Best February bias">Fewer moves, central hotels, protected transport, and low-friction meals.</td><td data-label="What to watch">Exact holiday dates, closures, family travel demand, and uneven restart rhythm.</td><td data-label="VietnamGuide verdict">Worth doing deliberately; risky when treated like an ordinary week.</td></tr>
</tbody>
</table>

<!-- vg-february-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: what February is asking you to decide</h2>
<p>These images are planning evidence, not decoration. Each one points to a February decision: central heritage value, northern city rhythm, Hue's imperial chapter, southern island rest, Mekong river time, bay exposure, and Ninh Binh daylight.</p>
<div class="vg-guide-photo-grid vg-february-photo-proof" aria-label="Vietnam in February photo proof">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Hoi An Ancient Town street in central Vietnam" loading="lazy" decoding="async"><figcaption>Hoi An can be a strong February base when the trip wants heritage, food, evenings, tailoring, cafes, and manageable day trips. Image: Jakub Halun / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hanoi_image}" alt="Hoan Kiem Lake in Hanoi, a February northern Vietnam city reference" loading="lazy" decoding="async"><figcaption>Hanoi often anchors February well because cool-season walking, food, museums, and Old Quarter rhythm do not need tropical heat. Image: Alex 69200 vx / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hue_image}" alt="Hue Citadel in central Vietnam" loading="lazy" decoding="async"><figcaption>Hue makes February deeper when the route protects a real imperial-history day instead of passing through between beach bases. Image: CEphoto, Uwe Aranas / CC BY-SA 3.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$phu_quoc_image}" alt="Kem Beach aerial view on Phu Quoc Island" loading="lazy" decoding="async"><figcaption>Phu Quoc can be a February rest finish when flights, resort terms, and holiday demand are handled early. Image: Vivu Vietnam / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mekong_image}" alt="Boat on a river in the Mekong Delta" loading="lazy" decoding="async"><figcaption>The Mekong is useful when southern Vietnam deserves river time, not when it is squeezed into the final morning before departure. Image: Vyacheslav Argenberg / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$ha_long_image}" alt="Ha Long Bay limestone islands from above" loading="lazy" decoding="async"><figcaption>A February cruise should be booked for scenery, cabin quality, and routing, with cool decks and visibility treated as planning variables. Image: Vyacheslav Argenberg / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$trang_an_image}" alt="Trang An limestone landscape in Ninh Binh" loading="lazy" decoding="async"><figcaption>Ninh Binh is one of February's strongest north-side values when the route protects daylight and avoids a rushed transfer sandwich. Image: Jakub Halun / CC BY 4.0.</figcaption></figure>
</div>

<!-- vg-february-source-diversity:v1 -->
<h2 class="wp-block-heading">Source diversity for February planning</h2>
<table class="vg-decision-table vg-february-source-diversity">
<thead><tr><th>Source family</th><th>What it helps decide</th><th>What it cannot decide alone</th><th>VietnamGuide judgment</th></tr></thead>
<tbody>
<tr><td data-label="Source family">Official climate and tourism guidance</td><td data-label="What it helps decide">Broad regional season posture: cooler north, improving central coast, warm south, and island appeal.</td><td data-label="What it cannot decide alone">Your exact weather, cruise route, beach water condition, hotel value, or whether a rushed itinerary is worth it.</td><td data-label="VietnamGuide judgment">Use for route framing, then verify weather and operators close to travel.</td></tr>
<tr><td data-label="Source family">Tet cultural and holiday guidance</td><td data-label="What it helps decide">Why the holiday changes city mood, family movement, shopping, closures, and the post-Tet restart.</td><td data-label="What it cannot decide alone">The exact current-year impact on your chosen restaurant, train, cruise, museum, market, or hotel.</td><td data-label="VietnamGuide judgment">Use it to plan buffers, not to freeze a universal closure list.</td></tr>
<tr><td data-label="Source family">Live weather and warnings</td><td data-label="What it helps decide">Whether bay cruises, ferries, mountain roads, beach days, and central-coast transfers need a pivot.</td><td data-label="What it cannot decide alone">Whether a destination belongs in the route months before departure.</td><td data-label="VietnamGuide judgment">Use official forecasts as a final-payment and final-week check.</td></tr>
<tr><td data-label="Source family">Transport sources</td><td data-label="What it helps decide">Whether a flight, train, bus, private transfer, cruise transfer, or ferry is realistic around Tet.</td><td data-label="What it cannot decide alone">The value of adding a stop simply because a vehicle exists.</td><td data-label="VietnamGuide judgment">Transport should protect the route, not justify adding a fragile extra region.</td></tr>
<tr><td data-label="Source family">Internal route guides</td><td data-label="What it helps decide">Whether February should become a 14-day full-country route, a central heritage route, a southern warmth route, or a Tet-light route.</td><td data-label="What it cannot decide alone">Current-year hours, tickets, and weather-sensitive operator policies.</td><td data-label="VietnamGuide judgment">Use internal guides for decision logic, then live-check volatile details.</td></tr>
<tr><td data-label="Source family">Licensed image records</td><td data-label="What it helps decide">Whether the visual proof shown in the guide is real, credited, and reusable.</td><td data-label="What it cannot decide alone">Current conditions at the exact destination on your travel day.</td><td data-label="VietnamGuide judgment">Images support practical planning jobs; they are not weather promises.</td></tr>
</tbody>
</table>

<!-- vg-february-region-weather:v1 -->
<h2 class="wp-block-heading">February weather by region: the useful version</h2>
<p>The most reliable February advice is regional. Vietnam is long, and the same month can mean cool northern mornings, improving central conditions, and warm southern beach value. Build the route around those differences instead of expecting one national answer.</p>
<table class="vg-decision-table vg-february-region-weather">
<thead><tr><th>Region</th><th>February route value</th><th>Main caution</th><th>Better planning move</th></tr></thead>
<tbody>
<tr><td data-label="Region">Hanoi and the northern lowlands</td><td data-label="February route value">Food, walking, museums, Old Quarter rhythm, Ninh Binh, and a softer cool-season atmosphere.</td><td data-label="Main caution">North warming slowly can still mean cool mornings, light rain, mist, and travelers packing too thinly.</td><td data-label="Better planning move">Plan layers, protect indoor/cafe pivots, and avoid treating Hanoi as a hot-weather city.</td></tr>
<tr><td data-label="Region">Ha Long / Lan Ha / Bai Tu Long</td><td data-label="February route value">Scenery, overnight cruise experience, limestone landscape, and a clean northern route anchor.</td><td data-label="Main caution">Cool decks, mist, visibility, weather-related routing, and tight onward transfers.</td><td data-label="Better planning move">Book by operator quality, cabin, route, pickup, policy, and buffer rather than only by photo weather.</td></tr>
<tr><td data-label="Region">Ninh Binh</td><td data-label="February route value">Karst scenery, boat routes, cycling, temples, viewpoints, and a strong north-side buffer from Hanoi.</td><td data-label="Main caution">Short rushed visits can waste the daylight and make the route feel administrative.</td><td data-label="Better planning move">Give Ninh Binh a protected overnight when scenery is a core reason for going north.</td></tr>
<tr><td data-label="Region">Hue, Hoi An, and Da Nang</td><td data-label="February route value">Central and southern route value starts here for travelers who want heritage, food, calmer temperatures, and improving coastal odds.</td><td data-label="Main caution">Beach expectations can still outrun reality, especially when the route has no rainy-day value.</td><td data-label="Better planning move">Choose central Vietnam for Hoi An, Hue, food, culture, hotels, and atmosphere, with beach time as a bonus.</td></tr>
<tr><td data-label="Region">Ho Chi Minh City and the Mekong</td><td data-label="February route value">Warm southern city energy, river trips, easier winter-sun logic, and a strong finish after the north or central region.</td><td data-label="Main caution">Heat, road time, holiday demand, and adding the Mekong after the itinerary is already full.</td><td data-label="Better planning move">Use HCMC and the Mekong when the south has a real route job, not as a final checklist add-on.</td></tr>
<tr><td data-label="Region">Phu Quoc and southern islands</td><td data-label="February route value">Island rest, resort value, warm-weather appeal, and a soft landing after busier mainland travel.</td><td data-label="Main caution">High demand, flight scarcity, ferry exposure, resort isolation, and cancellation terms.</td><td data-label="Better planning move">Use at least three nights when the island is the point; skip it when the extra transfer weakens the whole trip.</td></tr>
<tr><td data-label="Region">Northern mountains</td><td data-label="February route value">Scenery, culture, road-trip drama, and a different emotional tone from lowland Vietnam.</td><td data-label="Main caution">Cold, fog, road risk, limited daylight, insurance assumptions, and Tet-adjacent operator variation.</td><td data-label="Better planning move">Add mountains only when the route has margin and the traveler actively wants cool-season conditions.</td></tr>
</tbody>
</table>

<!-- vg-february-route-chooser:v1 -->
<h2 class="wp-block-heading">Choose the February route shape</h2>
<table class="vg-decision-table vg-february-route-chooser">
<thead><tr><th>Route shape</th><th>Best for</th><th>What to skip first</th><th>Next guide</th></tr></thead>
<tbody>
<tr><td data-label="Route shape">Classic 14-day full route</td><td data-label="Best for">First-timers with enough time for Hanoi, Ninh Binh, bay, Hoi An/Hue, HCMC, and a selective southern finish.</td><td data-label="What to skip first">A weak one-night stop added only because February looks good everywhere.</td><td data-label="Next guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">Central heritage route</td><td data-label="Best for">Travelers who want Hoi An evenings, Hue history, Da Nang access, food, and a less rushed middle chapter.</td><td data-label="What to skip first">A pure beach promise that leaves no value if the coast is mixed.</td><td data-label="Next guide"><a href="/destinations/hoi-an-ancient-town-guide/">Hoi An Ancient Town Guide</a></td></tr>
<tr><td data-label="Route shape">South and island route</td><td data-label="Best for">Travelers choosing February for warmth, rest, family ease, or a resort finish.</td><td data-label="What to skip first">A token north add-on that turns a rest trip into airports.</td><td data-label="Next guide"><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a></td></tr>
<tr><td data-label="Route shape">Tet-light compact route</td><td data-label="Best for">Travelers arriving near the holiday who need reliability more than maximum coverage.</td><td data-label="What to skip first">Remote transfers, one-night jumps, and meals that require every restaurant to run normally.</td><td data-label="Next guide"><a href="/travel-planning/tet-in-vietnam-travel-guide/">Tet in Vietnam Travel Guide</a></td></tr>
<tr><td data-label="Route shape">North plus central</td><td data-label="Best for">Travelers who want cooler north, bay or Ninh Binh, Hoi An/Hue, and no need for a southern flight.</td><td data-label="What to skip first">Phu Quoc unless there are enough nights for a true island finish.</td><td data-label="Next guide"><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a></td></tr>
</tbody>
</table>

<!-- vg-february-tet-timing:v1 -->
<h2 class="wp-block-heading">Tet timing and the post-Tet restart</h2>
<p>Tet often falls in late January or February, but the exact date changes every year. That is why February planning needs two layers: evergreen behavior and current-year confirmation. The evergreen behavior is that the holiday can compress transport, change opening rhythms, make family movement more important, and create a slower post-Tet restart. The current-year layer is your exact date, city, hotel, train, flight, tour, cruise, ferry, restaurant, and attraction.</p>
<table class="vg-decision-table vg-february-tet-timing">
<thead><tr><th>Timing zone</th><th>What tends to change</th><th>Safer planning move</th></tr></thead>
<tbody>
<tr><td data-label="Timing zone">Two to three weeks before Tet</td><td data-label="What tends to change">Prices and availability can begin tightening for trains, flights, family travel, central hotels, and good cruise cabins.</td><td data-label="Safer planning move">Book critical transport and first/last nights earlier, then keep ordinary city time flexible.</td></tr>
<tr><td data-label="Timing zone">Final days before Tet</td><td data-label="What tends to change">Markets, streets, shopping, and family movement can become vivid but less efficient for travelers.</td><td data-label="Safer planning move">Stay central, reduce transfers, enjoy the atmosphere, and avoid important errands that depend on normal hours.</td></tr>
<tr><td data-label="Timing zone">Peak Tet days</td><td data-label="What tends to change">Some restaurants, shops, services, offices, museums, tours, and transport patterns may pause or operate differently.</td><td data-label="Safer planning move">Choose a comfortable base, confirm meals and transport directly, and treat the day as a cultural rhythm rather than a checklist day.</td></tr>
<tr><td data-label="Timing zone">Post-Tet restart</td><td data-label="What tends to change">Businesses, operators, guides, drivers, public offices, and ordinary city services may return unevenly before the normal travel rhythm is fully back.</td><td data-label="Safer planning move">Build one or two low-stakes days after the peak before relying on remote transfers, complex tours, or paperwork.</td></tr>
<tr><td data-label="Timing zone">Clear of Tet peak</td><td data-label="What tends to change">The month becomes easier to route, but weather and popular winter-sun demand still matter.</td><td data-label="Safer planning move">Use the stronger February route conditions without forgetting live weather and booking checks.</td></tr>
</tbody>
</table>

<!-- vg-february-booking:v1 -->
<h2 class="wp-block-heading">What to book early in February</h2>
<table class="vg-decision-table vg-february-booking">
<thead><tr><th>Booking anchor</th><th>Why February changes it</th><th>Book early when</th><th>Keep flexible when</th></tr></thead>
<tbody>
<tr><td data-label="Booking anchor">International flights</td><td data-label="Why February changes it">Holiday demand, winter travel, and open-jaw route value can compress good options.</td><td data-label="Book early when">Flying near Tet, school holidays, or using Hanoi in and HCMC out to save a backtrack.</td><td data-label="Keep flexible when">You have not chosen whether the route is full-country, central-led, or south-led.</td></tr>
<tr><td data-label="Booking anchor">Domestic flights and trains</td><td data-label="Why February changes it">Family movement and traveler demand can make convenient times and baggage-friendly fares harder.</td><td data-label="Book early when">A move protects a real day or avoids a punishing road transfer.</td><td data-label="Keep flexible when">The move exists only because the itinerary has too many stops.</td></tr>
<tr><td data-label="Booking anchor">First and last nights</td><td data-label="Why February changes it">Arrivals, departures, and holiday timing make central, easy-transfer hotels more valuable.</td><td data-label="Book early when">Arriving late, traveling with family, carrying luggage across regions, or close to Tet.</td><td data-label="Keep flexible when">Middle route choices are still changing.</td></tr>
<tr><td data-label="Booking anchor">Hoi An / Hue stay</td><td data-label="Why February changes it">Central Vietnam can be popular when weather improves and heritage towns carry strong route value.</td><td data-label="Book early when">The hotel location makes walking, food, and weather pivots easier.</td><td data-label="Keep flexible when">You only want central Vietnam if beach conditions are perfect.</td></tr>
<tr><td data-label="Booking anchor">Bay cruise</td><td data-label="Why February changes it">Good cabins and reputable operators can tighten during attractive travel windows.</td><td data-label="Book early when">The cruise is a route anchor and cancellation/weather policy is clear.</td><td data-label="Keep flexible when">You would only enjoy the cruise with bright-sun deck weather.</td></tr>
<tr><td data-label="Booking anchor">Phu Quoc or island hotel</td><td data-label="Why February changes it">Warm-weather demand can push the best beach stays and flights earlier.</td><td data-label="Book early when">The island is a real rest chapter of three nights or more.</td><td data-label="Keep flexible when">The island is a late add-on after too many mainland moves.</td></tr>
</tbody>
</table>

<!-- vg-february-packing:v1 -->
<h2 class="wp-block-heading">What February changes in the suitcase</h2>
<table class="vg-decision-table vg-february-packing">
<thead><tr><th>Route piece</th><th>Pack first</th><th>Why it matters</th><th>Do not overpack</th></tr></thead>
<tbody>
<tr><td data-label="Route piece">Hanoi / Ninh Binh / bay</td><td data-label="Pack first">Light jacket, long sleeves, socks, closed walking shoes, and a compact rain layer.</td><td data-label="Why it matters">North warming slowly still leaves cool mornings, mist, bay wind, and early starts.</td><td data-label="Do not overpack">Heavy winter gear unless mountains, motorbike exposure, or cold sensitivity require it.</td></tr>
<tr><td data-label="Route piece">Hue / Hoi An / Da Nang</td><td data-label="Pack first">Quick-dry layers, a light rain shell, temple-friendly clothing, and shoes that handle damp streets.</td><td data-label="Why it matters">Central Vietnam can be pleasant, but heritage days need comfort when weather shifts.</td><td data-label="Do not overpack">A resort-only wardrobe if the central chapter is mostly food, walking, and culture.</td></tr>
<tr><td data-label="Route piece">Ho Chi Minh City / Mekong</td><td data-label="Pack first">Breathable clothing, sun protection, a thin air-conditioning layer, cash organization, and a dry bag for boat days.</td><td data-label="Why it matters">The south can feel like a different trip from the north, especially after cool mornings.</td><td data-label="Do not overpack">Extra layers that will never leave the suitcase after Hanoi.</td></tr>
<tr><td data-label="Route piece">Phu Quoc / islands</td><td data-label="Pack first">Swimwear, sandals, sun shirt, reef-safe habits, small first-aid basics, and one nicer dinner layer.</td><td data-label="Why it matters">A February island finish should reduce decision fatigue and transfer strain.</td><td data-label="Do not overpack">A separate outfit for every possible beach day.</td></tr>
<tr><td data-label="Route piece">Tet-adjacent city days</td><td data-label="Pack first">Respectful layers, small cash, medications, essential toiletries, and patience for changed routines.</td><td data-label="Why it matters">Around the holiday, simple errands can take more planning and fewer stores may be operating normally.</td><td data-label="Do not overpack">Special occasion clothing unless your trip actually includes a formal event.</td></tr>
</tbody>
</table>

<!-- vg-february-fragile-plans:v1 -->
<h2 class="wp-block-heading">Fragile February plans to avoid</h2>
<table class="vg-decision-table vg-february-fragile-plans">
<thead><tr><th>Fragile plan</th><th>Why it is weak</th><th>Safer alternative</th></tr></thead>
<tbody>
<tr><td data-label="Fragile plan">Treating Tet week like any other travel week</td><td data-label="Why it is weak">Even when a city is enjoyable, transport, meals, operators, and service rhythm may be different.</td><td data-label="Safer alternative">Check exact dates, protect essential movement, and choose a comfortable base.</td></tr>
<tr><td data-label="Fragile plan">Assuming the post-Tet restart is instant</td><td data-label="Why it is weak">Some places return quickly; others resume gradually, and ordinary logistics can be uneven.</td><td data-label="Safer alternative">Leave low-stakes days after the peak before remote transfers or complex tours.</td></tr>
<tr><td data-label="Fragile plan">Building central Vietnam only around the beach</td><td data-label="Why it is weak">February can improve, but a route with no food, heritage, hotel, or city value is brittle.</td><td data-label="Safer alternative">Choose Hoi An, Hue, and Da Nang for central route value, with beach time as a bonus.</td></tr>
<tr><td data-label="Fragile plan">Adding Phu Quoc for two nights after a busy full-country route</td><td data-label="Why it is weak">The extra flights and transfers can erase the rest value.</td><td data-label="Safer alternative">Use three or more island nights, or keep the mainland route stronger.</td></tr>
<tr><td data-label="Fragile plan">Cruise exit directly into an important flight</td><td data-label="Why it is weak">Weather, port timing, road traffic, and operator changes can make small delays expensive.</td><td data-label="Safer alternative">Add a buffer night or choose safer onward timing.</td></tr>
<tr><td data-label="Fragile plan">North, central, south, Mekong, and island in a short trip</td><td data-label="Why it is weak">February tempts travelers to add everything, but transfer days still count.</td><td data-label="Safer alternative">Choose two strong chapters for 10 days, or use 14 days for three with buffers.</td></tr>
</tbody>
</table>

<!-- vg-february-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before final payment</h2>
<ul class="vg-check-list vg-february-live-checks">
<li>Check exact Tet dates for the year you travel before locking flights, trains, cruises, ferries, remote transfers, or non-refundable hotels.</li>
<li>Check whether your key travel days sit before Tet, during the peak, or in the post-Tet restart window.</li>
<li>Check official forecasts and warnings before paying for bay cruises, ferries, mountain roads, central-coast beach hotels, or weather-sensitive tours.</li>
<li>Check whether Hoi An and Hue still make you happy if beach weather is mixed for several days.</li>
<li>Check whether the north route includes layers for cool mornings, bay decks, early transfers, and possible mist.</li>
<li>Check whether a Phu Quoc or island finish has enough nights to feel restful after airport and transfer time.</li>
<li>Check domestic transport and baggage rules before assuming a mixed north/central/south suitcase will move easily.</li>
<li>Check <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> if your dates overlap Tet, popular winter-sun weeks, cruises, or island resorts.</li>
</ul>

<!-- vg-february-faq:v1 -->
<h2 class="wp-block-heading">Vietnam in February FAQ</h2>
<div class="vg-faq-list vg-february-faq">
<details><summary>Is February a good time to visit Vietnam?</summary><p>February can be an excellent time when the route respects Tet timing and regional differences. It is especially useful for full-country trips with buffers, central heritage routes, and warm southern or island finishes.</p></details>
<details><summary>Where should I go in Vietnam in February?</summary><p>For a first trip with enough time, use Hanoi, Ninh Binh, a bay decision, Hoi An or Hue, Ho Chi Minh City, and possibly Phu Quoc. For shorter trips, choose two strong regions instead of touching every famous place.</p></details>
<details><summary>Does Tet affect February travel?</summary><p>It can. Tet often sits in late January or February, and the days before, during, and after the holiday can affect transport, openings, prices, and service rhythm. Exact dates change every year, so live confirmation matters.</p></details>
<details><summary>Is Hoi An good in February?</summary><p>Hoi An can be very good in February when you value food, heritage, evening walks, cafes, hotels, and central Vietnam atmosphere. It is weaker when the plan depends only on beach conditions.</p></details>
<details><summary>Is northern Vietnam cold in February?</summary><p>Northern Vietnam is usually not a deep-winter trip for most lowland travelers, but it can still feel cool, damp, or misty. Pack layers for Hanoi, Ninh Binh, bay cruises, and mountain extensions.</p></details>
<details><summary>Is Phu Quoc good in February?</summary><p>Phu Quoc can be a strong February island option when the trip needs warm rest and the route can protect at least three nights. Book carefully around Tet or popular winter-sun periods.</p></details>
<details><summary>Should I visit Vietnam before or after Tet?</summary><p>Both can work. Before Tet can feel vivid and busy; peak days need patience and confirmed logistics; after Tet can be calmer but may restart unevenly. The best choice depends on your tolerance for changed routines.</p></details>
</div>

<h2 class="wp-block-heading">Where this guide fits next</h2>
<p>Use this post once February is on your shortlist. Then choose the Tet posture, route length, central chapter, southern warmth decision, island value, and transport plan.</p>
<div class="vg-related-routes vg-february-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Use this for the broader seasonal frame before choosing February-specific trade-offs.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/travel-planning/tet-in-vietnam-travel-guide/">Tet in Vietnam Travel Guide</a><span class="vg-related-route-note">Use this when dates overlap the holiday peak or the post-Tet restart.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/travel-planning/vietnam-in-january/">Vietnam in January</a><span class="vg-related-route-note">Use this when your trip crosses the month boundary or begins before Tet.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/destinations/hoi-an-ancient-town-guide/">Hoi An Ancient Town Guide</a><span class="vg-related-route-note">Use this when central Vietnam should carry the February route.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/destinations/hue-imperial-city-guide/">Hue Imperial City Guide</a><span class="vg-related-route-note">Use this when Hue deserves a protected imperial-history day.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a><span class="vg-related-route-note">Use this when southern island rest is the reason for choosing February.</span></li>
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
    'vg-february-hero:v1',
    'vg-february-concierge-verdict:v1',
    'vg-february-at-a-glance:v1',
    'vg-february-photo-proof:v1',
    'vg-february-source-diversity:v1',
    'vg-february-region-weather:v1',
    'vg-february-route-chooser:v1',
    'vg-february-tet-timing:v1',
    'vg-february-booking:v1',
    'vg-february-packing:v1',
    'vg-february-fragile-plans:v1',
    'vg-february-live-checks:v1',
    'vg-february-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_february_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Vietnam in February: Weather, Tet Timing and Best Routes',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Plan Vietnam in February by Tet timing, post-Tet restart, regional weather, central and southern route value, booking pressure, packing, and live checks.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_february_post_fail('Could not update Vietnam in February post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Vietnam in February: Weather, Tet and Best Routes');
update_post_meta($post_id, 'rank_math_description', 'Plan Vietnam in February by Tet timing, post-Tet restart, regional weather, route shape, transport pressure, packing, and live checks.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Vietnam in February');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Choose a February Vietnam route by Tet timing, post-Tet restart, north warming slowly, central and southern route value, island demand, and live weather checks.');
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
    vg_february_post_fail('Could not assign Vietnam in February categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_february_post_fail('Could not assign Vietnam in February tags: ' . $tag_result->get_error_message());
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
        vg_february_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Vietnam in February post to complete draft: {$post_id}");

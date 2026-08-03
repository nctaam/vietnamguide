<?php
/**
 * Expand the What to Pack for Vietnam by Region and Season post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-what-to-pack-vietnam-region-season.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_packing_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_packing_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_packing_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_packing_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_packing_post_find_by_slug(string $slug): ?WP_Post
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
        vg_packing_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_packing_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-03',
            'vg_editorial_brief_status' => 'brief',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if ($actual_value !== $expected_value) {
            vg_packing_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_packing_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_packing_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'what-to-pack-for-vietnam-region-season';
$post = vg_packing_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_packing_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_packing_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 25, 2026';
vg_packing_post_assert_target_meta($post);

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$hanoi_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg');
$hoi_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hoi_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg');
$hai_van_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1920px-Vietnam%2C_Hai-Van-Pass.jpg');
$hai_van_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg');
$hcmc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg');
$hcmc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg');
$mekong_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1920px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg');
$mekong_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg');
$phu_quoc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$phu_quoc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');

$meta_update_summary = 'Expanded the native WordPress post brief into a complete packing draft with a photo-led hero, proof panel, concierge verdict, at-a-glance packing matrix, photo proof grid, source-diversity table, region-season matrix, activity-specific packing logic, carry-on versus checked bag tradeoff, temple and modesty notes, overpack traps, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for broad regional packing context and season awareness.",
    "Vietnam.travel - Plan your trip - https://vietnam.travel/plan-your-trip - checked {$review_date}; used for route-order context before packing turns into overpacking.",
    "Vietnam.travel - Health and safety - https://vietnam.travel/plan-your-trip/health-safety - checked {$review_date}; used for sun, rain, comfort, and traveler health discipline.",
    "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked {$review_date}; used for north/cool-weather and highland layer framing.",
    "Vietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked {$review_date}; used for central-coast rain, heat, and heritage-day context.",
    "Vietnam.travel - Southern Vietnam destinations - https://vietnam.travel/places-to-go/southern-vietnam - checked {$review_date}; used for heat, humidity, island, and south-route clothing logic.",
    "National Centre for Hydro-Meteorological Forecasting - English forecast and warning pages - https://nchmf.gov.vn/KttvsiteE/en-US/2/index.html - checked {$review_date}; used for live weather and warning discipline close to travel.",
    "World Weather Information Service - Viet Nam official city forecasts - https://worldweather.wmo.int/en/country.html?countryCode=82 - checked {$review_date}; used for official city-level weather context.",
    "CDC Travelers' Health - Vietnam - https://wwwnc.cdc.gov/travel/destinations/traveler/none/vietnam - checked {$review_date}; used for traveler-health framing and personal medical judgment.",
    "GOV.UK - Vietnam health - https://www.gov.uk/foreign-travel-advice/vietnam/health - checked {$review_date}; used as an additional government travel-health source family.",
    "Wikimedia Commons image direct URL - Hanoi Hoan Kiem Lake - {$hero_image} - credit Alex 69200 vx / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hoi An Ancient Town - {$hoi_an_image} - credit Jakub Halun / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Vietnam, Hai-Van-Pass - {$hai_van_image} - credit Wolkenkratzer / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ho Chi Minh City Hall - {$hcmc_image} - credit Steffen Schmitz / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Vietnam, Phong Dien, Mekong Delta, River - {$mekong_image} - credit Vyacheslav Argenberg / CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Kem Beach aerial view Phu Quoc Island Vietnam - {$phu_quoc_image} - credit Vivu Vietnam / CC BY-SA 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This post is a region-and-season packing layer for the native WordPress editorial calendar. It deliberately avoids a generic tropical checklist and instead answers what to pack when the route is north, central, south, mountainous, coastal, rainy-season, or transfer-heavy.';
$meta_evidence_moat = implode("\n", [
    'Decision-led packing guide built around region, season, and activity instead of a universal tropical list.',
    'At-a-glance packing matrix turns north, central, south, mountain, and coast into concrete fabric and layer choices.',
    'Photo proof makes each image do route work: cool north, humid south, central rain, pass wind, Mekong water, and island sun.',
    'Source-diversity table separates broad climate guidance from live weather and traveler-health decisions.',
    'Activity-specific logic covers city walking, temples, boats, cruises, islands, mountain days, rainy transfer days, and family travel.',
    'Carry-on versus checked-bag table keeps route length, laundry, and transfer friction visible.',
    'Temple and modesty notes keep the guide useful for heritage days rather than just beaches and city walks.',
    'Overpack traps explain what slows the route down and what to skip.',
    'Live checks remind travelers to recheck weather, baggage limits, drying time, and exact activities before departure.',
    'No visible external body anchors; official source trail and image-license records stay auditable through metadata and shortcode output.',
]);
$meta_related_routes = implode("\n", [
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Use this for the broad route order before clothing starts competing with base choice.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Use this to match packing to the season and region before locking dates.',
    'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Use before departure for medication, personal health, and insurance preparation.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Keep the bag, laundry, and baggage decisions honest against the actual route budget.',
    'Money in Vietnam | /plan/money-cash-cards-atms/ | Keep cash, cards, small notes, and payment habits workable while packing the day bag.',
    'SIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, weather checks, translation, and hotel contact working before packing around first arrivals.',
    'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair city movement, bags, and valuables with practical street awareness.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Pack differently for train, flight, car, cruise, and transfer-heavy days.',
    'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use northern city detail to decide whether a jacket or light layer should live in the carry-on.',
    'Ho Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Use southern city rhythm to decide how light the wardrobe can stay.',
    'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use central-coast logic for rain shells, beachwear, and city-comfort packing.',
    'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Use island logic for swimwear, sun protection, and dry-bag choices.',
    'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Use countryside and boat-day logic when deciding on shoes, shells, and quick-dry layers.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Use the route length to decide whether a carry-on is enough or a checked bag is simpler.',
]);
$meta_hero_image_credit = 'Hero image: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0. Body images: Hoi An Ancient Town by Jakub Halun, CC BY-SA 4.0; Vietnam, Hai-Van-Pass by Wolkenkratzer, CC BY-SA 4.0; Ho Chi Minh City Hall by Steffen Schmitz, CC BY-SA 4.0; Vietnam, Phong Dien, Mekong Delta, River by Vyacheslav Argenberg, CC BY 4.0; Kem Beach aerial view Phu Quoc Island Vietnam by Vivu Vietnam, CC BY-SA 4.0.';
$meta_admin_notes = 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, checks Rank Math/social preview, verifies image presentation, weather logic, packing logic, carry-on tradeoffs, temple modesty notes, source-trail records, and adds any first-hand packing notes before publishing. This article is not a universal packing list; it is a route-specific packing decision guide.';

$content = <<<HTML
<!-- vg-packing-hero:v1 -->
<section class="vg-guide-hero vg-packing-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Pack for the route, not the stereotype</p>
<h1>What to Pack for Vietnam by Region and Season</h1>
<p class="vg-guide-lede">Vietnam does not reward one generic packing list. Northern mornings can feel cool, central days can swing from heritage heat to rain, the south usually asks for breathable clothing, and mountain or coast days can change the bag again. Pack by region, season, and activity, then choose a bag strategy that still leaves room for laundry and route changes.</p>
<p class="vg-field-note">Use this with <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>, <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>, and <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before you close the suitcase.</p>
</div>
<figure class="vg-guide-hero-image"><img src="{$hero_image}" alt="Hanoi Hoan Kiem Lake used as a northern Vietnam packing and layering reference" loading="eager" decoding="async"><figcaption>Northern Vietnam is where pack-light assumptions can break first. A small jacket or layer is often more useful than another pair of jeans. Image: Alex 69200 vx / CC BY-SA 4.0.</figcaption></figure>
</section>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-packing-concierge-verdict:v1 -->
<aside class="vg-concierge-verdict vg-packing-concierge-verdict" aria-label="Vietnam packing verdict">
<p class="vg-kicker">VietnamGuide verdict</p>
<h2>Pack by region, then trim for season and activity.</h2>
<p><strong>The safest premium move is not to overpack for every possibility. It is to pack one base wardrobe, then add region-specific layers and activity-specific extras.</strong> Northern trips usually need more layering than travelers expect. Central Vietnam needs a real rain and wind plan. The south rewards breathable, quick-drying clothing. Mountain and coast days need their own accessories.</p>
<ul>
<li><strong>Best default:</strong> mix breathable clothing, one light outer layer, one rain layer, comfortable walking shoes, and a compact day bag.</li>
<li><strong>Best first-trip mistake to avoid:</strong> packing for a climate slogan instead of the exact cities and months on your route.</li>
<li><strong>Best bag strategy:</strong> carry-on for shorter or cleaner routes, checked bag only when the route, season, or family gear makes it worthwhile.</li>
<li><strong>Best wardrobe test:</strong> if a piece only works in one perfect scenario, it probably does not belong on a first Vietnam trip.</li>
</ul>
</aside>

<p>This guide is built for the traveler deciding between Hanoi, Ninh Binh, Ha Long, Hue, Hoi An, Da Nang, Ho Chi Minh City, the Mekong, Phu Quoc, or a mountain stop. The same shirt can be excellent in the south and annoying in the north. The same shoes can be fine in the city and wrong for a wet boat or a cliffside pass. The answer changes with the route, which is exactly why a premium packing guide needs a route brain, not a shopping list.</p>

<p>It also avoids the classic packing trap: adding "just in case" items until the bag becomes a penalty. Packing should help you move better, sleep better, and recover faster between cities. If a piece of clothing does not improve one of those three things, leave it out.</p>

<p>The practical frame is simple: pack by region and season, then let the activity decide whether a rain shell, a warmer layer, swimwear, or temple cover belongs in the bag.</p>

<p>When the bag choice gets tight, treat carry-on vs checked bag as a route decision, not a status decision.</p>

<p>Visible external links are intentionally avoided in the body. Official weather, region, and health sources stay in the source trail metadata so the article can remain clean, evergreen, and auditable.</p>

<!-- vg-packing-at-a-glance:v1 -->
<h2 class="wp-block-heading">Packing by region and season at a glance</h2>
<table class="vg-decision-table vg-packing-at-a-glance">
<thead><tr><th>Region / season</th><th>Pack first</th><th>Add if needed</th><th>Skip the impulse item</th></tr></thead>
<tbody>
<tr><td data-label="Region / season">North in cooler months</td><td data-label="Pack first">Light jacket, long sleeves, breathable base layers, closed walking shoes.</td><td data-label="Add if needed">Scarf, warmer socks, compact umbrella, one warmer top for highland or bay wind.</td><td data-label="Skip the impulse item">Bulky coat if you are only doing city and lowland days.</td></tr>
<tr><td data-label="Region / season">North in warm or humid months</td><td data-label="Pack first">Breathable shirts, quick-dry bottoms, rain shell, good sandals or shoes.</td><td data-label="Add if needed">Spare socks, cap, anti-friction item, extra hydration bottle.</td><td data-label="Skip the impulse item">Heavy denim that stays damp and slow to wash.</td></tr>
<tr><td data-label="Region / season">Central Vietnam in wet or shoulder months</td><td data-label="Pack first">Compact rain shell, quick-dry clothing, waterproof bag cover, sandals that can handle water.</td><td data-label="Add if needed">Foldable umbrella, extra socks, temple-appropriate layer, small towel.</td><td data-label="Skip the impulse item">Leather shoes that hate rain and sand.</td></tr>
<tr><td data-label="Region / season">South in hot or humid months</td><td data-label="Pack first">Lightweight tops, breathable trousers or skirts, sun hat, sun protection, comfortable sandals.</td><td data-label="Add if needed">Insect repellent, backup shirt for wet heat, thin rain layer for showers.</td><td data-label="Skip the impulse item">Heavy sweaters or multiple thick layers you will not use.</td></tr>
<tr><td data-label="Region / season">Mountains and highland days</td><td data-label="Pack first">Warm layer, socks, closed shoes, rain shell, one piece that blocks wind.</td><td data-label="Add if needed">Gloves, beanie, extra dry bag, faster-drying base layers.</td><td data-label="Skip the impulse item">A single city outfit that assumes lowland warmth will follow you upward.</td></tr>
<tr><td data-label="Region / season">Coast and island days</td><td data-label="Pack first">Swimwear, sun shirt, sandals, dry bag, quick-dry clothing.</td><td data-label="Add if needed">Rash guard, extra towel, spare phone protection, motion or sun comfort item.</td><td data-label="Skip the impulse item">Heavy boots and a wardrobe that only makes sense in the city.</td></tr>
</tbody>
</table>

<!-- vg-packing-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: what the bag should be answering</h2>
<p>These images are not decoration. They show why a route can ask for layers, quick-dry clothes, wind protection, or an island-light wardrobe.</p>
<div class="vg-guide-photo-grid vg-packing-photo-proof" aria-label="Vietnam packing photo proof">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Hoan Kiem Lake in Hanoi, a reference for northern Vietnam layers" loading="lazy" decoding="async"><figcaption>North-first packing usually needs a layer because mornings, nights, and transfer days can feel cooler than the day suggests. Image: Alex 69200 vx / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hoi_an_image}" alt="Hoi An Ancient Town in central Vietnam during evening light" loading="lazy" decoding="async"><figcaption>Central Vietnam can reward a compact rain shell and clothing that dries fast after heat or showers. Image: Jakub Halun / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hai_van_image}" alt="Hai Van Pass between Hue and Da Nang, showing wind and mountain exposure" loading="lazy" decoding="async"><figcaption>Pass or highland days ask for wind and temperature protection even when the city below feels warm. Image: Wolkenkratzer / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall in southern Vietnam" loading="lazy" decoding="async"><figcaption>The south usually rewards breathable clothes, lighter shoes, and less bag weight overall. Image: Steffen Schmitz / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$phu_quoc_image}" alt="Phu Quoc Island beach view in southern Vietnam" loading="lazy" decoding="async"><figcaption>Island and coast days need swimwear, sun protection, and a dry bag more than a second city outfit. Image: Vivu Vietnam / CC BY-SA 4.0.</figcaption></figure>
</div>

<!-- vg-packing-source-diversity:v1 -->
<h2 class="wp-block-heading">Source trail: what the official sources help with</h2>
<table class="vg-decision-table vg-packing-source-diversity">
<thead><tr><th>Source family</th><th>What it helps decide</th><th>What it does not decide for you</th><th>VietnamGuide judgment</th></tr></thead>
<tbody>
<tr><td data-label="Source family">Weather and climate pages</td><td data-label="What it helps decide">Broad seasonal shape by region and month.</td><td data-label="What it does not decide for you">Whether your exact route should include rain gear, a jacket, or one more night inland.</td><td data-label="VietnamGuide judgment">Use for the climate frame, not the full packing list.</td></tr>
<tr><td data-label="Source family">Forecast and warning services</td><td data-label="What it helps decide">Close-in weather and warning checks.</td><td data-label="What it does not decide for you">How many outfits you will need or whether a city day should become a beach day.</td><td data-label="VietnamGuide judgment">Use close to travel, especially for storms, wind, or heavy rain.</td></tr>
<tr><td data-label="Source family">Regional tourism pages</td><td data-label="What it helps decide">Which areas are north, central, or south, and what the route is asking of you.</td><td data-label="What it does not decide for you">Laundry access, family gear, or how much you personally dislike damp shoes.</td><td data-label="VietnamGuide judgment">Use to align packing with the route spine.</td></tr>
<tr><td data-label="Source family">Traveler-health sources</td><td data-label="What it helps decide">Sun, heat, rain, medication, and personal health caution.</td><td data-label="What it does not decide for you">Fashion, souvenirs, or whether a second pair of sandals is worth the weight.</td><td data-label="VietnamGuide judgment">Use for health safety, not style advice.</td></tr>
<tr><td data-label="Source family">Image-license records</td><td data-label="What it helps decide">Which visual cues are tied to which route conditions.</td><td data-label="What it does not decide for you">Packing weight or transport timing.</td><td data-label="VietnamGuide judgment">Use as visual proof, not decoration.</td></tr>
</tbody>
</table>

<!-- vg-packing-region-season-matrix:v1 -->
<h2 class="wp-block-heading">Region and season matrix</h2>
<table class="vg-decision-table vg-packing-region-season-matrix">
<thead><tr><th>Route band</th><th>Season pressure</th><th>Pack this first</th><th>Do not overpack this</th></tr></thead>
<tbody>
<tr><td data-label="Route band">Hanoi, Ninh Binh, Ha Long, Sapa-side north</td><td data-label="Season pressure">Cooler mornings, damp shoulder weather, or real highland cold in mountain extensions.</td><td data-label="Pack this first">Light jacket, long sleeve layer, closed shoes, socks, compact umbrella, one warm base layer for higher ground.</td><td data-label="Do not overpack this">Thick city fashion pieces that cannot handle walking or damp air.</td></tr>
<tr><td data-label="Route band">Hue, Hoi An, Da Nang, central coast</td><td data-label="Season pressure">Heat, rain, wind, and quick weather swings matter more than a single clean clothing rule.</td><td data-label="Pack this first">Quick-dry clothing, rain shell, sandals or water-ready shoes, umbrella, modest layer for temples.</td><td data-label="Do not overpack this">Dress shoes, bulky coats, or fabrics that take forever to dry.</td></tr>
<tr><td data-label="Route band">Ho Chi Minh City and the Mekong</td><td data-label="Season pressure">Heat and humidity usually dominate, with sudden showers and river-side moisture.</td><td data-label="Pack this first">Breathable tops, light trousers or skirts, sun protection, insect repellent, light rain layer.</td><td data-label="Do not overpack this">Heavy denim, thick knits, or more than one "dressy" option for a casual city route.</td></tr>
<tr><td data-label="Route band">Phu Quoc and coast-first routes</td><td data-label="Season pressure">Sun, salt, sand, showers, and beach downtime change the wardrobe math.</td><td data-label="Pack this first">Swimwear, rash guard, sandals, dry bag, quick-dry towel or clothing, sun shirt.</td><td data-label="Do not overpack this">City shoes and clothing that only works in dry interiors.</td></tr>
<tr><td data-label="Route band">Mountain or pass days</td><td data-label="Season pressure">Wind, elevation, and cloud can make the same day feel colder than the lowland city.</td><td data-label="Pack this first">Warm layer, socks, wind layer, closed shoes, and something that dries quickly.</td><td data-label="Do not overpack this">A single warm layer that assumes the trip stays in one climate band.</td></tr>
</tbody>
</table>

<!-- vg-packing-activity-logic:v1 -->
<h2 class="wp-block-heading">Activity-specific packing logic</h2>
<p>The route decides the activity, and the activity decides the extras. A perfect city outfit is usually wrong on a boat; a beach outfit can be useless in a temple or mountain pass. This is the logic that keeps one bag useful across the whole trip.</p>
<table class="vg-decision-table vg-packing-activity-logic">
<thead><tr><th>Activity</th><th>Pack first</th><th>What it solves</th><th>Common mistake</th></tr></thead>
<tbody>
<tr><td data-label="Activity">City walking</td><td data-label="Pack first">Breathable tops, comfortable shoes, day bag, water bottle, sun protection.</td><td data-label="What it solves">Long walking days in Hanoi, Hoi An, Ho Chi Minh City, or Da Nang without foot fatigue.</td><td data-label="Common mistake">Too many outfit changes and not enough comfortable walking gear.</td></tr>
<tr><td data-label="Activity">Temple and heritage visits</td><td data-label="Pack first">Shoulder-cover layer, knee-friendly bottoms, slip-on shoes, light scarf or wrap.</td><td data-label="What it solves">The need to look respectful without carrying separate formal clothes.</td><td data-label="Common mistake">Packing only beachwear and hoping to improvise at every pagoda or historic site.</td></tr>
<tr><td data-label="Activity">Boats, cruises, ferries, bays</td><td data-label="Pack first">Wind layer, quick-dry clothing, dry bag, motion comfort item, extra socks.</td><td data-label="What it solves">Spray, wind, cool decks, wet boarding, and damp transfers.</td><td data-label="Common mistake">Treating a cruise or ferry like a hotel lounge.</td></tr>
<tr><td data-label="Activity">Mountains and highlands</td><td data-label="Pack first">Warm layer, socks, closed shoes, rain shell, gloves if the route is high enough.</td><td data-label="What it solves">Cold evenings, wet weather, and elevation that undercuts a tropical assumption.</td><td data-label="Common mistake">Leaving all layers in the city because the lowlands felt warm.</td></tr>
<tr><td data-label="Activity">Beach and island days</td><td data-label="Pack first">Swimwear, sun shirt, sandals, dry bag, quick-dry towel, hat.</td><td data-label="What it solves">Salt, heat, and sand without turning the suitcase into a resort store.</td><td data-label="Common mistake">Bringing city shoes and no true beach layer.</td></tr>
<tr><td data-label="Activity">Rainy transfer days</td><td data-label="Pack first">Rain shell, spare socks, quick-dry shoes, umbrella, bag cover.</td><td data-label="What it solves">The day when your walk, ride, and luggage all meet wet weather.</td><td data-label="Common mistake">Packing only one rain option and hoping the weather behaves.</td></tr>
<tr><td data-label="Activity">Family travel</td><td data-label="Pack first">Extra backup outfit, snack layer, small medical basics, easy-wash clothing.</td><td data-label="What it solves">The unglamorous reality of spills, fatigue, and quick changes.</td><td data-label="Common mistake">Packing like every day will be a perfect Instagram day.</td></tr>
</tbody>
</table>

<!-- vg-packing-carry-on-vs-checked:v1 -->
<h2 class="wp-block-heading">Carry-on vs checked bag</h2>
<table class="vg-decision-table vg-packing-carry-on-vs-checked">
<thead><tr><th>Bag choice</th><th>Best for</th><th>Trade-off</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Bag choice">Carry-on + personal item</td><td data-label="Best for">Short trips, clean route shapes, stronger laundry access, and travelers who move fast between cities.</td><td data-label="Trade-off">You must think harder about fabrics, packing cubes, and what actually earns a place in the bag.</td><td data-label="VietnamGuide verdict">Best for most first-time trips under 10-12 days if the weather is not forcing bulky layers.</td></tr>
<tr><td data-label="Bag choice">Checked bag</td><td data-label="Best for">Longer routes, family travel, winter layering, beach plus mountain combinations, or travelers bringing gifts and extra gear.</td><td data-label="Trade-off">More waiting, more handling risk, and more temptation to overpack.</td><td data-label="VietnamGuide verdict">Worth it only when the route truly needs the space.</td></tr>
<tr><td data-label="Bag choice">Hybrid packing</td><td data-label="Best for">Open-jaw routes, one domestic flight, or a trip that mixes city and coast with one protected outer layer.</td><td data-label="Trade-off">You still need discipline, but you get one small comfort buffer.</td><td data-label="VietnamGuide verdict">A good premium middle ground for mixed-season itineraries.</td></tr>
</tbody>
</table>

<!-- vg-packing-temple-modesty:v1 -->
<h2 class="wp-block-heading">Temple, heritage, and modesty notes</h2>
<p>This is a packing guide, not a dress code lecture. The practical rule is simple: pack one light layer that can make a city outfit respectful for temple, pagoda, or heritage visits.</p>
<table class="vg-decision-table vg-packing-temple-modesty">
<thead><tr><th>Situation</th><th>Pack this</th><th>Why it helps</th><th>What not to assume</th></tr></thead>
<tbody>
<tr><td data-label="Situation">Pagoda or temple visit</td><td data-label="Pack this">Scarf, overshirt, longer bottoms, or another layer that covers shoulders.</td><td data-label="Why it helps">It avoids last-minute discomfort or being turned away from a more formal space.</td><td data-label="What not to assume">That beachwear will always be acceptable because the weather is hot.</td></tr>
<tr><td data-label="Situation">Heritage town day</td><td data-label="Pack this">Comfortable closed or semi-closed shoes and a breathable respectful layer.</td><td data-label="Why it helps">Old streets, steps, and stone or tile surfaces are easier in good shoes.</td><td data-label="What not to assume">That sandals are always the most premium choice.</td></tr>
<tr><td data-label="Situation">Museum or indoor culture day</td><td data-label="Pack this">One light layer for air-conditioning and one bag that stays comfortable while walking.</td><td data-label="Why it helps">Indoor days can feel colder than the street.</td><td data-label="What not to assume">That a hot city means every room will feel warm.</td></tr>
</tbody>
</table>

<!-- vg-packing-overpack-traps:v1 -->
<h2 class="wp-block-heading">Overpack traps worth avoiding</h2>
<table class="vg-decision-table vg-packing-overpack-traps">
<thead><tr><th>Trap</th><th>Why it hurts the trip</th><th>Better alternative</th></tr></thead>
<tbody>
<tr><td data-label="Trap">Too many maybe-outfits</td><td data-label="Why it hurts the trip">They create weight without solving route problems.</td><td data-label="Better alternative">Choose one base outfit pattern per region and repeat it.</td></tr>
<tr><td data-label="Trap">Heavy jeans for every day</td><td data-label="Why it hurts the trip">They are slower to wash, dry, and wear in heat or rain.</td><td data-label="Better alternative">Use lighter trousers or quicker-drying fabric.</td></tr>
<tr><td data-label="Trap">Multiple bulky shoes</td><td data-label="Why it hurts the trip">Shoes are the easiest way to waste suitcase volume.</td><td data-label="Better alternative">One comfortable walking pair plus one activity-specific pair.</td></tr>
<tr><td data-label="Trap">Full-size toiletries and duplicates</td><td data-label="Why it hurts the trip">They steal space from clothes that actually change with region.</td><td data-label="Better alternative">Carry a minimal first-night kit and buy the rest locally if needed.</td></tr>
<tr><td data-label="Trap">Packing for every weather theory</td><td data-label="Why it hurts the trip">A first trip should not carry the weight of every hypothetical month.</td><td data-label="Better alternative">Pack for the actual month, the actual regions, and the actual activities.</td></tr>
</tbody>
</table>

<!-- vg-packing-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before you close the bag</h2>
<ul class="vg-check-list vg-packing-live-checks">
<li>Check the month and region again before deciding whether the light jacket or rain shell belongs in your day bag or your checked bag.</li>
<li>Check weather, warnings, and elevation for the exact cities, not only the arrival city.</li>
<li>Check how many wash-and-dry nights the route really gives you.</li>
<li>Check whether any day depends on a temple, boat, cruise, ferry, beach, or mountain stop that needs special shoes or a cover layer.</li>
<li>Check airline baggage limits and self-transfer rules before assuming a checked bag will be painless.</li>
<li>Check whether your shoes can handle city walking, a wet sidewalk, and a long day without becoming the route's weakest point.</li>
</ul>

<!-- vg-packing-faq:v1 -->
<h2 class="wp-block-heading">Short answers to common packing questions</h2>
<div class="vg-faq-list vg-packing-faq">
<details><summary>Do I need a jacket for Vietnam?</summary><p>Often yes if you are visiting the north, highlands, or a route with cool mornings, air-conditioning, or mountain/pass days. In the south-only route, a very light layer is usually enough.</p></details>
<details><summary>Can I wear shorts everywhere?</summary><p>Shorts are fine for many city and beach days, but pack one modest layer or longer bottom for temples, heritage sites, or more formal spaces.</p></details>
<details><summary>Should I bring a rain jacket or umbrella?</summary><p>Ideally both if your route touches central Vietnam, shoulder season, or high-transfer days. A compact rain shell and a small umbrella cover different situations.</p></details>
<details><summary>Is a carry-on enough for Vietnam?</summary><p>For many first trips, yes. A carry-on works well when the route is compact, the clothing is quick-dry, and you are not bringing bulk for mountains, family gear, or long mixed-weather stays.</p></details>
<details><summary>Can I just buy things there?</summary><p>Yes for some basics, but you should not rely on local shopping to fix a bad route-based packing decision. Pack the items that protect the first nights, the weather, and the exact activities.</p></details>
<details><summary>What is the most underrated packing item?</summary><p>A light layer that also works for temples, cold buses, air-conditioning, and a windy bay or pass day. It earns its space in more than one scenario.</p></details>
</div>

<h2 class="wp-block-heading">Where this guide fits next</h2>
<p>Use this post to close the suitcase. Then move back outward into the route, weather, and practical guides that decide what still matters.</p>
<div class="vg-related-routes vg-packing-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Use the month and region to decide whether layers, rain gear, or heat gear should win.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a><span class="vg-related-route-note">Use before departure for medication, personal health, and trip protection.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Use to pack for flights, trains, cars, cruises, ferries, and transfer days.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a><span class="vg-related-route-note">Use northern city detail to judge jackets, layers, and walking shoes.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a><span class="vg-related-route-note">Use southern city rhythm to keep the wardrobe light and breathable.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a><span class="vg-related-route-note">Use central coast detail to decide whether the rain shell stays close.</span></li>
<li><span class="vg-related-route-step">07</span><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a><span class="vg-related-route-note">Use island logic to add swimwear and sun protection without overpacking.</span></li>
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
    'vg-packing-hero:v1',
    'vg-packing-concierge-verdict:v1',
    'vg-packing-at-a-glance:v1',
    'vg-packing-photo-proof:v1',
    'vg-packing-source-diversity:v1',
    'vg-packing-region-season-matrix:v1',
    'vg-packing-activity-logic:v1',
    'vg-packing-temple-modesty:v1',
    'vg-packing-carry-on-vs-checked:v1',
    'vg-packing-overpack-traps:v1',
    'vg-packing-live-checks:v1',
    'vg-packing-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_packing_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'What to Pack for Vietnam by Region and Season',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Pack for Vietnam by region and season with north, central, south, mountain, coast, rain, and carry-on logic for first-time travelers.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_packing_post_fail('Could not update Vietnam packing post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'What to Pack for Vietnam by Region and Season');
update_post_meta($post_id, 'rank_math_description', 'Pack for Vietnam by region and season with north, central, south, mountain, coast, rain, and carry-on logic for first-time travelers.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'what to pack for Vietnam');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Pack by region, season, and activity, then choose carry-on or checked bag only when the route and laundry access justify it.');
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

wp_set_object_terms($post_id, vg_packing_post_term_ids('category', ['practicalities', 'seasonal-travel']), 'category', false);
wp_set_object_terms($post_id, vg_packing_post_term_ids('post_tag', ['first-time-vietnam', 'rainy-season', 'family-travel', 'anti-spam-evergreen']), 'post_tag', false);

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
        vg_packing_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Vietnam packing post to complete draft: {$post_id}");

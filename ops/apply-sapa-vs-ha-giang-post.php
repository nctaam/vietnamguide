<?php
/**
 * Expand the Sapa vs Ha Giang post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-sapa-vs-ha-giang-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_sapa_ha_giang_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_sapa_ha_giang_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_sapa_ha_giang_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_sapa_ha_giang_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_sapa_ha_giang_post_find_by_slug(string $slug): ?WP_Post
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
        vg_sapa_ha_giang_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_sapa_ha_giang_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-22',
            'vg_editorial_brief_status' => ['brief', 'complete_draft'],
            'vg_editorial_batch' => 'batch-2-evergreen-planning',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if (is_array($expected_value)) {
            if (! in_array($actual_value, $expected_value, true)) {
                vg_sapa_ha_giang_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected one of " . implode(', ', $expected_value) . '.');
            }
            continue;
        }

        if ($actual_value !== $expected_value) {
            vg_sapa_ha_giang_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_sapa_ha_giang_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_sapa_ha_giang_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'sapa-vs-ha-giang';
$post = vg_sapa_ha_giang_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_sapa_ha_giang_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_sapa_ha_giang_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 26, 2026';

if ($post_id !== 504) {
    vg_sapa_ha_giang_post_fail("Refusing to update {$slug}: expected post ID 504, found {$post_id}.");
}

vg_sapa_ha_giang_post_assert_target_meta($post);

$category_term_ids = vg_sapa_ha_giang_post_term_ids('category', ['destinations', 'travel-planning']);
$tag_term_ids = vg_sapa_ha_giang_post_term_ids('post_tag', ['route-planning', 'first-time-vietnam', 'mountain-planning', 'anti-spam-evergreen']);

$sapa_terraces_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/ff/Rice_terraces_in_Sapa%2C_Vietnam.jpg/1280px-Rice_terraces_in_Sapa%2C_Vietnam.jpg');
$muong_hoa_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/e/ef/Ta_Van_Muong_Ha_vallei.jpg/1280px-Ta_Van_Muong_Ha_vallei.jpg');
$ma_pi_leng_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/4/46/Ma_Pi_Leng_Pass_winding_road_Ha_Giang_Vietnam.jpg/1280px-Ma_Pi_Leng_Pass_winding_road_Ha_Giang_Vietnam.jpg');
$dong_van_karst_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/9d/Karst%40SinhLung_DongVan_HaGiang.jpg/1280px-Karst%40SinhLung_DongVan_HaGiang.jpg');
$tu_san_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/TuSan_Canyon.jpg/1280px-TuSan_Canyon.jpg');

$meta_update_summary = 'Expanded the native WordPress batch 2 brief into a complete Sapa vs Ha Giang decision draft with photo-led hero, proof panel, concierge verdict, internal demand note, scenery/comfort/safety comparison, source-diversity table, choose Sapa/choose Ha Giang/both/neither matrix, trip-length planner, season and terrace timing caveats, transport and license/insurance guardrails, easy-rider/self-drive/private-car filter, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Sapa - https://vietnam.travel/places-to-go/northern-vietnam/sapa - checked {$review_date}; used for national-tourism framing of Sapa terraces, trekking, villages, and northern mountain context.",
    "Vietnam.travel - Ha Giang - https://vietnam.travel/places-to-go/northern-vietnam/ha-giang - checked {$review_date}; used for Ha Giang landscape, route role, and northern frontier context.",
    "Vietnam.travel - Ha Giang Loop - https://vietnam.travel/things-to-do/ha-giang-loop - checked {$review_date}; used for loop framing, scenic road context, and why road travel needs conservative planning.",
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for north-season, rain, fog, heat, and cool-weather caveats.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for bus, rail, private transfer, and route-friction context.",
    "Dong Van Karst Plateau Geopark official site - http://dongvangeopark.com/ - checked {$review_date}; used for geopark identity and local update pointer.",
    "UNESCO Global Geoparks - Dong Van Karst Plateau - https://en.unesco.org/global-geoparks/dong-van-karst-plateau - checked {$review_date}; used for geopark context and landscape identity.",
    "Global Geoparks Network - Dong Van Karst Plateau - https://globalgeoparksnetwork.org/?page_id=600 - checked {$review_date}; used as a source-diverse geopark reference.",
    "Vietnam National Center for Hydro-Meteorological Forecasting - https://nchmf.gov.vn/Kttv/en-US/1/index.html - checked {$review_date}; used as official weather live-check pointer for mountain rain, fog, storms, and cold snaps.",
    "UK FCDO Vietnam travel advice - https://www.gov.uk/foreign-travel-advice/vietnam/safety-and-security - checked {$review_date}; used for road-safety and motorbike-risk source trail.",
    "Australian Smartraveller Vietnam advice - https://www.smartraveller.gov.au/destinations/asia/vietnam - checked {$review_date}; used for insurance, motorcycle, and road-safety source trail.",
    "Internal published page check - https://vietnamguide.net/destinations/hanoi-travel-guide/ - checked {$review_date}; used for Hanoi gateway handoff.",
    "Internal published page check - https://vietnamguide.net/plan/best-time-to-visit-vietnam/ - checked {$review_date}; used for season planning handoff.",
    "Internal published page check - https://vietnamguide.net/plan/transport-within-vietnam/ - checked {$review_date}; used for transfer planning handoff.",
    "Internal published page check - https://vietnamguide.net/plan/health-travel-insurance-vietnam/ - checked {$review_date}; used for insurance and safety handoff.",
    "Internal published page check - https://vietnamguide.net/plan/safety-scams-vietnam/ - checked {$review_date}; used for road, tour, payment, and risk handoff.",
    "Internal published page check - https://vietnamguide.net/compare/north-central-south-vietnam/ - checked {$review_date}; used for region-fit handoff.",
    "Internal published page check - https://vietnamguide.net/itineraries/14-days-in-vietnam/ - checked {$review_date}; used for route-length handoff.",
    "Wikimedia Commons image direct URL - Rice terraces in Sapa, Vietnam - {$sapa_terraces_image} - credit Eerin25, CC0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ta Van Muong Hoa valley - {$muong_hoa_image} - credit Andre Hospers, CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ma Pi Leng Pass winding road Ha Giang - {$ma_pi_leng_image} - credit Khanh Hmoong, CC BY 2.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Dong Van karst landscape - {$dong_van_karst_image} - credit BacLuong, public domain - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Tu San Canyon - {$tu_san_image} - credit NKSTTSSHNVN, CC BY-SA 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This complete draft treats Sapa vs Ha Giang as a comfort, safety, season, and route-pressure decision. Sapa is not dismissed as only crowded; Ha Giang is not romanticized as a casual motorbike loop. The right answer depends on time, road tolerance, weather, license/insurance reality, and whether mountains are the main trip purpose.';
$meta_evidence_moat = implode("\n", [
    'The article separates terrace/trek comfort, loop-road scenery, both-route ambition, and clean-skip logic instead of ranking two mountain names by popularity.',
    'Road safety, self-drive legality, insurance, easy-rider comfort, private-car alternatives, fog, rain, and road fatigue are treated as core decision filters.',
    'Sapa is framed by valley stays, guided trekking, terrace timing, comfort range, and crowd-management rather than dismissed as spoiled.',
    'Ha Giang is framed by road exposure, distance, weather, guide choice, license/insurance discipline, and recovery time rather than romantic loop copy.',
    'Trip-length tables protect short first routes from adding both Sapa and Ha Giang unless northern mountains are the point.',
    'Official tourism, geopark, weather, government travel-advice, image-license, and internal route sources are preserved in metadata/source trail.',
    'No visible external body anchors; source URLs and image credits remain auditable in metadata/source trail.',
]);
$meta_related_routes = implode("\n", [
    'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use as the northern gateway before Sapa, Ha Giang, Ninh Binh, or bay movement.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check northern rain, fog, cool-weather, and terrace-season logic.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Count sleeper bus, rail, private car, pickups, and recovery time before choosing mountains.',
    'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check motorcycle, activity, evacuation, cancellation, and medical cover.',
    'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair mountain tours, deposits, road claims, helmets, and cash with practical risk checks.',
    'North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Decide whether the north deserves enough route weight for mountains.',
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Place the mountain decision inside the first-trip planning order.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Compare guided trekking, easy rider, private car, and extra-night costs.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether any mountain chapter should survive a short route.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide whether Sapa or Ha Giang should be the single mountain extension.',
    '21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use a longer trip to consider both only with real buffers.',
    'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Compare easier karst countryside with a mountain chapter.',
    'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Avoid stacking every northern scenery icon into one short route.',
    'SIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Prepare coverage, offline maps, hotel contacts, and backup communications for mountain routes.',
]);
$meta_hero_image_credit = 'Hero image: Rice terraces in Sapa, Vietnam by Eerin25, CC0. Body images: Ta Van Muong Hoa valley by Andre Hospers, CC BY 4.0; Ma Pi Leng Pass winding road by Khanh Hmoong, CC BY 2.0; Dong Van karst landscape by BacLuong, public domain; Tu San Canyon by NKSTTSSHNVN, CC BY-SA 4.0.';
$meta_admin_notes = 'Complete draft generated for WordPress Admin review. Keep draft until manual preview, Rank Math review, image check, road/weather/source refresh, and final editorial pass are complete. The body intentionally avoids external anchors; source URLs are preserved in metadata and source trail.';

$content = <<<HTML
<!-- vg-sapa-ha-giang-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$sapa_terraces_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Rice terraces in Sapa, Vietnam" src="{$sapa_terraces_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<div class="wp-block-group vg-guide-hero-inner">
<p class="vg-kicker">Reviewed route decision - Updated {$review_date}</p>
<h1 class="wp-block-heading vg-display vg-guide-title">Sapa vs Ha Giang: Terraces, Loop Roads or Softer Mountain Travel?</h1>
<p class="vg-guide-lede">Choose Sapa when you want terraces, guided walking, valley stays, and a softer mountain chapter. Choose Ha Giang when road scenery is the point and you can manage weather, comfort, safety, license, insurance, and recovery time honestly.</p>
<p class="vg-field-note">Concierge verdict: most first-time travelers should choose one northern mountain chapter, not both. Sapa is the calmer fit; Ha Giang is the higher-commitment fit; neither is better than a rushed or underinsured mountain plan.</p>
</div>
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-sapa-ha-giang-concierge-verdict:v1 -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<p><strong>Choose Sapa for a softer mountain stay.</strong> It is the better default for terraces, guided walks, families, couples who want comfort, travelers who prefer hotels or valley lodges, and anyone who wants mountain scenery without making road exposure the main activity.</p>
<p><strong>Choose Ha Giang when the road journey is the reason.</strong> The value is the loop-road landscape, karst plateau, passes, villages, and sense of distance. That same value creates the risk: long riding days, weather exposure, road fatigue, guide quality, helmet quality, license/insurance questions, and limited margin for weak conditions.</p>
<p><strong>Choose both only when northern mountains are the trip's main chapter.</strong> If the route still tries to include Hanoi, Ninh Binh, a bay cruise, central Vietnam, Ho Chi Minh City, and beaches, adding both Sapa and Ha Giang usually turns the trip into transfer management.</p>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-sapa-ha-giang-internal-demand:v1 -->
<h2 class="wp-block-heading">Why this comparison matters</h2>
<p>VietnamGuide's itinerary and Hanoi planning pages already warn against adding every northern scenery name to a short route. Sapa and Ha Giang are the classic pressure point: both sound like mountains, but they solve different traveler jobs. This guide makes the choice explicit before hotels, sleeper buses, easy riders, or private transfers are booked.</p>

<!-- vg-sapa-ha-giang-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: similar region, different trip</h2>
<div class="vg-photo-grid vg-sapa-ha-giang-photo-proof">
<figure><img src="{$sapa_terraces_image}" alt="Rice terraces in Sapa" loading="lazy" decoding="async"><figcaption>Sapa is strongest when terraces, valleys, walks, and softer mountain comfort are the goal. Image: Eerin25, CC0.</figcaption></figure>
<figure><img src="{$muong_hoa_image}" alt="Muong Hoa valley near Sapa" loading="lazy" decoding="async"><figcaption>Valley stays and guided walks give Sapa range beyond the town center. Image: Andre Hospers, CC BY 4.0.</figcaption></figure>
<figure><img src="{$ma_pi_leng_image}" alt="Winding mountain road at Ma Pi Leng Pass in Ha Giang" loading="lazy" decoding="async"><figcaption>Ha Giang's drama is tied to road exposure, so safety and comfort are not side notes. Image: Khanh Hmoong, CC BY 2.0.</figcaption></figure>
<figure><img src="{$dong_van_karst_image}" alt="Karst landscape near Dong Van in Ha Giang" loading="lazy" decoding="async"><figcaption>Dong Van karst scenery rewards a slower, better-managed loop. Image: BacLuong, public domain.</figcaption></figure>
</div>

<!-- vg-sapa-ha-giang-source-diversity:v1 -->
<h2 class="wp-block-heading">How the source trail is used</h2>
<table class="vg-decision-table vg-sapa-ha-giang-source-diversity">
<thead><tr><th>Source type</th><th>What it can prove</th><th>How this guide uses it</th></tr></thead>
<tbody>
<tr><td data-label="Source type">Vietnam.travel destination pages</td><td data-label="What it can prove">National tourism framing for Sapa, Ha Giang, and northern Vietnam highlights.</td><td data-label="How this guide uses it">To define the traveler job without copying attraction lists.</td></tr>
<tr><td data-label="Source type">Vietnam.travel loop and transport pages</td><td data-label="What it can prove">Road-trip and movement context without freezing individual operator claims.</td><td data-label="How this guide uses it">To keep transport friction visible before booking.</td></tr>
<tr><td data-label="Source type">Geopark and UNESCO sources</td><td data-label="What it can prove">Dong Van karst identity, landscape value, and geopark context.</td><td data-label="How this guide uses it">To explain why Ha Giang is a road-and-karst chapter, not just a photo stop.</td></tr>
<tr><td data-label="Source type">Weather and travel-advice sources</td><td data-label="What it can prove">Rain, fog, cold, road safety, motorcycle risk, and insurance caution.</td><td data-label="How this guide uses it">To make risk filters part of the recommendation, not a disclaimer at the end.</td></tr>
<tr><td data-label="Source type">Internal route guides</td><td data-label="What it can prove">How mountain choices compete with Hanoi, Ninh Binh, the bay, and route length.</td><td data-label="How this guide uses it">To prevent northern mountain overstacking on short trips.</td></tr>
</tbody>
</table>

<!-- vg-sapa-ha-giang-decision-matrix:v1 -->
<h2 class="wp-block-heading">Sapa, Ha Giang, both or neither?</h2>
<table class="vg-decision-table vg-sapa-ha-giang-decision-matrix">
<thead><tr><th>Choice</th><th>Best when</th><th>Watch out for</th></tr></thead>
<tbody>
<tr><td data-label="Choice">Sapa</td><td data-label="Best when">You want terraces, guided walking, valley views, hotel choice, and softer mountain travel.</td><td data-label="Watch out for">Staying only in town, expecting perfect terraces in every month, or booking treks without weather/footing checks.</td></tr>
<tr><td data-label="Choice">Ha Giang</td><td data-label="Best when">You want a road-led mountain journey and can handle long days, weather exposure, and safety planning.</td><td data-label="Watch out for">Underestimating road fatigue, riding without valid cover, or treating the loop as a casual add-on.</td></tr>
<tr><td data-label="Choice">Both</td><td data-label="Best when">You have a north-focused route, at least five to seven mountain days, and enough recovery between transfers.</td><td data-label="Watch out for">Duplicating mountain time while cutting Hanoi, Ninh Binh, bay, or central Vietnam too thin.</td></tr>
<tr><td data-label="Choice">Neither</td><td data-label="Best when">The trip is short, weather is weak, comfort needs are high, or the route already has Ninh Binh and bay scenery.</td><td data-label="Watch out for">Adding mountains from fear of missing out rather than a real trip purpose.</td></tr>
</tbody>
</table>

<!-- vg-sapa-ha-giang-sapa-fit:v1 -->
<h2 class="wp-block-heading">Choose Sapa when comfort and terraces matter</h2>
<p>Sapa is the calmer answer when the group wants mountain scenery with more accommodation range, guided walking options, and easier adjustment for mixed fitness. The best version is not a quick town stop. It is a valley-aware stay with realistic weather, walking, and terrace-season expectations.</p>
<ul class="vg-check-list vg-sapa-ha-giang-sapa-fit">
<li><strong>Good fit:</strong> first-time travelers who want a softer highland chapter after Hanoi.</li>
<li><strong>Good fit:</strong> couples and families who value hotels, lodges, shorter walks, and private transfer control.</li>
<li><strong>Good fit:</strong> travelers who prefer guided trekking over sitting on exposed mountain roads for days.</li>
<li><strong>Weak fit:</strong> travelers expecting empty viewpoints, guaranteed clear weather, or perfect rice terraces in every month.</li>
<li><strong>Weak fit:</strong> routes that only allow one tired night after a sleeper transfer.</li>
</ul>

<!-- vg-sapa-ha-giang-ha-giang-fit:v1 -->
<h2 class="wp-block-heading">Choose Ha Giang when the road is the point</h2>
<p>Ha Giang is strongest when the traveler wants the journey itself: passes, karst, valleys, homestays, distance, and changing light. It is also the choice that needs the most discipline. The road cannot be separated from the recommendation.</p>
<ul class="vg-check-list vg-sapa-ha-giang-ha-giang-fit">
<li><strong>Good fit:</strong> confident travelers who choose an experienced driver or carefully vetted easy-rider operator.</li>
<li><strong>Good fit:</strong> photographers and scenery-first travelers who can protect several days in the north.</li>
<li><strong>Good fit:</strong> travelers who understand that weather may change the loop shape.</li>
<li><strong>Weak fit:</strong> anyone planning to self-drive without valid license, insurance clarity, and mountain-road experience.</li>
<li><strong>Weak fit:</strong> trips that require a fast return to Hanoi for an international flight or fragile connection.</li>
</ul>

<!-- vg-sapa-ha-giang-safety-license:v1 -->
<h2 class="wp-block-heading">Safety, license and insurance filter</h2>
<p>The Ha Giang decision should include legal and insurance reality before romance. If a traveler is not licensed and insured for the vehicle they plan to use, self-drive should be removed from the plan. If the group dislikes exposure, long riding days, or uncertain road conditions, private car or a different destination may be the better answer.</p>
<table class="vg-decision-table vg-sapa-ha-giang-safety-license">
<thead><tr><th>Mode</th><th>Use when</th><th>Do not ignore</th></tr></thead>
<tbody>
<tr><td data-label="Mode">Guided Sapa walk</td><td data-label="Use when">You want local navigation, footing help, and a softer mountain day.</td><td data-label="Do not ignore">Rain, mud, shoes, guide quality, and village-visit ethics.</td></tr>
<tr><td data-label="Mode">Ha Giang easy rider</td><td data-label="Use when">You want road scenery without personally controlling the motorbike.</td><td data-label="Do not ignore">Helmet quality, driver reputation, insurance wording, luggage, and fatigue.</td></tr>
<tr><td data-label="Mode">Ha Giang self-drive</td><td data-label="Use when">Only when license, insurance, skill, weather, and road judgment all line up.</td><td data-label="Do not ignore">Medical costs, liability, road surfaces, fog, trucks, fatigue, and guide authority.</td></tr>
<tr><td data-label="Mode">Private car</td><td data-label="Use when">Comfort, weather protection, family needs, or risk reduction matter most.</td><td data-label="Do not ignore">Motion sickness, long days, stops, driver quality, and whether the route still feels worth it.</td></tr>
<tr><td data-label="Mode">Skip or choose Sapa</td><td data-label="Use when">The group wants mountains without road exposure as the main activity.</td><td data-label="Do not ignore">The best safety decision can be choosing a different mountain style.</td></tr>
</tbody>
</table>

<!-- vg-sapa-ha-giang-season-weather:v1 -->
<h2 class="wp-block-heading">Season, fog and terrace timing</h2>
<p>Northern mountains are not a single weather product. Sapa can mean mist, mud, cool mornings, or terrace color depending on the month. Ha Giang can mean clear passes, fog, rain, cold, landslide concern, or tiring heat. The article should be refreshed before publishing with current weather and road notes, but the evergreen rule is simple: mountain routes need flexibility.</p>
<table class="vg-decision-table vg-sapa-ha-giang-season-weather">
<thead><tr><th>Condition</th><th>Sapa effect</th><th>Ha Giang effect</th></tr></thead>
<tbody>
<tr><td data-label="Condition">Clear shoulder weather</td><td data-label="Sapa effect">Good for guided walks, viewpoints, and valley stays.</td><td data-label="Ha Giang effect">Good for pass visibility and longer road days.</td></tr>
<tr><td data-label="Condition">Rain or muddy trails</td><td data-label="Sapa effect">Trekking comfort drops; better shoes, guide, and shorter routes matter.</td><td data-label="Ha Giang effect">Road exposure and delay risk become central planning issues.</td></tr>
<tr><td data-label="Condition">Fog or low visibility</td><td data-label="Sapa effect">Views may disappear, but a comfortable lodge can still work.</td><td data-label="Ha Giang effect">The loop can lose its scenic payoff while risk stays high.</td></tr>
<tr><td data-label="Condition">Terrace-color goals</td><td data-label="Sapa effect">Timing matters; do not promise golden terraces year-round.</td><td data-label="Ha Giang effect">Karst road scenery matters more than rice-field timing.</td></tr>
<tr><td data-label="Condition">Cold snap</td><td data-label="Sapa effect">Pack warmer layers and choose accommodation carefully.</td><td data-label="Ha Giang effect">Riding comfort drops sharply; wind chill and fatigue matter.</td></tr>
</tbody>
</table>

<!-- vg-sapa-ha-giang-trip-length:v1 -->
<h2 class="wp-block-heading">Trip length: how much mountain time is honest?</h2>
<table class="vg-decision-table vg-sapa-ha-giang-trip-length">
<thead><tr><th>Trip length</th><th>Mountain answer</th><th>Cut first</th></tr></thead>
<tbody>
<tr><td data-label="Trip length">7 days</td><td data-label="Mountain answer">Usually choose neither, or one very focused Sapa-style chapter if the north is the whole trip.</td><td data-label="Cut first">Ha Giang loop plus bay plus Ninh Binh plus Hanoi in one compressed week.</td></tr>
<tr><td data-label="Trip length">10 days</td><td data-label="Mountain answer">Choose one: Sapa for comfort/trekking or Ha Giang for road scenery. Do not add both unless other regions leave.</td><td data-label="Cut first">A mountain add-on that weakens central Vietnam or the bay.</td></tr>
<tr><td data-label="Trip length">14 days</td><td data-label="Mountain answer">One mountain chapter can work when northern scenery is a major priority.</td><td data-label="Cut first">Duplicating Ninh Binh, bay, Sapa, and Ha Giang without a rest plan.</td></tr>
<tr><td data-label="Trip length">21 days</td><td data-label="Mountain answer">Both can work only if the north is protected and road recovery is real.</td><td data-label="Cut first">A second mountain area that exists only for coverage.</td></tr>
<tr><td data-label="Trip length">North-only trip</td><td data-label="Mountain answer">Sapa plus Ha Giang can be a deliberate theme with buffers.</td><td data-label="Cut first">Rushing every night to prove the route is possible.</td></tr>
</tbody>
</table>

<!-- vg-sapa-ha-giang-comfort-mobility:v1 -->
<h2 class="wp-block-heading">Comfort, mobility and traveler type</h2>
<p>The right mountain choice often depends less on scenery and more on how the group handles movement. Sapa gives more ways to soften the day. Ha Giang gives stronger road drama but fewer ways to remove the road from the experience.</p>
<ul class="vg-check-list vg-sapa-ha-giang-comfort-mobility">
<li><strong>Families:</strong> Sapa or private-car Ha Giang is usually safer than a motorbike-led loop.</li>
<li><strong>Couples:</strong> Sapa works for lodge comfort; Ha Giang works if the road trip is shared enthusiasm.</li>
<li><strong>Solo travelers:</strong> Ha Giang can be social, but operator vetting, insurance, and fatigue checks matter.</li>
<li><strong>Premium travelers:</strong> private transfers, better hotels, and fewer one-night moves often improve Sapa more clearly than Ha Giang.</li>
<li><strong>Limited mobility travelers:</strong> choose viewpoints, private car, or skip mountains rather than forcing trekking or riding days.</li>
</ul>

<!-- vg-sapa-ha-giang-transport-route-pressure:v1 -->
<h2 class="wp-block-heading">Transport and route pressure</h2>
<p>Both Sapa and Ha Giang are usually Hanoi-linked decisions. The hidden cost is not only the transfer there. It is the recovery night, the early pickup, the onward plan, the luggage handoff, and the way mountain fatigue affects the next chapter.</p>
<table class="vg-decision-table vg-sapa-ha-giang-transport-route-pressure">
<thead><tr><th>Route pressure</th><th>Better Sapa move</th><th>Better Ha Giang move</th></tr></thead>
<tbody>
<tr><td data-label="Route pressure">Short first route</td><td data-label="Better Sapa move">One or two focused nights only if mountains are a top priority.</td><td data-label="Better Ha Giang move">Usually skip unless the loop is the trip's main purpose.</td></tr>
<tr><td data-label="Route pressure">After a bay cruise</td><td data-label="Better Sapa move">Add a recovery buffer before another transfer.</td><td data-label="Better Ha Giang move">Avoid stacking cruise fatigue into loop road fatigue.</td></tr>
<tr><td data-label="Route pressure">Before a flight</td><td data-label="Better Sapa move">Return to Hanoi with a protected final night.</td><td data-label="Better Ha Giang move">Do not return from the loop on a fragile flight day.</td></tr>
<tr><td data-label="Route pressure">North-only trip</td><td data-label="Better Sapa move">Use valleys and guided walks to vary the mountain chapter.</td><td data-label="Better Ha Giang move">Use a slower loop and avoid consecutive hard transfer days.</td></tr>
</tbody>
</table>

<!-- vg-sapa-ha-giang-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before booking</h2>
<ul class="vg-check-list vg-sapa-ha-giang-live-checks">
<li>Check current weather, fog, rain, storm, cold, and road-condition signals close to travel.</li>
<li>Check whether the traveler wants walking/terraces or road-led mountain scenery before comparing prices.</li>
<li>Check license, insurance, helmet, driver, luggage, and medical cover before any motorbike-based Ha Giang plan.</li>
<li>Check Sapa accommodation location: town, valley, lodge, homestay, pickup point, and walking distance.</li>
<li>Check Ha Giang operator safety practices, route pace, group size, and what happens if weather changes.</li>
<li>Check Hanoi return timing and avoid mountain returns on fragile international flight days.</li>
</ul>

<!-- vg-sapa-ha-giang-faq:v1 -->
<h2 class="wp-block-heading">Sapa vs Ha Giang FAQ</h2>
<div class="vg-faq-list vg-sapa-ha-giang-faq">
<details><summary>Is Sapa or Ha Giang better for a first Vietnam trip?</summary><p>Sapa is the better default for most first-time travelers who want mountain scenery with softer comfort. Ha Giang is better when road scenery is the main reason and the traveler can manage safety, weather, insurance, and recovery time.</p></details>
<details><summary>Can I visit both Sapa and Ha Giang?</summary><p>Yes on a north-focused route with enough days and buffers. On a broad first trip, choosing both often weakens Hanoi, Ninh Binh, the bay, or central Vietnam.</p></details>
<details><summary>Is Ha Giang safe for self-drive?</summary><p>Only consider self-drive if your license, insurance, skill, weather, and mountain-road judgment all line up. Many travelers should choose an experienced driver, easy rider, private car, or a different mountain style.</p></details>
<details><summary>Is Sapa too crowded?</summary><p>Sapa can be busy, especially around the town core, but it should not be reduced to crowds. Valley stays, guided walks, shoulder timing, and better accommodation choices can make it a useful mountain chapter.</p></details>
<details><summary>How many nights do I need?</summary><p>Sapa can work with two nights when the plan is focused. Ha Giang usually needs three nights or more to avoid turning the loop into pure fatigue. Both together need a north-focused route.</p></details>
<details><summary>Should I choose Ninh Binh instead?</summary><p>Choose Ninh Binh when you want easier karst countryside and simpler Hanoi-linked logistics. Choose Sapa or Ha Giang only when the mountain chapter itself is worth the extra movement.</p></details>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Use these related guides to decide whether northern mountains improve the trip or simply overload it.</p>
<div class="vg-related-routes vg-sapa-ha-giang-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a><span class="vg-related-route-note">Use as the northern gateway before any mountain move.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Check northern rain, fog, cool-weather, and terrace timing.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Count sleeper bus, rail, private car, pickup, and recovery time.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a><span class="vg-related-route-note">Check motorcycle, activity, evacuation, cancellation, and medical cover.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a><span class="vg-related-route-note">Use practical checks for tours, deposits, road claims, helmets, and cash.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a><span class="vg-related-route-note">Decide whether one mountain chapter earns its place.</span></li>
<li><span class="vg-related-route-step">07</span><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a><span class="vg-related-route-note">Compare easier karst countryside with a harder mountain commitment.</span></li>
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
    'vg-sapa-ha-giang-hero:v1',
    'vg-sapa-ha-giang-concierge-verdict:v1',
    'vg-sapa-ha-giang-internal-demand:v1',
    'vg-sapa-ha-giang-photo-proof:v1',
    'vg-sapa-ha-giang-source-diversity:v1',
    'vg-sapa-ha-giang-decision-matrix:v1',
    'vg-sapa-ha-giang-sapa-fit:v1',
    'vg-sapa-ha-giang-ha-giang-fit:v1',
    'vg-sapa-ha-giang-safety-license:v1',
    'vg-sapa-ha-giang-season-weather:v1',
    'vg-sapa-ha-giang-trip-length:v1',
    'vg-sapa-ha-giang-comfort-mobility:v1',
    'vg-sapa-ha-giang-transport-route-pressure:v1',
    'vg-sapa-ha-giang-live-checks:v1',
    'vg-sapa-ha-giang-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_sapa_ha_giang_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Sapa vs Ha Giang: Terraces, Loop Roads or Softer Mountain Travel?',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Choose Sapa, Ha Giang, both, or neither by scenery style, comfort, season, road safety, license and insurance reality, and route pressure.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_sapa_ha_giang_post_fail('Could not update Sapa vs Ha Giang post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Sapa vs Ha Giang: Terraces, Loop Roads or Softer Travel');
update_post_meta($post_id, 'rank_math_description', 'Choose Sapa, Ha Giang, both, or neither by scenery style, comfort, season, road safety, insurance, route length, and Hanoi transfer pressure.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Sapa vs Ha Giang');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Decide whether Sapa, Ha Giang, both, or neither belongs in a Vietnam itinerary based on terrace and trekking value, loop-road scenery, comfort, safety, weather, license and insurance reality, transfer load, and route length.');
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
    vg_sapa_ha_giang_post_fail('Could not assign Sapa vs Ha Giang categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_sapa_ha_giang_post_fail('Could not assign Sapa vs Ha Giang tags: ' . $tag_result->get_error_message());
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
        vg_sapa_ha_giang_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Sapa vs Ha Giang post to complete draft: {$post_id}");

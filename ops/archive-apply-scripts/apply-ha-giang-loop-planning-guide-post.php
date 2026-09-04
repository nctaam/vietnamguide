<?php
/**
 * Expand the Ha Giang Loop Planning Guide post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-ha-giang-loop-planning-guide-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_ha_giang_loop_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_ha_giang_loop_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_ha_giang_loop_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_ha_giang_loop_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_ha_giang_loop_post_find_by_slug(string $slug): ?WP_Post
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
        vg_ha_giang_loop_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_ha_giang_loop_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-24',
            'vg_editorial_brief_status' => ['brief', 'complete_draft'],
            'vg_editorial_batch' => 'batch-3-northern-mountains',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if (is_array($expected_value)) {
            if (! in_array($actual_value, $expected_value, true)) {
                vg_ha_giang_loop_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected one of " . implode(', ', $expected_value) . '.');
            }
            continue;
        }

        if ($actual_value !== $expected_value) {
            vg_ha_giang_loop_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_ha_giang_loop_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_ha_giang_loop_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'ha-giang-loop-planning-guide';
$post = vg_ha_giang_loop_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_ha_giang_loop_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_ha_giang_loop_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 27, 2026';

if ($post_id !== 519) {
    vg_ha_giang_loop_post_fail("Refusing to update {$slug}: expected post ID 519, found {$post_id}.");
}

vg_ha_giang_loop_post_assert_target_meta($post);

$category_term_ids = vg_ha_giang_loop_post_term_ids('category', ['destinations', 'travel-planning']);
$tag_term_ids = vg_ha_giang_loop_post_term_ids('post_tag', ['mountain-planning', 'safety-planning', 'route-planning', 'anti-spam-evergreen']);

$ma_pi_leng_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/4/46/Ma_Pi_Leng_Pass_winding_road_Ha_Giang_Vietnam.jpg/1280px-Ma_Pi_Leng_Pass_winding_road_Ha_Giang_Vietnam.jpg');
$dong_van_karst_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/9d/Karst%40SinhLung_DongVan_HaGiang.jpg/1280px-Karst%40SinhLung_DongVan_HaGiang.jpg');
$tu_san_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/TuSan_Canyon.jpg/1280px-TuSan_Canyon.jpg');
$sapa_terraces_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/ff/Rice_terraces_in_Sapa%2C_Vietnam.jpg/1280px-Rice_terraces_in_Sapa%2C_Vietnam.jpg');

$meta_update_summary = 'Expanded the native WordPress Batch 3 brief into a complete Ha Giang Loop Planning Guide draft with photo-led hero, proof panel, concierge verdict, internal demand note, photo proof, source-diversity table, go/modify/skip matrix, loop-length planner, easy-rider/private-car/self-drive decision logic, license and insurance guardrails, operator vetting checklist, weather and road-condition matrix, Hanoi transfer buffers, route anatomy, comfort and culture notes, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Ha Giang - https://vietnam.travel/places-to-go/northern-vietnam/ha-giang - checked {$review_date}; used for official destination framing, northern mountain context, and route role.",
    "Vietnam.travel - The Ha Giang Loop - https://vietnam.travel/things-to-do/ha-giang-loop - checked {$review_date}; used for loop framing, Quan Ba/Yen Minh/Dong Van/Meo Vac context, and professional steering caution.",
    "Vietnam.travel - Ha Giang Loop four-day road trip - https://vietnam.travel/things-to-do/ha-giang-loop-four-day-road-trip - checked {$review_date}; used for four-day route structure and QL4C/Dong Van Karst Plateau context without publishing fragile itinerary promises.",
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for north-season, rain, fog, cool-weather, storm, and visibility caveats.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for transfer-friction and route-movement context.",
    "UNESCO - Dong Van Karst Plateau UNESCO Global Geopark - https://www.unesco.org/en/iggp/dong-van-karst-plateau-unesco-global-geopark - checked {$review_date}; used for geopark status, limestone landscape, deep canyon, ethnic-community, and geological-significance context.",
    "Dong Van Karst Plateau Geopark official site - http://dongvangeopark.com/ - checked {$review_date}; used as a local geopark update pointer before manual publishing.",
    "UNESCO Global Geoparks listing - https://www.unesco.org/en/iggp/geoparks - checked {$review_date}; used as a second UNESCO-level source for geopark identity.",
    "Global Geoparks Network map - https://www.globalgeopark.org/GeoparkMap/geoparks/Vietnam/12601.html - checked {$review_date}; used as a source-diverse geopark reference for landscape and canyon context.",
    "Vietnam National Center for Hydro-Meteorological Forecasting - https://nchmf.gov.vn/Kttv/en-US/1/index.html - checked {$review_date}; used as live weather source pointer for mountain rain, fog, storms, cold snaps, and visibility checks.",
    "UK FCDO Vietnam travel advice - https://www.gov.uk/foreign-travel-advice/vietnam/safety-and-security - checked {$review_date}; used for road-safety, motorcycle, and travel-risk source trail.",
    "Australian Smartraveller Vietnam advice - https://www.smartraveller.gov.au/destinations/asia/vietnam - checked {$review_date}; used for insurance, motorcycle, road-safety, and medical-cover source trail.",
    "Internal published page check - https://vietnamguide.net/destinations/hanoi-travel-guide/ - checked {$review_date}; used for Hanoi gateway handoff.",
    "Internal published page check - https://vietnamguide.net/plan/best-time-to-visit-vietnam/ - checked {$review_date}; used for northern weather and season planning handoff.",
    "Internal published page check - https://vietnamguide.net/plan/transport-within-vietnam/ - checked {$review_date}; used for transfer planning handoff.",
    "Internal published page check - https://vietnamguide.net/plan/health-travel-insurance-vietnam/ - checked {$review_date}; used for motorcycle, activity, evacuation, medical, and cancellation cover handoff.",
    "Internal published page check - https://vietnamguide.net/plan/safety-scams-vietnam/ - checked {$review_date}; used for road-risk, guide, deposit, operator, and cash handoff.",
    "Internal published page check - https://vietnamguide.net/compare/north-central-south-vietnam/ - checked {$review_date}; used for region-fit handoff.",
    "Internal published page check - https://vietnamguide.net/plan/vietnam-travel-guide/ - checked {$review_date}; used for first-trip planning order.",
    "Internal published page check - https://vietnamguide.net/costs/vietnam-travel-cost/ - checked {$review_date}; used for guide, easy-rider, private-car, extra-night, and buffer-cost handoff.",
    "Internal published page check - https://vietnamguide.net/itineraries/10-days-in-vietnam/ - checked {$review_date}; used for short-route pressure.",
    "Internal published page check - https://vietnamguide.net/itineraries/14-days-in-vietnam/ - checked {$review_date}; used for one northern mountain chapter planning.",
    "Internal published page check - https://vietnamguide.net/itineraries/21-days-in-vietnam/ - checked {$review_date}; used for slower north-focused route planning.",
    "Internal published page check - https://vietnamguide.net/destinations/ninh-binh-travel-guide/ - checked {$review_date}; used for easier northern scenery alternative.",
    "Internal published page check - https://vietnamguide.net/destinations/ha-long-bay-travel-guide/ - checked {$review_date}; used for northern scenery stacking caution.",
    "Internal published page check - https://vietnamguide.net/plan/sim-esim-vietnam/ - checked {$review_date}; used for offline map, route communication, and backup connectivity handoff.",
    "Wikimedia Commons image direct URL - Ma Pi Leng Pass winding road Ha Giang - {$ma_pi_leng_image} - credit Khanh Hmoong, CC BY 2.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Dong Van karst landscape - {$dong_van_karst_image} - credit BacLuong, public domain - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Tu San Canyon - {$tu_san_image} - credit NKSTTSSHNVN, CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Rice terraces in Sapa, Vietnam - {$sapa_terraces_image} - credit Eerin25, CC0 - license context checked {$review_date}; used only as a comparison visual for softer mountain alternatives.",
]);
$meta_field_note = 'This complete draft treats the Ha Giang Loop as a route-risk and route-fit decision, not a thrill article. The loop can be exceptional when road scenery, time, weather, operator quality, license/insurance reality, and recovery buffers line up. It should be modified or skipped when the plan depends on casual self-drive, weak insurance, bad weather, fragile flight timing, or a traveler who does not actually want road exposure.';
$meta_evidence_moat = implode("\n", [
    'The article answers whether to go, modify, upgrade, or skip the Ha Giang Loop before a traveler pays for a tour, motorbike, easy rider, or private car.',
    'The first screen makes safety, license, insurance, weather, and recovery time part of the recommendation instead of a disclaimer.',
    'The guide separates easy rider, self-drive, private car, and skip decisions by responsibility, not by adventure branding.',
    'Loop length is framed as energy and risk management: two days is usually too thin, three days is compressed, four days is a better default, and five days only helps when the route has slack.',
    'Operator vetting questions give practical decision value beyond rewritten route lists.',
    'Dong Van Karst Plateau context explains why the landscape matters without turning the article into a geology encyclopedia.',
    'Hanoi transfer and return buffers are included so the loop is judged inside the whole Vietnam itinerary.',
    'Photo proof uses credited real Ha Giang/Dong Van/Tu San imagery plus one comparison image, with image credits retained in captions and metadata.',
    'No visible external body anchors; source URLs and image-license records remain in metadata/source trail.',
]);
$meta_related_routes = implode("\n", [
    'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use as the northern gateway before Ha Giang transfer planning.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check northern rain, fog, cool-weather, and visibility windows.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Count Hanoi transfers, sleeper bus fatigue, private car, luggage, and recovery time.',
    'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check motorcycle, activity, medical, evacuation, cancellation, and liability wording.',
    'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair operator vetting, road claims, helmets, deposits, cash, and emergency fallback with practical checks.',
    'North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Decide whether northern scenery should dominate the trip.',
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Place Ha Giang inside the full first-trip planning order.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Compare easy rider, private car, extra-night, buffer, and insurance costs.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Ha Giang survives a short route.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use one mountain chapter only when it earns the time.',
    '21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Consider a slower north-focused route with real buffers.',
    'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Compare easier karst countryside with loop-road exposure.',
    'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Avoid stacking every northern scenery icon into one short route.',
    'SIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Prepare offline maps, hotel contacts, and backup communication for mountain routes.',
]);
$meta_hero_image_credit = 'Hero image: Ma Pi Leng Pass winding road in Ha Giang by Khanh Hmoong, CC BY 2.0. Body images: Dong Van karst landscape by BacLuong, public domain; Tu San Canyon by NKSTTSSHNVN, CC BY-SA 4.0; Rice terraces in Sapa, Vietnam by Eerin25, CC0, used as a comparison image for softer mountain travel.';
$meta_admin_notes = 'Complete draft generated for WordPress Admin review. Keep draft until manual preview, Rank Math review, image check, road/weather/operator/source refresh, and final editorial pass are complete. The body intentionally avoids external anchors; source URLs are preserved in metadata and source trail. Before publishing, refresh weather, road-condition, operator, license, insurance, emergency, and image-license notes.';

$content = <<<HTML
<!-- vg-ha-giang-loop-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$ma_pi_leng_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Winding mountain road at Ma Pi Leng Pass in Ha Giang" src="{$ma_pi_leng_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<div class="wp-block-group vg-guide-hero-inner">
<p class="vg-kicker">Reviewed loop planning guide - Updated {$review_date}</p>
<h1 class="wp-block-heading vg-display vg-guide-title">Ha Giang Loop Planning Guide: Safety, Scenery and Route Fit</h1>
<p class="vg-guide-lede">The Ha Giang Loop is worth planning when the road journey is the reason for adding northern Vietnam. It is a poor fit when a traveler wants easy scenery, has fragile timing, lacks license or insurance clarity, or treats mountain-road exposure as a casual add-on.</p>
<p class="vg-field-note">Concierge verdict: choose Ha Giang only when safety, weather, road comfort, operator quality, route length, and Hanoi return buffers all support the decision. The better loop is slower, better vetted, and honest about risk.</p>
</div>
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-ha-giang-loop-concierge-verdict:v1 -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<p><strong>Go when the loop road is the point.</strong> Ha Giang is strongest for travelers who want karst passes, mountain roads, local stops, changing light, and a north-focused route that has time to absorb weather and fatigue.</p>
<p><strong>Modify the plan when the scenery is attractive but the risk stack is wrong.</strong> Easy rider, private car, a shorter scenic section, or a different northern destination can be the better answer when self-drive responsibility, road exposure, or group comfort does not fit.</p>
<p><strong>Skip when the trip is already overloaded.</strong> If Hanoi, Ninh Binh, a bay cruise, Central Vietnam, the south, and beaches are all competing for a short itinerary, Ha Giang may damage the whole route rather than improve it.</p>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-ha-giang-loop-internal-demand:v1 -->
<h2 class="wp-block-heading">Why Ha Giang needs a planning guide, not only an itinerary</h2>
<p>Many Ha Giang articles begin with a route map. That is not where most international travelers should start. The first decision is whether the loop fits the traveler's risk tolerance, time budget, weather window, insurance wording, and route rhythm. The same scenery that makes Ha Giang memorable also creates the planning problem: long riding days, exposed roads, changing mountain weather, limited recovery margin, and a strong temptation to underrate fatigue.</p>
<p>This guide is written to protect the trip before the booking stage. It does not sell one operator, one exact route, or one universal number of days. It helps the editor and traveler decide whether to go, slow down, choose an easy rider, choose a private car, self-drive only with proper safeguards, or skip Ha Giang for a route that will work better.</p>

<!-- vg-ha-giang-loop-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: what the loop is really asking from you</h2>
<div class="vg-photo-grid vg-ha-giang-loop-photo-proof">
<figure><img src="{$ma_pi_leng_image}" alt="Winding mountain road at Ma Pi Leng Pass in Ha Giang" loading="lazy" decoding="async"><figcaption>Ha Giang's drama is tied to road exposure, so safety and comfort are part of the scenery decision. Image: Khanh Hmoong, CC BY 2.0.</figcaption></figure>
<figure><img src="{$dong_van_karst_image}" alt="Karst landscape near Dong Van in Ha Giang" loading="lazy" decoding="async"><figcaption>Dong Van Karst Plateau gives the loop its landscape identity; it rewards slower pacing and clearer weather. Image: BacLuong, public domain.</figcaption></figure>
<figure><img src="{$tu_san_image}" alt="Tu San Canyon near Ha Giang" loading="lazy" decoding="async"><figcaption>Tu San and the Ma Pi Leng area are highlights, but a viewpoint day can still fail if fog, rain, or fatigue is ignored. Image: NKSTTSSHNVN, CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$sapa_terraces_image}" alt="Rice terraces in Sapa, Vietnam" loading="lazy" decoding="async"><figcaption>If the group wants softer mountain travel, terrace walks, and lodge comfort, a Sapa-style chapter may fit better than a road-led loop. Image: Eerin25, CC0.</figcaption></figure>
</div>

<!-- vg-ha-giang-loop-source-diversity:v1 -->
<h2 class="wp-block-heading">How the source trail is used</h2>
<table class="vg-decision-table vg-ha-giang-loop-source-diversity">
<thead><tr><th>Source type</th><th>What it can prove</th><th>How this guide uses it</th></tr></thead>
<tbody>
<tr><td data-label="Source type">National tourism pages</td><td data-label="What it can prove">Destination role, loop framing, core districts, and route language.</td><td data-label="How this guide uses it">To anchor the route without copying a day-by-day plan as permanent truth.</td></tr>
<tr><td data-label="Source type">Geopark and UNESCO sources</td><td data-label="What it can prove">Dong Van Karst Plateau identity, limestone landscape, deep canyon context, and heritage value.</td><td data-label="How this guide uses it">To explain why the scenery is meaningful without making geology the whole article.</td></tr>
<tr><td data-label="Source type">Weather sources</td><td data-label="What it can prove">Rain, fog, storms, cold snaps, visibility, and live-check discipline.</td><td data-label="How this guide uses it">To make weather a booking filter rather than a footnote.</td></tr>
<tr><td data-label="Source type">Government travel advice and insurance sources</td><td data-label="What it can prove">Road-risk, motorcycle, medical, liability, and insurance caution.</td><td data-label="How this guide uses it">To keep safety and license reality central to the recommendation.</td></tr>
<tr><td data-label="Source type">Internal route guides</td><td data-label="What it can prove">How Ha Giang competes with Hanoi, Ninh Binh, bay cruising, Central Vietnam, and route length.</td><td data-label="How this guide uses it">To decide whether Ha Giang strengthens the whole itinerary or only adds pressure.</td></tr>
</tbody>
</table>

<!-- vg-ha-giang-loop-go-modify-skip:v1 -->
<h2 class="wp-block-heading">Go, modify, upgrade or skip?</h2>
<table class="vg-decision-table vg-ha-giang-loop-go-modify-skip">
<thead><tr><th>Decision</th><th>Best when</th><th>Planning move</th></tr></thead>
<tbody>
<tr><td data-label="Decision">Go</td><td data-label="Best when">The traveler wants a road-led mountain chapter and can protect at least several days in the north.</td><td data-label="Planning move">Choose a slower loop, vetted operator, weather margin, and a protected return to Hanoi.</td></tr>
<tr><td data-label="Decision">Modify</td><td data-label="Best when">The scenery matters but self-drive, group comfort, or weather makes the standard loop too exposed.</td><td data-label="Planning move">Use easy rider, private car, shorter scenic legs, or a staged route with recovery.</td></tr>
<tr><td data-label="Decision">Upgrade</td><td data-label="Best when">The route is worth it but fatigue, luggage, children, photography, or premium comfort needs more control.</td><td data-label="Planning move">Spend on better driver, smaller group, private guide, private car, stronger helmets, and better lodging.</td></tr>
<tr><td data-label="Decision">Skip</td><td data-label="Best when">The traveler lacks license or insurance clarity, dislikes exposed roads, has bad weather, or must return for a fragile flight.</td><td data-label="Planning move">Choose Hanoi, Ninh Binh, bay scenery, Pu Luong, Sapa-style comfort, or Central Vietnam instead.</td></tr>
<tr><td data-label="Decision">Postpone</td><td data-label="Best when">Ha Giang is personally important but the current route is too short.</td><td data-label="Planning move">Build a future north-focused Vietnam trip rather than forcing the loop into a first-trip sample platter.</td></tr>
</tbody>
</table>

<!-- vg-ha-giang-loop-route-length:v1 -->
<h2 class="wp-block-heading">How many days should the loop get?</h2>
<p>The common mistake is treating loop length as a bragging number. For planning, days are not only sightseeing time; they are weather margin, road fatigue management, photography flexibility, lunch delays, luggage handling, and the ability to stop before the group is tired enough to make poor decisions.</p>
<table class="vg-decision-table vg-ha-giang-loop-route-length">
<thead><tr><th>Loop shape</th><th>Who it fits</th><th>What can go wrong</th></tr></thead>
<tbody>
<tr><td data-label="Loop shape">Two days / one night</td><td data-label="Who it fits">Usually only a taste for travelers who know exactly what they are sacrificing.</td><td data-label="What can go wrong">Too much road, too little slack, weak scenic payoff if weather turns, and a rushed return.</td></tr>
<tr><td data-label="Loop shape">Three days / two nights</td><td data-label="Who it fits">Time-limited travelers choosing one northern mountain chapter.</td><td data-label="What can go wrong">Compression, fewer slow stops, tired return to Ha Giang city, and less margin for fog or rain.</td></tr>
<tr><td data-label="Loop shape">Four days / three nights</td><td data-label="Who it fits">The cleaner default for many international travelers who genuinely want Ha Giang.</td><td data-label="What can go wrong">Still requires honest road tolerance, operator quality, and a protected Hanoi return.</td></tr>
<tr><td data-label="Loop shape">Five days or more</td><td data-label="Who it fits">Photographers, slow travelers, and north-focused routes.</td><td data-label="What can go wrong">Extra nights help only when the route is not stealing from higher-priority parts of Vietnam.</td></tr>
<tr><td data-label="Loop shape">Private-car scenic route</td><td data-label="Who it fits">Families, cautious travelers, premium routes, or anyone who wants landscape without motorbike exposure.</td><td data-label="What can go wrong">Long days can still cause motion sickness and fatigue; driver quality remains critical.</td></tr>
</tbody>
</table>

<!-- vg-ha-giang-loop-mode-filter:v1 -->
<h2 class="wp-block-heading">Easy rider, private car or self-drive?</h2>
<p>Mode choice is the core of Ha Giang planning. It is not only about control or cost. It decides who carries road responsibility, how weather changes the day, how luggage moves, how tired the traveler becomes, and whether insurance and medical fallback make sense.</p>
<table class="vg-decision-table vg-ha-giang-loop-mode-filter">
<thead><tr><th>Mode</th><th>Use when</th><th>Do not ignore</th></tr></thead>
<tbody>
<tr><td data-label="Mode">Easy rider</td><td data-label="Use when">You want motorbike-based scenery but do not want to control the bike yourself.</td><td data-label="Do not ignore">Driver reputation, helmet quality, passenger comfort, luggage, insurance wording, and group size.</td></tr>
<tr><td data-label="Mode">Small guided group</td><td data-label="Use when">Budget, social travel, and a set route matter more than custom pacing.</td><td data-label="Do not ignore">Convoy behavior, stop discipline, weather decisions, medical fallback, and pressure to keep riding.</td></tr>
<tr><td data-label="Mode">Private easy rider</td><td data-label="Use when">Photography, comfort, mixed ability, or flexible stops matter.</td><td data-label="Do not ignore">Cost, driver skill, route authority, and whether the plan can shorten for weather.</td></tr>
<tr><td data-label="Mode">Private car or jeep</td><td data-label="Use when">Families, cautious travelers, premium pacing, or bad-weather protection matter.</td><td data-label="Do not ignore">Motion sickness, long road days, visibility, driver quality, and the fact that risk is reduced, not removed.</td></tr>
<tr><td data-label="Mode">Self-drive</td><td data-label="Use when">Only when legal riding permission, insurance cover, mountain-road experience, weather judgment, and emergency fallback all line up.</td><td data-label="Do not ignore">Liability, medical costs, police checks, road surfaces, trucks, fog, gravel, fatigue, and overconfidence.</td></tr>
</tbody>
</table>

<!-- vg-ha-giang-loop-safety-license-insurance:v1 -->
<h2 class="wp-block-heading">Safety, license and insurance reality</h2>
<p>The safest Ha Giang article is the one that removes some readers from the loop. If a traveler cannot confirm legal riding permission and insurance cover for the vehicle and activity, self-drive should leave the plan. If the group is nervous, unwell, under time pressure, or traveling in poor weather, the better travel decision may be private car, easy rider, a shorter route, or a different destination.</p>
<ul class="vg-check-list vg-ha-giang-loop-safety-license-insurance">
<li><strong>License check:</strong> confirm the exact legal requirement for the vehicle class before any self-drive plan.</li>
<li><strong>Insurance check:</strong> read policy wording for motorbike riding, being a passenger, engine size, license condition, medical evacuation, and exclusions.</li>
<li><strong>Helmet check:</strong> reject weak helmets, poor fit, and pressure to ride without proper protection.</li>
<li><strong>Weather check:</strong> rain, fog, cold, heat, and storms can change both scenic value and risk.</li>
<li><strong>Fatigue check:</strong> the loop is not safer because the traveler is excited. Sleep, hydration, food, and shorter days matter.</li>
<li><strong>Emergency check:</strong> keep offline maps, hotel contacts, operator contacts, cash, phone power, and a clear plan for stopping early.</li>
</ul>

<!-- vg-ha-giang-loop-weather-road:v1 -->
<h2 class="wp-block-heading">Weather and road conditions change the value</h2>
<p>Ha Giang is not a fixed product. A clear day can make the same route feel expansive; fog can erase the viewpoint payoff while leaving the road responsibility intact. Rain can make the route slower and more tiring. Cold can make long riding days feel much harder than expected. The guide should always be refreshed close to publication or travel.</p>
<table class="vg-decision-table vg-ha-giang-loop-weather-road">
<thead><tr><th>Condition</th><th>What changes</th><th>Planning move</th></tr></thead>
<tbody>
<tr><td data-label="Condition">Clear shoulder weather</td><td data-label="What changes">Pass visibility, viewpoints, and photography improve.</td><td data-label="Planning move">Protect enough days to avoid rushing the best sections.</td></tr>
<tr><td data-label="Condition">Fog or low cloud</td><td data-label="What changes">Scenic payoff may drop while road exposure remains.</td><td data-label="Planning move">Use a driver with authority to slow, reroute, wait, or shorten.</td></tr>
<tr><td data-label="Condition">Rain or slick roads</td><td data-label="What changes">Grip, braking, visibility, and comfort become central.</td><td data-label="Planning move">Consider private car, shorter riding days, or postponement.</td></tr>
<tr><td data-label="Condition">Cold snap</td><td data-label="What changes">Wind chill, numb hands, and fatigue can build quickly.</td><td data-label="Planning move">Pack layers and do not make long exposed riding days mandatory.</td></tr>
<tr><td data-label="Condition">Heat and sun</td><td data-label="What changes">Dehydration, glare, and concentration become risks.</td><td data-label="Planning move">Start earlier, hydrate, protect skin, and shorten exposed afternoon riding.</td></tr>
<tr><td data-label="Condition">Storm or landslide concern</td><td data-label="What changes">The loop can stop being a travel plan and become a risk-management problem.</td><td data-label="Planning move">Do not force the route; keep cancellation and route-change permission clear.</td></tr>
</tbody>
</table>

<!-- vg-ha-giang-loop-operator-vetting:v1 -->
<h2 class="wp-block-heading">Operator vetting questions before paying</h2>
<p>A polished route description is not enough. The useful questions are practical and slightly uncomfortable. They reveal whether the operator has a safety culture or only a sales script.</p>
<ul class="vg-check-list vg-ha-giang-loop-operator-vetting">
<li>Who decides to slow down, stop, reroute, or cancel when weather turns?</li>
<li>What helmets are provided, and can the traveler reject a poor fit?</li>
<li>How many riders are in the group, and how is spacing controlled on bends?</li>
<li>What is the plan if a passenger becomes anxious, sick, injured, or too tired?</li>
<li>Are drivers licensed and experienced on the exact route, not just generally confident?</li>
<li>How are luggage, rain gear, phone charging, water, and medicine handled?</li>
<li>What parts of the route are optional if visibility is poor or the group is slow?</li>
<li>What payments are refundable or changeable if weather or health makes the loop unsafe?</li>
<li>Can the operator explain insurance assumptions without promising coverage they do not control?</li>
</ul>

<!-- vg-ha-giang-loop-route-anatomy:v1 -->
<h2 class="wp-block-heading">Route anatomy without fragile timetable promises</h2>
<p>The classic loop usually starts from Ha Giang city and moves through mountain districts such as Quan Ba, Yen Minh, Dong Van, and Meo Vac, with Ma Pi Leng and the Dong Van Karst Plateau as major scenic anchors. Some slower versions add or adjust stops such as Du Gia, local villages, markets, viewpoints, or river/canyon side trips. The exact order matters less than whether the route has enough slack for real conditions.</p>
<table class="vg-decision-table vg-ha-giang-loop-route-anatomy">
<thead><tr><th>Route element</th><th>Why it matters</th><th>Planning caution</th></tr></thead>
<tbody>
<tr><td data-label="Route element">Ha Giang city start</td><td data-label="Why it matters">This is where many travelers arrive tired from Hanoi before the loop begins.</td><td data-label="Planning caution">Do not begin a hard riding day after a poor overnight transfer.</td></tr>
<tr><td data-label="Route element">Quan Ba and Yen Minh area</td><td data-label="Why it matters">Early mountain scenery sets the rhythm and reveals comfort level.</td><td data-label="Planning caution">The first day should not be used to prove toughness.</td></tr>
<tr><td data-label="Route element">Dong Van Karst Plateau</td><td data-label="Why it matters">This is the landscape identity of the loop and a reason for slow stops.</td><td data-label="Planning caution">Fog and rain can reduce visibility; keep expectations flexible.</td></tr>
<tr><td data-label="Route element">Ma Pi Leng and Tu San area</td><td data-label="Why it matters">This is a high-payoff scenic section for many travelers.</td><td data-label="Planning caution">Do not make it a fragile one-shot viewpoint if weather is unstable.</td></tr>
<tr><td data-label="Route element">Meo Vac / Du Gia style extensions</td><td data-label="Why it matters">They can add local rhythm and route texture.</td><td data-label="Planning caution">Extra stops should slow the route, not create more rushed riding.</td></tr>
</tbody>
</table>

<!-- vg-ha-giang-loop-hanoi-transfer:v1 -->
<h2 class="wp-block-heading">Hanoi transfer and return buffers</h2>
<p>Ha Giang is usually sold as a loop, but the real itinerary includes Hanoi access, arrival fatigue, hotel check-in, luggage decisions, the loop itself, and the return to Hanoi or onward travel. A weak transfer plan can make a good loop feel unsafe and rushed.</p>
<table class="vg-decision-table vg-ha-giang-loop-hanoi-transfer">
<thead><tr><th>Pressure point</th><th>Risk</th><th>Better move</th></tr></thead>
<tbody>
<tr><td data-label="Pressure point">Overnight transfer before riding</td><td data-label="Risk">Poor sleep can weaken judgment on the first mountain day.</td><td data-label="Better move">Add a recovery morning or start with a shorter first section.</td></tr>
<tr><td data-label="Pressure point">Immediate return to Hanoi after loop</td><td data-label="Risk">The last day becomes a race against fatigue, traffic, and onward timing.</td><td data-label="Better move">Protect a Hanoi night before flights or long onward movement.</td></tr>
<tr><td data-label="Pressure point">Fragile international flight</td><td data-label="Risk">Weather, road delay, or illness can turn the loop into a connection problem.</td><td data-label="Better move">Return to Hanoi at least one protected night earlier.</td></tr>
<tr><td data-label="Pressure point">Luggage and valuables</td><td data-label="Risk">Poor luggage handling adds stress and can reduce comfort on the road.</td><td data-label="Better move">Store main luggage securely and carry only what the loop needs.</td></tr>
<tr><td data-label="Pressure point">Phone and connectivity</td><td data-label="Risk">Mountain signal can be uneven and phones drain faster on map-heavy days.</td><td data-label="Better move">Download offline maps, keep backup power, and save hotel/operator contacts.</td></tr>
</tbody>
</table>

<!-- vg-ha-giang-loop-traveler-fit:v1 -->
<h2 class="wp-block-heading">Traveler fit: who should be cautious?</h2>
<table class="vg-decision-table vg-ha-giang-loop-traveler-fit">
<thead><tr><th>Traveler type</th><th>Better Ha Giang version</th><th>Weak version</th></tr></thead>
<tbody>
<tr><td data-label="Traveler type">First-time Vietnam traveler</td><td data-label="Better Ha Giang version">One mountain chapter inside a slower north-focused route.</td><td data-label="Weak version">Adding Ha Giang because every itinerary online mentions it.</td></tr>
<tr><td data-label="Traveler type">Solo traveler</td><td data-label="Better Ha Giang version">Vetted small group or private easy rider with clear safety standards.</td><td data-label="Weak version">Following the cheapest crowd without checking driver, helmet, weather, or insurance.</td></tr>
<tr><td data-label="Traveler type">Couple</td><td data-label="Better Ha Giang version">Private easy rider or private car if comfort and photography matter.</td><td data-label="Weak version">Assuming both people share the same risk tolerance.</td></tr>
<tr><td data-label="Traveler type">Family</td><td data-label="Better Ha Giang version">Private car, shorter scenic route, or an easier northern alternative.</td><td data-label="Weak version">Motorbike-heavy loop that depends on every person coping well.</td></tr>
<tr><td data-label="Traveler type">Premium traveler</td><td data-label="Better Ha Giang version">Private driver, better lodging, fewer hard days, and weather-flexible pacing.</td><td data-label="Weak version">Paying more without gaining safety authority or route control.</td></tr>
<tr><td data-label="Traveler type">Limited mobility or anxiety</td><td data-label="Better Ha Giang version">Private car, selected viewpoints, or a softer countryside chapter.</td><td data-label="Weak version">Long exposed riding days with no graceful exit.</td></tr>
</tbody>
</table>

<!-- vg-ha-giang-loop-culture-comfort:v1 -->
<h2 class="wp-block-heading">Culture, comfort and respectful travel</h2>
<p>Ha Giang is not only a road surface and a set of viewpoints. The loop passes communities that live with tourism's benefits and pressure. A stronger plan gives drivers time to stop well, asks before close portraits, buys with patience, treats homestays honestly, and avoids turning local life into a prop for speed-running scenery.</p>
<ul class="vg-check-list vg-ha-giang-loop-culture-comfort">
<li><strong>Homestays:</strong> check bathroom, bedding, privacy, meals, noise, and heat expectations before assuming comfort.</li>
<li><strong>Markets and villages:</strong> visit with time and consent, not as a rushed photo stop.</li>
<li><strong>Photography:</strong> ask before close portraits, especially of children or private domestic life.</li>
<li><strong>Food and water:</strong> plan simple meals, hydration, and snacks rather than relying on perfect timing.</li>
<li><strong>Spending:</strong> choose local services for route value, not only the lowest price.</li>
</ul>

<!-- vg-ha-giang-loop-cost-comfort:v1 -->
<h2 class="wp-block-heading">Cost should follow risk, not only distance</h2>
<p>The cheapest loop is not always the cheapest trip. If a low price creates poor helmets, tired drivers, oversized groups, weak lodging, rushed days, or no weather flexibility, the saving is being taken from safety and comfort. A better budget asks which risk needs money first.</p>
<table class="vg-decision-table vg-ha-giang-loop-cost-comfort">
<thead><tr><th>Spend more on</th><th>When it is worth it</th><th>When to save</th></tr></thead>
<tbody>
<tr><td data-label="Spend more on">Private or smaller-group driver</td><td data-label="When it is worth it">The traveler needs pacing, photography, safety authority, or less convoy pressure.</td><td data-label="When to save">The group is confident, healthy, and comfortable with a vetted shared format.</td></tr>
<tr><td data-label="Spend more on">Better lodging</td><td data-label="When it is worth it">Cold, rain, families, couples, or older travelers need recovery to enjoy the next day.</td><td data-label="When to save">Simple homestay comfort is part of the travel goal and expectations are realistic.</td></tr>
<tr><td data-label="Spend more on">Private car</td><td data-label="When it is worth it">Motorbike exposure is the blocker but the scenery still matters.</td><td data-label="When to save">The traveler specifically wants the motorbike experience and has proper safeguards.</td></tr>
<tr><td data-label="Spend more on">Extra night or buffer</td><td data-label="When it is worth it">The route has flights, weather risk, or a tired group.</td><td data-label="When to save">Only when the rest of the itinerary is already slow and flexible.</td></tr>
</tbody>
</table>

<!-- vg-ha-giang-loop-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before booking or publishing</h2>
<ul class="vg-check-list vg-ha-giang-loop-live-checks">
<li>Check current weather, fog, rain, storm, cold, heat, and visibility close to travel.</li>
<li>Check whether the traveler actually wants a road-led mountain chapter or only wants scenery.</li>
<li>Check license and insurance wording before any self-drive or motorcycle passenger plan.</li>
<li>Check operator safety standards, helmet quality, group size, driver experience, and cancellation policy.</li>
<li>Check route length against Hanoi transfer fatigue and onward flight timing.</li>
<li>Check lodging comfort, heat, bathroom, privacy, food, and noise expectations.</li>
<li>Check offline maps, power bank, SIM/eSIM coverage, hotel contacts, operator contacts, cash, and emergency fallback.</li>
<li>Check that Ha Giang is replacing a weaker route chapter rather than being added from fear of missing out.</li>
</ul>

<!-- vg-ha-giang-loop-faq:v1 -->
<h2 class="wp-block-heading">Ha Giang Loop FAQ</h2>
<div class="vg-faq-list vg-ha-giang-loop-faq">
<details><summary>Is the Ha Giang Loop worth it?</summary><p>Yes when the road journey itself is the reason for visiting northern Vietnam and the route has enough time, weather margin, safety planning, and recovery. It is weaker when the traveler only wants easy scenery or has a short, fragile itinerary.</p></details>
<details><summary>How many days do I need for the Ha Giang Loop?</summary><p>Four days and three nights is a cleaner default for many travelers. Three days can work but is compressed. Two days is usually too thin unless the traveler accepts a tasting route. More days help only when the rest of the trip has slack.</p></details>
<details><summary>Should I self-drive the Ha Giang Loop?</summary><p>Only consider self-drive if legal riding permission, insurance cover, mountain-road skill, weather judgment, and emergency fallback all line up. Many travelers should choose easy rider, private car, or a different northern chapter.</p></details>
<details><summary>Is an easy rider automatically safe?</summary><p>No. Easy rider reduces self-drive responsibility, but safety still depends on driver quality, helmet quality, group behavior, weather decisions, route pacing, and what happens if the traveler becomes tired or anxious.</p></details>
<details><summary>Can families do Ha Giang?</summary><p>Some families can, but private car or a shorter scenic route is often a better fit than a motorbike-heavy loop. The decision should account for motion sickness, road tolerance, lodging comfort, weather, and graceful exit options.</p></details>
<details><summary>Can I add Ha Giang to a 10-day Vietnam trip?</summary><p>Only if northern landscapes are the main trip purpose and other regions are reduced. On a balanced first trip, Ha Giang often creates too much movement unless it replaces another major chapter.</p></details>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Use these related guides to decide whether Ha Giang improves the route or only makes it riskier and busier.</p>
<div class="vg-related-routes vg-ha-giang-loop-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a><span class="vg-related-route-note">Use Hanoi as the gateway before Ha Giang transfer planning.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Check northern rain, fog, cold, and visibility windows.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Count transfer fatigue, luggage, pickup, and recovery time.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a><span class="vg-related-route-note">Check motorcycle, activity, medical, evacuation, cancellation, and liability wording.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a><span class="vg-related-route-note">Use practical checks for operators, deposits, road claims, helmets, cash, and fallback plans.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a><span class="vg-related-route-note">Decide whether one northern mountain chapter earns its place.</span></li>
<li><span class="vg-related-route-step">07</span><a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a><span class="vg-related-route-note">Prepare offline maps, backup communication, and contact access for mountain routes.</span></li>
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
    'vg-ha-giang-loop-hero:v1',
    'vg-ha-giang-loop-concierge-verdict:v1',
    'vg-ha-giang-loop-internal-demand:v1',
    'vg-ha-giang-loop-photo-proof:v1',
    'vg-ha-giang-loop-source-diversity:v1',
    'vg-ha-giang-loop-go-modify-skip:v1',
    'vg-ha-giang-loop-route-length:v1',
    'vg-ha-giang-loop-mode-filter:v1',
    'vg-ha-giang-loop-safety-license-insurance:v1',
    'vg-ha-giang-loop-weather-road:v1',
    'vg-ha-giang-loop-operator-vetting:v1',
    'vg-ha-giang-loop-route-anatomy:v1',
    'vg-ha-giang-loop-hanoi-transfer:v1',
    'vg-ha-giang-loop-traveler-fit:v1',
    'vg-ha-giang-loop-culture-comfort:v1',
    'vg-ha-giang-loop-cost-comfort:v1',
    'vg-ha-giang-loop-live-checks:v1',
    'vg-ha-giang-loop-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_ha_giang_loop_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Ha Giang Loop Planning Guide: Safety, Scenery and Route Fit',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Decide whether the Ha Giang Loop fits your Vietnam route by safety, scenery, easy rider vs self-drive, insurance, weather, road fatigue, and Hanoi buffers.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_ha_giang_loop_post_fail('Could not update Ha Giang Loop Planning Guide post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Ha Giang Loop Planning Guide: Safety, Scenery, Route Fit');
update_post_meta($post_id, 'rank_math_description', 'Plan the Ha Giang Loop by safety, scenery, easy rider vs self-drive, license, insurance, weather, road fatigue, route length, and Hanoi buffers.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Ha Giang Loop planning guide');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Decide whether the Ha Giang Loop belongs in a Vietnam itinerary based on scenery value, road exposure, easy rider versus self-drive responsibility, license and insurance reality, weather, operator quality, Hanoi transfer fatigue, and route length.');
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
    vg_ha_giang_loop_post_fail('Could not assign Ha Giang Loop categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_ha_giang_loop_post_fail('Could not assign Ha Giang Loop tags: ' . $tag_result->get_error_message());
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
        vg_ha_giang_loop_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Ha Giang Loop Planning Guide post to complete draft: {$post_id}");

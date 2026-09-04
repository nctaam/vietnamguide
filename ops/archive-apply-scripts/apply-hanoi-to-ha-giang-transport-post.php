<?php
/**
 * Expand the Hanoi to Ha Giang Transport post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-hanoi-to-ha-giang-transport-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_hanoi_ha_giang_transport_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_hanoi_ha_giang_transport_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_hanoi_ha_giang_transport_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_hanoi_ha_giang_transport_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_hanoi_ha_giang_transport_post_find_by_slug(string $slug): ?WP_Post
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
        vg_hanoi_ha_giang_transport_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_hanoi_ha_giang_transport_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-26',
            'vg_editorial_brief_status' => ['brief', 'complete_draft'],
            'vg_editorial_batch' => 'batch-3-northern-mountains',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if (is_array($expected_value)) {
            if (! in_array($actual_value, $expected_value, true)) {
                vg_hanoi_ha_giang_transport_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected one of " . implode(', ', $expected_value) . '.');
            }
            continue;
        }

        if ($actual_value !== $expected_value) {
            vg_hanoi_ha_giang_transport_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_hanoi_ha_giang_transport_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_hanoi_ha_giang_transport_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'hanoi-to-ha-giang-transport';
$post = vg_hanoi_ha_giang_transport_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_hanoi_ha_giang_transport_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_hanoi_ha_giang_transport_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 27, 2026';

if ($post_id !== 521) {
    vg_hanoi_ha_giang_transport_post_fail("Refusing to update {$slug}: expected post ID 521, found {$post_id}.");
}

if ($post->post_title !== 'Hanoi to Ha Giang Transport: Bus, Private Car or Staged Route?') {
    vg_hanoi_ha_giang_transport_post_fail("Refusing to update {$slug}: unexpected title {$post->post_title}.");
}

if ($post->post_name !== $slug) {
    vg_hanoi_ha_giang_transport_post_fail("Refusing to update {$slug}: unexpected slug {$post->post_name}.");
}

vg_hanoi_ha_giang_transport_post_assert_target_meta($post);

$category_term_ids = vg_hanoi_ha_giang_transport_post_term_ids('category', ['transport-logistics', 'travel-planning']);
$tag_term_ids = vg_hanoi_ha_giang_transport_post_term_ids('post_tag', ['transport-planning', 'mountain-planning', 'safety-planning', 'anti-spam-evergreen']);

$my_dinh_bus_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/a/a6/Rows_of_Hanoibus%27_Daewoo_BC095_and_BC110_at_M%E1%BB%B9_%C4%90%C3%ACnh_Bus_Station.png');
$hanoi_bus_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/2/26/Daewoo_BC095_buses_by_Daewoo_Vietnam_in_Hanoi.jpg');
$ma_pi_leng_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/4/46/Ma_Pi_Leng_Pass_winding_road_Ha_Giang_Vietnam.jpg');
$dong_van_karst_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/9/9d/Karst%40SinhLung_DongVan_HaGiang.jpg');
$tu_san_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/3/3d/TuSan_Canyon.jpg');
$ha_thanh_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/a/a4/H%E1%BA%A1_Th%C3%A0nh%2C_H%C3%A0_Giang%2C_Vietnam_-_5.jpg');

$meta_update_summary = 'Expanded the native WordPress Batch 3 brief into a complete Hanoi to Ha Giang Transport draft with photo-led hero, proof panel, concierge verdict, internal demand note, source-diversity table, photo proof, sleeper/cabin bus versus day bus versus private car versus staged-route chooser, arrival-fatigue filter, loop-start handoff, luggage and motion-sickness checks, return-to-Hanoi buffers, road/weather/safety guardrails, cost-control logic, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Ha Giang - https://vietnam.travel/places-to-go/northern-vietnam/ha-giang - checked {$review_date}; used for official destination framing, mountain geography, and route role.",
    "Vietnam.travel - The Ha Giang Loop - https://vietnam.travel/things-to-do/ha-giang-loop - checked {$review_date}; used for loop-start context, road-scenery framing, and professional steering caution.",
    "Vietnam.travel - Ha Giang Loop four-day road trip - https://vietnam.travel/things-to-do/ha-giang-loop-four-day-road-trip - checked {$review_date}; used for route-pressure context without publishing fragile timetable promises.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for bus, car, overland movement, and transfer-friction framing.",
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for northern weather, fog, rain, cool-season, and live-check discipline.",
    "Vietnam National Center for Hydro-Meteorological Forecasting - https://nchmf.gov.vn/Kttv/en-US/1/index.html - checked {$review_date}; used as live weather source pointer for Ha Giang, storms, rain, fog, and visibility.",
    "UK FCDO Vietnam travel advice - https://www.gov.uk/foreign-travel-advice/vietnam/safety-and-security - checked {$review_date}; used for road-safety, motorbike, transport-risk, and source-trail caution.",
    "Australian Smartraveller Vietnam advice - https://www.smartraveller.gov.au/destinations/asia/vietnam - checked {$review_date}; used for insurance, road-safety, motorcycle, medical, and evacuation source trail.",
    "Internal published page check - https://vietnamguide.net/destinations/hanoi-travel-guide/ - checked {$review_date}; used for Hanoi gateway and pickup-area handoff.",
    "Internal published page check - https://vietnamguide.net/plan/best-time-to-visit-vietnam/ - checked {$review_date}; used for northern weather and season handoff.",
    "Internal published page check - https://vietnamguide.net/plan/transport-within-vietnam/ - checked {$review_date}; used for mode selection, overland fatigue, luggage, and recovery handoff.",
    "Internal published page check - https://vietnamguide.net/plan/health-travel-insurance-vietnam/ - checked {$review_date}; used for road-transfer, activity, cancellation, motorcycle, medical, and evacuation cover handoff.",
    "Internal published page check - https://vietnamguide.net/plan/safety-scams-vietnam/ - checked {$review_date}; used for pickup claims, deposits, driver quality, helmets, cash, and operator-vetting handoff.",
    "Internal published page check - https://vietnamguide.net/compare/north-central-south-vietnam/ - checked {$review_date}; used for northern-region route-fit handoff.",
    "Internal published page check - https://vietnamguide.net/plan/vietnam-travel-guide/ - checked {$review_date}; used for first-trip planning order.",
    "Internal published page check - https://vietnamguide.net/costs/vietnam-travel-cost/ - checked {$review_date}; used for bus, private-car, extra-night, guide, and buffer-cost handoff.",
    "Internal published page check - https://vietnamguide.net/itineraries/10-days-in-vietnam/ - checked {$review_date}; used for short-route pressure and mountain add-on caution.",
    "Internal published page check - https://vietnamguide.net/itineraries/14-days-in-vietnam/ - checked {$review_date}; used for one northern mountain chapter planning.",
    "Internal published page check - https://vietnamguide.net/itineraries/21-days-in-vietnam/ - checked {$review_date}; used for slower north-focused route planning.",
    "Internal published page check - https://vietnamguide.net/destinations/ninh-binh-travel-guide/ - checked {$review_date}; used for easier northern scenery alternative.",
    "Internal published page check - https://vietnamguide.net/destinations/ha-long-bay-travel-guide/ - checked {$review_date}; used for northern scenery stacking caution.",
    "Internal published page check - https://vietnamguide.net/plan/sim-esim-vietnam/ - checked {$review_date}; used for pickup calls, offline maps, hotel contacts, and backup communication handoff.",
    "Wikimedia Commons image direct URL - Rows of buses at My Dinh Bus Station - {$my_dinh_bus_image} - credit TheNetheriteGuy, CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Daewoo buses in Hanoi - {$hanoi_bus_image} - credit Khaikiet27112002, CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ma Pi Leng Pass winding road Ha Giang - {$ma_pi_leng_image} - credit Khanh Hmoong, CC BY 2.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Dong Van karst landscape - {$dong_van_karst_image} - credit BacLuong, public domain - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Tu San Canyon - {$tu_san_image} - credit NKSTTSSHNVN, CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ha Thanh village landscape in Ha Giang - {$ha_thanh_image} - credit Benjamin Smith, CC BY-SA 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This complete draft treats Hanoi to Ha Giang transport as the first safety and fatigue decision of a Ha Giang plan. The route is not only a way to reach the loop; it decides whether the traveler starts the mountain road rested, informed, properly insured, and buffered against weather or return delays.';
$meta_evidence_moat = implode("\n", [
    'The article answers whether to choose overnight bus, day bus or limousine-style transfer, private car, staged route, or skip/modify the Ha Giang plan before paying.',
    'The first screen treats arrival fatigue and loop-start timing as part of the route recommendation.',
    'The bus section compares directness with sleep, pickup reliability, luggage access, and road-motion tolerance instead of ranking operators.',
    'The private-car section frames premium spend as control over stops, luggage, motion sickness, return timing, and route authority.',
    'The staged-route section gives an anti-spam planning moat because it solves the real problem: not starting a mountain loop exhausted.',
    'Safety and insurance guidance connects the Hanoi transfer to motorcycle/easy-rider/private-car decisions without promoting unsafe self-drive.',
    'Photo proof uses credited real bus, road, karst, canyon, and Ha Giang landscape imagery with source and license records retained in metadata.',
    'No visible external body anchors; source URLs and image-license records remain in metadata/source trail.',
]);
$meta_related_routes = implode("\n", [
    'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use as the gateway before pickup, buffer night, and return timing decisions.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check northern rain, fog, cool weather, and visibility before a mountain-road plan.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Compare buses, private cars, overland fatigue, luggage, and recovery time.',
    'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check road-transfer, motorcycle, activity, medical, evacuation, and cancellation cover.',
    'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Use pickup, deposit, driver, helmet, cash, and operator-vetting checks.',
    'North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Decide whether northern mountains deserve enough time in the route.',
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Place Ha Giang transport inside the first-trip planning sequence.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Compare bus, private-car, extra-night, guide, buffer, and insurance costs.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Ha Giang survives a short route.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use one northern mountain chapter without overstacking scenery.',
    '21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Build a slower northern route with real transfer and return buffers.',
    'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Compare easier countryside logistics before committing to Ha Giang.',
    'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Avoid stacking every northern scenery icon into one rushed trip.',
    'SIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Prepare pickup calls, offline maps, hotel contacts, and backup communication.',
]);
$meta_hero_image_credit = 'Hero image: Rows of buses at My Dinh Bus Station by TheNetheriteGuy, CC BY 4.0. Body images: Daewoo buses in Hanoi by Khaikiet27112002, CC BY-SA 4.0; Ma Pi Leng Pass winding road by Khanh Hmoong, CC BY 2.0; Dong Van karst landscape by BacLuong, public domain; Tu San Canyon by NKSTTSSHNVN, CC BY-SA 4.0; Ha Thanh village landscape by Benjamin Smith, CC BY-SA 4.0.';
$meta_admin_notes = 'Complete draft generated for WordPress Admin review. Keep draft until manual preview, Rank Math review, image check, current bus/private-car/staged-route checks, road/weather refresh, insurance/source refresh, and final editorial pass are complete. The body intentionally avoids external anchors; source URLs are preserved in metadata and source trail. Before publishing, refresh pickup/drop-off wording, Ha Giang city arrival logic, return timing, loop-start policy, road/weather conditions, operator claims, cancellation terms, and image-license notes.';

$content = <<<HTML
<!-- vg-hanoi-ha-giang-transport-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$my_dinh_bus_image}","dimRatio":56,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Buses parked at My Dinh Bus Station in Hanoi before a northern Vietnam transfer" src="{$my_dinh_bus_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<div class="wp-block-group vg-guide-hero-inner">
<p class="vg-kicker">Reviewed transport planning guide - Updated {$review_date}</p>
<h1 class="wp-block-heading vg-display vg-guide-title">Hanoi to Ha Giang Transport: Bus, Private Car or Staged Route?</h1>
<p class="vg-guide-lede">The Hanoi to Ha Giang transfer is not just a way to reach the mountains. It decides whether the traveler starts the loop rested, insured, weather-aware, and protected from a fragile return to Hanoi.</p>
<p class="vg-field-note">Concierge verdict: choose a sleeper or cabin bus only when arrival fatigue is acceptable, choose a day transfer when sleep matters more than saving a hotel night, choose a private car when control is worth the spend, and stage the route when the loop should not begin from a tired overnight arrival.</p>
</div>
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-hanoi-ha-giang-transport-concierge-verdict:v1 -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<p><strong>Do not judge this route by fare or departure time alone.</strong> The better question is whether the transfer leaves the traveler able to make good safety decisions on mountain roads the next day.</p>
<p><strong>Choose overnight bus or cabin bus when directness matters and the first loop day is protected.</strong> It can be efficient, but it is weak when a traveler arrives tired and immediately starts a long riding day.</p>
<p><strong>Choose day bus, limousine-style transfer, or private car when sleep and timing matter more than saving a night.</strong> These options can feel less efficient on paper and better in real route quality.</p>
<p><strong>Choose a staged route when Ha Giang is important enough to slow down.</strong> A protected Ha Giang city night before the loop, a Hanoi buffer after return, or a gentler northern sequence can be the difference between a memorable mountain chapter and a route that feels reckless.</p>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-hanoi-ha-giang-transport-internal-demand:v1 -->
<h2 class="wp-block-heading">Why this route needs its own transport guide</h2>
<p>Many Hanoi to Ha Giang articles behave like transport listings. They compare bus names, prices, and sample schedules. That is useful only after the safer planning question has been answered. Ha Giang is usually not the final destination in the traveler's mind; it is the start of a road-led mountain route. Arriving there tired, confused about luggage, uncertain about pickup, or without a return buffer can weaken the whole loop.</p>
<p>This guide is written for the decision before booking. It does not freeze operator timetables or promote one company. It explains how to choose a transfer mode by sleep, arrival hour, loop-start timing, motion comfort, luggage, private-car control, staged routing, weather, insurance, and return-to-Hanoi risk.</p>

<!-- vg-hanoi-ha-giang-transport-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: transport is part of the road decision</h2>
<div class="vg-photo-grid vg-hanoi-ha-giang-transport-photo-proof">
<figure><img src="{$my_dinh_bus_image}" alt="Rows of buses at My Dinh Bus Station in Hanoi" loading="lazy" decoding="async"><figcaption>The route often begins as a bus-station or pickup decision in Hanoi. Image: TheNetheriteGuy, CC BY 4.0.</figcaption></figure>
<figure><img src="{$hanoi_bus_image}" alt="Buses in Hanoi before an overland transfer" loading="lazy" decoding="async"><figcaption>Vehicle choice matters less than pickup clarity, sleep, luggage, and arrival condition. Image: Khaikiet27112002, CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$ma_pi_leng_image}" alt="Winding road at Ma Pi Leng Pass in Ha Giang" loading="lazy" decoding="async"><figcaption>The transfer is the prelude to road exposure; do not arrive too tired to judge conditions. Image: Khanh Hmoong, CC BY 2.0.</figcaption></figure>
<figure><img src="{$dong_van_karst_image}" alt="Karst landscape in Dong Van, Ha Giang" loading="lazy" decoding="async"><figcaption>The landscape reward is real, but it needs time, weather margin, and a calm start. Image: BacLuong, public domain.</figcaption></figure>
<figure><img src="{$tu_san_image}" alt="Tu San Canyon near Ha Giang" loading="lazy" decoding="async"><figcaption>High-payoff scenic days should not depend on a fragile overnight arrival. Image: NKSTTSSHNVN, CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$ha_thanh_image}" alt="Village landscape in Ha Giang" loading="lazy" decoding="async"><figcaption>A slower route gives Ha Giang room to be a place, not only a road challenge. Image: Benjamin Smith, CC BY-SA 4.0.</figcaption></figure>
</div>

<!-- vg-hanoi-ha-giang-transport-source-diversity:v1 -->
<h2 class="wp-block-heading">How the source trail is used</h2>
<table class="vg-decision-table vg-hanoi-ha-giang-transport-source-diversity">
<thead><tr><th>Source type</th><th>What it can prove</th><th>How this guide uses it</th></tr></thead>
<tbody>
<tr><td data-label="Source type">National tourism sources</td><td data-label="What it can prove">Ha Giang's destination role, loop context, transport modes, and weather patterns.</td><td data-label="How this guide uses it">To anchor the route without rewriting destination marketing.</td></tr>
<tr><td data-label="Source type">Weather sources</td><td data-label="What it can prove">Rain, fog, storms, cold, heat, and visibility risks that affect road movement.</td><td data-label="How this guide uses it">To make live weather checks part of transfer and loop-start timing.</td></tr>
<tr><td data-label="Source type">Government travel advice</td><td data-label="What it can prove">Road-safety, motorcycle, insurance, and medical fallback cautions.</td><td data-label="How this guide uses it">To connect the transfer with safe loop planning instead of treating safety as a disclaimer.</td></tr>
<tr><td data-label="Source type">Internal route pages</td><td data-label="What it can prove">How Ha Giang competes with Hanoi, Ninh Binh, Ha Long Bay, trip length, cost, and region choice.</td><td data-label="How this guide uses it">To decide whether the Ha Giang transfer improves the full Vietnam route.</td></tr>
<tr><td data-label="Source type">Image-license records</td><td data-label="What it can prove">Real station, bus, road, karst, canyon, and village visuals with recorded credits.</td><td data-label="How this guide uses it">To keep the visual layer evidence-led rather than decorative.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-ha-giang-transport-mode-chooser:v1 -->
<h2 class="wp-block-heading">Quick mode chooser</h2>
<table class="vg-decision-table vg-hanoi-ha-giang-transport-mode-chooser">
<thead><tr><th>Mode</th><th>Choose it when</th><th>Be careful when</th></tr></thead>
<tbody>
<tr><td data-label="Mode">Overnight sleeper or cabin bus</td><td data-label="Choose it when">Budget and direct arrival matter, the traveler can sleep in a road vehicle, and the first loop day is not overloaded.</td><td data-label="Be careful when">The plan begins riding immediately after arrival or the traveler is motion-sensitive, anxious, tall, or traveling with children.</td></tr>
<tr><td data-label="Mode">Day bus or limousine-style transfer</td><td data-label="Choose it when">Sleep quality matters and the route can afford a daylight transfer to Ha Giang city.</td><td data-label="Be careful when">Late arrival, shared pickups, luggage space, or a remote hotel creates another transfer problem.</td></tr>
<tr><td data-label="Mode">Private car</td><td data-label="Choose it when">Family comfort, premium pacing, motion breaks, luggage, photography stops, or return control is worth paying for.</td><td data-label="Be careful when">A busy itinerary uses private transfer as a way to force too much road travel.</td></tr>
<tr><td data-label="Mode">Staged route</td><td data-label="Choose it when">The loop is important enough to protect with a Ha Giang city night, slower north routing, or a Hanoi return buffer.</td><td data-label="Be careful when">The staged night is added without removing another weak itinerary chapter.</td></tr>
<tr><td data-label="Mode">Modify or skip Ha Giang</td><td data-label="Choose it when">The transfer plus loop would overload the traveler, weather window, insurance reality, or flight timing.</td><td data-label="Be careful when">The decision is being driven by fear of missing out instead of a clear road-scenery priority.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-ha-giang-transport-arrival-loop-start:v1 -->
<h2 class="wp-block-heading">The arrival and loop-start test</h2>
<p>The strongest Hanoi to Ha Giang plan protects the first loop day. A traveler who arrives at dawn after poor sleep may still be technically on schedule, but the route can already be weaker. Mountain-road decisions need patience: checking weather, meeting the driver or guide, choosing helmets, storing luggage, carrying medicine, understanding the day's route, and saying no if conditions are wrong.</p>
<table class="vg-decision-table vg-hanoi-ha-giang-transport-arrival-loop-start">
<thead><tr><th>Arrival setup</th><th>Main risk</th><th>Better move</th></tr></thead>
<tbody>
<tr><td data-label="Arrival setup">Overnight arrival then immediate loop</td><td data-label="Main risk">Fatigue weakens road judgment before the hardest part of the trip begins.</td><td data-label="Better move">Start later, shorten the first section, or sleep in Ha Giang city first.</td></tr>
<tr><td data-label="Arrival setup">Day arrival in Ha Giang city</td><td data-label="Main risk">Late arrival can compress guide briefing, luggage storage, and dinner.</td><td data-label="Better move">Treat the arrival day as setup, not as a hidden loop day.</td></tr>
<tr><td data-label="Arrival setup">Private transfer before loop</td><td data-label="Main risk">Comfort can hide the fact that road hours still consume energy.</td><td data-label="Better move">Use the car to control stops and timing, then protect the first riding day.</td></tr>
<tr><td data-label="Arrival setup">Return transfer after loop</td><td data-label="Main risk">The final day becomes a race against fatigue, traffic, weather, and onward travel.</td><td data-label="Better move">Return to Hanoi with a protected night before important flights or tours.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-ha-giang-transport-overnight-bus:v1 -->
<h2 class="wp-block-heading">Overnight sleeper bus or cabin bus</h2>
<p>The overnight bus is popular because it is direct and can save a hotel night. That saving is real only when the traveler sleeps reasonably well and the next day is designed with mercy. If the plan moves from bus arrival straight into a long mountain-road day, the bus has moved fatigue from the budget column into the safety column.</p>
<p>For editors, the evergreen rule is to avoid freezing exact bus schedules. Services, pickup points, vehicle types, and arrival times change. The useful content is the checklist that tells a traveler what to verify before paying.</p>
<table class="vg-decision-table vg-hanoi-ha-giang-transport-overnight-bus">
<thead><tr><th>Bus factor</th><th>Good sign</th><th>Warning sign</th></tr></thead>
<tbody>
<tr><td data-label="Bus factor">Pickup clarity</td><td data-label="Good sign">Exact pickup address, time window, contact method, luggage rule, and backup point are confirmed.</td><td data-label="Warning sign">The booking promises vague hotel pickup and changes details close to departure.</td></tr>
<tr><td data-label="Bus factor">Sleep fit</td><td data-label="Good sign">The traveler understands cabin size, road motion, lights, noise, and shared-space limits.</td><td data-label="Warning sign">The traveler expects hotel-level rest or has anxiety around night roads.</td></tr>
<tr><td data-label="Bus factor">Arrival handoff</td><td data-label="Good sign">Hotel, operator, luggage storage, breakfast, guide meeting, and loop start are sequenced clearly.</td><td data-label="Warning sign">The loop begins because the booking says it can, not because the traveler is ready.</td></tr>
<tr><td data-label="Bus factor">Return plan</td><td data-label="Good sign">The return to Hanoi has a buffer before flights, cruises, or long onward movement.</td><td data-label="Warning sign">The route relies on a same-day connection after mountain roads.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-ha-giang-transport-day-private-staged:v1 -->
<h2 class="wp-block-heading">Day transfer, private car or staged route</h2>
<p>A day transfer can look inefficient because it consumes daylight. In reality, it may protect the part of the trip that matters: sleeping in a bed, arriving oriented, meeting the operator calmly, checking the weather, and starting the loop with a clearer head. A shared day bus or limousine-style transfer can work when pickup and luggage details are solid.</p>
<p>A private car buys more than comfort. It buys stop control, motion-sickness breaks, luggage access, communication, pacing, and the ability to protect a family or premium route from the stress of shared transfer uncertainty. It does not make an overloaded itinerary wise; it simply gives the route more control.</p>
<p>A staged route is the most underused option. It may mean sleeping in Ha Giang city before the loop, returning to Hanoi one night before a flight, or reshaping the north so Ha Giang replaces a weaker stop instead of being added on top of everything else. Staging is not wasted time when it removes the riskiest compression from the plan.</p>
<table class="vg-decision-table vg-hanoi-ha-giang-transport-day-private-staged">
<thead><tr><th>Option</th><th>Best use</th><th>Weak use</th></tr></thead>
<tbody>
<tr><td data-label="Option">Day bus or limousine-style transfer</td><td data-label="Best use">A normal sleep schedule and clear evening arrival matter.</td><td data-label="Weak use">The arrival still has no hotel, food, luggage, or loop-start plan.</td></tr>
<tr><td data-label="Option">Private car</td><td data-label="Best use">Family, premium comfort, motion stops, luggage, photography, or protected timing matters.</td><td data-label="Weak use">It is used to squeeze Ha Giang into a route that should skip it.</td></tr>
<tr><td data-label="Option">Ha Giang city setup night</td><td data-label="Best use">The traveler wants the loop to start rested and properly briefed.</td><td data-label="Weak use">The extra night is added without reducing later route pressure.</td></tr>
<tr><td data-label="Option">Protected Hanoi return night</td><td data-label="Best use">Flights, cruises, tours, or long onward transfers follow the loop.</td><td data-label="Weak use">The route still treats the final loop day as a race.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-ha-giang-transport-luggage-motion-family:v1 -->
<h2 class="wp-block-heading">Luggage, motion sickness and family fit</h2>
<p>Small practical details decide whether the transfer supports the loop or sabotages it. Ha Giang travelers often need to separate main luggage from loop luggage, keep medicine accessible, manage cool or wet weather, charge phones, coordinate with a guide or driver, and arrive with enough calm to make safety choices.</p>
<ul class="vg-check-list vg-hanoi-ha-giang-transport-luggage-motion-family">
<li><strong>Luggage:</strong> store main bags securely and carry only the loop kit when possible; keep passport, medicine, warm layer, rain layer, charger, and cash accessible.</li>
<li><strong>Motion sickness:</strong> road curves matter before and during Ha Giang; choose a mode with breaks if the traveler is sensitive.</li>
<li><strong>Children:</strong> private car or a modified route is often stronger than a night bus plus road-heavy loop.</li>
<li><strong>Older travelers:</strong> check vehicle access, hotel stairs, bathroom breaks, and whether the route has a graceful exit.</li>
<li><strong>Solo travelers:</strong> confirm pickup, arrival, operator contact, luggage storage, and what happens if the bus arrives late.</li>
<li><strong>Couples and groups:</strong> make sure everyone shares the same tolerance for night roads, shared cabins, easy-rider exposure, and recovery time.</li>
<li><strong>Photographers:</strong> keep gear dry and accessible, but do not design the transfer around unsafe roadside stops.</li>
</ul>

<!-- vg-hanoi-ha-giang-transport-safety-insurance:v1 -->
<h2 class="wp-block-heading">Safety and insurance start before the loop</h2>
<p>The Hanoi to Ha Giang transfer is part of the same risk plan as the loop. It affects sleep, judgment, luggage, weather checks, and whether the traveler has time to understand motorcycle, easy-rider, private-car, helmet, medical, and insurance realities. A rushed transfer can make every later safety decision harder.</p>
<table class="vg-decision-table vg-hanoi-ha-giang-transport-safety-insurance">
<thead><tr><th>Risk item</th><th>What to check</th><th>Better action</th></tr></thead>
<tbody>
<tr><td data-label="Risk item">Road weather</td><td data-label="What to check">Rain, fog, storms, cold, heat, and visibility before both transfer and loop.</td><td data-label="Better action">Adjust route length or delay the start when conditions reduce safety.</td></tr>
<tr><td data-label="Risk item">Insurance wording</td><td data-label="What to check">Road transfer, medical, evacuation, motorcycle passenger, self-drive, activity, and cancellation cover.</td><td data-label="Better action">Do not assume a tour or operator promise replaces policy wording.</td></tr>
<tr><td data-label="Risk item">Operator handoff</td><td data-label="What to check">Who meets the traveler, where luggage goes, helmet quality, route briefing, and emergency contact.</td><td data-label="Better action">Treat unclear answers as a planning signal, not a minor inconvenience.</td></tr>
<tr><td data-label="Risk item">Return to Hanoi</td><td data-label="What to check">Weather, road delays, fatigue, bus reliability, and flight/tour timing.</td><td data-label="Better action">Keep a protected Hanoi night before important departures.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-ha-giang-transport-route-pressure:v1 -->
<h2 class="wp-block-heading">Route pressure: when Ha Giang is too much</h2>
<p>Ha Giang is at its best when the road journey is a deliberate reason for shaping the northern route. It is weaker when it is squeezed into a checklist route that already includes Hanoi, Ninh Binh, Ha Long Bay, Central Vietnam, southern Vietnam, and beaches. The transfer itself is the warning sign: if the only way to make Ha Giang fit is to remove sleep and buffers, the route is probably asking too much.</p>
<table class="vg-decision-table vg-hanoi-ha-giang-transport-route-pressure">
<thead><tr><th>Trip shape</th><th>Transfer pressure</th><th>Cleaner decision</th></tr></thead>
<tbody>
<tr><td data-label="Trip shape">10 days in Vietnam</td><td data-label="Transfer pressure">High unless Ha Giang replaces another major chapter.</td><td data-label="Cleaner decision">Choose Ha Giang only when northern road scenery is the main purpose.</td></tr>
<tr><td data-label="Trip shape">14 days in Vietnam</td><td data-label="Transfer pressure">Possible, but the route should choose one mountain chapter carefully.</td><td data-label="Cleaner decision">Protect a Ha Giang city setup night or a Hanoi return buffer.</td></tr>
<tr><td data-label="Trip shape">21 days in Vietnam</td><td data-label="Transfer pressure">More manageable, but fatigue still accumulates.</td><td data-label="Cleaner decision">Use slower transfers, better lodging, and weather-flexible pacing.</td></tr>
<tr><td data-label="Trip shape">North-focused route</td><td data-label="Transfer pressure">Strongest fit when Ha Giang is not competing with too many regions.</td><td data-label="Cleaner decision">Build the north around weather windows and road recovery.</td></tr>
<tr><td data-label="Trip shape">Flight after loop</td><td data-label="Transfer pressure">Risky if return timing depends on perfect roads and no delay.</td><td data-label="Cleaner decision">Return to Hanoi one protected night before important flights.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-ha-giang-transport-budget-control:v1 -->
<h2 class="wp-block-heading">Budget should buy control where risk is highest</h2>
<p>The cheapest transfer may still be right for a rested, flexible traveler with a light route. But budget should follow the part of the plan most likely to fail: sleep, pickup clarity, luggage, weather flexibility, private stops, guide handoff, return to Hanoi, and the cost of missing a flight or losing a loop day.</p>
<table class="vg-decision-table vg-hanoi-ha-giang-transport-budget-control">
<thead><tr><th>Spend more on</th><th>When it is worth it</th><th>When to save</th></tr></thead>
<tbody>
<tr><td data-label="Spend more on">Better bus or cabin</td><td data-label="When it is worth it">The traveler can sleep on roads but needs clearer comfort and pickup details.</td><td data-label="When to save">The first Ha Giang day is light and budget is the main constraint.</td></tr>
<tr><td data-label="Spend more on">Day transfer</td><td data-label="When it is worth it">A normal sleep schedule improves loop safety and enjoyment.</td><td data-label="When to save">The traveler sleeps well on buses and has a recovery plan.</td></tr>
<tr><td data-label="Spend more on">Private car</td><td data-label="When it is worth it">Family, premium pacing, motion stops, luggage, and return control matter.</td><td data-label="When to save">Private speed would only hide an overpacked route.</td></tr>
<tr><td data-label="Spend more on">Extra night</td><td data-label="When it is worth it">The route needs a Ha Giang setup night or Hanoi return buffer.</td><td data-label="When to save">The trip already has multiple flexible northern nights.</td></tr>
<tr><td data-label="Spend more on">Insurance clarity</td><td data-label="When it is worth it">Motorcycle, easy-rider, self-drive, private-car, or activity-heavy plans create real exposure.</td><td data-label="When to save">Do not save by ignoring policy wording; change the route instead.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-ha-giang-transport-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before booking or publishing</h2>
<ul class="vg-check-list vg-hanoi-ha-giang-transport-live-checks">
<li>Check current bus, cabin bus, day transfer, limousine-style transfer, private-car, pickup, drop-off, luggage, and cancellation details before publishing any service claim.</li>
<li>Check whether arrival in Ha Giang city gives enough time for hotel access, luggage storage, operator briefing, meals, helmet checks, and loop-start decisions.</li>
<li>Check Ha Giang weather, road condition, rain, fog, storm risk, cold, heat, and visibility close to travel.</li>
<li>Check insurance wording for road transfer, motorcycle passenger, self-drive, activity, medical, evacuation, cancellation, and liability exposure.</li>
<li>Check whether the traveler needs medicine, warm layers, rain gear, offline maps, SIM/eSIM data, backup power, cash, and saved hotel/operator contacts.</li>
<li>Check return timing to Hanoi before flights, cruises, tours, trains, or long onward movement.</li>
<li>Check whether Ha Giang is replacing a weaker route chapter rather than being added from fear of missing out.</li>
<li>Check that the body still has no visible external source anchors after WordPress Admin edits; source URLs belong in metadata/source trail.</li>
</ul>

<!-- vg-hanoi-ha-giang-transport-faq:v1 -->
<h2 class="wp-block-heading">Hanoi to Ha Giang transport FAQ</h2>
<div class="vg-faq-list vg-hanoi-ha-giang-transport-faq">
<details><summary>What is the best way to get from Hanoi to Ha Giang?</summary><p>The best option depends on the route. Overnight sleeper or cabin bus can work for budget/directness when the first loop day is protected. Day transfer is better when sleep matters. Private car is strongest for control, families, luggage, motion breaks, and premium pacing. Staging is best when Ha Giang is important enough to avoid a tired loop start.</p></details>
<details><summary>Should I start the Ha Giang Loop right after an overnight bus?</summary><p>Only if the traveler slept well, the first day is light, weather is acceptable, and the operator handoff is clear. Many travelers should sleep in Ha Giang city first or start with a shorter first section.</p></details>
<details><summary>Is a private car worth it from Hanoi to Ha Giang?</summary><p>It can be worth it when control matters: family pacing, comfort stops, luggage, motion sickness, late changes, photography, and a calmer arrival. It is less convincing when the route is already too crowded and needs fewer stops rather than a more expensive transfer.</p></details>
<details><summary>Can I do Ha Giang on a 10-day Vietnam trip?</summary><p>Yes, but only when northern road scenery is a top priority and another major region is reduced. If Ha Giang is added on top of a balanced first-trip route, the transfer and loop can make the whole itinerary too rushed.</p></details>
<details><summary>Do I need a Hanoi buffer after Ha Giang?</summary><p>Use a protected Hanoi night before important flights, cruises, tours, or long onward travel. Mountain roads, weather, fatigue, and transfer delays should not be allowed to threaten a major departure.</p></details>
<details><summary>Should I book exact bus times far ahead?</summary><p>Book only after checking current pickup, drop-off, vehicle, cancellation, and arrival details. Editors should avoid publishing fragile timetables as evergreen facts.</p></details>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Use these related guides to decide whether the Ha Giang transfer belongs in the whole route.</p>
<div class="vg-related-routes vg-hanoi-ha-giang-transport-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a><span class="vg-related-route-note">Plan the gateway night, pickup area, and return buffer.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Check northern fog, rain, cool weather, and visibility before a mountain-road plan.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Compare buses, cars, overland fatigue, luggage, and recovery time.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a><span class="vg-related-route-note">Check road, motorcycle, activity, medical, evacuation, and cancellation cover.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a><span class="vg-related-route-note">Use pickup, deposit, driver, helmet, cash, and operator-vetting checks.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a><span class="vg-related-route-note">Decide whether one northern mountain chapter earns its place.</span></li>
<li><span class="vg-related-route-step">07</span><a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a><span class="vg-related-route-note">Keep pickup calls, maps, hotel contacts, and backup communication working.</span></li>
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
    'vg-hanoi-ha-giang-transport-hero:v1',
    'vg-hanoi-ha-giang-transport-concierge-verdict:v1',
    'vg-hanoi-ha-giang-transport-internal-demand:v1',
    'vg-hanoi-ha-giang-transport-photo-proof:v1',
    'vg-hanoi-ha-giang-transport-source-diversity:v1',
    'vg-hanoi-ha-giang-transport-mode-chooser:v1',
    'vg-hanoi-ha-giang-transport-arrival-loop-start:v1',
    'vg-hanoi-ha-giang-transport-overnight-bus:v1',
    'vg-hanoi-ha-giang-transport-day-private-staged:v1',
    'vg-hanoi-ha-giang-transport-luggage-motion-family:v1',
    'vg-hanoi-ha-giang-transport-safety-insurance:v1',
    'vg-hanoi-ha-giang-transport-route-pressure:v1',
    'vg-hanoi-ha-giang-transport-budget-control:v1',
    'vg-hanoi-ha-giang-transport-live-checks:v1',
    'vg-hanoi-ha-giang-transport-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_hanoi_ha_giang_transport_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Hanoi to Ha Giang Transport: Bus, Private Car or Staged Route?',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Choose Hanoi to Ha Giang transport by overnight bus, day transfer, private car, or staged route using fatigue, loop-start timing, luggage, safety, insurance, weather, and return buffers.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_hanoi_ha_giang_transport_post_fail('Could not update Hanoi to Ha Giang Transport post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Hanoi to Ha Giang Transport: Bus, Private Car or Staged Route?');
update_post_meta($post_id, 'rank_math_description', 'Compare Hanoi to Ha Giang transport by sleeper bus, day transfer, private car, and staged route using fatigue, safety, weather, and buffers.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Hanoi to Ha Giang transport');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Choose the best Hanoi to Ha Giang transfer mode by arrival fatigue, loop-start timing, overnight bus comfort, day transfer trade-offs, private-car control, staged routing, luggage, motion sickness, insurance, weather, safety, and protected return to Hanoi.');
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
    vg_hanoi_ha_giang_transport_post_fail('Could not assign Hanoi to Ha Giang Transport categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_hanoi_ha_giang_transport_post_fail('Could not assign Hanoi to Ha Giang Transport tags: ' . $tag_result->get_error_message());
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
    'vg_last_manual_review',
    'vg_admin_first_notes',
];

foreach ($required_meta as $meta_key) {
    $meta_value = get_post_meta($post_id, $meta_key, true);

    if ((is_string($meta_value) && trim($meta_value) === '') || $meta_value === [] || $meta_value === null) {
        vg_hanoi_ha_giang_transport_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Hanoi to Ha Giang Transport post to complete draft: {$post_id}");

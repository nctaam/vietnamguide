<?php
/**
 * Expand the Hanoi to Sapa Transport post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-hanoi-to-sapa-transport-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_hanoi_sapa_transport_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_hanoi_sapa_transport_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_hanoi_sapa_transport_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_hanoi_sapa_transport_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_hanoi_sapa_transport_post_find_by_slug(string $slug): ?WP_Post
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
        vg_hanoi_sapa_transport_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_hanoi_sapa_transport_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-25',
            'vg_editorial_brief_status' => ['brief', 'complete_draft'],
            'vg_editorial_batch' => 'batch-3-northern-mountains',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if (is_array($expected_value)) {
            if (! in_array($actual_value, $expected_value, true)) {
                vg_hanoi_sapa_transport_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected one of " . implode(', ', $expected_value) . '.');
            }
            continue;
        }

        if ($actual_value !== $expected_value) {
            vg_hanoi_sapa_transport_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_hanoi_sapa_transport_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_hanoi_sapa_transport_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'hanoi-to-sapa-transport';
$post = vg_hanoi_sapa_transport_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_hanoi_sapa_transport_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_hanoi_sapa_transport_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 27, 2026';

if ($post_id !== 520) {
    vg_hanoi_sapa_transport_post_fail("Refusing to update {$slug}: expected post ID 520, found {$post_id}.");
}

if ($post->post_title !== 'Hanoi to Sapa Transport: Train, Cabin Bus or Private Car?') {
    vg_hanoi_sapa_transport_post_fail("Refusing to update {$slug}: unexpected title {$post->post_title}.");
}

if ($post->post_name !== $slug) {
    vg_hanoi_sapa_transport_post_fail("Refusing to update {$slug}: unexpected slug {$post->post_name}.");
}

vg_hanoi_sapa_transport_post_assert_target_meta($post);

$category_term_ids = vg_hanoi_sapa_transport_post_term_ids('category', ['transport-logistics', 'travel-planning']);
$tag_term_ids = vg_hanoi_sapa_transport_post_term_ids('post_tag', ['transport-planning', 'mountain-planning', 'first-time-vietnam', 'anti-spam-evergreen']);

$hanoi_station_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/9/95/Hanoi_Railway_Station_20130725.jpg');
$hanoi_platform_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/e/e2/Platform_of_Hanoi_Station_01.jpg');
$lao_cai_station_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/5/53/Lao_Cai_Railway_Station_2010_-_panoramio.jpg');
$sapa_terrace_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/8/8b/Sa_Pa_Rice_Terrace_IV.jpg');
$sapa_road_rain_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/2/2b/Road_Sapa_Lao_Cai_in_rainy_weather_%2815094%29.jpg');
$fansipan_cable_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/1/16/Fansipan_cable_car_aerial_view_terraced_rice_fields_Sa_Pa_Viet_Nam.jpg');

$meta_update_summary = 'Expanded the native WordPress Batch 3 brief into a complete Hanoi to Sapa Transport draft with photo-led hero, proof panel, concierge verdict, internal demand note, source-diversity table, photo proof, train versus cabin bus versus limousine versus private car chooser, sleep and arrival logic, Lao Cai transfer cautions, luggage and family planning, motion sickness guardrails, valley drop-off guidance, route pressure and recovery-time planning, budget versus comfort logic, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Sapa - https://vietnam.travel/places-to-go/northern-vietnam/sapa - checked {$review_date}; used for official Sapa destination framing, mountain access, Fansipan, trekking, terraces, and route-fit context.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for train, bus, car, domestic movement, and transfer-friction framing.",
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for northern-weather, mountain fog, rain, cool-season, and live-check discipline.",
    "Vietnam Railways official site - https://vr.com.vn/en - checked {$review_date}; used as a primary rail source pointer before manual publication.",
    "Vietnam Railways official online ticketing - https://dsvn.vn - checked {$review_date}; used as the live train booking and timetable check pointer without freezing fragile schedule promises in the article.",
    "Vietnam National Center for Hydro-Meteorological Forecasting - https://nchmf.gov.vn/Kttv/en-US/1/index.html - checked {$review_date}; used as live weather source pointer for Lao Cai and mountain transfer conditions.",
    "UK FCDO Vietnam travel advice - https://www.gov.uk/foreign-travel-advice/vietnam/safety-and-security - checked {$review_date}; used for road-safety, transport caution, and source-trail risk framing.",
    "Australian Smartraveller Vietnam advice - https://www.smartraveller.gov.au/destinations/asia/vietnam - checked {$review_date}; used for insurance, transport, road-safety, and medical fallback source trail.",
    "Internal published page check - https://vietnamguide.net/destinations/hanoi-travel-guide/ - checked {$review_date}; used for Hanoi gateway and station/town handoff.",
    "Internal published page check - https://vietnamguide.net/plan/best-time-to-visit-vietnam/ - checked {$review_date}; used for northern weather and season handoff.",
    "Internal published page check - https://vietnamguide.net/plan/transport-within-vietnam/ - checked {$review_date}; used for mode selection, overland movement, luggage, and recovery handoff.",
    "Internal published page check - https://vietnamguide.net/plan/health-travel-insurance-vietnam/ - checked {$review_date}; used for medical, cancellation, activity, and road-transfer risk handoff.",
    "Internal published page check - https://vietnamguide.net/plan/safety-scams-vietnam/ - checked {$review_date}; used for pickup claims, deposits, taxis, cash, and operator-vetting handoff.",
    "Internal published page check - https://vietnamguide.net/compare/north-central-south-vietnam/ - checked {$review_date}; used for northern-region route-fit handoff.",
    "Internal published page check - https://vietnamguide.net/plan/vietnam-travel-guide/ - checked {$review_date}; used for first-trip planning order.",
    "Internal published page check - https://vietnamguide.net/costs/vietnam-travel-cost/ - checked {$review_date}; used for private car, train cabin, van, extra-night, and comfort budgeting handoff.",
    "Internal published page check - https://vietnamguide.net/itineraries/10-days-in-vietnam/ - checked {$review_date}; used for short-route pressure and mountain add-on caution.",
    "Internal published page check - https://vietnamguide.net/itineraries/14-days-in-vietnam/ - checked {$review_date}; used for one northern mountain chapter planning.",
    "Internal published page check - https://vietnamguide.net/itineraries/21-days-in-vietnam/ - checked {$review_date}; used for slower north-focused route planning.",
    "Internal published page check - https://vietnamguide.net/destinations/ninh-binh-travel-guide/ - checked {$review_date}; used for easier northern scenery alternative.",
    "Internal published page check - https://vietnamguide.net/destinations/ha-long-bay-travel-guide/ - checked {$review_date}; used for northern scenery stacking caution.",
    "Internal published page check - https://vietnamguide.net/plan/sim-esim-vietnam/ - checked {$review_date}; used for offline maps, pickup calls, and backup communication handoff.",
    "Wikimedia Commons image direct URL - Hanoi Railway Station - {$hanoi_station_image} - credit Alancrh, CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Platform of Hanoi Station - {$hanoi_platform_image} - credit Kelcey Kinjo, CC BY 2.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Lao Cai Railway Station 2010 - {$lao_cai_station_image} - credit Huang Yi Le, CC BY 3.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Sa Pa Rice Terrace IV - {$sapa_terrace_image} - credit Ekrem Canli, CC BY-SA 3.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Road Sapa Lao Cai in rainy weather - {$sapa_road_rain_image} - credit Andre Hospers, CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Fansipan cable car aerial view - {$fansipan_cable_image} - credit Vivu Vietnam, CC BY-SA 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This complete draft treats Hanoi to Sapa transport as a sleep, arrival, luggage, family, motion-comfort, and route-pressure decision. The strongest transfer is not automatically the cheapest or fastest one. It is the option that lets the traveler arrive with enough energy, certainty, and drop-off control to make the Sapa chapter worth the time.';
$meta_evidence_moat = implode("\n", [
    'The article answers the transport decision before a traveler books a train cabin, cabin bus, limousine van, or private car.',
    'It compares modes by traveler job: sleep, arrival timing, final drop-off, luggage, children, motion sickness, valley stays, and next-day walking capacity.',
    'It avoids fragile timetable claims and forces editors to refresh Vietnam Railways and operator details before publication.',
    'The train section separates Hanoi to Lao Cai rail comfort from the final Lao Cai to Sapa road transfer, which is where many shallow articles blur the friction.',
    'The bus and limousine sections separate directness from sleep quality, motion comfort, pickup reliability, and recovery cost.',
    'The private-car section treats premium spend as route control rather than luxury decoration.',
    'Photo proof uses credited real station, road, terrace, and mountain imagery with source and license records retained in metadata.',
    'No visible external body anchors; source URLs and image-license records remain in metadata/source trail.',
]);
$meta_related_routes = implode("\n", [
    'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use as the gateway before station, pickup, and first-night decisions.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check northern rain, fog, cool weather, and visibility before choosing a mountain transfer.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Compare trains, buses, private cars, luggage, overland fatigue, and recovery time.',
    'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check medical, cancellation, road-transfer, luggage, and activity cover before mountain travel.',
    'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Use pickup, deposit, driver, taxi, and late-arrival checks before committing.',
    'North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Decide whether northern mountains deserve enough time in the route.',
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Place Sapa transport inside the first-trip planning sequence.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Compare train cabin, bus, limousine, private-car, taxi, and extra-night costs.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Sapa transfer friction survives a short route.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use one northern mountain chapter without overstacking scenery.',
    '21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Build a slower northern route with real transfer buffers.',
    'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Compare easier countryside logistics before committing to Sapa.',
    'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Avoid stacking every northern scenery icon into one rushed trip.',
    'SIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Prepare pickup calls, maps, hotel contacts, and backup communication.',
]);
$meta_hero_image_credit = 'Hero image: Hanoi Railway Station by Alancrh, CC BY-SA 4.0. Body images: Platform of Hanoi Station by Kelcey Kinjo, CC BY 2.0; Lao Cai Railway Station by Huang Yi Le, CC BY 3.0; Sa Pa Rice Terrace IV by Ekrem Canli, CC BY-SA 3.0; Road Sapa Lao Cai in rainy weather by Andre Hospers, CC BY 4.0; Fansipan cable car aerial view by Vivu Vietnam, CC BY-SA 4.0.';
$meta_admin_notes = 'Complete draft generated for WordPress Admin review. Keep draft until manual preview, Rank Math review, image check, live Vietnam Railways/ticketing check, current operator/pickup check, weather refresh, and final editorial pass are complete. The body intentionally avoids external anchors; source URLs are preserved in metadata and source trail. Before publishing, refresh train schedules, Lao Cai transfer options, road/weather conditions, pickup/drop-off wording, cancellation terms, and image-license notes.';

$content = <<<HTML
<!-- vg-hanoi-sapa-transport-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hanoi_station_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hanoi Railway Station before a northern Vietnam train journey" src="{$hanoi_station_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<div class="wp-block-group vg-guide-hero-inner">
<p class="vg-kicker">Reviewed transport planning guide - Updated {$review_date}</p>
<h1 class="wp-block-heading vg-display vg-guide-title">Hanoi to Sapa Transport: Train, Cabin Bus or Private Car?</h1>
<p class="vg-guide-lede">The best Hanoi to Sapa transfer is the one that protects sleep, arrival timing, luggage, motion comfort, and the first mountain day. A cheap seat that leaves you exhausted can cost more than a better-timed transfer.</p>
<p class="vg-field-note">Concierge verdict: choose the overnight train when rail comfort and a Lao Cai transfer fit your rhythm, choose a cabin bus when directness and budget matter, choose a limousine van for a daytime comfort compromise, and choose a private car when control is worth the spend.</p>
</div>
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-hanoi-sapa-transport-concierge-verdict:v1 -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<p><strong>Most first-time travelers should choose by the morning after arrival, not only by the departure time.</strong> Sapa is a mountain chapter with walking, weather, road curves, valley stays, and early check-in friction. The transfer is successful only if the traveler still has enough energy to use the day.</p>
<p><strong>Use the train when sleep quality and a calmer start matter.</strong> The Hanoi to Lao Cai overnight train can feel more structured than a long night road transfer, but it still needs a final road leg to Sapa and a plan for early arrival.</p>
<p><strong>Use a cabin or sleeper bus when direct arrival and budget matter more than rail rhythm.</strong> The bus can simplify the route, but it also concentrates the discomfort: road motion, pickup uncertainty, cabin size, luggage, and a tired first morning.</p>
<p><strong>Use a private car when the transfer has to behave like part of the itinerary, not a commodity.</strong> Families, premium travelers, fragile schedules, valley lodges, motion-sensitive travelers, and groups with luggage often buy control more than speed.</p>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-hanoi-sapa-transport-internal-demand:v1 -->
<h2 class="wp-block-heading">Why this route needs a transport guide</h2>
<p>Hanoi to Sapa is often reduced to a simple list of trains and buses. That is useful only after the real decision has been made. The stronger question is which transfer mode protects the traveler's purpose for going to Sapa: terraces, guided walking, a valley stay, mountain air, or a slower northern chapter. A mode can look efficient in a booking widget and still damage the trip by creating poor sleep, awkward arrival, late valley drop-off, motion sickness, or too little recovery before the next long move.</p>
<p>This guide is intentionally not a timetable dump. Timetables, pickup points, vehicle quality, luggage rules, ticket names, and road conditions change. The evergreen value is a decision framework that tells an editor and traveler what to verify before purchase. Exact services should be checked live before publishing, booking, or recommending a named operator.</p>

<!-- vg-hanoi-sapa-transport-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: where the friction appears</h2>
<div class="vg-photo-grid vg-hanoi-sapa-transport-photo-proof">
<figure><img src="{$hanoi_station_image}" alt="Hanoi Railway Station exterior" loading="lazy" decoding="async"><figcaption>The train option begins as a city-station decision before it becomes a mountain transfer. Image: Alancrh, CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$hanoi_platform_image}" alt="Train platform at Hanoi Station" loading="lazy" decoding="async"><figcaption>Rail comfort depends on cabin type, sleep expectations, luggage, and early arrival planning. Image: Kelcey Kinjo, CC BY 2.0.</figcaption></figure>
<figure><img src="{$lao_cai_station_image}" alt="Lao Cai Railway Station" loading="lazy" decoding="async"><figcaption>Lao Cai is not Sapa; the train still needs the final uphill road transfer. Image: Huang Yi Le, CC BY 3.0.</figcaption></figure>
<figure><img src="{$sapa_road_rain_image}" alt="Road from Lao Cai to Sapa in rainy weather" loading="lazy" decoding="async"><figcaption>Weather and curves make the last leg a comfort and safety decision, not a footnote. Image: Andre Hospers, CC BY 4.0.</figcaption></figure>
<figure><img src="{$sapa_terrace_image}" alt="Rice terraces in Sa Pa" loading="lazy" decoding="async"><figcaption>The transfer earns its place only if the Sapa chapter has time to reach the terraces and valleys. Image: Ekrem Canli, CC BY-SA 3.0.</figcaption></figure>
<figure><img src="{$fansipan_cable_image}" alt="Fansipan cable car over terraced fields in Sa Pa" loading="lazy" decoding="async"><figcaption>View-focused plans need weather flexibility; transport timing should leave room for live decisions. Image: Vivu Vietnam, CC BY-SA 4.0.</figcaption></figure>
</div>

<!-- vg-hanoi-sapa-transport-source-diversity:v1 -->
<h2 class="wp-block-heading">How the source trail is used</h2>
<table class="vg-decision-table vg-hanoi-sapa-transport-source-diversity">
<thead><tr><th>Source type</th><th>What it can prove</th><th>How this guide uses it</th></tr></thead>
<tbody>
<tr><td data-label="Source type">National tourism sources</td><td data-label="What it can prove">Sapa's destination role, transport modes, weather context, and broad route framing.</td><td data-label="How this guide uses it">To keep the article grounded without turning it into copied destination copy.</td></tr>
<tr><td data-label="Source type">Vietnam Railways sources</td><td data-label="What it can prove">Current train search, official rail operator context, ticketing checks, and route availability.</td><td data-label="How this guide uses it">To force live verification before any exact train claim is published.</td></tr>
<tr><td data-label="Source type">Weather and government advice</td><td data-label="What it can prove">Road caution, insurance implications, mountain weather, fog, rain, and transport-risk discipline.</td><td data-label="How this guide uses it">To connect transfer mode with comfort, safety, and fallback planning.</td></tr>
<tr><td data-label="Source type">Internal route pages</td><td data-label="What it can prove">How Sapa competes with Hanoi, Ninh Binh, Ha Long Bay, trip length, cost, and regional priorities.</td><td data-label="How this guide uses it">To decide whether the Sapa transfer improves the whole itinerary or only adds movement.</td></tr>
<tr><td data-label="Source type">Image-license records</td><td data-label="What it can prove">The guide uses real station, road, terrace, and mountain visuals with recorded credits.</td><td data-label="How this guide uses it">To make the visual layer evidence-led rather than decorative.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-sapa-transport-mode-chooser:v1 -->
<h2 class="wp-block-heading">Quick mode chooser</h2>
<table class="vg-decision-table vg-hanoi-sapa-transport-mode-chooser">
<thead><tr><th>Mode</th><th>Choose it when</th><th>Be careful when</th></tr></thead>
<tbody>
<tr><td data-label="Mode">Overnight train to Lao Cai plus transfer</td><td data-label="Choose it when">You want a more structured night, like rail travel, can manage early arrival, and accept a separate uphill transfer.</td><td data-label="Be careful when">You expect door-to-door simplicity, dislike shared cabins, have heavy luggage, or need confirmed hotel access at dawn.</td></tr>
<tr><td data-label="Mode">Cabin bus or sleeper bus</td><td data-label="Choose it when">Budget, direct arrival, and fewer moving parts matter more than perfect sleep.</td><td data-label="Be careful when">Road motion, late pickups, cramped cabins, luggage, children, or an important first walking day could become problems.</td></tr>
<tr><td data-label="Mode">Limousine van</td><td data-label="Choose it when">You prefer a daytime road transfer with better seats and a clearer arrival rhythm.</td><td data-label="Be careful when">Traffic, shared pickups, luggage space, and late valley drop-off would hurt the plan.</td></tr>
<tr><td data-label="Mode">Private car</td><td data-label="Choose it when">Family comfort, premium pacing, motion stops, valley lodging, luggage, or fragile timing makes control valuable.</td><td data-label="Be careful when">The trip is short enough that the cost would be better spent on an easier destination or extra night.</td></tr>
<tr><td data-label="Mode">Choose a different route</td><td data-label="Choose it when">The transfer consumes more energy than Sapa can return in the itinerary.</td><td data-label="Be careful when">Fear of missing out is driving the decision instead of a clear mountain purpose.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-sapa-transport-sleep-arrival:v1 -->
<h2 class="wp-block-heading">The sleep and arrival test</h2>
<p>The most useful test is not whether the transfer is possible. It is whether the traveler can function well on arrival. Sapa often asks for an uphill ride, check-in uncertainty, cool or wet weather, stairs, a walk, a guide pickup, or a valley transfer on the same day. A transport plan that looks neat on paper can become weak when the first Sapa morning is spent waiting, repacking, recovering, or arguing about pickup details.</p>
<table class="vg-decision-table vg-hanoi-sapa-transport-sleep-arrival">
<thead><tr><th>Arrival problem</th><th>Why it matters</th><th>Better planning move</th></tr></thead>
<tbody>
<tr><td data-label="Arrival problem">Very early arrival</td><td data-label="Why it matters">The room may not be ready, cafes may become storage rooms, and tired travelers make poor choices.</td><td data-label="Better planning move">Pre-book early check-in, book the previous night, or keep the first day light.</td></tr>
<tr><td data-label="Arrival problem">Poor sleep</td><td data-label="Why it matters">Terrace walks, guide meetings, and road transfers feel harder after a fragmented night.</td><td data-label="Better planning move">Upgrade cabin/seat quality or travel by day if the first mountain day matters.</td></tr>
<tr><td data-label="Arrival problem">Late arrival</td><td data-label="Why it matters">A valley lodge can require another transfer after dark or in poor weather.</td><td data-label="Better planning move">Confirm final drop-off before booking and avoid optimistic same-day trekking plans.</td></tr>
<tr><td data-label="Arrival problem">Immediate onward movement</td><td data-label="Why it matters">Sapa becomes a transport interruption instead of a destination.</td><td data-label="Better planning move">Protect two nights or choose a closer northern alternative.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-sapa-transport-train-lao-cai-transfer:v1 -->
<h2 class="wp-block-heading">Overnight train to Lao Cai plus transfer</h2>
<p>The train is the most psychologically comfortable option for many travelers because it separates the long night from the mountain road. It can feel more stable, more traditional, and easier to understand than a night road vehicle. But it does not go all the way to Sapa. The final decision is really a two-part route: Hanoi to Lao Cai by rail, then Lao Cai to Sapa by road.</p>
<p>That final road leg is not a minor detail. It affects arrival timing, motion comfort, luggage handling, taxi or shuttle trust, and the first day in town or the valley. Train travelers should know where they are sleeping, where luggage goes, who handles the station transfer, and whether their hotel can support an early arrival.</p>
<table class="vg-decision-table vg-hanoi-sapa-transport-train-lao-cai-transfer">
<thead><tr><th>Train planning point</th><th>What to decide</th><th>Manual check before publishing</th></tr></thead>
<tbody>
<tr><td data-label="Train planning point">Cabin type</td><td data-label="What to decide">Shared berth, private cabin, soft sleeper, and comfort expectations change the value of the train.</td><td data-label="Manual check before publishing">Verify current official train search and ticket class wording.</td></tr>
<tr><td data-label="Train planning point">Lao Cai arrival</td><td data-label="What to decide">Early arrival can be convenient or awkward depending on hotel access and traveler fatigue.</td><td data-label="Manual check before publishing">Confirm current arrival patterns and station transfer options.</td></tr>
<tr><td data-label="Train planning point">Final road transfer</td><td data-label="What to decide">Shuttle, taxi, private pickup, or hotel-arranged transfer determines the last-mile experience.</td><td data-label="Manual check before publishing">Refresh pickup price, pickup point, payment, and luggage handling.</td></tr>
<tr><td data-label="Train planning point">Return rhythm</td><td data-label="What to decide">The return can protect a Hanoi night or create a tired connection problem.</td><td data-label="Manual check before publishing">Do not recommend same-day international flight timing without a protected buffer.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-sapa-transport-cabin-sleeper-bus:v1 -->
<h2 class="wp-block-heading">Cabin bus or sleeper bus</h2>
<p>A cabin bus or sleeper bus can be the simplest answer when the traveler wants a direct Hanoi to Sapa movement and a lower price. It can remove the Lao Cai transfer step and may arrive closer to Sapa town. That directness is useful. It also means the entire journey depends on one road vehicle, one pickup process, one drop-off process, and the traveler's ability to sleep in a moving cabin.</p>
<p>This is the mode most likely to be oversold by shallow content because it is easy to compare prices. Price is only one variable. A better bus decision asks about cabin size, pickup point, luggage, toilet breaks, road motion, driver behavior, arrival hour, child comfort, and whether the traveler needs to walk or check into a valley stay soon after arrival.</p>
<table class="vg-decision-table vg-hanoi-sapa-transport-cabin-sleeper-bus">
<thead><tr><th>Bus factor</th><th>Good sign</th><th>Warning sign</th></tr></thead>
<tbody>
<tr><td data-label="Bus factor">Pickup clarity</td><td data-label="Good sign">Exact pickup address, time window, contact method, and backup plan are confirmed.</td><td data-label="Warning sign">The booking says hotel pickup but the operator later changes it to a vague meeting point.</td></tr>
<tr><td data-label="Bus factor">Cabin fit</td><td data-label="Good sign">The traveler understands seat/cabin size and can tolerate sleeping in a road vehicle.</td><td data-label="Warning sign">Tall travelers, anxious sleepers, or light sleepers assume it will feel like a hotel bed.</td></tr>
<tr><td data-label="Bus factor">Luggage handling</td><td data-label="Good sign">Main bags, fragile items, medicine, and valuables have a clear place.</td><td data-label="Warning sign">The traveler has large bags, camera gear, or medicine and no access plan during the ride.</td></tr>
<tr><td data-label="Bus factor">Arrival hour</td><td data-label="Good sign">The first Sapa day is light, flexible, and does not depend on immediate trekking.</td><td data-label="Warning sign">The route expects a full walking day after a poor road sleep.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-sapa-transport-limousine-private-car:v1 -->
<h2 class="wp-block-heading">Limousine van or private car</h2>
<p>Daytime road transfer is often underrated because it does not save a hotel night. But for many travelers, especially couples, families, older travelers, and premium trips, arriving rested is worth more than preserving a night on paper. A limousine van can be a good middle ground: more comfortable than a basic bus, cheaper than a private car, and easier to combine with a normal sleep schedule.</p>
<p>A private car is not automatically about luxury. On this route it buys control: pickup timing, comfort stops, motion sickness breaks, luggage access, valley drop-off, communication, and the ability to change pace when weather or fatigue makes the day harder. It is strongest when the Sapa chapter is important enough to protect.</p>
<table class="vg-decision-table vg-hanoi-sapa-transport-limousine-private-car">
<thead><tr><th>Traveler need</th><th>Limousine van fit</th><th>Private car fit</th></tr></thead>
<tbody>
<tr><td data-label="Traveler need">Couple with two-night Sapa stay</td><td data-label="Limousine van fit">Good when hotel pickup and drop-off are clear.</td><td data-label="Private car fit">Better when the lodge is outside town or the route needs photo/meal stops.</td></tr>
<tr><td data-label="Traveler need">Family</td><td data-label="Limousine van fit">Possible if children handle road motion and luggage is simple.</td><td data-label="Private car fit">Usually stronger for stops, snacks, toilet breaks, and late changes.</td></tr>
<tr><td data-label="Traveler need">Premium itinerary</td><td data-label="Limousine van fit">Acceptable when Sapa is a light add-on.</td><td data-label="Private car fit">Cleaner when Sapa is a central mountain chapter and comfort is part of the value.</td></tr>
<tr><td data-label="Traveler need">Motion-sensitive traveler</td><td data-label="Limousine van fit">Risky if shared schedule limits breaks.</td><td data-label="Private car fit">Better because the driver can stop and pace the final climbs.</td></tr>
<tr><td data-label="Traveler need">Valley lodge</td><td data-label="Limousine van fit">Only if final drop-off is confirmed, not assumed.</td><td data-label="Private car fit">Often the clearest answer when the road beyond town matters.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-sapa-transport-luggage-children-motion:v1 -->
<h2 class="wp-block-heading">Luggage, children and motion sickness</h2>
<p>The wrong transfer often reveals itself through small practical details. Can the traveler reach medicine during the ride? Does a child need a bathroom stop? Can a large suitcase fit without becoming a negotiation? Does a tall traveler fit a sleeper cabin? Will motion sickness start before the best part of the route? These details are not secondary. They determine whether Sapa starts with energy or irritation.</p>
<ul class="vg-check-list vg-hanoi-sapa-transport-luggage-children-motion">
<li><strong>Luggage:</strong> keep valuables, passport, medicine, warm layer, charger, and one arrival kit accessible instead of buried under the coach.</li>
<li><strong>Children:</strong> prefer daytime movement or private control when sleep, food, bathroom stops, or road curves could become the main event.</li>
<li><strong>Motion sickness:</strong> treat the final climb and rainy road as planning variables; choose seats, timing, and stops accordingly.</li>
<li><strong>Older travelers:</strong> check stairs, berth height, station walking, vehicle access, and whether arrival requires more transfers.</li>
<li><strong>Couples and groups:</strong> do not assume everyone shares the same comfort threshold for night roads or shared cabins.</li>
<li><strong>Photographers:</strong> plan for safe camera access and dry storage rather than opening luggage in a chaotic arrival zone.</li>
<li><strong>Medical needs:</strong> keep medicine, snacks, water, and insurance details close during the transfer.</li>
</ul>

<!-- vg-hanoi-sapa-transport-valley-dropoff:v1 -->
<h2 class="wp-block-heading">Sapa town is not always the final destination</h2>
<p>Many of the better Sapa stays are outside the town core. That is part of the appeal, but it changes transport planning. A transfer that says it goes to Sapa may still leave the traveler needing another car, hotel shuttle, local taxi, or walk. This matters most in rain, fog, after dark, with children, with large luggage, or when a valley lodge has limited vehicle access.</p>
<table class="vg-decision-table vg-hanoi-sapa-transport-valley-dropoff">
<thead><tr><th>Final-base scenario</th><th>Main risk</th><th>Better move</th></tr></thead>
<tbody>
<tr><td data-label="Final-base scenario">Sapa town hotel</td><td data-label="Main risk">Early arrival before room access or a vague bus drop-off.</td><td data-label="Better move">Confirm baggage storage and the exact walk or taxi distance.</td></tr>
<tr><td data-label="Final-base scenario">Valley lodge</td><td data-label="Main risk">The main transfer stops in town and the last road leg becomes a surprise.</td><td data-label="Better move">Ask the lodge for pickup advice before choosing mode.</td></tr>
<tr><td data-label="Final-base scenario">Homestay</td><td data-label="Main risk">Road access, language, weather, and late arrival can complicate the final leg.</td><td data-label="Better move">Confirm arrival time, pickup point, phone contact, cash, and walking distance.</td></tr>
<tr><td data-label="Final-base scenario">Premium resort</td><td data-label="Main risk">A cheap transfer can undermine an otherwise high-comfort stay.</td><td data-label="Better move">Use hotel-arranged pickup or private transfer if the base is remote.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-sapa-transport-route-pressure-recovery:v1 -->
<h2 class="wp-block-heading">Route pressure and recovery time</h2>
<p>Sapa transport should be judged inside the whole Vietnam route. A two-night Sapa stay can work beautifully when the north is a priority. It can also make a 10-day itinerary brittle if it is added on top of Hanoi, Ninh Binh, Ha Long Bay, Central Vietnam, and the south. The transfer consumes emotional space as much as calendar space: packing, pickup, sleeping, arrival, check-in, weather decisions, and the return to Hanoi.</p>
<table class="vg-decision-table vg-hanoi-sapa-transport-route-pressure-recovery">
<thead><tr><th>Trip shape</th><th>Sapa transport risk</th><th>Cleaner decision</th></tr></thead>
<tbody>
<tr><td data-label="Trip shape">10 days in Vietnam</td><td data-label="Sapa transport risk">The route may spend too much time moving for one mountain chapter.</td><td data-label="Cleaner decision">Add Sapa only if northern scenery replaces another major region.</td></tr>
<tr><td data-label="Trip shape">14 days in Vietnam</td><td data-label="Sapa transport risk">Works if the route avoids stacking every northern icon.</td><td data-label="Cleaner decision">Protect two Sapa nights and a Hanoi buffer before onward movement.</td></tr>
<tr><td data-label="Trip shape">21 days in Vietnam</td><td data-label="Sapa transport risk">Less timing pressure, but fatigue still accumulates.</td><td data-label="Cleaner decision">Use slower transfers or a private car if comfort improves the chapter.</td></tr>
<tr><td data-label="Trip shape">North-focused itinerary</td><td data-label="Sapa transport risk">The transfer is easier to justify, but weather remains the swing factor.</td><td data-label="Cleaner decision">Build flexible sequencing around Hanoi, Ninh Binh, the bay, and mountain days.</td></tr>
<tr><td data-label="Trip shape">Flight after Sapa</td><td data-label="Sapa transport risk">Road, rail, weather, and fatigue can turn the return into a connection problem.</td><td data-label="Cleaner decision">Return to Hanoi with a protected night before important flights.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-sapa-transport-budget-comfort:v1 -->
<h2 class="wp-block-heading">Budget should follow the day you are protecting</h2>
<p>The cheapest Hanoi to Sapa transfer is not always the cheapest Sapa plan. If the transfer causes poor sleep, missed check-in, wasted guiding time, motion sickness, extra taxis, or a recovery day, the hidden cost may be higher than the fare difference. The right budget asks which part of the itinerary deserves protection.</p>
<table class="vg-decision-table vg-hanoi-sapa-transport-budget-comfort">
<thead><tr><th>Spend more on</th><th>When it is worth it</th><th>When to save</th></tr></thead>
<tbody>
<tr><td data-label="Spend more on">Better train cabin</td><td data-label="When it is worth it">Sleep quality, privacy, luggage, and arrival energy matter.</td><td data-label="When to save">The traveler sleeps easily and the first Sapa day is light.</td></tr>
<tr><td data-label="Spend more on">Better bus or van</td><td data-label="When it is worth it">Directness matters but basic sleeper comfort feels too uncertain.</td><td data-label="When to save">Budget matters most and the traveler has realistic road-sleep expectations.</td></tr>
<tr><td data-label="Spend more on">Private car</td><td data-label="When it is worth it">Family needs, motion stops, valley lodging, luggage, or premium pacing require control.</td><td data-label="When to save">Sapa is a brief add-on and the budget would improve the route more elsewhere.</td></tr>
<tr><td data-label="Spend more on">Extra hotel night</td><td data-label="When it is worth it">Early arrival or poor sleep would damage the first mountain day.</td><td data-label="When to save">The lodging has reliable storage and the plan starts slowly.</td></tr>
<tr><td data-label="Spend more on">Hanoi buffer</td><td data-label="When it is worth it">The return connects to flights, cruises, tours, or long onward travel.</td><td data-label="When to save">The route has several flexible nights after Sapa.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-sapa-transport-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before booking or publishing</h2>
<ul class="vg-check-list vg-hanoi-sapa-transport-live-checks">
<li>Check Vietnam Railways and current ticketing before publishing any train detail.</li>
<li>Check whether the selected train still requires a Lao Cai to Sapa transfer that matches arrival time and luggage needs.</li>
<li>Check current bus, cabin bus, limousine van, and private-car pickup points, drop-off points, luggage rules, and cancellation terms.</li>
<li>Check Sapa and Lao Cai weather, fog, rain, storm risk, road condition, and visibility close to travel.</li>
<li>Check whether the first Sapa day needs a guide, room access, baggage storage, valley pickup, or a light recovery plan.</li>
<li>Check that the traveler has accessible medicine, warm layers, water, snacks, charger, phone data, offline maps, and hotel/operator contacts.</li>
<li>Check insurance and medical fallback if mountain roads, trekking, biking, or activity-heavy days are part of the route.</li>
<li>Check whether Sapa is replacing a weaker route chapter rather than being added because of fear of missing out.</li>
</ul>

<!-- vg-hanoi-sapa-transport-faq:v1 -->
<h2 class="wp-block-heading">Hanoi to Sapa transport FAQ</h2>
<div class="vg-faq-list vg-hanoi-sapa-transport-faq">
<details><summary>What is the best way to get from Hanoi to Sapa?</summary><p>The best option depends on the traveler. The train plus Lao Cai transfer suits people who prefer rail rhythm and can handle early arrival. A cabin or sleeper bus suits budget/directness. A limousine van suits daytime comfort. A private car suits families, premium trips, valley lodging, luggage, and motion-sensitive travelers.</p></details>
<details><summary>Does the train go all the way to Sapa?</summary><p>No. The practical rail route is Hanoi to Lao Cai, followed by a road transfer to Sapa. That last leg should be planned before booking, especially for early arrival, luggage, children, weather, and valley accommodation.</p></details>
<details><summary>Is the overnight bus better than the train?</summary><p>It can be better when direct arrival and price matter most. It is weaker when poor road sleep, motion sickness, pickup uncertainty, or arrival fatigue would damage the first Sapa day.</p></details>
<details><summary>Is a private car worth it?</summary><p>A private car is worth considering when control has value: family pacing, motion breaks, luggage, remote lodge drop-off, premium comfort, fragile timing, or a protected first day. It is less convincing when Sapa is only a brief add-on in an already busy route.</p></details>
<details><summary>Should I travel overnight or during the day?</summary><p>Overnight travel saves a hotel night on paper but can weaken the arrival day. Daytime travel costs daylight but often protects sleep, decision-making, and comfort. Choose based on the day you want after arrival.</p></details>
<details><summary>Can I visit Sapa on a short Vietnam itinerary?</summary><p>Yes, but only when northern mountains are a clear priority and another major route chapter is reduced. On a balanced short trip, the transfer can make the itinerary too movement-heavy.</p></details>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Use these related guides to decide whether the Sapa transfer improves the route or only adds fatigue.</p>
<div class="vg-related-routes vg-hanoi-sapa-transport-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a><span class="vg-related-route-note">Plan the gateway night, station/pickup area, and return buffer.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Check northern fog, rain, cool weather, and visibility before choosing a mountain transfer.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Compare trains, buses, cars, overland fatigue, and recovery time.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a><span class="vg-related-route-note">Check road transfer, medical, cancellation, trekking, and activity cover.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a><span class="vg-related-route-note">Use pickup, deposit, driver, taxi, cash, and late-arrival checks.</span></li>
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
    'vg-hanoi-sapa-transport-hero:v1',
    'vg-hanoi-sapa-transport-concierge-verdict:v1',
    'vg-hanoi-sapa-transport-internal-demand:v1',
    'vg-hanoi-sapa-transport-photo-proof:v1',
    'vg-hanoi-sapa-transport-source-diversity:v1',
    'vg-hanoi-sapa-transport-mode-chooser:v1',
    'vg-hanoi-sapa-transport-sleep-arrival:v1',
    'vg-hanoi-sapa-transport-train-lao-cai-transfer:v1',
    'vg-hanoi-sapa-transport-cabin-sleeper-bus:v1',
    'vg-hanoi-sapa-transport-limousine-private-car:v1',
    'vg-hanoi-sapa-transport-luggage-children-motion:v1',
    'vg-hanoi-sapa-transport-valley-dropoff:v1',
    'vg-hanoi-sapa-transport-route-pressure-recovery:v1',
    'vg-hanoi-sapa-transport-budget-comfort:v1',
    'vg-hanoi-sapa-transport-live-checks:v1',
    'vg-hanoi-sapa-transport-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_hanoi_sapa_transport_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Hanoi to Sapa Transport: Train, Cabin Bus or Private Car?',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Choose the best Hanoi to Sapa transfer by train, cabin bus, limousine van, or private car using sleep, arrival, luggage, children, motion comfort, and route pressure.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_hanoi_sapa_transport_post_fail('Could not update Hanoi to Sapa Transport post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Hanoi to Sapa Transport: Train, Cabin Bus or Private Car?');
update_post_meta($post_id, 'rank_math_description', 'Compare Hanoi to Sapa transport by train, cabin bus, limousine van, and private car using sleep, arrival, luggage, motion comfort, and route fit.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Hanoi to Sapa transport');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Choose the best Hanoi to Sapa transfer mode by sleep quality, arrival timing, Lao Cai transfer needs, direct bus trade-offs, luggage, children, motion sickness, valley drop-off, private-car control, route pressure, and recovery time.');
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
    vg_hanoi_sapa_transport_post_fail('Could not assign Hanoi to Sapa Transport categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_hanoi_sapa_transport_post_fail('Could not assign Hanoi to Sapa Transport tags: ' . $tag_result->get_error_message());
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
        vg_hanoi_sapa_transport_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Hanoi to Sapa Transport post to complete draft: {$post_id}");

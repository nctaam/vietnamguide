<?php
/**
 * Expand the Sapa Travel Guide post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-sapa-travel-guide-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_sapa_travel_guide_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_sapa_travel_guide_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_sapa_travel_guide_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_sapa_travel_guide_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_sapa_travel_guide_post_find_by_slug(string $slug): ?WP_Post
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
        vg_sapa_travel_guide_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_sapa_travel_guide_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-23',
            'vg_editorial_brief_status' => ['brief', 'complete_draft'],
            'vg_editorial_batch' => 'batch-3-northern-mountains',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if (is_array($expected_value)) {
            if (! in_array($actual_value, $expected_value, true)) {
                vg_sapa_travel_guide_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected one of " . implode(', ', $expected_value) . '.');
            }
            continue;
        }

        if ($actual_value !== $expected_value) {
            vg_sapa_travel_guide_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_sapa_travel_guide_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_sapa_travel_guide_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'sapa-travel-guide';
$post = vg_sapa_travel_guide_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_sapa_travel_guide_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_sapa_travel_guide_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 26, 2026';

if ($post_id !== 518) {
    vg_sapa_travel_guide_post_fail("Refusing to update {$slug}: expected post ID 518, found {$post_id}.");
}

vg_sapa_travel_guide_post_assert_target_meta($post);

$category_term_ids = vg_sapa_travel_guide_post_term_ids('category', ['destinations', 'travel-planning']);
$tag_term_ids = vg_sapa_travel_guide_post_term_ids('post_tag', ['mountain-planning', 'trekking-planning', 'first-time-vietnam', 'anti-spam-evergreen']);

$sapa_terraces_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/f/ff/Rice_terraces_in_Sapa%2C_Vietnam.jpg');
$muong_hoa_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/e/ef/Ta_Van_Muong_Ha_vallei.jpg');
$fansipan_cable_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/3/3d/Fansipan_cable_car_terraced_rice_fields_valley_aerial_view_Sa_Pa_Vietnam.jpg');
$fansipan_misty_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/d/da/Fansipan_cable_car_misty_mountain_valley_Sa_Pa_Vietnam.png');

$meta_update_summary = 'Expanded the native WordPress Batch 3 brief into a complete Sapa Travel Guide draft with photo-led hero, proof panel, concierge verdict, decision matrix, source-diversity table, photo proof, route-fit planner, town versus valley stay logic, guided versus self-guided trekking filter, terrace and weather timing caveats, transport friction, comfort and mobility guidance, Fansipan filter, ethical local-experience notes, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Sapa - https://vietnam.travel/places-to-go/northern-vietnam/sapa - checked {$review_date}; used for Sapa terraces, Fansipan, trekking, transport, lodges, and national-tourism framing.",
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for Sapa seasonal, fog, rain, cool-weather, and terrace-timing caveats.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for general Vietnam movement context and transfer-friction framing.",
    "Vietnam.travel - Sapa for sustainable travellers - https://vietnam.travel/things-to-do/sapa-itinerary-sustainable-travellers - checked {$review_date}; used for local-guide, homestay, responsible travel, herbal bath, and town-versus-terrace context.",
    "Vietnam.travel - My Sapa: Ly Thy Ker - https://vietnam.travel/things-to-do/my-sapa-ly-thy-ker - checked {$review_date}; used for local guide perspective, village context, culture, markets, and why town-only visits are thin.",
    "Vietnam.travel - Why Fansipan is a must-do in Sapa - https://vietnam.travel/things-to-do/why-fansipan-must-do-sapa - checked {$review_date}; used for Fansipan cable-car context while keeping it optional rather than mandatory.",
    "Vietnam National Center for Hydro-Meteorological Forecasting - https://nchmf.gov.vn/Kttv/en-US/1/index.html - checked {$review_date}; used as live weather source pointer for mountain fog, storms, rain, cold snaps, and visibility checks.",
    "UK FCDO Vietnam travel advice - https://www.gov.uk/foreign-travel-advice/vietnam/safety-and-security - checked {$review_date}; used for road-safety and adventure caution in the source trail.",
    "Australian Smartraveller Vietnam advice - https://www.smartraveller.gov.au/destinations/asia/vietnam - checked {$review_date}; used for insurance, road-safety, and activity-cover source trail.",
    "Wikimedia Commons image direct URL - Rice terraces in Sapa, Vietnam - {$sapa_terraces_image} - credit Eerin25, CC0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ta Van Muong Hoa valley - {$muong_hoa_image} - credit Andre Hospers, CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Fansipan cable car over terraced rice fields - {$fansipan_cable_image} - credit Vivu Vietnam, CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Fansipan cable car misty mountain valley - {$fansipan_misty_image} - credit Vivu Vietnam, CC BY-SA 4.0 - license context checked {$review_date}.",
    "Internal published page check - https://vietnamguide.net/destinations/hanoi-travel-guide/ - checked {$review_date}; used for Hanoi gateway handoff.",
    "Internal published page check - https://vietnamguide.net/plan/best-time-to-visit-vietnam/ - checked {$review_date}; used for season planning handoff.",
    "Internal published page check - https://vietnamguide.net/plan/transport-within-vietnam/ - checked {$review_date}; used for transfer planning handoff.",
    "Internal published page check - https://vietnamguide.net/plan/health-travel-insurance-vietnam/ - checked {$review_date}; used for trekking, activity, cancellation, and medical cover handoff.",
    "Internal published page check - https://vietnamguide.net/plan/safety-scams-vietnam/ - checked {$review_date}; used for guide, deposit, cash, road, and activity-risk handoff.",
    "Internal published page check - https://vietnamguide.net/compare/north-central-south-vietnam/ - checked {$review_date}; used for region-fit handoff.",
    "Internal published page check - https://vietnamguide.net/itineraries/10-days-in-vietnam/ - checked {$review_date}; used for short-route pressure.",
    "Internal published page check - https://vietnamguide.net/itineraries/14-days-in-vietnam/ - checked {$review_date}; used for one-mountain-chapter planning.",
    "Internal published page check - https://vietnamguide.net/itineraries/21-days-in-vietnam/ - checked {$review_date}; used for longer northern mountain planning.",
    "Internal published page check - https://vietnamguide.net/destinations/ninh-binh-travel-guide/ - checked {$review_date}; used for easier scenery alternative.",
    "Internal published page check - https://vietnamguide.net/destinations/ha-long-bay-travel-guide/ - checked {$review_date}; used for northern scenery stacking caution.",
    "Internal published page check - https://vietnamguide.net/costs/vietnam-travel-cost/ - checked {$review_date}; used for guide, transfer, lodge, and extra-night cost handoff.",
]);
$meta_field_note = 'This complete draft treats Sapa as a route, comfort, season, and walking decision. It does not dismiss Sapa as only crowded and does not sell it as a guaranteed terrace dream. The right answer depends on whether terraces, valley stays, guided walks, softer mountain comfort, and Hanoi-linked logistics improve the trip enough to justify the transfer.';
$meta_evidence_moat = implode("\n", [
    'The article answers whether to add, shorten, upgrade, or skip Sapa before booking transfers or lodges.',
    'The first screen separates softer mountain travel from road-heavy Ha Giang-style ambition without linking to unpublished draft content.',
    'The town versus valley section gives original base logic beyond hotel lists.',
    'The trekking section separates guided walks, self-guided short walks, homestays, footwear, mud, fog, and local context.',
    'Season guidance avoids golden-terrace promises and treats fog, rain, cold, and visibility as planning factors.',
    'Transport guidance treats Hanoi transfer fatigue and recovery time as part of the recommendation.',
    'Photo proof uses credited real Sapa and Fansipan images, with image credits retained in captions and metadata.',
    'No visible external body anchors; source URLs and image-license records remain in metadata/source trail.',
]);
$meta_related_routes = implode("\n", [
    'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use as the northern gateway before Sapa or any mountain transfer.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check northern weather, fog, cold, rain, and terrace timing.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Count train, cabin bus, private car, pickups, and recovery time.',
    'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check trekking, activity, cancellation, medical, and evacuation cover.',
    'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair guides, deposits, village visits, taxis, road movement, and cash with practical checks.',
    'North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Decide whether the north deserves enough trip weight for mountains.',
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Place Sapa inside the full first-trip planning order.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Compare guide, transfer, lodge, private-car, and extra-night costs.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Sapa survives a short route.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use one northern mountain chapter without overstacking scenery.',
    '21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Consider Sapa with wider northern buffers.',
    'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Compare easier karst countryside with a mountain chapter.',
    'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Avoid stacking every northern scenery icon into one short route.',
]);
$meta_hero_image_credit = 'Hero image: Rice terraces in Sapa, Vietnam by Eerin25, CC0. Body images: Ta Van Muong Hoa valley by Andre Hospers, CC BY 4.0; Fansipan cable car over terraced rice fields by Vivu Vietnam, CC BY-SA 4.0; Fansipan misty mountain valley by Vivu Vietnam, CC BY-SA 4.0.';
$meta_admin_notes = 'Complete draft generated for WordPress Admin review. Keep draft until manual preview, Rank Math review, image check, weather/source refresh, and final editorial pass are complete. The body intentionally avoids external anchors; source URLs are preserved in metadata and source trail. Before publishing, remove or keep only published internal links and refresh Sapa weather, transport, guide, and image-license notes.';

$content = <<<HTML
<!-- vg-sapa-travel-guide-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$sapa_terraces_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Rice terraces in Sapa, Vietnam" src="{$sapa_terraces_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<div class="wp-block-group vg-guide-hero-inner">
<p class="vg-kicker">Reviewed mountain planning guide - Updated {$review_date}</p>
<h1 class="wp-block-heading vg-display vg-guide-title">Sapa Travel Guide: Terraces, Trekking and Softer Mountain Travel</h1>
<p class="vg-guide-lede">Sapa is worth adding when terraces, guided walking, valley stays, and cooler mountain comfort improve the route enough to justify the Hanoi transfer. It is weaker when the trip is short, the weather window is poor, or the plan only wants a quick viewpoint.</p>
<p class="vg-field-note">Concierge verdict: choose Sapa for a softer mountain chapter, not because every Vietnam itinerary needs it. Stay beyond the town core, protect at least two nights when possible, and treat weather, shoes, guides, and transfer fatigue as part of the decision.</p>
</div>
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-sapa-travel-guide-concierge-verdict:v1 -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<p><strong>Add Sapa when the route wants terraces, guided walking, and a softer mountain stay.</strong> It is strongest for travelers who prefer lodges, valley views, cultural context, cooler weather, and walks that can be scaled to the group's ability.</p>
<p><strong>Shorten Sapa when it is only a scenic side trip.</strong> One focused night can give a taste, but it often turns into transfer fatigue. Two nights are cleaner for most first-time travelers because the second morning gives weather and walking margin.</p>
<p><strong>Skip Sapa when the route is already scenery-heavy.</strong> If Hanoi, Ninh Binh, a bay cruise, central Vietnam, and southern Vietnam are all squeezed into a short route, Sapa may reduce the quality of every other stop.</p>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-sapa-travel-guide-internal-demand:v1 -->
<h2 class="wp-block-heading">Why Sapa needs a decision guide</h2>
<p>Sapa is often sold as a simple mountain add-on from Hanoi. That framing hides the real choice. The question is not only what to do in Sapa; it is whether terraces, walking, village context, cooler air, and a mountain lodge improve the whole trip enough to pay for the transfers, weather risk, and recovery time.</p>

<!-- vg-sapa-travel-guide-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: what Sapa actually offers</h2>
<div class="vg-photo-grid vg-sapa-travel-guide-photo-proof">
<figure><img src="{$sapa_terraces_image}" alt="Rice terraces in Sapa" loading="lazy" decoding="async"><figcaption>Terraces are Sapa's strongest promise, but their look changes by season. Image: Eerin25, CC0.</figcaption></figure>
<figure><img src="{$muong_hoa_image}" alt="Muong Hoa valley near Sapa" loading="lazy" decoding="async"><figcaption>Valley stays make Sapa more useful than a town-only stop. Image: Andre Hospers, CC BY 4.0.</figcaption></figure>
<figure><img src="{$fansipan_cable_image}" alt="Fansipan cable car over terraced rice fields" loading="lazy" decoding="async"><figcaption>Fansipan can add an easy high-mountain viewpoint, but weather decides the payoff. Image: Vivu Vietnam, CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$fansipan_misty_image}" alt="Fansipan cable car in misty mountain valley" loading="lazy" decoding="async"><figcaption>Mist is part of the mountain experience; it can be atmospheric or frustrating depending on expectations. Image: Vivu Vietnam, CC BY-SA 4.0.</figcaption></figure>
</div>

<!-- vg-sapa-travel-guide-source-diversity:v1 -->
<h2 class="wp-block-heading">How the source trail is used</h2>
<table class="vg-decision-table vg-sapa-travel-guide-source-diversity">
<thead><tr><th>Source type</th><th>What it can prove</th><th>How this guide uses it</th></tr></thead>
<tbody>
<tr><td data-label="Source type">National tourism Sapa pages</td><td data-label="What it can prove">Terraces, Fansipan, trekking, lodges, transport, and broad destination context.</td><td data-label="How this guide uses it">To anchor the guide without copying a things-to-do list.</td></tr>
<tr><td data-label="Source type">Sustainable and local-perspective pages</td><td data-label="What it can prove">Local guide value, village context, homestays, markets, herbal baths, and responsible travel framing.</td><td data-label="How this guide uses it">To keep Sapa from becoming a view-only article.</td></tr>
<tr><td data-label="Source type">Weather sources</td><td data-label="What it can prove">Fog, rain, cool-season, hot-season, storm, and visibility risk.</td><td data-label="How this guide uses it">To make timing and flexibility central to the recommendation.</td></tr>
<tr><td data-label="Source type">Transport and safety sources</td><td data-label="What it can prove">Transfer friction, mountain roads, trekking comfort, and insurance/live-check discipline.</td><td data-label="How this guide uses it">To stop Sapa from being treated as a frictionless Hanoi add-on.</td></tr>
<tr><td data-label="Source type">Internal route pages</td><td data-label="What it can prove">How Sapa competes with Hanoi, Ninh Binh, the bay, and route length.</td><td data-label="How this guide uses it">To protect the overall itinerary from northern scenery overstacking.</td></tr>
</tbody>
</table>

<!-- vg-sapa-travel-guide-add-shorten-skip:v1 -->
<h2 class="wp-block-heading">Add, shorten, upgrade or skip Sapa?</h2>
<table class="vg-decision-table vg-sapa-travel-guide-add-shorten-skip">
<thead><tr><th>Decision</th><th>Best when</th><th>What to protect</th></tr></thead>
<tbody>
<tr><td data-label="Decision">Add Sapa</td><td data-label="Best when">Terraces, guided walking, valley views, and cooler mountain air are a top northern priority.</td><td data-label="What to protect">Two nights, good shoes, flexible weather expectations, and a clean Hanoi return.</td></tr>
<tr><td data-label="Decision">Shorten Sapa</td><td data-label="Best when">The route wants only a taste and can accept a town/near-valley experience.</td><td data-label="What to protect">A focused plan, not a tired transfer plus one foggy viewpoint.</td></tr>
<tr><td data-label="Decision">Upgrade Sapa</td><td data-label="Best when">Comfort, family needs, romance, or premium pacing matter more than covering more places.</td><td data-label="What to protect">A valley lodge, private transfer, private guide, and fewer one-night moves.</td></tr>
<tr><td data-label="Decision">Skip Sapa</td><td data-label="Best when">The route is short, weather is weak, mobility is limited, or Ninh Binh and the bay already carry the scenery.</td><td data-label="What to protect">The quality of the whole Vietnam route.</td></tr>
</tbody>
</table>

<!-- vg-sapa-travel-guide-route-fit:v1 -->
<h2 class="wp-block-heading">How many days does Sapa need?</h2>
<table class="vg-decision-table vg-sapa-travel-guide-route-fit">
<thead><tr><th>Trip length</th><th>Sapa fit</th><th>Better move</th></tr></thead>
<tbody>
<tr><td data-label="Trip length">7 days</td><td data-label="Sapa fit">Usually too tight unless the trip is north-only and mountains are the point.</td><td data-label="Better move">Stay with Hanoi, Ninh Binh, or the bay rather than adding a tired mountain transfer.</td></tr>
<tr><td data-label="Trip length">10 days</td><td data-label="Sapa fit">Possible if Sapa replaces another scenery chapter.</td><td data-label="Better move">Choose one mountain or karst extension, not all of them.</td></tr>
<tr><td data-label="Trip length">14 days</td><td data-label="Sapa fit">A good fit when the north is a major route chapter.</td><td data-label="Better move">Use two nights and keep the Hanoi return protected.</td></tr>
<tr><td data-label="Trip length">21 days</td><td data-label="Sapa fit">Works well with wider northern buffers.</td><td data-label="Better move">Pair Sapa with slower Hanoi, Ninh Binh, bay, or central movement instead of rushing.</td></tr>
<tr><td data-label="Trip length">North-only route</td><td data-label="Sapa fit">Strong if terraces and walking are central goals.</td><td data-label="Better move">Build a deliberate mountain/countryside chapter with weather slack.</td></tr>
</tbody>
</table>

<!-- vg-sapa-travel-guide-town-valley:v1 -->
<h2 class="wp-block-heading">Where Sapa works best: town or valley?</h2>
<p>Sapa town is useful for arrival, restaurants, taxis, markets, and a shorter stay. The valleys make Sapa feel more like a mountain trip. The best choice is not the prettiest listing; it is the base that matches how the traveler wants to move.</p>
<table class="vg-decision-table vg-sapa-travel-guide-town-valley">
<thead><tr><th>Base</th><th>Use when</th><th>Trade-off</th></tr></thead>
<tbody>
<tr><td data-label="Base">Sapa town</td><td data-label="Use when">You need restaurants, taxis, quick pickups, and easier logistics.</td><td data-label="Trade-off">Views and atmosphere can feel less special if the stay never reaches the valleys.</td></tr>
<tr><td data-label="Base">Muong Hoa / valley lodge</td><td data-label="Use when">Views, quieter mornings, and comfort matter more than town convenience.</td><td data-label="Trade-off">Transfers, dinner plans, and weather become more important.</td></tr>
<tr><td data-label="Base">Village homestay</td><td data-label="Use when">Local context and simple accommodation are part of the reason to visit.</td><td data-label="Trade-off">Comfort, privacy, bathroom expectations, and communication need checking.</td></tr>
<tr><td data-label="Base">Premium lodge</td><td data-label="Use when">The group wants Sapa to be restorative, romantic, or family-friendly.</td><td data-label="Trade-off">Higher cost, less spontaneous town access, and weather-dependent views.</td></tr>
</tbody>
</table>

<!-- vg-sapa-travel-guide-trekking-filter:v1 -->
<h2 class="wp-block-heading">Trekking: guided, self-guided or just a short walk?</h2>
<p>Guided trekking is often the better default for first-time visitors because Sapa's value is not only the path. A good guide can adjust pace, explain local context, help with footing, reduce awkward village encounters, and decide when weather makes a route poor. Self-guided walks can still work for confident travelers on short, obvious routes near town or lodging.</p>
<ul class="vg-check-list vg-sapa-travel-guide-trekking-filter">
<li><strong>Choose a guide</strong> when trails are muddy, the route enters villages, the group has mixed fitness, or local context matters.</li>
<li><strong>Choose a private guide</strong> when pace, photography, children, mobility, or lunch timing matters.</li>
<li><strong>Choose a short self-guided walk</strong> when the weather is stable and the route is simple.</li>
<li><strong>Skip trekking</strong> when shoes, knees, rain, fog, or illness would turn the day into proof of endurance.</li>
<li><strong>Ask before booking</strong> about distance, elevation, mud, road sections, lunch, pickup, guide language, group size, and what happens in rain.</li>
</ul>

<!-- vg-sapa-travel-guide-season-terraces:v1 -->
<h2 class="wp-block-heading">Season, fog and rice terraces</h2>
<p>Sapa is not one look year-round. Clear mountain days, mist, rain, cold, flowers, green terraces, harvested fields, and golden rice all produce different trips. The evergreen rule is simple: do not book Sapa only for one photograph unless the month and weather window support that goal. Treat any golden terrace goal as a live timing check, not a promise.</p>
<table class="vg-decision-table vg-sapa-travel-guide-season-terraces">
<thead><tr><th>Condition</th><th>What changes</th><th>Planning move</th></tr></thead>
<tbody>
<tr><td data-label="Condition">Clear shoulder weather</td><td data-label="What changes">Walking, viewpoints, and valley stays are easier to enjoy.</td><td data-label="Planning move">Protect two nights and avoid overloading the next transfer.</td></tr>
<tr><td data-label="Condition">Golden terrace goal</td><td data-label="What changes">Timing matters and exact color varies by valley and year.</td><td data-label="Planning move">Refresh local terrace timing before publishing or booking.</td></tr>
<tr><td data-label="Condition">Rain or mud</td><td data-label="What changes">Footing, shoes, guide value, and route length become central.</td><td data-label="Planning move">Choose shorter walks, better shoes, and a flexible guide.</td></tr>
<tr><td data-label="Condition">Fog or low visibility</td><td data-label="What changes">View payoff may drop even while transfer effort stays high.</td><td data-label="Planning move">Book a comfortable base and avoid a one-viewpoint plan.</td></tr>
<tr><td data-label="Condition">Cold snap</td><td data-label="What changes">Heated bedding, layers, hot meals, and transport comfort matter.</td><td data-label="Planning move">Pack warm layers and confirm lodging comfort.</td></tr>
</tbody>
</table>

<!-- vg-sapa-travel-guide-transport:v1 -->
<h2 class="wp-block-heading">Getting to Sapa without ruining the next day</h2>
<p>Most Sapa plans start and end in Hanoi. The transfer choice should be judged by sleep, arrival time, luggage, pickup location, motion comfort, and how much the next day matters. A cheap transfer that destroys the first walking day is not cheap in route terms.</p>
<table class="vg-decision-table vg-sapa-travel-guide-transport">
<thead><tr><th>Mode</th><th>Use when</th><th>Watch out for</th></tr></thead>
<tbody>
<tr><td data-label="Mode">Overnight train to Lao Cai plus transfer</td><td data-label="Use when">You like rail travel, want to save a hotel night, or prefer avoiding long road time.</td><td data-label="Watch out for">Sleep quality, early arrival, station transfer, luggage, and the final uphill leg.</td></tr>
<tr><td data-label="Mode">Cabin bus or sleeper bus</td><td data-label="Use when">Budget and direct town arrival matter.</td><td data-label="Watch out for">Motion comfort, pickup location, road fatigue, and arriving too tired to walk.</td></tr>
<tr><td data-label="Mode">Limousine van</td><td data-label="Use when">A daytime transfer with more comfort fits the route.</td><td data-label="Watch out for">Traffic, hotel pickup claims, luggage space, and late arrival in the valley.</td></tr>
<tr><td data-label="Mode">Private car</td><td data-label="Use when">Family, comfort, premium pacing, fragile timing, or valley lodging makes control valuable.</td><td data-label="Watch out for">Cost, driver quality, stops, motion sickness, and whether the route earns the spend.</td></tr>
<tr><td data-label="Mode">Skip Sapa</td><td data-label="Use when">The transfer consumes more energy than the mountain chapter can return.</td><td data-label="Watch out for">Fear of missing out disguised as planning.</td></tr>
</tbody>
</table>

<!-- vg-sapa-travel-guide-fansipan:v1 -->
<h2 class="wp-block-heading">Fansipan: add it or leave it?</h2>
<p>Fansipan can be a strong add-on when the weather is clear and the traveler wants high-mountain views without a hard trek. It should not become the whole reason to visit Sapa. If fog is thick, the cable car may still be atmospheric, but the route value shifts from view payoff to mountain novelty.</p>
<ul class="vg-check-list vg-sapa-travel-guide-fansipan">
<li><strong>Add Fansipan</strong> when the forecast, visibility, timing, and budget support it.</li>
<li><strong>Keep it optional</strong> when the trip's real value is terraces, walking, and valley comfort.</li>
<li><strong>Skip it</strong> when the route is already rushed or weather makes the viewpoint unlikely.</li>
<li><strong>Check live</strong> for operating hours, ticketing, weather, closures, and transport to the station before publishing or booking.</li>
</ul>

<!-- vg-sapa-travel-guide-comfort-ethics:v1 -->
<h2 class="wp-block-heading">Comfort, culture and responsible choices</h2>
<p>Sapa's long-term value depends on not treating villages as scenery. The better trip gives local guides time to explain context, asks before taking close portraits, buys with patience, and understands that homestays are not boutique hotels unless sold that way.</p>
<table class="vg-decision-table vg-sapa-travel-guide-comfort-ethics">
<thead><tr><th>Traveler need</th><th>Better Sapa choice</th><th>Avoid</th></tr></thead>
<tbody>
<tr><td data-label="Traveler need">Families</td><td data-label="Better Sapa choice">Private transfer, shorter walks, flexible guide, warmer lodging, and valley views without overdoing distance.</td><td data-label="Avoid">Long muddy treks that depend on everyone coping well.</td></tr>
<tr><td data-label="Traveler need">Couples</td><td data-label="Better Sapa choice">Two-night lodge stay, one guided walk, one slow morning, optional Fansipan if clear.</td><td data-label="Avoid">Moving every night to collect northern icons.</td></tr>
<tr><td data-label="Traveler need">Budget travelers</td><td data-label="Better Sapa choice">Simple lodging, shared guide or short self-guided walks, and honest transfer recovery.</td><td data-label="Avoid">Choosing the cheapest transfer and losing the walking day.</td></tr>
<tr><td data-label="Traveler need">Premium travelers</td><td data-label="Better Sapa choice">Private car, private guide, valley lodge, fewer stops, and weather-flexible pacing.</td><td data-label="Avoid">Paying for views without checking fog and location.</td></tr>
<tr><td data-label="Traveler need">Responsible travelers</td><td data-label="Better Sapa choice">Local guide, fair buying, photo consent, realistic homestay expectations, and less intrusive routes.</td><td data-label="Avoid">Treating culture as a checklist or village stops as free entertainment.</td></tr>
</tbody>
</table>

<!-- vg-sapa-travel-guide-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before booking</h2>
<ul class="vg-check-list vg-sapa-travel-guide-live-checks">
<li>Check current Sapa weather, fog, rain, cold, storm, and visibility signals close to travel.</li>
<li>Check whether terrace timing matches the reason you want Sapa.</li>
<li>Check transfer pickup point, arrival time, luggage handling, and final hotel or valley drop-off.</li>
<li>Check lodging heat, bedding, bathroom, meals, stairs, road access, and dinner options.</li>
<li>Check guide distance, elevation, road sections, mud, lunch, group size, language, and rain policy.</li>
<li>Check activity and medical insurance if trekking, biking, or difficult walking is part of the plan.</li>
<li>Check that Sapa is replacing a weaker route chapter rather than overloading the itinerary.</li>
</ul>

<!-- vg-sapa-travel-guide-faq:v1 -->
<h2 class="wp-block-heading">Sapa FAQ</h2>
<div class="vg-faq-list vg-sapa-travel-guide-faq">
<details><summary>Is Sapa worth visiting on a first Vietnam trip?</summary><p>Yes when terraces, guided walking, valley views, and cooler mountain comfort are a real priority. It is weaker on short routes that already include Hanoi, Ninh Binh, the bay, central Vietnam, and southern Vietnam.</p></details>
<details><summary>How many nights should I spend in Sapa?</summary><p>Two nights is the cleaner default for most travelers. One night can work as a taste, but it is vulnerable to transfer fatigue and poor visibility. Three nights are useful when Sapa is the main mountain chapter.</p></details>
<details><summary>Should I stay in Sapa town or a valley lodge?</summary><p>Stay in town for easier logistics, restaurants, and quick pickups. Choose a valley lodge or homestay when views, quieter mornings, walking access, and mountain atmosphere matter more than convenience.</p></details>
<details><summary>Do I need a trekking guide in Sapa?</summary><p>A guide is strongly useful for village context, muddy conditions, mixed fitness, and longer walks. Short self-guided walks can work when the route is obvious and the weather is stable.</p></details>
<details><summary>When are Sapa rice terraces best?</summary><p>Terrace appearance changes by valley, farming rhythm, and year. Do not expect golden or green terraces in every month; check current local timing before booking a terrace-focused trip.</p></details>
<details><summary>Is Fansipan essential?</summary><p>No. Fansipan is a good add-on when weather and visibility support it, but Sapa's deeper value usually comes from terraces, valley stays, and guided walking.</p></details>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Use these related guides to decide whether Sapa improves the route or only makes it busier.</p>
<div class="vg-related-routes vg-sapa-travel-guide-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a><span class="vg-related-route-note">Use Hanoi as the gateway before any Sapa transfer.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Check northern fog, cold, rain, and terrace timing.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Compare train, bus, van, private car, and transfer recovery.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a><span class="vg-related-route-note">Check trekking, medical, cancellation, and activity cover.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a><span class="vg-related-route-note">Use practical checks for guides, deposits, taxis, cash, and road movement.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a><span class="vg-related-route-note">See whether one northern mountain chapter earns its place.</span></li>
<li><span class="vg-related-route-step">07</span><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a><span class="vg-related-route-note">Compare easier karst countryside with a mountain commitment.</span></li>
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
    'vg-sapa-travel-guide-hero:v1',
    'vg-sapa-travel-guide-concierge-verdict:v1',
    'vg-sapa-travel-guide-internal-demand:v1',
    'vg-sapa-travel-guide-photo-proof:v1',
    'vg-sapa-travel-guide-source-diversity:v1',
    'vg-sapa-travel-guide-add-shorten-skip:v1',
    'vg-sapa-travel-guide-route-fit:v1',
    'vg-sapa-travel-guide-town-valley:v1',
    'vg-sapa-travel-guide-trekking-filter:v1',
    'vg-sapa-travel-guide-season-terraces:v1',
    'vg-sapa-travel-guide-transport:v1',
    'vg-sapa-travel-guide-fansipan:v1',
    'vg-sapa-travel-guide-comfort-ethics:v1',
    'vg-sapa-travel-guide-live-checks:v1',
    'vg-sapa-travel-guide-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_sapa_travel_guide_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Sapa Travel Guide: Terraces, Trekking and Softer Mountain Travel',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Decide whether Sapa belongs in your Vietnam route by terraces, trekking, season, comfort, town vs valley base, Hanoi transfer load, and weather risk.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_sapa_travel_guide_post_fail('Could not update Sapa Travel Guide post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Sapa Travel Guide: Terraces, Trekking and Softer Travel');
update_post_meta($post_id, 'rank_math_description', 'Plan Sapa by terraces, trekking, town vs valley stays, season, fog, transport from Hanoi, comfort, Fansipan, and when to skip it.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Sapa Travel Guide');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Decide whether Sapa belongs in a Vietnam itinerary based on terraces, walking, town versus valley stays, weather, comfort, transport from Hanoi, route length, and whether softer mountain travel is worth the transfer.');
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
    vg_sapa_travel_guide_post_fail('Could not assign Sapa Travel Guide categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_sapa_travel_guide_post_fail('Could not assign Sapa Travel Guide tags: ' . $tag_result->get_error_message());
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
        vg_sapa_travel_guide_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Sapa Travel Guide post to complete draft: {$post_id}");

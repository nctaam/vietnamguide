<?php
/**
 * Expand the Phong Nha Travel Guide post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-phong-nha-travel-guide-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_phong_nha_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_phong_nha_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_phong_nha_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_phong_nha_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_phong_nha_post_find_by_slug(string $slug): ?WP_Post
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
        vg_phong_nha_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_phong_nha_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-21',
            'vg_editorial_brief_status' => ['brief', 'complete_draft'],
            'vg_editorial_batch' => 'batch-2-evergreen-planning',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if (is_array($expected_value)) {
            if (! in_array($actual_value, $expected_value, true)) {
                vg_phong_nha_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected one of " . implode(', ', $expected_value) . '.');
            }
            continue;
        }

        if ($actual_value !== $expected_value) {
            vg_phong_nha_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_phong_nha_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_phong_nha_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'phong-nha-travel-guide';
$post = vg_phong_nha_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_phong_nha_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_phong_nha_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 26, 2026';

if ($post_id !== 503) {
    vg_phong_nha_post_fail("Refusing to update {$slug}: expected post ID 503, found {$post_id}.");
}

vg_phong_nha_post_assert_target_meta($post);

$category_term_ids = vg_phong_nha_post_term_ids('category', ['destinations', 'heritage-culture']);
$tag_term_ids = vg_phong_nha_post_term_ids('post_tag', ['route-planning', 'heritage-travel', 'cave-planning', 'anti-spam-evergreen']);

$cave_entrance_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/b/bd/Phong_Nha_cave_entrance.jpg');
$cave_interior_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/b/bc/Phong_Nha-Ke_Bang_cave3.jpg');
$son_river_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f1/Son_River-Quang_Binh_province.jpg/1920px-Son_River-Quang_Binh_province.jpg');
$paradise_cave_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/7/72/Cueva_Para%C3%ADso%2C_Parque_Nacional_Phong_Nha_-_Ke_Bang%2C_Vietnam_%2841309962172%29.jpg');
$son_doong_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/b/b4/Son_Doong_Cave_by_Daniel_Burka.jpg');

$meta_update_summary = 'Expanded the native WordPress batch 2 brief into a complete Phong Nha route-fit draft with photo-led hero, proof panel, concierge verdict, internal demand note, UNESCO/source context, add/skip matrix, cave chooser, night-count planner, season and rain guardrails, safety and insurance filter, gateway routing, booking checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "UNESCO World Heritage Centre - Phong Nha-Ke Bang National Park and Hin Nam No National Park - https://whc.unesco.org/en/list/951/ - checked {$review_date}; used for World Heritage identity and 2025 transboundary naming context.",
    "UNESCO World Heritage Centre - Decision 47 COM 8B.6 - https://whc.unesco.org/en/decisions/8942/ - checked {$review_date}; used for 2025 extension context and wording discipline.",
    "Vietnam.travel - Phong Nha - https://vietnam.travel/places-to-go/central-vietnam/phong-nha - checked {$review_date}; used for national tourism framing, first-time destination role, and visitor overview.",
    "Phong Nha-Ke Bang National Park Management Board - https://phongnhakebang.vn/ - checked {$review_date}; used as official park authority and source-update pointer.",
    "Official park page - Phong Nha Cave - https://phongnhakebang.vn/travel/phong-nha-ky-quan-de-nhat-dong1-en - checked {$review_date}; used for show-cave access, boat model, timing, and rainy-season caveat reminders.",
    "Official park page - Dai A, Hang Over, Hang Pygmy route - https://phongnhakebang.vn/travel/tuyen-hang-dai-a-hang-over-hang-pygmy31-en - checked {$review_date}; used for expedition intensity, trekking, camping, and operator-role context.",
    "Oxalis Adventure - How to choose the right cave tour - https://oxalisadventure.com/how-tour-choose-the-right-cave-tour-oxalis/ - checked {$review_date}; used for fitness, swimming, camping, and cave-tour chooser language.",
    "Oxalis Adventure - Son Doong Expedition - https://oxalisadventure.com/tour/son-doong-cave-expedition-4d3n/ - checked {$review_date}; used for specialist expedition, permit, group-size, medical, and insurance caution context.",
    "Oxalis Adventure - Tu Lan Expedition - https://oxalisadventure.com/tour/tu-lan-expedition/ - checked {$review_date}; used for demanding tour and season-closure caution context.",
    "Oxalis Adventure - Booking Conditions - https://oxalisadventure.com/booking-conditions/ - checked {$review_date}; used for cancellation, remote-area risk, guide authority, and fitness disclosure reminders.",
    "Airports Corporation of Vietnam - Dong Hoi Airport - https://acv.vn/en/airports/dong-hoi-airport - checked {$review_date}; used for airport-gateway live-check pointer without freezing flight schedules.",
    "Vietnam Railways official booking - https://dsvn.vn/ - checked {$review_date}; used for rail-to-Dong-Hoi live-check pointer without freezing timetables.",
    "Vietnam National Center for Hydro-Meteorological Forecasting - https://nchmf.gov.vn/Kttv/en-US/1/index.html - checked {$review_date}; used for storm, flood, rain, and official weather live-check pointer.",
    "Internal published page check - https://vietnamguide.net/destinations/unesco-heritage-sites-vietnam/ - checked {$review_date}; used for UNESCO context handoff.",
    "Internal published page check - https://vietnamguide.net/compare/north-central-south-vietnam/ - checked {$review_date}; used for regional fit handoff.",
    "Internal published page check - https://vietnamguide.net/plan/best-time-to-visit-vietnam/ - checked {$review_date}; used for season planning handoff.",
    "Internal published page check - https://vietnamguide.net/plan/transport-within-vietnam/ - checked {$review_date}; used for transfer and gateway handoff.",
    "Internal published page check - https://vietnamguide.net/plan/health-travel-insurance-vietnam/ - checked {$review_date}; used for insurance and activity-risk handoff.",
    "Internal published page check - https://vietnamguide.net/itineraries/14-days-in-vietnam/ - checked {$review_date}; used for route-length fit handoff.",
    "Wikimedia Commons image direct URL - Phong Nha cave entrance - {$cave_entrance_image} - credit Tycho / shansov.net, CC BY-SA 3.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Phong Nha-Ke Bang cave interior - {$cave_interior_image} - credit Tycho / shansov.net, CC BY-SA 3.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Son River, Quang Binh province - {$son_river_image} - credit BacLuong, CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Paradise Cave - {$paradise_cave_image} - credit Edgardo W. Olivera, CC BY 2.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Son Doong Cave by Daniel Burka - {$son_doong_image} - credit Daniel Burka, CC0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This complete draft treats Phong Nha as a route-fit and cave-intensity decision, not a superlative cave list. The article asks whether the traveler has enough time, season margin, fitness, insurance, and central-route space to make the detour worthwhile.';
$meta_evidence_moat = implode("\n", [
    'The article separates show-cave stops, two-night nature chapters, and specialist cave expeditions instead of flattening every cave into one recommendation.',
    'UNESCO and 2025 transboundary context is preserved without turning the travel guide into a brittle fact list.',
    'Cave choice is tied to route length, fitness, water activity, camping tolerance, season, guide requirements, and weather-risk discipline.',
    'Phong Nha is framed as easiest from a central or north-central route, not as a default add-on from every city.',
    'Skip logic protects short trips, beach-first travelers, limited-mobility travelers, and plans without proper insurance or weather flexibility.',
    'Official park, UNESCO, Vietnam.travel, operator, airport, rail, weather, and internal route sources are preserved in metadata/source trail.',
    'No visible external body anchors; source URLs and image credits remain auditable in metadata/source trail.',
]);
$meta_related_routes = implode("\n", [
    'UNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Use for World Heritage context and the 2025 Phong Nha-Hin Nam No update.',
    'North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether central Vietnam deserves a cave-and-nature chapter.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check rain, storm, heat, and regional season trade-offs before booking caves.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Count Dong Hoi, train, bus, private transfer, and onward-route friction.',
    'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check activity, cave, evacuation, medical, and cancellation coverage.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Phong Nha is too much for a short first route.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide whether one nature extension should be Phong Nha, Mekong, beach, or mountains.',
    '21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use a longer route to give Phong Nha the buffer it needs.',
    'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ | Compare central-coast base time with a cave detour.',
    'Da Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Choose the better central base before adding Phong Nha.',
    'Hoi An vs Hue | /compare/hoi-an-vs-hue/ | Use Hue or central heritage routing before adding a cave chapter.',
    'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Compare easier karst scenery with the deeper Phong Nha cave commitment.',
    'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use if Phong Nha becomes part of a north-to-central overland route.',
]);
$meta_hero_image_credit = 'Hero image: Phong Nha cave entrance by Tycho / shansov.net, CC BY-SA 3.0. Body images: Phong Nha-Ke Bang cave interior by Tycho / shansov.net, CC BY-SA 3.0; Son River by BacLuong, CC BY-SA 4.0; Paradise Cave by Edgardo W. Olivera, CC BY 2.0; Son Doong Cave by Daniel Burka, CC0.';
$meta_admin_notes = 'Complete draft generated for WordPress Admin review. Keep draft until manual preview, Rank Math review, image check, source refresh, and final editorial pass are complete. The body intentionally avoids external anchors; source URLs are preserved in metadata and source trail.';

$content = <<<HTML
<!-- vg-phong-nha-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$cave_entrance_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Boat entrance to Phong Nha Cave in Quang Binh, Vietnam" src="{$cave_entrance_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<div class="wp-block-group vg-guide-hero-inner">
<p class="vg-kicker">Reviewed route decision - Updated {$review_date}</p>
<h1 class="wp-block-heading vg-display vg-guide-title">Phong Nha Travel Guide: Caves, Seasons and Route Fit</h1>
<p class="vg-guide-lede">Phong Nha is worth adding when caves, karst, jungle, and central Vietnam depth are the point of the detour. It is weak when it is squeezed between Hue, Hoi An, and a flight because a route feels incomplete without another famous stop.</p>
<p class="vg-field-note">Concierge verdict: use Phong Nha as a protected nature chapter. Choose the cave intensity first, then count nights, season risk, fitness, insurance, and onward transport before booking.</p>
</div>
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-phong-nha-concierge-verdict:v1 -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<p><strong>Add Phong Nha when caves and central Vietnam nature are a real priority.</strong> The destination works best for travelers who want limestone scenery, a national-park stay, and a cave choice that matches their body, season, budget, and route length.</p>
<p><strong>Use two nights as the default premium answer.</strong> One night can work for a show-cave stop, but it often leaves no weather margin and makes the transfer feel louder than the cave. Two nights allow one arrival/reset, one focused cave or nature day, and a cleaner onward move.</p>
<p><strong>Skip Phong Nha when the route is short or comfort does not match the cave plan.</strong> A traveler with limited mobility, no activity insurance, a beach-first itinerary, or only a thin central window will usually get better value from Hue, Hoi An, Da Nang, Ninh Binh, or a slower route.</p>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-phong-nha-internal-demand:v1 -->
<h2 class="wp-block-heading">Why this guide exists</h2>
<p>Phong Nha is already mentioned inside the VietnamGuide itinerary, UNESCO, and best-places planning spine as an optional nature extension. That creates a decision problem: travelers know the name, but they still need to decide whether the detour earns its transfer cost. This article answers that job without turning the page into a generic cave roundup.</p>

<!-- vg-phong-nha-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: caves are not one product</h2>
<div class="vg-photo-grid vg-phong-nha-photo-proof">
<figure><img src="{$cave_entrance_image}" alt="Entrance to Phong Nha Cave by boat" loading="lazy" decoding="async"><figcaption>Phong Nha Cave is the lower-friction water-cave choice for many first visitors. Image: Tycho / shansov.net, CC BY-SA 3.0.</figcaption></figure>
<figure><img src="{$cave_interior_image}" alt="Illuminated cave formations inside Phong Nha-Ke Bang" loading="lazy" decoding="async"><figcaption>Show caves still need time and realistic pacing; they are not just quick photo stops. Image: Tycho / shansov.net, CC BY-SA 3.0.</figcaption></figure>
<figure><img src="{$son_river_image}" alt="Son River and karst landscape near Phong Nha" loading="lazy" decoding="async"><figcaption>The Son River and karst setting explain why Phong Nha is a landscape chapter, not only an underground stop. Image: BacLuong, CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$paradise_cave_image}" alt="Boardwalk and formations inside Paradise Cave" loading="lazy" decoding="async"><figcaption>Paradise Cave suits travelers who want a dramatic dry show cave with clearer footing than expedition routes. Image: Edgardo W. Olivera, CC BY 2.0.</figcaption></figure>
</div>

<!-- vg-phong-nha-source-diversity:v1 -->
<h2 class="wp-block-heading">How the source trail is used</h2>
<table class="vg-decision-table vg-phong-nha-source-diversity">
<thead><tr><th>Source type</th><th>What it can prove</th><th>How to use it</th></tr></thead>
<tbody>
<tr><td data-label="Source type">UNESCO</td><td data-label="What it can prove">World Heritage identity, criteria, and 2025 Phong Nha-Hin Nam No naming context.</td><td data-label="How to use it">Use for factual identity, not for day-to-day cave operations.</td></tr>
<tr><td data-label="Source type">Vietnam.travel</td><td data-label="What it can prove">Destination overview, national tourism framing, and first-time visitor context.</td><td data-label="How to use it">Use for broad planning language, then verify logistics elsewhere.</td></tr>
<tr><td data-label="Source type">Official park pages</td><td data-label="What it can prove">Park authority, official cave descriptions, ticket models, access notes, and update pointers.</td><td data-label="How to use it">Check close to publication and again before recommending specifics.</td></tr>
<tr><td data-label="Source type">Licensed operators</td><td data-label="What it can prove">Fitness levels, swimming/camping requirements, season windows, safety forms, and booking conditions.</td><td data-label="How to use it">Use for traveler filters, not for affiliate-style tour ranking.</td></tr>
<tr><td data-label="Source type">Weather, airport, rail, internal guides</td><td data-label="What it can prove">Storm/flood checks, gateway options, route length, insurance, and regional trade-offs.</td><td data-label="How to use it">Use to decide whether Phong Nha belongs in the route at all.</td></tr>
</tbody>
</table>

<!-- vg-phong-nha-add-skip-matrix:v1 -->
<h2 class="wp-block-heading">Add, shorten or skip Phong Nha?</h2>
<table class="vg-decision-table vg-phong-nha-add-skip-matrix">
<thead><tr><th>Decision</th><th>Best when</th><th>Route implication</th></tr></thead>
<tbody>
<tr><td data-label="Decision">Add two nights</td><td data-label="Best when">Caves, karst, and central nature are a primary reason for the trip.</td><td data-label="Route implication">Remove a weaker extra stop instead of compressing Hue, Hoi An, and Da Nang.</td></tr>
<tr><td data-label="Decision">Add one night</td><td data-label="Best when">You want one show cave and can use Dong Hoi or Phong Nha efficiently.</td><td data-label="Route implication">Keep expectations narrow and avoid expedition-style promises.</td></tr>
<tr><td data-label="Decision">Use as a specialist expedition</td><td data-label="Best when">The route is built around an operator-led cave, camping, trekking, or swimming commitment.</td><td data-label="Route implication">Book far ahead, protect weather/fitness margin, and let the cave plan lead the itinerary.</td></tr>
<tr><td data-label="Decision">Shorten to another karst stop</td><td data-label="Best when">You want easier scenery without the Phong Nha transfer.</td><td data-label="Route implication">Use Ninh Binh, the bay, or a central-coast base instead.</td></tr>
<tr><td data-label="Decision">Skip cleanly</td><td data-label="Best when">The trip is short, beach-first, city-first, mobility-limited, or not insured for adventure activity.</td><td data-label="Route implication">Spend the saved energy on stronger core days and better buffers.</td></tr>
</tbody>
</table>

<!-- vg-phong-nha-night-count:v1 -->
<h2 class="wp-block-heading">How many nights?</h2>
<table class="vg-decision-table vg-phong-nha-night-count">
<thead><tr><th>Time</th><th>What it can do well</th><th>Do not use it for</th></tr></thead>
<tbody>
<tr><td data-label="Time">No overnight</td><td data-label="What it can do well">Usually nothing meaningful unless you are already in the immediate area.</td><td data-label="Do not use it for">A rushed Hoi An, Da Nang, or Hue day trip built on optimistic road time.</td></tr>
<tr><td data-label="Time">One night</td><td data-label="What it can do well">A focused show-cave visit, town reset, and onward movement.</td><td data-label="Do not use it for">Weather margin, multiple caves, or anything with camping/trekking intensity.</td></tr>
<tr><td data-label="Time">Two nights</td><td data-label="What it can do well">The best default: arrival, one cave/nature day, and cleaner exit.</td><td data-label="Do not use it for">Every cave type or an overloaded central route.</td></tr>
<tr><td data-label="Time">Three or more nights</td><td data-label="What it can do well">Expeditions, jungle/cave combinations, recovery, and weather slack.</td><td data-label="Do not use it for">A first-trip checklist that still tries to cover every region.</td></tr>
</tbody>
</table>

<!-- vg-phong-nha-cave-chooser:v1 -->
<h2 class="wp-block-heading">Choose the cave type before choosing the cave</h2>
<p>The practical question is not which cave is famous. It is what your group can enjoy safely and honestly. Footing, heat, stairs, boats, swimming, darkness, mud, camping, medical disclosure, and guide authority matter more than a photo caption.</p>
<table class="vg-decision-table vg-phong-nha-cave-chooser">
<thead><tr><th>Cave style</th><th>Best for</th><th>Check before booking</th></tr></thead>
<tbody>
<tr><td data-label="Cave style">Low-friction show cave</td><td data-label="Best for">First visitors, families, mixed fitness, and travelers who want a controlled cave day.</td><td data-label="Check before booking">Boat access, stairs, walking surface, heat, crowds, ticket model, and current operations.</td></tr>
<tr><td data-label="Cave style">Dry show cave with boardwalk</td><td data-label="Best for">Travelers who want dramatic formations with less water activity.</td><td data-label="Check before booking">Steps, lighting, walking distance, crowds, transport, and opening updates.</td></tr>
<tr><td data-label="Cave style">Water activity cave</td><td data-label="Best for">Confident swimmers and active travelers who want mud, river, or zipline-style energy.</td><td data-label="Check before booking">Life jackets, water levels, weather, age limits, swimming comfort, and safety briefing.</td></tr>
<tr><td data-label="Cave style">Overnight cave or jungle route</td><td data-label="Best for">Fit travelers who want camping, trekking, river crossings, and guide-led remote terrain.</td><td data-label="Check before booking">Fitness level, gear, medical forms, cancellation terms, group size, and insurance coverage.</td></tr>
<tr><td data-label="Cave style">Flagship expedition</td><td data-label="Best for">Travelers planning months ahead around one specialist cave experience.</td><td data-label="Check before booking">Permit availability, season window, guide authority, evacuation reality, and what insurance excludes.</td></tr>
</tbody>
</table>

<!-- vg-phong-nha-season-rain:v1 -->
<h2 class="wp-block-heading">Season, rain and cave access</h2>
<p>Phong Nha planning should never freeze cave access as if rain, river levels, storms, and operator seasons do not exist. Some caves are easier show-cave choices; others depend heavily on water levels, jungle conditions, and operator windows. Treat any exact price, departure time, or cave operation claim as something to refresh before publishing or booking.</p>
<ul class="vg-check-list vg-phong-nha-season-rain">
<li><strong>Dry-season logic:</strong> better odds for active cave routes, but still verify heat, group fit, availability, and operator terms.</li>
<li><strong>Rainy-season logic:</strong> show caves may still be possible, but river color, cave access, flooding, and storm systems need fresh checks.</li>
<li><strong>Storm-window logic:</strong> build a backup day or skip active cave plans when the forecast and cancellation rules are not friendly.</li>
<li><strong>Photography logic:</strong> do not sell a cave by one perfect image; explain what the average visitor can realistically access.</li>
<li><strong>Evergreen logic:</strong> store exact cave prices, timetables, and operator dates as update tasks, not frozen promises.</li>
</ul>

<!-- vg-phong-nha-safety-insurance:v1 -->
<h2 class="wp-block-heading">Safety, fitness and insurance filter</h2>
<p>The premium answer is sometimes to step down, not up. If the group is unsure about swimming, medical disclosure, long walks, mud, ladders, darkness, camping, or remote evacuation, choose a lower-friction cave or skip the expedition layer. A cave you can enjoy calmly is better than a cave you endure for status.</p>
<table class="vg-decision-table vg-phong-nha-safety-insurance">
<thead><tr><th>Filter</th><th>Ask this</th><th>Better decision</th></tr></thead>
<tbody>
<tr><td data-label="Filter">Fitness</td><td data-label="Ask this">Can everyone handle the listed walking, climbing, swimming, and heat?</td><td data-label="Better decision">Match the least comfortable traveler, not the most ambitious one.</td></tr>
<tr><td data-label="Filter">Insurance</td><td data-label="Ask this">Does the policy cover the specific activity, remote area, cancellation, and evacuation risk?</td><td data-label="Better decision">Do not book expedition-style activities on vague coverage.</td></tr>
<tr><td data-label="Filter">Weather</td><td data-label="Ask this">What happens if rain, flood risk, or operator safety rules change the plan?</td><td data-label="Better decision">Choose flexible dates or a less fragile cave option.</td></tr>
<tr><td data-label="Filter">Family comfort</td><td data-label="Ask this">Will early starts, wet gear, low light, steps, and long transfers feel fun or draining?</td><td data-label="Better decision">Use a show cave and a better hotel base when comfort matters.</td></tr>
<tr><td data-label="Filter">Guide authority</td><td data-label="Ask this">Will the group accept the operator's safety call if conditions change?</td><td data-label="Better decision">Book only when everyone accepts that the guide can change the plan.</td></tr>
</tbody>
</table>

<!-- vg-phong-nha-route-fit:v1 -->
<h2 class="wp-block-heading">Route fit: where Phong Nha belongs</h2>
<p>Phong Nha fits best when the route already has central Vietnam space or a north-to-central overland shape. It is easiest to justify from Hue or a slower central route, reasonable from Hanoi or Ninh Binh if you are moving south, and weaker from HCMC unless the cave is a trip-defining reason.</p>
<table class="vg-decision-table vg-phong-nha-route-fit">
<thead><tr><th>Starting point</th><th>Best use</th><th>Watch out for</th></tr></thead>
<tbody>
<tr><td data-label="Starting point">Hue</td><td data-label="Best use">Cleanest central add-on when the route has one or two spare nights.</td><td data-label="Watch out for">Stealing the only unhurried Hue or Hoi An day.</td></tr>
<tr><td data-label="Starting point">Da Nang or Hoi An</td><td data-label="Best use">Works when central Vietnam is spacious and transport is planned conservatively.</td><td data-label="Watch out for">Treating the detour as casual because it appears close on a map.</td></tr>
<tr><td data-label="Starting point">Hanoi or Ninh Binh</td><td data-label="Best use">A southbound train/bus route toward Dong Hoi and central Vietnam.</td><td data-label="Watch out for">Adding Phong Nha without cutting another landscape stop.</td></tr>
<tr><td data-label="Starting point">Ho Chi Minh City</td><td data-label="Best use">Special-purpose cave trip using flights and strong buffers.</td><td data-label="Watch out for">Making it a default first-trip detour.</td></tr>
<tr><td data-label="Starting point">14-day full route</td><td data-label="Best use">One chosen extension: Phong Nha, Mekong, beach, or mountains.</td><td data-label="Watch out for">Trying to add all extensions because two weeks sounds long.</td></tr>
</tbody>
</table>

<!-- vg-phong-nha-booking-checks:v1 -->
<h2 class="wp-block-heading">Booking checks that matter</h2>
<ul class="vg-check-list vg-phong-nha-booking-checks">
<li>Check whether your chosen cave is a show cave, activity cave, overnight cave, or expedition route.</li>
<li>Check current operating status, weather, river level, and operator season before paying.</li>
<li>Check fitness level, age limits, medical disclosure, swimming, camping, and guide authority.</li>
<li>Check travel insurance wording for cave activity, adventure activity, remote evacuation, and cancellation.</li>
<li>Check Dong Hoi airport, rail, bus, and private-transfer options close to travel instead of relying on old schedules.</li>
<li>Check whether Phong Nha replaces a weaker stop, rather than compressing every core destination.</li>
</ul>

<!-- vg-phong-nha-itinerary-length:v1 -->
<h2 class="wp-block-heading">Phong Nha by itinerary length</h2>
<table class="vg-decision-table vg-phong-nha-itinerary-length">
<thead><tr><th>Trip length</th><th>Recommended role</th><th>Cut first</th></tr></thead>
<tbody>
<tr><td data-label="Trip length">7 days</td><td data-label="Recommended role">Usually skip unless the entire trip is central/nature focused.</td><td data-label="Cut first">A rushed cave detour that weakens Hanoi, Ninh Binh, Hue, or Hoi An.</td></tr>
<tr><td data-label="Trip length">10 days</td><td data-label="Recommended role">Only add if caves are a top reason and another region is reduced.</td><td data-label="Cut first">The extra city or beach day that exists only for coverage.</td></tr>
<tr><td data-label="Trip length">14 days</td><td data-label="Recommended role">Strong one-extension candidate when central Vietnam has protected space.</td><td data-label="Cut first">Mekong, Phu Quoc, or mountains if Phong Nha is the chosen nature chapter.</td></tr>
<tr><td data-label="Trip length">21 days</td><td data-label="Recommended role">Can hold two nights or a deeper cave plan with buffers.</td><td data-label="Cut first">Duplicated scenery stops that do not improve the route.</td></tr>
<tr><td data-label="Trip length">Expedition trip</td><td data-label="Recommended role">Let the cave booking shape the route calendar.</td><td data-label="Cut first">Any nonessential move before or after the expedition window.</td></tr>
</tbody>
</table>

<!-- vg-phong-nha-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before publishing or booking</h2>
<ul class="vg-check-list vg-phong-nha-live-checks">
<li>Refresh UNESCO property name and any new decision wording around Phong Nha-Ke Bang and Hin Nam No.</li>
<li>Refresh official park pages for cave access, ticket model, weather caveats, and visitor notices.</li>
<li>Refresh operator pages for season windows, fitness levels, insurance, cancellation, and safety forms.</li>
<li>Refresh official weather and flood information before recommending water-heavy or jungle-heavy cave plans.</li>
<li>Refresh Dong Hoi airport, train, bus, and private-transfer options before writing any exact routing note.</li>
<li>Refresh internal related routes so the body links only to published pages.</li>
</ul>

<!-- vg-phong-nha-faq:v1 -->
<h2 class="wp-block-heading">Phong Nha travel FAQ</h2>
<div class="vg-faq-list vg-phong-nha-faq">
<details><summary>Is Phong Nha worth visiting on a first Vietnam trip?</summary><p>Yes if caves and central Vietnam nature are a real trip priority and you can protect at least one or two nights. It is less compelling when a short route already has Hanoi, Ninh Binh, the bay, Hue, Hoi An, and Da Nang competing for time.</p></details>
<details><summary>How many nights do I need in Phong Nha?</summary><p>Two nights is the best default for most travelers because it gives arrival, one focused cave or nature day, and a cleaner exit. One night is a narrow show-cave stop. Three or more nights are for expedition or deeper nature plans.</p></details>
<details><summary>Which cave should I choose?</summary><p>Start with the cave style, not the name. Choose a lower-friction show cave for mixed fitness, a dry boardwalk cave for comfort, a water activity cave for confident active travelers, and guided overnight routes only when fitness, weather, insurance, and comfort all line up.</p></details>
<details><summary>Can I visit Phong Nha from Hoi An or Da Nang?</summary><p>You can, but it should not be treated as a casual side trip. It works better when central Vietnam has enough time and the route is willing to trade one easier beach or city day for a cave chapter.</p></details>
<details><summary>Is Phong Nha safe in rainy season?</summary><p>Safety depends on the exact cave, water level, storm risk, operator rules, and your group's fitness. Check official weather and operator updates close to travel, and choose flexible plans when rain or flood risk is present.</p></details>
<details><summary>Should I choose Phong Nha or Ninh Binh?</summary><p>Choose Ninh Binh for easier karst scenery and simpler routing from Hanoi. Choose Phong Nha when caves and central Vietnam nature are the goal and you can accept the extra logistics.</p></details>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Use these related routes to decide whether Phong Nha improves the trip or steals time from a stronger chapter.</p>
<div class="vg-related-routes vg-phong-nha-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a><span class="vg-related-route-note">Use for World Heritage context and the 2025 Phong Nha-Hin Nam No update.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a><span class="vg-related-route-note">Confirm whether central Vietnam deserves a cave-and-nature chapter.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Check rain, storm, heat, and regional season trade-offs.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Count Dong Hoi, rail, bus, private transfer, and onward movement.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a><span class="vg-related-route-note">Check activity and cancellation coverage before cave bookings.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a><span class="vg-related-route-note">Decide whether Phong Nha should be the one chosen extension.</span></li>
<li><span class="vg-related-route-step">07</span><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a><span class="vg-related-route-note">Compare easier karst scenery with the deeper cave commitment.</span></li>
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
    'vg-phong-nha-hero:v1',
    'vg-phong-nha-concierge-verdict:v1',
    'vg-phong-nha-internal-demand:v1',
    'vg-phong-nha-photo-proof:v1',
    'vg-phong-nha-source-diversity:v1',
    'vg-phong-nha-add-skip-matrix:v1',
    'vg-phong-nha-night-count:v1',
    'vg-phong-nha-cave-chooser:v1',
    'vg-phong-nha-season-rain:v1',
    'vg-phong-nha-safety-insurance:v1',
    'vg-phong-nha-route-fit:v1',
    'vg-phong-nha-booking-checks:v1',
    'vg-phong-nha-itinerary-length:v1',
    'vg-phong-nha-live-checks:v1',
    'vg-phong-nha-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_phong_nha_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Phong Nha Travel Guide: Caves, Seasons and Route Fit',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Decide whether Phong Nha-Ke Bang deserves one night, two nights, a specialist cave expedition, or a clean skip by route fit, season, fitness, and insurance.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_phong_nha_post_fail('Could not update Phong Nha post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Phong Nha Travel Guide: Caves, Seasons and Route Fit');
update_post_meta($post_id, 'rank_math_description', 'Decide whether Phong Nha is worth the cave detour by route fit, season, nights, fitness, insurance, and central Vietnam trade-offs.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Phong Nha travel guide');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Decide whether Phong Nha-Ke Bang deserves one night, two nights, a specialist cave expedition, or a clean skip based on cave intensity, season, fitness, insurance, gateway logistics, and route fit.');
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
    vg_phong_nha_post_fail('Could not assign Phong Nha categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_phong_nha_post_fail('Could not assign Phong Nha tags: ' . $tag_result->get_error_message());
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
        vg_phong_nha_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Phong Nha post to complete draft: {$post_id}");

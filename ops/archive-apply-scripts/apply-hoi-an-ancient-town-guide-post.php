<?php
/**
 * Expand the Hoi An Ancient Town Guide post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-hoi-an-ancient-town-guide-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_hoi_an_ancient_town_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_hoi_an_ancient_town_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_hoi_an_ancient_town_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_hoi_an_ancient_town_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_hoi_an_ancient_town_post_find_by_slug(string $slug): ?WP_Post
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
        vg_hoi_an_ancient_town_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_hoi_an_ancient_town_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-17',
            'vg_editorial_brief_status' => ['brief', 'complete_draft'],
            'vg_editorial_batch' => 'batch-2-evergreen-planning',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if (is_array($expected_value)) {
            if (! in_array($actual_value, $expected_value, true)) {
                vg_hoi_an_ancient_town_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected one of " . implode(', ', $expected_value) . '.');
            }
            continue;
        }

        if ($actual_value !== $expected_value) {
            vg_hoi_an_ancient_town_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_hoi_an_ancient_town_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_hoi_an_ancient_town_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'hoi-an-ancient-town-guide';
$post = vg_hoi_an_ancient_town_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_hoi_an_ancient_town_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_hoi_an_ancient_town_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 26, 2026';

if ($post_id !== 499) {
    vg_hoi_an_ancient_town_post_fail("Refusing to update {$slug}: expected post ID 499, found {$post_id}.");
}

vg_hoi_an_ancient_town_post_assert_target_meta($post);

$category_term_ids = vg_hoi_an_ancient_town_post_term_ids('category', ['destinations', 'heritage-culture']);
$tag_term_ids = vg_hoi_an_ancient_town_post_term_ids('post_tag', ['heritage-travel', 'first-time-vietnam', 'premium-travel', 'anti-spam-evergreen']);

$ancient_town_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$lantern_street_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d7/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-05.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-05.jpg');
$japanese_bridge_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/69/Japanese_covered-bridge_of_Hoi_An_in_2015_11.jpg/1280px-Japanese_covered-bridge_of_Hoi_An_in_2015_11.jpg');
$old_town_night_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/68/2024-12-20_Hoi_An_Old_Town_at_night_3.jpg/1920px-2024-12-20_Hoi_An_Old_Town_at_night_3.jpg');

$meta_update_summary = 'Expanded the native WordPress batch 2 brief into a complete Hoi An Ancient Town decision draft with photo-led hero, proof panel, concierge verdict, stay-visit-skip matrix, GSC demand note, photo proof, source-diversity table, day vs evening pacing, overnight vs day-trip logic, Da Nang vs Hoi An base guidance, heritage/ticket live checks, heat/rain/crowd rhythm, food/tailoring/beach fit, route-length matrix, skip logic, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "UNESCO World Heritage Centre - Hoi An Ancient Town - https://whc.unesco.org/en/list/948/ - checked {$review_date}; used for heritage value, authenticity/integrity framing, and conservation-sensitive visitor tone.",
    "Vietnam.travel - Hoi An destination page - https://vietnam.travel/places-to-go/central-vietnam/hoi-an - checked {$review_date}; used for Hoi An route role, old-town rhythm, food, tailoring, coast, and central Vietnam placement.",
    "Vietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked {$review_date}; used for Da Nang, Hoi An, Hue, coast, and heritage-cluster context.",
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for central Vietnam rain, heat, and flexible time-of-day guidance.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for Da Nang airport, road-transfer, and central-base decision logic.",
    "Hoi An World Heritage official site - http://hoianworldheritage.org.vn/en - checked {$review_date}; used only as a live-check pointer for ticket/visitor-rule verification, without freezing prices in the article.",
    "Google Search Console query export from 2026-07-25 - checked {$review_date}; used for observed Da Nang-or-Hoi An and early Hoi An things-to-do demand.",
    "Internal published page check - https://vietnamguide.net/destinations/best-things-to-do-in-hoi-an/ - checked {$review_date}; used for attraction-depth handoff.",
    "Internal published page check - https://vietnamguide.net/compare/da-nang-vs-hoi-an/ - checked {$review_date}; used for base-choice handoff.",
    "Internal published page check - https://vietnamguide.net/compare/hoi-an-vs-hue/ - checked {$review_date}; used for central heritage trade-off handoff.",
    "Internal published page check - https://vietnamguide.net/destinations/unesco-heritage-sites-vietnam/ - checked {$review_date}; used for heritage-cluster handoff.",
    "Wikimedia Commons image direct URL - Hoi An Ancient Town CN-11 - {$ancient_town_image} - credit Steffen Schmitz / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hoi An Ancient Town CN-05 - {$lantern_street_image} - credit Steffen Schmitz / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Japanese covered bridge of Hoi An - {$japanese_bridge_image} - credit Vuong Tri Binh / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hoi An Old Town at night - {$old_town_night_image} - credit Alexkom000 / CC BY 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This complete draft treats Hoi An Ancient Town as a route-pacing decision, not as a lantern-photo checklist. It helps travelers decide whether to stay overnight, visit from Da Nang, protect an evening, visit by day, add beach time, add tailoring or food depth, or skip Hoi An when central Vietnam needs Hue, Da Nang, or a calmer transfer plan more.';
$meta_evidence_moat = implode("\n", [
    'UNESCO framing is used to explain why visitor behavior and pacing matter, not to paste monument history.',
    'The core verdict separates overnight, day trip, evening-only, and skip decisions by route job.',
    'Da Nang vs Hoi An base logic connects this article to live published comparison intent visible in GSC.',
    'Ticket/live-rule guidance tells readers what to verify close to travel without freezing volatile prices.',
    'Day vs evening tables give practical crowd, heat, food, photography, and heritage trade-offs.',
    'Skip logic protects short trips from adding Hoi An only because it appears on every list.',
    'Photo proof captions explain route value and time-of-day differences rather than decorating the page.',
    'No visible external body anchors; official sources and image credits stay auditable in metadata/source trail.',
]);
$meta_related_routes = implode("\n", [
    'Best Things to Do in Hoi An | /destinations/best-things-to-do-in-hoi-an/ | Use after deciding that Hoi An deserves enough time for specific sights, food, and walking rhythm.',
    'Da Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Choose whether to sleep in Hoi An atmosphere or Da Nang logistics before booking hotels.',
    'Hoi An vs Hue | /compare/hoi-an-vs-hue/ | Decide whether central Vietnam should prioritize old-town atmosphere, imperial history, or both.',
    'UNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Put Hoi An inside the broader heritage route without treating every site as mandatory.',
    'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use when central Vietnam needs airport access, beach comfort, and easier day-trip logistics.',
    'Best Things to Do in Hue | /destinations/best-things-to-do-in-hue/ | Add Hue only when imperial history deserves protected time.',
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Put Hoi An inside the full first-trip planning order.',
    'North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Decide whether central Vietnam deserves the route center.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check central Vietnam weather before locking Hoi An as the emotional base.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Count Da Nang airport, road transfers, luggage, and onward movement before choosing Hoi An nights.',
    '7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Use when a short trip may need to choose one region and skip central Vietnam.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Hoi An should be the central anchor or only a focused stop.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use when two weeks can protect Hoi An, Hue, Da Nang, and one northern or southern chapter.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the difference between sleeping in Hoi An, sleeping in Da Nang, and adding private transfers.',
]);
$meta_hero_image_credit = 'Hero image: Hoi An Ancient Town CN-11 by Steffen Schmitz, CC BY-SA 4.0. Body images: Hoi An Ancient Town CN-05 by Steffen Schmitz, CC BY-SA 4.0; Japanese covered bridge of Hoi An by Vuong Tri Binh, CC BY-SA 4.0; Hoi An Old Town at night by Alexkom000, CC BY 4.0.';
$meta_admin_notes = 'Complete draft generated for WordPress Admin review. Keep draft until manual preview, Rank Math review, image check, and final editorial pass are complete. The body intentionally avoids external anchors; source URLs are preserved in metadata and source trail.';

$content = <<<HTML
<!-- vg-hoi-an-ancient-town-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$ancient_town_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hoi An Ancient Town street and yellow heritage houses" src="{$ancient_town_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<div class="wp-block-group vg-guide-hero-inner">
<p class="vg-kicker">Reviewed heritage guide - Updated {$review_date}</p>
<h1 class="wp-block-heading vg-display vg-guide-title">Hoi An Ancient Town Guide: When to Stay, Visit or Skip</h1>
<p class="vg-guide-lede">Hoi An Ancient Town can be the emotional center of a Vietnam trip, a beautiful evening from Da Nang, or an overcrowded detour that steals time from the route. The difference is not whether Hoi An is famous. It is whether you give it the right job.</p>
<p class="vg-field-note">Concierge verdict: stay overnight when Hoi An is the central Vietnam atmosphere chapter; visit from Da Nang when logistics matter more; skip cleanly when the route already has enough heritage and not enough rest.</p>
</div>
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-hoi-an-ancient-town-concierge-verdict:v1 -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<p><strong>Stay in Hoi An for two nights if the old town, food, cafes, walking evenings, tailoring, cooking classes, or a calmer central Vietnam chapter are part of the reason you are coming.</strong> Hoi An rewards protected time. It is least useful when it is squeezed between Da Nang airport, Hue, a beach day, and a flight with no margin.</p>
<p><strong>Visit Hoi An as a day or evening trip from Da Nang if your route needs airport access, beach hotels, family comfort, or simpler transfers.</strong> That is not a lesser version of Hoi An. It is the better version when your trip has limited nights or when the hotel base needs to solve logistics more than atmosphere.</p>
<p><strong>Skip Hoi An when adding it would turn central Vietnam into a chain of check-ins.</strong> If you already have Hanoi heritage, Hue imperial history, Da Nang beach comfort, and only a short route, one protected day may beat another famous stop.</p>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-hoi-an-ancient-town-stay-visit-skip:v1 -->
<!-- vg-hoi-an-ancient-town-decision-matrix:v1 -->
<h2 class="wp-block-heading">Stay, visit or skip matrix</h2>
<table class="vg-decision-table vg-hoi-an-ancient-town-stay-visit-skip">
<thead><tr><th>Choice</th><th>Best when</th><th>Weak when</th><th>Minimum time</th></tr></thead>
<tbody>
<tr><td data-label="Choice">Stay overnight in Hoi An</td><td data-label="Best when">You want old-town evenings, food, cafes, tailoring, cooking class, beach edge, or slower walking rhythm.</td><td data-label="Weak when">You have an early Da Nang flight or need a modern beach-hotel base.</td><td data-label="Minimum time">2 nights</td></tr>
<tr><td data-label="Choice">Visit from Da Nang</td><td data-label="Best when">You want airport access, beach comfort, family logistics, or one focused Hoi An evening.</td><td data-label="Weak when">You expect Hoi An to feel calm while rushing both afternoon and night crowds.</td><td data-label="Minimum time">Half day plus evening</td></tr>
<tr><td data-label="Choice">Daytime heritage visit</td><td data-label="Best when">You care about houses, assembly halls, museums, ticketed sites, and quieter photography.</td><td data-label="Weak when">Heat is intense or the schedule has no shaded break.</td><td data-label="Minimum time">4-6 hours</td></tr>
<tr><td data-label="Choice">Evening visit</td><td data-label="Best when">You want atmosphere, river lights, dinner, low-pressure wandering, and a memorable central Vietnam night.</td><td data-label="Weak when">You dislike crowds or only have one tired transfer evening.</td><td data-label="Minimum time">3-4 hours</td></tr>
<tr><td data-label="Choice">Skip Hoi An</td><td data-label="Best when">The route needs Hue history, Da Nang beach rest, or fewer transfers more than another famous place.</td><td data-label="Weak when">Hoi An atmosphere is one of the trip's core reasons.</td><td data-label="Minimum time">No guilt required</td></tr>
</tbody>
</table>

<!-- vg-hoi-an-ancient-town-gsc-demand:v1 -->
<h2 class="wp-block-heading">Search evidence shaping this draft</h2>
<p>Recent Search Console data for vietnamguide.net shows early central Vietnam comparison demand, including "da nang or hoi an", "hoi an or danang", "da nang hoi an", and "best things to do in hoi an". That is why this guide focuses on the higher-value decision before the attraction list: should Hoi An be your base, a visit from Da Nang, or a stop you deliberately skip?</p>

<!-- vg-hoi-an-ancient-town-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: what Hoi An actually solves</h2>
<div class="vg-photo-grid vg-hoi-an-ancient-town-photo-proof">
<figure><img src="{$ancient_town_image}" alt="Hoi An Ancient Town yellow houses and street scene" loading="lazy" decoding="async"><figcaption>The old-town streets justify an overnight when walking atmosphere is a core route goal. Image: Steffen Schmitz / CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$lantern_street_image}" alt="Hoi An Ancient Town evening lanterns" loading="lazy" decoding="async"><figcaption>Evening atmosphere is real, but it needs time and crowd tolerance, not just a rushed photo stop. Image: Steffen Schmitz / CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$japanese_bridge_image}" alt="Japanese Covered Bridge in Hoi An" loading="lazy" decoding="async"><figcaption>Heritage anchors work best in a slower daytime visit when ticket rules and restoration context are checked locally. Image: Vuong Tri Binh / CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$old_town_night_image}" alt="Hoi An Old Town at night" loading="lazy" decoding="async"><figcaption>Night atmosphere can be memorable when it is planned as a simple evening, not a rushed queue for the same photo. Image: Alexkom000 / CC BY 4.0.</figcaption></figure>
</div>

<!-- vg-hoi-an-ancient-town-source-diversity:v1 -->
<h2 class="wp-block-heading">Source diversity behind this guide</h2>
<table class="vg-decision-table vg-hoi-an-ancient-town-source-diversity">
<thead><tr><th>Evidence layer</th><th>What it can prove</th><th>What still needs editorial judgment</th></tr></thead>
<tbody>
<tr><td data-label="Evidence layer">UNESCO listing</td><td data-label="What it can prove">Hoi An's heritage significance, urban fabric, authenticity, and conservation importance.</td><td data-label="What still needs editorial judgment">Whether a traveler should stay overnight, visit from Da Nang, or skip.</td></tr>
<tr><td data-label="Evidence layer">Vietnam.travel destination pages</td><td data-label="What it can prove">Hoi An's central Vietnam role, food, old town, coast, and nearby-route context.</td><td data-label="What still needs editorial judgment">How much route time Hoi An deserves compared with Da Nang or Hue.</td></tr>
<tr><td data-label="Evidence layer">Official local heritage/ticket source</td><td data-label="What it can prove">Where to live-check current visitor rules and tickets close to travel.</td><td data-label="What still needs editorial judgment">Whether ticketed sights improve a specific travel day.</td></tr>
<tr><td data-label="Evidence layer">GSC demand</td><td data-label="What it can prove">Readers are already comparing Da Nang and Hoi An and asking for Hoi An activities.</td><td data-label="What still needs editorial judgment">Which comparison deserves the top answer before attraction detail.</td></tr>
<tr><td data-label="Evidence layer">Licensed real images</td><td data-label="What it can prove">The difference between street, evening, heritage-site, and river rhythm.</td><td data-label="What still needs editorial judgment">Whether that mood is worth a hotel night.</td></tr>
</tbody>
</table>

<!-- vg-hoi-an-ancient-town-day-vs-evening:v1 -->
<h2 class="wp-block-heading">Day vs evening in Hoi An Ancient Town</h2>
<p>Hoi An changes character by time of day. Many rushed travelers only see the most crowded evening version, then leave with a pretty but shallow impression. The better move is to decide what time of day matches the job you need.</p>
<table class="vg-decision-table vg-hoi-an-ancient-town-day-vs-evening">
<thead><tr><th>Time</th><th>Best use</th><th>Main risk</th><th>How to make it work</th></tr></thead>
<tbody>
<tr><td data-label="Time">Early morning</td><td data-label="Best use">Photography, markets waking up, quiet lanes, coffee, low heat.</td><td data-label="Main risk">Some ticketed houses or shops may not be active yet.</td><td data-label="How to make it work">Stay overnight or sleep close enough that you do not need a long transfer.</td></tr>
<tr><td data-label="Time">Late morning</td><td data-label="Best use">Heritage houses, assembly halls, museums, guided context, food stops.</td><td data-label="Main risk">Heat and tour-group movement can rise quickly.</td><td data-label="How to make it work">Buy or confirm ticket rules locally, then group heritage stops by walking distance.</td></tr>
<tr><td data-label="Time">Afternoon</td><td data-label="Best use">Tailoring appointments, cafes, hotel rest, cooking class, beach break.</td><td data-label="Main risk">This can become the least comfortable old-town walking window.</td><td data-label="How to make it work">Treat afternoon as recovery, not proof that Hoi An is boring.</td></tr>
<tr><td data-label="Time">Evening</td><td data-label="Best use">Lantern streets, river walk, dinner, soft wandering, first-trip atmosphere.</td><td data-label="Main risk">Crowds, photo pressure, boats, and oversold romantic expectations.</td><td data-label="How to make it work">Arrive before peak dinner time and keep the plan simple.</td></tr>
</tbody>
</table>

<!-- vg-hoi-an-ancient-town-overnight-vs-day-trip:v1 -->
<h2 class="wp-block-heading">Overnight Hoi An vs day trip from Da Nang</h2>
<p>The real choice is not Hoi An versus no Hoi An. It is whether Hoi An should carry the hotel-base job. Use <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a> when the hotel decision is still open.</p>
<h3>Stay overnight when Hoi An is the point</h3>
<p>Choose an overnight when your central Vietnam chapter is about old-town walking, food, tailoring, cafes, a slower river evening, and easy returns after dinner. Two nights is the practical minimum because one night often collapses into arrival, dinner, packing, and departure.</p>
<h3>Visit from Da Nang when logistics are the point</h3>
<p>Choose a Da Nang base when airport access, beach hotels, child-friendly facilities, wider roads, and easier movement matter more than sleeping inside Hoi An's atmosphere. You can still get a strong Hoi An evening from Da Nang if you do not overload the day.</p>
<h3>Split stays only when each base has a different job</h3>
<p>Sleeping one night in Da Nang and one night in Hoi An can work, but only if Da Nang solves arrival or beach comfort and Hoi An solves an evening plus morning. If both bases are doing the same activities, the split mostly creates luggage friction.</p>

<!-- vg-hoi-an-ancient-town-ticket-live-check:v1 -->
<h2 class="wp-block-heading">Ticket and heritage live checks</h2>
<p>Hoi An Ancient Town has a living-city feel, but some heritage houses, assembly halls, museums, and preserved sites may involve local ticket rules, restoration conditions, or opening-hour changes. Do not build the day around a fixed ticket price copied from an old article. Check the current local rule near travel, then choose whether the ticketed heritage layer actually improves your route.</p>
<ul class="vg-check-list vg-hoi-an-ancient-town-ticket-live-check">
<li>Confirm current Ancient Town ticket rules locally before promising yourself a specific set of houses or halls.</li>
<li>Check whether the Japanese Covered Bridge or any key site has restoration, access, or viewing restrictions.</li>
<li>Decide whether you want guided context; Hoi An is more interesting when architecture, trade history, and household life are explained.</li>
<li>Keep cash available for small purchases, tickets, snacks, tailoring deposits, or local transport.</li>
<li>Respect residential lanes, ancestor altars, assembly halls, temples, and private thresholds.</li>
</ul>

<!-- vg-hoi-an-ancient-town-heat-rain-crowd:v1 -->
<h2 class="wp-block-heading">Heat, rain and crowd rhythm</h2>
<p>Hoi An is fragile when every important moment is placed into one hot afternoon or one crowded evening. The better plan gives the old town a morning, a late-afternoon reset, and an evening that does not have to carry the whole trip.</p>
<table class="vg-decision-table vg-hoi-an-ancient-town-heat-rain-crowd">
<thead><tr><th>Risk</th><th>What goes wrong</th><th>Better plan</th></tr></thead>
<tbody>
<tr><td data-label="Risk">Heat</td><td data-label="What goes wrong">Walking turns into endurance and the old town feels smaller than it is.</td><td data-label="Better plan">Do heritage stops early, rest in the afternoon, return for dinner.</td></tr>
<tr><td data-label="Risk">Rain</td><td data-label="What goes wrong">Outdoor photo plans and river wandering become unreliable.</td><td data-label="Better plan">Use cafes, cooking, tailoring, museums, guided houses, and flexible meal timing.</td></tr>
<tr><td data-label="Risk">Crowds</td><td data-label="What goes wrong">The evening becomes a queue for the same photos everyone saw online.</td><td data-label="Better plan">Arrive before peak, choose side lanes, and let dinner anchor the evening.</td></tr>
<tr><td data-label="Risk">Transfer fatigue</td><td data-label="What goes wrong">Hoi An becomes one more hotel move instead of a pause.</td><td data-label="Better plan">Sleep in one base and make Hoi An a focused chapter.</td></tr>
</tbody>
</table>

<!-- vg-hoi-an-ancient-town-route-fit:v1 -->
<h2 class="wp-block-heading">How Hoi An fits central Vietnam</h2>
<p>Hoi An is strongest when paired deliberately with Da Nang and Hue, not when all three are treated as equal checklist stops. Da Nang solves airport, beach, hotels, and logistics. Hoi An solves atmosphere, walking, food, and a slower emotional chapter. Hue solves imperial history and a deeper heritage day. Use <a href="/compare/hoi-an-vs-hue/">Hoi An vs Hue</a> if you cannot give both enough time.</p>
<table class="vg-decision-table vg-hoi-an-ancient-town-route-fit">
<thead><tr><th>Route shape</th><th>Hoi An role</th><th>What to protect</th></tr></thead>
<tbody>
<tr><td data-label="Route shape">Da Nang airport plus Hoi An</td><td data-label="Hoi An role">Atmosphere base after arrival or before departure.</td><td data-label="What to protect">Do not land late and expect a meaningful old-town evening immediately.</td></tr>
<tr><td data-label="Route shape">Da Nang beach plus Hoi An evening</td><td data-label="Hoi An role">Evening heritage/food chapter from a beach hotel.</td><td data-label="What to protect">Return transport and a simple dinner plan.</td></tr>
<tr><td data-label="Route shape">Hue plus Hoi An</td><td data-label="Hoi An role">Contrast after imperial history and longer transfer.</td><td data-label="What to protect">A slow first evening rather than another attraction sprint.</td></tr>
<tr><td data-label="Route shape">North-central first trip</td><td data-label="Hoi An role">Main central atmosphere stop after Hanoi/Ninh Binh/bay.</td><td data-label="What to protect">Two nights if the route can afford it.</td></tr>
<tr><td data-label="Route shape">South or island-focused trip</td><td data-label="Hoi An role">Optional central detour only if flights and time are clean.</td><td data-label="What to protect">Do not add Hoi An at the cost of the main trip purpose.</td></tr>
</tbody>
</table>

<!-- vg-hoi-an-ancient-town-food-tailoring-beach:v1 -->
<h2 class="wp-block-heading">Food, tailoring and beach: add only what supports the stay</h2>
<p>Food, tailoring, cooking classes, and the nearby coast can make Hoi An feel richer, but they also create appointment pressure. A good Hoi An plan does not need every famous dish, a rushed suit fitting, a cooking class, a basket boat, a beach afternoon, every assembly hall, and a lantern evening in one night.</p>
<ul class="vg-check-list vg-hoi-an-ancient-town-food-tailoring-beach">
<li><strong>Food:</strong> protect one unhurried lunch or dinner, then let the attraction list stay smaller.</li>
<li><strong>Tailoring:</strong> stay at least two nights if fittings matter; a rushed custom order is not a souvenir upgrade.</li>
<li><strong>Cooking class:</strong> choose it when you want a slower cultural block, not when it steals the only heritage morning.</li>
<li><strong>Beach:</strong> add An Bang or the coast when rest is part of the central chapter; use Da Nang if beach hotels are the priority.</li>
<li><strong>Photography:</strong> use early morning or a calmer evening edge instead of making the whole visit revolve around the busiest lantern moment.</li>
</ul>

<!-- vg-hoi-an-ancient-town-trip-length:v1 -->
<h2 class="wp-block-heading">Hoi An by trip length</h2>
<table class="vg-decision-table vg-hoi-an-ancient-town-trip-length">
<thead><tr><th>Trip length</th><th>Recommended Hoi An role</th><th>What to cut first</th></tr></thead>
<tbody>
<tr><td data-label="Trip length">5-6 days</td><td data-label="Recommended Hoi An role">Only include Hoi An if central Vietnam is the whole trip.</td><td data-label="What to cut first">Cross-country flights to add one famous old town.</td></tr>
<tr><td data-label="Trip length">7 days</td><td data-label="Recommended Hoi An role">Use Hoi An as the central anchor or skip central Vietnam entirely.</td><td data-label="What to cut first">Trying to combine Hanoi, bay, Hoi An, Hue, HCMC, and Mekong.</td></tr>
<tr><td data-label="Trip length">10 days</td><td data-label="Recommended Hoi An role">Two nights can work after Hanoi/Ninh Binh or before a southern exit.</td><td data-label="What to cut first">An extra city that does the same route job.</td></tr>
<tr><td data-label="Trip length">14 days</td><td data-label="Recommended Hoi An role">Hoi An plus Hue or Da Nang can both work when each has a different job.</td><td data-label="What to cut first">A one-night hotel shuffle that adds no better day.</td></tr>
<tr><td data-label="Trip length">21 days</td><td data-label="Recommended Hoi An role">Protect a deeper Hoi An chapter with food, beach, craft, and rest.</td><td data-label="What to cut first">Overbuilt side trips that erase the calm Hoi An is supposed to provide.</td></tr>
</tbody>
</table>

<!-- vg-hoi-an-ancient-town-skip-logic:v1 -->
<h2 class="wp-block-heading">Skip logic that makes Hoi An better</h2>
<ul class="vg-check-list vg-hoi-an-ancient-town-skip-logic">
<li>Skip an overnight if you only have a late arrival and an early departure from Da Nang.</li>
<li>Skip daytime heritage depth if the traveler only wants a dinner-and-lantern evening from Da Nang.</li>
<li>Skip the evening crowd if early morning photography, cafes, and heritage houses are the real interest.</li>
<li>Skip tailoring if the route cannot support fittings and decisions without stress.</li>
<li>Skip Hoi An entirely when Hue or Da Nang better solves the central Vietnam chapter for that specific trip.</li>
<li>Skip adding unpublished or unverified side trips into the plan until they have current rules and a clear route job.</li>
</ul>

<!-- vg-hoi-an-ancient-town-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before booking Hoi An</h2>
<ul class="vg-check-list vg-hoi-an-ancient-town-live-checks">
<li>Check Da Nang arrival and departure times before committing to a Hoi An hotel.</li>
<li>Check current Ancient Town ticket and site-access rules near travel, especially for preserved houses and the Japanese Covered Bridge.</li>
<li>Check central Vietnam weather and flood/rain risk if traveling in more volatile months.</li>
<li>Check whether your hotel is inside walking distance, beach-side, river-side, or outside town; each solves a different trip.</li>
<li>Check transport back to Da Nang before making Hoi An a late-night day trip.</li>
<li>Check whether tailoring, cooking, beach, or heritage gets the priority block; do not make all four fight for one day.</li>
</ul>

<!-- vg-hoi-an-ancient-town-faq:v1 -->
<h2 class="wp-block-heading">Hoi An Ancient Town FAQ</h2>
<div class="vg-faq-list vg-hoi-an-ancient-town-faq">
<details><summary>Is Hoi An Ancient Town worth visiting?</summary><p>Yes when old-town atmosphere, food, walking, heritage houses, river evenings, tailoring, or central Vietnam charm matter to the trip. It is less worth it when the route is too short and Hoi An becomes only another transfer.</p></details>
<details><summary>Should I stay in Hoi An or Da Nang?</summary><p>Stay in Hoi An for atmosphere, walking evenings, food, and a slower old-town chapter. Stay in Da Nang for airport access, beach hotels, family logistics, and easier movement. Use the Hoi An visit from Da Nang when you need both atmosphere and logistics.</p></details>
<details><summary>How many nights do I need in Hoi An?</summary><p>Two nights is the most useful minimum if Hoi An matters. One night can work only when arrival and departure times are gentle. Three nights makes sense when you add tailoring, cooking, beach time, or a slow central Vietnam reset.</p></details>
<details><summary>Is Hoi An better by day or at night?</summary><p>Daytime is better for heritage houses, guided context, markets, cafes, and quieter lanes. Evening is better for atmosphere, dinner, river lights, and a memorable first-trip mood. The best stay often uses both.</p></details>
<details><summary>Do I need a ticket for Hoi An Ancient Town?</summary><p>Some preserved houses, assembly halls, museums, or heritage sites may require local ticket checks. Rules and prices can change, so verify current details near travel rather than relying on an old fixed price.</p></details>
<details><summary>Can I visit Hoi An as a day trip from Da Nang?</summary><p>Yes. A focused afternoon and evening from Da Nang can be enough if you want atmosphere without changing hotels. It becomes weak when you try to combine too many sights, meals, shopping, tailoring, and photos into one rushed visit.</p></details>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Use this guide before choosing the hotel base. Once Hoi An's route job is clear, move into the attraction guide, Da Nang comparison, Hue comparison, season, transport, and itinerary pages.</p>
<div class="vg-related-routes vg-hoi-an-ancient-town-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a><span class="vg-related-route-note">Choose sights after the old-town role is clear.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a><span class="vg-related-route-note">Choose the central Vietnam sleep base by route job.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/compare/hoi-an-vs-hue/">Hoi An vs Hue</a><span class="vg-related-route-note">Decide atmosphere versus imperial history when time is tight.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a><span class="vg-related-route-note">Place Hoi An in the broader heritage route.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a><span class="vg-related-route-note">Use Da Nang when airport, beach, and logistics matter more.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Check central Vietnam weather before locking the stay.</span></li>
<li><span class="vg-related-route-step">07</span><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a><span class="vg-related-route-note">Test whether Hoi An gets enough nights in a first route.</span></li>
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
    'vg-hoi-an-ancient-town-hero:v1',
    'vg-hoi-an-ancient-town-concierge-verdict:v1',
    'vg-hoi-an-ancient-town-stay-visit-skip:v1',
    'vg-hoi-an-ancient-town-decision-matrix:v1',
    'vg-hoi-an-ancient-town-gsc-demand:v1',
    'vg-hoi-an-ancient-town-photo-proof:v1',
    'vg-hoi-an-ancient-town-source-diversity:v1',
    'vg-hoi-an-ancient-town-day-vs-evening:v1',
    'vg-hoi-an-ancient-town-overnight-vs-day-trip:v1',
    'vg-hoi-an-ancient-town-ticket-live-check:v1',
    'vg-hoi-an-ancient-town-heat-rain-crowd:v1',
    'vg-hoi-an-ancient-town-route-fit:v1',
    'vg-hoi-an-ancient-town-food-tailoring-beach:v1',
    'vg-hoi-an-ancient-town-trip-length:v1',
    'vg-hoi-an-ancient-town-skip-logic:v1',
    'vg-hoi-an-ancient-town-live-checks:v1',
    'vg-hoi-an-ancient-town-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_hoi_an_ancient_town_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Hoi An Ancient Town Guide: When to Stay, Visit or Skip',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Decide whether to stay overnight in Hoi An Ancient Town, visit from Da Nang, go by day or evening, or skip when central Vietnam needs a cleaner route.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_hoi_an_ancient_town_post_fail('Could not update Hoi An Ancient Town post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Hoi An Ancient Town Guide: When to Stay, Visit or Skip');
update_post_meta($post_id, 'rank_math_description', 'Decide whether to stay overnight in Hoi An, visit from Da Nang, go by day or evening, or skip when central Vietnam needs a cleaner route.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Hoi An Ancient Town guide');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Decide whether Hoi An Ancient Town should be an overnight base, a focused visit from Da Nang, a day/evening stop, or a clean skip based on central Vietnam route value.');
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
    vg_hoi_an_ancient_town_post_fail('Could not assign Hoi An Ancient Town categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_hoi_an_ancient_town_post_fail('Could not assign Hoi An Ancient Town tags: ' . $tag_result->get_error_message());
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
        vg_hoi_an_ancient_town_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Hoi An Ancient Town post to complete draft: {$post_id}");

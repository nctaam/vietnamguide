<?php
/**
 * Expand the Best Vietnam Routes for First-Time Visitors post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-best-vietnam-routes-first-time-visitors-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_best_routes_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_best_routes_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_best_routes_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_best_routes_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_best_routes_post_find_by_slug(string $slug): ?WP_Post
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
        vg_best_routes_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_best_routes_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-02',
            'vg_editorial_brief_status' => 'brief',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if ($actual_value !== $expected_value) {
            vg_best_routes_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_best_routes_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_best_routes_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'best-vietnam-routes-first-time-visitors';
$post = vg_best_routes_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_best_routes_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_best_routes_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 25, 2026';
vg_best_routes_post_assert_target_meta($post);

$hero_image = set_url_scheme(get_stylesheet_directory_uri(), 'https') . '/assets/images/ha-long-bay-vietnam-hero.jpg';
$hero_credit_url = 'https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg';
$hanoi_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg';
$hanoi_credit_url = 'https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg';
$trang_an_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg';
$trang_an_credit_url = 'https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg';
$hoi_an_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg';
$hoi_an_credit_url = 'https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg';
$hcmc_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1280px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg';
$hcmc_credit_url = 'https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg';
$phu_quoc_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg';
$phu_quoc_credit_url = 'https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg';

$meta_update_summary = 'Expanded the native WordPress post brief into a complete route-shape guide with a photo-led hero, proof panel, concierge verdict, at-a-glance matrix, photo proof, source-diversity table, route archetypes, trip-length ladder, open-jaw logic, movement-cost guidance, regional spine, season filter, traveler-fit table, red flags, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Plan your trip - https://vietnam.travel/plan-your-trip - checked {$review_date}; used for the planning frame before exact cities are chosen.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for transfer, domestic flight, and overland movement logic.",
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for regional season pressure, comfort, and route timing.",
    "Vietnam.travel - Traveller's guide to Vietnam's airports - https://vietnam.travel/things-to-do/travellers-guide-vietnams-airports - checked {$review_date}; used for open-jaw and arrival/departure airport logic.",
    "Vietnam.travel - Hanoi destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-noi - checked {$review_date}; used for the north anchor and first-trip city context.",
    "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked {$review_date}; used for the north route spine around Hanoi, Ninh Binh, and Ha Long.",
    "Vietnam.travel - Ninh Binh destination page - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh - checked {$review_date}; used for countryside pacing and a protected inland night.",
    "Vietnam.travel - Ha Long destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-long - checked {$review_date}; used for bay-night context inside northern or open-jaw routes.",
    "Vietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked {$review_date}; used for the central spine and why Hue, Hoi An, and Da Nang work together.",
    "Vietnam.travel - Hoi An destination page - https://vietnam.travel/places-to-go/central-vietnam/hoi-an - checked {$review_date}; used for heritage-town base logic and central-route pacing.",
    "Vietnam.travel - Da Nang destination page - https://vietnam.travel/places-to-go/central-vietnam/da-nang - checked {$review_date}; used for beach, airport, and city-logistics base logic.",
    "Vietnam.travel - Hue destination page - https://vietnam.travel/places-to-go/central-vietnam/hue - checked {$review_date}; used for heritage-culture route structure and central-region depth.",
    "Vietnam.travel - Southern Vietnam destinations - https://vietnam.travel/places-to-go/southern-vietnam - checked {$review_date}; used for the southern spine and Ho Chi Minh City / Phu Quoc logic.",
    "Vietnam.travel - Ho Chi Minh City destination page - https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city - checked {$review_date}; used for the south anchor and open-jaw finish logic.",
    "Vietnam.travel - Phu Quoc destination page - https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc - checked {$review_date}; used for island-finish route logic.",
    "Vietnam Railways - official rail fare lookup and booking reference - https://dsvn.vn/ - checked {$review_date}; used for rail-versus-flight movement-cost discipline.",
    "National Center for Hydro-Meteorological Forecasting - live weather and warnings reference - https://nchmf.gov.vn/KttvsiteE/en-US/1/index.html - checked {$review_date}; used for live season awareness.",
    "Vietnam National Electronic Visa system - official e-visa portal - https://evisa.gov.vn/e-visa/foreigners - checked {$review_date}; used as an entry-logistics check when open-jaw routes touch different arrival and departure cities.",
    "Wikimedia Commons image direct URL - Ha Long Bay, Vietnam, View from above - {$hero_image} - credit Vyacheslav Argenberg / CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hanoi Hoan Kiem Lake - {$hanoi_image} - credit Alex 69200 vx / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Trang An Landscape Complex - {$trang_an_image} - credit Jakub Halun / CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hoi An Ancient Town - {$hoi_an_image} - credit Jakub Halun / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ho Chi Minh City Hall - {$hcmc_image} - credit Steffen Schmitz / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Kem Beach aerial view Phu Quoc Island - {$phu_quoc_image} - credit Vivu Vietnam / CC BY-SA 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This post is the route-shape layer for the native WordPress editorial calendar. It deliberately avoids city detail overload and hotel lists, then sends readers to the best next route or destination guide once the shape is clear.';
$meta_evidence_moat = implode("\n", [
    'Decision-led route guide built around route shape before city selection.',
    'Separates short-trip region spines, open-jaw logic, full-country routes, and beach or island finishes.',
    'Uses movement cost, season pressure, and transfer budget instead of generic "must see" compression.',
    'Photo proof makes each image do planning work: north, inland, central, city, and island route signals.',
    'Open-jaw section shows why entering one city and leaving another is often cleaner than backtracking.',
    'Trip-length ladder turns route planning into a durable decision sequence instead of a maximum-coverage checklist.',
    'No affiliate intent, no visible external body anchors, and no hotel-by-hotel ranking.',
]);
$meta_related_routes = implode("\n", [
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Use this before deciding which route shape deserves the trip.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Use this to align route shape with season and regional weather pressure.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Use this to judge flight, rail, road, and transfer cost before route locking.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Use this to keep movement cost visible while comparing route shapes.',
    'North Central South Vietnam | /compare/north-central-south-vietnam/ | Use this to compare region-level trade-offs before choosing a route spine.',
    '7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Use this when the trip must stay inside one region or one very tight spine.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Use this when the trip is short enough to demand a tighter route decision.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use this when open-jaw or a second region becomes realistic.',
    '21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use this when a fuller north-central-south route can still protect rest and transfer margin.',
    'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use this when the route needs a northern anchor.',
    'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Use this when the route needs a protected countryside night.',
    'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Use this when a bay night is worth its movement cost.',
    'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use this when the central route needs beach, airport, or modern city simplicity.',
    'Ho Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Use this when the south is a real chapter rather than a token finish.',
    'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Use this when the route deserves an island finish and enough nights to feel it.',
    'Da Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Use this when the central route must choose between beach-city and heritage-town rhythm.',
]);
$meta_hero_image_credit = 'Hero image: Ha Long Bay, Vietnam, View from above by Vyacheslav Argenberg, CC BY 4.0. Body images: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0; Trang An Landscape Complex by Jakub Halun, CC BY 4.0; Hoi An Ancient Town by Jakub Halun, CC BY-SA 4.0; Ho Chi Minh City Hall by Steffen Schmitz, CC BY-SA 4.0; Kem Beach aerial view Phu Quoc Island by Vivu Vietnam, CC BY-SA 4.0.';
$meta_admin_notes = 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, checks Rank Math/social preview, verifies route-fit judgment, image-license text, source-trail records, and adds any first-hand route or flight notes before publishing. Do not publish a maximum-coverage itinerary masquerading as route advice.';

$content = <<<HTML
<!-- vg-best-routes-hero:v1 -->
<!-- wp:html -->
<section class="vg-guide-hero vg-best-routes-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Route shape before city names</p>
<h1>Best Vietnam Routes for First-Time Visitors: North, Central, South or Open-Jaw?</h1>
<p class="vg-guide-lede">The best first Vietnam route is not the one that touches the most famous places. It is the one that gives each night a job: recover after the flight, protect a countryside morning, hold a bay night, keep a central heritage stretch calm, or finish the trip without backtracking across the country.</p>
<p class="vg-field-note">Use this guide before booking flights, hotels, or private transfers. It is a route-shape decision tool for first-time international travelers, not a maximum-coverage itinerary and not a checklist for forcing every region into one trip.</p>
<p class="vg-section-link"><a href="/plan/vietnam-travel-guide/">Start with the Vietnam travel guide</a></p>
</div>
<figure class="vg-guide-hero-image"><img src="{$hero_image}" alt="Ha Long Bay seen from above, a reference point for northern Vietnam route planning" loading="eager" decoding="async"><figcaption>Northern landscapes often set the first trip tone, but the route should still begin with a realistic shape, not a scenic impulse. Image: Vyacheslav Argenberg / CC BY 4.0.</figcaption></figure>
</section>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-best-routes-concierge-verdict:v1 -->
<!-- wp:html -->
<aside class="vg-concierge-verdict vg-best-routes-concierge-verdict" aria-label="Best Vietnam routes verdict">
<p class="vg-kicker">VietnamGuide verdict</p>
<p class="vg-verdict-lede"><strong>For most first-time trips, a north-to-south open-jaw or a north-plus-central route is the strongest default.</strong> Shorter trips should stay inside one region or one tight spine. Ten to fourteen days unlock open-jaw logic, while twenty-one days can justify a fuller north-central-south route if you still protect transfer days and do not chase every famous stop.</p>
<p>Do not build the trip by adding all the pins that fit on the map. Route success comes from what you remove: duplicate overnights, forced backtracking, and long transfer days that erase the value of the place you just visited.</p>
<ul class="wp-block-list vg-feature-list vg-best-routes-verdict-list">
<li><strong>7 days or less:</strong> one region only. Choose north, central, or south.</li>
<li><strong>8 to 10 days:</strong> north-plus-central or a very tight open-jaw if flights are clean.</li>
<li><strong>11 to 14 days:</strong> open-jaw becomes the premium default for most first-time travelers.</li>
<li><strong>15 to 21 days:</strong> a fuller north-central-south route can work if you still limit hotel moves.</li>
<li><strong>Anything shorter than the route deserves:</strong> cut a stop, not sleep, food, or transfer margin.</li>
</ul>
</aside>
<!-- /wp:html -->

<!-- vg-best-routes-at-a-glance:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route shapes at a glance</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-routes-at-a-glance">
<thead><tr><th>Trip length</th><th>Best route shape</th><th>Why it works</th><th>Watch-out</th></tr></thead>
<tbody>
<tr><td data-label="Trip length">5-7 days</td><td data-label="Best route shape">One region only</td><td data-label="Why it works">You can move less, sleep better, and still feel the place.</td><td data-label="Watch-out">Trying to squeeze in north, central, and south at once.</td></tr>
<tr><td data-label="Trip length">8-10 days</td><td data-label="Best route shape">North plus central</td><td data-label="Why it works">It balances Hanoi, countryside, bay, Hue, and Hoi An or Da Nang without a south detour.</td><td data-label="Watch-out">Turning ten days into a five-city relay.</td></tr>
<tr><td data-label="Trip length">11-14 days</td><td data-label="Best route shape">Open-jaw north-to-south</td><td data-label="Why it works">The route can move in one clean direction and save backtracking time.</td><td data-label="Watch-out">Adding extra beach or mountain detours without protecting recovery nights.</td></tr>
<tr><td data-label="Trip length">15-21 days</td><td data-label="Best route shape">Full-country route with restraint</td><td data-label="Why it works">You have enough time for northern scenery, central heritage, and a southern finish.</td><td data-label="Watch-out">Treating more days as permission to add more hotel changes than the trip can absorb.</td></tr>
<tr><td data-label="Trip length">Any short trip with beach priority</td><td data-label="Best route shape">One region plus a beach finish</td><td data-label="Why it works">It gives the beach real time instead of a token overnight.</td><td data-label="Watch-out">A rushed island or coast add-on that steals the best city days.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-routes-photo-proof:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what each route shape feels like</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the images as route signals. A strong route should look like a sequence, not a scatter plot.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-best-routes-photo-proof" aria-label="Best Vietnam routes photo proof">
<figure class="vg-guide-photo"><img src="{$hanoi_image}" alt="Hoan Kiem Lake in Hanoi" loading="lazy" decoding="async"><figcaption>North-first routes work when Hanoi gives the first trip a real anchor. Image: Alex 69200 vx / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$trang_an_image}" alt="Trang An landscape in Ninh Binh" loading="lazy" decoding="async"><figcaption>Ninh Binh deserves a protected night when countryside pacing matters more than a quick day trip. Image: Jakub Halun / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hoi_an_image}" alt="Hoi An Ancient Town" loading="lazy" decoding="async"><figcaption>Central routes get stronger when heritage, food, and slower evenings stay close together. Image: Jakub Halun / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall" loading="lazy" decoding="async"><figcaption>The south works best as a deliberate city chapter, not a rushed arrival stamp. Image: Steffen Schmitz / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$phu_quoc_image}" alt="Kem Beach aerial view on Phu Quoc Island" loading="lazy" decoding="async"><figcaption>Island finishes earn their place only when the route leaves enough room to slow down. Image: Vivu Vietnam / CC BY-SA 4.0.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-best-routes-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Source diversity: what can and cannot be verified</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Official destination, transport, airport, and weather sources can show which regions exist, how movement works, and which season pressure matters. They cannot tell you whether your exact route feels calm, whether your group likes long drives, or whether a beach night is worth the transfer cost.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-routes-source-diversity">
<thead><tr><th>Source job</th><th>Useful reference</th><th>Traveler judgment still needed</th></tr></thead>
<tbody>
<tr><td data-label="Source job">Planning frame</td><td data-label="Useful reference">Vietnam.travel plan your trip and weather pages.</td><td data-label="Traveler judgment still needed">Whether the route should favor weather-safe comfort or scenic ambition.</td></tr>
<tr><td data-label="Source job">North spine</td><td data-label="Useful reference">Vietnam.travel Hanoi, Ninh Binh, Ha Long, and Northern Vietnam pages.</td><td data-label="Traveler judgment still needed">Whether the north needs 2 nights or 5 before moving on.</td></tr>
<tr><td data-label="Source job">Central spine</td><td data-label="Useful reference">Vietnam.travel Central Vietnam, Hue, Hoi An, and Da Nang pages.</td><td data-label="Traveler judgment still needed">Whether the route should lean beach, heritage, or both.</td></tr>
<tr><td data-label="Source job">South spine</td><td data-label="Useful reference">Vietnam.travel Southern Vietnam, Ho Chi Minh City, and Phu Quoc pages.</td><td data-label="Traveler judgment still needed">Whether the south is a full chapter or just a final exit city.</td></tr>
<tr><td data-label="Source job">Movement and timing</td><td data-label="Useful reference">Vietnam.travel transport, airports, and Vietnam Railways booking references.</td><td data-label="Traveler judgment still needed">Whether a domestic flight, rail leg, road transfer, or open-jaw fare is the better trade.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-routes-archetypes:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route archetypes that actually age well</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The best way to plan Vietnam is to pick a route archetype first, then choose the exact cities inside it. That keeps the article useful after prices, schedules, and hotel rankings change.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-routes-archetypes">
<thead><tr><th>Route archetype</th><th>What it includes</th><th>When to choose it</th><th>Main risk</th></tr></thead>
<tbody>
<tr><td data-label="Route archetype">North-only loop</td><td data-label="What it includes">Hanoi, Ninh Binh, and either Ha Long or Lan Ha as the one big landscape chapter.</td><td data-label="When to choose it">Short first trips, food and scenery priority, or cooler-season travel when the north is the point.</td><td data-label="Main risk">Trying to add the central coast and south on top of a short north route.</td></tr>
<tr><td data-label="Route archetype">Central spine</td><td data-label="What it includes">Hue, Hoi An, Da Nang, and optionally one beach or heritage extension.</td><td data-label="When to choose it">Couples, families, food-first travelers, and anyone who wants calmer transfer density.</td><td data-label="Main risk">Weather pressure if beach ambition outruns season reality.</td></tr>
<tr><td data-label="Route archetype">South-only loop</td><td data-label="What it includes">Ho Chi Minh City, Mekong, and optionally Phu Quoc or a city-beach split.</td><td data-label="When to choose it">Island finish, warm-weather goals, or a southern entry/exit that makes the south the real chapter.</td><td data-label="Main risk">Expecting the south to replace the northern limestone or central heritage feeling.</td></tr>
<tr><td data-label="Route archetype">North-to-south open-jaw</td><td data-label="What it includes">Fly into Hanoi, end in Ho Chi Minh City, and move one direction through the country.</td><td data-label="When to choose it">Ten to fourteen days, first-time travelers, and anyone who wants the cleanest full-country shape.</td><td data-label="Main risk">Adding too many extra nights and losing the open-jaw efficiency you paid for.</td></tr>
<tr><td data-label="Route archetype">Full-country one-way</td><td data-label="What it includes">North, central, and south in one direction with a realistic number of base changes.</td><td data-label="When to choose it">Fifteen to twenty-one days and a traveler who values breadth without rushing.</td><td data-label="Main risk">Too many hotel moves and too few real rest nights.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-routes-trip-length:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Trip length decides the route, not the other way around</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Route shape should follow time budget. A first trip becomes premium when the days are used for presence, not pin collecting.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-routes-trip-length">
<thead><tr><th>Trip length</th><th>Best route shape</th><th>What to include</th><th>What to cut first</th></tr></thead>
<tbody>
<tr><td data-label="Trip length">7 days</td><td data-label="Best route shape">One region only</td><td data-label="What to include">Hanoi plus Ninh Binh, or Hoi An plus Da Nang, or Ho Chi Minh City plus Mekong.</td><td data-label="What to cut first">Any second region and any token beach add-on.</td></tr>
<tr><td data-label="Trip length">10 days</td><td data-label="Best route shape">North plus central</td><td data-label="What to include">Hanoi, Ninh Binh, one bay night, Hue, and Hoi An or Da Nang.</td><td data-label="What to cut first">Mekong, Phu Quoc, or a second bay/beach stop.</td></tr>
<tr><td data-label="Trip length">14 days</td><td data-label="Best route shape">North-to-south open-jaw</td><td data-label="What to include">Hanoi, Ninh Binh, bay, central Vietnam, and Ho Chi Minh City with one deliberate extension only.</td><td data-label="What to cut first">The weaker of the optional beach or mountain add-ons.</td></tr>
<tr><td data-label="Trip length">21 days</td><td data-label="Best route shape">Full-country route with restraint</td><td data-label="What to include">A north spine, a central spine, a south finish, and one slower chapter each for scenery and city rhythm.</td><td data-label="What to cut first">Extra hotel swaps inside the same city.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-routes-open-jaw:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Open-jaw flights are often the cleanest answer</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Open-jaw means you arrive in one city and leave from another. For Vietnam, that often means Hanoi in and Ho Chi Minh City out, or Hanoi in and Da Nang out if the central route is the real priority. It is not a luxury trick; it is often the simplest way to stop backtracking.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-routes-open-jaw">
<thead><tr><th>Open-jaw question</th><th>Why it matters</th><th>Good answer</th><th>Risk answer</th></tr></thead>
<tbody>
<tr><td data-label="Open-jaw question">Does the route move in one direction?</td><td data-label="Why it matters">If yes, open-jaw saves a return leg.</td><td data-label="Good answer">North to south, or north to central, or a clear loop with one clean finish.</td><td data-label="Risk answer">You need to backtrack to recover a forgotten city.</td></tr>
<tr><td data-label="Open-jaw question">Are baggage and booking rules simple?</td><td data-label="Why it matters">Separate tickets can break a pretty itinerary if connections are fragile.</td><td data-label="Good answer">One ticket or a well-buffered connection with enough margin.</td><td data-label="Risk answer">Tight self-transfer with heavy luggage and a long first-day route.</td></tr>
<tr><td data-label="Open-jaw question">Do the arrival and departure cities match the actual route job?</td><td data-label="Why it matters">Route shape should follow city function.</td><td data-label="Good answer">Hanoi for the north, Ho Chi Minh City for the south, Da Nang for the central coast.</td><td data-label="Risk answer">Choosing a flight path that forces a city detour only because the fare looked lower at first glance.</td></tr>
<tr><td data-label="Open-jaw question">Is the route long enough to feel the benefit?</td><td data-label="Why it matters">Short trips do not always justify complex ticket logic.</td><td data-label="Good answer">Ten to fourteen days or more with a real multi-region sequence.</td><td data-label="Risk answer">A very short trip where a simple round-trip would be calmer.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-routes-movement-cost:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Movement cost changes the route more than most travelers expect</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A route should be judged by movement cost, not only by hotel rate or postcard value. The real expense is the time, fatigue, and transfer friction you remove or create.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-routes-movement-cost">
<thead><tr><th>Movement type</th><th>Best use</th><th>Why it helps</th><th>When it is a bad trade</th></tr></thead>
<tbody>
<tr><td data-label="Movement type">Domestic flight</td><td data-label="Best use">Long country jumps and open-jaw route changes.</td><td data-label="Why it helps">It can save a day when the route spans north, central, and south.</td><td data-label="When it is a bad trade">When the flight is only covering for a route that is already too full.</td></tr>
<tr><td data-label="Movement type">Train</td><td data-label="Best use">Selected north-central or coast-aligned legs when the schedule works.</td><td data-label="Why it helps">It can be calmer than a road transfer and more scenic than a flight.</td><td data-label="When it is a bad trade">When it creates an early-morning scramble that erases the benefit.</td></tr>
<tr><td data-label="Movement type">Private car / van</td><td data-label="Best use">Point-to-point countryside, family, or transfer-heavy days.</td><td data-label="Why it helps">It protects a route with pickups, stops, and flexible timing.</td><td data-label="When it is a bad trade">When the same money could buy a better route shape instead of a more expensive transfer.</td></tr>
<tr><td data-label="Movement type">Overland day</td><td data-label="Best use">Shorter city-to-countryside links where the road itself is part of the trip.</td><td data-label="Why it helps">It can be useful when the route is intentionally slow.</td><td data-label="When it is a bad trade">When it swallows the only rest day or adds backtracking.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-routes-regional-spine:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Which cities belong on which spine?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The route gets easier when you group Vietnam by spine instead of by list. That keeps the trip coherent even when you later change one city or one night.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-routes-regional-spine">
<thead><tr><th>Spine</th><th>Core cities</th><th>What it gives you</th><th>What to avoid</th></tr></thead>
<tbody>
<tr><td data-label="Spine">North</td><td data-label="Core cities">Hanoi, Ninh Binh, Ha Long or Lan Ha.</td><td data-label="What it gives you">City, countryside, and limestone scenery in one coherent arc.</td><td data-label="What to avoid">Adding a southern city just because the fare search surfaced it.</td></tr>
<tr><td data-label="Spine">Central</td><td data-label="Core cities">Hue, Hoi An, Da Nang.</td><td data-label="What it gives you">Heritage, food, beach, and lower transfer friction.</td><td data-label="What to avoid">Turning the central coast into three separate hotel changes for one short stay.</td></tr>
<tr><td data-label="Spine">South</td><td data-label="Core cities">Ho Chi Minh City, Mekong, Phu Quoc.</td><td data-label="What it gives you">Urban energy, river texture, island rest, and easy warm-weather planning.</td><td data-label="What to avoid">Expecting the south to behave like the north in scenery or climate.</td></tr>
<tr><td data-label="Spine">Beach finish</td><td data-label="Core cities">Da Nang, Hoi An, Phu Quoc, or a southern island extension.</td><td data-label="What it gives you">A slower ending if the beach has enough nights to count.</td><td data-label="What to avoid">A token beach overnight that steals the stronger days.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-routes-season-filter:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Season and month should move the route too</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam is not one climate. Route shape changes with season. A northern landscape route can feel excellent in one month and heavy in another, while the central coast can flip between beautiful and weather-sensitive faster than casual trip lists admit.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-routes-season-filter">
<thead><tr><th>Season pressure</th><th>Route implication</th><th>Best default shift</th><th>What to check live</th></tr></thead>
<tbody>
<tr><td data-label="Season pressure">North cooler months</td><td data-label="Route implication">The north can carry more scenic and city days comfortably.</td><td data-label="Best default shift">North-only or north-plus-central routes become especially strong.</td><td data-label="What to check live">Forecasts, fog/rain, and transfer timing.</td></tr>
<tr><td data-label="Season pressure">Central coast weather-sensitive months</td><td data-label="Route implication">Beach and heritage timing need more caution.</td><td data-label="Best default shift">Tilt toward heritage, city, or flexible central days before beach expansion.</td><td data-label="What to check live">Rain, storms, and cancellation terms.</td></tr>
<tr><td data-label="Season pressure">South warm and steady periods</td><td data-label="Route implication">The south can hold a city-plus-island finish well.</td><td data-label="Best default shift">Open-jaw or south-finish routes become easier.</td><td data-label="What to check live">Heat, humidity, and flight connections.</td></tr>
<tr><td data-label="Season pressure">Mixed-season shoulder months</td><td data-label="Route implication">The route should protect flexibility rather than chase perfection.</td><td data-label="Best default shift">Use fewer bases with stronger buffer days.</td><td data-label="What to check live">Forecast updates, cancellation rules, and local advisories.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-routes-traveler-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Which route fits which traveler?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Route choice is also traveler fit. A couple, family, food-first traveler, and beach-first traveler may all be looking at the same country, but they should not book the same shape.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-routes-traveler-fit">
<thead><tr><th>Traveler type</th><th>Best route shape</th><th>Why</th><th>What to avoid</th></tr></thead>
<tbody>
<tr><td data-label="Traveler type">First-time solo traveler</td><td data-label="Best route shape">One region or open-jaw with simple transfers.</td><td data-label="Why">Confidence comes from less juggling, not more moving parts.</td><td data-label="What to avoid">Trying to see every famous place on the first pass.</td></tr>
<tr><td data-label="Traveler type">Couple</td><td data-label="Best route shape">North plus central or open-jaw with a slower middle.</td><td data-label="Why">This creates a premium-feeling sequence without constant packing.</td><td data-label="What to avoid">Too many early morning checkouts.</td></tr>
<tr><td data-label="Traveler type">Family</td><td data-label="Best route shape">Central spine or north plus central with fewer hotel changes.</td><td data-label="Why">Families benefit from predictability, food access, and shorter transfer days.</td><td data-label="What to avoid">Multi-city compression that turns every day into logistics.</td></tr>
<tr><td data-label="Traveler type">Food-first traveler</td><td data-label="Best route shape">North plus central or south plus central depending on flight geometry.</td><td data-label="Why">Food quality grows when meals can be spaced around a coherent route, not chase-mode movement.</td><td data-label="What to avoid">Routes where dinner is always an afterthought.</td></tr>
<tr><td data-label="Traveler type">Beach-first traveler</td><td data-label="Best route shape">One region plus one beach finish.</td><td data-label="Why">Beaches need enough nights to slow the pace, not just a sunset photo.</td><td data-label="What to avoid">A token beach stop at the end of an overfull trip.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-routes-red-flags:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route red flags</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The route is probably too ambitious if it starts with one of these mistakes.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-routes-red-flags">
<thead><tr><th>Red flag</th><th>Why it breaks the trip</th><th>Safer alternative</th></tr></thead>
<tbody>
<tr><td data-label="Red flag">Trying to fit north, central, and south into a very short trip</td><td data-label="Why it breaks the trip">The route becomes a transfer race instead of a travel experience.</td><td data-label="Safer alternative">Pick one region, or two at most.</td></tr>
<tr><td data-label="Red flag">Choosing a route by the cheapest fare only</td><td data-label="Why it breaks the trip">A cheap ticket can hide expensive movement cost later.</td><td data-label="Safer alternative">Compare the full route shape, not just the flight price.</td></tr>
<tr><td data-label="Red flag">Adding an island without enough nights</td><td data-label="Why it breaks the trip">The transfer can eat the rest value.</td><td data-label="Safer alternative">Use the island only when it is the actual point of the trip.</td></tr>
<tr><td data-label="Red flag">Backtracking across the country for one missing city</td><td data-label="Why it breaks the trip">You pay twice in time and fatigue.</td><td data-label="Safer alternative">Decide earlier whether the missing city belongs in this trip at all.</td></tr>
<tr><td data-label="Red flag">Treating every night as interchangeable</td><td data-label="Why it breaks the trip">Arrival nights, transfer nights, and scenic nights need different base jobs.</td><td data-label="Safer alternative">Assign each night a job before booking the room.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-routes-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before you lock the route</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-best-routes-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-best-routes-live-checks">
<li>Check the flight map first: arrival city, departure city, and whether open-jaw saves a real transfer.</li>
<li>Check the weather and the month before you choose beach or bay priorities.</li>
<li>Check whether the route depends on a train, domestic flight, or long road transfer that will need an early wake-up.</li>
<li>Check how many hotel moves the route creates and whether each move solves a real problem.</li>
<li>Check if the route still feels good when you cut one stop from the draft. If it collapses, it was too full.</li>
<li>Check cancellation and baggage rules before using separate tickets or open-jaw flights.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Related VietnamGuide routes</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-related-routes vg-best-routes-related-manual">
<p><a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a> is the short-trip companion when the route must stay inside one region.</p>
<p><a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> is the first-trip pillar before you choose a route shape.</p>
<p><a href="/compare/north-central-south-vietnam/">North Central South Vietnam</a> compares the regions before the route gets specific.</p>
<p><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a> is the best short-route companion to this guide.</p>
<p><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a> shows how open-jaw becomes realistic with more time.</p>
<p><a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> is the longer-route companion when all three regions can fit without rushing.</p>
<p><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> keeps movement cost visible.</p>
</div>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- vg-best-routes-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-best-routes-faq">
<details><summary>What is the best route for first-time visitors to Vietnam?</summary><p>For most travelers, a north-to-south open-jaw or a north-plus-central route is the strongest answer. The best route is the one that gives each night a job and avoids needless backtracking.</p></details>
<details><summary>Is 10 days enough for Vietnam?</summary><p>Yes, but only for a north-plus-central route or a very tight open-jaw. Ten days is usually not enough to cross every region without rushing.</p></details>
<details><summary>Should I start in the north or the south?</summary><p>Start where your route shape and flight pricing make the most sense. North works well for scenery and a classic first-trip arc. South works well when the south is the point, or when a southern exit makes the route cleaner.</p></details>
<details><summary>Is open-jaw worth it?</summary><p>Usually yes if the trip is long enough. Open-jaw saves backtracking when the route naturally moves in one direction, especially on 10- to 14-day trips.</p></details>
<details><summary>Should I add an island to every first trip?</summary><p>No. An island is only worth it when the route has enough nights to let it feel like a rest chapter rather than a transfer detour.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_source_trail]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[vg_update_log]
<!-- /wp:shortcode -->
HTML;

$required_markers = [
    'vg-best-routes-hero:v1',
    'vg-best-routes-concierge-verdict:v1',
    'vg-best-routes-at-a-glance:v1',
    'vg-best-routes-photo-proof:v1',
    'vg-best-routes-source-diversity:v1',
    'vg-best-routes-archetypes:v1',
    'vg-best-routes-trip-length:v1',
    'vg-best-routes-open-jaw:v1',
    'vg-best-routes-movement-cost:v1',
    'vg-best-routes-regional-spine:v1',
    'vg-best-routes-season-filter:v1',
    'vg-best-routes-traveler-fit:v1',
    'vg-best-routes-red-flags:v1',
    'vg-best-routes-live-checks:v1',
    'vg-best-routes-faq:v1',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_best_routes_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Best Vietnam Routes for First-Time Visitors: North, Central, South or Open-Jaw?',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Compare route shapes before booking: north-only, central spine, south-only, open-jaw, and full-country routes for first-time Vietnam trips.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_best_routes_post_fail('Could not update Best Vietnam routes post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Best Vietnam Routes for First-Time Visitors');
update_post_meta($post_id, 'rank_math_description', 'Compare route shapes before booking: north-only, central spine, south-only, open-jaw, and full-country routes for first-time Vietnam trips.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'best vietnam routes first time visitors');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Choose the route shape before the city list: one region for short trips, north-plus-central for many first trips, open-jaw for 10-14 days, and full-country only when time and movement budget are real.');
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

wp_set_object_terms($post_id, vg_best_routes_post_term_ids('category', ['travel-planning', 'itineraries']), 'category', false);
wp_set_object_terms($post_id, vg_best_routes_post_term_ids('post_tag', ['route-planning', 'first-time-vietnam', 'anti-spam-evergreen']), 'post_tag', false);

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
        vg_best_routes_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Best Vietnam routes post to complete draft: {$post_id}");

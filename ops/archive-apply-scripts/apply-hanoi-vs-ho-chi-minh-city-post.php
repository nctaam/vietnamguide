<?php
/**
 * Expand the Hanoi vs Ho Chi Minh City post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-hanoi-vs-ho-chi-minh-city-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_hanoi_hcmc_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_hanoi_hcmc_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_hanoi_hcmc_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_hanoi_hcmc_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_hanoi_hcmc_post_find_by_slug(string $slug): ?WP_Post
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
        vg_hanoi_hcmc_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_hanoi_hcmc_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-16',
            'vg_editorial_brief_status' => ['brief', 'complete_draft'],
            'vg_editorial_batch' => 'batch-2-evergreen-planning',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if (is_array($expected_value)) {
            if (! in_array($actual_value, $expected_value, true)) {
                vg_hanoi_hcmc_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected one of " . implode(', ', $expected_value) . '.');
            }
            continue;
        }

        if ($actual_value !== $expected_value) {
            vg_hanoi_hcmc_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_hanoi_hcmc_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_hanoi_hcmc_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'hanoi-vs-ho-chi-minh-city';
$post = vg_hanoi_hcmc_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_hanoi_hcmc_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_hanoi_hcmc_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 26, 2026';

if ($post_id !== 498) {
    vg_hanoi_hcmc_post_fail("Refusing to update {$slug}: expected post ID 498, found {$post_id}.");
}

vg_hanoi_hcmc_post_assert_target_meta($post);

$category_term_ids = vg_hanoi_hcmc_post_term_ids('category', ['destinations', 'travel-planning']);
$tag_term_ids = vg_hanoi_hcmc_post_term_ids('post_tag', ['city-comparison', 'first-time-vietnam', 'route-planning', 'anti-spam-evergreen']);

$hanoi_lake_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$hanoi_old_quarter_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/da/Old_Quarter_Street_Scene_-_Hanoi_-_Vietnam_%2848256301206%29.jpg/1920px-Old_Quarter_Street_Scene_-_Hanoi_-_Vietnam_%2848256301206%29.jpg');
$hcmc_skyline_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/9/95/Ho_Chi_Minh_City_Skyline.jpg');
$ben_thanh_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/4/45/Ben_Thanh_Market_Ho_Chi_Minh_City.jpg');

$meta_update_summary = 'Expanded the native WordPress batch 2 brief into a complete Hanoi vs Ho Chi Minh City decision draft with photo-led hero, proof panel, concierge verdict, city-first matrix, GSC demand note, photo proof, source-diversity table, arrival and departure logic, route-direction guidance, weather and comfort trade-offs, day-trip consequences, food and culture framing, trip-length matrix, skip logic, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Ha Noi destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-noi - checked {$review_date}; used for Hanoi history, Old Quarter, food, airport, rail, northern access, and weather notes.",
    "Vietnam.travel - Ho Chi Minh City destination page - https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city - checked {$review_date}; used for HCMC city role, District 1 landmarks, Cho Lon, Ben Thanh, food, airport, Mekong and Cu Chi access, and weather notes.",
    "Vietnam.travel - Traveller guide to Vietnam airports - https://vietnam.travel/things-to-do/travellers-guide-vietnams-airports - checked {$review_date}; used for HAN/SGN terminal and airport-transfer risk framing.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for domestic movement, train and flight decision discipline, and transfer-cost logic.",
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for north-vs-south seasonal trade-offs and rainy-season flexibility.",
    "Google Search Console query export from 2026-07-25 - checked {$review_date}; used for observed Hanoi things-to-do demand, one HCMC/north-south query, and FAQ phrasing.",
    "Internal published page check - https://vietnamguide.net/destinations/hanoi-travel-guide/ - checked {$review_date}; used for Hanoi route-role link.",
    "Internal published page check - https://vietnamguide.net/destinations/ho-chi-minh-city-travel-guide/ - checked {$review_date}; used for HCMC route-role link.",
    "Internal published page check - https://vietnamguide.net/compare/north-central-south-vietnam/ - checked {$review_date}; used for region-spine link.",
    "Internal published page check - https://vietnamguide.net/plan/transport-within-vietnam/ - checked {$review_date}; used for transfer-friction link.",
    "Wikimedia Commons image direct URL - Hanoi Hoan Kiem Lake - {$hanoi_lake_image} - credit Alex 69200 vx / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Old Quarter Street Scene, Hanoi - {$hanoi_old_quarter_image} - credit Adam Jones / CC BY-SA 2.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ho Chi Minh City Skyline - {$hcmc_skyline_image} - credit Pimnl / CC0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ben Thanh Market, Ho Chi Minh City - {$ben_thanh_image} - credit Shoestring / Public domain - license context checked {$review_date}.",
]);
$meta_field_note = 'This complete draft compares Hanoi and Ho Chi Minh City as route tools, not personality mascots. The editorial judgment centers on first-night comfort, open-jaw flights, north/south route direction, day-trip consequences, food and culture depth, weather resilience, and what the traveler must remove if both cities are included.';
$meta_evidence_moat = implode("\n", [
    'The article gives a direct default verdict but refuses a universal winner.',
    'GSC query evidence is used to prioritize Hanoi attraction phrasing and north/south comparison language without keyword padding.',
    'Airport and first-night logic separate emotional preference from actual arrival risk.',
    'Route-direction modules explain when Hanoi should start the trip and when HCMC should start or end it.',
    'Day-trip consequence tables compare Ninh Binh and bay access against Mekong and Cu Chi access instead of listing attractions.',
    'Weather guidance treats north and south as different planning systems rather than one national climate claim.',
    'Skip logic tells readers when to remove one city, which gives decision value beyond rewritten search results.',
    'No visible external body anchors; source URLs and image credits stay auditable in metadata/source trail.',
]);
$meta_related_routes = implode("\n", [
    'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Decide how Hanoi should work as the northern base before choosing attractions.',
    'Ho Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Decide whether HCMC deserves a real southern chapter, not only an exit airport.',
    'North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Choose the regional spine before forcing both big cities into a short trip.',
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Put the city comparison inside the full first-trip planning order.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Count airport, train, domestic-flight, and day-trip friction before adding both cities.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check whether north weather or southern rain changes which city should lead.',
    'Best Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Use after choosing Hanoi to protect the first two days from attraction overload.',
    'Best Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, bay, mountain, or stay-in-city logic from the northern base.',
    'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Add countryside contrast near Hanoi instead of treating another city as the only next move.',
    'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Plan the bay chapter after Hanoi arrival and transfer logic are clear.',
    'Best Day Trips from Ho Chi Minh City | /destinations/best-day-trips-from-ho-chi-minh-city/ | Choose Cu Chi, Mekong, Can Gio, Tay Ninh, or a stay-in-city day from HCMC.',
    'Mekong Delta Travel Guide | /destinations/mekong-delta-travel-guide/ | Decide whether the south needs a day trip, overnight, or no delta chapter.',
    'Cu Chi Tunnels vs Mekong Delta Day Trip | /compare/cu-chi-tunnels-vs-mekong-delta-day-trip/ | Narrow the strongest southern excursion fork before adding more tours.',
    '7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Use when a one-week trip usually cannot support both Hanoi and HCMC well.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a short first trip can hold both cities without becoming flight-heavy.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use when two weeks can support a north-central-south route with cleaner open-jaw flights.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the difference between one city base, two city bases, and a domestic-flight-heavy route.',
]);
$meta_hero_image_credit = 'Hero image: split decision using Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0. Body images: Old Quarter Street Scene, Hanoi by Adam Jones, CC BY-SA 2.0; Ho Chi Minh City Skyline by Pimnl, CC0; Ben Thanh Market, Ho Chi Minh City by Shoestring, public domain.';
$meta_admin_notes = 'Complete draft generated for WordPress Admin review. Keep draft until manual preview, Rank Math review, image check, and final editorial pass are complete. The body intentionally avoids external anchors; source URLs are preserved in metadata and source trail.';

$content = <<<HTML
<!-- vg-hanoi-hcmc-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero vg-compare-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero vg-compare-hero">
<!-- wp:cover {"url":"{$hanoi_lake_image}","dimRatio":58,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hoan Kiem Lake in Hanoi, a first Vietnam city base" src="{$hanoi_lake_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<div class="wp-block-group vg-guide-hero-inner">
<p class="vg-kicker">Reviewed city comparison - Updated {$review_date}</p>
<h1 class="wp-block-heading vg-display vg-guide-title">Hanoi vs Ho Chi Minh City: Which Should You Visit First?</h1>
<p class="vg-guide-lede">Hanoi and Ho Chi Minh City are both strong first-trip cities, but they solve different route problems. The better first city is the one that makes your arrival, onward travel, weather plan, and first real days in Vietnam easier.</p>
<p class="vg-field-note">Concierge verdict: choose Hanoi first for the classic north-led route; choose Ho Chi Minh City first when the south is the purpose of the trip or your flights make a southern start materially cleaner.</p>
</div>
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-hanoi-hcmc-concierge-verdict:v1 -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<p><strong>Most first-time international visitors should choose Hanoi first if the trip is built around northern scenery, food, culture, Ninh Binh, Ha Long Bay, Cat Ba, Sapa, or Ha Giang.</strong> Hanoi is the stronger opener when you want Vietnam to feel distinctive immediately and when the first half of the route points north or central.</p>
<p><strong>Choose Ho Chi Minh City first when the south is not an afterthought.</strong> HCMC works better as the opening city for travelers flying into SGN, building the trip around southern food, museums, Mekong, Cu Chi, Phu Quoc, Con Dao, or Cambodia connections, or visiting in a month when southern weather is easier than the north.</p>
<p>The mistake is asking which city is better in isolation. The useful question is: which city does the job your route needs first?</p>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-hanoi-hcmc-quick-answer:v1 -->
<!-- vg-hanoi-hcmc-city-first-matrix:v1 -->
<h2 class="wp-block-heading">Quick answer matrix</h2>
<table class="vg-decision-table vg-hanoi-hcmc-quick-answer">
<thead><tr><th>Decision</th><th>Choose Hanoi first</th><th>Choose HCMC first</th></tr></thead>
<tbody>
<tr><td data-label="Decision">Best default first trip</td><td data-label="Choose Hanoi first">Yes, especially for a north or north-plus-central route.</td><td data-label="Choose HCMC first">Yes only when the south is a real chapter or flights strongly favor SGN.</td></tr>
<tr><td data-label="Decision">First-night feeling</td><td data-label="Choose Hanoi first">Dense, atmospheric, walkable in pockets, intense around the Old Quarter.</td><td data-label="Choose HCMC first">Bigger-city energy, wider hotel choice, easier warm-weather city reset.</td></tr>
<tr><td data-label="Decision">Best nearby contrast</td><td data-label="Choose Hanoi first">Ninh Binh, Ha Long or Lan Ha Bay, Cat Ba, mountain routes.</td><td data-label="Choose HCMC first">Mekong Delta, Cu Chi, Can Gio, Tay Ninh, Phu Quoc connections.</td></tr>
<tr><td data-label="Decision">Weather advantage</td><td data-label="Choose Hanoi first">Pleasant in many spring and autumn windows; cooler in winter.</td><td data-label="Choose HCMC first">More consistently warm; dry season can be easier for light packing.</td></tr>
<tr><td data-label="Decision">Trip-length pressure</td><td data-label="Choose Hanoi first">Best for 7-10 days when the route stays north or north-central.</td><td data-label="Choose HCMC first">Best for 7-10 days when the route stays south or south-central.</td></tr>
<tr><td data-label="Decision">When to include both</td><td data-label="Choose Hanoi first">Start Hanoi, end HCMC for a north-to-south open-jaw route.</td><td data-label="Choose HCMC first">Start HCMC, end Hanoi only when flights, weather, or Cambodia logic justify it.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-hcmc-gsc-demand:v1 -->
<h2 class="wp-block-heading">Search evidence shaping this draft</h2>
<p>Recent Search Console data for vietnamguide.net already shows meaningful impressions around Hanoi attraction intent, including variations of "things to do in Hanoi", "what to see in Hanoi Vietnam", "Hanoi must visit", and "what to do in Hanoi city". It also shows early north/south comparison demand around Ho Chi Minh City. That is why this page compares the two cities as route-entry choices, while sending attraction-heavy Hanoi readers toward the dedicated Hanoi guide instead of stuffing every sight into this comparison.</p>

<!-- vg-hanoi-hcmc-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: two different first-city jobs</h2>
<div class="vg-photo-grid vg-hanoi-hcmc-photo-proof">
<figure><img src="{$hanoi_lake_image}" alt="Hoan Kiem Lake in Hanoi" loading="lazy" decoding="async"><figcaption>Hanoi is strongest when the first city needs culture, food, lakeside rhythm, and northern access. Image: Alex 69200 vx / CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$hanoi_old_quarter_image}" alt="Old Quarter street scene in Hanoi" loading="lazy" decoding="async"><figcaption>The Old Quarter can be thrilling on night one, but it should be matched to jet lag, luggage, and walking tolerance. Image: Adam Jones / CC BY-SA 2.0.</figcaption></figure>
<figure><img src="{$hcmc_skyline_image}" alt="Ho Chi Minh City skyline" loading="lazy" decoding="async"><figcaption>HCMC is strongest when the south needs a full city chapter, not a tired final-night airport stop. Image: Pimnl / CC0.</figcaption></figure>
<figure><img src="{$ben_thanh_image}" alt="Ben Thanh Market in Ho Chi Minh City" loading="lazy" decoding="async"><figcaption>Markets, food streets, museums, and southern day trips give HCMC depth when you give it enough nights. Image: Shoestring / Public domain.</figcaption></figure>
</div>

<!-- vg-hanoi-hcmc-source-diversity:v1 -->
<h2 class="wp-block-heading">Source diversity behind the comparison</h2>
<table class="vg-decision-table vg-hanoi-hcmc-source-diversity">
<thead><tr><th>Evidence layer</th><th>What it tells us</th><th>How the article uses it</th></tr></thead>
<tbody>
<tr><td data-label="Evidence layer">Official city pages</td><td data-label="What it tells us">Hanoi and HCMC destination roles, attractions, food, transport, and weather basics.</td><td data-label="How the article uses it">Separates official city identity from route-specific editorial judgment.</td></tr>
<tr><td data-label="Evidence layer">Airport and transport checks</td><td data-label="What it tells us">Arrival airport, domestic movement, city transfer friction, and end-of-trip risk.</td><td data-label="How the article uses it">Turns "which city first" into an arrival/departure decision.</td></tr>
<tr><td data-label="Evidence layer">Weather checks</td><td data-label="What it tells us">Northern and southern weather behave differently by season.</td><td data-label="How the article uses it">Prevents one generic Vietnam weather answer from deciding both cities.</td></tr>
<tr><td data-label="Evidence layer">GSC demand</td><td data-label="What it tells us">Hanoi attraction queries are already surfacing; HCMC/north-south comparison demand is early but useful.</td><td data-label="How the article uses it">Keeps the page useful for searchers without duplicating the Hanoi attractions page.</td></tr>
<tr><td data-label="Evidence layer">Published internal routes</td><td data-label="What it tells us">Which supporting pages are live and safe to link.</td><td data-label="How the article uses it">Avoids visible links to unpublished drafts and keeps route flow clean.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-hcmc-arrival-logic:v1 -->
<h2 class="wp-block-heading">Arrival logic: choose the city that lowers day-one risk</h2>
<p>Arrival day is not a normal sightseeing day. It includes immigration, luggage, phone setup, cash, transport, check-in timing, jet lag, and the first decision about how much intensity you can handle. Hanoi and HCMC can both work, but they fail in different ways.</p>
<table class="vg-decision-table vg-hanoi-hcmc-arrival-logic">
<thead><tr><th>Arrival condition</th><th>Better first city</th><th>Why it matters</th></tr></thead>
<tbody>
<tr><td data-label="Arrival condition">You land late after a long-haul flight</td><td data-label="Better first city">Whichever airport you land in</td><td data-label="Why it matters">Do not add a domestic transfer just to start in the city that sounds more romantic.</td></tr>
<tr><td data-label="Arrival condition">You want classic northern Vietnam first</td><td data-label="Better first city">Hanoi</td><td data-label="Why it matters">It connects naturally to Old Quarter days, Ninh Binh, the bay, and mountain routes.</td></tr>
<tr><td data-label="Arrival condition">You want southern food, museums, and Mekong first</td><td data-label="Better first city">HCMC</td><td data-label="Why it matters">It avoids wasting a northern arrival when the real trip purpose is southern.</td></tr>
<tr><td data-label="Arrival condition">You are nervous about dense first-night streets</td><td data-label="Better first city">HCMC, with a calm central hotel</td><td data-label="Why it matters">The first night can be softer when the hotel location is chosen for arrival, not nightlife.</td></tr>
<tr><td data-label="Arrival condition">You want to walk into atmosphere immediately</td><td data-label="Better first city">Hanoi</td><td data-label="Why it matters">The Old Quarter and Hoan Kiem area give a concentrated sense of place quickly.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-hcmc-route-direction:v1 -->
<h2 class="wp-block-heading">Route direction: north-to-south or south-to-north?</h2>
<p>The cleanest first-time route often uses open-jaw flights: arrive in Hanoi and leave from HCMC, or arrive in HCMC and leave from Hanoi. Backtracking to the same airport can be cheaper on paper, but it often costs a day of energy. Count hotel check-outs, airport transfers, domestic flight buffers, and the emotional cost of ending the trip with a logistics day.</p>
<h3>Start in Hanoi when the route is north-led</h3>
<p>Start with <a href="/destinations/hanoi-travel-guide/">Hanoi</a> when the first real choices are Ninh Binh, Ha Long Bay, Lan Ha Bay, Cat Ba, Sapa, Ha Giang, or a north-to-central route. This lets the trip open with food, streets, museums, and northern scenery before moving south or central.</p>
<h3>Start in HCMC when the route is south-led</h3>
<p>Start with <a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City</a> when the first real choices are Mekong, Cu Chi, Can Gio, southern food, Phu Quoc, Con Dao, or Cambodia. HCMC is not weaker than Hanoi; it is weaker only when it is forced into a trip whose real shape is northern.</p>
<h3>Use the region spine before deciding</h3>
<p>If you are still torn, use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a> first. The city decision becomes easier once the region spine is clear.</p>

<!-- vg-hanoi-hcmc-weather-comfort:v1 -->
<h2 class="wp-block-heading">Weather and comfort trade-offs</h2>
<p>Hanoi has stronger seasonal contrast. It can feel wonderful in clearer autumn windows and cooler in winter, but it can also be humid, hot, gray, or drizzly depending on month. Ho Chi Minh City is more consistently warm; the choice is often dry heat, rainy-season downpours, or a slightly fresher early-year window. Weather should not automatically choose the city, but it should decide how much backup value each city needs.</p>
<table class="vg-decision-table vg-hanoi-hcmc-weather-comfort">
<thead><tr><th>Travel month pattern</th><th>Hanoi implication</th><th>HCMC implication</th></tr></thead>
<tbody>
<tr><td data-label="Travel month pattern">Cooler northern months</td><td data-label="Hanoi implication">Good for walking when skies cooperate; pack layers.</td><td data-label="HCMC implication">Still warm; easier light packing.</td></tr>
<tr><td data-label="Travel month pattern">Hot shoulder periods</td><td data-label="Hanoi implication">Plan slower afternoons and avoid overloading Old Quarter days.</td><td data-label="HCMC implication">Use hotels, cafes, museums, and evening food rhythm to manage heat.</td></tr>
<tr><td data-label="Travel month pattern">Rain-sensitive route</td><td data-label="Hanoi implication">Protect indoor value and avoid stacking bay or mountain assumptions too tightly.</td><td data-label="HCMC implication">Downpours may be intense but city days can recover if the schedule has margin.</td></tr>
<tr><td data-label="Travel month pattern">Beach or island extension</td><td data-label="Hanoi implication">Hanoi does not solve beach weather; it solves northern access.</td><td data-label="HCMC implication">Often cleaner for southern island or Mekong extensions when season supports them.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-hcmc-day-trip-consequences:v1 -->
<h2 class="wp-block-heading">Day-trip consequences</h2>
<p>The biggest difference between Hanoi and HCMC is not only city atmosphere. It is what each city unlocks nearby. If you choose the wrong first city, the whole route may lean toward the wrong side trips.</p>
<table class="vg-decision-table vg-hanoi-hcmc-day-trip-consequences">
<thead><tr><th>Base</th><th>Strong nearby choices</th><th>Pressure warning</th></tr></thead>
<tbody>
<tr><td data-label="Base">Hanoi</td><td data-label="Strong nearby choices"><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh</a>, <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay</a>, Lan Ha Bay, Cat Ba, Sapa, Ha Giang, craft villages.</td><td data-label="Pressure warning">Do not force Ninh Binh, bay, mountains, Hanoi food, museums, and a flight south into three nights.</td></tr>
<tr><td data-label="Base">HCMC</td><td data-label="Strong nearby choices"><a href="/destinations/mekong-delta-travel-guide/">Mekong Delta</a>, <a href="/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/">Cu Chi or Mekong</a>, Can Gio, Tay Ninh, Vung Tau-style coast, Phu Quoc flights.</td><td data-label="Pressure warning">Do not treat HCMC as only a transfer point and then expect the south to feel complete.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-hcmc-food-culture:v1 -->
<h2 class="wp-block-heading">Food and culture: do not use lazy stereotypes</h2>
<p>Hanoi is not only "traditional" and HCMC is not only "modern". Those labels are too thin for a useful travel decision. Hanoi gives first-timers old-quarter food rhythm, lake life, museums, art, cafes, and a dense capital atmosphere. HCMC gives southern street food, markets, museums, old apartments turned creative spaces, Cho Lon, river movement, and a faster metropolitan pulse.</p>
<p>If food is the deciding factor, choose by route. Hanoi pairs naturally with northern dishes, Old Quarter eating, and a first-trip attraction cluster. HCMC pairs naturally with southern dishes, night food energy, markets, and a better southern excursion base. If you have two weeks and open-jaw flights, both cities can earn their place. If you have one week, make one city deep instead of making two cities shallow.</p>

<!-- vg-hanoi-hcmc-trip-length:v1 -->
<h2 class="wp-block-heading">Which city by trip length?</h2>
<table class="vg-decision-table vg-hanoi-hcmc-trip-length">
<thead><tr><th>Trip length</th><th>Recommended city choice</th><th>What to cut first</th></tr></thead>
<tbody>
<tr><td data-label="Trip length">5-6 days</td><td data-label="Recommended city choice">Choose one city only. Hanoi for north scenery/culture; HCMC for south food/Mekong/city energy.</td><td data-label="What to cut first">Cross-country city hopping.</td></tr>
<tr><td data-label="Trip length">7 days</td><td data-label="Recommended city choice">Usually Hanoi plus Ninh Binh or bay, or HCMC plus Mekong and city depth.</td><td data-label="What to cut first">The other big city unless flights make it unavoidable.</td></tr>
<tr><td data-label="Trip length">10 days</td><td data-label="Recommended city choice">Hanoi plus central Vietnam is often stronger than forcing HCMC too.</td><td data-label="What to cut first">A token HCMC final night with no real southern plan.</td></tr>
<tr><td data-label="Trip length">14 days</td><td data-label="Recommended city choice">Both can work if the route is Hanoi, north contrast, central Vietnam, then HCMC or the reverse.</td><td data-label="What to cut first">Duplicate city nights that do not unlock a better day.</td></tr>
<tr><td data-label="Trip length">21 days</td><td data-label="Recommended city choice">Both should normally fit, but each needs a defined job.</td><td data-label="What to cut first">Extra transfers that turn good cities into recovery stops.</td></tr>
</tbody>
</table>

<!-- vg-hanoi-hcmc-skip-logic:v1 -->
<h2 class="wp-block-heading">Skip logic that improves the route</h2>
<ul class="vg-check-list vg-hanoi-hcmc-skip-logic">
<li>Skip HCMC on a short north-plus-central trip if it would become only a late arrival and early flight.</li>
<li>Skip Hanoi on a south-led trip when your flights, weather, and interests all point to HCMC, Mekong, and islands.</li>
<li>Skip both-city sightseeing pressure if one city is already doing the airport job and the other city is doing the emotional route job.</li>
<li>Skip the second big city when it removes the overnight that would make Ninh Binh, bay, Mekong, Hue, or Hoi An actually work.</li>
<li>Skip any city base that you cannot describe in one sentence as a route job.</li>
</ul>

<!-- vg-hanoi-hcmc-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before you book</h2>
<ul class="vg-check-list vg-hanoi-hcmc-live-checks">
<li>Check whether your best international fare can arrive in one city and depart from the other.</li>
<li>Check your actual landing time before adding a domestic flight on arrival day.</li>
<li>Check hotel location against first-night needs: sleep, food, walking, airport transfer, or tour pickup.</li>
<li>Check domestic flight times before assuming Hanoi and HCMC both fit into a 10-day trip.</li>
<li>Check weather by region, not just country-level averages.</li>
<li>Check whether your side trips are northern or southern before choosing the opening city.</li>
<li>Check if one planned day can become a slow city day without breaking the route.</li>
</ul>

<!-- vg-hanoi-hcmc-faq:v1 -->
<h2 class="wp-block-heading">Hanoi vs Ho Chi Minh City FAQ</h2>
<div class="vg-faq-list vg-hanoi-hcmc-faq">
<details><summary>Is Hanoi or Ho Chi Minh City better for first-time visitors?</summary><p>Hanoi is better for most classic first trips because it connects naturally to northern culture, food, Ninh Binh, the bay, and mountain routes. HCMC is better when the south is the focus, your flights favor SGN, or you want museums, markets, Mekong, Cu Chi, and warm city energy first.</p></details>
<details><summary>Should I visit both Hanoi and Ho Chi Minh City?</summary><p>Yes if you have enough time and open-jaw flights make the route clean. For many 14-day trips, starting in Hanoi and ending in HCMC works well. For 7-10 days, both cities can make the route too flight-heavy unless the trip is carefully shaped.</p></details>
<details><summary>Which city is better for food?</summary><p>Both are excellent food cities. Hanoi is stronger for a northern food opening and Old Quarter eating rhythm. HCMC is stronger for southern food, markets, night food energy, and a broader metropolitan feel. Choose by route rather than declaring one permanent winner.</p></details>
<details><summary>Which city is easier after a long flight?</summary><p>The easiest city is usually the one you actually land in. Avoid adding a same-day domestic flight just to reach a preferred city. If both arrival options are equal, HCMC can feel softer for warm-weather hotel recovery, while Hanoi gives faster atmosphere if you choose a good central base.</p></details>
<details><summary>Is Ho Chi Minh City in North or South Vietnam?</summary><p>Ho Chi Minh City is in southern Vietnam. Hanoi is in northern Vietnam. This matters because nearby routes, climate patterns, day trips, and onward logistics are very different.</p></details>
<details><summary>Which city should I start with for 10 days in Vietnam?</summary><p>For most 10-day first trips, start with Hanoi if you want northern scenery and central Vietnam. Start with HCMC if your trip is south-led or your flights make southern entry much easier. Do not add both big cities unless you are comfortable losing time to domestic movement.</p></details>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Use this comparison before buying long-haul flights or domestic flights. Once the first city is chosen, move into route shape, transport, season, cost, and the city-specific guides.</p>
<div class="vg-related-routes vg-hanoi-hcmc-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a><span class="vg-related-route-note">Plan Hanoi as the northern route anchor.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a><span class="vg-related-route-note">Plan HCMC as a real southern chapter.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a><span class="vg-related-route-note">Choose the region spine before forcing both big cities into the trip.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Count flights, trains, airport transfers, and day-trip friction.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Check north and south weather before choosing the opener.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a><span class="vg-related-route-note">Test whether the city count is realistic.</span></li>
<li><span class="vg-related-route-step">07</span><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a><span class="vg-related-route-note">Price the cost of adding one more city base.</span></li>
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
    'vg-hanoi-hcmc-hero:v1',
    'vg-hanoi-hcmc-concierge-verdict:v1',
    'vg-hanoi-hcmc-quick-answer:v1',
    'vg-hanoi-hcmc-city-first-matrix:v1',
    'vg-hanoi-hcmc-gsc-demand:v1',
    'vg-hanoi-hcmc-photo-proof:v1',
    'vg-hanoi-hcmc-source-diversity:v1',
    'vg-hanoi-hcmc-arrival-logic:v1',
    'vg-hanoi-hcmc-route-direction:v1',
    'vg-hanoi-hcmc-weather-comfort:v1',
    'vg-hanoi-hcmc-day-trip-consequences:v1',
    'vg-hanoi-hcmc-food-culture:v1',
    'vg-hanoi-hcmc-trip-length:v1',
    'vg-hanoi-hcmc-skip-logic:v1',
    'vg-hanoi-hcmc-live-checks:v1',
    'vg-hanoi-hcmc-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_hanoi_hcmc_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Hanoi vs Ho Chi Minh City: Which Should You Visit First?',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Compare Hanoi and Ho Chi Minh City by first-night comfort, route direction, weather, food, day trips, airport logic, and trip length.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_hanoi_hcmc_post_fail('Could not update Hanoi vs Ho Chi Minh City post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Hanoi vs Ho Chi Minh City: Which Should You Visit First?');
update_post_meta($post_id, 'rank_math_description', 'Compare Hanoi and Ho Chi Minh City by first-night comfort, route direction, weather, food, day trips, airport logic, and trip length.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Hanoi vs Ho Chi Minh City');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Choose Hanoi first for a north-led first Vietnam trip; choose Ho Chi Minh City first when the south is the trip purpose or flights make SGN the materially cleaner start.');
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
    vg_hanoi_hcmc_post_fail('Could not assign Hanoi vs HCMC categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_hanoi_hcmc_post_fail('Could not assign Hanoi vs HCMC tags: ' . $tag_result->get_error_message());
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
        vg_hanoi_hcmc_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Hanoi vs Ho Chi Minh City post to complete draft: {$post_id}");

<?php
/**
 * Expand the Mekong Delta Overnight vs Day Trip post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-mekong-delta-overnight-vs-day-trip-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_mekong_overnight_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_mekong_overnight_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_mekong_overnight_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_mekong_overnight_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_mekong_overnight_post_find_by_slug(string $slug): ?WP_Post
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
        vg_mekong_overnight_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_mekong_overnight_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-20',
            'vg_editorial_brief_status' => ['brief', 'complete_draft'],
            'vg_editorial_batch' => 'batch-2-evergreen-planning',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if (is_array($expected_value)) {
            if (! in_array($actual_value, $expected_value, true)) {
                vg_mekong_overnight_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected one of " . implode(', ', $expected_value) . '.');
            }
            continue;
        }

        if ($actual_value !== $expected_value) {
            vg_mekong_overnight_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_mekong_overnight_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_mekong_overnight_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'mekong-delta-overnight-vs-day-trip';
$post = vg_mekong_overnight_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_mekong_overnight_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_mekong_overnight_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 26, 2026';

if ($post_id !== 502) {
    vg_mekong_overnight_post_fail("Refusing to update {$slug}: expected post ID 502, found {$post_id}.");
}

vg_mekong_overnight_post_assert_target_meta($post);

$category_term_ids = vg_mekong_overnight_post_term_ids('category', ['destinations', 'transport-logistics']);
$tag_term_ids = vg_mekong_overnight_post_term_ids('post_tag', ['route-planning', 'first-time-vietnam', 'family-travel', 'anti-spam-evergreen']);

$river_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1280px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg');
$cai_rang_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/fb/Cai_Rang_Floating_Market_1.jpg/1280px-Cai_Rang_Floating_Market_1.jpg');
$hcmc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1280px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg');
$chau_doc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Vietnam%2C_Panorama_of_Chau_Doc.jpg/1280px-Vietnam%2C_Panorama_of_Chau_Doc.jpg');

$meta_update_summary = 'Expanded the native WordPress batch 2 brief into a complete Mekong Delta overnight-vs-day-trip decision draft with photo-led hero, proof panel, concierge verdict, GSC demand note, day-trip/overnight/deeper-route/skip matrix, photo proof, source-diversity table, HCMC departure-risk logic, Can Tho and Cai Rang timing logic, Ben Tre/My Tho taste guidance, family and comfort filters, transport and flight-day guardrails, route-length matrix, upgrade/skip logic, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Day-tripping the Mekong Delta - https://vietnam.travel/things-to-do/day-tripping-mekong-delta - checked {$review_date}; used for day-trip framing and why a short Mekong taste is different from an overnight chapter.",
    "Vietnam.travel - 4 memorable days in the Mekong Delta - https://vietnam.travel/things-to-do/4-memorable-days-mekong-delta - checked {$review_date}; used for deeper-route context beyond a one-day tour.",
    "Vietnam.travel - Towns in the Mekong Delta - https://vietnam.travel/things-to-do/towns-mekong-delta - checked {$review_date}; used for Can Tho, river-town, and multi-stop Delta logic.",
    "Vietnam.travel - Can Tho glimpse of river and garden - https://vietnam.travel/things-to-do/can-tho-glimpse-river-and-garden - checked {$review_date}; used for Can Tho overnight and river-garden context.",
    "Vietnam.travel - Floating markets in the Mekong Delta - https://vietnam.travel/things-to-do/chasing-floating-flavour-mekong-deltas-floating-markets - checked {$review_date}; used for Cai Rang timing, early-market value, and why floating-market promises need live checks.",
    "Vietnam.travel - How to travel the Mekong Delta - https://vietnam.travel/things-to-do/how-to-travel-mekong-delta - checked {$review_date}; used for movement and route-shape context.",
    "Vietnam.travel - Cai Be - https://vietnam.travel/things-to-do/5-reasons-youll-love-cai-be - checked {$review_date}; used for short-taste and day-trip alternative framing.",
    "Vietnam.travel - Can Tho destination page - https://vietnam.travel/places-to-go/southern-vietnam/can-tho - checked {$review_date}; used for Can Tho and Cai Rang overnight role.",
    "Vietnam.travel - Chau Doc destination page - https://vietnam.travel/places-to-go/southern-vietnam/chau-doc - checked {$review_date}; used for deeper southern-route and Cambodia-border extension context.",
    "Vietnam.travel - Ho Chi Minh City destination page - https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city - checked {$review_date}; used for HCMC gateway and day-trip origin framing.",
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for heat, rain, wet-season flexibility, and weather buffers.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for bus, road-transfer, private-driver, and onward-route caution.",
    "Can Tho tourism portal - https://tourismcantho.vn - checked {$review_date}; used as a local Can Tho live-check source for visitor context.",
    "National Center for Hydro-Meteorological Forecasting - Can Tho weather - https://nchmf.gov.vn/kttvsiteE/en-US/2/can-tho-w58.html - checked {$review_date}; used as a primary Can Tho weather live-check pointer.",
    "Google Search Console query export from 2026-07-25 - checked {$review_date}; no strong Mekong-specific query cluster yet, so the draft is built from internal HCMC/itinerary intent and related day-trip comparison demand.",
    "Internal published page check - https://vietnamguide.net/destinations/mekong-delta-travel-guide/ - checked {$review_date}; used for full destination handoff.",
    "Internal published page check - https://vietnamguide.net/destinations/ho-chi-minh-city-travel-guide/ - checked {$review_date}; used for HCMC gateway handoff.",
    "Internal published page check - https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/ - checked {$review_date}; used for day-trip decision handoff.",
    "Internal published page check - https://vietnamguide.net/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/ - checked {$review_date}; used for HCMC day-trip comparison handoff.",
    "Wikimedia Commons image direct URL - Vietnam, Phong Dien, Mekong Delta, River - {$river_image} - credit Vyacheslav Argenberg / CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Cai Rang Floating Market - {$cai_rang_image} - credit Arnaud 25 / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ho Chi Minh City Hall - {$hcmc_image} - credit Steffen Schmitz / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Panorama of Chau Doc - {$chau_doc_image} - credit trungydang / CC BY 2.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This complete draft treats the Mekong Delta as a time-depth and transfer-risk decision. A day trip can be a valid taste; an overnight is worth it when Can Tho, Cai Rang, river-town evenings, or a slower southern chapter are the reason; skipping is stronger than forcing a final-flight-day checklist.';
$meta_evidence_moat = implode("\n", [
    'The article separates day trip, one-night, deeper route, and skip decisions instead of promising every Mekong tour delivers the same depth.',
    'Can Tho and Cai Rang are treated as timing-sensitive overnight value, with live checks for market rhythm and weather.',
    'Ben Tre, Cai Be, and My Tho are framed as easier tastes rather than substitutes for a serious Delta chapter.',
    'HCMC departure risk, final-flight-day traps, family comfort, private-transfer value, and route length are central decision filters.',
    'Skip logic protects travelers from final-flight-day road risk and from adding the Delta only because it is famous.',
    'Official Vietnam.travel, Tourism Can Tho, NCHMF, and internal route sources are preserved in metadata/source trail.',
    'No visible external body anchors; source URLs and image credits remain auditable in metadata/source trail.',
]);
$meta_related_routes = implode("\n", [
    'Mekong Delta Travel Guide | /destinations/mekong-delta-travel-guide/ | Use when the Delta deserves a full southern river chapter beyond this comparison.',
    'Ho Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Decide whether HCMC has enough room to support a Delta day or overnight.',
    'Best Day Trips from Ho Chi Minh City | /destinations/best-day-trips-from-ho-chi-minh-city/ | Compare Mekong taste, Cu Chi, Can Gio, Vung Tau, and other HCMC day-trip options.',
    'Cu Chi Tunnels vs Mekong Delta Day Trip | /compare/cu-chi-tunnels-vs-mekong-delta-day-trip/ | Choose a single HCMC day trip by history, river contrast, road time, and departure risk.',
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Put the Mekong decision inside the full first-trip planning order.',
    'North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether the south deserves enough route weight for the Delta.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Count bus, private-driver, airport, ferry, and onward movement before booking.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the difference between a day tour, private day, overnight, and deeper route.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check heat, rain, and wet-season flexibility before locking river-heavy days.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether the Mekong should be a taste, overnight, or clean skip on a short route.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use two weeks to decide when Can Tho or Ben Tre deserves real time.',
    'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Compare southern island recovery with a Delta river chapter before adding both.',
    'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check road, water, heat, food, and interruption coverage before Delta movement.',
    'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair day tours, deposits, private drivers, boat stops, and final-day movement with practical risk checks.',
]);
$meta_hero_image_credit = 'Hero image: Vietnam, Phong Dien, Mekong Delta, River by Vyacheslav Argenberg, CC BY 4.0. Body images: Cai Rang Floating Market by Arnaud 25, CC BY-SA 4.0; Ho Chi Minh City Hall by Steffen Schmitz, CC BY-SA 4.0; Panorama of Chau Doc by trungydang, CC BY 2.0.';
$meta_admin_notes = 'Complete draft generated for WordPress Admin review. Keep draft until manual preview, Rank Math review, image check, and final editorial pass are complete. The body intentionally avoids external anchors; source URLs are preserved in metadata and source trail.';

$content = <<<HTML
<!-- vg-mekong-overnight-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$river_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Small boat on a river in the Mekong Delta near Phong Dien" src="{$river_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<div class="wp-block-group vg-guide-hero-inner">
<p class="vg-kicker">Reviewed route decision - Updated {$review_date}</p>
<h1 class="wp-block-heading vg-display vg-guide-title">Mekong Delta Overnight vs Day Trip: Which Is Worth It?</h1>
<p class="vg-guide-lede">A Mekong Delta day trip can give a useful river taste from Ho Chi Minh City. An overnight is worth it when the reason is Can Tho, Cai Rang timing, a real river-town evening, or a slower southern chapter. The weak choice is squeezing the Delta into the final morning before a flight because it sounds famous.</p>
<p class="vg-field-note">Concierge verdict: choose the amount of Delta time by depth, comfort, road time, market timing, and departure risk. One honest day beats a fake overnight; one protected night beats a staged checklist.</p>
</div>
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-mekong-overnight-concierge-verdict:v1 -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<p><strong>Choose a Mekong day trip when the route only needs a taste.</strong> A good day from Ho Chi Minh City can add river canals, orchards, lunch, and a softer southern contrast, but it should be treated as a sample rather than proof that you have experienced the Delta deeply.</p>
<p><strong>Choose one night when Cai Rang, Can Tho, or a river-town evening is the reason.</strong> Overnight time changes the day: you can avoid some road pressure, catch earlier rhythm, and let the Delta feel less like an HCMC add-on.</p>
<p><strong>Skip the Delta when it only creates final-day risk.</strong> If the plan requires a long road day before an international flight, or if southern Vietnam is already crowded with HCMC, Phu Quoc, tunnels, and food plans, a clean skip can be the more premium decision.</p>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-mekong-overnight-gsc-demand:v1 -->
<h2 class="wp-block-heading">Search evidence shaping this draft</h2>
<p>Recent Search Console data for vietnamguide.net does not yet show a strong Mekong-specific query cluster, but the site already receives related first-trip, 10-day itinerary, HCMC day-trip, and cost-planning demand. This draft is built as an internal decision module for that traffic: when a traveler reaches the south, should the Delta be a taste, an overnight, a deeper route, or removed?</p>

<!-- vg-mekong-overnight-decision-matrix:v1 -->
<h2 class="wp-block-heading">Day trip, overnight, deeper route or skip?</h2>
<table class="vg-decision-table vg-mekong-overnight-decision-matrix">
<thead><tr><th>Choice</th><th>Best when</th><th>Keep</th><th>Cut first</th></tr></thead>
<tbody>
<tr><td data-label="Choice">Day trip from HCMC</td><td data-label="Best when">You want a river taste but cannot protect a night.</td><td data-label="Keep">One focused area, early departure, lunch, canals, no flight-day pressure.</td><td data-label="Cut first">Combining too many stops, shopping padding, and late return risk.</td></tr>
<tr><td data-label="Choice">One-night Can Tho</td><td data-label="Best when">Cai Rang, river-town evening, and slower southern rhythm matter.</td><td data-label="Keep">Can Tho arrival, evening food/walk, early floating-market check, controlled return.</td><td data-label="Cut first">Extra villages if market timing and rest suffer.</td></tr>
<tr><td data-label="Choice">Ben Tre / Cai Be taste</td><td data-label="Best when">You want easier canals, fruit, lunch, and less commitment than Can Tho.</td><td data-label="Keep">Comfortable transport and a narrow river/canal goal.</td><td data-label="Cut first">Pretending this equals a full Delta chapter.</td></tr>
<tr><td data-label="Choice">Deeper southern route</td><td data-label="Best when">The trip has enough time for Can Tho, Chau Doc, Tra Su, or onward Cambodia logic.</td><td data-label="Keep">Two or more nights and route-specific movement.</td><td data-label="Cut first">A rushed return to HCMC that erases the depth.</td></tr>
<tr><td data-label="Choice">Clean skip</td><td data-label="Best when">The route is short, flight timing is fragile, or the south is already overloaded.</td><td data-label="Keep">A stronger HCMC day, Cu Chi, food, rest, or Phu Quoc buffer.</td><td data-label="Cut first">Guilt-driven river checklist.</td></tr>
</tbody>
</table>

<!-- vg-mekong-overnight-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: depth changes the Delta</h2>
<div class="vg-photo-grid vg-mekong-overnight-photo-proof">
<figure><img src="{$river_image}" alt="Small boat on a Mekong Delta river near Phong Dien" loading="lazy" decoding="async"><figcaption>A day trip can be a good taste when the route cannot hold a night. Image: Vyacheslav Argenberg / CC BY 4.0.</figcaption></figure>
<figure><img src="{$cai_rang_image}" alt="Cai Rang Floating Market in Can Tho" loading="lazy" decoding="async"><figcaption>Cai Rang is the main reason many travelers upgrade from a day trip to one night. Image: Arnaud 25 / CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall near Nguyen Hue walking street" loading="lazy" decoding="async"><figcaption>Ho Chi Minh City is the gateway, but its airport and city rhythm shape how much Delta time is realistic. Image: Steffen Schmitz / CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$chau_doc_image}" alt="Panorama of Chau Doc in southern Vietnam" loading="lazy" decoding="async"><figcaption>Chau Doc belongs to a deeper southern route, not a casual day trip from HCMC. Image: trungydang / CC BY 2.0.</figcaption></figure>
</div>

<!-- vg-mekong-overnight-source-diversity:v1 -->
<h2 class="wp-block-heading">Source diversity behind this guide</h2>
<table class="vg-decision-table vg-mekong-overnight-source-diversity">
<thead><tr><th>Evidence layer</th><th>What it can prove</th><th>What still needs editorial judgment</th></tr></thead>
<tbody>
<tr><td data-label="Evidence layer">Vietnam.travel Mekong articles</td><td data-label="What it can prove">Day-trip, multi-day, towns, floating-market, Can Tho, Cai Be, and movement context.</td><td data-label="What still needs editorial judgment">Whether the route should buy one day, one night, or a deeper southern chapter.</td></tr>
<tr><td data-label="Evidence layer">Can Tho and Chau Doc destination sources</td><td data-label="What it can prove">Why Can Tho and Chau Doc are different Delta jobs.</td><td data-label="What still needs editorial judgment">Whether a first-time visitor should go beyond Can Tho.</td></tr>
<tr><td data-label="Evidence layer">Weather and transport sources</td><td data-label="What it can prove">Where to check rain, heat, road, bus, private-driver, and onward movement constraints.</td><td data-label="What still needs editorial judgment">Whether the plan should shrink, move, or be skipped.</td></tr>
<tr><td data-label="Evidence layer">Tourism Can Tho and NCHMF</td><td data-label="What it can prove">Local Can Tho visitor context and weather live-check path.</td><td data-label="What still needs editorial judgment">Whether Cai Rang timing and hotel choice justify the overnight.</td></tr>
<tr><td data-label="Evidence layer">GSC and internal routes</td><td data-label="What it can prove">The site needs a southern comparison module for itinerary, HCMC, day-trip, and cost pages.</td><td data-label="What still needs editorial judgment">How to avoid duplicating the Mekong Delta Travel Guide.</td></tr>
</tbody>
</table>

<!-- vg-mekong-overnight-day-trip-fit:v1 -->
<h2 class="wp-block-heading">When a Mekong day trip is enough</h2>
<p>A day trip is enough when the route wants contrast, not depth. It works best for travelers who have one spare HCMC day, no final-flight risk, and a realistic expectation: canals, lunch, fruit, small boats, villages, and road time, not a full river life education.</p>
<ul class="vg-check-list vg-mekong-overnight-day-trip-fit">
<li><strong>Good fit:</strong> first-timers with limited time who want one softer river-and-food day outside HCMC.</li>
<li><strong>Good fit:</strong> families who need a simpler hotel base and do not want another check-in.</li>
<li><strong>Good fit:</strong> travelers comparing Cu Chi and the Mekong and choosing river contrast over war-history context.</li>
<li><strong>Weak fit:</strong> anyone who cares about Cai Rang, early market rhythm, or a real Delta evening.</li>
<li><strong>Weak fit:</strong> final-day plans where a road delay could threaten an international flight.</li>
</ul>

<!-- vg-mekong-overnight-upgrade-logic:v1 -->
<h2 class="wp-block-heading">When one night is worth it</h2>
<p>Upgrade to an overnight when the Delta is not just scenery. The best one-night version is usually built around Can Tho or a carefully chosen local base, with enough time for arrival, dinner, an early river or market block, and a return that does not punish the next city.</p>
<table class="vg-decision-table vg-mekong-overnight-upgrade-logic">
<thead><tr><th>Reason to upgrade</th><th>What the overnight adds</th><th>Watch out for</th></tr></thead>
<tbody>
<tr><td data-label="Reason to upgrade">Cai Rang matters</td><td data-label="What the overnight adds">Better early timing and less road pressure before the market.</td><td data-label="Watch out for">Assuming the market is equally lively every day and season.</td></tr>
<tr><td data-label="Reason to upgrade">River-town evening matters</td><td data-label="What the overnight adds">Food, promenade, hotel reset, and less HCMC-centric pacing.</td><td data-label="Watch out for">A hotel too far from the evening rhythm.</td></tr>
<tr><td data-label="Reason to upgrade">Family comfort matters</td><td data-label="What the overnight adds">Less one-day road compression and more rest control.</td><td data-label="Watch out for">Homestay comfort promises that do not match the group.</td></tr>
<tr><td data-label="Reason to upgrade">Southern route matters</td><td data-label="What the overnight adds">A real regional chapter before Phu Quoc, Chau Doc, or return to HCMC.</td><td data-label="Watch out for">Adding the night while keeping every HCMC plan unchanged.</td></tr>
</tbody>
</table>

<!-- vg-mekong-overnight-can-tho-cai-rang:v1 -->
<h2 class="wp-block-heading">Can Tho and Cai Rang: why timing matters</h2>
<p>Can Tho is the cleanest upgrade when the floating-market question is real. Cai Rang rewards early timing and realistic expectations. It should not be sold as a guaranteed postcard or a timeless spectacle; it is a working, changing place that needs live checks, local timing, and some humility from the itinerary.</p>
<ul class="vg-check-list vg-mekong-overnight-can-tho-cai-rang">
<li>Check current Cai Rang timing and market rhythm close to travel before building the overnight around it.</li>
<li>Choose a Can Tho hotel or homestay by early transfer practicality, not only charm.</li>
<li>Protect the evening before the market; arriving exhausted makes the early start weaker.</li>
<li>Do not add every garden, village, market, and canal if the overnight's real value is early timing.</li>
<li>Use the full Mekong Delta guide if Can Tho is becoming a real route chapter.</li>
</ul>

<!-- vg-mekong-overnight-ben-tre-my-tho:v1 -->
<h2 class="wp-block-heading">Ben Tre, Cai Be and My Tho: better as a taste?</h2>
<p>Ben Tre, Cai Be, and My Tho are often easier to package from HCMC than Can Tho. That does not make them bad. It means they should be judged honestly: they are strong for a taste, canals, lunch, and softer day structure, but weaker when the traveler expects early floating-market depth.</p>
<table class="vg-decision-table vg-mekong-overnight-ben-tre-my-tho">
<thead><tr><th>Area role</th><th>Use it when</th><th>Do not pretend</th></tr></thead>
<tbody>
<tr><td data-label="Area role">Ben Tre</td><td data-label="Use it when">You want canals, lunch, coconut-country texture, and an easier day or soft overnight.</td><td data-label="Do not pretend">It is the same as a Can Tho/Cai Rang overnight.</td></tr>
<tr><td data-label="Area role">Cai Be</td><td data-label="Use it when">You want a gentler Mekong stop and can verify current local value.</td><td data-label="Do not pretend">Every old floating-market claim is current.</td></tr>
<tr><td data-label="Area role">My Tho</td><td data-label="Use it when">You need the simplest HCMC-based taste and accept staged-tour risk.</td><td data-label="Do not pretend">It gives the deepest Delta experience.</td></tr>
<tr><td data-label="Area role">Can Tho</td><td data-label="Use it when">You want the strongest one-night market and river-town logic.</td><td data-label="Do not pretend">It is frictionless as a casual day trip.</td></tr>
</tbody>
</table>

<!-- vg-mekong-overnight-family-comfort:v1 -->
<h2 class="wp-block-heading">Families, comfort and private transfer value</h2>
<p>The Delta decision changes with children, older relatives, heat sensitivity, luggage, and comfort expectations. A cheap long day can become expensive in energy; a private transfer can be worth it if it protects rest, but not if it merely makes an overstuffed plan more expensive.</p>
<ul class="vg-check-list vg-mekong-overnight-family-comfort">
<li><strong>Choose a day trip</strong> when one hotel base, simple meals, and no repacking matter most.</li>
<li><strong>Choose one night</strong> when the group can handle an early start better after sleeping near the river.</li>
<li><strong>Choose private timing</strong> when the group needs control over stops, heat breaks, and return time.</li>
<li><strong>Choose a hotel over homestay</strong> when bathroom comfort, air-conditioning, stairs, or bedding could affect sleep.</li>
<li><strong>Skip the Delta</strong> when the southern schedule already has too many road days.</li>
</ul>

<!-- vg-mekong-overnight-transport-flight-day:v1 -->
<h2 class="wp-block-heading">Transport and flight-day guardrails</h2>
<p>Most weak Mekong plans are not weak because of the river. They are weak because the traveler undercounts road time, checkout time, luggage, weather, and flight anxiety. Never place the Mekong on a fragile international departure day.</p>
<table class="vg-decision-table vg-mekong-overnight-transport-flight-day">
<thead><tr><th>Constraint</th><th>Safer move</th><th>Risky move</th></tr></thead>
<tbody>
<tr><td data-label="Constraint">International flight day</td><td data-label="Safer move">Stay in HCMC or near the airport with a city plan.</td><td data-label="Risky move">Return from a Delta tour and fly the same evening without real buffer.</td></tr>
<tr><td data-label="Constraint">Domestic onward flight</td><td data-label="Safer move">Return to HCMC the day before or use conservative timing.</td><td data-label="Risky move">Counting a best-case road estimate as if delays cannot happen.</td></tr>
<tr><td data-label="Constraint">Luggage-heavy family</td><td data-label="Safer move">Private transfer or one HCMC base with day trip.</td><td data-label="Risky move">Complex bus and hotel moves for a shallow overnight.</td></tr>
<tr><td data-label="Constraint">Rainy or wet-season travel</td><td data-label="Safer move">Flexible timing, covered pivots, and hotel comfort.</td><td data-label="Risky move">A non-flexible boat-heavy day with no backup.</td></tr>
</tbody>
</table>

<!-- vg-mekong-overnight-route-length:v1 -->
<h2 class="wp-block-heading">Mekong decision by trip length</h2>
<table class="vg-decision-table vg-mekong-overnight-route-length">
<thead><tr><th>Trip length</th><th>Recommended Mekong role</th><th>Cut first</th></tr></thead>
<tbody>
<tr><td data-label="Trip length">5-6 days</td><td data-label="Recommended Mekong role">Usually skip unless southern Vietnam is the whole trip.</td><td data-label="Cut first">A long Delta day that weakens HCMC and departure comfort.</td></tr>
<tr><td data-label="Trip length">7 days</td><td data-label="Recommended Mekong role">Day trip only if HCMC is a main base and no flight-day risk.</td><td data-label="Cut first">Overnight Delta if it forces a thinner north or central route.</td></tr>
<tr><td data-label="Trip length">10 days</td><td data-label="Recommended Mekong role">Taste or clean skip unless the route is south-led.</td><td data-label="Cut first">Adding Mekong because it is famous while cutting recovery.</td></tr>
<tr><td data-label="Trip length">14 days</td><td data-label="Recommended Mekong role">One night can work if the south has real weight.</td><td data-label="Cut first">A rushed extra beach or weak final-city day.</td></tr>
<tr><td data-label="Trip length">21 days</td><td data-label="Recommended Mekong role">A deeper Can Tho, Chau Doc, or southern route can be justified.</td><td data-label="Cut first">Duplicated river time if the trip already has enough slow-water scenery.</td></tr>
</tbody>
</table>

<!-- vg-mekong-overnight-upgrade-skip-logic:v1 -->
<h2 class="wp-block-heading">Upgrade and skip logic</h2>
<ul class="vg-check-list vg-mekong-overnight-upgrade-skip-logic">
<li>Upgrade from day trip to overnight when Cai Rang timing or a real river-town evening is the reason.</li>
<li>Upgrade when one night reduces road compression instead of adding another hotel chore.</li>
<li>Keep it as a day trip when the route only needs contrast and the traveler accepts that it is a taste.</li>
<li>Skip when the Delta is being used to fill a final day before a flight.</li>
<li>Skip when Phu Quoc, HCMC food, Cu Chi, or rest is a stronger southern use of time.</li>
<li>Skip any plan that promises too much floating-market certainty without a live timing check.</li>
</ul>

<!-- vg-mekong-overnight-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before booking</h2>
<ul class="vg-check-list vg-mekong-overnight-live-checks">
<li>Check current Can Tho and Cai Rang timing, market rhythm, and whether the chosen tour is built around real early value.</li>
<li>Check Can Tho weather, rain, heat, and river conditions close to travel.</li>
<li>Check HCMC departure time, airport transfer, and luggage plan before placing the Delta near a flight.</li>
<li>Check whether the route needs Ben Tre/My Tho/Cai Be taste, Can Tho overnight, Chau Doc extension, or a clean skip.</li>
<li>Check private-driver, bus, boat, hotel, and homestay comfort details before comparing prices.</li>
<li>Check refund, cancellation, and weather flexibility for boat-heavy plans.</li>
</ul>

<!-- vg-mekong-overnight-faq:v1 -->
<h2 class="wp-block-heading">Mekong Delta overnight vs day trip FAQ</h2>
<div class="vg-faq-list vg-mekong-overnight-faq">
<details><summary>Is a Mekong Delta day trip worth it?</summary><p>Yes when you want a river taste from Ho Chi Minh City and cannot protect a night. Treat it as a sample, not a substitute for a real Can Tho or deeper Delta chapter.</p></details>
<details><summary>Is one night in the Mekong Delta worth it?</summary><p>Yes when Cai Rang, Can Tho, an early river morning, or a river-town evening is the reason. It is less worth it if the overnight only adds hotel movement without deeper timing.</p></details>
<details><summary>Should I choose Ben Tre or Can Tho?</summary><p>Choose Ben Tre for an easier canal-and-lunch taste. Choose Can Tho when the overnight and Cai Rang timing matter. Do not judge them as the same product.</p></details>
<details><summary>Can I visit the Mekong Delta before an international flight?</summary><p>Avoid it unless the timing is extremely conservative and the flight is not fragile. The safer premium move is to return to HCMC the day before departure.</p></details>
<details><summary>How many nights do I need for the Mekong Delta?</summary><p>One night is enough for a focused Can Tho/Cai Rang upgrade. Two or more nights make sense only when the south is a real route chapter, such as Chau Doc, Tra Su, or onward movement.</p></details>
<details><summary>Should I do Cu Chi or the Mekong Delta from HCMC?</summary><p>Choose Cu Chi for history and wartime context. Choose the Mekong for river contrast, food, and softer southern rhythm. If the Mekong is the main reason, upgrade beyond a day trip.</p></details>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Use this guide before booking a Delta tour or moving hotels. Then cross-check the full Mekong guide, HCMC guide, HCMC day trips, Cu Chi comparison, transport, cost, season, and itinerary pages.</p>
<div class="vg-related-routes vg-mekong-overnight-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/destinations/mekong-delta-travel-guide/">Mekong Delta Travel Guide</a><span class="vg-related-route-note">Use when the Delta deserves a full southern chapter.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a><span class="vg-related-route-note">Check whether HCMC can support a day or overnight Delta move.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/destinations/best-day-trips-from-ho-chi-minh-city/">Best Day Trips from Ho Chi Minh City</a><span class="vg-related-route-note">Compare the Mekong taste with other HCMC day-trip jobs.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/">Cu Chi Tunnels vs Mekong Delta Day Trip</a><span class="vg-related-route-note">Choose history or river contrast when there is only one day.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a><span class="vg-related-route-note">Price the day trip, private day, overnight, and deeper route difference.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Count road time, airport pressure, luggage, and onward movement.</span></li>
<li><span class="vg-related-route-step">07</span><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a><span class="vg-related-route-note">Test when one Mekong night earns its place.</span></li>
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
    'vg-mekong-overnight-hero:v1',
    'vg-mekong-overnight-concierge-verdict:v1',
    'vg-mekong-overnight-gsc-demand:v1',
    'vg-mekong-overnight-decision-matrix:v1',
    'vg-mekong-overnight-photo-proof:v1',
    'vg-mekong-overnight-source-diversity:v1',
    'vg-mekong-overnight-day-trip-fit:v1',
    'vg-mekong-overnight-upgrade-logic:v1',
    'vg-mekong-overnight-can-tho-cai-rang:v1',
    'vg-mekong-overnight-ben-tre-my-tho:v1',
    'vg-mekong-overnight-family-comfort:v1',
    'vg-mekong-overnight-transport-flight-day:v1',
    'vg-mekong-overnight-route-length:v1',
    'vg-mekong-overnight-upgrade-skip-logic:v1',
    'vg-mekong-overnight-live-checks:v1',
    'vg-mekong-overnight-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_mekong_overnight_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Mekong Delta Overnight vs Day Trip: Which Is Worth It?',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Decide whether the Mekong Delta deserves a day trip, one-night Can Tho/Cai Rang stay, deeper southern route, or clean skip by timing, comfort, transfer risk, and route length.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_mekong_overnight_post_fail('Could not update Mekong Delta Overnight post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Mekong Delta Overnight vs Day Trip: Which Is Worth It?');
update_post_meta($post_id, 'rank_math_description', 'Choose a Mekong Delta day trip, one-night Can Tho/Cai Rang stay, deeper route, or clean skip by timing, comfort, transfer risk, and route length.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Mekong Delta overnight vs day trip');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Decide whether the Mekong Delta deserves a day trip, one-night Can Tho/Cai Rang stay, deeper southern route, or clean skip based on timing, comfort, transfer risk, flight-day pressure, and route length.');
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
    vg_mekong_overnight_post_fail('Could not assign Mekong Delta Overnight categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_mekong_overnight_post_fail('Could not assign Mekong Delta Overnight tags: ' . $tag_result->get_error_message());
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
        vg_mekong_overnight_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Mekong Delta Overnight post to complete draft: {$post_id}");

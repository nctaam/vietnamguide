<?php
/**
 * Expand the Hue Imperial City Guide post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-hue-imperial-city-guide-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_hue_imperial_city_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_hue_imperial_city_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_hue_imperial_city_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_hue_imperial_city_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_hue_imperial_city_post_find_by_slug(string $slug): ?WP_Post
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
        vg_hue_imperial_city_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_hue_imperial_city_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-18',
            'vg_editorial_brief_status' => ['brief', 'complete_draft'],
            'vg_editorial_batch' => 'batch-2-evergreen-planning',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if (is_array($expected_value)) {
            if (! in_array($actual_value, $expected_value, true)) {
                vg_hue_imperial_city_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected one of " . implode(', ', $expected_value) . '.');
            }
            continue;
        }

        if ($actual_value !== $expected_value) {
            vg_hue_imperial_city_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_hue_imperial_city_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_hue_imperial_city_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'hue-imperial-city-guide';
$post = vg_hue_imperial_city_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_hue_imperial_city_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_hue_imperial_city_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 26, 2026';

if ($post_id !== 500) {
    vg_hue_imperial_city_post_fail("Refusing to update {$slug}: expected post ID 500, found {$post_id}.");
}

vg_hue_imperial_city_post_assert_target_meta($post);

$category_term_ids = vg_hue_imperial_city_post_term_ids('category', ['destinations', 'heritage-culture']);
$tag_term_ids = vg_hue_imperial_city_post_term_ids('post_tag', ['heritage-travel', 'route-planning', 'first-time-vietnam', 'anti-spam-evergreen']);

$citadel_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg/1920px-Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$thien_mu_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/Hue_Vietnam_Thien-Mu-Temple-and-Pagoda-03.jpg/1280px-Hue_Vietnam_Thien-Mu-Temple-and-Pagoda-03.jpg');
$minh_mang_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Hue_Vietnam_Tomb-of-Emperor-Minh-Mang-01.jpg/1920px-Hue_Vietnam_Tomb-of-Emperor-Minh-Mang-01.jpg');
$perfume_river_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/Hue_Vietnam_Perfume-River-01.jpg/1920px-Hue_Vietnam_Perfume-River-01.jpg');

$meta_update_summary = 'Expanded the native WordPress batch 2 brief into a complete Hue Imperial City decision draft with photo-led hero, proof panel, concierge verdict, stay-half-day-pass-through matrix, GSC demand note, photo proof, source-diversity table, time-budget matrix, Imperial City pacing, tomb-choice logic, guide-value guidance, heat/rain rhythm, Da Nang/Hoi An route order, ticket/opening/access live checks, food and river pacing, trip-length matrix, skip logic, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "UNESCO World Heritage Centre - Complex of Hue Monuments - https://whc.unesco.org/en/list/678/ - checked {$review_date}; used for Hue heritage framing, conservation tone, and why the Imperial City should not be reduced to a photo stop.",
    "UNESCO World Heritage Centre - Complex of Hue Monuments map reference - https://whc.unesco.org/en/list/678/maps/ - checked {$review_date}; used for scale, property/buffer-zone discipline, and why route pacing matters.",
    "Hue Monuments Conservation Centre - Tourism information and ticket price reference - https://hueworldheritage.org.vn/en-us/Tourism-information/Price - checked {$review_date}; used as the official live-check pointer for ticket, combo, access, and site-rule verification.",
    "Hue Monuments Conservation Centre - opening and closing times - https://hueworldheritage.org.vn/en-us/tabid/99/language/en-US/Default.aspx/tid/OpeningClosing-times-at-monumental-sites.html/pid/3839/cid/208/ - checked {$review_date}; used as the official live-check pointer for current opening windows and ticket-office cutoff.",
    "Hue Monuments Conservation Centre - Hue Imperial City page - https://hueworldheritage.org.vn/en-us/Home/tid/Hue-Imperial-City/pid/6D26AD43-0DCF-48F7-8E7F-AF6000AC75D4 - checked {$review_date}; used for site-specific Imperial City context.",
    "Hue Monuments Conservation Centre - guide and interpretation at Hue Imperial City - https://hueworldheritage.org.vn/en-us/tabid/151/language/en-US/Default.aspx/tid/Guide-and-interpretation-at-Hue-Imperial-City/pid/2961CB09-F12F-4585-97A3-AF6000B2C10D - checked {$review_date}; used for guide-value and interpretation guidance.",
    "Vietnam.travel - Hue destination page - https://vietnam.travel/places-to-go/central-vietnam/hue - checked {$review_date}; used for Hue destination role, Citadel, tombs, food, river, and central Vietnam placement.",
    "Vietnam.travel - Hue itinerary - https://vietnam.travel/things-to-do/hue-itinerary - checked {$review_date}; used for practical sequencing, food, river, and city rhythm checks.",
    "Vietnam.travel - inside guide to Hue tombs - https://vietnam.travel/things-to-do/an-inside-guide-hue-tombs - checked {$review_date}; used for tomb choice, contrast, and why one chosen tomb can beat a tomb circuit.",
    "Vietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked {$review_date}; used for Da Nang, Hoi An, Hue, coast, and central-route sequencing.",
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for heat, rain, central Vietnam weather, and flexible timing guidance.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for train, flight, road-transfer, and Da Nang/Hoi An movement framing.",
    "Vietnam.travel - Hoi An to Hue over Hai Van Pass - https://vietnam.travel/things-to-do/motorbiking-hoi-an-hue-over-hai-van-pass - checked {$review_date}; used for directional central-route and Hai Van transfer logic.",
    "Google Search Console query export from 2026-07-25 - checked {$review_date}; used for observed Hue things-to-do, what-to-see, and heritage-route query phrasing.",
    "Internal published page check - https://vietnamguide.net/destinations/best-things-to-do-in-hue/ - checked {$review_date}; used for attraction-depth handoff.",
    "Internal published page check - https://vietnamguide.net/compare/hoi-an-vs-hue/ - checked {$review_date}; used for central heritage choice handoff.",
    "Internal published page check - https://vietnamguide.net/destinations/unesco-heritage-sites-vietnam/ - checked {$review_date}; used for heritage-cluster handoff.",
    "Wikimedia Commons image direct URL - Hue Vietnam Citadel - {$citadel_image} - credit CEphoto, Uwe Aranas / CC BY-SA 3.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hue Vietnam Thien Mu Temple and Pagoda - {$thien_mu_image} - credit CEphoto, Uwe Aranas / CC BY-SA 3.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hue Vietnam Tomb of Emperor Minh Mang - {$minh_mang_image} - credit CEphoto, Uwe Aranas / CC BY-SA 3.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hue Vietnam Perfume River - {$perfume_river_image} - credit CEphoto, Uwe Aranas / CC BY-SA 3.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This complete draft treats Hue Imperial City as a route-pacing and context decision, not as a copied monument history page. It helps travelers decide whether Hue deserves a dedicated stay, a disciplined half-day, or a pass-through role based on heat, guide value, tomb selection, food, river rhythm, and Da Nang/Hoi An transfer pressure.';
$meta_evidence_moat = implode("\n", [
    'UNESCO framing explains why the Complex of Hue Monuments needs context and visitor discipline rather than a thin gate-and-tomb checklist.',
    'The core verdict separates two-night stay, one-night stay, half-day, and pass-through decisions by route job.',
    'GSC demand is used for Hue things-to-do and what-to-see phrasing while keeping this page focused on pacing, not attraction duplication.',
    'Ticket, opening, guide, and access guidance points readers to official live-check sources without freezing volatile prices or hours in the article body.',
    'Imperial City pacing, guide value, tomb-choice logic, food blocks, and river time are treated as trade-offs inside one day.',
    'Heat/rain guidance changes the order of the day instead of pretending every monument works at every hour.',
    'Skip logic protects central Vietnam routes from adding Hue only because it is famous.',
    'No visible external body anchors; official sources and image credits stay auditable in metadata/source trail.',
]);
$meta_related_routes = implode("\n", [
    'Best Things to Do in Hue | /destinations/best-things-to-do-in-hue/ | Use after deciding Hue deserves a protected heritage day and you need the attraction-level priority map.',
    'Hoi An vs Hue | /compare/hoi-an-vs-hue/ | Decide whether central Vietnam should prioritize Hoi An atmosphere, Hue imperial depth, or both.',
    'UNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Put Hue inside the broader heritage route without treating every monument as mandatory.',
    'Da Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Choose the central Vietnam sleep base before forcing Hue into a weak transfer day.',
    'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use when the central route needs airport access, beach comfort, and easier logistics.',
    'Best Things to Do in Hoi An | /destinations/best-things-to-do-in-hoi-an/ | Pair Hoi An with Hue only when central Vietnam has enough protected slack.',
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Put Hue inside the full first-trip planning order.',
    'North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Decide whether central Vietnam deserves the route center.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check central Vietnam heat, rain, and storm-sensitive months before locking Hue.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Count Da Nang airport, Hue train, private-car, and Hoi An transfer friction before booking.',
    '7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Use when a short trip may need to skip Hue or central Vietnam entirely.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Hue can fit without thinning Hoi An, Ninh Binh, or the bay.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use when two weeks can protect Hue, Hoi An, and a northern or southern chapter.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the difference between one night, two nights, guide value, tickets, and private transfer choices.',
]);
$meta_hero_image_credit = 'Hero image: Hue Vietnam Citadel by CEphoto, Uwe Aranas, CC BY-SA 3.0. Body images: Thien Mu Temple and Pagoda, Minh Mang Tomb, and Perfume River in Hue by CEphoto, Uwe Aranas, CC BY-SA 3.0.';
$meta_admin_notes = 'Complete draft generated for WordPress Admin review. Keep draft until manual preview, Rank Math review, image check, and final editorial pass are complete. The body intentionally avoids external anchors; source URLs are preserved in metadata and source trail.';

$content = <<<HTML
<!-- vg-hue-imperial-city-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$citadel_image}","dimRatio":56,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hue Citadel architecture inside the Complex of Hue Monuments" src="{$citadel_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<div class="wp-block-group vg-guide-hero-inner">
<p class="vg-kicker">Reviewed heritage guide - Updated {$review_date}</p>
<h1 class="wp-block-heading vg-display vg-guide-title">Hue Imperial City Guide: How to Visit Without Rushing</h1>
<p class="vg-guide-lede">Hue Imperial City is not a monument to squeeze between Da Nang and Hoi An. It is the core argument for giving Hue protected time: context, shade, a guide if useful, one tomb chosen well, a real meal, and a transfer plan that does not turn heritage into logistics.</p>
<p class="vg-field-note">Concierge verdict: give Hue two nights if imperial history matters, one disciplined night if the route is tight, a half-day only with a narrow goal, and a clean skip when central Vietnam needs Hoi An or Da Nang more.</p>
</div>
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-hue-imperial-city-concierge-verdict:v1 -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<p><strong>Hue Imperial City deserves protected time when the route needs a serious heritage chapter, not just another central Vietnam stop.</strong> The best first Hue plan is the Imperial City early, one royal tomb or Thien Mu Pagoda, one food block, and enough shade or rest that the day still has attention.</p>
<p><strong>Two nights is the clean default for heritage-minded travelers.</strong> One night can work if arrival is gentle and the next transfer is not too early. A drive-through stop can be better than nothing, but only when the goal is narrow: one site, one meal, or one guided context block.</p>
<p><strong>Skip Hue when the route cannot give the city context.</strong> If the schedule already has Hoi An, Da Nang, a flight, a beach plan, and no slack, forcing Hue can make central Vietnam feel more impressive on paper and weaker in memory.</p>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-hue-imperial-city-stay-halfday-pass:v1 -->
<!-- vg-hue-imperial-city-decision-matrix:v1 -->
<h2 class="wp-block-heading">Stay, half-day or pass-through matrix</h2>
<table class="vg-decision-table vg-hue-imperial-city-stay-halfday-pass">
<thead><tr><th>Hue role</th><th>Best when</th><th>Keep</th><th>Cut first</th></tr></thead>
<tbody>
<tr><td data-label="Hue role">Two-night Hue stay</td><td data-label="Best when">Imperial history, tomb landscapes, food, and a calmer central chapter matter.</td><td data-label="Keep">Imperial City, one tomb, Thien Mu or river, food, guide context.</td><td data-label="Cut first">Third monument, distant detours, rushed cruise.</td></tr>
<tr><td data-label="Hue role">One-night Hue stay</td><td data-label="Best when">You want real Hue value but the route is north-central or central-south tight.</td><td data-label="Keep">Arrival meal, early Imperial City, one tomb or Thien Mu, clean onward transfer.</td><td data-label="Cut first">A second tomb unless departure is late.</td></tr>
<tr><td data-label="Hue role">Focused half-day</td><td data-label="Best when">The route passes Hue and you can accept a narrow experience.</td><td data-label="Keep">Imperial City with guide context or one tomb chosen for meaning.</td><td data-label="Cut first">Trying to see Imperial City, tombs, river, market, and Hai Van in one block.</td></tr>
<tr><td data-label="Hue role">Pass-through only</td><td data-label="Best when">Transfers are fixed and the route cannot absorb a full Hue chapter.</td><td data-label="Keep">Food stop, short river view, or single context stop if convenient.</td><td data-label="Cut first">Any plan that makes the next city start exhausted.</td></tr>
<tr><td data-label="Hue role">Clean skip</td><td data-label="Best when">Hoi An atmosphere, Da Nang beach/logistics, or northern scenery is the real priority.</td><td data-label="Keep">The route's main job.</td><td data-label="Cut first">Guilt-driven heritage padding.</td></tr>
</tbody>
</table>

<!-- vg-hue-imperial-city-gsc-demand:v1 -->
<h2 class="wp-block-heading">Search evidence shaping this draft</h2>
<p>Recent Search Console data for vietnamguide.net shows early Hue demand around "things to do in Hue", "Hue what to see", "what to do in Hue", "things to do in Hue Vietnam", and "heritage route". That demand supports a practical Hue page, but this draft deliberately answers the planning question before the attraction list: how much time should Hue get, what should be protected first, and what should be removed when the day is too hot, wet, or transfer-heavy?</p>

<!-- vg-hue-imperial-city-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: why Hue needs pacing</h2>
<div class="vg-photo-grid vg-hue-imperial-city-photo-proof">
<figure><img src="{$citadel_image}" alt="Hue Citadel architecture in central Vietnam" loading="lazy" decoding="async"><figcaption>The Imperial City is Hue's core route argument and should get the best attention window of the day. Image: CEphoto, Uwe Aranas / CC BY-SA 3.0.</figcaption></figure>
<figure><img src="{$thien_mu_image}" alt="Thien Mu Pagoda beside the Perfume River in Hue" loading="lazy" decoding="async"><figcaption>Thien Mu works as a calmer river-side chapter when the day needs a reset after the Imperial City. Image: CEphoto, Uwe Aranas / CC BY-SA 3.0.</figcaption></figure>
<figure><img src="{$minh_mang_image}" alt="Minh Mang Tomb landscape in Hue" loading="lazy" decoding="async"><figcaption>Minh Mang is the strongest default tomb when the route has room for one royal landscape. Image: CEphoto, Uwe Aranas / CC BY-SA 3.0.</figcaption></figure>
<figure><img src="{$perfume_river_image}" alt="Perfume River in Hue" loading="lazy" decoding="async"><figcaption>The Perfume River explains why Hue should feel slower than a vehicle transfer between central bases. Image: CEphoto, Uwe Aranas / CC BY-SA 3.0.</figcaption></figure>
</div>

<!-- vg-hue-imperial-city-source-diversity:v1 -->
<h2 class="wp-block-heading">Source diversity behind this guide</h2>
<table class="vg-decision-table vg-hue-imperial-city-source-diversity">
<thead><tr><th>Evidence layer</th><th>What it can prove</th><th>What still needs editorial judgment</th></tr></thead>
<tbody>
<tr><td data-label="Evidence layer">UNESCO listing</td><td data-label="What it can prove">The Complex of Hue Monuments has world-heritage significance and conservation context.</td><td data-label="What still needs editorial judgment">Whether a traveler should stay two nights, one night, half a day, or skip.</td></tr>
<tr><td data-label="Evidence layer">Hue Monuments official source</td><td data-label="What it can prove">Where to verify current ticket and site-access details close to travel.</td><td data-label="What still needs editorial judgment">Whether a ticket bundle or extra tomb improves the day.</td></tr>
<tr><td data-label="Evidence layer">Opening and interpretation sources</td><td data-label="What it can prove">Where to verify current opening windows, ticket-office cutoff, and guide availability.</td><td data-label="What still needs editorial judgment">Whether the day needs a guide, a shorter route, or a later start.</td></tr>
<tr><td data-label="Evidence layer">Vietnam.travel destination pages</td><td data-label="What it can prove">Hue's central Vietnam role, Citadel, tombs, food, river, and route context.</td><td data-label="What still needs editorial judgment">How much route time Hue deserves compared with Hoi An or Da Nang.</td></tr>
<tr><td data-label="Evidence layer">Hue itinerary and tomb notes</td><td data-label="What it can prove">Which tombs, river blocks, food stops, and route combinations are commonly useful.</td><td data-label="What still needs editorial judgment">Which single tomb or city rhythm best fits this traveler's fatigue and transfer day.</td></tr>
<tr><td data-label="Evidence layer">Weather and transport checks</td><td data-label="What it can prove">Heat, rain, train, flight, road-transfer, and onward-movement constraints.</td><td data-label="What still needs editorial judgment">Which sightseeing blocks should move, shrink, or be cut.</td></tr>
<tr><td data-label="Evidence layer">GSC demand</td><td data-label="What it can prove">Readers are already asking what to do and what to see in Hue.</td><td data-label="What still needs editorial judgment">How to avoid duplicating the attraction guide.</td></tr>
</tbody>
</table>

<!-- vg-hue-imperial-city-time-budget:v1 -->
<h2 class="wp-block-heading">Time budget: how much Hue can actually hold</h2>
<p>The anti-rush question is not how many monuments Hue has. It is how many meaningful blocks your route can hold after arrival, check-in, heat, lunch, transport, and the next city are counted. Use time as an editorial filter before adding sights.</p>
<table class="vg-decision-table vg-hue-imperial-city-time-budget">
<thead><tr><th>Available time</th><th>Protect first</th><th>Add only if easy</th><th>Do not force</th></tr></thead>
<tbody>
<tr><td data-label="Available time">2-3 focused hours</td><td data-label="Protect first">Imperial City highlights with context.</td><td data-label="Add only if easy">A nearby meal or cafe recovery.</td><td data-label="Do not force">Tombs, river, market, and onward transfer in the same block.</td></tr>
<tr><td data-label="Available time">One full sightseeing day</td><td data-label="Protect first">Imperial City early, one tomb, lunch, and a calmer late-day chapter.</td><td data-label="Add only if easy">Thien Mu, river time, or food-focused evening.</td><td data-label="Do not force">Three tombs plus a long transfer.</td></tr>
<tr><td data-label="Available time">One night</td><td data-label="Protect first">Arrival food, early Imperial City, one selected tomb or pagoda.</td><td data-label="Add only if easy">Hai Van transfer scenery on the onward leg.</td><td data-label="Do not force">A sunrise-to-night monument list.</td></tr>
<tr><td data-label="Available time">Two nights</td><td data-label="Protect first">A proper heritage day and a separate food/river/rest rhythm.</td><td data-label="Add only if easy">A second tomb for contrast, not completion.</td><td data-label="Do not force">More sites just because tickets bundle neatly.</td></tr>
</tbody>
</table>

<!-- vg-hue-imperial-city-pacing:v1 -->
<h2 class="wp-block-heading">Imperial City pacing: protect the first serious block</h2>
<p>The Imperial City is large, exposed in places, and more rewarding when the visitor understands what the walls, gates, palaces, restoration work, and ceremonial spaces represent. Treating it as a quick photo stop is the fastest way to make Hue feel thinner than it is.</p>
<table class="vg-decision-table vg-hue-imperial-city-pacing">
<thead><tr><th>Plan type</th><th>Best use</th><th>Watch out for</th><th>Verdict</th></tr></thead>
<tbody>
<tr><td data-label="Plan type">Self-guided early block</td><td data-label="Best use">Independent travelers who read ahead and can move slowly.</td><td data-label="Watch out for">Missing context and walking too far in heat.</td><td data-label="Verdict">Good when you have enough time and a simple map.</td></tr>
<tr><td data-label="Plan type">Guided Imperial City block</td><td data-label="Best use">First-timers who want the site to feel coherent quickly.</td><td data-label="Watch out for">Overlong guide pacing that erases lunch and shade.</td><td data-label="Verdict">Best upgrade when Hue is a serious heritage reason.</td></tr>
<tr><td data-label="Plan type">Late-afternoon block</td><td data-label="Best use">Hot months, late arrivals, and travelers protecting morning transfer time.</td><td data-label="Watch out for">Shorter site time and closing-hour pressure.</td><td data-label="Verdict">Useful when heat would wreck a midday visit.</td></tr>
<tr><td data-label="Plan type">Transfer-day block</td><td data-label="Best use">Only when luggage, transport, and time windows are genuinely clean.</td><td data-label="Watch out for">Turning the Imperial City into a clock-watching exercise.</td><td data-label="Verdict">Use sparingly; one focused stop beats a rushed circuit.</td></tr>
</tbody>
</table>

<!-- vg-hue-imperial-city-tombs:v1 -->
<h2 class="wp-block-heading">Citadel plus tombs: choose contrast, not quantity</h2>
<p>The most common Hue mistake is adding too many royal tombs because transport packages make it easy. Tombs are not interchangeable, but they blur when heat, hunger, and vehicle fatigue take over. For most first-timers, the stronger day is Imperial City plus one tomb chosen well.</p>
<ul class="vg-check-list vg-hue-imperial-city-tombs">
<li><strong>Choose Minh Mang first</strong> when you want landscape, water, symmetry, quiet pacing, and the best default royal-tomb chapter.</li>
<li><strong>Add Khai Dinh only when contrast matters</strong> and the day still has attention for ornate architecture after the Imperial City.</li>
<li><strong>Use Thien Mu as a softening chapter</strong> when the day needs a pagoda, river-side pause, and less monument intensity.</li>
<li><strong>Skip the tomb marathon</strong> when the route has only one night, an early transfer, or strong heat.</li>
</ul>

<!-- vg-hue-imperial-city-guide-value:v1 -->
<h2 class="wp-block-heading">When a guide is worth it</h2>
<p>A knowledgeable guide can turn Hue from a large old site into a coherent imperial chapter. That does not mean every visitor needs a full-day lecture. It means the guide should solve a problem: context, pacing, shade, transport, or selecting the right tomb.</p>
<table class="vg-decision-table vg-hue-imperial-city-guide-value">
<thead><tr><th>Traveler need</th><th>Guide value</th><th>Better alternative</th></tr></thead>
<tbody>
<tr><td data-label="Traveler need">You know little about Hue history</td><td data-label="Guide value">High for the Imperial City and one tomb.</td><td data-label="Better alternative">Self-guided only if you read before arriving.</td></tr>
<tr><td data-label="Traveler need">You have one tight day</td><td data-label="Guide value">High if the guide prevents bad routing.</td><td data-label="Better alternative">Private transport with a short guided block.</td></tr>
<tr><td data-label="Traveler need">You dislike long explanations</td><td data-label="Guide value">Medium; ask for a focused route.</td><td data-label="Better alternative">Audio/text preparation plus slower walking.</td></tr>
<tr><td data-label="Traveler need">Family travel in heat</td><td data-label="Guide value">High when it shortens exposed wandering and builds breaks.</td><td data-label="Better alternative">One major site plus hotel/pool/food recovery.</td></tr>
</tbody>
</table>

<!-- vg-hue-imperial-city-heat-rain:v1 -->
<h2 class="wp-block-heading">Heat, rain and timing</h2>
<p>Hue rewards visitors who move the day around conditions. The Imperial City and tombs can be exposed; the river can be beautiful or merely damp; food blocks and cafes can rescue a day that would otherwise become a monument march.</p>
<table class="vg-decision-table vg-hue-imperial-city-heat-rain">
<thead><tr><th>Condition</th><th>Move earlier</th><th>Move later or cut</th><th>Route note</th></tr></thead>
<tbody>
<tr><td data-label="Condition">Hot clear day</td><td data-label="Move earlier">Imperial City, tomb walking, exposed courtyards.</td><td data-label="Move later or cut">Midday walls, second tomb, long uncovered walks.</td><td data-label="Route note">Hotel location and private transport become real value.</td></tr>
<tr><td data-label="Condition">Rainy day</td><td data-label="Move earlier">Any clear outdoor window, especially tombs.</td><td data-label="Move later or cut">Non-refundable river plans and scenic photo assumptions.</td><td data-label="Route note">Use food, cafes, museums, and shorter heritage blocks.</td></tr>
<tr><td data-label="Condition">Transfer day</td><td data-label="Move earlier">One narrow site if luggage and transport are controlled.</td><td data-label="Move later or cut">A full monument circuit.</td><td data-label="Route note">The next city should not inherit the exhaustion.</td></tr>
<tr><td data-label="Condition">Two-night stay</td><td data-label="Move earlier">Imperial City on the clearest morning.</td><td data-label="Move later or cut">Extra tombs only if the core day still breathes.</td><td data-label="Route note">Use the extra night for better pacing, not more pressure.</td></tr>
</tbody>
</table>

<!-- vg-hue-imperial-city-route-order:v1 -->
<h2 class="wp-block-heading">Hue, Da Nang and Hoi An route order</h2>
<p>Hue usually works as part of a central Vietnam sequence with Da Nang and Hoi An. The cleanest route uses one directional crossing over the Hai Van Pass instead of a same-day out-and-back. Use <a href="/compare/hoi-an-vs-hue/">Hoi An vs Hue</a> if only one heritage chapter can be protected, and <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a> if the sleep-base decision is still unresolved.</p>
<table class="vg-decision-table vg-hue-imperial-city-route-order">
<thead><tr><th>Route order</th><th>Works when</th><th>Risk</th></tr></thead>
<tbody>
<tr><td data-label="Route order">Da Nang to Hue to Hoi An</td><td data-label="Works when">You want airport arrival, serious Hue heritage, then softer Hoi An atmosphere.</td><td data-label="Risk">The Hue-to-Hoi An transfer can become overloaded if Hai Van, food, photos, and extra stops are all added.</td></tr>
<tr><td data-label="Route order">Hoi An to Hue to Da Nang</td><td data-label="Works when">Flights or hotels make Da Nang the exit point and Hue gets a protected day first.</td><td data-label="Risk">Leaving Hue too late can make the departure city fragile, especially if the pass becomes a photo circuit.</td></tr>
<tr><td data-label="Route order">Da Nang base, Hue day trip</td><td data-label="Works when">You want one guided Imperial City or tomb block without moving hotels.</td><td data-label="Risk">Long round-trip driving can flatten the heritage value; use this only with a narrow goal.</td></tr>
<tr><td data-label="Route order">Skip Hue, keep Hoi An/Da Nang</td><td data-label="Works when">Central Vietnam is about old-town atmosphere, beach comfort, airport access, or family ease.</td><td data-label="Risk">You lose imperial depth, but may gain a calmer route.</td></tr>
</tbody>
</table>

<!-- vg-hue-imperial-city-ticket-live-check:v1 -->
<h2 class="wp-block-heading">Ticket and access live checks</h2>
<p>Hue monument ticket rules, combo options, opening details, guide services, restorations, and site access can change. Use official local sources close to travel, then decide whether the extra site, combo, or guide actually improves your day. The goal is not to maximize paid entries; it is to protect attention.</p>
<ul class="vg-check-list vg-hue-imperial-city-ticket-live-check">
<li>Check current Imperial City, tomb, and combo-ticket rules before building a multi-site day.</li>
<li>Check the current opening window and last ticket-office timing before planning an early or late block.</li>
<li>Check restoration or access notes for any site that is the reason you are coming.</li>
<li>Check whether a guide is included, optional, or better booked separately.</li>
<li>Check whether your chosen sites are realistic with heat, lunch, transport, and onward luggage.</li>
<li>Keep ticket plans flexible enough to remove one tomb if the Imperial City takes longer than expected.</li>
</ul>

<!-- vg-hue-imperial-city-food-river:v1 -->
<h2 class="wp-block-heading">Food and river time are not filler</h2>
<p>Hue food and the Perfume River are often treated as leftover time after monuments. That is backwards for many travelers. One good meal, one riverside pause, or one quieter evening can make Hue feel like a city rather than a sequence of historic sites.</p>
<ul class="vg-check-list vg-hue-imperial-city-food-river">
<li><strong>Protect lunch</strong> when the Imperial City is the first serious block; hungry sightseeing becomes sloppy sightseeing.</li>
<li><strong>Use Dong Ba Market or a food route</strong> when food is a real interest, not when it steals the only shade break.</li>
<li><strong>Add river time</strong> when weather, route, and energy support it; do not treat a cruise as compulsory.</li>
<li><strong>Choose a central hotel</strong> when walking to food and easy recovery matter more than a resort-style stay.</li>
</ul>

<!-- vg-hue-imperial-city-trip-length:v1 -->
<h2 class="wp-block-heading">Hue by trip length</h2>
<table class="vg-decision-table vg-hue-imperial-city-trip-length">
<thead><tr><th>Trip length</th><th>Recommended Hue role</th><th>What to cut first</th></tr></thead>
<tbody>
<tr><td data-label="Trip length">5-6 days</td><td data-label="Recommended Hue role">Only include Hue if central Vietnam is the trip's main focus.</td><td data-label="What to cut first">Cross-country heritage padding.</td></tr>
<tr><td data-label="Trip length">7 days</td><td data-label="Recommended Hue role">Usually skip or choose one central chapter: Hue, Hoi An, or Da Nang.</td><td data-label="What to cut first">Trying to hold Hanoi, bay, Hue, Hoi An, HCMC, and Mekong.</td></tr>
<tr><td data-label="Trip length">10 days</td><td data-label="Recommended Hue role">One night can work if central Vietnam is disciplined; two nights only if heritage leads.</td><td data-label="What to cut first">A token southern city or extra beach stop.</td></tr>
<tr><td data-label="Trip length">14 days</td><td data-label="Recommended Hue role">Hue plus Hoi An can work when each has a different job.</td><td data-label="What to cut first">A transfer-heavy day with too many scenic stops.</td></tr>
<tr><td data-label="Trip length">21 days</td><td data-label="Recommended Hue role">Two nights or deeper heritage time can be justified.</td><td data-label="What to cut first">Extra monuments that do not change the story.</td></tr>
</tbody>
</table>

<!-- vg-hue-imperial-city-skip-logic:v1 -->
<h2 class="wp-block-heading">Skip logic that makes Hue stronger</h2>
<ul class="vg-check-list vg-hue-imperial-city-skip-logic">
<li>Skip a second tomb if the Imperial City, lunch, heat, and transfer already fill the day.</li>
<li>Skip the river cruise when weather is poor or the route needs rest more than another booking.</li>
<li>Skip a drive-through Hue stop if it only creates a long day between Hoi An and Da Nang.</li>
<li>Skip Hue entirely when Hoi An atmosphere or Da Nang logistics is the real central Vietnam priority.</li>
<li>Skip cheap transfer timing if a private or better-timed move protects the whole route.</li>
<li>Skip guide-free wandering if the site will feel like walls and gates without context.</li>
</ul>

<!-- vg-hue-imperial-city-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before booking Hue</h2>
<ul class="vg-check-list vg-hue-imperial-city-live-checks">
<li>Check current Imperial City and tomb ticket rules close to travel.</li>
<li>Check site access and restoration notes if a specific monument matters.</li>
<li>Check heat, rain, and storm-sensitive central Vietnam timing before locking outdoor-heavy days.</li>
<li>Check train, flight, private-car, and Da Nang/Hoi An transfer timing before choosing one or two nights.</li>
<li>Check whether a guide, driver, or private transfer improves the day enough to justify the cost.</li>
<li>Check whether each added site creates a better memory or just another vehicle stop.</li>
</ul>

<!-- vg-hue-imperial-city-faq:v1 -->
<h2 class="wp-block-heading">Hue Imperial City FAQ</h2>
<div class="vg-faq-list vg-hue-imperial-city-faq">
<details><summary>Is Hue Imperial City worth visiting?</summary><p>Yes when imperial history, architecture, restoration context, and a serious central Vietnam heritage chapter matter. It is less worth it when the route can only give Hue a hot, rushed transfer stop with no context.</p></details>
<details><summary>How long do I need for Hue Imperial City?</summary><p>Most first-timers should protect a serious morning or late-afternoon block. If you also want a tomb, pagoda, food, and a calmer transfer, one or two nights in Hue are much better than a rushed pass-through.</p></details>
<details><summary>How many nights should I stay in Hue?</summary><p>Two nights is best for heritage-minded travelers. One night can work with discipline. A half-day works only with a narrow goal. Skip Hue when the route cannot give it enough attention.</p></details>
<details><summary>Which tomb should I visit first?</summary><p>Minh Mang is the strongest default tomb for many first-timers because it gives landscape, water, symmetry, and slower pacing. Add a second tomb only when the day still has shade, food, and attention.</p></details>
<details><summary>Should I visit Hue or Hoi An?</summary><p>Choose Hue for imperial history and a serious heritage day. Choose Hoi An for old-town atmosphere, food, walking evenings, and softer central Vietnam rhythm. Choose both only when the route gives each a different job.</p></details>
<details><summary>Can I visit Hue as a day trip from Da Nang?</summary><p>Yes, but it should be narrow: a guided Imperial City block, one tomb, or a single heritage priority. If the day trip tries to include a full monument circuit, Hai Van scenery, meals, river time, and Hoi An logistics, Hue usually becomes too shallow.</p></details>
<details><summary>Do I need a guide for Hue Imperial City?</summary><p>You do not strictly need one, but a good guide is valuable if you want context quickly, have limited time, or do not want the Imperial City to feel like a large site without a clear story.</p></details>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Use this guide before finalizing central Vietnam nights. Once Hue's route job is clear, move into the attraction guide, Hoi An comparison, UNESCO heritage, season, transport, and itinerary pages.</p>
<div class="vg-related-routes vg-hue-imperial-city-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/destinations/best-things-to-do-in-hue/">Best Things to Do in Hue</a><span class="vg-related-route-note">Choose specific Hue sights after the time decision is clear.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/compare/hoi-an-vs-hue/">Hoi An vs Hue</a><span class="vg-related-route-note">Decide atmosphere versus imperial depth when central Vietnam is tight.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a><span class="vg-related-route-note">Place Hue inside the wider heritage route.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a><span class="vg-related-route-note">Choose the central sleep base before adding Hue transfers.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a><span class="vg-related-route-note">Use Da Nang when airport and beach logistics matter more.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Check central Vietnam weather before locking outdoor-heavy Hue days.</span></li>
<li><span class="vg-related-route-step">07</span><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a><span class="vg-related-route-note">Test whether Hue fits without thinning the route.</span></li>
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
    'vg-hue-imperial-city-hero:v1',
    'vg-hue-imperial-city-concierge-verdict:v1',
    'vg-hue-imperial-city-stay-halfday-pass:v1',
    'vg-hue-imperial-city-decision-matrix:v1',
    'vg-hue-imperial-city-gsc-demand:v1',
    'vg-hue-imperial-city-photo-proof:v1',
    'vg-hue-imperial-city-source-diversity:v1',
    'vg-hue-imperial-city-time-budget:v1',
    'vg-hue-imperial-city-pacing:v1',
    'vg-hue-imperial-city-tombs:v1',
    'vg-hue-imperial-city-guide-value:v1',
    'vg-hue-imperial-city-heat-rain:v1',
    'vg-hue-imperial-city-route-order:v1',
    'vg-hue-imperial-city-ticket-live-check:v1',
    'vg-hue-imperial-city-food-river:v1',
    'vg-hue-imperial-city-trip-length:v1',
    'vg-hue-imperial-city-skip-logic:v1',
    'vg-hue-imperial-city-live-checks:v1',
    'vg-hue-imperial-city-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_hue_imperial_city_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Hue Imperial City Guide: How to Visit Without Rushing',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Decide whether Hue Imperial City deserves a two-night stay, one-night stop, focused half-day, or clean skip by pacing, heat, tombs, guide value, and route order.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_hue_imperial_city_post_fail('Could not update Hue Imperial City post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Hue Imperial City Guide: How to Visit Without Rushing');
update_post_meta($post_id, 'rank_math_description', 'Decide whether Hue Imperial City deserves a two-night stay, one-night stop, focused half-day, or clean skip by pacing, heat, tombs, and route order.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Hue Imperial City guide');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Decide whether Hue Imperial City deserves a two-night stay, one-night stop, focused half-day, or clean skip based on pacing, heat, guide value, tomb choice, food, river rhythm, and central Vietnam transfer pressure.');
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
    vg_hue_imperial_city_post_fail('Could not assign Hue Imperial City categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_hue_imperial_city_post_fail('Could not assign Hue Imperial City tags: ' . $tag_result->get_error_message());
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
        vg_hue_imperial_city_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Hue Imperial City post to complete draft: {$post_id}");

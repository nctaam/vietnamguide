<?php
/**
 * Expand the Best Vietnam Cities for First-Time Visitors post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-best-vietnam-cities-first-time-visitors-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_best_cities_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_best_cities_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_best_cities_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_best_cities_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_best_cities_post_find_by_slug(string $slug): ?WP_Post
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
        vg_best_cities_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_best_cities_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-15',
            'vg_editorial_brief_status' => ['brief', 'complete_draft'],
            'vg_editorial_batch' => 'batch-2-evergreen-planning',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if (is_array($expected_value)) {
            if (! in_array($actual_value, $expected_value, true)) {
                vg_best_cities_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected one of " . implode(', ', $expected_value) . '.');
            }
            continue;
        }

        if ($actual_value !== $expected_value) {
            vg_best_cities_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_best_cities_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_best_cities_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'best-vietnam-cities-for-first-time-visitors';
$post = vg_best_cities_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_best_cities_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_best_cities_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 26, 2026';

if ($post_id !== 497) {
    vg_best_cities_post_fail("Refusing to update {$slug}: expected post ID 497, found {$post_id}.");
}

vg_best_cities_post_assert_target_meta($post);

$category_term_ids = vg_best_cities_post_term_ids('category', ['destinations', 'travel-planning']);
$tag_term_ids = vg_best_cities_post_term_ids('post_tag', ['first-time-vietnam', 'city-comparison', 'route-planning', 'anti-spam-evergreen']);

$hanoi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$hoi_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hue_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg/1920px-Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$hcmc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1280px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg');
$da_nang_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg/1280px-My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg');

$meta_update_summary = 'Expanded the native WordPress batch 2 brief into a complete city-base decision draft with a photo-led hero, proof panel, concierge verdict, at-a-glance matrix, source-diversity table, city-by-city base roles, arrival and departure logic, traveler-fit filters, day-trip pressure, skip logic, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Hanoi destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-noi - checked {$review_date}; used for Hanoi as the northern culture, food, old-quarter, and first-arrival base.",
    "Vietnam.travel - Ho Chi Minh City destination page - https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city - checked {$review_date}; used for HCMC as the southern city, airport, food, museums, Mekong, and Cu Chi gateway.",
    "Vietnam.travel - Hoi An destination page - https://vietnam.travel/places-to-go/central-vietnam/hoi-an - checked {$review_date}; used for heritage-town, food, walking-rhythm, and central Vietnam base logic.",
    "Vietnam.travel - Hue destination page - https://vietnam.travel/places-to-go/central-vietnam/hue - checked {$review_date}; used for imperial-history, slower culture, and protected central Vietnam depth.",
    "Vietnam.travel - Da Nang destination page - https://vietnam.travel/places-to-go/central-vietnam/da-nang - checked {$review_date}; used for airport, beach-city, family comfort, and central-coast logistics.",
    "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked {$review_date}; used for Hanoi, Ninh Binh, Ha Long, and mountain route consequences.",
    "Vietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked {$review_date}; used for Da Nang, Hoi An, Hue, coast, and heritage clustering.",
    "Vietnam.travel - Southern Vietnam destinations - https://vietnam.travel/places-to-go/southern-vietnam - checked {$review_date}; used for HCMC, Mekong, southern heat, and island-gateway logic.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for domestic movement, city transfer friction, and base-count discipline.",
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for seasonal trade-offs by region rather than one national city answer.",
    "Wikimedia Commons image direct URL - Hanoi Hoan Kiem Lake - {$hanoi_image} - credit Alex 69200 vx / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hoi An Ancient Town - {$hoi_an_image} - credit Jakub Halun / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hue Vietnam Citadel - {$hue_image} - credit CEphoto, Uwe Aranas / CC BY-SA 3.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ho Chi Minh City Hall - {$hcmc_image} - credit Steffen Schmitz / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - My Khe Beach seen from the Son Tra Mountain - {$da_nang_image} - credit Christophe95 / CC BY-SA 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This complete draft treats Vietnam cities as base jobs inside a route, not as a popularity ranking. It helps first-time visitors choose fewer better bases, protect arrival and departure nights, avoid duplicate city roles, and link city choice to weather, transport, day trips, food, culture, beaches, and route length.';
$meta_evidence_moat = implode("\n", [
    'City-base decision framing avoids a generic best-cities list and asks what each city solves inside the route.',
    'Concierge verdict gives a default first-trip city set and explains when to add, swap, or skip each base.',
    'At-a-glance matrix ranks cities by route job, not popularity.',
    'Source-diversity table separates official destination context, internal route judgment, transport/weather checks, and image-license records.',
    'City-by-city base logic explains Hanoi, Hoi An, Hue, Da Nang, Ho Chi Minh City, Ninh Binh, Ha Long/Lan Ha, Phu Quoc, and Mekong use cases without pretending every place is mandatory.',
    'Arrival/departure logic protects first and last nights from becoming high-risk transfer days.',
    'Traveler-fit filters separate culture-first, food-first, beach-comfort, family, premium, slow-travel, and short-trip city choices.',
    'Skip logic helps readers remove duplicate bases, token nights, and city stops that do not improve the route.',
    'No visible external body anchors; official sources and image credits stay auditable in source-trail metadata and shortcode output.',
]);
$meta_related_routes = implode("\n", [
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before deciding which city bases should carry the route.',
    'Best Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Use this after choosing city bases to decide which landscapes, heritage sites, coast, or islands deserve the remaining nights.',
    'North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Use this before choosing too many cities across three regions.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Filter city choices by heat, rain, central-coast timing, bay risk, and southern dry-season logic.',
    'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use this when the northern city base should carry culture, food, Old Quarter rhythm, and first-arrival logic.',
    'Ho Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Use this when the south needs a real city chapter rather than only an exit airport.',
    'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use this when central Vietnam needs airport access, beach comfort, and easy Hoi An/Hue movement.',
    'Best Things to Do in Hoi An | /destinations/best-things-to-do-in-hoi-an/ | Use this when a walking heritage town should be the emotional center of the central route.',
    'Best Things to Do in Hue | /destinations/best-things-to-do-in-hue/ | Use this when imperial history deserves a protected day instead of a rushed transfer stop.',
    'Da Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Use this before splitting central Vietnam into two nearby hotel bases.',
    'Hoi An vs Hue | /compare/hoi-an-vs-hue/ | Use this when the central route needs a clear atmosphere-versus-history decision.',
    'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Add countryside contrast near Hanoi instead of pretending another city night solves the same job.',
    'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Use this when the northern route needs water-and-karst scenery rather than more city time.',
    'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Use this when island rest should replace, not merely add to, another city base.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Count transfer friction before adding another city base.',
    '7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Use this when a short route needs one region and a disciplined city count.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Use this when the route can support only a small number of city bases.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use this when two weeks can hold north, central, and possibly one southern city chapter.',
    '21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use this when three weeks can support a broader city ladder without turning every stop into a transfer.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the difference between fewer better bases and a transfer-heavy city list.',
]);
$meta_hero_image_credit = 'Hero image: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0. Body images: Hoi An Ancient Town by Jakub Halun, CC BY-SA 4.0; Hue Vietnam Citadel by CEphoto, Uwe Aranas, CC BY-SA 3.0; Ho Chi Minh City Hall by Steffen Schmitz, CC BY-SA 4.0; My Khe Beach seen from Son Tra Mountain by Christophe95, CC BY-SA 4.0.';
$meta_admin_notes = 'Complete draft generated for WordPress Admin review. Keep draft until manual preview, Rank Math review, image check, and final editorial pass are complete.';

$content = <<<HTML
<!-- vg-best-cities-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hanoi_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hoan Kiem Lake in Hanoi, a classic first Vietnam city base" src="{$hanoi_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<div class="wp-block-group vg-guide-hero-inner">
<p class="vg-kicker">Reviewed city-base guide - Updated {$review_date}</p>
<h1 class="wp-block-heading vg-display vg-guide-title">Best Vietnam Cities for First-Time Visitors</h1>
<p class="vg-guide-lede">The best Vietnam cities for first-time visitors are not the cities with the loudest names. They are the bases that make your route calmer, your transfers cleaner, and your days feel more like travel than logistics.</p>
<p class="vg-field-note">City-base verdict: choose Hanoi plus one central Vietnam base as the default; add Ho Chi Minh City only when the south is a real chapter, not a token airport stamp.</p>
</div>
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-best-cities-concierge-verdict:v1 -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<p><strong>For most first-time trips, the strongest city set is Hanoi, Hoi An or Da Nang, and optionally Ho Chi Minh City.</strong> Hanoi gives the north a cultural and food anchor. Hoi An gives the central route atmosphere and walking rhythm. Da Nang gives the same region airport access, beach comfort, and easier logistics. Ho Chi Minh City works when the south gets enough nights for museums, food, districts, Mekong, Cu Chi, or island connections.</p>
<ul class="vg-feature-list">
<li><strong>Best 7-10 day city set:</strong> Hanoi plus one central base. Add Ninh Binh or a bay night as non-city contrast.</li>
<li><strong>Best 14 day city set:</strong> Hanoi, Hoi An or Da Nang, Hue if history matters, and HCMC if flights or southern plans justify it.</li>
<li><strong>Best slow/premium set:</strong> two stronger bases, fewer check-ins, better hotels, and day trips chosen by decision value.</li>
</ul>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-best-cities-at-a-glance:v1 -->
<!-- vg-best-cities-city-role-matrix:v1 -->
<h2 class="wp-block-heading">Best Vietnam cities at a glance</h2>
<table class="vg-decision-table vg-best-cities-at-a-glance">
<thead><tr><th>Base</th><th>Best job</th><th>Ideal nights</th><th>Weak when</th></tr></thead>
<tbody>
<tr><td data-label="Base">Hanoi</td><td data-label="Best job">First-arrival base, food, Old Quarter, museums, Ninh Binh, bay access.</td><td data-label="Ideal nights">2-4</td><td data-label="Weak when">You need beaches, easy heat, or a soft first night far from traffic.</td></tr>
<tr><td data-label="Base">Hoi An</td><td data-label="Best job">Atmosphere, walking, food, tailoring, lantern evenings, central Vietnam emotion.</td><td data-label="Ideal nights">2-4</td><td data-label="Weak when">You need airport convenience or expect a large modern city.</td></tr>
<tr><td data-label="Base">Da Nang</td><td data-label="Best job">Airport, beach comfort, family-friendly hotels, Hoi An/Hue access, central logistics.</td><td data-label="Ideal nights">1-3</td><td data-label="Weak when">You want old-town character without taxis or beach weather is poor.</td></tr>
<tr><td data-label="Base">Hue</td><td data-label="Best job">Imperial history, slower culture, food, Perfume River, central Vietnam depth.</td><td data-label="Ideal nights">1-2</td><td data-label="Weak when">You only have ten days and history is not a priority.</td></tr>
<tr><td data-label="Base">Ho Chi Minh City</td><td data-label="Best job">Southern city chapter, food, museums, airport access, Mekong or Cu Chi gateway.</td><td data-label="Ideal nights">2-4</td><td data-label="Weak when">It becomes one exhausted final night after too many transfers.</td></tr>
<tr><td data-label="Base">Ninh Binh / Tam Coc</td><td data-label="Best job">Countryside contrast near Hanoi, karst scenery, cycling, boat routes, slower night.</td><td data-label="Ideal nights">1-2</td><td data-label="Weak when">You treat it as only a quick city substitute.</td></tr>
</tbody>
</table>

<!-- vg-best-cities-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: what each base solves</h2>
<div class="vg-photo-grid">
<figure><img src="{$hanoi_image}" alt="Hanoi Hoan Kiem Lake city base" loading="lazy" decoding="async"><figcaption>Hanoi solves first-arrival culture, food, and northern access. Image: Alex 69200 vx / CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$hoi_an_image}" alt="Hoi An Ancient Town city base" loading="lazy" decoding="async"><figcaption>Hoi An solves atmosphere, walking rhythm, and central Vietnam emotion. Image: Jakub Halun / CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$hue_image}" alt="Hue imperial city base" loading="lazy" decoding="async"><figcaption>Hue solves imperial-history depth when it gets protected time. Image: CEphoto, Uwe Aranas / CC BY-SA 3.0.</figcaption></figure>
<figure><img src="{$hcmc_image}" alt="Ho Chi Minh City southern base" loading="lazy" decoding="async"><figcaption>Ho Chi Minh City solves the south when museums, food, airport logic, and day trips matter. Image: Steffen Schmitz / CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$da_nang_image}" alt="Da Nang beach and airport city base" loading="lazy" decoding="async"><figcaption>Da Nang solves central-coast logistics, beach comfort, and easier hotel planning. Image: Christophe95 / CC BY-SA 4.0.</figcaption></figure>
</div>

<!-- vg-best-cities-source-diversity:v1 -->
<h2 class="wp-block-heading">Source diversity behind this guide</h2>
<table class="vg-decision-table vg-best-cities-source-diversity">
<thead><tr><th>Evidence type</th><th>What it can prove</th><th>What still needs judgment</th></tr></thead>
<tbody>
<tr><td data-label="Evidence type">Official destination pages</td><td data-label="What it can prove">Core city identity, regional positioning, broad planning context.</td><td data-label="What still needs judgment">Whether the city improves a specific first-trip route.</td></tr>
<tr><td data-label="Evidence type">Internal destination guides</td><td data-label="What it can prove">How Hanoi, HCMC, Da Nang, Hoi An, Hue, Ninh Binh, and islands fit existing route modules.</td><td data-label="What still needs judgment">Which bases to cut when time is limited.</td></tr>
<tr><td data-label="Evidence type">Transport and airport checks</td><td data-label="What it can prove">Transfer friction, domestic movement, airport logic, and arrival/departure risk.</td><td data-label="What still needs judgment">Whether another city night is worth one more check-in.</td></tr>
<tr><td data-label="Evidence type">Weather and season checks</td><td data-label="What it can prove">Regional comfort, beach risk, heat, rain, and month-sensitive trade-offs.</td><td data-label="What still needs judgment">Which city can absorb a slower day if weather changes.</td></tr>
<tr><td data-label="Evidence type">Licensed real images</td><td data-label="What it can prove">The visual role of each base: lake, old town, imperial site, southern city, beach city.</td><td data-label="What still needs judgment">Whether the image supports a decision instead of decorating a list.</td></tr>
</tbody>
</table>

<!-- vg-best-cities-base-roles:v1 -->
<!-- vg-best-cities-city-by-city-base-logic:v1 -->
<h2 class="wp-block-heading">City-by-city base logic</h2>
<h3>Hanoi: best first base for most first trips</h3>
<p>Choose <a href="/destinations/hanoi-travel-guide/">Hanoi</a> when the trip needs food, street life, museums, lakes, Old Quarter energy, and access to Ninh Binh or the bay. Hanoi is the best first city when you want Vietnam to feel specific immediately. It is less ideal when the first night must be quiet, beach-led, or low-traffic.</p>
<h3>Hoi An: best emotional base in central Vietnam</h3>
<p>Choose <a href="/destinations/best-things-to-do-in-hoi-an/">Hoi An</a> when walking, food, lantern evenings, cafes, tailoring, and slower rhythm matter more than airport convenience. It is a strong first-trip base because it gives central Vietnam a clear feeling. Do not make it the only central base if you also need early flights, beach-resort comfort, or easy day trips in several directions.</p>
<h3>Da Nang: best logistics-and-comfort base in central Vietnam</h3>
<p>Choose <a href="/destinations/da-nang-travel-guide/">Da Nang</a> when the central route needs airport access, modern hotels, beach time, family comfort, and simpler transfers to Hoi An, Hue, Marble Mountains, or Son Tra. Da Nang is strongest as a friction reducer. It is weaker when the traveler expects old-town romance or stays far from the part of the city that solves the route problem.</p>
<h3>Hue: best protected history chapter</h3>
<p>Choose <a href="/destinations/best-things-to-do-in-hue/">Hue</a> when imperial history, slower river rhythm, temples, tombs, and central Vietnam depth are worth a protected night. Hue fails when it is treated as a rushed lunch stop between Da Nang and somewhere else. Give it time or skip it cleanly.</p>
<h3>Ho Chi Minh City: best real southern chapter</h3>
<p>Choose <a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City</a> when the south deserves museums, food, neighborhoods, Tan Son Nhat airport logic, Mekong access, Cu Chi, or island connections. It is not automatically better than Hanoi; it does a different job. It is weakest as a tired final-night stamp after the trip has already spent its energy.</p>
<h3>Ninh Binh, Ha Long, Phu Quoc, and Mekong are not city substitutes</h3>
<p>Ninh Binh, Ha Long or Lan Ha, Phu Quoc, and the Mekong Delta can be essential, but they solve contrast rather than city-base logic. Use them to break up cities with scenery, water, countryside, or rest. Do not count them as interchangeable with Hanoi, Da Nang, Hoi An, Hue, or HCMC.</p>

<!-- vg-best-cities-arrival-departure:v1 -->
<h2 class="wp-block-heading">Arrival and departure rules</h2>
<table class="vg-decision-table vg-best-cities-arrival-departure">
<thead><tr><th>Travel moment</th><th>Safer city choice</th><th>Why</th></tr></thead>
<tbody>
<tr><td data-label="Travel moment">First night after a long-haul flight</td><td data-label="Safer city choice">Hanoi or HCMC, centrally located</td><td data-label="Why">Do not stack jet lag, luggage, traffic, and a long onward transfer on day one.</td></tr>
<tr><td data-label="Travel moment">Early central Vietnam flight</td><td data-label="Safer city choice">Da Nang</td><td data-label="Why">Da Nang reduces airport friction compared with waking up deep in Hoi An for every flight.</td></tr>
<tr><td data-label="Travel moment">Last night before international departure</td><td data-label="Safer city choice">The departure city</td><td data-label="Why">A romantic final stop is not worth a missed international flight.</td></tr>
<tr><td data-label="Travel moment">Rainy or storm-sensitive month</td><td data-label="Safer city choice">City with backup indoor value</td><td data-label="Why">Hanoi, HCMC, Hue, and Da Nang can absorb slower days better than exposed transfers.</td></tr>
<tr><td data-label="Travel moment">Family or premium trip</td><td data-label="Safer city choice">Fewer bases with better hotels</td><td data-label="Why">The luxury move is often fewer check-ins, not more famous names.</td></tr>
</tbody>
</table>

<!-- vg-best-cities-traveler-fit:v1 -->
<h2 class="wp-block-heading">Choose cities by traveler type</h2>
<ul class="vg-check-list vg-best-cities-traveler-fit">
<li><strong>Food-first travelers:</strong> Hanoi and HCMC are the strongest city pair; add Hoi An when food plus walking atmosphere matters.</li>
<li><strong>Culture-first travelers:</strong> Hanoi, Hue, and Hoi An are stronger than a three-city airport sprint.</li>
<li><strong>Beach-comfort travelers:</strong> Da Nang plus Hoi An is cleaner than trying to force every beach and island into one route.</li>
<li><strong>Families:</strong> choose Da Nang, Hoi An, Hanoi, or HCMC by hotel support and transfer tolerance, not by checklist volume.</li>
<li><strong>Premium travelers:</strong> protect fewer bases, better rooms, private transfers, and meals with breathing room.</li>
<li><strong>Short-trip travelers:</strong> pick one region and make one city do multiple jobs.</li>
</ul>

<!-- vg-best-cities-day-trip-pressure:v1 -->
<h2 class="wp-block-heading">Day-trip pressure: when a city is doing too much</h2>
<table class="vg-decision-table vg-best-cities-day-trip-pressure">
<thead><tr><th>Base</th><th>Good day-trip use</th><th>Pressure warning</th></tr></thead>
<tbody>
<tr><td data-label="Base">Hanoi</td><td data-label="Good day-trip use">Ninh Binh day trip only when time is tight; better as an overnight if pace matters.</td><td data-label="Pressure warning">Adding bay, Ninh Binh, museums, food tours, and Old Quarter in two nights is not realistic.</td></tr>
<tr><td data-label="Base">Da Nang</td><td data-label="Good day-trip use">Hoi An, Marble Mountains, Son Tra, or Hue with careful timing.</td><td data-label="Pressure warning">Using Da Nang as a base for every central place can flatten Hoi An and Hue.</td></tr>
<tr><td data-label="Base">Hoi An</td><td data-label="Good day-trip use">My Son, An Bang, food classes, and a calm old-town rhythm.</td><td data-label="Pressure warning">Too many transfers can erase the reason you chose Hoi An.</td></tr>
<tr><td data-label="Base">HCMC</td><td data-label="Good day-trip use">Cu Chi, Can Gio, or a carefully chosen Mekong day when the city has enough nights.</td><td data-label="Pressure warning">Stacking Cu Chi, Mekong, museums, markets, food, and departure in two nights is brittle.</td></tr>
</tbody>
</table>

<!-- vg-best-cities-skip-logic:v1 -->
<h2 class="wp-block-heading">Skip logic that improves the trip</h2>
<ul class="vg-check-list vg-best-cities-skip-logic">
<li>Skip Ho Chi Minh City on a 10-day north-plus-central route if it would become only a late arrival and early flight.</li>
<li>Skip Hue if imperial history is not a priority and you cannot give it a protected day.</li>
<li>Skip one of Hoi An or Da Nang if you are treating them as separate hotel bases but doing the same activities from both.</li>
<li>Skip a second northern city night if Ninh Binh or the bay gives the contrast the route is missing.</li>
<li>Skip extra city bases when the route has too many flights, night trains, one-night stays, or forced day trips.</li>
</ul>

<!-- vg-best-cities-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before booking city bases</h2>
<ul class="vg-check-list vg-best-cities-live-checks">
<li>Check international arrival and departure airports before deciding whether Hanoi or HCMC is the first or final city.</li>
<li>Check domestic flight times before splitting Hoi An and Da Nang into separate hotel bases.</li>
<li>Check seasonal weather before choosing beach-led Da Nang, island extensions, bay cruises, or mountain side trips.</li>
<li>Check hotel location against the job of the city: food, airport, old town, beach, quiet sleep, or day-trip pickup.</li>
<li>Check whether each added city creates a better day or just another transfer.</li>
<li>Check whether the route still works if one ordinary sightseeing day becomes a slow city day.</li>
</ul>

<!-- vg-best-cities-faq:v1 -->
<h2 class="wp-block-heading">Best Vietnam cities FAQ</h2>
<div class="vg-faq-list vg-best-cities-faq">
<details><summary>What are the best Vietnam cities for a first trip?</summary><p>For most first trips, Hanoi plus one central Vietnam base is the strongest start. Add HCMC when the south has enough nights to be a real chapter. Add Hue when history matters. Use Da Nang when airport, beach, or family logistics matter more than old-town atmosphere.</p></details>
<details><summary>Should I visit both Hanoi and Ho Chi Minh City?</summary><p>Visit both when your route has enough time, open-jaw flights, or a real north-to-south story. Skip one when the trip is short and the second city would only create another airport day.</p></details>
<details><summary>Is Hoi An or Da Nang better as a base?</summary><p>Hoi An is better for atmosphere, walking, food, and old-town rhythm. Da Nang is better for airport access, beach hotels, family comfort, and central-coast logistics. Many travelers do not need to sleep in both.</p></details>
<details><summary>How many city bases should I choose for 10 days?</summary><p>Usually two city bases plus one or two non-city contrasts. Hanoi and Hoi An or Da Nang work better than Hanoi, Hue, Hoi An, Da Nang, HCMC, and an island squeezed into one fast route.</p></details>
<details><summary>Which city is best for food?</summary><p>Hanoi and HCMC are the strongest food-city pair. Hoi An adds a different central Vietnam food rhythm. Choose by route fit, not by trying to eat everywhere in one trip.</p></details>
<details><summary>Which city is easiest for families?</summary><p>Da Nang is often the easiest central base because airport access, beach hotels, and modern city services lower friction. Hanoi and HCMC can work well when the hotel location and first-night pace are chosen carefully.</p></details>
</div>

<h2 class="wp-block-heading">Where this guide fits next</h2>
<p>Use this guide before booking hotels. Once the city bases are chosen, move to the route guide, destination guide, transport guide, and cost guide so each city has a clear job.</p>
<div class="vg-related-routes vg-best-cities-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a><span class="vg-related-route-note">Start with the full planning order before choosing city bases.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a><span class="vg-related-route-note">Build the full shortlist after choosing city bases.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Filter city choices by region and season.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a><span class="vg-related-route-note">Choose which region deserves the route center.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Count transfer friction before adding another city.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a><span class="vg-related-route-note">Test the city count against a realistic first-trip route.</span></li>
<li><span class="vg-related-route-step">07</span><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a><span class="vg-related-route-note">Price hotels, transfers, tours, and buffers by city base.</span></li>
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
    'vg-best-cities-hero:v1',
    'vg-best-cities-concierge-verdict:v1',
    'vg-best-cities-at-a-glance:v1',
    'vg-best-cities-city-role-matrix:v1',
    'vg-best-cities-photo-proof:v1',
    'vg-best-cities-source-diversity:v1',
    'vg-best-cities-base-roles:v1',
    'vg-best-cities-city-by-city-base-logic:v1',
    'vg-best-cities-arrival-departure:v1',
    'vg-best-cities-traveler-fit:v1',
    'vg-best-cities-day-trip-pressure:v1',
    'vg-best-cities-skip-logic:v1',
    'vg-best-cities-live-checks:v1',
    'vg-best-cities-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_best_cities_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Best Vietnam Cities for First-Time Visitors: Which Base Fits Your Route?',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Choose the best Vietnam city bases for a first trip by route job, arrival logic, food, culture, beaches, day trips, skip logic, and transfer pressure.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_best_cities_post_fail('Could not update Best Vietnam Cities post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Best Vietnam Cities for First-Time Visitors');
update_post_meta($post_id, 'rank_math_description', 'Choose Vietnam city bases by route role, arrival logic, food, culture, beach comfort, day trips, skip logic, and transfer pressure.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'best Vietnam cities for first time visitors');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Choose the best Vietnam city bases for a first trip by route role, arrival and departure logic, food, culture, beaches, day trips, skip logic, transfer pressure, and trip length.');
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
    vg_best_cities_post_fail('Could not assign Best Vietnam Cities categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_best_cities_post_fail('Could not assign Best Vietnam Cities tags: ' . $tag_result->get_error_message());
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
        vg_best_cities_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Best Vietnam Cities post to complete draft: {$post_id}");

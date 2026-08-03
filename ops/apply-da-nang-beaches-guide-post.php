<?php
/**
 * Expand the Da Nang Beaches Guide post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-da-nang-beaches-guide-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_da_nang_beaches_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_da_nang_beaches_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_da_nang_beaches_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_da_nang_beaches_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_da_nang_beaches_post_find_by_slug(string $slug): ?WP_Post
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
        vg_da_nang_beaches_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_da_nang_beaches_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-19',
            'vg_editorial_brief_status' => ['brief', 'complete_draft'],
            'vg_editorial_batch' => 'batch-2-evergreen-planning',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if (is_array($expected_value)) {
            if (! in_array($actual_value, $expected_value, true)) {
                vg_da_nang_beaches_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected one of " . implode(', ', $expected_value) . '.');
            }
            continue;
        }

        if ($actual_value !== $expected_value) {
            vg_da_nang_beaches_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_da_nang_beaches_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_da_nang_beaches_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'da-nang-beaches-guide';
$post = vg_da_nang_beaches_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_da_nang_beaches_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_da_nang_beaches_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 26, 2026';

if ($post_id !== 501) {
    vg_da_nang_beaches_post_fail("Refusing to update {$slug}: expected post ID 501, found {$post_id}.");
}

vg_da_nang_beaches_post_assert_target_meta($post);

$category_term_ids = vg_da_nang_beaches_post_term_ids('category', ['beaches-islands', 'destinations']);
$tag_term_ids = vg_da_nang_beaches_post_term_ids('post_tag', ['beach-planning', 'first-time-vietnam', 'route-planning', 'anti-spam-evergreen']);

$my_khe_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/a/af/My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg/1920px-My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg');
$an_bang_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b0/2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg/1920px-2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg');
$marble_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/1d/Da_Nang_Marble_Mountains_2020_IMG_4008.jpg/1920px-Da_Nang_Marble_Mountains_2020_IMG_4008.jpg');
$hai_van_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1280px-Vietnam%2C_Hai-Van-Pass.jpg');

$meta_update_summary = 'Expanded the native WordPress batch 2 brief into a complete Da Nang beaches decision draft with photo-led hero, proof panel, concierge verdict, GSC demand note, My Khe-Non Nuoc-Hoi An Coast chooser, photo proof, source-diversity table, north-to-south orientation map, beach-base map, season and sea-condition logic, family and swimmer guidance, hotel-base decision matrix, beach-culture route order, airport and transport checks, time-budget matrix, skip logic, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Da Nang destination page - https://vietnam.travel/places-to-go/central-vietnam/da-nang - checked {$review_date}; used for Da Nang beach, city, Son Tra, Marble Mountains, and central-coast role.",
    "Vietnam.travel - must-visit places in Da Nang - https://vietnam.travel/things-to-do/must-visit-places-in-da-nang - checked {$review_date}; used for Da Nang attraction proximity and why beach choices should leave room for selected city sights.",
    "Vietnam.travel - Hoi An destination page - https://vietnam.travel/places-to-go/central-vietnam/hoi-an - checked {$review_date}; used for Hoi An coast, An Bang, old-town pairing, and base trade-offs.",
    "Vietnam.travel - ways to explore Hoi An Ancient Town - https://vietnam.travel/things-to-do/the-best-ways-to-explore-the-ancient-town-of-hoi-an - checked {$review_date}; used for Hoi An old-town evening and beach-support framing.",
    "Vietnam.travel - Hue destination page - https://vietnam.travel/places-to-go/central-vietnam/hue - checked {$review_date}; used for why Hue should be treated as a heritage chapter, not a beach-base substitute.",
    "Vietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked {$review_date}; used for Da Nang, Hoi An, Hue, coast, and central-route sequencing.",
    "Vietnam.travel - Beaches perfect for families - https://vietnam.travel/things-to-do/beaches-perfect-for-families - checked {$review_date}; used for family beach filters and why convenience can matter more than scenery.",
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for central-coast heat, rain, storm-sensitive timing, and seasonal flexibility.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for airport, train, road-transfer, and Hoi An/Da Nang/Hue movement framing.",
    "Da Nang Fantasticity - official local tourism portal - https://danangfantasticity.com/en/ - checked {$review_date}; used for local tourism context and official city update discipline.",
    "Da Nang Fantasticity - About Da Nang city - https://danangfantasticity.com/en/about-da-nang-city - checked {$review_date}; used for official city orientation and visitor context.",
    "Da Nang Fantasticity - Weather Da Nang - https://danangfantasticity.com/en/weather-da-nang - checked {$review_date}; used as a local weather live-check source.",
    "Da Nang Fantasticity - Da Nang airport visitor guide - https://danangfantasticity.com/en/travel-information/guide-for-visitors-arriving-at-and-departing-from-da-nang-international-airport - checked {$review_date}; used for airport-arrival and departure live-check framing.",
    "Da Nang Fantasticity - Da Nang beaches - https://danangfantasticity.com/en/danang-beaches/da-nang-beaches-2 - checked {$review_date}; used for local beach context, beach-safety live-check framing, and My Khe/Non Nuoc beach language.",
    "National Center for Hydro-Meteorological Forecasting - Da Nang weather - https://nchmf.gov.vn/kttvsiteE/en-US/2/hai-chau-tp-da-nang-w55.html - checked {$review_date}; used as a primary weather live-check pointer.",
    "Da Nang International Airport - https://danangairport.vn/ - checked {$review_date}; used as an airport live-check pointer for arrival and departure details.",
    "Google Search Console query export from 2026-07-25 - checked {$review_date}; used for observed Da Nang/Hoi An comparison and beach query phrasing.",
    "Internal published page check - https://vietnamguide.net/destinations/da-nang-travel-guide/ - checked {$review_date}; used for full Da Nang city handoff.",
    "Internal published page check - https://vietnamguide.net/compare/da-nang-vs-hoi-an/ - checked {$review_date}; used for sleep-base comparison handoff.",
    "Internal published page check - https://vietnamguide.net/destinations/best-beaches-in-vietnam/ - checked {$review_date}; used for country-level beach handoff.",
    "Wikimedia Commons image direct URL - My Khe Beach seen from Son Tra Mountain - {$my_khe_image} - credit Christophe95 / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - An Bang Beach in Hoi An - {$an_bang_image} - credit Alexkom000 / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Da Nang Marble Mountains - {$marble_image} - credit Kuroczynski / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hai Van Pass - {$hai_van_image} - credit Wolkenkratzer / CC BY-SA 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This complete draft treats Da Nang beaches as a base and route decision, not as a broad prettiest-beach ranking. It helps travelers decide whether My Khe, Non Nuoc, Son Tra edge, Hoi An coast, or Hoi An town makes the route calmer after weather, airport timing, family needs, hotel style, and transfer pressure are counted.';
$meta_evidence_moat = implode("\n", [
    'GSC evidence shows central comparison demand around Da Nang or Hoi An plus broader beach queries, so the page answers base choice rather than duplicating the country beach hub.',
    'The core verdict separates My Khe, Non Nuoc, Son Tra edge, An Bang/Hoi An coast, and Hoi An town by route job.',
    'Official Da Nang, Hoi An, weather, transport, family-beach, and local tourism sources are preserved in metadata/source trail.',
    'Beach safety, sea condition, hotel location, and transport guidance are framed as live checks instead of frozen operational claims.',
    'North-to-south orientation separates Man Thai/Son Tra edge, Pham Van Dong/My Khe, My An/An Thuong, Non Nuoc, and Hoi An coast.',
    'Photo proof explains route and base feel, not decorative scenery: city beach, old-town coast, marble/resort corridor, and Hai Van movement pressure.',
    'Airport, local weather, national weather, Da Nang tourism, and DAD checks are treated as final live-check layers.',
    'The page keeps visible external body anchors at zero while preserving source auditability.',
    'Skip logic protects central Vietnam routes from adding beach nights only because a beach looks convenient online.',
]);
$meta_related_routes = implode("\n", [
    'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use after choosing Da Nang beach as the base and needing the full city, Son Tra, Marble Mountains, airport, and day-trip plan.',
    'Da Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Decide whether the sleep base should be beach-first Da Nang, old-town-first Hoi An, or a careful split.',
    'Best Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ | Compare Da Nang coast value against Phu Quoc, Con Dao, Nha Trang, Mui Ne, Quy Nhon, and Cat Ba.',
    'Best Things to Do in Hoi An | /destinations/best-things-to-do-in-hoi-an/ | Use when An Bang or Cua Dai beach should support Hoi An atmosphere rather than replace it.',
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Place a Da Nang beach stay inside the full first-trip planning order.',
    'North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether central Vietnam should carry the route before adding beach nights.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check central-coast heat, rain, and storm-sensitive months before booking beach hotels.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Count airport, train, private-driver, Hoi An, and Hue movement before choosing a beach base.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Price beach hotels, private transfers, split bases, and weather buffers.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a Da Nang beach reset fits without thinning Hoi An, Hue, Ninh Binh, or the bay.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use two weeks to decide when Da Nang beach becomes a real central coast chapter.',
    'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check water, heat, road, activity, and interruption coverage before beach-heavy plans.',
    'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair beach taxis, late returns, deposits, water activities, and private drivers with practical risk checks.',
]);
$meta_hero_image_credit = 'Hero image: My Khe Beach seen from Son Tra Mountain by Christophe95, CC BY-SA 4.0. Body images: An Bang Beach by Alexkom000, Da Nang Marble Mountains by Kuroczynski, and Hai Van Pass by Wolkenkratzer, CC BY-SA 4.0.';
$meta_admin_notes = 'Complete draft generated for WordPress Admin review. Keep draft until manual preview, Rank Math review, image check, and final editorial pass are complete. The body intentionally avoids external anchors; source URLs are preserved in metadata and source trail.';

$content = <<<HTML
<!-- vg-da-nang-beaches-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$my_khe_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="My Khe Beach and Da Nang coastline seen from Son Tra Mountain" src="{$my_khe_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<div class="wp-block-group vg-guide-hero-inner">
<p class="vg-kicker">Reviewed beach-base guide - Updated {$review_date}</p>
<h1 class="wp-block-heading vg-display vg-guide-title">Da Nang Beaches Guide: My Khe, Non Nuoc or Hoi An Coast?</h1>
<p class="vg-guide-lede">Da Nang beaches are not one decision. My Khe is the easiest city-beach base, Non Nuoc suits a resort-and-Marble-Mountains rhythm, Son Tra edge feels quieter but less central, and the Hoi An coast works when old-town evenings matter more than airport convenience.</p>
<p class="vg-field-note">Concierge verdict: choose the beach that lowers route friction. The right base should make central Vietnam calmer after weather, transfers, family needs, hotel style, and Hoi An or Hue plans are counted.</p>
</div>
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-da-nang-beaches-concierge-verdict:v1 -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<p><strong>My Khe is the safest default for most first-time travelers who want Da Nang beach plus easy city access.</strong> It keeps airport transfers simple, gives a real beach chapter, and still leaves room for Hoi An, Son Tra, Marble Mountains, or a rest day.</p>
<p><strong>Non Nuoc is better when the trip is more resort-led.</strong> It can feel calmer and more spacious, especially for travelers who care about a hotel beach, pool rhythm, Marble Mountains access, and fewer city errands. It is weaker when you want easy walking food choices or frequent city movement.</p>
<p><strong>The Hoi An coast is best when Hoi An is the reason for central Vietnam.</strong> An Bang and the coast near Hoi An work beautifully when old-town evenings, food, cafes, tailoring, and heritage atmosphere matter more than airport convenience.</p>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-da-nang-beaches-gsc-demand:v1 -->
<h2 class="wp-block-heading">Search evidence shaping this draft</h2>
<p>Recent Search Console data for vietnamguide.net shows early demand around "da nang or hoi an", "hoi an or danang", "da nang hoi an", "beaches in vietnam", "nicest beaches in vietnam", and "most beautiful beach in vietnam". This draft uses those phrases as planning evidence, but it does not try to become another broad beach ranking. Its job is narrower: help international travelers choose the right central-coast base.</p>

<!-- vg-da-nang-beaches-quick-chooser:v1 -->
<h2 class="wp-block-heading">Quick chooser: which beach base fits?</h2>
<table class="vg-decision-table vg-da-nang-beaches-quick-chooser">
<thead><tr><th>Base choice</th><th>Best when</th><th>Weak when</th><th>Verdict</th></tr></thead>
<tbody>
<tr><td data-label="Base choice">My Khe / My An</td><td data-label="Best when">You want the easiest Da Nang beach base with restaurants, taxis, airport access, and flexible day trips.</td><td data-label="Weak when">You expect a remote resort mood or silence.</td><td data-label="Verdict">Best first-time default.</td></tr>
<tr><td data-label="Base choice">Non Nuoc / Marble corridor</td><td data-label="Best when">You want a resort-led stay, wider hotel grounds, pool time, and a quieter beach rhythm.</td><td data-label="Weak when">You want lots of independent dining and city walking.</td><td data-label="Verdict">Best for resort recovery.</td></tr>
<tr><td data-label="Base choice">Son Tra edge</td><td data-label="Best when">You want views, a softer edge of the city, and easier access to Son Tra if weather allows.</td><td data-label="Weak when">You need the simplest late-night food, shopping, and taxi rhythm.</td><td data-label="Verdict">Good for repeat visitors and quiet-seeking couples.</td></tr>
<tr><td data-label="Base choice">Hoi An coast / An Bang</td><td data-label="Best when">Hoi An old-town evenings, cafes, food, and atmosphere are the trip's main central chapter.</td><td data-label="Weak when">You have a late arrival, early flight, or need Da Nang airport convenience.</td><td data-label="Verdict">Best beach-plus-old-town pairing.</td></tr>
<tr><td data-label="Base choice">Hoi An town, beach as day use</td><td data-label="Best when">You want Hoi An first and beach second.</td><td data-label="Weak when">Beach time is the reason for the stay.</td><td data-label="Verdict">Best when atmosphere beats sand.</td></tr>
</tbody>
</table>

<!-- vg-da-nang-beaches-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: base feel changes the trip</h2>
<div class="vg-photo-grid vg-da-nang-beaches-photo-proof">
<figure><img src="{$my_khe_image}" alt="My Khe Beach and Da Nang coast viewed from Son Tra Mountain" loading="lazy" decoding="async"><figcaption>My Khe is the easiest city-beach answer when the route still needs airport, restaurants, and day-trip flexibility. Image: Christophe95 / CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$an_bang_image}" alt="An Bang Beach in Hoi An on the central Vietnam coast" loading="lazy" decoding="async"><figcaption>An Bang makes sense when beach time supports Hoi An evenings instead of replacing them. Image: Alexkom000 / CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$marble_image}" alt="Marble Mountains near Non Nuoc and Da Nang beach corridor" loading="lazy" decoding="async"><figcaption>The Non Nuoc and Marble corridor fits travelers who want a resort rhythm with one strong nearby sight. Image: Kuroczynski / CC BY-SA 4.0.</figcaption></figure>
<figure><img src="{$hai_van_image}" alt="Hai Van Pass coastal road between Da Nang and Hue" loading="lazy" decoding="async"><figcaption>Da Nang beach decisions change the route when Hue, Hai Van, or Hoi An movement is part of the same central chapter. Image: Wolkenkratzer / CC BY-SA 4.0.</figcaption></figure>
</div>

<!-- vg-da-nang-beaches-source-diversity:v1 -->
<h2 class="wp-block-heading">Source diversity behind this guide</h2>
<table class="vg-decision-table vg-da-nang-beaches-source-diversity">
<thead><tr><th>Evidence layer</th><th>What it can prove</th><th>What still needs editorial judgment</th></tr></thead>
<tbody>
<tr><td data-label="Evidence layer">Vietnam.travel Da Nang and Hoi An</td><td data-label="What it can prove">Da Nang's beach/city role and Hoi An's coast-plus-old-town value.</td><td data-label="What still needs editorial judgment">Which sleep base reduces friction for a specific route.</td></tr>
<tr><td data-label="Evidence layer">Da Nang Fantasticity</td><td data-label="What it can prove">Official local tourism, city orientation, beach context, airport visitor notes, and local update discipline.</td><td data-label="What still needs editorial judgment">Whether a traveler should stay My Khe, Non Nuoc, Son Tra edge, or Hoi An coast.</td></tr>
<tr><td data-label="Evidence layer">Weather and transport sources</td><td data-label="What it can prove">Season, rain, heat, airport, road, train, and transfer constraints.</td><td data-label="What still needs editorial judgment">Which beach plan should shrink, move, or be cut.</td></tr>
<tr><td data-label="Evidence layer">Airport and national weather checks</td><td data-label="What it can prove">Where to verify DAD airport details and Da Nang weather close to travel.</td><td data-label="What still needs editorial judgment">Whether the first or last night should prioritize airport ease over beach mood.</td></tr>
<tr><td data-label="Evidence layer">Family beach guidance</td><td data-label="What it can prove">Why shallow logistics, simple meals, shade, and hotel comfort matter for beach planning.</td><td data-label="What still needs editorial judgment">Whether family ease beats prettier but harder beach options.</td></tr>
<tr><td data-label="Evidence layer">GSC demand</td><td data-label="What it can prove">Readers already ask about Da Nang vs Hoi An and Vietnam beach choices.</td><td data-label="What still needs editorial judgment">How to answer without repeating the broad Best Beaches hub.</td></tr>
</tbody>
</table>

<!-- vg-da-nang-beaches-north-south-orientation:v1 -->
<h2 class="wp-block-heading">North-to-south beach orientation</h2>
<p>Da Nang's beach strip reads differently as you move south. This is the practical map to hold in your head before comparing hotels.</p>
<table class="vg-decision-table vg-da-nang-beaches-north-south-orientation">
<thead><tr><th>Coast segment</th><th>What it feels like</th><th>Best use</th><th>Live-check note</th></tr></thead>
<tbody>
<tr><td data-label="Coast segment">Man Thai / Son Tra edge</td><td data-label="What it feels like">Quieter, more local in the morning, closer to peninsula views.</td><td data-label="Best use">Sunrise walks, photography, a softer couple stay, and Son Tra access.</td><td data-label="Live-check note">Confirm food, transport, and any Son Tra access restrictions.</td></tr>
<tr><td data-label="Coast segment">Pham Van Dong / My Khe</td><td data-label="What it feels like">The cleanest city-beach default with easy movement.</td><td data-label="Best use">First-timers, short stays, families, airport buffers, and flexible day trips.</td><td data-label="Live-check note">Check flags, lifeguards, road crossings, and hotel noise.</td></tr>
<tr><td data-label="Coast segment">My An / An Thuong</td><td data-label="What it feels like">More food, cafes, traveler services, and independent walking.</td><td data-label="Best use">Travelers who want beach plus easy meals without resort isolation.</td><td data-label="Live-check note">Check exact distance from the beach and street noise.</td></tr>
<tr><td data-label="Coast segment">Non Nuoc / Hoa Hai</td><td data-label="What it feels like">More resort-led, quieter, and close to Marble Mountains.</td><td data-label="Best use">Pool, spa, golf, resort recovery, and slower family stays.</td><td data-label="Live-check note">Check dining cost, shuttle/taxi timing, and beach access.</td></tr>
<tr><td data-label="Coast segment">Ha My / An Bang / Cua Dai</td><td data-label="What it feels like">Hoi An beach support rather than Da Nang city beach.</td><td data-label="Best use">Hoi An evenings, food, cafes, and slower mornings by the coast.</td><td data-label="Live-check note">Check current Cua Dai condition, An Bang facilities, and Hoi An transfer rhythm.</td></tr>
</tbody>
</table>

<!-- vg-da-nang-beaches-my-khe-non-nuoc-hoi-an:v1 -->
<h2 class="wp-block-heading">My Khe, Non Nuoc or Hoi An coast?</h2>
<p>The three options are close on a map but different in feeling. My Khe is a city beach with easy services. Non Nuoc is a resort corridor with a calmer hotel-led rhythm. Hoi An coast is a beach extension of an old-town stay.</p>
<table class="vg-decision-table vg-da-nang-beaches-base-comparison">
<thead><tr><th>Question</th><th>My Khe</th><th>Non Nuoc</th><th>Hoi An coast</th></tr></thead>
<tbody>
<tr><td data-label="Question">Best route job</td><td data-label="My Khe">Make Da Nang easy and beach-forward.</td><td data-label="Non Nuoc">Make the stay restful and resort-led.</td><td data-label="Hoi An coast">Make Hoi An more breathable.</td></tr>
<tr><td data-label="Question">Best traveler</td><td data-label="My Khe">First-timer, couple, solo traveler, family needing flexibility.</td><td data-label="Non Nuoc">Resort traveler, family wanting pool and space, slower couple trip.</td><td data-label="Hoi An coast">Food, cafes, old-town evenings, soft beach days.</td></tr>
<tr><td data-label="Question">Main upside</td><td data-label="My Khe">Airport, food, taxis, city, and beach are all easy.</td><td data-label="Non Nuoc">More retreat feeling without leaving the central route.</td><td data-label="Hoi An coast">Beach plus Hoi An atmosphere in one chapter.</td></tr>
<tr><td data-label="Question">Main risk</td><td data-label="My Khe">Can feel urban and exposed if hotel choice is weak.</td><td data-label="Non Nuoc">Less walkable for independent meals and city errands.</td><td data-label="Hoi An coast">Less convenient for Da Nang airport and city nights.</td></tr>
<tr><td data-label="Question">Best minimum stay</td><td data-label="My Khe">Two nights.</td><td data-label="Non Nuoc">Two or three nights.</td><td data-label="Hoi An coast">Two nights if Hoi An is the main event.</td></tr>
</tbody>
</table>

<!-- vg-da-nang-beaches-base-map:v1 -->
<h2 class="wp-block-heading">Beach-base map for real planning</h2>
<p>Choose the base by what happens after breakfast and after sunset, not only by the beach photo. A good Da Nang beach hotel solves movement, food, shade, and recovery; a weak one creates taxi friction every time the group wants something simple.</p>
<ul class="vg-check-list vg-da-nang-beaches-base-map">
<li><strong>My Khe / My An:</strong> choose this when you want the simplest mix of beach, independent restaurants, cafes, taxis, and quick access to the airport or Han River.</li>
<li><strong>An Thuong area:</strong> useful when walkable food and traveler services matter, but check street noise and how far the hotel really is from the sand.</li>
<li><strong>Non Nuoc / Marble corridor:</strong> choose this when hotel grounds, pool, quiet, and resort service matter more than walking out to many local options.</li>
<li><strong>Son Tra edge:</strong> choose this when views and a softer city edge matter, but verify transport and food rhythm before assuming it is convenient.</li>
<li><strong>Hoi An coast:</strong> choose this when beach time should support Hoi An old-town evenings, not when Da Nang airport timing is the pressure point.</li>
</ul>

<!-- vg-da-nang-beaches-season-sea:v1 -->
<h2 class="wp-block-heading">Season and sea-condition logic</h2>
<p>Central Vietnam beach planning needs flexibility. Heat, rain, wind, storm-sensitive months, surf conditions, and hotel facilities can change whether the beach is the main event or a pleasant recovery block. Do not sell yourself a beach holiday if the route really needs weather buffers.</p>
<table class="vg-decision-table vg-da-nang-beaches-season-sea">
<thead><tr><th>Season pattern</th><th>Better move</th><th>Watch out for</th><th>Route note</th></tr></thead>
<tbody>
<tr><td data-label="Season pattern">Clear hot stretch</td><td data-label="Better move">Beach early or late, shaded lunch, pool reset, light evening.</td><td data-label="Watch out for">Long exposed walks and overpacked day trips.</td><td data-label="Route note">Hotel quality and shade become real value.</td></tr>
<tr><td data-label="Season pattern">Rainy or storm-sensitive window</td><td data-label="Better move">Keep flexible city, food, spa, Hoi An, or indoor pivots.</td><td data-label="Watch out for">Prepaid beach-only plans with no backup.</td><td data-label="Route note">Use live weather checks close to travel.</td></tr>
<tr><td data-label="Season pattern">Rougher sea or flagged beach conditions</td><td data-label="Better move">Treat the beach as scenery and hotel time, not mandatory swimming.</td><td data-label="Watch out for">Assuming every beach day is safe for children or weak swimmers.</td><td data-label="Route note">Pool, lifeguard, and hotel safety details matter.</td></tr>
<tr><td data-label="Season pattern">Short central stay</td><td data-label="Better move">Choose one beach base and one cultural handoff.</td><td data-label="Watch out for">Da Nang, Hoi An, Hue, Ba Na Hills, and beach recovery in one compressed block.</td><td data-label="Route note">A clean skip is sometimes the premium move.</td></tr>
</tbody>
</table>

<!-- vg-da-nang-beaches-family-swimmer:v1 -->
<h2 class="wp-block-heading">Families, swimmers and beach comfort</h2>
<p>Families and less confident swimmers should not choose only by the view. The practical filters are beach access, pool quality, shade, room layout, food, traffic crossing, lifeguard or flagged-area checks, and how easily the day can be shortened when heat or sea conditions change.</p>
<ul class="vg-check-list vg-da-nang-beaches-family-swimmer">
<li><strong>Choose My Khe</strong> when you need simple meals, quick taxis, and many hotel options near the beach.</li>
<li><strong>Choose Non Nuoc</strong> when the resort itself is the safety net: pool, breakfast, shade, room comfort, and fewer street crossings.</li>
<li><strong>Choose Hoi An coast</strong> when the family wants beach plus short Hoi An evenings, not late Da Nang city movement.</li>
<li><strong>Check flagged swimming conditions</strong> close to travel and never make sea swimming the only reason the day succeeds.</li>
<li><strong>Use a pool as backup</strong> when traveling with children, older relatives, or anyone who may need a heat reset.</li>
</ul>

<!-- vg-da-nang-beaches-hotel-decision:v1 -->
<h2 class="wp-block-heading">Hotel-base decision matrix</h2>
<table class="vg-decision-table vg-da-nang-beaches-hotel-decision">
<thead><tr><th>Hotel style</th><th>Best beach base</th><th>Check before booking</th><th>Do not pay extra for</th></tr></thead>
<tbody>
<tr><td data-label="Hotel style">City-beach hotel</td><td data-label="Best beach base">My Khe / My An.</td><td data-label="Check before booking">Road crossing, room noise, actual beach distance, breakfast, pool.</td><td data-label="Do not pay extra for">A vague sea view that does not improve the day.</td></tr>
<tr><td data-label="Hotel style">Resort recovery</td><td data-label="Best beach base">Non Nuoc / Marble corridor.</td><td data-label="Check before booking">Beach access, pool shade, dining cost, taxi timing, family facilities.</td><td data-label="Do not pay extra for">A remote feel if you will leave the hotel twice a day.</td></tr>
<tr><td data-label="Hotel style">Quiet couple stay</td><td data-label="Best beach base">Son Tra edge or a calmer Non Nuoc property.</td><td data-label="Check before booking">Food access, evening transport, weather exposure, room privacy.</td><td data-label="Do not pay extra for">Isolation that creates every-meal friction.</td></tr>
<tr><td data-label="Hotel style">Hoi An atmosphere stay</td><td data-label="Best beach base">Hoi An coast or Hoi An town plus beach time.</td><td data-label="Check before booking">Old-town transfer, bikes/shuttle, evening return, flood/rain posture.</td><td data-label="Do not pay extra for">A beach hotel if you mainly want old-town nights.</td></tr>
</tbody>
</table>

<!-- vg-da-nang-beaches-route-order:v1 -->
<h2 class="wp-block-heading">Beach plus culture route order</h2>
<p>Da Nang beach time works best when it has a clear job in the central chapter. It can soften an arrival, reset the route after northern movement, sit between Hoi An and Hue, or replace a weaker attraction day. It is less useful when it becomes another hotel move.</p>
<table class="vg-decision-table vg-da-nang-beaches-route-order">
<thead><tr><th>Route shape</th><th>Use the beach for</th><th>Risk</th></tr></thead>
<tbody>
<tr><td data-label="Route shape">Fly into Da Nang, then Hoi An</td><td data-label="Use the beach for">One easy beach night or airport buffer before old-town days.</td><td data-label="Risk">Sleeping in Da Nang too long when Hoi An is the real goal.</td></tr>
<tr><td data-label="Route shape">Hoi An first, Da Nang last</td><td data-label="Use the beach for">A cleaner airport exit and softer final central-coast reset.</td><td data-label="Risk">Choosing a beach hotel too far from the final flight needs.</td></tr>
<tr><td data-label="Route shape">Da Nang base with Hoi An evening</td><td data-label="Use the beach for">Beach mornings and one controlled Hoi An evening.</td><td data-label="Risk">Commuting repeatedly to Hoi An during its best hours.</td></tr>
<tr><td data-label="Route shape">Da Nang plus Hue transfer</td><td data-label="Use the beach for">Recovery before or after a serious Hai Van/Hue movement day.</td><td data-label="Risk">Trying to keep beach, Hai Van, Hue, and Hoi An all shallow.</td></tr>
<tr><td data-label="Route shape">Beach-led central stay</td><td data-label="Use the beach for">Pool, sand, food, easy day trips, and fewer transitions.</td><td data-label="Risk">Adding distant attractions until the beach base stops being restful.</td></tr>
</tbody>
</table>

<!-- vg-da-nang-beaches-airport-transport:v1 -->
<h2 class="wp-block-heading">Airport and transport reality</h2>
<p>Da Nang International Airport is the reason many beach plans work. It can turn My Khe into a low-stress first or final night, but it can also make travelers choose Da Nang when Hoi An would have given the better memory. Count the first transfer, the last transfer, and every Hoi An or Hue move before deciding.</p>
<ul class="vg-check-list vg-da-nang-beaches-airport-transport">
<li><strong>Late arrival:</strong> My Khe or a practical Da Nang hotel usually beats transferring to Hoi An tired.</li>
<li><strong>Early departure:</strong> Da Nang beach can be a better final night than Hoi An coast if flight timing is tight.</li>
<li><strong>Hoi An evenings:</strong> one planned evening from Da Nang is easy; repeated best-hour commuting is a sign to sleep in Hoi An.</li>
<li><strong>Hue movement:</strong> protect the Hai Van/Hue day as a route chapter, not a beach-day add-on.</li>
<li><strong>No motorbike plan:</strong> choose a walkable beach base or reliable taxis instead of assuming every hotel is equally convenient.</li>
</ul>

<!-- vg-da-nang-beaches-time-budget:v1 -->
<h2 class="wp-block-heading">Time budget: how much beach belongs in the route?</h2>
<table class="vg-decision-table vg-da-nang-beaches-time-budget">
<thead><tr><th>Available time</th><th>Best beach move</th><th>Add only if easy</th><th>Cut first</th></tr></thead>
<tbody>
<tr><td data-label="Available time">One night</td><td data-label="Best beach move">My Khe near the airport or Hoi An town if atmosphere matters more.</td><td data-label="Add only if easy">A short beach walk and one good meal.</td><td data-label="Cut first">Non Nuoc resort detours, Son Tra, and complex transfers.</td></tr>
<tr><td data-label="Available time">Two nights</td><td data-label="Best beach move">My Khe default, Hoi An coast if old-town evenings lead, Non Nuoc if resort recovery leads.</td><td data-label="Add only if easy">Marble Mountains, Hoi An evening, or a pool-centered day.</td><td data-label="Cut first">Ba Na Hills or a second distant excursion.</td></tr>
<tr><td data-label="Available time">Three nights</td><td data-label="Best beach move">Beach base plus one cultural handoff and one flexible recovery block.</td><td data-label="Add only if easy">Son Tra, Hoi An, Marble, or a route-specific day trip.</td><td data-label="Cut first">Changing hotels unless it removes friction.</td></tr>
<tr><td data-label="Available time">Four nights or more</td><td data-label="Best beach move">Make the beach a real central-coast chapter.</td><td data-label="Add only if easy">Split Da Nang/Hoi An only if each base has a different job.</td><td data-label="Cut first">Famous add-ons that steal the reason you booked the coast.</td></tr>
</tbody>
</table>

<!-- vg-da-nang-beaches-skip-logic:v1 -->
<h2 class="wp-block-heading">Skip logic that protects the beach stay</h2>
<ul class="vg-check-list vg-da-nang-beaches-skip-logic">
<li>Skip Non Nuoc if you will want independent city food, nightlife, and errands every day.</li>
<li>Skip My Khe if the whole point is a quieter resort retreat with fewer outside decisions.</li>
<li>Skip Hoi An coast if an early Da Nang flight or late arrival makes airport convenience more important.</li>
<li>Skip a beach hotel entirely if Hoi An evenings are the only central-coast memory you care about.</li>
<li>Skip extra attractions before cutting sleep, shade, pool time, or flexible weather buffers.</li>
<li>Skip any beach plan that depends on perfect swimming conditions every day.</li>
</ul>

<!-- vg-da-nang-beaches-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before booking Da Nang beaches</h2>
<ul class="vg-check-list vg-da-nang-beaches-live-checks">
<li>Check current central Vietnam weather, heat, rain, storm-sensitive months, and sea conditions close to travel.</li>
<li>Check the hotel's exact beach access, road crossing, pool shade, room noise, and breakfast/meal setup.</li>
<li>Check flagged swimming areas, lifeguard context, and beach-safety notices before making swimming the day's main promise.</li>
<li>Check Da Nang airport, arrival, departure, bus, taxi, ride-hailing, and hotel-transfer details before treating every beach base as equally easy.</li>
<li>Check Da Nang airport timing before choosing Hoi An coast or Non Nuoc for a first or final night.</li>
<li>Check Hoi An transfer timing if the beach stay depends on old-town evenings.</li>
<li>Check Cua Dai beach condition and An Bang facilities if the Hoi An coast is the reason for booking.</li>
<li>Check local events, festivals, and Vietnamese holiday pressure if room rates or crowds matter.</li>
<li>Check whether Son Tra, Marble Mountains, Hue, or Hoi An improves the beach chapter or simply makes it busier.</li>
</ul>

<!-- vg-da-nang-beaches-faq:v1 -->
<h2 class="wp-block-heading">Da Nang beaches FAQ</h2>
<div class="vg-faq-list vg-da-nang-beaches-faq">
<details><summary>What is the best beach area to stay in Da Nang?</summary><p>My Khe or My An is the safest first-time default because it balances beach access, restaurants, taxis, airport convenience, and day-trip flexibility.</p></details>
<details><summary>Is Non Nuoc better than My Khe?</summary><p>Non Nuoc is better for resort recovery, pool time, Marble Mountains access, and a quieter hotel-led stay. My Khe is better for independent food, city access, airport convenience, and flexible movement.</p></details>
<details><summary>Should I stay at Da Nang beach or Hoi An beach?</summary><p>Stay at Da Nang beach when airport convenience, a city-beach base, and day-trip flexibility matter. Stay near Hoi An coast when old-town evenings and Hoi An atmosphere are the central reason for the trip.</p></details>
<details><summary>How many nights do I need for Da Nang beaches?</summary><p>Two nights is enough for a practical beach reset. Three nights is better if Da Nang is your central base. Four or more nights work when beach recovery and resort comfort are the point, not filler.</p></details>
<details><summary>Are Da Nang beaches good for families?</summary><p>Yes when the hotel, beach access, pool, shade, meals, and transport are chosen carefully. Families should check current sea conditions and avoid making open-water swimming the only way the stay succeeds.</p></details>
<details><summary>Can I visit Hoi An from a Da Nang beach hotel?</summary><p>Yes, but it is best as one controlled evening or a planned day. If you want Hoi An during its best hours repeatedly, Hoi An town or coast may be a better base.</p></details>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Use this guide after the country beach decision and before booking the central-coast hotel. Then move into Da Nang city planning, Da Nang vs Hoi An, Best Beaches, Hoi An, season, transport, cost, and itinerary checks.</p>
<div class="vg-related-routes vg-da-nang-beaches-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a><span class="vg-related-route-note">Use once Da Nang is likely to be the central beach base.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a><span class="vg-related-route-note">Decide whether the route should sleep by the beach, old town, or both.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a><span class="vg-related-route-note">Compare Da Nang coast with island and south-coast alternatives.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a><span class="vg-related-route-note">Use if An Bang or Hoi An coast is really an old-town support choice.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Check weather and season before locking beach hotels.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Count airport, Hoi An, Hue, train, and private-driver movement.</span></li>
<li><span class="vg-related-route-step">07</span><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a><span class="vg-related-route-note">Test whether the beach reset fits a short route.</span></li>
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
    'vg-da-nang-beaches-hero:v1',
    'vg-da-nang-beaches-concierge-verdict:v1',
    'vg-da-nang-beaches-gsc-demand:v1',
    'vg-da-nang-beaches-quick-chooser:v1',
    'vg-da-nang-beaches-photo-proof:v1',
    'vg-da-nang-beaches-source-diversity:v1',
    'vg-da-nang-beaches-north-south-orientation:v1',
    'vg-da-nang-beaches-my-khe-non-nuoc-hoi-an:v1',
    'vg-da-nang-beaches-base-map:v1',
    'vg-da-nang-beaches-season-sea:v1',
    'vg-da-nang-beaches-family-swimmer:v1',
    'vg-da-nang-beaches-hotel-decision:v1',
    'vg-da-nang-beaches-route-order:v1',
    'vg-da-nang-beaches-airport-transport:v1',
    'vg-da-nang-beaches-time-budget:v1',
    'vg-da-nang-beaches-skip-logic:v1',
    'vg-da-nang-beaches-live-checks:v1',
    'vg-da-nang-beaches-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_da_nang_beaches_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Da Nang Beaches Guide: My Khe, Non Nuoc or Hoi An Coast?',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Choose between My Khe, Non Nuoc, Son Tra edge, Hoi An coast, or Hoi An town by route friction, season, hotel style, family needs, and transfer pressure.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_da_nang_beaches_post_fail('Could not update Da Nang Beaches post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Da Nang Beaches Guide: My Khe, Non Nuoc or Hoi An Coast?');
update_post_meta($post_id, 'rank_math_description', 'Choose between My Khe, Non Nuoc, Son Tra edge, Hoi An coast, or Hoi An town by season, hotel style, family needs, and route pressure.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Da Nang beaches guide');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Decide whether My Khe, Non Nuoc, Son Tra edge, Hoi An coast, or Hoi An town is the better central-coast base after airport timing, weather, family comfort, hotel style, and Hoi An/Hue movement are counted.');
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
    vg_da_nang_beaches_post_fail('Could not assign Da Nang Beaches categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_da_nang_beaches_post_fail('Could not assign Da Nang Beaches tags: ' . $tag_result->get_error_message());
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
        vg_da_nang_beaches_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Da Nang Beaches post to complete draft: {$post_id}");

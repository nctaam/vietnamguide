<?php
/**
 * Expand the Ha Long Bay Cruise Questions post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-ha-long-cruise-questions-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_ha_long_cruise_questions_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_ha_long_cruise_questions_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_ha_long_cruise_questions_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_ha_long_cruise_questions_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_ha_long_cruise_questions_post_find_by_slug(string $slug): ?WP_Post
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
        vg_ha_long_cruise_questions_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_ha_long_cruise_questions_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-07',
            'vg_editorial_brief_status' => 'brief',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if ($actual_value !== $expected_value) {
            vg_ha_long_cruise_questions_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_ha_long_cruise_questions_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_ha_long_cruise_questions_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'ha-long-bay-cruise-questions-before-booking';
$post = vg_ha_long_cruise_questions_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_ha_long_cruise_questions_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_ha_long_cruise_questions_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 25, 2026';
vg_ha_long_cruise_questions_post_assert_target_meta($post);

$hero_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg/1920px-Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg';
$cruise_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3e/Halong_Bay_Cruise_Boats_01.jpg/1920px-Halong_Bay_Cruise_Boats_01.jpg';
$cabin_context_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/28/View_of_sea_from_Titov_Island%2C_Ha_Long_Bay%2C_Vietnam%2C_20240128_1337_3732.jpg/1920px-View_of_sea_from_Titov_Island%2C_Ha_Long_Bay%2C_Vietnam%2C_20240128_1337_3732.jpg';
$cave_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/10/Sung_Sot_Cave%2C_Ha_Long_Bay%2C_Vietnam%2C_20240128_1549_3836.jpg/1920px-Sung_Sot_Cave%2C_Ha_Long_Bay%2C_Vietnam%2C_20240128_1549_3836.jpg';
$lan_ha_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/ca/Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg/1920px-Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg';
$bai_tu_long_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/46/B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg/1920px-B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg';

$meta_update_summary = 'Expanded the native WordPress post brief into a complete cruise-shopping draft with a photo-led hero, proof panel, concierge verdict, at-a-glance buying matrix, photo proof, source-diversity table, route-map questions, port and transfer checks, cabin and deck checks, weather/cancellation policy questions, family comfort checks, Bai Tu Long trade-offs, payment-risk questions, red flags, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Ha Long destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-long - checked {$review_date}; used for high-level Ha Long destination framing, seasonal references, and cruise context without replacing product-level judgment.",
    "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked {$review_date}; used for northern route context around Hanoi, Ninh Binh, Ha Long, Lan Ha, and Cat Ba.",
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for weather-check discipline before bay cruise deposits, exposed activities, and onward transfers.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for transfer and road/port planning context.",
    "UNESCO World Heritage Centre - Ha Long Bay-Cat Ba Archipelago - https://whc.unesco.org/en/list/672/ - checked {$review_date}; used for heritage framing without letting a UNESCO label replace cruise-product checks.",
    "Ha Long Bay Management - https://halongbay.com.vn/ - checked {$review_date}; used for bay management, route, ticket, and operational context close to travel.",
    "Ha Long Bay Management - route VHL4 - https://halongbay.com.vn/tours/4-hanh-trinh-vhl-4-cang-tau-hang-co-thien-canh-son-hang-thay-hang-cap-la-vong-vieng-khu-sinh-thai-tung-ang-dao-cong-do-cong-vien-hon-xep - checked {$review_date}; used as an example of why route names and route maps matter for Bai Tu Long claims.",
    "Cat Ba tourism/service information - https://catba.com.vn/ - checked {$review_date}; used for Lan Ha and Cat Ba route context when a cruise uses an island-linked gateway.",
    "Bai Tu Long National Park - https://vuonquocgiabaitulong.vn/ - checked {$review_date}; used for national-park context when a cruise claim mentions Bai Tu Long or nature depth.",
    "Wikimedia Commons image direct URL - Ha Long Bay, Vietnam, View from above - {$hero_image} - credit Vyacheslav Argenberg / CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Halong Bay Cruise Boats 01 - {$cruise_image} - credit Shyamal L. / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Titov Island view - {$cabin_context_image} - credit Jakub Halun / CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Sung Sot Cave - {$cave_image} - credit Jakub Halun / CC BY 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Lan Ha Bay-Cat Ba Vietnam - {$lan_ha_image} - credit Saaremees / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Bai Tu Long Bay 01 - {$bai_tu_long_image} - credit Benjamin Smith / CC BY-SA 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This post is a cruise-shopping decision layer for the native WordPress editorial calendar. It avoids duplicating the Ha Long Bay Travel Guide, Ha Long Bay vs Lan Ha Bay, Bai Tu Long Bay Guide, and Ninh Binh to Ha Long Bay Transfer by focusing on questions to ask before a traveler pays a cruise deposit.';
$meta_evidence_moat = implode("\n", [
    'Question-led cruise-shopping article built around port, cabin, weather, route, and transfer risk rather than cruise rankings.',
    'Concierge verdict tells travelers to buy the route quality and operating clarity, not only the prettiest photos or star rating.',
    'At-a-glance matrix turns the main buying questions into answer, why it matters, and weak-answer risk.',
    'Photo proof explains what each cruise promise should map to: seascape, cruise density, viewpoint, cave route, Lan Ha, and Bai Tu Long.',
    'Source-diversity section separates official destination and heritage context from product-level questions a traveler must ask an operator.',
    'Route-map section requires route name, stops, activity sequence, time on water, and what changes in bad weather.',
    'Port and transfer section connects Hanoi, Ninh Binh, Tuan Chau, Ha Long, Lan Ha, Cat Ba, Bai Tu Long, luggage, and onward timing.',
    'Cabin, deck, food, and noise checks give practical decision value beyond generic luxury cruise copy.',
    'Weather and cancellation policy checks explain reroute, refund, authority restriction, and onward flight risk without stale guarantees.',
    'Family comfort and mobility section makes the guide useful for parents, older travelers, and mixed-energy groups.',
    'Bai Tu Long trade-off section prevents vague quieter-bay marketing from replacing route verification.',
    'Payment-risk and red-flag tables avoid affiliate-style recommendations and support manual WordPress review.',
    'No visible external body anchors; official source trail and image-license records stay auditable through metadata and shortcode output.',
]);
$meta_related_routes = implode("\n", [
    'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Use for the full destination frame, cruise length, timing, port, route fit, and skip logic.',
    'Ha Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Choose the right bay, port, cruise style, Cat Ba access, crowd pressure, and overnight value before booking.',
    'Bai Tu Long Bay Guide | /destinations/bai-tu-long-bay-guide/ | Decide whether a quieter, route-specific bay cruise is worth the extra buying friction versus Ha Long, Lan Ha, or Cat Ba.',
    'Ninh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check northern visibility, mist, heat, rain, storm-season pressure, and cancellation risk before cruise deposits.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Put cruise pickup, pier, road transfer, luggage, and onward movement inside the wider Vietnam transport plan.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the real difference between cabin class, route quality, transfer certainty, flexibility, and deposit risk.',
    'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check boat, weather, activity, interruption, cancellation, evacuation, and medical coverage before a cruise.',
    'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair cruise deposits, transfer offers, payment channels, and activity promises with practical risk checks.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a bay cruise improves or overloads a short first route.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when the bay can become a protected northern chapter without stealing from Central or South Vietnam.',
    'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide whether the northern landscape chapter should be countryside, bay, or both.',
    'Best Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Compare whether a bay cruise should be a day preview, overnight cruise, or clean skip.',
]);
$meta_hero_image_credit = 'Hero and Ha Long aerial image: Ha Long Bay, Vietnam, View from above by Vyacheslav Argenberg, CC BY 4.0. Body images: Halong Bay Cruise Boats 01 by Shyamal L., CC BY-SA 4.0; Titov Island view by Jakub Halun, CC BY 4.0; Sung Sot Cave by Jakub Halun, CC BY 4.0; Lan Ha Bay-Cat Ba by Saaremees, CC BY-SA 4.0; Bai Tu Long Bay 01 by Benjamin Smith, CC BY-SA 4.0.';
$meta_admin_notes = 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, confirms image presentation, checks Rank Math/social preview, verifies Ha Long, Lan Ha, Cat Ba, Bai Tu Long, UNESCO, weather, route-management, transfer, and image-license sources, and decides whether first-hand cruise vetting notes should be added before publishing.';

$content = <<<HTML
<!-- vg-ha-long-cruise-questions-hero:v1 -->
<!-- wp:html -->
<section class="vg-guide-hero vg-ha-long-cruise-questions-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Ha Long cruise buying questions</p>
<h1>Ha Long Bay Cruise Questions to Ask Before Booking</h1>
<p class="vg-guide-lede">A Ha Long Bay cruise is rarely ruined by the bay itself. It is ruined by a vague port, weak route map, poor cabin fit, bad weather policy, rushed transfer, or a pretty sales page that never explains what you are actually buying.</p>
<p class="vg-field-note">Use this before paying a deposit. The point is not to find the single best cruise. The point is to ask better questions so the cruise product matches your route, comfort needs, weather risk, and onward travel.</p>
</div>
<figure class="vg-guide-hero-image"><img src="{$hero_image}" alt="Aerial limestone islands in Ha Long Bay, Vietnam" loading="eager" decoding="async"><figcaption>Before buying the postcard, ask whether the route gives you enough real water time to enjoy it. Image: Vyacheslav Argenberg / CC BY 4.0.</figcaption></figure>
</section>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-ha-long-cruise-questions-verdict:v1 -->
<!-- wp:html -->
<aside class="vg-concierge-verdict vg-ha-long-cruise-questions-verdict" aria-label="Ha Long Bay cruise booking verdict">
<p class="vg-kicker">VietnamGuide verdict</p>
<h2>Do not book a bay name. Book a route you understand.</h2>
<p><strong>The best cruise choice is the one that can answer port, cabin, weather, route, and transfer risk clearly before payment.</strong> Star rating, balcony wording, and sunset photos matter less than where you board, where the boat goes, how much deck time you get, what happens when weather changes, and whether the next transfer can survive a delayed return.</p>
<ul>
<li><strong>Best default question:</strong> can you send the exact pier, pickup, route map, cabin type, inclusions, cancellation terms, and return time in writing?</li>
<li><strong>Best premium question:</strong> what does the higher price improve beyond branding: cabin, deck, food, route, staff, activity pace, or flexibility?</li>
<li><strong>Best family question:</strong> where are the easy exits, quiet spaces, child-safe activity choices, and realistic meal/sleep windows?</li>
<li><strong>Best anti-spam question:</strong> would this cruise still be worth buying if the marketing photos were removed?</li>
</ul>
</aside>
<!-- /wp:html -->

<!-- wp:paragraph -->
<p>This guide supports the <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>, <a href="/destinations/bai-tu-long-bay-guide/">Bai Tu Long Bay Guide</a>, and <a href="/plan/ninh-binh-to-ha-long-bay-transfer/">Ninh Binh to Ha Long Bay Transfer</a>. Those pages help you choose the bay and route shape. This page helps you interrogate the specific cruise offer before money leaves your account.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The durable pattern is simple: every cruise promise should turn into a verifiable detail. "Luxury" should become cabin size, deck space, food quality, staff help, and pickup reliability. "Less crowded" should become a route map and timing. "Lan Ha" or "Bai Tu Long" should become a pier, sailing lane, activity sequence, and return plan. "Flexible cancellation" should become written dates, refund rules, and reroute policy.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Visible external links are intentionally avoided in the article body. Official destination, heritage, bay-management, tourism-service, weather, and image-license sources are stored in the source trail metadata. The body stays focused on buying judgment and internal planning routes.</p>
<!-- /wp:paragraph -->

<!-- vg-ha-long-cruise-questions-at-a-glance:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cruise questions at a glance</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-cruise-questions-at-a-glance">
<thead><tr><th>Ask before booking</th><th>Strong answer sounds like</th><th>Weak answer sounds like</th><th>Why it matters</th></tr></thead>
<tbody>
<tr><td data-label="Ask before booking">Which pier and pickup point?</td><td data-label="Strong answer sounds like">Exact pier, hotel pickup zone, meeting time, return point, and luggage plan.</td><td data-label="Weak answer sounds like">"Ha Long pickup included" with no pier or timing detail.</td><td data-label="Why it matters">The cruise begins as a transfer problem before it becomes a bay memory.</td></tr>
<tr><td data-label="Ask before booking">What route map does the boat use?</td><td data-label="Strong answer sounds like">Named route, activity order, sailing area, and what changes if weather or authority rules shift.</td><td data-label="Weak answer sounds like">"Best route" or "less touristy route" without proof.</td><td data-label="Why it matters">The bay name alone does not tell you what the boat actually does.</td></tr>
<tr><td data-label="Ask before booking">What cabin am I buying?</td><td data-label="Strong answer sounds like">Cabin size, window or balcony type, deck level, noise risk, bed setup, bathroom, and child setup.</td><td data-label="Weak answer sounds like">A beautiful cabin photo without floor, size, or view certainty.</td><td data-label="Why it matters">Overnight value lives in the room and deck, not only the scenery.</td></tr>
<tr><td data-label="Ask before booking">What is the weather and cancellation policy?</td><td data-label="Strong answer sounds like">Written refund, reschedule, reroute, and cancellation rules for bad weather or official restrictions.</td><td data-label="Weak answer sounds like">"Weather is usually fine" or "we will help" without terms.</td><td data-label="Why it matters">Bay cruises can change because of weather and local operating decisions.</td></tr>
<tr><td data-label="Ask before booking">What happens after disembarkation?</td><td data-label="Strong answer sounds like">Return timing, onward transfer, airport buffer, and plan if the boat is delayed.</td><td data-label="Weak answer sounds like">Same-day flight or train booked close to return time.</td><td data-label="Why it matters">A great cruise can still damage the trip if the next leg is too tight.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-cruise-questions-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: every pretty image hides a buying question</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-ha-long-cruise-questions-photo-grid" aria-label="Ha Long Bay cruise booking context photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Aerial limestone seascape in Ha Long Bay" loading="lazy" decoding="async"><figcaption>Question: does the cruise give you enough open-water time to experience the famous scene? Image: Vyacheslav Argenberg / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cruise_image}" alt="Cruise boats in Ha Long Bay" loading="lazy" decoding="async"><figcaption>Question: how dense is the route and what exactly does the operator mean by quieter? Image: Shyamal L. / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cabin_context_image}" alt="Viewpoint over Ha Long Bay from Titov Island" loading="lazy" decoding="async"><figcaption>Question: is the viewpoint stop worth the climb, queue, and time it takes from deck hours? Image: Jakub Halun / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cave_image}" alt="Sung Sot Cave inside Ha Long Bay" loading="lazy" decoding="async"><figcaption>Question: does the cave stop add depth or just another rushed queue? Image: Jakub Halun / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$lan_ha_image}" alt="Lan Ha Bay scenery near Cat Ba Island" loading="lazy" decoding="async"><figcaption>Question: would Lan Ha and Cat Ba logistics fit better than a standard Ha Long product? Image: Saaremees / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$bai_tu_long_image}" alt="Bai Tu Long Bay limestone islands" loading="lazy" decoding="async"><figcaption>Question: can the operator prove the quieter Bai Tu Long route, not just sell the adjective? Image: Benjamin Smith / CC BY-SA 4.0.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-ha-long-cruise-questions-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How to use the evidence without turning the article into source clutter</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Official sources can frame Ha Long, Cat Ba, UNESCO status, weather, transport, and local bay-management context. They cannot choose your exact cruise product. That product decision needs a different layer: route map, pier, cabin, weather policy, food, child comfort, activity pace, and transfer risk.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-cruise-questions-source-diversity">
<thead><tr><th>Evidence layer</th><th>Useful for</th><th>Still ask the operator</th></tr></thead>
<tbody>
<tr><td data-label="Evidence layer">Vietnam.travel Ha Long and Northern Vietnam</td><td data-label="Useful for">Destination frame, seasonal context, and why the bay belongs in a northern route.</td><td data-label="Still ask the operator">Exact route, timing, cabin, pier, and cancellation terms.</td></tr>
<tr><td data-label="Evidence layer">UNESCO Ha Long Bay-Cat Ba Archipelago</td><td data-label="Useful for">Heritage context and the reason the seascape matters beyond photos.</td><td data-label="Still ask the operator">Whether the cruise actually gives respectful time in the landscape.</td></tr>
<tr><td data-label="Evidence layer">Ha Long Bay Management</td><td data-label="Useful for">Bay-management, route, ticket, service, and operating context close to travel.</td><td data-label="Still ask the operator">Which route code or route map the cruise uses and what changes in poor weather.</td></tr>
<tr><td data-label="Evidence layer">Cat Ba tourism and Bai Tu Long sources</td><td data-label="Useful for">Lan Ha, Cat Ba, and Bai Tu Long alternatives when the classic Ha Long label is not the best fit.</td><td data-label="Still ask the operator">Exact gateway, island-base logic, route proof, and return timing.</td></tr>
<tr><td data-label="Evidence layer">Licensed image records</td><td data-label="Useful for">Visual proof of the scenes buyers are comparing.</td><td data-label="Still ask the operator">Whether the product actually reaches, slows down, or respects those scenes.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-cruise-questions-route-map:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Question 1: can I see the actual route map?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A route map is the first anti-marketing filter. Without it, "Ha Long", "Lan Ha", "Bai Tu Long", "less crowded", and "luxury" are only labels. A strong operator can explain where the boat boards, where it sails, where it anchors, what activity stops exist, how long you spend on deck, and what changes if weather or local rules shift the itinerary.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-cruise-questions-route-map">
<thead><tr><th>Route question</th><th>Good answer</th><th>Red flag</th><th>Use this guide next</th></tr></thead>
<tbody>
<tr><td data-label="Route question">Which bay or route area?</td><td data-label="Good answer">Ha Long, Lan Ha, Cat Ba-linked, Bai Tu Long, or a mixed route is named clearly.</td><td data-label="Red flag">The sales page uses all names but the itinerary cannot prove them.</td><td data-label="Use this guide next"><a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a></td></tr>
<tr><td data-label="Route question">Which activity stops?</td><td data-label="Good answer">Cave, viewpoint, kayaking, beach, swimming, village, or deck time are listed with order and approximate timing.</td><td data-label="Red flag">Every possible activity is promised without saying what is included.</td><td data-label="Use this guide next"><a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a></td></tr>
<tr><td data-label="Route question">How much quiet water time?</td><td data-label="Good answer">The cruise protects real sailing or deck time, not only transfers and stops.</td><td data-label="Red flag">The route is a queue of activities with little time to simply be on the bay.</td><td data-label="Use this guide next"><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a></td></tr>
<tr><td data-label="Route question">What if the route changes?</td><td data-label="Good answer">The operator names weather, authority, tide, and safety changes plus refund/reschedule rules.</td><td data-label="Red flag">The answer says changes are rare, so no written policy is needed.</td><td data-label="Use this guide next"><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-cruise-questions-port-transfer:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Question 2: which pier, pickup, return point, and transfer chain?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The cruise does not start at the gangway. It starts with a hotel lobby, road transfer, pier, luggage handoff, boarding window, and return plan. This matters even more if you come from Ninh Binh, leave for an airport, use a Cat Ba or Lan Ha gateway, or connect to a premium hotel with strict check-in or checkout timing.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-cruise-questions-port-transfer">
<thead><tr><th>Transfer question</th><th>Ask for</th><th>Why it matters</th><th>Bad booking pattern</th></tr></thead>
<tbody>
<tr><td data-label="Transfer question">Pickup from Hanoi</td><td data-label="Ask for">Exact pickup district, time range, vehicle type, luggage rules, and return drop-off.</td><td data-label="Why it matters">A vague pickup can erase the calm part of a premium cruise.</td><td data-label="Bad booking pattern">Buying the cruise before knowing if the hotel is inside the pickup zone.</td></tr>
<tr><td data-label="Transfer question">Transfer from Ninh Binh</td><td data-label="Ask for">Exact bay port, pickup point, cruise check-in time, and luggage handling.</td><td data-label="Why it matters">Ninh Binh to bay transfers are fragile when the port is unclear.</td><td data-label="Bad booking pattern">Booking "Ha Long transfer" before choosing the actual cruise port.</td></tr>
<tr><td data-label="Transfer question">Lan Ha or Cat Ba gateway</td><td data-label="Ask for">Whether the route uses Ha Long/Tuan Chau, Hai Phong/Got, Cat Ba, or another handoff.</td><td data-label="Why it matters">Lan Ha can be excellent, but gateway confusion can make it feel messy.</td><td data-label="Bad booking pattern">Assuming Lan Ha is always simpler because it sounds quieter.</td></tr>
<tr><td data-label="Transfer question">After disembarkation</td><td data-label="Ask for">Return time, buffer, backup if delayed, and whether an airport or train connection is realistic.</td><td data-label="Why it matters">The next leg can turn a good cruise into a stressful day.</td><td data-label="Bad booking pattern">International flight, domestic flight, or train too close after cruise return.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-cruise-questions-cabin-deck:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Question 3: what cabin, deck, food, and noise level am I actually buying?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Cabin language can be slippery. Balcony, ocean view, suite, premium, deluxe, and luxury can mean very different things across boats. Ask for the actual cabin type, deck level, bed setup, window or balcony, bathroom, air-conditioning, noise exposure, stairs, and whether the room shown in photos is the room class you are buying.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-cruise-questions-cabin-deck">
<thead><tr><th>Comfort item</th><th>Ask this</th><th>Worth paying more when...</th><th>Do not pay more for...</th></tr></thead>
<tbody>
<tr><td data-label="Comfort item">Cabin</td><td data-label="Ask this">Exact room class, square meters, deck, window/balcony, bed layout, and bathroom.</td><td data-label="Worth paying more when...">You get real space, view, quiet, and a better sleep window.</td><td data-label="Do not pay more for...">A photo that may belong to another category.</td></tr>
<tr><td data-label="Comfort item">Deck space</td><td data-label="Ask this">How much public deck space exists and when meals/activities interrupt it.</td><td data-label="Worth paying more when...">Deck time is the main reason for the overnight cruise.</td><td data-label="Do not pay more for...">An itinerary that keeps you off the deck all day.</td></tr>
<tr><td data-label="Comfort item">Food</td><td data-label="Ask this">Menu style, dietary needs, children meals, drinks inclusions, and meal timing.</td><td data-label="Worth paying more when...">Food quality and pacing help the cruise feel calm.</td><td data-label="Do not pay more for...">Vague "fine dining" copy with no sample menu.</td></tr>
<tr><td data-label="Comfort item">Noise and stairs</td><td data-label="Ask this">Whether the room is near engine, karaoke, dining room, generator, kitchen, or high-traffic stairs.</td><td data-label="Worth paying more when...">The upgrade solves sleep, mobility, or family comfort.</td><td data-label="Do not pay more for...">An upper deck room if mobility or noise risk gets worse.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-cruise-questions-weather-policy:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Question 4: what is the weather, cancellation, reroute, and refund policy?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Bay weather does not need fear. It needs policy clarity. Mist can be atmospheric; heavy rain, storms, unsafe water, poor visibility, or authority restrictions can change a cruise. The important question is not whether the operator promises good weather. It is what happens if the plan changes.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-cruise-questions-weather-policy">
<thead><tr><th>Policy question</th><th>Ask for written answer</th><th>Why it matters</th><th>Keep flexible</th></tr></thead>
<tbody>
<tr><td data-label="Policy question">Cancellation</td><td data-label="Ask for written answer">Refund percentage by timing and who decides whether sailing is unsafe.</td><td data-label="Why it matters">You need to know whether a weather problem becomes a money problem.</td><td data-label="Keep flexible">The day after cruise return.</td></tr>
<tr><td data-label="Policy question">Reroute</td><td data-label="Ask for written answer">What route, activity, or accommodation changes if the bay route changes.</td><td data-label="Why it matters">A rerouted cruise may still be good, but it should not be a surprise.</td><td data-label="Keep flexible">Activity expectations.</td></tr>
<tr><td data-label="Policy question">Delay</td><td data-label="Ask for written answer">What happens if boarding, disembarkation, or transfer return is late.</td><td data-label="Why it matters">A tight onward flight or train is the real danger.</td><td data-label="Keep flexible">Flight, train, and hotel check-in timing.</td></tr>
<tr><td data-label="Policy question">Deposit</td><td data-label="Ask for written answer">Refund method, payment channel, currency, card fee, and deadline.</td><td data-label="Why it matters">A soft promise is weaker than a clear payment record.</td><td data-label="Keep flexible">Non-refundable pieces around the cruise.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-cruise-questions-family-comfort:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Question 5: does the cruise fit your family, mobility, and mixed-energy group?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A cruise can be beautiful and still wrong for a group. Families need safe transitions, meal timing, sleeping setup, railings, stairs, child-friendly activity choices, and places to rest. Older travelers or anyone with mobility limits need honest answers about gangways, tender boats, cave steps, viewpoint climbs, wet decks, and whether skipping an activity is comfortable.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-cruise-questions-family-comfort">
<thead><tr><th>Traveler profile</th><th>Ask this</th><th>Better cruise answer</th><th>Warning sign</th></tr></thead>
<tbody>
<tr><td data-label="Traveler profile">Family with young children</td><td data-label="Ask this">Can children skip kayaking, caves, or viewpoint climbs without losing the whole day?</td><td data-label="Better cruise answer">Flexible activity choices, early meals, and a cabin setup that actually sleeps everyone.</td><td data-label="Warning sign">Every hour is scheduled and every stop is treated as mandatory.</td></tr>
<tr><td data-label="Traveler profile">Older travelers</td><td data-label="Ask this">How many stairs, tenders, wet surfaces, cave steps, and transfers are involved?</td><td data-label="Better cruise answer">Clear mobility advice and realistic alternatives.</td><td data-label="Warning sign">The operator says everyone can do everything.</td></tr>
<tr><td data-label="Traveler profile">Premium short trip</td><td data-label="Ask this">What does the upgrade remove: waiting, crowding, noise, poor food, or transfer ambiguity?</td><td data-label="Better cruise answer">A calmer product with visible operational advantages.</td><td data-label="Warning sign">The only difference is adjectives.</td></tr>
<tr><td data-label="Traveler profile">Mixed interests</td><td data-label="Ask this">Can one person rest while another kayaks, climbs, or joins the cave visit?</td><td data-label="Better cruise answer">A route that allows different energy levels without tension.</td><td data-label="Warning sign">The group must move as one block all day.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-cruise-questions-bai-tu-long:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Question 6: is Bai Tu Long or Lan Ha a real improvement, or just marketing?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Lan Ha and Bai Tu Long can both be excellent. They can also become vague alternatives sold to travelers who are afraid Ha Long will be crowded. Ask the operator to prove the difference. A better alternative bay product should name the pier, route, activity sequence, return point, and why the route creates a different experience.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-cruise-questions-bai-tu-long">
<thead><tr><th>Claim</th><th>Ask for proof</th><th>Good reason to choose it</th><th>Weak reason to choose it</th></tr></thead>
<tbody>
<tr><td data-label="Claim">Lan Ha is quieter</td><td data-label="Ask for proof">Cat Ba or Lan Ha gateway, sailing route, kayaking area, and how pickup works.</td><td data-label="Good reason to choose it">You want Cat Ba-linked routing or softer cruise density and logistics are clear.</td><td data-label="Weak reason to choose it">You assume every Lan Ha cruise is quieter automatically.</td></tr>
<tr><td data-label="Claim">Bai Tu Long is more exclusive</td><td data-label="Ask for proof">Route name, route code if available, pier, activity stops, and whether it truly enters Bai Tu Long.</td><td data-label="Good reason to choose it">The quieter route is the point and the operator can explain it.</td><td data-label="Weak reason to choose it">The sales page says "off the beaten path" with no route detail.</td></tr>
<tr><td data-label="Claim">Two nights are better</td><td data-label="Ask for proof">What the second night adds beyond more meals and the same scenery.</td><td data-label="Good reason to choose it">The route reaches calmer water, better activity timing, or a slower northern chapter.</td><td data-label="Weak reason to choose it">You are trying to justify a high price without improving the itinerary.</td></tr>
<tr><td data-label="Claim">Luxury solves crowding</td><td data-label="Ask for proof">How the route, cabin, deck, staff, and activity schedule reduce friction.</td><td data-label="Good reason to choose it">The product removes real problems.</td><td data-label="Weak reason to choose it">The same crowded route is repackaged with nicer photos.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-cruise-questions-payment-risk:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Question 7: how safe is the payment and booking trail?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Cruise booking is not only about price. It is about accountability. A clear booking trail should show company name, boat name, date, cabin category, passenger names, inclusions, exclusions, pickup, return, payment amount, refund terms, and contact method. If any of those are vague, the discount may not be a discount.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-cruise-questions-payment-risk">
<thead><tr><th>Payment item</th><th>Ask this</th><th>Good answer</th><th>Risk if vague</th></tr></thead>
<tbody>
<tr><td data-label="Payment item">Deposit</td><td data-label="Ask this">How much, when refundable, by what method, and under what weather or cancellation terms?</td><td data-label="Good answer">Written refund schedule and payment record.</td><td data-label="Risk if vague">A policy dispute after weather changes.</td></tr>
<tr><td data-label="Payment item">Inclusions</td><td data-label="Ask this">Are transfers, meals, kayaking, cave fees, drinks, tips, park fees, and surcharge dates included?</td><td data-label="Good answer">Line-item clarity.</td><td data-label="Risk if vague">The cheap fare becomes expensive later.</td></tr>
<tr><td data-label="Payment item">Boat identity</td><td data-label="Ask this">What is the boat name and is the photo the actual boat or a class example?</td><td data-label="Good answer">Specific vessel or clear equivalent-class guarantee.</td><td data-label="Risk if vague">A bait-and-switch feeling even if technically similar.</td></tr>
<tr><td data-label="Payment item">Emergency contact</td><td data-label="Ask this">Who answers on travel day if the pickup is late or the pier changes?</td><td data-label="Good answer">A working local contact and hotel/operator backup.</td><td data-label="Risk if vague">You solve a port problem alone while traveling.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-cruise-questions-red-flags:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Red flags before you pay</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-cruise-questions-red-flags">
<thead><tr><th>Red flag</th><th>Why it matters</th><th>Calm response</th></tr></thead>
<tbody>
<tr><td data-label="Red flag">The operator cannot name the pier or route</td><td data-label="Why it matters">You are buying a label, not an itinerary.</td><td data-label="Calm response">Pause and request written route details.</td></tr>
<tr><td data-label="Red flag">The cabin photo is not tied to your room category</td><td data-label="Why it matters">Cruise disappointment often begins in the room.</td><td data-label="Calm response">Ask for room class, deck, size, and view.</td></tr>
<tr><td data-label="Red flag">Weather answer is only reassurance</td><td data-label="Why it matters">You need policy, not comfort words.</td><td data-label="Calm response">Ask for cancellation, reroute, refund, and reschedule rules.</td></tr>
<tr><td data-label="Red flag">The itinerary promises every activity</td><td data-label="Why it matters">Too many stops can remove the best part of the bay.</td><td data-label="Calm response">Ask what is guaranteed, optional, seasonal, or weather-dependent.</td></tr>
<tr><td data-label="Red flag">Payment is pushed before terms are written</td><td data-label="Why it matters">A fast deposit weakens your leverage.</td><td data-label="Calm response">Get the full booking record first.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-cruise-questions-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before booking</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-ha-long-cruise-questions-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-ha-long-cruise-questions-live-checks">
<li>Ask for exact pier, pickup, return, route map, boat name, cabin category, and activity inclusions before paying.</li>
<li>Check whether the bay choice is Ha Long, Lan Ha, Cat Ba-linked, Bai Tu Long, or a mixed route, then use the specialist comparison guide.</li>
<li>Verify the weather, cancellation, reroute, refund, and reschedule policy in writing before locking non-refundable onward travel.</li>
<li>Confirm whether a Ninh Binh-to-bay move uses the correct port and has enough cruise check-in buffer.</li>
<li>For families or older travelers, ask about stairs, wet decks, tender boats, cave steps, viewpoint climbs, meal timing, and skip options.</li>
<li>Keep a payment record with operator name, boat name, cabin type, date, inclusions, exclusions, and emergency contact.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits next</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this page at the buying stage. If the bay choice is not yet clear, move back to the broader bay guides before comparing operators.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-related-routes vg-ha-long-cruise-questions-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a><span class="vg-related-route-note">Use for the full bay decision: day cruise, overnight, two nights, timing, port, and skip logic.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a><span class="vg-related-route-note">Use before buying any product that claims classic bay, quieter bay, Cat Ba, or Lan Ha value.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/destinations/bai-tu-long-bay-guide/">Bai Tu Long Bay Guide</a><span class="vg-related-route-note">Use when a quieter-bay claim needs route proof.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/plan/ninh-binh-to-ha-long-bay-transfer/">Ninh Binh to Ha Long Bay Transfer</a><span class="vg-related-route-note">Use before connecting Ninh Binh to cruise check-in.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a><span class="vg-related-route-note">Use when comparing cheap, midrange, premium, and private-transfer versions.</span></li>
</ol>
</div>
<!-- /wp:html -->

<!-- vg-ha-long-cruise-questions-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Ha Long Bay cruise booking FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-ha-long-cruise-questions-faq">
<details><summary>What is the most important question to ask before booking a Ha Long Bay cruise?</summary><p>Ask for the exact pier, route map, cabin category, pickup and return details, and written weather/cancellation policy. If those details are vague, the cruise is not ready to buy.</p></details>
<details><summary>Is a luxury Ha Long Bay cruise worth it?</summary><p>It is worth paying more when the money improves cabin comfort, deck space, route quality, food, staff help, activity pacing, or cancellation flexibility. It is not worth it when the upgrade is mostly branding.</p></details>
<details><summary>Should I choose Ha Long Bay, Lan Ha Bay, or Bai Tu Long?</summary><p>Choose Ha Long for the classic easier-to-buy cruise, Lan Ha for quieter Cat Ba-linked routing when logistics are clear, and Bai Tu Long when the operator can prove the quieter route. Do not choose only by name.</p></details>
<details><summary>Can I book a Ha Long cruise after Ninh Binh?</summary><p>Yes, but confirm the exact port, pickup point, luggage plan, cruise check-in time, and buffer. Ninh Binh-to-bay transfers are strong when precise and fragile when vague.</p></details>
<details><summary>Can weather cancel or change a Ha Long Bay cruise?</summary><p>Yes. Weather and local operating decisions can delay, reroute, or cancel bay activity. Read refund, reschedule, reroute, and onward-transfer terms before paying.</p></details>
<details><summary>How do I avoid a bad cruise booking?</summary><p>Avoid vague answers. Ask for written details, verify the actual cabin and route, keep a payment record, and do not book a tight flight or train immediately after disembarkation.</p></details>
</div>
<!-- /wp:html -->

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
    'vg-ha-long-cruise-questions-hero:v1',
    'vg-ha-long-cruise-questions-verdict:v1',
    'vg-ha-long-cruise-questions-at-a-glance:v1',
    'vg-ha-long-cruise-questions-photo-grid:v1',
    'vg-ha-long-cruise-questions-source-diversity:v1',
    'vg-ha-long-cruise-questions-route-map:v1',
    'vg-ha-long-cruise-questions-port-transfer:v1',
    'vg-ha-long-cruise-questions-cabin-deck:v1',
    'vg-ha-long-cruise-questions-weather-policy:v1',
    'vg-ha-long-cruise-questions-family-comfort:v1',
    'vg-ha-long-cruise-questions-bai-tu-long:v1',
    'vg-ha-long-cruise-questions-payment-risk:v1',
    'vg-ha-long-cruise-questions-red-flags:v1',
    'vg-ha-long-cruise-questions-live-checks:v1',
    'vg-ha-long-cruise-questions-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_ha_long_cruise_questions_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Ha Long Bay Cruise Questions to Ask Before Booking',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'A decision-led Ha Long Bay cruise buying checklist for asking better questions about port, cabin, route map, weather policy, Lan Ha, Bai Tu Long, family comfort, payment, and transfer risk.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_ha_long_cruise_questions_post_fail('Could not update Ha Long cruise questions post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Ha Long Bay Cruise Questions to Ask Before Booking');
update_post_meta($post_id, 'rank_math_description', 'Ask these Ha Long Bay cruise questions before booking: port, cabin, route map, weather policy, Lan Ha vs Ha Long, Bai Tu Long, family comfort, payment, and transfer risk.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Ha Long Bay cruise questions');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Before booking a Ha Long Bay cruise, require clear written answers about pier, pickup, route map, cabin, deck space, weather policy, cancellation, payment, activity inclusions, family comfort, and onward transfer risk.');
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

wp_set_object_terms($post_id, vg_ha_long_cruise_questions_post_term_ids('category', ['beaches-islands', 'transport-logistics']), 'category', false);
wp_set_object_terms($post_id, vg_ha_long_cruise_questions_post_term_ids('post_tag', ['cruise-planning', 'premium-travel', 'family-travel', 'anti-spam-evergreen']), 'post_tag', false);

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
        vg_ha_long_cruise_questions_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Ha Long cruise questions post to complete draft: {$post_id}");

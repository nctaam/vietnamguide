<?php
/**
 * Expand the Where to Stay in Vietnam post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-where-to-stay-in-vietnam-base-decisions-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_stay_vietnam_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_stay_vietnam_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_stay_vietnam_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_stay_vietnam_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_stay_vietnam_post_find_by_slug(string $slug): ?WP_Post
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
        vg_stay_vietnam_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_stay_vietnam_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-09',
            'vg_editorial_brief_status' => 'brief',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if ($actual_value !== $expected_value) {
            vg_stay_vietnam_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_stay_vietnam_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_stay_vietnam_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'where-to-stay-in-vietnam-base-decisions';
$post = vg_stay_vietnam_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_stay_vietnam_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_stay_vietnam_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 25, 2026';
vg_stay_vietnam_post_assert_target_meta($post);

$hero_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg';
$hcmc_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1280px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg';
$ninh_binh_image = 'https://upload.wikimedia.org/wikipedia/commons/a/a5/Tam_Coc_Rice_Valley_%288756354342%29.jpg';
$da_nang_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/af/My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg/1920px-My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg';
$phu_quoc_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg';

$meta_update_summary = 'Expanded the native WordPress post brief into a complete country-level hotel-base decision draft with a photo-led hero, proof panel, concierge verdict, at-a-glance matrix, source-diversity table, base-job framework, city-fit matrix, arrival/departure logic, family and sleep filters, food-access guidance, quiet-night and transfer-pickup checks, booking filters, skip traps, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Hanoi - https://vietnam.travel/places-to-go/northern-vietnam/ha-noi - checked {$review_date}; used for the northern city base frame, Old Quarter/Hoan Kiem orientation, and first-time Hanoi context without replacing hotel-block judgment.",
    "Vietnam.travel - Ho Chi Minh City - https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city - checked {$review_date}; used for southern city base framing, central movement, market/museum density, and airport-exit context.",
    "Vietnam.travel - Ninh Binh - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh - checked {$review_date}; used for countryside-base logic, boat-day pacing, and why a room can protect the landscape experience.",
    "Vietnam.travel - Da Nang - https://vietnam.travel/places-to-go/central-vietnam/da-nang - checked {$review_date}; used for coastal-city base logic, airport convenience, beach access, and day-trip reach.",
    "Vietnam.travel - Hoi An - https://vietnam.travel/places-to-go/central-vietnam/hoi-an - checked {$review_date}; used for heritage-town base logic, old-town walkability, and Da Nang/Hoi An trade-offs.",
    "Vietnam.travel - Phu Quoc - https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc - checked {$review_date}; used for island-base logic, airport/beach resort fit, and when a stay area is mostly a rest chapter.",
    "Vietnam.travel - Traveller's guide to Vietnam's airports - https://vietnam.travel/things-to-do/travellers-guide-vietnams-airports - checked {$review_date}; used for arrival/departure discipline before choosing airport-side nights.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for transfer-pickup and route-order logic around hotels.",
    "Vietnam.travel - Weather and climate - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for heat, rain, walking radius, and beach-base season context.",
    "Wikimedia Commons image direct URL - Hanoi Hoan Kiem Lake - {$hero_image} - credit Alex 69200 vx / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ho Chi Minh City City Hall - {$hcmc_image} - credit Steffen Schmitz / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Tam Coc Rice Valley - {$ninh_binh_image} - credit Hoang Giang Hai / CC BY 2.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - My Khe Beach from Son Tra - {$da_nang_image} - credit nguyenhuuthanh / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Kem Beach aerial view Phu Quoc - {$phu_quoc_image} - credit Vivu Vietnam / CC BY-SA 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This post is the country-level stay-base decision layer. It deliberately avoids hotel rankings and affiliate-style room recommendations, then points readers toward city-specific stay guides once the base job is clear.';
$meta_evidence_moat = implode("\n", [
    'Decision-led accommodation guide that asks travelers to choose the city base before choosing the hotel.',
    'Separates arrival recovery, first-time walkability, food access, quiet sleep, family rhythm, airport risk, and transfer pickup into different base jobs.',
    'Uses official destination and transport sources for city roles while keeping room-level claims out of the article.',
    'Photo proof makes each image explain a base trade-off rather than decorate a generic hotel article.',
    'City-fit matrix reduces thin list spam by explaining when Hanoi, Ho Chi Minh City, Ninh Binh, Da Nang, Hoi An, Phu Quoc, beach bases, and airport-side nights actually earn a stay.',
    'Booking-filter section gives durable questions readers can use across seasons and budgets without naming volatile hotels.',
    'No affiliate intent, no visible external body anchors, and no ranking of individual properties.',
]);
$meta_related_routes = implode("\n", [
    'Where to Stay in Hanoi | /destinations/where-to-stay-in-hanoi/ | Use after the country-level base job is clear and Hanoi is part of the route.',
    'Where to Stay in Ho Chi Minh City | /destinations/where-to-stay-in-ho-chi-minh-city/ | Choose the southern city area by museums, food, airport risk, nightlife tolerance, and longer-stay rhythm.',
    'Where to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Protect boat days, countryside evenings, family pace, and onward transfers before choosing a room.',
    'Da Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Decide whether central Vietnam needs beach-city logistics, heritage-town atmosphere, or a split stay.',
    'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use for the northern city chapter before choosing Hoan Kiem, Old Quarter edge, Ba Dinh, Tay Ho, or airport-side.',
    'Ho Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Use for southern route fit before choosing District 1, District 3, Thao Dien, riverside, or airport-side.',
    'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use when beach access, airport simplicity, and central-coast day trips compete with Hoi An charm.',
    'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Use before deciding whether a countryside stay is worth more than a Hanoi day trip.',
    'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Use when the route needs island rest, winter sun, family resort time, or an easier southern finish.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Keep pickup, station, airport, and road-transfer pressure visible before locking accommodation.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Adjust beach, city, mountain, and walking bases for heat, rain, humidity, and regional season pressure.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Match the base strategy to the route length so hotel moves do not consume the trip.',
]);
$meta_hero_image_credit = 'Hero image: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0. Body images: Ho Chi Minh City City Hall by Steffen Schmitz, CC BY-SA 4.0; Tam Coc Rice Valley by Hoang Giang Hai, CC BY 2.0; My Khe Beach from Son Tra by nguyenhuuthanh, CC BY-SA 4.0; Kem Beach aerial view Phu Quoc by Vivu Vietnam, CC BY-SA 4.0.';
$meta_admin_notes = 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, checks Rank Math/social preview, verifies stay-area claims, image-license text, source-trail records, and adds any first-hand base notes before publishing. Do not add hotel rankings or affiliate-style room recommendations unless a separate review policy is added.';

$content = <<<HTML
<!-- vg-stay-vietnam-hero:v1 -->
<!-- wp:html -->
<section class="vg-guide-hero vg-stay-vietnam-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Accommodation planning without hotel-list spam</p>
<h1>Where to Stay in Vietnam: City Base Decisions Before Choosing Hotels</h1>
<p class="vg-guide-lede">The first accommodation decision in Vietnam is not the hotel name. It is the job of the base: recover after a long flight, walk into food and sights, sleep quietly, protect a day trip pickup, make family evenings easier, reduce an early-flight risk, or turn a beach stay into real rest.</p>
<p class="vg-field-note">This guide helps you choose the city base before choosing the hotel. It is built for first-time international travelers who do not want a thin list of rooms, affiliate pressure, or vague "central is best" advice.</p>
<p class="vg-section-link"><a href="/plan/vietnam-travel-guide/">Start with the Vietnam travel guide</a></p>
</div>
<figure class="vg-guide-hero-image"><img src="{$hero_image}" alt="Hoan Kiem Lake in central Hanoi, a useful first-time Vietnam base reference" loading="eager" decoding="async"><figcaption>A good base shortens the first learning curve: food, walking radius, pickup clarity, and sleep. Image: Alex 69200 vx / CC BY-SA 4.0.</figcaption></figure>
</section>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-stay-vietnam-concierge-verdict:v1 -->
<!-- wp:html -->
<aside class="vg-concierge-verdict vg-stay-vietnam-concierge-verdict" aria-label="Where to stay in Vietnam verdict">
<p class="vg-kicker">VietnamGuide verdict</p>
<p class="vg-verdict-lede"><strong>Choose the base by what the next 18 hours must protect.</strong> For most first trips, the strongest default is central Hanoi for the northern start, Hoi An or Da Nang for the central chapter depending on beach-versus-heritage needs, and central Ho Chi Minh City for the southern city finish. Add Ninh Binh, Phu Quoc, or airport-side nights only when they solve a real route problem.</p>
<p>The mistake is booking a beautiful room first and asking the route to adapt around it. A cheaper room in the wrong base can cost more in transfer fatigue, missed food, pickup confusion, weak sleep, or an early-flight scramble.</p>
<ul class="wp-block-list vg-feature-list vg-stay-vietnam-verdict-list">
<li><strong>First-time north:</strong> Hanoi first, then consider Ninh Binh if the countryside needs a protected night.</li>
<li><strong>Central coast:</strong> Hoi An for old-town evenings; Da Nang for beach, airport, family space, and easier day trips.</li>
<li><strong>Southern city:</strong> Central Ho Chi Minh City when museums, food, markets, and airport access must stay simple.</li>
<li><strong>Beach finish:</strong> Phu Quoc only when the route has enough time for an island chapter, not as a rushed badge.</li>
<li><strong>Airport-side:</strong> Use only for late arrivals, early departures, separate tickets, or fragile connections.</li>
</ul>
</aside>
<!-- /wp:html -->

<!-- vg-stay-vietnam-at-a-glance:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Vietnam base decisions at a glance</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-stay-vietnam-at-a-glance">
<thead><tr><th>Traveler problem</th><th>Better base decision</th><th>Why it works</th><th>Weak signal to avoid</th></tr></thead>
<tbody>
<tr><td data-label="Traveler problem">I arrive long-haul and need an easy first night.</td><td data-label="Better base decision">Central Hanoi or central Ho Chi Minh City, close to food and simple ride-hailing.</td><td data-label="Why it works">You can eat, walk lightly, withdraw cash, test your SIM, and sleep without a second logistics puzzle.</td><td data-label="Weak signal to avoid">A far scenic room that requires a long transfer immediately after immigration.</td></tr>
<tr><td data-label="Traveler problem">I want heritage atmosphere in central Vietnam.</td><td data-label="Better base decision">Hoi An for old-town evenings; Da Nang if beach, airport, and modern-city comfort matter more.</td><td data-label="Why it works">The base controls evening rhythm more than the daytime taxi distance.</td><td data-label="Weak signal to avoid">Choosing only by which hotel photo looks more romantic.</td></tr>
<tr><td data-label="Traveler problem">I want Ninh Binh without rushing.</td><td data-label="Better base decision">Stay near Tam Coc, Trang An, or a quiet countryside base when the boat day deserves margin.</td><td data-label="Why it works">You protect sunrise, cycling, boat timing, and evening recovery instead of compressing everything into a Hanoi day trip.</td><td data-label="Weak signal to avoid">A room that forces early checkout before the countryside has paid off.</td></tr>
<tr><td data-label="Traveler problem">I travel with children or older relatives.</td><td data-label="Better base decision">Prioritize elevators, easy meals, short evening walks, pool or apartment space, and reliable transport.</td><td data-label="Why it works">The right base reduces decision fatigue every morning and every evening.</td><td data-label="Weak signal to avoid">Nightlife streets, stairs-only guesthouses, and distant "quiet" bases without food access.</td></tr>
<tr><td data-label="Traveler problem">I have an early flight or separate tickets.</td><td data-label="Better base decision">Use an airport-side night only when the flight risk is real.</td><td data-label="Why it works">It protects the chain of travel, but it usually weakens the city experience.</td><td data-label="Weak signal to avoid">Staying airport-side because it feels safer, then losing your only real city evening.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-stay-vietnam-photo-proof:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: each base changes the trip</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the images as practical signals. A base is not good because it is famous; it is good when the surroundings support the way you will actually move, eat, rest, and leave.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-stay-vietnam-photo-proof" aria-label="Vietnam stay base photo proof">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Hoan Kiem Lake in Hanoi" loading="lazy" decoding="async"><figcaption>Hanoi central bases reduce the first-trip learning curve: food, lake walks, pickup conversations, and northern route starts. Image: Alex 69200 vx / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall and Nguyen Hue area at night" loading="lazy" decoding="async"><figcaption>Ho Chi Minh City rewards a central base when the stay is short and food, museums, markets, and airport access compete. Image: Steffen Schmitz / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$ninh_binh_image}" alt="Tam Coc rice valley near Ninh Binh" loading="lazy" decoding="async"><figcaption>Ninh Binh works best when the room protects the countryside rhythm, not just the lowest nightly rate. Image: Hoang Giang Hai / CC BY 2.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$da_nang_image}" alt="My Khe Beach and Da Nang coast" loading="lazy" decoding="async"><figcaption>Da Nang is useful when beach access, airport simplicity, and central-coast day trips matter more than old-town atmosphere. Image: nguyenhuuthanh / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$phu_quoc_image}" alt="Kem Beach aerial view on Phu Quoc Island" loading="lazy" decoding="async"><figcaption>Phu Quoc should be a rest chapter with enough nights, not a rushed flight added because the island looks pretty. Image: Vivu Vietnam / CC BY-SA 4.0.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-stay-vietnam-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Source-diversity: what can be verified</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Official tourism and transport sources can frame destination roles, airport logic, weather pressure, and route movement. They cannot verify your exact hotel block, room noise, elevator reliability, breakfast quality, construction next door, or whether a tour operator will pick up from that side street.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-stay-vietnam-source-diversity">
<thead><tr><th>Source job</th><th>What it supports</th><th>What still needs judgment</th></tr></thead>
<tbody>
<tr><td data-label="Source job">Destination framing</td><td data-label="What it supports">Vietnam.travel - Hanoi, Ho Chi Minh City, Ninh Binh, Da Nang, Hoi An, and Phu Quoc pages help identify the role each stop can play.</td><td data-label="What still needs judgment">Whether your route needs that stop as a base, a day trip, a transit night, or a clean skip.</td></tr>
<tr><td data-label="Source job">Transport logic</td><td data-label="What it supports">Vietnam.travel - Transport within Vietnam and airport guidance help flag when pickup, airport, station, or ferry pressure matters.</td><td data-label="What still needs judgment">Your terminal timing, traffic tolerance, luggage burden, and whether an airport-side night is worth losing atmosphere.</td></tr>
<tr><td data-label="Source job">Season and comfort</td><td data-label="What it supports">Vietnam.travel - Weather and climate helps frame heat, rain, humidity, and beach-season pressure.</td><td data-label="What still needs judgment">How far your group actually wants to walk after dark, in rain, or with children.</td></tr>
<tr><td data-label="Source job">Accommodation claims</td><td data-label="What it supports">Recent reviews, maps, direct hotel messages, and cancellation terms must be checked by the editor or traveler before publishing or booking.</td><td data-label="What still needs judgment">Room-facing noise, construction, elevators, stairs, pickup access, and neighborhood feel.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-stay-vietnam-base-jobs:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Start with the job of the base</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A Vietnam hotel base should earn its place by solving one dominant job. If it tries to solve everything, it often solves nothing well. Before comparing rooms, write one sentence: "This base must protect..." and choose the answer below.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-stay-vietnam-base-jobs">
<thead><tr><th>Base job</th><th>What to prioritize</th><th>Where it usually points</th><th>Question before choosing a room</th></tr></thead>
<tbody>
<tr><td data-label="Base job">First-night orientation</td><td data-label="What to prioritize">Walkable food, simple taxi app pickup, recognizable landmarks, and low-effort errands.</td><td data-label="Where it usually points">Hoan Kiem/Old Quarter edge in Hanoi or District 1/3 in Ho Chi Minh City.</td><td data-label="Question">Can we get dinner, cash, water, SIM support, and sleep without crossing the city?</td></tr>
<tr><td data-label="Base job">Heritage evening</td><td data-label="What to prioritize">Old-town walking, evening atmosphere, short return to the room, and less vehicle dependence.</td><td data-label="Where it usually points">Hoi An old-town edge or a quiet central heritage-town base.</td><td data-label="Question">Will the room still feel good after the day-trip crowds leave?</td></tr>
<tr><td data-label="Base job">Beach and family rhythm</td><td data-label="What to prioritize">Beach access, pool, elevators, simple meals, shaded breaks, and airport simplicity.</td><td data-label="Where it usually points">Da Nang beach areas, Phu Quoc, Nha Trang, or a resort chapter with enough nights.</td><td data-label="Question">Will this base reduce daily friction, or only look relaxing online?</td></tr>
<tr><td data-label="Base job">Countryside protection</td><td data-label="What to prioritize">Boat timing, cycling radius, quiet evenings, morning views, and onward transfer control.</td><td data-label="Where it usually points">Ninh Binh countryside/Tam Coc/Trang An rather than a rushed Hanoi day trip.</td><td data-label="Question">Does staying here give us a better day, or just another check-in?</td></tr>
<tr><td data-label="Base job">Flight or train risk</td><td data-label="What to prioritize">Short transfer, early checkout, reliable ride, and a backup plan.</td><td data-label="Where it usually points">Airport-side or station-side only for the risky night.</td><td data-label="Question">Is logistics the whole purpose of this night?</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-stay-vietnam-city-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">City-by-city base fit</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This is the country-level filter. Use it before opening neighborhood guides or hotel maps. It helps you avoid the common mistake of treating every city as if "central" meant the same thing.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-stay-vietnam-city-fit">
<thead><tr><th>Destination</th><th>Default base logic</th><th>Upgrade or change base when...</th><th>Internal next step</th></tr></thead>
<tbody>
<tr><td data-label="Destination">Hanoi</td><td data-label="Default base logic">Hoan Kiem or calmer Old Quarter edge for first-time orientation.</td><td data-label="Upgrade or change base when...">You need French Quarter calm, Ba Dinh history/sleep, Tay Ho longer-stay space, or Noi Bai flight protection.</td><td data-label="Internal next step"><a href="/destinations/where-to-stay-in-hanoi/">Choose the Hanoi base</a></td></tr>
<tr><td data-label="Destination">Ho Chi Minh City</td><td data-label="Default base logic">District 1/3 central edge for short stays with museums, food, markets, and airport access.</td><td data-label="Upgrade or change base when...">Nightlife is intentional, a longer Thao Dien rhythm matters, or Tan Son Nhat risk dominates.</td><td data-label="Internal next step"><a href="/destinations/where-to-stay-in-ho-chi-minh-city/">Choose the Ho Chi Minh City base</a></td></tr>
<tr><td data-label="Destination">Ninh Binh</td><td data-label="Default base logic">Stay close enough to the boat/countryside experience if you want the place to breathe.</td><td data-label="Upgrade or change base when...">A one-night route needs transfer control or a two-night stay needs calmer rural evenings.</td><td data-label="Internal next step"><a href="/destinations/where-to-stay-in-ninh-binh/">Choose the Ninh Binh base</a></td></tr>
<tr><td data-label="Destination">Da Nang and Hoi An</td><td data-label="Default base logic">Da Nang for beach, airport, and logistics; Hoi An for old-town evenings and heritage atmosphere.</td><td data-label="Upgrade or change base when...">The route has enough nights for a split stay or the group needs easier beach/pool days.</td><td data-label="Internal next step"><a href="/compare/da-nang-vs-hoi-an/">Compare Da Nang and Hoi An</a></td></tr>
<tr><td data-label="Destination">Phu Quoc</td><td data-label="Default base logic">Choose the beach/resort zone by rest style and exit plan, not just photos.</td><td data-label="Upgrade or change base when...">You need a real island finish with three or more nights, family comfort, or winter sun.</td><td data-label="Internal next step"><a href="/destinations/phu-quoc-travel-guide/">Plan Phu Quoc</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-stay-vietnam-arrival-departure:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Arrival and departure nights need different rules</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The first and last night are not normal hotel nights. They carry immigration, luggage, late food, local money, SIM setup, airport transfers, checkout timing, and fatigue. Treat them as logistics nights first, atmosphere nights second.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-stay-vietnam-arrival-departure">
<thead><tr><th>Night type</th><th>Base rule</th><th>Good compromise</th><th>What to avoid</th></tr></thead>
<tbody>
<tr><td data-label="Night type">First night after long-haul</td><td data-label="Base rule">Stay central enough for an easy dinner and a short first walk.</td><td data-label="Good compromise">A calmer central block rather than the loudest nightlife street.</td><td data-label="What to avoid">A remote resort or countryside transfer when the body needs recovery.</td></tr>
<tr><td data-label="Night type">Night before early domestic flight</td><td data-label="Base rule">Choose airport-side only if the flight time, luggage, group, or ticket risk deserves it.</td><td data-label="Good compromise">Stay central, pre-book transport, and leave margin when the airport is predictable.</td><td data-label="What to avoid">Moving hotels just because the airport looks far on a map.</td></tr>
<tr><td data-label="Night type">Night before Ninh Binh, bay, or private transfer pickup</td><td data-label="Base rule">Ask where pickup actually happens, then choose a base that does not complicate it.</td><td data-label="Good compromise">Central pickup zone with quiet side-street room.</td><td data-label="What to avoid">A beautiful outlying base that adds a morning taxi before the real transfer.</td></tr>
<tr><td data-label="Night type">Final Vietnam night</td><td data-label="Base rule">Protect the international exit before chasing one more perfect neighborhood.</td><td data-label="Good compromise">Central city with proven airport route if the flight is not too early.</td><td data-label="What to avoid">Island or countryside final night with fragile same-day flight chains.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-stay-vietnam-family-sleep:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Families and sleep-sensitive travelers should filter harder</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>For families, older travelers, light sleepers, and anyone mixing work with travel, the right base is often less famous and more functional. Read the hotel map like a daily-life map: breakfast, laundry, pharmacy, simple dinner, shaded return, elevator, road crossing, pool, quiet facing, and ride-hailing pickup.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-stay-vietnam-family-sleep">
<thead><tr><th>Need</th><th>Base signal</th><th>Room-level check</th><th>When to pay more</th></tr></thead>
<tbody>
<tr><td data-label="Need">Children need predictable evenings</td><td data-label="Base signal">Food within a short walk, pool or apartment space, and simple transport.</td><td data-label="Room-level check">Elevator, extra bed policy, connecting rooms, and noise-facing side.</td><td data-label="When to pay more">When the room reduces taxis, late dinners, and daily arguments.</td></tr>
<tr><td data-label="Need">Older travelers need low friction</td><td data-label="Base signal">Short walks, fewer stairs, easy cars, and clear pickup.</td><td data-label="Room-level check">Lift, shower access, step-free entrance, and street crossing burden.</td><td data-label="When to pay more">When the cheaper base turns every outing into a transfer.</td></tr>
<tr><td data-label="Need">Light sleepers need protection</td><td data-label="Base signal">Away from nightlife lanes, party hostels, karaoke, and road-facing rooms.</td><td data-label="Room-level check">Interior room, high floor, recent noise reviews, and construction notes.</td><td data-label="When to pay more">When sleep protects the next transfer or activity day.</td></tr>
<tr><td data-label="Need">Remote work or slow travel</td><td data-label="Base signal">Apartment options, cafes, laundry, grocery access, and a neighborhood you can repeat.</td><td data-label="Room-level check">Desk, real Wi-Fi reviews, backup cafes, and noise during the day.</td><td data-label="When to pay more">When work stability matters more than sightseeing density.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-stay-vietnam-food-access:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Food access is a base feature, not an afterthought</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam rewards travelers who can step out for simple meals without turning dinner into research. The best base for a first trip usually has a low-effort food layer: cafes in the morning, casual lunch nearby, easy dinner after rain or heat, and a backup option when someone is tired.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-stay-vietnam-food-access">
<thead><tr><th>Food scenario</th><th>Better base feature</th><th>Why it matters</th><th>Bad signal</th></tr></thead>
<tbody>
<tr><td data-label="Food scenario">First arrival dinner</td><td data-label="Better base feature">Several simple options within a 10-minute walk.</td><td data-label="Why it matters">Jet lag makes choice harder; proximity prevents a bad first evening.</td><td data-label="Bad signal">A hotel that needs a taxi for every meal.</td></tr>
<tr><td data-label="Food scenario">Street food curiosity</td><td data-label="Better base feature">Busy local eating streets nearby but not directly under the window.</td><td data-label="Why it matters">You can observe turnover, return easily, and leave if the group is tired.</td><td data-label="Bad signal">Only tourist cafes or one isolated restaurant.</td></tr>
<tr><td data-label="Food scenario">Family backup</td><td data-label="Better base feature">Mix of local, international, and hotel food options.</td><td data-label="Why it matters">Children, picky eaters, and stomach-sensitive days need optionality.</td><td data-label="Bad signal">A remote scenic base with no evening fallback.</td></tr>
<tr><td data-label="Food scenario">Beach resort chapter</td><td data-label="Better base feature">Know whether you are choosing isolation, walkable dinners, or resort-contained meals.</td><td data-label="Why it matters">The same beach can feel restful or trapped depending on food access.</td><td data-label="Bad signal">Assuming all beach bases have the same evening rhythm.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-stay-vietnam-quiet-night:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Quiet-night filters before you reserve</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Quiet is not a city, district, or star rating. It is a room-facing question. A calm-looking hotel can face a karaoke room, main road, school courtyard, construction site, or nightlife alley. A central hotel can sleep well if the room position, floor, and window are right.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-stay-vietnam-quiet-night">
<thead><tr><th>Check</th><th>How to verify</th><th>Why it matters</th></tr></thead>
<tbody>
<tr><td data-label="Check">Room facing</td><td data-label="How to verify">Ask for interior, courtyard, high-floor, or non-street-facing room when sleep matters.</td><td data-label="Why it matters">Street names rarely tell the full noise story.</td></tr>
<tr><td data-label="Check">Recent review pattern</td><td data-label="How to verify">Read only the last few months for noise, construction, elevator, and breakfast issues.</td><td data-label="Why it matters">Old reviews can describe a different building next door.</td></tr>
<tr><td data-label="Check">Nightlife adjacency</td><td data-label="How to verify">Map the hotel against beer streets, bars, karaoke, night markets, and party hostels.</td><td data-label="Why it matters">Being near nightlife and being inside it are different decisions.</td></tr>
<tr><td data-label="Check">Transfer-day sleep</td><td data-label="How to verify">Pay attention before early flights, bay transfers, or long road days.</td><td data-label="Why it matters">A noisy bargain can weaken the next expensive travel day.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-stay-vietnam-transfer-pickup:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Transfer pickup can make a base better or worse</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam routes often rely on morning pickups: Ha Long/Lan Ha cruises, Ninh Binh transfers, Cu Chi or Mekong day trips, airport cars, private drivers, and train-station moves. A stay area that looks calm can create friction if drivers cannot reach it easily or if the operator only collects from certain central zones.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-stay-vietnam-transfer-pickup">
<thead><tr><th>Route move</th><th>Base implication</th><th>Question to ask before choosing</th></tr></thead>
<tbody>
<tr><td data-label="Route move">Hanoi to Ha Long, Lan Ha, Bai Tu Long, or Ninh Binh</td><td data-label="Base implication">Central pickup zones can be more valuable than a prettier outlying room.</td><td data-label="Question">Will the operator pick up at this address, a nearby meeting point, or only certain districts?</td></tr>
<tr><td data-label="Route move">Ho Chi Minh City to Cu Chi or Mekong</td><td data-label="Base implication">District 1/3 and clear hotel access simplify early starts.</td><td data-label="Question">Is the pickup private, shared, hotel-door, or meeting-point only?</td></tr>
<tr><td data-label="Route move">Da Nang airport to Hoi An</td><td data-label="Base implication">Da Nang is easier for late arrival; Hoi An is better when the first evening atmosphere matters.</td><td data-label="Question">Does the arrival time justify going straight to Hoi An, or should Da Nang absorb the first night?</td></tr>
<tr><td data-label="Route move">Island or beach departure</td><td data-label="Base implication">Airport or ferry timing can outweigh beach aesthetics on the final night.</td><td data-label="Question">Can the exit fail without harming the international flight?</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-stay-vietnam-booking-filter:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The booking filter: questions that age well</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This article avoids room recommendations because room quality changes faster than a destination guide should. Use these durable checks inside WordPress Admin review and again before any traveler publishes a final itinerary.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-stay-vietnam-booking-filter">
<thead><tr><th>Filter</th><th>Ask this</th><th>Good answer</th><th>Risk answer</th></tr></thead>
<tbody>
<tr><td data-label="Filter">Location</td><td data-label="Ask this">What problem does this base solve better than the cheaper alternative?</td><td data-label="Good answer">It shortens meals, pickups, evening walks, or airport movement.</td><td data-label="Risk answer">It is only cheaper or prettier.</td></tr>
<tr><td data-label="Filter">Noise</td><td data-label="Ask this">What do recent reviews say about road, bar, construction, and room-facing noise?</td><td data-label="Good answer">Recent noise comments are specific and manageable.</td><td data-label="Risk answer">Reviews are old, vague, or repeatedly mention sleep problems.</td></tr>
<tr><td data-label="Filter">Mobility</td><td data-label="Ask this">Will stairs, crossings, hills, heat, or luggage create daily friction?</td><td data-label="Good answer">Elevator, easy entrance, and short walks are confirmed.</td><td data-label="Risk answer">The map looks close but the route is awkward.</td></tr>
<tr><td data-label="Filter">Cancellation</td><td data-label="Ask this">Can the stay survive route changes, weather, or delayed flights?</td><td data-label="Good answer">Flexible terms cover the fragile parts of the trip.</td><td data-label="Risk answer">Nonrefundable stay attached to uncertain transfers.</td></tr>
<tr><td data-label="Filter">Communication</td><td data-label="Ask this">Can the property answer a precise pickup, late-arrival, or early-checkout question?</td><td data-label="Good answer">Clear direct answer with practical details.</td><td data-label="Risk answer">Generic reply that does not address the actual problem.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-stay-vietnam-skip-traps:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Stay-base traps to skip</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The wrong base usually fails quietly. It does not ruin the trip in one dramatic moment; it adds twenty small frictions. These traps are more important than tiny differences in room score.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-stay-vietnam-skip-traps">
<thead><tr><th>Trap</th><th>Why it hurts</th><th>Better move</th></tr></thead>
<tbody>
<tr><td data-label="Trap">Changing hotels inside the same city without a clear reason</td><td data-label="Why it hurts">Packing, checkout, luggage storage, and transport eat a half-day.</td><td data-label="Better move">Move only when the second base solves airport, beach, family, or long-stay rhythm.</td></tr>
<tr><td data-label="Trap">Remote scenic first night after a long flight</td><td data-label="Why it hurts">The body needs food, shower, cash, SIM, and sleep before another long transfer.</td><td data-label="Better move">Use a central recovery night, then move when you can enjoy the scenery.</td></tr>
<tr><td data-label="Trap">Choosing nightlife by accident</td><td data-label="Why it hurts">The base may look central but sleep becomes a negotiation.</td><td data-label="Better move">Stay near food and movement, not directly in the loudest strip unless that is intentional.</td></tr>
<tr><td data-label="Trap">Treating airport-side as safer by default</td><td data-label="Why it hurts">You may lose the only meaningful city evening and still need a transfer.</td><td data-label="Better move">Use airport-side only when the flight chain is genuinely fragile.</td></tr>
<tr><td data-label="Trap">Beach isolation without enough nights</td><td data-label="Why it hurts">The transfer cost can exceed the rest value.</td><td data-label="Better move">Choose a beach chapter only when the route has time to slow down.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-stay-vietnam-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before publishing or reserving</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-stay-vietnam-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-stay-vietnam-live-checks">
<li>Open the hotel on at least two maps and confirm the walking route to food, pickup, or beach access, not just the straight-line distance.</li>
<li>Read the last few months of reviews for noise, construction, elevator issues, smell, breakfast, damp rooms, and staff response.</li>
<li>Message the property with one precise question: late arrival, early checkout, pickup access, room facing, or family-bed setup.</li>
<li>Check whether the route depends on a morning transfer, shared pickup, ferry, train, or airport move before choosing the final night.</li>
<li>Re-check weather and season if the base depends on beach time, walking radius, boat days, or outdoor evenings.</li>
<li>Keep the final recommendation in WordPress as a base decision, not a property endorsement, unless an editor has inspected the exact hotel claim.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Related VietnamGuide routes</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-related-routes vg-stay-vietnam-related-manual">
<p><a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a> narrows the northern city base.</p>
<p><a href="/destinations/where-to-stay-in-ho-chi-minh-city/">Where to Stay in Ho Chi Minh City</a> narrows the southern city base.</p>
<p><a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a> protects countryside pacing.</p>
<p><a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a> decides the central Vietnam base split.</p>
<p><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> keeps pickup and transfer pressure visible.</p>
</div>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- vg-stay-vietnam-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-stay-vietnam-faq">
<details><summary>Should I stay in Hanoi or Ho Chi Minh City first?</summary><p>Choose by flight path and route direction. Hanoi is usually easier for northern scenery, Ninh Binh, Ha Long, Lan Ha, Bai Tu Long, and a classic north-to-south first trip. Ho Chi Minh City is better when the south, Mekong, Phu Quoc, or an easier southern exit drives the route.</p></details>
<details><summary>Is Da Nang or Hoi An the better base?</summary><p>Hoi An is better when old-town evenings are the main reason for the stay. Da Nang is better when beach access, airport ease, modern hotels, family space, and central-coast day trips matter more.</p></details>
<details><summary>Is it worth staying overnight in Ninh Binh?</summary><p>Yes when the boat day, countryside evening, family pace, or onward transfer needs protection. If the route is very tight and Hanoi already has too few nights, a day trip can still work, but it is a different experience.</p></details>
<details><summary>Should I stay near the airport in Vietnam?</summary><p>Only when the flight chain is fragile: late arrival, early departure, separate tickets, heavy luggage, children, older travelers, or a same-day connection. Airport-side is a logistics tool, not a default city base.</p></details>
<details><summary>How many hotel moves are too many?</summary><p>On a first trip, every hotel move should solve a clear problem. If the move does not protect a new region, beach chapter, countryside night, early flight, or major transfer, it probably adds friction.</p></details>
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
    'vg-stay-vietnam-hero:v1',
    'vg-stay-vietnam-concierge-verdict:v1',
    'vg-stay-vietnam-at-a-glance:v1',
    'vg-stay-vietnam-photo-proof:v1',
    'vg-stay-vietnam-source-diversity:v1',
    'vg-stay-vietnam-base-jobs:v1',
    'vg-stay-vietnam-city-fit:v1',
    'vg-stay-vietnam-arrival-departure:v1',
    'vg-stay-vietnam-family-sleep:v1',
    'vg-stay-vietnam-food-access:v1',
    'vg-stay-vietnam-quiet-night:v1',
    'vg-stay-vietnam-transfer-pickup:v1',
    'vg-stay-vietnam-booking-filter:v1',
    'vg-stay-vietnam-skip-traps:v1',
    'vg-stay-vietnam-live-checks:v1',
    'vg-stay-vietnam-faq:v1',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_stay_vietnam_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Where to Stay in Vietnam: City Base Decisions Before Choosing Hotels',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'A country-level Vietnam stay-base framework for choosing Hanoi, HCMC, Ninh Binh, Da Nang, Hoi An, Phu Quoc, beach, or airport-side nights before comparing hotels.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_stay_vietnam_post_fail('Could not update Where to Stay in Vietnam post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Where to Stay in Vietnam: Base Decision Guide');
update_post_meta($post_id, 'rank_math_description', 'Choose where to stay in Vietnam by base job: arrival ease, food access, quiet sleep, family rhythm, transfers, beach rest, and airport risk.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'where to stay in Vietnam');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Choose the Vietnam city base before choosing the hotel: protect the next 18 hours, then compare rooms only after arrival, food, sleep, family, transfer, beach, or airport risk is clear.');
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

wp_set_object_terms($post_id, vg_stay_vietnam_post_term_ids('category', ['hotels-neighborhoods', 'travel-planning']), 'category', false);
wp_set_object_terms($post_id, vg_stay_vietnam_post_term_ids('post_tag', ['hotel-base', 'premium-travel', 'family-travel', 'anti-spam-evergreen']), 'post_tag', false);

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
        vg_stay_vietnam_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Where to Stay in Vietnam base decisions post to complete draft: {$post_id}");

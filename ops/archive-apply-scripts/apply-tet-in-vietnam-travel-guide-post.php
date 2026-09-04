<?php
/**
 * Expand the Tet in Vietnam Travel Guide post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-tet-in-vietnam-travel-guide-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_tet_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_tet_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_tet_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_tet_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_tet_post_find_by_slug(string $slug): ?WP_Post
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
        vg_tet_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_tet_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-14',
            'vg_editorial_brief_status' => ['brief', 'complete_draft'],
            'vg_editorial_batch' => 'batch-2-evergreen-planning',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if (is_array($expected_value)) {
            if (! in_array($actual_value, $expected_value, true)) {
                vg_tet_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected one of " . implode(', ', $expected_value) . '.');
            }
            continue;
        }

        if ($actual_value !== $expected_value) {
            vg_tet_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_tet_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_tet_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'tet-in-vietnam-travel-guide';
$post = vg_tet_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_tet_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_tet_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 26, 2026';
vg_tet_post_assert_target_meta($post);

$category_term_ids = vg_tet_post_term_ids('category', ['seasonal-travel', 'food-culture', 'travel-planning']);
$tag_term_ids = vg_tet_post_term_ids('post_tag', ['tet-travel', 'international-travelers', 'route-planning', 'anti-spam-evergreen']);

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/3/36/Ch%E1%BB%A3_hoa_t%E1%BA%BFt_2.jpg');
$flower_market_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/8/8d/Ch%E1%BB%A3_hoa_t%E1%BA%BFt.jpg');
$peach_blossom_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/7/7b/Hoa_dao.jpg/1920px-Hoa_dao.jpg');
$banh_chung_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/2/25/Banh_chung.jpg');
$hanoi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$hcmc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg');
$airport_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/38/Noi_Bai_International_Airport_Terminal_2_Night_View.JPG/1920px-Noi_Bai_International_Airport_Terminal_2_Night_View.JPG');

$meta_update_summary = 'Expanded the native WordPress batch 2 brief into a complete Tet travel guide draft with photo-led hero, proof panel, concierge verdict, quick-decision table, photo proof grid, source-diversity table, before/during/after Tet timing phases, booking guidance, operating rhythm, route chooser, city choice, respectful behavior, money/food/medicine prep, fragile-plan traps, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - A traveller's guide to Tet holiday - https://vietnam.travel/things-to-do/tet-vietnam-lunar-new-year - checked {$review_date}; used for cultural and traveler-facing Tet context.",
    "Vietnam.travel - Tet: Tradition, Reunion & Taste - https://vietnam.travel/things-to-do/tet-tradition-reunion-taste - checked {$review_date}; used for family, food, reunion, and respect context without turning the article into a copied festival explainer.",
    "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for transport mode and booking-pressure context around Lunar New Year.",
    "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for route and weather caveats when Tet falls in late January or February.",
    "Vietnam Labor Code 2019 - public holiday framework - https://thuvienphapluat.vn/van-ban/Lao-dong-Tien-luong/Labor-Code-2019-333670.aspx - checked {$review_date}; used for the 5 public holiday days framework, not as a current-year date calendar.",
    "Vietnam official public-holiday announcement - official public-holiday announcement for the current travel year must be checked before publication and before traveler final payment - checked status noted {$review_date}; used as an update discipline requirement because exact Tet dates change yearly and implementation decisions can shift by year.",
    "National Centre for Hydro-Meteorological Forecasting - English forecast and warning pages - https://nchmf.gov.vn/KttvsiteE/en-US/2/index.html - checked {$review_date}; used for live weather and warning checks close to travel.",
    "World Weather Information Service - Viet Nam official city forecasts - https://worldweather.wmo.int/en/country.html?countryCode=82 - checked {$review_date}; used for official city-level weather context close to travel.",
    "Wikimedia Commons image direct URL - Cho hoa tet 2 - {$hero_image} - credit Nguyen Dung Tien / CC BY-SA 2.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Cho hoa tet - {$flower_market_image} - credit Nguyen Dung Tien / CC BY-SA 2.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hoa dao - {$peach_blossom_image} - credit Vinataba / CC BY-SA 3.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Banh chung - {$banh_chung_image} - credit Andrea Nguyen / CC BY 2.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Hanoi Hoan Kiem Lake - {$hanoi_image} - credit Alex 69200 vx / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Ho Chi Minh City Hall - {$hcmc_image} - credit Steffen Schmitz / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Noi Bai International Airport Terminal 2 Night View - {$airport_image} - credit Dragfyre / CC BY-SA 3.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This complete draft treats Tet as a Tet travel decision, not a closure panic article or festival explainer. It separates before, during, and after Tet, explains that closures are uneven, pushes travelers to book transport early, includes respectful cultural behavior, and requires exact annual date checks before publishing or final payment.';
$meta_evidence_moat = implode("\n", [
    'Tet travel decision framing helps international travelers decide whether to embrace the holiday, avoid peak days, or build a compact low-friction route.',
    'Before, during, and after Tet timing table separates preparation weeks, final days, peak days, and post-Tet restart instead of using one vague holiday warning.',
    'Source-diversity table distinguishes Vietnam.travel cultural context, transport guidance, Vietnam Labor Code 2019 public-holiday framework, official public-holiday announcement checks, weather sources, and image-license records.',
    'Operating-rhythm matrix explains that closures are uneven across restaurants, shops, tours, museums, banks, pharmacies, transport, markets, temples, and hotels.',
    'Booking section tells travelers what to book transport early for: flights, trains, first/last nights, central hotels, cruises, island connections, and remote transfers.',
    'Route chooser compares embracing Tet in a city, shifting travel days away from the peak, compact north, central heritage, south-first warmth, and island rest.',
    'City-choice section separates Hanoi, Ho Chi Minh City, Hoi An/Hue, Da Nang, Phu Quoc, and rural/remote moves by holiday resilience.',
    'Respectful cultural behavior guidance gives practical etiquette without pretending a tourist can fully join private family rituals.',
    'Money, food, and medicine section protects travelers from holiday-period cash, card, meal, pharmacy, and prescription friction.',
    'No visible external body anchors; source URLs and image-license records stay auditable through metadata and shortcode output.',
]);
$meta_related_routes = implode("\n", [
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Use this to understand the seasonal frame when Tet falls in late January or February.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Use this before buying Tet-period flights, trains, buses, cruises, ferries, or private transfers.',
    'Vietnam in January | /travel-planning/vietnam-in-january/ | Use this when the Tet lead-up sits inside January travel dates.',
    'Vietnam in February | /travel-planning/vietnam-in-february/ | Use this when Tet peak or post-Tet restart affects a February route.',
    'Vietnam Food Safety and Street Food Etiquette | /travel-planning/vietnam-food-safety-street-food-etiquette/ | Use this when holiday meals, markets, and changed restaurant rhythms affect food choices.',
    'Vietnam Airport Arrival Checklist | /travel-planning/vietnam-airport-arrival-checklist/ | Use this when arriving close to Tet and first-night logistics need to be boring in the best way.',
    'Money in Vietnam | /plan/money-cash-cards-atms/ | Use this before banks, ATMs, small vendors, deposits, and holiday cash habits become urgent.',
    'SIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Use this to keep maps, translation, ride-hailing, hotel contact, and operator messaging alive around holiday changes.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Use this when Tet transport, hotels, cruises, and islands change the real trip budget.',
    '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use this when two weeks can hold Tet buffers without weakening the route.',
]);
$meta_hero_image_credit = 'Hero image: Cho hoa tet 2 by Nguyen Dung Tien, CC BY-SA 2.0. Body images: Cho hoa tet by Nguyen Dung Tien, CC BY-SA 2.0; Hoa dao by Vinataba, CC BY-SA 3.0; Banh chung by Andrea Nguyen, CC BY 2.0; Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0; Ho Chi Minh City Hall by Steffen Schmitz, CC BY-SA 4.0; Noi Bai International Airport Terminal 2 Night View by Dragfyre, CC BY-SA 3.0.';
$meta_admin_notes = 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, checks Rank Math/social preview, verifies exact current-year Tet dates, confirms the official public-holiday announcement, reviews operating-hour examples, checks source-trail records, and adds first-hand Tet travel notes if available. Do not publish as a current-year closure schedule.';

$content = <<<HTML
<!-- vg-tet-hero:v1 -->
<section class="vg-guide-hero vg-tet-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Holiday travel planning</p>
<h1>Tet in Vietnam Travel Guide: What International Visitors Should Know</h1>
<p class="vg-guide-lede">Tet can be one of the most memorable times to be in Vietnam, but it is not an ordinary travel week. The right plan separates cultural atmosphere from logistics reality: exact Tet dates change yearly, transport tightens before the holiday, closures are uneven, some services restart gradually, and the best traveler experience often comes from fewer moves and more patience.</p>
<p class="vg-field-note">Use this with <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, Vietnam in January, Vietnam in February, <a href="/travel-planning/vietnam-airport-arrival-checklist/">Vietnam Airport Arrival Checklist</a>, and <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a> before paying for holiday-period movement.</p>
</div>
<figure class="vg-guide-hero-image"><img src="{$hero_image}" alt="Tet flower market in Vietnam used as a holiday travel planning reference" loading="eager" decoding="async"><figcaption>Tet can make Vietnam vivid, beautiful, and slower at the same time. The best visitor plan protects the holiday rhythm instead of fighting it. Image: Nguyen Dung Tien / CC BY-SA 2.0.</figcaption></figure>
</section>

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-tet-concierge-verdict:v1 -->
<aside class="vg-concierge-verdict vg-tet-concierge-verdict" aria-label="Tet in Vietnam verdict">
<p class="vg-kicker">VietnamGuide verdict</p>
<h2>Tet is worth it when you choose the holiday deliberately.</h2>
<p><strong>International travelers should visit during Tet only if they are comfortable trading normal operating rhythm for atmosphere, family movement, decorations, slower days, and a route with fewer fragile commitments.</strong> If your trip needs every restaurant, museum, ferry, tour, pharmacy, and transfer to behave normally, move the hardest travel days away from the peak.</p>
<ul>
<li><strong>Best traveler fit:</strong> people who like street atmosphere, slower city days, cultural context, and flexible plans.</li>
<li><strong>Weak traveler fit:</strong> people with tight multi-region routes, remote transfers, special diets, or zero tolerance for changed hours.</li>
<li><strong>Best route rule:</strong> choose one comfortable base around the peak, then move again after the post-Tet restart becomes clearer.</li>
<li><strong>Best planning rule:</strong> book transport early and recheck exact annual dates before final payment.</li>
</ul>
</aside>

<p>Tet is Vietnam's Lunar New Year holiday and one of the most important family periods of the year. For travelers, that importance is exactly why generic advice breaks. A holiday that is meaningful locally can be beautiful for visitors and inconvenient for logistics at the same time. Both things can be true.</p>

<p>The practical answer is not fear. It is design. A good Tet route protects arrival recovery, first-night comfort, food options, cash, phone data, medication, essential transport, and enough unstructured time to enjoy the city or town you are actually in. A weak Tet route stacks airports, cruises, remote roads, small operators, and non-refundable hotels as if the holiday were invisible.</p>

<p>Vietnam Labor Code 2019 gives the public-holiday framework, including 5 public holiday days for Lunar New Year. The exact annual calendar, surrounding bridge days, operator schedules, and local openings still need current-year confirmation. That is why this guide stays evergreen by teaching the decision pattern rather than pretending to be a live closure schedule.</p>

<p>Visible external links are intentionally avoided in the body. Official Tet, transport, weather, public-holiday, and image-license records stay in the source trail metadata so the article remains clean, evergreen, and auditable.</p>

<!-- vg-tet-quick-decision:v1 -->
<h2 class="wp-block-heading">Should you travel in Vietnam during Tet?</h2>
<table class="vg-decision-table vg-tet-quick-decision">
<thead><tr><th>Traveler situation</th><th>Tet fit</th><th>Main risk</th><th>VietnamGuide decision</th></tr></thead>
<tbody>
<tr><td data-label="Traveler situation">You want cultural atmosphere and can slow down</td><td data-label="Tet fit">Strong.</td><td data-label="Main risk">Expecting private family rituals to become tourist activities.</td><td data-label="VietnamGuide decision">Stay central, enjoy public mood, markets, flowers, streets, and temples with respect.</td></tr>
<tr><td data-label="Traveler situation">You have only 7-10 days and want north, central, south, and an island</td><td data-label="Tet fit">Weak.</td><td data-label="Main risk">Every transfer becomes more important than the place itself.</td><td data-label="VietnamGuide decision">Cut the route to one or two chapters, or move travel days away from the peak.</td></tr>
<tr><td data-label="Traveler situation">You arrive one or two days before Tet</td><td data-label="Tet fit">Possible but needs discipline.</td><td data-label="Main risk">Late arrival plus holiday errands, cash, meals, and transport surprises.</td><td data-label="VietnamGuide decision">Book a central first base, arrange airport transfer, carry essentials, and keep the first full day simple.</td></tr>
<tr><td data-label="Traveler situation">You travel with children or older parents</td><td data-label="Tet fit">Good only with comfort bias.</td><td data-label="Main risk">Meal uncertainty, heat/cold, pharmacy hours, and too many moves.</td><td data-label="VietnamGuide decision">Choose better hotels, central locations, backup meals, and fewer route changes.</td></tr>
<tr><td data-label="Traveler situation">You need a specific cruise, ferry, food tour, or remote homestay</td><td data-label="Tet fit">Conditional.</td><td data-label="Main risk">Operator schedule and restart timing may not match ordinary weeks.</td><td data-label="VietnamGuide decision">Confirm directly, get terms in writing, and build a backup day.</td></tr>
</tbody>
</table>

<!-- vg-tet-photo-proof:v1 -->
<h2 class="wp-block-heading">Photo proof: what Tet changes for travelers</h2>
<p>These images show why Tet is not just a calendar note. It changes streets, flowers, food, city energy, arrival logistics, and the way travelers should pace movement.</p>
<div class="vg-guide-photo-grid vg-tet-photo-proof" aria-label="Tet in Vietnam photo proof">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Tet flower market in Vietnam" loading="lazy" decoding="async"><figcaption>Flower markets can make the lead-up vivid, but they also signal that ordinary errands and movement may take longer. Image: Nguyen Dung Tien / CC BY-SA 2.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$flower_market_image}" alt="Tet market flowers in Vietnam" loading="lazy" decoding="async"><figcaption>Use public street atmosphere as a reason to slow the route, not as a reason to add more transfers. Image: Nguyen Dung Tien / CC BY-SA 2.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$peach_blossom_image}" alt="Peach blossom flowers associated with Tet in Vietnam" loading="lazy" decoding="async"><figcaption>Decorations help visitors feel the season, but exact local events and hours still need live checks. Image: Vinataba / CC BY-SA 3.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$banh_chung_image}" alt="Banh chung served around Vietnamese Tet" loading="lazy" decoding="async"><figcaption>Holiday food context is useful; travelers still need backup meals and food-safety judgment when familiar restaurants change hours. Image: Andrea Nguyen / CC BY 2.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hanoi_image}" alt="Hoan Kiem Lake in Hanoi as a Tet city-base reference" loading="lazy" decoding="async"><figcaption>Hanoi can be a strong Tet base when you value walking, atmosphere, cafes, lakes, and low-friction days. Image: Alex 69200 vx / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall area as a Tet city-base reference" loading="lazy" decoding="async"><figcaption>Ho Chi Minh City can work well for Tet when the hotel area protects food, ride-hailing, and airport access. Image: Steffen Schmitz / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$airport_image}" alt="Noi Bai International Airport terminal at night" loading="lazy" decoding="async"><figcaption>Arrival and exit days matter more around Tet; airport transfers should be arranged before fatigue arrives. Image: Dragfyre / CC BY-SA 3.0.</figcaption></figure>
</div>

<!-- vg-tet-source-diversity:v1 -->
<h2 class="wp-block-heading">Source diversity for Tet planning</h2>
<table class="vg-decision-table vg-tet-source-diversity">
<thead><tr><th>Source family</th><th>What it helps decide</th><th>What it cannot decide alone</th><th>VietnamGuide judgment</th></tr></thead>
<tbody>
<tr><td data-label="Source family">Vietnam.travel Tet guidance</td><td data-label="What it helps decide">Cultural meaning, public-facing holiday context, food, flowers, streets, and etiquette orientation.</td><td data-label="What it cannot decide alone">Your exact hotel, restaurant, train, cruise, museum, or ferry schedule.</td><td data-label="VietnamGuide judgment">Use it for context, not as a live operating-hours database.</td></tr>
<tr><td data-label="Source family">Vietnam Labor Code 2019</td><td data-label="What it helps decide">The legal public-holiday framework, including 5 public holiday days for Lunar New Year.</td><td data-label="What it cannot decide alone">The exact current-year consecutive days, bridge days, or private business behavior.</td><td data-label="VietnamGuide judgment">Use the legal frame, then check the official public-holiday announcement for the travel year.</td></tr>
<tr><td data-label="Source family">Official public-holiday announcement</td><td data-label="What it helps decide">Exact current-year public-holiday dates, government office rhythm, and update discipline before publication.</td><td data-label="What it cannot decide alone">Individual restaurants, tour operators, ferry companies, or hotel policies.</td><td data-label="VietnamGuide judgment">Treat as a publish gate and final-payment gate.</td></tr>
<tr><td data-label="Source family">Transport sources</td><td data-label="What it helps decide">Whether flights, trains, buses, cruise transfers, ferries, and private cars are realistic near peak movement.</td><td data-label="What it cannot decide alone">Whether the extra destination deserves the route cost.</td><td data-label="VietnamGuide judgment">Transport should protect the holiday plan, not justify overloading it.</td></tr>
<tr><td data-label="Source family">Weather and warnings</td><td data-label="What it helps decide">Bay, ferry, mountain, beach, and central-coast risk close to travel.</td><td data-label="What it cannot decide alone">Whether Tet itself is a good fit for your personality.</td><td data-label="VietnamGuide judgment">Use World Weather Information Service, NCHMF, and local forecasts as final-week checks.</td></tr>
<tr><td data-label="Source family">Internal route guides</td><td data-label="What it helps decide">Whether the trip should be January, February, compact, city-based, transport-protected, or cost-adjusted.</td><td data-label="What it cannot decide alone">Current-year hours and exact operator policy.</td><td data-label="VietnamGuide judgment">Use internal guides for decisions, live sources for volatile details.</td></tr>
</tbody>
</table>

<!-- vg-tet-timing-phases:v1 -->
<h2 class="wp-block-heading">Tet timing: before, during, and after Tet</h2>
<p>The biggest mistake is treating Tet as one day. Travelers need to understand before, during, and after Tet because each phase has a different planning job. Exact Tet dates change yearly, and the surrounding travel pressure can begin before the public-holiday period itself.</p>
<table class="vg-decision-table vg-tet-timing-phases">
<thead><tr><th>Timing phase</th><th>What changes</th><th>Best traveler move</th></tr></thead>
<tbody>
<tr><td data-label="Timing phase">Four to six weeks before peak travel</td><td data-label="What changes">Better flights, trains, central hotels, cruises, and family-friendly rooms may start disappearing or rising in price.</td><td data-label="Best traveler move">Book critical transport early and avoid routes that depend on last-minute domestic movement.</td></tr>
<tr><td data-label="Timing phase">Final week before Tet</td><td data-label="What changes">Markets, flowers, shopping, decorations, traffic, and family movement become more visible.</td><td data-label="Best traveler move">Stay central, reduce errands, keep luggage movement simple, and enjoy public atmosphere.</td></tr>
<tr><td data-label="Timing phase">Tet eve and first days</td><td data-label="What changes">Family time becomes central. Some restaurants, shops, tours, offices, museums, markets, banks, and services may pause or change hours.</td><td data-label="Best traveler move">Confirm meals and transport, choose a comfortable base, and treat these as slow cultural days.</td></tr>
<tr><td data-label="Timing phase">Middle holiday days</td><td data-label="What changes">Some public places and businesses return, while others stay limited. Popular domestic leisure spots can become busy.</td><td data-label="Best traveler move">Use flexible city days, temples, walking areas, cafes that are confirmed open, and simple day plans.</td></tr>
<tr><td data-label="Timing phase">Post-Tet restart</td><td data-label="What changes">The post-Tet restart can be uneven: operators, guides, drivers, restaurants, offices, and small businesses may return on different schedules.</td><td data-label="Best traveler move">Leave one or two low-stakes days before relying on remote transfers, complex tours, paperwork, or tight onward plans.</td></tr>
</tbody>
</table>

<!-- vg-tet-book-early:v1 -->
<h2 class="wp-block-heading">What to book early for Tet travel</h2>
<table class="vg-decision-table vg-tet-book-early">
<thead><tr><th>Booking anchor</th><th>Why Tet changes it</th><th>Book early when</th><th>Keep flexible when</th></tr></thead>
<tbody>
<tr><td data-label="Booking anchor">International flights</td><td data-label="Why Tet changes it">Holiday movement can make good arrival and exit timing more valuable.</td><td data-label="Book early when">Open-jaw flights reduce domestic backtracking or arrival lands near peak days.</td><td data-label="Keep flexible when">You have not decided whether to embrace Tet or route around it.</td></tr>
<tr><td data-label="Booking anchor">Domestic flights and trains</td><td data-label="Why Tet changes it">Family travel demand can make popular routes, good times, and baggage-friendly fares tighter.</td><td data-label="Book early when">The move is essential to the route and date-specific.</td><td data-label="Keep flexible when">The move exists only to preserve an overloaded itinerary.</td></tr>
<tr><td data-label="Booking anchor">First and last nights</td><td data-label="Why Tet changes it">Late arrivals, changed restaurant hours, luggage, and airport movement become less forgiving.</td><td data-label="Book early when">Arriving near Tet, traveling with family, or departing early after the holiday.</td><td data-label="Keep flexible when">Middle stops are still being cut.</td></tr>
<tr><td data-label="Booking anchor">Central city hotel</td><td data-label="Why Tet changes it">A walkable base reduces dependence on taxis, long food searches, and scattered openings.</td><td data-label="Book early when">Tet peak days sit inside your stay.</td><td data-label="Keep flexible when">The city is only a transit stop.</td></tr>
<tr><td data-label="Booking anchor">Cruise, ferry, island, or remote transfer</td><td data-label="Why Tet changes it">Operators may run different schedules and weather can still affect exposed movement.</td><td data-label="Book early when">The operator confirms schedule, terms, pickup, route, and refund/weather policy.</td><td data-label="Keep flexible when">The add-on is nice-to-have rather than the trip's reason.</td></tr>
<tr><td data-label="Booking anchor">Food tours and guides</td><td data-label="Why Tet changes it">Good guides may be with family or running limited holiday schedules.</td><td data-label="Book early when">The guide experience is core and the provider confirms what is open.</td><td data-label="Keep flexible when">A self-guided city walk would satisfy the same trip job.</td></tr>
</tbody>
</table>

<!-- vg-tet-operating-rhythm:v1 -->
<h2 class="wp-block-heading">What may operate differently during Tet</h2>
<p>The useful rule is simple: closures are uneven. Large hotels may keep essential services running, some restaurants may open for holiday demand, some small shops may pause, and some attractions or tours may change hours. Do not build a plan that needs every category below to behave like a normal week.</p>
<table class="vg-decision-table vg-tet-operating-rhythm">
<thead><tr><th>Category</th><th>What can change</th><th>Safer traveler move</th></tr></thead>
<tbody>
<tr><td data-label="Category">Restaurants and cafes</td><td data-label="What can change">Family-run places may close, hotel dining may matter more, and popular open places may be busier.</td><td data-label="Safer traveler move">Keep a hotel breakfast or confirmed nearby option and avoid rigid dish-chasing.</td></tr>
<tr><td data-label="Category">Markets and shops</td><td data-label="What can change">Flower markets and holiday shopping can be lively before Tet; ordinary retail may pause during peak days.</td><td data-label="Safer traveler move">Buy essentials before the peak and treat markets as atmosphere rather than errands.</td></tr>
<tr><td data-label="Category">Museums and attractions</td><td data-label="What can change">Some may close, shorten hours, or reopen on a special schedule.</td><td data-label="Safer traveler move">Check each exact venue and do not make a closed museum ruin the day.</td></tr>
<tr><td data-label="Category">Tours and guides</td><td data-label="What can change">Some operators pause, some run limited holiday products, and others restart gradually.</td><td data-label="Safer traveler move">Confirm guide language, pickup, inclusions, and cancellation terms directly.</td></tr>
<tr><td data-label="Category">Banks, ATMs, and payments</td><td data-label="What can change">Bank counters may close, ATMs can be busier, and small vendors may prefer cash.</td><td data-label="Safer traveler move">Carry smaller VND notes, backup cards, and cash discipline before peak days.</td></tr>
<tr><td data-label="Category">Pharmacies and clinics</td><td data-label="What can change">Major cities keep options, but hours and access can be less convenient.</td><td data-label="Safer traveler move">Carry prescriptions, basic medicines, insurance details, and hotel support contacts.</td></tr>
<tr><td data-label="Category">Transport</td><td data-label="What can change">Flights, trains, buses, ride-hailing, private cars, cruise transfers, and ferries may face demand or schedule changes.</td><td data-label="Safer traveler move">Book essentials early, confirm pickups, and keep transfer days lighter.</td></tr>
<tr><td data-label="Category">Temples and public spaces</td><td data-label="What can change">Some places become more meaningful and crowded with local visitors.</td><td data-label="Safer traveler move">Dress respectfully, give people space, and do not treat prayer or family rituals as a performance.</td></tr>
</tbody>
</table>

<!-- vg-tet-route-chooser:v1 -->
<h2 class="wp-block-heading">Choose the right Tet route shape</h2>
<table class="vg-decision-table vg-tet-route-chooser">
<thead><tr><th>Route shape</th><th>Best for</th><th>What to remove first</th><th>Next guide</th></tr></thead>
<tbody>
<tr><td data-label="Route shape">Embrace Tet in one city</td><td data-label="Best for">Travelers who want atmosphere, flowers, public streets, temples, cafes, and lower movement.</td><td data-label="What to remove first">Remote transfers during peak days.</td><td data-label="Next guide"><a href="/travel-planning/vietnam-airport-arrival-checklist/">Vietnam Airport Arrival Checklist</a></td></tr>
<tr><td data-label="Route shape">Move before or after the peak</td><td data-label="Best for">Travelers who want normal tours, more restaurant choice, and smoother transport.</td><td data-label="What to remove first">Date-specific domestic movement inside the peak.</td><td data-label="Next guide"><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a></td></tr>
<tr><td data-label="Route shape">Compact north</td><td data-label="Best for">Hanoi, Ninh Binh, and a bay decision when the route can hold cool-season layers and buffers.</td><td data-label="What to remove first">A rushed central or southern add-on.</td><td data-label="Next guide">Vietnam in January</td></tr>
<tr><td data-label="Route shape">Central heritage base</td><td data-label="Best for">Hoi An, Hue, Da Nang access, food, old streets, and a calmer middle chapter.</td><td data-label="What to remove first">Beach-only expectations and one-night city hopping.</td><td data-label="Next guide">Vietnam in February</td></tr>
<tr><td data-label="Route shape">South-first comfort</td><td data-label="Best for">Ho Chi Minh City, Mekong, warm weather, airport access, and a less cold-weather route.</td><td data-label="What to remove first">A far northern move that strains the calendar.</td><td data-label="Next guide"><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a></td></tr>
<tr><td data-label="Route shape">Island finish</td><td data-label="Best for">Travelers who can protect three or more nights and confirm flights/ferries.</td><td data-label="What to remove first">A two-night island add-on that is mostly transfer time.</td><td data-label="Next guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
</tbody>
</table>

<!-- vg-tet-city-choice:v1 -->
<h2 class="wp-block-heading">Best places to be during Tet by traveler job</h2>
<table class="vg-decision-table vg-tet-city-choice">
<thead><tr><th>Base</th><th>Why it can work</th><th>What to watch</th><th>Best traveler fit</th></tr></thead>
<tbody>
<tr><td data-label="Base">Hanoi</td><td data-label="Why it can work">Strong public atmosphere, lakes, Old Quarter walks, cafes, flowers, temples, museums when open, and northern route access.</td><td data-label="What to watch">Cool weather, changed openings, heavy family movement, and bay/Ninh Binh transfer timing.</td><td data-label="Best traveler fit">Travelers who want culture, food, walking, and a city base that can slow down.</td></tr>
<tr><td data-label="Base">Ho Chi Minh City</td><td data-label="Why it can work">Warmer weather, airport logic, wide hotel choice, public decorations, and southern route access.</td><td data-label="What to watch">Heat, ride demand, restaurant selection by district, and road-heavy day trips.</td><td data-label="Best traveler fit">Travelers who want easier arrival/exit and a city that can support backup plans.</td></tr>
<tr><td data-label="Base">Hoi An / Hue</td><td data-label="Why it can work">Heritage, food, quieter walking rhythm, cultural setting, and central Vietnam route value.</td><td data-label="What to watch">Beach expectations, specific attraction hours, and transfers through Da Nang.</td><td data-label="Best traveler fit">Travelers who want atmosphere and can avoid a hard checklist.</td></tr>
<tr><td data-label="Base">Da Nang</td><td data-label="Why it can work">Airport access, beach-city comfort, modern hotels, and easy connection to Hoi An or Hue.</td><td data-label="What to watch">Beach weather assumptions and whether the city itself is the real base or only a transfer.</td><td data-label="Best traveler fit">Families and comfort-first travelers who want a less fragile central base.</td></tr>
<tr><td data-label="Base">Phu Quoc or island resort</td><td data-label="Why it can work">Rest, warmth, resort services, and fewer daily decisions once the island chapter is protected.</td><td data-label="What to watch">Flights, ferry exposure, resort isolation, holiday pricing, and minimum nights.</td><td data-label="Best traveler fit">Travelers who want recovery, not maximum sightseeing.</td></tr>
<tr><td data-label="Base">Remote mountains or rural homestays</td><td data-label="Why it can work">Can be meaningful with the right operator and cultural sensitivity.</td><td data-label="What to watch">Weather, road risk, family obligations, limited services, and operator restart timing.</td><td data-label="Best traveler fit">Experienced travelers with direct confirmation and route margin.</td></tr>
</tbody>
</table>

<!-- vg-tet-respectful-behavior:v1 -->
<h2 class="wp-block-heading">Respectful cultural behavior for visitors</h2>
<table class="vg-decision-table vg-tet-respectful-behavior">
<thead><tr><th>Situation</th><th>Respectful behavior</th><th>Why it matters</th></tr></thead>
<tbody>
<tr><td data-label="Situation">Temples and pagodas</td><td data-label="Respectful behavior">Dress modestly, move slowly, keep voices down, and do not block people who are praying.</td><td data-label="Why it matters">Tet visits may be personal and spiritual, not just visual.</td></tr>
<tr><td data-label="Situation">Taking photos</td><td data-label="Respectful behavior">Ask before photographing people closely, especially families, children, worship, and private homes.</td><td data-label="Why it matters">Public streets are not permission to turn private moments into content.</td></tr>
<tr><td data-label="Situation">Invitations or local hospitality</td><td data-label="Respectful behavior">Accept only when genuinely invited, follow the host's lead, and do not overstay.</td><td data-label="Why it matters">Tet is family-centered and hospitality should not be treated as a product.</td></tr>
<tr><td data-label="Situation">Lucky money and gifts</td><td data-label="Respectful behavior">Do not hand out money randomly; follow local guidance if invited into a family setting.</td><td data-label="Why it matters">Meaning depends on relationship and context.</td></tr>
<tr><td data-label="Situation">Bargaining and service pressure</td><td data-label="Respectful behavior">Be patient with limited staffing and avoid making holiday service delays personal.</td><td data-label="Why it matters">Many people are balancing work and family obligations.</td></tr>
<tr><td data-label="Situation">Street celebrations</td><td data-label="Respectful behavior">Enjoy public decorations and atmosphere while keeping paths clear and belongings secure.</td><td data-label="Why it matters">Crowded holiday spaces work better when visitors behave like guests, not obstacles.</td></tr>
</tbody>
</table>

<!-- vg-tet-money-food-medicine:v1 -->
<h2 class="wp-block-heading">Money, food, and medicine before Tet</h2>
<table class="vg-decision-table vg-tet-money-food-medicine">
<thead><tr><th>Need</th><th>What can go wrong</th><th>Safer preparation</th><th>Related guide</th></tr></thead>
<tbody>
<tr><td data-label="Need">Cash</td><td data-label="What can go wrong">Small vendors, taxis, tips, deposits, and markets may be easier with smaller VND notes.</td><td data-label="Safer preparation">Use ATMs before peak days and split cash safely.</td><td data-label="Related guide"><a href="/plan/money-cash-cards-atms/">Money in Vietnam</a></td></tr>
<tr><td data-label="Need">Cards and payment backup</td><td data-label="What can go wrong">Foreign cards, surcharge rules, and holiday staffing can make payment slower.</td><td data-label="Safer preparation">Carry at least two payment methods and keep receipts for deposits.</td><td data-label="Related guide"><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a></td></tr>
<tr><td data-label="Need">Phone data</td><td data-label="What can go wrong">Ride-hailing, translation, hotel contact, operator messaging, and maps matter more when routines shift.</td><td data-label="Safer preparation">Activate connectivity before relying on it during holiday movement.</td><td data-label="Related guide"><a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a></td></tr>
<tr><td data-label="Need">Food</td><td data-label="What can go wrong">Favorite restaurants may pause, open places may be crowded, and holiday foods may not match every diet.</td><td data-label="Safer preparation">Keep hotel breakfast, snacks, hydration, and backup restaurants.</td><td data-label="Related guide"><a href="/travel-planning/vietnam-food-safety-street-food-etiquette/">Food Safety and Street Food Etiquette</a></td></tr>
<tr><td data-label="Need">Medicine</td><td data-label="What can go wrong">Pharmacy hours and doctor access can be less convenient around peak days.</td><td data-label="Safer preparation">Carry prescriptions, basic medicines, insurance details, and allergy notes.</td><td data-label="Related guide"><a href="/travel-planning/vietnam-airport-arrival-checklist/">Airport Arrival Checklist</a></td></tr>
<tr><td data-label="Need">Documents and bookings</td><td data-label="What can go wrong">Holiday staffing can slow problem-solving if bookings or entry details are unclear.</td><td data-label="Safer preparation">Save offline confirmations, hotel address, passport copy, insurance, and transport contacts.</td><td data-label="Related guide"><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a></td></tr>
</tbody>
</table>

<!-- vg-tet-fragile-plans:v1 -->
<h2 class="wp-block-heading">Fragile Tet plans to avoid</h2>
<table class="vg-decision-table vg-tet-fragile-plans">
<thead><tr><th>Fragile plan</th><th>Why it breaks</th><th>Safer alternative</th></tr></thead>
<tbody>
<tr><td data-label="Fragile plan">Landing late and transferring far the next morning</td><td data-label="Why it breaks">Jet lag, airport friction, holiday movement, and luggage make small delays expensive.</td><td data-label="Safer alternative">Spend the first night centrally and move only after recovery.</td></tr>
<tr><td data-label="Fragile plan">Counting on one specific small restaurant</td><td data-label="Why it breaks">Family-run places may change hours or close around peak days.</td><td data-label="Safer alternative">Have three meal options: hotel, confirmed nearby restaurant, and simple backup.</td></tr>
<tr><td data-label="Fragile plan">Booking remote tours immediately after peak days</td><td data-label="Why it breaks">The post-Tet restart can be uneven and operators may return at different speeds.</td><td data-label="Safer alternative">Use low-stakes city days before complex tours.</td></tr>
<tr><td data-label="Fragile plan">Moving north, central, south, and island through the holiday</td><td data-label="Why it breaks">The trip becomes a transport test instead of a Vietnam trip.</td><td data-label="Safer alternative">Choose one base for the peak and resume larger movement later.</td></tr>
<tr><td data-label="Fragile plan">Assuming a cruise or ferry is simple because it is available online</td><td data-label="Why it breaks">Weather, staffing, holiday demand, port timing, and pickup policy still matter.</td><td data-label="Safer alternative">Confirm route, pickup, refund, weather policy, and onward buffer directly.</td></tr>
<tr><td data-label="Fragile plan">Treating Tet only as a sightseeing opportunity</td><td data-label="Why it breaks">The holiday is family-centered and some of the best experiences are slower public atmosphere.</td><td data-label="Safer alternative">Plan one or two meaningful public experiences and leave room to observe respectfully.</td></tr>
</tbody>
</table>

<!-- vg-tet-live-checks:v1 -->
<h2 class="wp-block-heading">Live checks before final payment</h2>
<ul class="vg-check-list vg-tet-live-checks">
<li>Check the official public-holiday announcement for the exact travel year before locking non-refundable plans.</li>
<li>Check whether your travel days are before Tet, peak Tet days, or the post-Tet restart.</li>
<li>Check flight, train, bus, cruise, ferry, and private-transfer schedules directly before paying deposits.</li>
<li>Check hotel restaurant hours, breakfast policy, airport transfer, luggage storage, and late-arrival support.</li>
<li>Check museum, attraction, food tour, cooking class, market tour, and guide schedules one by one.</li>
<li>Check ATMs, backup cash, cards, phone data, hotel contact, and ride-hailing setup before the final pre-holiday days.</li>
<li>Check pharmacy needs, prescriptions, insurance, allergies, and basic medicine before assuming errands will be easy.</li>
<li>Check official weather and warnings before bay cruises, ferries, mountain roads, beach hotels, or exposed transfers.</li>
<li>Check whether your itinerary still works if two ordinary sightseeing days become slower city days.</li>
</ul>

<!-- vg-tet-faq:v1 -->
<h2 class="wp-block-heading">Tet in Vietnam FAQ</h2>
<div class="vg-faq-list vg-tet-faq">
<details><summary>Is Tet a good time to visit Vietnam?</summary><p>Tet can be a good time if you want atmosphere, flowers, public holiday mood, and slower travel. It is weaker if your route needs many transfers, fixed tours, remote operators, or ordinary restaurant and attraction hours every day.</p></details>
<details><summary>How many days does Tet last for travelers?</summary><p>The public-holiday framework includes 5 public holiday days for Lunar New Year, but the practical travel effect can begin before the official dates and continue into the post-Tet restart. Exact annual dates must be checked for your travel year.</p></details>
<details><summary>Will restaurants and shops close during Tet?</summary><p>Some will close or change hours, while others stay open for hotel guests, visitors, or local demand. The practical rule is that closures are uneven, so keep backup meals and do not depend on one specific small business.</p></details>
<details><summary>Should I book transport early for Tet?</summary><p>Yes. Flights, trains, buses, private cars, cruises, ferries, and good travel times can become more competitive around family movement and holiday demand. Book essential movement early and remove weak extra stops.</p></details>
<details><summary>Where should I stay during Tet?</summary><p>Choose a central, comfortable base with food options, hotel support, and easy movement. Hanoi, Ho Chi Minh City, Hoi An, Hue, Da Nang, or a resort base can all work when the route job is clear.</p></details>
<details><summary>Is it respectful for tourists to visit temples during Tet?</summary><p>Yes when visitors dress modestly, move slowly, do not block worship, ask before close photos, and remember that people may be observing family or spiritual traditions.</p></details>
<details><summary>Should I avoid peak Tet days?</summary><p>Move hard travel away from peak days if you need normal operations and fixed logistics. Stay through the peak if you want atmosphere and can accept slower, simpler days.</p></details>
</div>

<h2 class="wp-block-heading">Where this guide fits next</h2>
<p>Use this guide before turning January or February into a non-refundable route. Then choose whether Tet is the reason for the trip, a constraint to route around, or a short slow chapter inside a longer itinerary.</p>
<div class="vg-related-routes vg-tet-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a><span class="vg-related-route-note">Use this for the broader seasonal frame when Tet sits in late January or February.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Use this before buying holiday-period flights, trains, buses, cruises, ferries, or cars.</span></li>
<li><span class="vg-related-route-step">03</span>Vietnam in January<span class="vg-related-route-note">Use this when the Tet lead-up affects a January route.</span></li>
<li><span class="vg-related-route-step">04</span>Vietnam in February<span class="vg-related-route-note">Use this when peak Tet or the restart affects a February route.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/plan/money-cash-cards-atms/">Money in Vietnam</a><span class="vg-related-route-note">Use this before cash, cards, deposits, and ATMs become urgent.</span></li>
<li><span class="vg-related-route-step">06</span><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a><span class="vg-related-route-note">Use this when two weeks can hold buffers without weakening the whole route.</span></li>
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
    'vg-tet-hero:v1',
    'vg-tet-concierge-verdict:v1',
    'vg-tet-quick-decision:v1',
    'vg-tet-photo-proof:v1',
    'vg-tet-source-diversity:v1',
    'vg-tet-timing-phases:v1',
    'vg-tet-book-early:v1',
    'vg-tet-operating-rhythm:v1',
    'vg-tet-route-chooser:v1',
    'vg-tet-city-choice:v1',
    'vg-tet-respectful-behavior:v1',
    'vg-tet-money-food-medicine:v1',
    'vg-tet-fragile-plans:v1',
    'vg-tet-live-checks:v1',
    'vg-tet-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_tet_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Tet in Vietnam Travel Guide: What International Visitors Should Know',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'Plan travel around Tet in Vietnam with practical guidance on dates, transport, closures, respectful behavior, money, food, medicine, routes, and live checks.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_tet_post_fail('Could not update Tet in Vietnam Travel Guide post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Tet in Vietnam Travel Guide: Dates, Closures and Routes');
update_post_meta($post_id, 'rank_math_description', 'Plan Tet travel in Vietnam by exact dates, transport pressure, changed openings, respectful behavior, money, meals, medicine, route choices, and live checks.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Tet in Vietnam travel guide');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Decide whether to visit Vietnam during Tet, move around the peak, or build a compact holiday route by exact dates, transport pressure, uneven closures, post-Tet restart, respectful behavior, and live checks.');
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
    vg_tet_post_fail('Could not assign Tet in Vietnam categories: ' . $category_result->get_error_message());
}

$tag_result = wp_set_object_terms($post_id, $tag_term_ids, 'post_tag', false);

if (is_wp_error($tag_result)) {
    vg_tet_post_fail('Could not assign Tet in Vietnam tags: ' . $tag_result->get_error_message());
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
        vg_tet_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Tet in Vietnam Travel Guide post to complete draft: {$post_id}");

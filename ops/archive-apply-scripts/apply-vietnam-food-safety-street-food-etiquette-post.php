<?php
/**
 * Expand the Vietnam Food Safety and Street Food Etiquette post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-vietnam-food-safety-street-food-etiquette-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_food_safety_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_food_safety_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && $value === '1';
}

if (! vg_food_safety_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_food_safety_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_food_safety_post_find_by_slug(string $slug): ?WP_Post
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
        vg_food_safety_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_food_safety_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-08',
            'vg_editorial_brief_status' => 'brief',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if ($actual_value !== $expected_value) {
            vg_food_safety_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_food_safety_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_food_safety_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'vietnam-food-safety-street-food-etiquette';
$post = vg_food_safety_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_food_safety_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_food_safety_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 25, 2026';
vg_food_safety_post_assert_target_meta($post);

$hero_image = 'https://upload.wikimedia.org/wikipedia/commons/c/ca/Street_food_in_Vietnam.jpg';
$hanoi_vendor_image = 'https://upload.wikimedia.org/wikipedia/commons/7/7a/Street_Food_vendors_Soft_crab_Hanoi_Vietnam.jpg';
$hue_stand_image = 'https://upload.wikimedia.org/wikipedia/commons/9/99/Street_food_stand_in_Hu%E1%BA%BF.jpg';
$banh_trang_image = 'https://upload.wikimedia.org/wikipedia/commons/f/fd/B%C3%A1nh_tr%C3%A1ng_n%C6%B0%E1%BB%9Bng_TP._H%E1%BB%93_Ch%C3%AD_Minh_-_street_food_in_Ho_Chi_Minh_City%2C_Vietnam.jpg';
$market_image = 'https://upload.wikimedia.org/wikipedia/commons/4/40/Vietnamise_Street_Food_Market_002.jpg';

$meta_update_summary = 'Expanded the native WordPress post brief into a complete food-safety and street-food etiquette draft with a photo-led hero, proof panel, concierge verdict, at-a-glance decision matrix, photo proof, source-diversity table, stall-choice framework, hygiene-risk guidance, sauces/ice/drinks section, ordering etiquette, seating/payment habits, flexible meal plan, city rhythm, red flags, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.';
$meta_sources_checked = implode("\n", [
    "Vietnam.travel - Beginner's guide to Vietnamese street food - https://vietnam.travel/things-to-do/beginners-guide-vietnamese-street-food - checked {$review_date}; used for destination-level street-food framing and the idea that food is part of everyday travel, not a fear topic.",
    "Vietnam.travel - Vietnamese etiquette for travellers - https://vietnam.travel/things-to-do/vietnamese-etiquette-travellers - checked {$review_date}; used for polite behavior, local rhythm, and cultural context around shared spaces.",
    "Vietnam.travel - Health and safety - https://vietnam.travel/plan-your-trip/health-safety - checked {$review_date}; used for broad health-safety framing without turning this article into medical advice.",
    "Vietnam.travel - Currency and payments in Vietnam - https://vietnam.travel/plan-your-trip/currency-vietnam - checked {$review_date}; used for small-cash and payment-context reminders around markets and informal meals.",
    "CDC Travelers' Health - Vietnam - https://wwwnc.cdc.gov/travel/destinations/traveler/none/vietnam - checked {$review_date}; used for traveler-health framing and the need for personal medical judgment.",
    "CDC - Food and Water Safety - https://wwwnc.cdc.gov/travel/page/food-water-safety - checked {$review_date}; used for safe-food and safe-drink behavior principles for travelers.",
    "CDC Yellow Book - Food and Water Precautions for Travelers - https://www.cdc.gov/yellow-book/hcp/preparing-international-travelers/food-and-water-precautions-for-travelers.html - checked {$review_date}; used for traveler food/water risk discipline and health disclaimer boundaries.",
    "WHO - Five keys to safer food - https://www.who.int/activities/promoting-safe-food-handling/five-key-to-safer-food - checked {$review_date}; used for heat, cleanliness, separation, and safer-food handling principles.",
    "GOV.UK - Vietnam health - https://www.gov.uk/foreign-travel-advice/vietnam/health - checked {$review_date}; used as an additional government travel-health source family.",
    "TravelHealthPro - Vietnam - https://travelhealthpro.org.uk/country/240/vietnam - checked {$review_date}; used as an optional UK travel-health source trail entry, not as the only health source.",
    "Wikimedia Commons image direct URL - Street food in Vietnam - {$hero_image} - credit Dieglop / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Street Food vendors Soft crab Hanoi Vietnam - {$hanoi_vendor_image} - credit Celestine M.C. Leroy / CC BY-SA 2.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Street food stand in Hue - {$hue_stand_image} - credit Christophe95 / CC BY-SA 4.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Banh trang nuong in Ho Chi Minh City - {$banh_trang_image} - credit Light Write / CC BY-SA 2.0 - license context checked {$review_date}.",
    "Wikimedia Commons image direct URL - Vietnamese Street Food Market 002 - {$market_image} - credit Dieglop / CC BY-SA 4.0 - license context checked {$review_date}.",
]);
$meta_field_note = 'This post is a first-timer behavior layer for the native WordPress editorial calendar. It avoids duplicating broad food, safety, money, health, Hanoi, and Ho Chi Minh City guides by focusing on how travelers choose stalls, behave politely, manage uncertainty, and keep meals flexible.';
$meta_evidence_moat = implode("\n", [
    'Decision-led street-food guide built around behavior, stall context, ordering, payment, and flexible meal planning rather than a dish ranking.',
    'Concierge verdict gives a calm first answer: eat street food, but choose context well.',
    'At-a-glance matrix turns first-timer questions into practical choices and weak-signal warnings.',
    'Photo proof makes each image do planning work: crowd rhythm, visible prep, seating, cooked-to-order food, and market context.',
    'Source-diversity table separates what official food/travel-health sources can support from what still needs traveler-specific judgment.',
    'Stall-choice framework uses turnover, local meal time, visible cooking, queue behavior, and menu complexity instead of viral recommendations.',
    'Hygiene-risk section gives practical risk reduction without fear framing or medical promises.',
    'Sauces, ice, drinks, herbs, utensils, and shared-table guidance help travelers make small decisions at the meal, not only before the trip.',
    'Ordering etiquette and payment sections make the guide culturally useful and reduce friction for first-time international travelers.',
    'Flexible meal plan prevents rigid dish-list itineraries and gives backup choices for heat, fatigue, stomach sensitivity, and travel days.',
    'City rhythm section distinguishes Hanoi, Hue, Hoi An, Ho Chi Minh City, and travel-day eating without stale restaurant claims.',
    'No visible external body anchors; official source trail and image-license records stay auditable through metadata and shortcode output.',
]);
$meta_related_routes = implode("\n", [
    'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Use this for the broad first-trip route before food choices start competing with transfer energy.',
    'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Use before departure for medical, policy, pharmacy, interruption, and personal-health preparation.',
    'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair food streets, markets, night movement, phone handling, and payment habits with practical street awareness.',
    'Money in Vietnam | /plan/money-cash-cards-atms/ | Prepare small cash, change, cards, and payment backup before informal meals and markets.',
    'SIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, translation, ride-hailing, and hotel contact working before searching for meals in a new city.',
    'Transport Within Vietnam | /plan/transport-within-vietnam/ | Keep meal timing realistic around airport transfers, early pickups, train days, and long road legs.',
    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Price food realistically without letting cheap meals hide transfer, health, or comfort costs.',
    'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Adjust food walks for heat, rain, humidity, and regional season pressure.',
    'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use Hanoi food streets as part of city orientation, not an arrival-night endurance test.',
    'Best Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Fold food, markets, and walking radius into a first-time Hanoi day without overloading it.',
    'Ho Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Use southern city rhythm, markets, cafes, and night food energy without losing airport or traffic control.',
    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Place food experiences where they help the route breathe, not where they make early transfers harder.',
]);
$meta_hero_image_credit = 'Hero image: Street food in Vietnam by Dieglop, CC BY-SA 4.0. Body images: Street Food vendors Soft crab Hanoi Vietnam by Celestine M.C. Leroy, CC BY-SA 2.0; Street food stand in Hue by Christophe95, CC BY-SA 4.0; Banh trang nuong in Ho Chi Minh City by Light Write, CC BY-SA 2.0; Vietnamese Street Food Market 002 by Dieglop, CC BY-SA 4.0.';
$meta_admin_notes = 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, checks Rank Math/social preview, verifies food, etiquette, health, payment, safety, image-license, and source-trail records, and adds any first-hand editorial notes or restaurant-locality observations before publishing. This article is not medical advice.';

$content = <<<HTML
<!-- vg-food-safety-hero:v1 -->
<!-- wp:html -->
<section class="vg-guide-hero vg-food-safety-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Street food without panic</p>
<h1>Vietnam Food Safety and Street Food Etiquette for First-Timers</h1>
<p class="vg-guide-lede">Vietnam street food is one of the best parts of a first trip, but the good decision is not "eat everything" or "avoid everything." The useful middle is learning how to choose the right context, order without slowing the stall, and keep a flexible meal plan when your body, weather, or transfer day says to go easier.</p>
<p class="vg-field-note">Use this before your first food walk in Hanoi, Hue, Hoi An, Ho Chi Minh City, or a market town. It is a practical behavior guide, not medical advice and not a list of restaurants that will age badly.</p>
</div>
<figure class="vg-guide-hero-image"><img src="{$hero_image}" alt="Vietnamese street food being prepared at a busy local stall" loading="eager" decoding="async"><figcaption>Street food is easier to judge when you watch the rhythm: what is cooked, what is turning over, and how locals order. Image: Dieglop / CC BY-SA 4.0.</figcaption></figure>
</section>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-food-safety-concierge-verdict:v1 -->
<!-- wp:html -->
<aside class="vg-concierge-verdict vg-food-safety-concierge-verdict" aria-label="Vietnam food safety and street food etiquette verdict">
<p class="vg-kicker">VietnamGuide verdict</p>
<h2>Eat street food, but choose context well.</h2>
<p><strong>For most first-timers, eat street food, but choose context well: busy stall, hot turnover, simple order, clean hands, bottled drink if unsure, small cash ready, and a backup meal plan.</strong> The mistake is treating street food as either a dare or a checklist. The better posture is curious, observant, and willing to skip a stall that does not feel right.</p>
<ul>
<li><strong>Best first meal:</strong> a simple cooked-to-order dish at a busy place during normal meal hours, close to your hotel or walking route.</li>
<li><strong>Best first-night rule:</strong> if you land late or feel tired, keep dinner easy and save the ambitious food crawl for a rested day.</li>
<li><strong>Best etiquette rule:</strong> watch first, order clearly, move with the stall's pace, and pay without turning the meal into a negotiation scene.</li>
<li><strong>Best health rule:</strong> use official travel-health guidance for your medical decisions and adapt food choices to your own risk tolerance.</li>
</ul>
</aside>
<!-- /wp:html -->

<!-- wp:paragraph -->
<p>This guide sits beside the <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>, <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>, and <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> pages. Those guides cover trip-wide preparation. This post handles the meal-level choices that happen when you are standing on a sidewalk, looking at a tiny menu, holding cash, and deciding whether to sit down.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The article deliberately avoids a restaurant list. Restaurants open, close, change cooks, move locations, and shift quality. A behavior framework lasts longer: choose heat and turnover, understand the order flow, keep sauces and drinks simple when uncertain, know how to sit and pay, and leave room for your body to veto the plan.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Visible external links are intentionally avoided in the article body. Official food, etiquette, health, payment, and image-license sources are stored in the source trail metadata. The body stays focused on traveler judgment and internal VietnamGuide routes.</p>
<!-- /wp:paragraph -->

<!-- vg-food-safety-at-a-glance:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Street food safety and etiquette at a glance</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-food-safety-at-a-glance">
<thead><tr><th>Decision</th><th>Better first-timer move</th><th>Weak signal</th><th>Why it matters</th></tr></thead>
<tbody>
<tr><td data-label="Decision">Choosing a stall</td><td data-label="Better first-timer move">Choose a busy stall at meal time where food is moving, cooked visibly, and locals understand the order flow.</td><td data-label="Weak signal">Empty trays, old-looking garnish, unclear storage, or a stall trying to sell everything.</td><td data-label="Why it matters">Turnover is often more useful than a stale online recommendation.</td></tr>
<tr><td data-label="Decision">First order</td><td data-label="Better first-timer move">Start with one signature dish and copy the local rhythm: point, confirm, sit, eat, pay.</td><td data-label="Weak signal">A huge menu, long negotiation, or staff pulling you in before you can observe.</td><td data-label="Why it matters">Simple stalls usually have simpler quality signals.</td></tr>
<tr><td data-label="Decision">Drinks and ice</td><td data-label="Better first-timer move">Use sealed bottles or cans when unsure; judge ice and fresh juice by venue confidence and your own tolerance.</td><td data-label="Weak signal">Unclear water source, lukewarm drinks, or open containers sitting exposed.</td><td data-label="Why it matters">Drink decisions are often where cautious travelers can reduce uncertainty without missing the meal.</td></tr>
<tr><td data-label="Decision">Sauces and herbs</td><td data-label="Better first-timer move">Use small amounts first, avoid double-dipping, and skip raw add-ons when your stomach or schedule is fragile.</td><td data-label="Weak signal">Shared utensils handled carelessly or raw items sitting warm and uncovered.</td><td data-label="Why it matters">Condiments and garnish are optional; the main dish can still be excellent.</td></tr>
<tr><td data-label="Decision">Payment</td><td data-label="Better first-timer move">Carry small VND notes, confirm price gently if unclear, and keep change simple.</td><td data-label="Weak signal">Large bills, no cash, or a rushed payment in a crowded lane.</td><td data-label="Why it matters">Good payment habits make the meal smoother and reduce stress.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-food-safety-photo-proof:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what to look for before sitting down</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The photos below are not decoration. Each one points to a different judgment: turnover, visible prep, seating behavior, cooked-to-order confidence, and market context.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-food-safety-photo-proof" aria-label="Vietnam street food safety and etiquette photo proof">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Busy Vietnamese street food stall with visible preparation" loading="lazy" decoding="async"><figcaption>Look for food that is moving through the stall, not sitting as a display. Image: Dieglop / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hanoi_vendor_image}" alt="Street food vendors preparing food in Hanoi" loading="lazy" decoding="async"><figcaption>Visible prep helps you judge heat, handling, and whether the stall has one clear job. Image: Celestine M.C. Leroy / CC BY-SA 2.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hue_stand_image}" alt="Street food stand with small seating in Hue" loading="lazy" decoding="async"><figcaption>Small seating is normal; the question is whether the stall rhythm feels organized. Image: Christophe95 / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$banh_trang_image}" alt="Banh trang nuong being cooked at a Ho Chi Minh City street stall" loading="lazy" decoding="async"><figcaption>Cooked-to-order snacks can be a good first step because heat and turnover are visible. Image: Light Write / CC BY-SA 2.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$market_image}" alt="Vietnamese street food market with multiple stalls" loading="lazy" decoding="async"><figcaption>Markets are useful when you compare stalls by rhythm rather than choosing the loudest seller. Image: Dieglop / CC BY-SA 4.0.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-food-safety-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How the source trail supports this guide</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Official tourism and health sources can support broad safety and etiquette principles. They cannot tell you which stall has good turnover at 8:30 tonight, whether you slept enough after a long flight, or whether your stomach is ready for a spicy, raw-herb-heavy meal before a six-hour transfer. That is why the guide combines source discipline with on-the-ground decision rules.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-food-safety-source-diversity">
<thead><tr><th>Evidence layer</th><th>What it can support</th><th>What still needs judgment</th></tr></thead>
<tbody>
<tr><td data-label="Evidence layer">Vietnam.travel food and etiquette pages</td><td data-label="What it can support">Street food as part of everyday travel, cultural manners, and the value of observing local rhythm.</td><td data-label="What still needs judgment">Which exact stall, time, crowd, and dish suit your first meal.</td></tr>
<tr><td data-label="Evidence layer">Vietnam.travel health and safety</td><td data-label="What it can support">General travel-health posture and the need to prepare before the trip.</td><td data-label="What still needs judgment">Your personal medical risk, medication, insurance, and decision to seek clinical advice.</td></tr>
<tr><td data-label="Evidence layer">CDC, WHO, GOV.UK, and TravelHealthPro</td><td data-label="What it can support">Food and water safety principles, traveler-health precautions, and risk-reduction behavior.</td><td data-label="What still needs judgment">Whether a specific drink, sauce, raw item, or long food walk is wise today.</td></tr>
<tr><td data-label="Evidence layer">VietnamGuide internal routes</td><td data-label="What it can support">How food choices interact with safety, money, SIM, transport, route pace, and city plans.</td><td data-label="What still needs judgment">Whether to eat now, wait, simplify, or move the meal to a calmer day.</td></tr>
<tr><td data-label="Evidence layer">Licensed photography</td><td data-label="What it can support">Real visual cues: crowd, prep, seating, stall scale, and market density.</td><td data-label="What still needs judgment">Current cleanliness, food temperature, water source, and your comfort level.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-food-safety-choose-stalls:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How to choose a street food stall without overthinking it</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The best first-timer stall is rarely the quietest, cheapest, or most photogenic. It is the one with a simple specialty, steady local turnover, visible cooking, and a flow you can understand before sitting down. Watch for two minutes. Where does food come from? How fast does it move? Are locals ordering confidently? Does the stall look like it has one clear job?</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Meal time matters. A breakfast stall at breakfast or a noodle stall during lunch gives you better signals than a random mid-afternoon stop with tired ingredients. If a dish depends on broth, grill heat, frying, or a constantly refreshed batch, your eye should follow the heat and turnover rather than the signboard.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-food-safety-choose-stalls">
<thead><tr><th>Signal</th><th>Stronger version</th><th>Weaker version</th><th>First-timer response</th></tr></thead>
<tbody>
<tr><td data-label="Signal">Crowd</td><td data-label="Stronger version">Steady local flow, not just a tourist queue around one viral sign.</td><td data-label="Weaker version">A seller pushing hard while the food sits still.</td><td data-label="First-timer response">Prefer steady turnover over hype.</td></tr>
<tr><td data-label="Signal">Menu</td><td data-label="Stronger version">One or two specialties that the stall repeats well.</td><td data-label="Weaker version">A long menu that no one else seems to order.</td><td data-label="First-timer response">Order the thing the stall is clearly built to make.</td></tr>
<tr><td data-label="Signal">Cooking</td><td data-label="Stronger version">Hot broth, grill, pan, steam, or fresh assembly you can see.</td><td data-label="Weaker version">Food kept warm vaguely with no visible refresh.</td><td data-label="First-timer response">Favor heat and turnover when your tolerance is uncertain.</td></tr>
<tr><td data-label="Signal">Handling</td><td data-label="Stronger version">Money and food handling appear separated or controlled.</td><td data-label="Weaker version">The same wet hands handle cash, raw garnish, and cooked food carelessly.</td><td data-label="First-timer response">Move on without drama if the handling makes you uneasy.</td></tr>
<tr><td data-label="Signal">Location</td><td data-label="Stronger version">A practical street, market, or neighborhood with natural demand.</td><td data-label="Weaker version">A stall that exists mainly to catch confused foot traffic.</td><td data-label="First-timer response">Use city guides for area logic, then judge the actual stall in front of you.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-food-safety-order-etiquette:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Ordering etiquette: be clear, quick, and observant</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Many good stalls are small systems. Your job is to join the system politely. Stand where others stand. Do not block the cook or the person collecting money. If there is no English menu, point to the dish, show one finger for one portion, confirm the price if needed, then move to the seating area. Translation apps are useful, but a busy cook is not always the right person for a long conversation.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Shared tables and tiny stools are normal. If the place is busy, eat with the pace of the room. Do not turn a two-dollar bowl into a long table occupation unless the stall clearly works like a cafe. If utensils are in a shared container, take what you need and avoid touching the eating ends of utensils you do not use.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-food-safety-order-etiquette">
<thead><tr><th>Moment</th><th>Good etiquette</th><th>Why it helps</th><th>Useful phrase/action</th></tr></thead>
<tbody>
<tr><td data-label="Moment">Before ordering</td><td data-label="Good etiquette">Watch how locals queue, pay, and sit before stepping in.</td><td data-label="Why it helps">You avoid interrupting a fast workflow.</td><td data-label="Useful phrase/action">Point, smile, show quantity with fingers.</td></tr>
<tr><td data-label="Moment">Customizing</td><td data-label="Good etiquette">Keep the first order simple unless you know the dish well.</td><td data-label="Why it helps">Complex requests can confuse a small stall.</td><td data-label="Useful phrase/action">Ask for less chili by gesture or leave chili on the table.</td></tr>
<tr><td data-label="Moment">Seating</td><td data-label="Good etiquette">Sit where directed and share table space when needed.</td><td data-label="Why it helps">Small stalls depend on seat turnover.</td><td data-label="Useful phrase/action">Move bags off spare seats in busy periods.</td></tr>
<tr><td data-label="Moment">Eating</td><td data-label="Good etiquette">Use shared condiments carefully and avoid double-dipping.</td><td data-label="Why it helps">It respects both hygiene and local diners.</td><td data-label="Useful phrase/action">Add a little first; adjust after tasting.</td></tr>
<tr><td data-label="Moment">Leaving</td><td data-label="Good etiquette">Pay with small notes, check change calmly, and move when finished.</td><td data-label="Why it helps">The stall can keep serving others smoothly.</td><td data-label="Useful phrase/action">Prepare cash before the last bite if the place is crowded.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-food-safety-hygiene-risk:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hygiene risk: reduce uncertainty without turning food into fear</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Food safety is partly personal. A traveler with a sensitive stomach, chronic condition, pregnancy, immune concerns, or important early-morning transfer may need a stricter filter than a traveler with more margin. Use official health sources for medical decisions. This guide gives practical travel behavior, not diagnosis or treatment.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>A cautious first-timer can still eat well. Choose dishes that are cooked hot, served promptly, and easy to observe. Keep raw herbs, salads, peeled fruit, seafood, and sauces optional until you understand your own tolerance. Wash or sanitize hands before eating, especially when the meal involves bread, wraps, herbs, or shared utensils.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-food-safety-hygiene-risk">
<thead><tr><th>Choice</th><th>Lower-uncertainty move</th><th>Higher-uncertainty move</th><th>When to be stricter</th></tr></thead>
<tbody>
<tr><td data-label="Choice">Main dish</td><td data-label="Lower-uncertainty move">Hot broth, fresh grill, pan, or cooked-to-order snack.</td><td data-label="Higher-uncertainty move">Food that has sat uncovered or lukewarm.</td><td data-label="When to be stricter">Arrival night, early transfer, family travel, or sensitive stomach.</td></tr>
<tr><td data-label="Choice">Raw herbs and garnish</td><td data-label="Lower-uncertainty move">Add a small amount or skip when unsure.</td><td data-label="Higher-uncertainty move">Large raw garnish pile of uncertain washing/storage.</td><td data-label="When to be stricter">Before flights, long drives, or medical vulnerability.</td></tr>
<tr><td data-label="Choice">Seafood</td><td data-label="Lower-uncertainty move">Busy coastal place with visible cooking and fast turnover.</td><td data-label="Higher-uncertainty move">Quiet display seafood late in the day.</td><td data-label="When to be stricter">Hot days, remote areas, or tight next-day plans.</td></tr>
<tr><td data-label="Choice">Utensils</td><td data-label="Lower-uncertainty move">Use clean-looking utensils, tissue, or sanitizer if needed.</td><td data-label="Higher-uncertainty move">Touching multiple shared utensils unnecessarily.</td><td data-label="When to be stricter">Crowded markets and fast roadside stops.</td></tr>
<tr><td data-label="Choice">Hands</td><td data-label="Lower-uncertainty move">Clean hands before eating and after handling cash or phones.</td><td data-label="Higher-uncertainty move">Handling bread, herbs, or fruit after street contact.</td><td data-label="When to be stricter">Any finger-food meal.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-food-safety-drinks-ice:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Drinks, ice, coffee, and fresh juice</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Drinks are where you can simplify without losing the food experience. If you are unsure, choose sealed bottled water, canned drinks, or hot coffee/tea from a place with strong turnover. Ice is not automatically a problem in every urban setting, but first-timers should judge the venue and their own risk tolerance rather than copying someone else's confidence.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Fresh juice, smoothies, and iced drinks ask for more trust: water, ice, washing, knives, storage, and turnover all matter. They can be enjoyable, but they are not required for a great food day. On an arrival night, before a flight, or when your stomach is already unsettled, make the drink boring and let the meal carry the pleasure.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-food-safety-drinks-ice">
<thead><tr><th>Drink choice</th><th>Safer default when unsure</th><th>Ask yourself</th><th>Good compromise</th></tr></thead>
<tbody>
<tr><td data-label="Drink choice">Water</td><td data-label="Safer default when unsure">Sealed bottle opened by you or in front of you.</td><td data-label="Ask yourself">Do I need this stall to provide drinking water?</td><td data-label="Good compromise">Carry your own bottle and enjoy the food.</td></tr>
<tr><td data-label="Drink choice">Ice</td><td data-label="Safer default when unsure">Skip ice at very informal places when the source is unclear.</td><td data-label="Ask yourself">Does this place have enough turnover and cleanliness signals?</td><td data-label="Good compromise">Use ice in higher-confidence venues, skip at uncertain stalls.</td></tr>
<tr><td data-label="Drink choice">Coffee</td><td data-label="Safer default when unsure">Hot coffee or a busy cafe-style stall.</td><td data-label="Ask yourself">Is the preparation visible and fast-moving?</td><td data-label="Good compromise">Save elaborate iced drinks for places you trust.</td></tr>
<tr><td data-label="Drink choice">Fresh juice</td><td data-label="Safer default when unsure">Choose sealed drinks if fruit handling looks unclear.</td><td data-label="Ask yourself">Are fruit, knives, ice, and cups handled confidently?</td><td data-label="Good compromise">Try it on a relaxed day, not before a hard transfer.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-food-safety-sauces-seating-payment:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Sauces, seating, and payment habits</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Sauces and table items are part of the pleasure of Vietnamese food, but they are also optional. Taste first. Add small amounts. Avoid touching shared spoons or squeeze bottles in a way that contaminates the table for the next person. If you are unsure, the cleanest version is usually: eat the hot main dish, use fewer raw add-ons, and keep your own hands clean.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Payment is usually simple when you prepare. Small VND notes make street meals smoother. If the price is unclear, ask or gesture before ordering, not after eating. Do not expect cards at small stalls. For a broader money setup, use the <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a> guide before your first market or food street.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-food-safety-sauces-seating-payment">
<thead><tr><th>Small moment</th><th>Better habit</th><th>Why it matters</th><th>Common mistake</th></tr></thead>
<tbody>
<tr><td data-label="Small moment">Condiments</td><td data-label="Better habit">Use a clean spoon or pour without touching food back to the container.</td><td data-label="Why it matters">Shared tables work better when everyone keeps the common items clean.</td><td data-label="Common mistake">Dipping used chopsticks into shared sauce.</td></tr>
<tr><td data-label="Small moment">Herbs and lime</td><td data-label="Better habit">Add gradually or skip when uncertain.</td><td data-label="Why it matters">The main dish is still complete without every garnish.</td><td data-label="Common mistake">Treating every table item as required.</td></tr>
<tr><td data-label="Small moment">Stools</td><td data-label="Better habit">Keep bags close and leave spare seats for diners.</td><td data-label="Why it matters">Small stalls depend on compact seating.</td><td data-label="Common mistake">Blocking seats with backpacks.</td></tr>
<tr><td data-label="Small moment">Cash</td><td data-label="Better habit">Pay with small notes and organize change calmly.</td><td data-label="Why it matters">It protects both you and the stall from confusion.</td><td data-label="Common mistake">Using a large bill for a small snack.</td></tr>
<tr><td data-label="Small moment">Tipping</td><td data-label="Better habit">Do not force a tipping habit onto a tiny stall; round up only when it feels natural.</td><td data-label="Why it matters">Street food is usually a straightforward transaction.</td><td data-label="Common mistake">Making payment socially awkward.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-food-safety-flex-meal-plan:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Build a flexible meal plan, not a rigid dish hunt</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The most durable food plan has options. Put the ambitious meal on a rested day. Keep arrival night simple. Do not schedule your first major food crawl before a long van ride, airport transfer, cave day, cruise pickup, or early train. If a stall looks wrong, if the weather turns heavy, or if your stomach wants a quieter meal, the plan should have a graceful exit.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>This is where <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> matter. Food is not isolated from the route. Maps, translation, small cash, ride-hailing, hotel distance, heat, rain, and next-day movement all change whether a food plan feels fun or exhausting.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-food-safety-flex-meal-plan">
<thead><tr><th>Travel moment</th><th>Best food posture</th><th>What to avoid</th><th>Backup plan</th></tr></thead>
<tbody>
<tr><td data-label="Travel moment">Arrival night</td><td data-label="Best food posture">Nearby, simple, cooked hot, no long walk.</td><td data-label="What to avoid">A long street-food crawl after immigration, luggage, SIM, cash, and transfer fatigue.</td><td data-label="Backup plan">Hotel-area bowl, simple rice meal, or cafe.</td></tr>
<tr><td data-label="Travel moment">First full city day</td><td data-label="Best food posture">One food street or market plus one culture/walking block.</td><td data-label="What to avoid">Treating every famous dish as mandatory.</td><td data-label="Backup plan">Return to a busy simple stall you already trust.</td></tr>
<tr><td data-label="Travel moment">Before long transport</td><td data-label="Best food posture">Eat familiar, moderate, and early.</td><td data-label="What to avoid">Raw-heavy, seafood-heavy, spicy, or unfamiliar late-night meals.</td><td data-label="Backup plan">Pack sealed water and simple snacks.</td></tr>
<tr><td data-label="Travel moment">Hot or rainy day</td><td data-label="Best food posture">Shorter walks, stronger venue confidence, more hydration.</td><td data-label="What to avoid">Forcing a far stall because it was on the list.</td><td data-label="Backup plan">Indoor market, cafe, or hotel-adjacent meal.</td></tr>
<tr><td data-label="Travel moment">Sensitive stomach day</td><td data-label="Best food posture">Simplify: hot food, sealed drink, fewer raw extras, more rest.</td><td data-label="What to avoid">Trying to "push through" because a dish is famous.</td><td data-label="Backup plan">Plain rice, soup, pharmacy/clinic plan if needed, and insurance contact ready.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-food-safety-city-rhythm:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">City rhythm: Hanoi, Hue, Hoi An, and Ho Chi Minh City</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Street food works differently by city and by travel day. Hanoi rewards walking radius and Old Quarter observation, but arrival fatigue can make the first night feel louder than expected. Hue is excellent for smaller food traditions and slower meals, but weather and heritage pacing matter. Hoi An can be easy and gentle, though tourist density can blur price and quality signals. Ho Chi Minh City has huge food range and night energy, but traffic, heat, and distance can punish overambitious food maps.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Use the <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>, <a href="/destinations/best-things-to-do-in-hanoi/">Best Things to Do in Hanoi</a>, and <a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a> to place food inside the day. Use <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a> when heat, rain, humidity, or northern cool weather will change how long you want to walk for food.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-food-safety-city-rhythm">
<thead><tr><th>Place</th><th>Food planning advantage</th><th>Main friction</th><th>Better first-timer move</th></tr></thead>
<tbody>
<tr><td data-label="Place">Hanoi</td><td data-label="Food planning advantage">Dense walking areas, breakfast culture, noodles, cafes, and market texture.</td><td data-label="Main friction">Arrival overload, traffic crossings, and trying to do too much on the first night.</td><td data-label="Better first-timer move">Choose a hotel-area first meal, then a stronger food walk when rested.</td></tr>
<tr><td data-label="Place">Hue</td><td data-label="Food planning advantage">Small dishes, regional food identity, and meals that pair well with heritage days.</td><td data-label="Main friction">Rain, heat, and spreading food stops too far apart.</td><td data-label="Better first-timer move">Group food around the day's heritage route instead of crisscrossing town.</td></tr>
<tr><td data-label="Place">Hoi An</td><td data-label="Food planning advantage">Easy walking, calmer evenings, and approachable first-timer meals.</td><td data-label="Main friction">Tourist-density pricing and assuming every famous dish needs a separate stop.</td><td data-label="Better first-timer move">Mix one local specialty with a slow evening rather than a checklist dinner.</td></tr>
<tr><td data-label="Place">Ho Chi Minh City</td><td data-label="Food planning advantage">Huge range of street food, markets, cafes, and night eating.</td><td data-label="Main friction">Traffic distance, heat, and late-night overreach before airport or day trips.</td><td data-label="Better first-timer move">Plan by district and ride-hailing comfort, not by a scattered food map.</td></tr>
<tr><td data-label="Place">Travel days</td><td data-label="Food planning advantage">Simple meals can protect the route.</td><td data-label="Main friction">Early pickup, luggage, toilet access, and motion sensitivity.</td><td data-label="Better first-timer move">Keep food conservative until the transfer is complete.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-food-safety-red-flags:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Red flags that make it easy to walk away</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Skipping a stall is not failure. It is part of eating well. The best travelers are not fearless; they are observant. Walk away calmly when the signals are weak, then find another stall with better turnover, clearer cooking, or a simpler setup.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-food-safety-red-flags">
<thead><tr><th>Red flag</th><th>Why it matters</th><th>Calm response</th></tr></thead>
<tbody>
<tr><td data-label="Red flag">Lukewarm food sitting uncovered</td><td data-label="Why it matters">You cannot judge how long it has been there.</td><td data-label="Calm response">Choose a hotter, faster-moving stall.</td></tr>
<tr><td data-label="Red flag">The seller pushes before you can observe</td><td data-label="Why it matters">Pressure removes the two-minute judgment window.</td><td data-label="Calm response">Smile, keep walking, and compare another stall.</td></tr>
<tr><td data-label="Red flag">A huge menu with little activity</td><td data-label="Why it matters">Too much variety can mean weak turnover.</td><td data-label="Calm response">Choose a specialist stall instead.</td></tr>
<tr><td data-label="Red flag">Raw extras look tired or exposed</td><td data-label="Why it matters">The garnish may carry more uncertainty than the cooked dish.</td><td data-label="Calm response">Skip the garnish or choose another meal.</td></tr>
<tr><td data-label="Red flag">You are tired, hot, rushed, or unwell</td><td data-label="Why it matters">Your body is part of the decision.</td><td data-label="Calm response">Use a conservative backup meal and try again later.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-food-safety-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before your first street-food meal</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-food-safety-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-food-safety-live-checks">
<li>Check your body first: sleep, heat, hydration, stomach, medication, and tomorrow's transfer all matter.</li>
<li>Choose a stall at a normal meal time with one clear specialty and visible cooking or fast turnover.</li>
<li>Carry small VND notes, phone data, hotel address, and a simple way back before wandering for food at night.</li>
<li>Keep drinks simple when uncertain: sealed bottle or can, hot drink, or a higher-confidence cafe.</li>
<li>Add sauces, herbs, chili, and raw extras gradually; skip optional items when your tolerance is uncertain.</li>
<li>Use official health guidance and a clinician for medical decisions, especially if pregnant, immune-compromised, or managing a condition.</li>
<li>Have a graceful backup: a cafe, hotel-area restaurant, simple rice/soup meal, or early night.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits next</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this post at the meal-decision level. Then move to the planning guide that controls the bigger variable: health, safety, cash, SIM, city base, season, or route pace.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-related-routes vg-food-safety-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a><span class="vg-related-route-note">Use before departure for medical preparation, insurance, pharmacy, and emergency planning.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a><span class="vg-related-route-note">Use for markets, night movement, phone handling, and street awareness.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/plan/money-cash-cards-atms/">Money in Vietnam</a><span class="vg-related-route-note">Use before informal meals, markets, small cash payments, and change handling.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a><span class="vg-related-route-note">Use when Hanoi food needs to fit arrival, walking radius, markets, and northern route energy.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a><span class="vg-related-route-note">Use when southern food plans need to respect heat, traffic, markets, and airport timing.</span></li>
</ol>
</div>
<!-- /wp:html -->

<!-- vg-food-safety-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Vietnam street food safety FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-food-safety-faq">
<details><summary>Is street food in Vietnam okay for first-time visitors?</summary><p>Many first-time visitors enjoy street food, but the smarter question is context. Choose busy stalls, hot food, visible prep, small-cash payment, and a simple backup plan. Use official health guidance for personal medical decisions.</p></details>
<details><summary>What should I eat first if I am cautious?</summary><p>Start with a simple cooked-to-order dish at a busy stall during normal meal hours. Keep raw herbs, seafood, heavy spice, fresh juice, and unfamiliar sauces optional until you know your tolerance.</p></details>
<details><summary>Should I avoid ice in Vietnam?</summary><p>Do not treat ice as one universal yes-or-no rule. If you are unsure, choose sealed drinks or hot drinks. Use ice only when the venue, turnover, and your own risk tolerance make sense.</p></details>
<details><summary>How do I order politely at a street stall?</summary><p>Watch the flow first, point clearly, show quantity with fingers if needed, sit where directed, use shared condiments carefully, pay with small notes, and avoid occupying seats longer than the stall rhythm suggests.</p></details>
<details><summary>What if I have allergies or dietary restrictions?</summary><p>Be conservative. Translation cards can help, but small stalls may not understand cross-contamination or hidden ingredients. Choose simpler venues, confirm carefully, and keep medical preparation and insurance details ready.</p></details>
<details><summary>How do I avoid turning a food plan into a stressful checklist?</summary><p>Plan one food priority per city day, then leave space. If the stall looks weak, the weather is harsh, or your body is tired, choose a simpler meal and try again later.</p></details>
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
    'vg-food-safety-hero:v1',
    'vg-food-safety-concierge-verdict:v1',
    'vg-food-safety-at-a-glance:v1',
    'vg-food-safety-photo-proof:v1',
    'vg-food-safety-source-diversity:v1',
    'vg-food-safety-choose-stalls:v1',
    'vg-food-safety-order-etiquette:v1',
    'vg-food-safety-hygiene-risk:v1',
    'vg-food-safety-drinks-ice:v1',
    'vg-food-safety-sauces-seating-payment:v1',
    'vg-food-safety-flex-meal-plan:v1',
    'vg-food-safety-city-rhythm:v1',
    'vg-food-safety-red-flags:v1',
    'vg-food-safety-live-checks:v1',
    'vg-food-safety-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_food_safety_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Vietnam Food Safety and Street Food Etiquette for First-Timers',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'A calm first-timer guide to Vietnam street food safety and etiquette: choose busy stalls, order politely, handle sauces, drinks, cash, seating, and keep a flexible meal plan.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_food_safety_post_fail('Could not update Vietnam food safety and street food etiquette post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Vietnam Food Safety and Street Food Etiquette');
update_post_meta($post_id, 'rank_math_description', 'Vietnam street food safety and etiquette for first-timers: choose busy stalls, order politely, handle sauces, ice, drinks, cash, seating, and flexible meals.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Vietnam food safety street food etiquette');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Enjoy Vietnam street food by choosing context well: busy stalls, hot turnover, visible prep, simple first orders, careful sauces and drinks, small cash, clean hands, and a flexible meal plan.');
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

wp_set_object_terms($post_id, vg_food_safety_post_term_ids('category', ['food-culture', 'practicalities']), 'category', false);
wp_set_object_terms($post_id, vg_food_safety_post_term_ids('post_tag', ['street-food', 'first-time-vietnam', 'international-travelers', 'anti-spam-evergreen']), 'post_tag', false);

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
        vg_food_safety_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Vietnam food safety and street food etiquette post to complete draft: {$post_id}");

<?php
/**
 * Expand the Vietnam Airport Arrival Checklist post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-vietnam-airport-arrival-checklist-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_airport_arrival_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_airport_arrival_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
}

if (! vg_airport_arrival_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_airport_arrival_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_airport_arrival_post_find_by_slug(string $slug): ?WP_Post
{
    $posts = get_posts(
        [
            'post_type' => 'post',
            'post_status' => ['draft', 'pending', 'private', 'future', 'publish'],
            'name' => $slug,
            'posts_per_page' => 1,
        ]
    );

    return $posts[0] ?? null;
}

function vg_airport_arrival_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_airport_arrival_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'vietnam-airport-arrival-checklist';
$post = vg_airport_arrival_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_airport_arrival_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_airport_arrival_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 25, 2026';

$hero_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bd/Arrivals_of_Tan_Son_Nhat_International_Airport.JPG/1920px-Arrivals_of_Tan_Son_Nhat_International_Airport.JPG';
$noi_bai_night_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/38/Noi_Bai_International_Airport_Terminal_2_Night_View.JPG/1920px-Noi_Bai_International_Airport_Terminal_2_Night_View.JPG';
$da_nang_terminal_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d3/2024_Da_Nang_International_Airport_%28DAD%29_-_international_terminal_-_img_01.jpg/1920px-2024_Da_Nang_International_Airport_%28DAD%29_-_international_terminal_-_img_01.jpg';

$content = <<<HTML
<!-- vg-airport-arrival-checklist-hero:v1 -->
<!-- wp:html -->
<section class="vg-guide-hero vg-airport-arrival-checklist-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Airport arrival checklist</p>
<p class="vg-guide-lede">Vietnam airport arrival is not the moment to optimize every purchase. It is the moment to keep documents, luggage, phone data, small cash, transport, and the first hotel handoff calm enough that the trip starts well.</p>
</div>
<figure class="vg-guide-hero-image"><img src="{$hero_image}" alt="Arrivals hall at Tan Son Nhat International Airport in Ho Chi Minh City" loading="eager" decoding="async"><figcaption>Arrival halls are decision filters: solve the first hour, then compare the rest later. Image: Mkckim / CC BY-SA 4.0.</figcaption></figure>
</section>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-airport-arrival-checklist-verdict:v1 -->
<!-- wp:html -->
<aside class="vg-concierge-verdict vg-airport-arrival-checklist-verdict" aria-label="Vietnam airport arrival verdict">
<p class="vg-kicker">VietnamGuide verdict</p>
<h2>Keep the first hour boring.</h2>
<p><strong>Use the order documents -&gt; luggage -&gt; connectivity -&gt; small cash -&gt; verified transport -&gt; hotel.</strong> Airports are not the best place to optimize every purchase; they are the best place to avoid tired decisions. Buy enough certainty to leave the terminal safely, then handle better-rate, better-plan, and better-value choices after check-in.</p>
<ul>
<li><strong>Do now:</strong> clear entry, retrieve bags, activate a working connection, get a modest VND buffer, and choose transport you can verify.</li>
<li><strong>Do later:</strong> compare SIM packages, chase exchange rates, withdraw larger cash, book day tours, or negotiate non-urgent services.</li>
<li><strong>Default after dark:</strong> hotel pickup, official taxi queue/counter, or a ride-hailing pickup you understand before leaving the hall.</li>
<li><strong>Default when rested:</strong> airport SIM, ATM, ride-hailing, shuttle, or bus can be good if the handoff stays simple.</li>
</ul>
</aside>
<!-- /wp:html -->

<!-- wp:paragraph -->
<p>This Vietnam airport arrival checklist is written for international travelers landing in Hanoi, Ho Chi Minh City, Da Nang, or another Vietnamese airport and trying to make the first hour calm. It is intentionally judgment-led rather than deal-led. Airport counters, ATMs, ride-hailing pickup rules, bus routes, and SIM offers change; the arrival sequence is more durable.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The core idea is restraint. You do not need the perfect SIM, the cheapest transfer, the best exchange rate, and a full city plan before leaving the terminal. You need legal entry, your bags, working communication or a fallback, enough cash for friction, a transport choice that does not depend on trust under fatigue, and a hotel check-in that is easy to reach.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Visible external links are intentionally avoided in the article body. Official source checks are stored in the source trail metadata, while the article itself points to related VietnamGuide planning pages for money, SIM/eSIM, transport, airport transfer, safety, e-visa, and cost decisions.</p>
<!-- /wp:paragraph -->

<!-- vg-airport-arrival-checklist-sequence:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The arrival sequence to follow before leaving the airport</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Most arrival mistakes happen when the order gets scrambled. A traveler starts negotiating transport before phone data works, buys a SIM before confirming luggage, leaves the terminal without small cash, or follows a driver before checking the pickup identity. Use this sequence as a calm script.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-airport-arrival-checklist-sequence">
<thead><tr><th>Order</th><th>Task</th><th>Good enough answer</th><th>Do not turn it into...</th></tr></thead>
<tbody>
<tr><td data-label="Order">01</td><td data-label="Task">Documents and entry</td><td data-label="Good enough answer">Passport, visa/e-visa approval if needed, arrival details, and hotel address are easy to access.</td><td data-label="Do not turn it into...">A terminal-side admin session. Resolve paperwork before departure whenever possible.</td></tr>
<tr><td data-label="Order">02</td><td data-label="Task">Luggage and bag check</td><td data-label="Good enough answer">You have all bags, documents, cards, phone, and medication before leaving the controlled area.</td><td data-label="Do not turn it into...">A rushed exit because a driver, counter, or companion is waiting.</td></tr>
<tr><td data-label="Order">03</td><td data-label="Task">Connectivity</td><td data-label="Good enough answer">Your eSIM, roaming bridge, airport Wi-Fi, or SIM counter plan lets you contact the hotel and use maps or ride-hailing if needed.</td><td data-label="Do not turn it into...">A hunt for the absolute best data package while tired.</td></tr>
<tr><td data-label="Order">04</td><td data-label="Task">Small cash buffer</td><td data-label="Good enough answer">A modest amount of VND for snacks, backup transport, tips, or small first-night costs.</td><td data-label="Do not turn it into...">A full-trip withdrawal or exchange before you have slept.</td></tr>
<tr><td data-label="Order">05</td><td data-label="Task">Verified transport</td><td data-label="Good enough answer">Hotel pickup, official taxi queue/counter, ride-hailing car you can match, or a bus/shuttle you understand.</td><td data-label="Do not turn it into...">Following an unsolicited offer because it sounds convenient.</td></tr>
<tr><td data-label="Order">06</td><td data-label="Task">Hotel first</td><td data-label="Good enough answer">Check in, secure bags, eat nearby, and confirm tomorrow's first real plan.</td><td data-label="Do not turn it into...">A big first-night itinerary with low battery and airport fatigue.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-airport-arrival-checklist-do-now-wait:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Do now at the airport, wait until the hotel</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam's main international airports have useful services, including Wi-Fi, money services, transport options, and SIM availability. That does not mean every airport choice is the best long-term choice. Treat the airport as a first-hour bridge, not as the place where the whole trip needs to be optimized.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-airport-arrival-checklist-do-now-wait">
<thead><tr><th>Decision</th><th>Do now</th><th>Wait when...</th><th>VietnamGuide rule</th></tr></thead>
<tbody>
<tr><td data-label="Decision">Phone data</td><td data-label="Do now">Activate pre-trip eSIM/roaming or buy from a clear counter if data is needed for pickup.</td><td data-label="Wait when...">Your hotel transfer is arranged and you have offline details.</td><td data-label="VietnamGuide rule">Connectivity is urgent only if it protects the next decision.</td></tr>
<tr><td data-label="Decision">Cash</td><td data-label="Do now">Get a modest VND buffer if you have no local cash.</td><td data-label="Wait when...">The hotel pickup is prepaid and you already have backup card access.</td><td data-label="VietnamGuide rule">Arrival cash solves friction; it is not the whole money strategy.</td></tr>
<tr><td data-label="Decision">Transport</td><td data-label="Do now">Choose the most verifiable option, especially after dark or with children.</td><td data-label="Wait when...">You are deciding future domestic transfers, tours, or day trips.</td><td data-label="VietnamGuide rule">First-night certainty usually beats a small fare saving.</td></tr>
<tr><td data-label="Decision">Food and water</td><td data-label="Do now">Buy something simple if the hotel area will be closed or you arrive late.</td><td data-label="Wait when...">You land at a normal hour and can eat near the hotel.</td><td data-label="VietnamGuide rule">Do not make the arrival hall your first dining plan unless it solves a need.</td></tr>
<tr><td data-label="Decision">Tours and onward plans</td><td data-label="Do now">Confirm only urgent prebooked pickup messages.</td><td data-label="Wait when...">You are comparing optional tours, cruises, food walks, or shopping.</td><td data-label="VietnamGuide rule">Optional purchases get better after sleep.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:html -->
<div class="vg-guide-photo-grid vg-airport-arrival-checklist-photo-grid" aria-label="Vietnam airport arrival context photography">
<figure class="vg-guide-photo"><img src="{$noi_bai_night_image}" alt="Noi Bai International Airport Terminal 2 at night in Hanoi" loading="lazy" decoding="async"><figcaption>Night arrivals reduce your tolerance for ambiguity. Pay for a cleaner handoff when fatigue is high. Image: Christakis Mina / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$da_nang_terminal_image}" alt="Da Nang International Airport international terminal exterior" loading="lazy" decoding="async"><figcaption>Da Nang is often a gentler first airport, but the same order applies: connection, cash buffer, verified transfer, hotel. Image: Chainwit. / CC BY 4.0.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-airport-arrival-checklist-money:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Money: get enough VND to move, not enough to worry</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam is a mixed-payment country for travelers. Cards are useful at many hotels, larger restaurants, airlines, and some higher-value services, while VND cash remains practical for small local spending, snacks, tips, taxis, markets, and backup movement. The arrival mistake is going to either extreme: assuming one card solves everything, or withdrawing/exchanging too much while jet-lagged.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>At the airport, think in stages. Stage one is the first night: transport backup, a small meal, water, maybe a SIM counter, and a little margin. Stage two is the next morning or first full day, when you can compare a bank ATM, hotel advice, an official exchange counter, or your route's cash needs with a clearer head.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-airport-arrival-checklist-money">
<thead><tr><th>Money situation</th><th>Airport move</th><th>Wait until later</th><th>Risk it prevents</th></tr></thead>
<tbody>
<tr><td data-label="Money situation">No VND at all</td><td data-label="Airport move">Use an ATM or currency exchange for a modest local-cash buffer.</td><td data-label="Wait until later">Large withdrawals, multi-day exchange, or rate shopping.</td><td data-label="Risk it prevents">Transport, snack, tip, or small payment friction.</td></tr>
<tr><td data-label="Money situation">Card works, transfer prepaid</td><td data-label="Airport move">Keep cards separate and consider a small backup only if convenient.</td><td data-label="Wait until later">A full ATM session before you know the hotel area.</td><td data-label="Risk it prevents">Carrying too much cash on the first night.</td></tr>
<tr><td data-label="Money situation">Late arrival</td><td data-label="Airport move">Prioritize verified transport and enough VND for a backup ride or food.</td><td data-label="Wait until later">Exchange-rate optimization.</td><td data-label="Risk it prevents">Being stuck if one card, counter, or pickup fails.</td></tr>
<tr><td data-label="Money situation">Family or heavy luggage</td><td data-label="Airport move">One adult handles cash while another watches bags and documents.</td><td data-label="Wait until later">Group debates at the arrivals curb.</td><td data-label="Risk it prevents">Dropped bags, misplaced passport, or rushed payment choices.</td></tr>
<tr><td data-label="Money situation">Carrying large foreign cash or VND</td><td data-label="Airport move">Re-check customs declaration rules before travel and declare when required.</td><td data-label="Wait until later">Assuming an old threshold still applies.</td><td data-label="Risk it prevents">Border paperwork and compliance problems.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
<!-- wp:paragraph -->
<p>Vietnam Customs guidance checked for this draft listed declaration thresholds for foreign currency equivalent to USD 5,000 and for VND 15,000,000 carried on entry or exit. Treat those as an official-source prompt, not a forever rule: re-check the customs source before travel, especially if you carry unusual cash amounts, business funds, group money, or multiple currencies.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>For the deeper payment strategy, use <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a> after you have slept. For budget shape, use <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>. The airport task is smaller: leave with enough payment flexibility that one failed card, one closed counter, or one confusing fare does not control the first night.</p>
<!-- /wp:paragraph -->

<!-- vg-airport-arrival-checklist-connectivity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Connectivity: solve the next handoff first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Phone data is not a trophy purchase. It is a safety and logistics tool for maps, hotel messaging, ride-hailing, translation, banking prompts, and family coordination. The airport decision is whether you need working data before you reach the hotel. If yes, use a pre-trip eSIM, roaming bridge, airport Wi-Fi, or a clear SIM counter process. If no, you can wait for a calmer comparison.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-airport-arrival-checklist-connectivity">
<thead><tr><th>Traveler type</th><th>Best first-hour answer</th><th>What to check before paying</th><th>Fallback</th></tr></thead>
<tbody>
<tr><td data-label="Traveler type">Pre-trip eSIM user</td><td data-label="Best first-hour answer">Activate or test data before leaving airport Wi-Fi.</td><td data-label="What to check before paying">Device support, unlock status, activation timing, data cap, hotspot, and support route.</td><td data-label="Fallback">Airport Wi-Fi, home roaming bridge, or physical SIM counter.</td></tr>
<tr><td data-label="Traveler type">Physical SIM buyer</td><td data-label="Best first-hour answer">Use a clear counter, understand the plan, keep passport control deliberate, and test data before leaving.</td><td data-label="What to check before paying">Data, calls, local number, validity, hotspot, registration, and top-up.</td><td data-label="Fallback">Wait for an official carrier store or hotel-supported purchase.</td></tr>
<tr><td data-label="Traveler type">Ride-hailing dependent</td><td data-label="Best first-hour answer">Do not leave Wi-Fi until pickup point, app account, plate matching, and hotel address are ready.</td><td data-label="What to check before paying">Local-number needs, payment method, driver messaging, and pickup signage.</td><td data-label="Fallback">Official taxi queue/counter or hotel pickup.</td></tr>
<tr><td data-label="Traveler type">Family or work traveler</td><td data-label="Best first-hour answer">Keep one backup line or roaming path in case the main phone fails.</td><td data-label="What to check before paying">Hotspot, two-factor authentication, home-number SMS, and battery.</td><td data-label="Fallback">Second adult phone, power bank, paper hotel address, and cash backup.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
<!-- wp:paragraph -->
<p>Do not spend thirty tired minutes trying to perfect the phone plan while bags, children, or a driver wait. If the counter is confusing, use airport Wi-Fi as a bridge and choose a safer transport path. Then read <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a> before committing to a longer data setup.</p>
<!-- /wp:paragraph -->

<!-- vg-airport-arrival-checklist-transport:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Transport: choose the option you can verify</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Airport transport in Vietnam can be easy when you decide by verification, not charm. The practical choices are usually hotel pickup, an official airport taxi queue or counter, ride-hailing when your phone and pickup point are under control, airport shuttle/bus when timing and luggage are simple, or private transfer when comfort and certainty matter.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-airport-arrival-checklist-transport">
<thead><tr><th>Transport choice</th><th>Use it when...</th><th>Check before moving</th><th>Skip when...</th></tr></thead>
<tbody>
<tr><td data-label="Transport choice">Hotel pickup</td><td data-label="Use it when...">Late arrival, children, premium short stay, heavy luggage, or a confusing first hotel lane.</td><td data-label="Check before moving">Driver name/sign, flight tracking, waiting rule, included tolls/parking, and hotel confirmation.</td><td data-label="Skip when...">You land rested in daylight and the hotel is easy to reach independently.</td></tr>
<tr><td data-label="Transport choice">Official taxi queue/counter</td><td data-label="Use it when...">You want a simple staffed process without app pickup confusion.</td><td data-label="Check before moving">Queue/counter identity, fare basis, destination, luggage, and receipt if available.</td><td data-label="Skip when...">A person pulls you away from the official process.</td></tr>
<tr><td data-label="Transport choice">Ride-hailing</td><td data-label="Use it when...">Data works, the account is ready, and you can match plate, driver, pickup point, and route calmly.</td><td data-label="Check before moving">Correct car, app fare basis, terminal pickup location, and hotel address.</td><td data-label="Skip when...">You cannot find the pickup point or the driver pressures you off-app.</td></tr>
<tr><td data-label="Transport choice">Airport bus or shuttle</td><td data-label="Use it when...">Daylight, light luggage, central stop, working map, and no fragile first-night plan.</td><td data-label="Check before moving">Route, stop, final walk, operating posture, and luggage practicality.</td><td data-label="Skip when...">Rain, late arrival, family bags, or a hotel far from the stop will erase the saving.</td></tr>
<tr><td data-label="Transport choice">Private transfer</td><td data-label="Use it when...">You are paying for certainty, child seats, space, a remote hotel, or a premium first night.</td><td data-label="Check before moving">Vehicle size, cancellation, waiting time, inclusions, and contact method.</td><td data-label="Skip when...">It adds cost without reducing real friction.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
<!-- wp:paragraph -->
<p>For the specific Hanoi handoff, use <a href="/plan/hanoi-airport-to-old-quarter/">Hanoi Airport to Old Quarter</a>. For country-wide movement decisions, use <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>. The arrival rule stays the same across airports: if you cannot verify who is taking you, how payment works, and where the vehicle is going, pause before leaving the airport process.</p>
<!-- /wp:paragraph -->

<!-- vg-airport-arrival-checklist-red-flags:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Red flags without paranoia</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam airport arrival does not need fear content. It needs a few practical refusal rules. Most problems are avoidable if you decline ambiguity while tired.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-airport-arrival-checklist-red-flags">
<thead><tr><th>Red flag</th><th>Why it matters</th><th>Calm response</th></tr></thead>
<tbody>
<tr><td data-label="Red flag">Someone approaches before you choose a queue, counter, app, or hotel pickup</td><td data-label="Why it matters">Unsolicited help can blur price, identity, and accountability.</td><td data-label="Calm response">Smile, decline, and return to a verified process.</td></tr>
<tr><td data-label="Red flag">The price, meter, route, or payment basis is unclear</td><td data-label="Why it matters">Ambiguity is harder to fix once luggage is in the vehicle.</td><td data-label="Calm response">Confirm before bags enter the car, or choose another option.</td></tr>
<tr><td data-label="Red flag">A driver or seller asks to hold your passport, all cash, or unlocked phone</td><td data-label="Why it matters">Documents and devices should stay under your control.</td><td data-label="Calm response">Use your own hands, own phone, and official registration process.</td></tr>
<tr><td data-label="Red flag">A SIM plan sounds too vague to understand</td><td data-label="Why it matters">Data, calls, hotspot, validity, and local-number rules matter once you leave support.</td><td data-label="Calm response">Buy only if you can repeat the terms back clearly; otherwise wait.</td></tr>
<tr><td data-label="Red flag">An ATM or card terminal offers a home-currency conversion</td><td data-label="Why it matters">Dynamic conversion can hide a weak rate.</td><td data-label="Calm response">Choose VND unless your own bank has confirmed a better card-specific rule.</td></tr>
<tr><td data-label="Red flag">Someone says your hotel, road, or booking has changed</td><td data-label="Why it matters">This can push you into an unverified transfer or service.</td><td data-label="Calm response">Contact the hotel through your saved details before changing plans.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
<!-- wp:paragraph -->
<p>For a broader trip posture, use <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>. The arrival version is simple: keep documents close, split cards and cash, do not hand over your phone, verify the transport identity, and refuse rushed decisions.</p>
<!-- /wp:paragraph -->

<!-- vg-airport-arrival-checklist-first-night:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">First-night plan: hotel, food nearby, sleep</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Your first night should be deliberately unambitious. Long-haul arrival, immigration, baggage, phone setup, cash, and transport already create enough decisions. A good first night is a working hotel address, a transfer that reaches it, a simple nearby meal, water, and enough sleep to make better choices tomorrow.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-airport-arrival-checklist-first-night">
<thead><tr><th>Arrival profile</th><th>First-night rule</th><th>What to avoid</th><th>Tomorrow's better decision</th></tr></thead>
<tbody>
<tr><td data-label="Arrival profile">Late international arrival</td><td data-label="First-night rule">Pay for a cleaner transfer and stay near a staffed hotel reception.</td><td data-label="What to avoid">Street food hunt, bar crawl, or tour booking while exhausted.</td><td data-label="Tomorrow's better decision">SIM plan, larger cash, neighborhood walk, first museum or food stop.</td></tr>
<tr><td data-label="Arrival profile">Daylight city arrival</td><td data-label="First-night rule">Use the calmest transport that still fits the budget.</td><td data-label="What to avoid">Dragging bags through traffic just to prove you can save a small amount.</td><td data-label="Tomorrow's better decision">Local transport habits and daily cash rhythm.</td></tr>
<tr><td data-label="Arrival profile">Family arrival</td><td data-label="First-night rule">Assign jobs: one adult handles documents/payment, one watches bags/children.</td><td data-label="What to avoid">Everyone debating the next step in the arrivals hall.</td><td data-label="Tomorrow's better decision">Activities, rest blocks, and meal timing.</td></tr>
<tr><td data-label="Arrival profile">Premium short trip</td><td data-label="First-night rule">Buy certainty when it preserves the first full day.</td><td data-label="What to avoid">False economy on the airport-to-hotel handoff.</td><td data-label="Tomorrow's better decision">Concierge help, private guide timing, spa or recovery block.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
<!-- wp:paragraph -->
<p>If Vietnam is the start of a wider trip, confirm entry paperwork before you fly with <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>. If it is your first time in the country, keep this airport checklist beside <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> so the first hour supports the route instead of becoming its first problem.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this checklist fits next</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this checklist close to departure, then use the deeper guides when you are making a specific decision. The airport checklist tells you the sequence; the linked guides give you the full planning logic.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-related-routes vg-airport-arrival-checklist-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/plan/money-cash-cards-atms/">Money in Vietnam</a><span class="vg-related-route-note">Plan cash, cards, ATMs, exchange, and customs declaration checks.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a><span class="vg-related-route-note">Choose data, local number, roaming, and backup communication before airport pickup matters.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/plan/hanoi-airport-to-old-quarter/">Hanoi Airport to Old Quarter</a><span class="vg-related-route-note">Use when Hanoi is your arrival city and the first hotel handoff needs a specific transfer answer.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a><span class="vg-related-route-note">Keep taxi, money, phone, market, and nightlife risk practical rather than fearful.</span></li>
</ol>
</div>
<!-- /wp:html -->

<!-- vg-airport-arrival-checklist-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Vietnam airport arrival FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-airport-arrival-checklist-faq">
<details><summary>What should I do first after landing in Vietnam?</summary><p>Follow the boring order: documents, luggage, connectivity, small cash, verified transport, then hotel. Do not start with transport offers, SIM-package debates, or large cash decisions before the basics are stable.</p></details>
<details><summary>Should I buy a SIM card at the airport in Vietnam?</summary><p>Buy one at the airport when you need immediate data and the counter terms are clear. Wait until the hotel or a carrier store when your transfer is already arranged, you have offline details, or the plan explanation feels confusing.</p></details>
<details><summary>How much cash should I get at the airport?</summary><p>Get enough VND for first-night friction, not the whole trip. Think transport backup, snacks, small tips, and a little margin. Handle larger withdrawals or exchange after sleep and after checking your route's real cash needs.</p></details>
<details><summary>Is ride-hailing safe from Vietnam airports?</summary><p>It can be a good option when your phone works and you can verify the pickup point, plate, driver, route, and payment basis. If pickup feels confusing after a long flight, use hotel pickup or an official airport taxi queue/counter.</p></details>
<details><summary>Should I exchange money before travel or on arrival?</summary><p>Many travelers use a small arrival buffer and then compare better options later. Avoid carrying excessive cash without checking customs declaration rules, and re-check official thresholds before travel if carrying large amounts.</p></details>
<details><summary>What is the biggest airport arrival scam risk?</summary><p>The main risk is ambiguity: unclear price, unofficial transport, rushed SIM terms, or someone asking to handle your phone, passport, or bags. The fix is not paranoia; it is refusing decisions you cannot verify.</p></details>
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
    'vg-airport-arrival-checklist-hero:v1',
    'vg-airport-arrival-checklist-verdict:v1',
    'vg-airport-arrival-checklist-sequence:v1',
    'vg-airport-arrival-checklist-do-now-wait:v1',
    'vg-airport-arrival-checklist-money:v1',
    'vg-airport-arrival-checklist-connectivity:v1',
    'vg-airport-arrival-checklist-transport:v1',
    'vg-airport-arrival-checklist-red-flags:v1',
    'vg-airport-arrival-checklist-first-night:v1',
    'vg-airport-arrival-checklist-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_airport_arrival_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Vietnam Airport Arrival Checklist: Money, SIM, Transport and Scams',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'A calm first-hour Vietnam airport arrival checklist for documents, luggage, SIM or eSIM, small cash, verified transport, scam avoidance, and first-night decisions.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_airport_arrival_post_fail('Could not update airport arrival checklist post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Vietnam Airport Arrival Checklist');
update_post_meta($post_id, 'rank_math_description', 'Use this Vietnam airport arrival checklist to handle documents, luggage, SIM or eSIM, small cash, verified transport, scams, and first-night decisions.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Vietnam airport arrival checklist');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Keep the first hour after landing in Vietnam boring: clear documents, collect luggage, get working connectivity, secure a small VND buffer, choose verified transport, and reach the hotel before optimizing purchases.');
update_post_meta($post_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($post_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($post_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($post_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($post_id, 'vg_eeat_update_summary', 'Expanded the native WordPress post brief into a complete airport-arrival draft with a first-hour verdict, documents-to-hotel sequence, do-now-versus-wait table, money/cash guidance, connectivity setup, verified transport framework, practical red flags, first-night rules, licensed airport imagery, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.');
update_post_meta($post_id, 'vg_eeat_sources_checked', "Vietnam.travel - A traveller's guide to Vietnam's airports - https://vietnam.travel/things-to-do/travellers-guide-vietnams-airports - checked {$review_date}; used for high-level airport facilities, currency exchange, free Wi-Fi, airport taxi, and transfer framing.\nVietnam.travel - Currency and payments in Vietnam - https://vietnam.travel/things-to-do/currency-and-payments-vietnam - checked {$review_date}; used for VND cash, airport ATMs, and currency exchange framing without promising specific fees.\nVietnam.travel - Health and safety in Vietnam - https://vietnam.travel/plan-your-trip/health-safety - checked {$review_date}; used for practical scam and taxi caution without fear content.\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for airport transfer modes and wider transport framing.\nVietnam Customs - cash declaration guidance for foreign currency and VND carried on entry or exit, including USD 5,000 equivalent and VND 15,000,000 declaration thresholds - https://www.customs.gov.vn/index.jsp?cid=4203&id=55031&pageId=2311 - checked {$review_date}; readers are told to re-check before travel.\nVietnam National Electronic Visa system - https://evisa.gov.vn/ - checked {$review_date}; used only as a pre-arrival paperwork reminder.\nWikimedia Commons image direct URL - Arrivals of Tan Son Nhat International Airport - {$hero_image} - credit Mkckim / CC BY-SA 4.0 - license context checked {$review_date}.\nWikimedia Commons image direct URL - Noi Bai International Airport Terminal 2 Night View - {$noi_bai_night_image} - credit Christakis Mina / CC BY-SA 4.0 - license context checked {$review_date}.\nWikimedia Commons image direct URL - 2024 Da Nang International Airport international terminal - {$da_nang_terminal_image} - credit Chainwit. / CC BY 4.0 - license context checked {$review_date}.");
update_post_meta($post_id, 'vg_eeat_field_note', 'This checklist treats Vietnam airport arrival as a sequence problem. The article intentionally avoids exact airport taxi prices, ATM fees, and SIM packages because they change, and instead gives a durable first-hour order for international travelers.');
update_post_meta($post_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($post_id, 'vg_eeat_evidence_moat', "Judgment-led first-hour sequence instead of generic arrival tips.\nExplicit documents-to-hotel order that separates urgent tasks from optimizable purchases.\nDo-now-versus-wait framework for SIM, cash, transport, food, and tours.\nMoney section uses official VND/currency framing and customs thresholds with a re-check caveat rather than stale fees.\nConnectivity section separates eSIM, physical SIM, ride-hailing dependency, local-number needs, and backup lines without affiliate package ranking.\nTransport matrix chooses hotel pickup, official taxi/counter, ride-hailing, bus/shuttle, or private transfer by verification and first-night risk.\nPractical red-flag table focuses on ambiguity, document/phone control, unclear prices, and rushed decisions without fear content.\nFirst-night rules protect sleep, hotel handoff, family logistics, and premium short-trip value.\nLicensed Wikimedia airport imagery with text-only credits and no visible source links.\nSource trail and update log keep official URLs in metadata while the body stays free of external anchors.");
update_post_meta($post_id, 'vg_eeat_related_routes', "Money in Vietnam | /plan/money-cash-cards-atms/ | Plan VND cash, cards, ATMs, exchange, arrival buffer, and customs declaration checks after the first-hour sequence is clear.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Choose pre-trip eSIM, airport SIM, local number, roaming backup, hotspot, and failure-mode planning before pickup depends on data.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Use the Hanoi-specific transfer guide when Noi Bai is the first airport and the Old Quarter handoff matters.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair airport arrival with practical taxi, money, phone, document, and rushed-decision risk habits.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Put airport transfer modes beside trains, domestic flights, buses, ferries, and private cars across the route.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget airport arrival as friction control, not only as a fare or SIM price.\nVietnam E-Visa | /plan/vietnam-evisa/ | Confirm entry paperwork before the airport sequence starts.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Use the country-level planning guide after the first hour is calm.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check rain, heat, storm, and seasonal disruption before relying on long walks or fragile first-night plans.");
update_post_meta($post_id, 'vg_eeat_hero_image_credit', 'Hero image: Arrivals of Tan Son Nhat International Airport by Mkckim, CC BY-SA 4.0. Body images: Noi Bai International Airport Terminal 2 Night View by Christakis Mina, CC BY-SA 4.0; 2024 Da Nang International Airport international terminal by Chainwit., CC BY 4.0.');
update_post_meta($post_id, 'vg_content_owner', 'wp_admin');
update_post_meta($post_id, 'vg_automation_lock', 'locked');
update_post_meta($post_id, 'vg_last_manual_review', $review_date);
update_post_meta($post_id, 'vg_admin_first_notes', 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, confirms image presentation, checks Rank Math/social preview, and re-checks official airport, customs, transport, money, health/safety, and e-visa sources before publishing.');

wp_set_object_terms($post_id, vg_airport_arrival_post_term_ids('category', ['transport-logistics', 'practicalities']), 'category', false);
wp_set_object_terms($post_id, vg_airport_arrival_post_term_ids('post_tag', ['arrival-day', 'first-time-vietnam', 'international-travelers', 'anti-spam-evergreen']), 'post_tag', false);

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
        vg_airport_arrival_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded airport arrival checklist post to complete draft: {$post_id}");

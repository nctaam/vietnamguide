<?php
/**
 * Publish the "Vietnam Travel Budget by Route" cluster and update the Costs Hub.
 *
 * Targets:
 * - Child Page 1: /costs/ha-giang-loop-cost-budget/ (parent 10)
 * - Child Page 2: /costs/da-nang-hoi-an-budget/ (parent 10)
 * - Child Page 3: /costs/hanoi-ninh-binh-ha-long-budget/ (parent 10)
 * - Update Post 10: /costs/ (Costs Hub with Route Budget cluster)
 * - Update Post 22: /costs/vietnam-travel-cost/ (Pillar guide route shortcuts)
 *
 * Run via WP-CLI on production VPS:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-route-budget-hub.php --allow-root
 *
 * @package VietnamGuide
 */

if (! defined('ABSPATH')) {
    exit('Run via WP-CLI: wp eval-file ops/apply-route-budget-hub.php --allow-root' . PHP_EOL);
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

echo "=== Publishing 'Vietnam Travel Budget by Route' Content Cluster ===" . PHP_EOL;

$review_date = 'September 20, 2026';
$parent_id   = 10; // /costs/ hub page

// Ensure parent page exists
$parent_page = get_post($parent_id);
if (! $parent_page) {
    WP_CLI::error("Parent page ID {$parent_id} (/costs/) not found in database.");
}

/**
 * Upserts a page under a specific parent.
 */
function vg_upsert_route_budget_page(string $slug, string $title, int $parent_id, string $content, string $excerpt, array $meta = []): int
{
    $existing = get_posts([
        'post_type'      => 'page',
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'name'           => $slug,
        'post_parent'    => $parent_id,
        'posts_per_page' => 1,
    ]);

    $post_args = [
        'post_type'      => 'page',
        'post_title'     => $title,
        'post_name'      => $slug,
        'post_parent'    => $parent_id,
        'post_status'    => 'publish',
        'post_content'   => $content,
        'post_excerpt'   => $excerpt,
        'comment_status' => 'closed',
        'ping_status'    => 'closed',
    ];

    if (! empty($existing)) {
        $post_id = (int) $existing[0]->ID;
        $post_args['ID'] = $post_id;
        $res = wp_update_post($post_args, true);
        if (is_wp_error($res)) {
            WP_CLI::error("Failed to update page {$slug}: " . $res->get_error_message());
        }
        echo "Updated existing page ID {$post_id}: /costs/{$slug}/" . PHP_EOL;
    } else {
        $res = wp_insert_post($post_args, true);
        if (is_wp_error($res)) {
            WP_CLI::error("Failed to insert page {$slug}: " . $res->get_error_message());
        }
        $post_id = (int) $res;
        echo "Created new page ID {$post_id}: /costs/{$slug}/" . PHP_EOL;
    }

    foreach ($meta as $key => $val) {
        update_post_meta($post_id, $key, $val);
    }

    update_post_meta($post_id, '_vg_schema_author_override', 'VietnamGuide editorial team');
    update_post_meta($post_id, 'vg_eeat_reviewed_guide', '1');
    update_post_meta($post_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
    update_post_meta($post_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
    update_post_meta($post_id, 'vg_eeat_last_meaningful_update', 'September 20, 2026');

    return $post_id;
}

// ==============================================================================
// 1. Page 1: Ha Giang Loop Cost & Budget (/costs/ha-giang-loop-cost-budget/)
// ==============================================================================
$p1_slug    = 'ha-giang-loop-cost-budget';
$p1_title   = 'Ha Giang Loop Cost & Budget: 4-Day Self-Drive vs Easy Rider Breakdown';
$p1_excerpt = 'Realistic 4-day Ha Giang Loop budget breakdown: compare self-drive vs easy rider costs, motorbike rental, fuel, permits, homestays, boat trips, and sleeper buses.';
$p1_img     = 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/46/Ma_Pi_Leng_Pass_winding_road_Ha_Giang_Vietnam.jpg/1280px-Ma_Pi_Leng_Pass_winding_road_Ha_Giang_Vietnam.jpg';

$p1_content = <<<HTML
<!-- vg-route-budget-hero:v1 -->
<section class="vg-guide-hero vg-route-budget-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Northern Mountain Road Budget &bull; Updated {$review_date}</p>
<h1>Ha Giang Loop Cost &amp; Budget: 4-Day Self-Drive vs Easy Rider Breakdown</h1>
<p class="vg-guide-lede">Motorcycle touring through the Dong Van Karst Plateau is one of Vietnam's most iconic journeys, but unexpected road fees, permits, and fuel stops catch unprepared riders off guard. Here is an honest, itemized financial model comparing independent self-driving against hiring an experienced local Easy Rider driver across 350 mountain kilometers.</p>
</div>
<figure class="vg-guide-hero-image">
<img src="{$p1_img}" alt="Winding asphalt mountain highway along the Ma Pi Leng Pass in Ha Giang province northern Vietnam" loading="eager" decoding="async" fetchpriority="high">
<figcaption>Ma Pi Leng Pass on Highway 4C. Road safety, bike condition, and fuel planning dictate your overall trip expense. Image: CC BY 2.0 Khanh Hmoong.</figcaption>
</figure>
</section>

<!-- vg-route-budget-verdict:v1 -->
<aside class="vg-concierge-verdict vg-route-budget-verdict" aria-label="Ha Giang Loop budget verdict">
<p class="vg-kicker">VietnamGuide budget verdict</p>
<h2>Budget $115&ndash;$145 USD (2.9M&ndash;3.7M VND) for self-drive, or $210&ndash;$270 USD (5.3M&ndash;6.9M VND) for an Easy Rider tour.</h2>
<p><strong>Self-driving saves money only if you possess a valid 1968 International Driving Permit (IDP) with motorcycle endorsement and substantial manual bike experience.</strong> Hiring an Easy Rider driver-guide doubles your outlay but eliminates police checkpoint fines, bike breakdown liability, mountain fatigue, and navigating treacherous gravel construction zones in heavy fog. Both options require carrying physical cash, as mountain ATMs in Yen Minh, Dong Van, and Du Gia frequently suffer network outages.</p>
</aside>

<!-- wp:shortcode -->
[vg_route_budget route="ha-giang" days="4" tier="midrange"]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<nav class="vg-guide-toc" aria-label="Table of contents">
<p class="vg-guide-toc-title">In this Ha Giang budget guide</p>
<ul>
<li><a href="#quick-comparison">1. Self-Drive vs Easy Rider Cost Matrix</a></li>
<li><a href="#line-item-breakdown">2. Itemized On-the-Ground Cost Breakdown</a></li>
<li><a href="#transit-costs">3. Getting There: Hanoi to Ha Giang Sleeper Buses</a></li>
<li><a href="#daily-spending">4. Day-by-Day Route Spending Model (4D3N)</a></li>
<li><a href="#hidden-costs">5. Permits, Checkpoints &amp; Hidden Road Costs</a></li>
<li><a href="#money-tips">6. Mountain Cash &amp; Payment Realities</a></li>
<li><a href="#faq">7. Frequently Asked Questions</a></li>
</ul>
</nav>

<div class="vg-guide-content">

<h2 id="quick-comparison">1. Self-Drive vs Easy Rider Cost Matrix</h2>
<p>A classic 4-day, 3-night circuit covers approximately 350 to 380 kilometers across Ha Giang City, Quan Ba, Yen Minh, Dong Van, Meo Vac, and Du Gia. Compare independent riding against an Easy Rider package:</p>

<table class="vg-decision-table">
<thead>
<tr>
<th>Expense Category</th>
<th>Self-Drive (Independent)</th>
<th>Easy Rider (Pillion Passenger)</th>
<th>Budget Notes</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Bike &amp; Driver Service</strong></td>
<td>600,000&ndash;800,000 VND ($24&ndash;$31 USD)<br><small>Semi-auto 110cc (150k&ndash;200k/day)</small></td>
<td>4,800,000&ndash;6,000,000 VND ($188&ndash;$235 USD)<br><small>Includes bike, driver, fuel, helmet, pads</small></td>
<td>For rougher mountain tracks in Mau Due, manual 150cc bikes cost 300,000&ndash;350,000 VND daily. Easy Rider pricing includes driver lodging and food.</td>
</tr>
<tr>
<td><strong>Fuel (Gasoline RON 95)</strong></td>
<td>220,000&ndash;280,000 VND ($9&ndash;$11 USD)</td>
<td>Included in driver fee</td>
<td>Budget approximately 24,500 VND per liter. You will consume roughly 8 to 11 liters over four mountain riding days.</td>
</tr>
<tr>
<td><strong>Accommodation (3 Nights)</strong></td>
<td>300,000&ndash;750,000 VND ($12&ndash;$30 USD)<br><small>Dorm or private homestay room</small></td>
<td>450,000&ndash;900,000 VND ($18&ndash;$35 USD)<br><small>Private twin or double room</small></td>
<td>Du Gia and Dong Van dorm bunks cost 100,000&ndash;150,000 VND nightly. Solid pine private rooms range from 250,000 to 400,000 VND.</td>
</tr>
<tr>
<td><strong>Meals &amp; Family Dinners</strong></td>
<td>700,000&ndash;900,000 VND ($28&ndash;$35 USD)</td>
<td>Included or 600,000&ndash;800,000 VND ($24&ndash;$31 USD)</td>
<td>Evening homestay family dinners cost 100,000&ndash;140,000 VND per person. Morning pho costs 40,000&ndash;50,000 VND.</td>
</tr>
<tr>
<td><strong>Permits &amp; Attraction Entry</strong></td>
<td>350,000&ndash;450,000 VND ($14&ndash;$18 USD)</td>
<td>350,000&ndash;450,000 VND ($14&ndash;$18 USD)</td>
<td>Dong Van border permit (150,000 VND), Tu San boat trip (150,000 VND), Lung Cu tower (40,000 VND).</td>
</tr>
<tr>
<td><strong>Transit Hanoi &harr; Ha Giang</strong></td>
<td>600,000&ndash;900,000 VND ($24&ndash;$35 USD)</td>
<td>600,000&ndash;900,000 VND ($24&ndash;$35 USD)</td>
<td>VIP cabin sleeper bus costs 300,000&ndash;450,000 VND each way. Regular sleeper costs 250,000 VND.</td>
</tr>
<tr>
<td><strong>Total 4D3N Cost</strong></td>
<td><strong>2,770,000&ndash;4,080,000 VND<br>($108&ndash;$160 USD)</strong></td>
<td><strong>6,800,000&ndash;9,050,000 VND<br>($266&ndash;$355 USD)</strong></td>
<td>Easy Rider provides peace of mind, zero driving fatigue, and authentic local cultural commentary.</td>
</tr>
</tbody>
</table>

<h2 id="line-item-breakdown">2. Itemized On-the-Ground Cost Breakdown</h2>
<p>Understanding individual ticket and rental prices allows you to adjust your daily run rate accurately:</p>

<table class="vg-decision-table">
<thead>
<tr>
<th>Expense Item</th>
<th>Standard Price (VND)</th>
<th>USD Equivalent</th>
<th>Operational Guidance</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Honda Blade 110cc Rental</strong></td>
<td>150,000&ndash;180,000 VND / day</td>
<td>$6&ndash;$7 USD</td>
<td>Semi-automatic. Easiest for braking down steep 12% declines without overheating pads. Check tire treads and front brakes before signing.</td>
</tr>
<tr>
<td><strong>Honda Winner / XR150cc Rental</strong></td>
<td>300,000&ndash;400,000 VND / day</td>
<td>$12&ndash;$16 USD</td>
<td>Manual transmission. Better clearance for rocky detours and muddy trails in Meo Vac and Mau Due.</td>
</tr>
<tr>
<td><strong>Dong Van Karst Plateau Permit</strong></td>
<td>150,000 VND / person</td>
<td>$6 USD</td>
<td>Mandatory police entry permit for foreign passports within border areas. Obtainable at Ha Giang Immigration or through your hostel in Dong Van.</td>
</tr>
<tr>
<td><strong>Nho Que River Tu San Boat</strong></td>
<td>150,000 VND / person</td>
<td>$6 USD</td>
<td>Official motorized boat ticket through Tu San Canyon. Add 50,000 VND for the motorbike shuttle from QL4C down the switchback track to the boat pier.</td>
</tr>
<tr>
<td><strong>Lung Cu National Flag Tower</strong></td>
<td>40,000 VND / person</td>
<td>$1.60 USD</td>
<td>Electric tram up to the steps costs 30,000 VND extra if you prefer not to hike the lower concrete ramp.</td>
</tr>
<tr>
<td><strong>Vuong Palace (H'mong King)</strong></td>
<td>30,000 VND / person</td>
<td>$1.20 USD</td>
<td>Sa Phin valley heritage site. Parking fee for motorbikes is 10,000 VND.</td>
</tr>
<tr>
<td><strong>Phao Dai Dong Van French Fortress</strong></td>
<td>Free / 20,000 VND parking</td>
<td>$0.80 USD</td>
<td>Hike up behind Dong Van Old Quarter for sunrise or sunset panoramic views.</td>
</tr>
<tr>
<td><strong>Emergency Tire Puncture Repair</strong></td>
<td>30,000&ndash;80,000 VND</td>
<td>$1.20&ndash;$3.10 USD</td>
<td>Roadside mechanics (&quot;Sửa xe máy&quot;) operate in every mountain village along QL4C. Patching a tube takes 15 minutes.</td>
</tr>
</tbody>
</table>

<h2 id="transit-costs">3. Getting There: Hanoi to Ha Giang Sleeper Buses</h2>
<p>Ha Giang has no operating railway or commercial airport. All international travelers arrive via road transit from Hanoi (approximately 6 to 7 hours travel time across 300 km):</p>
<ul class="vg-check-list">
<li><strong>VIP Cabin Sleeper Bus (Recommended):</strong> 350,000&ndash;450,000 VND ($14&ndash;$18 USD) each way. Features enclosed privacy curtains, individual USB charging ports, reading lights, and flat reclining beds. Operators such as Bang Phan, Quang Nghi, and Truly Ha Giang depart from My Dinh Bus Station or Old Quarter hotels between 20:30 and 22:00, arriving in Ha Giang City at 03:30&ndash;04:30 AM (most hostels permit free sleep until 07:00 AM departure).</li>
<li><strong>Standard Sleeper Bus:</strong> 250,000&ndash;300,000 VND ($10&ndash;$12 USD) each way. Three rows of stacked double bunks without curtains. Adequate for budget travelers under 180 cm in height.</li>
<li><strong>Luxury Limousine Minibus:</strong> 350,000&ndash;400,000 VND ($14&ndash;$16 USD) each way. Daytime 9-seat DCar transit with plush leather captain chairs, departing Hanoi at 06:30 AM and arriving Ha Giang City by 13:00. Ideal for travelers avoiding overnight road travel.</li>
</ul>

<h2 id="daily-spending">4. Day-by-Day Route Spending Model (4D3N)</h2>
<p>Here is how on-the-ground funds flow over the standard 4-day loop:</p>
<ol class="vg-check-list">
<li><strong>Day 1 (Ha Giang City to Yen Minh via Quan Ba):</strong> 80 km. Petrol top-up (80,000 VND), Quan Ba Heaven Gate coffee (40,000 VND), lunch in Tam Son (50,000 VND), Yen Minh pine forest scenic stop (free), homestay bed + family dinner in Yen Minh (250,000 VND). <em>Daily run rate: ~420,000 VND ($16.50 USD).</em></li>
<li><strong>Day 2 (Yen Minh to Dong Van via Lung Cu):</strong> 95 km. Petrol (80,000 VND), Tham Ma Pass photo stop (free), Vuong Palace ticket (30,000 VND), lunch in Sa Phin (50,000 VND), Lung Cu Flag Tower entry &amp; tram (70,000 VND), border permit registration (150,000 VND), private homestay room &amp; hot pot dinner in Dong Van (400,000 VND). <em>Daily run rate: ~780,000 VND ($30.50 USD).</em></li>
<li><strong>Day 3 (Dong Van to Du Gia via Ma Pi Leng Pass &amp; Meo Vac):</strong> 110 km. Petrol (90,000 VND), Ma Pi Leng panoramic view stop (free), Nho Que boat ride + transfer (200,000 VND), lunch in Meo Vac (60,000 VND), Du Gia waterfall swim (free), Du Gia stilt homestay bed &amp; family feast with corn wine (280,000 VND). <em>Daily run rate: ~630,000 VND ($24.70 USD).</em></li>
<li><strong>Day 4 (Du Gia to Ha Giang City &amp; Hanoi Return):</strong> 75 km. Roadside breakfast banh mi (30,000 VND), final petrol fill (50,000 VND), bike wash &amp; return inspection (free to 30,000 VND), lunch in Ha Giang City (60,000 VND), evening VIP sleeper cabin return to Hanoi (400,000 VND). <em>Daily run rate: ~540,000 VND ($21.20 USD).</em></li>
</ol>

<h2 id="hidden-costs">5. Permits, Checkpoints &amp; Hidden Road Costs</h2>
<p>Budgeting for the loop requires accounting for legal realities and unexpected mountain incidents:</p>
<ul>
<li><strong>Traffic Police Checkpoints:</strong> Traffic police frequently check licenses near Quan Ba and Yen Minh. Under Vietnamese law, riding without an IDP from a 1968 Convention country (or without a verified Vietnamese driving license) carries administrative fines ranging from 1,000,000 to 2,000,000 VND, plus potential bike impoundment for up to 7 days. If you lack proper licensing, book an Easy Rider to avoid legal risk and voided insurance coverage.</li>
<li><strong>Accident Damage Deposit:</strong> Rental agencies hold your original passport or a cash deposit of 2,000,000 to 5,000,000 VND ($80&ndash;$200 USD). Inspect existing scratches, brake levers, and mirror mounts thoroughly; photograph the bike before leaving the garage. Typical minor drop repair costs: broken clutch/brake lever (100,000 VND), cracked plastic fairing (250,000&ndash;450,000 VND).</li>
<li><strong>Severe Weather Detours:</strong> During heavy monsoon downpours between July and August, rockslides can block sections of QL4C between Yen Minh and Meo Vac. Keep a cash reserve of 500,000 VND for unexpected extra night stays or van rerouting.</li>
</ul>

<h2 id="money-tips">6. Mountain Cash &amp; Payment Realities</h2>
<p>Card acceptance on the loop is virtually zero outside large hotels in Ha Giang City and a handful of cafes in Dong Van Old Quarter. Follow these banking rules:</p>
<ul class="vg-check-list">
<li><strong>Withdraw full cash in Hanoi or Ha Giang City:</strong> Agribank, BIDV, and Vietcombank ATMs in Ha Giang City reliably accept international Visa/Mastercard cards. Withdraw at least 3,500,000 to 5,000,000 VND in cash per person before departing on the bike.</li>
<li><strong>Denomination Strategy:</strong> Request smaller banknotes (50,000 VND, 100,000 VND, 200,000 VND). Mountain fuel shacks and roadside noodle vendors cannot break 500,000 VND notes.</li>
<li><strong>Mobile Banking / QR Code Transfers:</strong> Many homestays accept VietQR transfers through Vietnamese banking apps, but foreign cards cannot connect to VietQR. Carry physical Dong.</li>
</ul>

<h2 id="faq">7. Frequently Asked Questions</h2>
<div class="vg-faq-accordion">
<h3>Is it cheaper to book an Easy Rider in Hanoi or Ha Giang?</h3>
<p>Booking directly in Ha Giang City or online through reputable local cooperatives (such as Jasmine, Road Kings, or Cheers) is typically 15% to 25% cheaper than booking through third-party tour desks in Hanoi's Old Quarter, who tack on hefty agency commissions.</p>

<h3>Do I need travel insurance that specifically covers motorcycles?</h3>
<p>Yes. Standard travel insurance policies explicitly exclude motorcycle accidents if you ride above 50cc without a valid motorcycle license recognized in Vietnam. Verify that your insurer covers medical evacuation up to at least $100,000 USD, as the nearest international-standard hospitals are located in Hanoi, 7 hours away.</p>

<h3>Can two people share one motorcycle to cut costs?</h3>
<p>Riding two-up (rider plus passenger with luggage) on a 110cc semi-automatic bike on steep 14% mountain switchbacks is strongly discouraged. It strains brakes, increases tipping risk, and makes hill climbing dangerously sluggish. If riding as a couple, either rent two bikes or hire two Easy Riders.</p>
</div>

<p>For more regional road and budgeting advice, consult our <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> pillar guide, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/plan/money-cash-cards-atms/">Money in Vietnam: Cash, Cards and ATMs</a>.</p>

</div>
HTML;

$p1_meta = [
    'rank_math_title'         => 'Ha Giang Loop Cost & Budget: 4-Day Self-Drive vs Easy Rider Breakdown',
    'rank_math_description'   => 'Realistic 4-day Ha Giang Loop budget breakdown: compare self-drive vs easy rider costs, motorbike rental, fuel, permits, homestays, boat trips, and sleeper buses.',
    'rank_math_focus_keyword' => 'Ha Giang Loop cost',
    'vg_eeat_primary_decision'=> 'Compare realistic 4-day self-drive versus easy rider budget, permits, homestays, fuel, and sleeper bus costs on the Ha Giang Loop.',
    'vg_eeat_field_note'      => 'Ha Giang Loop expenses are dictated by transport choices and licensing risk. Self-driving saves money only for experienced riders with valid 1968 IDP permits; Easy Rider packages provide comprehensive safety and cultural value.',
];

vg_upsert_route_budget_page($p1_slug, $p1_title, $parent_id, $p1_content, $p1_excerpt, $p1_meta);

// ==============================================================================
// 2. Page 2: Da Nang & Hoi An Travel Budget (/costs/da-nang-hoi-an-budget/)
// ==============================================================================
$p2_slug    = 'da-nang-hoi-an-budget';
$p2_title   = 'Da Nang & Hoi An Travel Budget: 5-Day Realistic Spending Guide';
$p2_excerpt = 'Realistic 5-day Da Nang and Hoi An travel budget: compare backpacker, mid-range, and luxury spending across boutique hotels, Hai Van transfers, tickets, and dining.';
$p2_img     = 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg';

$p2_content = <<<HTML
<!-- vg-route-budget-hero:v1 -->
<section class="vg-guide-hero vg-route-budget-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Central Coast Budget &bull; Updated {$review_date}</p>
<h1>Da Nang &amp; Hoi An Travel Budget: 5-Day Realistic Spending Guide</h1>
<p class="vg-guide-lede">Central Vietnam pairs sandy coastlines with atmospheric lantern-lit streets, offering some of the country's most compelling travel value. Here is an honest, line-item spending framework for a 5-day trip balancing beach resorts, ancient town heritage, seafood feasts, and coastal passes across three comfort tiers.</p>
</div>
<figure class="vg-guide-hero-image">
<img src="{$p2_img}" alt="Evening street lanterns lighting up traditional wooden shopfronts in Hoi An Ancient Town central Vietnam" loading="eager" decoding="async" fetchpriority="high">
<figcaption>Hoi An Ancient Town lantern district. Balancing beach boutique resorts with old town dining keeps central coast spending predictable. Image: CC BY-SA 4.0 Steffen Schmitz.</figcaption>
</figure>
</section>

<!-- vg-route-budget-verdict:v1 -->
<aside class="vg-concierge-verdict vg-route-budget-verdict" aria-label="Da Nang and Hoi An budget verdict">
<p class="vg-kicker">VietnamGuide budget verdict</p>
<h2>Plan $150&ndash;$200 USD for backpackers, $350&ndash;$480 USD for mid-range travelers, or $850+ USD for luxury resort stays across 5 days.</h2>
<p><strong>Da Nang and Hoi An offer Vietnam's highest hospitality flexibility per dollar spent.</strong> Because Da Nang International Airport (DAD) sits only 35 minutes from Hoi An, transit costs remain low compared to northern mountain transfers. Splitting your stay between a My Khe beachfront hotel in Da Nang and a riverside boutique villa in Hoi An provides the ideal balance between modern urban amenities and peaceful heritage walking.</p>
</aside>

<!-- wp:shortcode -->
[vg_route_budget route="da-nang-hoi-an" days="5" tier="midrange"]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<nav class="vg-guide-toc" aria-label="Table of contents">
<p class="vg-guide-toc-title">In this Central Vietnam budget guide</p>
<ul>
<li><a href="#spending-tiers">1. 5-Day Spending Tiers at a Glance</a></li>
<li><a href="#accommodation-costs">2. Hotel Costs: Da Nang Beachfront vs Hoi An Old Town</a></li>
<li><a href="#transit-transfers">3. Local Transport &amp; Airport Transfers</a></li>
<li><a href="#attraction-tickets">4. Sightseeing Tickets &amp; Activity Pricing</a></li>
<li><a href="#dining-costs">5. Food &amp; Beverage Run Rates</a></li>
<li><a href="#sample-budget">6. Itemized 5-Day Mid-Range Cost Model</a></li>
<li><a href="#faq">7. Frequently Asked Questions</a></li>
</ul>
</nav>

<div class="vg-guide-content">

<h2 id="spending-tiers">1. 5-Day Spending Tiers at a Glance</h2>
<p>This 5-day / 4-night itinerary assumes 2 nights in Da Nang and 2 nights in Hoi An (or 4 consecutive nights based in Hoi An with day trips to Da Nang):</p>

<table class="vg-decision-table">
<thead>
<tr>
<th>Expense Component</th>
<th>Backpacker / Budget</th>
<th>Mid-Range / Boutique</th>
<th>Luxury Heritage</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Accommodation (4 Nights)</strong></td>
<td>800,000&ndash;1,200,000 VND ($31&ndash;$47 USD)<br><small>Hostel dorm / guesthouse</small></td>
<td>3,500,000&ndash;6,000,000 VND ($137&ndash;$235 USD)<br><small>3&ndash;4★ boutique hotel with pool</small></td>
<td>15,000,000&ndash;35,000,000+ VND ($588&ndash;$1,372+ USD)<br><small>5★ luxury beach resort villa</small></td>
</tr>
<tr>
<td><strong>Food &amp; Drinks (5 Days)</strong></td>
<td>750,000&ndash;1,100,000 VND ($29&ndash;$43 USD)<br><small>Street stalls &amp; local markets</small></td>
<td>2,000,000&ndash;3,200,000 VND ($78&ndash;$125 USD)<br><small>Cafes, riverside bistros, seafood</small></td>
<td>5,500,000&ndash;9,000,000 VND ($215&ndash;$353 USD)<br><small>Fine dining &amp; cocktail lounges</small></td>
</tr>
<tr>
<td><strong>Local Transport &amp; Airport</strong></td>
<td>300,000&ndash;500,000 VND ($12&ndash;$20 USD)<br><small>Bicycles, Grab bike, public bus</small></td>
<td>800,000&ndash;1,400,000 VND ($31&ndash;$55 USD)<br><small>Grab cars &amp; private transfers</small></td>
<td>2,500,000&ndash;4,500,000 VND ($98&ndash;$176 USD)<br><small>Private chauffeur / luxury SUV</small></td>
</tr>
<tr>
<td><strong>Activities &amp; Sightseeing</strong></td>
<td>250,000&ndash;450,000 VND ($10&ndash;$18 USD)<br><small>Hoi An pass, Marble Mountains</small></td>
<td>1,400,000&ndash;2,200,000 VND ($55&ndash;$86 USD)<br><small>Add Ba Na Hills or cooking class</small></td>
<td>3,000,000&ndash;5,500,000 VND ($118&ndash;$216 USD)<br><small>Private boat, Memories VIP show</small></td>
</tr>
<tr>
<td><strong>Total 5-Day Estimate</strong></td>
<td><strong>2,100,000&ndash;3,250,000 VND<br>($82&ndash;$128 USD / person)</strong></td>
<td><strong>7,700,000&ndash;12,800,000 VND<br>($301&ndash;$501 USD / person)</strong></td>
<td><strong>26,000,000&ndash;54,000,000+ VND<br>($1,019&ndash;$2,117+ USD / person)</strong></td>
</tr>
</tbody>
</table>

<h2 id="accommodation-costs">2. Hotel Costs: Da Nang Beachfront vs Hoi An Old Town</h2>
<p>Where you base yourself fundamentally shapes your daily run rate:</p>
<ul>
<li><strong>Da Nang Beachfront (My Khe / An Thuong):</strong> Budget traveler hostels cost 180,000&ndash;250,000 VND ($7&ndash;$10 USD)/night. Mid-range sea-view high-rises (e.g., Sala Danang Beach, Monarque) range from 900,000 to 1,600,000 VND ($35&ndash;$63 USD)/night with generous rooftop infinity pools and breakfast buffets included. Ultra-luxury properties on Son Tra Peninsula (such as InterContinental Danang Sun Peninsula) start at 10,000,000 VND ($390 USD)/night.</li>
<li><strong>Hoi An Ancient Town &amp; An Bang Beach:</strong> Atmospheric heritage courtyard hotels in Cam Pho or Minh An (e.g., Lasenta Boutique, Little Riverside) cost 1,100,000 to 1,800,000 VND ($43&ndash;$70 USD)/night. Quiet beachfront villas near An Bang cost 1,200,000 to 2,500,000 VND ($47&ndash;$98 USD)/night. Luxury heritage properties (Anantara Hoi An, Four Seasons Nam Hai) span 5,500,000 to 18,000,000 VND ($215&ndash;$705 USD)/night.</li>
</ul>

<h2 id="transit-transfers">3. Local Transport &amp; Airport Transfers</h2>
<p>Central Vietnam offers straightforward ground transport with minimal scam risk when booked through apps or licensed desks:</p>

<table class="vg-decision-table">
<thead>
<tr>
<th>Transit Route / Mode</th>
<th>Cost (VND)</th>
<th>USD Equivalent</th>
<th>Booking Notes</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>DAD Airport to My Khe Beach</strong></td>
<td>100,000&ndash;140,000 VND</td>
<td>$4&ndash;$5.50 USD</td>
<td>15 minutes via GrabCar. Airport exit toll (10,000&ndash;15,000 VND) is added to the app receipt.</td>
</tr>
<tr>
<td><strong>DAD Airport to Hoi An Hotel</strong></td>
<td>300,000&ndash;380,000 VND</td>
<td>$12&ndash;$15 USD</td>
<td>30 km, 45 minutes. Private car pre-booked through hotel or 12Go is often 50,000 VND cheaper than street Grab during rain.</td>
</tr>
<tr>
<td><strong>Hoi An to Da Nang (One Way)</strong></td>
<td>280,000&ndash;350,000 VND</td>
<td>$11&ndash;$14 USD</td>
<td>GrabCar 4-seater. GrabBike costs ~120,000 VND for solo travelers.</td>
</tr>
<tr>
<td><strong>Hai Van Pass Private Car Tour</strong></td>
<td>900,000&ndash;1,300,000 VND</td>
<td>$35&ndash;$51 USD</td>
<td>Half-day round trip from Da Nang to Lang Co beach over the pass summit with photo stops. Split across 2&ndash;3 passengers.</td>
</tr>
<tr>
<td><strong>Hoi An Bicycle Rental</strong></td>
<td>Free to 40,000 VND / day</td>
<td>$0&ndash;$1.60 USD</td>
<td>Most boutique hotels and homestays provide cruisers with baskets for free. Ideal for cycling through Cam Thanh rice fields to An Bang.</td>
</tr>
<tr>
<td><strong>Grab within Hoi An / Da Nang</strong></td>
<td>35,000&ndash;70,000 VND / ride</td>
<td>$1.40&ndash;$2.80 USD</td>
<td>Short hops within city limits. Easy, metered, and transparent.</td>
</tr>
</tbody>
</table>

<h2 id="attraction-tickets">4. Sightseeing Tickets &amp; Activity Pricing</h2>
<p>Sightseeing fees in Central Vietnam are fixed by government regulations and private resort operators:</p>
<ul class="vg-check-list">
<li><strong>Hoi An Ancient Town Heritage Pass:</strong> 120,000 VND ($4.70 USD) per person. Includes admission to 5 heritage sites across the old quarter (Japanese Covered Bridge, assembly halls such as Phuc Kien, communal houses, and traditional musical performances). The ticket remains valid for your entire stay.</li>
<li><strong>Marble Mountains (Ngu Hanh Son):</strong> 40,000 VND ($1.60 USD) entrance fee to Water Mountain (Thuy Son), plus 15,000 VND for the glass elevator each way. Am Phu Cave ticket is 20,000 VND ($0.80 USD).</li>
<li><strong>Ba Na Hills Sun World &amp; Golden Bridge:</strong> 900,000 VND ($35 USD) for standard adult cable car admission and access to the iconic giant stone hands bridge, French Village, and Fantasy Park. Lunch buffet combo adds 350,000 VND ($14 USD).</li>
<li><strong>My Son Sanctuary (UNESCO):</strong> 150,000 VND ($6 USD) entrance fee including electric shuttle tram within the archaeological reserve. Guided half-day group tours from Hoi An cost 250,000&ndash;350,000 VND ($10&ndash;$14 USD) including transport.</li>
<li><strong>Cam Thanh Coconut Forest Basket Boat:</strong> 150,000&ndash;200,000 VND ($6&ndash;$8 USD) per round basket boat (seats 2 persons) for a 45-minute spin through the nipa palm water channels.</li>
<li><strong>Hoi An Memories Show (Ky Uc Hoi An):</strong> 600,000 VND ($24 USD) for ECO seats; 750,000 VND for MID seats. Spectacular 500-performer outdoor stage production on Hen Island.</li>
</ul>

<h2 id="dining-costs">5. Food &amp; Beverage Run Rates</h2>
<p>Dining in Da Nang and Hoi An provides extraordinary variety at low cost:</p>
<ul>
<li><strong>Local Heritage Dishes:</strong> Mi Quang noodles (35,000&ndash;45,000 VND / $1.40&ndash;$1.80 USD), Hoi An Cao Lau pork noodles (35,000&ndash;50,000 VND), Banh Mi (25,000&ndash;35,000 VND at Madam Khanh the Banh Mi Queen or Banh Mi Phuong), Hoi An Chicken Rice (Com Ga, 45,000&ndash;60,000 VND).</li>
<li><strong>Fresh Coastal Seafood:</strong> Da Nang beachfront seafood restaurants along Vo Nguyen Giap (e.g., Be Man, Quan Be Nho). Steamed clams with lemongrass (90,000&ndash;130,000 VND/plate), grilled sea prawns (250,000&ndash;350,000 VND/half-kilo), morning glory with garlic (40,000 VND). A feast for two costs 500,000&ndash;750,000 VND ($20&ndash;$30 USD).</li>
<li><strong>Specialty Coffee &amp; Drinks:</strong> Vietnamese iced milk coffee (Ca phe sua da, 20,000&ndash;30,000 VND), Salt coffee (Ca phe muoi, 25,000&ndash;35,000 VND), Coconut coffee (40,000&ndash;55,000 VND at Cong Ca Phe), local Larue or Huda draft beer (15,000&ndash;25,000 VND).</li>
</ul>

<h2 id="sample-budget">6. Itemized 5-Day Mid-Range Cost Model</h2>
<p>Here is a realistic spending blueprint for a couple traveling in mid-range comfort ($750 USD total for two, or ~$375 USD per person):</p>
<ul class="vg-check-list">
<li><strong>Day 1 (Arrival Da Nang &amp; Beach Sunset):</strong> DAD Airport Grab (120,000 VND), 4★ My Khe beach hotel check-in (1,200,000 VND), seafood dinner at Be Man (550,000 VND), Dragon Bridge weekend fire show stroll (free). <em>Total: ~1,870,000 VND ($73 USD).</em></li>
<li><strong>Day 2 (Marble Mountains &amp; Hai Van Pass Drive):</strong> Marble Mountains entry + lift (110,000 VND), morning coffee (60,000 VND), Hai Van Pass private driver excursion (950,000 VND), seafood lunch in Lang Co (400,000 VND), hotel stay (1,200,000 VND). <em>Total: ~2,720,000 VND ($107 USD).</em></li>
<li><strong>Day 3 (Transfer to Hoi An &amp; Ancient Town Walking):</strong> Private car transfer to Hoi An boutique hotel (320,000 VND), 4★ boutique resort stay (1,400,000 VND), Cao Lau &amp; Banh Mi lunch (140,000 VND), Ancient Town heritage passes for two (240,000 VND), riverside dinner at Morning Glory (450,000 VND). <em>Total: ~2,550,000 VND ($100 USD).</em></li>
<li><strong>Day 4 (An Bang Beach &amp; Sunset Lantern Boat):</strong> Hotel bicycles to An Bang (free), sun lounger &amp; fresh coconut (80,000 VND), beachside lunch at Soul Kitchen (380,000 VND), evening Thu Bon river lantern wooden boat ride (150,000 VND), night market street snacks (150,000 VND), hotel stay (1,400,000 VND). <em>Total: ~2,160,000 VND ($85 USD).</em></li>
<li><strong>Day 5 (Cooking Class / Souvenirs &amp; Departure):</strong> Half-day organic garden cooking class for two (800,000 VND), souvenir coffee &amp; lantern shopping (350,000 VND), afternoon Grab to DAD Airport (320,000 VND). <em>Total: ~1,470,000 VND ($58 USD).</em></li>
</ul>

<h2 id="faq">7. Frequently Asked Questions</h2>
<div class="vg-faq-accordion">
<h3>Is it cheaper to stay in Da Nang or Hoi An?</h3>
<p>Da Nang offers cheaper modern high-rise hotel rooms and lower seafood prices compared to Hoi An's tourist core. However, Hoi An delivers a walkable heritage village setting with free bicycle transport, reducing local taxi spending. Splitting 2 nights in each destination provides the optimal balance.</p>

<h3>Do restaurants in Hoi An add service charge and VAT?</h3>
<p>Upscale riverside restaurants and hotel bistros frequently add 5% service charge and 8% to 10% government VAT to your final bill. Local street stalls and market vendors charge net prices with zero tax added. Check whether menu prices specify &quot;prices subject to 10% VAT and 5% service charge&quot; (often denoted as &quot;++&quot;).</p>

<h3>Are credit cards widely accepted in Da Nang and Hoi An?</h3>
<p>Credit cards (Visa and Mastercard) are accepted across 90% of hotels, boutique cafes, spas, and supermarkets in both cities, usually with zero surcharge. Street food vendors, market stalls, and local boatmen require cash in Vietnamese Dong.</p>
</div>

<p>For additional central coast planning, review our <a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a>, <a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>.</p>

</div>
HTML;

$p2_meta = [
    'rank_math_title'         => 'Da Nang & Hoi An Travel Budget: 5-Day Realistic Spending Guide',
    'rank_math_description'   => 'Realistic 5-day Da Nang and Hoi An travel budget: compare backpacker, mid-range, and luxury spending across boutique hotels, Hai Van transfers, tickets, and dining.',
    'rank_math_focus_keyword' => 'Da Nang Hoi An budget',
    'vg_eeat_primary_decision'=> 'Plan realistic 5-day spending across Da Nang and Hoi An covering boutique beachfront hotels, heritage passes, airport transfers, and seafood dining.',
    'vg_eeat_field_note'      => 'Da Nang and Hoi An offer Vietnam\'s highest hospitality value per dollar. Low airport transfer friction and walkable heritage centers keep daily outlays predictable across all budget tiers.',
];

vg_upsert_route_budget_page($p2_slug, $p2_title, $parent_id, $p2_content, $p2_excerpt, $p2_meta);

// ==============================================================================
// 3. Page 3: Hanoi, Ninh Binh & Ha Long Bay Budget (/costs/hanoi-ninh-binh-ha-long-budget/)
// ==============================================================================
$p3_slug    = 'hanoi-ninh-binh-ha-long-budget';
$p3_title   = 'Hanoi, Ninh Binh & Ha Long Bay Budget: 4-Day Northern Highlights Breakdown';
$p3_excerpt = 'Itemized 4-day northern highlights travel budget: compare day cruises vs 2D1N boutique overnight cruises, limousine transfers, Trang An boats, and boutique stays.';
$p3_img     = 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg/1920px-Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg';

$p3_content = <<<HTML
<!-- vg-route-budget-hero:v1 -->
<section class="vg-guide-hero vg-route-budget-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Northern Highlights Budget &bull; Updated {$review_date}</p>
<h1>Hanoi, Ninh Binh &amp; Ha Long Bay Budget: 4-Day Northern Highlights Breakdown</h1>
<p class="vg-guide-lede">The Northern Golden Triangle connects Hanoi's historic Old Quarter with the limestone river karsts of Ninh Binh and the emerald waters of Ha Long Bay. Because cruise options range from basic $50 day boats to $500 luxury balcony cabins, structuring your transport and tour choices prevents massive budget inflation.</p>
</div>
<figure class="vg-guide-hero-image">
<img src="{$p3_img}" alt="Aerial panorama of emerald waters and limestone karst islands in Ha Long Bay northern Vietnam" loading="eager" decoding="async" fetchpriority="high">
<figcaption>Ha Long Bay karst seascape. Your choice between a day cruise and an overnight vessel is the single biggest northern budget decision. Image: CC BY 4.0 Vyacheslav Argenberg.</figcaption>
</figure>
</section>

<!-- vg-route-budget-verdict:v1 -->
<aside class="vg-concierge-verdict vg-route-budget-verdict" aria-label="Northern highlights budget verdict">
<p class="vg-kicker">VietnamGuide budget verdict</p>
<h2>Budget $180&ndash;$250 USD for a Day Cruise + Eco-Lodge circuit, or $340&ndash;$520 USD for a 2D1N Boutique Overnight Cruise package.</h2>
<p><strong>The cruise cabin represents 50% to 65% of your total northern itinerary cost.</strong> Opting for an overnight cruise in Lan Ha Bay or Bai Tu Long Bay provides an unhurried, serene experience with sunset kayaking and private balconies, but budget-conscious travelers can substitute a high-speed 6-hour luxury day cruise from Tuan Chau Port combined with an overnight stay at a tranquil Ninh Binh eco-lodge to save $150 to $200 USD per person with identical geological scenery.</p>
</aside>

<!-- wp:shortcode -->
[vg_route_budget route="hanoi-ninh-binh-halong" days="4" tier="midrange"]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<nav class="vg-guide-toc" aria-label="Table of contents">
<p class="vg-guide-toc-title">In this Northern Vietnam budget guide</p>
<ul>
<li><a href="#cruise-comparison">1. Day Cruise vs Overnight Cruise Financial Model</a></li>
<li><a href="#itemized-costs">2. Itemized Northern Highlights Cost Table</a></li>
<li><a href="#limousine-transit">3. Limousine Vans &amp; Expressway Transfers</a></li>
<li><a href="#ninh-binh-tickets">4. Ninh Binh Excursion Pricing (Trang An vs Tam Coc)</a></li>
<li><a href="#daily-spending">5. 4-Day Route Spending Blueprint</a></li>
<li><a href="#mistakes-to-avoid">6. Common Northern Budget Mistakes</a></li>
<li><a href="#faq">7. Frequently Asked Questions</a></li>
</ul>
</nav>

<div class="vg-guide-content">

<h2 id="cruise-comparison">1. Day Cruise vs Overnight Cruise Financial Model</h2>
<p>Comparing the two primary ways to experience northern Vietnam's marine karst landscapes:</p>

<table class="vg-decision-table">
<thead>
<tr>
<th>Cost Category</th>
<th>Option A: Day Cruise + Ninh Binh Lodge</th>
<th>Option B: 2D1N Boutique Overnight Cruise</th>
<th>Practical Planning Notes</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Cruise Experience</strong></td>
<td>1,200,000&ndash;1,600,000 VND ($47&ndash;$63 USD)<br><small>6-hour premier day cruise with buffet</small></td>
<td>3,800,000&ndash;6,500,000 VND ($149&ndash;$255 USD)<br><small>2D1N boutique cabin per person</small></td>
<td>Overnight cruise includes 4 full meals, private oceanview balcony, cooking demonstration, and dawn Tai Chi.</td>
</tr>
<tr>
<td><strong>Limousine Transfers</strong></td>
<td>900,000&ndash;1,100,000 VND ($35&ndash;$43 USD)<br><small>Hanoi &harr; Ninh Binh &amp; Hanoi &harr; Port</small></td>
<td>600,000&ndash;800,000 VND ($24&ndash;$31 USD)<br><small>Hanoi &harr; Cruise Port round trip</small></td>
<td>Limousine vans use the modern expressway (CT01 / CT04), taking 2 hours rather than 4 hours on old roads.</td>
</tr>
<tr>
<td><strong>Hotel Stays (3 Nights)</strong></td>
<td>1,800,000&ndash;2,600,000 VND ($71&ndash;$102 USD)<br><small>Hanoi (2n) + Ninh Binh eco-lodge (1n)</small></td>
<td>1,400,000&ndash;2,000,000 VND ($55&ndash;$78 USD)<br><small>Hanoi (2n) + Cruise cabin (1n covered above)</small></td>
<td>Ninh Binh eco-resorts (e.g., Tam Coc Garden, Emeralda) offer serene mountain pools at a fraction of cruise cabin rates.</td>
</tr>
<tr>
<td><strong>Excursion Tickets</strong></td>
<td>450,000&ndash;550,000 VND ($18&ndash;$22 USD)<br><small>Trang An boat + Hang Mua + Temple</small></td>
<td>Included in cruise package + 150,000 VND Hanoi</td>
<td>Trang An boat ticket is 250,000 VND; Hang Mua dragon viewpoint hike is 100,000 VND.</td>
</tr>
<tr>
<td><strong>Food &amp; Dining</strong></td>
<td>800,000&ndash;1,100,000 VND ($31&ndash;$43 USD)</td>
<td>500,000&ndash;700,000 VND ($20&ndash;$27 USD)</td>
<td>Overnight cruise meals are fully covered in cruise ticket.</td>
</tr>
<tr>
<td><strong>Total 4D3N Cost</strong></td>
<td><strong>5,150,000&ndash;6,950,000 VND<br>($202&ndash;$272 USD / person)</strong></td>
<td><strong>6,300,000&ndash;10,000,000 VND<br>($247&ndash;$392 USD / person)</strong></td>
<td>Day cruise saves money for independent travelers who prefer spending more nights on solid land in Ninh Binh.</td>
</tr>
</tbody>
</table>

<h2 id="itemized-costs">2. Itemized Northern Highlights Cost Table</h2>
<p>Reference these authoritative price benchmarks when budgeting your northern journey:</p>

<table class="vg-decision-table">
<thead>
<tr>
<th>Service / Attraction</th>
<th>Official Price (VND)</th>
<th>USD Equivalent</th>
<th>Booking Guidance</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Trang An Eco-Tourism Boat Tour</strong></td>
<td>250,000 VND / person</td>
<td>$9.80 USD</td>
<td>Route 2 (4 caves, 3 temples) or Route 3 (longest cave, Kong film set). 4 adults per rowboat, lasting ~3 hours. Tipping boatwoman 50,000&ndash;100,000 VND is customary.</td>
</tr>
<tr>
<td><strong>Tam Coc Sampan Boat Tour</strong></td>
<td>250,000 VND / 2 persons</td>
<td>$9.80 USD total</td>
<td>Rowed with feet through 3 river caves beneath karst arches. Slower, more pastoral than Trang An.</td>
</tr>
<tr>
<td><strong>Hang Mua Dragon Peak Viewpoint</strong></td>
<td>100,000 VND / person</td>
<td>$3.90 USD</td>
<td>500 stone steps climbing Ngoa Long mountain. Visit at 07:00 AM or 17:00 to avoid mid-day sun and crowds.</td>
</tr>
<tr>
<td><strong>Hoa Lu Ancient Capital Entry</strong></td>
<td>20,000 VND / person</td>
<td>$0.80 USD</td>
<td>10th-century historic temples of King Dinh and King Le. Modest attire covering shoulders and knees required.</td>
</tr>
<tr>
<td><strong>Bich Dong Pagoda</strong></td>
<td>Free admission</td>
<td>Free</td>
<td>Parking fee for bicycles/scooters is 10,000&ndash;20,000 VND. Ancient cave pagoda surrounded by lotus ponds.</td>
</tr>
<tr>
<td><strong>Express Bus 86 (Noi Bai &harr; Old Quarter)</strong></td>
<td>45,000 VND / person</td>
<td>$1.80 USD</td>
<td>Departs Terminal 1 &amp; 2 every 45 minutes directly to Hoan Kiem Lake. Cheaper than Grab (300,000 VND).</td>
</tr>
<tr>
<td><strong>Hanoi Old Quarter 3★ Boutique Hotel</strong></td>
<td>700,000&ndash;1,200,000 VND / night</td>
<td>$27&ndash;$47 USD</td>
<td>Clean, air-conditioned boutique properties with western bathrooms and breakfast on Hang Bac or Ma May.</td>
</tr>
<tr>
<td><strong>Ninh Binh Karst Eco-Lodge</strong></td>
<td>800,000&ndash;1,600,000 VND / night</td>
<td>$31&ndash;$63 USD</td>
<td>Bungalows overlooking rice fields and limestone bluffs in Tam Coc, Trang An, or Van Long.</td>
</tr>
</tbody>
</table>

<h2 id="limousine-transit">3. Limousine Vans &amp; Expressway Transfers</h2>
<p>Modern highway infrastructure makes Northern Vietnam transfers remarkably comfortable and swift when booked via luxury limousine vans (DCar 9-seat conversions):</p>
<ul class="vg-check-list">
<li><strong>Hanoi Old Quarter to Ninh Binh (Tam Coc / Trang An):</strong> 200,000&ndash;250,000 VND ($8&ndash;$10 USD) per seat each way. Door-to-door hotel pickup, taking approximately 1 hour 45 minutes via Highway CT01. Operators include Trang An Limousine, Duy Khang, and Binh Minh.</li>
<li><strong>Ninh Binh directly to Ha Long / Tuan Chau Port:</strong> 300,000&ndash;350,000 VND ($12&ndash;$14 USD) per seat each way. 3 hours travel time across Hai Phong via Highway CT04, completely bypassing the need to backtrack through Hanoi. This is the ultimate route hack for saving both time and money.</li>
<li><strong>Hanoi Old Quarter to Tuan Chau / Halong International Port:</strong> 250,000&ndash;300,000 VND ($10&ndash;$12 USD) each way. 2 hours via the modern Hanoi-Ha Long Expressway.</li>
</ul>

<h2 id="ninh-binh-tickets">4. Ninh Binh Excursion Pricing (Trang An vs Tam Coc)</h2>
<p>Choosing between Trang An and Tam Coc is a frequent traveler dilemma:</p>
<ul>
<li><strong>Trang An ($10 USD / 250,000 VND):</strong> Managed professionally as a UNESCO World Heritage site. Boat drivers are salaried and strictly forbidden from aggressively soliciting tips. The waterways are wider, the caves are longer and higher, and temples dot the route. Best choice for first-time visitors seeking majestic scale.</li>
<li><strong>Tam Coc ($10 USD / 250,000 VND for 2):</strong> Narrower rural waterway passing directly through golden rice paddies (stunning in May). Local boatwomen row using their feet. However, floating boat vendors inside the third cave can be persistent with snack sales, and boaters expect a 50,000 to 100,000 VND tip upon return.</li>
</ul>

<h2 id="daily-spending">5. 4-Day Route Spending Blueprint</h2>
<p>Here is an optimized itinerary combining Hanoi, Ninh Binh, and a 2D1N cruise ($385 USD per person based on double occupancy):</p>
<ol class="vg-check-list">
<li><strong>Day 1 (Hanoi Old Quarter Arrival &amp; Street Food):</strong> Airport Bus 86 (45,000 VND), Old Quarter 3★ boutique hotel check-in (900,000 VND / 2 = 450,000 VND), street food dinner with Bun cha and egg coffee (110,000 VND). <em>Total: ~605,000 VND ($24 USD).</em></li>
<li><strong>Day 2 (Limousine to Ninh Binh &amp; Countryside Boating):</strong> Morning limousine van to Tam Coc (220,000 VND), Trang An boat tour (250,000 VND), goat meat specialty lunch (140,000 VND), Hang Mua sunset climb (100,000 VND), Tam Coc eco-lodge bungalow (1,100,000 VND / 2 = 550,000 VND), dinner (120,000 VND). <em>Total: ~1,380,000 VND ($54 USD).</em></li>
<li><strong>Day 3 (Direct Transfer to Lan Ha Bay Overnight Cruise):</strong> Direct morning limousine from Ninh Binh to Got / Tuan Chau Port (320,000 VND), embark 4★ boutique cruise cabin (4,200,000 VND / 2 = 2,100,000 VND per person for 2D1N all-inclusive), buffet lunch, kayak excursions, 4-course seafood dinner on board. <em>Total: ~2,420,000 VND ($95 USD).</em></li>
<li><strong>Day 4 (Morning Bay Tai Chi, Return to Hanoi &amp; Departure):</strong> Dawn Tai Chi and cave excursion, brunch on cruise, disembarkation at 11:30 AM, express limousine back to Hanoi Old Quarter (280,000 VND), farewell coffee and souvenir shopping (150,000 VND), evening departure. <em>Total: ~430,000 VND ($17 USD).</em></li>
</ol>

<h2 id="mistakes-to-avoid">6. Common Northern Budget Mistakes</h2>
<ul class="vg-check-list">
<li><strong>Booking ultra-cheap $25 Ha Long Day Tours:</strong> Dirt-cheap day boat tours from Hanoi spend 8 hours in cramped minibuses on non-expressway backroads, stop at tourist factory shops for 40 minutes, and crowd 60 passengers onto aging wooden day boats with subpar food. Pay the extra $25 for a modern 6-hour luxury catamaran utilizing expressway transport.</li>
<li><strong>Backtracking through Hanoi:</strong> Booking separate round trips (Hanoi &harr; Ninh Binh, then Hanoi &harr; Ha Long) wastes 5 hours and 500,000 VND in redundant transit. Always book the direct Ninh Binh &rarr; Ha Long connecting limousine.</li>
<li><strong>Ignoring Harbor Tenders &amp; Kayak Fees:</strong> Some low-end cruise agencies advertise rock-bottom cabin prices but charge extra for kayak rental (150,000 VND/person) or drinks on board ($4 USD for beer, $8 USD for cocktails). Book verified all-inclusive boutique operators.</li>
</ul>

<h2 id="faq">7. Frequently Asked Questions</h2>
<div class="vg-faq-accordion">
<h3>Is 4 days enough for Hanoi, Ninh Binh, and Ha Long Bay?</h3>
<p>Yes, provided you sequence the loop efficiently without backtracking: Hanoi &rarr; Ninh Binh &rarr; Ha Long Bay &rarr; Hanoi. This minimizes highway transit time to just 2 hours between each leg.</p>

<h3>Should I visit Ha Long Bay or Lan Ha Bay?</h3>
<p>Lan Ha Bay (departing from Cat Ba / Hai Phong) shares the identical limestone topography as Ha Long Bay but experiences significantly lower tourist boat density and cleaner swimming waters. Cabin prices are comparable.</p>

<h3>What is the tipping culture on northern boat tours?</h3>
<p>Tipping is not legally mandatory in Vietnam, but on rowboats in Trang An and Tam Coc, boaters row non-stop for 2 to 3 hours for modest base wages. A tip of 50,000 to 100,000 VND ($2&ndash;$4 USD) per boat is deeply appreciated.</p>
</div>

<p>For more detailed itinerary sequencing, explore our <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>.</p>

</div>
HTML;

$p3_meta = [
    'rank_math_title'         => 'Hanoi, Ninh Binh & Ha Long Bay Budget: 4-Day Northern Highlights Breakdown',
    'rank_math_description'   => 'Itemized 4-day northern highlights travel budget: compare day cruises vs 2D1N boutique overnight cruises, limousine transfers, Trang An boats, and boutique stays.',
    'rank_math_focus_keyword' => 'Hanoi Ninh Binh Ha Long budget',
    'vg_eeat_primary_decision'=> 'Compare day cruise versus overnight cruise packages, limousine transfers, and eco-lodge pricing across Hanoi, Ninh Binh, and Ha Long Bay.',
    'vg_eeat_field_note'      => 'The cruise cabin represents over 50% of the northern golden triangle budget. Combining a premier day cruise with an overnight Ninh Binh countryside stay saves $150 to $200 USD per person.',
];

vg_upsert_route_budget_page($p3_slug, $p3_title, $parent_id, $p3_content, $p3_excerpt, $p3_meta);

// ==============================================================================
// 4. Update Post 10 (/costs/) - Costs Hub with "Travel Budget by Route" section
// ==============================================================================
echo "Updating Costs Hub (Post ID 10)..." . PHP_EOL;

$p10 = get_post(10);
if ($p10) {
    $p10_content = $p10->post_content;

    // Check if the route budget hub section is already present
    if (strpos($p10_content, 'vg-route-budget-hub-section') === false) {
        $route_hub_block = <<<HTML

<!-- wp:group {"className":"vg-hub-section vg-route-budget-hub-section"} -->
<div class="wp-block-group vg-hub-section vg-route-budget-hub-section">
<h2 class="wp-block-heading">Vietnam Travel Budget by Route</h2>
<p class="vg-hub-section-lede">National daily averages only tell half the story. Real spending is dictated by your geographical route: mountain motorcycle loops carry vastly different costs than coastal resort stays or island transfers. Explore our verified, route-specific cost breakdowns with live interactive budget estimators:</p>

<div class="wp-block-group vg-hub-grid vg-route-budget-grid">

<div class="wp-block-group vg-hub-card vg-route-card">
<span class="vg-badge">4 Days &bull; Far North</span>
<h3><a href="/costs/ha-giang-loop-cost-budget/">Ha Giang Loop Budget Guide</a></h3>
<p>Complete itemized breakdown for the 350km mountain circuit: compare independent self-drive (~$115&ndash;$145 USD) against licensed Easy Rider driver packages (~$210&ndash;$270 USD), homestays, border permits, boat tickets, and sleeper buses.</p>
<p class="vg-card-action"><a href="/costs/ha-giang-loop-cost-budget/" class="vg-btn vg-btn-sm">Inspect Ha Giang Route Costs &rarr;</a></p>
</div>

<div class="wp-block-group vg-hub-card vg-route-card">
<span class="vg-badge">5 Days &bull; Central Coast</span>
<h3><a href="/costs/da-nang-hoi-an-budget/">Da Nang &amp; Hoi An Budget Guide</a></h3>
<p>Spending models across 3 comfort tiers (Backpacker $150&ndash;$200 | Mid-Range $350&ndash;$480 | Luxury $850+) covering beachfront My Khe hotels, Hoi An Ancient Town heritage passes, Hai Van Pass transfers, and fresh seafood dining.</p>
<p class="vg-card-action"><a href="/costs/da-nang-hoi-an-budget/" class="vg-btn vg-btn-sm">Calculate Da Nang &amp; Hoi An Budget &rarr;</a></p>
</div>

<div class="wp-block-group vg-hub-card vg-route-card">
<span class="vg-badge">4 Days &bull; Northern Triangle</span>
<h3><a href="/costs/hanoi-ninh-binh-ha-long-budget/">Hanoi, Ninh Binh &amp; Ha Long Bay Budget</a></h3>
<p>Navigate northern Vietnam's biggest spending decision: 6-hour premier day cruise + Ninh Binh eco-lodge ($180&ndash;$250 USD) vs 2D1N boutique overnight cruise ($340&ndash;$520 USD), limousine vans, and Trang An boat tickets.</p>
<p class="vg-card-action"><a href="/costs/hanoi-ninh-binh-ha-long-budget/" class="vg-btn vg-btn-sm">Explore Northern Highlights Expenses &rarr;</a></p>
</div>

<div class="wp-block-group vg-hub-card vg-route-card vg-route-card-full">
<span class="vg-badge">Pillar Guide &bull; National</span>
<h3><a href="/costs/vietnam-travel-cost/">Full Vietnam Travel Cost &amp; Calculator</a></h3>
<p>Comprehensive national budgeting guide with dual-currency interactive calculator, daily expense benchmarks across 4 travel styles, 7/10/14/21-day total estimates, and live route preset switches.</p>
<p class="vg-card-action"><a href="/costs/vietnam-travel-cost/" class="vg-btn vg-btn-sm">Open Full Budget Calculator &rarr;</a></p>
</div>

</div>
</div>
<!-- /wp:group -->

HTML;

        // Insert right after the hero block if possible, or append
        $hero_pos = strpos($p10_content, '<!-- /wp:group -->');
        if ($hero_pos !== false) {
            $insert_idx = $hero_pos + strlen('<!-- /wp:group -->');
            $p10_content = substr($p10_content, 0, $insert_idx) . "\n" . $route_hub_block . "\n" . substr($p10_content, $insert_idx);
        } else {
            $p10_content .= $route_hub_block;
        }

        wp_update_post([
            'ID'           => 10,
            'post_content' => $p10_content,
        ]);
        echo "Successfully updated Post 10 (/costs/) with route budget cluster." . PHP_EOL;
    } else {
        echo "Post 10 already contains route budget cluster; skipping content insertion." . PHP_EOL;
    }
}

// ==============================================================================
// 5. Update Post 22 (/costs/vietnam-travel-cost/) - Pillar guide route preset links
// ==============================================================================
echo "Updating Pillar Guide (Post ID 22)..." . PHP_EOL;

$p22 = get_post(22);
if ($p22) {
    $p22_content = $p22->post_content;

    if (strpos($p22_content, 'vg-route-presets-callout') === false) {
        $presets_callout = <<<HTML

<!-- wp:group {"className":"vg-route-presets-callout","style":{"spacing":{"margin":{"top":"2rem","bottom":"2rem"}}}} -->
<div class="wp-block-group vg-route-presets-callout" style="background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #1a365d;border-radius:8px;padding:24px;margin-top:2rem;margin-bottom:2rem;">
<h3 style="margin-top:0;color:#1a365d;">🗺️ Plan by Real Route, Not Just National Averages</h3>
<p>Different regions carry distinct transport logistics and daily cost profiles. Jump directly to our itemized route cost guides or trigger calculator presets:</p>
<ul style="line-height:1.8;margin-bottom:12px;">
<li>🏍️ <strong><a href="/costs/ha-giang-loop-cost-budget/">Ha Giang Loop Budget Guide</a>:</strong> 4 Days / 3 Nights &bull; Self-Drive (~$115&ndash;$145) vs Easy Rider (~$210&ndash;$270). <a href="/costs/vietnam-travel-cost/?route=ha-giang"><em>[Load Ha Giang Preset in Calculator &rarr;]</em></a></li>
<li>🏮 <strong><a href="/costs/da-nang-hoi-an-budget/">Da Nang &amp; Hoi An Travel Budget</a>:</strong> 5 Days / 4 Nights &bull; Backpacker (~$150&ndash;$200) vs Mid-Range (~$350&ndash;$480). <a href="/costs/vietnam-travel-cost/?route=da-nang-hoi-an"><em>[Load Da Nang &amp; Hoi An Preset &rarr;]</em></a></li>
<li>⛵ <strong><a href="/costs/hanoi-ninh-binh-ha-long-budget/">Hanoi, Ninh Binh &amp; Ha Long Bay Budget</a>:</strong> 4 Days / 3 Nights &bull; Day Cruise (~$180&ndash;$250) vs Overnight Cruise (~$340&ndash;$520). <a href="/costs/vietnam-travel-cost/?route=hanoi-ninh-binh-halong"><em>[Load Northern Highlights Preset &rarr;]</em></a></li>
</ul>
</div>
<!-- /wp:group -->

HTML;

        // Insert before "Prebook, live-check, or stay flexible?" heading
        $target_heading = '<h2 class="wp-block-heading">Prebook, live-check, or stay flexible?</h2>';
        $heading_pos = strpos($p22_content, $target_heading);
        if ($heading_pos !== false) {
            $p22_content = substr($p22_content, 0, $heading_pos) . $presets_callout . "\n" . substr($p22_content, $heading_pos);
        } else {
            $p22_content .= "\n" . $presets_callout;
        }

        wp_update_post([
            'ID'           => 22,
            'post_content' => $p22_content,
        ]);
        echo "Successfully updated Post 22 (/costs/vietnam-travel-cost/) with route preset callout." . PHP_EOL;
    } else {
        echo "Post 22 already contains route preset callout; skipping insertion." . PHP_EOL;
    }
}

// ==============================================================================
// 6. Flush Rewrites & Final Confirmation
// ==============================================================================
flush_rewrite_rules(false);
echo "Flushed rewrite rules successfully." . PHP_EOL;
echo "=== Done publishing Vietnam Travel Budget by Route cluster! ===" . PHP_EOL;

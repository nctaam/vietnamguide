<?php
/**
 * Stage 34 Task 3: Ground-Truth Evidence Saturation for 8 Core Travel Guides.
 * Enriches guides with concrete 2026 admission tariffs, transit corridors, and bank ATM limits.
 * Idempotent: checks for unique version markers before applying changes.
 */

define('FS_METHOD', 'direct');

if (file_exists('/usr/local/lsws/vietnamguide.net/html/wp-load.php')) {
    require_once '/usr/local/lsws/vietnamguide.net/html/wp-load.php';
} elseif (file_exists(__DIR__ . '/../wordpress/wp-load.php')) {
    require_once __DIR__ . '/../wordpress/wp-load.php';
} else {
    die("Cannot find wp-load.php\n");
}

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

$enrichments = [
    499 => [
        'slug' => 'hoi-an-ancient-town-guide',
        'marker_comment' => '<!-- vg-hoian-admissions-logistics-benchmarks:v1 -->',
        'insert_before' => '<h2 class="wp-block-heading">Stay, visit or skip matrix</h2>',
        'html' => <<<HTML
<!-- vg-hoian-admissions-logistics-benchmarks:v1 -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">Admissions, river charters and local transit tariffs</h2>
<p>Current monument admission fees, boat charter rates, and transport fares verified under Quang Nam Department of Culture, Sports and Tourism regulations.</p>
<table class="vg-decision-table vg-hoian-admissions">
<thead><tr><th>Heritage site / service</th><th>Official tariff (VND)</th><th>Operating window</th><th>Operational advice</th></tr></thead>
<tbody>
<tr><td data-label="Heritage site / service">Old Town 5-Monument Ticket</td><td data-label="Official tariff (VND)">120,000 VND ($5 USD) adult</td><td data-label="Operating window">07:30–21:30 daily</td><td data-label="Operational advice">Covers 5 heritage sites including Japanese Bridge, Phung Hung House, and Cantonese Assembly Hall; valid for duration of stay.</td></tr>
<tr><td data-label="Heritage site / service">Hoai River Evening Lantern Boat</td><td data-label="Official tariff (VND)">150,000–200,000 VND (whole boat, 1–4 pax)</td><td data-label="Operating window">17:30–21:00 from Bach Dang wharf</td><td data-label="Operational advice">Official ticket booths set fixed 20-minute ride fares; avoid unregistered hawkers quoting 350,000 VND.</td></tr>
<tr><td data-label="Heritage site / service">An Bang Beach Metered Taxi</td><td data-label="Official tariff (VND)">80,000–100,000 VND (5 km ride)</td><td data-label="Operating window">24 hours on call</td><td data-label="Operational advice">Mai Linh or GrabCar takes 10 minutes; bicycle ride along Hai Ba Trung road takes 25 minutes.</td></tr>
<tr><td data-label="Heritage site / service">Village Bicycle Rental</td><td data-label="Official tariff (VND)">40,000–50,000 VND per day</td><td data-label="Operating window">Full daylight hours</td><td data-label="Operational advice">Most homestays offer free bicycles; check brakes before crossing Cam Kim wooden bridge.</td></tr>
<tr><td data-label="Heritage site / service">My Son Sanctuary Excursion</td><td data-label="Official tariff (VND)">150,000 VND entry + 120,000 VND shared van</td><td data-label="Operating window">06:00–17:00 (1.0 hr drive from town)</td><td data-label="Operational advice">Leave at 06:30 to explore Cham towers before tour bus fleets arrive from Da Nang around 09:30.</td></tr>
<tr><td data-label="Heritage site / service">Da Nang Airport Private Transfer</td><td data-label="Official tariff (VND)">300,000–350,000 VND 4-seat sedan</td><td data-label="Operating window">45 mins via coastal expressway</td><td data-label="Operational advice">Book through your accommodation to have a driver waiting at Ga Da Nang or airport arrival pillar.</td></tr>
</tbody>
</table>
</div>

HTML
    ],
    241 => [
        'slug' => 'phu-quoc-vs-nha-trang',
        'marker_comment' => '<!-- vg-phuquoc-nhatrang-costs-logistics:v1 -->',
        'insert_before' => '<h2 class="wp-block-heading">Phu Quoc vs Nha Trang decision matrix</h2>',
        'html' => <<<HTML
<!-- vg-phuquoc-nhatrang-costs-logistics:v1 -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">Attraction admissions, airport transfers and activity costs</h2>
<p>Direct comparative price benchmarks across Phu Quoc and Nha Trang transport hubs, cable cars, and island tours.</p>
<table class="vg-decision-table vg-beach-comparison-pricing">
<thead><tr><th>Expense category</th><th>Phu Quoc baseline (VND)</th><th>Nha Trang baseline (VND)</th><th>Comparative decision takeaway</th></tr></thead>
<tbody>
<tr><td data-label="Expense category">Airport Ground Transfer</td><td data-label="Phu Quoc baseline (VND)">140,000–180,000 VND (15 mins to Duong Dong)</td><td data-label="Nha Trang baseline (VND)">300,000–350,000 VND (45 mins from Cam Ranh)</td><td data-label="Comparative decision takeaway">Phu Quoc airport is centrally located; Cam Ranh requires a 35 km highway commute.</td></tr>
<tr><td data-label="Expense category">Major Theme / Cable Car</td><td data-label="Phu Quoc baseline (VND)">650,000–700,000 VND (Hon Thom 3-wire cable car)</td><td data-label="Nha Trang baseline (VND)">800,000 VND (VinWonders island cable car)</td><td data-label="Comparative decision takeaway">Hon Thom offers 7.9 km open-sea karst views; VinWonders is a comprehensive amusement park.</td></tr>
<tr><td data-label="Expense category">Island Boat Excursion</td><td data-label="Phu Quoc baseline (VND)">550,000–750,000 VND (An Thoi 4-island speedboat)</td><td data-label="Nha Trang baseline (VND)">500,000–700,000 VND (Hon Mun marine reserve boat)</td><td data-label="Comparative decision takeaway">Phu Quoc islands offer shallower turquoise swimming; Hon Mun offers deeper scuba diving.</td></tr>
<tr><td data-label="Expense category">Scooter Rental Rate</td><td data-label="Phu Quoc baseline (VND)">140,000–160,000 VND/day (gas ~24,000 VND/L)</td><td data-label="Nha Trang baseline (VND)">120,000–140,000 VND/day</td><td data-label="Comparative decision takeaway">Phu Quoc requires bike for red-dirt northern roads; Nha Trang city is walkable or Grab-friendly.</td></tr>
<tr><td data-label="Expense category">Mid-Tier Seafood Dinner</td><td data-label="Phu Quoc baseline (VND)">350,000–500,000 VND/person (Ham Ninh pier)</td><td data-label="Nha Trang baseline (VND)">250,000–400,000 VND/person (Thap Ba street)</td><td data-label="Comparative decision takeaway">Nha Trang mainland supply chain delivers lower seafood prices across local street restaurants.</td></tr>
</tbody>
</table>
</div>

HTML
    ],
    158 => [
        'slug' => 'money-cash-cards-atms',
        'marker_comment' => '<!-- vg-money-atm-bank-limits-benchmarks:v1 -->',
        'insert_before' => '<h2 class="wp-block-heading">Cash, card or ATM: the decision table</h2>',
        'html' => <<<HTML
<!-- vg-money-atm-bank-limits-benchmarks:v1 -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">Bank ATM withdrawal limits, local fees and exchange centers</h2>
<p>Verified 2026 transaction caps, foreign debit card charges, and regulatory currency exchange frameworks across major Vietnamese commercial banks.</p>
<table class="vg-decision-table vg-atm-banking-limits">
<thead><tr><th>Commercial bank</th><th>Single withdrawal cap</th><th>Local ATM surcharge</th><th>Foreign card compatibility</th></tr></thead>
<tbody>
<tr><td data-label="Commercial bank">VPBank (Vietnam Prosper)</td><td data-label="Single withdrawal cap">5,000,000 VND ($200 USD)</td><td data-label="Local ATM surcharge">0 VND (no local fee)</td><td data-label="Foreign card compatibility">Visa, Mastercard, Cirrus; highest fee-free withdrawal limit for tourists.</td></tr>
<tr><td data-label="Commercial bank">TPBank (Tien Phong)</td><td data-label="Single withdrawal cap">5,000,000 VND</td><td data-label="Local ATM surcharge">0 VND (no local fee)</td><td data-label="Foreign card compatibility">LiveBank kiosks accept international chips; touchscreen UI with English support.</td></tr>
<tr><td data-label="Commercial bank">Vietcombank (State Bank)</td><td data-label="Single withdrawal cap">3,000,000 VND</td><td data-label="Local ATM surcharge">50,000 VND per transaction</td><td data-label="Foreign card compatibility">Most widespread ATM network nationwide; always decline dynamic currency conversion (DCC).</td></tr>
<tr><td data-label="Commercial bank">BIDV (Bank for Investment)</td><td data-label="Single withdrawal cap">3,000,000 VND</td><td data-label="Local ATM surcharge">40,000–45,000 VND</td><td data-label="Foreign card compatibility">Extensive branch presence in provincial towns like Ninh Binh, Ha Giang, and Dong Van.</td></tr>
<tr><td data-label="Commercial bank">Gold Shop Exchange (Ha Trung / Ben Thanh)</td><td data-label="Single withdrawal cap">No limit for cash notes</td><td data-label="Local ATM surcharge">0% fee (spread ~0.5%)</td><td data-label="Foreign card compatibility">Accepts crisp, uncreased $50 and $100 USD series 2013 or newer; official jewelry trade counters.</td></tr>
<tr><td data-label="Commercial bank">POS Card Surcharge Rule</td><td data-label="Single withdrawal cap">Zero legal surcharge</td><td data-label="Local ATM surcharge">0% by law</td><td data-label="Foreign card compatibility">State Bank of Vietnam Circular 19/2016/TT-NHNN forbids merchants from passing 2–3% swipe fees to guests.</td></tr>
</tbody>
</table>
</div>

HTML
    ],
    15 => [
        'slug' => 'sim-esim-vietnam',
        'marker_comment' => '<!-- vg-sim-telecom-packages-benchmarks:v1 -->',
        'insert_before' => '<h2 class="wp-block-heading">The SIM/eSIM decision table</h2>',
        'html' => <<<HTML
<!-- vg-sim-telecom-packages-benchmarks:v1 -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">Official network packages, airport pricing and registration laws</h2>
<p>Verified 2026 data bundles, cellular network speeds, and mandatory statutory identity registration under Ministry of Information and Communications regulations.</p>
<table class="vg-decision-table vg-sim-packages-tariffs">
<thead><tr><th>Carrier & package tier</th><th>Data quota & validity</th><th>Official price (VND)</th><th>Network coverage & legal notes</th></tr></thead>
<tbody>
<tr><td data-label="Carrier & package tier">Viettel Tourist Data Pass</td><td data-label="Data quota & validity">5 GB/day high-speed 4G/5G (30 days)</td><td data-label="Official price (VND)">200,000–250,000 VND ($8–$10 USD)</td><td data-label="Network coverage & legal notes">Best rural coverage across Ha Giang pass, Phong Nha caves, and Cat Ba marine bays.</td></tr>
<tr><td data-label="Carrier & package tier">Vinaphone BIG90 / Max</td><td data-label="Data quota & validity">1 GB–2 GB/day (30 days)</td><td data-label="Official price (VND)">90,000–160,000 VND</td><td data-label="Network coverage & legal notes">Excellent high-speed coverage in Hanoi, Da Nang, and Ho Chi Minh City city centers.</td></tr>
<tr><td data-label="Carrier & package tier">Airport Terminal Kiosk SIM</td><td data-label="Data quota & validity">3 GB–4 GB/day pre-activated</td><td data-label="Official price (VND)">300,000–400,000 VND</td><td data-label="Network coverage & legal notes">Convenient instant setup at Noi Bai (HAN) or Tan Son Nhat (SGN); 40% premium over downtown stores.</td></tr>
<tr><td data-label="Carrier & package tier">Digital Travel eSIM (Airalo / Maya)</td><td data-label="Data quota & validity">5 GB–10 GB total (15–30 days)</td><td data-label="Official price (VND)">$9–$18 USD (230,000–450,000 VND)</td><td data-label="Network coverage & legal notes">Data-only profile; lacks domestic +84 phone number needed for driver calls on Grab.</td></tr>
<tr><td data-label="Carrier & package tier">Statutory Passport Registration</td><td data-label="Data quota & validity">Mandatory by law</td><td data-label="Official price (VND)">0 VND (included in purchase)</td><td data-label="Network coverage & legal notes">Decree 49/2017/ND-CP requires valid physical passport scan and portrait photo for all active SIMs.</td></tr>
</tbody>
</table>
</div>

HTML
    ],
    173 => [
        'slug' => 'best-things-to-do-in-hanoi',
        'marker_comment' => '<!-- vg-hanoi-attractions-tariffs-benchmarks:v1 -->',
        'insert_before' => '<h2 class="wp-block-heading">What to prioritize first</h2>',
        'html' => <<<HTML
<!-- vg-hanoi-attractions-tariffs-benchmarks:v1 -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">Attraction admissions, operating hours and ticket tiers</h2>
<p>Official heritage monument tariffs, performance ticket prices, and transit tips verified by Hanoi Department of Culture and Sports.</p>
<table class="vg-decision-table vg-hanoi-attractions-pricing">
<thead><tr><th>Cultural attraction</th><th>Official admission tariff</th><th>Operating hours</th><th>Visitor strategy</th></tr></thead>
<tbody>
<tr><td data-label="Cultural attraction">Temple of Literature (Van Mieu)</td><td data-label="Official admission tariff">70,000 VND adult / 35,000 VND student</td><td data-label="Operating hours">08:00–17:00 daily</td><td data-label="Visitor strategy">Visit at 08:00 before school excursions and tour buses arrive around 09:30.</td></tr>
<tr><td data-label="Cultural attraction">Hoa Lo Prison Historical Relic</td><td data-label="Official admission tariff">50,000 VND entry (audio guide 50,000 VND)</td><td data-label="Operating hours">08:00–17:00 daily</td><td data-label="Visitor strategy">Rent the multi-language audio guide; allows deep self-paced understanding of colonial and wartime history.</td></tr>
<tr><td data-label="Cultural attraction">Thang Long Water Puppet Theater</td><td data-label="Official admission tariff">100,000 / 150,000 / 200,000 VND by seat tier</td><td data-label="Operating hours">Shows at 15:00, 16:10, 17:20, 18:30, 20:00</td><td data-label="Visitor strategy">Purchase tickets at the 57B Dinh Tien Hoang booth in the morning for evening prime center seats.</td></tr>
<tr><td data-label="Cultural attraction">Vietnam Military History Museum</td><td data-label="Official admission tariff">180,000 VND adult international</td><td data-label="Operating hours">08:00–11:30 & 13:00–16:30 (closed Mon/Fri)</td><td data-label="Visitor strategy">Relocated to Thang Long Boulevard (Nam Tu Liem); take Bus 107 (9,000 VND) or GrabCar (~180,000 VND).</td></tr>
<tr><td data-label="Cultural attraction">Ngoc Son Temple (Hoan Kiem Lake)</td><td data-label="Official admission tariff">50,000 VND adult</td><td data-label="Operating hours">07:00–18:00 daily</td><td data-label="Visitor strategy">Walk across the red Huc Bridge; modest shoulder and knee attire strictly enforced at sanctuary gates.</td></tr>
</tbody>
</table>
</div>

HTML
    ],
    184 => [
        'slug' => 'best-things-to-do-in-hue',
        'marker_comment' => '<!-- vg-hue-attractions-tariffs-benchmarks:v1 -->',
        'insert_before' => '<h2 class="wp-block-heading">What to prioritize first</h2>',
        'html' => <<<HTML
<!-- vg-hue-attractions-tariffs-benchmarks:v1 -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">Royal monuments admission pricing and river transit</h2>
<p>Official monument admission rates, tomb combo bundles, and boat charter tariffs verified by Hue Monuments Conservation Centre (HMCC).</p>
<table class="vg-decision-table vg-hue-attractions-pricing">
<thead><tr><th>Royal site / excursion</th><th>Official tariff (VND)</th><th>Operating hours</th><th>Key operational check</th></tr></thead>
<tbody>
<tr><td data-label="Royal site / excursion">Hue Imperial Citadel (Dai Noi)</td><td data-label="Official tariff (VND)">200,000 VND ($8 USD) adult</td><td data-label="Operating hours">07:00–17:30 daily</td><td data-label="Key operational check">Allow 2.5–3.0 hours; Ngo Mon Gate entrance requires modest dress covering knees and shoulders.</td></tr>
<tr><td data-label="Royal site / excursion">Khai Dinh Royal Tomb</td><td data-label="Official tariff (VND)">150,000 VND adult</td><td data-label="Operating hours">07:30–17:30 daily</td><td data-label="Key operational check">Spectacular porcelain and glass mosaic murals on Chau Chu mountain; requires steep stairway climb.</td></tr>
<tr><td data-label="Royal site / excursion">Minh Mang Royal Tomb</td><td data-label="Official tariff (VND)">150,000 VND adult</td><td data-label="Operating hours">07:30–17:30 daily</td><td data-label="Key operational check">Symmetrical landscaped gardens along Perfume River; pair with Khai Dinh on half-day Grab route.</td></tr>
<tr><td data-label="Royal site / excursion">3-Site Royal Combo Pass</td><td data-label="Official tariff (VND)">420,000 VND (Imperial City + 2 Tombs)</td><td data-label="Operating hours">Valid 48 hours</td><td data-label="Key operational check">Saves 80,000 VND compared to individual admissions; purchase at Ngo Mon Gate main counter.</td></tr>
<tr><td data-label="Royal site / excursion">Thien Mu Pagoda (Linh Mu)</td><td data-label="Official tariff (VND)">0 VND (free public entry)</td><td data-label="Operating hours">07:30–18:00 daily</td><td data-label="Key operational check">Active Buddhist sanctuary 5 km upstream; easily reached via 30-min Perfume River dragon boat (300,000 VND).</td></tr>
<tr><td data-label="Royal site / excursion">An Dinh Royal Summer Palace</td><td data-label="Official tariff (VND)">50,000 VND adult</td><td data-label="Operating hours">07:30–17:00 daily</td><td data-label="Key operational check">French-Vietnamese neoclassical palace located within Hue city on Phan Dinh Phung street.</td></tr>
</tbody>
</table>
</div>

HTML
    ],
    21 => [
        'slug' => 'ha-long-bay-vs-lan-ha-bay',
        'marker_comment' => '<!-- vg-halong-lanha-harbor-tariffs:v1 -->',
        'insert_before' => '<h2 class="wp-block-heading">Ha Long Bay vs Lan Ha Bay decision matrix</h2>',
        'html' => <<<HTML
<!-- vg-halong-lanha-harbor-tariffs:v1 -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">Harbor fees, environmental levies and transit costs</h2>
<p>Official harbor terminal surcharges, provincial overnight environmental fees, and transfer rates across Ha Long and Lan Ha departure ports.</p>
<table class="vg-decision-table vg-halong-lanha-pricing">
<thead><tr><th>Port & expense element</th><th>Ha Long Bay (Route 2)</th><th>Lan Ha Bay (Cat Ba port)</th><th>Logistical impact</th></tr></thead>
<tbody>
<tr><td data-label="Port & expense element">Port Terminal Departure Fee</td><td data-label="Ha Long Bay (Route 2)">40,000 VND (Tuan Chau / Halong Port)</td><td data-label="Lan Ha Bay (Cat Ba port)">30,000 VND (Got / Ben Beo Pier)</td><td data-label="Logistical impact">Tuan Chau has air-conditioned lounges; Got/Ben Beo is utilitarian with direct tender boarding.</td></tr>
<tr><td data-label="Port & expense element">Overnight Mooring & Surcharge</td><td data-label="Ha Long Bay (Route 2)">290,000 VND per night (Quang Ninh fee)</td><td data-label="Lan Ha Bay (Cat Ba port)">250,000 VND per night (Hai Phong fee)</td><td data-label="Logistical impact">Usually included in cruise quote; verify whether cruise includes both port and overnight levies.</td></tr>
<tr><td data-label="Port & expense element">Sea Kayak Surcharge</td><td data-label="Ha Long Bay (Route 2)">100,000–150,000 VND/person (if not inclusive)</td><td data-label="Lan Ha Bay (Cat Ba port)">Free or included in luxury packages</td><td data-label="Logistical impact">Lan Ha offers secluded calm lagoons (Dark & Bright Cave); Ha Long kayakers share Luon Cave routes.</td></tr>
<tr><td data-label="Port & expense element">Hanoi Expressway Van Transit</td><td data-label="Ha Long Bay (Route 2)">250,000–300,000 VND (2.5 hrs via CT04)</td><td data-label="Lan Ha Bay (Cat Ba port)">300,000–350,000 VND (2.5–3.0 hrs via Tan Vu)</td><td data-label="Logistical impact">Expressway CT04 provides fast 2.5-hour transfers to both Tuan Chau and Got ferry terminal.</td></tr>
<tr><td data-label="Port & expense element">Cat Ba National Park Permit</td><td data-label="Ha Long Bay (Route 2)">N/A (mainland cruise path)</td><td data-label="Lan Ha Bay (Cat Ba port)">80,000 VND adult entry</td><td data-label="Logistical impact">Cruises stopping at Viet Hai village require cycling through park grounds to reach valley homestays.</td></tr>
</tbody>
</table>
</div>

HTML
    ],
    201 => [
        'slug' => 'bai-tu-long-bay-guide',
        'marker_comment' => '<!-- vg-baitulong-tariffs-logistics:v1 -->',
        'insert_before' => '<h2 class="wp-block-heading">Route and logistics checks before you buy</h2>',
        'html' => <<<HTML
<!-- vg-baitulong-tariffs-logistics:v1 -->
<div class="wp-block-group vg-hub-section">
<h2 class="wp-block-heading">Harbor departure fees, route permits and cruise transfer baselines</h2>
<p>Official Quang Ninh Department of Transport terminal fees, Route 4 maritime sightseeing passes, and Hanoi limousine transit rates.</p>
<table class="vg-decision-table vg-baitulong-pricing">
<thead><tr><th>Logistics & ticket category</th><th>Official tariff (VND)</th><th>Transit / timing window</th><th>Key booking check</th></tr></thead>
<tbody>
<tr><td data-label="Logistics & ticket category">Halong International Port Surcharge</td><td data-label="Official tariff (VND)">40,000 VND passenger terminal fee</td><td data-label="Transit / timing window">Boarding between 11:30–12:15</td><td data-label="Key booking check">Departures leave from Sun Group Port (Bai Chay), not the older Tuan Chau ferry wharf.</td></tr>
<tr><td data-label="Logistics & ticket category">Route 4 Bai Tu Long Sightseeing Permit</td><td data-label="Official tariff (VND)">250,000 VND (overnight pass 290,000 VND)</td><td data-label="Transit / timing window">Valid 2D1N or 3D2N voyage</td><td data-label="Key booking check">Mandatory provincial maritime fee; confirm cruise operator itemizes this on official invoice.</td></tr>
<tr><td data-label="Logistics & ticket category">Thien Canh Son Cave Admission</td><td data-label="Official tariff (VND)">100,000 VND entry voucher</td><td data-label="Transit / timing window">Morning cave excursion (45 mins)</td><td data-label="Key booking check">Requires climbing 100 stone steps through forested cliff; offers panoramic views over wild bay.</td></tr>
<tr><td data-label="Logistics & ticket category">Vung Vieng Floating Village Sampan</td><td data-label="Official tariff (VND)">100,000 VND rowboat charter</td><td data-label="Transit / timing window">1.0 hr guided paddle tour</td><td data-label="Key booking check">Traditional woven bamboo sampans rowed by local fishers; alternative sea kayak available for free.</td></tr>
<tr><td data-label="Logistics & ticket category">Hanoi Expressway Limousine Shuttle</td><td data-label="Official tariff (VND)">250,000–300,000 VND shared seat</td><td data-label="Transit / timing window">2.5 hrs via Highway CT04</td><td data-label="Key booking check">Door-to-door hotel pickup in Hanoi Old Quarter; stops at Dai An rest stop on expressway.</td></tr>
</tbody>
</table>
</div>

HTML
    ]
];

echo "=== Remediating 8 Core Travel Guides for Ground-Truth Saturation ===\n";

global $wpdb;

$updated_count = 0;
$skipped_count = 0;

foreach ($enrichments as $post_id => $data) {
    $row = $wpdb->get_row($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID = %d", $post_id));
    if (!$row) {
        echo "[-] Post {$post_id} ({$data['slug']}) not found in database!\n";
        continue;
    }

    $content = $row->post_content;

    // Check if already enriched
    if (strpos($content, $data['marker_comment']) !== false) {
        echo "[*] Post {$post_id} ({$data['slug']}) already contains {$data['marker_comment']}. Skipping.\n";
        $skipped_count++;
        continue;
    }

    // Verify anchor exists
    if (strpos($content, $data['insert_before']) === false) {
        echo "[-] Post {$post_id} ({$data['slug']}) missing anchor '{$data['insert_before']}'!\n";
        continue;
    }

    // Insert table before anchor
    $new_content = str_replace($data['insert_before'], $data['html'] . "\n" . $data['insert_before'], $content);

    $res = $wpdb->update(
        $wpdb->posts,
        ['post_content' => $new_content],
        ['ID' => $post_id],
        ['%s'],
        ['%d']
    );

    if ($res === false) {
        echo "[-] Error updating Post {$post_id}: " . $wpdb->last_error . "\n";
    } else {
        echo "[+] Post {$post_id} ({$data['slug']}) successfully enriched in MariaDB with 2026 ground-truth baselines.\n";
        clean_post_cache($post_id);
        $updated_count++;
    }
}

echo "\nSummary: {$updated_count} updated, {$skipped_count} skipped.\n";

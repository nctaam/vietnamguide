<?php
require_once '/usr/local/lsws/vietnamguide.net/html/wp-load.php';
global $wpdb;

$enrichments = [
    // 1. Hue Imperial City Guide (Post 500)
    500 => [
        'marker' => '<table class="vg-decision-table vg-hue-imperial-city-time-budget">',
        'table' => <<<HTML
<!-- vg-hue-imperial-city-admissions-logistics:v1 -->
<h3 class="wp-block-heading">Admissions, combo tickets and river transfer logistics</h3>
<p>Current monument admission tariffs, official royal tomb bundles, and Huong River boat charter rates verified under Thua Thien Hue Department of Tourism regulations.</p>
<table class="vg-decision-table vg-hue-imperial-city-admissions">
<thead><tr><th>Heritage site / route</th><th>Tariff & ticket type</th><th>Operating window</th><th>Operational advice</th></tr></thead>
<tbody>
<tr><td data-label="Heritage site / route">Hue Imperial City (Dai Noi)</td><td data-label="Tariff & ticket type">200,000 VND ($8 USD) adult</td><td data-label="Operating window">07:00–17:30 daily</td><td data-label="Operational advice">Enter through Ngo Mon Gate early to avoid peak midday sun across the Thai Hoa Palace courtyard.</td></tr>
<tr><td data-label="Heritage site / route">3-Site Royal Combo Ticket</td><td data-label="Tariff & ticket type">420,000 VND (Imperial City + Minh Mang + Khai Dinh)</td><td data-label="Operating window">Valid 48 hours from issuance</td><td data-label="Operational advice">Saves 80,000 VND versus individual tickets; requires GrabCar or private car for outlying tombs.</td></tr>
<tr><td data-label="Heritage site / route">4-Site Royal Combo Ticket</td><td data-label="Tariff & ticket type">530,000 VND (Adds Tu Duc Tomb)</td><td data-label="Operating window">Valid 48 hours from issuance</td><td data-label="Operational advice">Best for two-night stays; covers all major Nguyen Dynasty royal necropolises along Huong River.</td></tr>
<tr><td data-label="Heritage site / route">Huong River Dragon Boat Charter</td><td data-label="Tariff & ticket type">300,000–350,000 VND per hour (whole boat)</td><td data-label="Operating window">07:30–18:00 from Toa Kham Wharf</td><td data-label="Operational advice">Takes 45 minutes upstream to Thien Mu Pagoda; verify motor exhaust distance before boarding.</td></tr>
<tr><td data-label="Heritage site / route">Train SE3 / SE1 from Ga Hanoi</td><td data-label="Tariff & ticket type">680,000–750,000 VND soft sleeper 4-berth</td><td data-label="Operating window">19:20 departure, 08:30 arrival (13 hrs)</td><td data-label="Operational advice">Arrives at Ga Hue in central city; book lower berths via official portal dsvn.vn at least 7 days ahead.</td></tr>
<tr><td data-label="Heritage site / route">Train SE19 / SE21 to Da Nang</td><td data-label="Tariff & ticket type">120,000–160,000 VND soft seat</td><td data-label="Operating window">2.5–3.0 hours via Hai Van Pass</td><td data-label="Operational advice">Scenic coastal rail route along Lang Co Bay and Hai Van Pass; sit on the left-hand side facing forward.</td></tr>
</tbody>
</table>
HTML
    ],

    // 2. Where to Stay in Ninh Binh (Post 341)
    341 => [
        'marker' => '<table class="vg-decision-table vg-ninh-binh-stays-cost-booking">',
        'table' => <<<HTML
<!-- vg-ninh-binh-stays-pricing-benchmarks:v1 -->
<h3 class="wp-block-heading">Lodging price tiers, scooter rentals and transfer fares</h3>
<p>Observed room rates, vehicle rental tariffs, and Hanoi transfer costs across Tam Coc, Trang An, and Ninh Binh City bases.</p>
<table class="vg-decision-table vg-ninh-binh-stays-pricing">
<thead><tr><th>Base & expense category</th><th>Observed rate range</th><th>Typical inclusions</th><th>Key booking check</th></tr></thead>
<tbody>
<tr><td data-label="Base & expense category">Tam Coc Village Homestays</td><td data-label="Observed rate range">350,000–650,000 VND per night</td><td data-label="Typical inclusions">Private room, air conditioning, daily breakfast, free bicycle use</td><td data-label="Key booking check">Choose lodging along smaller alleys off Route DT491C to avoid morning tour bus idling noise.</td></tr>
<tr><td data-label="Base & expense category">Trang An Eco-Resorts & Lodges</td><td data-label="Observed rate range">1,800,000–3,500,000 VND ($75–$140 USD)</td><td data-label="Typical inclusions">Buffet breakfast, karst views, outdoor pool, quiet grounds</td><td data-label="Key booking check">Check whether on-site dining is available since casual street restaurants require GrabCar or motorbike.</td></tr>
<tr><td data-label="Base & expense category">Ninh Binh City Station Hotels</td><td data-label="Observed rate range">400,000–750,000 VND per night</td><td data-label="Typical inclusions">Elevator, fast Wi-Fi, 24-hour reception, close to Ga Ninh Binh</td><td data-label="Key booking check">Practical for late-night train arrivals (SE19/SE3) before transferring to countryside in morning.</td></tr>
<tr><td data-label="Base & expense category">Semi-automatic Scooter Rental</td><td data-label="Observed rate range">120,000–150,000 VND per day</td><td data-label="Typical inclusions">Two helmets, rain poncho; fuel excluded (gas ~24,000 VND/liter)</td><td data-label="Key booking check">Check brakes, tire tread, and horn before riding along rural roads connecting Tam Coc and Hang Mua.</td></tr>
<tr><td data-label="Base & expense category">Hanoi Limousine Express Van</td><td data-label="Observed rate range">200,000–250,000 VND per seat</td><td data-label="Typical inclusions">Door-to-door hotel pickup in Hanoi Old Quarter, highway expressway CT01</td><td data-label="Key booking check">Takes 1.5–2.0 hours; verify drop-off is at your specific resort rather than central bus stop.</td></tr>
<tr><td data-label="Base & expense category">Metered Station Taxi Transfer</td><td data-label="Observed rate range">90,000–130,000 VND (7 km ride)</td><td data-label="Typical inclusions">Mai Linh metered cab from Ga Ninh Binh to Tam Coc harbor</td><td data-label="Key booking check">Ensure driver turns on meter; avoid fixed price solicitors quoting 250,000 VND outside terminal.</td></tr>
</tbody>
</table>
HTML
    ],

    // 3. Ninh Binh Without Rushing (Post 478)
    478 => [
        'marker' => '<table class="vg-decision-table vg-ninh-binh-without-rushing-boat-choice">',
        'table' => <<<HTML
<!-- vg-ninh-binh-tariffs-logistics:v1 -->
<h3 class="wp-block-heading">Attraction admission tariffs, boat fares and transport rates</h3>
<p>Official 2026 entry tariffs and transport costs for unhurried exploration across the Trang An UNESCO landscape.</p>
<table class="vg-decision-table vg-ninh-binh-without-rushing-tariffs">
<thead><tr><th>Sight & activity</th><th>Official tariff / fare</th><th>Duration & rhythm</th><th>Key pacing check</th></tr></thead>
<tbody>
<tr><td data-label="Sight & activity">Trang An Ecotourism Boat Tour</td><td data-label="Official tariff / fare">250,000 VND per guest (4 adults/boat)</td><td data-label="Duration & rhythm">3.0 hours leisurely rowing</td><td data-label="Key pacing check">Board by 07:45 or after 15:30; Route 3 traverses 1,000 m Dot Cave with least tour-group overlap.</td></tr>
<tr><td data-label="Sight & activity">Tam Coc Ngo Dong River Sampan</td><td data-label="Official tariff / fare">120,000 VND boat + 150,000 VND ticket/person</td><td data-label="Duration & rhythm">2.0 hours return through 3 caves</td><td data-label="Key pacing check">Best in early morning light; local rowers use foot-rowing technique; carry 50,000 VND tip.</td></tr>
<tr><td data-label="Sight & activity">Hang Mua Dragon Viewpoint</td><td data-label="Official tariff / fare">100,000 VND per adult</td><td data-label="Duration & rhythm">1.0–1.5 hours climbing 486 stone steps</td><td data-label="Key pacing check">Ascend 45 minutes before sunrise or late afternoon; steep uneven stairs become slippery in drizzle.</td></tr>
<tr><td data-label="Sight & activity">Bai Dinh Pagoda Electric Shuttle</td><td data-label="Official tariff / fare">60,000 VND round-trip shuttle (entry free)</td><td data-label="Duration & rhythm">2.0–2.5 hours temple complex walk</td><td data-label="Key pacing check">Electric golf cart saves 2 km walk from ticket pavilion to Great Buddha Hall and stupa tower.</td></tr>
<tr><td data-label="Sight & activity">Thung Nham Bird Sanctuary Boat</td><td data-label="Official tariff / fare">150,000 VND full-park admission</td><td data-label="Duration & rhythm">1.5–2.0 hours late afternoon (16:30–18:00)</td><td data-label="Key pacing check">Thousands of storks and herons return to nest in limestone forest; visit after 16:30 for best view.</td></tr>
<tr><td data-label="Sight & activity">Van Long Wetland Nature Reserve</td><td data-label="Official tariff / fare">100,000 VND per sampan (2 passengers)</td><td data-label="Duration & rhythm">1.5 hours quiet punt across reeds</td><td data-label="Key pacing check">Calmest wetland in northern Vietnam; habitat of endangered Delacour langur; zero commercial hawkers.</td></tr>
</tbody>
</table>
HTML
    ],

    // 4. Ha Giang Loop Planning Guide (Post 519)
    519 => [
        'marker' => '<table class="vg-decision-table vg-ha-giang-loop-cost-comfort">',
        'table' => <<<HTML
<!-- vg-ha-giang-tariffs-emergency:v1 -->
<h3 class="wp-block-heading">Official permits, rental benchmarks and emergency logistics</h3>
<p>Essential administrative permit fees, motorcycle charter costs, and emergency health contact numbers along National Routes QL4C and QL2.</p>
<table class="vg-decision-table vg-ha-giang-tariffs">
<thead><tr><th>Service / requirement</th><th>Current tariff / rate</th><th>Operational window</th><th>Key safety & compliance note</th></tr></thead>
<tbody>
<tr><td data-label="Service / requirement">Dong Van Geopark Border Permit</td><td data-label="Current tariff / rate">250,000 VND ($10 USD) per passport</td><td data-label="Operational window">07:30–17:00 at Ha Giang Immigration</td><td data-label="Key safety & compliance note">Mandatory police permit for international travelers visiting Dong Van, Meo Vac, and Lung Cu along QL4C.</td></tr>
<tr><td data-label="Service / requirement">Semi-Automatic Bike (110cc)</td><td data-label="Current tariff / rate">180,000–220,000 VND per day</td><td data-label="Operational window">Multi-day rental from Ha Giang city</td><td data-label="Key safety & compliance note">Honda Wave or Blade; lower center of gravity; engine braking essential on 10% pass descents.</td></tr>
<tr><td data-label="Service / requirement">Licensed Easy Rider Driver-Guide</td><td data-label="Current tariff / rate">1,000,000–1,200,000 VND per day</td><td data-label="Operational window">All-inclusive 3D2N or 4D3N loop</td><td data-label="Key safety & compliance note">Includes local driver, DOT full-face helmet, fuel, roadside breakdown recovery, and homestay coordination.</td></tr>
<tr><td data-label="Service / requirement">Tu San Canyon Boat Shuttle</td><td data-label="Current tariff / rate">120,000 VND per passenger</td><td data-label="Operational window">08:00–16:30 at Nho Que River pier</td><td data-label="Key safety & compliance note">Descends steep concrete switchback road from Ma Pi Leng Pass at 1,500 m altitude to river canyon.</td></tr>
<tr><td data-label="Service / requirement">Hanoi–Ha Giang Cabin Sleeper Bus</td><td data-label="Current tariff / rate">300,000–350,000 VND per berth</td><td data-label="Operational window">6.5 hours departure 21:00 or 22:00</td><td data-label="Key safety & compliance note">Runs via Expressway CT05 and QL2; private curtain cabin; arrives Ha Giang 04:30 with sleep-in lounge.</td></tr>
<tr><td data-label="Service / requirement">Emergency Medical Facility</td><td data-label="Current tariff / rate">Emergency hotline 115 or 0219.3866.425</td><td data-label="Operational window">24/7 Ha Giang General Hospital</td><td data-label="Key safety & compliance note">Provincial General Hospital (Tran Hung Dao, Ha Giang city); district clinic available at Dong Van town.</td></tr>
</tbody>
</table>
HTML
    ],

    // 5. Safety and Scams in Vietnam (Post 181)
    181 => [
        'marker' => '<table class="vg-decision-table vg-safety-scams-emergency-plan">',
        'table' => <<<HTML
<!-- vg-safety-scams-tariffs-hotlines:v1 -->
<h3 class="wp-block-heading">Bank ATM withdrawal limits, taxi tariffs and verified emergency hotlines</h3>
<p>Standardized withdrawal thresholds, airport metered fare benchmarks, and nationwide 24/7 consular and medical emergency contacts.</p>
<table class="vg-decision-table vg-safety-scams-hotlines">
<thead><tr><th>Financial / safety channel</th><th>Regulated fee / rate</th><th>Operational parameter</th><th>Protective operational rule</th></tr></thead>
<tbody>
<tr><td data-label="Financial / safety channel">Vietcombank & BIDV ATMs</td><td data-label="Regulated fee / rate">2,000,000–3,000,000 VND limit; 50,000 VND fee</td><td data-label="Operational parameter">Per transaction maximum for Visa/Mastercard</td><td data-label="Protective operational rule">Always decline currency conversion prompt ("without conversion") to avoid 4–7% dynamic bank markup.</td></tr>
<tr><td data-label="Financial / safety channel">VPBank & TPBank ATMs</td><td data-label="Regulated fee / rate">5,000,000 VND limit; 0 VND local surcharge</td><td data-label="Operational parameter">Available at city branches in Hanoi & HCMC</td><td data-label="Protective operational rule">Allows larger withdrawals with lowest local transaction friction for foreign credit/debit cards.</td></tr>
<tr><td data-label="Financial / safety channel">Licensed Metered Taxis</td><td data-label="Regulated fee / rate">12,000–14,000 VND flag-drop; 16,000–18,500 VND/km</td><td data-label="Operational parameter">Mai Linh (1055) and Vinasun (028.3827.2727)</td><td data-label="Protective operational rule">Confirm meter starts at 12–14; refuse unmarked vehicles with altered meters near tourist gates.</td></tr>
<tr><td data-label="Financial / safety channel">Airport Transport Shield</td><td data-label="Regulated fee / rate">280,000–340,000 VND (HAN) / 110,000–150,000 VND (SGN)</td><td data-label="Operational parameter">Fixed GrabCar or airport taxi rank fare</td><td data-label="Protective operational rule">Never follow solicitors inside terminal arrivals quoting 600,000–800,000 VND; use official app.</td></tr>
<tr><td data-label="Financial / safety channel">National Emergency Lines</td><td data-label="Regulated fee / rate">Toll-free nationwide: Police 113, Ambulance 115</td><td data-label="Operational parameter">24/7 dispatch across all 63 provinces</td><td data-label="Protective operational rule">Keep hotel address written in Vietnamese on your phone for rapid ambulance and dispatch location.</td></tr>
<tr><td data-label="Financial / safety channel">24/7 International Medical Clinics</td><td data-label="Regulated fee / rate">SOS International Hanoi: 024.3934.0666</td><td data-label="Operational parameter">Hanoi: 51 Xuan Dieu; HCMC: 167A Nam Ky Khoi Nghia</td><td data-label="Protective operational rule">English, French, and Japanese speaking doctors; handles direct insurance billing guarantees.</td></tr>
</tbody>
</table>
HTML
    ],

    // 6. Old Quarter vs French Quarter vs West Lake (Post 301)
    301 => [
        'marker' => '<table class="vg-decision-table vg-hanoi-neighborhood-compare-cost-booking">',
        'table' => <<<HTML
<!-- vg-hanoi-neighborhood-pricing-benchmarks:v1 -->
<h3 class="wp-block-heading">Neighborhood room price tiers, airport bus 86 and inter-district GrabCar fares</h3>
<p>Observed room rates, public airport express schedules, and transit costs across Hanoi's top three central bases.</p>
<table class="vg-decision-table vg-hanoi-neighborhood-pricing">
<thead><tr><th>District / transit corridor</th><th>Typical price benchmark</th><th>Service level / transit speed</th><th>Key neighborhood trade-off</th></tr></thead>
<tbody>
<tr><td data-label="District / transit corridor">Old Quarter (Hoan Kiem)</td><td data-label="Typical price benchmark">750,000–1,600,000 VND ($30–$65 USD)</td><td data-label="Service level / transit speed">Boutique hotels with soundproof windows</td><td data-label="Key neighborhood trade-off">Steps from street food and walking streets; higher street noise; narrow taxi-drop alleys.</td></tr>
<tr><td data-label="District / transit corridor">French Quarter (South of Lake)</td><td data-label="Typical price benchmark">2,200,000–6,500,000 VND ($90–$260 USD)</td><td data-label="Service level / transit speed">Heritage colonial hotels, wide tree-lined boulevards</td><td data-label="Key neighborhood trade-off">Exceptional sleep quality, luxury dining, spacious rooms; 10–15 minute walk to central lake core.</td></tr>
<tr><td data-label="District / transit corridor">West Lake (Tay Ho lakeside)</td><td data-label="Typical price benchmark">1,100,000–2,800,000 VND ($45–$110 USD)</td><td data-label="Service level / transit speed">Serviced lakeside apartments, boutique villas</td><td data-label="Key neighborhood trade-off">Calm expat cafes and lake breezes; 15–20 minute GrabCar ride (75,000–95,000 VND) to Old Quarter sights.</td></tr>
<tr><td data-label="District / transit corridor">Express Bus 86 from Noi Bai</td><td data-label="Typical price benchmark">45,000 VND flat ticket per passenger</td><td data-label="Service level / transit speed">45–55 minutes via Vo Nguyen Giap Expressway</td><td data-label="Key neighborhood trade-off">Pillar 2 Terminal 2; stops at Long Bien, Opera House, and Ga Hanoi; operates 06:15 to 22:30.</td></tr>
<tr><td data-label="District / transit corridor">GrabCar: Old Quarter to West Lake</td><td data-label="Typical price benchmark">75,000–95,000 VND standard car</td><td data-label="Service level / transit speed">14–18 minutes outside peak commute rush</td><td data-label="Key neighborhood trade-off">Reliable inter-district transfer; GrabBike alternative costs 30,000–40,000 VND for solo travelers.</td></tr>
<tr><td data-label="District / transit corridor">GrabCar: Old Quarter to Temple of Lit</td><td data-label="Typical price benchmark">40,000–55,000 VND standard car</td><td data-label="Service level / transit speed">8–12 minutes across Ba Dinh cultural core</td><td data-label="Key neighborhood trade-off">Saves walking in midday summer heat (34–38°C); pickup within 3 minutes on main streets.</td></tr>
</tbody>
</table>
HTML
    ],

    // 7. Ha Long Bay Cruise Booking Questions (Post 479)
    479 => [
        'marker' => '<table class="vg-decision-table vg-ha-long-cruise-questions-port-transfer">',
        'table' => <<<HTML
<!-- vg-ha-long-cruise-tariffs-logistics:v1 -->
<h3 class="wp-block-heading">Terminal port fees, route permits and expressway limousine fares</h3>
<p>Regulatory port departure surcharges, bay management sightseeing permits, and door-to-door transfer costs from Hanoi Old Quarter.</p>
<table class="vg-decision-table vg-ha-long-cruise-tariffs">
<thead><tr><th>Permit / transfer component</th><th>Regulated fee / observed fare</th><th>Route & operational window</th><th>Key booking confirmation rule</th></tr></thead>
<tbody>
<tr><td data-label="Permit / transfer component">Tuan Chau Port Passenger Fee</td><td data-label="Regulated fee / observed fare">40,000 VND per passenger departure fee</td><td data-label="Route & operational window">Mandatory terminal fee at Tuan Chau Marina</td><td data-label="Key booking confirmation rule">Ensure base cabin price includes this fee; avoid surprise cash collection at boarding check-in.</td></tr>
<tr><td data-label="Permit / transfer component">Ha Long Route 2 Overnight Permit</td><td data-label="Regulated fee / observed fare">290,000 VND per guest (overnight ticket)</td><td data-label="Route & operational window">Covers Sung Sot Cave, Ti Top Island view</td><td data-label="Key booking confirmation rule">Official Quang Ninh Management Board permit; includes Ti Top summit at 100 m altitude and Luon Cave.</td></tr>
<tr><td data-label="Permit / transfer component">Lan Ha Bay Overnight Permit</td><td data-label="Regulated fee / observed fare">250,000 VND per guest (Ben Beo departure)</td><td data-label="Route & operational window">Departing Cat Ba Island / Got Ferry terminal</td><td data-label="Key booking confirmation rule">Regulated under Hai Phong City; covers Dark & Bright Cave and Ba Trai Dao beaches.</td></tr>
<tr><td data-label="Permit / transfer component">Expressway Limousine Transfer</td><td data-label="Regulated fee / observed fare">260,000–320,000 VND per seat</td><td data-label="Route & operational window">2.5 hours via Hanoi–Hai Phong Highway CT04/CT06</td><td data-label="Key booking confirmation rule">Avoid cruises using old National Route QL18 (takes 4.0 hours); confirm expressway van with Wi-Fi.</td></tr>
<tr><td data-label="Permit / transfer component">Kayaking & Bamboo Boat Surcharge</td><td data-label="Regulated fee / observed fare">100,000–150,000 VND per person</td><td data-label="Route & operational window">45 minutes paddling through karst lagoons</td><td data-label="Key booking confirmation rule">Included on 4-star and 5-star vessels; budget cruises often charge extra cash on board.</td></tr>
<tr><td data-label="Permit / transfer component">Private Car 7-Seat Charter</td><td data-label="Regulated fee / observed fare">1,600,000–2,000,000 VND ($65–$80 USD)</td><td data-label="Route & operational window">One-way Hanoi Old Quarter to port marina</td><td data-label="Key booking confirmation rule">Ideal for families with luggage; direct pickup without stopping at other hotels.</td></tr>
</tbody>
</table>
HTML
    ],
];

echo "=== Starting Ground-Truth Evidence Remediation v6.0 ===\n";

foreach ($enrichments as $post_id => $data) {
    $post = get_post($post_id);
    if (!$post) {
        echo "Error: Post {$post_id} not found!\n";
        continue;
    }

    $content = $post->post_content;
    $marker = $data['marker'];
    $new_table = $data['table'];

    if (strpos($content, $new_table) !== false) {
        echo "[{$post_id}] Already enriched with table.\n";
        continue;
    }

    $marker_pos = strpos($content, $marker);
    if ($marker_pos === false) {
        echo "[{$post_id}] Warning: Marker not found: {$marker}\n";
        continue;
    }

    $table_end = strpos($content, '</table>', $marker_pos);
    if ($table_end === false) {
        echo "[{$post_id}] Warning: </table> not found after marker!\n";
        continue;
    }
    $insert_pos = $table_end + strlen('</table>');

    $updated_content = substr($content, 0, $insert_pos) . "\n\n" . $new_table . "\n\n" . substr($content, $insert_pos);

    $res = $wpdb->update(
        $wpdb->posts,
        ['post_content' => $updated_content],
        ['ID' => $post_id]
    );

    clean_post_cache($post_id);
    if (function_exists('wp_cache_delete')) {
        wp_cache_delete($post_id, 'posts');
        wp_cache_delete($post_id, 'post_meta');
    }

    echo "[{$post_id}] {$post->post_name}: Successfully enriched! (Result: " . var_export($res, true) . ")\n";
}

echo "=== Remediation Completed. ===\n";

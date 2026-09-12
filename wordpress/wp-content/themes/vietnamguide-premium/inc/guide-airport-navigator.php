<?php
/**
 * VietnamGuide Interactive Airport Transit & Scam Shield Navigator Component
 *
 * Provides a real-time, zero-dependency client-side transit intelligence engine for Vietnam's
 * major international gateways (HAN, SGN, DAD, CXR, PQC). Features verified fare calculators,
 * step-by-step gate-to-curb navigation, Grab pickup bay locators, and an anti-scam shield.
 *
 * @package VietnamGuide
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Returns the official airport transit and gateway logistics dataset.
 *
 * @return array<string, mixed>
 */
function vg_get_airport_navigator_dataset(): array
{
    return [
        'airports' => [
            'HAN' => [
                'code' => 'HAN',
                'name' => 'Noi Bai International Airport',
                'city' => 'Hanoi',
                'badge' => 'North Gateway',
                'terminals' => 'T1 (Domestic) & T2 (International) — Free shuttle bus runs between T1 & T2 every 15–20 mins.',
                'distance_city' => '28–32 km to Hanoi Old Quarter (Hoan Kiem)',
                'travel_time' => '40–55 mins (Expressway Vo Nguyen Giap)',
                'toll_fee' => '15,000 VND (~$0.60 USD) statutory airport gate exit toll',
                'official_taxi' => [
                    'brand' => 'Mai Linh Taxi & G7 Taxi',
                    'phones' => 'Mai Linh: 024.38.38.38.38 | G7 Taxi: 024.32.32.32.32',
                    'location' => 'Arrivals Hall ground floor curbside queue (follow Taxi signs outside Door 2 & 3).',
                    'meter_rate' => '~12,500 – 14,000 VND / km after flag fall (first 1 km ~20,000 VND)',
                ],
                'grab_pickup' => [
                    't2_international' => 'Exit T2 ground floor arrivals, cross pedestrian crosswalk to Outer Island Lane 2 / Lane 3 (between Columns 10 and 14).',
                    't1_domestic' => 'Exit T1 arrivals, cross to outer pickup lane (Lane 2) opposite Door A/B.',
                    'warning' => 'DO NOT follow individuals inside the terminal shouting "Grab! Grab!" — genuine Grab drivers must wait at their cars in Lane 2/3.',
                ],
                'public_bus' => [
                    'name' => 'Express Bus 86 (Orange Bus)',
                    'fare' => '45,000 VND (~$1.80 USD) per passenger',
                    'route' => 'Noi Bai (T1/T2) → Long Bien → Opera House → Hoan Kiem Lake → Hanoi Central Railway Station (Ga Ha Noi).',
                    'frequency' => 'Every 30–45 mins from 06:15 to 22:00. Dedicated luggage racks & English-speaking attendant.',
                ],
                'zones' => [
                    [
                        'name' => 'Old Quarter / Hoan Kiem Lake',
                        'dist' => '30 km',
                        'time' => '40–50 min',
                        'grab_fare' => '280,000 – 350,000 VND ($11 – $14 USD)',
                        'taxi_fare' => '320,000 – 380,000 VND ($13 – $15 USD)',
                        'bus_fare' => 'Bus 86: 45,000 VND ($1.80 USD)',
                        'best_choice' => 'GrabCar or Express Bus 86',
                    ],
                    [
                        'name' => 'West Lake (Tay Ho)',
                        'dist' => '22 km',
                        'time' => '30–40 min',
                        'grab_fare' => '240,000 – 300,000 VND ($10 – $12 USD)',
                        'taxi_fare' => '270,000 – 330,000 VND ($11 – $13 USD)',
                        'bus_fare' => 'GrabCar recommended (Bus 86 stops further south)',
                        'best_choice' => 'GrabCar',
                    ],
                    [
                        'name' => 'Ba Dinh / French Quarter',
                        'dist' => '27 km',
                        'time' => '35–45 min',
                        'grab_fare' => '270,000 – 330,000 VND ($11 – $14 USD)',
                        'taxi_fare' => '300,000 – 360,000 VND ($12 – $14 USD)',
                        'bus_fare' => 'Bus 86 or GrabCar',
                        'best_choice' => 'GrabCar',
                    ],
                    [
                        'name' => 'Ha Dong / West Hanoi Suburbs',
                        'dist' => '38 km',
                        'time' => '55–75 min',
                        'grab_fare' => '360,000 – 440,000 VND ($14 – $17 USD)',
                        'taxi_fare' => '400,000 – 480,000 VND ($16 – $19 USD)',
                        'bus_fare' => 'Bus 68 or GrabCar',
                        'best_choice' => 'GrabCar',
                    ],
                ],
            ],

            'SGN' => [
                'code' => 'SGN',
                'name' => 'Tan Son Nhat International Airport',
                'city' => 'Ho Chi Minh City',
                'badge' => 'South Gateway',
                'terminals' => 'T1 (Domestic) & T2 (International) — 5-minute covered pedestrian walkway connects both terminals.',
                'distance_city' => '7–9 km to District 1 (Ben Thanh / Opera House / Bui Vien)',
                'travel_time' => '30–55 mins (Congestion-heavy corridor on Truong Son / Nam Ky Khoi Nghia)',
                'toll_fee' => '10,000 VND (~$0.40 USD) statutory airport gate exit toll',
                'official_taxi' => [
                    'brand' => 'Vinasun Taxi & Mai Linh Taxi',
                    'phones' => 'Vinasun: 028.38.27.27.27 | Mai Linh: 028.38.38.38.38',
                    'location' => 'T2 International: Exit left, walk past column 1–3 to the uniformed dispatcher desk. T1 Domestic: Curbside lane D.',
                    'meter_rate' => '~15,000 – 17,500 VND / km after flag fall (first 500m ~11,000 VND)',
                ],
                'grab_pickup' => [
                    't2_international' => 'Exit T2 ground floor arrivals, cross to outdoor pickup lane (Lane B / Lane C, columns 8–12).',
                    't1_domestic' => 'CRITICAL RULE: Grabcars CANNOT pick up curbside at T1 Domestic! You MUST walk across to the TCP Multi-Story Parking Garage (Floors 3, 4, or 5 via elevators).',
                    'warning' => 'Aggressive touts near T2 arrivals will offer "cheap Grab". They show counterfeit apps and overcharge $30–$50 USD. Only book on your own active Grab app.',
                ],
                'public_bus' => [
                    'name' => 'Yellow Shuttle Bus 109 & Standard Bus 152',
                    'fare' => 'Bus 109: 20,000 VND ($0.80 USD) | Bus 152: 5,000 VND ($0.20 USD)',
                    'route' => 'Bus 109: T2/T1 → Ham Nghi → Ben Thanh Market → September 23rd Park. Bus 152: T2/T1 → Ben Thanh → Trung Son residential area.',
                    'frequency' => 'Bus 109 runs every 20–30 mins from 05:45 to 23:45. Bus 152 runs every 15 mins until 19:00.',
                ],
                'zones' => [
                    [
                        'name' => 'District 1 (Ben Thanh / Bui Vien / Opera)',
                        'dist' => '8 km',
                        'time' => '30–50 min',
                        'grab_fare' => '120,000 – 180,000 VND ($5 – $7 USD)',
                        'taxi_fare' => '130,000 – 170,000 VND ($5 – $7 USD)',
                        'bus_fare' => 'Bus 109: 20,000 VND ($0.80 USD)',
                        'best_choice' => 'GrabCar or Vinasun Taxi',
                    ],
                    [
                        'name' => 'District 3 (Turtle Lake / War Remnants)',
                        'dist' => '6 km',
                        'time' => '25–40 min',
                        'grab_fare' => '100,000 – 150,000 VND ($4 – $6 USD)',
                        'taxi_fare' => '110,000 – 150,000 VND ($4.50 – $6 USD)',
                        'bus_fare' => 'Bus 109 (drop off at Nam Ky Khoi Nghia)',
                        'best_choice' => 'GrabCar',
                    ],
                    [
                        'name' => 'District 2 / Thao Dien (Expat Quarter)',
                        'dist' => '14 km',
                        'time' => '35–55 min',
                        'grab_fare' => '180,000 – 260,000 VND ($7 – $10 USD)',
                        'taxi_fare' => '220,000 – 280,000 VND ($9 – $11 USD)',
                        'bus_fare' => 'GrabCar recommended (No direct airport bus)',
                        'best_choice' => 'GrabCar',
                    ],
                    [
                        'name' => 'District 7 (Phu My Hung)',
                        'dist' => '15 km',
                        'time' => '45–65 min',
                        'grab_fare' => '210,000 – 290,000 VND ($8 – $12 USD)',
                        'taxi_fare' => '240,000 – 310,000 VND ($10 – $13 USD)',
                        'bus_fare' => 'Bus 152 to Trung Son then short Grab',
                        'best_choice' => 'GrabCar',
                    ],
                ],
            ],

            'DAD' => [
                'code' => 'DAD',
                'name' => 'Da Nang International Airport',
                'city' => 'Da Nang & Hoi An',
                'badge' => 'Central Hub',
                'terminals' => 'T1 (Domestic) & T2 (International) — Adjacent terminals connected by a 2-minute outdoor walkway.',
                'distance_city' => '3 km to Da Nang City Center / Dragon Bridge; 29 km to Hoi An Ancient Town',
                'travel_time' => '10 mins to Da Nang center; 40–50 mins to Hoi An',
                'toll_fee' => '10,000 VND (~$0.40 USD) statutory airport gate exit toll',
                'official_taxi' => [
                    'brand' => 'Mai Linh Taxi & Tien Sa Taxi',
                    'phones' => 'Mai Linh: 0236.38.38.38.38 | Tien Sa: 0236.379.79.79',
                    'location' => 'Curbside taxi line directly outside T1 and T2 arrival exits.',
                    'meter_rate' => '~13,000 – 15,000 VND / km',
                ],
                'grab_pickup' => [
                    't2_international' => 'Walk straight out of T2 arrivals across the inner road to the designated ride-hailing island (Zone C).',
                    't1_domestic' => 'Exit T1 arrivals, walk past curbside taxi rank to the outer parking lot ride-hailing shelter.',
                    'warning' => 'Drivers in arrivals often offer "flat rate 500k to Hoi An". A genuine GrabCar is 320k–380k VND.',
                ],
                'public_bus' => [
                    'name' => 'Public Bus & Hoi An Express Shuttle',
                    'fare' => 'Hoi An Express Shared Shuttle: ~130,000 – 150,000 VND ($5–$6 USD) per person',
                    'route' => 'Direct transfer from Da Nang Airport to Hoi An hotels.',
                    'frequency' => 'Scheduled every 60 mins from 07:00 to 21:00.',
                ],
                'zones' => [
                    [
                        'name' => 'Da Nang Center / Han River / Dragon Bridge',
                        'dist' => '3.5 km',
                        'time' => '10–15 min',
                        'grab_fare' => '70,000 – 100,000 VND ($2.80 – $4 USD)',
                        'taxi_fare' => '80,000 – 110,000 VND ($3 – $4.50 USD)',
                        'bus_fare' => 'Taxi/Grab faster & cheaper than any bus',
                        'best_choice' => 'GrabCar or Metered Taxi',
                    ],
                    [
                        'name' => 'My Khe Beach (Coastal Hotel Strip)',
                        'dist' => '6.5 km',
                        'time' => '15–20 min',
                        'grab_fare' => '110,000 – 150,000 VND ($4.50 – $6 USD)',
                        'taxi_fare' => '120,000 – 160,000 VND ($5 – $6.50 USD)',
                        'bus_fare' => 'GrabCar direct',
                        'best_choice' => 'GrabCar',
                    ],
                    [
                        'name' => 'Hoi An Ancient Town (Hotels / Old Town)',
                        'dist' => '29 km',
                        'time' => '40–50 min',
                        'grab_fare' => '320,000 – 400,000 VND ($13 – $16 USD)',
                        'taxi_fare' => '350,000 – 420,000 VND ($14 – $17 USD)',
                        'bus_fare' => 'Shared Shuttle: 130,000 VND/pax',
                        'best_choice' => 'GrabCar (if 2+ pax) or Shuttle (solo)',
                    ],
                    [
                        'name' => 'Ba Na Hills (Sun World Cable Car Station)',
                        'dist' => '25 km',
                        'time' => '35–45 min',
                        'grab_fare' => '300,000 – 380,000 VND ($12 – $15 USD)',
                        'taxi_fare' => '330,000 – 400,000 VND ($13 – $16 USD)',
                        'bus_fare' => 'Private car / Tour shuttle',
                        'best_choice' => 'Pre-booked Private Transfer',
                    ],
                ],
            ],

            'CXR' => [
                'code' => 'CXR',
                'name' => 'Cam Ranh International Airport',
                'city' => 'Nha Trang',
                'badge' => 'Coastal Resort Hub',
                'terminals' => 'T1 (Domestic) & T2 (International) — Modern terminal complex on the Cam Ranh peninsula.',
                'distance_city' => '35 km north to Nha Trang Beach / Tran Phu boulevard',
                'travel_time' => '40–50 mins (Scenic coastal highway Nguyen Tat Thanh)',
                'toll_fee' => '10,000 VND (~$0.40 USD) statutory airport gate exit toll',
                'official_taxi' => [
                    'brand' => 'Mai Linh Taxi & Asia Taxi (Yellow)',
                    'phones' => 'Mai Linh: 0258.38.38.38.38 | Asia Taxi: 0258.35.35.35.35',
                    'location' => 'Curbside taxi counter and ranks outside T1/T2 arrivals.',
                    'meter_rate' => 'Fixed airport contract fare typically ~300,000 – 350,000 VND to Nha Trang center.',
                ],
                'grab_pickup' => [
                    't2_international' => 'Exit T2 arrivals, cross inner drop-off road to the parking lot pickup area.',
                    't1_domestic' => 'Exit T1 arrivals, walk 30m to the right side of the curbside canopy.',
                    'warning' => 'Grab availability can be sparse late at night at Cam Ranh; official metered fixed-rate taxis are reliable alternatives.',
                ],
                'public_bus' => [
                    'name' => 'Dat Moi Airport Shuttle Bus (Bus 18)',
                    'fare' => '60,000 VND (~$2.40 USD) per passenger',
                    'route' => 'Cam Ranh Airport → Yersin Stadium / Nha Trang Center.',
                    'frequency' => 'Every 30 mins matching flight arrivals from 05:30 to 21:30.',
                ],
                'zones' => [
                    [
                        'name' => 'Nha Trang Center / Tran Phu Beach',
                        'dist' => '35 km',
                        'time' => '40–50 min',
                        'grab_fare' => '320,000 – 380,000 VND ($13 – $15 USD)',
                        'taxi_fare' => '300,000 – 350,000 VND ($12 – $14 USD fixed)',
                        'bus_fare' => 'Dat Moi Bus 18: 60,000 VND ($2.40 USD)',
                        'best_choice' => 'Fixed-rate Taxi or Bus 18',
                    ],
                    [
                        'name' => 'Bai Dai Resort Strip (Cam Ranh Beach)',
                        'dist' => '5–10 km',
                        'time' => '10–15 min',
                        'grab_fare' => '90,000 – 140,000 VND ($3.50 – $5.50 USD)',
                        'taxi_fare' => '100,000 – 150,000 VND ($4 – $6 USD)',
                        'bus_fare' => 'Hotel Shuttle / Taxi',
                        'best_choice' => 'Complimentary Resort Shuttle / Taxi',
                    ],
                ],
            ],

            'PQC' => [
                'code' => 'PQC',
                'name' => 'Phu Quoc International Airport',
                'city' => 'Phu Quoc Island',
                'badge' => 'Island Gateway',
                'terminals' => 'Single unified terminal for both international and domestic flights.',
                'distance_city' => '10 km to Duong Dong town center; 18 km to Sunset Town (An Thoi); 32 km to Grand World',
                'travel_time' => '15 mins to Duong Dong; 25 mins to An Thoi; 45 mins to North Island',
                'toll_fee' => '10,000 VND (~$0.40 USD) statutory airport exit toll',
                'official_taxi' => [
                    'brand' => 'Phu Quoc Taxi & Mai Linh Taxi & Vinasun',
                    'phones' => 'Mai Linh: 0297.38.38.38.38 | Vinasun: 0297.38.27.27.27',
                    'location' => 'Curbside directly outside the main arrival doors.',
                    'meter_rate' => '~15,000 – 18,000 VND / km',
                ],
                'grab_pickup' => [
                    't2_international' => 'Curbside directly outside the arrival doors across the pedestrian walkway.',
                    't1_domestic' => 'Same unified curb zone.',
                    'warning' => 'Many Phu Quoc resorts provide FREE airport shuttle pickup if requested 24h before arrival. Check your booking before paying for a taxi!',
                ],
                'public_bus' => [
                    'name' => 'VinBus Electric Shuttle (Free routes)',
                    'fare' => 'Free on select VinBus routes connecting to Grand World / VinWonders.',
                    'route' => 'PQC Airport → Duong Dong Center → Grand World North Island (Lines 17 & 19).',
                    'frequency' => 'Every 20–30 mins, 24/7 operating schedule.',
                ],
                'zones' => [
                    [
                        'name' => 'Duong Dong Town Center / Night Market',
                        'dist' => '10 km',
                        'time' => '15–20 min',
                        'grab_fare' => '140,000 – 190,000 VND ($5.50 – $7.50 USD)',
                        'taxi_fare' => '150,000 – 200,000 VND ($6 – $8 USD)',
                        'bus_fare' => 'VinBus Line 17 (Free) or Taxi',
                        'best_choice' => 'Resort Transfer or GrabCar',
                    ],
                    [
                        'name' => 'Sunset Town / An Thoi (South Island Cable Car)',
                        'dist' => '18 km',
                        'time' => '25–35 min',
                        'grab_fare' => '220,000 – 290,000 VND ($9 – $11.50 USD)',
                        'taxi_fare' => '240,000 – 310,000 VND ($9.50 – $12 USD)',
                        'bus_fare' => 'Taxi or Resort Transfer',
                        'best_choice' => 'GrabCar or Metered Taxi',
                    ],
                    [
                        'name' => 'Grand World / Ganh Dau (North Island)',
                        'dist' => '32 km',
                        'time' => '40–50 min',
                        'grab_fare' => '350,000 – 430,000 VND ($14 – $17 USD)',
                        'taxi_fare' => '380,000 – 460,000 VND ($15 – $18 USD)',
                        'bus_fare' => 'VinBus Line 17 & 19 (100% Free)',
                        'best_choice' => 'VinBus Electric Shuttle (Free!)',
                    ],
                ],
            ],
        ],

        'scams' => [
            [
                'id' => 'grab_cancelled',
                'title' => 'The "Grab Cancelled / I Am Your Driver" Switch',
                'severity' => 'Critical',
                'icon' => '🚨',
                'pattern' => 'A tout approaches you inside the arrivals hall or curbside holding a smartphone with an active Grab screen. They insist: "Your Grab ride was cancelled because of traffic" or "I am your Grab driver, come to my car". They then disable the app or quote 5x the real price upon arrival.',
                'antidote' => 'Never follow anyone inside the terminal. Only board a vehicle whose LICENSE PLATE EXACTLY MATCHES the 6-digit plate displayed on your personal phone screen. If your Grab booking were cancelled, YOUR app would notify you directly.',
            ],
            [
                'id' => 'fast_meter',
                'title' => 'Rigged Fast Meter (Đồng hồ nhảy xung)',
                'severity' => 'High',
                'icon' => '⏱️',
                'pattern' => 'Unofficial or counterfeit taxis (painted like Mai Linh or Vinasun with subtle spelling changes like "Mailinh" or "Vinasum") install an electronic pulse accelerator. The fare jumps 50,000 VND every 300 meters.',
                'antidote' => 'Only use the official uniformed dispatcher booths outside the terminal. Ensure the driver turns on the dashboard meter (starting around 10,000–20,000 VND) and that the driver ID card with photo is clearly visible on the dashboard.',
            ],
            [
                'id' => 'note_switch',
                'title' => 'The 500,000 VND vs 20,000 VND Color Switch',
                'severity' => 'High',
                'icon' => '💵',
                'pattern' => 'The 500,000 VND note (highest denomination, ~$20 USD) and the 20,000 VND note (~$0.80 USD) share an identical cyan/blue color palette. In dim vehicle lighting, the driver takes your 500k note, quickly drops it between the seats, flashes a 20k note, and angrily demands: "You only gave me 20k!"',
                'antidote' => 'Always separate your notes beforehand. When paying cash, hold the note up, look the driver in the eye, and clearly announce: "Here is five hundred thousand VND" before handing it over.',
            ],
            [
                'id' => 'hotel_closed',
                'title' => '"Your Hotel Is Closed / On Fire" Diversion',
                'severity' => 'Medium',
                'icon' => '🏨',
                'pattern' => 'During transit from the airport, the driver claims your booked hotel is closed for government inspection, undergoing noisy construction, or burned down yesterday. They offer to take you to a "much better, cheaper" hotel where they pocket a 40% kickback.',
                'antidote' => 'Never accept route deviations. Insist firmly: "Take me to my address, my friend is already waiting for me at reception." Call your hotel reception directly if in doubt.',
            ],
            [
                'id' => 'toll_extortion',
                'title' => 'Inflated Airport Toll Fee (Phí ra cổng)',
                'severity' => 'Medium',
                'icon' => '🚧',
                'pattern' => 'At the airport exit toll booth, the driver demands 100,000 – 200,000 VND cash from you, claiming it is an "international airport passenger tax".',
                'antidote' => 'The official statutory airport gate toll across all Vietnamese airports is strictly 10,000 – 15,000 VND (~$0.40 – $0.60 USD). On Grab, this toll is automatically added into your digital receipt.',
            ],
        ],

        'essentials' => [
            'currency' => [
                'title' => 'Airport Currency Exchange Reality',
                'rule' => 'Change only $20 – $50 USD at airport booths for immediate bus or cash needs.',
                'detail' => 'Airport bank kiosks (Vietcombank, BIDV, Eximbank) offer fair but slightly wider spreads (2–3% lower). Wait until city center jewelry shops (Phố Hà Trung in Hanoi Old Quarter, Chợ Bến Thành gold shops in HCMC) or official bank branches for the highest market rates.',
                'atm_tip' => 'Airport arrival ATMs (VPBank, TPBank, Vietcombank) dispense maximum 2,000,000 – 5,000,000 VND per withdrawal with foreign card fees of 50,000 – 60,000 VND. Always choose "WITHOUT CONVERSION" on the ATM screen to avoid dynamic currency conversion (DCC) markups.',
            ],
            'sim' => [
                'title' => 'Airport SIM vs eSIM Strategy',
                'rule' => 'Pre-purchase an eSIM online or buy only at official Viettel / Vinaphone airport kiosks.',
                'detail' => 'Ensure the kiosk attendant registers the SIM card with your physical passport scan before you leave the counter. If the SIM is not registered under Decree 49/2017/ND-CP, the telecom provider will deactivate the data service within 48 to 72 hours.',
            ],
        ],
    ];
}

/**
 * Renders the Interactive Airport Transit & Scam Shield Navigator component HTML.
 *
 * Enforces zero H2 tags to prevent TOC pollution.
 *
 * @return string
 */
function vg_render_airport_navigator_html(): string
{
    $data = vg_get_airport_navigator_dataset();
    $airportsJson = esc_attr(wp_json_encode($data['airports']));

    ob_start();
    ?>
    <section class="vg-airport-navigator" id="airport-navigator" aria-label="<?php esc_attr_e('Vietnam Airport Arrival & Transit Navigator', 'vietnamguide-premium'); ?>" data-airports="<?php echo $airportsJson; ?>">
        <div class="vg-an-header">
            <div class="vg-an-heading-group">
                <span class="vg-an-kicker"><?php esc_html_e('Interactive Arrival Intelligence', 'vietnamguide-premium'); ?></span>
                <div class="vg-an-title" role="heading" aria-level="2"><?php esc_html_e('Vietnam Airport Arrival & Transit Navigator', 'vietnamguide-premium'); ?></div>
                <p class="vg-an-subtitle"><?php esc_html_e('Real-world gateway logistics, verified taxi fares, Grab pickup zones, and scam-prevention protocols for Vietnam’s major international airports.', 'vietnamguide-premium'); ?></p>
            </div>
            <div class="vg-an-header-actions">
                <button type="button" class="vg-an-btn vg-an-btn-secondary vg-an-copy-btn" id="vg-an-copy-cheat-sheet" aria-label="<?php esc_attr_e('Copy arrival cheat sheet to clipboard', 'vietnamguide-premium'); ?>">
                    <span class="vg-an-btn-icon">📋</span>
                    <span class="vg-an-btn-text"><?php esc_html_e('Copy Arrival Sheet', 'vietnamguide-premium'); ?></span>
                </button>
                <button type="button" class="vg-an-btn vg-an-btn-secondary vg-an-print-btn" onclick="window.print()" aria-label="<?php esc_attr_e('Print arrival guide', 'vietnamguide-premium'); ?>">
                    <span class="vg-an-btn-icon">🖨️</span>
                    <span class="vg-an-btn-text"><?php esc_html_e('Print Sheet', 'vietnamguide-premium'); ?></span>
                </button>
            </div>
        </div>

        <!-- Airport Selector Chips -->
        <div class="vg-an-airports-bar" role="tablist" aria-label="<?php esc_attr_e('Select Arrival Airport', 'vietnamguide-premium'); ?>">
            <span class="vg-an-bar-label"><?php esc_html_e('Select Airport:', 'vietnamguide-premium'); ?></span>
            <div class="vg-an-chips-wrapper">
                <?php
                $first = true;
                foreach ($data['airports'] as $code => $airport) :
                    $activeClass = $first ? ' active' : '';
                    $ariaSelected = $first ? 'true' : 'false';
                    $first = false;
                ?>
                    <button type="button" class="vg-an-chip<?php echo $activeClass; ?>" role="tab" aria-selected="<?php echo $ariaSelected; ?>" data-airport="<?php echo esc_attr($code); ?>" id="vg-an-chip-<?php echo esc_attr($code); ?>">
                        <span class="vg-an-chip-code"><?php echo esc_html($code); ?></span>
                        <span class="vg-an-chip-city"><?php echo esc_html($airport['city']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Active Airport Quick Banner -->
        <div class="vg-an-airport-banner" id="vg-an-airport-banner">
            <div class="vg-an-banner-info">
                <div class="vg-an-banner-top">
                    <span class="vg-an-banner-code" id="vg-an-b-code">HAN</span>
                    <span class="vg-an-banner-name" id="vg-an-b-name">Noi Bai International Airport</span>
                    <span class="vg-an-badge" id="vg-an-b-badge">North Gateway</span>
                </div>
                <div class="vg-an-banner-meta">
                    <span class="vg-an-meta-item">📍 <strong id="vg-an-b-dist">28–32 km to Hanoi Old Quarter</strong></span>
                    <span class="vg-an-meta-item">⏱️ <strong id="vg-an-b-time">40–55 mins</strong></span>
                    <span class="vg-an-meta-item">🚧 Toll: <strong id="vg-an-b-toll">15,000 VND</strong></span>
                </div>
                <div class="vg-an-terminal-note" id="vg-an-b-terminals">
                    T1 (Domestic) & T2 (International) — Free shuttle bus runs between T1 & T2 every 15–20 mins.
                </div>
            </div>
        </div>

        <!-- Module Navigation Tabs -->
        <div class="vg-an-tabs" role="tablist" aria-label="<?php esc_attr_e('Navigator Sections', 'vietnamguide-premium'); ?>">
            <button type="button" class="vg-an-tab active" role="tab" aria-selected="true" data-tab="fare-calc" id="vg-tab-fare-calc">
                <span class="vg-tab-icon">🚗</span>
                <span class="vg-tab-text"><?php esc_html_e('Fair Fares & Options', 'vietnamguide-premium'); ?></span>
            </button>
            <button type="button" class="vg-an-tab" role="tab" aria-selected="false" data-tab="gate-nav" id="vg-tab-gate-nav">
                <span class="vg-tab-icon">🚶</span>
                <span class="vg-tab-text"><?php esc_html_e('Gate-to-Curb Navigator', 'vietnamguide-premium'); ?></span>
            </button>
            <button type="button" class="vg-an-tab" role="tab" aria-selected="false" data-tab="scam-shield" id="vg-tab-scam-shield">
                <span class="vg-tab-icon">🛡️</span>
                <span class="vg-tab-text"><?php esc_html_e('Scam Shield & Red Flags', 'vietnamguide-premium'); ?></span>
            </button>
            <button type="button" class="vg-an-tab" role="tab" aria-selected="false" data-tab="money-sim" id="vg-tab-money-sim">
                <span class="vg-tab-icon">💱</span>
                <span class="vg-tab-text"><?php esc_html_e('Cash & SIM Reality', 'vietnamguide-premium'); ?></span>
            </button>
        </div>

        <!-- Tab 1: Fair Fares & Options -->
        <div class="vg-an-panel active" id="vg-panel-fare-calc" role="tabpanel" aria-labelledby="vg-tab-fare-calc">
            <div class="vg-an-zone-selector-bar">
                <label for="vg-an-zone-select" class="vg-an-zone-label"><?php esc_html_e('Select Destination District / Zone:', 'vietnamguide-premium'); ?></label>
                <select id="vg-an-zone-select" class="vg-an-select" aria-label="<?php esc_attr_e('Destination zone', 'vietnamguide-premium'); ?>">
                    <!-- Populated dynamically via JS -->
                </select>
            </div>

            <div class="vg-an-fares-grid" id="vg-an-fares-grid">
                <!-- Card 1: GrabCar -->
                <div class="vg-an-fare-card vg-an-card-grab">
                    <div class="vg-an-card-header">
                        <span class="vg-an-card-badge">Digital App</span>
                        <span class="vg-an-card-title">GrabCar (4–7 Seats)</span>
                    </div>
                    <div class="vg-an-card-price" id="vg-an-price-grab">280,000 – 350,000 VND</div>
                    <div class="vg-an-card-usd" id="vg-an-usd-grab">~$11 – $14 USD</div>
                    <ul class="vg-an-card-features">
                        <li>Fixed upfront price locked on app before booking</li>
                        <li>Toll fee automatically included in digital receipt</li>
                        <li>Cashless payment via credit card (Visa/Mastercard)</li>
                    </ul>
                    <div class="vg-an-card-verdict">Best for: Transparent pricing & solo / couple travelers</div>
                </div>

                <!-- Card 2: Official Metered Taxi -->
                <div class="vg-an-fare-card vg-an-card-taxi">
                    <div class="vg-an-card-header">
                        <span class="vg-an-card-badge">Metered / Dispatch</span>
                        <span class="vg-an-card-title" id="vg-an-brand-taxi">Mai Linh / Vinasun</span>
                    </div>
                    <div class="vg-an-card-price" id="vg-an-price-taxi">320,000 – 380,000 VND</div>
                    <div class="vg-an-card-usd" id="vg-an-usd-taxi">~$13 – $15 USD</div>
                    <ul class="vg-an-card-features">
                        <li>Queue at official curbside dispatcher booth</li>
                        <li>Always confirm meter is activated at flag fall</li>
                        <li>Driver phone: <strong id="vg-an-phones-taxi">024.38.38.38.38</strong></li>
                    </ul>
                    <div class="vg-an-card-verdict">Best for: No active data SIM upon landing</div>
                </div>

                <!-- Card 3: Express Public Bus -->
                <div class="vg-an-fare-card vg-an-card-bus">
                    <div class="vg-an-card-header">
                        <span class="vg-an-card-badge">Public Transit</span>
                        <span class="vg-an-card-title" id="vg-an-name-bus">Express Bus 86</span>
                    </div>
                    <div class="vg-an-card-price" id="vg-an-price-bus">45,000 VND</div>
                    <div class="vg-an-card-usd" id="vg-an-usd-bus">~$1.80 USD / passenger</div>
                    <ul class="vg-an-card-features">
                        <li id="vg-an-route-bus">Stops at Old Quarter & Central Railway Station</li>
                        <li>Departs every 30–45 mins outside T1/T2</li>
                        <li>Spacious luggage racks, air conditioned</li>
                    </ul>
                    <div class="vg-an-card-verdict">Best for: Budget travelers with rolling luggage</div>
                </div>
            </div>

            <!-- Grab Pickup Location Callout -->
            <div class="vg-an-pickup-callout" id="vg-an-pickup-callout">
                <div class="vg-an-callout-icon">📍</div>
                <div class="vg-an-callout-content">
                    <strong class="vg-an-callout-title"><?php esc_html_e('Exact Grab Pickup Point:', 'vietnamguide-premium'); ?></strong>
                    <p id="vg-an-pickup-text"><?php esc_html_e('Exit T2 ground floor arrivals, cross pedestrian crosswalk to Outer Island Lane 2 / Lane 3 (between Columns 10 and 14).', 'vietnamguide-premium'); ?></p>
                    <div class="vg-an-callout-alert" id="vg-an-pickup-warning">
                        <?php esc_html_e('⚠️ Never accept rides from individuals inside the terminal hall shouting "Grab, Grab". Genuine drivers must remain with their vehicles.', 'vietnamguide-premium'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Gate-to-Curb Step Navigator -->
        <div class="vg-an-panel" id="vg-panel-gate-nav" role="tabpanel" aria-labelledby="vg-tab-gate-nav">
            <div class="vg-an-steps-container">
                <div class="vg-an-step-item">
                    <div class="vg-an-step-marker">1</div>
                    <div class="vg-an-step-content">
                        <div class="vg-an-step-title"><?php esc_html_e('Immigration Clearance & Baggage Reclaim', 'vietnamguide-premium'); ?></div>
                        <p><?php esc_html_e('Present passport, printed E-visa, and boarding pass at Immigration. Once cleared, head down to the baggage carousels. Verify your baggage claim tag before exiting.', 'vietnamguide-premium'); ?></p>
                    </div>
                </div>

                <div class="vg-an-step-item">
                    <div class="vg-an-step-marker">2</div>
                    <div class="vg-an-step-content">
                        <div class="vg-an-step-title"><?php esc_html_e('Customs & Exit Door Protocol', 'vietnamguide-premium'); ?></div>
                        <p><?php esc_html_e('Walk through Green Customs channel (nothing to declare). As sliding doors open into the public arrivals hall, you will see money changers, SIM kiosks, and crowds of greeters.', 'vietnamguide-premium'); ?></p>
                    </div>
                </div>

                <div class="vg-an-step-item">
                    <div class="vg-an-step-marker">3</div>
                    <div class="vg-an-step-content">
                        <div class="vg-an-step-title"><?php esc_html_e('Essential Needs: SIM & Small Cash (2 Minutes)', 'vietnamguide-premium'); ?></div>
                        <p><?php esc_html_e('If you do not have an active eSIM, visit an official Viettel or Vinaphone desk. Withdraw 1,000,000–2,000,000 VND from an ATM or exchange $30–$50 USD for immediate cash.', 'vietnamguide-premium'); ?></p>
                    </div>
                </div>

                <div class="vg-an-step-item">
                    <div class="vg-an-step-marker">4</div>
                    <div class="vg-an-step-content">
                        <div class="vg-an-step-title"><?php esc_html_e('Connecting With Your Vehicle Outside', 'vietnamguide-premium'); ?></div>
                        <p id="vg-an-step4-detail"><?php esc_html_e('Do not stop for greeters inside. Walk completely outside into the fresh air. For Grab: cross to Lane 2/3. For metered taxi: turn left to the official queue.', 'vietnamguide-premium'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Scam Shield & Red Flags -->
        <div class="vg-an-panel" id="vg-panel-scam-shield" role="tabpanel" aria-labelledby="vg-tab-scam-shield">
            <div class="vg-an-scam-intro">
                <p><?php esc_html_e('Vietnam is remarkably safe, but airport arrivals are high-leverage friction points where deceptive operators exploit jetlag. Here are the 5 classic airport taxi traps and the exact antidote for each.', 'vietnamguide-premium'); ?></p>
            </div>

            <div class="vg-an-scams-accordion">
                <?php foreach ($data['scams'] as $index => $scam) : ?>
                    <details class="vg-an-scam-item" id="vg-an-scam-<?php echo esc_attr($scam['id']); ?>" <?php echo $index === 0 ? 'open' : ''; ?>>
                        <summary class="vg-an-scam-summary">
                            <span class="vg-an-scam-icon"><?php echo esc_html($scam['icon']); ?></span>
                            <span class="vg-an-scam-title-text"><?php echo esc_html($scam['title']); ?></span>
                            <span class="vg-an-scam-badge-sev"><?php echo esc_html($scam['severity']); ?></span>
                        </summary>
                        <div class="vg-an-scam-body">
                            <div class="vg-an-scam-pattern">
                                <strong>⚠️ <?php esc_html_e('The Trap:', 'vietnamguide-premium'); ?></strong>
                                <p><?php echo esc_html($scam['pattern']); ?></p>
                            </div>
                            <div class="vg-an-scam-antidote">
                                <strong>🛡️ <?php esc_html_e('The Antidote:', 'vietnamguide-premium'); ?></strong>
                                <p><?php echo esc_html($scam['antidote']); ?></p>
                            </div>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tab 4: Cash & SIM Reality -->
        <div class="vg-an-panel" id="vg-panel-money-sim" role="tabpanel" aria-labelledby="vg-tab-money-sim">
            <div class="vg-an-essentials-grid">
                <div class="vg-an-essential-box">
                    <div class="vg-an-essential-head">
                        <span class="vg-an-essential-icon">💵</span>
                        <div class="vg-an-essential-title" role="heading" aria-level="3"><?php echo esc_html($data['essentials']['currency']['title']); ?></div>
                    </div>
                    <div class="vg-an-essential-rule">
                        <strong>Golden Rule:</strong> <?php echo esc_html($data['essentials']['currency']['rule']); ?>
                    </div>
                    <p><?php echo esc_html($data['essentials']['currency']['detail']); ?></p>
                    <div class="vg-an-essential-tip">
                        💡 <?php echo esc_html($data['essentials']['currency']['atm_tip']); ?>
                    </div>
                </div>

                <div class="vg-an-essential-box">
                    <div class="vg-an-essential-head">
                        <span class="vg-an-essential-icon">📱</span>
                        <div class="vg-an-essential-title" role="heading" aria-level="3"><?php echo esc_html($data['essentials']['sim']['title']); ?></div>
                    </div>
                    <div class="vg-an-essential-rule">
                        <strong>Golden Rule:</strong> <?php echo esc_html($data['essentials']['sim']['rule']); ?>
                    </div>
                    <p><?php echo esc_html($data['essentials']['sim']['detail']); ?></p>
                    <div class="vg-an-essential-tip">
                        💡 <?php esc_html_e('Viettel has the strongest coverage across remote mountainous regions (Ha Giang, Pu Luong, Sapa). Vinaphone is optimal for major metropolitan cities and island resorts.', 'vietnamguide-premium'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cross-Tool Synergy & Next Steps Bridge -->
        <div class="vg-an-next-steps">
            <span class="vg-an-steps-kicker"><?php esc_html_e('Continue Planning Your Journey', 'vietnamguide-premium'); ?></span>
            <div class="vg-an-steps-grid">
                <a href="<?php echo esc_url(home_url('/plan/vietnam-evisa/')); ?>" class="vg-an-step-link">
                    <span class="vg-an-step-icon">🛂</span>
                    <span class="vg-an-step-title"><?php esc_html_e('Visa Exemption & E-Visa Checker', 'vietnamguide-premium'); ?></span>
                    <span class="vg-an-step-desc"><?php esc_html_e('Verify entry rules & avoid middleman fees', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/costs/vietnam-travel-cost/')); ?>" class="vg-an-step-link">
                    <span class="vg-an-step-icon">💰</span>
                    <span class="vg-an-step-title"><?php esc_html_e('Travel Cost Calculator', 'vietnamguide-premium'); ?></span>
                    <span class="vg-an-step-desc"><?php esc_html_e('Calculate realistic daily travel budgets', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/best-time-to-visit-vietnam/')); ?>" class="vg-an-step-link">
                    <span class="vg-an-step-icon">🌤️</span>
                    <span class="vg-an-step-title"><?php esc_html_e('Regional Weather & Packing Matrix', 'vietnamguide-premium'); ?></span>
                    <span class="vg-an-step-desc"><?php esc_html_e('Check 3-climate monsoon patterns', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/itineraries/')); ?>" class="vg-an-step-link">
                    <span class="vg-an-step-icon">🗺️</span>
                    <span class="vg-an-step-title"><?php esc_html_e('Interactive Itinerary Finder', 'vietnamguide-premium'); ?></span>
                    <span class="vg-an-step-desc"><?php esc_html_e('Filter tested routes from 7 to 21 days', 'vietnamguide-premium'); ?></span>
                </a>
            </div>
        </div>
    </section>

    <!-- Vanilla Client-Side Controller -->
    <script>
    (function() {
        var root = document.getElementById('airport-navigator');
        if (!root) return;

        var airportsData = {};
        try {
            airportsData = JSON.parse(root.getAttribute('data-airports') || '{}');
        } catch(e) {
            return;
        }

        var currentAirport = 'HAN';

        // Elements
        var chips = root.querySelectorAll('.vg-an-chip');
        var tabs = root.querySelectorAll('.vg-an-tab');
        var panels = root.querySelectorAll('.vg-an-panel');
        var zoneSelect = document.getElementById('vg-an-zone-select');

        // Banner elements
        var bCode = document.getElementById('vg-an-b-code');
        var bName = document.getElementById('vg-an-b-name');
        var bBadge = document.getElementById('vg-an-b-badge');
        var bDist = document.getElementById('vg-an-b-dist');
        var bTime = document.getElementById('vg-an-b-time');
        var bToll = document.getElementById('vg-an-b-toll');
        var bTerminals = document.getElementById('vg-an-b-terminals');

        // Card elements
        var priceGrab = document.getElementById('vg-an-price-grab');
        var usdGrab = document.getElementById('vg-an-usd-grab');
        var brandTaxi = document.getElementById('vg-an-brand-taxi');
        var priceTaxi = document.getElementById('vg-an-price-taxi');
        var usdTaxi = document.getElementById('vg-an-usd-taxi');
        var phonesTaxi = document.getElementById('vg-an-phones-taxi');
        var nameBus = document.getElementById('vg-an-name-bus');
        var priceBus = document.getElementById('vg-an-price-bus');
        var usdBus = document.getElementById('vg-an-usd-bus');
        var routeBus = document.getElementById('vg-an-route-bus');
        var pickupText = document.getElementById('vg-an-pickup-text');
        var pickupWarning = document.getElementById('vg-an-pickup-warning');
        var step4Detail = document.getElementById('vg-an-step4-detail');

        function updateAirport(code) {
            var data = airportsData[code];
            if (!data) return;
            currentAirport = code;

            // Update chips
            chips.forEach(function(c) {
                var match = c.getAttribute('data-airport') === code;
                c.classList.toggle('active', match);
                c.setAttribute('aria-selected', match ? 'true' : 'false');
            });

            // Update banner
            if (bCode) bCode.textContent = data.code;
            if (bName) bName.textContent = data.name;
            if (bBadge) bBadge.textContent = data.badge;
            if (bDist) bDist.textContent = data.distance_city;
            if (bTime) bTime.textContent = data.travel_time;
            if (bToll) bToll.textContent = data.toll_fee;
            if (bTerminals) bTerminals.textContent = data.terminals;

            // Update taxi & bus labels
            if (brandTaxi) brandTaxi.textContent = data.official_taxi.brand;
            if (phonesTaxi) phonesTaxi.textContent = data.official_taxi.phones;
            if (nameBus) nameBus.textContent = data.public_bus.name;
            if (priceBus) priceBus.textContent = data.public_bus.fare;
            if (usdBus) usdBus.textContent = data.public_bus.fare.indexOf('Free') !== -1 ? 'Free Shuttle' : '~' + data.public_bus.fare;
            if (routeBus) routeBus.textContent = data.public_bus.route;

            // Update pickup info
            if (pickupText) pickupText.textContent = data.grab_pickup.t2_international;
            if (pickupWarning) pickupWarning.textContent = '⚠️ ' + data.grab_pickup.warning;
            if (step4Detail) step4Detail.textContent = data.grab_pickup.t2_international + ' Official Taxi: ' + data.official_taxi.location;

            // Update zone select
            if (zoneSelect) {
                zoneSelect.innerHTML = '';
                data.zones.forEach(function(zone, idx) {
                    var opt = document.createElement('option');
                    opt.value = idx;
                    opt.textContent = zone.name + ' (' + zone.dist + ' — ' + zone.time + ')';
                    zoneSelect.appendChild(opt);
                });
                updateZone(0);
            }
        }

        function updateZone(idx) {
            var data = airportsData[currentAirport];
            if (!data || !data.zones[idx]) return;
            var z = data.zones[idx];

            if (priceGrab) priceGrab.textContent = z.grab_fare;
            if (usdGrab) {
                var usdMatch = z.grab_fare.match(/\(([^)]+)\)/);
                usdGrab.textContent = usdMatch ? usdMatch[1] : '';
            }

            if (priceTaxi) priceTaxi.textContent = z.taxi_fare;
            if (usdTaxi) {
                var usdTaxiMatch = z.taxi_fare.match(/\(([^)]+)\)/);
                usdTaxi.textContent = usdTaxiMatch ? usdTaxiMatch[1] : '';
            }
        }

        // Chip click events
        chips.forEach(function(chip) {
            chip.addEventListener('click', function() {
                var code = this.getAttribute('data-airport');
                updateAirport(code);
            });
        });

        // Zone change event
        if (zoneSelect) {
            zoneSelect.addEventListener('change', function() {
                updateZone(parseInt(this.value, 10) || 0);
            });
        }

        // Tab click events
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var tabId = this.getAttribute('data-tab');

                tabs.forEach(function(t) {
                    var match = t === tab;
                    t.classList.toggle('active', match);
                    t.setAttribute('aria-selected', match ? 'true' : 'false');
                });

                panels.forEach(function(p) {
                    var match = p.id === 'vg-panel-' + tabId;
                    p.classList.toggle('active', match);
                });
            });
        });

        // Copy cheat sheet to clipboard
        var copyBtn = document.getElementById('vg-an-copy-cheat-sheet');
        if (copyBtn) {
            copyBtn.addEventListener('click', function() {
                var d = airportsData[currentAirport];
                if (!d) return;

                var text = '✈️ Vietnam Airport Arrival Cheat Sheet — ' + d.code + ' (' + d.city + ')\n' +
                           '• Terminal: ' + d.terminals + '\n' +
                           '• Grab Pickup: ' + d.grab_pickup.t2_international + '\n' +
                           '• Warning: ' + d.grab_pickup.warning + '\n' +
                           '• Official Taxi: ' + d.official_taxi.brand + ' (' + d.official_taxi.phones + ')\n' +
                           '• Statutory Airport Exit Toll: ' + d.toll_fee + '\n' +
                           '• Express Bus: ' + d.public_bus.name + ' (' + d.public_bus.fare + ')\n' +
                           '• Source: VietnamGuide.net Practical Concierge';

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function() {
                        var originalHtml = copyBtn.innerHTML;
                        copyBtn.innerHTML = '<span>✅</span> <span>Copied!</span>';
                        setTimeout(function() { copyBtn.innerHTML = originalHtml; }, 2000);
                    });
                } else {
                    prompt('Copy your airport arrival cheat sheet:', text);
                }
            });
        }

        // Initialize default
        updateAirport('HAN');
    })();
    </script>
    <?php
    return (string) ob_get_clean();
}

/**
 * Register shortcode [vg_airport_navigator].
 */
add_shortcode('vg_airport_navigator', 'vg_render_airport_navigator_html');

/**
 * Injects the Airport Navigator automatically on relevant travel planning guides.
 *
 * Enforces hero group guard and post ID idempotence guard to protect single-pass rendering.
 *
 * @param string $content
 * @return string
 */
function vg_inject_airport_navigator_on_page(string $content): string
{
    if (! is_singular() && ! is_page()) {
        return $content;
    }

    // Never inject into hero block
    if (strpos($content, 'vg-guide-hero') !== false) {
        return $content;
    }

    static $injectedPosts = [];
    $postId = get_the_ID();
    if ($postId && isset($injectedPosts[$postId])) {
        return $content;
    }

    $isTargetPage = is_page('vietnam-airport-arrival-checklist')
        || is_page('plan/vietnam-airport-arrival-checklist')
        || (is_singular('page') && get_post_field('post_name') === 'vietnam-airport-arrival-checklist')
        || is_page('transport-within-vietnam')
        || is_page('plan/transport-within-vietnam')
        || (is_singular('page') && get_post_field('post_name') === 'transport-within-vietnam')
        || is_page('safety-and-scams-in-vietnam')
        || is_page('plan/safety-and-scams-in-vietnam')
        || (is_singular('page') && get_post_field('post_name') === 'safety-and-scams-in-vietnam')
        || is_page('vietnam-first-trip-planning-checklist')
        || is_page('plan/vietnam-first-trip-planning-checklist')
        || (is_singular('page') && get_post_field('post_name') === 'vietnam-first-trip-planning-checklist');

    if (! $isTargetPage) {
        return $content;
    }

    if (has_shortcode($content, 'vg_airport_navigator')) {
        return $content;
    }

    if ($postId) {
        $injectedPosts[$postId] = true;
    }

    $navigatorHtml = vg_render_airport_navigator_html();

    return $navigatorHtml . "\n\n" . $content;
}
add_filter('the_content', 'vg_inject_airport_navigator_on_page', 20);

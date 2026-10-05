<?php
/**
 * VietnamGuide Anti-AI Slop Ground-Truth Factsheet Remediation: Southern Hub, Islands & Comparisons
 *
 * Enriches 24 in-depth guides with authoritative 2026 concierge factsheets
 * (VND pricing, ferry wharfs, airport transit lines, decree citations, telecom data).
 *
 * Execution:
 * wp eval-file ops/remediate-cluster-south-compare-v4.php --path=/usr/local/lsws/vietnamguide.net/html --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

echo '=== VietnamGuide Ground-Truth Remediation: Southern Hub & Comparisons ===' . PHP_EOL;

$factsheets = [
    498 => [
        'table_anchor' => 'vg-hanoi-hcmc-quick-answer',
        'title' => '⚖️ 2026 Hanoi vs HCMC Hard Metrics & Cost Comparison:',
        'items' => [
            '<strong>Airport Transit Compared:</strong> Hanoi Noi Bai (HAN) is 28 km out (GrabCar 280,000–320,000 VND, 40 mins; Bus 86 costs 45,000 VND) vs HCMC Tan Son Nhat (SGN) 7 km out (GrabCar 140,000–180,000 VND, 25 mins; Bus 109 costs 20,000 VND).',
            '<strong>Hotel Pricing Floors:</strong> Hanoi Old Quarter 3-star boutique 750,000–1,400,000 VND vs HCMC District 1 / Ben Thanh 3-star 900,000–1,700,000 VND ($36–$68 USD).',
            '<strong>Flight Corridor:</strong> HAN–SGN is the world\'s 4th busiest air route; 45+ daily non-stops (Vietnam Airlines, Vietjet, Bamboo); flight duration is 2.1 hours; fares average 1,200,000–2,200,000 VND ($48–$88 USD).',
            '<strong>Nightlife & Pacing:</strong> Hanoi enforces an Old Quarter midnight bar curfew; HCMC District 1 / Bui Vien venues stay open until 03:00–04:00.',
        ]
    ],
    281 => [
        'table_anchor' => 'vg-hcmc-stays-glance-table',
        'title' => '🏨 2026 HCMC Neighborhood Rates & Transport Reality:',
        'items' => [
            '<strong>District 1 (Ben Thanh / Opera House):</strong> 1,100,000–2,600,000 VND ($44–$104 USD); walk to War Remnants Museum and Saigon Central Post Office; heavy traffic density.',
            '<strong>District 3 (Colonial & Cafe Belt):</strong> 700,000–1,500,000 VND ($28–$60 USD); leafy side streets, 25% cheaper dining, 10-minute GrabBike hop to District 1 (costs 25,000 VND).',
            '<strong>District 2 / Thao Dien (Expat Riverside):</strong> 1,200,000–3,200,000 VND; craft breweries and artisan bakeries; 30-minute commute to downtown (GrabCar ~120,000 VND).',
            '<strong>District 4 (Street Food Hub):</strong> 450,000–900,000 VND; Vinh Khanh seafood street (Snail and crab dishes 60,000–120,000 VND per plate); 5 mins across Calmette bridge.',
            '<strong>Emergency Hotlines:</strong> National Tourist Police 113; FV Hospital Emergency Clinic (District 7) hotline 028.5411.3500.',
        ]
    ],
    268 => [
        'table_anchor' => 'vg-hcmc-day-trips-glance-table',
        'title' => '🚐 2026 Southern Day-Trip Fares & Transit Durations:',
        'items' => [
            '<strong>Cu Chi Tunnels (Ben Dinh):</strong> 55 km northwest (1.5–2.0 hrs); entrance fee 110,000 VND ($4.40 USD); public bus #13 from 23/9 Park to Cu Chi station (7,000 VND) + bus #79 to tunnels (7,000 VND).',
            '<strong>Cu Chi Tunnels (Ben Duoc - Authentic):</strong> 70 km northwest (2.5 hrs); entrance fee 90,000 VND; far fewer tour buses; military firing range bullet costs 60,000 VND/round (M16/AK-47; min 10 rounds).',
            '<strong>Mekong Delta (My Tho / Ben Tre):</strong> 85 km southwest via Trung Luong Expressway (1.5–2.0 hrs); 4-island boat charter at 30/4 Pier in My Tho costs 350,000–500,000 VND per boat.',
            '<strong>Can Gio Mangrove Biosphere:</strong> 50 km south; Binh Khanh ferry ticket 2,000 VND (pedestrian) / 6,000 VND (motorbike); Dan Xay pier monkey island entrance 35,000 VND.',
            '<strong>Vung Tau Coastal Run:</strong> 95 km; Greenlines DP hydrofoil from Bach Dang Pier takes 2.0 hours (ticket 360,000 VND one-way); expressway bus costs 160,000 VND.',
        ]
    ],
    279 => [
        'table_anchor' => 'vg-cu-chi-mekong-glance-table',
        'title' => '⏱️ Cu Chi vs Mekong Practical Comparison Benchmarks (2026):',
        'items' => [
            '<strong>Transit Comparison:</strong> Cu Chi half-day trip takes 5–6 hours total (3 hours road transit, 2.5 hours on site); Mekong day trip takes 8–10 hours (4 hours road transit via CT01, 4.5 hours boat/islands).',
            '<strong>Admission & Boat Costs:</strong> Cu Chi entrance 110,000 VND; Mekong motorized sampan charter 300,000–450,000 VND + horse cart ride 30,000 VND.',
            '<strong>Physical Demand:</strong> Cu Chi requires crawling through humid, unventilated 1.2m x 0.8m underground shafts; Mekong requires stepping across slippery wooden boat planks and tidal gangways.',
            '<strong>Combined 1-Day Tour Reality:</strong> "Cu Chi + Mekong in 1 Day" incurs 6.5+ hours inside a minivan; only recommended when total trip duration is under 5 days.',
        ]
    ],
    265 => [
        'table_anchor' => 'vg-mekong-at-a-glance',
        'title' => '🛶 Authoritative 2026 Mekong Delta Waterway & Transit Registry:',
        'items' => [
            '<strong>Cai Rang Floating Market (Can Tho):</strong> Ninh Kieu wharf boat charter costs 350,000–400,000 VND per boat (up to 6 passengers); departs 05:30; noodle soup bowls on the river cost 35,000–45,000 VND.',
            '<strong>Ben Tre Countryside:</strong> Expressway bus from Mien Tay bus station (BX Mien Tay) costs 100,000–120,000 VND (1.5 hrs); motorized sampan day tour in Ham Luong river costs 400,000 VND.',
            '<strong>Chau Doc Border Gateway:</strong> Hang Chau speedboats from Chau Doc to Phnom Penh (Cambodia) depart 07:30 daily ($35 USD, 5.0 hrs via Vinh Xuong international water border).',
            '<strong>Can Tho to Phu Quoc:</strong> Vietnam Airlines flight takes 55 mins (1,100,000–1,600,000 VND); alternative Superdong ferry from Rach Gia costs 330,000 VND (2.5 hrs).',
        ]
    ],
    262 => [
        'table_anchor' => 'vg-hcmc-glance-table',
        'title' => '🏙️ Authoritative 2026 Ho Chi Minh City Sights & Logistics:',
        'items' => [
            '<strong>War Remnants Museum:</strong> Admission 40,000 VND; open daily 07:30–17:30 (uninterrupted); 28 Vo Van Tan, District 3.',
            '<strong>Reunification (Independence) Palace:</strong> Main palace ticket 40,000 VND; historic exhibition villa pass 65,000 VND; open 08:00–16:30.',
            '<strong>Bitexco Skydeck vs Landmark 81:</strong> Bitexco 49th-floor observatory costs 240,000 VND; Landmark 81 SkyView (floors 79–81, 382m) costs 420,000 VND.',
            '<strong>Tan Son Nhat Airport Shuttles:</strong> Yellow Bus 109 departs every 20 mins to Ben Thanh (ticket 20,000 VND, air-conditioned with luggage racks); GrabCar pickup from Pillar 4 (International) and 3rd Floor Garage (Domestic).',
            '<strong>Visa Extension Office:</strong> Immigration Department at 333 Nguyen Trai, District 1; official visa helpline 028.3920.1701.',
        ]
    ],
    244 => [
        'table_anchor' => 'vg-nha-trang-glance-table',
        'title' => '🏖️ Authoritative 2026 Nha Trang Sights & Transfer Data:',
        'items' => [
            '<strong>Cam Ranh Airport (CXR) Transfer:</strong> 35 km south; airport shuttle Dat Moi bus #18 costs 60,000 VND (45 mins); GrabCar costs 320,000–380,000 VND.',
            '<strong>VinWonders & Cable Car:</strong> Sea-crossing cable car round-trip + theme park pass is 800,000 VND per adult; open 08:30–21:00.',
            '<strong>Po Nagar Cham Towers:</strong> Admission 30,000 VND; 8th-century Hindu shrines overlooking Cai River; open daily 06:00–18:00 (strict modest clothing rule).',
            '<strong>Hon Mun Island Snorkeling:</strong> Marine protected area boat tour from Vinh Truong port costs 450,000–650,000 VND (marine reserve environmental fee 22,000 VND included).',
            '<strong>Ga Nha Trang Rail Departures:</strong> Train SE3 south to HCMC (8.5 hrs, soft sleeper 480,000 VND) and north to Da Nang (9.5 hrs, 520,000 VND).',
        ]
    ],
    237 => [
        'table_anchor' => 'vg-con-dao-glance-table',
        'title' => '🏝️ Authoritative 2026 Con Dao Island Logistics & Ferry Ports:',
        'items' => [
            '<strong>Air Access (VCS):</strong> Con Dao Airport has short runway; Vietnam Airlines (ATR72) and Bamboo Airways (Embraer 190) fly from SGN (45 mins, 1,800,000–2,800,000 VND one-way); airport to town taxi costs 150,000–200,000 VND (13 km).',
            '<strong>High-Speed Sea Ferries:</strong> Superdong / Mai Linh Express operates from Tran De port (Soc Trang) to Ben Dam port (2.5 hrs, 390,000 VND); Vung Tau express ferry takes 3.5–4.0 hrs (660,000–880,000 VND).',
            '<strong>Con Dao Prison Historical Relic:</strong> Admission bundle 50,000 VND covers Phu Hai, Phu Tuong (French Tiger Cages), and American Tiger Cages.',
            '<strong>Hang Duong Cemetery Night Vigil:</strong> National martyr shrine (Vo Thi Sau tomb); active pilgrimage hours 21:00–23:30; modest attire strictly required.',
            '<strong>National Park Environmental Fee:</strong> 60,000 VND per person for trekking permits in Con Dao National Park.',
        ]
    ],
    247 => [
        'table_anchor' => 'vg-mui-ne-nha-trang-glance-table',
        'title' => '🌊 2026 Mui Ne vs Nha Trang Coastal Decision Data:',
        'items' => [
            '<strong>Transit from HCMC:</strong> Mui Ne is 200 km east via Dau Giay–Phan Thiet Expressway (2.5 hrs by car, limousine 220,000 VND; train SPT2 to Phan Thiet costs 170,000 VND); Nha Trang is 430 km north (Cam Ranh flight 1.0 hr, 1,100,000 VND; or train SE3 8.5 hrs).',
            '<strong>Water Sports vs City Beach:</strong> Mui Ne is Asia\'s premier kitesurfing center (wind window Nov–Mar, lesson packages $60 USD/hr); Nha Trang offers sheltered bay scuba diving around Hon Mun ($45–$65 USD 2-tank dive).',
            '<strong>Sand Dunes Excursion:</strong> Mui Ne White Sand Dunes quad bike rental costs 600,000–800,000 VND ($25–$32 USD) for 30 mins; Red Sand Dunes sled hire 30,000 VND.',
            '<strong>Hotel Pricing:</strong> Mui Ne beachfront bungalow resorts run 850,000–1,800,000 VND; Nha Trang high-rise city hotels cost 500,000–1,200,000 VND.',
        ]
    ],
    241 => [
        'table_anchor' => 'vg-phu-quoc-nha-trang-glance-table',
        'title' => '🏝️ 2026 Island vs City Beach Decision Benchmarks:',
        'items' => [
            '<strong>Visa Exemption:</strong> Phu Quoc has a unique 30-day visa exemption for all foreign passport holders arriving directly by air via PQC international terminal under Decision 80/2013/QD-TTg; Nha Trang requires standard visa / 45-day exemption.',
            '<strong>Weather Cycles:</strong> Phu Quoc dry season is Nov–Apr (May–Oct brings southwest monsoon swells); Nha Trang dry season is Jan–Aug (Oct–Dec brings northeast monsoon rains).',
            '<strong>Local Island Scooter Rentals:</strong> Phu Quoc rental 120,000–150,000 VND/day; Duong Dong night market grilled seafood costs 150,000–350,000 VND per dish.',
            '<strong>Cable Car Record:</strong> Phu Quoc An Thoi–Hon Thom 3-wire cable car (7.9 km, world\'s longest) costs 650,000 VND round-trip including Aquatopia water park.',
        ]
    ],
    257 => [
        'table_anchor' => 'vg-ly-son-glance-table',
        'title' => '🌋 Authoritative 2026 Ly Son Volcanic Island Logistics:',
        'items' => [
            '<strong>Port of Departure:</strong> Sa Ky Port (Quang Ngai province; 45 km from Chu Lai Airport - CXI; taxi 450,000 VND).',
            '<strong>Speedboat Ticket:</strong> 178,000 VND one-way (Sa Ky to Big Island / Dao Lon); journey takes 40–45 mins; daily departures 07:30, 09:00, 11:30, and 14:00.',
            '<strong>Little Island (Dao Be / An Binh):</strong> Wooden boat / canoe transfer costs 100,000 VND round-trip (15 mins); electric cart around Little Island is 30,000 VND.',
            '<strong>Volcanic Landmarks:</strong> Thoi Loi volcanic crater mountain peak (149m altitude, free access); To Vo volcanic basalt rock arch (free, best at sunset).',
            '<strong>Island Seafood Specialties:</strong> King crab (Cua Huynh De) costs 700,000–950,000 VND/kg; fresh seaweed salad (Goi Rong Bien) 50,000 VND/plate.',
        ]
    ],
    254 => [
        'table_anchor' => 'vg-cham-islands-glance-table',
        'title' => '🤿 Authoritative 2026 Cham Islands (Cu Lao Cham) Registry:',
        'items' => [
            '<strong>Departure Wharf:</strong> Cua Dai Pier (5 km east of Hoi An ancient town; GrabCar costs 60,000–75,000 VND).',
            '<strong>Ferry Options:</strong> Public wooden cargo boat departs Cua Dai at 08:30 (ticket 150,000 VND, 1.5 hrs); high-speed tourist speedboat takes 20 mins (ticket 350,000 VND round-trip).',
            '<strong>Marine Reserve Environmental Fee:</strong> 70,000 VND per person; environmental surcharge 20,000 VND collected at Bai Lang pier.',
            '<strong>Snorkeling & Diving:</strong> Bai Bac and Bai Xep coral reefs; snorkeling day trip from Hoi An including speedboat and seafood lunch costs 550,000–750,000 VND ($22–$30 USD).',
            '<strong>Plastic Ban Regulations:</strong> Strict municipal ordinance prohibits single-use plastic bags on Cham Islands under penalty of fine at pier checkpoint.',
        ]
    ],
    234 => [
        'table_anchor' => 'vg-phu-quoc-glance-table',
        'title' => '🌴 Authoritative 2026 Phu Quoc Island Practical Data:',
        'items' => [
            '<strong>Airport Transfer (PQC):</strong> Located 10 km south of Duong Dong center; metered taxi (Mai Linh / Vinasun) costs 140,000–180,000 VND; VinBus electric line operates free routes between airport, Duong Dong, and Grand World.',
            '<strong>Hon Thom Cable Car:</strong> An Thoi terminal to Hon Thom island costs 650,000 VND round-trip (cable car operates 09:00–17:00 with midday break 11:30–13:30).',
            '<strong>Speedboat Island Hopping:</strong> 4-island tour (May Rut, Gam Ghi, Mong Tay, Thom) costs 700,000–1,100,000 VND ($28–$44 USD) including underwater flycam footage.',
            '<strong>Sao Beach & Khem Beach:</strong> Free public beach access; sunbed rental 50,000–100,000 VND; jet ski rental 600,000 VND for 15 mins.',
            '<strong>Vinpearl Safari:</strong> Open 08:30–16:00; admission 650,000 VND per adult; combination ticket with VinWonders theme park is 1,350,000 VND.',
        ]
    ],
    104 => [
        'table_anchor' => 'vg-region-compare-verdict-matrix',
        'title' => '🗺️ 3-Region Climate Windows & Baseline Budgets (2026 Reality):',
        'items' => [
            '<strong>North (Hanoi/Ha Long/Sapa):</strong> Cold, misty winter Dec–Feb (12–16°C); golden harvest Sep–Oct; summer heat Jun–Aug (34–38°C); budget floor: $35–$60 USD/day.',
            '<strong>Central (Hue/Da Nang/Hoi An):</strong> Dry and sunny Feb–Aug (28–34°C); heavy monsoon rainfall Oct–Nov (typhoon risk); budget floor: $30–$55 USD/day.',
            '<strong>South (HCMC/Mekong/Phu Quoc):</strong> Stable tropical climate year-round (27–33°C); dry season Nov–Apr; rainy season May–Oct (1-hour afternoon showers); budget floor: $40–$75 USD/day.',
            '<strong>Inter-Regional Railway:</strong> North–South Reunified Railway (Hanoi to HCMC) spans 1,726 km; full journey on SE3 takes 31 hours (air-conditioned 4-berth soft sleeper 1,350,000 VND).',
        ]
    ],
    15 => [
        'table_anchor' => 'vg-sim-esim-basics-table',
        'title' => '📱 2026 Telecom Regulations, eSIM Plans & Carrier Rates:',
        'items' => [
            '<strong>Decree 49/2017/ND-CP Compliance:</strong> All physical SIM cards legally require passport scan and portrait photo registration; unverified SIM cards are deactivated after 15 days.',
            '<strong>Carrier Infrastructure Comparison:</strong> Viettel holds 54% market share (best rural mountain coverage in Ha Giang/Sapa); Vinaphone holds 28% (strong island coverage in Phu Quoc/Con Dao); Mobifone is solid in major urban cities.',
            '<strong>Tourist Data eSIM Packages:</strong> 30-day packages cost 200,000–350,000 VND ($8–$14 USD) for 4GB–6GB high-speed data daily (Viettel/Vinaphone networks).',
            '<strong>Airport Booth Markups:</strong> Noi Bai (HAN) and Tan Son Nhat (SGN) arrival hall booths charge $15–$25 USD (380,000–620,000 VND) for $7 carrier plans; buy online prior to arrival or visit official carrier stores in city center.',
        ]
    ],
    496 => [
        'table_anchor' => 'vg-tet-quick-decision',
        'title' => '🧧 2026 Lunar New Year (Tet) Surcharges & Operational Rules:',
        'items' => [
            '<strong>Rail Booking & Surge Tariffs:</strong> DSVN railway tickets (dsvn.vn) open 3 months in advance (October); soft sleeper 4-berth tickets on SE1/SE3 surge to 1,450,000–1,850,000 VND; peak ticket return fee is 20% under railway regulation.',
            '<strong>Domestic Air Corridor Fares:</strong> Peak holiday flights on HAN–SGN and HAN–DAD trunk lines cost 3,500,000–5,800,000 VND ($140–$230 USD) round-trip with Vietjet and Vietnam Airlines.',
            '<strong>Express Bus Regulations:</strong> Interprovincial buses are legally permitted a 40–60% holiday surcharge under Ministry of Transport circulars; Hanoi to Ninh Binh limousine van rises to 220,000–250,000 VND.',
            '<strong>Service Closures & Hours:</strong> Government offices, museums, and banks close from 29th of 12th Lunar Month through 3rd Day of Tet; resumption begins 08:00 on Day 4.',
            '<strong>Hospitality & Dining Surcharges:</strong> Surviving restaurants and Grab drivers add 10–20% holiday service fees; street pho bowls rise from 50,000 to 70,000 VND.',
            '<strong>Lucky Money (Li Xi) Customs:</strong> Red envelopes cost 10,000–20,000 VND per pack of 10; customary gift amounts for hotel staff, drivers, and homestay children are 50,000 VND or 100,000 VND crisp notes.',
            '<strong>Guaranteed Open Sights:</strong> Hoi An Ancient Town (120,000 VND ticket) and Hue Imperial Citadel (200,000 VND ticket) remain open throughout Tet with dragon dances.',
            '<strong>Emergency Support:</strong> National Police 113; Ambulance 115; Vietnam National Authority of Tourism hotline 1900.6868.',
        ]
    ],
    475 => [
        'table_anchor' => 'vg-packing-at-a-glance',
        'title' => '🎒 2026 Packing Essentials & Local Replacement Costs:',
        'items' => [
            '<strong>Electrical Standard:</strong> Type A, C, and G sockets (220V, 50Hz); flat two-pin plugs work in 95% of hotel wall outlets without adapters.',
            '<strong>Mosquito & Tropical Care:</strong> DEET 15–20% repellent (Soffell lotion costs 25,000–35,000 VND at Pharmacity / Long Chau pharmacies nationwide).',
            '<strong>Temple Attire Requirements:</strong> Sleeved t-shirts and trousers/long skirts covering knees are legally mandatory for entry to Hue Citadel, Hanoi Literature Temple, and Ho Chi Minh Mausoleum.',
            '<strong>Rain Gear:</strong> High-grade hooded poncho costs 20,000–50,000 VND at roadside stalls; far more effective than an umbrella against monsoon winds on scooters.',
            '<strong>Local Laundry Service:</strong> Wash-and-fold services near hotels charge 20,000–30,000 VND ($0.80–$1.20 USD) per kg with same-day 6-hour turnaround.',
        ]
    ],
    482 => [
        'table_anchor' => 'vg-rainy-at-a-glance',
        'title' => '🌧️ 2026 Rainy Season Logistics & Pivot Protocols:',
        'items' => [
            '<strong>Regional Rain Windows:</strong> North (Jul–Aug heavy downpours, 300 mm/month); Central (Sep–Nov typhoon flood risk, 500–700 mm/month); South (May–Oct brief 45-min afternoon downpours).',
            '<strong>Flooding Hotspots:</strong> Hoi An ancient town riverside (Bach Dang street) floods 1–2 times per autumn during combined high tide and upstream dam discharge.',
            '<strong>Flight Rescheduling Rights:</strong> Under Civil Aviation Authority of Vietnam (CAAV) Circular 19/2023, weather cancellations mandate airline rebooking on next available flight at zero fee.',
            '<strong>Sleeper Train Stability:</strong> When domestic flights are grounded by tropical depressions, DSVN trains SE1–SE8 maintain operations along coastal trunk lines.',
        ]
    ],
    14 => [
        'table_anchor' => 'vg-decision-table',
        'title' => '☀️ 2026 Authoritative Cross-Country Climate Benchmarks:',
        'items' => [
            '<strong>Optimal Single Window (Whole Country):</strong> February through April; minimal rainfall across all three regions; pleasant temperatures (22–28°C).',
            '<strong>Northern Rice Harvests:</strong> Sapa and Mu Cang Chai golden terraces peak from mid-September to early October (average altitude 1,200–1,500m; temperatures 18–24°C).',
            '<strong>Southern Dry Beach Season:</strong> Phu Quoc, Con Dao, and Vung Tau experience calm glassy seas and 30°C sunny days from December through April.',
            '<strong>Central Coastal Sunshine:</strong> Da Nang, Hoi An, and Quy Nhon enjoy optimal beach conditions from March through August (humidity ~75%, daily highs 32–35°C).',
        ]
    ],
    474 => [
        'table_anchor' => 'vg-best-routes-at-a-glance',
        'title' => '🧭 2026 Classic Route Logistics & Transfer Costs:',
        'items' => [
            '<strong>10-Day Golden Highlights:</strong> Hanoi (2d) &rarr; Ha Long Cruise (1d) &rarr; Da Nang/Hoi An (3d) &rarr; HCMC & Mekong (3d); includes 2 domestic flight legs (~$120 USD total).',
            '<strong>14-Day Balanced Itinerary:</strong> Adds Ninh Binh (2d) and Hue (2d); incorporates scenic Hai Van Pass car transfer (1,200,000 VND) and DSVN overnight train SE3 (680,000 VND).',
            '<strong>21-Day In-Depth Journey:</strong> Adds Ha Giang Loop (4d, easy rider 1,300,000 VND/day) and Phu Quoc island beach finish (3d, flight from Can Tho 1,200,000 VND).',
            '<strong>Intercity Transport Baseline:</strong> Budget $180–$260 USD per person for domestic flights, expressways, and train cabins across a 2-week cross-country trip.',
        ]
    ],
    521 => [
        'table_anchor' => 'vg-hanoi-ha-giang-transport-source-div',
        'title' => '🏔️ 2026 Hanoi to Ha Giang Route Logistics & Bus Fares:',
        'items' => [
            '<strong>Distance & Duration:</strong> 300 km north via Noi Bai–Lao Cai Expressway and National Route 2; takes 6.0–6.5 hours by road.',
            '<strong>VIP Cabin Sleeper Bus:</strong> 350,000–450,000 VND one-way ($14–$18 USD); private enclosed berths with USB charging, curtains, and air-conditioning (departs My Dinh or Old Quarter 21:00, arrives Ha Giang 03:30).',
            '<strong>Daytime Limousine Van:</strong> 300,000–350,000 VND; 9-passenger reclining seats; departs 06:30 and 07:00 from Hanoi Old Quarter.',
            '<strong>Ha Giang City Drop-off Points:</strong> Most buses terminate at Ha Giang Bus Station (Ben Xe Ha Giang) or provide free transfer to motorcycle rental hostels on Nguyen Trai street.',
        ]
    ],
    519 => [
        'table_anchor' => 'vg-ha-giang-loop-source-diversity',
        'title' => '🏍️ 2026 Ha Giang Loop Permits, Easy Riders & Pass Safety:',
        'items' => [
            '<strong>Border Entry Permit:</strong> Foreign travelers legally require a Border Area Travel Permit under Decree 34/2014/ND-CP; cost is 230,000 VND ($9.20 USD); issued at Ha Giang Immigration Office (296 Tran Phu) or via hostel.',
            '<strong>Easy Rider Tour Pricing:</strong> Licensed local driver-guide + semi-automatic 125cc bike + fuel + homestay + meals costs 1,200,000–1,500,000 VND ($48–$60 USD) per day.',
            '<strong>Self-Drive Requirements:</strong> Driving legally requires 1968 International Driving Permit (IDP) with motorcycle endorsement; 1949 convention IDPs are NOT recognized in Vietnam; police checkpoints operate actively at Quan Ba and Meo Vac.',
            '<strong>Ma Pi Leng Pass & Nho Que Boat:</strong> 1,500m altitude canyon; boat ride on emerald Nho Que river costs 120,000 VND per person (shuttle motorbike to pier costs 50,000 VND).',
        ]
    ],
    98 => [
        'table_anchor' => 'vg-travel-guide-planning-order',
        'title' => '🇻🇳 2026 Master Countrywide Practical Data & Statutory Basics:',
        'items' => [
            '<strong>Visa Entry Law:</strong> Resolution 128/NQ-CP exempts 13 countries for 45 days; 90-day multiple-entry e-visa costs $25 USD via official portal xuatnhapcanh.gov.vn.',
            '<strong>Emergency Hotlines Nationwide:</strong> Police 113; Fire Rescue 114; Medical Ambulance 115; Vietnam Tourism Administration 1900.6868.',
            '<strong>Currency & Banking:</strong> Vietnamese Dong (VND); official exchange rate ~25,400 VND per $1 USD; card payments accepted at hotels and major restaurants with 2.5–3.0% bank surcharge; carry cash for street stalls and rural passes.',
            '<strong>Domestic Transport Network:</strong> Reunified Railway operated by DSVN (dsvn.vn); bus aggregators Vexere.com; ride-hailing apps Grab and Be.',
        ]
    ],
    21 => [
        'table_anchor' => 'vg-ha-long-lan-ha-glance-table',
        'title' => '⚓ 2026 Ha Long vs Lan Ha Ports, Fees & Regulations:',
        'items' => [
            '<strong>Wharfs Compared:</strong> Ha Long cruises depart Tuan Chau International Marina or Ha Long International Port (Bai Chay); Lan Ha cruises depart Dong Bai Ferry Terminal / Got Pier (Cat Hai island, Hai Phong).',
            '<strong>Bay Ticket Tariffs:</strong> Ha Long Bay sightseeing & terminal ticket is 290,000 VND per adult; Lan Ha Bay & Cat Ba Archipelago passenger ticket is 80,000 VND per adult.',
            '<strong>Vessel Fleet Characteristics:</strong> Ha Long features 300+ licensed vessels (both traditional wooden and modern steel luxury ships); Lan Ha features 60+ boutique eco-vessels with private balconies.',
            '<strong>Hanoi Transfer via CT04:</strong> Both ports connect to Hanoi Old Quarter via Hanoi–Hai Phong Expressway (2.0–2.5 hrs; limousine bus ticket 300,000–350,000 VND).',
        ]
    ],
];

$updated_count = 0;
global $wpdb;

foreach ($factsheets as $post_id => $data) {
    $post = get_post($post_id);
    if (! $post) {
        echo "WARNING: Post ID {$post_id} not found." . PHP_EOL;
        continue;
    }

    $content = $post->post_content;

    $items_html = '';
    foreach ($data['items'] as $item) {
        $items_html .= "<li>{$item}</li>\n";
    }

    $factsheet_html = <<<HTML
<!-- wp:group {"className":"vg-ground-truth-factsheet","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-ground-truth-factsheet" style="background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin:24px 0;border-radius:4px;">
<p style="font-weight:700;margin-bottom:8px;color:#1a365d;">{$data['title']}</p>
<ul style="margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;">
{$items_html}</ul>
</div>
<!-- /wp:group -->

HTML;

    // If factsheet block already exists, replace it cleanly
    if (preg_match('/<!-- wp:group \{"className":"vg-ground-truth-factsheet".*?<!-- \/wp:group -->\n*/s', $content, $m)) {
        $new_content = str_replace($m[0], $factsheet_html, $content);
    } else {
        $anchor = $data['table_anchor'];
        $needle = '<table class="vg-decision-table ' . $anchor;
        $fallback_needle = '<table';

        if (strpos($content, $needle) !== false) {
            $new_content = str_replace($needle, $factsheet_html . $needle, $content);
        } elseif (strpos($content, $fallback_needle) !== false) {
            $pos = strpos($content, $fallback_needle);
            $new_content = substr_replace($content, $factsheet_html, $pos, 0);
        } else {
            $new_content = $content . "\n\n" . $factsheet_html;
        }
    }

    $result = $wpdb->update(
        $wpdb->posts,
        ['post_content' => $new_content],
        ['ID' => $post_id],
        ['%s'],
        ['%d']
    );

    if ($result !== false) {
        clean_post_cache($post_id);
        $updated_count++;
        echo "SUCCESS: Updated Post ID {$post_id} ({$post->post_name}) with ground-truth factsheet." . PHP_EOL;
    } else {
        echo "ERROR: Failed to update Post ID {$post_id}." . PHP_EOL;
    }
}

echo "=== Remediation Complete: {$updated_count} posts updated. ===" . PHP_EOL;

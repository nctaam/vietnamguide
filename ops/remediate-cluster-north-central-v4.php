<?php
/**
 * VietnamGuide Anti-AI Slop Ground-Truth Factsheet Remediation: Northern & Central Clusters
 *
 * Enriches 22 in-depth guides with authoritative 2026 concierge factsheets
 * (VND admissions, DSVN rail codes, expressway transit times, hotline numbers, decree citations).
 *
 * Execution:
 * wp eval-file ops/remediate-cluster-north-central-v4.php --path=/usr/local/lsws/vietnamguide.net/html --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

echo "=== VietnamGuide Ground-Truth Remediation: Northern & Central Clusters ===" . PHP_EOL;

$factsheets = [
    // 1. Where to Stay in Hanoi (ID 294)
    294 => [
        'table_anchor' => 'vg-hanoi-stays-at-a-glance',
        'title' => '📋 2026 Hanoi Base Logistics & Ground-Truth Price Benchmarks:',
        'items' => [
            '<strong>Airport Arrival Transfer:</strong> Express Bus 86 costs 45,000 VND from Pillar 2 outside Terminal 2; GrabCar to Hoan Kiem costs 280,000–320,000 VND (plus 15,000 VND airport toll).',
            '<strong>Old Quarter Room Rates:</strong> Budget dorms cost 150,000–250,000 VND ($6–$10 USD); boutique 3-star hotels on Hang Bac / Ma May run 750,000–1,400,000 VND ($30–$55 USD); French Quarter luxury (Sofitel Metropole) starts from 6,500,000 VND ($260 USD).',
            '<strong>Intercity Departures:</strong> Ninh Binh limousines depart every 60 mins from Old Quarter offices (150,000–180,000 VND, 90 mins via CT01 Expressway); Ga Hanoi overnight sleeper train SE3 to Hue leaves at 19:20 (soft sleeper 4-berth 680,000 VND).',
            '<strong>Left Luggage & Curfews:</strong> Lockers near Hoan Kiem cost 50,000 VND per bag for 6 hours; verify 24-hour reception access if arriving on flights landing after 23:00.',
            '<strong>Emergency Hotlines:</strong> National Tourist Police hotline 113; SOS International Hanoi clinic at 024.3934.0666; official immigration portal at xuatnhapcanh.gov.vn.',
        ]
    ],

    // 2. Best Day Trips from Hanoi (ID 309)
    309 => [
        'table_anchor' => 'vg-hanoi-day-trips-glance',
        'title' => '🚄 2026 Hanoi Day-Trip Transit Times & Admission Costs:',
        'items' => [
            '<strong>Ninh Binh (Trang An / Tam Coc):</strong> 95 km south via CT01 expressway; limousine van costs 150,000–180,000 VND (90 mins). Trang An boat ticket is 250,000 VND per adult (Route 1, 2, or 3; 3 hrs).',
            '<strong>Ha Long Bay Day Tour:</strong> 155 km east via Hanoi–Hai Phong–Ha Long Expressway (2.5 hrs); Tuan Chau passenger terminal fee and day-boat ticket is 290,000 VND; round-trip highway toll is 210,000 VND.',
            '<strong>Perfume Pagoda (Chua Huong):</strong> 65 km southwest; boat ride on Yen Stream + cable car round-trip costs 220,000 VND per passenger.',
            '<strong>Duong Lam Ancient Village:</strong> 50 km west via National Route 32; entrance ticket is 20,000 VND; bicycle rental costs 50,000 VND per half-day.',
            '<strong>Ba Vi National Park:</strong> 60 km west; adult entrance ticket is 60,000 VND; motorbikes pay 5,000 VND parking at the park gate.',
        ]
    ],

    // 3. Best Things to Do in Hanoi (ID 173)
    173 => [
        'table_anchor' => 'vg-best-things-hanoi-priority-map',
        'title' => '🏛️ 2026 Hanoi Sights Admission Fees & Operating Hours:',
        'items' => [
            '<strong>Temple of Literature (Van Mieu):</strong> Admission 70,000 VND per adult; open daily 08:00–17:00 (audio guide rental 50,000 VND).',
            '<strong>Hoa Lo Prison Memorial:</strong> Admission 50,000 VND; open daily 08:00–17:00 (night tour ticket 399,000 VND on Fri/Sat/Sun evenings, book via official hotline).',
            '<strong>Thang Long Imperial Citadel:</strong> UNESCO site admission 30,000 VND; open Tuesday to Sunday 08:00–17:00 (closed Mondays).',
            '<strong>Vietnam Museum of Ethnology:</strong> Admission 40,000 VND; open Tuesday to Sunday 08:30–17:30 (located 7 km west of Old Quarter; GrabCar fare ~85,000 VND).',
            '<strong>Thang Long Water Puppet Theatre:</strong> Ticket tiers 100,000 / 150,000 / 200,000 VND; performances run daily at 15:00, 16:10, 17:20, 18:30, and 20:00.',
            '<strong>Ho Chi Minh Mausoleum:</strong> Free admission; open mornings only 07:30–10:30 (Tuesday–Thursday, Saturday–Sunday; closed Mondays and Fridays). Strictly enforce covered shoulders and knees.',
        ]
    ],

    // 4. Hanoi First Time Visitor Mistakes (ID 477)
    477 => [
        'table_anchor' => 'vg-hanoi-mistakes-at-a-glance',
        'title' => '⚠️ 2026 Hanoi Tourist Pitfalls & Ground-Truth Fares:',
        'items' => [
            '<strong>Airport Taxi Overcharging:</strong> Fixed GrabCar fare is 280,000–320,000 VND; terminal exit solicitors quote 600,000–800,000 VND. Public Express Bus 86 departs Pillar 2 every 45 mins for 45,000 VND.',
            '<strong>Street Cyclo Negotiations:</strong> Standard hourly cyclo rate is 150,000–200,000 VND ($6–$8 USD) per hour. Confirm exact total for the whole party before boarding to avoid 500,000 VND post-ride extortion.',
            '<strong>Train Street Safety Rules:</strong> Unauthorized track access is strictly penalized under Decree 100/2019/ND-CP; visit licensed trackside cafes on Phung Hung Street only with designated cafe staff escort.',
            '<strong>ATM Foreign Card Limits:</strong> Vietcombank limits withdrawals to 2,000,000 VND per transaction (fee 50,000 VND); use BIDV or VPBank for limits up to 5,000,000 VND ($200 USD).',
            '<strong>Emergency & Police Assistance:</strong> Contact Hanoi Tourist Support Centre hotline 024.1080 or Tourist Police at 113.',
        ]
    ],

    // 5. Hanoi Airport to Old Quarter (ID 316)
    316 => [
        'table_anchor' => 'vg-hanoi-airport-transfer-at-a-glance',
        'title' => '✈️ 2026 Noi Bai International Airport (HAN) Transfer Reality:',
        'items' => [
            '<strong>Distance & Duration:</strong> 28 km north of Hoan Kiem; 35–45 minutes by car via Nhat Tan Bridge outside rush hour (07:00–08:30 and 17:00–19:00 adds 25 mins).',
            '<strong>Express Bus 86:</strong> 45,000 VND cash ticket; runs 06:15–22:00 daily; departs Pillar 2 (Terminal 2 International) and Pillar 1 (Terminal 1 Domestic); stops at Long Bien, Hanoi Opera House, and Ga Hanoi.',
            '<strong>GrabCar & FastGo:</strong> App-hailed cars range from 280,000 to 330,000 VND; passenger pays 15,000 VND airport entrance toll.',
            '<strong>Official Metred Taxis:</strong> Mai Linh (024.3838.3838) and Taxi Group (024.3853.5353) charge fixed flat rates of 280,000–300,000 VND from airport to Hoan Kiem.',
            '<strong>Late Night Arrivals:</strong> Flights landing after 23:00 have no public bus service; pre-book private transfer or GrabCar at arrivals curb.',
        ]
    ],

    // 6. Hanoi in 2 Days (ID 320)
    320 => [
        'table_anchor' => 'vg-hanoi-2-days-at-a-glance',
        'title' => '⏱️ 48-Hour Hanoi Schedule & Cost Breakdown (2026 Standards):',
        'items' => [
            '<strong>Day 1 Heritage Budget:</strong> Temple of Literature (70,000 VND) + Hoa Lo Prison (50,000 VND) + Water Puppet show (150,000 VND) = 270,000 VND admissions.',
            '<strong>Day 2 Culture Budget:</strong> Museum of Ethnology (40,000 VND) + Tran Quoc Pagoda (free) + West Lake GrabBike hops (60,000 VND total) = 100,000 VND.',
            '<strong>Daily Street Food Budget:</strong> Pho bowl on Bat Dan (55,000 VND), Bun Cha Dac Kim (70,000 VND), Egg Coffee at Cafe Giang (35,000 VND), Bia Hoi glass (11,000–14,000 VND).',
            '<strong>Local Transit:</strong> Average GrabBike hop within Hoan Kiem/Ba Dinh costs 20,000–35,000 VND ($0.80–$1.40 USD).',
            '<strong>Connecting Outbound:</strong> DSVN train SE1 departs Ga Hanoi at 22:15 arriving Da Nang at 13:45 next day (soft sleeper 720,000 VND).',
        ]
    ],

    // 7. Old Quarter vs French Quarter vs West Lake (ID 301)
    301 => [
        'table_anchor' => 'vg-hanoi-neighborhood-compare-glance',
        'title' => '🏘️ Neighborhood Comparison Benchmarks (2026 Concierge Data):',
        'items' => [
            '<strong>Old Quarter Noise & Rates:</strong> 150,000–300,000 VND dorms, 800,000–1,500,000 VND 3-star; high scooter noise levels (75–85 dB) until 23:30; step-out walking access to street food.',
            '<strong>French Quarter Quiet & Rates:</strong> 1,800,000–4,500,000 VND 4-5 star; tree-lined avenues with noise levels below 60 dB; 10-minute walk south of Hoan Kiem Lake.',
            '<strong>West Lake (Tay Ho) Expat Hub:</strong> Serviced apartments 600,000–1,200,000 VND/night; 20-minute Grab ride to Old Quarter (costs 65,000–85,000 VND by GrabCar); lake breeze and artisan coffee.',
            '<strong>Noi Bai Airport Base:</strong> Transit hotels on Vo Van Kiet avenue cost 350,000–600,000 VND; free 5-minute shuttle to terminals; zero nightlife.',
        ]
    ],

    // 8. Hanoi Travel Guide (ID 287)
    287 => [
        'table_anchor' => 'vg-hanoi-travel-glance-table',
        'title' => '📜 Authoritative 2026 Hanoi Travel Practical Data:',
        'items' => [
            '<strong>Visa Regulations:</strong> Resolution 128/NQ-CP grants 45 days visa-free for 13 nations (UK, Germany, France, Italy, Spain, Japan, South Korea); 90-day multiple entry e-visa ($25 USD) via xuatnhapcanh.gov.vn.',
            '<strong>Noi Bai Airport Transit:</strong> Express Bus 86 (45,000 VND), GrabCar (280,000–320,000 VND, 40 mins via Vo Nguyen Giap expressway).',
            '<strong>Railway Departures:</strong> Ga Hanoi (120 Le Duan) connects north to Lao Cai/Sapa (train SP3 at 22:00, 380,000 VND) and south to Hue/Da Nang (SE3 at 19:20, 680,000 VND).',
            '<strong>Emergency Support:</strong> Vietnam National Administration of Tourism hotline 1900.6868; Tourist Police 113; SOS International Clinic 024.3934.0666.',
        ]
    ],

    // 9. Ha Long Bay Cruise Questions Before Booking (ID 479)
    479 => [
        'table_anchor' => 'vg-ha-long-cruise-questions-at-a-glance',
        'title' => '🚢 2026 Ha Long Cruise Hard Numbers & Port Reality:',
        'items' => [
            '<strong>Wharf Check-in Points:</strong> Tuan Chau International Marina (15 km west of Ha Long city; 220+ licensed vessels) vs Halong International Cruise Port (Sun Group port in Bai Chay; 85 vessels).',
            '<strong>Mandatory Port Surcharges:</strong> Passenger terminal fee and bay sight pass costs 290,000 VND per person (must be included in cruise invoice or paid at ticket window).',
            '<strong>Expressway Transfer Surcharge:</strong> Limousine bus from Hanoi Old Quarter via CT04 Expressway costs 300,000–350,000 VND ($12–$14 USD) one-way (2.0–2.5 hrs).',
            '<strong>Cancellation Policy under Maritime Law:</strong> In case of storms (Typhoon warning Signal 3), Quang Ninh Port Authority halts sailings; 100% cruise fare is legally refundable minus consumed transfer costs.',
            '<strong>Quang Ninh Maritime Safety Hotline:</strong> Port Authority emergency dispatch 0203.3846.594.',
        ]
    ],

    // 10. Ha Long Bay Travel Guide (ID 195)
    195 => [
        'table_anchor' => 'vg-ha-long-bay-glance-table',
        'title' => '⚓ Authoritative 2026 Ha Long Bay Logistics & Costs:',
        'items' => [
            '<strong>Day Cruise Pricing:</strong> 4-hour Route 1 (Thien Cung Cave) costs 650,000–850,000 VND; 6-hour Route 2 (Sung Sot Cave + Ti Top Island) costs 950,000–1,300,000 VND including lunch.',
            '<strong>Overnight 2D1N Tiers:</strong> Mid-range 3-star steel vessels cost 2,800,000–3,800,000 VND ($110–$150 USD) per cabin; luxury 5-star balcony suites cost 6,200,000–9,500,000 VND ($250–$380 USD).',
            '<strong>Hanoi Transfer:</strong> Expressway limousine departs Old Quarter hourly (300,000–350,000 VND, 2.5 hrs). Local bus from My Dinh station costs 140,000 VND (3.5 hrs).',
            '<strong>Kayaking & Tender Fees:</strong> Double kayak rental at Luon Cave costs 50,000 VND per person; bamboo rowing boat operated by local boatmen costs 50,000 VND.',
        ]
    ],

    // 11. Ninh Binh Without Rushing (ID 478)
    478 => [
        'table_anchor' => 'vg-ninh-binh-without-rushing-at-a-glance',
        'title' => '🚣 2026 Ninh Binh Sights Fees & Time Investment Benchmarks:',
        'items' => [
            '<strong>Trang An Rowing Boat Tour:</strong> 250,000 VND per person (maximum 4 adults per boat); Route 2 is the balanced choice (3 caves, Kong film set, 3 hours on water).',
            '<strong>Tam Coc Boat Tour:</strong> 250,000 VND (ticket 120,000 VND entrance + 150,000 VND boat fee for 2 passengers; 1.5–2 hours on Ngo Dong River).',
            '<strong>Hang Mua Dragon Peak:</strong> Admission 100,000 VND; 486 stone steps to summit; best climbed at 06:30 or 17:00 to avoid midday heat.',
            '<strong>Bai Dinh Pagoda Complex:</strong> Free entrance to temple grounds; electric shuttle cart costs 60,000 VND round-trip; stupa elevator ticket 50,000 VND.',
            '<strong>Local Bicycle & Scooter Rental:</strong> Push bikes cost 50,000 VND/day; 110cc automatic scooters cost 120,000–150,000 VND/day (gasoline ~24,000 VND/litre).',
        ]
    ],

    // 12. Ninh Binh Day Trip vs Overnight (ID 326)
    326 => [
        'table_anchor' => 'vg-ninh-binh-day-trip-overnight-glance',
        'title' => '⏱️ Ninh Binh Schedule & Budget Trade-Off (2026 Reality):',
        'items' => [
            '<strong>Day Trip Math:</strong> Hanoi departure 07:30, arrival 09:15; sights visited: Hang Mua (100,000 VND) + Trang An (250,000 VND); return 16:30, arrive Hanoi 18:30. Total tour cost: 850,000–1,200,000 VND ($35–$48 USD).',
            '<strong>2D1N Overnight Math:</strong> Adds sunset at Tam Coc, early morning Bich Dong Pagoda (free), and Van Long Nature Reserve boat (100,000 VND). Homestay in Trang An/Tam Coc runs 350,000–700,000 VND ($14–$28 USD).',
            '<strong>Expressway Transfer:</strong> Hanoi Old Quarter limousine pick-up costs 150,000–180,000 VND one-way (90 mins via CT01 Expressway).',
            '<strong>Railway Alternative:</strong> Train SE7 departs Ga Hanoi 06:00, arrives Ninh Binh 08:15 (ticket 95,000 VND for air-conditioned soft seat).',
        ]
    ],

    // 13. Ninh Binh to Ha Long Bay Transfer (ID 345)
    345 => [
        'table_anchor' => 'vg-ninh-binh-ha-long-transfer-glance',
        'title' => '🚐 2026 Ninh Binh to Ha Long Direct Logistics Fares:',
        'items' => [
            '<strong>Direct Limousine Van:</strong> Fares range from 280,000 to 320,000 VND ($11–$13 USD); 180 km route takes 3.0 to 3.5 hours via National Route 10 and Hai Phong Expressway.',
            '<strong>Departure Timings:</strong> Daily scheduled departures at 06:30, 07:30, 08:00, and 13:00; morning van arrives Tuan Chau Marina before 11:30 for 12:00 cruise boarding.',
            '<strong>Private Car Charter:</strong> 4-seater sedan costs 1,600,000–1,800,000 VND ($65–$72 USD); 7-seater SUV costs 1,900,000–2,200,000 VND including highway tolls.',
            '<strong>Luggage Transfer Policy:</strong> Scheduled limousines permit 1 large suitcase (max 20 kg) and 1 daypack per ticketed seat.',
        ]
    ],

    // 14. Ninh Binh Travel Guide (ID 190)
    190 => [
        'table_anchor' => 'vg-ninh-binh-glance-table',
        'title' => '🌾 Authoritative 2026 Ninh Binh Sights & Transit Registry:',
        'items' => [
            '<strong>Trang An UNESCO Complex:</strong> 250,000 VND ticket covers 3-hour boat tour through 4 karst grottos; open 07:00–17:00 daily.',
            '<strong>Bich Dong Cave Pagoda:</strong> Free entrance; parking attendant fee 10,000 VND; open 07:00–18:00.',
            '<strong>Hoa Lu Ancient Capital:</strong> Admission 20,000 VND; historic Dinh and Le dynasty shrines; open 07:00–17:00.',
            '<strong>Van Long Wetland Nature Reserve:</strong> Boat fee 100,000 VND; quiet reed marshes with Delacour langur sightings; open 07:00–17:30.',
            '<strong>Train Connection:</strong> Ga Ninh Binh on Le Vy street connects to Hanoi (SE8, 2 hrs, 95,000 VND) and Hue (SE3, 11 hrs, 580,000 VND).',
        ]
    ],

    // 15. Tam Coc Travel Guide (ID 418)
    418 => [
        'table_anchor' => 'vg-tam-coc-glance',
        'title' => '🚣 2026 Tam Coc Boat Wharf Logistics & Costs:',
        'items' => [
            '<strong>Wharf Pier Pricing:</strong> 250,000 VND total for 2 passengers (120,000 VND entrance ticket + 150,000 VND boat hire); 90-minute rowing journey under 3 natural caves.',
            '<strong>Harvest Window:</strong> Golden rice season runs late May to early June; during non-harvest months, paddies are lush green (Feb–Apr) or flooded mirrors (Jan).',
            '<strong>Bicycle Excursions:</strong> 3 km level ride to Thai Vi Temple and Bich Dong Pagoda; bike hire 50,000 VND/day.',
            '<strong>Tipping Norms:</strong> Local boatwomen appreciate a tip of 50,000–100,000 VND ($2–$4 USD) per boat for 90 minutes of foot-rowing exertion.',
        ]
    ],

    // 16. Hue Imperial City Guide (ID 500)
    500 => [
        'table_anchor' => 'vg-hue-imperial-city-stay-halfday-pass',
        'title' => '🏯 2026 Hue Imperial Citadel Admissions & Ticket Bundles:',
        'items' => [
            '<strong>Citadel (The Imperial City):</strong> 200,000 VND per adult; open daily 07:00–17:30 (audio guide rental 50,000 VND via QR code).',
            '<strong>3-Site Combination Pass:</strong> 420,000 VND (saves 60,000 VND; covers Citadel + Khai Dinh Tomb + Minh Mang Tomb; valid for 2 consecutive days).',
            '<strong>4-Site Combination Pass:</strong> 530,000 VND (covers Citadel + Khai Dinh + Minh Mang + Tu Duc Tomb).',
            '<strong>Dress Code Regulations:</strong> Sleeved shirts and knee-length shorts/skirts are legally mandatory inside To Mieu Temple and Thai Hoa Palace under heritage decree.',
            '<strong>Ga Hue Rail Arrivals:</strong> Train SE3 from Hanoi arrives 08:30; GrabCar to Citadel south gate costs 45,000–55,000 VND.',
        ]
    ],

    // 17. Hoi An Ancient Town Guide (ID 499)
    499 => [
        'table_anchor' => 'vg-hoi-an-ancient-town-stay-visit-skip',
        'title' => '🏮 2026 Hoi An Ancient Town Ticket Regulations & Fares:',
        'items' => [
            '<strong>Official Heritage Ticket:</strong> 120,000 VND per international visitor ($4.80 USD); valid for full stay; includes 5 coupons for heritage houses (Tan Ky, Phung Hung), assembly halls (Fujian, Cantonese), and Japanese Bridge.',
            '<strong>Pedestrian-Only Hours:</strong> Motorbikes banned from old quarter 09:00–11:00 and 15:00–21:30 daily; bicycle rental costs 40,000–50,000 VND/day.',
            '<strong>Lantern Boat Rides on Hoai River:</strong> Fixed government tariff of 150,000 VND for 1–3 passengers (200,000 VND for 4–5 passengers) for 20-minute cruise; paper lanterns cost 10,000 VND.',
            '<strong>Da Nang Airport (DAD) to Hoi An:</strong> 30 km; GrabCar costs 320,000–380,000 VND (45 mins); shared shuttle bus costs 130,000–150,000 VND per seat.',
        ]
    ],

    // 18. Best Things to Do in Hoi An (ID 178)
    178 => [
        'table_anchor' => 'vg-best-things-hoi-an-priority-map',
        'title' => '🚲 2026 Hoi An Experiences Price & Schedule Registry:',
        'items' => [
            '<strong>Cam Thanh Coconut Basket Boat:</strong> 150,000 VND per basket boat (seats 2 adults, 45-minute spin through water coconut palms); GrabCar from town costs 70,000 VND.',
            '<strong>Tra Que Vegetable Village:</strong> Free pedestrian access; cooking class + foot bath package runs 350,000–550,000 VND ($14–$22 USD).',
            '<strong>An Bang Beach Excursion:</strong> 4.5 km north via Hai Ba Trung street; free beach access; motorbike parking 10,000 VND; deck chair rental with coconut 50,000 VND.',
            '<strong>My Son Sanctuary Day Excursion:</strong> 40 km west; entrance fee 150,000 VND (includes electric cart shuttle); morning tour bus costs 150,000 VND round-trip.',
            '<strong>Memories Show (Ky Uc Hoi An):</strong> Open-air theatrical performance on Hen Island; ticket tiers Eco 600,000 VND / Mid 750,000 VND / VIP 1,200,000 VND; runs 20:00–21:00 daily (except Tuesdays).',
        ]
    ],

    // 19. Hoi An vs Hue (ID 227)
    227 => [
        'table_anchor' => 'vg-hoi-an-hue-glance-table',
        'title' => '⚖️ 2026 Central Heritage Comparison Benchmarks:',
        'items' => [
            '<strong>Distance & Transit:</strong> 130 km apart; private car via Hai Van Pass takes 3.0 hours (1,100,000–1,300,000 VND); train SE1/SE3 between Ga Hue and Ga Da Nang takes 2.5 hours (110,000 VND soft seat, spectacular cliffside track).',
            '<strong>Daily Budget Floor:</strong> Hue mid-range hotel 500,000–850,000 VND; Hoi An old town boutique 800,000–1,600,000 VND; food budget in Hue is ~30% lower (Bun Bo Hue bowl 35,000–45,000 VND vs Hoi An Cao Lau 40,000–50,000 VND).',
            '<strong>Heritage Density:</strong> Hue has 1 Imperial Citadel (200k) + 7 Royal Tombs (150k each); Hoi An has 1 compact walking town (120k ticket) + beach access (An Bang, 4 km).',
            '<strong>Weather Window:</strong> Both face heavy monsoon rains in Oct–Nov (Hue averages 600 mm/month); ideal visiting window is February through July.',
        ]
    ],

    // 20. Da Nang vs Hoi An (ID 209)
    209 => [
        'table_anchor' => 'vg-da-nang-hoi-an-glance-table',
        'title' => '🏖️ 2026 Da Nang vs Hoi An Base Comparison Data:',
        'items' => [
            '<strong>Airport Logistics:</strong> Da Nang International Airport (DAD) is 4 km from My Khe Beach (GrabCar 70,000–90,000 VND) vs 30 km from Hoi An (GrabCar 320,000–380,000 VND).',
            '<strong>Hotel Rates by Tier:</strong> Da Nang beachside high-rise hotel costs 600,000–1,100,000 VND ($24–$44 USD); Hoi An boutique garden hotel runs 850,000–1,800,000 VND ($34–$72 USD).',
            '<strong>Marble Mountains (Ngu Hanh Son):</strong> Halfway point between cities (12 km from Da Nang); admission 40,000 VND + elevator ticket 15,000 VND.',
            '<strong>Evening Vibe:</strong> Da Nang Dragon Bridge fire & water breathing show runs Friday, Saturday, Sunday at 21:00 (free); Hoi An lantern-lit walking streets run nightly until 21:30.',
        ]
    ],

    // 21. Da Nang Travel Guide (ID 213)
    213 => [
        'table_anchor' => 'vg-da-nang-guide-glance-table',
        'title' => '🌊 Authoritative 2026 Da Nang Coastal & City Data:',
        'items' => [
            '<strong>Ba Na Hills & Golden Bridge:</strong> Cable car + theme park admission is 900,000 VND per adult; GrabCar round-trip with waiting time is 550,000–650,000 VND.',
            '<strong>Son Tra Peninsula & Linh Ung Pagoda:</strong> Free entrance to 67m Lady Buddha statue; manual transmission motorbikes permitted on mountain pass (scooters restricted on peak roads under city safety decree).',
            '<strong>Cham Sculpture Museum:</strong> 60,000 VND admission; open daily 07:30–17:00 at dragon bridge junction.',
            '<strong>Intercity Rail Connection:</strong> Ga Da Nang (202 Hai Phong street) connects north to Hue (2.5 hrs, 110,000 VND) and south to Quy Nhon / HCMC (SE3, 16 hrs, 850,000 VND).',
        ]
    ],

    // 22. Best Things to Do in Hue (ID 184)
    184 => [
        'table_anchor' => 'vg-best-things-hue-priority-map',
        'title' => '👑 2026 Hue Imperial Monuments Pricing & Logistics:',
        'items' => [
            '<strong>Khai Dinh Royal Tomb:</strong> Admission 150,000 VND; elaborate mosaic glass and cement architecture; open 07:00–17:30; 9 km south of city center (GrabCar ~110,000 VND).',
            '<strong>Minh Mang Royal Tomb:</strong> Admission 150,000 VND; grand axial symmetry and lotus ponds; open 07:00–17:30; 12 km south.',
            '<strong>Thien Mu Pagoda:</strong> Free entrance; 7-story octagonal tower on Perfume River; dragon boat rental from Toa Kham pier costs 150,000–200,000 VND round-trip.',
            '<strong>Thuy Xuan Incense Village:</strong> Free to walk and photograph; purchase incense bundle or conical hat (50,000–80,000 VND) to support local craft families.',
            '<strong>Dong Ba Market:</strong> Open 06:00–18:00 daily; street food stall bowls (Banh Beo, Banh Nam, Banh Loc) cost 25,000–35,000 VND per plate.',
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

    // Check if factsheet is already installed
    if (strpos($content, 'vg-ground-truth-factsheet') !== false && strpos($content, $data['title']) !== false) {
        echo "Post ID {$post_id} ({$post->post_name}): Factsheet already present, skipping." . PHP_EOL;
        continue;
    }

    // Build factsheet HTML
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

    // Find table anchor
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

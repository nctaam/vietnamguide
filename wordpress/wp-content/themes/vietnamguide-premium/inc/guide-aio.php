<?php
/**
 * VietnamGuide AIO (AI Optimization & Agent Discoverability)
 * Provides /llms.txt, /llms-full.txt endpoints and AI crawler directives.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Static route directory for AI knowledge discovery.
 * Zero database queries, sub-millisecond execution.
 */
function vg_aio_routes_inventory(): array
{
    static $routes = null;
    if ($routes !== null) {
        return $routes;
    }

    $routes = [
        'destinations/ho-chi-minh-city-travel-guide' => [
            'title' => 'Ho Chi Minh City Travel Guide',
            'desc'  => 'Ho Chi Minh City travel guide: District 1 vs District 3 bases, War Remnants Museum, street food scenes, Tan Son Nhat airport tips, and Cu Chi day trips.',
        ],
        'itineraries/10-days-in-vietnam' => [
            'title' => '10 Days in Vietnam',
            'desc'  => 'Plan a 10-day Vietnam itinerary: balanced route order from Hanoi to Ho Chi Minh City, internal transfer timings, realistic night allocation, and costs.',
        ],
        'itineraries/7-days-in-vietnam' => [
            'title' => '7 Days in Vietnam',
            'desc'  => 'Plan a 7-day Vietnam itinerary: focused one-week routes covering the north (Hanoi, Ha Long Bay, Ninh Binh) or central region without rushing transfers.',
        ],
        'itineraries/14-days-in-vietnam' => [
            'title' => '14 Days in Vietnam',
            'desc'  => 'Plan 14 days in Vietnam by month, route shape, and travel style, with a two-week route chooser, transfer checks, route photos, and booking sequence.',
        ],
        'itineraries/21-days-in-vietnam' => [
            'title' => '21 Days in Vietnam',
            'desc'  => 'Complete 21-day Vietnam itinerary: full-country travel route from North to South with Ha Giang Loop, Sapa, Central Heritage, Saigon, and Mekong Delta.',
        ],
        'itineraries/hanoi-in-2-days' => [
            'title' => 'Hanoi in 2 Days',
            'desc'  => 'Hanoi in 2 days itinerary: ideal 48-hour plan covering Hoan Kiem Lake, Temple of Literature, Old Quarter coffee culture, evening street food, and museums.',
        ],
        'compare/ha-long-bay-vs-lan-ha-bay' => [
            'title' => 'Ha Long Bay vs Lan Ha Bay',
            'desc'  => 'Ha Long Bay vs Lan Ha Bay: compare overnight cruise routes, limestone scenery, kayak stops, crowd levels, port transfers, and weather considerations.',
        ],
        'plan/vietnam-evisa' => [
            'title' => 'Vietnam E-Visa Guide',
            'desc'  => 'Vietnam e-visa application guide: official portal link, exact fees, photo rules, entry port requirements, processing times, and common mistake prevention.',
        ],
        'compare/cu-chi-tunnels-vs-mekong-delta-day-trip' => [
            'title' => 'Cu Chi Tunnels vs Mekong Delta Day Trip',
            'desc'  => 'Cu Chi Tunnels vs Mekong Delta day trip: compare war history vs river scenery, travel times from Saigon, tour fatigue, speedboat options, and itinerary fit.',
        ],
        'compare/da-nang-vs-hoi-an' => [
            'title' => 'Da Nang vs Hoi An',
            'desc'  => 'Da Nang vs Hoi An: choose your central Vietnam base by beach access, airport proximity, dining variety, historic charm, nightlife, and day trip options.',
        ],
        'compare/hoi-an-vs-hue' => [
            'title' => 'Hoi An vs Hue',
            'desc'  => 'Hoi An vs Hue: compare central Vietnam\'s lantern town and imperial capital by historic depth, food scenes, walking comfort, nightlife, and travel pace.',
        ],
        'compare/mui-ne-vs-nha-trang' => [
            'title' => 'Mui Ne vs Nha Trang',
            'desc'  => 'Mui Ne vs Nha Trang: compare coastal kitesurfing and sand dunes in Mui Ne with high-rise hotels, scuba diving, and nightlife in Nha Trang.',
        ],
        'compare/ninh-binh-day-trip-vs-overnight' => [
            'title' => 'Ninh Binh Day Trip vs Overnight',
            'desc'  => 'Ninh Binh: Hanoi day trip or overnight stay? Practical verdict by route length, transfer pressure, Trang An vs Tam Coc, weather, cost, and hotel choices.',
        ],
        'compare/north-central-south-vietnam' => [
            'title' => 'North vs Central vs South Vietnam',
            'desc'  => 'Compare North, Central and South Vietnam for a first trip: best region by season, route length, traveler style, pacing, and what to skip.',
        ],
        'compare/old-quarter-vs-french-quarter-vs-west-lake' => [
            'title' => 'Old Quarter vs French Quarter vs West Lake',
            'desc'  => 'Compare Hanoi\'s Old Quarter, French Quarter, and West Lake by sleep, walking, food, pickup clarity, family fit, premium calm, and longer-stay comfort.',
        ],
        'compare/phu-quoc-vs-nha-trang' => [
            'title' => 'Phu Quoc vs Nha Trang',
            'desc'  => 'Phu Quoc vs Nha Trang: compare island resort relaxation with active city-beach energy, water sports, dining scenes, direct flights, and rainy seasons.',
        ],
        'compare/trang-an-vs-tam-coc' => [
            'title' => 'Trang An vs Tam Coc',
            'desc'  => 'Trang An vs Tam Coc: choose the best Ninh Binh boat trip by scenery, base, crowds, timing, photography, family comfort, day trip vs overnight, and route fit.',
        ],
        'destinations/unesco-heritage-sites-vietnam' => [
            'title' => 'UNESCO Heritage Sites in Vietnam',
            'desc'  => 'Choose which UNESCO World Heritage Sites in Vietnam fit your route, with 2025 updates, first-trip priorities, skip logic, source checks, and photo proof.',
        ],
        'destinations/best-beaches-in-vietnam' => [
            'title' => 'Best Beaches in Vietnam',
            'desc'  => 'Choose the best beach in Vietnam by month, route, and trip style: Phu Quoc, Da Nang, Hoi An, Nha Trang, Con Dao, Mui Ne, Quy Nhon, Cat Ba, or Phu Quy.',
        ],
        'destinations/best-places-to-visit-vietnam' => [
            'title' => 'Best Places to Visit in Vietnam',
            'desc'  => 'Best places to visit in Vietnam: curated shortlist of top destinations by season, travel pace, cultural depth, beach time, and realistic route connections.',
        ],
        'destinations/best-things-to-do-in-hanoi' => [
            'title' => 'Best Things to Do in Hanoi',
            'desc'  => 'Best things to do in Hanoi: Old Quarter walking routes, street food highlights, French Quarter heritage, water puppets, day trips, and sights to skip.',
        ],
        'destinations/best-things-to-do-in-hoi-an' => [
            'title' => 'Best Things to Do in Hoi An',
            'desc'  => 'Best things to do in Hoi An: Ancient Town heritage ticket rules, lantern night walks, An Bang beach, My Son Sanctuary day trips, and regional food tips.',
        ],
        'destinations/best-things-to-do-in-hue' => [
            'title' => 'Best Things to Do in Hue',
            'desc'  => 'Best things to do in Hue: Imperial Citadel walking tour, royal tombs of Tu Duc and Khai Dinh, Perfume River boats, and authentic royal court cuisine.',
        ],
        'destinations/ninh-binh-travel-guide' => [
            'title' => 'Ninh Binh Travel Guide',
            'desc'  => 'Ninh Binh travel guide: Trang An vs Tam Coc boat routes, Hang Mua viewpoint climb, where to stay in Tam Coc vs Trang An, and day trips from Hanoi.',
        ],
        'destinations/ha-long-bay-travel-guide' => [
            'title' => 'Ha Long Bay Travel Guide',
            'desc'  => 'Ha Long Bay travel guide: decide between a 4-hour day cruise or a 2-day luxury overnight boat, pick Tuan Chau vs Halong International port, and avoid scams.',
        ],
        'destinations/cat-ba-travel-guide' => [
            'title' => 'Cat Ba Travel Guide',
            'desc'  => 'Cat Ba travel guide: island base logistics, Lan Ha Bay boat tours, Cannon Fort, Cat Ba National Park hikes, ferry connections from Hanoi, and hotels.',
        ],
        'destinations/bai-tu-long-bay-guide' => [
            'title' => 'Bai Tu Long Bay Guide',
            'desc'  => 'Bai Tu Long Bay guide: quieter cruise alternative to Ha Long Bay with uncrowded limestone karsts, secluded kayak routes, cave excursions, and port logistics.',
        ],
        'destinations/da-nang-travel-guide' => [
            'title' => 'Da Nang Travel Guide',
            'desc'  => 'Da Nang travel guide: My Khe beach resorts, Dragon Bridge weekend fire shows, Marble Mountains, Son Tra peninsula, and day trips to Hoi An and Hue.',
        ],
        'destinations/best-islands-in-vietnam' => [
            'title' => 'Best Islands in Vietnam',
            'desc'  => 'Best islands in Vietnam: compare Phu Quoc resort beaches, Con Dao marine tranquility, Cat Ba national park karsts, and Cham Islands diving day trips.',
        ],
        'destinations/phu-quoc-travel-guide' => [
            'title' => 'Phu Quoc Travel Guide',
            'desc'  => 'Phu Quoc travel guide: 30-day visa exemption rules, best beaches from Sao to Ong Lang, where to stay, direct international flights, and night markets.',
        ],
        'destinations/con-dao-travel-guide' => [
            'title' => 'Con Dao Travel Guide',
            'desc'  => 'Con Dao travel guide: pristine coral reefs, sea turtle nesting seasons, historic prison sites, flights from Saigon, boutique resorts, and island weather.',
        ],
        'destinations/nha-trang-travel-guide' => [
            'title' => 'Nha Trang Travel Guide',
            'desc'  => 'Nha Trang travel guide: urban beachfront hotels, island hopping boat tours, mud baths, diving spots, Cam Ranh airport transfers, and seasonal timing.',
        ],
        'destinations/quy-nhon-travel-guide' => [
            'title' => 'Quy Nhon Travel Guide',
            'desc'  => 'Quy Nhon travel guide: uncrowded central coast beaches, Ky Co and Eo Gio coastal cliffs, Cham towers, local seafood dining, and Phu Cat airport access.',
        ],
        'destinations/cham-islands-travel-guide' => [
            'title' => 'Cham Islands Travel Guide',
            'desc'  => 'Cham Islands travel guide: speedboats from Hoi An, UNESCO biosphere marine reserve snorkeling, Bai Chong beach, homestay visits, and rough sea season advice.',
        ],
        'destinations/ly-son-travel-guide' => [
            'title' => 'Ly Son Travel Guide',
            'desc'  => 'Ly Son travel guide: volcanic crater views at Thoi Loi, To Vo gate, garlic farming heritage, Sa Ky port ferry logistics, homestays, and sea weather safety.',
        ],
        'destinations/mekong-delta-travel-guide' => [
            'title' => 'Mekong Delta Travel Guide',
            'desc'  => 'Mekong Delta travel guide: floating markets in Can Tho, Ben Tre riverboat day trips, homestays in Vinh Long, Chau Doc border routes, and transfer logistics.',
        ],
        'destinations/best-day-trips-from-ho-chi-minh-city' => [
            'title' => 'Best Day Trips from Ho Chi Minh City',
            'desc'  => 'Best day trips from Ho Chi Minh City: Cu Chi Tunnels half-day tours, Ben Tre Mekong Delta cruises, Tay Ninh Cao Dai temple, and Can Gio mangrove forests.',
        ],
        'destinations/where-to-stay-in-ho-chi-minh-city' => [
            'title' => 'Where to Stay in Ho Chi Minh City',
            'desc'  => 'Where to stay in Ho Chi Minh City: choose District 1, District 3, Thao Dien, riverside, Bui Vien, or airport areas by route job and comfort.',
        ],
        'destinations/hanoi-travel-guide' => [
            'title' => 'Hanoi Travel Guide',
            'desc'  => 'Hanoi travel guide: how many days to stay, Old Quarter vs French Quarter hotels, Noi Bai airport taxis, street food safety, and day trips to Ninh Binh.',
        ],
        'destinations/where-to-stay-in-hanoi' => [
            'title' => 'Where to Stay in Hanoi',
            'desc'  => 'Where to stay in Hanoi: choose Hoan Kiem, Old Quarter edge, French Quarter, Ba Dinh, Tay Ho, or Noi Bai by first-night and pickup logic.',
        ],
        'destinations/best-day-trips-from-hanoi' => [
            'title' => 'Best Day Trips from Hanoi',
            'desc'  => 'Best day trips from Hanoi, compared by route job: Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, timing, weather, and skip logic.',
        ],
        'destinations/where-to-stay-in-ninh-binh' => [
            'title' => 'Where to Stay in Ninh Binh',
            'desc'  => 'Where to stay in Ninh Binh: choose Tam Coc, Trang An area, Ninh Binh city, Van Long/Gia Vien or Cuc Phuong by route, sleep, pickup and comfort.',
        ],
        'destinations/tam-coc-travel-guide' => [
            'title' => 'Tam Coc Travel Guide',
            'desc'  => 'Tam Coc travel guide for international visitors: choose the Ninh Binh base, boat timing, cycling, Bich Dong, Mua Cave, season, cost and route pacing.',
        ],
        'plan/best-time-to-visit-vietnam' => [
            'title' => 'Best Time to Visit Vietnam',
            'desc'  => 'Best time to visit Vietnam by route: north, central, south, beaches, weather trade-offs, seasonal pivots, booking checks, and live sources.',
        ],
        'plan/vietnam-travel-guide' => [
            'title' => 'Vietnam Travel Guide',
            'desc'  => 'Complete Vietnam travel guide for first-timers: step-by-step route planning, visa rules, regional climates, daily budgets, transport, and cultural norms.',
        ],
        'plan/transport-within-vietnam' => [
            'title' => 'Transport Within Vietnam',
            'desc'  => 'Choose transport within Vietnam by route: domestic flights, trains, private cars, sleeper buses, ferries, airport transfers, prebooking, and live checks.',
        ],
        'plan/money-cash-cards-atms' => [
            'title' => 'Money in Vietnam: Cash, Cards and ATMs',
            'desc'  => 'Plan money in Vietnam with VND cash, card use, ATM withdrawals, exchange checks, arrival cash, declaration thresholds, payment safety, and live checks.',
        ],
        'plan/sim-esim-vietnam' => [
            'title' => 'SIM and eSIM in Vietnam',
            'desc'  => 'Choose SIM or eSIM in Vietnam with device checks, local number needs, airport setup, coverage, hotspot, roaming backup, failure modes, and live checks.',
        ],
        'plan/safety-scams-vietnam' => [
            'title' => 'Safety and Scams in Vietnam',
            'desc'  => 'Vietnam safety and scams guide: practical advice on airport taxi fraud, fake Grab drivers, ATM skimming, street crossing safety, and emergency contacts.',
        ],
        'plan/health-travel-insurance-vietnam' => [
            'title' => 'Health and Travel Insurance for Vietnam',
            'desc'  => 'Vietnam travel insurance and health guide: international hospital networks in Hanoi and HCMC, motorbike injury coverage rules, vaccines, and pharmacies.',
        ],
        'plan/hanoi-airport-to-old-quarter' => [
            'title' => 'Hanoi Airport to Old Quarter',
            'desc'  => 'How to get from Hanoi airport to Old Quarter: choose hotel pickup, taxi, app ride, airport bus, or public bus by arrival hour and luggage.',
        ],
        'plan/hanoi-to-ninh-binh-transport' => [
            'title' => 'Hanoi to Ninh Binh Transport',
            'desc'  => 'How to get from Hanoi to Ninh Binh: compare train, limousine van, private car, day tour and onward transfers by drop-off, luggage, timing and route fit.',
        ],
        'plan/ninh-binh-to-ha-long-bay-transfer' => [
            'title' => 'Ninh Binh to Ha Long Bay Transfer',
            'desc'  => 'Ninh Binh to Ha Long Bay transfer guide: choose private car, cruise transfer, shared van or overnight buffer by exact port, pickup, luggage and cruise timing.',
        ],
        'destinations/ha-giang-loop-planning-guide' => [
            'title' => 'Ha Giang Loop Planning Guide: Safety, Scenery and Route Fit',
            'desc'  => 'Plan the Ha Giang Loop by safety, scenery, easy rider vs self-drive, license, insurance, weather, road fatigue, route length, and Hanoi buffers.',
        ],
        'destinations/sapa-travel-guide' => [
            'title' => 'Sapa Travel Guide: Terraces, Trekking and Softer Mountain Travel',
            'desc'  => 'Plan Sapa by terraces, trekking, town vs valley stays, season, fog, transport from Hanoi, comfort, Fansipan, and when to skip it.',
        ],
        'plan/hanoi-to-ha-giang-transport' => [
            'title' => 'Hanoi to Ha Giang Transport: Bus, Private Car or Staged Route?',
            'desc'  => 'Compare Hanoi to Ha Giang transport by sleeper bus, day transfer, private car, and staged route using fatigue, safety, weather, and buffers.',
        ],
        'plan/hanoi-to-sapa-transport' => [
            'title' => 'Hanoi to Sapa Transport: Train, Cabin Bus or Private Car?',
            'desc'  => 'Compare Hanoi to Sapa transport by train, cabin bus, limousine van, and private car using sleep, arrival, luggage, motion comfort, and route fit.',
        ],
        'compare/sapa-vs-ha-giang' => [
            'title' => 'Sapa vs Ha Giang: Terraces, Loop Roads or Softer Mountain Travel?',
            'desc'  => 'Choose Sapa, Ha Giang, both, or neither by scenery style, comfort, season, road safety, insurance, route length, and Hanoi transfer pressure.',
        ],
        'destinations/hoi-an-ancient-town-guide' => [
            'title' => 'Hoi An Ancient Town Guide: When to Stay, Visit or Skip',
            'desc'  => 'Decide whether to stay overnight in Hoi An, visit from Da Nang, go by day or evening, or skip when central Vietnam needs a cleaner route.',
        ],
        'destinations/hue-imperial-city-guide' => [
            'title' => 'Hue Imperial City Guide: How to Visit Without Rushing',
            'desc'  => 'Decide whether Hue Imperial City deserves a two-night stay, one-night stop, focused half-day, or clean skip by pacing, heat, tombs, and route order.',
        ],
        'destinations/da-nang-beaches-guide' => [
            'title' => 'Da Nang Beaches Guide: My Khe, Non Nuoc or Hoi An Coast?',
            'desc'  => 'Choose between My Khe, Non Nuoc, Son Tra edge, Hoi An coast, or Hoi An town by season, hotel style, family needs, and route pressure.',
        ],
        'destinations/phong-nha-travel-guide' => [
            'title' => 'Phong Nha Travel Guide: Caves, Seasons and Route Fit',
            'desc'  => 'Decide whether Phong Nha is worth the cave detour by route fit, season, nights, fitness, insurance, and central Vietnam trade-offs.',
        ],
        'compare/hanoi-vs-ho-chi-minh-city' => [
            'title' => 'Hanoi vs Ho Chi Minh City: Which Should You Visit First?',
            'desc'  => 'Compare Hanoi and Ho Chi Minh City by first-night comfort, route direction, weather, food, day trips, airport logic, and trip length.',
        ],
        'compare/mekong-delta-overnight-vs-day-trip' => [
            'title' => 'Mekong Delta Overnight vs Day Trip: Which Is Worth It?',
            'desc'  => 'Choose a Mekong Delta day trip, one-night Can Tho/Cai Rang stay, deeper route, or clean skip by timing, comfort, transfer risk, and route length.',
        ],
        'plan/best-vietnam-routes-first-time-visitors' => [
            'title' => 'Best Vietnam Routes for First-Time Visitors: North, Central, South or Open-Jaw?',
            'desc'  => 'Compare route shapes before booking: north-only, central spine, south-only, open-jaw, and full-country routes for first-time Vietnam trips.',
        ],
        'plan/vietnam-in-december' => [
            'title' => 'Vietnam in December: Weather, Routes and What to Book Early',
            'desc'  => 'Plan Vietnam in December by region, route shape, weather risk, holiday booking pressure, beach and bay trade-offs, packing, and live checks.',
        ],
        'plan/vietnam-in-january' => [
            'title' => 'Vietnam in January: Best Routes, Weather and Tet Watchouts',
            'desc'  => 'Plan Vietnam in January by region, route shape, Tet lead-up, weather risk, transport pressure, packing, and live checks.',
        ],
        'plan/vietnam-in-february' => [
            'title' => 'Vietnam in February: Weather, Tet Timing and Best Routes',
            'desc'  => 'Plan Vietnam in February by Tet timing, post-Tet restart, regional weather, route shape, transport pressure, packing, and live checks.',
        ],
        'plan/tet-in-vietnam-travel-guide' => [
            'title' => 'Tet in Vietnam Travel Guide: What International Visitors Should Know',
            'desc'  => 'Plan Tet travel in Vietnam by exact dates, transport pressure, changed openings, respectful behavior, money, meals, medicine, route choices, and live checks.',
        ],
        'plan/vietnam-rainy-season-flexible-route' => [
            'title' => 'Vietnam Rainy Season Travel: How to Build a Flexible Route',
            'desc'  => 'Plan Vietnam rainy season travel with regional weather logic, route buffers, cancellation posture, indoor pivots, and transport checks.',
        ],
        'plan/vietnam-first-trip-planning-checklist' => [
            'title' => 'Vietnam First Trip Planning Checklist: What to Decide Before Booking',
            'desc'  => 'Plan a first Vietnam trip before booking with entry checks, route shape, weather risk, cost anchors, arrival setup, and what to skip.',
        ],
        'plan/what-to-pack-for-vietnam-region-season' => [
            'title' => 'What to Pack for Vietnam by Region and Season',
            'desc'  => 'Pack for Vietnam by region and season with north, central, south, mountain, coast, rain, and carry-on logic for first-time travelers.',
        ],
        'plan/vietnam-airport-arrival-checklist' => [
            'title' => 'Vietnam Airport Arrival Checklist: Money, SIM, Transport and Scams',
            'desc'  => 'Use this Vietnam airport arrival checklist to handle documents, luggage, SIM or eSIM, small cash, verified transport, scams, and first-night decisions.',
        ],
        'plan/vietnam-food-safety-street-food-etiquette' => [
            'title' => 'Vietnam Food Safety and Street Food Etiquette for First-Timers',
            'desc'  => 'Vietnam street food safety and etiquette for first-timers: choose busy stalls, order politely, handle sauces, ice, drinks, cash, seating, and flexible meals.',
        ],
        'plan/where-to-stay-in-vietnam-base-decisions' => [
            'title' => 'Where to Stay in Vietnam: City Base Decisions Before Choosing Hotels',
            'desc'  => 'Choose where to stay in Vietnam by base job: arrival ease, food access, quiet sleep, family rhythm, transfers, beach rest, and airport risk.',
        ],
        'plan/hanoi-first-time-visitor-mistakes' => [
            'title' => 'Hanoi First-Time Visitor Mistakes to Avoid',
            'desc'  => 'Avoid common Hanoi first-time visitor mistakes around where to stay, arrival night, day trips, weather, and crowded northern Vietnam routes.',
        ],
        'plan/ninh-binh-without-rushing' => [
            'title' => 'Ninh Binh Without Rushing: How to Choose Boat, Base and Transfer',
            'desc'  => 'Plan Ninh Binh without rushing. Choose one long day, one protected night, or two slower nights by boat route, base, transfer pressure, weather, and bay timing.',
        ],
        'plan/ha-long-bay-cruise-questions-before-booking' => [
            'title' => 'Ha Long Bay Cruise Questions to Ask Before Booking',
            'desc'  => 'Ask these crucial Ha Long Bay cruise questions before booking: port, cabin tier, route map, weather policy, bay choice, family comfort, and transfer risk.',
        ],
        'plan/best-vietnam-cities-for-first-time-visitors' => [
            'title' => 'Best Vietnam Cities for First-Time Visitors: Which Base Fits Your Route?',
            'desc'  => 'Choose Vietnam city bases by route role, arrival logic, food, culture, beach comfort, day trips, skip logic, and transfer pressure.',
        ],
        'plan/ha-giang-safety-guide' => [
            'title' => 'Ha Giang Safety Guide: Easy Rider, Self-Drive and Insurance Reality',
            'desc'  => 'Essential Ha Giang Loop safety guide: evaluate easy rider vs self-drive risks, police checkpoints, hospital access, and valid motorbike insurance rules.',
        ],
        'compare/ha-giang-easy-rider-vs-self-drive' => [
            'title' => 'Ha Giang Easy Rider vs Self-Drive: Which Is Right for You?',
            'desc'  => 'Decide between Ha Giang easy rider or self-drive: compare costs, license rules, road hazards, physical fatigue, and passenger comfort on the loop route.',
        ],
        'plan/sapa-trekking-guided-vs-self-guided' => [
            'title' => 'Sapa Trekking: Guided vs Self-Guided for First-Time Visitors',
            'desc'  => 'Plan your Sapa trekking route: compare guided vs self-guided village walks, trail navigation, mud seasons, ethical homestays, and local guide costs.',
        ],
        'destinations/where-to-stay-in-sapa' => [
            'title' => 'Where to Stay in Sapa: Town, Valley Lodge or Homestay?',
            'desc'  => 'Choose the best Sapa base: compare central Sapa town hotels, Muong Hoa valley eco-lodges, and village homestays by views, quiet, and taxi access.',
        ],
        'plan/best-time-for-northern-vietnam' => [
            'title' => 'Best Time for Northern Vietnam: Hanoi, Bay, Ninh Binh, Sapa and Ha Giang',
            'desc'  => 'Find the best time for northern Vietnam: monthly weather analysis across Hanoi, Ha Long Bay, Ninh Binh, Sapa, and Ha Giang to avoid fog and heavy rain.',
        ],
        'compare/vietnam-rice-terraces-guide' => [
            'title' => 'Vietnam Rice Terraces Guide: Sapa, Mu Cang Chai, Hoang Su Phi or Pu Luong?',
            'desc'  => 'Compare Vietnam rice terrace destinations: Sapa, Mu Cang Chai, Hoang Su Phi, and Pu Luong by golden season timing, travel logistics, and scenery.',
        ],
        'destinations/mu-cang-chai-travel-guide' => [
            'title' => 'Mu Cang Chai Travel Guide: Rice Terraces Without Forcing the Route',
            'desc'  => 'Plan your Mu Cang Chai trip: golden harvest timing, Khau Pha Pass transport from Hanoi, local homestays, and whether the mountain detour fits your route.',
        ],
        'destinations/pu-luong-travel-guide' => [
            'title' => 'Pu Luong Travel Guide: Softer Countryside or Mountain Detour?',
            'desc'  => 'Plan your Pu Luong getaway: valley retreats, water wheels, easy trekking, shuttle bus transfers from Hanoi or Ninh Binh, and best seasons to visit.',
        ],
    ];

    return $routes;
}

function vg_handle_llms_txt_request(): void
{
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = trim((string) parse_url($request_uri, PHP_URL_PATH), '/');

    if ($path !== 'llms.txt' && $path !== 'llms-full.txt') {
        return;
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: all');

    $site_url = untrailingslashit(home_url());
    $routes = vg_aio_routes_inventory();

    if ($path === 'llms.txt') {
        echo "# VietnamGuide — Independent Vietnam Travel Intelligence\n\n";
        echo "> High-evidence, logistics-first curated travel guides for international travelers visiting Vietnam.\n";
        echo "> Published by the VietnamGuide editorial team. All routes independently vetted with verified pricing, transit times, and route maps.\n\n";
        echo "Canonical Base: {$site_url}/\n";
        echo "Full Knowledge Base: {$site_url}/llms-full.txt\n";
        echo "Sitemap: {$site_url}/sitemap_index.xml\n\n";

        echo "## Interactive Travel Toolkits & Decision Engines\n\n";
        echo "- [Vietnam Travel Cost Calculator]({$site_url}/costs/vietnam-travel-cost/): Real-time interactive daily budget planner with 5-currency support (USD, VND, EUR, GBP, AUD) across 10+ destinations with itemized cost models.\n";
        echo "- [Vietnam Visa Requirement Checker]({$site_url}/plan/vietnam-evisa/): Interactive nationality-based visa requirements, 45-day exemption eligibility, and 90-day e-visa official portal guidelines.\n";
        echo "- [Vietnam Weather & Regional Season Matrix]({$site_url}/plan/best-time-to-visit-vietnam/): Month-by-month regional weather navigator across North, Central, and South Vietnam with seasonal pivot recommendations.\n";
        echo "- [Vietnam Route Packing & Preparation Checklist]({$site_url}/plan/vietnam-first-trip-planning-checklist/): Interactive 24-item travel preparation checklist covering official documents, electronics, modest clothing, medical essentials, and motorbike gear with browser state persistence.\n";
        echo "- [Vietnam Gateway Airport Navigator & Scam Shield]({$site_url}/plan/vietnam-airport-arrival-checklist/): Terminal-to-curb arrival guide, verified Grab pickup bay locators, and metered taxi fare estimators for Noi Bai (HAN), Tan Son Nhat (SGN), Da Nang (DAD), Cam Ranh (CXR), and Phu Quoc (PQC).\n";
        echo "- [Vietnam Interactive Itinerary Finder & Route Matcher]({$site_url}/itineraries/): Filterable route catalog matching traveler styles, duration (7d, 10d, 14d, 21d), and arrival gateways with zero backtrack transit days.\n\n";

        echo "## Primary Regional Hubs\n\n";
        echo "- [Vietnam Destinations Hub]({$site_url}/destinations/): Strategic overview of Vietnam\'s top regions, geographic trade-offs, and first-trip priorities.\n";
        echo "- [Vietnam Itineraries Hub]({$site_url}/itineraries/): Curated 7, 10, 14, and 21-day itinerary planning frameworks.\n";
        echo "- [Vietnam Comparison Guides Hub]({$site_url}/compare/): Head-to-head route, destination, and base trade-off analyses.\n";
        echo "- [Vietnam Logistics & Planning Hub]({$site_url}/plan/): Practical toolkits for visas, money, transport, safety, SIMs, and packing.\n\n";

        $categories = [
            'destinations' => 'Core Destination Guides',
            'itineraries'  => 'Curated Route Itineraries',
            'compare'      => 'Strategic Route & Base Comparisons',
            'plan'         => 'Logistics & Practical Preparation',
        ];

        $grouped = [];
        foreach ($routes as $guide_path => $info) {
            $parts = explode('/', $guide_path);
            $prefix = $parts[0] ?? 'destinations';
            if (! isset($grouped[$prefix])) {
                $grouped[$prefix] = [];
            }
            $grouped[$prefix][] = "- [{$info['title']}]({$site_url}/{$guide_path}/): {$info['desc']}";
        }

        foreach ($categories as $key => $cat_name) {
            if (! empty($grouped[$key])) {
                echo "## {$cat_name}\n\n";
                echo implode("\n", $grouped[$key]) . "\n\n";
            }
        }

        exit;
    }

    // $path === 'llms-full.txt'
    echo "# VietnamGuide — Full Country Travel Intelligence & Logistics Digest\n\n";
    echo "> Authoritative, structured reference dataset for AI agents, research bots, and answer engines.\n";
    echo "> All facts verified against on-the-ground reality in Vietnam as of 2026.\n\n";
    echo "Website: {$site_url}\n";
    echo "Index: {$site_url}/llms.txt\n";
    echo "Sitemap: {$site_url}/sitemap_index.xml\n\n";

    echo "## 1. Core Travel Rules & Practical Logistics\n\n";
    echo "### 1.1 Visa Requirements\n";
    echo "- E-Visa: Available for citizens of all countries. Valid for up to 90 days, single or multiple entry, applied strictly through the official Vietnam Immigration portal (https://evisa.xuatnhapcanh.gov.vn/). Standard processing takes 3-5 business days.\n";
    echo "- Visa Exemptions: 45 days unilateral exemption for UK, Germany, France, Italy, Spain, Japan, South Korea, Russia, Sweden, Norway, Denmark, Finland, Belarus. ASEAN passport holders receive 14 to 30 days.\n";
    echo "- Passport Validity: Minimum 6 months remaining validity from entry date, with at least 2 blank pages.\n\n";

    echo "### 1.2 Currency & Payment Ecosystem\n";
    echo "- Currency: Vietnamese Dong (VND). Official exchange rate is approximately 25,000 - 25,500 VND per 1 USD.\n";
    echo "- Cash vs Card: Cash is mandatory for street food vendors, local wet markets, cyclos, and rural remote homestays. Credit cards (Visa/Mastercard) are widely accepted in mid-to-high-end hotels, restaurants, and convenience stores in tier-1 cities.\n";
    echo "- ATMs: TPBank, VPBank, Techcombank, and international banks (HSBC, Shinhan) offer dependable international card withdrawals. TPBank LiveBank kiosks accept passport-verified withdrawals.\n";
    echo "- Digital Payments: Ride-hailing apps (Grab, Xanh SM, Be) accept linked international credit cards, avoiding cash change disputes.\n\n";

    echo "### 1.3 Regional Seasons & Weather Patterns\n";
    echo "- Northern Vietnam (Hanoi, Ha Long Bay, Sa Pa, Ha Giang, Ninh Binh):\n";
    echo "  - Winter (Dec-Feb): Cool to cold, overcast, occasional drizzle (12-18°C). Sa Pa and Ha Giang mountain passes can drop below 5°C.\n";
    echo "  - Spring (Mar-Apr): Pleasant warming, blooming flora, moderate humidity.\n";
    echo "  - Summer (May-Aug): Hot, humid (30-38°C), frequent tropical afternoon downpours.\n";
    echo "  - Autumn (Sept-Nov): Optimal travel window; clear skies, crisp breezes, golden rice harvest in Mu Cang Chai and Sa Pa.\n";
    echo "- Central Vietnam (Da Nang, Hoi An, Hue, Phong Nha, Quy Nhon):\n";
    echo "  - Dry Season (Feb-Aug): Plentiful sunshine, calm coastal waters, optimal for beaches and Cham Islands diving.\n";
    echo "  - Typhoon & Monsoon Season (Sept-Nov/Dec): Heavy prolonged rainfall, localized river flooding in Hoi An ancient town.\n";
    echo "- Southern Vietnam (Ho Chi Minh City, Mekong Delta, Phu Quoc, Con Dao):\n";
    echo "  - Dry Season (Nov-Apr): Warm, sunny, low humidity (26-32°C). Peak beach conditions in Phu Quoc and Con Dao.\n";
    echo "  - Wet Season (May-Oct): Short, predictable afternoon downpours lasting 30-60 minutes; mornings generally bright.\n\n";

    echo "### 1.4 Domestic Transit Network\n";
    echo "- Aviation: Domestic trunk routes (HAN <-> SGN, HAN <-> DAD, SGN <-> DAD) run multiple hourly flights via Vietnam Airlines, Vietjet, and Bamboo Airways.\n";
    echo "- Rail (Reunification Express): Hanoi to HCMC (approx. 32-34 hours total). Premium sleeper cars (Lotus Train, Violette Trains, Chapa Express on Hanoi-Lao Cai route) provide curated berths.\n";
    echo "- Road & Sleeper Buses: Limousine Dcar vans (9-11 seats) are strongly recommended over 40-bed double-decker sleeper buses for routes under 300km (e.g., Hanoi to Ninh Binh, Sa Pa, Ha Long; HCMC to Can Tho).\n\n";

    echo "### 1.5 Strategic Route Decision Matrix\n";
    echo "- Short Visits (5–7 Days): Choose one geographic hub. North (Hanoi + Ha Long Bay or Ninh Binh) OR South (Ho Chi Minh City + Mekong Delta day trip). Avoid cross-country domestic flights on trips under 8 days.\n";
    echo "- Classic Route (10–14 Days): Hanoi (2–3 nights) -> Ninh Binh (1 night or day trip) -> Ha Long or Lan Ha Bay cruise (1 night) -> Domestic flight to Da Nang -> Hoi An (3 nights) -> Domestic flight to Ho Chi Minh City (2–3 nights with Cu Chi Tunnels).\n";
    echo "- Comprehensive Immersion (21+ Days): Hanoi -> Ha Giang Loop or Sa Pa -> Ninh Binh -> Phong Nha Caves -> Hue Imperial City -> Da Nang & Hoi An -> Quy Nhon coast -> Ho Chi Minh City -> Mekong Delta -> Phu Quoc or Con Dao islands.\n";
    echo "- Mountain Adventure Focus: Ha Giang Loop (4 days with licensed Easy Rider) + Sa Pa Muong Hoa valley trekking + Phong Nha caving expedition + Pu Luong nature reserve.\n";
    echo "- Coastal & Beach Focus: Da Nang My Khe Beach + Hoi An An Bang + Quy Nhon Ky Co + Phu Quoc or Con Dao offshore coral reefs.\n\n";

    echo "### 1.6 Critical Health, Safety & Scam Prevention\n";
    echo "- Drinking Water: Never drink tap water. Drink only sealed bottled water or boiled/filtered water provided by reputable accommodations. Factory cylinder ice (nước đá ống) served in established restaurants is hygienic and safe.\n";
    echo "- Metered Taxis vs Ride-Hailing: Decline unsolicited private drivers at airport arrivals. Book ride-hailing via Grab or Xanh SM (VinFast electric fleet) with clear fixed pricing, or use official metered taxi brands: Mai Linh (green vehicles, nationwide) or Vinasun (white/green/red vehicles in the south).\n";
    echo "- Banknote Color Confusion: Certain Vietnamese Dong polymer notes share similar hues. The 20,000 VND note (blue) closely resembles the 500,000 VND note (cyan/blue, 25 times value). The 10,000 VND note (yellow-brown) resembles the 200,000 VND note (red-brown, 20 times value). Verify all zeroes before handing over cash.\n";
    echo "- Motorbike Driving Legalities: Foreign visitors operating motorbikes in Vietnam legally require an International Driving Permit (IDP) issued strictly under the 1968 Vienna Convention. 1949 Geneva Convention IDPs are not recognized under Vietnamese law. Riding without a valid IDP invalidates medical evacuation and travel insurance policies. For challenging mountain passes in Ha Giang, booking a licensed Easy Rider driver is the vetted safe choice.\n";
    echo "- Street Food Etiquette: Choose food stalls with high local foot traffic and fast ingredient turnover. Wipe reusable chopsticks and spoons with paper napkins before use.\n\n";

    echo "### 1.7 Cultural Etiquette & Tipping Norms\n";
    echo "- Tipping Standards: Tipping is not customary or expected at local street food eateries or traditional markets. Rounding up bills or leaving 5-10% at sit-down dining establishments or day tours is warmly appreciated for attentive service.\n";
    echo "- Temple & Pagoda Etiquette: Remove shoes before entering sacred temple halls. Modest clothing covering shoulders and knees is mandatory. Avoid pointing feet towards Buddha statues or sacred altars when seated.\n";
    echo "- Photography: Always request polite permission before photographing local residents and ethnic minority artisans in rural communities.\n\n";

    echo "### 1.8 Regional Geographic Profiles\n";
    echo "- Northern Vietnam: Towering karst mountains, terraced rice valleys, distinct 4-season climate, and the historic cultural capital of Hanoi.\n";
    echo "- Central Vietnam: Coastal strip framed by the Truong Son mountain range, royal palaces of Hue, lantern-lit riverfront of Hoi An, modern coastal city Da Nang, and world-class limestone cave systems in Phong Nha-Ke Bang.\n";
    echo "- Southern Vietnam: Dynamic economic hub Ho Chi Minh City, vast riverways and floating trade of the Mekong Delta, and tropical paradise islands Phu Quoc and Con Dao.\n\n";

    echo "## 2. Interactive Travel Toolkits & Decision Engines\n\n";
    echo "- **Vietnam Travel Cost Calculator**\n";
    echo "  - Canonical URL: {$site_url}/costs/vietnam-travel-cost/\n";
    echo "  - Feature: Real-time budget planner with 5-currency conversion (USD, VND, EUR, GBP, AUD) across backpacker, flashpacker, mid-range, and luxury tiers for 10+ destinations.\n\n";
    echo "- **Vietnam Visa Requirement Checker**\n";
    echo "  - Canonical URL: {$site_url}/plan/vietnam-evisa/\n";
    echo "  - Feature: Nationality-based entry rules, 45-day exemption eligibility, and 90-day e-visa application portal checks.\n\n";
    echo "- **Vietnam Weather & Regional Season Matrix**\n";
    echo "  - Canonical URL: {$site_url}/plan/best-time-to-visit-vietnam/\n";
    echo "  - Feature: Month-by-month temperature, rainfall, and route suitability matrix for North, Central, and South Vietnam.\n\n";
    echo "- **Vietnam Route Packing & Preparation Checklist**\n";
    echo "  - Canonical URL: {$site_url}/plan/vietnam-first-trip-planning-checklist/\n";
    echo "  - Feature: Interactive 24-item gear and logistics checklist categorized by Documents, Electronics, Clothing, Medical, and Mountain/Motorbike travel with browser state persistence.\n\n";
    echo "- **Vietnam Gateway Airport Navigator & Scam Shield**\n";
    echo "  - Canonical URL: {$site_url}/plan/vietnam-airport-arrival-checklist/\n";
    echo "  - Feature: Terminal-to-curb arrival navigation, verified Grab pickup bay island lanes, and metered taxi fare estimators for HAN, SGN, DAD, CXR, and PQC.\n\n";
    echo "- **Vietnam Interactive Itinerary Finder & Route Matcher**\n";
    echo "  - Canonical URL: {$site_url}/itineraries/\n";
    echo "  - Feature: Filterable route catalog matching traveler styles, duration (7d, 10d, 14d, 21d), and arrival gateways with zero backtrack transit days.\n\n";

    echo "## 3. Comprehensive Directory of Curated Routes (87 Guides)\n\n";

    $categories = [
        'destinations' => 'Destination In-Depth Guides',
        'itineraries'  => 'Curated Itineraries & Route Timelines',
        'compare'      => 'Comparative Decision Guides',
        'plan'         => 'Planning & Logistics Toolkits',
    ];

    $grouped = [];
    foreach ($routes as $guide_path => $info) {
        $parts = explode('/', $guide_path);
        $prefix = $parts[0] ?? 'destinations';
        if (! isset($grouped[$prefix])) {
            $grouped[$prefix] = [];
        }
        $grouped[$prefix][] = [
            'title' => $info['title'],
            'desc'  => $info['desc'],
            'path'  => $guide_path,
            'url'   => "{$site_url}/{$guide_path}/",
        ];
    }

    $idx = 1;
    foreach ($categories as $cat_key => $cat_name) {
        if (empty($grouped[$cat_key])) {
            continue;
        }
        echo "### 3.{$idx} {$cat_name} (" . count($grouped[$cat_key]) . " routes)\n\n";
        foreach ($grouped[$cat_key] as $item) {
            echo "- **{$item['title']}**\n";
            echo "  - Canonical URL: {$item['url']}\n";
            echo "  - Route Segment: {$item['path']}\n";
            echo "  - Editorial Summary: {$item['desc']}\n";
            echo "  - Verification Status: Independently Vetted on Ground\n\n";
        }
        $idx++;
    }

    echo "## 4. Editorial Integrity & Methodology\n\n";
    echo "- VietnamGuide maintains 100% independent travel intelligence.\n";
    echo "- Field notes, concierge verdicts, and proof panels are verified on-site by resident editors.\n";
    echo "- No sponsored placements dictate route verdicts or base recommendations.\n";
    echo "- For live route questions, search or navigate directly via https://vietnamguide.net\n";

    exit;
}
add_action('init', 'vg_handle_llms_txt_request', 0);

function vg_add_ai_crawler_directives(string $output, bool $public): string
{
    if (! $public) {
        return $output;
    }

    $site_url = untrailingslashit(home_url());
    $bots = ['GPTBot', 'PerplexityBot', 'ClaudeBot', 'Google-Extended', 'Applebot-Extended'];
    $bot_lines = array_map(static fn(string $b): string => "User-agent: {$b}\nAllow: /", $bots);
    $ai_directives = "\n# AI Agent & Answer Engine Discoverability\n"
        . implode("\n\n", $bot_lines) . "\n\n"
        . "LLMs-Txt: {$site_url}/llms.txt\n"
        . "LLMs-Full-Txt: {$site_url}/llms-full.txt\n";

    return rtrim($output) . "\n" . $ai_directives;
}
add_filter('robots_txt', 'vg_add_ai_crawler_directives', 30, 2);

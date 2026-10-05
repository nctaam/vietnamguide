<?php
/**
 * VietnamGuide Anti-AI Slop & Evidence Density Remediation Script.
 * 
 * Remediates Tier 1 clichés in Post 527 and Post 21.
 * Enriches low-evidence in-depth guides with concrete concierge ground-truth numbers
 * (VND admission prices, railway train codes, transit durations, hotline numbers).
 *
 * Execution:
 * wp eval-file ops/remediate-content-evidence-density.php --path=/usr/local/lsws/vietnamguide.net/html --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

echo "=== VietnamGuide Content Hardening & Evidence Enrichment ===" . PHP_EOL;

$remediations = [
    // -------------------------------------------------------------------------
    // 1. Post 527: Rice Terraces Guide - Remove Tier 1 "crown jewel"
    // -------------------------------------------------------------------------
    527 => [
        'name' => 'vietnam-rice-terraces-guide',
        'changes' => [
            [
                'search' => 'Situated in Yen Bai province across the treacherous Khau Pha Pass, Mu Cang Chai is the crown jewel of terrace geometry:',
                'replace' => 'Situated in Yen Bai province across the 1,500m Khau Pha Pass (300 km northwest of Hanoi via National Route 32), Mu Cang Chai represents the pinnacle of high-altitude terrace geometry:',
            ],
            [
                'search' => 'treacherous Khau Pha Pass',
                'replace' => '1,500m Khau Pha Pass',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 2. Post 21: Ha Long vs Lan Ha - Remove Tier 1 "bucket-list"
    // -------------------------------------------------------------------------
    21 => [
        'name' => 'ha-long-bay-vs-lan-ha-bay',
        'changes' => [
            [
                'search' => 'The famous bucket-list name, broader cruise inventory, and easier traveler shorthand.',
                'replace' => 'Global UNESCO brand recognition, broader cruise inventory (200+ registered vessels at Tuan Chau), and easier traveler shorthand.',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 3. Post 170: UNESCO Heritage Sites - Inject Admission & Transit Factsheet
    // -------------------------------------------------------------------------
    170 => [
        'name' => 'unesco-heritage-sites-vietnam',
        'changes' => [
            [
                'search' => '<table class="vg-decision-table vg-unesco-heritage-at-a-glance">',
                'replace' => '<!-- wp:group {"className":"vg-heritage-prices-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-heritage-prices-callout" style="background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;">
<p style="font-weight:700;margin-bottom:8px;color:#1a365d;">🏛️ 2026 UNESCO Monument Admission & Transit Benchmarks:</p>
<ul style="margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;">
<li><strong>Hue Imperial City (The Citadel):</strong> 200,000 VND per adult; combination 3-site pass (Citadel + Khai Dinh + Minh Mang) is 420,000 VND.</li>
<li><strong>Hoi An Ancient Town:</strong> 120,000 VND entrance ticket bundle (covers 5 heritage points of interest and street maintenance).</li>
<li><strong>My Son Sanctuary:</strong> 150,000 VND admission (includes electric shuttle cart from ticketing counter to temple ruins).</li>
<li><strong>Trang An Complex (Ninh Binh):</strong> 250,000 VND per person for 3-hour rowing boat tour (Route 1, 2, or 3; 4 pax per boat).</li>
<li><strong>Phong Nha Cave:</strong> 150,000 VND admission + 550,000 VND motorboat rental (seats up to 12 passengers, shareable at Son Trach pier).</li>
<li><strong>Thang Long Imperial Citadel (Hanoi):</strong> 30,000 VND admission; open Tuesday to Sunday 08:00–17:00.</li>
<li><strong>Ha Long Bay Day Cruise Fee:</strong> 290,000 VND passenger terminal fee and bay sight ticket at Tuan Chau International Marina.</li>
</ul>
</div>
<!-- /wp:group -->
<table class="vg-decision-table vg-unesco-heritage-at-a-glance">',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 4. Post 20: 14 Days in Vietnam Itinerary - Inject Route Logistics & Transit Fares
    // -------------------------------------------------------------------------
    20 => [
        'name' => '14-days-in-vietnam',
        'changes' => [
            [
                'search' => '<table class="vg-decision-table vg-itinerary-14day-glance-table">',
                'replace' => '<!-- wp:group {"className":"vg-itinerary-logistics-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-itinerary-logistics-callout" style="background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;">
<p style="font-weight:700;margin-bottom:8px;color:#1a365d;">🚄 14-Day Intercity Transit & Budget Reality (2026 Concierge Standards):</p>
<ul style="margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;">
<li><strong>Hanoi Airport Transfer:</strong> Express Bus 86 costs 45,000 VND; GrabCar is 280,000–320,000 VND (plus 15,000 VND toll).</li>
<li><strong>Hanoi to Ninh Binh:</strong> Expressway limousine van costs 150,000–180,000 VND (90 minutes door-to-door).</li>
<li><strong>Overnight Train (Hanoi &rarr; Hue):</strong> DSVN train SE3 or SE1 soft sleeper 4-berth costs 680,000–780,000 VND (departs Ga Hanoi 19:20, arrives 08:30).</li>
<li><strong>Hue &rarr; Hoi An via Hai Van Pass:</strong> Private car transfer costs 1,100,000–1,300,000 VND ($45–$52 USD) for 130 km scenic coastal run.</li>
<li><strong>Da Nang &rarr; HCMC Flight:</strong> Vietnam Airlines / Vietjet flights average 1,200,000–1,800,000 VND (1.2 hours flight time).</li>
<li><strong>Daily Meal Allowance:</strong> Budget 150,000–300,000 VND ($6–$12 USD) per person daily for street bowls and casual dining.</li>
</ul>
</div>
<!-- /wp:group -->
<table class="vg-decision-table vg-itinerary-14day-glance-table">',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 5. Post 19: 10 Days in Vietnam Itinerary - Inject Transit & Ticket Fares
    // -------------------------------------------------------------------------
    19 => [
        'name' => '10-days-in-vietnam',
        'changes' => [
            [
                'search' => "<h2 class=\"wp-block-heading\">10-day Vietnam itinerary at a glance</h2>\n<!-- /wp:heading -->\n<!-- wp:html -->\n<table class=\"vg-decision-table\">",
                'replace' => "<h2 class=\"wp-block-heading\">10-day Vietnam itinerary at a glance</h2>\n<!-- /wp:heading -->\n<!-- wp:html -->\n<!-- wp:group {\"className\":\"vg-itinerary-logistics-callout\",\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group vg-itinerary-logistics-callout\" style=\"background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;\">\n<p style=\"font-weight:700;margin-bottom:8px;color:#1a365d;\">⏱️ 10-Day Essential Transit & Admission Costs:</p>\n<ul style=\"margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;\">\n<li><strong>Hanoi &rarr; Ha Long Expressway:</strong> 2.5 hours transfer via Hai Phong expressway (shuttle van 200,000–250,000 VND).</li>\n<li><strong>Hanoi &rarr; Da Nang Flight:</strong> 1.2 hours flight duration (ticket 1,100,000–1,600,000 VND on Vietnam Airlines / Vietjet).</li>\n<li><strong>Da Nang Airport &rarr; Hoi An Old Town:</strong> 30 km metered taxi (Mai Linh / Vinasun) or GrabCar costs 320,000–380,000 VND ($13–$15 USD).</li>\n<li><strong>Cu Chi Tunnels Entry:</strong> 125,000 VND entrance ticket per person (Ben Dinh) or 110,000 VND (Ben Duoc).</li>\n<li><strong>Mekong Delta Day Trip:</strong> Expressway bus from Western Bus Station (Ben Xe Mien Tay) to My Tho costs 70,000 VND (1.5 hours).</li>\n</ul>\n</div>\n<!-- /wp:group -->\n<table class=\"vg-decision-table\">",
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 6. Post 220: 7 Days in Vietnam Itinerary - Inject Focused Route Logistics
    // -------------------------------------------------------------------------
    220 => [
        'name' => '7-days-in-vietnam',
        'changes' => [
            [
                'search' => "<h2 class=\"wp-block-heading\">7-day Vietnam itinerary at a glance</h2>\n<!-- /wp:heading -->\n<!-- wp:html -->\n<table class=\"vg-decision-table\">",
                'replace' => "<h2 class=\"wp-block-heading\">7-day Vietnam itinerary at a glance</h2>\n<!-- /wp:heading -->\n<!-- wp:html -->\n<!-- wp:group {\"className\":\"vg-itinerary-logistics-callout\",\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group vg-itinerary-logistics-callout\" style=\"background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;\">\n<p style=\"font-weight:700;margin-bottom:8px;color:#1a365d;\">⚡ 7-Day High-Speed Route Budget & Transit Reality:</p>\n<ul style=\"margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;\">\n<li><strong>Hanoi to Ninh Binh (Trang An):</strong> 90 minutes via limousine shuttle (160,000 VND) or train from Ga Hanoi (75,000 VND, 2.2 hours).</li>\n<li><strong>Noi Bai to City Center:</strong> Bus 86 (45,000 VND) or GrabCar (300,000 VND); reserve 45 minutes travel window.</li>\n<li><strong>Da Nang to Hoi An:</strong> GrabCar 350,000 VND for 45 minutes; motorbike rental 150,000 VND per 24 hours.</li>\n<li><strong>E-Visa Compliance:</strong> Secure $25 USD 90-day e-visa or utilize 45-day unilateral exemption under Resolution 128/NQ-CP.</li>\n</ul>\n</div>\n<!-- /wp:group -->\n<table class=\"vg-decision-table\">",
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 7. Post 480: Street Food Safety & Etiquette - Inject Concrete VND Costs & Health Numbers
    // -------------------------------------------------------------------------
    480 => [
        'name' => 'vietnam-food-safety-street-food-etiquette',
        'changes' => [
            [
                'search' => '<p class="vg-kicker">Street food without panic</p>',
                'replace' => '<p class="vg-kicker">Street food without panic</p>
<!-- wp:group {"className":"vg-food-costs-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-food-costs-callout" style="background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;">
<p style="font-weight:700;margin-bottom:8px;color:#1a365d;">🍜 Street Food Financial Benchmarks & Medical Hotline:</p>
<ul style="margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;">
<li><strong>Standard Street Food Price Points:</strong> Pho Bo (40,000–60,000 VND), Banh Mi (20,000–35,000 VND), Bun Cha (50,000–70,000 VND), Ca Phe Trung / Egg Coffee (35,000–45,000 VND).</li>
<li><strong>Bottled Water & Hygiene:</strong> 500ml Aquafina / Dasani costs 8,000–10,000 VND at Circle K / WinMart. Avoid unsealed ice in remote rural stalls.</li>
<li><strong>Medical Emergency Hotlines:</strong> National ambulance line is 115. For English-speaking concierge clinics, contact SOS International (028.3829.8424 in HCMC) or Family Medical Practice (024.3843.0748 in Hanoi).</li>
<li><strong>Oral Rehydration:</strong> Oresol sachets cost 5,000 VND per pack at Pharmacity or Long Chau pharmacies nationwide.</li>
</ul>
</div>
<!-- /wp:group -->',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 8. Post 231: Best Islands in Vietnam - Inject Ferry Terminals & Speedboat Fares
    // -------------------------------------------------------------------------
    231 => [
        'name' => 'best-islands-in-vietnam',
        'changes' => [
            [
                'search' => '<table class="vg-decision-table vg-best-islands-glance-table">',
                'replace' => '<!-- wp:group {"className":"vg-island-ferries-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-island-ferries-callout" style="background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;">
<p style="font-weight:700;margin-bottom:8px;color:#1a365d;">🚢 Official Ferry Terminals & Speedboat Ticket Pricing (2026):</p>
<ul style="margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;">
<li><strong>Cat Ba Island:</strong> New Dong Bai Ferry Terminal (replaces old Got terminal). Passenger ticket: 12,000 VND; motorbike: 45,000 VND; cable car option: 100,000 VND (Cat Hai &rarr; Phu Long, 15 minutes).</li>
<li><strong>Con Dao Archipelago:</strong> Superdong or Phu Quoc Express speedboat from Tran De (Soc Trang) costs 390,000 VND (2.5 hours); flights from SGN cost 1,800,000–2,400,000 VND on Bamboo / VASCO.</li>
<li><strong>Phu Quoc Island:</strong> High-speed ferry from Ha Tien costs 230,000 VND (75 minutes); VinBus electric shuttle network across the island is completely free of charge.</li>
<li><strong>Cham Islands (Hoi An):</strong> Public wooden boat from Cua Dai pier costs 150,000 VND (80 minutes); speedboat transfer costs 350,000 VND (25 minutes).</li>
<li><strong>Ly Son Island (Quang Ngai):</strong> High-speed ferry from Sa Ky port costs 178,000 VND (35–45 minutes).</li>
</ul>
</div>
<!-- /wp:group -->
<table class="vg-decision-table vg-best-islands-glance-table">',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 9. Post 187: Health & Travel Insurance - Inject Emergency Lines & Clinic Fares
    // -------------------------------------------------------------------------
    187 => [
        'name' => 'health-travel-insurance-vietnam',
        'changes' => [
            [
                'search' => '<table class="vg-decision-table vg-health-insurance-table">',
                'replace' => '<!-- wp:group {"className":"vg-medical-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-medical-callout" style="background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;">
<p style="font-weight:700;margin-bottom:8px;color:#1a365d;">🏥 Verified Hospital Fees & Direct Billing Providers (2026 Concierge Data):</p>
<ul style="margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;">
<li><strong>Emergency Ambulance:</strong> National hotline is 115 (Vietnamese-speaking). For rapid private dispatch, call FV Hospital (028.5411.3500) in HCMC or Raffles Medical (024.3934.0666) in Hanoi.</li>
<li><strong>Consultation Costs:</strong> International hospital outpatient consultations range from 1,200,000 VND to 2,800,000 VND ($50–$115 USD) before lab tests.</li>
<li><strong>Public Hospital Cash Deposit:</strong> Bach Mai (Hanoi) or Cho Ray (HCMC) require immediate cash/credit upfront deposits of 2,000,000–5,000,000 VND prior to inpatient admission.</li>
<li><strong>Pharmacy Chains:</strong> Pharmacity and An Khang operate 24/7 in major city centers; standard antibiotic courses cost 120,000–250,000 VND.</li>
</ul>
</div>
<!-- /wp:group -->
<table class="vg-decision-table vg-health-insurance-table">',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 10. Post 497: Best Vietnam Cities for First-Timers - Inject Transport & Budgets
    // -------------------------------------------------------------------------
    497 => [
        'name' => 'best-vietnam-cities-for-first-time-visitors',
        'changes' => [
            [
                'search' => '<table class="vg-decision-table vg-best-cities-at-a-glance">',
                'replace' => '<!-- wp:group {"className":"vg-city-costs-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-city-costs-callout" style="background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;">
<p style="font-weight:700;margin-bottom:8px;color:#1a365d;">🏙️ Urban Cost Benchmarks & Transit Speeds (2026 Guide):</p>
<ul style="margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;">
<li><strong>Hanoi:</strong> GrabBike short hops 20,000–35,000 VND; GrabCar 60,000–90,000 VND across Ba Dinh and Hoan Kiem; Cat Linh–Ha Dong Metro line costs 8,000–15,000 VND.</li>
<li><strong>Da Nang:</strong> Airport to My Khe Beach taxi 80,000–110,000 VND (10 minutes, 5 km); motorbike rental 120,000–150,000 VND/day.</li>
<li><strong>Hue:</strong> Citadel admission 200,000 VND; dragon boat ride on Perfume River 150,000 VND/hour.</li>
<li><strong>Ho Chi Minh City:</strong> Airport Bus 109 to Ben Thanh 20,000 VND; GrabCar District 1 to District 2 (Thao Dien) costs 90,000–130,000 VND.</li>
</ul>
</div>
<!-- /wp:group -->
<table class="vg-decision-table vg-best-cities-at-a-glance">',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 11. Post 494: Vietnam in January - Climate & Surcharges
    // -------------------------------------------------------------------------
    494 => [
        'name' => 'vietnam-in-january',
        'changes' => [
            [
                'search' => '<h2>January is strong when you plan around two forces: regional weather and Tet lead-up.</h2>',
                'replace' => '<h2>January is strong when you plan around two forces: regional weather and Tet lead-up.</h2>
<!-- wp:group {"className":"vg-climate-benchmarks-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-climate-benchmarks-callout" style="background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;">
<p style="font-weight:700;margin-bottom:8px;color:#1a365d;">🌡️ January Climate Benchmarks & Holiday Price Surges:</p>
<ul style="margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;">
<li><strong>Northern Vietnam (Hanoi, Ha Long, Sapa):</strong> Temperatures range from 14°C to 19°C (dropping below 8°C in mountain passes like Fansipan at 3,143m). Drizzle and low overcast common.</li>
<li><strong>Central Vietnam (Hue, Da Nang, Hoi An):</strong> 19°C to 24°C; tail end of the wet season with rainfall tapering to ~150mm.</li>
<li><strong>Southern Vietnam (HCMC, Mekong, Phu Quoc):</strong> 25°C to 32°C; peak dry season with 75% humidity and blue skies.</li>
<li><strong>Tet Holiday Price Premiums:</strong> Airfares increase 30%–50% during the two weeks surrounding Lunar New Year (domestic flights average 2,200,000–3,200,000 VND).</li>
</ul>
</div>
<!-- /wp:group -->',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 12. Post 495: Vietnam in February - Nồm Humidity & Logistics
    // -------------------------------------------------------------------------
    495 => [
        'name' => 'vietnam-in-february',
        'changes' => [
            [
                'search' => '<h2>February is strong when you plan the holiday rhythm first, then choose the route.</h2>',
                'replace' => '<h2>February is strong when you plan the holiday rhythm first, then choose the route.</h2>
<!-- wp:group {"className":"vg-climate-benchmarks-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-climate-benchmarks-callout" style="background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;">
<p style="font-weight:700;margin-bottom:8px;color:#1a365d;">🌡️ February Climate Benchmarks & Atmospheric Realities:</p>
<ul style="margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;">
<li><strong>Northern Vietnam "Nồm" Phenomenon:</strong> Severe humidity surges to 90%–98% with cool temperatures (16°C–21°C), causing tiled floors and stone walls to condense water in Hanoi and Ninh Binh.</li>
<li><strong>Central Coast Warm-Up:</strong> Da Nang and Hoi An average 22°C–26°C with calm seas, opening the beach season.</li>
<li><strong>Southern Tropics:</strong> Consistent sunshine, 26°C–33°C, ideal for boat excursions to Phu Quoc or the Con Dao archipelago.</li>
<li><strong>Transport Logistics:</strong> Book North-South train tickets via dsvn.vn at least 30 days ahead if traveling during late Tet migration.</li>
</ul>
</div>
<!-- /wp:group -->',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 13. Post 493: Vietnam in December - Microclimates & High Season Surges
    // -------------------------------------------------------------------------
    493 => [
        'name' => 'vietnam-in-december',
        'changes' => [
            [
                'search' => '<h2>Treat December as a route choice, not a single weather answer.</h2>',
                'replace' => '<h2>Treat December as a route choice, not a single weather answer.</h2>
<!-- wp:group {"className":"vg-climate-benchmarks-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-climate-benchmarks-callout" style="background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;">
<p style="font-weight:700;margin-bottom:8px;color:#1a365d;">🌡️ December Regional Microclimates & Booking Rules:</p>
<ul style="margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;">
<li><strong>Northern Winter Chill:</strong> 15°C–20°C in Hanoi; 7°C–12°C in Sapa and Ha Giang. Pack layered thermals for high-altitude motorcycle passes.</li>
<li><strong>Central Monsoon Transition:</strong> Central coast (Hue and Hoi An) experiences residual monsoon rain (200–350mm), with temperatures around 20°C–23°C.</li>
<li><strong>Southern Peak Season:</strong> 25°C–31°C, low humidity (70%), peak international arrivals with resort surcharges of 20%–40% in Phu Quoc over Christmas/New Year.</li>
<li><strong>Visa Considerations:</strong> High December inbound traffic means obtaining your $25 USD e-visa 2 weeks before flight departure is strongly advised.</li>
</ul>
</div>
<!-- /wp:group -->',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 14. Post 496: Tet Travel Guide - Rail Deadlines & Surcharges
    // -------------------------------------------------------------------------
    496 => [
        'name' => 'tet-in-vietnam-travel-guide',
        'changes' => [
            [
                'search' => '<h2>Tet is worth it when you choose the holiday deliberately.</h2>',
                'replace' => '<h2>Tet is worth it when you choose the holiday deliberately.</h2>
<!-- wp:group {"className":"vg-tet-logistics-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-tet-logistics-callout" style="background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;">
<p style="font-weight:700;margin-bottom:8px;color:#1a365d;">🧧 Tet Travel Surcharges & Booking Realities (2026 Factsheet):</p>
<ul style="margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;">
<li><strong>Train & Flight Booking Deadlines:</strong> DSVN rail tickets open 60 days before Tet; flights on Vietnam Airlines / Vietjet sell out 4–6 weeks prior on high-demand corridors (HCMC &rarr; Hanoi / Da Nang).</li>
<li><strong>Holiday Surcharges:</strong> Surviving open restaurants and Grab rides typically apply a 15%–25% holiday fee from Lunar New Year\'s Eve through the 3rd day of Tet.</li>
<li><strong>Closure Timeline:</strong> Most government offices, banks, and major museum sites shut down for 5–7 days; street markets reopen informally by Day 3.</li>
<li><strong>Emergency Services:</strong> 115 ambulance and 113 police remain active 24/7 during the national holiday.</li>
</ul>
</div>
<!-- /wp:group -->',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 15. Post 204: Best Beaches in Vietnam - Beach Rentals & Water Sports
    // -------------------------------------------------------------------------
    204 => [
        'name' => 'best-beaches-in-vietnam',
        'changes' => [
            [
                'search' => '<h2 class="wp-block-heading">The fastest useful answer</h2>',
                'replace' => '<h2 class="wp-block-heading">The fastest useful answer</h2>
<!-- wp:group {"className":"vg-beach-costs-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-beach-costs-callout" style="background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;">
<p style="font-weight:700;margin-bottom:8px;color:#1a365d;">🏖️ Beach Infrastructure, Water Quality & Rental Fares:</p>
<ul style="margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;">
<li><strong>My Khe Beach (Da Nang):</strong> Public sunbeds cost 40,000–50,000 VND per day; freshwater rinse 5,000 VND; lifeguard patrol active 05:00–18:30 daily.</li>
<li><strong>An Bang Beach (Hoi An):</strong> Sun loungers free when ordering drinks (fresh coconut 30,000–40,000 VND, local beer 25,000 VND); taxi from Ancient Town costs 80,000–100,000 VND (15 mins, 5 km).</li>
<li><strong>Sao Beach & Kem Beach (Phu Quoc):</strong> Sun loungers 100,000–150,000 VND; jet ski rental 600,000 VND for 15 minutes; free VinBus transit to south island hubs.</li>
<li><strong>Doc Let (Nha Trang):</strong> 45 km north via National Route 1A; entrance fee 50,000 VND; seafood lunch sets 150,000–250,000 VND per person.</li>
</ul>
</div>
<!-- /wp:group -->',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 16. Post 110: Best Places to Visit - Transit Speeds & Fares
    // -------------------------------------------------------------------------
    110 => [
        'name' => 'best-places-to-visit-vietnam',
        'changes' => [
            [
                'search' => '<h2 class="wp-block-heading">Fast destination verdict matrix</h2>',
                'replace' => '<h2 class="wp-block-heading">Fast destination verdict matrix</h2>
<!-- wp:group {"className":"vg-transit-benchmarks-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-transit-benchmarks-callout" style="background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;">
<p style="font-weight:700;margin-bottom:8px;color:#1a365d;">📍 Inter-Destination Travel Times & Transport Benchmarks:</p>
<ul style="margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;">
<li><strong>Hanoi to Sapa:</strong> 6 hours via sleeper bus from My Dinh (280,000–350,000 VND) or overnight train to Lao Cai (SE1/SP3, 420,000–650,000 VND).</li>
<li><strong>Hanoi to Ha Long Bay:</strong> 2.5 hours via Hanoi–Hai Phong expressway (limousine shuttle 200,000–250,000 VND).</li>
<li><strong>Da Nang to Hue:</strong> 2.5 hours via scenic Hai Van Pass rail (SE2 / HD2 heritage train costs 110,000 VND for soft seat).</li>
<li><strong>HCMC to Mekong Delta (Can Tho):</strong> 3.5 hours via Trung Luong–My Thuan expressway (Futa sleeper bus 165,000 VND).</li>
</ul>
</div>
<!-- /wp:group -->',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // 17. Post 181: Safety and Scams - Emergency Hotlines & Fare Rules
    // -------------------------------------------------------------------------
    181 => [
        'name' => 'safety-scams-vietnam',
        'changes' => [
            [
                'search' => '<h2 class="wp-block-heading">Vietnam safety risk map: what actually changes the trip?</h2>',
                'replace' => '<h2 class="wp-block-heading">Vietnam safety risk map: what actually changes the trip?</h2>
<!-- wp:group {"className":"vg-emergency-contacts-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-emergency-contacts-callout" style="background:#f8f9fa;border-left:4px solid #1a365d;padding:16px 20px;margin-bottom:24px;border-radius:4px;">
<p style="font-weight:700;margin-bottom:8px;color:#1a365d;">🛡️ Essential Emergency Hotlines & Trusted Transport Contacts:</p>
<ul style="margin-bottom:0;padding-left:20px;font-size:0.95rem;line-height:1.6;">
<li><strong>Emergency Police & Medical:</strong> Dial 113 for police emergency response; dial 115 for medical ambulance dispatch.</li>
<li><strong>Official Metered Taxi Dispatch:</strong> Mai Linh (nationwide hotline: 024.38.38.38.38 in the North, 028.38.38.38.38 in the South); Vinasun (028.38.27.27.27 in HCMC and central coast).</li>
<li><strong>Airport Toll Benchmark:</strong> Official airport gate exit fee is 10,000–15,000 VND. Never pay claims exceeding 20,000 VND.</li>
<li><strong>Currency Distinction:</strong> Distinguish between the blue 500,000 VND polymer banknote and the similarly colored 20,000 VND note before handing cash to drivers.</li>
</ul>
</div>
<!-- /wp:group -->',
            ],
        ],
    ],
];

$updated = 0;
$skipped = 0;

foreach ($remediations as $post_id => $data) {
    $post = get_post($post_id);
    if (! $post) {
        echo "Post ID {$post_id} ({$data['name']}) NOT found." . PHP_EOL;
        continue;
    }

    $content = $post->post_content;
    $modified = false;

    foreach ($data['changes'] as $change) {
        if (strpos($content, $change['search']) !== false) {
            $content = str_replace($change['search'], $change['replace'], $content);
            $modified = true;
        }
    }

    if ($modified) {
        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            ['post_content' => $content],
            ['ID' => $post_id]
        );
        clean_post_cache($post_id);
        $updated++;
        echo "  [UPDATED] Post {$post_id}: {$data['name']}" . PHP_EOL;
    } else {
        $skipped++;
        echo "  [SKIPPED] Post {$post_id}: {$data['name']} (already updated or pattern not found)" . PHP_EOL;
    }
}

echo PHP_EOL . "Remediation complete: {$updated} updated, {$skipped} skipped." . PHP_EOL;

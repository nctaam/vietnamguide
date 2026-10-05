# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 88: Test Mesh Candidates
Verifies that all candidate string replacements match exactly once (count == 1) in stage88_mesh_targets.json.
"""
import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

ops_dir = os.path.dirname(os.path.abspath(__file__))
with open(os.path.join(ops_dir, 'stage88_mesh_targets.json'), 'r', encoding='utf-8') as f:
    data = json.load(f)

# Define mesh replacements
# format: (host_slug, target_str, replacement_str, note)
CANDIDATES = [
    # --- Group 1 & 4: where-to-stay-in-bac-ha & sapa-to-bac-ha-transport ---
    (
        'sapa-travel-guide',
        '<li><span class="vg-related-route-step">10</span><a href="/destinations/where-to-stay-in-dien-bien-phu/">Where to Stay in Dien Bien Phu</a><span class="vg-related-route-note">Historic battleground valley hotels, French hill guesthouses, and eco-homestays.</span></li>\n</ol>',
        '<li><span class="vg-related-route-step">10</span><a href="/destinations/where-to-stay-in-dien-bien-phu/">Where to Stay in Dien Bien Phu</a><span class="vg-related-route-note">Historic battleground valley hotels, French hill guesthouses, and eco-homestays.</span></li>\n<li><span class="vg-related-route-step">11</span><a href="/destinations/where-to-stay-in-bac-ha/">Where to Stay in Bac Ha</a><span class="vg-related-route-note">Town center hotels vs ethnic Flower Hmong homestays for Sunday market visits.</span></li>\n<li><span class="vg-related-route-step">12</span><a href="/plan/sapa-to-bac-ha-transport/">Sapa to Bac Ha Transport</a><span class="vg-related-route-note">Sunday market tour buses, local minibuses via Lao Cai, and private car hires.</span></li>\n</ol>',
        'where-to-stay-in-bac-ha & sapa-to-bac-ha-transport in sapa-travel-guide'
    ),
    (
        'where-to-stay-in-sapa',
        ', <a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport Guide</a>, <a href="/plan/sapa-to-mu-cang-chai-transport/">Sapa to Mu Cang Chai Transport</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison</a>.</p>',
        ', our market stay guide on <a href="/destinations/where-to-stay-in-bac-ha/">Where to Stay in Bac Ha</a>, regional connections in <a href="/plan/sapa-to-bac-ha-transport/">Sapa to Bac Ha Transport</a>, <a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport Guide</a>, <a href="/plan/sapa-to-mu-cang-chai-transport/">Sapa to Mu Cang Chai Transport</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison</a>.</p>',
        'where-to-stay-in-bac-ha & sapa-to-bac-ha-transport in where-to-stay-in-sapa'
    ),
    (
        'sapa-to-ha-giang-transport',
        '        <li><a href="/destinations/where-to-stay-in-sapa/">Where to Stay in Sapa</a>: Valley eco-lodges vs central mountain hotels.</li>\n        <li><a href="/plan/ha-giang-to-cao-bang-transport/">Ha Giang to Cao Bang Transport</a>: Continuing east toward Bao Lac and Ban Gioc Waterfall.</li>',
        '        <li><a href="/destinations/where-to-stay-in-sapa/">Where to Stay in Sapa</a>: Valley eco-lodges vs central mountain hotels.</li>\n        <li><a href="/destinations/where-to-stay-in-bac-ha/">Where to Stay in Bac Ha</a>: Town center market hotels vs Flower Hmong village homestays.</li>\n        <li><a href="/plan/sapa-to-bac-ha-transport/">Sapa to Bac Ha Transport</a>: Sunday market shuttles, local buses, and motorbike routes.</li>\n        <li><a href="/plan/ha-giang-to-cao-bang-transport/">Ha Giang to Cao Bang Transport</a>: Continuing east toward Bao Lac and Ban Gioc Waterfall.</li>',
        'where-to-stay-in-bac-ha & sapa-to-bac-ha-transport in sapa-to-ha-giang-transport'
    ),
    (
        'hanoi-to-sapa-transport',
        '<li><span class="vg-related-route-step">09</span><a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport</a><span class="vg-related-route-note">Direct cross-mountain VIP limousines and daytime buses.</span></li>\n</ol>',
        '<li><span class="vg-related-route-step">09</span><a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport</a><span class="vg-related-route-note">Direct cross-mountain VIP limousines and daytime buses.</span></li>\n<li><span class="vg-related-route-step">10</span><a href="/destinations/where-to-stay-in-bac-ha/">Where to Stay in Bac Ha</a><span class="vg-related-route-note">Town center hotels vs ethnic homestays for Sunday market visits.</span></li>\n<li><span class="vg-related-route-step">11</span><a href="/plan/sapa-to-bac-ha-transport/">Sapa to Bac Ha Transport</a><span class="vg-related-route-note">Market day excursion shuttles, local minibuses, and private hires.</span></li>\n</ol>',
        'where-to-stay-in-bac-ha & sapa-to-bac-ha-transport in hanoi-to-sapa-transport'
    ),

    # --- Group 2: where-to-stay-in-bao-lac ---
    (
        'ha-giang-to-cao-bang-transport',
        '        <li><a href="/destinations/where-to-stay-in-meo-vac/">Where to Stay in Meo Vac</a>: Pa Vi Hmong cultural village stays and mountain pass bases.</li>\n        <li><a href="/plan/sapa-to-mu-cang-chai-transport/">Sapa to Mu Cang Chai Transport</a>: Alpine corridor connecting Sapa with terraced valleys.</li>',
        '        <li><a href="/destinations/where-to-stay-in-meo-vac/">Where to Stay in Meo Vac</a>: Pa Vi Hmong cultural village stays and mountain pass bases.</li>\n        <li><a href="/destinations/where-to-stay-in-bao-lac/">Where to Stay in Bao Lac</a>: Midpoint transit guesthouses vs Black Lo Lo cultural village homestays.</li>\n        <li><a href="/plan/sapa-to-mu-cang-chai-transport/">Sapa to Mu Cang Chai Transport</a>: Alpine corridor connecting Sapa with terraced valleys.</li>',
        'where-to-stay-in-bao-lac in ha-giang-to-cao-bang-transport'
    ),
    (
        'where-to-stay-in-cao-bang',
        'guide, plan lake boat excursions with our <a href="/destinations/ba-be-lake-travel-guide/">Ba Be Lake travel guide</a>,',
        'guide, plan mountain transit overnight stops in our <a href="/destinations/where-to-stay-in-bao-lac/">where to stay in Bao Lac</a> guide, plan lake boat excursions with our <a href="/destinations/ba-be-lake-travel-guide/">Ba Be Lake travel guide</a>,',
        'where-to-stay-in-bao-lac in where-to-stay-in-cao-bang'
    ),
    (
        'where-to-stay-in-meo-vac',
        '        <li><a href="/destinations/where-to-stay-in-cao-bang/">Where to Stay in Cao Bang</a>: Connecting eastward to Ban Gioc Waterfall and stone villages.</li>\n        <li><a href="/costs/ha-giang-loop-cost-budget/">Ha Giang Loop Cost &amp; Budget</a>: Daily breakdown of fuel, homestays, bikes, and food.</li>',
        '        <li><a href="/destinations/where-to-stay-in-cao-bang/">Where to Stay in Cao Bang</a>: Connecting eastward to Ban Gioc Waterfall and stone villages.</li>\n        <li><a href="/destinations/where-to-stay-in-bao-lac/">Where to Stay in Bao Lac</a>: Midpoint mountain transit hotels and Black Lo Lo homestays toward Cao Bang.</li>\n        <li><a href="/costs/ha-giang-loop-cost-budget/">Ha Giang Loop Cost &amp; Budget</a>: Daily breakdown of fuel, homestays, bikes, and food.</li>',
        'where-to-stay-in-bao-lac in where-to-stay-in-meo-vac'
    ),
    (
        'cao-bang-travel-guide',
        '<li><a href="/destinations/best-day-trips-from-hanoi/">Best Day Trips from Hanoi: Evaluating Excursion Travel Ratios</a></li>\n</ul>',
        '<li><a href="/destinations/best-day-trips-from-hanoi/">Best Day Trips from Hanoi: Evaluating Excursion Travel Ratios</a></li>\n<li><a href="/destinations/where-to-stay-in-bao-lac/">Where to Stay in Bao Lac: Transit Guesthouses and Ethnic Homestays</a></li>\n</ul>',
        'where-to-stay-in-bao-lac in cao-bang-travel-guide'
    ),

    # --- Group 3: where-to-stay-in-bao-loc ---
    (
        'where-to-stay-in-da-lat',
        '        <li><a href="/itineraries/central-highlands-vietnam-itinerary/">Central Highlands Vietnam Itinerary</a>: 5 to 7-day highland overland route to Lak Lake and Buon Ma Thuot.</li>\n    </ul>',
        '        <li><a href="/destinations/where-to-stay-in-bao-loc/">Where to Stay in Bao Loc</a>: Tea plantation eco-resorts and quiet southern highland hotels.</li>\n        <li><a href="/itineraries/central-highlands-vietnam-itinerary/">Central Highlands Vietnam Itinerary</a>: 5 to 7-day highland overland route to Lak Lake and Buon Ma Thuot.</li>\n    </ul>',
        'where-to-stay-in-bao-loc in where-to-stay-in-da-lat'
    ),
    (
        'da-lat-travel-guide',
        '<li><a href="/plan/vietnam-travel-cost/">Vietnam Travel Cost: Realistic Highland vs Coastal Budgets</a></li>\n</ul>',
        '<li><a href="/plan/vietnam-travel-cost/">Vietnam Travel Cost: Realistic Highland vs Coastal Budgets</a></li>\n<li><a href="/destinations/where-to-stay-in-bao-loc/">Where to Stay in Bao Loc: Tea Plantation Eco-Lodges &amp; Foothill Stays</a></li>\n</ul>',
        'where-to-stay-in-bao-loc in da-lat-travel-guide'
    ),
    (
        'da-lat-to-buon-ma-thuot-transport',
        'guide, or review highland hotel options in <a href="/destinations/where-to-stay-in-da-lat/">where to stay in Da Lat</a>,',
        'guide, explore southern tea plateau retreats in our <a href="/destinations/where-to-stay-in-bao-loc/">where to stay in Bao Loc</a> guide, or review highland hotel options in <a href="/destinations/where-to-stay-in-da-lat/">where to stay in Da Lat</a>,',
        'where-to-stay-in-bao-loc in da-lat-to-buon-ma-thuot-transport'
    ),
    (
        'da-lat-to-mui-ne-transport',
        'or review highland connections in our <a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot transport</a> breakdown,',
        'compare southern mountain coffee and tea bases via our <a href="/destinations/where-to-stay-in-bao-loc/">where to stay in Bao Loc</a> guide, or review highland connections in our <a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot transport</a> breakdown,',
        'where-to-stay-in-bao-loc in da-lat-to-mui-ne-transport'
    ),

    # --- Group 5 & 6: tran-de-to-con-dao-ferry & vung-tau-to-con-dao-ferry ---
    (
        'how-to-get-to-con-dao-flight-vs-ferry',
        '        <li><a href="/plan/saigon-to-vung-tau-transport/">Saigon to Vung Tau Transport</a>: Connecting to the Vung Tau fast ferry port.</li>\n    </ul>',
        '        <li><a href="/plan/tran-de-to-con-dao-ferry/">Tran De to Con Dao Ferry</a>: Shortest speedboat crossing from Soc Trang via Superdong.</li>\n        <li><a href="/plan/vung-tau-to-con-dao-ferry/">Vung Tau to Con Dao Ferry</a>: Direct high-speed catamaran routes from Cau Da port.</li>\n        <li><a href="/plan/saigon-to-vung-tau-transport/">Saigon to Vung Tau Transport</a>: Connecting to the Vung Tau fast ferry port.</li>\n    </ul>',
        'tran-de-to-con-dao-ferry & vung-tau-to-con-dao-ferry in how-to-get-to-con-dao-flight-vs-ferry'
    ),
    (
        'where-to-stay-in-con-dao',
        '        <li><a href="/plan/how-to-get-to-con-dao-flight-vs-ferry/">How to Get to Con Dao: Flight vs Ferry</a>: Turboprop flight schedules vs high-speed catamaran ferries.</li>\n        <li><a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>: Practical safety realities and island transit tips.</li>',
        '        <li><a href="/plan/how-to-get-to-con-dao-flight-vs-ferry/">How to Get to Con Dao: Flight vs Ferry</a>: Turboprop flight schedules vs high-speed catamaran ferries.</li>\n        <li><a href="/plan/tran-de-to-con-dao-ferry/">Tran De to Con Dao Ferry</a>: Superdong express boats from Soc Trang Tran De port.</li>\n        <li><a href="/plan/vung-tau-to-con-dao-ferry/">Vung Tau to Con Dao Ferry</a>: Con Dao Express 36 catamaran schedules from Vung Tau.</li>\n        <li><a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>: Practical safety realities and island transit tips.</li>',
        'tran-de-to-con-dao-ferry & vung-tau-to-con-dao-ferry in where-to-stay-in-con-dao'
    ),
    (
        'con-dao-travel-guide',
        'and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> to decide whether Con Dao improves the route or simply makes it more fragile.</p>',
        'and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a>. For sea crossings, consult our dedicated logistics guides on the <a href="/plan/tran-de-to-con-dao-ferry/">Tran De to Con Dao ferry</a> (shortest delta route) and the <a href="/plan/vung-tau-to-con-dao-ferry/">Vung Tau to Con Dao ferry</a> (direct southern mainland express catamaran) to decide whether Con Dao improves the route or simply makes it more fragile.</p>',
        'tran-de-to-con-dao-ferry & vung-tau-to-con-dao-ferry in con-dao-travel-guide'
    ),
    (
        'where-to-stay-in-soc-trang',
        'organize delta highway transit using our <a href="/plan/can-tho-to-ca-mau-transport/">Can Tho to Ca Mau transport</a> guide,',
        'plan island speedboat departures with our <a href="/plan/tran-de-to-con-dao-ferry/">Tran De to Con Dao ferry</a> guide, organize delta highway transit using our <a href="/plan/can-tho-to-ca-mau-transport/">Can Tho to Ca Mau transport</a> guide,',
        'tran-de-to-con-dao-ferry in where-to-stay-in-soc-trang'
    ),
    (
        'where-to-stay-in-vung-tau',
        '        <li><a href="/plan/best-day-trips-from-ho-chi-minh-city/">Best Day Trips from Saigon</a>: Cu Chi Tunnels, Mekong Delta, and coastal breaks.</li>\n    </ul>',
        '        <li><a href="/plan/vung-tau-to-con-dao-ferry/">Vung Tau to Con Dao Ferry</a>: Con Dao Express 36 catamaran schedules and Cau Da port boarding.</li>\n        <li><a href="/plan/best-day-trips-from-ho-chi-minh-city/">Best Day Trips from Saigon</a>: Cu Chi Tunnels, Mekong Delta, and coastal breaks.</li>\n    </ul>',
        'vung-tau-to-con-dao-ferry in where-to-stay-in-vung-tau'
    ),

    # --- Group 7: pleiku-to-quy-nhon-transport ---
    (
        'where-to-stay-in-pleiku',
        '        <li><a href="/plan/buon-ma-thuot-to-pleiku-transport/">Buon Ma Thuot to Pleiku Transport</a>: Shared limousine vans and express buses along Highway 14.</li>\n        <li><a href="/itineraries/central-highlands-vietnam-itinerary/">Central Highlands Vietnam Itinerary</a>: Complete multi-day overland route from Da Lat to Pleiku.</li>',
        '        <li><a href="/plan/buon-ma-thuot-to-pleiku-transport/">Buon Ma Thuot to Pleiku Transport</a>: Shared limousine vans and express buses along Highway 14.</li>\n        <li><a href="/plan/pleiku-to-quy-nhon-transport/">Pleiku to Quy Nhon Transport</a>: Direct Highway 19 limousine coaches and private cars descending to the coast.</li>\n        <li><a href="/itineraries/central-highlands-vietnam-itinerary/">Central Highlands Vietnam Itinerary</a>: Complete multi-day overland route from Da Lat to Pleiku.</li>',
        'pleiku-to-quy-nhon-transport in where-to-stay-in-pleiku'
    ),
    (
        'where-to-stay-in-quy-nhon',
        '        <li><a href="/plan/quy-nhon-to-hoi-an-transport/">Quy Nhon to Hoi An Transport</a>: Trains from Dieu Tri to Tra Kieu and direct sleeper limousines.</li>\n    </ul>',
        '        <li><a href="/plan/pleiku-to-quy-nhon-transport/">Pleiku to Quy Nhon Transport</a>: Highway 19 limousine buses connecting coastal Quy Nhon with Gia Lai highlands.</li>\n        <li><a href="/plan/quy-nhon-to-hoi-an-transport/">Quy Nhon to Hoi An Transport</a>: Trains from Dieu Tri to Tra Kieu and direct sleeper limousines.</li>\n    </ul>',
        'pleiku-to-quy-nhon-transport in where-to-stay-in-quy-nhon'
    ),
    (
        'quy-nhon-travel-guide',
        'and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding a south-central coast chapter.</p>',
        '<a href="/plan/pleiku-to-quy-nhon-transport/">Pleiku to Quy Nhon Transport</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding a south-central coast chapter.</p>',
        'pleiku-to-quy-nhon-transport in quy-nhon-travel-guide'
    ),
    (
        'pleiku-to-kon-tum-transport',
        '        <li><a href="/plan/buon-ma-thuot-to-pleiku-transport/">Buon Ma Thuot to Pleiku Transport</a>: Highway 14 bus schedules and limousine options.</li>\n        <li><a href="/plan/vietnam-scooter-rental-checklist/">Vietnam Scooter Rental Checklist</a>: Inspection guide before highland road touring.</li>',
        '        <li><a href="/plan/buon-ma-thuot-to-pleiku-transport/">Buon Ma Thuot to Pleiku Transport</a>: Highway 14 bus schedules and limousine options.</li>\n        <li><a href="/plan/pleiku-to-quy-nhon-transport/">Pleiku to Quy Nhon Transport</a>: Descending Highway 19 through An Khe pass to coastal Binh Dinh.</li>\n        <li><a href="/plan/vietnam-scooter-rental-checklist/">Vietnam Scooter Rental Checklist</a>: Inspection guide before highland road touring.</li>',
        'pleiku-to-quy-nhon-transport in pleiku-to-kon-tum-transport'
    ),
]

def main():
    print(f"Testing {len(CANDIDATES)} mesh candidate replacements against stage88_mesh_targets.json...")
    all_passed = True
    match_counts = {}

    for host_slug, target_str, replacement_str, note in CANDIDATES:
        if host_slug not in data:
            print(f"[FAIL] Host slug '{host_slug}' not found in data! ({note})")
            all_passed = False
            continue
        
        content = data[host_slug]['post_content']
        count = content.count(target_str)
        if count == 1:
            print(f"[PASS] {host_slug}: match count == 1 ({note})")
            match_counts[host_slug] = match_counts.get(host_slug, 0) + 1
        elif count == 0:
            print(f"[FAIL] {host_slug}: match count == 0! Target string not found! ({note})")
            all_passed = False
        else:
            print(f"[FAIL] {host_slug}: match count == {count}! Expected 1. ({note})")
            all_passed = False

    print("\n--- Summary ---")
    print(f"Total candidates: {len(CANDIDATES)}")
    print(f"Hosts affected: {len(match_counts)}")
    if all_passed:
        print("[SUCCESS] All candidate replacements matched exactly once!")
    else:
        print("[ERROR] Some candidate replacements failed.")
        sys.exit(1)

if __name__ == '__main__':
    main()

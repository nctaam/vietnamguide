# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 76: Assemble and Strictly Validate the 28 Inbound Links Mesh
"""

import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    targets_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage76_mesh_targets.json')
    targets = json.load(open(targets_path, encoding='utf-8'))

    replacements = {
        # --- 1: ninh-binh-travel-guide -> where-to-stay-in-tam-coc ---
        "ninh-binh-travel-guide": [
            (
                'our transit guide on <a href="/plan/hanoi-to-ninh-binh-train-vs-limousine/">Hanoi to Ninh Binh Train vs Limousine</a>, and <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>',
                'our transit guide on <a href="/plan/hanoi-to-ninh-binh-train-vs-limousine/">Hanoi to Ninh Binh Train vs Limousine</a>, our village base guide on <a href="/destinations/where-to-stay-in-tam-coc/">Where to Stay in Tam Coc</a>, and <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>',
                ["where-to-stay-in-tam-coc"]
            )
        ],

        # --- 2: tam-coc-travel-guide -> where-to-stay-in-tam-coc ---
        "tam-coc-travel-guide": [
            (
                'and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a> before adding both Tam Coc and the bay.',
                '<a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and select rural bases via our <a href="/destinations/where-to-stay-in-tam-coc/">Where to Stay in Tam Coc Guide</a> before adding both Tam Coc and the bay.',
                ["where-to-stay-in-tam-coc"]
            )
        ],

        # --- 3: where-to-stay-in-ninh-binh -> where-to-stay-in-tam-coc ---
        "where-to-stay-in-ninh-binh": [
            (
                'and <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a> before the northern route becomes too full.',
                '<a href="/destinations/where-to-stay-in-tam-coc/">Where to Stay in Tam Coc</a>, and <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a> before the northern route becomes too full.',
                ["where-to-stay-in-tam-coc"]
            )
        ],

        # --- 4: hang-mua-ninh-binh-guide -> where-to-stay-in-tam-coc ---
        "hang-mua-ninh-binh-guide": [
            (
                'our base recommendations in <a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a>, and the complete <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>.',
                'our base recommendations in <a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a>, our village lodging guide on <a href="/destinations/where-to-stay-in-tam-coc/">Where to Stay in Tam Coc</a>, and the complete <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>.',
                ["where-to-stay-in-tam-coc"]
            )
        ],

        # --- 5, 15: saigon-to-vung-tau-transport -> where-to-stay-in-vung-tau, how-to-get-to-con-dao-flight-vs-ferry ---
        "saigon-to-vung-tau-transport": [
            (
                '<li><a href="/destinations/saigon-airport-to-district-1/">Saigon Airport to District 1</a>: Airport bus routes, metered taxis, and Grab transfers.</li>',
                '<li><a href="/destinations/saigon-airport-to-district-1/">Saigon Airport to District 1</a>: Airport bus routes, metered taxis, and Grab transfers.</li>\n        <li><a href="/destinations/where-to-stay-in-vung-tau/">Where to Stay in Vung Tau</a>: Back Beach vs Front Beach hotels and hillside villas.</li>\n        <li><a href="/plan/how-to-get-to-con-dao-flight-vs-ferry/">How to Get to Con Dao: Flight vs Ferry</a>: Fast catamaran ferry connections from Vung Tau port.</li>',
                ["where-to-stay-in-vung-tau", "how-to-get-to-con-dao-flight-vs-ferry"]
            )
        ],

        # --- 6: best-day-trips-from-ho-chi-minh-city -> where-to-stay-in-vung-tau ---
        "best-day-trips-from-ho-chi-minh-city": [
            (
                'and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> resources prior to submitting tour deposits.',
                '<a href="/destinations/where-to-stay-in-vung-tau/">Where to Stay in Vung Tau</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> resources prior to submitting tour deposits.',
                ["where-to-stay-in-vung-tau"]
            )
        ],

        # --- 7: ho-chi-minh-city-travel-guide -> where-to-stay-in-vung-tau ---
        "ho-chi-minh-city-travel-guide": [
            (
                'and <a href="/destinations/con-dao-travel-guide/">Con Dao Travel Guide</a> before building a south-ending route.',
                '<a href="/destinations/where-to-stay-in-vung-tau/">Where to Stay in Vung Tau</a>, and <a href="/destinations/con-dao-travel-guide/">Con Dao Travel Guide</a> before building a south-ending route.',
                ["where-to-stay-in-vung-tau"]
            )
        ],

        # --- 8, 16: southern-vietnam-itinerary -> where-to-stay-in-vung-tau, how-to-get-to-con-dao-flight-vs-ferry ---
        "southern-vietnam-itinerary": [
            (
                '<li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Ferry port hotels and quiet Gulf of Thailand stays.</li>',
                '<li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Ferry port hotels and quiet Gulf of Thailand stays.</li>\n        <li><a href="/destinations/where-to-stay-in-vung-tau/">Where to Stay in Vung Tau</a>: Oceanfront resort towers and coastal weekend stays.</li>\n        <li><a href="/plan/how-to-get-to-con-dao-flight-vs-ferry/">How to Get to Con Dao: Flight vs Ferry</a>: Flights from Saigon vs express ferries from Tran De and Vung Tau.</li>',
                ["where-to-stay-in-vung-tau", "how-to-get-to-con-dao-flight-vs-ferry"]
            )
        ],

        # --- 9: da-nang-travel-guide -> da-nang-to-quy-nhon-transport ---
        "da-nang-travel-guide": [
            (
                'and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>.',
                '<a href="/plan/da-nang-to-quy-nhon-transport/">Da Nang to Quy Nhon Transport</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>.',
                ["da-nang-to-quy-nhon-transport"]
            )
        ],

        # --- 10: quy-nhon-travel-guide -> da-nang-to-quy-nhon-transport ---
        "quy-nhon-travel-guide": [
            (
                'and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding a south-central coast chapter.',
                '<a href="/plan/da-nang-to-quy-nhon-transport/">Da Nang to Quy Nhon Transport</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding a south-central coast chapter.',
                ["da-nang-to-quy-nhon-transport"]
            )
        ],

        # --- 11: where-to-stay-in-quy-nhon -> da-nang-to-quy-nhon-transport ---
        "where-to-stay-in-quy-nhon": [
            (
                '<li><a href="/plan/vietnam-train-travel/">Vietnam Train Travel</a>: How to ride the scenic coastal railway to Dieu Tri and Quy Nhon.</li>',
                '<li><a href="/plan/vietnam-train-travel/">Vietnam Train Travel</a>: How to ride the scenic coastal railway to Dieu Tri and Quy Nhon.</li>\n        <li><a href="/plan/da-nang-to-quy-nhon-transport/">Da Nang to Quy Nhon Transport</a>: Reunification Express trains vs luxury Vietage rail carriages.</li>',
                ["da-nang-to-quy-nhon-transport"]
            )
        ],

        # --- 12: central-vietnam-itinerary -> da-nang-to-quy-nhon-transport ---
        "central-vietnam-itinerary": [
            (
                '<li><a href="/itineraries/da-nang-to-hue-train-vs-car/">Da Nang to Hue Train vs Car</a>: Detailed Hai Van Pass route comparison.</li>',
                '<li><a href="/itineraries/da-nang-to-hue-train-vs-car/">Da Nang to Hue Train vs Car</a>: Detailed Hai Van Pass route comparison.</li>\n        <li><a href="/plan/da-nang-to-quy-nhon-transport/">Da Nang to Quy Nhon Transport</a>: Coastal rail routes connecting Da Nang with Binh Dinh.</li>',
                ["da-nang-to-quy-nhon-transport"]
            )
        ],

        # --- 13: where-to-stay-in-con-dao -> how-to-get-to-con-dao-flight-vs-ferry ---
        "where-to-stay-in-con-dao": [
            (
                '<li><a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc</a>: Compare beach enclaves across Vietnam\'s southern islands.</li>',
                '<li><a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc</a>: Compare beach enclaves across Vietnam\'s southern islands.</li>\n        <li><a href="/plan/how-to-get-to-con-dao-flight-vs-ferry/">How to Get to Con Dao: Flight vs Ferry</a>: Turboprop flight schedules vs high-speed catamaran ferries.</li>',
                ["how-to-get-to-con-dao-flight-vs-ferry"]
            )
        ],

        # --- 14: con-dao-vs-phu-quoc -> how-to-get-to-con-dao-flight-vs-ferry ---
        "con-dao-vs-phu-quoc": [
            (
                '<li><a href="/destinations/where-to-stay-in-con-dao/">Where to Stay in Con Dao: Best Areas &amp; Resorts</a></li>',
                '<li><a href="/destinations/where-to-stay-in-con-dao/">Where to Stay in Con Dao: Best Areas &amp; Resorts</a></li>\n<li><a href="/plan/how-to-get-to-con-dao-flight-vs-ferry/">How to Get to Con Dao: Flight vs Fast Ferry</a></li>',
                ["how-to-get-to-con-dao-flight-vs-ferry"]
            )
        ],

        # --- 17: where-to-stay-in-ba-be -> northeast-vietnam-itinerary ---
        "where-to-stay-in-ba-be": [
            (
                '<li><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>: Express limousines and regional buses across the north.</li>',
                '<li><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>: Express limousines and regional buses across the north.</li>\n        <li><a href="/itineraries/northeast-vietnam-itinerary/">Northeast Vietnam Itinerary</a>: 5 to 7-day scenic circuit from Ba Be to Cao Bang and Ban Gioc.</li>',
                ["northeast-vietnam-itinerary"]
            )
        ],

        # --- 18: hanoi-to-ba-be-transport -> northeast-vietnam-itinerary ---
        "hanoi-to-ba-be-transport": [
            (
                '<li><a href="/plan/best-time-for-northern-vietnam/">Best Time for Northern Vietnam</a>: Regional rainfall charts and seasonal travel advice.</li>',
                '<li><a href="/plan/best-time-for-northern-vietnam/">Best Time for Northern Vietnam</a>: Regional rainfall charts and seasonal travel advice.</li>\n        <li><a href="/itineraries/northeast-vietnam-itinerary/">Northeast Vietnam Itinerary</a>: Complete multi-day circuit connecting Ba Be Lake with Ban Gioc Waterfall.</li>',
                ["northeast-vietnam-itinerary"]
            )
        ],

        # --- 19: where-to-stay-in-cao-bang -> northeast-vietnam-itinerary ---
        "where-to-stay-in-cao-bang": [
            (
                '<li><a href="/destinations/where-to-stay-in-ba-be/">Where to Stay in Ba Be</a>: Pac Ngoi stilt homestays, Bo Lu waterfront lodges, and park bases.</li>',
                '<li><a href="/destinations/where-to-stay-in-ba-be/">Where to Stay in Ba Be</a>: Pac Ngoi stilt homestays, Bo Lu waterfront lodges, and park bases.</li>\n        <li><a href="/itineraries/northeast-vietnam-itinerary/">Northeast Vietnam Itinerary</a>: Multi-day route from Hanoi through Ba Be and Ban Gioc Waterfall.</li>',
                ["northeast-vietnam-itinerary"]
            )
        ],

        # --- 20: hanoi-to-cao-bang-transport -> northeast-vietnam-itinerary ---
        "hanoi-to-cao-bang-transport": [
            (
                '<li><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>: Express buses, trains, and flight comparisons nationwide.</li>',
                '<li><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>: Express buses, trains, and flight comparisons nationwide.</li>\n        <li><a href="/itineraries/northeast-vietnam-itinerary/">Northeast Vietnam Itinerary</a>: Loop route from Hanoi through Cao Bang, Ban Gioc, and Ba Be Lake.</li>',
                ["northeast-vietnam-itinerary"]
            )
        ],

        # --- 21: ha-giang-safety-guide -> vietnam-motorbike-license-laws ---
        "ha-giang-safety-guide": [
            (
                'and verify coverage with our <a href="/plan/health-travel-insurance-vietnam/">Vietnam Health &amp; Travel Insurance Guide</a>.',
                'review official driving rules in <a href="/plan/vietnam-motorbike-license-laws/">Vietnam Motorbike License Laws</a>, and verify coverage with our <a href="/plan/health-travel-insurance-vietnam/">Vietnam Health &amp; Travel Insurance Guide</a>.',
                ["vietnam-motorbike-license-laws"]
            )
        ],

        # --- 22: ha-giang-easy-rider-vs-self-drive -> vietnam-motorbike-license-laws ---
        "ha-giang-easy-rider-vs-self-drive": [
            (
                'and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison Guide</a>.</p>',
                '<a href="/plan/vietnam-motorbike-license-laws/">Vietnam Motorbike License Laws</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison Guide</a>.</p>',
                ["vietnam-motorbike-license-laws"]
            )
        ],

        # --- 23: transport-within-vietnam -> vietnam-motorbike-license-laws ---
        "transport-within-vietnam": [
            (
                'explore highland bases in <a href="/destinations/where-to-stay-in-buon-ma-thuot/">Where to Stay in Buon Ma Thuot</a>, and consult <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying for high-friction transfers.</p>',
                'explore highland bases in <a href="/destinations/where-to-stay-in-buon-ma-thuot/">Where to Stay in Buon Ma Thuot</a>, understand two-wheeled regulations via <a href="/plan/vietnam-motorbike-license-laws/">Vietnam Motorbike License Laws</a>, and consult <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying for high-friction transfers.</p>',
                ["vietnam-motorbike-license-laws"]
            )
        ],

        # --- 24, 25: vietnam-first-trip-planning-checklist -> vietnam-motorbike-license-laws, vietnam-plug-adapter-electricity-guide ---
        "vietnam-first-trip-planning-checklist": [
            (
                'The map looks better than the day feels (consult our <a href="/plan/vietnam-sleeper-bus-guide/">Vietnam sleeper bus guide</a>, train guides, and our dedicated <a href="/itineraries/vietnam-family-travel-guide/">Vietnam Family Travel Guide</a> before locking long overland legs).</p>',
                'The map looks better than the day feels (consult our <a href="/plan/vietnam-sleeper-bus-guide/">Vietnam sleeper bus guide</a>, review license rules in <a href="/plan/vietnam-motorbike-license-laws/">Vietnam Motorbike License Laws</a>, check socket types in our <a href="/plan/vietnam-plug-adapter-electricity-guide/">Vietnam Plug Adapter Guide</a>, and consult our dedicated <a href="/itineraries/vietnam-family-travel-guide/">Vietnam Family Travel Guide</a> before locking long overland legs).</p>',
                ["vietnam-motorbike-license-laws", "vietnam-plug-adapter-electricity-guide"]
            )
        ],

        # --- 26: what-to-pack-for-vietnam-region-season -> vietnam-plug-adapter-electricity-guide ---
        "what-to-pack-for-vietnam-region-season": [
            (
                '<li><span class="vg-related-route-step">07</span><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a><span class="vg-related-route-note">Use island logic to add swimwear and sun protection without overpacking.</span></li>\n</ol>',
                '<li><span class="vg-related-route-step">07</span><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a><span class="vg-related-route-note">Use island logic to add swimwear and sun protection without overpacking.</span></li>\n<li><span class="vg-related-route-step">08</span><a href="/plan/vietnam-plug-adapter-electricity-guide/">Vietnam Plug Adapter Guide</a><span class="vg-related-route-note">Check 220V voltage compatibility, dual-voltage gadgets, and plug types.</span></li>\n</ol>',
                ["vietnam-plug-adapter-electricity-guide"]
            )
        ],

        # --- 27: vietnam-train-travel -> vietnam-plug-adapter-electricity-guide ---
        "vietnam-train-travel": [
            (
                '<li><a href="/plan/vietnam-travel-cost/">Vietnam Travel Cost: Rail Fares &amp; Regional Travel Budgets</a></li>\n</ul>',
                '<li><a href="/plan/vietnam-travel-cost/">Vietnam Travel Cost: Rail Fares &amp; Regional Travel Budgets</a></li>\n<li><a href="/plan/vietnam-plug-adapter-electricity-guide/">Vietnam Plug Adapter Guide: Sleeper Berth Sockets &amp; Charging Tech</a></li>\n</ul>',
                ["vietnam-plug-adapter-electricity-guide"]
            )
        ],

        # --- 28: vietnam-sim-card-airport-vs-city -> vietnam-plug-adapter-electricity-guide ---
        "vietnam-sim-card-airport-vs-city": [
            (
                '<li><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>: Realistic daily spending estimates across street food, transport, and hotels.</li>\n    </ul>',
                '<li><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>: Realistic daily spending estimates across street food, transport, and hotels.</li>\n        <li><a href="/plan/vietnam-plug-adapter-electricity-guide/">Vietnam Plug Adapter Guide</a>: Dual-voltage charging, hybrid wall sockets, and power banks.</li>\n    </ul>',
                ["vietnam-plug-adapter-electricity-guide"]
            )
        ],
    }

    inlink_counts = {
        "where-to-stay-in-tam-coc": 0,
        "where-to-stay-in-vung-tau": 0,
        "da-nang-to-quy-nhon-transport": 0,
        "how-to-get-to-con-dao-flight-vs-ferry": 0,
        "northeast-vietnam-itinerary": 0,
        "vietnam-motorbike-license-laws": 0,
        "vietnam-plug-adapter-electricity-guide": 0,
    }

    updates = {}

    print("=== Validating Stage 76 Substring Matches ===")
    for slug, rules in replacements.items():
        if slug not in targets or not targets[slug]:
            print(f"[ERROR] Target slug {slug} not found in targets JSON!")
            return

        content = targets[slug]['content']
        post_id = targets[slug]['id']

        for search_str, replace_str, expected_pillars in rules:
            occurrences = content.count(search_str)
            if occurrences == 0:
                print(f"[FAIL] Substring NOT FOUND in '{slug}'")
                print(f"       Search str: {search_str[:80]}...")
                return
            elif occurrences > 1:
                print(f"[FAIL] Ambiguous match ({occurrences} times) in '{slug}'")
                return
            else:
                print(f"[PASS] Exact 1:1 match in '{slug}' -> provides {expected_pillars}")
                content = content.replace(search_str, replace_str)
                for ep in expected_pillars:
                    inlink_counts[ep] += 1

        updates[slug] = {
            'id': post_id,
            'slug': slug,
            'content': content
        }

    print("\n=== Inbound Link Distribution Summary ===")
    all_ok = True
    for pillar, count in inlink_counts.items():
        print(f"  {pillar}: {count} inbound links")
        if count < 4:
            all_ok = False

    if not all_ok:
        print("\n[ERROR] Not all pillars have at least 4 inbound links!")
        return

    print(f"\nTotal inlinks: {sum(inlink_counts.values())} across {len(updates)} posts.")
    print("Writing validated operations to 'ops/stage76_mesh_ops.json'...")

    ops_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage76_mesh_ops.json')
    with open(ops_path, 'w', encoding='utf-8') as f:
        json.dump(updates, f, ensure_ascii=False, indent=2)

    print("Mesh successfully assembled and ready for deployment!")

if __name__ == '__main__':
    main()

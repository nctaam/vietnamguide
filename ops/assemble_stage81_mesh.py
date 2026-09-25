# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 81: Assemble and Strictly Validate the Internal Link Mesh
Generates 36 verified inbound links across 27 host posts for all 7 Stage 81 pillars.
"""

import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    targets_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage81_raw_targets.json')
    targets = json.load(open(targets_path, encoding='utf-8'))

    replacements = {
        # --- 1: where-to-stay-in-cao-bang -> ha-giang-to-cao-bang-transport ---
        "where-to-stay-in-cao-bang": [
            (
                '<li><a href="/destinations/cao-bang-travel-guide/">Cao Bang Travel Guide</a>: Waterfalls,',
                '<li><a href="/plan/ha-giang-to-cao-bang-transport/">Ha Giang to Cao Bang Transport</a>: Mountain bus routes and shared vans via Bao Lac and Highway 34.</li>\n        <li><a href="/destinations/cao-bang-travel-guide/">Cao Bang Travel Guide</a>: Waterfalls,',
                ["ha-giang-to-cao-bang-transport"]
            )
        ],

        # --- 2: where-to-stay-in-ha-giang -> ha-giang-to-cao-bang-transport ---
        "where-to-stay-in-ha-giang": [
            (
                '<li><a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport</a>: Connecting mountain hubs by daytime limousine.</li>',
                '<li><a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport</a>: Connecting mountain hubs by daytime limousine.</li>\n        <li><a href="/plan/ha-giang-to-cao-bang-transport/">Ha Giang to Cao Bang Transport</a>: Traverse the northern grand loop via Bao Lac and Highway 34.</li>',
                ["ha-giang-to-cao-bang-transport"]
            )
        ],

        # --- 3: where-to-stay-in-meo-vac -> ha-giang-to-cao-bang-transport ---
        "where-to-stay-in-meo-vac": [
            (
                '<li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>: Historic',
                '<li><a href="/plan/ha-giang-to-cao-bang-transport/">Ha Giang to Cao Bang Transport</a>: Connect eastern loop viewpoints with Ban Gioc Waterfall.</li>\n        <li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>: Historic',
                ["ha-giang-to-cao-bang-transport"]
            )
        ],

        # --- 4: where-to-stay-in-dong-van -> ha-giang-to-cao-bang-transport ---
        "where-to-stay-in-dong-van": [
            (
                '<li><a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a>: Staging hostels and loop bases in Ha Giang City and Du Gia.</li>',
                '<li><a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a>: Staging hostels and loop bases in Ha Giang City and Du Gia.</li>\n        <li><a href="/plan/ha-giang-to-cao-bang-transport/">Ha Giang to Cao Bang Transport</a>: Overland routes linking the Karst Plateau with Cao Bang.</li>',
                ["ha-giang-to-cao-bang-transport"]
            )
        ],

        # --- 5: sapa-to-ha-giang-transport -> ha-giang-to-cao-bang-transport & sapa-to-mu-cang-chai-transport ---
        "sapa-to-ha-giang-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-sapa/">Where to Stay in Sapa</a>: Valley eco-lodges vs central mountain hotels.</li>',
                '<li><a href="/destinations/where-to-stay-in-sapa/">Where to Stay in Sapa</a>: Valley eco-lodges vs central mountain hotels.</li>\n        <li><a href="/plan/ha-giang-to-cao-bang-transport/">Ha Giang to Cao Bang Transport</a>: Continuing east toward Bao Lac and Ban Gioc Waterfall.</li>\n        <li><a href="/plan/sapa-to-mu-cang-chai-transport/">Sapa to Mu Cang Chai Transport</a>: Mountain pass route over O Quy Ho and Highway 32.</li>',
                ["ha-giang-to-cao-bang-transport", "sapa-to-mu-cang-chai-transport"]
            )
        ],

        # --- 6: northeast-vietnam-itinerary -> ha-giang-to-cao-bang-transport ---
        "northeast-vietnam-itinerary": [
            (
                'For western routes, explore our <a href="/northwest-vietnam-itinerary/">Northwest Vietnam itinerary</a>.',
                'connect mountain hubs using our <a href="/plan/ha-giang-to-cao-bang-transport/">Ha Giang to Cao Bang transport</a> guide, and for western routes, explore our <a href="/northwest-vietnam-itinerary/">Northwest Vietnam itinerary</a>.',
                ["ha-giang-to-cao-bang-transport"]
            )
        ],

        # --- 7: where-to-stay-in-mu-cang-chai -> sapa-to-mu-cang-chai-transport ---
        "where-to-stay-in-mu-cang-chai": [
            (
                '<li><a href="/destinations/where-to-stay-in-sapa/">Where to Stay in Sapa</a>: Mountain view chalets,',
                '<li><a href="/plan/sapa-to-mu-cang-chai-transport/">Sapa to Mu Cang Chai Transport</a>: Local minivans and private transfers over O Quy Ho Pass.</li>\n        <li><a href="/destinations/where-to-stay-in-sapa/">Where to Stay in Sapa</a>: Mountain view chalets,',
                ["sapa-to-mu-cang-chai-transport"]
            )
        ],

        # --- 8: where-to-stay-in-sapa -> sapa-to-mu-cang-chai-transport ---
        "where-to-stay-in-sapa": [
            (
                '<a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport Guide</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison</a>.',
                '<a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport Guide</a>, <a href="/plan/sapa-to-mu-cang-chai-transport/">Sapa to Mu Cang Chai Transport</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison</a>.',
                ["sapa-to-mu-cang-chai-transport"]
            )
        ],

        # --- 9: northwest-vietnam-itinerary -> sapa-to-mu-cang-chai-transport ---
        "northwest-vietnam-itinerary": [
            (
                '<li><a href="/destinations/where-to-stay-in-mu-cang-chai/">Where to Stay in Mu Cang Chai</a>:',
                '<li><a href="/plan/sapa-to-mu-cang-chai-transport/">Sapa to Mu Cang Chai Transport</a>: Crossing O Quy Ho Pass to the terraced rice fields.</li>\n        <li><a href="/destinations/where-to-stay-in-mu-cang-chai/">Where to Stay in Mu Cang Chai</a>:',
                ["sapa-to-mu-cang-chai-transport"]
            )
        ],

        # --- 10: ha-giang-to-cao-bang-transport -> sapa-to-mu-cang-chai-transport ---
        "ha-giang-to-cao-bang-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-meo-vac/">Where to Stay in Meo Vac</a>: Pa Vi Hmong cultural village stays and mountain pass bases.</li>',
                '<li><a href="/destinations/where-to-stay-in-meo-vac/">Where to Stay in Meo Vac</a>: Pa Vi Hmong cultural village stays and mountain pass bases.</li>\n        <li><a href="/plan/sapa-to-mu-cang-chai-transport/">Sapa to Mu Cang Chai Transport</a>: Alpine corridor connecting Sapa with terraced valleys.</li>',
                ["sapa-to-mu-cang-chai-transport"]
            )
        ],

        # --- 11: where-to-stay-in-rach-gia -> can-tho-to-rach-gia-transport ---
        "where-to-stay-in-rach-gia": [
            (
                '<li><a href="/plan/phu-quoc-ferry-guide/">Phu Quoc Ferry Guide</a>: Fast catamaran schedules, tickets, and harbor advice.</li>',
                '<li><a href="/plan/phu-quoc-ferry-guide/">Phu Quoc Ferry Guide</a>: Fast catamaran schedules, tickets, and harbor advice.</li>\n        <li><a href="/plan/can-tho-to-rach-gia-transport/">Can Tho to Rach Gia Transport</a>: Futa express buses and VIP limousines via CT02 expressway.</li>',
                ["can-tho-to-rach-gia-transport"]
            )
        ],

        # --- 12: where-to-stay-in-can-tho -> can-tho-to-rach-gia-transport & where-to-stay-in-soc-trang ---
        "where-to-stay-in-can-tho": [
            (
                '<li><a href="/destinations/where-to-stay-in-rach-gia/">Where to Stay in Rach Gia</a>: Fast ferry harbor hotels for early Phu Quoc boats.</li>',
                '<li><a href="/destinations/where-to-stay-in-rach-gia/">Where to Stay in Rach Gia</a>: Fast ferry harbor hotels for early Phu Quoc boats.</li>\n        <li><a href="/plan/can-tho-to-rach-gia-transport/">Can Tho to Rach Gia Transport</a>: Fast expressway shuttles to the Phu Quoc ferry docks.</li>\n        <li><a href="/destinations/where-to-stay-in-soc-trang/">Where to Stay in Soc Trang</a>: Khmer pagoda hotels and Tran De port transit options.</li>',
                ["can-tho-to-rach-gia-transport", "where-to-stay-in-soc-trang"]
            )
        ],

        # --- 13: can-tho-to-chau-doc-transport -> can-tho-to-rach-gia-transport ---
        "can-tho-to-chau-doc-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a>:',
                '<li><a href="/plan/can-tho-to-rach-gia-transport/">Can Tho to Rach Gia Transport</a>: Fast connections to island fast ferries via Highway CT02.</li>\n        <li><a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a>:',
                ["can-tho-to-rach-gia-transport"]
            )
        ],

        # --- 14: phu-quoc-ferry-guide -> can-tho-to-rach-gia-transport ---
        "phu-quoc-ferry-guide": [
            (
                '<li><a href="/destinations/where-to-stay-in-rach-gia/">Where to Stay in Rach Gia</a>: Ferry pier transit hotels and waterfront stays.</li>',
                '<li><a href="/destinations/where-to-stay-in-rach-gia/">Where to Stay in Rach Gia</a>: Ferry pier transit hotels and waterfront stays.</li>\n        <li><a href="/plan/can-tho-to-rach-gia-transport/">Can Tho to Rach Gia Transport</a>: Overland transfers to connect with morning fast ferries.</li>',
                ["can-tho-to-rach-gia-transport"]
            )
        ],

        # --- 15: where-to-stay-in-soc-trang -> can-tho-to-rach-gia-transport ---
        "where-to-stay-in-soc-trang": [
            (
                'or explore regional urban stays in our <a href="/destinations/where-to-stay-in-can-tho/">where to stay in Can Tho</a> directory.',
                'explore regional urban stays in our <a href="/destinations/where-to-stay-in-can-tho/">where to stay in Can Tho</a> directory, or check island transit routes in our <a href="/plan/can-tho-to-rach-gia-transport/">Can Tho to Rach Gia transport</a> guide.',
                ["can-tho-to-rach-gia-transport"]
            )
        ],

        # --- 16: where-to-stay-in-dong-hoi -> hue-to-dong-hoi-transport ---
        "where-to-stay-in-dong-hoi": [
            (
                '<li><a href="/destinations/where-to-stay-in-phong-nha/">Where to Stay in Phong Nha</a>:',
                '<li><a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi Transport</a>: Reunification Express trains vs shared limousine vans.</li>\n        <li><a href="/destinations/where-to-stay-in-phong-nha/">Where to Stay in Phong Nha</a>:',
                ["hue-to-dong-hoi-transport"]
            )
        ],

        # --- 17: where-to-stay-in-hue -> hue-to-dong-hoi-transport ---
        "where-to-stay-in-hue": [
            (
                '<li><a href="/destinations/where-to-stay-in-lang-co/">Where to Stay in Lang Co</a>:',
                '<li><a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi Transport</a>: Coastal trains and limousine vans heading north to the cave gateway.</li>\n        <li><a href="/destinations/where-to-stay-in-lang-co/">Where to Stay in Lang Co</a>:',
                ["hue-to-dong-hoi-transport"]
            )
        ],

        # --- 18: where-to-stay-in-phong-nha -> hue-to-dong-hoi-transport ---
        "where-to-stay-in-phong-nha": [
            (
                '<li><a href="/destinations/where-to-stay-in-dong-hoi/">Where to Stay in Dong Hoi</a>:',
                '<li><a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi Transport</a>: Northbound trains and DMZ transfers toward the national park.</li>\n        <li><a href="/destinations/where-to-stay-in-dong-hoi/">Where to Stay in Dong Hoi</a>:',
                ["hue-to-dong-hoi-transport"]
            )
        ],

        # --- 19: vietnam-night-train-safety-tips -> hue-to-dong-hoi-transport & vietnam-domestic-flights-guide ---
        "vietnam-night-train-safety-tips": [
            (
                '<li><a href="/plan/vietnam-train-travel/">Vietnam Train Travel Guide</a>: Reunification Express routes, booking portals, and ticket classes.</li>',
                '<li><a href="/plan/vietnam-train-travel/">Vietnam Train Travel Guide</a>: Reunification Express routes, booking portals, and ticket classes.</li>\n        <li><a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi Transport</a>: Coastal railway schedules across the former DMZ.</li>\n        <li><a href="/plan/vietnam-domestic-flights-guide/">Vietnam Domestic Flights Guide</a>: Domestic flights vs coastal railways and night sleepers.</li>',
                ["hue-to-dong-hoi-transport", "vietnam-domestic-flights-guide"]
            )
        ],

        # --- 20: da-lat-to-buon-ma-thuot-transport -> hue-to-dong-hoi-transport ---
        "da-lat-to-buon-ma-thuot-transport": [
            (
                'review highland hotel options in <a href="/destinations/where-to-stay-in-da-lat/">where to stay in Da Lat</a>.',
                'review highland hotel options in <a href="/destinations/where-to-stay-in-da-lat/">where to stay in Da Lat</a>, or compare central overland connections via our <a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi transport</a> guide.',
                ["hue-to-dong-hoi-transport"]
            )
        ],

        # --- 21: where-to-stay-in-buon-ma-thuot -> da-lat-to-buon-ma-thuot-transport ---
        "where-to-stay-in-buon-ma-thuot": [
            (
                '<li><a href="/itineraries/central-highlands-vietnam-itinerary/">Central Highlands Vietnam Itinerary</a>: 5 to 7-day circuit from Da Lat to Buon Ma Thuot.</li>',
                '<li><a href="/itineraries/central-highlands-vietnam-itinerary/">Central Highlands Vietnam Itinerary</a>: 5 to 7-day circuit from Da Lat to Buon Ma Thuot.</li>\n        <li><a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot Transport</a>: Shared VIP limousines and scenic Highway 27 buses.</li>',
                ["da-lat-to-buon-ma-thuot-transport"]
            )
        ],

        # --- 22: where-to-stay-in-da-lat -> da-lat-to-buon-ma-thuot-transport ---
        "where-to-stay-in-da-lat": [
            (
                '<li><a href="/plan/da-lat-to-mui-ne-transport/">Da Lat to Mui Ne Transport</a>: Shared limousine vans and private cars descending to coastal beaches.</li>',
                '<li><a href="/plan/da-lat-to-mui-ne-transport/">Da Lat to Mui Ne Transport</a>: Shared limousine vans and private cars descending to coastal beaches.</li>\n        <li><a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot Transport</a>: Mountain highway limousines via Lak Lake.</li>',
                ["da-lat-to-buon-ma-thuot-transport"]
            )
        ],

        # --- 23: pleiku-to-kon-tum-transport -> da-lat-to-buon-ma-thuot-transport ---
        "pleiku-to-kon-tum-transport": [
            (
                '<li><a href="/plan/buon-ma-thuot-to-pleiku-transport/">Buon Ma Thuot to Pleiku Transport</a>: Highway 14 bus schedules an',
                '<li><a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot Transport</a>: Overland highland routes across Lam Dong and Dak Lak.</li>\n        <li><a href="/plan/buon-ma-thuot-to-pleiku-transport/">Buon Ma Thuot to Pleiku Transport</a>: Highway 14 bus schedules an',
                ["da-lat-to-buon-ma-thuot-transport"]
            )
        ],

        # --- 24: where-to-stay-in-pleiku -> da-lat-to-buon-ma-thuot-transport ---
        "where-to-stay-in-pleiku": [
            (
                '<li><a href="/plan/pleiku-to-kon-tum-transport/">Pleiku to Kon Tum Transport</a>: Public bus 02 vs taxis along Highway 14.</li>',
                '<li><a href="/plan/pleiku-to-kon-tum-transport/">Pleiku to Kon Tum Transport</a>: Public bus 02 vs taxis along Highway 14.</li>\n        <li><a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot Transport</a>: Connecting southern highlands with Dak Lak coffee country.</li>',
                ["da-lat-to-buon-ma-thuot-transport"]
            )
        ],

        # --- 25: where-to-stay-in-cam-ranh -> da-lat-to-buon-ma-thuot-transport & vietnam-domestic-flights-guide ---
        "where-to-stay-in-cam-ranh": [
            (
                '<li><a href="/destinations/where-to-stay-in-mui-ne/">Where to Stay in Mui Ne</a>: Coastal resort strip and windsurfing bays.</li>',
                '<li><a href="/destinations/where-to-stay-in-mui-ne/">Where to Stay in Mui Ne</a>: Coastal resort strip and windsurfing bays.</li>\n        <li><a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot Transport</a>: Highland crossings connecting pine hills with Dak Lak.</li>\n        <li><a href="/plan/vietnam-domestic-flights-guide/">Vietnam Domestic Flights Guide</a>: Comparing airlines, luggage limits, and terminal transfers.</li>',
                ["da-lat-to-buon-ma-thuot-transport", "vietnam-domestic-flights-guide"]
            )
        ],

        # --- 26: how-to-get-to-con-dao-flight-vs-ferry -> where-to-stay-in-soc-trang & vietnam-domestic-flights-guide ---
        "how-to-get-to-con-dao-flight-vs-ferry": [
            (
                '<li><a href="/destinations/where-to-stay-in-rach-gia/">Where to Stay in Rach Gia</a>: Mainland ferry port hotels for island crossings.</li>',
                '<li><a href="/destinations/where-to-stay-in-rach-gia/">Where to Stay in Rach Gia</a>: Mainland ferry port hotels for island crossings.</li>\n        <li><a href="/destinations/where-to-stay-in-soc-trang/">Where to Stay in Soc Trang</a>: City hotels and Tran De ferry harbor stays for Con Dao boats.</li>\n        <li><a href="/plan/vietnam-domestic-flights-guide/">Vietnam Domestic Flights Guide</a>: Airline comparisons, baggage fees, and connection windows.</li>',
                ["where-to-stay-in-soc-trang", "vietnam-domestic-flights-guide"]
            )
        ],

        # --- 27: where-to-stay-in-con-dao -> where-to-stay-in-soc-trang ---
        "where-to-stay-in-con-dao": [
            (
                '<li><a href="/compare/con-dao-vs-phu-quoc/">Con Dao vs Phu Quoc</a>: Choose between remote sanctuary and developed resort playground.</li>',
                '<li><a href="/compare/con-dao-vs-phu-quoc/">Con Dao vs Phu Quoc</a>: Choose between remote sanctuary and developed resort playground.</li>\n        <li><a href="/destinations/where-to-stay-in-soc-trang/">Where to Stay in Soc Trang</a>: Tran De port transit hotels and Khmer cultural city bases.</li>',
                ["where-to-stay-in-soc-trang"]
            )
        ],

        # --- 28: where-to-stay-in-ben-tre -> where-to-stay-in-soc-trang ---
        "where-to-stay-in-ben-tre": [
            (
                '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf hotels vs peaceful riverside lodges.</li>',
                '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf hotels vs peaceful riverside lodges.</li>\n        <li><a href="/destinations/where-to-stay-in-soc-trang/">Where to Stay in Soc Trang</a>: Southern delta Khmer temples and Con Dao boat transit stays.</li>',
                ["where-to-stay-in-soc-trang"]
            )
        ],

        # --- 29: can-tho-to-rach-gia-transport -> where-to-stay-in-soc-trang ---
        "can-tho-to-rach-gia-transport": [
            (
                'or explore our island beach recommendations in <a href="/destinations/where-to-stay-in-phu-quoc/">where to stay in Phu Quoc</a>.',
                'explore our island beach recommendations in <a href="/destinations/where-to-stay-in-phu-quoc/">where to stay in Phu Quoc</a>, or check southern transit stays in our <a href="/destinations/where-to-stay-in-soc-trang/">where to stay in Soc Trang</a> guide.',
                ["where-to-stay-in-soc-trang"]
            )
        ],

        # --- 30: vietnam-sleeper-bus-survival-guide -> vietnam-domestic-flights-guide ---
        "vietnam-sleeper-bus-survival-guide": [
            (
                '<li><a href="/plan/vietnam-night-train-safety-tips/">Vietnam Night Train Safety Tips</a>: Securing sleeper berths, door locks, and rail baggage rules.</li>',
                '<li><a href="/plan/vietnam-night-train-safety-tips/">Vietnam Night Train Safety Tips</a>: Securing sleeper berths, door locks, and rail baggage rules.</li>\n        <li><a href="/plan/vietnam-domestic-flights-guide/">Vietnam Domestic Flights Guide</a>: When to fly vs choosing long-distance sleeper buses.</li>',
                ["vietnam-domestic-flights-guide"]
            )
        ],

        # --- 31: hue-to-dong-hoi-transport -> vietnam-domestic-flights-guide ---
        "hue-to-dong-hoi-transport": [
            (
                'review the return route with our <a href="/plan/phongnha-to-hue/">Phong Nha to Hue</a> transport guide.',
                'review the return route with our <a href="/plan/phongnha-to-hue/">Phong Nha to Hue</a> transport guide, or review internal air connections with our <a href="/plan/vietnam-domestic-flights-guide/">Vietnam domestic flights guide</a>.',
                ["vietnam-domestic-flights-guide"]
            )
        ]
    }

    modified_posts = {}
    inlink_counts = {
        "ha-giang-to-cao-bang-transport": 0,
        "sapa-to-mu-cang-chai-transport": 0,
        "can-tho-to-rach-gia-transport": 0,
        "hue-to-dong-hoi-transport": 0,
        "da-lat-to-buon-ma-thuot-transport": 0,
        "where-to-stay-in-soc-trang": 0,
        "vietnam-domestic-flights-guide": 0
    }

    print("=== Applying and Validating Stage 81 Mesh Replacements ===")
    for slug, rules in replacements.items():
        if slug not in targets or not targets[slug]:
            print(f"[ERROR] Target slug {slug} not found in targets data!")
            sys.exit(1)
        
        post_data = targets[slug]
        content = post_data['content']
        orig_len = len(content)

        for target_str, repl_str, targeted_pillars in rules:
            match_count = content.count(target_str)
            if match_count == 0:
                print(f"[FAIL] In {slug}: target string not found!\nTarget: {target_str[:80]!r}")
                sys.exit(1)
            elif match_count > 1:
                print(f"[FAIL] In {slug}: target string found multiple times ({match_count})!\nTarget: {target_str[:80]!r}")
                sys.exit(1)
            
            content = content.replace(target_str, repl_str)
            for p in targeted_pillars:
                inlink_counts[p] += 1
        
        print(f"  [OK] {slug}: applied {len(rules)} replacements (length: {orig_len} -> {len(content)})")
        modified_posts[slug] = {
            'id': post_data['id'],
            'title': post_data['title'],
            'slug': post_data['slug'],
            'parent_id': post_data['parent_id'],
            'content': content
        }

    out_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage81_modified_targets.json')
    with open(out_path, 'w', encoding='utf-8') as f:
        json.dump(modified_posts, f, ensure_ascii=False, indent=2)

    print(f"\nSuccessfully wrote {len(modified_posts)} modified target posts to {out_path}")
    print("\n=== Verified Inbound Links per Stage 81 Pillar ===")
    all_passed = True
    total_inlinks = 0
    for p, count in inlink_counts.items():
        total_inlinks += count
        status = "[PASS]" if count >= 4 else "[FAIL]"
        print(f"  {status} {p}: {count} inbound links")
        if count < 4:
            all_passed = False

    print(f"\nTotal Stage 81 Inbound Links Created: {total_inlinks}")
    if all_passed and total_inlinks >= 28:
        print("ALL STAGE 81 MESH REQUIREMENTS SATISFIED!")
    else:
        print("FAIL: Inlink requirements not met!")
        sys.exit(1)

if __name__ == '__main__':
    main()

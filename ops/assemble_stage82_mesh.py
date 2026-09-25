# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 82: Assemble and Strictly Validate the Internal Link Mesh
Generates 35 verified inbound links across 26 host posts for all 7 Stage 82 pillars.
"""

import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    targets_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage82_mesh_targets.json')
    targets = json.load(open(targets_path, encoding='utf-8'))

    # Group replacements by host slug
    # Each host will have a list of (target_str, replacement_str, [target_pillar_slugs])
    replacements = {
        # --- 1: where-to-stay-in-quy-nhon -> hoi-an-to-quy-nhon-transport ---
        "where-to-stay-in-quy-nhon": [
            (
                '<li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Scenic rail journeys and limousine vans from Khanh Hoa.</li>',
                '<li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Scenic rail journeys and limousine vans from Khanh Hoa.</li>\n        <li><a href="/plan/hoi-an-to-quy-nhon-transport/">Hoi An to Quy Nhon Transport</a>: Direct sleeper buses and coastal rail transfers via Dieu Tri.</li>',
                ["hoi-an-to-quy-nhon-transport"]
            )
        ],

        # --- 2: where-to-stay-in-hoi-an -> hoi-an-to-quy-nhon-transport ---
        "where-to-stay-in-hoi-an": [
            (
                '<li><a href="/destinations/hoi-an-street-food-guide/">Hoi An Street Food Guide</a> &mdash; essential regional dishes, iconic',
                '<li><a href="/plan/hoi-an-to-quy-nhon-transport/">Hoi An to Quy Nhon Transport</a> &mdash; direct sleeper buses and trains southward to Binh Dinh.</li>\n<li><a href="/destinations/hoi-an-street-food-guide/">Hoi An Street Food Guide</a> &mdash; essential regional dishes, iconic',
                ["hoi-an-to-quy-nhon-transport"]
            )
        ],

        # --- 3: nha-trang-to-quy-nhon-transport -> hoi-an-to-quy-nhon-transport ---
        "nha-trang-to-quy-nhon-transport": [
            (
                '<li><a href="/plan/da-nang-to-quy-nhon-transport/">Da Nang to Quy Nhon Transport</a>: Trains, sleeper buses, and Vietage luxury rail.</li>',
                '<li><a href="/plan/da-nang-to-quy-nhon-transport/">Da Nang to Quy Nhon Transport</a>: Trains, sleeper buses, and Vietage luxury rail.</li>\n        <li><a href="/plan/hoi-an-to-quy-nhon-transport/">Hoi An to Quy Nhon Transport</a>: Connecting ancient Faifo to Quy Nhon via sleeper bus or train.</li>',
                ["hoi-an-to-quy-nhon-transport"]
            )
        ],

        # --- 4: da-nang-to-quy-nhon-transport -> hoi-an-to-quy-nhon-transport ---
        "da-nang-to-quy-nhon-transport": [
            (
                '<li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Coastal trains and Highway 1D minivans from the south.</li>',
                '<li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Coastal trains and Highway 1D minivans from the south.</li>\n        <li><a href="/plan/hoi-an-to-quy-nhon-transport/">Hoi An to Quy Nhon Transport</a>: Direct sleeper buses and trains via Dieu Tri or Da Nang.</li>',
                ["hoi-an-to-quy-nhon-transport"]
            )
        ],

        # --- 5: central-vietnam-itinerary -> hoi-an-to-quy-nhon-transport ---
        "central-vietnam-itinerary": [
            (
                '<li><a href="/destinations/hoi-an-ancient-town-guide/">Hoi An Ancient Town Guide</a>: Merchant houses, tailoring guides, and night markets.</li>',
                '<li><a href="/destinations/hoi-an-ancient-town-guide/">Hoi An Ancient Town Guide</a>: Merchant houses, tailoring guides, and night markets.</li>\n        <li><a href="/plan/hoi-an-to-quy-nhon-transport/">Hoi An to Quy Nhon Transport</a>: Overland sleeper buses and trains along Highway 1A.</li>',
                ["hoi-an-to-quy-nhon-transport"]
            )
        ],

        # --- 6: where-to-stay-in-cat-ba -> hanoi-to-hai-phong-transport ---
        "where-to-stay-in-cat-ba": [
            (
                '<li><a href="/plan/cat-ba-to-ninh-binh-transport/">Cat Ba to Ninh Binh Transport</a>: Direct tourist bus and ferry combos to Tam Coc.</li>',
                '<li><a href="/plan/cat-ba-to-ninh-binh-transport/">Cat Ba to Ninh Binh Transport</a>: Direct tourist bus and ferry combos to Tam Coc.</li>\n        <li><a href="/plan/hanoi-to-hai-phong-transport/">Hanoi to Hai Phong Transport</a>: Heritage express trains and Expressway 5B limousine vans.</li>',
                ["hanoi-to-hai-phong-transport"]
            )
        ],

        # --- 7: cat-ba-to-ninh-binh-transport -> hanoi-to-hai-phong-transport ---
        "cat-ba-to-ninh-binh-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a>: Comprehensive guide comparing Tam Coc, Trang An, and city center.</li>',
                '<li><a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a>: Comprehensive guide comparing Tam Coc, Trang An, and city center.</li>\n        <li><a href="/plan/hanoi-to-hai-phong-transport/">Hanoi to Hai Phong Transport</a>: Port transfers and food tour express trains.</li>',
                ["hanoi-to-hai-phong-transport"]
            )
        ],

        # --- 8: hanoi-to-cat-ba-island-transport -> hanoi-to-hai-phong-transport ---
        "hanoi-to-cat-ba-island-transport": [
            (
                '<li><a href="/plan/cat-ba-to-ninh-binh-transport/">Cat Ba to Ninh Binh Transport: Tourist Bus &amp; Ferry Combos</a></li>',
                '<li><a href="/plan/cat-ba-to-ninh-binh-transport/">Cat Ba to Ninh Binh Transport: Tourist Bus &amp; Ferry Combos</a></li>\n<li><a href="/plan/hanoi-to-hai-phong-transport/">Hanoi to Hai Phong Transport: Express Trains &amp; Expressway Vans</a></li>',
                ["hanoi-to-hai-phong-transport"]
            )
        ],

        # --- 9: hanoi-to-ha-long-bay-transport -> hanoi-to-hai-phong-transport ---
        "hanoi-to-ha-long-bay-transport": [
            (
                '<li><a href="/plan/hanoi-to-cat-ba-island-transport/">Hanoi to Cat Ba Island Transport</a> &mdash; compare tourist combo buses, speedboats, and ferry routes.</li>',
                '<li><a href="/plan/hanoi-to-cat-ba-island-transport/">Hanoi to Cat Ba Island Transport</a> &mdash; compare tourist combo buses, speedboats, and ferry routes.</li>\n<li><a href="/plan/hanoi-to-hai-phong-transport/">Hanoi to Hai Phong Transport</a> &mdash; express trains and 5B expressway limousine vans.</li>',
                ["hanoi-to-hai-phong-transport"]
            )
        ],

        # --- 10: phu-quoc-ferry-guide -> can-tho-to-ha-tien-transport ---
        "phu-quoc-ferry-guide": [
            (
                '<li><a href="/plan/can-tho-to-rach-gia-transport/">Can Tho to Rach Gia Transport</a>: Overland transfers to connect with morning fast ferries.</li>',
                '<li><a href="/plan/can-tho-to-rach-gia-transport/">Can Tho to Rach Gia Transport</a>: Overland transfers to connect with morning fast ferries.</li>\n        <li><a href="/plan/can-tho-to-ha-tien-transport/">Can Tho to Ha Tien Transport</a>: FUTA express coaches and minivans connecting to Ha Tien ferries.</li>',
                ["can-tho-to-ha-tien-transport"]
            )
        ],

        # --- 11: can-tho-to-rach-gia-transport -> can-tho-to-ha-tien-transport ---
        "can-tho-to-rach-gia-transport": [
            (
                'find transit hotels in our <a href="/destinations/where-to-stay-in-rach-gia/">where to stay in Rach Gia</a> directory,',
                'find transit hotels in our <a href="/destinations/where-to-stay-in-rach-gia/">where to stay in Rach Gia</a> directory, compare ferry staging routes in our <a href="/plan/can-tho-to-ha-tien-transport/">Can Tho to Ha Tien transport</a> guide,',
                ["can-tho-to-ha-tien-transport"]
            )
        ],

        # --- 12: can-tho-to-chau-doc-transport -> can-tho-to-ha-tien-transport ---
        "can-tho-to-chau-doc-transport": [
            (
                '<li><a href="/plan/can-tho-to-rach-gia-transport/">Can Tho to Rach Gia Transport</a>: Fast connections to island fast ferries via Highway CT02.</li>',
                '<li><a href="/plan/can-tho-to-rach-gia-transport/">Can Tho to Rach Gia Transport</a>: Fast connections to island fast ferries via Highway CT02.</li>\n        <li><a href="/plan/can-tho-to-ha-tien-transport/">Can Tho to Ha Tien Transport</a>: Cross the delta toward western coastal ferry ports.</li>',
                ["can-tho-to-ha-tien-transport"]
            )
        ],

        # --- 13: where-to-stay-in-ha-tien -> can-tho-to-ha-tien-transport ---
        "where-to-stay-in-ha-tien": [
            (
                '<li><a href="/plan/phu-quoc-ferry-guide/">Phu Quoc Ferry Guide</a>: Fast catamaran timetables, fares, and vehicle ferry advice.</li>',
                '<li><a href="/plan/phu-quoc-ferry-guide/">Phu Quoc Ferry Guide</a>: Fast catamaran timetables, fares, and vehicle ferry advice.</li>\n        <li><a href="/plan/can-tho-to-ha-tien-transport/">Can Tho to Ha Tien Transport</a>: Overland FUTA buses and shared minivans from Can Tho.</li>',
                ["can-tho-to-ha-tien-transport"]
            )
        ],

        # --- 14: where-to-stay-in-phu-quoc -> can-tho-to-ha-tien-transport ---
        "where-to-stay-in-phu-quoc": [
            (
                '<li><a href="/compare/con-dao-vs-phu-quoc/">Con Dao vs Phu Quoc</a></li>',
                '<li><a href="/compare/con-dao-vs-phu-quoc/">Con Dao vs Phu Quoc</a></li>\n        <li><a href="/plan/can-tho-to-ha-tien-transport/">Can Tho to Ha Tien Transport</a>: Overland routes connecting delta towns to island ferries.</li>',
                ["can-tho-to-ha-tien-transport"]
            )
        ],

        # --- 15: where-to-stay-in-rach-gia -> can-tho-to-ha-tien-transport & where-to-stay-in-ca-mau ---
        "where-to-stay-in-rach-gia": [
            (
                '<li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Border crossing bases and coastal riverfront hotels.</li>',
                '<li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Border crossing bases and coastal riverfront hotels.</li>\n        <li><a href="/destinations/where-to-stay-in-ca-mau/">Where to Stay in Ca Mau</a>: Southernmost city center hotels and Dat Mui eco-homestays.</li>\n        <li><a href="/plan/can-tho-to-ha-tien-transport/">Can Tho to Ha Tien Transport</a>: Express coaches and limousine minivans for early boats.</li>',
                ["can-tho-to-ha-tien-transport", "where-to-stay-in-ca-mau"]
            )
        ],

        # --- 16: da-lat-to-buon-ma-thuot-transport -> buon-ma-thuot-to-nha-trang-transport ---
        "da-lat-to-buon-ma-thuot-transport": [
            (
                'continue northward across the highlands with our <a href="/plan/pleiku-to-kon-tum-transport/">Pleiku to Kon Tum transport</a> guide,',
                'descend to the coast with our <a href="/plan/buon-ma-thuot-to-nha-trang-transport/">Buon Ma Thuot to Nha Trang transport</a> guide, continue northward across the highlands with our <a href="/plan/pleiku-to-kon-tum-transport/">Pleiku to Kon Tum transport</a> guide,',
                ["buon-ma-thuot-to-nha-trang-transport"]
            )
        ],

        # --- 17: where-to-stay-in-buon-ma-thuot -> buon-ma-thuot-to-nha-trang-transport ---
        "where-to-stay-in-buon-ma-thuot": [
            (
                '<li><a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot Transport</a>: Shared VIP limousines and scenic Highway 27 buses.</li>',
                '<li><a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot Transport</a>: Shared VIP limousines and scenic Highway 27 buses.</li>\n        <li><a href="/plan/buon-ma-thuot-to-nha-trang-transport/">Buon Ma Thuot to Nha Trang Transport</a>: Shared limousine vans and cars over Phoenix Pass to the coast.</li>',
                ["buon-ma-thuot-to-nha-trang-transport"]
            )
        ],

        # --- 18: where-to-stay-in-nha-trang -> buon-ma-thuot-to-nha-trang-transport ---
        "where-to-stay-in-nha-trang": [
            (
                '<li><a href="/destinations/where-to-stay-in-cam-ranh/">Where to Stay in Cam Ranh</a>: Bai Dai beach luxury resorts vs airport transit hotels.</li>',
                '<li><a href="/destinations/where-to-stay-in-cam-ranh/">Where to Stay in Cam Ranh</a>: Bai Dai beach luxury resorts vs airport transit hotels.</li>\n        <li><a href="/plan/buon-ma-thuot-to-nha-trang-transport/">Buon Ma Thuot to Nha Trang Transport</a>: Descending from the Central Highlands over Phoenix Pass.</li>',
                ["buon-ma-thuot-to-nha-trang-transport"]
            )
        ],

        # --- 19: pleiku-to-kon-tum-transport -> buon-ma-thuot-to-nha-trang-transport ---
        "pleiku-to-kon-tum-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum</a>: Dak Bla riverside stays, Bahnar lodges, and central hotels.</li>',
                '<li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum</a>: Dak Bla riverside stays, Bahnar lodges, and central hotels.</li>\n        <li><a href="/plan/buon-ma-thuot-to-nha-trang-transport/">Buon Ma Thuot to Nha Trang Transport</a>: Highland-to-coast descent via National Highway 26.</li>',
                ["buon-ma-thuot-to-nha-trang-transport"]
            )
        ],

        # --- 20: central-highlands-vietnam-itinerary -> buon-ma-thuot-to-nha-trang-transport ---
        "central-highlands-vietnam-itinerary": [
            (
                '<li><a href="/destinations/where-to-stay-in-buon-ma-thuot/">Where to Stay in Buon Ma Thuot</a>: Downtown business hotels, Ako Dhong village stilt stays, and Lak Lake lodges.</li>',
                '<li><a href="/destinations/where-to-stay-in-buon-ma-thuot/">Where to Stay in Buon Ma Thuot</a>: Downtown business hotels, Ako Dhong village stilt stays, and Lak Lake lodges.</li>\n        <li><a href="/plan/buon-ma-thuot-to-nha-trang-transport/">Buon Ma Thuot to Nha Trang Transport</a>: Connecting Dak Lak with coastal Khanh Hoa beaches.</li>',
                ["buon-ma-thuot-to-nha-trang-transport"]
            )
        ],

        # --- 21: where-to-stay-in-soc-trang -> where-to-stay-in-bac-lieu & where-to-stay-in-ca-mau ---
        "where-to-stay-in-soc-trang": [
            (
                'explore regional urban stays in our <a href="/destinations/where-to-stay-in-can-tho/">where to stay in Can Tho</a> directory,',
                'explore regional urban stays in our <a href="/destinations/where-to-stay-in-can-tho/">where to stay in Can Tho</a> directory, discover wind farm retreats in <a href="/destinations/where-to-stay-in-bac-lieu/">where to stay in Bac Lieu</a>,',
                ["where-to-stay-in-bac-lieu"]
            ),
            (
                'discover beachfront resorts in <a href="/destinations/where-to-stay-in-con-dao/">where to stay in Con Dao</a>,',
                'discover beachfront resorts in <a href="/destinations/where-to-stay-in-con-dao/">where to stay in Con Dao</a>, explore southernmost mangrove stays in <a href="/destinations/where-to-stay-in-ca-mau/">where to stay in Ca Mau</a>,',
                ["where-to-stay-in-ca-mau"]
            )
        ],

        # --- 22: where-to-stay-in-ben-tre -> where-to-stay-in-bac-lieu ---
        "where-to-stay-in-ben-tre": [
            (
                '<li><a href="/destinations/where-to-stay-in-soc-trang/">Where to Stay in Soc Trang</a>: Southern delta Khmer temples and Con Dao boat transit stays.</li>',
                '<li><a href="/destinations/where-to-stay-in-soc-trang/">Where to Stay in Soc Trang</a>: Southern delta Khmer temples and Con Dao boat transit stays.</li>\n        <li><a href="/destinations/where-to-stay-in-bac-lieu/">Where to Stay in Bac Lieu</a>: Historic Prince of Bac Lieu mansion hotels and coastal wind farm lodges.</li>',
                ["where-to-stay-in-bac-lieu"]
            )
        ],

        # --- 23: where-to-stay-in-can-tho -> where-to-stay-in-bac-lieu, where-to-stay-in-ca-mau ---
        "where-to-stay-in-can-tho": [
            (
                '<li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Strategic speedboat pier hotels and Mui Nai beach bases.</li>',
                '<li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Strategic speedboat pier hotels and Mui Nai beach bases.</li>\n        <li><a href="/destinations/where-to-stay-in-bac-lieu/">Where to Stay in Bac Lieu</a>: Historic mansion hotels and coastal wind farm stays.</li>',
                ["where-to-stay-in-bac-lieu"]
            ),
            (
                '<li><a href="/destinations/where-to-stay-in-soc-trang/">Where to Stay in Soc Trang</a>: Khmer pagoda hotels and Tran De port transit options.</li>',
                '<li><a href="/destinations/where-to-stay-in-soc-trang/">Where to Stay in Soc Trang</a>: Khmer pagoda hotels and Tran De port transit options.</li>\n        <li><a href="/destinations/where-to-stay-in-ca-mau/">Where to Stay in Ca Mau</a>: City business hotels and cape eco-lodges in Dat Mui.</li>',
                ["where-to-stay-in-ca-mau"]
            )
        ],

        # --- 24: where-to-stay-in-chau-doc -> where-to-stay-in-bac-lieu ---
        "where-to-stay-in-chau-doc": [
            (
                '<li><a href="/plan/vietnam-to-cambodia-boat-guide/">Vietnam to Cambodia Boat Guide</a>: High-speed Mekong river ferries, border stamps, and visas.</li>',
                '<li><a href="/plan/vietnam-to-cambodia-boat-guide/">Vietnam to Cambodia Boat Guide</a>: High-speed Mekong river ferries, border stamps, and visas.</li>\n        <li><a href="/destinations/where-to-stay-in-bac-lieu/">Where to Stay in Bac Lieu</a>: Prince of Bac Lieu colonial mansion hotels and coastal retreats.</li>',
                ["where-to-stay-in-bac-lieu"]
            )
        ],

        # --- 25: how-to-get-to-con-dao-flight-vs-ferry -> where-to-stay-in-ca-mau ---
        "how-to-get-to-con-dao-flight-vs-ferry": [
            (
                '<li><a href="/destinations/where-to-stay-in-soc-trang/">Where to Stay in Soc Trang</a>: City hotels and Tran De ferry harbor stays for Con Dao boats.</li>',
                '<li><a href="/destinations/where-to-stay-in-soc-trang/">Where to Stay in Soc Trang</a>: City hotels and Tran De ferry harbor stays for Con Dao boats.</li>\n        <li><a href="/destinations/where-to-stay-in-ca-mau/">Where to Stay in Ca Mau</a>: Southernmost city hotels and Dat Mui mangrove eco-lodges.</li>',
                ["where-to-stay-in-ca-mau"]
            )
        ],

        # --- 26: ho-chi-minh-city-to-can-tho-transport -> where-to-stay-in-ca-mau ---
        "ho-chi-minh-city-to-can-tho-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf vs riverside eco-resorts.</li>',
                '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf vs riverside eco-resorts.</li>\n        <li><a href="/destinations/where-to-stay-in-ca-mau/">Where to Stay in Ca Mau</a>: Exploring the southernmost mangrove tip of Vietnam.</li>',
                ["where-to-stay-in-ca-mau"]
            )
        ],

        # --- 27: vietnam-domestic-flights-guide -> vietnam-train-vs-flight ---
        "vietnam-domestic-flights-guide": [
            (
                'compare high-speed coastal trains in <a href="/plan/vietnam-night-train-safety-tips/">Vietnam night train tips</a>,',
                'evaluate coastal railways vs airfares in our <a href="/plan/vietnam-train-vs-flight/">Vietnam train vs flight</a> comparison, compare high-speed coastal trains in <a href="/plan/vietnam-night-train-safety-tips/">Vietnam night train tips</a>,',
                ["vietnam-train-vs-flight"]
            )
        ],

        # --- 28: vietnam-train-travel -> vietnam-train-vs-flight ---
        "vietnam-train-travel": [
            (
                '<li><a href="/plan/transport-within-vietnam/">Transport Within Vietnam: Trains vs Flights vs VIP Sleeper Buses</a></li>',
                '<li><a href="/plan/transport-within-vietnam/">Transport Within Vietnam: Trains vs Flights vs VIP Sleeper Buses</a></li>\n <li><a href="/plan/vietnam-train-vs-flight/">Vietnam Train vs Flight: Costs, Travel Times &amp; Route Comparisons</a></li>',
                ["vietnam-train-vs-flight"]
            )
        ],

        # --- 29: vietnam-sleeper-bus-survival-guide -> vietnam-train-vs-flight ---
        "vietnam-sleeper-bus-survival-guide": [
            (
                '<li><a href="/plan/vietnam-domestic-flights-guide/">Vietnam Domestic Flights Guide</a>: When to fly vs choosing long-distance sleeper buses.</li>',
                '<li><a href="/plan/vietnam-domestic-flights-guide/">Vietnam Domestic Flights Guide</a>: When to fly vs choosing long-distance sleeper buses.</li>\n        <li><a href="/plan/vietnam-train-vs-flight/">Vietnam Train vs Flight</a>: Cost, travel time, and baggage policy comparison.</li>',
                ["vietnam-train-vs-flight"]
            )
        ],

        # --- 30: da-nang-to-hue-train-vs-car -> vietnam-train-vs-flight ---
        "da-nang-to-hue-train-vs-car": [
            (
                '<li><a href="/destinations/where-to-stay-in-hue/">Where to Stay in Hue</a> &mdash; riverfront hotels vs Citadel proximity.</li>',
                '<li><a href="/destinations/where-to-stay-in-hue/">Where to Stay in Hue</a> &mdash; riverfront hotels vs Citadel proximity.</li>\n <li><a href="/plan/vietnam-train-vs-flight/">Vietnam Train vs Flight</a> &mdash; strategic breakdown of coastal rail vs domestic flights.</li>',
                ["vietnam-train-vs-flight"]
            )
        ],

        # --- 31: hanoi-to-sapa-train-vs-sleeper-bus -> vietnam-train-vs-flight ---
        "hanoi-to-sapa-train-vs-sleeper-bus": [
            (
                '<li><a href="/destinations/sapa-travel-guide/">Sapa Travel Guide</a>: Fansipan cable car, trekking routes, and hill tribe markets.</li>',
                '<li><a href="/destinations/sapa-travel-guide/">Sapa Travel Guide</a>: Fansipan cable car, trekking routes, and hill tribe markets.</li>\n        <li><a href="/plan/vietnam-train-vs-flight/">Vietnam Train vs Flight</a>: When to take scenic passenger railways vs domestic air connections.</li>',
                ["vietnam-train-vs-flight"]
            )
        ],

        # --- 32: vietnam-night-train-safety-tips -> vietnam-train-vs-flight ---
        "vietnam-night-train-safety-tips": [
            (
                '<li><a href="/plan/vietnam-train-travel/">Vietnam Train Travel Guide</a>: Reunification Express routes, booking portals, and ticket classes.</li>',
                '<li><a href="/plan/vietnam-train-travel/">Vietnam Train Travel Guide</a>: Reunification Express routes, booking portals, and ticket classes.</li>\n        <li><a href="/plan/vietnam-train-vs-flight/">Vietnam Train vs Flight</a>: Deciding between long-distance sleeper trains and domestic flights.</li>',
                ["vietnam-train-vs-flight"]
            )
        ],
    }

    print("=== Assembling Stage 82 Internal Link Mesh ===")
    inlink_counts = {
        "hoi-an-to-quy-nhon-transport": 0,
        "hanoi-to-hai-phong-transport": 0,
        "can-tho-to-ha-tien-transport": 0,
        "buon-ma-thuot-to-nha-trang-transport": 0,
        "where-to-stay-in-bac-lieu": 0,
        "where-to-stay-in-ca-mau": 0,
        "vietnam-train-vs-flight": 0,
    }

    updates = []
    errors = 0

    for host_slug, rule_list in replacements.items():
        if host_slug not in targets:
            print(f"[ERROR] Host post {host_slug} not found in targets!")
            errors += 1
            continue

        post_data = targets[host_slug]
        content = post_data['content']

        modified = content
        for target_str, replacement_str, pillars in rule_list:
            cnt = modified.count(target_str)
            if cnt != 1:
                print(f"[ERROR] Host '{host_slug}': found {cnt} occurrences of target string! (Expected 1)")
                errors += 1
                continue
            modified = modified.replace(target_str, replacement_str, 1)
            for p in pillars:
                inlink_counts[p] = inlink_counts.get(p, 0) + 1

        if modified != content:
            updates.append({
                'id': post_data['id'],
                'slug': host_slug,
                'content': modified
            })

    print(f"\nHost Posts to Update: {len(updates)}")
    print("\nVerified Inbound Links Added Per Target Pillar:")
    for pillar, cnt in sorted(inlink_counts.items()):
        status = "[PASS]" if cnt >= 4 else "[FAIL]"
        print(f"  {status} {pillar}: {cnt} inbound links")
        if cnt < 4:
            errors += 1

    if errors == 0:
        out_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage82_mesh_updates.json')
        with open(out_path, 'w', encoding='utf-8') as f:
            json.dump(updates, f, ensure_ascii=False, indent=2)
        print(f"\n[SUCCESS] Successfully assembled mesh updates for {len(updates)} host posts -> {out_path}")
    else:
        print(f"\n[FAIL] Found {errors} validation errors. Mesh updates aborted.")
        sys.exit(1)

if __name__ == '__main__':
    main()

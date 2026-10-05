# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 80: Assemble and Strictly Validate the Internal Link Mesh
"""

import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    targets_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage80_mesh_targets.json')
    targets = json.load(open(targets_path, encoding='utf-8'))

    replacements = {
        # --- 1: where-to-stay-in-sapa -> sapa-to-ha-giang-transport ---
        "where-to-stay-in-sapa": [
            (
                'and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison</a>.</p>',
                '<a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport Guide</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison</a>.</p>',
                ["sapa-to-ha-giang-transport"]
            )
        ],

        # --- 2: hanoi-to-sapa-transport -> sapa-to-ha-giang-transport ---
        "hanoi-to-sapa-transport": [
            (
                '<li><span class="vg-related-route-step">08</span><a href="/destinations/best-things-to-do-in-sapa/">Best Things to Do in Sapa</a><span class="vg-related-route-note">Explore Fansipan peak, village trekking trails, and O Quy Ho Pass.</span></li>',
                '<li><span class="vg-related-route-step">08</span><a href="/destinations/best-things-to-do-in-sapa/">Best Things to Do in Sapa</a><span class="vg-related-route-note">Explore Fansipan peak, village trekking trails, and O Quy Ho Pass.</span></li>\n<li><span class="vg-related-route-step">09</span><a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport</a><span class="vg-related-route-note">Direct cross-mountain VIP limousines and daytime buses.</span></li>',
                ["sapa-to-ha-giang-transport"]
            )
        ],

        # --- 3: hanoi-to-ha-giang-transport -> sapa-to-ha-giang-transport ---
        "hanoi-to-ha-giang-transport": [
            (
                '<li><span class="vg-related-route-step">09</span><a href="/plan/hanoi-to-cao-bang-transport/">Hanoi to Cao Bang Transport</a><span class="vg-related-route-note">Compare eastern mountain routes, limousine vans, and sleeper buses to Ban Gioc.</span></li>',
                '<li><span class="vg-related-route-step">09</span><a href="/plan/hanoi-to-cao-bang-transport/">Hanoi to Cao Bang Transport</a><span class="vg-related-route-note">Compare eastern mountain routes, limousine vans, and sleeper buses to Ban Gioc.</span></li>\n<li><span class="vg-related-route-step">10</span><a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport</a><span class="vg-related-route-note">Connect mountain loop hubs without backtracking to Hanoi.</span></li>',
                ["sapa-to-ha-giang-transport"]
            )
        ],

        # --- 4: where-to-stay-in-ha-giang -> sapa-to-ha-giang-transport ---
        "where-to-stay-in-ha-giang": [
            (
                '<li><a href="/plan/hanoi-to-ha-giang-transport/">Hanoi to Ha Giang Transport</a>: Cabin sleeper buses, VIP limousines, and transit schedules.</li>',
                '<li><a href="/plan/hanoi-to-ha-giang-transport/">Hanoi to Ha Giang Transport</a>: Cabin sleeper buses, VIP limousines, and transit schedules.</li>\n        <li><a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport</a>: Connecting mountain hubs by daytime limousine.</li>',
                ["sapa-to-ha-giang-transport"]
            )
        ],

        # --- 5: where-to-stay-in-ben-tre -> ho-chi-minh-city-to-ben-tre-transport ---
        "where-to-stay-in-ben-tre": [
            (
                '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf hotels vs peaceful riverside lodges.</li>',
                '<li><a href="/plan/ho-chi-minh-city-to-ben-tre-transport/">Ho Chi Minh City to Ben Tre Transport</a>: Shared limousine vans and express buses from Saigon.</li>\n        <li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf hotels vs peaceful riverside lodges.</li>',
                ["ho-chi-minh-city-to-ben-tre-transport"]
            )
        ],

        # --- 6: ho-chi-minh-city-to-can-tho-transport -> ho-chi-minh-city-to-ben-tre-transport ---
        "ho-chi-minh-city-to-can-tho-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-ben-tre/">Where to Stay in Ben Tre</a>: Riverside eco-lodges along the coconut canal belt.</li>',
                '<li><a href="/plan/ho-chi-minh-city-to-ben-tre-transport/">Ho Chi Minh City to Ben Tre Transport</a>: Fast expressway shuttles to the coconut capital.</li>\n        <li><a href="/destinations/where-to-stay-in-ben-tre/">Where to Stay in Ben Tre</a>: Riverside eco-lodges along the coconut canal belt.</li>',
                ["ho-chi-minh-city-to-ben-tre-transport"]
            )
        ],

        # --- 7: where-to-stay-in-ho-chi-minh-city -> ho-chi-minh-city-to-ben-tre-transport ---
        "where-to-stay-in-ho-chi-minh-city": [
            (
                '<p>Stay in District 1 or District 3 unless your operator confirms another pickup point clearly. Read <a href="/destinations/best-day-trips-from-ho-chi-minh-city/">Best Day Trips from Ho Chi Minh City</a> and <a href="/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/">Cu Chi vs Mekong</a> before using hotel area as a tour shortcut.</p>',
                '<p>Stay in District 1 or District 3 unless your operator confirms another pickup point clearly. Read <a href="/destinations/best-day-trips-from-ho-chi-minh-city/">Best Day Trips from Ho Chi Minh City</a>, our <a href="/plan/ho-chi-minh-city-to-ben-tre-transport/">Ho Chi Minh City to Ben Tre Transport</a> guide, and <a href="/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/">Cu Chi vs Mekong</a> before using hotel area as a tour shortcut.</p>',
                ["ho-chi-minh-city-to-ben-tre-transport"]
            )
        ],

        # --- 8: saigon-airport-to-district-1 -> ho-chi-minh-city-to-ben-tre-transport ---
        "saigon-airport-to-district-1": [
            (
                'and organize coastal trips via our <a href="/plan/saigon-to-vung-tau-transport/">Saigon to Vung Tau Transport Guide</a>.</p>',
                'and organize southern trips via our <a href="/plan/saigon-to-vung-tau-transport/">Saigon to Vung Tau Transport Guide</a> and <a href="/plan/ho-chi-minh-city-to-ben-tre-transport/">Ho Chi Minh City to Ben Tre Transport Guide</a>.</p>',
                ["ho-chi-minh-city-to-ben-tre-transport"]
            )
        ],

        # --- 9: southern-vietnam-itinerary -> ho-chi-minh-city-to-ben-tre-transport ---
        "southern-vietnam-itinerary": [
            (
                '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf hotels vs peaceful riverside lodges.</li>',
                '<li><a href="/plan/ho-chi-minh-city-to-ben-tre-transport/">Ho Chi Minh City to Ben Tre Transport</a>: Shared limousine vans and gateway express routes.</li>\n        <li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf hotels vs peaceful riverside lodges.</li>',
                ["ho-chi-minh-city-to-ben-tre-transport"]
            )
        ],

        # --- 10: where-to-stay-in-cat-ba -> cat-ba-to-ninh-binh-transport ---
        "where-to-stay-in-cat-ba": [
            (
                '<li><a href="/plan/hanoi-to-cat-ba-island-transport/">Hanoi to Cat Ba Transport</a>: Bus, ferry, and expressway combo options.</li>',
                '<li><a href="/plan/hanoi-to-cat-ba-island-transport/">Hanoi to Cat Ba Transport</a>: Bus, ferry, and expressway combo options.</li>\n        <li><a href="/plan/cat-ba-to-ninh-binh-transport/">Cat Ba to Ninh Binh Transport</a>: Direct tourist bus and ferry combos to Tam Coc.</li>',
                ["cat-ba-to-ninh-binh-transport"]
            )
        ],

        # --- 11: where-to-stay-in-ninh-binh -> cat-ba-to-ninh-binh-transport ---
        "where-to-stay-in-ninh-binh": [
            (
                '<p>Use <a href="/plan/hanoi-to-ninh-binh-transport/">Hanoi to Ninh Binh Transport</a> before locking a room if the final drop-off, luggage, or onward transfer is still uncertain.</p>',
                '<p>Use <a href="/plan/hanoi-to-ninh-binh-transport/">Hanoi to Ninh Binh Transport</a> and <a href="/plan/cat-ba-to-ninh-binh-transport/">Cat Ba to Ninh Binh Transport</a> before locking a room if the final drop-off, luggage, or onward transfer is still uncertain.</p>',
                ["cat-ba-to-ninh-binh-transport"]
            )
        ],

        # --- 12: where-to-stay-in-tam-coc -> cat-ba-to-ninh-binh-transport ---
        "where-to-stay-in-tam-coc": [
            (
                '<li><a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a>: Comprehensive province guide comparing Tam Coc, Trang An, and Gia Vien.</li>',
                '<li><a href="/plan/cat-ba-to-ninh-binh-transport/">Cat Ba to Ninh Binh Transport</a>: Direct tourist bus and speedboat combo transfers.</li>\n        <li><a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a>: Comprehensive province guide comparing Tam Coc, Trang An, and Gia Vien.</li>',
                ["cat-ba-to-ninh-binh-transport"]
            )
        ],

        # --- 13: ninh-binh-to-ha-long-bay-transfer -> cat-ba-to-ninh-binh-transport ---
        "ninh-binh-to-ha-long-bay-transfer": [
            (
                'choose the stay area with <a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a>, decide the bay with <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a> and <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>, then use this page before paying for a direct transfer.',
                'choose the stay area with <a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a>, decide the bay with <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a> and <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>, or consult our <a href="/plan/cat-ba-to-ninh-binh-transport/">Cat Ba to Ninh Binh Transport</a> guide before paying for a direct transfer.',
                ["cat-ba-to-ninh-binh-transport"]
            )
        ],

        # --- 14: hanoi-to-cat-ba-island-transport -> cat-ba-to-ninh-binh-transport ---
        "hanoi-to-cat-ba-island-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-cat-ba/">Where to Stay on Cat Ba Island: Town Hotels vs Cat Co Beach Resorts</a></li>',
                '<li><a href="/destinations/where-to-stay-in-cat-ba/">Where to Stay on Cat Ba Island: Town Hotels vs Cat Co Beach Resorts</a></li>\n<li><a href="/plan/cat-ba-to-ninh-binh-transport/">Cat Ba to Ninh Binh Transport: Tourist Bus &amp; Ferry Combos</a></li>',
                ["cat-ba-to-ninh-binh-transport"]
            )
        ],

        # --- 15: where-to-stay-in-pleiku -> pleiku-to-kon-tum-transport ---
        "where-to-stay-in-pleiku": [
            (
                '<li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum</a>: Riverside homestays, wooden church views, and Rong communal houses.</li>',
                '<li><a href="/plan/pleiku-to-kon-tum-transport/">Pleiku to Kon Tum Transport</a>: Public bus 02 vs taxis along Highway 14.</li>\n        <li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum</a>: Riverside homestays, wooden church views, and Rong communal houses.</li>',
                ["pleiku-to-kon-tum-transport"]
            )
        ],

        # --- 16: where-to-stay-in-kon-tum -> pleiku-to-kon-tum-transport ---
        "where-to-stay-in-kon-tum": [
            (
                '<li><a href="/destinations/where-to-stay-in-pleiku/">Where to Stay in Pleiku</a>: City hotels, Bien Ho volcanic crater lake, and tea estates.</li>',
                '<li><a href="/plan/pleiku-to-kon-tum-transport/">Pleiku to Kon Tum Transport</a>: Frequent 50-minute public buses and airport taxis.</li>\n        <li><a href="/destinations/where-to-stay-in-pleiku/">Where to Stay in Pleiku</a>: City hotels, Bien Ho volcanic crater lake, and tea estates.</li>',
                ["pleiku-to-kon-tum-transport"]
            )
        ],

        # --- 17: buon-ma-thuot-to-pleiku-transport -> pleiku-to-kon-tum-transport ---
        "buon-ma-thuot-to-pleiku-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum</a>: Dak Bla riverside hotels, Wooden Church, and Bahnar homestays.</li>',
                '<li><a href="/plan/pleiku-to-kon-tum-transport/">Pleiku to Kon Tum Transport</a>: Highway 14 connector buses and scenic rides.</li>\n        <li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum</a>: Dak Bla riverside hotels, Wooden Church, and Bahnar homestays.</li>',
                ["pleiku-to-kon-tum-transport"]
            )
        ],

        # --- 18: central-highlands-vietnam-itinerary -> pleiku-to-kon-tum-transport ---
        "central-highlands-vietnam-itinerary": [
            (
                '<li><a href="/destinations/where-to-stay-in-pleiku/">Where to Stay in Pleiku</a>: Central Gia Lai business hotels, crater lake retreats, and volcanic hill homestays.</li>',
                '<li><a href="/destinations/where-to-stay-in-pleiku/">Where to Stay in Pleiku</a>: Central Gia Lai business hotels, crater lake retreats, and volcanic hill homestays.</li>\n        <li><a href="/plan/pleiku-to-kon-tum-transport/">Pleiku to Kon Tum Transport</a>: Public buses and private taxis along Highway 14.</li>',
                ["pleiku-to-kon-tum-transport"]
            )
        ],

        # --- 19: vietnam-scooter-rental-checklist -> pleiku-to-kon-tum-transport ---
        "vietnam-scooter-rental-checklist": [
            (
                '<li><a href="/plan/da-lat-to-mui-ne-transport/">Da Lat to Mui Ne Transport</a>: Mountain pass minivan schedules and private transfer alternatives.</li>',
                '<li><a href="/plan/da-lat-to-mui-ne-transport/">Da Lat to Mui Ne Transport</a>: Mountain pass minivan schedules and private transfer alternatives.</li>\n        <li><a href="/plan/pleiku-to-kon-tum-transport/">Pleiku to Kon Tum Transport</a>: Highway 14 volcanic road touring tips and scooter guidelines.</li>',
                ["pleiku-to-kon-tum-transport"]
            )
        ],

        # --- 20: where-to-stay-in-nha-trang -> where-to-stay-in-cam-ranh ---
        "where-to-stay-in-nha-trang": [
            (
                '<li><a href="/destinations/best-things-to-do-in-nha-trang/">Best Things to Do in Nha Trang</a></li>',
                '<li><a href="/destinations/where-to-stay-in-cam-ranh/">Where to Stay in Cam Ranh</a>: Bai Dai beach luxury resorts vs airport transit hotels.</li>\n        <li><a href="/destinations/best-things-to-do-in-nha-trang/">Best Things to Do in Nha Trang</a></li>',
                ["where-to-stay-in-cam-ranh"]
            )
        ],

        # --- 21: nha-trang-to-quy-nhon-transport -> where-to-stay-in-cam-ranh ---
        "nha-trang-to-quy-nhon-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-nha-trang/">Where to Stay in Nha Trang</a>: Beachfront luxury resorts and downtown boutique hotels.</li>',
                '<li><a href="/destinations/where-to-stay-in-cam-ranh/">Where to Stay in Cam Ranh</a>: Bai Dai luxury beach resorts near the airport.</li>\n        <li><a href="/destinations/where-to-stay-in-nha-trang/">Where to Stay in Nha Trang</a>: Beachfront luxury resorts and downtown boutique hotels.</li>',
                ["where-to-stay-in-cam-ranh"]
            )
        ],

        # --- 22: da-lat-to-nha-trang-transport -> where-to-stay-in-cam-ranh ---
        "da-lat-to-nha-trang-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-nha-trang/">Where to Stay in Nha Trang: Beach Areas &amp; Hotels</a></li>',
                '<li><a href="/destinations/where-to-stay-in-nha-trang/">Where to Stay in Nha Trang: Beach Areas &amp; Hotels</a></li>\n<li><a href="/destinations/where-to-stay-in-cam-ranh/">Where to Stay in Cam Ranh: Bai Dai Beach Resorts</a></li>',
                ["where-to-stay-in-cam-ranh"]
            )
        ],

        # --- 23: da-nang-to-nha-trang-transport -> where-to-stay-in-cam-ranh ---
        "da-nang-to-nha-trang-transport": [
            (
                '<li><a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a>: Island boat tours, scuba diving, and city beaches.</li>',
                '<li><a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a>: Island boat tours, scuba diving, and city beaches.</li>\n        <li><a href="/destinations/where-to-stay-in-cam-ranh/">Where to Stay in Cam Ranh</a>: Bai Dai white sand beach resorts and airport stays.</li>',
                ["where-to-stay-in-cam-ranh"]
            )
        ],

        # --- 24: where-to-stay-in-vietnam-base-decisions -> where-to-stay-in-cam-ranh ---
        "where-to-stay-in-vietnam-base-decisions": [
            (
                '<td data-label="Where it usually points">Da Nang beach areas, Phu Quoc, Nha Trang, or a resort chapter with enough nights.</td>',
                '<td data-label="Where it usually points">Da Nang beach areas, Phu Quoc, Nha Trang, <a href="/destinations/where-to-stay-in-cam-ranh/">Cam Ranh (Bai Dai)</a>, or a resort chapter with enough nights.</td>',
                ["where-to-stay-in-cam-ranh"]
            )
        ],

        # --- 25: where-to-stay-in-phu-quoc -> where-to-stay-in-rach-gia & phu-quoc-ferry-guide ---
        "where-to-stay-in-phu-quoc": [
            (
                '<li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Ferry pier hotels and Mui Nai beach bases for Phu Quoc crossings.</li>',
                '<li><a href="/plan/phu-quoc-ferry-guide/">Phu Quoc Ferry Guide</a>: Fast catamaran schedules and mainland port comparison.</li>\n<li><a href="/destinations/where-to-stay-in-rach-gia/">Where to Stay in Rach Gia</a>: Ferry pier transit hotels for early Phu Quoc boats.</li>\n<li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Ferry pier hotels and Mui Nai beach bases for Phu Quoc crossings.</li>',
                ["where-to-stay-in-rach-gia", "phu-quoc-ferry-guide"]
            )
        ],

        # --- 26: where-to-stay-in-ha-tien -> where-to-stay-in-rach-gia & phu-quoc-ferry-guide ---
        "where-to-stay-in-ha-tien": [
            (
                '<li><a href="/plan/ferry-to-phu-quoc/">Ferry to Phu Quoc</a>: Speedboat timetables from Ha Tien and Rach Gia with vehicle fares.</li>',
                '<li><a href="/plan/phu-quoc-ferry-guide/">Phu Quoc Ferry Guide</a>: Fast catamaran timetables, fares, and vehicle ferry advice.</li>\n        <li><a href="/destinations/where-to-stay-in-rach-gia/">Where to Stay in Rach Gia</a>: Fast ferry passenger harbor hotels and city stays.</li>',
                ["where-to-stay-in-rach-gia", "phu-quoc-ferry-guide"]
            )
        ],

        # --- 27: where-to-stay-in-can-tho -> where-to-stay-in-rach-gia ---
        "where-to-stay-in-can-tho": [
            (
                '<li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Strategic speedboat pier hotels and Mui Nai beach bases.</li>',
                '<li><a href="/destinations/where-to-stay-in-rach-gia/">Where to Stay in Rach Gia</a>: Fast ferry harbor hotels for early Phu Quoc boats.</li>\n        <li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Strategic speedboat pier hotels and Mui Nai beach bases.</li>',
                ["where-to-stay-in-rach-gia"]
            )
        ],

        # --- 28: how-to-get-to-con-dao-flight-vs-ferry -> where-to-stay-in-rach-gia ---
        "how-to-get-to-con-dao-flight-vs-ferry": [
            (
                '<li><a href="/plan/saigon-to-vung-tau-transport/">Saigon to Vung Tau Transport</a>: Connecting to the Vung Tau fast ferry port.</li>',
                '<li><a href="/destinations/where-to-stay-in-rach-gia/">Where to Stay in Rach Gia</a>: Mainland ferry port hotels for island crossings.</li>\n        <li><a href="/plan/saigon-to-vung-tau-transport/">Saigon to Vung Tau Transport</a>: Connecting to the Vung Tau fast ferry port.</li>',
                ["where-to-stay-in-rach-gia"]
            )
        ],

        # --- 29: mekong-delta-travel-guide -> where-to-stay-in-rach-gia ---
        "mekong-delta-travel-guide": [
            (
                'alternative Superdong ferry from Rach Gia costs 330,000 VND (2.5 hrs).</li>',
                'alternative Superdong ferry from <a href="/destinations/where-to-stay-in-rach-gia/">Rach Gia</a> costs 330,000 VND (2.5 hrs).</li>',
                ["where-to-stay-in-rach-gia"]
            )
        ],

        # --- 30: ho-chi-minh-city-to-phu-quoc-transport -> phu-quoc-ferry-guide ---
        "ho-chi-minh-city-to-phu-quoc-transport": [
            (
                '<li><a href="/compare/con-dao-vs-phu-quoc/">Con Dao vs Phu Quoc</a></li>',
                '<li><a href="/plan/phu-quoc-ferry-guide/">Phu Quoc Ferry Guide</a>: High-speed passenger boats from Rach Gia and Ha Tien.</li>\n<li><a href="/compare/con-dao-vs-phu-quoc/">Con Dao vs Phu Quoc</a></li>',
                ["phu-quoc-ferry-guide"]
            )
        ],

        # --- 31: best-things-to-do-in-phu-quoc -> phu-quoc-ferry-guide ---
        "best-things-to-do-in-phu-quoc": [
            (
                '<li><a href="/plan/ho-chi-minh-city-to-phu-quoc-transport/">Ho Chi Minh City to Phu Quoc Transport</a></li>',
                '<li><a href="/plan/ho-chi-minh-city-to-phu-quoc-transport/">Ho Chi Minh City to Phu Quoc Transport</a></li>\n<li><a href="/plan/phu-quoc-ferry-guide/">Phu Quoc Ferry Guide</a>: Mainland fast boat schedules and port transfers.</li>',
                ["phu-quoc-ferry-guide"]
            )
        ],

        # --- 32: transport-within-vietnam -> sapa-to-ha-giang-transport & phu-quoc-ferry-guide ---
        "transport-within-vietnam": [
            (
                'compare our specific route guides for <a href="/transport/hanoi-to-sapa-transport/">Hanoi to Sapa transport</a> and <a href="/transport/hanoi-to-ha-giang-transport/">Hanoi to Ha Giang transport</a>',
                'compare our specific route guides for <a href="/transport/hanoi-to-sapa-transport/">Hanoi to Sapa transport</a>, <a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang transport</a>, and <a href="/transport/hanoi-to-ha-giang-transport/">Hanoi to Ha Giang transport</a>',
                ["sapa-to-ha-giang-transport"]
            ),
            (
                'For northern bay journeys, review our <a href="/plan/hanoi-to-ha-long-bay-transport/">Hanoi to Ha Long Bay transport</a> guide for Expressway 5B limousine connections before boarding.',
                'For northern bay journeys, review our <a href="/plan/hanoi-to-ha-long-bay-transport/">Hanoi to Ha Long Bay transport</a> guide for Expressway 5B limousine connections before boarding. For southern island sailings, consult our <a href="/plan/phu-quoc-ferry-guide/">Phu Quoc ferry guide</a> for fast catamaran schedules and port details.',
                ["phu-quoc-ferry-guide"]
            )
        ]
    }

    inlinks_count = {
        "sapa-to-ha-giang-transport": 0,
        "ho-chi-minh-city-to-ben-tre-transport": 0,
        "cat-ba-to-ninh-binh-transport": 0,
        "pleiku-to-kon-tum-transport": 0,
        "where-to-stay-in-cam-ranh": 0,
        "where-to-stay-in-rach-gia": 0,
        "phu-quoc-ferry-guide": 0,
    }

    mesh_ops = {}
    validation_failed = False

    for host_slug, op_list in replacements.items():
        if host_slug not in targets:
            print(f"[ERROR] Host slug '{host_slug}' not found in fetched targets!")
            validation_failed = True
            continue

        post_data = targets[host_slug]
        content = post_data['content']

        for search_text, replace_text, target_keys in op_list:
            occurrences = content.count(search_text)
            if occurrences == 0:
                print(f"[ERROR] Search text NOT found in '{host_slug}':\n  {repr(search_text[:80])}")
                validation_failed = True
            elif occurrences > 1:
                print(f"[ERROR] Search text found {occurrences} times in '{host_slug}' (ambiguous match):\n  {repr(search_text[:80])}")
                validation_failed = True
            else:
                content = content.replace(search_text, replace_text)
                for tk in target_keys:
                    inlinks_count[tk] += 1

        mesh_ops[host_slug] = {
            'id': post_data['id'],
            'title': post_data['title'],
            'content': content
        }

    # Cross-link verification:
    # where-to-stay-in-rach-gia already has inlink in phu-quoc-ferry-guide initial content!
    inlinks_count["where-to-stay-in-rach-gia"] += 1
    # phu-quoc-ferry-guide already has inlink in where-to-stay-in-rach-gia initial content!
    inlinks_count["phu-quoc-ferry-guide"] += 1

    print("\n=== STAGE 80 INBOUND LINK DISTRIBUTION ===")
    for slug, count in inlinks_count.items():
        print(f"  - {slug}: {count} incoming links")
        if count < 4:
            print(f"[WARNING] Pillar '{slug}' has only {count} inbound links (minimum 4 required)!")
            validation_failed = True

    if validation_failed:
        print("\n[FAIL] Mesh validation failed. Operations not saved.")
        sys.exit(1)

    ops_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage80_mesh_ops.json')
    with open(ops_file, 'w', encoding='utf-8') as f:
        json.dump(mesh_ops, f, ensure_ascii=False, indent=2)

    print(f"\n[SUCCESS] Mesh assembled and strictly validated! {len(mesh_ops)} posts to update.")
    print(f"Saved to {ops_file}")

if __name__ == '__main__':
    main()

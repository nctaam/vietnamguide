# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 77: Assemble and Strictly Validate the 28 Inbound Links Mesh
"""

import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    targets_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage77_mesh_targets.json')
    targets = json.load(open(targets_path, encoding='utf-8'))

    replacements = {
        # --- 1: where-to-stay-in-phong-nha -> where-to-stay-in-dong-hoi ---
        "where-to-stay-in-phong-nha": [
            (
                '<li><a href="/plan/phong-nha-to-hue-transport/">Phong Nha to Hue Transport</a></li>',
                '<li><a href="/plan/phong-nha-to-hue-transport/">Phong Nha to Hue Transport</a></li>\n        <li><a href="/destinations/where-to-stay-in-dong-hoi/">Where to Stay in Dong Hoi</a>: Beachfront resorts and city center transit hotels.</li>',
                ["where-to-stay-in-dong-hoi"]
            )
        ],

        # --- 2: phong-nha-travel-guide -> where-to-stay-in-dong-hoi, da-nang-to-phong-nha-transport ---
        "phong-nha-travel-guide": [
            (
                '<li><span class="vg-related-route-step">08</span><a href="/destinations/where-to-stay-in-phong-nha/">Where to Stay in Phong Nha</a><span class="vg-related-route-note">Choose riverside farmstays, eco-lodges, or Son Trach village hostels.</span></li>',
                '<li><span class="vg-related-route-step">08</span><a href="/destinations/where-to-stay-in-phong-nha/">Where to Stay in Phong Nha</a><span class="vg-related-route-note">Choose riverside farmstays, eco-lodges, or Son Trach village hostels.</span></li>\n<li><span class="vg-related-route-step">09</span><a href="/destinations/where-to-stay-in-dong-hoi/">Where to Stay in Dong Hoi</a><span class="vg-related-route-note">Beachfront resorts and city center hotels near railway and airport hubs.</span></li>\n<li><span class="vg-related-route-step">10</span><a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport</a><span class="vg-related-route-note">Reunification Express train, direct sleeper buses, and DMZ transfers.</span></li>',
                ["where-to-stay-in-dong-hoi", "da-nang-to-phong-nha-transport"]
            )
        ],

        # --- 3: phong-nha-to-hue-transport -> where-to-stay-in-dong-hoi, da-nang-to-phong-nha-transport ---
        "phong-nha-to-hue-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-phong-nha/">Where to Stay in Phong Nha: Farmstays, Eco-Lodges &amp; Hostels</a></li>',
                '<li><a href="/destinations/where-to-stay-in-phong-nha/">Where to Stay in Phong Nha: Farmstays, Eco-Lodges &amp; Hostels</a></li>\n<li><a href="/destinations/where-to-stay-in-dong-hoi/">Where to Stay in Dong Hoi: Nhat Le Beach &amp; City Hotels</a></li>\n<li><a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport: Train vs Bus</a></li>',
                ["where-to-stay-in-dong-hoi", "da-nang-to-phong-nha-transport"]
            )
        ],

        # --- 4: hanoi-to-phong-nha-transport -> where-to-stay-in-dong-hoi, da-nang-to-phong-nha-transport ---
        "hanoi-to-phong-nha-transport": [
            (
                '<li><a href="/plan/phong-nha-to-hue-transport/">Phong Nha to Hue Transport</a>: Heading further south? Here is how to cross the DMZ and reach the Imperial City.</li>',
                '<li><a href="/plan/phong-nha-to-hue-transport/">Phong Nha to Hue Transport</a>: Heading further south? Here is how to cross the DMZ and reach the Imperial City.</li>\n    <li><a href="/destinations/where-to-stay-in-dong-hoi/">Where to Stay in Dong Hoi</a>: Nhat Le beach resorts and railway station hotels for cave travelers.</li>\n    <li><a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport</a>: Reunification Express train schedules and direct sleeper buses.</li>',
                ["where-to-stay-in-dong-hoi", "da-nang-to-phong-nha-transport"]
            )
        ],

        # --- 5: da-lat-travel-guide -> da-lat-to-mui-ne-transport ---
        "da-lat-travel-guide": [
            (
                '<li><a href="/plan/ho-chi-minh-city-to-da-lat-transport/">Ho Chi Minh City to Da Lat Transport: Sleeper Buses, Flights &amp; Private Cars</a></li>',
                '<li><a href="/plan/ho-chi-minh-city-to-da-lat-transport/">Ho Chi Minh City to Da Lat Transport: Sleeper Buses, Flights &amp; Private Cars</a></li>\n<li><a href="/plan/da-lat-to-mui-ne-transport/">Da Lat to Mui Ne Transport: Minivan vs Bus via Dai Ninh Pass</a></li>',
                ["da-lat-to-mui-ne-transport"]
            )
        ],

        # --- 6: where-to-stay-in-da-lat -> da-lat-to-mui-ne-transport ---
        "where-to-stay-in-da-lat": [
            (
                '<li><a href="/plan/ho-chi-minh-city-to-da-lat-transport/">Ho Chi Minh City to Da Lat Transport</a></li>',
                '<li><a href="/plan/ho-chi-minh-city-to-da-lat-transport/">Ho Chi Minh City to Da Lat Transport</a></li>\n        <li><a href="/plan/da-lat-to-mui-ne-transport/">Da Lat to Mui Ne Transport</a>: Shared limousine vans and private cars descending to coastal beaches.</li>',
                ["da-lat-to-mui-ne-transport"]
            )
        ],

        # --- 7: where-to-stay-in-mui-ne -> da-lat-to-mui-ne-transport ---
        "where-to-stay-in-mui-ne": [
            (
                '<li><a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a>: Expressway limousines, buses, and trains.</li>',
                '<li><a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a>: Expressway limousines, buses, and trains.</li>\n        <li><a href="/plan/da-lat-to-mui-ne-transport/">Da Lat to Mui Ne Transport</a>: Mountain pass minivans and private transfers from the highlands.</li>',
                ["da-lat-to-mui-ne-transport"]
            )
        ],

        # --- 8: best-things-to-do-in-mui-ne -> da-lat-to-mui-ne-transport ---
        "best-things-to-do-in-mui-ne": [
            (
                '<li><a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a>: Expressway buses, trains, and cars.</li>',
                '<li><a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a>: Expressway buses, trains, and cars.</li>\n        <li><a href="/plan/da-lat-to-mui-ne-transport/">Da Lat to Mui Ne Transport</a>: Shared limousine minivans descending Dai Ninh Pass.</li>',
                ["da-lat-to-mui-ne-transport"]
            )
        ],

        # --- 9: central-highlands-vietnam-itinerary -> where-to-stay-in-pleiku ---
        "central-highlands-vietnam-itinerary": [
            (
                '<li><a href="/destinations/where-to-stay-in-buon-ma-thuot/">Where to Stay in Buon Ma Thuot</a>: Downtown business hotels, Ako Dhong village stilt stays, and Lak Lake lodges.</li>',
                '<li><a href="/destinations/where-to-stay-in-buon-ma-thuot/">Where to Stay in Buon Ma Thuot</a>: Downtown business hotels, Ako Dhong village stilt stays, and Lak Lake lodges.</li>\n        <li><a href="/destinations/where-to-stay-in-pleiku/">Where to Stay in Pleiku</a>: Central Gia Lai business hotels, crater lake retreats, and volcanic hill homestays.</li>',
                ["where-to-stay-in-pleiku"]
            )
        ],

        # --- 10: where-to-stay-in-buon-ma-thuot -> where-to-stay-in-pleiku ---
        "where-to-stay-in-buon-ma-thuot": [
            (
                '<li><a href="/itineraries/central-highlands-vietnam-itinerary/">Central Highlands Vietnam Itinerary</a>: 5 to 7-day circuit from Da Lat to Buon Ma Thuot.</li>',
                '<li><a href="/itineraries/central-highlands-vietnam-itinerary/">Central Highlands Vietnam Itinerary</a>: 5 to 7-day circuit from Da Lat to Buon Ma Thuot.</li>\n        <li><a href="/destinations/where-to-stay-in-pleiku/">Where to Stay in Pleiku</a>: Volcanic lake eco-lodges and highland coffee hub hotels.</li>',
                ["where-to-stay-in-pleiku"]
            )
        ],

        # --- 11: da-lat-coffee-farms-guide -> where-to-stay-in-pleiku ---
        "da-lat-coffee-farms-guide": [
            (
                '<li><a href="/plan/da-lat-to-nha-trang-transport/">Da Lat to Nha Trang Transport</a>: Ready for the beach? Here is how to get from the mountains to the coast.</li>',
                '<li><a href="/plan/da-lat-to-nha-trang-transport/">Da Lat to Nha Trang Transport</a>: Ready for the beach? Here is how to get from the mountains to the coast.</li>\n    <li><a href="/destinations/where-to-stay-in-pleiku/">Where to Stay in Pleiku</a>: Highland Robusta coffee estates, volcanic lakes, and city hotels in Gia Lai.</li>',
                ["where-to-stay-in-pleiku"]
            )
        ],

        # --- 12: best-things-to-do-in-da-lat -> where-to-stay-in-pleiku ---
        "best-things-to-do-in-da-lat": [
            (
                '<li><a href="/destinations/da-lat-coffee-farms-guide/">Da Lat Coffee Farms Guide</a></li>',
                '<li><a href="/destinations/da-lat-coffee-farms-guide/">Da Lat Coffee Farms Guide</a></li>\n        <li><a href="/destinations/where-to-stay-in-pleiku/">Where to Stay in Pleiku</a>: Gia Lai coffee hubs and volcanic crater lake eco-resorts.</li>',
                ["where-to-stay-in-pleiku"]
            )
        ],

        # --- 13: vietnam-motorbike-license-laws -> vietnam-scooter-rental-checklist ---
        "vietnam-motorbike-license-laws": [
            (
                'Prepare your itinerary with the <a href="/vietnam-first-trip-planning-checklist/">Vietnam first trip planning checklist</a>.',
                'Prepare your itinerary with the <a href="/vietnam-first-trip-planning-checklist/">Vietnam first trip planning checklist</a>, and inspect your rental bike with our <a href="/plan/vietnam-scooter-rental-checklist/">Vietnam Scooter Rental Checklist</a>.',
                ["vietnam-scooter-rental-checklist"]
            )
        ],

        # --- 14: ha-giang-safety-guide -> vietnam-scooter-rental-checklist ---
        "ha-giang-safety-guide": [
            (
                'review official driving rules in <a href="/plan/vietnam-motorbike-license-laws/">Vietnam Motorbike License Laws</a>, and verify coverage with our <a href="/plan/health-travel-insurance-vietnam/">Vietnam Health &amp; Travel Insurance Guide</a>.',
                'conduct pre-ride bike inspections using our <a href="/plan/vietnam-scooter-rental-checklist/">Vietnam Scooter Rental Checklist</a>, review official driving rules in <a href="/plan/vietnam-motorbike-license-laws/">Vietnam Motorbike License Laws</a>, and verify coverage with our <a href="/plan/health-travel-insurance-vietnam/">Vietnam Health &amp; Travel Insurance Guide</a>.',
                ["vietnam-scooter-rental-checklist"]
            )
        ],

        # --- 15: ha-giang-easy-rider-vs-self-drive -> vietnam-scooter-rental-checklist ---
        "ha-giang-easy-rider-vs-self-drive": [
            (
                '<a href="/plan/vietnam-motorbike-license-laws/">Vietnam Motorbike License Laws</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison Guide</a>.',
                '<a href="/plan/vietnam-motorbike-license-laws/">Vietnam Motorbike License Laws</a>, our mechanical inspection guide on <a href="/plan/vietnam-scooter-rental-checklist/">Vietnam Scooter Rental Checklist</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison Guide</a>.',
                ["vietnam-scooter-rental-checklist"]
            )
        ],

        # --- 16: transport-within-vietnam -> vietnam-scooter-rental-checklist ---
        "transport-within-vietnam": [
            (
                'understand two-wheeled regulations via <a href="/plan/vietnam-motorbike-license-laws/">Vietnam Motorbike License Laws</a>, and consult <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>',
                'understand two-wheeled regulations via <a href="/plan/vietnam-motorbike-license-laws/">Vietnam Motorbike License Laws</a>, test your rental bike using our <a href="/plan/vietnam-scooter-rental-checklist/">Vietnam Scooter Rental Checklist</a>, and consult <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>',
                ["vietnam-scooter-rental-checklist"]
            )
        ],

        # --- 17: mu-cang-chai-travel-guide -> hanoi-to-mu-cang-chai-transport, where-to-stay-in-mu-cang-chai ---
        "mu-cang-chai-travel-guide": [
            (
                'Continue your northern travel research with our <a href="/compare/vietnam-rice-terraces-guide/">Vietnam Rice Terraces Comparison</a>',
                'Continue your northern travel research with our transit guide on <a href="/plan/hanoi-to-mu-cang-chai-transport/">Hanoi to Mu Cang Chai Transport</a>, our village lodging guide on <a href="/destinations/where-to-stay-in-mu-cang-chai/">Where to Stay in Mu Cang Chai</a>, <a href="/compare/vietnam-rice-terraces-guide/">Vietnam Rice Terraces Comparison</a>',
                ["hanoi-to-mu-cang-chai-transport", "where-to-stay-in-mu-cang-chai"]
            )
        ],

        # --- 18: northwest-vietnam-itinerary -> hanoi-to-mu-cang-chai-transport, where-to-stay-in-mu-cang-chai ---
        "northwest-vietnam-itinerary": [
            (
                '<li><a href="/destinations/where-to-stay-in-mai-chau/">Where to Stay in Mai Chau</a>: White Thai stilt village homestays and valley boutique eco-lodges.</li>',
                '<li><a href="/destinations/where-to-stay-in-mai-chau/">Where to Stay in Mai Chau</a>: White Thai stilt village homestays and valley boutique eco-lodges.</li>\n        <li><a href="/plan/hanoi-to-mu-cang-chai-transport/">Hanoi to Mu Cang Chai Transport</a>: Shared limousine vans and sleeper buses via Khau Pha Pass.</li>\n        <li><a href="/destinations/where-to-stay-in-mu-cang-chai/">Where to Stay in Mu Cang Chai</a>: Hillside stilt homestays, town hotels, and Tu Le hot spring resorts.</li>',
                ["hanoi-to-mu-cang-chai-transport", "where-to-stay-in-mu-cang-chai"]
            )
        ],

        # --- 19: hanoi-travel-guide -> hanoi-to-mu-cang-chai-transport ---
        "hanoi-travel-guide": [
            (
                'via <a href="/plan/hanoi-to-sapa-train-vs-sleeper-bus/">Hanoi to Sapa Train vs Sleeper Bus</a>.',
                'via <a href="/plan/hanoi-to-sapa-train-vs-sleeper-bus/">Hanoi to Sapa Train vs Sleeper Bus</a>, or terraced harvest trips via <a href="/plan/hanoi-to-mu-cang-chai-transport/">Hanoi to Mu Cang Chai Transport</a>.',
                ["hanoi-to-mu-cang-chai-transport"]
            )
        ],

        # --- 20: sapa-travel-guide -> hanoi-to-mu-cang-chai-transport ---
        "sapa-travel-guide": [
            (
                '<li><span class="vg-related-route-step">08</span><a href="/destinations/best-things-to-do-in-sapa/">Best Things to Do in Sapa</a><span class="vg-related-route-note">Fansipan summit cable car, Muong Hoa valley treks, and waterfalls.</span></li>',
                '<li><span class="vg-related-route-step">08</span><a href="/destinations/best-things-to-do-in-sapa/">Best Things to Do in Sapa</a><span class="vg-related-route-note">Fansipan summit cable car, Muong Hoa valley treks, and waterfalls.</span></li>\n<li><span class="vg-related-route-step">09</span><a href="/plan/hanoi-to-mu-cang-chai-transport/">Hanoi to Mu Cang Chai Transport</a><span class="vg-related-route-note">Direct limousine vans and sleeper coaches across Khau Pha Pass.</span></li>',
                ["hanoi-to-mu-cang-chai-transport"]
            )
        ],

        # --- 21: where-to-stay-in-sapa -> where-to-stay-in-mu-cang-chai ---
        "where-to-stay-in-sapa": [
            (
                '<a href="/plan/where-to-stay-in-vietnam-base-decisions/">Vietnam Where to Stay Strategy</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison</a>.',
                '<a href="/plan/where-to-stay-in-vietnam-base-decisions/">Vietnam Where to Stay Strategy</a>, our terrace lodging guide on <a href="/destinations/where-to-stay-in-mu-cang-chai/">Where to Stay in Mu Cang Chai</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison</a>.',
                ["where-to-stay-in-mu-cang-chai"]
            )
        ],

        # --- 22: sapa-vs-ha-giang -> where-to-stay-in-mu-cang-chai ---
        "sapa-vs-ha-giang": [
            (
                '<li><span class="vg-related-route-step">07</span><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a><span class="vg-related-route-note">Compare easier karst countryside with a harder mountain commitment.</span></li>',
                '<li><span class="vg-related-route-step">07</span><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a><span class="vg-related-route-note">Compare easier karst countryside with a harder mountain commitment.</span></li>\n<li><span class="vg-related-route-step">08</span><a href="/destinations/where-to-stay-in-mu-cang-chai/">Where to Stay in Mu Cang Chai</a><span class="vg-related-route-note">Hillside stilt houses, rice terrace homestays, and hot spring resorts.</span></li>',
                ["where-to-stay-in-mu-cang-chai"]
            )
        ],

        # --- 23: da-nang-travel-guide -> da-nang-to-phong-nha-transport ---
        "da-nang-travel-guide": [
            (
                '<a href="/plan/da-nang-to-quy-nhon-transport/">Da Nang to Quy Nhon Transport</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>.',
                '<a href="/plan/da-nang-to-quy-nhon-transport/">Da Nang to Quy Nhon Transport</a>, northbound rail routes via <a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>.',
                ["da-nang-to-phong-nha-transport"]
            )
        ],

        # --- 24: where-to-stay-in-da-nang -> da-nang-to-phong-nha-transport ---
        "where-to-stay-in-da-nang": [
            (
                '<li><a href="/plan/da-nang-airport-to-hoi-an/">Da Nang Airport to Hoi An</a> &mdash; private transfers, shuttle buses, and taxi price benchmarks.</li>',
                '<li><a href="/plan/da-nang-airport-to-hoi-an/">Da Nang Airport to Hoi An</a> &mdash; private transfers, shuttle buses, and taxi price benchmarks.</li>\n<li><a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport</a> &mdash; Reunification Express train berths, sleeper buses, and DMZ transfers.</li>',
                ["da-nang-to-phong-nha-transport"]
            )
        ],

        # --- 25: central-vietnam-itinerary -> where-to-stay-in-dong-hoi, da-nang-to-phong-nha-transport ---
        "central-vietnam-itinerary": [
            (
                '<li><a href="/plan/da-nang-to-quy-nhon-transport/">Da Nang to Quy Nhon Transport</a>: Coastal rail routes connecting Da Nang with Binh Dinh.</li>',
                '<li><a href="/plan/da-nang-to-quy-nhon-transport/">Da Nang to Quy Nhon Transport</a>: Coastal rail routes connecting Da Nang with Binh Dinh.</li>\n        <li><a href="/destinations/where-to-stay-in-dong-hoi/">Where to Stay in Dong Hoi</a>: Nhat Le beach resorts and city bases near the railway station.</li>\n        <li><a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport</a>: Reunification Express train and sleeper bus routes northward to the caves.</li>',
                ["where-to-stay-in-dong-hoi", "da-nang-to-phong-nha-transport"]
            )
        ],

        # --- 26: vietnam-train-travel -> da-nang-to-phong-nha-transport ---
        "vietnam-train-travel": [
            (
                '<li><a href="/plan/vietnam-plug-adapter-electricity-guide/">Vietnam Plug Adapter Guide: Sleeper Berth Sockets &amp; Charging Tech</a></li>',
                '<li><a href="/plan/vietnam-plug-adapter-electricity-guide/">Vietnam Plug Adapter Guide: Sleeper Berth Sockets &amp; Charging Tech</a></li>\n<li><a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport: Central Coast Rail &amp; Cave Transfers</a></li>',
                ["da-nang-to-phong-nha-transport"]
            )
        ]
    }

    inlinks_count = {
        "where-to-stay-in-dong-hoi": 0,
        "da-lat-to-mui-ne-transport": 0,
        "where-to-stay-in-pleiku": 0,
        "vietnam-scooter-rental-checklist": 0,
        "hanoi-to-mu-cang-chai-transport": 0,
        "where-to-stay-in-mu-cang-chai": 0,
        "da-nang-to-phong-nha-transport": 0
    }

    modified_targets = {}

    print("=== VALIDATING STAGE 77 MESH REPLACEMENTS ===")
    for slug, rules in replacements.items():
        if slug not in targets:
            print(f"ERROR: Slug {slug} not in targets data!")
            sys.exit(1)
        
        content = targets[slug]['content']
        post_id = targets[slug]['id']

        for old_s, new_s, linked_pillars in rules:
            cnt = content.count(old_s)
            if cnt != 1:
                print(f"ERROR in {slug}: Target string match count is {cnt} (must be exactly 1)!")
                print("Target snippet:", repr(old_s[:80]))
                sys.exit(1)
            
            content = content.replace(old_s, new_s)
            for p in linked_pillars:
                inlinks_count[p] += 1
            print(f"  ✓ {slug}: Matched & replaced successfully -> Links to {linked_pillars}")

        modified_targets[slug] = {
            'id': post_id,
            'title': targets[slug]['title'],
            'content': content
        }

    print("\n=== STAGE 77 INBOUND LINK AUDIT ===")
    all_ok = True
    total_inlinks = 0
    for p, count in inlinks_count.items():
        print(f"  {p}: {count} inlinks")
        total_inlinks += count
        if count < 4:
            print(f"  FAILED: {p} has fewer than 4 inlinks!")
            all_ok = False

    print(f"\nTotal Inbound Links Generated: {total_inlinks}")
    if not all_ok:
        sys.exit(1)

    out_ops_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage77_mesh_ops.json')
    with open(out_ops_file, 'w', encoding='utf-8') as f:
        json.dump(modified_targets, f, ensure_ascii=False, indent=2)

    print(f"\nSUCCESS: All replacements validated. Exported {len(modified_targets)} updated posts to {out_ops_file}")

if __name__ == '__main__':
    main()

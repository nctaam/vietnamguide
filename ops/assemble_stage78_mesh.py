# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 78: Assemble and Strictly Validate the Internal Link Mesh
"""

import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    targets_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage78_mesh_targets.json')
    targets = json.load(open(targets_path, encoding='utf-8'))

    replacements = {
        # --- 1: central-highlands-vietnam-itinerary -> where-to-stay-in-kon-tum, buon-ma-thuot-to-pleiku-transport ---
        "central-highlands-vietnam-itinerary": [
            (
                '<li><a href="/destinations/where-to-stay-in-pleiku/">Where to Stay in Pleiku</a>: Central Gia Lai business hotels, crater lake retreats, and volcanic hill homestays.</li>',
                '<li><a href="/destinations/where-to-stay-in-pleiku/">Where to Stay in Pleiku</a>: Central Gia Lai business hotels, crater lake retreats, and volcanic hill homestays.</li>\n        <li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum</a>: Riverside boutique hotels, wooden church views, and Bahnar stilt homestays.</li>\n        <li><a href="/plan/buon-ma-thuot-to-pleiku-transport/">Buon Ma Thuot to Pleiku Transport</a>: Highway 14 limousine vans, local buses, and coffee corridor routes.</li>',
                ["where-to-stay-in-kon-tum", "buon-ma-thuot-to-pleiku-transport"]
            )
        ],

        # --- 2: where-to-stay-in-buon-ma-thuot -> where-to-stay-in-kon-tum, buon-ma-thuot-to-pleiku-transport ---
        "where-to-stay-in-buon-ma-thuot": [
            (
                '<li><a href="/destinations/where-to-stay-in-pleiku/">Where to Stay in Pleiku</a>: Volcanic lake eco-lodges and highland coffee hub hotels.</li>',
                '<li><a href="/destinations/where-to-stay-in-pleiku/">Where to Stay in Pleiku</a>: Volcanic lake eco-lodges and highland coffee hub hotels.</li>\n        <li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum</a>: Dak Bla riverfront hotels and ethnic village homestays.</li>\n        <li><a href="/plan/buon-ma-thuot-to-pleiku-transport/">Buon Ma Thuot to Pleiku Transport</a>: Shared limousine vans vs local buses on Highway 14.</li>',
                ["where-to-stay-in-kon-tum", "buon-ma-thuot-to-pleiku-transport"]
            )
        ],

        # --- 3: where-to-stay-in-pleiku -> buon-ma-thuot-to-pleiku-transport ---
        "where-to-stay-in-pleiku": [
            (
                '<li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum</a>: Riverside homestays, wooden church views, and Rong communal houses.</li>',
                '<li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum</a>: Riverside homestays, wooden church views, and Rong communal houses.</li>\n        <li><a href="/plan/buon-ma-thuot-to-pleiku-transport/">Buon Ma Thuot to Pleiku Transport</a>: Shared limousine vans and express buses along Highway 14.</li>',
                ["buon-ma-thuot-to-pleiku-transport"]
            )
        ],

        # --- 4: da-lat-travel-guide -> where-to-stay-in-kon-tum ---
        "da-lat-travel-guide": [
            (
                '<li><a href="/destinations/where-to-stay-in-buon-ma-thuot/">Where to Stay in Buon Ma Thuot: City Hotels &amp; Eco-Stays</a></li>',
                '<li><a href="/destinations/where-to-stay-in-buon-ma-thuot/">Where to Stay in Buon Ma Thuot: City Hotels &amp; Eco-Stays</a></li>\n<li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum: Dak Bla River Stays &amp; Stilt Homestays</a></li>',
                ["where-to-stay-in-kon-tum"]
            )
        ],

        # --- 5: southern-vietnam-itinerary -> where-to-stay-in-chau-doc, vietnam-to-cambodia-boat-guide ---
        "southern-vietnam-itinerary": [
            (
                '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf hotels vs peaceful riverside lodges.</li>',
                '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf hotels vs peaceful riverside lodges.</li>\n        <li><a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a>: Sam Mountain sunset resorts vs Hau River hotels.</li>\n        <li><a href="/plan/vietnam-to-cambodia-boat-guide/">Vietnam to Cambodia Boat Guide</a>: Fast Mekong passenger ferries from Chau Doc to Phnom Penh.</li>',
                ["where-to-stay-in-chau-doc", "vietnam-to-cambodia-boat-guide"]
            )
        ],

        # --- 6: where-to-stay-in-can-tho -> where-to-stay-in-chau-doc ---
        "where-to-stay-in-can-tho": [
            (
                '<li><a href="/destinations/mekong-delta-travel-guide/">Mekong Delta Travel Guide</a>: Complete regional overview of provinces and seasons.</li>',
                '<li><a href="/destinations/mekong-delta-travel-guide/">Mekong Delta Travel Guide</a>: Complete regional overview of provinces and seasons.</li>\n        <li><a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a>: Hau River retreats, Sam Mountain lodges, and border transit hotels.</li>',
                ["where-to-stay-in-chau-doc"]
            )
        ],

        # --- 7: ho-chi-minh-city-to-can-tho-transport -> where-to-stay-in-chau-doc ---
        "ho-chi-minh-city-to-can-tho-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf vs riverside eco-resorts.</li>',
                '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf vs riverside eco-resorts.</li>\n        <li><a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a>: Deep delta riverfront boutique hotels and border stays.</li>',
                ["where-to-stay-in-chau-doc"]
            )
        ],

        # --- 8: mekong-delta-travel-guide -> where-to-stay-in-chau-doc ---
        "mekong-delta-travel-guide": [
            (
                '<li>Use <a href="https://vietnam.travel/places-to-go/southern-vietnam/can-tho" target="_blank" rel="noopener">Vietnam.travel Can Tho</a>, <a href="https://vietnam.travel/places-to-go/southern-vietnam/chau-doc" target="_blank" rel="noopener">Vietnam.travel Chau Doc</a>, and <a href="https://tourismcantho.vn" target="_blank" rel="noopener">Can Tho Tourism</a> for official destination context.</li>',
                '<li>Use <a href="https://vietnam.travel/places-to-go/southern-vietnam/can-tho" target="_blank" rel="noopener">Vietnam.travel Can Tho</a>, <a href="https://vietnam.travel/places-to-go/southern-vietnam/chau-doc" target="_blank" rel="noopener">Vietnam.travel Chau Doc</a>, and <a href="https://tourismcantho.vn" target="_blank" rel="noopener">Can Tho Tourism</a> for official destination context.</li>\n<li>Review our dedicated accommodation advice in <a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a> when planning an overnight river stay near Sam Mountain or the Cambodian border.</li>',
                ["where-to-stay-in-chau-doc"]
            )
        ],

        # --- 9: mekong-delta-floating-markets-guide -> vietnam-to-cambodia-boat-guide ---
        "mekong-delta-floating-markets-guide": [
            (
                '<li><a href="/compare/mekong-delta-overnight-vs-day-trip/">Mekong Delta: Overnight vs Day Trip</a></li>',
                '<li><a href="/compare/mekong-delta-overnight-vs-day-trip/">Mekong Delta: Overnight vs Day Trip</a></li>\n        <li><a href="/plan/vietnam-to-cambodia-boat-guide/">Vietnam to Cambodia Boat Guide</a>: Connecting the Mekong Delta with Phnom Penh by express river ferry.</li>',
                ["vietnam-to-cambodia-boat-guide"]
            )
        ],

        # --- 10: vietnam-to-cambodia-border-crossings -> vietnam-to-cambodia-boat-guide, where-to-stay-in-chau-doc ---
        "vietnam-to-cambodia-border-crossings": [
            (
                '<li><a href="/plan/vietnam-evisa/">Vietnam E-Visa Guide: Official Application &amp; Entry Gates</a></li>',
                '<li><a href="/plan/vietnam-to-cambodia-boat-guide/">Vietnam to Cambodia Boat Guide: Fast Mekong Ferry Logistics</a></li>\n<li><a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc: Riverfront Stays &amp; Border Staging</a></li>\n<li><a href="/plan/vietnam-evisa/">Vietnam E-Visa Guide: Official Application &amp; Entry Gates</a></li>',
                ["vietnam-to-cambodia-boat-guide", "where-to-stay-in-chau-doc"]
            )
        ],

        # --- 11: transport-within-vietnam -> buon-ma-thuot-to-pleiku-transport, vietnam-to-cambodia-boat-guide, hanoi-to-dien-bien-phu-transport ---
        "transport-within-vietnam": [
            (
                'test your rental bike using our <a href="/plan/vietnam-scooter-rental-checklist/">Vietnam Scooter Rental Checklist</a>, and consult <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying for high-friction transfers.',
                'test your rental bike using our <a href="/plan/vietnam-scooter-rental-checklist/">Vietnam Scooter Rental Checklist</a>, check regional highland routes in <a href="/plan/buon-ma-thuot-to-pleiku-transport/">Buon Ma Thuot to Pleiku Transport</a>, review river cross-border connections in <a href="/plan/vietnam-to-cambodia-boat-guide/">Vietnam to Cambodia Boat Guide</a>, compare mountain flight options in <a href="/plan/hanoi-to-dien-bien-phu-transport/">Hanoi to Dien Bien Phu Transport</a>, and consult <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying for high-friction transfers.',
                ["buon-ma-thuot-to-pleiku-transport", "vietnam-to-cambodia-boat-guide", "hanoi-to-dien-bien-phu-transport"]
            )
        ],

        # --- 12: vietnam-train-travel -> vietnam-night-train-safety-tips ---
        "vietnam-train-travel": [
            (
                '<li><a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport: Central Coast Rail &amp; Cave Transfers</a></li>',
                '<li><a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport: Central Coast Rail &amp; Cave Transfers</a></li>\n<li><a href="/plan/vietnam-night-train-safety-tips/">Vietnam Night Train Safety Tips: Sleeper Berths, Door Locks &amp; Luggage Security</a></li>',
                ["vietnam-night-train-safety-tips"]
            )
        ],

        # --- 13: da-nang-to-phong-nha-transport -> vietnam-night-train-safety-tips ---
        "da-nang-to-phong-nha-transport": [
            (
                '<li><a href="/destinations/phong-nha-travel-guide/">Phong Nha Travel Guide</a>: Cave exploration permits, Son Doong tours, and boat tickets.</li>',
                '<li><a href="/destinations/phong-nha-travel-guide/">Phong Nha Travel Guide</a>: Cave exploration permits, Son Doong tours, and boat tickets.</li>\n        <li><a href="/plan/vietnam-night-train-safety-tips/">Vietnam Night Train Safety Tips</a>: Securing sleeper berths, luggage safety, and cabin etiquette.</li>',
                ["vietnam-night-train-safety-tips"]
            )
        ],

        # --- 14: da-nang-to-quy-nhon-transport -> vietnam-night-train-safety-tips ---
        "da-nang-to-quy-nhon-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-da-nang/">Where to Stay in Da Nang</a>: My Khe beachfront resorts vs Han River city hotels.</li>',
                '<li><a href="/destinations/where-to-stay-in-da-nang/">Where to Stay in Da Nang</a>: My Khe beachfront resorts vs Han River city hotels.</li>\n        <li><a href="/plan/vietnam-night-train-safety-tips/">Vietnam Night Train Safety Tips</a>: Door locks, lower bunk luggage storage, and overnight security.</li>',
                ["vietnam-night-train-safety-tips"]
            )
        ],

        # --- 15: vietnam-plug-adapter-electricity-guide -> vietnam-night-train-safety-tips ---
        "vietnam-plug-adapter-electricity-guide": [
            (
                'Ensure your digital connectivity is covered with our guide to <a href="/vietnam-sim-card-airport-vs-city/">Vietnam SIM card airport vs city</a>',
                'Prepare for long rail journeys with our <a href="/plan/vietnam-night-train-safety-tips/">Vietnam Night Train Safety Tips</a>. Ensure your digital connectivity is covered with our guide to <a href="/vietnam-sim-card-airport-vs-city/">Vietnam SIM card airport vs city</a>',
                ["vietnam-night-train-safety-tips"]
            )
        ],

        # --- 16: safety-scams-vietnam -> vietnam-night-train-safety-tips ---
        "safety-scams-vietnam": [
            (
                '<li>Check <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport within Vietnam</a> and the <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> guide before road, ferry, train, or airport-transfer decisions.</li>',
                '<li>Check <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport within Vietnam</a> and the <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> guide before road, ferry, train, or airport-transfer decisions.</li>\n<li>Review overnight rail security protocols in our dedicated <a href="/plan/vietnam-night-train-safety-tips/">Vietnam Night Train Safety Tips</a> before boarding sleeper carriages.</li>',
                ["vietnam-night-train-safety-tips"]
            )
        ],

        # --- 17: northwest-vietnam-itinerary -> where-to-stay-in-dien-bien-phu, hanoi-to-dien-bien-phu-transport ---
        "northwest-vietnam-itinerary": [
            (
                '<li><a href="/destinations/ninh-binh-without-rushing/">Ninh Binh Without Rushing</a>: How to explore Trang An and Tam Coc away from day-trip crowds.</li>',
                '<li><a href="/destinations/where-to-stay-in-dien-bien-phu/">Where to Stay in Dien Bien Phu</a>: Valley hotels, French battle site guesthouses, and Muong Thanh eco-resorts.</li>\n        <li><a href="/plan/hanoi-to-dien-bien-phu-transport/">Hanoi to Dien Bien Phu Transport</a>: Direct Noi Bai flights vs overnight sleeper buses via Pha Din Pass.</li>\n        <li><a href="/destinations/ninh-binh-without-rushing/">Ninh Binh Without Rushing</a>: How to explore Trang An and Tam Coc away from day-trip crowds.</li>',
                ["where-to-stay-in-dien-bien-phu", "hanoi-to-dien-bien-phu-transport"]
            )
        ],

        # --- 18: where-to-stay-in-sapa -> where-to-stay-in-dien-bien-phu ---
        "where-to-stay-in-sapa": [
            (
                'our terrace lodging guide on <a href="/destinations/where-to-stay-in-mu-cang-chai/">Where to Stay in Mu Cang Chai</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison</a>.',
                'our terrace lodging guide on <a href="/destinations/where-to-stay-in-mu-cang-chai/">Where to Stay in Mu Cang Chai</a>, our northwest valley guide on <a href="/destinations/where-to-stay-in-dien-bien-phu/">Where to Stay in Dien Bien Phu</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison</a>.',
                ["where-to-stay-in-dien-bien-phu"]
            )
        ],

        # --- 19: sapa-travel-guide -> where-to-stay-in-dien-bien-phu ---
        "sapa-travel-guide": [
            (
                '<li><span class="vg-related-route-step">09</span><a href="/plan/hanoi-to-mu-cang-chai-transport/">Hanoi to Mu Cang Chai Transport</a><span class="vg-related-route-note">Direct limousine vans and sleeper coaches across Khau Pha Pass.</span></li>',
                '<li><span class="vg-related-route-step">09</span><a href="/plan/hanoi-to-mu-cang-chai-transport/">Hanoi to Mu Cang Chai Transport</a><span class="vg-related-route-note">Direct limousine vans and sleeper coaches across Khau Pha Pass.</span></li>\n<li><span class="vg-related-route-step">10</span><a href="/destinations/where-to-stay-in-dien-bien-phu/">Where to Stay in Dien Bien Phu</a><span class="vg-related-route-note">Historic battleground valley hotels, French hill guesthouses, and eco-homestays.</span></li>',
                ["where-to-stay-in-dien-bien-phu"]
            )
        ],

        # --- 20: vietnam-scooter-rental-checklist -> where-to-stay-in-dien-bien-phu ---
        "vietnam-scooter-rental-checklist": [
            (
                '<li><a href="/plan/vietnam-travel-insurance-guide/">Vietnam Travel Insurance Guide</a>: Two-wheeler coverage clauses and medical evacuation terms.</li>',
                '<li><a href="/plan/vietnam-travel-insurance-guide/">Vietnam Travel Insurance Guide</a>: Two-wheeler coverage clauses and medical evacuation terms.</li>\n        <li><a href="/destinations/where-to-stay-in-dien-bien-phu/">Where to Stay in Dien Bien Phu</a>: Valley base hotels and mountain eco-lodges after riding Pha Din Pass.</li>',
                ["where-to-stay-in-dien-bien-phu"]
            )
        ],

        # --- 21: hanoi-travel-guide -> hanoi-to-dien-bien-phu-transport ---
        "hanoi-travel-guide": [
            (
                'or terraced harvest trips via <a href="/plan/hanoi-to-mu-cang-chai-transport/">Hanoi to Mu Cang Chai Transport</a>.',
                'or terraced harvest trips via <a href="/plan/hanoi-to-mu-cang-chai-transport/">Hanoi to Mu Cang Chai Transport</a>, or northwest battlefield routes via <a href="/plan/hanoi-to-dien-bien-phu-transport/">Hanoi to Dien Bien Phu Transport</a>.',
                ["hanoi-to-dien-bien-phu-transport"]
            )
        ],

        # --- 22: best-day-trips-from-hanoi -> hanoi-to-dien-bien-phu-transport ---
        "best-day-trips-from-hanoi": [
            (
                '<a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before placing a long day trip near a fragile transfer.',
                '<a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, <a href="/plan/hanoi-to-dien-bien-phu-transport/">Hanoi to Dien Bien Phu Transport</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before placing a long day trip near a fragile transfer.',
                ["hanoi-to-dien-bien-phu-transport"]
            )
        ],
    }

    updated_posts = []
    links_created = {
        'where-to-stay-in-kon-tum': 0,
        'buon-ma-thuot-to-pleiku-transport': 0,
        'where-to-stay-in-chau-doc': 0,
        'vietnam-to-cambodia-boat-guide': 0,
        'vietnam-night-train-safety-tips': 0,
        'where-to-stay-in-dien-bien-phu': 0,
        'hanoi-to-dien-bien-phu-transport': 0
    }

    for slug, ops in replacements.items():
        if slug not in targets:
            print(f"[ERROR] Target post not found: {slug}")
            sys.exit(1)
        
        post_data = targets[slug]
        content = post_data['content']
        
        for search_str, replace_str, targets_linked in ops:
            count = content.count(search_str)
            if count != 1:
                print(f"[ERROR] In {slug}: Expected exactly 1 match for search snippet, found {count}")
                print("Search snippet was:")
                print(repr(search_str))
                sys.exit(1)
            
            content = content.replace(search_str, replace_str)
            for t in targets_linked:
                links_created[t] += 1
                
        updated_posts.append({
            'id': post_data['id'],
            'slug': slug,
            'content': content
        })

    print("=== Replacement Verification Summary ===")
    all_ok = True
    for pillar, count in links_created.items():
        print(f"  {pillar}: {count} new inbound link operations")
        if count < 3: # With existing inlinks, total >= 4
            all_ok = False

    if not all_ok:
        print("[ERROR] Some pillars do not meet the minimum inbound links threshold!")
        sys.exit(1)

    out_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage78_mesh_ops.json')
    with open(out_path, 'w', encoding='utf-8') as f:
        json.dump(updated_posts, f, ensure_ascii=False, indent=2)

    print(f"\nSuccessfully validated and saved {len(updated_posts)} post updates to {out_path}")

if __name__ == '__main__':
    main()
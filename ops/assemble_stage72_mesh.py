# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 72: Assemble and Strictly Validate the 28 Inbound Links Mesh
"""

import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    targets_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage72_mesh_targets.json')
    targets = json.load(open(targets_path, encoding='utf-8'))

    replacements = {
        # --- 1 & 2: pu-luong-travel-guide -> where-to-stay-in-pu-luong & where-to-stay-in-mai-chau ---
        "pu-luong-travel-guide": [
            (
                '<p>For more regional planning, explore our <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/destinations/sapa-travel-guide/">Sapa Travel Guide</a>, <a href="/compare/vietnam-rice-terraces-guide/">Vietnam Rice Terraces Comparison</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam Itinerary</a>.</p>',
                '<p>For more regional planning, explore our accommodation reviews on <a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a> and <a href="/destinations/where-to-stay-in-mai-chau/">Where to Stay in Mai Chau</a>, alongside our <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/destinations/sapa-travel-guide/">Sapa Travel Guide</a>, <a href="/compare/vietnam-rice-terraces-guide/">Vietnam Rice Terraces Comparison</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam Itinerary</a>.</p>',
                ["where-to-stay-in-pu-luong", "where-to-stay-in-mai-chau"]
            )
        ],

        # --- 3 & 4: best-time-for-northern-vietnam -> where-to-stay-in-mai-chau & where-to-stay-in-pu-luong ---
        "best-time-for-northern-vietnam": [
            (
                '<a href="/destinations/ha-giang-loop-planning-guide/">Ha Giang Loop Guide</a>, and master <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>.</p>',
                '<a href="/destinations/ha-giang-loop-planning-guide/">Ha Giang Loop Guide</a>, our valley accommodation guides for <a href="/destinations/where-to-stay-in-mai-chau/">Where to Stay in Mai Chau</a> and <a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>, and master <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>.</p>',
                ["where-to-stay-in-mai-chau", "where-to-stay-in-pu-luong"]
            )
        ],

        # --- 5, 6, 7: where-to-stay-in-vietnam-base-decisions -> where-to-stay-in-mai-chau, where-to-stay-in-pu-luong, where-to-stay-in-con-dao ---
        "where-to-stay-in-vietnam-base-decisions": [
            (
                'beach chapter (such as deciding <a href="/destinations/where-to-stay-in-mui-ne/">where to stay in Mui Ne</a>), countryside night, early flight, or major transfer, it probably adds friction.',
                'beach chapter (such as deciding <a href="/destinations/where-to-stay-in-mui-ne/">where to stay in Mui Ne</a> or island seclusion in <a href="/destinations/where-to-stay-in-con-dao/">where to stay in Con Dao</a>), countryside valley retreat (such as <a href="/destinations/where-to-stay-in-mai-chau/">where to stay in Mai Chau</a> or terraced lodgings in <a href="/destinations/where-to-stay-in-pu-luong/">where to stay in Pu Luong</a>), early flight, or major transfer, it probably adds friction.',
                ["where-to-stay-in-mai-chau", "where-to-stay-in-pu-luong", "where-to-stay-in-con-dao"]
            )
        ],

        # --- 8: best-day-trips-from-hanoi -> where-to-stay-in-mai-chau ---
        "best-day-trips-from-hanoi": [
            (
                '<li>Use <a href="https://vietnamairport.vn/en/noi-bai-airport" target="_blank" rel="noopener">Noi Bai airport</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before placing a long day trip near a flight, train, cruise pickup, or travel-insurance-sensitive activity.</li>',
                '<li>Use <a href="https://vietnamairport.vn/en/noi-bai-airport" target="_blank" rel="noopener">Noi Bai airport</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, our valley overnight guide on <a href="/destinations/where-to-stay-in-mai-chau/">Where to Stay in Mai Chau</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before placing a long day trip near a flight, train, cruise pickup, or travel-insurance-sensitive activity.</li>',
                ["where-to-stay-in-mai-chau"]
            )
        ],

        # --- 9: ninh-binh-without-rushing -> where-to-stay-in-pu-luong ---
        "ninh-binh-without-rushing": [
            (
                '<p>Two nights are worth it for family pace, photography, weather flexibility, Van Long, Cuc Phuong, or a premium slow route. They are not worth it if they weaken the rest of the Vietnam itinerary.</p>',
                '<p>Two nights are worth it for family pace, photography, weather flexibility, Van Long, Cuc Phuong, connecting onward to <a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>, or a premium slow route. They are not worth it if they weaken the rest of the Vietnam itinerary.</p>',
                ["where-to-stay-in-pu-luong"]
            )
        ],

        # --- 10: con-dao-travel-guide -> where-to-stay-in-con-dao ---
        "con-dao-travel-guide": [
            (
                'then compare island and beach value in <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, and <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a>.',
                'then compare island and beach value in <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, our hotel guide on <a href="/destinations/where-to-stay-in-con-dao/">Where to Stay in Con Dao</a>, <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, and <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a>.',
                ["where-to-stay-in-con-dao"]
            )
        ],

        # --- 11: con-dao-vs-phu-quoc -> where-to-stay-in-con-dao ---
        "con-dao-vs-phu-quoc": [
            (
                '<li><a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc: Best Beaches &amp; Resort Areas</a></li>',
                '<li><a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc: Best Beaches &amp; Resort Areas</a></li>\n<li><a href="/destinations/where-to-stay-in-con-dao/">Where to Stay in Con Dao: Best Areas &amp; Resorts</a></li>',
                ["where-to-stay-in-con-dao"]
            )
        ],

        # --- 12: where-to-stay-in-phu-quoc -> where-to-stay-in-con-dao ---
        "where-to-stay-in-phu-quoc": [
            (
                '<li><a href="/compare/con-dao-vs-phu-quoc/">Con Dao vs Phu Quoc</a></li>',
                '<li><a href="/compare/con-dao-vs-phu-quoc/">Con Dao vs Phu Quoc</a></li>\n        <li><a href="/destinations/where-to-stay-in-con-dao/">Where to Stay in Con Dao</a></li>',
                ["where-to-stay-in-con-dao"]
            )
        ],

        # --- 13: vietnam-first-trip-planning-checklist -> vietnam-family-travel-guide ---
        "vietnam-first-trip-planning-checklist": [
            (
                'The map looks better than the day feels (consult our <a href="/plan/vietnam-sleeper-bus-guide/">Vietnam sleeper bus guide</a> and train guides before locking long overland legs).</p>',
                'The map looks better than the day feels (consult our <a href="/plan/vietnam-sleeper-bus-guide/">Vietnam sleeper bus guide</a>, train guides, and our dedicated <a href="/itineraries/vietnam-family-travel-guide/">Vietnam Family Travel Guide</a> before locking long overland legs).</p>',
                ["vietnam-family-travel-guide"]
            )
        ],

        # --- 14: 10-days-in-vietnam -> vietnam-family-travel-guide ---
        "10-days-in-vietnam": [
            (
                'Use the <a href="/plan/">Plan hub</a> for entry, timing, safety, and logistics checks before the route becomes final.</p>',
                'Use the <a href="/itineraries/vietnam-family-travel-guide/">Vietnam Family Travel Guide</a> for kid-friendly pacing, and the <a href="/plan/">Plan hub</a> for entry, timing, safety, and logistics checks before the route becomes final.</p>',
                ["vietnam-family-travel-guide"]
            )
        ],

        # --- 15: 14-days-in-vietnam -> vietnam-family-travel-guide ---
        "14-days-in-vietnam": [
            (
                'This is especially true for families, older travelers, honeymooners, and anyone visiting in a season where one region clearly has better conditions than the others.</p>',
                'This is especially true for families (review our <a href="/itineraries/vietnam-family-travel-guide/">Vietnam Family Travel Guide</a>), older travelers, honeymooners, and anyone visiting in a season where one region clearly has better conditions than the others.</p>',
                ["vietnam-family-travel-guide"]
            )
        ],

        # --- 16: health-travel-insurance-vietnam -> vietnam-family-travel-guide ---
        "health-travel-insurance-vietnam": [
            (
                '<a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>.</p>',
                '<a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/itineraries/vietnam-family-travel-guide/">Vietnam Family Travel Guide</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>.</p>',
                ["vietnam-family-travel-guide"]
            )
        ],

        # --- 17: da-nang-to-hue-train-vs-car -> central-vietnam-itinerary ---
        "da-nang-to-hue-train-vs-car": [
            (
                '<li><a href="/plan/vietnam-train-travel/">Vietnam Train Travel Guide</a> &mdash; ticketing rules, cabin classes, and railway safety nationwide.</li>',
                '<li><a href="/plan/vietnam-train-travel/">Vietnam Train Travel Guide</a> &mdash; ticketing rules, cabin classes, and railway safety nationwide.</li>\n<li><a href="/itineraries/central-vietnam-itinerary/">Central Vietnam Itinerary</a> &mdash; complete 5 to 7-day circuit across Hue, Hai Van Pass, Da Nang, and Hoi An.</li>',
                ["central-vietnam-itinerary"]
            )
        ],

        # --- 18: 7-days-in-vietnam -> central-vietnam-itinerary ---
        "7-days-in-vietnam": [
            (
                '<a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a> when central Vietnam should lead.</p>',
                '<a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>, and our regional <a href="/itineraries/central-vietnam-itinerary/">Central Vietnam Itinerary</a> when central Vietnam should lead.</p>',
                ["central-vietnam-itinerary"]
            )
        ],

        # --- 19 & 20: hue-imperial-city-guide -> central-vietnam-itinerary & hanoi-to-hue-transport ---
        "hue-imperial-city-guide": [
            (
                '<li><span class="vg-related-route-step">09</span><a href="/plan/hue-to-hoi-an-transport/">Hue to Hoi An Transport</a><span class="vg-related-route-note">Compare Hai Van Pass scenic train, luxury limousine bus, and private car stops.</span></li>',
                '<li><span class="vg-related-route-step">09</span><a href="/plan/hue-to-hoi-an-transport/">Hue to Hoi An Transport</a><span class="vg-related-route-note">Compare Hai Van Pass scenic train, luxury limousine bus, and private car stops.</span></li>\n<li><span class="vg-related-route-step">10</span><a href="/itineraries/central-vietnam-itinerary/">Central Vietnam Itinerary</a><span class="vg-related-route-note">5 to 7-day coastal circuit linking Hue, Da Nang, and Hoi An.</span></li>\n<li><span class="vg-related-route-step">11</span><a href="/plan/hanoi-to-hue-transport/">Hanoi to Hue Transport</a><span class="vg-related-route-note">Reunification Express overnight sleeper trains vs direct domestic flights.</span></li>',
                ["central-vietnam-itinerary", "hanoi-to-hue-transport"]
            )
        ],

        # --- 21: hoi-an-ancient-town-guide -> central-vietnam-itinerary ---
        "hoi-an-ancient-town-guide": [
            (
                '<li><span class="vg-related-route-step">08</span><a href="/plan/hue-to-hoi-an-transport/">Hue to Hoi An Transport</a><span class="vg-related-route-note">Navigate coastal transfers via Hai Van Pass, heritage train, or private car.</span></li>',
                '<li><span class="vg-related-route-step">08</span><a href="/plan/hue-to-hoi-an-transport/">Hue to Hoi An Transport</a><span class="vg-related-route-note">Navigate coastal transfers via Hai Van Pass, heritage train, or private car.</span></li>\n<li><span class="vg-related-route-step">09</span><a href="/itineraries/central-vietnam-itinerary/">Central Vietnam Itinerary</a><span class="vg-related-route-note">Plan 5 to 7 days linking Hoi An lantern alleys with Hue and Da Nang.</span></li>',
                ["central-vietnam-itinerary"]
            )
        ],

        # --- 22: vietnam-travel-cost -> ho-chi-minh-city-mekong-budget ---
        "vietnam-travel-cost": [
            (
                'use <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a> before comparing roaming, local SIMs, and pre-trip eSIMs.</p>',
                'use <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, and our regional budget guide on <a href="/costs/ho-chi-minh-city-mekong-budget/">Ho Chi Minh City and Mekong Budget</a> before comparing roaming, local SIMs, and pre-trip eSIMs.</p>',
                ["ho-chi-minh-city-mekong-budget"]
            )
        ],

        # --- 23: ho-chi-minh-city-travel-guide -> ho-chi-minh-city-mekong-budget ---
        "ho-chi-minh-city-travel-guide": [
            (
                '<a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a>, and <a href="/destinations/con-dao-travel-guide/">Con Dao Travel Guide</a> before building a south-ending route.</p>',
                '<a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a>, <a href="/costs/ho-chi-minh-city-mekong-budget/">Ho Chi Minh City and Mekong Budget</a>, and <a href="/destinations/con-dao-travel-guide/">Con Dao Travel Guide</a> before building a south-ending route.</p>',
                ["ho-chi-minh-city-mekong-budget"]
            )
        ],

        # --- 24: mekong-delta-overnight-vs-day-trip -> ho-chi-minh-city-mekong-budget ---
        "mekong-delta-overnight-vs-day-trip": [
            (
                '<li><span class="vg-related-route-step">08</span><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a><span class="vg-related-route-note">Ninh Kieu Wharf walkability and delta river lodges.</span></li>',
                '<li><span class="vg-related-route-step">08</span><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a><span class="vg-related-route-note">Ninh Kieu Wharf walkability and delta river lodges.</span></li>\n<li><span class="vg-related-route-step">09</span><a href="/costs/ho-chi-minh-city-mekong-budget/">Ho Chi Minh City and Mekong Budget</a><span class="vg-related-route-note">5-day southern spending guide: Grab rides, street eats &amp; sampan charters.</span></li>',
                ["ho-chi-minh-city-mekong-budget"]
            )
        ],

        # --- 25: ho-chi-minh-city-in-2-days -> ho-chi-minh-city-mekong-budget ---
        "ho-chi-minh-city-in-2-days": [
            (
                '<li><a href="/itineraries/hanoi-in-2-days/">Hanoi in 2 Days</a>: Compare the southern commercial hub with Vietnam\'s historic northern capital.</li>',
                '<li><a href="/itineraries/hanoi-in-2-days/">Hanoi in 2 Days</a>: Compare the southern commercial hub with Vietnam\'s historic northern capital.</li>\n        <li><a href="/costs/ho-chi-minh-city-mekong-budget/">Ho Chi Minh City and Mekong Budget</a>: 5-day spending guide for street food, rides, and delta trips.</li>',
                ["ho-chi-minh-city-mekong-budget"]
            )
        ],

        # --- 26: vietnam-train-travel -> hanoi-to-hue-transport ---
        "vietnam-train-travel": [
            (
                '<li><a href="/destinations/hue-imperial-city-guide/">Hue Imperial City Guide: Historic Capital Rail Arrivals</a></li>',
                '<li><a href="/destinations/hue-imperial-city-guide/">Hue Imperial City Guide: Historic Capital Rail Arrivals</a></li>\n<li><a href="/plan/hanoi-to-hue-transport/">Hanoi to Hue Transport: SE1/SE3 Sleeper Trains &amp; Rail Guide</a></li>',
                ["hanoi-to-hue-transport"]
            )
        ],

        # --- 27: phong-nha-to-hue-transport -> hanoi-to-hue-transport ---
        "phong-nha-to-hue-transport": [
            (
                '<li><a href="/destinations/hue-imperial-city-guide/">Hue Imperial City Guide: Citadel, Royal Tombs &amp; Tickets</a></li>',
                '<li><a href="/destinations/hue-imperial-city-guide/">Hue Imperial City Guide: Citadel, Royal Tombs &amp; Tickets</a></li>\n<li><a href="/plan/hanoi-to-hue-transport/">Hanoi to Hue Transport: Overnight Sleeper Trains vs Flights</a></li>',
                ["hanoi-to-hue-transport"]
            )
        ],

        # --- 28: hanoi-travel-guide -> hanoi-to-hue-transport ---
        "hanoi-travel-guide": [
            (
                'before choosing day trips, mountain extensions, or a flight south.</p>',
                'before choosing day trips, mountain extensions, or overland transit via <a href="/plan/hanoi-to-hue-transport/">Hanoi to Hue Transport</a>.</p>',
                ["hanoi-to-hue-transport"]
            )
        ],
    }

    inlink_counts = {
        "where-to-stay-in-mai-chau": 0,
        "where-to-stay-in-pu-luong": 0,
        "where-to-stay-in-con-dao": 0,
        "vietnam-family-travel-guide": 0,
        "central-vietnam-itinerary": 0,
        "ho-chi-minh-city-mekong-budget": 0,
        "hanoi-to-hue-transport": 0,
    }

    mesh_ops = []
    print("=== Validating Stage 72 Mesh String Replacements ===")
    
    for slug, ops in replacements.items():
        if slug not in targets or not targets[slug]:
            print(f"[ERROR] Target slug '{slug}' not found in targets!")
            sys.exit(1)
        
        content = targets[slug]['post_content']
        post_id = targets[slug]['ID']
        
        for search_str, replace_str, pillars in ops:
            if search_str not in content:
                print(f"[FAIL] Match string not found in '{slug}':")
                print("  Search:", repr(search_str))
                sys.exit(1)
            
            # Count inlinks
            for p in pillars:
                inlink_counts[p] += 1
            
            # Apply replacement to check
            new_content = content.replace(search_str, replace_str, 1)
            content = new_content
            
            print(f"  ✓ [{slug}] -> Inlinks to {pillars}")
            mesh_ops.append({
                'slug': slug,
                'id': post_id,
                'search': search_str,
                'replace': replace_str,
                'pillars': pillars
            })

    print("\n=== Inlink Distribution Summary ===")
    all_four = True
    for pillar, count in inlink_counts.items():
        print(f"  {pillar}: {count} inlinks")
        if count != 4:
            all_four = False

    if not all_four:
        print("\n[ERROR] Not all pillars have exactly 4 inlinks!")
        sys.exit(1)

    print(f"\nTotal Inlinks Verified: {sum(inlink_counts.values())} across {len(replacements)} target posts.")
    print("Saving mesh operations to ops/stage72_mesh_ops.json...")

    ops_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage72_mesh_ops.json')
    with open(ops_path, 'w', encoding='utf-8') as f:
        json.dump(mesh_ops, f, ensure_ascii=False, indent=2)
    print("SUCCESS: stage72_mesh_ops.json generated and fully verified.")

if __name__ == '__main__':
    main()

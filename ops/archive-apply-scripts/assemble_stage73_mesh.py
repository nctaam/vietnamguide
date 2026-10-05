# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 73: Assemble and Strictly Validate the 28 Inbound Links Mesh
"""

import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    targets_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage73_mesh_targets.json')
    targets = json.load(open(targets_path, encoding='utf-8'))

    replacements = {
        # --- 1, 9, 25: cao-bang-travel-guide -> where-to-stay-in-cao-bang, where-to-stay-in-ba-be, hanoi-to-cao-bang-transport ---
        "cao-bang-travel-guide": [
            (
                '<h2 class="wp-block-heading">Where to go next</h2>\n<div class="vg-related-routes vg-cao-bang-related-manual">\n<ul>\n<li><a href="/destinations/ha-giang-loop-planning-guide/">Ha Giang Loop Planning Guide: Combining the Northeast Circuit</a></li>',
                '<h2 class="wp-block-heading">Where to go next</h2>\n<div class="vg-related-routes vg-cao-bang-related-manual">\n<ul>\n<li><a href="/destinations/where-to-stay-in-cao-bang/">Where to Stay in Cao Bang: City Hotels vs Ban Gioc Eco-Lodges</a></li>\n<li><a href="/destinations/where-to-stay-in-ba-be/">Where to Stay in Ba Be: Stilt Homestays &amp; Lakeside Lodges</a></li>\n<li><a href="/plan/hanoi-to-cao-bang-transport/">Hanoi to Cao Bang Transport: Limousines &amp; Bus Schedules</a></li>\n<li><a href="/destinations/ha-giang-loop-planning-guide/">Ha Giang Loop Planning Guide: Combining the Northeast Circuit</a></li>',
                ["where-to-stay-in-cao-bang", "where-to-stay-in-ba-be", "hanoi-to-cao-bang-transport"]
            )
        ],

        # --- 2: ha-giang-loop-planning-guide -> where-to-stay-in-cao-bang ---
        "ha-giang-loop-planning-guide": [
            (
                '<li><span class="vg-related-route-step">08</span><a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a><span class="vg-related-route-note">Overnight loop staging in Dong Van, Meo Vac, and Du Gia.</span></li>',
                '<li><span class="vg-related-route-step">08</span><a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a><span class="vg-related-route-note">Overnight loop staging in Dong Van, Meo Vac, and Du Gia.</span></li>\n<li><span class="vg-related-route-step">09</span><a href="/destinations/where-to-stay-in-cao-bang/">Where to Stay in Cao Bang</a><span class="vg-related-route-note">Staging bases in Cao Bang City vs Ban Gioc waterfall eco-lodges.</span></li>',
                ["where-to-stay-in-cao-bang"]
            )
        ],

        # --- 3, 11: where-to-stay-in-vietnam-base-decisions -> where-to-stay-in-cao-bang, where-to-stay-in-ba-be ---
        "where-to-stay-in-vietnam-base-decisions": [
            (
                'countryside valley retreat (such as <a href="/destinations/where-to-stay-in-mai-chau/">where to stay in Mai Chau</a> or terraced lodgings in <a href="/destinations/where-to-stay-in-pu-luong/">where to stay in Pu Luong</a>), early flight, or major transfer, it probably adds friction.',
                'countryside valley retreat (such as <a href="/destinations/where-to-stay-in-mai-chau/">where to stay in Mai Chau</a> or terraced lodgings in <a href="/destinations/where-to-stay-in-pu-luong/">where to stay in Pu Luong</a>, or northern frontier bases in <a href="/destinations/where-to-stay-in-cao-bang/">where to stay in Cao Bang</a> and <a href="/destinations/where-to-stay-in-ba-be/">where to stay in Ba Be</a>), early flight, or major transfer, it probably adds friction.',
                ["where-to-stay-in-cao-bang", "where-to-stay-in-ba-be"]
            )
        ],

        # --- 4, 10: best-time-for-northern-vietnam -> where-to-stay-in-cao-bang, where-to-stay-in-ba-be ---
        "best-time-for-northern-vietnam": [
            (
                'our valley accommodation guides for <a href="/destinations/where-to-stay-in-mai-chau/">Where to Stay in Mai Chau</a> and <a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>, and master <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>.',
                'our accommodation guides for <a href="/destinations/where-to-stay-in-mai-chau/">Where to Stay in Mai Chau</a>, <a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>, <a href="/destinations/where-to-stay-in-cao-bang/">Where to Stay in Cao Bang</a>, and <a href="/destinations/where-to-stay-in-ba-be/">Where to Stay in Ba Be</a>, and master <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>.',
                ["where-to-stay-in-cao-bang", "where-to-stay-in-ba-be"]
            )
        ],

        # --- 5: phu-yen-travel-guide -> where-to-stay-in-phu-yen ---
        "phu-yen-travel-guide": [
            (
                '<summary><strong>Where should I stay in Phu Yen?</strong></summary>\n<p>Stay along Doc Lap Street in Tuy Hoa City for immediate beach access, local seafood dining, and evening walking promenades. For rural quiet, consider eco-lodges near Bai Xep or boutique resorts along Xuan Dai Bay.</p>',
                '<summary><strong>Where should I stay in Phu Yen?</strong></summary>\n<p>Stay along Doc Lap Street in Tuy Hoa City for immediate beach access, local seafood dining, and evening walking promenades (read our in-depth area breakdown in <a href="/destinations/where-to-stay-in-phu-yen/">Where to Stay in Phu Yen</a>). For rural quiet, consider eco-lodges near Bai Xep or boutique resorts along Xuan Dai Bay.</p>',
                ["where-to-stay-in-phu-yen"]
            )
        ],

        # --- 6: quy-nhon-to-phu-yen-coastal-drive -> where-to-stay-in-phu-yen ---
        "quy-nhon-to-phu-yen-coastal-drive": [
            (
                '<li><a href="/plan/vietnam-scooter-rental-guide/">Vietnam Scooter Rental Guide</a> &mdash; license rules, deposit practices, insurance, and road safety.</li>\n</ul>',
                '<li><a href="/plan/vietnam-scooter-rental-guide/">Vietnam Scooter Rental Guide</a> &mdash; license rules, deposit practices, insurance, and road safety.</li>\n<li><a href="/destinations/where-to-stay-in-phu-yen/">Where to Stay in Phu Yen</a> &mdash; Tuy Hoa beachfront hotels, Bai Xep coastal stays, and Xuan Dai Bay resorts.</li>\n</ul>',
                ["where-to-stay-in-phu-yen"]
            )
        ],

        # --- 7: where-to-stay-in-quy-nhon -> where-to-stay-in-phu-yen ---
        "where-to-stay-in-quy-nhon": [
            (
                '<li><a href="/plan/vietnam-train-travel/">Vietnam Train Travel</a>: How to ride the scenic coastal railway to Dieu Tri and Quy Nhon.</li>\n    </ul>',
                '<li><a href="/plan/vietnam-train-travel/">Vietnam Train Travel</a>: How to ride the scenic coastal railway to Dieu Tri and Quy Nhon.</li>\n        <li><a href="/destinations/where-to-stay-in-phu-yen/">Where to Stay in Phu Yen</a>: Tuy Hoa beachfront resorts, Bai Xep eco-lodges, and south-coast bases.</li>\n    </ul>',
                ["where-to-stay-in-phu-yen"]
            )
        ],

        # --- 8: best-beaches-in-vietnam -> where-to-stay-in-phu-yen ---
        "best-beaches-in-vietnam": [
            (
                'href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon</a>, and our base guide on <a href="/destinations/where-to-stay-in-quy-nhon/">Where to Stay in Quy Nhon</a>.</p>',
                'href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon</a>, and our base guides on <a href="/destinations/where-to-stay-in-quy-nhon/">Where to Stay in Quy Nhon</a> and <a href="/destinations/where-to-stay-in-phu-yen/">Where to Stay in Phu Yen</a>.</p>',
                ["where-to-stay-in-phu-yen"]
            )
        ],

        # --- 12: where-to-stay-in-ha-giang -> where-to-stay-in-ba-be ---
        "where-to-stay-in-ha-giang": [
            (
                '<li><a href="/plan/hanoi-to-ha-giang-transport/">Hanoi to Ha Giang Transport</a>: Cabin sleeper buses, VIP limousines, and transit schedules.</li>\n    </ul>',
                '<li><a href="/plan/hanoi-to-ha-giang-transport/">Hanoi to Ha Giang Transport</a>: Cabin sleeper buses, VIP limousines, and transit schedules.</li>\n        <li><a href="/destinations/where-to-stay-in-ba-be/">Where to Stay in Ba Be</a>: Pac Ngoi stilt homestays, Bo Lu lakefront lodges, and national park bases.</li>\n    </ul>',
                ["where-to-stay-in-ba-be"]
            )
        ],

        # --- where-to-stay-in-cao-bang -> where-to-stay-in-ba-be (bonus) ---
        "where-to-stay-in-cao-bang": [
            (
                '        <li><a href="/plan/best-time-for-northern-vietnam/">Best Time for Northern Vietnam</a>: Water levels at Ban Gioc and seasonal weather patterns.</li>\n    </ul>',
                '        <li><a href="/plan/best-time-for-northern-vietnam/">Best Time for Northern Vietnam</a>: Water levels at Ban Gioc and seasonal weather patterns.</li>\n        <li><a href="/destinations/where-to-stay-in-ba-be/">Where to Stay in Ba Be</a>: Pac Ngoi stilt homestays, Bo Lu waterfront lodges, and park bases.</li>\n    </ul>',
                ["where-to-stay-in-ba-be"]
            )
        ],

        # --- 13: pu-luong-travel-guide -> pu-luong-trekking-routes-guide ---
        "pu-luong-travel-guide": [
            (
                '<p>For more regional planning, explore our accommodation reviews on <a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a> and <a href="/destinations/where-to-stay-in-mai-chau/">Where to Stay in Mai Chau</a>, alongside our <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/destinations/sapa-travel-guide/">Sapa Travel Guide</a>, <a href="/compare/vietnam-rice-terraces-guide/">Vietnam Rice Terraces Comparison</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam Itinerary</a>.</p>',
                '<p>For more regional planning, explore our trail guide on <a href="/plan/pu-luong-trekking-routes-guide/">Pu Luong Trekking Routes</a>, accommodation reviews on <a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a> and <a href="/destinations/where-to-stay-in-mai-chau/">Where to Stay in Mai Chau</a>, alongside our <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/destinations/sapa-travel-guide/">Sapa Travel Guide</a>, <a href="/compare/vietnam-rice-terraces-guide/">Vietnam Rice Terraces Comparison</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam Itinerary</a>.</p>',
                ["pu-luong-trekking-routes-guide"]
            )
        ],

        # --- 14: where-to-stay-in-pu-luong -> pu-luong-trekking-routes-guide ---
        "where-to-stay-in-pu-luong": [
            (
                '<li><a href="/destinations/pu-luong-travel-guide/">Pu Luong Travel Guide</a>: Trekking itineraries, waterwheel trails, and harvest calendars.</li>',
                '<li><a href="/destinations/pu-luong-travel-guide/">Pu Luong Travel Guide</a>: Trekking itineraries, waterwheel trails, and harvest calendars.</li>\n        <li><a href="/plan/pu-luong-trekking-routes-guide/">Pu Luong Trekking Routes</a>: Day hikes from Ban Don to Kho Muong, Hieu waterfall trails, and guide hiring.</li>',
                ["pu-luong-trekking-routes-guide"]
            )
        ],

        # --- 15: sapa-trekking-routes-guide -> pu-luong-trekking-routes-guide ---
        "sapa-trekking-routes-guide": [
            (
                '<li><a href="/destinations/da-lat-waterfalls-guide/">Da Lat Waterfalls Guide</a> &mdash; canyoning descents, alpine coasters, and southern highland cascades.</li>\n</ul>',
                '<li><a href="/destinations/da-lat-waterfalls-guide/">Da Lat Waterfalls Guide</a> &mdash; canyoning descents, alpine coasters, and southern highland cascades.</li>\n<li><a href="/plan/pu-luong-trekking-routes-guide/">Pu Luong Trekking Routes</a> &mdash; karst valley hikes, waterwheel paths, and reserve trail logistics.</li>\n</ul>',
                ["pu-luong-trekking-routes-guide"]
            )
        ],

        # --- 16: ninh-binh-without-rushing -> pu-luong-trekking-routes-guide ---
        "ninh-binh-without-rushing": [
            (
                'connecting onward to <a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>, or a premium slow route. They are not worth it if they weaken the rest of the Vietnam itinerary.</p>',
                'connecting onward to <a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a> and hiking trails in <a href="/plan/pu-luong-trekking-routes-guide/">Pu Luong Trekking Routes</a>, or a premium slow route. They are not worth it if they weaken the rest of the Vietnam itinerary.</p>',
                ["pu-luong-trekking-routes-guide"]
            )
        ],

        # --- 17: ho-chi-minh-city-in-2-days -> southern-vietnam-itinerary ---
        "ho-chi-minh-city-in-2-days": [
            (
                '<li><a href="/costs/ho-chi-minh-city-mekong-budget/">Ho Chi Minh City and Mekong Budget</a>: 5-day spending guide for street food, rides, and delta trips.</li>\n    </ul>',
                '<li><a href="/costs/ho-chi-minh-city-mekong-budget/">Ho Chi Minh City and Mekong Budget</a>: 5-day spending guide for street food, rides, and delta trips.</li>\n        <li><a href="/itineraries/southern-vietnam-itinerary/">Southern Vietnam Itinerary</a>: Complete 5 to 7-day circuit covering Saigon, the Mekong Delta, and Phu Quoc island.</li>\n    </ul>',
                ["southern-vietnam-itinerary"]
            )
        ],

        # --- 18: ho-chi-minh-city-mekong-budget -> southern-vietnam-itinerary ---
        "ho-chi-minh-city-mekong-budget": [
            (
                '<li><a href="/destinations/mekong-delta-overnight-vs-day-trip/">Mekong Delta Overnight vs Day Trip</a>: Cost efficiency and route planning.</li>\n    </ul>',
                '<li><a href="/destinations/mekong-delta-overnight-vs-day-trip/">Mekong Delta Overnight vs Day Trip</a>: Cost efficiency and route planning.</li>\n        <li><a href="/itineraries/southern-vietnam-itinerary/">Southern Vietnam Itinerary</a>: Complete 5 to 7-day southern routing connecting Saigon, delta markets, and coastal beaches.</li>\n    </ul>',
                ["southern-vietnam-itinerary"]
            )
        ],

        # --- 19: mekong-delta-overnight-vs-day-trip -> southern-vietnam-itinerary ---
        "mekong-delta-overnight-vs-day-trip": [
            (
                '<li><span class="vg-related-route-step">09</span><a href="/costs/ho-chi-minh-city-mekong-budget/">Ho Chi Minh City and Mekong Budget</a><span class="vg-related-route-note">5-day southern spending guide: Grab rides, street eats &amp; sampan charters.</span></li>\n</ol>',
                '<li><span class="vg-related-route-step">09</span><a href="/costs/ho-chi-minh-city-mekong-budget/">Ho Chi Minh City and Mekong Budget</a><span class="vg-related-route-note">5-day southern spending guide: Grab rides, street eats &amp; sampan charters.</span></li>\n<li><span class="vg-related-route-step">10</span><a href="/itineraries/southern-vietnam-itinerary/">Southern Vietnam Itinerary</a><span class="vg-related-route-note">5 to 7-day southern routing connecting Saigon, Cai Rang, and Phu Quoc.</span></li>\n</ol>',
                ["southern-vietnam-itinerary"]
            )
        ],

        # --- 20: 10-days-in-vietnam -> southern-vietnam-itinerary ---
        "10-days-in-vietnam": [
            (
                '<a href="/itineraries/vietnam-family-travel-guide/">Vietnam Family Travel Guide</a> for kid-friendly pacing, and the <a href="/plan/">Plan hub</a> for entry, timing, safety, and logistics checks before the route becomes final.</p>',
                '<a href="/itineraries/vietnam-family-travel-guide/">Vietnam Family Travel Guide</a> for kid-friendly pacing, our regional <a href="/itineraries/southern-vietnam-itinerary/">Southern Vietnam Itinerary</a>, and the <a href="/plan/">Plan hub</a> for entry, timing, safety, and logistics checks before the route becomes final.</p>',
                ["southern-vietnam-itinerary"]
            )
        ],

        # --- 21: vietnam-food-safety-street-food-etiquette -> vietnam-vegetarian-travel-guide ---
        "vietnam-food-safety-street-food-etiquette": [
            (
                '<p>Plan one food priority per city day, then leave space. If the stall looks weak, the weather is harsh, or your body is tired, choose a simpler meal and try again later.</p>',
                '<p>Plan one food priority per city day, then leave space (plant-based travelers should consult our specialized <a href="/plan/vietnam-vegetarian-travel-guide/">Vietnam Vegetarian Travel Guide</a> for Buddhist buffets and seasoning ordering tips). If the stall looks weak, the weather is harsh, or your body is tired, choose a simpler meal and try again later.</p>',
                ["vietnam-vegetarian-travel-guide"]
            )
        ],

        # --- 22: hue-street-food-guide -> vietnam-vegetarian-travel-guide ---
        "hue-street-food-guide": [
            (
                '<li><a href="/plan/vietnam-food-safety-street-food-etiquette/">Vietnam Food Safety &amp; Street Food Etiquette</a> &mdash; ice safety, street stall selection, and digestive health tips.</li>\n</ul>',
                '<li><a href="/plan/vietnam-food-safety-street-food-etiquette/">Vietnam Food Safety &amp; Street Food Etiquette</a> &mdash; ice safety, street stall selection, and digestive health tips.</li>\n<li><a href="/plan/vietnam-vegetarian-travel-guide/">Vietnam Vegetarian Travel Guide</a> &mdash; Buddhist vegetarian restaurants, fish sauce watchouts, and ordering phrases.</li>\n</ul>',
                ["vietnam-vegetarian-travel-guide"]
            )
        ],

        # --- 23: hanoi-street-food-guide -> vietnam-vegetarian-travel-guide ---
        "hanoi-street-food-guide": [
            (
                '<div class="wp-block-details">\n<summary><strong>How do I pay at Hanoi street food stalls?</strong></summary>\n<p>Cash is king at sidewalk stalls. Carry small notes of 20,000, 50,000, and 100,000 VND; street vendors frequently cannot break 500,000 VND bills for a 40,000 VND breakfast bowl. Vietnamese bank transfer apps (VietQR) are widely accepted if you maintain a local bank account.</p>\n</div>',
                '<div class="wp-block-details">\n<summary><strong>How do I pay at Hanoi street food stalls?</strong></summary>\n<p>Cash is king at sidewalk stalls. Carry small notes of 20,000, 50,000, and 100,000 VND; street vendors frequently cannot break 500,000 VND bills for a 40,000 VND breakfast bowl. Vietnamese bank transfer apps (VietQR) are widely accepted if you maintain a local bank account.</p>\n</div>\n<div class="wp-block-details">\n<summary><strong>Is Hanoi street food suitable for vegetarians?</strong></summary>\n<p>Vegetarian travelers can easily find dedicated "quán cơm chay" (Buddhist vegetarian buffets) throughout the Old Quarter and Tay Ho; read our comprehensive <a href="/plan/vietnam-vegetarian-travel-guide/">Vietnam Vegetarian Travel Guide</a> for essential phrases and ingredients.</p>\n</div>',
                ["vietnam-vegetarian-travel-guide"]
            )
        ],

        # --- 24: saigon-street-food-guide -> vietnam-vegetarian-travel-guide ---
        "saigon-street-food-guide": [
            (
                '<li><a href="/destinations/saigon-night-markets-guide/">Saigon Night Markets Guide</a> &mdash; street food alleys, opening times, and evening food stalls.</li>\n</ul>',
                '<li><a href="/destinations/saigon-night-markets-guide/">Saigon Night Markets Guide</a> &mdash; street food alleys, opening times, and evening food stalls.</li>\n<li><a href="/plan/vietnam-vegetarian-travel-guide/">Vietnam Vegetarian Travel Guide</a> &mdash; Buddhist buffets, vegan street food alternatives, and sauce-free ordering tips.</li>\n</ul>',
                ["vietnam-vegetarian-travel-guide"]
            )
        ],

        # --- 26: hanoi-travel-guide -> hanoi-to-cao-bang-transport ---
        "hanoi-travel-guide": [
            (
                'overland transit via <a href="/plan/hanoi-to-hue-transport/">Hanoi to Hue Transport</a>.</p>',
                'overland transit via <a href="/plan/hanoi-to-hue-transport/">Hanoi to Hue Transport</a> or mountain routes via <a href="/plan/hanoi-to-cao-bang-transport/">Hanoi to Cao Bang Transport</a>.</p>',
                ["hanoi-to-cao-bang-transport"]
            )
        ],

        # --- 27: transport-within-vietnam -> hanoi-to-cao-bang-transport ---
        "transport-within-vietnam": [
            (
                '<a href="/plan/vietnam-to-cambodia-border-crossings/">Vietnam to Cambodia Border Crossings</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying for high-friction transfers.</p>',
                '<a href="/plan/vietnam-to-cambodia-border-crossings/">Vietnam to Cambodia Border Crossings</a>, our regional corridor guide on <a href="/plan/hanoi-to-cao-bang-transport/">Hanoi to Cao Bang Transport</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying for high-friction transfers.</p>',
                ["hanoi-to-cao-bang-transport"]
            )
        ],

        # --- 28: hanoi-to-ha-giang-transport -> hanoi-to-cao-bang-transport ---
        "hanoi-to-ha-giang-transport": [
            (
                '<li><span class="vg-related-route-step">08</span><a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a><span class="vg-related-route-note">Staging hostels, Dong Van old town, and village homestays.</span></li>',
                '<li><span class="vg-related-route-step">08</span><a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a><span class="vg-related-route-note">Staging hostels, Dong Van old town, and village homestays.</span></li>\n<li><span class="vg-related-route-step">09</span><a href="/plan/hanoi-to-cao-bang-transport/">Hanoi to Cao Bang Transport</a><span class="vg-related-route-note">Compare eastern mountain routes, limousine vans, and sleeper buses to Ban Gioc.</span></li>',
                ["hanoi-to-cao-bang-transport"]
            )
        ],
    }

    inlink_counts = {
        "where-to-stay-in-cao-bang": 0,
        "where-to-stay-in-phu-yen": 0,
        "where-to-stay-in-ba-be": 0,
        "pu-luong-trekking-routes-guide": 0,
        "southern-vietnam-itinerary": 0,
        "vietnam-vegetarian-travel-guide": 0,
        "hanoi-to-cao-bang-transport": 0,
    }

    mesh_ops = []
    all_valid = True

    print("=== Validating Stage 73 Mesh Replacements ===")
    for slug, ops in replacements.items():
        if slug not in targets or not targets[slug]:
            print(f"[ERROR] Target slug not found in fetched data: {slug}")
            all_valid = False
            continue

        content = targets[slug]['post_content']
        post_id = targets[slug]['ID']
        updated_content = content

        for search_str, replace_str, pillars in ops:
            count = updated_content.count(search_str)
            if count == 0:
                print(f"[FAIL] Target string NOT FOUND in {slug} (ID {post_id})")
                print("--- Searched: ---")
                print(repr(search_str[:120]))
                all_valid = False
            elif count > 1:
                print(f"[FAIL] Target string AMBIGUOUS ({count} occurrences) in {slug} (ID {post_id})")
                all_valid = False
            else:
                print(f"[PASS] Exact match in {slug} (ID {post_id}) for {pillars}")
                updated_content = updated_content.replace(search_str, replace_str, 1)
                for p in pillars:
                    inlink_counts[p] += 1

        mesh_ops.append({
            'slug': slug,
            'id': post_id,
            'original_content': content,
            'updated_content': updated_content
        })

    print("\n=== Inlink Counts by Pillar ===")
    total_inlinks = 0
    for pillar, count in inlink_counts.items():
        print(f"  {pillar}: {count} inbound links")
        total_inlinks += count
        if count < 4:
            all_valid = False

    print(f"\nTotal Inbound Links Added in Mesh: {total_inlinks}")

    if all_valid and total_inlinks >= 28:
        print("\nSUCCESS: All links are 100% valid, unambiguous, and meet exact criteria!")
        out_ops_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage73_mesh_ops.json')
        with open(out_ops_file, 'w', encoding='utf-8') as f:
            json.dump(mesh_ops, f, ensure_ascii=False, indent=2)
        print(f"Saved {len(mesh_ops)} update operations to {out_ops_file}")
    else:
        print("\nERROR: Mesh validation failed. Please inspect errors above.")
        sys.exit(1)

if __name__ == '__main__':
    main()

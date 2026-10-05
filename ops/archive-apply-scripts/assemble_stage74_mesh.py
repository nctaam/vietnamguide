# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 74: Assemble and Strictly Validate the 28 Inbound Links Mesh
"""

import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    targets_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage74_mesh_targets.json')
    targets = json.load(open(targets_path, encoding='utf-8'))

    replacements = {
        # --- 1, 17: where-to-stay-in-ha-giang -> where-to-stay-in-dong-van, where-to-stay-in-meo-vac ---
        "where-to-stay-in-ha-giang": [
            (
                '<li><a href="/destinations/where-to-stay-in-ba-be/">Where to Stay in Ba Be</a>: Pac Ngoi stilt homestays, Bo Lu lakefront lodges, and national park bases.</li>\n    </ul>',
                '<li><a href="/destinations/where-to-stay-in-ba-be/">Where to Stay in Ba Be</a>: Pac Ngoi stilt homestays, Bo Lu lakefront lodges, and national park bases.</li>\n        <li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>: Old Quarter heritage homestays, cliffside lodges, and fortress bases.</li>\n        <li><a href="/destinations/where-to-stay-in-meo-vac/">Where to Stay in Meo Vac</a>: Pa Vi Hmong cultural village clay homestays, town motels, and canyon lodges.</li>\n    </ul>',
                ["where-to-stay-in-dong-van", "where-to-stay-in-meo-vac"]
            )
        ],

        # --- 2, 18: ha-giang-loop-planning-guide -> where-to-stay-in-dong-van, where-to-stay-in-meo-vac ---
        "ha-giang-loop-planning-guide": [
            (
                '<li><span class="vg-related-route-step">09</span><a href="/destinations/where-to-stay-in-cao-bang/">Where to Stay in Cao Bang</a><span class="vg-related-route-note">Staging bases in Cao Bang City vs Ban Gioc waterfall eco-lodges.</span></li>\n</ol>',
                '<li><span class="vg-related-route-step">09</span><a href="/destinations/where-to-stay-in-cao-bang/">Where to Stay in Cao Bang</a><span class="vg-related-route-note">Staging bases in Cao Bang City vs Ban Gioc waterfall eco-lodges.</span></li>\n<li><span class="vg-related-route-step">10</span><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a><span class="vg-related-route-note">Old Quarter heritage homestays and mountain pass staging.</span></li>\n<li><span class="vg-related-route-step">11</span><a href="/destinations/where-to-stay-in-meo-vac/">Where to Stay in Meo Vac</a><span class="vg-related-route-note">Pa Vi Hmong cultural village stays and Ma Pi Leng pass access.</span></li>\n</ol>',
                ["where-to-stay-in-dong-van", "where-to-stay-in-meo-vac"]
            )
        ],

        # --- 3: ha-giang-safety-guide -> where-to-stay-in-dong-van ---
        "ha-giang-safety-guide": [
            (
                'on <a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a>, calculate realistic expenses in our <a href="/costs/ha-giang-loop-cost-budget/">Ha Giang Loop Cost &amp; Budget breakdown</a>',
                'on <a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a> and <a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>, calculate realistic expenses in our <a href="/costs/ha-giang-loop-cost-budget/">Ha Giang Loop Cost &amp; Budget breakdown</a>',
                ["where-to-stay-in-dong-van"]
            )
        ],

        # --- 4: ha-giang-loop-cost-budget -> where-to-stay-in-dong-van ---
        "ha-giang-loop-cost-budget": [
            (
                'consult our <a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a> loop guide, our <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> pillar guide',
                'consult our accommodation guides for <a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a> and <a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>, our <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> pillar guide',
                ["where-to-stay-in-dong-van"]
            )
        ],

        # --- 5: where-to-stay-in-ba-be -> hanoi-to-ba-be-transport ---
        "where-to-stay-in-ba-be": [
            (
                '<li><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>: Express limousines and regional buses across the north.</li>\n    </ul>',
                '<li><a href="/plan/hanoi-to-ba-be-transport/">Hanoi to Ba Be Lake Transport</a>: Direct homestay shuttle vans, private SUVs, and bus routes.</li>\n        <li><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>: Express limousines and regional buses across the north.</li>\n    </ul>',
                ["hanoi-to-ba-be-transport"]
            )
        ],

        # --- 6: cao-bang-travel-guide -> hanoi-to-ba-be-transport ---
        "cao-bang-travel-guide": [
            (
                '<li><a href="/destinations/where-to-stay-in-ba-be/">Where to Stay in Ba Be: Stilt Homestays &amp; Lakeside Lodges</a></li>',
                '<li><a href="/destinations/where-to-stay-in-ba-be/">Where to Stay in Ba Be: Stilt Homestays &amp; Lakeside Lodges</a></li>\n<li><a href="/plan/hanoi-to-ba-be-transport/">Hanoi to Ba Be Transport: Direct Shuttles &amp; Mountain Routes</a></li>',
                ["hanoi-to-ba-be-transport"]
            )
        ],

        # --- 7, 27: hanoi-travel-guide -> hanoi-to-ba-be-transport, hanoi-to-ninh-binh-train-vs-limousine ---
        "hanoi-travel-guide": [
            (
                'overland transit via <a href="/plan/hanoi-to-hue-transport/">Hanoi to Hue Transport</a> or mountain routes via <a href="/plan/hanoi-to-cao-bang-transport/">Hanoi to Cao Bang Transport</a>.</p>',
                'overland transit via <a href="/plan/hanoi-to-hue-transport/">Hanoi to Hue Transport</a>, mountain routes via <a href="/plan/hanoi-to-cao-bang-transport/">Hanoi to Cao Bang Transport</a> and <a href="/plan/hanoi-to-ba-be-transport/">Hanoi to Ba Be Lake Transport</a>, or day excursions via <a href="/plan/hanoi-to-ninh-binh-train-vs-limousine/">Hanoi to Ninh Binh Train vs Limousine</a>.</p>',
                ["hanoi-to-ba-be-transport", "hanoi-to-ninh-binh-train-vs-limousine"]
            )
        ],

        # --- 8, 28: transport-within-vietnam -> hanoi-to-ba-be-transport, hanoi-to-ninh-binh-train-vs-limousine ---
        "transport-within-vietnam": [
            (
                'our regional corridor guide on <a href="/plan/hanoi-to-cao-bang-transport/">Hanoi to Cao Bang Transport</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying for high-friction transfers.</p>',
                'our regional corridor guides on <a href="/plan/hanoi-to-cao-bang-transport/">Hanoi to Cao Bang Transport</a>, <a href="/plan/hanoi-to-ba-be-transport/">Hanoi to Ba Be Lake Transport</a>, and <a href="/plan/hanoi-to-ninh-binh-train-vs-limousine/">Hanoi to Ninh Binh Train vs Limousine</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying for high-friction transfers.</p>',
                ["hanoi-to-ba-be-transport", "hanoi-to-ninh-binh-train-vs-limousine"]
            )
        ],

        # --- 9: sim-esim-vietnam -> vietnam-sim-card-airport-vs-city ---
        "sim-esim-vietnam": [
            (
                '<a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before relying on phone-based pickup, maps, or rural transfer coordination.</p>',
                '<a href="/plan/vietnam-sim-card-airport-vs-city/">Vietnam SIM Card Airport vs City Guide</a> and <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before relying on phone-based pickup, maps, or rural transfer coordination.</p>',
                ["vietnam-sim-card-airport-vs-city"]
            )
        ],

        # --- 10: vietnam-first-trip-planning-checklist -> vietnam-sim-card-airport-vs-city ---
        "vietnam-first-trip-planning-checklist": [
            (
                '<strong>Connectivity</strong><p>Use eSIM if already active, or buy a SIM only if it is convenient and transparent. It is fine to wait until the hotel if your transfer is arranged.</p>',
                '<strong>Connectivity</strong><p>Use eSIM if already active, or buy a physical card using our <a href="/plan/vietnam-sim-card-airport-vs-city/">Vietnam SIM Card Airport vs City Guide</a> if convenient and transparent. It is fine to wait until the hotel if your transfer is arranged.</p>',
                ["vietnam-sim-card-airport-vs-city"]
            )
        ],

        # --- 11: saigon-airport-to-district-1 -> vietnam-sim-card-airport-vs-city ---
        "saigon-airport-to-district-1": [
            (
                'prepare your documents with the <a href="/plan/vietnam-airport-arrival-checklist/">Vietnam Airport Arrival Checklist</a>, and plan onward overland connections with our <a href="/plan/vietnam-to-cambodia-border-crossings/">Vietnam to Cambodia Border Crossings Guide</a>.</p>',
                'prepare your connectivity with our <a href="/plan/vietnam-sim-card-airport-vs-city/">Vietnam SIM Card Airport vs City Guide</a>, review the <a href="/plan/vietnam-airport-arrival-checklist/">Vietnam Airport Arrival Checklist</a>, and plan onward overland connections with our <a href="/plan/vietnam-to-cambodia-border-crossings/">Vietnam to Cambodia Border Crossings Guide</a>.</p>',
                ["vietnam-sim-card-airport-vs-city"]
            )
        ],

        # --- 12: hanoi-airport-to-old-quarter -> vietnam-sim-card-airport-vs-city ---
        "hanoi-airport-to-old-quarter": [
            (
                'Transport</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before turning the first night into an improvised transfer.</p>',
                'Transport</a>, our buying guide on <a href="/plan/vietnam-sim-card-airport-vs-city/">Vietnam SIM Card Airport vs City</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before turning the first night into an improvised transfer.</p>',
                ["vietnam-sim-card-airport-vs-city"]
            )
        ],

        # --- 13: where-to-stay-in-mai-chau -> northwest-vietnam-itinerary ---
        "where-to-stay-in-mai-chau": [
            (
                '<li><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>: Limousines and buses from Hanoi to northern valleys.</li>\n    </ul>',
                '<li><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>: Limousines and buses from Hanoi to northern valleys.</li>\n        <li><a href="/itineraries/northwest-vietnam-itinerary/">Northwest Vietnam Itinerary</a>: Complete 6 to 8-day loop connecting Mai Chau, Pu Luong, and Ninh Binh.</li>\n    </ul>',
                ["northwest-vietnam-itinerary"]
            )
        ],

        # --- 14: where-to-stay-in-pu-luong -> northwest-vietnam-itinerary ---
        "where-to-stay-in-pu-luong": [
            (
                '<li><a href="/plan/best-time-for-northern-vietnam/">Best Time for Northern Vietnam</a>: Month-by-month regional rainfall and harvest timing.</li>\n    </ul>',
                '<li><a href="/plan/best-time-for-northern-vietnam/">Best Time for Northern Vietnam</a>: Month-by-month regional rainfall and harvest timing.</li>\n        <li><a href="/itineraries/northwest-vietnam-itinerary/">Northwest Vietnam Itinerary</a>: 6 to 8-day overland circuit linking Hanoi, mountain valleys, and karst waterways.</li>\n    </ul>',
                ["northwest-vietnam-itinerary"]
            )
        ],

        # --- 15: ninh-binh-without-rushing -> northwest-vietnam-itinerary ---
        "ninh-binh-without-rushing": [
            (
                'connecting onward to <a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a> and hiking trails in <a href="/plan/pu-luong-trekking-routes-guide/">Pu Luong Trekking Routes</a>, or a premium slow route.',
                'connecting onward to <a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>, hiking in <a href="/plan/pu-luong-trekking-routes-guide/">Pu Luong Trekking Routes</a>, our overland circuit in <a href="/itineraries/northwest-vietnam-itinerary/">Northwest Vietnam Itinerary</a>, or a premium slow route.',
                ["northwest-vietnam-itinerary"]
            )
        ],

        # --- 16: 10-days-in-vietnam -> northwest-vietnam-itinerary ---
        "10-days-in-vietnam": [
            (
                'our regional <a href="/itineraries/southern-vietnam-itinerary/">Southern Vietnam Itinerary</a>, and the <a href="/plan/">Plan hub</a> for entry, timing, safety, and logistics checks before the route becomes final.</p>',
                'our regional <a href="/itineraries/southern-vietnam-itinerary/">Southern Vietnam Itinerary</a>, our mountain loop in <a href="/itineraries/northwest-vietnam-itinerary/">Northwest Vietnam Itinerary</a>, and the <a href="/plan/">Plan hub</a> for entry, timing, safety, and logistics checks before the route becomes final.</p>',
                ["northwest-vietnam-itinerary"]
            )
        ],

        # --- 19: ha-giang-easy-rider-vs-self-drive -> where-to-stay-in-meo-vac ---
        "ha-giang-easy-rider-vs-self-drive": [
            (
                'and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison Guide</a>.</p>',
                '<a href="/destinations/where-to-stay-in-meo-vac/">Where to Stay in Meo Vac</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison Guide</a>.</p>',
                ["where-to-stay-in-meo-vac"]
            )
        ],

        # --- 20: where-to-stay-in-dong-van -> where-to-stay-in-meo-vac ---
        "where-to-stay-in-dong-van": [
            (
                'Should I stay in Dong Van or Meo Vac on the loop?</summary>\n    <p>Dong Van offers more restaurants, lively cafes, and ATM services. Meo Vac provides quieter cultural homestays in Pa Vi village.',
                'Should I stay in Dong Van or Meo Vac on the loop?</summary>\n    <p>Dong Van offers more restaurants, lively cafes, and ATM services. Meo Vac provides quieter cultural homestays in Pa Vi village (read our full area review in <a href="/destinations/where-to-stay-in-meo-vac/">Where to Stay in Meo Vac</a>).',
                ["where-to-stay-in-meo-vac"]
            )
        ],

        # --- 21: vietnam-vegetarian-travel-guide -> vietnam-gluten-free-travel-guide ---
        "vietnam-vegetarian-travel-guide": [
            (
                '<li><a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>: Clinic advice, allergies, and emergency care.</li>\n    </ul>',
                '<li><a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>: Clinic advice, allergies, and emergency care.</li>\n        <li><a href="/plan/vietnam-gluten-free-travel-guide/">Gluten-Free in Vietnam</a>: Essential celiac guide, naturally gluten-free rice dishes, and restaurant cards.</li>\n    </ul>',
                ["vietnam-gluten-free-travel-guide"]
            )
        ],

        # --- 22: vietnam-food-safety-street-food-etiquette -> vietnam-gluten-free-travel-guide ---
        "vietnam-food-safety-street-food-etiquette": [
            (
                'plant-based travelers should consult our specialized <a href="/plan/vietnam-vegetarian-travel-guide/">Vietnam Vegetarian Travel Guide</a> for Buddhist buffets and seasoning ordering tips',
                'plant-based travelers should consult our <a href="/plan/vietnam-vegetarian-travel-guide/">Vietnam Vegetarian Travel Guide</a> and celiacs should review our <a href="/plan/vietnam-gluten-free-travel-guide/">Vietnam Gluten-Free Travel Guide</a>',
                ["vietnam-gluten-free-travel-guide"]
            )
        ],

        # --- 23: hanoi-street-food-guide -> vietnam-gluten-free-travel-guide ---
        "hanoi-street-food-guide": [
            (
                '<summary><strong>Is Hanoi street food suitable for vegetarians?</strong></summary>\n<p>Vegetarian travelers can easily find dedicated "quán cơm chay" (Buddhist vegetarian buffets) throughout the Old Quarter and Tay Ho; read our comprehensive <a href="/plan/vietnam-vegetarian-travel-guide/">Vietnam Vegetarian Travel Guide</a> for essential phrases and ingredients.</p>',
                '<summary><strong>Is Hanoi street food suitable for vegetarians?</strong></summary>\n<p>Vegetarian travelers can easily find dedicated "quán cơm chay" (Buddhist vegetarian buffets) throughout the Old Quarter and Tay Ho; read our comprehensive <a href="/plan/vietnam-vegetarian-travel-guide/">Vietnam Vegetarian Travel Guide</a> and <a href="/plan/vietnam-gluten-free-travel-guide/">Vietnam Gluten-Free Travel Guide</a> for essential phrases and ingredients.</p>',
                ["vietnam-gluten-free-travel-guide"]
            )
        ],

        # --- 24: saigon-street-food-guide -> vietnam-gluten-free-travel-guide ---
        "saigon-street-food-guide": [
            (
                '<li><a href="/plan/vietnam-vegetarian-travel-guide/">Vietnam Vegetarian Travel Guide</a> &mdash; Buddhist buffets, vegan street food alternatives, and sauce-free ordering tips.</li>',
                '<li><a href="/plan/vietnam-vegetarian-travel-guide/">Vietnam Vegetarian Travel Guide</a> &mdash; Buddhist buffets, vegan street food alternatives, and sauce-free ordering tips.</li>\n<li><a href="/plan/vietnam-gluten-free-travel-guide/">Vietnam Gluten-Free Travel Guide</a> &mdash; celiac dining tips, soy sauce watchouts, and restaurant explanation cards.</li>',
                ["vietnam-gluten-free-travel-guide"]
            )
        ],

        # --- 25: ninh-binh-travel-guide -> hanoi-to-ninh-binh-train-vs-limousine ---
        "ninh-binh-travel-guide": [
            (
                'and <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a> before locking hotels and transfers.</p>',
                'our transit guide on <a href="/plan/hanoi-to-ninh-binh-train-vs-limousine/">Hanoi to Ninh Binh Train vs Limousine</a>, and <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a> before locking hotels and transfers.</p>',
                ["hanoi-to-ninh-binh-train-vs-limousine"]
            )
        ],

        # --- 26: hanoi-to-ninh-binh-transport -> hanoi-to-ninh-binh-train-vs-limousine ---
        "hanoi-to-ninh-binh-transport": [
            (
                '<a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before placing the transfer near a bay cruise, airport, or southbound move.</p>',
                '<a href="/plan/hanoi-to-ninh-binh-train-vs-limousine/">Hanoi to Ninh Binh Train vs Limousine</a> and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before placing the transfer near a bay cruise, airport, or southbound move.</p>',
                ["hanoi-to-ninh-binh-train-vs-limousine"]
            )
        ],
    }

    inlink_counts = {
        "where-to-stay-in-dong-van": 0,
        "hanoi-to-ba-be-transport": 0,
        "vietnam-sim-card-airport-vs-city": 0,
        "northwest-vietnam-itinerary": 0,
        "where-to-stay-in-meo-vac": 0,
        "vietnam-gluten-free-travel-guide": 0,
        "hanoi-to-ninh-binh-train-vs-limousine": 0,
    }

    mesh_ops = []
    all_valid = True

    print("=== Validating Stage 74 Mesh Replacements ===")
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
        out_ops_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage74_mesh_ops.json')
        with open(out_ops_file, 'w', encoding='utf-8') as f:
            json.dump(mesh_ops, f, ensure_ascii=False, indent=2)
        print(f"Saved {len(mesh_ops)} update operations to {out_ops_file}")
    else:
        print("\nERROR: Mesh validation failed. Please inspect errors above.")
        sys.exit(1)

if __name__ == '__main__':
    main()

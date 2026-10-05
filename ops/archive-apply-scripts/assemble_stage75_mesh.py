# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 75: Assemble and Strictly Validate the 28 Inbound Links Mesh
"""

import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    targets_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage75_mesh_targets.json')
    targets = json.load(open(targets_path, encoding='utf-8'))

    replacements = {
        # --- 1: where-to-stay-in-phu-quoc -> where-to-stay-in-ha-tien ---
        "where-to-stay-in-phu-quoc": [
            (
                '<li><a href="/destinations/where-to-stay-in-con-dao/">Where to Stay in Con Dao</a></li>\n        <li><a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a></li>\n    </ul>',
                '<li><a href="/destinations/where-to-stay-in-con-dao/">Where to Stay in Con Dao</a></li>\n        <li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Ferry pier hotels and Mui Nai beach bases for Phu Quoc crossings.</li>\n        <li><a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a></li>\n    </ul>',
                ["where-to-stay-in-ha-tien"]
            )
        ],

        # --- 2: phu-quoc-travel-guide -> where-to-stay-in-ha-tien ---
        "phu-quoc-travel-guide": [
            (
                'Ferries can be useful from Ha Tien or Rach Gia, especially on south-only or Mekong-adjacent routes, but they add port timing',
                'Ferries can be useful from Ha Tien (staging at pier hotels in our <a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien Guide</a>) or Rach Gia, especially on south-only or Mekong-adjacent routes, but they add port timing',
                ["where-to-stay-in-ha-tien"]
            )
        ],

        # --- 3: where-to-stay-in-can-tho -> where-to-stay-in-ha-tien ---
        "where-to-stay-in-can-tho": [
            (
                '<li><a href="/compare/mekong-delta-overnight-vs-day-trip/">Mekong Delta Overnight vs Day Trip</a>: Why sleeping in Can Tho beats a rushed day tour.</li>\n    </ul>',
                '<li><a href="/compare/mekong-delta-overnight-vs-day-trip/">Mekong Delta Overnight vs Day Trip</a>: Why sleeping in Can Tho beats a rushed day tour.</li>\n        <li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Strategic speedboat pier hotels and Mui Nai beach bases.</li>\n    </ul>',
                ["where-to-stay-in-ha-tien"]
            )
        ],

        # --- 4: southern-vietnam-itinerary -> where-to-stay-in-ha-tien ---
        "southern-vietnam-itinerary": [
            (
                '<li><a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc</a>: Best beaches, boutique hideaways, and resort areas.</li>\n    </ul>',
                '<li><a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc</a>: Best beaches, boutique hideaways, and resort areas.</li>\n        <li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Ferry port hotels and quiet Gulf of Thailand stays.</li>\n    </ul>',
                ["where-to-stay-in-ha-tien"]
            )
        ],

        # --- 5: ho-chi-minh-city-travel-guide -> saigon-to-vung-tau-transport ---
        "ho-chi-minh-city-travel-guide": [
            (
                'and explore our <a href="/destinations/mekong-delta-floating-markets-guide/">floating markets guide</a>). It is strongest when the south becomes a real chapter, not when HCMC is only a final flight night.',
                'explore our <a href="/destinations/mekong-delta-floating-markets-guide/">floating markets guide</a>, or take a coastal hydrofoil ferry via our <a href="/plan/saigon-to-vung-tau-transport/">Saigon to Vung Tau Transport Guide</a>). It is strongest when the south becomes a real chapter, not when HCMC is only a final flight night.',
                ["saigon-to-vung-tau-transport"]
            )
        ],

        # --- 6: best-day-trips-from-ho-chi-minh-city -> saigon-to-vung-tau-transport ---
        "best-day-trips-from-ho-chi-minh-city": [
            (
                'or attempt a Vung Tau-style coastal move.</p>',
                'or attempt a coastal escape via our <a href="/plan/saigon-to-vung-tau-transport/">Saigon to Vung Tau Transport Guide</a>.</p>',
                ["saigon-to-vung-tau-transport"]
            )
        ],

        # --- 7: saigon-airport-to-district-1 -> saigon-to-vung-tau-transport ---
        "saigon-airport-to-district-1": [
            (
                'and plan onward overland connections with our <a href="/plan/vietnam-to-cambodia-border-crossings/">Vietnam to Cambodia Border Crossings Guide</a>.</p>',
                'plan onward overland connections with our <a href="/plan/vietnam-to-cambodia-border-crossings/">Vietnam to Cambodia Border Crossings Guide</a>, and organize coastal trips via our <a href="/plan/saigon-to-vung-tau-transport/">Saigon to Vung Tau Transport Guide</a>.</p>',
                ["saigon-to-vung-tau-transport"]
            )
        ],

        # --- 8, 20, 28: transport-within-vietnam -> saigon-to-vung-tau-transport, where-to-stay-in-buon-ma-thuot, hanoi-to-sapa-train-vs-sleeper-bus ---
        "transport-within-vietnam": [
            (
                'and <a href="/plan/hanoi-to-ninh-binh-train-vs-limousine/">Hanoi to Ninh Binh Train vs Limousine</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying for high-friction transfers.</p>',
                '<a href="/plan/hanoi-to-ninh-binh-train-vs-limousine/">Hanoi to Ninh Binh Train vs Limousine</a>, <a href="/plan/hanoi-to-sapa-train-vs-sleeper-bus/">Hanoi to Sapa Train vs Sleeper Bus</a>, and <a href="/plan/saigon-to-vung-tau-transport/">Saigon to Vung Tau Transport</a>, explore highland bases in <a href="/destinations/where-to-stay-in-buon-ma-thuot/">Where to Stay in Buon Ma Thuot</a>, and consult <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying for high-friction transfers.</p>',
                ["saigon-to-vung-tau-transport", "where-to-stay-in-buon-ma-thuot", "hanoi-to-sapa-train-vs-sleeper-bus"]
            )
        ],

        # --- 9: sim-esim-vietnam -> vietnam-travel-apps ---
        "sim-esim-vietnam": [
            (
                '<a href="/plan/vietnam-sim-card-airport-vs-city/">Vietnam SIM Card Airport vs City Guide</a> and <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before relying on phone-based pickup, maps, or rural transfer coordination.</p>',
                '<a href="/plan/vietnam-sim-card-airport-vs-city/">Vietnam SIM Card Airport vs City Guide</a>, download essential tools from our <a href="/plan/vietnam-travel-apps/">Best Travel Apps for Vietnam</a>, and review <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before relying on phone-based pickup, maps, or rural transfer coordination.</p>',
                ["vietnam-travel-apps"]
            )
        ],

        # --- 10: vietnam-first-trip-planning-checklist -> vietnam-travel-apps ---
        "vietnam-first-trip-planning-checklist": [
            (
                '<strong>Connectivity</strong><p>Use eSIM if already active, or buy a physical card using our <a href="/plan/vietnam-sim-card-airport-vs-city/">Vietnam SIM Card Airport vs City Guide</a> if convenient and transparent. It is fine to wait until the hotel if your transfer is arranged.</p>',
                '<strong>Connectivity</strong><p>Use eSIM if already active, or buy a physical card using our <a href="/plan/vietnam-sim-card-airport-vs-city/">Vietnam SIM Card Airport vs City Guide</a>. Install ride-hailing and translation tools from our <a href="/plan/vietnam-travel-apps/">Best Travel Apps for Vietnam</a> before departure.</p>',
                ["vietnam-travel-apps"]
            )
        ],

        # --- 11: safety-scams-vietnam -> vietnam-travel-apps ---
        "safety-scams-vietnam": [
            (
                'Never follow solicitors inside terminal arrivals quoting 600,000',
                'Never follow solicitors inside terminal arrivals quoting 600,000'
            )
        ],

        # --- 12: vietnam-travel-cost -> vietnam-travel-apps ---
        "vietnam-travel-cost": [
            (
                'the <a href="/plan/">Plan hub</a> for entry and logistics checks, and <a href="/compare/">Compare</a>',
                'the <a href="/plan/">Plan hub</a> for entry and logistics checks, our digital guide to the <a href="/plan/vietnam-travel-apps/">Best Travel Apps for Vietnam</a>, and <a href="/compare/">Compare</a>',
                ["vietnam-travel-apps"]
            )
        ],

        # --- 13, 18: da-lat-travel-guide -> central-highlands-vietnam-itinerary, where-to-stay-in-buon-ma-thuot ---
        "da-lat-travel-guide": [
            (
                '<li><a href="/destinations/where-to-stay-in-da-lat/">Where to Stay in Da Lat: Best Areas, French Villas &amp; Lakes</a></li>',
                '<li><a href="/destinations/where-to-stay-in-da-lat/">Where to Stay in Da Lat: Best Areas, French Villas &amp; Lakes</a></li>\n<li><a href="/itineraries/central-highlands-vietnam-itinerary/">Central Highlands Vietnam Itinerary: Da Lat to Buon Ma Thuot</a></li>\n<li><a href="/destinations/where-to-stay-in-buon-ma-thuot/">Where to Stay in Buon Ma Thuot: City Hotels &amp; Eco-Stays</a></li>',
                ["central-highlands-vietnam-itinerary", "where-to-stay-in-buon-ma-thuot"]
            )
        ],

        # --- 14: where-to-stay-in-da-lat -> central-highlands-vietnam-itinerary ---
        "where-to-stay-in-da-lat": [
            (
                '<li><a href="/plan/ho-chi-minh-city-to-da-lat-transport/">Ho Chi Minh City to Da Lat Transport</a></li>\n    </ul>',
                '<li><a href="/plan/ho-chi-minh-city-to-da-lat-transport/">Ho Chi Minh City to Da Lat Transport</a></li>\n        <li><a href="/itineraries/central-highlands-vietnam-itinerary/">Central Highlands Vietnam Itinerary</a>: 5 to 7-day highland overland route to Lak Lake and Buon Ma Thuot.</li>\n    </ul>',
                ["central-highlands-vietnam-itinerary"]
            )
        ],

        # --- 15: 10-days-in-vietnam -> central-highlands-vietnam-itinerary ---
        "10-days-in-vietnam": [
            (
                'mountain pass limousines on the <a href="/plan/da-lat-to-nha-trang-transport/">Da Lat to Nha Trang route</a>) can be better value than saving money and losing rest.</td></tr>',
                'mountain pass limousines on the <a href="/plan/da-lat-to-nha-trang-transport/">Da Lat to Nha Trang route</a> or our overland <a href="/itineraries/central-highlands-vietnam-itinerary/">Central Highlands Vietnam Itinerary</a>) can be better value than saving money and losing rest.</td></tr>',
                ["central-highlands-vietnam-itinerary"]
            )
        ],

        # --- 16: 14-days-in-vietnam -> central-highlands-vietnam-itinerary ---
        "14-days-in-vietnam": [
            (
                '<td data-label="Use the extra time for">Con Dao, Phu Quoc, Da Lat, or a slower central coast stay.</td>',
                '<td data-label="Use the extra time for">Con Dao, Phu Quoc, the <a href="/itineraries/central-highlands-vietnam-itinerary/">Central Highlands Itinerary</a> (Da Lat to Buon Ma Thuot), or a slower central coast stay.</td>',
                ["central-highlands-vietnam-itinerary"]
            )
        ],

        # --- 19: where-to-stay-in-nha-trang -> where-to-stay-in-buon-ma-thuot ---
        "where-to-stay-in-nha-trang": [
            (
                '<li><a href="/plan/da-lat-to-nha-trang-transport/">Da Lat to Nha Trang Transport</a></li>\n    </ul>',
                '<li><a href="/plan/da-lat-to-nha-trang-transport/">Da Lat to Nha Trang Transport</a></li>\n        <li><a href="/destinations/where-to-stay-in-buon-ma-thuot/">Where to Stay in Buon Ma Thuot</a>: Ede village longhouses and Lak Lake eco-resorts.</li>\n    </ul>',
                ["where-to-stay-in-buon-ma-thuot"]
            )
        ],

        # --- 21: saigon-street-food-guide -> vietnam-craft-beer-guide ---
        "saigon-street-food-guide": [
            (
                '<li><a href="/compare/cu-chi-tunnels-ben-duoc-vs-ben-dinh/">Ben Duoc vs Ben Dinh Cu Chi Tunnels</a> &mdash; choose between authentic war history and tourist-adapted crawl sectors.</li>',
                '<li><a href="/plan/vietnam-craft-beer-guide/">Vietnam Craft Beer Guide</a> &mdash; top artisanal taprooms in District 1 and Thao Dien, tropical IPAs, and pint prices.</li>\n<li><a href="/compare/cu-chi-tunnels-ben-duoc-vs-ben-dinh/">Ben Duoc vs Ben Dinh Cu Chi Tunnels</a> &mdash; choose between authentic war history and tourist-adapted crawl sectors.</li>',
                ["vietnam-craft-beer-guide"]
            )
        ],

        # --- 22: hanoi-street-food-guide -> vietnam-craft-beer-guide ---
        "hanoi-street-food-guide": [
            (
                'read our comprehensive <a href="/plan/vietnam-vegetarian-travel-guide/">Vietnam Vegetarian Travel Guide</a> and <a href="/plan/vietnam-gluten-free-travel-guide/">Vietnam Gluten-Free Travel Guide</a> for essential phrases and ingredients.</p>',
                'read our comprehensive <a href="/plan/vietnam-vegetarian-travel-guide/">Vietnam Vegetarian Travel Guide</a> and <a href="/plan/vietnam-gluten-free-travel-guide/">Vietnam Gluten-Free Travel Guide</a>, or discover local taprooms in our <a href="/plan/vietnam-craft-beer-guide/">Vietnam Craft Beer Guide</a>.</p>',
                ["vietnam-craft-beer-guide"]
            )
        ],

        # --- 23: vietnam-food-safety-street-food-etiquette -> vietnam-craft-beer-guide ---
        "vietnam-food-safety-street-food-etiquette": [
            (
                '<a href="/plan/tap-water-in-vietnam/">tap water in Vietnam</a>.</p></details>',
                '<a href="/plan/tap-water-in-vietnam/">tap water in Vietnam</a>.</p></details>\n<details><summary>Is craft beer and draft bia hoi safe to drink?</summary><p>Yes, commercially brewed lagers and independent microbreweries adhere to strict sanitation standards; consult our comprehensive <a href="/plan/vietnam-craft-beer-guide/">Vietnam Craft Beer Guide</a> for brewery locations and taprooms.</p></details>',
                ["vietnam-craft-beer-guide"]
            )
        ],

        # --- 24: where-to-stay-in-da-nang -> vietnam-craft-beer-guide ---
        "where-to-stay-in-da-nang": [
            (
                '<li><a href="/destinations/ba-na-hills-golden-bridge-guide/">Ba Na Hills Golden Bridge Guide</a> &mdash; cable car routes, crowd strategies, and high-altitude weather timing.</li>',
                '<li><a href="/plan/vietnam-craft-beer-guide/">Vietnam Craft Beer Guide</a> &mdash; Han River microbreweries, rooftop dragon fire views, and tasting flights.</li>\n<li><a href="/destinations/ba-na-hills-golden-bridge-guide/">Ba Na Hills Golden Bridge Guide</a> &mdash; cable car routes, crowd strategies, and high-altitude weather timing.</li>',
                ["vietnam-craft-beer-guide"]
            )
        ],

        # --- 25: sapa-travel-guide -> hanoi-to-sapa-train-vs-sleeper-bus ---
        "sapa-travel-guide": [
            (
                '<li><span class="vg-related-route-step">03</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Compare train, bus, van, private car, and transfer recovery.</span></li>',
                '<li><span class="vg-related-route-step">03</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a><span class="vg-related-route-note">Compare train, bus, van, private car, and transfer recovery.</span></li>\n<li><span class="vg-related-route-step">04</span><a href="/plan/hanoi-to-sapa-train-vs-sleeper-bus/">Hanoi to Sapa Train vs Sleeper Bus</a><span class="vg-related-route-note">Overnight rail berths to Lao Cai vs direct luxury cabin sleeper buses.</span></li>',
                ["hanoi-to-sapa-train-vs-sleeper-bus"]
            )
        ],

        # --- 26: where-to-stay-in-sapa -> hanoi-to-sapa-train-vs-sleeper-bus ---
        "where-to-stay-in-sapa": [
            (
                '<a href="/plan/where-to-stay-in-vietnam-base-decisions/">Vietnam Where to Stay Strategy</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison</a>.</p>',
                '<a href="/plan/hanoi-to-sapa-train-vs-sleeper-bus/">Hanoi to Sapa Train vs Sleeper Bus Guide</a>, <a href="/plan/where-to-stay-in-vietnam-base-decisions/">Vietnam Where to Stay Strategy</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison</a>.</p>',
                ["hanoi-to-sapa-train-vs-sleeper-bus"]
            )
        ],

        # --- 27: hanoi-travel-guide -> hanoi-to-sapa-train-vs-sleeper-bus ---
        "hanoi-travel-guide": [
            (
                'or day excursions via <a href="/plan/hanoi-to-ninh-binh-train-vs-limousine/">Hanoi to Ninh Binh Train vs Limousine</a>.</p>',
                'day excursions via <a href="/plan/hanoi-to-ninh-binh-train-vs-limousine/">Hanoi to Ninh Binh Train vs Limousine</a>, or overnight mountain departures via <a href="/plan/hanoi-to-sapa-train-vs-sleeper-bus/">Hanoi to Sapa Train vs Sleeper Bus</a>.</p>',
                ["hanoi-to-sapa-train-vs-sleeper-bus"]
            )
        ],
    }

    # Also handle safety-scams-vietnam exact string
    # Let's verify safety-scams-vietnam string:
    scams_content = targets["safety-scams-vietnam"]["content"]
    needle_scams = 'use official app (see our <a href="/plan/grab-in-vietnam-guide/">Grab in Vietnam guide</a>).</td></tr>'
    replace_scams = 'use official app (see our <a href="/plan/grab-in-vietnam-guide/">Grab in Vietnam guide</a> and <a href="/plan/vietnam-travel-apps/">Best Travel Apps for Vietnam</a>).</td></tr>'
    replacements["safety-scams-vietnam"] = [(needle_scams, replace_scams, ["vietnam-travel-apps"])]

    print("=" * 70)
    print("STAGE 75: ASSEMBLING & VALIDATING INBOUND LINK MESH")
    print("=" * 70)

    mesh_ops = {}
    inlink_counts = {
        "where-to-stay-in-ha-tien": 0,
        "saigon-to-vung-tau-transport": 0,
        "vietnam-travel-apps": 0,
        "central-highlands-vietnam-itinerary": 0,
        "where-to-stay-in-buon-ma-thuot": 1, # Already present in central-highlands-vietnam-itinerary
        "vietnam-craft-beer-guide": 0,
        "hanoi-to-sapa-train-vs-sleeper-bus": 0
    }

    for slug, rules in replacements.items():
        if slug not in targets:
            print(f"[ERROR] Target slug '{slug}' not found in fetched targets!")
            sys.exit(1)
        
        content = targets[slug]["content"]
        updated_content = content
        
        for needle, replacement, linked_pillars in rules:
            count = content.count(needle)
            if count == 0:
                print(f"[FAIL] Target '{slug}': Needle not found!")
                print("  Needle:", repr(needle[:80]))
                sys.exit(1)
            elif count > 1:
                print(f"[FAIL] Target '{slug}': Needle appears {count} times (ambiguous)!")
                sys.exit(1)
            
            updated_content = updated_content.replace(needle, replacement)
            for p in linked_pillars:
                inlink_counts[p] += 1
                print(f"  ✓ [{slug}] -> '{p}'")

        mesh_ops[slug] = {
            "ID": targets[slug]["ID"],
            "title": targets[slug]["title"],
            "parent": targets[slug]["parent"],
            "slug": slug,
            "new_content": updated_content
        }

    print("\n" + "=" * 70)
    print("INBOUND LINK DISTRIBUTION VERIFICATION:")
    print("=" * 70)
    for p, cnt in inlink_counts.items():
        status = "PASSED (>= 4 inlinks)" if cnt >= 4 else "FAILED (< 4 inlinks)"
        print(f"  {p}: {cnt} inbound links -> {status}")
        assert cnt >= 4, f"Pillar '{p}' has only {cnt} inlinks!"

    ops_output = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage75_mesh_ops.json')
    with open(ops_output, 'w', encoding='utf-8') as f:
        json.dump(mesh_ops, f, ensure_ascii=False, indent=2)

    print(f"\n[SUCCESS] Assembled {len(mesh_ops)} mesh update operations into {ops_output}")

if __name__ == '__main__':
    main()

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 66: Exact Mesh Assembler and Validator.
Constructs 28 surgical internal linking replacements across 27 posts,
verifying that each target exists exactly once in the source contents.
"""

import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

base_dir = os.path.dirname(os.path.abspath(__file__))
sources = json.load(open(os.path.join(base_dir, 'stage66_mesh_sources.json'), 'r', encoding='utf-8'))

OPERATIONS = [
    # =========================================================================
    # Group 1: hanoi-train-street-guide (4 links)
    # URL: /destinations/hanoi-train-street-guide/
    # =========================================================================
    {
        "post_id": sources["hanoi-travel-guide"]["ID"],
        "slug": "hanoi-travel-guide",
        "pillar": "hanoi-train-street-guide",
        "target": "and south to Hue/Da Nang (SE3 at 19:20, 680,000 VND).</li>",
        "replacement": "and south to Hue/Da Nang (SE3 at 19:20, 680,000 VND). For travelers eager to watch railcars rumble along narrow residential corridors, consult our <a href=\"/destinations/hanoi-train-street-guide/\">Hanoi Train Street guide</a> for timetables, checkpoint rules, and trackside cafes.</li>"
    },
    {
        "post_id": sources["hanoi-street-food-guide"]["ID"],
        "slug": "hanoi-street-food-guide",
        "pillar": "hanoi-train-street-guide",
        "target": "The birthplace of egg coffee, founded by former Metropole Hotel bartender Nguyen Van Giang.",
        "replacement": "The birthplace of egg coffee, founded by former Metropole Hotel bartender Nguyen Van Giang (for trackside coffee options, see our <a href=\"/destinations/hanoi-train-street-guide/\">Hanoi Train Street guide</a>)."
    },
    {
        "post_id": sources["vietnam-coffee-guide"]["ID"],
        "slug": "vietnam-coffee-guide",
        "pillar": "hanoi-train-street-guide",
        "target": "pair this guide with our <a href=\"/destinations/hanoi-street-food-guide/\">Hanoi Street Food Guide</a>,",
        "replacement": "pair this guide with our <a href=\"/destinations/hanoi-street-food-guide/\">Hanoi Street Food Guide</a>, check trackside brews in our <a href=\"/destinations/hanoi-train-street-guide/\">Hanoi Train Street Guide</a>,"
    },
    {
        "post_id": sources["vietnam-train-travel"]["ID"],
        "slug": "vietnam-train-travel",
        "pillar": "hanoi-train-street-guide",
        "target": "<li><a href=\"/destinations/da-nang-travel-guide/\">Da Nang Travel Guide: Coastal Hub &amp; Hai Van Pass Staging</a></li>",
        "replacement": "<li><a href=\"/destinations/hanoi-train-street-guide/\">Hanoi Train Street Guide: Timetable, Cafes &amp; Safety</a></li>\n<li><a href=\"/destinations/da-nang-travel-guide/\">Da Nang Travel Guide: Coastal Hub &amp; Hai Van Pass Staging</a></li>"
    },

    # =========================================================================
    # Group 2: cu-chi-tunnels-ben-duoc-vs-ben-dinh (4 links)
    # URL: /compare/cu-chi-tunnels-ben-duoc-vs-ben-dinh/
    # =========================================================================
    {
        "post_id": sources["ho-chi-minh-city-travel-guide"]["ID"],
        "slug": "ho-chi-minh-city-travel-guide",
        "pillar": "cu-chi-tunnels-ben-duoc-vs-ben-dinh",
        "target": "<a href=\"/destinations/mekong-delta-travel-guide/\">Mekong Delta Travel Guide</a>,",
        "replacement": "<a href=\"/compare/cu-chi-tunnels-ben-duoc-vs-ben-dinh/\">Ben Duoc vs Ben Dinh Cu Chi Tunnels</a> comparison, <a href=\"/destinations/mekong-delta-travel-guide/\">Mekong Delta Travel Guide</a>,"
    },
    {
        "post_id": sources["saigon-airport-to-district-1"]["ID"],
        "slug": "saigon-airport-to-district-1",
        "pillar": "cu-chi-tunnels-ben-duoc-vs-ben-dinh",
        "target": "explore local flavors in our <a href=\"/destinations/saigon-street-food-guide/\">Saigon Street Food Guide</a>,",
        "replacement": "compare day-trip war tunnels in our <a href=\"/compare/cu-chi-tunnels-ben-duoc-vs-ben-dinh/\">Ben Duoc vs Ben Dinh Cu Chi Tunnels</a> guide, explore local flavors in our <a href=\"/destinations/saigon-street-food-guide/\">Saigon Street Food Guide</a>,"
    },
    {
        "post_id": sources["saigon-street-food-guide"]["ID"],
        "slug": "saigon-street-food-guide",
        "pillar": "cu-chi-tunnels-ben-duoc-vs-ben-dinh",
        "target": "<li><a href=\"/destinations/ho-chi-minh-city-travel-guide/\">Ho Chi Minh City Travel Guide</a> &mdash; complete city itinerary",
        "replacement": "<li><a href=\"/compare/cu-chi-tunnels-ben-duoc-vs-ben-dinh/\">Ben Duoc vs Ben Dinh Cu Chi Tunnels</a> &mdash; choose between authentic war history and tourist-adapted crawl sectors.</li>\n<li><a href=\"/destinations/ho-chi-minh-city-travel-guide/\">Ho Chi Minh City Travel Guide</a> &mdash; complete city itinerary"
    },
    {
        "post_id": sources["saigon-night-markets-guide"]["ID"],
        "slug": "saigon-night-markets-guide",
        "pillar": "cu-chi-tunnels-ben-duoc-vs-ben-dinh",
        "target": "<li><a href=\"/destinations/ho-chi-minh-city-travel-guide/\">Ho Chi Minh City Travel Guide</a> &mdash; full orientation",
        "replacement": "<li><a href=\"/compare/cu-chi-tunnels-ben-duoc-vs-ben-dinh/\">Ben Duoc vs Ben Dinh Cu Chi Tunnels</a> &mdash; explore historic wartime tunnel sectors before evening night markets.</li>\n<li><a href=\"/destinations/ho-chi-minh-city-travel-guide/\">Ho Chi Minh City Travel Guide</a> &mdash; full orientation"
    },

    # =========================================================================
    # Group 3: ba-na-hills-golden-bridge-guide (4 links)
    # URL: /destinations/ba-na-hills-golden-bridge-guide/
    # =========================================================================
    {
        "post_id": sources["da-nang-travel-guide"]["ID"],
        "slug": "da-nang-travel-guide",
        "pillar": "ba-na-hills-golden-bridge-guide",
        "target": "Explore our detailed <a href=\"/destinations/da-nang-beaches-guide/\">Da Nang beaches guide</a> covering My Khe, Non Nuoc, and Son Tra Peninsula access,",
        "replacement": "Explore our detailed <a href=\"/destinations/da-nang-beaches-guide/\">Da Nang beaches guide</a>, plan mountain visits with our <a href=\"/destinations/ba-na-hills-golden-bridge-guide/\">Ba Na Hills Golden Bridge guide</a>,"
    },
    {
        "post_id": sources["where-to-stay-in-da-nang"]["ID"],
        "slug": "where-to-stay-in-da-nang",
        "pillar": "ba-na-hills-golden-bridge-guide",
        "target": "<li><a href=\"/destinations/da-nang-travel-guide/\">Da Nang Travel Guide</a> &mdash; complete city",
        "replacement": "<li><a href=\"/destinations/ba-na-hills-golden-bridge-guide/\">Ba Na Hills Golden Bridge Guide</a> &mdash; cable car routes, crowd strategies, and high-altitude weather timing.</li>\n<li><a href=\"/destinations/da-nang-travel-guide/\">Da Nang Travel Guide</a> &mdash; complete city"
    },
    {
        "post_id": sources["da-nang-airport-to-hoi-an"]["ID"],
        "slug": "da-nang-airport-to-hoi-an",
        "pillar": "ba-na-hills-golden-bridge-guide",
        "target": "compare urban beach life with our <a href=\"/compare/da-nang-vs-hoi-an/\">Da Nang vs Hoi An</a> breakdown,",
        "replacement": "plan mountain day tours with our <a href=\"/destinations/ba-na-hills-golden-bridge-guide/\">Ba Na Hills Golden Bridge Guide</a>, compare urban beach life with our <a href=\"/compare/da-nang-vs-hoi-an/\">Da Nang vs Hoi An</a> breakdown,"
    },
    {
        "post_id": sources["da-nang-street-food-guide"]["ID"],
        "slug": "da-nang-street-food-guide",
        "pillar": "ba-na-hills-golden-bridge-guide",
        "target": "explore our <a href=\"/destinations/hoi-an-street-food-guide/\">Hoi An street food guide</a> for authentic Ba Le well noodle stalls),",
        "replacement": "explore our <a href=\"/destinations/hoi-an-street-food-guide/\">Hoi An street food guide</a> for authentic Ba Le well noodle stalls; for mountain excursions see our <a href=\"/destinations/ba-na-hills-golden-bridge-guide/\">Ba Na Hills Golden Bridge Guide</a>),"
    },

    # =========================================================================
    # Group 4: vietnam-to-cambodia-border-crossings (4 links)
    # URL: /plan/vietnam-to-cambodia-border-crossings/
    # =========================================================================
    {
        "post_id": sources["vietnam-evisa"]["ID"],
        "slug": "vietnam-evisa",
        "pillar": "vietnam-to-cambodia-border-crossings",
        "target": "Verify the official entry/exit point list for your exact airport, land border, or seaport.",
        "replacement": "Verify the official entry/exit point list for your exact airport, land border, or seaport (for overland routes, consult our <a href=\"/plan/vietnam-to-cambodia-border-crossings/\">Vietnam to Cambodia border crossings guide</a>)."
    },
    {
        "post_id": sources["vietnam-sleeper-bus-guide"]["ID"],
        "slug": "vietnam-sleeper-bus-guide",
        "pillar": "vietnam-to-cambodia-border-crossings",
        "target": "<li><a href=\"/plan/transport-within-vietnam/\">Transport Within Vietnam: Trains, Buses, Flights &amp; Private Cars</a></li>",
        "replacement": "<li><a href=\"/plan/vietnam-to-cambodia-border-crossings/\">Vietnam to Cambodia Border Crossings: Bus &amp; Ferry Routes</a></li>\n<li><a href=\"/plan/transport-within-vietnam/\">Transport Within Vietnam: Trains, Buses, Flights &amp; Private Cars</a></li>"
    },
    {
        "post_id": sources["transport-within-vietnam"]["ID"],
        "slug": "transport-within-vietnam",
        "pillar": "vietnam-to-cambodia-border-crossings",
        "target": "and <a href=\"/costs/vietnam-travel-cost/\">Vietnam Travel Cost</a> before paying for high-friction transfers.",
        "replacement": "<a href=\"/plan/vietnam-to-cambodia-border-crossings/\">Vietnam to Cambodia Border Crossings</a>, and <a href=\"/costs/vietnam-travel-cost/\">Vietnam Travel Cost</a> before paying for high-friction transfers."
    },
    {
        "post_id": sources["saigon-airport-to-district-1"]["ID"],
        "slug": "saigon-airport-to-district-1",
        "pillar": "vietnam-to-cambodia-border-crossings",
        "target": "prepare your documents with the <a href=\"/plan/vietnam-airport-arrival-checklist/\">Vietnam Airport Arrival Checklist</a>.",
        "replacement": "prepare your documents with the <a href=\"/plan/vietnam-airport-arrival-checklist/\">Vietnam Airport Arrival Checklist</a>, and plan onward overland connections with our <a href=\"/plan/vietnam-to-cambodia-border-crossings/\">Vietnam to Cambodia Border Crossings Guide</a>."
    },

    # =========================================================================
    # Group 5: hanoi-to-cat-ba-island-transport (4 links)
    # URL: /plan/hanoi-to-cat-ba-island-transport/
    # =========================================================================
    {
        "post_id": sources["cat-ba-travel-guide"]["ID"],
        "slug": "cat-ba-travel-guide",
        "pillar": "hanoi-to-cat-ba-island-transport",
        "target": "<a href=\"/plan/transport-within-vietnam/\">Transport Within Vietnam</a>, and <a href=\"/costs/vietnam-travel-cost/\">Vietnam Travel Cost</a>.",
        "replacement": "<a href=\"/plan/hanoi-to-cat-ba-island-transport/\">Hanoi to Cat Ba Island Transport</a>, <a href=\"/plan/transport-within-vietnam/\">Transport Within Vietnam</a>, and <a href=\"/costs/vietnam-travel-cost/\">Vietnam Travel Cost</a>."
    },
    {
        "post_id": sources["hanoi-to-ha-long-bay-transport"]["ID"],
        "slug": "hanoi-to-ha-long-bay-transport",
        "pillar": "hanoi-to-cat-ba-island-transport",
        "target": "<li><a href=\"/compare/ha-long-bay-day-trip-vs-overnight-cruise/\">Ha Long Bay Day Trip vs Overnight Cruise",
        "replacement": "<li><a href=\"/plan/hanoi-to-cat-ba-island-transport/\">Hanoi to Cat Ba Island Transport</a> &mdash; compare tourist combo buses, speedboats, and ferry routes.</li>\n<li><a href=\"/compare/ha-long-bay-day-trip-vs-overnight-cruise/\">Ha Long Bay Day Trip vs Overnight Cruise"
    },
    {
        "post_id": sources["ha-long-bay-day-trip-vs-overnight-cruise"]["ID"],
        "slug": "ha-long-bay-day-trip-vs-overnight-cruise",
        "pillar": "hanoi-to-cat-ba-island-transport",
        "target": "<li><a href=\"/destinations/ha-long-bay-travel-guide/\">Ha Long Bay Travel Guide</a> &mdash; full regional overview, ports, and route planning.",
        "replacement": "<li><a href=\"/plan/hanoi-to-cat-ba-island-transport/\">Hanoi to Cat Ba Island Transport</a> &mdash; door-to-door transit to Cat Ba for independent Lan Ha Bay cruising.</li>\n<li><a href=\"/destinations/ha-long-bay-travel-guide/\">Ha Long Bay Travel Guide</a> &mdash; full regional overview, ports, and route planning."
    },
    {
        "post_id": sources["hanoi-airport-to-old-quarter"]["ID"],
        "slug": "hanoi-airport-to-old-quarter",
        "pillar": "hanoi-to-cat-ba-island-transport",
        "target": "and <a href=\"/costs/vietnam-travel-cost/\">Vietnam Travel Cost</a> before turning the first night into an improvised transfer.",
        "replacement": "<a href=\"/plan/hanoi-to-cat-ba-island-transport/\">Hanoi to Cat Ba Island Transport</a>, and <a href=\"/costs/vietnam-travel-cost/\">Vietnam Travel Cost</a> before turning the first night into an improvised transfer."
    },

    # =========================================================================
    # Group 6: hue-street-food-guide (4 links)
    # URL: /destinations/hue-street-food-guide/
    # =========================================================================
    {
        "post_id": sources["hue-imperial-city-guide"]["ID"],
        "slug": "hue-imperial-city-guide",
        "pillar": "hue-street-food-guide",
        "target": "<li><span class=\"vg-related-route-step\">07</span><a href=\"/itineraries/10-days-in-vietnam/\">10 Days in Vietnam</a><span class=\"vg-related-route-note\">Test whether Hue fits without thinning the route.</span></li>",
        "replacement": "<li><span class=\"vg-related-route-step\">07</span><a href=\"/itineraries/10-days-in-vietnam/\">10 Days in Vietnam</a><span class=\"vg-related-route-note\">Test whether Hue fits without thinning the route.</span></li>\n<li><span class=\"vg-related-route-step\">08</span><a href=\"/destinations/hue-street-food-guide/\">Hue Street Food Guide</a><span class=\"vg-related-route-note\">Taste authentic Bun Bo Hue, royal steamed rice cakes, and Con Hen clam rice.</span></li>"
    },
    {
        "post_id": sources["best-things-to-do-in-hue"]["ID"],
        "slug": "best-things-to-do-in-hue",
        "pillar": "hue-street-food-guide",
        "target": "riverside walk, or food block. Add a boat when weather, route, and timing support it;",
        "replacement": "riverside walk, or sampling imperial dishes with our <a href=\"/destinations/hue-street-food-guide/\">Hue Street Food Guide</a>. Add a boat when weather, route, and timing support it;"
    },
    {
        "post_id": sources["da-nang-to-hue-train-vs-car"]["ID"],
        "slug": "da-nang-to-hue-train-vs-car",
        "pillar": "hue-street-food-guide",
        "target": "<li><a href=\"/destinations/hue-imperial-city-guide/\">Hue Imperial City Guide</a> &mdash; tour the Citadel, Nguyen Dynasty",
        "replacement": "<li><a href=\"/destinations/hue-street-food-guide/\">Hue Street Food Guide</a> &mdash; 9 essential imperial dishes, bun bo stalls, and evening sweet che.</li>\n<li><a href=\"/destinations/hue-imperial-city-guide/\">Hue Imperial City Guide</a> &mdash; tour the Citadel, Nguyen Dynasty"
    },
    {
        "post_id": sources["hoi-an-street-food-guide"]["ID"],
        "slug": "hoi-an-street-food-guide",
        "pillar": "hue-street-food-guide",
        "target": "<li><a href=\"/destinations/hoi-an-ancient-town-guide/\">Hoi An Ancient Town Guide</a> &mdash; ticket rules, heritage houses, Japanese Covered Bridge, and walking routes.</li>",
        "replacement": "<li><a href=\"/destinations/hue-street-food-guide/\">Hue Street Food Guide</a> &mdash; compare central flavors with authentic Bun Bo Hue, royal steamed cakes, and clam rice.</li>\n<li><a href=\"/destinations/hoi-an-ancient-town-guide/\">Hoi An Ancient Town Guide</a> &mdash; ticket rules, heritage houses, Japanese Covered Bridge, and walking routes.</li>"
    },

    # =========================================================================
    # Group 7: vietnam-in-july (4 links)
    # URL: /plan/vietnam-in-july/
    # =========================================================================
    {
        "post_id": sources["vietnam-in-june"]["ID"],
        "slug": "vietnam-in-june",
        "pillar": "vietnam-in-july",
        "target": "<li><a href=\"/plan/best-time-to-visit-vietnam/\">Best Time to Visit Vietnam: Weather, Seasons &amp; Regional Guides</a></li>",
        "replacement": "<li><a href=\"/plan/vietnam-in-july/\">Vietnam in July: Weather, Beach Sun &amp; Rain Patterns</a></li>\n<li><a href=\"/plan/best-time-to-visit-vietnam/\">Best Time to Visit Vietnam: Weather, Seasons &amp; Regional Guides</a></li>"
    },
    {
        "post_id": sources["vietnam-in-may"]["ID"],
        "slug": "vietnam-in-may",
        "pillar": "vietnam-in-july",
        "target": "<li><a href=\"/plan/vietnam-in-june/\">Vietnam in June</a> &mdash; summer weather patterns, central coast sunshine, and peak beach season.</li>",
        "replacement": "<li><a href=\"/plan/vietnam-in-june/\">Vietnam in June</a> &mdash; summer weather patterns, central coast sunshine, and peak beach season.</li>\n<li><a href=\"/plan/vietnam-in-july/\">Vietnam in July</a> &mdash; mid-summer beach conditions, rain shadow mechanics, and domestic holiday peaks.</li>"
    },
    {
        "post_id": sources["best-time-to-visit-vietnam"]["ID"],
        "slug": "best-time-to-visit-vietnam",
        "pillar": "vietnam-in-july",
        "target": "changing region focus can be wiser than keeping a famous stop that no longer fits the month.",
        "replacement": "changing region focus can be wiser than keeping a famous stop that no longer fits the month (for summer beach planning, consult our <a href=\"/plan/vietnam-in-july/\">Vietnam in July travel guide</a>)."
    },
    {
        "post_id": sources["cham-islands-day-trip-guide"]["ID"],
        "slug": "cham-islands-day-trip-guide",
        "pillar": "vietnam-in-july",
        "target": "<li><a href=\"/destinations/hoi-an-ancient-town-guide/\">Hoi An Ancient Town Guide</a> &mdash; walking tickets, heritage landmarks, lantern nights, and tailor shops.</li>",
        "replacement": "<li><a href=\"/plan/vietnam-in-july/\">Vietnam in July Travel Guide</a> &mdash; regional rain patterns, summer heatwaves, and central beach sunshine.</li>\n<li><a href=\"/destinations/hoi-an-ancient-town-guide/\">Hoi An Ancient Town Guide</a> &mdash; walking tickets, heritage landmarks, lantern nights, and tailor shops.</li>"
    },
]

def main():
    print("=" * 70)
    print("VIETNAMGUIDE STAGE 66: ASSEMBLING & VALIDATING INTERNAL LINK MESH")
    print("=" * 70)
    print(f"Total operations defined: {len(OPERATIONS)}")

    # Work on a copy of the contents
    updated_contents = {slug: data['content'] for slug, data in sources.items()}
    
    pillars_link_count = {}
    
    for i, op in enumerate(OPERATIONS, 1):
        slug = op['slug']
        pillar = op['pillar']
        target = op['target']
        repl = op['replacement']
        
        c = updated_contents[slug]
        cnt = c.count(target)
        if cnt == 0:
            raise ValueError(f"Op #{i} FAILED: Target string not found in '{slug}'!\nNeedle: {target[:60]}")
        if cnt > 1:
            raise ValueError(f"Op #{i} FAILED: Target string occurs {cnt} times in '{slug}'!\nNeedle: {target[:60]}")
            
        updated_contents[slug] = c.replace(target, repl, 1)
        pillars_link_count[pillar] = pillars_link_count.get(pillar, 0) + 1
        print(f"  [OP {i:02d}/28 OK] Post #{op['post_id']} ({slug}) -> Linked to {pillar}")

    print("\nInbound links distribution across 7 pillars:")
    for p, cnt in sorted(pillars_link_count.items()):
        print(f"  - {p}: {cnt} inbound links")
        assert cnt >= 4, f"Pillar {p} has fewer than 4 inbound links ({cnt})!"

    # Package output payload for VPS deployment
    out_payload = []
    # Group changes by post so each post is updated once
    posts_to_update = {}
    for op in OPERATIONS:
        slug = op['slug']
        posts_to_update[slug] = {
            'post_id': op['post_id'],
            'slug': slug,
            'content': updated_contents[slug]
        }

    for slug, pdata in posts_to_update.items():
        out_payload.append(pdata)

    ops_file = os.path.join(base_dir, 'stage66_mesh_ops.json')
    with open(ops_file, 'w', encoding='utf-8') as f:
        json.dump(out_payload, f, ensure_ascii=False, indent=2)

    print(f"\nSuccessfully verified and compiled {len(out_payload)} modified posts into {ops_file}")
    print("=" * 70)

if __name__ == '__main__':
    main()

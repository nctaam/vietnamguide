# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 68: Assemble and Validate Internal Link Mesh
"""

import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

base_dir = os.path.dirname(os.path.abspath(__file__))
sources = json.load(open(os.path.join(base_dir, 'stage68_mesh_sources.json'), 'r', encoding='utf-8'))

OPERATIONS = [
    # Group 1: Links to vietnam-in-october
    {
        "post_id": 826,
        "slug": "vietnam-in-september",
        "pillar": "vietnam-in-october",
        "target": '<li>Traveling earlier or later? See what to expect in <a href="/plan/vietnam-in-august/">Vietnam in August</a> or <a href="/plan/vietnam-in-november/">Vietnam in November</a>.</li>',
        "replacement": '<li>Traveling earlier or later? See what to expect in <a href="/plan/vietnam-in-august/">Vietnam in August</a>, our autumn guide to <a href="/plan/vietnam-in-october/">Vietnam in October</a>, or <a href="/plan/vietnam-in-november/">Vietnam in November</a>.</li>'
    },
    {
        "post_id": 651,
        "slug": "vietnam-in-november",
        "pillar": "vietnam-in-october",
        "target": 'compare with winter travel in <a href="/plan/vietnam-in-december/">Vietnam in December</a>',
        "replacement": 'review autumn conditions in <a href="/plan/vietnam-in-october/">Vietnam in October</a>, compare with winter travel in <a href="/plan/vietnam-in-december/">Vietnam in December</a>'
    },
    {
        "post_id": 14,
        "slug": "best-time-to-visit-vietnam",
        "pillar": "vietnam-in-october",
        "target": '(for summer beach planning, consult our <a href="/plan/vietnam-in-july/">Vietnam in July travel guide</a>)',
        "replacement": '(for autumn planning, consult our <a href="/plan/vietnam-in-october/">Vietnam in October guide</a>, or for summer beach planning see our <a href="/plan/vietnam-in-july/">Vietnam in July travel guide</a>)'
    },
    {
        "post_id": 518,
        "slug": "sapa-travel-guide",
        "pillar": "vietnam-in-october",
        "target": '<li><span class="vg-related-route-step">07</span><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a><span class="vg-related-route-note">Compare easier karst countryside with a mountain commitment.</span></li>',
        "replacement": '<li><span class="vg-related-route-step">00</span><a href="/plan/vietnam-in-october/">Vietnam in October Guide</a><span class="vg-related-route-note">Plan autumn trekking during the crisp northern shoulder season.</span></li>\n<li><span class="vg-related-route-step">07</span><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a><span class="vg-related-route-note">Compare easier karst countryside with a mountain commitment.</span></li>'
    },

    # Group 2: Links to best-things-to-do-in-ho-chi-minh-city
    {
        "post_id": 262,
        "slug": "ho-chi-minh-city-travel-guide",
        "pillar": "best-things-to-do-in-ho-chi-minh-city",
        "target": '<a href="/compare/cu-chi-tunnels-ben-duoc-vs-ben-dinh/">Ben Duoc vs Ben Dinh Cu Chi Tunnels</a> comparison,',
        "replacement": 'our comprehensive <a href="/destinations/best-things-to-do-in-ho-chi-minh-city/">Best Things to Do in Ho Chi Minh City</a> guide, <a href="/compare/cu-chi-tunnels-ben-duoc-vs-ben-dinh/">Ben Duoc vs Ben Dinh Cu Chi Tunnels</a> comparison,'
    },
    {
        "post_id": 615,
        "slug": "saigon-street-food-guide",
        "pillar": "best-things-to-do-in-ho-chi-minh-city",
        "target": '<li><a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a> &mdash; complete city itinerary and neighborhood orientation.</li>',
        "replacement": '<li><a href="/destinations/best-things-to-do-in-ho-chi-minh-city/">Best Things to Do in Ho Chi Minh City</a> &mdash; top war history museums, rooftop cafes, and cultural sights.</li>\n<li><a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a> &mdash; complete city itinerary and neighborhood orientation.</li>'
    },
    {
        "post_id": 268,
        "slug": "best-day-trips-from-ho-chi-minh-city",
        "pillar": "best-things-to-do-in-ho-chi-minh-city",
        "target": 'highway excursions. Verify seasonal weather patterns,',
        "replacement": 'highway excursions, after exploring our curated <a href="/destinations/best-things-to-do-in-ho-chi-minh-city/">Best Things to Do in Ho Chi Minh City</a> guide. Verify seasonal weather patterns,'
    },
    {
        "post_id": 498,
        "slug": "hanoi-vs-ho-chi-minh-city",
        "pillar": "best-things-to-do-in-ho-chi-minh-city",
        "target": '<li><span class="vg-related-route-step">04</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>',
        "replacement": '<li><span class="vg-related-route-step">00</span><a href="/destinations/best-things-to-do-in-ho-chi-minh-city/">Best Things to Do in Ho Chi Minh City</a><span class="vg-related-route-note">Discover urban highlights, war museums, and night markets in Saigon.</span></li>\n<li><span class="vg-related-route-step">04</span><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>'
    },

    # Group 3: Links to ho-chi-minh-city-to-da-lat-transport
    {
        "post_id": 611,
        "slug": "da-lat-travel-guide",
        "pillar": "ho-chi-minh-city-to-da-lat-transport",
        "target": '<li><a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide: Urban Hub to Highland Escape</a></li>',
        "replacement": '<li><a href="/plan/ho-chi-minh-city-to-da-lat-transport/">Ho Chi Minh City to Da Lat Transport: Sleeper Buses, Flights &amp; Private Cars</a></li>\n<li><a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide: Urban Hub to Highland Escape</a></li>'
    },
    {
        "post_id": 824,
        "slug": "da-lat-coffee-farms-guide",
        "pillar": "ho-chi-minh-city-to-da-lat-transport",
        "target": '<li><a href="/plan/da-lat-to-nha-trang-transport/">Da Lat to Nha Trang Transport</a>: Ready for the beach? Here is how to get from the mountains to the coast.</li>',
        "replacement": '<li><a href="/plan/ho-chi-minh-city-to-da-lat-transport/">Ho Chi Minh City to Da Lat Transport</a>: Compare limousine sleeper buses, direct flights, and private transfers from the south.</li>\n<li><a href="/plan/da-lat-to-nha-trang-transport/">Da Lat to Nha Trang Transport</a>: Ready for the beach? Here is how to get from the mountains to the coast.</li>'
    },
    {
        "post_id": 753,
        "slug": "da-lat-waterfalls-guide",
        "pillar": "ho-chi-minh-city-to-da-lat-transport",
        "target": '<li><a href="/plan/da-lat-to-nha-trang-transport/">Da Lat to Nha Trang Transport: Khanh Le Pass Buses &amp; Limousines</a></li>',
        "replacement": '<li><a href="/plan/ho-chi-minh-city-to-da-lat-transport/">Ho Chi Minh City to Da Lat Transport: Sleeper Buses, Flights &amp; Cars</a></li>\n<li><a href="/plan/da-lat-to-nha-trang-transport/">Da Lat to Nha Trang Transport: Khanh Le Pass Buses &amp; Limousines</a></li>'
    },
    {
        "post_id": 155,
        "slug": "transport-within-vietnam",
        "pillar": "ho-chi-minh-city-to-da-lat-transport",
        "target": '<a href="/plan/vietnam-to-cambodia-border-crossings/">Vietnam to Cambodia Border Crossings</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>',
        "replacement": 'our route guide on <a href="/plan/ho-chi-minh-city-to-da-lat-transport/">Ho Chi Minh City to Da Lat transport</a>, <a href="/plan/vietnam-to-cambodia-border-crossings/">Vietnam to Cambodia Border Crossings</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>'
    },

    # Group 4: Links to where-to-stay-in-phu-quoc
    {
        "post_id": 234,
        "slug": "phu-quoc-travel-guide",
        "pillar": "where-to-stay-in-phu-quoc",
        "target": 'beach value in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>.',
        "replacement": 'beach value in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, and hotel locations in our <a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc guide</a>.'
    },
    {
        "post_id": 751,
        "slug": "phu-quoc-beaches-guide",
        "pillar": "where-to-stay-in-phu-quoc",
        "target": '<li><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide: Island Logistics &amp; Itineraries</a></li>',
        "replacement": '<li><a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc: Best Beaches, Areas &amp; Resorts</a></li>\n<li><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide: Island Logistics &amp; Itineraries</a></li>'
    },
    {
        "post_id": 752,
        "slug": "con-dao-vs-phu-quoc",
        "pillar": "where-to-stay-in-phu-quoc",
        "target": '<li><a href="/destinations/phu-quoc-beaches-guide/">Phu Quoc Beaches Guide: 7 Best Beaches &amp; Coast Map</a></li>',
        "replacement": '<li><a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc: Best Beaches &amp; Resort Areas</a></li>\n<li><a href="/destinations/phu-quoc-beaches-guide/">Phu Quoc Beaches Guide: 7 Best Beaches &amp; Coast Map</a></li>'
    },
    {
        "post_id": 481,
        "slug": "where-to-stay-in-vietnam-base-decisions",
        "pillar": "where-to-stay-in-phu-quoc",
        "target": '<details><summary>Is it worth staying overnight in Ninh Binh?</summary>',
        "replacement": '<details><summary>Where should I base myself in Phu Quoc?</summary><p>Choose Northern Long Beach or Ong Lang for central dining and sunsets, or southern Khem Beach for quiet white-sand luxury (see our <a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc guide</a>).</p></details>\n<details><summary>Is it worth staying overnight in Ninh Binh?</summary>'
    },

    # Group 5: Links to best-things-to-do-in-da-nang
    {
        "post_id": 213,
        "slug": "da-nang-travel-guide",
        "pillar": "best-things-to-do-in-da-nang",
        "target": 'test Da Nang against <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>,',
        "replacement": 'plan sightseeing with our <a href="/destinations/best-things-to-do-in-da-nang/">Best Things to Do in Da Nang</a> guide, test Da Nang against <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>,'
    },
    {
        "post_id": 501,
        "slug": "da-nang-beaches-guide",
        "pillar": "best-things-to-do-in-da-nang",
        "target": '<li><span class="vg-related-route-step">04</span><a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a>',
        "replacement": '<li><span class="vg-related-route-step">00</span><a href="/destinations/best-things-to-do-in-da-nang/">Best Things to Do in Da Nang</a><span class="vg-related-route-note">Marble Mountains, Son Tra Peninsula, and Dragon Bridge weekend shows.</span></li>\n<li><span class="vg-related-route-step">04</span><a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a>'
    },
    {
        "post_id": 616,
        "slug": "da-nang-street-food-guide",
        "pillar": "best-things-to-do-in-da-nang",
        "target": 'Chau Thi Vinh Te street, which is packed with neighborhood student eateries.</p>',
        "replacement": 'Chau Thi Vinh Te street, which is packed with neighborhood student eateries. Combine food tours with sightseeing using our <a href="/destinations/best-things-to-do-in-da-nang/">Best Things to Do in Da Nang guide</a>.</p>'
    },
    {
        "post_id": 209,
        "slug": "da-nang-vs-hoi-an",
        "pillar": "best-things-to-do-in-da-nang",
        "target": 'test the central Vietnam base against <a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a>,',
        "replacement": 'test the central Vietnam base against our <a href="/destinations/best-things-to-do-in-da-nang/">Best Things to Do in Da Nang</a> guide, <a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a>,'
    },

    # Group 6: Links to ho-chi-minh-city-to-phu-quoc-transport
    {
        "post_id": 234,
        "slug": "phu-quoc-travel-guide",
        "pillar": "ho-chi-minh-city-to-phu-quoc-transport",
        "target": '<a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>',
        "replacement": '<a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/plan/ho-chi-minh-city-to-phu-quoc-transport/">Ho Chi Minh City to Phu Quoc Transport</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>'
    },
    {
        "post_id": 241,
        "slug": "phu-quoc-vs-nha-trang",
        "pillar": "ho-chi-minh-city-to-phu-quoc-transport",
        "target": 'Phu Quoc-specific routing in <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a>.',
        "replacement": 'Phu Quoc-specific routing in <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a> and <a href="/plan/ho-chi-minh-city-to-phu-quoc-transport/">Ho Chi Minh City to Phu Quoc Transport</a>.'
    },
    {
        "post_id": 231,
        "slug": "best-islands-in-vietnam",
        "pillar": "ho-chi-minh-city-to-phu-quoc-transport",
        "target": '<a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, and <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>.',
        "replacement": '<a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>, and our route breakdown on <a href="/plan/ho-chi-minh-city-to-phu-quoc-transport/">Ho Chi Minh City to Phu Quoc transport</a>.'
    },
    {
        "post_id": 98,
        "slug": "vietnam-travel-guide",
        "pillar": "ho-chi-minh-city-to-phu-quoc-transport",
        "target": 'the <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam guide</a> for pacing,',
        "replacement": 'transit choices in our <a href="/plan/ho-chi-minh-city-to-phu-quoc-transport/">Ho Chi Minh City to Phu Quoc transport guide</a>, the <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam guide</a> for pacing,'
    },

    # Group 7: Links to where-to-stay-in-nha-trang
    {
        "post_id": 244,
        "slug": "nha-trang-travel-guide",
        "pillar": "where-to-stay-in-nha-trang",
        "target": 'explore northern basalt cliffs via the <a href="/destinations/phu-yen-travel-guide/">Phu Yen Travel Guide</a>,',
        "replacement": 'choose accommodations with our <a href="/destinations/where-to-stay-in-nha-trang/">Where to Stay in Nha Trang guide</a>, explore northern basalt cliffs via the <a href="/destinations/phu-yen-travel-guide/">Phu Yen Travel Guide</a>,'
    },
    {
        "post_id": 247,
        "slug": "mui-ne-vs-nha-trang",
        "pillar": "where-to-stay-in-nha-trang",
        "target": 'Use <a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a> for the active city-beach base,',
        "replacement": 'Use <a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a> and <a href="/destinations/where-to-stay-in-nha-trang/">Where to Stay in Nha Trang</a> for the active city-beach base,'
    },
    {
        "post_id": 756,
        "slug": "da-lat-to-nha-trang-transport",
        "pillar": "where-to-stay-in-nha-trang",
        "target": '<li><a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide: Beaches, Islands &amp; Diving</a></li>',
        "replacement": '<li><a href="/destinations/where-to-stay-in-nha-trang/">Where to Stay in Nha Trang: Beach Areas &amp; Hotels</a></li>\n<li><a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide: Beaches, Islands &amp; Diving</a></li>'
    },
    {
        "post_id": 204,
        "slug": "best-beaches-in-vietnam",
        "pillar": "where-to-stay-in-nha-trang",
        "target": '<a href="/destinations/cat-ba-travel-guide/">Cat Ba Travel Guide</a>, <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>,',
        "replacement": 'our hotel guide on <a href="/destinations/where-to-stay-in-nha-trang/">Where to Stay in Nha Trang</a>, <a href="/destinations/cat-ba-travel-guide/">Cat Ba Travel Guide</a>, <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>,'
    },
]

def main():
    print("=" * 70)
    print("VIETNAMGUIDE STAGE 68: ASSEMBLING & VALIDATING INTERNAL LINK MESH")
    print("=" * 70)
    print(f"Total operations defined: {len(OPERATIONS)}")

    # We track modified contents in a dictionary keyed by slug
    modified_contents = {}
    for slug, data in sources.items():
        if data:
            modified_contents[slug] = data['content']

    errors = 0
    pillar_counts = {}

    for idx, op in enumerate(OPERATIONS, 1):
        slug = op['slug']
        pid = op['post_id']
        target = op['target']
        replacement = op['replacement']
        pillar = op['pillar']

        pillar_counts[pillar] = pillar_counts.get(pillar, 0) + 1

        if slug not in modified_contents:
            print(f"  [OP {idx:02d}/28 ERROR] Slug '{slug}' not found in sources!")
            errors += 1
            continue

        current_content = modified_contents[slug]
        occurrences = current_content.count(target)

        if occurrences == 0:
            print(f"  [OP {idx:02d}/28 ERROR] Post #{pid} ({slug}) -> Target string NOT FOUND!")
            errors += 1
        elif occurrences > 1:
            print(f"  [OP {idx:02d}/28 ERROR] Post #{pid} ({slug}) -> Target string occurs {occurrences} times (MUST BE EXACTLY 1)!")
            errors += 1
        else:
            # Perform replacement on current content
            modified_contents[slug] = current_content.replace(target, replacement, 1)
            print(f"  [OP {idx:02d}/28 OK] Post #{pid} ({slug}) -> Linked to {pillar}")

    print("\nInbound links distribution across 7 pillars:")
    for p, c in sorted(pillar_counts.items()):
        print(f"  - {p}: {c} inbound links")

    if errors > 0:
        print(f"\n[FAILED] Encountered {errors} validation errors. Fix before compiling.")
        sys.exit(1)

    # Compile modified posts into list format expected by apply_stage68_mesh.py
    output_ops = []
    for slug, new_content in modified_contents.items():
        original_content = sources[slug]['content']
        if new_content != original_content:
            output_ops.append({
                "post_id": sources[slug]['ID'],
                "slug": slug,
                "content": new_content
            })

    out_file = os.path.join(base_dir, 'stage68_mesh_ops.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(output_ops, f, ensure_ascii=False, indent=2)

    print(f"\nSuccessfully verified and compiled {len(output_ops)} modified posts into {out_file}")
    print("=" * 70)

if __name__ == '__main__':
    main()

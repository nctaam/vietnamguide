# -*- coding: utf-8 -*-
"""
Assemble and strictly validate the 28 inbound links for Stage 71 mesh
"""

import json
import os

def main():
    sources_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage71_mesh_sources.json')
    sources = json.load(open(sources_path, encoding='utf-8'))
    
    replacements = {
        # --- Pillar 1: where-to-stay-in-ha-long-bay ---
        "ha-long-bay-travel-guide": [
            (
                'our activity guide on <a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a>, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>,',
                'our activity guide on <a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a>, our accommodation review on <a href="/destinations/where-to-stay-in-ha-long-bay/">Where to Stay in Ha Long Bay</a>, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>,',
                "where-to-stay-in-ha-long-bay"
            )
        ],
        "best-things-to-do-in-ha-long-bay": [
            (
                '<li><a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>: Cruise vessel categories, itineraries, and booking advice.</li>',
                '<li><a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>: Cruise vessel categories, itineraries, and booking advice.</li>\n        <li><a href="/destinations/where-to-stay-in-ha-long-bay/">Where to Stay in Ha Long Bay</a>: Bai Chay hotel strip vs Tuan Chau marina resorts.</li>',
                "where-to-stay-in-ha-long-bay"
            )
        ],
        "ha-long-bay-vs-lan-ha-bay": [
            (
                'our itinerary guide on <a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a>, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>,',
                'our itinerary guide on <a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a>, our base advice on <a href="/destinations/where-to-stay-in-ha-long-bay/">Where to Stay in Ha Long Bay</a>, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>,',
                "where-to-stay-in-ha-long-bay"
            ),
            (
                '<a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>,',
                '<a href="/destinations/where-to-stay-in-cat-ba/">Where to Stay on Cat Ba Island</a>, <a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>,',
                "where-to-stay-in-cat-ba"
            )
        ],
        "hanoi-to-ha-long-bay-transport": [
            (
                '<li><a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a> &mdash; Sung Sot cave, Ti Top island viewpoints, and kayaking.</li>',
                '<li><a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a> &mdash; Sung Sot cave, Ti Top island viewpoints, and kayaking.</li>\n<li><a href="/destinations/where-to-stay-in-ha-long-bay/">Where to Stay in Ha Long Bay</a> &mdash; mainland hotel staging vs Tuan Chau marina stays.</li>',
                "where-to-stay-in-ha-long-bay"
            )
        ],

        # --- Pillar 2: where-to-stay-in-quy-nhon ---
        "quy-nhon-travel-guide": [
            (
                'our activity breakdown on <a href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>,',
                'our activity breakdown on <a href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon</a>, our hotel review on <a href="/destinations/where-to-stay-in-quy-nhon/">Where to Stay in Quy Nhon</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>,',
                "where-to-stay-in-quy-nhon"
            )
        ],
        "best-things-to-do-in-quy-nhon": [
            (
                '<li><a href="/destinations/quy-nhon-travel-guide/">Quy Nhon Travel Guide</a>: Itineraries, transport, and regional logistics.</li>',
                '<li><a href="/destinations/quy-nhon-travel-guide/">Quy Nhon Travel Guide</a>: Itineraries, transport, and regional logistics.</li>\n        <li><a href="/destinations/where-to-stay-in-quy-nhon/">Where to Stay in Quy Nhon</a>: City beach vs Bai Xep fishing village homestays.</li>',
                "where-to-stay-in-quy-nhon"
            )
        ],
        "best-beaches-in-vietnam": [
            (
                'and our guide on <a href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon</a>.</p>',
                '<a href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon</a>, and our base guide on <a href="/destinations/where-to-stay-in-quy-nhon/">Where to Stay in Quy Nhon</a>.</p>',
                "where-to-stay-in-quy-nhon"
            )
        ],
        "vietnam-train-travel": [
            (
                '<li><a href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon: Cliffs, Bays &amp; Dieu Tri Rail</a></li>',
                '<li><a href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon: Cliffs, Bays &amp; Dieu Tri Rail</a></li>\n<li><a href="/destinations/where-to-stay-in-quy-nhon/">Where to Stay in Quy Nhon: City Beach &amp; Bai Xep Stays</a></li>',
                "where-to-stay-in-quy-nhon"
            ),
            (
                '<li><a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide: Coastal Hub &amp; Hai Van Pass Staging</a></li>',
                '<li><a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide: Coastal Hub &amp; Hai Van Pass Staging</a></li>\n<li><a href="/plan/da-nang-to-nha-trang-transport/">Da Nang to Nha Trang Transport: Sleeper Trains &amp; Coastal Rails</a></li>',
                "da-nang-to-nha-trang-transport"
            )
        ],

        # --- Pillar 3: where-to-stay-in-can-tho ---
        "mekong-delta-travel-guide": [
            (
                'our overland route guide on <a href="/plan/ho-chi-minh-city-to-can-tho-transport/">Ho Chi Minh City to Can Tho Transport</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>,',
                'our overland route guide on <a href="/plan/ho-chi-minh-city-to-can-tho-transport/">Ho Chi Minh City to Can Tho Transport</a>, our base guide on <a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>,',
                "where-to-stay-in-can-tho"
            )
        ],
        "ho-chi-minh-city-to-can-tho-transport": [
            (
                '<li><a href="/destinations/mekong-delta-travel-guide/">Mekong Delta Travel Guide</a>: River branches, homestays, and regional logistics.</li>',
                '<li><a href="/destinations/mekong-delta-travel-guide/">Mekong Delta Travel Guide</a>: River branches, homestays, and regional logistics.</li>\n        <li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf vs riverside eco-resorts.</li>',
                "where-to-stay-in-can-tho"
            )
        ],
        "mekong-delta-floating-markets-guide": [
            (
                '<li><a href="/plan/ho-chi-minh-city-to-can-tho-transport/">Ho Chi Minh City to Can Tho Transport</a></li>',
                '<li><a href="/plan/ho-chi-minh-city-to-can-tho-transport/">Ho Chi Minh City to Can Tho Transport</a></li>\n        <li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a></li>',
                "where-to-stay-in-can-tho"
            )
        ],
        "mekong-delta-overnight-vs-day-trip": [
            (
                '<li><span class="vg-related-route-step">07</span><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a><span class="vg-related-route-note">Test when one Mekong night earns its place.</span></li>\n</ol>',
                '<li><span class="vg-related-route-step">07</span><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a><span class="vg-related-route-note">Test when one Mekong night earns its place.</span></li>\n<li><span class="vg-related-route-step">08</span><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a><span class="vg-related-route-note">Ninh Kieu Wharf walkability and delta river lodges.</span></li>\n</ol>',
                "where-to-stay-in-can-tho"
            )
        ],

        # --- Pillar 4: where-to-stay-in-cat-ba ---
        "cat-ba-travel-guide": [
            (
                '<a href="/plan/hanoi-to-cat-ba-island-transport/">Hanoi to Cat Ba Island Transport</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>,',
                '<a href="/plan/hanoi-to-cat-ba-island-transport/">Hanoi to Cat Ba Island Transport</a>, our island lodging guide on <a href="/destinations/where-to-stay-in-cat-ba/">Where to Stay on Cat Ba Island</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>,',
                "where-to-stay-in-cat-ba"
            )
        ],
        "hanoi-to-cat-ba-island-transport": [
            (
                '<li><a href="/destinations/cat-ba-travel-guide/">Cat Ba Travel Guide: Lan Ha Bay, National Park &amp; Beaches</a></li>',
                '<li><a href="/destinations/cat-ba-travel-guide/">Cat Ba Travel Guide: Lan Ha Bay, National Park &amp; Beaches</a></li>\n<li><a href="/destinations/where-to-stay-in-cat-ba/">Where to Stay on Cat Ba Island: Town Hotels vs Cat Co Beach Resorts</a></li>',
                "where-to-stay-in-cat-ba"
            )
        ],
        "best-islands-in-vietnam": [
            (
                '<a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>,',
                '<a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, <a href="/destinations/where-to-stay-in-cat-ba/">Where to Stay on Cat Ba Island</a>, <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>,',
                "where-to-stay-in-cat-ba"
            )
        ],

        # --- Pillar 5: where-to-stay-in-ha-giang ---
        "ha-giang-loop-planning-guide": [
            (
                '<li><span class="vg-related-route-step">07</span><a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a><span class="vg-related-route-note">Prepare offline maps, backup communication, and contact access for mountain routes.</span></li>\n</ol>',
                '<li><span class="vg-related-route-step">07</span><a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a><span class="vg-related-route-note">Prepare offline maps, backup communication, and contact access for mountain routes.</span></li>\n<li><span class="vg-related-route-step">08</span><a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a><span class="vg-related-route-note">Overnight loop staging in Dong Van, Meo Vac, and Du Gia.</span></li>\n</ol>',
                "where-to-stay-in-ha-giang"
            )
        ],
        "hanoi-to-ha-giang-transport": [
            (
                '<li><span class="vg-related-route-step">07</span><a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a><span class="vg-related-route-note">Keep pickup calls, maps, hotel contacts, and backup communication working.</span></li>\n</ol>',
                '<li><span class="vg-related-route-step">07</span><a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a><span class="vg-related-route-note">Keep pickup calls, maps, hotel contacts, and backup communication working.</span></li>\n<li><span class="vg-related-route-step">08</span><a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a><span class="vg-related-route-note">Staging hostels, Dong Van old town, and village homestays.</span></li>\n</ol>',
                "where-to-stay-in-ha-giang"
            )
        ],
        "ha-giang-safety-guide": [
            (
                '<p>For complete route planning, stopover recommendations, and itinerary pacing, cross-reference our <a href="/destinations/ha-giang-loop-planning-guide/">Ha Giang Loop Planning Guide</a>,',
                '<p>For complete route planning, stopover recommendations, and itinerary pacing, cross-reference our <a href="/destinations/ha-giang-loop-planning-guide/">Ha Giang Loop Planning Guide</a>, our base guide on <a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a>,',
                "where-to-stay-in-ha-giang"
            )
        ],
        "ha-giang-loop-cost-budget": [
            (
                '<p>For more regional road and budgeting advice, consult our <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> pillar guide, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/plan/money-cash-cards-atms/">Money in Vietnam: Cash, Cards and ATMs</a>.</p>',
                '<p>For more regional road and budgeting advice, consult our <a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a> loop guide, our <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> pillar guide, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/plan/money-cash-cards-atms/">Money in Vietnam: Cash, Cards and ATMs</a>.</p>',
                "where-to-stay-in-ha-giang"
            )
        ],

        # --- Pillar 6: ho-chi-minh-city-in-2-days ---
        "ho-chi-minh-city-travel-guide": [
            (
                'plan coastal escapes with our <a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a> guide,',
                'follow our 48-hour guide on <a href="/itineraries/ho-chi-minh-city-in-2-days/">Ho Chi Minh City in 2 Days</a>, plan coastal escapes with our <a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a> guide,',
                "ho-chi-minh-city-in-2-days"
            )
        ],
        "best-things-to-do-in-ho-chi-minh-city": [
            (
                '<li><a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a></li>',
                '<li><a href="/itineraries/ho-chi-minh-city-in-2-days/">Ho Chi Minh City in 2 Days</a></li>\n        <li><a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a></li>',
                "ho-chi-minh-city-in-2-days"
            )
        ],
        "saigon-street-food-guide": [
            (
                '<li><a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a> &mdash; complete city itinerary and neighborhood orientation.</li>',
                '<li><a href="/itineraries/ho-chi-minh-city-in-2-days/">Ho Chi Minh City in 2 Days</a> &mdash; 48-hour itinerary covering landmarks, Chinatown, and food streets.</li>\n<li><a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a> &mdash; complete city itinerary and neighborhood orientation.</li>',
                "ho-chi-minh-city-in-2-days"
            )
        ],
        "hanoi-in-2-days": [
            (
                '<p>Start with <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>, then choose the base with <a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a>',
                '<p>Compare northern and southern urban pacing with <a href="/itineraries/ho-chi-minh-city-in-2-days/">Ho Chi Minh City in 2 Days</a>. Start with <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>, then choose the base with <a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a>',
                "ho-chi-minh-city-in-2-days"
            )
        ],

        # --- Pillar 7: da-nang-to-nha-trang-transport ---
        "da-nang-travel-guide": [
            (
                'our coastal guide on <a href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>,',
                'our coastal guide on <a href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon</a>, our transit guide on <a href="/plan/da-nang-to-nha-trang-transport/">Da Nang to Nha Trang Transport</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>,',
                "da-nang-to-nha-trang-transport"
            )
        ],
        "nha-trang-travel-guide": [
            (
                '<a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>,',
                'our transit guide on <a href="/plan/da-nang-to-nha-trang-transport/">Da Nang to Nha Trang Transport</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>,',
                "da-nang-to-nha-trang-transport"
            )
        ],
        "transport-within-vietnam": [
            (
                'our delta route guide for <a href="/plan/ho-chi-minh-city-to-can-tho-transport/">Ho Chi Minh City to Can Tho transport</a>, <a href="/plan/vietnam-to-cambodia-border-crossings/">Vietnam to Cambodia Border Crossings</a>,',
                'our delta route guide for <a href="/plan/ho-chi-minh-city-to-can-tho-transport/">Ho Chi Minh City to Can Tho transport</a>, our coastal route guide on <a href="/plan/da-nang-to-nha-trang-transport/">Da Nang to Nha Trang transport</a>, <a href="/plan/vietnam-to-cambodia-border-crossings/">Vietnam to Cambodia Border Crossings</a>,',
                "da-nang-to-nha-trang-transport"
            )
        ],
    }

    updated_posts = {}
    total_links_verified = 0

    print("=== Validating 28 Inbound Link Operations for Stage 71 ===")
    for slug, rep_list in replacements.items():
        if slug not in sources or sources[slug] is None:
            raise ValueError(f"Target slug '{slug}' not found in sources!")
        
        post = sources[slug]
        content = post['content']
        post_id = post['ID']

        for target_str, replacement_str, pillar_info in rep_list:
            c = content.count(target_str)
            if c != 1:
                raise ValueError(f"Target string count is {c} (must be 1) for slug '{slug}' -> pillar '{pillar_info}'.\nTarget: {target_str[:80]}...")
            
            content = content.replace(target_str, replacement_str, 1)
            total_links_verified += 1
            print(f"  [OK] {slug} (ID: {post_id}) -> {pillar_info}")

        updated_posts[str(post_id)] = {
            'ID': post_id,
            'slug': slug,
            'title': post['title'],
            'new_content': content
        }

    print(f"\nTotal Inbound Links Verified: {total_links_verified} / 28")
    if total_links_verified != 28:
        raise ValueError(f"Expected 28 inbound links, found {total_links_verified}!")
    
    out_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage71_mesh_ops.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(updated_posts, f, ensure_ascii=False, indent=2)
    print(f"Exported {len(updated_posts)} updated posts to {out_file}")

if __name__ == '__main__':
    main()

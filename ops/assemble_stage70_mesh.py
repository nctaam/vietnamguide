# -*- coding: utf-8 -*-
"""
Assemble and strictly validate the 28 inbound links for Stage 70 mesh
"""

import json
import os

def main():
    sources_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage70_mesh_sources.json')
    sources = json.load(open(sources_path, encoding='utf-8'))
    
    replacements = {
        # --- Pillar 1: best-things-to-do-in-sapa ---
        "sapa-travel-guide": [
            (
                '<li><span class="vg-related-route-step">07</span><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a><span class="vg-related-route-note">Compare easier karst countryside with a mountain commitment.</span></li>\n</ol>',
                '<li><span class="vg-related-route-step">07</span><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a><span class="vg-related-route-note">Compare easier karst countryside with a mountain commitment.</span></li>\n<li><span class="vg-related-route-step">08</span><a href="/destinations/best-things-to-do-in-sapa/">Best Things to Do in Sapa</a><span class="vg-related-route-note">Fansipan summit cable car, Muong Hoa valley treks, and waterfalls.</span></li>\n</ol>',
                "best-things-to-do-in-sapa"
            )
        ],
        "where-to-stay-in-sapa": [
            (
                'read our <a href="/destinations/sapa-travel-guide/">Sapa Travel Guide</a>, <a href="/plan/sapa-trekking-routes-guide/">Sapa Trekking Routes Guide</a>,',
                'read our <a href="/destinations/sapa-travel-guide/">Sapa Travel Guide</a>, our itinerary on <a href="/destinations/best-things-to-do-in-sapa/">Best Things to Do in Sapa</a>, <a href="/plan/sapa-trekking-routes-guide/">Sapa Trekking Routes Guide</a>,',
                "best-things-to-do-in-sapa"
            )
        ],
        "hanoi-to-sapa-transport": [
            (
                '<li><span class="vg-related-route-step">07</span><a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a><span class="vg-related-route-note">Keep pickup calls, maps, hotel contacts, and backup communication working.</span></li>\n</ol>',
                '<li><span class="vg-related-route-step">07</span><a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a><span class="vg-related-route-note">Keep pickup calls, maps, hotel contacts, and backup communication working.</span></li>\n<li><span class="vg-related-route-step">08</span><a href="/destinations/best-things-to-do-in-sapa/">Best Things to Do in Sapa</a><span class="vg-related-route-note">Explore Fansipan peak, village trekking trails, and O Quy Ho Pass.</span></li>\n</ol>',
                "best-things-to-do-in-sapa"
            )
        ],
        "sapa-trekking-routes-guide": [
            (
                '<li><a href="/destinations/sapa-travel-guide/">Sapa Travel Guide</a> &mdash; complete overview of towns, weather, sleeper trains, and cultural homestays.</li>',
                '<li><a href="/destinations/sapa-travel-guide/">Sapa Travel Guide</a> &mdash; complete overview of towns, weather, sleeper trains, and cultural homestays.</li>\n<li><a href="/destinations/best-things-to-do-in-sapa/">Best Things to Do in Sapa</a> &mdash; Fansipan cable car, mountain passes, waterfalls, and local markets.</li>',
                "best-things-to-do-in-sapa"
            )
        ],

        # --- Pillar 2: best-things-to-do-in-ninh-binh ---
        "ninh-binh-travel-guide": [
            (
                'then check <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>,',
                'then check our activity shortlist on <a href="/destinations/best-things-to-do-in-ninh-binh/">Best Things to Do in Ninh Binh</a>, <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>,',
                "best-things-to-do-in-ninh-binh"
            )
        ],
        "where-to-stay-in-ninh-binh": [
            (
                'choose the boat with <a href="/compare/trang-an-vs-tam-coc/">Trang An vs Tam Coc</a>, then use this page before booking the room.',
                'plan daily activities with <a href="/destinations/best-things-to-do-in-ninh-binh/">Best Things to Do in Ninh Binh</a>, choose the boat with <a href="/compare/trang-an-vs-tam-coc/">Trang An vs Tam Coc</a>, then use this page before booking the room.',
                "best-things-to-do-in-ninh-binh"
            )
        ],
        "hanoi-to-ninh-binh-transport": [
            (
                'tions/hanoi-travel-guide/">Hanoi Travel Guide</a>, <a href="/itineraries/hanoi-in-2-days/">Hanoi in 2 Days</a>,',
                'tions/hanoi-travel-guide/">Hanoi Travel Guide</a>, our highlights guide on <a href="/destinations/best-things-to-do-in-ninh-binh/">Best Things to Do in Ninh Binh</a>, <a href="/itineraries/hanoi-in-2-days/">Hanoi in 2 Days</a>,',
                "best-things-to-do-in-ninh-binh"
            )
        ],
        "hang-mua-ninh-binh-guide": [
            (
                '<p>Drone regulations in Ninh Binh are strictly enforced due to protected heritage status and military radar zones. Flying drones over Hang Mua without an official Ministry of National Defense permit is prohibited and subject to equipment confiscation and heavy fines.</p>\n</div>',
                '<p>Drone regulations in Ninh Binh are strictly enforced due to protected heritage status and military radar zones. Flying drones over Hang Mua without an official Ministry of National Defense permit is prohibited and subject to equipment confiscation and heavy fines.</p>\n</div>\n\n<p>For wider countryside planning, pair this climb with our <a href="/destinations/best-things-to-do-in-ninh-binh/">Best Things to Do in Ninh Binh</a> guide, our base recommendations in <a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a>, and the complete <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>.</p>',
                "best-things-to-do-in-ninh-binh"
            )
        ],

        # --- Pillar 3: best-things-to-do-in-ha-long-bay ---
        "ha-long-bay-travel-guide": [
            (
                'tions/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>,',
                'our activity guide on <a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a>, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>,',
                "best-things-to-do-in-ha-long-bay"
            )
        ],
        "ha-long-bay-vs-lan-ha-bay": [
            (
                'tions/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>,',
                'our itinerary guide on <a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a>, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>,',
                "best-things-to-do-in-ha-long-bay"
            )
        ],
        "hanoi-to-ha-long-bay-transport": [
            (
                '<li><a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a> &mdash; comprehensive attraction maps and port breakdowns.</li>',
                '<li><a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a> &mdash; comprehensive attraction maps and port breakdowns.</li>\n<li><a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a> &mdash; Sung Sot cave, Ti Top island viewpoints, and kayaking.</li>',
                "best-things-to-do-in-ha-long-bay"
            )
        ],
        "bai-tu-long-bay-guide": [
            (
                'tions/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>,',
                'our comprehensive <a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a> guide, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>,',
                "best-things-to-do-in-ha-long-bay"
            )
        ],

        # --- Pillar 4: best-things-to-do-in-mui-ne & Pillar 5: where-to-stay-in-mui-ne ---
        "mui-ne-vs-nha-trang": [
            (
                'and <a href="/destinations/where-to-stay-in-nha-trang/">Where to Stay in Nha Trang</a> for the active city-beach base,',
                'our accommodation review on <a href="/destinations/where-to-stay-in-mui-ne/">Where to Stay in Mui Ne</a>, and <a href="/destinations/where-to-stay-in-nha-trang/">Where to Stay in Nha Trang</a> for the active city-beach base,',
                "where-to-stay-in-mui-ne"
            ),
            (
                'our transit guide on <a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a>,',
                'our highlights guide on <a href="/destinations/best-things-to-do-in-mui-ne/">Best Things to Do in Mui Ne</a>, our transit guide on <a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a>,',
                "best-things-to-do-in-mui-ne"
            )
        ],
        "ho-chi-minh-city-to-mui-ne-transport": [
            (
                '<li><a href="/destinations/mui-ne-travel-guide/">Mui Ne Travel Guide</a></li>',
                '<li><a href="/destinations/best-things-to-do-in-mui-ne/">Best Things to Do in Mui Ne</a></li>\n        <li><a href="/destinations/where-to-stay-in-mui-ne/">Where to Stay in Mui Ne</a></li>',
                "best-things-to-do-in-mui-ne + where-to-stay-in-mui-ne"
            )
        ],
        "where-to-stay-in-nha-trang": [
            (
                '<li><a href="/compare/mui-ne-vs-nha-trang/">Mui Ne vs Nha Trang</a></li>',
                '<li><a href="/compare/mui-ne-vs-nha-trang/">Mui Ne vs Nha Trang</a></li>\n        <li><a href="/destinations/best-things-to-do-in-mui-ne/">Best Things to Do in Mui Ne</a></li>',
                "best-things-to-do-in-mui-ne"
            )
        ],
        "best-beaches-in-vietnam": [
            (
                'our activity guide on <a href="/destinations/best-things-to-do-in-nha-trang/">Best Things to Do in Nha Trang</a>,',
                'our activity guide on <a href="/destinations/best-things-to-do-in-nha-trang/">Best Things to Do in Nha Trang</a>, our coastal guide on <a href="/destinations/best-things-to-do-in-mui-ne/">Best Things to Do in Mui Ne</a>,',
                "best-things-to-do-in-mui-ne"
            ),
            (
                'and <a href="/destinations/bai-tu-long-bay-guide/">Bai Tu Long Bay Guide</a>.</p>',
                '<a href="/destinations/bai-tu-long-bay-guide/">Bai Tu Long Bay Guide</a>, and our guide on <a href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon</a>.</p>',
                "best-things-to-do-in-quy-nhon"
            )
        ],

        # --- Pillar 5 remaining: where-to-stay-in-mui-ne ---
        "where-to-stay-in-vietnam-base-decisions": [
            (
                '<details><summary>How many hotel moves are too many?</summary><p>On a first trip, every hotel move should solve a clear problem. If the move does not protect a new region, beach chapter, countryside night, early flight, or major transfer, it probably adds friction.</p></details>',
                '<details><summary>How many hotel moves are too many?</summary><p>On a first trip, every hotel move should solve a clear problem. If the move does not protect a new region, beach chapter (such as deciding <a href="/destinations/where-to-stay-in-mui-ne/">where to stay in Mui Ne</a>), countryside night, early flight, or major transfer, it probably adds friction.</p></details>',
                "where-to-stay-in-mui-ne"
            )
        ],
        "best-day-trips-from-ho-chi-minh-city": [
            (
                'our seaside transfer guide on <a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a>,',
                'our seaside transfer guide on <a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a>, our coastal hotel guide on <a href="/destinations/where-to-stay-in-mui-ne/">Where to Stay in Mui Ne</a>,',
                "where-to-stay-in-mui-ne"
            )
        ],

        # --- Pillar 6: best-things-to-do-in-quy-nhon ---
        "quy-nhon-travel-guide": [
            (
                'f="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>,',
                'our activity breakdown on <a href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>,',
                "best-things-to-do-in-quy-nhon"
            )
        ],
        "vietnam-train-travel": [
            (
                '<li><a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide: Coastal Hub &amp; Hai Van Pass Staging</a></li>',
                '<li><a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide: Coastal Hub &amp; Hai Van Pass Staging</a></li>\n<li><a href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon: Cliffs, Bays &amp; Dieu Tri Rail</a></li>',
                "best-things-to-do-in-quy-nhon"
            )
        ],
        "da-nang-travel-guide": [
            (
                '<a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>,',
                '<a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, our coastal guide on <a href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>,',
                "best-things-to-do-in-quy-nhon"
            )
        ],

        # --- Pillar 7: ho-chi-minh-city-to-can-tho-transport ---
        "mekong-delta-travel-guide": [
            (
                '<a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>,',
                '<a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, our overland route guide on <a href="/plan/ho-chi-minh-city-to-can-tho-transport/">Ho Chi Minh City to Can Tho Transport</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>,',
                "ho-chi-minh-city-to-can-tho-transport"
            )
        ],
        "mekong-delta-floating-markets-guide": [
            (
                '<li><a href="/destinations/mekong-delta-travel-guide/">Mekong Delta Travel Guide</a></li>',
                '<li><a href="/destinations/mekong-delta-travel-guide/">Mekong Delta Travel Guide</a></li>\n        <li><a href="/plan/ho-chi-minh-city-to-can-tho-transport/">Ho Chi Minh City to Can Tho Transport</a></li>',
                "ho-chi-minh-city-to-can-tho-transport"
            )
        ],
        "transport-within-vietnam": [
            (
                'our transit breakdown for <a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne transport</a>,',
                'our transit breakdown for <a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne transport</a>, our delta route guide for <a href="/plan/ho-chi-minh-city-to-can-tho-transport/">Ho Chi Minh City to Can Tho transport</a>,',
                "ho-chi-minh-city-to-can-tho-transport"
            )
        ],
        "ho-chi-minh-city-travel-guide": [
            (
                'plan coastal escapes with our <a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a> guide,',
                'plan coastal escapes with our <a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a> guide, our delta transit guide on <a href="/plan/ho-chi-minh-city-to-can-tho-transport/">Ho Chi Minh City to Can Tho Transport</a>,',
                "ho-chi-minh-city-to-can-tho-transport"
            )
        ],
    }

    updated_posts = {}
    total_links_verified = 0

    print("=== Validating 28 Inbound Link Operations for Stage 70 ===")
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
            links_added = 2 if '+' in pillar_info else 1
            total_links_verified += links_added
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
    
    out_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage70_mesh_ops.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(updated_posts, f, ensure_ascii=False, indent=2)
    print(f"Exported {len(updated_posts)} updated posts to {out_file}")

if __name__ == '__main__':
    main()

# -*- coding: utf-8 -*-
"""
Assemble and strictly validate the 28 inbound links for Stage 69 mesh
"""

import json
import os

def main():
    sources_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage69_mesh_sources.json')
    sources = json.load(open(sources_path, encoding='utf-8'))
    
    # Define replacements per slug
    # slug -> list of (target_str, replacement_str, pillar_slug)
    replacements = {
        # --- Pillar 1: where-to-stay-in-da-lat & Pillar 2: best-things-to-do-in-da-lat ---
        "da-lat-travel-guide": [
            (
                '<div class="vg-related-routes vg-da-lat-related-manual">\n<ul>\n<li><a href="/destinations/da-lat-coffee-farms-guide/">Da Lat Coffee Farms Guide: Plantations, Tours &amp; Tasting</a></li>',
                '<div class="vg-related-routes vg-da-lat-related-manual">\n<ul>\n<li><a href="/destinations/where-to-stay-in-da-lat/">Where to Stay in Da Lat: Best Areas, French Villas &amp; Lakes</a></li>\n<li><a href="/destinations/best-things-to-do-in-da-lat/">Best Things to Do in Da Lat: Waterfalls, Cable Cars &amp; Cafes</a></li>\n<li><a href="/destinations/da-lat-coffee-farms-guide/">Da Lat Coffee Farms Guide: Plantations, Tours &amp; Tasting</a></li>',
                "where-to-stay-in-da-lat + best-things-to-do-in-da-lat"
            )
        ],
        "da-lat-coffee-farms-guide": [
            (
                '<li><a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide</a>: Find the best neighborhoods to base yourself in the city.</li>',
                '<li><a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide</a>: Find the best neighborhoods to base yourself in the city.</li>\n    <li><a href="/destinations/where-to-stay-in-da-lat/">Where to Stay in Da Lat</a>: Review central boutique hotels, pine valley villas, and Tuyen Lam lakeside resorts.</li>',
                "where-to-stay-in-da-lat"
            )
        ],
        "da-lat-waterfalls-guide": [
            (
                '<li><a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide: Highlands, Cafes &amp; Countryside</a></li>',
                '<li><a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide: Highlands, Cafes &amp; Countryside</a></li>\n<li><a href="/destinations/where-to-stay-in-da-lat/">Where to Stay in Da Lat: Pine Forest Resorts &amp; City Stays</a></li>',
                "where-to-stay-in-da-lat"
            )
        ],
        "ho-chi-minh-city-to-da-lat-transport": [
            (
                '<li><a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide</a></li>',
                '<li><a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide</a></li>\n        <li><a href="/destinations/where-to-stay-in-da-lat/">Where to Stay in Da Lat</a></li>',
                "where-to-stay-in-da-lat"
            )
        ],
        
        # --- Pillar 2 remaining: best-things-to-do-in-da-lat ---
        "vietnam-coffee-guide": [
            (
                'explore the mountain roasters in our <a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide</a>, and review dining manners',
                'plan highland highlights with our <a href="/destinations/best-things-to-do-in-da-lat/">Best Things to Do in Da Lat</a> guide, explore mountain roasters in the <a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide</a>, and review dining manners',
                "best-things-to-do-in-da-lat"
            )
        ],
        "da-lat-to-nha-trang-transport": [
            (
                '<li><a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide: Highlands, Cafes &amp; Countryside</a></li>',
                '<li><a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide: Highlands, Cafes &amp; Countryside</a></li>\n<li><a href="/destinations/best-things-to-do-in-da-lat/">Best Things to Do in Da Lat: Waterfalls, Pine Treks &amp; Highlights</a></li>',
                "best-things-to-do-in-da-lat"
            )
        ],
        "vietnam-in-october": [
            (
                '<li><a href="/plan/vietnam-in-september/">Vietnam in September</a></li>',
                '<li><a href="/plan/vietnam-in-september/">Vietnam in September</a></li>\n        <li><a href="/destinations/best-things-to-do-in-da-lat/">Best Things to Do in Da Lat</a></li>',
                "best-things-to-do-in-da-lat"
            )
        ],

        # --- Pillar 3: best-things-to-do-in-nha-trang ---
        "nha-trang-travel-guide": [
            (
                'the beach fork in <a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a>.',
                'the beach fork in <a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a>, explore top coastal activities with our <a href="/destinations/best-things-to-do-in-nha-trang/">Best Things to Do in Nha Trang</a> guide,',
                "best-things-to-do-in-nha-trang"
            )
        ],
        "where-to-stay-in-nha-trang": [
            (
                '<li><a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a></li>',
                '<li><a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a></li>\n        <li><a href="/destinations/best-things-to-do-in-nha-trang/">Best Things to Do in Nha Trang</a></li>',
                "best-things-to-do-in-nha-trang"
            )
        ],
        "best-beaches-in-vietnam": [
            (
                'our hotel guide on <a href="/destinations/where-to-stay-in-nha-trang/">Where to Stay in Nha Trang</a>,',
                'our hotel guide on <a href="/destinations/where-to-stay-in-nha-trang/">Where to Stay in Nha Trang</a>, our activity guide on <a href="/destinations/best-things-to-do-in-nha-trang/">Best Things to Do in Nha Trang</a>,',
                "best-things-to-do-in-nha-trang"
            )
        ],

        # --- Pillar 4: best-things-to-do-in-phu-quoc ---
        "phu-quoc-travel-guide": [
            (
                '<a href="/plan/ho-chi-minh-city-to-phu-quoc-transport/">Ho Chi Minh City to Phu Quoc Transport</a>,',
                '<a href="/plan/ho-chi-minh-city-to-phu-quoc-transport/">Ho Chi Minh City to Phu Quoc Transport</a>, our activity guide on <a href="/destinations/best-things-to-do-in-phu-quoc/">Best Things to Do in Phu Quoc</a>,',
                "best-things-to-do-in-phu-quoc"
            )
        ],
        "where-to-stay-in-phu-quoc": [
            (
                '<li><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a></li>',
                '<li><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a></li>\n        <li><a href="/destinations/best-things-to-do-in-phu-quoc/">Best Things to Do in Phu Quoc</a></li>',
                "best-things-to-do-in-phu-quoc"
            )
        ],
        "phu-quoc-beaches-guide": [
            (
                '<li><a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc: Best Beaches, Areas &amp; Resorts</a></li>',
                '<li><a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc: Best Beaches, Areas &amp; Resorts</a></li>\n<li><a href="/destinations/best-things-to-do-in-phu-quoc/">Best Things to Do in Phu Quoc: Cable Cars, Islands &amp; Sunsets</a></li>',
                "best-things-to-do-in-phu-quoc"
            )
        ],
        "ho-chi-minh-city-to-phu-quoc-transport": [
            (
                '<li><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a></li>',
                '<li><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a></li>\n        <li><a href="/destinations/best-things-to-do-in-phu-quoc/">Best Things to Do in Phu Quoc</a></li>',
                "best-things-to-do-in-phu-quoc"
            )
        ],

        # --- Pillar 5: where-to-stay-in-phong-nha ---
        "phong-nha-travel-guide": [
            (
                '<li><span class="vg-related-route-step">07</span><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a><span class="vg-related-route-note">Compare easier karst scenery with the deeper cave commitment.</span></li>\n</ol>',
                '<li><span class="vg-related-route-step">07</span><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a><span class="vg-related-route-note">Compare easier karst scenery with the deeper cave commitment.</span></li>\n<li><span class="vg-related-route-step">08</span><a href="/destinations/where-to-stay-in-phong-nha/">Where to Stay in Phong Nha</a><span class="vg-related-route-note">Choose riverside farmstays, eco-lodges, or Son Trach village hostels.</span></li>\n</ol>',
                "where-to-stay-in-phong-nha"
            )
        ],
        "phong-nha-cave-treks": [
            (
                '(for onward travel south to Hue, consult our <a href="/plan/phong-nha-to-hue-transport/">Phong Nha to Hue transport guide</a>).',
                '(for onward travel south to Hue, consult our <a href="/plan/phong-nha-to-hue-transport/">Phong Nha to Hue transport guide</a>; select your base with our <a href="/destinations/where-to-stay-in-phong-nha/">Where to Stay in Phong Nha guide</a>).',
                "where-to-stay-in-phong-nha"
            )
        ],
        "hanoi-to-phong-nha-transport": [
            (
                '<li><a href="/destinations/phong-nha-travel-guide/">Phong Nha Travel Guide</a>: Figure out where to stay and what to eat once you arrive.</li>',
                '<li><a href="/destinations/phong-nha-travel-guide/">Phong Nha Travel Guide</a>: Figure out where to stay and what to eat once you arrive.</li>\n    <li><a href="/destinations/where-to-stay-in-phong-nha/">Where to Stay in Phong Nha</a>: Pick the ideal base between Son Trach village hostels and Bong Lai valley farmstays.</li>',
                "where-to-stay-in-phong-nha"
            )
        ],
        "phong-nha-to-hue-transport": [
            (
                '<li><a href="/destinations/phong-nha-travel-guide/">Phong Nha Travel Guide: National Park, Caves &amp; Trekking</a></li>',
                '<li><a href="/destinations/phong-nha-travel-guide/">Phong Nha Travel Guide: National Park, Caves &amp; Trekking</a></li>\n<li><a href="/destinations/where-to-stay-in-phong-nha/">Where to Stay in Phong Nha: Farmstays, Eco-Lodges &amp; Hostels</a></li>',
                "where-to-stay-in-phong-nha"
            )
        ],

        # --- Pillar 6: hue-to-hoi-an-transport ---
        "hue-imperial-city-guide": [
            (
                '<li><span class="vg-related-route-step">08</span><a href="/destinations/hue-street-food-guide/">Hue Street Food Guide</a><span class="vg-related-route-note">Taste authentic Bun Bo Hue, royal steamed rice cakes, and Con Hen clam rice.</span></li>\n</ol>',
                '<li><span class="vg-related-route-step">08</span><a href="/destinations/hue-street-food-guide/">Hue Street Food Guide</a><span class="vg-related-route-note">Taste authentic Bun Bo Hue, royal steamed rice cakes, and Con Hen clam rice.</span></li>\n<li><span class="vg-related-route-step">09</span><a href="/plan/hue-to-hoi-an-transport/">Hue to Hoi An Transport</a><span class="vg-related-route-note">Compare Hai Van Pass scenic train, luxury limousine bus, and private car stops.</span></li>\n</ol>',
                "hue-to-hoi-an-transport"
            )
        ],
        "hoi-an-ancient-town-guide": [
            (
                '<li><span class="vg-related-route-step">07</span><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a><span class="vg-related-route-note">Test whether Hoi An gets enough nights in a first route.</span></li>\n</ol>',
                '<li><span class="vg-related-route-step">07</span><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a><span class="vg-related-route-note">Test whether Hoi An gets enough nights in a first route.</span></li>\n<li><span class="vg-related-route-step">08</span><a href="/plan/hue-to-hoi-an-transport/">Hue to Hoi An Transport</a><span class="vg-related-route-note">Navigate coastal transfers via Hai Van Pass, heritage train, or private car.</span></li>\n</ol>',
                "hue-to-hoi-an-transport"
            )
        ],
        "da-nang-to-hue-train-vs-car": [
            (
                '<li><a href="/destinations/hoi-an-ancient-town-guide/">Hoi An Ancient Town Guide</a> &mdash; discover the UNESCO lantern streets and tailoring heritage.</li>',
                '<li><a href="/destinations/hoi-an-ancient-town-guide/">Hoi An Ancient Town Guide</a> &mdash; discover the UNESCO lantern streets and tailoring heritage.</li>\n<li><a href="/plan/hue-to-hoi-an-transport/">Hue to Hoi An Transport Guide</a> &mdash; compare Hai Van Pass train, tourist buses, and direct private cars.</li>',
                "hue-to-hoi-an-transport"
            )
        ],
        "da-nang-airport-to-hoi-an": [
            (
                'compare urban beach life with our <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a> breakdown,',
                'compare urban beach life with our <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a> breakdown, plan your northern transfer via our <a href="/plan/hue-to-hoi-an-transport/">Hue to Hoi An Transport</a> guide,',
                "hue-to-hoi-an-transport"
            )
        ],

        # --- Pillar 3 & Pillar 7 shared: mui-ne-vs-nha-trang ---
        "mui-ne-vs-nha-trang": [
            (
                'and <a href="/destinations/where-to-stay-in-nha-trang/">Where to Stay in Nha Trang</a> for the active city-beach base,',
                'our activity guide on <a href="/destinations/best-things-to-do-in-nha-trang/">Best Things to Do in Nha Trang</a>, and <a href="/destinations/where-to-stay-in-nha-trang/">Where to Stay in Nha Trang</a> for the active city-beach base,',
                "best-things-to-do-in-nha-trang"
            ),
            (
                '<a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> for movement, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>',
                '<a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> for movement, our transit guide on <a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>',
                "ho-chi-minh-city-to-mui-ne-transport"
            )
        ],

        # --- Pillar 7: ho-chi-minh-city-to-mui-ne-transport ---
        "ho-chi-minh-city-travel-guide": [
            (
                '<a href="/destinations/mekong-delta-travel-guide/">Mekong Delta Travel Guide</a>,',
                '<a href="/destinations/mekong-delta-travel-guide/">Mekong Delta Travel Guide</a>, plan coastal escapes with our <a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a> guide,',
                "ho-chi-minh-city-to-mui-ne-transport"
            )
        ],
        "transport-within-vietnam": [
            (
                'our route guide on <a href="/plan/ho-chi-minh-city-to-da-lat-transport/">Ho Chi Minh City to Da Lat transport</a>,',
                'our route guide on <a href="/plan/ho-chi-minh-city-to-da-lat-transport/">Ho Chi Minh City to Da Lat transport</a>, our transit breakdown for <a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne transport</a>,',
                "ho-chi-minh-city-to-mui-ne-transport"
            )
        ],
        "best-day-trips-from-ho-chi-minh-city": [
            (
                '<a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>,',
                '<a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, our seaside transfer guide on <a href="/plan/ho-chi-minh-city-to-mui-ne-transport/">Ho Chi Minh City to Mui Ne Transport</a>,',
                "ho-chi-minh-city-to-mui-ne-transport"
            )
        ],
    }

    # Verify and apply replacements
    updated_posts = {}
    total_links_verified = 0

    print("=== Validating 28 Inbound Link Operations ===")
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
            # Count how many links added
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
    
    out_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage69_mesh_ops.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(updated_posts, f, ensure_ascii=False, indent=2)
    print(f"Exported {len(updated_posts)} updated posts to {out_file}")

if __name__ == '__main__':
    main()

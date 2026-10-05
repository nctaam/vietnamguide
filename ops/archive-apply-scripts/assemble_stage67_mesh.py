# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 67: Exact Mesh Assembler and Validator.
Constructs 28 surgical internal linking replacements across 27 posts.
"""

import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

base_dir = os.path.dirname(os.path.abspath(__file__))
sources = json.load(open(os.path.join(base_dir, 'stage67_mesh_sources.json'), 'r', encoding='utf-8'))

OPERATIONS = [
    {
        "post_id": 792,
        "slug": "vietnam-in-july",
        "pillar": "vietnam-in-august",
        "target": "<li><a href=\"/plan/vietnam-in-june/\">Vietnam in June Weather &amp; Route Guide</a> &mdash; early summer conditions, harvest timelines, and coastal routes.</li>",
        "replacement": "<li><a href=\"/plan/vietnam-in-august/\">Vietnam in August Weather &amp; Route Guide</a> &mdash; late summer transitions, central coast beach days, and northern harvest prep.</li>\n<li><a href=\"/plan/vietnam-in-june/\">Vietnam in June Weather &amp; Route Guide</a> &mdash; early summer conditions, harvest timelines, and coastal routes.</li>"
    },
    {
        "post_id": 14,
        "slug": "best-time-to-visit-vietnam",
        "pillar": "vietnam-in-august",
        "target": "March through August (humidity ~75%, daily highs 32\u201335\u00b0C).</li>",
        "replacement": "March through August (humidity ~75%, daily highs 32\u201335\u00b0C, though see our <a href=\"/plan/vietnam-in-august/\">Vietnam in August guide</a> for late-summer rain patterns).</li>"
    },
    {
        "post_id": 501,
        "slug": "da-nang-beaches-guide",
        "pillar": "vietnam-in-august",
        "target": "<li><span class=\"vg-related-route-step\">01</span><a href=\"/destinations/da-nang-travel-guide/\">Da Nang Travel Guide</a>",
        "replacement": "<li><span class=\"vg-related-route-step\">00</span><a href=\"/plan/vietnam-in-august/\">Vietnam in August Travel Guide</a><span class=\"vg-related-route-note\">Check late summer weather patterns before booking August beach days.</span></li>\n<li><span class=\"vg-related-route-step\">01</span><a href=\"/destinations/da-nang-travel-guide/\">Da Nang Travel Guide</a>"
    },
    {
        "post_id": 757,
        "slug": "vietnam-in-june",
        "pillar": "vietnam-in-august",
        "target": "<li><a href=\"/plan/vietnam-in-july/\">Vietnam in July: Weather, Beach Sun &amp; Rain Patterns</a></li>",
        "replacement": "<li><a href=\"/plan/vietnam-in-july/\">Vietnam in July: Weather, Beach Sun &amp; Rain Patterns</a></li>\n<li><a href=\"/plan/vietnam-in-august/\">Vietnam in August: Late Summer Heat &amp; Changing Monsoons</a></li>"
    },
    {
        "post_id": 500,
        "slug": "hue-imperial-city-guide",
        "pillar": "where-to-stay-in-hue",
        "target": "<li><span class=\"vg-related-route-step\">01</span><a href=\"/destinations/best-things-to-do-in-hue/\">Best Things to Do in Hue</a>",
        "replacement": "<li><span class=\"vg-related-route-step\">00</span><a href=\"/destinations/where-to-stay-in-hue/\">Where to Stay in Hue</a><span class=\"vg-related-route-note\">Choose a hotel near the Citadel or along the Perfume River.</span></li>\n<li><span class=\"vg-related-route-step\">01</span><a href=\"/destinations/best-things-to-do-in-hue/\">Best Things to Do in Hue</a>"
    },
    {
        "post_id": 791,
        "slug": "hue-street-food-guide",
        "pillar": "where-to-stay-in-hue",
        "target": "<li><a href=\"/destinations/danang-to-hue-train-scenic-railway-guide/\">Da Nang to Hue Train Scenic Railway Guide</a>",
        "replacement": "<li><a href=\"/destinations/where-to-stay-in-hue/\">Where to Stay in Hue</a> &mdash; find walking-distance bases to the best evening street food stalls.</li>\n<li><a href=\"/destinations/danang-to-hue-train-scenic-railway-guide/\">Da Nang to Hue Train Scenic Railway Guide</a>"
    },
    {
        "post_id": 184,
        "slug": "best-things-to-do-in-hue",
        "pillar": "where-to-stay-in-hue",
        "target": "text, and a sense of place no beach base can replace.</td>",
        "replacement": "text, and a sense of place no beach base can replace (see our <a href=\"/destinations/where-to-stay-in-hue/\">Where to Stay in Hue guide</a> for base planning).</td>"
    },
    {
        "post_id": 684,
        "slug": "da-nang-to-hue-train-vs-car",
        "pillar": "where-to-stay-in-hue",
        "target": "<li><a href=\"/destinations/hue-street-food-guide/\">Hue Street Food Guide</a>",
        "replacement": "<li><a href=\"/destinations/where-to-stay-in-hue/\">Where to Stay in Hue</a> &mdash; riverfront hotels vs Citadel proximity.</li>\n<li><a href=\"/destinations/hue-street-food-guide/\">Hue Street Food Guide</a>"
    },
    {
        "post_id": 265,
        "slug": "mekong-delta-travel-guide",
        "pillar": "mekong-delta-floating-markets-guide",
        "target": "<tr><td data-label=\"Trip length\">7 days</td>",
        "replacement": "<tr><td data-label=\"Trip length\">Focus on commerce?</td><td data-label=\"Best Mekong answer\">Prioritize morning markets.</td><td data-label=\"What to cut first\">Skip generic land tours and focus on authentic river trade.</td><td data-label=\"Related guide\"><a href=\"/destinations/mekong-delta-floating-markets-guide/\">Mekong Delta Floating Markets Guide</a></td></tr>\n<tr><td data-label=\"Trip length\">7 days</td>"
    },
    {
        "post_id": 502,
        "slug": "mekong-delta-overnight-vs-day-trip",
        "pillar": "mekong-delta-floating-markets-guide",
        "target": "<li><span class=\"vg-related-route-step\">01</span><a href=\"/destinations/mekong-delta-travel-guide/\">Mekong Delta Travel Guide</a>",
        "replacement": "<li><span class=\"vg-related-route-step\">00</span><a href=\"/destinations/mekong-delta-floating-markets-guide/\">Mekong Delta Floating Markets</a><span class=\"vg-related-route-note\">Determine if early morning river markets justify the overnight stay.</span></li>\n<li><span class=\"vg-related-route-step\">01</span><a href=\"/destinations/mekong-delta-travel-guide/\">Mekong Delta Travel Guide</a>"
    },
    {
        "post_id": 262,
        "slug": "ho-chi-minh-city-travel-guide",
        "pillar": "mekong-delta-floating-markets-guide",
        "target": "Mekong Delta overnight vs day trip breakdown</a>). It is strongest when the south",
        "replacement": "Mekong Delta overnight vs day trip breakdown</a>, and explore our <a href=\"/destinations/mekong-delta-floating-markets-guide/\">floating markets guide</a>). It is strongest when the south"
    },
    {
        "post_id": 268,
        "slug": "best-day-trips-from-ho-chi-minh-city",
        "pillar": "mekong-delta-floating-markets-guide",
        "target": "exhausting early-morning highway travel.</p></details>",
        "replacement": "exhausting early-morning highway travel (see our <a href=\"/destinations/mekong-delta-floating-markets-guide/\">Mekong Delta Floating Markets guide</a>).</p></details>"
    },
    {
        "post_id": 503,
        "slug": "phong-nha-travel-guide",
        "pillar": "hanoi-to-phong-nha-transport",
        "target": "<li><span class=\"vg-related-route-step\">01</span><a href=\"/destinations/unesco-heritage-sites-vietnam/\">UNESCO Heritage Sites in Vietnam</a>",
        "replacement": "<li><span class=\"vg-related-route-step\">00</span><a href=\"/plan/hanoi-to-phong-nha-transport/\">Hanoi to Phong Nha Transport</a><span class=\"vg-related-route-note\">Plan the long southbound sleeper bus or train journey.</span></li>\n<li><span class=\"vg-related-route-step\">01</span><a href=\"/destinations/unesco-heritage-sites-vietnam/\">UNESCO Heritage Sites in Vietnam</a>"
    },
    {
        "post_id": 629,
        "slug": "phong-nha-cave-treks",
        "pillar": "hanoi-to-phong-nha-transport",
        "target": "hin hours.</p>\n</div>\n<div class=\"wp-block-details\">\n<summary><strong>How do I reach Phong Nha from Hanoi",
        "replacement": "hin hours.</p>\n</div>\n<div class=\"wp-block-details\">\n<summary><strong>How do I reach Phong Nha from Hanoi (see our <a href=\"/plan/hanoi-to-phong-nha-transport/\">Hanoi to Phong Nha transport guide</a>) or Da Nang?</strong></summary>\n<p>Take the overnight sleeper train to Dong Hoi"
    },
    {
        "post_id": 755,
        "slug": "phong-nha-to-hue-transport",
        "pillar": "hanoi-to-phong-nha-transport",
        "target": "<li><a href=\"/destinations/phong-nha-travel-guide/\">Phong Nha Travel Guide: National Park, Caves &amp; Trekking</a></li>",
        "replacement": "<li><a href=\"/plan/hanoi-to-phong-nha-transport/\">Hanoi to Phong Nha Transport: Sleeper Buses &amp; Dong Hoi Trains</a></li>\n<li><a href=\"/destinations/phong-nha-travel-guide/\">Phong Nha Travel Guide: National Park, Caves &amp; Trekking</a></li>"
    },
    {
        "post_id": 613,
        "slug": "vietnam-train-travel",
        "pillar": "hanoi-to-phong-nha-transport",
        "target": "<li><a href=\"/plan/transport-within-vietnam/\">Transport Within Vietnam: Trains vs Flights vs VIP Sleeper Buses</a></li>",
        "replacement": "<li><a href=\"/plan/hanoi-to-phong-nha-transport/\">Hanoi to Phong Nha Transport: Dong Hoi Sleeper Trains</a></li>\n<li><a href=\"/plan/transport-within-vietnam/\">Transport Within Vietnam: Trains vs Flights vs VIP Sleeper Buses</a></li>"
    },
    {
        "post_id": 611,
        "slug": "da-lat-travel-guide",
        "pillar": "da-lat-coffee-farms-guide",
        "target": "<li><a href=\"/destinations/nha-trang-travel-guide/\">Nha Trang Travel Guide: Coastal Beaches &amp; Khanh Le Pass Connection</a></li>",
        "replacement": "<li><a href=\"/destinations/da-lat-coffee-farms-guide/\">Da Lat Coffee Farms Guide: Plantations, Tours &amp; Tasting</a></li>\n<li><a href=\"/destinations/nha-trang-travel-guide/\">Nha Trang Travel Guide: Coastal Beaches &amp; Khanh Le Pass Connection</a></li>"
    },
    {
        "post_id": 753,
        "slug": "da-lat-waterfalls-guide",
        "pillar": "da-lat-coffee-farms-guide",
        "target": "<li><a href=\"/destinations/da-lat-travel-guide/\">Da Lat Travel Guide: Highlands, Cafes &amp; Countryside</a></li>",
        "replacement": "<li><a href=\"/destinations/da-lat-coffee-farms-guide/\">Da Lat Coffee Farms Guide: Plantations, Tours &amp; Tasting</a></li>\n<li><a href=\"/destinations/da-lat-travel-guide/\">Da Lat Travel Guide: Highlands, Cafes &amp; Countryside</a></li>"
    },
    {
        "post_id": 756,
        "slug": "da-lat-to-nha-trang-transport",
        "pillar": "da-lat-coffee-farms-guide",
        "target": "<li><a href=\"/destinations/da-lat-travel-guide/\">Da Lat Travel Guide: Highlands, Cafes &amp; Countryside</a></li>",
        "replacement": "<li><a href=\"/destinations/da-lat-coffee-farms-guide/\">Da Lat Coffee Farms Guide: Arabica Plantations &amp; Tasting</a></li>\n<li><a href=\"/destinations/da-lat-travel-guide/\">Da Lat Travel Guide: Highlands, Cafes &amp; Countryside</a></li>"
    },
    {
        "post_id": 649,
        "slug": "vietnam-coffee-guide",
        "pillar": "da-lat-coffee-farms-guide",
        "target": "Da Lat's high-altitude specialty Arabica farms",
        "replacement": "Da Lat's high-altitude specialty Arabica farms (explored in our <a href=\"/destinations/da-lat-coffee-farms-guide/\">Da Lat Coffee Farms Guide</a>)"
    },
    {
        "post_id": 499,
        "slug": "hoi-an-ancient-town-guide",
        "pillar": "hoi-an-tailoring-guide",
        "target": "<li><span class=\"vg-related-route-step\">01</span><a href=\"/destinations/best-things-to-do-in-hoi-an/\">Best Things to Do in Hoi An</a>",
        "replacement": "<li><span class=\"vg-related-route-step\">00</span><a href=\"/destinations/hoi-an-tailoring-guide/\">Hoi An Tailoring Guide</a><span class=\"vg-related-route-note\">Plan dressmaking and bespoke suits while exploring town.</span></li>\n<li><span class=\"vg-related-route-step\">01</span><a href=\"/destinations/best-things-to-do-in-hoi-an/\">Best Things to Do in Hoi An</a>"
    },
    {
        "post_id": 178,
        "slug": "best-things-to-do-in-hoi-an",
        "pillar": "hoi-an-tailoring-guide",
        "target": "Use nights as the unit, not checkmarks. For an itemized breakdown of heritage passes, bike rentals, tailor deposits, and dining run rates",
        "replacement": "Use nights as the unit, not checkmarks. For an itemized breakdown of heritage passes, bike rentals, tailor deposits (see our <a href=\"/destinations/hoi-an-tailoring-guide/\">Hoi An Tailoring Guide</a>), and dining run rates"
    },
    {
        "post_id": 716,
        "slug": "hoi-an-street-food-guide",
        "pillar": "hoi-an-tailoring-guide",
        "target": "<li><a href=\"/destinations/hue-street-food-guide/\">Hue Street Food Guide</a>",
        "replacement": "<li><a href=\"/destinations/hoi-an-tailoring-guide/\">Hoi An Tailoring Guide</a> &mdash; fit bespoke clothing orders around your culinary walking routes.</li>\n<li><a href=\"/destinations/hue-street-food-guide/\">Hue Street Food Guide</a>"
    },
    {
        "post_id": 209,
        "slug": "da-nang-vs-hoi-an",
        "pillar": "hoi-an-tailoring-guide",
        "target": "tailoring, My Son, or An Bang recovery are the point.</li>",
        "replacement": "<a href=\"/destinations/hoi-an-tailoring-guide/\">tailoring</a>, My Son, or An Bang recovery are the point.</li>"
    },
    {
        "post_id": 651,
        "slug": "vietnam-in-november",
        "pillar": "vietnam-in-september",
        "target": "September 22, 2026</p>",
        "replacement": "September 22, 2026</p>\n<p><em>Planning an earlier trip? See our <a href=\"/plan/vietnam-in-september/\">Vietnam in September Weather &amp; Route Guide</a>.</em></p>"
    },
    {
        "post_id": 528,
        "slug": "mu-cang-chai-travel-guide",
        "pillar": "vietnam-in-september",
        "target": "typically September 20 to October 5)",
        "replacement": "typically September 20 to October 5, see our <a href=\"/plan/vietnam-in-september/\">Vietnam in September guide</a>)"
    },
    {
        "post_id": 518,
        "slug": "sapa-travel-guide",
        "pillar": "vietnam-in-september",
        "target": "September 22, 2026</p>",
        "replacement": "September 22, 2026</p>\n<p><em>Traveling earlier? Check our <a href=\"/plan/vietnam-in-september/\">Vietnam in September</a> guide for harvest conditions.</em></p>"
    },
    {
        "post_id": 792,
        "slug": "vietnam-in-july",
        "pillar": "vietnam-in-september",
        "target": "<li><a href=\"/plan/vietnam-in-june/\">Vietnam in June Weather &amp; Route Guide</a>",
        "replacement": "<li><a href=\"/plan/vietnam-in-september/\">Vietnam in September Weather &amp; Route Guide</a> &mdash; late summer autumn transitions and harvest season in the north.</li>\n<li><a href=\"/plan/vietnam-in-june/\">Vietnam in June Weather &amp; Route Guide</a>"
    }
]

def main():
    print("=" * 70)
    print("VIETNAMGUIDE STAGE 67: ASSEMBLING & VALIDATING INTERNAL LINK MESH")
    print("=" * 70)
    print(f"Total operations defined: {len(OPERATIONS)}")

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

    out_payload = []
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

    ops_file = os.path.join(base_dir, 'stage67_mesh_ops.json')
    with open(ops_file, 'w', encoding='utf-8') as f:
        json.dump(out_payload, f, ensure_ascii=False, indent=2)

    print(f"\nSuccessfully verified and compiled {len(out_payload)} modified posts into {ops_file}")
    print("=" * 70)

if __name__ == '__main__':
    main()

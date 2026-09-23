# -*- coding: utf-8 -*-
"""
Update ops/meta_inventory.json with Stage 70 pillars (IDs 921-927)
"""

import json
import os

INVENTORY_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'meta_inventory.json')

STAGE70_ENTRIES = [
    {
        "ID": 921,
        "post_name": "best-things-to-do-in-sapa",
        "post_title": "Best Things to Do in Sapa (2026): Treks, Views & Honest Choices",
        "meta_title": "Best Things to Do in Sapa (2026): Treks, Views & Honest Choices",
        "meta_desc": "Plan what to do in Sapa without tour fluff: Fansipan cable car, Muong Hoa treks, waterfalls, markets, and weather strategies for northern Vietnam's highlands.",
        "post_modified": "2026-09-23 11:20:00",
        "parent": 7,
        "url": "/destinations/best-things-to-do-in-sapa/"
    },
    {
        "ID": 922,
        "post_name": "best-things-to-do-in-ninh-binh",
        "post_title": "Best Things to Do in Ninh Binh (2026): Boats, Karsts & Routes",
        "meta_desc": "What to do in Ninh Binh without wasted hours: Trang An vs Tam Coc boats, Hang Mua viewpoint climb, cycling backroads, temples, and realistic day routing.",
        "post_modified": "2026-09-23 11:20:00",
        "parent": 7,
        "url": "/destinations/best-things-to-do-in-ninh-binh/"
    },
    {
        "ID": 923,
        "post_name": "best-things-to-do-in-ha-long-bay",
        "post_title": "Best Things to Do in Ha Long Bay (2026): Cruises, Caves & Kayaks",
        "meta_desc": "Make the most of Ha Long Bay: overnight cruises vs day trips, Sung Sot cave, Ti Top Island viewpoint, kayaking karst arches, and weather season tradeoffs.",
        "post_modified": "2026-09-23 11:20:00",
        "parent": 7,
        "url": "/destinations/best-things-to-do-in-ha-long-bay/"
    },
    {
        "ID": 924,
        "post_name": "best-things-to-do-in-mui-ne",
        "post_title": "Best Things to Do in Mui Ne (2026): Dunes, Kitesurfing & Coast",
        "meta_desc": "Plan Mui Ne activities with realistic pacing: White & Red Sand Dunes, Fairy Stream walk, kitesurfing seasons, fishing harbor, and coastal day planning.",
        "post_modified": "2026-09-23 11:20:00",
        "parent": 7,
        "url": "/destinations/best-things-to-do-in-mui-ne/"
    },
    {
        "ID": 925,
        "post_name": "where-to-stay-in-mui-ne",
        "post_title": "Where to Stay in Mui Ne (2026): Best Areas & Resort Strips",
        "meta_desc": "Where to stay in Mui Ne: Nguyen Dinh Chieu resort strip vs quiet Ham Tien, kitesurfing beachfronts, budget guesthouses, and honest coastal base tradeoffs.",
        "post_modified": "2026-09-23 11:20:00",
        "parent": 7,
        "url": "/destinations/where-to-stay-in-mui-ne/"
    },
    {
        "ID": 926,
        "post_name": "best-things-to-do-in-quy-nhon",
        "post_title": "Best Things to Do in Quy Nhon (2026): Cliffs, Beaches & Relics",
        "meta_desc": "Explore Quy Nhon without crowds: Ky Co beach, Eo Gio coastal boardwalk, Cham towers, fishing villages, and seafood street dinners in central Vietnam.",
        "post_modified": "2026-09-23 11:20:00",
        "parent": 7,
        "url": "/destinations/best-things-to-do-in-quy-nhon/"
    },
    {
        "ID": 927,
        "post_name": "ho-chi-minh-city-to-can-tho-transport",
        "post_title": "Ho Chi Minh City to Can Tho (2026): Bus, Limousine & Route Guide",
        "meta_desc": "How to travel from Ho Chi Minh City to Can Tho: express limousines, sleeper buses, private cars, highway transit times, booking tips, and station arrivals.",
        "post_modified": "2026-09-23 11:20:00",
        "parent": 6,
        "url": "/plan/ho-chi-minh-city-to-can-tho-transport/"
    }
]

def main():
    with open(INVENTORY_PATH, 'r', encoding='utf-8') as f:
        inventory = json.load(f)
    
    print(f"Current inventory count: {len(inventory)}")
    
    existing_ids = {item['ID'] for item in inventory}
    existing_slugs = {item['post_name'] for item in inventory}
    
    added = 0
    for entry in STAGE70_ENTRIES:
        if entry['ID'] in existing_ids or entry['post_name'] in existing_slugs:
            print(f"  [SKIP] Entry already exists: {entry['post_name']} (ID {entry['ID']})")
        else:
            inventory.append(entry)
            added += 1
            print(f"  [ADD] Added entry: {entry['post_name']} (ID {entry['ID']})")
            
    # Sort inventory by ID
    inventory.sort(key=lambda x: int(x['ID']))
    
    with open(INVENTORY_PATH, 'w', encoding='utf-8') as f:
        json.dump(inventory, f, ensure_ascii=False, indent=2)
        
    print(f"\nUpdated inventory count: {len(inventory)} (+{added} added)")

if __name__ == '__main__':
    main()

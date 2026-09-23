# -*- coding: utf-8 -*-
"""
Update ops/meta_inventory.json with Stage 71 pillars (IDs 964-970)
"""

import json
import os

INVENTORY_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'meta_inventory.json')

STAGE71_ENTRIES = [
    {
        "ID": 964,
        "post_name": "where-to-stay-in-ha-long-bay",
        "post_title": "Where to Stay in Ha Long Bay (2026): Best Areas & Bases",
        "meta_title": "Where to Stay in Ha Long Bay (2026): Best Areas & Bases",
        "meta_desc": "Where to stay in Ha Long Bay: Bai Chay hotel strip vs Tuan Chau marina resorts, Hon Gai local living, or overnight cruise staging with 2026 nightly rates.",
        "post_modified": "2026-09-23 12:25:00",
        "parent": 7,
        "url": "/destinations/where-to-stay-in-ha-long-bay/"
    },
    {
        "ID": 965,
        "post_name": "where-to-stay-in-quy-nhon",
        "post_title": "Where to Stay in Quy Nhon (2026): Beach Areas & Resorts",
        "meta_title": "Where to Stay in Quy Nhon (2026): Beach Areas & Resorts",
        "meta_desc": "Decide where to stay in Quy Nhon: city beach hotels along Xuan Dieu, Bai Xep backpacker cove homestays, or luxury private bay resorts with 2026 VND prices.",
        "post_modified": "2026-09-23 12:25:00",
        "parent": 7,
        "url": "/destinations/where-to-stay-in-quy-nhon/"
    },
    {
        "ID": 966,
        "post_name": "where-to-stay-in-can-tho",
        "post_title": "Where to Stay in Can Tho (2026): Best Areas & Hotels",
        "meta_title": "Where to Stay in Can Tho (2026): Best Areas & Hotels",
        "meta_desc": "Where to stay in Can Tho for floating markets: Ninh Kieu Wharf walkability, Cai Rang river lodges, and luxury delta resorts with realistic 2026 VND rates.",
        "post_modified": "2026-09-23 12:25:00",
        "parent": 7,
        "url": "/destinations/where-to-stay-in-can-tho/"
    },
    {
        "ID": 967,
        "post_name": "where-to-stay-in-cat-ba",
        "post_title": "Where to Stay on Cat Ba Island (2026): Best Areas",
        "meta_title": "Where to Stay on Cat Ba Island (2026): Best Areas",
        "meta_desc": "Where to stay on Cat Ba Island: waterfront hotels in Cat Ba town, Cat Co cliff resorts, and quiet Viet Hai village eco-lodges with realistic 2026 rates.",
        "post_modified": "2026-09-23 12:25:00",
        "parent": 7,
        "url": "/destinations/where-to-stay-in-cat-ba/"
    },
    {
        "ID": 968,
        "post_name": "where-to-stay-in-ha-giang",
        "post_title": "Where to Stay in Ha Giang (2026): Loop Bases & Stays",
        "meta_title": "Where to Stay in Ha Giang (2026): Loop Bases & Stays",
        "meta_desc": "Where to stay in Ha Giang and the loop: city staging hostels, Dong Van old town homestays, Meo Vac clay lodges, and Du Gia waterfall stays in 2026.",
        "post_modified": "2026-09-23 12:25:00",
        "parent": 7,
        "url": "/destinations/where-to-stay-in-ha-giang/"
    },
    {
        "ID": 969,
        "post_name": "ho-chi-minh-city-in-2-days",
        "post_title": "Ho Chi Minh City in 2 Days (2026): 48-Hour Itinerary",
        "meta_title": "Ho Chi Minh City in 2 Days (2026): 48-Hour Itinerary",
        "meta_desc": "Plan Ho Chi Minh City in 2 days without wasted travel: District 1 colonial sights, War Remnants Museum, Cholon pagodas, rooftop sunset, and street food.",
        "post_modified": "2026-09-23 12:25:00",
        "parent": 8,
        "url": "/itineraries/ho-chi-minh-city-in-2-days/"
    },
    {
        "ID": 970,
        "post_name": "da-nang-to-nha-trang-transport",
        "post_title": "Da Nang to Nha Trang (2026): Train, Bus & Flight Guide",
        "meta_title": "Da Nang to Nha Trang (2026): Train, Bus & Flight Guide",
        "meta_desc": "How to travel from Da Nang to Nha Trang: Reunification Express sleeper train vs day seats, sleeper coaches, flights, and travel times with 2026 VND fares.",
        "post_modified": "2026-09-23 12:25:00",
        "parent": 6,
        "url": "/plan/da-nang-to-nha-trang-transport/"
    }
]

def main():
    with open(INVENTORY_PATH, 'r', encoding='utf-8') as f:
        inventory = json.load(f)
    
    print(f"Current inventory count: {len(inventory)}")
    
    existing_ids = {int(item['ID']) for item in inventory}
    existing_slugs = {item['post_name'] for item in inventory}
    
    added = 0
    for entry in STAGE71_ENTRIES:
        if entry['ID'] in existing_ids or entry['post_name'] in existing_slugs:
            print(f"  [SKIP] Entry already exists: {entry['post_name']} (ID {entry['ID']})")
        else:
            inventory.append(entry)
            added += 1
            print(f"  [ADD] Added entry: {entry['post_name']} (ID {entry['ID']})")
            
    # Sort inventory by ID numerically
    inventory.sort(key=lambda x: int(x['ID']))
    
    with open(INVENTORY_PATH, 'w', encoding='utf-8') as f:
        json.dump(inventory, f, ensure_ascii=False, indent=2)
        
    print(f"\nUpdated inventory count: {len(inventory)} (+{added} added)")

if __name__ == '__main__':
    main()

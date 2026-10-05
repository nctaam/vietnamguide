# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 84: Update Meta Inventory with all 7 Stage 84 pillars.
Synchronizes local ops/meta_inventory.json to 273 entries.
"""
import json
import os
import sys

ops_dir = os.path.dirname(os.path.abspath(__file__))
if ops_dir not in sys.path:
    sys.path.insert(0, ops_dir)

import content_where_to_stay_in_dong_van as p1
import content_where_to_stay_in_cao_bang as p2
import content_ha_long_to_ninh_binh_transport as p3
import content_dong_hoi_to_hue_transport as p4
import content_da_lat_to_mui_ne_transport as p5
import content_where_to_stay_in_vinh_hy as p6
import content_quy_nhon_to_nha_trang_transport as p7

def main():
    inv_file = os.path.join(ops_dir, 'meta_inventory.json')
    with open(inv_file, 'r', encoding='utf-8') as f:
        data = json.load(f)

    # Convert to dict by post_name
    inv_by_slug = {d['post_name']: d for d in data}
    print(f"Current inventory count: {len(data)}")

    pillars = [
        (1066, p1, 'destinations'),
        (1034, p2, 'destinations'),
        (1425, p3, 'plan'),
        (1426, p4, 'plan'),
        (1169, p5, 'plan'),
        (1428, p6, 'destinations'),
        (1429, p7, 'plan')
    ]

    for post_id, mod, parent_slug in pillars:
        entry = {
            "ID": post_id,
            "post_name": mod.SLUG,
            "post_title": mod.TITLE,
            "rm_title": mod.TITLE,
            "rm_desc": mod.META_DESC,
            "rm_keyword": mod.FOCUS_KEYWORD,
            "meta_title": mod.TITLE,
            "meta_desc": mod.META_DESC,
            "post_modified": "2026-09-26 10:45:00",
            "parent": mod.PARENT_ID,
            "url": f"/{parent_slug}/{mod.SLUG}/"
        }
        if mod.SLUG in inv_by_slug:
            print(f"  [UPDATED] {mod.SLUG} (ID: {post_id})")
            inv_by_slug[mod.SLUG].update(entry)
        else:
            print(f"  [ADDED] {mod.SLUG} (ID: {post_id})")
            inv_by_slug[mod.SLUG] = entry

    # Convert back to list preserving ID/URL order
    new_data = list(inv_by_slug.values())
    with open(inv_file, 'w', encoding='utf-8') as f:
        json.dump(new_data, f, ensure_ascii=False, indent=2)

    print(f"\nSuccessfully synced meta_inventory.json. Total count: {len(new_data)}")

if __name__ == '__main__':
    main()

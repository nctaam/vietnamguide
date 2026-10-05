# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 88: Update Meta Inventory with all 7 Stage 88 pillars.
Synchronizes local ops/meta_inventory.json from 294 to 301 entries.
"""
import json
import os
import sys

ops_dir = os.path.dirname(os.path.abspath(__file__))
if ops_dir not in sys.path:
    sys.path.insert(0, ops_dir)

import content_where_to_stay_in_bac_ha as p1
import content_where_to_stay_in_bao_lac as p2
import content_where_to_stay_in_bao_loc as p3
import content_sapa_to_bac_ha_transport as p4
import content_tran_de_to_con_dao_ferry as p5
import content_vung_tau_to_con_dao_ferry as p6
import content_pleiku_to_quy_nhon_transport as p7

def main():
    inv_file = os.path.join(ops_dir, 'meta_inventory.json')
    with open(inv_file, 'r', encoding='utf-8') as f:
        data = json.load(f)

    # Convert to dict by post_name
    inv_by_slug = {d['post_name']: d for d in data}
    print(f"Current inventory count: {len(data)}")

    pillars = [
        (1561, p1, 'destinations'),
        (1562, p2, 'destinations'),
        (1563, p3, 'destinations'),
        (1564, p4, 'plan'),
        (1565, p5, 'plan'),
        (1566, p6, 'plan'),
        (1567, p7, 'plan')
    ]

    for post_id, mod, parent_slug in pillars:
        entry = {
            "ID": str(post_id),
            "post_name": mod.SLUG,
            "post_title": mod.TITLE,
            "rm_title": mod.TITLE,
            "rm_desc": mod.META_DESC,
            "rm_keyword": mod.FOCUS_KEYWORD,
            "meta_title": mod.TITLE,
            "meta_desc": mod.META_DESC,
            "post_modified": "2026-09-26 19:30:00",
            "parent": mod.PARENT_ID,
            "url": f"/{parent_slug}/{mod.SLUG}/"
        }
        if mod.SLUG in inv_by_slug:
            print(f"  [UPDATED] {mod.SLUG} (ID: {post_id})")
            inv_by_slug[mod.SLUG].update(entry)
        else:
            print(f"  [ADDED] {mod.SLUG} (ID: {post_id})")
            inv_by_slug[mod.SLUG] = entry

    # Convert back to list preserving order
    new_data = list(inv_by_slug.values())
    with open(inv_file, 'w', encoding='utf-8') as f:
        json.dump(new_data, f, ensure_ascii=False, indent=2)

    print(f"\nSuccessfully synced meta_inventory.json. Total count: {len(new_data)}")

if __name__ == '__main__':
    main()

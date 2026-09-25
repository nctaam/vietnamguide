# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 81: Update Meta Inventory with 7 Newly Deployed Pillars
Expands inventory from 248 to 255 entries.
"""
import json
import os
import sys

ops_dir = os.path.dirname(os.path.abspath(__file__))
if ops_dir not in sys.path:
    sys.path.insert(0, ops_dir)

import content_ha_giang_to_cao_bang_transport as p1
import content_sapa_to_mu_cang_chai_transport as p2
import content_can_tho_to_rach_gia_transport as p3
import content_hue_to_dong_hoi_transport as p4
import content_da_lat_to_buon_ma_thuot_transport as p5
import content_where_to_stay_soc_trang as p6
import content_vietnam_domestic_flights_guide as p7

def main():
    inv_file = os.path.join(ops_dir, 'meta_inventory.json')
    with open(inv_file, 'r', encoding='utf-8') as f:
        data = json.load(f)

    existing_slugs = {d['post_name'] for d in data}
    print(f"Current inventory count: {len(data)}")

    new_pillars = [
        {
            "ID": 1310,
            "post_name": p1.SLUG,
            "post_title": p1.TITLE,
            "rm_title": p1.TITLE,
            "rm_desc": p1.META_DESC,
            "rm_keyword": p1.FOCUS_KEYWORD,
            "meta_title": p1.TITLE,
            "meta_desc": p1.META_DESC,
            "post_modified": "2026-09-25 14:00:00",
            "parent": p1.PARENT_ID,
            "url": f"/plan/{p1.SLUG}/"
        },
        {
            "ID": 1311,
            "post_name": p2.SLUG,
            "post_title": p2.TITLE,
            "rm_title": p2.TITLE,
            "rm_desc": p2.META_DESC,
            "rm_keyword": p2.FOCUS_KEYWORD,
            "meta_title": p2.TITLE,
            "meta_desc": p2.META_DESC,
            "post_modified": "2026-09-25 14:00:00",
            "parent": p2.PARENT_ID,
            "url": f"/plan/{p2.SLUG}/"
        },
        {
            "ID": 1312,
            "post_name": p3.SLUG,
            "post_title": p3.TITLE,
            "rm_title": p3.TITLE,
            "rm_desc": p3.META_DESC,
            "rm_keyword": p3.FOCUS_KEYWORD,
            "meta_title": p3.TITLE,
            "meta_desc": p3.META_DESC,
            "post_modified": "2026-09-25 14:00:00",
            "parent": p3.PARENT_ID,
            "url": f"/plan/{p3.SLUG}/"
        },
        {
            "ID": 1313,
            "post_name": p4.SLUG,
            "post_title": p4.TITLE,
            "rm_title": p4.TITLE,
            "rm_desc": p4.META_DESC,
            "rm_keyword": p4.FOCUS_KEYWORD,
            "meta_title": p4.TITLE,
            "meta_desc": p4.META_DESC,
            "post_modified": "2026-09-25 14:00:00",
            "parent": p4.PARENT_ID,
            "url": f"/plan/{p4.SLUG}/"
        },
        {
            "ID": 1314,
            "post_name": p5.SLUG,
            "post_title": p5.TITLE,
            "rm_title": p5.TITLE,
            "rm_desc": p5.META_DESC,
            "rm_keyword": p5.FOCUS_KEYWORD,
            "meta_title": p5.TITLE,
            "meta_desc": p5.META_DESC,
            "post_modified": "2026-09-25 14:00:00",
            "parent": p5.PARENT_ID,
            "url": f"/plan/{p5.SLUG}/"
        },
        {
            "ID": 1315,
            "post_name": p6.SLUG,
            "post_title": p6.TITLE,
            "rm_title": p6.TITLE,
            "rm_desc": p6.META_DESC,
            "rm_keyword": p6.FOCUS_KEYWORD,
            "meta_title": p6.TITLE,
            "meta_desc": p6.META_DESC,
            "post_modified": "2026-09-25 14:00:00",
            "parent": p6.PARENT_ID,
            "url": f"/destinations/{p6.SLUG}/"
        },
        {
            "ID": 1316,
            "post_name": p7.SLUG,
            "post_title": p7.TITLE,
            "rm_title": p7.TITLE,
            "rm_desc": p7.META_DESC,
            "rm_keyword": p7.FOCUS_KEYWORD,
            "meta_title": p7.TITLE,
            "meta_desc": p7.META_DESC,
            "post_modified": "2026-09-25 14:00:00",
            "parent": p7.PARENT_ID,
            "url": f"/plan/{p7.SLUG}/"
        }
    ]

    added = 0
    for np in new_pillars:
        if np['post_name'] not in existing_slugs:
            data.append(np)
            added += 1
            print(f"  [ADDED] {np['post_name']} (ID: {np['ID']})")
        else:
            print(f"  [EXISTS] {np['post_name']}")

    with open(inv_file, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    print(f"\nSuccessfully added {added} new pillars. Total inventory: {len(data)}")

if __name__ == '__main__':
    main()
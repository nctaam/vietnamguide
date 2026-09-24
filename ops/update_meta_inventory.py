# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 78: Update Meta Inventory with 7 Newly Deployed Pillars
Expands inventory from 227 to 234 entries.
"""
import json
import os
import sys

ops_dir = os.path.dirname(os.path.abspath(__file__))
if ops_dir not in sys.path:
    sys.path.insert(0, ops_dir)

import content_where_to_stay_kon_tum as p1
import content_buon_ma_thuot_to_pleiku_transport as p2
import content_where_to_stay_chau_doc as p3
import content_vietnam_to_cambodia_boat_guide as p4
import content_vietnam_night_train_safety_tips as p5
import content_where_to_stay_dien_bien_phu as p6
import content_hanoi_to_dien_bien_phu_transport as p7

def main():
    inv_file = os.path.join(ops_dir, 'meta_inventory.json')
    with open(inv_file, 'r', encoding='utf-8') as f:
        data = json.load(f)

    existing_slugs = {d['post_name'] for d in data}
    print(f"Current inventory count: {len(data)}")

    new_pillars = [
        {
            "ID": 1201,
            "post_name": p1.SLUG,
            "post_title": p1.TITLE,
            "rm_title": p1.TITLE,
            "rm_desc": p1.META_DESC,
            "rm_keyword": p1.FOCUS_KEYWORD,
            "meta_title": p1.TITLE,
            "meta_desc": p1.META_DESC,
            "post_modified": "2026-09-24 15:30:00",
            "parent": p1.PARENT_ID,
            "url": f"/destinations/{p1.SLUG}/"
        },
        {
            "ID": 1202,
            "post_name": p2.SLUG,
            "post_title": p2.TITLE,
            "rm_title": p2.TITLE,
            "rm_desc": p2.META_DESC,
            "rm_keyword": p2.FOCUS_KEYWORD,
            "meta_title": p2.TITLE,
            "meta_desc": p2.META_DESC,
            "post_modified": "2026-09-24 15:30:00",
            "parent": p2.PARENT_ID,
            "url": f"/plan/{p2.SLUG}/"
        },
        {
            "ID": 1203,
            "post_name": p3.SLUG,
            "post_title": p3.TITLE,
            "rm_title": p3.TITLE,
            "rm_desc": p3.META_DESC,
            "rm_keyword": p3.FOCUS_KEYWORD,
            "meta_title": p3.TITLE,
            "meta_desc": p3.META_DESC,
            "post_modified": "2026-09-24 15:30:00",
            "parent": p3.PARENT_ID,
            "url": f"/destinations/{p3.SLUG}/"
        },
        {
            "ID": 1204,
            "post_name": p4.SLUG,
            "post_title": p4.TITLE,
            "rm_title": p4.TITLE,
            "rm_desc": p4.META_DESC,
            "rm_keyword": p4.FOCUS_KEYWORD,
            "meta_title": p4.TITLE,
            "meta_desc": p4.META_DESC,
            "post_modified": "2026-09-24 15:30:00",
            "parent": p4.PARENT_ID,
            "url": f"/plan/{p4.SLUG}/"
        },
        {
            "ID": 1205,
            "post_name": p5.SLUG,
            "post_title": p5.TITLE,
            "rm_title": p5.TITLE,
            "rm_desc": p5.META_DESC,
            "rm_keyword": p5.FOCUS_KEYWORD,
            "meta_title": p5.TITLE,
            "meta_desc": p5.META_DESC,
            "post_modified": "2026-09-24 15:30:00",
            "parent": p5.PARENT_ID,
            "url": f"/plan/{p5.SLUG}/"
        },
        {
            "ID": 1206,
            "post_name": p6.SLUG,
            "post_title": p6.TITLE,
            "rm_title": p6.TITLE,
            "rm_desc": p6.META_DESC,
            "rm_keyword": p6.FOCUS_KEYWORD,
            "meta_title": p6.TITLE,
            "meta_desc": p6.META_DESC,
            "post_modified": "2026-09-24 15:30:00",
            "parent": p6.PARENT_ID,
            "url": f"/destinations/{p6.SLUG}/"
        },
        {
            "ID": 1207,
            "post_name": p7.SLUG,
            "post_title": p7.TITLE,
            "rm_title": p7.TITLE,
            "rm_desc": p7.META_DESC,
            "rm_keyword": p7.FOCUS_KEYWORD,
            "meta_title": p7.TITLE,
            "meta_desc": p7.META_DESC,
            "post_modified": "2026-09-24 15:30:00",
            "parent": p7.PARENT_ID,
            "url": f"/plan/{p7.SLUG}/"
        },
    ]

    added = 0
    for p in new_pillars:
        if p['post_name'] not in existing_slugs:
            data.append(p)
            print(f"Added {p['post_name']} (ID {p['ID']})")
            added += 1
        else:
            print(f"Already exists: {p['post_name']}")

    with open(inv_file, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    print(f"Updated inventory saved: {len(data)} total entries (+{added} new).")

if __name__ == '__main__':
    main()
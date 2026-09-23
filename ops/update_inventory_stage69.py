# -*- coding: utf-8 -*-
"""
Append Stage 69 entries to ops/meta_inventory.json
"""
import json
import os
import sys

ops_dir = os.path.dirname(os.path.abspath(__file__))
if ops_dir not in sys.path:
    sys.path.insert(0, ops_dir)

import content_where_to_stay_da_lat
import content_best_things_da_lat
import content_best_things_nha_trang
import content_best_things_phu_quoc
import content_where_to_stay_phong_nha
import content_hue_to_hoi_an
import content_hcmc_to_mui_ne

MODULES = [
    (888, content_where_to_stay_da_lat),
    (889, content_best_things_da_lat),
    (890, content_best_things_nha_trang),
    (891, content_best_things_phu_quoc),
    (892, content_where_to_stay_phong_nha),
    (893, content_hue_to_hoi_an),
    (894, content_hcmc_to_mui_ne),
]

inv_file = os.path.join(ops_dir, 'meta_inventory.json')
with open(inv_file, 'r', encoding='utf-8') as f:
    inv = json.load(f)

existing_ids = {str(item['ID']) for item in inv}

added = 0
for pid, mod in MODULES:
    if str(pid) in existing_ids:
        print(f"Skipping already existing ID {pid}")
        continue
    inv.append({
        "ID": str(pid),
        "post_name": mod.SLUG,
        "post_title": mod.TITLE,
        "rm_title": mod.TITLE,
        "rm_desc": mod.META_DESC,
        "rm_keyword": mod.FOCUS_KEYWORD
    })
    added += 1

with open(inv_file, 'w', encoding='utf-8') as f:
    json.dump(inv, f, ensure_ascii=False, indent=2)

print(f"Added {added} new entries. Total entries now: {len(inv)}")

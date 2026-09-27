# -*- coding: utf-8 -*-
"""
VietnamGuide: Plan Group B Curation & Sideloading (64 Posts)
"""
import json

def main():
    with open('ops/group_b_extracted.json', 'r', encoding='utf-8') as f:
        items = json.load(f)

    print(f"Total Group B items: {len(items)}")
    for idx, it in enumerate(items, 1):
        src_short = it['src'].split('/')[-1] if it['src'] else 'NO_SRC'
        print(f"[{idx:02d}] ID {it['id']:<4} | {it['slug']:<42} | {src_short[:35]}")

if __name__ == '__main__':
    main()

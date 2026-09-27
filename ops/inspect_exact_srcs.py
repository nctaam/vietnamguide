# -*- coding: utf-8 -*-
"""
VietnamGuide: Inspect exact working Wikimedia URLs from ops/group_b_extracted.json
"""
import json
import urllib.parse

def main():
    with open('ops/group_b_extracted.json', 'r', encoding='utf-8') as f:
        data = json.load(f)

    # First 32 items
    batch_8b = data[:32]
    for it in batch_8b:
        src = it['src']
        if not src and it['id'] == 234:
            src = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg'
        elif not src and it['id'] == 237:
            src = 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg/1920px-Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg'

        fn = src.split('/')[-1]
        print(f"[{it['id']}] {it['slug']}")
        print(f"    SRC: {src}")

if __name__ == '__main__':
    main()

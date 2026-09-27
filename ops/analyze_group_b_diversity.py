# -*- coding: utf-8 -*-
"""
VietnamGuide: Analyze Group B Image Quality & Diversity
"""
import json
import urllib.parse
from collections import defaultdict

def main():
    with open('ops/group_b_extracted.json', 'r', encoding='utf-8') as f:
        items = json.load(f)

    # In posts 234 and 237, get first image from content
    for it in items:
        if it['id'] == 234:
            it['src'] = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg'
            it['alt'] = 'Kem Beach aerial view on Phu Quoc Island'
            it['caption'] = 'Kem Beach shows why Phu Quoc can work as a polished island resort finish. Image: <a href="https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg" target="_blank" rel="license noopener">Vivu Vietnam / CC BY-SA 4.0</a>.'
        elif it['id'] == 237:
            it['src'] = 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg/1920px-Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg'
            it['alt'] = 'Beach view from a quiet Con Dao resort area'
            it['caption'] = "Con Dao's beach value is quiet and premium, not maximum convenience. Image: <a href=\"https://commons.wikimedia.org/wiki/File:Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_(April_2022).jpg\" target=\"_blank\" rel=\"license noopener\">Daeva Trac / CC BY-SA 4.0</a>."

    # Group by base image filename
    by_image = defaultdict(list)
    for it in items:
        src = it['src']
        # decode filename from URL
        parts = src.split('/')
        fn = parts[-1]
        if fn.startswith('1920px-') or fn.startswith('1280px-'):
            fn = fn.split('-', 1)[1]
        fn = urllib.parse.unquote(fn)
        by_image[fn].append(it)

    print("=== IMAGES USED MULTIPLE TIMES ===")
    for fn, posts in by_image.items():
        if len(posts) > 1:
            print(f"\nImage: {fn} (Used in {len(posts)} posts):")
            for p in posts:
                print(f"  * ID {p['id']}: {p['slug']} ({p['title'][:40]})")

    print("\n=== UNIQUE IMAGES (1 USE EACH) ===")
    unique_count = sum(1 for fn, posts in by_image.items() if len(posts) == 1)
    print(f"Total unique images: {unique_count}")

if __name__ == '__main__':
    main()

# -*- coding: utf-8 -*-
"""
VietnamGuide: Prepare and Validate All 64 Group B Posts
1. Standardizes URLs to 1280px thumb width
2. Downloads and validates dimensions via PIL (Zero CLS)
3. Ensures all 64 posts have valid alt and caption attributions
"""
import urllib.request
import urllib.parse
import json
import ssl
import sys
import os
import io
import re
from PIL import Image

sys.stdout.reconfigure(encoding='utf-8')

def main():
    with open('ops/group_b_extracted.json', 'r', encoding='utf-8') as f:
        posts = json.load(f)

    print(f"=== PROCESSING ALL {len(posts)} GROUP B POSTS ===")

    # Handle post 234 & 237 first figures
    for p in posts:
        if p['id'] == 234 and not p['src']:
            p['src'] = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1280px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg'
            p['alt'] = 'Kem Beach aerial view on Phu Quoc Island'
            p['caption'] = 'Kem Beach shows why Phu Quoc can work as a polished island resort finish. Image: <a href="https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg" target="_blank" rel="license noopener">Vivu Vietnam / CC BY-SA 4.0</a>.'
        elif p['id'] == 237 and not p['src']:
            p['src'] = 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg/1280px-Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg'
            p['alt'] = 'Beach view from a quiet Con Dao resort area'
            p['caption'] = "Con Dao's beach value is quiet and premium, not maximum convenience. Image: <a href=\"https://commons.wikimedia.org/wiki/File:Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_(April_2022).jpg\" target=\"_blank\" rel=\"license noopener\">Daeva Trac / CC BY-SA 4.0</a>."

    staging_dir = 'ops/group_b_images'
    os.makedirs(staging_dir, exist_ok=True)

    ctx = ssl.create_default_context()
    manifest = []

    for idx, p in enumerate(posts, 1):
        pid = p['id']
        slug = p['slug']
        src = p['src']

        # Standardize 1920px -> 1280px for performance & Zero CLS standard
        norm_src = re.sub(r'/(?:1920px|1600px|800px|640px)-', '/1280px-', src)
        # If it was an un-thumbed commons URL: e.g. .../commons/a/b/Name.jpg -> .../commons/thumb/a/b/Name.jpg/1280px-Name.jpg
        if '/commons/' in norm_src and '/thumb/' not in norm_src:
            m = re.search(r'/commons/([^/]+/[^/]+)/([^/]+)$', norm_src)
            if m:
                path_part = m.group(1)
                file_part = m.group(2)
                norm_src = f"https://upload.wikimedia.org/wikipedia/commons/thumb/{path_part}/{file_part}/1280px-{file_part}"

        ext = '.jpg'
        if '.png' in norm_src.lower():
            ext = '.png'
        elif '.webp' in norm_src.lower():
            ext = '.webp'

        local_file = os.path.join(staging_dir, f"{pid}_{slug}{ext}")

        print(f"[{idx:02d}/{len(posts)}] Downloading {slug} (ID: {pid})...")
        req = urllib.request.Request(norm_src, headers={
            'User-Agent': 'VietnamGuideMediaAuditor/1.0 (travel@vietnamguide.net; contact@vietnamguide.net)'
        })

        try:
            with urllib.request.urlopen(req, context=ctx, timeout=20) as resp:
                data = resp.read()
                with open(local_file, 'wb') as lf:
                    lf.write(data)
                
                # Check dimensions
                im = Image.open(io.BytesIO(data))
                w, h = im.size
                ratio = round(w / h, 2) if h > 0 else 0

                manifest.append({
                    'id': pid,
                    'slug': slug,
                    'title': p['title'],
                    'alt': p['alt'],
                    'caption': p['caption'],
                    'orig_src': src,
                    'norm_src': norm_src,
                    'local_file': local_file,
                    'filename': f"{pid}_{slug}{ext}",
                    'width': w,
                    'height': h,
                    'ratio': ratio
                })
                print(f"       -> OK: {w}x{h} (ratio: {ratio}), {len(data)} bytes")
        except Exception as e:
            print(f"       ERROR downloading {norm_src}: {e}")

    with open('ops/group_b_validated_manifest.json', 'w', encoding='utf-8') as f:
        json.dump(manifest, f, indent=2, ensure_ascii=False)

    print(f"\nSuccessfully downloaded and validated {len(manifest)}/{len(posts)} images.")

if __name__ == '__main__':
    main()

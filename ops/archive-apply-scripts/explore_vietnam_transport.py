# -*- coding: utf-8 -*-
"""
Explore Wikimedia Commons categories and titles for authentic Vietnam transport photos.
"""
import urllib.request
import urllib.parse
import json
import ssl
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def find_vietnam_photos(query, limit=5):
    # Enforce Vietnam in search
    full_query = f"{query} Vietnam filetype:bitmap"
    params = {
        'action': 'query',
        'generator': 'search',
        'gsrsearch': full_query,
        'gsrnamespace': '6',
        'gsrlimit': str(limit),
        'prop': 'imageinfo',
        'iiprop': 'url|size|extmetadata',
        'iiurlwidth': '1280',
        'format': 'json'
    }
    url = f"https://commons.wikimedia.org/w/api.php?{urllib.parse.urlencode(params)}"
    req = urllib.request.Request(url, headers={'User-Agent': 'VietnamGuideBot/1.0 (https://vietnamguide.net; contact@vietnamguide.net)'})
    ctx = ssl.create_default_context()
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=12) as resp:
            data = json.loads(resp.read().decode('utf-8'))
    except Exception as e:
        print(f"Error searching '{query}': {e}")
        return []

    pages = data.get('query', {}).get('pages', {})
    results = []
    for pid, pdata in pages.items():
        title = pdata.get('title', '').replace('File:', '')
        ii = pdata.get('imageinfo', [{}])[0]
        meta = ii.get('extmetadata', {})
        thumb = ii.get('thumburl')
        w = int(ii.get('thumbwidth', 0))
        h = int(ii.get('thumbheight', 0))
        desc = meta.get('ImageDescription', {}).get('value', '')
        
        # Check aspect ratio
        if thumb and w >= 1000 and 450 <= h <= 1200:
            results.append({
                'title': title,
                'thumb': thumb,
                'w': w,
                'h': h,
                'artist': meta.get('Artist', {}).get('value', 'Wikimedia Commons'),
                'license': meta.get('LicenseShortName', {}).get('value', 'CC BY-SA')
            })
    return results

if __name__ == '__main__':
    test_topics = [
        "Bach Dang Bridge Quang Ninh",
        "Hai Van Pass railway",
        "Hien Luong Bridge",
        "Khanh Le pass",
        "Dinh Vu Cat Hai Bridge",
        "Ga Dong Hoi",
        "Deo Bao Loc",
        "Phu Quoc Airport",
        "Hai Van Pass",
        "Phan Thiet Expressway",
        "Can Tho Bridge",
        "Duong sat Viet Nam",
        "Ga Ninh Binh",
        "Lao Cai Railway Station",
        "Ga Dieu Tri",
        "Co Ong Airport",
        "Khau Pha Pass",
        "Pha Din Pass",
        "Deo Cu Mong",
        "Chau Doc boat",
        "Thung Khe Pass",
        "Pu Luong",
        "Ma Pi Leng pass",
        "Superdong Vietnam",
        "Con Dao ferry",
        "Ha Tien port"
    ]
    for top in test_topics:
        res = find_vietnam_photos(top, limit=3)
        print(f"\n=== Query: {top} ===")
        if res:
            for r in res[:2]:
                print(f"  - {r['title']} ({r['w']}x{r['h']}) [{r['license']}]")
        else:
            print("  [NO RESULTS]")

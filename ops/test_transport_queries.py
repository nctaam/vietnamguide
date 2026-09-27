# -*- coding: utf-8 -*-
import urllib.request
import urllib.parse
import json
import ssl
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

test_queries = [
    "Bach Dang Wharf Saigon",
    "Tau cao toc",
    "Cang Rach Gia",
    "Ben Dam Con Dao",
    "Cang Cau Da Vung Tau",
    "Phu Quoc boat",
    "Deo Khanh Le",
    "Deo Ngoan Muc",
    "Cao Bang road",
    "Deo Thung Khe",
    "Deo Pha Din",
    "Deo Ca Phu Yen",
    "Quoc lo 14",
    "Deo Dai Ninh",
    "Deo Gia Bac"
]

def search(q):
    url = f"https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrsearch={urllib.parse.quote(q + ' filetype:bitmap')}&gsrnamespace=6&gsrlimit=3&prop=imageinfo&iiprop=url|size&iiurlwidth=1280&format=json"
    req = urllib.request.Request(url, headers={'User-Agent': 'VietnamGuideBot/1.0'})
    try:
        with urllib.request.urlopen(req, context=ssl.create_default_context(), timeout=10) as r:
            data = json.loads(r.read())
        pages = data.get('query', {}).get('pages', {})
        for p in pages.values():
            ii = p['imageinfo'][0]
            w = ii.get('thumbwidth', 0)
            h = ii.get('thumbheight', 0)
            if w >= 1000 and 450 <= h <= 1200:
                print(f"[{q}] -> {p['title']} ({w}x{h})")
                return
        print(f"[{q}] -> NO VALID ASPECT RATIO MATCH")
    except Exception as e:
        print(f"[{q}] -> ERROR: {e}")

for t in test_queries:
    search(t)

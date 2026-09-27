# -*- coding: utf-8 -*-
"""
VietnamGuide Batch 4: Search and Curate Wikimedia Commons Images
Remaining 21 Regional Where-to-Stay & Lodging Guides
"""
import urllib.request
import urllib.parse
import json
import ssl
import sys
import time

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

SEARCH_TARGETS = [
    {
        'id': 1206,
        'slug': 'where-to-stay-in-dien-bien-phu',
        'query': 'Dien Bien Phu valley OR Dien Bien Phu city'
    },
    {
        'id': 1233,
        'slug': 'where-to-stay-in-ben-tre',
        'query': 'Ben Tre canal OR Ben Tre coconut Vietnam'
    },
    {
        'id': 1234,
        'slug': 'where-to-stay-in-lang-co',
        'query': 'Lang Co bay OR Lap An lagoon'
    },
    {
        'id': 1275,
        'slug': 'where-to-stay-in-cam-ranh',
        'query': 'Cam Ranh bay OR Bai Dai Cam Ranh'
    },
    {
        'id': 1276,
        'slug': 'where-to-stay-in-rach-gia',
        'query': 'Rach Gia Vietnam OR Rach Gia Kien Giang'
    },
    {
        'id': 1315,
        'slug': 'where-to-stay-in-soc-trang',
        'query': 'Chua Doi Soc Trang OR Bat Pagoda Soc Trang'
    },
    {
        'id': 1352,
        'slug': 'where-to-stay-in-bac-lieu',
        'query': 'Bac Lieu wind farm OR Bac Lieu Vietnam'
    },
    {
        'id': 1353,
        'slug': 'where-to-stay-in-ca-mau',
        'query': 'Mui Ca Mau OR Ca Mau national park'
    },
    {
        'id': 1387,
        'slug': 'where-to-stay-in-hai-phong',
        'query': 'Hai Phong Opera House OR Hai Phong city'
    },
    {
        'id': 1390,
        'slug': 'where-to-stay-in-phan-rang',
        'query': 'Po Klong Garai OR Phan Rang Cham'
    },
    {
        'id': 1392,
        'slug': 'where-to-stay-in-tam-dao',
        'query': 'Tam Dao stone church OR Tam Dao hill station'
    },
    {
        'id': 1428,
        'slug': 'where-to-stay-in-vinh-hy',
        'query': 'Vinh Hy bay Vietnam'
    },
    {
        'id': 1453,
        'slug': 'where-to-stay-in-du-gia',
        'query': 'Du Gia waterfall OR Du Gia Ha Giang'
    },
    {
        'id': 1454,
        'slug': 'where-to-stay-in-mang-den',
        'query': 'Mang Den Kon Tum OR Pa Sy waterfall'
    },
    {
        'id': 1459,
        'slug': 'where-to-stay-in-an-giang',
        'query': 'Tra Su cajuput forest OR Tra Su An Giang'
    },
    {
        'id': 1494,
        'slug': 'where-to-stay-in-ly-son',
        'query': 'Ly Son island OR To Vo gate Ly Son'
    },
    {
        'id': 1529,
        'slug': 'where-to-stay-in-yen-minh',
        'query': 'Yen Minh pine forest OR Yen Minh Ha Giang'
    },
    {
        'id': 1530,
        'slug': 'where-to-stay-in-quang-ngai',
        'query': 'Quang Ngai city OR Tra Khuc river'
    },
    {
        'id': 1561,
        'slug': 'where-to-stay-in-bac-ha',
        'query': 'Bac Ha market OR Hoang A Tuong palace'
    },
    {
        'id': 1562,
        'slug': 'where-to-stay-in-bao-lac',
        'query': 'Bao Lac Cao Bang OR Bao Lac Vietnam'
    },
    {
        'id': 1563,
        'slug': 'where-to-stay-in-bao-loc',
        'query': 'Dambri waterfall OR Bao Loc tea Vietnam'
    }
]

def search_commons(query, limit=3):
    params = {
        'action': 'query',
        'list': 'search',
        'srsearch': query,
        'srnamespace': '6',
        'srlimit': str(limit),
        'format': 'json'
    }
    url = f"https://commons.wikimedia.org/w/api.php?{urllib.parse.urlencode(params)}"
    req = urllib.request.Request(url, headers={'User-Agent': 'VietnamGuideBot/1.0 (https://vietnamguide.net; contact@vietnamguide.net)'})
    ctx = ssl.create_default_context()
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=12) as resp:
            data = json.loads(resp.read().decode('utf-8'))
        return [r['title'].replace('File:', '') for r in data.get('query', {}).get('search', [])]
    except Exception as e:
        return []

def main():
    print(f"Searching Wikimedia Commons for all {len(SEARCH_TARGETS)} Batch 4 targets...")
    for idx, t in enumerate(SEARCH_TARGETS, 1):
        results = search_commons(t['query'], limit=3)
        print(f"[{idx}/21] [{t['id']}] {t['slug']}")
        print(f"   Query: {t['query']}")
        for r in results:
            print(f"     * {r}")
        time.sleep(0.8)

if __name__ == '__main__':
    main()

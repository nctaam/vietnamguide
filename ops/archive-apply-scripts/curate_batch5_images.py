# -*- coding: utf-8 -*-
"""
VietnamGuide Batch 5: Search and Curate Wikimedia Commons Images
Remaining 31 Regional Transit & Connecting Corridors Guides
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
        'id': 1272,
        'slug': 'ho-chi-minh-city-to-ben-tre-transport',
        'query': 'Cau Rach Mieu OR Rach Mieu Bridge'
    },
    {
        'id': 1273,
        'slug': 'cat-ba-to-ninh-binh-transport',
        'query': 'Cat Ba ferry OR Ben pha Got'
    },
    {
        'id': 1274,
        'slug': 'pleiku-to-kon-tum-transport',
        'query': 'National Route 14 Vietnam OR Kon Tum road'
    },
    {
        'id': 1311,
        'slug': 'sapa-to-mu-cang-chai-transport',
        'query': 'O Quy Ho Pass OR Deo O Quy Ho'
    },
    {
        'id': 1312,
        'slug': 'can-tho-to-rach-gia-transport',
        'query': 'Vam Cong Bridge OR Lo Te Rach Soi'
    },
    {
        'id': 1313,
        'slug': 'hue-to-dong-hoi-transport',
        'query': 'Quang Tri citadel OR Hien Luong bridge'
    },
    {
        'id': 1314,
        'slug': 'da-lat-to-buon-ma-thuot-transport',
        'query': 'Ho Lak OR Lak lake Vietnam'
    },
    {
        'id': 1348,
        'slug': 'hoi-an-to-quy-nhon-transport',
        'query': 'Ga Tam Ky OR Quang Ngai train'
    },
    {
        'id': 1349,
        'slug': 'hanoi-to-hai-phong-transport',
        'query': 'Hanoi Hai Phong expressway'
    },
    {
        'id': 1350,
        'slug': 'can-tho-to-ha-tien-transport',
        'query': 'Kenh Vinh Te OR Ha Tien border'
    },
    {
        'id': 1351,
        'slug': 'buon-ma-thuot-to-nha-trang-transport',
        'query': 'Deo Phuong Hoang OR National Route 26 Vietnam'
    },
    {
        'id': 1388,
        'slug': 'dong-hoi-to-phong-nha-transport',
        'query': 'Ho Chi Minh highway Phong Nha OR Son River boat'
    },
    {
        'id': 1389,
        'slug': 'can-tho-to-ca-mau-transport',
        'query': 'Can Tho Ca Mau OR Phung Hiep canal'
    },
    {
        'id': 1391,
        'slug': 'hanoi-to-tam-dao-transport',
        'query': 'Tam Dao road OR Tam Dao mountain'
    },
    {
        'id': 1393,
        'slug': 'kon-tum-to-da-nang-transport',
        'query': 'Deo Lo Xo OR Lo Xo pass'
    },
    {
        'id': 1425,
        'slug': 'ha-long-to-ninh-binh-transport',
        'query': 'Bach Dang bridge OR Red River delta highway'
    },
    {
        'id': 1426,
        'slug': 'dong-hoi-to-hue-transport',
        'query': 'Ga Hue OR Hue Railway Station'
    },
    {
        'id': 1429,
        'slug': 'quy-nhon-to-nha-trang-transport',
        'query': 'Deo Cu Mong OR Cu Mong Pass'
    },
    {
        'id': 1455,
        'slug': 'nha-trang-to-da-lat-transport',
        'query': 'Deo Khanh Le OR Khanh Le pass'
    },
    {
        'id': 1456,
        'slug': 'ninh-binh-to-phong-nha-transport',
        'query': 'North South railway Vietnam OR Duong sat Bac Nam'
    },
    {
        'id': 1457,
        'slug': 'cao-bang-to-ba-be-transport',
        'query': 'Ba Be lake road OR Cho Ra Bac Kan'
    },
    {
        'id': 1458,
        'slug': 'ho-chi-minh-city-to-chau-doc-transport',
        'query': 'Vam Cong bridge OR Chau Doc ferry'
    },
    {
        'id': 1497,
        'slug': 'da-nang-to-ly-son-transport',
        'query': 'Cang Sa Ky OR Sa Ky port'
    },
    {
        'id': 1498,
        'slug': 'hue-to-phong-nha-transport',
        'query': 'Vinh Moc tunnels OR DMZ Vietnam'
    },
    {
        'id': 1499,
        'slug': 'mai-chau-to-pu-luong-transport',
        'query': 'Ba Thuoc Thanh Hoa OR Pu Luong mountain'
    },
    {
        'id': 1531,
        'slug': 'ha-giang-to-dong-van-transport',
        'query': 'Cong troi Quan Ba OR Quan Ba Heaven Gate'
    },
    {
        'id': 1533,
        'slug': 'can-tho-to-phu-quoc-transport',
        'query': 'Can Tho International Airport OR Phu Quoc Express'
    },
    {
        'id': 1534,
        'slug': 'quy-nhon-to-hoi-an-transport',
        'query': 'Vietnam railway carriage OR North South railway'
    },
    {
        'id': 1535,
        'slug': 'da-lat-to-pleiku-transport',
        'query': 'Highway 27 Vietnam OR Central Highlands Vietnam road'
    },
    {
        'id': 1564,
        'slug': 'sapa-to-bac-ha-transport',
        'query': 'Coc Ly market OR Bac Ha road'
    },
    {
        'id': 1567,
        'slug': 'pleiku-to-quy-nhon-transport',
        'query': 'Deo An Khe OR An Khe Pass'
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
    print(f"Searching Wikimedia Commons for all {len(SEARCH_TARGETS)} Batch 5 targets...")
    for idx, t in enumerate(SEARCH_TARGETS, 1):
        results = search_commons(t['query'], limit=3)
        print(f"[{idx}/31] [{t['id']}] {t['slug']}")
        print(f"   Query: {t['query']}")
        for r in results:
            print(f"     * {r}")
        time.sleep(0.8)

if __name__ == '__main__':
    main()

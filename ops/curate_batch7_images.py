# -*- coding: utf-8 -*-
"""
VietnamGuide Batch 7: Search and Curate Wikimedia Commons Images
22 Destination Guides & Cost Budget Pillars
"""
import urllib.request
import urllib.parse
import json
import ssl
import sys
import time

sys.stdout.reconfigure(encoding='utf-8')

SEARCH_TARGETS = [
    # 18 Destination Guides (Parent 7)
    {
        'id': 110,
        'slug': 'best-places-to-visit-vietnam',
        'queries': ['Trang An Ninh Binh landscape', 'Halong bay panoramic view', 'Vietnam landscape scenic']
    },
    {
        'id': 499,
        'slug': 'hoi-an-ancient-town-guide',
        'queries': ['Hoi An Japanese Covered Bridge', 'Chua Cau Hoi An', 'Hoi An ancient town daytime']
    },
    {
        'id': 500,
        'slug': 'hue-imperial-city-guide',
        'queries': ['Thai Hoa Palace Hue', 'Hue Imperial City courtyard', 'Dien Thai Hoa Hue']
    },
    {
        'id': 501,
        'slug': 'da-nang-beaches-guide',
        'queries': ['My Khe Beach Danang sunny', 'Non Nuoc Beach Da Nang', 'Son Tra Peninsula Da Nang']
    },
    {
        'id': 503,
        'slug': 'phong-nha-travel-guide',
        'queries': ['Paradise Cave Phong Nha', 'Phong Nha cave entrance boat', 'Son Doong Phong Nha national park']
    },
    {
        'id': 518,
        'slug': 'sapa-travel-guide',
        'queries': ['Sapa stone church', 'Nha tho da Sa Pa', 'Sapa town square']
    },
    {
        'id': 519,
        'slug': 'ha-giang-loop-planning-guide',
        'queries': ['Ma Pi Leng pass view', 'Nho Que river Ha Giang', 'Dong Van karst plateau loop']
    },
    {
        'id': 525,
        'slug': 'where-to-stay-in-sapa',
        'queries': ['Ta Van village Sapa homestay', 'Ecolodge Sapa valley view', 'Sapa mountain resort view']
    },
    {
        'id': 528,
        'slug': 'mu-cang-chai-travel-guide',
        'queries': ['Mu Cang Chai rice terraces panoramic', 'De Cu Nha rice terrace', 'La Pan Tan Mu Cang Chai']
    },
    {
        'id': 529,
        'slug': 'pu-luong-travel-guide',
        'queries': ['Pu Luong water wheel', 'Con nuoc Pu Luong', 'Ban Don Pu Luong']
    },
    {
        'id': 611,
        'slug': 'da-lat-travel-guide',
        'queries': ['Ho Xuan Huong Da Lat', 'Xuan Huong lake Da Lat', 'Da Lat French villa']
    },
    {
        'id': 612,
        'slug': 'cao-bang-travel-guide',
        'queries': ['Nguom Ngao cave Cao Bang', 'Khuoi Ky stone village Cao Bang', 'Trung Khanh Cao Bang landscape']
    },
    {
        'id': 626,
        'slug': 'phu-yen-travel-guide',
        'queries': ['Ganh Da Dia basalt columns Phu Yen', 'Ganh Da Dia Phu Yen', 'Bai Xep Phu Yen']
    },
    {
        'id': 922,
        'slug': 'best-things-to-do-in-ninh-binh',
        'queries': ['Hang Mua viewpoint Tam Coc', 'Mua cave dragon peak', 'Dinh Hang Mua Ninh Binh']
    },
    {
        'id': 924,
        'slug': 'best-things-to-do-in-mui-ne',
        'queries': ['White sand dunes Mui Ne', 'Doi cat trang Mui Ne', 'Suoi Tien Fairy Stream Mui Ne']
    },
    {
        'id': 926,
        'slug': 'best-things-to-do-in-quy-nhon',
        'queries': ['Eo Gio Quy Nhon', 'Ky Co beach Quy Nhon', 'Ghenh Rang Quy Nhon']
    },
    {
        'id': 1495,
        'slug': 'mang-den-travel-guide',
        'queries': ['Thac Pa Sy Mang Den', 'Pa Sy waterfall Mang Den', 'Ho Dak Ke Mang Den']
    },
    {
        'id': 1496,
        'slug': 'ba-be-lake-travel-guide',
        'queries': ['Ba Be Lake boat tour', 'Scene at Ba Be Lake', 'Ba Be national park boat']
    },

    # 4 Cost Budget Guides (Parent 10)
    {
        'id': 600,
        'slug': 'ha-giang-loop-cost-budget',
        'queries': ['Ha Giang motorbike trip', 'Du Gia waterfall Ha Giang', 'Ha Giang hostel homestay']
    },
    {
        'id': 601,
        'slug': 'da-nang-hoi-an-budget',
        'queries': ['Hoi An river night lanterns', 'Hoi An night market lanterns', 'Hoi An Thu Bon river boat']
    },
    {
        'id': 602,
        'slug': 'hanoi-ninh-binh-ha-long-budget',
        'queries': ['Halong bay cruise daytime', 'Ninh Binh river tour boat', 'Trang An boat tour']
    },
    {
        'id': 1002,
        'slug': 'ho-chi-minh-city-mekong-budget',
        'queries': ['Ben Thanh market facade Saigon', 'Cho Ben Thanh Saigon', 'Saigon street food market']
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
    req = urllib.request.Request(url, headers={'User-Agent': 'VietnamGuideBot/1.0 (contact@vietnamguide.net)'})
    ctx = ssl.create_default_context()
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=12) as resp:
            data = json.loads(resp.read().decode('utf-8'))
        return [r['title'].replace('File:', '') for r in data.get('query', {}).get('search', []) if not r['title'].lower().endswith(('.pdf', '.svg', '.djvu'))]
    except Exception as e:
        return []

def main():
    print(f"Searching Wikimedia Commons for all {len(SEARCH_TARGETS)} Batch 7 targets...")
    for idx, t in enumerate(SEARCH_TARGETS, 1):
        print(f"\n[{idx}/22] [{t['id']}] {t['slug']}")
        seen = set()
        for q in t['queries']:
            time.sleep(1.0)
            res = search_commons(q, limit=2)
            for r in res:
                if r not in seen:
                    seen.add(r)
                    print(f"   * ({q}) -> {r}")

if __name__ == '__main__':
    main()

# -*- coding: utf-8 -*-
"""
VietnamGuide Batch 3: Search and Curate Wikimedia Commons Images
Food, Night Markets, Culture, and Iconic Sights (30 Posts)
"""
import urllib.request
import urllib.parse
import json
import ssl
import sys
import time
import re

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

SEARCH_TARGETS = [
    {
        'id': 614,
        'slug': 'hanoi-street-food-guide',
        'query': 'Pho Bo Hanoi OR Bun Cha Hanoi'
    },
    {
        'id': 615,
        'slug': 'saigon-street-food-guide',
        'query': 'Banh mi Saigon OR Com tam Saigon'
    },
    {
        'id': 616,
        'slug': 'da-nang-street-food-guide',
        'query': 'Mi Quang Da Nang'
    },
    {
        'id': 628,
        'slug': 'hang-mua-ninh-binh-guide',
        'query': 'Hang Mua Ninh Binh'
    },
    {
        'id': 629,
        'slug': 'phong-nha-cave-treks',
        'query': 'Phong Nha cave trek OR Paradise Cave Vietnam'
    },
    {
        'id': 649,
        'slug': 'vietnam-coffee-guide',
        'query': 'Egg coffee Hanoi OR Ca phe trung'
    },
    {
        'id': 715,
        'slug': 'saigon-night-markets-guide',
        'query': 'Ben Thanh night market OR Ho Thi Ky market'
    },
    {
        'id': 716,
        'slug': 'hoi-an-street-food-guide',
        'query': 'Cao Lau Hoi An'
    },
    {
        'id': 717,
        'slug': 'tam-coc-vs-trang-an-boat-tour',
        'query': 'Trang An boat OR Tam Coc boat'
    },
    {
        'id': 718,
        'slug': 'sapa-trekking-routes-guide',
        'query': 'Sapa trekking OR Muong Hoa valley terrace'
    },
    {
        'id': 720,
        'slug': 'cham-islands-day-trip-guide',
        'query': 'Cu Lao Cham beach OR Cham Islands Vietnam'
    },
    {
        'id': 751,
        'slug': 'phu-quoc-beaches-guide',
        'query': 'Bai Sao Phu Quoc OR Phu Quoc beach'
    },
    {
        'id': 753,
        'slug': 'da-lat-waterfalls-guide',
        'query': 'Pongour waterfall OR Datanla waterfall'
    },
    {
        'id': 786,
        'slug': 'hanoi-train-street-guide',
        'query': 'Hanoi train street'
    },
    {
        'id': 787,
        'slug': 'cu-chi-tunnels-ben-duoc-vs-ben-dinh',
        'query': 'Cu Chi tunnels entrance'
    },
    {
        'id': 788,
        'slug': 'ba-na-hills-golden-bridge-guide',
        'query': 'Golden Bridge Ba Na Hills'
    },
    {
        'id': 791,
        'slug': 'hue-street-food-guide',
        'query': 'Bun Bo Hue OR Banh beo Hue'
    },
    {
        'id': 822,
        'slug': 'mekong-delta-floating-markets-guide',
        'query': 'Cai Rang floating market'
    },
    {
        'id': 824,
        'slug': 'da-lat-coffee-farms-guide',
        'query': 'Coffee plantation Da Lat OR coffee cherries Vietnam'
    },
    {
        'id': 825,
        'slug': 'hoi-an-tailoring-guide',
        'query': 'Hoi An tailor OR silk tailor Hoi An'
    },
    {
        'id': 855,
        'slug': 'best-things-to-do-in-ho-chi-minh-city',
        'query': 'Saigon Central Post Office'
    },
    {
        'id': 858,
        'slug': 'best-things-to-do-in-da-nang',
        'query': 'Dragon Bridge Da Nang'
    },
    {
        'id': 889,
        'slug': 'best-things-to-do-in-da-lat',
        'query': 'Dalat Railway Station OR Xuan Huong Lake'
    },
    {
        'id': 890,
        'slug': 'best-things-to-do-in-nha-trang',
        'query': 'Po Nagar Cham Towers'
    },
    {
        'id': 891,
        'slug': 'best-things-to-do-in-phu-quoc',
        'query': 'Phu Quoc sunset OR An Thoi cable car'
    },
    {
        'id': 921,
        'slug': 'best-things-to-do-in-sapa',
        'query': 'Fansipan peak OR Fansipan cable car'
    },
    {
        'id': 923,
        'slug': 'best-things-to-do-in-ha-long-bay',
        'query': 'Ha Long Bay junk boat karst'
    },
    {
        'id': 1039,
        'slug': 'vietnam-vegetarian-travel-guide',
        'query': 'Vietnamese vegetarian food OR Goi cuon chay'
    },
    {
        'id': 1071,
        'slug': 'vietnam-gluten-free-travel-guide',
        'query': 'Goi cuon Vietnam OR Vietnamese spring rolls'
    },
    {
        'id': 1102,
        'slug': 'vietnam-craft-beer-guide',
        'query': 'Pasteur Street Brewing OR Vietnamese craft beer'
    }
]

def search_commons(query, limit=4):
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
    print(f"Searching Wikimedia Commons for all {len(SEARCH_TARGETS)} Batch 3 targets...")
    for idx, t in enumerate(SEARCH_TARGETS, 1):
        results = search_commons(t['query'], limit=3)
        print(f"[{idx}/30] [{t['id']}] {t['slug']}")
        print(f"   Query: {t['query']}")
        for r in results:
            print(f"     * {r}")
        time.sleep(0.8)

if __name__ == '__main__':
    main()

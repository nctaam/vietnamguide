# -*- coding: utf-8 -*-
"""
VietnamGuide Batch 6: Search and Curate Wikimedia Commons Images
26 Itineraries, Regional Comparisons & Seasonal Weather Guides
"""
import urllib.request
import urllib.parse
import json
import ssl
import sys
import time

sys.stdout.reconfigure(encoding='utf-8')

SEARCH_TARGETS = [
    # 8 Itineraries
    {
        'id': 682,
        'slug': 'ha-long-bay-day-trip-vs-overnight-cruise',
        'queries': ['Ha Long Bay cruise junk', 'Overnight cruise Ha Long Bay', 'Halong bay boat']
    },
    {
        'id': 969,
        'slug': 'ho-chi-minh-city-in-2-days',
        'queries': ['Ho Chi Minh City skyline Saigon', 'Bitexco Financial Tower Saigon River', 'Saigon skyline dusk']
    },
    {
        'id': 1000,
        'slug': 'vietnam-family-travel-guide',
        'queries': ['Hoi An lantern street night', 'Hoi An lanterns evening', 'An Bang beach Hoi An']
    },
    {
        'id': 1001,
        'slug': 'central-vietnam-itinerary',
        'queries': ['Hue Imperial Citadel Ngo Mon', 'Hai Van Pass view ocean', 'Hoi An Japanese covered bridge']
    },
    {
        'id': 1038,
        'slug': 'southern-vietnam-itinerary',
        'queries': ['Cai Be floating market boat', 'Mekong delta canal boat rowing', 'Can Tho river boat']
    },
    {
        'id': 1069,
        'slug': 'northwest-vietnam-itinerary',
        'queries': ['Mu Cang Chai rice terrace', 'La Pan Tan terraced fields', 'Sapa valley terrace']
    },
    {
        'id': 1100,
        'slug': 'central-highlands-vietnam-itinerary',
        'queries': ['Coffee plantation Dak Lak', 'Buon Ma Thuot coffee highland', 'Dray Sap waterfall']
    },
    {
        'id': 1140,
        'slug': 'northeast-vietnam-itinerary',
        'queries': ['Ban Gioc falls Cao Bang', 'Ba Be Lake scenic', 'Dong Van karst mountain']
    },
    # 9 Comparisons
    {
        'id': 104,
        'slug': 'north-central-south-vietnam',
        'queries': ['Vietnam landscape panorama', 'Red River Delta Vietnam landscape', 'Vietnam aerial view landscape']
    },
    {
        'id': 301,
        'slug': 'old-quarter-vs-french-quarter-vs-west-lake',
        'queries': ['Hanoi Old Quarter street life', 'Ta Hien street Hanoi', 'Hang Ma street Hanoi']
    },
    {
        'id': 498,
        'slug': 'hanoi-vs-ho-chi-minh-city',
        'queries': ['Turtle Tower Hoan Kiem Hanoi', 'Hanoi Opera House exterior', 'Hanoi Hoan Kiem lake']
    },
    {
        'id': 502,
        'slug': 'mekong-delta-overnight-vs-day-trip',
        'queries': ['Mekong delta rowing boat canal', 'Ben Tre rowing boat canal', 'Mekong river sunset sampan']
    },
    {
        'id': 504,
        'slug': 'sapa-vs-ha-giang',
        'queries': ['Fansipan mountain cable car view', 'Muong Hoa valley Sapa', 'Hoang Lien Son mountain range']
    },
    {
        'id': 523,
        'slug': 'ha-giang-easy-rider-vs-self-drive',
        'queries': ['Motorbike Ha Giang loop', 'Riders Ha Giang loop pass', 'Motorcycle Vietnam mountain']
    },
    {
        'id': 527,
        'slug': 'vietnam-rice-terraces-guide',
        'queries': ['Golden rice terraces Mu Cang Chai', 'Mam Xoi terrace Mu Cang Chai', 'Terraced rice fields Vietnam harvest']
    },
    {
        'id': 627,
        'slug': 'ly-son-vs-cham-islands',
        'queries': ['Cu Lao Cham beach', 'Cham Islands bay', 'Cu Lao Cham coral']
    },
    {
        'id': 752,
        'slug': 'con-dao-vs-phu-quoc',
        'queries': ['Dam Trau beach Con Dao', 'Con Dao island aerial bay', 'Con Dao beach turquoise']
    },
    # 9 Weather Months
    {
        'id': 650,
        'slug': 'vietnam-in-march',
        'queries': ['Halong Bay spring clear', 'Ninh Binh March green', 'Hanoi March spring']
    },
    {
        'id': 685,
        'slug': 'vietnam-in-april',
        'queries': ['My Khe beach Da Nang sunny', 'Nha Trang beach April sunny', 'Hoi An sunny April']
    },
    {
        'id': 721,
        'slug': 'vietnam-in-may',
        'queries': ['Tam Coc rice field boat May', 'Ninh Binh golden river May', 'Trang An sunny May']
    },
    {
        'id': 757,
        'slug': 'vietnam-in-june',
        'queries': ['Phu Quoc Sao beach summer', 'Phu Quoc turquoise summer', 'Danang beach June']
    },
    {
        'id': 792,
        'slug': 'vietnam-in-july',
        'queries': ['Hoi An river sunset July', 'Da Nang Dragon bridge July', 'Lang Co bay sunny July']
    },
    {
        'id': 820,
        'slug': 'vietnam-in-august',
        'queries': ['Pu Luong green rice August', 'Mai Chau August valley', 'Sapa August terraces']
    },
    {
        'id': 826,
        'slug': 'vietnam-in-september',
        'queries': ['Mu Cang Chai September harvest', 'Sapa golden rice September', 'Tu Le valley September']
    },
    {
        'id': 854,
        'slug': 'vietnam-in-october',
        'queries': ['Hanoi autumn Hoan Kiem', 'Sword Lake Hanoi autumn', 'Hanoi October street']
    },
    {
        'id': 651,
        'slug': 'vietnam-in-november',
        'queries': ['Buckwheat flower Ha Giang November', 'Hoa tam giac mach Ha Giang', 'Phu Quoc sunset November']
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
    print(f"Searching Wikimedia Commons for all {len(SEARCH_TARGETS)} Batch 6 targets...")
    for idx, t in enumerate(SEARCH_TARGETS, 1):
        print(f"\n[{idx}/26] [{t['id']}] {t['slug']}")
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

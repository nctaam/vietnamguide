# -*- coding: utf-8 -*-
"""
VietnamGuide Batch 8A: Search and Curate Wikimedia Commons Images
28 Practical Travel & Transit Guides
"""
import urllib.request
import urllib.parse
import json
import ssl
import sys
import time

sys.stdout.reconfigure(encoding='utf-8')

TARGETS = [
    {
        'id': 13,
        'slug': 'vietnam-evisa',
        'queries': ['Noi Bai Airport immigration', 'Vietnam passport control airport', 'Tan Son Nhat airport terminal international', 'Noi Bai International Airport terminal']
    },
    {
        'id': 497,
        'slug': 'best-vietnam-cities-for-first-time-visitors',
        'queries': ['Dragon Bridge Da Nang daytime', 'Hanoi Old Quarter street daytime', 'Hoan Kiem lake bridge red']
    },
    {
        'id': 520,
        'slug': 'hanoi-to-sapa-transport',
        'queries': ['Lao Cai railway station', 'Noi Bai Lao Cai highway', 'Vietnam train mountain']
    },
    {
        'id': 521,
        'slug': 'hanoi-to-ha-giang-transport',
        'queries': ['Ha Giang mountain highway', 'Vietnam mountain road bus', 'Ha Giang pass road']
    },
    {
        'id': 522,
        'slug': 'ha-giang-safety-guide',
        'queries': ['Motorcycle tour Ha Giang', 'Ha Giang loop motorcycle', 'Ma Pi Leng motorcycle scenic']
    },
    {
        'id': 524,
        'slug': 'sapa-trekking-guided-vs-self-guided',
        'queries': ['Muong Hoa valley trekking', 'Sapa rice fields hikers', 'Trekking in Sa Pa Vietnam']
    },
    {
        'id': 526,
        'slug': 'best-time-for-northern-vietnam',
        'queries': ['Mu Cang Chai terraced fields green', 'Sapa terraced fields sunny', 'Ninh Binh landscape sunny boat']
    },
    {
        'id': 613,
        'slug': 'vietnam-train-travel',
        'queries': ['Hai Van Pass train', 'Vietnam Railways locomotive', 'Reunification Express train']
    },
    {
        'id': 645,
        'slug': 'saigon-airport-to-district-1',
        'queries': ['Tan Son Nhat International Airport facade', 'Tan Son Nhat airport international terminal', 'Tan Son Nhat airport bus']
    },
    {
        'id': 646,
        'slug': 'da-nang-airport-to-hoi-an',
        'queries': ['Da Nang International Airport terminal', 'Vo Nguyen Giap street Da Nang', 'Da Nang coastal highway']
    },
    {
        'id': 647,
        'slug': 'grab-in-vietnam-guide',
        'queries': ['Grab bike Vietnam', 'Grab delivery Vietnam scooter', 'Motorcycles street Hanoi traffic']
    },
    {
        'id': 648,
        'slug': 'tipping-in-vietnam',
        'queries': ['Vietnamese dong banknotes', 'Vietnamese currency dong', 'Vietnam cash money']
    },
    {
        'id': 686,
        'slug': 'tap-water-in-vietnam',
        'queries': ['Tra da iced tea Vietnam', 'Vietnamese street drink stall', 'Vietnamese iced tea tra da']
    },
    {
        'id': 719,
        'slug': 'quy-nhon-to-phu-yen-coastal-drive',
        'queries': ['Cu Mong Pass Vietnam', 'Song Cau Phu Yen coast', 'Quy Nhon coastal road']
    },
    {
        'id': 754,
        'slug': 'vietnam-sleeper-bus-guide',
        'queries': ['Sleeper bus Vietnam', 'Thaco bus Vietnam', 'Vietnam intercity bus']
    },
    {
        'id': 789,
        'slug': 'vietnam-to-cambodia-border-crossings',
        'queries': ['Moc Bai border gate', 'Bavet border crossing', 'Moc Bai international border gate']
    },
    {
        'id': 1037,
        'slug': 'pu-luong-trekking-routes-guide',
        'queries': ['Water wheel Pu Luong', 'Pu Luong nature reserve water wheels', 'Pu Luong terraced fields']
    },
    {
        'id': 1068,
        'slug': 'vietnam-sim-card-airport-vs-city',
        'queries': ['Viettel store Vietnam', 'Vinaphone shop Hanoi', 'Telecom shop Vietnam']
    },
    {
        'id': 1099,
        'slug': 'vietnam-travel-apps',
        'queries': ['Smartphone cafe Vietnam', 'Mobile phone street Vietnam', 'Using phone Hanoi']
    },
    {
        'id': 1141,
        'slug': 'vietnam-motorbike-license-laws',
        'queries': ['Motorbike traffic Hanoi intersection', 'Motorcycles street Saigon traffic', 'Scooters waiting traffic light Vietnam']
    },
    {
        'id': 1142,
        'slug': 'vietnam-plug-adapter-electricity-guide',
        'queries': ['Power outlet Type C', 'Europlug socket wall', 'AC power plugs and sockets Type C']
    },
    {
        'id': 1171,
        'slug': 'vietnam-scooter-rental-checklist',
        'queries': ['Motorcycles parked street Vietnam', 'Scooter rental shop Vietnam', 'Honda Wave motorcycle Vietnam']
    },
    {
        'id': 1204,
        'slug': 'vietnam-to-cambodia-boat-guide',
        'queries': ['Chau Doc boat river', 'Speedboat Mekong river Chau Doc', 'Chau Doc floating village boat']
    },
    {
        'id': 1205,
        'slug': 'vietnam-night-train-safety-tips',
        'queries': ['Vietnam railway sleeper berth', 'Soft sleeper train compartment', 'Vietnam train sleeper cabin']
    },
    {
        'id': 1235,
        'slug': 'vietnam-sleeper-bus-survival-guide',
        'queries': ['Cabin bus Vietnam', 'Sleeper bus cabin Vietnam', 'Bus passenger cabin Vietnam']
    },
    {
        'id': 1277,
        'slug': 'phu-quoc-ferry-guide',
        'queries': ['Superdong ferry Phu Quoc', 'Phu Quoc Express ferry catamaran', 'Rach Gia passenger ferry port']
    },
    {
        'id': 1316,
        'slug': 'vietnam-domestic-flights-guide',
        'queries': ['Vietnam Airlines Airbus A321 tarmac', 'Vietjet Air aircraft airport', 'Bamboo Airways plane airport Vietnam']
    },
    {
        'id': 1354,
        'slug': 'vietnam-train-vs-flight',
        'queries': ['Hai Van Pass railway bridge', 'Lang Co bay railway scenic', 'Vietnam passenger train coastal']
    }
]

def search_commons(query, max_results=8):
    params = {
        'action': 'query',
        'format': 'json',
        'generator': 'search',
        'gsrsearch': query,
        'gsrnamespace': '6',
        'gsrlimit': str(max_results),
        'prop': 'imageinfo',
        'iiprop': 'url|size|extmetadata|dimensions',
        'iiurlwidth': '1280'
    }
    url = 'https://commons.wikimedia.org/w/api.php?' + urllib.parse.urlencode(params)
    req = urllib.request.Request(url, headers={
        'User-Agent': 'VietnamGuideMediaAuditor/1.0 (travel@vietnamguide.net; contact@vietnamguide.net)'
    })
    ctx = ssl.create_default_context()
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=15) as resp:
            data = json.loads(resp.read().decode('utf-8'))
            pages = data.get('query', {}).get('pages', {})
            return list(pages.values())
    except Exception as e:
        print(f"  Error searching '{query}': {e}")
        return []

def main():
    print(f"=== SEARCHING COMMONS FOR 28 PRACTICAL GUIDES (Batch 8A) ===")
    results = {}
    for item in TARGETS:
        slug = item['slug']
        pid = item['id']
        print(f"\nTarget {pid}: {slug}")
        candidates = []
        seen_titles = set()
        for q in item['queries']:
            hits = search_commons(q, max_results=6)
            for h in hits:
                title = h.get('title', '')
                if title in seen_titles:
                    continue
                seen_titles.add(title)
                imginfo = h.get('imageinfo', [{}])[0]
                width = imginfo.get('width', 0)
                height = imginfo.get('height', 0)
                if width <= 0 or height <= 0:
                    continue
                ratio = width / height
                # Landscape only (1.2 to 2.2)
                if 1.2 <= ratio <= 2.2 and width >= 1000:
                    ext = imginfo.get('extmetadata', {})
                    candidates.append({
                        'title': title,
                        'width': width,
                        'height': height,
                        'ratio': round(ratio, 2),
                        'thumburl': imginfo.get('thumburl'),
                        'thumbwidth': imginfo.get('thumbwidth'),
                        'thumbheight': imginfo.get('thumbheight'),
                        'descriptionurl': imginfo.get('descriptionurl'),
                        'url': imginfo.get('url'),
                        'artist': ext.get('Artist', {}).get('value', 'Wikimedia Commons contributor'),
                        'license': ext.get('LicenseShortName', {}).get('value', 'CC BY-SA'),
                        'license_url': ext.get('LicenseUrl', {}).get('value', 'https://creativecommons.org/licenses/')
                    })
            time.sleep(0.8) # rate limiting protection
        print(f"  Found {len(candidates)} landscape candidates")
        results[slug] = {
            'id': pid,
            'slug': slug,
            'candidates': candidates
        }

    with open('ops/batch8a_candidates.json', 'w', encoding='utf-8') as f:
        json.dump(results, f, indent=2, ensure_ascii=False)
    print("\nSaved candidates to ops/batch8a_candidates.json")

if __name__ == '__main__':
    main()

# -*- coding: utf-8 -*-
"""
VietnamGuide Batch 8A: Targeted search for the 7 missing targets
"""
import urllib.request
import urllib.parse
import json
import ssl
import sys
import time

sys.stdout.reconfigure(encoding='utf-8')

MISSING_TARGETS = [
    {
        'id': 521,
        'slug': 'hanoi-to-ha-giang-transport',
        'queries': ['Ha Giang bus', 'Ha Giang city Vietnam', 'Road to Ha Giang']
    },
    {
        'id': 522,
        'slug': 'ha-giang-safety-guide',
        'queries': ['Ma Pi Leng pass road', 'Dong Van karst plateau loop', 'Ha Giang motorcycle']
    },
    {
        'id': 526,
        'slug': 'best-time-for-northern-vietnam',
        'queries': ['Sapa rice terraces foggy', 'Mu Cang Chai harvest', 'Halong Bay limestone sunny']
    },
    {
        'id': 648,
        'slug': 'tipping-in-vietnam',
        'queries': ['Vietnamese dong', 'Vietnam currency', 'Banknotes of Vietnam']
    },
    {
        'id': 686,
        'slug': 'tap-water-in-vietnam',
        'queries': ['Vietnamese iced tea', 'Vietnamese coffee street', 'Hanoi street vendor drink']
    },
    {
        'id': 1037,
        'slug': 'pu-luong-trekking-routes-guide',
        'queries': ['Pu Luong', 'Pu Luong Vietnam', 'Ba Thuoc Thanh Hoa']
    },
    {
        'id': 1068,
        'slug': 'vietnam-sim-card-airport-vs-city',
        'queries': ['Viettel store', 'Vinaphone Hanoi', 'Viettel shop', 'Mobile phone shop Vietnam']
    },
    {
        'id': 1235,
        'slug': 'vietnam-sleeper-bus-survival-guide',
        'queries': ['Sleeper bus interior Vietnam', 'Bus interior Vietnam', 'Night bus Vietnam']
    },
    {
        'id': 1277,
        'slug': 'phu-quoc-ferry-guide',
        'queries': ['Superdong', 'Phu Quoc boat', 'Bai Vong port Phu Quoc', 'Rach Gia ferry']
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
    print(f"=== TARGETED RE-SEARCH FOR MISSING TARGETS ===")
    with open('ops/batch8a_candidates.json', 'r', encoding='utf-8') as f:
        existing = json.load(f)

    for item in MISSING_TARGETS:
        slug = item['slug']
        pid = item['id']
        print(f"\nTarget {pid}: {slug}")
        candidates = existing.get(slug, {}).get('candidates', [])
        seen_titles = {c['title'] for c in candidates}
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
            time.sleep(0.9)
        print(f"  Total landscape candidates now: {len(candidates)}")
        existing[slug] = {
            'id': pid,
            'slug': slug,
            'candidates': candidates
        }

    with open('ops/batch8a_candidates.json', 'w', encoding='utf-8') as f:
        json.dump(existing, f, indent=2, ensure_ascii=False)
    print("\nUpdated ops/batch8a_candidates.json")

if __name__ == '__main__':
    main()

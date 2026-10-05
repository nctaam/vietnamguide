# -*- coding: utf-8 -*-
"""
VietnamGuide: Refine specific Batch 8A targets with authentic real photographs
"""
import urllib.request
import urllib.parse
import json
import ssl
import sys
import time

sys.stdout.reconfigure(encoding='utf-8')

REFINEMENTS = {
    'grab-in-vietnam-guide': [
        'Motorcycles street Hanoi',
        'Traffic in Hanoi Vietnam motorcycles',
        'Scooters street Hanoi Vietnam'
    ],
    'tipping-in-vietnam': [
        'Vietnamese dong notes',
        'Vietnam dong currency banknotes',
        'Cafe street Hanoi Vietnam'
    ],
    'tap-water-in-vietnam': [
        'Tra da vinh yen',
        'Iced tea street Vietnam',
        'Street tea stall Hanoi'
    ],
    'vietnam-sim-card-airport-vs-city': [
        'Street shop Hanoi electronics',
        'Hanoi street market vendor',
        'Shopping street Hanoi Vietnam'
    ],
    'vietnam-plug-adapter-electricity-guide': [
        'Europlug socket',
        'Electrical outlet Type C',
        'Schuko socket'
    ],
    'vietnam-night-train-safety-tips': [
        'Reunification Express passenger train',
        'Vietnam Railways train station Hanoi',
        'Hanoi Railway Station train platform'
    ],
    'vietnam-sleeper-bus-survival-guide': [
        'Bus in Vietnam',
        'Intercity bus Vietnam',
        'Thaco bus Vietnam passenger'
    ],
    'vietnam-domestic-flights-guide': [
        'Vietnam Airlines Airbus A321',
        'Vietnam Airlines Noi Bai',
        'Tan Son Nhat airport aircraft'
    ]
}

def search_commons(query):
    params = {
        'action': 'query',
        'format': 'json',
        'generator': 'search',
        'gsrsearch': query,
        'gsrnamespace': '6',
        'gsrlimit': '6',
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
        print(f"Error {query}: {e}")
        return []

def main():
    for slug, queries in REFINEMENTS.items():
        print(f"\n=== Refinement for: {slug} ===")
        found = []
        for q in queries:
            hits = search_commons(q)
            for h in hits:
                title = h.get('title', '')
                if title.lower().endswith('.pdf') or title.lower().endswith('.svg'):
                    continue
                info = h.get('imageinfo', [{}])[0]
                w = info.get('width', 0)
                h_dim = info.get('height', 0)
                if w <= 0 or h_dim <= 0:
                    continue
                ratio = w / h_dim
                if 1.2 <= ratio <= 2.2 and w >= 1000:
                    ext = info.get('extmetadata', {})
                    found.append({
                        'title': title,
                        'w': w,
                        'h': h_dim,
                        'ratio': round(ratio, 2),
                        'thumburl': info.get('thumburl'),
                        'thumbwidth': info.get('thumbwidth'),
                        'thumbheight': info.get('thumbheight'),
                        'desc_url': info.get('descriptionurl'),
                        'artist': ext.get('Artist', {}).get('value', 'Contributor'),
                        'license': ext.get('LicenseShortName', {}).get('value', 'CC BY-SA')
                    })
            time.sleep(0.8)
        print(f"Found {len(found)} candidates:")
        for f_it in found[:4]:
            print(f"  * {f_it['title']} ({f_it['w']}x{f_it['h']}, ratio {f_it['ratio']}) - {f_it['license']}")

        with open(f"ops/refine_{slug}.json", 'w', encoding='utf-8') as out_f:
            json.dump(found, out_f, indent=2, ensure_ascii=False)

if __name__ == '__main__':
    main()

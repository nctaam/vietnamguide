# -*- coding: utf-8 -*-
import urllib.request
import json
import re

urls = [
    'https://vietnamguide.net/plan/vietnam-evisa/',
    'https://vietnamguide.net/plan/sim-esim-vietnam/',
    'https://vietnamguide.net/plan/vietnam-airport-arrival-checklist/',
    'https://vietnamguide.net/transport/hanoi-to-sapa-transport/',
    'https://vietnamguide.net/destinations/ha-giang-easy-rider-vs-self-drive/',
    'https://vietnamguide.net/destinations/trang-an-vs-tam-coc/',
    'https://vietnamguide.net/plan/vietnam-travel-cost/',
]

for url in urls:
    print(f"\n=== URL: {url} ===")
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
    html = urllib.request.urlopen(req, timeout=10).read().decode('utf-8')
    matches = re.findall(r'<script type=["\']application/ld\+json["\'][^>]*>(.*?)</script>', html, re.DOTALL)
    for i, m in enumerate(matches):
        try:
            data = json.loads(m)
            graph = data.get('@graph', [data])
            types = [n.get('@type') for n in graph if isinstance(n, dict)]
            print(f"  Script #{i+1} types: {types}")
            for n in graph:
                if isinstance(n, dict) and n.get('@type') == 'FAQPage':
                    entities = n.get('mainEntity', [])
                    print(f"    -> Found FAQPage with {len(entities)} questions:")
                    for q in entities:
                        print(f"       * {q.get('name')}")
                if isinstance(n, dict) and n.get('@type') == 'HowTo':
                    steps = n.get('step', [])
                    print(f"    -> Found HowTo '{n.get('name')}' with {len(steps)} steps:")
                    for s in steps:
                        print(f"       * {s.get('name')}")
        except Exception as e:
            print(f"  Script #{i+1} error: {e}")


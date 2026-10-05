# -*- coding: utf-8 -*-
import urllib.request
import json
import re

url = 'https://vietnamguide.net/plan/vietnam-evisa/'
req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
html = urllib.request.urlopen(req).read().decode('utf-8')
matches = re.findall(r'<script type=["\']application/ld\+json["\'][^>]*>(.*?)</script>', html, re.DOTALL)
for m in matches:
    data = json.loads(m)
    graph = data.get('@graph', [data])
    for n in graph:
        if isinstance(n, dict) and n.get('@type') in ['Person', 'Organization']:
            print(f"=== Type: {n.get('@type')} -> {n.get('name')} ===")
            print(json.dumps(n, indent=2))

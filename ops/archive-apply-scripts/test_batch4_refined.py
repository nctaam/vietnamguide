# -*- coding: utf-8 -*-
import urllib.request
import urllib.parse
import json
import ssl
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

test_queries = [
    'Cau Rach Mieu Ben Tre',
    'Ham Luong Ben Tre',
    'Ben Tre Vietnam river',
    'Cam Ranh Bay Vietnam',
    'Bai Dai Cam Ranh',
    'Nha hat Hai Phong',
    'Hai Phong Opera House',
    'Tam Dao Vietnam',
    'Tam Dao',
    'Du Gia',
    'Ha Giang Du Gia',
    'Thac Pa Sy',
    'Mang Den'
]

def search(q):
    params = {
        'action': 'query',
        'list': 'search',
        'srsearch': q,
        'srnamespace': '6',
        'srlimit': '3',
        'format': 'json'
    }
    url = f"https://commons.wikimedia.org/w/api.php?{urllib.parse.urlencode(params)}"
    req = urllib.request.Request(url, headers={'User-Agent': 'VietnamGuideBot/1.0 (https://vietnamguide.net; contact@vietnamguide.net)'})
    ctx = ssl.create_default_context()
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=10) as resp:
            data = json.loads(resp.read().decode('utf-8'))
        return [r['title'].replace('File:', '') for r in data.get('query', {}).get('search', [])]
    except Exception as e:
        return []

for q in test_queries:
    res = search(q)
    print(f"Query: {q}")
    for r in res:
        print(f"  * {r}")

# -*- coding: utf-8 -*-
import urllib.request
import urllib.parse
import json
import ssl
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

test_queries = [
    'Street food Ho Chi Minh City',
    'Com tam Saigon',
    'Banh mi Ho Chi Minh City',
    'Halong Bay islands',
    'Ha Long Bay cruise',
    'Goi cuon',
    'Banh cuon'
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
    with urllib.request.urlopen(req, context=ctx, timeout=10) as resp:
        data = json.loads(resp.read().decode('utf-8'))
    return [r['title'].replace('File:', '') for r in data.get('query', {}).get('search', [])]

for q in test_queries:
    res = search(q)
    print(f"Query: {q}")
    for r in res:
        print(f"  * {r}")

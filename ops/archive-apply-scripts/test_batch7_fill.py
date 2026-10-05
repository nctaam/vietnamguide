# -*- coding: utf-8 -*-
import urllib.request
import urllib.parse
import json
import ssl
import sys

sys.stdout.reconfigure(encoding='utf-8')

queries = {
    'pu-luong': ['Sunset in Pu Luong- Nov 2025.jpg', 'Pu Luong nature reserve', 'Pu Luong Thanh Hoa'],
    'ninh-binh-things': ['Hang Mua', 'Mua Cave', 'Dinh Hang Mua'],
    'ha-giang-budget': ['Ma Pi Leng Pass winding road Ha Giang Vietnam.jpg', 'Tham Ma pass', 'Du Gia Ha Giang']
}

def search(q):
    url = f"https://commons.wikimedia.org/w/api.php?action=query&list=search&srsearch={urllib.parse.quote(q)}&srnamespace=6&srlimit=3&format=json"
    req = urllib.request.Request(url, headers={'User-Agent': 'VietnamGuideBot/1.0 (contact@vietnamguide.net)'})
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            data = json.loads(resp.read().decode('utf-8'))
        return [r['title'].replace('File:', '') for r in data.get('query', {}).get('search', []) if not r['title'].lower().endswith(('.pdf', '.svg', '.djvu'))]
    except Exception as e:
        return [str(e)]

for cat, qlist in queries.items():
    print(f"\nCategory: {cat}")
    for q in qlist:
        print(f"  ({q}) -> {search(q)}")

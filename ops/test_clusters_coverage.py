# -*- coding: utf-8 -*-
"""
Test travel cluster mapping across all 102 URLs in sitemap
"""

import json

with open('ops/meta_inventory.json', 'r', encoding='utf-8') as f:
    pages = json.load(f)

def find_cluster(path):
    normalized = path.lower().strip('/')

    if any(x in normalized for x in ['hanoi', 'old-quarter', 'west-lake', 'french-quarter', 'northern']):
        return 'northern_triangle'

    if any(x in normalized for x in ['ha-long', 'lan-ha', 'cat-ba', 'bai-tu-long']):
        return 'ha_long_bay'

    if any(x in normalized for x in ['ninh-binh', 'tam-coc', 'trang-an']):
        return 'ninh_binh'

    if any(x in normalized for x in ['sapa', 'ha-giang', 'mu-cang-chai', 'pu-luong', 'rice-terraces']):
        return 'northern_highlands'

    if any(x in normalized for x in ['da-nang', 'hoi-an', 'hue', 'cham-islands', 'phong-nha']):
        return 'central_heritage'

    if any(x in normalized for x in ['ho-chi-minh', 'mekong', 'cu-chi']):
        return 'southern_delta'

    if any(x in normalized for x in ['phu-quoc', 'con-dao', 'nha-trang', 'quy-nhon', 'mui-ne', 'ly-son', 'beach', 'island']):
        return 'coastal_islands'

    return 'national_circuit'

cluster_counts = {}
cluster_pages = {}

for p in pages:
    slug = p['post_name']
    cid = find_cluster(slug)
    cluster_counts[cid] = cluster_counts.get(cid, 0) + 1
    if cid not in cluster_pages:
        cluster_pages[cid] = []
    cluster_pages[cid].append(slug)

print("=== CLUSTER DISTRIBUTION ACROSS ALL 102 PAGES ===")
for cid, cnt in sorted(cluster_counts.items(), key=lambda x: -x[1]):
    print(f"{cid:20s}: {cnt:2d} pages")

print("\n=== PAGES IN NATIONAL_CIRCUIT (FALLBACK/CROSS-REGIONAL) ===")
for slug in cluster_pages.get('national_circuit', []):
    print(f"  /{slug}/")

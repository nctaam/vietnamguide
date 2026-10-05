# -*- coding: utf-8 -*-
import json
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

with open('ops/pages_without_images.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

# Load batch 1 & batch 2 deployed IDs
with open('ops/batch1_final_images.json', 'r', encoding='utf-8') as f:
    b1 = json.load(f)
b1_ids = {it['post_id'] for it in b1}

with open('ops/batch2_final_images.json', 'r', encoding='utf-8') as f:
    b2 = json.load(f)
b2_ids = {it['post_id'] for it in b2}

completed_ids = b1_ids | b2_ids
print(f"Total already deployed: {len(completed_ids)} (Batch 1: {len(b1_ids)}, Batch 2: {len(b2_ids)})")

# Exclude static policy pages that do not need featured images (e.g. privacy-policy, contact, terms, about)
exclude_slugs = {
    'privacy-policy', 'contact', 'about', 'editorial-policy', 'source-update-policy',
    'affiliate-disclosure', 'affiliate-review-policy', 'newsletter', 'home', 'destinations', 'itineraries', 'plan', 'costs', 'compare'
}

remaining = []
for cluster_name, items in data['clusters'].items():
    for it in items:
        if it['id'] in completed_ids:
            continue
        if it['slug'] in exclude_slugs:
            continue
        remaining.append((cluster_name, it))

print(f"Total actionable pages remaining without images: {len(remaining)}\n")

clusters_remaining = {}
for cluster_name, it in remaining:
    clusters_remaining.setdefault(cluster_name, []).append(it)

for cname, items in clusters_remaining.items():
    print(f"=== Cluster: {cname} ({len(items)} items) ===")
    for it in items:
        print(f"  [{it['id']}] {it['slug']} | {it['title']}")
    print()

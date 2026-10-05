# -*- coding: utf-8 -*-
import json, sys

with open('ops/content_audit_results.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

thin = [x for x in data if x['word_count'] < 1000]

# Group by category
weather = [x for x in thin if 'vietnam-in-' in x['slug']]
where_to_stay = [x for x in thin if 'where-to-stay-' in x['slug']]
transport = [x for x in thin if '-transport' in x['slug'] or '-ferry' in x['slug'] or '-bus-' in x['slug'] or '-train-' in x['slug']]
guides = [x for x in thin if x not in weather and x not in where_to_stay and x not in transport]

print(f"Thin articles breakdown ({len(thin)} total):")
print(f"  Weather/Seasonal Guides: {len(weather)}")
for x in weather:
    print(f"    - {x['slug']} ({x['word_count']}w)")

print(f"\n  Accommodation/Base Guides: {len(where_to_stay)}")
for x in where_to_stay:
    print(f"    - {x['slug']} ({x['word_count']}w)")

print(f"\n  Transport/Logistics Routes: {len(transport)}")
for x in transport:
    print(f"    - {x['slug']} ({x['word_count']}w)")

print(f"\n  Practical/Activity Guides: {len(guides)}")
for x in guides:
    print(f"    - {x['slug']} ({x['word_count']}w)")

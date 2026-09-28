# -*- coding: utf-8 -*-
import json, sys

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

with open('ops/content_audit_results.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

data.sort(key=lambda x: x['word_count'])

thin = [x for x in data if x['word_count'] < 1000]
mid_thin = [x for x in data if 1000 <= x['word_count'] < 1200]

print(f"Total articles: {len(data)}")
print(f"Articles < 1000 words: {len(thin)}")
print(f"Articles 1000-1200 words: {len(mid_thin)}")

print("\nAll articles < 1000 words:")
for idx, x in enumerate(thin, 1):
    print(f"  {idx:>2}. {x['slug']:<45} (ID:{x['id']:>5}) | Words: {x['word_count']:>4} | QS: {x['quality_score']:>4.1f} | EDI: {x['edi']:>4.1f}")

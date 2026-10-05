# -*- coding: utf-8 -*-
import sys
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
import json

with open('ops/content_audit_results.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

# Distribution analysis
hls_dist = {}
for r in data:
    hls = r['hls_score']
    hls_dist[str(hls)] = hls_dist.get(str(hls), 0) + 1

print('=== HLS Score Distribution ===')
for k in sorted(hls_dist.keys(), key=lambda x: int(x)):
    print(f'  HLS={k}: {hls_dist[k]} articles')

# Thin content details
print('\n=== Thin Content (< 1000 words) - Top 25 ===')
thin = [r for r in data if r['word_count'] < 1000]
thin.sort(key=lambda x: x['word_count'])
for r in thin[:25]:
    title = r["title"][:55]
    wc = r["word_count"]
    hls = r["hls_score"]
    print(f'  {wc:>5}w | HLS={hls:>3} | {title}')
print(f'  ... total: {len(thin)} thin articles')

# Low EDI details
print('\n=== Low EDI (< 5.0) ===')
low_edi = [r for r in data if r['edi'] < 5.0]
low_edi.sort(key=lambda x: x['edi'])
for r in low_edi:
    title = r["title"][:55]
    edi = r["edi"]
    hls = r["hls_score"]
    wc = r["word_count"]
    print(f'  EDI={edi:>5.1f} | HLS={hls:>3} | W={wc:>5} | {title}')

# Failed count by HLS
print('\n=== Failed Articles Count by HLS ===')
failed = [r for r in data if r['hls_score'] < 100]
failed.sort(key=lambda x: x['hls_score'])
print(f'Total failed: {len(failed)}')
for hls_val in [10, 15, 20, 30, 40, 50, 60, 70, 80, 90]:
    count = sum(1 for r in failed if r['hls_score'] == hls_val)
    if count:
        print(f'  HLS={hls_val}: {count} articles')

# Top priorities: HLS <= 60 articles
print('\n=== CRITICAL: HLS <= 60 (worst quality) ===')
critical = [r for r in data if r['hls_score'] <= 60]
critical.sort(key=lambda x: x['hls_score'])
for r in critical:
    title = r["title"][:50]
    hls = r["hls_score"]
    edi = r["edi"]
    cv = r["cv"]
    wc = r["word_count"]
    qs = r["quality_score"]
    slug = r["slug"]
    print(f'  Q={qs:<6} HLS={hls:<4} EDI={edi:<6.1f} CV={cv:<5.2f} W={wc:<6} {title}')
    print(f'           slug: {slug}')

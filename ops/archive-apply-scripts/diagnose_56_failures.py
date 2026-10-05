# -*- coding: utf-8 -*-
import sys, os, json, re
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_ai_slop_linter import analyze_text
from fast_content_audit import clean_text

with open('ops/vg_all_posts.json', 'r', encoding='utf-8') as f:
    posts = json.load(f)

with open('ops/content_audit_results.json', 'r', encoding='utf-8') as f:
    results = json.load(f)

failed_slugs = {r['slug']: r for r in results if not r['passed']}
print(f"Total failed: {len(failed_slugs)}")

failure_causes = {}

for p in posts:
    slug = p.get('post_name')
    if slug not in failed_slugs:
        continue
    
    text = clean_text(p.get('post_content', ''))
    analysis = analyze_text(text, source_name=slug)
    
    reasons = []
    if len(analysis.get('tier1_violations', [])) > 0:
        reasons.append(f"tier1({len(analysis['tier1_violations'])})")
    if len(analysis.get('tier3_violations', [])) >= 3:
        reasons.append(f"heavy_signposting({len(analysis['tier3_violations'])})")
    for t in range(4, 13):
        k = f'tier{t}_violations'
        if len(analysis.get(k, [])) > 0:
            reasons.append(f"tier{t}({len(analysis[k])})")
    if len(analysis.get('syntactic_monotony_violations', [])) > 0:
        reasons.append(f"syntactic_monotony({len(analysis['syntactic_monotony_violations'])})")
    if len(analysis.get('repetitive_openers_violations', [])) > 0:
        reasons.append(f"rep_openers({len(analysis['repetitive_openers_violations'])})")
    if len(analysis.get('repetitive_bigram_violations', [])) > 0:
        reasons.append(f"rep_bigrams({len(analysis['repetitive_bigram_violations'])})")
    if analysis.get('edi', 0) < 4.0 and not (analysis.get('evidence_count', 0) >= 10 and analysis.get('edi', 0) >= 3.0):
        reasons.append(f"low_edi({analysis.get('edi', 0):.1f})")
    if analysis.get('hls_score', 0) < 80:
        reasons.append(f"low_hls({analysis.get('hls_score', 0)})")
    
    for r in reasons:
        cause = r.split('(')[0]
        failure_causes[cause] = failure_causes.get(cause, 0) + 1
    
    print(f"  ID={p.get('ID'):>5} | {slug:<45} | {', '.join(reasons)}")

print("\n--- Failure causes frequency across 56 articles ---")
for c, cnt in sorted(failure_causes.items(), key=lambda x: -x[1]):
    print(f"  {c}: {cnt}")

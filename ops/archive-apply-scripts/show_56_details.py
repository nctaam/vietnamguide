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

for p in posts:
    slug = p.get('post_name')
    if slug not in failed_slugs:
        continue
    
    text = clean_text(p.get('post_content', ''))
    analysis = analyze_text(text, source_name=slug)
    
    rep_bi = analysis.get('repetitive_bigram_violations', [])
    rep_op = analysis.get('repetitive_openers_violations', [])
    t1 = analysis.get('tier1_violations', [])
    edi = analysis.get('edi', 0)
    
    details = []
    if t1:
        details.append(f"tier1: {t1}")
    if rep_bi:
        details.append(f"bigrams: {rep_bi}")
    if rep_op:
        details.append(f"openers: {rep_op}")
    if edi < 4.0:
        details.append(f"edi: {edi:.1f}")
        
    print(f"{slug} (ID: {p.get('ID')}): {' | '.join(details)}")

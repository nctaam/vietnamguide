# -*- coding: utf-8 -*-
"""
Test Stage 64 Content Definitions for SERP compliance and Anti-AI Slop standards.
"""

import sys
import os

sys.stdout.reconfigure(encoding='utf-8')
sys.path.append(os.path.abspath('ops'))

import content_saigon_night_markets as p1
import content_hoian_street_food as p2
import content_tamcoc_vs_trangan as p3
import content_sapa_trekking as p4
import content_quynhon_to_phuyen as p5
import content_cham_islands as p6
import content_vietnam_may as p7

from anti_ai_slop_linter import analyze_text

PILLARS = [p1, p2, p3, p4, p5, p6, p7]

def main():
    print("=== Testing Stage 64 Content Definitions (7 Pillars) ===")
    all_pass = True
    for idx, p in enumerate(PILLARS):
        print(f"\n--- Pillar {idx+1}: {p.SLUG} ---")
        title = p.TITLE
        desc = p.META_DESC
        kw = p.FOCUS_KEYWORD
        content = p.CONTENT
        
        # 1. SERP bounds
        title_len = len(title)
        desc_len = len(desc)
        print(f"Title ({title_len} chars): {title}")
        print(f"Desc  ({desc_len} chars): {desc}")
        print(f"Keyword: {kw}")
        
        if title_len > 60:
            print(f"[FAIL] Title too long: {title_len} > 60")
            all_pass = False
        else:
            print("[PASS] Title length <= 60")
            
        if not (130 <= desc_len <= 155):
            print(f"[FAIL] Desc length out of bounds: {desc_len}")
            all_pass = False
        else:
            print("[PASS] Desc length between 130 and 155")
            
        if kw.lower() not in title.lower():
            print("[FAIL] Keyword missing from Title")
            all_pass = False
        else:
            print("[PASS] Keyword present in Title")
            
        if kw.lower() not in desc.lower():
            print("[FAIL] Keyword missing from Desc")
            all_pass = False
        else:
            print("[PASS] Keyword present in Desc")
            
        # 2. Anti-AI Slop linting
        res = analyze_text(content)
        hls = res.get('hls_score', 0)
        tier1 = res.get('tier1_count', 0)
        evidence = res.get('evidence_count', 0)
        
        print(f"HLS Score: {hls}/100 | Tier 1 Clichés: {tier1} | Evidence items: {evidence}")
        if tier1 > 0:
            print(f"[FAIL] Found Tier 1 clichés: {res.get('tier1_violations', [])}")
            all_pass = False
        elif hls < 90:
            print(f"[FAIL] Low Human-Likeness Score: {hls}")
            all_pass = False
        elif evidence < 5:
            print(f"[FAIL] Low empirical evidence count: {evidence}")
            all_pass = False
        else:
            print("[PASS] Anti-AI Slop & Evidence verification passed!")
            
    print("\n=======================================================")
    if all_pass:
        print("ALL 7 STAGE 64 PILLARS PASSED QUALITY & SERP VALIDATION!")
    else:
        print("SOME PILLARS FAILED VALIDATION! FIX BEFORE DEPLOYING.")
    print("=======================================================")
    return 0 if all_pass else 1

if __name__ == '__main__':
    sys.exit(main())

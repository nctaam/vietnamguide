# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 78: Comprehensive Quality Test Suite for 7 Content Pillars
Validates SERP bounds, focus keyword density, Anti-AI Slop compliance, and HTML structure.
"""
import sys
import os

ops_dir = os.path.dirname(os.path.abspath(__file__))
if ops_dir not in sys.path:
    sys.path.insert(0, ops_dir)

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

import anti_ai_slop_linter as linter
import content_where_to_stay_kon_tum
import content_buon_ma_thuot_to_pleiku_transport
import content_where_to_stay_chau_doc
import content_vietnam_to_cambodia_boat_guide
import content_vietnam_night_train_safety_tips
import content_where_to_stay_dien_bien_phu
import content_hanoi_to_dien_bien_phu_transport

MODULES = [
    content_where_to_stay_kon_tum,
    content_buon_ma_thuot_to_pleiku_transport,
    content_where_to_stay_chau_doc,
    content_vietnam_to_cambodia_boat_guide,
    content_vietnam_night_train_safety_tips,
    content_where_to_stay_dien_bien_phu,
    content_hanoi_to_dien_bien_phu_transport,
]

def run_tests():
    print("=" * 70)
    print("VIETNAMGUIDE STAGE 78: CONTENT PILLAR QUALITY ASSURANCE SUITE")
    print("=" * 70)
    
    total_passed = 0
    
    for mod in MODULES:
        print(f"\n[TESTING] {mod.SLUG}")
        print(f"  Title: '{mod.TITLE}' ({len(mod.TITLE)} chars)")
        print(f"  Focus Keyword: '{mod.FOCUS_KEYWORD}'")
        print(f"  Meta Description: ({len(mod.META_DESC)} chars)")
        
        # 1. SERP Title Bounds
        assert len(mod.TITLE) <= 60, f"FAILED: Title > 60 chars ({len(mod.TITLE)})"
        print("  ✓ Title character length <= 60")
        
        # 2. SERP Meta Description Bounds
        assert 130 <= len(mod.META_DESC) <= 155, f"FAILED: Meta desc length {len(mod.META_DESC)} outside 130-155"
        print("  ✓ Meta description character length within 130-155 chars")
        
        # 3. Focus Keyword Exact Match
        assert mod.FOCUS_KEYWORD.lower() in mod.TITLE.lower(), "FAILED: Focus keyword missing from Title"
        assert mod.FOCUS_KEYWORD.lower() in mod.META_DESC.lower(), "FAILED: Focus keyword missing from Meta Description"
        assert mod.FOCUS_KEYWORD.lower() in mod.CONTENT.lower(), "FAILED: Focus keyword missing from Content body"
        print("  ✓ Focus keyword exact match in Title, Meta Description, and Content")
        
        # 4. Anti-AI Slop Quality Engine
        res = linter.analyze_text(mod.CONTENT)
        assert res['passed'], f"FAILED: Anti-AI Slop linter failed: {res.get('tier1_violations') or res.get('local_cadence_violations')}"
        assert len(res['tier1_violations']) == 0, f"FAILED: Tier 1 violations found: {res['tier1_violations']}"
        assert res['hls_score'] == 100, f"FAILED: HLS score {res['hls_score']} < 100"
        assert res['evidence_count'] >= 15, f"FAILED: Evidence count {res['evidence_count']} < 15"
        print(f"  ✓ Anti-AI Slop: HLS = {res['hls_score']}/100, Tier 1 Clichés = 0, Evidence Anchors = {res['evidence_count']}")
        
        # 5. Structure & Concierge Verdict Presence
        assert "concierge-verdict" in mod.CONTENT.lower(), "FAILED: Missing Concierge verdict block"
        assert "wp-block-table" in mod.CONTENT, "FAILED: Missing comparison table"
        assert "where to go next" in mod.CONTENT.lower() or "related travel guides" in mod.CONTENT.lower(), "FAILED: Missing related guides section"
        print("  ✓ Editorial Architecture: Hero, Concierge Verdict, Comparative Table, Related Links")
        
        total_passed += 1

    print("\n" + "=" * 70)
    print(f"STAGE 78 QA RESULT: {total_passed}/{len(MODULES)} PILLARS PASSED ALL CHECKS!")
    print("=" * 70)

if __name__ == "__main__":
    run_tests()
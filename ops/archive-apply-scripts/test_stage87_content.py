# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 87 Content QA Test Suite
Validates all 7 authored pillars against SEO bounds, Anti-AI Slop rules,
evidence anchor counts, and schema markup contracts.
"""

import sys
import os
import re

ops_dir = os.path.dirname(os.path.abspath(__file__))
if ops_dir not in sys.path:
    sys.path.insert(0, ops_dir)

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

import anti_ai_slop_linter as linter
import content_where_to_stay_in_yen_minh
import content_where_to_stay_in_quang_ngai
import content_ha_giang_to_dong_van_transport
import content_rach_gia_to_phu_quoc_ferry
import content_can_tho_to_phu_quoc_transport
import content_quy_nhon_to_hoi_an_transport
import content_da_lat_to_pleiku_transport

PILLARS = [
    content_where_to_stay_in_yen_minh,
    content_where_to_stay_in_quang_ngai,
    content_ha_giang_to_dong_van_transport,
    content_rach_gia_to_phu_quoc_ferry,
    content_can_tho_to_phu_quoc_transport,
    content_quy_nhon_to_hoi_an_transport,
    content_da_lat_to_pleiku_transport,
]

def main():
    print("=" * 70)
    print("VIETNAMGUIDE STAGE 87: CONTENT PILLAR QUALITY ASSURANCE SUITE")
    print("=" * 70)

    for mod in PILLARS:
        name = mod.SLUG
        print(f"\n--- Testing Pillar: {name} ---")

        # Title checks
        title = mod.TITLE
        assert len(title) <= 60, f"Title too long ({len(title)}): {title}"
        print(f"  [PASS] Title length: {len(title)} chars ('{title}')")

        # Meta desc checks
        desc = mod.META_DESC
        assert 130 <= len(desc) <= 155, f"Meta description out of bounds ({len(desc)}): {desc}"
        print(f"  [PASS] Meta desc length: {len(desc)} chars")

        # Focus keyword
        kw = mod.FOCUS_KEYWORD.lower()
        assert kw in title.lower(), f"Keyword '{kw}' not in title: {title}"
        assert kw in desc.lower(), f"Keyword '{kw}' not in meta desc: {desc}"
        assert kw in mod.CONTENT.lower(), f"Keyword '{kw}' not in content body"
        print(f"  [PASS] Focus keyword exact matches verified ('{mod.FOCUS_KEYWORD}')")

        # Structure checks
        content = mod.CONTENT
        assert '<figure class="wp-block-table">' in content, "Missing wp-block-table"
        assert '<div class="vg-concierge-verdict">' in content, "Missing concierge verdict"
        assert '<div class="vg-guide-hero">' in content, "Missing guide hero"
        assert 'Updated September 26, 2026' in content, "Missing or incorrect date badge"
        assert 'where to go next' in content.lower(), "Missing where to go next kicker"
        assert '<details class="wp-block-details">' in content, "Missing FAQ details block"
        print("  [PASS] Required HTML structures present")

        # Anti-AI Slop check
        res = linter.analyze_text(mod.CONTENT)
        assert res['passed'], f"FAILED: Anti-AI Slop linter failed: {res.get('tier1_violations') or res.get('local_cadence_violations')}"
        assert len(res['tier1_violations']) == 0, f"FAILED: Tier 1 violations found: {res['tier1_violations']}"
        assert res['hls_score'] == 100, f"FAILED: HLS score {res['hls_score']} < 100"
        assert res['evidence_count'] >= 15, f"FAILED: Evidence count {res['evidence_count']} < 15"
        print(f"  [PASS] Anti-AI Slop: HLS={res['hls_score']}/100, Tier 1 Clichés=0, Evidence Anchors={res['evidence_count']}")

    print("\n" + "=" * 70)
    print("STAGE 87 QA RESULT: ALL 7 PILLARS PASSED QUALITY AUDIT!")
    print("=" * 70)

if __name__ == '__main__':
    main()

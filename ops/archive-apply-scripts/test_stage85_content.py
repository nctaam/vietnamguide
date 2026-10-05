# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 85 Content QA Test Suite
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
import content_where_to_stay_in_du_gia
import content_where_to_stay_in_mang_den
import content_nha_trang_to_da_lat_transport
import content_ninh_binh_to_phong_nha_transport
import content_cao_bang_to_ba_be_transport
import content_ho_chi_minh_city_to_chau_doc_transport
import content_where_to_stay_in_an_giang

PILLARS = [
    content_where_to_stay_in_du_gia,
    content_where_to_stay_in_mang_den,
    content_nha_trang_to_da_lat_transport,
    content_ninh_binh_to_phong_nha_transport,
    content_cao_bang_to_ba_be_transport,
    content_ho_chi_minh_city_to_chau_doc_transport,
    content_where_to_stay_in_an_giang,
]

def test_pillar(mod):
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

    return True

def main():
    print("=" * 70)
    print("VIETNAMGUIDE STAGE 85: CONTENT PILLAR QUALITY ASSURANCE SUITE")
    print("=" * 70)
    failed = 0
    for mod in PILLARS:
        try:
            test_pillar(mod)
        except Exception as e:
            print(f"  [FAIL] {mod.SLUG}: {e}")
            failed += 1

    print("\n" + "=" * 70)
    if failed == 0:
        print(f"STAGE 85 QA RESULT: ALL {len(PILLARS)} PILLARS PASSED QUALITY AUDIT!")
        print("=" * 70)
        sys.exit(0)
    else:
        print(f"STAGE 85 QA RESULT: {failed} PILLARS FAILED QUALITY AUDIT!")
        print("=" * 70)
        sys.exit(1)

if __name__ == '__main__':
    main()

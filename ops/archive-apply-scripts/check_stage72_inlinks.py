# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 72: Live HTTP Inlink Verification
Validates that every one of the 28 planned inbound links is present in live rendered HTML over HTTP.
"""

import urllib.request
import json
import re
import sys
import os

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    ops_dir = os.path.dirname(os.path.abspath(__file__))
    ops_file = os.path.join(ops_dir, 'stage72_mesh_ops.json')
    ops = json.load(open(ops_file, encoding='utf-8'))

    # Load parent mappings to construct live URLs
    parent_map = {
        'pu-luong-travel-guide': 'destinations',
        'best-time-for-northern-vietnam': 'plan',
        'where-to-stay-in-vietnam-base-decisions': 'destinations',
        'best-day-trips-from-hanoi': 'destinations',
        'ninh-binh-without-rushing': 'destinations',
        'con-dao-travel-guide': 'destinations',
        'con-dao-vs-phu-quoc': 'compare',
        'where-to-stay-in-phu-quoc': 'destinations',
        'vietnam-first-trip-planning-checklist': 'plan',
        '10-days-in-vietnam': 'itineraries',
        '14-days-in-vietnam': 'itineraries',
        'health-travel-insurance-vietnam': 'plan',
        'da-nang-to-hue-train-vs-car': 'itineraries',
        '7-days-in-vietnam': 'itineraries',
        'hue-imperial-city-guide': 'destinations',
        'hoi-an-ancient-town-guide': 'destinations',
        'vietnam-travel-cost': 'costs',
        'ho-chi-minh-city-travel-guide': 'destinations',
        'mekong-delta-overnight-vs-day-trip': 'compare',
        'ho-chi-minh-city-in-2-days': 'itineraries',
        'vietnam-train-travel': 'plan',
        'phong-nha-to-hue-transport': 'plan',
        'hanoi-travel-guide': 'destinations',
    }

    pillar_urls = {
        'where-to-stay-in-mai-chau': '/destinations/where-to-stay-in-mai-chau/',
        'where-to-stay-in-pu-luong': '/destinations/where-to-stay-in-pu-luong/',
        'where-to-stay-in-con-dao': '/destinations/where-to-stay-in-con-dao/',
        'vietnam-family-travel-guide': '/itineraries/vietnam-family-travel-guide/',
        'central-vietnam-itinerary': '/itineraries/central-vietnam-itinerary/',
        'ho-chi-minh-city-mekong-budget': '/costs/ho-chi-minh-city-mekong-budget/',
        'hanoi-to-hue-transport': '/plan/hanoi-to-hue-transport/',
    }

    print("=" * 70)
    print("STAGE 72: LIVE HTTP INBOUND LINK VERIFICATION (28 LINKS)")
    print("=" * 70)

    # Cache fetched page HTMLs: {slug: html}
    page_htmls = {}
    total_verified = 0
    total_expected = 0

    for op in ops:
        slug = op['slug']
        parent = parent_map[slug]
        url = f"https://vietnamguide.net/{parent}/{slug}/"

        if slug not in page_htmls:
            req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
            try:
                with urllib.request.urlopen(req, timeout=12) as resp:
                    page_htmls[slug] = resp.read().decode('utf-8', errors='ignore')
            except Exception as e:
                print(f"[HTTP ERROR] Fetching {url}: {e}")
                page_htmls[slug] = ""

        html = page_htmls[slug]

        for pillar in op['pillars']:
            total_expected += 1
            target_link = pillar_urls[pillar]
            found = target_link in html
            if found:
                total_verified += 1
                print(f"  ✓ [{slug}] -> '{target_link}' VERIFIED LIVE HTTP")
            else:
                print(f"  ✗ [FAIL] [{slug}] -> '{target_link}' NOT FOUND in live page!")

    print("\n" + "=" * 70)
    print(f"STAGE 72 INLINK RESULT: {total_verified}/{total_expected} INLINKS VERIFIED LIVE OVER HTTP!")
    print("=" * 70)

    if total_verified == total_expected:
        print("PERFECT: 100% Inlink Mesh Coverage!")
    else:
        print("WARNING: Some inlinks were not verified!")
        sys.exit(1)

if __name__ == '__main__':
    main()

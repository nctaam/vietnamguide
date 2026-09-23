# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 71: Verify inbound links to 7 new pillars on live site
"""

import urllib.request
import re
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

PILLAR_URLS = {
    '/destinations/where-to-stay-in-ha-long-bay/': [
        'https://vietnamguide.net/destinations/ha-long-bay-travel-guide/',
        'https://vietnamguide.net/destinations/best-things-to-do-in-ha-long-bay/',
        'https://vietnamguide.net/compare/ha-long-bay-vs-lan-ha-bay/',
        'https://vietnamguide.net/plan/hanoi-to-ha-long-bay-transport/',
    ],
    '/destinations/where-to-stay-in-quy-nhon/': [
        'https://vietnamguide.net/destinations/quy-nhon-travel-guide/',
        'https://vietnamguide.net/destinations/best-things-to-do-in-quy-nhon/',
        'https://vietnamguide.net/destinations/best-beaches-in-vietnam/',
        'https://vietnamguide.net/plan/vietnam-train-travel/',
    ],
    '/destinations/where-to-stay-in-can-tho/': [
        'https://vietnamguide.net/destinations/mekong-delta-travel-guide/',
        'https://vietnamguide.net/plan/ho-chi-minh-city-to-can-tho-transport/',
        'https://vietnamguide.net/destinations/mekong-delta-floating-markets-guide/',
        'https://vietnamguide.net/compare/mekong-delta-overnight-vs-day-trip/',
    ],
    '/destinations/where-to-stay-in-cat-ba/': [
        'https://vietnamguide.net/destinations/cat-ba-travel-guide/',
        'https://vietnamguide.net/plan/hanoi-to-cat-ba-island-transport/',
        'https://vietnamguide.net/compare/ha-long-bay-vs-lan-ha-bay/',
        'https://vietnamguide.net/destinations/best-islands-in-vietnam/',
    ],
    '/destinations/where-to-stay-in-ha-giang/': [
        'https://vietnamguide.net/destinations/ha-giang-loop-planning-guide/',
        'https://vietnamguide.net/plan/hanoi-to-ha-giang-transport/',
        'https://vietnamguide.net/plan/ha-giang-safety-guide/',
        'https://vietnamguide.net/costs/ha-giang-loop-cost-budget/',
    ],
    '/itineraries/ho-chi-minh-city-in-2-days/': [
        'https://vietnamguide.net/destinations/ho-chi-minh-city-travel-guide/',
        'https://vietnamguide.net/destinations/best-things-to-do-in-ho-chi-minh-city/',
        'https://vietnamguide.net/destinations/saigon-street-food-guide/',
        'https://vietnamguide.net/itineraries/hanoi-in-2-days/',
    ],
    '/plan/da-nang-to-nha-trang-transport/': [
        'https://vietnamguide.net/destinations/da-nang-travel-guide/',
        'https://vietnamguide.net/destinations/nha-trang-travel-guide/',
        'https://vietnamguide.net/plan/transport-within-vietnam/',
        'https://vietnamguide.net/plan/vietnam-train-travel/',
    ],
}

def main():
    print("=" * 60)
    print("STAGE 71: INBOUND LINK VERIFICATION")
    print("=" * 60)

    total = 0
    passed = 0
    failed = 0

    for pillar_path, source_urls in PILLAR_URLS.items():
        print(f"\nChecking inbound links to {pillar_path}:")
        for src_url in source_urls:
            total += 1
            try:
                req = urllib.request.Request(src_url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
                with urllib.request.urlopen(req, timeout=10) as resp:
                    html = resp.read().decode('utf-8', errors='ignore')
                    if pillar_path in html:
                        print(f"  ✓ {src_url}")
                        passed += 1
                    else:
                        print(f"  ✗ MISSING LINK in {src_url}")
                        failed += 1
            except Exception as e:
                print(f"  ✗ ERROR fetching {src_url}: {e}")
                failed += 1

    print(f"\n{'=' * 60}")
    print(f"RESULT: {passed}/{total} inbound links verified ({failed} missing)")
    print(f"{'=' * 60}")

    if failed > 0:
        sys.exit(1)

if __name__ == '__main__':
    main()

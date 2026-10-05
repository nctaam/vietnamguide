# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 68: Verify inbound links to 7 new pillars on live site
"""

import urllib.request
import re
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

PILLAR_URLS = {
    '/plan/vietnam-in-october/': [
        'https://vietnamguide.net/plan/vietnam-in-september/',
        'https://vietnamguide.net/plan/vietnam-in-november/',
        'https://vietnamguide.net/plan/best-time-to-visit-vietnam/',
        'https://vietnamguide.net/destinations/sapa-travel-guide/',
    ],
    '/destinations/best-things-to-do-in-ho-chi-minh-city/': [
        'https://vietnamguide.net/destinations/ho-chi-minh-city-travel-guide/',
        'https://vietnamguide.net/destinations/saigon-street-food-guide/',
        'https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/',
        'https://vietnamguide.net/compare/hanoi-vs-ho-chi-minh-city/',
    ],
    '/plan/ho-chi-minh-city-to-da-lat-transport/': [
        'https://vietnamguide.net/destinations/da-lat-travel-guide/',
        'https://vietnamguide.net/destinations/da-lat-coffee-farms-guide/',
        'https://vietnamguide.net/destinations/da-lat-waterfalls-guide/',
        'https://vietnamguide.net/plan/transport-within-vietnam/',
    ],
    '/destinations/where-to-stay-in-phu-quoc/': [
        'https://vietnamguide.net/destinations/phu-quoc-travel-guide/',
        'https://vietnamguide.net/destinations/phu-quoc-beaches-guide/',
        'https://vietnamguide.net/compare/con-dao-vs-phu-quoc/',
        'https://vietnamguide.net/destinations/where-to-stay-in-vietnam-base-decisions/',
    ],
    '/destinations/best-things-to-do-in-da-nang/': [
        'https://vietnamguide.net/destinations/da-nang-travel-guide/',
        'https://vietnamguide.net/destinations/da-nang-beaches-guide/',
        'https://vietnamguide.net/destinations/da-nang-street-food-guide/',
        'https://vietnamguide.net/compare/da-nang-vs-hoi-an/',
    ],
    '/plan/ho-chi-minh-city-to-phu-quoc-transport/': [
        'https://vietnamguide.net/destinations/phu-quoc-travel-guide/',
        'https://vietnamguide.net/compare/phu-quoc-vs-nha-trang/',
        'https://vietnamguide.net/destinations/best-islands-in-vietnam/',
        'https://vietnamguide.net/plan/vietnam-travel-guide/',
    ],
    '/destinations/where-to-stay-in-nha-trang/': [
        'https://vietnamguide.net/destinations/nha-trang-travel-guide/',
        'https://vietnamguide.net/compare/mui-ne-vs-nha-trang/',
        'https://vietnamguide.net/plan/da-lat-to-nha-trang-transport/',
        'https://vietnamguide.net/destinations/best-beaches-in-vietnam/',
    ],
}

def main():
    print("=" * 60)
    print("STAGE 68: INBOUND LINK VERIFICATION")
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

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 70: Verify inbound links to 7 new pillars on live site
"""

import urllib.request
import re
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

PILLAR_URLS = {
    '/destinations/best-things-to-do-in-sapa/': [
        'https://vietnamguide.net/destinations/sapa-travel-guide/',
        'https://vietnamguide.net/destinations/where-to-stay-in-sapa/',
        'https://vietnamguide.net/plan/hanoi-to-sapa-transport/',
        'https://vietnamguide.net/plan/sapa-trekking-routes-guide/',
    ],
    '/destinations/best-things-to-do-in-ninh-binh/': [
        'https://vietnamguide.net/destinations/ninh-binh-travel-guide/',
        'https://vietnamguide.net/destinations/where-to-stay-in-ninh-binh/',
        'https://vietnamguide.net/plan/hanoi-to-ninh-binh-transport/',
        'https://vietnamguide.net/destinations/hang-mua-ninh-binh-guide/',
    ],
    '/destinations/best-things-to-do-in-ha-long-bay/': [
        'https://vietnamguide.net/destinations/ha-long-bay-travel-guide/',
        'https://vietnamguide.net/compare/ha-long-bay-vs-lan-ha-bay/',
        'https://vietnamguide.net/plan/hanoi-to-ha-long-bay-transport/',
        'https://vietnamguide.net/destinations/bai-tu-long-bay-guide/',
    ],
    '/destinations/best-things-to-do-in-mui-ne/': [
        'https://vietnamguide.net/compare/mui-ne-vs-nha-trang/',
        'https://vietnamguide.net/plan/ho-chi-minh-city-to-mui-ne-transport/',
        'https://vietnamguide.net/destinations/where-to-stay-in-nha-trang/',
        'https://vietnamguide.net/destinations/best-beaches-in-vietnam/',
    ],
    '/destinations/where-to-stay-in-mui-ne/': [
        'https://vietnamguide.net/compare/mui-ne-vs-nha-trang/',
        'https://vietnamguide.net/plan/ho-chi-minh-city-to-mui-ne-transport/',
        'https://vietnamguide.net/plan/where-to-stay-in-vietnam-base-decisions/',
        'https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/',
    ],
    '/destinations/best-things-to-do-in-quy-nhon/': [
        'https://vietnamguide.net/destinations/best-beaches-in-vietnam/',
        'https://vietnamguide.net/destinations/quy-nhon-travel-guide/',
        'https://vietnamguide.net/plan/vietnam-train-travel/',
        'https://vietnamguide.net/destinations/da-nang-travel-guide/',
    ],
    '/plan/ho-chi-minh-city-to-can-tho-transport/': [
        'https://vietnamguide.net/destinations/mekong-delta-travel-guide/',
        'https://vietnamguide.net/destinations/mekong-delta-floating-markets-guide/',
        'https://vietnamguide.net/plan/transport-within-vietnam/',
        'https://vietnamguide.net/destinations/ho-chi-minh-city-travel-guide/',
    ],
}

def main():
    print("=" * 60)
    print("STAGE 70: INBOUND LINK VERIFICATION")
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

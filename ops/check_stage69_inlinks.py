# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 69: Verify inbound links to 7 new pillars on live site
"""

import urllib.request
import re
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

PILLAR_URLS = {
    '/destinations/where-to-stay-in-da-lat/': [
        'https://vietnamguide.net/destinations/da-lat-travel-guide/',
        'https://vietnamguide.net/destinations/da-lat-coffee-farms-guide/',
        'https://vietnamguide.net/destinations/da-lat-waterfalls-guide/',
        'https://vietnamguide.net/plan/ho-chi-minh-city-to-da-lat-transport/',
    ],
    '/destinations/best-things-to-do-in-da-lat/': [
        'https://vietnamguide.net/destinations/da-lat-travel-guide/',
        'https://vietnamguide.net/destinations/vietnam-coffee-guide/',
        'https://vietnamguide.net/plan/da-lat-to-nha-trang-transport/',
        'https://vietnamguide.net/plan/vietnam-in-october/',
    ],
    '/destinations/best-things-to-do-in-nha-trang/': [
        'https://vietnamguide.net/destinations/nha-trang-travel-guide/',
        'https://vietnamguide.net/destinations/where-to-stay-in-nha-trang/',
        'https://vietnamguide.net/compare/mui-ne-vs-nha-trang/',
        'https://vietnamguide.net/destinations/best-beaches-in-vietnam/',
    ],
    '/destinations/best-things-to-do-in-phu-quoc/': [
        'https://vietnamguide.net/destinations/phu-quoc-travel-guide/',
        'https://vietnamguide.net/destinations/where-to-stay-in-phu-quoc/',
        'https://vietnamguide.net/destinations/phu-quoc-beaches-guide/',
        'https://vietnamguide.net/plan/ho-chi-minh-city-to-phu-quoc-transport/',
    ],
    '/destinations/where-to-stay-in-phong-nha/': [
        'https://vietnamguide.net/destinations/phong-nha-travel-guide/',
        'https://vietnamguide.net/destinations/phong-nha-cave-treks/',
        'https://vietnamguide.net/plan/hanoi-to-phong-nha-transport/',
        'https://vietnamguide.net/plan/phong-nha-to-hue-transport/',
    ],
    '/plan/hue-to-hoi-an-transport/': [
        'https://vietnamguide.net/destinations/hue-imperial-city-guide/',
        'https://vietnamguide.net/destinations/hoi-an-ancient-town-guide/',
        'https://vietnamguide.net/plan/da-nang-to-hue-train-vs-car/',
        'https://vietnamguide.net/plan/da-nang-airport-to-hoi-an/',
    ],
    '/plan/ho-chi-minh-city-to-mui-ne-transport/': [
        'https://vietnamguide.net/destinations/ho-chi-minh-city-travel-guide/',
        'https://vietnamguide.net/compare/mui-ne-vs-nha-trang/',
        'https://vietnamguide.net/plan/transport-within-vietnam/',
        'https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/',
    ],
}

def main():
    print("=" * 60)
    print("STAGE 69: INBOUND LINK VERIFICATION")
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

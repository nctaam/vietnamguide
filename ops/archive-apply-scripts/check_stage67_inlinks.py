# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 67: Verify inbound links to 7 new pillars on live site
"""

import urllib.request
import re
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

PILLAR_URLS = {
    '/plan/vietnam-in-august/': [
        'https://vietnamguide.net/plan/vietnam-in-july/',
        'https://vietnamguide.net/plan/best-time-to-visit-vietnam/',
        'https://vietnamguide.net/destinations/da-nang-beaches-guide/',
        'https://vietnamguide.net/plan/vietnam-in-june/',
    ],
    '/destinations/where-to-stay-in-hue/': [
        'https://vietnamguide.net/destinations/hue-imperial-city-guide/',
        'https://vietnamguide.net/destinations/hue-street-food-guide/',
        'https://vietnamguide.net/destinations/best-things-to-do-in-hue/',
        'https://vietnamguide.net/compare/da-nang-to-hue-train-vs-car/',
    ],
    '/destinations/mekong-delta-floating-markets-guide/': [
        'https://vietnamguide.net/destinations/mekong-delta-travel-guide/',
        'https://vietnamguide.net/compare/mekong-delta-overnight-vs-day-trip/',
        'https://vietnamguide.net/destinations/ho-chi-minh-city-travel-guide/',
        'https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/',
    ],
    '/plan/hanoi-to-phong-nha-transport/': [
        'https://vietnamguide.net/destinations/phong-nha-travel-guide/',
        'https://vietnamguide.net/destinations/phong-nha-cave-treks/',
        'https://vietnamguide.net/plan/phong-nha-to-hue-transport/',
        'https://vietnamguide.net/plan/vietnam-train-travel/',
    ],
    '/destinations/da-lat-coffee-farms-guide/': [
        'https://vietnamguide.net/destinations/da-lat-travel-guide/',
        'https://vietnamguide.net/destinations/da-lat-waterfalls-guide/',
        'https://vietnamguide.net/plan/da-lat-to-nha-trang-transport/',
        'https://vietnamguide.net/destinations/vietnam-coffee-guide/',
    ],
    '/destinations/hoi-an-tailoring-guide/': [
        'https://vietnamguide.net/destinations/hoi-an-ancient-town-guide/',
        'https://vietnamguide.net/destinations/best-things-to-do-in-hoi-an/',
        'https://vietnamguide.net/destinations/hoi-an-street-food-guide/',
        'https://vietnamguide.net/compare/da-nang-vs-hoi-an/',
    ],
    '/plan/vietnam-in-september/': [
        'https://vietnamguide.net/plan/vietnam-in-november/',
        'https://vietnamguide.net/destinations/mu-cang-chai-travel-guide/',
        'https://vietnamguide.net/destinations/sapa-travel-guide/',
        'https://vietnamguide.net/plan/vietnam-in-july/',
    ],
}

def main():
    print("=" * 60)
    print("STAGE 67: INBOUND LINK VERIFICATION")
    print("=" * 60)

    total = 0
    passed = 0
    failed = 0

    for pillar_path, source_urls in PILLAR_URLS.items():
        print(f"\nChecking inbound links to {pillar_path}:")
        for src_url in source_urls:
            total += 1
            try:
                req = urllib.request.Request(src_url, headers={'User-Agent': 'VGBot/1.0'})
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

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 77: Live HTTP Inlink Verification
Validates that all 32 planned inbound links are present in live rendered HTML over HTTP.
"""

import urllib.request
import json
import sys
import os
import time

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    parent_map = {
        'where-to-stay-in-phong-nha': 'destinations',
        'phong-nha-travel-guide': 'destinations',
        'phong-nha-to-hue-transport': 'plan',
        'hanoi-to-phong-nha-transport': 'plan',
        'da-lat-travel-guide': 'destinations',
        'where-to-stay-in-da-lat': 'destinations',
        'where-to-stay-in-mui-ne': 'destinations',
        'best-things-to-do-in-mui-ne': 'destinations',
        'central-highlands-vietnam-itinerary': 'itineraries',
        'where-to-stay-in-buon-ma-thuot': 'destinations',
        'da-lat-coffee-farms-guide': 'destinations',
        'best-things-to-do-in-da-lat': 'destinations',
        'vietnam-motorbike-license-laws': 'plan',
        'ha-giang-safety-guide': 'plan',
        'ha-giang-easy-rider-vs-self-drive': 'compare',
        'transport-within-vietnam': 'plan',
        'mu-cang-chai-travel-guide': 'destinations',
        'northwest-vietnam-itinerary': 'itineraries',
        'hanoi-travel-guide': 'destinations',
        'sapa-travel-guide': 'destinations',
        'where-to-stay-in-sapa': 'destinations',
        'sapa-vs-ha-giang': 'compare',
        'da-nang-travel-guide': 'destinations',
        'where-to-stay-in-da-nang': 'destinations',
        'central-vietnam-itinerary': 'itineraries',
        'vietnam-train-travel': 'plan',
    }

    expected_links = [
        # where-to-stay-in-dong-hoi (5)
        ('where-to-stay-in-phong-nha', '/destinations/where-to-stay-in-dong-hoi/'),
        ('phong-nha-travel-guide', '/destinations/where-to-stay-in-dong-hoi/'),
        ('phong-nha-to-hue-transport', '/destinations/where-to-stay-in-dong-hoi/'),
        ('hanoi-to-phong-nha-transport', '/destinations/where-to-stay-in-dong-hoi/'),
        ('central-vietnam-itinerary', '/destinations/where-to-stay-in-dong-hoi/'),

        # da-lat-to-mui-ne-transport (4)
        ('da-lat-travel-guide', '/plan/da-lat-to-mui-ne-transport/'),
        ('where-to-stay-in-da-lat', '/plan/da-lat-to-mui-ne-transport/'),
        ('where-to-stay-in-mui-ne', '/plan/da-lat-to-mui-ne-transport/'),
        ('best-things-to-do-in-mui-ne', '/plan/da-lat-to-mui-ne-transport/'),

        # where-to-stay-in-pleiku (4)
        ('central-highlands-vietnam-itinerary', '/destinations/where-to-stay-in-pleiku/'),
        ('where-to-stay-in-buon-ma-thuot', '/destinations/where-to-stay-in-pleiku/'),
        ('da-lat-coffee-farms-guide', '/destinations/where-to-stay-in-pleiku/'),
        ('best-things-to-do-in-da-lat', '/destinations/where-to-stay-in-pleiku/'),

        # vietnam-scooter-rental-checklist (4)
        ('vietnam-motorbike-license-laws', '/plan/vietnam-scooter-rental-checklist/'),
        ('ha-giang-safety-guide', '/plan/vietnam-scooter-rental-checklist/'),
        ('ha-giang-easy-rider-vs-self-drive', '/plan/vietnam-scooter-rental-checklist/'),
        ('transport-within-vietnam', '/plan/vietnam-scooter-rental-checklist/'),

        # hanoi-to-mu-cang-chai-transport (4)
        ('mu-cang-chai-travel-guide', '/plan/hanoi-to-mu-cang-chai-transport/'),
        ('northwest-vietnam-itinerary', '/plan/hanoi-to-mu-cang-chai-transport/'),
        ('hanoi-travel-guide', '/plan/hanoi-to-mu-cang-chai-transport/'),
        ('sapa-travel-guide', '/plan/hanoi-to-mu-cang-chai-transport/'),

        # where-to-stay-in-mu-cang-chai (4)
        ('mu-cang-chai-travel-guide', '/destinations/where-to-stay-in-mu-cang-chai/'),
        ('northwest-vietnam-itinerary', '/destinations/where-to-stay-in-mu-cang-chai/'),
        ('where-to-stay-in-sapa', '/destinations/where-to-stay-in-mu-cang-chai/'),
        ('sapa-vs-ha-giang', '/destinations/where-to-stay-in-mu-cang-chai/'),

        # da-nang-to-phong-nha-transport (7)
        ('phong-nha-travel-guide', '/plan/da-nang-to-phong-nha-transport/'),
        ('phong-nha-to-hue-transport', '/plan/da-nang-to-phong-nha-transport/'),
        ('hanoi-to-phong-nha-transport', '/plan/da-nang-to-phong-nha-transport/'),
        ('da-nang-travel-guide', '/plan/da-nang-to-phong-nha-transport/'),
        ('where-to-stay-in-da-nang', '/plan/da-nang-to-phong-nha-transport/'),
        ('central-vietnam-itinerary', '/plan/da-nang-to-phong-nha-transport/'),
        ('vietnam-train-travel', '/plan/da-nang-to-phong-nha-transport/'),
    ]

    print(f"=== VERIFYING {len(expected_links)} INBOUND LINKS ACROSS {len(parent_map)} PAGES OVER HTTP ===")

    # Cache page HTML to avoid fetching same URL multiple times
    page_html_cache = {}
    success_count = 0
    fail_count = 0

    for source_slug, target_href in expected_links:
        if source_slug not in page_html_cache:
            parent_prefix = parent_map[source_slug]
            url = f"https://vietnamguide.net/{parent_prefix}/{source_slug}/"
            
            # Fetch with retries
            for attempt in range(3):
                try:
                    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
                    with urllib.request.urlopen(req, timeout=25) as resp:
                        body = resp.read().decode('utf-8', errors='ignore')
                        page_html_cache[source_slug] = body
                        break
                except Exception as e:
                    if attempt == 2:
                        print(f"  [ERROR FETCHING] {url}: {e}")
                        page_html_cache[source_slug] = ""
                    time.sleep(2)

        body = page_html_cache.get(source_slug, "")
        found = target_href in body

        if found:
            print(f"  ✓ {source_slug} -> {target_href} [FOUND]")
            success_count += 1
        else:
            print(f"  ✗ {source_slug} -> {target_href} [MISSING]")
            fail_count += 1

    print("\n" + "=" * 70)
    print(f"STAGE 77 INLINK VERIFICATION: {success_count}/{len(expected_links)} VERIFIED LIVE OVER HTTP!")
    print(f"FAILURES: {fail_count}")
    print("=" * 70)

    if fail_count > 0:
        sys.exit(1)

if __name__ == '__main__':
    main()

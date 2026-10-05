# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 78: Live HTTP Inlink Verification
Validates that all 36 planned inbound links are present in live rendered HTML over HTTP.
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
        'where-to-stay-in-kon-tum': 'destinations',
        'buon-ma-thuot-to-pleiku-transport': 'plan',
        'where-to-stay-in-chau-doc': 'destinations',
        'vietnam-to-cambodia-boat-guide': 'plan',
        'vietnam-night-train-safety-tips': 'plan',
        'where-to-stay-in-dien-bien-phu': 'destinations',
        'hanoi-to-dien-bien-phu-transport': 'plan',
        'central-highlands-vietnam-itinerary': 'itineraries',
        'where-to-stay-in-buon-ma-thuot': 'destinations',
        'where-to-stay-in-pleiku': 'destinations',
        'da-lat-travel-guide': 'destinations',
        'southern-vietnam-itinerary': 'itineraries',
        'where-to-stay-in-can-tho': 'destinations',
        'ho-chi-minh-city-to-can-tho-transport': 'plan',
        'mekong-delta-travel-guide': 'destinations',
        'mekong-delta-floating-markets-guide': 'destinations',
        'vietnam-to-cambodia-border-crossings': 'plan',
        'transport-within-vietnam': 'plan',
        'vietnam-train-travel': 'plan',
        'da-nang-to-phong-nha-transport': 'plan',
        'da-nang-to-quy-nhon-transport': 'plan',
        'vietnam-plug-adapter-electricity-guide': 'plan',
        'safety-scams-vietnam': 'plan',
        'northwest-vietnam-itinerary': 'itineraries',
        'where-to-stay-in-sapa': 'destinations',
        'sapa-travel-guide': 'destinations',
        'vietnam-scooter-rental-checklist': 'plan',
        'hanoi-travel-guide': 'destinations',
        'best-day-trips-from-hanoi': 'destinations',
    }

    expected_links = [
        # where-to-stay-in-kon-tum (5)
        ('buon-ma-thuot-to-pleiku-transport', '/destinations/where-to-stay-in-kon-tum/'),
        ('where-to-stay-in-pleiku', '/destinations/where-to-stay-in-kon-tum/'),
        ('central-highlands-vietnam-itinerary', '/destinations/where-to-stay-in-kon-tum/'),
        ('where-to-stay-in-buon-ma-thuot', '/destinations/where-to-stay-in-kon-tum/'),
        ('da-lat-travel-guide', '/destinations/where-to-stay-in-kon-tum/'),

        # buon-ma-thuot-to-pleiku-transport (5)
        ('where-to-stay-in-kon-tum', '/plan/buon-ma-thuot-to-pleiku-transport/'),
        ('where-to-stay-in-pleiku', '/plan/buon-ma-thuot-to-pleiku-transport/'),
        ('where-to-stay-in-buon-ma-thuot', '/plan/buon-ma-thuot-to-pleiku-transport/'),
        ('central-highlands-vietnam-itinerary', '/plan/buon-ma-thuot-to-pleiku-transport/'),
        ('transport-within-vietnam', '/plan/buon-ma-thuot-to-pleiku-transport/'),

        # where-to-stay-in-chau-doc (6)
        ('vietnam-to-cambodia-boat-guide', '/destinations/where-to-stay-in-chau-doc/'),
        ('southern-vietnam-itinerary', '/destinations/where-to-stay-in-chau-doc/'),
        ('where-to-stay-in-can-tho', '/destinations/where-to-stay-in-chau-doc/'),
        ('ho-chi-minh-city-to-can-tho-transport', '/destinations/where-to-stay-in-chau-doc/'),
        ('mekong-delta-travel-guide', '/destinations/where-to-stay-in-chau-doc/'),
        ('vietnam-to-cambodia-border-crossings', '/destinations/where-to-stay-in-chau-doc/'),

        # vietnam-to-cambodia-boat-guide (5)
        ('where-to-stay-in-chau-doc', '/plan/vietnam-to-cambodia-boat-guide/'),
        ('southern-vietnam-itinerary', '/plan/vietnam-to-cambodia-boat-guide/'),
        ('vietnam-to-cambodia-border-crossings', '/plan/vietnam-to-cambodia-boat-guide/'),
        ('transport-within-vietnam', '/plan/vietnam-to-cambodia-boat-guide/'),
        ('mekong-delta-floating-markets-guide', '/plan/vietnam-to-cambodia-boat-guide/'),

        # vietnam-night-train-safety-tips (5)
        ('vietnam-train-travel', '/plan/vietnam-night-train-safety-tips/'),
        ('da-nang-to-phong-nha-transport', '/plan/vietnam-night-train-safety-tips/'),
        ('da-nang-to-quy-nhon-transport', '/plan/vietnam-night-train-safety-tips/'),
        ('vietnam-plug-adapter-electricity-guide', '/plan/vietnam-night-train-safety-tips/'),
        ('safety-scams-vietnam', '/plan/vietnam-night-train-safety-tips/'),

        # where-to-stay-in-dien-bien-phu (5)
        ('hanoi-to-dien-bien-phu-transport', '/destinations/where-to-stay-in-dien-bien-phu/'),
        ('northwest-vietnam-itinerary', '/destinations/where-to-stay-in-dien-bien-phu/'),
        ('where-to-stay-in-sapa', '/destinations/where-to-stay-in-dien-bien-phu/'),
        ('sapa-travel-guide', '/destinations/where-to-stay-in-dien-bien-phu/'),
        ('vietnam-scooter-rental-checklist', '/destinations/where-to-stay-in-dien-bien-phu/'),

        # hanoi-to-dien-bien-phu-transport (5)
        ('where-to-stay-in-dien-bien-phu', '/plan/hanoi-to-dien-bien-phu-transport/'),
        ('northwest-vietnam-itinerary', '/plan/hanoi-to-dien-bien-phu-transport/'),
        ('hanoi-travel-guide', '/plan/hanoi-to-dien-bien-phu-transport/'),
        ('best-day-trips-from-hanoi', '/plan/hanoi-to-dien-bien-phu-transport/'),
        ('transport-within-vietnam', '/plan/hanoi-to-dien-bien-phu-transport/'),
    ]

    print(f"=== VERIFYING {len(expected_links)} INBOUND LINKS OVER HTTP ===")

    page_html_cache = {}
    success_count = 0
    fail_count = 0

    for source_slug, target_href in expected_links:
        prefix = parent_map.get(source_slug, 'destinations')
        url = f"https://vietnamguide.net/{prefix}/{source_slug}/"

        if url not in page_html_cache:
            for attempt in range(3):
                try:
                    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
                    with urllib.request.urlopen(req, timeout=25) as resp:
                        html = resp.read().decode('utf-8', errors='ignore')
                        page_html_cache[url] = html
                        break
                except Exception as e:
                    if attempt < 2:
                        time.sleep(2)
                    else:
                        print(f"[FETCH ERROR] Could not fetch {url}: {e}")
                        page_html_cache[url] = ""

        html = page_html_cache.get(url, "")
        if target_href in html:
            print(f"  [OK] {source_slug} -> {target_href}")
            success_count += 1
        else:
            print(f"  [FAIL] {source_slug} does NOT link to {target_href} (URL: {url})")
            fail_count += 1

    print("\n" + "=" * 60)
    print(f"VERIFICATION RESULT: {success_count}/{len(expected_links)} links verified over HTTP.")
    print("=" * 60)

    if fail_count > 0:
        print(f"[ERROR] {fail_count} inbound links failed verification!")
        sys.exit(1)
    else:
        print("[SUCCESS] All 36 inbound links verified live on production VPS!")

if __name__ == '__main__':
    main()
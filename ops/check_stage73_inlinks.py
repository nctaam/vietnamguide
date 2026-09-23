# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 73: Live HTTP Inlink Verification
Validates that every one of the inbound links is present in live rendered HTML over HTTP.
"""

import urllib.request
import json
import sys
import os

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    parent_map = {
        'cao-bang-travel-guide': 'destinations',
        'ha-giang-loop-planning-guide': 'destinations',
        'where-to-stay-in-vietnam-base-decisions': 'destinations',
        'best-time-for-northern-vietnam': 'plan',
        'phu-yen-travel-guide': 'destinations',
        'quy-nhon-to-phu-yen-coastal-drive': 'plan',
        'where-to-stay-in-quy-nhon': 'destinations',
        'best-beaches-in-vietnam': 'destinations',
        'where-to-stay-in-ha-giang': 'destinations',
        'where-to-stay-in-cao-bang': 'destinations',
        'pu-luong-travel-guide': 'destinations',
        'where-to-stay-in-pu-luong': 'destinations',
        'sapa-trekking-routes-guide': 'plan',
        'ninh-binh-without-rushing': 'destinations',
        'ho-chi-minh-city-in-2-days': 'itineraries',
        'ho-chi-minh-city-mekong-budget': 'costs',
        'mekong-delta-overnight-vs-day-trip': 'compare',
        '10-days-in-vietnam': 'itineraries',
        'vietnam-food-safety-street-food-etiquette': 'plan',
        'hue-street-food-guide': 'destinations',
        'hanoi-street-food-guide': 'destinations',
        'saigon-street-food-guide': 'destinations',
        'hanoi-travel-guide': 'destinations',
        'transport-within-vietnam': 'plan',
        'hanoi-to-ha-giang-transport': 'plan',
    }

    # Expected links to verify from each source slug
    expected_links = [
        # where-to-stay-in-cao-bang (4)
        ('cao-bang-travel-guide', '/destinations/where-to-stay-in-cao-bang/'),
        ('ha-giang-loop-planning-guide', '/destinations/where-to-stay-in-cao-bang/'),
        ('where-to-stay-in-vietnam-base-decisions', '/destinations/where-to-stay-in-cao-bang/'),
        ('best-time-for-northern-vietnam', '/destinations/where-to-stay-in-cao-bang/'),

        # where-to-stay-in-phu-yen (4)
        ('phu-yen-travel-guide', '/destinations/where-to-stay-in-phu-yen/'),
        ('quy-nhon-to-phu-yen-coastal-drive', '/destinations/where-to-stay-in-phu-yen/'),
        ('where-to-stay-in-quy-nhon', '/destinations/where-to-stay-in-phu-yen/'),
        ('best-beaches-in-vietnam', '/destinations/where-to-stay-in-phu-yen/'),

        # where-to-stay-in-ba-be (5)
        ('cao-bang-travel-guide', '/destinations/where-to-stay-in-ba-be/'),
        ('best-time-for-northern-vietnam', '/destinations/where-to-stay-in-ba-be/'),
        ('where-to-stay-in-vietnam-base-decisions', '/destinations/where-to-stay-in-ba-be/'),
        ('where-to-stay-in-ha-giang', '/destinations/where-to-stay-in-ba-be/'),
        ('where-to-stay-in-cao-bang', '/destinations/where-to-stay-in-ba-be/'),

        # pu-luong-trekking-routes-guide (4)
        ('pu-luong-travel-guide', '/plan/pu-luong-trekking-routes-guide/'),
        ('where-to-stay-in-pu-luong', '/plan/pu-luong-trekking-routes-guide/'),
        ('sapa-trekking-routes-guide', '/plan/pu-luong-trekking-routes-guide/'),
        ('ninh-binh-without-rushing', '/plan/pu-luong-trekking-routes-guide/'),

        # southern-vietnam-itinerary (4)
        ('ho-chi-minh-city-in-2-days', '/itineraries/southern-vietnam-itinerary/'),
        ('ho-chi-minh-city-mekong-budget', '/itineraries/southern-vietnam-itinerary/'),
        ('mekong-delta-overnight-vs-day-trip', '/itineraries/southern-vietnam-itinerary/'),
        ('10-days-in-vietnam', '/itineraries/southern-vietnam-itinerary/'),

        # vietnam-vegetarian-travel-guide (4)
        ('vietnam-food-safety-street-food-etiquette', '/plan/vietnam-vegetarian-travel-guide/'),
        ('hue-street-food-guide', '/plan/vietnam-vegetarian-travel-guide/'),
        ('hanoi-street-food-guide', '/plan/vietnam-vegetarian-travel-guide/'),
        ('saigon-street-food-guide', '/plan/vietnam-vegetarian-travel-guide/'),

        # hanoi-to-cao-bang-transport (5: 4 targets + where-to-stay-in-cao-bang)
        ('cao-bang-travel-guide', '/plan/hanoi-to-cao-bang-transport/'),
        ('hanoi-travel-guide', '/plan/hanoi-to-cao-bang-transport/'),
        ('transport-within-vietnam', '/plan/hanoi-to-cao-bang-transport/'),
        ('hanoi-to-ha-giang-transport', '/plan/hanoi-to-cao-bang-transport/'),
        ('where-to-stay-in-cao-bang', '/plan/hanoi-to-cao-bang-transport/'),
    ]

    print("=" * 70)
    print(f"STAGE 73: LIVE HTTP INBOUND LINK VERIFICATION ({len(expected_links)} LINKS)")
    print("=" * 70)

    page_htmls = {}
    total_verified = 0

    for source_slug, target_link in expected_links:
        parent = parent_map[source_slug]
        url = f"https://vietnamguide.net/{parent}/{source_slug}/"

        if source_slug not in page_htmls:
            req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
            try:
                with urllib.request.urlopen(req, timeout=12) as resp:
                    page_htmls[source_slug] = resp.read().decode('utf-8', errors='ignore')
            except Exception as e:
                print(f"[HTTP ERROR] Fetching {url}: {e}")
                page_htmls[source_slug] = ""

        html = page_htmls[source_slug]
        if target_link in html:
            total_verified += 1
            print(f"  ✓ [{source_slug}] -> '{target_link}' VERIFIED LIVE HTTP")
        else:
            print(f"  ✗ [FAIL] [{source_slug}] -> '{target_link}' NOT FOUND in live page!")

    print("\n" + "=" * 70)
    print(f"STAGE 73 INLINK RESULT: {total_verified}/{len(expected_links)} INLINKS VERIFIED LIVE OVER HTTP!")
    print("=" * 70)

    if total_verified == len(expected_links):
        print("PERFECT: 100% Inlink Mesh Coverage!")
    else:
        print("WARNING: Some inlinks were not verified!")
        sys.exit(1)

if __name__ == '__main__':
    main()

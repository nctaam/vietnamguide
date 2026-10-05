# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 75: Live HTTP Inlink Verification
Validates that every one of the 28 planned inbound links is present in live rendered HTML over HTTP.
"""

import urllib.request
import json
import sys
import os

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    parent_map = {
        'where-to-stay-in-phu-quoc': 'destinations',
        'phu-quoc-travel-guide': 'destinations',
        'where-to-stay-in-can-tho': 'destinations',
        'southern-vietnam-itinerary': 'itineraries',
        'ho-chi-minh-city-travel-guide': 'destinations',
        'best-day-trips-from-ho-chi-minh-city': 'destinations',
        'saigon-airport-to-district-1': 'plan',
        'transport-within-vietnam': 'plan',
        'sim-esim-vietnam': 'plan',
        'vietnam-first-trip-planning-checklist': 'plan',
        'safety-scams-vietnam': 'plan',
        'vietnam-travel-cost': 'costs',
        'da-lat-travel-guide': 'destinations',
        'where-to-stay-in-da-lat': 'destinations',
        '10-days-in-vietnam': 'itineraries',
        '14-days-in-vietnam': 'itineraries',
        'central-highlands-vietnam-itinerary': 'itineraries',
        'where-to-stay-in-nha-trang': 'destinations',
        'saigon-street-food-guide': 'destinations',
        'hanoi-street-food-guide': 'destinations',
        'vietnam-food-safety-street-food-etiquette': 'plan',
        'where-to-stay-in-da-nang': 'destinations',
        'sapa-travel-guide': 'destinations',
        'where-to-stay-in-sapa': 'destinations',
        'hanoi-travel-guide': 'destinations',
    }

    # Expected links to verify from each source slug
    expected_links = [
        # where-to-stay-in-ha-tien (4)
        ('where-to-stay-in-phu-quoc', '/destinations/where-to-stay-in-ha-tien/'),
        ('phu-quoc-travel-guide', '/destinations/where-to-stay-in-ha-tien/'),
        ('where-to-stay-in-can-tho', '/destinations/where-to-stay-in-ha-tien/'),
        ('southern-vietnam-itinerary', '/destinations/where-to-stay-in-ha-tien/'),

        # saigon-to-vung-tau-transport (4)
        ('ho-chi-minh-city-travel-guide', '/plan/saigon-to-vung-tau-transport/'),
        ('best-day-trips-from-ho-chi-minh-city', '/plan/saigon-to-vung-tau-transport/'),
        ('saigon-airport-to-district-1', '/plan/saigon-to-vung-tau-transport/'),
        ('transport-within-vietnam', '/plan/saigon-to-vung-tau-transport/'),

        # vietnam-travel-apps (4)
        ('sim-esim-vietnam', '/plan/vietnam-travel-apps/'),
        ('vietnam-first-trip-planning-checklist', '/plan/vietnam-travel-apps/'),
        ('safety-scams-vietnam', '/plan/vietnam-travel-apps/'),
        ('vietnam-travel-cost', '/plan/vietnam-travel-apps/'),

        # central-highlands-vietnam-itinerary (4)
        ('da-lat-travel-guide', '/itineraries/central-highlands-vietnam-itinerary/'),
        ('where-to-stay-in-da-lat', '/itineraries/central-highlands-vietnam-itinerary/'),
        ('10-days-in-vietnam', '/itineraries/central-highlands-vietnam-itinerary/'),
        ('14-days-in-vietnam', '/itineraries/central-highlands-vietnam-itinerary/'),

        # where-to-stay-in-buon-ma-thuot (4)
        ('central-highlands-vietnam-itinerary', '/destinations/where-to-stay-in-buon-ma-thuot/'),
        ('da-lat-travel-guide', '/destinations/where-to-stay-in-buon-ma-thuot/'),
        ('where-to-stay-in-nha-trang', '/destinations/where-to-stay-in-buon-ma-thuot/'),
        ('transport-within-vietnam', '/destinations/where-to-stay-in-buon-ma-thuot/'),

        # vietnam-craft-beer-guide (4)
        ('saigon-street-food-guide', '/plan/vietnam-craft-beer-guide/'),
        ('hanoi-street-food-guide', '/plan/vietnam-craft-beer-guide/'),
        ('vietnam-food-safety-street-food-etiquette', '/plan/vietnam-craft-beer-guide/'),
        ('where-to-stay-in-da-nang', '/plan/vietnam-craft-beer-guide/'),

        # hanoi-to-sapa-train-vs-sleeper-bus (4)
        ('sapa-travel-guide', '/plan/hanoi-to-sapa-train-vs-sleeper-bus/'),
        ('where-to-stay-in-sapa', '/plan/hanoi-to-sapa-train-vs-sleeper-bus/'),
        ('hanoi-travel-guide', '/plan/hanoi-to-sapa-train-vs-sleeper-bus/'),
        ('transport-within-vietnam', '/plan/hanoi-to-sapa-train-vs-sleeper-bus/'),
    ]

    print("=" * 70)
    print(f"STAGE 75: LIVE HTTP INBOUND LINK VERIFICATION ({len(expected_links)} LINKS)")
    print("=" * 70)

    page_htmls = {}
    total_verified = 0

    for source_slug, target_link in expected_links:
        parent = parent_map[source_slug]
        url = f"https://vietnamguide.net/{parent}/{source_slug}/"

        if source_slug not in page_htmls:
            req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
            try:
                with urllib.request.urlopen(req, timeout=15) as resp:
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
    print(f"STAGE 75 INLINK RESULT: {total_verified}/{len(expected_links)} INLINKS VERIFIED LIVE OVER HTTP!")
    print("=" * 70)

    if total_verified == len(expected_links):
        print("PERFECT: 100% Inlink Mesh Coverage!")
    else:
        print("WARNING: Some inlinks were not verified!")
        sys.exit(1)

if __name__ == '__main__':
    main()

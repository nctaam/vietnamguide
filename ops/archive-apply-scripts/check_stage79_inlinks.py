# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 79: Live HTTP Inlink Verification
Validates that all 34 planned inbound links are present in live rendered HTML over HTTP.
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
        'where-to-stay-in-nha-trang': 'destinations',
        'where-to-stay-in-quy-nhon': 'destinations',
        'da-nang-to-quy-nhon-transport': 'plan',
        'quy-nhon-travel-guide': 'destinations',
        'vietnam-train-travel': 'plan',
        'where-to-stay-in-can-tho': 'destinations',
        'where-to-stay-in-chau-doc': 'destinations',
        'vietnam-to-cambodia-boat-guide': 'plan',
        'ho-chi-minh-city-to-can-tho-transport': 'plan',
        'mekong-delta-travel-guide': 'destinations',
        'hanoi-to-pu-luong-transport': 'plan',
        'where-to-stay-in-mai-chau': 'destinations',
        'best-day-trips-from-hanoi': 'destinations',
        'hanoi-travel-guide': 'destinations',
        'northwest-vietnam-itinerary': 'itineraries',
        'southern-vietnam-itinerary': 'itineraries',
        'where-to-stay-in-hue': 'destinations',
        'where-to-stay-in-da-nang': 'destinations',
        'da-nang-to-hue-train-vs-car': 'compare',
        'central-vietnam-itinerary': 'itineraries',
        'hue-to-hoi-an-transport': 'plan',
        'transport-within-vietnam': 'plan',
        'vietnam-night-train-safety-tips': 'plan',
        'vietnam-scooter-rental-checklist': 'plan',
        'safety-scams-vietnam': 'plan',
        'hanoi-to-sapa-train-vs-sleeper-bus': 'plan',
        'where-to-stay-in-pu-luong': 'destinations',
        'pu-luong-trekking-routes-guide': 'plan',
        'hanoi-to-mai-chau-transport': 'plan',
    }

    expected_links = [
        # nha-trang-to-quy-nhon-transport (5)
        ('where-to-stay-in-nha-trang', '/plan/nha-trang-to-quy-nhon-transport/'),
        ('where-to-stay-in-quy-nhon', '/plan/nha-trang-to-quy-nhon-transport/'),
        ('da-nang-to-quy-nhon-transport', '/plan/nha-trang-to-quy-nhon-transport/'),
        ('quy-nhon-travel-guide', '/plan/nha-trang-to-quy-nhon-transport/'),
        ('vietnam-train-travel', '/plan/nha-trang-to-quy-nhon-transport/'),

        # can-tho-to-chau-doc-transport (5)
        ('where-to-stay-in-can-tho', '/plan/can-tho-to-chau-doc-transport/'),
        ('where-to-stay-in-chau-doc', '/plan/can-tho-to-chau-doc-transport/'),
        ('vietnam-to-cambodia-boat-guide', '/plan/can-tho-to-chau-doc-transport/'),
        ('ho-chi-minh-city-to-can-tho-transport', '/plan/can-tho-to-chau-doc-transport/'),
        ('mekong-delta-travel-guide', '/plan/can-tho-to-chau-doc-transport/'),

        # hanoi-to-mai-chau-transport (5)
        ('hanoi-to-pu-luong-transport', '/plan/hanoi-to-mai-chau-transport/'),
        ('where-to-stay-in-mai-chau', '/plan/hanoi-to-mai-chau-transport/'),
        ('best-day-trips-from-hanoi', '/plan/hanoi-to-mai-chau-transport/'),
        ('hanoi-travel-guide', '/plan/hanoi-to-mai-chau-transport/'),
        ('northwest-vietnam-itinerary', '/plan/hanoi-to-mai-chau-transport/'),

        # where-to-stay-in-ben-tre (4)
        ('where-to-stay-in-can-tho', '/destinations/where-to-stay-in-ben-tre/'),
        ('ho-chi-minh-city-to-can-tho-transport', '/destinations/where-to-stay-in-ben-tre/'),
        ('mekong-delta-travel-guide', '/destinations/where-to-stay-in-ben-tre/'),
        ('southern-vietnam-itinerary', '/destinations/where-to-stay-in-ben-tre/'),

        # where-to-stay-in-lang-co (5)
        ('where-to-stay-in-hue', '/destinations/where-to-stay-in-lang-co/'),
        ('where-to-stay-in-da-nang', '/destinations/where-to-stay-in-lang-co/'),
        ('da-nang-to-hue-train-vs-car', '/destinations/where-to-stay-in-lang-co/'),
        ('central-vietnam-itinerary', '/destinations/where-to-stay-in-lang-co/'),
        ('hue-to-hoi-an-transport', '/destinations/where-to-stay-in-lang-co/'),

        # vietnam-sleeper-bus-survival-guide (5)
        ('transport-within-vietnam', '/plan/vietnam-sleeper-bus-survival-guide/'),
        ('vietnam-night-train-safety-tips', '/plan/vietnam-sleeper-bus-survival-guide/'),
        ('vietnam-scooter-rental-checklist', '/plan/vietnam-sleeper-bus-survival-guide/'),
        ('safety-scams-vietnam', '/plan/vietnam-sleeper-bus-survival-guide/'),
        ('hanoi-to-sapa-train-vs-sleeper-bus', '/plan/vietnam-sleeper-bus-survival-guide/'),

        # hanoi-to-pu-luong-transport (5)
        ('hanoi-to-mai-chau-transport', '/plan/hanoi-to-pu-luong-transport/'),
        ('where-to-stay-in-pu-luong', '/plan/hanoi-to-pu-luong-transport/'),
        ('pu-luong-trekking-routes-guide', '/plan/hanoi-to-pu-luong-transport/'),
        ('where-to-stay-in-mai-chau', '/plan/hanoi-to-pu-luong-transport/'),
        ('northwest-vietnam-itinerary', '/plan/hanoi-to-pu-luong-transport/'),
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
        print("[SUCCESS] All 34 inbound links verified live on production VPS!")

if __name__ == '__main__':
    main()
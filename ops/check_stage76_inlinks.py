# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 76: Live HTTP Inlink Verification
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
        'ninh-binh-travel-guide': 'destinations',
        'tam-coc-travel-guide': 'destinations',
        'where-to-stay-in-ninh-binh': 'destinations',
        'hang-mua-ninh-binh-guide': 'destinations',
        'saigon-to-vung-tau-transport': 'plan',
        'best-day-trips-from-ho-chi-minh-city': 'destinations',
        'ho-chi-minh-city-travel-guide': 'destinations',
        'southern-vietnam-itinerary': 'itineraries',
        'da-nang-travel-guide': 'destinations',
        'quy-nhon-travel-guide': 'destinations',
        'where-to-stay-in-quy-nhon': 'destinations',
        'central-vietnam-itinerary': 'itineraries',
        'where-to-stay-in-con-dao': 'destinations',
        'con-dao-vs-phu-quoc': 'compare',
        'where-to-stay-in-ba-be': 'destinations',
        'hanoi-to-ba-be-transport': 'plan',
        'where-to-stay-in-cao-bang': 'destinations',
        'hanoi-to-cao-bang-transport': 'plan',
        'ha-giang-safety-guide': 'plan',
        'ha-giang-easy-rider-vs-self-drive': 'compare',
        'transport-within-vietnam': 'plan',
        'vietnam-first-trip-planning-checklist': 'plan',
        'what-to-pack-for-vietnam-region-season': 'plan',
        'vietnam-train-travel': 'plan',
        'vietnam-sim-card-airport-vs-city': 'plan',
    }

    # Expected links to verify from each source slug
    expected_links = [
        # where-to-stay-in-tam-coc (4)
        ('ninh-binh-travel-guide', '/destinations/where-to-stay-in-tam-coc/'),
        ('tam-coc-travel-guide', '/destinations/where-to-stay-in-tam-coc/'),
        ('where-to-stay-in-ninh-binh', '/destinations/where-to-stay-in-tam-coc/'),
        ('hang-mua-ninh-binh-guide', '/destinations/where-to-stay-in-tam-coc/'),

        # where-to-stay-in-vung-tau (4)
        ('saigon-to-vung-tau-transport', '/destinations/where-to-stay-in-vung-tau/'),
        ('best-day-trips-from-ho-chi-minh-city', '/destinations/where-to-stay-in-vung-tau/'),
        ('ho-chi-minh-city-travel-guide', '/destinations/where-to-stay-in-vung-tau/'),
        ('southern-vietnam-itinerary', '/destinations/where-to-stay-in-vung-tau/'),

        # da-nang-to-quy-nhon-transport (4)
        ('da-nang-travel-guide', '/plan/da-nang-to-quy-nhon-transport/'),
        ('quy-nhon-travel-guide', '/plan/da-nang-to-quy-nhon-transport/'),
        ('where-to-stay-in-quy-nhon', '/plan/da-nang-to-quy-nhon-transport/'),
        ('central-vietnam-itinerary', '/plan/da-nang-to-quy-nhon-transport/'),

        # how-to-get-to-con-dao-flight-vs-ferry (4)
        ('where-to-stay-in-con-dao', '/plan/how-to-get-to-con-dao-flight-vs-ferry/'),
        ('con-dao-vs-phu-quoc', '/plan/how-to-get-to-con-dao-flight-vs-ferry/'),
        ('saigon-to-vung-tau-transport', '/plan/how-to-get-to-con-dao-flight-vs-ferry/'),
        ('southern-vietnam-itinerary', '/plan/how-to-get-to-con-dao-flight-vs-ferry/'),

        # northeast-vietnam-itinerary (4)
        ('where-to-stay-in-ba-be', '/itineraries/northeast-vietnam-itinerary/'),
        ('hanoi-to-ba-be-transport', '/itineraries/northeast-vietnam-itinerary/'),
        ('where-to-stay-in-cao-bang', '/itineraries/northeast-vietnam-itinerary/'),
        ('hanoi-to-cao-bang-transport', '/itineraries/northeast-vietnam-itinerary/'),

        # vietnam-motorbike-license-laws (4)
        ('ha-giang-safety-guide', '/plan/vietnam-motorbike-license-laws/'),
        ('ha-giang-easy-rider-vs-self-drive', '/plan/vietnam-motorbike-license-laws/'),
        ('transport-within-vietnam', '/plan/vietnam-motorbike-license-laws/'),
        ('vietnam-first-trip-planning-checklist', '/plan/vietnam-motorbike-license-laws/'),

        # vietnam-plug-adapter-electricity-guide (4)
        ('what-to-pack-for-vietnam-region-season', '/plan/vietnam-plug-adapter-electricity-guide/'),
        ('vietnam-first-trip-planning-checklist', '/plan/vietnam-plug-adapter-electricity-guide/'),
        ('vietnam-train-travel', '/plan/vietnam-plug-adapter-electricity-guide/'),
        ('vietnam-sim-card-airport-vs-city', '/plan/vietnam-plug-adapter-electricity-guide/'),
    ]

    print("=" * 70)
    print(f"STAGE 76: LIVE HTTP INBOUND LINK VERIFICATION ({len(expected_links)} LINKS)")
    print("=" * 70)

    page_htmls = {}
    total_verified = 0

    for source_slug, target_link in expected_links:
        parent = parent_map[source_slug]
        url = f"https://vietnamguide.net/{parent}/{source_slug}/"

        if source_slug not in page_htmls:
            req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
            for attempt in range(3):
                try:
                    with urllib.request.urlopen(req, timeout=25) as resp:
                        page_htmls[source_slug] = resp.read().decode('utf-8', errors='ignore')
                        break
                except Exception as e:
                    if attempt == 2:
                        print(f"[ERROR] Failed to fetch {url}: {e}")
                        page_htmls[source_slug] = ""

        html = page_htmls[source_slug]
        if target_link in html:
            print(f"  ✓ [VERIFIED] {source_slug} -> {target_link}")
            total_verified += 1
        else:
            print(f"  ✗ [MISSING] {source_slug} does NOT contain {target_link}")

    print("=" * 70)
    print(f"STAGE 76 INLINK RESULT: {total_verified}/{len(expected_links)} VERIFIED LIVE OVER HTTP!")
    print("=" * 70)

    if total_verified == len(expected_links):
        print("\nAll 28 inbound links verified live on production VPS!")
    else:
        print(f"\n[WARNING] Only {total_verified}/{len(expected_links)} links verified.")

if __name__ == '__main__':
    main()

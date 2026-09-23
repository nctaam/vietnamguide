# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 74: Live HTTP Inlink Verification
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
        'where-to-stay-in-ha-giang': 'destinations',
        'ha-giang-loop-planning-guide': 'destinations',
        'ha-giang-safety-guide': 'plan',
        'ha-giang-loop-cost-budget': 'costs',
        'where-to-stay-in-ba-be': 'destinations',
        'cao-bang-travel-guide': 'destinations',
        'hanoi-travel-guide': 'destinations',
        'transport-within-vietnam': 'plan',
        'sim-esim-vietnam': 'plan',
        'vietnam-first-trip-planning-checklist': 'plan',
        'saigon-airport-to-district-1': 'plan',
        'hanoi-airport-to-old-quarter': 'plan',
        'where-to-stay-in-mai-chau': 'destinations',
        'where-to-stay-in-pu-luong': 'destinations',
        'ninh-binh-without-rushing': 'destinations',
        '10-days-in-vietnam': 'itineraries',
        'ha-giang-easy-rider-vs-self-drive': 'plan',
        'where-to-stay-in-dong-van': 'destinations',
        'vietnam-vegetarian-travel-guide': 'plan',
        'vietnam-food-safety-street-food-etiquette': 'plan',
        'hanoi-street-food-guide': 'destinations',
        'saigon-street-food-guide': 'destinations',
        'ninh-binh-travel-guide': 'destinations',
        'hanoi-to-ninh-binh-transport': 'plan',
    }

    # Expected links to verify from each source slug
    expected_links = [
        # where-to-stay-in-dong-van (4)
        ('where-to-stay-in-ha-giang', '/destinations/where-to-stay-in-dong-van/'),
        ('ha-giang-loop-planning-guide', '/destinations/where-to-stay-in-dong-van/'),
        ('ha-giang-safety-guide', '/destinations/where-to-stay-in-dong-van/'),
        ('ha-giang-loop-cost-budget', '/destinations/where-to-stay-in-dong-van/'),

        # hanoi-to-ba-be-transport (4)
        ('where-to-stay-in-ba-be', '/plan/hanoi-to-ba-be-transport/'),
        ('cao-bang-travel-guide', '/plan/hanoi-to-ba-be-transport/'),
        ('hanoi-travel-guide', '/plan/hanoi-to-ba-be-transport/'),
        ('transport-within-vietnam', '/plan/hanoi-to-ba-be-transport/'),

        # vietnam-sim-card-airport-vs-city (4)
        ('sim-esim-vietnam', '/plan/vietnam-sim-card-airport-vs-city/'),
        ('vietnam-first-trip-planning-checklist', '/plan/vietnam-sim-card-airport-vs-city/'),
        ('saigon-airport-to-district-1', '/plan/vietnam-sim-card-airport-vs-city/'),
        ('hanoi-airport-to-old-quarter', '/plan/vietnam-sim-card-airport-vs-city/'),

        # northwest-vietnam-itinerary (4)
        ('where-to-stay-in-mai-chau', '/itineraries/northwest-vietnam-itinerary/'),
        ('where-to-stay-in-pu-luong', '/itineraries/northwest-vietnam-itinerary/'),
        ('ninh-binh-without-rushing', '/itineraries/northwest-vietnam-itinerary/'),
        ('10-days-in-vietnam', '/itineraries/northwest-vietnam-itinerary/'),

        # where-to-stay-in-meo-vac (4)
        ('where-to-stay-in-ha-giang', '/destinations/where-to-stay-in-meo-vac/'),
        ('ha-giang-loop-planning-guide', '/destinations/where-to-stay-in-meo-vac/'),
        ('ha-giang-easy-rider-vs-self-drive', '/destinations/where-to-stay-in-meo-vac/'),
        ('where-to-stay-in-dong-van', '/destinations/where-to-stay-in-meo-vac/'),

        # vietnam-gluten-free-travel-guide (4)
        ('vietnam-vegetarian-travel-guide', '/plan/vietnam-gluten-free-travel-guide/'),
        ('vietnam-food-safety-street-food-etiquette', '/plan/vietnam-gluten-free-travel-guide/'),
        ('hanoi-street-food-guide', '/plan/vietnam-gluten-free-travel-guide/'),
        ('saigon-street-food-guide', '/plan/vietnam-gluten-free-travel-guide/'),

        # hanoi-to-ninh-binh-train-vs-limousine (4)
        ('ninh-binh-travel-guide', '/plan/hanoi-to-ninh-binh-train-vs-limousine/'),
        ('hanoi-to-ninh-binh-transport', '/plan/hanoi-to-ninh-binh-train-vs-limousine/'),
        ('hanoi-travel-guide', '/plan/hanoi-to-ninh-binh-train-vs-limousine/'),
        ('transport-within-vietnam', '/plan/hanoi-to-ninh-binh-train-vs-limousine/'),
    ]

    print("=" * 70)
    print(f"STAGE 74: LIVE HTTP INBOUND LINK VERIFICATION ({len(expected_links)} LINKS)")
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
    print(f"STAGE 74 INLINK RESULT: {total_verified}/{len(expected_links)} INLINKS VERIFIED LIVE OVER HTTP!")
    print("=" * 70)

    if total_verified == len(expected_links):
        print("PERFECT: 100% Inlink Mesh Coverage!")
    else:
        print("WARNING: Some inlinks were not verified!")
        sys.exit(1)

if __name__ == '__main__':
    main()

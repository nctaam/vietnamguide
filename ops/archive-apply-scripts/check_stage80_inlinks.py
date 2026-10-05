# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 80 In-Link HTTP Verification Script
Validates that every one of the 7 Stage 80 pillars has at least 4 verified live inlinks over HTTP.
"""

import urllib.request
import re
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

# Map of new pillar slugs to the parent paths and list of hosts expected to link to them
STAGE80_PILLARS = {
    'sapa-to-ha-giang-transport': {
        'path': '/plan/sapa-to-ha-giang-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-sapa/',
            'https://vietnamguide.net/plan/hanoi-to-sapa-transport/',
            'https://vietnamguide.net/plan/hanoi-to-ha-giang-transport/',
            'https://vietnamguide.net/destinations/where-to-stay-in-ha-giang/',
            'https://vietnamguide.net/plan/transport-within-vietnam/',
        ]
    },
    'ho-chi-minh-city-to-ben-tre-transport': {
        'path': '/plan/ho-chi-minh-city-to-ben-tre-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-ben-tre/',
            'https://vietnamguide.net/plan/ho-chi-minh-city-to-can-tho-transport/',
            'https://vietnamguide.net/destinations/where-to-stay-in-ho-chi-minh-city/',
            'https://vietnamguide.net/plan/saigon-airport-to-district-1/',
            'https://vietnamguide.net/itineraries/southern-vietnam-itinerary/',
        ]
    },
    'cat-ba-to-ninh-binh-transport': {
        'path': '/plan/cat-ba-to-ninh-binh-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-cat-ba/',
            'https://vietnamguide.net/destinations/where-to-stay-in-ninh-binh/',
            'https://vietnamguide.net/destinations/where-to-stay-in-tam-coc/',
            'https://vietnamguide.net/plan/ninh-binh-to-ha-long-bay-transfer/',
            'https://vietnamguide.net/plan/hanoi-to-cat-ba-island-transport/',
        ]
    },
    'pleiku-to-kon-tum-transport': {
        'path': '/plan/pleiku-to-kon-tum-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-pleiku/',
            'https://vietnamguide.net/destinations/where-to-stay-in-kon-tum/',
            'https://vietnamguide.net/plan/buon-ma-thuot-to-pleiku-transport/',
            'https://vietnamguide.net/itineraries/central-highlands-vietnam-itinerary/',
            'https://vietnamguide.net/plan/vietnam-scooter-rental-checklist/',
        ]
    },
    'where-to-stay-in-cam-ranh': {
        'path': '/destinations/where-to-stay-in-cam-ranh/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-nha-trang/',
            'https://vietnamguide.net/plan/nha-trang-to-quy-nhon-transport/',
            'https://vietnamguide.net/plan/da-lat-to-nha-trang-transport/',
            'https://vietnamguide.net/plan/da-nang-to-nha-trang-transport/',
            'https://vietnamguide.net/plan/where-to-stay-in-vietnam-base-decisions/',
        ]
    },
    'where-to-stay-in-rach-gia': {
        'path': '/destinations/where-to-stay-in-rach-gia/',
        'expected_hosts': [
            'https://vietnamguide.net/plan/phu-quoc-ferry-guide/',
            'https://vietnamguide.net/destinations/where-to-stay-in-phu-quoc/',
            'https://vietnamguide.net/destinations/where-to-stay-in-ha-tien/',
            'https://vietnamguide.net/destinations/where-to-stay-in-can-tho/',
            'https://vietnamguide.net/plan/how-to-get-to-con-dao-flight-vs-ferry/',
            'https://vietnamguide.net/destinations/mekong-delta-travel-guide/',
        ]
    },
    'phu-quoc-ferry-guide': {
        'path': '/plan/phu-quoc-ferry-guide/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-rach-gia/',
            'https://vietnamguide.net/destinations/where-to-stay-in-ha-tien/',
            'https://vietnamguide.net/destinations/where-to-stay-in-phu-quoc/',
            'https://vietnamguide.net/plan/ho-chi-minh-city-to-phu-quoc-transport/',
            'https://vietnamguide.net/destinations/best-things-to-do-in-phu-quoc/',
            'https://vietnamguide.net/plan/transport-within-vietnam/',
        ]
    }
}

def verify_live_inlinks():
    print("=== VietnamGuide Stage 80 In-Link Live HTTP Verification ===")
    total_checks = 0
    total_passed = 0
    failed_links = []

    # Cache fetched host pages to avoid multiple downloads
    host_cache = {}

    for slug, data in STAGE80_PILLARS.items():
        print(f"\nVerifying Inlinks for: {slug} ({data['path']})")
        pillar_passed = 0
        for host_url in data['expected_hosts']:
            total_checks += 1
            if host_url not in host_cache:
                req = urllib.request.Request(host_url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
                try:
                    with urllib.request.urlopen(req, timeout=15) as resp:
                        host_cache[host_url] = resp.read().decode('utf-8', errors='ignore')
                except Exception as e:
                    print(f"  [ERROR] Failed to fetch {host_url}: {e}")
                    host_cache[host_url] = ""

            content = host_cache[host_url]
            # Check for the link
            if data['path'] in content or f'href="{data["path"]}"' in content or f"href='{data['path']}'" in content:
                print(f"  [PASS] Found inlink from: {host_url}")
                pillar_passed += 1
                total_passed += 1
            else:
                print(f"  [FAIL] Missing inlink in: {host_url}")
                failed_links.append((slug, host_url))

        print(f"  Result: {pillar_passed}/{len(data['expected_hosts'])} verified (min 4 required)")
        if pillar_passed < 4:
            print(f"  [CRITICAL] Pillar {slug} failed minimum 4 inbound link requirement!")

    print(f"\n==========================================")
    print(f"TOTAL INLINKS VERIFIED: {total_passed}/{total_checks}")
    if total_passed == total_checks:
        print("ALL STAGE 80 INLINKS VERIFIED 100% OVER LIVE HTTP!")
        return True
    else:
        print(f"FAILED INLINKS ({len(failed_links)}):")
        for p, h in failed_links:
            print(f"  - Pillar {p} missing in {h}")
        return False

if __name__ == '__main__':
    ok = verify_live_inlinks()
    sys.exit(0 if ok else 1)

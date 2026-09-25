# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 82 In-Link HTTP Verification Script
Validates that every one of the 7 Stage 82 pillars has at least 4 verified live inlinks over HTTP.
"""

import urllib.request
import re
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

STAGE82_PILLARS = {
    'hoi-an-to-quy-nhon-transport': {
        'path': '/plan/hoi-an-to-quy-nhon-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-quy-nhon/',
            'https://vietnamguide.net/destinations/where-to-stay-in-hoi-an/',
            'https://vietnamguide.net/plan/nha-trang-to-quy-nhon-transport/',
            'https://vietnamguide.net/plan/da-nang-to-quy-nhon-transport/',
            'https://vietnamguide.net/itineraries/central-vietnam-itinerary/',
        ]
    },
    'hanoi-to-hai-phong-transport': {
        'path': '/plan/hanoi-to-hai-phong-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-cat-ba/',
            'https://vietnamguide.net/plan/cat-ba-to-ninh-binh-transport/',
            'https://vietnamguide.net/plan/hanoi-to-cat-ba-island-transport/',
            'https://vietnamguide.net/plan/hanoi-to-ha-long-bay-transport/',
        ]
    },
    'can-tho-to-ha-tien-transport': {
        'path': '/plan/can-tho-to-ha-tien-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/plan/phu-quoc-ferry-guide/',
            'https://vietnamguide.net/plan/can-tho-to-rach-gia-transport/',
            'https://vietnamguide.net/plan/can-tho-to-chau-doc-transport/',
            'https://vietnamguide.net/destinations/where-to-stay-in-ha-tien/',
            'https://vietnamguide.net/destinations/where-to-stay-in-phu-quoc/',
            'https://vietnamguide.net/destinations/where-to-stay-in-rach-gia/',
        ]
    },
    'buon-ma-thuot-to-nha-trang-transport': {
        'path': '/plan/buon-ma-thuot-to-nha-trang-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/plan/da-lat-to-buon-ma-thuot-transport/',
            'https://vietnamguide.net/destinations/where-to-stay-in-buon-ma-thuot/',
            'https://vietnamguide.net/destinations/where-to-stay-in-nha-trang/',
            'https://vietnamguide.net/plan/pleiku-to-kon-tum-transport/',
            'https://vietnamguide.net/itineraries/central-highlands-vietnam-itinerary/',
        ]
    },
    'where-to-stay-in-bac-lieu': {
        'path': '/destinations/where-to-stay-in-bac-lieu/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-soc-trang/',
            'https://vietnamguide.net/destinations/where-to-stay-in-ben-tre/',
            'https://vietnamguide.net/destinations/where-to-stay-in-can-tho/',
            'https://vietnamguide.net/destinations/where-to-stay-in-chau-doc/',
            'https://vietnamguide.net/destinations/where-to-stay-in-ca-mau/',
        ]
    },
    'where-to-stay-in-ca-mau': {
        'path': '/destinations/where-to-stay-in-ca-mau/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-soc-trang/',
            'https://vietnamguide.net/destinations/where-to-stay-in-rach-gia/',
            'https://vietnamguide.net/destinations/where-to-stay-in-can-tho/',
            'https://vietnamguide.net/plan/how-to-get-to-con-dao-flight-vs-ferry/',
            'https://vietnamguide.net/plan/ho-chi-minh-city-to-can-tho-transport/',
            'https://vietnamguide.net/destinations/where-to-stay-in-bac-lieu/',
        ]
    },
    'vietnam-train-vs-flight': {
        'path': '/plan/vietnam-train-vs-flight/',
        'expected_hosts': [
            'https://vietnamguide.net/plan/vietnam-domestic-flights-guide/',
            'https://vietnamguide.net/plan/vietnam-train-travel/',
            'https://vietnamguide.net/plan/vietnam-sleeper-bus-survival-guide/',
            'https://vietnamguide.net/itineraries/da-nang-to-hue-train-vs-car/',
            'https://vietnamguide.net/plan/hanoi-to-sapa-train-vs-sleeper-bus/',
            'https://vietnamguide.net/plan/vietnam-night-train-safety-tips/',
        ]
    }
}

def verify_live_inlinks():
    print("=== VietnamGuide Stage 82 In-Link Live HTTP Verification ===")
    total_checks = 0
    total_passed = 0
    failed_links = []

    host_cache = {}

    for slug, data in STAGE82_PILLARS.items():
        print(f"\nVerifying Inlinks for: {slug} ({data['path']})")
        pillar_passed = 0
        path = data['path']
        
        for host_url in data['expected_hosts']:
            total_checks += 1
            if host_url not in host_cache:
                req = urllib.request.Request(
                    host_url,
                    headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) VietnamGuideMeshVerifier/1.0'}
                )
                try:
                    with urllib.request.urlopen(req, timeout=15) as resp:
                        body = resp.read().decode('utf-8', errors='ignore')
                        host_cache[host_url] = body
                except Exception as e:
                    print(f"  [ERROR] Could not fetch {host_url}: {e}")
                    host_cache[host_url] = ""

            body = host_cache[host_url]
            if path in body:
                print(f"  [PASS] Found in {host_url}")
                total_passed += 1
                pillar_passed += 1
            else:
                print(f"  [FAIL] MISSING in {host_url}")
                failed_links.append((slug, host_url, path))

        print(f"  --> Pillar '{slug}' has {pillar_passed} verified inbound links (Required >= 4)")
        if pillar_passed < 4:
            print(f"  [ERROR] Pillar '{slug}' has FEWER than 4 inbound links!")

    print("\n" + "=" * 70)
    print(f"TOTAL IN-LINKS VERIFIED: {total_passed}/{total_checks}")
    print("=" * 70)

    if failed_links:
        print(f"\nWARNING: {len(failed_links)} expected in-links were not found:")
        for slug, host_url, path in failed_links:
            print(f"  - {slug} missing from {host_url}")
        sys.exit(1)
    else:
        print("\nALL STAGE 82 INBOUND LINKS VERIFIED LIVE OVER HTTP (100% SUCCESS)!")
        sys.exit(0)

if __name__ == '__main__':
    verify_live_inlinks()

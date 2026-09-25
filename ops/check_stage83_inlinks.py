# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 83 In-Link HTTP Verification Script
Validates that every one of the 7 Stage 83 pillars has at least 4 verified live inlinks over HTTP.
"""

import urllib.request
import re
import sys
import time

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

STAGE83_PILLARS = {
    'where-to-stay-in-hai-phong': {
        'path': '/destinations/where-to-stay-in-hai-phong/',
        'expected_hosts': [
            'https://vietnamguide.net/plan/hanoi-to-hai-phong-transport/',
            'https://vietnamguide.net/destinations/where-to-stay-in-cat-ba/',
            'https://vietnamguide.net/plan/cat-ba-to-ninh-binh-transport/',
            'https://vietnamguide.net/plan/hanoi-to-ha-long-bay-transport/',
        ]
    },
    'dong-hoi-to-phong-nha-transport': {
        'path': '/plan/dong-hoi-to-phong-nha-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-dong-hoi/',
            'https://vietnamguide.net/destinations/where-to-stay-in-phong-nha/',
            'https://vietnamguide.net/plan/hue-to-dong-hoi-transport/',
            'https://vietnamguide.net/plan/hanoi-to-phong-nha-transport/',
            'https://vietnamguide.net/plan/da-nang-to-phong-nha-transport/',
        ]
    },
    'can-tho-to-ca-mau-transport': {
        'path': '/plan/can-tho-to-ca-mau-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-ca-mau/',
            'https://vietnamguide.net/destinations/where-to-stay-in-bac-lieu/',
            'https://vietnamguide.net/destinations/where-to-stay-in-soc-trang/',
            'https://vietnamguide.net/destinations/where-to-stay-in-can-tho/',
            'https://vietnamguide.net/plan/can-tho-to-ha-tien-transport/',
        ]
    },
    'where-to-stay-in-phan-rang': {
        'path': '/destinations/where-to-stay-in-phan-rang/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-cam-ranh/',
            'https://vietnamguide.net/destinations/where-to-stay-in-nha-trang/',
            'https://vietnamguide.net/destinations/where-to-stay-in-mui-ne/',
            'https://vietnamguide.net/plan/buon-ma-thuot-to-nha-trang-transport/',
            'https://vietnamguide.net/plan/nha-trang-to-quy-nhon-transport/',
        ]
    },
    'hanoi-to-tam-dao-transport': {
        'path': '/plan/hanoi-to-tam-dao-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-tam-dao/',
            'https://vietnamguide.net/itineraries/best-day-trips-from-hanoi/',
            'https://vietnamguide.net/destinations/hanoi-travel-guide/',
            'https://vietnamguide.net/plan/hanoi-to-hai-phong-transport/',
            'https://vietnamguide.net/plan/hanoi-to-ha-long-bay-transport/',
        ]
    },
    'where-to-stay-in-tam-dao': {
        'path': '/destinations/where-to-stay-in-tam-dao/',
        'expected_hosts': [
            'https://vietnamguide.net/plan/hanoi-to-tam-dao-transport/',
            'https://vietnamguide.net/destinations/where-to-stay-in-mai-chau/',
            'https://vietnamguide.net/destinations/where-to-stay-in-ninh-binh/',
            'https://vietnamguide.net/destinations/where-to-stay-in-hanoi/',
            'https://vietnamguide.net/itineraries/best-day-trips-from-hanoi/',
        ]
    },
    'kon-tum-to-da-nang-transport': {
        'path': '/plan/kon-tum-to-da-nang-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-kon-tum/',
            'https://vietnamguide.net/plan/pleiku-to-kon-tum-transport/',
            'https://vietnamguide.net/destinations/where-to-stay-in-da-nang/',
            'https://vietnamguide.net/destinations/where-to-stay-in-pleiku/',
            'https://vietnamguide.net/itineraries/central-highlands-vietnam-itinerary/',
        ]
    }
}

def verify_live_inlinks():
    print("=== VietnamGuide Stage 83 In-Link Live HTTP Verification ===")
    total_checks = 0
    total_passed = 0
    failed_links = []

    host_cache = {}

    for slug, data in STAGE83_PILLARS.items():
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
                body = ""
                for attempt in range(3):
                    try:
                        with urllib.request.urlopen(req, timeout=25) as resp:
                            body = resp.read().decode('utf-8', errors='ignore')
                            break
                    except Exception as e:
                        if attempt == 2:
                            print(f"  [ERROR] Could not fetch {host_url}: {e}")
                        else:
                            time.sleep(1)
                host_cache[host_url] = body

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
        print("\nALL STAGE 83 INBOUND LINKS VERIFIED LIVE OVER HTTP (100% SUCCESS)!")
        sys.exit(0)

if __name__ == '__main__':
    verify_live_inlinks()

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 81 In-Link HTTP Verification Script
Validates that every one of the 7 Stage 81 pillars has at least 4 verified live inlinks over HTTP.
"""

import urllib.request
import re
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

STAGE81_PILLARS = {
    'ha-giang-to-cao-bang-transport': {
        'path': '/plan/ha-giang-to-cao-bang-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-cao-bang/',
            'https://vietnamguide.net/destinations/where-to-stay-in-ha-giang/',
            'https://vietnamguide.net/destinations/where-to-stay-in-meo-vac/',
            'https://vietnamguide.net/destinations/where-to-stay-in-dong-van/',
            'https://vietnamguide.net/plan/sapa-to-ha-giang-transport/',
            'https://vietnamguide.net/itineraries/northeast-vietnam-itinerary/',
        ]
    },
    'sapa-to-mu-cang-chai-transport': {
        'path': '/plan/sapa-to-mu-cang-chai-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-mu-cang-chai/',
            'https://vietnamguide.net/destinations/where-to-stay-in-sapa/',
            'https://vietnamguide.net/plan/sapa-to-ha-giang-transport/',
            'https://vietnamguide.net/itineraries/northwest-vietnam-itinerary/',
            'https://vietnamguide.net/plan/ha-giang-to-cao-bang-transport/',
        ]
    },
    'can-tho-to-rach-gia-transport': {
        'path': '/plan/can-tho-to-rach-gia-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-rach-gia/',
            'https://vietnamguide.net/destinations/where-to-stay-in-can-tho/',
            'https://vietnamguide.net/plan/can-tho-to-chau-doc-transport/',
            'https://vietnamguide.net/plan/phu-quoc-ferry-guide/',
            'https://vietnamguide.net/destinations/where-to-stay-in-soc-trang/',
        ]
    },
    'hue-to-dong-hoi-transport': {
        'path': '/plan/hue-to-dong-hoi-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-dong-hoi/',
            'https://vietnamguide.net/destinations/where-to-stay-in-hue/',
            'https://vietnamguide.net/destinations/where-to-stay-in-phong-nha/',
            'https://vietnamguide.net/plan/vietnam-night-train-safety-tips/',
            'https://vietnamguide.net/plan/da-lat-to-buon-ma-thuot-transport/',
        ]
    },
    'da-lat-to-buon-ma-thuot-transport': {
        'path': '/plan/da-lat-to-buon-ma-thuot-transport/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-buon-ma-thuot/',
            'https://vietnamguide.net/destinations/where-to-stay-in-da-lat/',
            'https://vietnamguide.net/plan/pleiku-to-kon-tum-transport/',
            'https://vietnamguide.net/destinations/where-to-stay-in-pleiku/',
            'https://vietnamguide.net/destinations/where-to-stay-in-cam-ranh/',
        ]
    },
    'where-to-stay-in-soc-trang': {
        'path': '/destinations/where-to-stay-in-soc-trang/',
        'expected_hosts': [
            'https://vietnamguide.net/plan/how-to-get-to-con-dao-flight-vs-ferry/',
            'https://vietnamguide.net/destinations/where-to-stay-in-con-dao/',
            'https://vietnamguide.net/destinations/where-to-stay-in-can-tho/',
            'https://vietnamguide.net/destinations/where-to-stay-in-ben-tre/',
            'https://vietnamguide.net/plan/can-tho-to-rach-gia-transport/',
        ]
    },
    'vietnam-domestic-flights-guide': {
        'path': '/plan/vietnam-domestic-flights-guide/',
        'expected_hosts': [
            'https://vietnamguide.net/destinations/where-to-stay-in-cam-ranh/',
            'https://vietnamguide.net/plan/how-to-get-to-con-dao-flight-vs-ferry/',
            'https://vietnamguide.net/plan/vietnam-night-train-safety-tips/',
            'https://vietnamguide.net/plan/vietnam-sleeper-bus-survival-guide/',
            'https://vietnamguide.net/plan/hue-to-dong-hoi-transport/',
        ]
    }
}

def verify_live_inlinks():
    print("=== VietnamGuide Stage 81 In-Link Live HTTP Verification ===")
    total_checks = 0
    total_passed = 0
    failed_links = []

    host_cache = {}

    for slug, data in STAGE81_PILLARS.items():
        print(f"\nVerifying Inlinks for: {slug} ({data['path']})")
        pillar_passed = 0
        path = data['path']
        
        for host in data['expected_hosts']:
            total_checks += 1
            if host not in host_cache:
                try:
                    req = urllib.request.Request(host, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
                    with urllib.request.urlopen(req, timeout=15) as resp:
                        body = resp.read().decode('utf-8', errors='ignore')
                        host_cache[host] = (resp.status, body)
                except Exception as e:
                    host_cache[host] = (0, str(e))
            
            status, body = host_cache[host]
            if status == 200:
                # Check for exact href link
                link_pattern = re.compile(rf'href=["\']{re.escape(path)}["\']', re.IGNORECASE)
                if link_pattern.search(body):
                    print(f"  [PASS] Found link on: {host}")
                    pillar_passed += 1
                    total_passed += 1
                else:
                    print(f"  [FAIL] Missing link on: {host}")
                    failed_links.append((slug, host, "Link href not found in HTML"))
            else:
                print(f"  [ERROR] Could not fetch host: {host} (Status {status})")
                failed_links.append((slug, host, f"HTTP Error {status}"))
        
        status_str = "SUCCESS" if pillar_passed >= 4 else "FAILED"
        print(f"  -> {slug}: {pillar_passed}/{len(data['expected_hosts'])} verified inlinks [{status_str}]")

    print("\n" + "=" * 70)
    print(f"Total Live HTTP In-Link Checks: {total_passed}/{total_checks} PASSED")
    if failed_links:
        print("\nFailed Checks:")
        for slug, host, err in failed_links:
            print(f"  - {slug} from {host}: {err}")
        print("=" * 70)
        sys.exit(1)
    else:
        print("ALL 7 STAGE 81 PILLARS SATISFY INLINK REQUIREMENTS (>= 4 VERIFIED INLINKS EACH)!")
        print("=" * 70)
        sys.exit(0)

if __name__ == '__main__':
    verify_live_inlinks()

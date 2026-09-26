# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 86: Test Mesh Candidates
Verifies that all candidate string replacements match exactly once (count == 1) in stage86_mesh_targets.json.
"""
import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

ops_dir = os.path.dirname(os.path.abspath(__file__))
with open(os.path.join(ops_dir, 'stage86_mesh_targets.json'), 'r', encoding='utf-8') as f:
    data = json.load(f)

# Define mesh replacements
# format: (host_slug, target_str, replacement_str, target_pillar_slug)
CANDIDATES = [
    # --- Group 1: where-to-stay-in-ly-son ---
    (
        'ly-son-travel-guide',
        'and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding a Sa Ky ferry island to the route.</p>',
        'our detailed <a href="/destinations/where-to-stay-in-ly-son/">where to stay in Ly Son</a> hotel review, mainland connections in <a href="/plan/da-nang-to-ly-son-transport/">Da Nang to Ly Son transport</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding a Sa Ky ferry island to the route.</p>',
        'where-to-stay-in-ly-son'
    ),
    (
        'ly-son-vs-cham-islands',
        'or coordinate mainland itineraries with <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a>.</p>',
        'explore island bases in our <a href="/destinations/where-to-stay-in-ly-son/">where to stay in Ly Son</a> guide, review speedboat connections in <a href="/plan/da-nang-to-ly-son-transport/">Da Nang to Ly Son transport</a>, or coordinate mainland itineraries with <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a>.</p>',
        'where-to-stay-in-ly-son'
    ),
    (
        'where-to-stay-in-quy-nhon',
        '<li><a href="/destinations/quy-nhon-travel-guide/">Quy Nhon Travel Guide</a>: Comprehensive overview of beaches, attractions, and history.</li>',
        '<li><a href="/destinations/quy-nhon-travel-guide/">Quy Nhon Travel Guide</a>: Comprehensive overview of beaches, attractions, and history.</li>\n        <li><a href="/destinations/where-to-stay-in-ly-son/">Where to Stay in Ly Son</a>: Volcanic Big Island harbor hotels vs Little Island beach eco-homestays.</li>',
        'where-to-stay-in-ly-son'
    ),
    (
        'where-to-stay-in-da-nang',
        '<li><a href="/plan/kon-tum-to-da-nang-transport/">Kon Tum to Da Nang Transport</a> &mdash; descending the Ho Chi Minh Trail over Lo Xo Pass to the coast.</li>',
        '<li><a href="/plan/kon-tum-to-da-nang-transport/">Kon Tum to Da Nang Transport</a> &mdash; descending the Ho Chi Minh Trail over Lo Xo Pass to the coast.</li>\n<li><a href="/destinations/where-to-stay-in-ly-son/">Where to Stay in Ly Son</a> &mdash; volcanic Big Island harbor hotels vs quiet Little Island beach homestays.</li>\n<li><a href="/plan/da-nang-to-ly-son-transport/">Da Nang to Ly Son Transport</a> &mdash; coastal trains and express speedboats from Sa Ky Port.</li>',
        'where-to-stay-in-ly-son'
    ),

    # --- Group 2: mang-den-travel-guide ---
    (
        'where-to-stay-in-mang-den',
        'Extend your regional route planning with our <a href="/destinations/where-to-stay-in-kon-tum/">where to stay in Kon Tum</a> directory,',
        'Explore attractions, cafes, and loop routes in our comprehensive <a href="/destinations/mang-den-travel-guide/">Mang Den travel guide</a>, extend your regional route planning with our <a href="/destinations/where-to-stay-in-kon-tum/">where to stay in Kon Tum</a> directory,',
        'mang-den-travel-guide'
    ),
    (
        'where-to-stay-in-kon-tum',
        '<li><a href="/destinations/where-to-stay-in-mang-den/">Where to Stay in Mang Den</a>: Pine forest wooden chalets, Pa Sy waterfall eco-lodges, and boutique hotels.</li>',
        '<li><a href="/destinations/where-to-stay-in-mang-den/">Where to Stay in Mang Den</a>: Pine forest wooden chalets, Pa Sy waterfall eco-lodges, and boutique hotels.</li>\n        <li><a href="/destinations/mang-den-travel-guide/">Mang Den Travel Guide</a>: Pa Sy waterfall, pine forest trails, and highland cultural villages.</li>',
        'mang-den-travel-guide'
    ),
    (
        'pleiku-to-kon-tum-transport',
        '<li><a href="/destinations/where-to-stay-in-mang-den/">Where to Stay in Mang Den</a>: Pine forest chalets, Pa Sy waterfall lodges, and cool highland retreats.</li>',
        '<li><a href="/destinations/where-to-stay-in-mang-den/">Where to Stay in Mang Den</a>: Pine forest chalets, Pa Sy waterfall lodges, and cool highland retreats.</li>\n        <li><a href="/destinations/mang-den-travel-guide/">Mang Den Travel Guide</a>: Complete highland guide to waterfalls, pine trails, and weather.</li>',
        'mang-den-travel-guide'
    ),
    (
        'kon-tum-to-da-nang-transport',
        'check pine forest chalets in <a href="/destinations/where-to-stay-in-mang-den/">where to stay in Mang Den</a>,',
        'explore alpine trails in our <a href="/destinations/mang-den-travel-guide/">Mang Den travel guide</a>, check pine forest chalets in <a href="/destinations/where-to-stay-in-mang-den/">where to stay in Mang Den</a>,',
        'mang-den-travel-guide'
    ),
    (
        'where-to-stay-in-pleiku',
        '<li><a href="/destinations/where-to-stay-in-mang-den/">Where to Stay in Mang Den</a>: Pine forest wooden chalets and Pa Sy waterfall eco-lodges.</li>',
        '<li><a href="/destinations/where-to-stay-in-mang-den/">Where to Stay in Mang Den</a>: Pine forest wooden chalets and Pa Sy waterfall eco-lodges.</li>\n        <li><a href="/destinations/mang-den-travel-guide/">Mang Den Travel Guide</a>: Pine forests, waterfalls, and cool mountain plateau itineraries.</li>',
        'mang-den-travel-guide'
    ),

    # --- Group 3: ba-be-lake-travel-guide ---
    (
        'where-to-stay-in-ba-be',
        '<li><a href="/destinations/cao-bang-travel-guide/">Cao Bang Travel Guide</a>: Extend your northern journey from Ba Be to Ban Gioc Waterfall.</li>',
        '<li><a href="/destinations/cao-bang-travel-guide/">Cao Bang Travel Guide</a>: Extend your northern journey from Ba Be to Ban Gioc Waterfall.</li>\n        <li><a href="/destinations/ba-be-lake-travel-guide/">Ba Be Lake Travel Guide</a>: Boat tours, Puong Cave river passage, Dau Dang waterfall, and Pac Ngoi homestays.</li>',
        'ba-be-lake-travel-guide'
    ),
    (
        'hanoi-to-ba-be-transport',
        '<li><a href="/destinations/where-to-stay-in-ba-be/">Where to Stay in Ba Be</a>: Stilt village homestays in Pac Ngoi and Bo Lu waterfront lodges.</li>',
        '<li><a href="/destinations/where-to-stay-in-ba-be/">Where to Stay in Ba Be</a>: Stilt village homestays in Pac Ngoi and Bo Lu waterfront lodges.</li>\n        <li><a href="/destinations/ba-be-lake-travel-guide/">Ba Be Lake Travel Guide</a>: Motorized boat charters, kayak rentals, and national park exploration.</li>',
        'ba-be-lake-travel-guide'
    ),
    (
        'cao-bang-to-ba-be-transport',
        'Organize your lodging with our <a href="/destinations/where-to-stay-in-ba-be/">where to stay in Ba Be</a> guide,',
        'Explore boat tours and cave routes in our <a href="/destinations/ba-be-lake-travel-guide/">Ba Be Lake travel guide</a>, organize your lodging with our <a href="/destinations/where-to-stay-in-ba-be/">where to stay in Ba Be</a> guide,',
        'ba-be-lake-travel-guide'
    ),
    (
        'cao-bang-travel-guide',
        '<li><a href="/destinations/where-to-stay-in-ba-be/">Where to Stay in Ba Be: Stilt Homestays &amp; Lakeside Lodges</a></li>',
        '<li><a href="/destinations/where-to-stay-in-ba-be/">Where to Stay in Ba Be: Stilt Homestays &amp; Lakeside Lodges</a></li>\n<li><a href="/destinations/ba-be-lake-travel-guide/">Ba Be Lake Travel Guide: Boat Charters, Puong Cave &amp; Homestays</a></li>',
        'ba-be-lake-travel-guide'
    ),
    (
        'where-to-stay-in-cao-bang',
        'connect to the national park via our <a href="/plan/cao-bang-to-ba-be-transport/">Cao Bang to Ba Be transport</a> guide,',
        'connect to the national park via our <a href="/plan/cao-bang-to-ba-be-transport/">Cao Bang to Ba Be transport</a> guide, plan lake boat excursions with our <a href="/destinations/ba-be-lake-travel-guide/">Ba Be Lake travel guide</a>,',
        'ba-be-lake-travel-guide'
    ),

    # --- Group 4: da-nang-to-ly-son-transport ---
    (
        'da-nang-travel-guide',
        'northbound rail routes via <a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>.',
        'northbound rail routes via <a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport</a>, offshore volcanic island transfers via <a href="/plan/da-nang-to-ly-son-transport/">Da Nang to Ly Son Transport</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>.',
        'da-nang-to-ly-son-transport'
    ),

    # --- Group 5: hue-to-phong-nha-transport ---
    (
        'where-to-stay-in-phong-nha',
        '<li><a href="/plan/phong-nha-to-hue-transport/">Phong Nha to Hue Transport</a></li>',
        '<li><a href="/plan/phong-nha-to-hue-transport/">Phong Nha to Hue Transport</a></li>\n        <li><a href="/plan/hue-to-phong-nha-transport/">Hue to Phong Nha Transport</a>: Direct tourist buses, trains to Dong Hoi, and DMZ tour cars.</li>',
        'hue-to-phong-nha-transport'
    ),
    (
        'phong-nha-to-hue-transport',
        '<li><a href="/plan/hanoi-to-hue-transport/">Hanoi to Hue Transport: Overnight Sleeper Trains vs Flights</a></li>',
        '<li><a href="/plan/hanoi-to-hue-transport/">Hanoi to Hue Transport: Overnight Sleeper Trains vs Flights</a></li>\n<li><a href="/plan/hue-to-phong-nha-transport/">Hue to Phong Nha Transport: Direct Tourist Buses, Trains &amp; DMZ Tours</a></li>',
        'hue-to-phong-nha-transport'
    ),
    (
        'where-to-stay-in-hue',
        '<li><a href="/plan/dong-hoi-to-hue-transport/">Dong Hoi to Hue Transport</a>: Southbound trains, regional buses, and private car options via the DMZ.</li>',
        '<li><a href="/plan/dong-hoi-to-hue-transport/">Dong Hoi to Hue Transport</a>: Southbound trains, regional buses, and private car options via the DMZ.</li>\n        <li><a href="/plan/hue-to-phong-nha-transport/">Hue to Phong Nha Transport</a>: Northbound tourist buses, trains to Dong Hoi, and private DMZ car excursions.</li>',
        'hue-to-phong-nha-transport'
    ),
    (
        'dong-hoi-to-phong-nha-transport',
        'plan coastal transit options via <a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi transport</a> and <a href="/plan/dong-hoi-to-hue-transport/">Dong Hoi to Hue transport</a>,',
        'plan coastal transit options via <a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi transport</a>, <a href="/plan/hue-to-phong-nha-transport/">Hue to Phong Nha transport</a>, and <a href="/plan/dong-hoi-to-hue-transport/">Dong Hoi to Hue transport</a>,',
        'hue-to-phong-nha-transport'
    ),

    # --- Group 6: mai-chau-to-pu-luong-transport ---
    (
        'where-to-stay-in-pu-luong',
        '<li><a href="/destinations/pu-luong-travel-guide/">Pu Luong Travel Guide</a>: Trekking itineraries, waterwheel trails, and harvest calendars.</li>',
        '<li><a href="/destinations/pu-luong-travel-guide/">Pu Luong Travel Guide</a>: Trekking itineraries, waterwheel trails, and harvest calendars.</li>\n        <li><a href="/plan/mai-chau-to-pu-luong-transport/">Mai Chau to Pu Luong Transport</a>: Shared resort shuttle vans, scenic motorbike rides, and private car options via Highway 15C.</li>',
        'mai-chau-to-pu-luong-transport'
    ),
    (
        'where-to-stay-in-mai-chau',
        '<li><a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>: Compare stilt homestays with terraced valley retreats.</li>',
        '<li><a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>: Compare stilt homestays with terraced valley retreats.</li>\n        <li><a href="/plan/mai-chau-to-pu-luong-transport/">Mai Chau to Pu Luong Transport</a>: Connecting White Thai stilt villages with terraced reserve eco-lodges.</li>',
        'mai-chau-to-pu-luong-transport'
    ),
    (
        'hanoi-to-pu-luong-transport',
        '<li><a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>: Valley viewpoints, mountain retreats, and Ban Don eco-resorts.</li>',
        '<li><a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>: Valley viewpoints, mountain retreats, and Ban Don eco-resorts.</li>\n        <li><a href="/plan/mai-chau-to-pu-luong-transport/">Mai Chau to Pu Luong Transport</a>: Inter-valley resort shuttles, taxis, and scenic mountain motorbike routes.</li>',
        'mai-chau-to-pu-luong-transport'
    ),
    (
        'hanoi-to-mai-chau-transport',
        '<li><a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>: Valley viewpoints, mountain retreats, and Ban Don eco-resorts.</li>',
        '<li><a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>: Valley viewpoints, mountain retreats, and Ban Don eco-resorts.</li>\n        <li><a href="/plan/mai-chau-to-pu-luong-transport/">Mai Chau to Pu Luong Transport</a>: Cross-provincial shuttle vans and private transfers into Thanh Hoa.</li>',
        'mai-chau-to-pu-luong-transport'
    ),
    (
        'pu-luong-travel-guide',
        'ions/where-to-stay-in-mai-chau/">Where to Stay in Mai Chau</a>,',
        'ions/where-to-stay-in-mai-chau/">Where to Stay in Mai Chau</a>, mountain transit advice in <a href="/plan/mai-chau-to-pu-luong-transport/">Mai Chau to Pu Luong Transport</a>,',
        'mai-chau-to-pu-luong-transport'
    ),

    # --- Group 7: ha-tien-to-phu-quoc-ferry ---
    (
        'where-to-stay-in-ha-tien',
        '<li><a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc</a>: Beach resorts, sunset hotels, and town bases on Bai Dai and Bai Truong.</li>',
        '<li><a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc</a>: Beach resorts, sunset hotels, and town bases on Bai Dai and Bai Truong.</li>\n        <li><a href="/plan/ha-tien-to-phu-quoc-ferry/">Ha Tien to Phu Quoc Ferry</a>: Superdong vs Phu Quoc Express fast catamarans, timetables, and pier logistics.</li>',
        'ha-tien-to-phu-quoc-ferry'
    ),
    (
        'where-to-stay-in-phu-quoc',
        '<li><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a></li>',
        '<li><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a></li>\n        <li><a href="/plan/ha-tien-to-phu-quoc-ferry/">Ha Tien to Phu Quoc Ferry</a>: High-speed catamarans and vehicle ferry connections from the mainland.</li>',
        'ha-tien-to-phu-quoc-ferry'
    ),
    (
        'can-tho-to-ha-tien-transport',
        'Review sailing schedules and ticket prices in our <a href="/plan/phu-quoc-ferry-guide/">Phu Quoc ferry guide</a>,',
        'Review fast speedboat options in our <a href="/plan/ha-tien-to-phu-quoc-ferry/">Ha Tien to Phu Quoc ferry</a> guide, explore comprehensive crossing routes in our <a href="/plan/phu-quoc-ferry-guide/">Phu Quoc ferry guide</a>,',
        'ha-tien-to-phu-quoc-ferry'
    ),
    (
        'phu-quoc-ferry-guide',
        '<li><a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc</a>: Sunset beach resorts, family bungalows, and island villas.</li>',
        '<li><a href="/destinations/where-to-stay-in-phu-quoc/">Where to Stay in Phu Quoc</a>: Sunset beach resorts, family bungalows, and island villas.</li>\n        <li><a href="/plan/ha-tien-to-phu-quoc-ferry/">Ha Tien to Phu Quoc Ferry</a>: Detailed breakdown of the fastest 75-minute catamaran route from Ha Tien port.</li>',
        'ha-tien-to-phu-quoc-ferry'
    ),
]

def main():
    print(f"=== Testing {len(CANDIDATES)} Mesh Candidates ===")
    all_passed = True
    counts_per_pillar = {}

    for i, (host_slug, target_str, replacement_str, target_pillar) in enumerate(CANDIDATES, 1):
        if host_slug not in data:
            print(f"[FAIL] Candidate {i:02d}: Host {host_slug} not found in fetched data!")
            all_passed = False
            continue

        content = data[host_slug]['content']
        count = content.count(target_str)
        if count == 1:
            print(f"[PASS] Candidate {i:02d}: {host_slug} -> {target_pillar} (exact count=1)")
            counts_per_pillar[target_pillar] = counts_per_pillar.get(target_pillar, 0) + 1
        elif count == 0:
            print(f"[FAIL] Candidate {i:02d}: {host_slug} -> {target_pillar} (TARGET NOT FOUND)")
            all_passed = False
        else:
            print(f"[FAIL] Candidate {i:02d}: {host_slug} -> {target_pillar} (Ambiguous count={count})")
            all_passed = False

    print("\n=== Inbound Links Breakdown by Pillar (From External Hosts) ===")
    for pillar, cnt in sorted(counts_per_pillar.items()):
        print(f"  {pillar}: {cnt} external inbound links")

    if all_passed:
        print(f"\nALL {len(CANDIDATES)} CANDIDATES PASSED VALIDATION!")
    else:
        print("\nSOME CANDIDATES FAILED!")
        sys.exit(1)

if __name__ == '__main__':
    main()

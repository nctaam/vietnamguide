# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 87: Test Mesh Candidates
Verifies that all candidate string replacements match exactly once (count == 1) in stage87_mesh_targets.json.
"""
import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

ops_dir = os.path.dirname(os.path.abspath(__file__))
with open(os.path.join(ops_dir, 'stage87_mesh_targets.json'), 'r', encoding='utf-8') as f:
    data = json.load(f)

# Define mesh replacements
# format: (host_slug, target_str, replacement_str, target_pillar_slug)
CANDIDATES = [
    # --- Group 1: where-to-stay-in-yen-minh ---
    (
        'ha-giang-loop-planning-guide',
        '<li><span class="vg-related-route-step">11</span><a href="/destinations/where-to-stay-in-meo-vac/">Where to Stay in Meo Vac</a><span class="vg-related-route-note">Pa Vi Hmong cultural village stays and Ma Pi Leng pass access.</span></li>',
        '<li><span class="vg-related-route-step">11</span><a href="/destinations/where-to-stay-in-yen-minh/">Where to Stay in Yen Minh</a><span class="vg-related-route-note">Pine forest homestays and town transit guesthouses for Night 1.</span></li>\n<li><span class="vg-related-route-step">12</span><a href="/destinations/where-to-stay-in-meo-vac/">Where to Stay in Meo Vac</a><span class="vg-related-route-note">Pa Vi Hmong cultural village stays and Ma Pi Leng pass access.</span></li>',
        'where-to-stay-in-yen-minh'
    ),
    (
        'ha-giang-safety-guide',
        'realistic expenses in our <a href="/costs/ha-giang-loop-cost-budget/">Ha Giang Loop Cost &amp; Budget breakdown</a>',
        'nightly stopovers in <a href="/destinations/where-to-stay-in-yen-minh/">where to stay in Yen Minh</a>, realistic expenses in our <a href="/costs/ha-giang-loop-cost-budget/">Ha Giang Loop Cost &amp; Budget breakdown</a>',
        'where-to-stay-in-yen-minh'
    ),
    (
        'where-to-stay-in-ha-giang',
        '<li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>: Old Quarter heritage homestays, cliffside lodges, and fortress bases.</li>',
        '<li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>: Old Quarter heritage homestays, cliffside lodges, and fortress bases.</li>\n        <li><a href="/destinations/where-to-stay-in-yen-minh/">Where to Stay in Yen Minh</a>: Town center guesthouses vs pine forest homestays for loop Night 1.</li>',
        'where-to-stay-in-yen-minh'
    ),
    (
        'where-to-stay-in-dong-van',
        'explore quiet valley lodgings in our <a href="/destinations/where-to-stay-in-du-gia/">where to stay in Du Gia</a> guide, and compare provincial lodging in our <a href="/destinations/where-to-stay-in-cao-bang/">where to stay in Cao Bang</a> review.',
        'review Night 1 bases in our <a href="/destinations/where-to-stay-in-yen-minh/">where to stay in Yen Minh</a> guide, master the mountain ascent via our <a href="/plan/ha-giang-to-dong-van-transport/">Ha Giang to Dong Van transport</a> guide, explore quiet valley lodgings in our <a href="/destinations/where-to-stay-in-du-gia/">where to stay in Du Gia</a> guide, and compare provincial lodging in our <a href="/destinations/where-to-stay-in-cao-bang/">where to stay in Cao Bang</a> review.',
        'where-to-stay-in-yen-minh & ha-giang-to-dong-van-transport'
    ),

    # --- Group 2: where-to-stay-in-quang-ngai ---
    (
        'where-to-stay-in-ly-son',
        'or connect south to coastal beaches in <a href="/destinations/where-to-stay-in-quy-nhon/">where to stay in Quy Nhon</a>.',
        'review mainland transit bases in <a href="/destinations/where-to-stay-in-quang-ngai/">where to stay in Quang Ngai</a>, or connect south to coastal beaches in <a href="/destinations/where-to-stay-in-quy-nhon/">where to stay in Quy Nhon</a>.',
        'where-to-stay-in-quang-ngai'
    ),
    (
        'da-nang-to-ly-son-transport',
        'or explore coastal resort bases in <a href="/destinations/where-to-stay-in-da-nang/">where to stay in Da Nang</a>.',
        'check port hotels in <a href="/destinations/where-to-stay-in-quang-ngai/">where to stay in Quang Ngai</a>, or explore coastal resort bases in <a href="/destinations/where-to-stay-in-da-nang/">where to stay in Da Nang</a>.',
        'where-to-stay-in-quang-ngai'
    ),
    (
        'ly-son-travel-guide',
        'mainland connections in <a href="/plan/da-nang-to-ly-son-transport/">Da Nang to Ly Son transport</a>,',
        'mainland connections in <a href="/plan/da-nang-to-ly-son-transport/">Da Nang to Ly Son transport</a>, ferry transit hotels in <a href="/destinations/where-to-stay-in-quang-ngai/">where to stay in Quang Ngai</a>,',
        'where-to-stay-in-quang-ngai'
    ),
    (
        'where-to-stay-in-quy-nhon',
        '<li><a href="/destinations/where-to-stay-in-phu-yen/">Where to Stay in Phu Yen</a>: Tuy Hoa beachfront resorts, Bai Xep eco-lodges, and south-coast bases.</li>\n    </ul>',
        '<li><a href="/destinations/where-to-stay-in-phu-yen/">Where to Stay in Phu Yen</a>: Tuy Hoa beachfront resorts, Bai Xep eco-lodges, and south-coast bases.</li>\n        <li><a href="/destinations/where-to-stay-in-quang-ngai/">Where to Stay in Quang Ngai</a>: City center riverside hotels vs Sa Ky Port transit motels for Ly Son speedboats.</li>\n        <li><a href="/plan/quy-nhon-to-hoi-an-transport/">Quy Nhon to Hoi An Transport</a>: Trains from Dieu Tri to Tra Kieu and direct sleeper limousines.</li>\n    </ul>',
        'where-to-stay-in-quang-ngai & quy-nhon-to-hoi-an-transport'
    ),

    # --- Group 3: ha-giang-to-dong-van-transport ---
    (
        'hanoi-to-ha-giang-transport',
        '<li><span class="vg-related-route-step">10</span><a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport</a><span class="vg-related-route-note">Connect mountain loop hubs without backtracking to Hanoi.</span></li>',
        '<li><span class="vg-related-route-step">10</span><a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport</a><span class="vg-related-route-note">Connect mountain loop hubs without backtracking to Hanoi.</span></li>\n<li><span class="vg-related-route-step">11</span><a href="/plan/ha-giang-to-dong-van-transport/">Ha Giang to Dong Van Transport</a><span class="vg-related-route-note">Minibuses, motorbikes, and Easy Riders along QL4C Happiness Road.</span></li>',
        'ha-giang-to-dong-van-transport'
    ),
    (
        'ha-giang-easy-rider-vs-self-drive',
        '<a href="/plan/ha-giang-safety-guide/">Ha Giang Safety &amp; Insurance Guide</a>,',
        '<a href="/plan/ha-giang-safety-guide/">Ha Giang Safety &amp; Insurance Guide</a>, pass logistics in <a href="/plan/ha-giang-to-dong-van-transport/">Ha Giang to Dong Van Transport</a>,',
        'ha-giang-to-dong-van-transport'
    ),
    (
        'ha-giang-loop-cost-budget',
        'and <a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>,',
        '<a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>, mountain transit in <a href="/plan/ha-giang-to-dong-van-transport/">Ha Giang to Dong Van Transport</a>,',
        'ha-giang-to-dong-van-transport'
    ),

    # --- Group 4: rach-gia-to-phu-quoc-ferry ---
    (
        'ha-tien-to-phu-quoc-ferry',
        'or review national ferry options in our comprehensive <a href="/plan/phu-quoc-ferry-guide/">Phu Quoc ferry guide</a>.',
        'compare southern port departures via our <a href="/plan/rach-gia-to-phu-quoc-ferry/">Rach Gia to Phu Quoc ferry</a> guide, or review national ferry options in our comprehensive <a href="/plan/phu-quoc-ferry-guide/">Phu Quoc ferry guide</a>.',
        'rach-gia-to-phu-quoc-ferry'
    ),
    (
        'where-to-stay-in-rach-gia',
        '<li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Border crossing bases and coastal riverfront hotels.</li>',
        '<li><a href="/plan/rach-gia-to-phu-quoc-ferry/">Rach Gia to Phu Quoc Ferry</a>: Speedboat timetables, catamaran fares, and terminal boarding advice.</li>\n        <li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Border crossing bases and coastal riverfront hotels.</li>',
        'rach-gia-to-phu-quoc-ferry'
    ),
    (
        'can-tho-to-rach-gia-transport',
        'find transit hotels in our <a href="/destinations/where-to-stay-in-rach-gia/">where to stay in Rach Gia</a> directory, compare ferry staging routes in our <a href="/plan/can-tho-to-ha-tien-transport/">Can Tho to Ha Tien transport</a> guide, explore our island beach recommendations in <a href="/destinations/where-to-stay-in-phu-quoc/">where to stay in Phu Quoc</a>,',
        'catch fast catamarans via our <a href="/plan/rach-gia-to-phu-quoc-ferry/">Rach Gia to Phu Quoc ferry</a> guide, compare seamless island connections in <a href="/plan/can-tho-to-phu-quoc-transport/">Can Tho to Phu Quoc transport</a>, find transit hotels in our <a href="/destinations/where-to-stay-in-rach-gia/">where to stay in Rach Gia</a> directory, compare ferry staging routes in our <a href="/plan/can-tho-to-ha-tien-transport/">Can Tho to Ha Tien transport</a> guide, explore our island beach recommendations in <a href="/destinations/where-to-stay-in-phu-quoc/">where to stay in Phu Quoc</a>,',
        'rach-gia-to-phu-quoc-ferry & can-tho-to-phu-quoc-transport'
    ),
    (
        'where-to-stay-in-ha-tien',
        '<li><a href="/plan/can-tho-to-ha-tien-transport/">Can Tho to Ha Tien Transport</a>: Overland FUTA buses and shared minivans from Can Tho.</li>',
        '<li><a href="/plan/can-tho-to-ha-tien-transport/">Can Tho to Ha Tien Transport</a>: Overland FUTA buses and shared minivans from Can Tho.</li>\n        <li><a href="/plan/rach-gia-to-phu-quoc-ferry/">Rach Gia to Phu Quoc Ferry</a>: Superdong vs Phu Quoc Express fast catamaran guide.</li>',
        'rach-gia-to-phu-quoc-ferry'
    ),

    # --- Group 5: can-tho-to-phu-quoc-transport ---
    (
        'where-to-stay-in-can-tho',
        '<li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Strategic speedboat pier hotels and Mui Nai beach bases.</li>',
        '<li><a href="/plan/can-tho-to-phu-quoc-transport/">Can Tho to Phu Quoc Transport</a>: Connecting buses via Rach Gia fast ferries vs direct flights.</li>\n        <li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Strategic speedboat pier hotels and Mui Nai beach bases.</li>',
        'can-tho-to-phu-quoc-transport'
    ),
    (
        'can-tho-to-ha-tien-transport',
        'explore coastal transit alternatives with our <a href="/plan/can-tho-to-rach-gia-transport/">Can Tho to Rach Gia transport</a> breakdown,',
        'plan full island transfers with our <a href="/plan/can-tho-to-phu-quoc-transport/">Can Tho to Phu Quoc transport</a> guide, explore coastal transit alternatives with our <a href="/plan/can-tho-to-rach-gia-transport/">Can Tho to Rach Gia transport</a> breakdown,',
        'can-tho-to-phu-quoc-transport'
    ),
    (
        'ho-chi-minh-city-to-can-tho-transport',
        '<li><a href="/destinations/mekong-delta-floating-markets-guide/">Mekong Floating Markets Guide</a>: Visiting Cai Rang and Phong Dien.</li>',
        '<li><a href="/destinations/mekong-delta-floating-markets-guide/">Mekong Floating Markets Guide</a>: Visiting Cai Rang and Phong Dien.</li>\n        <li><a href="/plan/can-tho-to-phu-quoc-transport/">Can Tho to Phu Quoc Transport</a>: Combining Delta bus connections with Gulf of Thailand fast ferries.</li>',
        'can-tho-to-phu-quoc-transport'
    ),

    # --- Group 6: quy-nhon-to-hoi-an-transport ---
    (
        'where-to-stay-in-hoi-an',
        '<li><a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a> &mdash; compare resort convenience against historic lantern atmosphere.</li>',
        '<li><a href="/plan/quy-nhon-to-hoi-an-transport/">Quy Nhon to Hoi An Transport</a> &mdash; trains to Tra Kieu, direct limousine sleeper buses, and car transfers.</li>\n<li><a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a> &mdash; compare resort convenience against historic lantern atmosphere.</li>',
        'quy-nhon-to-hoi-an-transport'
    ),
    (
        'hoi-an-to-quy-nhon-transport',
        'continue south via our <a href="/plan/quy-nhon-to-nha-trang-transport/">Quy Nhon to Nha Trang transport</a> guide,',
        'plan your return northbound journey via our <a href="/plan/quy-nhon-to-hoi-an-transport/">Quy Nhon to Hoi An transport</a> guide, continue south via our <a href="/plan/quy-nhon-to-nha-trang-transport/">Quy Nhon to Nha Trang transport</a> guide,',
        'quy-nhon-to-hoi-an-transport'
    ),
    (
        'da-nang-to-quy-nhon-transport',
        '<li><a href="/plan/hoi-an-to-quy-nhon-transport/">Hoi An to Quy Nhon Transport</a>: Direct sleeper buses and trains via Dieu Tri or Da Nang.</li>',
        '<li><a href="/plan/hoi-an-to-quy-nhon-transport/">Hoi An to Quy Nhon Transport</a>: Direct sleeper buses and trains via Dieu Tri or Da Nang.</li>\n        <li><a href="/plan/quy-nhon-to-hoi-an-transport/">Quy Nhon to Hoi An Transport</a>: Reunification Express train stops and coastal sleeper coaches.</li>',
        'quy-nhon-to-hoi-an-transport'
    ),

    # --- Group 7: da-lat-to-pleiku-transport ---
    (
        'where-to-stay-in-da-lat',
        '<li><a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot Transport</a>: Mountain highway limousines via Lak Lake.</li>',
        '<li><a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot Transport</a>: Mountain highway limousines via Lak Lake.</li>\n        <li><a href="/plan/da-lat-to-pleiku-transport/">Da Lat to Pleiku Transport</a>: Direct sleeper buses and VIP limousine cabins across Tay Nguyen.</li>',
        'da-lat-to-pleiku-transport'
    ),
    (
        'da-lat-travel-guide',
        '<li><a href="/plan/da-lat-to-mui-ne-transport/">Da Lat to Mui Ne Transport: Minivan vs Bus via Dai Ninh Pass</a></li>',
        '<li><a href="/plan/da-lat-to-mui-ne-transport/">Da Lat to Mui Ne Transport: Minivan vs Bus via Dai Ninh Pass</a></li>\n<li><a href="/plan/da-lat-to-pleiku-transport/">Da Lat to Pleiku Transport: Direct Highland Sleeper Buses</a></li>',
        'da-lat-to-pleiku-transport'
    ),
    (
        'where-to-stay-in-pleiku',
        '<li><a href="/plan/buon-ma-thuot-to-pleiku-transport/">Buon Ma Thuot to Pleiku Transport</a>: Shared limousine vans and express buses along Highway 14.</li>',
        '<li><a href="/plan/da-lat-to-pleiku-transport/">Da Lat to Pleiku Transport</a>: Overland sleeper buses and limousine cabins via QL27 and QL14.</li>\n        <li><a href="/plan/buon-ma-thuot-to-pleiku-transport/">Buon Ma Thuot to Pleiku Transport</a>: Shared limousine vans and express buses along Highway 14.</li>',
        'da-lat-to-pleiku-transport'
    ),
    (
        'pleiku-to-kon-tum-transport',
        '<li><a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot Transport</a>: Overland highland routes across Lam Dong and Dak Lak.</li>',
        '<li><a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot Transport</a>: Overland highland routes across Lam Dong and Dak Lak.</li>\n        <li><a href="/plan/da-lat-to-pleiku-transport/">Da Lat to Pleiku Transport</a>: Direct sleeper bus lines connecting the Central Highlands spine.</li>',
        'da-lat-to-pleiku-transport'
    ),
]

def main():
    print("=" * 70)
    print("VIETNAMGUIDE STAGE 87: TEST MESH REPLACEMENTS")
    print("=" * 70)

    all_passed = True
    for host_slug, target_str, replacement_str, pillar in CANDIDATES:
        if host_slug not in data or data[host_slug] is None:
            print(f"[FAIL] Host slug not in data: {host_slug}")
            all_passed = False
            continue

        content = data[host_slug]['post_content']
        count = content.count(target_str)
        if count == 1:
            print(f"  [PASS] {host_slug:<35} -> {pillar} (count=1)")
        else:
            print(f"  [FAIL] {host_slug:<35} -> {pillar} (count={count})")
            all_passed = False

    print("=" * 70)
    if all_passed:
        print(f"SUCCESS: All {len(CANDIDATES)} replacements matched exactly once!")
    else:
        print("ERROR: Some replacements failed count == 1 verification!")
        sys.exit(1)

if __name__ == '__main__':
    main()

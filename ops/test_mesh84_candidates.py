# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 84: Test Mesh Candidates
Verifies that all candidate string replacements match exactly once (count == 1) in stage84_mesh_targets.json.
"""
import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

ops_dir = os.path.dirname(os.path.abspath(__file__))
with open(os.path.join(ops_dir, 'stage84_mesh_targets.json'), 'r', encoding='utf-8') as f:
    data = json.load(f)

# Define mesh replacements
# format: (host_slug, target_str, replacement_str, target_pillar_slug)
CANDIDATES = [
    # --- Group A: ha-long-to-ninh-binh-transport ---
    (
        'where-to-stay-in-ninh-binh',
        '<a href="/plan/hanoi-to-ninh-binh-transport/">Hanoi to Ninh Binh Transport</a> and <a href="/plan/cat-ba-to-ninh-binh-transport/">Cat Ba to Ninh Binh Transport</a> before locking a room',
        '<a href="/plan/hanoi-to-ninh-binh-transport/">Hanoi to Ninh Binh Transport</a>, <a href="/plan/ha-long-to-ninh-binh-transport/">Ha Long to Ninh Binh Transport</a>, and <a href="/plan/cat-ba-to-ninh-binh-transport/">Cat Ba to Ninh Binh Transport</a> before locking a room',
        'ha-long-to-ninh-binh-transport'
    ),
    (
        'cat-ba-to-ninh-binh-transport',
        '<li><a href="/plan/ninh-binh-to-ha-long-bay-transfer/">Ninh Binh to Ha Long Bay Transfer</a>: Travel comparisons across northern karst corridors.</li>',
        '<li><a href="/plan/ninh-binh-to-ha-long-bay-transfer/">Ninh Binh to Ha Long Bay Transfer</a>: Travel comparisons across northern karst corridors.</li>\n        <li><a href="/plan/ha-long-to-ninh-binh-transport/">Ha Long to Ninh Binh Transport</a>: Direct pier limousines vs express tourist buses.</li>',
        'ha-long-to-ninh-binh-transport'
    ),
    (
        'hanoi-to-ha-long-bay-transport',
        '<li><a href="/plan/ninh-binh-to-ha-long-bay-transfer/">Ninh Binh to Ha Long Bay Transfer</a> &mdash; connect the limestone mountains to the sea without returning to Hanoi.</li>',
        '<li><a href="/plan/ninh-binh-to-ha-long-bay-transfer/">Ninh Binh to Ha Long Bay Transfer</a> &mdash; connect the limestone mountains to the sea without returning to Hanoi.</li>\n<li><a href="/plan/ha-long-to-ninh-binh-transport/">Ha Long to Ninh Binh Transport</a> &mdash; return limousine vans and buses connecting cruise ports to Tam Coc.</li>',
        'ha-long-to-ninh-binh-transport'
    ),
    (
        'hanoi-to-ninh-binh-transport',
        'and <a href="/plan/hanoi-to-ninh-binh-train-vs-limousine/">Hanoi to Ninh Binh Train vs Limousine</a> and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a>',
        'and <a href="/plan/hanoi-to-ninh-binh-train-vs-limousine/">Hanoi to Ninh Binh Train vs Limousine</a>, <a href="/plan/ha-long-to-ninh-binh-transport/">Ha Long to Ninh Binh Transport</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a>',
        'ha-long-to-ninh-binh-transport'
    ),
    (
        'ha-long-bay-travel-guide',
        'our accommodation review on <a href="/destinations/where-to-stay-in-ha-long-bay/">Where to Stay in Ha Long Bay</a>, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>,',
        'our accommodation review on <a href="/destinations/where-to-stay-in-ha-long-bay/">Where to Stay in Ha Long Bay</a>, our transit route guide on <a href="/plan/ha-long-to-ninh-binh-transport/">Ha Long to Ninh Binh Transport</a>, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>,',
        'ha-long-to-ninh-binh-transport'
    ),

    # --- Group B: dong-hoi-to-hue-transport ---
    (
        'hue-to-dong-hoi-transport',
        'or review the return route with our <a href="/plan/phongnha-to-hue/">Phong Nha to Hue</a> transport guide, or review internal air connections with our <a href="/plan/vietnam-domestic-flights-guide/">Vietnam domestic flights guide</a>.',
        'or review the return route with our <a href="/plan/dong-hoi-to-hue-transport/">Dong Hoi to Hue transport</a> guide, our <a href="/plan/phongnha-to-hue/">Phong Nha to Hue</a> transport guide, or review internal air connections with our <a href="/plan/vietnam-domestic-flights-guide/">Vietnam domestic flights guide</a>.',
        'dong-hoi-to-hue-transport'
    ),
    (
        'dong-hoi-to-phong-nha-transport',
        'plan coastal transit options via <a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi transport</a>, explore capital sleeper routes in <a href="/plan/hanoi-to-phong-nha-transport/">Hanoi to Phong Nha transport</a>,',
        'plan coastal transit options via <a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi transport</a> and <a href="/plan/dong-hoi-to-hue-transport/">Dong Hoi to Hue transport</a>, explore capital sleeper routes in <a href="/plan/hanoi-to-phong-nha-transport/">Hanoi to Phong Nha transport</a>,',
        'dong-hoi-to-hue-transport'
    ),
    (
        'phong-nha-to-hue-transport',
        '<li><a href="/destinations/where-to-stay-in-dong-hoi/">Where to Stay in Dong Hoi: Nhat Le Beach &amp; City Hotels</a></li>',
        '<li><a href="/destinations/where-to-stay-in-dong-hoi/">Where to Stay in Dong Hoi: Nhat Le Beach &amp; City Hotels</a></li>\n<li><a href="/plan/dong-hoi-to-hue-transport/">Dong Hoi to Hue Transport: Trains, Limousine Vans &amp; DMZ Tours</a></li>',
        'dong-hoi-to-hue-transport'
    ),
    (
        'where-to-stay-in-dong-hoi',
        '<li><a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi Transport</a>: Reunification Express trains vs shared limousine vans.</li>',
        '<li><a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi Transport</a>: Reunification Express trains vs shared limousine vans.</li>\n        <li><a href="/plan/dong-hoi-to-hue-transport/">Dong Hoi to Hue Transport</a>: Southbound express trains, direct limousine vans, and DMZ tours.</li>',
        'dong-hoi-to-hue-transport'
    ),
    (
        'where-to-stay-in-hue',
        '<li><a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi Transport</a>: Coastal trains and limousine vans heading north to the cave gateway.</li>',
        '<li><a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi Transport</a>: Coastal trains and limousine vans heading north to the cave gateway.</li>\n        <li><a href="/plan/dong-hoi-to-hue-transport/">Dong Hoi to Hue Transport</a>: Southbound trains, regional buses, and private car options via the DMZ.</li>',
        'dong-hoi-to-hue-transport'
    ),

    # --- Group C: where-to-stay-in-vinh-hy ---
    (
        'where-to-stay-in-phan-rang',
        'Explore airport luxury lodgings in our <a href="/destinations/where-to-stay-in-cam-ranh/">where to stay in Cam Ranh</a> guide, check premier diving bases in <a href="/destinations/where-to-stay-in-nha-trang/">where to stay in Nha Trang</a>, plan dune adventures via <a href="/destinations/where-to-stay-in-mui-ne/">where to stay in Mui Ne</a>, or review mountain transit routes in <a href="/plan/buon-ma-thuot-to-nha-trang-transport/">Buon Ma Thuot to Nha Trang transport</a>.',
        'Explore secluded coastal retreats in our <a href="/destinations/where-to-stay-in-vinh-hy/">where to stay in Vinh Hy</a> guide, airport luxury lodgings in our <a href="/destinations/where-to-stay-in-cam-ranh/">where to stay in Cam Ranh</a> guide, check premier diving bases in <a href="/destinations/where-to-stay-in-nha-trang/">where to stay in Nha Trang</a>, plan dune adventures via <a href="/destinations/where-to-stay-in-mui-ne/">where to stay in Mui Ne</a>, or review mountain transit routes in <a href="/plan/buon-ma-thuot-to-nha-trang-transport/">Buon Ma Thuot to Nha Trang transport</a>.',
        'where-to-stay-in-vinh-hy'
    ),
    (
        'where-to-stay-in-cam-ranh',
        '<li><a href="/destinations/where-to-stay-in-phan-rang/">Where to Stay in Phan Rang</a>: Ninh Chu beach resorts, Cham towers, and Vinh Hy bay villas.</li>',
        '<li><a href="/destinations/where-to-stay-in-phan-rang/">Where to Stay in Phan Rang</a>: Ninh Chu beach resorts, Cham towers, and Vinh Hy bay villas.</li>\n        <li><a href="/destinations/where-to-stay-in-vinh-hy/">Where to Stay in Vinh Hy</a>: Ultra-luxury Amanoi cliff villas, bay homestays, and Nui Chua eco-lodges.</li>',
        'where-to-stay-in-vinh-hy'
    ),
    (
        'where-to-stay-in-nha-trang',
        '<li><a href="/destinations/where-to-stay-in-phan-rang/">Where to Stay in Phan Rang</a>: Ninh Chu beach resorts, Cham culture stays, and Vinh Hy bay villas.</li>',
        '<li><a href="/destinations/where-to-stay-in-phan-rang/">Where to Stay in Phan Rang</a>: Ninh Chu beach resorts, Cham culture stays, and Vinh Hy bay villas.</li>\n        <li><a href="/destinations/where-to-stay-in-vinh-hy/">Where to Stay in Vinh Hy</a>: Luxury Amanoi villas vs fishing village homestays in Nui Chua.</li>',
        'where-to-stay-in-vinh-hy'
    ),
    (
        'where-to-stay-in-mui-ne',
        '<li><a href="/destinations/where-to-stay-in-phan-rang/">Where to Stay in Phan Rang</a>: Quiet beach resorts, grape vineyards, and Cham temples.</li>',
        '<li><a href="/destinations/where-to-stay-in-phan-rang/">Where to Stay in Phan Rang</a>: Quiet beach resorts, grape vineyards, and Cham temples.</li>\n        <li><a href="/destinations/where-to-stay-in-vinh-hy/">Where to Stay in Vinh Hy</a>: Secluded luxury resorts and bay eco-homestays in Nui Chua National Park.</li>',
        'where-to-stay-in-vinh-hy'
    ),

    # --- Group D: quy-nhon-to-nha-trang-transport ---
    (
        'where-to-stay-in-quy-nhon',
        '<li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Scenic rail journeys and limousine vans from Khanh Hoa.</li>',
        '<li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Scenic rail journeys and limousine vans from Khanh Hoa.</li>\n        <li><a href="/plan/quy-nhon-to-nha-trang-transport/">Quy Nhon to Nha Trang Transport</a>: Coastal Reunification Express trains vs limousine vans heading south.</li>',
        'quy-nhon-to-nha-trang-transport'
    ),
    (
        'nha-trang-to-quy-nhon-transport',
        '<li><a href="/destinations/where-to-stay-in-quy-nhon/">Where to Stay in Quy Nhon</a>: City beach hotels vs Bai Xep fishing village homestays.</li>',
        '<li><a href="/destinations/where-to-stay-in-quy-nhon/">Where to Stay in Quy Nhon</a>: City beach hotels vs Bai Xep fishing village homestays.</li>\n        <li><a href="/plan/quy-nhon-to-nha-trang-transport/">Quy Nhon to Nha Trang Transport</a>: Southbound trains, limousine vans, and Highway 1D options.</li>',
        'quy-nhon-to-nha-trang-transport'
    ),
    (
        'da-nang-to-quy-nhon-transport',
        '<li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Coastal trains and Highway 1D minivans from the south.</li>',
        '<li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Coastal trains and Highway 1D minivans from the south.</li>\n        <li><a href="/plan/quy-nhon-to-nha-trang-transport/">Quy Nhon to Nha Trang Transport</a>: Coastal express trains and regional limousine vans heading south.</li>',
        'quy-nhon-to-nha-trang-transport'
    ),
    (
        'hoi-an-to-quy-nhon-transport',
        'plan your next coastal transfer using our <a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon transport</a> guide, or explore northern heritage stays in <a href="/destinations/where-to-stay-in-hoi-an/">where to stay in Hoi An</a>.',
        'plan your next coastal transfer using our <a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon transport</a> guide, continue south via our <a href="/plan/quy-nhon-to-nha-trang-transport/">Quy Nhon to Nha Trang transport</a> guide, or explore northern heritage stays in <a href="/destinations/where-to-stay-in-hoi-an/">where to stay in Hoi An</a>.',
        'quy-nhon-to-nha-trang-transport'
    ),
    (
        'where-to-stay-in-nha-trang',
        '<li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Coastal trains and shared limousine vans via Highway 1D.</li>',
        '<li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Coastal trains and shared limousine vans via Highway 1D.</li>\n        <li><a href="/plan/quy-nhon-to-nha-trang-transport/">Quy Nhon to Nha Trang Transport</a>: Coastal trains and highway limousine vans heading south.</li>',
        'quy-nhon-to-nha-trang-transport'
    ),

    # --- Group E: Strengthening where-to-stay-in-dong-van, cao-bang, da-lat-to-mui-ne ---
    (
        'sapa-to-ha-giang-transport',
        'secure your Dong Van border area permit before setting off toward Quan Ba Pass. Check our guide on <a href="/destinations/where-to-stay-in-ha-giang/">where to stay in Ha Giang</a>',
        'secure your Dong Van border area permit and select your mountain base using our <a href="/destinations/where-to-stay-in-dong-van/">where to stay in Dong Van</a> guide before setting off toward Quan Ba Pass. Check our guide on <a href="/destinations/where-to-stay-in-ha-giang/">where to stay in Ha Giang</a>',
        'where-to-stay-in-dong-van'
    ),
    (
        'ha-giang-to-cao-bang-transport',
        '<li><a href="/destinations/where-to-stay-in-cao-bang/">Where to Stay in Cao Bang</a>: Riverside city hotels, Ban Gioc eco-lodges, and village homestays.</li>',
        '<li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>: Old Quarter heritage houses, stone homestays, and canyon lodges.</li>\n        <li><a href="/destinations/where-to-stay-in-cao-bang/">Where to Stay in Cao Bang</a>: Riverside city hotels, Ban Gioc eco-lodges, and village homestays.</li>',
        'where-to-stay-in-dong-van'
    ),
    (
        'cao-bang-travel-guide',
        '<li><a href="/destinations/where-to-stay-in-cao-bang/">Where to Stay in Cao Bang: City Hotels vs Ban Gioc Eco-Lodges</a></li>',
        '<li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van: Old Quarter Heritage Homestays &amp; Canyon Lodges</a></li>\n<li><a href="/destinations/where-to-stay-in-cao-bang/">Where to Stay in Cao Bang: City Hotels vs Ban Gioc Eco-Lodges</a></li>',
        'where-to-stay-in-dong-van'
    ),
    (
        'where-to-stay-in-ba-be',
        '<li><a href="/destinations/where-to-stay-in-cao-bang/">Where to Stay in Cao Bang</a>: Stone homestays and city hotel bases.</li>',
        '<li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>: Karst plateau boutique stays and canyon view lodges.</li>\n        <li><a href="/destinations/where-to-stay-in-cao-bang/">Where to Stay in Cao Bang</a>: Stone homestays and city hotel bases.</li>',
        'where-to-stay-in-dong-van'
    ),
    (
        'da-lat-to-buon-ma-thuot-transport',
        'or review highland hotel options in <a href="/destinations/where-to-stay-in-da-lat/">where to stay in Da Lat</a>, or compare central overland connections via our <a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi transport</a> guide.',
        'or review highland hotel options in <a href="/destinations/where-to-stay-in-da-lat/">where to stay in Da Lat</a>, descend to the coast with our <a href="/plan/da-lat-to-mui-ne-transport/">Da Lat to Mui Ne transport</a> guide, or compare central overland connections via our <a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi transport</a> guide.',
        'da-lat-to-mui-ne-transport'
    )
]

def main():
    print(f"=== Testing {len(CANDIDATES)} Mesh Replacements ===")
    all_ok = True
    for idx, (host_slug, target_str, replacement_str, target_pillar) in enumerate(CANDIDATES, 1):
        if host_slug not in data:
            print(f"[{idx:02d}] FAIL: Host {host_slug} not found in fetched data!")
            all_ok = False
            continue
        content = data[host_slug]['content']
        cnt = content.count(target_str)
        if cnt == 1:
            print(f"[{idx:02d}] PASS: {host_slug} -> {target_pillar} (exact count=1)")
        elif cnt == 0:
            print(f"[{idx:02d}] FAIL: {host_slug} -> {target_pillar} (target string NOT FOUND)")
            all_ok = False
        else:
            print(f"[{idx:02d}] FAIL: {host_slug} -> {target_pillar} (ambiguous match, count={cnt})")
            all_ok = False

    if all_ok:
        print("\nALL REPLACEMENTS VALIDATED! Count=1 for all candidates.")
    else:
        print("\nSOME REPLACEMENTS FAILED VALIDATION.")
        sys.exit(1)

if __name__ == '__main__':
    main()

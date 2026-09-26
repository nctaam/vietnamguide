# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 85: Test Mesh Candidates
Verifies that all candidate string replacements match exactly once (count == 1) in stage85_mesh_targets.json.
"""
import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

ops_dir = os.path.dirname(os.path.abspath(__file__))
with open(os.path.join(ops_dir, 'stage85_mesh_targets.json'), 'r', encoding='utf-8') as f:
    data = json.load(f)

# Define mesh replacements
# format: (host_slug, target_str, replacement_str, target_pillar_slug)
CANDIDATES = [
    # --- Group 1: where-to-stay-in-du-gia ---
    (
        'where-to-stay-in-ha-giang',
        '<li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>: Old Quarter heritage homestays, cliffside lodges, and fortress bases.</li>',
        '<li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>: Old Quarter heritage homestays, cliffside lodges, and fortress bases.</li>\n        <li><a href="/destinations/where-to-stay-in-du-gia/">Where to Stay in Du Gia</a>: Traditional Tay wooden stilt houses and riverside homestays near the waterfall.</li>',
        'where-to-stay-in-du-gia'
    ),
    (
        'where-to-stay-in-dong-van',
        'and compare provincial lodging in our <a href="/destinations/where-to-stay-in-cao-bang/">where to stay in Cao Bang</a> review.</p>',
        'explore quiet valley lodgings in our <a href="/destinations/where-to-stay-in-du-gia/">where to stay in Du Gia</a> guide, and compare provincial lodging in our <a href="/destinations/where-to-stay-in-cao-bang/">where to stay in Cao Bang</a> review.</p>',
        'where-to-stay-in-du-gia'
    ),
    (
        'where-to-stay-in-meo-vac',
        '<li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>: Historic Old Quarter homestays and mountain view hotels.</li>',
        '<li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>: Historic Old Quarter homestays and mountain view hotels.</li>\n        <li><a href="/destinations/where-to-stay-in-du-gia/">Where to Stay in Du Gia</a>: Waterfall homestays, traditional Tay stilt houses, and quiet valley lodges.</li>',
        'where-to-stay-in-du-gia'
    ),
    (
        'ha-giang-to-cao-bang-transport',
        '<li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>: Old Quarter heritage houses, stone homestays, and canyon lodges.</li>',
        '<li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>: Old Quarter heritage houses, stone homestays, and canyon lodges.</li>\n        <li><a href="/destinations/where-to-stay-in-du-gia/">Where to Stay in Du Gia</a>: Tay stilt house homestays and mountain stream lodges along the southern loop.</li>',
        'where-to-stay-in-du-gia'
    ),
    (
        'ha-giang-loop-planning-guide',
        'stops such as Du Gia, local villages, markets, viewpoints, or river/canyon side trips.',
        'stops such as Du Gia (see our <a href="/destinations/where-to-stay-in-du-gia/">where to stay in Du Gia</a> guide), local villages, markets, viewpoints, or river/canyon side trips.',
        'where-to-stay-in-du-gia'
    ),

    # --- Group 2: where-to-stay-in-mang-den ---
    (
        'where-to-stay-in-kon-tum',
        'Travelers who have an extra two days frequently continue 55 kilometers northeast up National Route QL24 to Mang Den. Sitting at an altitude of 1,200 meters',
        'Travelers who have an extra two days frequently continue 55 kilometers northeast up National Route QL24 to Mang Den (explore our dedicated <a href="/destinations/where-to-stay-in-mang-den/">where to stay in Mang Den</a> guide). Sitting at an altitude of 1,200 meters',
        'where-to-stay-in-mang-den'
    ),
    (
        'where-to-stay-in-kon-tum',
        '<li><a href="/plan/kon-tum-to-da-nang-transport/">Kon Tum to Da Nang Transport</a>: Limousine vans and sleeper coaches via Ho Chi Minh Highway.</li>',
        '<li><a href="/plan/kon-tum-to-da-nang-transport/">Kon Tum to Da Nang Transport</a>: Limousine vans and sleeper coaches via Ho Chi Minh Highway.</li>\n        <li><a href="/destinations/where-to-stay-in-mang-den/">Where to Stay in Mang Den</a>: Pine forest wooden chalets, Pa Sy waterfall eco-lodges, and boutique hotels.</li>',
        'where-to-stay-in-mang-den'
    ),
    (
        'pleiku-to-kon-tum-transport',
        '<li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum</a>: Dak Bla riverside stays, Bahnar lodges, and central hotels.</li>',
        '<li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum</a>: Dak Bla riverside stays, Bahnar lodges, and central hotels.</li>\n        <li><a href="/destinations/where-to-stay-in-mang-den/">Where to Stay in Mang Den</a>: Pine forest chalets, Pa Sy waterfall lodges, and cool highland retreats.</li>',
        'where-to-stay-in-mang-den'
    ),
    (
        'kon-tum-to-da-nang-transport',
        '<a href="/destinations/where-to-stay-in-kon-tum/">where to stay in Kon Tum</a>, study provincial transit in our <a href="/plan/pleiku-to-kon-tum-transport/">Pleiku to Kon Tum transport</a> guide,',
        '<a href="/destinations/where-to-stay-in-kon-tum/">where to stay in Kon Tum</a>, check pine forest chalets in <a href="/destinations/where-to-stay-in-mang-den/">where to stay in Mang Den</a>, study provincial transit in our <a href="/plan/pleiku-to-kon-tum-transport/">Pleiku to Kon Tum transport</a> guide,',
        'where-to-stay-in-mang-den'
    ),
    (
        'where-to-stay-in-pleiku',
        '<li><a href="/plan/kon-tum-to-da-nang-transport/">Kon Tum to Da Nang Transport</a>: Highland route over Lo Xo Pass to Da Nang beaches.</li>',
        '<li><a href="/plan/kon-tum-to-da-nang-transport/">Kon Tum to Da Nang Transport</a>: Highland route over Lo Xo Pass to Da Nang beaches.</li>\n        <li><a href="/destinations/where-to-stay-in-mang-den/">Where to Stay in Mang Den</a>: Pine forest wooden chalets and Pa Sy waterfall eco-lodges.</li>',
        'where-to-stay-in-mang-den'
    ),

    # --- Group 3: nha-trang-to-da-lat-transport ---
    (
        'da-lat-to-nha-trang-transport',
        '<li><a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide: Beaches, Islands &amp; Diving</a></li>',
        '<li><a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide: Beaches, Islands &amp; Diving</a></li>\n<li><a href="/plan/nha-trang-to-da-lat-transport/">Nha Trang to Da Lat Transport: Shared Limousine Vans vs Tourist Coaches</a></li>',
        'nha-trang-to-da-lat-transport'
    ),
    (
        'where-to-stay-in-nha-trang',
        '<li><a href="/destinations/where-to-stay-in-cam-ranh/">Where to Stay in Cam Ranh</a>: Bai Dai beach luxury resorts vs airport transit hotels.</li>',
        '<li><a href="/destinations/where-to-stay-in-cam-ranh/">Where to Stay in Cam Ranh</a>: Bai Dai beach luxury resorts vs airport transit hotels.</li>\n        <li><a href="/plan/nha-trang-to-da-lat-transport/">Nha Trang to Da Lat Transport</a>: Shared limousine vans and tourist buses climbing Khanh Le Pass.</li>',
        'nha-trang-to-da-lat-transport'
    ),
    (
        'where-to-stay-in-da-lat',
        '<li><a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide</a></li>',
        '<li><a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide</a></li>\n        <li><a href="/plan/nha-trang-to-da-lat-transport/">Nha Trang to Da Lat Transport</a>: Coastal to highland limousine vans ascending from Khanh Hoa.</li>',
        'nha-trang-to-da-lat-transport'
    ),
    (
        'da-lat-to-mui-ne-transport',
        'review highland connections in our <a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot transport</a> breakdown.</p>',
        'review highland connections in our <a href="/plan/da-lat-to-buon-ma-thuot-transport/">Da Lat to Buon Ma Thuot transport</a> breakdown, or plan coastal-mountain transfers via <a href="/plan/nha-trang-to-da-lat-transport/">Nha Trang to Da Lat transport</a>.</p>',
        'nha-trang-to-da-lat-transport'
    ),
    (
        'da-lat-travel-guide',
        '<li><a href="/destinations/where-to-stay-in-da-lat/">Where to Stay in Da Lat: Best Areas, French Villas &amp; Lakes</a></li>',
        '<li><a href="/destinations/where-to-stay-in-da-lat/">Where to Stay in Da Lat: Best Areas, French Villas &amp; Lakes</a></li>\n<li><a href="/plan/nha-trang-to-da-lat-transport/">Nha Trang to Da Lat Transport: Shared Limousine Vans vs Tourist Coaches</a></li>',
        'nha-trang-to-da-lat-transport'
    ),

    # --- Group 4: ninh-binh-to-phong-nha-transport ---
    (
        'where-to-stay-in-phong-nha',
        '<li><a href="/destinations/where-to-stay-in-dong-hoi/">Where to Stay in Dong Hoi</a>: Beachfront resorts and city center transit hotels.</li>',
        '<li><a href="/destinations/where-to-stay-in-dong-hoi/">Where to Stay in Dong Hoi</a>: Beachfront resorts and city center transit hotels.</li>\n        <li><a href="/plan/ninh-binh-to-phong-nha-transport/">Ninh Binh to Phong Nha Transport</a>: Direct overnight sleeper buses vs trains to Dong Hoi with local taxi transfers.</li>',
        'ninh-binh-to-phong-nha-transport'
    ),
    (
        'dong-hoi-to-phong-nha-transport',
        'explore capital sleeper routes in <a href="/plan/hanoi-to-phong-nha-transport/">Hanoi to Phong Nha transport</a>,',
        'explore capital sleeper routes in <a href="/plan/hanoi-to-phong-nha-transport/">Hanoi to Phong Nha transport</a>, connect from Tam Coc via <a href="/plan/ninh-binh-to-phong-nha-transport/">Ninh Binh to Phong Nha transport</a>,',
        'ninh-binh-to-phong-nha-transport'
    ),
    (
        'hanoi-to-phong-nha-transport',
        '<li><a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport</a>: Reunification Express train schedules and direct sleeper buses.</li>',
        '<li><a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport</a>: Reunification Express train schedules and direct sleeper buses.</li>\n    <li><a href="/plan/ninh-binh-to-phong-nha-transport/">Ninh Binh to Phong Nha Transport</a>: Karst-to-cave sleeper buses and train-taxi connections.</li>',
        'ninh-binh-to-phong-nha-transport'
    ),
    (
        'where-to-stay-in-dong-hoi',
        '<li><a href="/plan/vietnam-train-travel-guide/">Vietnam Train Travel Guide</a>: Reunification Express booking, sleeper berths, and luggage rules.</li>',
        '<li><a href="/plan/vietnam-train-travel-guide/">Vietnam Train Travel Guide</a>: Reunification Express booking, sleeper berths, and luggage rules.</li>\n        <li><a href="/plan/ninh-binh-to-phong-nha-transport/">Ninh Binh to Phong Nha Transport</a>: Overnight sleeper coaches and trains linking Tam Coc with the caves.</li>',
        'ninh-binh-to-phong-nha-transport'
    ),

    # --- Group 5: cao-bang-to-ba-be-transport ---
    (
        'where-to-stay-in-ba-be',
        '<li><a href="/plan/hanoi-to-ba-be-transport/">Hanoi to Ba Be Lake Transport</a>: Direct homestay shuttle vans, private SUVs, and bus routes.</li>',
        '<li><a href="/plan/hanoi-to-ba-be-transport/">Hanoi to Ba Be Lake Transport</a>: Direct homestay shuttle vans, private SUVs, and bus routes.</li>\n        <li><a href="/plan/cao-bang-to-ba-be-transport/">Cao Bang to Ba Be Transport</a>: Local passenger minivans vs private cars via Highway 34 and 279.</li>',
        'cao-bang-to-ba-be-transport'
    ),
    (
        'where-to-stay-in-cao-bang',
        'Check overland transit connections with our <a href="/plan/ha-giang-to-cao-bang-transport/">Ha Giang to Cao Bang transport</a> guide,',
        'Check overland transit connections with our <a href="/plan/ha-giang-to-cao-bang-transport/">Ha Giang to Cao Bang transport</a> guide, connect to the national park via our <a href="/plan/cao-bang-to-ba-be-transport/">Cao Bang to Ba Be transport</a> guide,',
        'cao-bang-to-ba-be-transport'
    ),
    (
        'hanoi-to-ba-be-transport',
        '<li><a href="/plan/hanoi-to-cao-bang-transport/">Hanoi to Cao Bang Transport</a>: Express limousines, sleeper buses, and travel times.</li>',
        '<li><a href="/plan/hanoi-to-cao-bang-transport/">Hanoi to Cao Bang Transport</a>: Express limousines, sleeper buses, and travel times.</li>\n        <li><a href="/plan/cao-bang-to-ba-be-transport/">Cao Bang to Ba Be Transport</a>: Mountain minivans connecting Ban Gioc Waterfall to Ba Be Lake.</li>',
        'cao-bang-to-ba-be-transport'
    ),
    (
        'hanoi-to-cao-bang-transport',
        '<li><a href="/itineraries/northeast-vietnam-itinerary/">Northeast Vietnam Itinerary</a>: Loop route from Hanoi through Cao Bang, Ban Gioc, and Ba Be Lake.</li>',
        '<li><a href="/itineraries/northeast-vietnam-itinerary/">Northeast Vietnam Itinerary</a>: Loop route from Hanoi through Cao Bang, Ban Gioc, and Ba Be Lake.</li>\n        <li><a href="/plan/cao-bang-to-ba-be-transport/">Cao Bang to Ba Be Transport</a>: Regional minivans and private transfers to Ba Be National Park.</li>',
        'cao-bang-to-ba-be-transport'
    ),

    # --- Group 6: ho-chi-minh-city-to-chau-doc-transport ---
    (
        'where-to-stay-in-chau-doc',
        '<li><a href="/plan/can-tho-to-chau-doc-transport/">Can Tho to Chau Doc Transport</a>: Futa buses and shared limousine shuttles from the delta hub.</li>',
        '<li><a href="/plan/can-tho-to-chau-doc-transport/">Can Tho to Chau Doc Transport</a>: Futa buses and shared limousine shuttles from the delta hub.</li>\n        <li><a href="/plan/ho-chi-minh-city-to-chau-doc-transport/">Ho Chi Minh City to Chau Doc Transport</a>: Futa sleeper coaches vs VIP limousine minivans from Saigon.</li>',
        'ho-chi-minh-city-to-chau-doc-transport'
    ),
    (
        'can-tho-to-chau-doc-transport',
        '<li><a href="/plan/vietnam-to-cambodia-boat-guide/">Vietnam to Cambodia Boat Guide</a>: Fast Mekong express ferries from Chau Doc to Phnom Penh.</li>',
        '<li><a href="/plan/vietnam-to-cambodia-boat-guide/">Vietnam to Cambodia Boat Guide</a>: Fast Mekong express ferries from Chau Doc to Phnom Penh.</li>\n        <li><a href="/plan/ho-chi-minh-city-to-chau-doc-transport/">Ho Chi Minh City to Chau Doc Transport</a>: Direct sleeper coaches and VIP limousine vans from Saigon.</li>',
        'ho-chi-minh-city-to-chau-doc-transport'
    ),
    (
        'vietnam-to-cambodia-boat-guide',
        '<li><a href="/itineraries/southern-vietnam-itinerary/">Southern Vietnam Itinerary</a>: Complete Mekong Delta circuit from Saigon to the Cambodian border.</li>',
        '<li><a href="/itineraries/southern-vietnam-itinerary/">Southern Vietnam Itinerary</a>: Complete Mekong Delta circuit from Saigon to the Cambodian border.</li>\n        <li><a href="/plan/ho-chi-minh-city-to-chau-doc-transport/">Ho Chi Minh City to Chau Doc Transport</a>: Direct sleeper buses and VIP vans connecting Saigon to the boat pier.</li>',
        'ho-chi-minh-city-to-chau-doc-transport'
    ),
    (
        'where-to-stay-in-can-tho',
        '<li><a href="/plan/can-tho-to-chau-doc-transport/">Can Tho to Chau Doc Transport</a>: Futa express buses and VIP limousine vans via Highway 91.</li>',
        '<li><a href="/plan/can-tho-to-chau-doc-transport/">Can Tho to Chau Doc Transport</a>: Futa express buses and VIP limousine vans via Highway 91.</li>\n        <li><a href="/plan/ho-chi-minh-city-to-chau-doc-transport/">Ho Chi Minh City to Chau Doc Transport</a>: Overnight sleeper buses and VIP limousines from Saigon.</li>',
        'ho-chi-minh-city-to-chau-doc-transport'
    ),

    # --- Group 7: where-to-stay-in-an-giang ---
    (
        'where-to-stay-in-chau-doc',
        '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Riverside resorts, floating market farmstays, and boutique hotels.</li>',
        '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Riverside resorts, floating market farmstays, and boutique hotels.</li>\n        <li><a href="/destinations/where-to-stay-in-an-giang/">Where to Stay in An Giang</a>: Chau Doc riverfront hotels vs Sam Mountain panoramic retreats and Long Xuyen bases.</li>',
        'where-to-stay-in-an-giang'
    ),
    (
        'can-tho-to-chau-doc-transport',
        '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf hotels vs peaceful riverside lodges.</li>',
        '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf hotels vs peaceful riverside lodges.</li>\n        <li><a href="/destinations/where-to-stay-in-an-giang/">Where to Stay in An Giang</a>: Provincial lodging across Chau Doc, Sam Mountain, and Long Xuyen.</li>',
        'where-to-stay-in-an-giang'
    ),
    (
        'vietnam-to-cambodia-boat-guide',
        '<li><a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a>: Riverfront hotels near the boat pier and Sam Mountain lodges.</li>',
        '<li><a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a>: Riverfront hotels near the boat pier and Sam Mountain lodges.</li>\n        <li><a href="/destinations/where-to-stay-in-an-giang/">Where to Stay in An Giang</a>: Delta riverfront hotels, Sam Mountain retreats, and provincial staging.</li>',
        'where-to-stay-in-an-giang'
    ),
    (
        'where-to-stay-in-can-tho',
        '<li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Strategic speedboat pier hotels and Mui Nai beach bases.</li>',
        '<li><a href="/destinations/where-to-stay-in-ha-tien/">Where to Stay in Ha Tien</a>: Strategic speedboat pier hotels and Mui Nai beach bases.</li>\n        <li><a href="/destinations/where-to-stay-in-an-giang/">Where to Stay in An Giang</a>: Border riverfront hotels, Sam Mountain lodges, and floating market bases.</li>',
        'where-to-stay-in-an-giang'
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

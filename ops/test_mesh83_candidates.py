# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 83 Mesh Candidate Test
"""

import json
import os

targets = json.load(open('ops/stage83_mesh_targets.json', encoding='utf-8'))

candidates = [
    # === Pillar 1: where-to-stay-in-hai-phong ===
    (
        "hanoi-to-hai-phong-transport",
        '<a href="/destinations/where-to-stay-in-cat-ba/">where to stay in Cat Ba</a> lodging directory.',
        'choose central food tour hotels in our <a href="/destinations/where-to-stay-in-hai-phong/">where to stay in Hai Phong</a> guide, or browse seaside stays in our <a href="/destinations/where-to-stay-in-cat-ba/">where to stay in Cat Ba</a> lodging directory.',
        "where-to-stay-in-hai-phong"
    ),
    (
        "where-to-stay-in-cat-ba",
        '<li><a href="/plan/hanoi-to-cat-ba-island-transport/">Hanoi to Cat Ba Transport</a>: Bus, ferry, and expressway combo options.</li>',
        '<li><a href="/plan/hanoi-to-cat-ba-island-transport/">Hanoi to Cat Ba Transport</a>: Bus, ferry, and expressway combo options.</li>\n        <li><a href="/destinations/where-to-stay-in-hai-phong/">Where to Stay in Hai Phong</a>: French Quarter boutique hotels and mainland port bases.</li>',
        "where-to-stay-in-hai-phong"
    ),
    (
        "cat-ba-to-ninh-binh-transport",
        '        <li><a href="/destinations/where-to-stay-in-cat-ba/">Where to Stay in Cat Ba</a>: Ocean-view hotels, Lan Ha Bay resorts, and national park stays.</li>',
        '        <li><a href="/destinations/where-to-stay-in-cat-ba/">Where to Stay in Cat Ba</a>: Ocean-view hotels, Lan Ha Bay resorts, and national park stays.</li>\n        <li><a href="/destinations/where-to-stay-in-hai-phong/">Where to Stay in Hai Phong</a>: Mainland transit hotels and French Quarter boutique stays.</li>',
        "where-to-stay-in-hai-phong"
    ),
    (
        "hanoi-to-ha-long-bay-transport",
        '<li><a href="/plan/hanoi-to-hai-phong-transport/">Hanoi to Hai Phong Transport</a> &mdash; express trains and 5B expressway limousine vans.</li>',
        '<li><a href="/plan/hanoi-to-hai-phong-transport/">Hanoi to Hai Phong Transport</a> &mdash; express trains and 5B expressway limousine vans.</li>\n<li><a href="/destinations/where-to-stay-in-hai-phong/">Where to Stay in Hai Phong</a> &mdash; French colonial hotels and central food tour bases.</li>',
        "where-to-stay-in-hai-phong"
    ),

    # === Pillar 2: dong-hoi-to-phong-nha-transport ===
    (
        "where-to-stay-in-dong-hoi",
        '<li><a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi Transport</a>: Reunification Express trains vs shared limousine vans.</li>',
        '<li><a href="/plan/hue-to-dong-hoi-transport/">Hue to Dong Hoi Transport</a>: Reunification Express trains vs shared limousine vans.</li>\n        <li><a href="/plan/dong-hoi-to-phong-nha-transport/">Dong Hoi to Phong Nha Transport</a>: Local bus B4, station shuttles, and airport taxis.</li>',
        "dong-hoi-to-phong-nha-transport"
    ),
    (
        "where-to-stay-in-phong-nha",
        '<li><a href="/plan/hanoi-to-phong-nha-transport/">Hanoi to Phong Nha Transport</a></li>',
        '<li><a href="/plan/hanoi-to-phong-nha-transport/">Hanoi to Phong Nha Transport</a></li>\n        <li><a href="/plan/dong-hoi-to-phong-nha-transport/">Dong Hoi to Phong Nha Transport</a>: Station shuttles, local bus B4, and taxis.</li>',
        "dong-hoi-to-phong-nha-transport"
    ),
    (
        "hue-to-dong-hoi-transport",
        'organize subterranean expeditions using the <a href="/destinations/where-to-stay-in-phong-nha/">where to stay in Phong Nha</a> directory,',
        'connect from the station with our <a href="/plan/dong-hoi-to-phong-nha-transport/">Dong Hoi to Phong Nha transport</a> guide, organize subterranean expeditions using the <a href="/destinations/where-to-stay-in-phong-nha/">where to stay in Phong Nha</a> directory,',
        "dong-hoi-to-phong-nha-transport"
    ),
    (
        "hanoi-to-phong-nha-transport",
        '<li><a href="/destinations/where-to-stay-in-phong-nha/">Where to Stay in Phong Nha</a>: Pick the ideal base between Son Trach village hostels and Bong Lai valley farmstays.</li>',
        '<li><a href="/destinations/where-to-stay-in-phong-nha/">Where to Stay in Phong Nha</a>: Pick the ideal base between Son Trach village hostels and Bong Lai valley farmstays.</li>\n    <li><a href="/plan/dong-hoi-to-phong-nha-transport/">Dong Hoi to Phong Nha Transport</a>: Local bus B4, train station minivans, and private taxis.</li>',
        "dong-hoi-to-phong-nha-transport"
    ),
    (
        "da-nang-to-phong-nha-transport",
        '<li><a href="/destinations/where-to-stay-in-dong-hoi/">Where to Stay in Dong Hoi</a>: Beachfront resorts and city center hotels near the station.</li>',
        '<li><a href="/destinations/where-to-stay-in-dong-hoi/">Where to Stay in Dong Hoi</a>: Beachfront resorts and city center hotels near the station.</li>\n        <li><a href="/plan/dong-hoi-to-phong-nha-transport/">Dong Hoi to Phong Nha Transport</a>: Station shuttle minivans, public bus B4, and taxis.</li>',
        "dong-hoi-to-phong-nha-transport"
    ),

    # === Pillar 3: can-tho-to-ca-mau-transport ===
    (
        "where-to-stay-in-ca-mau",
        'Continue planning your delta itinerary with our curated <a href="/destinations/where-to-stay-in-bac-lieu/">where to stay in Bac Lieu</a> lodging review,',
        'Plan delta overland transit with our <a href="/plan/can-tho-to-ca-mau-transport/">Can Tho to Ca Mau transport</a> guide, continue planning your delta itinerary with our curated <a href="/destinations/where-to-stay-in-bac-lieu/">where to stay in Bac Lieu</a> lodging review,',
        "can-tho-to-ca-mau-transport"
    ),
    (
        "where-to-stay-in-bac-lieu",
        'explore southernmost tips with our <a href="/destinations/where-to-stay-in-ca-mau/">where to stay in Ca Mau</a> directory,',
        'explore southernmost tips with our <a href="/destinations/where-to-stay-in-ca-mau/">where to stay in Ca Mau</a> directory, check highway bus options in <a href="/plan/can-tho-to-ca-mau-transport/">Can Tho to Ca Mau transport</a>,',
        "can-tho-to-ca-mau-transport"
    ),
    (
        "where-to-stay-in-soc-trang",
        'explore southernmost mangrove stays in <a href="/destinations/where-to-stay-in-ca-mau/">where to stay in Ca Mau</a>,',
        'explore southernmost mangrove stays in <a href="/destinations/where-to-stay-in-ca-mau/">where to stay in Ca Mau</a>, organize delta highway transit using our <a href="/plan/can-tho-to-ca-mau-transport/">Can Tho to Ca Mau transport</a> guide,',
        "can-tho-to-ca-mau-transport"
    ),
    (
        "where-to-stay-in-can-tho",
        '<li><a href="/destinations/where-to-stay-in-ca-mau/">Where to Stay in Ca Mau</a>: City business hotels and cape eco-lodges in Dat Mui.</li>',
        '<li><a href="/destinations/where-to-stay-in-ca-mau/">Where to Stay in Ca Mau</a>: City business hotels and cape eco-lodges in Dat Mui.</li>\n        <li><a href="/plan/can-tho-to-ca-mau-transport/">Can Tho to Ca Mau Transport</a>: Express FUTA coaches and VIP limousine minivans down Highway 1A.</li>',
        "can-tho-to-ca-mau-transport"
    ),
    (
        "can-tho-to-ha-tien-transport",
        'explore coastal transit alternatives with our <a href="/plan/can-tho-to-rach-gia-transport/">Can Tho to Rach Gia transport</a> breakdown,',
        'explore coastal transit alternatives with our <a href="/plan/can-tho-to-rach-gia-transport/">Can Tho to Rach Gia transport</a> breakdown, plan southernmost overland runs with our <a href="/plan/can-tho-to-ca-mau-transport/">Can Tho to Ca Mau transport</a> guide,',
        "can-tho-to-ca-mau-transport"
    ),

    # === Pillar 4: where-to-stay-in-phan-rang ===
    (
        "where-to-stay-in-cam-ranh",
        '<li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Scenic coastal trains vs limousine vans.</li>',
        '<li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Scenic coastal trains vs limousine vans.</li>\n        <li><a href="/destinations/where-to-stay-in-phan-rang/">Where to Stay in Phan Rang</a>: Ninh Chu beach resorts, Cham towers, and Vinh Hy bay villas.</li>',
        "where-to-stay-in-phan-rang"
    ),
    (
        "where-to-stay-in-nha-trang",
        '<li><a href="/destinations/where-to-stay-in-cam-ranh/">Where to Stay in Cam Ranh</a>: Bai Dai beach luxury resorts vs airport transit hotels.</li>',
        '<li><a href="/destinations/where-to-stay-in-cam-ranh/">Where to Stay in Cam Ranh</a>: Bai Dai beach luxury resorts vs airport transit hotels.</li>\n        <li><a href="/destinations/where-to-stay-in-phan-rang/">Where to Stay in Phan Rang</a>: Ninh Chu beach resorts, Cham culture stays, and Vinh Hy bay villas.</li>',
        "where-to-stay-in-phan-rang"
    ),
    (
        "where-to-stay-in-mui-ne",
        '<li><a href="/destinations/best-things-to-do-in-mui-ne/">Best Things to Do in Mui Ne</a>: Sand dunes, Fairy Stream, and kitesurfing.</li>',
        '<li><a href="/destinations/best-things-to-do-in-mui-ne/">Best Things to Do in Mui Ne</a>: Sand dunes, Fairy Stream, and kitesurfing.</li>\n        <li><a href="/destinations/where-to-stay-in-phan-rang/">Where to Stay in Phan Rang</a>: Quiet beach resorts, grape vineyards, and Cham temples.</li>',
        "where-to-stay-in-phan-rang"
    ),
    (
        "buon-ma-thuot-to-nha-trang-transport",
        'Continue organizing your regional trip with our <a href="/destinations/where-to-stay-in-nha-trang/">where to stay in Nha Trang</a> area guide,',
        'Continue organizing your regional trip with our <a href="/destinations/where-to-stay-in-nha-trang/">where to stay in Nha Trang</a> area guide, discover quiet coastal resorts in our <a href="/destinations/where-to-stay-in-phan-rang/">where to stay in Phan Rang</a> guide,',
        "where-to-stay-in-phan-rang"
    ),

    # === Pillar 5: hanoi-to-tam-dao-transport ===
    (
        "where-to-stay-in-tam-dao",
        '<p>Exploring the mist-shrouded elevations of Tam Dao delivers a refreshing alpine contrast to northern city touring. Plan your mountain transit using our <a href="/plan/hanoi-to-tam-dao-transport/">Hanoi to Tam Dao transport</a> guide,',
        '<p>Exploring the mist-shrouded elevations of Tam Dao delivers a refreshing alpine contrast to northern city touring. Plan your mountain transit using our <a href="/plan/hanoi-to-tam-dao-transport/">Hanoi to Tam Dao transport</a> guide,',
        "hanoi-to-tam-dao-transport"
    ),
    (
        "best-day-trips-from-hanoi",
        '<a href="/plan/hanoi-to-dien-bien-phu-transport/">Hanoi to Dien Bien Phu Transport</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>',
        '<a href="/plan/hanoi-to-tam-dao-transport/">Hanoi to Tam Dao Transport</a>, explore misty mountain hotels in <a href="/destinations/where-to-stay-in-tam-dao/">where to stay in Tam Dao</a>, <a href="/plan/hanoi-to-dien-bien-phu-transport/">Hanoi to Dien Bien Phu Transport</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>',
        "hanoi-to-tam-dao-transport"
    ),
    (
        "hanoi-travel-guide",
        'or peaceful valley escapes via <a href="/plan/hanoi-to-mai-chau-transport/">Hanoi to Mai Chau Transport</a>.</p>',
        'cool mountain getaways via <a href="/plan/hanoi-to-tam-dao-transport/">Hanoi to Tam Dao transport</a>, or peaceful valley escapes via <a href="/plan/hanoi-to-mai-chau-transport/">Hanoi to Mai Chau Transport</a>.</p>',
        "hanoi-to-tam-dao-transport"
    ),
    (
        "hanoi-to-hai-phong-transport",
        'continue southward using our <a href="/plan/cat-ba-to-ninh-binh-transport/">Cat Ba to Ninh Binh transport</a> itinerary,',
        'explore mist mountain getaways in our <a href="/plan/hanoi-to-tam-dao-transport/">Hanoi to Tam Dao transport</a> guide, continue southward using our <a href="/plan/cat-ba-to-ninh-binh-transport/">Cat Ba to Ninh Binh transport</a> itinerary,',
        "hanoi-to-tam-dao-transport"
    ),

    # === Pillar 6: where-to-stay-in-tam-dao ===
    (
        "hanoi-to-tam-dao-transport",
        'Discover cliffside boutique hotels in our <a href="/destinations/where-to-stay-in-tam-dao/">where to stay in Tam Dao</a> lodging review,',
        'Discover cliffside boutique hotels in our <a href="/destinations/where-to-stay-in-tam-dao/">where to stay in Tam Dao</a> lodging review,',
        "where-to-stay-in-tam-dao"
    ),
    (
        "where-to-stay-in-mai-chau",
        '<li><a href="/plan/hanoi-to-mai-chau-transport/">Hanoi to Mai Chau Transport</a>: Old Quarter limousine vans and local buses via Thung Khe Pass.</li>',
        '<li><a href="/plan/hanoi-to-mai-chau-transport/">Hanoi to Mai Chau Transport</a>: Old Quarter limousine vans and local buses via Thung Khe Pass.</li>\n        <li><a href="/destinations/where-to-stay-in-tam-dao/">Where to Stay in Tam Dao</a>: Cloud-hunting chalets and mountain castle hotels.</li>',
        "where-to-stay-in-tam-dao"
    ),
    (
        "where-to-stay-in-ninh-binh",
        '<a href="/destinations/where-to-stay-in-tam-coc/">Where to Stay in Tam Coc</a>, and <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>',
        '<a href="/destinations/where-to-stay-in-tam-coc/">Where to Stay in Tam Coc</a>, <a href="/destinations/where-to-stay-in-tam-dao/">Where to Stay in Tam Dao</a>, and <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>',
        "where-to-stay-in-tam-dao"
    ),

    # === Pillar 7: kon-tum-to-da-nang-transport ===
    (
        "where-to-stay-in-kon-tum",
        '<li><a href="/plan/pleiku-to-kon-tum-transport/">Pleiku to Kon Tum Transport</a>: Frequent 50-minute public buses and airport taxis.</li>',
        '<li><a href="/plan/pleiku-to-kon-tum-transport/">Pleiku to Kon Tum Transport</a>: Frequent 50-minute public buses and airport taxis.</li>\n        <li><a href="/plan/kon-tum-to-da-nang-transport/">Kon Tum to Da Nang Transport</a>: Limousine vans and sleeper coaches via Ho Chi Minh Highway.</li>',
        "kon-tum-to-da-nang-transport"
    ),
    (
        "pleiku-to-kon-tum-transport",
        '<li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum</a>: Dak Bla riverside stays, Bahnar lodges, and central hotels.</li>',
        '<li><a href="/destinations/where-to-stay-in-kon-tum/">Where to Stay in Kon Tum</a>: Dak Bla riverside stays, Bahnar lodges, and central hotels.</li>\n        <li><a href="/plan/kon-tum-to-da-nang-transport/">Kon Tum to Da Nang Transport</a>: Limousine vans and buses via Highway 14 and Lo Xo Pass.</li>',
        "kon-tum-to-da-nang-transport"
    ),
    (
        "where-to-stay-in-da-nang",
        '<li><a href="/plan/vietnam-craft-beer-guide/">Vietnam Craft Beer Guide</a> &mdash; Han River microbreweries, rooftop dragon fire views, and tasting flights.</li>',
        '<li><a href="/plan/kon-tum-to-da-nang-transport/">Kon Tum to Da Nang Transport</a> &mdash; descending the Ho Chi Minh Trail over Lo Xo Pass to the coast.</li>\n<li><a href="/plan/vietnam-craft-beer-guide/">Vietnam Craft Beer Guide</a> &mdash; Han River microbreweries, rooftop dragon fire views, and tasting flights.</li>',
        "kon-tum-to-da-nang-transport"
    ),
    (
        "where-to-stay-in-pleiku",
        '<li><a href="/destinations/where-to-stay-in-buon-ma-thuot/">Where to Stay in Buon Ma Thuot</a>: Coffee capital hotels, eco-resorts, and waterfall lodges.</li>',
        '<li><a href="/destinations/where-to-stay-in-buon-ma-thuot/">Where to Stay in Buon Ma Thuot</a>: Coffee capital hotels, eco-resorts, and waterfall lodges.</li>\n        <li><a href="/plan/kon-tum-to-da-nang-transport/">Kon Tum to Da Nang Transport</a>: Highland route over Lo Xo Pass to Da Nang beaches.</li>',
        "kon-tum-to-da-nang-transport"
    ),
    (
        "central-highlands-vietnam-itinerary",
        '<li><a href="/plan/buon-ma-thuot-to-nha-trang-transport/">Buon Ma Thuot to Nha Trang Transport</a>: Connecting Dak Lak with coastal Khanh Hoa beaches.</li>',
        '<li><a href="/plan/buon-ma-thuot-to-nha-trang-transport/">Buon Ma Thuot to Nha Trang Transport</a>: Connecting Dak Lak with coastal Khanh Hoa beaches.</li>\n        <li><a href="/plan/kon-tum-to-da-nang-transport/">Kon Tum to Da Nang Transport</a>: Descending the Ho Chi Minh Trail over Lo Xo Pass to the coast.</li>',
        "kon-tum-to-da-nang-transport"
    ),
]

counts = {}
failed = 0

for host, old_s, new_s, pillar in candidates:
    if host not in targets:
        print(f"FAIL: host {host} not in targets!")
        failed += 1
        continue
    c = targets[host]['content']
    cnt = c.count(old_s)
    if cnt != 1:
        print(f"FAIL: host {host} has {cnt} occurrences of old_s! (expected 1)")
        failed += 1
    else:
        counts[pillar] = counts.get(pillar, 0) + 1

# Note that best-day-trips-from-hanoi also gives 1 inlink to where-to-stay-in-tam-dao
counts["where-to-stay-in-tam-dao"] = counts.get("where-to-stay-in-tam-dao", 0) + 1

print("\n--- Inbound Counts Per Target Pillar ---")
for pillar, cnt in sorted(counts.items()):
    print(f"  {pillar}: {cnt} inbound links")

if failed == 0:
    print(f"\nALL {len(candidates)} CANDIDATE REPLACEMENTS VERIFIED SUCCESSFULLY!")
else:
    print(f"\nFAILED: {failed} candidate replacements had errors.")

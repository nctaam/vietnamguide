# -*- coding: utf-8 -*-
"""
Verify metadata for all 22 candidate images for Batch 7.
"""
import urllib.request
import urllib.parse
import json
import ssl
import sys
import time
import re

sys.stdout.reconfigure(encoding='utf-8')

BATCH7_ITEMS = [
    # 18 Destination Guides (Parent 7)
    {
        'id': 110,
        'slug': 'best-places-to-visit-vietnam',
        'file': 'Halong Bay Titov island.jpg',
        'alt': 'Panoramic aerial view of Ha Long Bay limestone karsts and turquoise waters from Titop Island',
        'caption': 'Sweeping aerial panorama of Ha Long Bay\'s iconic limestone peaks and emerald waters.'
    },
    {
        'id': 499,
        'slug': 'hoi-an-ancient-town-guide',
        'file': 'Hội An, Chùa Cầu, 2020-01 CN-01.jpg',
        'alt': 'Historic Japanese Covered Bridge Chua Cau over the canal in Hoi An',
        'caption': 'The 17th-century Japanese Covered Bridge (Chùa Cầu) in the heart of Hoi An ancient town.'
    },
    {
        'id': 500,
        'slug': 'hue-imperial-city-guide',
        'file': 'Thai Hoa Palace 20190917.jpg',
        'alt': 'Thai Hoa Palace of Supreme Harmony inside the Hue Imperial Citadel',
        'caption': 'Ornate Nguyen Dynasty throne hall of Điện Thái Hòa within the Hue Imperial Citadel.'
    },
    {
        'id': 501,
        'slug': 'da-nang-beaches-guide',
        'file': 'Non nuoc Basket boats.jpg',
        'alt': 'Traditional round woven bamboo basket boats resting on Da Nang white sand beach',
        'caption': 'Traditional round coracle basket boats resting on the broad sands of Da Nang\'s coastline.'
    },
    {
        'id': 503,
        'slug': 'phong-nha-travel-guide',
        'file': 'Paradise cave.JPG',
        'alt': 'Towering crystalline stalactites and stalagmites inside Paradise Cave Phong Nha',
        'caption': 'Colossal limestone stalagmites and cathedral-like karst chambers inside Paradise Cave.'
    },
    {
        'id': 518,
        'slug': 'sapa-travel-guide',
        'file': 'Stone church in Sapa.jpg',
        'alt': 'French colonial stone church and central town square in Sapa',
        'caption': 'The iconic French colonial stone church presiding over the central town square of Sapa.'
    },
    {
        'id': 519,
        'slug': 'ha-giang-loop-planning-guide',
        'file': 'SongNhoQue.JPG',
        'alt': 'Turquoise Nho Que River flowing through the deep Tu San canyon below Ma Pi Leng Pass',
        'caption': 'The turquoise Nho Que River carving through the monumental abyss of Tu San Canyon in Ha Giang.'
    },
    {
        'id': 525,
        'slug': 'where-to-stay-in-sapa',
        'file': 'Sapa vu du mont Ham Rong.jpg',
        'alt': 'Panoramic view of Sapa town nestled among mist-veiled mountain peaks and valley terraces',
        'caption': 'Elevated panorama across Sapa town and terraced valleys viewed from Ham Rong mountain.'
    },
    {
        'id': 528,
        'slug': 'mu-cang-chai-travel-guide',
        'file': 'Golden-terraced-fields-in-Mu-Cang-Chai.jpg',
        'alt': 'Vast tiers of golden ripe rice paddies sculpted into the mountain slopes of Mu Cang Chai',
        'caption': 'World-famous terraced rice amphitheaters sculpting the mountain slopes of Mu Cang Chai.'
    },
    {
        'id': 529,
        'slug': 'pu-luong-travel-guide',
        'file': 'Sunset in Pu Luong- Nov 2025.jpg',
        'alt': 'Misty sunset over tranquil terraced fields and limestone mountains of Pu Luong',
        'caption': 'Tranquil twilight glowing over traditional stilt-house villages in Pu Luong nature reserve.'
    },
    {
        'id': 611,
        'slug': 'da-lat-travel-guide',
        'file': 'Ho Xuan Huong 03.jpg',
        'alt': 'Peaceful waters of Xuan Huong Lake surrounded by pine-covered hills in Da Lat',
        'caption': 'Crescent-shaped Xuan Huong Lake reflecting pine forests in the cool mountain climate of Da Lat.'
    },
    {
        'id': 612,
        'slug': 'cao-bang-travel-guide',
        'file': 'Scenery around Nguom Ngao Cave - Cao Bang Province - Vietnam - 03 (48119876592).jpg',
        'alt': 'Scenic karst valley landscape near Nguom Ngao Cave in Cao Bang province',
        'caption': 'Dramatic limestone karst towers rising above fertile agricultural valleys in Cao Bang.'
    },
    {
        'id': 626,
        'slug': 'phu-yen-travel-guide',
        'file': 'Basalt rocks at Gành Đá Đĩa.jpg',
        'alt': 'Unique interlocking polygonal basalt rock columns at Ganh Da Dia in Phu Yen',
        'caption': 'The interlocking volcanic basalt columns of Gành Đá Đĩa forming a natural sea organ in Phú Yên.'
    },
    {
        'id': 922,
        'slug': 'best-things-to-do-in-ninh-binh',
        'file': 'View from Hang Mua.jpg',
        'alt': 'Panoramic karst mountain view overlooking the winding Ngo Dong River and Tam Coc valley from Hang Mua',
        'caption': 'Spectacular 360-degree karst panorama over Tam Coc valley from the Dragon peak of Hang Múa.'
    },
    {
        'id': 924,
        'slug': 'best-things-to-do-in-mui-ne',
        'file': 'Mui Ne - White sanddunes.JPG',
        'alt': 'Vast rolling white sand dunes and desert oasis landscape in Mui Ne',
        'caption': 'Expansive rolling dunes of the White Sand Dunes (Đồi Cát Trắng) on the coast of Mũi Né.'
    },
    {
        'id': 926,
        'slug': 'best-things-to-do-in-quy-nhon',
        'file': 'Eo Gió - Nhơn Lý.jpg',
        'alt': 'Rugged coastal walking path along the dramatic rocky cliffs of Eo Gio in Quy Nhon',
        'caption': 'Dramatic rocky cliffs and coastal boardwalk embracing the wind at Eo Gió in Quy Nhơn.'
    },
    {
        'id': 1495,
        'slug': 'mang-den-travel-guide',
        'file': 'Chiec-thuyen-ngap-nuoc-o-ho-dak-ke-mang-den.jpg',
        'alt': 'Pine forests and tranquil highland lake scenery at Dak Ke Lake in Mang Den',
        'caption': 'Highland pine forests mirrored in the serene waters of Đắk Ke Lake in Măng Đen.'
    },
    {
        'id': 1496,
        'slug': 'ba-be-lake-travel-guide',
        'file': 'Scene at Ba Be Lake - Ba Be - Vietnam - 01 (48096891367).jpg',
        'alt': 'Wooden boat excursion gliding across the calm waters of Ba Be freshwater lake',
        'caption': 'Scenic boat journey across Ba Bể freshwater lake framed by primary rainforest cliffs.'
    },

    # 4 Cost Budget Guides (Parent 10)
    {
        'id': 600,
        'slug': 'ha-giang-loop-cost-budget',
        'file': 'Ma Pi Leng Pass winding road Ha Giang Vietnam.jpg',
        'alt': 'Motorcyclists riding the winding mountain road along Ma Pi Leng Pass in Ha Giang',
        'caption': 'Navigating the legendary cliffside curves of Mã Pí Lèng Pass on a Ha Giang loop budget.'
    },
    {
        'id': 601,
        'slug': 'da-nang-hoi-an-budget',
        'file': 'Boats with lanterns on the Thu Bon river IMG 3864.jpg',
        'alt': 'Traditional wooden sampans with candlelit paper lanterns floating on the Thu Bon River in Hoi An',
        'caption': 'Night boat excursion illuminated by candlelit lanterns along the Thu Bồn River in Hội An.'
    },
    {
        'id': 602,
        'slug': 'hanoi-ninh-binh-ha-long-budget',
        'file': 'Trang An Landscape Complex, Ninh Binh Province, Vietnam, 20240202 1456 5313.jpg',
        'alt': 'Traditional flat-bottomed sampan rowboat on the river at Trang An Ninh Binh',
        'caption': 'Budget-friendly traditional rowboat exploring the karst caverns of Tràng An in Ninh Bình.'
    },
    {
        'id': 1002,
        'slug': 'ho-chi-minh-city-mekong-budget',
        'file': 'Ben Thanh, Ciudad Ho Chi Minh, Vietnam, 2013-08-14, DD 01.JPG',
        'alt': 'Historic clocktower entrance facade of Ben Thanh Market in District 1 Ho Chi Minh City',
        'caption': 'Iconic south clocktower facade of Bến Thành Market, a focal point for Saigon budget travelers.'
    }
]

def fetch_commons_meta(filename, target_width=1280):
    params = {
        'action': 'query',
        'titles': f"File:{filename}",
        'prop': 'imageinfo',
        'iiprop': 'url|size|extmetadata',
        'iiurlwidth': str(target_width),
        'format': 'json'
    }
    url = f"https://commons.wikimedia.org/w/api.php?{urllib.parse.urlencode(params)}"
    req = urllib.request.Request(url, headers={'User-Agent': 'VietnamGuideBot/1.0 (contact@vietnamguide.net)'})
    ctx = ssl.create_default_context()
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=15) as resp:
            data = json.loads(resp.read().decode('utf-8'))
        pages = data.get('query', {}).get('pages', {})
        for pid, pdata in pages.items():
            if int(pid) < 0:
                return {'error': 'File not found on Commons'}
            ii = pdata.get('imageinfo', [{}])[0]
            em = ii.get('extmetadata', {})
            author = em.get('Artist', {}).get('value', 'Wikimedia Commons')
            author_clean = re.sub(r'<[^>]+>', '', author).strip()
            if not author_clean:
                author_clean = 'Wikimedia Commons contributor'
            license_short = em.get('LicenseShortName', {}).get('value', 'CC BY-SA')
            license_url = em.get('LicenseUrl', {}).get('value', 'https://creativecommons.org/licenses/')
            return {
                'thumburl': ii.get('thumburl'),
                'thumbwidth': ii.get('thumbwidth'),
                'thumbheight': ii.get('thumbheight'),
                'origurl': ii.get('url'),
                'origwidth': ii.get('width'),
                'origheight': ii.get('height'),
                'descriptionurl': ii.get('descriptionurl'),
                'author': author_clean[:50],
                'license_short': license_short,
                'license_url': license_url
            }
    except Exception as e:
        return {'error': str(e)}

def main():
    print(f"Checking metadata for {len(BATCH7_ITEMS)} Batch 7 candidates...")
    all_ok = True
    results = []
    for idx, item in enumerate(BATCH7_ITEMS, 1):
        meta = fetch_commons_meta(item['file'])
        if 'error' in meta:
            print(f"[{idx:02d}/22] ERROR for {item['slug']} ({item['file']}): {meta['error']}")
            all_ok = False
        else:
            w = meta['thumbwidth']
            h = meta['thumbheight']
            ratio = w / h if h else 0
            print(f"[{idx:02d}/22] OK: [{item['id']}] {item['slug']}")
            print(f"        File: {item['file']}")
            print(f"        Dim: {w}x{h} (ratio: {ratio:.2f}) | Author: {meta['author']} | License: {meta['license_short']}")
            item['meta'] = meta
            results.append(item)
        time.sleep(0.7)
    
    if all_ok:
        print("\nAll 22 items verified successfully with rich metadata!")
        with open('ops/batch7_final_images.json', 'w', encoding='utf-8') as f:
            json.dump(results, f, ensure_ascii=False, indent=2)
        print("Saved to ops/batch7_final_images.json")
    else:
        print("\nSome items failed verification!")

if __name__ == '__main__':
    main()

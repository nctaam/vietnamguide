# -*- coding: utf-8 -*-
"""
VietnamGuide Batch 4: Generate Verified Plan for 21 Remaining Regional Lodging Guides
Fetches exact metadata from Wikimedia Commons API with rate-limit protection.
"""
import urllib.request
import urllib.parse
import json
import ssl
import sys
import time
import re

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

BATCH4_ITEMS = [
    {
        'id': 1206,
        'slug': 'where-to-stay-in-dien-bien-phu',
        'file': 'Panorama of Ricefields in Muong Thanh Valley - Dien Bien Phu - Vietnam - 01 (48178533987).jpg',
        'alt': 'Lush green rice paddies and surrounding mountains of Muong Thanh valley in Dien Bien Phu',
        'caption': 'The broad alluvial Muong Thanh basin hosts central hotels and scenic mountain homestays near Dien Bien Phu historic sites.'
    },
    {
        'id': 1233,
        'slug': 'where-to-stay-in-ben-tre',
        'file': 'Song Ben Tre @TxBenTre.jpg',
        'alt': 'Ben Tre river waterfront with motorized wooden sampans and riverside coconut palms',
        'caption': 'Ben Tre’s tranquil riverfront lodges and coconut orchard homestays offer immersive Mekong Delta eco-stays.'
    },
    {
        'id': 1234,
        'slug': 'where-to-stay-in-lang-co',
        'file': 'Lang Co Bay (26631664428).jpg',
        'alt': 'Curving white sand beach and turquoise waters of Lang Co bay backed by Bach Ma mountains',
        'caption': 'Tucked beneath Hai Van Pass, Lang Co features tranquil beachfront resorts and lagoon-facing seafood retreats.'
    },
    {
        'id': 1275,
        'slug': 'where-to-stay-in-cam-ranh',
        'file': 'Tu tren cao cam ranh.jpg',
        'alt': 'Aerial coastal view of Cam Ranh peninsula and turquoise waters of Bai Dai beach',
        'caption': 'Bai Dai along the Cam Ranh peninsula is lined with spacious international 5-star beachfront resorts just minutes from the airport.'
    },
    {
        'id': 1276,
        'slug': 'where-to-stay-in-rach-gia',
        'file': 'Rạch Giá, Kien Giang, Vietnam - panoramio.jpg',
        'alt': 'Urban coastal boulevard and seaport waterfront of Rach Gia in Kien Giang province',
        'caption': 'Coastal transit hotels in Rach Gia cater to travelers overnighting before morning fast ferry departures to Phu Quoc and Nam Du.'
    },
    {
        'id': 1315,
        'slug': 'where-to-stay-in-soc-trang',
        'file': 'Chánh điện chùa Dơi - Sóc Trăng.jpg',
        'alt': 'Ornate gilded sanctum and curved tiled roof of Chua Doi Bat Pagoda in Soc Trang',
        'caption': 'Soc Trang provides comfortable town hotels for visitors exploring historic Khmer temples and departing for Con Dao speedboats.'
    },
    {
        'id': 1352,
        'slug': 'where-to-stay-in-bac-lieu',
        'file': 'Bạc Liêu windpower farm.jpg',
        'alt': 'Offshore wind turbine farm extending across coastal mudflats into the sea in Bac Lieu',
        'caption': 'Bac Lieu town offers central boutique lodging for touring coastal offshore wind farms and French-colonial heritage villas.'
    },
    {
        'id': 1353,
        'slug': 'where-to-stay-in-ca-mau',
        'file': 'Mudflats in Dat Mui.jpg',
        'alt': 'Mangrove wetlands and tidal mudflats at Dat Mui Ca Mau at the southern tip of Vietnam',
        'caption': 'Eco-lodges and guesthouses around Dat Mui immerse travelers in Vietnam’s southernmost mangrove national park.'
    },
    {
        'id': 1387,
        'slug': 'where-to-stay-in-hai-phong',
        'file': 'Nha hat tp.jpg',
        'alt': 'Colonial architecture and manicured flower gardens of Hai Phong Municipal Theater Opera House',
        'caption': 'Central Hai Phong hotels place travelers within walking distance of the historic French quarter, cafe streets, and railway station.'
    },
    {
        'id': 1390,
        'slug': 'where-to-stay-in-phan-rang',
        'file': 'Tháp Po Klong Garai Phan Rang - panoramio.jpg',
        'alt': 'Historic Po Klong Garai red brick Cham sanctuary towers on a hill in Phan Rang Ninh Thuan',
        'caption': 'Phan Rang offers coastal beach resorts along Ninh Chu beach alongside quiet city hotels near historic Cham towers.'
    },
    {
        'id': 1392,
        'slug': 'where-to-stay-in-tam-dao',
        'file': 'Phong cảnh Tam Đảo.JPG',
        'alt': 'Misty mountain ridges and hill station villas nestled in the high cloud forests of Tam Dao',
        'caption': 'Tam Dao hill station perches at 900 meters altitude, featuring cool-climate alpine chalets and misty valley hotels.'
    },
    {
        'id': 1428,
        'slug': 'where-to-stay-in-vinh-hy',
        'file': 'Vinh hy bay.jpg',
        'alt': 'Sheltered emerald cove and fishing boats anchored in scenic Vinh Hy bay surrounded by Nui Chua cliffs',
        'caption': 'Vinh Hy Bay shelters exclusive luxury cliffside villas alongside intimate traditional fishing village homestays.'
    },
    {
        'id': 1453,
        'slug': 'where-to-stay-in-du-gia',
        'file': 'Du Gia 062026.jpg',
        'alt': 'Quiet mountain valley village and clear freshwater stream in Du Gia Ha Giang',
        'caption': 'Du Gia valley homestays provide traditional Tay stilt house hospitality and waterfall swimming holes along the Ha Giang Loop.'
    },
    {
        'id': 1454,
        'slug': 'where-to-stay-in-mang-den',
        'file': 'Thị trấn Măng Đen.jpg',
        'alt': 'Lush pine forests and quiet mountain town center of Mang Den plateau in Kon Tum',
        'caption': 'Dubbed the second Da Lat at 1,200m altitude, Mang Den features tranquil pine-scented wooden chalets and forest retreats.'
    },
    {
        'id': 1459,
        'slug': 'where-to-stay-in-an-giang',
        'file': 'Tra Su Cajuput Forest, An Giang, Viet Nam.jpg',
        'alt': 'Emerald duckweed carpet and submerged cajuput trees navigated by rowboats in Tra Su forest An Giang',
        'caption': 'An Giang offers riverside hotels in Long Xuyen and border market stays in Chau Doc near floating villages and Tra Su forest.'
    },
    {
        'id': 1494,
        'slug': 'where-to-stay-in-ly-son',
        'file': 'Ly Son3.jpg',
        'alt': 'Turquoise volcanic island coastline and lush green crater rim on Ly Son Island',
        'caption': 'Ly Son island homestays and waterfront hotels face dramatic volcanic black cliffs and coral reef beaches.'
    },
    {
        'id': 1529,
        'slug': 'where-to-stay-in-yen-minh',
        'file': 'Autumn comes on TerraceField-YenMinh HaGiang Vietnam.jpg',
        'alt': 'Layered golden rice terraces carved into limestone mountainsides in Yen Minh Ha Giang',
        'caption': 'Yen Minh serves as an essential mid-loop overnight stop on the Ha Giang circuit with comfortable family-run guesthouses.'
    },
    {
        'id': 1530,
        'slug': 'where-to-stay-in-quang-ngai',
        'file': 'Sông Trà Khúc đoạn qua TP.Quảng Ngãi.JPG',
        'alt': 'Wide Tra Khuc river flowing through the provincial capital city of Quang Ngai',
        'caption': 'Quang Ngai city hotels offer a relaxed urban staging base near Sa Ky port for early morning ferries to Ly Son Island.'
    },
    {
        'id': 1561,
        'slug': 'where-to-stay-in-bac-ha',
        'file': 'Bac Ha market day, Vietnam.jpg',
        'alt': 'Flower Hmong women in vibrant embroidered traditional dress trading at Bac Ha Sunday Market',
        'caption': 'Bac Ha’s village homestays and town lodges offer front-row access to the world-famous Sunday morning Flower Hmong ethnic market.'
    },
    {
        'id': 1562,
        'slug': 'where-to-stay-in-bao-lac',
        'file': 'Lý Bôn, Bảo Lạc, Cao Bằng, Vietnam - panoramio.jpg',
        'alt': 'Scenic river valley and limestone mountain backdrop in Bao Lac district Cao Bang',
        'caption': 'Bao Lac mountain guesthouses provide essential rest for adventure riders traversing the rugged pass between Meo Vac and Cao Bang.'
    },
    {
        'id': 1563,
        'slug': 'where-to-stay-in-bao-loc',
        'file': 'Dambri Waterfall 1.jpg',
        'alt': 'Towering cascade and misty gorge of Dambri Waterfall surrounded by rainforest in Bao Loc',
        'caption': 'Bao Loc highlands offer peaceful tea estate homestays and mountain lodges cooler and quieter than nearby Da Lat.'
    }
]

def clean_html(raw):
    return re.sub(r'<[^>]+>', '', raw).strip()

def get_file_info(file_title):
    params = {
        'action': 'query',
        'titles': f"File:{file_title}",
        'prop': 'imageinfo',
        'iiprop': 'url|size|extmetadata',
        'iiurlwidth': '1280',
        'format': 'json'
    }
    url = f"https://commons.wikimedia.org/w/api.php?{urllib.parse.urlencode(params)}"
    req = urllib.request.Request(url, headers={'User-Agent': 'VietnamGuideBot/1.0 (https://vietnamguide.net; contact@vietnamguide.net)'})
    ctx = ssl.create_default_context()
    
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=15) as resp:
            data = json.loads(resp.read().decode('utf-8'))
    except Exception as e:
        print(f"   Network error fetching {file_title}: {e}")
        return None
    
    pages = data.get('query', {}).get('pages', {})
    for pid, pdata in pages.items():
        if pid == '-1':
            return None
        ii = pdata.get('imageinfo', [{}])[0]
        meta = ii.get('extmetadata', {})
        thumb = ii.get('thumburl')
        w = int(ii.get('thumbwidth', 0))
        h = int(ii.get('thumbheight', 0))
        
        artist = clean_html(meta.get('Artist', {}).get('value', 'Wikimedia Commons'))
        if not artist or len(artist) > 50:
            artist = 'Wikimedia Commons'
        license_name = meta.get('LicenseShortName', {}).get('value', 'CC BY-SA')

        return {
            'file_title': file_title,
            'thumb_url': thumb,
            'orig_url': ii.get('url'),
            'width': w,
            'height': h,
            'page_url': ii.get('descriptionurl'),
            'artist': artist,
            'license': license_name
        }
    return None

def main():
    print(f"Resolving exact Wikimedia metadata for all {len(BATCH4_ITEMS)} Batch 4 lodging guides...")
    results = []
    failed = []

    for idx, item in enumerate(BATCH4_ITEMS, 1):
        print(f"[{idx}/{len(BATCH4_ITEMS)}] Resolving '{item['file']}' ({item['slug']})...")
        info = get_file_info(item['file'])
        if info and info['thumb_url']:
            print(f"   -> OK: {info['width']}x{info['height']} | {info['artist']} ({info['license']})")
            results.append({
                'post_id': item['id'],
                'slug': item['slug'],
                'alt': item['alt'],
                'caption': item['caption'],
                'image': info
            })
        else:
            print(f"   -> FAILED for: {item['file']}")
            failed.append(item['slug'])
        time.sleep(0.8) # Polite pause to prevent 429 rate limiting

    print(f"\nFinal Verified Count: {len(results)} / {len(BATCH4_ITEMS)}")
    if failed:
        print(f"Failed items: {failed}")
        sys.exit(1)

    out_file = 'ops/batch4_final_images.json'
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(results, f, ensure_ascii=False, indent=2)
    print(f"Saved all 21 verified definitions to {out_file}")

if __name__ == '__main__':
    main()

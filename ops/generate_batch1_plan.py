# -*- coding: utf-8 -*-
"""
VietnamGuide Batch 1: 30 Curated Authentic Real Photographs for Where-to-Stay Guides
"""
import urllib.request
import urllib.parse
import json
import ssl
import sys
import re

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

DESTINATIONS_CONFIG = [
    {
        'id': 680,
        'slug': 'where-to-stay-in-da-nang',
        'query': 'My Khe Beach Da Nang Vietnam',
        'alt': 'My Khe Beach coastline and resort hotels in Da Nang, Vietnam',
        'caption': 'Da Nang offers a distinct split between beachfront resorts along My Khe and central city hotels along the Han River.'
    },
    {
        'id': 681,
        'slug': 'where-to-stay-in-hoi-an',
        'query': 'Hoi An Ancient Town',
        'alt': 'Thu Bon river and lantern-lit streets in Hoi An Ancient Town, Vietnam',
        'caption': 'Staying in Hoi An requires balancing walkable access to the pedestrian Ancient Town against quiet beachfront bases at An Bang.'
    },
    {
        'id': 821,
        'slug': 'where-to-stay-in-hue',
        'query': 'Hue Citadel Imperial City Vietnam',
        'alt': 'Ngo Mon Gate and moat of the Hue Imperial Citadel in Vietnam',
        'caption': 'Hue lodging centers along the southern bank of the Perfume River, offering easy access to the Imperial Citadel and royal tombs.'
    },
    {
        'id': 857,
        'slug': 'where-to-stay-in-phu-quoc',
        'query': 'Sunset Long Beach Phu Quoc Island Vietnam',
        'alt': 'Sunset over the Gulf of Thailand along Long Beach in Phu Quoc, Vietnam',
        'caption': 'Phu Quoc lodging spans bustling sunset beach hotels along Bai Truong (Long Beach) to secluded northern and southern bay hideaways.'
    },
    {
        'id': 860,
        'slug': 'where-to-stay-in-nha-trang',
        'query': 'Nha Trang Beach',
        'alt': 'Tran Phu beachfront promenade and coastal high-rises in Nha Trang, Vietnam',
        'caption': 'Tran Phu boulevard hosts the highest concentration of beachfront hotels, while northern Hon Chong offers calmer local quarters.'
    },
    {
        'id': 888,
        'slug': 'where-to-stay-in-da-lat',
        'query': 'Da Lat Xuan Huong Lake Vietnam',
        'alt': 'Xuan Huong Lake and pine-covered hills in central Da Lat, Vietnam',
        'caption': 'Da Lat bases divide between bustling French Quarter hotels near Xuan Huong Lake and quiet pine-valley forest eco-resorts.'
    },
    {
        'id': 892,
        'slug': 'where-to-stay-in-phong-nha',
        'query': 'Son River Phong Nha Ke Bang Vietnam',
        'alt': 'Limestone karst peaks along Son River in Phong Nha, Vietnam',
        'caption': 'Phong Nha accommodation concentrates along the main village riverside road, serving as the staging base for national park cave expeditions.'
    },
    {
        'id': 925,
        'slug': 'where-to-stay-in-mui-ne',
        'query': 'Vietnam Mui Ne Kitesurfing',
        'alt': 'Palm-lined coastal beachfront in Mui Ne, Phan Thiet, Vietnam',
        'caption': 'Nguyen Dinh Chieu street forms the classic resort strip in Mui Ne, balancing watersports access with seaside dining.'
    },
    {
        'id': 964,
        'slug': 'where-to-stay-in-ha-long-bay',
        'query': 'Bai Chay beach Ha Long Vietnam',
        'alt': 'Limestone karst islands rising from the waters of Ha Long Bay, Vietnam',
        'caption': 'Visitors to Ha Long Bay choose between modern mainland hotel hubs at Bai Chay and overnight luxury cruise vessels on the water.'
    },
    {
        'id': 965,
        'slug': 'where-to-stay-in-quy-nhon',
        'query': 'Ky Co beach Quy Nhon Vietnam',
        'alt': 'Turquoise waters and dramatic coastal cliffs at Ky Co near Quy Nhon, Vietnam',
        'caption': 'Quy Nhon offers relaxed city beachfront hotels along Xuan Dieu alongside cliffside eco-resorts tucked into Bai Xep fishing cove.'
    },
    {
        'id': 966,
        'slug': 'where-to-stay-in-can-tho',
        'query': 'Ninh Kieu Quay Can Tho',
        'alt': 'Hau River and Ninh Kieu wharf promenade in central Can Tho, Mekong Delta',
        'caption': 'Ninh Kieu wharf forms Can Tho premier lodging district, placing travelers within steps of early morning floating market boats.'
    },
    {
        'id': 967,
        'slug': 'where-to-stay-in-cat-ba',
        'query': 'Cat Ba Island Vietnam',
        'alt': 'Harbor view and limestone cliffs surrounding Cat Ba Town, Vietnam',
        'caption': 'Cat Ba Town provides direct ferry and tour harbor access, while isolated cove resorts across Lan Ha Bay offer true marine seclusion.'
    },
    {
        'id': 968,
        'slug': 'where-to-stay-in-ha-giang',
        'query': 'Ha Giang landscape Vietnam',
        'alt': 'Mountain valley and river scenery surrounding Ha Giang city in northern Vietnam',
        'caption': 'Ha Giang City serves as the staging base for the northern loop, featuring town transit hotels alongside traditional Tay stilt homestays.'
    },
    {
        'id': 997,
        'slug': 'where-to-stay-in-mai-chau',
        'query': 'Mai Chau Hoa Binh',
        'alt': 'Verdant rice paddies and White Thai stilt homestays in Mai Chau valley, Vietnam',
        'caption': 'Mai Chau lodging centers on White Thai ethnic stilt homestays in Lac and Pom Coong villages, framed by green rice paddies.'
    },
    {
        'id': 998,
        'slug': 'where-to-stay-in-pu-luong',
        'query': 'Pu Luong National Reserve',
        'alt': 'Terraced rice fields and mountain retreat in Pu Luong Nature Reserve, Vietnam',
        'caption': 'Pu Luong offers boutique mountain eco-lodges perched above dramatic rice terraces and bamboo waterwheels in Don village.'
    },
    {
        'id': 999,
        'slug': 'where-to-stay-in-con-dao',
        'query': 'Con Dao island Vietnam',
        'alt': 'Golden sand and turquoise waters on Con Dao Island, Vietnam',
        'caption': 'Con Son town retains peaceful colonial-era seaside guesthouses, while upscale island resorts occupy private ocean coves.'
    },
    {
        'id': 1034,
        'slug': 'where-to-stay-in-cao-bang',
        'query': 'Ban Gioc Waterfall Cao Bang Vietnam',
        'alt': 'Multi-tiered Ban Gioc Waterfall on the border in Cao Bang province, Vietnam',
        'caption': 'Cao Bang visitors balance central city riverside hotels against rustic Tay homestays near Ban Gioc Waterfall and Nguom Ngao Cave.'
    },
    {
        'id': 1035,
        'slug': 'where-to-stay-in-phu-yen',
        'query': 'Ganh Da Dia basalt columns Phu Yen Vietnam',
        'alt': 'Interlocking basalt columns of Ganh Da Dia on the Phu Yen coast, Vietnam',
        'caption': 'Tuy Hoa city offers wide, uncrowded beachfront hotels, with boutique surf lodges scattered along the rural south-central coast.'
    },
    {
        'id': 1036,
        'slug': 'where-to-stay-in-ba-be',
        'query': 'Ba Be Lake 2',
        'alt': 'Tranquil freshwater lake and forested karst cliffs in Ba Be National Park, Vietnam',
        'caption': 'Pac Ngoi and Bo Lu villages provide authentic Tay stilt homestays directly along the quiet shores of Ba Be Lake.'
    },
    {
        'id': 1066,
        'slug': 'where-to-stay-in-dong-van',
        'query': 'Doline SinhLung HaGiang VN',
        'alt': 'Stone architecture and limestone peaks surrounding Dong Van town in Ha Giang',
        'caption': 'Dong Van Old Quarter offers restored 19th-century clay and stone guesthouses at the northernmost point of the rocky plateau.'
    },
    {
        'id': 1070,
        'slug': 'where-to-stay-in-meo-vac',
        'query': 'Ma Pi Leng pass Vietnam',
        'alt': 'Dramatic mountain road winding through Ma Pi Leng pass near Meo Vac, Ha Giang',
        'caption': 'Pa Vi Hmong Cultural Village provides purpose-built heritage homestays at the foot of the iconic Ma Pi Leng mountain pass.'
    },
    {
        'id': 1097,
        'slug': 'where-to-stay-in-ha-tien',
        'query': 'Mui Nai beach Ha Tien',
        'alt': 'Gulf of Thailand shoreline at Mui Nai near Ha Tien, Kien Giang, Vietnam',
        'caption': 'Ha Tien combines riverside commercial hotels near the Cambodia border crossing with relaxed beachside motels at Mui Nai.'
    },
    {
        'id': 1101,
        'slug': 'where-to-stay-in-buon-ma-thuot',
        'query': 'Dray Nur Waterfall',
        'alt': 'Powerful cascades of Dray Nur waterfall near Buon Ma Thuot in Dak Lak, Vietnam',
        'caption': 'Buon Ma Thuot central hotels offer convenient urban amenities, while coffee farm lodges offer immersive stays in the Central Highlands.'
    },
    {
        'id': 1136,
        'slug': 'where-to-stay-in-tam-coc',
        'query': 'Ninh Binh Tam Coc',
        'alt': 'Rowing boats on the Ngo Dong river winding through limestone karsts in Tam Coc, Ninh Binh',
        'caption': 'Tam Coc village hosts Ninh Binh highest concentration of traveler homestays and boutique eco-lodges along the Ngo Dong river.'
    },
    {
        'id': 1137,
        'slug': 'where-to-stay-in-vung-tau',
        'query': 'Bai Truoc Vung Tau',
        'alt': 'Front Beach (Bai Truoc) coastal promenade and palm trees in Vung Tau, Vietnam',
        'caption': 'Vung Tau divides between lively Back Beach (Bai Sau) resort towers and relaxed sunset dining hotels along Front Beach (Bai Truoc).'
    },
    {
        'id': 1168,
        'slug': 'where-to-stay-in-dong-hoi',
        'query': 'Cau Nhat Le Dong Hoi',
        'alt': 'Nhat Le river mouth and coastal bridge in Dong Hoi, Quang Binh, Vietnam',
        'caption': 'Dong Hoi provides comfortable urban and beachfront lodging for travelers arriving by train or flight before heading to Phong Nha caves.'
    },
    {
        'id': 1170,
        'slug': 'where-to-stay-in-pleiku',
        'query': 'Bien Ho TP Pleiku Gia Lai',
        'alt': 'Calm volcanic crater lake Bien Ho surrounded by pine forests in Pleiku, Gia Lai',
        'caption': 'Pleiku town center hotels provide practical overland transit bases along Highway 14, within easy reach of volcanic tea plantations.'
    },
    {
        'id': 1173,
        'slug': 'where-to-stay-in-mu-cang-chai',
        'query': 'Mu Cang Chai terrace rice fields Yen Bai Vietnam',
        'alt': 'Golden terraced rice paddies cascading down the mountain slopes of Mu Cang Chai, Vietnam',
        'caption': 'Mu Cang Chai lodging consists mainly of family-run Hmong and Thai homestays in La Pan Tan and riverside guesthouses in town.'
    },
    {
        'id': 1201,
        'slug': 'where-to-stay-in-kon-tum',
        'query': 'Kontum wooden catholic church',
        'alt': 'Centuries-old Roman-Gothic wooden church in Kon Tum, Central Highlands of Vietnam',
        'caption': 'Kon Tum offers quiet riverside hotels along the Dak Bla River and authentic Bahnar village homestays near traditional communal Rong houses.'
    },
    {
        'id': 1203,
        'slug': 'where-to-stay-in-chau-doc',
        'query': 'Chau Doc City streets Vietnam',
        'alt': 'Central streets and waterways of Chau Doc in the Mekong Delta near Cambodia',
        'caption': 'Chau Doc town hotels line the Bassac River near floating markets and border speedboat piers connecting to Phnom Penh.'
    }
]

def clean_html(raw):
    return re.sub(r'<[^>]+>', '', raw).strip()

def search_wikimedia(query):
    params = {
        'action': 'query',
        'generator': 'search',
        'gsrsearch': f"{query} filetype:bitmap",
        'gsrnamespace': '6',
        'gsrlimit': '6',
        'prop': 'imageinfo',
        'iiprop': 'url|size|extmetadata',
        'iiurlwidth': '1280',
        'format': 'json'
    }
    url = f"https://commons.wikimedia.org/w/api.php?{urllib.parse.urlencode(params)}"
    req = urllib.request.Request(url, headers={'User-Agent': 'VietnamGuideBot/1.0 (https://vietnamguide.net; contact@vietnamguide.net)'})
    ctx = ssl.create_default_context()
    with urllib.request.urlopen(req, context=ctx, timeout=12) as resp:
        data = json.loads(resp.read().decode('utf-8'))
    
    pages = data.get('query', {}).get('pages', {})
    for pid, pdata in pages.items():
        title = pdata.get('title', '')
        ii = pdata.get('imageinfo', [{}])[0]
        meta = ii.get('extmetadata', {})
        thumb = ii.get('thumburl')
        w = int(ii.get('thumbwidth', 0))
        h = int(ii.get('thumbheight', 0))
        # Filter for valid landscape or reasonable portrait
        if thumb and w >= 1000 and 450 <= h <= 1200:
            artist = clean_html(meta.get('Artist', {}).get('value', 'Wikimedia Commons'))
            if not artist or len(artist) > 50:
                artist = 'Wikimedia Commons'
            license_name = meta.get('LicenseShortName', {}).get('value', 'CC BY-SA')
            
            return {
                'file_title': title.replace('File:', ''),
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
    print(f"Resolving 30 authentic Wikimedia images for Batch 1...")
    results = []
    failed = []
    
    for item in DESTINATIONS_CONFIG:
        print(f"Resolving [{item['slug']}] (query: {item['query']})...")
        img_info = search_wikimedia(item['query'])
        if img_info:
            print(f"  -> OK: {img_info['file_title']} ({img_info['width']}x{img_info['height']}) - {img_info['artist']} ({img_info['license']})")
            results.append({
                'post_id': item['id'],
                'slug': item['slug'],
                'alt': item['alt'],
                'caption': item['caption'],
                'image': img_info
            })
        else:
            print(f"  -> FAILED: {item['slug']}")
            failed.append(item['slug'])

    print(f"\nFinal tally: {len(results)} / {len(DESTINATIONS_CONFIG)}")
    if failed:
        print(f"Errors on: {failed}")
        sys.exit(1)
    
    with open('ops/batch1_final_images.json', 'w', encoding='utf-8') as f:
        json.dump(results, f, ensure_ascii=False, indent=2)
    print("Saved all 30 verified image definitions to ops/batch1_final_images.json")

if __name__ == '__main__':
    main()

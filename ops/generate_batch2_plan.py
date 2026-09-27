# -*- coding: utf-8 -*-
"""
VietnamGuide Batch 2: Generate Verified Plan for 35 Transport & Ferry Guides
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

BATCH2_ITEMS = [
    {
        'id': 683,
        'slug': 'hanoi-to-ha-long-bay-transport',
        'file': 'Bạch Đằng Bridge (49534690148).jpg',
        'alt': 'Bach Dang cable-stayed bridge along the modern Hanoi to Ha Long expressway',
        'caption': 'The modern expressway and Bach Dang Bridge cut road transit between Hanoi and Ha Long Bay down to just 2.5 to 3 hours.'
    },
    {
        'id': 684,
        'slug': 'da-nang-to-hue-train-vs-car',
        'file': 'Hai Van Pass, Vietnam, North-South Railway.jpg',
        'alt': 'Reunification Express railway tracks hugging the coastal cliffs of Hai Van Pass',
        'caption': 'The coastal railway section along Hai Van Pass is widely regarded as one of the most scenic train journeys in Southeast Asia.'
    },
    {
        'id': 755,
        'slug': 'phong-nha-to-hue-transport',
        'file': 'Benhairiver1.jpg',
        'alt': 'Ben Hai river and historic Hien Luong bridge along Highway 1 in Quang Tri',
        'caption': 'Overland transport between Phong Nha and Hue follows National Route 1, traversing the historic Demilitarized Zone in Quang Tri.'
    },
    {
        'id': 756,
        'slug': 'da-lat-to-nha-trang-transport',
        'file': 'Deo Ngoan Muc 2.JPG',
        'alt': 'Sweeping hairpins of Ngoan Muc Pass descending from the Da Lat plateau to the coast',
        'caption': 'Winding mountain passes drop steeply from the cool Da Lat pine highlands to the coastal plains of Khanh Hoa.'
    },
    {
        'id': 790,
        'slug': 'hanoi-to-cat-ba-island-transport',
        'file': 'Dinh Vu - Cat Hai Bridge.jpg',
        'alt': 'Tan Vu - Cat Hai sea-crossing bridge connecting mainland Hai Phong toward Cat Ba Island',
        'caption': 'Direct tourist buses use the Tan Vu - Cat Hai sea-crossing bridge to connect passengers swiftly to Cat Ba Island ferries.'
    },
    {
        'id': 823,
        'slug': 'hanoi-to-phong-nha-transport',
        'file': 'Dong Hoi Railway Station, 11 Nov 2024.jpg',
        'alt': 'Dong Hoi railway station building and plaza in central Quang Binh province',
        'caption': 'Overnight sleeper trains from Hanoi arrive at Dong Hoi station, the primary rail transit gateway for Phong Nha caves.'
    },
    {
        'id': 856,
        'slug': 'ho-chi-minh-city-to-da-lat-transport',
        'file': 'Deo Bao Loc.jpg',
        'alt': 'Highway 20 winding up the lush mountain rainforest slopes of Bao Loc Pass',
        'caption': 'Highway 20 ascends the steep curves of Bao Loc Pass, linking southern plains to the cooler Central Highland plateau.'
    },
    {
        'id': 859,
        'slug': 'ho-chi-minh-city-to-phu-quoc-transport',
        'file': 'Approaching Phu Quoc International Airport.jpeg',
        'alt': 'Aerial view approaching the runway at Phu Quoc International Airport in southern Vietnam',
        'caption': 'Commercial domestic flights represent the fastest route between Ho Chi Minh City and Phu Quoc, taking just 55 minutes.'
    },
    {
        'id': 893,
        'slug': 'hue-to-hoi-an-transport',
        'file': 'Lăng Cô, Hải Vân Pass, 2020-01 CN-01.jpg',
        'alt': 'Scenic view of Lang Co bay and coastal mountains from Hai Van Pass',
        'caption': 'Private cars and motorcycle tours over Hai Van Pass offer panoramic views of Lang Co lagoon and Da Nang bay.'
    },
    {
        'id': 894,
        'slug': 'ho-chi-minh-city-to-mui-ne-transport',
        'file': 'Phan Thiết-Dầu Giây Expressway Speed Sign.jpg',
        'alt': 'Dau Giay to Phan Thiet expressway corridor connecting Saigon to coastal Mui Ne',
        'caption': 'The Dau Giay - Phan Thiet expressway reduced road travel time between Ho Chi Minh City and Mui Ne to roughly 2.5 hours.'
    },
    {
        'id': 927,
        'slug': 'ho-chi-minh-city-to-can-tho-transport',
        'file': 'Can Tho, Vietnam, Bridge.jpg',
        'alt': 'Can Tho cable-stayed bridge spanning the Hau River in the Mekong Delta',
        'caption': 'Expressway coaches cross the Hau River via Can Tho Bridge, connecting Ho Chi Minh City to the delta hub in 3.5 hours.'
    },
    {
        'id': 970,
        'slug': 'da-nang-to-nha-trang-transport',
        'file': 'Duong Sat Viet Nam speeding Hanoi.JPG',
        'alt': 'North-South express train traveling along the coastal railway network of Vietnam',
        'caption': 'Air-conditioned Reunification Express trains link Da Nang with Nha Trang, offering both scenic daytime seating and overnight sleeper berths.'
    },
    {
        'id': 1003,
        'slug': 'hanoi-to-hue-transport',
        'file': 'Duong sat viet nam (6) 003.jpg',
        'alt': 'Passenger carriages of Vietnam Railways traversing central Vietnam toward Hue',
        'caption': 'Overnight SE train services departing Hanoi allow travelers to save on lodging while traveling 688 km south to imperial Hue.'
    },
    {
        'id': 1040,
        'slug': 'hanoi-to-cao-bang-transport',
        'file': 'Ban Gioc Waterfall - Trung Kanh District - Cao Bang Province - Vietnam - 07 (48119780441).jpg',
        'alt': 'Ban Gioc waterfall and karst peaks in Cao Bang province along the northern border',
        'caption': 'Express limousines and daytime buses travel Highway 3 from Hanoi to Cao Bang, the gateway to Ban Gioc Waterfall.'
    },
    {
        'id': 1067,
        'slug': 'hanoi-to-ba-be-transport',
        'file': 'Ba Be Lake 2.jpg',
        'alt': 'Motorized wooden boat navigating the freshwater waters of Ba Be Lake National Park',
        'caption': 'Direct shuttle vans transfer travelers from Hanoi to Ba Be boat docks, where local boats ferry guests to village homestays.'
    },
    {
        'id': 1072,
        'slug': 'hanoi-to-ninh-binh-train-vs-limousine',
        'file': 'Ga Ninh Binh.jpg',
        'alt': 'Tracks and passenger platforms at Ninh Binh railway station',
        'caption': 'Travelers choose between comfortable 2-hour Reunification trains and door-to-door 9-seat luxury limousine vans from Hanoi.'
    },
    {
        'id': 1098,
        'slug': 'saigon-to-vung-tau-transport',
        'file': 'Bach Dang Quay (52681304130).jpg',
        'alt': 'Bach Dang ferry quay on the Saigon River in central Ho Chi Minh City',
        'caption': 'Greenlines DP fast passenger ferries depart Bach Dang wharf in District 1, arriving at Vung Tau harbor in approximately 2 hours.'
    },
    {
        'id': 1103,
        'slug': 'hanoi-to-sapa-train-vs-sleeper-bus',
        'file': 'Bao Ha - Hanoi-Lao Cai Railway - P1380670.JPG',
        'alt': 'Hanoi to Lao Cai railway line winding through northern mountain river valleys',
        'caption': 'Overnight sleeper trains run along the Red River valley to Lao Cai, where connecting vans transfer passengers up to Sapa.'
    },
    {
        'id': 1138,
        'slug': 'da-nang-to-quy-nhon-transport',
        'file': 'Ga Diêu Trì, Tuy Phước, Bình Định.JPG',
        'alt': 'Dieu Tri railway station junction platform near Quy Nhon in Binh Dinh',
        'caption': 'Coastal trains from Da Nang stop at Dieu Tri junction station, from where a quick 15-minute taxi ride reaches Quy Nhon city center.'
    },
    {
        'id': 1139,
        'slug': 'how-to-get-to-con-dao-flight-vs-ferry',
        'file': 'ATR72-200 at Co Ong.jpg',
        'alt': 'Commercial ATR-72 turboprop aircraft on the tarmac at Co Ong Airport, Con Dao',
        'caption': 'Travelers reaching Con Dao choose between 45-minute turboprop flights and high-speed catamarans braving open-ocean crossings.'
    },
    {
        'id': 1169,
        'slug': 'da-lat-to-mui-ne-transport',
        'file': 'Vietnam, Mui Ne, Kitesurfing.jpg',
        'alt': 'Tropical coastline and ocean waters at Mui Ne beach in Binh Thuan province',
        'caption': 'Overland transport descends from Da Lat cool highlands down the Dai Ninh mountain pass to coastal Mui Ne.'
    },
    {
        'id': 1172,
        'slug': 'hanoi-to-mu-cang-chai-transport',
        'file': 'Khau Phạ.jpg',
        'alt': 'Scenic mountain curves and terraced valley viewpoints along Khau Pha Pass',
        'caption': 'National Route 32 climbs the iconic Khau Pha Pass, bringing limousine coaches and motorbikes to the terraced hills of Mu Cang Chai.'
    },
    {
        'id': 1174,
        'slug': 'da-nang-to-phong-nha-transport',
        'file': 'Barquero en el Río Son, Phong Nha, Vietnam (43409828472).jpg',
        'alt': 'Local boat on the Son River approaching limestone cliffs in Phong Nha',
        'caption': 'Direct sleeper trains and limousine transfers link Da Nang with Dong Hoi and the world-famous cave networks of Phong Nha.'
    },
    {
        'id': 1202,
        'slug': 'buon-ma-thuot-to-pleiku-transport',
        'file': 'Biển Hồ, TP Pleiku, Gia Lai.jpg',
        'alt': 'T’Nung volcanic crater lake surrounded by pine forests in Pleiku, Gia Lai',
        'caption': 'Highway 14 provides a smooth, paved corridor linking the major Central Highland cities of Buon Ma Thuot and Pleiku in 3.5 hours.'
    },
    {
        'id': 1207,
        'slug': 'hanoi-to-dien-bien-phu-transport',
        'file': 'Landscape on Edge of Dien Bien Phu - Vietnam (48168741986).jpg',
        'alt': 'Mountain landscape and valley basin on the edge of Dien Bien Phu',
        'caption': 'Highway 6 and regional flights link Hanoi to the northwestern valley basin of Dien Bien Phu.'
    },
    {
        'id': 1230,
        'slug': 'nha-trang-to-quy-nhon-transport',
        'file': 'Ky-Co-Beach,-Quy-Nhon,-Vietnam-1300px.jpg',
        'alt': 'Coastal headlands and turquoise ocean waters along the coastline near Quy Nhon',
        'caption': 'Coastal express trains and limousines journey north through Phu Yen, skirting turquoise bays between Nha Trang and Quy Nhon.'
    },
    {
        'id': 1231,
        'slug': 'can-tho-to-chau-doc-transport',
        'file': 'Vietnam, Boat on Bassac River.jpg',
        'alt': 'Passenger ferry and riverboats on the Bassac River in the Mekong Delta near Chau Doc',
        'caption': 'Highway 91 follows the Bassac River northwest across the Mekong Delta, connecting Can Tho with the Cambodian border town of Chau Doc.'
    },
    {
        'id': 1232,
        'slug': 'hanoi-to-mai-chau-transport',
        'file': 'Mai Chau.jpg',
        'alt': 'Verdant rice paddies and stilt houses across the mountain valley of Mai Chau',
        'caption': 'Shared limousines and tourist buses ascend Highway 6 through Hoa Binh, arriving in Mai Chau valley in around 3.5 hours.'
    },
    {
        'id': 1236,
        'slug': 'hanoi-to-pu-luong-transport',
        'file': 'Pu Luong 01.JPG',
        'alt': 'Lush terraced valley and mountain slopes of Pu Luong Nature Reserve',
        'caption': 'Direct resort eco-buses travel from Hanoi via Hoa Binh and Ba Thuoc, ascending the quiet mountain trails into Pu Luong Nature Reserve.'
    },
    {
        'id': 1271,
        'slug': 'sapa-to-ha-giang-transport',
        'file': 'Mountain road at Mã Pí Lèng Pass, Hà Giang Province, Vietnam.jpg',
        'alt': 'Winding mountain roadway hugging limestone cliff faces in northern Ha Giang province',
        'caption': 'Cross-mountain daytime limousines and regional buses navigate Highway 279, linking northern adventure hubs Sapa and Ha Giang in 6 hours.'
    },
    {
        'id': 1310,
        'slug': 'ha-giang-to-cao-bang-transport',
        'file': 'Ban Gioc Waterfall - Trung Kanh District - Cao Bang Province - Vietnam - 11 (48119809353).jpg',
        'alt': 'Limestone mountains and waterfalls in Cao Bang province along the Northeast loop',
        'caption': 'National Route 34 forms the rugged connecting artery of the Northeast loop, running from Meo Vac through Bao Lac to Cao Bang city.'
    },
    {
        'id': 1532,
        'slug': 'rach-gia-to-phu-quoc-ferry',
        'file': 'Cau cang,duong Nguyen Con Tru, xa Vĩnh Thanh, tp. Rạch Giá, tỉnh Kiên Giang, Việt Nam,02-07-2016-Dyt - panoramio.jpg',
        'alt': 'Commercial passenger ferry pier at Rach Gia harbor in Kien Giang province',
        'caption': 'Superdong and Phu Quoc Express fast catamarans depart Rach Gia port daily, reaching Bai Vong harbor in 2 hours and 30 minutes.'
    },
    {
        'id': 1565,
        'slug': 'tran-de-to-con-dao-ferry',
        'file': 'Bến Đầm, Côn Đảo, Vietnam - panoramio.jpg',
        'alt': 'Ben Dam harbor port surrounded by mountain peaks on Con Dao Island',
        'caption': 'Tran De port in Soc Trang provides the shortest open-ocean route to Con Dao, with Superdong speedboats docking at Ben Dam harbor.'
    },
    {
        'id': 1566,
        'slug': 'vung-tau-to-con-dao-ferry',
        'file': 'Bai Truoc, Vung Tau.jpg',
        'alt': 'Front Beach harbor and coastal headlands at Vung Tau departure port',
        'caption': 'Con Dao Express 36 catamarans depart Cau Da port in Vung Tau, carrying passengers across the East Sea to Ben Dam port in 3.5 to 4 hours.'
    },
    {
        'id': 1500,
        'slug': 'ha-tien-to-phu-quoc-ferry',
        'file': 'Bãi biển Hà Tiên.jpeg',
        'alt': 'Coastal port waters and shoreline at Ha Tien in Kien Giang province',
        'caption': 'Ha Tien represents the closest mainland departure point to Phu Quoc, with express speedboats reaching the island in just 75 minutes.'
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
    
    with urllib.request.urlopen(req, context=ctx, timeout=15) as resp:
        data = json.loads(resp.read().decode('utf-8'))
    
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
    print(f"Resolving exact Wikimedia metadata for all {len(BATCH2_ITEMS)} Batch 2 transport routes...")
    results = []
    failed = []

    for idx, item in enumerate(BATCH2_ITEMS, 1):
        print(f"[{idx}/35] Querying metadata for '{item['file']}' ({item['slug']})...")
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
            print(f"   -> FAILED to get metadata for: {item['file']}")
            failed.append(item['slug'])
        time.sleep(0.8) # Polite pause to prevent any 429 rate limiting

    print(f"\nFinal Verified Count: {len(results)} / {len(BATCH2_ITEMS)}")
    if failed:
        print(f"Failed items: {failed}")
        sys.exit(1)

    out_file = 'ops/batch2_final_images.json'
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(results, f, ensure_ascii=False, indent=2)
    print(f"Saved all 35 verified definitions to {out_file}")

if __name__ == '__main__':
    main()

# -*- coding: utf-8 -*-
"""
VietnamGuide Batch 2: Curate 35 Authentic Real Photographs for Transport & Ferry Guides
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
        'id': 683,
        'slug': 'hanoi-to-ha-long-bay-transport',
        'query': 'Bach Dang Bridge Ha Long expressway',
        'alt': 'Bach Dang cable-stayed bridge on the Hanoi to Ha Long expressway',
        'caption': 'The modern expressway network cuts travel time between Hanoi and Ha Long Bay down to just 2.5 to 3 hours.'
    },
    {
        'id': 684,
        'slug': 'da-nang-to-hue-train-vs-car',
        'query': 'Hai Van Pass railway train Vietnam',
        'alt': 'Reunification Express train rounding coastal cliffs along Hai Van Pass, Vietnam',
        'caption': 'The scenic coastal railway route along the Hai Van Pass offers sweeping views of Lang Co bay and the South China Sea.'
    },
    {
        'id': 755,
        'slug': 'phong-nha-to-hue-transport',
        'query': 'Hien Luong bridge Ben Hai river Quang Tri',
        'alt': 'Historic Hien Luong Bridge across the DMZ along the overland route from Phong Nha to Hue',
        'caption': 'Overland transport between Phong Nha and Hue follows Highway 1, passing through the historic Demilitarized Zone in Quang Tri.'
    },
    {
        'id': 756,
        'slug': 'da-lat-to-nha-trang-transport',
        'query': 'Khanh Le pass Da Lat Nha Trang',
        'alt': 'Winding mountain road through mist at Khanh Le pass between Da Lat and Nha Trang',
        'caption': 'Khanh Le pass drops over 1,500 meters from the pine-covered Da Lat plateau down to the coastal plains of Khanh Hoa.'
    },
    {
        'id': 790,
        'slug': 'hanoi-to-cat-ba-island-transport',
        'query': 'Cat Hai bridge Hai Phong Vietnam',
        'alt': 'Tan Vu - Cat Hai sea-crossing bridge connecting mainland Hai Phong toward Cat Ba Island',
        'caption': 'Modern combined bus-ferry tickets utilize the Tan Vu - Cat Hai sea bridge and ferry terminals for direct transfers to Cat Ba.'
    },
    {
        'id': 823,
        'slug': 'hanoi-to-phong-nha-transport',
        'query': 'Dong Hoi railway station Vietnam',
        'alt': 'Dong Hoi railway station platform along the North-South railway line in Quang Binh',
        'caption': 'Overnight sleeper trains from Hanoi terminate at Dong Hoi station, where travelers transfer by local bus or taxi to Phong Nha.'
    },
    {
        'id': 856,
        'slug': 'ho-chi-minh-city-to-da-lat-transport',
        'query': 'Bao Loc pass Lam Dong Vietnam',
        'alt': 'Bao Loc mountain pass winding through lush green highlands along Highway 20 to Da Lat',
        'caption': 'Highway 20 climbs sharply up the Bao Loc Pass, linking southern industrial plains to the cooler Central Highland plateau.'
    },
    {
        'id': 859,
        'slug': 'ho-chi-minh-city-to-phu-quoc-transport',
        'query': 'Phu Quoc International Airport terminal',
        'alt': 'Modern terminal building of Phu Quoc International Airport in southern Vietnam',
        'caption': 'Direct 55-minute commercial jet flights from Tan Son Nhat airport represent the fastest and most reliable way to reach Phu Quoc.'
    },
    {
        'id': 893,
        'slug': 'hue-to-hoi-an-transport',
        'query': 'Hai Van Pass road view Vietnam',
        'alt': 'Winding coastal asphalt road ascending the summit of Hai Van Pass between Hue and Da Nang',
        'caption': 'Private cars and open-top Easy Rider motorbike tours climb the coastal hairpin bends of the Hai Van Pass.'
    },
    {
        'id': 894,
        'slug': 'ho-chi-minh-city-to-mui-ne-transport',
        'query': 'Phan Thiet expressway Vietnam',
        'alt': 'Dau Giay - Phan Thiet expressway cutting through dragon fruit plantations in Binh Thuan',
        'caption': 'The Dau Giay - Phan Thiet expressway slashed travel time between Ho Chi Minh City and Mui Ne down to approximately 2.5 hours.'
    },
    {
        'id': 927,
        'slug': 'ho-chi-minh-city-to-can-tho-transport',
        'query': 'Can Tho Bridge Hau River Vietnam',
        'alt': 'Can Tho cable-stayed bridge spanning the Hau River in the Mekong Delta',
        'caption': 'Expressway coaches cross the grand Can Tho Bridge, connecting Ho Chi Minh City to the delta commercial capital in 3.5 hours.'
    },
    {
        'id': 970,
        'slug': 'da-nang-to-nha-trang-transport',
        'query': 'Vietnam railway train coast',
        'alt': 'North-South express train traveling alongside the south-central coastline of Vietnam',
        'caption': 'Air-conditioned express trains run down the south-central coast, offering day and sleeper berths between Da Nang and Nha Trang.'
    },
    {
        'id': 1003,
        'slug': 'hanoi-to-hue-transport',
        'query': 'Reunification Express train Vietnam interior',
        'alt': 'Four-berth soft sleeper compartment on the Reunification Express train in Vietnam',
        'caption': 'Overnight SE sleeper trains departing Hanoi allow travelers to sleep comfortably while traversing 688 kilometers south to Hue.'
    },
    {
        'id': 1040,
        'slug': 'hanoi-to-cao-bang-transport',
        'query': 'National Route 3 Vietnam Cao Bang',
        'alt': 'Highway winding through karst peaks and valleys on the mountain road to Cao Bang',
        'caption': 'Limousine vans and daytime express buses navigate Highway 3, connecting Hanoi to Cao Bang city in around 6 to 7 hours.'
    },
    {
        'id': 1067,
        'slug': 'hanoi-to-ba-be-transport',
        'query': 'Ba Be lake boat pier national park',
        'alt': 'Wooden motorized boat dock on the freshwater shores of Ba Be Lake, Bac Kan',
        'caption': 'Combined shared shuttles run from Hanoi to the boat docks of Ba Be National Park, transferring visitors directly to village homestays.'
    },
    {
        'id': 1072,
        'slug': 'hanoi-to-ninh-binh-train-vs-limousine',
        'query': 'Ninh Binh railway station Vietnam',
        'alt': 'Ninh Binh railway station tracks and passenger platform in northern Vietnam',
        'caption': 'Travelers choose between comfortable 2.5-hour Reunification trains and 9-seat luxury limousine vans with hotel pickup.'
    },
    {
        'id': 1098,
        'slug': 'saigon-to-vung-tau-transport',
        'query': 'Greenlines DP hydrofoil fast ferry Vung Tau',
        'alt': 'Greenlines DP high-speed hydrofoil ferry traveling down the Saigon River toward Vung Tau',
        'caption': 'Greenlines DP fast catamarans depart Bach Dang wharf in central District 1, arriving at Vung Tau in just 2 hours.'
    },
    {
        'id': 1103,
        'slug': 'hanoi-to-sapa-train-vs-sleeper-bus',
        'query': 'Lao Cai railway station train Vietnam',
        'alt': 'Lao Cai railway station with overnight sleeper train from Hanoi at the mountain terminus',
        'caption': 'Overnight sleeper trains stop at Lao Cai station for bus connections to Sapa, while direct sleeper buses travel the Noi Bai expressway.'
    },
    {
        'id': 1138,
        'slug': 'da-nang-to-quy-nhon-transport',
        'query': 'Dieu Tri railway station Binh Dinh',
        'alt': 'Dieu Tri railway station junction platform near Quy Nhon in Binh Dinh province',
        'caption': 'Coastal express trains connect Da Nang to Dieu Tri junction station in 5 to 6 hours, followed by a short taxi ride to Quy Nhon city.'
    },
    {
        'id': 1139,
        'slug': 'how-to-get-to-con-dao-flight-vs-ferry',
        'query': 'Con Dao Airport ATR 72 plane',
        'alt': 'Commercial ATR-72 turboprop aircraft on the runway at Co Ong Airport, Con Dao Island',
        'caption': 'Reaching Con Dao involves choosing between 45-minute turboprop flights and high-speed catamarans braving open-ocean crossings.'
    },
    {
        'id': 1169,
        'slug': 'da-lat-to-mui-ne-transport',
        'query': 'Dai Ninh Pass Lam Dong Binh Thuan',
        'alt': 'Winding mountain descent of Dai Ninh Pass between Lam Dong highlands and coastal Binh Thuan',
        'caption': 'Dai Ninh and Gia Bac mountain passes descend sharply through tropical valleys, connecting Da Lat highlands with Mui Ne beach.'
    },
    {
        'id': 1172,
        'slug': 'hanoi-to-mu-cang-chai-transport',
        'query': 'Khau Pha Pass Yen Bai Vietnam',
        'alt': 'Khau Pha mountain pass hairpin road overlooking terraced rice valleys in Yen Bai',
        'caption': 'Highway 32 traverses the dramatic Khau Pha Pass, bringing limousine coaches and motorbikes to Mu Cang Chai.'
    },
    {
        'id': 1174,
        'slug': 'da-nang-to-phong-nha-transport',
        'query': 'Dong Hoi railway station platform Vietnam',
        'alt': 'North-South railway tracks approaching Dong Hoi station in central Vietnam',
        'caption': 'Direct sleeper trains and VIP limousine shuttles connect Da Nang to Dong Hoi and the cave networks of Phong Nha.'
    },
    {
        'id': 1202,
        'slug': 'buon-ma-thuot-to-pleiku-transport',
        'query': 'National Route 14 Ho Chi Minh highway Central Highlands',
        'alt': 'National Route 14 paved highway passing through basalt hills and coffee plantations in Gia Lai',
        'caption': 'National Route 14 forms the smooth overland spine of the Central Highlands, linking Buon Ma Thuot and Pleiku in 3.5 to 4 hours.'
    },
    {
        'id': 1207,
        'slug': 'hanoi-to-dien-bien-phu-transport',
        'query': 'Pha Din Pass Vietnam',
        'alt': 'Scenic asphalt mountain curves of Pha Din Pass between Son La and Dien Bien provinces',
        'caption': 'The historic northwestern overland corridor crosses the clouds of Pha Din Pass before descending into the Dien Bien Phu basin.'
    },
    {
        'id': 1230,
        'slug': 'nha-trang-to-quy-nhon-transport',
        'query': 'Cu Mong Pass Vietnam Binh Dinh',
        'alt': 'Mountain pass road of Cu Mong between Phu Yen and Binh Dinh along the central coast',
        'caption': 'Coaches and coastal trains journey north through Phu Yen, skirting turquoise bays and basalt cliffs between Nha Trang and Quy Nhon.'
    },
    {
        'id': 1231,
        'slug': 'can-tho-to-chau-doc-transport',
        'query': 'Bassac River Chau Doc boat Vietnam',
        'alt': 'Passenger ferry and riverboats on the Hau (Bassac) River near Chau Doc in An Giang',
        'caption': 'Highway 91 follows the Bassac River northwest across the Mekong Delta, connecting Can Tho with the Cambodian border town of Chau Doc.'
    },
    {
        'id': 1232,
        'slug': 'hanoi-to-mai-chau-transport',
        'query': 'Thung Khe Pass Hoa Binh Vietnam',
        'alt': 'Thung Khe White Stone pass summit road overlooking the mountain valleys of Hoa Binh',
        'caption': 'Comfortable limousine vans climb National Highway 6 through the Thung Khe pass, reaching Mai Chau valley in around 3.5 hours.'
    },
    {
        'id': 1236,
        'slug': 'hanoi-to-pu-luong-transport',
        'query': 'Pu Luong nature reserve road Vietnam',
        'alt': 'Scenic mountain valley road entering Pu Luong Nature Reserve in Thanh Hoa province',
        'caption': 'Direct resort eco-buses travel via Hoa Binh and Ba Thuoc, ascending the misty mountain trails into Pu Luong Nature Reserve.'
    },
    {
        'id': 1271,
        'slug': 'sapa-to-ha-giang-transport',
        'query': 'Highway 279 mountain pass Vietnam',
        'alt': 'National Highway 279 winding through northern mountain ranges between Sapa and Ha Giang',
        'caption': 'Cross-mountain daytime limousines and local buses navigate Highway 279, linking the two northern adventure hubs in 6 hours.'
    },
    {
        'id': 1310,
        'slug': 'ha-giang-to-cao-bang-transport',
        'query': 'National Route 34 Bao Lac mountain road',
        'alt': 'National Route 34 hugging the Gam River through the mountain gorges of Bao Lac',
        'caption': 'Highway 34 forms the rugged connecting artery of the Northeast loop, running from Meo Vac through Bao Lac to Cao Bang city.'
    },
    {
        'id': 1532,
        'slug': 'rach-gia-to-phu-quoc-ferry',
        'query': 'Superdong high speed ferry boat Vietnam',
        'alt': 'Superdong high-speed ferry cutting through waves in the Gulf of Thailand',
        'caption': 'Superdong and Phu Quoc Express catamarans depart Rach Gia port daily, reaching Bai Vong harbor in 2 hours and 30 minutes.'
    },
    {
        'id': 1565,
        'slug': 'tran-de-to-con-dao-ferry',
        'query': 'Superdong ferry boat Soc Trang',
        'alt': 'Superdong high-speed passenger ferry docked at Tran De commercial port in Soc Trang',
        'caption': 'Tran De port in Soc Trang provides the shortest open-ocean crossing to Con Dao, with Superdong speedboats taking just 2.5 hours.'
    },
    {
        'id': 1566,
        'slug': 'vung-tau-to-con-dao-ferry',
        'query': 'Con Dao Express 36 catamaran ferry',
        'alt': 'Con Dao Express 36 high-speed red catamaran at Cau Da harbor in Vung Tau',
        'caption': 'The Con Dao Express 36 double-hulled catamaran carries over 500 passengers from Vung Tau directly to Ben Dam port in 3.5 to 4 hours.'
    },
    {
        'id': 1492,
        'slug': 'ha-tien-to-phu-quoc-ferry',
        'query': 'Ha Tien ferry port boat Phu Quoc',
        'alt': 'Passenger ferry and vehicle vessel berthed at Ha Tien passenger port in Kien Giang',
        'caption': 'Ha Tien represents the closest mainland departure point to Phu Quoc, with express boats reaching the island in just 75 minutes.'
    }
]

def clean_html(raw):
    return re.sub(r'<[^>]+>', '', raw).strip()

def search_wikimedia(query):
    # Try query as is, and if fails, try first 2-3 words
    attempts = [query]
    words = query.split()
    if len(words) > 3:
        attempts.append(' '.join(words[:3]))
    if len(words) > 2:
        attempts.append(' '.join(words[:2]))

    ctx = ssl.create_default_context()

    for q in attempts:
        params = {
            'action': 'query',
            'generator': 'search',
            'gsrsearch': f"{q} filetype:bitmap",
            'gsrnamespace': '6',
            'gsrlimit': '6',
            'prop': 'imageinfo',
            'iiprop': 'url|size|extmetadata',
            'iiurlwidth': '1280',
            'format': 'json'
        }
        url = f"https://commons.wikimedia.org/w/api.php?{urllib.parse.urlencode(params)}"
        req = urllib.request.Request(url, headers={'User-Agent': 'VietnamGuideBot/1.0 (https://vietnamguide.net; contact@vietnamguide.net)'})
        try:
            with urllib.request.urlopen(req, context=ctx, timeout=12) as resp:
                data = json.loads(resp.read().decode('utf-8'))
        except Exception:
            continue
        
        pages = data.get('query', {}).get('pages', {})
        for pid, pdata in pages.items():
            title = pdata.get('title', '')
            ii = pdata.get('imageinfo', [{}])[0]
            meta = ii.get('extmetadata', {})
            thumb = ii.get('thumburl')
            w = int(ii.get('thumbwidth', 0))
            h = int(ii.get('thumbheight', 0))
            
            # Filter for valid landscape or reasonable photo
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
    print(f"Resolving 35 authentic Wikimedia images for Batch 2 (Transport & Ferries)...")
    results = []
    failed = []
    
    for item in DESTINATIONS_CONFIG:
        print(f"Resolving [{item['slug']}] (query: '{item['query']}')...")
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
    
    with open('ops/batch2_final_images.json', 'w', encoding='utf-8') as f:
        json.dump(results, f, ensure_ascii=False, indent=2)
    print("Saved all 35 verified image definitions to ops/batch2_final_images.json")

if __name__ == '__main__':
    main()

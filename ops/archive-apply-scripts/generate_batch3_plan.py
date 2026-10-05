# -*- coding: utf-8 -*-
"""
VietnamGuide Batch 3: Generate Verified Plan for 30 Food, Sights & Culture Guides
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

BATCH3_ITEMS = [
    {
        'id': 614,
        'slug': 'hanoi-street-food-guide',
        'file': 'Bun cha Hanoi.jpg',
        'alt': 'Traditional Hanoi bun cha grilled pork patties and rice vermicelli noodle bowl with fresh herbs',
        'caption': 'A quintessential Hanoi street food staple featuring charcoal-grilled pork meatballs in a light nuoc cham broth alongside fresh rice noodles.'
    },
    {
        'id': 615,
        'slug': 'saigon-street-food-guide',
        'file': 'Banh mi thit Nhu Lan.jpg',
        'alt': 'Crusty Saigon banh mi baguette stuffed with pate, cold cuts, pickled daikon and fresh coriander',
        'caption': 'Saigon banh mi combines French-influenced airy baguettes with rich liver pate, savory pork rolls, and crisp pickled vegetables.'
    },
    {
        'id': 616,
        'slug': 'da-nang-street-food-guide',
        'file': 'Mì Quảng, Da Nang, Vietnam.jpg',
        'alt': 'Authentic bowl of Mi Quang noodles with pork, shrimp, roasted peanuts, and rice crackers in Da Nang',
        'caption': 'Central Vietnam’s signature Mi Quang features wide rice noodles in a rich shallow turmeric broth topped with toasted sesame crackers.'
    },
    {
        'id': 628,
        'slug': 'hang-mua-ninh-binh-guide',
        'file': 'View from Hang Mua.jpg',
        'alt': 'Panoramic limestone karst valley and winding Ngo Dong river viewed from the summit of Hang Mua',
        'caption': 'Climbing the 500 stone steps to Mua Cave’s dragon peak rewards travelers with 360-degree vistas across Tam Coc valley.'
    },
    {
        'id': 629,
        'slug': 'phong-nha-cave-treks',
        'file': 'Paradise cave.JPG',
        'alt': 'Vast underground cavern and illuminated stalactites inside Paradise Cave in Phong Nha',
        'caption': 'Phong Nha-Ke Bang National Park holds some of the world’s most immense subterranean limestone chambers and multi-day cave trekking routes.'
    },
    {
        'id': 649,
        'slug': 'vietnam-coffee-guide',
        'file': 'Egg Coffee (6923068614).jpg',
        'alt': 'Hot cup of creamy Hanoi egg coffee with whipped egg yolks and condensed milk foam',
        'caption': 'Invented during milk shortages in 1946 Hanoi, ca phe trung whips chicken egg yolks with condensed milk over robusta espresso.'
    },
    {
        'id': 715,
        'slug': 'saigon-night-markets-guide',
        'file': 'Ben Thanh market at night.JPG',
        'alt': 'Historic Ben Thanh market clock tower and bustling illuminated street food stalls at dusk',
        'caption': 'As evening falls around Ben Thanh Market, peripheral streets transform into vibrant outdoor night markets with wok-fried seafood and fruit stalls.'
    },
    {
        'id': 716,
        'slug': 'hoi-an-street-food-guide',
        'file': 'Cao lầu Hội An (2024).jpg',
        'alt': 'Traditional bowl of Cao Lau noodles with char siu pork and greens in Hoi An ancient town',
        'caption': 'Authentic Cao Lau noodles require water drawn from ancient Cham wells in Hoi An, resulting in their distinct chewy texture.'
    },
    {
        'id': 717,
        'slug': 'tam-coc-vs-trang-an-boat-tour',
        'file': 'Boat in Tam Coc - August 2023.jpg',
        'alt': 'Local sampan rowing boat gliding through limestone karst tunnels in Tam Coc Ninh Binh',
        'caption': 'Rowers navigate traditional metal sampans through dramatic karst water caves surrounded by seasonal emerald rice paddies.'
    },
    {
        'id': 718,
        'slug': 'sapa-trekking-routes-guide',
        'file': 'Ta Van Muong Ha vallei (56941).jpg',
        'alt': 'Trekking trails winding through layered rice terraces in Muong Hoa valley near Ta Van village Sapa',
        'caption': 'Hiking trails descend from Sapa town through Muong Hoa valley, connecting travelers with Black Hmong and Giay ethnic homestays.'
    },
    {
        'id': 720,
        'slug': 'cham-islands-day-trip-guide',
        'file': 'ChamIslands1.jpg',
        'alt': 'Turquoise bay waters and lush green granite hills of Cham Islands offshore from Hoi An',
        'caption': 'Cu Lao Cham UNESCO biosphere reserve offers white-sand coves, snorkeling reefs, and fishing village day trips from Cua Dai pier.'
    },
    {
        'id': 751,
        'slug': 'phu-quoc-beaches-guide',
        'file': 'Bai Sao Beach.jpg',
        'alt': 'Powdery white sand and gentle turquoise waves at Bai Sao beach on Phu Quoc Island',
        'caption': 'Bai Sao on Phu Quoc’s southeastern coast is famed for soft white sand and sheltered, crystal-clear tropical waters.'
    },
    {
        'id': 753,
        'slug': 'da-lat-waterfalls-guide',
        'file': 'Pongour Falls (6226122698).jpg',
        'alt': 'Multi-tiered cascading rock terraces of Pongour Waterfall near Da Lat in the Central Highlands',
        'caption': 'Pongour Falls plunges over seven stepped natural amphitheater terraces surrounded by virgin Central Highland pine forests.'
    },
    {
        'id': 786,
        'slug': 'hanoi-train-street-guide',
        'file': 'Train street in Hanoi.jpg',
        'alt': 'Narrow residential railway corridor and cafe tables along the tracks of Hanoi Train Street',
        'caption': 'National railway lines squeeze tightly through narrow residential alleyways in Hanoi, where trackside cafes seat curious travelers.'
    },
    {
        'id': 787,
        'slug': 'cu-chi-tunnels-ben-duoc-vs-ben-dinh',
        'file': 'Củ Chi tunnels entrance.JPG',
        'alt': 'Concealed trapdoor entrance hidden beneath fallen leaves in the Cu Chi tunnel network',
        'caption': 'The vast subterranean Cu Chi tunnel complex features camouflaged trapdoors, underground kitchens, and historic command bunkers.'
    },
    {
        'id': 788,
        'slug': 'ba-na-hills-golden-bridge-guide',
        'file': 'Golden Bridge at Ba Na Hills 20250718.jpg',
        'alt': 'Golden Bridge pedestrian walkway supported by giant weathered stone hands at Ba Na Hills Da Nang',
        'caption': 'Perched 1,400 meters above sea level, the iconic Golden Bridge appears cradled aloft by colossal moss-clad sculptured hands.'
    },
    {
        'id': 791,
        'slug': 'hue-street-food-guide',
        'file': 'Bún bò Huế minh28397.jpg',
        'alt': 'Steaming bowl of spicy Bun Bo Hue beef noodle soup with lemongrass and chili oil',
        'caption': 'Imperial Hue’s culinary crown jewel balances slow-simmered beef shank, spicy chili lemongrass oil, and fermented shrimp paste.'
    },
    {
        'id': 822,
        'slug': 'mekong-delta-floating-markets-guide',
        'file': 'Cai Rang Floating Market 1.jpg',
        'alt': 'Wooden merchant boats loaded with watermelons and pineapples at Cai Rang floating market Can Tho',
        'caption': 'Wholesale fruit traders display their cargo aloft on bamboo poles across the morning waterways of Cai Rang floating market.'
    },
    {
        'id': 824,
        'slug': 'da-lat-coffee-farms-guide',
        'file': 'Vietnam - coffee plantation.jpg',
        'alt': 'Highland Arabica coffee trees bearing ripe red coffee cherries on a farm in Vietnam',
        'caption': 'Volcanic red basalt soil and cool mountain altitude at 1,500m make Da Lat Vietnam’s premier producer of specialty Arabica coffee.'
    },
    {
        'id': 825,
        'slug': 'hoi-an-tailoring-guide',
        'file': 'Ants Silk Tailoring shop of Hoi An in 2015.jpg',
        'alt': 'Colorful silk fabric bolts and tailored mannequins inside a Hoi An bespoke garment atelier',
        'caption': 'Hoi An’s master tailors craft bespoke linen suits, dresses, and winter coats within 24 to 48 hours from fine local silks.'
    },
    {
        'id': 855,
        'slug': 'best-things-to-do-in-ho-chi-minh-city',
        'file': 'Ho Chi Minh City, Central Post Office, 2020-01 CN-01.jpg',
        'alt': 'French colonial facade and arched entrance of Saigon Central Post Office in District 1',
        'caption': 'Designed with arched vaulted ironwork, the historic 19th-century Saigon Central Post Office stands opposite Notre-Dame Cathedral.'
    },
    {
        'id': 858,
        'slug': 'best-things-to-do-in-da-nang',
        'file': 'Da Nang Dragon Bridge.jpg',
        'alt': 'Golden Dragon Bridge spanning the Han River in central Da Nang',
        'caption': 'Spanning 666 meters across the Han River, Da Nang’s Dragon Bridge breathes fire and spouts water on weekend evenings.'
    },
    {
        'id': 889,
        'slug': 'best-things-to-do-in-da-lat',
        'file': 'Da Lat - Xuan Huong Lake.jpg',
        'alt': 'Tranquil waters and shoreline gardens of crescent-shaped Xuan Huong Lake in central Da Lat',
        'caption': 'Crescent-shaped Xuan Huong Lake forms the scenic heart of Da Lat, ringed by weeping willows, flower gardens, and walking promenades.'
    },
    {
        'id': 890,
        'slug': 'best-things-to-do-in-nha-trang',
        'file': 'Po Nagar Cham Towers façade (14636082242).jpg',
        'alt': 'Ancient terracotta brick facade and carved pillars of Po Nagar Cham Towers overlooking Nha Trang river',
        'caption': 'Dating back to the 8th century, Po Nagar Cham Towers honor the goddess Yan Po Nagar on a granite hill overlooking Cai River.'
    },
    {
        'id': 891,
        'slug': 'best-things-to-do-in-phu-quoc',
        'file': 'An Thoi fishing harbour Sunset Town Sun World Phu Quoc Vietnam.jpg',
        'alt': 'Sunset views across An Thoi harbor and sea-crossing cable car towers in southern Phu Quoc',
        'caption': 'Southern Phu Quoc hosts the world’s longest non-stop three-wire sea cable car, connecting An Thoi with Hon Thom island.'
    },
    {
        'id': 921,
        'slug': 'best-things-to-do-in-sapa',
        'file': 'Fansipan Cable Car peak station.jpg',
        'alt': 'Mountain summit complex and Buddhist temple shrines at Fansipan peak in Sapa',
        'caption': 'Known as the Roof of Indochina at 3,143m, Fansipan peak is accessible via scenic mountain railway and panoramic cable car.'
    },
    {
        'id': 923,
        'slug': 'best-things-to-do-in-ha-long-bay',
        'file': 'Cruising Ha Long Bay (25415940037).jpg',
        'alt': 'Traditional cruise boats navigating emerald waters between limestone pillars in Ha Long Bay',
        'caption': 'Over 1,600 limestone karst islands rise out of Ha Long Bay, making multi-day boat cruising an iconic Vietnam travel experience.'
    },
    {
        'id': 1039,
        'slug': 'vietnam-vegetarian-travel-guide',
        'file': 'Vietnamese vegetarian crab noodle soup.jpg',
        'alt': 'Artfully prepared bowl of Vietnamese vegetarian noodle soup with tofu, mushrooms, and herbs',
        'caption': 'Buddhist culinary traditions ensure that "quan chay" (vegetarian eateries) serve rich plant-based broths, tofu, and fresh herbs nationwide.'
    },
    {
        'id': 1071,
        'slug': 'vietnam-gluten-free-travel-guide',
        'file': 'Spring rolls with fish sauce for dipping.jpg',
        'alt': 'Fresh Vietnamese translucent rice paper spring rolls packed with herbs, prawns, and rice vermicelli',
        'caption': 'Vietnam’s widespread use of pure rice paper, pho noodles, and fresh greens makes naturally gluten-free dining accessible.'
    },
    {
        'id': 1102,
        'slug': 'vietnam-craft-beer-guide',
        'file': 'Tour of Pasteur Street Brewing Company, Vietnam.jpg',
        'alt': 'Stainless steel brewing fermenter tanks and taproom bar at Pasteur Street Brewing in Vietnam',
        'caption': 'Vietnam boasts Southeast Asia’s most dynamic craft beer scene, blending American brewing techniques with local jasmine and passionfruit.'
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
    print(f"Resolving exact Wikimedia metadata for all {len(BATCH3_ITEMS)} Batch 3 guides...")
    results = []
    failed = []

    for idx, item in enumerate(BATCH3_ITEMS, 1):
        print(f"[{idx}/{len(BATCH3_ITEMS)}] Resolving '{item['file']}' ({item['slug']})...")
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

    print(f"\nFinal Verified Count: {len(results)} / {len(BATCH3_ITEMS)}")
    if failed:
        print(f"Failed items: {failed}")
        sys.exit(1)

    out_file = 'ops/batch3_final_images.json'
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(results, f, ensure_ascii=False, indent=2)
    print(f"Saved all 30 verified definitions to {out_file}")

if __name__ == '__main__':
    main()

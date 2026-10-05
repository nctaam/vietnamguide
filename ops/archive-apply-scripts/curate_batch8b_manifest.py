# -*- coding: utf-8 -*-
"""
VietnamGuide: Curate Batch 8B Manifest (32 Posts, IDs 14 to 250)
Resolves duplicates, ensures distinct authentic photos, downloads metadata,
and prepares sideloading + zero-CLS figure replacement.
"""
import urllib.request
import urllib.parse
import json
import ssl
import re
import sys
import time

sys.stdout.reconfigure(encoding='utf-8')

# High quality curation mapping for the 32 posts of Batch 8B
BATCH_8B_TARGETS = [
    {
        'id': 14,
        'slug': 'best-time-to-visit-vietnam',
        'file': 'File:Halong Bay 2019-10-27 (4).jpg',
        'alt': "Clear sunny weather over the limestone karsts of Halong Bay in autumn, Vietnam",
        'caption': "Vietnam's diverse regional climates mean the best time to visit varies: spring (March–April) and autumn (September–November) offer the most pleasant weather nationwide."
    },
    {
        'id': 15,
        'slug': 'sim-esim-vietnam',
        'file': 'File:Viettel Store on Nguyen Van Cu Street, Hanoi 01.jpg',
        'alt': "Viettel official retail store along an urban street in Hanoi, Vietnam",
        'caption': "Acquiring a local SIM or eSIM from Viettel or Vinaphone ensures reliable high-speed data coverage across remote mountainous and coastal regions of Vietnam."
    },
    {
        'id': 19,
        'slug': '10-days-in-vietnam',
        'file': 'File:Hoi An ancient town at dusk.jpg',
        'alt': "Illuminated lanterns reflecting on the Thu Bon River in Hoi An ancient town, Vietnam",
        'caption': "A classic 10-day Vietnam route connects Hanoi's historic quarters, a Halong Bay cruise, and Hoi An's UNESCO heritage riverside."
    },
    {
        'id': 20,
        'slug': '14-days-in-vietnam',
        'file': 'File:Trang An Landscape Complex, Ninh Binh Province, Vietnam, 20240202 1435 5283.jpg',
        'alt': "Sampan boats navigating through the limestone mountains of Trang An in Ninh Binh, Vietnam",
        'caption': "A 14-day two-week itinerary adds Ninh Binh and Hue to the classic trail, giving travelers balanced exposure to heritage, karst landscapes, and coastal cuisine."
    },
    {
        'id': 21,
        'slug': 'ha-long-bay-vs-lan-ha-bay',
        'file': 'File:Ha Long Bay, Vietnam, View from above.jpg',
        'alt': "Panoramic aerial view of emerald waters and limestone islets in Ha Long Bay, Vietnam",
        'caption': "Comparing Ha Long Bay and Lan Ha Bay comes down to route style: Ha Long features dramatic grand vistas and large cruise operations, while Lan Ha offers quieter kayaking waters."
    },
    {
        'id': 22,
        'slug': 'vietnam-travel-cost',
        'file': 'File:Vietnam dong currency banknotes.jpg',
        'alt': "Spread of Vietnamese polymer dong banknotes in different denominations",
        'caption': "Vietnam remains an exceptional-value destination in Southeast Asia, with daily budgets ranging from $35–$50 USD for budget travelers to $150+ USD for luxury resort stays."
    },
    {
        'id': 98,
        'slug': 'vietnam-travel-guide',
        'file': 'File:Hanoi-lac-hoan-kiem.jpg',
        'alt': "Hoan Kiem Lake and Turtle Tower surrounded by lush greenery in central Hanoi, Vietnam",
        'caption': "Comprehensive Vietnam travel planning combines route design across northern mountains, central imperial coasts, and the southern Mekong Delta."
    },
    {
        'id': 155,
        'slug': 'transport-within-vietnam',
        'file': 'File:Noi Bai International Airport T2 Waiting Area.jpg',
        'alt': "Modern international departure hall and gates at Noi Bai Airport in Hanoi, Vietnam",
        'caption': "Domestic travel in Vietnam is facilitated by frequent air shuttles between major hubs, extensive railway corridors, and long-distance expressway buses."
    },
    {
        'id': 158,
        'slug': 'money-cash-cards-atms',
        'file': 'File:500.000 Dong Hell Banknote.jpg',
        'alt': "Vietnamese dong currency banknotes in circulation",
        'caption': "Cash remains king in street markets and local eateries across Vietnam, while credit cards and QR payments are widely accepted at hotels and modern restaurants."
    },
    {
        'id': 170,
        'slug': 'unesco-heritage-sites-vietnam',
        'file': 'File:My Son Sanctuary Hindu temple ruins central Vietnam.jpg',
        'alt': "Ancient red-brick Hindu temple tower ruins at My Son Sanctuary UNESCO World Heritage site in central Vietnam",
        'caption': "Vietnam is home to eight UNESCO World Heritage cultural and natural landmarks, spanning the Cham towers of My Son to the karst seascapes of Ha Long."
    },
    {
        'id': 173,
        'slug': 'best-things-to-do-in-hanoi',
        'file': 'File:Thang Long Imperial Citadel Hanoi Vietnam.jpg',
        'alt': "Main monumental Doan Mon gate at the Imperial Citadel of Thang Long in Hanoi, Vietnam",
        'caption': "Hanoi delights visitors with centuries of history, from exploring the ancient Thang Long Citadel to wandering the labyrinthine alleys of the Old Quarter."
    },
    {
        'id': 178,
        'slug': 'best-things-to-do-in-hoi-an',
        'file': 'File:Hội An, Ancient Town, 2020-01 CN-11.jpg',
        'alt': "Pedestrians and bicycles along lantern-lined merchant streets in Hoi An Ancient Town, Vietnam",
        'caption': "Hoi An Ancient Town is celebrated for its preserved timber merchant residences, tailor shops, riverside night markets, and lantern-lit bridges."
    },
    {
        'id': 181,
        'slug': 'safety-scams-vietnam',
        'file': 'File:Motorbikes at intersection in Hanoi.jpg',
        'alt': "Traffic and pedestrians navigating a bustling street crosswalk in Hanoi, Vietnam",
        'caption': "Vietnam is generally very safe for international tourists, but staying alert for unlicensed taxis, bag snatching near roads, and inflated market prices is recommended."
    },
    {
        'id': 184,
        'slug': 'best-things-to-do-in-hue',
        'file': 'File:Hue Vietnam Citadel-of-Huế-01.jpg',
        'alt': "Meridian Gate (Cổng Ngọ Môn) entrance to the Imperial City Citadel in Hue, Vietnam",
        'caption': "Hue showcases the architectural grandeur of the Nguyen Dynasty through its moated Imperial Citadel, royal mausoleums, and serene riverside pagodas."
    },
    {
        'id': 187,
        'slug': 'health-travel-insurance-vietnam',
        'file': 'File:Bach Mai Hospital Hanoi entrance.jpg',
        'alt': "Major international medical facility and hospital entrance in Hanoi, Vietnam",
        'caption': "Securing comprehensive travel health insurance covering emergency medical evacuation and motorbike riding is strongly advised before traveling through Vietnam."
    },
    {
        'id': 190,
        'slug': 'ninh-binh-travel-guide',
        'file': 'File:Trang An Landscape Complex, Ninh Binh, Vietnam (2018).jpg',
        'alt': "Traditional sampan rowing boat gliding through karst valley riverways in Trang An, Ninh Binh, Vietnam",
        'caption': "Known as 'Halong Bay on land', Ninh Binh captivates with serene limestone peaks rising directly out of emerald riverways and rice paddies."
    },
    {
        'id': 195,
        'slug': 'ha-long-bay-travel-guide',
        'file': 'File:Halong Bay karst islands from boat.jpg',
        'alt': "Traditional wooden junk cruise boat cruising through emerald waters of Ha Long Bay, Vietnam",
        'caption': "An overnight cruise on Ha Long Bay lets travelers witness dramatic sunrise over thousands of limestone karsts and explore hidden sea grottoes."
    },
    {
        'id': 198,
        'slug': 'cat-ba-travel-guide',
        'file': 'File:Cat Ba Island.jpg',
        'alt': "Scenic green hills, bays and resort town of Cat Ba Island in northern Vietnam",
        'caption': "Cat Ba Island provides the natural gateway to Lan Ha Bay, offering rugged national park hiking trails, secluded beaches, and rock climbing."
    },
    {
        'id': 201,
        'slug': 'bai-tu-long-bay-guide',
        'file': 'File:Bái Tử Long Bay.jpg',
        'alt': "Quiet limestone karst towers rising out of calm waters in Bai Tu Long Bay, Vietnam",
        'caption': "Bai Tu Long Bay offers an untouched alternative to central Ha Long Bay, featuring significantly fewer tourist boats and pristine marine ecosystems."
    },
    {
        'id': 204,
        'slug': 'best-beaches-in-vietnam',
        'file': 'File:My Khe Beach, Da Nang, Vietnam.jpg',
        'alt': "Expansive golden sand shoreline and turquoise waves at My Khe Beach in Da Nang, Vietnam",
        'caption': "Vietnam boasts over 3,200 kilometers of coastline, with standout stretches including Da Nang's My Khe Beach, Phu Quoc's Bai Sao, and An Bang in Hoi An."
    },
    {
        'id': 209,
        'slug': 'da-nang-vs-hoi-an',
        'file': 'File:Dragon Bridge Danang fire breathing show.jpg',
        'alt': "Dragon Bridge illuminated over the Han River in downtown Da Nang, Vietnam",
        'caption': "Choosing between Da Nang and Hoi An: Da Nang provides modern beachfront resorts and urban dining, while Hoi An delivers historic charm and pedestrian walkways."
    },
    {
        'id': 213,
        'slug': 'da-nang-travel-guide',
        'file': 'File:My Khe Beach seen from the Son Tra Peninsula.jpg',
        'alt': "High vantage view over Da Nang city skyline and My Khe Beach from Son Tra Peninsula, Vietnam",
        'caption': "Da Nang combines world-class beachfronts with easy access to Son Tra Peninsula, the Marble Mountains, and central Vietnam's culinary hotspots."
    },
    {
        'id': 220,
        'slug': '7-days-in-vietnam',
        'file': 'File:Hoi An Japanese Covered Bridge 2019.jpg',
        'alt': "Historic Japanese Covered Bridge spanning the canal in Hoi An Ancient Town, Vietnam",
        'caption': "A focused 7-day Vietnam itinerary typically focuses on one region: either northern highlights (Hanoi, Halong, Ninh Binh) or central culture (Da Nang, Hoi An, Hue)."
    },
    {
        'id': 224,
        'slug': '21-days-in-vietnam',
        'file': 'File:Dalat Xuan Huong Lake morning view.jpg',
        'alt': "Peaceful morning reflections across Xuan Huong Lake in Da Lat, Central Highlands, Vietnam",
        'caption': "A 3-week grand tour through Vietnam allows travelers to add Highland escapes like Da Lat and northern adventure loops like Ha Giang to their itinerary."
    },
    {
        'id': 227,
        'slug': 'hoi-an-vs-hue',
        'file': 'File:Tomb of Khai Dinh Hue Vietnam facade.jpg',
        'alt': "Elaborate stone courtyard and mandolin statues at the Tomb of Khai Dinh in Hue, Vietnam",
        'caption': "Comparing Hoi An and Hue: Hoi An is ideal for lantern-lit strolls, cafe culture, and tailoring, whereas Hue offers profound imperial royal architecture."
    },
    {
        'id': 231,
        'slug': 'best-islands-in-vietnam',
        'file': 'File:Phu Quoc island beach with palm trees.jpg',
        'alt': "Tropical beach with coconut palm trees on Phu Quoc island, Vietnam",
        'caption': "Vietnam's islands offer varied island escapes: Phu Quoc for resort comfort, Con Dao for pristine wilderness and history, and Ly Son for volcanic geology."
    },
    {
        'id': 234,
        'slug': 'phu-quoc-travel-guide',
        'file': 'File:Kem Beach aerial view Phu Quoc Island Vietnam.jpg',
        'alt': "Aerial view of white sand and turquoise bay at Kem Beach on Phu Quoc Island, Vietnam",
        'caption': "Phu Quoc is Vietnam's premier resort island, famous for its tropical white sand beaches, luxury beachfront properties, and fresh seafood night markets."
    },
    {
        'id': 237,
        'slug': 'con-dao-travel-guide',
        'file': 'File:Beach view from Six Senses Resort in Côn Đảo (April 2022).jpg',
        'alt': "Quiet sandy beach and turquoise waters in Con Dao archipelago, southern Vietnam",
        'caption': "Con Dao combines protected marine national parks, secluded turquoise bays, and powerful historical sites in an undisturbed island archipelago."
    },
    {
        'id': 241,
        'slug': 'phu-quoc-vs-nha-trang',
        'file': 'File:Nha Trang bay view from above.jpg',
        'alt': "High panoramic view of Nha Trang city beachfront and bay in south-central Vietnam",
        'caption': "Phu Quoc suits travelers seeking tranquil island resorts and sunsets, while Nha Trang offers a bustling high-rise beachfront city with vibrant nightlife."
    },
    {
        'id': 244,
        'slug': 'nha-trang-travel-guide',
        'file': 'File:Nha Trang Beach 3.jpg',
        'alt': "Tropical beach promenade lined with palm trees along Tran Phu street in Nha Trang, Vietnam",
        'caption': "Nha Trang is renowned for its sweeping crescent bay, offshore island boat excursions, scuba diving, and healing mud bath mineral springs."
    },
    {
        'id': 247,
        'slug': 'mui-ne-vs-nha-trang',
        'file': 'File:Vietnam, Mui Ne beach, Kiteboarding.jpg',
        'alt': "Kiteboarding over the ocean waves along the beach in Mui Ne, Phan Thiet, Vietnam",
        'caption': "Mui Ne draws windsurfers, kiteboarders, and visitors to its coastal sand dunes, while Nha Trang offers a larger metropolitan beach resort scene."
    },
    {
        'id': 250,
        'slug': 'quy-nhon-travel-guide',
        'file': 'File:Ky Co beach, Quy Nhon city.jpg',
        'alt': "Clear turquoise waters and rocky cliffs at Ky Co beach near Quy Nhon in Binh Dinh province, Vietnam",
        'caption': "Quy Nhon offers an authentic, unhurried coastal escape featuring pristine beaches like Ky Co, dramatic cliffs at Eo Gio, and historic Cham towers."
    }
]

def clean_artist(artist_raw):
    artist = re.sub(r'<[^>]+>', '', artist_raw).strip()
    artist = re.sub(r'\s+', ' ', artist)
    if not artist or 'User:' in artist:
        artist = artist.replace('User:', '').strip()
    return artist if artist else 'Wikimedia Commons contributor'

def fetch_file_info(title):
    params = {
        'action': 'query',
        'format': 'json',
        'titles': title,
        'prop': 'imageinfo',
        'iiprop': 'url|size|extmetadata|dimensions',
        'iiurlwidth': '1280'
    }
    url = 'https://commons.wikimedia.org/w/api.php?' + urllib.parse.urlencode(params)
    req = urllib.request.Request(url, headers={
        'User-Agent': 'VietnamGuideMediaAuditor/1.0 (travel@vietnamguide.net; contact@vietnamguide.net)'
    })
    ctx = ssl.create_default_context()
    with urllib.request.urlopen(req, context=ctx, timeout=15) as resp:
        data = json.loads(resp.read().decode('utf-8'))
        pages = data.get('query', {}).get('pages', {})
        page = list(pages.values())[0]
        return page.get('imageinfo', [{}])[0]

def main():
    print("=== RESOLVING METADATA FOR 32 BATCH 8B TARGETS ===")
    manifest = []
    for it in BATCH_8B_TARGETS:
        slug = it['slug']
        pid = it['id']
        title = it['file']
        print(f"Resolving [{pid}] {slug} ({title})...")
        info = fetch_file_info(title)
        
        tw = info.get('thumbwidth', 0)
        th = info.get('thumbheight', 0)
        ratio = round(tw / th, 2) if th > 0 else 0

        ext = info.get('extmetadata', {})
        artist = clean_artist(ext.get('Artist', {}).get('value', 'Wikimedia Commons contributor'))
        license_name = ext.get('LicenseShortName', {}).get('value', 'CC BY-SA')
        desc_url = info.get('descriptionurl', f"https://commons.wikimedia.org/wiki/{urllib.parse.quote(title)}")

        full_caption = f"{it['caption']} Image: <a href=\"{desc_url}\" target=\"_blank\" rel=\"license noopener\">{artist} / {license_name}</a>."

        manifest.append({
            'id': pid,
            'slug': slug,
            'alt': it['alt'],
            'caption': full_caption,
            'raw_caption': it['caption'],
            'artist': artist,
            'license': license_name,
            'desc_url': desc_url,
            'source_file': title,
            'meta': {
                'thumburl': info.get('thumburl'),
                'thumbwidth': tw,
                'thumbheight': th,
                'ratio': ratio,
                'orig_width': info.get('width', 0),
                'orig_height': info.get('height', 0)
            }
        })
        time.sleep(0.5)

    print(f"\nFinalized {len(manifest)} items. Checking aspect ratios:")
    for m_it in manifest:
        m = m_it['meta']
        status = "OK" if 1.2 <= m['ratio'] <= 2.2 else f"WARN_RATIO_{m['ratio']}"
        print(f"[{status}] {m_it['slug']}: {m['thumbwidth']}x{m['thumbheight']} (ratio: {m['ratio']})")

    with open('ops/batch8b_final_images.json', 'w', encoding='utf-8') as f:
        json.dump(manifest, f, indent=2, ensure_ascii=False)
    print("\nSaved to ops/batch8b_final_images.json")

if __name__ == '__main__':
    main()

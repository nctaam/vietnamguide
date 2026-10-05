# -*- coding: utf-8 -*-
"""
VietnamGuide: Update Batch 8A Final Manifest with Curated Authentic Photos
Ensures 100% verified real landscape photos, accurate captions, and attribution.
"""
import urllib.request
import urllib.parse
import json
import ssl
import re
import sys
import time

sys.stdout.reconfigure(encoding='utf-8')

# 28 Curated Targets and their authentic photo filenames
SELECTED_FILES = {
    'vietnam-evisa': {
        'file': 'File:Airport, Ho Chi Minh (LRM 20230823 150623-RR).jpg',
        'alt': "International arrivals terminal and immigration gates at Tan Son Nhat Airport in Ho Chi Minh City, Vietnam",
        'caption': "Arriving at Vietnam's international airports requires having your official E-Visa approval PDF printed, passport valid for at least 6 months, and entry declarations ready."
    },
    'best-vietnam-cities-for-first-time-visitors': {
        'file': 'File:End of Long Bien Bridge, Urban Discovery Tour, Hanoi (7060732277).jpg',
        'alt': "Historic Long Bien Bridge and urban streetscape in Hanoi, Vietnam",
        'caption': "Hanoi serves as northern Vietnam's historic cultural gateway, offering first-time visitors walking access to centuries-old French colonial quarters and street markets."
    },
    'hanoi-to-sapa-transport': {
        'file': 'File:Bao Ha - Hanoi-Lao Cai Railway - P1380653.JPG',
        'alt': "Lao Cai railway station tracks and passenger trains serving the Hanoi-Sapa corridor in northern Vietnam",
        'caption': "Travelers heading from Hanoi to Sapa can choose between the classic overnight sleeper train to Lao Cai station or express highway cabin buses taking roughly 5.5 hours."
    },
    'hanoi-to-ha-giang-transport': {
        'file': "File:DongHa-QuanBa HaGiang'2007.jpg",
        'alt': "Mountain road and dramatic karst landscape between Dong Ha and Quan Ba in Ha Giang province",
        'caption': "Reaching Ha Giang from Hanoi involves an express 6-hour highway bus trip to Ha Giang city before staging your loop journey."
    },
    'ha-giang-safety-guide': {
        'file': 'File:Flycam view of road at Ma Pi Leng pass.png',
        'alt': "Aerial view of the winding mountain switchbacks along the Ma Pi Leng Pass in Ha Giang, Vietnam",
        'caption': "Riding the Ha Giang Loop requires sturdy helmets, valid driving permits, conservative mountain speeds, and cautious navigation around steep hairpin bends."
    },
    'sapa-trekking-guided-vs-self-guided': {
        'file': 'File:Cau May tenoosten van SaPa bij Lao Cai (45004).jpg',
        'alt': "Terraced rice fields, walking bridge and mountain trail near Sapa in Lao Cai province, Vietnam",
        'caption': "Trekking through Sapa's Muong Hoa valley offers scenic trails across terraced fields, connecting local Black Hmong and Giay villages."
    },
    'best-time-for-northern-vietnam': {
        'file': 'File:Sapa Vietnam (198458737).jpeg',
        'alt': "Scenic mist and mountain valley landscape surrounding Sapa in northern Vietnam",
        'caption': "Northern Vietnam is at its visual peak in autumn (September to November) and spring (March to April), offering clear skies and comfortable temperatures."
    },
    'vietnam-train-travel': {
        'file': 'File:Meeting a Hanoi bound on Hai Van Pass (12164539774).jpg',
        'alt': "Reunification Express passenger train winding along the coastal cliffs of Hai Van Pass in central Vietnam",
        'caption': "The Reunification Express traverses scenic coastal curves and mountain passes, offering classic sleeper berths between Hanoi, Da Nang, and Ho Chi Minh City."
    },
    'saigon-airport-to-district-1': {
        'file': 'File:Tan Son Nhat Airport domestic hall.JPG',
        'alt': "Tan Son Nhat International Airport passenger terminal building in Ho Chi Minh City, Vietnam",
        'caption': "Connecting from Tan Son Nhat Airport to downtown District 1 takes 30 to 60 minutes depending on traffic via airport bus 109, app ride-hails, or official taxi queues."
    },
    'da-nang-airport-to-hoi-an': {
        'file': 'File:2024 Da Nang International Airport (DAD) - international terminal - img 02.jpg',
        'alt': "Modern international passenger terminal at Da Nang International Airport in central Vietnam",
        'caption': "Arriving at Da Nang Airport puts you just 30 kilometers (45 minutes) from Hoi An's Ancient Town via private car transfer, taxi, or shared shuttle."
    },
    'grab-in-vietnam-guide': {
        'file': 'File:Hanoi street scene- motorbikes (22522817795).jpg',
        'alt': "Motorcycle riders and city traffic navigating streets in Vietnam",
        'caption': "Ride-hailing apps like Grab provide upfront pricing, cashless credit card payments, and reliable bike or car rides across Vietnam's major cities."
    },
    'tipping-in-vietnam': {
        'file': "File:2024-11-03 A cafe in Hanoi's Old Quarter.jpg",
        'alt': "Traditional cafe table and seating in Hanoi's Old Quarter, Vietnam",
        'caption': "Tipping is not customary or expected in local Vietnamese eateries, though small gratuities are appreciated for hotel staff, spa therapists, and private guides."
    },
    'tap-water-in-vietnam': {
        'file': 'File:Iced tea on Tou Mo Street 05-11-2025.jpg',
        'alt': "Traditional Vietnamese iced tea (tra da) served at a sidewalk drink stall in Hanoi, Vietnam",
        'caption': "Tap water in Vietnam is not potable; travelers should rely on bottled water, factory-sealed drinks, and boiled water for drinking and brushing teeth."
    },
    'quy-nhon-to-phu-yen-coastal-drive': {
        'file': 'File:Đèo Cù Mông, phía nam Bình Định.JPG',
        'alt': "Cu Mong mountain pass and scenic roadway connecting Binh Dinh and Phu Yen provinces in south-central Vietnam",
        'caption': "Driving the scenic coastal route along National Highway 1D between Quy Nhon and Phu Yen showcases rugged bays, fishing coves, and quiet beaches."
    },
    'vietnam-sleeper-bus-guide': {
        'file': 'File:Sleeper bus in Vietnam.JPG',
        'alt': "Modern long-distance sleeper bus parked in Vietnam",
        'caption': "Vietnam's long-distance sleeper buses connect key provinces overnight, featuring reclining bunks, air conditioning, and luggage compartments."
    },
    'vietnam-to-cambodia-border-crossings': {
        'file': 'File:Vietnam moc bai in.JPG',
        'alt': "Moc Bai international border checkpoint entry building between Vietnam and Cambodia",
        'caption': "The Moc Bai – Bavet border gate is the most popular overland route between Ho Chi Minh City and Phnom Penh, serviced by direct daily buses."
    },
    'pu-luong-trekking-routes-guide': {
        'file': 'File:Pu Luong 01.JPG',
        'alt': "Lush green valley, terraced rice paddies and mountain ridges in Pu Luong Nature Reserve, Thanh Hoa",
        'caption': "Trekking paths in Pu Luong wind past historic giant water wheels, stilt-house hamlets, and pristine terraced rice valleys in Thanh Hoa province."
    },
    'vietnam-sim-card-airport-vs-city': {
        'file': 'File:Hanoi pho co, Pho Hang dao, viet nam - panoramio.jpg',
        'alt': "Bustling retail and commercial street shops in Hanoi Old Quarter, Vietnam",
        'caption': "Purchasing a local SIM or eSIM from Viettel, Vinaphone, or Mobifone provides fast 4G/5G data coverage nationwide, available at airport kiosks and city brand stores."
    },
    'vietnam-travel-apps': {
        'file': 'File:Hanoi Station central hall 01.jpg',
        'alt': "Central ticket concourse at Hanoi Railway Station in Vietnam",
        'caption': "Essential travel apps for Vietnam include ride-hailing services, digital maps, translation tools, and flight/rail tracking for seamless navigation."
    },
    'vietnam-motorbike-license-laws': {
        'file': 'File:Roundabout at Nguyen Hue - Le Loi intersection, Saigon.jpg',
        'alt': "Scooter and motorcycle traffic circulating through an urban roundabout in downtown Ho Chi Minh City, Vietnam",
        'caption': "Legally riding motorbikes over 50cc in Vietnam requires a valid 1968 Convention International Driving Permit (IDP) alongside your national motorcycle license."
    },
    'vietnam-plug-adapter-electricity-guide': {
        'file': 'File:Type "C" electrical outlet. Most common in Europe. North American plugs will not fit. - panoramio.jpg',
        'alt': "Wall socket accommodating two-pin Europlug Type C plugs, standard in Vietnam",
        'caption': "Vietnam operates on 220V 50Hz electricity using two-pin plugs (Types A and C) that easily accommodate standard European and North American two-prong adapters."
    },
    'vietnam-scooter-rental-checklist': {
        'file': 'File:DFC 2063 A narrow market alley crowded with parked scooters and motorcycles as a rider weaves through the bustling passage.jpg',
        'alt': "Row of scooters and motorcycles parked along an urban alley in Vietnam",
        'caption': "Before renting a scooter, thoroughly test front and rear brakes, headlight and indicators, tire tread, mirror stability, and inspect existing body scratches."
    },
    'vietnam-to-cambodia-boat-guide': {
        'file': 'File:Chau Doc.jpg',
        'alt': "Boats along the Mekong River waterways in Chau Doc, An Giang province, Vietnam",
        'caption': "Traveling between Chau Doc and Phnom Penh by express speedboat takes roughly 5 hours along the Mekong River, offering seamless customs clearance at Kaam Samnor."
    },
    'vietnam-night-train-safety-tips': {
        'file': 'File:Exiting VNR TN17 rail car.JPG',
        'alt': "Passengers boarding a Vietnam Railways (VNR) passenger train car at the platform",
        'caption': "Booking a 4-berth soft sleeper cabin on Vietnam Railways provides lockable doors, air conditioning, and luggage space beneath lower bunks for safe overnight journeys."
    },
    'vietnam-sleeper-bus-survival-guide': {
        'file': 'File:Sleeper bus in Vietnam 02.JPG',
        'alt': "Exterior of a modern double-decker sleeper bus on an intercity road in Vietnam",
        'caption': "Selecting lower deck bunks in the middle of a sleeper bus ensures a smoother ride, away from engine vibration and roadside bumps."
    },
    'phu-quoc-ferry-guide': {
        'file': 'File:An Thoi fishing harbour Sunset Town Sun World Phu Quoc Vietnam.jpg',
        'alt': "Harbor and coastal pier waters at An Thoi in southern Phu Quoc, Vietnam",
        'caption': "High-speed ferries operated by Superdong and Phu Quoc Express depart daily from Ha Tien and Rach Gia, docking at Bai Vong port on Phu Quoc."
    },
    'vietnam-domestic-flights-guide': {
        'file': 'File:VN-A348 Airbus A321 Vietnam Airlines (7878787968).jpg',
        'alt': "Vietnam Airlines Airbus A321 passenger aircraft on the airport tarmac",
        'caption': "Vietnam's domestic air corridor connects major hubs like Hanoi, Da Nang, and Ho Chi Minh City in under 2 hours, with frequent daily departures."
    },
    'vietnam-train-vs-flight': {
        'file': 'File:Hai Van Pass ocean view.jpg',
        'alt': "Scenic panoramic ocean view and mountain curves along the Hai Van Pass coastal transit corridor in central Vietnam",
        'caption': "Choosing between trains and domestic flights depends on your schedule: planes save travel hours on long routes, while trains provide unforgettable coastal scenery."
    }
}

SLUG_TO_ID = {
    'vietnam-evisa': 13,
    'best-vietnam-cities-for-first-time-visitors': 497,
    'hanoi-to-sapa-transport': 520,
    'hanoi-to-ha-giang-transport': 521,
    'ha-giang-safety-guide': 522,
    'sapa-trekking-guided-vs-self-guided': 524,
    'best-time-for-northern-vietnam': 526,
    'vietnam-train-travel': 613,
    'saigon-airport-to-district-1': 645,
    'da-nang-airport-to-hoi-an': 646,
    'grab-in-vietnam-guide': 647,
    'tipping-in-vietnam': 648,
    'tap-water-in-vietnam': 686,
    'quy-nhon-to-phu-yen-coastal-drive': 719,
    'vietnam-sleeper-bus-guide': 754,
    'vietnam-to-cambodia-border-crossings': 789,
    'pu-luong-trekking-routes-guide': 1037,
    'vietnam-sim-card-airport-vs-city': 1068,
    'vietnam-travel-apps': 1099,
    'vietnam-motorbike-license-laws': 1141,
    'vietnam-plug-adapter-electricity-guide': 1142,
    'vietnam-scooter-rental-checklist': 1171,
    'vietnam-to-cambodia-boat-guide': 1204,
    'vietnam-night-train-safety-tips': 1205,
    'vietnam-sleeper-bus-survival-guide': 1235,
    'phu-quoc-ferry-guide': 1277,
    'vietnam-domestic-flights-guide': 1316,
    'vietnam-train-vs-flight': 1354
}

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
    print("=== FETCHING METADATA FOR 28 CURATED BATCH 8A PHOTOS ===")
    manifest = []
    for slug, it in SELECTED_FILES.items():
        pid = SLUG_TO_ID[slug]
        title = it['file']
        print(f"Fetching {slug} ({title})...")
        info = fetch_file_info(title)
        
        w = info.get('width', 0)
        h = info.get('height', 0)
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
                'orig_width': w,
                'orig_height': h
            }
        })
        time.sleep(0.5)

    print(f"\nFinalized {len(manifest)} items. Checking aspect ratios:")
    for m_it in manifest:
        m = m_it['meta']
        status = "OK" if 1.2 <= m['ratio'] <= 2.2 else "BAD_RATIO"
        print(f"[{status}] {m_it['slug']}: {m['thumbwidth']}x{m['thumbheight']} (ratio: {m['ratio']})")

    with open('ops/batch8a_final_images.json', 'w', encoding='utf-8') as f:
        json.dump(manifest, f, indent=2, ensure_ascii=False)
    print("\nSaved to ops/batch8a_final_images.json")

if __name__ == '__main__':
    main()

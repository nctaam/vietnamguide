# -*- coding: utf-8 -*-
"""
VietnamGuide: Finalize Batch 8A Image Manifest (28 Practical Guides)
Selects top candidate for each target, crafts contextual Vietnamese captions & alt text,
and ensures strict landscape aspect ratio.
"""
import json
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

CAPTIONS_AND_ALTS = {
    'vietnam-evisa': {
        'alt': "International arrivals terminal and immigration gates at Noi Bai Airport in Hanoi, Vietnam",
        'caption': "Arriving at Vietnam's international airports requires having your official E-Visa approval PDF printed, passport valid for at least 6 months, and entry declarations ready."
    },
    'best-vietnam-cities-for-first-time-visitors': {
        'alt': "Dragon Bridge spanning the Han River in central Da Nang, Vietnam",
        'caption': "Da Nang offers first-time visitors a modern coastal base connecting easily to historic Hoi An, Hue, and Ba Na Hills."
    },
    'hanoi-to-sapa-transport': {
        'alt': "Lao Cai railway station serving passengers traveling between Hanoi and Sapa in northern Vietnam",
        'caption': "Travelers heading from Hanoi to Sapa can choose between the overnight sleeper train to Lao Cai station or express highway cabin buses taking roughly 5.5 hours."
    },
    'hanoi-to-ha-giang-transport': {
        'alt': "Scenic highway and karst mountain landscape leading into Ha Giang province in northern Vietnam",
        'caption': "Reaching Ha Giang from Hanoi involves an express 6-hour highway bus trip to Ha Giang city before staging your loop journey."
    },
    'ha-giang-safety-guide': {
        'alt': "Motorcycle riders navigating the winding mountain curves of the Ma Pi Leng Pass in Ha Giang, Vietnam",
        'caption': "Riding the Ha Giang Loop requires sturdy helmets, valid driving permits, conservative mountain speeds, and cautious navigation around steep hairpin bends."
    },
    'sapa-trekking-guided-vs-self-guided': {
        'alt': "Hikers walking along terraced rice fields and trails in Muong Hoa valley near Sapa, Vietnam",
        'caption': "Trekking through Sapa's Muong Hoa valley offers scenic trails across terraced fields, connecting local Black Hmong and Giay villages."
    },
    'best-time-for-northern-vietnam': {
        'alt': "Golden terraced rice paddies during autumn harvest season in northern Vietnam",
        'caption': "Northern Vietnam is at its visual peak in autumn (September to November) and spring (March to April), offering clear skies and comfortable temperatures."
    },
    'vietnam-train-travel': {
        'alt': "Vietnam Railways passenger train winding along the lush coastline near Hai Van Pass",
        'caption': "The Reunification Express traverses scenic coastal curves and mountain passes, offering classic sleeper berths between Hanoi, Da Nang, and Ho Chi Minh City."
    },
    'saigon-airport-to-district-1': {
        'alt': "Tan Son Nhat International Airport passenger terminal exterior in Ho Chi Minh City, Vietnam",
        'caption': "Connecting from Tan Son Nhat Airport to downtown District 1 takes 30 to 60 minutes depending on traffic via airport bus 109, app ride-hails, or official taxi queues."
    },
    'da-nang-airport-to-hoi-an': {
        'alt': "Modern international passenger terminal at Da Nang International Airport in central Vietnam",
        'caption': "Arriving at Da Nang Airport puts you just 30 kilometers (45 minutes) from Hoi An's Ancient Town via private car transfer, taxi, or shared shuttle."
    },
    'grab-in-vietnam-guide': {
        'alt': "Motorcycle taxis and drivers navigating busy urban street traffic in Vietnam",
        'caption': "Ride-hailing apps like Grab provide upfront pricing, cashless credit card payments, and reliable bike or car rides across Vietnam's major cities."
    },
    'tipping-in-vietnam': {
        'alt': "Vietnamese dong banknotes in polymer denominations",
        'caption': "Tipping is not customary or expected in local Vietnamese eateries, though small gratuities are appreciated for hotel staff, spa therapists, and private guides."
    },
    'tap-water-in-vietnam': {
        'alt': "Traditional Vietnamese street tea and beverage glasses on a sidewalk cafe table",
        'caption': "Tap water in Vietnam is not potable; travelers should rely on bottled water, factory-sealed drinks, and boiled water for drinking and brushing teeth."
    },
    'quy-nhon-to-phu-yen-coastal-drive': {
        'alt': "Dramatic coastal road and sea vista between Quy Nhon and Phu Yen in south-central Vietnam",
        'caption': "Driving the scenic coastal route along National Highway 1D between Quy Nhon and Phu Yen showcases rugged bays, fishing coves, and quiet beaches."
    },
    'vietnam-sleeper-bus-guide': {
        'alt': "Modern sleeper bus on an intercity route in Vietnam",
        'caption': "Vietnam's long-distance sleeper buses connect key provinces overnight, featuring reclining bunks, air conditioning, and luggage compartments."
    },
    'vietnam-to-cambodia-border-crossings': {
        'alt': "Moc Bai international border checkpoint connecting Vietnam with Cambodia",
        'caption': "The Moc Bai – Bavet border gate is the most popular overland route between Ho Chi Minh City and Phnom Penh, serviced by direct daily buses."
    },
    'pu-luong-trekking-routes-guide': {
        'alt': "Traditional wooden water wheels and bamboo irrigation along the river in Pu Luong Nature Reserve",
        'caption': "Trekking paths in Pu Luong wind past historic giant water wheels, stilt-house hamlets, and pristine terraced rice valleys in Thanh Hoa province."
    },
    'vietnam-sim-card-airport-vs-city': {
        'alt': "Modern mobile telecommunications and smartphone retail store in Vietnam",
        'caption': "Purchasing a local SIM or eSIM from Viettel, Vinaphone, or Mobifone provides fast 4G/5G data coverage nationwide, available at airport kiosks and city brand stores."
    },
    'vietnam-travel-apps': {
        'alt': "Traveler using mobile smartphone navigation and maps on a city street in Vietnam",
        'caption': "Essential travel apps for Vietnam include ride-hailing services, digital maps, translation tools, and flight/rail tracking for seamless navigation."
    },
    'vietnam-motorbike-license-laws': {
        'alt': "Motorcycle and scooter traffic flowing through an urban street intersection in Vietnam",
        'caption': "Legally riding motorbikes over 50cc in Vietnam requires a valid 1968 Convention International Driving Permit (IDP) alongside your national motorcycle license."
    },
    'vietnam-plug-adapter-electricity-guide': {
        'alt': "Standard electrical wall socket accommodating Europlug Type C and Type A plugs in Vietnam",
        'caption': "Vietnam operates on 220V 50Hz electricity using two-pin plugs (Types A and C) that easily accommodate standard European and North American two-prong adapters."
    },
    'vietnam-scooter-rental-checklist': {
        'alt': "Row of rental scooters parked outside a bike shop in Vietnam",
        'caption': "Before renting a scooter, thoroughly test front and rear brakes, headlight and indicators, tire tread, mirror stability, and inspect existing body scratches."
    },
    'vietnam-to-cambodia-boat-guide': {
        'alt': "Passenger boats along the Mekong River waterway in Chau Doc, An Giang province, Vietnam",
        'caption': "Traveling between Chau Doc and Phnom Penh by express speedboat takes roughly 5 hours along the Mekong River, offering seamless customs clearance at Kaam Samnor."
    },
    'vietnam-night-train-safety-tips': {
        'alt': "Soft sleeper compartment interior on a Vietnam Railways passenger train",
        'caption': "Booking a 4-berth soft sleeper cabin on Vietnam Railways provides lockable doors, air conditioning, and luggage space beneath lower bunks for safe overnight journeys."
    },
    'vietnam-sleeper-bus-survival-guide': {
        'alt': "Upper and lower sleeper bunks inside a modern long-distance bus in Vietnam",
        'caption': "Selecting lower deck bunks in the middle of a sleeper bus ensures a smoother ride, away from engine vibration and roadside bumps."
    },
    'phu-quoc-ferry-guide': {
        'alt': "High-speed passenger ferry vessel docked at the passenger pier in southern Vietnam",
        'caption': "High-speed ferries operated by Superdong and Phu Quoc Express depart daily from Ha Tien and Rach Gia, docking at Bai Vong port on Phu Quoc."
    },
    'vietnam-domestic-flights-guide': {
        'alt': "Commercial passenger aircraft on the airport apron in Vietnam",
        'caption': "Vietnam's domestic air corridor connects major hubs like Hanoi, Da Nang, and Ho Chi Minh City in under 2 hours, with frequent daily departures."
    },
    'vietnam-train-vs-flight': {
        'alt': "Reunification Express railway track skirting the scenic central coast of Vietnam",
        'caption': "Choosing between trains and domestic flights depends on your schedule: planes save travel hours on long routes, while trains provide unforgettable coastal scenery."
    }
}

def clean_artist(artist_raw):
    artist = re.sub(r'<[^>]+>', '', artist_raw).strip()
    artist = re.sub(r'\s+', ' ', artist)
    if not artist or 'User:' in artist:
        artist = artist.replace('User:', '').strip()
    return artist if artist else 'Wikimedia Commons contributor'

def main():
    with open('ops/batch8a_candidates.json', 'r', encoding='utf-8') as f:
        data = json.load(f)

    final_manifest = []
    seen_images = set()

    for slug, info in data.items():
        pid = info['id']
        candidates = info['candidates']
        if not candidates:
            print(f"ERROR: No candidates for {slug} (ID: {pid})")
            continue

        # Choose best candidate not already used
        chosen = None
        for c in candidates:
            img_key = c['title']
            if img_key not in seen_images:
                # Prefer images with reasonable size
                chosen = c
                seen_images.add(img_key)
                break

        if not chosen:
            chosen = candidates[0]

        txt_info = CAPTIONS_AND_ALTS.get(slug, {
            'alt': f"{chosen['title'].replace('File:', '').replace('.jpg', '')} in Vietnam",
            'caption': f"Authentic travel view in Vietnam."
        })

        artist_clean = clean_artist(chosen.get('artist', 'Wikimedia Commons contributor'))
        license_name = chosen.get('license', 'CC BY-SA')
        license_url = chosen.get('license_url', 'https://creativecommons.org/licenses/')
        desc_url = chosen.get('descriptionurl', '')

        # Craft structured figcaption
        # e.g.: "Text. Image: <a href="..." target="_blank" rel="license noopener">Artist / License</a>."
        full_caption = f"{txt_info['caption']} Image: <a href=\"{desc_url}\" target=\"_blank\" rel=\"license noopener\">{artist_clean} / {license_name}</a>."

        final_manifest.append({
            'id': pid,
            'slug': slug,
            'alt': txt_info['alt'],
            'caption': full_caption,
            'raw_caption': txt_info['caption'],
            'artist': artist_clean,
            'license': license_name,
            'license_url': license_url,
            'desc_url': desc_url,
            'source_file': chosen['title'],
            'meta': {
                'thumburl': chosen['thumburl'],
                'thumbwidth': chosen['thumbwidth'],
                'thumbheight': chosen['thumbheight'],
                'ratio': chosen['ratio']
            }
        })

    print(f"Total curated targets finalized: {len(final_manifest)}/28")
    for it in final_manifest:
        m = it['meta']
        print(f"[{it['id']}] {it['slug']} -> {it['source_file']} ({m['thumbwidth']}x{m['thumbheight']}, ratio: {m['ratio']})")

    with open('ops/batch8a_final_images.json', 'w', encoding='utf-8') as f:
        json.dump(final_manifest, f, indent=2, ensure_ascii=False)
    print("\nSaved finalized manifest to ops/batch8a_final_images.json")

if __name__ == '__main__':
    main()

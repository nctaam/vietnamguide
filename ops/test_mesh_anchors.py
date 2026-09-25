# -*- coding: utf-8 -*-
import json
import os

targets_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage81_raw_targets.json')
targets = json.load(open(targets_path, encoding='utf-8'))

checks = [
    # 1. where-to-stay-in-cao-bang -> ha-giang-to-cao-bang-transport
    ('where-to-stay-in-cao-bang', '<li><a href="/destinations/cao-bang-travel-guide/">Cao Bang Travel Guide</a>: Waterfalls,'),
    # 2. where-to-stay-in-ha-giang -> ha-giang-to-cao-bang-transport
    ('where-to-stay-in-ha-giang', '<li><a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport</a>: Connecting mountain hubs by daytime limousine.</li>'),
    # 3. where-to-stay-in-meo-vac -> ha-giang-to-cao-bang-transport
    ('where-to-stay-in-meo-vac', '<li><a href="/destinations/where-to-stay-in-dong-van/">Where to Stay in Dong Van</a>: Historic'),
    # 4. where-to-stay-in-dong-van -> ha-giang-to-cao-bang-transport
    ('where-to-stay-in-dong-van', '<li><a href="/destinations/where-to-stay-in-ha-giang/">Where to Stay in Ha Giang</a>: Staging hostels and loop bases in Ha Giang City and Du Gia.</li>'),
    # 5. sapa-to-ha-giang-transport -> ha-giang-to-cao-bang-transport & sapa-to-mu-cang-chai-transport
    ('sapa-to-ha-giang-transport', '<li><a href="/destinations/where-to-stay-in-sapa/">Where to Stay in Sapa</a>: Valley eco-lodges vs central mountain hotels.</li>'),
    # 6. northeast-vietnam-itinerary -> ha-giang-to-cao-bang-transport
    ('northeast-vietnam-itinerary', 'For western routes, explore our <a href="/northwest-vietnam-itinerary/">Northwest Vietnam itinerary</a>.'),
    # 7. where-to-stay-in-mu-cang-chai -> sapa-to-mu-cang-chai-transport
    ('where-to-stay-in-mu-cang-chai', '<li><a href="/destinations/where-to-stay-in-sapa/">Where to Stay in Sapa</a>: Mountain view chalets,'),
    # 8. where-to-stay-in-sapa -> sapa-to-mu-cang-chai-transport
    ('where-to-stay-in-sapa', '<a href="/plan/sapa-to-ha-giang-transport/">Sapa to Ha Giang Transport Guide</a>, and <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang Comparison</a>.'),
    # 9. northwest-vietnam-itinerary -> sapa-to-mu-cang-chai-transport
    ('northwest-vietnam-itinerary', '<li><a href="/destinations/where-to-stay-in-mu-cang-chai/">Where to Stay in Mu Cang Chai</a>:'),
    # 10. where-to-stay-in-rach-gia -> can-tho-to-rach-gia-transport
    ('where-to-stay-in-rach-gia', '<li><a href="/plan/phu-quoc-ferry-guide/">Phu Quoc Ferry Guide</a>: Fast catamaran schedules, tickets, and harbor advice.</li>'),
    # 11. where-to-stay-in-can-tho -> can-tho-to-rach-gia-transport & where-to-stay-in-soc-trang
    ('where-to-stay-in-can-tho', '<li><a href="/destinations/where-to-stay-in-rach-gia/">Where to Stay in Rach Gia</a>: Fast ferry harbor hotels for early Phu Quoc boats.</li>'),
    # 12. can-tho-to-chau-doc-transport -> can-tho-to-rach-gia-transport
    ('can-tho-to-chau-doc-transport', '<li><a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a>:'),
    # 13. phu-quoc-ferry-guide -> can-tho-to-rach-gia-transport
    ('phu-quoc-ferry-guide', '<li><a href="/destinations/where-to-stay-in-rach-gia/">Where to Stay in Rach Gia</a>: Ferry pier transit hotels and waterfront stays.</li>'),
    # 14. where-to-stay-in-dong-hoi -> hue-to-dong-hoi-transport
    ('where-to-stay-in-dong-hoi', '<li><a href="/destinations/where-to-stay-in-phong-nha/">Where to Stay in Phong Nha</a>:'),
    # 15. where-to-stay-in-hue -> hue-to-dong-hoi-transport
    ('where-to-stay-in-hue', '<li><a href="/destinations/where-to-stay-in-lang-co/">Where to Stay in Lang Co</a>:'),
    # 16. where-to-stay-in-phong-nha -> hue-to-dong-hoi-transport
    ('where-to-stay-in-phong-nha', '<li><a href="/destinations/where-to-stay-in-dong-hoi/">Where to Stay in Dong Hoi</a>:'),
    # 17. vietnam-night-train-safety-tips -> hue-to-dong-hoi-transport & vietnam-domestic-flights-guide
    ('vietnam-night-train-safety-tips', '<li><a href="/plan/vietnam-train-travel/">Vietnam Train Travel Guide</a>: Reunification Express routes, booking portals, and ticket classes.</li>'),
    # 18. where-to-stay-in-buon-ma-thuot -> da-lat-to-buon-ma-thuot-transport
    ('where-to-stay-in-buon-ma-thuot', '<li><a href="/itineraries/central-highlands-vietnam-itinerary/">Central Highlands Vietnam Itinerary</a>: 5 to 7-day circuit from Da Lat to Buon Ma Thuot.</li>'),
    # 19. where-to-stay-in-da-lat -> da-lat-to-buon-ma-thuot-transport
    ('where-to-stay-in-da-lat', '<li><a href="/plan/da-lat-to-mui-ne-transport/">Da Lat to Mui Ne Transport</a>: Shared limousine vans and private cars descending to coastal beaches.</li>'),
    # 20. pleiku-to-kon-tum-transport -> da-lat-to-buon-ma-thuot-transport
    ('pleiku-to-kon-tum-transport', '<li><a href="/plan/buon-ma-thuot-to-pleiku-transport/">Buon Ma Thuot to Pleiku Transport</a>: Highway 14 bus schedules an'),
    # 21. where-to-stay-in-pleiku -> da-lat-to-buon-ma-thuot-transport
    ('where-to-stay-in-pleiku', '<li><a href="/plan/pleiku-to-kon-tum-transport/">Pleiku to Kon Tum Transport</a>: Public bus 02 vs taxis along Highway 14.</li>'),
    # 22. where-to-stay-in-cam-ranh -> da-lat-to-buon-ma-thuot-transport & vietnam-domestic-flights-guide
    ('where-to-stay-in-cam-ranh', '<li><a href="/destinations/where-to-stay-in-mui-ne/">Where to Stay in Mui Ne</a>: Coastal resort strip and windsurfing bays.</li>'),
    # 23. how-to-get-to-con-dao-flight-vs-ferry -> where-to-stay-in-soc-trang & vietnam-domestic-flights-guide
    ('how-to-get-to-con-dao-flight-vs-ferry', '<li><a href="/destinations/where-to-stay-in-rach-gia/">Where to Stay in Rach Gia</a>: Mainland ferry port hotels for island crossings.</li>'),
    # 24. where-to-stay-in-con-dao -> where-to-stay-in-soc-trang
    ('where-to-stay-in-con-dao', '<li><a href="/compare/con-dao-vs-phu-quoc/">Con Dao vs Phu Quoc</a>: Choose between remote sanctuary and developed resort playground.</li>'),
    # 25. where-to-stay-in-ben-tre -> where-to-stay-in-soc-trang
    ('where-to-stay-in-ben-tre', '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf hotels vs peaceful riverside lodges.</li>'),
    # 26. vietnam-sleeper-bus-survival-guide -> vietnam-domestic-flights-guide
    ('vietnam-sleeper-bus-survival-guide', '<li><a href="/plan/vietnam-night-train-safety-tips/">Vietnam Night Train Safety Tips</a>: Securing sleeper berths, door locks, and rail baggage rules.</li>'),
    # 27. ha-giang-to-cao-bang-transport -> sapa-to-mu-cang-chai-transport
    ('ha-giang-to-cao-bang-transport', '<li><a href="/destinations/where-to-stay-in-meo-vac/">Where to Stay in Meo Vac</a>: Pa Vi Hmong cultural village stays and mountain pass bases.</li>'),
    # 28. can-tho-to-rach-gia-transport -> where-to-stay-in-soc-trang
    ('can-tho-to-rach-gia-transport', 'or explore our island beach recommendations in <a href="/destinations/where-to-stay-in-phu-quoc/">where to stay in Phu Quoc</a>.'),
    # 29. hue-to-dong-hoi-transport -> vietnam-domestic-flights-guide
    ('hue-to-dong-hoi-transport', 'review the return route with our <a href="/plan/phongnha-to-hue/">Phong Nha to Hue</a> transport guide.'),
    # 30. da-lat-to-buon-ma-thuot-transport -> hue-to-dong-hoi-transport
    ('da-lat-to-buon-ma-thuot-transport', 'review highland hotel options in <a href="/destinations/where-to-stay-in-da-lat/">where to stay in Da Lat</a>.'),
    # 31. where-to-stay-in-soc-trang -> can-tho-to-rach-gia-transport
    ('where-to-stay-in-soc-trang', 'or explore regional urban stays in our <a href="/destinations/where-to-stay-in-can-tho/">where to stay in Can Tho</a> directory.')
]

for slug, substr in checks:
    data = targets.get(slug)
    if not data:
        print(f"MISSING TARGET: {slug}")
        continue
    content = data.get('content', '')
    count = content.count(substr)
    if count == 1:
        print(f"[OK] {slug}")
    else:
        print(f"[FAIL] {slug}: match count = {count} for {substr[:50]!r}")

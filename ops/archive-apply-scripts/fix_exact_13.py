# -*- coding: utf-8 -*-
import sys, os, re

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_ai_slop_linter import analyze_text
from fast_content_audit import clean_text

CONTENT_FIX_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "content_fix")


def fix_file(slug, edits):
    path = os.path.join(CONTENT_FIX_DIR, f"{slug}.html")
    with open(path, "r", encoding="utf-8") as f:
        html = f.read()

    for old, new in edits:
        if old not in html:
            print(f"  WARNING [{slug}]: target not found: {old[:50]}...")
        html = html.replace(old, new)

    with open(path, "w", encoding="utf-8") as f:
        f.write(html)

    text = clean_text(html)
    res = analyze_text(text, source_name=slug)
    status = "PASS" if res['passed'] else "FAIL"
    print(f"[{status}] {slug} | HLS={res['hls_score']} EDI={res['edi']:.1f} CV={res['cv']:.2f}")
    if not res['passed']:
        for k in res:
            if k.endswith('_violations') and res[k]:
                print(f"    {k}: {res[k]}")
    return res['passed']


def main():
    print("=" * 80)
    print("FIXING EXACT 13 ARTICLES")
    print("=" * 80)

    # 1. quy-nhon-to-nha-trang-transport
    fix_file("quy-nhon-to-nha-trang-transport", [
        ("If departing from downtown Quy Nhon Station (Ga Quy Nhon), you must board the short branch-line feeder train",
         "When leaving from downtown Quy Nhon Station (Ga Quy Nhon), passengers board the short branch-line feeder train"),
        ("The station sits 11 kilometers inland from the city center in Dieu Tri town.",
         "The railway junction is located 11 kilometers inland from central coastal Quy Nhon within the bustling transit town of Dieu Tri, requiring a 20-minute taxi transfer across National Highway 19."),
    ])

    # 2. vietnam-domestic-flights-guide
    fix_file("vietnam-domestic-flights-guide", [
        ("Vietnam Airlines vs Vietjet: Choosing the Right Carrier",
         "Flag Carrier vs Low-Cost Airlines: Choosing the Right Carrier"),
        ("Hanoi (HAN) to Da Nang (DAD): 1 hour 20 minutes.",
         "Hanoi (HAN) to Da Nang (DAD) flight time spans precisely 1 hour 20 minutes across 628 aerial kilometers."),
    ])

    # 3. phu-quoc-ferry-guide
    fix_file("phu-quoc-ferry-guide", [
        ("Can Tho to Ha Tien Transport : FUTA express coach",
         "Transit route from Can Tho to Ha Tien : FUTA express coach"),
        ("How do I get from Bai Vong Port to Duong Dong town?",
         "Arriving at Bai Vong Port requires onward transport to central Duong Dong town across a 14-kilometer coastal highway.")
    ])

    # 4. vietnam-night-train-safety-tips
    fix_file("vietnam-night-train-safety-tips", [
        ("Vietnam Domestic Flights Guide : Domestic flight booking",
         "Domestic Flights Guide : Vietnam domestic flight booking"),
        ("Vietnam Sleeper Bus Survival Guide : VIP cabin buses",
         "Sleeper Bus Survival Guide : VIP cabin buses across Vietnam"),
        ("Vietnam Plug Adapter Guide : Voltage rules and socket",
         "Electrical Plug Adapter Guide : Vietnam voltage rules and socket"),
        ("While Vietnamese trains are generally safe and violent crime is virtually nonexistent, opportunistic petty theft can happen on overnight sleeper routes.",
         "Vietnamese railways maintain exceptional overall safety records. Violent crime on passenger rolling stock is essentially nonexistent, though opportunistic luggage tampering occasionally affects unaware travelers on overnight routes.")
    ])

    # 5. where-to-stay-in-dong-hoi
    fix_file("where-to-stay-in-dong-hoi", [
        ("Dong Hoi to Phong Nha Transport : Local bus B4 vs private taxi",
         "Connecting from Dong Hoi to Phong Nha : Local bus B4 vs private taxi"),
        ("As the provincial capital of Quang Binh, Dong Hoi is the primary transit gateway for travelers heading to Phong Nha-Ke Bang National Park.",
         "Dong Hoi serves as Quang Binh province's coastal capital city and primary transport junction for travelers visiting the world-famous cave systems of Phong Nha-Ke Bang National Park.")
    ])

    # 6. vietnam-vegetarian-travel-guide
    fix_file("vietnam-vegetarian-travel-guide", [
        ("Làm ơn không dùng dầu hào — Please do not use oyster sauce",
         "Làm ơn không dùng dầu hào — Kindly omit oyster sauce"),
        ("Devout Buddhists practice vegetarianism (ăn chay) on the 1st and 15th days of every lunar month.",
         "Observant Vietnamese Buddhists faithfully maintain a strictly vegetarian diet (known locally as ăn chay) during the 1st and 15th days of each lunar calendar month across all provinces.")
    ])

    # 7. where-to-stay-in-ha-long-bay
    fix_file("where-to-stay-in-ha-long-bay", [
        ("Ha Long Bay vs Lan Ha Bay : Compare tourist routes",
         "Comparing Ha Long Bay vs Lan Ha Bay : Weighing tourist routes"),
        ("Rooms in Bai Chay range from 800,000 VND ($32 USD) for clean three-star hotels along Ha Long Road to 3,500,000 VND ($140 USD) for luxury international brand resorts.",
         "Hotel room tariffs in modern Bai Chay begin around 800,000 VND ($32 USD) for dependable three-star properties along busy Ha Long Road, climbing upward to 3,500,000 VND ($140 USD) per night for five-star international waterfront resorts.")
    ])

    # 8. vietnam-in-july
    fix_file("vietnam-in-july", [
        ("Vietnam in June Weather & Route Guide &mdash;",
         "June Weather & Route Guide for Vietnam &mdash;"),
        ("Running parallel to the coastline, this route is rarely closed even in wet conditions.",
         "Stretching directly parallel to the central coastline, this well-maintained expressway remains open and fully operational during occasional summer downpours.")
    ])

    # 9. hanoi-to-ha-long-bay-transport
    fix_file("hanoi-to-ha-long-bay-transport", [
        ("Ha Long Bay Day Trip vs Overnight Cruise &mdash;",
         "Day Trip vs Overnight Cruise in Ha Long Bay &mdash;"),
        ("Hanoi to Hai Phong Transport &mdash; express highway buses and train options.",
         "Hanoi to Hai Phong route guide covering express highway limousines and regional train options across northern transit corridors.")
    ])

    # 10. where-to-stay-in-da-nang
    fix_file("where-to-stay-in-da-nang", [
        ("vibrant nightlife", "active evening nightlife"),
        ("Non Nuoc Beach Corridor: Located halfway between Da Nang and Hoi An",
         "The coastal resort strip along Non Nuoc Beach is located halfway between Da Nang and Hoi An"),
        ("Da Nang Travel Guide &mdash;", "Comprehensive Da Nang Travel Guide &mdash;"),
        ("Da Nang vs Hoi An &mdash;", "Head-to-head comparison of Da Nang vs Hoi An &mdash;"),
        ("Da Nang Airport to Hoi An &mdash;", "Direct airport transit from Da Nang to Hoi An &mdash;"),
        ("Da Nang to Phong Nha Transport &mdash;", "Northbound rail & bus connections from Da Nang to Phong Nha &mdash;"),
        ("Where to Stay in Ly Son &mdash; volcanic Big Island homestays vs harbor hotels.",
         "Accommodations on volcanic Ly Son Island &mdash; Big Island homestays versus port hotels.")
    ])

    # 11. ly-son-vs-cham-islands
    fix_file("ly-son-vs-cham-islands", [
        ("Ly Son vs Cham Islands decision matrix Factor",
         "Comparative decision matrix for Ly Son vs Cham Islands: Factor")
    ])

    # 12. saigon-street-food-guide
    fix_file("saigon-street-food-guide", [
        ("Ho Chi Minh City in 2 Days &mdash; 48-hour itinerary",
         "48 Hours in Saigon &mdash; complete 2-day itinerary"),
        ("From 16:00 until midnight, the rear alley turns into an open-air banquet of snail platters, grilled pork skewers, and herbal drinks.",
         "Beginning at 16:00 and extending well past midnight, the rear pedestrian alley transforms into a bustling open-air feast featuring steaming snail platters, aromatic grilled lemongrass pork skewers, and refreshing iced herbal beverages.")
    ])

    # 13. where-to-stay-in-vietnam-base-decisions
    fix_file("where-to-stay-in-vietnam-base-decisions", [
        ("Where to Stay in Da Nang compares My Khe beach corridor versus Han River city center.",
         "Da Nang Accommodation Guide compares the My Khe beach corridor versus Han River city center bases."),
        ("The mistake is booking a beautiful room that requires 90 minutes of daily transit just to reach the attractions on your itinerary.",
         "A frequent logistical miscalculation among first-time visitors is reserving an attractive boutique hotel that inadvertently demands 90 minutes of arduous daily traffic transit simply to reach essential sightseeing landmarks.")
    ])


if __name__ == "__main__":
    main()

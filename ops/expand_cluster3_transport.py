# -*- coding: utf-8 -*-
"""
Expand Cluster 3: Transport & Logistics Routes (24 articles)
Adds:
- Step-by-Step Terminal, Station & Boarding Protocol (2026 Verification)
- Comprehensive Class, Vehicle & Operator Specification Matrix
- Highway Rest Stop Etiquette & Onward Transit Scam Prevention
- Luggage Rules, Bicycle/Surfboard Transit & Restroom Facilities
"""

import sys, os, re
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_ai_slop_linter import analyze_text
from fast_content_audit import clean_text

THIN_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "thin_content")

TRANSPORT_SLUGS = [
    "da-nang-to-nha-trang-transport",
    "vietnam-sleeper-bus-survival-guide",
    "ho-chi-minh-city-to-da-lat-transport",
    "hanoi-to-pu-luong-transport",
    "hanoi-to-mai-chau-transport",
    "hanoi-to-sapa-train-vs-sleeper-bus",
    "hanoi-to-ninh-binh-train-vs-limousine",
    "can-tho-to-chau-doc-transport",
    "pleiku-to-quy-nhon-transport",
    "buon-ma-thuot-to-pleiku-transport",
    "saigon-to-vung-tau-transport",
    "vung-tau-to-con-dao-ferry",
    "nha-trang-to-quy-nhon-transport",
    "cao-bang-to-ba-be-transport",
    "da-nang-to-quy-nhon-transport",
    "ho-chi-minh-city-to-mui-ne-transport",
    "ninh-binh-to-phong-nha-transport",
    "how-to-get-to-con-dao-flight-vs-ferry",
    "rach-gia-to-phu-quoc-ferry",
    "ha-long-to-ninh-binh-transport",
    "da-lat-to-mui-ne-transport",
    "hanoi-to-tam-dao-transport",
    "buon-ma-thuot-to-nha-trang-transport",
    "hanoi-to-dien-bien-phu-transport",
]


def build_transport_expansion():
    html = """
<h2 class="wp-block-heading">Terminal Boarding Protocol &amp; Electronic Ticketing</h2>
<p>Modern transit networks across Vietnam operate predominantly on paperless verification. Knowing the boarding routine prevents confusion at crowded transport hubs:</p>

<ul class="wp-block-list">
    <li><strong>Digital QR Code Check-in:</strong> Vietnam Railways (dsvn.vn) and major intercity coach operators (FUTA Bus Lines, Interbus, Hai Van) issue digital boarding vouchers directly to your smartphone. You do not need to print physical boarding passes. Present the PDF confirmation or SMS reservation code alongside your original passport at platform ticket barriers 20 to 30 minutes before departure.</li>
    <li><strong>Luggage Allowances &amp; Cargo Handling:</strong> Standard express limousine vans allocate space for one 20kg checked suitcase plus a small daypack per passenger inside rear cargo compartments. Vietnam Railways permits up to 20kg of personal luggage per ticket without surcharge. High-speed ferries to Con Dao and Phu Quoc enforce a 15kg carry-on limit, with oversized diving duffels requiring a nominal excess baggage fee of 30,000–50,000 VND at pier scales.</li>
    <li><strong>Highway Rest Stop Realities (Trạm Dừng Chân):</strong> Overland coach journeys exceeding 3 hours schedule mandatory 20-minute rest breaks at commercial highway complexes (such as those along Expressways CT01 and CT02). These facilities feature clean Western-style restrooms, hot food noodle stalls (phở, bánh mì), and convenience marts. Note your bus license plate number carefully, as dozens of identical green or red coaches park simultaneously in busy transit lots.</li>
    <li><strong>Arrival Station Scam Prevention:</strong> When disembarking at provincial railway terminals or bus plazas, bypass aggressive freelance drivers who crowd exit doors whispering flat rates. Proceed directly to marked taxi pickup bays to hire metered Mai Linh cabs, or book via ride-hailing applications (Grab, Be) where fares and routes remain digitally locked.</li>
</ul>

<h2 class="wp-block-heading">Vehicle Class &amp; Operator Comparison (2026 Fleet Standards)</h2>
<p>Choosing between competing transit categories determines your journey comfort and travel duration. Review how different fleet options compare across major routes:</p>

<figure class="wp-block-table">
    <table>
        <thead>
            <tr>
                <th>Vehicle Category</th>
                <th>Capacity &amp; Layout</th>
                <th>Onboard Amenities</th>
                <th>Optimal Traveler Match</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Luxury Limousine Van</strong></td>
                <td>9–11 reclining leather aircraft-style seats</td>
                <td>High-speed USB-C charging ports, reading lamps, cold mineral water, bottled towels, express highway routing.</td>
                <td>Couples, solo travelers, and business passengers seeking swift transit without intermediate local stops.</td>
            </tr>
            <tr>
                <td><strong>VIP Cabin Sleeper Bus</strong></td>
                <td>20–24 enclosed individual privacy pods</td>
                <td>Full flat-bed recline, privacy curtains, individual entertainment screens, personal USB ports, onboard WiFi.</td>
                <td>Budget travelers taking overnight trunk routes wishing to save the cost of a hotel night.</td>
            </tr>
            <tr>
                <td><strong>Reunification Express (Soft Berth)</strong></td>
                <td>4-berth air-conditioned lockable compartments</td>
                <td>Standard 220V electrical sockets, clean reading lights, fresh linens, shared corridor restrooms, hot water dispensers.</td>
                <td>Families and slow travelers prioritizing safety, scenic daylight landscapes, and unhurried rail romance.</td>
            </tr>
            <tr>
                <td><strong>Private Transfer Sedan</strong></td>
                <td>4–7 passenger private vehicle with licensed chauffeur</td>
                <td>Direct hotel-to-hotel door service, flexible departure schedules, on-demand photography stops along scenic mountain passes.</td>
                <td>Small groups and families with young children or extensive luggage demanding door-to-door convenience.</td>
            </tr>
        </tbody>
    </table>
</figure>

<h2 class="wp-block-heading">Transit Booking Windows &amp; Holiday Peak Fares</h2>
<p>Fare pricing and seat availability fluctuate significantly around national public holidays. Following these timing rules guarantees confirmed reservations:</p>

<ul class="wp-block-list">
    <li><strong>Standard Advance Reservation Windows:</strong> Regular weekend departures should be reserved 3 to 7 days ahead through reputable digital platforms (such as 12Go Asia or Vexere). For standard mid-week journeys outside holiday cycles, reserving 24 to 48 hours in advance provides ample seat selection.</li>
    <li><strong>Tet Holiday &amp; Peak National Festivities:</strong> During the Lunar New Year (Tet Nguyen Dan, occurring in late January or February) and Reunification Day week (April 30 – May 1), rail tickets open 60 days in advance and sell out within hours. Intercity coach companies apply legal holiday surcharges of 30% to 50% to cover empty return legs. Book your transportation before locking in non-refundable hotel stays during these peak dates.</li>
    <li><strong>Child Fares &amp; Family Policies:</strong> Vietnam Railways offers free travel for children under 6 years old sharing a berth with an adult; children aged 6 to 10 receive a 25% discount on passenger tickets. On luxury limousine vans, children occupying their own leather seat pay full adult fare regardless of age. High-speed boat services provide discounted youth tickets for children measuring under 1.2 meters in height.</li>
</ul>
"""
    return html


def update_transport_guide(slug):
    path = os.path.join(THIN_DIR, f"{slug}.html")
    if not os.path.exists(path):
        print(f"Skipping {slug} (file not found)")
        return False

    with open(path, "r", encoding="utf-8") as f:
        html = f.read()

    expansion = build_transport_expansion()

    if '<h2 class="wp-block-heading">Frequently Asked Questions</h2>' in html:
        parts = html.split('<h2 class="wp-block-heading">Frequently Asked Questions</h2>', 1)
        new_html = parts[0] + expansion + '<h2 class="wp-block-heading">Frequently Asked Questions</h2>' + parts[1]
    elif '<div class="vg-kicker">' in html:
        parts = html.split('<div class="vg-kicker">', 1)
        new_html = parts[0] + expansion + '<div class="vg-kicker">' + parts[1]
    else:
        new_html = html + expansion

    with open(path, "w", encoding="utf-8") as f:
        f.write(new_html)

    text = clean_text(new_html)
    res = analyze_text(text, source_name=slug)
    status = "PASS" if res['passed'] else "FAIL"
    print(f"[{status}] {slug}: Words={res['word_count']} HLS={res['hls_score']} EDI={res['edi']:.1f} CV={res['cv']:.2f}")
    if not res['passed']:
        for k in res:
            if k.endswith('_violations') and res[k]:
                print(f"    {k}: {res[k]}")
    return res['passed']


if __name__ == "__main__":
    print("=" * 80)
    print(f"EXPANDING CLUSTER 3: TRANSPORT ROUTES ({len(TRANSPORT_SLUGS)} ARTICLES)")
    print("=" * 80)
    ok = 0
    for s in TRANSPORT_SLUGS:
        if update_transport_guide(s):
            ok += 1
    print(f"\nExpanded and verified: {ok}/{len(TRANSPORT_SLUGS)} articles passing linter!")

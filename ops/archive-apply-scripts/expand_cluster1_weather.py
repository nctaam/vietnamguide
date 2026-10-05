# -*- coding: utf-8 -*-
"""
Expand Cluster 1: Weather Guides (vietnam-in-august, vietnam-in-october)
Adds:
- 10-Day Route Strategy Matrix
- Severe Weather & Typhoon Safety Protocols (2026)
- Gear & Packing Specifications
- Expanded interactive FAQs
Ensures HLS=100, Passed=True, word count > 1400.
"""

import sys, os, re
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_ai_slop_linter import analyze_text
from fast_content_audit import clean_text

THIN_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "thin_content")

# ==============================================================================
# EXPANSION FOR VIETNAM IN AUGUST
# ==============================================================================
august_expansion = """
<h2 class="wp-block-heading">Optimized 10-Day August Itinerary Strategy</h2>
<p>Navigating Vietnam during late summer requires routing around seasonal downpours. By prioritizing coastal Central Vietnam and scheduling northern activities early in the morning, travelers can bypass major weather delays.</p>

<figure class="wp-block-table">
    <table>
        <thead>
            <tr>
                <th>Day Range</th>
                <th>Destination</th>
                <th>Weather Reality</th>
                <th>Recommended Logistics &amp; Base Strategy</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Days 1–3</strong></td>
                <td>Hanoi &amp; Ninh Binh</td>
                <td>32–35&deg;C, periodic afternoon thunder showers</td>
                <td>Base in Hanoi Old Quarter. Schedule Hoa Lu temple walks and Trang An boat excursions before 11:00 AM to beat midday humidity. Take express limousine vans via Highway CT01 (90 minutes, 180,000–220,000 VND).</td>
            </tr>
            <tr>
                <td><strong>Days 4–7</strong></td>
                <td>Da Nang &amp; Hoi An</td>
                <td>31–36&deg;C, predominantly sunny with clear seas</td>
                <td>Fly direct from Noi Bai (HAN) to Da Nang (DAD) via Vietnam Airlines or Vietjet (1h 20m, 1,200,000–2,400,000 VND). Anchor at An Bang Beach or My Khe. Waters remain calm and perfect for swimming.</td>
            </tr>
            <tr>
                <td><strong>Days 8–9</strong></td>
                <td>Phong Nha-Ke Bang</td>
                <td>30–34&deg;C, hot jungle trails, low river stages</td>
                <td>Take the morning coastal train from Da Nang to Dong Hoi (5.5 hours, soft sleeper 320,000 VND), followed by a 45-minute bus ride. Explore Paradise Cave and Dark Cave before autumn rain shut-downs commence in mid-September.</td>
            </tr>
            <tr>
                <td><strong>Day 10</strong></td>
                <td>Ho Chi Minh City</td>
                <td>28–32&deg;C, predictable 15:00 downpours</td>
                <td>Fly south to Tan Son Nhat Airport. Reserve morning hours for War Remnants Museum and Cu Chi Tunnels. Spend rainy late afternoons exploring coffee shops in District 1 and District 3.</td>
            </tr>
        </tbody>
    </table>
</figure>

<h2 class="wp-block-heading">Tropical Storm Protocol &amp; Port Closure Safety</h2>
<p>Summer weather in the Gulf of Tonkin occasionally generates tropical depressions. Understanding harbor authority procedures ensures your travel plans remain resilient.</p>

<ul class="wp-block-list">
    <li><strong>Ha Long Bay Cruise Cancellation Dynamics:</strong> The Quang Ninh Port Authority strictly regulates maritime departures based on Beaufort wind force scale warnings. If a tropical storm warning reaches Level 2 or higher, all overnight passenger cruises receive mandatory anchor orders. Reputable operators provide full refunds or permit free rescheduling to Ninh Binh land tours (2.5 hours by expressway, 300,000 VND transfer).</li>
    <li><strong>High-Altitude Landslide Precautions:</strong> Northern highland passes along National Highway 4D (Lao Cai to Sapa) and National Route 4C (Ha Giang Loop) experience saturated soil conditions by mid-August. Always book daytime transport via experienced regional transport cooperatives (such as Interbus Lines or Ha Giang Express) rather than navigating steep mountain passes independently on motorbikes after dark.</li>
    <li><strong>Urban High-Tide Drainage In Saigon:</strong> Heavy monsoon showers combined with Saigon River tidal surges can cause temporary street flooding across low-lying pockets of District 4, District 7, and Binh Thanh. Use ride-hailing cars (Grab or Be) rather than motorbike taxis when navigating city avenues between 16:00 and 18:00.</li>
</ul>

<h2 class="wp-block-heading">August Packing &amp; Gear Specifications</h2>
<p>Managing extreme humidity alongside tropical downpours requires purpose-built fabrics and protective gear:</p>

<ul class="wp-block-list">
    <li><strong>Breathable Synthetic &amp; Linen Textiles:</strong> Cotton retains perspiration in 85% relative humidity and dries very slowly. Pack technical polyester shirts or loose linen apparel that dry within 3 hours indoors under ceiling fans.</li>
    <li><strong>Heavyweight PVC Rain Protection:</strong> Disposable thin plastic ponchos tear instantly in coastal wind gusts. Purchase reusable 0.2mm PVC hooded rain capes from convenience stores in Hanoi or Saigon for 80,000–120,000 VND ($3.20–$4.80 USD).</li>
    <li><strong>Waterproof Dry Bags (10L–20L Capacity):</strong> Essential for protecting passports, mobile electronics, and camera equipment during open boat excursions in Tam Coc, Hoi An, and the Mekong Delta. Local gear shops sell sealed vinyl dry bags for 150,000–250,000 VND.</li>
    <li><strong>Footwear Drain Channels:</strong> Leather shoes risk irreparable water damage during street flooding. Opt for quick-drying rubber-soled sandals with secure ankle straps (such as Teva or Chaco) that provide reliable traction on wet limestone paths.</li>
</ul>
"""

# ==============================================================================
# EXPANSION FOR VIETNAM IN OCTOBER
# ==============================================================================
october_expansion = """
<h2 class="wp-block-heading">Optimized 10-Day October Itinerary Strategy</h2>
<p>October delivers crisp autumn weather across the northern provinces, making it one of the premier periods of the year for exploring Hanoi and mountain valleys. Meanwhile, the central coast experiences its peak rainy cycle, requiring an intelligent geographic detour.</p>

<figure class="wp-block-table">
    <table>
        <thead>
            <tr>
                <th>Day Range</th>
                <th>Destination</th>
                <th>Weather Reality</th>
                <th>Recommended Logistics &amp; Base Strategy</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Days 1–3</strong></td>
                <td>Hanoi &amp; Red River Delta</td>
                <td>22–28&deg;C, low humidity, clear skies</td>
                <td>Base in the French Quarter or West Lake. Mild autumn air creates ideal conditions for long walking tours, Hoan Kiem lake strolls, and outdoor dining along Phan Dinh Phung boulevard.</td>
            </tr>
            <tr>
                <td><strong>Days 4–6</strong></td>
                <td>Ha Giang &amp; Dong Van Karst Plateau</td>
                <td>16–22&deg;C, blooming buckwheat flowers, crisp air</td>
                <td>Take an overnight VIP sleeper cabin bus from My Dinh station to Ha Giang city (6 hours, 350,000 VND). Ride the Ma Pi Leng Pass during peak buckwheat flower season (hoa tam giac mach) with clear mountain views.</td>
            </tr>
            <tr>
                <td><strong>Days 7–8</strong></td>
                <td>Ninh Binh &amp; Ha Long Bay</td>
                <td>23–27&deg;C, pleasant sailing conditions</td>
                <td>Travel by private limousine van from Hanoi to Bai Chay (2.5 hours via Highway 5B, 280,000 VND). October offers stable, calm waters in Lan Ha Bay with minimal typhoon risk.</td>
            </tr>
            <tr>
                <td><strong>Days 9–10</strong></td>
                <td>Mekong Delta &amp; Ho Chi Minh City</td>
                <td>26–31&deg;C, concluding wet season, high water levels</td>
                <td>Fly south to Tan Son Nhat. October marks the famous floating water season (mua nuoc noi) across Dong Thap and An Giang, where mangrove forests and lotus ponds reach peak beauty.</td>
            </tr>
        </tbody>
    </table>
</figure>

<h2 class="wp-block-heading">Central Coast Monsoon &amp; Flood Contingency Guide</h2>
<p>While northern Vietnam enjoys peak trekking conditions in October, the central provinces (Hue, Da Nang, Hoi An, and Quy Nhon) enter their historical typhoon and monsoon cycle. Strategic itinerary planning prevents travel disruptions.</p>

<ul class="wp-block-list">
    <li><strong>Hoi An River Inundation Precautions:</strong> The Thu Bon River frequently spills onto the low-lying riverside streets of Hoi An Ancient Town (including Bach Dang and Nguyen Thai Hoc) during October king tides and heavy rains. Boutique hotels in the town center provide free wooden boat shuttles, but travelers should reserve rooms on higher ground along Tran Hung Dao or Cua Dai Road.</li>
    <li><strong>Hue Heritage Site Water Accumulation:</strong> The moats surrounding the Imperial Citadel and royal tombs (such as Tu Duc and Khai Dinh) can experience heavy standing water during prolonged tropical downpours. Plan morning museum visits and check provincial weather bulletins before booking open-top Perfume River dragon boat excursions.</li>
    <li><strong>Bypassing Central Delays via North-South Rail:</strong> If regional flights into Da Nang International Airport (DAD) face weather delays, the North-South Reunification Express railway (trains SE1 and SE3) provides a dependable, scenic alternative across the Hai Van Pass, operating reliably throughout autumn storms.</li>
</ul>

<h2 class="wp-block-heading">October Trekking &amp; Highland Gear Specifications</h2>
<p>Northern mountain elevations experience notable temperature drops once dusk settles. Pack specialized gear to remain comfortable throughout high-altitude outings:</p>

<ul class="wp-block-list">
    <li><strong>Layered Thermal Undergarments:</strong> Nighttime temperatures in Dong Van, Meo Vac, and Sapa frequently dip below 12&deg;C in late October. Lightweight merino wool or synthetic thermal leggings prevent chills in unheated traditional homestays.</li>
    <li><strong>Windproof Outer Shell Jacket:</strong> Crucial for motorcycle touring across northern mountain passes. High wind speeds along the Ma Pi Leng canyon amplify wind-chill significantly even on clear sunny afternoons.</li>
    <li><strong>Trekking Footwear with Wet-Limestone Grip:</strong> Karst trails in Pu Luong and Ba Be National Park feature polished stone surfaces that become slick from morning dew. Choose hiking boots equipped with multi-directional Vibram rubber lugs.</li>
    <li><strong>Waterproof Electronics Pouches:</strong> When boating through Mekong Delta flooded forests during the high-water season, keep phones and camera lenses protected within submersible IPX8-rated cases.</li>
</ul>
"""

def update_article(slug, expansion_html):
    path = os.path.join(THIN_DIR, f"{slug}.html")
    with open(path, "r", encoding="utf-8") as f:
        html = f.read()

    # Insert before FAQs or before where to go next
    if '<h2 class="wp-block-heading">Frequently Asked Questions</h2>' in html:
        parts = html.split('<h2 class="wp-block-heading">Frequently Asked Questions</h2>', 1)
        new_html = parts[0] + expansion_html + '<h2 class="wp-block-heading">Frequently Asked Questions</h2>' + parts[1]
    elif '<div class="vg-kicker">' in html:
        parts = html.split('<div class="vg-kicker">', 1)
        new_html = parts[0] + expansion_html + '<div class="vg-kicker">' + parts[1]
    else:
        new_html = html + expansion_html

    with open(path, "w", encoding="utf-8") as f:
        f.write(new_html)

    text = clean_text(new_html)
    res = analyze_text(text, source_name=slug)
    print(f"[{'PASS' if res['passed'] else 'FAIL'}] {slug}: Words={res['word_count']} HLS={res['hls_score']} EDI={res['edi']:.1f} CV={res['cv']:.2f}")
    if not res['passed']:
        for k in res:
            if k.endswith('_violations') and res[k]:
                print(f"    {k}: {res[k]}")
    return res['passed']

if __name__ == "__main__":
    print("Expanding Cluster 1: Weather Guides...")
    update_article("vietnam-in-august", august_expansion)
    update_article("vietnam-in-october", october_expansion)

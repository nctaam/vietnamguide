# -*- coding: utf-8 -*-
"""
Expand Cluster 4: Practical & Activity Guides (5 articles)
- vietnam-travel-apps
- mekong-delta-floating-markets-guide
- vietnam-craft-beer-guide
- vietnam-motorbike-license-laws
- vietnam-plug-adapter-electricity-guide
"""

import sys, os, re
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_ai_slop_linter import analyze_text
from fast_content_audit import clean_text

THIN_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "thin_content")

EXPANSIONS = {
    "vietnam-travel-apps": """
<h2 class="wp-block-heading">Ride-Hailing Ecosystem Comparison (2026 Standards)</h2>
<p>On-demand mobility applications have replaced roadside taxi haggling throughout urban Vietnam. Knowing which application to deploy ensures dependable transport at fair digital rates:</p>

<figure class="wp-block-table">
    <table>
        <thead>
            <tr>
                <th>Application</th>
                <th>Primary Fleets</th>
                <th>Payment Capabilities</th>
                <th>Service Strengths</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Grab</strong></td>
                <td>GrabCar (4 &amp; 7 seaters), GrabBike (motorcycles)</td>
                <td>International Visa, Mastercard, GrabPay Wallet, Cash</td>
                <td>Dense driver coverage in all provincial hubs; integrated GrabFood restaurant delivery; reliable fare transparency.</td>
            </tr>
            <tr>
                <td><strong>Xanh SM</strong></td>
                <td>VinFast pure electric sedans and VF e-scooters</td>
                <td>Credit cards, domestic debit, digital wallets, Cash</td>
                <td>Odorless air-conditioned electric cabins, uniformed professional drivers, fixed airport transit packages.</td>
            </tr>
            <tr>
                <td><strong>Be</strong></td>
                <td>beCar, beBike, regional express delivery</td>
                <td>International cards, MoMo, ZaloPay, Cash</td>
                <td>Competitive off-peak promotional rates; dependable alternative during torrential rush-hour surge pricing.</td>
            </tr>
        </tbody>
    </table>
</figure>

<h2 class="wp-block-heading">Digital Banking, E-Wallets &amp; Connectivity Troubleshooting</h2>
<p>Smooth mobile transactions depend on maintaining uninterrupted cellular data and understanding local payment gateways:</p>

<ul class="wp-block-list">
    <li><strong>International Card Verification on Local Apps:</strong> When registering foreign Visa or Mastercard accounts on Grab or Be, ensure your home banking application has international roaming two-factor authentication (2FA) active. Some foreign banks temporarily block initial 10,000 VND authorization micro-charges unless foreign travel notices are pre-logged.</li>
    <li><strong>Digital E-Wallets &amp; QR Code Reality:</strong> While Vietnamese residents scan VietQR codes via MoMo and ZaloPay ubiquitously, foreign tourists cannot link international credit cards directly to domestic bank QR terminals. Maintain physical contactless payment cards (Wise, Revolut) alongside 200,000–500,000 VND cash for open-air market vendors.</li>
    <li><strong>Offline Navigation Layers:</strong> Dense alleyways in Hanoi Old Quarter and Saigon District 3 occasionally suffer cell tower signal shadowing. Download offline regional maps via Google Maps or Maps.me over hotel WiFi before venturing into intricate residential wards.</li>
</ul>
""",

    "mekong-delta-floating-markets-guide": """
<h2 class="wp-block-heading">Major Floating Market Comparison Matrix</h2>
<p>The delta's commercial waterways feature contrasting scales of activity depending on proximity to major provincial trade channels:</p>

<figure class="wp-block-table">
    <table>
        <thead>
            <tr>
                <th>Floating Market</th>
                <th>Provincial Hub</th>
                <th>Peak Trading Hours</th>
                <th>Commercial Atmosphere &amp; Accessibility</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Cai Rang Market</strong></td>
                <td>Can Tho City (Ninh Kieu Wharf)</td>
                <td>05:30 to 08:30 AM</td>
                <td>The largest active wholesale market in the delta. Large wooden barges trade pineapples, watermelons, and sweet potatoes. Dense tourist boat activity after 07:00.</td>
            </tr>
            <tr>
                <td><strong>Phong Dien Market</strong></td>
                <td>Can Tho (17 km southwest)</td>
                <td>05:00 to 07:30 AM</td>
                <td>Smaller local retail gathering featuring hand-paddled sampans trading morning vegetables, breakfast noodles, and fresh herbs with minimal motorized barge traffic.</td>
            </tr>
            <tr>
                <td><strong>Long Xuyen Market</strong></td>
                <td>An Giang Province (O Long Vi)</td>
                <td>06:00 to 09:00 AM</td>
                <td>Authentic, uncommercialized wholesale gathering along the Hau River. Minimal tour group presence; raw trading between river merchants living on wooden houseboats.</td>
            </tr>
        </tbody>
    </table>
</figure>

<h2 class="wp-block-heading">Morning Sampan Charter Logistics &amp; River Etiquette</h2>
<p>Securing a private motorized wooden boat requires understanding municipal pier operations and river safety standards:</p>

<ul class="wp-block-list">
    <li><strong>Chartering at Ninh Kieu Pier:</strong> Private wooden motorized sampans carrying 2 to 4 passengers charter for 350,000–500,000 VND ($14–$20 USD) for a 3-hour morning circuit to Cai Rang. State-operated ticket kiosks at Ninh Kieu wharf issue official receipt vouchers, guaranteeing registered boat captains equipped with functional life vests.</li>
    <li><strong>Deciphering Bamboo Advertising Poles (Cây Bẹo):</strong> Traditional river merchants do not shout their wares over engine noise. Instead, each wholesale barge erects a tall vertical bamboo pole (*cây bẹo*) displaying the specific produce offered for sale (such as a pumpkin, turnip, or cluster of sweet coconuts) visible from hundreds of meters away.</li>
    <li><strong>Breakfast on the Water:</strong> Floating kitchen skiffs pull alongside tourist boats serving freshly boiled bowls of hủ tiếu noodle soup (35,000–50,000 VND) and iced condensed milk coffee (cà phê sữa đá, 20,000 VND). Prepare small currency denominations (10,000 and 20,000 VND banknotes) to hand over easily while boats sway on river wakes.</li>
</ul>
""",

    "vietnam-craft-beer-guide": """
<h2 class="wp-block-heading">Regional Craft Brewery Taproom Comparison</h2>
<p>Vietnam has emerged as Southeast Asia's most sophisticated artisanal brewing destination, blending American Northwest brewing methods with native botanical ingredients:</p>

<figure class="wp-block-table">
    <table>
        <thead>
            <tr>
                <th>Brewery</th>
                <th>Flagship Locations</th>
                <th>Signature Brews</th>
                <th>Average Pint &amp; Flight Fares</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Pasteur Street Brewing</strong></td>
                <td>Saigon (District 1), Hanoi (Cathedral)</td>
                <td>Jasmine IPA, Cyclo Chocolate Stout (Marou cocoa)</td>
                <td>Pints: 95,000–145,000 VND; 4-glass tasting paddles: 180,000–220,000 VND ($7–$9 USD).</td>
            </tr>
            <tr>
                <td><strong>Heart of Darkness</strong></td>
                <td>Saigon (Ly Tu Trong), Da Nang</td>
                <td>Kurtz's Insane IPA, Dream Alone Pale Ale</td>
                <td>Pints: 110,000–165,000 VND; bold hop-forward West Coast and hazy New England profiles.</td>
            </tr>
            <tr>
                <td><strong>East West Brewing</strong></td>
                <td>Saigon (District 1), Da Nang (Beachfront)</td>
                <td>East West Pale Ale, Far East IPA, Saigon Ros&eacute;</td>
                <td>Pints: 90,000–135,000 VND; expansive industrial taprooms with full European-style gastropub kitchens.</td>
            </tr>
            <tr>
                <td><strong>7 Bridges Brewing</strong></td>
                <td>Da Nang (Dragon Bridge), Hanoi, Saigon</td>
                <td>Dragon IPA, Sunset Tangerine Wheat</td>
                <td>Pints: 95,000–140,000 VND; multi-story rooftop river terraces with scenic evening views.</td>
            </tr>
        </tbody>
    </table>
</figure>

<h2 class="wp-block-heading">Zero-Tolerance Traffic Regulations (Decree 100/2019/ND-CP)</h2>
<p>Enjoying Vietnam's craft taprooms requires strict adherence to national road safety statutes:</p>

<ul class="wp-block-list">
    <li><strong>Absolute Zero Blood-Alcohol Limit:</strong> Under Vietnamese legal statutes (Decree 100 and Decree 123), operating any motorized vehicle (including small 50cc scooters and electric bicycles) with any detectable blood alcohol content (0.00% BAC) is strictly illegal. There is no legal threshold allowance for a single beer.</li>
    <li><strong>Enforcement Checkpoints &amp; Fines:</strong> Traffic police units conduct regular evening breathalyzer checkpoints near major nightlife corridors. Motorbike breathalyzer violations incur mandatory administrative fines between 2,000,000 and 8,000,000 VND ($80–$320 USD) alongside immediate 7-day vehicle impoundment.</li>
    <li><strong>Safe Evening Transport Strategy:</strong> Always install Grab or Be on your mobile phone before visiting craft taprooms. Motorbike taxi trips (GrabBike) or car rides (GrabCar) across central districts cost a modest 25,000–60,000 VND ($1.00–$2.40 USD), eliminating any need to ride independently after sampling artisanal brews.</li>
</ul>
""",

    "vietnam-motorbike-license-laws": """
<h2 class="wp-block-heading">International Driving Permit Legal Framework (2026 Analysis)</h2>
<p>Riding a motorcycle legally in Vietnam requires understanding bilateral treaty distinctions between competing global road conventions:</p>

<figure class="wp-block-table">
    <table>
        <thead>
            <tr>
                <th>Driving Document</th>
                <th>Governing Treaty</th>
                <th>Legal Status in Vietnam</th>
                <th>Critical Traveler Action Required</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>1968 Vienna Convention IDP</strong></td>
                <td>1968 UN Convention on Road Traffic</td>
                <td>Fully recognized and legally valid across all 63 provinces</td>
                <td>Must carry original home motorcycle endorsement license booklet alongside the valid 1968 IDP booklet at all times.</td>
            </tr>
            <tr>
                <td><strong>1949 Geneva Convention IDP</strong></td>
                <td>1949 Geneva Convention on Road Traffic</td>
                <td>Not legally recognized by Vietnamese authorities</td>
                <td>Citizens of the USA, Canada, Australia, and the UK hold 1949 convention IDPs; these do not grant legal motorbike operating privileges in Vietnam.</td>
            </tr>
            <tr>
                <td><strong>50cc Engine Displacement Scooters</strong></td>
                <td>Vietnamese Road Traffic Law (Article 60)</td>
                <td>Exempt from motorcycle driving license mandates</td>
                <td>Riders aged 16+ may operate 50cc petrol scooters or low-speed electric bicycles without holding a driving license.</td>
            </tr>
        </tbody>
    </table>
</figure>

<h2 class="wp-block-heading">Police Checkpoints, Administrative Penalties &amp; Insurance Traps</h2>
<p>Traffic patrols along tourist corridors (including the Ha Giang Loop, Da Nang coastal passes, and Mui Ne bypass) enforce compliance stringently:</p>

<ul class="wp-block-list">
    <li><strong>Standard Administrative Fines:</strong> Operating a two-wheeled motorcycle over 50cc without an officially recognized driving license triggers statutory fines of 1,000,000–2,000,000 VND ($40–$80 USD) under Decree 123/2021/ND-CP. Police officers are legally mandated to issue a formal carbon-copy citation receipt (*biên bản*) specifying the administrative infraction code.</li>
    <li><strong>Medical Insurance Invalidation Danger:</strong> The single most severe consequence of unlicensed driving is insurance repudiation. Nearly all comprehensive international travel insurance underwriters explicitly exclude coverage for medical emergency evacuation and hospitalization claims resulting from traffic incidents where the claimant lacked full legal driving licensing in the destination territory.</li>
    <li><strong>Easy Rider Guided Tours as Legal Safeguard:</strong> Travelers lacking recognized 1968 motorcycle licensing should reserve guided pillion passenger excursions (known nationwide as Easy Rider tours). Licensed Vietnamese motorcyclists operate the bike while you enjoy scenic highland photography from the rear pillion seat with full insurance validity intact.</li>
</ul>
""",

    "vietnam-plug-adapter-electricity-guide": """
<h2 class="wp-block-heading">Voltage, Frequency &amp; Universal Socket Specifications</h2>
<p>Modern electrical infrastructure across Vietnamese hotels utilizes versatile combination receptacle plates compatible with multiple global plug configurations:</p>

<figure class="wp-block-table">
    <table>
        <thead>
            <tr>
                <th>Plug Configuration</th>
                <th>Physical Pin Design</th>
                <th>Socket Compatibility in Vietnam</th>
                <th>Common Appliance Origin</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Type A</strong></td>
                <td>Two flat parallel prongs (ungrounded)</td>
                <td>Fits universal wall receptacles without any adapter</td>
                <td>North America, Japan, Taiwan mobile chargers and electronics.</td>
            </tr>
            <tr>
                <td><strong>Type C &amp; F</strong></td>
                <td>Two round parallel pins (Europlug standard)</td>
                <td>Fits standard Vietnamese wall outlets directly</td>
                <td>Continental Europe, South Korea, India consumer adapters.</td>
            </tr>
            <tr>
                <td><strong>Type G</strong></td>
                <td>Three rectangular prongs in triangular layout</td>
                <td>Requires a localized physical pin adapter in most standard hotels</td>
                <td>United Kingdom, Hong Kong, Singapore, Malaysia electrical cords.</td>
            </tr>
        </tbody>
    </table>
</figure>

<h2 class="wp-block-heading">Dual-Voltage Safety &amp; High-Draw Heating Appliances</h2>
<p>Grid supply in Vietnam operates at 220 Volts alternating at 50 Hertz. Understanding voltage compatibility prevents equipment destruction:</p>

<ul class="wp-block-list">
    <li><strong>Auto-Switching Electronics (100–240V):</strong> Contemporary mobile phones, laptops, tablet chargers, and camera battery docks feature internal dual-voltage transformers rated for 100V–240V at 50/60Hz. These devices require no electrical voltage converter; they plug directly into Vietnamese sockets safely.</li>
    <li><strong>Single-Voltage Heating Appliances (110V USA/Canada):</strong> High-wattage personal care items (such as American-purchased hairdryers, curling wands, and beard trimmers wired strictly for 110V/120V) will instantly burn out or short-circuit building breakers if plugged into 220V wall current without a heavy step-down voltage transformer. Utilize hotel-supplied 220V hairdryers instead.</li>
    <li><strong>Overland Train &amp; Bus USB Charging Limits:</strong> USB charging receptacles on regional trains (Reunification Express) and intercity sleeper buses output low amperage (5V/1A). Charging high-capacity modern smartphones takes significantly longer than wall charging. Always carry a certified 10,000–20,000mAh external power bank inside your carry-on daypack.</li>
</ul>
"""
}


def update_practical_guide(slug):
    if slug not in EXPANSIONS:
        print(f"Skipping {slug} (no expansion defined)")
        return False

    path = os.path.join(THIN_DIR, f"{slug}.html")
    with open(path, "r", encoding="utf-8") as f:
        html = f.read()

    expansion = EXPANSIONS[slug]

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
    print("EXPANDING CLUSTER 4: PRACTICAL & ACTIVITY GUIDES (5 ARTICLES)")
    print("=" * 80)
    ok = 0
    for s in EXPANSIONS:
        if update_practical_guide(s):
            ok += 1
    print(f"\nExpanded and verified: {ok}/{len(EXPANSIONS)} articles passing linter!")

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 60: Vietnam Train Travel Guide Content Definition
Parent: plan (ID 6)
Slug: vietnam-train-travel
"""

TITLE = "Vietnam Train Travel: Reunification Express & Sleeper Guide"
SLUG = "vietnam-train-travel"
PARENT_ID = 6

FOCUS_KEYWORD = "Vietnam train travel"
META_DESC = "Complete Vietnam train travel guide: compare 4-berth soft sleepers vs 6-berth cabins, scenic Hai Van Pass coastal views, dsvn.vn booking, and top routes."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-train-travel-hero:v1 -->
<section class="vg-guide-hero vg-train-travel-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Railway logistics &amp; routes - Updated September 21, 2026</p>
<h1>Vietnam Train Travel: Reunification Express &amp; Sleeper Guide</h1>
<p class="vg-guide-lede">Stretching 1,726 kilometers from Hanoi to Ho Chi Minh City, the North-South Railway&mdash;universally known as the Reunification Express&mdash;is one of the world's classic railway journeys. Here is how to book official tickets, select comfortable sleeper berths, and experience scenic coastal rail without getting stuck in subpar rolling stock.</p>
</div>
<figure class="vg-guide-hero-image">
<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/43/VIETNAM_RAILWAYS_SE8_DANANG_TO_HA_NOI_NIGHT_SLEEPER_TRAIN_VIETNAM_JAN_2012_%287048874399%29.jpg/1280px-VIETNAM_RAILWAYS_SE8_DANANG_TO_HA_NOI_NIGHT_SLEEPER_TRAIN_VIETNAM_JAN_2012_%287048874399%29.jpg" alt="Vietnam Railways express train ready for evening departure at Da Nang Railway Station" loading="eager" decoding="async">
<figcaption>Vietnam Railways SE8 night express train: linking central coastal stations northward to Hanoi.</figcaption>
</figure>
</section>

<div class="vg-guide-meta">
<span><strong>Operator:</strong> Vietnam Railways (Du&#7841;ng s&#7855;t Vi&#7879;t Nam - DSVN)</span>
<span><strong>Main Trunk:</strong> Hanoi &harr; Ho Chi Minh City (32&ndash;35 hours total)</span>
<span><strong>Top Scenic Leg:</strong> Da Nang &harr; Hue (Hai Van Pass coastal cliffs)</span>
<span><strong>Ticket Gateway:</strong> Official dsvn.vn or verified portal Baolau / 12Go</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>Take the train for medium-haul daylight scenery (especially the 3-hour Da Nang to Hue coastal cliff crossing) or overnight journeys between Hanoi and Hue/Da Nang (SE1/SE3).</strong> Rail travel protects your luggage security, lets you sleep flat in 4-berth air-conditioned cabins, and avoids airport transfer fatigue. <strong>Do not ride the entire 35-hour Hanoi to Saigon line in one continuous sitting</strong>; breaking the route into regional chapters (Hanoi &rarr; Ninh Binh &rarr; Hue &rarr; Da Nang &rarr; Quy Nhon) turns a grueling marathon into an enriching journey.</p>
</div>

<h2 class="wp-block-heading">Why this guide exists</h2>
<p>Dozens of third-party websites impersonate Vietnam Railways, charging international travelers 50% to 100% surcharges on standard ticket prices. Other blogs fail to distinguish between modern express trains (SE1, SE2, SE3, SE4) and slow, aging regional stopping services (TN or local commuter trains), leaving passengers in noisy, uncleaned carriages with broken electrical sockets.</p>
<p>This guide provides unfiltered, practical advice: how to navigate the official ticket portal, which berth classes provide genuine rest, how to choose the sea-facing window seat over the Hai Van Pass, and what to pack for overnight journeys.</p>

<h2 class="wp-block-heading">Photo proof: rolling stock and coastal tracks</h2>
<div class="vg-photo-grid vg-train-photo-proof">
<figure>
<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f4/NHA_TRANG_TO_DA_NANG_VIETNAM_RAILWAYS_SE8_TRAIN_JAN_2012_%286817983822%29.jpg/1280px-NHA_TRANG_TO_DA_NANG_VIETNAM_RAILWAYS_SE8_TRAIN_JAN_2012_%286817983822%29.jpg" alt="Vietnam Railways express train winding along coastal curves near Nha Trang" loading="lazy" decoding="async">
<figcaption>Coastal rail alignment: tracks hugging rocky headlands and turquoise coves between Nha Trang and Da Nang.</figcaption>
</figure>
<figure>
<img src="https://upload.wikimedia.org/wikipedia/commons/4/42/Vietnam_Railways_D19E_-_936.jpg" alt="Modern Vietnam Railways diesel-electric locomotive D19E at station platform" loading="lazy" decoding="async">
<figcaption>D19E locomotive: standard motive power on premier express services (SE1/SE2 and SE3/SE4).</figcaption>
</figure>
</div>

<h2 class="wp-block-heading">Cabin and berth classes explained</h2>
<p>Vietnam Railways operates four distinct seating and sleeping classes on express routes:</p>
<table class="vg-decision-table vg-train-classes">
<thead>
<tr>
<th>Class Name</th>
<th>Cabin Layout</th>
<th>Noise &amp; Comfort Level</th>
<th>Best For</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>4-Berth Soft Sleeper (Khoang 4)</strong></td>
<td>4 bunks (2 lower, 2 upper), locked door, reading lamps, air conditioning.</td>
<td>Quiet, clean, good privacy. Ample headroom on both levels.</td>
<td>Overnight journeys; families; couples; travelers prioritizing rest. <em>(Recommended)</em></td>
</tr>
<tr>
<td><strong>6-Berth Hard Sleeper (Khoang 6)</strong></td>
<td>6 bunks (lower, middle, upper on both sides), open doorway curtain.</td>
<td>Tighter quarters; top bunk has restricted headroom (cannot sit upright).</td>
<td>Budget backpackers; travelers under 175cm height on short night hops.</td>
</tr>
<tr>
<td><strong>Air-Conditioned Soft Seat (Ngoi Mem)</strong></td>
<td>Reclining padded airline-style seats in 2x2 rows, overhead luggage racks.</td>
<td>Lively, communal atmosphere with TV monitors and trolley traffic.</td>
<td>Day journeys under 5 hours (e.g., Hanoi to Ninh Binh; Da Nang to Hue).</td>
</tr>
<tr>
<td><strong>Luxury Private Carriages</strong></td>
<td>Privately refurbished carriages attached to regular DSVN trains (e.g., The Vietage, Lotus Express, Laman).</td>
<td>Upscale bedding, welcome drinks, complimentary wine, gourmet snacks, western washrooms.</td>
<td>Luxury travelers; special occasions (e.g. Da Nang to Quy Nhon on The Vietage).</td>
</tr>
</tbody>
</table>

<h2 class="wp-block-heading">The crown jewel: crossing the Hai Van Pass by train</h2>
<p>The 100-kilometer railway stretch between Da Nang and Hue is widely considered one of the most scenic rail journeys in Asia. Unlike the highway tunnel that bypasses the mountains, the train tracks hug vertical cliff faces high above the South China Sea:</p>
<ul>
<li><strong>Travel Time:</strong> 2.5 to 3.5 hours on trains SE2, SE4, SE6, or daily tourist trains (such as the "Connecting Central Heritage" train HD1/HD2/HD3/HD4).</li>
<li><strong>Which Side to Sit:</strong>
  <ul>
    <li><strong>Heading North (Da Nang &rarr; Hue):</strong> Book seats on the <strong>RIGHT side</strong> of the train for uninterrupted ocean vistas.</li>
    <li><strong>Heading South (Hue &rarr; Da Nang):</strong> Book seats on the <strong>LEFT side</strong> of the train.</li>
  </ul>
</li>
<li><strong>Highlights:</strong> Lang Co Bay turquoise lagoons, isolated jungle beaches accessible only by track maintenance crews, and dramatic ocean spray crashing against rocky headlands directly below your window.</li>
</ul>

<h2 class="wp-block-heading">How to book official train tickets</h2>
<p>Avoid predatory ticket resellers by following this verified booking procedure:</p>
<ol>
<li><strong>Official Portal (<a href="https://dsvn.vn" target="_blank" rel="noopener noreferrer">dsvn.vn</a>):</strong> The official state portal displays real-time seat availability and exact government fares. However, the payment gateway frequently rejects non-Vietnamese credit cards.</li>
<li><strong>Verified Online Travel Agents (<a href="https://www.baolau.com" target="_blank" rel="noopener noreferrer">Baolau</a> or <a href="https://12go.asia" target="_blank" rel="noopener noreferrer">12Go</a>):</strong> The most reliable alternative for international travelers. They charge a minor convenience fee ($1.50&ndash;$2.50 USD), accept international Visa/Mastercard/Amex seamlessly, allow seat selection, and issue official DSVN QR code e-tickets directly via email.</li>
<li><strong>At the Railway Station Counter:</strong> You can purchase tickets in person at Ga Ha Noi, Ga Da Nang, or Ga Saigon. Bring cash in VND and your original passport. During peak holiday periods (Tet Lunar New Year in Jan/Feb), tickets sell out weeks in advance; book at least 30 days ahead.</li>
</ol>

<h2 class="wp-block-heading">Top Vietnam train routes compared</h2>
<table class="vg-decision-table vg-train-routes">
<thead>
<tr>
<th>Route</th>
<th>Distance / Duration</th>
<th>Top Train Numbers</th>
<th>Why Ride This Route</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Hanoi &harr; Ninh Binh</strong></td>
<td>115 km / 2 hours 15 mins</td>
<td>SE5, SE7, SE19</td>
<td>Fast, stress-free morning exit from Hanoi without highway traffic jams.</td>
</tr>
<tr>
<td><strong>Hanoi &harr; Hue</strong></td>
<td>688 km / 13 hours</td>
<td>SE1, SE3, SE19</td>
<td>Board at 7:30 PM in Hanoi; wake up at dawn rolling through central countryside.</td>
</tr>
<tr>
<td><strong>Da Nang &harr; Hue</strong></td>
<td>103 km / 3 hours</td>
<td>SE2, SE4, HD2</td>
<td>The iconic Hai Van Pass coastal cliff route. Spectacular daylight scenery.</td>
</tr>
<tr>
<td><strong>Da Nang &harr; Quy Nhon (Dieu Tri)</strong></td>
<td>300 km / 5.5 hours</td>
<td>SE1, SE3, The Vietage</td>
<td>Gentle coastal progression connecting central heritage with pristine beaches.</td>
</tr>
<tr>
<td><strong>Hanoi &harr; Lao Cai (Sapa)</strong></td>
<td>296 km / 8 hours</td>
<td>SP1, SP3</td>
<td>Classic overnight mountain sleeper train with private tourist carriage options.</td>
</tr>
</tbody>
</table>

<h2 class="wp-block-heading">Life onboard: survival tips for sleeper trains</h2>
<p>Ensure a comfortable journey with these field-tested precautions:</p>
<ul>
<li><strong>Luggage Storage:</strong> Suitcases and large backpacks slide beneath the bottom bunks or onto high overhead racks at the cabin entrance. Keep valuables, passport, and chargers in a small daypack by your pillow.</li>
<li><strong>Electrical Sockets:</strong> Modern SE carriages provide 220V power outlets and USB ports near the small cabin table. Bring a standard 2-pin universal adapter or power bank.</li>
<li><strong>Food &amp; Refreshments:</strong> Train attendants wheel hot food carts serving simple rice meals (<em>c&#417;m g&agrave;</em> or braised pork) for 40,000 to 50,000 VND ($1.60&ndash;$2 USD). However, travelers prefer packing fresh baguettes (<em>b&aacute;nh m&igrave;</em>), fruit, and bottled water from station convenience stores. Free hot water dispensers are located at the end of each carriage for instant noodles and tea.</li>
<li><strong>Restrooms &amp; Cleanliness:</strong> Carriages feature both western-style sit-down toilets and squat toilets at the car junctions. Restrooms are cleanest at departure and can become messy on long overnight hauls. Always carry personal pocket tissue packs and alcohol hand sanitizer.</li>
</ul>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<div class="vg-faq-list vg-train-faq">
<details>
<summary>Do I need to print my train ticket?</summary>
<p>No. You can present the electronic PDF ticket with readable QR code on your smartphone screen at the station gate and to conductors onboard.</p>
</details>
<details>
<summary>Which bunk is better: top or bottom?</summary>
<p>In a 4-berth cabin, the <strong>lower bunk (berth 1 or 2)</strong> is preferred because you do not have to climb a narrow ladder and can access luggage easily. The <strong>upper bunk (berth 3 or 4)</strong> offers slightly more privacy and is cheaper, but has less headroom.</p>
</details>
<details>
<summary>Can I transport a motorbike or bicycle on the train?</summary>
<p>Yes. Vietnam Railways accepts motorbikes and bicycles as registered freight baggage on designated trains. You must check the bike in at the station cargo counter at least 2 hours before departure and empty all gasoline from the fuel tank.</p>
</details>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<div class="vg-related-routes vg-train-related-manual">
<ul>
<li><a href="/plan/transport-within-vietnam/">Transport Within Vietnam: Trains vs Flights vs VIP Sleeper Buses</a></li>
<li><a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide: Coastal Hub &amp; Hai Van Pass Staging</a></li>
<li><a href="/destinations/hue-imperial-city-guide/">Hue Imperial City Guide: Historic Capital Rail Arrivals</a></li>
<li><a href="/plan/vietnam-travel-cost/">Vietnam Travel Cost: Rail Fares &amp; Regional Travel Budgets</a></li>
</ul>
</div>
"""

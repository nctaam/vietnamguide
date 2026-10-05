# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 62: Da Nang Airport to Hoi An Content Definition
Parent: plan (ID 6)
Slug: da-nang-airport-to-hoi-an
"""

TITLE = "Da Nang Airport to Hoi An: Private Car, Taxi & Bus (2026)"
SLUG = "da-nang-airport-to-hoi-an"
PARENT_ID = 6

FOCUS_KEYWORD = "Da Nang airport to Hoi An"
META_DESC = "Complete Da Nang airport to Hoi An transfer guide: compare pre-booked private cars, Grab fares, shuttle buses, taxi costs, and coastal route options."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-danang-airport-hero:v1 -->
<section class="vg-guide-hero vg-danang-airport-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Central Vietnam transit - Updated September 21, 2026</p>
<h1>Da Nang Airport to Hoi An: Private Car, Taxi &amp; Bus</h1>
<p class="vg-guide-lede">Da Nang International Airport (DAD) is the primary aviation gateway for visitors traveling to the UNESCO World Heritage ancient town of Hoi An. Located approximately 30 kilometers south of the airport, Hoi An has no commercial airport or train station of its own. Traversing this 45-minute journey requires choosing between pre-booked private transfers, app-based ride hailing, shared airport shuttles, and metered taxis. Here is your definitive guide from Da Nang airport to Hoi An.</p>
</div>
</section>

<div class="vg-guide-meta">
<span><strong>Distance:</strong> 30 km (18.6 miles) south along coastal or inland corridors</span>
<span><strong>Travel Time:</strong> 40–55 minutes depending on route and traffic</span>
<span><strong>Best Value:</strong> Pre-booked private car (250,000–320,000 VND flat)</span>
<span><strong>Cheapest Option:</strong> Shared airport shuttle van (120,000–150,000 VND/seat)</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>A pre-booked private car arranged through your Hoi An boutique hotel or a reputable local transport service is overwhelmingly the best choice.</strong> At 250,000 to 320,000 VND ($10–$13 USD) flat rate for the entire vehicle, it is cheaper than on-demand GrabCar, includes airport toll fees, and features your driver waiting at arrivals holding a name sign. <strong>Only take on-demand GrabCar (350,000–450,000 VND) if your flight schedule changed last minute.</strong> Never accept unmetered flat-rate quotes from curbside taxi touts at the airport exit.</p>
</div>

<h2 class="wp-block-heading">Da Nang airport to Hoi An transport comparison</h2>
<div class="wp-block-table">
<table>
<thead>
<tr>
<th>Transport Method</th>
<th>Typical Cost (VND)</th>
<th>Travel Time</th>
<th>Booking Method</th>
<th>Pros &amp; Cons</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Pre-Booked Private Car</strong></td>
<td>250,000–320,000 VND (4-seat sedan)<br>350,000–420,000 VND (7-seat SUV)</td>
<td>40–45 mins</td>
<td>Online or via Hoi An hotel (24h ahead)</td>
<td>Cheapest private option; driver waits with sign; door-to-door direct service</td>
</tr>
<tr>
<td><strong>GrabCar (Ride-Hailing)</strong></td>
<td>350,000–450,000 VND (+15k airport toll)</td>
<td>45–50 mins</td>
<td>Grab mobile app on arrival</td>
<td>Instant booking; credit card payment; subject to peak surge pricing</td>
</tr>
<tr>
<td><strong>Metered Taxi (Mai Linh / Vinasun)</strong></td>
<td>380,000–480,000 VND (+15k airport toll)</td>
<td>45–55 mins</td>
<td>Official taxi queue outside terminal</td>
<td>No app needed; slightly more expensive than pre-booking</td>
</tr>
<tr>
<td><strong>Shared Airport Shuttle Bus</strong></td>
<td>120,000–150,000 VND per passenger</td>
<td>60–80 mins</td>
<td>Hoi An Express counters or online</td>
<td>Economical for solo backpackers; drops off at multiple hotels</td>
</tr>
<tr>
<td><strong>Public City Bus (Route 01)</strong></td>
<td><em>Currently Suspended / Unreliable</em></td>
<td>90+ mins</td>
<td>Requires transfer to city bus station</td>
<td>Not recommended for airport arrivals with luggage</td>
</tr>
</tbody>
</table>
</div>

<h2 class="wp-block-heading" id="jump-private-car">1. Pre-booked private car: The gold standard</h2>
<p>Pre-arranging a private vehicle before boarding your flight to Da Nang is the smoothest arrival experience in Vietnam:</p>
<ul>
<li><strong>How It Works:</strong> Provide your flight number (e.g., VN128 or VJ512) and landing time to your Hoi An hotel or a local travel agency. Local drivers track flight arrival boards for early landings or flight delays at no extra fee.</li>
<li><strong>Arrival Pickup:</strong> Exit the Da Nang arrivals hall and look for your driver standing just beyond the sliding glass doors holding a printed sign with your full name. They will assist with heavy luggage and lead you to the nearby parking bay.</li>
<li><strong>Fixed Price Transparency:</strong> Standard tariffs range from 250,000 to 300,000 VND for a 4-seater sedan (Toyota Vios or Hyundai Accent) and 350,000 to 400,000 VND for a 7-seater SUV/MPV (Mitsubishi Xpander or Toyota Innova). The price is all-inclusive of airport parking tolls and fuel. You pay the driver directly in cash or add the fee to your hotel room folio upon check-in.</li>
</ul>

<h2 class="wp-block-heading" id="jump-grab">2. GrabCar and app-based ride hailing</h2>
<p>Booking Grab at Da Nang Airport is straightforward compared to larger hubs like Saigon:</p>
<ul>
<li><strong>Curbside Pickup Allowed:</strong> Unlike Tan Son Nhat where passengers must walk to an off-site garage, Da Nang International Airport allows Grab cars to pull directly up to designated outer curbside lanes immediately outside Domestic Arrivals (Column 1–4) and International Arrivals.</li>
<li><strong>Price Fluctuation:</strong> Off-peak fares start around 340,000 VND, but sudden afternoon tropical showers or heavy arrival clusters can push fares to 480,000 VND through dynamic surge pricing.</li>
<li><strong>Airport Surcharge:</strong> The Da Nang airport entry gate fee (10,000 to 15,000 VND) will be added to your final Grab receipt.</li>
</ul>

<h2 class="wp-block-heading" id="jump-routes">3. Coastal road vs. inland highway</h2>
<p>There are two primary vehicular routes connecting Da Nang Airport to Hoi An:</p>
<ol>
<li><strong>Coastal Route via Vo Nguyen Giap &amp; Lac Long Quan (Recommended):</strong> Heading east across the Dragon Bridge or Tien Son Bridge, this 32-kilometer route hugs Da Nang's My Khe and Non Nuoc beaches, passing luxury oceanfront resorts and the iconic limestone peaks of the Marble Mountains (Ngu Hanh Son). It offers expansive South China Sea views and gentle breezes.</li>
<li><strong>Inland Route via Le Van Hien &amp; Provincial Road DT607:</strong> A slightly shorter 28-kilometer route cutting through suburban Ngu Hanh Son and Dien Ban districts. While less scenic, it is slightly faster during beachfront festival traffic or weekend night congestion near the Dragon Bridge.</li>
</ol>

<h2 class="wp-block-heading" id="jump-scams">4. Scams and common pitfalls to avoid</h2>
<p>While Da Nang is celebrated as one of Vietnam's safest and most orderly municipal cities, several common airport transit traps still occur:</p>
<ul>
<li><strong>The Unofficial "Fixed Price" Tout:</strong> Men standing right at the arrival doors will wave laminated cards offering rides to Hoi An for 500,000 to 600,000 VND. Walk past them without engaging.</li>
<li><strong>The Marble Mountains Forced Detour:</strong> Unscrupulous taxi drivers may insist on stopping at a commercial stone-carving workshop near the Marble Mountains, claiming it is "free sightseeing" while earning hefty commissions from retail shops. If you wish to travel straight to your hotel, state clearly: <em>"Đi thẳng Hội An, không dừng lại"</em> (Go straight to Hoi An, do not stop).</li>
<li><strong>Luggage Capacity Reality:</strong> Standard 4-seater sedans in Vietnam have modest trunk space. If your traveling group consists of 3 or 4 adults carrying two large 28-inch suitcases plus carry-ons, book a 7-seater SUV to prevent cramped luggage on passenger laps.</li>
</ul>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<div class="wp-block-details">
<summary><strong>Can I take a public bus from Da Nang airport to Hoi An?</strong></summary>
<p>Public Bus Route 01 previously ran from central Da Nang to Hoi An bus station for 20,000 VND, but the route was suspended and reorganized. Taking public buses requires hauling bags 3 km into the city center. For arrivals with luggage, a shared shuttle (130,000 VND) or pre-booked car (280,000 VND) is vastly more practical.</p>
</div>
<div class="wp-block-details">
<summary><strong>How late do airport transfers operate to Hoi An?</strong></summary>
<p>Pre-booked private transfers operate 24 hours a day as long as you provide your flight number in advance. Grab and metered taxis are readily available outside the terminal for late-night flights landing past midnight.</p>
</div>
<div class="wp-block-details">
<summary><strong>Can I pay the driver in US Dollars?</strong></summary>
<p>Most local drivers prefer payment in Vietnamese Dong (VND). While some drivers accept crisp USD banknotes, they will calculate unfavorable exchange rates. Withdraw local currency from airport ATMs before leaving the terminal.</p>
</div>

<p>For more Central Vietnam insights, explore our comprehensive <a href="/destinations/hoi-an-ancient-town-guide/">Hoi An Ancient Town Guide</a>, compare urban beach life with our <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a> breakdown, plan coastal outings with the <a href="/destinations/da-nang-beaches-guide/">Da Nang Beaches Guide</a>, and organize your trip with our <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a> itinerary.</p>
"""

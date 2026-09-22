# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 65: Da Lat to Nha Trang Transport Guide Content Definition
Parent: plan (ID 6)
Slug: da-lat-to-nha-trang-transport
"""

TITLE = "Da Lat to Nha Trang Transport: Bus, Limousine & Car (2026)"
SLUG = "da-lat-to-nha-trang-transport"
PARENT_ID = 6

FOCUS_KEYWORD = "Da Lat to Nha Trang transport"
META_DESC = "Complete Da Lat to Nha Trang transport guide: compare 9-seat VIP limousines, local buses over scenic Khanh Le Pass, private cars, and travel times."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-dalat-to-nhatrang-hero:v1 -->
<section class="vg-guide-hero vg-dalat-to-nhatrang-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Transport guide - Updated September 22, 2026</p>
<h1>Da Lat to Nha Trang Transport: Mountain Pass Routes, Limousines &amp; Times</h1>
<p class="vg-lede">Descending from the cool temperate pine plateau of Da Lat down to the tropical beaches of Nha Trang covers roughly 135 kilometers along National Highway 27C. The route navigates the dramatic 33-kilometer Khanh Le mountain pass, dropping 1,500 vertical meters through rainforest gorges and misty hairpins. Understanding vehicle options, road conditions, and departure timing ensures a comfortable, nausea-free transition between the Central Highlands and the coast.</p>
</div>
</section>

<div class="vg-guide-meta">
<span><strong>Total Route Distance:</strong> 135 km via QL27C</span>
<span><strong>Average Travel Time:</strong> 3 to 3.5 Hours</span>
<span><strong>Recommended Transit:</strong> 9-Seat DCar VIP Limousine</span>
<span><strong>Typical Ticket Fare:</strong> 180,000 to 250,000 VND</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>Book a seat in a 9-passenger VIP DCar Limousine (190,000 to 240,000 VND) departing between 08:00 and 10:00.</strong> Limousine operators like Cat Thien Hai and Lac Hong provide hotel door-to-door pickup in Da Lat and drop-off in central Nha Trang, cutting terminal taxi hassle while offering wide reclining leather armchairs with individual suspension. <strong>If you are prone to motion sickness, take medication 30 minutes before leaving:</strong> the 33-kilometer descent down Khanh Le Pass features hundreds of continuous switchbacks that challenge even hardy stomachs.</p>
</div>

<h2 class="wp-block-heading">Da Lat to Nha Trang transport options compared</h2>
<div class="wp-block-table">
<table>
<thead>
<tr>
<th>Transport Option</th>
<th>Transit Time</th>
<th>Price (VND)</th>
<th>Comfort &amp; Vehicle Specs</th>
<th>Pickup / Drop-off</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>9-Seat VIP Limousine</strong><br>(Cat Thien Hai, Lac Hong)</td>
<td>3 to 3.5 hours</td>
<td>190,000–240,000 VND</td>
<td>Wide leather captain chairs, USB charging, smooth ride</td>
<td>Hotel door to hotel door</td>
</tr>
<tr>
<td><strong>Standard Coach Bus</strong><br>(Futa Bus / Phuong Trang)</td>
<td>3.5 to 4 hours</td>
<td>140,000–180,000 VND</td>
<td>Large 29–34 seat coach, fixed timetable</td>
<td>Da Lat Interprovincial Bus Station to Nha Trang Southern Station</td>
</tr>
<tr>
<td><strong>Private Car / SUV</strong><br>(Sedan or Fortuner)</td>
<td>3 to 3.5 hours</td>
<td>1,200,000–1,600,000 VND (whole car)</td>
<td>Complete schedule control, photo stops at waterfalls</td>
<td>Direct hotel-to-hotel private transfer</td>
</tr>
<tr>
<td><strong>Motorbike / Easy Rider</strong></td>
<td>4 to 5 hours</td>
<td>150,000 VND/day (rental) or 900,000 VND (guided)</td>
<td>Exhilarating open-air descent, weather exposed</td>
<td>Flexible scenic route</td>
</tr>
</tbody>
</table>
</div>

<h2 class="wp-block-heading">1. 9-Seat VIP DCar Limousines: the balanced choice</h2>
<p>For most independent travelers, the 9-seat DCar Limousine represents the optimal balance of price, convenience, and comfort on the Da Lat to Nha Trang corridor. Modified from Ford Transit or Hyundai Solati vans, these vehicles replace crammed bench rows with spacious, reclining leather captain armchairs equipped with independent armrests, cup holders, and USB charging ports.</p>
<p>Crucially, limousine vans offer complimentary pickup at your hotel or guesthouse anywhere within Da Lat city center and drop you off directly at your hotel along Nha Trang's Tran Phu beach boulevard. Prominent reliable operators running hourly morning and afternoon departures include:</p>
<ul>
<li><strong>Cat Thien Hai Limousine:</strong> Clean vehicles, professional drivers, online seat selection via Vexere (approx. 200,000 VND).</li>
<li><strong>Lac Hong Limousine:</strong> Premium leather interiors, punctuality on morning departures (approx. 220,000 VND).</li>
<li><strong>Minh Tri Limousine:</strong> Frequent daily runs between central Da Lat and Nha Trang (approx. 190,000 VND).</li>
</ul>

<h2 class="wp-block-heading">2. Scheduled coach buses (Futa / Phuong Trang)</h2>
<p>If you are traveling on a strict budget, Futa Bus Lines operates scheduled 29-seat and 34-seat interprovincial coaches connecting Da Lat with Nha Trang several times daily. Tickets cost 140,000 to 180,000 VND.</p>
<p>However, keep two logistical factors in mind:</p>
<ol>
<li><strong>Terminal locations:</strong> In Da Lat, coaches depart from the main Interprovincial Bus Station on Robin Hill (*Bến xe Liên Tỉnh Đà Lạt*), requiring a 50,000 VND taxi from the city center. In Nha Trang, coaches arrive at the Southern Bus Station (*Bến xe Phía Nam*), roughly 6 kilometers west of Tran Phu beach, requiring a 90,000 to 120,000 VND Grab taxi to reach the seaside hotel strip.</li>
<li><strong>Pass handling:</strong> Large buses move slowly on steep uphill climbs and take wider lines around sharp hairpin turns on Khanh Le Pass, resulting in more lateral body roll.</li>
</ol>

<h2 class="wp-block-heading">3. Private transfer car: family comfort and photo stops</h2>
<p>Booking a private transfer car or 7-seat SUV (Toyota Fortuner, Innova, or Mitsubishi Xpander) costs between 1,200,000 and 1,600,000 VND total. For a traveling couple or family of three to four, this matches the per-person cost of a shared limousine while delivering total control over pacing.</p>
<p>A private driver can pull over safely at designated paved lookout bays along Khanh Le Pass, where roadside springs tumble down sheer granite cliffs and mountain lookouts reveal panoramic vistas over the coastal plains of Khanh Hoa province. Drivers can also pause at clean coffee stops in Khanh Vinh valley for a stretch and cold coconut water.</p>

<h2 class="wp-block-heading">4. Khanh Le mountain pass: road conditions &amp; motion safety</h2>
<p>National Highway QL27C is an engineering triumph, but Khanh Le Pass (*Đèo Khánh Lê*, also known locally as Omega Pass or Bidoup Pass) demands driver respect. Stretching over 33 kilometers, it is one of the longest mountain passes in Vietnam, plunging from 1,500 meters altitude near Lac Duong down to barely 100 meters above sea level in Khanh Vinh.</p>
<div class="vg-card" style="background:#fff8e6; border-left:4px solid #d97706; padding:16px 20px; margin:24px 0; border-radius:4px;">
<h3 style="margin-top:0; color:#b45309;">Pass navigation &amp; weather advisory</h3>
<p style="margin-bottom:0;"><strong>Dense mountain fog (*sương mù*):</strong> Cloud banks frequently settle over the upper pass between 1,100m and 1,500m elevation after 14:00, reducing visibility to under 15 meters. Book morning departures (08:00 to 10:00) to cross the pass in bright daylight.<br><strong>Rainy season landslide risks:</strong> During the central coast monsoon from October to mid-December, heavy downpours can trigger localized rockfalls. Check road status with your hotel before departing during stormy weather.</p>
</div>

<h2 class="wp-block-heading">Motion sickness prevention tips</h2>
<p>Because Khanh Le Pass curves continuously for nearly an hour, travelers susceptible to car sickness should take proactive measures:</p>
<ul>
<li><strong>Medication:</strong> Buy *thuốc chống say xe* (such as Dimenhydrinate or Cinnarizine) at any Da Lat pharmacy for 10,000 to 20,000 VND; ingest it with water 30 minutes before boarding.</li>
<li><strong>Seat choice:</strong> In a 9-seat limousine, book seats 3, 4, 5, or 6 (the middle row captain chairs). Avoid the 3-seat rear bench, which amplifies road sway over the rear axle.</li>
<li><strong>Visual horizon:</strong> Keep your eyes focused on the distant road or mountain ridge ahead; avoid reading books or staring down at smartphone screens while descending the switchbacks.</li>
<li><strong>Hydration and food:</strong> Eat a light, non-greasy meal (such as plain bread or a light noodle soup) before departure; avoid traveling on an entirely empty or overly full stomach.</li>
</ul>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<h3 class="wp-block-heading">Is there a train between Da Lat and Nha Trang?</h3>
<p>No. Da Lat's historic railway station only operates a short 7-kilometer heritage tourist excursion to Trai Mat village. The colonial cog-railway connecting Da Lat to Thap Cham was dismantled decades ago, meaning highway road transit via QL27C is the only land route.</p>

<h3 class="wp-block-heading">Can I ride a scooter from Da Lat to Nha Trang?</h3>
<p>Yes, experienced motorcyclists frequently ride the route. However, descending Khanh Le Pass requires engine braking skills (using low gears on semi-automatics or manual bikes) to prevent disc brake overheating and failure. Scooter riders should avoid dragging brakes continuously down the 33-kilometer grade.</p>

<h2 class="wp-block-heading">Related travel guides</h2>
<ul>
<li><a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide: Highlands, Cafes &amp; Countryside</a></li>
<li><a href="/destinations/da-lat-waterfalls-guide/">Da Lat Waterfalls Guide: Top 5 Falls, Routes &amp; Map</a></li>
<li><a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide: Beaches, Islands &amp; Diving</a></li>
<li><a href="/plan/transport-within-vietnam/">Transport Within Vietnam: Trains, Buses, Flights &amp; Private Cars</a></li>
</ul>
"""

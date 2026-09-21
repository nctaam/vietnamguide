# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 62: Grab in Vietnam Guide Content Definition
Parent: plan (ID 6)
Slug: grab-in-vietnam-guide
"""

TITLE = "Grab in Vietnam: App Setup, Payments, Airports & Tips (2026)"
SLUG = "grab-in-vietnam-guide"
PARENT_ID = 6

FOCUS_KEYWORD = "Grab in Vietnam"
META_DESC = "Complete guide to Grab in Vietnam: foreign card setup, cash payments, airport pickup zones in Hanoi and Saigon, GrabBike safety, and app alternatives."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-grab-hero:v1 -->
<section class="vg-guide-hero vg-grab-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Practical travel logistics - Updated September 21, 2026</p>
<h1>Grab in Vietnam: App Setup, Payments, Airports &amp; Tips</h1>
<p class="vg-guide-lede">Grab is Southeast Asia's ubiquitous super-app and the single most valuable smartphone tool for international travelers in Vietnam. Operating across major metropolises like Hanoi, Ho Chi Minh City, and Da Nang, as well as tourist centers like Hoi An, Nha Trang, and Phu Quoc, it eliminates language barriers, displays upfront fixed pricing, and provides GPS-tracked rides. Here is everything you need to know about setting up and using Grab in Vietnam safely.</p>
</div>
</section>

<div class="vg-guide-meta">
<span><strong>Core Service:</strong> Ride-Hailing (GrabCar, GrabBike) &amp; Food Delivery</span>
<span><strong>Coverage:</strong> Nationwide in all major cities and tourist hubs</span>
<span><strong>Payment:</strong> Credit / Debit Card (Cashless) or Vietnamese Dong (Cash)</span>
<span><strong>Alternatives:</strong> Xanh SM (VinFast Electric), Be, traditional metered taxis</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>Download and configure the Grab app before leaving your home country so your home bank's SMS verification OTP works seamlessly.</strong> Link an international Visa or Mastercard with zero foreign transaction fees to enjoy frictionless cashless travel; you simply step out of the car when your trip ends. <strong>Always double-check the license plate on the car against your app before opening the door,</strong> and never get into an unmarked vehicle whose driver waves their phone claiming to be your ride.</p>
</div>

<h2 class="wp-block-heading">Grab service tiers and vehicle options</h2>
<div class="wp-block-table">
<table>
<thead>
<tr>
<th>Service Tier</th>
<th>Vehicle Type</th>
<th>Typical Base Fare</th>
<th>Best Use Case</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>GrabBike</strong></td>
<td>Scooter / Motorcycle (1 passenger)</td>
<td>12,500–15,000 VND first 2 km</td>
<td>Solo travelers, beating dense rush-hour city traffic, short hops</td>
</tr>
<tr>
<td><strong>GrabCar 4-Seater</strong></td>
<td>Compact sedan (Vios, Accent, Morning)</td>
<td>25,000–30,000 VND first 2 km</td>
<td>1–3 passengers with light luggage, rainy days, air-conditioned comfort</td>
</tr>
<tr>
<td><strong>GrabCar 7-Seater</strong></td>
<td>SUV / MPV (Innova, Xpander, Fortuner)</td>
<td>32,000–38,000 VND first 2 km</td>
<td>Families, groups of 4+, airport transfers with 2+ large rolling suitcases</td>
</tr>
<tr>
<td><strong>GrabCar Plus</strong></td>
<td>Higher-tier sedan with top-rated drivers</td>
<td>35,000–45,000 VND first 2 km</td>
<td>Business meetings, premium air-conditioned comfort, newer vehicles</td>
</tr>
<tr>
<td><strong>GrabFood</strong></td>
<td>Motorcycle courier food delivery</td>
<td>15,000–25,000 VND delivery fee</td>
<td>Hotel room delivery for local street food, boba tea, late-night snacks</td>
</tr>
</tbody>
</table>
</div>

<h2 class="wp-block-heading" id="jump-setup">1. App installation and payment setup</h2>
<p>Setting up your account properly ensures hassle-free operation across Vietnam:</p>
<ul>
<li><strong>Install Before Departure:</strong> Download Grab from the iOS App Store or Google Play Store while still at home. Register using your primary domestic phone number. Many international banks require two-factor SMS authentication (OTP) when adding a payment card; doing this while connected to your home network prevents international roaming SMS delivery failures.</li>
<li><strong>Linking Foreign Credit Cards:</strong> Go to <em>Account &gt; Payment Methods &gt; Add Card</em>. Grab supports international Visa, Mastercard, and American Express. Once linked, ride fares are automatically deducted upon trip completion—no handling dirty cash, fumbling with large notes, or waiting for drivers to find small change.</li>
<li><strong>Cash Payment Fallback:</strong> If your bank blocks international app transactions, you can switch payment to "Cash" (Tiền mặt) before confirming a ride. Keep small denominations (10,000, 20,000, 50,000 VND) ready. Grab drivers frequently carry minimal change and cannot break 500,000 VND bills on a 35,000 VND fare.</li>
</ul>

<h2 class="wp-block-heading" id="jump-airports">2. Airport pickup zones: Hanoi, Saigon, Da Nang</h2>
<p>Because municipal airport authorities enforce strict anti-congestion traffic rules, Grab pickup spots differ by airport:</p>
<ol>
<li><strong>Hanoi Noi Bai (HAN):</strong> Grab cars are permitted outside the Domestic (T1) and International (T2) arrivals halls. Cross the first roadway to the second outer traffic island or designated parking bays (Columns 10–14 at T2). Message your driver your exact column number using the in-app photo sharing feature.</li>
<li><strong>Ho Chi Minh City Tan Son Nhat (SGN):</strong> Grab cars <em>cannot</em> pick up passengers directly at curbside arrival doors. You must walk 150 meters across to the multi-story TCP Parking Garage (Nhà xe TCP). Most pickups happen at Lane D1 or Lane D2 on the ground level, or upper floors (Floors 3, 4, or 5) as indicated in the app during peak congestion.</li>
<li><strong>Da Nang Airport (DAD):</strong> Very convenient. Grab cars pull right up to designated curbside pick-up columns immediately outside Domestic and International arrivals.</li>
</ol>

<h2 class="wp-block-heading" id="jump-grabbike">3. GrabBike: Safety, helmets, and etiquette</h2>
<p>Riding pillion on a scooter is the most exhilarating and efficient way to zip through Vietnam's buzzing traffic:</p>
<ul>
<li><strong>Helmet Law is Strictly Enforced:</strong> Vietnamese law requires all motorcycle riders to wear a protective helmet. Your GrabBike driver will provide a standard half-shell helmet. Always snap the chin strap securely before climbing aboard.</li>
<li><strong>Luggage Limitations:</strong> GrabBike is strictly for one passenger carrying a daypack or small backpack. You cannot ride a GrabBike with a 28-inch rolling hard-shell suitcase.</li>
<li><strong>Rain Protocols:</strong> In the event of a sudden downpour, drivers will immediately pull over under an overpass or awning and hand you a plastic disposable poncho (<em>áo mưa</em>) to wear over your clothes.</li>
</ul>

<h2 class="wp-block-heading" id="jump-alternatives">4. Essential alternatives: Xanh SM and Be</h2>
<p>While Grab dominates the market, knowing the primary local competitors provides a valuable backup during heavy surge pricing:</p>
<ul>
<li><strong>Xanh SM (Green SM):</strong> Operated by Vietnamese conglomerate Vingroup, Xanh SM runs an entire fleet of pure electric VinFast vehicles (cyan-colored electric cars and electric scooters). Fares are competitive with Grab, cars are brand-new, exceptionally quiet, air-conditioned without gasoline fumes, and drivers wear sharp uniforms with professional etiquette. You can download the Xanh SM app or hail them on the street.</li>
<li><strong>Be:</strong> Vietnam's homegrown ride-hailing competitor featuring yellow driver uniforms. Particularly popular in Saigon and Hanoi, Be often offers lower rates during non-peak hours.</li>
</ul>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<div class="wp-block-details">
<summary><strong>Do Grab drivers speak English?</strong></summary>
<p>Most local drivers speak limited English, but the app eliminates the need for conversation. You type your exact pickup and drop-off location, the fare is calculated in advance, the in-app chat provides automatic real-time translation, and GPS guides the navigation.</p>
</div>
<div class="wp-block-details">
<summary><strong>Are airport toll road fees included in the Grab fare?</strong></summary>
<p>No. Municipal airport entry fees (typically 10,000 to 15,000 VND) and highway tollbooths are not included in the base fare display. The driver will manually add this toll to your digital receipt upon clearing the gate, which charges automatically to your card.</p>
</div>
<div class="wp-block-details">
<summary><strong>Can I tip my driver through the Grab app?</strong></summary>
<p>Yes. After your trip concludes, the app displays a 5-star rating screen with pre-set tip amounts (10,000, 20,000, or 50,000 VND). Tipping is entirely optional but warmly appreciated for helpful luggage handling or navigating difficult monsoon rain.</p>
</div>

<p>For more essential arrival and logistics tips, read our <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> hub, check connectivity advice in our <a href="/plan/sim-esim-vietnam/">Vietnam SIM &amp; eSIM Guide</a>, review payment habits with <a href="/plan/money-cash-cards-atms/">Money, Cash &amp; Cards in Vietnam</a>, and learn common transit safety tips in our <a href="/plan/safety-scams-vietnam/">Safety &amp; Scams in Vietnam</a> guide.</p>
"""

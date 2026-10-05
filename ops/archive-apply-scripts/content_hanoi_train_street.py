# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 66: Hanoi Train Street Guide Content Definition
Parent: destinations (ID 7)
Slug: hanoi-train-street-guide
"""

TITLE = "Hanoi Train Street Guide: Timetable, Cafes & Rules (2026)"
SLUG = "hanoi-train-street-guide"
PARENT_ID = 7

FOCUS_KEYWORD = "Hanoi train street"
META_DESC = "Complete Hanoi train street guide: compare Tran Phu and Phung Hung sections, train timetable schedules, police checkpoints, cafe access, and safety."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-hanoi-train-street-hero:v1 -->
<section class="vg-guide-hero vg-hanoi-train-street-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Destination guide - Updated September 22, 2026</p>
<h1>Hanoi Train Street: Timetables, Cafe Access &amp; Safety Checkpoints</h1>
<p class="vg-lede">Watching a multi-ton diesel locomotive rumble mere inches past residential doorways and cafe stools on Hanoi train street has become one of northern Vietnam's most recognizable spectacles. However, strict municipal safety closures, police barricades, and shifting train timetables mean visiting requires strategic planning. Understanding the physical layout, arrival timing, and local cafe escort customs ensures a secure, stress-free trackside coffee experience.</p>
</div>
</section>

<div class="vg-guide-meta">
<span><strong>Primary Section:</strong> Tran Phu Alley (Hoan Kiem / Ba Dinh border)</span>
<span><strong>Alternative Section:</strong> Phung Hung Street (Near Long Bien Station)</span>
<span><strong>Best Viewing Days:</strong> Saturday &amp; Sunday (Frequent daytime trains)</span>
<span><strong>Track Clearance:</strong> 30 to 50 cm from cafe stools</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>Plan your visit on a Saturday or Sunday afternoon (between 15:00 and 17:30) to catch back-to-back daytime trains in natural photography light.</strong> Do not attempt to walk past the official metal barricades and police guards on Tran Phu street independently, as you will be turned away. <strong>Instead, arrange trackside cafe access in advance by messaging a reputable trackside coffee shop</strong> (such as Railway Cafe or Coffee 74) via WhatsApp, or let a cafe host escort you past the security checkpoint directly to their second-floor balcony.</p>
</div>

<h2 class="wp-block-heading">Hanoi train street sections compared</h2>
<div class="wp-block-table">
<table>
<thead>
<tr>
<th>Location Section</th>
<th>Primary Access Point</th>
<th>Atmosphere &amp; Views</th>
<th>Police Checkpoint Strictness</th>
<th>Best Train Windows</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Tran Phu Section (Old Quarter)</strong></td>
<td>Intersection of Tran Phu &amp; Dien Bien Phu</td>
<td>Classic narrow residential corridor, dense cafes, 2nd-floor balconies</td>
<td>Strict: Guards turn away unescorted tourists at metal gates</td>
<td>Sat–Sun: 15:30, 16:30, 17:30<br>Daily: 19:30–21:30</td>
</tr>
<tr>
<td><strong>Phung Hung Section (North)</strong></td>
<td>Near Phung Hung mural street / Long Bien</td>
<td>Slightly wider track bed, stone arches, local workshops</td>
<td>Moderate: Periodic patrols, occasional open track walking</td>
<td>Daily morning departures toward Hai Phong &amp; Lao Cai</td>
</tr>
<tr>
<td><strong>Le Duan Section (South)</strong></td>
<td>Along Le Duan street, south of Ga Ha Noi</td>
<td>Working-class residential rail, zero tourist cafes, raw local life</td>
<td>Unregulated: Open pedestrian crossings, no commercial seating</td>
<td>Daily intercity express runs (SE trains)</td>
</tr>
</tbody>
</table>
</div>

<h2 class="wp-block-heading">1. Tran Phu section vs Phung Hung section</h2>
<p>Understanding where to go determines your entire experience:</p>
<h3 class="wp-block-heading">The Tran Phu Corridor (The Iconic Hotspot)</h3>
<p>Running between Tran Phu street and Dien Bien Phu street on the western edge of the Old Quarter, this 300-meter rail corridor represents the famous postcard scene. French colonial shophouses stand barely two meters apart across the single narrow-gauge track. Ground-floor living rooms operate as cozy cafes, serving iced coconut coffee, drip filter robusta, and local craft beers. Because clearance between passing trains and cafe stools measures under 50 centimeters, the Hanoi Department of Transport maintains metal barricades and security officers at entry alleys to prevent accidents.</p>

<h3 class="wp-block-heading">The Phung Hung Corridor (The Northern Alternative)</h3>
<p>Located roughly one kilometer north near the Long Bien railway arches and Hang Dau water tower, the Phung Hung stretch feels more residential and slightly wider. While cafes have opened here too, foot traffic is lighter. It pairs easily with walking tours of the Phung Hung mural street, though train frequency is somewhat lower than the southern junction.</p>

<h2 class="wp-block-heading">2. Train timetable schedule (2026 realistic timetable)</h2>
<p>Vietnam Railways schedules change based on cargo connections and seasonal holidays. Freight trains and passenger coaches also face periodic 15-to-30 minute delays. Use this operational timetable as your planning baseline:</p>
<div class="wp-block-table">
<table>
<thead>
<tr>
<th>Day of the Week</th>
<th>Morning Trains</th>
<th>Afternoon Trains</th>
<th>Evening Trains</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Monday to Friday</strong></td>
<td>Rare (Occasional early freight before 06:30)</td>
<td>Rare (Service transfers around 15:30)</td>
<td><strong>19:00, 19:45, 20:30, 21:00, 21:45</strong></td>
</tr>
<tr>
<td><strong>Saturday &amp; Sunday</strong></td>
<td><strong>06:00, 09:00, 09:30, 11:30</strong></td>
<td><strong>15:30, 16:30, 17:30, 18:00</strong></td>
<td><strong>19:00, 19:45, 20:30, 21:15, 22:00</strong></td>
</tr>
</tbody>
</table>
</div>
<p><strong>Pro tip:</strong> Arrive at your chosen trackside cafe at least 30 to 45 minutes prior to the scheduled pass. Cafe owners must pull in folding tables, move plastic stools behind painted safety lines, and clear track debris before the train arrives.</p>

<h2 class="wp-block-heading">3. How to pass the police checkpoints legally</h2>
<p>Since the official safety closure in autumn 2022, security personnel staff the metal barricades at the Tran Phu and Dien Bien Phu intersections. Tourists walking up independently will be gestured away. However, local residents running home businesses retain legal rights to invite guests to their premises.</p>
<ol>
<li><strong>Method 1 (Advance Messaging):</strong> Contact a cafe owner via WhatsApp or Instagram before you go (for example, Hao Hao Coffee or Railway Station Cafe). Give them your arrival time and party size. A family member will meet you at the barricade, tell the officers you are their guest, and walk you straight inside.</li>
<li><strong>Method 2 (On-Site Host Escort):</strong> When you approach the barricade, local cafe owners will quietly approach you asking if you want coffee. Nod yes, follow them past the guard booth, and take a seat at their venue.</li>
<li><strong>Etiquette &amp; Pricing:</strong> Supporting the resident family by ordering drinks is mandatory. Standard drinks cost 35,000 to 60,000 VND ($1.50 to $2.50 USD), representing fair value for hosting and trackside access.</li>
</ol>

<h2 class="wp-block-heading">4. Crucial safety protocols trackside</h2>
<div class="vg-card" style="background:#fef2f2; border-left:4px solid #ef4444; padding:16px 20px; margin:24px 0; border-radius:4px;">
<h3 style="margin-top:0; color:#b91c1c;">Trackside survival rules</h3>
<p style="margin-bottom:0;"><strong>1. Obey the warning bell:</strong> When the crossing bell sounds, immediately step behind the yellow or red painted safety line on the cafe floor.<br><strong>2. Keep backs flat against the wall:</strong> Passing trains generate significant air suction and protruding engine steps. Flatten yourself against the wall and do not lean out for selfies.<br><strong>3. Keep hands and cameras close:</strong> Never hold a phone or selfie stick out toward the passing train wagons.<br><strong>4. Guard children:</strong> Keep children seated firmly on your lap or inside the cafe doorway; never let them wander near the gravel ballast.</p>
</div>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<h3 class="wp-block-heading">Is Hanoi train street completely closed?</h3>
<p>No. While independent casual walking down the tracks is officially restricted by police barriers, all trackside cafes and residential homes operate actively. Visitors escorted by cafe hosts enter daily without issue.</p>

<h3 class="wp-block-heading">Which seat provides the best view?</h3>
<p>Second-floor open-air balconies provide superior photography angles. From above, you can capture the train snaking between rooftops while avoiding the engine wind and ground-level dust.</p>

<h2 class="wp-block-heading">Related travel guides</h2>
<ul>
<li><a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide: Old Quarter, West Lake &amp; Culture</a></li>
<li><a href="/destinations/best-things-to-do-in-hanoi/">Best Things to Do in Hanoi: Street Life &amp; Highlights</a></li>
<li><a href="/destinations/hanoi-street-food-guide/">Hanoi Street Food Guide: Egg Coffee &amp; Old Quarter Stalls</a></li>
<li><a href="/plan/vietnam-train-travel/">Vietnam Train Travel: Routes, Sleeper Berths &amp; Schedules</a></li>
</ul>
"""

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 63: Where to Stay in Hoi An Content Definition
Parent: destinations (ID 7)
Slug: where-to-stay-in-hoi-an
"""

TITLE = "Where to Stay in Hoi An: Old Town vs Beach & Fields (2026)"
SLUG = "where-to-stay-in-hoi-an"
PARENT_ID = 7

FOCUS_KEYWORD = "Where to stay in Hoi An"
META_DESC = "Complete guide on where to stay in Hoi An: compare Ancient Town walking distance, An Bang Beach resorts, and Cam Chau rice field boutique villas."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-hoian-stays-hero:v1 -->
<section class="vg-guide-hero vg-hoian-stays-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Accommodation guide - Updated September 22, 2026</p>
<h1>Where to Stay in Hoi An: Old Town vs Beach &amp; Fields</h1>
<p class="vg-guide-lede">Hoi An is one of Southeast Asia's most charming travel destinations, but deciding where to sleep requires choosing between three distinctly different environments: the lantern-lit pedestrian streets of the Ancient Town, the emerald-green rice paddies and palm waterways of Cam Chau, or the laid-back coastal rhythm of An Bang Beach. Each base serves a different trip objective. Here is our practical guide on where to stay in Hoi An.</p>
</div>
</section>

<div class="vg-guide-meta">
<span><strong>Heritage Walking Base:</strong> Ancient Town &amp; Cam Pho (Central)</span>
<span><strong>Scenic Rural Base:</strong> Cam Chau &amp; Cam Thanh (Rice Fields)</span>
<span><strong>Coastal Beach Base:</strong> An Bang Beach (4 km north of town)</span>
<span><strong>Island &amp; River Luxury:</strong> An Hoi Islet &amp; Thu Bon Riverfront</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>First-time visitors staying for 2 or 3 nights should choose a boutique hotel in Cam Pho or along the immediate edge of the Ancient Town.</strong> This gives you effortless walking access to morning markets, tailor shops, and lantern evenings without needing bicycles or taxis, while remaining just outside the pedestrian-only zone where taxis cannot drop luggage. <strong>Travelers staying 4 or more nights should stay in Cam Chau amidst the rice paddies</strong> for tranquil pool villas and scenic bicycle commutes between both town and beach.</p>
</div>

<h2 class="wp-block-heading">Hoi An area comparison</h2>
<div class="wp-block-table">
<table>
<thead>
<tr>
<th>Area / Zone</th>
<th>Vibe &amp; Scenery</th>
<th>Best For</th>
<th>Average Hotel Rate (USD)</th>
<th>Friction / Trade-off</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Ancient Town Edge (Cam Pho / Minh An)</strong></td>
<td>Historic shophouses, lantern streets, lively riverfront</td>
<td>First-timers, photographers, 1–2 night short stays</td>
<td>$40–$110 / night</td>
<td>Pedestrian zone restrictions; evening foot traffic noise</td>
</tr>
<tr>
<td><strong>Cam Chau (Rice Paddies)</strong></td>
<td>Lush rice fields, cycling lanes, quiet boutique villas</td>
<td>Couples, relaxation, longer stays, bicycle lovers</td>
<td>$35–$85 / night</td>
<td>Requires 10-minute bicycle ride or 40,000 VND taxi to town</td>
</tr>
<tr>
<td><strong>An Bang Beach</strong></td>
<td>Laid-back surf town, beachfront cafes, seafood grills</td>
<td>Beach lovers, seafood dining, summer sunseekers</td>
<td>$45–$130 / night</td>
<td>4 km from Ancient Town; winter rough seas (Nov–Jan)</td>
</tr>
<tr>
<td><strong>Cam Thanh (Coconut Groves)</strong></td>
<td>Nipa palm canals, basket boats, rural tranquility</td>
<td>Families, eco-retreats, cooking school visitors</td>
<td>$30–$75 / night</td>
<td>5 km southeast of center; requires motorized transport</td>
</tr>
</tbody>
</table>
</div>

<h2 class="wp-block-heading" id="ancient-town-edge">1. Ancient Town Edge: Heritage walkability and morning calm</h2>
<p>Hoi An's UNESCO Ancient Town is strictly pedestrianized for large portions of the day, prohibiting cars and motorbikes. The best hotels do not sit inside the noisy core, but rather along its immediate perimeter in Cam Pho ward (such as along Thoai Ngoc Hau, Dao Duy Tu, or Ba Trieu streets).</p>
<ul class="wp-block-list">
<li><strong>Why it works:</strong> You can wake up at 06:00 and walk directly into the quiet yellow-walled alleys before tourist tour buses arrive at 09:00. In the evening, after dining on Cao Lau and watching the floating paper lanterns, you can stroll back to your room in five minutes.</li>
<li><strong>The taxi constraint:</strong> Because motorized vehicles cannot enter the core walking streets, verify your hotel's exact drop-off point. Hotels on the edge have direct vehicle access, so drivers can drop your luggage right at reception.</li>
</ul>

<h2 class="wp-block-heading" id="cam-chau">2. Cam Chau: Boutique pool villas amidst the rice paddies</h2>
<p>Cam Chau occupies the scenic rural expanse between the Ancient Town and the ocean. It represents Hoi An's golden sweet spot for boutique lodging, featuring family-run eco-resorts and stylish villas set directly against green rice terraces.</p>
<ul class="wp-block-list">
<li><strong>Why it works:</strong> You enjoy expansive views of water buffalo grazing in the fields, serene swimming pools surrounded by tropical frangipani trees, and absolute nighttime quiet. Almost all boutique hotels here provide free bicycles for guests.</li>
<li><strong>The commute:</strong> Cam Chau sits conveniently in the middle: an easy 2 km (8-minute) bicycle ride west takes you into the Ancient Town, while a 2.5 km bicycle ride northeast leads directly to An Bang Beach.</li>
</ul>

<h2 class="wp-block-heading" id="an-bang-beach">3. An Bang Beach: Coastal relaxation and seafood dining</h2>
<p>Located 4 kilometers north of town, An Bang Beach is Hoi An's primary swimming and dining coastline. Unlike the concrete high-rises of Da Nang, An Bang retains a bohemian village atmosphere with thatched-roof beach bars, homestays, and boutique hotels tucked along sandy alleys.</p>
<ul class="wp-block-list">
<li><strong>Why it works:</strong> Perfect during Central Vietnam's hot dry season (March to August). You can spend mornings swimming in calm ocean waters, lunch on fresh grilled squid at beachfront restaurants like Soul Kitchen or The Deck House, and take a quick 10-minute taxi into Hoi An for dinner.</li>
<li><strong>Winter note:</strong> Between October and January, monsoon swells erode the shoreline and bring rough, choppy surf. During these months, staying inland in Cam Pho or Cam Chau is far more enjoyable.</li>
</ul>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<div class="wp-block-details">
<summary><strong>Is it better to stay in Hoi An Ancient Town or An Bang Beach?</strong></summary>
<p>Choose Ancient Town or its immediate edge if your trip focuses on cultural sightseeing, street food, evening lantern strolls, tailor fittings, and photography. Choose An Bang Beach if you visit between March and August and prioritize ocean swims, sunbathing, and relaxed beachside cocktail bars.</p>
</div>
<div class="wp-block-details">
<summary><strong>How do I get between Hoi An town and An Bang Beach?</strong></summary>
<p>The 4-kilometer route is flat and easy. Most travelers take a leisurely 15-to-20-minute bicycle ride along Hai Ba Trung street through rural countryside. Alternatively, a GrabCar or metered taxi costs just 60,000 to 80,000 VND ($2.40 to $3.20 USD) each way.</p>
</div>
<div class="wp-block-details">
<summary><strong>How many days should I stay in Hoi An?</strong></summary>
<p>Two nights is the recommended minimum to see the Ancient Town illuminated at night and take one countryside excursion. Three to four nights is ideal to add a cooking class, visit the My Son Sanctuary ruins, tailor customized clothing, and enjoy beach relaxation without rushing.</p>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Pair your accommodation choice with our curated Hoi An trip planning guides:</p>
<ul class="wp-block-list">
<li><a href="/destinations/hoi-an-ancient-town-guide/">Hoi An Ancient Town Guide</a> &mdash; full walking itinerary, ticket requirements, and essential cultural sights.</li>
<li><a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a> &mdash; top experiences from tailor shops to cooking classes and night markets.</li>
<li><a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a> &mdash; compare resort convenience against historic lantern atmosphere.</li>
<li><a href="/plan/da-nang-airport-to-hoi-an/">Da Nang Airport to Hoi An</a> &mdash; private car tariffs, shuttle services, and taxi transfer options.</li>
</ul>
"""

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 64: Hoi An Street Food Guide Content Definition
Parent: destinations (ID 7)
Slug: hoi-an-street-food-guide
"""

TITLE = "Hoi An Street Food Guide: 9 Essential Dishes & Stalls (2026)"
SLUG = "hoi-an-street-food-guide"
PARENT_ID = 7

FOCUS_KEYWORD = "Hoi An street food"
META_DESC = "Complete Hoi An street food guide: where to eat Cao Lau, Mi Quang, White Rose dumplings, Madam Khanh banh mi, night market snacks, and hygiene tips."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-hoian-street-food-hero:v1 -->
<section class="vg-guide-hero vg-hoian-street-food-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Destination guide - Updated September 22, 2026</p>
<h1>Hoi An Street Food Guide: Essential Dishes &amp; Iconic Stalls</h1>
<p class="vg-guide-lede">Hoi An's food culture reflects centuries as a bustling maritime trading port where Vietnamese, Chinese, Japanese, and French culinary traditions converged. Exploring Hoi An street food reveals unique regional specialties found nowhere else on earth, rooted in ancient well water, aromatic herbs from Tra Que village, and wood-fired ovens. Here is our logistical guide to the town's best dishes, verified stalls, and realistic prices.</p>
</div>
</section>

<div class="vg-guide-meta">
<span><strong>Iconic Regional Specialty:</strong> Cao Lau &amp; White Rose Dumplings</span>
<span><strong>Banh Mi Hotspots:</strong> Madam Khanh &amp; Banh Mi Phuong</span>
<span><strong>Evening Street Stalls:</strong> An Hoi Night Market (Nguyen Hoang)</span>
<span><strong>Average Dish Cost:</strong> 30,000 to 60,000 VND ($1.20–$2.40 USD)</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>Skip the westernized riverfront restaurants along Bach Dang and eat where local families dine across the northern perimeter of the Ancient Town.</strong> Start with a morning bowl of chewy ash-water noodles at <strong>Quan Cao Lau Thanh</strong> on Tran Cao Van (35,000 to 45,000 VND), then walk three minutes to <strong>Madam Khanh "The Banh Mi Queen"</strong> for a roast pork and pâté baguette (35,000 VND). For Hoi An's famous White Rose dumplings, head directly to the original family workshop at <strong>Bong Hong Trang</strong> (533 Hai Ba Trung), where women have hand-crimped translucent shrimp pastries for three generations.</p>
</div>

<h2 class="wp-block-heading">Hoi An street food comparison</h2>
<div class="wp-block-table">
<table>
<thead>
<tr>
<th>Specialty Dish</th>
<th>Signature Stall / Address</th>
<th>Core Ingredients</th>
<th>Average Price (VND)</th>
<th>Best Time to Eat</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Cao Lau</strong></td>
<td>Quan Cao Lau Thanh (26 Tran Cao Van)</td>
<td>Thick ash noodles, braised char siu pork, crispy croutons, Tra Que herbs</td>
<td>35,000–45,000 VND</td>
<td>Lunch (11:00–13:30)</td>
</tr>
<tr>
<td><strong>Mi Quang</strong></td>
<td>Mi Quang Ong Hai (6A Truong Minh Luong)</td>
<td>Turmeric rice noodles, shrimp, pork, roasted peanuts, sesame rice cracker</td>
<td>40,000–50,000 VND</td>
<td>Breakfast &amp; Lunch</td>
</tr>
<tr>
<td><strong>White Rose (*Bánh Bao Bánh Vạc*)</strong></td>
<td>Bong Hong Trang (533 Hai Ba Trung)</td>
<td>Translucent rice starch, spiced shrimp paste, fried shallots, chili dip</td>
<td>70,000–100,000 VND</td>
<td>Afternoon &amp; Dinner</td>
</tr>
<tr>
<td><strong>Banh Mi</strong></td>
<td>Madam Khanh (115 Tran Cao Van)</td>
<td>Crispy baguette, liver pâté, roasted pork, fried egg, pickled papaya</td>
<td>30,000–40,000 VND</td>
<td>All day (07:00–19:30)</td>
</tr>
<tr>
<td><strong>Nem Lui &amp; Banh Xeo</strong></td>
<td>Gieng Ba Le (Alley 45/51 Phan Chu Trinh)</td>
<td>Grilled pork skewers, crispy rice crepes, rice paper, rich peanut liver sauce</td>
<td>140,000–180,000 VND (set)</td>
<td>Dinner (17:30–21:00)</td>
</tr>
<tr>
<td><strong>Che Bap (Sweet Corn Soup)</strong></td>
<td>Hoi An Central Market food court</td>
<td>Cam Nam island tender sweet corn, coconut milk, crushed ice</td>
<td>15,000–25,000 VND</td>
<td>Afternoon snack</td>
</tr>
</tbody>
</table>
</div>

<h2 class="wp-block-heading" id="cao-lau">1. Cao Lau: The noodle that cannot be replicated</h2>
<p>Cao Lau is Hoi An's undisputed signature dish. Legend holds that true Cao Lau can only be cooked in Hoi An, because the noodles must be soaked in lye water made from wood ash gathered on the Cham Islands, then kneaded with water drawn exclusively from the thousand-year-old Ba Le Well.</p>
<ul class="wp-block-list">
<li><strong>Texture &amp; flavor:</strong> The resulting noodles are remarkably firm and chewy, closer in texture to Japanese udon than standard Vietnamese pho noodles. The noodles are served with a ladle of spiced pork braising broth, thin slices of char siu pork, square croutons cut from dried noodle dough and deep-fried in lard, and a handful of bitter greens and mint from nearby Tra Que vegetable village.</li>
<li><strong>Where to go:</strong> Visit <em>Quan Cao Lau Thanh</em> (26 Tran Cao Van) or <em>Cao Lau Ba Le</em> (49/3 Tran Hung Dao). Stalls operate from early morning until mid-afternoon, charging 35,000 to 45,000 VND per bowl.</li>
</ul>

<h2 class="wp-block-heading" id="white-rose">2. White Rose Dumplings: The secret family workshop</h2>
<p>Known in Vietnamese as <em>bánh bao bánh vạc</em>, French travelers dubbed these delicate steamed dumplings "White Rose" due to their resemblance to folded flower petals.</p>
<ul class="wp-block-list">
<li><strong>The family monopoly:</strong> A single local family produces virtually every White Rose dumpling served across Hoi An's restaurants and luxury hotels. At their restaurant workshop, <em>Bông Hồng Trắng</em> (533 Hai Ba Trung), diners sit at wooden tables while kitchen staff crimp dough wrappers around seasoned ground shrimp and minced pork paste in the adjoining open room.</li>
<li><strong>How to eat:</strong> A plate of dumplings arrives sprinkled with crisp golden fried shallots and accompanied by a small saucer of sweet dipping sauce brewed from shrimp broth, fish sauce, sugar, and mild chili slices. Expect to pay 70,000 to 100,000 VND for a full tasting plate.</li>
</ul>

<h2 class="wp-block-heading" id="banh-mi-battle">3. The Banh Mi Duel: Madam Khanh vs Banh Mi Phuong</h2>
<p>Hoi An baguettes differ from the airy, feather-light loaves of Saigon: they have tapered pointy ends and a denser, crackling crust engineered to hold heavy fillings without collapsing.</p>
<ul class="wp-block-list">
<li><strong>Madam Khanh "The Banh Mi Queen":</strong> Located at 115 Tran Cao Van. Founded by Nguyen Thi Loc, this shop specializes in a rich, harmonious filling of roast pork, Chinese sausage, soft pâté, fried egg, cucumber ribbons, and a secret caramel-like sauce. Service is fast and friendly; a mixed baguette (*bánh mì thập cẩm*) costs 35,000 VND.</li>
<li><strong>Banh Mi Phuong:</strong> Located at 2B Phan Chu Trinh. Catapulted to international fame by chef Anthony Bourdain, Phuong's baguettes feature an extensive combination of cold cuts, barbecue pork, liver pâté, pickled daikon, and heavy mayonnaise sauce. Lines can be long between 11:30 and 13:00.</li>
</ul>

<h2 class="wp-block-heading" id="ba-le-well">4. Ba Le Well (Gieng Ba Le): Grilled skewers and wrapping feasts</h2>
<p>Tucked down an alley off Phan Chu Trinh street (Alley 45/51), Gieng Ba Le operates without a printed menu. As soon as you sit down, the waitstaff covers your table with an abundance of grilled pork skewers (<em>nem lụi</em>), crispy savory turmeric crepes (<em>bánh xèo</em>), grilled pork steaks, fresh mint, cucumber, green mango strips, and stacks of pliable dry rice paper.</p>
<ul class="wp-block-list">
<li><strong>How it works:</strong> The staff will happily demonstrate how to dip rice paper in water, stack the grilled skewer, pull out the wooden stick, layer fresh herbs, roll tightly, and dip the parcel into their signature warm dipping sauce made of ground pork liver, crushed peanuts, and sesame seeds. The all-inclusive set costs roughly 140,000 to 180,000 VND per person.</li>
</ul>

<h2 class="wp-block-heading" id="an-hoi-night-market">5. An Hoi Night Market: Evening river snacks</h2>
<p>Across the footbridge on the An Hoi peninsula, the Nguyen Hoang night market comes alive every evening from 17:30 to 22:00.</p>
<ul class="wp-block-list">
<li><strong>Popular treats:</strong> Look for carts grilling pork skewers on bamboo sticks (15,000 VND), banana pancakes toasted over buttered griddles (<em>bánh chuối</em>, 20,000 to 30,000 VND), and small cups of warm soybean pudding topped with spicy ginger syrup (<em>tàu phớ</em>, 15,000 VND).</li>
</ul>

<h2 class="wp-block-heading">Hoi An food hygiene rules</h2>
<p>Hoi An's food safety track record is generally strong due to local tourism board licensing, but smart precautions remain essential:</p>
<ul class="wp-block-list">
<li><strong>Look for hot preparation:</strong> Choose stalls where soups are boiling continuously in metal vats and grilled meats are pulled directly from red charcoal embers.</li>
<li><strong>Herbs and wash water:</strong> If you have an exceptionally sensitive stomach during your first 48 hours in Vietnam, avoid raw fresh herb plates and stick to cooked dishes like hot Cao Lau and fresh-off-the-griddle banh mi.</li>
</ul>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<div class="wp-block-details">
<summary><strong>Is street food in Hoi An safe to eat?</strong></summary>
<p>Yes. The majority of street food vendors in Hoi An cater to high volumes of international visitors and local residents daily. High turnover ensures fresh ingredients. Eat at busy stalls with clean counters and avoid cold food sitting uncovered in the afternoon heat.</p>
</div>
<div class="wp-block-details">
<summary><strong>Why can Cao Lau noodles only be made in Hoi An?</strong></summary>
<p>Authentic Cao Lau noodles require a precise chemical reaction. The soaking water uses ash from specific trees on the Cham Islands, and the dough is mixed using mineral-rich water drawn from the ancient Ba Le Well. Noodle makers outside the region cannot recreate the exact springy texture and smoky aroma.</p>
</div>
<div class="wp-block-details">
<summary><strong>Do street food vendors in Hoi An accept credit cards?</strong></summary>
<p>No. Standard street stalls, market food courts, and alley restaurants in Hoi An operate strictly in Vietnamese Dong cash. Keep 20,000, 50,000, and 100,000 VND notes handy. Sit-down restaurants like Bong Hong Trang accept cards, but usually add a 2% to 3% merchant processing fee.</p>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Plan your culinary itinerary and central Vietnam logistics with our companion guides:</p>
<ul class="wp-block-list">
<li><a href="/destinations/hoi-an-ancient-town-guide/">Hoi An Ancient Town Guide</a> &mdash; ticket rules, heritage houses, Japanese Covered Bridge, and walking routes.</li>
<li><a href="/destinations/where-to-stay-in-hoi-an/">Where to Stay in Hoi An</a> &mdash; compare Ancient Town walking distance, An Bang beach, and rice field villas.</li>
<li><a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a> &mdash; basket boat rides, cycling Tra Que village, and night lantern boats.</li>
<li><a href="/plan/vietnam-food-safety-street-food-etiquette/">Vietnam Street Food Safety &amp; Etiquette</a> &mdash; chopstick protocols, dining etiquette, and avoiding stomach troubles.</li>
</ul>
"""

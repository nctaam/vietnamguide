# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 61: Phong Nha Cave Treks Content Definition
Parent: plan (ID 6)
Slug: phong-nha-cave-treks
"""

TITLE = "Phong Nha Cave Treks: 1 to 3-Day Expedition Guide (2026)"
SLUG = "phong-nha-cave-treks"
PARENT_ID = 6

FOCUS_KEYWORD = "Phong Nha cave treks"
META_DESC = "Top Phong Nha cave treks: compare Hang En 2-day expedition, Tu Lan river cave swimming, Paradise Cave 7km trek, operator standards, fitness, and gear."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-phong-nha-treks-hero:v1 -->
<section class="vg-guide-hero vg-phong-nha-treks-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Expedition planning - Updated September 21, 2026</p>
<h1>Phong Nha Cave Treks: 1 to 3-Day Expedition Guide</h1>
<p class="vg-guide-lede">Phong Nha-Ke Bang National Park protects the oldest karst mountains in Asia and some of the planet's most colossal underground river chambers. Beyond the illuminated wooden boardwalks of tourist show caves lies a world of multi-day jungle expeditions, subterranean river swims, and wilderness beach camping inside subterranean caverns. Here is your definitive guide to Phong Nha cave treks.</p>
</div>
</section>

<div class="vg-guide-meta">
<span><strong>Region:</strong> Central Vietnam (Quang Binh Province)</span>
<span><strong>Operating Season:</strong> Late November through August</span>
<span><strong>Trek Durations:</strong> 1-Day, 2-Day (1N), 3-Day (2N)</span>
<span><strong>Key Operators:</strong> Oxalis Adventure &amp; Jungle Boss</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>Book a 2-day or 3-day guided expedition if you want genuine wilderness adventure;</strong> swimming through underground rivers in Tu Lan or sleeping on an underground sand beach inside Hang En is unmatched anywhere in Southeast Asia. <strong>Never attempt to explore wild caves independently;</strong> complex subterranean hydrology, flash flood risks, and unmapped karst chasms make certified guides, technical safety gear, and licensed porters mandatory.</p>
</div>

<h2 class="wp-block-heading">Top Phong Nha cave treks compared</h2>
<div class="wp-block-table">
<table>
<thead>
<tr>
<th>Expedition Name</th>
<th>Duration &amp; Operator</th>
<th>Physical Rating</th>
<th>Key Experience</th>
<th>Approximate Cost (VND)</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Paradise Cave 7km Trek</strong></td>
<td>1 Day (Phong Nha Discovery)</td>
<td>Moderate (7 km underground walk)</td>
<td>Walk past the tourist boardwalk with headlamps; subterranean river boat ride</td>
<td>2,600,000 VND ($105 USD)</td>
</tr>
<tr>
<td><strong>Hang Én Expedition</strong></td>
<td>2 Days / 1 Night (Oxalis)</td>
<td>Moderate to Demanding (22 km trek)</td>
<td>Camp on an underground sandy beach inside the world's 3rd largest cave</td>
<td>7,600,000 VND ($300 USD)</td>
</tr>
<tr>
<td><strong>Tú Làn Cave System</strong></td>
<td>1 to 3 Days (Oxalis)</td>
<td>Moderate (River swims + jungle)</td>
<td>Swim through dark flooded caverns with helmet lights; lush jungle valley walks</td>
<td>1,800,000 - 8,500,000 VND</td>
</tr>
<tr>
<td><strong>Hang Tiên Discovery</strong></td>
<td>1 to 2 Days (Oxalis)</td>
<td>Demanding (Rocky boulder scrambles)</td>
<td>Giant cavern passages with terraced rimstone pools and swirling limestone ceilings</td>
<td>2,000,000 - 6,500,000 VND</td>
</tr>
<tr>
<td><strong>Kong Collapse Top Gear</strong></td>
<td>3 Days / 2 Nights (Jungle Boss)</td>
<td>Extreme (100m vertical abseil)</td>
<td>Technical vertical rappel into a deep karst sinkhole doline</td>
<td>15,000,000 VND ($600 USD)</td>
</tr>
</tbody>
</table>
</div>

<h2 class="wp-block-heading" id="jump-expeditions">1. The core expeditions: Which trek fits your style?</h2>
<p>Choosing a cave expedition depends on your physical comfort with water, dark spaces, and rugged jungle terrain:</p>
<ul>
<li><strong>Hang Én (The Ultimate 2-Day Classic):</strong> Hang En is the world's third-largest cave and serves as the gateway feeder cave to Son Doong. Day 1 involves a 10-kilometer jungle hike through Doong minority village, crossing dozens of knee-deep rivers before entering a massive cavern portal. Tents are pitched on an interior sandbar beside a turquoise subterranean lake where swifts nest hundreds of meters overhead. Perfect for adventurous beginners with good walking stamina.</li>
<li><strong>Tú Làn Caves (The Water Lover's Trek):</strong> Located in Tan Hoa valley, 70 kilometers northwest of Phong Nha town. Tu Lan is defined by river cave swimming. Wearing life jackets, helmet lamps, and boots, you swim through cavern tunnels between sunlit tropical jungle valleys. Exceptional during hot spring and summer months (April to August).</li>
<li><strong>Paradise Cave 7km (The Non-Camping Deep Day Hike):</strong> While regular tourists turn around after the first 1,000 meters of wooden boardwalk, this specialized tour outfits you with headlamps and caving helmets to hike 6 kilometers deeper into dark cavern chambers, culminating in a subterranean boat paddle across underground rapids.</li>
</ul>

<h2 class="wp-block-heading" id="jump-operators">2. Licensed operators and safety standards</h2>
<p>Unlike standard sightseeing where you can hire freelance guides, genuine wild cave expeditions in Phong Nha are strictly regulated by the national park management board to protect delicate geological formations and ensure international safety compliance:</p>
<ul>
<li><strong>Oxalis Adventure:</strong> The premier expedition operator in Vietnam, holding exclusive concession rights for Son Doong, Hang En, Tu Lan, and Hang Tien. Guided by British Cave Research Association (BCRA) standards, with 1:1 or 1:2 guide/porter-to-client ratios, satellite communication, and strict leave-no-trace compost toilet protocols.</li>
<li><strong>Jungle Boss:</strong> A licensed alternative specializing in high-adrenaline technical treks including the 100-meter vertical descent into Kong Collapse sinkhole and expeditions into Tiger Cave and Pygmy Cave.</li>
</ul>

<h2 class="wp-block-heading" id="jump-seasons">3. Seasonal windows and the autumn flood shutdown</h2>
<p>Subterranean hydrology is unforgiving. Heavy rains hundreds of kilometers upstream can cause underground river chambers to flood to the ceiling within hours:</p>
<ol>
<li><strong>March to August (Prime Expedition Window):</strong> Warm, dry weather, stable water levels, and clear subterranean river swimming. June and July are hot outside (35&deg;C+), making cool cave interiors (around 22&deg;C to 24&deg;C) remarkably refreshing.</li>
<li><strong>Late November to February (Cool Winter Window):</strong> Jungle temperatures drop to 15&deg;C to 20&deg;C. River water is chilly; operators provide wetsuits for swimming segments in Tu Lan. Pack thermal fleece layers for evenings at camp.</li>
<li><strong>September to Mid-November (Mandatory Flood Shutdown):</strong> Central Vietnam's tropical storm and monsoon season. Underground rivers rise by up to 30 meters. <strong>All multi-day caving expeditions shut down completely.</strong> Do not attempt to travel to Phong Nha for trekking during this period.</li>
</ol>

<h2 class="wp-block-heading" id="jump-gear">4. Essential gear and packing rules</h2>
<p>What you wear on a cave trek determines whether you enjoy the journey or suffer from blisters and jungle chafing:</p>
<ul>
<li><strong>Footwear:</strong> Do not wear waterproof Gore-Tex hiking boots; once water enters over your ankle during river crossings, waterproof membranes trap water inside, causing severe blisters. Wear quick-draining trail runners or military canvas jungle boots with aggressive rubber lugs.</li>
<li><strong>Apparel:</strong> Wear quick-dry synthetic long trekking pants and long-sleeved shirts to protect against sharp karst limestone edges, stinging nettles, and jungle leeches.</li>
<li><strong>Electronics:</strong> Bring heavy-duty dry bags (5L to 10L) to double-seal cameras, phones, and power banks before river swim sections.</li>
</ul>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<div class="wp-block-details">
<summary><strong>Do I need prior technical caving experience for Hang En or Tu Lan?</strong></summary>
<p>No prior caving experience is required for Hang En or standard 1 to 2-day Tu Lan treks. However, you must be capable of walking 8 to 12 kilometers per day over steep, muddy, and boulder-strewn terrain, and be comfortable swimming in deep water wearing a life jacket.</p>
</div>
<div class="wp-block-details">
<summary><strong>How far in advance must cave treks be booked?</strong></summary>
<p>Hang En and multi-day Tu Lan expeditions have strict daily group limits (typically 12 to 16 guests) to minimize ecological impact. Book 2 to 4 months in advance for travel between March and July. For Hang Son Doong, bookings open once per year and sell out within hours.</p>
</div>
<div class="wp-block-details">
<summary><strong>How do I reach Phong Nha from Hanoi or Da Nang?</strong></summary>
<p>Take the overnight sleeper train to Dong Hoi Railway Station (10 hours from Hanoi, 6 hours from Da Nang). From Dong Hoi, local buses, private taxis, or Oxalis transfer shuttles take 45 minutes to reach Phong Nha town.</p>
</div>
"""

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 61: Phu Yen Travel Guide Content Definition
Parent: destinations (ID 7)
Slug: phu-yen-travel-guide
"""

TITLE = "Phu Yen Travel Guide: Coastal Drive, Cliffs & Tips (2026)"
SLUG = "phu-yen-travel-guide"
PARENT_ID = 7

FOCUS_KEYWORD = "Phu Yen travel guide"
META_DESC = "Complete Phu Yen travel guide: explore Ganh Da Dia basalt cliffs, Mui Dien lighthouse, Bai Xep beach, O Loan lagoon seafood, routes, and coastal drives."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-phu-yen-hero:v1 -->
<section class="vg-guide-hero vg-phu-yen-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Central Coast exploration - Updated September 21, 2026</p>
<h1>Phu Yen Travel Guide: Coastal Drive, Cliffs &amp; Tips</h1>
<p class="vg-guide-lede">Located between the crowded resort hubs of Nha Trang and Quy Nhon, Phu Yen remains one of Central Vietnam's most pristine coastal frontiers. Defined by volcanic basalt sea cliffs, empty golden beaches, lagoons harvesting sweet blood cockles, and the continent's earliest sunrise, it rewards travelers who prioritize wild landscapes over developed resort nightlife. Here is your definitive Phu Yen travel guide.</p>
</div>
</section>

<div class="vg-guide-meta">
<span><strong>Region:</strong> South Central Coast (Phu Yen Province)</span>
<span><strong>Best Window:</strong> January to August (Dry, calm seas)</span>
<span><strong>Ideal Stay:</strong> 2 to 3 days (often paired with Quy Nhon)</span>
<span><strong>Hub Town:</strong> Tuy Hoa City (TBB Airport)</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>Add Phu Yen if you want raw geological coastlines, quiet coastal roads, and authentic fishing port hospitality.</strong> The 100-kilometer coastal drive connecting Quy Nhon to Tuy Hoa offers dramatic ocean headlands without tour bus congestion. <strong>Skip Phu Yen if you require luxury beach club infrastructure or English-speaking resort concierges;</strong> this is an independent exploration province where scooter mobility or a private driver is necessary.</p>
</div>

<h2 class="wp-block-heading">Phu Yen travel essentials at a glance</h2>
<div class="wp-block-table">
<table>
<thead>
<tr>
<th>Highlight</th>
<th>Location &amp; Distance from Tuy Hoa</th>
<th>Entry Fee (VND)</th>
<th>Best Visiting Window</th>
<th>Key Feature</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Gành Đá Đĩa (Stone Plates Cliff)</strong></td>
<td>An Ninh Dong (35 km north)</td>
<td>20,000 VND</td>
<td>06:30 - 08:30 or 16:00 - 17:30</td>
<td>Interlocking hexagonal basalt columns plunging into the sea</td>
</tr>
<tr>
<td><strong>Mũi Điện (Đại Lãnh Lighthouse)</strong></td>
<td>Hoa Tam (35 km south)</td>
<td>20,000 VND</td>
<td>04:45 - 06:15 (Sunrise)</td>
<td>Vietnam's earliest continental sunrise viewpoint</td>
</tr>
<tr>
<td><strong>Bãi Xép Beach</strong></td>
<td>An Chan (14 km north)</td>
<td>20,000 VND</td>
<td>08:00 - 16:30</td>
<td>Black volcanic headlands framing golden surf</td>
</tr>
<tr>
<td><strong>Đầm Ô Loan (O Loan Lagoon)</strong></td>
<td>Tuy An (30 km north)</td>
<td>Free (Meals pay per order)</td>
<td>11:30 - 14:00 (Lunch)</td>
<td>Brackish water blood cockles (sò huyết) &amp; flower crab</td>
</tr>
<tr>
<td><strong>Nhà Thờ Mằng Lăng</strong></td>
<td>An Thach (32 km north)</td>
<td>Free</td>
<td>07:30 - 17:00</td>
<td>1892 Gothic church housing Vietnam's first printed script book</td>
</tr>
</tbody>
</table>
</div>

<h2 class="wp-block-heading" id="jump-coastal-drive">1. The Quy Nhon to Phu Yen coastal drive</h2>
<p>The 100-kilometer journey between Quy Nhon and Tuy Hoa is one of the most rewarding coastal routes in Vietnam. Travelers can follow national highway QL1D and provincial route DT649 along the edge of Xuan Dai Bay instead of the congested inland truck route of QL1A.</p>
<ul>
<li><strong>Route highlights:</strong> The road winds past lobster-breeding rafts in Song Cau, through peaceful salt pans, and over rocky headlands opening onto deserted sandy coves. Pavement is well-maintained asphalt with light motorcycle traffic.</li>
<li><strong>Pacing:</strong> Allow 4 to 5 hours on a scooter or private car, incorporating stops at Ganh Da Dia, Mang Lang Church, and a seafood lunch at O Loan Lagoon before arriving in Tuy Hoa before dusk.</li>
</ul>

<h2 class="wp-block-heading" id="jump-ganh-da-dia">2. Gành Đá Đĩa: Hexagonal basalt wonder</h2>
<p>Formed millions of years ago by volcanic eruptions on the Van Hoa Plateau, molten basalt lava met cold ocean water, rapidly contracting and fracturing into thousands of vertical polygonal columns. The columns stand tightly packed like giant stacks of dinner plates stepping directly into crashing Pacific surf.</p>
<ul>
<li><strong>Photography tips:</strong> Arrive before 08:00 to avoid strong tropical glare and tour groups from Quy Nhon. Morning light illuminates the dark basalt faces against emerald sea spray.</li>
<li><strong>Safety:</strong> Rocks near the water line are covered in slick green algae; wear shoes with reliable rubber grip and avoid climbing wet seaward ledges during high tide. Entry ticket is 20,000 VND.</li>
</ul>

<h2 class="wp-block-heading" id="jump-mui-dien">3. Mũi Điện: Continental sunrise at the lighthouse</h2>
<p>Mui Dien (also known as Cape Dai Lanh) marks the easternmost point of continental Vietnam. Built by French engineers in 1890, the stone lighthouse perches 110 meters above turquoise sea waters.</p>
<ul>
<li><strong>The sunrise climb:</strong> Depart Tuy Hoa by 04:15 on a motorbike or taxi. From the parking gate at Bai Mon, climb the 1-kilometer stone staircase (approximately 25 minutes of steady uphill walking). Standing on the cliff edge as the orange sun emerges from the open Pacific horizon is one of Central Vietnam's most memorable moments.</li>
<li><strong>Bai Mon Beach:</strong> Located at the base of the lighthouse trail, this sheltered sandy cove features clean surf and zero commercial development.</li>
</ul>

<h2 class="wp-block-heading" id="jump-cuisine">4. Phu Yen culinary specialties</h2>
<p>Phu Yen cuisine reflects oceanic abundance with affordable pricing:</p>
<ul>
<li><strong>Sò Huyết Đầm Ô Loan (O Loan Blood Cockles):</strong> Harvested from the brackish waters of O Loan lagoon, these cockles are renowned for plump, sweet meat. Order them grilled over charcoal with scallion oil (<em>nướng mỡ hành</em>) or stir-fried with tamarind (<em>xào me</em>) at floating wooden restaurants along the water for 120,000 to 180,000 VND per kilogram.</li>
<li><strong>Mắt Cá Ngừ Đại Dương (Ocean Tuna Eye):</strong> Tuy Hoa is Vietnam's capital for ocean tuna fishing. A single giant tuna eye is slow-cooked in a miniature ceramic pot with Chinese medicinal herbs, ginger, and wild leaves (<em>lá giang</em>) for 40,000 to 50,000 VND. Rich, gelatinous, and deeply restorative.</li>
<li><strong>Cơm Gà Phú Yên (Tuy Hoa Chicken Rice):</strong> Rice cooked in rich chicken stock with turmeric, served with shredded free-range chicken, pickled purple shallots, and spicy herb dipping sauce at Cơm Gà Tuyết Nhung (189 Le Thanh Ton, Tuy Hoa).</li>
</ul>

<h2 class="wp-block-heading" id="jump-logistics">Logistics and seasonal timing</h2>
<ol>
<li><strong>Best travel season:</strong> The dry season runs from <strong>January through August</strong>, bringing calm blue waters and clear skies. <strong>Avoid September through December</strong>, when heavy Central Coast rains and typhoon swells cause rough seas and boat restrictions.</li>
<li><strong>How to get there:</strong> Tuy Hoa Airport (TBB) receives daily direct flights from Hanoi and Ho Chi Minh City via Vietnam Airlines and Vietjet. Alternatively, the Reunification Express train stops directly at Tuy Hoa Railway Station along the main coastal railway line.</li>
<li><strong>Local transportation:</strong> Renting a 125cc scooter (130,000 to 160,000 VND per day) gives complete freedom to explore northern and southern cliff roads. Private taxis can be arranged via hotel desks for 800,000 to 1,200,000 VND for full-day touring.</li>
</ol>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<div class="wp-block-details">
<summary><strong>How many days are needed in Phu Yen?</strong></summary>
<p>Two full days are ideal. Dedicate Day 1 to the northern circuit (Ganh Da Dia, O Loan Lagoon, Mang Lang Church, Bai Xep) and Day 2 to the southern circuit (Mui Dien sunrise, Bai Mon, Vung Ro Bay historical port).</p>
</div>
<div class="wp-block-details">
<summary><strong>Can I visit Phu Yen as a day trip from Quy Nhon?</strong></summary>
<p>Yes. Northern Phu Yen attractions like Ganh Da Dia and O Loan Lagoon sit only 35 kilometers south of Quy Nhon, making them an easy full-day excursion before returning to Quy Nhon for the night.</p>
</div>
<div class="wp-block-details">
<summary><strong>Where should I stay in Phu Yen?</strong></summary>
<p>Stay along Doc Lap Street in Tuy Hoa City for immediate beach access, local seafood dining, and evening walking promenades. For rural quiet, consider eco-lodges near Bai Xep or boutique resorts along Xuan Dai Bay.</p>
</div>
"""

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 61: Saigon Street Food Guide Content Definition
Parent: destinations (ID 7)
Slug: saigon-street-food-guide
"""

TITLE = "Saigon Street Food Guide: Night Markets & Must-Eats (2026)"
SLUG = "saigon-street-food-guide"
PARENT_ID = 7

FOCUS_KEYWORD = "Saigon street food guide"
META_DESC = "Complete Saigon street food guide: best broken rice (com tam), banh mi, District 4 seafood street, Ho Thi Ky night market, prices, and food safety advice."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-saigon-food-hero:v1 -->
<section class="vg-guide-hero vg-saigon-food-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Culinary intelligence - Updated September 21, 2026</p>
<h1>Saigon Street Food Guide: Night Markets &amp; Must-Eats</h1>
<p class="vg-guide-lede">Saigon street food is exuberant, sweet, savory, and relentlessly nocturnal. While Hanoi prizes delicate broths and subtle herbs, Ho Chi Minh City delivers bold southern garlic, chili heat, sweet marinades, and an unmatched seafood street culture where metal tables spill onto neon-lit sidewalks until two in the morning. Here is your definitive Saigon street food guide.</p>
</div>
</section>

<div class="vg-guide-meta">
<span><strong>Region:</strong> Southern Vietnam (Ho Chi Minh City)</span>
<span><strong>Price Per Meal:</strong> 35,000 to 220,000 VND ($1.40 to $8.80 USD)</span>
<span><strong>Primary Hubs:</strong> District 1, District 3, District 4, District 10</span>
<span><strong>Core Dining Hours:</strong> 17:00 to 01:00 (Night Markets &amp; Seafood Streets)</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>Head across the canal to District 4 and District 3 to escape overpriced District 1 tourist menus.</strong> Vinh Khanh Street in District 4 delivers Vietnam's finest snail and seafood street atmosphere, while alleyways in Ban Co Market serve generational southern classics at local prices. <strong>Avoid the Ben Thanh night market stalls along Phan Boi Chau street;</strong> their prices are inflated by 200% compared to authentic neighborhood alleys.</p>
</div>

<h2 class="wp-block-heading">Saigon street food essentials at a glance</h2>
<div class="wp-block-table">
<table>
<thead>
<tr>
<th>Dish Name</th>
<th>Key Ingredients</th>
<th>Benchmark Price (VND)</th>
<th>Recommended Stall</th>
<th>Best Meal Window</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Cơm Tấm (Broken Rice)</strong></td>
<td>Fractured rice grains, charcoal pork chop, steamed egg meatloaf, scallion oil</td>
<td>45,000 - 110,000 VND</td>
<td>Cơm Tấm Ba Ghiền (84 Đặng Văn Ngữ, Phú Nhuận)</td>
<td>07:00 - 21:00</td>
</tr>
<tr>
<td><strong>Bánh Mì Kẹp Thịt</strong></td>
<td>Airy crust baguette, liver pâté, cured pork ham, pork floss, pickled daikon</td>
<td>25,000 - 68,000 VND</td>
<td>Bánh Mì Huỳnh Hoa (26 Lê Thị Riêng) or Bảy Hổ (19 Huỳnh Khương Ninh)</td>
<td>All day / Late night</td>
</tr>
<tr>
<td><strong>Ốc &amp; Hải Sản (Snails &amp; Shellfish)</strong></td>
<td>Mud snails with tamarind sauce, sea snails in salted egg butter, steamed clams</td>
<td>70,000 - 180,000 VND per dish</td>
<td>Ốc Oanh (534 Vĩnh Khánh, District 4)</td>
<td>17:30 - 23:30</td>
</tr>
<tr>
<td><strong>Bò Lá Lốt (Beef in Betel Leaves)</strong></td>
<td>Minced spiced beef, wild betel leaves, rice vermicelli sheets, fermented fish dip</td>
<td>40,000 - 60,000 VND</td>
<td>Bò Lá Lốt Cô Liêng (321 Võ Văn Tần, District 3)</td>
<td>16:00 - 21:30</td>
</tr>
<tr>
<td><strong>Phá Lấu Bò (Braised Offal Stew)</strong></td>
<td>Tender beef offal, spiced coconut broth, fresh crusty bread, sweet tamarind dip</td>
<td>30,000 - 45,000 VND</td>
<td>Phá Lấu Dì Nủi (243/30 Tôn Đản, District 4)</td>
<td>14:00 - 19:00</td>
</tr>
</tbody>
</table>
</div>

<h2 class="wp-block-heading" id="jump-com-tam">1. Cơm Tấm: The heartbeat of southern mornings</h2>
<p>Originally eaten by poor farmers who salvaged fractured, unsold rice grains from mill floors, cơm tấm has evolved into Saigon's most ubiquitous comfort meal. The smaller rice grains absorb marinades and scallion oil more intensely than unbroken rice. A complete plate (<em>cơm tấm sườn bì chả</em>) features a massive charcoal-grilled pork cutlet, shredded pork skin tossed in toasted rice powder (<em>bì</em>), steamed egg and pork meatloaf (<em>chả trứng</em>), a sunny-side-up egg with runny yolk, and sweet-garlic fish sauce.</p>
<ul>
<li><strong>Cơm Tấm Ba Ghiền (84 Đặng Văn Ngữ, Phu Nhuan):</strong> Legendary for colossal pork chops marinated in condensed milk, lemongrass, and fish sauce, hanging well over the edges of the plate. It is smoky, noisy, and unpolished. A full luxury plate costs 90,000 to 110,000 VND. Take a 15-minute Grab ride from District 1; the pilgrimage is essential.</li>
<li><strong>Sidewalk Carts across District 1 &amp; 3:</strong> Look for smoke rising from roadside metal grills between 06:30 and 09:00. Neighborhood stalls serve exceptional breakfast plates for 35,000 to 45,000 VND.</li>
</ul>

<h2 class="wp-block-heading" id="jump-banh-mi">2. Bánh Mì: Saigon's iconic street sandwich</h2>
<p>Unlike French baguettes, Vietnamese bánh mì utilizes rice flour blended into wheat flour, yielding an ultra-thin, feather-crisp outer crust that shatters on impact and a hollow, airy interior. Saigon-style bánh mì is packed generously with rich liver pâté, homemade mayonnaise, multiple layers of cold cuts, pork floss, cucumber batons, cilantro, and tart pickled carrots and daikon.</p>
<ul>
<li><strong>Bánh Mì Huỳnh Hoa (26 Lê Thị Riêng, District 1):</strong> The heavyweight champion of Saigon street food. Each sandwich weighs nearly half a kilogram, crammed with up to eight layers of meat, thick pâté, and fiery green chilies for 68,000 VND ($2.70 USD). Expect a fast-moving assembly line of motorbikes and food couriers out front. Perfect to split between two people.</li>
<li><strong>Bánh Mì Bảy Hổ (19 Huỳnh Khương Ninh, Da Kao, District 1):</strong> A historic contrast operating since the 1930s. Their pork is simmered in secret pan juices that are spooned warm over freshly baked bread. Compact, balanced, and nostalgic at 25,000 VND per roll.</li>
</ul>

<h2 class="wp-block-heading" id="jump-seafood-oc">3. Ốc &amp; Hải Sản: District 4 snail feast</h2>
<p>Eating <em>ốc</em> (snails and shellfish) is Saigon's quintessential evening social ritual, known locally as <em>nhậu</em> (drinking and socializing). Diners gather around aluminum tables, crack crab claws, suck sea snails from shells, and wash everything down with cold Saigon Special beer over ice.</p>
<ul>
<li><strong>Vĩnh Khánh Street (District 4):</strong> A 500-meter gauntlet of roaring woks, smoking charcoal grills, and motorcycle exhaust just five minutes south of District 1.</li>
<li><strong>What to order:</strong>
<ul>
<li><em>Ốc mỡ xào me:</em> Mud snails stir-fried in thick, sour-sweet tamarind sauce with crispy pork fat cracklings (<em>tóp mỡ</em>).</li>
<li><em>Ốc hương sốt trứng muối:</em> Fragrant spotted Babylon snails drenched in rich, creamy salted egg yolk sauce, mopped up with warm bread.</li>
<li><em>Sò điệp nướng mỡ hành:</em> Sweet sea scallops grilled on half-shells over charcoal with bubbling scallion oil and crushed roasted peanuts.</li>
<li><em>Nghêu hấp sả:</em> Hard clams steamed in a fragrant broth of fresh lemongrass, Thai basil, and sliced chilies.</li>
</ul>
</li>
<li><strong>Where to sit:</strong> <strong>Ốc Oanh (534 Vĩnh Khánh)</strong> is the street's most boisterous institution. Dishes range from 70,000 to 180,000 VND ($2.80 to $7.20 USD).</li>
</ul>

<h2 class="wp-block-heading" id="jump-markets">4. The best street food markets and alleys</h2>
<p>Skip Westernized food courts and head directly to Saigon's authentic neighborhood food alleys:</p>
<ul>
<li><strong>Chợ Hoa Hồ Thị Kỷ (District 10):</strong> During the day, it is Saigon's largest wholesale flower market. From 16:00 until midnight, the rear alley turns into a sensory dining corridor influenced by southern Cambodian flavors. Sample Cambodian num bo chok noodle soup, grilled lemongrass beef skewers (15,000 VND each), and sweet shaved-ice desserts.</li>
<li><strong>Chợ Bàn Cờ (District 3):</strong> A tight grid of residential alleys (bounded by Nguyen Dinh Chieu and Dien Bien Phu) filled with morning stalls. Exceptional for <em>bún thịt nướng</em> (cold vermicelli with grilled pork and crispy egg rolls), <em>bánh bèo</em> steamed water fern cakes, and avocado smoothies.</li>
<li><strong>Hẻm 200 Xóm Chiếu (District 4):</strong> Known as the "food sanctuary" of District 4. A tight, motorbike-heavy alley packed with banana fritters, fried quail, noodle soups, and sweet soups (<em>chè</em>).</li>
</ul>

<h2 class="wp-block-heading" id="jump-street-etiquette">Saigon street dining rules and tips</h2>
<p>Navigating Saigon street dining is effortless when you follow these practical pointers:</p>
<ol>
<li><strong>Embrace the ice bucket:</strong> In southern heat, beer is served warm in cans or bottles with a large chunk of ice placed inside your glass (<em>bia ôm đá</em>). If you prefer your beer chilled without ice, ask for <em>bia lạnh không đá</em>.</li>
<li><strong>Watch your belongings on sidewalks:</strong> While Saigon is generally safe, motorcycle drive-by phone snatching can occur on open sidewalk corners. Keep smartphones inside your bag and avoid resting them loosely on street tables facing the street.</li>
<li><strong>Carry cash and small change:</strong> Sidewalk vendors do not accept international credit cards. Carry bills in denominations of 10,000, 20,000, and 50,000 VND for seamless transactions.</li>
</ol>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<div class="wp-block-details">
<summary><strong>What is the difference between Hanoi and Saigon street food?</strong></summary>
<p>Hanoi street food emphasizes delicate, clear broths, savory fish sauce, and fresh herbs without added sugar. Saigon street food incorporates bold southern sweetness, coconut cream, fresh garlic, tamarind sauces, and a massive nocturnal seafood culture.</p>
</div>
<div class="wp-block-details">
<summary><strong>Where can I eat street food late at night in Saigon?</strong></summary>
<p>Vinh Khanh Street in District 4 operates until midnight or 01:00. For dining after 01:00, visit Tan Dinh Market (District 1) for late-night pork rib congee (<em>cháo sườn</em>) or noodle soups along Nguyen Trai Street (District 5).</p>
</div>
<div class="wp-block-details">
<summary><strong>How much should I budget per day for street food in Saigon?</strong></summary>
<p>A generous daily street food budget is 200,000 to 350,000 VND ($8 to $14 USD) per person, covering a hearty breakfast of com tam, an afternoon banh mi with iced coffee, and an evening multi-dish seafood feast with beers.</p>
</div>
"""

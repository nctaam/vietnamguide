# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 61: Da Nang Street Food Guide Content Definition
Parent: destinations (ID 7)
Slug: da-nang-street-food-guide
"""

TITLE = "Da Nang Street Food Guide: Seafood, Mi Quang & Gems (2026)"
SLUG = "da-nang-street-food-guide"
PARENT_ID = 7

FOCUS_KEYWORD = "Da Nang street food guide"
META_DESC = "Complete Da Nang street food guide: where to eat authentic mi quang, banh xeo Ba Duong, Nam O fish salad, coastal seafood, Con Market, and local prices."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-danang-food-hero:v1 -->
<section class="vg-guide-hero vg-danang-food-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Culinary intelligence - Updated September 21, 2026</p>
<h1>Da Nang Street Food Guide: Seafood, Mi Quang &amp; Gems</h1>
<p class="vg-guide-lede">Da Nang is Central Vietnam's culinary powerhouse, where oceanic abundance meets intense central seasoning. Characterized by turmeric-stained noodles, rich liver-peanut dipping sauces, fiery chili jams, and beachfront tanks of live clams and sea snails, eating here is remarkably affordable compared to Hanoi or Saigon. Here is your definitive Da Nang street food guide.</p>
</div>
</section>

<div class="vg-guide-meta">
<span><strong>Region:</strong> Central Coast (Da Nang)</span>
<span><strong>Price Per Meal:</strong> 30,000 to 180,000 VND ($1.20 to $7.20 USD)</span>
<span><strong>Primary Hubs:</strong> Hai Chau downtown &amp; My Khe beachfront</span>
<span><strong>Core Dining Hours:</strong> 06:30-09:30 (Morning Noodles), 16:30-22:30 (Seafood &amp; Markets)</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>Split your meals between downtown Hai Chau for heritage noodle bowls and Son Tra beachfront for live seafood.</strong> Hai Chau contains generational specialists like Banh Xeo Ba Duong and legendary Mi Quang shops tucked inside residential alleys. <strong>For seafood along Vo Nguyen Giap street, always select live shellfish from aerated tanks</strong> and verify the price per kilogram before cooking to avoid bill surprises.</p>
</div>

<h2 class="wp-block-heading">Da Nang street food essentials at a glance</h2>
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
<td><strong>Mì Quảng (Quang-Style Noodles)</strong></td>
<td>Turmeric rice noodles, shallow pork-shrimp broth, peanuts, rice crackers</td>
<td>35,000 - 55,000 VND</td>
<td>Mì Quảng Bà Mua (19 Trần Bình Trọng) or 1A Hải Phòng</td>
<td>06:30 - 13:00</td>
</tr>
<tr>
<td><strong>Bánh Xèo &amp; Nem Lụi</strong></td>
<td>Crispy golden crepes, pork skewers, rice paper, rich liver dipping sauce</td>
<td>50,000 - 90,000 VND</td>
<td>Bánh Xèo Bà Dưỡng (K280/23 Hoàng Diệu)</td>
<td>11:00 - 21:30</td>
</tr>
<tr>
<td><strong>Bún Chả Cá (Fish Cake Noodle Soup)</strong></td>
<td>Handmade mackerel patties, sweet broth simmered with pumpkin &amp; pineapple</td>
<td>35,000 - 50,000 VND</td>
<td>Bún Chả Cá 109 (109 Nguyễn Chí Thanh)</td>
<td>06:00 - 11:00</td>
</tr>
<tr>
<td><strong>Chíp Chíp Hấp Sả (Steamed Clams)</strong></td>
<td>Local hard-shell clams, fresh lemongrass stalks, Thai basil, lime chili salt</td>
<td>80,000 - 120,000 VND</td>
<td>Hải Sản Bé Mặn (Lô 11 Võ Nguyên Giáp)</td>
<td>17:00 - 22:30</td>
</tr>
<tr>
<td><strong>Kem Bơ (Avocado Coconut Ice Cream)</strong></td>
<td>Mashed ripe buttery avocado, homemade coconut ice cream, toasted shavings</td>
<td>20,000 - 25,000 VND</td>
<td>Kem Bơ Cô Vân (Chợ Bắc Mỹ An)</td>
<td>10:00 - 18:00</td>
</tr>
</tbody>
</table>
</div>

<h2 class="wp-block-heading" id="jump-mi-quang">1. Mì Quảng: The soul of Central Vietnam</h2>
<p>Unlike northern phở or southern hủ tiếu, mì quảng is not a traditional soup. It is served with only a ladleful of intensely concentrated, savory broth at the bottom of the bowl, just enough to coat wide turmeric-tinted rice noodles. Toppings include braised pork belly, fresh prawns, hard-boiled quail eggs, crushed roasted peanuts, and crispy toasted sesame rice crackers (<em>bánh tráng mè</em>) broken over the top.</p>
<ul>
<li><strong>How to eat:</strong> Break the sesame cracker into crunchy bite-sized pieces into your bowl. Toss with fresh herbs (banana blossom threads, mustard greens, mint, coriander) and a spoonful of roasted chili jam (<em>ớt rim</em>). The cracker softens slightly while lending crunch to the chew of the noodles.</li>
<li><strong>Where to go:</strong>
<ul>
<li><strong>Mì Quảng Bà Mua (19 Trần Bình Trọng, Hai Chau):</strong> Consistently excellent across its locations. Order <em>mì quảng tôm thịt</em> (shrimp and pork) or <em>mì quảng gà</em> (free-range country chicken) for 40,000 to 50,000 VND.</li>
<li><strong>Mì Quảng 1A (1A Hải Phòng, Hai Chau):</strong> A local neighborhood favorite since the 1990s, renowned for tender stewed pork ribs and deeply savory reduced bone broth.</li>
</ul>
</li>
</ul>

<h2 class="wp-block-heading" id="jump-banh-xeo">2. Bánh Xèo Bà Dưỡng: Alleyway pancake mastery</h2>
<p>Central-style bánh xèo is smaller and thicker than the giant southern variety, fried in small cast-iron pans until the exterior achieves an addictive, shattering crunch while retaining a moist center filled with whole bean sprouts, sweet local shrimp, and pork slices.</p>
<ul>
<li><strong>The secret dipping sauce (nước lèo):</strong> What elevates Da Nang's pancakes above all others is the sauce. Instead of standard watery fish sauce, Da Nang serves a thick, warm, savory-sweet emulsion made from simmered pork liver, roasted peanuts, toasted sesame, and fermented soybeans.</li>
<li><strong>How to wrap:</strong> Lay a sheet of soft dry rice paper flat on your hand. Add a strip of pancake, fresh lettuce, cucumber, sour star fruit, green banana slices, and a stick of charcoal-grilled pork on lemongrass skewers (<em>nem lụi</em>). Roll tightly, pull out the lemongrass skewer, and dip deeply into the liver sauce.</li>
<li><strong>Where to find it:</strong> <strong>Bánh Xèo Bà Dưỡng (K280/23 Hoàng Diệu, Hai Chau)</strong>. Walk 150 meters to the very end of alley K280. A feast for two with pancakes and nem lui costs approximately 120,000 to 160,000 VND ($5 to $6.50 USD).</li>
</ul>

<h2 class="wp-block-heading" id="jump-seafood">3. Coastal seafood: Chíp chíp and beachfront feasts</h2>
<p>Positioned right along the East Sea, Da Nang offers some of Southeast Asia's freshest and most affordable coastal seafood, concentrated along Vo Nguyen Giap beachfront in Son Tra and along the Han River.</p>
<ul>
<li><strong>Chíp Chíp Hấp Sả (Steamed Local Clams):</strong> Chíp chíp is a small, sweet triangular clam endemic to the central coast. Steamed in metal bowls with crushed lemongrass stalks, lime leaves, and bird's eye chilies. Dip clam meat into green chili salt with fresh lime (<em>muối ớt xanh</em>). A generous bowl costs 80,000 to 100,000 VND.</li>
<li><strong>Sò Điệp Nướng Mỡ Hành:</strong> Fresh sea scallops grilled over charcoal embers with bubbling scallion lard and crushed peanuts.</li>
<li><strong>Where to sit:</strong> <strong>Hải Sản Bé Mặn (Lô 11 Võ Nguyên Giáp, Son Tra)</strong> is massive, loud, and honest. Walk to the aerated saltwater tanks, point to what you want, have it weighed in front of you, specify cooking style (steamed with lemongrass, grilled with scallion oil, or stir-fried with tamarind), and take a metal table overlooking the surf.</li>
</ul>

<h2 class="wp-block-heading" id="jump-markets">4. Con Market food court and Bac My An avocado ice cream</h2>
<p>For midday snacking away from the midday coastal sun, explore Da Nang's historic food markets:</p>
<ul>
<li><strong>Chợ Cồn (Con Market, corner of Hung Vuong &amp; Ong Ich Khiem):</strong> Walk into the central indoor food court (<em>khu ẩm thực</em>) between 14:00 and 18:00. Dozens of vendor stalls serve steamed water fern cakes (<em>bánh bèo</em>), tapioca shrimp dumplings (<em>bánh bột lọc</em>), grilled pork noodle bowls, and sweet iced dessert soups (<em>chè</em>) for 15,000 to 30,000 VND per dish.</li>
<li><strong>Chợ Bắc Mỹ An (Ngu Hanh Son):</strong> A neighborhood residential market famous for Da Nang's iconic dessert: <strong>Kem Bơ Cô Vân</strong>. Ripe, buttery green avocado is hand-mashed into a glass, topped with two scoops of creamy coconut ice cream, sweet condensed milk, and a handful of toasted coconut shavings for just 20,000 VND ($0.80 USD).</li>
</ul>

<h2 class="wp-block-heading" id="jump-street-etiquette">Dining practicalities and market navigation</h2>
<ol>
<li><strong>Confirm seafood weight:</strong> At live beachfront seafood restaurants, the price board shows cost per kilogram. Staff will scoop live seafood into a bucket and weigh it on a spring scale. Watch the scale and confirm the weight before it heads to the kitchen.</li>
<li><strong>Condiment awareness:</strong> Central Vietnamese food is significantly spicier than northern cuisine. Central chili jam (<em>ớt rim</em>) is sweet, oily, and intensely hot. Sample a tiny dab before coating your noodles.</li>
<li><strong>Transport between food hubs:</strong> Hai Chau downtown and My Khe beach are separated by the Han River (approximately 3 to 4 kilometers). A GrabCar or GrabBike ride across the Dragon Bridge takes under ten minutes and costs 20,000 to 45,000 VND.</li>
</ol>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<div class="wp-block-details">
<summary><strong>Is Da Nang street food cheaper than Hanoi and Saigon?</strong></summary>
<p>Yes. Classic noodle dishes like mi quang, bun cha ca, and market snacks in Da Nang are typically 20% to 30% cheaper than equivalent meals in the two major metropolitan hubs, with most street bowls averaging 35,000 to 45,000 VND.</p>
</div>
<div class="wp-block-details">
<summary><strong>What is the difference between Mi Quang and Cao Lau?</strong></summary>
<p>While both are central noodle dishes, mi quang uses soft turmeric rice noodles with a shallow broth and various proteins (pork, chicken, shrimp). Cao lau is exclusive to neighboring Hoi An, made with chewy, thick, lye-water-soaked noodles, dry five-spice char siu pork, and no broth.</p>
</div>
<div class="wp-block-details">
<summary><strong>Where can I eat street food near My Khe Beach?</strong></summary>
<p>Along Vo Nguyen Giap and Pham Van Dong avenues you will find major seafood restaurants like Be Man. For affordable local noodles, walk two blocks inland to Chau Thi Vinh Te street, which is packed with neighborhood student eateries.</p>
</div>
"""

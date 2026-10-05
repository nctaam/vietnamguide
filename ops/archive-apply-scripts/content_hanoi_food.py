# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 61: Hanoi Street Food Guide Content Definition
Parent: destinations (ID 7)
Slug: hanoi-street-food-guide
"""

TITLE = "Hanoi Street Food Guide: Best Dishes, Stalls & Tips (2026)"
SLUG = "hanoi-street-food-guide"
PARENT_ID = 7

FOCUS_KEYWORD = "Hanoi street food guide"
META_DESC = "Complete Hanoi street food guide: where to eat pho bo, bun cha, cha ca, banh cuon, and egg coffee. Old Quarter stall addresses, prices, and hygiene tips."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-hanoi-food-hero:v1 -->
<section class="vg-guide-hero vg-hanoi-food-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Culinary intelligence - Updated September 21, 2026</p>
<h1>Hanoi Street Food Guide: Best Dishes, Stalls &amp; Tips</h1>
<p class="vg-guide-lede">Hanoi street food is defined by restraint, clear broths, fresh mountain herbs, and low plastic stools clustered along narrow sidewalks. From boiling cauldrons of marrow bone broth to smoking charcoal grills on street corners, eating well in the capital requires knowing exact stall addresses, ordering etiquette, and operating hours. Here is your definitive Hanoi street food guide.</p>
</div>
</section>

<div class="vg-guide-meta">
<span><strong>Region:</strong> Northern Vietnam (Hanoi Capital)</span>
<span><strong>Price Per Meal:</strong> 40,000 to 180,000 VND ($1.60 to $7.20 USD)</span>
<span><strong>Primary Hub:</strong> Old Quarter (Hoan Kiem) &amp; Ba Dinh</span>
<span><strong>Core Dining Hours:</strong> 06:30-09:30 (Breakfast), 11:30-13:30 (Lunch), 18:00-21:30 (Dinner)</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>Eat street food for breakfast and lunch to taste Hanoi at its peak freshness.</strong> Morning cauldrons of pho broth and midday charcoal grills for bun cha operate at maximum turnover between 07:00 and 12:30. <strong>Do not default to tourist restaurants with laminated English menus;</strong> the best versions of every classic northern dish are served by single-item sidewalk specialists who have occupied the same alley entrance for thirty years.</p>
</div>

<h2 class="wp-block-heading">Hanoi street food essentials at a glance</h2>
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
<td><strong>Phở Bò (Beef Noodle Soup)</strong></td>
<td>Simmered marrow bones, flat rice noodles, rare flank, scallions</td>
<td>55,000 - 90,000 VND</td>
<td>Phở Gia Truyền (49 Bát Đàn)</td>
<td>06:30 - 09:00</td>
</tr>
<tr>
<td><strong>Bún Chả (Grilled Pork Noodles)</strong></td>
<td>Charcoal-grilled pork patties, cold rice vermicelli, green papaya, fish sauce</td>
<td>45,000 - 70,000 VND</td>
<td>Bún Chả Đắc Kim (1 Hàng Mành) or ngõ 74 Hàng Quạt</td>
<td>11:00 - 13:30</td>
</tr>
<tr>
<td><strong>Chả Cá Lăng (Turmeric Fish)</strong></td>
<td>Marinated catfish fillets, fresh dill, spring onions, shrimp paste</td>
<td>150,000 - 180,000 VND</td>
<td>Chả Cá Thăng Long (6B Đường Thành)</td>
<td>12:00 - 14:00 or Dinner</td>
</tr>
<tr>
<td><strong>Bánh Cuốn (Steamed Rice Rolls)</strong></td>
<td>Fermented rice sheets, minced pork, wood ear mushroom, crispy shallots</td>
<td>40,000 - 55,000 VND</td>
<td>Bánh Cuốn Bà Hanh (26B Thọ Xương)</td>
<td>07:00 - 11:00</td>
</tr>
<tr>
<td><strong>Cà Phê Trứng (Egg Coffee)</strong></td>
<td>Whipped egg yolk, condensed milk, dark roasted Robusta coffee</td>
<td>35,000 - 45,000 VND</td>
<td>Café Giảng (39 Nguyễn Hữu Huân)</td>
<td>Mid-morning or Afternoon</td>
</tr>
</tbody>
</table>
</div>

<h2 class="wp-block-heading" id="jump-pho-bo">1. Phở Bò: The benchmark noodle broth</h2>
<p>Northern phở differs sharply from its southern counterpart. In Hanoi, broth is subtle, clear, and delicate, relying on hours of simmering beef marrow bones, charred ginger, star anise, and fish sauce. You will not find bean sprouts, hoisin sauce, or piles of basil on northern tables. Instead, you season your bowl with fresh lime or calamansi juice, pickled garlic slices in white vinegar (giấm tỏi), and bird's eye chilies.</p>
<ul>
<li><strong>Phở Gia Truyền Bát Đàn (49 Bát Đàn, Hoan Kiem):</strong> The classic Old Quarter institution. Expect a 15-minute queue along the curb. Pay first at the wooden counter, take your steaming bowl directly to a low shared table, and order <em>phở tái nạm</em> (half flash-poached rare beef, half tender cooked flank) for 65,000 VND. Arrive before 08:30 to avoid sold-out cuts.</li>
<li><strong>Phở Thìn Lò Đúc (13 Lò Đúc, Hai Ba Trung):</strong> A divisive, full-throttle alternative south of the French Quarter. Chef Thìn flash-fries beef in a smoking wok with heaps of crushed garlic before ladling it over noodles and burying the bowl under a forest of chopped green scallions. The broth is rich and fatty at 90,000 VND per bowl. Pair with crispy fried crullers (<em>quẩy</em>, 10,000 VND for three).</li>
</ul>

<h2 class="wp-block-heading" id="jump-bun-cha">2. Bún Chả: Charcoal smoke and sweet-tart broth</h2>
<p>Bún chả is Hanoi's signature midday meal. Fatty pork slices (<em>chả miếng</em>) and seasoned minced pork patties (<em>chả viên</em>) are clamped into wire hand-grates and grilled over open mangrove charcoal on sidewalk braziers. The caramelized meat is dropped hot into a bowl of warm, diluted fish sauce sweetened with sugar and balanced with vinegar, pickled carrot slices, and green papaya.</p>
<ul>
<li><strong>Bún Chả ngõ 74 Hàng Quạt (Hoan Kiem):</strong> Tucked deep into a residential alley less than two meters wide. You sit elbow-to-elbow on miniature red plastic stools while smoke fills the corridor. Patties here feature crisp, blackened edges with moist interiors. A standard bowl with herbs and rice noodles costs 45,000 to 50,000 VND. Open strictly from 10:30 to 14:00.</li>
<li><strong>Bún Chả Hương Liên (24 Lê Văn Hưu, Hai Ba Trung):</strong> Famously nicknamed "Bún Chả Obama" after Anthony Bourdain brought the US President here in 2016. Order the "Combo Obama" (bún chả, fried crab spring roll <em>nem cua bể</em>, and cold Hanoi Beer) for 120,000 VND. Quality remains consistent, though expect air-conditioned dining room bustle across three floors.</li>
</ul>

<h2 class="wp-block-heading" id="jump-cha-ca">3. Chả Cá: Sizzling turmeric fish and dill</h2>
<p>Created in the late 19th century, chả cá is Hanoi's most ceremonial specialty. Marinated river catfish fillets (traditionally <em>cá lăng</em>) seasoned with turmeric, galangal, and fermented rice paste are cooked tableside in a hot skillet with generous handfuls of fresh dill and spring onion stems.</p>
<ul>
<li><strong>How to eat:</strong> Place a small nest of cold rice vermicelli in your bowl. Add roasted peanuts, fresh coriander, and fried fish pieces straight from the bubbling pan. Spoon over a dash of fermented shrimp paste (<em>mắm tôm</em>) whipped with lime juice, sugar, and chili until frothy. If shrimp paste is too pungent for your palate, ask for standard fish sauce (<em>nước mắm</em>).</li>
<li><strong>Where to go:</strong> <strong>Chả Cá Thăng Long (6B Đường Thành, Hoan Kiem)</strong> serves pristine fillets with less grease and better ventilation than the historic Chả Cá Lã Vọng original. A complete set costs 180,000 VND ($7.20 USD) per person.</li>
</ul>

<h2 class="wp-block-heading" id="jump-banh-cuon">4. Bánh Cuốn: Delicate steamed rice sheets</h2>
<p>Bánh cuốn requires exceptional hand dexterity. The cook ladles fermented rice batter over a taut cloth stretched across a cauldron of boiling water, covers it with a conical bamboo lid for thirty seconds, and lifts the paper-thin sheet with a bamboo stick. The wrapper is filled with minced pork, wood ear mushrooms, and shallots, then rolled and sprinkled with fried crispy shallots.</p>
<ul>
<li><strong>Bánh Cuốn Bà Hanh (26B Thọ Xương, Hoan Kiem):</strong> Located near St. Joseph's Cathedral. They grind their own rice batter fresh every morning and avoid chemical softeners. Dip rolls into warm dipping sauce flavored with cinnamon pork sausage (<em>chả quế</em>). Expect to pay 45,000 to 55,000 VND per plate.</li>
<li><strong>Bánh Cuốn Gia An:</strong> A reliable local chain across Hanoi for travelers who want immaculate, air-conditioned seating without compromising the texture of fresh steamed rice flour rolls.</li>
</ul>

<h2 class="wp-block-heading" id="jump-egg-coffee">5. Cà Phê Trứng: Hanoi's liquid dessert</h2>
<p>Invented during the 1946 French war when fresh dairy milk was scarce in Hanoi, egg coffee blends raw egg yolk whipped with sweetened condensed milk into a thick, airy meringue foam poured over hot Robusta coffee.</p>
<ul>
<li><strong>Café Giảng (39 Nguyễn Hữu Huân, Hoan Kiem):</strong> The birthplace of egg coffee, founded by former Metropole Hotel bartender Nguyen Van Giang. The drink arrives resting inside a bowl of hot water to keep the whipped egg custard warm. Cost is 35,000 VND. Sip through the foam to taste the dark, bitter espresso underneath.</li>
<li><strong>Café Đinh (13 Đinh Tiên Hoàng, Hoan Kiem):</strong> Operated by Giang's daughter on the second floor of an unlabelled French colonial shophouse overlooking Hoan Kiem Lake. Walk through a shoe shop and climb narrow concrete stairs. A vintage, bohemian haunt favored by local university students.</li>
</ul>

<h2 class="wp-block-heading" id="jump-street-etiquette">Sidewalk hygiene and dining etiquette</h2>
<p>Eating street food safely in Hanoi is straightforward once you observe three fundamental local rules:</p>
<ol>
<li><strong>Follow high customer turnover:</strong> Choose stalls crowded with local families and office workers. Rapid turnover guarantees that broth remains scalding hot and herbs are freshly washed, never sitting in warm humidity.</li>
<li><strong>Inspect the ice:</strong> Cylindrical machine-made ice tubes with a hollow center (<em>đá ống</em>) are made with purified commercial water and safe to consume with iced tea (<em>trà đá</em>). Avoid crushed ice chipped by hand from large street blocks.</li>
<li><strong>Clean utensils yourself:</strong> Sidewalk tables provide lime wedges, paper tissues, and chili sauce. Take a tissue and wipe your chopsticks and metal spoon before eating, exactly as locals do. Small moist towelettes (<em>khăn lạnh</em>) placed on your table are not complimentary; using one adds 2,000 to 5,000 VND to your final bill.</li>
</ol>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<div class="wp-block-details">
<summary><strong>Is Hanoi street food safe for sensitive stomachs?</strong></summary>
<p>Yes, provided you choose high-turnover stalls serving piping hot food. Avoid raw blood puddings (<em>tiết canh</em>) and unpeeled fresh fruit from mobile street vendors. Bottled water or hot tea is recommended over unverified ice.</p>
</div>
<div class="wp-block-details">
<summary><strong>What is the best area in Hanoi for a self-guided food walk?</strong></summary>
<p>The northern half of the Old Quarter bounded by Hang Buom, Hang Manh, Bat Dan, and Dong Xuan Market offers the dense concentration of heritage stalls within a flat, 1.5-kilometer walking radius.</p>
</div>
<div class="wp-block-details">
<summary><strong>How do I pay at Hanoi street food stalls?</strong></summary>
<p>Cash is king at sidewalk stalls. Carry small notes of 20,000, 50,000, and 100,000 VND; street vendors frequently cannot break 500,000 VND bills for a 40,000 VND breakfast bowl. Vietnamese bank transfer apps (VietQR) are widely accepted if you maintain a local bank account.</p>
</div>
"""

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 62: Vietnam Coffee Guide Content Definition
Parent: destinations (ID 7)
Slug: vietnam-coffee-guide
"""

TITLE = "Vietnam Coffee Guide: Egg, Salt, Coconut & Cafes (2026)"
SLUG = "vietnam-coffee-guide"
PARENT_ID = 7

FOCUS_KEYWORD = "Vietnam coffee guide"
META_DESC = "Complete Vietnam coffee guide: authentic egg coffee, salt coffee, coconut coffee, robusta phin culture, specialty roasters, and iconic cafes nationwide."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-coffee-hero:v1 -->
<section class="vg-guide-hero vg-coffee-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Culinary culture &amp; specialty beans - Updated September 21, 2026</p>
<h1>Vietnam Coffee Guide: Egg, Salt, Coconut &amp; Cafes</h1>
<p class="vg-guide-lede">Vietnam is the world's second-largest coffee producer and its undisputed champion of bold, full-bodied Robusta. But coffee here is far more than an agricultural commodity; it is a national way of life, an unhurried social ritual played out on low plastic sidewalk stools from dawn until midnight. From Hanoi's legendary egg coffee to Hue's savory salt brew, Da Lat's high-altitude specialty Arabica farms, and Saigon's iconic iced milk coffee, here is your definitive Vietnam coffee guide.</p>
</div>
</section>

<div class="vg-guide-meta">
<span><strong>Global Rank:</strong> #2 Producer Worldwide (Top Exporter of Robusta)</span>
<span><strong>Brewing Instrument:</strong> Traditional metal Phin filter</span>
<span><strong>Average Price:</strong> 15,000–35,000 VND (Street) / 45,000–85,000 VND (Specialty)</span>
<span><strong>Key Roasting Terroirs:</strong> Buon Ma Thuot (Robusta) &amp; Da Lat / Cau Dat (Arabica)</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>Vietnamese coffee is significantly stronger than Western espresso or filter coffee—Robusta beans pack nearly twice the caffeine content of Arabica.</strong> Pace yourself if you are sensitive to caffeine spikes. <strong>Must-try regional milestones include Cà phê trứng at Cafe Giảng in Hanoi, Cà phê muối on Nguyễn Huệ street in Hue, and a classic Cà phê sữa đá on a street corner in Saigon.</strong> For single-origin specialty pour-overs, seek out third-wave roasters in Da Lat and Da Nang sourcing high-altitude Cau Dat Arabica.</p>
</div>

<h2 class="wp-block-heading">Iconic Vietnamese coffee varieties at a glance</h2>
<div class="wp-block-table">
<table>
<thead>
<tr>
<th>Beverage Name</th>
<th>Vietnamese Name</th>
<th>Key Ingredients</th>
<th>Birth City</th>
<th>Flavor Profile</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Iced Milk Coffee</strong></td>
<td>Cà phê sữa đá</td>
<td>Dark robusta phin brew, sweetened condensed milk, crushed ice</td>
<td>Saigon</td>
<td>Bold, caramel-sweet, intensely refreshing</td>
</tr>
<tr>
<td><strong>Egg Coffee</strong></td>
<td>Cà phê trứng</td>
<td>Robusta coffee, whisked egg yolks, condensed milk, sugar</td>
<td>Hanoi (1946)</td>
<td>Rich, custardy, tiramisu-like dessert froth</td>
</tr>
<tr>
<td><strong>Salt Coffee</strong></td>
<td>Cà phê muối</td>
<td>Dark drip coffee, condensed milk, fermented salted cream froth</td>
<td>Hue (2010)</td>
<td>Salty-sweet, velvety, balances dark bean bitterness</td>
</tr>
<tr>
<td><strong>Coconut Coffee</strong></td>
<td>Cà phê cốt dừa</td>
<td>Black espresso/phin shot, blended frozen coconut milk slush</td>
<td>Hanoi</td>
<td>Tropical, nutty, ice-blended, silky smooth</td>
</tr>
<tr>
<td><strong>White Coffee</strong></td>
<td>Bạc xỉu</td>
<td>Steamed fresh milk, generous condensed milk, light dash of coffee</td>
<td>Saigon (Cholon)</td>
<td>Milky, gentle sweetness with subtle coffee aroma</td>
</tr>
<tr>
<td><strong>Black Iced Coffee</strong></td>
<td>Cà phê đen đá</td>
<td>Pure robusta drip brew, sugar (optional), ice cubes</td>
<td>Nationwide</td>
<td>Intense, smoky, bitter, chocolate notes</td>
</tr>
</tbody>
</table>
</div>

<h2 class="wp-block-heading" id="jump-phin">1. The traditional Phin filter and brewing art</h2>
<p>The slow-drip <em>phin</em> filter is the mechanical heart of Vietnamese coffee culture:</p>
<ul>
<li><strong>How It Works:</strong> A simple, four-piece stainless steel or aluminum apparatus consisting of a perforated plate, brewing chamber, gravity press (or screw filter), and lid. Coarsely ground dark-roast coffee sits inside, blooms with a small splash of 95°C water for thirty seconds, and then drips drop-by-slow-drop into a glass below over four to six minutes.</li>
<li><strong>Condensed Milk Partnership:</strong> Because the French introduced coffee in the mid-19th century when fresh dairy was scarce in tropical Indochina, locals paired dark, bitter Robusta with shelf-stable sweetened condensed milk (most famously the ubiquitous <em>Sữa đặc Ông Thọ</em> brand). The dense milk sits at the bottom of the glass in a creamy golden layer, meant to be vigorously stirred with a long spoon before pouring over crushed ice.</li>
</ul>

<h2 class="wp-block-heading" id="jump-regional">2. Four legendary regional coffee creations</h2>
<p>Each major region of Vietnam has engineered its own ingenious answer to the coffee bean:</p>
<ol>
<li><strong>Hanoi's Egg Coffee (Cà Phê Trứng):</strong> Invented in 1946 by Nguyen Van Giang, a former head bartender at Hanoi's grand Sofitel Legend Metropole hotel during wartime milk shortages. By vigorously whipping fresh chicken egg yolks with sweetened condensed milk, he created a rich, marshmallow-like meringue that floats atop steaming robusta. Visit the original <strong>Cafe Giảng</strong> (39 Nguyen Huu Huan, Old Quarter) or <strong>Cafe Đinh</strong> overlooking Hoan Kiem Lake.</li>
<li><strong>Hue's Salt Coffee (Cà Phê Muối):</strong> Originating in the former royal capital in 2010 at a cozy shop on Nguyen Hue street. A layer of sea salt-whipped cream sits over condensed milk and dark brew. The subtle saltiness suppresses the inherent astringency of the coffee while highlighting deep chocolate and caramel tones.</li>
<li><strong>Coconut Slush Coffee (Cà Phê Cốt Dừa):</strong> Popularized across the nation by vintage-military-themed chain <strong>Cộng Cà Phê</strong>. Creamy coconut milk is blended with condensed milk and ice into a thick slushie, then crowned with a shot of strong dark coffee. It is the ultimate afternoon antidote to tropical humidity.</li>
<li><strong>Saigon's Bạc Xỉu:</strong> Created by Cantonese immigrants in Saigon's historic Cholon (Chinatown) neighborhood who found pure robusta too bitter. It reverses the ratio: primarily hot fresh milk and condensed milk with just a splash of coffee for fragrance.</li>
</ol>

<h2 class="wp-block-heading" id="jump-specialty">3. Robusta vs. Arabica and the third-wave roasters</h2>
<p>While 95% of Vietnam's harvest is commercial-grade Robusta grown in the red basalt volcanic soils of Buon Ma Thuot (Dak Lak province), a sophisticated third-wave specialty movement is flourishing:</p>
<ul>
<li><strong>Da Lat's High-Altitude Terroir:</strong> In Cau Dat, situated at 1,500 to 1,650 meters above sea level, mist-shrouded mountain slopes cultivate delicate Arabica varietals (Catimor, Typica, Bourbon) with bright citrus acidity and floral jasmine aromatics.</li>
<li><strong>Fine Robusta Revolution:</strong> Progressive local roasters are proving that meticulously processed, ripe-harvested Fine Robusta delivers complex tasting notes of dark cocoa, roasted hazelnuts, and dried figs without burnt rubber harshness.</li>
<li><strong>Iconic Specialty Roasters:</strong> Seek out <strong>The Workshop</strong> and <strong>Bosgaurus Coffee Roasters</strong> in Ho Chi Minh City, <strong>43 Factory Coffee Roaster</strong> in Da Nang, and <strong>Loading T</strong> or <strong>Yen Cafe</strong> in Hanoi for precision pour-overs, V60s, and cold brews.</li>
</ul>

<h2 class="wp-block-heading" id="jump-ordering">4. Practical ordering guide and useful phrases</h2>
<p>Ordering coffee like a seasoned local is simple once you master these basic terms:</p>
<ul>
<li><em>"Cho tôi một cà phê sữa đá"</em> (Pronounced: Chaw toy mot ca-feh sua dah) = Please give me an iced coffee with condensed milk.</li>
<li><em>"Cho tôi một cà phê đen đá, ít đường"</em> = Please give me an iced black coffee, less sugar.</li>
<li><em>"Cà phê nóng"</em> = Hot coffee.</li>
<li><em>"Ít ngọt"</em> = Less sweet (vital if you find condensed milk overly sugary).</li>
<li><em>"Mang về"</em> = Takeaway / to go.</li>
</ul>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<div class="wp-block-details">
<summary><strong>Is Vietnamese coffee safe for travelers with sensitive stomachs?</strong></summary>
<p>Yes, coffee in Vietnam is brewed with boiling water and served hot or over commercial block ice. However, because Robusta contains almost double the caffeine of standard Arabica and condensed milk is rich in lactose, drink slowly on your first morning to test your tolerance.</p>
</div>
<div class="wp-block-details">
<summary><strong>What whole coffee beans should I buy to take home?</strong></summary>
<p>Look for whole beans labeled "100% Cà Phê Nguyên Chất" (100% Pure Coffee) from reputable specialty roasters (such as Cau Dat Farm, Shin Coffee, or Bosgaurus). Avoid cheap pre-ground market packets flavored with artificial butter, chicory, or roasted soy filler.</p>
</div>
<div class="wp-block-details">
<summary><strong>What is the complimentary tea served with Vietnamese coffee?</strong></summary>
<p>Almost every cafe in Vietnam automatically serves a tall glass of light, chilled iced tea called <em>trà đá</em> (green or jasmine tea). It is free or costs 2,000–5,000 VND, designed to cleanse your palate between deep sips of intense coffee.</p>
</div>

<p>For more culinary and cultural planning, pair this guide with our <a href="/destinations/hanoi-street-food-guide/">Hanoi Street Food Guide</a>, discover nocturnal dishes in the <a href="/destinations/saigon-street-food-guide/">Saigon Street Food Guide</a>, explore the mountain roasters in our <a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide</a>, and review dining manners in <a href="/plan/vietnam-food-safety-street-food-etiquette/">Vietnam Food Safety &amp; Etiquette</a>.</p>
"""

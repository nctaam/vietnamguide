# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 63: Tap Water in Vietnam Content Definition
Parent: plan (ID 6)
Slug: tap-water-in-vietnam
"""

TITLE = "Tap Water in Vietnam: Drinking, Ice, Brushing Teeth (2026)"
SLUG = "tap-water-in-vietnam"
PARENT_ID = 6

FOCUS_KEYWORD = "Tap water in Vietnam"
META_DESC = "Can you drink tap water in Vietnam? Complete guide covering bottled water, street drink ice safety, brushing teeth, boiling, and avoiding stomach illness."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-tap-water-hero:v1 -->
<section class="vg-guide-hero vg-tap-water-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Health &amp; safety - Updated September 22, 2026</p>
<h1>Tap Water in Vietnam: Drinking, Ice &amp; Safety Rules</h1>
<p class="vg-guide-lede">Can you drink tap water in Vietnam? The unequivocal answer is no. Even in modern five-star hotels and luxury high-rises in Hanoi and Ho Chi Minh City, municipal tap water is not safe for direct consumption. While water treatment plants in major cities meet basic microbiological criteria, aging transmission pipes, low municipal pressure, and ubiquitous rooftop water storage tanks introduce heavy metals, sediment, and bacterial contamination. Here is everything you need to know about drinking water, ice safety in iced coffee, and brushing teeth in Vietnam.</p>
</div>
</section>

<div class="vg-guide-meta">
<span><strong>Direct Tap Drinking:</strong> Unsafe everywhere in Vietnam; never drink untreated tap water</span>
<span><strong>Brushing Teeth:</strong> Safe with tap water in modern hotels; use bottled water if sensitive</span>
<span><strong>Ice Safety (Đá Bi):</strong> Machine tube ice with a hole is safe; avoid crushed block ice</span>
<span><strong>Bottled Water Price:</strong> 7,000–12,000 VND ($0.30–$0.50 USD) for 500ml at convenience stores</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>Never drink tap water directly anywhere in Vietnam. Always rely on sealed bottled water, boiled water, or hotel filtration carafes.</strong> Brushing your teeth with tap water is generally safe in established hotels across Hanoi, Saigon, and Da Nang as long as you rinse thoroughly and do not swallow. <strong>Ice served in iced coffee (cà phê sữa đá) and fruit juices is overwhelmingly safe in urban cafes when it takes the form of machine-made cylindrical tube ice with a center hole (<em>đá bi</em>).</strong> Only avoid ice if you see a vendor chipping shards off a large dirty ice block resting on the sidewalk (<em>đá cây</em>).</p>
</div>

<h2 class="wp-block-heading">Water &amp; ice safety guide for travelers</h2>
<div class="wp-block-table">
<table>
<thead>
<tr>
<th>Water Situation</th>
<th>Safety Level</th>
<th>Rules &amp; Best Practices</th>
<th>Risk Factor</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Drinking Tap Water Directly</strong></td>
<td>Unsafe (High Risk)</td>
<td>Never drink directly from any faucet, bathroom, or public sink</td>
<td>Bacterial infection, E. coli, heavy metal contaminants</td>
</tr>
<tr>
<td><strong>Brushing Teeth</strong></td>
<td>Low Risk (Major Cities)<br>Moderate Risk (Rural)</td>
<td>Fine in 3-star+ hotels in Hanoi, HCMC, Da Nang; spit thoroughly. Use bottled water in remote homestays</td>
<td>Accidental swallowing of untreated tap water</td>
</tr>
<tr>
<td><strong>Factory Tube Ice (Đá Bi)</strong></td>
<td>Safe (Low Risk)</td>
<td>Cylindrical ice with a hollow center made from purified factory water; standard across Vietnam</td>
<td>Minimal; widespread in cafes and restaurants</td>
</tr>
<tr>
<td><strong>Crushed Block Ice (Đá Cây)</strong></td>
<td>Avoid (Moderate Risk)</td>
<td>Large solid ice blocks cut with saws and transported on motorbikes; avoid in drinks</td>
<td>Contamination during transport and floor handling</td>
</tr>
<tr>
<td><strong>Boiled Water (Tea &amp; Soups)</strong></td>
<td>Safe (Zero Risk)</td>
<td>Water brought to a rolling boil in kettles, phở broths, hot tea (trà đá uses boiled water)</td>
<td>Boiling neutralizes pathogens and bacteria</td>
</tr>
<tr>
<td><strong>Hotel Refill Stations</strong></td>
<td>Safe (Zero Risk)</td>
<td>Reverse osmosis (RO) filtered water carafes provided in eco-conscious boutique hotels</td>
<td>Eco-friendly and thoroughly filtered</td>
</tr>
</tbody>
</table>
</div>

<h2 class="wp-block-heading" id="why-tap-water-is-unsafe">1. Why municipal tap water is not drinkable</h2>
<p>Understanding the water infrastructure helps eliminate confusion. In Hanoi and Ho Chi Minh City, modern municipal treatment facilities purify water to international safety thresholds. The contamination occurs during transit:</p>
<ul class="wp-block-list">
<li><strong>Aging underground pipes:</strong> Decades-old cast iron and PVC distribution networks suffer from minor leaks and negative pressure fluctuations, allowing groundwater microbes to seep into the supply line.</li>
<li><strong>Rooftop water storage tanks:</strong> Because city water pressure is insufficient to reach multi-story buildings, virtually every house, apartment, and hotel pumps water up into stainless steel or concrete rooftop storage tanks. These tanks sit under the tropical sun and, unless scrupulously cleaned every quarter, can accumulate sediment, algae, and airborne bacteria.</li>
</ul>

<h2 class="wp-block-heading" id="the-ice-question">2. Can you drink ice in Vietnam? (Đá Bi vs Đá Cây)</h2>
<p>One of the most persistent travel myths is that travelers must avoid all ice in Vietnam. In reality, modern Vietnam possesses a massive commercial ice industry that manufactures hygienic ice using purified, UV-treated water.</p>
<ul class="wp-block-list">
<li><strong>How to identify safe ice (Đá Bi):</strong> Look at the ice in your glass. If it consists of uniform, clear cylindrical cubes with a hollow circular hole running through the center, it is machine-made factory ice (<em>đá bi</em>). It is produced under regulated hygienic standards and is safe in iced coffee, fruit smoothies, and draft beer.</li>
<li><strong>How to spot unsafe ice (Đá Cây):</strong> If you see irregular, jagged shards of opaque ice chipped off a large rectangular slab using an ice pick, exercise caution. These large industrial blocks (<em>đá cây</em>) are intended for chilling seafood and fish crates, and are frequently transported on motorbike racks without protective packaging.</li>
</ul>

<h2 class="wp-block-heading" id="practical-water-habits">3. Practical water rules for first-timers</h2>
<p>Following a few simple daily habits guarantees a comfortable, stomach-worry-free holiday in Vietnam:</p>
<ul class="wp-block-list">
<li><strong>Check bottle seals:</strong> When purchasing bottled water from street kiosks, ensure the plastic tamper-evident cap ring clicks open upon twisting. Reputable national brands include La Vie (mineral water), Aquafina (purified water), and Dasani. A 500ml bottle costs 7,000 to 10,000 VND ($0.30 to $0.40 USD) at convenience stores like Circle K, GS25, and WinMart.</li>
<li><strong>Use hotel kettles:</strong> If your room provides an electric kettle, boiling municipal tap water for 1 to 2 minutes makes it completely safe for drinking, making tea, or cooling down in your own reusable water flask.</li>
<li><strong>Carry oral rehydration salts (Oresol):</strong> Tropical humidity and perspiration cause rapid electrolyte loss. Pick up a few packets of Oresol at any local pharmacy (<em>nhà thuốc</em>) for 5,000 VND ($0.20 USD). Dissolving one packet in a liter of bottled water restores mineral balance quickly after long walking tours.</li>
</ul>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<div class="wp-block-details">
<summary><strong>Can I brush my teeth with tap water in Vietnam?</strong></summary>
<p>In standard and luxury hotels in major cities (Hanoi, Saigon, Da Nang, Hoi An), brushing your teeth with tap water is safe as long as you spit it out and rinse thoroughly. If you have a sensitive stomach, are pregnant, or are staying in a rural village homestay, use bottled water to rinse your toothbrush.</p>
</div>
<div class="wp-block-details">
<summary><strong>Is iced tea (Trà Đá) safe at street food stalls?</strong></summary>
<p>Yes. Trà đá (iced green tea) is made by brewing green tea leaves in boiling hot water, which sterilizes the beverage. It is then poured over factory tube ice. Millions of locals and international travelers drink trà đá daily with zero digestive issues.</p>
</div>
<div class="wp-block-details">
<summary><strong>Can I wash fruit and vegetables with tap water?</strong></summary>
<p>At local restaurants, fresh herbs (coriander, mint, Thai basil) served with pho and bun cha are soaked in salted or ozone-treated water before serving. If preparing raw fruit in your hotel room, peel fruits with thick rinds (mangoes, dragon fruit, bananas) or rinse them with bottled/filtered water.</p>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Protect your wellbeing throughout Vietnam with our health, food, and safety resources:</p>
<ul class="wp-block-list">
<li><a href="/plan/health-travel-insurance-vietnam/">Health &amp; Travel Insurance in Vietnam</a> &mdash; emergency clinics, pharmacies, and policy coverage.</li>
<li><a href="/plan/vietnam-food-safety-street-food-etiquette/">Vietnam Food Safety &amp; Street Food Etiquette</a> &mdash; how to pick busy stalls and avoid stomach bugs.</li>
<li><a href="/plan/what-to-pack-for-vietnam-region-season/">What to Pack for Vietnam</a> &mdash; medical kit essentials, electrolytes, and tropical toiletries.</li>
<li><a href="/plan/safety-scams-vietnam/">Safety &amp; Scams in Vietnam</a> &mdash; street-level situational awareness and common tourist traps.</li>
</ul>
"""

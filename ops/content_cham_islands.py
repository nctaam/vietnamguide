# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 64: Cham Islands Day Trip Guide Content Definition
Parent: plan (ID 6)
Slug: cham-islands-day-trip-guide
"""

TITLE = "Cham Islands Day Trip Guide: Speedboats & Snorkeling (2026)"
SLUG = "cham-islands-day-trip-guide"
PARENT_ID = 6

FOCUS_KEYWORD = "Cham Islands day trip"
META_DESC = "Complete Cham Islands day trip guide: speedboats from Cua Dai pier, snorkeling at Bai Ong, seafood lunch, UNESCO biosphere rules, tickets, and season."

# Exact SERP character bounds validation
assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """<!-- vg-cham-islands-hero:v1 -->
<section class="vg-guide-hero vg-cham-islands-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Planning guide - Updated September 22, 2026</p>
<h1>Cham Islands Day Trip: Speedboats, Reefs &amp; UNESCO Biosphere</h1>
<p class="vg-guide-lede">Rising from the East Sea just 15 kilometers off the coast of Hoi An, the Cham Islands (Cù Lao Chàm) form a granite archipelago of eight islands recognized as a UNESCO World Biosphere Reserve. Known for pristine coral reefs, traditional fishing hamlets, and sheltered palm-fringed coves, taking a Cham Islands day trip is Central Vietnam's premier marine excursion. Here is our complete operational guide to speedboat logistics, snorkeling sites, environmental rules, and seasonal timing.</p>
</div>
</section>

<div class="vg-guide-meta">
<span><strong>Departure Pier:</strong> Cua Dai Harbor (Cảng Cửa Đại), Hoi An</span>
<span><strong>Speedboat Transit Time:</strong> 20 to 25 minutes across open sea</span>
<span><strong>All-Inclusive Day Tour:</strong> 650,000–850,000 VND ($26–$34 USD)</span>
<span><strong>Operating Season:</strong> Strictly March to September (Closed in Winter)</span>
</div>

<h2 class="wp-block-heading">Concierge verdict</h2>
<div class="vg-concierge-verdict">
<p><strong>Between April and August, a Cham Islands day trip is one of the most rewarding island excursions in Central Vietnam.</strong> Book an organized speedboat day tour (650,000 to 800,000 VND) departing from Cua Dai Pier in Hoi An; this covers your port taxes, the 70,000 VND marine biosphere fee, reef snorkeling gear, a multi-course seafood lunch at Bai Ong, and hotel transfers. <strong>Crucial environmental requirement:</strong> The Cham Islands enforce an absolute ban on single-use plastic bags (*Nói không với túi nilon*). Port authorities inspect bags at Cua Dai pier and will confiscate plastic grocery bags. <strong>Never attempt this trip between October and February:</strong> the northeast winter monsoon churns heavy ocean swells, and maritime authorities suspend all tourist speedboats for safety.</p>
</div>

<h2 class="wp-block-heading">Cham Islands day trip options comparison</h2>
<div class="wp-block-table">
<table>
<thead>
<tr>
<th>Travel Option</th>
<th>Departure / Transit Time</th>
<th>What is Included</th>
<th>Typical Cost (VND)</th>
<th>Best For</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Organized Speedboat Day Tour</strong></td>
<td>08:00 departure; 20-min speedboat</td>
<td>Roundtrip boat, biosphere fees, guide, snorkel gear, seafood lunch</td>
<td>650,000–850,000 VND</td>
<td>First-time visitors, families, hassle-free logistics</td>
</tr>
<tr>
<td><strong>Independent Speedboat Ticket</strong></td>
<td>08:30 from Cua Dai pier; 20-min transit</td>
<td>Roundtrip speedboat transfer only (fixed return at 14:00 or 15:00)</td>
<td>450,000–500,000 VND</td>
<td>Independent travelers exploring villages or staying overnight</td>
</tr>
<tr>
<td><strong>Wooden Public Ferry (*Tàu Chợ*)</strong></td>
<td>08:00 from Bach Dang / Cua Dai; 90 mins</td>
<td>Slow wooden cargo boat with island locals, vegetables, and freight</td>
<td>150,000 VND each way</td>
<td>Budget backpackers not prone to sea sickness</td>
</tr>
<tr>
<td><strong>Scuba Diving Day Excursion</strong></td>
<td>07:45 specialized dive boat; full day</td>
<td>2 guided ocean boat dives, full PADI gear, lunch, marine park taxes</td>
<td>1,800,000–2,400,000 VND</td>
<td>Certified divers (Cham Island Diving Center / Blue Coral)</td>
</tr>
</tbody>
</table>
</div>

<h2 class="wp-block-heading" id="pier-transit">1. Departure logistics: Cua Dai Pier and speedboat crossing</h2>
<p>All speedboats to Cu Lao Cham depart from Cua Dai Harbor (Cang Cua Dai), situated 5 kilometers east of Hoi An Ancient Town at the mouth of the Thu Bon River.</p>
<ul class="wp-block-list">
<li><strong>The morning departure:</strong> Speedboats depart between 08:15 and 09:00. The modern 20- to 36-passenger fiberglass speedboats are powered by twin or triple 200HP outboard motors, skimming across the open sea to the main island of Hon Lao in 20 to 25 minutes.</li>
<li><strong>Weather &amp; sea conditions:</strong> During the calm summer window (May to July), the crossing is smooth. However, morning choppy waters can cause moderate jarring as the hull cuts through offshore waves. If you are prone to motion sickness, take dimenhydrinate 30 minutes before boarding and request a seat in the stern (rear) of the boat where pitching is minimized.</li>
</ul>

<h2 class="wp-block-heading" id="village-heritage">2. Cultural landmarks: Bai Lang and ancient Cham heritage</h2>
<p>Speedboats dock at the concrete pier of Bai Lang, the primary residential settlement on Hon Lao where over 2,500 fishermen and their families reside.</p>
<ul class="wp-block-list">
<li><strong>Hai Tang Pagoda:</strong> Founded in 1758 and relocated to the base of the Western hills in 1848, this peaceful Mahayana Buddhist sanctuary features carved ironwood pillars, bronze bells, and an open courtyard overlooking green rice paddies.</li>
<li><strong>The Ancient Cham Well (Giếng Cổ Chăm):</strong> Dug by the Champa people more than 200 years ago, this square brick well provides sweet, abundant freshwater year-round, never drying up even during severe coastal droughts. Local folklore claims that drinking water from the well can cure sea-sickness.</li>
<li><strong>Tan Hiep Seafood Market:</strong> Located adjacent to the pier, vendors display fresh morning catches: sea snails, swimming crabs, sea cucumbers, and wild-caught squid dried on bamboo racks under the tropical sun (*mực một nắng*).</li>
</ul>

<h2 class="wp-block-heading" id="snorkeling-reefs">3. Snorkeling and diving in the UNESCO Marine Park</h2>
<p>Around 10:30, speedboats ferry passengers out to sheltered bays around Hon Dai, Hon Tai, or Bai Xep for 45 to 60 minutes of snorkeling over protected coral gardens.</p>
<ul class="wp-block-list">
<li><strong>Marine biodiversity:</strong> The biosphere reserve encompasses more than 135 species of hard and soft corals, 202 fish species, and four species of sea anemones. You will spot colorful damselfish, parrotfish, clownfish, and spiny sea urchins living among boulder and brain corals.</li>
<li><strong>Reef etiquette:</strong> The marine park enforces strict conservation rules. Do not touch or step on living coral heads with your fins. Avoid collecting shells or sea stars from the water. Always choose mineral, reef-safe sunscreen (zinc oxide or titanium dioxide) to prevent coral bleaching.</li>
</ul>

<h2 class="wp-block-heading" id="beaches-lunch">4. Beach relaxation and seafood feast at Bai Ong</h2>
<p>Around midday, boats transport guests to Bai Ong or Bai Chong, the archipelago's premier leisure beaches.</p>
<ul class="wp-block-list">
<li><strong>The multi-course seafood lunch:</strong> Standard tour packages seat guests in shaded open-air thatched pavilions overlooking the beach. The generous family-style meal includes grilled half-shell scallops with crushed peanuts, steamed island clams with lemongrass, sweet-and-sour stir-fried squid, fried whole fish, boiled wild island greens (<em>rau rừng</em>) dipped in spicy fermented bean paste, and steamed jasmine rice.</li>
<li><strong>Afternoon leisure:</strong> After lunch, relax on sun loungers under swaying coconut palms, rent a sea kayak, or swim in the calm, protected sandy bay before speedboats reassemble at 14:00 to 14:30 for the return crossing to Cua Dai.</li>
</ul>

<h2 class="wp-block-heading">Strict zero-plastic policy: What to know</h2>
<p>The Cham Islands are celebrated across Southeast Asia as a pioneering zero-waste model. Since 2009, single-use plastic bags have been strictly illegal on the island.</p>
<ul class="wp-block-list">
<li><strong>Inspection at Cua Dai Pier:</strong> Port security officers inspect bags before boarding. Any nylon or thin plastic grocery bags will be confiscated. Carry your wet swimwear, sunscreen, and towels inside a reusable cloth tote or waterproof dry bag.</li>
<li><strong>Drinking water:</strong> Bring a reusable metal or hard plastic water bottle. Tour operators and island restaurants provide filtered water dispensers to refill bottles free of charge.</li>
</ul>

<h2 class="wp-block-heading">Frequently asked questions</h2>
<div class="wp-block-details">
<summary><strong>When is the Cham Islands season open?</strong></summary>
<p>The visiting season runs strictly from March through September. The ideal weather window occurs between May and August, when waters are calm and clear. From October through February, winter monsoon storms generate heavy waves, shutting down all tourist boat sailings.</p>
</div>
<div class="wp-block-details">
<summary><strong>Can I stay overnight on the Cham Islands?</strong></summary>
<p>Yes. Several welcoming local family homestays operate in Bai Lang and Bai Huong (costing 250,000 to 450,000 VND / $10 to $18 USD per night). Staying overnight allows you to experience the island after the last day-tour speedboats depart at 14:30, enjoying peaceful sunset fishing harbors.</p>
</div>
<div class="wp-block-details">
<summary><strong>Is snorkeling suitable for non-swimmers?</strong></summary>
<p>Yes. Tour operators provide fitted life jackets and buoyant snorkeling masks. Even if you cannot swim, wearing the secured life jacket keeps you completely afloat while you float face-down to observe the shallow coral gardens.</p>
</div>

<h2 class="wp-block-heading">Where to go next</h2>
<p>Enhance your Central Vietnam coastal adventures with our expert guides:</p>
<ul class="wp-block-list">
<li><a href="/destinations/hoi-an-ancient-town-guide/">Hoi An Ancient Town Guide</a> &mdash; walking tickets, heritage landmarks, lantern nights, and tailor shops.</li>
<li><a href="/destinations/da-nang-beaches-guide/">Da Nang Beaches Guide</a> &mdash; compare My Khe Beach, Non Nuoc surf, and Son Tra snorkeling coves.</li>
<li><a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a> &mdash; basket boat tours, Tra Que herb farms, and night river lanterns.</li>
<li><a href="/destinations/where-to-stay-in-hoi-an/">Where to Stay in Hoi An</a> &mdash; boutique Old Town stays, An Bang beachfront resorts, and rice paddy villas.</li>
</ul>
"""

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 75: Vietnam Craft Beer Guide
Parent: Plan (ID 6)
Slug: vietnam-craft-beer-guide
"""

TITLE = "Vietnam Craft Beer Guide (2026): Breweries, Taps & Prices"
SLUG = "vietnam-craft-beer-guide"
PARENT_ID = 6

FOCUS_KEYWORD = "Vietnam craft beer"
META_DESC = "Vietnam craft beer guide: top artisanal breweries in Saigon, Hanoi, and Da Nang, signature tropical IPAs, taproom locations, and 2026 pint prices."

assert len(TITLE) <= 60, f"Title too long: {len(TITLE)}"
assert 130 <= len(META_DESC) <= 155, f"Meta description out of bounds: {len(META_DESC)}"
assert FOCUS_KEYWORD.lower() in TITLE.lower(), "Focus keyword missing from title"
assert FOCUS_KEYWORD.lower() in META_DESC.lower(), "Focus keyword missing from description"

CONTENT = """
<div class="vg-guide-hero">
    <h1>Vietnam Craft Beer Guide (2026): Breweries, Taps &amp; Prices</h1>
    <p class="vg-guide-meta">Updated September 23, 2026</p>
</div>

<p>Exploring the booming world of <strong>Vietnam craft beer</strong> reveals one of the most dynamic artisan beverage scenes in all of Asia. Over the past decade, innovative local and international brewmasters transformed a market historically defined by light commercial lagers and roadside bia hoi into a creative playground. Artisanal breweries throughout Ho Chi Minh City, Hanoi, and Da Nang now craft world-class ales infused with native ingredients like Dalat passionfruit, Phu Quoc black peppercorns, Mekong cacao nibs, and fragrant jasmine blossoms.</p>

<div class="vg-concierge-verdict">
    <h3>The Concierge Verdict</h3>
    <p>Do not leave Vietnam without ordering a tasting flight of tropical IPAs at Pasteur Street Brewing's historic original alleyway taproom in District 1, Ho Chi Minh City. In Hanoi, spend an evening on the second-story open-air terrace of The Standing Bar on Truc Bach Lake, sampling 20 guest taps from microbreweries across the country. In Da Nang, head to 7 Bridges Brewing on the Han River bank for riverside sunset views and zero-waste botanical beers.</p>
</div>

<h2 class="wp-block-heading">Top Independent Craft Breweries in Vietnam</h2>

<figure class="wp-block-table">
    <table>
        <thead>
            <tr>
                <th>Brewery</th>
                <th>Primary Taprooms</th>
                <th>Signature Brew</th>
                <th>Pint Price (VND / USD)</th>
                <th>Notable Craft Distinction</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Pasteur Street Brewing</strong></td>
                <td>Saigon, Hanoi, Hoi An</td>
                <td>Jasmine IPA (6.5% ABV)</td>
                <td>95,000 - 130,000 VND ($3.80 - $5.20 USD)</td>
                <td>Pioneered Vietnam craft using native botanicals</td>
            </tr>
            <tr>
                <td><strong>Heart of Darkness</strong></td>
                <td>Saigon (D1), Da Nang</td>
                <td>Kurtz's Insane IPA (7.1% ABV)</td>
                <td>110,000 - 150,000 VND ($4.40 - $6.00 USD)</td>
                <td>High-hop American styles, 100+ rotating recipes</td>
            </tr>
            <tr>
                <td><strong>7 Bridges Brewing</strong></td>
                <td>Da Nang, Saigon, Hanoi</td>
                <td>Sunset Tangerine Wheat</td>
                <td>90,000 - 135,000 VND ($3.60 - $5.40 USD)</td>
                <td>Zero-waste environmental ethos, fruit sours</td>
            </tr>
            <tr>
                <td><strong>East West Brewing Co.</strong></td>
                <td>Saigon (D1), Da Nang Beach</td>
                <td>Far East IPA (6.7% ABV)</td>
                <td>85,000 - 125,000 VND ($3.40 - $5.00 USD)</td>
                <td>Large European-style showcase brewing halls</td>
            </tr>
            <tr>
                <td><strong>The Standing Bar (Guest Taps)</strong></td>
                <td>Hanoi (Truc Bach Lake)</td>
                <td>20 Rotating Vietnamese Taps</td>
                <td>90,000 - 145,000 VND ($3.60 - $5.80 USD)</td>
                <td>Curated independent multi-brewery taphouse</td>
            </tr>
        </tbody>
    </table>
</figure>

<h2 class="wp-block-heading">1. Ho Chi Minh City: The Brewing Epicenter</h2>
<p>Saigon serves as the vibrant capital of Vietnamese microbrewing. Pasteur Street Brewing started the craft movement in 2014 from a hidden second-story room down an alleyway at 144 Pasteur Street in District 1. Today, they operate multiple taprooms serving crisp Passionfruit Wheat beers and their world-beer-cup winning Jasmine IPA, infused with dried jasmine blossoms from northern Vietnam.</p>
<p>A few blocks away along Ly Tu Trong Street, Heart of Darkness operates a multi-level industrial bar featuring 20 taps poured directly from temperature-controlled keg rooms. Further east across the Saigon River in Thao Dien (District 2), Belgo Belgian Craft Beer Brewery brews abbey-style ales and dubbels following centuries-old Belgian methods using authentic imported European malt.</p>

<h2 class="wp-block-heading">2. Hanoi: Heritage Taprooms &amp; Lakeside Pours</h2>
<p>In the national capital, the craft culture adopts a relaxed, neighborhood vibe. Overlooking Truc Bach Lake, The Standing Bar is an essential stop for beer lovers. With 20 constantly rotating taps, guests can sample beers from northern nanobreweries like Furbrew, C-Brewmaster, and Turtle Lake alongside southern guest kegs while sitting on wooden sidewalk benches.</p>
<p>In the Old Quarter, Pasteur Street's Au Trieu taproom sits adjacent to the neo-Gothic facade of St. Joseph's Cathedral, allowing visitors to enjoy cold hoppy pints in a restored heritage courtyard.</p>

<h2 class="wp-block-heading">3. Da Nang &amp; Hoi An: Coastal Taprooms</h2>
<p>Central Vietnam pairs craft brewing with coastal views. 7 Bridges Brewing built its multi-level taproom directly on the Han River bank in Da Nang. On Saturday and Sunday evenings, patrons sip Imperial IPAs and Dragon Fruit Sours while watching the illuminated Dragon Bridge breathe real fire over the water.</p>
<p>Along My Khe beach, East West Brewing's expansive oceanfront brewpub pairs fresh seafood with cold Pacific Pilsners. In nearby Hoi An, Pasteur Street's ancient town taproom offers an air-conditioned oasis shaded by yellow plaster walls and silk lanterns.</p>

<h2 class="wp-block-heading">Pint Prices, Flights &amp; Drinking Etiquette</h2>
<p>Draft craft beers in Vietnam cost significantly less than in Singapore, Tokyo, or Western capitals. A standard 330ml glass ranges from 85,000 to 110,000 VND ($3.40 &ndash; $4.40 USD), while 500ml pints or high-ABV double IPAs range from 120,000 to 150,000 VND ($4.80 &ndash; $6.00 USD). Tasting flights (usually four 150ml glasses) average 160,000 to 220,000 VND ($6.40 &ndash; $8.80 USD), providing an ideal format for discovering new flavors.</p>

<h2 class="wp-block-heading">Frequently Asked Questions</h2>

<details class="wp-block-details">
    <summary>How does craft beer compare in price to local lagers?</summary>
    <p>Standard canned lagers (Tiger, Bia Saigon) cost 20,000 to 35,000 VND ($0.80 &ndash; $1.40 USD) in restaurants, while artisan craft pints cost 85,000 to 140,000 VND ($3.40 &ndash; $5.60 USD) due to high-grade imported hops and malt.</p>
</details>

<details class="wp-block-details">
    <summary>Can I buy canned craft beer to take home?</summary>
    <p>Yes, all major taprooms sell 330ml and 500ml cans to go, and gourmet supermarkets like Annam Gourmet stock canned craft beers chilled.</p>
</details>

<details class="wp-block-details">
    <summary>Are brewery tours available for visitors?</summary>
    <p>East West Brewing in Saigon offers visible tours of its on-site brew kettles, while Pasteur Street occasionally runs guided tasting sessions at their larger production facility.</p>
</details>

<div class="vg-kicker">
    <h3>Where to go next</h3>
    <ul class="wp-block-list">
        <li><a href="/destinations/saigon-street-food-guide/">Saigon Street Food Guide</a>: Best street food dishes, alleys, and evening food markets.</li>
        <li><a href="/destinations/hanoi-street-food-guide/">Hanoi Street Food Guide</a>: Classic northern noodle soups, bun cha, and bia hoi corners.</li>
        <li><a href="/plan/vietnam-travel-cost/">Vietnam Travel Cost &amp; Budget</a>: Daily expense breakdown for dining, drinking, and transit.</li>
        <li><a href="/destinations/where-to-stay-in-da-nang/">Where to Stay in Da Nang</a>: Beachfront resorts along My Khe vs riverside city hotels.</li>
    </ul>
</div>
"""

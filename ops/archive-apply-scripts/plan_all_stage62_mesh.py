# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 62: Internal Linking Mesh Generator & Validator
Matches exact substrings in 24 source posts and prepares 28 verified link insertions.
"""

import json
import sys

sys.stdout.reconfigure(encoding='utf-8')

sources = json.load(open('ops/stage62_source_contents.json', encoding='utf-8'))

def find_context(pid, phrase):
    p = sources.get(str(pid))
    if not p:
        return None
    content = p['content']
    pos = content.find(phrase)
    if pos == -1:
        return None
    start = max(0, pos - 40)
    end = min(len(content), pos + len(phrase) + 40)
    return content[start:end]

# Define candidate link replacement operations
# Each op has: post_id, slug, target, replacement
OPS = [
    # ----------------------------------------------------
    # Group 1: saigon-airport-to-district-1
    # ----------------------------------------------------
    {
        "post_id": 262,
        "slug": "ho-chi-minh-city-travel-guide",
        "target": '<td data-label="Movement">Airport transfer</td><td data-label="Use it when...">Every HCMC route starts or ends through Tan Son Nhat.</td>',
        "replacement": '<td data-label="Movement">Airport transfer</td><td data-label="Use it when...">Every HCMC route starts or ends through Tan Son Nhat (see our guide to <a href="/plan/saigon-airport-to-district-1/">Saigon airport to District 1</a> for Bus 109, Grab, and taxi options).</td>'
    },
    {
        "post_id": 281,
        "slug": "where-to-stay-in-ho-chi-minh-city",
        "target": "<p>Airport-side is not automatically safer. It is useful when the flight problem is real. Otherwise, central HCMC gives a better last evening, simpler food access, and more value.</p>",
        "replacement": '<p>Airport-side is not automatically safer. It is useful when the flight problem is real. Otherwise, central HCMC gives a better last evening, simpler food access, and more value (review transfer logistics in our <a href="/plan/saigon-airport-to-district-1/">Saigon airport to District 1</a> guide).</p>'
    },
    {
        "post_id": 476,
        "slug": "vietnam-airport-arrival-checklist",
        "target": '<p>At Tan Son Nhat (SGN), the airport is close to the city, but traffic on Truong Son can turn a short transfer into a slow crawl. If arriving between 16:30 and 19:00, add buffer before any evening tour or dinner booking.</p>',
        "replacement": '<p>At Tan Son Nhat (SGN), the airport is close to the city, but traffic on Truong Son can turn a short transfer into a slow crawl (compare bus, Grab, and taxi options in our <a href="/plan/saigon-airport-to-district-1/">Saigon airport to District 1</a> guide). If arriving between 16:30 and 19:00, add buffer before any evening tour or dinner booking.</p>'
    },
    {
        "post_id": 615,
        "slug": "saigon-street-food-guide",
        "target": '<p>For complete trip planning, consult our <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, check neighborhood safety and hotel bases in our <a href="/destinations/where-to-stay-in-ho-chi-minh-city/">Where to Stay in Ho Chi Minh City</a> guide, explore local flavors in our <a href="/destinations/saigon-street-food-guide/">Saigon Street Food Guide</a>, and prepare your documents with the <a href="/plan/vietnam-airport-arrival-checklist/">Vietnam Airport Arrival Checklist</a>.</p>',
        "replacement": '<p>For complete trip planning, consult our <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, check arrival options in our <a href="/plan/saigon-airport-to-district-1/">Saigon airport to District 1</a> guide, explore morning cafes in the <a href="/destinations/vietnam-coffee-guide/">Vietnam Coffee Guide</a>, review neighborhood bases in <a href="/destinations/where-to-stay-in-ho-chi-minh-city/">Where to Stay in Ho Chi Minh City</a>, and prepare with the <a href="/plan/vietnam-airport-arrival-checklist/">Vietnam Airport Arrival Checklist</a>.</p>'
    },

    # ----------------------------------------------------
    # Group 2: da-nang-airport-to-hoi-an
    # ----------------------------------------------------
    {
        "post_id": 213,
        "slug": "da-nang-travel-guide",
        "target": '<td data-label="Role in route">Hoi An launch pad</td><td data-label="Why it fits">Direct flight arrival followed by a short road transfer south.</td>',
        "replacement": '<td data-label="Role in route">Hoi An launch pad</td><td data-label="Why it fits">Direct flight arrival followed by a short road transfer south (review transfer costs in our <a href="/plan/da-nang-airport-to-hoi-an/">Da Nang airport to Hoi An</a> guide).</td>'
    },
    {
        "post_id": 499,
        "slug": "hoi-an-ancient-town-guide",
        "target": '<p>Hoi An has no commercial airport or train station; travelers arrive via Da Nang (DAD airport, 30 km north) by taxi, private car, or shuttle bus.</p>',
        "replacement": '<p>Hoi An has no commercial airport or train station; travelers arrive via Da Nang (DAD airport, 30 km north) by taxi, private car, or shuttle bus (see our full <a href="/plan/da-nang-airport-to-hoi-an/">Da Nang airport to Hoi An</a> transfer breakdown).</p>'
    },
    {
        "post_id": 209,
        "slug": "da-nang-vs-hoi-an",
        "target": '<p>Travel time between Da Nang and Hoi An is only 45 to 60 minutes by taxi or private car (around 250,000 to 350,000 VND), making it easy to split a stay or day-trip between them.</p>',
        "replacement": '<p>Travel time between Da Nang and Hoi An is only 45 to 60 minutes by taxi or private car (around 250,000 to 350,000 VND; see our complete <a href="/plan/da-nang-airport-to-hoi-an/">Da Nang airport to Hoi An</a> transport guide), making it easy to split a stay or day-trip between them.</p>'
    },
    {
        "post_id": 155,
        "slug": "transport-within-vietnam",
        "target": '<p>For regional airport transfers (such as Hanoi Noi Bai to the Old Quarter or Da Nang to Hoi An), private hotel cars or pre-arranged transfers often cost only slightly more than app-based rides and eliminate arrival confusion.</p>',
        "replacement": '<p>For regional airport transfers, private hotel cars or pre-arranged transfers often cost only slightly more than app-based rides (compare options in our <a href="/plan/hanoi-airport-to-old-quarter/">Hanoi airport to Old Quarter</a> and <a href="/plan/da-nang-airport-to-hoi-an/">Da Nang airport to Hoi An</a> guides) and eliminate arrival confusion.</p>'
    },

    # ----------------------------------------------------
    # Group 3: grab-in-vietnam-guide
    # ----------------------------------------------------
    {
        "post_id": 155,
        "slug": "transport-within-vietnam",
        "target": '<p>Ride-hailing apps (principally Grab, alongside local alternatives like Be and Xanh SM) operate in all major cities. They provide upfront fixed pricing, GPS tracking, and cashless card payments, drastically reducing meter-scam risks.</p>',
        "replacement": '<p>Ride-hailing apps operate in all major cities (read our complete <a href="/plan/grab-in-vietnam-guide/">Grab in Vietnam guide</a> for app setup, card linking, and airport pickup zones). They provide upfront fixed pricing, GPS tracking, and cashless card payments, drastically reducing meter-scam risks.</p>'
    },
    {
        "post_id": 15,
        "slug": "sim-esim-vietnam",
        "target": '<p>Having an active data connection on arrival allows you to book ride-hailing cars immediately, translate menus, check real-time map routes, and contact hotels.</p>',
        "replacement": '<p>Having an active data connection on arrival allows you to book ride-hailing cars immediately (see our step-by-step <a href="/plan/grab-in-vietnam-guide/">Grab in Vietnam guide</a> for airport pickups), translate menus, check real-time map routes, and contact hotels.</p>'
    },
    {
        "post_id": 158,
        "slug": "money-cash-cards-atms",
        "target": '<p>Linking an international credit card to ride-hailing apps like Grab allows frictionless cashless travel for daily city movement, eliminating the need to handle small cash or wait for drivers to find change.</p>',
        "replacement": '<p>Linking an international credit card to ride-hailing apps allows frictionless cashless travel for daily city movement (explore our <a href="/plan/grab-in-vietnam-guide/">Grab in Vietnam guide</a> for foreign card setup and OTP tips), eliminating the need to handle small cash or wait for drivers to find change.</p>'
    },
    {
        "post_id": 181,
        "slug": "safety-scams-vietnam",
        "target": '<p>To avoid taxi overcharging and rigged taximeters, use ride-hailing apps like Grab or stick exclusively to reliable metered taxi brands: Vinasun (white) and Mai Linh (green).</p>',
        "replacement": '<p>To avoid taxi overcharging and rigged taximeters, use ride-hailing apps (follow our <a href="/plan/grab-in-vietnam-guide/">Grab in Vietnam guide</a> for airport and city pickups) or stick exclusively to reliable metered taxi brands: Vinasun (white) and Mai Linh (green).</p>'
    },

    # ----------------------------------------------------
    # Group 4: tipping-in-vietnam
    # ----------------------------------------------------
    {
        "post_id": 22,
        "slug": "vietnam-travel-cost",
        "target": '<p>Tipping is not customary in everyday Vietnamese life. However, in tourism sectors—such as private tour guides, long-distance drivers, and upscale spas—tips are warmly received and increasingly expected.</p>',
        "replacement": '<p>Tipping is not customary in everyday Vietnamese life, though standard in tourism services (read our complete breakdown on <a href="/plan/tipping-in-vietnam/">Tipping in Vietnam</a> for customary amounts across guides, spas, and restaurants).</p>'
    },
    {
        "post_id": 158,
        "slug": "money-cash-cards-atms",
        "target": '<p>Keep small denominations (10,000, 20,000, and 50,000 VND) in an accessible pocket for small purchases, street snacks, parking fees, and occasional service gratuities.</p>',
        "replacement": '<p>Keep small denominations (10,000, 20,000, and 50,000 VND) in an accessible pocket for small purchases, street snacks, parking fees, and occasional service gratuities (review our cultural guidelines in <a href="/plan/tipping-in-vietnam/">Tipping in Vietnam</a>).</p>'
    },
    {
        "post_id": 480,
        "slug": "vietnam-food-safety-street-food-etiquette",
        "target": '<p>Tipping at street food stalls or casual family-run restaurants (quán ăn) is not part of Vietnamese dining culture. Vendors may chase after you if you leave cash behind on the table.</p>',
        "replacement": '<p>Tipping at street food stalls or casual family-run restaurants (quán ăn) is not part of Vietnamese dining culture (see our full guide to <a href="/plan/tipping-in-vietnam/">Tipping in Vietnam</a> for restaurant service charge details). Vendors may chase after you if you leave cash behind on the table.</p>'
    },
    {
        "post_id": 473,
        "slug": "vietnam-first-trip-planning-checklist",
        "target": '<p>Budget cash for daily incidentals: street meals, coffee, temple donations, grab rides, and tips for tour guides or massage therapists.</p>',
        "replacement": '<p>Budget cash for daily incidentals: street meals, coffee, temple donations, grab rides, and tips for tour guides or massage therapists (consult our <a href="/plan/tipping-in-vietnam/">Tipping in Vietnam</a> guide for realistic daily amounts).</p>'
    },

    # ----------------------------------------------------
    # Group 5: vietnam-coffee-guide
    # ----------------------------------------------------
    {
        "post_id": 614,
        "slug": "hanoi-street-food-guide",
        "target": '<p><strong>Cà phê trứng (egg coffee) is Hanoi\'s crowning beverage invention</strong>, created in 1946 by Nguyen Van Giang during wartime condensed milk shortages.</p>',
        "replacement": '<p><strong>Cà phê trứng (egg coffee) is Hanoi\'s crowning beverage invention</strong>, created in 1946 by Nguyen Van Giang during wartime condensed milk shortages (explore the full story and nationwide coffee styles in our <a href="/destinations/vietnam-coffee-guide/">Vietnam Coffee Guide</a>).</p>'
    },
    {
        "post_id": 615,
        "slug": "saigon-street-food-guide",
        "target": '<p>Morning in Saigon begins with a tall glass of cà phê sữa đá (iced coffee with sweetened condensed milk) sipped from low plastic stools along the pavement.</p>',
        "replacement": '<p>Morning in Saigon begins with a tall glass of cà phê sữa đá (iced coffee with sweetened condensed milk; explore all regional styles in our <a href="/destinations/vietnam-coffee-guide/">Vietnam Coffee Guide</a>) sipped from low plastic stools along the pavement.</p>'
    },
    {
        "post_id": 611,
        "slug": "da-lat-travel-guide",
        "target": '<p>The high-altitude Cau Dat plateau (1,500m) cultivates Vietnam\'s finest Arabica beans, offering specialty cuppings and estate tours far removed from the low-altitude robusta plains of Buon Ma Thuot.</p>',
        "replacement": '<p>The high-altitude Cau Dat plateau (1,500m) cultivates Vietnam\'s finest Arabica beans (learn more about Vietnam\'s specialty coffee terroirs in our <a href="/destinations/vietnam-coffee-guide/">Vietnam Coffee Guide</a>), offering specialty cuppings and estate tours far removed from the low-altitude robusta plains of Buon Ma Thuot.</p>'
    },
    {
        "post_id": 287,
        "slug": "hanoi-travel-guide",
        "target": '<p>Coffee in Hanoi is an unhurried morning institution. Beyond classic cà phê nâu (iced milk coffee), the capital is renowned for cà phê trứng (egg coffee), where whipped yolk foam crowns dark robusta.</p>',
        "replacement": '<p>Coffee in Hanoi is an unhurried morning institution (explore our comprehensive <a href="/destinations/vietnam-coffee-guide/">Vietnam Coffee Guide</a> for egg, salt, and coconut variations). Beyond classic cà phê nâu (iced milk coffee), the capital is renowned for cà phê trứng (egg coffee), where whipped yolk foam crowns dark robusta.</p>'
    },

    # ----------------------------------------------------
    # Group 6: vietnam-in-march
    # ----------------------------------------------------
    {
        "post_id": 14,
        "slug": "best-time-to-visit-vietnam",
        "target": '<p>March is one of the very few months that works beautifully across all three regions of Vietnam simultaneously: dry pleasant spring in the north, sunny beach days in the central coast, and warm dry weather in the south.</p>',
        "replacement": '<p>March is one of the very few months that works beautifully across all three regions of Vietnam simultaneously (read our complete <a href="/plan/vietnam-in-march/">Vietnam in March</a> planning guide for temperature breakdowns and route tips): dry pleasant spring in the north, sunny beach days in the central coast, and warm dry weather in the south.</p>'
    },
    {
        "post_id": 495,
        "slug": "vietnam-in-february",
        "target": '<p>As February transitions into March, northern drizzle fades, central coast beaches enter their prime sunny season, and nationwide route travel becomes exceptionally smooth.</p>',
        "replacement": '<p>As February transitions into March, northern drizzle fades, central coast beaches enter their prime sunny season (see our dedicated guide to <a href="/plan/vietnam-in-march/">Vietnam in March</a>), and nationwide route travel becomes exceptionally smooth.</p>'
    },
    {
        "post_id": 19,
        "slug": "10-days-in-vietnam",
        "target": '<p>The best months for a fast 10-day sprint across North and Central Vietnam are February through April, when rainfall is low and outdoor sightseeing is comfortable.</p>',
        "replacement": '<p>The best months for a fast 10-day sprint across North and Central Vietnam are February through April (especially <a href="/plan/vietnam-in-march/">Vietnam in March</a>, the golden nationwide window), when rainfall is low and outdoor sightseeing is comfortable.</p>'
    },
    {
        "post_id": 110,
        "slug": "best-places-to-visit-vietnam",
        "target": '<p>Spring (February to April) and autumn (September to November) offer the most balanced weather conditions for traveling through multiple regions of Vietnam on a single journey.</p>',
        "replacement": '<p>Spring (February to April, particularly <a href="/plan/vietnam-in-march/">Vietnam in March</a>) and autumn (September to November, especially <a href="/plan/vietnam-in-november/">Vietnam in November</a>) offer the most balanced weather conditions for traveling through multiple regions of Vietnam on a single journey.</p>'
    },

    # ----------------------------------------------------
    # Group 7: vietnam-in-november
    # ----------------------------------------------------
    {
        "post_id": 14,
        "slug": "best-time-to-visit-vietnam",
        "target": '<p>November brings Hanoi\'s most beloved autumn weather—cool breezes, golden sunshine, and low humidity—while southern beach destinations like Phu Quoc begin their dry season.</p>',
        "replacement": '<p>November brings Hanoi\'s most beloved autumn weather while southern beach destinations like Phu Quoc begin their dry season (review weather trade-offs in our dedicated <a href="/plan/vietnam-in-november/">Vietnam in November</a> guide).</p>'
    },
    {
        "post_id": 493,
        "slug": "vietnam-in-december",
        "target": '<p>December follows November as the dry season establishes itself across southern Vietnam, while the north turns cool and crisp.</p>',
        "replacement": '<p>December follows November (explore autumn highlights in our <a href="/plan/vietnam-in-november/">Vietnam in November</a> guide) as the dry season establishes itself across southern Vietnam, while the north turns cool and crisp.</p>'
    },
    {
        "post_id": 482,
        "slug": "vietnam-rainy-season-flexible-route",
        "target": '<p>In central Vietnam (Hue, Da Nang, Hoi An), the rainiest window falls late in the year, between October and November, when northeast monsoons bring heavy coastal downpours.</p>',
        "replacement": '<p>In central Vietnam (Hue, Da Nang, Hoi An), the rainiest window falls late in the year (read our route advice in <a href="/plan/vietnam-in-november/">Vietnam in November</a>), when northeast monsoons bring heavy coastal downpours.</p>'
    },
    {
        "post_id": 20,
        "slug": "14-days-in-vietnam",
        "target": '<p>Autumn (September to November) is a spectacular time to visit Northern Vietnam, though travelers should monitor central coast rain forecasts when traveling between Hue and Hoi An.</p>',
        "replacement": '<p>Autumn is a spectacular time to visit Northern Vietnam (consult our dedicated <a href="/plan/vietnam-in-november/">Vietnam in November</a> route strategy), though travelers should monitor central coast rain forecasts when traveling between Hue and Hoi An.</p>'
    }
]

# Verify that each target exists uniquely in the source post
print("=== Validating 28 Stage 62 Mesh Operations ===")
matched = 0
unmatched = 0

verified_ops = []

for op in OPS:
    pid = op['post_id']
    slug = op['slug']
    target = op['target']
    p = sources.get(str(pid))
    if not p:
        print(f"[MISSING POST] ID {pid} ({slug}) not in source contents!")
        unmatched += 1
        continue
    content = p['content']
    count = content.count(target)
    if count == 1:
        matched += 1
        verified_ops.append(op)
    elif count == 0:
        print(f"[TARGET NOT FOUND] in ID {pid} ({slug}):")
        print(f"  Target: {target[:80]}...")
        unmatched += 1
    else:
        print(f"[AMBIGUOUS TARGET] found {count} times in ID {pid} ({slug})!")
        unmatched += 1

print(f"\nValidation Summary: Matched {matched} / {len(OPS)}, Unmatched/Errors: {unmatched}")

with open('ops/stage62_mesh_ops.json', 'w', encoding='utf-8') as f:
    json.dump(verified_ops, f, indent=2, ensure_ascii=False)

if matched == len(OPS):
    print("ALL 28 OPERATIONS ARE 100% VALIDATED!")
else:
    print("Some operations need adjustment.")

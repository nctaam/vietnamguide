# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 65: Exact Mesh Assembler and Validator.
Constructs 28 surgical internal linking replacements across 28 posts,
verifying that each target exists exactly once in the source contents.
"""

import json
import sys

sys.stdout.reconfigure(encoding='utf-8')

sources = json.load(open('ops/stage65_mesh_raw_sources.json', 'r', encoding='utf-8'))

# Helper to slice exact strings from source content to prevent any encoding/whitespace mismatches
def get_exact_snippet(slug, pattern, start_delim, end_delim):
    c = sources[slug]['content']
    idx = c.find(pattern)
    if idx == -1:
        raise ValueError(f"Pattern '{pattern}' not found in {slug}")
    start = c.rfind(start_delim, 0, idx) if start_delim else idx
    end = c.find(end_delim, idx) + len(end_delim) if end_delim else idx + len(pattern)
    return c[start:end]

OPERATIONS = [
    # =========================================================================
    # Group 1: phu-quoc-beaches-guide (4 links)
    # URL: /destinations/phu-quoc-beaches-guide/
    # =========================================================================
    {
        "post_id": 234,
        "slug": "phu-quoc-travel-guide",
        "pillar": "phu-quoc-beaches-guide",
        "target": '<li><strong>Best premium resort fit:</strong> Kem Beach, Sao Beach, Ong Lang, or the quieter northern coast depending on isolation tolerance.</li>',
        "replacement": '<li><strong>Best premium resort fit:</strong> Kem Beach, Sao Beach, Ong Lang (see our dedicated <a href="/destinations/phu-quoc-beaches-guide/">Phu Quoc beaches guide</a> for seasonal winds and maps), or the quieter northern coast depending on isolation tolerance.</li>'
    },
    {
        "post_id": 204,
        "slug": "best-beaches-in-vietnam",
        "pillar": "phu-quoc-beaches-guide",
        "target": '<li><strong>Best island resort pick:</strong> Phu Quoc when the beach itself is the trip, not an add-on.</li>',
        "replacement": '<li><strong>Best island resort pick:</strong> Phu Quoc when the beach itself is the trip (explore our <a href="/destinations/phu-quoc-beaches-guide/">Phu Quoc beaches guide</a> for sand quality and wind seasons), not an add-on.</li>'
    },
    {
        "post_id": 720,
        "slug": "cham-islands-day-trip-guide",
        "pillar": "phu-quoc-beaches-guide",
        "target": '<li><a href="/destinations/where-to-stay-in-hoi-an/">Where to Stay in Hoi An</a> &mdash; boutique Old Town stays, An Bang beachfront resorts, and rice paddy villas.</li>\n</ul>',
        "replacement": '<li><a href="/destinations/where-to-stay-in-hoi-an/">Where to Stay in Hoi An</a> &mdash; boutique Old Town stays, An Bang beachfront resorts, and rice paddy villas.</li>\n<li><a href="/destinations/phu-quoc-beaches-guide/">Phu Quoc Beaches Guide</a> &mdash; compare Sao Beach, Ong Lang sunsets, Bai Khem resorts, and seasonal winds.</li>\n</ul>'
    },
    {
        "post_id": 241,
        "slug": "phu-quoc-vs-nha-trang",
        "pillar": "phu-quoc-beaches-guide",
        "target": '<li>Compare <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, and <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a> before choosing Phu Quoc over Nha Trang, Con Dao, Da Nang/Hoi An, or skipping beach time.</li>',
        "replacement": '<li>Compare <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, <a href="/destinations/phu-quoc-beaches-guide/">Phu Quoc Beaches Guide</a>, <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, and <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a> before choosing Phu Quoc over Nha Trang, Con Dao, Da Nang/Hoi An, or skipping beach time.</li>'
    },

    # =========================================================================
    # Group 2: con-dao-vs-phu-quoc (4 links)
    # URL: /compare/con-dao-vs-phu-quoc/
    # =========================================================================
    {
        "post_id": 237,
        "slug": "con-dao-travel-guide",
        "pillar": "con-dao-vs-phu-quoc",
        "target": '<li><strong>Skip when convenience matters more than quiet</strong>; Phu Quoc, Da Nang, or Hoi An usually give easier backup plans.</li>',
        "replacement": '<li><strong>Skip when convenience matters more than quiet</strong>; compare our <a href="/compare/con-dao-vs-phu-quoc/">Con Dao vs Phu Quoc</a> guide to see which southern island fits your budget and travel pace.</li>'
    },
    {
        "post_id": 231,
        "slug": "best-islands-in-vietnam",
        "pillar": "con-dao-vs-phu-quoc",
        "target": 'For most international travelers, Phu Quoc is the easiest island resort answer, Con Dao is the strongest quiet premium answer, and Cat Ba is the best northern island-base answer.',
        "replacement": 'For most international travelers, Phu Quoc is the easiest island resort answer, Con Dao is the strongest quiet premium answer (see our head-to-head <a href="/compare/con-dao-vs-phu-quoc/">Con Dao vs Phu Quoc comparison</a>), and Cat Ba is the best northern island-base answer.'
    },
    {
        "post_id": 481,
        "slug": "where-to-stay-in-vietnam-base-decisions",
        "pillar": "con-dao-vs-phu-quoc",
        "target": '<tr><td data-label="Destination">Phu Quoc</td><td data-label="Default base logic">Choose the beach/resort zone by rest style and exit plan, not just photos.</td>',
        "replacement": '<tr><td data-label="Destination">Phu Quoc</td><td data-label="Default base logic">Choose the beach/resort zone by rest style and exit plan (compare with <a href="/compare/con-dao-vs-phu-quoc/">Con Dao vs Phu Quoc</a>).</td>'
    },
    {
        "post_id": 20,
        "slug": "14-days-in-vietnam",
        "pillar": "con-dao-vs-phu-quoc",
        "target": 'Central coast beach time around Da Nang/Hoi An, or Phu Quoc only if the final flight path supports it.',
        "replacement": 'Central coast beach time around Da Nang/Hoi An, or an island finish (read our <a href="/compare/con-dao-vs-phu-quoc/">Con Dao vs Phu Quoc</a> breakdown) if the flight path supports it.'
    },

    # =========================================================================
    # Group 3: da-lat-waterfalls-guide (4 links)
    # URL: /destinations/da-lat-waterfalls-guide/
    # =========================================================================
    {
        "post_id": 611,
        "slug": "da-lat-travel-guide",
        "pillar": "da-lat-waterfalls-guide",
        "target": '<li><strong>Datanla Canyoning &amp; Alpine Coaster:</strong> Professionally guided river abseiling down natural waterfalls, cliff jumps, and a self-controlled bobsled run through native pine canopies. Book exclusively with licensed operators holding international UIAA gear certifications.</li>',
        "replacement": '<li><strong>Datanla Canyoning &amp; Alpine Coaster:</strong> Professionally guided river abseiling down natural waterfalls, cliff jumps, and a self-controlled bobsled run through native pine canopies (see our complete <a href="/destinations/da-lat-waterfalls-guide/">Da Lat waterfalls guide</a> for Pongour, Elephant falls, and canyoning safety). Book exclusively with licensed operators holding international UIAA gear certifications.</li>'
    },
    {
        "post_id": 718,
        "slug": "sapa-trekking-routes-guide",
        "pillar": "da-lat-waterfalls-guide",
        "target": '<li><a href="/plan/what-to-pack-for-vietnam-region-season/">Vietnam Packing Guide</a> &mdash; trail footwear, waterproof layers, and highland gear essentials.</li>\n</ul>',
        "replacement": '<li><a href="/plan/what-to-pack-for-vietnam-region-season/">Vietnam Packing Guide</a> &mdash; trail footwear, waterproof layers, and highland gear essentials.</li>\n<li><a href="/destinations/da-lat-waterfalls-guide/">Da Lat Waterfalls Guide</a> &mdash; canyoning descents, alpine coasters, and southern highland cascades.</li>\n</ul>'
    },
    {
        "post_id": 104,
        "slug": "north-central-south-vietnam",
        "pillar": "da-lat-waterfalls-guide",
        "target": 'Use this comparison when the route feels too wide. Then move to the <a href="/plan/best-time-to-visit-vietnam/">Best Time guide</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before committing money.',
        "replacement": 'Use this comparison when the route feels too wide. Then move to the <a href="/plan/best-time-to-visit-vietnam/">Best Time guide</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, highland adventures in our <a href="/destinations/da-lat-waterfalls-guide/">Da Lat waterfalls guide</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before committing money.'
    },
    {
        "post_id": 224,
        "slug": "21-days-in-vietnam",
        "pillar": "da-lat-waterfalls-guide",
        "target": '<p>Select the extension only if it gives a new kind of memory: hill climate, coffee, gardens, beach, or cave time.</p>',
        "replacement": '<p>Select the extension only if it gives a new kind of memory: hill climate, coffee, gardens, beach, or <a href="/destinations/da-lat-waterfalls-guide/">Da Lat waterfalls</a> and canyoning adventures.</p>'
    },

    # =========================================================================
    # Group 4: vietnam-sleeper-bus-guide (4 links)
    # URL: /plan/vietnam-sleeper-bus-guide/
    # =========================================================================
    {
        "post_id": 155,
        "slug": "transport-within-vietnam",
        "pillar": "vietnam-sleeper-bus-guide",
        "target": '<tr><td data-label="Mistake">Using sleeper buses as the default</td><td data-label="Why it happens">They look cheap and seem to save a hotel night.</td><td data-label="Better move">Use them only when the operator, seat, pickup, and arrival time still make sense.</td><td data-label="What it protects">Sleep, safety margin, and the next day\'s experience.</td></tr>',
        "replacement": '<tr><td data-label="Mistake">Using sleeper buses as the default</td><td data-label="Why it happens">They look cheap and seem to save a hotel night.</td><td data-label="Better move">Use our <a href="/plan/vietnam-sleeper-bus-guide/">Vietnam sleeper bus guide</a> to book VIP private cabin buses and choose lower-deck berths.</td><td data-label="What it protects">Sleep, safety margin, and the next day\'s experience.</td></tr>'
    },
    {
        "post_id": 473,
        "slug": "vietnam-first-trip-planning-checklist",
        "pillar": "vietnam-sleeper-bus-guide",
        "target": "<h3>What is the biggest first-trip mistake?</h3>\n<p>Adding a destination without naming the transfer cost. The map looks better than the day feels.</p>",
        "replacement": "<h3>What is the biggest first-trip mistake?</h3>\n<p>Adding a destination without naming the transfer cost. The map looks better than the day feels (consult our <a href=\"/plan/vietnam-sleeper-bus-guide/\">Vietnam sleeper bus guide</a> and train guides before locking long overland legs).</p>"
    },
    {
        "post_id": 520,
        "slug": "hanoi-to-sapa-transport",
        "pillar": "vietnam-sleeper-bus-guide",
        "target": '<p><strong>Use a cabin or sleeper bus when direct arrival and budget matter more than rail rhythm.</strong>',
        "replacement": '<p><strong>Use a cabin or sleeper bus (see our <a href="/plan/vietnam-sleeper-bus-guide/">Vietnam sleeper bus guide</a> for berth selection) when direct arrival and budget matter more than rail rhythm.</strong>'
    },
    {
        "post_id": 521,
        "slug": "hanoi-to-ha-giang-transport",
        "pillar": "vietnam-sleeper-bus-guide",
        "target": get_exact_snippet('hanoi-to-ha-giang-transport', 'VIP Cabin Sleeper Bus:', '<li>', '</li>'),
        "replacement": '<li><strong>VIP Cabin Sleeper Bus:</strong> 350,000–450,000 VND one-way ($14–$18 USD; review our <a href="/plan/vietnam-sleeper-bus-guide/">Vietnam sleeper bus guide</a> for cabin vs standard comparisons); private enclosed berths with USB charging, curtains, and air-conditioning (Sao Viet, Bang Phan, Quang Nghi).</li>'
    },

    # =========================================================================
    # Group 5: phong-nha-to-hue-transport (4 links)
    # URL: /plan/phong-nha-to-hue-transport/
    # =========================================================================
    {
        "post_id": 503,
        "slug": "phong-nha-travel-guide",
        "pillar": "phong-nha-to-hue-transport",
        "target": '<li><strong>Transit from Dong Hoi Airport (VDH) / Ga Dong Hoi:</strong> Local public bus B4 to Phong Nha town center: 40,000 VND (45 km, 60 minutes). GrabCar / Taxi: 380,000–450,000 VND.</li>',
        "replacement": '<li><strong>Transit from Dong Hoi Airport (VDH) / Ga Dong Hoi:</strong> Local public bus B4 to Phong Nha town center: 40,000 VND (45 km, 60 minutes). GrabCar / Taxi: 380,000–450,000 VND (for onward travel to Hue, see our <a href="/plan/phong-nha-to-hue-transport/">Phong Nha to Hue transport guide</a>).</li>'
    },
    {
        "post_id": 500,
        "slug": "hue-imperial-city-guide",
        "pillar": "phong-nha-to-hue-transport",
        "target": 'dedicated <a href="/destinations/phong-nha-travel-guide/">Phong Nha travel guide</a> for karst logistics and expedition planning.',
        "replacement": 'dedicated <a href="/destinations/phong-nha-travel-guide/">Phong Nha travel guide</a> for karst logistics, alongside our <a href="/plan/phong-nha-to-hue-transport/">Phong Nha to Hue transport guide</a> for DMZ stops.'
    },
    {
        "post_id": 629,
        "slug": "phong-nha-cave-treks",
        "pillar": "phong-nha-to-hue-transport",
        "target": '<p>Take the overnight sleeper train to Dong Hoi Railway Station (10 hours from Hanoi, 6 hours from Da Nang). From Dong Hoi, local buses, private taxis, or Oxalis transfer shuttles take 45 minutes to reach Phong Nha town.</p>',
        "replacement": '<p>Take the overnight sleeper train to Dong Hoi Railway Station (10 hours from Hanoi, 6 hours from Da Nang). From Dong Hoi, local buses, private taxis, or Oxalis transfer shuttles take 45 minutes to reach Phong Nha town (for onward travel south to Hue, consult our <a href="/plan/phong-nha-to-hue-transport/">Phong Nha to Hue transport guide</a>).</p>'
    },
    {
        "post_id": 184,
        "slug": "best-things-to-do-in-hue",
        "pillar": "phong-nha-to-hue-transport",
        "target": get_exact_snippet('best-things-to-do-in-hue', 'DMZ history', '<tr>', '</tr>'),
        "replacement": '<tr><td data-label="Pacing priority">Add only if the route breathes</td><td data-label="Add only if the route breathes">Bach Ma, DMZ history (see <a href="/plan/phong-nha-to-hue-transport/">Phong Nha to Hue transport</a> for Vinh Moc tunnels), or slower countryside if those are personal priorities.</td><td data-label="Skip first">Adding distant trips because the hotel is already booked.</td><td data-label="Why">Hue, Hoi An, and Da Nang can create a rich trip with fewer hotel moves.</td></tr>'
    },

    # =========================================================================
    # Group 6: da-lat-to-nha-trang-transport (4 links)
    # URL: /plan/da-lat-to-nha-trang-transport/
    # =========================================================================
    {
        "post_id": 244,
        "slug": "nha-trang-travel-guide",
        "pillar": "da-lat-to-nha-trang-transport",
        "target": 'a 3-hour limousine shuttle climbs the Khanh Le Pass directly into the Central Highlands (see our <a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide &amp; Highlands Route</a>).',
        "replacement": 'a 3-hour limousine shuttle climbs the Khanh Le Pass directly into the Central Highlands (see our <a href="/plan/da-lat-to-nha-trang-transport/">Da Lat to Nha Trang transport guide</a> and <a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide &amp; Highlands Route</a>).'
    },
    {
        "post_id": 247,
        "slug": "mui-ne-vs-nha-trang",
        "pillar": "da-lat-to-nha-trang-transport",
        "target": 'Highlands (detailed in our <a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide</a>).',
        "replacement": 'Highlands (detailed in our <a href="/plan/da-lat-to-nha-trang-transport/">Da Lat to Nha Trang transport guide</a> and <a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide</a>).'
    },
    {
        "post_id": 19,
        "slug": "10-days-in-vietnam",
        "pillar": "da-lat-to-nha-trang-transport",
        "target": 'Shared private transport can be better value than saving money and losing rest.',
        "replacement": 'Shared private transport (such as mountain pass limousines on the <a href="/plan/da-lat-to-nha-trang-transport/">Da Lat to Nha Trang route</a>) can be better value than saving money and losing rest.'
    },
    {
        "post_id": 474,
        "slug": "best-vietnam-routes-first-time-visitors",
        "pillar": "da-lat-to-nha-trang-transport",
        "target": get_exact_snippet('best-vietnam-routes-first-time-visitors', 'Intercity Transport Baseline', '<li>', '</li>'),
        "replacement": '<li><strong>Intercity Transport Baseline:</strong> Budget $180–$260 USD per person for domestic flights, mountain limousines (like <a href="/plan/da-lat-to-nha-trang-transport/">Da Lat to Nha Trang transport</a>), and train cabins across a 2-week trip.</li>'
    },

    # =========================================================================
    # Group 7: vietnam-in-june (4 links)
    # URL: /plan/vietnam-in-june/
    # =========================================================================
    {
        "post_id": 14,
        "slug": "best-time-to-visit-vietnam",
        "pillar": "vietnam-in-june",
        "target": 'and <a href="/plan/vietnam-in-may/">Vietnam in May</a>, alongside our <a href="/plan/tet-in-vietnam-travel-guide/">Tet in Vietnam guide</a>',
        "replacement": '<a href="/plan/vietnam-in-may/">Vietnam in May</a>, and <a href="/plan/vietnam-in-june/">Vietnam in June</a>, alongside our <a href="/plan/tet-in-vietnam-travel-guide/">Tet in Vietnam guide</a>'
    },
    {
        "post_id": 685,
        "slug": "vietnam-in-april",
        "pillar": "vietnam-in-june",
        "target": '<li><a href="/plan/vietnam-in-may/">Vietnam in May</a> &mdash; post-holiday shoulder season calm, central beach sunshine, and Tam Coc harvest.</li>',
        "replacement": '<li><a href="/plan/vietnam-in-may/">Vietnam in May</a> &mdash; post-holiday shoulder season calm, central beach sunshine, and Tam Coc harvest.</li>\n<li><a href="/plan/vietnam-in-june/">Vietnam in June</a> &mdash; peak central beach sunshine, summer harvest, and school holiday dynamics.</li>'
    },
    {
        "post_id": 721,
        "slug": "vietnam-in-may",
        "pillar": "vietnam-in-june",
        "target": '<li><a href="/plan/vietnam-in-june/">Vietnam in June</a> &mdash; summer heat dynamics, fruit harvest festivals, and beach strategies.</li>',
        "replacement": '<li><a href="/plan/vietnam-in-june/">Vietnam in June</a> &mdash; summer weather patterns, central coast sunshine, and peak beach season.</li>'
    },
    {
        "post_id": 482,
        "slug": "vietnam-rainy-season-flexible-route",
        "pillar": "vietnam-in-june",
        "target": '<p class="vg-guide-lede">Traveling during Vietnam\'s monsoon months remains thoroughly rewarding if you build buffer days into sensitive transit corridors.',
        "replacement": '<p class="vg-guide-lede">Traveling during Vietnam\'s monsoon months (see our seasonal breakdown in <a href="/plan/vietnam-in-june/">Vietnam in June</a>) remains thoroughly rewarding if you build buffer days into sensitive transit corridors.'
    }
]

def main():
    print(f"=== Validating {len(OPERATIONS)} Operations against Raw Sources ===")
    errors = 0
    validated_ops = []

    mutated_sources = {}
    for k, v in sources.items():
        if v:
            mutated_sources[k] = v['content']

    for i, op in enumerate(OPERATIONS, 1):
        slug = op['slug']
        pillar = op['pillar']
        target = op['target']
        replacement = op['replacement']

        if slug not in mutated_sources:
            print(f"[{i}] FAIL: {slug} not found in sources")
            errors += 1
            continue

        curr_content = mutated_sources[slug]
        cnt = curr_content.count(target)

        if cnt == 0:
            print(f"[{i}] FAIL (0 matches): {slug} for pillar '{pillar}'")
            print(f"    Target snippet: {repr(target[:80])}...")
            errors += 1
        elif cnt > 1:
            print(f"[{i}] FAIL ({cnt} matches, ambiguous): {slug} for pillar '{pillar}'")
            print(f"    Target snippet: {repr(target[:80])}...")
            errors += 1
        else:
            print(f"[{i}] PASS (1/1): {slug} -> {pillar}")
            mutated_sources[slug] = curr_content.replace(target, replacement, 1)
            validated_ops.append(op)

    print("\n" + "=" * 60)
    if errors == 0:
        print(f"SUCCESS: All {len(validated_ops)} operations validated 1-to-1 perfectly!")
        with open('ops/stage65_mesh_ops.json', 'w', encoding='utf-8') as f:
            json.dump(validated_ops, f, ensure_ascii=False, indent=2)
        print("Saved to ops/stage65_mesh_ops.json")
    else:
        print(f"FAILED: {errors} operation(s) had matching errors.")
        sys.exit(1)

if __name__ == '__main__':
    main()

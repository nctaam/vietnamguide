# -*- coding: utf-8 -*-
"""
Design and verify exact paragraph replacements for Route Budget Link Mesh.
"""
import json
import re

with open('ops/target_posts_content.json', 'r', encoding='utf-8') as f:
    posts = json.load(f)

print(f"Loaded {len(posts)} posts for mesh design.")

# Candidate replacements
# Ha Giang links -> /costs/ha-giang-loop-cost-budget/
# Da Nang & Hoi An links -> /costs/da-nang-hoi-an-budget/
# Northern Highlights links -> /costs/hanoi-ninh-binh-ha-long-budget/

REPLACEMENTS = []

# 1. Post 519: ha-giang-loop-planning-guide
c519 = posts['519']['content']
match519 = re.search(r'<p>The cheapest loop is not always the cheapest trip\..*?</p>', c519, re.DOTALL)
if match519:
    orig = match519.group(0)
    rep = orig.replace(
        'A better budget asks which risk needs money first.',
        'A better budget asks which risk needs money first—compare complete self-drive and guided pricing tiers in our <a href="/costs/ha-giang-loop-cost-budget/">Ha Giang Loop Cost &amp; Budget breakdown</a>.'
    )
    REPLACEMENTS.append({'post_id': 519, 'slug': 'ha-giang-loop-planning-guide', 'target': orig, 'replacement': rep})

# 2. Post 521: hanoi-to-ha-giang-transport
c521 = posts['521']['content']
match521 = re.search(r'<p>The cheapest transfer may still be right for a rested, flexible traveler with a light route\..*?</p>', c521, re.DOTALL)
if match521:
    orig = match521.group(0)
    rep = orig.replace(
        'and the cost of missing a flight or losing a loop day.',
        'and the cost of missing a flight or losing a loop day. To factor bus tickets into your total itinerary expenses, consult our <a href="/costs/ha-giang-loop-cost-budget/">Ha Giang Loop travel budget breakdown</a>.'
    )
    REPLACEMENTS.append({'post_id': 521, 'slug': 'hanoi-to-ha-giang-transport', 'target': orig, 'replacement': rep})

# 3. Post 522: ha-giang-safety-guide
c522 = posts['522']['content']
match522 = re.search(r'<p>For complete route planning, stopover recommendations, and itinerary pacing, cross-reference our <a href="/destinations/ha-giang-loop-planning-guide/">Ha Giang Loop Planning Guide</a>, <a href="/plan/hanoi-to-ha-giang-transport/">Hanoi to Ha Giang Transport Guide</a>, and <a href="/plan/health-travel-insurance-vietnam/">Vietnam Health &amp; Travel Insurance Guide</a>\.</p>', c522, re.DOTALL)
if match522:
    orig = match522.group(0)
    rep = '<p>For complete route planning, stopover recommendations, and itinerary pacing, cross-reference our <a href="/destinations/ha-giang-loop-planning-guide/">Ha Giang Loop Planning Guide</a>, calculate realistic expenses in our <a href="/costs/ha-giang-loop-cost-budget/">Ha Giang Loop Cost &amp; Budget breakdown</a>, and verify coverage with our <a href="/plan/health-travel-insurance-vietnam/">Vietnam Health &amp; Travel Insurance Guide</a>.</p>'
    REPLACEMENTS.append({'post_id': 522, 'slug': 'ha-giang-safety-guide', 'target': orig, 'replacement': rep})

# 4. Post 523: ha-giang-easy-rider-vs-self-drive
c523 = posts['523']['content']
match523 = re.search(r'<p>While self-driving appears cheaper upfront, hidden expenses \(fuel, mechanical repairs, gear rental, police fines, and damage deposits\) narrow the price gap significantly:</p>', c523, re.DOTALL)
if match523:
    orig = match523.group(0)
    rep = '<p>While self-driving appears cheaper upfront, hidden expenses (fuel, mechanical repairs, gear rental, police fines, and damage deposits) narrow the price gap significantly (see our full itemized <a href="/costs/ha-giang-loop-cost-budget/">Ha Giang Loop Cost &amp; Budget comparison</a>):</p>'
    REPLACEMENTS.append({'post_id': 523, 'slug': 'ha-giang-easy-rider-vs-self-drive', 'target': orig, 'replacement': rep})

# 5. Post 504: sapa-vs-ha-giang
c504 = posts['504']['content']
match504 = re.search(r'<p>Both Sapa and Ha Giang are usually Hanoi-linked decisions\..*?</p>', c504, re.DOTALL)
if match504:
    orig = match504.group(0)
    rep = orig.replace(
        'The hidden cost is not only the transfer there.',
        'The hidden cost is not only the transfer there—budgeting permits, motorbike rentals, homestay meals, and sleeper buses requires careful accounting (see our itemized <a href="/costs/ha-giang-loop-cost-budget/">Ha Giang Loop budget guide</a>).'
    )
    REPLACEMENTS.append({'post_id': 504, 'slug': 'sapa-vs-ha-giang', 'target': orig, 'replacement': rep})

# 6. Post 213: da-nang-travel-guide
c213 = posts['213']['content']
match213 = re.search(r'<p>Count nights by what Da Nang protects: beach, airport, a day trip, a transfer, or rest\..*?</p>', c213, re.DOTALL)
if match213:
    orig = match213.group(0)
    rep = orig.replace(
        'Extra nights are valuable only when they make the route calmer or deeper.',
        'Extra nights are valuable only when they make the route calmer or deeper. Benchmark expected spending across beachfront hotels, taxis, and dining with our <a href="/costs/da-nang-hoi-an-budget/">Da Nang &amp; Hoi An Travel Budget guide</a>.'
    )
    REPLACEMENTS.append({'post_id': 213, 'slug': 'da-nang-travel-guide', 'target': orig, 'replacement': rep})

# 7. Post 209: da-nang-vs-hoi-an
c209 = posts['209']['content']
match209 = re.search(r'<p>The base decision is a friction decision\. A hotel move only makes sense when it saves more energy than it costs\.</p>', c209, re.DOTALL)
if match209:
    orig = match209.group(0)
    rep = '<p>The base decision is a friction decision. A hotel move only makes sense when it saves more energy than it costs. To compare 5-day accommodation, transfer, and dining expenses across both bases, consult our <a href="/costs/da-nang-hoi-an-budget/">Da Nang &amp; Hoi An Travel Budget guide</a>.</p>'
    REPLACEMENTS.append({'post_id': 209, 'slug': 'da-nang-vs-hoi-an', 'target': orig, 'replacement': rep})

# 8. Post 178: best-things-to-do-in-hoi-an
c178 = posts['178']['content']
match178 = re.search(r'<p>Hoi An is less about how many attractions fit and more about whether the route protects the right light, meal, weather window, and transfer energy\..*?</p>', c178, re.DOTALL)
if match178:
    orig = match178.group(0)
    rep = orig.replace(
        'Use nights as the unit, not checkmarks.',
        'Use nights as the unit, not checkmarks. For an itemized breakdown of heritage passes, bike rentals, tailor deposits, and dining run rates, explore our <a href="/costs/da-nang-hoi-an-budget/">Da Nang &amp; Hoi An Budget guide</a>.'
    )
    REPLACEMENTS.append({'post_id': 178, 'slug': 'best-things-to-do-in-hoi-an', 'target': orig, 'replacement': rep})

# 9. Post 287: hanoi-travel-guide
c287 = posts['287']['content']
match287 = re.search(r'<p>Hanoi can be inexpensive, but the lowest room price is not always the lowest trip cost\..*?</p>', c287, re.DOTALL)
if match287:
    orig = match287.group(0)
    rep = orig.replace(
        'or private northern move can save more value than it costs.',
        'or private northern move can save more value than it costs. When combining the capital with countryside and bay highlights, reference our <a href="/costs/hanoi-ninh-binh-ha-long-budget/">Hanoi, Ninh Binh &amp; Ha Long Bay Budget guide</a>.'
    )
    REPLACEMENTS.append({'post_id': 287, 'slug': 'hanoi-travel-guide', 'target': orig, 'replacement': rep})

# 10. Post 190: ninh-binh-travel-guide
c190 = posts['190']['content']
match190 = re.search(r'<p>Ninh Binh is close enough to Hanoi to tempt travelers into underplanning\. The smart move is to decide the transfer style before choosing the base\.</p>', c190, re.DOTALL)
if match190:
    orig = match190.group(0)
    rep = '<p>Ninh Binh is close enough to Hanoi to tempt travelers into underplanning. The smart move is to decide the transfer style before choosing the base. To budget limousine shuttles, Trang An boat tickets, and eco-lodges alongside Ha Long Bay, review our <a href="/costs/hanoi-ninh-binh-ha-long-budget/">Hanoi, Ninh Binh &amp; Ha Long Budget guide</a>.</p>'
    REPLACEMENTS.append({'post_id': 190, 'slug': 'ninh-binh-travel-guide', 'target': orig, 'replacement': rep})

# 11. Post 195: ha-long-bay-travel-guide
c195 = posts['195']['content']
match195 = re.search(r'<p>Ha Long Bay works best when it is a deliberate northern chapter, not a famous name pasted between transfers\.</p>', c195, re.DOTALL)
if match195:
    orig = match195.group(0)
    rep = '<p>Ha Long Bay works best when it is a deliberate northern chapter, not a famous name pasted between transfers. For a realistic financial comparison between 6-hour premier day boats and 2D1N overnight boutique cruise cabins, consult our <a href="/costs/hanoi-ninh-binh-ha-long-budget/">Hanoi, Ninh Binh &amp; Ha Long Bay Budget guide</a>.</p>'
    REPLACEMENTS.append({'post_id': 195, 'slug': 'ha-long-bay-travel-guide', 'target': orig, 'replacement': rep})

# 12. Post 309: best-day-trips-from-hanoi
c309 = posts['309']['content']
match309 = re.search(r'<p>Use the transfer-to-experience ratio as a hard filter: a long day must buy a different route chapter, not just a famous name and a tired return\.</p>', c309, re.DOTALL)
if match309:
    orig = match309.group(0)
    rep = '<p>Use the transfer-to-experience ratio as a hard filter: a long day must buy a different route chapter, not just a famous name and a tired return. Compare admission tickets, boat fees, and limousine transfers across the northern triangle in our <a href="/costs/hanoi-ninh-binh-ha-long-budget/">Hanoi, Ninh Binh &amp; Ha Long Budget guide</a>.</p>'
    REPLACEMENTS.append({'post_id': 309, 'slug': 'best-day-trips-from-hanoi', 'target': orig, 'replacement': rep})

print(f"Designed {len(REPLACEMENTS)} replacements.")

# Validate unique occurrence of each target
for r in REPLACEMENTS:
    pid = str(r['post_id'])
    content = posts[pid]['content']
    cnt = content.count(r['target'])
    print(f"[{r['slug']}] Target count in post {pid}: {cnt}")
    if cnt != 1:
        print(f"  WARNING: target count is {cnt} (expected 1)")

with open('ops/route_budget_mesh_ops.json', 'w', encoding='utf-8') as f:
    json.dump(REPLACEMENTS, f, ensure_ascii=False, indent=2)
print("Saved to ops/route_budget_mesh_ops.json")

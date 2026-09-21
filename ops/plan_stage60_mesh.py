# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 60: Plan & Validate Link Mesh Replacements.
"""
import json
import re

with open('ops/stage60_mesh_sources.json', 'r', encoding='utf-8') as f:
    posts = json.load(f)

REPLACEMENTS = []

# 1. transport-within-vietnam -> /plan/vietnam-train-travel/
c_trans = posts['transport-within-vietnam']['content']
m_trans = re.search(r'<p>Fly if the itinerary is tight or the next day matters\..*?</p>', c_trans, re.DOTALL)
if m_trans:
    orig = m_trans.group(0)
    rep = orig.replace(
        'Check dsvn.vn for current train options before treating rail as the answer.',
        'For ticket booking procedures, berth choices, and scenic coastal rail advice, explore our <a href="/plan/vietnam-train-travel/">Vietnam Train Travel &amp; Reunification Express guide</a>.'
    )
    REPLACEMENTS.append({'slug': 'transport-within-vietnam', 'target': orig, 'replacement': rep, 'post_id': posts['transport-within-vietnam']['ID']})

# 2. da-nang-travel-guide -> /plan/vietnam-train-travel/
c_dn = posts['da-nang-travel-guide']['content']
m_dn = re.search(r'<p>Yes for Hoi An evenings and selected Hue movement, but avoid turning Da Nang into a commute hub\..*?</p>', c_dn, re.DOTALL)
if m_dn:
    orig = m_dn.group(0)
    rep = orig.replace(
        'an overnight in that place may be better.',
        'an overnight in that place may be better. For the scenic northward crossing to Hue, consider the coastal cliffside railway route detailed in our <a href="/plan/vietnam-train-travel/">Vietnam Train Travel &amp; Hai Van Pass guide</a>.'
    )
    REPLACEMENTS.append({'slug': 'da-nang-travel-guide', 'target': orig, 'replacement': rep, 'post_id': posts['da-nang-travel-guide']['ID']})

# 3. hue-imperial-city-guide -> /plan/vietnam-train-travel/
c_hue = posts['hue-imperial-city-guide']['content']
m_hue = re.search(r'<p>Hue usually works as part of a central Vietnam sequence with Da Nang and Hoi An\..*?</p>', c_hue, re.DOTALL)
if m_hue:
    orig = m_hue.group(0)
    rep = orig.replace(
        'The cleanest route uses one directional crossing over the Hai Van Pass instead of a same-day out-and-back.',
        'The cleanest route uses one directional crossing over the Hai Van Pass—either by road or via the scenic coastal rail line (see our <a href="/plan/vietnam-train-travel/">Vietnam Train Travel guide</a> for seat selection tips)—instead of a same-day out-and-back.'
    )
    REPLACEMENTS.append({'slug': 'hue-imperial-city-guide', 'target': orig, 'replacement': rep, 'post_id': posts['hue-imperial-city-guide']['ID']})

# 4. hanoi-travel-guide -> /plan/vietnam-train-travel/
c_han = posts['hanoi-travel-guide']['content']
m_han = re.search(r'<p>Hanoi transport planning is mostly about timing and energy\..*?</p>', c_han, re.DOTALL)
if m_han:
    orig = m_han.group(0)
    rep = orig.replace(
        'and early pickups should shape the previous night.',
        'and early pickups should shape the previous night. For travelers continuing southward without domestic flights, Hanoi Railway Station (Ga Ha Noi) anchors overnight sleeper trains to Hue and Da Nang (see our <a href="/plan/vietnam-train-travel/">Vietnam Train Travel &amp; Reunification Express guide</a>).'
    )
    REPLACEMENTS.append({'slug': 'hanoi-travel-guide', 'target': orig, 'replacement': rep, 'post_id': posts['hanoi-travel-guide']['ID']})

# 5. nha-trang-travel-guide -> /destinations/da-lat-travel-guide/
c_nt = posts['nha-trang-travel-guide']['content']
m_nt = re.search(r'<p>Two nights is the fast minimum\. Three or four nights is better if you want Nha Trang to feel like more than an airport-linked beach stop\.</p>', c_nt, re.DOTALL)
if m_nt:
    orig = m_nt.group(0)
    rep = '<p>Two nights is the fast minimum. Three or four nights is better if you want Nha Trang to feel like more than an airport-linked beach stop. For travelers combining coastal waters with cool alpine air, a 3-hour limousine shuttle climbs the Khanh Le Pass directly into the Central Highlands (see our <a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide &amp; Highlands Route</a>).</p>'
    REPLACEMENTS.append({'slug': 'nha-trang-travel-guide', 'target': orig, 'replacement': rep, 'post_id': posts['nha-trang-travel-guide']['ID']})

# 6. ho-chi-minh-city-travel-guide -> /destinations/da-lat-travel-guide/
c_hcm = posts['ho-chi-minh-city-travel-guide']['content']
m_hcm = re.search(r'<p>Two nights is the fast minimum\. Three nights is the best first answer\. Four nights works when you want both the city and one serious day trip without rushing the airport chain\.</p>', c_hcm, re.DOTALL)
if m_hcm:
    orig = m_hcm.group(0)
    rep = '<p>Two nights is the fast minimum. Three nights is the best first answer. Four nights works when you want both the city and one serious day trip without rushing the airport chain. For an alpine contrast to southern humidity, short 50-minute domestic flights connect directly to the pine hills and coffee estates of the Central Highlands (explore our <a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide</a>).</p>'
    REPLACEMENTS.append({'slug': 'ho-chi-minh-city-travel-guide', 'target': orig, 'replacement': rep, 'post_id': posts['ho-chi-minh-city-travel-guide']['ID']})

# 7. mui-ne-vs-nha-trang -> /destinations/da-lat-travel-guide/
c_mn = posts['mui-ne-vs-nha-trang']['content']
m_mn = re.search(r'<p>Mui Ne competes with south-from-Ho-Chi-Minh-City time, central-coast stops, and any beach that needs sport conditions\..*?</p>', c_mn, re.DOTALL)
if m_mn:
    orig = m_mn.group(0)
    rep = orig.replace(
        'and any beach that needs sport conditions. Nha Trang competes with',
        'and any beach that needs sport conditions. Both coastal bases also serve as staging points climbing inland to the pine forests and cooler altitudes of the Central Highlands (detailed in our <a href="/destinations/da-lat-travel-guide/">Da Lat Travel Guide</a>). Nha Trang competes with'
    )
    REPLACEMENTS.append({'slug': 'mui-ne-vs-nha-trang', 'target': orig, 'replacement': rep, 'post_id': posts['mui-ne-vs-nha-trang']['ID']})

# 8. best-places-to-visit-vietnam -> /destinations/cao-bang-travel-guide/ & /destinations/da-lat-travel-guide/
c_bp = posts['best-places-to-visit-vietnam']['content']
orig_bp1 = '<tr><td data-label="Anchor type">Northern landscapes</td><td data-label="Examples to consider">Ninh Binh, Ha Long or Lan Ha Bay, Sapa, Ha Giang, and northern countryside.</td><td data-label="What it changes">The north can justify a focused route without crossing the whole country.</td><td data-label="Planning caution">Remote mountain routes need weather, road, and time discipline.</td></tr>'
if orig_bp1 in c_bp:
    rep_bp1 = '<tr><td data-label="Anchor type">Northern landscapes</td><td data-label="Examples to consider">Ninh Binh, Ha Long or Lan Ha Bay, Sapa, Ha Giang, and border geoparks like <a href="/destinations/cao-bang-travel-guide/">Cao Bang &amp; Ban Gioc Waterfall</a>.</td><td data-label="What it changes">The north can justify a focused route without crossing the whole country.</td><td data-label="Planning caution">Remote mountain routes need weather, road, and time discipline.</td></tr>'
    REPLACEMENTS.append({'slug': 'best-places-to-visit-vietnam', 'target': orig_bp1, 'replacement': rep_bp1, 'post_id': posts['best-places-to-visit-vietnam']['ID']})

orig_bp2 = '<tr><td data-label="Anchor type">Southern city, river, and island texture</td><td data-label="Examples to consider">Ho Chi Minh City, Mekong Delta, Phu Quoc, and southern food culture.</td><td data-label="What it changes">The south works best when chosen for its own energy, not as a final checkbox.</td><td data-label="Planning caution">Phu Quoc and Mekong add-ons need season, flight, and transfer checks.</td></tr>'
if orig_bp2 in c_bp:
    rep_bp2 = '<tr><td data-label="Anchor type">Southern city, river, and island texture</td><td data-label="Examples to consider">Ho Chi Minh City, Mekong Delta, Phu Quoc, and highland coffee retreats like <a href="/destinations/da-lat-travel-guide/">Da Lat</a>.</td><td data-label="What it changes">The south works best when chosen for its own energy, not as a final checkbox.</td><td data-label="Planning caution">Phu Quoc and Mekong add-ons need season, flight, and transfer checks.</td></tr>'
    REPLACEMENTS.append({'slug': 'best-places-to-visit-vietnam', 'target': orig_bp2, 'replacement': rep_bp2, 'post_id': posts['best-places-to-visit-vietnam']['ID']})

# 9. ha-giang-loop-planning-guide -> /destinations/cao-bang-travel-guide/
c_hg = posts['ha-giang-loop-planning-guide']['content']
m_hg = re.search(r'<p>The classic loop usually starts from Ha Giang city and moves through mountain districts such as Quan Ba, Yen Minh, Dong Van, and Meo Vac, with Ma Pi Leng and the Dong Van Karst Plateau as major scenic anchors\..*?</p>', c_hg, re.DOTALL)
if m_hg:
    orig = m_hg.group(0)
    rep = orig.replace(
        'as major scenic anchors. Some slower versions',
        'as major scenic anchors. Ambitious multi-day routes extend eastward from Meo Vac through Bao Lac toward Ban Gioc Waterfall (see our comprehensive <a href="/destinations/cao-bang-travel-guide/">Cao Bang Travel Guide &amp; Loop Logistics</a>). Some slower versions'
    )
    REPLACEMENTS.append({'slug': 'ha-giang-loop-planning-guide', 'target': orig, 'replacement': rep, 'post_id': posts['ha-giang-loop-planning-guide']['ID']})

# 10. best-day-trips-from-hanoi -> /destinations/cao-bang-travel-guide/
c_dt = posts['best-day-trips-from-hanoi']['content']
m_dt = re.search(r'<p>Do not use a distant day trip before a flight unless the flight is late, the plan is private, and the return buffer is conservative\..*?</p>', c_dt, re.DOTALL)
if m_dt:
    orig = m_dt.group(0)
    rep = orig.replace(
        'A final Hanoi food, lake, museum, or craft block is safer.',
        'Furthermore, never attempt remote northern landmarks like Ban Gioc Waterfall as a single day trip&mdash;the 280-kilometer mountain drive requires an overnight loop (detailed in our <a href="/destinations/cao-bang-travel-guide/">Cao Bang Travel Guide &amp; Loop Logistics</a>). A final Hanoi food, lake, museum, or craft block is far safer.'
    )
    REPLACEMENTS.append({'slug': 'best-day-trips-from-hanoi', 'target': orig, 'replacement': rep, 'post_id': posts['best-day-trips-from-hanoi']['ID']})

# 11. sapa-vs-ha-giang -> /destinations/cao-bang-travel-guide/
c_sp = posts['sapa-vs-ha-giang']['content']
m_sp = re.search(r'<p>Choose Ninh Binh when you want easier karst countryside and simpler Hanoi-linked logistics\. Choose Sapa or Ha Giang only when the mountain chapter itself is worth the extra movement\.</p>', c_sp, re.DOTALL)
if m_sp:
    orig = m_sp.group(0)
    rep = '<p>Choose Ninh Binh when you want easier karst countryside and simpler Hanoi-linked logistics. Choose Sapa or Ha Giang only when the mountain chapter itself is worth the extra movement. For travelers seeking majestic frontier waterfalls and UNESCO geopark landscapes with gentler passenger pacing, explore our <a href="/destinations/cao-bang-travel-guide/">Cao Bang Travel Guide</a>.</p>'
    REPLACEMENTS.append({'slug': 'sapa-vs-ha-giang', 'target': orig, 'replacement': rep, 'post_id': posts['sapa-vs-ha-giang']['ID']})

print(f"Planned {len(REPLACEMENTS)} replacements.")

# Validation: target count == 1 and target != replacement
all_ok = True
for r in REPLACEMENTS:
    slug = r['slug']
    content = posts[slug]['content']
    cnt = content.count(r['target'])
    is_diff = r['target'] != r['replacement']
    status = "OK" if cnt == 1 and is_diff else "FAIL"
    print(f"[{status}] {slug} -> count: {cnt}, diff: {is_diff}")
    if status == "FAIL":
        all_ok = False

if all_ok:
    with open('ops/stage60_mesh_ops.json', 'w', encoding='utf-8') as f:
        json.dump(REPLACEMENTS, f, ensure_ascii=False, indent=2)
    print("\n[SUCCESS] All operations validated and saved to ops/stage60_mesh_ops.json")
else:
    print("\n[ERROR] Some replacements failed validation!")

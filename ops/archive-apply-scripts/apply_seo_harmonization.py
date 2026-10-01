# -*- coding: utf-8 -*-
"""
VietnamGuide Production SEO Metadata Harmonizer
Updates Rank Math titles, descriptions, and focus keywords for exact-match SERP optimization.
"""

import json
import paramiko

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

TITLE_UPDATES = {
    3: "VietnamGuide Privacy Policy & Independent Travel Data Terms",
    8: "Vietnam Itinerary & Route Guide: 7, 10, 14 Day Plans",
    11: "Vietnam Travel Newsletter - Independent Route Intelligence",
    12: "VietnamGuide Affiliate Disclosure & Editorial Integrity",
    59: "VietnamGuide Editorial Policy & Independent Standards",
    60: "VietnamGuide Source and Update Policy for Travel Facts",
    61: "Contact VietnamGuide Editorial Desk & Travel Planners",
    181: "Vietnam Safety and Scams: Practical Risk Guide for Visitors",
    187: "Vietnam Travel Insurance & Health Guide: What to Cover",
    474: "Best Vietnam Routes for First-Time Visitors: Planning Guide",
    477: "Hanoi First-Time Visitor Mistakes to Avoid When Planning",
    480: "Vietnam Food Safety and Street Food Etiquette Guide",
    497: "Best Vietnam Cities for First-Time Visitors: Route Guide",
    523: "Ha Giang Easy Rider vs Self-Drive: Motorbike Route Guide",
    524: "Sapa Trekking Guided vs Self-Guided: Mountain Route Guide",
}

KEYWORD_UPDATES = {
    8: "Vietnam itinerary",
    474: "best Vietnam routes for first-time visitors",
    477: "Hanoi first-time visitor mistakes",
    480: "Vietnam food safety and street food etiquette",
    497: "best Vietnam cities for first-time visitors",
    523: "Ha Giang easy rider vs self-drive",
    524: "Sapa trekking guided vs self-guided",
}

DESC_UPDATES = {
    5: "Independent Vietnam travel guide: compare realistic routes, honest travel costs, e-visa steps, regional weather patterns, and smart destination decisions.",
    6: "Essential Vietnam travel planning guide: practical advice on visa entry checks, route design, weather seasons, money, transport, and smart booking order.",
    7: "Explore top Vietnam destinations with practical guidance by region, travel style, weather seasons, route efficiency, and first-trip travel priorities.",
    11: "Subscribe to our Vietnam travel newsletter for concise travel planning updates, verified route ideas, practical checklists, and destination decision notes.",
    15: "Choose SIM and eSIM in Vietnam with device checks, local number needs, airport setup, coverage, hotspot tethering, roaming backup, and live signal checks.",
    19: "Plan 10 days in Vietnam with a balanced route order from Hanoi to Ho Chi Minh City, internal transfer timings, realistic night allocation, and daily costs.",
    58: "Learn more about VietnamGuide: an independent, on-the-ground travel planning team delivering verified route logistics, safety checks, and practical advice.",
    59: "Read the VietnamGuide editorial policy: zero sponsored placements, independent route vetting, strict fact-checking, and unbiased travel recommendations.",
    60: "Read the VietnamGuide source and update policy: how we verify travel pricing, train timetables, visa rules, and safety alerts through quarterly audits.",
    61: "How to contact VietnamGuide: reach our editorial desk for fact corrections, route updates, travel inquiries, or verified local intelligence in 48 hours.",
    62: "Read our affiliate review policy and partner vetting criteria: we only recommend transport providers, booking platforms, and services evaluated directly.",
    104: "Compare North vs Central vs South Vietnam for a first trip: choose the best region by season, route length, travel style, pacing, and what to skip.",
    170: "Choose which UNESCO heritage sites in Vietnam fit your route, with 2026 updates, first-trip priorities, skip logic, source checks, and verified photo proof.",
    204: "Discover the best beaches in Vietnam by month, route, and trip style: Phu Quoc, Da Nang, Hoi An, Nha Trang, Con Dao, Mui Ne, Quy Nhon, Cat Ba, and Phu Quy.",
    220: "Plan 7 days in Vietnam with focused one-week routes covering the north (Hanoi, Ha Long Bay, Ninh Binh) or central region without rushing transfers.",
    224: "Plan 21 days in Vietnam with a complete full-country route from North to South featuring Ha Giang Loop, Sapa, Central Heritage, Saigon, and Mekong Delta.",
    301: "Compare Old Quarter vs French Quarter vs West Lake by sleep quality, walking, food, pickup clarity, family fit, premium calm, and longer-stay comfort.",
    326: "Evaluate Ninh Binh day trip vs overnight stay: practical verdict by route length, transfer pressure, Trang An vs Tam Coc, weather, cost, and hotels.",
    331: "Compare Hanoi to Ninh Binh transport options: evaluate train, limousine van, private car, and day tours by drop-off point, luggage space, and timing.",
    473: "Prepare with our Vietnam first trip planning checklist before booking: entry checks, route shape, weather risk, cost anchors, arrival setup, and tips.",
    474: "Find the best Vietnam routes for first-time visitors: compare north-only, central heritage, south-only, and open-jaw itineraries before booking flights.",
    475: "Learn what to pack for Vietnam by region and season with north, central, south, mountain trekking, coastal, rainy season, and carry-on gear logic.",
    477: "Avoid common Hanoi first-time visitor mistakes regarding hotel locations, arrival night transit, day trip booking pressure, and winter weather packing.",
    480: "Master Vietnam food safety and street food etiquette: choose clean busy stalls, order politely, handle sauces, ice, cash, seating, and flexible dining.",
    496: "Authoritative Tet in Vietnam travel guide: exact dates, transport pressure, changed opening hours, holiday etiquette, cash needs, and route decisions.",
    497: "Choose the best Vietnam cities for first-time visitors by route role, airport arrival logic, culinary culture, beach comfort, day trips, and pacing.",
    498: "Compare Hanoi vs Ho Chi Minh City by first-night comfort, route direction, weather seasons, food scenes, day trips, airport logic, and trip length.",
    499: "Authoritative Hoi An Ancient Town guide: decide whether to stay overnight, visit from Da Nang, go by day or evening, or skip when routes need focus.",
    500: "Authoritative Hue Imperial City guide: decide whether Hue deserves a two-night stay, one-night stop, or half-day visit by pacing, heat, and tombs.",
    501: "Authoritative Da Nang beaches guide: choose between My Khe, Non Nuoc, Son Tra edge, and Hoi An coast by weather season, hotel style, and family needs.",
    502: "Decide between Mekong Delta overnight vs day trip: choose a quick day tour, Can Tho stay, deeper river route, or clean skip by timing and comfort.",
    503: "Authoritative Phong Nha travel guide: decide whether Phong Nha is worth the cave detour by route fit, season, nights needed, fitness, and trade-offs.",
    504: "Compare Sapa vs Ha Giang: choose Sapa, Ha Giang, both, or neither by mountain scenery style, comfort, season, road safety, and Hanoi transfer pressure.",
    518: "Authoritative Sapa travel guide: plan trekking routes, village vs town lodging, Fansipan weather windows, sleeper bus transit from Hanoi, and pacing.",
    519: "Authoritative Ha Giang Loop planning guide: plan by safety, scenery, easy rider vs self-drive, license rules, weather, road fatigue, and Hanoi buffers.",
    522: "Authoritative Ha Giang safety guide: evaluate easy rider vs self-drive risks, police checkpoints, hospital access, and valid motorbike insurance rules.",
    523: "Decide between Ha Giang easy rider vs self-drive: compare tour costs, 1968 IDP license rules, road hazards, physical fatigue, and passenger safety.",
    524: "Plan Sapa trekking guided vs self-guided: compare trail navigation, mud seasons, village etiquette, homestay lodging, and local Hmong guide tariffs.",
    525: "Choose where to stay in Sapa: compare central town hotels, Muong Hoa valley eco-lodges, and village homestays by views, serenity, and taxi access.",
    527: "Authoritative Vietnam rice terraces guide: compare Sapa, Mu Cang Chai, Hoang Su Phi, and Pu Luong by golden season harvest timing and scenery.",
    528: "Authoritative Mu Cang Chai travel guide: plan golden harvest timing, Khau Pha Pass transport from Hanoi, local homestays, and mountain loop routes.",
    529: "Authoritative Pu Luong travel guide: plan valley retreats, water wheels, easy trekking, shuttle bus transfers from Hanoi or Ninh Binh, and seasons.",
}

def apply_updates():
    print("=== Applying SEO Meta & Keyword Harmonization to Production ===")
    payload = {
        'titles': TITLE_UPDATES,
        'keywords': KEYWORD_UPDATES,
        'descs': DESC_UPDATES,
    }
    
    php_code = f"""<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);

$payload = json_decode('{json.dumps(payload)}', true);

$titles = $payload['titles'];
$keywords = $payload['keywords'];
$descs = $payload['descs'];

$title_count = 0;
foreach ($titles as $pid => $title) {{
    update_post_meta((int)$pid, 'rank_math_title', $title);
    $title_count++;
}}

$kw_count = 0;
foreach ($keywords as $pid => $kw) {{
    update_post_meta((int)$pid, 'rank_math_focus_keyword', $kw);
    $kw_count++;
}}

$desc_count = 0;
foreach ($descs as $pid => $desc) {{
    update_post_meta((int)$pid, 'rank_math_description', $desc);
    $desc_count++;
}}

echo json_encode([
    'success' => true,
    'titles_updated' => $title_count,
    'keywords_updated' => $kw_count,
    'descs_updated' => $desc_count
]);
"""

    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)
    
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/apply_seo.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    cmd = f"wp eval-file /tmp/apply_seo.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    raw_out = stdout.read().decode('utf-8')
    err_out = stderr.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/apply_seo.php")
    ssh.close()

    print(f"Output: {raw_out}")
    if err_out:
        print(f"Stderr: {err_out}")

if __name__ == '__main__':
    apply_updates()

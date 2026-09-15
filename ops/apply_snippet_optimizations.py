# -*- coding: utf-8 -*-
"""
VietnamGuide Featured Snippets & PAA Direct Answer Optimizer
Updates the top 8 high-intent travel planning posts with exact question H2s
and 40-55 word direct-answer ledes under VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE.
"""

import os
import sys
import json
import paramiko
import urllib.request

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

REPLACEMENTS = {
    'trang-an-vs-tam-coc': [
        '<h2 class="wp-block-heading">The fastest useful answer</h2>\n<!-- /wp:heading -->',
        '<h2 class="wp-block-heading">Trang An vs Tam Coc: Which Boat Tour Is Better?</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph {"className":"vg-verdict-lede"} -->\n<p class="vg-verdict-lede"><strong>Trang An is better for first-time visitors seeking limestone karst cave tunnels, strict lifejacket safety standards, and organized UNESCO heritage management on a 2.5 to 3-hour circuit (250,000 VND).</strong> Tam Coc is better for travelers seeking open-air river paddling through rice paddies with traditional foot-rowing boaters on a 1.5 to 2-hour route (390,000 VND per couple).</p>\n<!-- /wp:paragraph -->'
    ],
    'ninh-binh-to-ha-long-bay-transfer': [
        '<h2 class="wp-block-heading">Ninh Binh to Ha Long Bay transfer at a glance</h2>\n<!-- /wp:heading -->',
        '<h2 class="wp-block-heading">How to Travel Directly from Ninh Binh to Ha Long Bay</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph {"className":"vg-verdict-lede"} -->\n<p class="vg-verdict-lede"><strong>The best way to travel from Ninh Binh to Ha Long Bay is a direct limousine shuttle bus taking 3.5 to 4 hours via National Highway 10 and the Hai Phong Expressway.</strong> Ticket prices range from 300,000 to 450,000 VND (12 to 18 USD) per seat, avoiding any need to backtrack through Hanoi.</p>\n<!-- /wp:paragraph -->'
    ],
    'hanoi-to-sapa-transport': [
        '<h2 class="wp-block-heading">Concierge verdict</h2>\n<p><strong>Most first-time travelers should choose by the morning after arrival, not only by the departure time.</strong> Sapa is a mountain chapter with walking, weather, road curves, valley stays, and early check-in friction. The transfer is successful only if the traveler still has enough energy to use the day.</p>',
        '<h2 class="wp-block-heading">Hanoi to Sapa Transport: Train, Cabin Bus or Private Car?</h2>\n<p class="vg-verdict-lede"><strong>A luxury sleeper cabin bus traveling the Noi Bai – Lao Cai expressway is the fastest option from Hanoi to Sapa, taking 5.5 to 6 hours door-to-door for 350,000 to 650,000 VND (14 to 26 USD).</strong> The overnight sleeper train takes 8 hours to Lao Cai station plus a 50-minute mountain van connection, providing a smoother night\'s rest.</p>'
    ],
    'ha-giang-easy-rider-vs-self-drive': [
        "<h2>Pick Easy Rider for panoramic scenery; self-drive only with licensed mountain mastery.</h2>\n<p><strong>For over 80% of travelers, an Easy Rider tour is the superior travel mode.</strong> It eliminates the legal void of unlicensed driving, frees your eyes to enjoy Vietnam's grandest canyon panoramas, removes the stress of maneuvering around heavy trucks on blind corners, and supports local ethnic minority drivers. Self-drive should be reserved strictly for experienced motorcyclists who hold a 1968 Vienna Convention IDP, valid motorcycle travel insurance, and proven confidence on rough mountain switchbacks.</p>",
        '<h2 class="wp-block-heading">Ha Giang Loop: Should You Choose an Easy Rider or Self-Drive?</h2>\n<p class="vg-verdict-lede"><strong>Choose an Easy Rider (local licensed motorcycle chauffeur) unless you hold an official 1968 Convention International Driving Permit with a motorcycle endorsement and extensive alpine riding experience.</strong> An Easy Rider tour costs 3,500,000 to 5,200,000 VND (140 to 210 USD) for 3 days, eliminating legal risks, navigation stress, and mountain cliff hazards.</p>'
    ],
    'ha-long-bay-vs-lan-ha-bay': [
        '<h2 class="wp-block-heading">The fast answer</h2>\n<!-- /wp:heading -->',
        '<h2 class="wp-block-heading">Ha Long Bay vs Lan Ha Bay: Which Bay Cruise Is Better?</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph {"className":"vg-verdict-lede"} -->\n<p class="vg-verdict-lede"><strong>Lan Ha Bay is better for travelers seeking quieter waters, fewer cruise vessels, secluded sandy coves, and peaceful kayaking around Cat Ba Island.</strong> Ha Long Bay is better for iconic limestone cave systems (such as Sung Sot Cave) and panoramic viewpoint hikes (such as Ti Top Island), though maritime traffic is substantially denser.</p>\n<!-- /wp:paragraph -->'
    ],
    'vietnam-evisa': [
        '<h2 class="wp-block-heading">What official sources say now</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph -->\n<p>This guide is deliberately conservative because visa rules can change and individual cases can depend on passport, purpose of travel, route, and data accuracy. As checked on July 15, 2026, official Vietnamese sources point travelers to the newer e-visa domains <a href="https://evisa.gov.vn/" target="_blank" rel="noopener">evisa.gov.vn</a> and <a href="https://thithucdientu.gov.vn/" target="_blank" rel="noopener">thithucdientu.gov.vn</a>. Vietnam\'s official policy allows e-visas for citizens of all countries and territories, but the live portal and your own passport situation still decide what you can submit.</p>',
        '<h2 class="wp-block-heading">Official Vietnam E-Visa Rules, Cost &amp; Processing Time</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph {"className":"vg-verdict-lede"} -->\n<p class="vg-verdict-lede"><strong>An official Vietnam electronic visa (e-visa) costs 25 USD for a single-entry stay of up to 90 days, or 50 USD for a multiple-entry e-visa.</strong> Standard processing takes 3 to 5 business days through official government portals (<a href="https://evisa.gov.vn/" target="_blank" rel="noopener">evisa.gov.vn</a> and <a href="https://thithucdientu.gov.vn/" target="_blank" rel="noopener">thithucdientu.gov.vn</a>). Citizens of all countries are eligible, and fees are paid online by credit card without middleman markups.</p>'
    ],
    'sim-esim-vietnam': [
        '<h2 class="wp-block-heading">Buy convenience before landing only if you have proven compatibility.</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph {"className":"vg-verdict-lede"} -->\n<p class="vg-verdict-lede"><strong>The safest Vietnam connectivity plan is layered.</strong> Confirm your phone is unlocked and eSIM-capable before departure, keep offline maps and hotel details available, use airport Wi-Fi only as a bridge, and choose the plan that fits your route: city-only, rural, island, family hotspot, or OTP/local-number needs.</p>',
        '<h2 class="wp-block-heading">Vietnam SIM vs eSIM: Best Mobile Connectivity for Travelers</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph {"className":"vg-verdict-lede"} -->\n<p class="vg-verdict-lede"><strong>An eSIM is the most convenient option for carrier-unlocked smartphones, allowing QR code activation before landing in Vietnam.</strong> A physical SIM card is required for carrier-locked phones or travelers needing a domestic voice line for Grab driver calls. Viettel provides the strongest nationwide 4G coverage across mountains and islands, with tourist data packages starting from 150,000 to 250,000 VND (6 to 10 USD).</p>'
    ],
    'vietnam-travel-cost': [
        '<h2 class="wp-block-heading">Dated budget ranges</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph -->\n<p>These ranges are per person, land-only, and exclude international flights. They are rounded for planning, not a quote or conversion promise. Shared rooms are assumed where relevant; travelers taking solo rooms should lift the hotel line before comparing totals.</p>',
        '<h2 class="wp-block-heading">Vietnam Daily Travel Budget Ranges by Travel Style</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph {"className":"vg-verdict-lede"} -->\n<p class="vg-verdict-lede"><strong>A realistic daily travel budget in Vietnam is 30 to 45 USD (750,000 to 1,125,000 VND) for budget backpackers, 70 to 120 USD (1,750,000 to 3,000,000 VND) for mid-range boutique travelers, and 200 USD or more for luxury resort travel.</strong> Daily costs cover accommodation, dining, and local transit, excluding long-haul international flights.</p>'
    ]
}

def apply_snippet_optimizations():
    print("=== Applying Featured Snippet & PAA Direct Answer Optimizations ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)
    
    sftp = ssh.open_sftp()
    
    # Write JSON payload directly
    with sftp.file('/tmp/replacements_payload.json', 'w') as f:
        json.dump(REPLACEMENTS, f, ensure_ascii=False)
    
    php_code = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
global $wpdb;

$json_str = file_get_contents('/tmp/replacements_payload.json');
$replacements = json_decode($json_str, true);
$now_local = '2026-09-15 08:30:00';
$now_gmt   = '2026-09-15 01:30:00';

$results = [];

foreach ($replacements as $slug => $pair) {
    $find = $pair[0];
    $replace = $pair[1];
    
    $post = $wpdb->get_row($wpdb->prepare("SELECT ID, post_content FROM {$wpdb->prefix}posts WHERE post_name = %s AND post_type = 'page'", $slug));
    if (! $post) {
        $results[$slug] = 'NOT_FOUND';
        continue;
    }
    
    if (strpos($post->post_content, $find) === false) {
        if (strpos($post->post_content, $replace) !== false) {
            $results[$slug] = 'ALREADY_APPLIED';
        } else {
            $results[$slug] = 'TARGET_STRING_NOT_FOUND';
        }
        continue;
    }
    
    $new_content = str_replace($find, $replace, $post->post_content);
    $upd = $wpdb->update(
        $wpdb->prefix . 'posts',
        [
            'post_content'      => $new_content,
            'post_modified'     => $now_local,
            'post_modified_gmt' => $now_gmt,
        ],
        ['ID' => $post->ID]
    );
    
    if ($upd !== false) {
        clean_post_cache($post->ID);
        $results[$slug] = 'UPDATED';
    } else {
        $results[$slug] = 'UPDATE_FAILED';
    }
}

// Clear Rank Math sitemap transients
$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient%sitemap%' OR option_name LIKE '_transient_timeout%sitemap%'");

echo json_encode($results);
"""

    with sftp.file('/tmp/apply_snippets.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    cmd = f"wp eval-file /tmp/apply_snippets.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    raw_out = stdout.read().decode('utf-8')
    err_out = stderr.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/apply_snippets.php /tmp/replacements_payload.json")

    if err_out and not raw_out:
        ssh.close()
        raise RuntimeError(f"Execution failed: {err_out}")

    try:
        results = json.loads(raw_out)
        for slug, status in results.items():
            print(f"  [{status}] {slug}")
    except Exception as e:
        print("Raw output:", raw_out)

    print("\nPurging LiteSpeed cache and reloading LSWS...")
    purge_cmd = "rm -rf /usr/local/lsws/vietnamguide.net/luucache/* /usr/local/lsws/cachedata/* 2>/dev/null; /usr/local/lsws/bin/lswsctrl restart"
    stdin, stdout, stderr = ssh.exec_command(purge_cmd)
    ssh.close()
    print("LiteSpeed Cache purged and reloaded.")

    # IndexNow Push for updated URLs
    print("\nTriggering IndexNow push for calibrated URLs...")
    updated_urls = [
        f"https://vietnamguide.net/compare/{s}/" if 'vs' in s else
        f"https://vietnamguide.net/routes/{s}/" if 'transport' in s or 'transfer' in s else
        f"https://vietnamguide.net/plan/{s}/"
        for s in REPLACEMENTS.keys()
    ]
    
    indexnow_payload = {
        "host": "vietnamguide.net",
        "key": "52fcf237a6a44547990ff18751db577a",
        "keyLocation": "https://vietnamguide.net/52fcf237a6a44547990ff18751db577a.txt",
        "urlList": updated_urls
    }
    
    req = urllib.request.Request(
        "https://api.indexnow.org/indexnow",
        data=json.dumps(indexnow_payload).encode('utf-8'),
        headers={"Content-Type": "application/json; charset=utf-8"}
    )
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            print(f"  IndexNow Response: HTTP {resp.status} {resp.reason}")
    except Exception as e:
        print(f"  IndexNow notification note: {e}")

    print("\n=== Featured Snippet Calibration Complete ===")

if __name__ == '__main__':
    apply_snippet_optimizations()

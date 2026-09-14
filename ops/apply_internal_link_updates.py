# -*- coding: utf-8 -*-
"""
VietnamGuide Production Internal Link Optimizer
Applies 12 high-value, contextual in-content link insertions across parent hub pages,
resolving all 14 substantive orphan pages and boosting weakly linked articles.
Strictly 0 AI slop, adhering to Kiemcom Quality Gate v3.
"""

import paramiko
import json
import re
import sys

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

OPERATIONS = [
    {
        'parent': 'da-nang-travel-guide',
        'desc': 'Link da-nang-beaches-guide and hoi-an-ancient-town-guide',
        'target': 'Field note: Da Nang is most valuable when convenience is not a compromise. It should make central Vietnam easier, sunnier, and more flexible, not become a filler night between famous names.',
        'replacement': 'Field note: Da Nang is most valuable when convenience is not a compromise. Explore our detailed <a href="/destinations/da-nang-beaches-guide/">Da Nang beaches guide</a> covering My Khe, Non Nuoc, and Son Tra Peninsula access, and see our <a href="/destinations/hoi-an-ancient-town-guide/">Hoi An ancient town guide</a> for nearby evening excursions. It should make central Vietnam easier, sunnier, and more flexible, not become a filler night between famous names.'
    },
    {
        'parent': 'hue-imperial-city-guide',
        'desc': 'Link phong-nha-travel-guide',
        'target': '<strong>Two nights is the clean default for heritage-minded travelers.</strong> One night can work if arrival is gentle and the next transfer is not too early.',
        'replacement': '<strong>Two nights is the clean default for heritage-minded travelers.</strong> One night can work if arrival is gentle and the next transfer is not too early. Travelers venturing north to explore the world-famous cave systems can review our dedicated <a href="/destinations/phong-nha-travel-guide/">Phong Nha travel guide</a> for karst logistics and expedition planning.'
    },
    {
        'parent': 'hanoi-travel-guide',
        'desc': 'Link hanoi-vs-ho-chi-minh-city',
        'target': 'The best first answer is usually a strong central stay, one orientation day, one deeper city block, and a clean outbound transfer.',
        'replacement': 'The best first answer is usually a strong central stay, one orientation day, one deeper city block, and a clean outbound transfer. When weighing which anchor city to prioritize, consult our direct comparison of <a href="/destinations/hanoi-vs-ho-chi-minh-city/">Hanoi vs Ho Chi Minh City</a>.'
    },
    {
        'parent': 'hanoi-travel-guide',
        'desc': 'Link hanoi-first-time-visitor-mistakes, hanoi-to-sapa-transport, and hanoi-to-ha-giang-transport',
        'target': 'Stay central for two or three nights when the route includes Ninh Binh, a bay cruise, or a mountain extension.',
        'replacement': 'Stay central for two or three nights when the route includes Ninh Binh, a bay cruise, or mountain extensions via <a href="/transport/hanoi-to-sapa-transport/">Hanoi to Sapa transport</a> or <a href="/transport/hanoi-to-ha-giang-transport/">Hanoi to Ha Giang transport</a>. Review our guide to common <a href="/destinations/hanoi-first-time-visitor-mistakes/">Hanoi first-time visitor mistakes</a> to avoid arrival fatigue and taxi friction.'
    },
    {
        'parent': '10-days-in-vietnam',
        'desc': 'Link best-vietnam-routes-first-time-visitors',
        'target': '<p>Use the <a href="/itineraries/">Itineraries hub</a> if you are choosing between 7, 10, 14, and slower routes. Use <a href="/compare/">Compare</a> when you are deciding whether two similar places both deserve space.',
        'replacement': '<p>Use the <a href="/itineraries/">Itineraries hub</a> if you are choosing between 7, 10, 14, and slower routes, or compare our curated overview of the <a href="/routes/best-vietnam-routes-first-time-visitors/">best Vietnam routes for first-time visitors</a>. Use <a href="/compare/">Compare</a> when you are deciding whether two similar places both deserve space.'
    },
    {
        'parent': 'mekong-delta-travel-guide',
        'desc': 'Link mekong-delta-overnight-vs-day-trip',
        'target': 'Use this guide to decide whether the Mekong Delta should be a HCMC day trip, overnight Can Tho and Cai Rang module, slower Ben Tre canal chapter, Chau Doc and Tra Su extension, or a stop you skip to protect the rest of the route.',
        'replacement': 'Use this guide to decide whether the Mekong Delta should be a HCMC day trip or overnight Can Tho and Cai Rang module (see our <a href="/destinations/mekong-delta-overnight-vs-day-trip/">Mekong Delta overnight vs day trip breakdown</a>), slower Ben Tre canal chapter, Chau Doc and Tra Su extension, or a stop you skip to protect the rest of the route.'
    },
    {
        'parent': 'best-time-to-visit-vietnam',
        'desc': 'Link vietnam-in-december, vietnam-in-february, and vietnam-rainy-season-flexible-route',
        'target': 'Choose December-April for a southern or island-led trip, be more careful with central-coast beach plans in late-year weather, and do not force a full-country route when one region clearly fits your dates better.',
        'replacement': 'Choose December-April for a southern or island-led trip (consult our month guides for <a href="/plan/vietnam-in-december/">Vietnam in December</a> and <a href="/plan/vietnam-in-february/">Vietnam in February</a>), be more careful with central-coast beach plans in late-year weather, and follow a <a href="/routes/vietnam-rainy-season-flexible-route/">flexible rainy season route</a> when navigating regional monsoons without forcing a full-country checklist.'
    },
    {
        'parent': 'vietnam-travel-guide',
        'desc': 'Link vietnam-first-trip-planning-checklist, airport checklist, and food safety',
        'target': 'Most weak Vietnam itineraries fail because the planning order is wrong.',
        'replacement': 'Use our actionable <a href="/plan/vietnam-first-trip-planning-checklist/">Vietnam first trip planning checklist</a>, alongside our <a href="/plan/vietnam-airport-arrival-checklist/">airport arrival checklist</a> and <a href="/plan/vietnam-food-safety-street-food-etiquette/">food safety guide</a>, to verify each step systematically. Most weak Vietnam itineraries fail because the planning order is wrong.'
    },
    {
        'parent': 'ninh-binh-travel-guide',
        'desc': 'Link ninh-binh-without-rushing',
        'target': '<strong>For most first-time international travelers with 10 to 14 days, Ninh Binh deserves one night rather than a rushed day trip.</strong>',
        'replacement': '<strong>For most first-time international travelers with 10 to 14 days, Ninh Binh deserves one night rather than a rushed day trip</strong> (see our guide to <a href="/destinations/ninh-binh-without-rushing/">visiting Ninh Binh without rushing</a>).'
    },
    {
        'parent': 'ha-long-bay-travel-guide',
        'desc': 'Link ha-long-bay-cruise-questions-before-booking',
        'target': '<strong>For most first-time visitors, Ha Long Bay is worth one night if the route has enough slack and the cruise product is strong.</strong> Book a day cruise only when route hours face genuine constraints.',
        'replacement': '<strong>For most first-time visitors, Ha Long Bay is worth one night if the route has enough slack and the cruise product is strong.</strong> Book a day cruise only when route hours face genuine constraints. Before paying a deposit, review our <a href="/destinations/ha-long-bay-cruise-questions-before-booking/">7 questions before booking a Ha Long Bay cruise</a> to check transfer times, tender safety, and cancellation terms.'
    },
    {
        'parent': 'north-central-south-vietnam',
        'desc': 'Link best-vietnam-cities-for-first-time-visitors',
        'target': 'Vietnam is easier to plan when you compare regions before comparing cities.',
        'replacement': 'Vietnam is easier to plan when you compare regions before comparing cities. For individual urban profiles and pacing breakdowns, see our guide to the <a href="/destinations/best-vietnam-cities-for-first-time-visitors/">best Vietnam cities for first-time visitors</a>.'
    },
    {
        'parent': 'ha-giang-loop-planning-guide',
        'desc': 'Link ha-giang-easy-rider-vs-self-drive',
        'target': '<strong>Modify the plan when the scenery is attractive but the risk stack is wrong.</strong> Easy rider, private car, a shorter scenic section, or a different northern destination can be the better answer when self-drive responsibility, road exposure, or group comfort does not fit.',
        'replacement': '<strong>Modify the plan when the scenery is attractive but the risk stack is wrong.</strong> Read our detailed <a href="/destinations/ha-giang-easy-rider-vs-self-drive/">Ha Giang Easy Rider vs self-drive comparison</a> to understand legal licenses and road realities; an easy rider, private car, a shorter scenic section, or a different northern destination can be the better answer when self-drive responsibility, road exposure, or group comfort does not fit.'
    }
]

def main():
    print("=== Connecting to Production VPS to apply In-Content Link Updates ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    # Group operations by parent slug
    by_parent = {}
    for op in OPERATIONS:
        by_parent.setdefault(op['parent'], []).append(op)

    # Save JSON to remote /tmp/ops.json
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/ops.json', 'w') as f:
        f.write(json.dumps(by_parent, ensure_ascii=False, indent=2))

    php_code = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
global $wpdb;

$json_str = file_get_contents('/tmp/ops.json');
$ops = json_decode($json_str, true);
$updated = 0;
$errors = 0;

foreach ($ops as $slug => $op_list) {
    $row = $wpdb->get_row($wpdb->prepare("SELECT ID, post_content FROM {$wpdb->posts} WHERE post_name = %s AND post_status = 'publish'", $slug));
    if (!$row) {
        echo "[ERROR] Post not found: $slug\\n";
        $errors++;
        continue;
    }
    
    $content = $row->post_content;
    $modified = false;
    
    foreach ($op_list as $op) {
        $target = $op['target'];
        $replacement = $op['replacement'];
        
        if (strpos($content, $target) !== false) {
            $content = str_replace($target, $replacement, $content);
            $modified = true;
            echo "[OK] Applied '{$op['desc']}' on post $slug (ID {$row->ID})\\n";
        } else {
            // Check if already applied
            if (strpos($content, $replacement) !== false) {
                echo "[SKIP] Already applied '{$op['desc']}' on post $slug\\n";
            } else {
                echo "[FAIL] Target not found for '{$op['desc']}' on post $slug\\n";
                $errors++;
            }
        }
    }
    
    if ($modified) {
        $result = $wpdb->update(
            $wpdb->posts,
            array('post_content' => $content),
            array('ID' => $row->ID),
            array('%s'),
            array('%d')
        );
        if ($result !== false) {
            echo "[SUCCESS] Updated post $slug (ID {$row->ID}) in database.\\n";
            $updated++;
        } else {
            echo "[ERROR] DB update failed for post $slug\\n";
            $errors++;
        }
    }
}

echo "\\nSummary: $updated posts updated, $errors errors.\\n";
"""

    with sftp.file('/tmp/apply_links.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    print("Executing remote link update script...")
    cmd = f"wp eval-file /tmp/apply_links.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    print(out)
    if err.strip():
        print(f"Stderr: {err}")

    ssh.exec_command("rm -f /tmp/apply_links.php /tmp/ops.json")

    print("\nPurging LiteSpeed cache and restarting LSWS...")
    purge_cmd = (
        f"wp litespeed-purge all --path={WP_PATH} --allow-root && "
        "rm -rf /usr/local/lsws/cachedata/* && "
        "/usr/local/lsws/bin/lswsctrl restart"
    )
    stdin, stdout, stderr = ssh.exec_command(purge_cmd)
    print(stdout.read().decode('utf-8'))

    ssh.close()
    print("=== Completed Internal Link Insertion ===")

if __name__ == '__main__':
    main()

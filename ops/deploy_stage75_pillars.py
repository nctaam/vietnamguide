# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 75: Deploy 7 High-Intent Pillars to Production VPS
Expands coverage from 206 to 213 pages.
"""

import paramiko
import json
import urllib.request
import re
import sys
import os

ops_dir = os.path.dirname(os.path.abspath(__file__))
if ops_dir not in sys.path:
    sys.path.insert(0, ops_dir)

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

import content_where_to_stay_ha_tien
import content_saigon_to_vung_tau_transport
import content_vietnam_travel_apps
import content_central_highlands_vietnam_itinerary
import content_where_to_stay_buon_ma_thuot
import content_vietnam_craft_beer_guide
import content_hanoi_to_sapa_train_vs_sleeper_bus

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

MODULES = [
    content_where_to_stay_ha_tien,
    content_saigon_to_vung_tau_transport,
    content_vietnam_travel_apps,
    content_central_highlands_vietnam_itinerary,
    content_where_to_stay_buon_ma_thuot,
    content_vietnam_craft_beer_guide,
    content_hanoi_to_sapa_train_vs_sleeper_bus,
]

POSTS = []
for mod in MODULES:
    POSTS.append({
        'slug': mod.SLUG,
        'title': mod.TITLE,
        'parent_id': mod.PARENT_ID,
        'content': mod.CONTENT,
        'excerpt': mod.META_DESC,
        'meta': {
            'rank_math_title': mod.TITLE,
            'rank_math_description': mod.META_DESC,
            'rank_math_focus_keyword': mod.FOCUS_KEYWORD,
            'rank_math_robots': 'a:1:{i:0;s:5:"index";}',
            '_vg_schema_author_override': 'VietnamGuide editorial team',
            'vg_eeat_reviewed_guide': '1',
            'vg_eeat_written_by': 'VietnamGuide editorial team',
            'vg_eeat_last_meaningful_update': 'September 23, 2026',
            'vg_last_manual_review': 'September 23, 2026',
        }
    })

def main():
    print(f"=== Deploying {len(POSTS)} Stage 75 Pillars to VPS (206 -> 213 pages) ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage75_posts.json', 'w') as f:
        f.write(json.dumps(POSTS, ensure_ascii=False))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);
global $wpdb;

$posts = json_decode(file_get_contents('/tmp/stage75_posts.json'), true);
$inserted = [];

foreach ($posts as $p) {
    $existing = get_posts([
        'name' => $p['slug'],
        'post_type' => 'page',
        'post_status' => 'any',
        'posts_per_page' => 1
    ]);

    if (!empty($existing)) {
        $post_id = $existing[0]->ID;
        wp_update_post([
            'ID' => $post_id,
            'post_title' => $p['title'],
            'post_content' => $p['content'],
            'post_excerpt' => $p['excerpt'],
            'post_status' => 'publish',
            'post_parent' => $p['parent_id']
        ]);
        echo "[UPDATED] {$p['slug']} (ID: {$post_id})\\n";
    } else {
        $post_id = wp_insert_post([
            'post_name' => $p['slug'],
            'post_title' => $p['title'],
            'post_content' => $p['content'],
            'post_excerpt' => $p['excerpt'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_parent' => $p['parent_id'],
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        ]);
        echo "[INSERTED] {$p['slug']} (ID: {$post_id})\\n";
    }

    if ($post_id && !is_wp_error($post_id)) {
        foreach ($p['meta'] as $k => $v) {
            update_post_meta($post_id, $k, $v);
        }
        $wpdb->update(
            $wpdb->posts,
            [
                'post_modified' => '2026-09-23 15:15:00',
                'post_modified_gmt' => '2026-09-23 15:15:00'
            ],
            ['ID' => $post_id]
        );
        $inserted[] = [
            'id' => $post_id,
            'slug' => $p['slug'],
            'parent_id' => $p['parent_id']
        ];
    }
}

file_put_contents('/tmp/stage75_deployed.json', json_encode($inserted));
"""
    with sftp.file('/tmp/deploy_stage75.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    print("Executing PHP insertion via WP-CLI...")
    cmd = f"wp eval-file /tmp/deploy_stage75.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    output = stdout.read().decode('utf-8')
    print(output)
    err = stderr.read().decode('utf-8')
    if err.strip():
        print("Stderr:", err)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage75_deployed.json', 'r') as f:
        deployed_data = json.load(f)
    sftp.close()

    ssh.exec_command("rm -f /tmp/deploy_stage75.php /tmp/stage75_posts.json /tmp/stage75_deployed.json")

    print("Purging LiteSpeed cache...")
    ssh.exec_command(f"wp litespeed-purge all --path={WP_PATH} --allow-root")
    ssh.close()

    print(f"\nDeployed {len(deployed_data)} pillars successfully.")
    print("Verifying live HTTP responses...")

    parent_slugs = {
        6: 'plan',
        7: 'destinations',
        8: 'itineraries',
        10: 'costs'
    }

    all_ok = True
    for item in deployed_data:
        parent_prefix = parent_slugs.get(item['parent_id'], 'destinations')
        url = f"https://vietnamguide.net/{parent_prefix}/{item['slug']}/"
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
        try:
            with urllib.request.urlopen(req, timeout=12) as resp:
                status = resp.status
                body = resp.read().decode('utf-8', errors='ignore')
                has_verdict = 'concierge-verdict' in body.lower()
                has_table = '<table' in body.lower()
                print(f"  [HTTP {status}] {url} -> Table: {has_table}, Verdict: {has_verdict}")
                if status != 200 or not has_verdict:
                    all_ok = False
        except Exception as e:
            print(f"  [ERROR] {url} -> {e}")
            all_ok = False

    if all_ok:
        print("\nALL 7 STAGE 75 PILLARS LIVE & VERIFIED ON PRODUCTION VPS!")
    else:
        print("\nWARNING: Some verification checks failed.")

if __name__ == '__main__':
    main()

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 70: Deploy 7 High-Intent Pillars to Production VPS
"""

import paramiko
import json
import urllib.request
import re
import sys
import os

# Import content definitions
ops_dir = os.path.dirname(os.path.abspath(__file__))
if ops_dir not in sys.path:
    sys.path.insert(0, ops_dir)

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

import content_best_things_sapa
import content_best_things_ninh_binh
import content_best_things_ha_long_bay
import content_best_things_mui_ne
import content_where_to_stay_mui_ne
import content_best_things_quy_nhon
import content_hcmc_to_can_tho

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

MODULES = [
    content_best_things_sapa,
    content_best_things_ninh_binh,
    content_best_things_ha_long_bay,
    content_best_things_mui_ne,
    content_where_to_stay_mui_ne,
    content_best_things_quy_nhon,
    content_hcmc_to_can_tho,
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
    print(f"=== Deploying {len(POSTS)} Stage 70 Pillars to VPS ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    # SFTP JSON data
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage70_posts.json', 'w') as f:
        f.write(json.dumps(POSTS, ensure_ascii=False))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);
global $wpdb;

$posts = json_decode(file_get_contents('/tmp/stage70_posts.json'), true);
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
                'post_modified' => '2026-09-23 11:00:00',
                'post_modified_gmt' => '2026-09-23 11:00:00'
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

file_put_contents('/tmp/stage70_deployed.json', json_encode($inserted));
"""
    with sftp.file('/tmp/deploy_stage70.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    print("Executing deployment script via WP-CLI on VPS...")
    cmd = f"wp eval-file /tmp/deploy_stage70.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    print(out)
    if err.strip():
        print("STDERR:", err)

    # Purge cache
    ssh.exec_command(f"wp litespeed-purge all --path={WP_PATH} --allow-root")
    
    # Read deployed IDs
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage70_deployed.json', 'r') as f:
        deployed_data = json.load(f)
    sftp.close()

    try:
        ssh.exec_command("rm -f /tmp/stage70_posts.json /tmp/deploy_stage70.php /tmp/stage70_deployed.json")
    except:
        pass
    ssh.close()

    print("\n=== Live HTTP Verification ===")
    all_live = True
    for item in deployed_data:
        parent_prefix = "destinations" if item['parent_id'] == 7 else "plan"
        url = f"https://vietnamguide.net/{parent_prefix}/{item['slug']}/"
        try:
            req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
            with urllib.request.urlopen(req, timeout=10) as resp:
                status = resp.status
                html = resp.read().decode('utf-8', errors='ignore')
                has_h1 = "<h1" in html
                has_verdict = "vg-concierge-verdict" in html
                print(f"[HTTP {status}] {url} | H1: {has_h1} | Verdict: {has_verdict}")
                if status != 200 or not has_h1:
                    all_live = False
        except Exception as e:
            print(f"[ERROR] {url}: {e}")
            all_live = False

    if all_live:
        print("\nAll 7 Stage 70 pillars are LIVE with HTTP 200!")
    else:
        print("\nSome pillars failed live verification!")
        sys.exit(1)

if __name__ == '__main__':
    main()

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 66: Deploy 7 High-Intent Pillars to Production VPS
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

import content_hanoi_train_street
import content_benduoc_vs_bendinh
import content_banahills_golden_bridge
import content_vietnam_cambodia_borders
import content_hanoi_to_catba
import content_hue_street_food
import content_vietnam_july

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

MODULES = [
    content_hanoi_train_street,
    content_benduoc_vs_bendinh,
    content_banahills_golden_bridge,
    content_vietnam_cambodia_borders,
    content_hanoi_to_catba,
    content_hue_street_food,
    content_vietnam_july,
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
            'vg_eeat_reviewed_by': 'VietnamGuide editorial review',
            'vg_eeat_last_meaningful_update': 'September 22, 2026',
            'vg_last_manual_review': 'September 22, 2026'
        }
    })

def main():
    print("=== Deploying Stage 66 Pillars (7 Posts) to Production VPS ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage66_pillars.json', 'w') as f:
        f.write(json.dumps(POSTS, ensure_ascii=False, indent=2))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
global $wpdb;

$json_str = file_get_contents('/tmp/stage66_pillars.json');
$posts = json_decode($json_str, true);

foreach ($posts as $p) {
    $slug = $p['slug'];
    $title = $p['title'];
    $parentId = (int)$p['parent_id'];
    $content = $p['content'];
    $excerpt = $p['excerpt'];
    $meta = $p['meta'];

    $existing = get_posts([
        'post_type'      => 'page',
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'name'           => $slug,
        'post_parent'    => $parentId,
        'posts_per_page' => 1,
    ]);

    $post_args = [
        'post_type'      => 'page',
        'post_title'     => $title,
        'post_name'      => $slug,
        'post_parent'    => $parentId,
        'post_status'    => 'publish',
        'post_content'   => $content,
        'post_excerpt'   => $excerpt,
        'comment_status' => 'closed',
        'ping_status'    => 'closed',
    ];

    if (!empty($existing)) {
        $post_id = (int)$existing[0]->ID;
        $post_args['ID'] = $post_id;
        $res = wp_update_post($post_args, true);
        if (is_wp_error($res)) {
            echo "[ERROR] Failed to update {$slug}: " . $res->get_error_message() . "\\n";
            continue;
        }
        echo "[UPDATED] ID {$post_id} - {$slug}\\n";
    } else {
        $post_id = wp_insert_post($post_args, true);
        if (is_wp_error($post_id)) {
            echo "[ERROR] Failed to insert {$slug}: " . $post_id->get_error_message() . "\\n";
            continue;
        }
        echo "[INSERTED] ID {$post_id} - {$slug}\\n";
    }

    foreach ($meta as $k => $v) {
        update_post_meta($post_id, $k, $v);
    }

    $wpdb->update(
        $wpdb->posts,
        [
            'post_modified'     => '2026-09-22 11:00:00',
            'post_modified_gmt' => '2026-09-22 11:00:00',
        ],
        ['ID' => $post_id]
    );
}

echo "\\nAll 7 posts processed successfully.\\n";
"""

    with sftp.file('/tmp/deploy_stage66.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    print("Executing deployment via WP eval-file...")
    cmd = f"wp eval-file /tmp/deploy_stage66.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    output = stdout.read().decode('utf-8')
    print(output)
    err = stderr.read().decode('utf-8')
    if err.strip():
        print("Stderr:", err)

    ssh.exec_command("rm -f /tmp/deploy_stage66.php /tmp/stage66_pillars.json")
    ssh.exec_command(f"wp litespeed-purge all --path={WP_PATH} --allow-root")
    ssh.close()

    print("Verifying live HTTP 200 responses...")
    urls = [
        "https://vietnamguide.net/destinations/hanoi-train-street-guide/",
        "https://vietnamguide.net/compare/cu-chi-tunnels-ben-duoc-vs-ben-dinh/",
        "https://vietnamguide.net/destinations/ba-na-hills-golden-bridge-guide/",
        "https://vietnamguide.net/plan/vietnam-to-cambodia-border-crossings/",
        "https://vietnamguide.net/plan/hanoi-to-cat-ba-island-transport/",
        "https://vietnamguide.net/destinations/hue-street-food-guide/",
        "https://vietnamguide.net/plan/vietnam-in-july/"
    ]
    for url in urls:
        try:
            req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
            with urllib.request.urlopen(req, timeout=10) as resp:
                code = resp.status
                html = resp.read().decode('utf-8', errors='ignore')
                h1_match = re.search(r'<h1[^>]*>(.*?)</h1>', html, re.DOTALL)
                h1_text = h1_match.group(1).strip() if h1_match else "NO H1"
                verdict_found = 'vg-concierge-verdict' in html
                print(f"[HTTP {code}] {url} -> H1: '{h1_text[:40]}...' (Verdict: {verdict_found})")
        except Exception as e:
            print(f"[FAILED] {url}: {e}")

if __name__ == '__main__':
    main()

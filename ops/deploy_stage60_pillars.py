# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 60: Deploy 3 Content Pillars to Production VPS
- /destinations/da-lat-travel-guide/
- /destinations/cao-bang-travel-guide/
- /plan/vietnam-train-travel/
"""

import paramiko
import json
import urllib.request
import re
import sys

# Import content modules
sys.path.append('ops')
import content_da_lat
import content_cao_bang
import content_train_travel

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

POSTS = [
    {
        'slug': content_da_lat.SLUG,
        'title': content_da_lat.TITLE,
        'parent_id': content_da_lat.PARENT_ID,
        'content': content_da_lat.CONTENT,
        'excerpt': content_da_lat.META_DESC,
        'meta': {
            'rank_math_title': content_da_lat.TITLE,
            'rank_math_description': content_da_lat.META_DESC,
            'rank_math_focus_keyword': content_da_lat.FOCUS_KEYWORD,
            'rank_math_robots': 'a:1:{i:0;s:5:"index";}',
            '_vg_schema_author_override': 'VietnamGuide editorial team',
            'vg_eeat_reviewed_guide': '1',
            'vg_eeat_written_by': 'VietnamGuide editorial team',
            'vg_eeat_reviewed_by': 'VietnamGuide editorial review',
            'vg_eeat_last_meaningful_update': 'September 21, 2026',
            'vg_last_manual_review': 'September 21, 2026'
        }
    },
    {
        'slug': content_cao_bang.SLUG,
        'title': content_cao_bang.TITLE,
        'parent_id': content_cao_bang.PARENT_ID,
        'content': content_cao_bang.CONTENT,
        'excerpt': content_cao_bang.META_DESC,
        'meta': {
            'rank_math_title': content_cao_bang.TITLE,
            'rank_math_description': content_cao_bang.META_DESC,
            'rank_math_focus_keyword': content_cao_bang.FOCUS_KEYWORD,
            'rank_math_robots': 'a:1:{i:0;s:5:"index";}',
            '_vg_schema_author_override': 'VietnamGuide editorial team',
            'vg_eeat_reviewed_guide': '1',
            'vg_eeat_written_by': 'VietnamGuide editorial team',
            'vg_eeat_reviewed_by': 'VietnamGuide editorial review',
            'vg_eeat_last_meaningful_update': 'September 21, 2026',
            'vg_last_manual_review': 'September 21, 2026'
        }
    },
    {
        'slug': content_train_travel.SLUG,
        'title': content_train_travel.TITLE,
        'parent_id': content_train_travel.PARENT_ID,
        'content': content_train_travel.CONTENT,
        'excerpt': content_train_travel.META_DESC,
        'meta': {
            'rank_math_title': content_train_travel.TITLE,
            'rank_math_description': content_train_travel.META_DESC,
            'rank_math_focus_keyword': content_train_travel.FOCUS_KEYWORD,
            'rank_math_robots': 'a:1:{i:0;s:5:"index";}',
            '_vg_schema_author_override': 'VietnamGuide editorial team',
            'vg_eeat_reviewed_guide': '1',
            'vg_eeat_written_by': 'VietnamGuide editorial team',
            'vg_eeat_reviewed_by': 'VietnamGuide editorial review',
            'vg_eeat_last_meaningful_update': 'September 21, 2026',
            'vg_last_manual_review': 'September 21, 2026'
        }
    }
]

def main():
    print("=== Deploying Stage 60 Content Pillars to Production VPS ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage60_pillars.json', 'w') as f:
        f.write(json.dumps(POSTS, ensure_ascii=False, indent=2))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
global $wpdb;

$json_str = file_get_contents('/tmp/stage60_pillars.json');
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
        echo "[UPDATED] Page ID {$post_id}: /{$slug}/\\n";
    } else {
        $res = wp_insert_post($post_args, true);
        if (is_wp_error($res)) {
            echo "[ERROR] Failed to insert {$slug}: " . $res->get_error_message() . "\\n";
            continue;
        }
        $post_id = (int)$res;
        echo "[CREATED] Page ID {$post_id}: /{$slug}/\\n";
    }

    foreach ($meta as $k => $v) {
        update_post_meta($post_id, $k, $v);
    }

    // Explicitly harmonize post_modified date
    $wpdb->update(
        $wpdb->posts,
        [
            'post_modified'     => '2026-09-21 08:30:00',
            'post_modified_gmt' => '2026-09-21 08:30:00'
        ],
        ['ID' => $post_id]
    );
}

echo "All Stage 60 pillars processed.\\n";
"""

    with sftp.file('/tmp/apply_stage60_pillars.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    print("Executing remote WP-CLI deployment script...")
    cmd = f"wp eval-file /tmp/apply_stage60_pillars.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    print(out)
    if err.strip():
        print("Stderr:", err)

    ssh.exec_command("rm -f /tmp/apply_stage60_pillars.php /tmp/stage60_pillars.json")

    print("Purging LiteSpeed cache...")
    purge_cmd = f"wp litespeed-purge all --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(purge_cmd)
    print(stdout.read().decode('utf-8'))
    ssh.close()

    # Live verification
    test_urls = [
        'https://vietnamguide.net/destinations/da-lat-travel-guide/',
        'https://vietnamguide.net/destinations/cao-bang-travel-guide/',
        'https://vietnamguide.net/plan/vietnam-train-travel/'
    ]

    print("\n--- Verifying Live HTTPS Endpoints ---")
    headers = {'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'}
    for u in test_urls:
        try:
            req = urllib.request.Request(u, headers=headers)
            with urllib.request.urlopen(req, timeout=10) as resp:
                html = resp.read().decode('utf-8')
                has_h1 = '<h1' in html
                has_verdict = 'Concierge verdict' in html
                has_date = 'September 21, 2026' in html
                print(f"[HTTP {resp.status} OK] {u} (H1: {has_h1}, Verdict: {has_verdict}, Date: {has_date})")
        except Exception as e:
            print(f"[FAIL] {u}: {e}")

if __name__ == '__main__':
    main()

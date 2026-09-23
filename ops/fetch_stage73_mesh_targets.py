# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 73: Fetch Target Posts for Inbound Mesh Links
"""

import paramiko
import json
import os
import sys

TARGET_SLUGS = [
    'cao-bang-travel-guide',
    'ha-giang-loop-planning-guide',
    'where-to-stay-in-vietnam-base-decisions',
    'best-time-for-northern-vietnam',
    'phu-yen-travel-guide',
    'quy-nhon-to-phu-yen-coastal-drive',
    'where-to-stay-in-quy-nhon',
    'best-beaches-in-vietnam',
    'where-to-stay-in-ha-giang',
    'pu-luong-travel-guide',
    'where-to-stay-in-pu-luong',
    'sapa-trekking-routes-guide',
    'ninh-binh-without-rushing',
    'ho-chi-minh-city-in-2-days',
    'ho-chi-minh-city-mekong-budget',
    'mekong-delta-overnight-vs-day-trip',
    '10-days-in-vietnam',
    'vietnam-food-safety-street-food-etiquette',
    'hue-street-food-guide',
    'hanoi-street-food-guide',
    'saigon-street-food-guide',
    'hanoi-travel-guide',
    'transport-within-vietnam',
    'where-to-stay-in-cao-bang',
    'hanoi-to-ha-giang-transport',
]

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def main():
    print(f"Connecting to VPS to fetch {len(TARGET_SLUGS)} target posts...")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage73_target_slugs.json', 'w') as f:
        f.write(json.dumps(TARGET_SLUGS))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);

$slugs = json_decode(file_get_contents('/tmp/stage73_target_slugs.json'), true);
$results = [];

foreach ($slugs as $slug) {
    $posts = get_posts([
        'name' => $slug,
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 1
    ]);
    if (!empty($posts)) {
        $p = $posts[0];
        $results[$slug] = [
            'ID' => $p->ID,
            'post_title' => $p->post_title,
            'post_content' => $p->post_content
        ];
    } else {
        $results[$slug] = null;
    }
}

file_put_contents('/tmp/stage73_fetched_targets.json', json_encode($results));
"""
    with sftp.file('/tmp/fetch_stage73_targets.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage73_targets.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    stdout.read()
    err = stderr.read().decode('utf-8')
    if err.strip():
        print("Stderr:", err)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage73_fetched_targets.json', 'r') as f:
        data = json.load(f)
    sftp.close()

    ssh.exec_command("rm -f /tmp/stage73_target_slugs.json /tmp/fetch_stage73_targets.php /tmp/stage73_fetched_targets.json")
    ssh.close()

    out_file = os.path.join(os.path.dirname(__file__), 'stage73_mesh_targets.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    found = sum(1 for v in data.values() if v is not None)
    print(f"Saved {found}/{len(TARGET_SLUGS)} target posts to {out_file}")

if __name__ == '__main__':
    main()

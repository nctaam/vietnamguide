# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 72: Fetch Target Posts for Inbound Mesh Links
"""

import paramiko
import json
import os
import sys

TARGET_SLUGS = [
    'pu-luong-travel-guide',
    'best-time-for-northern-vietnam',
    'where-to-stay-in-vietnam-base-decisions',
    'best-day-trips-from-hanoi',
    'ninh-binh-without-rushing',
    'con-dao-travel-guide',
    'con-dao-vs-phu-quoc',
    'where-to-stay-in-phu-quoc',
    'vietnam-first-trip-planning-checklist',
    '10-days-in-vietnam',
    '14-days-in-vietnam',
    'health-travel-insurance-vietnam',
    'da-nang-to-hue-train-vs-car',
    '7-days-in-vietnam',
    'hue-imperial-city-guide',
    'hoi-an-ancient-town-guide',
    'vietnam-travel-cost',
    'ho-chi-minh-city-travel-guide',
    'mekong-delta-overnight-vs-day-trip',
    'ho-chi-minh-city-in-2-days',
    'vietnam-train-travel',
    'phong-nha-to-hue-transport',
    'hanoi-travel-guide',
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
    with sftp.file('/tmp/stage72_target_slugs.json', 'w') as f:
        f.write(json.dumps(TARGET_SLUGS))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);

$slugs = json_decode(file_get_contents('/tmp/stage72_target_slugs.json'), true);
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

file_put_contents('/tmp/stage72_fetched_targets.json', json_encode($results));
"""
    with sftp.file('/tmp/fetch_stage72_targets.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage72_targets.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    stdout.read()
    err = stderr.read().decode('utf-8')
    if err.strip():
        print("Stderr:", err)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage72_fetched_targets.json', 'r') as f:
        data = json.load(f)
    sftp.close()

    ssh.exec_command("rm -f /tmp/stage72_target_slugs.json /tmp/fetch_stage72_targets.php /tmp/stage72_fetched_targets.json")
    ssh.close()

    out_file = os.path.join(os.path.dirname(__file__), 'stage72_mesh_targets.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    found = sum(1 for v in data.values() if v is not None)
    print(f"Saved {found}/{len(TARGET_SLUGS)} target posts to {out_file}")

if __name__ == '__main__':
    main()

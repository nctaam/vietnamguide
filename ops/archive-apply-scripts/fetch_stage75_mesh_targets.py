# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 75: Fetch Mesh Target Posts
Exports the post_content of the 24 target posts that will provide inbound links to Stage 75 pillars.
"""

import paramiko
import json
import os
import sys

ops_dir = os.path.dirname(os.path.abspath(__file__))
if ops_dir not in sys.path:
    sys.path.insert(0, ops_dir)

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

TARGET_SLUGS = [
    'where-to-stay-in-phu-quoc',
    'phu-quoc-travel-guide',
    'where-to-stay-in-can-tho',
    'southern-vietnam-itinerary',
    'ho-chi-minh-city-travel-guide',
    'best-day-trips-from-ho-chi-minh-city',
    'saigon-airport-to-district-1',
    'transport-within-vietnam',
    'sim-esim-vietnam',
    'vietnam-first-trip-planning-checklist',
    'safety-scams-vietnam',
    'vietnam-travel-cost',
    'da-lat-travel-guide',
    'where-to-stay-in-da-lat',
    '10-days-in-vietnam',
    '14-days-in-vietnam',
    'central-highlands-vietnam-itinerary',
    'where-to-stay-in-nha-trang',
    'saigon-street-food-guide',
    'hanoi-street-food-guide',
    'vietnam-food-safety-street-food-etiquette',
    'where-to-stay-in-da-nang',
    'sapa-travel-guide',
    'where-to-stay-in-sapa',
    'hanoi-travel-guide',
]

def main():
    print(f"=== Fetching {len(TARGET_SLUGS)} Stage 75 Mesh Targets from VPS ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage75_slugs.json', 'w') as f:
        f.write(json.dumps(TARGET_SLUGS))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);
$slugs = json_decode(file_get_contents('/tmp/stage75_slugs.json'), true);
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
            'title' => $p->post_title,
            'slug' => $p->post_name,
            'parent' => $p->post_parent,
            'content' => $p->post_content
        ];
    }
}

file_put_contents('/tmp/stage75_targets_out.json', json_encode($results));
"""
    with sftp.file('/tmp/fetch_stage75_targets.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage75_targets.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    stdout.read()

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage75_targets_out.json', 'r') as f:
        targets_data = json.load(f)
    sftp.close()

    ssh.exec_command("rm -f /tmp/stage75_slugs.json /tmp/fetch_stage75_targets.php /tmp/stage75_targets_out.json")
    ssh.close()

    output_path = os.path.join(ops_dir, 'stage75_mesh_targets.json')
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump(targets_data, f, ensure_ascii=False, indent=2)

    print(f"Successfully fetched {len(targets_data)}/{len(TARGET_SLUGS)} target posts.")
    print(f"Saved to {output_path}")

if __name__ == '__main__':
    main()

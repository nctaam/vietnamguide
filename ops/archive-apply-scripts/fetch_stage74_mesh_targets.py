# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 74: Fetch Target Posts for Inbound Mesh Links
"""

import paramiko
import json
import os
import sys

TARGET_SLUGS = [
    'where-to-stay-in-ha-giang',
    'ha-giang-loop-planning-guide',
    'ha-giang-safety-guide',
    'ha-giang-loop-cost-budget',
    'ha-giang-easy-rider-vs-self-drive',
    'where-to-stay-in-ba-be',
    'cao-bang-travel-guide',
    'hanoi-travel-guide',
    'transport-within-vietnam',
    'sim-esim-vietnam',
    'vietnam-first-trip-planning-checklist',
    'saigon-airport-to-district-1',
    'hanoi-airport-to-old-quarter',
    'vietnam-airport-arrival-checklist',
    'where-to-stay-in-mai-chau',
    'where-to-stay-in-pu-luong',
    'ninh-binh-without-rushing',
    '10-days-in-vietnam',
    'vietnam-vegetarian-travel-guide',
    'vietnam-food-safety-street-food-etiquette',
    'hanoi-street-food-guide',
    'saigon-street-food-guide',
    'hue-street-food-guide',
    'ninh-binh-travel-guide',
    'hanoi-to-ninh-binh-transport',
    'where-to-stay-in-dong-van',
    'where-to-stay-in-meo-vac',
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
    with sftp.file('/tmp/stage74_target_slugs.json', 'w') as f:
        f.write(json.dumps(TARGET_SLUGS))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);

$slugs = json_decode(file_get_contents('/tmp/stage74_target_slugs.json'), true);
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

file_put_contents('/tmp/stage74_fetched_targets.json', json_encode($results));
"""
    with sftp.file('/tmp/fetch_stage74_targets.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage74_targets.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    stdout.read()
    err = stderr.read().decode('utf-8')
    if err.strip():
        print("Stderr:", err)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage74_fetched_targets.json', 'r') as f:
        data = json.load(f)
    sftp.close()

    ssh.exec_command("rm -f /tmp/stage74_target_slugs.json /tmp/fetch_stage74_targets.php /tmp/stage74_fetched_targets.json")
    ssh.close()

    out_file = os.path.join(os.path.dirname(__file__), 'stage74_mesh_targets.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    found = sum(1 for v in data.values() if v is not None)
    print(f"Saved {found}/{len(TARGET_SLUGS)} target posts to {out_file}")

if __name__ == '__main__':
    main()

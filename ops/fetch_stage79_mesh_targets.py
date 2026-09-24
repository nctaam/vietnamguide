# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 79: Fetch Mesh Target Posts
Exports the post_content of target posts that will provide inbound links to Stage 79 pillars.
"""

import paramiko
import json
import os
import sys

ops_dir = os.path.dirname(os.path.abspath(__file__))
if ops_dir not in sys.path:
    sys.path.insert(0, ops_dir)

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

TARGET_SLUGS = [
    'where-to-stay-in-nha-trang',
    'where-to-stay-in-quy-nhon',
    'da-nang-to-quy-nhon-transport',
    'quy-nhon-travel-guide',
    'vietnam-train-travel',
    'where-to-stay-in-can-tho',
    'where-to-stay-in-chau-doc',
    'vietnam-to-cambodia-boat-guide',
    'ho-chi-minh-city-to-can-tho-transport',
    'mekong-delta-travel-guide',
    'where-to-stay-in-mai-chau',
    'best-day-trips-from-hanoi',
    'hanoi-travel-guide',
    'northwest-vietnam-itinerary',
    'transport-within-vietnam',
    'southern-vietnam-itinerary',
    'where-to-stay-in-hue',
    'where-to-stay-in-da-nang',
    'da-nang-to-hue-train-vs-car',
    'central-vietnam-itinerary',
    'hue-to-hoi-an-transport',
    'vietnam-night-train-safety-tips',
    'vietnam-scooter-rental-checklist',
    'safety-scams-vietnam',
    'hanoi-to-sapa-train-vs-sleeper-bus',
    'where-to-stay-in-pu-luong',
    'pu-luong-trekking-routes-guide',
    'nha-trang-to-quy-nhon-transport',
    'can-tho-to-chau-doc-transport',
    'hanoi-to-mai-chau-transport',
    'where-to-stay-in-ben-tre',
    'where-to-stay-in-lang-co',
    'vietnam-sleeper-bus-survival-guide',
    'hanoi-to-pu-luong-transport',
]

def main():
    print(f"=== Fetching {len(TARGET_SLUGS)} Stage 79 Mesh Targets from VPS ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage79_slugs.json', 'w') as f:
        f.write(json.dumps(TARGET_SLUGS))

    php_script = """<?php
$slugs = json_decode(file_get_contents('/tmp/stage79_slugs.json'), true);
$results = [];

foreach ($slugs as $s) {
    $posts = get_posts([
        'name' => $s,
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 1
    ]);
    if (!empty($posts)) {
        $p = $posts[0];
        $results[$s] = [
            'id' => $p->ID,
            'title' => $p->post_title,
            'slug' => $p->post_name,
            'parent' => $p->post_parent,
            'content' => $p->post_content
        ];
    } else {
        echo "NOT FOUND: $s\n";
    }
}

file_put_contents('/tmp/stage79_targets.json', json_encode($results, JSON_UNESCAPED_UNICODE));
"""
    with sftp.file('/tmp/fetch_stage79.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    print("Running WP-CLI target export script...")
    cmd = f"wp eval-file /tmp/fetch_stage79.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    output = stdout.read().decode('utf-8')
    if output.strip():
        print(output)
    err = stderr.read().decode('utf-8')
    if err.strip():
        print("Stderr:", err)

    sftp = ssh.open_sftp()
    local_path = os.path.join(ops_dir, 'stage79_mesh_targets.json')
    sftp.get('/tmp/stage79_targets.json', local_path)
    sftp.close()

    ssh.exec_command("rm -f /tmp/stage79_slugs.json /tmp/fetch_stage79.php /tmp/stage79_targets.json")
    ssh.close()

    with open(local_path, 'r', encoding='utf-8') as f:
        data = json.load(f)

    print(f"Successfully fetched {len(data)} target posts to {local_path}")

if __name__ == '__main__':
    main()
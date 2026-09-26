# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 85: Fetch Mesh Target Posts
Exports post_content of target posts that will provide inbound links to Stage 85 pillars.
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
    # Hosts for where-to-stay-in-du-gia
    'where-to-stay-in-ha-giang',
    'ha-giang-loop-planning-guide',
    'ha-giang-loop-cost-budget',
    'where-to-stay-in-dong-van',
    'where-to-stay-in-meo-vac',
    'ha-giang-to-cao-bang-transport',

    # Hosts for where-to-stay-in-mang-den
    'where-to-stay-in-kon-tum',
    'pleiku-to-kon-tum-transport',
    'kon-tum-to-da-nang-transport',
    'where-to-stay-in-pleiku',
    'where-to-stay-in-da-lat',
    'buon-ma-thuot-to-pleiku-transport',

    # Hosts for nha-trang-to-da-lat-transport
    'where-to-stay-in-da-lat',
    'where-to-stay-in-nha-trang',
    'da-lat-travel-guide',
    'nha-trang-travel-guide',
    'da-lat-to-nha-trang-transport',
    'da-lat-to-mui-ne-transport',

    # Hosts for ninh-binh-to-phong-nha-transport
    'where-to-stay-in-phong-nha',
    'phong-nha-travel-guide',
    'where-to-stay-in-ninh-binh',
    'ninh-binh-travel-guide',
    'where-to-stay-in-dong-hoi',
    'dong-hoi-to-phong-nha-transport',
    'hanoi-to-phong-nha-transport',

    # Hosts for cao-bang-to-ba-be-transport
    'where-to-stay-in-ba-be',
    'where-to-stay-in-cao-bang',
    'cao-bang-travel-guide',
    'hanoi-to-ba-be-transport',
    'hanoi-to-cao-bang-transport',

    # Hosts for ho-chi-minh-city-to-chau-doc-transport
    'where-to-stay-in-chau-doc',
    'can-tho-to-chau-doc-transport',
    'vietnam-to-cambodia-boat-guide',
    'where-to-stay-in-ho-chi-minh-city',
    'ho-chi-minh-city-travel-guide',

    # Hosts for where-to-stay-in-an-giang
    'where-to-stay-in-chau-doc',
    'can-tho-to-chau-doc-transport',
    'where-to-stay-in-can-tho',
    'vietnam-to-cambodia-boat-guide',
    'mekong-delta-travel-guide',
]

def main():
    # Remove duplicates while preserving order
    unique_slugs = list(dict.fromkeys(TARGET_SLUGS))
    print(f"=== Fetching {len(unique_slugs)} Target Posts for Stage 85 Mesh ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage85_slugs.json', 'w') as f:
        f.write(json.dumps(unique_slugs))

    php_script = """<?php
$slugs = json_decode(file_get_contents('/tmp/stage85_slugs.json'), true);
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
            'content' => $p->post_content
        ];
    }
}

file_put_contents('/tmp/stage85_fetched_targets.json', json_encode($results));
"""
    with sftp.file('/tmp/fetch_stage85.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage85.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    stdout.channel.recv_exit_status()

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage85_fetched_targets.json', 'r') as f:
        targets_data = json.load(f)
    sftp.close()

    ssh.exec_command("rm -f /tmp/fetch_stage85.php /tmp/stage85_slugs.json /tmp/stage85_fetched_targets.json")
    ssh.close()

    out_file = os.path.join(ops_dir, 'stage85_mesh_targets.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(targets_data, f, ensure_ascii=False, indent=2)

    print(f"Exported {len(targets_data)} target posts to {out_file}")

if __name__ == '__main__':
    main()

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 86: Fetch Mesh Target Posts
Exports post_content of target posts that will provide inbound links to Stage 86 pillars.
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
    # Hosts for where-to-stay-in-ly-son & da-nang-to-ly-son-transport
    'ly-son-travel-guide',
    'ly-son-vs-cham-islands',
    'where-to-stay-in-quy-nhon',
    'where-to-stay-in-da-nang',
    'da-nang-travel-guide',

    # Hosts for mang-den-travel-guide
    'where-to-stay-in-mang-den',
    'where-to-stay-in-kon-tum',
    'pleiku-to-kon-tum-transport',
    'kon-tum-to-da-nang-transport',
    'where-to-stay-in-pleiku',

    # Hosts for ba-be-lake-travel-guide
    'where-to-stay-in-ba-be',
    'hanoi-to-ba-be-transport',
    'cao-bang-to-ba-be-transport',
    'cao-bang-travel-guide',
    'where-to-stay-in-cao-bang',

    # Hosts for hue-to-phong-nha-transport
    'where-to-stay-in-phong-nha',
    'phong-nha-travel-guide',
    'phong-nha-to-hue-transport',
    'where-to-stay-in-hue',
    'dong-hoi-to-phong-nha-transport',

    # Hosts for mai-chau-to-pu-luong-transport
    'where-to-stay-in-pu-luong',
    'pu-luong-travel-guide',
    'where-to-stay-in-mai-chau',
    'hanoi-to-pu-luong-transport',
    'hanoi-to-mai-chau-transport',

    # Hosts for ha-tien-to-phu-quoc-ferry
    'where-to-stay-in-ha-tien',
    'where-to-stay-in-phu-quoc',
    'phu-quoc-travel-guide',
    'can-tho-to-ha-tien-transport',
    'phu-quoc-ferry-guide',
]

def main():
    unique_slugs = list(dict.fromkeys(TARGET_SLUGS))
    print(f"=== Fetching {len(unique_slugs)} Target Posts for Stage 86 Mesh ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage86_slugs.json', 'w') as f:
        f.write(json.dumps(unique_slugs))

    php_script = """<?php
$slugs = json_decode(file_get_contents('/tmp/stage86_slugs.json'), true);
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

file_put_contents('/tmp/stage86_fetched_targets.json', json_encode($results));
"""
    with sftp.file('/tmp/fetch_stage86.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage86.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    stdout.channel.recv_exit_status()

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage86_fetched_targets.json', 'r') as f:
        targets_data = json.load(f)
    sftp.close()

    ssh.exec_command("rm -f /tmp/fetch_stage86.php /tmp/stage86_slugs.json /tmp/stage86_fetched_targets.json")
    ssh.close()

    out_file = os.path.join(ops_dir, 'stage86_mesh_targets.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(targets_data, f, ensure_ascii=False, indent=2)

    print(f"Exported {len(targets_data)} target posts to {out_file}")

if __name__ == '__main__':
    main()

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 83: Fetch Mesh Target Posts
Exports post_content of target posts that will provide inbound links to Stage 83 pillars.
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
    # Hosts for where-to-stay-in-hai-phong
    'hanoi-to-hai-phong-transport',
    'where-to-stay-in-cat-ba',
    'cat-ba-to-ninh-binh-transport',
    'hanoi-to-ha-long-bay-transport',
    'where-to-stay-in-hanoi',

    # Hosts for dong-hoi-to-phong-nha-transport
    'where-to-stay-in-dong-hoi',
    'where-to-stay-in-phong-nha',
    'hue-to-dong-hoi-transport',
    'hanoi-to-phong-nha-transport',
    'da-nang-to-phong-nha-transport',
    'phong-nha-to-hue-transport',

    # Hosts for can-tho-to-ca-mau-transport
    'where-to-stay-in-ca-mau',
    'where-to-stay-in-bac-lieu',
    'where-to-stay-in-soc-trang',
    'where-to-stay-in-can-tho',
    'can-tho-to-ha-tien-transport',
    'can-tho-to-rach-gia-transport',

    # Hosts for where-to-stay-in-phan-rang
    'where-to-stay-in-cam-ranh',
    'where-to-stay-in-nha-trang',
    'where-to-stay-in-mui-ne',
    'buon-ma-thuot-to-nha-trang-transport',
    'nha-trang-to-quy-nhon-transport',

    # Hosts for hanoi-to-tam-dao-transport
    'where-to-stay-in-tam-dao',
    'best-day-trips-from-hanoi',
    'hanoi-travel-guide',

    # Hosts for where-to-stay-in-tam-dao
    'hanoi-to-tam-dao-transport',
    'where-to-stay-in-mai-chau',
    'where-to-stay-in-ninh-binh',

    # Hosts for kon-tum-to-da-nang-transport
    'where-to-stay-in-kon-tum',
    'pleiku-to-kon-tum-transport',
    'where-to-stay-in-da-nang',
    'where-to-stay-in-pleiku',
    'central-highlands-vietnam-itinerary',
]

def main():
    print(f"=== Fetching {len(TARGET_SLUGS)} Target Posts for Stage 83 Mesh ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage83_slugs.json', 'w') as f:
        f.write(json.dumps(TARGET_SLUGS))

    php_script = """<?php
$slugs = json_decode(file_get_contents('/tmp/stage83_slugs.json'), true);
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

file_put_contents('/tmp/stage83_fetched_targets.json', json_encode($results));
"""
    with sftp.file('/tmp/fetch_stage83.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage83.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    stdout.channel.recv_exit_status()

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage83_fetched_targets.json', 'r') as f:
        targets_data = json.load(f)
    sftp.close()

    ssh.exec_command("rm -f /tmp/fetch_stage83.php /tmp/stage83_slugs.json /tmp/stage83_fetched_targets.json")
    ssh.close()

    out_file = os.path.join(ops_dir, 'stage83_mesh_targets.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(targets_data, f, ensure_ascii=False, indent=2)

    print(f"Exported {len(targets_data)} target posts to {out_file}")

if __name__ == '__main__':
    main()

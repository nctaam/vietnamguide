# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 82: Fetch Mesh Target Posts
Exports post_content of target posts that will provide inbound links to Stage 82 pillars.
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
    # Host targets for hoi-an-to-quy-nhon-transport
    'where-to-stay-in-quy-nhon',
    'where-to-stay-in-hoi-an',
    'nha-trang-to-quy-nhon-transport',
    'da-nang-to-quy-nhon-transport',
    'hue-to-hoi-an-transport',
    'central-vietnam-itinerary',
    # Host targets for hanoi-to-hai-phong-transport
    'where-to-stay-in-cat-ba',
    'cat-ba-to-ninh-binh-transport',
    'hanoi-to-cat-ba-island-transport',
    'hanoi-to-ha-long-bay-transport',
    'where-to-stay-in-hanoi',
    # Host targets for can-tho-to-ha-tien-transport
    'phu-quoc-ferry-guide',
    'can-tho-to-rach-gia-transport',
    'can-tho-to-chau-doc-transport',
    'where-to-stay-in-ha-tien',
    'where-to-stay-in-rach-gia',
    'where-to-stay-in-phu-quoc',
    # Host targets for buon-ma-thuot-to-nha-trang-transport
    'da-lat-to-buon-ma-thuot-transport',
    'where-to-stay-in-buon-ma-thuot',
    'where-to-stay-in-nha-trang',
    'pleiku-to-kon-tum-transport',
    'central-highlands-vietnam-itinerary',
    # Host targets for where-to-stay-in-bac-lieu
    'where-to-stay-in-soc-trang',
    'where-to-stay-in-ben-tre',
    'where-to-stay-in-can-tho',
    'where-to-stay-in-chau-doc',
    'mekong-delta-travel-guide',
    # Host targets for where-to-stay-in-ca-mau
    'how-to-get-to-con-dao-flight-vs-ferry',
    'ho-chi-minh-city-to-can-tho-transport',
    # Host targets for vietnam-train-vs-flight
    'vietnam-domestic-flights-guide',
    'vietnam-train-travel',
    'vietnam-sleeper-bus-survival-guide',
    'da-nang-to-hue-train-vs-car',
    'hanoi-to-sapa-train-vs-sleeper-bus',
    'hanoi-to-hue-transport',
    'vietnam-night-train-safety-tips',
]

def main():
    print(f"=== Fetching {len(TARGET_SLUGS)} Target Posts for Stage 82 Mesh ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage82_slugs.json', 'w') as f:
        f.write(json.dumps(TARGET_SLUGS))

    php_script = """<?php
$slugs = json_decode(file_get_contents('/tmp/stage82_slugs.json'), true);
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

file_put_contents('/tmp/stage82_fetched_targets.json', json_encode($results));
"""
    with sftp.file('/tmp/fetch_stage82.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage82.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    stdout.channel.recv_exit_status()

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage82_fetched_targets.json', 'r') as f:
        targets_data = json.load(f)
    sftp.close()

    ssh.exec_command("rm -f /tmp/fetch_stage82.php /tmp/stage82_slugs.json /tmp/stage82_fetched_targets.json")
    ssh.close()

    out_file = os.path.join(ops_dir, 'stage82_mesh_targets.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(targets_data, f, ensure_ascii=False, indent=2)

    print(f"Exported {len(targets_data)} target posts to {out_file}")

if __name__ == '__main__':
    main()

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 84: Fetch Mesh Target Posts
Exports post_content of target posts that will provide inbound links to Stage 84 pillars.
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
    # Hosts for where-to-stay-in-dong-van
    'where-to-stay-in-ha-giang',
    'ha-giang-loop-planning-guide',
    'sapa-to-ha-giang-transport',
    'ha-giang-to-cao-bang-transport',
    'where-to-stay-in-cao-bang',

    # Hosts for where-to-stay-in-cao-bang
    'cao-bang-travel-guide',
    'hanoi-to-cao-bang-transport',
    'where-to-stay-in-ba-be',
    'best-places-to-visit-vietnam',
    'where-to-stay-in-dong-van',

    # Hosts for ha-long-to-ninh-binh-transport
    'where-to-stay-in-ninh-binh',
    'cat-ba-to-ninh-binh-transport',
    'hanoi-to-ha-long-bay-transport',
    'hanoi-to-ninh-binh-transport',
    'ha-long-bay-travel-guide',

    # Hosts for dong-hoi-to-hue-transport
    'where-to-stay-in-hue',
    'where-to-stay-in-dong-hoi',
    'hue-to-dong-hoi-transport',
    'dong-hoi-to-phong-nha-transport',
    'phong-nha-to-hue-transport',
    'da-nang-to-hue-train-vs-car',

    # Hosts for da-lat-to-mui-ne-transport
    'where-to-stay-in-mui-ne',
    'where-to-stay-in-da-lat',
    'best-things-to-do-in-mui-ne',
    'da-lat-to-buon-ma-thuot-transport',

    # Hosts for where-to-stay-in-vinh-hy
    'where-to-stay-in-phan-rang',
    'where-to-stay-in-cam-ranh',
    'where-to-stay-in-nha-trang',

    # Hosts for quy-nhon-to-nha-trang-transport
    'where-to-stay-in-quy-nhon',
    'nha-trang-to-quy-nhon-transport',
    'da-nang-to-quy-nhon-transport',
    'hoi-an-to-quy-nhon-transport',
]

def main():
    # Remove duplicates while preserving order
    unique_slugs = list(dict.fromkeys(TARGET_SLUGS))
    print(f"=== Fetching {len(unique_slugs)} Target Posts for Stage 84 Mesh ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage84_slugs.json', 'w') as f:
        f.write(json.dumps(unique_slugs))

    php_script = """<?php
$slugs = json_decode(file_get_contents('/tmp/stage84_slugs.json'), true);
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

file_put_contents('/tmp/stage84_fetched_targets.json', json_encode($results));
"""
    with sftp.file('/tmp/fetch_stage84.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage84.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    stdout.channel.recv_exit_status()

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage84_fetched_targets.json', 'r') as f:
        targets_data = json.load(f)
    sftp.close()

    ssh.exec_command("rm -f /tmp/fetch_stage84.php /tmp/stage84_slugs.json /tmp/stage84_fetched_targets.json")
    ssh.close()

    out_file = os.path.join(ops_dir, 'stage84_mesh_targets.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(targets_data, f, ensure_ascii=False, indent=2)

    print(f"Exported {len(targets_data)} target posts to {out_file}")

if __name__ == '__main__':
    main()

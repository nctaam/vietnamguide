# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 77: Fetch Mesh Target Posts
Exports the post_content of the 26 target posts that will provide inbound links to Stage 77 pillars.
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
    'phong-nha-travel-guide',
    'where-to-stay-in-phong-nha',
    'phong-nha-to-hue-transport',
    'hanoi-to-phong-nha-transport',
    'da-lat-travel-guide',
    'where-to-stay-in-da-lat',
    'where-to-stay-in-mui-ne',
    'best-things-to-do-in-mui-ne',
    'central-highlands-vietnam-itinerary',
    'where-to-stay-in-buon-ma-thuot',
    'da-lat-coffee-farms-guide',
    'best-things-to-do-in-da-lat',
    'vietnam-motorbike-license-laws',
    'ha-giang-safety-guide',
    'ha-giang-easy-rider-vs-self-drive',
    'transport-within-vietnam',
    'mu-cang-chai-travel-guide',
    'northwest-vietnam-itinerary',
    'sapa-travel-guide',
    'hanoi-travel-guide',
    'where-to-stay-in-sapa',
    'sapa-vs-ha-giang',
    'da-nang-travel-guide',
    'where-to-stay-in-da-nang',
    'central-vietnam-itinerary',
    'vietnam-train-travel',
]

def main():
    print(f"=== Fetching {len(TARGET_SLUGS)} Stage 77 Mesh Targets from VPS ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage77_slugs.json', 'w') as f:
        f.write(json.dumps(TARGET_SLUGS))

    php_script = """<?php
$slugs = json_decode(file_get_contents('/tmp/stage77_slugs.json'), true);
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
        echo "NOT FOUND: $s\\n";
    }
}

file_put_contents('/tmp/stage77_targets.json', json_encode($results, JSON_UNESCAPED_UNICODE));
"""
    with sftp.file('/tmp/fetch_stage77.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    print("Running WP-CLI target export script...")
    cmd = f"wp eval-file /tmp/fetch_stage77.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    output = stdout.read().decode('utf-8')
    if output.strip():
        print("Output:", output)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage77_targets.json', 'r') as f:
        targets_data = json.load(f)
    sftp.close()

    ssh.exec_command("rm -f /tmp/fetch_stage77.php /tmp/stage77_slugs.json /tmp/stage77_targets.json")
    ssh.close()

    out_file = os.path.join(ops_dir, 'stage77_mesh_targets.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(targets_data, f, ensure_ascii=False, indent=2)

    print(f"Successfully exported {len(targets_data)} target posts to {out_file}")

if __name__ == '__main__':
    main()

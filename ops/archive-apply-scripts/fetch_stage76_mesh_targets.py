# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 76: Fetch Mesh Target Posts
Exports the post_content of the 25 target posts that will provide inbound links to Stage 76 pillars.
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
    'ninh-binh-travel-guide',
    'tam-coc-travel-guide',
    'where-to-stay-in-ninh-binh',
    'hang-mua-ninh-binh-guide',
    'saigon-to-vung-tau-transport',
    'best-day-trips-from-ho-chi-minh-city',
    'ho-chi-minh-city-travel-guide',
    'southern-vietnam-itinerary',
    'da-nang-travel-guide',
    'quy-nhon-travel-guide',
    'where-to-stay-in-quy-nhon',
    'central-vietnam-itinerary',
    'where-to-stay-in-con-dao',
    'con-dao-vs-phu-quoc',
    'where-to-stay-in-ba-be',
    'hanoi-to-ba-be-transport',
    'where-to-stay-in-cao-bang',
    'hanoi-to-cao-bang-transport',
    'ha-giang-safety-guide',
    'ha-giang-easy-rider-vs-self-drive',
    'transport-within-vietnam',
    'vietnam-first-trip-planning-checklist',
    'what-to-pack-for-vietnam-region-season',
    'vietnam-train-travel',
    'vietnam-sim-card-airport-vs-city',
]

def main():
    print(f"=== Fetching {len(TARGET_SLUGS)} Stage 76 Mesh Targets from VPS ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage76_slugs.json', 'w') as f:
        f.write(json.dumps(TARGET_SLUGS))

    php_script = """<?php
$slugs = json_decode(file_get_contents('/tmp/stage76_slugs.json'), true);
$results = [];

foreach ($slugs as $slug) {
    $p = get_posts([
        'name' => $slug,
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 1
    ]);
    if (!empty($p)) {
        $results[$slug] = [
            'id' => $p[0]->ID,
            'title' => $p[0]->post_title,
            'content' => $p[0]->post_content
        ];
    } else {
        $results[$slug] = null;
    }
}

file_put_contents('/tmp/stage76_targets.json', json_encode($results));
"""
    with sftp.file('/tmp/fetch_stage76.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage76.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    stdout.read()

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage76_targets.json', 'r') as f:
        targets = json.load(f)
    sftp.close()

    ssh.exec_command("rm -f /tmp/stage76_slugs.json /tmp/fetch_stage76.php /tmp/stage76_targets.json")
    ssh.close()

    out_file = os.path.join(ops_dir, 'stage76_mesh_targets.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(targets, f, ensure_ascii=False, indent=2)

    found_count = sum(1 for v in targets.values() if v is not None)
    print(f"Successfully fetched {found_count}/{len(TARGET_SLUGS)} target posts into {out_file}")

    for slug, data in targets.items():
        if data is None:
            print(f"  [MISSING] {slug}")
        else:
            print(f"  [OK] {slug} (ID: {data['id']}) - Content: {len(data['content'])} chars")

if __name__ == '__main__':
    main()

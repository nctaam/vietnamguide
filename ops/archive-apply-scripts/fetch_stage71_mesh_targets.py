# -*- coding: utf-8 -*-
"""
Fetch exact source post contents for Stage 71 internal link mesh
"""

import paramiko
import json
import sys
import os

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

TARGET_SLUGS = [
    # 1. where-to-stay-in-ha-long-bay
    "ha-long-bay-travel-guide",
    "best-things-to-do-in-ha-long-bay",
    "ha-long-bay-vs-lan-ha-bay",
    "hanoi-to-ha-long-bay-transport",

    # 2. where-to-stay-in-quy-nhon
    "quy-nhon-travel-guide",
    "best-things-to-do-in-quy-nhon",
    "best-beaches-in-vietnam",
    "vietnam-train-travel",

    # 3. where-to-stay-in-can-tho
    "mekong-delta-travel-guide",
    "ho-chi-minh-city-to-can-tho-transport",
    "mekong-delta-floating-markets-guide",
    "mekong-delta-overnight-vs-day-trip",

    # 4. where-to-stay-in-cat-ba
    "cat-ba-travel-guide",
    "hanoi-to-cat-ba-island-transport",
    # ha-long-bay-vs-lan-ha-bay already included
    "best-islands-in-vietnam",

    # 5. where-to-stay-in-ha-giang
    "ha-giang-loop-planning-guide",
    "hanoi-to-ha-giang-transport",
    "ha-giang-safety-guide",
    "ha-giang-loop-cost-budget",

    # 6. ho-chi-minh-city-in-2-days
    "ho-chi-minh-city-travel-guide",
    "best-things-to-do-in-ho-chi-minh-city",
    "saigon-street-food-guide",
    "hanoi-in-2-days",

    # 7. da-nang-to-nha-trang-transport
    "da-nang-travel-guide",
    "nha-trang-travel-guide",
    "transport-within-vietnam",
    # vietnam-train-travel already included
]

# Deduplicate preserving order
UNIQUE_SLUGS = []
for s in TARGET_SLUGS:
    if s not in UNIQUE_SLUGS:
        UNIQUE_SLUGS.append(s)

def main():
    print(f"=== Fetching {len(UNIQUE_SLUGS)} target posts for Stage 71 Mesh ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage71_targets.json', 'w') as f:
        f.write(json.dumps(UNIQUE_SLUGS))

    php_script = """<?php
global $wpdb;
$slugs = json_decode(file_get_contents('/tmp/stage71_targets.json'), true);
$results = [];

foreach ($slugs as $slug) {
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT ID, post_name, post_title, post_content FROM {$wpdb->prefix}posts WHERE post_name = %s AND post_status = 'publish' LIMIT 1",
        $slug
    ), ARRAY_A);

    if ($row) {
        $results[$slug] = [
            'ID' => (int)$row['ID'],
            'title' => $row['post_title'],
            'content' => $row['post_content'],
        ];
    } else {
        $results[$slug] = null;
    }
}

echo json_encode($results);
"""
    with sftp.file('/tmp/fetch_stage71.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage71.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    raw_output = stdout.read().decode('utf-8')

    ssh.exec_command("rm -f /tmp/fetch_stage71.php /tmp/stage71_targets.json")
    ssh.close()

    data = json.loads(raw_output)
    missing = [slug for slug, val in data.items() if val is None]
    if missing:
        print(f"[WARNING] Missing slugs: {missing}")
    else:
        print(f"All {len(data)} target posts successfully fetched from VPS.")

    out_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage71_mesh_sources.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(f"Saved to {out_file}")

if __name__ == '__main__':
    main()

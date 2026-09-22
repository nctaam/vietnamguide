# -*- coding: utf-8 -*-
"""
Fetch exact source post contents for Stage 66 internal link mesh
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
    # hanoi-train-street-guide (4)
    "hanoi-travel-guide",
    "hanoi-street-food-guide",
    "vietnam-coffee-guide",
    "vietnam-train-travel",

    # cu-chi-tunnels-ben-duoc-vs-ben-dinh (4)
    "ho-chi-minh-city-travel-guide",
    "saigon-airport-to-district-1",
    "saigon-street-food-guide",
    "saigon-night-markets-guide",

    # ba-na-hills-golden-bridge-guide (4)
    "da-nang-travel-guide",
    "where-to-stay-in-da-nang",
    "da-nang-airport-to-hoi-an",
    "da-nang-street-food-guide",

    # vietnam-to-cambodia-border-crossings (4)
    "vietnam-evisa",
    "vietnam-sleeper-bus-guide",
    "transport-within-vietnam",
    # (saigon-airport-to-district-1 is already in list)

    # hanoi-to-cat-ba-island-transport (4)
    "cat-ba-travel-guide",
    "hanoi-to-ha-long-bay-transport",
    "ha-long-bay-day-trip-vs-overnight-cruise",
    "hanoi-airport-to-old-quarter",

    # hue-street-food-guide (4)
    "hue-imperial-city-guide",
    "best-things-to-do-in-hue",
    "da-nang-to-hue-train-vs-car",
    "hoi-an-street-food-guide",

    # vietnam-in-july (4)
    "vietnam-in-june",
    "vietnam-in-may",
    "best-time-to-visit-vietnam",
    "cham-islands-day-trip-guide"
]

def main():
    unique_slugs = sorted(list(set(TARGET_SLUGS)))
    print(f"=== Fetching exact content for {len(unique_slugs)} unique target posts ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage66_slugs.json', 'w') as f:
        f.write(json.dumps(unique_slugs, ensure_ascii=False))

    php_script = """<?php
$slugs = json_decode(file_get_contents('/tmp/stage66_slugs.json'), true);
$results = [];

foreach ($slugs as $slug) {
    $p = get_posts([
        'name' => $slug,
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 1
    ]);
    if (!empty($p)) {
        $post = $p[0];
        $results[$slug] = [
            'ID' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content
        ];
    } else {
        $results[$slug] = null;
    }
}

file_put_contents('/tmp/stage66_sources.json', json_encode($results, JSON_UNESCAPED_UNICODE));
echo "Exported " . count($results) . " posts.\\n";
"""
    with sftp.file('/tmp/fetch_stage66.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage66.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(stdout.read().decode('utf-8'))

    # Download stage66_sources.json locally
    sftp = ssh.open_sftp()
    ops_dir = os.path.dirname(os.path.abspath(__file__))
    local_out = os.path.join(ops_dir, 'stage66_mesh_sources.json')
    sftp.get('/tmp/stage66_sources.json', local_out)
    sftp.close()

    ssh.exec_command("rm -f /tmp/stage66_slugs.json /tmp/fetch_stage66.php /tmp/stage66_sources.json")
    ssh.close()
    print(f"Saved local sources to {local_out}")

if __name__ == '__main__':
    main()

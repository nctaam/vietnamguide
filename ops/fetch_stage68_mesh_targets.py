# -*- coding: utf-8 -*-
"""
Fetch exact source post contents for Stage 68 internal link mesh
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
    # Group 1: vietnam-in-october (4)
    "vietnam-in-september",
    "vietnam-in-november",
    "best-time-to-visit-vietnam",
    "sapa-travel-guide",

    # Group 2: best-things-to-do-in-ho-chi-minh-city (4)
    "ho-chi-minh-city-travel-guide",
    "saigon-street-food-guide",
    "best-day-trips-from-ho-chi-minh-city",
    "hanoi-vs-ho-chi-minh-city",

    # Group 3: ho-chi-minh-city-to-da-lat-transport (4)
    "da-lat-travel-guide",
    "da-lat-coffee-farms-guide",
    "da-lat-waterfalls-guide",
    "transport-within-vietnam",

    # Group 4: where-to-stay-in-phu-quoc (4)
    "phu-quoc-travel-guide",
    "phu-quoc-beaches-guide",
    "con-dao-vs-phu-quoc",
    "where-to-stay-in-vietnam-base-decisions",

    # Group 5: best-things-to-do-in-da-nang (4)
    "da-nang-travel-guide",
    "da-nang-beaches-guide",
    "da-nang-street-food-guide",
    "da-nang-vs-hoi-an",

    # Group 6: ho-chi-minh-city-to-phu-quoc-transport (4)
    # phu-quoc-travel-guide already in list
    "phu-quoc-vs-nha-trang",
    "best-islands-in-vietnam",
    "vietnam-travel-guide",

    # Group 7: where-to-stay-in-nha-trang (4)
    "nha-trang-travel-guide",
    "mui-ne-vs-nha-trang",
    "da-lat-to-nha-trang-transport",
    "best-beaches-in-vietnam",
]

def main():
    unique_slugs = sorted(list(set(TARGET_SLUGS)))
    print(f"=== Fetching exact content for {len(unique_slugs)} unique target posts ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage68_slugs.json', 'w') as f:
        f.write(json.dumps(unique_slugs, ensure_ascii=False))

    php_script = """<?php
$slugs = json_decode(file_get_contents('/tmp/stage68_slugs.json'), true);
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

file_put_contents('/tmp/stage68_sources.json', json_encode($results, JSON_UNESCAPED_UNICODE));
echo "Exported " . count($results) . " posts.\\n";
"""
    with sftp.file('/tmp/fetch_stage68.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage68.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(stdout.read().decode('utf-8'))

    # Download stage68_sources.json locally
    sftp = ssh.open_sftp()
    ops_dir = os.path.dirname(os.path.abspath(__file__))
    local_out = os.path.join(ops_dir, 'stage68_mesh_sources.json')
    sftp.get('/tmp/stage68_sources.json', local_out)
    sftp.close()

    ssh.exec_command("rm -f /tmp/stage68_slugs.json /tmp/fetch_stage68.php /tmp/stage68_sources.json")
    ssh.close()
    print(f"Saved local sources to {local_out}")

if __name__ == '__main__':
    main()

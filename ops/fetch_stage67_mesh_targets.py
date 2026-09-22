# -*- coding: utf-8 -*-
"""
Fetch exact source post contents for Stage 67 internal link mesh
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
    # vietnam-in-august (4)
    "vietnam-in-july",
    "best-time-to-visit-vietnam",
    "da-nang-beaches-guide",
    "vietnam-in-june",

    # where-to-stay-in-hue (4)
    "hue-imperial-city-guide",
    "hue-street-food-guide",
    "best-things-to-do-in-hue",
    "da-nang-to-hue-train-vs-car",

    # mekong-delta-floating-markets-guide (4)
    "mekong-delta-travel-guide",
    "mekong-delta-overnight-vs-day-trip",
    "ho-chi-minh-city-travel-guide",
    "best-day-trips-from-ho-chi-minh-city",

    # hanoi-to-phong-nha-transport (4)
    "phong-nha-travel-guide",
    "phong-nha-cave-treks",
    "phong-nha-to-hue-transport",
    "vietnam-train-travel",

    # da-lat-coffee-farms-guide (4)
    "da-lat-travel-guide",
    "da-lat-waterfalls-guide",
    "da-lat-to-nha-trang-transport",
    "vietnam-coffee-guide",

    # hoi-an-tailoring-guide (4)
    "hoi-an-ancient-town-guide",
    "best-things-to-do-in-hoi-an",
    "hoi-an-street-food-guide",
    "da-nang-vs-hoi-an",

    # vietnam-in-september (4)
    "vietnam-in-november",
    "mu-cang-chai-travel-guide",
    # best-time-to-visit-vietnam already in list
    # vietnam-in-august is a new pillar, use another
    "sapa-travel-guide",
]

def main():
    unique_slugs = sorted(list(set(TARGET_SLUGS)))
    print(f"=== Fetching exact content for {len(unique_slugs)} unique target posts ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage67_slugs.json', 'w') as f:
        f.write(json.dumps(unique_slugs, ensure_ascii=False))

    php_script = """<?php
$slugs = json_decode(file_get_contents('/tmp/stage67_slugs.json'), true);
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

file_put_contents('/tmp/stage67_sources.json', json_encode($results, JSON_UNESCAPED_UNICODE));
echo "Exported " . count($results) . " posts.\\n";
"""
    with sftp.file('/tmp/fetch_stage67.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage67.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(stdout.read().decode('utf-8'))

    # Download stage67_sources.json locally
    sftp = ssh.open_sftp()
    ops_dir = os.path.dirname(os.path.abspath(__file__))
    local_out = os.path.join(ops_dir, 'stage67_mesh_sources.json')
    sftp.get('/tmp/stage67_sources.json', local_out)
    sftp.close()

    ssh.exec_command("rm -f /tmp/stage67_slugs.json /tmp/fetch_stage67.php /tmp/stage67_sources.json")
    ssh.close()
    print(f"Saved local sources to {local_out}")

if __name__ == '__main__':
    main()

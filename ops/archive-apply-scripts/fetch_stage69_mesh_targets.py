# -*- coding: utf-8 -*-
"""
Fetch exact source post contents for Stage 69 internal link mesh
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
    # 1. where-to-stay-in-da-lat
    "da-lat-travel-guide",
    "da-lat-coffee-farms-guide",
    "da-lat-waterfalls-guide",
    "ho-chi-minh-city-to-da-lat-transport",

    # 2. best-things-to-do-in-da-lat
    # da-lat-travel-guide already in list
    "vietnam-coffee-guide",
    "da-lat-to-nha-trang-transport",
    "vietnam-in-october",

    # 3. best-things-to-do-in-nha-trang
    "nha-trang-travel-guide",
    "where-to-stay-in-nha-trang",
    "mui-ne-vs-nha-trang",
    "best-beaches-in-vietnam",

    # 4. best-things-to-do-in-phu-quoc
    "phu-quoc-travel-guide",
    "where-to-stay-in-phu-quoc",
    "phu-quoc-beaches-guide",
    "ho-chi-minh-city-to-phu-quoc-transport",

    # 5. where-to-stay-in-phong-nha
    "phong-nha-travel-guide",
    "phong-nha-cave-treks",
    "hanoi-to-phong-nha-transport",
    "phong-nha-to-hue-transport",

    # 6. hue-to-hoi-an-transport
    "hue-imperial-city-guide",
    "hoi-an-ancient-town-guide",
    "da-nang-to-hue-train-vs-car",
    "da-nang-airport-to-hoi-an",

    # 7. ho-chi-minh-city-to-mui-ne-transport
    "mui-ne-travel-guide",
    # mui-ne-vs-nha-trang already in list
    "transport-within-vietnam",
    "best-day-trips-from-ho-chi-minh-city",
]

def main():
    unique_slugs = sorted(list(set(TARGET_SLUGS)))
    print(f"=== Fetching exact content for {len(unique_slugs)} unique target posts ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage69_slugs.json', 'w') as f:
        f.write(json.dumps(unique_slugs, ensure_ascii=False))

    php_script = """<?php
$slugs = json_decode(file_get_contents('/tmp/stage69_slugs.json'), true);
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

file_put_contents('/tmp/stage69_sources.json', json_encode($results, JSON_UNESCAPED_UNICODE));
echo "Exported " . count($results) . " posts.\\n";
"""
    with sftp.file('/tmp/fetch_stage69.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage69.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(stdout.read().decode('utf-8'))

    # Download stage69_sources.json locally
    sftp = ssh.open_sftp()
    ops_dir = os.path.dirname(os.path.abspath(__file__))
    local_out = os.path.join(ops_dir, 'stage69_mesh_sources.json')
    sftp.get('/tmp/stage69_sources.json', local_out)
    sftp.close()

    ssh.exec_command("rm -f /tmp/stage69_slugs.json /tmp/fetch_stage69.php /tmp/stage69_sources.json")
    ssh.close()
    print(f"Saved local sources to {local_out}")

if __name__ == '__main__':
    main()

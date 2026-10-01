# -*- coding: utf-8 -*-
"""
Fetch exact source post contents for Stage 65 internal link mesh
"""

import paramiko
import json
import sys

sys.stdout.reconfigure(encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

TARGET_SLUGS = [
    # For phu-quoc-beaches-guide
    "phu-quoc-travel-guide",
    "best-beaches-in-vietnam",
    "cham-islands-day-trip-guide",
    "phu-quoc-vs-nha-trang",
    # For con-dao-vs-phu-quoc
    "con-dao-travel-guide",
    "best-islands-in-vietnam",
    "where-to-stay-in-vietnam-base-decisions",
    "14-days-in-vietnam",
    # For da-lat-waterfalls-guide
    "da-lat-travel-guide",
    "sapa-trekking-routes-guide",
    "north-central-south-vietnam",
    "21-days-in-vietnam",
    # For vietnam-sleeper-bus-guide
    "transport-within-vietnam",
    "vietnam-first-trip-planning-checklist",
    "hanoi-to-sapa-transport",
    "hanoi-to-ha-giang-transport",
    # For phong-nha-to-hue-transport
    "phong-nha-travel-guide",
    "hue-imperial-city-guide",
    "phong-nha-cave-treks",
    "best-things-to-do-in-hue",
    # For da-lat-to-nha-trang-transport
    "nha-trang-travel-guide",
    "mui-ne-vs-nha-trang",
    "10-days-in-vietnam",
    "best-vietnam-routes-first-time-visitors",
    # For vietnam-in-june
    "best-time-to-visit-vietnam",
    "vietnam-in-april",
    "vietnam-in-may",
    "vietnam-rainy-season-flexible-route"
]

def main():
    print(f"=== Fetching exact content for {len(TARGET_SLUGS)} target posts ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage65_slugs.json', 'w') as f:
        f.write(json.dumps(TARGET_SLUGS, ensure_ascii=False))

    php_script = """<?php
$slugs = json_decode(file_get_contents('/tmp/stage65_slugs.json'), true);
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

file_put_contents('/tmp/stage65_sources.json', json_encode($results, JSON_UNESCAPED_UNICODE));
echo "Exported " . count($results) . " posts.\\n";
"""
    with sftp.file('/tmp/fetch_stage65.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage65.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(stdout.read().decode('utf-8'))

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage65_sources.json', 'r') as f:
        sources = json.load(f)
    sftp.close()

    ssh.exec_command("rm -f /tmp/stage65_slugs.json /tmp/fetch_stage65.php /tmp/stage65_sources.json")
    ssh.close()

    with open('ops/stage65_mesh_raw_sources.json', 'w', encoding='utf-8') as f:
        json.dump(sources, f, ensure_ascii=False, indent=2)

    found = sum(1 for v in sources.values() if v is not None)
    print(f"Successfully saved {found}/{len(TARGET_SLUGS)} posts to ops/stage65_mesh_raw_sources.json")

if __name__ == '__main__':
    main()

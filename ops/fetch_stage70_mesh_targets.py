# -*- coding: utf-8 -*-
"""
Fetch exact source post contents for Stage 70 internal link mesh
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
    # 1. best-things-to-do-in-sapa
    "sapa-travel-guide",
    "where-to-stay-in-sapa",
    "hanoi-to-sapa-transport",
    "sapa-trekking-routes-guide",

    # 2. best-things-to-do-in-ninh-binh
    "ninh-binh-travel-guide",
    "where-to-stay-in-ninh-binh",
    "hanoi-to-ninh-binh-transport",
    "hang-mua-ninh-binh-guide",

    # 3. best-things-to-do-in-ha-long-bay
    "ha-long-bay-travel-guide",
    "ha-long-bay-vs-lan-ha-bay",
    "hanoi-to-ha-long-bay-transport",
    "bai-tu-long-bay-guide",

    # 4. best-things-to-do-in-mui-ne
    "mui-ne-vs-nha-trang",
    "ho-chi-minh-city-to-mui-ne-transport",
    "where-to-stay-in-nha-trang",
    "best-beaches-in-vietnam",

    # 5. where-to-stay-in-mui-ne
    # mui-ne-vs-nha-trang already included
    # ho-chi-minh-city-to-mui-ne-transport already included
    "where-to-stay-in-vietnam-base-decisions",
    "best-day-trips-from-ho-chi-minh-city",

    # 6. best-things-to-do-in-quy-nhon
    "quy-nhon-travel-guide",
    # best-beaches-in-vietnam already included
    "vietnam-train-travel",
    "da-nang-travel-guide",

    # 7. ho-chi-minh-city-to-can-tho-transport
    "mekong-delta-travel-guide",
    "mekong-delta-floating-markets-guide",
    "transport-within-vietnam",
    "ho-chi-minh-city-travel-guide",
]

def main():
    unique_slugs = sorted(list(set(TARGET_SLUGS)))
    print(f"=== Fetching exact content for {len(unique_slugs)} unique target posts ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage70_slugs.json', 'w') as f:
        f.write(json.dumps(unique_slugs, ensure_ascii=False))

    php_script = """<?php
$slugs = json_decode(file_get_contents('/tmp/stage70_slugs.json'), true);
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

file_put_contents('/tmp/stage70_sources.json', json_encode($results, JSON_UNESCAPED_UNICODE));
echo "Exported " . count($results) . " posts.\\n";
"""
    with sftp.file('/tmp/fetch_stage70.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage70.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(stdout.read().decode('utf-8'))

    # Download stage70_sources.json locally
    sftp = ssh.open_sftp()
    ops_dir = os.path.dirname(os.path.abspath(__file__))
    local_out = os.path.join(ops_dir, 'stage70_mesh_sources.json')
    sftp.get('/tmp/stage70_sources.json', local_out)
    sftp.close()

    ssh.exec_command("rm -f /tmp/stage70_slugs.json /tmp/fetch_stage70.php /tmp/stage70_sources.json")
    ssh.close()
    print(f"Saved local sources to {local_out}")

if __name__ == '__main__':
    main()

# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 80: Fetch Mesh Target Posts
Exports post_content of target posts that will provide inbound links to Stage 80 pillars.
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
    # Host targets for sapa-to-ha-giang-transport
    'where-to-stay-in-sapa',
    'hanoi-to-sapa-transport',
    'hanoi-to-ha-giang-transport',
    'where-to-stay-in-ha-giang',
    # Host targets for ho-chi-minh-city-to-ben-tre-transport
    'where-to-stay-in-ben-tre',
    'ho-chi-minh-city-to-can-tho-transport',
    'where-to-stay-in-ho-chi-minh-city',
    'saigon-airport-to-district-1',
    'southern-vietnam-itinerary',
    # Host targets for cat-ba-to-ninh-binh-transport
    'where-to-stay-in-cat-ba',
    'where-to-stay-in-ninh-binh',
    'where-to-stay-in-tam-coc',
    'ninh-binh-to-ha-long-bay-transfer',
    'hanoi-to-cat-ba-island-transport',
    # Host targets for pleiku-to-kon-tum-transport
    'where-to-stay-in-pleiku',
    'where-to-stay-in-kon-tum',
    'buon-ma-thuot-to-pleiku-transport',
    'vietnam-scooter-rental-checklist',
    'central-highlands-vietnam-itinerary',
    # Host targets for where-to-stay-in-cam-ranh
    'where-to-stay-in-nha-trang',
    'nha-trang-to-quy-nhon-transport',
    'da-lat-to-nha-trang-transport',
    'da-nang-to-nha-trang-transport',
    'where-to-stay-in-vietnam-base-decisions',
    # Host targets for where-to-stay-in-rach-gia
    'where-to-stay-in-phu-quoc',
    'where-to-stay-in-ha-tien',
    'where-to-stay-in-can-tho',
    'how-to-get-to-con-dao-flight-vs-ferry',
    'mekong-delta-travel-guide',
    # Host targets for phu-quoc-ferry-guide
    'ho-chi-minh-city-to-phu-quoc-transport',
    'best-things-to-do-in-phu-quoc',
    'transport-within-vietnam',
    # Newly deployed pillars themselves
    'sapa-to-ha-giang-transport',
    'ho-chi-minh-city-to-ben-tre-transport',
    'cat-ba-to-ninh-binh-transport',
    'pleiku-to-kon-tum-transport',
    'where-to-stay-in-cam-ranh',
    'where-to-stay-in-rach-gia',
    'phu-quoc-ferry-guide',
]

def main():
    print(f"=== Fetching {len(TARGET_SLUGS)} Target Posts for Stage 80 Mesh ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage80_target_slugs.json', 'w') as f:
        f.write(json.dumps(list(set(TARGET_SLUGS))))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);
global $wpdb;

$slugs = json_decode(file_get_contents('/tmp/stage80_target_slugs.json'), true);
$results = [];

foreach ($slugs as $slug) {
    $post = get_page_by_path($slug, OBJECT, 'page');
    if (!$post) {
        $posts = get_posts([
            'name' => $slug,
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 1
        ]);
        if (!empty($posts)) {
            $post = $posts[0];
        }
    }

    if ($post) {
        $results[$slug] = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content
        ];
    } else {
        echo "NOT FOUND: $slug\\n";
    }
}

file_put_contents('/tmp/stage80_mesh_targets.json', json_encode($results));
"""
    with sftp.file('/tmp/fetch_stage80.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    print("Executing fetch via WP-CLI...")
    cmd = f"wp eval-file /tmp/fetch_stage80.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    output = stdout.read().decode('utf-8')
    print(output)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage80_mesh_targets.json', 'r') as f:
        data = json.load(f)
    sftp.close()

    ssh.exec_command("rm -f /tmp/fetch_stage80.php /tmp/stage80_target_slugs.json /tmp/stage80_mesh_targets.json")
    ssh.close()

    out_file = os.path.join(ops_dir, 'stage80_mesh_targets.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    print(f"Exported {len(data)} posts to {out_file}")

if __name__ == '__main__':
    main()

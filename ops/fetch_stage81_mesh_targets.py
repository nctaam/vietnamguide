# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 81: Fetch Mesh Target Posts
Exports post_content of target posts that will provide inbound links to Stage 81 pillars.
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
    # Host targets for ha-giang-to-cao-bang-transport
    'where-to-stay-in-cao-bang',
    'where-to-stay-in-ha-giang',
    'where-to-stay-in-meo-vac',
    'where-to-stay-in-dong-van',
    'sapa-to-ha-giang-transport',
    'northeast-vietnam-itinerary',
    # Host targets for sapa-to-mu-cang-chai-transport
    'where-to-stay-in-mu-cang-chai',
    'where-to-stay-in-sapa',
    'sapa-trekking',
    'northwest-vietnam-itinerary',
    # Host targets for can-tho-to-rach-gia-transport
    'where-to-stay-in-rach-gia',
    'where-to-stay-in-can-tho',
    'can-tho-to-chau-doc-transport',
    'phu-quoc-ferry-guide',
    'hcmc-to-can-tho',
    # Host targets for hue-to-dong-hoi-transport
    'where-to-stay-in-dong-hoi',
    'where-to-stay-in-hue',
    'where-to-stay-in-phong-nha',
    'phongnha-to-hue',
    'hue-to-hoi-an',
    # Host targets for da-lat-to-buon-ma-thuot-transport
    'where-to-stay-in-buon-ma-thuot',
    'where-to-stay-in-da-lat',
    'pleiku-to-kon-tum-transport',
    'where-to-stay-in-pleiku',
    'hcmc-to-da-lat',
    # Host targets for where-to-stay-in-soc-trang
    'how-to-get-to-con-dao-flight-vs-ferry',
    'where-to-stay-in-con-dao',
    'where-to-stay-in-ben-tre',
    # Host targets for vietnam-domestic-flights-guide
    'saigon-airport',
    'train-travel',
    'vietnam-night-train-safety-tips',
    'vietnam-sleeper-bus-survival-guide',
    'where-to-stay-in-cam-ranh',
    # The 7 new pillars themselves
    'ha-giang-to-cao-bang-transport',
    'sapa-to-mu-cang-chai-transport',
    'can-tho-to-rach-gia-transport',
    'hue-to-dong-hoi-transport',
    'da-lat-to-buon-ma-thuot-transport',
    'where-to-stay-in-soc-trang',
    'vietnam-domestic-flights-guide',
]

def main():
    print(f"=== Fetching {len(TARGET_SLUGS)} Target Posts for Stage 81 Mesh ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage81_target_slugs.json', 'w') as f:
        f.write(json.dumps(list(set(TARGET_SLUGS))))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);
global $wpdb;

$slugs = json_decode(file_get_contents('/tmp/stage81_target_slugs.json'), true);
$results = [];

foreach ($slugs as $slug) {
    $posts = get_posts([
        'name' => $slug,
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 1
    ]);

    if (!empty($posts)) {
        $p = $posts[0];
        $results[$slug] = [
            'id' => $p->ID,
            'title' => $p->post_title,
            'slug' => $p->post_name,
            'content' => $p->post_content,
            'parent_id' => $p->post_parent
        ];
    } else {
        $results[$slug] = null;
    }
}

file_put_contents('/tmp/stage81_raw_targets.json', json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Exported " . count(array_filter($results)) . " posts to /tmp/stage81_raw_targets.json\n";
"""
    with sftp.file('/tmp/fetch_stage81_targets.php', 'w') as f:
        f.write(php_script)

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/fetch_stage81_targets.php --path={WP_PATH} --allow-root")
    print(stdout.read().decode('utf-8'))

    local_out = os.path.join(ops_dir, 'stage81_raw_targets.json')
    sftp.get('/tmp/stage81_raw_targets.json', local_out)
    ssh.exec_command("rm -f /tmp/fetch_stage81_targets.php /tmp/stage81_target_slugs.json /tmp/stage81_raw_targets.json")
    sftp.close()
    ssh.close()
    print(f"Successfully saved targets to {local_out}")

if __name__ == '__main__':
    main()

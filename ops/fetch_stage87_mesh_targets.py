# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 87: Fetch Mesh Target Posts
Exports post_content of target posts that will provide inbound links to Stage 87 pillars.
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
    # Hosts for where-to-stay-in-yen-minh & ha-giang-to-dong-van-transport
    'ha-giang-loop-planning-guide',
    'ha-giang-safety-guide',
    'ha-giang-loop-cost-budget',
    'ha-giang-easy-rider-vs-self-drive',
    'where-to-stay-in-dong-van',
    'where-to-stay-in-meo-vac',
    'where-to-stay-in-du-gia',
    'where-to-stay-in-ha-giang',
    'hanoi-to-ha-giang-transport',

    # Hosts for where-to-stay-in-quang-ngai
    'where-to-stay-in-ly-son',
    'da-nang-to-ly-son-transport',
    'ly-son-travel-guide',
    'where-to-stay-in-quy-nhon',
    'quy-nhon-travel-guide',

    # Hosts for rach-gia-to-phu-quoc-ferry & can-tho-to-phu-quoc-transport
    'where-to-stay-in-rach-gia',
    'where-to-stay-in-ha-tien',
    'where-to-stay-in-can-tho',
    'can-tho-to-rach-gia-transport',
    'can-tho-to-ha-tien-transport',
    'ha-tien-to-phu-quoc-ferry',
    'ho-chi-minh-city-to-can-tho-transport',

    # Hosts for quy-nhon-to-hoi-an-transport
    'hoi-an-to-quy-nhon-transport',
    'da-nang-to-quy-nhon-transport',
    'best-things-to-do-in-quy-nhon',
    'where-to-stay-in-hoi-an',

    # Hosts for da-lat-to-pleiku-transport
    'where-to-stay-in-da-lat',
    'da-lat-travel-guide',
    'where-to-stay-in-pleiku',
    'pleiku-to-kon-tum-transport',
    'da-lat-to-buon-ma-thuot-transport',
    'buon-ma-thuot-to-pleiku-transport',
]

TARGET_SLUGS = sorted(list(set(TARGET_SLUGS)))

def main():
    print(f"Connecting to VPS to fetch {len(TARGET_SLUGS)} target posts...")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage87_fetch_slugs.json', 'w') as f:
        f.write(json.dumps(TARGET_SLUGS))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);

$slugs = json_decode(file_get_contents('/tmp/stage87_fetch_slugs.json'), true);
$out = [];

foreach ($slugs as $slug) {
    $posts = get_posts([
        'name' => $slug,
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 1
    ]);
    if (!empty($posts)) {
        $p = $posts[0];
        $out[$slug] = [
            'ID' => $p->ID,
            'post_title' => $p->post_title,
            'post_name' => $p->post_name,
            'post_parent' => $p->post_parent,
            'post_content' => $p->post_content
        ];
    } else {
        $out[$slug] = null;
    }
}

echo json_encode($out);
"""
    with sftp.file('/tmp/fetch_stage87_targets.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage87_targets.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    raw_out = stdout.read().decode('utf-8')
    err_out = stderr.read().decode('utf-8')

    ssh.exec_command("rm -f /tmp/stage87_fetch_slugs.json /tmp/fetch_stage87_targets.php")
    ssh.close()

    if err_out.strip():
        print(f"STDERR: {err_out}")

    # Extract JSON line
    lines = [l for l in raw_out.strip().splitlines() if l.strip().startswith('{')]
    if not lines:
        print("Failed to find JSON in output:\n", raw_out)
        sys.exit(1)

    data = json.loads(lines[-1])
    found_count = sum(1 for v in data.values() if v is not None)
    print(f"Fetched {found_count}/{len(TARGET_SLUGS)} target posts successfully!")

    out_file = os.path.join(ops_dir, 'stage87_mesh_targets.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    print(f"Saved to {out_file}")

if __name__ == '__main__':
    main()

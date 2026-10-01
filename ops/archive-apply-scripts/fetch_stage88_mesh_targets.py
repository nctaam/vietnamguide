# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 88: Fetch Mesh Target Posts
Exports post_content of target posts that will provide inbound links to Stage 88 pillars.
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
    # Hosts for where-to-stay-in-bac-ha & sapa-to-bac-ha-transport
    'sapa-travel-guide',
    'where-to-stay-in-sapa',
    'sapa-to-ha-giang-transport',
    'hanoi-to-sapa-transport',

    # Hosts for where-to-stay-in-bao-lac
    'ha-giang-to-cao-bang-transport',
    'where-to-stay-in-cao-bang',
    'where-to-stay-in-meo-vac',
    'cao-bang-travel-guide',

    # Hosts for where-to-stay-in-bao-loc
    'where-to-stay-in-da-lat',
    'da-lat-travel-guide',
    'da-lat-to-buon-ma-thuot-transport',
    'da-lat-to-mui-ne-transport',

    # Hosts for tran-de-to-con-dao-ferry & vung-tau-to-con-dao-ferry
    'how-to-get-to-con-dao-flight-vs-ferry',
    'where-to-stay-in-con-dao',
    'con-dao-travel-guide',
    'where-to-stay-in-soc-trang',
    'where-to-stay-in-vung-tau',

    # Hosts for pleiku-to-quy-nhon-transport
    'where-to-stay-in-pleiku',
    'where-to-stay-in-quy-nhon',
    'quy-nhon-travel-guide',
    'pleiku-to-kon-tum-transport',
]

TARGET_SLUGS = sorted(list(set(TARGET_SLUGS)))

def main():
    print(f"Connecting to VPS to fetch {len(TARGET_SLUGS)} target posts...")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage88_fetch_slugs.json', 'w') as f:
        f.write(json.dumps(TARGET_SLUGS))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);

$slugs = json_decode(file_get_contents('/tmp/stage88_fetch_slugs.json'), true);
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
    with sftp.file('/tmp/fetch_stage88_targets.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage88_targets.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    raw_out = stdout.read().decode('utf-8')
    err_out = stderr.read().decode('utf-8')

    ssh.exec_command("rm -f /tmp/stage88_fetch_slugs.json /tmp/fetch_stage88_targets.php")
    ssh.close()

    if err_out.strip():
        print(f"STDERR: {err_out}")

    lines = [l for l in raw_out.strip().splitlines() if l.strip().startswith('{')]
    if not lines:
        print("Failed to find JSON in output:\n", raw_out)
        sys.exit(1)

    data = json.loads(lines[-1])
    found_count = sum(1 for v in data.values() if v is not None)
    print(f"Fetched {found_count}/{len(TARGET_SLUGS)} target posts successfully!")

    out_file = os.path.join(ops_dir, 'stage88_mesh_targets.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    print(f"Saved to {out_file}")

if __name__ == '__main__':
    main()

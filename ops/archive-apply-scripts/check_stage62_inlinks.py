# -*- coding: utf-8 -*-
"""
Verify incoming internal links for the 7 Stage 62 pillars on Production VPS.
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

PILLARS = [
    {"slug": "saigon-airport-to-district-1", "path": "/plan/saigon-airport-to-district-1/"},
    {"slug": "da-nang-airport-to-hoi-an", "path": "/plan/da-nang-airport-to-hoi-an/"},
    {"slug": "grab-in-vietnam-guide", "path": "/plan/grab-in-vietnam-guide/"},
    {"slug": "tipping-in-vietnam", "path": "/plan/tipping-in-vietnam/"},
    {"slug": "vietnam-coffee-guide", "path": "/destinations/vietnam-coffee-guide/"},
    {"slug": "vietnam-in-march", "path": "/plan/vietnam-in-march/"},
    {"slug": "vietnam-in-november", "path": "/plan/vietnam-in-november/"},
]

def main():
    print("=== Checking Stage 62 Incoming Internal Links on VPS ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    php_script = """<?php
global $wpdb;
$posts = $wpdb->get_results("SELECT ID, post_name, post_content FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('page', 'post')");

$targets = [
    'saigon-airport-to-district-1' => '/plan/saigon-airport-to-district-1/',
    'da-nang-airport-to-hoi-an'    => '/plan/da-nang-airport-to-hoi-an/',
    'grab-in-vietnam-guide'        => '/plan/grab-in-vietnam-guide/',
    'tipping-in-vietnam'           => '/plan/tipping-in-vietnam/',
    'vietnam-coffee-guide'         => '/destinations/vietnam-coffee-guide/',
    'vietnam-in-march'             => '/plan/vietnam-in-march/',
    'vietnam-in-november'          => '/plan/vietnam-in-november/',
];

$results = [];
foreach ($targets as $slug => $path) {
    $results[$slug] = [];
    foreach ($posts as $p) {
        if ($p->post_name === $slug) continue;
        if (strpos($p->post_content, $path) !== false || strpos($p->post_content, $slug) !== false) {
            $results[$slug][] = "ID {$p->ID}: {$p->post_name}";
        }
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/check_stage62_inlinks.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/check_stage62_inlinks.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8')
    ssh.close()

    try:
        data = json.loads(out)
        all_passed = True
        for slug, inlinks in data.items():
            count = len(inlinks)
            status = "PASS" if count >= 4 else "FAIL"
            print(f"[{status}] {slug}: {count} in-links")
            for link in inlinks:
                print(f"       <- {link}")
            if count < 4:
                all_passed = False
        print("\nAll Stage 62 Pillars In-Link Check:", "100% PASSED" if all_passed else "FAILED")
    except Exception as e:
        print("Raw output:", out)
        print("Error parsing JSON:", e)

if __name__ == '__main__':
    main()

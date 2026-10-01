# -*- coding: utf-8 -*-
"""
Verify incoming internal links for the 7 Stage 63 pillars on Production VPS.
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
    {"slug": "where-to-stay-in-da-nang", "path": "/destinations/where-to-stay-in-da-nang/"},
    {"slug": "where-to-stay-in-hoi-an", "path": "/destinations/where-to-stay-in-hoi-an/"},
    {"slug": "ha-long-bay-day-trip-vs-overnight-cruise", "path": "/compare/ha-long-bay-day-trip-vs-overnight-cruise/"},
    {"slug": "hanoi-to-ha-long-bay-transport", "path": "/plan/hanoi-to-ha-long-bay-transport/"},
    {"slug": "da-nang-to-hue-train-vs-car", "path": "/compare/da-nang-to-hue-train-vs-car/"},
    {"slug": "vietnam-in-april", "path": "/plan/vietnam-in-april/"},
    {"slug": "tap-water-in-vietnam", "path": "/plan/tap-water-in-vietnam/"},
]

def main():
    print("=== Checking Stage 63 Incoming Internal Links on VPS ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    php_script = """<?php
global $wpdb;
$posts = $wpdb->get_results("SELECT ID, post_name, post_content FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('page', 'post')");

$targets = [
    'where-to-stay-in-da-nang'                => '/destinations/where-to-stay-in-da-nang/',
    'where-to-stay-in-hoi-an'                => '/destinations/where-to-stay-in-hoi-an/',
    'ha-long-bay-day-trip-vs-overnight-cruise' => '/compare/ha-long-bay-day-trip-vs-overnight-cruise/',
    'hanoi-to-ha-long-bay-transport'          => '/plan/hanoi-to-ha-long-bay-transport/',
    'da-nang-to-hue-train-vs-car'             => '/compare/da-nang-to-hue-train-vs-car/',
    'vietnam-in-april'                        => '/plan/vietnam-in-april/',
    'tap-water-in-vietnam'                    => '/plan/tap-water-in-vietnam/',
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
    with sftp.file('/tmp/check_stage63_inlinks.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/check_stage63_inlinks.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/check_stage63_inlinks.php")
    ssh.close()

    try:
        # locate json
        start_idx = out.find('{')
        data = json.loads(out[start_idx:])
        all_passed = True
        for slug, inlinks in data.items():
            count = len(inlinks)
            status = "PASS" if count >= 4 else "FAIL"
            print(f"[{status}] {slug}: {count} in-links")
            for link in inlinks:
                print(f"       <- {link}")
            if count < 4:
                all_passed = False
        print("\nAll Stage 63 Pillars In-Link Check:", "100% PASSED" if all_passed else "FAILED")
    except Exception as e:
        print("Raw output:", out)
        print("Error parsing JSON:", e)

if __name__ == '__main__':
    main()

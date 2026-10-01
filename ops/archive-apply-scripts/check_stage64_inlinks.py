# -*- coding: utf-8 -*-
"""
Verify incoming internal links for the 7 Stage 64 pillars on Production VPS.
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
    {"slug": "saigon-night-markets-guide", "path": "/destinations/saigon-night-markets-guide/"},
    {"slug": "hoi-an-street-food-guide", "path": "/destinations/hoi-an-street-food-guide/"},
    {"slug": "tam-coc-vs-trang-an-boat-tour", "path": "/compare/tam-coc-vs-trang-an-boat-tour/"},
    {"slug": "sapa-trekking-routes-guide", "path": "/plan/sapa-trekking-routes-guide/"},
    {"slug": "quy-nhon-to-phu-yen-coastal-drive", "path": "/plan/quy-nhon-to-phu-yen-coastal-drive/"},
    {"slug": "cham-islands-day-trip-guide", "path": "/plan/cham-islands-day-trip-guide/"},
    {"slug": "vietnam-in-may", "path": "/plan/vietnam-in-may/"},
]

def main():
    print("=== Checking Stage 64 Incoming Internal Links on VPS ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    php_script = """<?php
global $wpdb;
$posts = $wpdb->get_results("SELECT ID, post_name, post_content FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('page', 'post')");

$targets = [
    'saigon-night-markets-guide'          => '/destinations/saigon-night-markets-guide/',
    'hoi-an-street-food-guide'            => '/destinations/hoi-an-street-food-guide/',
    'tam-coc-vs-trang-an-boat-tour'       => '/compare/tam-coc-vs-trang-an-boat-tour/',
    'sapa-trekking-routes-guide'          => '/plan/sapa-trekking-routes-guide/',
    'quy-nhon-to-phu-yen-coastal-drive'   => '/plan/quy-nhon-to-phu-yen-coastal-drive/',
    'cham-islands-day-trip-guide'         => '/plan/cham-islands-day-trip-guide/',
    'vietnam-in-may'                      => '/plan/vietnam-in-may/',
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
    with sftp.file('/tmp/check_stage64_inlinks.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/check_stage64_inlinks.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    raw_json = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')

    ssh.exec_command("rm -f /tmp/check_stage64_inlinks.php")
    ssh.close()

    if err.strip():
        print("Stderr:", err)

    results = json.loads(raw_json)
    all_pass = True
    for slug, links in results.items():
        count = len(links)
        status = "[PASS]" if count >= 4 else "[WARN/FAIL]"
        if count < 4:
            all_pass = False
        print(f"{status} {slug:38s}: {count} incoming links")
        for link in links:
            print(f"    <- {link}")

    print("\n=======================================================")
    if all_pass:
        print("ALL 7 STAGE 64 PILLARS HAVE >= 4 INBOUND INTERNAL LINKS!")
    else:
        print("SOME PILLARS HAVE FEWER THAN 4 INBOUND LINKS.")
    print("=======================================================")
    return 0 if all_pass else 1

if __name__ == '__main__':
    sys.exit(main())

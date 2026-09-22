# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 66: Verify Inbound Link Counts for 7 New Pillars
"""

import paramiko
import json
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

PILLARS = [
    "hanoi-train-street-guide",
    "cu-chi-tunnels-ben-duoc-vs-ben-dinh",
    "ba-na-hills-golden-bridge-guide",
    "vietnam-to-cambodia-border-crossings",
    "hanoi-to-cat-ba-island-transport",
    "hue-street-food-guide",
    "vietnam-in-july"
]

def main():
    print("=== Auditing Inbound Links for Stage 66 Pillars ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    php_script = """<?php
$pillars = json_decode(file_get_contents('/tmp/stage66_check_pillars.json'), true);

$all_pages = get_posts([
    'post_type' => 'page',
    'post_status' => 'publish',
    'posts_per_page' => -1
]);

$inlinks = [];
foreach ($pillars as $pil) {
    $inlinks[$pil] = [];
}

foreach ($all_pages as $p) {
    $content = $p->post_content;
    $slug = $p->post_name;
    $id = $p->ID;

    foreach ($pillars as $pil) {
        if ($slug === $pil) continue;
        if (strpos($content, '/' . $pil . '/') !== false) {
            $inlinks[$pil][] = [
                'id' => $id,
                'slug' => $slug,
                'title' => $p->post_title
            ];
        }
    }
}

echo json_encode($inlinks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
"""

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage66_check_pillars.json', 'w') as f:
        f.write(json.dumps(PILLARS))
    with sftp.file('/tmp/check_stage66_inlinks.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/check_stage66_inlinks.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    raw = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/stage66_check_pillars.json /tmp/check_stage66_inlinks.php")
    ssh.close()

    try:
        results = json.loads(raw)
        all_passed = True
        for pil, links in results.items():
            cnt = len(links)
            status = "PASS" if cnt >= 4 else "FAIL"
            if cnt < 4: all_passed = False
            print(f"\n[{status}] {pil}: {cnt} inbound link(s)")
            for l in links:
                print(f"    - ID {l['id']}: {l['slug']}")
        
        print("\n" + "=" * 60)
        if all_passed:
            print("SUCCESS: All 7 pillars have >= 4 inbound contextual links!")
        else:
            print("ERROR: One or more pillars have < 4 inbound links.")
            sys.exit(1)
    except Exception as e:
        print("Failed to parse JSON output:", e)
        print("Raw output was:", raw)
        sys.exit(1)

if __name__ == '__main__':
    main()

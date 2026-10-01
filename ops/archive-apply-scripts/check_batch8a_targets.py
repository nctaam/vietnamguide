# -*- coding: utf-8 -*-
"""
VietnamGuide: Inspect Batch 8A Target Posts (28 Practical Guides)
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

BATCH_8A_IDS = [
    13, 497, 520, 521, 522, 524, 526, 613, 645, 646,
    647, 648, 686, 719, 754, 789, 1037, 1068, 1099, 1141,
    1142, 1171, 1204, 1205, 1235, 1277, 1316, 1354
]

def main():
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=20)

    php_script = f"""<?php
global $wpdb;
$ids = implode(',', {json.dumps(BATCH_8A_IDS)});
$posts = $wpdb->get_results("
    SELECT ID, post_name, post_title, post_parent,
           LOCATE('vg-concierge-verdict', post_content) as has_verdict,
           LOCATE('<h2', post_content) as has_h2,
           LOCATE('vg-guide-photo', post_content) as has_photo
    FROM {{$wpdb->posts}}
    WHERE ID IN ($ids)
    ORDER BY FIELD(ID, $ids)
", ARRAY_A);
echo json_encode($posts);
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/check_8a.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/check_8a.php --path={WP_PATH} --allow-root")
    raw = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/check_8a.php")
    ssh.close()

    json_line = [l for l in raw.strip().splitlines() if l.strip().startswith('[')][-1]
    posts = json.loads(json_line)

    print(f"Total target posts inspected: {len(posts)}")
    for p in posts:
        print(f"ID {p['ID']}: [{p['post_name']}] - Verdict: {int(p['has_verdict'])>0}, H2: {int(p['has_h2'])>0}, Photo: {int(p['has_photo'])>0} | {p['post_title']}")

if __name__ == '__main__':
    main()

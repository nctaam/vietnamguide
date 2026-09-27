# -*- coding: utf-8 -*-
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

BATCH4_CANDIDATES = [
    (1206, 'where-to-stay-in-dien-bien-phu'),
    (1233, 'where-to-stay-in-ben-tre'),
    (1234, 'where-to-stay-in-lang-co'),
    (1275, 'where-to-stay-in-cam-ranh'),
    (1276, 'where-to-stay-in-rach-gia'),
    (1315, 'where-to-stay-in-soc-trang'),
    (1352, 'where-to-stay-in-bac-lieu'),
    (1353, 'where-to-stay-in-ca-mau'),
    (1387, 'where-to-stay-in-hai-phong'),
    (1390, 'where-to-stay-in-phan-rang'),
    (1392, 'where-to-stay-in-tam-dao'),
    (1428, 'where-to-stay-in-vinh-hy'),
    (1453, 'where-to-stay-in-du-gia'),
    (1454, 'where-to-stay-in-mang-den'),
    (1459, 'where-to-stay-in-an-giang'),
    (1494, 'where-to-stay-in-ly-son'),
    (1529, 'where-to-stay-in-yen-minh'),
    (1530, 'where-to-stay-in-quang-ngai'),
    (1561, 'where-to-stay-in-bac-ha'),
    (1562, 'where-to-stay-in-bao-lac'),
    (1563, 'where-to-stay-in-bao-loc')
]

def main():
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    post_ids = [c[0] for c in BATCH4_CANDIDATES]
    php_code = f"""<?php
global $wpdb;
$ids = implode(',', {json.dumps(post_ids)});
$rows = $wpdb->get_results("SELECT ID, post_name, post_status, post_type, post_content FROM {{$wpdb->posts}} WHERE ID IN ($ids);", ARRAY_A);
$out = [];
foreach ($rows as $r) {{
    $c = $r['post_content'];
    $out[] = [
        'ID' => (int)$r['ID'],
        'slug' => $r['post_name'],
        'status' => $r['post_status'],
        'type' => $r['post_type'],
        'has_verdict' => strpos($c, 'vg-concierge-verdict') !== false,
        'has_photo' => strpos($c, 'vg-guide-photo') !== false,
        'permalink' => get_permalink((int)$r['ID'])
    ];
}}
echo json_encode($out);
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/check_batch4_anchors.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/check_batch4_anchors.php --path={WP_PATH} --allow-root")
    res_str = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/check_batch4_anchors.php")
    ssh.close()

    data = json.loads([l for l in res_str.splitlines() if l.strip().startswith('[')][-1])
    print(f"Checked {len(data)} candidates for Batch 4:")
    verdict_count = sum(1 for r in data if r['has_verdict'])
    photo_count = sum(1 for r in data if r['has_photo'])
    print(f"  Has vg-concierge-verdict: {verdict_count}/{len(data)}")
    print(f"  Has existing vg-guide-photo: {photo_count}/{len(data)}")
    for r in data:
        print(f"[{r['ID']}] {r['slug']} -> status={r['status']}, verdict={r['has_verdict']}, photo={r['has_photo']}, url={r['permalink']}")

if __name__ == '__main__':
    main()

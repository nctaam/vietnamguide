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

BATCH5_CANDIDATES = [
    (1272, 'ho-chi-minh-city-to-ben-tre-transport'),
    (1273, 'cat-ba-to-ninh-binh-transport'),
    (1274, 'pleiku-to-kon-tum-transport'),
    (1311, 'sapa-to-mu-cang-chai-transport'),
    (1312, 'can-tho-to-rach-gia-transport'),
    (1313, 'hue-to-dong-hoi-transport'),
    (1314, 'da-lat-to-buon-ma-thuot-transport'),
    (1348, 'hoi-an-to-quy-nhon-transport'),
    (1349, 'hanoi-to-hai-phong-transport'),
    (1350, 'can-tho-to-ha-tien-transport'),
    (1351, 'buon-ma-thuot-to-nha-trang-transport'),
    (1388, 'dong-hoi-to-phong-nha-transport'),
    (1389, 'can-tho-to-ca-mau-transport'),
    (1391, 'hanoi-to-tam-dao-transport'),
    (1393, 'kon-tum-to-da-nang-transport'),
    (1425, 'ha-long-to-ninh-binh-transport'),
    (1426, 'dong-hoi-to-hue-transport'),
    (1429, 'quy-nhon-to-nha-trang-transport'),
    (1455, 'nha-trang-to-da-lat-transport'),
    (1456, 'ninh-binh-to-phong-nha-transport'),
    (1457, 'cao-bang-to-ba-be-transport'),
    (1458, 'ho-chi-minh-city-to-chau-doc-transport'),
    (1497, 'da-nang-to-ly-son-transport'),
    (1498, 'hue-to-phong-nha-transport'),
    (1499, 'mai-chau-to-pu-luong-transport'),
    (1531, 'ha-giang-to-dong-van-transport'),
    (1533, 'can-tho-to-phu-quoc-transport'),
    (1534, 'quy-nhon-to-hoi-an-transport'),
    (1535, 'da-lat-to-pleiku-transport'),
    (1564, 'sapa-to-bac-ha-transport'),
    (1567, 'pleiku-to-quy-nhon-transport')
]

def main():
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    post_ids = [c[0] for c in BATCH5_CANDIDATES]
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
    with sftp.file('/tmp/check_batch5_anchors.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/check_batch5_anchors.php --path={WP_PATH} --allow-root")
    res_str = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/check_batch5_anchors.php")
    ssh.close()

    data = json.loads([l for l in res_str.splitlines() if l.strip().startswith('[')][-1])
    print(f"Checked {len(data)} candidates for Batch 5:")
    verdict_count = sum(1 for r in data if r['has_verdict'])
    photo_count = sum(1 for r in data if r['has_photo'])
    print(f"  Has vg-concierge-verdict: {verdict_count}/{len(data)}")
    print(f"  Has existing vg-guide-photo: {photo_count}/{len(data)}")
    for r in data:
        print(f"[{r['ID']}] {r['slug']} -> status={r['status']}, verdict={r['has_verdict']}, photo={r['has_photo']}, url={r['permalink']}")

if __name__ == '__main__':
    main()

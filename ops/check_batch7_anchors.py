# -*- coding: utf-8 -*-
"""
Check anchors, status, and permalinks for all 22 Batch 7 candidates on Production.
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

BATCH7_CANDIDATES = [
    # 18 Destination Guides (Parent 7)
    (110, 'best-places-to-visit-vietnam'),
    (499, 'hoi-an-ancient-town-guide'),
    (500, 'hue-imperial-city-guide'),
    (501, 'da-nang-beaches-guide'),
    (503, 'phong-nha-travel-guide'),
    (518, 'sapa-travel-guide'),
    (519, 'ha-giang-loop-planning-guide'),
    (525, 'where-to-stay-in-sapa'),
    (528, 'mu-cang-chai-travel-guide'),
    (529, 'pu-luong-travel-guide'),
    (611, 'da-lat-travel-guide'),
    (612, 'cao-bang-travel-guide'),
    (626, 'phu-yen-travel-guide'),
    (922, 'best-things-to-do-in-ninh-binh'),
    (924, 'best-things-to-do-in-mui-ne'),
    (926, 'best-things-to-do-in-quy-nhon'),
    (1495, 'mang-den-travel-guide'),
    (1496, 'ba-be-lake-travel-guide'),
    # 4 Cost Budget Guides (Parent 10)
    (600, 'ha-giang-loop-cost-budget'),
    (601, 'da-nang-hoi-an-budget'),
    (602, 'hanoi-ninh-binh-ha-long-budget'),
    (1002, 'ho-chi-minh-city-mekong-budget')
]

def main():
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    post_ids = [c[0] for c in BATCH7_CANDIDATES]
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
        'has_h2' => strpos($c, '<h2') !== false,
        'permalink' => get_permalink((int)$r['ID'])
    ];
}}
echo json_encode($out);
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/check_batch7_anchors.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/check_batch7_anchors.php --path={WP_PATH} --allow-root")
    res_str = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/check_batch7_anchors.php")
    ssh.close()

    data = json.loads([l for l in res_str.splitlines() if l.strip().startswith('[')][-1])
    print(f"Checked {len(data)} candidates for Batch 7:")
    verdict_count = sum(1 for r in data if r['has_verdict'])
    photo_count = sum(1 for r in data if r['has_photo'])
    h2_count = sum(1 for r in data if r['has_h2'])
    print(f"  Has vg-concierge-verdict: {verdict_count}/{len(data)}")
    print(f"  Has existing vg-guide-photo: {photo_count}/{len(data)}")
    print(f"  Has h2 tag: {h2_count}/{len(data)}")
    for r in data:
        print(f"[{r['ID']}] {r['slug']} -> status={r['status']}, verdict={r['has_verdict']}, photo={r['has_photo']}, url={r['permalink']}")

if __name__ == '__main__':
    main()

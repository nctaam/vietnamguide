# -*- coding: utf-8 -*-
"""
Check anchors, status, and permalinks for all 26 Batch 6 candidates on Production.
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

BATCH6_CANDIDATES = [
    # 8 Itineraries
    (682, 'ha-long-bay-day-trip-vs-overnight-cruise'),
    (969, 'ho-chi-minh-city-in-2-days'),
    (1000, 'vietnam-family-travel-guide'),
    (1001, 'central-vietnam-itinerary'),
    (1038, 'southern-vietnam-itinerary'),
    (1069, 'northwest-vietnam-itinerary'),
    (1100, 'central-highlands-vietnam-itinerary'),
    (1140, 'northeast-vietnam-itinerary'),
    # 9 Comparisons
    (104, 'north-central-south-vietnam'),
    (301, 'old-quarter-vs-french-quarter-vs-west-lake'),
    (498, 'hanoi-vs-ho-chi-minh-city'),
    (502, 'mekong-delta-overnight-vs-day-trip'),
    (504, 'sapa-vs-ha-giang'),
    (523, 'ha-giang-easy-rider-vs-self-drive'),
    (527, 'vietnam-rice-terraces-guide'),
    (627, 'ly-son-vs-cham-islands'),
    (752, 'con-dao-vs-phu-quoc'),
    # 9 Weather Months
    (650, 'vietnam-in-march'),
    (685, 'vietnam-in-april'),
    (721, 'vietnam-in-may'),
    (757, 'vietnam-in-june'),
    (792, 'vietnam-in-july'),
    (820, 'vietnam-in-august'),
    (826, 'vietnam-in-september'),
    (854, 'vietnam-in-october'),
    (651, 'vietnam-in-november')
]

def main():
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    post_ids = [c[0] for c in BATCH6_CANDIDATES]
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
    with sftp.file('/tmp/check_batch6_anchors.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/check_batch6_anchors.php --path={WP_PATH} --allow-root")
    res_str = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/check_batch6_anchors.php")
    ssh.close()

    data = json.loads([l for l in res_str.splitlines() if l.strip().startswith('[')][-1])
    print(f"Checked {len(data)} candidates for Batch 6:")
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

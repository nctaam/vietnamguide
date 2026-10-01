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

BATCH3_CANDIDATES = [
    (614, 'hanoi-street-food-guide'),
    (615, 'saigon-street-food-guide'),
    (616, 'da-nang-street-food-guide'),
    (649, 'vietnam-coffee-guide'),
    (715, 'saigon-night-markets-guide'),
    (716, 'hoi-an-street-food-guide'),
    (791, 'hue-street-food-guide'),
    (822, 'mekong-delta-floating-markets-guide'),
    (824, 'da-lat-coffee-farms-guide'),
    (1039, 'vietnam-vegetarian-travel-guide'),
    (1071, 'vietnam-gluten-free-travel-guide'),
    (1102, 'vietnam-craft-beer-guide'),
    (921, 'best-things-to-do-in-sapa'),
    (628, 'hang-mua-ninh-binh-guide'),
    (629, 'phong-nha-cave-treks'),
    (717, 'tam-coc-vs-trang-an-boat-tour'),
    (718, 'sapa-trekking-routes-guide'),
    (720, 'cham-islands-day-trip-guide'),
    (751, 'phu-quoc-beaches-guide'),
    (753, 'da-lat-waterfalls-guide'),
    (786, 'hanoi-train-street-guide'),
    (787, 'cu-chi-tunnels-ben-duoc-vs-ben-dinh'),
    (788, 'ba-na-hills-golden-bridge-guide'),
    (825, 'hoi-an-tailoring-guide'),
    (855, 'best-things-to-do-in-ho-chi-minh-city'),
    (858, 'best-things-to-do-in-da-nang'),
    (889, 'best-things-to-do-in-da-lat'),
    (890, 'best-things-to-do-in-nha-trang'),
    (891, 'best-things-to-do-in-phu-quoc'),
    (923, 'best-things-to-do-in-ha-long-bay'),
]

def main():
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    post_ids = [c[0] for c in BATCH3_CANDIDATES]
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
        'has_h2' => preg_match('#<h2\\b#i', $c) === 1,
        'has_photo' => strpos($c, 'vg-guide-photo') !== false,
        'permalink' => get_permalink((int)$r['ID'])
    ];
}}
echo json_encode($out);
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/check_batch3_anchors.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/check_batch3_anchors.php --path={WP_PATH} --allow-root")
    res_str = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/check_batch3_anchors.php")
    ssh.close()

    data = json.loads([l for l in res_str.splitlines() if l.strip().startswith('[')][-1])
    print(f"Checked {len(data)} candidates:")
    verdict_count = sum(1 for r in data if r['has_verdict'])
    photo_count = sum(1 for r in data if r['has_photo'])
    print(f"  Has vg-concierge-verdict: {verdict_count}/{len(data)}")
    print(f"  Has existing vg-guide-photo: {photo_count}/{len(data)}")
    for r in data:
        if r['has_photo']:
            print(f"  --> Existing photo in: [{r['ID']}] {r['slug']}")
    for r in data:
        print(f"[{r['ID']}] {r['slug']} -> status={r['status']}, verdict={r['has_verdict']}, url={r['permalink']}")

if __name__ == '__main__':
    main()

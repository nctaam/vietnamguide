# -*- coding: utf-8 -*-
"""
Fetch exact source post contents for Stage 64 internal link mesh
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

TARGET_SLUGS = [
    # For saigon-night-markets-guide
    "ho-chi-minh-city-travel-guide",
    "vietnam-food-safety-street-food-etiquette",
    "where-to-stay-in-ho-chi-minh-city",
    "saigon-street-food-guide",
    # For hoi-an-street-food-guide
    "hoi-an-ancient-town-guide",
    "best-things-to-do-in-hoi-an",
    "where-to-stay-in-hoi-an",
    "da-nang-street-food-guide",
    # For tam-coc-vs-trang-an-boat-tour
    "ninh-binh-travel-guide",
    "ninh-binh-day-trip-vs-overnight",
    "tam-coc-travel-guide",
    "hang-mua-ninh-binh-guide",
    # For sapa-trekking-routes-guide
    "sapa-travel-guide",
    "sapa-trekking-guided-vs-self-guided",
    "where-to-stay-in-sapa",
    "sapa-vs-ha-giang",
    # For quy-nhon-to-phu-yen-coastal-drive
    "quy-nhon-travel-guide",
    "phu-yen-travel-guide",
    "transport-within-vietnam",
    "21-days-in-vietnam",
    # For cham-islands-day-trip-guide
    "cham-islands-travel-guide",
    "ly-son-vs-cham-islands",
    "da-nang-beaches-guide",
    "where-to-stay-in-da-nang",
    # For vietnam-in-may
    "best-time-to-visit-vietnam",
    "vietnam-in-april",
    "vietnam-in-march",
    "vietnam-rainy-season-flexible-route"
]

def main():
    print(f"=== Fetching exact content for {len(TARGET_SLUGS)} target posts ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    slugs_sql = "'" + "','".join(TARGET_SLUGS) + "'"
    php_script = f"""<?php
global $wpdb;
$slugs = [{slugs_sql}];
$in_clause = "'" . implode("','", array_map('esc_sql', $slugs)) . "'";
$sql = "SELECT ID, post_name, post_title, post_content FROM {{$wpdb->prefix}}posts WHERE post_name IN ($in_clause) AND post_type IN ('page', 'post') AND post_status = 'publish'";
$rows = $wpdb->get_results($sql, ARRAY_A);
$data = [];
foreach ($rows as $r) {{
    $data[$r['post_name']] = [
        'ID' => (int)$r['ID'],
        'post_name' => $r['post_name'],
        'post_title' => $r['post_title'],
        'post_content' => $r['post_content']
    ];
}}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
"""

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/fetch_stage64_mesh.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage64_mesh.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    raw_json = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')

    ssh.exec_command("rm -f /tmp/fetch_stage64_mesh.php")
    ssh.close()

    if err.strip():
        print("Stderr:", err)

    data = json.loads(raw_json)
    print(f"Retrieved {len(data)} posts.")
    for slug in TARGET_SLUGS:
        if slug in data:
            print(f"  [OK] {slug} (ID: {data[slug]['ID']}, length: {len(data[slug]['post_content'])})")
        else:
            print(f"  [MISSING] {slug}")

    with open('ops/stage64_mesh_raw_sources.json', 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print("Saved to ops/stage64_mesh_raw_sources.json")

if __name__ == '__main__':
    main()

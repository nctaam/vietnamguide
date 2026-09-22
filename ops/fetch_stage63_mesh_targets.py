# -*- coding: utf-8 -*-
"""
Fetch exact source post contents for Stage 63 internal link mesh
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
    "da-nang-travel-guide",
    "da-nang-beaches-guide",
    "da-nang-vs-hoi-an",
    "where-to-stay-in-vietnam-base-decisions",
    "hoi-an-ancient-town-guide",
    "best-things-to-do-in-hoi-an",
    "ha-long-bay-travel-guide",
    "ha-long-bay-vs-lan-ha-bay",
    "ha-long-bay-cruise-questions-before-booking",
    "best-day-trips-from-hanoi",
    "transport-within-vietnam",
    "hanoi-travel-guide",
    "ninh-binh-to-ha-long-bay-transfer",
    "vietnam-train-travel",
    "hue-imperial-city-guide",
    "best-time-to-visit-vietnam",
    "vietnam-in-march",
    "10-days-in-vietnam",
    "best-places-to-visit-vietnam",
    "health-travel-insurance-vietnam",
    "vietnam-food-safety-street-food-etiquette",
    "what-to-pack-for-vietnam-region-season",
    "vietnam-first-trip-planning-checklist"
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
        'id' => (int)$r['ID'],
        'slug' => $r['post_name'],
        'title' => $r['post_title'],
        'content' => $r['post_content']
    ];
}}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
"""

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/fetch_stage63_sources.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/fetch_stage63_sources.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    raw_json = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    if err.strip():
        print("Stderr:", err)

    ssh.exec_command("rm -f /tmp/fetch_stage63_sources.php")
    ssh.close()

    # Find where json starts
    start_idx = raw_json.find('{')
    if start_idx == -1:
        print("Failed to find JSON in output:", raw_json)
        return

    json_str = raw_json[start_idx:]
    data = json.loads(json_str)

    print(f"Fetched {len(data)} target posts successfully.")
    missing = set(TARGET_SLUGS) - set(data.keys())
    if missing:
        print(f"[WARNING] Missing slugs: {missing}")
    else:
        print("[SUCCESS] All 23 unique parent slugs located perfectly!")

    with open('ops/stage63_mesh_raw_sources.json', 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print("Saved to ops/stage63_mesh_raw_sources.json")

if __name__ == '__main__':
    main()

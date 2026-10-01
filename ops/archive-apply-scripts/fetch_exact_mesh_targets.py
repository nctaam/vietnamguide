# -*- coding: utf-8 -*-
"""
Fetch exact source post contents for Stage 62 link mesh
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

TARGET_MAP = {
    262: "ho-chi-minh-city-travel-guide",
    281: "where-to-stay-in-ho-chi-minh-city",
    476: "vietnam-airport-arrival-checklist",
    615: "saigon-street-food-guide",
    213: "da-nang-travel-guide",
    499: "hoi-an-ancient-town-guide",
    209: "da-nang-vs-hoi-an",
    155: "transport-within-vietnam",
    15: "sim-esim-vietnam",
    158: "money-cash-cards-atms",
    181: "safety-scams-vietnam",
    22: "vietnam-travel-cost",
    480: "vietnam-food-safety-street-food-etiquette",
    473: "vietnam-first-trip-planning-checklist",
    614: "hanoi-street-food-guide",
    611: "da-lat-travel-guide",
    287: "hanoi-travel-guide",
    14: "best-time-to-visit-vietnam",
    495: "vietnam-in-february",
    493: "vietnam-in-december",
    482: "vietnam-rainy-season-flexible-route",
    19: "10-days-in-vietnam",
    20: "14-days-in-vietnam",
    110: "best-places-to-visit-vietnam"
}

pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

php_export = """<?php
global $wpdb;
$ids = [" . implode(',', array_keys($GLOBALS['TARGET_MAP'])) . "];
$sql = "SELECT ID, post_name, post_content FROM {$wpdb->prefix}posts WHERE ID IN (" . implode(',', array_keys($GLOBALS['TARGET_MAP'])) . ")";
$rows = $wpdb->get_results($sql, ARRAY_A);
$data = [];
foreach ($rows as $r) {
    $data[$r['ID']] = [
        'slug' => $r['post_name'],
        'content' => $r['post_content']
    ];
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
"""

# Format PHP script directly with IDs
id_list = ','.join(str(k) for k in TARGET_MAP.keys())
php_script = f"""<?php
global $wpdb;
$sql = "SELECT ID, post_name, post_content FROM {{$wpdb->prefix}}posts WHERE ID IN ({id_list})";
$rows = $wpdb->get_results($sql, ARRAY_A);
$data = [];
foreach ($rows as $r) {{
    $data[$r['ID']] = [
        'slug' => $r['post_name'],
        'content' => $r['post_content']
    ];
}}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
"""

sftp = ssh.open_sftp()
with sftp.file('/tmp/export_stage62_exact.php', 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = ssh.exec_command(f'wp eval-file /tmp/export_stage62_exact.php --path={WP_PATH} --allow-root')
out = stdout.read().decode('utf-8')
ssh.exec_command('rm -f /tmp/export_stage62_exact.php')
ssh.close()

data = json.loads(out)
with open('ops/stage62_source_contents.json', 'w', encoding='utf-8') as f:
    json.dump(data, f, indent=2, ensure_ascii=False)

print(f"Successfully fetched all {len(data)} source posts.")
for pid, info in data.items():
    print(f"  ID {pid} ({info['slug']}): {len(info['content'])} chars")

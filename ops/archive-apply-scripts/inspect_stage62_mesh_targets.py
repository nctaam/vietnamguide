# -*- coding: utf-8 -*-
"""
Inspect exact paragraphs in parent posts for Stage 62 internal link mesh
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

# Target post IDs to inspect:
# 14: ho-chi-minh-city-travel-guide
# 349: where-to-stay-in-ho-chi-minh-city
# 334: vietnam-airport-arrival-checklist
# 615: saigon-street-food-guide
# 213: da-nang-travel-guide
# 499: hoi-an-ancient-town-guide
# 209: da-nang-vs-hoi-an
# 24: transport-within-vietnam
# 18: sim-esim-vietnam
# 26: money-cash-cards-atms
# 30: safety-scams-vietnam
# 22: vietnam-travel-cost
# 176: vietnam-food-safety-street-food-etiquette
# 319: vietnam-first-trip-planning-checklist
# 614: hanoi-street-food-guide
# 611: da-lat-travel-guide
# 287: hanoi-travel-guide
# 17: best-time-to-visit-vietnam
# 495: vietnam-in-february
# 493: vietnam-in-december
# 482: vietnam-rainy-season-flexible-route

TARGET_IDS = [14, 349, 334, 615, 213, 499, 209, 24, 18, 26, 30, 22, 176, 319, 614, 611, 287, 17, 495, 493, 482]

pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

php_export = f"""<?php
$ids = [{','.join(str(x) for x in TARGET_IDS)}];
$posts = get_posts([
    'include' => $ids,
    'post_type' => 'page',
    'numberposts' => -1
]);
$data = [];
foreach ($posts as $p) {{
    $data[$p->ID] = [
        'slug' => $p->post_name,
        'content' => $p->post_content
    ];
}}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
"""

sftp = ssh.open_sftp()
with sftp.file('/tmp/export_stage62_mesh.php', 'w') as f:
    f.write(php_export)
sftp.close()

stdin, stdout, stderr = ssh.exec_command(f'wp eval-file /tmp/export_stage62_mesh.php --path={WP_PATH} --allow-root')
out = stdout.read().decode('utf-8')
ssh.exec_command('rm -f /tmp/export_stage62_mesh.php')
ssh.close()

with open('ops/stage62_mesh_raw_sources.json', 'w', encoding='utf-8') as f:
    f.write(out)

sources = json.loads(out)
print(f"Exported {len(sources)} parent posts for mesh targeting.")
for pid, info in sources.items():
    print(f"ID {pid} ({info['slug']}): {len(info['content'])} chars")

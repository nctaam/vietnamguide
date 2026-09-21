# -*- coding: utf-8 -*-
import paramiko
import json

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
KEY_PATH = r'C:\Users\NCTaam\.ssh\deploy_bot_key'

pkey = paramiko.Ed25519Key.from_private_key_file(KEY_PATH)
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey)

post_ids = [480, 287, 262, 213, 320, 501, 209, 110]
id_list = ','.join(str(x) for x in post_ids)

php_export = f"""<?php
$posts = get_posts([
    'include' => [{id_list}],
    'post_type' => 'page',
    'numberposts' => -1
]);
$data = [];
foreach ($posts as $p) {{
    $data[$p->ID] = [
        'slug' => $p->post_name,
        'title' => $p->post_title,
        'content' => $p->post_content
    ];
}}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
"""

sftp = ssh.open_sftp()
with sftp.file('/tmp/export_sources.php', 'w') as f:
    f.write(php_export)
sftp.close()

stdin, stdout, stderr = ssh.exec_command('wp eval-file /tmp/export_sources.php --path=/usr/local/lsws/vietnamguide.net/html --allow-root')
out = stdout.read().decode('utf-8', errors='ignore')
ssh.exec_command('rm -f /tmp/export_sources.php')
ssh.close()

with open('ops/stage61_mesh_sources.json', 'w', encoding='utf-8') as f:
    f.write(out)

data = json.loads(out)
print(f"Successfully fetched {len(data)} source posts.")
for pid, info in data.items():
    print(f"  ID {pid}: {info['slug']} ({len(info['content'])} chars)")

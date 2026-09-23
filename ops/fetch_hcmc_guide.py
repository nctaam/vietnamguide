# -*- coding: utf-8 -*-
import paramiko
import json
import os

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

php_script = """<?php
$p = get_posts([
    'name' => 'ho-chi-minh-city-travel-guide',
    'post_type' => 'page',
    'post_status' => 'publish',
    'posts_per_page' => 1
]);
if (!empty($p)) {
    $post = $p[0];
    echo json_encode([
        'ID' => $post->ID,
        'title' => $post->post_title,
        'content' => $post->post_content
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo "NOT_FOUND";
}
"""

sftp = ssh.open_sftp()
with sftp.file('/tmp/fetch_hcmc.php', 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/fetch_hcmc.php --path={WP_PATH} --allow-root")
out = stdout.read().decode('utf-8')
ssh.exec_command("rm -f /tmp/fetch_hcmc.php")
ssh.close()

data = json.loads(out)
print(f"Fetched ID: {data['ID']}, title: {data['title']}")

sources_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage69_mesh_sources.json')
all_sources = json.load(open(sources_file, encoding='utf-8'))
if 'mui-ne-travel-guide' in all_sources:
    del all_sources['mui-ne-travel-guide']
all_sources['ho-chi-minh-city-travel-guide'] = data

with open(sources_file, 'w', encoding='utf-8') as f:
    json.dump(all_sources, f, ensure_ascii=False, indent=2)

print("Updated stage69_mesh_sources.json successfully!")

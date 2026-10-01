# -*- coding: utf-8 -*-
"""
Count existing links to the 7 Stage 88 pillars in the VPS database.
"""
import paramiko
import json

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

slugs = [
    'where-to-stay-in-bac-ha',
    'where-to-stay-in-bao-lac',
    'where-to-stay-in-bao-loc',
    'sapa-to-bac-ha-transport',
    'tran-de-to-con-dao-ferry',
    'vung-tau-to-con-dao-ferry',
    'pleiku-to-quy-nhon-transport'
]

php_script = f"""<?php
$slugs = {json.dumps(slugs)};
$posts = get_posts([
    'post_type' => 'page',
    'post_status' => 'publish',
    'posts_per_page' => -1
]);

$results = [];
foreach ($slugs as $s) {{
    $results[$s] = [];
    foreach ($posts as $p) {{
        if ($p->post_name === $s) continue;
        if (strpos($p->post_content, '/' . $s . '/') !== false) {{
            $results[$s][] = [
                'id' => $p->ID,
                'slug' => $p->post_name,
                'title' => $p->post_title
            ];
        }}
    }}
}}

echo json_encode($results);
"""

pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

sftp = ssh.open_sftp()
with sftp.file('/tmp/count_links.php', 'w') as f:
    f.write(php_script)
sftp.close()

cmd = f"wp eval-file /tmp/count_links.php --path={WP_PATH} --allow-root"
stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode('utf-8')

ssh.exec_command("rm -f /tmp/count_links.php")
ssh.close()

json_line = [l for l in out.strip().splitlines() if l.strip().startswith('{')][-1]
data = json.loads(json_line)
print("=== INBOUND LINKS TO STAGE 88 PILLARS IN PRODUCTION DATABASE ===")
all_pass = True
for s, inlinks in data.items():
    print(f"\n[{s}]: {len(inlinks)} inbound links")
    for item in inlinks:
        print(f"  - {item['slug']} (ID: {item['id']})")
    if len(inlinks) < 4:
        all_pass = False

if all_pass:
    print("\nSUCCESS: All 7 Stage 88 pillars have at least 4 verified inbound links!")
else:
    print("\nWARNING: Some pillars have fewer than 4 inbound links!")

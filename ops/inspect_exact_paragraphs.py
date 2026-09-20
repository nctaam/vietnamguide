# -*- coding: utf-8 -*-
import paramiko
import json
import io
import sys
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

POST_IDS = [519, 521, 522, 523, 504, 213, 209, 178, 287, 190, 195, 309]

def get_full_paragraphs():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=paramiko.Ed25519Key.from_private_key_file(SSH_KEY))

    php_code = f"""<?php
$ids = {json.dumps(POST_IDS)};
$out = [];
foreach ($ids as $id) {{
    $p = get_post($id);
    if ($p) {{
        $out[$id] = ['id' => $p->ID, 'slug' => $p->post_name, 'content' => $p->post_content];
    }}
}}
echo json_encode($out);
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/get_full_p.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/get_full_p.php --path={WP_PATH} --allow-root")
    data = json.loads(stdout.read().decode('utf-8'))
    ssh.exec_command("rm -f /tmp/get_full_p.php")
    ssh.close()

    with open('ops/target_posts_content.json', 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print("Saved exact post contents to ops/target_posts_content.json")

if __name__ == '__main__':
    get_full_paragraphs()

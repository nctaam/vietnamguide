# -*- coding: utf-8 -*-
import json
import paramiko

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\NCTaam\.ssh\deploy_bot_key')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('66.42.48.146', port=2209, username='root', pkey=pkey, timeout=15)

php_code = """<?php
global $wpdb;
$sql = "SELECT ID, post_name, post_content FROM {$wpdb->prefix}posts WHERE post_type = 'page' AND post_status = 'publish';";
$posts = $wpdb->get_results($sql, ARRAY_A);
echo json_encode($posts);
"""

sftp = ssh.open_sftp()
with sftp.file('/tmp/get_links.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = ssh.exec_command('wp eval-file /tmp/get_links.php --path=/usr/local/lsws/vietnamguide.net/html --allow-root')
posts_data = json.loads(stdout.read().decode('utf-8'))
ssh.exec_command('rm -f /tmp/get_links.php')
ssh.close()

targets = ['ha-giang-loop-cost-budget', 'da-nang-hoi-an-budget', 'hanoi-ninh-binh-ha-long-budget']
for t in targets:
    sources = [p['post_name'] for p in posts_data if t in (p['post_content'] or '')]
    print(f"{t}: {len(sources)} in-links -> {', '.join(sources)}")

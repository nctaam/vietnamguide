# -*- coding: utf-8 -*-
import paramiko
import json
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

sample_transport_ids = [683, 684, 755, 756, 790, 823, 856, 859, 893, 894, 1139, 1532, 1565, 1566]

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\NCTaam\.ssh\deploy_bot_key')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('66.42.48.146', port=2209, username='root', pkey=pkey, timeout=15)

php_snippet = f"""<?php
global $wpdb;
$ids = implode(',', {json.dumps(sample_transport_ids)});
$posts = $wpdb->get_results("SELECT ID, post_name, post_content FROM {{$wpdb->posts}} WHERE ID IN ($ids);", ARRAY_A);

$results = [];
foreach ($posts as $p) {{
    $has_verdict = (strpos($p['post_content'], 'vg-concierge-verdict') !== false);
    $first_h2 = false;
    if (preg_match('/<h2[^>]*>(.*?)<\\/h2>/i', $p['post_content'], $m)) {{
        $first_h2 = $m[1];
    }}
    $results[$p['post_name']] = [
        'has_verdict' => $has_verdict,
        'first_h2' => $first_h2,
        'has_img' => (strpos($p['post_content'], '<img') !== false)
    ];
}}
echo json_encode($results);
"""

sftp = ssh.open_sftp()
with sftp.file('/tmp/check_trans_anchors.php', 'w') as f:
    f.write(php_snippet)
sftp.close()

cmd = 'wp eval-file /tmp/check_trans_anchors.php --path=/usr/local/lsws/vietnamguide.net/html --allow-root'
stdin, stdout, stderr = ssh.exec_command(cmd)
raw = stdout.read().decode('utf-8')
ssh.exec_command('rm -f /tmp/check_trans_anchors.php')
ssh.close()

json_str = [line for line in raw.strip().splitlines() if line.startswith('{')][-1]
data = json.loads(json_str)

print(f"Total checked: {len(data)}")
for slug, info in data.items():
    print(f"- {slug}: has_verdict={info['has_verdict']}, first_h2='{info['first_h2']}', has_img={info['has_img']}")

# -*- coding: utf-8 -*-
"""
VietnamGuide Meta Inventory Exporter
Queries production database for all published pages' Rank Math SEO metadata.
"""

import json
import paramiko

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def fetch_meta_inventory():
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)
    
    php_code = """<?php
global $wpdb;
$sql = "SELECT p.ID, p.post_name, p.post_title,
m1.meta_value AS rm_title, m2.meta_value AS rm_desc, m3.meta_value AS rm_keyword
FROM {$wpdb->prefix}posts p
LEFT JOIN {$wpdb->prefix}postmeta m1 ON p.ID = m1.post_id AND m1.meta_key = 'rank_math_title'
LEFT JOIN {$wpdb->prefix}postmeta m2 ON p.ID = m2.post_id AND m2.meta_key = 'rank_math_description'
LEFT JOIN {$wpdb->prefix}postmeta m3 ON p.ID = m3.post_id AND m3.meta_key = 'rank_math_focus_keyword'
WHERE p.post_type = 'page' AND p.post_status = 'publish'
ORDER BY p.ID ASC;";
$res = $wpdb->get_results($sql, ARRAY_A);
echo json_encode($res);
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/export_meta.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    cmd = f"wp eval-file /tmp/export_meta.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    raw_out = stdout.read().decode('utf-8')
    err_out = stderr.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/export_meta.php")
    ssh.close()
    
    if err_out and not raw_out:
        raise RuntimeError(f"Database query error: {err_out}")
        
    data = json.loads(raw_out)
    return data

if __name__ == '__main__':
    data = fetch_meta_inventory()
    print(f"Fetched {len(data)} pages.")
    with open('ops/meta_inventory.json', 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print("Saved to ops/meta_inventory.json")

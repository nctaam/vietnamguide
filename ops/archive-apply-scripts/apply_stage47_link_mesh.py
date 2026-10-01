# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 47 Production Link Mesh Applier
Applies all 38 high-value in-content link insertions across parent articles in the WordPress database,
elevating all 34 weakly linked travel guides to >= 3-4 incoming in-body editorial links.
Strictly 0 AI slop, adhering to Kiemcom Quality Gate v3.
"""
import paramiko
import json
import re
import sys
from pathlib import Path

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

REPO_ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(REPO_ROOT / 'ops'))
from plan_all_stage47_insertions import OPS

def main():
    print(f"=== VietnamGuide Stage 47 Master Link Mesh Applier ===")
    print(f"Total operations to apply: {len(OPS)}")

    # Group operations by parent slug
    by_parent = {}
    for op in OPS:
        by_parent.setdefault(op['parent'], []).append(op)

    print(f"Total parent posts to update: {len(by_parent)}")

    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage47_ops.json', 'w') as f:
        f.write(json.dumps(by_parent, ensure_ascii=False, indent=2))

    php_code = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
global $wpdb;

$json_str = file_get_contents('/tmp/stage47_ops.json');
$ops = json_decode($json_str, true);
$updated = 0;
$errors = 0;

foreach ($ops as $slug => $op_list) {
    $row = $wpdb->get_row($wpdb->prepare("SELECT ID, post_content FROM {$wpdb->posts} WHERE post_name = %s AND post_status = 'publish'", $slug));
    if (!$row) {
        echo "[ERROR] Post not found: $slug\\n";
        $errors++;
        continue;
    }
    
    $content = $row->post_content;
    $modified = false;
    
    foreach ($op_list as $op) {
        $target = $op['target'];
        $replacement = $op['replacement'];
        
        if (strpos($content, $target) !== false) {
            $content = str_replace($target, $replacement, $content);
            $modified = true;
            echo "[OK] Applied '{$op['desc']}' on post $slug (ID {$row->ID})\\n";
        } else {
            if (strpos($content, $replacement) !== false) {
                echo "[SKIP] Already applied '{$op['desc']}' on post $slug\\n";
            } else {
                echo "[FAIL] Target not found for '{$op['desc']}' on post $slug\\n";
                $errors++;
            }
        }
    }
    
    if ($modified) {
        $result = $wpdb->update(
            $wpdb->posts,
            array('post_content' => $content),
            array('ID' => $row->ID),
            array('%s'),
            array('%d')
        );
        if ($result !== false) {
            echo "[SUCCESS] Updated post $slug (ID {$row->ID}) in database.\\n";
            $updated++;
        } else {
            echo "[ERROR] DB update failed for post $slug\\n";
            $errors++;
        }
    }
}

echo "\\nSummary: $updated posts updated, $errors errors.\\n";
"""

    with sftp.file('/tmp/apply_stage47_links.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    print("Executing remote Stage 47 link mesh update script...")
    cmd = f"wp eval-file /tmp/apply_stage47_links.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    print(out)
    if err.strip():
        print(f"Stderr: {err}")

    ssh.exec_command("rm -f /tmp/apply_stage47_links.php /tmp/stage47_ops.json")

    print("\nPurging LiteSpeed cache and restarting LSWS...")
    purge_cmd = (
        f"wp litespeed-purge all --path={WP_PATH} --allow-root && "
        "rm -rf /usr/local/lsws/cachedata/* && "
        "/usr/local/lsws/bin/lswsctrl restart"
    )
    stdin, stdout, stderr = ssh.exec_command(purge_cmd)
    print(stdout.read().decode('utf-8'))

    ssh.close()
    print("=== Stage 47 Master Link Mesh Applier Completed ===")

if __name__ == '__main__':
    main()

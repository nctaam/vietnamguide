# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 60: Apply Internal Linking Mesh Insertions to VPS.
Links 12 high-authority pages to:
- /plan/vietnam-train-travel/
- /destinations/da-lat-travel-guide/
- /destinations/cao-bang-travel-guide/
"""
import paramiko
import json

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def main():
    print("=== Applying Stage 60 Internal Link Mesh Insertions ===")
    with open('ops/stage60_mesh_ops.json', 'r', encoding='utf-8') as f:
        ops = json.load(f)

    print(f"Loaded {len(ops)} operations.")

    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage60_mesh_ops.json', 'w') as f:
        f.write(json.dumps(ops, ensure_ascii=False, indent=2))

    php_code = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
global $wpdb;

$json_str = file_get_contents('/tmp/stage60_mesh_ops.json');
$ops = json_decode($json_str, true);
$updated = 0;
$errors = 0;
$already = 0;

foreach ($ops as $op) {
    $postId = (int)$op['post_id'];
    $slug = $op['slug'];
    $row = $wpdb->get_row($wpdb->prepare("SELECT ID, post_content FROM {$wpdb->posts} WHERE ID = %d AND post_status = 'publish'", $postId));
    if (!$row) {
        echo "[ERROR] Post ID {$postId} ($slug) not found\\n";
        $errors++;
        continue;
    }

    $content = $row->post_content;
    $target = $op['target'];
    $replacement = $op['replacement'];

    if (strpos($content, $target) !== false) {
        $new_content = str_replace($target, $replacement, $content);
        $result = $wpdb->update(
            $wpdb->posts,
            array('post_content' => $new_content),
            array('ID' => $row->ID),
            array('%s'),
            array('%d')
        );
        if ($result !== false) {
            echo "[SUCCESS] Inserted contextual mesh link in post $slug (ID {$row->ID})\\n";
            $updated++;
        } else {
            echo "[ERROR] Database update failed for post $slug (ID {$row->ID})\\n";
            $errors++;
        }
    } else {
        if (strpos($content, $replacement) !== false) {
            echo "[SKIP] Already applied on post $slug (ID {$row->ID})\\n";
            $already++;
        } else {
            echo "[FAIL] Target string not found on post $slug (ID {$row->ID})\\n";
            $errors++;
        }
    }
}

echo "\\nSummary: $updated updated, $already already applied, $errors errors.\\n";
"""

    with sftp.file('/tmp/apply_stage60_mesh_links.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    print("Executing remote Stage 60 link mesh insertion script...")
    cmd = f"wp eval-file /tmp/apply_stage60_mesh_links.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    print(out)
    if err.strip():
        print(f"Stderr: {err}")

    ssh.exec_command("rm -f /tmp/apply_stage60_mesh_links.php /tmp/stage60_mesh_ops.json")

    print("Purging LiteSpeed cache...")
    purge_cmd = f"wp litespeed-purge all --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(purge_cmd)
    print(stdout.read().decode('utf-8'))

    ssh.close()
    print("=== Stage 60 Link Mesh Insertion Complete ===")

if __name__ == '__main__':
    main()

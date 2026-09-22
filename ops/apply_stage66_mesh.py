# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 66: Apply Semantic Internal Linking Mesh for 7 New Pillars
Updates 27 target posts on VPS to ensure 28 high-relevance inbound links.
"""

import paramiko
import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def main():
    print("=== Applying Stage 66 Link Mesh (27 Posts, 28 Inbound Links) to Production VPS ===")
    
    base_dir = os.path.dirname(os.path.abspath(__file__))
    mesh_ops_file = os.path.join(base_dir, 'stage66_mesh_ops.json')
    with open(mesh_ops_file, 'r', encoding='utf-8') as f:
        posts = json.load(f)

    print(f"Loaded {len(posts)} posts to update from ops/stage66_mesh_ops.json")

    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage66_mesh_ops.json', 'w') as f:
        f.write(json.dumps(posts, ensure_ascii=False))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
global $wpdb;

$posts = json_decode(file_get_contents('/tmp/stage66_mesh_ops.json'), true);

$applied = 0;
$errors = 0;

foreach ($posts as $idx => $p) {
    $postId = (int)$p['post_id'];
    $slug = $p['slug'];
    $new_content = $p['content'];

    $res = wp_update_post([
        'ID' => $postId,
        'post_content' => $new_content
    ], true);

    if (is_wp_error($res)) {
        echo "[ERROR] wp_update_post failed for ID {$postId} ({$slug}): " . $res->get_error_message() . "\\n";
        $errors++;
        continue;
    }

    $wpdb->update(
        $wpdb->posts,
        [
            'post_modified'     => '2026-09-22 11:00:00',
            'post_modified_gmt' => '2026-09-22 11:00:00'
        ],
        ['ID' => $postId]
    );

    echo "[UPDATED " . ($idx+1) . "/" . count($posts) . "] ID {$postId} ({$slug})\\n";
    $applied++;
}

echo "\\nMesh Summary: Successfully updated {$applied}/" . count($posts) . " posts, {$errors} errors.\\n";
"""

    with sftp.file('/tmp/apply_stage66_mesh.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    print("Executing mesh updates via WP-CLI on production VPS...")
    cmd = f"wp eval-file /tmp/apply_stage66_mesh.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8')
    print(out)
    err = stderr.read().decode('utf-8')
    if err.strip():
        print("Stderr:", err)

    print("Purging LiteSpeed cache...")
    ssh.exec_command(f"wp litespeed-purge all --path={WP_PATH} --allow-root")
    ssh.exec_command("rm -f /tmp/apply_stage66_mesh.php /tmp/stage66_mesh_ops.json")
    ssh.close()
    print("Deployment completed.")

if __name__ == '__main__':
    main()

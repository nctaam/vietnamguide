# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 63: Apply Semantic Internal Linking Mesh for 7 New Pillars
"""

import paramiko
import json
import sys

sys.stdout.reconfigure(encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def main():
    print("=== Applying Stage 63 Link Mesh to Production VPS ===")
    
    with open('ops/stage63_mesh_ops.json', 'r', encoding='utf-8') as f:
        operations = json.load(f)

    print(f"Loaded {len(operations)} operations from ops/stage63_mesh_ops.json")

    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage63_mesh_ops.json', 'w') as f:
        f.write(json.dumps(operations, ensure_ascii=False, indent=2))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
global $wpdb;

$json_str = file_get_contents('/tmp/stage63_mesh_ops.json');
$ops = json_decode($json_str, true);

$applied = 0;
$errors = 0;

foreach ($ops as $idx => $op) {
    $postId = (int)$op['post_id'];
    $slug = $op['slug'];
    $target = $op['target'];
    $replacement = $op['replacement'];
    $pillar = $op['pillar'];

    $post = get_post($postId);
    if (!$post) {
        echo "[ERROR] Post ID {$postId} ({$slug}) not found.\\n";
        $errors++;
        continue;
    }

    $content = $post->post_content;
    if (strpos($content, $target) === false) {
        echo "[FAIL TARGET " . ($idx+1) . "] String not found in ID {$postId} ({$slug}) for {$pillar}\\n";
        $errors++;
        continue;
    }

    $new_content = str_replace($target, $replacement, $content);
    $res = wp_update_post([
        'ID' => $postId,
        'post_content' => $new_content
    ], true);

    if (is_wp_error($res)) {
        echo "[ERROR] wp_update_post failed for ID {$postId}: " . $res->get_error_message() . "\\n";
        $errors++;
        continue;
    }

    // Harmonize modified timestamp
    $wpdb->update(
        $wpdb->posts,
        [
            'post_modified'     => '2026-09-22 08:00:00',
            'post_modified_gmt' => '2026-09-22 08:00:00'
        ],
        ['ID' => $postId]
    );

    echo "[APPLIED " . ($idx+1) . "/28] ID {$postId} ({$slug}) -> {$pillar}\\n";
    $applied++;
}

echo "\\nMesh Summary: Applied {$applied}, Errors: {$errors}\\n";
"""

    with sftp.file('/tmp/apply_stage63_mesh.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    print("Executing mesh update via WP-CLI on production VPS...")
    cmd = f"wp eval-file /tmp/apply_stage63_mesh.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    output = stdout.read().decode('utf-8')
    print(output)
    err = stderr.read().decode('utf-8')
    if err.strip():
        print("Stderr:", err)

    print("Purging LiteSpeed cache...")
    ssh.exec_command(f"wp litespeed-purge all --path={WP_PATH} --allow-root")
    ssh.exec_command("rm -f /tmp/apply_stage63_mesh.php /tmp/stage63_mesh_ops.json")
    ssh.close()
    print("Mesh deployment completed.")

if __name__ == '__main__':
    main()

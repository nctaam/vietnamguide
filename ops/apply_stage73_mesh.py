# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 73: Apply Internal Link Mesh to Production VPS
"""

import paramiko
import json
import sys
import os

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def main():
    ops_dir = os.path.dirname(os.path.abspath(__file__))
    ops_file = os.path.join(ops_dir, 'stage73_mesh_ops.json')

    ops = json.load(open(ops_file, encoding='utf-8'))

    # Build updated content map: {post_id_str: new_content}
    mesh_dict = {str(op['id']): op['updated_content'] for op in ops}

    print(f"=== Applying Stage 73 Mesh: {len(mesh_dict)} posts to update ===")

    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage73_mesh_apply.json', 'w') as f:
        f.write(json.dumps(mesh_dict, ensure_ascii=False))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);
global $wpdb;

$mesh = json_decode(file_get_contents('/tmp/stage73_mesh_apply.json'), true);
$count = 0;

foreach ($mesh as $post_id_str => $new_content) {
    $post_id = (int)$post_id_str;
    if ($post_id < 1) {
        echo "[SKIP] Invalid post ID: {$post_id_str}\\n";
        continue;
    }

    $result = wp_update_post([
        'ID' => $post_id,
        'post_content' => $new_content,
    ], true);

    if (is_wp_error($result)) {
        echo "[ERROR] Post {$post_id}: " . $result->get_error_message() . "\\n";
    } else {
        $wpdb->update(
            $wpdb->posts,
            [
                'post_modified' => '2026-09-23 14:40:00',
                'post_modified_gmt' => '2026-09-23 14:40:00'
            ],
            ['ID' => $post_id]
        );
        echo "[OK] Post {$post_id} updated.\\n";
        $count++;
    }
}

echo "\\nTotal: {$count} posts updated successfully.\\n";
"""
    with sftp.file('/tmp/apply_stage73_mesh.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/apply_stage73_mesh.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    output = stdout.read().decode('utf-8')
    print(output)
    err = stderr.read().decode('utf-8')
    if err.strip():
        print("Stderr:", err)

    ssh.exec_command("rm -f /tmp/apply_stage73_mesh.php /tmp/stage73_mesh_apply.json")
    print("Purging LiteSpeed cache...")
    ssh.exec_command(f"wp litespeed-purge all --path={WP_PATH} --allow-root")
    ssh.close()
    print("Stage 73 Mesh applied and cache purged.")

if __name__ == '__main__':
    main()

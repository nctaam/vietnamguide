# -*- coding: utf-8 -*-
"""
Audit all remaining published posts on WordPress that still do not have a vg-guide-photo or Featured Image.
"""
import paramiko
import json
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def main():
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    php_code = """<?php
global $wpdb;
$rows = $wpdb->get_results("
    SELECT p.ID, p.post_name, p.post_parent, p.post_type, p.post_content, pm.meta_value as thumb_id
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
    WHERE p.post_status = 'publish'
      AND p.post_type IN ('page', 'post')
      AND p.ID NOT IN (2)
    ORDER BY p.ID ASC;
", ARRAY_A);

$no_photo = [];
$total = count($rows);
$with_photo = 0;
$with_thumb = 0;

foreach ($rows as $r) {
    $has_photo = strpos($r['post_content'], 'vg-guide-photo') !== false;
    $has_thumb = !empty($r['thumb_id']);
    if ($has_photo) $with_photo++;
    if ($has_thumb) $with_thumb++;
    if (!$has_photo || !$has_thumb) {
        $no_photo[] = [
            'ID' => (int)$r['ID'],
            'slug' => $r['post_name'],
            'parent' => (int)$r['post_parent'],
            'has_photo' => $has_photo,
            'has_thumb' => $has_thumb
        ];
    }
}

echo json_encode([
    'total_posts' => $total,
    'with_photo' => $with_photo,
    'with_thumb' => $with_thumb,
    'remaining_count' => count($no_photo),
    'remaining' => $no_photo
]);
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/audit_remaining.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/audit_remaining.php --path={WP_PATH} --allow-root")
    res_str = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/audit_remaining.php")
    ssh.close()

    json_line = [l for l in res_str.splitlines() if l.strip().startswith('{')][-1]
    data = json.loads(json_line)
    print(f"Total Published Posts: {data['total_posts']}")
    print(f"Posts with vg-guide-photo: {data['with_photo']}")
    print(f"Posts with Featured Image: {data['with_thumb']}")
    print(f"Remaining candidates to enrich: {data['remaining_count']}")
    print("\nRemaining Posts List:")
    for r in data['remaining']:
        print(f"  [{r['ID']}] {r['slug']} (parent: {r['parent']}, photo: {r['has_photo']}, thumb: {r['has_thumb']})")

if __name__ == '__main__':
    main()

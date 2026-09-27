# -*- coding: utf-8 -*-
"""
VietnamGuide: Comprehensive Site Media Audit
Queries all published posts to check:
1. Has _thumbnail_id (Featured image)
2. Has <figure class="vg-guide-photo"> in post_content
3. Categorizes posts needing new curation vs backfill
"""
import paramiko
import json
import os
import sys

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
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=20)

    php_script = """<?php
global $wpdb;
$posts = $wpdb->get_results("
    SELECT p.ID, p.post_name, p.post_title, p.post_parent, p.post_type,
           pm.meta_value as thumb_id,
           LOCATE('vg-guide-photo', p.post_content) as has_figure,
           LOCATE('<figure', p.post_content) as has_any_figure
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
    WHERE p.post_type IN ('post', 'page') AND p.post_status = 'publish'
    ORDER BY p.ID ASC
", ARRAY_A);

$out = [];
foreach ($posts as $p) {
    $out[] = [
        'id' => (int)$p['ID'],
        'slug' => $p['post_name'],
        'title' => $p['post_title'],
        'parent' => (int)$p['post_parent'],
        'thumb_id' => $p['thumb_id'] ? (int)$p['thumb_id'] : null,
        'has_vg_photo' => ((int)$p['has_figure'] > 0),
        'has_any_figure' => ((int)$p['has_any_figure'] > 0),
    ];
}
echo json_encode($out);
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/audit_media.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/audit_media.php --path={WP_PATH} --allow-root")
    raw = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/audit_media.php")
    ssh.close()

    json_line = [l for l in raw.strip().splitlines() if l.strip().startswith('[')][-1]
    all_posts = json.loads(json_line)

    print(f"Total published posts analyzed: {len(all_posts)}")

    no_thumb = [p for p in all_posts if not p['thumb_id']]
    has_thumb = [p for p in all_posts if p['thumb_id']]
    no_vg_photo = [p for p in all_posts if not p['has_vg_photo']]
    has_vg_photo = [p for p in all_posts if p['has_vg_photo']]
    
    has_figure_no_thumb = [p for p in all_posts if p['has_vg_photo'] and not p['thumb_id']]
    no_figure_no_thumb = [p for p in all_posts if not p['has_vg_photo'] and not p['thumb_id']]
    has_thumb_no_figure = [p for p in all_posts if p['thumb_id'] and not p['has_vg_photo']]

    print(f"\n--- SUMMARY STATISTICS ---")
    print(f"Posts WITH featured image (_thumbnail_id): {len(has_thumb)}")
    print(f"Posts WITHOUT featured image: {len(no_thumb)}")
    print(f"Posts WITH vg-guide-photo figure: {len(has_vg_photo)}")
    print(f"Posts WITHOUT vg-guide-photo figure: {len(no_vg_photo)}")
    
    print(f"\n--- BREAKDOWN ---")
    print(f"Group A (Need both Figure + Featured Image): {len(no_figure_no_thumb)}")
    print(f"Group B (Have Figure, need Featured Image backfill): {len(has_figure_no_thumb)}")
    print(f"Group C (Have Featured Image, need Figure insertion): {len(has_thumb_no_figure)}")

    ops_dir = os.path.dirname(os.path.abspath(__file__))
    out_file = os.path.join(ops_dir, 'media_audit_result.json')
    with open(out_file, 'w', encoding='utf-8') as f:
        json.dump({
            'total': len(all_posts),
            'has_thumb_count': len(has_thumb),
            'no_thumb_count': len(no_thumb),
            'has_vg_photo_count': len(has_vg_photo),
            'no_vg_photo_count': len(no_vg_photo),
            'no_figure_no_thumb': no_figure_no_thumb,
            'has_figure_no_thumb': has_figure_no_thumb,
            'has_thumb_no_figure': has_thumb_no_figure,
            'all_posts': all_posts
        }, f, indent=2, ensure_ascii=False)

    print(f"\nDetailed report saved to {out_file}")

    if no_figure_no_thumb:
        print("\n--- Posts needing both Figure and Featured Image (Group A) ---")
        for p in no_figure_no_thumb:
            print(f"ID {p['id']}: [{p['slug']}] {p['title']}")

    if has_figure_no_thumb:
        print("\n--- Posts having Figure but lacking Featured Image (Group B) ---")
        for p in has_figure_no_thumb:
            print(f"ID {p['id']}: [{p['slug']}] {p['title']}")

if __name__ == '__main__':
    main()

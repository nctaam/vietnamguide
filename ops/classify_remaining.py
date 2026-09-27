# -*- coding: utf-8 -*-
"""
Classify remaining posts by category to plan Batch 6
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

$out = [];
foreach ($rows as $r) {
    $out[] = [
        'ID' => (int)$r['ID'],
        'slug' => $r['post_name'],
        'parent' => (int)$r['post_parent'],
        'has_photo' => strpos($r['post_content'], 'vg-guide-photo') !== false,
        'has_thumb' => !empty($r['thumb_id'])
    ];
}
echo json_encode($out);
"""
sftp = ssh.open_sftp()
with sftp.file('/tmp/classify_posts.php', 'w') as f:
    f.write(php_code)
sftp.close()

_, stdout, _ = ssh.exec_command(f"wp eval-file /tmp/classify_posts.php --path={WP_PATH} --allow-root")
res_str = stdout.read().decode('utf-8')
ssh.exec_command("rm -f /tmp/classify_posts.php")
ssh.close()

json_line = [l for l in res_str.splitlines() if l.strip().startswith('[')][-1]
posts = json.loads(json_line)

UTILITY_SLUGS = {
    'privacy-policy', 'home', 'plan', 'destinations', 'itineraries', 'compare',
    'costs', 'newsletter', 'affiliate-disclosure', 'about', 'editorial-policy',
    'source-update-policy', 'contact', 'affiliate-review-policy'
}

content_posts = [p for p in posts if p['slug'] not in UTILITY_SLUGS and p['parent'] != 0]

no_photo_at_all = [p for p in content_posts if not p['has_photo']]
has_photo_no_thumb = [p for p in content_posts if p['has_photo'] and not p['has_thumb']]
complete = [p for p in content_posts if p['has_photo'] and p['has_thumb']]

print(f"Total Content Pillars: {len(content_posts)}")
print(f"Fully Complete (Photo + Featured Image): {len(complete)}")
print(f"No Photo At All: {len(no_photo_at_all)}")
print(f"Has Photo but No Featured Image: {len(has_photo_no_thumb)}")

print("\n--- Posts with NO PHOTO AT ALL (Grouped by Parent) ---")
by_parent = {}
for p in no_photo_at_all:
    by_parent.setdefault(p['parent'], []).append(p)

for par, plist in by_parent.items():
    print(f"\nParent {par} ({len(plist)} posts):")
    for p in plist:
        print(f"  [{p['ID']}] {p['slug']}")

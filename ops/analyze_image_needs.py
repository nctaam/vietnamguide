# -*- coding: utf-8 -*-
"""
Analyze image needs across all published pages on production VPS.
"""
import paramiko
import json
from collections import defaultdict

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\NCTaam\.ssh\deploy_bot_key')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('66.42.48.146', port=2209, username='root', pkey=pkey, timeout=15)

php_check = """<?php
global $wpdb;
$posts = $wpdb->get_results("SELECT ID, post_name, post_title, post_parent, post_content FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish';", ARRAY_A);

$no_img = [];
$has_img = [];

foreach ($posts as $p) {
    if (strpos($p['post_content'], '<img') === false) {
        $no_img[] = [
            'id' => (int)$p['ID'],
            'slug' => $p['post_name'],
            'title' => $p['post_title'],
            'parent' => (int)$p['post_parent']
        ];
    } else {
        $has_img[] = [
            'id' => (int)$p['ID'],
            'slug' => $p['post_name'],
            'title' => $p['post_title'],
            'parent' => (int)$p['post_parent']
        ];
    }
}

echo json_encode([
    'no_img_count' => count($no_img),
    'has_img_count' => count($has_img),
    'no_img' => $no_img,
    'has_img' => $has_img
]);
"""

sftp = ssh.open_sftp()
with sftp.file('/tmp/check_no_imgs.php', 'w') as f:
    f.write(php_check)
sftp.close()

cmd = 'wp eval-file /tmp/check_no_imgs.php --path=/usr/local/lsws/vietnamguide.net/html --allow-root'
stdin, stdout, stderr = ssh.exec_command(cmd)
raw_out = stdout.read().decode('utf-8')
ssh.exec_command('rm -f /tmp/check_no_imgs.php')
ssh.close()

json_str = [line for line in raw_out.strip().splitlines() if line.startswith('{')][-1]
data = json.loads(json_str)

print(f"Total Pages Analyzed: {data['no_img_count'] + data['has_img_count']}")
print(f"Pages WITH inline images: {data['has_img_count']}")
print(f"Pages WITHOUT inline images: {data['no_img_count']}")

# Group no_img by clusters
clusters = defaultdict(list)
for item in data['no_img']:
    slug = item['slug']
    if slug.startswith('where-to-stay-in-'):
        clusters['where-to-stay (Hotels & Lodging)'].append(item)
    elif '-to-' in slug and ('transport' in slug or 'ferry' in slug or 'train' in slug or 'bus' in slug):
        clusters['transport & ferries (Routes & Transit)'].append(item)
    elif 'vs' in slug or 'compare' in slug:
        clusters['comparisons & alternatives'].append(item)
    elif 'itinerary' in slug or 'days-in-vietnam' in slug or 'vietnam-in-' in slug:
        clusters['itineraries & seasons'].append(item)
    elif 'cost' in slug or 'budget' in slug or 'price' in slug:
        clusters['costs & practical'].append(item)
    else:
        clusters['other destination & practical guides'].append(item)

print("\n=== CLUSTERS OF PAGES WITHOUT IMAGES ===")
for cat, items in sorted(clusters.items(), key=lambda x: -len(x[1])):
    print(f"\n[{cat}] ({len(items)} pages):")
    for it in items[:5]:
        print(f"   - {it['slug']} (ID: {it['id']})")
    if len(items) > 5:
        print(f"   ... and {len(items) - 5} more")

# Save detailed list to json for batch planning
with open('ops/pages_without_images.json', 'w', encoding='utf-8') as f:
    json.dump({
        'summary': {
            'total': data['no_img_count'] + data['has_img_count'],
            'with_images': data['has_img_count'],
            'without_images': data['no_img_count']
        },
        'clusters': {k: v for k, v in clusters.items()}
    }, f, ensure_ascii=False, indent=2)

print("\nSaved full breakdown to ops/pages_without_images.json")

# -*- coding: utf-8 -*-
"""
VietnamGuide Comprehensive Internal Link Auditor
Analyzes internal link graph across all 102 pages:
- In-degree (how many pages link TO this page)
- Out-degree (how many internal links this page gives OUT)
- Orphan pages detection (0 incoming links)
- Weakly linked pages (< 3 incoming links)
"""

import json
import urllib.request
import re
from urllib.parse import urlparse

with open('ops/meta_inventory.json', 'r', encoding='utf-8') as f:
    pages = json.load(f)

print(f"=== Auditing Internal Link Graph ({len(pages)} Pages) ===")

# Build canonical URL set
slug_to_id = {p['post_name']: p['ID'] for p in pages}
all_slugs = set(slug_to_id.keys())

# We can query post_content directly from the database or via HTTP
import paramiko
pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\NCTaam\.ssh\deploy_bot_key')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('66.42.48.146', port=2209, username='root', pkey=pkey, timeout=15)

php_code = """<?php
global $wpdb;
$sql = "SELECT ID, post_name, post_content FROM {$wpdb->prefix}posts WHERE post_type = 'page' AND post_status = 'publish';";
$posts = $wpdb->get_results($sql, ARRAY_A);
echo json_encode($posts);
"""

sftp = ssh.open_sftp()
with sftp.file('/tmp/get_contents.php', 'w') as f:
    f.write(php_code)
sftp.close()

cmd = "wp eval-file /tmp/get_contents.php --path=/usr/local/lsws/vietnamguide.net/html --allow-root"
stdin, stdout, stderr = ssh.exec_command(cmd)
raw_json = stdout.read().decode('utf-8')
ssh.exec_command("rm -f /tmp/get_contents.php")
ssh.close()

posts_data = json.loads(raw_json)
print(f"Fetched {len(posts_data)} post contents from database.")

in_links = {p['post_name']: [] for p in posts_data}
out_links = {p['post_name']: [] for p in posts_data}

link_pattern = re.compile(r'href=["\'](?:https?://vietnamguide\.net)?(/[^"\'#?]+)', re.IGNORECASE)

for p in posts_data:
    source_slug = p['post_name']
    content = p['post_content'] or ''
    matches = link_pattern.findall(content)
    
    unique_targets = set()
    for m in matches:
        target_slug = m.strip('/').split('/')[-1]
        if target_slug in in_links and target_slug != source_slug:
            unique_targets.add(target_slug)
            
    for t in unique_targets:
        out_links[source_slug].append(t)
        in_links[t].append(source_slug)

print("\n=== INTERNAL LINK GRAPH SUMMARY ===")
total_edges = sum(len(v) for v in out_links.values())
print(f"Total Internal Links in Content: {total_edges}")
print(f"Average Outgoing Links per page: {total_edges / len(posts_data):.1f}")

orphans = [s for s, inc in in_links.items() if len(inc) == 0]
weak = [s for s, inc in in_links.items() if 0 < len(inc) < 3]
top_linked = sorted(in_links.items(), key=lambda x: len(x[1]), reverse=True)[:10]

print(f"\n1. Orphan Pages (0 incoming links in post_content): {len(orphans)}")
for o in orphans:
    print(f"   - /{o}/")

print(f"\n2. Weakly Linked Pages (< 3 incoming links in post_content): {len(weak)}")
for w in weak:
    print(f"   - /{w}/ ({len(in_links[w])} in-links: {', '.join(in_links[w])})")

print(f"\n3. Top 10 Most Linked Hub Pages:")
for s, inc in top_linked:
    print(f"   - /{s}/: {len(inc)} in-links")

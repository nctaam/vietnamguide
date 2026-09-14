# -*- coding: utf-8 -*-
import paramiko
import json
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

parent_slugs = [
    'da-nang-travel-guide',
    'hue-imperial-city-guide',
    'hanoi-travel-guide',
    '10-days-in-vietnam',
    'mekong-delta-travel-guide',
    'best-time-to-visit-vietnam',
    'tet-in-vietnam-travel-guide',
    'vietnam-travel-guide',
    'safety-scams-vietnam',
    'ninh-binh-travel-guide',
    'ha-long-bay-travel-guide',
    'north-central-south-vietnam',
    'ha-giang-loop-planning-guide'
]

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\NCTaam\.ssh\deploy_bot_key')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('66.42.48.146', port=2209, username='root', pkey=pkey)

slugs_str = "','".join(parent_slugs)
cmd = (
    f"wp db query \"SELECT ID, post_name, post_title, post_content FROM Sf5bm6_posts "
    f"WHERE post_name IN ('{slugs_str}') AND post_status='publish';\" "
    "--path=/usr/local/lsws/vietnamguide.net/html --allow-root"
)
stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode('utf-8')
lines = out.strip().splitlines()

posts = {}
for line in lines[1:]:
    parts = line.split('\t')
    if len(parts) >= 4:
        posts[parts[1]] = {
            'ID': parts[0],
            'title': parts[2],
            'content': parts[3]
        }

with open('ops/parent_posts_cache.json', 'w', encoding='utf-8') as f:
    json.dump(posts, f, ensure_ascii=False, indent=2)

print(f"Cached {len(posts)} parent posts.")
for slug, p in posts.items():
    print(f"\n[{slug}] (ID {p['ID']}): {p['title']}")
    # print all H2 headings
    h2s = re.findall(r'<h2[^>]*>(.*?)</h2>', p['content'], re.I)
    for h in h2s:
        print(f"   H2: {re.sub(r'<[^>]+>', '', h).strip()}")

ssh.close()

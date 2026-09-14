# -*- coding: utf-8 -*-
import paramiko
import json

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\NCTaam\.ssh\deploy_bot_key')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('66.42.48.146', port=2209, username='root', pkey=pkey)

cmd = "wp db query \"SELECT ID, post_name, post_modified FROM Sf5bm6_posts WHERE post_type='page' AND post_status='publish' ORDER BY post_modified ASC;\" --path=/usr/local/lsws/vietnamguide.net/html --allow-root"
stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode('utf-8')
lines = out.strip().splitlines()

posts = []
for l in lines[1:]:
    parts = l.split('\t')
    if len(parts) >= 3:
        posts.append({'id': parts[0], 'slug': parts[1], 'modified': parts[2]})

print(f"Total published pages: {len(posts)}")
old_posts = [p for p in posts if p['modified'].startswith('2026-07')]
recent_posts = [p for p in posts if not p['modified'].startswith('2026-07')]

print(f"Pages with July 2026 timestamps (stale to Google): {len(old_posts)}")
print(f"Pages with recent timestamps: {len(recent_posts)}")

print("\nSample of stale pages:")
for p in old_posts[:10]:
    print(f"  ID {p['id']} /{p['slug']}/ -> {p['modified']}")

ssh.close()

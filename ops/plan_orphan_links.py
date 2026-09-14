# -*- coding: utf-8 -*-
"""
Explore candidate parent pages for the 14 orphan articles.
"""
import paramiko
import json
import re

ORPHAN_PAIRS = [
    # (orphan_slug, candidate_parent_slug)
    ('da-nang-beaches-guide', 'da-nang-travel-guide'),
    ('phong-nha-travel-guide', 'hue-imperial-city-guide'),
    ('hanoi-vs-ho-chi-minh-city', 'hanoi-travel-guide'),
    ('best-vietnam-routes-first-time-visitors', '10-days-in-vietnam'),
    ('mekong-delta-overnight-vs-day-trip', 'mekong-delta-travel-guide'),
    ('vietnam-in-december', 'best-time-to-visit-vietnam'),
    ('vietnam-in-february', 'best-time-to-visit-vietnam'),
    ('vietnam-rainy-season-flexible-route', 'best-time-to-visit-vietnam'),
    ('vietnam-first-trip-planning-checklist', 'vietnam-travel-guide'),
    ('hanoi-first-time-visitor-mistakes', 'hanoi-travel-guide'),
    ('ninh-binh-without-rushing', 'ninh-binh-travel-guide'),
    ('ha-long-bay-cruise-questions-before-booking', 'ha-long-bay-cruise-guide'),
    ('best-vietnam-cities-for-first-time-visitors', 'north-central-south-vietnam'),
    ('ha-giang-easy-rider-vs-self-drive', 'ha-giang-travel-guide'),
]

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\NCTaam\.ssh\deploy_bot_key')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('66.42.48.146', port=2209, username='root', pkey=pkey)

parent_slugs = list(set([p[1] for p in ORPHAN_PAIRS]))
slug_list_sql = "','".join(parent_slugs)

cmd = (
    f"wp db query \"SELECT ID, post_name, post_title, post_content FROM Sf5bm6_posts "
    f"WHERE post_name IN ('{slug_list_sql}') AND post_status='publish';\" "
    "--path=/usr/local/lsws/vietnamguide.net/html --allow-root"
)
stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode('utf-8')
lines = out.strip().splitlines()

parent_contents = {}
for line in lines[1:]:
    parts = line.split('\t')
    if len(parts) >= 4:
        parent_contents[parts[1]] = {
            'ID': parts[0],
            'title': parts[2],
            'content': parts[3]
        }

print(f"Retrieved {len(parent_contents)} candidate parent pages.")

for orphan, parent in ORPHAN_PAIRS:
    if parent in parent_contents:
        pdata = parent_contents[parent]
        print(f"\nTarget: /{orphan}/ -> Parent: /{parent}/ (ID: {pdata['ID']})")
        # Check if parent already has link
        if orphan in pdata['content']:
            print("  [ALREADY LINKED!]")
        else:
            print("  [NOT LINKED YET - READY]")

ssh.close()

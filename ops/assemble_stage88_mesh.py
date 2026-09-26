# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 88: Assemble Mesh Updates
Applies all validated candidate string replacements to the host posts and saves to stage88_mesh_updates.json.
"""
import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

ops_dir = os.path.dirname(os.path.abspath(__file__))

with open(os.path.join(ops_dir, 'stage88_mesh_targets.json'), 'r', encoding='utf-8') as f:
    data = json.load(f)

from test_mesh88_candidates import CANDIDATES

updates = {}

# Group candidates by host_slug
by_host = {}
for host_slug, target_str, replacement_str, target_pillar in CANDIDATES:
    if host_slug not in by_host:
        by_host[host_slug] = []
    by_host[host_slug].append((target_str, replacement_str, target_pillar))

print(f"=== Assembling Stage 88 Mesh Updates across {len(by_host)} Hosts ===")

for host_slug, items in by_host.items():
    host_info = data[host_slug]
    host_id = host_info.get('ID') or host_info.get('id')
    title = host_info.get('post_title') or host_info.get('title')
    content = host_info.get('post_content') or host_info.get('content')
    
    modified_content = content
    for target_str, replacement_str, target_pillar in items:
        if target_str not in modified_content:
            raise ValueError(f"Target string not found in {host_slug} for {target_pillar}")
        modified_content = modified_content.replace(target_str, replacement_str, 1)
        print(f"Applied replacement in {host_slug} (ID: {host_id}) -> {target_pillar}")

    updates[host_slug] = {
        'id': host_id,
        'title': title,
        'content': modified_content
    }

out_file = os.path.join(ops_dir, 'stage88_mesh_updates.json')
with open(out_file, 'w', encoding='utf-8') as f:
    json.dump(updates, f, ensure_ascii=False, indent=2)

print(f"\nSuccessfully generated {out_file} with {len(updates)} updated host posts.")

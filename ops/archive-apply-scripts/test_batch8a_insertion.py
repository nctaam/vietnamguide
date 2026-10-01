# -*- coding: utf-8 -*-
"""
VietnamGuide: Test Batch 8A Content Insertion Regex across all 28 posts
"""
import paramiko
import json
import re
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

    with open('ops/batch8a_final_images.json', 'r', encoding='utf-8') as f:
        manifest = json.load(f)

    post_ids = [it['id'] for it in manifest]

    php_script = f"""<?php
global $wpdb;
$ids = implode(',', {json.dumps(post_ids)});
$posts = $wpdb->get_results("
    SELECT ID, post_name, post_content
    FROM {{$wpdb->posts}}
    WHERE ID IN ($ids)
", ARRAY_A);
$out = [];
foreach ($posts as $p) {{
    $out[$p['ID']] = [
        'id' => (int)$p['ID'],
        'slug' => $p['post_name'],
        'content' => $p['post_content']
    ];
}}
echo json_encode($out);
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/test_8a_fetch.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/test_8a_fetch.php --path={WP_PATH} --allow-root")
    raw = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/test_8a_fetch.php")
    ssh.close()

    json_line = [l for l in raw.strip().splitlines() if l.strip().startswith('{')][-1]
    db_posts = json.loads(json_line)

    verdict_close_pattern = re.compile(r'(<div[^>]*class="[^"]*vg-concierge-verdict[^"]*"[^>]*>.*?</div>(\s*<!--\s*/wp:group\s*-->)?)', re.DOTALL)
    h2_pattern = re.compile(r'(<h2\b)', re.IGNORECASE)

    all_ok = True
    for it in manifest:
        pid = str(it['id'])
        slug = it['slug']
        p = db_posts.get(pid)
        if not p:
            print(f"ERROR: Post {pid} ({slug}) not found in DB")
            all_ok = False
            continue

        c = p['content']
        match_v = verdict_close_pattern.search(c)
        match_h = h2_pattern.search(c)

        if match_v:
            print(f"[VERDICT OK] {slug} (ID: {pid}) -> insertion after verdict block")
        elif match_h:
            print(f"[H2 OK] {slug} (ID: {pid}) -> fallback insertion before first <h2>")
        else:
            print(f"[FAIL] {slug} (ID: {pid}) -> NO insertion anchor found!")
            all_ok = False

    print(f"\nDry-run insertion test: {'ALL 28 PASSED' if all_ok else 'SOME FAILED'}")

if __name__ == '__main__':
    main()

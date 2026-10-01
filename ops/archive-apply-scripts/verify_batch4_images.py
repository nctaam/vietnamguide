# -*- coding: utf-8 -*-
"""
VietnamGuide: Verify Batch 4 Real Images on Live Site (21 Regional Lodging Guides)
"""
import paramiko
import json
import urllib.request
import re
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def main():
    with open('ops/batch4_final_images.json', 'r', encoding='utf-8') as f:
        items = json.load(f)

    print(f"=== VERIFYING BATCH 4 REAL IMAGES ON PRODUCTION ({len(items)} Regional Lodging Guides) ===\n")
    
    # 1. DB Verification & URL resolution via WP-CLI
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    post_ids = [it['post_id'] for it in items]
    php_db_check = f"""<?php
global $wpdb;
$ids = implode(',', {json.dumps(post_ids)});
$sql = "SELECT p.ID, p.post_name, pm.meta_value as thumb_id, att.guid as thumb_url
        FROM {{$wpdb->posts}} p
        LEFT JOIN {{$wpdb->postmeta}} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
        LEFT JOIN {{$wpdb->posts}} att ON pm.meta_value = att.ID
        WHERE p.ID IN ($ids);";
$rows = $wpdb->get_results($sql, ARRAY_A);

$out = [];
foreach ($rows as $r) {{
    $pid = (int)$r['ID'];
    $out[$pid] = [
        'ID' => $pid,
        'post_name' => $r['post_name'],
        'thumb_id' => $r['thumb_id'],
        'thumb_url' => $r['thumb_url'],
        'permalink' => get_permalink($pid)
    ];
}}
echo json_encode($out);
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/verify_db_thumbs_batch4.php', 'w') as f:
        f.write(php_db_check)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/verify_db_thumbs_batch4.php --path={WP_PATH} --allow-root")
    raw_db = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/verify_db_thumbs_batch4.php")
    ssh.close()

    json_line = [l for l in raw_db.strip().splitlines() if l.strip().startswith('{')][-1]
    db_by_id = json.loads(json_line)

    print("--- Database Featured Image Verification ---")
    db_all_pass = True
    for it in items:
        pid = str(it['post_id'])
        r = db_by_id.get(pid, {})
        thumb_id = r.get('thumb_id')
        thumb_url = r.get('thumb_url')
        if thumb_id and int(thumb_id) > 0 and thumb_url:
            print(f"[PASS] {it['slug']} (ID: {pid}) -> Featured Image ID {thumb_id}")
        else:
            print(f"[FAIL] {it['slug']} (ID: {pid}) -> Missing featured image!")
            db_all_pass = False

    # 2. Live HTTP Verification
    print("\n--- Live HTTP Page Verification ---")
    http_all_pass = True
    for idx, it in enumerate(items, 1):
        pid = str(it['post_id'])
        r = db_by_id.get(pid, {})
        url = r.get('permalink') or f"https://vietnamguide.net/destinations/{it['slug']}/"
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
        try:
            with urllib.request.urlopen(req, timeout=12) as resp:
                html = resp.read().decode('utf-8')
                status = resp.status
                has_figure = 'class="vg-guide-photo"' in html or "vg-guide-photo" in html
                has_uploads = 'wp-content/uploads/' in html
                
                # Check OpenGraph image
                og_match = re.search(r'<meta\s+property="og:image"\s+content="([^"]+)"', html)
                og_image = og_match.group(1) if og_match else None

                if status == 200 and has_figure and has_uploads:
                    print(f"[{idx}/{len(items)}] [PASS] {it['slug']} -> HTTP 200 | Photo: Yes | Uploads: Yes | OG: {bool(og_image)}")
                else:
                    print(f"[{idx}/{len(items)}] [FAIL] {it['slug']} -> status={status}, figure={has_figure}, uploads={has_uploads}")
                    http_all_pass = False
        except Exception as e:
            print(f"[{idx}/{len(items)}] [FAIL] {it['slug']} ({url}) -> HTTP Request Error: {e}")
            http_all_pass = False

    print("\n=== SUMMARY ===")
    if db_all_pass and http_all_pass:
        print(f"[SUCCESS] All {len(items)} Regional Lodging guides verified with Featured Images and inline photos on production!")
    else:
        print("[WARNING] Some checks failed. Review output above.")

if __name__ == '__main__':
    main()

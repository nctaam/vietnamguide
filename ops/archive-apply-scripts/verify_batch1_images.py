# -*- coding: utf-8 -*-
"""
VietnamGuide: Verify Batch 1 Real Images on Live Site
"""
import paramiko
import json
import urllib.request
import re
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

with open('ops/batch1_final_images.json', 'r', encoding='utf-8') as f:
    items = json.load(f)

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def main():
    print(f"=== VERIFYING BATCH 1 REAL IMAGES ON PRODUCTION ({len(items)} Pages) ===\n")
    
    # 1. DB Verification
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
echo json_encode($rows);
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/verify_db_thumbs.php', 'w') as f:
        f.write(php_db_check)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/verify_db_thumbs.php --path={WP_PATH} --allow-root")
    raw_db = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/verify_db_thumbs.php")
    ssh.close()

    json_line = [l for l in raw_db.strip().splitlines() if l.strip().startswith('[')][-1]
    db_data = json.loads(json_line)
    db_by_id = {int(r['ID']): r for r in db_data}

    print("--- Database Featured Image Verification ---")
    db_all_pass = True
    for it in items:
        pid = it['post_id']
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
        url = f"https://vietnamguide.net/destinations/{it['slug']}/"
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
        try:
            with urllib.request.urlopen(req, timeout=12) as resp:
                html = resp.read().decode('utf-8')
                status = resp.status
                has_figure = 'class="vg-guide-photo"' in html or "vg-guide-photo" in html
                has_uploads = 'wp-content/uploads/' in html
                has_caption = it['caption'][:25] in html or 'Image:' in html
                
                # Check OpenGraph image
                og_match = re.search(r'<meta\s+property="og:image"\s+content="([^"]+)"', html)
                og_image = og_match.group(1) if og_match else None

                if status == 200 and has_figure and has_uploads:
                    print(f"[{idx}/30] [PASS] {it['slug']} -> HTTP 200 | Photo: Yes | Uploads: Yes | OG: {bool(og_image)}")
                else:
                    print(f"[{idx}/30] [FAIL] {it['slug']} -> status={status}, figure={has_figure}, uploads={has_uploads}")
                    http_all_pass = False
        except Exception as e:
            print(f"[{idx}/30] [FAIL] {it['slug']} -> HTTP Request Error: {e}")
            http_all_pass = False

    print("\n=== SUMMARY ===")
    if db_all_pass and http_all_pass:
        print("[SUCCESS] All 30 Where-to-Stay guides verified with Featured Images and inline photos on production!")
    else:
        print("[WARNING] Some checks failed. Review output above.")

if __name__ == '__main__':
    main()

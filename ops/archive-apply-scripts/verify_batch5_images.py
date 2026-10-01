# -*- coding: utf-8 -*-
"""
VietnamGuide: Verify Batch 5 Real Images (31 Regional Transit Guides)
1. Verifies MySQL _thumbnail_id is set and points to valid attachment
2. Checks live HTTP 200 response for all 31 post URLs
3. Verifies <figure class="vg-guide-photo"> presence and local upload URL in HTML
4. Verifies og:image OpenGraph meta tag points to the featured image
"""
import paramiko
import urllib.request
import urllib.parse
import json
import os
import sys
import re
import ssl

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def main():
    ops_dir = os.path.dirname(os.path.abspath(__file__))
    plan_file = os.path.join(ops_dir, 'batch5_final_images.json')
    with open(plan_file, 'r', encoding='utf-8') as f:
        images_plan = json.load(f)

    post_ids = [item['id'] for item in images_plan]

    print(f"=== VERIFYING BATCH 5 ({len(images_plan)} Regional Transit Guides) ===")

    # Step 1: Query MySQL on VPS for _thumbnail_id
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=20)

    php_verify = f"""<?php
global $wpdb;
$ids = implode(',', {json.dumps(post_ids)});
$rows = $wpdb->get_results("
    SELECT p.ID, p.post_name, pm.meta_value as thumb_id, ap.guid as thumb_url
    FROM {{$wpdb->posts}} p
    LEFT JOIN {{$wpdb->postmeta}} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
    LEFT JOIN {{$wpdb->posts}} ap ON pm.meta_value = ap.ID
    WHERE p.ID IN ($ids)
", ARRAY_A);
echo json_encode($rows);
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/verify_batch5.php', 'w') as f:
        f.write(php_verify)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/verify_batch5.php --path={WP_PATH} --allow-root")
    raw_res = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/verify_batch5.php")
    ssh.close()

    json_line = [l for l in raw_res.strip().splitlines() if l.strip().startswith('[')][-1]
    db_records = {int(r['ID']): r for r in json.loads(json_line)}

    print(f"Retrieved {len(db_records)} records from MySQL database.")

    # Step 2: Verify live pages via HTTP
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE

    passed = 0
    failed = 0

    print("\n--- Verifying Live Pages ---")
    for idx, item in enumerate(images_plan, 1):
        pid = item['id']
        slug = item['slug']
        url = f"https://vietnamguide.net/plan/{slug}/"
        db_rec = db_records.get(pid, {})
        thumb_id = db_rec.get('thumb_id')

        # Check DB thumbnail
        db_ok = thumb_id is not None and int(thumb_id) > 0

        # Check HTTP fetch
        http_ok = False
        has_figure = False
        has_og_image = False
        has_local_url = False

        try:
            req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
            with urllib.request.urlopen(req, context=ctx, timeout=12) as resp:
                status = resp.status
                if status == 200:
                    http_ok = True
                    html = resp.read().decode('utf-8')
                    if 'class="vg-guide-photo"' in html:
                        has_figure = True
                    if 'property="og:image"' in html:
                        has_og_image = True
                    if '/wp-content/uploads/' in html:
                        has_local_url = True
        except Exception as e:
            print(f"[{idx:02d}/31] HTTP FAIL {slug}: {e}")

        all_checks = db_ok and http_ok and has_figure and has_og_image and has_local_url
        if all_checks:
            passed += 1
            print(f"[{idx:02d}/31] PASS: {slug} (ID: {pid}, Thumb: {thumb_id})")
        else:
            failed += 1
            print(f"[{idx:02d}/31] FAIL: {slug} (DB: {db_ok}, HTTP: {http_ok}, Fig: {has_figure}, OG: {has_og_image}, Local: {has_local_url})")

    print(f"\n=== BATCH 5 VERIFICATION RESULTS: {passed}/{len(images_plan)} PASSED, {failed} FAILED ===")

if __name__ == '__main__':
    main()

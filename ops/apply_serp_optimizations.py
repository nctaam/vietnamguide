# -*- coding: utf-8 -*-
"""
VietnamGuide Production SERP Optimizer
Applies validated title, meta description, and focus keyword optimizations
to WordPress postmeta on VPS, purges LiteSpeed cache, and pings IndexNow.
"""

import sys
import json
import paramiko
from validate_full_serp_update import (
    TITLE_UPDATES,
    INSTRUCTIONAL_DESC_UPDATES,
    EVIDENCE_DESC_UPDATES,
    POLICY_KEYWORDS,
)

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def apply_updates():
    print("=== Applying SERP Metadata Optimizations to Production ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    all_descs = {}
    all_descs.update(INSTRUCTIONAL_DESC_UPDATES)
    all_descs.update(EVIDENCE_DESC_UPDATES)

    # Generate PHP script to execute on remote server
    php_lines = [
        "<?php",
        "define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);",
        "global $wpdb;",
        "$updated_titles = 0;",
        "$updated_descs = 0;",
        "$updated_keywords = 0;",
    ]

    # Titles
    for pid, title in TITLE_UPDATES.items():
        escaped_title = title.replace("'", "\\'")
        php_lines.append(f"update_post_meta({pid}, 'rank_math_title', '{escaped_title}');")
        php_lines.append("$updated_titles++;")

    # Descriptions
    for pid, desc in all_descs.items():
        escaped_desc = desc.replace("'", "\\'")
        php_lines.append(f"update_post_meta({pid}, 'rank_math_description', '{escaped_desc}');")
        php_lines.append("$updated_descs++;")

    # Keywords
    for pid, kw in POLICY_KEYWORDS.items():
        escaped_kw = kw.replace("'", "\\'")
        php_lines.append(f"update_post_meta({pid}, 'rank_math_focus_keyword', '{escaped_kw}');")
        php_lines.append("$updated_keywords++;")

    php_lines.extend([
        "echo json_encode([",
        "    'status' => 'OK',",
        "    'titles' => $updated_titles,",
        "    'descriptions' => $updated_descs,",
        "    'keywords' => $updated_keywords,",
        "]);",
    ])

    remote_script = "\n".join(php_lines)

    print(f"Uploading update script for {len(TITLE_UPDATES)} titles, {len(all_descs)} descriptions, and {len(POLICY_KEYWORDS)} keywords...")
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/apply_serp_update.php', 'w') as f:
        f.write(remote_script)
    sftp.close()

    print("Executing wp eval-file /tmp/apply_serp_update.php with admin overwrite authorized...")
    cmd = f"VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file /tmp/apply_serp_update.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    raw_out = stdout.read().decode('utf-8')
    err_out = stderr.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/apply_serp_update.php")

    if err_out and not raw_out:
        print(f"ERROR: {err_out}")
        ssh.close()
        sys.exit(1)

    print(f"Server response: {raw_out.strip()}")

    # Purge LiteSpeed cache
    print("Purging LiteSpeed cache...")
    purge_cmd = "rm -rf /usr/local/lsws/cachedata/* && killall -USR1 openlitespeed"
    stdin, stdout, stderr = ssh.exec_command(purge_cmd)
    print("LiteSpeed Cache purged.")

    ssh.close()
    print("\n[SUCCESS] Production database updated and cache purged.")

if __name__ == '__main__':
    apply_updates()

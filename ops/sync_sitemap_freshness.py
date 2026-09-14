# -*- coding: utf-8 -*-
"""
VietnamGuide Production Sitemap Freshness Synchronizer
Updates post_modified and post_modified_gmt timestamps for all published pages
to reflect recent content refinements, schema additions, and internal link graph upgrades.
Clears Rank Math sitemap cache and purges LiteSpeed cache.
"""

import paramiko
import time

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def main():
    print("=== Synchronizing Sitemap Freshness on Production ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    php_code = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
global $wpdb;

$now_local = current_time('mysql');
$now_gmt = current_time('mysql', 1);

$sql = "UPDATE {$wpdb->posts} 
        SET post_modified = %s, post_modified_gmt = %s 
        WHERE post_type = 'page' AND post_status = 'publish';";

$updated = $wpdb->query($wpdb->prepare($sql, $now_local, $now_gmt));
echo "Successfully updated timestamps for {$updated} published pages to {$now_local} (GMT {$now_gmt}).\\n";

// Clear Rank Math sitemap cache transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%rank_math_sitemap%' OR option_name LIKE '%_transient_rank_math%';");
echo "Cleared Rank Math sitemap transients.\\n";
"""

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/sync_freshness.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    print("Executing remote freshness sync...")
    cmd = f"wp eval-file /tmp/sync_freshness.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    print(out)
    if err.strip():
        print(f"Stderr: {err}")

    ssh.exec_command("rm -f /tmp/sync_freshness.php")

    print("\nPurging LiteSpeed cache and reloading LSWS...")
    purge_cmd = (
        f"wp litespeed-purge all --path={WP_PATH} --allow-root && "
        "rm -rf /usr/local/lsws/cachedata/* && "
        "/usr/local/lsws/bin/lswsctrl restart"
    )
    stdin, stdout, stderr = ssh.exec_command(purge_cmd)
    print(stdout.read().decode('utf-8'))

    ssh.close()
    print("=== Freshness Sync & Cache Invalidation Complete ===")

if __name__ == '__main__':
    main()

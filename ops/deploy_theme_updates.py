# -*- coding: utf-8 -*-
"""
VietnamGuide Production SFTP Deployer
Deploys updated theme widgets and CSS to VPS with SHA-256 parity verification and cache purge.
"""

import os
import sys
import hashlib
import paramiko

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'

REPO_ROOT = r'M:\Projects\vietnamguide'
REMOTE_ROOT = '/usr/local/lsws/vietnamguide.net/html'

DEPLOY_FILES = [
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-seo.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-airport-navigator.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-airport-navigator.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-cost-calculator.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-cost-calculator.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-itinerary-finder.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-itinerary-finder.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-season-matrix.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-season-matrix.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-visa-checker.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-visa-checker.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css',
     'wp-content/themes/vietnamguide-premium/assets/css/homepage.css'),
]

def get_sha256(filepath):
    with open(filepath, 'rb') as f:
        return hashlib.sha256(f.read()).hexdigest().lower()

def deploy():
    print("=== Deploying Interactive Travel Tools & Theme Updates to VPS ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)
    sftp = ssh.open_sftp()

    all_matched = True
    for local_rel, remote_rel in DEPLOY_FILES:
        local_path = os.path.join(REPO_ROOT, local_rel.replace('/', os.sep))
        remote_path = f"{REMOTE_ROOT}/{remote_rel}"
        local_hash = get_sha256(local_path)

        print(f"Uploading: {local_rel} -> {remote_path}")
        sftp.put(local_path, remote_path)

        # Verify remote SHA256
        cmd = f"sha256sum {remote_path}"
        stdin, stdout, stderr = ssh.exec_command(cmd)
        remote_out = stdout.read().decode().strip()
        remote_hash = remote_out.split()[0].lower() if remote_out else ''

        if local_hash == remote_hash:
            print(f"  [OK] SHA256 Parity: {local_hash}")
        else:
            print(f"  [FAIL] SHA256 Mismatch: local={local_hash}, remote={remote_hash}")
            all_matched = False

    sftp.close()
    ssh.close()

    if not all_matched:
        raise RuntimeError("One or more files failed SHA256 parity check!")

    print("\nPurging LiteSpeed cache and reloading LSWS...")
    ssh2 = paramiko.SSHClient()
    ssh2.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh2.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    purge_cmd = "rm -rf /usr/local/lsws/vietnamguide.net/luucache/* /usr/local/lsws/cachedata/* 2>/dev/null; /usr/local/lsws/bin/lswsctrl restart"
    stdin, stdout, stderr = ssh2.exec_command(purge_cmd)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    if out:
        print(f"  Output: {out}")
    if err:
        print(f"  Notice: {err}")

    ssh2.close()
    print("\n=== Deployment & Cache Purge Complete ===")

if __name__ == '__main__':
    deploy()
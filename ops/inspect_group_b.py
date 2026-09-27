# -*- coding: utf-8 -*-
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

    sample_ids = [14, 15, 19, 173, 178]
    for pid in sample_ids:
        cmd = f"wp post get {pid} --field=post_content --path={WP_PATH} --allow-root"
        stdin, stdout, stderr = ssh.exec_command(cmd)
        content = stdout.read().decode('utf-8')
        m = re.search(r'<figure\s+class="vg-guide-photo".*?</figure>', content, re.DOTALL)
        print(f"=== Post {pid} ===")
        if m:
            print(m.group(0))
        else:
            print("NO vg-guide-photo found")

    ssh.close()

if __name__ == '__main__':
    main()

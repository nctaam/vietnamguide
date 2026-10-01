import paramiko

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

new_desc = "Explore Vietnam travel costs, daily budget calculators, accommodation prices, transit expenses, and realistic spending models across all travel styles in 2026."

php_code = f"""<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
update_post_meta(10, 'rank_math_description', '{new_desc}');
echo "Updated ID 10 rank_math_description successfully.\\n";
"""

sftp = ssh.open_sftp()
with sftp.file('/tmp/update_desc_10.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/update_desc_10.php --path={WP_PATH} --allow-root")
print(stdout.read().decode('utf-8'))
ssh.exec_command(f"wp litespeed-purge all --path={WP_PATH} --allow-root")
ssh.exec_command("rm -f /tmp/update_desc_10.php")
ssh.close()
print("Post 10 updated and cache purged.")

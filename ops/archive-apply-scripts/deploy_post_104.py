# -*- coding: utf-8 -*-
import paramiko, sys

VPS_HOST = "66.42.48.146"
VPS_PORT = 2209
VPS_USER = "root"
VPS_KEY = r"C:\Users\NCTaam\.ssh\deploy_bot_key"
WP_PATH = "/usr/local/lsws/vietnamguide.net/html"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)
sftp = client.open_sftp()

print("Uploading post 104 content...")
with open(r"ops\content_fix\north-central-south-vietnam.html", "r", encoding="utf-8") as f:
    html = f.read()

with sftp.open("/tmp/post_104.html", "w") as f:
    f.write(html)
sftp.close()

print("Updating post 104 via WP-CLI...")
cmd = f"export VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 && wp post update 104 --post_content=\"$(cat /tmp/post_104.html)\" --path={WP_PATH} --allow-root && rm -f /tmp/post_104.html /tmp/vg_b3_*"
stdin, stdout, stderr = client.exec_command(cmd)
print(stdout.read().decode())
print(stderr.read().decode())

print("Purging LiteSpeed cache...")
purge_cmd = (
    "rm -rf /usr/local/lsws/vietnamguide.net/html/wp-content/litespeed/cssjs/* "
    "/usr/local/lsws/vietnamguide.net/html/wp-content/litespeed/htmlc/* "
    "/usr/local/lsws/vietnamguide.net/html/wp-content/cache/litespeed/* 2>/dev/null && "
    "echo 'CACHE PURGED SUCCESSFULLY'"
)
stdin, stdout, stderr = client.exec_command(purge_cmd)
print(stdout.read().decode())

client.close()
print("Post 104 updated and cache purged!")

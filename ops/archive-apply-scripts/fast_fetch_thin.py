# -*- coding: utf-8 -*-
import sys, os, json, paramiko

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

VPS_HOST = "66.42.48.146"
VPS_PORT = 2209
VPS_USER = "root"
VPS_KEY = r"C:\Users\NCTaam\.ssh\deploy_bot_key"
WP_PATH = "/usr/local/lsws/vietnamguide.net/html"
THIN_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "thin_content")
os.makedirs(THIN_DIR, exist_ok=True)

with open('ops/content_audit_results.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

thin_ids = {x['id']: x['slug'] for x in data if x['word_count'] < 1000}
id_list = ",".join(str(i) for i in thin_ids.keys())

print(f"Exporting {len(thin_ids)} thin articles from VPS in single batch...")
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)

cmd = f"wp post list --post__in={id_list} --post_type=page --fields=ID,post_name,post_content --format=json --path={WP_PATH} --allow-root > /tmp/thin_dump.json"
stdin, stdout, stderr = client.exec_command(cmd)
stdout.channel.recv_exit_status()

sftp = client.open_sftp()
local_dump = "ops/thin_dump.json"
sftp.get("/tmp/thin_dump.json", local_dump)
client.exec_command("rm -f /tmp/thin_dump.json")
sftp.close()
client.close()

with open(local_dump, "r", encoding="utf-8") as f:
    posts = json.load(f)

for p in posts:
    slug = p.get('post_name')
    content = p.get('post_content')
    path = os.path.join(THIN_DIR, f"{slug}.html")
    with open(path, "w", encoding="utf-8") as f:
        f.write(content)

os.remove(local_dump)
print(f"Successfully extracted all {len(posts)} thin articles to ops/thin_content/!")

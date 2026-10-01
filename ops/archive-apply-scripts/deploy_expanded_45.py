# -*- coding: utf-8 -*-
"""
Deploy all 45 expanded articles to VPS production.
"""
import sys, os, json, paramiko, time

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

VPS_HOST = "66.42.48.146"
VPS_PORT = 2209
VPS_USER = "root"
VPS_KEY = r"C:\Users\NCTaam\.ssh\deploy_bot_key"
WP_PATH = "/usr/local/lsws/vietnamguide.net/html"
THIN_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "thin_content")

with open('ops/content_audit_results.json', 'r', encoding='utf-8') as f:
    audit_data = json.load(f)

slug_to_id = {x['slug']: x['id'] for x in audit_data}

files = [f for f in os.listdir(THIN_DIR) if f.endswith(".html")]
print(f"Deploying {len(files)} expanded articles to VPS...")

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)
sftp = client.open_sftp()

ok = 0
fail = 0

for idx, filename in enumerate(files, 1):
    slug = filename.replace(".html", "")
    pid = slug_to_id.get(slug)
    if not pid:
        print(f"  Missing ID for {slug}")
        fail += 1
        continue

    local_path = os.path.join(THIN_DIR, filename)
    with open(local_path, "r", encoding="utf-8") as f:
        html = f.read()

    remote_tmp = f"/tmp/exp_{pid}.html"
    with sftp.open(remote_tmp, "w") as rf:
        rf.write(html)

    cmd = (
        f"export VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 && "
        f"wp post update {pid} --post_content=\"$(cat {remote_tmp})\" --path={WP_PATH} --allow-root 2>&1 && "
        f"rm -f {remote_tmp}"
    )
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode("utf-8", errors="replace").strip()
    
    if "Updated" in out or "Success" in out:
        ok += 1
        print(f"  [{idx:>2}/{len(files)}] OK   {slug} (ID:{pid})")
    else:
        fail += 1
        print(f"  [{idx:>2}/{len(files)}] FAIL {slug} (ID:{pid}): {out[:60]}")
    
    time.sleep(0.2)

sftp.close()

print("\nPurging LiteSpeed cache...")
purge_cmd = (
    "rm -rf /usr/local/lsws/vietnamguide.net/html/wp-content/litespeed/cssjs/* "
    "/usr/local/lsws/vietnamguide.net/html/wp-content/litespeed/htmlc/* "
    "/usr/local/lsws/vietnamguide.net/html/wp-content/cache/litespeed/* 2>/dev/null && "
    "echo 'CACHE PURGED'"
)
stdin, stdout, stderr = client.exec_command(purge_cmd)
print(stdout.read().decode().strip())

client.close()
print(f"\nDEPLOYMENT COMPLETE: {ok} OK, {fail} failed out of {len(files)} total articles.")

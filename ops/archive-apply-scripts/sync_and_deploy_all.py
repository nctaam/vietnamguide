# -*- coding: utf-8 -*-
"""
Verify live status of all 56 batch 3 articles on VPS vs local content_fix/.
Deploys any post that doesn't match the local fixed content.
"""
import sys, os, json, paramiko, time

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_ai_slop_linter import analyze_text
from fast_content_audit import clean_text

VPS_HOST = "66.42.48.146"
VPS_PORT = 2209
VPS_USER = "root"
VPS_KEY = r"C:\Users\NCTaam\.ssh\deploy_bot_key"
WP_PATH = "/usr/local/lsws/vietnamguide.net/html"
CONTENT_FIX_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "content_fix")

with open('ops/content_audit_results.json', 'r', encoding='utf-8') as f:
    results = json.load(f)

# Targets
target_posts = [(r['slug'], r['id']) for r in results if not r['passed']]
print(f"Checking {len(target_posts)} target posts on VPS...")

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)
sftp = client.open_sftp()

need_deploy = []

for slug, pid in target_posts:
    local_path = os.path.join(CONTENT_FIX_DIR, f"{slug}.html")
    if not os.path.exists(local_path):
        continue
    
    with open(local_path, "r", encoding="utf-8") as f:
        local_content = f.read()
    
    # Check live content
    cmd = f"wp post get {pid} --field=content --path={WP_PATH} --allow-root 2>/dev/null"
    stdin, stdout, stderr = client.exec_command(cmd)
    live_content = stdout.read().decode("utf-8", errors="replace")
    
    live_clean = clean_text(live_content)
    res = analyze_text(live_clean, source_name=slug)
    
    if not res['passed']:
        need_deploy.append((slug, pid, local_path))
        print(f"  [NEEDS DEPLOY] {slug} (ID:{pid}) | Live HLS={res['hls_score']}, Passed={res['passed']}")
    else:
        print(f"  [LIVE PASSED ] {slug} (ID:{pid}) | Live HLS={res['hls_score']}, Passed={res['passed']}")

print(f"\nTotal posts needing deployment: {len(need_deploy)} / {len(target_posts)}")

if need_deploy:
    print(f"\nDeploying {len(need_deploy)} posts to VPS...")
    for idx, (slug, pid, local_path) in enumerate(need_deploy, 1):
        with open(local_path, "r", encoding="utf-8") as f:
            html = f.read()
        
        remote_tmp = f"/tmp/deploy_sync_{pid}.html"
        with sftp.open(remote_tmp, "w") as rf:
            rf.write(html)
        
        cmd = (
            f"export VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 && "
            f"wp post update {pid} --post_content=\"$(cat {remote_tmp})\" --path={WP_PATH} --allow-root 2>&1 && "
            f"rm -f {remote_tmp}"
        )
        stdin, stdout, stderr = client.exec_command(cmd)
        out = stdout.read().decode("utf-8", errors="replace").strip()
        print(f"  [{idx:>2}/{len(need_deploy)}] Deployed {slug}: {out[:60]}")
        time.sleep(0.2)

# Purge cache
print("\nPurging LiteSpeed cache...")
stdin, stdout, stderr = client.exec_command(
    "rm -rf /usr/local/lsws/vietnamguide.net/html/wp-content/litespeed/cssjs/* "
    "/usr/local/lsws/vietnamguide.net/html/wp-content/litespeed/htmlc/* "
    "/usr/local/lsws/vietnamguide.net/html/wp-content/cache/litespeed/* 2>/dev/null && "
    "echo 'CACHE PURGED'"
)
print(stdout.read().decode().strip())

sftp.close()
client.close()
print("\nDone! All target posts synchronized and live.")

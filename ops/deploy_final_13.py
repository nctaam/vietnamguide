# -*- coding: utf-8 -*-
import sys, os, paramiko, time

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

VPS_HOST = "66.42.48.146"
VPS_PORT = 2209
VPS_USER = "root"
VPS_KEY = r"C:\Users\NCTaam\.ssh\deploy_bot_key"
WP_PATH = "/usr/local/lsws/vietnamguide.net/html"
CONTENT_FIX_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "content_fix")

FINAL_13 = [
    ("phu-quoc-ferry-guide", 1277),
    ("where-to-stay-in-rach-gia", 1276),
    ("sapa-to-ha-giang-transport", 1271),
    ("vietnam-in-june", 757),
    ("da-lat-waterfalls-guide", 753),
    ("tam-coc-vs-trang-an-boat-tour", 717),
    ("saigon-airport-to-district-1", 645),
    ("saigon-street-food-guide", 615),
    ("7-days-in-vietnam", 220),
    ("health-travel-insurance-vietnam", 187),
    ("best-things-to-do-in-hue", 184),
    ("unesco-heritage-sites-vietnam", 170),
    ("best-places-to-visit-vietnam", 110),
]

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)
sftp = client.open_sftp()

print(f"Deploying final {len(FINAL_13)} articles to VPS...")
for idx, (slug, pid) in enumerate(FINAL_13, 1):
    local_path = os.path.join(CONTENT_FIX_DIR, f"{slug}.html")
    if not os.path.exists(local_path):
        print(f"  Missing local file: {slug}")
        continue
    
    with open(local_path, "r", encoding="utf-8") as f:
        html = f.read()
    
    remote_tmp = f"/tmp/f13_{pid}.html"
    with sftp.open(remote_tmp, "w") as rf:
        rf.write(html)
    
    cmd = f"export VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 && wp post update {pid} --post_content=\"$(cat {remote_tmp})\" --path={WP_PATH} --allow-root 2>&1 && rm -f {remote_tmp}"
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode("utf-8", errors="replace").strip()
    print(f"  [{idx:>2}/{len(FINAL_13)}] {slug} (ID:{pid}): {out[:60]}")
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
print("\nFinal 13 articles deployed!")

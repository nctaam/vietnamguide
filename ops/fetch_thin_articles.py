# -*- coding: utf-8 -*-
"""
Fetch all 45 thin articles (< 1000 words) from VPS to local ops/thin_content/
"""
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

thin_articles = [x for x in data if x['word_count'] < 1000]
print(f"Fetching {len(thin_articles)} thin articles from VPS...")

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)

for idx, item in enumerate(thin_articles, 1):
    slug = item['slug']
    pid = item['id']
    local_path = os.path.join(THIN_DIR, f"{slug}.html")
    
    cmd = f"wp post get {pid} --field=content --path={WP_PATH} --allow-root 2>/dev/null"
    stdin, stdout, stderr = client.exec_command(cmd)
    content = stdout.read().decode("utf-8", errors="replace")
    
    with open(local_path, "w", encoding="utf-8") as f:
        f.write(content)
        
    print(f"  [{idx:>2}/{len(thin_articles)}] Fetched {slug} ({len(content)} bytes)")

client.close()
print(f"\nAll {len(thin_articles)} thin articles saved to ops/thin_content/")

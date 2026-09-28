# -*- coding: utf-8 -*-
"""Fetch P1 articles (HLS 70-85) and P2 articles (HLS 90) for fixing."""
import sys, os, json, re, csv, io
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import paramiko
from anti_ai_slop_linter import analyze_text

VPS_HOST = "66.42.48.146"
VPS_PORT = 2209
VPS_USER = "root"
VPS_KEY = r"C:\Users\NCTaam\.ssh\deploy_bot_key"
WP_PATH = "/usr/local/lsws/vietnamguide.net/html"

with open(os.path.join(os.path.dirname(os.path.abspath(__file__)), "content_audit_results.json"), "r", encoding="utf-8") as f:
    data = json.load(f)

# P1: HLS 70-85
p1 = [r for r in data if r["hls_score"] in [70, 80, 85] and r["slug"] not in [
    # Already fixed in batch 2
    "sapa-travel-guide", "cham-islands-travel-guide"
]]
p1.sort(key=lambda x: x["hls_score"])

print(f"P1 articles (HLS 70-85): {len(p1)}")
for r in p1:
    print(f"  HLS={r['hls_score']} | ID={r['id']:>5} | {r['slug']}")

# P2: HLS 90
p2 = [r for r in data if r["hls_score"] == 90 and r["slug"] not in [
    "vietnam-in-february", "vietnam-in-december", "hanoi-vs-ho-chi-minh-city", "mui-ne-vs-nha-trang"
]]
print(f"\nP2 articles (HLS 90): {len(p2)}")
for r in p2:
    print(f"  HLS={r['hls_score']} | ID={r['id']:>5} | {r['slug']}")

# Fetch all P1+P2 from VPS
all_targets = p1 + p2
print(f"\nTotal to fetch: {len(all_targets)}")

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)

save_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), "content_fix")
os.makedirs(save_dir, exist_ok=True)

for r in all_targets:
    slug = r["slug"]
    pid = r["id"]
    print(f"  Fetching {slug}...", end="", flush=True)
    
    cmd = f"wp post get {pid} --field=content --path={WP_PATH} --allow-root 2>/dev/null"
    stdin, stdout, stderr = client.exec_command(cmd)
    html = stdout.read().decode("utf-8", errors="replace")
    
    html_path = os.path.join(save_dir, f"{slug}.html")
    with open(html_path, "w", encoding="utf-8") as f:
        f.write(html)
    print(f" OK ({len(html)} bytes)")

client.close()
print(f"\nDone. {len(all_targets)} articles saved to ops/content_fix/")

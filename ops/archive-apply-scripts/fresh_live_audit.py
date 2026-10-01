# -*- coding: utf-8 -*-
"""
Fresh dump and analysis of all posts on VPS.
"""
import sys, os, json, re, paramiko, time

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_ai_slop_linter import analyze_text
from fast_content_audit import clean_text, compute_quality_score, SKIP_SLUGS

VPS_HOST = "66.42.48.146"
VPS_PORT = 2209
VPS_USER = "root"
VPS_KEY = r"C:\Users\NCTaam\.ssh\deploy_bot_key"
WP_PATH = "/usr/local/lsws/vietnamguide.net/html"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)

print("Exporting fresh JSON from VPS...")
client.exec_command(f"rm -f /tmp/vg_fresh_dump.json")
cmd = f"wp post list --post_type=page --post_status=publish --fields=ID,post_title,post_name,post_content --format=json --path={WP_PATH} --allow-root > /tmp/vg_fresh_dump.json"
stdin, stdout, stderr = client.exec_command(cmd)
stdout.channel.recv_exit_status()

print("Downloading dump via SFTP...")
sftp = client.open_sftp()
local_dump = "ops/vg_fresh_dump.json"
sftp.get("/tmp/vg_fresh_dump.json", local_dump)
client.exec_command("rm -f /tmp/vg_fresh_dump.json")
sftp.close()
client.close()

print(f"Downloaded fresh dump: {os.path.getsize(local_dump)/1024:.1f} KB")

with open(local_dump, "r", encoding="utf-8") as f:
    posts = json.load(f)

results = []
for p in posts:
    slug = p.get("post_name", "")
    pid = p.get("ID")
    title = p.get("post_title", "")
    content = p.get("post_content", "")

    if slug in SKIP_SLUGS:
        continue

    text = clean_text(content)
    if len(text) < 50:
        continue

    analysis = analyze_text(text, source_name=slug)
    qs = compute_quality_score(analysis)
    total_violations = sum([
        analysis.get(f"tier{t}_count", 0) for t in range(1, 13)
    ]) + analysis.get("passive_count", 0)

    results.append({
        "id": pid,
        "title": title,
        "slug": slug,
        "quality_score": qs,
        "hls_score": analysis.get("hls_score", 0),
        "edi": analysis.get("edi", 0),
        "cv": analysis.get("cv", 0),
        "word_count": analysis.get("word_count", 0),
        "total_violations": total_violations,
        "passed": analysis.get("passed", False),
    })

total = len(results)
passed = sum(1 for r in results if r["passed"])
failed = total - passed

print("\n" + "=" * 80)
print("REAL-TIME LIVE AUDIT REPORT ACROSS ALL 287 CONTENT PILLARS")
print("=" * 80)
print(f"Total Content Pillars:   {total}")
print(f"Passed Linter:           {passed} / {total} ({passed/total*100:.1f}%)")
print(f"Failed Linter:           {failed} / {total} ({failed/total*100:.1f}%)")
print(f"Average Quality Score:   {sum(r['quality_score'] for r in results)/total:.1f} / 100")
print(f"Average HLS:             {sum(r['hls_score'] for r in results)/total:.1f} / 100")
print(f"Average EDI:             {sum(r['edi'] for r in results)/total:.1f}")
print(f"Average CV:              {sum(r['cv'] for r in results)/total:.2f}")

hls_100 = sum(1 for r in results if r["hls_score"] == 100)
print(f"HLS = 100 (Flawless):    {hls_100} / {total} ({hls_100/total*100:.1f}%)")
print("=" * 80)

# Save to ops/content_audit_results.json
with open("ops/content_audit_results.json", "w", encoding="utf-8") as f:
    json.dump(results, f, indent=2, ensure_ascii=False)

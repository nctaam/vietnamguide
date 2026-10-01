# -*- coding: utf-8 -*-
"""
Fetch specific articles from VPS and run detailed linter analysis
to identify exact violations that need fixing.
"""
import sys
import os
import json
import re

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__))))
import paramiko
from anti_ai_slop_linter import analyze_text

VPS_HOST = "66.42.48.146"
VPS_PORT = 2209
VPS_USER = "root"
VPS_KEY = r"C:\Users\NCTaam\.ssh\deploy_bot_key"
WP_PATH = "/usr/local/lsws/vietnamguide.net/html"

# Top 5 worst articles by HLS
TARGET_SLUGS = [
    "sapa-vs-ha-giang",                     # HLS=10
    "best-day-trips-from-ho-chi-minh-city",  # HLS=15
    "mekong-delta-overnight-vs-day-trip",    # HLS=30
    "con-dao-travel-guide",                  # HLS=40
    "phu-quoc-travel-guide",                 # HLS=40
]

def ssh_connect():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)
    return client

def ssh_exec(client, cmd):
    stdin, stdout, stderr = client.exec_command(cmd)
    return stdout.read().decode("utf-8", errors="replace"), stderr.read().decode("utf-8", errors="replace")

def strip_html(html):
    text = re.sub(r"<[^>]+>", " ", html)
    text = re.sub(r"\s+", " ", text).strip()
    text = text.replace("&amp;", "&").replace("&lt;", "<").replace("&gt;", ">")
    text = text.replace("&quot;", '"').replace("&#039;", "'").replace("&nbsp;", " ")
    return text

def main():
    client = ssh_connect()

    for slug in TARGET_SLUGS:
        print(f"\n{'='*80}")
        print(f"ARTICLE: {slug}")
        print(f"{'='*80}")

        # Get post ID
        cmd = (f"wp post list --post_type=page --post_status=publish "
               f"--name={slug} --fields=ID --format=csv "
               f"--path={WP_PATH} --allow-root 2>/dev/null")
        out, _ = ssh_exec(client, cmd)
        lines = out.strip().split("\n")
        if len(lines) < 2:
            print(f"  NOT FOUND")
            continue
        pid = lines[1].strip()
        print(f"  Post ID: {pid}")

        # Fetch content
        cmd = f"wp post get {pid} --field=content --path={WP_PATH} --allow-root 2>/dev/null"
        html_content, _ = ssh_exec(client, cmd)
        text = strip_html(html_content)
        print(f"  Word count: {len(text.split())}")

        # Save raw HTML for later editing
        save_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), "content_fix")
        os.makedirs(save_dir, exist_ok=True)
        html_path = os.path.join(save_dir, f"{slug}.html")
        with open(html_path, "w", encoding="utf-8") as f:
            f.write(html_content)
        print(f"  Saved HTML to: {html_path}")

        # Run linter
        result = analyze_text(text, source_name=slug)
        print(f"  HLS: {result['hls_score']}")
        print(f"  EDI: {result['edi']:.1f}")
        print(f"  CV: {result['cv']:.2f}")
        print(f"  Passed: {result['passed']}")

        # Show ALL violations
        violation_keys = [k for k in result.keys() if k.endswith("_violations") and result[k]]
        print(f"\n  VIOLATIONS:")
        for vk in violation_keys:
            violations = result[vk]
            if not violations:
                continue
            category = vk.replace("_violations", "")
            print(f"\n  [{category}] ({len(violations)} violations):")
            for v in violations[:10]:  # limit to 10 per category
                if isinstance(v, dict):
                    print(f"    - {v.get('pattern', v.get('message', str(v)))}")
                    if 'context' in v:
                        ctx = v['context'][:120]
                        print(f"      Context: ...{ctx}...")
                elif isinstance(v, str):
                    print(f"    - {v[:120]}")
                else:
                    print(f"    - {str(v)[:120]}")

    client.close()
    print(f"\n\nDone. HTML files saved in ops/content_fix/")

if __name__ == "__main__":
    main()

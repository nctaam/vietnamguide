# -*- coding: utf-8 -*-
"""
Fetch next batch of P0 articles (HLS 50-60) for fixing.
"""
import sys
import os
import json
import re
import csv
import io

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

# Next batch: HLS 50-60 articles
BATCH2_SLUGS = [
    ("vietnam-in-february", 495),
    ("da-nang-beaches-guide", 501),
    ("where-to-stay-in-ha-giang", 0),  # need to find ID
    ("southern-vietnam-itinerary", 0),
    ("ha-giang-to-cao-bang-transport", 0),
    ("vietnam-in-december", 493),
    ("hue-imperial-city-guide", 500),
    ("sapa-travel-guide", 518),
    ("cham-islands-travel-guide", 254),
    ("mekong-delta-travel-guide", 265),
    ("hanoi-vs-ho-chi-minh-city", 498),
    ("ninh-binh-to-ha-long-bay-transfer", 345),
    ("where-to-stay-in-can-tho", 0),
    ("central-vietnam-itinerary", 0),
    ("vietnam-in-january", 494),
    ("best-beaches-in-vietnam", 204),
    ("sim-esim-vietnam", 15),
    ("mui-ne-vs-nha-trang", 247),
]


def main():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)

    save_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), "content_fix")
    os.makedirs(save_dir, exist_ok=True)

    for slug, pid in BATCH2_SLUGS:
        print(f"\nFetching: {slug}...", end="", flush=True)

        # Find post ID if not known
        if pid == 0:
            cmd = (f"wp post list --post_type=page --post_status=publish "
                   f"--name={slug} --fields=ID --format=csv "
                   f"--path={WP_PATH} --allow-root 2>/dev/null")
            stdin, stdout, stderr = client.exec_command(cmd)
            out = stdout.read().decode("utf-8", errors="replace")
            lines = out.strip().split("\n")
            if len(lines) >= 2:
                pid = int(lines[1].strip())
            else:
                print(f" NOT FOUND")
                continue

        # Fetch content
        cmd = f"wp post get {pid} --field=content --path={WP_PATH} --allow-root 2>/dev/null"
        stdin, stdout, stderr = client.exec_command(cmd)
        html = stdout.read().decode("utf-8", errors="replace")

        # Save HTML
        html_path = os.path.join(save_dir, f"{slug}.html")
        with open(html_path, "w", encoding="utf-8") as f:
            f.write(html)

        # Quick linter check
        text = re.sub(r"<[^>]+>", " ", html)
        text = re.sub(r"\s+", " ", text).strip()
        result = analyze_text(text, source_name=slug)

        # Show violation summary
        violation_keys = [k for k in result.keys() if k.endswith("_violations") and result[k]]
        violation_summary = ", ".join(
            f"{k.replace('_violations','')}: {len(result[k])}"
            for k in violation_keys
        )

        print(f" ID={pid} HLS={result['hls_score']} | {violation_summary}")

    client.close()
    print("\nAll batch 2 articles fetched to ops/content_fix/")


if __name__ == "__main__":
    main()

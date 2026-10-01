# -*- coding: utf-8 -*-
"""Deploy Batch 2 fixed content (18 articles) to VPS."""
import sys
import os
import paramiko
import time

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

VPS_HOST = "66.42.48.146"
VPS_PORT = 2209
VPS_USER = "root"
VPS_KEY = r"C:\Users\NCTaam\.ssh\deploy_bot_key"
WP_PATH = "/usr/local/lsws/vietnamguide.net/html"

ARTICLES = [
    ("vietnam-in-february", 495),
    ("da-nang-beaches-guide", 501),
    ("where-to-stay-in-ha-giang", 968),
    ("southern-vietnam-itinerary", 1038),
    ("ha-giang-to-cao-bang-transport", 1310),
    ("vietnam-in-december", 493),
    ("hue-imperial-city-guide", 500),
    ("sapa-travel-guide", 518),
    ("cham-islands-travel-guide", 254),
    ("mekong-delta-travel-guide", 265),
    ("hanoi-vs-ho-chi-minh-city", 498),
    ("ninh-binh-to-ha-long-bay-transfer", 345),
    ("where-to-stay-in-can-tho", 966),
    ("central-vietnam-itinerary", 1001),
    ("vietnam-in-january", 494),
    ("best-beaches-in-vietnam", 204),
    ("sim-esim-vietnam", 15),
    ("mui-ne-vs-nha-trang", 247),
]

CONTENT_FIX_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "content_fix")


def main():
    print("=" * 70)
    print(f"DEPLOYING BATCH 2 — {len(ARTICLES)} ARTICLES")
    print("=" * 70)

    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)
    sftp = client.open_sftp()

    ok_count = 0
    fail_count = 0

    for slug, pid in ARTICLES:
        local_path = os.path.join(CONTENT_FIX_DIR, f"{slug}.html")
        if not os.path.exists(local_path):
            print(f"  SKIP {slug}: file not found")
            continue

        with open(local_path, "r", encoding="utf-8") as f:
            html = f.read()

        remote_tmp = f"/tmp/vg_b2_{slug}.html"
        with sftp.open(remote_tmp, "w") as rf:
            rf.write(html)

        bash = f"""#!/bin/bash
export VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1
wp post update {pid} --post_content="$(cat {remote_tmp})" --path={WP_PATH} --allow-root 2>&1
rm -f {remote_tmp}
"""
        script_path = f"/tmp/vg_b2_update_{pid}.sh"
        with sftp.open(script_path, "w") as rf:
            rf.write(bash)

        stdin, stdout, stderr = client.exec_command(f"bash {script_path}")
        out = stdout.read().decode("utf-8", errors="replace").strip()
        err = stderr.read().decode("utf-8", errors="replace").strip()

        try:
            sftp.remove(script_path)
        except:
            pass

        success = "Updated" in out or "Success" in out
        if success:
            ok_count += 1
            print(f"  OK  {slug} (ID:{pid})")
        else:
            fail_count += 1
            print(f"  FAIL {slug} (ID:{pid}): {out[:80]} {err[:80]}")

        time.sleep(0.5)

    sftp.close()

    # Purge cache
    print("\n--- Purging LiteSpeed cache ---")
    stdin, stdout, stderr = client.exec_command(
        "rm -rf /usr/local/lsws/vietnamguide.net/html/wp-content/litespeed/cssjs/* "
        "/usr/local/lsws/vietnamguide.net/html/wp-content/litespeed/htmlc/* "
        "/usr/local/lsws/vietnamguide.net/html/wp-content/cache/litespeed/* 2>/dev/null && "
        "echo 'Cache purged'"
    )
    print(f"  {stdout.read().decode().strip()}")
    client.close()

    print(f"\n{'='*70}")
    print(f"BATCH 2 DEPLOYMENT: {ok_count}/{ok_count+fail_count} OK, {fail_count} failed")
    print(f"{'='*70}")


if __name__ == "__main__":
    main()

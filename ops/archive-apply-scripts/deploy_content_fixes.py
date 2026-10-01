# -*- coding: utf-8 -*-
"""
Deploy fixed content to VPS via WP-CLI.
Uploads fixed HTML files and updates WordPress posts.
"""
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

# Articles to deploy: (slug, post_id, local_html_path)
ARTICLES = [
    ("sapa-vs-ha-giang", 504, r"d:\Documents\vietnamguide\ops\content_fix\sapa-vs-ha-giang.html"),
    ("best-day-trips-from-ho-chi-minh-city", 268, r"d:\Documents\vietnamguide\ops\content_fix\best-day-trips-from-ho-chi-minh-city.html"),
    ("mekong-delta-overnight-vs-day-trip", 502, r"d:\Documents\vietnamguide\ops\content_fix\mekong-delta-overnight-vs-day-trip.html"),
    ("con-dao-travel-guide", 237, r"d:\Documents\vietnamguide\ops\content_fix\con-dao-travel-guide.html"),
    ("phu-quoc-travel-guide", 234, r"d:\Documents\vietnamguide\ops\content_fix\phu-quoc-travel-guide.html"),
]


def main():
    print("=" * 70)
    print("DEPLOYING OPTIMIZED CONTENT TO VPS")
    print("=" * 70)

    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)
    sftp = client.open_sftp()

    results = []

    for slug, pid, local_path in ARTICLES:
        print(f"\n--- Deploying: {slug} (Post ID: {pid}) ---")

        # Read local HTML
        with open(local_path, "r", encoding="utf-8") as f:
            html_content = f.read()

        # Upload HTML to temp file on VPS
        remote_tmp = f"/tmp/vg_fix_{slug}.html"
        with sftp.open(remote_tmp, "w") as rf:
            rf.write(html_content)
        print(f"  Uploaded to {remote_tmp} ({len(html_content)} bytes)")

        # Create bash script for wp post update
        bash_script = f"""#!/bin/bash
export VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1
wp post update {pid} --post_content="$(cat {remote_tmp})" --path={WP_PATH} --allow-root 2>&1
rm -f {remote_tmp}
"""
        script_path = f"/tmp/vg_update_{slug}.sh"
        with sftp.open(script_path, "w") as rf:
            rf.write(bash_script)

        # Execute
        stdin, stdout, stderr = client.exec_command(f"bash {script_path}")
        out = stdout.read().decode("utf-8", errors="replace")
        err = stderr.read().decode("utf-8", errors="replace")
        print(f"  WP-CLI output: {out.strip()}")
        if err.strip():
            print(f"  WP-CLI stderr: {err.strip()}")

        # Cleanup script
        try:
            sftp.remove(script_path)
        except:
            pass

        success = "Success" in out or "Updated" in out or f"post {pid}" in out.lower()
        results.append((slug, pid, success, out.strip()))
        status = "OK" if success else "FAILED"
        print(f"  Status: {status}")

        time.sleep(1)

    sftp.close()

    # Purge LiteSpeed cache
    print("\n--- Purging LiteSpeed cache ---")
    stdin, stdout, stderr = client.exec_command(
        "rm -rf /usr/local/lsws/vietnamguide.net/html/wp-content/litespeed/cssjs/* "
        "/usr/local/lsws/vietnamguide.net/html/wp-content/litespeed/htmlc/* "
        "/usr/local/lsws/vietnamguide.net/html/wp-content/cache/litespeed/* 2>/dev/null && "
        "echo 'Cache purged'"
    )
    print(f"  {stdout.read().decode().strip()}")

    client.close()

    # Summary
    print(f"\n{'='*70}")
    print("DEPLOYMENT SUMMARY")
    print(f"{'='*70}")
    ok = sum(1 for _, _, s, _ in results if s)
    print(f"Deployed: {ok}/{len(results)}")
    for slug, pid, success, msg in results:
        status = "OK" if success else "FAILED"
        print(f"  [{status}] {slug} (ID:{pid}): {msg[:60]}")


if __name__ == "__main__":
    main()

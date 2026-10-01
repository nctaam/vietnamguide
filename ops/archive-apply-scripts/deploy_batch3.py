# -*- coding: utf-8 -*-
"""
Deploy Batch 3 (56 articles) to VPS production.
"""
import sys, os, paramiko, time, json

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

VPS_HOST = "66.42.48.146"
VPS_PORT = 2209
VPS_USER = "root"
VPS_KEY = r"C:\Users\NCTaam\.ssh\deploy_bot_key"
WP_PATH = "/usr/local/lsws/vietnamguide.net/html"
CONTENT_FIX_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "content_fix")

with open('ops/vg_all_posts.json', 'r', encoding='utf-8') as f:
    posts = json.load(f)

with open('ops/content_audit_results.json', 'r', encoding='utf-8') as f:
    results = json.load(f)

# The 56 target slugs
failed_slugs = {r['slug']: r['id'] for r in results if not r['passed']}


def main():
    print("=" * 80)
    print(f"DEPLOYING FINAL BATCH 3 — {len(failed_slugs)} ARTICLES TO VPS")
    print("=" * 80)

    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)
    sftp = client.open_sftp()

    ok = 0
    fail = 0
    skip = 0

    for slug, pid in failed_slugs.items():
        local_path = os.path.join(CONTENT_FIX_DIR, f"{slug}.html")
        if not os.path.exists(local_path):
            print(f"  SKIP {slug}: local file not found")
            skip += 1
            continue

        with open(local_path, "r", encoding="utf-8") as f:
            html = f.read()

        remote_tmp = f"/tmp/vg_b3_{pid}.html"
        with sftp.open(remote_tmp, "w") as rf:
            rf.write(html)

        bash = f"""#!/bin/bash
export VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1
wp post update {pid} --post_content="$(cat {remote_tmp})" --path={WP_PATH} --allow-root 2>&1
rm -f {remote_tmp}
"""
        script_path = f"/tmp/vg_b3_update_{pid}.sh"
        with sftp.open(script_path, "w") as rf:
            rf.write(bash)

        stdin, stdout, stderr = client.exec_command(f"bash {script_path}")
        out = stdout.read().decode("utf-8", errors="replace").strip()

        try:
            sftp.remove(script_path)
        except:
            pass

        if "Updated" in out or "Success" in out:
            ok += 1
            print(f"  OK   [{ok:>2}/{len(failed_slugs)}] {slug} (ID:{pid})")
        else:
            fail += 1
            print(f"  FAIL {slug} (ID:{pid}): {out[:80]}")

        time.sleep(0.2)

    sftp.close()

    print("\n--- Purging LiteSpeed cache ---")
    stdin, stdout, stderr = client.exec_command(
        "rm -rf /usr/local/lsws/vietnamguide.net/html/wp-content/litespeed/cssjs/* "
        "/usr/local/lsws/vietnamguide.net/html/wp-content/litespeed/htmlc/* "
        "/usr/local/lsws/vietnamguide.net/html/wp-content/cache/litespeed/* 2>/dev/null && "
        "echo 'Cache purged'"
    )
    print(f"  {stdout.read().decode().strip()}")
    client.close()

    print("\n" + "=" * 80)
    print(f"BATCH 3 DEPLOYMENT COMPLETE: {ok} OK, {fail} failed, {skip} skipped")
    print(f"TOTAL ARTICLES OPTIMIZED & DEPLOYED ACROSS ENTIRE CAMPAIGN: {88 + ok} ARTICLES!")
    print("=" * 80)


if __name__ == "__main__":
    main()

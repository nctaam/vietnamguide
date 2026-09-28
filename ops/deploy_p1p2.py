# -*- coding: utf-8 -*-
"""Deploy ALL P1+P2 fixed content (65 articles) to VPS."""
import sys, os, paramiko, time
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

VPS_HOST = "66.42.48.146"
VPS_PORT = 2209
VPS_USER = "root"
VPS_KEY = r"C:\Users\NCTaam\.ssh\deploy_bot_key"
WP_PATH = "/usr/local/lsws/vietnamguide.net/html"

ARTICLES = [
    # P1 (HLS 70-85) - 31 articles
    ("tam-coc-travel-guide", 418), ("da-nang-vs-hoi-an", 209), ("ninh-binh-travel-guide", 190),
    ("how-to-get-to-con-dao-flight-vs-ferry", 1139), ("where-to-stay-in-mui-ne", 925),
    ("where-to-stay-in-meo-vac", 1070), ("ho-chi-minh-city-to-can-tho-transport", 927),
    ("best-day-trips-from-hanoi", 309), ("old-quarter-vs-french-quarter-vs-west-lake", 301),
    ("phu-quoc-vs-nha-trang", 241), ("bai-tu-long-bay-guide", 201), ("trang-an-vs-tam-coc", 336),
    ("vietnam-rainy-season-flexible-route", 482), ("where-to-stay-in-ho-chi-minh-city", 281),
    ("hanoi-to-sapa-transport", 520), ("hoi-an-vs-hue", 227), ("ha-long-bay-travel-guide", 195),
    ("where-to-stay-in-ben-tre", 1233), ("where-to-stay-in-phu-quoc", 857),
    ("ninh-binh-without-rushing", 478), ("where-to-stay-in-chau-doc", 1203),
    ("quy-nhon-travel-guide", 250), ("hanoi-in-2-days", 320),
    ("best-vietnam-cities-for-first-time-visitors", 497), ("hanoi-travel-guide", 287),
    ("tet-in-vietnam-travel-guide", 496), ("ha-long-bay-vs-lan-ha-bay", 21),
    ("hoi-an-ancient-town-guide", 499), ("mai-chau-to-pu-luong-transport", 1499),
    ("hanoi-to-phong-nha-transport", 823), ("hue-street-food-guide", 791),
    # P2 (HLS 90) - 34 articles
    ("what-to-pack-for-vietnam-region-season", 475), ("best-time-to-visit-vietnam", 14),
    ("cu-chi-tunnels-vs-mekong-delta-day-trip", 279), ("hanoi-first-time-visitor-mistakes", 477),
    ("hanoi-to-ha-giang-transport", 521), ("ly-son-travel-guide", 257),
    ("phong-nha-travel-guide", 503), ("da-nang-travel-guide", 213), ("21-days-in-vietnam", 224),
    ("ho-chi-minh-city-travel-guide", 262), ("northwest-vietnam-itinerary", 1069),
    ("pu-luong-trekking-routes-guide", 1037), ("where-to-stay-in-da-lat", 888),
    ("ha-long-bay-day-trip-vs-overnight-cruise", 682), ("where-to-stay-in-hoi-an", 681),
    ("safety-scams-vietnam", 181), ("vietnam-in-april", 685), ("cat-ba-travel-guide", 198),
    ("hanoi-airport-to-old-quarter", 316), ("pleiku-to-kon-tum-transport", 1274),
    ("vietnam-in-may", 721), ("cat-ba-to-ninh-binh-transport", 1273),
    ("ha-giang-easy-rider-vs-self-drive", 523), ("nha-trang-travel-guide", 244),
    ("hanoi-to-ninh-binh-transport", 331), ("ninh-binh-day-trip-vs-overnight", 326),
    ("ha-giang-loop-planning-guide", 519), ("best-things-to-do-in-hanoi", 173),
    ("14-days-in-vietnam", 20), ("where-to-stay-in-hanoi", 294),
    ("ha-long-bay-cruise-questions-before-booking", 479), ("where-to-stay-in-ninh-binh", 341),
    ("vietnam-food-safety-street-food-etiquette", 480), ("best-islands-in-vietnam", 231),
]

CONTENT_FIX_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "content_fix")


def main():
    print("=" * 70)
    print(f"DEPLOYING P1+P2 — {len(ARTICLES)} ARTICLES")
    print("=" * 70)

    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)
    sftp = client.open_sftp()

    ok = 0
    fail = 0
    skip = 0

    for slug, pid in ARTICLES:
        local_path = os.path.join(CONTENT_FIX_DIR, f"{slug}.html")
        if not os.path.exists(local_path):
            print(f"  SKIP {slug}: file not found")
            skip += 1
            continue

        with open(local_path, "r", encoding="utf-8") as f:
            html = f.read()

        remote_tmp = f"/tmp/vg_p1p2_{pid}.html"
        with sftp.open(remote_tmp, "w") as rf:
            rf.write(html)

        bash = f"#!/bin/bash\nexport VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1\nwp post update {pid} --post_content=\"$(cat {remote_tmp})\" --path={WP_PATH} --allow-root 2>&1\nrm -f {remote_tmp}\n"
        script_path = f"/tmp/vg_p1p2_{pid}.sh"
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
            print(f"  OK  {slug} (ID:{pid})")
        else:
            fail += 1
            print(f"  FAIL {slug} (ID:{pid}): {out[:80]}")

        time.sleep(0.3)

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

    print(f"\n{'='*70}")
    print(f"P1+P2 DEPLOYMENT: {ok} OK, {fail} failed, {skip} skipped")
    print(f"TOTAL DEPLOYED TODAY: 23 (P0) + {ok} (P1+P2) = {23+ok} articles")
    print(f"{'='*70}")


if __name__ == "__main__":
    main()

# -*- coding: utf-8 -*-
"""
VietnamGuide Real-Time Search Engine Discovery & Syndication Pinger
Dispatches instant publishing signals to Google WebSub (PubSubHubbub) hubs,
Bing IndexNow, and checks feed health for search crawler discovery.
"""

import sys
import urllib.request
import urllib.parse
import urllib.error
import subprocess

FEED_URL = "https://vietnamguide.net/feed/"
SITEMAP_URL = "https://vietnamguide.net/sitemap_index.xml"

WEBSUB_HUBS = [
    "https://pubsubhubbub.appspot.com/",
    "https://superfeedr.com/hubbub",
]

def ping_websub_hub(hub_url, feed_url):
    data = urllib.parse.urlencode({
        'hub.mode': 'publish',
        'hub.url': feed_url
    }).encode('utf-8')

    req = urllib.request.Request(
        hub_url,
        data=data,
        headers={
            'Content-Type': 'application/x-www-form-urlencoded',
            'User-Agent': 'VietnamGuide-Pinger/1.0'
        }
    )

    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            print(f"[WEBSUB] {hub_url} -> HTTP {resp.status} {resp.reason}")
            return True
    except urllib.error.HTTPError as e:
        # Some hubs return 204 No Content or accepted codes
        if e.code in (200, 204):
            print(f"[WEBSUB] {hub_url} -> HTTP {e.code} (Accepted)")
            return True
        print(f"[WEBSUB] {hub_url} -> HTTP Error: {e.code} {e.reason}")
        return False
    except Exception as e:
        print(f"[WEBSUB] {hub_url} -> Exception: {e}")
        return False

def verify_feed_health():
    req = urllib.request.Request(
        FEED_URL,
        headers={'User-Agent': 'VietnamGuide-Pinger/1.0'}
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            content = resp.read().decode('utf-8', errors='ignore')
            has_rss = '<rss version="2.0"' in content
            item_count = content.count('<item>')
            print(f"[FEED HEALTH] HTTP {resp.status} OK (Valid RSS: {has_rss}, Items: {item_count})")
            return resp.status == 200 and has_rss
    except Exception as e:
        print(f"[FEED HEALTH] Failed to fetch feed: {e}")
        return False

def run_indexnow():
    print("\n[INDEXNOW] Dispatching IndexNow bulk URL submission...")
    res = subprocess.run([sys.executable, "ops/submit_indexnow.py"], capture_output=True, text=True)
    print(res.stdout)
    if res.stderr:
        print("STDERR:", res.stderr)
    return res.returncode == 0

def verify_webmaster_endpoints():
    print("\n--- Verifying Webmaster Authentication Endpoints ---")
    endpoints = [
        ("https://vietnamguide.net/BingSiteAuth.xml", "<users>"),
        ("https://vietnamguide.net/852ef594b29d4da5a639612da3430b0f.txt", "852ef594b29d4da5a639612da3430b0f"),
    ]
    all_ok = True
    for url, needle in endpoints:
        req = urllib.request.Request(url, headers={'User-Agent': 'VietnamGuide-Pinger/1.0'})
        try:
            with urllib.request.urlopen(req, timeout=15) as resp:
                content = resp.read().decode('utf-8', errors='ignore')
                matched = needle in content
                print(f"[AUTH ENDPOINT] {url} -> HTTP {resp.status} (Contains token: {matched})")
                if resp.status != 200 or not matched:
                    all_ok = False
        except Exception as e:
            print(f"[AUTH ENDPOINT] Failed for {url}: {e}")
            all_ok = False
    return all_ok

def main():
    print("=== VietnamGuide Search Engine Discovery Acceleration ===")
    print(f"Target Feed: {FEED_URL}")
    print(f"Target Sitemap: {SITEMAP_URL}\n")

    # 1. Verify Feed Health
    feed_ok = verify_feed_health()
    if not feed_ok:
        print("[WARNING] Feed health verification did not pass.")

    # 2. Verify Webmaster Authentication Endpoints
    auth_ok = verify_webmaster_endpoints()

    # 3. Ping WebSub Hubs for instant Google / feed reader discovery
    print("\n--- Pinging WebSub / PubSubHubbub Hubs ---")
    hub_results = []
    for hub in WEBSUB_HUBS:
        ok = ping_websub_hub(hub, FEED_URL)
        hub_results.append(ok)

    # 4. Dispatch IndexNow
    indexnow_ok = run_indexnow()

    print("\n=== SUMMARY ===")
    print(f"Feed Health     : {'PASS' if feed_ok else 'FAIL'}")
    print(f"Webmaster Auth  : {'PASS' if auth_ok else 'FAIL'}")
    print(f"WebSub Pings    : {sum(hub_results)}/{len(WEBSUB_HUBS)} successful")
    print(f"IndexNow        : {'PASS' if indexnow_ok else 'FAIL'}")

    if feed_ok and indexnow_ok and auth_ok:
        print("\n[SUCCESS] Search engine syndication and discovery dispatched.")
        sys.exit(0)
    else:
        print("\n[WARNING] Completed with non-fatal warnings.")
        sys.exit(0)

if __name__ == '__main__':
    main()

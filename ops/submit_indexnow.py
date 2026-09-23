# -*- coding: utf-8 -*-
"""
VietnamGuide IndexNow URL Submitter
Extracts all URLs from the live sitemap and submits them to the IndexNow protocol
(Bing, Yandex, Seznam, Naver) for instant crawl and indexing.
"""

import sys
import json
import urllib.request
import urllib.error
import xml.etree.ElementTree as ET

HOST = "vietnamguide.net"
KEY = "852ef594b29d4da5a639612da3430b0f"
KEY_LOCATION = f"https://{HOST}/{KEY}.txt"
SITEMAP_INDEX_URL = f"https://{HOST}/sitemap_index.xml"
SITEMAP_URL = f"https://{HOST}/page-sitemap.xml"

ENDPOINTS = [
    "https://api.indexnow.org/indexnow",
    "https://www.bing.com/indexnow",
]

def fetch_xml(url):
    req = urllib.request.Request(
        url,
        headers={"User-Agent": "VietnamGuide-IndexNow-Bot/1.0"}
    )
    with urllib.request.urlopen(req, timeout=15) as resp:
        return resp.read()

def fetch_sitemap_urls():
    ns = {"sm": "http://www.sitemaps.org/schemas/sitemap/0.9"}
    try:
        xml_data = fetch_xml(SITEMAP_INDEX_URL)
        root = ET.fromstring(xml_data)
        sitemap_locs = [elem.text.strip() for elem in root.findall(".//sm:sitemap/sm:loc", ns) if elem.text]
    except Exception as e:
        print(f"Warning: Failed to parse sitemap index ({e}), falling back to direct sitemap.")
        sitemap_locs = [SITEMAP_URL]

    if not sitemap_locs:
        sitemap_locs = [SITEMAP_URL]

    all_urls = []
    for sm_url in sitemap_locs:
        try:
            sub_xml = fetch_xml(sm_url)
            sub_root = ET.fromstring(sub_xml)
            urls = [elem.text.strip() for elem in sub_root.findall(".//sm:loc", ns) if elem.text]
            all_urls.extend(urls)
        except Exception as e:
            print(f"Error fetching child sitemap {sm_url}: {e}")

    # Remove duplicates while preserving order
    seen = set()
    deduped = []
    for u in all_urls:
        if u not in seen:
            seen.add(u)
            deduped.append(u)
    return deduped

def submit_indexnow(endpoint, urls):
    payload = {
        "host": HOST,
        "key": KEY,
        "keyLocation": KEY_LOCATION,
        "urlList": urls,
    }
    data = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(
        endpoint,
        data=data,
        headers={
            "Content-Type": "application/json; charset=utf-8",
            "User-Agent": "VietnamGuide-IndexNow-Bot/1.0",
        },
        method="POST"
    )
    try:
        with urllib.request.urlopen(req, timeout=20) as resp:
            status = resp.status
            reason = resp.reason
            body = resp.read().decode("utf-8", errors="ignore")
            print(f"[{endpoint}] Success: HTTP {status} {reason} (Payload size: {len(urls)} URLs)")
            if body:
                print(f"  Response body: {body.strip()}")
            return True
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8", errors="ignore")
        print(f"[{endpoint}] HTTP Error: {e.code} {e.reason}")
        if body:
            print(f"  Error details: {body.strip()}")
        return False
    except Exception as e:
        print(f"[{endpoint}] Request Exception: {e}")
        return False

def main():
    print(f"=== VietnamGuide IndexNow Auto-Submission ===")
    print(f"Fetching URLs from {SITEMAP_INDEX_URL}...")
    urls = fetch_sitemap_urls()
    print(f"Found {len(urls)} URLs.")

    if not urls:
        print("ERROR: No URLs found in sitemap.")
        sys.exit(1)

    all_ok = True
    for ep in ENDPOINTS:
        print(f"\nSubmitting {len(urls)} URLs to {ep}...")
        ok = submit_indexnow(ep, urls)
        if not ok:
            all_ok = False

    if all_ok:
        print("\n[SUCCESS] IndexNow submission dispatched to all search engines.")
        sys.exit(0)
    else:
        print("\n[WARNING] Some IndexNow submissions failed.")
        sys.exit(1)

if __name__ == "__main__":
    main()

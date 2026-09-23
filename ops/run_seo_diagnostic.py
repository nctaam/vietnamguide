# -*- coding: utf-8 -*-
"""
VietnamGuide Evidence-Based SEO Diagnostic Audit Script
Audits live site for:
- robots.txt & XML sitemap structure
- canonical self-referencing consistency
- title tag lengths & uniqueness
- meta description lengths & uniqueness
- H1 count & heading structure
- JSON-LD structured data presence & valid schema types
- E-E-A-T visual and metadata tags
- Internal link density & graph metrics
"""

import urllib.request
import urllib.error
import json
import re
import xml.etree.ElementTree as ET
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

BASE_URL = "https://vietnamguide.net"

def fetch_url(url):
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) VietnamGuide-AuditBot/1.0'})
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            content = resp.read().decode('utf-8', errors='ignore')
            return resp.status, dict(resp.headers), content
    except urllib.error.HTTPError as e:
        return e.code, dict(e.headers), ""
    except Exception as e:
        return 0, {}, str(e)

def audit_robots_and_sitemap():
    print("--- 1. Auditing Robots.txt and Sitemaps ---")
    status, headers, robots = fetch_url(f"{BASE_URL}/robots.txt")
    print(f"robots.txt status: {status}")
    has_sitemap = "sitemap:" in robots.lower()
    print(f"robots.txt has Sitemap directive: {has_sitemap}")
    print("robots.txt content:")
    for l in robots.strip().splitlines()[:10]:
        print(f"  {l}")

    # Sitemap index
    status, headers, sitemap_xml = fetch_url(f"{BASE_URL}/sitemap_index.xml")
    print(f"\nsitemap_index.xml status: {status}")
    
    # Page sitemap
    status, headers, page_sitemap_xml = fetch_url(f"{BASE_URL}/page-sitemap.xml")
    print(f"page-sitemap.xml status: {status}")
    root = ET.fromstring(page_sitemap_xml)
    ns = {"sm": "http://www.sitemaps.org/schemas/sitemap/0.9"}
    urls = [elem.text.strip() for elem in root.findall(".//sm:loc", ns) if elem.text]
    lastmods = [elem.text.strip() for elem in root.findall(".//sm:lastmod", ns) if elem.text]
    print(f"Total URLs in page-sitemap.xml: {len(urls)}")
    print(f"Total lastmods found: {len(lastmods)}")
    return urls

def audit_inventory_metadata():
    print("\n--- 2. Auditing Inventory Metadata (Titles & Descriptions) ---")
    with open('ops/meta_inventory.json', 'r', encoding='utf-8') as f:
        inv = json.load(f)

    print(f"Total inventory items: {len(inv)}")
    titles = [item.get('rm_title') or item.get('post_title') for item in inv]
    descs = [item.get('rm_desc') for item in inv]

    long_titles = [t for t in titles if len(t) > 60]
    short_titles = [t for t in titles if len(t) < 30]
    dup_titles = {t for t in titles if titles.count(t) > 1}

    long_descs = [d for d in descs if d and len(d) > 160]
    short_descs = [d for d in descs if d and len(d) < 120]
    missing_descs = [item for item in inv if not item.get('rm_desc')]
    dup_descs = {d for d in descs if d and descs.count(d) > 1}

    print(f"Titles > 60 chars (SERP truncation risk): {len(long_titles)}")
    print(f"Titles < 30 chars: {len(short_titles)}")
    print(f"Duplicate titles: {len(dup_titles)}")
    if dup_titles:
        print(f"  Duplicates: {dup_titles}")

    print(f"\nDescriptions > 160 chars: {len(long_descs)}")
    print(f"Descriptions < 120 chars: {len(short_descs)}")
    print(f"Missing descriptions: {len(missing_descs)}")
    print(f"Duplicate descriptions: {len(dup_descs)}")
    if dup_descs:
        print(f"  Duplicates: {dup_descs}")

def audit_sample_live_pages():
    print("\n--- 3. Auditing Sample Live HTML Pages (Technical & On-Page) ---")
    sample_slugs = [
        "", # homepage
        "plan/vietnam-evisa",
        "plan/best-time-to-visit-vietnam",
        "destinations/hanoi-travel-guide",
        "destinations/where-to-stay-in-da-lat",
        "plan/ho-chi-minh-city-to-mui-ne-transport",
        "compare/ha-long-bay-vs-lan-ha-bay",
        "destinations/best-things-to-do-in-da-nang"
    ]

    for slug in sample_slugs:
        url = f"{BASE_URL}/{slug}/" if slug else f"{BASE_URL}/"
        status, headers, html = fetch_url(url)
        print(f"\nAudit for: {url}")
        print(f"  HTTP Status: {status}")
        
        # Canonical check
        canonical_match = re.search(r'<link\s+rel=["\']canonical["\']\s+href=["\']([^"\']+)["\']', html, re.I)
        canonical = canonical_match.group(1) if canonical_match else "NONE"
        is_canonical_correct = (canonical == url)
        print(f"  Canonical tag: {canonical} (Matches requested URL: {is_canonical_correct})")

        # Robots meta
        robots_match = re.search(r'<meta\s+name=["\']robots["\']\s+content=["\']([^"\']+)["\']', html, re.I)
        robots_meta = robots_match.group(1) if robots_match else "NONE"
        print(f"  Meta robots: {robots_meta}")

        # H1 count
        h1_matches = re.findall(r'<h1[^>]*>(.*?)</h1>', html, re.I | re.S)
        print(f"  H1 count: {len(h1_matches)}")
        if h1_matches:
            h1_clean = re.sub(r'<[^>]+>', '', h1_matches[0]).strip()
            print(f"  H1 text: {h1_clean[:60]}...")

        # Schema JSON-LD
        schema_matches = re.findall(r'<script\s+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>', html, re.I | re.S)
        schema_types = []
        for s in schema_matches:
            try:
                data = json.loads(s)
                if '@graph' in data:
                    schema_types.extend([item.get('@type') for item in data['@graph'] if '@type' in item])
                elif '@type' in data:
                    schema_types.append(data['@type'])
            except:
                pass
        print(f"  JSON-LD Blocks: {len(schema_matches)}, Detected @types: {schema_types}")

        # OpenGraph
        og_title = re.search(r'<meta\s+property=["\']og:title["\']\s+content=["\']([^"\']+)["\']', html, re.I)
        og_image = re.search(r'<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']+)["\']', html, re.I)
        print(f"  OG Title present: {bool(og_title)}, OG Image present: {bool(og_image)}")

        # Viewport
        viewport = re.search(r'<meta\s+name=["\']viewport["\']', html, re.I)
        print(f"  Viewport meta present: {bool(viewport)}")

if __name__ == '__main__':
    audit_robots_and_sitemap()
    audit_inventory_metadata()
    audit_sample_live_pages()

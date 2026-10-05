# -*- coding: utf-8 -*-
"""
VietnamGuide Technical SEO Diagnostic Part 2:
- Redirect Chains & Trailing Slash Consistency
- HTTP Protocol & Security Response Headers
- Image Alt attributes & Broken Link Sample
"""

import urllib.request
import urllib.error
import http.client
from urllib.parse import urlparse
import json
import re
import sys

def test_redirect(url):
    parsed = urlparse(url)
    conn = http.client.HTTPSConnection(parsed.netloc, timeout=10) if parsed.scheme == 'https' else http.client.HTTPConnection(parsed.netloc, timeout=10)
    path = parsed.path or '/'
    conn.request('HEAD', path)
    resp = conn.getresponse()
    location = resp.getheader('Location')
    return resp.status, location

print("--- 1. Testing Canonical Redirects & Host Normalization ---")
test_cases = [
    "http://vietnamguide.net/",
    "http://www.vietnamguide.net/",
    "https://www.vietnamguide.net/",
    "https://vietnamguide.net/plan/vietnam-evisa", # without trailing slash
]

for tc in test_cases:
    status, loc = test_redirect(tc)
    print(f"URL: {tc} -> HTTP {status} (Location: {loc})")

print("\n--- 2. Auditing Production HTTP Response Headers ---")
req = urllib.request.Request("https://vietnamguide.net/plan/vietnam-evisa/", headers={'User-Agent': 'Mozilla/5.0'})
with urllib.request.urlopen(req) as resp:
    headers = dict(resp.headers)
    print(f"Server: {headers.get('server')}")
    print(f"Content-Encoding (Compression): {headers.get('content-encoding')}")
    print(f"Strict-Transport-Security: {headers.get('strict-transport-security')}")
    print(f"X-Content-Type-Options: {headers.get('x-content-type-options')}")
    print(f"X-Frame-Options: {headers.get('x-frame-options')}")
    print(f"LiteSpeed Cache: {headers.get('x-litespeed-cache')}")
    print(f"Cache-Control: {headers.get('cache-control')}")

print("\n--- 3. Sampling Internal Links For 404s ---")
with open('ops/stage69_mesh_ops.json', 'r', encoding='utf-8') as f:
    mesh = json.load(f)

extracted_links = set()
for pid, val in mesh.items():
    content = val['new_content']
    found = re.findall(r'href=["\'](/[^"\'#?]+/)["\']', content)
    extracted_links.update(found)

print(f"Sampled {len(extracted_links)} unique internal link targets from mesh:")
broken = []
for link in list(extracted_links)[:25]:
    test_url = f"https://vietnamguide.net{link}"
    try:
        r = urllib.request.Request(test_url, headers={'User-Agent': 'Mozilla/5.0'}, method='HEAD')
        with urllib.request.urlopen(r, timeout=10) as resp:
            pass
    except urllib.error.HTTPError as e:
        broken.append((test_url, e.code))
    except Exception as e:
        broken.append((test_url, str(e)))

print(f"Broken links found in sample: {len(broken)}")
if broken:
    for b in broken:
        print(f"  Broken: {b[0]} -> {b[1]}")

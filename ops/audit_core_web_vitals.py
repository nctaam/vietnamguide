# -*- coding: utf-8 -*-
"""
VietnamGuide Core Web Vitals & Real-Experience Audit
Audits:
- TTFB and compression headers
- Hero image optimizations (fetchpriority, loading, decoding)
- Image dimensions (width/height attributes to prevent CLS)
- Image alt text coverage (accessibility & image SEO)
- Asset size & external requests
"""
import urllib.request
import time
import re
import json

SAMPLE_URLS = [
    'https://vietnamguide.net/',
    'https://vietnamguide.net/destinations/hanoi-travel-guide/',
    'https://vietnamguide.net/destinations/da-nang-travel-guide/',
    'https://vietnamguide.net/destinations/ha-long-bay-travel-guide/',
    'https://vietnamguide.net/plan/vietnam-evisa/',
    'https://vietnamguide.net/plan/best-time-to-visit-vietnam/',
    'https://vietnamguide.net/routes/10-days-in-vietnam/',
    'https://vietnamguide.net/plan/vietnam-travel-cost/',
]

print("=== Auditing Core Web Vitals & Real Experience Performance ===")

for url in SAMPLE_URLS:
    start_time = time.time()
    req = urllib.request.Request(url, headers={
        'User-Agent': 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1',
        'Accept-Encoding': 'gzip, deflate, br'
    })
    
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            headers = dict(resp.info())
            raw_body = resp.read()
            ttfb = time.time() - start_time
            
            # Check encoding
            encoding = headers.get('Content-Encoding', 'none')
            content_type = headers.get('Content-Type', '')
            cache_ctrl = headers.get('Cache-Control', 'none')
            x_litespeed = headers.get('X-LiteSpeed-Cache', 'none')
            
            # If gzip, decompress
            if encoding == 'gzip':
                import gzip
                html = gzip.decompress(raw_body).decode('utf-8', errors='ignore')
            else:
                html = raw_body.decode('utf-8', errors='ignore')
            
            # Check hero image
            hero_matches = re.findall(r'<img[^>]*class=["\'][^"\']*vg-guide-hero[^"\']*["\'][^>]*>|<img[^>]*data-object-fit=["\']cover["\'][^>]*>', html)
            
            # Check all images for width/height and alt
            all_imgs = re.findall(r'<img\s+([^>]+)>', html)
            missing_dims = 0
            missing_alt = 0
            for img_attrs in all_imgs:
                has_w = 'width=' in img_attrs.lower()
                has_h = 'height=' in img_attrs.lower()
                if not (has_w and has_h):
                    missing_dims += 1
                if 'alt=' not in img_attrs.lower():
                    missing_alt += 1
            
            print(f"\nURL: {url}")
            print(f"  TTFB: {ttfb*1000:.1f}ms | Encoding: {encoding} | Size: {len(raw_body)/1024:.1f}KB | LSCache: {x_litespeed}")
            print(f"  Images: {len(all_imgs)} total | Missing W/H (CLS risk): {missing_dims} | Missing Alt: {missing_alt}")
            if hero_matches:
                print(f"  Hero Img tag: {hero_matches[0][:120]}...")
            
    except Exception as e:
        print(f"\nURL: {url} -> ERROR: {e}")

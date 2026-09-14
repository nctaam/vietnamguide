# -*- coding: utf-8 -*-
"""
VietnamGuide SEO Metadata & Keyword Quality Analyzer
Evaluates all 102 pages against SERP CTR best practices, search intent, and length bounds.
"""

import json

def analyze():
    with open('ops/meta_inventory.json', 'r', encoding='utf-8') as f:
        pages = json.load(f)

    print(f"=== VietnamGuide SEO Inventory Analysis ({len(pages)} Pages) ===\n")

    missing_keyword = []
    missing_title = []
    missing_desc = []
    long_titles = []
    short_titles = []
    long_descs = []
    short_descs = []
    instructional_descs = []
    keyword_not_in_title = []
    keyword_not_in_desc = []

    for p in pages:
        pid = p['ID']
        slug = p['post_name']
        title = (p['rm_title'] or p['post_title'] or '').strip()
        desc = (p['rm_desc'] or '').strip()
        kw = (p['rm_keyword'] or '').strip()

        if not kw:
            missing_keyword.append((pid, slug, title))
        if not title:
            missing_title.append((pid, slug))
        if not desc:
            missing_desc.append((pid, slug))

        # Title length (Desktop Google SERP truncates ~60 chars)
        if len(title) > 60:
            long_titles.append((pid, slug, len(title), title))
        elif len(title) < 30:
            short_titles.append((pid, slug, len(title), title))

        # Description length (Optimal 130-160 chars)
        if len(desc) < 120:
            short_descs.append((pid, slug, len(desc), desc))
        elif len(desc) > 165:
            long_descs.append((pid, slug, len(desc), desc))

        # Check instructional / passive tone
        if desc.lower().startswith(('help travelers', 'help visitors', 'help sapa', 'designed to', 'help decide')):
            instructional_descs.append((pid, slug, desc))

        # Keyword alignment
        if kw:
            # check if primary keyword words are in title
            kw_clean = kw.lower()
            if kw_clean not in title.lower():
                keyword_not_in_title.append((pid, slug, kw, title))
            if kw_clean not in desc.lower():
                keyword_not_in_desc.append((pid, slug, kw, desc))

    print(f"1. Missing Focus Keyword: {len(missing_keyword)}")
    for pid, slug, t in missing_keyword:
        print(f"   - [ID {pid}] /{slug}/ -> '{t}'")

    print(f"\n2. Instructional / Non-SERP Descriptions ('Help travelers...'): {len(instructional_descs)}")
    for pid, slug, d in instructional_descs:
        print(f"   - [ID {pid}] /{slug}/ ({len(d)} chars): \"{d}\"")

    print(f"\n3. Short Meta Descriptions (< 120 chars): {len(short_descs)}")
    for pid, slug, l, d in short_descs:
        print(f"   - [ID {pid}] /{slug}/ ({l} chars): \"{d}\"")

    print(f"\n4. Long Titles (> 60 chars, SERP truncation risk): {len(long_titles)}")
    for pid, slug, l, t in long_titles[:10]:
        print(f"   - [ID {pid}] /{slug}/ ({l} chars): \"{t}\"")
    if len(long_titles) > 10:
        print(f"   ... and {len(long_titles) - 10} more.")

    print(f"\n5. Keyword Missing from Title: {len(keyword_not_in_title)}")
    for pid, slug, kw, t in keyword_not_in_title[:10]:
        print(f"   - [ID {pid}] /{slug}/ KW: '{kw}' | Title: '{t}'")
    if len(keyword_not_in_title) > 10:
        print(f"   ... and {len(keyword_not_in_title) - 10} more.")

    print(f"\n6. Keyword Missing from Description: {len(keyword_not_in_desc)}")
    for pid, slug, kw, d in keyword_not_in_desc[:10]:
        print(f"   - [ID {pid}] /{slug}/ KW: '{kw}' | Desc: '{d}'")
    if len(keyword_not_in_desc) > 10:
        print(f"   ... and {len(keyword_not_in_desc) - 10} more.")

if __name__ == '__main__':
    analyze()

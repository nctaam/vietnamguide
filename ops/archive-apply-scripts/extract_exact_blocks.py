# -*- coding: utf-8 -*-
"""
Helper to extract exact context around search terms in specific posts.
"""

import json
import sys

sys.stdout.reconfigure(encoding='utf-8')

sources = json.load(open('ops/stage62_source_contents.json', 'r', encoding='utf-8'))

def get_context(pid, term, span=150):
    p = sources.get(str(pid))
    if not p:
        print(f"Post {pid} not found")
        return
    c = p['content']
    idx = 0
    found = 0
    while True:
        pos = c.find(term, idx)
        if pos == -1:
            break
        found += 1
        start = max(0, pos - 60)
        end = min(len(c), pos + len(term) + span)
        print(f"--- Post {pid} ({p['slug']}) match {found} at pos {pos} ---")
        print(c[start:end])
        print("="*60)
        idx = pos + len(term)
        if found >= 3:
            break
    if found == 0:
        print(f"Term '{term}' NOT FOUND in Post {pid} ({p['slug']})")

if __name__ == '__main__':
    args = sys.argv[1:]
    if len(args) >= 2:
        pid = int(args[0])
        term = args[1]
        get_context(pid, term)
    else:
        print("Usage: python extract_exact_blocks.py <post_id> <search_term>")

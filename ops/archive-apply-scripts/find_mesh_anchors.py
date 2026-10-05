# -*- coding: utf-8 -*-
"""
Interactive search tool to find exact paragraph / table strings in the 24 source posts.
"""

import json
import sys

sys.stdout.reconfigure(encoding='utf-8')

sources = json.load(open('ops/stage62_source_contents.json', 'r', encoding='utf-8'))

def show_matches(pid, terms):
    p = sources.get(str(pid))
    if not p:
        print(f"Post {pid} NOT FOUND")
        return
    print(f"\n=======================================================")
    print(f"Post {pid}: {p['slug']}")
    print(f"=======================================================")
    lines = p['content'].split('\n')
    for i, line in enumerate(lines):
        if all(term.lower() in line.lower() for term in terms):
            print(f"L{i}: {line}")

if __name__ == '__main__':
    if len(sys.argv) > 2:
        pid = int(sys.argv[1])
        terms = sys.argv[2:]
        show_matches(pid, terms)

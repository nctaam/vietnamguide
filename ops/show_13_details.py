# -*- coding: utf-8 -*-
import sys, os, json, re
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_ai_slop_linter import analyze_text
from fast_content_audit import clean_text

CONTENT_FIX_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "content_fix")

slugs = [
    "quy-nhon-to-nha-trang-transport",
    "vietnam-domestic-flights-guide",
    "phu-quoc-ferry-guide",
    "vietnam-night-train-safety-tips",
    "where-to-stay-in-dong-hoi",
    "vietnam-vegetarian-travel-guide",
    "where-to-stay-in-ha-long-bay",
    "vietnam-in-july",
    "hanoi-to-ha-long-bay-transport",
    "where-to-stay-in-da-nang",
    "ly-son-vs-cham-islands",
    "saigon-street-food-guide",
    "where-to-stay-in-vietnam-base-decisions",
]

for s in slugs:
    p = os.path.join(CONTENT_FIX_DIR, f"{s}.html")
    with open(p, "r", encoding="utf-8") as f:
        html = f.read()
    txt = clean_text(html)
    res = analyze_text(txt, source_name=s)
    print(f"\n=== {s} (Passed={res['passed']}) ===")
    for k in res:
        if k.endswith('_violations') and res[k]:
            print(f"  {k}: {res[k]}")

# -*- coding: utf-8 -*-
import json, sys
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

with open('ops/content_audit_results.json', 'r', encoding='utf-8') as f:
    results = json.load(f)

failed = [r for r in results if not r['passed']]
print(f"Remaining {len(failed)} items:")
for r in failed:
    print(f"  {r['slug']:<45} | W={r['word_count']:>4} | HLS={r['hls_score']:>3} | EDI={r['edi']:>4.1f} | CV={r['cv']:.2f}")

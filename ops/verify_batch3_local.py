# -*- coding: utf-8 -*-
import os, sys, json

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_ai_slop_linter import analyze_text
from fast_content_audit import clean_text

with open('ops/content_audit_results.json', 'r', encoding='utf-8') as f:
    results = json.load(f)

failed_slugs = [r['slug'] for r in results if not r['passed']]
print(f"Checking all {len(failed_slugs)} target files in ops/content_fix/...")

passed = 0
failed = 0
for s in failed_slugs:
    p = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'content_fix', f'{s}.html')
    if not os.path.exists(p):
        print(f"  Missing file: {s}")
        failed += 1
        continue
    with open(p, 'r', encoding='utf-8') as f:
        html = f.read()
    txt = clean_text(html)
    res = analyze_text(txt, source_name=s)
    if res['passed']:
        passed += 1
    else:
        print(f"  Still failing: {s} | HLS={res['hls_score']} EDI={res['edi']:.1f}")
        for k in res:
            if k.endswith('_violations') and res[k]:
                print(f"    {k}: {len(res[k])}")
        failed += 1

print(f"\nPre-deploy verification: {passed}/{len(failed_slugs)} PASSED! (Failed: {failed})")

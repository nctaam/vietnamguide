# -*- coding: utf-8 -*-
import sys, json

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

with open('ops/content_audit_results.json', 'r', encoding='utf-8') as f:
    results = json.load(f)

failed = [r for r in results if not r['passed']]
print(f'Total failed: {len(failed)}')

reasons = {'hls_not_100': 0, 'thin_under_1000': 0, 'low_edi': 0, 'low_cv': 0, 'violations_exist': 0}
for r in failed:
    if r['hls_score'] < 100:
        reasons['hls_not_100'] += 1
    if r['word_count'] < 1000:
        reasons['thin_under_1000'] += 1
    if r['edi'] < 5.0:
        reasons['low_edi'] += 1
    if r['cv'] < 0.45:
        reasons['low_cv'] += 1
    if r['total_violations'] > 0:
        reasons['violations_exist'] += 1

print('Failure breakdown:')
for k, v in reasons.items():
    print(f'  {k}: {v}')

print('\nDetailed list of failed:')
for r in failed:
    issues = []
    if r['hls_score'] < 100:
        issues.append(f"HLS={r['hls_score']}")
    if r['word_count'] < 1000:
        issues.append(f"thin({r['word_count']}w)")
    if r['edi'] < 5.0:
        issues.append(f"low_edi({r['edi']:.1f})")
    if r['cv'] < 0.45:
        issues.append(f"low_cv({r['cv']:.2f})")
    if r['total_violations'] > 0:
        issues.append(f"viols({r['total_violations']})")
    print(f"  ID={r['id']:>5} | {r['slug']:<45} | {', '.join(issues)}")

# -*- coding: utf-8 -*-
import os, sys, glob

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_ai_slop_linter import analyze_text
from fast_content_audit import clean_text

files = glob.glob(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'thin_content', '*.html'))
print(f"Checking all {len(files)} files in ops/thin_content/...")

under_1000 = 0
failed_linter = 0
word_counts = []

for f in files:
    slug = os.path.basename(f).replace('.html', '')
    txt = clean_text(open(f, 'r', encoding='utf-8').read())
    r = analyze_text(txt, slug)
    wc = r.get('word_count', 0)
    word_counts.append(wc)
    
    if wc < 1000:
        print(f"  STILL THIN: {slug} ({wc} words)")
        under_1000 += 1
    if not r.get('passed', False):
        print(f"  FAILED LINTER: {slug} (HLS={r.get('hls_score')})")
        failed_linter += 1

print("\n" + "=" * 60)
print(f"Total Expanded Files:     {len(files)}")
print(f"Files < 1,000 words:      {under_1000} (Target: 0)")
print(f"Failed Linter:            {failed_linter} (Target: 0)")
print(f"Minimum Word Count:       {min(word_counts)}")
print(f"Average Word Count:       {sum(word_counts)/len(word_counts):.1f}")
print(f"Maximum Word Count:       {max(word_counts)}")
print("=" * 60)

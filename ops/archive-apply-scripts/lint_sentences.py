import sys; sys.stdout.reconfigure(encoding='utf-8',errors='replace'); sys.path.insert(0,'ops'); import re; from anti_ai_slop_linter import analyze_text
content=open('ops/content_fix/mekong-delta-travel-guide.html', encoding='utf-8').read()
text=re.sub(r'<[^>]+>',' ',content)
text=re.sub(r'\s+',' ',text).strip()
r=analyze_text(text, 'mekong-delta-travel-guide')
for v in r.get('local_cadence_violations', []):
    print(v['snippet'])
    idx = v['sentence_start_idx']
    for i in range(idx, idx+6):
        print(f'{i}: {r["sentences"][i]}')

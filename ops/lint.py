import sys; sys.stdout.reconfigure(encoding='utf-8',errors='replace'); sys.path.insert(0,'ops'); import re; from anti_ai_slop_linter import analyze_text
def lint(slug):
    html=open(f'ops/content_fix/{slug}.html','r',encoding='utf-8').read()
    text=re.sub(r'<[^>]+>',' ',html)
    text=re.sub(r'\s+',' ',text).strip()
    r=analyze_text(text,slug)
    print(f'\n--- {slug} ---')
    print(f'HLS={r["hls_score"]} Passed={r["passed"]}')
    for k in r:
        if k.endswith('_violations') and r[k]:
            print(f'  {k}: {len(r[k])}')
            for v in r[k][:3]:
                print(f'    - {v}')

for s in ['da-nang-beaches-guide', 'mekong-delta-travel-guide', 'vietnam-in-january', 'best-beaches-in-vietnam', 'vietnam-in-february', 'vietnam-in-december']:
    lint(s)

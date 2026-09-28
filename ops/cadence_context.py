import sys; sys.stdout.reconfigure(encoding='utf-8',errors='replace'); sys.path.insert(0,'ops'); import re; from anti_ai_slop_linter import analyze_text
def get_cadence(slug, snip):
    html=open(f'ops/content_fix/{slug}.html','r',encoding='utf-8').read()
    text=re.sub(r'<[^>]+>',' ',html)
    text=re.sub(r'\s+',' ',text).strip()
    r=analyze_text(text,slug)
    print(f'\n--- {slug} ---')
    for v in r.get('local_cadence_violations', []):
        if snip in v['snippet']:
            idx = v['sentence_start_idx']
            print(f"Lengths: {v['lengths']}")
            sentences = [s.strip() for s in re.split(r'(?<=[.!?])\s+', text)]
            for i in range(max(0, idx-1), min(len(sentences), idx+7)):
                print(f'{i}: {sentences[i]}')

get_cadence('vietnam-in-january', 'Central Vietnam')
get_cadence('vietnam-in-february', 'Central Coast Warm-Up')
get_cadence('vietnam-in-december', 'Central Monsoon Transition')
get_cadence('best-beaches-in-vietnam', 'The trip is already north')

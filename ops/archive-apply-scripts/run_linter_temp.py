import sys
sys.stdout.reconfigure(encoding='utf-8',errors='replace')
sys.path.insert(0,'ops')
import re
from anti_ai_slop_linter import analyze_text
import json

html=open('ops/content_fix/sapa-vs-ha-giang.html','r',encoding='utf-8').read()
text=re.sub(r'<[^>]+>',' ',html)
text=re.sub(r'\s+',' ',text).strip()
r=analyze_text(text,'sapa-vs-ha-giang')
print(json.dumps(r, indent=2))

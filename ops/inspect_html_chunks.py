# -*- coding: utf-8 -*-
"""
Inspect exact text of parent posts to design surgical, seamless link insertions.
"""
import json
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

with open('ops/parent_posts_cache.json', 'r', encoding='utf-8') as f:
    posts = json.load(f)

targets = [
    ('da-nang-travel-guide', ['beach', 'coastal', 'my khe', 'fast answer']),
    ('hue-imperial-city-guide', ['matrix', 'pass-through', 'verdict', 'stay']),
    ('hanoi-travel-guide', ['checklist', 'base', 'first', 'settle']),
    ('10-days-in-vietnam', ['itinerary at a glance', 'first-time', 'strongest']),
    ('mekong-delta-travel-guide', ['overnight beats', 'at a glance', 'hcmc day trip']),
    ('best-time-to-visit-vietnam', ['quick answer', 'easiest planning', 'december']),
    ('tet-in-vietnam-travel-guide', ['should you travel', 'holiday deliberately']),
    ('vietnam-travel-guide', ['by chapter', 'planning flow', 'plan in this order']),
    ('ninh-binh-travel-guide', ['fastest useful answer', 'field note', 'deserves one night']),
    ('ha-long-bay-travel-guide', ['fastest useful answer', 'unesco', 'worth one night']),
    ('north-central-south-vietnam', ['fast verdict matrix', 'strongest default', 'easier to plan']),
    ('ha-giang-loop-planning-guide', ['concierge verdict', 'easy rider', 'modify the plan']),
]

for slug, keywords in targets:
    p = posts[slug]
    print(f"\n=======================================================")
    print(f"PAGE: {slug} (ID: {p['ID']})")
    content = p['content']
    paras = re.split(r'(</p>|</div>|</h2>)', content)
    for part in paras:
        clean = re.sub(r'<[^>]+>', ' ', part).strip()
        if len(clean) > 40 and any(k.lower() in clean.lower() for k in keywords):
            print(f"--- MATCH ---")
            print(part.strip()[:300])

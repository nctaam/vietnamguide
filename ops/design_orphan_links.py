# -*- coding: utf-8 -*-
"""
Inspect relevant sections in parent posts to find optimal insertion points.
"""
import json
import re

with open('ops/parent_posts_cache.json', 'r', encoding='utf-8') as f:
    posts = json.load(f)

def show_sections(slug, keywords):
    p = posts.get(slug)
    if not p:
        print(f"Not found: {slug}")
        return
    content = p['content']
    # split into paragraphs
    paras = [p.strip() for p in re.findall(r'<p[^>]*>.*?</p>', content, re.DOTALL)]
    print(f"\n==========================================")
    print(f"[{slug}] Total paragraphs: {len(paras)}")
    for i, p in enumerate(paras):
        text = re.sub(r'<[^>]+>', '', p)
        if any(k.lower() in text.lower() for k in keywords):
            print(f"  Para {i}: {text[:140]}...")

show_sections('da-nang-travel-guide', ['beach', 'my khe', 'coast'])
show_sections('hue-imperial-city-guide', ['phong nha', 'cave', 'north', 'dmz', 'transfer'])
show_sections('hanoi-travel-guide', ['saigon', 'ho chi minh', 'mistake', 'first', 'route'])
show_sections('10-days-in-vietnam', ['route', 'first time', 'itinerary', 'variant'])
show_sections('mekong-delta-travel-guide', ['overnight', 'day trip', 'can tho', 'ben tre'])
show_sections('best-time-to-visit-vietnam', ['december', 'february', 'rain', 'monsoon'])
show_sections('vietnam-travel-guide', ['checklist', 'first trip', 'planning', 'start'])
show_sections('safety-scams-vietnam', ['hanoi', 'taxi', 'old quarter'])
show_sections('ninh-binh-travel-guide', ['rush', 'day trip', 'overnight', 'tam coc', 'stay'])
show_sections('ha-long-bay-travel-guide', ['cruise', 'booking', 'questions', 'overnight'])
show_sections('north-central-south-vietnam', ['cities', 'hanoi', 'first-time', 'da nang'])
show_sections('ha-giang-loop-planning-guide', ['easy rider', 'self-drive', 'motorcycle', 'driver', 'license'])

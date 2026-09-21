# -*- coding: utf-8 -*-
import json

meta = json.load(open('ops/meta_inventory.json', encoding='utf-8'))
slug_map = {p['post_name']: p['ID'] for p in meta}

targets = [
    'ho-chi-minh-city-travel-guide',
    'where-to-stay-in-ho-chi-minh-city',
    'vietnam-airport-arrival-checklist',
    'saigon-street-food-guide',
    'da-nang-travel-guide',
    'hoi-an-ancient-town-guide',
    'da-nang-vs-hoi-an',
    'transport-within-vietnam',
    'sim-esim-vietnam',
    'money-cash-cards-atms',
    'safety-scams-vietnam',
    'vietnam-travel-cost',
    'vietnam-food-safety-street-food-etiquette',
    'vietnam-first-trip-planning-checklist',
    'hanoi-street-food-guide',
    'da-lat-travel-guide',
    'hanoi-travel-guide',
    'best-time-to-visit-vietnam',
    'vietnam-in-february',
    'vietnam-in-december',
    'vietnam-rainy-season-flexible-route',
    '10-days-in-vietnam',
    '14-days-in-vietnam',
    'best-places-to-visit-vietnam'
]

for t in targets:
    print(f"{slug_map.get(t, 'NOT FOUND')}: {t}")

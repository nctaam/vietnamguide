# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 68: Update meta_inventory.json with 7 new pillars (157 -> 164)
"""
import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

base_dir = os.path.dirname(os.path.abspath(__file__))
inv_path = os.path.join(base_dir, 'meta_inventory.json')

with open(inv_path, 'r', encoding='utf-8') as f:
    inv = json.load(f)

print(f"Current inventory: {len(inv)} pages")
existing_slugs = {entry['post_name'] for entry in inv}

new_entries = [
    {
        'ID': '854',
        'post_name': 'vietnam-in-october',
        'post_title': 'Vietnam in October: Shoulder Season & Central Rains',
        'rm_title': 'Vietnam in October: Shoulder Season & Central Rains',
        'rm_desc': 'Planning a trip to Vietnam in October? Navigate the shoulder season transition, central coast rain risks, and northern trekking routes.',
        'rm_keyword': 'Vietnam in October'
    },
    {
        'ID': '855',
        'post_name': 'best-things-to-do-in-ho-chi-minh-city',
        'post_title': 'Best Things to Do in Ho Chi Minh City (2026 Guide)',
        'rm_title': 'Best Things to Do in Ho Chi Minh City (2026 Guide)',
        'rm_desc': 'Discover the best things to do in Ho Chi Minh City: war history museums, rooftop cafes, street food alleys, and day trips with 2026 costs in VND.',
        'rm_keyword': 'best things to do in Ho Chi Minh City'
    },
    {
        'ID': '856',
        'post_name': 'ho-chi-minh-city-to-da-lat-transport',
        'post_title': 'Ho Chi Minh City to Da Lat: Bus, Flight & Car',
        'rm_title': 'Ho Chi Minh City to Da Lat: Bus, Flight & Car',
        'rm_desc': 'Compare Ho Chi Minh City to Da Lat transport options: sleeper buses, flights, and private cars with journey times, ticket costs in VND, and schedules.',
        'rm_keyword': 'Ho Chi Minh City to Da Lat'
    },
    {
        'ID': '857',
        'post_name': 'where-to-stay-in-phu-quoc',
        'post_title': 'Where to Stay in Phu Quoc: Best Beaches & Areas',
        'rm_title': 'Where to Stay in Phu Quoc: Best Beaches & Areas',
        'rm_desc': 'Decide where to stay in Phu Quoc with our breakdown of Long Beach, Ong Lang, Bai Sao, and Duong Dong town with hotel prices in VND.',
        'rm_keyword': 'where to stay in Phu Quoc'
    },
    {
        'ID': '858',
        'post_name': 'best-things-to-do-in-da-nang',
        'post_title': 'Best Things to Do in Da Nang: Beaches & Mountains',
        'rm_title': 'Best Things to Do in Da Nang: Beaches & Mountains',
        'rm_desc': 'Explore the best things to do in Da Nang: My Khe Beach, Marble Mountains, Dragon Bridge, Ba Na Hills, and local street food with 2026 prices.',
        'rm_keyword': 'best things to do in Da Nang'
    },
    {
        'ID': '859',
        'post_name': 'ho-chi-minh-city-to-phu-quoc-transport',
        'post_title': 'Ho Chi Minh City to Phu Quoc: Flights & Ferry',
        'rm_title': 'Ho Chi Minh City to Phu Quoc: Flights & Ferry',
        'rm_desc': 'Compare Ho Chi Minh City to Phu Quoc transport options: direct flights, bus-and-ferry routes, departure ports, ticket costs in VND, and travel times.',
        'rm_keyword': 'Ho Chi Minh City to Phu Quoc'
    },
    {
        'ID': '860',
        'post_name': 'where-to-stay-in-nha-trang',
        'post_title': 'Where to Stay in Nha Trang: Best Areas & Hotels',
        'rm_title': 'Where to Stay in Nha Trang: Best Areas & Hotels',
        'rm_desc': 'Find where to stay in Nha Trang with our district guide covering Tran Phu beachfront, city center, and Bai Dai resorts with hotel prices in VND.',
        'rm_keyword': 'where to stay in Nha Trang'
    },
]

added = 0
for entry in new_entries:
    if entry['post_name'] not in existing_slugs:
        inv.append(entry)
        added += 1
        print(f"  Added: {entry['post_name']} (ID {entry['ID']})")
    else:
        print(f"  Exists: {entry['post_name']}")

with open(inv_path, 'w', encoding='utf-8') as f:
    json.dump(inv, f, ensure_ascii=False, indent=2)

print(f"Updated inventory: {len(inv)} pages (+{added} added)")

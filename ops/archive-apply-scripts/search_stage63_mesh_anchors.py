# -*- coding: utf-8 -*-
"""
Helper to inspect relevant paragraphs in parent posts for Stage 63 internal link mesh
"""

import json
import re

sources = json.load(open('ops/stage63_mesh_raw_sources.json', encoding='utf-8'))

def inspect(slug, keywords):
    if slug not in sources:
        print(f"[MISSING] {slug}")
        return
    content = sources[slug]['content']
    print(f"\n==================== {slug} ====================")
    for kw in keywords:
        matches = [m.start() for m in re.finditer(re.escape(kw), content, re.IGNORECASE)]
        print(f"Keyword '{kw}': {len(matches)} occurrences")
        for idx in matches[:3]:
            start = max(0, idx - 150)
            end = min(len(content), idx + 250)
            snippet = content[start:end].replace('\n', ' ')
            print(f"  ... {snippet} ...")

def main():
    # Group 1 & 2: Accommodation
    inspect('da-nang-travel-guide', ['stay', 'hotel', 'base', 'where to stay', 'My Khe'])
    inspect('da-nang-beaches-guide', ['stay', 'hotel', 'resort', 'My Khe'])
    inspect('da-nang-vs-hoi-an', ['stay', 'base', 'hotel', 'split'])
    inspect('where-to-stay-in-vietnam-base-decisions', ['Da Nang', 'Hoi An', 'Central Vietnam'])
    inspect('hoi-an-ancient-town-guide', ['stay', 'hotel', 'An Bang', 'where to stay'])
    inspect('best-things-to-do-in-hoi-an', ['stay', 'hotel', 'An Bang', 'Old Town'])

    # Group 3: Ha Long Day vs Overnight
    inspect('ha-long-bay-travel-guide', ['day trip', 'overnight', 'cruise', 'sleep'])
    inspect('ha-long-bay-vs-lan-ha-bay', ['day trip', 'overnight', '2D1N', 'cruise'])
    inspect('ha-long-bay-cruise-questions-before-booking', ['day trip', 'overnight', 'duration', 'nights'])
    inspect('best-day-trips-from-hanoi', ['Ha Long', 'Halong', 'cruise', 'bay'])

    # Group 4: Hanoi to Ha Long Transport
    inspect('ha-long-bay-travel-guide', ['transfer', 'limousine', 'bus', 'Expressway', 'Hanoi to'])
    inspect('transport-within-vietnam', ['Ha Long', 'Halong', 'limousine', '5B'])
    inspect('hanoi-travel-guide', ['Ha Long', 'Halong', 'day trip', 'transfer'])
    inspect('ninh-binh-to-ha-long-bay-transfer', ['Hanoi', 'limousine', 'expressway'])

    # Group 5: Da Nang to Hue Train vs Car
    inspect('transport-within-vietnam', ['Hai Van', 'Hue', 'scenic train'])
    inspect('vietnam-train-travel', ['Hai Van', 'Da Nang to Hue', 'scenic', 'heritage'])
    inspect('da-nang-travel-guide', ['Hai Van', 'Hue', 'day trip', 'train'])
    inspect('hue-imperial-city-guide', ['Da Nang', 'Hai Van', 'train', 'transfer'])

    # Group 6: Vietnam in April
    inspect('best-time-to-visit-vietnam', ['April', 'spring', 'holiday', 'April 30'])
    inspect('vietnam-in-march', ['April', 'next month', 'season'])
    inspect('10-days-in-vietnam', ['April', 'weather', 'season', 'Central'])
    inspect('best-places-to-visit-vietnam', ['April', 'season', 'weather'])

    # Group 7: Tap Water in Vietnam
    inspect('health-travel-insurance-vietnam', ['water', 'tap', 'drink', 'ice'])
    inspect('vietnam-food-safety-street-food-etiquette', ['water', 'tap', 'ice', 'drink'])
    inspect('what-to-pack-for-vietnam-region-season', ['water', 'bottle', 'purifier', 'filter'])
    inspect('vietnam-first-trip-planning-checklist', ['water', 'health', 'ice', 'drink'])

if __name__ == '__main__':
    main()

# -*- coding: utf-8 -*-
"""
Inspect exact text in stage62_source_contents.json to build verified replacements.
"""

import json
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

with open('ops/stage62_source_contents.json', 'r', encoding='utf-8') as f:
    sources = json.load(f)

def find_mentions(pid, keywords):
    p = sources.get(str(pid))
    if not p:
        print(f"Post {pid} NOT FOUND")
        return []
    content = p['content']
    print(f"\n=======================================================")
    print(f"Post {pid}: {p['slug']} ({len(content)} chars)")
    print(f"=======================================================")
    
    # Split by double newline or paragraph tags
    blocks = re.split(r'(</p>|</td>|</li>|</tr>)', content)
    results = []
    current = ""
    for b in blocks:
        current += b
        if any(tag in b for tag in ['</p>', '</td>', '</li>', '</tr>']):
            for kw in keywords:
                if kw.lower() in current.lower():
                    clean = current.strip().replace('\n', ' ')
                    # Print snippet
                    print(f"  [{kw}] -> {clean[:150]}...")
                    results.append(clean)
                    break
            current = ""
    return results

# Let's run focused searches
if __name__ == '__main__':
    # 1. Saigon Airport
    print("--- 1. Saigon Airport ---")
    find_mentions(281, ["airport", "tan son nhat", "tan binh"])
    find_mentions(476, ["tan son nhat", "sgn", "saigon", "ho chi minh"])
    find_mentions(615, ["related", "planning", "checklist", "guide", "tan son nhat"])

    # 2. Da Nang Airport to Hoi An
    print("\n--- 2. Da Nang Airport to Hoi An ---")
    find_mentions(213, ["hoi an", "airport", "transfer", "30 km"])
    find_mentions(499, ["da nang", "airport", "transfer", "arrival", "private car"])
    find_mentions(209, ["airport", "transfer", "between", "taxi"])
    find_mentions(155, ["da nang", "hoi an", "airport transfer", "private transfer"])

    # 3. Grab in Vietnam
    print("\n--- 3. Grab in Vietnam ---")
    find_mentions(155, ["grab", "ride-hailing", "app", "taxi"])
    find_mentions(15, ["grab", "app", "data", "ride"])
    find_mentions(158, ["grab", "card", "app", "cashless"])
    find_mentions(181, ["grab", "taxi", "meter", "scam"])

    # 4. Tipping in Vietnam
    print("\n--- 4. Tipping in Vietnam ---")
    find_mentions(22, ["tip", "tipping", "service charge", "etiquette"])
    find_mentions(158, ["tip", "tipping", "change", "rounding"])
    find_mentions(480, ["tip", "tipping", "bill", "service", "etiquette"])
    find_mentions(473, ["tip", "tipping", "cash", "budget", "daily"])

    # 5. Vietnam Coffee Guide
    print("\n--- 5. Vietnam Coffee Guide ---")
    find_mentions(614, ["coffee", "ca phe", "egg", "giang"])
    find_mentions(615, ["coffee", "ca phe", "sua da", "morning"])
    find_mentions(611, ["coffee", "ca phe", "cau dat", "arabica"])
    find_mentions(287, ["coffee", "ca phe", "egg", "giang"])

    # 6. Vietnam in March
    print("\n--- 6. Vietnam in March ---")
    find_mentions(14, ["march", "spring", "february to"])
    find_mentions(495, ["march", "transition", "following month"])
    find_mentions(19, ["march", "spring", "season", "weather"])
    find_mentions(110, ["march", "spring", "season", "weather"])

    # 7. Vietnam in November
    print("\n--- 7. Vietnam in November ---")
    find_mentions(14, ["november", "autumn", "fall", "october to"])
    find_mentions(493, ["november", "previous month", "transition"])
    find_mentions(482, ["november", "central", "typhoon", "rain"])
    find_mentions(20, ["november", "autumn", "fall", "season"])

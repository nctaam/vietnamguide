# -*- coding: utf-8 -*-
"""
Search exact candidate paragraphs in the 24 source posts
"""

import json
import re

sources = json.load(open('ops/stage62_source_contents.json', encoding='utf-8'))

def search_in_post(pid, keywords):
    p = sources.get(str(pid))
    if not p:
        print(f"Post {pid} not found in sources.")
        return
    print(f"\n==================== Post {pid} ({p['slug']}) ====================")
    lines = p['content'].split('\n')
    for i, line in enumerate(lines):
        if any(k.lower() in line.lower() for k in keywords):
            print(f"L{i}: {line[:140]}")

# 1. Saigon airport
search_in_post(262, ["airport", "tan son nhat", "bus 109"])
search_in_post(281, ["airport", "tan son nhat", "arrival"])
search_in_post(476, ["tan son nhat", "saigon", "sgn"])
search_in_post(615, ["vietnam travel guide", "planning", "saigon"])

# 2. Da Nang airport to Hoi An
search_in_post(213, ["hoi an", "airport", "transfer"])
search_in_post(499, ["da nang airport", "airport", "transfer", "arrival"])
search_in_post(209, ["airport", "transfer", "travel between", "getting between"])
search_in_post(155, ["da nang", "hoi an", "airport transfer", "taxi"])

# 3. Grab
search_in_post(155, ["grab", "ride-hailing", "taxi"])
search_in_post(15, ["grab", "app", "transport", "ride"])
search_in_post(158, ["grab", "cashless", "ride-hailing", "card"])
search_in_post(181, ["grab", "taxi scam", "meter"])

# 4. Tipping
search_in_post(22, ["tipping", "tip", "service charge"])
search_in_post(158, ["tipping", "tip", "change", "coins"])
search_in_post(480, ["tipping", "tip", "bill", "service"])
search_in_post(473, ["tipping", "tip", "budget", "cash"])

# 5. Coffee
search_in_post(614, ["coffee", "giang", "egg"])
search_in_post(615, ["coffee", "sua da", "morning"])
search_in_post(611, ["coffee", "cau dat", "arabica"])
search_in_post(287, ["coffee", "cafe", "egg"])

# 6. March
search_in_post(14, ["march", "february to april"])
search_in_post(495, ["march", "spring", "next month"])
search_in_post(19, ["march", "best time", "season"])
search_in_post(110, ["march", "best time", "spring"])

# 7. November
search_in_post(14, ["november", "october to december"])
search_in_post(493, ["november", "autumn", "fall"])
search_in_post(482, ["november", "central", "typhoon"])
search_in_post(20, ["november", "autumn", "fall"])

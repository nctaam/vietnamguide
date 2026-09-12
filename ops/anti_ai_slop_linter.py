# -*- coding: utf-8 -*-
"""
VietnamGuide Anti-AI Slop Linter & Quality Engine.
Analyzes English travel content for AI clichés, sentence rhythm (HLS / CV),
and ground-truth evidence density.
"""

import os
import sys
import re
import math
import json
import urllib.request
import xml.etree.ElementTree as ET

# ==============================================================================
# BANNED CLICHE PATTERNS
# ==============================================================================

TIER1_PATTERNS = [
    (r"\bnestled\s+(?:in|within|amongst|against|amidst|between|in\s+the\s+heart\s+of)\b", "nestled in / nestled in the heart of / amidst"),
    (r"\bwhether\s+you(?:'re|\s+are)\s+a\s+[a-z\s]+(?:or|buff|seeker)\b", "whether you're a [x] or [y]"),
    (r"\bwhether\s+you\s+seek\b|\bwhether\s+you(?:'re|\s+are)\s+seeking\b|\bwhether\s+you(?:'re|\s+are)\s+looking\s+for\b", "whether you seek / looking for"),
    (r"\blook\s+no\s+further\s+than\b", "look no further than"),
    (r"\bwithout\s+further\s+ado\b", "without further ado"),
    (r"\ba\s+testament\s+to\b|\bserves?\s+as\s+a\s+testament\s+to\b|\bstands?\s+as\s+a\s+testament\s+to\b", "a testament to / serves as a testament to"),
    (r"\b(?:a\s+)?rich\s+tapestry\b|\btapestry\s+of\b", "rich tapestry / tapestry of"),
    (r"\bbustling\s+metropolis\b", "bustling metropolis"),
    (r"\bsteeped\s+in\s+history\b", "steeped in history"),
    (r"\ba\s+land\s+of\s+contrasts\b", "a land of contrasts"),
    (r"\bhidden\s+gem[s]?\b", "hidden gem"),
    (r"\bmust[- ]visit\b|\bmust[- ]see\b", "must-visit / must-see"),
    (r"\bbreathtaking\s+(?:views?|scenery|landscapes?|beauty)?\b", "breathtaking"),
    (r"\bmesmerizing\s+(?:beauty|views?|waters?|culture)?\b", "mesmerizing"),
    (r"\bpicturesque\s+(?:town|village|scenery|landscape)?\b", "picturesque"),
    (r"\bpostcard[- ]perfect\b", "postcard-perfect"),
    (r"\bunforgettable\s+(?:journey|experience|trip|memory|adventure)\b", "unforgettable journey/experience"),
    (r"\b(?:an\s+)?unforgettable\s+adventure\s+awaits\b|\badventure\s+awaits\b", "adventure awaits"),
    (r"\boff\s+the\s+beaten\s+(?:path|track)\b", "off the beaten path"),
    (r"\bmelting\s+pot\b", "melting pot"),
    (r"\bkaleidoscope\s+of\b", "kaleidoscope of"),
    (r"\boasis\s+of\s+(?:tranquility|peace|calm)\b|\bhaven\s+of\s+(?:peace|tranquility)\b", "oasis/haven of tranquility/peace"),
    (r"\bparadise\s+for\s+(?:nature\s+)?(?:lovers|foodies|travelers|backpackers|adventurers)\b", "paradise for lovers of"),
    (r"\b(?:the\s+)?crown\s+jewel\b|\bjewel\s+in\s+the\s+crown\b", "crown jewel / jewel in the crown"),
    (r"\b(?:a\s+)?stone'?s\s+throw\s+(?:away\s+)?(?:from)?\b", "a stone's throw away"),
    (r"\bunravel\s+the\s+secrets\b|\bunlock\s+the\s+secrets\b|\bdiscover\s+the\s+secrets\b", "unravel/unlock the secrets"),
    (r"\bembodies\s+the\s+spirit\s+of\b|\bcaptures?\s+the\s+essence\s+of\b", "embodies the spirit / essence of"),
    (r"\bscenic\s+wonder[s]?\b|\bwonders?\s+of\s+nature\b", "scenic wonder / wonder of nature"),
    (r"\blet'?s\s+(?:delve|dive)\s+into\b", "let's delve/dive into"),
    (r"\bstep\s+back\s+in\s+time\b", "step back in time"),
    (r"\bin\s+a\s+nutshell\b", "in a nutshell"),
    (r"\bat\s+the\s+end\s+of\s+the\s+day\b", "at the end of the day"),
    (r"\bquintessential\s+(?:experience|vietnamese|charm|destination)\b", "quintessential experience"),
    (r"\bbucket[- ]list\s+(?:destination|trip|experience)?\b", "bucket-list destination"),
    (r"\bonce[- ]in[- ]a[- ]lifetime\s+(?:experience|trip|opportunity|adventure)\b", "once-in-a-lifetime"),
    (r"\bsymphony\s+of\s+flavors\b", "symphony of flavors"),
    (r"\btantalize\s+your\s+taste\s*buds\b", "tantalize your taste buds"),
    (r"\bfeast\s+for\s+the\s+(?:eyes|senses)\b", "feast for the eyes/senses"),
    (r"\bsensory\s+overload\b", "sensory overload"),
    (r"\bculinary\s+adventure\b", "culinary adventure"),
    (r"\bin\s+conclusion\b", "in conclusion"),
    (r"\ball\s+in\s+all\b", "all in all"),
    (r"\bto\s+wrap\s+things\s+up\b", "to wrap things up"),
    (r"\bso\s+pack\s+your\s+bags\b", "so pack your bags"),
    (r"\bhas\s+something\s+for\s+everyone\b|\bsomething\s+(?:to\s+offer\s+)?for\s+every(?:one|\s+kind\s+of\s+traveler)\b", "has something for everyone"),
    (r"\b(?:safe|happy)\s+travels!?\b", "happy travels / safe travels"),
    (r"\bdelve\s+(?:deep|into)\b", "delve into"),
    (r"\bcaptivating\s+blend\b", "captivating blend"),
]

TIER2_PATTERNS = [
    (r"\bprices\s+vary\s+widely\b|\bcosts?\s+vary\s+depending\s+on\b", "prices/costs vary widely"),
    (r"\btake\s+a\s+taxi\s+or\s+(?:public\s+)?bus\b", "take a taxi or bus (vague transit)"),
    (r"\bit\s+is\s+recommended\s+to\b", "it is recommended to (passive voice)"),
    (r"\b(?:it\s+is|it's)\s+worth\s+(?:noting|mentioning)\s+that\b", "it is worth noting/mentioning that"),
    (r"\b(?:it\s+is|it's)\s+important\s+to\s+remember\s+that\b", "it is important to remember that"),
    (r"\bkeep\s+in\s+mind\s+that\b", "keep in mind that"),
    (r"\bplays?\s+(?:a|an)\s+(?:crucial|vital|important|key)\s+role\b", "plays a crucial/vital role"),
    (r"\bneedless\s+to\s+say\b", "needless to say"),
    (r"\bhire\s+a\s+reputable\s+guide\b", "hire a reputable guide (vague advice)"),
    (r"\bpack\s+comfortable\s+walking\s+shoes\b", "pack comfortable walking shoes (generic advice)"),
    (r"\bbe\s+mindful\s+of\s+your\s+belongings\b", "be mindful of your belongings (vague security)"),
    (r"\bremember\s+to\s+stay\s+hydrated\b", "remember to stay hydrated"),
    (r"\ba\s+plethora\s+of\b", "a plethora of"),
    (r"\bvibrant\s+(?:culture|city|atmosphere|nightlife)\b", "vibrant [noun]"),
    (r"\bcheck\s+online\s+for\s+(?:schedules?|tickets?|prices?)\b", "check online for schedules (vague instruction)"),
]

TIER3_PATTERNS = [
    (r"\bfirst\s+and\s+foremost\b", "first and foremost (structural signposting)"),
    (r"\bwithout\s+further\s+ado\b", "without further ado (structural signposting)"),
    (r"\bwhether\s+you\s+(?:are|'re)\s+a\b[^.!?]{1,60}\bor\b[^.!?]{1,60}", "whether you are a [x] or [y] (structural signposting)"),
    (r"\bhas\s+something\s+for\s+everyone\b", "has something for everyone (formulaic cliché)"),
    (r"\ball\s+in\s+all\b", "all in all (structural signposting)"),
    (r"\bin\s+conclusion\b", "in conclusion (structural signposting)"),
    (r"\bto\s+wrap\s+things\s+up\b", "to wrap things up (structural signposting)"),
    (r"\blook\s+no\s+further\s+than\b", "look no further than (formulaic transition)"),
    (r"\ba\s+myriad\s+of\b", "a myriad of (structural signposting)"),
    (r"\bpicture\s+this\b", "picture this (formulaic hook)"),
    (r"\blet(?:'s|\s+us)\s+dive\s+in\b", "let's dive in (formulaic transition)"),
    (r"\bit\s+is\s+important\s+to\s+remember\s+that\b", "it is important to remember that (structural padding)"),
]

PASSIVE_AI_PADDING_PATTERNS = [
    (r"\bvisitors?\s+(?:are|is)\s+treated\s+to\b", "visitors are treated to (passive observer)"),
    (r"\bit\s+is\s+recommended\s+that\s+(?:one|visitors?|travelers?)\b", "it is recommended that [one/visitor] (passive instruction)"),
    (r"\btravelers?\s+will\s+find\s+that\b", "travelers will find that (passive observer)"),
    (r"\bone\s+can\s+(?:easily\s+)?(?:explore|experience|see|visit|enjoy)\b", "one can [easily] explore/experience (passive observer)"),
    (r"\bit\s+should\s+be\s+noted\s+that\b", "it should be noted that (bureaucratic filler)"),
    (r"\bit\s+can\s+be\s+seen\s+that\b", "it can be seen that (bureaucratic filler)"),
]

TIER4_PATTERNS = [
    (r"\brest\s+assured\s+(?:that)?\b", "rest assured (conversational padding)"),
    (r"\bhas\s+you\s+covered\b", "has you covered (marketing trope)"),
    (r"\bto\s+say\s+that\b[^.!?]{1,60}\bis\s+an\s+understatement\b", "to say that [...] is an understatement"),
    (r"\bwithout\s+a\s+doubt\b", "without a doubt (conversational filler)"),
    (r"\bit\s+goes\s+without\s+saying\s+(?:that)?\b", "it goes without saying that"),
    (r"\bleaves?\s+an\s+indelible\s+mark\b", "leaves an indelible mark (sentimental trope)"),
    (r"\bembark\s+on\s+(?:a|an|your)\s+journey\b", "embark on a journey (formulaic phrasing)"),
]

# ==============================================================================
# EVIDENCE PATTERNS
# ==============================================================================

CURRENCY_REGEX = re.compile(
    r"(?:\b(?:\d{1,3}(?:[.,]\d{3})*|\d+)\s*(?:VND|vnd|₫|đ)\b|\$\s*\d+(?:\.\d{2})?(?:\s*USD)?\b|\b(?:withdrawal\s+limit|local\s+fee|markup|surcharge|toll\s+fee)\b)",
    re.IGNORECASE
)

TRANSIT_TIME_REGEX = re.compile(
    r"(?:\b\d+(?:\.\d+)?\s*(?:hours?|hrs?|mins?|minutes?|km)\b|\b(?:grab(?:car|bike)?|mai\s+linh|vinasun|bus\s+\d+|expressway|limousine|ga\s+[ab]|terminal\s+\d+|pillar\s+\d+|soft\s+sleeper|hard\s+sleeper|4[- ]berth|6[- ]berth|se\d+|tn\d+)\b)",
    re.IGNORECASE
)

REGULATORY_REGEX = re.compile(
    r"(?:\b(?:resolution\s+\d+|decree\s+\d+|e[- ]?visa|45[- ]day\s+exemption|90[- ]day\s+e[- ]visa|immigration\s+department|customs|dsvn\.vn|loose[- ]leaf\s+visa)\b)",
    re.IGNORECASE
)

GEOLOCATION_REGEX = re.compile(
    r"(?:\b\d{1,4}(?:,\d{3})*\s*(?:m|meters?|metres?)\s*(?:altitude|peak|above\s+sea\s+level)?\b|\b(?:national\s+route\s+\d+[a-z]?|quốc\s+lộ\s+\d+[a-z]?|ql\d+[a-z]?|hai\s+van\s+pass|o\s+quy\s+ho|ma\s+pi\s+leng|fansipan|muong\s+hoa|dong\s+bai|tuan\s+chau|superdong)\b|\b(?:se\d+|tn\d+|ga\s+[a-z]+|bến\s+phà\s+[a-z]+|ferry\s+terminal)\b)",
    re.IGNORECASE
)

CLIMATE_REGEX = re.compile(
    r"(?:\b\d{1,2}(?:\.\d+)?\s*(?:°C|deg\s*C|degrees?\s*(?:celsius)?)\b|\b\d{1,3}\s*%\s*(?:humidity)?\b|\b\d{2,4}\s*mm\b)",
    re.IGNORECASE
)

OPERATOR_HOTLINE_REGEX = re.compile(
    r"(?:\b0\d{2,3}[-.]?\d{2,4}[-.]?\d{3,4}\b|\b(?:113|114|115)\b|\b(?:dsvn\.vn|vexere\.com|xuatnhapcanh\.gov\.vn|evisa\.xuatnhapcanh\.gov\.vn)\b|\b(?:sleeper\s+bus|cable\s+car|hydrofoil|speedboat|xe\s+om|cyclo|sos\s+international|tourist\s+police)\b)",
    re.IGNORECASE
)

# ==============================================================================
# TEXT EXTRACTION & NORMALIZATION
# ==============================================================================

def strip_html(html_text):
    """Strip script, style, comments, and tags to extract plain text."""
    # Remove script and style
    text = re.sub(r"<(script|style|svg)[^>]*>.*?</\1>", " ", html_text, flags=re.DOTALL | re.IGNORECASE)
    # Remove HTML comments
    text = re.sub(r"<!--.*?-->", " ", text, flags=re.DOTALL)
    # Remove URLs so external citations don't trigger false positive clichés
    text = re.sub(r"https?://[^\s<>\"']+", " ", text)
    # Replace block tags with newlines
    text = re.sub(r"</?(div|p|h[1-6]|li|section|article|blockquote|header|footer|tr)[^>]*>", "\n", text, flags=re.IGNORECASE)
    # Remove all remaining tags
    text = re.sub(r"<[^>]+>", " ", text)
    # Unescape common entities
    text = text.replace("&nbsp;", " ").replace("&amp;", "&").replace("&lt;", "<").replace("&gt;", ">").replace("&quot;", '"').replace("&#039;", "'").replace("&#8211;", "–").replace("&#8212;", "—")
    # Collapse whitespace
    lines = [re.sub(r"[ \t]+", " ", line).strip() for line in text.split("\n")]
    return "\n".join([line for line in lines if line])

def extract_sentences(text):
    """Split text into sentences cleanly."""
    # Replace multiple newlines with single space
    clean = re.sub(r"\s+", " ", text).strip()
    if not clean:
        return []
    # Split on sentence boundaries
    raw_sentences = re.split(r"(?<=[.!?])\s+(?=[A-Z0-9\"'“])", clean)
    sentences = [s.strip() for s in raw_sentences if len(s.strip().split()) >= 3]
    return sentences

# ==============================================================================
# CORE ANALYSIS ENGINE
# ==============================================================================

def analyze_text(text, source_name="direct_input"):
    """
    Analyzes text and returns a comprehensive anti-ai slop audit dict.
    """
    plain_text = strip_html(text) if "<" in text and ">" in text else text
    sentences = extract_sentences(plain_text)
    words = plain_text.split()
    word_count = len(words)
    sentence_count = len(sentences)

    # 1. Detect Clichés
    tier1_violations = []
    for pattern, name in TIER1_PATTERNS:
        matches = list(re.finditer(pattern, plain_text, re.IGNORECASE))
        for m in matches:
            # Capture context snippet
            start = max(0, m.start() - 40)
            end = min(len(plain_text), m.end() + 40)
            snippet = plain_text[start:end].replace("\n", " ")
            tier1_violations.append({
                'severity': 'S1_CRITICAL',
                'phrase': name,
                'matched_text': m.group(0),
                'snippet': f"...{snippet}..."
            })

    tier2_violations = []
    for pattern, name in TIER2_PATTERNS:
        matches = list(re.finditer(pattern, plain_text, re.IGNORECASE))
        for m in matches:
            start = max(0, m.start() - 30)
            end = min(len(plain_text), m.end() + 30)
            snippet = plain_text[start:end].replace("\n", " ")
            tier2_violations.append({
                'severity': 'S2_WARNING',
                'phrase': name,
                'matched_text': m.group(0),
                'snippet': f"...{snippet}..."
            })

    tier3_violations = []
    for pattern, name in TIER3_PATTERNS:
        matches = list(re.finditer(pattern, plain_text, re.IGNORECASE))
        for m in matches:
            start = max(0, m.start() - 30)
            end = min(len(plain_text), m.end() + 30)
            snippet = plain_text[start:end].replace("\n", " ")
            tier3_violations.append({
                'severity': 'S3_SIGNPOSTING',
                'phrase': name,
                'matched_text': m.group(0),
                'snippet': f"...{snippet}..."
            })

    passive_violations = []
    for pattern, name in PASSIVE_AI_PADDING_PATTERNS:
        matches = list(re.finditer(pattern, plain_text, re.IGNORECASE))
        for m in matches:
            start = max(0, m.start() - 30)
            end = min(len(plain_text), m.end() + 30)
            snippet = plain_text[start:end].replace("\n", " ")
            passive_violations.append({
                'severity': 'S3_PASSIVE',
                'phrase': name,
                'matched_text': m.group(0),
                'snippet': f"...{snippet}..."
            })

    tier4_violations = []
    for pattern, name in TIER4_PATTERNS:
        matches = list(re.finditer(pattern, plain_text, re.IGNORECASE))
        for m in matches:
            start = max(0, m.start() - 30)
            end = min(len(plain_text), m.end() + 30)
            snippet = plain_text[start:end].replace("\n", " ")
            tier4_violations.append({
                'severity': 'S2_MODERN_TROPE',
                'phrase': name,
                'matched_text': m.group(0),
                'snippet': f"...{snippet}..."
            })

    # 2. Measure Cadence (Coefficient of Variation)
    if sentence_count >= 3:
        lengths = [len(s.split()) for s in sentences]
        mean_len = sum(lengths) / sentence_count
        variance = sum((l - mean_len) ** 2 for l in lengths) / sentence_count
        std_dev = math.sqrt(variance)
        cv = std_dev / mean_len if mean_len > 0 else 0.0
    else:
        mean_len = float(word_count)
        std_dev = 0.0
        cv = 0.5  # Neutral default for very short inputs

    # 3. Detect Evidence Anchors
    currency_matches = list(CURRENCY_REGEX.finditer(plain_text))
    transit_matches = list(TRANSIT_TIME_REGEX.finditer(plain_text))
    regulatory_matches = list(REGULATORY_REGEX.finditer(plain_text))
    geolocation_matches = list(GEOLOCATION_REGEX.finditer(plain_text))
    climate_matches = list(CLIMATE_REGEX.finditer(plain_text))
    operator_matches = list(OPERATOR_HOTLINE_REGEX.finditer(plain_text))
    evidence_count = (len(currency_matches) + len(transit_matches) + len(regulatory_matches) +
                      len(geolocation_matches) + len(climate_matches) + len(operator_matches))

    # Evidence Density Index (EDI): Evidence anchors per 1,000 words
    if word_count > 0:
        edi = round((evidence_count / word_count) * 1000.0, 2)
    else:
        edi = 0.0

    # 4. Calculate Score
    base_score = 100
    base_score -= len(tier1_violations) * 25
    base_score -= len(tier2_violations) * 5
    base_score -= len(tier3_violations) * 10
    base_score -= len(passive_violations) * 5
    base_score -= len(tier4_violations) * 10

    # Cadence factor
    if cv >= 0.45:
        base_score += 10
    elif cv < 0.35 and sentence_count >= 5:
        base_score -= 15

    # Evidence factor
    if evidence_count >= 5:
        base_score += 10
    elif evidence_count == 0 and word_count >= 200:
        base_score -= 15

    final_score = max(0, min(100, base_score))

    # Strict Gate (v4.0):
    # 1. Zero Tier 1 violations
    # 2. Maximum 2 Tier 3 signposting violations
    # 3. Maximum 1 Tier 4 modern trope / sycophancy violation
    # 4. HLS score >= 80
    # 5. If in-depth guide (word_count >= 400 and not archive/policy page): must achieve strict EDI >= 4.0
    INDEX_OR_POLICY_SLUGS = (
        'privacy-policy', 'editorial-policy', 'affiliate-disclosure', 'affiliate-review-policy',
        'source-update-policy', 'contact', 'newsletter', 'about'
    )
    is_index_or_policy = any(s in source_name for s in INDEX_OR_POLICY_SLUGS) or source_name.rstrip('/').endswith(('destinations', 'compare', 'itineraries', 'plan', 'costs', 'vietnamguide.net'))

    has_heavy_signposting = (len(tier3_violations) >= 3)
    has_tier4_violations = (len(tier4_violations) >= 2)

    if is_index_or_policy:
        passed = (len(tier1_violations) == 0) and (not has_heavy_signposting) and (not has_tier4_violations) and (final_score >= 80)
    elif word_count >= 400:
        passed = (len(tier1_violations) == 0) and (not has_heavy_signposting) and (not has_tier4_violations) and (final_score >= 80) and (edi >= 4.0)
    else:
        passed = (len(tier1_violations) == 0) and (not has_heavy_signposting) and (not has_tier4_violations) and (final_score >= 80)

    return {
        'source': source_name,
        'passed': passed,
        'hls_score': final_score,
        'edi': edi,
        'word_count': word_count,
        'sentence_count': sentence_count,
        'mean_sentence_length': round(mean_len, 2),
        'std_dev': round(std_dev, 2),
        'cv': round(cv, 3),
        'tier1_count': len(tier1_violations),
        'tier2_count': len(tier2_violations),
        'tier3_count': len(tier3_violations),
        'passive_count': len(passive_violations),
        'tier4_count': len(tier4_violations),
        'tier1_violations': tier1_violations,
        'tier2_violations': tier2_violations,
        'tier3_violations': tier3_violations,
        'passive_violations': passive_violations,
        'tier4_violations': tier4_violations,
        'evidence_count': evidence_count,
        'evidence': {
            'currency_count': len(currency_matches),
            'transit_time_count': len(transit_matches),
            'regulatory_count': len(regulatory_matches),
            'geolocation_count': len(geolocation_matches),
            'operator_count': len(operator_matches),
            'climate_count': len(climate_matches),
            'sample_currencies': list(set([m.group(0) for m in currency_matches[:4]])),
            'sample_transit': list(set([m.group(0) for m in transit_matches[:4]])),
            'sample_operators': list(set([m.group(0) for m in operator_matches[:4]])),
            'sample_geolocation': list(set([m.group(0) for m in geolocation_matches[:4]])),
        }
    }

# ==============================================================================
# SITEMAP & URL FETCHER
# ==============================================================================

def fetch_url_content(url):
    req = urllib.request.Request(
        url,
        headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) VietnamGuide-Audit/1.0'}
    )
    with urllib.request.urlopen(req, timeout=15) as resp:
        return resp.read().decode('utf-8', errors='ignore')

def crawl_sitemap(sitemap_url):
    print(f"Fetching sitemap: {sitemap_url}...")
    xml_data = fetch_url_content(sitemap_url)
    root = ET.fromstring(xml_data)
    
    # Handle namespace if present
    ns = {'ns': 'http://www.sitemaps.org/schemas/sitemap/0.9'}
    urls = []
    
    # Check if this is a sitemapindex
    if root.tag.endswith('sitemapindex'):
        locs = [elem.text.strip() for elem in root.findall('.//ns:loc', ns) or root.findall('.//loc')]
        for sub_sitemap in locs:
            print(f"  Fetching sub-sitemap: {sub_sitemap}")
            sub_xml = fetch_url_content(sub_sitemap)
            sub_root = ET.fromstring(sub_xml)
            sub_locs = [elem.text.strip() for elem in sub_root.findall('.//ns:loc', ns) or sub_root.findall('.//loc')]
            urls.extend(sub_locs)
    else:
        urls = [elem.text.strip() for elem in root.findall('.//ns:loc', ns) or root.findall('.//loc')]
        
    return list(set(urls))

# ==============================================================================
# CLI HANDLER
# ==============================================================================

if __name__ == '__main__':
    import argparse

    parser = argparse.ArgumentParser(description="VietnamGuide Anti-AI Slop Quality Linter")
    parser.add_argument("--file", help="Path to local file to analyze")
    parser.add_argument("--url", help="URL of a page to analyze")
    parser.add_argument("--crawl-sitemap", help="URL of XML sitemap to crawl and analyze all URLs")
    parser.add_argument("--json", action="store_true", help="Output results in JSON format")
    parser.add_argument("--out", help="Save results to specified JSON file")

    args = parser.parse_args()

    if args.file:
        with open(args.file, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        res = analyze_text(content, source_name=args.file)
        if args.json:
            print(json.dumps(res, indent=2))
        else:
            status = "PASS [OK]" if res['passed'] else "FAIL [VIOLATIONS]"
            print(f"=== Anti-AI Slop Report: {args.file} ===")
            print(f"Status: {status} | Score: {res['hls_score']}/100 | Word Count: {res['word_count']}")
            print(f"Sentence CV: {res['cv']} (target >= 0.45) | Evidence Anchors: {res['evidence_count']}")
            print(f"Tier 1 Clichés: {res['tier1_count']} | Tier 2 Warnings: {res['tier2_count']}")
            if res['tier1_violations']:
                print("\n[CRITICAL TIER 1 VIOLATIONS]:")
                for v in res['tier1_violations']:
                    print(f"  - {v['phrase']}: {v['snippet']}")
        sys.exit(0 if res['passed'] else 1)

    elif args.url:
        print(f"Analyzing {args.url}...")
        html = fetch_url_content(args.url)
        res = analyze_text(html, source_name=args.url)
        if args.json:
            print(json.dumps(res, indent=2))
        else:
            status = "PASS [OK]" if res['passed'] else "FAIL [VIOLATIONS]"
            print(f"=== Anti-AI Slop Report: {args.url} ===")
            print(f"Status: {status} | Score: {res['hls_score']}/100 | Words: {res['word_count']}")
            print(f"Sentence CV: {res['cv']} | Evidence: {res['evidence_count']}")
            print(f"Tier 1 Clichés: {res['tier1_count']} | Tier 2 Warnings: {res['tier2_count']}")
            if res['tier1_violations']:
                print("\n[CRITICAL TIER 1 VIOLATIONS]:")
                for v in res['tier1_violations']:
                    print(f"  - {v['phrase']}: {v['snippet']}")
        sys.exit(0 if res['passed'] else 1)

    elif args.crawl_sitemap:
        urls = crawl_sitemap(args.crawl_sitemap)
        print(f"Found {len(urls)} URLs in sitemap. Starting audit...")
        results = []
        passed_count = 0
        failed_count = 0
        total_t1 = 0

        for idx, u in enumerate(urls, 1):
            try:
                html = fetch_url_content(u)
                r = analyze_text(html, source_name=u)
                results.append(r)
                if r['passed']:
                    passed_count += 1
                else:
                    failed_count += 1
                total_t1 += r['tier1_count']
                print(f"[{idx}/{len(urls)}] Score: {r['hls_score']}/100 | T1: {r['tier1_count']} | {u}")
            except Exception as e:
                print(f"[{idx}/{len(urls)}] ERROR on {u}: {e}")

        summary = {
            'total_urls': len(urls),
            'passed': passed_count,
            'failed': failed_count,
            'total_tier1_violations': total_t1,
            'results': results
        }

        if args.out:
            os.makedirs(os.path.dirname(os.path.abspath(args.out)), exist_ok=True)
            with open(args.out, 'w', encoding='utf-8') as f:
                json.dump(summary, f, indent=2)
            print(f"\nAudit complete. Saved {len(results)} reports to {args.out}")

        print(f"\n=== SITEMAP AUDIT SUMMARY ===")
        print(f"Total: {len(urls)} | Passed: {passed_count} | Failed: {failed_count} | Total Tier 1 Clichés: {total_t1}")
        sys.exit(0 if failed_count == 0 else 1)

    else:
        parser.print_help()

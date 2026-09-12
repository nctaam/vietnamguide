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

TIER5_PATTERNS = [
    (r"\bculinary\s+delight[s]?\b", "culinary delight(s) (empty travel fluff)"),
    (r"\bfoodie[s']?\s+paradise\b", "foodie paradise (cliché praise)"),
    (r"\bburst(?:ing|s)?\s+with\s+flavor[s]?\b", "bursting with flavor (sensory trope)"),
    (r"\ba\s+sight\s+to\s+behold\b", "a sight to behold (cliché praise)"),
    (r"\bunmatched\s+beauty\b|\bincomparable\s+beauty\b", "unmatched/incomparable beauty (empty superlative)"),
    (r"\bleave[s]?\s+(?:you|visitors?|travelers?)\s+in\s+awe\b", "leaves in awe (emotional hyperbole)"),
    (r"\btruly\s+something\s+special\b", "truly something special (vague fluff)"),
    (r"\ba\s+trip\s+you\s+won'?t\s+(?:soon\s+)?forget\b", "a trip you won't soon forget (marketing closer)"),
    (r"\bworld\s+of\s+its\s+own\b|\ba\s+world\s+away\b", "world of its own (vague geography)"),
    (r"\bstepping\s+into\s+a\s+postcard\b|\bstraight\s+out\s+of\s+a\s+postcard\b", "stepping into a postcard (visual cliché)"),
]

TIER6_PATTERNS = [
    (r"\bit\s+is\s+worth\s+noting\s+that\b", "it is worth noting that (meta-commentary filler)"),
    (r"\bit\s+is\s+important\s+to\s+(?:remember|note|keep\s+in\s+mind)\s+that\b", "it is important to remember/note that (over-explanation)"),
    (r"\bit\s+is\s+essential\s+to\s+note\s+that\b", "it is essential to note that (over-explanation)"),
    (r"\b(?:a\s+)?blend\s+of\s+tradition\s+and\s+modernity\b|\bwhere\s+tradition\s+meets\s+modernity\b", "blend of tradition and modernity (lazy cultural cliché)"),
    (r"\bvibrant\s+tapestry\b", "vibrant tapestry (formulaic metaphor)"),
    (r"\bstanding\s+as\s+a\s+beacon\s+of\b|\ba\s+beacon\s+of\b", "beacon of (grandiose metaphor)"),
    (r"\bdelve\s+deeper\s+into\b", "delve deeper into (conversational filler)"),
    (r"\b(?:a\s+)?testament\s+to\b|\bserves\s+as\s+a\s+testament\s+to\b", "testament to (formulaic praise)"),
    (r"\ba\s+journey\s+of\s+self[- ]discovery\b", "journey of self-discovery (pretentious marketing)"),
]

TIER7_PATTERNS = [
    (r"\b(?:it(?:'s|\s+is)\s+no\s+secret\s+that)\b", "it's no secret that (false consensus)"),
    (r"\bas\s+(?:any\s+)?seasoned\s+travelers?\s+know[s]?\b", "as any seasoned traveler knows (hollow authority)"),
    (r"\bneedless\s+to\s+say\b", "needless to say (sycophantic filler)"),
    (r"\bit\s+goes\s+without\s+saying\b", "it goes without saying (empty assertion)"),
    (r"\bsuffice\s+it\s+to\s+say\b", "suffice it to say (formulaic hedging)"),
    (r"\bat\s+the\s+end\s+of\s+the\s+day\b", "at the end of the day (conversational cliché)"),
    (r"\bwhen\s+all\s+is\s+said\s+and\s+done\b", "when all is said and done (conversational cliché)"),
    (r"\bmake\s+no\s+mistake\b", "make no mistake (hyperbolic framing)"),
    (r"\btruth\s+be\s+told\b|\btruth,\s*be\s+told\b", "truth be told (artificial intimacy)"),
    (r"\ball\s+in\s+all\b", "all in all (empty summary marker)"),
    (r"\bin\s+conclusion\b|\bto\s+sum\s+up\b|\bwrapping\s+up\b|\bparting\s+thoughts\b|\bfinal\s+thoughts\b", "in conclusion / to sum up / final thoughts (AI summary boilerplate)"),
]

TIER8_PATTERNS = [
    (r"\b(?:it(?:'s|\s+is)\s+not\s+just\s+about\b[^.!?]{1,60}\bit(?:'s|\s+is)\s+about)\b", "not just about X, it's about Y (hollow synthetic contrast)"),
    (r"\bfrom\s+[a-z0-9\s,-]{3,30}\s+to\s+[a-z0-9\s,-]{3,30}(?:,\s*)?vietnam\s+has\s+it\s+all\b", "from X to Y, Vietnam has it all (cliché formula)"),
    (r"\b(?:rich\s+)?tapestry\s+of\b", "tapestry of [cultures/history] (cliché trope)"),
    (r"\bmouth[- ]watering\s+(?:dishes|food|delicacies|flavors|cuisine)\b", "mouth-watering cuisine (sensory cliché)"),
    (r"\bsteeped\s+in\s+history\b", "steeped in history (cliché descriptor)"),
    (r"\bnestled\s+in\s+the\s+heart\s+of\b", "nestled in the heart of (formulaic geography)"),
    (r"\ba\s+stone(?:'s)?\s+throw\s+(?:away\s+)?from\b", "a stone's throw from (formulaic proximity)"),
    (r"\bhidden\s+gem[s]?\s+waiting\s+to\s+be\s+discovered\b", "hidden gems waiting to be discovered (cliché trope)"),
    (r"\boasis\s+of\s+(?:calm|peace|tranquility)\b", "oasis of calm (cliché refuge)"),
    (r"\bhave\s+you\s+ever\s+wondered\b", "have you ever wondered (formulaic hook)"),
    (r"\bare\s+you\s+ready\s+to\b", "are you ready to (conversational filler)"),
    (r"\blace\s+up\s+your\s+(?:hiking\s+)?boots\b", "lace up your boots (formulaic call-to-action)"),
    (r"\bdon(?:'t|\s+not)\s+take\s+our\s+word\s+for\s+it\b", "don't take our word for it (sycophantic filler)"),
    (r"\bsit\s+back(?:,|\s+)relax\b", "sit back and relax (conversational trope)"),
]

def count_syllables(word):
    """Estimate English syllables for Flesch readability calculation."""
    w = word.lower().strip()
    if len(w) <= 3:
        return 1
    w = re.sub(r'(?:[^laeiouy]|ed|es|e)$', '', w)
    w = re.sub(r'^y', '', w)
    matches = re.findall(r'[aeiouy]{1,2}', w)
    return max(1, len(matches))

HYPERBOLIC_ADJECTIVES = {
    "stunning", "breathtaking", "unique", "captivating", "unforgettable", "magical", "mesmerizing", "enchanting"
}


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
    # Remove script, style, svg, nav, and footer
    text = re.sub(r"<(script|style|svg|nav|footer)[^>]*>.*?</\1>", " ", html_text, flags=re.DOTALL | re.IGNORECASE)
    # Remove contextual journey, related routes, and season matrix interactive widgets so navigation and tabs do not pollute prose analysis
    text = re.sub(r"<section[^>]*class=[\"'][^\"']*(?:vg-contextual-journey|vg-related-routes|vg-season-matrix)[^\"']*[\"'][^>]*>.*?</section>", " ", text, flags=re.DOTALL | re.IGNORECASE)
    # Remove source lists and evidence ledgers so external citations and audit logs do not distort prose analysis
    text = re.sub(r"<ul[^>]*class=[\"'][^\"']*vg-source-list[^\"']*[\"'][^>]*>.*?</ul>", " ", text, flags=re.DOTALL | re.IGNORECASE)
    # Remove HTML comments
    text = re.sub(r"<!--.*?-->", " ", text, flags=re.DOTALL)
    # Remove URLs so external citations don't trigger false positive clichés
    text = re.sub(r"https?://[^\s<>\"']+", " ", text)
    # Mark list items and table cells with bullet prefix so prose extraction isolates narrative sentences
    text = re.sub(r"<(li|tr|th|td)[^>]*>", "\n• ", text, flags=re.IGNORECASE)
    # Replace block tags with paragraph breaks
    text = re.sub(r"</?(div|p|h[1-6]|section|article|blockquote|header|footer|ul|ol|table|thead|tbody)[^>]*>", "\n\n", text, flags=re.IGNORECASE)
    # Remove all remaining tags
    text = re.sub(r"<[^>]+>", " ", text)
    # Unescape common entities
    text = text.replace("&nbsp;", " ").replace("&amp;", "&").replace("&lt;", "<").replace("&gt;", ">").replace("&quot;", '"').replace("&#039;", "'").replace("&#8211;", "–").replace("&#8212;", "—")
    # Collapse whitespace
    lines = [re.sub(r"[ \t]+", " ", line).strip() for line in text.split("\n")]
    return "\n".join([line for line in lines if line])

def extract_sentences(text):
    """Split text into continuous narrative prose sentences cleanly."""
    paragraphs = text.split("\n\n")
    prose_sentences = []
    for para in paragraphs:
        lines = para.split("\n")
        # Prose lines only (exclude bullet lists and table rows marked with •)
        prose_lines = [l for l in lines if not l.startswith("•")]
        para_text = " ".join(prose_lines).strip()
        if not para_text:
            continue
        clean = re.sub(r"\s+", " ", para_text).strip()
        raw_sentences = re.split(r"(?<=[.!?])\s+(?=[A-Z0-9\"'“])", clean)
        for s in raw_sentences:
            s_clean = s.strip()
            if len(s_clean.split()) >= 3:
                prose_sentences.append(s_clean)
    if not prose_sentences and text.strip():
        # Fallback for plain text inputs without paragraph / bullet markup
        clean = re.sub(r"\s+", " ", text).strip()
        raw_sentences = re.split(r"(?<=[.!?])\s+(?=[A-Z0-9\"'“])", clean)
        prose_sentences = [s.strip() for s in raw_sentences if len(s.strip().split()) >= 3]
    return prose_sentences

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

    tier5_violations = []
    for pattern, name in TIER5_PATTERNS:
        matches = list(re.finditer(pattern, plain_text, re.IGNORECASE))
        for m in matches:
            start = max(0, m.start() - 30)
            end = min(len(plain_text), m.end() + 30)
            snippet = plain_text[start:end].replace("\n", " ")
            tier5_violations.append({
                'severity': 'S2_TRAVEL_FLUFF',
                'phrase': name,
                'matched_text': m.group(0),
                'snippet': f"...{snippet}..."
            })

    tier6_violations = []
    for pattern, name in TIER6_PATTERNS:
        matches = list(re.finditer(pattern, plain_text, re.IGNORECASE))
        for m in matches:
            start = max(0, m.start() - 30)
            end = min(len(plain_text), m.end() + 30)
            snippet = plain_text[start:end].replace("\n", " ")
            tier6_violations.append({
                'severity': 'S2_OVER_EXPLANATION',
                'phrase': name,
                'matched_text': m.group(0),
                'snippet': f"...{snippet}..."
            })

    tier7_violations = []
    for pattern, name in TIER7_PATTERNS:
        matches = list(re.finditer(pattern, plain_text, re.IGNORECASE))
        for m in matches:
            start = max(0, m.start() - 30)
            end = min(len(plain_text), m.end() + 30)
            snippet = plain_text[start:end].replace("\n", " ")
            tier7_violations.append({
                'severity': 'S1_TIER7_AUTHORITY_SLOP',
                'phrase': name,
                'matched_text': m.group(0),
                'snippet': f"...{snippet}..."
            })

    tier8_violations = []
    for pattern, name in TIER8_PATTERNS:
        matches = list(re.finditer(pattern, plain_text, re.IGNORECASE))
        for m in matches:
            start = max(0, m.start() - 30)
            end = min(len(plain_text), m.end() + 30)
            snippet = plain_text[start:end].replace("\n", " ")
            tier8_violations.append({
                'severity': 'S1_TIER8_SUPERFICIAL_RHETORIC',
                'phrase': name,
                'matched_text': m.group(0),
                'snippet': f"...{snippet}..."
            })

    # Adjective clustering analysis (detect 3+ hyperbolic adjectives within sliding 150-word window)
    adjective_cluster_violations = []
    plain_words_lower = [re.sub(r"[^\w]", "", w.lower()) for w in plain_text.split()]

    if len(plain_words_lower) <= 150:
        found_adj = [w for w in plain_words_lower if w in HYPERBOLIC_ADJECTIVES]
        if len(found_adj) >= 3:
            adjective_cluster_violations.append({
                'severity': 'S2_ADJECTIVE_CLUSTERING',
                'matched_words': found_adj,
                'word_window_idx': 0,
                'snippet': " ".join(plain_words_lower[:30]) + "..."
            })
    else:
        for w_idx in range(0, len(plain_words_lower) - 150 + 1, 50):
            sub_window = plain_words_lower[w_idx:w_idx + 150]
            found_adj = [w for w in sub_window if w in HYPERBOLIC_ADJECTIVES]
            if len(found_adj) >= 3:
                adjective_cluster_violations.append({
                    'severity': 'S2_ADJECTIVE_CLUSTERING',
                    'matched_words': found_adj,
                    'word_window_idx': w_idx,
                    'snippet': " ".join(sub_window[:30]) + "..."
                })
                break

    INDEX_OR_POLICY_SLUGS = (
        'privacy-policy', 'editorial-policy', 'affiliate-disclosure', 'affiliate-review-policy',
        'source-update-policy', 'contact', 'newsletter', 'about'
    )
    is_index_or_policy = any(s in source_name for s in INDEX_OR_POLICY_SLUGS) or source_name.rstrip('/').endswith(('destinations', 'compare', 'itineraries', 'plan', 'costs', 'vietnamguide.net'))

    # Lexical diversity analysis (Type-Token Ratio / windowed TTR)
    lexical_diversity_violations = []
    if len(plain_words_lower) >= 100:
        window_size = 100
        ttrs = []
        for i in range(0, len(plain_words_lower) - window_size + 1, 25):
            win_tokens = plain_words_lower[i:i + window_size]
            ttrs.append(len(set(win_tokens)) / float(window_size))
        lexical_diversity = round(sum(ttrs) / len(ttrs), 3) if ttrs else 1.0
        if lexical_diversity < 0.40 and not is_index_or_policy:
            lexical_diversity_violations.append({
                'severity': 'S2_LOW_LEXICAL_DIVERSITY',
                'ttr': lexical_diversity,
                'snippet': f"Average Type-Token Ratio {lexical_diversity:.3f} below 0.40 threshold across text"
            })
    elif len(plain_words_lower) > 0:
        lexical_diversity = round(len(set(plain_words_lower)) / float(len(plain_words_lower)), 3)
        if lexical_diversity < 0.40 and len(plain_words_lower) >= 15 and not is_index_or_policy:
            lexical_diversity_violations.append({
                'severity': 'S2_LOW_LEXICAL_DIVERSITY',
                'ttr': lexical_diversity,
                'snippet': f"Overall Type-Token Ratio {lexical_diversity:.3f} below 0.40 threshold"
            })
    else:
        lexical_diversity = 1.0

    # Flesch-Kincaid Reading Ease calculation
    total_syllables = sum(count_syllables(w) for w in plain_words_lower) if plain_words_lower else 0
    if sentence_count > 0 and word_count > 0:
        flesch_reading_ease = round(206.835 - 1.015 * (word_count / sentence_count) - 84.6 * (total_syllables / word_count), 2)
    else:
        flesch_reading_ease = 70.0

    # 2. Measure Cadence (Coefficient of Variation) & Repetitive Openers


    repetitive_openers_violations = []
    if sentence_count >= 3:
        lengths = [len(s.split()) for s in sentences]
        mean_len = sum(lengths) / sentence_count
        variance = sum((l - mean_len) ** 2 for l in lengths) / sentence_count
        std_dev = math.sqrt(variance)
        cv = std_dev / mean_len if mean_len > 0 else 0.0

        # Repetitive opener detection on continuous prose (exempting directory index / hub card loops)
        if not is_index_or_policy:
            idx = 0
            while idx < len(sentences) - 2:
                if len(sentences[idx].split()) < 5 or len(sentences[idx+1].split()) < 5 or len(sentences[idx+2].split()) < 5:
                    idx += 1
                    continue
                s1_words = re.findall(r"\b[A-Za-z0-9']+\b", sentences[idx])
                s2_words = re.findall(r"\b[A-Za-z0-9']+\b", sentences[idx + 1])
                s3_words = re.findall(r"\b[A-Za-z0-9']+\b", sentences[idx + 2])
                if s1_words and s2_words and s3_words:
                    first1 = s1_words[0].lower()
                    first2 = s2_words[0].lower()
                    first3 = s3_words[0].lower()
                    if first1 == first2 == first3:
                        repetitive_openers_violations.append({
                            'severity': 'S3_REPETITIVE_OPENER',
                            'opener': first1,
                            'sentence_start_idx': idx,
                            'snippet': f"{sentences[idx][:40]}... / {sentences[idx+1][:40]}... / {sentences[idx+2][:40]}..."
                        })
                        idx += 3
                        continue
                idx += 1

        # Local sentence cadence monotony detection (sliding window of 6 sentences)
        local_cadence_violations = []
        if sentence_count >= 6 and not is_index_or_policy:
            sentence_word_lengths = [len(s.split()) for s in sentences]
            for i in range(len(sentence_word_lengths) - 5):
                window = sentence_word_lengths[i:i + 6]
                win_mean = sum(window) / 6.0
                if win_mean >= 8.0:
                    win_var = sum((x - win_mean) ** 2 for x in window) / 6.0
                    win_cv = math.sqrt(win_var) / win_mean if win_mean > 0 else 0.0
                    if win_cv < 0.20:
                        local_cadence_violations.append({
                            'severity': 'S2_LOCAL_CADENCE_MONOTONY',
                            'sentence_start_idx': i,
                            'lengths': window,
                            'local_cv': round(win_cv, 3),
                            'snippet': f"{sentences[i][:40]}... [{window}]"
                        })
                        break
    else:
        mean_len = float(word_count)
        std_dev = 0.0
        cv = 0.5  # Neutral default for very short inputs
        local_cadence_violations = []

    passive_ratio = round(len(passive_violations) / sentence_count, 3) if sentence_count > 0 else 0.0

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
    base_score -= len(tier5_violations) * 15
    base_score -= len(tier6_violations) * 10
    base_score -= len(tier7_violations) * 15
    base_score -= len(tier8_violations) * 15
    base_score -= len(adjective_cluster_violations) * 10
    base_score -= len(repetitive_openers_violations) * 10
    base_score -= len(local_cadence_violations) * 10
    base_score -= len(lexical_diversity_violations) * 10
    if passive_ratio > 0.15 and sentence_count >= 5:
        base_score -= 10

    # Cadence factor
    if is_index_or_policy:
        pass
    elif cv >= 0.45:
        base_score += 10
    elif cv < 0.35 and sentence_count >= 5:
        base_score -= 15

    # Evidence factor
    if evidence_count >= 5:
        base_score += 10
    elif evidence_count == 0 and word_count >= 200:
        base_score -= 15

    final_score = max(0, min(100, base_score))

    # Strict Gate (v8.0):
    # 1. Zero Tier 1 violations
    # 2. Maximum 2 Tier 3 signposting violations
    # 3. Maximum 1 Tier 4 modern trope / sycophancy violation
    # 4. Zero Tier 5 travel fluff violations
    # 5. Zero Tier 6 over-explanation violations
    # 6. Zero Tier 7 authority slop violations
    # 7. Zero Tier 8 superficial rhetoric violations
    # 8. HLS score >= 80
    # 9. If in-depth guide (word_count >= 400 and not archive/policy page): must achieve strict EDI >= 4.0 (or evidence_count >= 10 and EDI >= 3.0)

    has_heavy_signposting = (len(tier3_violations) >= 3)
    has_tier4_violations = (len(tier4_violations) >= 2)
    has_tier5_violations = (len(tier5_violations) >= 1)
    has_tier6_violations = (len(tier6_violations) >= 1)
    has_tier7_violations = (len(tier7_violations) >= 1)
    has_tier8_violations = (len(tier8_violations) >= 1)

    common_pass = (
        (len(tier1_violations) == 0) and
        (not has_heavy_signposting) and
        (not has_tier4_violations) and
        (not has_tier5_violations) and
        (not has_tier6_violations) and
        (not has_tier7_violations) and
        (not has_tier8_violations) and
        (final_score >= 80)
    )

    if is_index_or_policy:
        passed = common_pass
    elif word_count >= 400:
        passed = common_pass and (edi >= 4.0 or (evidence_count >= 10 and edi >= 3.0))
    else:
        passed = common_pass

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
        'tier5_count': len(tier5_violations),
        'tier6_count': len(tier6_violations),
        'tier7_count': len(tier7_violations),
        'tier8_count': len(tier8_violations),
        'adjective_cluster_count': len(adjective_cluster_violations),
        'repetitive_openers_count': len(repetitive_openers_violations),
        'lexical_diversity': lexical_diversity,
        'flesch_reading_ease': flesch_reading_ease,
        'passive_ratio': passive_ratio,
        'tier1_violations': tier1_violations,
        'tier2_violations': tier2_violations,
        'tier3_violations': tier3_violations,
        'passive_violations': passive_violations,
        'tier4_violations': tier4_violations,
        'tier5_violations': tier5_violations,
        'tier6_violations': tier6_violations,
        'tier7_violations': tier7_violations,
        'tier8_violations': tier8_violations,
        'adjective_cluster_violations': adjective_cluster_violations,
        'repetitive_openers_violations': repetitive_openers_violations,
        'local_cadence_violations': local_cadence_violations,
        'lexical_diversity_violations': lexical_diversity_violations,
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

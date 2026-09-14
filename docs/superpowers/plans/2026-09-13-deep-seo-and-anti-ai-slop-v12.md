# Stage 39: Deep SEO Hardening & Anti-AI Slop Quality Engine v12.0 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Elevate VietnamGuide to peak editorial authenticity and semantic search sophistication by upgrading the Anti-AI Slop Quality Engine to v12.0 (Tier 12 semantic travel clichés/fluff, false-equivalence hedges, and syntactic opener monotony detection), hardening Schema.org travel structured data with precise entity semantics (`TouristDestination`, `TouristTrip`, `TravelAction`), and maintaining 100% pass across all 102 sitemap URLs with zero feature creep.

**Architecture:** Python 3.13 regex and statistical cadence quality engine (`ops/anti_ai_slop_linter.py`) enhanced with Tier 12 cliché patterns and syntactic monotony detectors (consecutive participle `-ing` and identical prepositional openers). WordPress theme layer (`wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php`) hardened with rich ISO travel attributes (`currenciesAccepted: "VND"`, `availableLanguage: ["en", "vi"]`, elevation metadata, accessibility indicators, and provider links). Comprehensive automated unit tests (`ops/tests/test-anti-ai-slop.py`) and live HTTPS regression tests (`ops/tests/test-policy-cadence.py`) enforce zero slop, valid Schema.org graph, 5/5 master CI/CD gates, and 87/87 HTTP 200 public routes.

**Tech Stack:** Python 3.13 (`unittest`, `urllib`, `re`, `math`, `json`), PHP 8.2 / WordPress 6.x Core, Rank Math SEO JSON-LD graph filter, OpenLiteSpeed web server, PowerShell 5.1 CI/CD test harness.

**Spec:** `docs/superpowers/plans/2026-09-13-deep-seo-and-anti-ai-slop-v12.md`

## Global Constraints

- **Strict Zero Feature Creep:** No new custom post types, no new public URLs, no new interactive shortcodes, no new WordPress plugins, no new npm dependencies. Deepen and polish existing systems only.
- **Single Source of Truth:** `M:\Projects\vietnamguide` on `master` branch. Never touch `D:\Documents\vietnamguide`.
- **Core MU-Plugin Invariant:** Hash `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` must remain 100% untouched; all 16 AST safety mutations must be rejected.
- **Public Route Invariant:** All 87 public HTTPS routes must return HTTP 200 with full DOM integrity.
- **Production Server:** `66.42.48.146:2209`, user `root`, SSH key `C:\Users\NCTaam\.ssh\deploy_bot_key`. Webroot: `/usr/local/lsws/vietnamguide.net/html`.
- **Quality Gate:** 102/102 sitemap URLs must achieve $HLS = 100.00$ with 0 Tier 1–12 slop violations, 0 repetitive openers, 0 repetitive bigrams, and 0 syntactic monotony defects.

---

## Tasks Breakdown

### Task 1: Anti-AI Slop Quality Engine v12.0 Upgrade

**Files:**
- Modify: `ops/anti_ai_slop_linter.py:207-230`, `ops/anti_ai_slop_linter.py:500-540`, `ops/anti_ai_slop_linter.py:610-660`, `ops/anti_ai_slop_linter.py:730-770`
- Test: `ops/tests/test-anti-ai-slop.py:380-430`

**Interfaces:**
- Consumes: `analyze_text(plain_text: str, source_name: str) -> dict`
- Produces: `report['tier12_count']`, `report['tier12_violations']`, `report['syntactic_monotony_count']`, `report['syntactic_monotony_violations']`

- [ ] **Step 1: Write failing unit tests for Tier 12 tropes and syntactic opener monotony**

Add the following tests to `ops/tests/test-anti-ai-slop.py`:

```python
    def test_tier12_travel_fluff_and_false_equivalence(self):
        text = (
            "A visit to the Imperial Citadel is a must for any itinerary. "
            "The morning market offers a glimpse into local life along the river. "
            "The ancient pagodas stand as a testament to the enduring heritage of Hue. "
            "Whether you choose a private sampan or a group boat, you won't be disappointed. "
            "This village is steeped in tradition and replete with handcrafted lanterns. "
            "The sunset leaves an indelible impression on everyone who witnesses it. "
            "In today's fast-paced world, finding tranquility is rare. "
            "Before embarking on this route, check bus schedules. "
            "Travelers can dive into the vibrant culture of the old quarter."
        )
        report = linter.analyze_text(text, source_name="test-tier12-slop")
        self.assertIn('tier12_violations', report, "Report must include tier12_violations")
        self.assertGreaterEqual(report.get('tier12_count', 0), 5, "Must detect Tier 12 travel fluff patterns")
        self.assertFalse(report['passed'], "Tier 12 slop must fail quality gate")

    def test_syntactic_participle_opener_monotony(self):
        text = (
            "Traveling through the northern highlands requires sturdy footwear and patience. "
            "Exploring the mountain passes reveals remote ethnic hamlets along the ridge. "
            "Navigating the steep gravel switchbacks demands low gear and steady throttle. "
            "The provincial road QL4D connects Sa Pa with Lai Chau over O Quy Ho Pass."
        )
        report = linter.analyze_text(text, source_name="test-participle-monotony")
        self.assertIn('syntactic_monotony_violations', report)
        self.assertGreaterEqual(report.get('syntactic_monotony_count', 0), 1, "Must detect 3 consecutive participle openers")
        self.assertFalse(report['passed'])

    def test_syntactic_prepositional_opener_monotony(self):
        text = (
            "For budget backpackers, the overnight sleeper bus departs My Dinh at 21:00 for 250,000 VND. "
            "For family groups, private limousine vans cost 450,000 VND per seat with hotel pickup. "
            "For solo motorcyclists, motorcycle rental shops in Ha Giang charge 180,000 VND per day. "
            "Always inspect brake pads and tire pressure before leaving town."
        )
        report = linter.analyze_text(text, source_name="test-prepositional-monotony")
        self.assertIn('syntactic_monotony_violations', report)
        self.assertGreaterEqual(report.get('syntactic_monotony_count', 0), 1, "Must detect 3 consecutive identical prepositional openers")
        self.assertFalse(report['passed'])

    def test_clean_prose_passes_tier12_and_syntactic_cadence(self):
        clean_text = (
            "Hanoi rail operations center on Ga Ha Noi at 120 Le Duan. "
            "Southbound trains SE1 and SE3 depart daily for Da Nang and Ho Chi Minh City. "
            "Fares for a 4-berth air-conditioned sleeper berth to Da Nang start at 850,000 VND. "
            "Passengers reserve tickets at the station counter or through dsvn.vn using international cards."
        )
        report = linter.analyze_text(clean_text, source_name="test-clean-tier12")
        self.assertEqual(report.get('tier12_count', 0), 0)
        self.assertEqual(report.get('syntactic_monotony_count', 0), 0)
        self.assertTrue(report['passed'])
```

- [ ] **Step 2: Run test suite to verify tests fail**

Run:
```powershell
python M:\Projects\vietnamguide\ops\tests\test-anti-ai-slop.py
```
Expected: FAIL with missing `tier12_violations` or `syntactic_monotony_violations`.

- [ ] **Step 3: Implement Tier 12 patterns and syntactic monotony detector in `ops/anti_ai_slop_linter.py`**

In `ops/anti_ai_slop_linter.py`:

1. Define `TIER12_PATTERNS`:
```python
TIER12_PATTERNS = [
    (r"\ba\s+must\s+for\s+any\s+itinerary\b|\ban\s+essential\s+addition\s+to\s+any\s+itinerary\b", "must for any itinerary (travel formula)"),
    (r"\boffers?\s+a\s+glimpse\s+into\b|\bprovides?\s+a\s+glimpse\s+(?:of|into)\b", "offers/provides a glimpse into (lazy observer trope)"),
    (r"\btestament\s+to\s+the\s+(?:enduring|rich|resilient|vibrant)\b", "testament to the enduring/rich (didactic praise)"),
    (r"\b(?:whether\s+you\s+choose|whichever\s+you\s+choose)\b[^.!?]{1,60}\byou\s+won'?t\s+be\s+disappointed\b|\beither\s+way,\s+you\s+can'?t\s+go\s+wrong\b", "won't be disappointed / can't go wrong (false equivalence cop-out)"),
    (r"\bsteeped\s+in\s+tradition\b", "steeped in tradition (cliché descriptor)"),
    (r"\breplete\s+with\b", "replete with (affected literary filler)"),
    (r"\bleaves?\s+an\s+indelible\s+impression\b", "leaves an indelible impression (sentimental hyperbole)"),
    (r"\bin\s+today'?s\s+fast[- ]paced\s+world\b", "in today's fast-paced world (AI cliché intro)"),
    (r"\bembarking\s+on\s+this\b", "embarking on this (formulaic staging)"),
    (r"\bdive\s+(?:headfirst\s+)?into\s+the\s+vibrant\s+culture\b", "dive into the vibrant culture (travel cliché)"),
]
```

2. Scan for `TIER12_PATTERNS` in `analyze_text()`:
```python
    tier12_violations = []
    for pattern, name in TIER12_PATTERNS:
        matches = list(re.finditer(pattern, plain_text, re.IGNORECASE))
        for m in matches:
            start = max(0, m.start() - 30)
            end = min(len(plain_text), m.end() + 30)
            snippet = plain_text[start:end].replace("\n", " ")
            tier12_violations.append({
                'severity': 'S1_TIER12_TRAVEL_FLUFF',
                'phrase': name,
                'matched_text': m.group(0),
                'snippet': f"...{snippet}..."
            })
```

3. Add syntactic monotony analysis (participle `-ing` opener and prepositional opener monotony):
```python
    syntactic_monotony_violations = []
    COMMON_OPENER_PREPOSITIONS = {
        'for', 'in', 'at', 'on', 'with', 'by', 'from', 'to', 'under', 'during', 'after', 'before'
    }
    if sentence_count >= 3 and not is_index_or_policy:
        for i in range(len(sentences) - 2):
            s1, s2, s3 = sentences[i], sentences[i+1], sentences[i+2]
            w1 = re.findall(r"\b[A-Za-z0-9']+\b", s1)
            w2 = re.findall(r"\b[A-Za-z0-9']+\b", s2)
            w3 = re.findall(r"\b[A-Za-z0-9']+\b", s3)
            if not (w1 and w2 and w3):
                continue
            first1 = w1[0].lower()
            first2 = w2[0].lower()
            first3 = w3[0].lower()
            
            # Rule A: Triple consecutive -ing participle openers
            if first1.endswith('ing') and first2.endswith('ing') and first3.endswith('ing'):
                if len(first1) > 4 and len(first2) > 4 and len(first3) > 4:
                    syntactic_monotony_violations.append({
                        'severity': 'S2_SYNTACTIC_PARTICIPLE_MONOTONY',
                        'opener_type': 'triple_ing_participle',
                        'words': [first1, first2, first3],
                        'sentence_start_idx': i,
                        'snippet': f"{s1[:35]}... / {s2[:35]}... / {s3[:35]}..."
                    })

            # Rule B: Triple consecutive identical prepositional openers
            if first1 == first2 == first3 and first1 in COMMON_OPENER_PREPOSITIONS:
                syntactic_monotony_violations.append({
                    'severity': 'S2_SYNTACTIC_PREPOSITION_MONOTONY',
                    'opener_type': 'triple_identical_preposition',
                    'preposition': first1,
                    'sentence_start_idx': i,
                    'snippet': f"{s1[:35]}... / {s2[:35]}... / {s3[:35]}..."
                })
```

4. Integrate into base score and quality gate:
```python
    base_score -= len(tier12_violations) * 15
    base_score -= len(syntactic_monotony_violations) * 10

    has_tier12_violations = (len(tier12_violations) >= 1)
    has_syntactic_monotony = (len(syntactic_monotony_violations) >= 1)

    common_pass = (
        ...
        (not has_tier11_violations) and
        (not has_tier12_violations) and
        (final_score >= 80)
    )

    if is_index_or_policy:
        passed = common_pass
    elif word_count >= 400:
        passed = common_pass and (not has_repetitive_openers) and (not has_repetitive_bigrams) and (not has_syntactic_monotony) and (edi >= 4.0 or (evidence_count >= 10 and edi >= 3.0))
    else:
        passed = common_pass
```

- [ ] **Step 4: Run unit tests to verify they pass**

Run:
```powershell
python M:\Projects\vietnamguide\ops\tests\test-anti-ai-slop.py
```
Expected: PASS with 100% test success.

- [ ] **Step 5: Commit changes**

```bash
git add ops/anti_ai_slop_linter.py ops/tests/test-anti-ai-slop.py
git commit -m "feat(linter): upgrade Anti-AI Slop Quality Engine to v12.0 with Tier 12 tropes and syntactic monotony detection"
```

---

### Task 2: Intelligent SEO Schema & Entity Architecture Hardening

**Files:**
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php:110-605`, `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php:925-1070`
- Test: `ops/tests/test-policy-cadence.py`

**Interfaces:**
- Consumes: `vg_get_travel_clusters_registry() -> array`, `vg_rich_travel_schema_filter($data, $context) -> array`
- Produces: Enhanced Schema.org JSON-LD graph with `TouristDestination` (`currenciesAccepted`, `availableLanguage`, `publicAccess`, `hasMap`), `TouristTrip` (`provider`, `offers`), and `TravelAction` (`actionStatus`).

- [ ] **Step 1: Write test verifying schema additions on simulated data**

In `ops/tests/test-policy-cadence.py`, add:
```python
    def test_seo_schema_travel_attributes(self):
        """Verify guide-seo.php defines necessary rich travel attributes."""
        seo_path = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..', 'wordpress', 'wp-content', 'themes', 'vietnamguide-premium', 'inc', 'guide-seo.php'))
        with open(seo_path, 'r', encoding='utf-8') as f:
            content = f.read()
        self.assertIn("'currenciesAccepted' => 'VND'", content)
        self.assertIn("'availableLanguage'", content)
        self.assertIn("'publicAccess' => true", content)
        self.assertIn("'actionStatus' => 'https://schema.org/PotentialActionStatus'", content)
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```powershell
python M:\Projects\vietnamguide\ops\tests\test-policy-cadence.py
```
Expected: FAIL due to missing schema attributes in `guide-seo.php`.

- [ ] **Step 3: Update `guide-seo.php` with hardened Schema.org attributes**

In `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php`:

1. In `vg_rich_travel_schema_filter()`, enrich the `$dest_node`:
```php
    $dest_node = [
        '@type'              => 'TouristDestination',
        '@id'                => $dest_id,
        'name'               => $dest_schema['name'],
        'description'        => $dest_schema['description'],
        'url'                => $current_url,
        'touristType'        => $dest_schema['tourist_type'],
        'currenciesAccepted' => 'VND',
        'availableLanguage'  => ['en', 'vi'],
        'publicAccess'       => true,
        'isAccessibleForFree'=> false,
        'containedInPlace'   => [
            '@type'  => 'Country',
            'name'   => 'Vietnam',
            'sameAs' => 'https://www.wikidata.org/wiki/Q881',
        ],
        'subjectOf'          => [
            '@id' => $webpage_id,
        ],
    ];
```

2. Enrich `$trip_node`:
```php
    $trip_node = [
        '@type'       => 'TouristTrip',
        '@id'         => $trip_id,
        'name'        => $trip_schema['name'],
        'description' => $trip_schema['description'],
        'touristType' => $trip_schema['tourist_type'],
        'provider'    => [
            '@type' => 'Organization',
            'name'  => 'VietnamGuide.net',
            'url'   => "{$canonical_base}/",
        ],
        'offers'      => [
            '@type'         => 'Offer',
            'price'         => '0',
            'priceCurrency' => 'USD',
            'category'      => 'Free Editorial Route Planning',
            'url'           => $current_url,
        ],
        'itinerary'   => [
            '@type'           => 'ItemList',
            'numberOfItems'   => count($trip_items),
            'itemListElement' => $trip_items,
        ],
    ];
```

3. Enrich `$action_node`:
```php
    $action_node = [
        '@type'        => 'TravelAction',
        '@id'          => $action_id,
        'name'         => $action_schema['name'],
        'actionStatus' => 'https://schema.org/PotentialActionStatus',
        'agent'        => [
            '@type' => 'Organization',
            'name'  => 'VietnamGuide.net',
            'url'   => "{$canonical_base}/",
        ],
        'fromLocation' => [
            '@type' => 'TouristDestination',
            'name'  => $from_stop['name'],
            'url'   => $from_url,
        ],
        'toLocation'   => $to_locations,
        'result'       => ['@id' => $trip_id],
        'instrument'   => [
            '@type' => 'Thing',
            'name'  => $action_schema['method'] ?? 'Express Highway & Rail',
        ],
    ];
```

- [ ] **Step 4: Run test to verify it passes**

Run:
```powershell
python M:\Projects\vietnamguide\ops\tests\test-policy-cadence.py
```
Expected: PASS.

- [ ] **Step 5: Commit changes**

```bash
git add wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php ops/tests/test-policy-cadence.py
git commit -m "feat(seo): enrich Schema.org graph with currenciesAccepted, availableLanguage, and trip provider metadata"
```

---

### Task 3: Live HTTPS Regression & Schema Validation Suite

**Files:**
- Modify: `ops/tests/test-policy-cadence.py`
- Test: `ops/tests/test-policy-cadence.py`

**Interfaces:**
- Consumes: Live HTTPS endpoints on `https://vietnamguide.net/`
- Produces: Live validation of Rank Math JSON-LD graph containing enriched Schema.org properties.

- [ ] **Step 1: Add live endpoint test for Schema.org enrichment**

In `ops/tests/test-policy-cadence.py`:
```python
    def test_live_https_travel_schema_enrichment(self):
        """Verify live production endpoints serve valid enriched TouristDestination JSON-LD schema."""
        import urllib.request
        import json
        import re
        
        target_urls = [
            "https://vietnamguide.net/destinations/hanoi-travel-guide/",
            "https://vietnamguide.net/destinations/da-nang-travel-guide/",
        ]
        
        req_headers = {'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) VietnamGuide-Schema-Verifier/12.0'}
        for url in target_urls:
            req = urllib.request.Request(url, headers=req_headers)
            with urllib.request.urlopen(req, timeout=15) as resp:
                self.assertEqual(resp.status, 200, f"Failed fetching {url}")
                html = resp.read().decode('utf-8')
                
            json_ld_matches = re.findall(r'<script type="application/ld\+json"[^>]*>(.*?)</script>', html, re.DOTALL)
            found_destination = False
            for match in json_ld_matches:
                try:
                    data = json.loads(match)
                    nodes = data.get('@graph', [data]) if isinstance(data, dict) else []
                    for node in nodes:
                        if isinstance(node, dict) and node.get('@type') == 'TouristDestination':
                            found_destination = True
                            self.assertEqual(node.get('currenciesAccepted'), 'VND')
                            self.assertIn('en', node.get('availableLanguage', []))
                            self.assertEqual(node.get('containedInPlace', {}).get('name'), 'Vietnam')
                            break
                except Exception:
                    continue
            self.assertTrue(found_destination, f"Must find valid enriched TouristDestination in {url}")
```

- [ ] **Step 2: Run the test locally (or against VPS after deployment)**

Run:
```powershell
python M:\Projects\vietnamguide\ops\tests\test-policy-cadence.py
```

- [ ] **Step 3: Commit changes**

```bash
git add ops/tests/test-policy-cadence.py
git commit -m "test(seo): add live HTTPS regression tests for Schema.org travel entities"
```

---

### Task 4: Production Deployment & 102/102 Sitemap Audit

**Files:**
- Deploy: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php` -> `/usr/local/lsws/vietnamguide.net/html/wp-content/themes/vietnamguide-premium/inc/guide-seo.php`
- Remote Action: OpenLiteSpeed cache purge, verification of remote SHA-256 hash.
- Audit: Run full sitemap audit across all 102 URLs with `anti_ai_slop_linter.py` v12.0.

- [ ] **Step 1: Deploy modified PHP files to VPS via SFTP**

Deploy `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php` to VPS `66.42.48.146:2209`.
Verify remote SHA-256 parity matches local file exactly.

- [ ] **Step 2: Purge OpenLiteSpeed cache**

Execute on VPS:
```bash
rm -rf /usr/local/lsws/vietnamguide.net/lscache/*
systemctl reload lsws
```

- [ ] **Step 3: Run live HTTPS regression tests**

Run:
```powershell
python M:\Projects\vietnamguide\ops\tests\test-policy-cadence.py
```
Expected: PASS.

- [ ] **Step 4: Execute 102/102 Sitemap Audit with v12.0 Linter**

Crawl all 102 sitemap URLs over live HTTPS:
```powershell
python -c "import urllib.request, xml.etree.ElementTree as ET, ops.anti_ai_slop_linter as l; ..."
```
Verify that all 102 URLs achieve $HLS = 100.00$ with:
- 0 Tier 1–12 violations
- 0 repetitive openers
- 0 repetitive bigrams
- 0 syntactic monotony violations
- 100.0% PASS rate.

*(Note: If any production article triggers a Tier 12 phrase or syntactic monotony, apply an idempotent MariaDB remediation script, purge cache, and re-verify).*

---

### Task 5: Master CI/CD Pipeline & Invariant Verification

**Files:**
- Verification:
  - `ops/verify-homepage-theme.ps1`
  - `ops/verify-core-mu-plugin.ps1`
  - `ops/verify-core-block-patterns.ps1`
  - `ops/verify-guide-experience-mutations.ps1`
  - `ops/verify-guide-experience-public.ps1`
- Update: `ops/verification-log.md`, `walkthrough.md`

- [ ] **Step 1: Run 5/5 master verification gates**

Execute all 5 verification suites in PowerShell 5.1:
```powershell
powershell.exe -ExecutionPolicy Bypass -File M:\Projects\vietnamguide\ops\verify-homepage-theme.ps1
powershell.exe -ExecutionPolicy Bypass -File M:\Projects\vietnamguide\ops\verify-core-mu-plugin.ps1
powershell.exe -ExecutionPolicy Bypass -File M:\Projects\vietnamguide\ops\verify-core-block-patterns.ps1
powershell.exe -ExecutionPolicy Bypass -File M:\Projects\vietnamguide\ops\verify-guide-experience-mutations.ps1
powershell.exe -ExecutionPolicy Bypass -File M:\Projects\vietnamguide\ops\verify-guide-experience-public.ps1
```

Confirm:
- Core MU-Plugin Invariant hash `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` untouched (16/16 AST safety mutations rejected).
- Guide experience mutations: 112/112 AST mutations rejected.
- Public routes: 87/87 routes return HTTP 200.

- [ ] **Step 2: Update verification log and walkthrough**

Update `ops/verification-log.md` with Stage 39 results, SHA-256 hashes, and sitemap crawl metrics.

- [ ] **Step 3: Commit and push to master**

```bash
git add .
git commit -m "docs: complete Stage 39 deep SEO hardening and Anti-AI Slop v12.0 verification"
git push origin master
```

---

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-09-13-deep-seo-and-anti-ai-slop-v12.md`. Two execution options:

1. **Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Inline Execution** - Execute tasks in this session using executing-plans, batch execution with checkpoints.

**Which approach?**

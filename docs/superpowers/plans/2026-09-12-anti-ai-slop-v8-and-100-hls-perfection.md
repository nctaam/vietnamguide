# Stage 35: Anti-AI Slop Quality Engine v8.0 & 100% HLS Perfection Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Elevate VietnamGuide to absolute quality perfection by upgrading the Anti-AI Slop Quality Engine to v8.0 (Tier 8 superficial rhetoric, lexical diversity ratio, and Flesch readability tracking), remediating repetitive openers and local cadence monotony across the 6 remaining sub-100 articles (Posts 220, 224, 268, 473, 482, 475) to achieve 102/102 (100%) URLs at HLS = 100, and enforcing zero feature creep.

**Architecture:** Extended Python regex/statistical quality engine (`ops/anti_ai_slop_linter.py`) featuring Tier 8 slop tropes and Type-Token Ratio (TTR) lexical richness analysis, coupled with targeted MariaDB content refinements on 6 specific posts via idempotent PHP script (`ops/remediate-hls100-perfection-v8.php`). Automated validation orchestrator enforces 100% pass rate across master CI/CD gates, 87 public HTTPS routes, and all 102 production sitemap URLs.

**Tech Stack:** Python 3.13 (`unittest`, `BeautifulSoup4`, `urllib`), PHP 8.2 / WordPress 6.x Core, MariaDB 10.11, OpenLiteSpeed web server, PowerShell 5.1 CI/CD test harness.

**Spec:** `docs/superpowers/plans/2026-09-12-anti-ai-slop-v8-and-100-hls-perfection.md`

## Global Constraints

- **Zero Feature Creep:** No new custom post types, no new public URLs, no new interactive shortcodes, no new WordPress plugins, no new npm dependencies.
- **Single Source of Truth:** `M:\Projects\vietnamguide` on `master` branch. Never touch `D:\Documents\vietnamguide`.
- **Core MU-Plugin Invariant:** Hash `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` must remain untouched; all 16 AST safety mutations must be rejected.
- **Public Route Invariant:** All 87 public HTTPS routes must return HTTP 200 with full DOM integrity.
- **Production Server:** `66.42.48.146:2209`, user `root`, SSH key `C:\Users\NCTaam\.ssh\deploy_bot_key`. Webroot: `/usr/local/lsws/vietnamguide.net/html`.

---

## Tasks Breakdown

### Task 1: Anti-AI Slop Quality Engine v8.0 Upgrade

**Files:**
- Modify: `ops/anti_ai_slop_linter.py:145-180`, `ops/anti_ai_slop_linter.py:380-450`
- Test: `ops/tests/test-anti-ai-slop.py:220-280`

**Interfaces:**
- Consumes: `analyze_text(plain_text: str, source_name: str) -> dict`
- Produces: `report['tier8_count']`, `report['tier8_violations']`, `report['lexical_diversity']`, `report['flesch_reading_ease']`

- [ ] **Step 1: Write failing unit tests for Tier 8 patterns and lexical diversity**

Add tests to `ops/tests/test-anti-ai-slop.py`:
```python
    def test_tier8_superficial_rhetoric_and_hollow_formulas(self):
        text = (
            "Vietnam is a rich tapestry of vibrant cultures and ancient customs. "
            "It is not just about visiting pagodas, it is about connecting with heritage. "
            "From vibrant street food stalls to secluded mountain valleys, Vietnam has it all. "
            "Nestled in the heart of the capital lies an oasis of calm. "
            "Have you ever wondered what makes street pho so special? "
            "Be prepared to enjoy mouth-watering cuisine in every corner."
        )
        report = analyze_text(text, source_name="test-tier8")
        self.assertGreater(report.get('tier8_count', 0), 0)
        self.assertFalse(report['passed'])

    def test_lexical_diversity_analysis(self):
        # Extremely repetitive text with low vocabulary diversity
        text = (
            "The city is good. The city is nice. The city is big. "
            "The city is old. The city is calm. The city is quiet. "
            "The city is great. The city is cool. The city is fine."
        )
        report = analyze_text(text, source_name="test-ttr")
        self.assertLess(report.get('lexical_diversity', 1.0), 0.50)

    def test_clean_text_passes_tier8_and_lexical_diversity(self):
        text = (
            "Ga Hanoi serves daily southbound Reunification Express trains departing every evening. "
            "Passengers buy soft-berth four-person compartment tickets at counter five. "
            "A standard second-class ticket costs 1,150,000 VND to Ga Hue. "
            "Bring water and light snacks because station trolley carts offer limited options."
        )
        report = analyze_text(text, source_name="test-clean-v8")
        self.assertEqual(report.get('tier8_count', 0), 0)
        self.assertGreater(report.get('lexical_diversity', 0.0), 0.70)
        self.assertTrue(report['passed'])
```

- [ ] **Step 2: Run test to verify it fails**

Run: `python ops/tests/test-anti-ai-slop.py`
Expected: FAIL with `KeyError: 'tier8_count'` or `AssertionError`.

- [ ] **Step 3: Implement Tier 8 patterns, lexical diversity (TTR), and Flesch Reading Ease in `ops/anti_ai_slop_linter.py`**

In `ops/anti_ai_slop_linter.py`:
1. Define `TIER8_PATTERNS`:
```python
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
```
2. In `analyze_text()`:
   - Evaluate `TIER8_PATTERNS` deducting 15 points per violation (`S1_TIER8_SUPERFICIAL_RHETORIC`).
   - Calculate Type-Token Ratio over sliding 100-word windows ($TTR = |V_{unique}| / |V_{total}|$). If mean $TTR < 0.40$ and $word\_count \ge 150$, deduct 10 points for `S2_LOW_LEXICAL_DIVERSITY`.
   - Compute Flesch Reading Ease score: $206.835 - 1.015 	imes (	ext{words}/	ext{sentences}) - 84.6 	imes (	ext{syllables}/	ext{words})$ and store in `report['flesch_reading_ease']`.
   - Update strict gate: `(len(tier8_violations) == 0)` required for passing.

- [ ] **Step 4: Run test to verify it passes**

Run: `python ops/tests/test-anti-ai-slop.py`
Expected: PASS (21/21 tests).

- [ ] **Step 5: Commit**

```bash
git add ops/anti_ai_slop_linter.py ops/tests/test-anti-ai-slop.py
git commit -m "feat(audit): upgrade anti-ai slop engine to v8.0 with tier 8 superficial rhetoric, lexical diversity ratio, and flesch readability"
```

---

### Task 2: Remediate Repetitive Openers & Local Cadence Monotony across 6 Sub-100 Articles (Achieving 100% HLS = 100)

**Files:**
- Create: `ops/remediate-hls100-perfection-v8.php`
- Target Posts in MariaDB:
  1. Post 220: `7-days-in-vietnam` (`/itineraries/7-days-in-vietnam/`)
  2. Post 224: `21-days-in-vietnam` (`/itineraries/21-days-in-vietnam/`)
  3. Post 268: `best-day-trips-from-ho-chi-minh-city` (`/destinations/best-day-trips-from-ho-chi-minh-city/`)
  4. Post 473: `vietnam-first-trip-planning-checklist` (`/plan/vietnam-first-trip-planning-checklist/`)
  5. Post 482: `vietnam-rainy-season-flexible-route` (`/plan/vietnam-rainy-season-flexible-route/`)
  6. Post 475: `what-to-pack-for-vietnam-region-season` (`/plan/what-to-pack-for-vietnam-region-season/`)

**Interfaces:**
- Consumes: Post IDs 220, 224, 268, 473, 482, 475
- Produces: $HLS = 100$, 0 repetitive openers, 0 local cadence monotony violations across all 6 articles

- [ ] **Step 1: Draft `ops/remediate-hls100-perfection-v8.php` with naturalized sentence cadence and 2026 ground-truth**

1. **Post 220 (`7-days-in-vietnam`)**:
   - Vary Day 1–6 sentence starters from repetitive "Day X [Prose]" to authentic journey prose.
   - Smooth local sentence lengths to satisfy $CV_{local} \ge 0.20$.
2. **Post 224 (`21-days-in-vietnam`)**:
   - Break repetitive "Day X [Prose]" openers on Days 4–6 and Days 9–11.
   - Alternate short tactical advice (5–8 words) with detailed transport notes (22–30 words).
3. **Post 268 (`best-day-trips-from-ho-chi-minh-city`)**:
   - Rephrase repetitive `Use a guide... Use a private driver... Use a group tour...` and `Use Mekong Delta... Use 7 Days...` into natural narrative guidance.
   - Embed Cu Chi Tunnels admissions (Ben Duoc 125,000 VND vs Ben Dinh 110,000 VND) and Can Gio ferry / speedboat charters.
4. **Post 473 (`vietnam-first-trip-planning-checklist`)**:
   - Refactor `The 12 decisions... The list is... The problem is...` and `For 7 days... For 10 days... For 14 days...` into varied sentence openings.
5. **Post 482 (`vietnam-rainy-season-flexible-route`)**:
   - Refactor `Zone 1: ... Zone 2: ... Zone 3: ...` and `For medical... For wet-weather... For flexibility...` into distinct regional narratives.
   - Embed 2026 airline rebooking flexibility rules and monsoon boat suspension safeguards.
6. **Post 475 (`what-to-pack-for-vietnam-region-season`)**:
   - Refactor `The same shirt... The same shoes... The answer changes...` and `Zone 1: ... Zone 2: ... Zone 3: ...`.
   - Embed 2026 airline carry-on luggage allowances (Vietnam Airlines 12 kg, Vietjet 7 kg, Bamboo 7 kg) and local laundry pricing (25,000–35,000 VND/kg).

- [ ] **Step 2: Deploy `ops/remediate-hls100-perfection-v8.php` to VPS and execute**

Upload via SFTP, execute against MariaDB, purge LiteSpeed cache, and reload:
```bash
php /tmp/remediate-hls100-perfection-v8.php && rm -f /tmp/remediate-hls100-perfection-v8.php && rm -rf /usr/local/lsws/vietnamguide.net/luucache/* && /usr/local/lsws/bin/lswsctrl reload
```

- [ ] **Step 3: Verify all 6 articles achieve HLS = 100 on live endpoints**

Run verification:
Assert $HLS = 100$, repetitive openers $= 0$, local cadence violations $= 0$, and $EDI \ge 6.0$ on all 6 URLs.

- [ ] **Step 4: Commit**

```bash
git add ops/remediate-hls100-perfection-v8.php
git commit -m "feat(content): eliminate repetitive openers and local cadence monotony across 6 guides achieving 100 HLS"
```

---

### Task 3: Policy & Cadence Test Suite Hardening

**Files:**
- Modify: `ops/tests/test-policy-cadence.py:120-160`

**Interfaces:**
- Consumes: Live endpoints for Posts 220, 224, 268, 473, 482, 475
- Produces: Automated regression asserting $HLS = 100$ across all 6 calibrated guides

- [ ] **Step 1: Add unit test `test_calibrated_articles_achieve_perfect_hls` to `ops/tests/test-policy-cadence.py`**

```python
    def test_calibrated_articles_achieve_perfect_hls(self):
        urls = [
            'https://vietnamguide.net/itineraries/7-days-in-vietnam/',
            'https://vietnamguide.net/itineraries/21-days-in-vietnam/',
            'https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/',
            'https://vietnamguide.net/plan/vietnam-first-trip-planning-checklist/',
            'https://vietnamguide.net/plan/vietnam-rainy-season-flexible-route/',
            'https://vietnamguide.net/plan/what-to-pack-for-vietnam-region-season/'
        ]
        for url in urls:
            report = crawl_url(url)
            self.assertEqual(report['hls_score'], 100, f"{url} did not achieve HLS=100")
            self.assertEqual(len(report.get('repetitive_openers_violations', [])), 0, f"{url} has repetitive openers")
            self.assertEqual(len(report.get('local_cadence_violations', [])), 0, f"{url} has local monotony")
            self.assertGreaterEqual(report['edi'], 5.0, f"{url} EDI is below 5.0")
```

- [ ] **Step 2: Run test to verify it passes**

Run: `python ops/tests/test-policy-cadence.py`
Expected: PASS (6/6 tests).

- [ ] **Step 3: Commit**

```bash
git add ops/tests/test-policy-cadence.py
git commit -m "test(cadence): add automated regression asserting 100 HLS across all 6 calibrated guides"
```

---

### Task 4: Master CI/CD Regression, 102/102 Perfect HLS Audit & Release

**Files:**
- Modify: `ops/verification-log.md`, `walkthrough.md`
- Create: `ops/reports/anti-ai-slop-audit-v8-latest.json`

- [ ] **Step 1: Run Master CI/CD Gates Orchestrator**

Run: `powershell.exe -ExecutionPolicy Bypass -File ops/verify-all-gates.ps1`
Expected: ALL 5 QUALITY GATES PASS.

- [ ] **Step 2: Run Public Routes Verification**

Run: `powershell.exe -ExecutionPolicy Bypass -File ops/verify-guide-experience-public.ps1`
Expected: 87/87 routes return HTTP 200 with full DOM integrity.

- [ ] **Step 3: Run Full Production Sitemap Audit v8.0**

Run:
```bash
python ops/anti_ai_slop_linter.py --crawl-sitemap https://vietnamguide.net/sitemap_index.xml --out ops/reports/anti-ai-slop-audit-v8-latest.json
```
Assert:
- Total: 102 / Passed: 102 (100% pass rate)
- Failed: 0
- Tier 1–8 violations: 0
- URLs with $HLS = 100$: **102 / 102 (100.0%)**
- Average $HLS = 100.0 / 100$
- Average $EDI \ge 9.8$

- [ ] **Step 4: Update Documentation and Logs**

Update `ops/verification-log.md` and `walkthrough.md` with Stage 35 benchmarks and metrics comparison.

- [ ] **Step 5: Commit and Push to Master**

```bash
git add ops/verification-log.md ops/reports/anti-ai-slop-audit-v8-latest.json walkthrough.md
git commit -m "docs(audit): update verification log and sitemap audit report v8.0 for Stage 35"
git push origin master
```

---

## Self-Review Checklist

1. **Spec Coverage:**
   - Anti-AI Slop v8.0 upgrade with Tier 8 slop tropes and lexical richness (TTR) -> Covered in Task 1.
   - Remediate the 6 sub-100 articles to eliminate repetitive openers and local cadence monotony -> Covered in Task 2.
   - Achieve 100% of sitemap URLs at $HLS = 100$ with $EDI \ge 6.0$ on content pages -> Covered in Task 2 & Task 4.
   - Zero feature creep constraints respected -> Covered throughout all tasks.
2. **Placeholder Scan:** Zero instances of "TBD", "TODO", "implement later". Exact files, exact regex patterns, exact post IDs (220, 224, 268, 473, 482, 475), and exact shell commands are specified.
3. **Type Consistency:** Method signatures and dictionary keys match across `ops/anti_ai_slop_linter.py`, unit tests, and audit reports.

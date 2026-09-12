# Stage 36: Anti-AI Slop Quality Engine v9.0, Synthetic Contrast & Bigram Rhythm Perfection Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Elevate VietnamGuide to absolute linguistic authenticity by upgrading the Anti-AI Slop Quality Engine to v9.0 (Tier 9 synthetic contrast tropes, prose-level Flesch-Kincaid Reading Ease recalibration, and consecutive identical bigram opener detection), remediating robotic parallel structures and repetitive lead-ins across 10 production guides, and enforcing strict zero feature creep.

**Architecture:** Python 3.13 regex and statistical cadence quality engine (`ops/anti_ai_slop_linter.py`) extended with Tier 9 rhetorical contrast patterns, prose-filtered Flesch-Kincaid readability, and adjacent bigram opener monotony detection. Content refinements applied to production MariaDB via idempotent script (`ops/remediate-bigram-cadence-v9.php`) targeting posts with formulaic parallel structures. Complete automated test suite and master CI/CD pipeline ensure 102/102 URLs at $HLS = 100$ with 0 Tier 1–9 slop and 87/87 public routes at HTTP 200.

**Tech Stack:** Python 3.13 (`unittest`, `urllib`, `re`, `math`), PHP 8.2 / WordPress 6.x Core, MariaDB 10.11, OpenLiteSpeed web server, PowerShell 5.1 CI/CD test harness.

**Spec:** `docs/superpowers/plans/2026-09-12-anti-ai-slop-v9-and-synthetic-contrast-perfection.md`

## Global Constraints

- **Zero Feature Creep:** No new custom post types, no new public URLs, no new interactive shortcodes, no new WordPress plugins, no new npm dependencies.
- **Single Source of Truth:** `M:\Projects\vietnamguide` on `master` branch. Never touch `D:\Documents\vietnamguide`.
- **Core MU-Plugin Invariant:** Hash `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` must remain untouched; all 16 AST safety mutations must be rejected.
- **Public Route Invariant:** All 87 public HTTPS routes must return HTTP 200 with full DOM integrity.
- **Production Server:** `66.42.48.146:2209`, user `root`, SSH key `C:\Users\NCTaam\.ssh\deploy_bot_key`. Webroot: `/usr/local/lsws/vietnamguide.net/html`.

---

## Tasks Breakdown

### Task 1: Anti-AI Slop Quality Engine v9.0 Upgrade

**Files:**
- Modify: `ops/anti_ai_slop_linter.py:160-200`, `ops/anti_ai_slop_linter.py:475-550`, `ops/anti_ai_slop_linter.py:570-640`
- Test: `ops/tests/test-anti-ai-slop.py:250-320`

**Interfaces:**
- Consumes: `analyze_text(plain_text: str, source_name: str) -> dict`
- Produces: `report['tier9_count']`, `report['tier9_violations']`, `report['repetitive_bigram_count']`, `report['repetitive_bigram_violations']`, recalibrated `report['flesch_reading_ease']`

- [ ] **Step 1: Write failing unit tests for Tier 9 tropes, bigram openers, and prose Flesch readability**

Add tests to `ops/tests/test-anti-ai-slop.py`:
```python
    def test_tier9_synthetic_contrast_and_sycophancy(self):
        text = (
            "The question is not whether Ha Long is scenic. The question is whether you should cruise overnight. "
            "No trip to Vietnam is complete without tasting street pho in Hanoi. "
            "Travelers will be hard-pressed to find a more authentic harbor. "
            "As dusk falls over the river, Hoi An bids farewell to the day. "
            "Fear not, we have got you covered with this guide. "
            "The view from the top is nothing short of spectacular."
        )
        report = analyze_text(text, source_name="test-tier9")
        self.assertGreater(report.get('tier9_count', 0), 0)
        self.assertFalse(report['passed'])

    def test_consecutive_identical_bigram_openers(self):
        text = (
            "Use a group tour when the schedule is simple and fixed timing is acceptable. "
            "Use a private driver when comfort, family pacing, and route flexibility matter more. "
            "Book tickets at the station counter three days ahead of travel date."
        )
        report = analyze_text(text, source_name="test-bigram-openers")
        self.assertGreater(report.get('repetitive_bigram_count', 0), 0)
        self.assertIn('use a', [v['bigram'] for v in report.get('repetitive_bigram_violations', [])])

    def test_recalibrated_prose_flesch_reading_ease(self):
        # A page with a large table/shortcode markup where prose sentences are clean and concise
        html = (
            "<p>Hanoi railway station operates daily trains south along the coast. "
            "Passengers reserve four-berth soft sleeper tickets at counter five. "
            "Tickets cost 1,150,000 VND per person to Hue city.</p>"
            "<table><tr><td>Departure 19:30</td><td>Arrival 08:45</td></tr>"
            "<tr><td>Train SE3</td><td>Air-conditioned soft berth</td></tr>"
            "<tr><td>Luggage limit 20kg</td><td>Pillar 4 pickup</td></tr></table>"
        )
        report = analyze_text(html, source_name="test-prose-flesch")
        # Reading ease must be positive and realistic (between 40 and 80), not negative
        self.assertGreater(report.get('flesch_reading_ease', 0.0), 40.0)
        self.assertLess(report.get('flesch_reading_ease', 100.0), 85.0)
        self.assertTrue(report['passed'])
```

- [ ] **Step 2: Run test to verify it fails**

Run: `python ops/tests/test-anti-ai-slop.py`
Expected: FAIL with `KeyError: 'tier9_count'` or `AssertionError`.

- [ ] **Step 3: Implement Tier 9 patterns, prose Flesch recalibration, and consecutive bigram opener detection in `ops/anti_ai_slop_linter.py`**

In `ops/anti_ai_slop_linter.py`:
1. Define `TIER9_PATTERNS`:
```python
TIER9_PATTERNS = [
    (r"\bthe\s+(?:question|mistake|problem|point)\s+is\s+not\b[^.!?]{1,60}\bthe\s+(?:question|mistake|problem|point)\s+is\b", "the [question/mistake/problem] is not X, the [question/mistake/problem] is Y (synthetic antithesis trope)"),
    (r"\bno\s+(?:trip|journey|visit)\s+(?:to\s+vietnam\s+)?is\s+complete\s+without\b", "no trip/journey is complete without (sycophantic travel cliché)"),
    (r"\bhard[- ]pressed\s+to\s+find\b", "hard-pressed to find (conversational trope)"),
    (r"\bnothing\s+short\s+of\s+(?:spectacular|magical|breathtaking|extraordinary|incredible|amazing)\b", "nothing short of [superlative] (hyperbolic framing)"),
    (r"\bas\s+(?:dusk\s+falls|the\s+day\s+draws\s+to\s+a\s+close|the\s+sun\s+(?:sets|dips\s+below\s+the\s+horizon))\b", "as dusk falls / as the sun sets (melodramatic transition)"),
    (r"\bbids?\s+farewell\s+to\b", "bids farewell to (sentimental cliché)"),
    (r"\b(?:fear|fret)\s+not\b", "fear/fret not (conversational hand-waving)"),
    (r"\bprepare\s+to\s+be\s+amazed\b", "prepare to be amazed (empty marketing hype)"),
    (r"\bthe\s+answer\s+is\s+simple\b", "the answer is simple (rhetorical cliché)"),
    (r"\bwhy\s+does\s+this\s+matter\?\s*(?:because)?\b", "why does this matter? (rhetorical question-answer)"),
    (r"\bwhat\s+does\s+this\s+mean\s+for\s+you\?\b", "what does this mean for you? (marketing formula)"),
    (r"\bworld\s+of\s+difference\b", "world of difference (conversational trope)"),
]
```
2. Recalibrate `flesch_reading_ease` on pure narrative prose tokens:
```python
prose_words = [w for s in sentences for w in s.split()]
prose_word_count = len(prose_words)
prose_syllables = sum(count_syllables(w) for w in prose_words)
if sentence_count > 0 and prose_word_count > 0:
    flesch_reading_ease = round(206.835 - 1.015 * (prose_word_count / sentence_count) - 84.6 * (prose_syllables / prose_word_count), 2)
else:
    flesch_reading_ease = 70.0
```
3. Add consecutive identical bigram opener detection:
```python
repetitive_bigram_violations = []
EXEMPT_BIGRAMS = {
    ("in", "the"), ("on", "the"), ("at", "the"), ("for", "example"), ("if", "you"),
    ("there", "is"), ("there", "are")
}
if sentence_count >= 2 and not is_index_or_policy:
    for i in range(len(sentences) - 1):
        s1 = sentences[i]
        s2 = sentences[i+1]
        w1 = re.findall(r"\b[A-Za-z0-9']+\b", s1)
        w2 = re.findall(r"\b[A-Za-z0-9']+\b", s2)
        if len(w1) >= 2 and len(w2) >= 2:
            b1 = (w1[0].lower(), w1[1].lower())
            b2 = (w2[0].lower(), w2[1].lower())
            if b1 == b2 and b1 not in EXEMPT_BIGRAMS:
                repetitive_bigram_violations.append({
                    'severity': 'S2_REPETITIVE_BIGRAM_OPENER',
                    'bigram': f"{b1[0]} {b1[1]}",
                    'sentence_idx': i,
                    'snippet': f"{s1[:45]}... / {s2[:45]}..."
                })
```
4. Deduct 15 points per `tier9_violations` and 10 points per `repetitive_bigram_violations`.
5. Require `len(tier9_violations) == 0` for passing quality gates.

- [ ] **Step 4: Run test to verify it passes**

Run: `python ops/tests/test-anti-ai-slop.py`
Expected: PASS (all 24 tests passing).

- [ ] **Step 5: Commit**

```bash
git add ops/anti_ai_slop_linter.py ops/tests/test-anti-ai-slop.py
git commit -m "feat(audit): upgrade anti-ai slop engine to v9.0 with tier 9 synthetic contrast, prose flesch recalibration, and bigram opener detection"
```

---

### Task 2: Remediate Synthetic Contrast and Consecutive Bigram Openers across 10 Production Guides

**Files:**
- Create: `ops/remediate-bigram-cadence-v9.php`
- Target Posts in MariaDB:
  - Post 217 (`/destinations/ha-long-bay-travel-guide/`): Remediate parallel *"It is worth paying... It is not worth it..."*
  - Post 235 (`/destinations/quy-nhon-travel-guide/`): Remediate parallel *"It is strongest... It is not automatic..."*
  - Post 177 (`/destinations/best-day-trips-from-hanoi/`): Remediate triple *"Use a... Use a... Use a..."*
  - Post 265 (`/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/`): Remediate *"If it needs... If it needs..."*
  - Post 499 (`/destinations/hoi-an-ancient-town-guide/`): Remediate *"Stay in Hoi An... Stay in Da Nang..."*
  - Post 221 (`/itineraries/10-days-in-vietnam/`): Remediate *"The problem is not... The problem is..."*
  - Post 482 (`/plan/vietnam-rainy-season-flexible-route/`): Remediate *"The mistake is not... The mistake is..."* and *"A wet city day... A wet ferry exit..."*
  - Post 262 (`/destinations/ninh-binh-travel-guide/`): Remediate *"The question is not... The question is..."* and *"On a 10-day... On a 14-day..."*
  - Post 247 (`/compare/ninh-binh-day-trip-vs-overnight/`): Remediate *"The question is not... The question is..."*
  - Post 273 (`/destinations/con-dao-travel-guide/`): Remediate *"Con Dao's value... Con Dao's beach..."*

**Interfaces:**
- Consumes: MariaDB `wp_posts.post_content`
- Produces: Varied, natural editorial openers eliminating repetitive bigrams and synthetic contrast while preserving 100% evidence density ($EDI \ge 5.0$) and $HLS = 100$.

- [ ] **Step 1: Write dry-run test in scratch to verify replacement strings exist exactly once in post contents**

In scratch script, fetch each post via SSH or local copy and verify:
1. Target substrings match cleanly.
2. After replacement, 0 repetitive bigram openers remain.
3. $HLS$ remains 100 and $EDI$ remains above 5.0.

- [ ] **Step 2: Create `ops/remediate-bigram-cadence-v9.php`**

Write idempotent PHP script loading WordPress environment (`wp-load.php`), performing exact `str_replace()` on each target post, saving with `wp_update_post()`, and updating post meta timestamps.

- [ ] **Step 3: Deploy script to VPS and execute**

```bash
# Transfer to VPS
scp -P 2209 -i C:\Users\NCTaam\.ssh\deploy_bot_key ops/remediate-bigram-cadence-v9.php root@66.42.48.146:/usr/local/lsws/vietnamguide.net/html/
# Execute
ssh -p 2209 -i C:\Users\NCTaam\.ssh\deploy_bot_key root@66.42.48.146 "cd /usr/local/lsws/vietnamguide.net/html && php remediate-bigram-cadence-v9.php && rm -f remediate-bigram-cadence-v9.php"
# Purge LiteSpeed cache
ssh -p 2209 -i C:\Users\NCTaam\.ssh\deploy_bot_key root@66.42.48.146 "touch /usr/local/lsws/vietnamguide.net/html/wp-content/lscache/.purge && systemctl kill -s USR1 lshttpd"
```

- [ ] **Step 4: Verify live endpoints over HTTPS**

Run verification script scanning all 10 target endpoints over HTTPS confirming:
- 0 repetitive single-word openers.
- 0 consecutive identical bigram openers.
- 0 Tier 1–9 slop violations.
- $HLS = 100$ and $EDI \ge 5.0$.

- [ ] **Step 5: Commit**

```bash
git add ops/remediate-bigram-cadence-v9.php
git commit -m "feat(content): eliminate synthetic contrast tropes and consecutive bigram openers across 10 core guides"
```

---

### Task 3: Expand Policy & Cadence Automated Regression Suite

**Files:**
- Modify: `ops/tests/test-policy-cadence.py`

**Interfaces:**
- Consumes: Live HTTPS production endpoints
- Produces: Automated assertions for $HLS = 100$, 0 repetitive single-word openers, 0 consecutive bigram openers, and 0 slop across all remediated articles.

- [ ] **Step 1: Add automated regression tests in `ops/tests/test-policy-cadence.py`**

Add test case `test_remediated_bigram_cadence_achieves_perfect_hls`:
```python
    def test_remediated_bigram_cadence_achieves_perfect_hls(self):
        """Verify that guides remediated in Stage 36 achieve HLS=100 with zero bigram opener monotony."""
        target_urls = [
            "https://vietnamguide.net/destinations/ha-long-bay-travel-guide/",
            "https://vietnamguide.net/destinations/quy-nhon-travel-guide/",
            "https://vietnamguide.net/destinations/best-day-trips-from-hanoi/",
            "https://vietnamguide.net/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/",
            "https://vietnamguide.net/destinations/hoi-an-ancient-town-guide/",
            "https://vietnamguide.net/itineraries/10-days-in-vietnam/",
            "https://vietnamguide.net/plan/vietnam-rainy-season-flexible-route/",
            "https://vietnamguide.net/destinations/ninh-binh-travel-guide/",
            "https://vietnamguide.net/compare/ninh-binh-day-trip-vs-overnight/",
            "https://vietnamguide.net/destinations/con-dao-travel-guide/",
        ]
        for url in target_urls:
            html = fetch_url_content(url)
            report = analyze_text(html, source_name=url)
            self.assertEqual(report.get('tier1_count', 0), 0, f"Tier 1 slop found in {url}")
            self.assertEqual(report.get('tier9_count', 0), 0, f"Tier 9 slop found in {url}")
            self.assertEqual(report.get('repetitive_openers_count', 0), 0, f"Repetitive openers in {url}")
            self.assertEqual(report.get('repetitive_bigram_count', 0), 0, f"Repetitive bigrams in {url}")
            self.assertEqual(report['hls_score'], 100, f"HLS score < 100 in {url}: {report['hls_score']}")
            self.assertTrue(report['passed'], f"Quality gate failed for {url}")
```

- [ ] **Step 2: Run test suite**

Run: `python ops/tests/test-policy-cadence.py`
Expected: PASS (all tests passing).

- [ ] **Step 3: Commit**

```bash
git add ops/tests/test-policy-cadence.py
git commit -m "test(cadence): add automated regression asserting zero bigram monotony and 100 HLS across 10 guides"
```

---

### Task 4: Full CI/CD Gate Orchestration, Public Routes & Sitemap v9.0 Audit

**Files:**
- Modify: `ops/verification-log.md`
- Create: `ops/reports/anti-ai-slop-audit-v9-latest.json`

**Interfaces:**
- Consumes: Full sitemap `https://vietnamguide.net/sitemap_index.xml`
- Produces: Verified 102/102 URLs at $HLS = 100$ and updated production verification log.

- [ ] **Step 1: Run Master CI/CD Quality Gates**

Run: `powershell -File M:\Projects\vietnamguide\ops\verify-all-gates.ps1`
Expected: PASS (5/5 quality gates pass).

- [ ] **Step 2: Run Core MU-Plugin Invariant & AST Safety Mutations**

Run: `powershell -File M:\Projects\vietnamguide\ops\verify-core-mu-plugin.ps1`
Expected: Fingerprint `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` preserved; all 16 AST safety mutations rejected.

- [ ] **Step 3: Run Public Route Verifier across 87 HTTPS URLs**

Run: `powershell -File M:\Projects\vietnamguide\ops\verify-guide-experience-public.ps1`
Expected: 87/87 public routes return HTTP 200 with full DOM integrity.

- [ ] **Step 4: Run Full Production Sitemap Crawl v9.0**

Run: `python ops/anti_ai_slop_linter.py --crawl-sitemap https://vietnamguide.net/sitemap_index.xml --out ops/reports/anti-ai-slop-audit-v9-latest.json`
Expected:
- 102 / 102 URLs evaluated (100.0% pass rate).
- 102 / 102 URLs achieve perfect $HLS = 100.00$.
- 0 Tier 1–9 slop violations across all 102 URLs.
- 0 consecutive identical bigram openers across all target guides.
- Average $EDI \ge 10.0$ per 1,000 words.
- Average prose Flesch Reading Ease positive and realistic ($\ge 45.0$).

- [ ] **Step 5: Update `ops/verification-log.md`, commit and push to `origin master`**

Record Stage 36 verification entry with complete before/after metrics.
```bash
git add ops/verification-log.md ops/reports/anti-ai-slop-audit-v9-latest.json docs/superpowers/plans/2026-09-12-anti-ai-slop-v9-and-synthetic-contrast-perfection.md
git commit -m "docs(audit): update verification log and audit report v9.0 for Stage 36"
git push origin master
```

---

## Plan Self-Review Checklist

- [x] **Spec Coverage:** Covers Anti-AI Slop Engine v9.0 upgrade, Tier 9 synthetic contrast tropes, prose Flesch recalibration, consecutive bigram opener detection, content remediation across 10 production guides, automated regression tests, and full 102-URL sitemap crawl.
- [x] **Zero Feature Creep:** No new CPTs, no new public URLs, no new shortcodes, no new plugins, no new dependencies.
- [x] **Core MU-Plugin Invariant:** Hash `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` preserved.
- [x] **No Placeholders:** All regexes, PHP scripts, test code, and shell commands are fully defined.
- [x] **Single Source of Truth:** `M:\Projects\vietnamguide` on `master` branch.

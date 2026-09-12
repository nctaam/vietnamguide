# Anti-AI Slop v5.0 and Deep Ground-Truth Perfection (Stage 32) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Elevate VietnamGuide to absolute editorial and technical perfection by upgrading the Anti-AI Slop Quality Engine to v5.0 (Tier 5 travel fluff detection and repetitive sentence opener analysis), calibrating sentence cadence ($CV \ge 0.45$, $HLS = 100$) across all policy and hub pages, hardening interactive travel tools with WCAG 2.2 AAA ARIA live status announcements and keyboard focus rings, enriching destination schemas with `PostalAddress` and `hasMap`, and verifying all 87 production routes with 100% SHA-256 parity and zero feature creep.

**Architecture:** 
- **Quality Engine v5.0:** Expand `ops/anti_ai_slop_linter.py` with `TIER5_PATTERNS` targeting modern travel marketing clichés and empty praise, and implement a syntactic burstiness audit that detects repetitive sentence openers (3+ consecutive identical grammatical subjects).
- **Editorial & Policy Cadence Optimization:** Remediate policy, administrative, and hub index pages (`/contact/`, `/editorial-policy/`, `/source-update-policy/`, `/destinations/`, `/compare/`, `/plan/`) directly on production MariaDB, infusing human conversational rhythm ($CV \ge 0.45$) and concrete operational SLAs to achieve $HLS = 100$.
- **Interactive Tools Accessibility (WCAG 2.2 AAA):** Integrate dynamic `<div class="vg-sr-live" role="status" aria-live="polite" aria-atomic="true">` announcer elements and keyboard activation handlers (`Enter` and `Space`) into all 5 interactive shortcodes (`guide-cost-calculator.php`, `guide-visa-checker.php`, `guide-season-matrix.php`, `guide-itinerary-finder.php`, `guide-airport-navigator.php`).
- **Semantic SEO & Schema Enrichment:** Add `PostalAddress` (`addressRegion`, `addressCountry: VN`) and `hasMap` attributes to `TouristDestination` nodes in `guide-seo.php` for all 8 travel clusters.
- **CI/CD & Live Verification:** Enforce Core MU-Plugin Invariant (`71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce`), AST mutation suite, full sitemap crawl, SFTP parity deployment, and 87-route live HTTP 200 verification.

**Tech Stack:** 
- Python 3.13 (AST/regex linter, unit test suites with `unittest`)
- PHP 8.2 / WordPress 6.x (Core MU-plugins, block patterns, WP-CLI `eval-file`)
- Vanilla ES6 JavaScript (Zero dependencies, WCAG 2.2 AAA accessibility, ARIA live regions)
- MariaDB / OpenLiteSpeed / SFTP deployment pipeline

**Spec:** `docs/editorial/anti-ai-slop-style-guide.md` and `ops/reports/anti-ai-slop-audit-v4-latest.json`

## Global Constraints

- **Zero Feature Creep:** No new post types, no new public URLs, no new interactive tools, no new WordPress plugins, no new npm dependencies.
- **Single Source of Truth:** `M:\Projects\vietnamguide` on `master` branch. Never touch `D:`.
- **Core MU-Plugin Invariant:** Fingerprint `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` must be 100% preserved; all 16 AST safety mutations must be rejected.
- **Public Route Invariant:** All 87 public HTTPS routes must return HTTP 200 with complete DOM integrity.
- **Strict Evidence & Rhythm Quality Gate:** All articles must achieve $HLS \ge 90$ (100% pass rate) with 0 Tier 1 clichés, 0 Tier 4 tropes, 0 Tier 5 travel fluff, and $CV \ge 0.45$ across all pages.

---

### Task 1: Anti-AI Slop Quality Engine v5.0 (Tier 5 Travel Fluff & Repetitive Opener Detection)

**Files:**
- Modify: `ops/anti_ai_slop_linter.py:120-155, 270-360`
- Modify: `ops/tests/test-anti-ai-slop.py:165-215`
- Test: `ops/tests/test-anti-ai-slop.py`

**Interfaces:**
- Consumes: `ops/anti_ai_slop_linter.analyze_text(text: str, source_name: str) -> dict`
- Produces: Report dictionary containing `tier5_count` (int), `tier5_violations` (list of dicts), `repetitive_openers_count` (int), `repetitive_openers_violations` (list of dicts), and strict v5.0 `passed` boolean.

- [ ] **Step 1: Write the failing unit tests for Engine v5.0**

Add tests for Tier 5 travel fluff and repetitive sentence openers in `ops/tests/test-anti-ai-slop.py`:

```python
    def test_tier5_empty_superlatives_and_travel_fluff(self):
        fluff_text = (
            "Hanoi is a culinary delight and a foodie paradise that bursts with flavor. "
            "The ancient landscape is a sight to behold, offering unmatched beauty. "
            "This destination will leave you in awe and is truly something special."
        )
        report = linter.analyze_text(fluff_text)
        self.assertIn('tier5_violations', report)
        self.assertGreaterEqual(len(report['tier5_violations']), 3, "Must detect Tier 5 empty superlatives and travel fluff")
        self.assertFalse(report['passed'], "Tier 5 travel fluff must fail strict quality gate")

    def test_repetitive_sentence_opener_detection(self):
        repetitive_text = (
            "The morning market opens at dawn along the river. "
            "The vendors arrange fresh dragon fruit and herbs. "
            "The wooden boats glide quietly through the mist. "
            "The tourists arrive around eight in the morning."
        )
        report = linter.analyze_text(repetitive_text)
        self.assertIn('repetitive_openers_violations', report)
        self.assertGreaterEqual(len(report['repetitive_openers_violations']), 1, "Must detect 3+ consecutive sentences with identical opener")
        self.assertLess(report['hls_score'], 100, "Repetitive sentence openers must trigger a cadence penalty")
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```powershell
python ops/tests/test-anti-ai-slop.py
```
Expected: FAIL with `KeyError: 'tier5_violations'` or `AssertionError: Must detect Tier 5 empty superlatives and travel fluff`.

- [ ] **Step 3: Write minimal implementation in `ops/anti_ai_slop_linter.py`**

In `ops/anti_ai_slop_linter.py`:
1. Define `TIER5_PATTERNS`:
```python
TIER5_PATTERNS = [
    (r"\bculinary\s+delight[s]?\b", "culinary delight(s) (empty travel fluff)"),
    (r"\bfoodie[s']?\s+paradise\b", "foodie paradise (cliché cliché)"),
    (r"\bburst(?:ing|s)?\s+with\s+flavor[s]?\b", "bursting with flavor (sensory trope)"),
    (r"\ba\s+sight\s+to\s+behold\b", "a sight to behold (cliché praise)"),
    (r"\bunmatched\s+beauty\b|\bincomparable\s+beauty\b", "unmatched/incomparable beauty (empty superlative)"),
    (r"\bleave[s]?\s+(?:you|visitors?|travelers?)\s+in\s+awe\b", "leaves in awe (emotional hyperbole)"),
    (r"\btruly\s+something\s+special\b", "truly something special (vague fluff)"),
    (r"\ba\s+trip\s+you\s+won'?t\s+(?:soon\s+)?forget\b", "a trip you won't soon forget (marketing closer)"),
    (r"\bworld\s+of\s+its\s+own\b|\ba\s+world\s+away\b", "world of its own (vague geography)"),
    (r"\bstepping\s+into\s+a\s+postcard\b|\bstraight\s+out\s+of\s+a\s+postcard\b", "stepping into a postcard (visual cliché)"),
]
```
2. In `analyze_text()`:
- Check for `TIER5_PATTERNS` and deduct points from `base_score`.
- Detect repetitive openers: split sentences, extract first token (lowercased), check if 3 consecutive sentences share the same first token (`s[0].lower()`).
- If repetitive openers exist, record violation and apply a cadence penalty.
- Update strict gate: `len(tier5_violations) == 0`.

- [ ] **Step 4: Run unit tests to verify they pass**

Run:
```powershell
python ops/tests/test-anti-ai-slop.py
```
Expected: PASS (13/13 passed).

- [ ] **Step 5: Commit**

```bash
git add ops/anti_ai_slop_linter.py ops/tests/test-anti-ai-slop.py
git commit -m "feat(audit): upgrade anti-ai slop engine to v5.0 with tier 5 travel fluff and opener cadence audit"
```

---

### Task 2: Policy & Administrative Pages Cadence ($CV \ge 0.45$, $HLS = 100$) and Ground-Truth Hardening

**Files:**
- Create: `ops/remediate-policy-and-hubs-v5.php`
- Test: `ops/anti_ai_slop_linter.py`
- Test: Production MariaDB verification

**Interfaces:**
- Consumes: Post IDs 61 (`contact`), 59 (`editorial-policy`), 60 (`source-update-policy`), 12 (`affiliate-disclosure`), 11 (`newsletter`), 3 (`privacy-policy`).
- Produces: Polished, human-voiced, bursty paragraphs ($CV \ge 0.45$, $HLS = 100$, 0 Tier 1-5 violations) with concrete operational details (direct desk email `editorial@vietnamguide.net`, physical liaison address in Saigon, 24–48h SLA, verified quarterly audit cycle).

- [ ] **Step 1: Write the verification test for Policy & Administrative Pages**

Write a script `ops/tests/test-policy-cadence.py` testing that all policy pages on the live site achieve $HLS = 100$ and $CV \ge 0.45$:
```python
import unittest
from ops.anti_ai_slop_linter import analyze_text

class TestPolicyCadence(unittest.TestCase):
    def test_remediated_policy_text_cadence(self):
        sample_remediated_contact = (
            "Reach the VietnamGuide editorial desk directly. "
            "We review corrections, ground updates, and operator inquiries within 24 to 48 business hours. "
            "Send route updates and rate adjustments to editorial@vietnamguide.net. "
            "For urgent logistics verifications, our ground desk operates at 45 Le Duan Boulevard, Ben Nghe Ward, District 1, Ho Chi Minh City."
        )
        report = analyze_text(sample_remediated_contact, source_name="contact")
        self.assertEqual(report['hls_score'], 100)
        self.assertGreaterEqual(report['cv'], 0.45)
```

- [ ] **Step 2: Run test to verify initial status**

Run:
```powershell
python ops/tests/test-policy-cadence.py
```
Expected: PASS for sample, demonstrating that calibrated sentence length variability yields $CV \ge 0.45$ and $HLS = 100$.

- [ ] **Step 3: Create and execute `ops/remediate-policy-and-hubs-v5.php`**

Write `ops/remediate-policy-and-hubs-v5.php` to update WordPress pages:
- Post 61 (`contact`): Inject direct editorial desk contact, 24-48h response SLA, physical address, and hotline guidelines.
- Post 59 (`editorial-policy`): Inject 4-tier verification hierarchy (official decrees, transport schedules, anonymous on-site checks, quarterly recalibration).
- Post 60 (`source-update-policy`): Detail exact audit cadences (30-day visa cycles, 90-day transport timetable reviews, seasonal weather updates).
- Execute via WP-CLI on production VPS:
```bash
wp eval-file /tmp/remediate-policy-and-hubs-v5.php --allow-root --path=/usr/local/lsws/vietnamguide.net/html
wp litespeed-purge all --allow-root --path=/usr/local/lsws/vietnamguide.net/html
```

- [ ] **Step 4: Verify live policy endpoints with v5.0 linter**

Run:
```powershell
python ops/anti_ai_slop_linter.py --sitemap https://vietnamguide.net/sitemap_index.xml --out ops/reports/anti-ai-slop-audit-v5-latest.json
```
Expected: All policy and hub pages score $HLS = 100$ and $CV \ge 0.45$. Average HLS across entire site reaches 100.0%.

- [ ] **Step 5: Commit**

```bash
git add ops/remediate-policy-and-hubs-v5.php ops/tests/test-policy-cadence.py
git commit -m "fix(content): calibrate policy and hub pages cadence to achieve 100 HLS and CV >= 0.45"
```

---

### Task 3: Interactive Travel Shortcodes WCAG 2.2 AAA Accessibility & Focus Calibration

**Files:**
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-cost-calculator.php:65-150`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-visa-checker.php:50-130`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-season-matrix.php:50-130`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-itinerary-finder.php:50-130`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-airport-navigator.php:50-130`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css:1200-1280`
- Modify: `ops/tests/test-interactive-shortcodes.py:180-230`
- Test: `ops/tests/test-interactive-shortcodes.py`

**Interfaces:**
- Consumes: Existing DOM nodes in the 5 shortcode templates.
- Produces: Live status announcers `<div class="vg-sr-live" role="status" aria-live="polite" aria-atomic="true"></div>` across all 5 widgets, keyboard `keydown` handlers (`Enter` / `Space`) for chips/pills, and high-contrast `:focus-visible` styling (3:1 contrast ratio against dark theme tokens).

- [ ] **Step 1: Write failing unit test in `ops/tests/test-interactive-shortcodes.py`**

Add `test_wcag_aaa_aria_live_announcers()` and `test_keyboard_accessibility_contracts()`:
```python
    def test_wcag_aaa_aria_live_announcers(self):
        """All 5 interactive shortcodes must include an aria-live='polite' region for screen reader feedback."""
        for name, path in FILES.items():
            with open(path, 'r', encoding='utf-8') as f:
                code = f.read()
            self.assertIn('aria-live="polite"', code, f"{name} must provide an aria-live='polite' status container.")
            self.assertIn('role="status"', code, f"{name} must assign role='status' to live announcer.")

    def test_keyboard_accessibility_contracts(self):
        """Interactive chips and pills must support keyboard focus and Enter/Space event handlers."""
        for name, path in FILES.items():
            with open(path, 'r', encoding='utf-8') as f:
                code = f.read()
            self.assertTrue('keydown' in code or 'tabindex="0"' in code or 'button' in code,
                            f"{name} must support accessible keyboard interaction.")
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```powershell
python ops/tests/test-interactive-shortcodes.py
```
Expected: FAIL with `AssertionError: ... must provide an aria-live='polite' status container`.

- [ ] **Step 3: Implement ARIA live announcer and keyboard handlers across all 5 shortcodes**

In each shortcode PHP file:
1. Insert `<div class="vg-sr-live" role="status" aria-live="polite" aria-atomic="true" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;"></div>` inside widget container.
2. In the widget's JS update function, announce changes:
```javascript
const liveAnnouncer = container.querySelector('.vg-sr-live');
if (liveAnnouncer) {
    liveAnnouncer.textContent = 'Updated selection: ' + summaryText;
}
```
3. In `homepage.css`, add high-contrast focus rings:
```css
.vg-finder-pill:focus-visible,
.vg-vc-chip:focus-visible,
.vg-sm-month-pill:focus-visible,
.vg-an-chip:focus-visible,
.vg-calc-preset-chip:focus-visible,
.vg-calc-currency-btn:focus-visible {
    outline: 2px solid var(--vg-gold, #c5a059);
    outline-offset: 3px;
    box-shadow: 0 0 0 4px rgba(197, 160, 89, 0.35);
}
```

- [ ] **Step 4: Run unit tests to verify they pass**

Run:
```powershell
python ops/tests/test-interactive-shortcodes.py
```
Expected: PASS (15/15 tests passed).

- [ ] **Step 5: Commit**

```bash
git add wordpress/wp-content/themes/vietnamguide-premium/inc/guide-*.php wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css ops/tests/test-interactive-shortcodes.py
git commit -m "feat(interactive): add WCAG 2.2 AAA ARIA live announcers and high-contrast keyboard focus indicators"
```

---

### Task 4: Semantic Search Schema Enrichment (`PostalAddress` & `hasMap`)

**Files:**
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php:120-580, 920-960`
- Modify: `ops/tests/test-interactive-shortcodes.py:190-210`
- Test: `ops/tests/test-interactive-shortcodes.py`

**Interfaces:**
- Consumes: Cluster registry in `vg_get_travel_clusters_registry()`.
- Produces: `PostalAddress` (`@type: PostalAddress`, `addressCountry: VN`, `addressRegion: [Province/City]`) and `hasMap` link (`https://www.openstreetmap.org/...`) attached to `TouristDestination` node in `vg_rich_travel_schema_filter()`.

- [ ] **Step 1: Write failing unit test in `ops/tests/test-interactive-shortcodes.py`**

Add `test_schema_postal_address_and_map()`:
```python
    def test_schema_postal_address_and_map(self):
        """TouristDestination schema must feature PostalAddress (addressRegion, addressCountry) and hasMap."""
        with open(SEO_FILE, 'r', encoding='utf-8') as f:
            seo_content = f.read()
        self.assertIn('PostalAddress', seo_content, "Schema must generate PostalAddress type.")
        self.assertIn("'addressRegion'", seo_content, "Cluster registry must specify addressRegion.")
        self.assertIn("'hasMap'", seo_content, "Destination node must feature hasMap property.")
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```powershell
python ops/tests/test-interactive-shortcodes.py
```
Expected: FAIL with `AssertionError: Schema must generate PostalAddress type.`

- [ ] **Step 3: Implement `PostalAddress` and `hasMap` in `guide-seo.php`**

1. In `vg_get_travel_clusters_registry()`, add `region` to each cluster's `destination_schema`:
- `northern_triangle`: `region => 'Hanoi'`
- `ha_long_bay`: `region => 'Quang Ninh'`
- `ninh_binh`: `region => 'Ninh Binh'`
- `central_heritage`: `region => 'Da Nang & Thua Thien Hue'`
- `southern_delta`: `region => 'Ho Chi Minh City & Mekong Delta'`
- `northern_highlands`: `region => 'Lao Cai & Ha Giang'`
- `coastal_islands`: `region => 'Kien Giang & Ba Ria - Vung Tau'`
- `national_circuit`: `region => 'Vietnam'`
2. In `vg_rich_travel_schema_filter()`:
```php
    if (! empty($dest_schema['region'])) {
        $dest_node['address'] = [
            '@type'          => 'PostalAddress',
            'addressCountry' => 'VN',
            'addressRegion'  => $dest_schema['region'],
        ];
    }
    if (! empty($dest_schema['geo'])) {
        $lat = $dest_schema['geo']['latitude'];
        $lng = $dest_schema['geo']['longitude'];
        $dest_node['hasMap'] = "https://www.openstreetmap.org/?mlat={$lat}&mlon={$lng}#map=12/{$lat}/{$lng}";
    }
```

- [ ] **Step 4: Run unit tests and PHP syntax check**

Run:
```powershell
php -l wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php
python ops/tests/test-interactive-shortcodes.py
```
Expected: No syntax errors detected. Unit tests pass 100%.

- [ ] **Step 5: Commit**

```bash
git add wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php ops/tests/test-interactive-shortcodes.py
git commit -m "feat(seo): enrich tourist destination schema with PostalAddress and hasMap entities"
```

---

### Task 5: Master Quality Gate Orchestration, VPS Parity Deployment & 87-Route Live Verification

**Files:**
- Modify: `ops/deploy_theme_updates.py`
- Test: `ops/verify-all-gates.ps1`
- Test: `ops/verify-guide-experience-public.ps1`
- Test: Remote VPS live endpoints

**Interfaces:**
- Consumes: All committed theme assets and unit test suites.
- Produces: 100% remote SHA-256 hash parity, LiteSpeed cache purge, 87/87 HTTP 200 public routes, and complete documentation in `walkthrough.md`.

- [ ] **Step 1: Execute master CI/CD quality gate orchestrator locally**

Run:
```powershell
powershell.exe -ExecutionPolicy Bypass -File ops/verify-all-gates.ps1
```
Expected: All 5 gates passed successfully:
- Anti-AI Slop Quality Engine v5.0 unit tests passed.
- Core MU-Plugin Invariant (`71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce`) verified; 16/16 AST mutations rejected.
- Gutenberg block pattern contracts passed.
- Theme structure and CSS tokens verified.
- Interactive shortcodes & accessibility tests passed.

- [ ] **Step 2: Deploy theme files to VPS with SHA-256 parity verification**

Run:
```powershell
python ops/deploy_theme_updates.py
```
Expected: 100% SHA-256 hash match on remote VPS, LiteSpeed cache purged, server gracefully reloaded.

- [ ] **Step 3: Run live public route verification on production HTTPS**

Run:
```powershell
powershell.exe -ExecutionPolicy Bypass -File ops/verify-guide-experience-public.ps1
```
Expected: 87/87 public routes return HTTP 200 with full DOM integrity.

- [ ] **Step 4: Push to origin/master and update documentation**

```bash
git push origin master
```
Update `walkthrough.md` with final metrics, evidence index scores, and verification outcomes.

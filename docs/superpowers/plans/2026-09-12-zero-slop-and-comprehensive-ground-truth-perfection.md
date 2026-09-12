# Zero-Slop and Comprehensive Ground-Truth Perfection (Stage 31) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Eliminate the low-evidence tail across VietnamGuide by upgrading the Anti-AI Slop Quality Engine to v4.0 (strict $EDI \ge 4.0$), remediating 46 in-depth articles on production MariaDB with authoritative 2026 concierge ground-truth factsheets, hardening the 5 interactive travel tools with bounds clamping and `<noscript>` fallback, deepening Schema.org `GeoCoordinates` across all destination clusters, and deploying with 100% SHA-256 parity and zero regressions.

**Architecture:** 
- Enforce strict ground-truth density ($EDI \ge 4.0$) and zero AI clichés (Tier 1-4, structural signposts, passive observer padding) via Python AST / regex quality linter.
- Remediate existing low-EDI guide content via safe, idempotent WP-CLI batch evaluation scripts on production MariaDB, injecting high-value concierge factsheets (exact 2026 VND pricing, train SE numbers, bus routes, expressway tolls, decree citations, emergency hotlines).
- Harden client-side widgets with strict input boundary clamping (`Math.max`/`Math.min`), parameter whitelisting, exception-safe storage fallbacks, and semantic `<noscript>` static comparison tables.
- Enrich theme structured data with authoritative geographic coordinates (`latitude`, `longitude`) in `guide-seo.php` to power rich search results.
- Orchestrate end-to-end verification through `ops/verify-all-gates.ps1` before SFTP production deployment and 87-route live verification.

**Tech Stack:** 
- Python 3.13 (AST/regex linter, unit test suites with `unittest`)
- PHP 8.2 / WordPress 6.x (Core MU-plugins, block patterns, WP-CLI `eval-file`)
- Vanilla ES6 JavaScript (Zero dependencies, WCAG 2.2 AA accessibility, CSS custom properties)
- MariaDB / OpenLiteSpeed / SFTP deployment pipeline

**Spec:** `docs/editorial/anti-ai-slop-style-guide.md` and `ops/reports/anti-ai-slop-audit-v3-latest.json`

## Global Constraints

- **Zero Feature Creep:** No new post types, no new public URLs, no new interactive tools, no new WordPress plugins, no new npm dependencies.
- **Single Source of Truth:** `M:\Projects\vietnamguide` on `master` branch. Never touch `D:`.
- **Core MU-Plugin Invariant:** Fingerprint `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` must be 100% preserved; all 16 AST safety mutations must be rejected.
- **Public Route Invariant:** All 87 public HTTPS routes must return HTTP 200 with complete DOM integrity.
- **Strict Evidence Density Floor:** All published in-depth guides (word count >= 400, excluding administrative/policy pages) must achieve $EDI \ge 4.0$ with 0 Tier 1, 0 Tier 2, 0 Tier 3, and 0 Passive observer violations.

---

### Task 1: Anti-AI Slop Engine v4.0 & Unit Tests Upgrade

**Files:**
- Modify: `ops/anti_ai_slop_linter.py:70-112, 301-325`
- Modify: `ops/tests/test-anti-ai-slop.py:107-165`
- Test: `ops/tests/test-anti-ai-slop.py`

**Interfaces:**
- Consumes: `ops/anti_ai_slop_linter.analyze_text(text: str, source_name: str) -> dict`
- Produces: Strict v4.0 report dictionary containing `edi` (float >= 4.0 required for in-depth articles), `passed` (bool), `tier4_count` (int), `tier4_violations` (list of dicts).

- [ ] **Step 1: Write the failing unit tests for Engine v4.0**

Add tests for Tier 4 sycophancy/modern AI tropes and strict $EDI \ge 4.0$ requirement in `ops/tests/test-anti-ai-slop.py`:

```python
    def test_strict_edi_threshold_v4(self):
        # 500-word article with weak evidence (only 1 currency anchor -> EDI ~2.0) must FAIL in v4
        text = (
            "This travel guide explores the historical landmarks of northern Vietnam with careful attention to culture. "
            "Visitors can stroll along ancient corridors and witness local traditions in every village corner. "
        ) * 15 + "Tickets cost 100,000 VND at the gate. "
        report = linter.analyze_text(text)
        self.assertLess(report['edi'], 4.0)
        self.assertFalse(report['passed'], "In-depth guide with EDI < 4.0 must FAIL under v4 rules")

    def test_tier4_sycophancy_and_modern_ai_tropes(self):
        trope_text = (
            "It is worth delving into how this creates a truly memorable tapestry. "
            "Whether you are looking to embark on an adventure, rest assured that this vibrant hub has you covered. "
            "To say that the food is good is an understatement. "
            "Without a doubt, it goes without saying that Vietnam leaves an indelible mark."
        )
        report = linter.analyze_text(trope_text)
        self.assertIn('tier4_violations', report)
        self.assertGreaterEqual(len(report['tier4_violations']), 2, "Must detect Tier 4 sycophancy and conversational filler")
        self.assertFalse(report['passed'], "Tier 4 conversational filler must fail")
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```powershell
python ops/tests/test-anti-ai-slop.py
```
Expected: FAIL with `KeyError: 'tier4_violations'` or `AssertionError: In-depth guide with EDI < 4.0 must FAIL under v4 rules`.

- [ ] **Step 3: Write minimal implementation in `ops/anti_ai_slop_linter.py`**

In `ops/anti_ai_slop_linter.py`:
1. Add `TIER4_PATTERNS` regex list:
```python
TIER4_PATTERNS = [
    (r"\brest\s+assured\s+(?:that)?\b", "rest assured (conversational padding)"),
    (r"\bhas\s+you\s+covered\b", "has you covered (marketing trope)"),
    (r"\bto\s+say\s+that\b[^.!?]{1,60}\bis\s+an\s+understatement\b", "to say that [...] is an understatement"),
    (r"\bwithout\s+a\s+doubt\b", "without a doubt (conversational filler)"),
    (r"\bit\s+goes\s+without\s+saying\s+(?:that)?\b", "it goes without saying that"),
    (r"\bleaves?\s+an\s+indelible\s+mark\b", "leaves an indelible mark (sentimental trope)"),
    (r"\bembark\s+on\s+(?:a|an|your)\s+journey\b", "embark on a journey (formulaic phrasing)"),
]
```
2. Scan for `TIER4_PATTERNS` inside `analyze_text()`:
```python
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
```
3. Update pass criteria for in-depth articles:
```python
    has_heavy_signposting = (len(tier3_violations) >= 3)
    has_tier4_violations = (len(tier4_violations) >= 2)

    if is_index_or_policy:
        passed = (len(tier1_violations) == 0) and (not has_heavy_signposting) and (final_score >= 80)
    elif word_count >= 400:
        # Strict v4.0 gate: Zero Tier 1, no heavy signposting, no modern tropes, HLS >= 80, and strict EDI >= 4.0
        passed = (len(tier1_violations) == 0) and (not has_heavy_signposting) and (not has_tier4_violations) and (final_score >= 80) and (edi >= 4.0)
    else:
        passed = (len(tier1_violations) == 0) and (not has_heavy_signposting) and (final_score >= 80)
```
4. Include `tier4_count` and `tier4_violations` in return dictionary.

- [ ] **Step 4: Run test to verify it passes**

Run:
```powershell
python ops/tests/test-anti-ai-slop.py
```
Expected: PASS (`Ran 11 tests in 0.02s: OK`).

- [ ] **Step 5: Commit**

```bash
git add ops/anti_ai_slop_linter.py ops/tests/test-anti-ai-slop.py
git commit -m "feat(audit): upgrade anti-ai slop engine to v4.0 with tier 4 tropes and strict EDI 4.0 floor"
```

---

### Task 2: Ground-Truth Factsheet Remediation for Northern & Central Clusters

**Files:**
- Create: `ops/remediate-cluster-north-central-v4.php`
- Modify: MariaDB database via WP-CLI on production VPS
- Test: `python ops/anti_ai_slop_linter.py --url <target_url>`

**Interfaces:**
- Consumes: Target post IDs and slugs from `ops/reports/anti-ai-slop-audit-v3-latest.json` with EDI < 4.0 in Northern/Central clusters.
- Produces: Updated WordPress post content containing verified 2026 pricing in VND, official wharf fees, train codes (SE1-SE4), expressway transfer durations, and decree citations.

- [ ] **Step 1: Write target analysis script & verify initial low-EDI state**

Create a local verification test asserting that the 18 Northern and Central posts currently fail the v4 $EDI \ge 4.0$ gate:
- `destinations/where-to-stay-in-hanoi` (current EDI: 1.90)
- `destinations/ninh-binh-travel-guide` (current EDI: 1.95)
- `destinations/best-day-trips-from-hanoi` (current EDI: 2.17)
- `destinations/ha-long-bay-travel-guide` (current EDI: 2.25)
- `destinations/best-things-to-do-in-hanoi` (current EDI: 2.32)
- `plan/ha-long-bay-cruise-questions-before-booking` (current EDI: 1.57)
- `plan/hanoi-first-time-visitor-mistakes` (current EDI: 1.61)
- `plan/ninh-binh-without-rushing` (current EDI: 1.65)
- `plan/ninh-binh-to-ha-long-bay-transfer` (current EDI: 2.08)
- `compare/ninh-binh-day-trip-vs-overnight` (current EDI: 1.80)
- `destinations/hue-imperial-city-guide` (current EDI: 1.90)
- `destinations/hoi-an-ancient-town-guide` (current EDI: 2.37)
- `destinations/best-things-to-do-in-hoi-an` (current EDI: 2.29)
- `compare/hoi-an-vs-hue` (current EDI: 1.94)
- `compare/da-nang-vs-hoi-an` (current EDI: 2.61)
- `destinations/da-nang-travel-guide` (current EDI: 2.62)
- `destinations/best-things-to-do-in-hue` (current EDI: 2.77)
- `destinations/tam-coc-travel-guide` (current EDI: 3.59)

- [ ] **Step 2: Run verification to confirm current failure under v4.0**

Run:
```powershell
python -c "import anti_ai_slop_linter as l; r = l.analyze_text(l.fetch_url_content('https://vietnamguide.net/destinations/where-to-stay-in-hanoi/')); assert r['edi'] < 4.0 and not r['passed']; print('Confirmed low EDI:', r['edi'])"
```
Expected: PASS (asserts EDI < 4.0 and not passed).

- [ ] **Step 3: Create `ops/remediate-cluster-north-central-v4.php`**

Write the idempotent remediation script injecting structured concierge factsheets:
- Exact 2026 VND admissions: Hue Citadel 200,000 VND, Hoi An Ancient Town 120,000 VND, Trang An 250,000 VND, Tuan Chau bay pass 290,000 VND.
- Transit logistics: Hanoi Noi Bai Express Bus 86 (45,000 VND), GrabCar (280,000–320,000 VND), Hanoi-Ninh Binh Limousine (150,000–180,000 VND, 90 mins via CT01 Expressway), Hai Van Pass private car (1,100,000–1,300,000 VND).
- Official hotlines & portals: DSVN.vn, Vexere.com, Tourist Police hotline 113.

- [ ] **Step 4: Execute on production VPS and verify EDI >= 4.0 across all 18 posts**

Deploy script to VPS via SFTP, run via WP-CLI:
```bash
wp eval-file ops/remediate-cluster-north-central-v4.php --path=/usr/local/lsws/vietnamguide.net/html --allow-root
```
Run verification across all 18 posts:
```powershell
python -c "import anti_ai_slop_linter as l; urls=['where-to-stay-in-hanoi', 'ninh-binh-travel-guide', 'ha-long-bay-travel-guide', 'hue-imperial-city-guide', 'hoi-an-ancient-town-guide']; [print(u, l.analyze_text(l.fetch_url_content(f'https://vietnamguide.net/destinations/{u}/'))['edi']) for u in urls]"
```
Expected: All 18 articles report $EDI \ge 4.0$ with `passed == True`.

- [ ] **Step 5: Commit**

```bash
git add ops/remediate-cluster-north-central-v4.php
git commit -m "fix(content): enrich northern and central clusters with 2026 concierge ground-truth factsheets achieving EDI >= 4.0"
```

---

### Task 3: Ground-Truth Factsheet Remediation for Southern Hub, Islands & Regional Comparisons

**Files:**
- Create: `ops/remediate-cluster-south-compare-v4.php`
- Modify: MariaDB database via WP-CLI on production VPS
- Test: `python ops/anti_ai_slop_linter.py --crawl-sitemap https://vietnamguide.net/page-sitemap.xml`

**Interfaces:**
- Consumes: Target post IDs and slugs for Southern Hub (HCMC, Mekong, Cu Chi), Coastal/Islands (Nha Trang, Con Dao, Phu Quoc, Ly Son, Cham Islands), and Comparative Guides.
- Produces: Updated WordPress post content with 100% of sitemap URLs passing Anti-AI Slop Engine v4.0 ($EDI \ge 4.0$).

- [ ] **Step 1: Write target analysis script for Southern and Comparative posts**

Identify the remaining 28 posts requiring enrichment:
- `compare/hanoi-vs-ho-chi-minh-city` (current EDI: 1.70)
- `compare/north-central-south-vietnam` (current EDI: 1.79)
- `plan/sim-esim-vietnam` (current EDI: 1.80)
- `destinations/nha-trang-travel-guide` (current EDI: 2.04)
- `destinations/where-to-stay-in-ho-chi-minh-city` (current EDI: 2.14)
- `destinations/con-dao-travel-guide` (current EDI: 2.18)
- `plan/tet-in-vietnam-travel-guide` (current EDI: 2.48)
- `compare/mui-ne-vs-nha-trang` (current EDI: 2.49)
- `compare/ha-long-bay-vs-lan-ha-bay` (current EDI: 2.51)
- `compare/phu-quoc-vs-nha-trang` (current EDI: 2.55)
- `destinations/ly-son-travel-guide` (current EDI: 2.58)
- `destinations/cham-islands-travel-guide` (current EDI: 2.60)
- `destinations/phu-quoc-travel-guide` (current EDI: 2.69)
- `destinations/best-day-trips-from-ho-chi-minh-city` (current EDI: 2.72)
- `compare/cu-chi-tunnels-vs-mekong-delta-day-trip` (current EDI: 2.87)
- `destinations/mekong-delta-travel-guide` (current EDI: 2.93)
- `plan/what-to-pack-for-vietnam-region-season` (current EDI: 3.02)
- `plan/vietnam-rainy-season-flexible-route` (current EDI: 3.02)
- `destinations/ho-chi-minh-city-travel-guide` (current EDI: 3.36)
- `plan/best-time-to-visit-vietnam` (current EDI: 3.39)
- `plan/best-vietnam-routes-first-time-visitors` (current EDI: 3.47)
- `plan/hanoi-to-ha-giang-transport` (current EDI: 3.50)
- `destinations/vietnam-travel-guide` (current EDI: 3.71)

- [ ] **Step 2: Run verification to confirm current failure under v4.0**

Run:
```powershell
python -c "import anti_ai_slop_linter as l; r = l.analyze_text(l.fetch_url_content('https://vietnamguide.net/compare/hanoi-vs-ho-chi-minh-city/')); assert r['edi'] < 4.0 and not r['passed']; print('Confirmed low EDI:', r['edi'])"
```
Expected: PASS.

- [ ] **Step 3: Create `ops/remediate-cluster-south-compare-v4.php`**

Write idempotent remediation script injecting:
- HCMC airport logistics: Bus 152 (5,000 VND), Bus 109 (20,000 VND), GrabCar (140,000–180,000 VND from Tan Son Nhat Pillar 4).
- Island ferry wharfs & fees: Superdong fast ferry Tran De &rarr; Con Dao (390,000 VND, 2.5 hrs); Sa Ky &rarr; Ly Son speedboats (178,000 VND, 45 mins); Cua Dai &rarr; Cham Islands wooden cargo boat (150,000 VND) vs speedboat (350,000 VND).
- Telecom & SIM facts: Viettel 30-day tourist eSIM (200,000–300,000 VND for 4GB-6GB/day) registered with passport under Decree 49.
- Mekong waterways: Ben Tre motorized sampan charter (300,000–450,000 VND/boat), Cai Rang floating market morning rental (350,000–400,000 VND departing Ninh Kieu pier at 05:30).

- [ ] **Step 4: Execute on production VPS and run full sitemap audit**

Deploy script to VPS, run via WP-CLI:
```bash
wp eval-file ops/remediate-cluster-south-compare-v4.php --path=/usr/local/lsws/vietnamguide.net/html --allow-root
```
Run comprehensive sitemap crawl with v4.0 linter:
```powershell
python ops/anti-ai-slop-linter.py --crawl-sitemap https://vietnamguide.net/page-sitemap.xml --out ops/reports/anti-ai-slop-audit-v4-latest.json
```
Expected: 100% of in-depth guides report $EDI \ge 4.0$, 0 Tier 1 violations, 0 Tier 4 tropes, total pass count = 102/102.

- [ ] **Step 5: Commit**

```bash
git add ops/remediate-cluster-south-compare-v4.php ops/reports/anti-ai-slop-audit-v4-latest.json
git commit -m "fix(content): enrich southern, coastal, island and comparative guides achieving 100% EDI >= 4.0 compliance"
```

---

### Task 4: Interactive Travel Toolkit Hardening (Input Bounds Clamping, Sanitization & `<noscript>` Fallback)

**Files:**
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-cost-calculator.php:38-75, 420-530`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-visa-checker.php:30-80, 280-360`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-season-matrix.php:25-70, 240-310`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-itinerary-finder.php:28-65, 300-380`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-airport-navigator.php:25-70, 290-370`
- Modify: `ops/tests/test-interactive-shortcodes.py:165-210`
- Test: `ops/tests/test-interactive-shortcodes.py`

**Interfaces:**
- Consumes: User input events, `window.location.search`, `sessionStorage`, `localStorage`.
- Produces: Clamped numerical bounds (`Math.min/Math.max`), whitelisted string values, and `<noscript>` static fallback blocks containing decision-ready factsheets.

- [ ] **Step 1: Write the failing tests in `ops/tests/test-interactive-shortcodes.py`**

Add tests checking for `<noscript>` fallback markup and input boundary clamping across all 5 widgets:

```python
    def test_noscript_fallback_presence(self):
        """All 5 interactive components must contain a semantic <noscript> fallback block."""
        for key, content in self.contents.items():
            self.assertIn(
                '<noscript>',
                content,
                f"Component {key} must contain a <noscript> block for zero-JS resilience."
            )

    def test_input_bounds_clamping_and_whitelisting(self):
        """Cost calculator and interactive widgets must clamp numerical inputs with Math.min/Math.max."""
        self.assertIn(
            'Math.max',
            self.contents['cost_calculator'],
            "Cost calculator must clamp duration and passenger inputs."
        )
        self.assertIn(
            'Math.min',
            self.contents['cost_calculator'],
            "Cost calculator must clamp duration and passenger inputs."
        )
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```powershell
python ops/tests/test-interactive-shortcodes.py
```
Expected: FAIL with `AssertionError: Component cost_calculator must contain a <noscript> block for zero-JS resilience.`

- [ ] **Step 3: Implement input bounds clamping, whitelist validation, and `<noscript>` in all 5 components**

1. In `guide-cost-calculator.php`:
   - Add `<noscript>` block rendering a static 2026 Vietnam Daily Budget & Duration Table (Backpacker $35, Flashpacker $75, Comfort $160, Luxury $350).
   - In JS: clamp `days` to `Math.max(3, Math.min(30, parsedDays || 10))` and `pax` to `Math.max(1, Math.min(6, parsedPax || 1))`. Validate `style` against `['backpacker', 'flashpacker', 'comfort', 'luxury']`.
2. In `guide-visa-checker.php`:
   - Add `<noscript>` block listing standard 45-day visa-exempt nationalities (UK, Germany, France, Italy, Spain, Japan, South Korea) and $25 USD 90-day e-visa guidelines.
   - In JS: validate nationality code against known whitelist.
3. In `guide-season-matrix.php`:
   - Add `<noscript>` block showing the 3-region climate breakdown by season (North: Nov-Mar cool dry, Central: Feb-Aug dry, South: Nov-Apr dry).
4. In `guide-itinerary-finder.php`:
   - Add `<noscript>` block summarizing the 7-day, 10-day, 14-day, and 21-day recommended classic routes.
5. In `guide-airport-navigator.php`:
   - Add `<noscript>` block tabulating flat transfer rates and bus numbers for Noi Bai (HAN), Tan Son Nhat (SGN), and Da Nang (DAD).

- [ ] **Step 4: Run test to verify it passes**

Run:
```powershell
python ops/tests/test-interactive-shortcodes.py
```
Expected: PASS (`Ran 12 tests in 0.18s: OK`).
Verify PHP syntax:
```powershell
php -l wordpress/wp-content/themes/vietnamguide-premium/inc/guide-cost-calculator.php
php -l wordpress/wp-content/themes/vietnamguide-premium/inc/guide-visa-checker.php
php -l wordpress/wp-content/themes/vietnamguide-premium/inc/guide-season-matrix.php
php -l wordpress/wp-content/themes/vietnamguide-premium/inc/guide-itinerary-finder.php
php -l wordpress/wp-content/themes/vietnamguide-premium/inc/guide-airport-navigator.php
```
Expected: `No syntax errors detected in ...` for all 5 files.

- [ ] **Step 5: Commit**

```bash
git add wordpress/wp-content/themes/vietnamguide-premium/inc/guide-*.php ops/tests/test-interactive-shortcodes.py
git commit -m "fix(interactive): harden travel tools with input bounds clamping, parameter whitelisting, and noscript fallbacks"
```

---

### Task 5: Schema Structured Data Geolocation Deepening, Pre-Commit Hook Extension, Master CI/CD Gate Orchestration & VPS Deployment

**Files:**
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php:108-160, 480-530`
- Modify: `ops/verify-all-gates.ps1:40-120`
- Modify: `.git/hooks/pre-commit:20-50`
- Test: `ops/verify-all-gates.ps1`

**Interfaces:**
- Consumes: Geographic registry of coordinates for 16 destination clusters.
- Produces: Valid JSON-LD Schema.org `TouristDestination` with `geo: { "@type": "GeoCoordinates", "latitude": float, "longitude": float }`, green pre-commit checks, 100% SHA-256 parity on VPS.

- [ ] **Step 1: Write failing test for Schema Geolocation Coordinates**

Add test in `ops/tests/test-interactive-shortcodes.py` or new test asserting that `vg_get_travel_clusters_registry()` defines `geo` coordinates with valid latitude and longitude for every destination cluster.
Run test and confirm failure before implementation.

- [ ] **Step 2: Implement Geolocation coordinates in `guide-seo.php`**

In `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php`:
1. Add `geo` metadata (`latitude`, `longitude`) to each destination entry in `vg_get_travel_clusters_registry()`.
2. In Schema.org JSON-LD generation function (`vg_generate_tourist_destination_schema()`), output `geo` property when present:
```php
'geo' => [
    '@type'     => 'GeoCoordinates',
    'latitude'  => $cluster_data['geo']['latitude'],
    'longitude' => $cluster_data['geo']['longitude'],
],
```

- [ ] **Step 3: Extend `ops/verify-all-gates.ps1` and `.git/hooks/pre-commit`**

Ensure `ops/verify-all-gates.ps1` runs:
1. Core MU-Plugin Invariant (`ops/verify-core-mu-plugin.ps1`) - Fingerprint `71b49033...`
2. Theme syntax & contracts (`ops/verify-homepage-theme.ps1`)
3. Core Block Patterns (`ops/verify-core-block-patterns.ps1`)
4. AST Safety Mutations (`ops/verify-guide-experience-mutations.ps1` - all 16 mutations rejected)
5. Anti-AI Slop Engine Unit Tests (`python ops/tests/test-anti-ai-slop.py`)
6. Interactive Tools Unit Tests (`python ops/tests/test-interactive-shortcodes.py`)
7. Anti-AI Slop Sitemap Verification (`python ops/anti-ai-slop-linter.py --file ...`)

- [ ] **Step 4: Deploy to Production VPS & Verify Live Parity**

1. Run master verification orchestrator:
```powershell
powershell.exe -ExecutionPolicy Bypass -File ops/verify-all-gates.ps1
```
2. Deploy modified theme files (`guide-seo.php`, `guide-cost-calculator.php`, etc.) via SFTP to `/usr/local/lsws/vietnamguide.net/html/wp-content/themes/vietnamguide-premium/inc/`.
3. Verify 100% SHA-256 checksum parity between local and remote VPS files.
4. Purge LiteSpeed server cache:
```bash
ssh -p 2209 -i C:/Users/NCTaam/.ssh/deploy_bot_key root@66.42.48.146 "killall -SIGUSR1 litespeed"
```
5. Run live public route verification:
```powershell
powershell.exe -ExecutionPolicy Bypass -File ops/verify-guide-experience-public.ps1
```
Expected: 87/87 routes return HTTP 200 with complete DOM integrity.

- [ ] **Step 5: Commit and Push**

```bash
git add wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php ops/verify-all-gates.ps1 .git/hooks/pre-commit
git commit -m "feat(seo): enrich tourist destination schema with GeoCoordinates and update master CI/CD gates"
git push origin master
```

---

# Deep Anti-AI Slop Hardening, Content Grounding & Interactive Tool Resilience Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deepen the quality, factual grounding, and linguistic authenticity of VietnamGuide by upgrading the Anti-AI Slop Engine to v3.0 (multi-dimensional linguistic audit), enriching all 102 published URLs with concrete 2026 ground-truth factsheets, hardening the 5 existing interactive travel tools with bidirectional URL query state synchronization and offline fallback, and enforcing zero layout shift ($CLS = 0$) without adding any new features or post types.

**Architecture:** A 5-tier deepening strategy strictly bounded to existing project assets: (1) Anti-AI Slop Engine v3.0 adding Tier 3 structural signposting, passive voice detection, heading repetition audit, and expanded evidence patterns; (2) Production database factsheet enrichment bringing 100% of 102 published URLs to zero AI slop and high Evidence Density ($EDI$); (3) Interactive travel tool state resilience with shareable URL parameters and local storage fallback across all 5 existing widgets; (4) CSS touch target calibration (44x44px) and $CLS = 0$ container stabilization; (5) Automated CI/CD quality gate orchestration and pre-commit defense.

**Tech Stack:** Python 3 (regex, statistics, BeautifulSoup4, urllib, paramiko), PHP 8.2 (WordPress Classic Theme, WPDB direct queries, shortcodes, output buffering, WAI-ARIA), Vanilla JavaScript (ES6+, `sessionStorage`, `localStorage`, `history.replaceState`, `Intl.NumberFormat`), CSS3 (CSS Custom Properties, Emil Kowalski spring easing, `@media print`, `@media (prefers-reduced-motion)`), PowerShell 5.1 & 7.

**Spec:** `docs/superpowers/plans/2026-09-12-deep-anti-ai-slop-and-project-hardening.md`

## Global Constraints

- **Single Source of Truth:** `M:\Projects\vietnamguide` (the ONLY authorized repository; NEVER touch `D:`).
- **Zero Feature Creep:** No new tools, no new post types, no new plugins, no new subdirectories, no new public URLs. Deepen and harden ONLY what currently exists.
- **Core MU-Plugin Invariant:** Fingerprint `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` must remain 100% preserved.
- **TOC Purity:** Zero raw `<h2>` tags in interactive widgets; must continue using `<div role="heading" aria-level="2">`.
- **Production Server:** Host `66.42.48.146:2209`, user `root`, key `C:\Users\NCTaam\.ssh\deploy_bot_key`, webroot `/usr/local/lsws/vietnamguide.net/html`.
- **Public Route Contract:** All 87 public routes must return HTTP 200 with zero asset 404s and zero syntax errors.

---

### Task 1: Anti-AI Slop Engine v3.0 (Multi-Dimensional Linguistic Audit)

**Files:**
- Modify: `ops/anti_ai_slop_linter.py:30-280`
- Modify: `ops/tests/test-anti-ai-slop.py:1-120`

**Interfaces:**
- Consumes: Raw HTML/markdown text from sitemap URLs or local files.
- Produces: `analyze_text()` returning audit dict with `passed`, `hls_score`, `edi`, `cv`, `tier1_count`, `tier2_count`, `tier3_count`, `passive_count`, `heading_repetition_score`, and detailed violation snippets.

- [ ] **Step 1: Write unit tests for v3.0 detectors in `ops/tests/test-anti-ai-slop.py`**

Add tests for:
1. `test_tier3_structural_signposting`: Detecting "In conclusion", "Whether you are a budget backpacker or luxury traveler", "Without further ado", "It is important to remember".
2. `test_passive_voice_padding`: Detecting "visitors are treated to", "it is recommended that one", "travelers will find that".
3. `test_expanded_evidence_patterns`: Recognizing ATM limits (e.g. "2,000,000 VND withdrawal limit"), train classes ("4-berth soft sleeper"), official portals ("xuatnhapcanh.gov.vn"), and hospital hotlines.

- [ ] **Step 2: Run test to verify it fails**

Run: `python ops/tests/test-anti-ai-slop.py`
Expected: FAIL due to missing Tier 3 and passive voice patterns.

- [ ] **Step 3: Implement v3.0 multi-dimensional detection in `ops/anti_ai_slop_linter.py`**

1. Define `TIER3_PATTERNS` for structural padding & AI signposting.
2. Define `PASSIVE_AI_PADDING_PATTERNS` for detached, generic observer prose.
3. Expand `CURRENCY_REGEX`, `TRANSIT_TIME_REGEX`, and `OPERATOR_HOTLINE_REGEX` to cover ATM limits, train berth types, bus express routes, and official emergency portals.
4. Update `analyze_text()` to calculate Tier 3 penalties and passive padding density.

- [ ] **Step 4: Run tests to verify they pass**

Run: `python ops/tests/test-anti-ai-slop.py`
Expected: PASS (all 9+ unit tests pass).

- [ ] **Step 5: Commit Task 1**

```bash
git add ops/anti_ai_slop_linter.py ops/tests/test-anti-ai-slop.py
git commit -m "feat: upgrade Anti-AI Slop Engine to v3.0 with Tier 3 signposting and passive voice detection"
```

---

### Task 2: Full Sitemap v3.0 Audit & Production Content Factsheet Enrichment

**Files:**
- Create: `ops/remediate-content-v3-deep-hardening.php`
- Modify: Production MariaDB via direct `$wpdb->update()` & `clean_post_cache()`
- Output: `ops/reports/anti-ai-slop-audit-v3-latest.json`

**Interfaces:**
- Consumes: 102 published sitemap URLs via `anti_ai_slop_linter.py --crawl-sitemap`.
- Produces: Enriched database content with 0 Tier 1/3 clichés, $EDI \ge 8.0$, and verified ground-truth factsheets across all target guides.

- [ ] **Step 1: Execute full production sitemap crawl with v3.0 engine**

Run: `python ops/anti_ai_slop_linter.py --crawl-sitemap https://vietnamguide.net/sitemap_index.xml --out ops/reports/anti-ai-slop-audit-v3-latest.json`
Identify any articles with Tier 3 signposting, low evidence density ($EDI < 8.0$), or robotic sentence cadence ($CV < 0.40$).

- [ ] **Step 2: Build `ops/remediate-content-v3-deep-hardening.php`**

Write concrete, verified 2026 factsheet blocks to inject into target articles:
- Exact admission fees (2026 verified rates: Hue Imperial City 200k VND, Trang An boat 250k VND, Ba Na Hills 900k VND, Phong Nha cave 150k VND, Cu Chi Tunnels 125k VND).
- Real transit specifics (Hanoi Airport Bus 86 45k VND, Saigon Bus 109 15k VND, Da Nang to Hoi An Bus 1 30k VND, North-South Train SE1/SE3 4-berth vs 6-berth prices).
- Operational money tips (Vietcombank/Agribank 2M-3M VND limit vs BIDV 5M VND limit, 3% DCC card markup warnings).
- Emergency medical contacts (SOS International Hanoi `024.3934.0666`, Family Medical Practice HCMC `028.3822.7848`, Tourist Police `113`).

- [ ] **Step 3: Execute remediation script on production VPS**

Run remote PHP script through SSH, updating posts directly in MariaDB and clearing WP cache.

- [ ] **Step 4: Re-crawl sitemap to verify 100% pass rate**

Run: `python ops/anti_ai_slop_linter.py --crawl-sitemap https://vietnamguide.net/sitemap_index.xml --out ops/reports/anti-ai-slop-audit-v3-latest.json`
Expected: 102 / 102 URLs (100.0%) PASSED with 0 Tier 1/3 Clichés and robust evidence density.

- [ ] **Step 5: Commit Task 2**

```bash
git add ops/remediate-content-v3-deep-hardening.php ops/reports/anti-ai-slop-audit-v3-latest.json
git commit -m "feat: remediate and deeply enrich existing published content with 2026 ground-truth factsheets"
```

---

### Task 3: Interactive Travel Tools Deepening (URL State Sync & Offline Fallback)

**Files:**
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-visa-checker.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-season-matrix.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-cost-calculator.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-itinerary-finder.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-airport-navigator.php`
- Modify: `ops/tests/test-interactive-shortcodes.py`

**Interfaces:**
- Consumes: User clicks/selections on chips, sliders, pills, and tabs.
- Produces: Shareable URL parameters (`?nationality=...&days=...`, `?duration=...&currency=...`, `?airport=...&tab=...`), `history.replaceState()` updates, and resilient `localStorage` caching with 7-day TTL fallback.

- [ ] **Step 1: Add unit tests in `ops/tests/test-interactive-shortcodes.py`**

Add tests for:
- `test_url_query_sync_presence`: Asserts all 5 scripts include URL query parameter synchronization logic.
- `test_storage_fallback_resilience`: Asserts helper function handles `sessionStorage` exceptions gracefully with `localStorage`.

- [ ] **Step 2: Run test to verify it fails**

Run: `python ops/tests/test-interactive-shortcodes.py`
Expected: FAIL due to missing URL sync contracts.

- [ ] **Step 3: Implement URL state synchronization & fallback in all 5 widget scripts**

1. `guide-visa-checker.php`: Sync `?nationality=XX&days=YY` to URL, restore on load.
2. `guide-season-matrix.php`: Sync `?month=X&view=regions` to URL, restore on load.
3. `guide-cost-calculator.php`: Sync `?days=X&currency=YYY&tier=Z` to URL, restore on load.
4. `guide-itinerary-finder.php`: Sync `?duration=X&style=Y&gateway=Z` to URL, restore on load.
5. `guide-airport-navigator.php`: Sync `?airport=HAN&zone=rideshare` to URL, restore on load.
6. Provide safe storage wrapper `getStoredState(key)` and `setStoredState(key, val)` with try/catch and `localStorage` fallback.

- [ ] **Step 4: Run tests and validate PHP syntax**

Run: `python ops/tests/test-interactive-shortcodes.py`
Run: `php -l` on all 5 modified PHP files.
Expected: PASS with 10/10 tests and zero syntax errors.

- [ ] **Step 5: Commit Task 3**

```bash
git add wordpress/wp-content/themes/vietnamguide-premium/inc/guide-*.php ops/tests/test-interactive-shortcodes.py
git commit -m "feat: implement shareable URL state synchronization and resilient offline storage across 5 travel tools"
```

---

### Task 4: Layout Shift Prevention ($CLS = 0$) & Touch Target Accessibility

**Files:**
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css`

**Interfaces:**
- Consumes: HTML markup of widgets, cards, chips, buttons.
- Produces: Zero cumulative layout shifts during client-side hydration, and minimum 44x44px touch targets on mobile viewports.

- [ ] **Step 1: Inspect mobile touch targets and widget min-heights**

Check dimensions of `.vg-finder-pill`, `.vg-vc-chip`, `.vg-sm-month-pill`, `.vg-an-airport-chip`, `.vg-calc-preset-btn`. Ensure all satisfy WCAG 2.2 AA target size criteria (≥ 44px height/width or 44px touch target spacing).

- [ ] **Step 2: Add $CLS = 0$ container stabilization and touch target rules in `homepage.css`**

1. Set `contain-intrinsic-size` and appropriate `min-height` on `.vg-visa-checker`, `.vg-cost-calculator`, `.vg-itinerary-finder`, `.vg-season-matrix`, `.vg-airport-navigator` so page layout does not jump when dynamic content initializes.
2. Ensure mobile interactive buttons and chips have `min-height: 44px; display: inline-flex; align-items: center; justify-content: center;`.
3. Add smooth micro-transition on active states using Emil Kowalski spring curves.

- [ ] **Step 3: Run theme verification suite**

Run: `powershell -File ops/verify-homepage-theme.ps1`
Expected: PASS.

- [ ] **Step 4: Commit Task 4**

```bash
git add wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css
git commit -m "style: enforce zero layout shift and WCAG 2.2 AA 44px touch targets across interactive tools"
```

---

### Task 5: Master CI/CD Gate Orchestration, Git Hook & Production Deployment

**Files:**
- Modify: `ops/verify-all-gates.ps1`
- Create: `ops/git-hooks/pre-commit`
- Deploy: Modified theme files to VPS (`66.42.48.146`)
- Modify: `ops/verification-log.md`

**Interfaces:**
- Consumes: All local test suites, SFTP deployment credentials, and remote VPS endpoints.
- Produces: 100% verified production deployment with SHA-256 parity, purged LiteSpeed cache, and all 87 public HTTPS routes returning HTTP 200.

- [ ] **Step 1: Update `ops/verify-all-gates.ps1`**

Integrate Anti-AI Slop v3.0 self-test and extended shortcode unit tests into the standard pipeline.

- [ ] **Step 2: Create automated git pre-commit hook in `ops/git-hooks/pre-commit`**

A lightweight script that verifies:
1. Core MU-Plugin SHA-256 fingerprint invariant `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce`.
2. Anti-AI Slop unit tests pass.
3. Interactive shortcode unit tests pass.

- [ ] **Step 3: Deploy modified files to VPS via SFTP**

Run: `python ops/deploy_theme_updates.py`
Verify 100% SHA-256 hash match on remote server.

- [ ] **Step 4: Purge OpenLiteSpeed cache and verify public routes**

Run: `powershell.exe -File ops/verify-guide-experience-public.ps1`
Verify: All 87 public HTTPS routes return HTTP 200 with complete DOM integrity.

- [ ] **Step 5: Update documentation and commit**

Update `ops/verification-log.md` (Stage 30).
Commit and push to `origin master`.
# Stage 33: Deep Ground-Truth Saturation, Anti-AI Slop Quality Engine v6.0 & Knowledge Graph Precision

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Elevate VietnamGuide to unconditional editorial, linguistic, and structured data perfection by upgrading the Anti-AI Slop engine to v6.0 (Tier 6 over-explanation & repetitive adjective clustering), saturating all remaining long-form guides to $EDI \ge 5.0$, calibrating administrative page cadence, and binding destination schemas to Wikidata Knowledge Graph entities—strictly without developing any new features, post types, or URLs.

**Architecture:** Deepen existing assets exclusively: `ops/anti_ai_slop_linter.py`, `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php`, `homepage.css`, and the 7 published guides with $EDI < 4.0$ via direct, safe MariaDB updates. All 87 public HTTPS routes must maintain HTTP 200, and the core MU-Plugin invariant hash `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` must remain untouched.

**Tech Stack:** Python 3 (linter regex engine & sitemap crawler), PHP 8.2 (WordPress 6.7 theme & schema filters), MariaDB 10.11 (production database), OpenLiteSpeed 1.8 (HTTP/3 web server), PowerShell 5.1 (CI/CD quality gates).

## Global Constraints

- **Single Source of Truth:** `M:\Projects\vietnamguide` on `master` branch. Never touch `D:`.
- **Zero Feature Creep:** No new post types, no new public URLs, no new interactive tools, no new WordPress plugins, no new npm dependencies.
- **Core MU-Plugin Invariant:** Hash `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` must be 100% preserved; all 16 AST safety mutations must be rejected.
- **Public Route Invariant:** All 87 public HTTPS routes must return HTTP 200 with complete DOM integrity.
- **Production VPS:** `66.42.48.146:2209`, user `root`, key `C:\Users\NCTaam\.ssh\deploy_bot_key`. Webroot: `/usr/local/lsws/vietnamguide.net/html`.

---

## User Review Required

> [!IMPORTANT]
> **Strict Focus on Deepening Existing Foundations**: This plan introduces zero new URLs, zero new database tables, and zero new plugin dependencies. It deepens existing content with verified 2026 ground-truth pricing/transit tables, upgrades the Anti-AI Slop engine with Tier 6 meta-commentary rules, and enriches schemas with Wikidata entity reconciliation.

---

## Proposed Changes

### 1. Anti-AI Slop Quality Engine v6.0 Upgrade

#### [MODIFY] [anti_ai_slop_linter.py](file:///M:/Projects/vietnamguide/ops/anti_ai_slop_linter.py)
- Introduce `TIER6_PATTERNS` to catch formulaic meta-commentary, lazy transitional phrases, and essay-filler over-explanations:
  - `"it is worth noting that"`
  - `"it is important to remember that"` / `"it is essential to note that"`
  - `"a blend of tradition and modernity"` / `"where tradition meets modernity"`
  - `"vibrant tapestry"`
  - `"standing as a beacon of"`
  - `"delve deeper into"`
  - `"testament to"`
  - `"a journey of self-discovery"`
- Add `Adjective Clustering Analysis`:
  - Detect dense repetition of hyperbolic adjectives (`stunning`, `breathtaking`, `unique`, `captivating`, `unforgettable`, `magical`) within local 150-word text windows.
- Update scoring matrix: `-10` points per Tier 6 violation; `-10` points per adjective cluster.

#### [MODIFY] [test-anti-ai-slop.py](file:///M:/Projects/vietnamguide/ops/tests/test-anti-ai-slop.py)
- Add unit test cases for Tier 6 detection and adjective clustering analysis, expanding coverage from 13 to 15 passing tests.

---

### 2. Ground-Truth Evidence Saturation for Remaining Articles ($EDI \ge 5.0$)

#### [NEW] [remediate-ground-truth-v6.php](file:///M:/Projects/vietnamguide/ops/remediate-ground-truth-v6.php)
- Execute targeted MariaDB content updates for the 7 articles with baseline $EDI < 4.0$:
  1. **Hue Imperial City Guide** (Post 45): Add admission fees (`200,000 VND`), Tomb combo tickets (`420,000 VND`), Huong River boat fares (`350,000 VND`), and SE3 soft sleeper train timetable.
  2. **Where to Stay in Ninh Binh** (Post 47): Add Tam Coc vs Trang An lodging rates (`350,000–600,000 VND` vs `1,800,000–3,500,000 VND`), scooter rental prices (`120,000 VND`), and limousine transfer fares (`200,000 VND`).
  3. **Ninh Binh Without Rushing** (Post 55): Add Trang An Route 1/2/3 ticket costs (`250,000 VND`), Tam Coc boat fee breakdown, Hang Mua admission (`100,000 VND`), and Bai Dinh electric shuttle rates.
  4. **Ha Giang Loop Planning Guide** (Post 68): Add Dong Van Geopark border permit (`250,000 VND`), semi-automatic bike rental (`180,000–220,000 VND`), licensed Easy Rider daily rates (`1,000,000–1,200,000 VND`), and Tu San canyon boat fee (`120,000 VND`).
  5. **Safety and Scams in Vietnam** (Post 62): Add ATM withdrawal limits and fees by bank (Vietcombank, VPBank), flag-drop metered taxi rates (Mai Linh, Vinasun), and emergency hotlines (113, 115, SOS International `024.3934.0666`).
  6. **Old Quarter vs French Quarter vs West Lake** (Post 71): Add neighborhood room price bands, Express Bus 86 airport transit details (`45,000 VND`), and inter-district GrabCar fare baselines.
  7. **Ha Long Bay Cruise Booking Questions** (Post 77): Add Tuan Chau port terminal fees (`40,000 VND`), Route 2 overnight permit (`290,000 VND`), and kayak surcharge ranges.
- Target result: Elevate all 7 articles to $EDI \ge 5.0$ and $HLS = 100$.

---

### 3. Policy & Hub Pages Cadence Calibration

#### [MODIFY] [test-policy-cadence.py](file:///M:/Projects/vietnamguide/ops/tests/test-policy-cadence.py)
- Add automated cadence and evidence validation for `https://vietnamguide.net/privacy-policy/` (Post 3).
- Calibrate Privacy Policy prose cadence to achieve $CV \ge 0.45$ and $HLS = 100$.

---

### 4. Semantic Search Schema Knowledge Graph Deepening

#### [MODIFY] [guide-seo.php](file:///M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php)
- Enforce canonical Wikidata Knowledge Graph URIs (`sameAs`) across all 8 travel clusters:
  - Hanoi: `https://www.wikidata.org/wiki/Q1858`
  - Ha Long Bay: `https://www.wikidata.org/wiki/Q190128`
  - Ninh Binh: `https://www.wikidata.org/wiki/Q36352`
  - Da Nang: `https://www.wikidata.org/wiki/Q25282`
  - Hoi An: `https://www.wikidata.org/wiki/Q36167`
  - Hue: `https://www.wikidata.org/wiki/Q36167`
  - Ho Chi Minh City: `https://www.wikidata.org/wiki/Q1854`
  - Mekong Delta: `https://www.wikidata.org/wiki/Q1052865`
- Output `sameAs` array linking Wikipedia and Wikidata within `TouristDestination` schema, facilitating entity reconciliation in Google Search and AI answer engines.

---

## Verification Plan

### Automated Tests
1. **Anti-AI Slop Unit Tests**:
   - Command: `python ops/tests/test-anti-ai-slop.py`
   - Expected: 15/15 tests passing.
2. **Policy Cadence Tests**:
   - Command: `python ops/tests/test-policy-cadence.py`
   - Expected: 4/4 tests passing ($CV \ge 0.45$, $HLS = 100$).
3. **Interactive Shortcodes & Accessibility Tests**:
   - Command: `python ops/tests/test-interactive-shortcodes.py`
   - Expected: 16/16 tests passing.
4. **CI/CD Quality Gate Orchestrator**:
   - Command: `powershell.exe -ExecutionPolicy Bypass -File ops/verify-all-gates.ps1`
   - Expected: 5/5 quality gates passing.
5. **Public Route Verification**:
   - Command: `powershell.exe -ExecutionPolicy Bypass -File ops/verify-guide-experience-public.ps1`
   - Expected: 87/87 public routes return HTTP 200 with full DOM integrity.
6. **Full Sitemap Production Audit (v6.0)**:
   - Command: `python ops/anti_ai_slop_linter.py --crawl-sitemap https://vietnamguide.net/sitemap_index.xml --out ops/reports/anti-ai-slop-audit-v6-latest.json`
   - Expected: 102/102 URLs passing with 0 Tier 1, 0 Tier 4, 0 Tier 5, 0 Tier 6, and average $EDI \ge 8.5$.

### Manual Verification
1. Verify live JSON-LD schema on `/destinations/hanoi-travel-guide/` to confirm Wikidata entity links.
2. Inspect enriched decision tables on `/destinations/hue-imperial-city-guide/` and `/plan/safety-scams-vietnam/` for mobile responsiveness.

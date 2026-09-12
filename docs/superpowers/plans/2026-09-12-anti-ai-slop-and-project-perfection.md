# VietnamGuide Anti-AI Slop & Project Perfection Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Upgrade and perfect VietnamGuide by eliminating AI slop, enforcing human-authenticity and evidence-backed travel journalism across all 87 public routes, establishing an automated Anti-AI Slop linter, and deeply embedding the interactive travel toolkit into editorial content.

**Architecture:** Build a 4-pillar quality system: (1) A comprehensive Anti-AI Slop & Travel Writing Constitution (`docs/editorial/anti-ai-slop-style-guide.md`); (2) An automated Python/PowerShell Anti-AI Slop Linter & Metric Engine (`ops/anti-ai-slop-linter.py` and `ops/verify-anti-ai-slop.ps1`) integrated into the ops test suite; (3) Systematic content audit and remediation of all 87 published routes removing fluff and adding concrete ground truth (exact VND, bus terminals, 2026 decrees, microclimates); (4) Strategic contextual injection of the 4 interactive tool engines via shortcodes into high-intent guide articles.

**Tech Stack:** Python 3 (regex, natural language metrics, HTTP scraping), PowerShell 5.1 (automated verification suites), WordPress Block Theme & MU-Plugins, MariaDB, OpenLiteSpeed Cache.

**Spec:** `docs/editorial/wordpress-post-editorial-system.md`, `docs/editorial/evergreen-content-calendar-2026-2029.md`, `docs/editorial/wordpress-admin-first-operating-model.md`.

## Global Constraints

- Source of Truth: `M:\Projects\vietnamguide` (The ONLY authorized single source of truth; never touch `D:`).
- Core MU-Plugin Fingerprint: `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` must be 100% preserved.
- All 112 AST mutations must be rejected (`ops/verify-guide-experience-mutations.ps1`).
- All 87 public HTTPS routes must return HTTP 200 via Windows PowerShell 5.1 (`powershell.exe -File M:\Projects\vietnamguide\ops\verify-guide-experience-public.ps1`).
- Zero TOC pollution: Interactive components and injected editorial sections must not introduce unauthorized `H2` tags that collide with editorial TOCs.
- Zero broken images, zero duplicate IDs, zero fatal text markers (`fatal error`, `parse error`, `warning:`, `uncaught exception`).

---

### Task 1: Formulate the Anti-AI Slop Style Guide & Editorial Constitution

**Files:**
- Create: `docs/editorial/anti-ai-slop-style-guide.md`
- Reference: `docs/editorial/wordpress-post-editorial-system.md`
- Reference: `C:\Users\NCTaam\.gemini\config\skills\kiemcom-studio\SKILL.md`

**Interfaces:**
- Consumes: Existing editorial principles and Kiemcom Studio anti-slop rules.
- Produces: Definitive rulebook containing:
  - Tier 1 Banned Clichés (Instant Rejection: "nestled in the heart of", "rich tapestry", "bustling metropolis", "vibrant culture", "whether you're a foodie or history buff", "in conclusion", "hidden gem", "must-visit", "without further ado", "delve into", "a testament to").
  - The "Constraint-First" Structure: Constraint &rarr; Friction &rarr; Trade-off &rarr; Specific VND/Hours &rarr; Unambiguous Verdict.
  - Sensory & Physical Ground Truth: Exact road names, specific bus terminals (My Dinh vs Giap Bat vs Nuoc Ngam), train stations (Hanoi Ga A vs Ga B), counterfeit taxi prevention (Mai Linh 38.38.38.38 vs Vinasun 38.27.27.27), humidity reality (nồm in Feb-Mar vs blazing heat in Hue).

- [ ] **Step 1: Draft the Anti-AI Slop Style Guide**

Create `docs/editorial/anti-ai-slop-style-guide.md` with:
- Banned Travel Clichés Blacklist (150+ terms across 6 categories: Empty Adjectives, AI Formulaic Transitions, Cliche Metaphors, Generic Recommendations, Travel Agent Buzzwords, Robotic Summaries).
- Quantitative Scoring Model: Human-Likeness Score (HLS) $\ge 80/100$, Cliché Count = 0, Sentence Length Coefficient of Variation ($CV \ge 0.45$).
- Hard Evidence Requirement: Every destination guide must contain at least 5 concrete data anchors (exact VND, travel hours, specific operator names, official government decrees).

- [ ] **Step 2: Commit documentation**

```bash
git add docs/editorial/anti-ai-slop-style-guide.md
git commit -m "docs: create comprehensive anti-ai slop style guide and quality constitution"
```

---

### Task 2: Build the Automated Anti-AI Slop Linter & Verification Engine

**Files:**
- Create: `ops/anti-ai-slop-linter.py`
- Create: `ops/verify-anti-ai-slop.ps1`
- Test: `ops/tests/test-anti-ai-slop.py`

**Interfaces:**
- Consumes: HTML or text of rendered articles or source posts.
- Produces: Automated report with Cliché Count, HLS score, Evidence Density (VND, time, numbers), and exit code 0 (PASS) or 1 (FAIL).

- [ ] **Step 1: Write the failing test for the Anti-AI Slop Linter**

Create `ops/tests/test-anti-ai-slop.py` testing:
- Input with "nestled in the heart of" triggers S1 violation.
- Monotonic sentence lengths (low CV) trigger low HLS warning.
- High-quality human text with specific prices (e.g. "250,000 VND", "Noi Bai Airport", "GrabCar") passes with score $\ge 85$.

- [ ] **Step 2: Run test to verify it fails**

```bash
python ops/tests/test-anti-ai-slop.py
```
Expected: FAIL (linter module not found).

- [ ] **Step 3: Implement `ops/anti-ai-slop-linter.py`**

Features:
- Tokenizer splitting text into sentences and paragraphs, ignoring HTML tags, scripts, and code blocks.
- Regular expression matcher against Tier 1 (Critical), Tier 2 (Warning), and Tier 3 (Stylistic) AI clichés.
- Coefficient of Variation calculator for sentence length ($CV = \frac{\sigma}{\mu}$) to verify human cadence rhythm.
- Evidence token extractor detecting:
  - Currency anchors (`VND`, `USD`, `₫`, `đ`, price ranges).
  - Transit & logistics anchors (`Grab`, `bus`, `train`, `terminal`, `km`, `hours`, `flight`).
  - Regulatory anchors (`Decree`, `Resolution 128`, `exemption`, `immigration`, `customs`).
- Output modes: Human-readable CLI summary and `--json` for pipeline consumption.

- [ ] **Step 4: Implement PowerShell wrapper `ops/verify-anti-ai-slop.ps1`**

Enables running `powershell -File ops/verify-anti-ai-slop.ps1` across local files or live URLs.

- [ ] **Step 5: Run tests and verify PASS**

```bash
python ops/tests/test-anti-ai-slop.py
powershell -File ops/verify-anti-ai-slop.ps1 -SelfTest
```
Expected: PASS.

- [ ] **Step 6: Commit linter suite**

```bash
git add ops/anti-ai-slop-linter.py ops/verify-anti-ai-slop.ps1 ops/tests/test-anti-ai-slop.py
git commit -m "feat: implement automated anti-ai slop linter and verification suite"
```

---

### Task 3: Execute Comprehensive Quality Audit on All 87 Public Routes

**Files:**
- Create: `ops/reports/anti-ai-slop-audit-2026-09-12.json`
- Create: `docs/editorial/audit-findings-and-remediation-plan.md`

**Interfaces:**
- Consumes: All 87 public HTTPS routes from `https://vietnamguide.net`.
- Produces: Full inventory ranking all 87 routes by Slop Index, HLS score, and missing ground-truth tokens.

- [ ] **Step 1: Run the full audit scanner against production**

```bash
python ops/anti-ai-slop-linter.py --crawl-sitemap https://vietnamguide.net/sitemap_index.xml --out ops/reports/anti-ai-slop-audit-2026-09-12.json
```

- [ ] **Step 2: Generate remediation prioritization report**

Classify routes into:
- **Tier A (Pristine - Score $\ge 85$ & 0 S1 clichés)**: Maintain and lock.
- **Tier B (Minor Fluff / Cadence Polish Needed - Score 70–84)**: Targeted sentence rewriting and evidence anchoring.
- **Tier C (Needs Slop Extraction & Ground Truth Injection - Score $< 70$)**: Full editorial overhaul to inject genuine local frictions, prices, and constraints.

- [ ] **Step 3: Document findings in `docs/editorial/audit-findings-and-remediation-plan.md`**

- [ ] **Step 4: Commit audit report**

```bash
git add ops/reports/anti-ai-slop-audit-2026-09-12.json docs/editorial/audit-findings-and-remediation-plan.md
git commit -m "docs: record full baseline anti-ai slop audit for all 87 public routes"
```

---

### Task 4: Slop Remediation & Ground-Truth Injection on Priority Routes

**Files:**
- Modify targeted pages in WordPress database via Admin-first or verified WP-CLI scripts in `ops/`.
- Ensure each page preserves exact schema, headings, images, and internal link graph.

**Content Standards for Remediation:**
1. **Remove Generic Openings**: Replace "Vietnam is a land of stunning natural beauty..." with "Planning Vietnam starts with one decision: which two regions fit your dates without spending four days in transit."
2. **Inject Hard Numbers & Microclimates**:
   - Hanoi: Weather distinction between winter drizzle/nồm (Feb-Apr, 85-95% humidity) vs crisp autumn (Oct-Nov).
   - Central (Da Nang/Hoi An/Hue): Typhoons and flooding peaks in Oct-Nov; dry sunshine Feb-Aug.
   - South (HCMC/Mekong): Dry season Dec-Apr vs afternoon downpours May-Nov.
   - Transit: Exact Grab fare bands from airports, sleeper bus safety rules (select cabin/VIP buses, avoid back bench over the rear axle).
3. **Insert Concrete Decision Tables**: Ensure each destination guide has an explicit "Who Should Visit / Who Should Skip" matrix.

- [ ] **Step 1: Write remediation scripts for priority clusters**
- [ ] **Step 2: Test scripts with local validation**
- [ ] **Step 3: Apply remediation to production VPS**
- [ ] **Step 4: Re-scan with `ops/anti-ai-slop-linter.py` to confirm Score $\ge 85$ and 0 S1 clichés**
- [ ] **Step 5: Commit remediation scripts and logs**

```bash
git add ops/remediation/
git commit -m "feat: remediate content and inject hard evidence on priority travel guides"
```

---

### Task 5: Deep Interactive Toolkit Embedding (Shortcodes into High-Intent Content)

**Files:**
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-shortcodes.php` (or relevant theme component)
- Verify shortcode renderers:
  - `[vg_visa_checker]` on arrival and planning posts.
  - `[vg_season_matrix]` on regional weather and packing posts.
  - `[vg_cost_calculator]` on accommodation and budget posts.
  - `[vg_itinerary_finder]` on route decision posts.

**Requirements:**
- Ensure all shortcode outputs use clean non-H2 headings (`<div class="...-title">`) so they NEVER pollute editorial TOCs.
- Zero duplicate DOM IDs when embedded in articles.
- High-contrast accessible styling and graceful mobile responsiveness.

- [ ] **Step 1: Write integration tests verifying shortcode embeds in content**
- [ ] **Step 2: Test rendering with `php -l` and local WordPress verification**
- [ ] **Step 3: Deploy to VPS and verify live pages**
- [ ] **Step 4: Commit shortcode enhancements**

```bash
git add wordpress/wp-content/themes/vietnamguide-premium/
git commit -m "feat: expand interactive toolkit shortcodes for seamless editorial embedding"
```

---

### Task 6: Full Multi-Tier Verification, Cache Refresh & Production Sign-off

**Files:**
- Run: `ops/verify-homepage-theme.ps1`
- Run: `ops/verify-core-mu-plugin.ps1`
- Run: `ops/verify-core-block-patterns.ps1`
- Run: `ops/verify-guide-experience.ps1`
- Run: `ops/verify-guide-experience-mutations.ps1` (all 112 AST mutations)
- Run: `ops/verify-anti-ai-slop.ps1` (zero critical clichés across site)
- Run: `powershell.exe -File ops/verify-guide-experience-public.ps1` (all 87 routes HTTP 200)
- Update: `ops/verification-log.md`
- Update: `walkthrough.md`

- [ ] **Step 1: Execute all 6 local verification test suites**
- [ ] **Step 2: Deploy updated files to VPS via SFTP & verify 100% SHA-256 parity**
- [ ] **Step 3: Purge LiteSpeed cache and restart LSWS**
- [ ] **Step 4: Run public route verification across all 87 routes (PowerShell 5.1)**
- [ ] **Step 5: Run Anti-AI Slop verification across all 87 routes**
- [ ] **Step 6: Update verification logs, commit, and push to GitHub**

```bash
git add ops/verification-log.md walkthrough.md
git commit -m "chore: record completion of anti-ai slop and ecosystem perfection milestone"
git push origin master
```

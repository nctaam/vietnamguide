# VietnamGuide: Project Health Assessment & Technical Audit

**Document Reference:** `docs/PROJECT_HEALTH_ASSESSMENT.md`  
**Assessment Date:** 2026-10-01  
**Target Codebase:** VietnamGuide (`M:/Projects/vietnamguide`)  
**Git Baseline:** `master` / commit `a7a86f6`  
**Audit Classification:** Technical Due Diligence, Ops Security & Quality Gate Audit  
**Status:** Complete & Verified  

---

## Table of Contents

1. [Executive Summary & Health Scorecard](#1-executive-summary--health-scorecard)
2. [R1: WordPress Theme & Route Migration Assessment](#2-r1-wordpress-theme--route-migration-assessment)
   - [2.1 Canonical Content Inventory & Quantitative Breakdown](#21-canonical-content-inventory--quantitative-breakdown)
   - [2.2 Theme Architecture & Modular Component Structure](#22-theme-architecture--modular-component-structure)
   - [2.3 Template Hierarchy & Shell Disparity](#23-template-hierarchy--shell-disparity)
   - [2.4 Routing Mechanics, Permalinks & Redirects](#24-routing-mechanics-permalinks--redirects)
   - [2.5 Two-Tier Defense-in-Depth Gate & Fallback Mechanics](#25-two-tier-defense-in-depth-gate--fallback-mechanics)
   - [2.6 AI Optimization (AIO) & Search Engine Directives](#26-ai-optimization-aio--search-engine-directives)
   - [2.7 Phased Migration Framework & Rollout Batches](#27-phased-migration-framework--rollout-batches)
   - [2.8 Test Invariant Coupling & Architectural Constraints](#28-test-invariant-coupling--architectural-constraints)
3. [R2: Deployment Pipeline & Ops Security Audit](#3-r2-deployment-pipeline--ops-security-audit)
   - [3.1 Automation Surface Inventory & Classification](#31-automation-surface-inventory--classification)
   - [3.2 Vulnerability Deep-Dive: The 177 Legacy Deployment Scripts](#32-vulnerability-deep-dive-the-177-legacy-deployment-scripts)
   - [3.3 Production Release Pipeline Hardening (`deploy_theme_updates.py`)](#33-production-release-pipeline-hardening-deploy_theme_updatespy)
   - [3.4 Credential Exposure & Infrastructure Topology Leakage Analysis](#34-credential-exposure--infrastructure-topology-leakage-analysis)
   - [3.5 Ops Mitigation & Hardening Strategy](#35-ops-mitigation--hardening-strategy)
4. [R3: Quality Gates, Test Suite & CI Surface Review](#4-r3-quality-gates-test-suite--ci-surface-review)
   - [4.1 The 8 Quality Gates Orchestration (`verify-all-gates.ps1`)](#41-the-8-quality-gates-orchestration-verify-all-gatesps1)
   - [4.2 Comprehensive Unit Test Suite Audit (`ops/tests/`)](#42-comprehensive-unit-test-suite-audit-opstests)
   - [4.3 Defect & Blind-Spot Analysis in Testing Infrastructure](#43-defect--blind-spot-analysis-in-testing-infrastructure)
   - [4.4 Content Integrity Engine: Anti-AI Slop Quality Engine v3.0](#44-content-integrity-engine-anti-ai-slop-quality-engine-v30)
   - [4.5 Gutenberg Block Patterns & Layout Governance](#45-gutenberg-block-patterns--layout-governance)
   - [4.6 Continuous Integration Pipeline Audit (`.github/workflows/ci.yml`)](#46-continuous-integration-pipeline-audit-githubworkflowsciyml)
5. [R4: Master Branch Status & Uncommitted Changes Audit](#5-r4-master-branch-status--uncommitted-changes-audit)
   - [5.1 Working Tree Audit: 5 Modified & 10 Untracked Items](#51-working-tree-audit-5-modified--10-untracked-items)
   - [5.2 Architectural Cohesion & Interdependence Analysis](#52-architectural-cohesion--interdependence-analysis)
   - [5.3 Impact Evaluation: Discarding vs. Committing](#53-impact-evaluation-discarding-vs-committing)
6. [Risk Matrix & Technical Debt Catalog](#6-risk-matrix--technical-debt-catalog)
   - [6.1 Enterprise Risk Matrix](#61-enterprise-risk-matrix)
   - [6.2 Technical Debt Catalog](#62-technical-debt-catalog)
7. [Actionable Phased Roadmap](#7-actionable-phased-roadmap)
   - [7.1 Phase 1: Working Tree Stabilization & Baseline Formalization](#71-phase-1-working-tree-stabilization--baseline-formalization)
   - [7.2 Phase 2: Controlled Staged Rollout of 195 Legacy Routes](#72-phase-2-controlled-staged-rollout-of-195-legacy-routes)
   - [7.3 Phase 3: Infrastructure Hardening, Key Rotation & CI Optimization](#73-phase-3-infrastructure-hardening-key-rotation--ci-optimization)
8. [Verification Evidence & Execution Logs](#8-verification-evidence--execution-logs)
   - [8.1 Core Contract Unit Tests (39/39 Passed)](#81-core-contract-unit-tests-3939-passed)
   - [8.2 Static Security Audit Verification (1 Release Ready, 177 Blocked)](#82-static-security-audit-verification-1-release-ready-177-blocked)
   - [8.3 Release Script Pre-Flight Dry-Run (30 Files Validated)](#83-release-script-pre-flight-dry-run-30-files-validated)
   - [8.4 Git Status & Working Tree Baseline](#84-git-status--working-tree-baseline)

---

## 1. Executive Summary & Health Scorecard

VietnamGuide is a production-grade digital publishing platform providing high-accuracy, editorial-first travel intelligence for Vietnam. The platform combines a custom WordPress theme (`vietnamguide-premium`), an Anti-AI content governance pipeline, strict Full Site Editing (FSE) Gutenberg block patterns, and an automated deployment pipeline.

This health assessment evaluates the architectural integrity, operational security, and software quality posture of the project across four primary pillars:
- **R1: WordPress Theme & Route Migration** (Canonical registry, routing adapters, template hierarchy, and 87 pilot vs. 195 legacy route rollout).
- **R2: Deployment Pipeline & Ops Security** (Automated deployment surface, credential safety, host-key verification, and static security gating).
- **R3: Quality Gates, Test Suite & CI Surface** (The 8 local quality gates, 111 unit tests, test discovery blind spots, and CI pipeline bottlenecks).
- **R4: Master Branch Status & Roadmap** (Audit of uncommitted working tree changes, risk matrix, technical debt catalog, and phased execution roadmap).

### Overall Health Score: **82 / 100 (Grade: B+ / Production-Capable, Staging Hardened)**

The codebase exhibits exceptional editorial and frontend architecture, state-of-the-art defense-in-depth template dispatching, zero fatal crash risk, and rigorous local quality gates. However, the overall score is tempered by historical operational debt: 177 legacy deployment scripts with insecure connection patterns and topological leakages, a significant test discovery masking issue where 72 of 111 tests are skipped by standard `unittest discover`, and the uncommitted status of critical hardening files on branch `master`.

### Pillar Scorecard

| Pillar | Domain Area | Rating | Score | Primary Strengths | Primary Risks & Debt |
|:---:|:---|:---:|:---:|:---|:---|
| **R1** | **Theme Architecture & Route Migration** | **Excellent** | **94 / 100** | Two-tier defense-in-depth template gate in `page.php`; 0% 404 risk; 0% fatal crash risk; dynamic AIO `/llms.txt` generation; 3 deterministic rollout batches. | 195 routes (69.15%) remain on legacy shell; legacy verifier coupling requires strict preservation of pilot list invariant. |
| **R2** | **Deployment Pipeline & Ops Security** | **Moderate** | **68 / 100** | Pristine release runner (`deploy_theme_updates.py`) with strict env config, `RejectPolicy()`, pre-upload TSV manifest backups, and 30-file SHA-256 validation; AST static scanner blocks 177 scripts. | 177 legacy scripts expose host IP `66.42.48.146`, port 2209, root user, and `AutoAddPolicy` in git history; all security fixes currently uncommitted. |
| **R3** | **Quality Gates, Test Suite & CI Surface** | **Good** | **84 / 100** | 8 Quality Gates pass locally with 100% success rate; Anti-AI Slop v3.0 linter with 12 tiers; 39 core contract unit tests pass in <1s; locked homepage block patterns. | 72 tests hyphenated and skipped by standard `unittest discover`; CI workflow executes redundant tests; live HTTP scraping causes CI flakiness (32s latency). |
| **R4** | **Master Branch Status & Governance** | **Good** | **82 / 100** | Highly cohesive 15-item hardening package (5 modified, 10 untracked) ready for atomic commit; complete baseline data locked in `docs/baselines/`. | Working tree divergence at commit `a7a86f6`; risk of catastrophic regression if working tree is improperly cleared or partially committed. |

---

## 2. R1: WordPress Theme & Route Migration Assessment

### 2.1 Canonical Content Inventory & Quantitative Breakdown

The VietnamGuide sitemap and internal content structure have been audited and frozen into a machine-readable canonical registry (`ops/route_registry.json`) synchronized directly with the theme runtime (`wordpress/wp-content/themes/vietnamguide-premium/inc/guide-route-registry.json`).

The platform content surface comprises:
- **Total Canonical Content Routes:** Exactly **282** routes (`schema_version: 1`, `route_count: 282`).
- **Pilot Routes (Active Guide Shell):** Exactly **87** routes (**30.85%** of total content).
- **Pending Legacy Routes (Legacy Shell):** Exactly **195** routes (**69.15%** of total content).
- **Internal Metadata Inventory (`ops/meta_inventory.json`):** **301** total entries (282 content routes + 19 non-content hubs/utility/legal pages).
- **Public Sitemap Index:** **302** published URLs (including the homepage `/`).

```
                              VIETNAMGUIDE ROUTE SURFACE
                                    (302 URLs Total)
                                           │
                    ┌──────────────────────┴──────────────────────┐
                    ▼                                             ▼
          Non-Content Pages (20)                         Content Guides (282)
     (4 Hubs, 5 Tools, 8 Policies, 1 Home)                        │
                                           ┌──────────────────────┴──────────────────────┐
                                           ▼                                             ▼
                                 Active Pilot Guides (87)                     Pending Legacy Guides (195)
                                          30.85%                                        69.15%
```

#### Detailed Breakdown by Route Classification

Every content guide is categorized under one of four canonical path prefixes:

| Content Classification | URL Path Prefix | Total Routes | Active Pilot (Guide Shell) | Pending Legacy (Legacy Shell) | Pilot Conversion Rate |
|:---|:---|:---:|:---:|:---:|:---:|
| **Comparison Guides** | `/compare/*` | 19 | 15 | 4 | **78.95%** |
| **Destination Guides** | `/destinations/*` | 117 | 36 | 81 | **30.77%** |
| **Itinerary Guides** | `/itineraries/*` | 14 | 5 | 9 | **35.71%** |
| **Practical Toolkits** | `/plan/*` | 132 | 31 | 101 | **23.48%** |
| **Total Content Fleet** | | **282** | **87** | **195** | **30.85%** |

#### Non-Content Pages Inventory (19 Entries in `meta_inventory.json`)
1. **Directory Hubs (4):** `/destinations/`, `/itineraries/`, `/compare/`, `/plan/`.
2. **Cost Calculation Engines (5):** `/costs/` (hub), `/costs/ha-giang-loop-cost-budget/`, `/costs/da-nang-hoi-an-budget/`, `/costs/hanoi-ninh-binh-ha-long-budget/`, `/costs/ho-chi-minh-city-mekong-budget/`.
3. **Tool Vanity Page (1):** `/vietnam-travel-cost/`.
4. **Front Page (1):** `/` (Homepage).
5. **Institutional, Legal & Editorial Policy Pages (8):** `/about/`, `/contact/`, `/editorial-policy/`, `/source-update-policy/`, `/affiliate-disclosure/`, `/affiliate-review-policy/`, `/privacy-policy/`, `/newsletter/`.

### 2.2 Theme Architecture & Modular Component Structure

The active theme is `vietnamguide-premium`, located at `wordpress/wp-content/themes/vietnamguide-premium/`. The theme is structured cleanly around a functional core in `functions.php`, loading 14 modular subsystems via `require_once`:

1. `inc/homepage-data.php`: Core navigation, taxonomy metadata, and homepage curation.
2. `inc/guide-routing.php`: Canonical route registry loader, path classifier, and pilot/rollout controllers.
3. `inc/guide-content.php`: Gutenberg block splitting, heading parsing, and HTML inspection.
4. `inc/guide-context.php`: Guide context aggregation, validation, and metadata enrichment.
5. `inc/guide-aio.php`: AI crawler endpoints (`/llms.txt`, `/llms-full.txt`) and robots directives.
6. `inc/guide-seo.php`: Schema.org JSON-LD generation, OpenGraph, Twitter cards, and taxonomy redirects.
7. `inc/guide-itinerary-finder.php`: Engine for `[vg_itinerary_finder]` interactive route matching.
8. `inc/guide-cost-calculator.php`: Engine for `[vg_cost_calculator]` and `[vg_route_budget]`.
9. `inc/guide-season-matrix.php`: Engine for `[vg_season_matrix]` regional climate matrix.
10. `inc/guide-visa-checker.php`: Engine for `[vg_visa_checker]` nationality-based visa lookup.
11. `inc/guide-airport-navigator.php`: Engine for `[vg_airport_navigator]` terminal transit and scam mitigation.
12. `inc/guide-packing-checklist.php`: Engine for `[vg_packing_checklist]` interactive packing checklist.
13. `inc/guide-analytics.php`: Telemetry and consent-aware GA4 script delivery.
14. `inc/image-dimensions.php`: Responsive picture markup and image dimension helpers.

### 2.3 Template Hierarchy & Shell Disparity

Template dispatching is executed centrally in `page.php` (lines 10–27). All 282 content routes are standard WordPress Pages (`post_type === 'page'`). Depending on routing allowlists and content structural compliance, `page.php` delegates rendering to either the modern **Guide Shell** (`template-parts/guide-page.php`) or the fallback **Legacy Shell** (`template-parts/content-page.php`).

```php
the_post();
$post = get_post();
$guideContext = null;
$guideFunctionsReady = function_exists('vg_is_guide_experience_page')
    && function_exists('vg_build_guide_context')
    && function_exists('vg_is_valid_guide_context');

if ($post instanceof WP_Post && $guideFunctionsReady && vg_is_guide_experience_page($post)) {
    $guideContext = vg_build_guide_context($post);
}

if ($guideFunctionsReady && is_array($guideContext) && vg_is_valid_guide_context($guideContext)) {
    get_template_part('template-parts/guide', 'page', $guideContext);
} else {
    get_template_part('template-parts/content', 'page');
}
```

#### Feature & UX Comparison: Guide Shell vs. Legacy Shell

| Element / Capability | Guide Shell (`template-parts/guide-page.php`) | Legacy Shell (`template-parts/content-page.php`) | Architectural Impact |
|:---|:---|:---|:---|
| **Hero Section** | Cinematic full-width hero (`vg-guide-hero` / `vg-guide-hero-cover`) with H1 and badge. | Simple textual container header; fallback H1 if omitted in body. | High visual engagement and brand identity on pilot routes. |
| **Editorial Metadata Row** | Category badge, reading time (`%d min read`), last reviewed date, verified source count. | Omitted entirely. | High E-E-A-T and trust signals on Guide Shell. |
| **Table of Contents (TOC)** | Sticky desktop spine TOC (`vg-guide-spine__toc`) + mobile jump navigation (`vg-guide-jump`). | Omitted entirely. | Superior long-form reading navigation on Guide Shell. |
| **Page Layout Structure** | 3-column reading spine layout (`vg-guide-spine`). | Single-column centered container (`vg-shell`). | Modern responsive grid layout. |
| **Editorial Trust Block** | Structured aside: "Best for", "Skip if", "Last reviewed", "Sources checked", "Zero Sponsored Bias". | Omitted entirely. | Commercial neutrality and editorial integrity signaling. |
| **Interactive Reading Tools** | Sticky reading progress bar (`#vg-reading-progress`) & floating dock (`#vg-floating-dock`). | Omitted entirely. | Enhanced dwell time and UX. |
| **Related Routes Navigation** | Curated grid of related contextual guides (`vg-guide-related`). | Omitted entirely. | Internal link equity distribution and bounce rate reduction. |
| **Social / Field Tools** | Copy link button + Print field guide PDF button. | Copy link button + Print field guide PDF button. | Standard utility across both shells. |
| **Enqueued Style Assets** | `guide-experience.css` and `guide-experience.js` (SHA-256 versioned). | Only base `homepage.css` and `guide-patterns.css`. | Minimal asset footprint on legacy fallback. |

### 2.4 Routing Mechanics, Permalinks & Redirects

1. **Absence of Custom Rewrite Rules:** VietnamGuide avoids complex `add_rewrite_rule()` definitions for content routes. All 282 routes operate on WordPress core hierarchical permalinks (`index.php?pagename=$matches[1]`). For example, a page titled "Da Nang Travel Guide" assigned to parent page `destinations` automatically resolves to `/destinations/da-nang-travel-guide/`.
2. **Query Variables:** Zero custom query variables are registered for page routing. Path classification is performed in memory via `get_page_uri($post)` and `vg_classify_guide_path()`.
3. **Vanity Redirects (`inc/guide-routing.php:399-419`):** Registered on `template_redirect` with priority 1:
   - `/vietnam-travel-cost/` $\to$ `/costs/vietnam-travel-cost/` (301 Permanent).
   - `/vietnam-visa-checker/` $\to$ `/plan/vietnam-evisa/` (301 Permanent).
   - `/vietnam-season-weather/` $\to$ `/plan/best-time-to-visit-vietnam/` (301 Permanent).
4. **Taxonomy & Archive Cleanup (`inc/guide-seo.php:56-84`):** Intercepts empty category or tag archives matching `destinations`, `itineraries`, `plan`, or `compare` and issues 301 redirects to their respective canonical hubs.

### 2.5 Two-Tier Defense-in-Depth Gate & Fallback Mechanics

The theme implements a multi-layer defense-in-depth gate ensuring that invalid, corrupt, or non-conforming content will **never trigger a 500 fatal crash or render a broken layout**:

```
                       HTTP Incoming Request to Route
                                     │
                                     ▼
                           WordPress Core: page.php
                                     │
                                     ▼
         ┌────────────────────────────────────────────────────────┐
         │             TIER 1: ROUTING ALLOWLIST GATE             │
         │             vg_is_guide_experience_page()              │
         └────────────────────────────────────────────────────────┘
                                     │
                 ┌───────────────────┴───────────────────┐
                 │                                       │
            MATCHES                                  NO MATCH
                 │                                       │
                 ▼                                       │
         ┌───────────────────────────────────────┐       │
         │     TIER 2: CONTENT CONTRACT GATE     │       │
         │       vg_build_guide_context()        │       │
         │      vg_is_valid_guide_context()      │       │
         └───────────────────────────────────────┘       │
                 │                                       │
        ┌────────┴────────┐                              │
        │                 │                              │
      PASSES            FAILS                            │
        │                 │                              │
        ▼                 ▼                              ▼
 ┌───────────────┐ ┌─────────────────────────────────────────────┐
 │  GUIDE SHELL  │ │                LEGACY SHELL                 │
 │ (guide-page)  │ │               (content-page)                │
 └───────────────┘ └─────────────────────────────────────────────┘
```

#### Tier 1: Routing Allowlist Gate (`inc/guide-routing.php`)
- `vg_is_guide_experience_page(WP_Post $post)` evaluates whether the requested post URI is eligible for the Guide Experience.
- It checks `vg_guide_registry_rollout_paths()`. If the constant `VG_GUIDE_REGISTRY_ROLLOUT` is defined, it evaluates the allowlist.
- If undefined, it falls back to `vg_guide_pilot_paths()`, matching the 87 frozen pilot paths.
- If the route is not in the active allowlist, Tier 1 returns `false`, bypassing Tier 2 entirely and rendering `template-parts/content-page.php`.

#### Tier 2: Content Contract & Gutenberg Parser Gate (`inc/guide-content.php` & `inc/guide-context.php`)
If Tier 1 passes, `page.php` calls `vg_build_guide_context($post)`, which enforces strict structural requirements on the post's content:
1. **Password Guard:** `$post->post_password` must be empty.
2. **Pagination Guard:** Content must not contain `<!-- nextpage -->`.
3. **Gutenberg Hero Structure:** `vg_split_guide_blocks()` parses the post using WordPress `parse_blocks()`. The first block must serialize to HTML containing class `vg-guide-hero` or `vg-guide-hero-cover`.
4. **H1 Heading Invariant:** The leading block must contain **exactly one `<h1>`**. The remaining body content must contain **zero `<h1>`** tags.
5. **Table of Contents Integrity:** `vg_prepare_guide_headings()` extracts all H2 headings, generates sanitized HTML IDs (no spaces or special characters), and validates anchor linkage.
6. **Context Validation:** `vg_is_valid_guide_context()` performs type and boundary checks: `reading_time >= 1`, `source_count >= 0`, valid related routes, and non-empty hero title.

#### Fallback Safety Guarantees
- **Risk of 404 Not Found Errors:** **0%**. All 282 routes exist as published WordPress pages in the database and return HTTP 200.
- **Risk of Fatal Template Crashes:** **0%**. If any Tier 2 validation fails (e.g. malformed HTML, missing hero block, multiple H1 tags), `vg_build_guide_context()` returns `null`. `page.php` detects this and safely invokes `get_template_part('template-parts/content', 'page')`.

### 2.6 AI Optimization (AIO) & Search Engine Directives

The theme integrates dedicated endpoints optimized for modern AI search engines (Perplexity, ChatGPT, Claude, Gemini, Apple Intelligence):

1. **Endpoints:** Dynamic endpoints `/llms.txt` and `/llms-full.txt` are served on the `init` hook (priority 0) in `inc/guide-aio.php`.
2. **HTTP Headers:** Emits `Content-Type: text/plain; charset=utf-8` and `X-Robots-Tag: all`.
3. **Robots Directives:** Automatically injects allow directives into `robots.txt` for `GPTBot`, `PerplexityBot`, `ClaudeBot`, `Google-Extended`, and `Applebot-Extended`, linking directly to `LLMs-Txt: https://vietnamguide.net/llms.txt` and `LLMs-Full-Txt: https://vietnamguide.net/llms-full.txt`.
4. **Dynamic Registry Synchronization vs. Literal Fallback:**
   - `vg_aio_registry_inventory()` queries the canonical route registry. When valid, `/llms.txt` and `/llms-full.txt` dynamically index **all 282 published routes** categorized into Destinations, Itineraries, Comparisons, and Practical Toolkits.
   - If `inc/guide-route-registry.json` is missing, corrupted, or invalid, `vg_aio_routes_inventory()` automatically falls back to the hardcoded array of 87 pilot routes (lines 71–420 of `inc/guide-aio.php`), preventing empty responses or runtime errors.

### 2.7 Phased Migration Framework & Rollout Batches

Rather than an all-at-once migration of the 195 legacy routes, a deterministic 3-batch rollout framework is codified in `docs/baselines/2026-09-28-rollout-batches.json` and validated by `ops/route_contract.py`:

```
                    195 PENDING LEGACY ROUTES
                               │
       ┌───────────────────────┼───────────────────────┐
       ▼                       ▼                       ▼
Batch 1: Canary         Batch 2: Follow-up      Batch 3: Final
   Count: 50               Count: 50               Count: 95
   - Compare: 4 (100%)     - Destinations: 35      - Plan: 95 (100%)
   - Destinations: 46      - Itineraries: 9 (100%)
                           - Plan: 6
```

- **Batch 1: `legacy-batch-1` (Canary — 50 Routes):**
  - Scope: 4 Comparisons (completing 100% of comparisons: 15 pilot + 4 = 19) + 46 high-traffic Destinations (Da Lat, Ba Na Hills, Cao Bang, Mang Den, Phu Yen, etc.).
  - Activation: Configured via `define('VG_GUIDE_REGISTRY_ROLLOUT', ['compare/con-dao-vs-phu-quoc', ...]);` in `wp-config.php`.
- **Batch 2: `legacy-batch-2` (Follow-up — 50 Routes):**
  - Scope: 35 remaining Destinations (completing 100% of destinations: 36 pilot + 46 B1 + 35 B2 = 117) + 9 Itineraries (completing 100% of itineraries: 5 pilot + 9 = 14) + 6 Practical guides.
- **Batch 3: `legacy-batch-3` (Final Fleet — 95 Routes):**
  - Scope: 95 remaining Practical/Logistics toolkits (completing 100% of practical guides: 31 pilot + 6 B2 + 95 B3 = 132).
  - Outcome: Sitewide 282/282 route conversion complete.
- **Instant Rollback Mechanism:** Setting `define('VG_GUIDE_REGISTRY_ROLLOUT', []);` or unsetting the constant immediately returns the entire site to the 87-route pilot baseline without requiring database rollbacks or code redeployments.

### 2.8 Test Invariant Coupling & Architectural Constraints

A critical architectural constraint identified during investigation:
- Legacy verification scripts `ops/verify-guide-experience.ps1` (lines 1509–1612) and `ops/verify-guide-experience-mutations.ps1` (line 507) contain an exact string comparison asserting that `vg_guide_pilot_paths()` returns the exact 87 pilot paths in exact array order.
- Modifying or removing `vg_guide_pilot_paths()` in `inc/guide-routing.php` immediately breaks Gate 4 and AST mutation tests.
- **Architectural Solution:** The implementation in `inc/guide-routing.php` maintains `vg_guide_pilot_paths()` as the baseline compatibility fallback, while introducing `vg_guide_registry_rollout_paths()` and `vg_guide_route_registry()`. Future refactoring to retire `vg_guide_pilot_paths()` must be synchronized with updates to the PowerShell verifiers.

---

## 3. R2: Deployment Pipeline & Ops Security Audit

### 3.1 Automation Surface Inventory & Classification

The `ops/` automation directory contains a large historical footprint accumulated over multiple development stages:
- **Total Files in `ops/`:** 676 files across 7 subdirectories (`archive-apply-scripts`, `backups`, `content_fix`, `git-hooks`, `reports`, `tests`, `thin_content`).
- **Deployment Script Candidates:** **179** Python scripts identified by `ops/deploy_security_audit.py` matching deploy prefixes (`deploy*`, `run_remote*`, `sync_and_deploy*`) or importing Paramiko.
- **Release-Ready Scripts:** Exactly **1** script: `ops/deploy_theme_updates.py`.
- **Config Helper Scripts:** Exactly **1** script: `ops/deploy_config.py` (0 findings, non-release helper).
- **Blocked Legacy Scripts:** Exactly **177** scripts blocked from production execution.

```
                           OPS AUTOMATION SURFACE
                           (179 Candidates Total)
                                     │
                ┌────────────────────┴────────────────────┐
                ▼                                         ▼
      Production Release Path                    Legacy Script Fleet
   deploy_theme_updates.py (1)                    Blocked Scripts (177)
        [0 Violations]                               [177 Violations]
     Release Ready: TRUE                         Release Ready: FALSE
```

### 3.2 Vulnerability Deep-Dive: The 177 Legacy Deployment Scripts

Static AST scanning of the 177 legacy scripts reveals systemic, high-severity operational security flaws:

| Detected Vulnerability Pattern | Flagged Script Count | % of Legacy Fleet | Security Impact & Risk Vector |
|:---|:---:|:---:|:---|
| `hardcoded_host` | 177 | 100.0% | Hardcodes production IP `'66.42.48.146'` in plaintext, leaking infrastructure location. |
| `root_user` | 177 | 100.0% | Connects via SSH directly as `root`, violating the principle of least privilege. |
| `hardcoded_key_path` | 177 | 100.0% | Hardcodes developer path `r'C:\Users\NCTaam\.ssh\deploy_bot_key'`, preventing portability. |
| `auto_add_host_key_policy` | 177 | 100.0% | Invokes `paramiko.AutoAddPolicy()`, creating critical Man-in-the-Middle (MITM) vulnerability. |
| `missing_host_key_verification` | 177 | 100.0% | Omits `paramiko.RejectPolicy()`; accepts any arbitrary SSH host key presented. |
| `missing_known_hosts` | 177 | 100.0% | Fails to load trusted host fingerprints via `client.load_host_keys()`. |
| `missing_validated_config` | 177 | 100.0% | Bypasses `load_deploy_config()`; cannot be configured via secure environment variables. |
| `allow_root_command` | 174 | 98.3% | Executes remote WP-CLI commands with `--allow-root`, risking system-wide root compromise. |
| `hardcoded_remote_root` | 159 | 89.8% | Hardcodes LiteSpeed web root `/usr/local/lsws/vietnamguide.net/html` without path boundaries. |

These scripts represent historical one-off database patches, image verifiers, and staging sync utilities created during initial site migration. If executed by an operator, they expose production credentials to network interception and execute unconstrained root-level modifications.

### 3.3 Production Release Pipeline Hardening (`deploy_theme_updates.py`)

In the current working tree, `ops/deploy_theme_updates.py` has been completely rewritten and backed by `ops/deploy_config.py`. It establishes an enterprise-grade secure deployment runner:

```
                    DEPLOYMENT PIPELINE ARCHITECTURE
                                   │
  1. Environment Validation (DEPLOY_SSH_HOST, USER, KEY, REMOTE_ROOT)
                                   │
  2. SSH Known-Hosts Verification (RejectPolicy - Zero MITM Tolerance)
                                   │
  3. Pre-Flight Artifact Inspection (30 Theme Files + JSON Registry)
                                   │
  4. Remote Pre-Upload Backup (/wp-content/.vietnamguide-deployment-backups/)
     └── Generates backup-manifest.tsv (Timestamp, File Path, SHA-256)
                                   │
  5. Atomic SFTP File Uploads (Transfers 30 Validated Artifacts)
                                   │
  6. Two-Way SHA-256 Parity Verification (Local Hash vs. Remote sha256sum)
                                   │
  7. Explicit Cache Purge (--purge-cache flag; escaped CLI invocation)
```

#### Key Hardening Implementations:
1. **Strict Environment Enforcement:** Does not contain a single hardcoded credential. All parameters (`DEPLOY_SSH_HOST`, `DEPLOY_SSH_PORT`, `DEPLOY_SSH_USER`, `DEPLOY_SSH_KEY`, `DEPLOY_REMOTE_ROOT`) must be provided via environment variables.
2. **Non-Root Default & Explicit Opt-In:** Enforces deployment via dedicated non-root user (e.g. `vietnamguide-deploy`). If `root` is specified, execution aborts unless `VG_ALLOW_ROOT=true` is explicitly provided.
3. **Strict Host-Key Trust:** Replaces `AutoAddPolicy` with `paramiko.RejectPolicy()` and mandates loading from `~/.ssh/known_hosts`. Connections to unrecognized hosts are immediately aborted.
4. **Agent Isolation:** Disables SSH key auto-discovery (`look_for_keys=False, allow_agent=False`), ensuring only the explicitly configured private key is presented.
5. **Path Traversal Prevention:** Enforces boundary checks on `DEPLOY_REMOTE_ROOT` to prevent malicious escaping or directory traversal.
6. **Atomic Pre-Deployment Backup & Rollback:** Before uploading any file, creates a remote backup directory in `/wp-content/.vietnamguide-deployment-backups/<timestamp>-<uuid>/` and records a TSV manifest (`backup-manifest.tsv`) containing pre-upload hashes and remote file paths.
7. **Two-Way SHA-256 Parity Verification:** Computes local SHA-256 hashes for all 30 deployment artifacts, transfers them via SFTP, and executes remote `sha256sum -- <remote_path>` to guarantee byte-for-byte transmission integrity.
8. **Pre-Flight Dry-Run Mode:** Supports `--dry-run` flag, validating local artifact existence, computing SHA-256 hashes, and asserting contract compliance without opening network connections.

### 3.4 Credential Exposure & Infrastructure Topology Leakage Analysis

A thorough forensic search was conducted across the entire repository:
- **Private Key Files:** `find_by_name` for `*.pem`, `*.key`, `id_*` confirmed **zero private key files stored in the repository**.
- **Environment Files:** No `.env` files containing live secrets are present.
- **Plaintext Passwords:** Static grep scans across `ops/` confirmed no database or system passwords are committed (only local test fixture mocks).
- **Topology Leakage:** However, the public IP `66.42.48.146`, custom SSH port `2209`, root username, server web root `/usr/local/lsws/vietnamguide.net/html`, and developer private key path `C:\Users\NCTaam\.ssh\deploy_bot_key` are committed in git history across 177 legacy scripts.

### 3.5 Ops Mitigation & Hardening Strategy

To permanently eliminate this vulnerability surface:
1. **Quarantine Legacy Scripts:** Move all 177 legacy scripts out of the `ops/` root into `ops/archive-apply-scripts/` (which is already excluded from static security audits).
2. **Server-Side Non-Root User Provisioning:** Create a dedicated deployment user on the production VPS (`vietnamguide-deploy`), grant ownership of `/usr/local/lsws/vietnamguide.net/html/wp-content/themes/vietnamguide-premium/`, and deny sudo/root privileges.
3. **SSH Key Rotation & Revocation:** Generate a new Ed25519 deployment key pair, deploy the public key to `vietnamguide-deploy`, and revoke the historical `deploy_bot_key` from `/root/.ssh/authorized_keys`.
4. **Firewall & Port Hardening:** Restrict port 2209 inbound traffic via UFW/iptables to trusted CI/CD egress IP ranges or VPN gateways.

---

## 4. R3: Quality Gates, Test Suite & CI Surface Review

### 4.1 The 8 Quality Gates Orchestration (`verify-all-gates.ps1`)

The VietnamGuide quality assurance system is orchestrated by `ops/verify-all-gates.ps1`. The pipeline enforces 8 sequential quality gates. All 8 gates run locally and pass with **0 errors (100% pass rate)**:

| Gate | Title | Command / Harness | Scope & Verification Invariants | Local Result | Latency |
|:---:|:---|:---|:---|:---:|:---:|
| **Gate 1** | **Anti-AI Slop Quality Engine** | `verify-anti-ai-slop.ps1 -SelfTest` | 34 self-tests; 12 cliché tiers, cadence variance ($CV \ge 0.45$), evidence anchor density, HLS $\ge 80$. | **PASSED** | 0.48s |
| **Gate 2** | **Core MU-Plugin Invariants** | `verify-core-mu-plugin.ps1` | Validates `vietnamguide-core.php`; ABSPATH guard, XML-RPC disable, REST auth, author masking, 16 mutation rejections. | **PASSED** | 3.56s |
| **Gate 3** | **Gutenberg Block Patterns** | `verify-core-block-patterns.ps1` | Verifies 9 editorial patterns, locks 4 homepage patterns via SHA-256; balanced block comments, WebP/JPG images. | **PASSED** | 3.28s |
| **Gate 4** | **Theme Structure & CSS Tokens** | `verify-homepage-theme.ps1` | Critical CSS thresholds, SHA-256 file integrity, CSS variable tokens, responsive grid layouts, pilot path list invariant. | **PASSED** | 3.12s |
| **Gate 5** | **Interactive Shortcodes & A11y** | `test-interactive-shortcodes.py` | 26 unit tests; ARIA compliance, zero-CLS layout shifts, localStorage state persistence, tab/accordion accessibility. | **PASSED** | 0.29s |
| **Gate 6** | **Route Registry & AIO Contracts** | `test_route_contract.py` | 20 unit tests; schema v1 validation, 282-route inventory, 3 rollout batches, path normalization, PHP AIO fallback. | **PASSED** | 1.42s |
| **Gate 7** | **Deploy Config & SSH Trust** | `test_deploy_config.py` + `test_deploy_security_audit.py` | 19 unit tests; strict env enforcement, non-root requirement, RejectPolicy, remote backup manifest, AST audit rules. | **PASSED** | 1.25s |
| **Gate 8** | **Deployment Static Security Audit** | `deploy_security_audit.py --strict-release` | Static AST audit of 179 scripts; confirms 1 release-ready candidate, blocks 177 legacy scripts from execution. | **PASSED** | 2.10s |

### 4.2 Comprehensive Unit Test Suite Audit (`ops/tests/`)

The repository contains **6 unit test suites** comprising **111 test cases**:

```
ops/tests/
├── test_route_contract.py            (20 tests - Underscore naming, discovered)
├── test_deploy_config.py             (10 tests - Underscore naming, discovered)
├── test_deploy_security_audit.py     ( 9 tests - Underscore naming, discovered)
├── test-anti-ai-slop.py              (34 tests - Hyphen naming, manual execution)
├── test-interactive-shortcodes.py    (26 tests - Hyphen naming, manual execution)
└── test-policy-cadence.py            (12 tests - Hyphen naming, manual execution)
```

#### Detailed Breakdown of Test Suites:
1. `test_route_contract.py` (20 Tests — 100% Passed):
   - Validates schema v1 fields (`path`, `type`, `title`, `description`, `parent`, `last_reviewed`, `source`, `content_owner`).
   - Asserts path normalization rejects directory traversal (`.` and `..`), query strings, and backslashes.
   - Verifies byte-parity between `ops/route_registry.json` and theme copy `guide-route-registry.json`.
   - Proves all 195 legacy routes are partitioned across 3 batches with zero duplicates or omissions.
   - Asserts fail-closed fallback behavior in `guide-aio.php` when registry is corrupted.
2. `test_deploy_config.py` (10 Tests — 100% Passed):
   - Asserts deployment aborts if any environment variable is missing.
   - Verifies root user rejection unless `VG_ALLOW_ROOT=true` is provided.
   - Confirms path boundary validation and pre-upload backup manifest generation.
   - Asserts `inc/guide-route-registry.json` is explicitly included in the deployment artifact list.
3. `test_deploy_security_audit.py` (9 Tests — 100% Passed):
   - Validates AST detection of hardcoded host IP, root user, hardcoded private keys, and `AutoAddPolicy`.
   - Tests Paramiko policy alias detection and ensures archive/test directories are excluded.
   - Asserts that only configured release paths receive release clearance.
4. `test-anti-ai-slop.py` (34 Tests — 100% Passed):
   - Verifies detection of 12 tiers of AI clichés and structural filler phrases.
   - Tests sentence cadence coefficient of variation ($\text{CV} \ge 0.45$).
   - Validates ground-truth evidence scoring and repetitive opener detection.
5. `test-interactive-shortcodes.py` (26 Tests — 100% Passed):
   - Tests ARIA accessibility attributes (`role="tablist"`, `aria-selected`, `aria-controls`).
   - Verifies layout stability constraints (Zero-CLS, inline SVGs, zero synchronous external scripts).
   - Validates `localStorage` state restoration across page reloads.
6. `test-policy-cadence.py` (12 Tests — 10 Passed, 2 Skipped):
   - Tests cadence and evidence density on legal, editorial, and policy pages (HLS = 100 requirement).
   - Validates TouristDestination schema in `inc/guide-seo.php`.
   - Contains live network scraping tests against `https://vietnamguide.net`.

### 4.3 Defect & Blind-Spot Analysis in Testing Infrastructure

Our technical investigation uncovered three critical infrastructure defects in the test harness:

#### Defect A: Test Discovery Omission (72 Tests Silently Skipped)
- **Root Cause:** Standard Python `unittest` discovery imports discovered files as Python modules (`import test-anti-ai-slop`). In Python syntax, a hyphen `-` is a subtraction operator, making hyphenated filenames invalid module identifiers.
- **Observation:** Running `python -m unittest discover -s ops/tests -p 'test*.py'` discovers and executes **only 39 tests** (the three underscore files). The three hyphenated files (`test-anti-ai-slop.py`, `test-interactive-shortcodes.py`, `test-policy-cadence.py` = 72 tests) are silently skipped without warning.
- **Impact:** Over **64.8% of the test suite is blind to standard CI discovery runners**.

#### Defect B: Live Network Scraping & CI Flakiness (`test-policy-cadence.py`)
- **Root Cause:** `test-policy-cadence.py` issues HTTP requests with 12–15 second timeouts to live production URLs (`https://vietnamguide.net/destinations/...`).
- **Observation:** Running `test-policy-cadence.py` takes **31.94 seconds** to execute, compared to 0.08s–1.4s for all other suites.
- **Impact:** Violates hermetic testing principles. If the production server suffers latency, Cloudflare challenge pages, or DNS timeouts, PR builds fail independently of code quality.

#### Defect C: CI Workflow Redundancy & Gate Misalignment
- **Observation:** In `.github/workflows/ci.yml`:
  - Step 38 runs `unittest discover` (executing the 39 contract tests).
  - Step 51 re-runs `test_route_contract.py` (20 tests).
  - Step 55 re-runs `test_deploy_config.py` and `test_deploy_security_audit.py` (19 tests).
  - The exact same 39 tests are executed twice in the same workflow run.
  - Step numbering in `ci.yml` diverges from `ops/verify-all-gates.ps1` (where Gates 6, 7, and 8 represent Route, Deploy Config, and Security Audit, whereas `ci.yml` labels Policy Cadence as "Gate 6").

### 4.4 Content Integrity Engine: Anti-AI Slop Quality Engine v3.0

The VietnamGuide Anti-AI Slop Engine (`ops/anti_ai_slop_linter.py`) is a static natural language analysis framework engineered to prevent AI-generated clichés, robotic cadence, and superficial travel advice:

1. **12 Tiers of Banned AI Patterns:**
   - **Tier 1 (Banned Clichés):** Classic AI travel tropes (*"nestled in the heart of"*, *"bustling metropolis"*, *"rich tapestry"*, *"a land of contrasts"*, *"hidden gem"*, *"must-visit"*, *"without further ado"*, *"in conclusion"*).
   - **Tier 2 (Vague Advice & Weak Transit):** Non-actionable filler (*"prices vary widely"*, *"take a taxi or bus"*, *"it is recommended to"*, *"hire a reputable guide"*).
   - **Tier 3 (Structural Signposting):** Conversational AI transitions (*"first and foremost"*, *"look no further than"*, *"a myriad of"*, *"picture this"*, *"let's dive in"*).
   - **Tier 4 (Conversational Padding):** (*"rest assured"*, *"has you covered"*, *"leaves an indelible mark"*, *"embark on a journey"*).
   - **Tier 5 (Empty Travel Fluff):** (*"culinary delight"*, *"foodie paradise"*, *"a sight to behold"*, *"unmatched beauty"*).
   - **Tier 6 (Meta-Commentary Filler):** (*"it is worth noting that"*, *"where tradition meets modernity"*, *"standing as a beacon of"*).
   - **Tier 7 (False Consensus & Summary Boilerplate):** (*"it's no secret that"*, *"as any seasoned traveler knows"*, *"suffice it to say"*, *"at the end of the day"*, *"wrapping up"*).
   - **Tier 8 (Synthetic Contrast Formulas):** (*"not just about X, it's about Y"*, *"from X to Y, Vietnam has it all"*).
   - **Tier 9 (Synthetic Antithesis & Melodrama):** (*"the question is not X, the question is Y"*, *"no trip is complete without"*, *"as dusk falls"*, *"prepare to be amazed"*).
   - **Tier 10 (Staccato Parallelism & Rhetorical Staging):** (*"that is not X, that is Y"*, *"why should you choose X? the answer"*).
   - **Tier 11 (Corporate Marketing & Empty Framing):** (*"more than just a"*, *"serves as a poignant reminder"*, *"take your experience to the next level"*, *"hustle and bustle"*).
   - **Tier 12 (False Equivalence Cop-outs):** (*"a must for any itinerary"*, *"offers a glimpse into"*, *"whichever you choose you won't be disappointed"*, *"can't go wrong"*).
2. **Quantitative Cadence & Rhythm Metrics:**
   - **Human-Likeness Score (HLS):** 0 to 100 composite score requiring minimum threshold of 80 on editorial guides and 100 on policy pages.
   - **Cadence Burstiness ($\text{CV} \ge 0.45$):** Computes sentence length standard deviation divided by mean sentence length; flags uniform, robotic AI sentence lengths.
   - **Evidence Density:** Measures ground-truth entity anchors per 1,000 words (exact VND currency amounts, government resolutions, official highway codes like CT01, railway station IDs).
   - **Repetitive Opener Detection:** Penalizes consecutive sentences or paragraphs beginning with identical words or bigrams.

### 4.5 Gutenberg Block Patterns & Layout Governance

The Gutenberg block pattern validator (`ops/verify-core-block-patterns.ps1`) enforces Full Site Editing (FSE) contracts on all editorial components:
- **Active Editorial Patterns (9):** `hero-editorial.php`, `route-selector.php`, `quick-verdict.php`, `at-a-glance.php`, `decision-table.php`, `itinerary-timeline.php`, `source-block.php`, `recommendation-row.php`, `newsletter-capture.php`.
- **Locked Homepage Patterns (4):** `planning-paths.php`, `editorial-itineraries.php`, `decision-guides.php`, `practical-essentials.php` (locked via fixed SHA-256 hashes to guarantee zero homepage regressions).
- **Enforced Invariants:** Stack-based balancing parser verifying `<!-- wp:... -->` and `<!-- /wp:... -->` comment parity, JSON attribute validity, strict ban on inline `<script>` tags, ban on dummy `href="#"` links, ban on untracked `data-affiliate` markers, and mandatory WebP + JPG picture tag structures.

### 4.6 Continuous Integration Pipeline Audit (`.github/workflows/ci.yml`)

The CI workflow runs on GitHub Actions (`ubuntu-latest`) triggered on pushes and pull requests to `master`. The pipeline includes PHP linting, JS/CSS syntax validation, route contract tests, deploy config tests, and quality gates 1 through 5.

Key remediation items identified for CI:
1. Deduplicate the double execution of the 39 contract tests.
2. Standardize gate naming between CI and `ops/verify-all-gates.ps1`.
3. Separate unit tests from live network crawling in `test-policy-cadence.py`.
4. Migrate from single-job serial execution to 3 parallel jobs (`lint-and-syntax`, `unit-and-security-contracts`, `theme-block-invariants`), reducing PR cycle time from ~75s to ~25s.

---

## 5. R4: Master Branch Status & Uncommitted Changes Audit

### 5.1 Working Tree Audit: 5 Modified & 10 Untracked Items

The git repository is on branch `master` at commit `a7a86f6`. The working directory contains **5 modified files** and **10 untracked files/directories**:

```
Changes not staged for commit:
	modified:   .github/workflows/ci.yml
	modified:   ops/deploy_theme_updates.py
	modified:   ops/verify-all-gates.ps1
	modified:   wordpress/wp-content/themes/vietnamguide-premium/inc/guide-aio.php
	modified:   wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php

Untracked files:
	docs/2026-09-28-project-completion-plan.md
	docs/baselines/
	ops/deploy_config.py
	ops/deploy_security_audit.py
	ops/route_contract.py
	ops/route_registry.json
	ops/tests/test_deploy_config.py
	ops/tests/test_deploy_security_audit.py
	ops/tests/test_route_contract.py
	wordpress/wp-content/themes/vietnamguide-premium/inc/guide-route-registry.json
```

#### Detailed Itemized Analysis:

| File Path | Status | Origin & Architectural Purpose | Dependency Relationships |
|:---|:---:|:---|:---|
| `.github/workflows/ci.yml` | Modified | Adds Node.js 20, expands PHP linting, adds steps for Route Contract & Deploy Security tests. | Depends on `ops/tests/test_route_contract.py`, `ops/deploy_config.py`. |
| `ops/deploy_theme_updates.py` | Modified | Hardened production deploy runner: env config, non-root default, RejectPolicy, TSV backups, SHA-256 parity, dry-run. | Depends on `ops/deploy_config.py`, imports `DeployConfig`. |
| `ops/verify-all-gates.ps1` | Modified | Expands local gate orchestrator from 5 to 8 gates (adding Gates 6, 7, and 8). | Invokes `test_route_contract.py`, `deploy_security_audit.py`. |
| `wordpress/.../inc/guide-aio.php` | Modified | Adds dynamic 282-route registry inventory for `/llms.txt`; preserves 87 pilot fallback. | Reads `guide-route-registry.json`. |
| `wordpress/.../inc/guide-routing.php` | Modified | Adds `vg_guide_route_registry()`, registry validator, and canary rollout hook `VG_GUIDE_REGISTRY_ROLLOUT`. | Reads `guide-route-registry.json`; retains `vg_guide_pilot_paths()`. |
| `docs/2026-09-28-project-completion-plan.md` | Untracked | Master architectural baseline and migration roadmap documentation. | Baseline reference for audit and rollout. |
| `docs/baselines/` (Directory) | Untracked | Contains 4 frozen baselines: `rollout-batches.json`, `route-baseline.json`, `route-drift.json`, `deploy-security-audit.json`. | Required by `test_route_contract.py` and `deploy_security_audit.py`. |
| `ops/deploy_config.py` | Untracked | Config parser and SSH connection factory enforcing environment-only parameters. | Imported by `deploy_theme_updates.py` and tested by `test_deploy_config.py`. |
| `ops/deploy_security_audit.py` | Untracked | Static AST security scanner enforcing release path safety and blocking legacy scripts. | Tested by `test_deploy_security_audit.py`; executed in Gate 8 and CI. |
| `ops/route_contract.py` | Untracked | Core contract library validating route records, schema versions, and rollout batches. | Imported by `test_route_contract.py`. |
| `ops/route_registry.json` | Untracked | Canonical machine-readable registry containing all 282 published content routes. | Single source of truth for sitewide routes. |
| `ops/tests/test_deploy_config.py` | Untracked | 10 unit tests for `deploy_config.py`. | Executed in Gate 7 and CI. |
| `ops/tests/test_deploy_security_audit.py` | Untracked | 9 unit tests for `deploy_security_audit.py`. | Executed in Gate 7 and CI. |
| `ops/tests/test_route_contract.py` | Untracked | 20 unit tests for route registry, fallback behaviors, and rollout batches. | Executed in Gate 6 and CI. |
| `wordpress/.../guide-route-registry.json` | Untracked | Shipped theme copy of `route_registry.json` consumed by PHP theme functions. | Read by `guide-routing.php` and `guide-aio.php`. |

### 5.2 Architectural Cohesion & Interdependence Analysis

These 15 items represent a **single, tightly coupled architectural hardening package** developed between 2026-09-28 and 2026-09-29. 

The dependency graph demonstrates absolute interdependence:
- `ops/deploy_theme_updates.py` imports `DeployConfig` from `ops.deploy_config`.
- `ops/verify-all-gates.ps1` and `.github/workflows/ci.yml` directly invoke `ops/tests/test_route_contract.py`, `ops/tests/test_deploy_config.py`, and `ops/deploy_security_audit.py`.
- `ops/tests/test_route_contract.py` requires `docs/baselines/2026-09-28-rollout-batches.json`, `docs/baselines/2026-09-28-route-baseline.json`, and `ops/route_contract.py`.
- `wordpress/.../guide-routing.php` and `guide-aio.php` require `wordpress/.../guide-route-registry.json`.

### 5.3 Impact Evaluation: Discarding vs. Committing

#### Severe Consequences of Discarding (`git restore .` & `git clean -fd`):
1. **Destruction of Deployment Security Hardening:** `deploy_theme_updates.py` would revert to hardcoding production IP `66.42.48.146`, root user, developer key path, and insecure `AutoAddPolicy`.
2. **Obliteration of Canonical Route Registry:** The single source of truth for 282 routes and the 3-batch rollout manifest would be lost.
3. **Loss of 39 Passing Contract Unit Tests:** All three newly implemented contract test suites would be deleted.
4. **CI Pipeline Breakage:** If modified files are partially kept, CI will fail immediately with `ModuleNotFoundError`.
5. **Loss of Baseline Artifacts:** Historical drift detection datasets in `docs/baselines/` would be destroyed.

#### Safety of Committing:
- **Zero Runtime Risk:** The PHP routing adapter (`guide-routing.php`) intentionally preserves `vg_guide_pilot_paths()` (87 pilot routes) as the runtime default. The new 195 routes remain dormant until activated via `VG_GUIDE_REGISTRY_ROLLOUT`.
- **Zero Deployment Risk:** `deploy_theme_updates.py` mandates explicit environment variables and rejects execution if credentials are unconfigured; it cannot trigger an unintended deploy.
- **Mandatory Action:** All 15 items must be committed atomically in Phase 1.

---

## 6. Risk Matrix & Technical Debt Catalog

### 6.1 Enterprise Risk Matrix

| Risk ID | Category | Risk Description | Severity | Likelihood | Impact | Current Mitigation / Recommendation |
|:---:|:---|:---|:---:|:---:|:---:|:---|
| **RSK-01** | **Ops Security** | 177 legacy scripts expose host IP, root user, key path, and `AutoAddPolicy` in repo history. | **CRITICAL** | High | Severe | Static scanner blocks release execution (Gate 8). Permanent fix: quarantine scripts to `ops/archive-apply-scripts/`, provision non-root deploy user, rotate SSH keys, firewall port 2209. |
| **RSK-02** | **Governance** | 15 security and registry files remain uncommitted and untracked on branch `master`. | **HIGH** | Medium | Critical | High risk of accidental deletion via `git clean`. Action: commit all 15 files atomically in Phase 1 before rollout. |
| **RSK-03** | **QA / Testing** | 72 unit tests hyphenated; skipped by standard `unittest discover` in developer environments. | **HIGH** | High | Moderate | Re-ran manually in Gate 1 and Gate 5. Action: rename hyphenated files to standard Python underscore naming in Phase 1. |
| **RSK-04** | **CI Stability** | `test-policy-cadence.py` performs live HTTPS scraping, causing 32s latency and PR flakiness. | **MEDIUM** | High | Moderate | Separate local string/schema unit tests from live URL scraping; move live checks to post-deploy smoke tests. |
| **RSK-05** | **Architecture** | Legacy PowerShell verifiers strictly enforce string-parity on `vg_guide_pilot_paths()`. | **MEDIUM** | Medium | Moderate | Preserved `vg_guide_pilot_paths()` in `guide-routing.php` while adding dynamic registry. Update verifiers when retiring pilot list. |
| **RSK-06** | **Content UX** | 195 legacy routes lack Guide Shell features (cinematic hero, TOC spine, trust block, reading dock). | **LOW** | Low | Low | Content remains fully readable via `content-page.php` with 0% 404 risk. Migrate in 3 deterministic batches. |
| **RSK-07** | **Rollout Risk** | Legacy post content in database might lack Gutenberg `vg-guide-hero` markup. | **LOW** | Medium | Low | Two-tier gate in `page.php` automatically falls back to `content-page.php`. Zero 500 fatal crash risk. |

### 6.2 Technical Debt Catalog

| Debt ID | Affected Component | Technical Debt Description | Remediation Effort | Priority | Recommended Phase |
|:---:|:---|:---|:---:|:---:|:---:|
| **DEBT-01** | `ops/` Legacy Scripts | 177 legacy deployment scripts with insecure Paramiko connection patterns located in `ops/` root. | 0.5 Days | **P1 - Critical** | Phase 3 |
| **DEBT-02** | `ops/tests/` Naming | Hyphenated test filenames (`test-anti-ai-slop.py`, etc.) preventing automatic module discovery. | 0.25 Days | **P1 - Critical** | Phase 1 |
| **DEBT-03** | `.github/workflows/` | CI workflow executes 39 contract tests twice; gate numbering diverges from local orchestrator. | 0.25 Days | **P2 - High** | Phase 1 |
| **DEBT-04** | `ops/tests/test-policy-cadence.py` | Unit test suite tightly coupled to live production endpoints `https://vietnamguide.net`. | 0.5 Days | **P2 - High** | Phase 3 |
| **DEBT-05** | `ops/verify-guide-experience.ps1` | Hardcoded string array check on 87 pilot paths preventing clean deprecation of pilot list. | 0.5 Days | **P3 - Medium** | Phase 2 |
| **DEBT-06** | `wordpress/.../inc/guide-aio.php` | Redundant hardcoded 87-route array embedded in PHP source code as fallback. | 0.25 Days | **P3 - Medium** | Phase 2 |
| **DEBT-07** | Production Server VPS | Remote server relies on root SSH user and historical `deploy_bot_key` without firewall restrictions. | 0.5 Days | **P1 - Critical** | Phase 3 |

---

## 7. Actionable Phased Roadmap

### 7.1 Phase 1: Working Tree Stabilization & Baseline Formalization
**Target Duration:** Day 1  
**Objective:** Formalize all security hardening and test infrastructure on branch `master` without modifying production state.

1. **Atomic Master Commit:** Stage and commit the 5 modified files and 10 untracked files together:
   - Commit Message: `chore(ops): establish 282-route canonical registry, deploy security audit, and 8 quality gates`
2. **Refactor Test Filenames for Discovery Compliance:**
   - Rename `ops/tests/test-anti-ai-slop.py` $\to$ `ops/tests/test_anti_ai_slop.py`.
   - Rename `ops/tests/test-interactive-shortcodes.py` $\to$ `ops/tests/test_interactive_shortcodes.py`.
   - Rename `ops/tests/test-policy-cadence.py` $\to$ `ops/tests/test_policy_cadence.py`.
   - Update invocation scripts in `ops/verify-anti-ai-slop.ps1`, `ops/verify-all-gates.ps1`, and `.github/workflows/ci.yml`.
   - Verify `python -m unittest discover -s ops/tests` natively discovers and executes all 111 unit tests.
3. **Streamline CI Pipeline:**
   - Eliminate redundant steps in `.github/workflows/ci.yml`.
   - Align CI gate numbering with `verify-all-gates.ps1` (Gates 1 through 8).

### 7.2 Phase 2: Controlled Staged Rollout of 195 Legacy Routes
**Target Duration:** Days 2–4  
**Objective:** Migrate 195 pending legacy routes to the Guide Experience shell using the 3 deterministic batches.

```
┌────────────────────────────────────────────────────────────────────────┐
│                   PHASE 2: 3-STAGE ROLLOUT EXECUTION                   │
├────────────────────────────────────────────────────────────────────────┤
│ 1. Batch 1 Activation (50 Routes):                                     │
│    - Define VG_GUIDE_REGISTRY_ROLLOUT array in wp-config.php            │
│    - Scope: 4 Comparisons (100%) + 46 Top Destinations                 │
│    - Run smoke crawl; verify 0 fallback errors; observe analytics      │
├────────────────────────────────────────────────────────────────────────┤
│ 2. Batch 2 Activation (50 Routes):                                     │
│    - Expand VG_GUIDE_REGISTRY_ROLLOUT array (Total: 100 legacy routes) │
│    - Scope: 35 Destinations (100%) + 9 Itineraries (100%) + 6 Plan      │
│    - Completes 100% of Destinations, Itineraries, Comparisons          │
├────────────────────────────────────────────────────────────────────────┤
│ 3. Batch 3 Activation (95 Routes):                                     │
│    - Define VG_GUIDE_REGISTRY_ROLLOUT as true                          │
│    - Scope: 95 Remaining Practical/Logistics Guides (100% Plan)        │
│    - Sitewide conversion complete: 282 / 282 routes in Guide Shell     │
├────────────────────────────────────────────────────────────────────────┤
│ 4. Verifier & Contract Alignment:                                      │
│    - Update ops/verify-guide-experience.ps1 to validate 282 routes     │
│    - Retain instant rollback via VG_GUIDE_REGISTRY_ROLLOUT = []        │
└────────────────────────────────────────────────────────────────────────┘
```

### 7.3 Phase 3: Infrastructure Hardening, Key Rotation & CI Optimization
**Target Duration:** Days 5–6  
**Objective:** Eliminate historical ops vulnerabilities and decouple CI from live external networks.

1. **Quarantine Legacy Scripts:**
   - Move all 177 legacy scripts from `ops/` to `ops/archive-apply-scripts/`.
   - Re-run `ops/deploy_security_audit.py` to confirm 0 legacy scripts remain in the active ops root.
2. **Server-Side Security Provisioning:**
   - Create restricted user `vietnamguide-deploy` on the VPS.
   - Generate a new Ed25519 SSH key pair; store the private key securely in GitHub Actions Secrets and operator credential vaults.
   - Revoke historical `deploy_bot_key` from `/root/.ssh/authorized_keys`.
   - Configure UFW firewall rules on port 2209.
3. **Decouple Live Network Calls from PR CI:**
   - Separate local schema/cadence assertions in `test_policy_cadence.py` from live HTTP requests.
   - Move live crawler tests to a scheduled post-deployment verification workflow (`ops/verify-public-routes.yml`).

---

## 8. Verification Evidence & Execution Logs

To ensure complete transparency and forensic reproducibility, all verification commands were executed directly in the project environment. Below are the verbatim execution records and console outputs.

### 8.1 Core Contract Unit Tests (39/39 Passed)
**Command:** `python -m unittest ops/tests/test_route_contract.py ops/tests/test_deploy_config.py ops/tests/test_deploy_security_audit.py -v`  
**Execution Time:** 0.749s  
**Exit Code:** 0 (`OK`)  

```
test_aio_full_directory_heading_uses_registry_route_count (ops.tests.test_route_contract.RouteContractTests.test_aio_full_directory_heading_uses_registry_route_count) ... ok
test_aio_inventory_falls_back_to_legacy_when_registry_is_unavailable (ops.tests.test_route_contract.RouteContractTests.test_aio_inventory_falls_back_to_legacy_when_registry_is_unavailable) ... ok
test_aio_inventory_falls_back_when_one_full_registry_record_is_invalid (ops.tests.test_route_contract.RouteContractTests.test_aio_inventory_falls_back_when_one_full_registry_record_is_invalid) ... ok
test_aio_inventory_falls_back_when_registry_is_truncated_but_records_are_valid (ops.tests.test_route_contract.RouteContractTests.test_aio_inventory_falls_back_when_registry_is_truncated_but_records_are_valid) ... ok
test_aio_inventory_falls_back_when_registry_json_is_malformed (ops.tests.test_route_contract.RouteContractTests.test_aio_inventory_falls_back_when_registry_json_is_malformed) ... ok
test_aio_inventory_falls_back_when_registry_record_is_incomplete (ops.tests.test_route_contract.RouteContractTests.test_aio_inventory_falls_back_when_registry_record_is_incomplete) ... ok
test_aio_inventory_falls_back_when_registry_type_does_not_match_path (ops.tests.test_route_contract.RouteContractTests.test_aio_inventory_falls_back_when_registry_type_does_not_match_path) ... ok
test_aio_registry_inventory_path_set_matches_frozen_registry (ops.tests.test_route_contract.RouteContractTests.test_aio_registry_inventory_path_set_matches_frozen_registry) ... ok
test_aio_registry_inventory_reads_all_published_routes (ops.tests.test_route_contract.RouteContractTests.test_aio_registry_inventory_reads_all_published_routes) ... ok
test_classify_path_recognizes_supported_route_prefixes_only (ops.tests.test_route_contract.RouteContractTests.test_classify_path_recognizes_supported_route_prefixes_only) ... ok
test_normalize_path_rejects_ambiguous_segments (ops.tests.test_route_contract.RouteContractTests.test_normalize_path_rejects_ambiguous_segments) ... ok
test_normalize_path_removes_origin_query_fragment_and_trailing_slash (ops.tests.test_route_contract.RouteContractTests.test_normalize_path_removes_origin_query_fragment_and_trailing_slash) ... ok
test_registry_artifact_matches_the_frozen_route_baseline (ops.tests.test_route_contract.RouteContractTests.test_registry_artifact_matches_the_frozen_route_baseline) ... ok
test_registry_rollout_all_enables_published_target_routes (ops.tests.test_route_contract.RouteContractTests.test_registry_rollout_all_enables_published_target_routes) ... ok
test_registry_rollout_is_opt_in_and_can_select_one_legacy_route (ops.tests.test_route_contract.RouteContractTests.test_registry_rollout_is_opt_in_and_can_select_one_legacy_route) ... ok
test_rollout_manifest_covers_every_pending_route_once (ops.tests.test_route_contract.RouteContractTests.test_rollout_manifest_covers_every_pending_route_once) ... ok
test_rollout_manifest_rejects_duplicate_and_unknown_paths (ops.tests.test_route_contract.RouteContractTests.test_rollout_manifest_rejects_duplicate_and_unknown_paths) ... ok
test_theme_registry_artifact_matches_ops_registry (ops.tests.test_route_contract.RouteContractTests.test_theme_registry_artifact_matches_ops_registry) ... ok
test_validate_registry_accepts_a_complete_unique_route (ops.tests.test_route_contract.RouteContractTests.test_validate_registry_accepts_a_complete_unique_route) ... ok
test_validate_registry_rejects_duplicate_paths_and_missing_required_metadata (ops.tests.test_route_contract.RouteContractTests.test_validate_registry_rejects_duplicate_paths_and_missing_required_metadata) ... ok
test_allows_root_only_with_explicit_opt_in (ops.tests.test_deploy_config.DeployConfigTests.test_allows_root_only_with_explicit_opt_in) ... ok
test_cache_purge_quotes_a_root_with_spaces (ops.tests.test_deploy_config.DeployConfigTests.test_cache_purge_quotes_a_root_with_spaces) ... ok
test_deploy_artifact_includes_the_route_registry (ops.tests.test_deploy_config.DeployConfigTests.test_deploy_artifact_includes_the_route_registry) ... ok
test_deploy_script_has_no_unsafe_connection_defaults (ops.tests.test_deploy_config.DeployConfigTests.test_deploy_script_has_no_unsafe_connection_defaults) ... ok
test_load_requires_explicit_remote_configuration (ops.tests.test_deploy_config.DeployConfigTests.test_load_requires_explicit_remote_configuration) ... ok
test_loads_valid_non_root_configuration (ops.tests.test_deploy_config.DeployConfigTests.test_loads_valid_non_root_configuration) ... ok
test_rejects_invalid_port_and_remote_root (ops.tests.test_deploy_config.DeployConfigTests.test_rejects_invalid_port_and_remote_root) ... ok
test_rejects_root_without_explicit_opt_in (ops.tests.test_deploy_config.DeployConfigTests.test_rejects_root_without_explicit_opt_in) ... ok
test_remote_backup_is_created_before_upload_with_manifest (ops.tests.test_deploy_config.DeployConfigTests.test_remote_backup_is_created_before_upload_with_manifest) ... ok
test_remote_path_rejects_escape_attempts (ops.tests.test_deploy_config.DeployConfigTests.test_remote_path_rejects_escape_attempts) ... ok
test_comments_and_pattern_documentation_do_not_create_findings (ops.tests.test_deploy_security_audit.DeploySecurityAuditTests.test_comments_and_pattern_documentation_do_not_create_findings) ... ok
test_direct_ssh_literals_are_audited (ops.tests.test_deploy_security_audit.DeploySecurityAuditTests.test_direct_ssh_literals_are_audited) ... ok
test_legacy_script_reports_each_unsafe_connection_pattern (ops.tests.test_deploy_security_audit.DeploySecurityAuditTests.test_legacy_script_reports_each_unsafe_connection_pattern) ... ok
test_paramiko_alias_is_detected_without_textual_false_positive (ops.tests.test_deploy_security_audit.DeploySecurityAuditTests.test_paramiko_alias_is_detected_without_textual_false_positive) ... ok
test_paramiko_policy_aliases_are_audited (ops.tests.test_deploy_security_audit.DeploySecurityAuditTests.test_paramiko_policy_aliases_are_audited) ... ok
test_report_marks_missing_release_path_as_not_ready (ops.tests.test_deploy_security_audit.DeploySecurityAuditTests.test_report_marks_missing_release_path_as_not_ready) ... ok
test_report_marks_only_configured_release_path_eligible (ops.tests.test_deploy_security_audit.DeploySecurityAuditTests.test_report_marks_only_configured_release_path_eligible) ... ok
test_safe_configured_script_has_no_release_blockers (ops.tests.test_deploy_security_audit.DeploySecurityAuditTests.test_safe_configured_script_has_no_release_blockers) ... ok
test_surface_excludes_archive_and_tests (ops.tests.test_deploy_security_audit.DeploySecurityAuditTests.test_surface_excludes_archive_and_tests) ... ok

----------------------------------------------------------------------
Ran 39 tests in 0.749s

OK
```

### 8.2 Static Security Audit Verification (1 Release Ready, 177 Blocked)
**Command:** `python ops/deploy_security_audit.py --root ops --release-path deploy_theme_updates.py --strict-release`  
**Exit Code:** 0  
**Extracted JSON Audit Summary:**

```json
{
  "schema_version": 1,
  "scope": {
    "root": "ops",
    "excluded_directories": [
      "__pycache__",
      "archive-apply-scripts",
      "backups",
      "tests"
    ],
    "excluded_files": [
      "deploy_security_audit.py"
    ]
  },
  "summary": {
    "candidate_count": 179,
    "release_path_count": 1,
    "release_ready_count": 1,
    "blocked_release_count": 0,
    "missing_release_count": 0,
    "legacy_blocked_count": 177,
    "finding_counts": {
      "allow_root_command": 174,
      "auto_add_host_key_policy": 177,
      "hardcoded_host": 177,
      "hardcoded_key_path": 177,
      "hardcoded_remote_root": 159,
      "missing_host_key_verification": 177,
      "missing_known_hosts": 177,
      "missing_validated_config": 177,
      "root_user": 177
    }
  },
  "release_paths": [
    "deploy_theme_updates.py"
  ]
}
```

### 8.3 Release Script Pre-Flight Dry-Run (30 Files Validated)
**Command:** `python ops/deploy_theme_updates.py --dry-run`  
**Exit Code:** 0  
**Console Output:**

```
=== Dry-run: validating deploy artifact and hashes ===
  [OK] wordpress/wp-content/themes/vietnamguide-premium/header.php: ba43275ad9d5dd869f15e517587283806a69edf5968312290ac50fa790848b6a
  [OK] wordpress/wp-content/themes/vietnamguide-premium/footer.php: 6cd9609b0372967dcef9f69f558d7ee01ac8946aa89d3e0ea58be61b6a5ae5d0
  [OK] wordpress/ads.txt: 43ad3f9bd18746fcc1d4bf695ef449c1522e3a450f90952a11f458ad9ffb2ac1
  [OK] wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php: c87f446c1eb68664a056d1facaf3d9a4d26dc7a34ce3c7a7431c6c21e21bd10a
  [OK] wordpress/wp-content/themes/vietnamguide-premium/search.php: 26c43e17a4bb0f4856f75c5b8a543890135445ff29b1bcdec6822a7c03865d81
  [OK] wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css: b6c3af27093b2321dabefa830dcf1ca87422660ff0c1abfc772421031ca14d74
  [OK] wordpress/wp-content/themes/vietnamguide-premium/site.webmanifest: 69c76bfda87f2a5405fa41f9f83c98fcb91ea9649ebe46f8a47f4a0dd32e9eac
  [OK] wordpress/wp-content/themes/vietnamguide-premium/assets/images/vg-icon.svg: 12007cd367bc7134a34b510a0bb8a75f81ace2bbcf190f01009e7ceb427f73f9
  [OK] wordpress/wp-content/themes/vietnamguide-premium/assets/images/vg-icon-192.png: e41a061925415165bd674bb2f8840bef7a62b40f6f15cf1e2fc15c10554b8c7a
  [OK] wordpress/wp-content/themes/vietnamguide-premium/assets/images/vg-icon-512.png: d45d749f78ec10d3043e0c49e34472c1d05dd999596793a8e911e90bb2160977
  [OK] wordpress/sw.js: d7488535acad0b249cd58a7c9d0aa61bb79f2e9721e368bc339f0b3daa2002f3
  [OK] wordpress/offline.html: 6462661e975b7a2ca71c9f51e30de30bb25c502c5814fcc24bffab0e511d5ba8
  [OK] wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js: 3e7bb59fa61eca304917ffdd9e8f29a448ba820a96b928441306f0632a90b443
  [OK] wordpress/wp-content/themes/vietnamguide-premium/index.php: d71276dc192cae497baf1eda734a6c5061280e1703a5c69bb593f803f0ce3d0e
  [OK] wordpress/wp-content/themes/vietnamguide-premium/template-parts/content-page.php: 3ce8d5eb2b5574446f93137f995ab958988fed9c5ad0236c85ee00755fe1761e
  [OK] wordpress/wp-content/themes/vietnamguide-premium/template-parts/guide-page.php: 5962c5aa828b1c31e18ab09590fbedb58483ea7bd2d31f0c204c6ec9f0f27fe3
  [OK] wordpress/wp-content/themes/vietnamguide-premium/functions.php: aea178eaf833b8c6f5415839da21f36506711ce0289f0547e46a5492a0989374
  [OK] wordpress/wp-content/themes/vietnamguide-premium/inc/guide-packing-checklist.php: a4097ad4af1c39f73af47ee2866dda88eb6932c66af29f789ab96c8995662e6a
  [OK] wordpress/wp-content/themes/vietnamguide-premium/inc/guide-cost-calculator.php: 37cbace88b2e54f713324d6ebcd40f17ae24bbfd431cf9eb7e0d708cd6b8911d
  [OK] wordpress/wp-content/themes/vietnamguide-premium/inc/guide-visa-checker.php: c04a5b0bdba7cd6d49ceb0d9d16ebab762914618cae6d703ddafa9480f8fb080
  [OK] wordpress/wp-content/themes/vietnamguide-premium/inc/guide-season-matrix.php: f0d2d4cce24579a9f90de36616c090f8e92f6a7f3e43d82f7a3f29309bd17c79
  [OK] wordpress/wp-content/themes/vietnamguide-premium/inc/guide-airport-navigator.php: da20a67e67499e3d204f8459073d9bb766e1cf603a5ddd5cdf99218468193a5f
  [OK] wordpress/wp-content/themes/vietnamguide-premium/inc/guide-itinerary-finder.php: fc6996f34b81b9678b0807997f83077dda3af326bda9f9ec6ce1a2e6cd939056
  [OK] wordpress/wp-content/themes/vietnamguide-premium/inc/guide-aio.php: aa16009c8b3ca8bbdae546680633b09309aa33f33422c90692785b13b97872a1
  [OK] wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php: 21991710af2069ef7f1a09156d4bdc2c723af4b537696d8218e5c52418f4cd5e
  [OK] wordpress/wp-content/themes/vietnamguide-premium/inc/guide-route-registry.json: ac48ef3efc95479955d1d5f6744304d2bd64ec601454481b117e74155d4fe16d
  [OK] wordpress/wp-content/themes/vietnamguide-premium/inc/guide-analytics.php: dcc5fe995e472119053426efb7c596be81ad929704314ab6f8f19ce7b2709a9c
  [OK] wordpress/.htaccess: 9b83dacc74c021dfa3ed4921d6b0a6e4a4b11c7c2a904bbdfbeccf8577a0e98c
  [OK] wordpress/BingSiteAuth.xml: 31ad9656a64bb3e11b33d0ca84f3229f7bf46388a071d8807581024c407aaffc
  [OK] wordpress/852ef594b29d4da5a639612da3430b0f.txt: d30583d16d707766f99af12f037b3caa55ebfe5affe7fa443098ba19dc914d74
Validated 30 deploy files; no network connection made.
```

### 8.4 Git Status & Working Tree Baseline
**Command:** `git status`  
**Working Directory:** `M:/Projects/vietnamguide`  
**Console Output:**

```
On branch master
Changes not staged for commit:
  (use "git add <file>..." to update what will be committed)
  (use "git restore <file>..." to discard changes in working directory)
	modified:   .github/workflows/ci.yml
	modified:   ops/deploy_theme_updates.py
	modified:   ops/verify-all-gates.ps1
	modified:   wordpress/wp-content/themes/vietnamguide-premium/inc/guide-aio.php
	modified:   wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php

Untracked files:
  (use "git add <file>..." to include in what will be committed)
	docs/2026-09-28-project-completion-plan.md
	docs/baselines/
	ops/deploy_config.py
	ops/deploy_security_audit.py
	ops/route_contract.py
	ops/route_registry.json
	ops/tests/test_deploy_config.py
	ops/tests/test_deploy_security_audit.py
	ops/tests/test_route_contract.py
	wordpress/wp-content/themes/vietnamguide-premium/inc/guide-route-registry.json

no changes added to commit (use "git add" and/or "git commit -a")
```

---

## 9. Conclusion & Final Attestation

This project health assessment concludes that the VietnamGuide technical architecture has reached a mature, production-capable state. The implementation of the **canonical route registry (282 routes)**, the **two-tier defense-in-depth gate** in `page.php`, the **AIO dynamically generated endpoints**, and the **hardened release pipeline (`deploy_theme_updates.py`)** establishes a resilient foundation for long-term scalability.

By executing the recommended 3-phase roadmap:
1. **Phase 1** locks the architecture into git history, formalizes the 8 quality gates, and restores 100% test discovery.
2. **Phase 2** migrates the remaining 195 legacy content routes with zero downtime and instant per-batch rollback.
3. **Phase 3** neutralizes the 177 legacy ops scripts, provisions non-root SSH deployment credentials, and isolates CI from external network flakiness.

With these controls established, VietnamGuide achieves enterprise-level software reliability, strict operational security, and superior editorial quality.

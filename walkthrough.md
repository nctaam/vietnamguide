# VietnamGuide Platform — Comprehensive Architecture & Quality Walkthrough

VietnamGuide is a production-grade digital publishing and travel intelligence platform engineered for Vietnam travel logistics, curated pacing routes, and empirical ground truth.

---

## 1. Core Architecture Principles

1. **Editorial Travel Intelligence (Zero Feature Creep):**
   - Pure, uncompromised performance with semantic HTML5, zero unnecessary bloated plugins, and zero external frontend runtime frameworks.
   - Built on a bespoke WordPress theme (`wordpress/wp-content/themes/vietnamguide-premium`).
2. **Canonical Route Registry & Two-Tier Defense-in-Depth:**
   - 282 published content routes formally cataloged in `ops/route_registry.json` and mirrored in `inc/guide-route-registry.json`.
   - Two-tier template gate in `page.php`: Routing allowlist gate (`vg_is_guide_experience_page()`) and Content contract gate (`vg_build_guide_context()`).
   - 100% coverage: All 282 routes active in the modern Guide Shell via `inc/guide-rollout.php` (`VG_GUIDE_REGISTRY_ROLLOUT = true`).
   - Zero 404 risk, zero fatal template crash risk (graceful fallback to `template-parts/content-page.php`).
3. **Google Stitch Design System & Token Harmonization:**
   - Visual tokens codified in `docs/DESIGN.md` from Google Stitch project `projects/2641938659919405436`.
   - Heritage color palette: Indochine Deep Navy (`#0F2537`), Hoi An Saffron Gold (`#C99446`), Warm Rice Paper Canvas (`#FAF8F5`), and Alabaster Surface (`#FFFFFF`).
   - Tri-Stack Typography: Playfair Display (editorial display), Be Vietnam Pro (clean body reading), and JetBrains Mono (tabular telemetry and financial benchmarks).
4. **6 Interactive Field Decision Engines:**
   - Visa Eligibility Checker (`[vg_visa_checker]` / `/plan/vietnam-evisa/`)
   - Daily Travel Cost & Budget Calculator (`[vg_cost_calculator]`, `[vg_route_budget]` / `/costs/vietnam-travel-cost/`)
   - Regional Weather & Climate Matrix (`[vg_season_matrix]` / `/plan/best-time-to-visit-vietnam/`)
   - Airport Navigator & Terminal Transit Shield (`[vg_airport_navigator]`)
   - Interactive Itinerary Finder (`[vg_itinerary_finder]`)
   - Field Packing Checklist Engine (`[vg_packing_checklist]`)
5. **Robust Ops Security & Clean Automation:**
   - All legacy one-off scripts safely archived in `ops/archive-apply-scripts/`.
   - Active operational runner `ops/deploy_theme_updates.py` backed by `ops/deploy_config.py` enforcing environment-only credentials, strict `RejectPolicy()` known-hosts verification, pre-upload remote backups, and 33-file SHA-256 parity validation.
   - Automated static security gating via `ops/deploy_security_audit.py` (Gate 8).

---

## 2. The 8 Master Quality Gates (`ops/verify-all-gates.ps1`)

All pull requests, master commits, and deployment runners must pass all 8 quality gates locally and on GitHub Actions CI:

| Gate | Verification Suite | Script / Target | Focus & Invariants |
| :---: | :--- | :--- | :--- |
| **1** | **Anti-AI Slop Quality Engine** | `ops/verify-anti-ai-slop.ps1` | Validates 12 tiers of clichés, filler tropes, meta-commentary, and adjective clusters via `ops/anti_ai_slop_linter.py`. |
| **2** | **Core MU-Plugin Invariant** | `ops/verify-core-mu-plugin.ps1` | Asserts SHA-256 fingerprint on `vietnamguide-core.php` and rejects all AST safety mutations. |
| **3** | **Gutenberg Core Block Patterns** | `ops/verify-core-block-patterns.ps1` | Validates all 12 block patterns in `patterns/` for syntax and inserter headers. |
| **4** | **Homepage Theme & CSS** | `ops/verify-homepage-theme.ps1` | Verifies semantic structure, image dimensions/sources, responsive CSS tokens, and `qa/homepage-preview.html`. |
| **5** | **Interactive Shortcodes & A11y** | `ops/tests/test_interactive_shortcodes.py` | 26 unit tests verifying ARIA roles, live telemetry, search barometer, and keyboard navigation. |
| **6** | **Route Registry & AIO Fallback** | `ops/tests/test_route_contract.py` | 20 unit tests validating 282 route schema contracts, classification rules, and `/llms.txt` generation. |
| **7** | **Deploy Config & SSH Trust** | `ops/tests/test_deploy_config.py` & `test_deploy_security_audit.py` | 19 unit tests enforcing environment variable isolation, path containment, and SSH security. |
| **8** | **Static Security Surface Audit** | `ops/deploy_security_audit.py` | AST audit ensuring zero hardcoded IPs, keys, or `AutoAddPolicy` in active deployment scripts. |

---

## 3. Comprehensive Unit Test Suite (`ops/tests/`)

The automated unit test suite covers **120 tests** discoverable natively via Python `unittest`:

```powershell
python -m unittest discover -s ops/tests -p 'test_*.py' -v
```

1. `test_anti_ai_slop.py` (34 tests): Anti-AI Slop rules, human-likeness scoring ($HLS = 100$), evidence density ($EDI$), and coefficient of variation ($CV$).
2. `test_deploy_config.py` (10 tests): `DeployConfig` parameter validation, port ranges, escape rejection, and backup creation.
3. `test_deploy_security_audit.py` (9 tests): AST pattern detection, policy alias tracking, and candidate discovery.
4. `test_interactive_shortcodes.py` (26 tests): Shortcode outputs, schema metadata, ARIA live states, and search barometer.
5. `test_policy_cadence.py` (11 tests): Prose rhythm and regulatory data on Contact, Privacy, Editorial, and Source policies.
6. `test_rollout_manager.py` (10 tests): Rollout batch generation and `wp-config.php` code injection.
7. `test_route_contract.py` (20 tests): Canonical route registry schema, path normalization, and AIO inventory fallback.

---

## 4. Production Deployment & Live Status

- **Production Host:** `66.42.48.146:2209` (LiteSpeed Enterprise Web Server)
- **Deployment Script:** `ops/deploy_theme_updates.py` (33 synchronized theme and root files with 100% SHA-256 parity verification)
- **Cache Management:** LiteSpeed cache automatically purged via `wp litespeed-purge all`
- **Sitemap Index:** 302 published URLs (282 content guides + 20 hubs, tools, and policy pages)
- **Live Verification:**
  - Homepage: `https://vietnamguide.net/` (HTTP 200 OK — Live Ground Telemetry, Search Barometer & 3 Decision Engines)
  - Regional Gazetteer: `https://vietnamguide.net/destinations/` (HTTP 200 OK)
  - Signature Routes: `https://vietnamguide.net/itineraries/` (HTTP 200 OK)
  - Comparison Hub: `https://vietnamguide.net/compare/` (HTTP 200 OK)
  - Practical Planning: `https://vietnamguide.net/plan/` (HTTP 200 OK)
  - Cost Calculator: `https://vietnamguide.net/costs/vietnam-travel-cost/` (HTTP 200 OK)
  - 404 Route Recovery: `https://vietnamguide.net/404-test/` (HTTP 404 Not Found — Cartographic Exception Recovery Grid)

# Project: VietnamGuide Adversarial Audit & System Hardening

## Architecture
- **Theme**: `wordpress/wp-content/themes/vietnamguide-premium/`
  - Core routing & fallback: `page.php`, `inc/guide-routing.php`, `inc/guide-rollout.php`
  - Content processing & TOC engine: `inc/guide-content.php`, `template-parts/guide-page.php`
  - Interactive Shortcodes: `inc/guide-interactive-map.php`, `inc/guide-photo-dispatch.php`, 6 travel toolkits
  - Stylesheets: `assets/css/homepage.css`, `assets/css/guide-experience.css`, `assets/css/guide-patterns.css`
  - Hub Integration: `front-page.php` (#cartography-desk and #visual-dispatches integrated with zero-H2)
- **Ops & Automation**: `ops/`
  - Master Verification: `ops/verify-all-gates.ps1` (8/8 quality gates PASS)
  - Deployment engine: `ops/deploy_theme_updates.py` (60 reconciled files, offline dry-run verified)
  - Static Security Scanner: `ops/deploy_security_audit.py` (0 findings, 0 release blockers)
  - Rollout manager: `ops/rollout_manager.py` (195 legacy routes across Batches 1, 2, 3 verified)
  - Anti-AI Slop engine: `ops/anti_ai_slop_linter.py` (0 Tier 1 clichés, Mean HLS: 100.0/100)
- **Route Registry**: `ops/route_registry.json` & `inc/guide-route-registry.json` (282 canonical routes, 100% SHA-256 byte parity)
- **Unit Test Suite**: `ops/tests/` (10 test modules, 173 unit tests, 100% pass)

## Feature Inventory
| # | Feature | Description | Milestone | Status | Source |
|---|---------|-------------|-----------|--------|--------|
| F1 | Adversarial Project Audit Report | Comprehensive audit report covering theme architecture vs WordPress VIP/Roots, two-tier fail-closed routing fallbacks, 282-route registry performance, ops deployment security, and CWV/responsive bottlenecks (`docs/ADVERSARIAL_PROJECT_AUDIT.md`) | M1 | DONE | Survey Arch & Ops Explorers |
| F2 | Ops Deployment Security Hardening & Parity | Reconciled `DEPLOY_FILES` in `ops/deploy_theme_updates.py` to eliminate deployment drift (expanded to 60 theme files including `guide-interactive-map.php`, `guide-photo-dispatch.php`, `page.php`, 13 patterns), hardened staging/rollback recommendations, 0 findings in `ops/deploy_security_audit.py` | M2 | DONE | Survey Ops Explorer |
| F3 | UI/UX Cartography & Photo Dispatch Hub Integration | Validated and integrated `[vg_interactive_map]` and `[vg_photo_dispatch]` into `front-page.php` with zero-H2 TOC integrity strictly enforced across all components | M3 | DONE | Survey UI Explorer |
| F4 | Unit Test Suite Expansion & Master Quality Gate Preservation | Expanded test suite to 173 unit tests (exceeding 158+ requirement), preserved 100% pass on all 8 Quality Gates (`ops/verify-all-gates.ps1`) with Exit Code 0 | M4 | DONE | Survey UI/Ops/Arch |

## Milestones
| # | Name | Scope | Dependencies | Status | Key Output |
|---|------|-------|-------------|--------|------------|
| M1 | Architecture & Routing Adversarial Audit | Author `docs/ADVERSARIAL_PROJECT_AUDIT.md` | None | DONE | 764-line whitepaper, 14 findings, empirical benchmarks |
| M2 | Ops Deployment Security Hardening & Parity | Reconcile `DEPLOY_FILES` in `ops/deploy_theme_updates.py` | None | DONE | 60 tracked theme files, 0 security findings |
| M3 | UI/UX Shortcodes Hub Integration & Zero-H2 Verification | Integrate shortcodes in `front-page.php`, zero-H2 invariant | None | DONE | #cartography-desk & #visual-dispatches, 0 H2 tags |
| M4 | Unit Test Suite Expansion & 8/8 Gate Preservation | Add contract tests in `ops/tests/` (173 tests), 8/8 gates | M1, M2, M3 | DONE | 173 tests PASS, 8/8 gates PASS, Stage 69 logged |

## Gate Verdict
- `GATE_STATUS.md`: **PASS** (Reviewer 1 APPROVE, Reviewer 2 APPROVE, Challenger 1 APPROVE, Challenger 2 APPROVE, Auditor CLEAN).

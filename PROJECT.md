# Project: VietnamGuide Deep Hardening & 282-Route Guide Shell Rollout

## Architecture
- **Theme**: `wordpress/wp-content/themes/vietnamguide-premium/`
  - Core routing & fallback: `page.php`, `inc/guide-routing.php`, `inc/guide-rollout.php` (VG_GUIDE_REGISTRY_ROLLOUT = true, 100% 282 routes active)
  - Content processing & TOC engine: `inc/guide-content.php`, `template-parts/guide-page.php`
  - Structured Data & SEO: `inc/guide-seo.php`, `inc/image-dimensions.php` (622 registered dimensions, CLS = 0)
  - Interactive Shortcodes: `inc/guide-interactive-map.php`, `inc/guide-photo-dispatch.php`, 6 travel toolkits
  - Stylesheets: `assets/css/homepage.css` (harmonized reduced-motion rules), `assets/css/guide-experience.css`, `assets/css/guide-patterns.css`
  - Navigation Components: Sticky Reading Spine TOC, Tactical Dock (`#vg-floating-dock`), Fact-Checked Trust Badge (`aside.vg-guide-trust`), E-E-A-T Author Card (`[vg_editorial_proof]`)
- **Ops & Automation**: `ops/`
  - Master Verification: `ops/verify-all-gates.ps1` (8/8 quality gates PASS, Exit Code 0)
  - Deployment engine: `ops/deploy_theme_updates.py` (atomic staging `.tmp.<uuid>`, in-memory SHA-256 snapshot, atomic rename `mv -f`)
  - Static Security Scanner: `ops/deploy_security_audit.py` (0 findings, 0 release blockers)
  - Rollout manager: `ops/rollout_manager.py` (282 canonical routes, 100% manifest integrity)
  - Anti-AI Slop engine: `ops/anti_ai_slop_linter.py` (0 Tier 1 clichés, Route Registry HLS 100.0, UI HLS 94.81 >= 85)
- **Route Registry**: `ops/route_registry.json` & `inc/guide-route-registry.json` (282 canonical routes, 100% SHA-256 byte parity)
- **Unit Test Suite**: `ops/tests/` (11 test modules, 205 unit tests, 100% pass)

## Feature Inventory
| # | Feature | Description | Milestone | Status | Source |
|---|---------|-------------|-----------|--------|--------|
| F1 | 282-Route Guide Shell Rollout & Navigation Experience | Activate Guide Shell across 282 canonical routes via `VG_GUIDE_REGISTRY_ROLLOUT = true`, ensure consistent display of TOC spine, tactical dock, trust badge, author card, fail-closed two-tier routing in `page.php`, and zero-H2 protection in `inc/*.php` | M1 | DONE | Survey Arch Explorer |
| F2 | Core Web Vitals, Schema.org JSON-LD & Technical SEO | Schema.org JSON-LD across all 4 guide classifications (Destination, Practical, Comparison, Itinerary), CLS = 0 via `image-dimensions.php` (622 entries), zero webfonts, WCAG AAA accessibility, dedicated `test_guide_schema.py` (26 tests) | M2 | DONE | Survey SEO/CWV Explorer |
| F3 | Deployment Security Hardening & Zero-Drift Protection | In-memory snapshotting, remote staging (`.tmp.<uuid>`), pre-promotion SHA-256 verification, atomic POSIX rename in `deploy_theme_updates.py`, 60/60 files parity, 0 blockers in `deploy_security_audit.py`, 19 tests in `test_deploy_config.py` | M3 | DONE | Survey Ops Explorer |
| F4 | Master Quality Gate & Unit Test Suite Preservation | 100% pass across all 8 Quality Gates (`ops/verify-all-gates.ps1`), 205 unit tests in `ops/tests/` (100% pass), 0 clichés, HLS >= 85, Stage 70/73 logged in `ops/verification-log.md` | M4 | DONE | Survey Ops/Arch/SEO |

## Milestones
| # | Name | Scope | Dependencies | Status | Key Output |
|---|------|-------|-------------|--------|------------|
| M1 | 282-Route Guide Shell & Navigation Experience | Verify/harden `guide-rollout.php`, `guide-routing.php`, `page.php`, `guide-page.php`, zero-H2 invariant across all `inc/*.php`, and CSS reduced-motion harmony | None | DONE | 282 routes active, fail-closed routing verified, 0 H2 in inc/*.php, reduced-motion verified |
| M2 | Schema.org JSON-LD & CWV Hardening | Author `ops/tests/test_guide_schema.py` verifying all 4 guide types (Destination, Comparison, Itinerary, Practical), verify 100% image dimensions resolution (CLS=0) | None | DONE | Dedicated schema test suite (26 tests PASS), CLS = 0, Google Rich Results compliance |
| M3 | Deployment Security Hardening & TOCTOU Defense | Harden `ops/deploy_theme_updates.py` with in-memory byte snapshotting, remote temp staging, and atomic promotion; add atomic staging test to `ops/tests/test_deploy_config.py` | None | DONE | TOCTOU-resistant deployment engine, 60/60 disk parity, 0 security blockers, 19/19 tests PASS |
| M4 | Master Quality Gate Preservation & Stage 70 Log | Run all 8 Quality Gates (`verify-all-gates.ps1`), full 205 unit test suite, Anti-AI Slop linter, append Stage 70 log entry in `ops/verification-log.md` | M1, M2, M3 | DONE | 8/8 gates PASS, 205/205 tests PASS, HLS >= 85, Stage 70 logged |

## Gate Verdict
- `GATE_STATUS.md`: **PASS** (Reviewer 1 APPROVE, Reviewer 2 APPROVE, Challenger 1 APPROVE, Challenger 2 APPROVE, Forensic Auditor CLEAN).

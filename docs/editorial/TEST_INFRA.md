# VietnamGuide E2E Linguistic & Anti-AI Slop Test Infrastructure

**Document Code:** VG-E2E-TEST-INFRA-01  
**Version:** 1.0.0 (2026-10-06)  
**Author:** Subagent `test_writer_e2e_1` (E2E Linguistic & Anti-Slop Testing Track)  
**Scope:** English Standardization, Anti-AI Slop Cleansing, Ground-Truth Density & Contract Invariants  
**Test Suite Path:** `ops/tests/test_linguistic_e2e.py`  

---

## 1. Executive Summary & Philosophy

VietnamGuide operates under a strict editorial constitution: travel content must meet the standards of international long-form travel journalism (FT Weekend, Monocle, Afar). Generic synthetic cheerleading ("nestled in the heart of", "rich tapestry", "hidden gem", "breathtaking views") degrades traveler trust and obscures vital logistical facts.

The E2E Linguistic & Anti-AI Slop Test Suite (`ops/tests/test_linguistic_e2e.py`) is an opaque-box, requirement-driven verification harness designed to guarantee:
1. **Zero AI Clichés**: Absolute elimination of Tier 1 banned patterns across all 282 routes, UI templates, toolkits, and block patterns.
2. **Boundary Resilience**: Robust handling of extreme inputs, empty states, and flawless preservation of Vietnamese diacritics and typography.
3. **Cross-Feature Harmonization**: Total geographical naming consistency, official administrative URLs (`evisa.xuatnhapcanh.gov.vn`), and frozen contract preservation.
4. **Real-World Ground Truth**: Mathematical human-likeness score ($HLS \ge 85$) and quantifiable logistical metrics (distance, transit time, pricing, microclimates).

---

## 2. Test Architecture: The 4-Tier Model

The E2E test suite is organized into 4 distinct testing tiers:

```
┌────────────────────────────────────────────────────────────────────────┐
│               Tier 1: Feature Coverage (0 Clichés)                     │
│  - 282 Route Registry titles & descriptions (ops & inc bundles)        │
│  - Homepage templates (front-page, header, footer, homepage-data)      │
│  - 6 Interactive Travel Toolkits (Visa, Cost, Season, Airport, etc.)   │
│  - Utility pages (search.php, 404.php, offline.html)                   │
│  - 13 Gutenberg Block Patterns (patterns/*.php)                        │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
┌───────────────────────────────────▼────────────────────────────────────┐
│               Tier 2: Boundary & Corner Cases                          │
│  - Empty string & whitespace input handling (zero false crash)         │
│  - Non-ASCII & Vietnamese diacritics preservation (Hà Nội, Đà Nẵng)    │
│  - Extreme string lengths (micro-titles to 1,000+ word deep guides)    │
│  - Numerical & currency formatting standards (VND 25,500, FX 25,500)   │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
┌───────────────────────────────────▼────────────────────────────────────┐
│      Tier 3: Cross-Feature Combinations & Invariants                   │
│  - Geographical naming consistency (Ha Long Bay, Da Nang, Da Lat, etc.)│
│  - Official E-Visa portal URL (evisa.xuatnhapcanh.gov.vn)              │
│  - Route Registry byte parity (100% SHA-256 match ops <-> inc)         │
│  - Frozen contracts (Route 1 vietnam-evisa-guide verbatim title)       │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
┌───────────────────────────────────▼────────────────────────────────────┐
│      Tier 4: Real-World Scenarios & Quality Thresholds                 │
│  - Human-Likeness Score threshold: HLS >= 85 across all routes         │
│  - Ground-truth factual density (distance, transit, VND, climate)      │
│  - Absence of synthetic contrast and formulaic conversational hedges   │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Tier Specifications & Thresholds

### Tier 1: Feature Coverage (Zero Cliché Mandate)
- **Target Assets:**
  - 282 Route Registry entries (`ops/route_registry.json` and `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-route-registry.json`).
  - Homepage UI: `front-page.php`, `header.php`, `footer.php`, `inc/homepage-data.php`.
  - 6 Interactive Toolkits: `guide-visa-checker.php`, `guide-cost-calculator.php`, `guide-season-matrix.php`, `guide-airport-navigator.php`, `guide-packing-checklist.php`, `guide-itinerary-finder.php`.
  - Utility Templates: `search.php`, `404.php`, `wordpress/offline.html`.
  - 13 Gutenberg Patterns: `patterns/*.php`.
- **Pass Threshold:** Exactly **0** Tier 1 violations detected across all checked strings.
- **Linter Engine:** Evaluated via `ops/anti_ai_slop_linter.py` regex catalog (`TIER1_PATTERNS`, 48 distinct patterns).

### Tier 2: Boundary & Corner Cases
- **Empty & Whitespace States:** Evaluates empty string `""`, whitespace `"   "`, newline sequences, and tabs. The analysis engine must return a graceful report (`passed = True`, `tier1_count = 0`) without raising exceptions.
- **Diacritics & UTF-8 Integrity:** Evaluates text containing Vietnamese tone marks and diacritics (`Hà Nội`, `Đà Nẵng`, `Sài Gòn`, `triều cường`, `nồm`, `Quốc lộ 1A`). The engine must not corrupt multibyte characters or mangle syllable counts.
- **Extreme Lengths:** Evaluates both ultra-short strings (1–3 tokens) and long articles (> 800 words), verifying that standard neutral baselines apply to short snippets while full cadence scoring activates on long text.
- **Numerical & Currency Conventions:** Validates Vietnamese Dong formatting: `VND 25,500` or `25,500 VND` with comma separation for thousands. Verifies FX rate calibration (`USD_TO_VND = 25500`) in financial components.

### Tier 3: Cross-Feature Combinations & Terminology Invariants
- **Geographical Standardization:** Enforces canonical English travel standards across UI microcopy and Route Registry:
  - `Ha Long Bay` (never `Halong Bay`, `Halong`, or `Ha-long`)
  - `Da Nang` (never unspaced `Danang` or `Da-nang`)
  - `Da Lat` (never unspaced `Dalat`)
  - `Hoi An` (never unspaced `Hoian`)
  - `Nha Trang` (never unspaced `Nhatrang`)
  - `Phu Quoc` (never unspaced `Phuquoc`)
  - `Ho Chi Minh City` / `HCMC` / `Saigon` (used for Ga Saigon and historical contexts)
  - `Sa Pa` / `Sapa`
- **Official Portal Consistency:** Ensures all references to the Vietnamese electronic visa portal point exclusively to the official government domain:
  - Canonical URL: `https://evisa.xuatnhapcanh.gov.vn/`
  - Commercial or unofficial third-party visa services (`vietnamvisa.*`, `myvietnamvisa.*`) are strictly prohibited.
- **Registry Byte Parity & Frozen Contracts:**
  - SHA-256 byte parity between `ops/route_registry.json` and theme `inc/guide-route-registry.json` must be 100% identical.
  - Frozen contract for Route 1 (`plan/vietnam-evisa`): title must strictly equal `'Vietnam E-Visa Guide: Official Portal, Fees and Mistakes'`.
  - Total route count must equal exactly **282**.

### Tier 4: Real-World Scenarios & Quality Thresholds
- **Human-Likeness Score ($HLS$):**
  - All 282 route entries must score $HLS \ge 85 / 100$.
- **Ground-Truth Density:**
  - Route descriptions must feature verifiable, real-world travel metrics:
    - **Distance Metrics**: `km`, `meters`, `elevation`.
    - **Transit Metrics**: `hours`, `minutes`, `sleeper train`, `bus`, `expressway`, `ferry`.
    - **Financial Metrics**: `VND`, `$ USD`, `fares`, `fees`, `costs`.
    - **Climate Metrics**: `season`, `dry`, `rain`, `monsoon`, `humidity`, `°C`.
- **Rhetoric Purity:** Zero instances of synthetic contrast (Tier 9: "the question is not X, the question is Y"), binary parallelism (Tier 10), or corporate marketing fluff (Tier 11).

---

## 4. Test Execution & Verification

### Running the E2E Test Suite
Execute the entire 4-tier suite via standard Python unittest:

```bash
python -m unittest ops/tests/test_linguistic_e2e.py
```

### Verbose Execution
```bash
python -m unittest ops/tests/test_linguistic_e2e.py -v
```

### Full Unit Test Suite Verification
To ensure no regressions across the 123 existing unit tests:

```bash
python -m unittest discover -s ops/tests -p "test_*.py"
```

---

## 5. Integration with Quality Gates

The E2E linguistic tests integrate into VietnamGuide's Master Quality Gate system:
- **Gate 1 (Anti-AI Slop & Cadence Verifier):** Verifies 0 Tier 1 clichés, cadence variation ($CV \ge 0.45$), and $HLS \ge 80$.
- **Gate 3 (Gutenberg Block Pattern Integrity):** Verifies all 13 patterns retain structural compliance.
- **Gate 5 (Zero H2 Hierarchy & Clean Microcopy):** Verifies template microcopy adheres to design system constraints.
- **Master Orchestrator:** `powershell -File ops/verify-all-gates.ps1`.

# Stage 34: Deep Ground-Truth Saturation (EDI >= 6.0), Anti-AI Slop Quality Engine v7.0 & Hub Page Ground-Truth Calibration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Elevate VietnamGuide's content quality and automated defense by upgrading the Anti-AI Slop Quality Engine to v7.0 (Tier 7 false-authority/sycophantic markers, local sentence cadence monotony window, passive voice density thresholds), calibrating hub pages (`/destinations/`, `/compare/`, `/plan/`) to achieve HLS = 100, and enriching the 8 remaining lowest-density long-form guides to achieve EDI >= 6.0 with zero feature creep.

**Architecture:** Python regex and statistical linter enhancements (`ops/anti_ai_slop_linter.py`) coupled with targeted WordPress/MariaDB SQL content enrichments containing verified 2026 admission tariffs, transit timetables, and regulatory decrees. Automated validation orchestrator enforces 100% pass rate across master CI/CD gates, 87 public HTTPS routes, and all 102 production sitemap URLs.

**Tech Stack:** Python 3.13 (`unittest`, `BeautifulSoup4`, `urllib`), PHP 8.2 / WordPress 6.x Core, MariaDB 10.11, OpenLiteSpeed web server, PowerShell 5.1 CI/CD test harness.

**Spec:** `docs/superpowers/plans/2026-09-12-deep-ground-truth-and-anti-slop-v7.md`

## Global Constraints

- **Zero Feature Creep:** No new custom post types, no new public URLs, no new interactive shortcodes, no new WordPress plugins, no new npm dependencies.
- **Single Source of Truth:** `M:\Projects\vietnamguide` on `master` branch. Never touch `D:\Documents\vietnamguide`.
- **Core MU-Plugin Invariant:** Hash `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` must remain untouched; all 16 AST safety mutations must be rejected.
- **Public Route Invariant:** All 87 public HTTPS routes must return HTTP 200 with full DOM integrity.
- **Production Server:** `66.42.48.146:2209`, user `root`, SSH key `C:\Users\NCTaam\.ssh\deploy_bot_key`. Webroot: `/usr/local/lsws/vietnamguide.net/html`.

---

## Tasks Breakdown

### Task 1: Anti-AI Slop Quality Engine v7.0 Upgrade

**Files:**
- Modify: `ops/anti_ai_slop_linter.py:120-145`, `ops/anti_ai_slop_linter.py:350-420`
- Test: `ops/tests/test-anti-ai-slop.py:160-220`

**Interfaces:**
- Consumes: `analyze_text(plain_text: str, source_name: str) -> dict`
- Produces: `report['tier7_count']`, `report['tier7_violations']`, `report['local_cadence_violations']`, `report['passive_ratio']`

- [ ] **Step 1: Write failing unit tests for Tier 7 patterns and local cadence monotony**

Add tests to `ops/tests/test-anti-ai-slop.py`:
```python
    def test_tier7_false_authority_and_conclusion_padding(self):
        text = (
            "It is no secret that Vietnam offers great street food. "
            "As any seasoned traveler knows, pho is best enjoyed on a low plastic stool. "
            "Needless to say, the broth takes ten hours to simmer. "
            "In conclusion, make no mistake about visiting Hanoi."
        )
        report = analyze_text(text, source_name="test-tier7")
        self.assertGreater(report.get('tier7_count', 0), 0)
        self.assertFalse(report['passed'])

    def test_local_cadence_monotony_detection(self):
        # 6 consecutive sentences with identical word count (12 words each) in narrative prose
        text = (
            "The ancient temple stands quietly beside the shimmering water of the wide lake. "
            "Local fishermen cast their nylon nets across the calm surface of the bay. "
            "Morning sunlight filters gently through the dense green canopy of coastal pine trees. "
            "Small wooden sampans drift lazily along the winding river toward the distant sea. "
            "Distant limestone mountains rise sharply into the misty horizon of the peaceful morning. "
            "Quiet village paths meander peacefully between the fertile green terraces of young rice."
        )
        report = analyze_text(text, source_name="test-monotony")
        self.assertGreater(len(report.get('local_cadence_violations', [])), 0)

    def test_passive_voice_density_threshold(self):
        # High passive density (> 20% passive)
        text = (
            "The tickets are sold at the gate. "
            "The baggage is checked by security. "
            "The passes are issued by officials. "
            "The luggage is handled with care."
        )
        report = analyze_text(text, source_name="test-passive-density")
        self.assertGreater(report.get('passive_ratio', 0.0), 0.15)
```

- [ ] **Step 2: Run test to verify it fails**

Run: `python ops/tests/test-anti-ai-slop.py`
Expected: FAIL with `KeyError: 'tier7_count'` or `AssertionError`.

- [ ] **Step 3: Implement Tier 7 patterns, local cadence monotony, and passive ratio in `ops/anti_ai_slop_linter.py`**

In `ops/anti_ai_slop_linter.py`:
1. Define `TIER7_PATTERNS`:
```python
TIER7_PATTERNS = [
    (r"\b(?:it(?:'s|\s+is)\s+no\s+secret\s+that)\b", "it's no secret that"),
    (r"\bas\s+(?:any\s+)?seasoned\s+travelers?\s+know[s]?\b", "as any seasoned traveler knows"),
    (r"\bneedless\s+to\s+say\b", "needless to say"),
    (r"\bit\s+goes\s+without\s+saying\b", "it goes without saying"),
    (r"\bsuffice\s+it\s+to\s+say\b", "suffice it to say"),
    (r"\bat\s+the\s+end\s+of\s+the\s+day\b", "at the end of the day"),
    (r"\bwhen\s+all\s+is\s+said\s+and\s+done\b", "when all is said and done"),
    (r"\bmake\s+no\s+mistake\b", "make no mistake"),
    (r"\btruth\b(?:,\s*|\s+)be\s+told\b", "truth be told"),
    (r"\ball\s+in\s+all\b", "all in all"),
    (r"\bin\s+conclusion\b|\bto\s+sum\s+up\b|\bwrapping\s+up\b|\bparting\s+thoughts\b|\bfinal\s+thoughts\b", "in conclusion / to sum up / final thoughts"),
]
```
2. In `analyze_text()`, evaluate `TIER7_PATTERNS` deducting 15 points per match (`S1_TIER7_AUTHORITY_SLOP`).
3. Add sliding window of 6 sentences calculating $CV_{local} = \sigma_{local} / \mu_{local}$. If $CV_{local} < 0.20$ and $N \ge 6$ on non-index pages, flag `S2_LOCAL_CADENCE_MONOTONY`.
4. Calculate `passive_ratio = len(passive_violations) / sentence_count`. If `passive_ratio > 0.15` and `sentence_count >= 5`, deduct 10 points.

- [ ] **Step 4: Run test to verify it passes**

Run: `python ops/tests/test-anti-ai-slop.py`
Expected: PASS (18/18 tests).

- [ ] **Step 5: Commit**

```bash
git add ops/anti_ai_slop_linter.py ops/tests/test-anti-ai-slop.py
git commit -m "feat(audit): upgrade anti-ai slop engine to v7.0 with tier 7 authority slop, local cadence monotony, and passive density analysis"
```

---

### Task 2: Hub & Directory Pages Ground-Truth & HLS 100 Calibration

**Files:**
- Modify: MariaDB Post 7 (`/destinations/`), Post 9 (`/compare/`), Post 6 (`/plan/`)
- Verify: `ops/anti_ai_slop_linter.py` crawl against live hub URLs

**Interfaces:**
- Consumes: Post IDs 7, 9, 6 on production database
- Produces: Evidence count $\ge 5$ and $HLS = 100$ on all 3 hub pages

- [ ] **Step 1: Write validation test for hub pages HLS and Evidence count**

Write test in scratch or `ops/tests/test-policy-cadence.py`:
Assert that `/destinations/`, `/compare/`, and `/plan/` achieve $HLS = 100$ and $EDI \ge 4.0$.

- [ ] **Step 2: Run test to verify current status**

Currently `/destinations/` ($HLS = 85$, $EDI = 0.0$), `/compare/` ($HLS = 85$, $EDI = 0.0$).

- [ ] **Step 3: Deploy content enrichment to Post 7, Post 9, and Post 6**

1. On `/destinations/` (Post 7): Embed regional transit corridors and pricing benchmark panel:
   - Hanoi to Ha Long Bay: `2.5 hrs` via Expressway CT04, limousine `250,000–300,000 VND`.
   - Hanoi to Ninh Binh: `1.5 hrs` via CT01, train SE3 soft seat `110,000 VND`.
   - Da Nang to Hoi An: `45 mins` coastal road, GrabCar `350,000–420,000 VND`.
   - Saigon to Mekong Delta (My Tho / Ben Tre): `2.0 hrs` via CT01 express van `150,000–180,000 VND`.
   - Lodging baselines: `350,000–650,000 VND` village homestays, `1,500,000–3,500,000 VND` heritage boutique hotels.
2. On `/compare/` (Post 9): Embed key comparative cost and logistics benchmarks:
   - Tam Coc vs Trang An: Boat tickets `250,000 VND` per boat / passenger.
   - Sapa vs Ha Giang: Daily budget `$35–$45 USD` vs `$45–$65 USD` with Easy Rider `1,000,000–1,200,000 VND/day`.
   - Da Nang vs Hoi An: Metered taxi fare `350,000 VND` (30 km).
   - Phu Quoc vs Nha Trang: Airport transfer `150,000 VND` (15 mins) vs `350,000 VND` (45 mins Cam Ranh).
3. Purge LiteSpeed cache:
   `rm -rf /usr/local/lsws/vietnamguide.net/luucache/* && /usr/local/lsws/bin/lswsctrl reload`

- [ ] **Step 4: Verify live endpoints return HLS = 100**

Run analysis on live HTML:
Assert `/destinations/` ($HLS = 100$), `/compare/` ($HLS = 100$), `/plan/` ($HLS = 100$).

- [ ] **Step 5: Commit documentation or test update**

```bash
git add ops/tests/test-policy-cadence.py
git commit -m "fix(hubs): enrich destinations and compare hub landing pages with transit and tariff baselines achieving 100 HLS"
```

---

### Task 3: Ground-Truth Evidence Saturation for 8 Lowest EDI Guides ($EDI \ge 6.0$)

**Files:**
- Create: `ops/remediate-ground-truth-v7.php`
- Modify: MariaDB posts:
  1. `hoi-an-ancient-town-guide` (ID 499)
  2. `phu-quoc-vs-nha-trang` (ID 241)
  3. `money-cash-cards-atms` (ID 158)
  4. `sim-esim-vietnam` (ID 15)
  5. `best-things-to-do-in-hanoi` (ID 173)
  6. `best-things-to-do-in-hue` (ID 184)
  7. `ha-long-bay-vs-lan-ha-bay` (ID 21)
  8. `bai-tu-long-bay-guide` (ID 201)

**Interfaces:**
- Consumes: Post IDs 499, 241, 158, 15, 173, 184, 21, 201
- Produces: $EDI \ge 6.0$ and $HLS = 100$ across all 8 articles

- [ ] **Step 1: Write `ops/remediate-ground-truth-v7.php` with concrete 2026 data tables**

1. **Hoi An Ancient Town Guide** (Post 499):
   - 5-monument Old Town entrance ticket (`120,000 VND` / `$5 USD`).
   - Hoai River evening lantern boat charter (`150,000–200,000 VND` for 20 mins, max 4 people).
   - Cam Kim wooden bridge bicycle rental (`40,000–50,000 VND/day`).
   - An Bang beach metered taxi (`80,000–100,000 VND`, 5 km).
2. **Phu Quoc vs Nha Trang** (Post 241):
   - Hon Thom 3-wire cable car ticket (`650,000–700,000 VND`).
   - Nha Trang VinWonders cable car / entry (`800,000 VND`).
   - Airport taxi transfer: Phu Quoc Airport to Duong Dong (`140,000–180,000 VND`, 15 mins) vs Cam Ranh Airport to Nha Trang city (`300,000–350,000 VND`, 45 mins).
   - Speedboat island hopping tour rates (`500,000–750,000 VND`).
3. **Money, Cash, Cards and ATMs in Vietnam** (Post 158):
   - Foreign card ATM withdrawal limits and fees by bank: VPBank (`5,000,000 VND` max, 0 VND local fee), Vietcombank (`3,000,000 VND` max, `50,000 VND` fee), BIDV (`3,000,000 VND` max, `40,000 VND` fee), TPBank (`5,000,000 VND` max).
   - Gold shop currency exchange centers: Ha Trung street (Hanoi Old Quarter), Ben Thanh / Dong Khoi jewelry shops (Saigon).
   - Credit card surcharge regulations: State Bank of Vietnam Circular 19/2016/TT-NHNN strictly forbidding merchant credit card surcharges.
4. **SIM and eSIM in Vietnam** (Post 15):
   - Official airport kiosk vs downtown boutique tariffs (`250,000–350,000 VND` vs `150,000–200,000 VND`).
   - Viettel tourist package (`200,000 VND`, 5GB/day for 30 days) and Vinaphone package (`BIG90`, `90,000 VND`, 1GB/day).
   - Mandatory passport registration law under Decree 49/2017/ND-CP.
5. **Best Things to Do in Hanoi** (Post 173):
   - Temple of Literature admission (`70,000 VND`, 08:00–17:00).
   - Hoa Lo Prison Relic admission (`50,000 VND`, audio guide `50,000 VND`).
   - Thang Long Water Puppet Theater tickets (`100,000 / 150,000 / 200,000 VND`).
   - Vietnam Military History Museum (`180,000 VND` international adult, ga Ga Hanoi to Nam Tu Liem bus 107 `9,000 VND`).
6. **Best Things to Do in Hue** (Post 184):
   - Imperial City admission (`200,000 VND`).
   - Khai Dinh Tomb admission (`150,000 VND`), Minh Mang Tomb (`150,000 VND`), Tu Duc Tomb (`150,000 VND`).
   - An Dinh Palace admission (`50,000 VND`).
   - Thien Mu Pagoda (free admission, 07:30–17:30).
7. **Ha Long Bay vs Lan Ha Bay** (Post 21):
   - Tuan Chau port terminal charge (`40,000 VND`) vs Got / Ben Beo ferry port charge (`30,000 VND`).
   - Overnight mooring and park fee: Ha Long Route 2 (`290,000 VND`) vs Lan Ha Bay overnight fee (`250,000 VND`).
   - Cat Ba National Park hiking entry (`80,000 VND`).
8. **Bai Tu Long Bay Guide** (Post 201):
   - Halong International Cruise Port departure fee (`40,000 VND`).
   - Route 4 Bai Tu Long Bay environmental fee (`250,000 VND`).
   - Thien Canh Son cave admission (`100,000 VND`).
   - Vung Vieng floating fishing village rowboat surcharge (`100,000 VND`).

- [ ] **Step 2: Deploy `ops/remediate-ground-truth-v7.php` to VPS and execute**

Upload via SFTP, run via SSH:
`php /tmp/remediate-ground-truth-v7.php && rm -f /tmp/remediate-ground-truth-v7.php && rm -rf /usr/local/lsws/vietnamguide.net/luucache/* && /usr/local/lsws/bin/lswsctrl reload`

- [ ] **Step 3: Verify all 8 articles achieve EDI >= 6.0 and HLS = 100**

Run analysis on live HTML:
Assert $EDI \ge 6.0$ and $HLS = 100$ for all 8 articles.

- [ ] **Step 4: Commit**

```bash
git add ops/remediate-ground-truth-v7.php
git commit -m "feat(content): saturate 8 core guides with concrete admissions, transit tariffs, and bank ATM limits achieving EDI >= 6.0"
```

---

### Task 4: Master CI/CD Regression, Sitemap v7.0 Audit & Release

**Files:**
- Modify: `ops/verification-log.md`, `walkthrough.md`
- Create: `ops/reports/anti-ai-slop-audit-v7-latest.json`

- [ ] **Step 1: Run Master CI/CD Gates Orchestrator**

Run: `powershell.exe -ExecutionPolicy Bypass -File ops/verify-all-gates.ps1`
Expected: 5/5 quality gates pass cleanly.

- [ ] **Step 2: Run Public Route Verification (87 HTTPS routes)**

Run: `powershell.exe -ExecutionPolicy Bypass -File ops/verify-guide-experience-public.ps1`
Expected: 87/87 public routes return HTTP 200 with full DOM integrity.

- [ ] **Step 3: Run Complete Production Sitemap Audit v7.0 (102 URLs)**

Run: `python ops/anti_ai_slop_linter.py --crawl-sitemap https://vietnamguide.net/sitemap_index.xml --out ops/reports/anti-ai-slop-audit-v7-latest.json`
Expected:
- 102/102 URLs passed.
- 0 Tier 1, 0 Tier 4, 0 Tier 5, 0 Tier 6, 0 Tier 7 clichés.
- 0 Adjective clusters, 0 Local cadence monotony violations.
- Average $HLS \ge 99.8$, Average $EDI \ge 8.8$.
- All 102 URLs at $HLS = 100$.

- [ ] **Step 4: Update Documentation and Commit**

Update `ops/verification-log.md` and `walkthrough.md`.
```bash
git add ops/verification-log.md walkthrough.md ops/reports/anti-ai-slop-audit-v7-latest.json
git commit -m "docs(audit): update verification log, sitemap audit report v7.0, and walkthrough for Stage 34"
```

- [ ] **Step 5: Push to Remote**

```bash
git push origin master
```

---

## Verification Plan

### Automated Tests
1. **Anti-AI Slop Unit Tests v7.0**: `python ops/tests/test-anti-ai-slop.py` (18/18 pass)
2. **Policy & Cadence Tests**: `python ops/tests/test-policy-cadence.py` (4/4 pass)
3. **Interactive Shortcodes & A11y**: `python ops/tests/test-interactive-shortcodes.py` (17/17 pass)
4. **CI/CD Quality Gate Orchestrator**: `powershell.exe -ExecutionPolicy Bypass -File ops/verify-all-gates.ps1` (5/5 pass)
5. **Public Route Verifier**: `powershell.exe -ExecutionPolicy Bypass -File ops/verify-guide-experience-public.ps1` (87/87 pass HTTP 200)
6. **Full Sitemap Audit v7.0**: `python ops/anti_ai_slop_linter.py --crawl-sitemap https://vietnamguide.net/sitemap_index.xml --out ops/reports/anti-ai-slop-audit-v7-latest.json` (102/102 pass, 0 violations, 102/102 at HLS = 100)

### Manual Verification
1. Inspect live decision tables on `/compare/phu-quoc-vs-nha-trang/` and `/destinations/hoi-an-ancient-town-guide/` for mobile display.
2. Confirm `/destinations/` and `/compare/` render transit and pricing summary panels with zero visual degradation.

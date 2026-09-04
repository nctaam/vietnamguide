# Ho Chi Minh City Travel Guide Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Publish an English-first Ho Chi Minh City Travel Guide that treats HCMC as a deliberate southern base, with premium district guidance, day-trip judgment, route-fit decisions, visible source trail, and EEAT verification.

**Architecture:** Build one destination page script that owns the HCMC guide content, metadata, and image credits. Then wire the page into the homepage, southern route spine, and shared EEAT verifier so the new guide is discoverable from planning pages before it goes live.

**Tech Stack:** WordPress, WP-CLI, PHP, Gutenberg block HTML, PowerShell static verifier, Rank Math metadata, Wikimedia Commons licensed images, official Vietnam tourism / HCMC government / airport / metro sources.

---

### Task 1: Write the failing HCMC static verifier

**Files:**
- Create: `ops/verify-ho-chi-minh-city-static.ps1`

- [ ] **Step 1: Write the failing test**

```powershell
$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$homepagePath = Join-Path $repoRoot 'ops/apply-homepage-premium.php'
$guidePath = Join-Path $repoRoot 'ops/apply-ho-chi-minh-city-travel-guide.php'
$verifierPath = Join-Path $repoRoot 'ops/verify-eeat-content.php'

$homepage = Get-Content -Raw -Path $homepagePath
$guide = if (Test-Path -LiteralPath $guidePath) { Get-Content -Raw -Path $guidePath } else { '' }
$verifier = Get-Content -Raw -Path $verifierPath

function Require-Contains([string] $Label, [string] $Haystack, [string] $Needle) {
    if (-not $Haystack.Contains($Needle)) {
        throw "$Label missing: $Needle"
    }
}

Require-Contains 'guide file exists' $guide 'ops/apply-ho-chi-minh-city-travel-guide.php'
Require-Contains 'guide title' $guide "'post_title'     => 'Ho Chi Minh City Travel Guide'"
Require-Contains 'hero marker' $guide 'vg-hcmc-hero:v1'
Require-Contains 'verdict marker' $guide 'vg-hcmc-concierge-verdict'
Require-Contains 'source diversity marker' $guide 'vg-hcmc-source-diversity:v1'
Require-Contains 'FAQ marker' $guide 'vg-hcmc-faq:v1'
Require-Contains 'homepage HCMC variable' $homepage '$ho_chi_minh_city_guide_href'
Require-Contains 'homepage HCMC fallback path' $homepage "vg_home_path('destinations/ho-chi-minh-city-travel-guide', 'destinations')"
Require-Contains 'homepage HCMC row' $homepage 'Ho Chi Minh City Travel Guide'
Require-Contains 'homepage HCMC verdict' $homepage 'Use Ho Chi Minh City when the south needs a real city chapter, not just an airport stamp.'
Require-Contains 'EEAT verifier HCMC label' $verifier 'Ho Chi Minh City Travel Guide'
Require-Contains 'EEAT verifier HCMC marker' $verifier 'vg-hcmc-hero:v1'

Write-Output 'Ho Chi Minh City static checks passed.'
```

- [ ] **Step 2: Run test to verify it fails**

Run: `powershell -ExecutionPolicy Bypass -File ops\verify-ho-chi-minh-city-static.ps1`
Expected: fail because the HCMC guide script and homepage wiring do not exist yet.

### Task 2: Implement the HCMC destination page

**Files:**
- Create: `ops/apply-ho-chi-minh-city-travel-guide.php`

- [ ] **Step 1: Build the guide script**

```php
<?php
// Publish Ho Chi Minh City as a southern-base decision guide with verdict,
// photo proof, source trail, district guidance, day-trip logic, route-fit tables,
// live checks, update log, and related routes.
```

- [ ] **Step 2: Run the HCMC static verifier until it turns green**

Run: `powershell -ExecutionPolicy Bypass -File ops\verify-ho-chi-minh-city-static.ps1`
Expected: pass after the guide script contains the required markers, title, source trail, and image-credit text.

### Task 3: Wire HCMC into homepage and southern spine

**Files:**
- Modify: `ops/apply-homepage-premium.php`
- Modify: `ops/apply-vietnam-travel-guide.php`
- Modify: `ops/apply-best-places-destination-guide.php`
- Modify: `ops/apply-north-central-south-comparison-guide.php`
- Modify: `ops/apply-best-time-guide.php`
- Modify: `ops/apply-transport-within-vietnam-guide.php`
- Modify: `ops/apply-vietnam-travel-cost-guide.php`
- Modify: `ops/apply-money-cash-cards-atms-guide.php`
- Modify: `ops/apply-sim-esim-vietnam-guide.php`
- Modify: `ops/apply-vietnam-evisa-guide.php`
- Modify: `ops/apply-7-days-itinerary-guide.php`
- Modify: `ops/apply-10-days-itinerary-guide.php`
- Modify: `ops/apply-14-days-itinerary-guide.php`
- Modify: `ops/apply-21-days-itinerary-guide.php`
- Modify: `ops/apply-health-travel-insurance-guide.php`
- Modify: `ops/apply-safety-scams-vietnam-guide.php`

- [ ] **Step 1: Add HCMC homepage routing with fallback-safe path logic**

```php
$ho_chi_minh_city_guide_href = vg_home_path('destinations/ho-chi-minh-city-travel-guide', 'destinations');
```

- [ ] **Step 2: Add HCMC to homepage planning rows, verdict rows, guide shelf, and update summary**

```php
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Southern city unclear</p><div><h3>I need to know whether Ho Chi Minh City deserves a real southern chapter.</h3><p>Use HCMC when the route needs city energy, museums, food, districts, airport access, and optional Mekong or Cu Chi logic instead of a token last night.</p><p class="vg-section-link"><a href="{$ho_chi_minh_city_guide_href}">Plan Ho Chi Minh City</a></p></div><p class="vg-home-plan-proof">Best when the south is part of the route, not just the exit airport.</p></div>
```

- [ ] **Step 3: Add HCMC structured related-route lines to the main planning spine**

```php
Ho Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Decide whether the city should anchor the south as a real chapter with district choice, airport access, food, museums, and optional Mekong or Cu Chi side trips.
```

- [ ] **Step 4: Re-run the HCMC verifier**

Run: `powershell -ExecutionPolicy Bypass -File ops\verify-ho-chi-minh-city-static.ps1`
Expected: pass once homepage and spine wiring are in place.

### Task 4: Extend EEAT verification and publish live

**Files:**
- Modify: `ops/verify-eeat-content.php`
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Add HCMC page coverage to EEAT verification**

```php
$required_hcmc_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'HCMC hero marker' => ['vg-hcmc-hero:v1'],
        'HCMC concierge verdict' => ['vg-hcmc-concierge-verdict'],
        'HCMC at a glance' => ['vg-hcmc-at-a-glance:v1'],
        'HCMC photo grid' => ['vg-hcmc-photo-grid:v1'],
        'HCMC source diversity' => ['vg-hcmc-source-diversity:v1'],
        'HCMC district guide' => ['vg-hcmc-districts:v1'],
        'HCMC route fit' => ['vg-hcmc-route-fit:v1'],
        'HCMC day trips' => ['vg-hcmc-day-trips:v1'],
        'HCMC season weather' => ['vg-hcmc-season-weather:v1'],
        'HCMC transport logistics' => ['vg-hcmc-transport-logistics:v1'],
        'HCMC cost booking' => ['vg-hcmc-cost-booking:v1'],
        'HCMC skip logic' => ['vg-hcmc-skip-logic:v1'],
        'HCMC live checks' => ['vg-hcmc-live-checks:v1'],
        'HCMC FAQ' => ['vg-hcmc-faq:v1'],
    ]
);
```

- [ ] **Step 2: Add the HCMC page to the relevant published-page verification and inbound related-route checks**

```php
$hcmc_guide_page = vg_verify_eeat_require_published_path(
    'destinations/ho-chi-minh-city-travel-guide',
    'Ho Chi Minh City Travel Guide',
    $failures
);
```

- [ ] **Step 3: Add a publication and QA entry after the page is live**

```markdown
### 2026-07-22 - Ho Chi Minh City Travel Guide Publication and QA
```

- [ ] **Step 4: Publish remotely, refresh homepage, purge cache, and run verification**

Run:
```bash
php -l /tmp/vg-hcmc/ops/apply-ho-chi-minh-city-travel-guide.php
wp eval-file /tmp/vg-hcmc/ops/apply-ho-chi-minh-city-travel-guide.php --allow-root
wp eval-file /tmp/vg-hcmc/ops/apply-homepage-premium.php --allow-root
wp eval-file /tmp/vg-hcmc/ops/verify-eeat-content.php --allow-root
wp cache flush
wp rewrite flush --hard
wp eval-file ops/purge-litespeed-cache.php --allow-root
```
Expected: HCMC page returns 200, homepage shows the HCMC route, EEAT verifier passes, and sitemap/public QA confirms the page is indexed-ready.

- [ ] **Step 5: Commit the verification and log update**

```bash
git add ops/verify-eeat-content.php ops/verification-log.md
git commit -m "feat: add Ho Chi Minh City EEAT coverage and publish log"
```

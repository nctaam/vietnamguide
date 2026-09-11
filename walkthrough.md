# Stage 20 Walkthrough: Rich Travel Structured Data & Contextual Route Journey Architecture

## Overview

Stage 20 completes the strategic Rich Travel Structured Data Expansion and Contextual Destination Hub Linking milestone for **VietnamGuide.net**, elevating user dwell time, organic travel circuit discovery, and semantic search authority across all 88 published routes.

---

## 1. Architecture & Capabilities Implemented

### A. Rich Travel Schema Structured Data (`wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php`)
Integrated directly into Rank Math SEO's JSON-LD graph filter (`rank_math/json_ld`, priority 100):
- **`TouristDestination`**: Canonical entity node defining regional destination hubs with Wikidata `sameAs` entity alignment, `touristType` taxonomies, `includesAttraction` clusters, and bi-directional `subjectOf` reference to the enclosing `WebPage`.
- **`TouristTrip`**: Multi-day route itinerary entities structuring regional travel circuits as ordered `ItemList` sequences of `ListItem` destination stops.
- **`TravelAction`**: Action node specifying transit operations, origin (`fromLocation`), destinations (`toLocation`), transport method (`instrument`), and managing entity (`agent`).
- **Graph Invariants**:
  - `WebPage` and `Article` nodes connect to the `TouristDestination` node via the standard Schema.org `about` property.
  - `TouristDestination` connects back to the `WebPage` node via `subjectOf`.
  - Action instruments strictly adopt Schema.org `Thing` types, avoiding invalid ontological entities.
  - Filter is strictly idempotent per graph array, preventing node duplication.

### B. Contextual Next-Step Journey Links
- **Regional Cluster Registry (`vg_get_travel_clusters_registry`)**: 8 authoritative regional clusters covering all Vietnam circuits:
  1. *Northern Golden Triangle*: Hanoi hub connecting to Ha Long Bay, Ninh Binh, Sa Pa.
  2. *Karst Archipelago*: Ha Long Bay & Lan Ha Bay connecting to Ninh Binh, Hanoi, Cat Ba Island.
  3. *Karst Countryside*: Ninh Binh connecting to Ha Long Bay, Phong Nha caves, Hanoi.
  4. *Central Heritage Corridor*: Da Nang, Hoi An & Hue connecting along the Hai Van Pass and Cham Islands.
  5. *Southern Riverine & Metropolis*: Ho Chi Minh City connecting to Mekong Delta, Cu Chi tunnels, Phu Quoc.
  6. *Northern Highlands Frontier*: Sa Pa, Ha Giang extreme loop, Mu Cang Chai terraces.
  7. *Coastal & Island Sanctuaries*: Phu Quoc, Con Dao marine park, Quy Nhon, Ly Son Island.
  8. *Vietnam Grand Overland Circuit*: National gateway linking northern, central, and southern circuits.
- **Auto-Suppression of Self-Links**: Destination guides dynamically suppress links to themselves across both relative paths and absolute canonical URLs.
- **Shortcodes**: Registered `[vg_journey_links]` and `[vg_contextual_journey]`, accepting optional `cluster` override attributes.
- **Automated Content Filter**: Injects contextual cards into singular guides via `the_content` (priority 30), guarded against front page, feeds, admin contexts, and hero blocks.

### C. Tactile UI & Accessibility (`assets/css/guide-patterns.css`)
- **Emil Kowalski Micro-Interactions**: Spring easing curve `cubic-bezier(0.34, 1.56, 0.64, 1)`, tactile card lift (`translateY(-3px)`), active press compression (`scale(0.985)`), and smooth indicator translation.
- **Design Tokens**: 100% semantic CSS variables (`var(--vg-*)`) with zero hardcoded hex codes.
- **WCAG 2.2 AA Contrast**: Verified `--vg-gold-text` (#7e5802) on white/limestone backgrounds meeting 4.5:1 minimum ratio.
- **Reduced Motion**: Complete `@media (prefers-reduced-motion: reduce)` block disabling all transitions and transforms.

---

## 2. Issues Discovered and Resolved During Review

| Area | Defect in Prior Attempt | Resolution Applied |
| :--- | :--- | :--- |
| **Schema.org Vocabulary** | `WebPage` & `Article` used `subjectOf: #tourist-destination` (Schema range error: `subjectOf` expects `CreativeWork`/`Event`, not `Place`). | `WebPage` & `Article` now reference destination via `about`. `TouristDestination` references `WebPage` via `subjectOf`. |
| **Schema Type** | Used non-existent `@type: TravelMethod` (HTTP 404 in Schema.org). | Standardized to `@type: Thing`. |
| **Shortcode Signature** | `vg_contextual_journey_shortcode(array $atts = [])` caused fatal `TypeError` when WordPress passed empty string `""`. | Changed to `$atts = []` and added cluster attribute parsing support. |
| **Content Filtering** | Checked `str_contains($content, 'vg-hero')`, which accidentally suppressed journey links on guides using `vg-hero-copy` or `vg-hero-proof-list`. | Replaced with explicit `is_front_page() \|\| is_home()` checks and targeted `vg-guide-hero` / `vg-contextual-journey` guards. |
| **Idempotence** | Static boolean flag `static $alreadyProcessed` permanently broke schema evaluation on subsequent calls in the same process. | Replaced with graph `@id` membership check. |
| **Self-Link Detection** | Only stripped paths if input was relative; failed if full canonical URL was passed. | Normalized via `parse_url($path, PHP_URL_PATH)` for both relative and absolute inputs. |
| **Ly Son Island** | Island route fell back to national circuit rather than coastal/island cluster. | Added `ly-son` matching to `coastal_islands` cluster. |
| **Documentation** | Missing `walkthrough.md` required by milestone specification. | Authored comprehensive `walkthrough.md` and committed to master. |

---

## 3. Verification & Acceptance Testing

- `ops/verify-homepage-theme.ps1`: **PASSED** (0 failures).
- `ops/verify-core-mu-plugin.ps1`: **PASSED** (Fingerprint `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` preserved; all 16 mutation tests rejected).
- `ops/verify-core-block-patterns.ps1`: **PASSED** (0 hardcoded hex colors).
- `ops/verify-guide-experience.ps1`: **PASSED**.
- `ops/verify-guide-experience-public.ps1` (Windows PowerShell 5.1): **PASSED** across all 87 public routes with HTTP 200.
- Unit Test Suite (`ops/test_stage20_fixes.php`): **5/5 PASSED**.
- Remote SHA-256 Parity: **100% matched** on production VPS.
- Live Endpoint Validation: Inspected live JSON-LD graph on `https://vietnamguide.net/destinations/hanoi-travel-guide/` verifying valid `TouristDestination`, `TouristTrip`, `TravelAction`, and `.vg-contextual-journey` components.

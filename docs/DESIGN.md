# VietnamGuide Design System — Editorial Field Intelligence

## 1. Visual Theme & Atmosphere
VietnamGuide is a premier travel intelligence platform delivering high-fidelity field guides, canonical route registries, and real-time interactive travel toolkits for explorers across Vietnam. 

The aesthetic philosophy is **"Editorial Field Intelligence"** — a fusion of high-end architectural monograph layout, National Geographic investigative field notes, and tactile Indochine warmth. The visual atmosphere is calm, authoritative, deeply legible, and free of generic AI-slop tropes.

- **Density:** Balanced to Focused Editorial (6/10) — generous breathing room for long-form narrative interspersed with high-density data matrices (cost estimates, transport timetables, visa requirements).
- **Variance:** Asymmetric Editorial Rhythm (7/10) — offset columns, structured sticky reading spines, callout sidebars, and dual-axis comparison tables.
- **Motion:** Weighted Spring Physics (5/10) — subtle, tactile micro-transitions (`cubic-bezier(0.16, 1, 0.3, 1)`) with zero layout thrashing and full respect for `prefers-reduced-motion`.

---

## 2. Color Calibration & Palette Roles

The color system draws from Vietnam's natural and architectural heritage: the deep navy of Ha Long Bay waters, the warm golden glow of Hoi An silk lanterns, the organic tone of handmade dó / rice paper, and the deep charcoal ink of classical woodblock prints.

| Color Name | Hex Code | Role & Functional Usage |
| :--- | :--- | :--- |
| **Indochine Navy (Primary)** | `#0F2537` | Primary brand ink, display headlines, dark badge backgrounds, high-contrast borders |
| **Saffron Gold (Accent)** | `#C99446` | Key action CTAs, focus indicators, highlight borders, active navigation pills |
| **Rice Paper Canvas (Base)** | `#FAF8F5` | Primary background canvas, evoking organic handmade paper texture |
| **Pure Alabaster (Surface)** | `#FFFFFF` | Card fills, elevated interactive toolkit surfaces, popover containers |
| **Charcoal Ink (Text)** | `#1A202C` | Primary editorial body text, offering high contrast without pure black harshness |
| **Muted Slate (Secondary)** | `#64748B` | Secondary descriptions, timestamps, author credits, and metadata tags |
| **Whisper Border** | `rgba(15, 37, 55, 0.08)` | 1px hairline card borders, table dividers, and structural spines |
| **Verified Emerald** | `#1E6B52` | Trust badges, visa-exempt indicators, live status indicators |
| **Alert Amber** | `#B45309` | Caution callouts, monsoon season notices, advisory notes |

### Banned Color Patterns
- **No AI Purple / Neon Blue glows** — strictly no box-shadow glows or holographic gradients.
- **No pure pitch black (`#000000`)** — always use Charcoal Ink (`#1A202C`).
- **No oversaturated buttons** — gold saturation capped at 75%.
- **Single accent discipline** — Saffron Gold (`#C99446`) is the only vibrant accent.

---

## 3. Typographic Architecture

A calibrated tri-stack typography system designed specifically for bilingual and Vietnamese diacritic excellence:

1. **Display & Editorial Headlines:**
   - **Font:** `Playfair Display`, serif with fallback to `Lora`, `Georgia`.
   - **Characteristics:** Track-tight (`-0.02em` to `-0.01em`), optical sizing enabled, high stroke contrast.
   - **Scale:** Fluid `clamp(1.75rem, 4vw, 2.75rem)` for H1 Hero; `clamp(1.35rem, 2.5vw, 1.85rem)` for H2 Sections.

2. **Body & Reading Narrative:**
   - **Font:** `Be Vietnam Pro`, sans-serif (system fallbacks: system-ui, -apple-system, sans-serif).
   - **Characteristics:** Designed expressly for Vietnamese tone marks and Latin readability. Relaxed line-height (`1.65` to `1.75`), max line length 68 characters (`68ch`).
   - **Size:** Base `1.0625rem` (17px) for desktop long-form reading, `1rem` (16px) for mobile.

3. **Data, Costs & Metadata (Mono):**
   - **Font:** `JetBrains Mono`, monospace (fallbacks: `SFMono-Regular`, `Consolas`, monospace).
   - **Characteristics:** Tabular figures (`font-variant-numeric: tabular-nums`), slightly reduced size (`0.875rem` / 14px), uppercase tracking (`+0.05em`).
   - **Usage:** VND/USD prices, route duration badges, weather temperature metrics, GPS coordinates, timestamps.

---

## 4. Component Stylings & Behaviors

### A. Canonical Guide Shell Hero
- **Structure:** Clean asymmetric layout. Breadcrumb navigation -> Editorial metadata pill row (Author, Verified Date, Reading Time, Region Badge) -> Large serif title -> Executive summary lead paragraph.
- **Micro-detail:** Subtle gold hairline accent on the left or top border of the lead callout box.

### B. Sticky Reading Spine (TOC)
- **Structure:** Left sidebar on desktop (min-width: 240px). Vertical subtle hairline (`rgba(15, 37, 55, 0.1)`).
- **Active State:** Indochine Navy text with a solid 2px Saffron Gold left indicator, font weight 600.
- **Reading Progress:** Smooth vertical track indicating scroll percentage through the article.

### C. Interactive Toolkits (Shortcodes)
- **Cost Calculator:** Segmented duration pills (3 days / 7 days / 14 days), travel style slider (Backpacker / Flashpacker / Luxury), instant breakdown cards with tabular currency figures.
- **Visa Checker:** Passport nationality searchable dropdown, instantaneous exemption verdict badge (Emerald green "Visa-Free 45 Days" or Amber "E-Visa Required 90 Days"), official portal links.
- **Season & Weather Matrix:** Month-by-month grid with dry/wet/shoulder season tonal tags, temperature spans, and storm warnings.
- **Packing Checklist:** Interactive checkboxes with persistent local storage, categorized into Essentials, Electronics, Tropical Health, and Cultural Attire.

### D. Tactile Interaction & Buttons
- **Primary Button:** Indochine Navy background (`#0F2537`), white text, rounded corners (`8px`), 1px subtle gold outline on hover, micro-translation `translateY(-1px)`.
- **Secondary / Ghost Button:** Transparent background, 1px whisper border, Charcoal Ink text, subtle slate background tint on hover.
- **Minimum Tap Target:** `44px x 44px` on all mobile interactive elements.

---

## 5. Mobile Field Experience (< 768px)
- **Sticky Quick-Action Bottom Bar:** Floating dock with 4 primary touch points:
  1. Jump to TOC / Quick Navigation.
  2. Currency / Cost toggle.
  3. Offline Save / Reading state.
  4. Quick Share / Bookmark.
- **Single-Column Collapse:** Sidebar TOC folds into a collapsible sheet / bottom drawer with high-contrast close button.
- **Zero Horizontal Scroll:** All tables and matrices wrapped in responsive swipe containers with subtle fade-edge indicators.

---

## 6. Accessibility & Performance Guardrails (WCAG AAA)
- **Contrast Ratios:** Charcoal `#1A202C` on Rice Paper `#FAF8F5` achieves **13.8:1** (exceeds WCAG AAA 7:1).
- **Focus Rings:** High-visibility outline `2px solid var(--vg-gold)` with `2px` offset on all keyboard focus states (`:focus-visible`).
- **Motion Restraint:** Full `@media (prefers-reduced-motion: reduce)` resets removing all transforms and transitions.
- **Clean Semantic Markup:** Native `<article>`, `<aside>`, `<nav>`, `<section>`, `<header>`, `<table>` elements with descriptive ARIA attributes.

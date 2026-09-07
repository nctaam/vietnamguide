---
name: VietnamGuide Editorial Luxury
colors:
  surface: '#fbf7ef'
  surface-dim: '#f3ece0'
  surface-bright: '#ffffff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f8f3e9'
  surface-container: '#f4eee1'
  surface-container-high: '#ede5d5'
  surface-container-highest: '#e6dac8'
  on-surface: '#111e1b'
  on-surface-variant: '#5b6b64'
  inverse-surface: '#012d1d'
  inverse-on-surface: '#fbf7ef'
  outline: '#dcd5c7'
  outline-variant: '#e8e2d5'
  surface-tint: '#012d1d'
  primary: '#012d1d'
  on-primary: '#ffffff'
  primary-container: '#08432d'
  on-primary-container: '#d8ece5'
  inverse-primary: '#85d4be'
  secondary: '#00875a'
  on-secondary: '#ffffff'
  secondary-container: '#d2f3e7'
  on-secondary-container: '#004d33'
  tertiary: '#c5a059'
  on-tertiary: '#241a04'
  tertiary-container: '#faeccf'
  on-tertiary-container: '#543f0e'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  background: '#fbf7ef'
  on-background: '#111e1b'
  surface-variant: '#f4eee1'
  surface-paper: '#fbf7ef'
  deep-ink: '#111e1b'
  gold-accent: '#c5a059'
  jade-accent: '#00875a'
  border-subtle: 'rgba(1, 45, 29, 0.12)'
typography:
  headline-xl:
    fontFamily: Lora
    fontSize: 44px
    fontWeight: '700'
    lineHeight: 52px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Lora
    fontSize: 34px
    fontWeight: '700'
    lineHeight: 42px
    letterSpacing: -0.015em
  headline-lg-mobile:
    fontFamily: Lora
    fontSize: 26px
    fontWeight: '700'
    lineHeight: 32px
  headline-md:
    fontFamily: Lora
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  headline-sm:
    fontFamily: Lora
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 32px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 28px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 22px
  label-md:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.06em
  label-sm:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '700'
    lineHeight: 14px
    letterSpacing: 0.08em
  stat-mono:
    fontFamily: JetBrains Mono
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
rounded:
  sm: 0.25rem
  DEFAULT: 0.375rem
  md: 0.5rem
  lg: 0.75rem
  xl: 1rem
  full: 9999px
spacing:
  base: 4px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 48px
  max-width: 1280px
  spine-max: 1186px
  content-width: 690px
---

# VietnamGuide - Editorial Luxury & Travel Intelligence Design System
**Project ID:** 12083447832634731669

## Brand Atmosphere & Philosophy
VietnamGuide is a high-end independent digital travel publication and logistics authority for international travelers visiting Vietnam. The visual language evokes the tactility, prestige, and immersive calm of heritage editorial travel journals (*Afar*, *Monocle*, *National Geographic Traveler*), combined with clinical engineering precision for transit routes and cost models.

- **Atmosphere:** Deep Pine Forest (`#012d1d`), Warm Silk Paper (`#fbf7ef`), Heritage Gold (`#c5a059`), and Imperial Jade (`#00875a`).
- **Reading Measure:** 690px central column yielding 68–74 characters per line (Bringhurst & WCAG 1.4.12 optimal ergonomics).
- **Spine Grid:** 3-column asymmetric layout (176px Table of Contents + 690px Central Article + 224px Trust Rail = 1186px max-width).
- **Anti-Slop Directives:** Strict ban on AI neon glows, cyan/purple futuristic gradient cards, centered floating cards, and pure black `#000000`.

## Colors & Semantic Roles
- **Primary (Deep Pine - `#012d1d`):** Primary brand authority, mastheads, major section titles, and primary interactive elements.
- **Secondary (Imperial Jade - `#00875a`):** Authentic field notes, verification badges, and positive travel advisories.
- **Tertiary (Heritage Gold - `#c5a059`):** Concierge Verdict left accent bar, reading progress indicator glow, and premium editorial highlights.
- **Surface Paper (`#fbf7ef`):** Warm, unbleached silk paper background eliminating eye fatigue during 3,000–5,000 word reading sessions.
- **Surface Cream (`#f4eee1`):** Tonal card containers, subtle sidebars, and callout panels.
- **Deep Ink (`#111e1b`):** High-contrast editorial typography exceeding WCAG 2.2 AAA contrast ratios.
- **Muted Text (`#5b6b64`):** Field notes, timestamps, reading durations, and secondary logistics data.

## Typography Architecture
- **Display & Section Headers:** `Lora` serif conveys literary gravitas, journey wisdom, and timeless exploration.
- **Body & Content Copy:** `Inter` or `Be Vietnam Pro` provides clinical legibility for Vietnamese diacritics and transit details with generous 1.7x line-height.
- **Technical & Timetable Mono:** `JetBrains Mono` for departure times, ATM withdrawal limits, and currency calculations.
- **Labels & Metas:** Uppercase, tracked-out (+0.06em) for Trust Chips, author tags, and geographical categories.

## Core Component Patterns
- **Tactile Trust Chips (`.vg-guide-meta span`):** Frosted glass chips with subtle borders (`1px solid color-mix(in srgb, var(--vg-ink) 12%, transparent)`), `backdrop-filter: blur(4px)`, and micro-elevation for E-E-A-T metadata.
- **Concierge Verdict Card (`.vg-concierge-verdict`):** Silk gradient background, 4px solid Heritage Gold left border, and `.vg-verdict-lede` summary typography optimized for 45-word AEO instant answers.
- **Field Note Box (`.vg-field-note`):** Authentic expedition journal box with 4px solid Imperial Jade left border and limestone background tint.
- **Decision & Logistics Table (`.vg-decision-table`):** Zebra-striped alternating rows (`tbody tr:nth-child(even)`), bold first column for key metrics, and horizontal scroll affordance with CSS scroll shadows.
- **Mobile Sticky Jump Navigation (`.vg-guide-jump`):** Fixed to top below header with 16px blur, 44px touch targets, and auto-scroll centering.

## Micro-Physics & Interaction
- **Physics Curve:** Emil Kowalski ease-out `cubic-bezier(0.16, 1, 0.3, 1)`.
- **Tactile Press:** `:active { transform: scale(0.97); }` with 160ms transition.
- **Reading Progress Bar:** Hardware-accelerated GPU `transform: scaleX(...)` with `transform-origin: 0 50%` and gold glow.

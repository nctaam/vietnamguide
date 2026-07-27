# VietnamGuide.net Homepage Redesign Design

Date: 2026-07-27
Project: vietnamguide.net
Scope: WordPress homepage redesign, local theme implementation
Status: Proposed for implementation after deep optimization review

## Goal

Turn the WordPress homepage into a premium travel-planning orientation surface for English-speaking visitors. The homepage must help a visitor choose a planning path within seconds while preserving the editorial credibility and practical voice defined in the existing VietnamGuide.net product spec.

The Stitch project `Vietnam Travel Experience Portal` is the visual source of truth. The primary references are:

- Desktop: `projects/10120543989568032144/screens/85c390be32164a6e809f8affd0d442f7` (Home, Cinematic Perfection)
- Mobile: `projects/10120543989568032144/screens/ebfbffa9ff2f43b38a13f67d2bfeb7ac` (Home, Final Editorial)

The other Stitch screens remain reference material for future destination, itinerary, comparison, cost, and contact templates. They are not separate homepage variants to implement independently.

## Visual Direction

### Visual thesis

Cinematic Vietnam photography, dark ink typography, river-jade actions, quiet gold details, and generous editorial spacing make practical trip planning feel curated and trustworthy.

### Content plan

1. Hero: brand, promise, two clear planning actions, and one dominant Vietnam image.
2. Planning paths: trip length and travel style choices that route users to existing guide clusters.
3. Editorial guidance: a small set of signature itineraries and destination decisions, presented as rows and image bands rather than a generic card grid.
4. Practical essentials: visa, timing, costs, transport, and connectivity links.
5. Final conversion: a low-friction first-trip checklist/newsletter invitation followed by a trust-focused footer.

### Interaction thesis

- On load, the wordmark, headline, supporting copy, and actions enter in a short upward fade sequence.
- As the user scrolls, image bands and editorial rows reveal with restrained opacity/translate transitions.
- The header changes from transparent-over-hero to a solid paper surface after the hero, while focus and hover states use the jade/gold system.

Motion must respect `prefers-reduced-motion` and must never delay readable content.

## Information Architecture

The homepage uses the existing navigation model:

- Plan
- Destinations
- Itineraries
- Compare
- Costs

Primary homepage actions:

- `Start planning` -> `/plan/`
- `See itineraries` -> `/itineraries/`

Planning selectors link to real WordPress URLs rather than client-side filters. Suggested options are 7, 10, 14, and 21 days, plus First trip, Food, Beach, Family, and Premium.

## Page Structure

### 1. Header and hero

- Full-bleed hero image with no shared page gutter.
- Transparent desktop header over the hero; mobile header remains compact and accessible.
- Wordmark `VietnamGuide.net` is the strongest brand element in the first viewport.
- Headline: `Vietnam for travelers who choose well.`
- Supporting copy is one short sentence.
- Primary and secondary actions are visible without scrolling.
- A subtle continuation cue points toward planning paths below.

### 2. Planning paths

Use a calm editorial layout with two groups: trip length and travel style. Each option is a text-first link with a clear hover/focus state; avoid pill-soup styling and decorative controls.

### 3. Signature itineraries

Show four routes as editorial rows with route title, duration, short fit statement, and destination imagery. The rows should support future WordPress query data but ship with curated links to the existing itinerary URLs.

### 4. Destination edit

Present a curated set of Hanoi, Hoi An, Ninh Binh, Lan Ha Bay, Ha Giang, and Phu Quoc. Each item has a short `best for` and `skip if` cue. Use image-led rows or alternating media/text bands, not a dense card mosaic.

### 5. Decision guides

Feature three practical comparisons: Ha Long Bay vs Lan Ha Bay, Sapa vs Ha Giang, and Hanoi vs Ho Chi Minh City. The visual treatment should communicate a decision, not just advertise articles.

### 6. Practical essentials

Use a restrained list/grid for Vietnam e-visa, best time to visit, travel cost, SIM/eSIM, getting around, and safety. Every item links to a maintained guide page and can display a review/update date when available.

### 7. Checklist capture and footer

Invite the user to get a `Vietnam First Trip Checklist`. Keep the form short, state the value plainly, and defer aggressive popups. The footer carries plan links, trust links, legal/affiliate disclosure, and the source/update policy.

## WordPress Architecture

Implement the first pass as a lightweight theme layer inside `wordpress/wp-content/themes/vietnamguide-premium`.

- `front-page.php` owns homepage composition and semantic section order.
- `functions.php` registers styles/scripts and theme support.
- `style.css` contains the theme header and design tokens.
- `assets/css/` contains focused component styles only if the theme needs separation.
- `assets/js/` contains progressive enhancement for header state and reveal motion; the page remains usable without JavaScript.
- `patterns/` contains reusable block patterns for planning paths, editorial rows, decision guides, and essentials.

The initial implementation may use curated arrays in the template for stable launch content. Do not introduce a custom post type or a page builder for this homepage pass. Keep link targets and content definitions isolated so they can later be replaced by WordPress queries or custom fields without changing markup.

## Design Tokens

- Ink: `#101417`
- Soft paper: `#F7F4ED`
- Limestone: `#E5DED1`
- River jade: `#0E6F5C`
- Deep river blue: `#173B56`
- Quiet gold: `#B98739`
- Clay coral: `#C95F4A`
- Display type: Source Serif 4 (or the closest locally available serif fallback)
- Body/UI type: Hanken Grotesk (or the closest locally available sans fallback)
- Content measure: 65-78 characters
- Major section gap: 72-112px desktop, reduced fluidly on mobile

Do not add a second accent system or default to purple gradients. Gold remains a small detail color, not a large surface.

## Responsive and Accessibility Requirements

- Desktop and mobile compositions must follow the two Stitch references, not just shrink the desktop layout.
- The first viewport must fit header plus hero content at common desktop and mobile sizes.
- All navigation, buttons, and planning links must be keyboard reachable with visible focus states.
- Hero text must retain strong contrast over the image; provide a tonal overlay rather than sacrificing readability.
- Images require explicit dimensions, meaningful alt text, and lazy loading below the fold.
- Reduced-motion users receive an immediate, static presentation.
- Editorial rows and any comparison-like content must remain readable at narrow widths without horizontal scrolling.

## Performance and Verification

- Use responsive WebP/AVIF image sources where available and avoid loading below-fold hero-sized images eagerly.
- Keep JavaScript limited to header state, reveal motion, and mobile menu behavior.
- Verify PHP syntax, theme file presence, responsive layout at desktop/mobile widths, keyboard navigation, and reduced-motion behavior.
- Confirm all homepage links resolve to the intended WordPress paths and no placeholder copy remains.

## Acceptance Criteria

1. The homepage is unmistakably VietnamGuide.net within the first viewport.
2. A user can choose a planning path in one click from the hero or planning section.
3. The visual hierarchy matches the selected Stitch desktop/mobile references without reproducing every exploratory screen as a separate template.
4. The page works as semantic HTML with JavaScript disabled.
5. The theme remains lightweight, responsive, accessible, and ready for later destination/itinerary templates.

## Deep Optimization Pass

This pass upgrades the homepage from a visual implementation into a durable WordPress system. It is still limited to the homepage foundation; it does not add a booking engine, user accounts, or a heavy page builder.

### Design-system governance

- Define all colors, type sizes, spacing, radii, shadows, breakpoints, and motion durations as named tokens in `theme.json` and CSS custom properties.
- Keep Stitch as the visual reference, but select one canonical desktop/mobile variant per content family. Exploratory and duplicate screens remain reference material only.
- Establish contracts for shared patterns: required fields, optional fields, link behavior, image aspect ratio, and fallback content.
- Support long titles, missing images, empty data, keyboard focus, reduced motion, and high zoom without breaking composition.
- Use visual regression screenshots at the selected desktop and mobile reference widths before accepting future changes.

### Content and data model

Keep content separate from presentation so editors can update links and copy without editing template markup.

- Store homepage collections as structured data in one isolated PHP data provider first, with a clear migration path to WordPress queries or custom fields.
- Give every editorial item a stable URL, short description, `best for` cue, optional `skip if` cue, image, alt text, and review date.
- Use the same field vocabulary that future Destination, Itinerary, Comparison, and Practical Guide templates will consume.
- Do not create a custom post type solely for the homepage pass; add one only when the editorial workflow demonstrates the need.

### Performance budget

- Target LCP <= 2.5 seconds, CLS <= 0.1, and INP <= 200 milliseconds on a representative mobile connection.
- Preload only the selected hero image and the minimum critical font resources; never preload every editorial image.
- Serve hero and editorial imagery through responsive `srcset`/`sizes` with explicit dimensions and WebP/AVIF sources where available.
- Keep homepage JavaScript below 25 KB compressed before third-party scripts. JavaScript must be progressive enhancement, not a rendering dependency.
- Keep the first viewport free of newsletter popups, autoplay video, heavy maps, and layout-shifting embeds.
- Capture a baseline with Lighthouse/PageSpeed before implementation and repeat it after the homepage is rendered.

### SEO and conversion instrumentation

- Map each section to a search or planning intent: route length, travel style, destination choice, practical requirement, or next action.
- Add semantic landmarks, one clear H1, descriptive link text, canonical metadata, Open Graph fields, and context-appropriate `ItemList`/`FAQ`/`Article` schema.
- Keep volatile facts behind maintained guide URLs and expose `last reviewed` metadata when available.
- Track clicks for hero CTAs, route-selector choices, itinerary rows, decision-guide rows, newsletter submission, and outbound affiliate links.
- Add event names and data attributes without making analytics scripts required for page usability.

### Interaction and resilience

- Use three motion primitives only: hero entrance, section reveal, and header surface transition.
- Respect `prefers-reduced-motion` by disabling transforms and reducing transitions to immediate state changes.
- Use an accessible mobile menu with focus management, Escape-to-close, and a non-JavaScript fallback navigation.
- Ensure planning links remain ordinary URLs so they work with prefetching, SEO crawlers, keyboard navigation, and disabled JavaScript.

### Verification matrix

Before calling the homepage complete, verify:

1. PHP syntax and WordPress theme discovery.
2. Desktop composition against the Stitch Cinematic Perfection reference.
3. Mobile composition against the Stitch Final Editorial reference.
4. Keyboard navigation, visible focus, screen-reader landmarks, and reduced-motion behavior.
5. JavaScript-disabled rendering and link functionality.
6. Image dimensions, alt text, lazy-loading behavior, and no visible layout shifts.
7. Lighthouse/PageSpeed performance budget and Web Vitals-related warnings.
8. Analytics events in a debug environment without leaking private data.

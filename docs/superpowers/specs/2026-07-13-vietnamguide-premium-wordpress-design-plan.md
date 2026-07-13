# VietnamGuide.net Premium WordPress Design and Development Plan

Date: 2026-07-13
Build direction: Quiet Luxury Concierge
Parent spec: `2026-07-13-vietnamguide-wordpress-development-design.md`
Status: Premium design and execution spec for review

## 1. Decision

The selected premium direction is **Quiet Luxury Concierge**.

VietnamGuide.net should feel like a refined travel advisor for Vietnam: cinematic, calm, selective, and practical. The design must signal quality and trust without becoming a generic luxury brochure. SEO remains the growth engine; the premium layer improves credibility, dwell time, affiliate conversion, and perceived editorial authority.

The design should not look like:

- A budget backpacker blog.
- A tour company landing page.
- A hotel booking clone.
- A generic card-heavy WordPress magazine.
- A decorative travel site with weak information architecture.

It should look like:

- A premium travel planning publication.
- A curated guide with clear choices.
- A calm concierge interface that helps travelers choose well.
- A content system built for 150+ evergreen guides.

## 2. Product Thesis

### Visual Thesis

Cinematic Vietnam photography, dark ink typography, river-jade actions, restrained gold details, and spacious editorial layouts that make practical travel planning feel curated and premium.

### Content Thesis

Every page should answer one travel decision with high confidence. Premium does not mean vague inspiration; premium means faster judgment, better recommendations, clearer trade-offs, and fewer mistakes.

### Interaction Thesis

Use motion sparingly to create presence:

- Hero text and navigation fade upward on load.
- Route and itinerary sections reveal progressively as the reader scrolls.
- Comparison rows, destination chips, and affiliate modules respond with subtle hover/focus states.

No ornamental motion, scroll hijacking, autoplay-heavy media, or slow page transitions.

## 3. Premium Positioning

The site should speak to international travelers who are willing to pay for better decisions, not necessarily only luxury travelers.

Primary reader archetypes:

- First-time Vietnam travelers planning a 10-14 day route.
- Couples and families who want comfort, not backpacker chaos.
- Mid-range to premium travelers comparing destinations and hotels.
- Long-haul travelers from the US, UK, Australia, India, Singapore, and Europe.
- Repeat travelers looking for a better-planned second trip.

Brand promise:

> Choose Vietnam well.

Supporting promise:

> Clear routes, refined stays, honest trade-offs, and current travel logistics for international visitors.

## 4. Brand System

### Name Treatment

Primary wordmark:

- `VietnamGuide.net`

Short mark:

- `VG`

The brand should appear strongly in the first viewport, not only in a small nav label. On the homepage hero, the brand should be visually equal to or stronger than the headline.

### Tone

Use plain English. Avoid luxury cliches.

Good:

- "Choose the right route for your first trip."
- "A calmer alternative to Ha Long Bay."
- "Best for couples who want comfort and food."
- "Skip this route if you only have seven days."

Avoid:

- "Unlock hidden gems."
- "Indulge in timeless wonders."
- "Your gateway to unforgettable experiences."
- "Discover paradise."

## 5. Design Tokens

### Color Palette

The interface must avoid becoming a one-note beige or gold luxury theme. Use a broad but restrained palette:

- Ink: `#101417` for primary text and dark surfaces.
- Soft paper: `#F7F4ED` for page background.
- Limestone: `#E5DED1` for subtle section contrast.
- River jade: `#0E6F5C` for primary actions and active states.
- Deep river blue: `#173B56` for secondary accents and map/route references.
- Quiet gold: `#B98739` for premium details, dividers, small highlights.
- Clay coral: `#C95F4A` for warnings, seasonal notes, and contrast accents.
- White: `#FFFFFF` for editorial content surfaces.

Usage rules:

- Ink and white carry readability.
- Jade is the main interactive color.
- Gold is an accent, never a large background.
- Coral is reserved for warnings and urgent travel notes.
- Avoid large beige-only sections; mix image, ink, white, jade, and structured content.

### Typography

Recommended pairing:

- Display: a refined serif such as `Cormorant Garamond`, `Fraunces`, or `Libre Baskerville`.
- Body/UI: a modern sans such as `Inter`, `Source Sans 3`, or system UI.

Rules:

- Letter spacing stays `0` for normal text.
- Use serif for brand moments, hero headlines, and section titles.
- Use sans for navigation, metadata, tables, buttons, and guide content.
- Do not scale type with viewport width beyond normal responsive clamps.
- Body text should be 17-19px on article pages.
- Reading line length should stay around 65-78 characters.

### Spacing

Base spacing scale:

- 4px micro.
- 8px compact.
- 16px content gap.
- 24px section gap.
- 40px block gap.
- 72px major section gap.
- 112px premium editorial section gap.

### Radius and Borders

- Buttons: 6-8px radius.
- Input fields: 6-8px radius.
- Cards only where the unit is repeated or interactive: 8px max.
- Page sections should not be styled as floating cards.
- No cards inside cards.

### Imagery

Photography is essential. The site needs a real visual anchor on homepage, destination pages, itinerary pages, and major guides.

Image rules:

- Use real destination photography, not abstract gradients.
- Prefer wide, calm images with room for text.
- Avoid overly dark, blurred, or generic stock-like hero images.
- Avoid embedded text, signs, or logos fighting the UI.
- Every image needs descriptive alt text.
- Store source/license details in media metadata or editorial records.

## 6. Global UX Architecture

### Header

Desktop header:

- Left: wordmark.
- Center: primary nav.
- Right: search icon, "Plan your trip" button.

Primary nav:

- Plan
- Destinations
- Itineraries
- Compare
- Costs

Mobile header:

- Wordmark.
- Search icon.
- Menu icon.
- Full-screen menu with grouped links.

Header behavior:

- Transparent over homepage hero.
- Solid paper/white after scroll.
- Sticky only if performance remains smooth.

### Search

Search is a premium utility, not an afterthought.

Phase 1:

- Native WordPress search styled as an overlay.
- Suggested quick links: Visa, 10 days, Best time, Hanoi, Ha Long vs Lan Ha.

Phase 2:

- Relevanssi or similar improved search.
- Search result grouping by content type.

### Footer

Footer should reinforce trust:

- Short brand statement.
- Plan links.
- Destination links.
- Legal and affiliate disclosure.
- About.
- Contact.
- Update policy.
- Source policy.

Avoid a cluttered mega-footer.

## 7. Homepage Design

The homepage should be a premium orientation surface, not a generic blog archive.

### First Viewport

Hero design:

- Full-bleed cinematic Vietnam image.
- Brand is prominent.
- Headline: "Vietnam for travelers who choose well."
- Supporting line: "Curated routes, refined stays, and practical guidance for planning your trip with confidence."
- Primary action: "Start planning"
- Secondary action: "See itineraries"
- Search or route selector visible but not inside a decorative card.

Hero should hint at the next section on desktop and mobile, so users understand there is content below.

### Homepage Sections

1. **Hero**
   - Brand, promise, action, image.

2. **Trip Planning Strip**
   - Choose by trip length: 7 days, 10 days, 14 days, 21 days.
   - Choose by style: First trip, Food, Beach, Family, Premium.

3. **Where To Begin**
   - Three direct paths:
     - Plan essentials.
     - Compare destinations.
     - Choose an itinerary.

4. **Signature Itineraries**
   - 10 days, 14 days, Northern Vietnam, Premium Vietnam.
   - Present as editorial rows, not generic cards.

5. **Destination Edit**
   - Curated set of major places: Hanoi, Hoi An, Ninh Binh, Lan Ha, Ha Giang, Phu Quoc.
   - Each destination includes "best for" and "skip if" microcopy.

6. **Decision Guides**
   - Ha Long vs Lan Ha.
   - Sapa vs Ha Giang.
   - Hanoi vs Ho Chi Minh City.
   - Da Nang vs Nha Trang.

7. **Practical Essentials**
   - E-visa.
   - Best time.
   - Travel cost.
   - SIM/eSIM.
   - Safety.

8. **Newsletter / PDF Lead Magnet**
   - "Vietnam First Trip Checklist"
   - Minimal copy, high trust.

## 8. Page Template Designs

### Destination Page

Example: `/destinations/hoi-an/`

Above the fold:

- Full-width destination photo.
- Breadcrumb.
- Destination name.
- One-sentence verdict.
- "Best for" tags.
- "Not ideal for" tag.
- Updated date.

Main layout:

- Article column.
- Sticky "At a glance" rail on desktop.
- Mobile converts rail into horizontal summary block.

Required sections:

- Quick verdict.
- Best time.
- How many days.
- Where to stay.
- Things to do.
- Food and coffee.
- Costs.
- How to get there.
- Suggested itinerary pairings.
- Best alternatives.
- FAQ.
- Sources and update history.

Premium detail:

- Use a destination "fit score" summary:
  - Romance.
  - Food.
  - Culture.
  - Beach.
  - Family.
  - First-timer ease.

### Itinerary Page

Example: `/itineraries/14-days-in-vietnam/`

Above the fold:

- Wide route image or map-style visual.
- Route title.
- Quick route path.
- Ideal traveler.
- Budget range.
- Pace rating.

Main sections:

- Quick route summary.
- Day-by-day itinerary.
- Route map placeholder.
- Transport legs.
- Where to upgrade.
- What to skip.
- Budget version.
- Premium version.
- Hotel base recommendations.
- Related destination pages.

Premium detail:

- "Route confidence" module:
  - Pace.
  - Travel time.
  - Weather resilience.
  - First-time ease.
  - Premium upgrade potential.

### Comparison Page

Example: `/compare/ha-long-bay-vs-lan-ha-bay/`

Above the fold:

- Split image treatment.
- Direct verdict.
- "Choose Ha Long if..." and "Choose Lan Ha if..."

Main sections:

- Quick comparison table.
- Cost.
- Crowds.
- Scenery.
- Cruise quality.
- Access.
- Best for couples.
- Best for families.
- Best for first-timers.
- Recommended choice by traveler type.

Premium detail:

- Use sticky comparison headers on desktop.
- Include a "decision in 30 seconds" block near the top.

### Practical Guide Page

Example: `/plan/vietnam-evisa/`

Above the fold:

- Plain, trustworthy design.
- No cinematic overkill.
- Current status.
- Last reviewed date.
- Official source buttons.

Main sections:

- Quick answer.
- Eligibility.
- Step-by-step.
- Processing time.
- Border gates.
- Common mistakes.
- What to do if rejected.
- FAQ.

Premium detail:

- Use warning bands for volatile facts.
- Include source links in clear "Official sources" blocks.

### Affiliate/Recommendation Page

Example: `/plan/best-esim-for-vietnam/`

Rules:

- Lead with methodology.
- Show affiliate disclosure near top.
- Avoid fake objectivity.
- Rank only products that are actually relevant.
- Include "best for" categories rather than one universal winner.
- Add `sponsored` or `nofollow` link attributes as appropriate.

Design:

- Editorial recommendation rows.
- Comparison table.
- Clear pros/cons.
- Primary CTA buttons in jade.
- Gold accent only for "premium pick" labels.

## 9. Component Library

### Core Components

1. **Hero Editorial**
   - Full-bleed image.
   - Brand and headline.
   - Primary/secondary CTAs.

2. **Route Selector**
   - Trip length chips.
   - Style chips.
   - Links to itinerary pages.

3. **Quick Verdict**
   - Short answer.
   - Best for.
   - Skip if.
   - Last updated.

4. **At A Glance Rail**
   - Best months.
   - Days needed.
   - Budget.
   - Airport.
   - Region.

5. **Decision Table**
   - Used for comparisons.
   - Mobile-friendly stacked rows.

6. **Itinerary Timeline**
   - Day number.
   - Location.
   - Travel leg.
   - Sleep base.
   - Optional upgrade.

7. **Source Block**
   - Official source links.
   - Last reviewed.
   - What was checked.

8. **Premium Recommendation Row**
   - Product/tour/hotel area.
   - Best for.
   - Caveat.
   - CTA.
   - Disclosure support.

9. **Newsletter Capture**
   - Minimal form.
   - One practical lead magnet.
   - No popup in phase 1.

10. **Editorial Image Band**
    - Full-width image with caption and location metadata.

### Component Rules

- Components must be reusable as WordPress block patterns or custom blocks.
- Components must work without JavaScript where possible.
- Components must support mobile-first layouts.
- Components must not depend on a page builder.
- Components must include accessible labels and focus states.

## 10. WordPress Build Strategy

### Recommended Build Approach

Use a lightweight premium theme plus a child theme or custom CSS layer.

Recommended path:

- Start with GeneratePress, Kadence, Blocksy, or a minimal block theme.
- Create reusable block patterns for core content sections.
- Add ACF fields for structured metadata.
- Add custom CSS for brand tokens and premium layouts.
- Avoid Elementor/Divi-style heavy builders unless the project explicitly chooses speed of editing over performance.

### Why Not Fully Custom Theme First

A fully custom theme can be excellent, but for a new SEO site it creates more engineering work before the content engine proves itself. A lightweight premium theme with controlled custom patterns gives the best balance:

- Faster launch.
- Easier editing.
- Lower maintenance risk.
- Good performance.
- Upgrade path to custom theme later.

### Phase 2 Customization

After the first 50-100 pages:

- Convert most-used patterns into custom blocks.
- Build custom archive templates.
- Add structured route data.
- Add better search and itinerary filters.

## 11. Content-to-Conversion Flow

Premium monetization should feel like assistance, not sales.

### Conversion Ladder

1. Free guide page.
2. Internal route recommendation.
3. Practical lead magnet.
4. Newsletter.
5. Contextual affiliate recommendation.
6. Premium PDF itinerary.
7. Custom planning inquiry.

### Affiliate Placement Rules

Allowed placements:

- After a relevant decision section.
- Inside "recommended next step".
- In comparison tables with disclosure.
- On dedicated recommendation pages.

Avoid:

- Above-the-fold affiliate CTAs on informational guides.
- Too many buttons in destination articles.
- Auto-inserted links in every paragraph.
- Affiliate modules on visa/safety pages unless extremely relevant and disclosed.

## 12. Advanced SEO Plan

### Topical Authority Layers

Layer 1: Pillars

- Vietnam Travel Guide.
- Vietnam Itinerary Guide.
- Vietnam Visa Guide.
- Vietnam Best Time Guide.
- Vietnam Travel Cost Guide.

Layer 2: Decision Pages

- Destination comparisons.
- Route comparisons.
- "How many days" content.

Layer 3: Practical Support

- Transport.
- SIM/eSIM.
- Cash/cards.
- Safety.
- Weather.

Layer 4: Monetization Support

- Best eSIM.
- Best cruises.
- Best areas to stay.
- Best day trips.
- Best travel insurance.

### Search Experience Requirements

Each SEO page should include:

- Clear answer in first screen.
- Table of contents.
- Quick verdict.
- Decision table when useful.
- Relevant internal links.
- Source block for volatile facts.
- Author/reviewer info.
- Last updated/reviewed date.
- Descriptive image alt text.

### Indexation Rules

Index:

- Evergreen guides.
- Destination pages.
- Itineraries.
- Comparison pages.
- Practical guides.

Noindex:

- Thin tag archives.
- Internal search result pages.
- Low-value author archives until author pages are built.
- Staging/test pages.

## 13. Image and Media Plan

### Initial Media Needs

Minimum launch library:

- 1 homepage hero image.
- 12 destination hero images.
- 6 itinerary/route images.
- 8 practical guide images.
- 10 editorial inline images.

Preferred sources:

- Original photography.
- Licensed stock with clear usage rights.
- Tourism board assets only when usage rights allow.

Do not use images without license clarity.

### Image Metadata

For each media item, store:

- Location.
- Photographer/source.
- License.
- Alt text.
- Caption.
- Usage notes.

## 14. Accessibility

Requirements:

- Strong contrast over hero images.
- Keyboard-visible focus states.
- Button labels that make sense out of context.
- No text embedded in images as the only source.
- Captions for editorial images.
- Tables readable on mobile.
- Skip links if theme supports them.
- Reduced-motion handling for animations.

## 15. Premium Motion Guidelines

Allowed:

- Hero entrance fade/translate.
- Header background transition on scroll.
- Image reveal on scroll.
- Itinerary timeline reveal.
- Button hover states.
- Search overlay transition.

Avoid:

- Parallax that hurts readability.
- Scroll hijacking.
- Infinite motion.
- Heavy animated maps in phase 1.
- Motion that delays content visibility.

## 16. Build Milestones

### Milestone A: Premium Foundation

Deliverables:

- Theme selected and installed.
- Child theme or custom CSS layer created.
- Design tokens configured.
- Header/footer built.
- Homepage skeleton built.
- Core plugin stack installed.
- Performance baseline measured.

Acceptance criteria:

- Mobile homepage loads cleanly.
- No layout shift in header/hero.
- Brand is unmistakable in first viewport.
- Search and navigation are usable.

### Milestone B: Template System

Deliverables:

- Destination template.
- Itinerary template.
- Comparison template.
- Practical guide template.
- Source/update block.
- Quick verdict block.
- At-a-glance block.

Acceptance criteria:

- At least one sample page exists for each template.
- Templates work on mobile and desktop.
- Editors can create pages without custom coding each time.

### Milestone C: SEO Launch Content

Deliverables:

- 20 priority pages published or staged.
- Internal links complete.
- Sitemap submitted.
- Search Console configured.
- Core pages reviewed for metadata and schema.

Acceptance criteria:

- No orphan priority pages.
- No accidental noindex on live pages.
- No thin tag archive indexation.
- PageSpeed passes reasonable mobile checks.

### Milestone D: Premium Conversion Layer

Deliverables:

- Newsletter capture.
- PDF lead magnet page.
- Affiliate disclosure page.
- First affiliate-ready recommendation template.
- Outbound click tracking.

Acceptance criteria:

- Affiliate modules are not intrusive.
- Disclosure is visible.
- Events track correctly.

### Milestone E: Scale and Authority

Deliverables:

- 50-100 pages.
- Comparison hub.
- Destination hub.
- Itinerary hub.
- Monthly update workflow.
- Content refresh process.

Acceptance criteria:

- Search Console impressions visible by cluster.
- Pages ranking 8-20 identified for refresh.
- Content update dates maintained.

## 17. Homepage Wireframe

Desktop order:

1. Transparent header over full-bleed hero.
2. Hero with brand, headline, short copy, two CTAs.
3. Trip-length selector.
4. "Begin with the right decision" section.
5. Signature itineraries as editorial rows.
6. Destination edit with image-led rows.
7. Comparison guide band.
8. Practical essentials grid.
9. Newsletter/PDF capture.
10. Trust/footer.

Mobile order:

1. Header with wordmark, search, menu.
2. Hero image and brand.
3. Primary CTA.
4. Trip selector.
5. Practical essentials.
6. Itineraries.
7. Destinations.
8. Comparisons.
9. Newsletter.

## 18. Editorial Governance

Premium feel depends on editorial discipline.

Rules:

- Every guide gets a clear verdict.
- Every volatile fact gets a review date.
- Every affiliate page gets a methodology.
- Every destination page says who should skip it.
- Every itinerary says what to remove if time is short.
- Every recommendation includes a caveat.

## 19. Immediate Next Implementation Plan

After this spec is approved, the first implementation plan should cover:

1. Server and WordPress hardening.
2. DNS/SSL verification.
3. Theme selection and setup.
4. Plugin stack installation.
5. Global design tokens.
6. Header/footer.
7. Homepage premium layout.
8. Reusable blocks/patterns.
9. Sample destination, itinerary, comparison, and practical pages.
10. SEO and analytics setup.
11. Performance verification.

## 20. Success Criteria

The premium design succeeds if:

- The site feels trustworthy within 5 seconds.
- Users can choose their next planning step from the homepage.
- Pages are beautiful but still fast.
- Article templates make long content easier to scan.
- The visual system scales to 100+ pages.
- Affiliate recommendations feel curated, not pushed.
- The site can compete with larger publishers through clarity and specificity.

## 21. Review Questions

Before implementation, confirm:

- The selected premium direction remains Quiet Luxury Concierge.
- The site should launch English-only.
- WordPress should use a lightweight theme rather than a heavy builder.
- Monetization should wait until the foundation content is live.
- The homepage should prioritize planning paths over latest posts.


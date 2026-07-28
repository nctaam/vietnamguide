# VietnamGuide Guide Experience System Design

Date: 2026-07-28
Status: Pending written-spec review

## 1. Decision

Build a reusable Guide Experience System for long-form VietnamGuide pages using the approved **Stitch + Editorial Spine** direction.

The system keeps the visual language of the Stitch project `Vietnam Travel Experience Portal` while adding the navigation, trust, resilience, and editorial structure required by long WordPress guides. It will launch through four allowlisted pilot pages before expanding by hub.

## 2. Approved Direction

### Visual thesis

Use Stitch's cinematic travel imagery, Deep Forest and Harvest Gold palette, Source Serif 4 display typography, Hanken Grotesk interface typography, editorial spacing, and restrained 12-column composition. Long-form pages should feel like premium field guides rather than generic WordPress articles or card-heavy travel portals.

### Content thesis

Help readers make one travel decision at a time. Every page opens with a clear verdict and context, supports scanning through a stable outline, then deepens into evidence, logistics, trade-offs, and related routes.

### Interaction thesis

Use only interactions that improve orientation:

1. Sticky desktop table of contents.
2. Mobile horizontal jump navigation.
3. Reading progress and header-surface enhancement when JavaScript is available.

The page remains fully readable and navigable without JavaScript.

## 3. Stitch References

Project: `projects/10120543989568032144`

Primary references:

- `Vietnam Guide | Itineraries (Final Premium) - Desktop`
  - Screen: `5fa3ac7a481e48f8b4a30f5d566cc5c3`
- `Vietnam Guide | Itineraries (Final Premium) - Mobile`
  - Screen: `94a8ddbe18f34278807bc32687304e37`
- `Vietnam Guide | Northern Bays (Decision Matrix) - Desktop`
  - Screen: `472d133a483340bcb7f35fa7e47dcee4`
- `Vietnam Guide | Northern Bays (Mobile Perfection)`
  - Screen: `2a8f5dec610f42ac810181c4a32e9147`
- `Vietnam Guide | Destinations (Final Cinematic) - Desktop`
  - Screen: `ea4c0dea6b1d4400971c24c315df35f8`
- `Vietnam Guide | Destinations (Final Cinematic) - Mobile`
  - Screen: `2b61e3fdc79d45fe826ff9f69e63ff31`

Stitch is the source for art direction, hierarchy, image rhythm, typography, color, and component tone. The WordPress system may adapt the exact layout where necessary for accessibility, long-form reading, existing content compatibility, and editor maintenance.

## 4. Current-State Findings

The homepage and reusable pattern system are established, but internal pages still use the generic singular branch in `index.php`.

Confirmed production gaps:

- Representative destination, itinerary, comparison, and practical guides render two identical H1 elements.
- The generic template adds a title before guide content that already owns a cinematic hero and H1.
- Existing guide content uses stable classes such as `vg-guide-hero`, `vg-concierge-verdict`, `vg-at-a-glance`, `vg-decision-table`, `vg-timeline`, and `vg-related-routes`, but the active theme does not yet provide a complete page-level layout contract for those classes.
- `homepage.css` and `guide-patterns.css` load globally even though the long-form guide system needs its own scoped asset.
- The footer links to `/source-policy/`, while the published source policy is `/source-update-policy/`.
- The site already contains more than 50 published planning, destination, itinerary, comparison, and cost pages, so per-page hard-coded templates will not scale.

## 5. Goals

1. Render exactly one H1 on every guide page.
2. Provide a premium, consistent reading experience across destination, itinerary, comparison, and practical guides.
3. Preserve existing Gutenberg content and URLs.
4. Add a server-rendered table of contents, trust context, and related-route behavior.
5. Provide safe fallbacks for missing or irregular content.
6. Keep the system progressively enhanced and independent of a page builder.
7. Validate four pilot pages before hub-wide rollout.
8. Establish reusable contracts that future guide content can follow.

## 6. Non-Goals

- Rewriting the full content library during this phase.
- Creating a new custom post type.
- Replacing Gutenberg or introducing a heavy page builder.
- Building accounts, saved trips, booking inventory, maps, or a recommendation engine.
- Adding intrusive newsletter, affiliate, or interstitial modules to the reading flow.
- Automatically converting every existing page before the pilot gate passes.

## 7. Pilot Scope

The initial allowlist contains one page for each guide type:

| Type | Page | URL |
|---|---|---|
| Destination | Ho Chi Minh City Travel Guide | `/destinations/ho-chi-minh-city-travel-guide/` |
| Itinerary | 10 Days in Vietnam | `/itineraries/10-days-in-vietnam/` |
| Comparison | Ha Long Bay vs Lan Ha Bay | `/compare/ha-long-bay-vs-lan-ha-bay/` |
| Practical | Vietnam E-Visa Guide | `/plan/vietnam-evisa/` |

Pages outside the allowlist continue through the current template until rollout is explicitly expanded.

## 8. WordPress Architecture

### Template routing

Use conventional WordPress template routing rather than a global output-rewrite filter.

- A page-level template decides whether the current page qualifies for the Guide Experience System.
- Allowlisted pages use the guide shell.
- Non-guide pages use the default page presentation.
- Archive and search behavior remains unchanged.

### Guide classification

Classification priority:

1. Explicit guide-type override supplied by the guide context API.
2. Parent hub slug.
3. URL path segment.
4. No match, which returns the default page template.

Supported types:

- `destination`
- `itinerary`
- `comparison`
- `practical`

The initial release uses an explicit pilot allowlist in addition to the classifier. A classification match alone does not enable the new template during the pilot.

### Content preservation

Existing post content remains the content authority.

The renderer may extract only a recognized leading top-level guide hero block so it can sit outside the three-column reading spine. Extraction requires a stable `vg-guide-hero` marker. If the leading structure is not recognized, the renderer does not attempt a risky transformation and falls back to the default page template.

After extraction, remaining serialized Gutenberg blocks pass through the normal WordPress content filters. Shortcodes, embeds, block rendering, image markup, and editor-authored links retain standard behavior.

## 9. Guide Context Contract

The template consumes one normalized guide context object. The provider owns classification and fallbacks; template parts do not query arbitrary page data independently.

Required context fields:

- `post_id`
- `type`
- `title`
- `permalink`
- `hero_html`
- `body_html`
- `headings`
- `reviewed_at`
- `reading_time`
- `source_count`
- `best_for`
- `skip_if`
- `related_routes`
- `has_existing_related_routes`

Field precedence:

1. Explicit editor or page metadata where available.
2. Stable semantic markers in existing rendered content.
3. Safe computed values.
4. Empty value, which hides the optional module.

No optional field may render an empty label, blank card, placeholder copy, or broken link.

## 10. Shared Page Anatomy

Every enabled guide follows this order:

1. Global site header.
2. Full-bleed cinematic guide hero containing the only H1.
3. Compact metadata strip.
4. Quick verdict or first decision block.
5. Editorial Spine reading layout.
6. Existing related-routes module or generated fallback.
7. Source and update context.
8. Global footer.

### Hero

- Full-width visual plane.
- Narrow text column anchored to a calm part of the image.
- One H1 only.
- Optional type kicker and concise lede.
- Existing hero imagery remains authoritative.
- Missing imagery uses a restrained Deep Forest tonal surface rather than an unrelated stock image.

### Metadata strip

May include:

- Guide type.
- Trip length or recommended stay where available.
- Reading time.
- Last reviewed date.
- Source count when confidently available.

Missing values collapse without leaving separators or whitespace artifacts.

### Editorial Spine

Desktop uses three coordinated columns:

- Left: sticky table of contents.
- Center: primary reading column.
- Right: compact trust and decision rail.

The center column remains the dominant visual and semantic region. Side rails must not make the article feel like a dashboard.

### Mobile transformation

- The left rail becomes a horizontal jump-navigation row below the metadata strip.
- The right rail becomes inline context blocks near the verdict and source sections.
- The article remains one column.
- Full-bleed media may extend to the viewport edge while body copy retains readable gutters.
- No sticky side panel survives on mobile.

## 11. Table of Contents

- Generated server-side from rendered H2 headings in the guide body.
- Uses deterministic, unique IDs.
- Preserves an existing valid heading ID.
- Resolves duplicate IDs with stable numeric suffixes.
- Excludes headings marked as decorative or explicitly opted out.
- Does not render when fewer than two eligible headings exist.
- Links remain ordinary fragment URLs.
- JavaScript may enhance active-section state but is not required for navigation.

## 12. Trust Rail

The trust rail supports, but does not interrupt, the article.

Potential modules:

- Best for.
- Skip if.
- Last reviewed.
- Source count.
- Guide type or route fit.

Extraction and fallback rules:

- Explicit metadata wins.
- Existing verdict blocks may supply best-for and skip-if values when semantic markers are present.
- Last reviewed falls back to the post modified date.
- Source count appears only when the provider can count a recognized source region confidently.
- A missing value removes the module entirely.

The quick verdict remains in the article even when a short summary appears in the rail. The rail must not duplicate full paragraphs.

## 13. Guide-Type Modules

### Destination

Preferred modules:

- Seasonal fit.
- Recommended stay length.
- District or area selection.
- Priority map or shortlist.
- Local transport.
- Day trips.
- Stay and route fit.

### Itinerary

Preferred modules:

- Route overview.
- Pace and audience fit.
- Day-by-day timeline.
- Transfer logic.
- Swap and skip options.
- Booking sequence.
- Seasonal pivots.

### Comparison

Preferred modules:

- Quick verdict.
- Decision matrix.
- Trade-offs.
- Choose-this-if guidance.
- Route fit.
- Accessibility or logistics differences.
- Final recommendation.

### Practical

Preferred modules:

- Official facts.
- Step-by-step process.
- Required checklist.
- Common mistakes.
- Time-sensitive warning.
- Update or expiry context.
- Official source links.

Existing content that does not match a preferred module still renders safely in the primary reading column.

## 14. Related Routes

Priority order:

1. An existing editor-authored `vg-related-routes` section.
2. Explicit related-page metadata.
3. Up to three published sibling pages under the same hub, excluding the current page.
4. The parent hub only.

The template must not render a second related-routes section when the existing content already contains one.

Generated related links use descriptive titles and ordinary anchors. They do not require JavaScript and do not insert affiliate destinations automatically.

## 15. Styling System

Guide-specific styles live in a scoped asset loaded only for enabled guide pages.

Visual tokens align with the existing theme and Stitch references:

- Deep Forest for navigation, anchoring surfaces, and high-emphasis text.
- Harvest Gold for labels, active states, and restrained accents.
- Paper White and Sand Stone for reading surfaces and separators.
- Source Serif 4 for display and editorial headings.
- Hanken Grotesk for body, metadata, navigation, and controls.

Rules:

- No generic card grid in the reading flow.
- No more than one dominant visual idea per section.
- Borders and shadows remain subtle.
- Tables support horizontal overflow without clipping page content.
- Full-width media does not force body copy wider than the reading measure.
- Existing guide classes receive compatibility styling where necessary.

## 16. JavaScript Enhancements

JavaScript may provide:

- Active table-of-contents state.
- Reading progress.
- Mobile jump-navigation affordance.
- Existing mobile-menu and header-surface behavior.

Constraints:

- No content rendering dependency.
- No client-side heading discovery required for the base TOC.
- No layout that shifts after script execution.
- Respect `prefers-reduced-motion`.
- Avoid scroll listeners that run unthrottled on every frame.
- The guide enhancement bundle should remain small and separately enqueued.

## 17. Accessibility

- Exactly one H1.
- Logical heading order after hero extraction.
- Visible keyboard focus on navigation, TOC, tables, and related links.
- Skip link continues to target the main content landmark.
- TOC uses a labelled navigation landmark.
- Trust rail uses complementary semantics only when it contains meaningful content.
- Mobile jump links meet touch-target requirements.
- Comparison tables retain headers and readable scroll behavior.
- Images keep editor-authored alt text and dimensions.
- High zoom does not create overlapping sticky elements.
- Reduced motion disables transform-based entrance effects and smooth scrolling.

## 18. SEO and Semantics

- Preserve canonical page URLs and Rank Math integration.
- Emit one H1 and semantic article structure.
- Keep all navigation and related routes as crawlable links.
- Do not duplicate article content in hidden mobile or desktop variants.
- Reviewed-date and source context must not claim precision the provider cannot verify.
- Existing schema remains authoritative during the pilot.
- The template must not add duplicate Article, FAQ, or Breadcrumb schema.

## 19. Performance Budget

- No new third-party frontend dependency.
- Load guide assets only on enabled pages.
- Keep guide JavaScript progressive and small.
- Do not preload non-hero guide images.
- Preserve explicit image dimensions.
- Lazy-load below-the-fold imagery using WordPress defaults.
- Avoid layout shifts from trust rail, TOC, tables, and media.
- Target LCP <= 2.5 seconds, CLS <= 0.1, and INP <= 200 milliseconds on a representative mobile connection.

## 20. Failure and Fallback Behavior

The system fails closed to the current page presentation.

Fallback conditions include:

- Page not in the pilot allowlist.
- Unsupported or ambiguous guide type.
- Missing post object.
- Unrecognized leading hero structure.
- Failed content split.
- Invalid normalized context.

Optional-module failures do not disable the whole template:

- No eligible headings: omit TOC.
- No trust values: omit trust rail.
- No confident source count: omit count.
- No related pages: link only to the parent hub or omit the section.
- No hero image: use the tonal fallback surface.

Frontend JavaScript errors must not hide content, links, navigation, or tables.

## 21. Deployment and Rollback

The pilot deployment changes theme code and assets only. It does not rewrite production post content or bulk-update the database.

Deployment sequence:

1. Verify local contracts and fixtures.
2. Deploy guide theme files through the available guarded deployment route.
3. Purge theme and page caches.
4. Verify the four pilot pages.
5. Leave non-pilot pages unchanged.

Rollback options:

1. Disable the pilot allowlist.
2. Restore the prior theme files from deployment backups.

Because content is not rewritten, rollback does not require restoring page revisions.

The adjacent footer defect is fixed in this phase by linking Source policy to `/source-update-policy/`.

## 22. Verification Strategy

### Automated contracts

Verify:

- Pilot allowlist and classifier behavior.
- Default-template fallback.
- Exactly one H1 in rendered guide fixtures.
- Unique heading IDs.
- Server-rendered TOC links.
- Missing-metadata collapse.
- Existing related-routes deduplication.
- Parent-hub fallback.
- Scoped asset enqueueing.
- Footer source-policy URL.
- JavaScript syntax.
- Theme JSON parsing.
- Whitespace and patch integrity.

Mutation checks must demonstrate that the verifier rejects at least:

- Reintroduced generic H1 output on guide pages.
- Unconditional guide activation outside the allowlist.
- Duplicate related-routes output.
- TOC generation from H1 or decorative headings.
- Broken fallback that returns an empty page.
- Global guide asset enqueueing.

### Visual and interaction QA

Capture and compare:

- Desktop at 1440px.
- Mobile at 390px.
- High zoom.
- Reduced-motion mode.
- JavaScript-disabled rendering.

Check:

- Hero and title hierarchy.
- Sticky TOC containment.
- Mobile jump navigation.
- Trust rail transformation.
- Long-title wrapping.
- Table overflow.
- Full-bleed media.
- Keyboard path and focus visibility.

### Production gate

Each pilot must satisfy:

- HTTP 200.
- Exactly one H1.
- Expected guide stylesheet and script load.
- No fatal-error text.
- No browser console errors caused by guide assets.
- Existing content sections remain present.
- TOC fragment links resolve.
- No obvious layout shift or horizontal page overflow.
- Source and related-route links remain valid.

## 23. Rollout After Pilot

Expansion happens hub by hub, not all at once:

1. Itineraries.
2. Comparisons.
3. Practical planning guides.
4. Destinations.

Before each expansion:

- Sample long, short, image-heavy, table-heavy, and irregular pages.
- Confirm classifier accuracy.
- Add compatibility selectors only when they serve a repeated content pattern.
- Run the complete local and production gate again.

## 24. Acceptance Criteria

The design is implemented successfully when:

1. All four pilot URLs use Stitch + Editorial Spine.
2. Each pilot renders exactly one H1.
3. Existing guide content and URLs remain intact.
4. Desktop TOC and mobile jump navigation work without blocking content.
5. Trust and related-route modules obey fallback and deduplication rules.
6. Destination, itinerary, comparison, and practical modules retain distinct semantics within one shared shell.
7. Keyboard, reduced-motion, high-zoom, and JavaScript-disabled checks pass.
8. Guide assets are scoped to enabled pages.
9. Non-pilot pages remain unchanged.
10. The footer source-policy link resolves to the published page.
11. Automated contract, mutation, visual, and production checks pass.
12. The system is ready for a hub-by-hub rollout without per-page template forks.

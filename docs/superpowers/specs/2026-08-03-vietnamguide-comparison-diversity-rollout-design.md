# VietnamGuide Comparison Diversity Rollout Design

Date: 2026-08-03 (Asia/Saigon)

Status: Revision 2 written and independently reviewed; pending user approval before implementation planning.

## Goal

Roll out the existing Stitch + Editorial Spine Guide Experience to every published comparison guide and add a structured Evidence + Decision System that makes the portfolio materially more diverse, current, useful, and auditable across evidence sources, decision format, traveler profile, related content type, and Vietnamese locality coverage.

## Background

The comparison hub currently has ten published comparison guides. `compare/ha-long-bay-vs-lan-ha-bay` already uses the Guide Experience. The other nine pages are published and compatible with the shared guide parser, but still use the default page template and render two H1 elements.

Public and REST audits on 2026-08-03 confirmed that every target returns HTTP 200, contains one H1 in its stored page content, has a comparison hero, has no fatal text, and contains between 8 and 17 H2 headings and between 5 and 11 tables.

This rollout is intentionally more than an allowlist expansion. It includes a guarded editorial metadata update, a reusable source registry, claim-level evidence coverage, comparison-specific decision framing, and narrowly scoped module insertion so the pages expose a visible, varied evidence trail and lead readers into different guide types and regions without duplicating the articles.

## In Scope

### Comparison pages

| Path | Post ID | Decision archetype | Locality coverage | Content stress |
| --- | ---: | --- | --- | --- |
| `compare/cu-chi-tunnels-vs-mekong-delta-day-trip` | 279 | Competing day trips | Ho Chi Minh City, Cu Chi, Mekong Delta | 9 H2, 5 tables |
| `compare/da-nang-vs-hoi-an` | 209 | City base versus heritage base | Da Nang, Hoi An, Quang Nam | 12 H2, 7 tables |
| `compare/hoi-an-vs-hue` | 227 | Heritage city choice | Hoi An, Quang Nam, Hue | 14 H2, 9 tables |
| `compare/mui-ne-vs-nha-trang` | 247 | Mainland coast choice | Binh Thuan, Khanh Hoa | 13 H2, 7 tables |
| `compare/ninh-binh-day-trip-vs-overnight` | 326 | Time-allocation choice | Ninh Binh | 17 H2, 11 tables |
| `compare/north-central-south-vietnam` | 104 | National macro-region choice | Northern, Central, Southern Vietnam | 8 H2, 5 tables |
| `compare/old-quarter-vs-french-quarter-vs-west-lake` | 301 | Three-way neighborhood choice | Hanoi | 15 H2, 10 tables |
| `compare/phu-quoc-vs-nha-trang` | 241 | Island versus city-beach choice | Kien Giang, Khanh Hoa | 13 H2, 7 tables |
| `compare/trang-an-vs-tam-coc` | 336 | Attraction and landscape choice | Ninh Binh | 17 H2, 11 tables |

After rollout, all ten published comparison guides use the Guide Experience. The `/compare/` hub page remains on the default hub template.

### Controlled data changes

The rollout may update only these fields for the nine target posts:

- `vg_eeat_primary_decision`
- `vg_eeat_reviewed_guide`
- `vg_eeat_written_by`
- `vg_eeat_reviewed_by`
- `vg_eeat_last_meaningful_update`
- `vg_eeat_update_summary`
- `vg_eeat_sources_checked`
- `vg_eeat_source_assignments`
- `vg_eeat_field_note`
- `vg_eeat_evidence_moat`
- `vg_eeat_related_routes`
- `vg_eeat_comparison_archetype`
- `vg_eeat_compared_localities`
- `vg_eeat_decision_axes`
- `vg_eeat_traveler_lenses`
- `vg_eeat_source_registry_version`
- `vg_eeat_comparison_bundle`

Stored post content may change only to add a missing source-trail shortcode and a missing update-log shortcode in the approved end-of-article module sequence. Existing modules may not be moved. No prose, table, heading, hero, image, Rank Math metadata, schema, slug, parent, menu order, author, or publish status may be rewritten by this rollout.

The theme and core MU plugin may receive scoped, server-rendered support for comparison archetypes, decision axes, traveler lenses, grouped source trails, and grouped related routes. These additions must reuse the existing Stitch + Editorial Spine tokens and require no new frontend dependency or network request.

## Out of Scope

- Rewriting the comparison articles.
- Adding new comparison URLs.
- Redesigning the global visual language, tokens, or shared guide shell. Comparison-specific server-rendered modules and minimal scoped CSS are allowed when they reuse the existing system.
- Enabling destination or practical-guide content pages beyond existing pilots.
- Changing the `/compare/` hub design.
- Changing Rank Math metadata, structured data, media, image credits, or affiliate configuration.
- Treating travel blogs, affiliate roundups, AI summaries, search result pages, or unattributed listicles as primary evidence.
- Displaying a synthetic numeric confidence score that implies more precision than the underlying evidence supports.
- Making external source-health requests during a public page request.
- Building personalized ranking, accounts, saved comparisons, live price aggregation, or an interactive trip-planning engine.
- Automatically rewriting recommendations when a source expires; stale evidence must be reviewed and explicitly replaced or labeled.

## Rollout Architecture

### 1. Explicit routing gate

Append the nine exact paths to `vg_guide_pilot_paths()`. Keep strict, ordered path matching and the existing type classifier. Do not introduce prefix-based or automatic comparison-hub enablement.

The resulting pilot inventory contains 17 exact paths: the existing destination, five itinerary, one comparison, and one practical pilots plus the nine comparison targets.

### 2. Exact verifier inventories

Update the local and mutation target-state inventories to the same ordered 17-path set. Preserve exact case-sensitive comparison. Live and public verification use an explicit stage inventory: the current eight pilots before rollout, 11 pilots after the three-page canary, and 17 pilots after Stage 2. The six not-yet-activated comparison paths must remain default-template controls during Stage 1.

The non-pilot public control set becomes:

- `compare`
- `destinations/hanoi-travel-guide`
- `plan/sim-esim-vietnam`
- `plan/transport-within-vietnam`

This proves that the comparison hub itself and representative destination and practical pages remain on their default templates.

### 3. Versioned editorial manifest and source registry

Create one local, version-controlled data manifest keyed by exact page path. Each entry contains:

- expected post ID, pre-update content fingerprint, and deterministic expected post-update fingerprint;
- comparison archetype, locality tags, three to six decision axes, and three to five traveler lenses;
- primary decision and field note;
- reviewed date, update summary, and evidence-moat lines;
- six to ten stable source IDs plus claim-group mappings and evidence labels;
- six to eight related-route records assigned to one of three route groups;
- required module-insertion flags.

Create a separate versioned source registry keyed by stable source ID. A registry record contains:

- stable publisher ID, publisher name, and canonical publisher domain;
- source class, responsible locality, and source language;
- canonical URL and optional canonical document title;
- freshness tier, checked date, and the claim groups the source can support.

Source IDs and publisher IDs remain stable when labels or checked dates change. A canonical URL may not appear under two source IDs. Publisher identity is bidirectional: one publisher ID resolves to exactly one canonical domain, and one canonical domain resolves to exactly one publisher ID. Page manifests reference registry IDs instead of copying source records. The deployment artifact records a source-registry version. The updater stores a normalized resolved assignment payload in `vg_eeat_source_assignments` and deterministically generates the legacy `vg_eeat_sources_checked` text from the same payload so existing trust modules remain compatible.

Both files are data only. A separate guarded apply script validates and installs them. This keeps editorial choices reviewable without mixing them into deployment logic.

### 4. Guarded WordPress updater

The updater must support validation and apply modes and must be idempotent.

Before any write it validates the complete nine-page manifest and source registry together. Production fingerprints are stage-aware: pages in the active stage must match their pre-update fingerprints, pages activated in an earlier stage must match their expected post-update fingerprints, and deferred pages must still match their pre-update fingerprints.

- exact path, post ID, page type, published status, and parent;
- the stage-appropriate pre-update or post-update content fingerprint;
- required hero marker and exactly one stored-content H1;
- no existing duplicate source-trail or update-log module;
- every external source URL and every internal related route passes its contract;
- every decisive recommendation has current claim-level evidence;
- every metadata entry satisfies the page-level and portfolio-level diversity rules.

It then records a complete backup of post content and every in-scope meta value for the exact rollout stage. Target-state metadata is first stored in the versioned `vg_eeat_comparison_bundle`; legacy fields remain unchanged while the path is inactive. If any write or post-write check fails, it restores every page changed during that stage. Cache purge occurs only after every page in the stage passes post-write verification.

## Source Diversity Contract

Each page must resolve six to ten unique source IDs from the typed registry. A resolved page-source assignment exposes publisher label, source class, locality, language, canonical URL, checked date, freshness tier, page-specific evidence label, mapped claim groups, and a short statement of what it establishes when that is not obvious.

Each page must include at least four of these five source classes:

1. National official: Vietnam National Authority of Tourism, national immigration, national transport, national weather, or another directly responsible national body.
2. Local official: municipal, provincial, destination-management, heritage-management, or local government sources tied to the compared places.
3. Operational: airport, railway, port, attraction operator, protected-area authority, museum, ticketing authority, or another first-party operational source.
4. Conditions and heritage: hydro-meteorological, marine, environmental, UNESCO, conservation, or cultural-heritage evidence.
5. Independent corroboration: a credible academic, institutional, cartographic, or established non-affiliate editorial source used to cross-check interpretation rather than replace primary evidence.

Additional rules:

- At least one national official source per page.
- At least two local official or operational sources tied to the compared localities.
- At least one conditions, heritage, or environmental source when the decision depends on weather, landscape, transport, or protected heritage.
- Multi-locality comparisons include evidence for both sides; the macro-region page includes evidence relevant to all three regions.
- No single domain contributes more than 40 percent of a page's source records, rounded down only when at least one source from every required class remains.
- URLs must use HTTP or HTTPS, resolve to the expected publisher, and must not be search-result, tracking-only, shortened, or affiliate URLs.
- URL verification rejects credentials, nonstandard ports, loopback, private, link-local, multicast, reserved, and cloud-metadata targets. DNS is resolved before each request and again after every redirect; every resolved address must be public.
- Deployment URL checks use bounded timeouts, response-size limits, at most three redirects, and final-domain publisher matching. Redirects to a different publisher, protocol downgrade, or a disallowed address fail closed.
- Vietnamese-language local sources are allowed and encouraged when they are the responsible first-party source.
- A checked date is mandatory and is preserved in the visible source trail.

Across the nine-page portfolio:

- at least 18 distinct publisher IDs must be represented;
- at least 12 distinct publishers must be local official or operational first parties;
- local official and operational records together must form at least 35 percent of all page-source assignments;
- no national publisher or canonical domain may appear on more than six target pages;
- each required geographic group must be supported by at least two publishers tied to that group, except the nationwide group, which must contain evidence for all three macro-regions;
- at least seven pages must include a Vietnamese-language local or operational source.

The validator evaluates both unique registry records and page-source assignments. Reusing an appropriate source across two related pages is allowed, but duplication cannot satisfy the distinct-publisher or local-first-party minimums.

## Claim-Level Evidence Contract

The Evidence + Decision System uses six claim groups:

1. Access and transport.
2. Timing and duration.
3. Cost and booking.
4. Season and current conditions.
5. Experience fit.
6. Constraints and safety.

Access and transport, timing and duration, and experience fit are mandatory for every page. The other groups become mandatory whenever the article or decision frame makes a recommendation that depends on them.

Every decision axis and every decisive recommendation in the comparison decision frame must map to at least one claim group and at least one valid source ID. A recommendation may use multiple sources. A source may support multiple related claim groups only when its registry record declares that coverage.

Evidence is described with one of three plain-language labels:

- `Primary`: directly responsible first-party evidence for the claim.
- `Corroborating`: independent or secondary evidence used to cross-check interpretation.
- `Live check required`: useful context whose operational value may change before the trip.

No numeric confidence score is calculated or displayed. A decisive recommendation cannot rely only on corroborating or stale evidence; it needs at least one current primary source unless the UI explicitly presents the decision as a live check rather than a settled recommendation.

## Freshness Contract

Every registry record uses one of three deterministic freshness tiers:

- `live`: prices, schedules, ticketing, closures, entry rules, and operating conditions; expires 30 days after its checked date;
- `current`: policy, transport patterns, seasonal guidance, weather or marine guidance, and destination operations; expires 180 days after its checked date;
- `stable`: geography, heritage status, durable cultural context, and long-lived infrastructure facts; expires 730 days after its checked date.

Freshness is evaluated during static build and deployment verification only. Public requests never fetch or health-check external sources.

An expired source fails preflight when it supports a settled decisive recommendation. It may remain only when the mapped claim is explicitly marked `Live check required`, the visible module preserves the checked date and direct URL, and another current source covers any stable part of the recommendation. Broken, redirected-to-unrelated, or publisher-mismatched URLs always fail preflight regardless of freshness label.

## Decision-Format Diversity Contract

The pages must not collapse into one repeated two-column template. Existing article content remains, but the editorial metadata and verification classify and preserve seven decision archetypes:

- Competing day trips: Cu Chi versus Mekong Delta.
- City or heritage base choice: Da Nang versus Hoi An; Hoi An versus Hue.
- Coast and island choice: Mui Ne versus Nha Trang; Phu Quoc versus Nha Trang.
- Time-allocation choice: Ninh Binh day trip versus overnight.
- Three-way neighborhood choice: Old Quarter versus French Quarter versus West Lake.
- Macro-region choice: Northern versus Central versus Southern Vietnam.
- Attraction and landscape choice: Trang An versus Tam Coc.

Each primary-decision statement must describe the actual choice mechanism for its archetype. Generic text such as "choose the best destination" is invalid.

Each archetype has a controlled decision-axis vocabulary:

| Archetype | Controlled axes |
| --- | --- |
| Competing day trips | transfer time, pace, experience depth, weather resilience, mobility fit |
| City or heritage base | walkability, transfer friction, evenings, day-trip reach, heritage immersion |
| Coast and island | seasonal weather, beach style, water conditions, resort-versus-city balance, transfer friction |
| Time allocation | travel overhead, crowd timing, overnight benefit, cost delta, itinerary fit |
| Neighborhood | noise, walkability, price level, atmosphere, transport access |
| Macro-region | season, trip length, route cohesion, intercity transport, experience range |
| Attraction and landscape | access mode, scenery, crowd timing, activity level, weather sensitivity |

Each page selects three to six axes from its archetype, including every axis used in the primary decision. Custom axes are allowed only when the manifest defines their meaning and claim-group mapping; generic labels such as "overall" or "best" are invalid.

Each page also declares three to five traveler lenses selected from a controlled vocabulary such as first-time visitor, short-on-time, slow traveler, family, budget-focused, comfort-focused, nightlife-oriented, beach-focused, culture-focused, and mobility-sensitive. The portfolio must use at least seven distinct lenses, and no page may render the same recommendation for every lens. Lens output explains who benefits and why; it does not create a second generic comparison table.

## Server-Rendered Comparison Experience

The existing Stitch + Editorial Spine guide shell remains the visual foundation. Comparison pages add four scoped enhancements:

1. A decision frame after the trust rail and before the long-form body. It shows the archetype, compared localities, primary decision, selected decision axes, and concise traveler-lens outcomes.
2. A source trail grouped by source class. Each row exposes publisher, locality, language when useful, checked date, claim coverage, and the `Primary`, `Corroborating`, or `Live check required` label.
3. Related routes grouped as `Deepen place`, `Build route`, and `Check practical` so the next action is explicit instead of a flat recommendation grid.
4. An update log that explains which decision inputs or evidence changed, rather than merely repeating a date.

All modules are rendered by PHP from validated stored metadata. For an active comparison path, the renderer reads the versioned comparison bundle as the authoritative snapshot and rejects a bundle whose registry version does not match the deployed artifact. It reuses existing typography, color, spacing, radius, focus, and motion tokens. It adds no JavaScript dependency, no client-side data fetch, no new font or image request, and no runtime external-source request.

The decision frame supplements the existing article; it must not copy full comparison tables or long prose. Semantic H2 and H3 headings, lists, links, and table wrappers remain keyboard accessible. Motion is optional enhancement only, and reduced-motion users receive the same information without transition-dependent disclosure.

The decision frame is bounded to one section heading, one primary-decision statement of at most 240 characters, three to six axis rows, and three to five traveler-lens outcomes. Axis labels are at most 48 characters, axis explanations at most 180 characters, and lens outcomes at most 220 characters. The complete frame contains at most 1,800 visible characters and no table. Manifest text is authored specifically for the frame rather than extracted from article tables.

## Runtime Data And Output Safety

- The comparison bundle uses a strict versioned schema with allowlisted keys, scalar types, enumerations, item counts, and length limits. Unknown keys, raw HTML, invalid UTF-8, control characters, and oversized payloads fail validation.
- The serialized bundle is capped at 64 KB per page and is read in one metadata operation; rendering may not issue per-source or per-route database queries.
- Text is escaped with `esc_html()`, attribute values with `esc_attr()`, and external or internal URLs with `esc_url()` at the final output boundary. Validation never substitutes for context-specific escaping.
- Shortcode attributes are not accepted for these modules. Renderer output cannot execute manifest-provided HTML, scripts, styles, event handlers, shortcodes, or block markup.
- Cache keys include the exact path, bundle version, source-registry version, and activation artifact version. Stage activation and rollback purge only the affected paths plus shared asset caches.

## Related-Route Diversity Contract

Each target page has six to eight unique, published internal related routes.

Every related-route set must include:

- at least one destination guide tied to each compared side where a matching destination guide exists;
- at least one itinerary guide;
- at least one practical guide;
- at least three guide types across destination, itinerary, comparison, practical, and cost content;
- at least three geographic scopes across Northern, Central, Southern, island/coast, and nationwide content;
- no more than two comparison links;
- no self-link, duplicate URL, fragment-only URL, unpublished target, redirecting target, or unrelated generic hub filler.

Every route is assigned to exactly one visible group:

- `Deepen place`: destination, neighborhood, attraction, or comparison content that clarifies one side.
- `Build route`: itinerary or routing content that turns the choice into a trip sequence.
- `Check practical`: transport, cost, entry, booking, season, or logistics content needed before acting.

Each page must render all three groups with at least one route per group. Across the portfolio, every group must contain at least three guide types and three geographic scopes. The route note must explain the next decision. Repeating the target title or using generic text such as "read more" is invalid.

## Locality Coverage Contract

The combined nine-page manifest must cover:

- Northern Vietnam: Hanoi and Ninh Binh;
- Central Vietnam: Da Nang, Hoi An, Quang Nam, and Hue;
- South-central coast: Binh Thuan and Khanh Hoa;
- Southern Vietnam: Ho Chi Minh City, Cu Chi, and the Mekong Delta;
- Island travel: Phu Quoc and Kien Giang;
- Nationwide routing: Northern, Central, and Southern Vietnam in one macro comparison.

Verification fails if a manifest edit removes an entire required geographic group from source or related-route coverage.

## Module Insertion Contract

All nine pages already render a related-routes module.

Add `[vg_source_trail]` only to the four pages currently missing a visible source trail:

- `compare/cu-chi-tunnels-vs-mekong-delta-day-trip`
- `compare/da-nang-vs-hoi-an`
- `compare/hoi-an-vs-hue`
- `compare/north-central-south-vietnam`

Add `[vg_update_log]` to all nine pages.

The canonical end-of-article order is:

1. existing `[vg_related_routes]` output;
2. `[vg_source_trail]`;
3. `[vg_update_log]`.

Insertion is marker-aware and idempotent. It must not duplicate an existing shortcode, pattern, source-trail class, related-routes class, or update-log class. Existing modules must already satisfy the canonical relative order; otherwise preflight stops for separate editorial review. The updater never moves an existing block.

For a staged comparison bundle whose path is not active, newly inserted source-trail and update-log shortcodes render no output. Pre-existing modules continue rendering their unchanged baseline output. Template routing, decision-frame rendering, enhanced source rendering, enhanced route grouping, and update-log rendering all depend on the same exact-path activation gate.

## Canary Rollout Contract

The source registry, complete nine-page manifest, renderer, updater, and verifier changes are reviewed and validated as one deterministic artifact. Production activation occurs in two exact stages behind an atomic routing gate.

Stage 1 activates three stress archetypes:

1. `compare/old-quarter-vs-french-quarter-vs-west-lake` for the longest three-way neighborhood title.
2. `compare/ninh-binh-day-trip-vs-overnight` for the densest heading and table structure.
3. `compare/north-central-south-vietnam` for the broadest geographic and decision scope.

For each stage, shared renderer support is deployed while the new paths remain disabled. The updater then backs up and writes the versioned pending bundle plus approved shortcode insertions; all legacy metadata remains unchanged. Stored-state verification must pass before the exact stage paths are added to the routing allowlist. The reviewed allowlist artifact is replaced atomically only after all paths in that stage are ready; if atomic artifact replacement is unavailable, the rollout stops before activation.

After cache purge, all static, live, public, desktop, mobile, keyboard, reduced-motion, source-freshness, and content-diff checks must pass for the three canary pages. A second independent uncached public request set must also pass before Stage 2 prepares and activates the remaining six paths.

After the activated stage passes its required public checks, the updater mirrors the authoritative bundle into the approved legacy metadata fields for compatibility and verifies that rendering remains byte-equivalent for the structured modules. This compatibility sync is idempotent and is not used as the renderer's source of truth.

Each stage has its own exact path list and complete pre-write backup. Before activation, a failed stage restores its page data while its paths remain disabled. After activation, a failed public check first disables the exact stage paths, purges caches, and then restores that stage's page data. A failed Stage 1 restores only the three canary pages and leaves the other six disabled. A failed Stage 2 restores only pages changed in Stage 2 while preserving the already verified canaries. Shared theme or MU-plugin artifacts remain independently reversible through their deployment backup.

## Performance And Accessibility Budgets

- No new frontend request, JavaScript bundle, font, image, or runtime external-source request is allowed.
- Additional server-rendered HTML is capped at 24 KB uncompressed UTF-8 per comparison page relative to its pre-rollout public HTML.
- Scoped CSS growth is capped at 8 KB uncompressed, with no new render-blocking asset.
- The hero remains the LCP candidate; the new modules reserve their layout server-side and may not introduce a module-attributable layout shift. Mobile Lighthouse CLS must remain at or below 0.10.
- Under the same throttling profile and three-run median, mobile LCP on each canary may not regress by more than 10 percent from its captured pre-rollout baseline.
- Decision and route groups use semantic headings and lists; external source links have descriptive accessible names; all table wrappers and interactive controls remain keyboard reachable with visible focus.
- At 200 percent zoom and 320 CSS pixels wide, no module causes document-level horizontal overflow or hides decision information.
- Source URL resolution and freshness checks run only in static, preflight, and deployment verification, never during page rendering.

## Data Flow

1. Public and REST audits identify the exact target page, baseline HTML metrics, and content shape.
2. The typed source registry defines reusable evidence records and freshness policy.
3. The reviewed page manifest selects source IDs, maps claims and decision axes, defines traveler lenses, groups related routes, and declares module requirements.
4. Static validation resolves the registry, generates compatibility text, and checks page-level plus portfolio-level contracts without WordPress writes.
5. The guarded updater validates all production preconditions and writes an exact backup for the current stage.
6. Shared renderer and verifier support is deployed from a deterministic reviewed artifact while new stage paths remain disabled.
7. The versioned pending comparison bundle and approved shortcode insertions are applied idempotently to the stage paths and verified in stored state without changing visible baseline output.
8. The exact stage routing gate is activated atomically, caches are purged, and WordPress runtime, public HTML, evidence labels, links, performance budgets, accessibility behavior, and browser layouts are verified twice for canaries and once for Stage 2.
9. After public verification, bundle data is mirrored into approved legacy fields for compatibility, byte-equivalent structured rendering is confirmed, the helper is removed, and registry version, validation evidence, and rollback artifacts are recorded.

## Failure And Rollback Behavior

- Any preflight failure aborts before the first write.
- A content-fingerprint mismatch aborts rather than merging with unknown editorial changes.
- A source, freshness, claim-coverage, decision-axis, traveler-lens, or related-route validation failure names the page and failing stable record ID.
- A missing, malformed, or registry-version-mismatched comparison bundle blocks activation and renders no enhanced module.
- A partial apply triggers restoration of every page changed in that rollout stage from the exact backup.
- Rollback restores post content, all in-scope meta values, modified timestamps where supported, and cache state through a fresh purge.
- Theme deployment remains independently reversible through the existing guarded artifact backup.
- Deployment helpers and transient status data are removed after successful verification or rollback.

## Verification Design

### Static and mutation verification

Add contracts for:

- the exact ordered target-state 17-path pilot inventory and exact 8/11/17 stage inventories;
- the exact non-pilot control set;
- all nine manifest entries and post IDs;
- stable unique source and publisher IDs, bidirectional canonical URL/domain identity, registry version, normalized assignment storage, and legacy compatibility-text generation;
- six-to-ten sources per page, required source-class coverage, valid source class, locality, language, and evidence label;
- freshness-tier expiry, checked dates, URL publisher matching, and the prohibition on runtime source fetching;
- SSRF-safe URL resolution, redirect, timeout, port, address-range, and response-size limits;
- mandatory claim groups and source coverage for every decision axis and decisive recommendation;
- page-level domain concentration and portfolio-level publisher, local-first-party, language, assignment-share, and geographic diversity;
- valid archetype axes and three-to-five traveler lenses per page plus portfolio lens diversity;
- six-to-eight related routes per page, three visible route groups, and guide-type/geography diversity;
- published, non-redirecting internal route targets;
- the four source-trail insertions and nine update-log insertions;
- module order and idempotence;
- server-rendered decision-frame placement and bounded output;
- strict bundle schema, size limits, single-read rendering, cache-version keys, and context-specific output escaping;
- exact three-page canary and six-page Stage 2 path inventories;
- versioned pending-bundle validation, inactive-path no-op behavior, stage-aware pre/post fingerprints, and data-before-routing atomic activation;
- unchanged prose outside approved module boundaries;
- stage-exact backup completeness and rollback behavior.

Permanent mutations must include at least:

- replacing a long comparison route with an unrelated path;
- accidentally enabling the `/compare/` hub;
- removing one locality group;
- reducing a page below four source classes;
- exceeding the source-domain concentration limit;
- expiring a decisive source without a valid live-check fallback;
- removing claim coverage from a decisive recommendation;
- duplicating a source ID or assigning one canonical URL to two IDs;
- assigning one publisher ID to conflicting canonical domains;
- assigning one canonical domain to two publisher IDs;
- using a source URL that resolves or redirects to loopback, private, link-local, reserved, or cloud-metadata space;
- using an invalid source class, locality, language, freshness tier, or evidence label;
- removing a mandatory decision axis or reducing a page below three traveler lenses;
- collapsing all traveler lenses to the same recommendation;
- reducing portfolio local or operational publisher coverage below its minimum;
- making one national publisher appear on seven target pages;
- removing an itinerary or practical related route;
- adding a third comparison link;
- removing one related-route group or causing portfolio group/type imbalance;
- duplicating a source-trail or update-log module;
- changing content outside the approved module boundary;
- using an unpublished or redirecting internal route;
- adding a frontend fetch or server-render-time external source request;
- injecting markup, event attributes, unsafe URL schemes, invalid UTF-8, unknown bundle keys, or an oversized bundle;
- removing required `esc_html()`, `esc_attr()`, or `esc_url()` output-boundary escaping;
- rendering a staged bundle, new source trail, or update log before its exact path is active;
- activating a stage path before its structured metadata and modules pass stored-state verification.

### Public verification

The public verifier checks the exact active stage inventory and its controls: eight pilots before rollout, 11 pilots plus the six deferred comparison controls during Stage 1, and all 17 pilots after Stage 2. The four permanent non-pilot controls are checked in every stage. Each active pilot must return HTTP 200 with exactly one H1, the guide shell, TOC or jump navigation, trust metadata, content-versioned assets, valid fragments, related routes, and no fatal text. Each deferred comparison control must remain on the default template until activated.

For the nine comparison targets it also checks decision-frame presence, archetype and locality labels, traveler-lens output, visible source count and evidence labels, grouped related routes, source-trail presence, update-log presence, table-wrapper focusability, absence of runtime source-fetch markers, and the HTML growth budget.

### Browser QA

Run all nine new comparison pages at 1280x900 and 390x844.

Every run verifies:

- one visible cinematic-hero H1;
- contained sticky desktop TOC and mobile jump navigation;
- trust rail source/review metadata;
- a decision frame within the section, row, item, character, and no-table limits;
- no horizontal document overflow;
- keyboard focus and horizontal scrolling for every comparison table;
- grouped related-route, grouped source, and update modules with semantic headings and descriptive links;
- no console or page errors.

Run reduced-motion stress tests on:

- `compare/old-quarter-vs-french-quarter-vs-west-lake` for the longest three-way title;
- `compare/ninh-binh-day-trip-vs-overnight` for dense tables and headings;
- `compare/north-central-south-vietnam` for the widest locality and decision-axis set.

## Acceptance Criteria

The rollout is complete only when:

- all nine target pages pass the compatibility gate before writes;
- the reviewed source registry and manifest satisfy every source, freshness, claim-coverage, archetype, traveler-lens, route, and locality rule;
- generated legacy source metadata exactly matches the reviewed registry resolution;
- normalized `vg_eeat_source_assignments` exactly matches the reviewed page-source mappings and renderer contract;
- each active path has one valid versioned comparison bundle, while inactive staged paths preserve their baseline public output;
- all controlled updates are backed up and applied idempotently;
- all ten comparison guides use the Guide Experience while `/compare/` remains default;
- the three canaries pass both verification rounds before the remaining six paths activate;
- each stage applies and verifies data before atomic routing activation, with stage-aware fingerprints passing before and after activation;
- post-verification legacy-field sync is idempotent and leaves structured module output byte-equivalent;
- the local, mutation, live, and public verifier suites pass;
- all 18 desktop/mobile browser runs pass;
- the three reduced-motion stress runs pass;
- the HTML, CSS, request-count, CLS, LCP, zoom, keyboard, and no-runtime-fetch budgets pass;
- the strict bundle-schema, 64 KB storage, single-read rendering, SSRF, redirect, escaping, and cache-version contracts pass;
- production source metadata and related routes match the reviewed manifest;
- no out-of-scope content, SEO, schema, media, menu, or site-setting change is detected;
- deployment helpers are removed and backups remain available.

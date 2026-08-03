# VietnamGuide Comparison Diversity Rollout Design

Date: 2026-08-03 (Asia/Saigon)

Status: Revision 3 written and independently reviewed; pending user approval before implementation planning.

## Goal

Roll out the existing Stitch + Editorial Spine Guide Experience to every published comparison guide and add an auditable Evidence + Decision System that balances evidence for every compared side, explains qualitative decision rules and trade-offs, detects source drift, and remains resilient, measurable, and reversible from editorial review through WordPress rendering and production rollout.

## Background

The comparison hub currently has ten published comparison guides. `compare/ha-long-bay-vs-lan-ha-bay` already uses the Guide Experience. The other nine pages are published and compatible with the shared guide parser, but still use the default page template and render two H1 elements.

Public and REST audits on 2026-08-03 confirmed that every target returns HTTP 200, contains one H1 in its stored page content, has a comparison hero, has no fatal text, and contains between 8 and 17 H2 headings and between 5 and 11 tables.

This rollout is intentionally more than an allowlist expansion. It includes a guarded editorial metadata update, a reusable source registry, a claim-to-decision provenance graph, comparison-specific decision framing, source-drift review, and narrowly scoped module insertion so the pages expose a visible, varied evidence trail and lead readers into different guide types and regions without duplicating the articles.

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
- Archiving full third-party source bodies, bypassing publisher access controls, or treating ETag and Last-Modified values as proof that meaning is unchanged.
- Replacing the site's general editorial workflow, adding public reviewer profiles, or extending the evidence graph to non-comparison content in this rollout.

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
- stable claim IDs, option or locality scope, primary decision, field note, and qualitative decision rules;
- reviewed date, update summary, and evidence-moat lines;
- six to ten stable source IDs plus claim, option, axis, and outcome mappings with evidence labels;
- six to eight related-route records assigned to one of three route groups;
- required module-insertion flags.

Create a separate versioned source registry keyed by stable source ID. A registry record contains:

- stable publisher ID, controlling-organization ID, publisher name, and canonical publisher domain;
- source class, responsible locality, and source language;
- canonical URL, expected media type, expected document title or title pattern, and an optional evidence locator such as section heading or document date;
- freshness tier, checked date, and the claim groups the source can support.

Create a separate versioned organization registry keyed by stable controlling-organization ID. It contains the canonical organization name, every approved publisher ID, known aliases or sub-brands, and an evidence note for ownership or control. Page manifests cannot create organization IDs. Adding, merging, or splitting an organization record requires its own reviewed registry change and cannot be bundled silently into a page-source edit.

Source IDs, publisher IDs, and controlling-organization IDs remain stable when labels or checked dates change. A canonical URL may not appear under two source IDs. Publisher identity is bidirectional: one publisher ID resolves to exactly one canonical domain, and one canonical domain resolves to exactly one publisher ID. Every publisher references exactly one existing organization record. Multiple publishers controlled by the same organization share one organization ID and count once for independence rules. Page manifests reference registry IDs instead of copying source records. The deployment artifact records source and organization-registry versions. The updater stores a normalized resolved assignment payload in `vg_eeat_source_assignments` and deterministically generates the legacy `vg_eeat_sources_checked` text from the same payload so existing trust modules remain compatible.

Create a versioned editorial identity registry keyed by stable identity ID and mapped to an active WordPress user ID, display name, and allowed writer or reviewer role. Approval artifacts are generated server-side against an exact manifest hash and contain identity ID, role, UTC timestamp, affected change IDs, signing-key ID, and an HMAC made with a dedicated approval-signing secret stored outside WordPress salts and outside the repository. Signing keys are context-specific, versioned by key ID, and retained for verification for at least as long as the corresponding deployment ledgers and backups. Secret material never enters the repository, artifact, or ledger.

The manifest, source registry, organization registry, identity registry, approval artifacts, and resolved comparison bundle use canonical key ordering and deterministic serialization. Their SHA-256 hashes are recorded in validation output, deployment evidence, and backups so the reviewed inputs, approved state, applied state, and rendered state can be compared without relying on timestamps alone.

The manifests and registries are version-controlled data only. Approval artifacts are server-generated deployment evidence bound to those exact data hashes. A separate guarded apply script validates and installs them. This keeps editorial choices and attestations reviewable without mixing them into deployment logic.

### 4. Guarded WordPress updater

The updater must support machine-readable validation, dry-run, apply, compatibility-sync, and rollback modes and must be idempotent. Every run uses a unique run ID and acquires an expiring deployment lock before the first production read that can lead to a write. A second updater run must fail closed while the lock is held.

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
- No single domain contributes more than 40 percent of a page's source records. The check uses `domain_sources * 100 <= page_sources * 40` with integer cross-multiplication and no rounding exception.
- URLs must use HTTP or HTTPS, resolve to the expected publisher, and must not be search-result, tracking-only, shortened, or affiliate URLs.
- URL verification rejects credentials, nonstandard ports, loopback, private, link-local, multicast, reserved, and cloud-metadata targets. DNS is resolved before each request and again after every redirect; every resolved address must be public. The HTTP connection is pinned to an approved address while preserving the original Host header and TLS SNI, and the actual connected peer address must match the approved set for every hop.
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

The validator evaluates registry records, publisher IDs, controlling-organization IDs, and page-source assignments. Reusing an appropriate source across two related pages is allowed, but duplication, mirror domains, sub-brands, and multiple publishers controlled by one organization cannot satisfy independence or local-first-party minimums more than once.

## Evidence Balance And Independence Contract

Evidence must cover the choice symmetrically enough to support a fair comparison.

- Each compared option or locality must have current primary evidence for access and transport plus timing and duration. Experience fit must have current evidence from at least two controlling organizations, including at least one local, operational, or independent source; it is not treated as an official fact merely because a tourism body promotes it.
- The counted unit is one unique option-source edge: `(page path, option ID, source ID)`. Mapping the same source to multiple claims or axes for one option still counts once. A source mapped to two options creates one edge for each option only when its claim mapping explicitly applies to both; an `all_options` or shared-context mapping is excluded from the balance denominator.
- The denominator is the total unique option-source edges across the compared options after excluding shared-context edges. A two-way option passes when `option_edges * 100 >= total_edges * 40`; a three-way or macro-region option passes when `option_edges * 100 >= total_edges * 25`. Integer cross-multiplication is used, so no rounding rule can change the result.
- Nationwide or shared-condition sources do not count toward a side minimum unless their mapped claim explicitly applies to that side.
- Each decisive axis must contain evidence for every option it compares. An axis with evidence for only one side fails preflight rather than silently favoring the better-documented side.
- A negative constraint or avoidance recommendation requires either one directly responsible current primary source or two current corroborating sources from different controlling organizations.
- A settled winner recommendation requires evidence from at least two controlling organizations across its decisive claim groups. A live-check outcome may use one current primary organization when it is the sole responsible authority.
- The page manifest records an explicit `not_applicable` reason when a claim group genuinely does not apply to one option; missing data cannot be labeled not applicable.

The verifier emits a page-by-page coverage matrix of option by claim group, axis, source ID, organization ID, freshness state, and evidence label. This matrix is deployment evidence but is not rendered as another public comparison table.

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

Each claim, decision axis, traveler-lens outcome, and recommendation has a stable ID inside the page manifest. Directed mappings form a validated provenance graph:

`source -> claim -> decision axis -> traveler lens or primary outcome`

Every public outcome must have at least one complete path through this graph. Orphan sources are allowed only as visible background context and cannot count toward decisive coverage. Orphan claims, axes, or outcomes fail validation. Cycles are invalid. A source refresh or removal must produce an impact report listing every downstream axis and outcome that requires editorial re-review; it never rewrites a recommendation automatically.

## Freshness Contract

Every registry record uses one of three deterministic freshness tiers:

- `live`: prices, schedules, ticketing, closures, entry rules, and operating conditions; expires 30 days after its checked date;
- `current`: policy, transport patterns, seasonal guidance, weather or marine guidance, and destination operations; expires 180 days after its checked date;
- `stable`: geography, heritage status, durable cultural context, and long-lived infrastructure facts; expires 730 days after its checked date.

Freshness is evaluated during static build and deployment verification only. Public requests never fetch or health-check external sources.

An expired source fails preflight when it supports a settled decisive recommendation. It may remain only when the mapped claim is explicitly marked `Live check required`, the visible module preserves the checked date and direct URL, and another current source covers any stable part of the recommendation. Broken, redirected-to-unrelated, or publisher-mismatched URLs always fail preflight regardless of freshness label.

## Source Drift And Editorial Governance

URL health alone does not prove that a source still supports the reviewed claim. Deployment verification compares final publisher, media type, normalized title or title pattern, document date when declared, and evidence locator when available. A material mismatch is `source drift` and blocks a settled recommendation until reviewed.

ETag and Last-Modified values are recorded when supplied but are advisory only. Their change triggers reinspection; their absence or stability never proves semantic equivalence. The system stores provenance metadata and hashes, not full copyrighted source snapshots.

Editorial review follows a four-eyes rule for decision-changing updates:

- every apply requires one server-verified author attestation for the exact manifest hash, change IDs, and change reason;
- a source refresh that leaves outcomes unchanged may use one authorized reviewer artifact, but the impact report and checked dates are still recorded;
- changing a primary outcome, hard constraint, negative recommendation, archetype, or decisive axis requires both an author attestation and a reviewer approval for the same manifest hash, with distinct identity IDs and WordPress user IDs;
- an artifact with an unknown or inactive identity, invalid HMAC, mismatched manifest hash, role mismatch, duplicate identity, or altered change scope fails preflight;
- update-log entries use controlled change reasons: `source_refresh`, `operational_change`, `decision_change`, `route_change`, or `correction`;
- every decision-changing entry names the affected claim and outcome IDs without exposing internal reviewer notes publicly.

The deployment ledger stores approval-artifact hashes, identity IDs, roles, WordPress user IDs, timestamps, manifest hash, and verification result. The append-only ledger plus server HMAC is the immutable approval evidence; the authentication secret and artifact payload are not exposed publicly.

No source expiry, drift event, registry edit, or automated validation result can change public recommendation text without explicit reviewed manifest changes.

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

## Qualitative Decision-Graph Contract

Recommendations are explained by explicit qualitative rules, never hidden weights, aggregate scores, or a runtime inference engine. Each page defines a rule catalog containing:

1. Zero to three hard constraints: closures, unsafe conditions, transfer limits, accessibility barriers, or trip-length limits that can remove an option for a stated traveler context.
2. Two to five preference branches: evidence-backed trade-offs such as atmosphere versus convenience, depth versus pace, or resort ease versus city flexibility.
3. One or two tie-breakers used only when hard constraints and preference branches do not resolve the choice.
4. An optional `combine_or_sequence` outcome when using both options is genuinely practical and supported by route evidence.

Every rule has a stable rule ID, one rule kind, controlled traveler-context tags, option scope, and stable claim and axis references. Each traveler lens declares controlled context tags and an explicit ordered decision path of rule IDs ending in one outcome ID. The stored path is the reviewed editorial decision; PHP renders it but does not calculate a new recommendation.

A decision path is valid only when:

- it contains at most one applicable hard constraint and places it first;
- it contains exactly one decisive preference branch unless the hard constraint already resolves the outcome;
- it contains at most one tie-breaker, after the preference branch;
- every rule shares at least one declared context tag with the traveler lens, references current evidence, and scopes to the rendered option or sequence;
- the terminal outcome names the option, `no_clear_winner`, or `combine_or_sequence`, followed by one material trade-off or reversal condition;
- it contains no repeated, skipped, or unreachable rule.

Rule-array order is authoritative; numeric priorities and weights are forbidden. The validator replays only schema order and references, so the same manifest always produces the same path. No lens may claim an unconditional universal winner.

The public decision frame renders only the lens's reviewed path: applicable constraint, decisive preference, optional tie-breaker, outcome, and trade-off. Unused catalog rules are not rendered. It does not expose internal IDs, source-count math, or pseudo-algorithmic scoring.

## Server-Rendered Comparison Experience

The existing Stitch + Editorial Spine guide shell remains the visual foundation. Comparison pages add four scoped enhancements:

1. A decision frame after the trust rail and before the long-form body. It shows the archetype, compared localities, primary decision, selected decision axes, and concise traveler-lens outcomes.
2. A source trail grouped by source class. Each row exposes publisher, locality, language when useful, checked date, claim coverage, and the `Primary`, `Corroborating`, or `Live check required` label.
3. Related routes grouped as `Deepen place`, `Build route`, and `Check practical` so the next action is explicit instead of a flat recommendation grid.
4. An update log that explains which decision inputs or evidence changed, rather than merely repeating a date.

All modules are rendered by PHP from validated stored metadata. For an active comparison path, the renderer reads the versioned comparison bundle as the authoritative snapshot and rejects a bundle whose registry version does not match the deployed artifact. Each module exposes non-sensitive version and render-hash data attributes for deterministic public verification. It reuses existing typography, color, spacing, radius, focus, and motion tokens. It adds no JavaScript dependency, no client-side data fetch, no new font or image request, and no runtime external-source request.

The decision frame supplements the existing article; it must not copy full comparison tables or long prose. Semantic H2 and H3 headings, lists, links, and table wrappers remain keyboard accessible. Motion is optional enhancement only, and reduced-motion users receive the same information without transition-dependent disclosure.

The decision frame is bounded to one section heading, one primary-decision statement of at most 240 characters, three to six axis rows, and three to five traveler-lens outcomes. Axis labels are at most 48 characters, axis explanations at most 180 characters, and lens outcomes at most 220 characters. The complete frame contains at most 1,800 visible characters and no table. Manifest text is authored specifically for the frame rather than extracted from article tables.

## Runtime Data And Output Safety

- The comparison bundle uses a strict versioned schema with allowlisted keys, scalar types, enumerations, item counts, and length limits. Unknown keys, raw HTML, invalid UTF-8, control characters, and oversized payloads fail validation.
- The serialized bundle is capped at 64 KB per page and is read in one metadata operation; rendering may not issue per-source or per-route database queries.
- Text is escaped with `esc_html()`, attribute values with `esc_attr()`, and external or internal URLs with `esc_url()` at the final output boundary. Validation never substitutes for context-specific escaping.
- Shortcode attributes are not accepted for these modules. Renderer output cannot execute manifest-provided HTML, scripts, styles, event handlers, shortcodes, or block markup.
- Cache keys include the exact path, resolved bundle SHA-256 hash, bundle schema version, source-registry version, and activation artifact version. Stage activation and rollback purge only the affected paths plus shared asset caches. Any content-only bundle change necessarily changes cache identity even when schema and activation versions remain unchanged.
- During a version transition the renderer supports the current bundle schema and the immediately previous schema only. Unknown, malformed, or incompatible versions fail safely to the unchanged baseline module behavior, emit a bounded server log event, and never produce a public fatal error or partial decision frame.
- Module failures are isolated: a rejected decision frame cannot suppress the article body, hero, TOC, or unchanged baseline modules. Public output never includes stack traces, local paths, run IDs, source-check diagnostics, or reviewer-only notes.
- Vietnamese source titles retain their original text and use `lang="vi"`; explanatory labels use the page language. Color is never the sole distinction between evidence labels, and external links do not open a new window by default.

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

When the decision graph exposes a `combine_or_sequence` outcome, at least one `Build route` record must make that combined sequence practical and must name the transfer or itinerary decision it resolves. A sequence outcome without a supporting internal route fails validation.

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

After cache purge, one complete static, live, public, desktop, mobile, keyboard, reduced-motion, source-freshness, and content-diff verification round must pass for the three canary pages.

Stage 1 then enters a minimum ten-minute canary observation window. During that window the verifier performs at least three spaced uncached request rounds and checks PHP, web-server, and WordPress error logs for new path-correlated warnings or fatals. Unchanged low traffic does not waive the active request rounds. Stage 2 may prepare and activate only after this window closes cleanly.

After the activated stage passes its required public checks, the updater mirrors the authoritative bundle into the approved legacy metadata fields for compatibility and verifies that rendering remains byte-equivalent for the structured modules. This compatibility sync is idempotent and is not used as the renderer's source of truth.

Each stage has its own exact path list and complete pre-write backup. Before activation, a failed stage restores its page data while its paths remain disabled. After activation, a failed public check first disables the exact stage paths, purges caches, and then restores that stage's page data. A failed Stage 1 restores only the three canary pages and leaves the other six disabled. A failed Stage 2 restores only pages changed in Stage 2 while preserving the already verified canaries. Shared theme or MU-plugin artifacts remain independently reversible through their deployment backup.

Every run writes an append-only machine-readable deployment ledger containing run ID, operator, UTC timestamps, stage, exact paths, manifest/registry/bundle/artifact hashes, backup location and checksum, lock lifecycle, validation results, activation event, cache purge/warm results, compatibility sync, and rollback status. Secrets, authentication tokens, source response bodies, and reviewer-only notes are never recorded.

WP-CLI or server-local execution is preferred. If a temporary HTTP deployment helper is unavoidable, it accepts only POST, requires a single-use signed token with a maximum ten-minute lifetime, validates the exact run ID and stage path set, rate-limits failures, performs no action on GET, and is removed before the deployment lock is released.

## Performance And Accessibility Budgets

- No new frontend request, JavaScript bundle, font, image, or runtime external-source request is allowed.
- Additional server-rendered HTML is capped at 20 KB uncompressed UTF-8 and 180 DOM nodes per comparison page relative to its pre-rollout public HTML.
- Scoped CSS growth is capped at 6 KB uncompressed, with no new render-blocking asset and no selector outside the comparison experience scope.
- After WordPress metadata cache warm-up, comparison rendering adds zero SQL queries. A cold render may add at most one metadata query for the bundle and no per-source or per-route queries.
- Across 100 local render iterations using the reviewed largest bundle, the comparison-module PHP p95 must remain at or below 8 ms on the deployment host or an equivalent documented environment.
- The hero remains the LCP candidate; the new modules reserve their layout server-side and may not introduce a module-attributable layout shift. Mobile Lighthouse CLS must remain at or below 0.10.
- Under the same throttling profile and three-run median, mobile LCP on each canary may not regress by more than 10 percent from its captured pre-rollout baseline.
- Decision and route groups use semantic headings and lists; external source links have descriptive accessible names; all table wrappers and interactive controls remain keyboard reachable with visible focus.
- At 200 percent zoom and 320 CSS pixels wide, no module causes document-level horizontal overflow or hides decision information.
- Source URL resolution and freshness checks run only in static, preflight, and deployment verification, never during page rendering.
- After every activation or rollback purge, the deployment process warms each affected public path and its primary content-versioned asset once before browser QA begins; cache warming may not bypass normal public routing or authentication rules.

## Data Flow

1. Public and REST audits identify the exact target page, baseline HTML metrics, and content shape.
2. The typed source registry defines reusable evidence records and freshness policy.
3. The reviewed page manifest selects source IDs, builds the claim-to-outcome provenance graph, defines qualitative decision rules and traveler lenses, groups related routes, and declares module requirements.
4. Static validation resolves the registry, generates compatibility text, emits evidence-balance and downstream-impact matrices, and checks page-level plus portfolio-level contracts without WordPress writes.
5. The guarded updater acquires the deployment lock, starts the ledger, validates all production preconditions, and writes an exact checksummed backup for the current stage.
6. Shared renderer and verifier support is deployed from a deterministic reviewed artifact while new stage paths remain disabled.
7. The versioned pending comparison bundle and approved shortcode insertions are applied idempotently to the stage paths and verified in stored state without changing visible baseline output.
8. The exact stage routing gate is activated atomically, caches are purged and warmed, and WordPress runtime, public HTML, evidence labels, links, performance budgets, accessibility behavior, browser layouts, and error logs are verified through the required stage rounds.
9. After public verification and any canary observation window, bundle data is mirrored into approved legacy fields for compatibility, byte-equivalent structured rendering is confirmed, the helper is removed, the ledger is finalized, and the deployment lock is released.

## Failure And Rollback Behavior

- Any preflight failure aborts before the first write.
- A live deployment lock or an unclosed prior ledger blocks a new apply until an operator performs an explicit read-only recovery audit. Expired locks are never silently stolen.
- A content-fingerprint mismatch aborts rather than merging with unknown editorial changes.
- A source, freshness, claim-coverage, decision-axis, traveler-lens, or related-route validation failure names the page and failing stable record ID.
- A missing, malformed, or registry-version-mismatched comparison bundle blocks activation and renders no enhanced module.
- A partial apply triggers restoration of every page changed in that rollout stage from the exact backup.
- Rollback restores post content, all in-scope meta values, modified timestamps where supported, and cache state through a fresh purge.
- Theme deployment remains independently reversible through the existing guarded artifact backup.
- Rollback records its own start/end events and restored hashes in the same ledger, then verifies baseline render hashes before releasing the lock.
- Deployment helpers and transient status data are removed after successful verification or rollback. Evidence ledgers and checksummed backups remain according to the existing operations retention policy.

## Verification Design

### Static and mutation verification

Add contracts for:

- the exact ordered target-state 17-path pilot inventory and exact 8/11/17 stage inventories;
- the exact non-pilot control set;
- all nine manifest entries and post IDs;
- stable unique source, publisher, controlling-organization, and editorial-identity IDs; organization alias ownership; bidirectional canonical URL/domain identity; registry versions; normalized assignment storage; deterministic hashes; and legacy compatibility-text generation;
- six-to-ten sources per page, required source-class coverage, valid source class, locality, language, and evidence label;
- freshness-tier expiry, checked dates, URL publisher matching, and the prohibition on runtime source fetching;
- SSRF-safe URL resolution, approved-IP socket pinning, connected-peer verification, Host/SNI preservation, redirect, timeout, port, address-range, and response-size limits;
- mandatory claim groups and source coverage for every decision axis and decisive recommendation;
- exact unique option-source edge counting, shared-context exclusion, integer 40/25 balance formulas, organization-level independence, negative-claim corroboration, and explicit not-applicable reasons;
- complete acyclic source-to-claim-to-axis-to-outcome provenance paths and deterministic downstream impact reports;
- source-drift checks for publisher, media type, title pattern, document date, and evidence locator plus four-eyes decision-change review;
- page-level domain concentration and portfolio-level publisher, local-first-party, language, assignment-share, and geographic diversity;
- valid archetype axes and three-to-five traveler lenses per page plus portfolio lens diversity;
- valid context tags, ordered hard-constraint/preference/tie-breaker paths, terminal outcomes, optional sequences, reversal conditions, and material trade-offs without weights, scores, or runtime inference;
- six-to-eight related routes per page, three visible route groups, and guide-type/geography diversity;
- published, non-redirecting internal route targets;
- the four source-trail insertions and nine update-log insertions;
- module order and idempotence;
- server-rendered decision-frame placement and bounded output;
- strict bundle schema, size limits, single-read rendering, bundle-hash cache identity, cache-version keys, and context-specific output escaping;
- current/previous schema compatibility, unknown-version baseline fallback, module failure isolation, language semantics, and non-sensitive render hashes;
- exact three-page canary and six-page Stage 2 path inventories;
- versioned pending-bundle validation, inactive-path no-op behavior, stage-aware pre/post fingerprints, and data-before-routing atomic activation;
- unchanged prose outside approved module boundaries;
- stage-exact backup completeness and rollback behavior;
- deployment-lock exclusion, append-only ledger completeness, helper authorization and expiry, cache purge/warm evidence, and canary observation rounds;
- dedicated-key server-HMAC approval validity, signing-key ID and retention, active WordPress identity mapping, exact manifest binding, role authorization, and distinct author/reviewer enforcement for decision changes;
- HTML, DOM-node, CSS-scope, SQL-query, PHP-render-time, CLS, and LCP budgets.

Permanent mutations must include at least:

- replacing a long comparison route with an unrelated path;
- accidentally enabling the `/compare/` hub;
- removing one locality group;
- reducing a page below four source classes;
- exceeding the source-domain concentration limit;
- expiring a decisive source without a valid live-check fallback;
- removing claim coverage from a decisive recommendation;
- reducing one option below its 40 or 25 percent evidence floor;
- leaving one side of a decisive axis without evidence;
- supporting a negative recommendation with only one non-primary organization;
- introducing an orphan or cycle in the provenance graph;
- changing the expected media type, title pattern, document date, or evidence locator without triggering source-drift review;
- duplicating a source ID or assigning one canonical URL to two IDs;
- assigning one publisher ID to conflicting canonical domains;
- assigning one canonical domain to two publisher IDs;
- splitting one controlling organization across publisher aliases to inflate independence;
- creating an organization ID in a page manifest, omitting an approved alias, or referencing a publisher outside its organization record;
- counting one source multiple times for multiple claims on the same option or including `all_options` edges in the 40/25 denominator;
- using a source URL that resolves or redirects to loopback, private, link-local, reserved, or cloud-metadata space;
- allowing the HTTP client to re-resolve an approved host or connect to a peer address outside the pinned approved set;
- using an invalid source class, locality, language, freshness tier, or evidence label;
- removing a mandatory decision axis or reducing a page below three traveler lenses;
- collapsing all traveler lenses to the same recommendation;
- removing the material trade-off or reversal condition from a traveler-lens outcome;
- placing a preference before a hard constraint or adding a hidden numeric weight;
- using a rule whose context tags do not match the lens, repeating a rule, omitting a terminal outcome, or rendering an unused catalog rule;
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
- causing an unknown bundle version or rejected module to suppress the article body or emit a public fatal;
- removing the resolved bundle hash, bundle version, registry version, or activation version from the cache key;
- changing bundle content without changing cache identity;
- adding a per-source or per-route runtime query or exceeding the render-time budget;
- adding an unscoped CSS selector or exceeding the HTML, DOM-node, or CSS budget;
- rendering a staged bundle, new source trail, or update log before its exact path is active;
- activating a stage path before its structured metadata and modules pass stored-state verification;
- allowing concurrent applies, silently stealing an expired lock, or finalizing a ledger without required hashes;
- allowing a temporary helper write through GET, an expired or reused token, or an out-of-stage path;
- accepting an approval signed with WordPress authentication salts, an unknown or unavailable signing-key ID, invalid HMAC, inactive identity, mismatched manifest hash, unauthorized role, or identical author and reviewer.

### Public verification

The public verifier checks the exact active stage inventory and its controls: eight pilots before rollout, 11 pilots plus the six deferred comparison controls during Stage 1, and all 17 pilots after Stage 2. The four permanent non-pilot controls are checked in every stage. Each active pilot must return HTTP 200 with exactly one H1, the guide shell, TOC or jump navigation, trust metadata, content-versioned assets, valid fragments, related routes, and no fatal text. Each deferred comparison control must remain on the default template until activated.

For the nine comparison targets it also checks decision-frame presence, archetype and locality labels, traveler-lens outcomes and trade-offs, visible source count and evidence labels, grouped related routes, source-trail presence, update-log reason, non-sensitive bundle/render hashes, table-wrapper focusability, absence of runtime source-fetch markers, schema-fallback safety, and the HTML/DOM growth budgets.

### Browser QA

Run all nine new comparison pages at 1280x900 and 390x844.

Run the three canary pages additionally at 768x1024 for tablet breakpoint stress.

Every run verifies:

- one visible cinematic-hero H1;
- contained sticky desktop TOC and mobile jump navigation;
- trust rail source/review metadata;
- a decision frame within the section, row, item, character, and no-table limits;
- no horizontal document overflow;
- keyboard focus and horizontal scrolling for every comparison table;
- grouped related-route, grouped source, and update modules with semantic headings and descriptive links;
- visible text equivalents for evidence states, correct `lang` semantics for Vietnamese source titles, and no forced new-window behavior;
- no console or page errors.

Run reduced-motion stress tests on:

- `compare/old-quarter-vs-french-quarter-vs-west-lake` for the longest three-way title;
- `compare/ninh-binh-day-trip-vs-overnight` for dense tables and headings;
- `compare/north-central-south-vietnam` for the widest locality and decision-axis set.

Run forced-colors checks on the same three canaries to confirm that evidence labels, focus states, links, and decision outcomes remain distinguishable without background color or motion.

## Acceptance Criteria

The rollout is complete only when:

- all nine target pages pass the compatibility gate before writes;
- the reviewed source registry and manifest satisfy every source, freshness, claim-coverage, archetype, traveler-lens, route, and locality rule;
- every option meets the exact unique-edge evidence-balance formula, every decisive axis is symmetric, and the reviewed organization registry prevents publisher aliases from inflating independence;
- every public outcome has a complete acyclic provenance path, a schema-valid deterministic rule path, and a material trade-off or reversal condition;
- source-drift checks and downstream impact reports pass, with valid dedicated-key server-HMAC approval artifacts bound to the exact manifest and distinct author/reviewer identities for every decision-changing update;
- generated legacy source metadata exactly matches the reviewed registry resolution;
- normalized `vg_eeat_source_assignments` exactly matches the reviewed page-source mappings and renderer contract;
- each active path has one valid versioned comparison bundle, while inactive staged paths preserve their baseline public output;
- manifest, registry, bundle, artifact, backup, and rendered module hashes match the finalized deployment ledger;
- all controlled updates are backed up and applied idempotently;
- all ten comparison guides use the Guide Experience while `/compare/` remains default;
- the three canaries pass the complete activation verification round before the remaining six paths prepare for activation;
- the three canaries pass the ten-minute observation window, three spaced uncached request rounds, and correlated error-log checks;
- each stage applies and verifies data before atomic routing activation, with stage-aware fingerprints passing before and after activation;
- post-verification legacy-field sync is idempotent and leaves structured module output byte-equivalent;
- the local, mutation, live, and public verifier suites pass;
- all 18 desktop/mobile browser runs pass;
- all three tablet breakpoint and three forced-colors canary runs pass;
- the three reduced-motion stress runs pass;
- the HTML, CSS, request-count, CLS, LCP, zoom, keyboard, and no-runtime-fetch budgets pass;
- the 20 KB HTML, 180-node, 6 KB scoped-CSS, warm zero-query, cold one-query, and 8 ms PHP p95 budgets pass;
- the strict bundle-schema, 64 KB storage, schema fallback, module isolation, single-read rendering, pinned-peer SSRF, redirect, escaping, language, bundle-hash cache identity, and cache-version contracts pass;
- deployment lock, helper authorization, append-only ledger, cache purge/warm, compatibility sync, and rollback evidence all close cleanly;
- production source metadata and related routes match the reviewed manifest;
- no out-of-scope content, SEO, schema, media, menu, or site-setting change is detected;
- deployment helpers are removed and backups remain available.

# VietnamGuide Comparison Diversity Rollout Design

Date: 2026-08-03 (Asia/Saigon)

Status: Approved design direction; implementation plan pending written-spec review.

## Goal

Roll out the existing Stitch + Editorial Spine Guide Experience to every published comparison guide while making the comparison portfolio materially more diverse in evidence sources, decision format, related content type, and Vietnamese locality coverage.

## Background

The comparison hub currently has ten published comparison guides. `compare/ha-long-bay-vs-lan-ha-bay` already uses the Guide Experience. The other nine pages are published and compatible with the shared guide parser, but still use the default page template and render two H1 elements.

Public and REST audits on 2026-08-03 confirmed that every target returns HTTP 200, contains one H1 in its stored page content, has a comparison hero, has no fatal text, and contains between 8 and 17 H2 headings and between 5 and 11 tables.

This rollout is intentionally more than an allowlist expansion. It includes a guarded editorial metadata update and narrowly scoped module insertion so the pages expose a visible, varied evidence trail and lead readers into different guide types and regions.

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
- `vg_eeat_field_note`
- `vg_eeat_evidence_moat`
- `vg_eeat_related_routes`

Stored post content may change only to add a missing source-trail shortcode and a missing update-log shortcode in the approved end-of-article module sequence. No prose, table, heading, hero, image, Rank Math metadata, schema, slug, parent, menu order, author, or publish status may be rewritten by this rollout.

## Out of Scope

- Rewriting the comparison articles.
- Adding new comparison URLs.
- Changing the visual language, CSS tokens, JavaScript behavior, or shared guide layout unless verification reveals a separate shared defect that receives its own review.
- Enabling destination or practical-guide content pages beyond existing pilots.
- Changing the `/compare/` hub design.
- Changing Rank Math metadata, structured data, media, image credits, or affiliate configuration.
- Treating travel blogs, affiliate roundups, AI summaries, search result pages, or unattributed listicles as primary evidence.

## Rollout Architecture

### 1. Explicit routing gate

Append the nine exact paths to `vg_guide_pilot_paths()`. Keep strict, ordered path matching and the existing type classifier. Do not introduce prefix-based or automatic comparison-hub enablement.

The resulting pilot inventory contains 17 exact paths: the existing destination, five itinerary, one comparison, and one practical pilots plus the nine comparison targets.

### 2. Exact verifier inventories

Update the local, live, mutation, and public verifier inventories to the same ordered 17-path set. Preserve exact case-sensitive comparison.

The non-pilot public control set becomes:

- `compare`
- `destinations/hanoi-travel-guide`
- `plan/sim-esim-vietnam`
- `plan/transport-within-vietnam`

This proves that the comparison hub itself and representative destination and practical pages remain on their default templates.

### 3. Versioned editorial manifest

Create one local, version-controlled data manifest keyed by exact page path. Each entry contains:

- expected post ID and current content fingerprint;
- comparison archetype and locality tags;
- primary decision and field note;
- reviewed date, update summary, and evidence-moat lines;
- six to ten checked source records;
- six to eight related-route records;
- required module-insertion flags.

The manifest is data only. A separate guarded apply script validates and installs it. This keeps editorial choices reviewable without mixing them into deployment logic.

### 4. Guarded WordPress updater

The updater must support validation and apply modes and must be idempotent.

Before any write it validates all nine pages together:

- exact path, post ID, page type, published status, and parent;
- expected pre-update content fingerprint;
- required hero marker and exactly one stored-content H1;
- no existing duplicate source-trail or update-log module;
- every external source URL and every internal related route passes its contract;
- every metadata entry satisfies the diversity rules.

It then records a complete backup of post content and every in-scope meta value. If any write or post-write check fails, it restores every page changed during that run. Cache purge occurs only after all nine pages pass post-write verification.

## Source Diversity Contract

Each page must have six to ten unique checked-source records. A source record includes a publisher label, subject, canonical URL, checked date, and a short statement of what the source can establish when that is not obvious.

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
- Vietnamese-language local sources are allowed and encouraged when they are the responsible first-party source.
- A checked date is mandatory and is preserved in the visible source trail.

## Decision-Format Diversity Contract

The pages must not collapse into one repeated two-column template. Existing article content remains, but the editorial metadata and verification classify and preserve six distinct comparison formats:

- Competing day trips: Cu Chi versus Mekong Delta.
- City or heritage base choice: Da Nang versus Hoi An; Hoi An versus Hue.
- Coast and island choice: Mui Ne versus Nha Trang; Phu Quoc versus Nha Trang.
- Time-allocation choice: Ninh Binh day trip versus overnight.
- Three-way neighborhood choice: Old Quarter versus French Quarter versus West Lake.
- Macro-region choice: Northern versus Central versus Southern Vietnam.
- Attraction and landscape choice: Trang An versus Tam Coc.

Each primary-decision statement must describe the actual choice mechanism for its archetype. Generic text such as "choose the best destination" is invalid.

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

The route note must explain the next decision. Repeating the target title or using generic text such as "read more" is invalid.

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

Insertion is marker-aware and idempotent. It must not duplicate an existing shortcode, pattern, source-trail class, related-routes class, or update-log class. Existing blocks are moved only when needed to establish the canonical module order; no unrelated block is reordered.

## Data Flow

1. Public and REST audits identify the exact target page and baseline content shape.
2. The reviewed manifest supplies editorial metadata, source records, related routes, and module requirements.
3. Static validation checks diversity, URLs, page mappings, module rules, and expected fingerprints without WordPress writes.
4. The guarded updater validates all production preconditions and writes a complete backup.
5. Metadata and approved modules are applied idempotently.
6. Theme routing and verifier changes are deployed from a deterministic reviewed artifact.
7. WordPress runtime, public HTML, links, accessibility behavior, and browser layouts are verified.
8. The helper is removed, caches are purged, and evidence is recorded.

## Failure And Rollback Behavior

- Any preflight failure aborts before the first write.
- A content-fingerprint mismatch aborts rather than merging with unknown editorial changes.
- A source or related-route validation failure names the page and failing record.
- A partial apply triggers restoration of every page changed in that run from the exact backup.
- Rollback restores post content, all in-scope meta values, modified timestamps where supported, and cache state through a fresh purge.
- Theme deployment remains independently reversible through the existing guarded artifact backup.
- Deployment helpers and transient status data are removed after successful verification or rollback.

## Verification Design

### Static and mutation verification

Add contracts for:

- the exact ordered 17-path pilot inventory;
- the exact non-pilot control set;
- all nine manifest entries and post IDs;
- six-to-ten sources per page and required source-class coverage;
- source domain concentration and checked dates;
- six-to-eight related routes per page and guide-type/geography diversity;
- published, non-redirecting internal route targets;
- the four source-trail insertions and nine update-log insertions;
- module order and idempotence;
- unchanged prose outside approved module boundaries;
- backup completeness and rollback behavior.

Permanent mutations must include at least:

- replacing a long comparison route with an unrelated path;
- accidentally enabling the `/compare/` hub;
- removing one locality group;
- reducing a page below four source classes;
- exceeding the source-domain concentration limit;
- removing an itinerary or practical related route;
- adding a third comparison link;
- duplicating a source-trail or update-log module;
- changing content outside the approved module boundary;
- using an unpublished or redirecting internal route.

### Public verification

The public verifier checks all 17 pilot pages and the four non-pilot controls. Each pilot must return HTTP 200 with exactly one H1, the guide shell, TOC or jump navigation, trust metadata, content-versioned assets, valid fragments, related routes, and no fatal text.

For the nine comparison targets it also checks visible source count, related-route module presence, source-trail presence, update-log presence, and table-wrapper focusability.

### Browser QA

Run all nine new comparison pages at 1280x900 and 390x844.

Every run verifies:

- one visible cinematic-hero H1;
- contained sticky desktop TOC and mobile jump navigation;
- trust rail source/review metadata;
- no horizontal document overflow;
- keyboard focus and horizontal scrolling for every comparison table;
- valid related-route, source, and update modules;
- no console or page errors.

Run reduced-motion stress tests on:

- `compare/old-quarter-vs-french-quarter-vs-west-lake` for the longest three-way title;
- `compare/ninh-binh-day-trip-vs-overnight` for dense tables and headings;
- `compare/phu-quoc-vs-nha-trang` for island/coast media and routing.

## Acceptance Criteria

The rollout is complete only when:

- all nine target pages pass the compatibility gate before writes;
- the reviewed manifest satisfies every source, format, route, and locality rule;
- all controlled updates are backed up and applied idempotently;
- all ten comparison guides use the Guide Experience while `/compare/` remains default;
- the local, mutation, live, and public verifier suites pass;
- all 18 desktop/mobile browser runs pass;
- the three reduced-motion stress runs pass;
- production source metadata and related routes match the reviewed manifest;
- no out-of-scope content, SEO, schema, media, menu, or site-setting change is detected;
- deployment helpers are removed and backups remain available.


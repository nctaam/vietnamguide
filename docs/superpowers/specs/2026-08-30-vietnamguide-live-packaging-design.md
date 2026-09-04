# VietnamGuide Live Packaging Design

Date: 2026-08-30 (Asia/Saigon)
Status: Proposed for review before implementation planning
Parent audits: live sitemap census (68 URLs), REST `content.rendered` dump, Guide Experience pilots
Does not implement: comparison-evidence HMAC system, unpublished `post` apply-scripts, new URLs, newsletter provider, font hosting, image localization

## 1. Decision

Package the already-published VietnamGuide.net corpus so the public site stops lying about its own inventory, identity, headings, and evidence.

This pass is a product-shell repair, not a content expansion.

Locked product choices:

- Keep the Stitch homepage in `front-page.php`. Do not resurrect page ID 5 as the visible homepage.
- Remap every homepage card to a published sitemap URL. Relabel cards that currently advertise unpublished pages. Do not silent-redirect a label to an unrelated URL.
- Do not insert EEAT shortcodes into `post_content`.
- Style existing Gutenberg source-trail blocks where they already exist. Coverage will stay uneven.
- Expand Guide Experience hub by hub: remaining comparisons, then destinations, then practical plan pages. Itinerary deep pages stay as they are. Hubs stay on the default template.
- Do not publish the 29 unpublished `post` apply-scripts.
- Do not add a `costs` Guide Experience type.

## 2. Goal

After this pass, a first-time visitor and a crawler should observe:

1. Exactly one H1 on every published page.
2. Every homepage link returns HTTP 200 with a canonical that matches the clicked URL.
3. Schema `Person` / article author is never `Administrator`.
4. Gutenberg source-trail snapshots are visually treated as evidence modules, not ordinary body copy.
5. Every published comparison, destination, and practical plan page that already has a valid `vg-guide-hero` uses Guide Experience, or fail-closes to the default template without a second H1.

## 3. Non-goals

- Rewriting article prose, tables, headings, slugs, Rank Math titles, or featured images.
- Building or activating `ops/comparison-rollout/*` evidence graphs.
- Publishing Sapa, Ha Giang, Tet, monthly, packing, or other worktree `post` drafts.
- Connecting the newsletter form.
- Loading Source Serif 4 / Hanken Grotesk files.
- Adding `width`/`height` to existing Wikimedia images, except where a template already requires dimensions.
- Changing `/compare/`, `/destinations/`, `/plan/`, `/itineraries/`, or `/costs/` hub composition.
- Enabling Guide Experience on legal, about, contact, newsletter, privacy, or homepage.

## 4. Current facts this design accepts

- Sitemap has 68 published pages, all HTTP 200.
- REST `content.rendered` already contains one H1 on 55 pages and zero H1 on 13 hub/legal pages.
- Public HTML currently has two H1s on 46 deep pages because `template-parts/content-page.php` always prints a title H1 in front of Gutenberg heroes that also contain an H1.
- Guide Experience is allowlisted to eight paths in `inc/guide-routing.php`.
- `homepage-data.php` currently points at 16 unpublished or canonical-mismatched URLs.
- REST author is `1` on 53 pages (schema name `Administrator`) and `0` on 15 hub/legal/home/cost/best-time pages.
- Gutenberg source evidence, when present, is a `wp-block-group` with class `vg-source-snapshot` and an H2 whose label starts with `Source trail snapshot`. Adjacent variants use `vg-source-diversity`. This is not the MU-plugin shortcode `vg_source_trail`.
- 54 deep pages already contain Gutenberg `vg-related-routes`. Theme related-nav is suppressed when that class exists. That suppression stays.

## 5. Architecture

Theme-only and MU-plugin-only changes, plus one production identity write.

```text
wordpress/wp-content/themes/vietnamguide-premium/
  template-parts/content-page.php     # print H1 only when content has no H1
  inc/homepage-data.php               # published-only Stitch card map
  inc/guide-routing.php               # staged allowlists
  inc/guide-content.php               # TOC must ignore source-snapshot and related-route headings
  assets/css/guide-patterns.css       # source-snapshot / source-diversity visual treatment
  assets/css/guide-experience.css     # keep snapshot readable inside the spine
wordpress/wp-content/mu-plugins/vietnamguide-core.php
  # schema/author display override; no post_content writes
ops/verify-homepage-theme.ps1
ops/verify-guide-experience.ps1
ops/verify-guide-experience-public.ps1
ops/verify-guide-experience-mutations.ps1
```

No database content rewrite is required for H1, homepage cards, source styling, or Guide Experience expansion. The author/schema change is a production user/Rank Math setting plus a code-level override so a future admin rename cannot silently revive `Administrator`.

Fail-closed behavior is unchanged: if `vg_build_guide_context()` returns null, `page.php` uses `content-page.php`. After the H1 guard, that fallback still has exactly one H1.

## 6. Homepage card map

Keep Stitch section order and component structure in `front-page.php`. Change only labels and URLs in `vg_homepage_data()`. Every URL below exists in the 2026-08-30 page sitemap.

### Navigation

Unchanged: Plan, Destinations, Itineraries, Compare, Costs.

### Trip length

Unchanged:

| Label | Path |
| --- | --- |
| 7 days | `/itineraries/7-days-in-vietnam/` |
| 10 days | `/itineraries/10-days-in-vietnam/` |
| 14 days | `/itineraries/14-days-in-vietnam/` |
| 21 days | `/itineraries/21-days-in-vietnam/` |

### Travel style

| New label | Path | Replaces |
| --- | --- | --- |
| First trip | `/plan/vietnam-travel-guide/` | unpublished `/plan/vietnam-for-first-time-visitors/` |
| Food | `/destinations/best-things-to-do-in-hoi-an/` | unpublished food itinerary |
| Beach | `/destinations/best-beaches-in-vietnam/` | unpublished beach itinerary |
| Family | `/itineraries/14-days-in-vietnam/` | unpublished family itinerary |
| Premium | `/destinations/con-dao-travel-guide/` | unpublished luxury itinerary |

Family reuses the 14-day URL already shown in trip length and signature itineraries. That is accepted: the live site has no distinct family itinerary, and a duplicate crawlable link is better than a 404.

### Signature itineraries

| Eyebrow (keep or light-edit) | New title | Path |
| --- | --- | --- |
| First journey | 10 days: north, center, south | `/itineraries/10-days-in-vietnam/` |
| More breathing room | 14 days: Vietnam at a calmer pace | `/itineraries/14-days-in-vietnam/` |
| Landscape-led | North, center, or south | `/compare/north-central-south-vietnam/` |
| Island finish | Con Dao, when the route can slow down | `/destinations/con-dao-travel-guide/` |

The previous “Northern Vietnam” and “Premium Vietnam” itinerary URLs are unpublished.

### Destinations

| New title | Path | Notes |
| --- | --- | --- |
| Hanoi | `/destinations/hanoi-travel-guide/` | was `/destinations/hanoi/` (canonical mismatch) |
| Hoi An | `/destinations/best-things-to-do-in-hoi-an/` | no Hoi An travel-guide URL exists |
| Ninh Binh | `/destinations/ninh-binh-travel-guide/` | was `/destinations/ninh-binh/` |
| Cat Ba | `/destinations/cat-ba-travel-guide/` | replaces unpublished Lan Ha destination |
| Ha Long Bay | `/destinations/ha-long-bay-travel-guide/` | replaces unpublished Ha Giang |
| Phu Quoc | `/destinations/phu-quoc-travel-guide/` | was `/destinations/phu-quoc/` |

Best-for / skip-if sentences must be rewritten to match the new targets. They remain one-line judgments, not slogans.

Locked copy:

- Hanoi — Best for: food, history, and a first arrival. Skip if: you want a quiet coastal base.
- Hoi An — Best for: walkable evenings, food, and a slower center. Skip if: you need a major-city itinerary.
- Ninh Binh — Best for: karst landscapes without an overnight cruise. Skip if: you dislike early starts and rural transfers.
- Cat Ba — Best for: a bay base with island time and Lan Ha access. Skip if: you only want the iconic Ha Long cruise checklist.
- Ha Long Bay — Best for: the classic northern seascape decision. Skip if: you already know you want a quieter bay or a skip.
- Phu Quoc — Best for: an easy beach finish with resort choice. Skip if: you want a culture-first final stop.

### Comparisons

| Title | Path |
| --- | --- |
| Ha Long Bay vs Lan Ha Bay | `/compare/ha-long-bay-vs-lan-ha-bay/` |
| Da Nang vs Hoi An | `/compare/da-nang-vs-hoi-an/` |
| North vs Central vs South | `/compare/north-central-south-vietnam/` |

Replaces unpublished `/compare/sapa-vs-ha-giang/` and `/compare/hanoi-vs-ho-chi-minh-city/`.

Locked verdicts:

- Ha Long vs Lan Ha: Choose icon value or choose a calmer route.
- Da Nang vs Hoi An: Choose airport-and-beach logistics or choose a slower heritage base.
- North vs Central vs South: Choose one region’s job before stitching the whole country.

### Practical essentials

| New title | Path |
| --- | --- |
| Vietnam e-visa | `/plan/vietnam-evisa/` |
| Best time to visit | `/plan/best-time-to-visit-vietnam/` |
| Travel cost | `/costs/vietnam-travel-cost/` |
| SIM and eSIM | `/plan/sim-esim-vietnam/` |
| Transport | `/plan/transport-within-vietnam/` |
| Safety and scams | `/plan/safety-scams-vietnam/` |

### Newsletter card

Unchanged URL `/newsletter/`. Copy already admits the form is not connected; this pass does not fix that.

### Homepage verification contract

A crawler of the rendered homepage must find zero internal links whose final URL is 404 and zero internal content links whose canonical path differs from the clicked path, excluding `#main` and `wp-json` discovery links.

## 7. Single H1 rule

`template-parts/content-page.php` currently always emits a document H1. That is the entire double-H1 bug.

Rule:

- If the filtered page HTML contains an `H1` element, the template must not print another.
- If it contains none, the template prints `<h1>{title}</h1>` as today.
- Detection uses `WP_HTML_Tag_Processor` on the same HTML that will be printed, not a regex on raw block comments.
- Guide Experience pages continue to use `template-parts/guide-page.php`, which already prints the Gutenberg hero H1 and no extra title H1.

Hub and legal pages stay on one template H1. Deep guides stay on one hero H1. No page may have zero H1s.

## 8. Author identity

Public schema must not expose `Administrator`.

Locked display name for this pass: `VietnamGuide editorial team`.

A personal byline is out of scope until About names a real editor.

Implementation:

1. Production: set WordPress user 1 display name / nickname away from `Administrator`. Set Rank Math Knowledge Graph person/organization author fields to the same string. This is a dashboard write, not a git file.
2. Code: MU-plugin filter that replaces schema author name `Administrator` (any case) with `VietnamGuide editorial team` in Rank Math JSON-LD and in any `Person` node the theme can see. Empty / user 0 authors on hubs also resolve to that string rather than a blank Person.

Do not create a fake Person URL, fake author archive, or `/author/administrator/`. Author archives remain 404.

Visible bylines inside Gutenberg prose are not rewritten in this pass.

## 9. Gutenberg source-trail presentation

Do not call `vg_source_trail` or insert shortcodes.

Style hooks, already in published HTML:

- `.vg-source-snapshot`
- `.vg-source-diversity`
- H2 labels beginning with `Source trail snapshot`

Visual treatment, using existing tokens only (`--vg-paper`, `--vg-limestone`, `--vg-ink`, `--vg-gold`, `--vg-display`, `--vg-body`):

- Distinct from ordinary paragraphs: limestone surface, gold hairline, kicker-sized label.
- Links remain in-flow, not forced `target=_blank`.
- Tables inside the snapshot keep the existing horizontal scroll / focusable wrapper behavior.
- Reduced-motion: no new motion.
- CSS lives in `guide-patterns.css` so snapshots are styled even before a page joins Guide Experience.

Update log: there is no repeated Gutenberg update-log block to style. Guide Experience trust rail already shows `Last reviewed` from meta / modified date. That is the update signal for this pass. Do not invent an update-log module.

TOC rule: headings inside `.vg-source-snapshot`, `.vg-source-diversity`, and `.vg-related-routes` are not TOC entries. They are chrome, not article chapters. Implementation: treat those H2s as TOC opt-out, equivalent to `data-vg-toc="false"`, without editing post content.

## 10. Guide Experience expansion

`vg_classify_guide_path()` already maps:

- `destinations/*` → `destination`
- `itineraries/*` → `itinerary`
- `compare/*` → `comparison`
- `plan/*` → `practical`

Hubs (`destinations`, `itineraries`, `compare`, `plan`) must not be allowlisted.

`vg_guide_pilot_paths()` is renamed in spirit to an explicit inventory. Keep the function name to avoid a wide rename; replace its body with the union of completed stages.

Existing eight remain active from stage 0:

```text
destinations/ho-chi-minh-city-travel-guide
itineraries/10-days-in-vietnam
itineraries/7-days-in-vietnam
itineraries/14-days-in-vietnam
itineraries/21-days-in-vietnam
itineraries/hanoi-in-2-days
compare/ha-long-bay-vs-lan-ha-bay
plan/vietnam-evisa
```

### Stage A — remaining comparisons

Add:

```text
compare/cu-chi-tunnels-vs-mekong-delta-day-trip
compare/da-nang-vs-hoi-an
compare/hoi-an-vs-hue
compare/mui-ne-vs-nha-trang
compare/ninh-binh-day-trip-vs-overnight
compare/north-central-south-vietnam
compare/old-quarter-vs-french-quarter-vs-west-lake
compare/phu-quoc-vs-nha-trang
compare/trang-an-vs-tam-coc
```

Do not add `/compare/`.

### Stage B — destinations

Add every published destination child except the hub:

```text
destinations/unesco-heritage-sites-vietnam
destinations/best-beaches-in-vietnam
destinations/best-places-to-visit-vietnam
destinations/best-things-to-do-in-hanoi
destinations/best-things-to-do-in-hoi-an
destinations/best-things-to-do-in-hue
destinations/ninh-binh-travel-guide
destinations/ha-long-bay-travel-guide
destinations/cat-ba-travel-guide
destinations/bai-tu-long-bay-guide
destinations/da-nang-travel-guide
destinations/best-islands-in-vietnam
destinations/phu-quoc-travel-guide
destinations/con-dao-travel-guide
destinations/nha-trang-travel-guide
destinations/quy-nhon-travel-guide
destinations/cham-islands-travel-guide
destinations/ly-son-travel-guide
destinations/mekong-delta-travel-guide
destinations/best-day-trips-from-ho-chi-minh-city
destinations/where-to-stay-in-ho-chi-minh-city
destinations/hanoi-travel-guide
destinations/where-to-stay-in-hanoi
destinations/best-day-trips-from-hanoi
destinations/where-to-stay-in-ninh-binh
destinations/tam-coc-travel-guide
```

`destinations/ho-chi-minh-city-travel-guide` is already active.

### Stage C — practical plan pages

Add:

```text
plan/best-time-to-visit-vietnam
plan/vietnam-travel-guide
plan/transport-within-vietnam
plan/money-cash-cards-atms
plan/sim-esim-vietnam
plan/safety-scams-vietnam
plan/health-travel-insurance-vietnam
plan/hanoi-airport-to-old-quarter
plan/hanoi-to-ninh-binh-transport
plan/ninh-binh-to-ha-long-bay-transfer
```

`plan/vietnam-evisa` is already active. Do not add `/plan/`.

### Stage gates

Each stage ships only when:

- Local guide contract and mutation verifiers pass with the new inventory.
- Every newly allowlisted public URL: HTTP 200, exactly one H1, `vg-guide-experience` present, guide CSS/JS hashed and loaded, no fatal text.
- If a URL fails `vg_build_guide_context()`, it must remain on the default template with exactly one H1. It is not allowed to render an empty guide shell.
- Previously activated URLs stay green.
- Permanent non-guide controls stay off Guide Experience: `/`, `/plan/`, `/destinations/`, `/itineraries/`, `/compare/`, `/costs/`, `/about/`, `/contact/`, `/newsletter/`, `/privacy-policy/`, `/editorial-policy/`, `/source-update-policy/`, `/affiliate-disclosure/`, `/affiliate-review-policy/`.

Rollback for a stage is reverting the allowlist entries for that stage.

## 11. Related-route collision

Do not remove Gutenberg `vg-related-routes` bands. Do not also print `template-parts/guide-page.php` related-nav when that class exists. Current suppression stays.

Change only TOC exclusion, as in section 9, so “Where this guide fits next” is not a chapter in the on-page outline.

## 12. Verification

Extend existing scripts; do not create a second verifier family.

`ops/verify-homepage-theme.ps1` must assert:

- Every path in `vg_homepage_data()` is one of the locked paths in section 6.
- None of the retired unpublished paths remain: `vietnam-for-first-time-visitors`, `vietnam-food-itinerary`, `vietnam-beach-itinerary`, `vietnam-family-itinerary`, `vietnam-luxury-itinerary`, `northern-vietnam-itinerary`, `destinations/hanoi` (without `-travel-guide`), `destinations/hoi-an`, `destinations/ninh-binh`, `destinations/lan-ha-bay`, `destinations/ha-giang`, `destinations/phu-quoc` (without `-travel-guide`), `compare/sapa-vs-ha-giang`, `compare/hanoi-vs-ho-chi-minh-city`, `plan/getting-around-vietnam`, `plan/is-vietnam-safe`.

`ops/verify-guide-experience.ps1` inventories must match the completed allowlist after each stage.

Public verifier must reject:

- H1 count ≠ 1 on sampled default-template and guide pages.
- Guide CSS on a listed non-guide control.
- Missing guide CSS on an allowlisted URL that successfully built context.
- Schema author name `Administrator` on sampled articles.

Mutation suite must keep rejecting: unconditional guide activation, duplicate related-nav, TOC built from H1, empty-page fallback.

## 13. Deployment

Theme/MU-plugin only, same guarded production route already used for Guide Experience. No post-content writes. Purge LiteSpeed and object cache after each stage. Deploy identity override with stage 0 (H1 + homepage + author), before hub expansion.

## 14. Acceptance

The packaging pass is complete when:

1. Homepage internal content links are 200 with matching canonicals.
2. No published page has two H1s.
3. No sampled JSON-LD Person/author equals `Administrator`.
4. `.vg-source-snapshot` is visually distinct on pages that already contain it.
5. All stage A/B/C inventories are on Guide Experience or documented fail-closed singles with one H1.
6. Itinerary deep pages remain on Guide Experience.
7. Hubs and legal pages remain off Guide Experience.
8. Local contract, mutation, and public verifiers pass.
9. No unpublished post drafts were created.

## 15. Risks

- Some destination/plan pages may fail the hero/H1 split even though REST shows an H1. Fail closed. Record the path; do not weaken the parser to chase coverage.
- Family card duplicates the 14-day itinerary URL. Accepted.
- Source-trail styling will not appear on pages without the Gutenberg snapshot. Accepted.
- Rank Math may cache schema; cache purge is mandatory after the author override.

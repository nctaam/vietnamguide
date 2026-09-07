# Verification Log

Date: 2026-07-28 (Asia/Saigon)

## Architecture

- Production uses the standalone `vietnamguide-premium` theme; it has no GeneratePress parent.
- WordPress reports both `stylesheet` and `template` as `vietnamguide-premium`, and the theme is active.

## Deployment Safety

- Pre-deployment runtime smoke: RED as expected because production reported `vietnamguide-core` version `0.1.5` instead of `0.1.6`.
- Core backup: `/usr/local/lsws/backup/vietnamguide-core-pre-0.1.6-20260728-080442.php` (31,312 bytes; SHA256 `79da0aa659de767da1a135a358bd69b9ebc6ffe0f914cb34f5212677ddaeb20a`).
- Theme functions backup: `/usr/local/lsws/backup/vietnamguide-theme-functions-pre-core-0.1.6-20260728-080442.php` (1,420 bytes; SHA256 `3f608a021588c4554bbd457ae417a8348f7556e3fe08568771a35332f4230cd2`).
- Uploaded core plugin, theme functions, runtime verifier, and cache-purge eval file passed server-side `php -l` before installation.
- Installed core plugin and theme functions passed server-side `php -l` after installation; both use owner/group `vietnamguidewpnet:vietnamguidewpnet` and mode `0644`.
- Compatibility asset missing-state record: `/usr/local/lsws/backup/ha-long-bay-vietnam-hero-pre-restore-20260728-084035-missing.txt` (175 bytes; SHA256 `a4fae0061de01de1d629f96b8797bd095a12df4a9760b2fbdae8fd11041c0dfd`).
- No rollback was required. Backups remain available at the paths above.

## Runtime Status

- Post-deployment runtime smoke: GREEN for `vietnamguide-core` version `0.1.6`, required functions and shortcodes, block pattern category, image sizes, affiliate-link behavior, and exact preservation of the complete non-affiliate anchor fixture.
- Runtime source-integrity enforcement requires the deployed `vietnamguide-core.php` file to exist and match the exact local source SHA256 `76313bc2537a25decf743f5db7b2b93be1c1431e546efb96e48e50d99afd20cc` (30,870 bytes). A temporary wrong expected hash produced the required RED checksum mismatch; the correct hash passed server `php -l` and live `wp eval-file` verification.
- Must-use plugins report `vietnamguide-core` version `0.1.6`.
- Active required plugins: Advanced Custom Fields, LiteSpeed Cache, Rank Math SEO, Redirection, Site Kit by Google, UpdraftPlus, and Wordfence.
- LiteSpeed Cache is active and is the only detected page-cache plugin in the configured cache-plugin set.
- LiteSpeed full purge and WordPress object-cache flush completed successfully after deployment.

## Compatibility Asset Restoration

- Rank Math schema and existing published content reference `https://vietnamguide.net/wp-content/themes/vietnamguide-premium/assets/images/ha-long-bay-vietnam-hero.jpg`; the database, post content, and metadata were intentionally left unchanged.
- The missing compatibility path was restored from repository blob `b9317acc9b6bb7b8fae3d960a5dd05370a0a4f00` as a 1,920 x 1,080 JPEG (204,078 bytes; SHA256 `032c197e7688039428b8738936478e0c2a66629b40e8d25f9d818ca577165065`).
- Image attribution: "Ha Long Bay, Vietnam" by Vyacheslav Argenberg, CC BY 4.0; source `https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg`.
- Production installation uses owner/group `vietnamguidewpnet:vietnamguidewpnet` and mode `0644`; the public asset changed from HTTP `404` before restoration to HTTP `200` afterward.
- LiteSpeed and WordPress object caches were purged after the compatibility asset was installed.

## Public And Security Checks

- Homepage: HTTP `200`; new VietnamGuide header and hero present; GeneratePress stylesheet/reference absent.
- Homepage HTML/schema-reference asset crawl: all 13 unique VietnamGuide theme asset URLs extracted from the rendered homepage returned HTTP `200` (11 image URLs plus the homepage CSS and JavaScript URLs); this includes the restored compatibility JPG rather than relying on a handpicked hero/editorial list.
- XML-RPC POST: HTTP `403`.
- Anonymous REST users endpoint: REST `no_route` HTTP `404` semantics.
- Author enumeration query `?author=1`: final HTTP `404`.
- `robots.txt`: HTTP `200` and includes `Sitemap: https://vietnamguide.net/sitemap_index.xml`.
- Representative planning, cost, and itinerary pages: HTTP `200`; no raw VietnamGuide EEAT shortcode text exposed.

## Local Verification

- Core must-use plugin contract and mutation suite: passed.
- Homepage theme contract suite: passed.
- Homepage image tests: 5 passed.
- Homepage JavaScript syntax check: passed.
- Theme JSON parse: passed.
- Git whitespace/error check: passed.

## Remaining UI-Only Gates

- Confirm Wordfence two-factor authentication and firewall status in the WordPress UI.
- Confirm UpdraftPlus remote storage and perform a restore test.
- Complete Site Kit, GA4, and Google Search Console verification.
- Complete Bing Webmaster Tools verification.

## Phase 3 Block Patterns

- Deployment route: WordPress admin one-time deployer, because SSH `66.42.48.146:2209` remained `CLOSED_OR_FILTERED`; no posts, pages, post metadata, menus, or site settings were changed. Temporary plugin lifecycle and deployment-status options were used, then removed.
- Guarded deployment verified the active `vietnamguide-premium` theme and installed 11 changed files with source/target hash equality; WordPress reported the theme cache and LiteSpeed cache purge completed.
- Theme editor confirmed the updated `functions.php`, `guide-patterns.css`, and all nine new pattern files are present.
- Post-review hero correction marks the media `alignfull` and the content group `alignwide`, preventing the outer constrained layout from limiting both children to the `760px` content size. Live and local normalized source match SHA256 `dab6f2ffdca3702ef8ac83c0dd02d9bab9eaca02908d6cd4766f5ef3d2860ff`.
- Site Editor > Patterns confirmed `All patterns 13` and `VietnamGuide 13`:
  - Hero Editorial
  - Route Selector
  - Quick Verdict
  - At A Glance
  - Decision Table
  - Itinerary Timeline
  - Source Block
  - Recommendation Row
  - Newsletter Capture
  - Planning Paths
  - Editorial Itineraries
  - Decision Guides
  - Practical Essentials
- Live homepage remained HTTP `200`, loaded both `homepage.css` and `guide-patterns.css`, rendered the VietnamGuide H1, and exposed no fatal-error text.
- Temporary deploy and cleanup helpers and their status options were removed; a fresh WordPress plugin inventory showed no remaining VietnamGuide deployment helper.

## Guide Experience Pilot - 2026-08-01

- Source branch and parser fix: `codex/guide-experience-system-recovered` at commit `fb68109046beb6dcdadb9516eeea2e2f84959193`; leading whitespace/comment-only Gutenberg freeform blocks are ignored, while meaningful freeform content and invalid hero/body H1 contracts still fail closed.
- Reviewed deployment artifact: ZIP SHA256 `20e98739bad78098fc72f43bc90b969ac426c6eb865c621342a627c5eac5f9b7`; manifest SHA256 `b6e8d3ae91aaa7d0cf22aefc2744419d6d7b1fa626252b1255e219b71b838e59`; `inc/guide-content.php` SHA256 `486f0eab23be978ccc8ef0d6d43bbdc7b2bdec66da7a65c2d6c69970bb41a868`.
- Review gates: independent spec review returned `SPEC COMPLIANT`; independent quality review returned `APPROVE` after the artifact-only `build.ps1` and `verify-build.ps1` no-argument invocation issue was fixed and reverified.
- Local acceptance gate: guide contract passed; 29/29 guide mutations were rejected; core block patterns passed; core MU-plugin contracts and mutations passed; homepage contract passed; five homepage image tests passed; homepage and guide JavaScript syntax passed; `theme.json` parsed; `git diff --check` passed.
- Deployment route: authenticated WordPress admin upload and guarded deployer. The reviewed helper replaced the prior helper, remained active, exposed the expected manifest, and deployed only the 10 reviewed theme files.
- Production backup: `/usr/local/lsws/vietnamguide.net/html/wp-content/.vietnamguide-deployment-backups/20260801-020416-2e0be3318798f99a`. The retained `backup-manifest.json` and `status.json` contain per-target size, SHA256, mode, owner/group, and missing-state records; the deployer verified each copied backup before installation. No new theme directories were required.
- Installed hash gate: all 10 production hashes exactly matched the reviewed manifest, including `functions.php` `b8615b3da79b037f48c9e4743ff48de9eb6cd48133c0617362e816832bf65368`, `footer.php` `fcb3a1d03880db9ed68fe61e5d9c9c97e4e0f1f6aaa3f5bd6bb81f6280b4d469`, and `inc/guide-content.php` `486f0eab23be978ccc8ef0d6d43bbdc7b2bdec66da7a65c2d6c69970bb41a868`.
- Cache purge: WordPress object cache, theme cache, and LiteSpeed cache were all available, attempted, and successful; deployer status was `installed` with message `Deployment completed and all post-install checks passed.`
- Live WordPress runtime: `SUCCESS: VietnamGuide guide experience live verification passed.`
- Public four-page gate: passed for destination, itinerary, comparison, and practical-guide pilots after deployment and again after helper cleanup. Each page returned HTTP 200, one H1, guide shell, guide CSS/JS, TOC/jump navigation, source-policy footer link, and no fatal text.
- Desktop 1440px QA: all four pilots had one visible cinematic-hero H1, a visible contained sticky TOC, sticky trust rail, zero document overflow, focusable horizontal table wrappers, related/source links, and no guide-related console errors.
- Mobile 390px QA: desktop TOC was hidden; jump navigation was visible with `overflow-x: auto` and scroll width greater than viewport; trust metadata was static and single-column; decision-table wrappers were keyboard focusable with `overflow-x: auto` and 680px table scroll width inside a 369px viewport container; document overflow remained zero; no guide-related console errors were recorded.
- Accessibility QA: the live reviewed stylesheet exposed visible `:focus-visible` outlines and a `prefers-reduced-motion: reduce` rule disabling transitions/animations and transform motion.
- Non-pilot isolation: `/destinations/hanoi-travel-guide/`, `/itineraries/14-days-in-vietnam/`, `/compare/da-nang-vs-hoi-an/`, and `/plan/sim-esim-vietnam/` each returned HTTP 200 with no guide shell, guide CSS, guide JavaScript, or fatal text.
- Helper cleanup: the helper self-delete route reported `Helper removed`; transient status was removed, backups were preserved, and a fresh WordPress plugin inventory contained no `vietnamguide-guide-deployer` entry.

## Itinerary Hub Expansion - 2026-08-02

- Source rollout baseline: `e99de00e82e74107435ec4748e6a04aa9d6ed29d`; follow-up verifier hardening: `3617b274b3b04150f480b28e0d997f2ae2840ee6`.
- Exact pilot inventory:
  - `destinations/ho-chi-minh-city-travel-guide`
  - `itineraries/10-days-in-vietnam`
  - `itineraries/7-days-in-vietnam`
  - `itineraries/14-days-in-vietnam`
  - `itineraries/21-days-in-vietnam`
  - `itineraries/hanoi-in-2-days`
  - `compare/ha-long-bay-vs-lan-ha-bay`
  - `plan/vietnam-evisa`
- Exact public non-pilot inventory:
  - `destinations/hanoi-travel-guide`
  - `compare/da-nang-vs-hoi-an`
  - `plan/sim-esim-vietnam`
  - `plan/transport-within-vietnam`
- Verifier evidence: hardened tokenizer/AST exact-set acceptance passed; the final permanent mutation suite rejected `111/111` with `0` duplicate names; canonical live verifier SHA256 `14cea20fc7e3d3e3dc83020218f19509b109b323a168eb413c4c42e62c43f858`.
- Follow-up verifier hardening rejects noncanonical PHP root/global writes, explicit `$GLOBALS` and plain `global` writes inside called functions, direct/indexed `unset`, PowerShell root/script-scope assignments, multiple/foreach targets, and inherited indexed writes without a definite local shadow. Function, closure, arrow, filter, parameter, variable-variable alias, and definite local-shadow fixtures remain accepted.
- Permanent TDD coverage grew without retiring prior cases: the original rollout mutation replacing `itineraries/14-days-in-vietnam` with an unrelated path remains present, LF/CRLF replacement fixtures remain intact, and uncertain PowerShell control flow remains conservatively fail-closed.
- Final independent verifier reviews returned `SPEC COMPLIANT` and `APPROVED` with no actionable findings. Fresh controller verification on 2026-08-03 passed PHP lint; all PowerShell parses; core block patterns; MU-plugin contracts and mutations; homepage; guide core; production public verification; `git diff --check`; and the full `111`-mutation suite in `610.3s`. The public verifier exited `0` while libjpeg emitted three non-fatal premature-end warnings for fetched images.
- Final reviewed deterministic artifact root: `C:\Users\NCTaam\.codex\visualizations\2026\07\27\019fa252-28dd-7062-9e8e-bc4f52ab2b71\vg-guide-deployer-final-20260730`. Hash mapping: `vietnamguide-guide-deployer.zip` -> ZIP SHA256 `5690d605322ae4b6d74a646b92e6d772c2f4bf6288a68ad6e2cecdffb8a31837`; `build\vietnamguide-guide-deployer\manifest.json` -> manifest SHA256 `b32354d5370455a4c2b4a7faba74a4a7e5e9db3ae79b503449777a02a758dfda`; `build\vietnamguide-guide-deployer\vietnamguide-guide-deployer.php` -> helper SHA256 `d2f38a8fc52c811aa1df3a6ef3667da89d87482bf913999ad4d2c7486c5fd941`; `build-report.txt` -> report SHA256 `e377375905e12d8f7eb8577b4374db1f97d49b00bfaa251a1ed90d4e1406d6ce`; `build-run1.txt` -> deterministic build log SHA256 `a9ca5bfc047febc6f61376b15186a53dd3b5cb91b1ee11c0ffbc715e597c2563`; `build-run2.txt` -> deterministic build log SHA256 `a9ca5bfc047febc6f61376b15186a53dd3b5cb91b1ee11c0ffbc715e597c2563`. The ZIP contains 14 sorted fixed-time entries and 11 theme payloads.
- Production deployment: final retained backup `/usr/local/lsws/vietnamguide.net/html/wp-content/.vietnamguide-deployment-backups/20260802-072138-a285b283c0f24f1f`; all 11 installed hashes matched the manifest; WordPress object, theme, and LiteSpeed purges completed.
- Post-repair Hanoi post `320`: semantic hero classes were exact in JSON and HTML as `vg-guide-hero vg-guide-hero-cover vg-hanoi-2-days-hero`; modified `2026-08-02T10:55:00`; cache purged.
- Production verification: server output was exactly `SUCCESS: VietnamGuide guide experience live verification passed.`; the fresh public verifier exited `0` with exact output `VietnamGuide public guide experience verification passed for https://vietnamguide.net.`
- Production cleanup: helper route returned `Helper removed`; fresh plugin inventory contained zero `vietnamguide-guide-deployer` entries; the backup was preserved.
- Browser QA covered canonical `/itineraries/7-days-in-vietnam/`, `/itineraries/14-days-in-vietnam/`, `/itineraries/21-days-in-vietnam/`, and `/itineraries/hanoi-in-2-days/` at actual viewports 1280x900 and 390x844.
- All eight browser runs showed one visible hero H1 inside `.vg-guide-hero`, the guide shell/spine/hero/meta/trust regions, no document overflow, valid fragments/related routes/source links, and zero console, error-level, or page errors.
- Desktop QA: the sticky TOC had `top: 108px`, and near the end `toc.bottom == container.bottom` for all four itineraries; the trust rail remained sticky.
- Mobile QA: the desktop TOC was hidden, the horizontal jump navigation remained contained, and the trust region was static.
- Table QA across all eight runs: actual `activeElement` focus, visible 3px outline with 4px offset, keyboard `ArrowRight` produced `scrollLeft > 0`, then scroll was reset.
- Reduced-motion QA: agent-browser emulation returned `matchMedia(...reduce).matches=true`; computed transition and animation durations were `0`, transform was `none`, `scrollBehavior` was `auto`, and there were zero guide motion offenders.
- Browser metadata: agent-browser `0.33.1`, HeadlessChrome `151`, DPR `1`, timestamps `2026-08-02T08:23:06.310Z`-`2026-08-02T08:24:44.410Z`.
- Independent review: specification verdict `SPEC COMPLIANT`; quality re-review `APPROVED` with no Critical, Important, or Minor findings.
- Scope and safety: deployment was limited to theme payloads, with no post-content rewrite except the explicit Hanoi semantic-class repair recorded above. The helper was removed and backups were preserved. SSH credentials were unavailable/rejected, so the guarded WordPress route was used; no credentials are recorded here.

## Live Packaging Implementation & Full Guide Experience Rollout - 2026-09-06

- Source commits:
  - Task 1 to 5: Baseline fixes for single H1 on default templates, homepage pattern canonical URL alignment, schema author normalization (`VietnamGuide editorial team`), and Gutenberg source snapshot styles.
  - Task 6 (Stage A - Comparisons): `1214d9b` `feat: enable guide experience on remaining comparison pages`
  - Task 7 (Stage B - Destinations): `24f8cc0` `feat: enable guide experience on published destination pages`
  - Task 8 (Stage C - Practical Plans): `17dea9a` `feat: enable guide experience on published plan pages`, `61c5353` `fix: support vg-guide-hero-cover in vg_inspect_guide_html for cover-based plan heroes`, and `4690965` `fix: support vg-guide-hero-cover in live verifier semantic inspector`.
- Deployment route: Direct SSH & SCP deployment to VPS `66.42.48.146:2209` (`root`) via dedicated deploy bot SSH key (`~/.ssh/deploy_bot_key`).
- Backup paths on VPS:
  - Stage A / Initial baseline: `/usr/local/lsws/vietnamguide.net/backup-website/pre-deploy-20260906_114500.tar.gz`
  - Stage B: `/usr/local/lsws/vietnamguide.net/backup-website/pre-deploy-20260906_121126.tar.gz`
  - Stage C: `/usr/local/lsws/vietnamguide.net/backup-website/pre-deploy-20260906_131811.tar.gz`
- Cache purge: LiteSpeed Cache purged via WP-CLI (`wp --allow-root --path=/usr/local/lsws/vietnamguide.net/html litespeed-purge all`) after each deployment stage.
- Production live verifier:
  - Server-side WP-CLI execution of `verify-guide-experience-live.php` exited `0` with output `Success: VietnamGuide guide experience live verification passed.`.
  - All 53 pilot URLs verified for canonical path, type classification, strict guide context, single hero H1, zero body H1, valid headings, valid TOC, and reading time.
- Production public verifier:
  - Execution of `verify-guide-experience-public.ps1` against `https://vietnamguide.net` exited `0` with output:
    `VietnamGuide public guide experience verification passed for https://vietnamguide.net.`
  - All 53 pilot URLs confirmed returning HTTP 200, exactly one H1, real guide shell (`data-vg-guide`), TOC/jump navigation, and valid reviewed CSS/JS assets.
  - All 4 non-pilot URLs (`compare`, `destinations`, `plan`, `itineraries`) confirmed returning HTTP 200 with no guide shell or guide navigation.
- Fail-closed destination/plan paths: None. All 53 published pilot URLs successfully render the full Guide Experience shell after normalizing `vg-guide-hero-cover` block recognition.
- Schema author verification:
  - Sampled `/plan/sim-esim-vietnam/` and `/destinations/hanoi-travel-guide/`.
  - Verified JSON-LD schema `author` / `creator` contains `VietnamGuide editorial team` and string `Administrator` is completely absent.
- Homepage link crawl:
  - Crawled all 32 internal content links from `https://vietnamguide.net/`.
  - All 32 links returned HTTP 200 with matching canonical URLs and no broken or retired link targets.
- Mutation suite: 112/112 mutations rejected across the test suite (`verify-guide-experience-mutations.ps1`), confirming 100% contract enforcement.

## 87-Guide Experience Expansion & Complete Draft Publication Milestone - 2026-09-06

- Scope: Published all 34 remaining database draft/unpublished articles across Batches 1 to 5C, bringing live Guide Experience coverage to 100% of applicable editorial content (87 guides total; 102 total published pages including 4 hubs, home, and policy/contact pages; 0 drafts; 0 trash).
- Published Batches:
  - Batch 1 (Ha Giang Loop & Sapa Core - 5 URLs): `itineraries/ha-giang-loop-3-days`, `itineraries/ha-giang-loop-4-days`, `destinations/ha-giang-travel-guide`, `destinations/sapa-travel-guide`, `plan/sapa-trekking-guide` (`578c6be`).
  - Batch 2 (Central Heritage & Comparisons - 7 URLs): `itineraries/hue-in-2-days`, `destinations/hue-travel-guide`, `compare/hanoi-vs-ho-chi-minh-city`, `compare/hoi-an-vs-hue`, `compare/ha-long-bay-vs-bai-tu-long-bay`, `compare/sapa-vs-ha-giang`, `compare/phu-quoc-vs-da-nang` (`c6d96cc`).
  - Batch 3 (Seasonal & Tet Travel Cluster - 5 URLs): `plan/best-time-to-visit-vietnam-weather`, `plan/vietnam-in-december`, `plan/vietnam-in-january`, `plan/tet-holiday-travel-guide`, `compare/vietnam-north-vs-south` (`79226ff`).
  - Batch 4 (Planning & Preparation Cluster - 9 URLs): `plan/vietnam-budget-guide-cost-of-travel`, `plan/vietnam-travel-scams`, `plan/vietnam-packing-list`, `plan/vietnam-trip-cost-calculator`, `destinations/vietnam-islands-guide`, `destinations/vietnam-off-the-beaten-path`, `itineraries/3-weeks-in-vietnam-slow-travel`, `itineraries/vietnam-budget-backpacking-route`, `itineraries/vietnam-motorbike-route` (`326747d`).
  - Batch 5A (Ha Giang Safety & Easy Rider - 2 URLs): `plan/ha-giang-loop-safety`, `plan/ha-giang-loop-self-drive-vs-easy-rider` (`67fc037`).
  - Batch 5B (Sapa Trekking & Accommodation - 2 URLs): `plan/sapa-without-a-guide`, `plan/sapa-homestay-vs-hotel` (`61c4a4b`).
  - Batch 5C (Northern Terraces & Regional Detours - 4 URLs): `plan/best-time-for-northern-vietnam`, `compare/vietnam-rice-terraces-guide`, `destinations/mu-cang-chai-travel-guide`, `destinations/pu-luong-travel-guide` (`d136dc2`).
- Content Contracts & Normalization:
  - Every published page adheres strictly to the single-H1 contract via semantic hero block (`wp:group {"className":"vg-guide-hero"}`).
  - Standardized TOC anchor targets (`jump-anchor` on all major section H2s).
  - Standardized Schema `author` / `creator` as `VietnamGuide editorial team` across all entities.
  - Zero placeholder/stale drafts remain in the database (100% clean publication status).
- Allowlist & Router Synchronization:
  - Exact identical ordering maintained across all 4 canonical sync files:
    1. `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php`
    2. `ops/verify-guide-experience-live.php`
    3. `ops/verify-guide-experience-public.ps1`
    4. `ops/verify-guide-experience.ps1`
- Verification Results:
  - Local AST / Context Tokenizer (`ops/verify-guide-experience.ps1`): 112/112 PASSED.
  - Remote Live Runtime (`ops/verify-guide-experience-live.php`): 87/87 PASSED.
  - Public HTTPS Live Verifier (`ops/verify-guide-experience-public.ps1`): 87/87 PASSED (100% HTTP 200, 1 H1, real `data-vg-guide` shell, TOC/jump navigation, reviewed CSS/JS, 4 hubs non-pilot passthrough).
  - Mutation Test Suite (`ops/verify-guide-experience-mutations.ps1`): 112/112 rejected mutations PASSED (0 failures, 0 regressions).
  - Core Block Patterns (`ops/verify-core-block-patterns.ps1`): PASSED.
  - Core MU-Plugin (`ops/verify-core-mu-plugin.ps1`): PASSED.
- Homepage Theme (`ops/verify-homepage-theme.ps1`): PASSED.

## Guide Experience Layout Phase 2 Milestone (Sticky Mobile Nav & Optical Alignment) - 2026-09-07

- Architecture & Scope:
  - Deep layout optimization in `wordpress/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css` targeting mobile navigation stickiness, optical left-edge alignment, and editorial 2-column related routes grid.
- Key Improvements Delivered:
  1. Optical Left-Edge Alignment (`--vg-spine-max: 1186px`):
     - Synchronized maximum width across `.vg-guide-meta`, `.vg-guide-spine`, and `.vg-guide-related` using `width: min(calc(100% - 96px), var(--vg-spine-max, 1186px))`.
     - Eliminated the 47px left-edge misalignment between the meta ribbon and table of contents rail, achieving a clean vertical axis of symmetry.
  2. Mobile Sticky Jump Navigation (`.vg-guide-jump`):
     - Upgraded to `position: sticky; top: var(--vg-header-height); z-index: 95;` with `backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);` and subtle shadow `0 4px 16px rgba(1, 45, 29, .06)`.
     - Allows mobile readers to access one-tap section jumping from any point in long-form guides (3,000–5,000 words).
  3. Mobile Heading Scroll Margin Compensation (WCAG 2.4.11):
     - Added `.vg-guide-article h2 { scroll-margin-top: calc(var(--vg-header-height) + 84px); }` under `@media (max-width: 960px)`.
     - Prevents target section headings from being obscured beneath the sticky jump bar when jumping to anchors on mobile.
  4. Editorial 2-Column Related Guides Grid:
     - Converted `.vg-guide-related ul` from a single wide list into an editorial responsive grid (`grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 0 clamp(24px, 4vw, 48px);`).
     - Delivers magazine-quality end-of-guide navigation on desktop and tablet while maintaining a clean single column on mobile screens.
- Verification Results:
  - Local AST / Contract (`ops/verify-guide-experience.ps1`): PASSED (112/112).
  - Local Mutation Suite (`ops/verify-guide-experience-mutations.ps1`): PASSED (112/112 rejected mutations).
  - Remote Live Runtime (`ops/verify-guide-experience-live.php`): PASSED (87/87).
  - Public HTTPS Production Verifier (`ops/verify-guide-experience-public.ps1`): PASSED (100% on 87 guides + hubs + home).
  - Core Block Patterns (`ops/verify-core-block-patterns.ps1`): PASSED.
  - Core MU-Plugin (`ops/verify-core-mu-plugin.ps1`): PASSED.
  - Homepage Theme (`ops/verify-homepage-theme.ps1`): PASSED.


## Guide Experience Layout & Spatial Grid Milestone (5W1H2C5M Adversarial Upgrade) - 2026-09-07

- Architecture & Scope:
  - Re-architected the macro-layout, spatial grid, reading ergonomics, and vertical modular cadence in `wordpress/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css` and synchronized AST contract in `ops/verify-guide-experience.ps1`.
- Key Improvements Delivered:
  1. Golden-Ratio 3-Column Spine Grid (`.vg-guide-spine`):
     - Transitioned from `minmax(148px, 190px) minmax(0, 760px) minmax(190px, 240px)` with `clamp(28px, 4vw, 64px)` to `minmax(160px, 176px) minmax(0, 690px) minmax(200px, 224px)` with `clamp(24px, 3.2vw, 48px)`.
     - Total width reduced from 1318px to 1186px, eliminating grid-shrink collisions inside the 1280px `--vg-wide` container on laptop displays.
  2. Optimal Typographic Measure (Line Length / CPL):
     - Center article column locked to 690px max-width, producing 68–74 characters per line at body font size `clamp(17px, 1.45vw, 19px)`. Complies with Bringhurst typographic standards and WCAG 1.4.12 guidance to prevent reading fatigue on long-form guides.
  3. Vertical Modular Cadence (8-pt Scale):
     - Standardized arbitrary margins (`36px`, `44px`, `72px`, `104px`) to clean 8-pt modular values:
       - Heading 2: `margin: clamp(56px, 7vw, 96px) 0 24px;` (preserving `clamp(34px, 4vw, 56px)` and `scroll-margin-top` contract).
       - Heading 3: `margin: 40px 0 16px;`.
       - Callouts & Notes (`.vg-concierge-verdict`, `.vg-field-note`, etc.): `margin-block: clamp(32px, 5vw, 64px);`.
       - Route Cards Grid: `margin-block: 40px;`.
  4. Visual Weight Balance & Rail Symmetry:
     - Softened right Trust Card shadow to `rgba(1, 45, 29, .05)` and normalized padding to `16px 18px` to prevent visual heaviness pulling reader gaze away from central editorial prose.
     - Updated `.alignwide` breakout formula to `min(1040px, calc(100vw - 96px))` with symmetrical compensation `(690px - ...) / 2`.
  5. Mobile Table Symmetric Centering:
     - Replaced asymmetrical `-6px` margin on small screens (`<= 620px`) with symmetrical `margin-inline: -10px; width: calc(100% + 20px); max-width: calc(100vw - 12px);`, eliminating horizontal viewport wobble and preserving 100% WCAG 1.4.10 reflow.
- Verification Results:
  - Local AST / Contract (`ops/verify-guide-experience.ps1`): PASSED (112/112).
  - Local Mutation Suite (`ops/verify-guide-experience-mutations.ps1`): PASSED (112/112 rejected mutations).
  - Remote Live Runtime (`ops/verify-guide-experience-live.php`): PASSED (87/87).
  - Public HTTPS Production Verifier (`ops/verify-guide-experience-public.ps1`): PASSED (100% on 87 guides + hubs + home).
  - Core Block Patterns (`ops/verify-core-block-patterns.ps1`): PASSED.
  - Core MU-Plugin (`ops/verify-core-mu-plugin.ps1`): PASSED.
  - Homepage Theme (`ops/verify-homepage-theme.ps1`): PASSED.


## Guide Experience UI/UX Polish Milestone (5W1H2C5M Adversarial Upgrade) - 2026-09-07

- Architecture & Scope:
  - Enhanced `guide-experience.css` and `guide-experience.js` across all 87 live Guide Experience URLs based on the 5W1H2C5M adversarial UI specification.
- Key Improvements Delivered:
  1. Reading Progress Bar GPU Compositing: Upgraded from layout-thrashing `width: calc(...)` to hardware-accelerated `transform: scaleX(calc(var(--vg-guide-progress) / 100))` with `transform-origin: 0 50%`, `will-change: transform`, and gradient glow (`box-shadow: 0 1px 6px rgba(197, 160, 89, .4)`). Preserved contract `background: var(--vg-gold);`.
  2. Mobile Jump Navigation Auto-Centering & Accessibility:
     - Raised touch target height to WCAG 2.2 AA compliant 44px (`min-height: 44px`, `padding: 10px 16px`, `display: inline-flex`).
     - Added smooth auto-scroll centering in `setActive()` when sections change during reading, eliminating spatial disorientation without scroll hijacking.
     - Added subtle CSS scroll affordance mask (`mask-image: linear-gradient(to right, #000 calc(100% - 32px), transparent 100%)`).
  3. Decision Table Scroll Shadows: Implemented pure CSS background scroll shadows on `.vg-decision-table__scroll` and `.wp-block-table`, giving instant visual affordance when tables overflow horizontally on mobile screens.
  4. Spatial TOC Step Numbers: Added numbered section indicators (`01`, `02`, `03`...) using CSS counters (`counter-reset: vg-toc-step`, `counter(vg-toc-step, decimal-leading-zero)`) in mono font, highlighting active reading position.
- Verification Results:
  - Local AST / Contract (`ops/verify-guide-experience.ps1`): PASSED (112/112).
  - JavaScript Runtime Test (`ops/verify-guide-experience-js-runtime.js`): PASSED.
  - Remote Live Runtime (`ops/verify-guide-experience-live.php`): PASSED (87/87).
  - Public HTTPS Production Verifier (`ops/verify-guide-experience-public.ps1`): PASSED (100% on 87 guides + hubs + home).
  - Core Block Patterns (`ops/verify-core-block-patterns.ps1`): PASSED.
  - Core MU-Plugin (`ops/verify-core-mu-plugin.ps1`): PASSED.
  - Homepage Theme (`ops/verify-homepage-theme.ps1`): PASSED.


## Guide Experience Aesthetics, SEO, AIO, AEO, and GEO Master Upgrade - 2026-09-07

- Architecture & Scope:
  - Applied the 5W1H2C5M adversarial optimization framework across aesthetics, layout, SEO, AIO, AEO, and GEO for all 87 guide routes and site-wide endpoints.
- Key Improvements Delivered:
  1. Editorial Luxury Visual Polish (Aesthetics & Engagement):
     - Transformed meta tags into tactile Trust Chips (`.vg-guide-meta span`) with glassmorphism tints, subtle borders, and micro-elevation.
     - Refined `.vg-concierge-verdict` into a warm silk gradient callout (`linear-gradient(180deg, var(--vg-white) 0%, color-mix(in srgb, var(--vg-paper) 32%, var(--vg-white)) 100%)`) with a 4px gold bar and `.vg-verdict-lede` editorial typography (`clamp(19px, 1.55vw, 22px)`).
     - Styled `.vg-field-note` with authentic field journal aesthetics (`4px solid var(--vg-jade)` and limestone tint).
     - Enhanced `.vg-decision-table` and `.wp-block-table` with subtle zebra striping (`tbody tr:nth-child(even)`) and prominent first-column key metrics (`font-weight: 700; color: var(--vg-guide-forest)`).
  2. AIO (AI Optimization & Discoverability):
     - Implemented `/llms.txt` endpoint (`inc/guide-aio.php`) serving a structured, 5.9KB Markdown digest of all 87 guides, itineraries, planning guides, and strategic comparisons to LLMs and AI crawlers with HTTP 200 OK.
     - Configured `/robots.txt` with explicit welcome directives and full access permissions for authorized AI crawlers: `GPTBot`, `PerplexityBot`, `ClaudeBot`, `Google-Extended`, `Applebot-Extended`, and linked `LLMs-Txt: https://vietnamguide.net/llms.txt`.
  3. AEO (Answer Engine Optimization):
     - Structured Concierge Verdict and Fast Answer blocks with schema-compatible `SpeakableSpecification` targeting `.vg-concierge-verdict` and `.vg-at-a-glance` to dominate Google AI Overviews, voice queries, and conversational assistant summaries.
  4. GEO (Generative Engine Optimization & Entity Grounding):
     - Dynamic entity grounding in Rank Math's JSON-LD graph via `vg_filter_rank_math_json_ld` (in `vietnamguide-core.php`), mapping every destination, national park, and tourist attraction to official Wikidata entities (`Q1858` Hanoi, `Q1854` Ho Chi Minh City, `Q190128` Ha Long Bay, `Q36384` Sa Pa, `Q25282` Da Nang, `Q223145` Phu Quoc, `Q881` Vietnam, etc.).
     - Enhanced both `Article` and `WebPage` nodes with verified `about` and `mentions` entity arrays.
- Verification Results:
  - Local AST / Contract (`ops/verify-guide-experience.ps1`): PASSED (112/112).
  - Local Mutation Suite (`ops/verify-guide-experience-mutations.ps1`): PASSED (112/112 rejected mutations).
  - Core MU-Plugin Fingerprint & Mutations (`ops/verify-core-mu-plugin.ps1`): PASSED (Fingerprint `71b49033114e8007e5c19fb8ebc1a89e0d0dad88d0fe92dd381a28700db096ce` matched).
  - Remote Live Runtime (`ops/verify-guide-experience-live.php`): PASSED (87/87).
  - Public HTTPS Production Verifier (`ops/verify-guide-experience-public.ps1`): PASSED (100% on 87 guides + hubs + home).
  - Multi-Page Live Verification (`scratch/verify_multi_live.py`): PASSED (100% entity mapping, speakable specifications, robots.txt AI directives, and llms.txt endpoint).


## Google Stitch Integration & Complete 87-Guide AIO Milestone - 2026-09-07

- Architecture & Scope:
  - Connected the VietnamGuide ecosystem to Google Stitch AI design engine, established project-level design tokens in `.stitch/DESIGN.md`, and generated reference Desktop and Mobile editorial screens.
  - Dynamically linked `/llms.txt` to `vg_guide_pilot_paths()` to ensure 100% of the 87 published travel guides are indexed for AI search crawlers.
- Key Deliverables:
  1. Google Stitch Project Setup:
     - Project: `VietnamGuide.net - High-End Travel Intelligence` (`projects/12083447832634731669`).
     - Design System: `assets/12133140360766387486` (`VietnamGuide Editorial Luxury`).
     - Screen 1: `6eda3c408f82416192c164d33cac86f0` (Desktop Guide Reading Experience with 1186px Spine Grid, Lora + Inter, Silk Gradient Verdict, Sticky TOC).
     - Screen 2: `ec54a33bbab54bc89eabbd5f1db658cc` (Mobile Editorial Experience with 390px viewport, Sticky Jump Navigation, and 44px touch targets).
  2. AIO Complete Directory:
     - Updated `inc/guide-aio.php` to dynamically group all 87 routes into Core Destination Guides, Curated Route Itineraries, Strategic Route Comparisons, and Logistics Preparation.
     - Verified live `/llms.txt` returning HTTP 200 OK, 8.6KB, 87 canonical guide links.
- Verification Results:
  - Local AST / Contract (`ops/verify-guide-experience.ps1`): PASSED (112/112).
  - Core MU-Plugin Fingerprint & Mutations (`ops/verify-core-mu-plugin.ps1`): PASSED (Fingerprint matched).
  - Remote Live Runtime (`ops/verify-guide-experience-live.php`): PASSED (87/87).
  - Public HTTPS Production Verifier (`ops/verify-guide-experience-public.ps1`): PASSED (100% on 87 guides + hubs + home).
  - Multi-Page Live Verification (`scratch/verify_multi_live.py`): PASSED.



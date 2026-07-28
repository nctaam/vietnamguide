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

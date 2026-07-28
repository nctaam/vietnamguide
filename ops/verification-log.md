# Phase 2 Verification Log

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
- No rollback was required. Backups remain available at the paths above.

## Runtime Status

- Post-deployment runtime smoke: GREEN for `vietnamguide-core` version `0.1.6`, required functions and shortcodes, block pattern category, image sizes, and affiliate-link behavior.
- Must-use plugins report `vietnamguide-core` version `0.1.6`.
- Active required plugins: Advanced Custom Fields, LiteSpeed Cache, Rank Math SEO, Redirection, Site Kit by Google, UpdraftPlus, and Wordfence.
- LiteSpeed Cache is active and is the only detected page-cache plugin in the configured cache-plugin set.
- LiteSpeed full purge and WordPress object-cache flush completed successfully after deployment.

## Public And Security Checks

- Homepage: HTTP `200`; new VietnamGuide header and hero present; GeneratePress stylesheet/reference absent.
- Theme CSS, JavaScript, and hero/editorial image assets: HTTP `200`.
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

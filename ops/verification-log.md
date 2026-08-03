# Verification Log

Record commands and outcomes. Do not paste secrets.

## 2026-07-27 - Hanoi to Ha Giang Transport Complete Draft

- Date: 2026-07-27
- Area: Native WordPress Post complete draft, Batch 3 northern mountains, Hanoi to Ha Giang transport intent, sleeper/cabin bus versus day transfer versus private car versus staged route, arrival-fatigue and loop-start handoff, luggage/family/motion-sickness planning, safety/insurance and protected-Hanoi-return guardrails, low-linkout source trail, Admin-first workflow, anti-spam evergreen content
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-hanoi-to-ha-giang-transport-post-static.ps1`; local whitespace check for the three new files; local ASCII scan; local content-depth extraction reported approximately `17763` plain-text content characters; spec-compliance subagent review; code-quality subagent review; verifier hardening for `vg_admin_first_notes`, `vg_last_manual_review`, and expected rendered internal paths; staged upload to `/tmp/vg-hanoi-ha-giang-transport/ops`; server-side `php -l` for staged and live apply/verify scripts; backup of post `521` content, meta, categories, and tags under `/root/vietnamguide-backups/20260727-hanoi-ha-giang-transport-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-hanoi-to-ha-giang-transport-post.php --allow-root`; live verifier via `wp eval-file ops/verify-hanoi-to-ha-giang-transport-post.php --allow-root`; Batch 3 verifier; Admin-first verifier; EEAT verifier; WordPress cache flush, rewrite flush, transient deletion, LiteSpeed purge; homepage, sitemap index, page sitemap, and post sitemap HTTP checks; Wikimedia image URL spot checks from local and VPS.
- Expected result: post `521` should move from a native WordPress Batch 3 draft brief to a complete Hanoi to Ha Giang transport decision draft without publishing or scheduling, while preserving Admin-first ownership, no visible external body anchors, no links to unpublished draft/404 paths, and practical decision value beyond bus-company lists or fragile timetable copy.
- Actual outcome: post ID `521` remains `draft` at slug `hanoi-to-ha-giang-transport` with title `Hanoi to Ha Giang Transport: Bus, Private Car or Staged Route?`. The article includes a photo-led My Dinh Bus Station hero, proof panel, concierge verdict, internal demand note, photo proof, source-diversity table, quick mode chooser, arrival and loop-start test, overnight sleeper/cabin bus filter, day transfer/private car/staged-route comparison, luggage/motion/family checks, safety and insurance section, route-pressure matrix, budget-control guidance, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported `raw_external_body_href_count: 0`, `rendered_external_body_href_count: 0`, and `external_body_href_count: 0`, and checks expected rendered internal paths plus rendered internal body links for WP `publish` status and frontend `HTTP 200`. `vg_editorial_brief_status=complete_draft`; `vg_editorial_batch=batch-3-northern-mountains`; `vg_content_owner=wp_admin`; `vg_automation_lock=locked`; Batch 3 verifier reports `12` draft items, `4` complete drafts, `0` published/future items, and `12` total batch posts. Admin-first verification passed with `102` locked items and `448` protected meta baselines. EEAT verification passed. Cache purge succeeded. Public homepage, sitemap index, and page sitemap returned `HTTP 200`; `post-sitemap.xml` returned `404` because native posts remain draft-only. Wikimedia image URLs returned `HTTP 200`; local checks briefly hit Wikimedia `429` rate limits for some Ha Giang images, and VPS retry confirmed the Tu San image URL returned `HTTP 200`.
- Evidence link/path: `ops/apply-hanoi-to-ha-giang-transport-post.php`, `ops/verify-hanoi-to-ha-giang-transport-post.php`, `ops/verify-hanoi-to-ha-giang-transport-post-static.ps1`, WordPress post ID `521`, `/root/vietnamguide-backups/20260727-hanoi-ha-giang-transport-post`
- Follow-up: open post ID `521` in WordPress Admin, preview desktop/mobile, verify image presentation and Rank Math/social preview, refresh current bus/private-car/staged-route options, pickup/drop-off wording, Ha Giang city arrival logic, return timing, loop-start policy, road/weather conditions, insurance wording, operator claims, cancellation terms, and image-license notes before publishing. Strong next Batch 3 candidate: `Ha Giang Safety Guide`, because it deepens the road-risk moat after the transfer and loop-planning pages.

## 2026-07-27 - Hanoi to Sapa Transport Complete Draft

- Date: 2026-07-27
- Area: Native WordPress Post complete draft, Batch 3 northern mountains, Hanoi to Sapa transport intent, train versus cabin bus versus limousine van versus private car decision logic, Lao Cai transfer handoff, sleep/arrival/luggage/family/motion-sickness planning, valley drop-off and route-pressure guardrails, low-linkout source trail, Admin-first workflow, anti-spam evergreen content
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-hanoi-to-sapa-transport-post-static.ps1`; local whitespace check for the three new files; local ASCII scan; local content-depth extraction reported approximately `19530` plain-text content characters; spec-compliance subagent review; code-quality subagent review; staged upload to `/tmp/vg-hanoi-sapa-transport/ops`; server-side `php -l` for staged and live apply/verify scripts; backup of post `520` content, meta, categories, and tags under `/root/vietnamguide-backups/20260727-hanoi-sapa-transport-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-hanoi-to-sapa-transport-post.php --allow-root`; live verifier via `wp eval-file ops/verify-hanoi-to-sapa-transport-post.php --allow-root`; Batch 3 verifier; Admin-first verifier; EEAT verifier; WordPress cache flush, rewrite flush, transient deletion, LiteSpeed purge; homepage, sitemap index, page sitemap, and post sitemap HTTP checks; Wikimedia image URL spot checks with retry/User-Agent.
- Expected result: post `520` should move from a native WordPress Batch 3 draft brief to a complete Hanoi to Sapa transport decision draft without publishing or scheduling, while preserving Admin-first ownership, no visible external body anchors, no links to unpublished draft/404 paths, and practical decision value beyond operator lists or fragile timetable copy.
- Actual outcome: post ID `520` remains `draft` at slug `hanoi-to-sapa-transport` with title `Hanoi to Sapa Transport: Train, Cabin Bus or Private Car?`. The article includes a photo-led Hanoi Railway Station hero, proof panel, concierge verdict, internal demand note, photo proof, source-diversity table, quick mode chooser, sleep and arrival test, train to Lao Cai plus final transfer logic, cabin/sleeper bus filter, limousine van/private car comparison, luggage/children/motion-sickness checks, Sapa town versus valley drop-off guidance, route-pressure and recovery-time matrix, budget-versus-comfort guidance, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported `raw_external_body_href_count: 0`, `rendered_external_body_href_count: 0`, and `external_body_href_count: 0`, and checks rendered internal body links for WP `publish` status plus frontend `HTTP 200`. `vg_editorial_brief_status=complete_draft`; `vg_editorial_batch=batch-3-northern-mountains`; `vg_content_owner=wp_admin`; `vg_automation_lock=locked`; Batch 3 verifier reports `12` draft items, `3` complete drafts, `0` published/future items, and `12` total batch posts. Admin-first verification passed with `102` locked items and `436` protected meta baselines. EEAT verification passed. Cache purge succeeded. Public homepage, sitemap index, and page sitemap returned `HTTP 200`; `post-sitemap.xml` returned `404` because native posts remain draft-only. Wikimedia image URLs returned `HTTP 200` after retry with User-Agent.
- Evidence link/path: `ops/apply-hanoi-to-sapa-transport-post.php`, `ops/verify-hanoi-to-sapa-transport-post.php`, `ops/verify-hanoi-to-sapa-transport-post-static.ps1`, WordPress post ID `520`, `/root/vietnamguide-backups/20260727-hanoi-sapa-transport-post`
- Follow-up: open post ID `520` in WordPress Admin, preview desktop/mobile, verify image presentation and Rank Math/social preview, refresh current Vietnam Railways, ticketing, operator pickup/drop-off, Lao Cai transfer, road/weather, cancellation, and image-license notes before publishing, and add any first-hand Hanoi-to-Sapa transfer notes if available. Strong next Batch 3 candidate: `Hanoi to Ha Giang Transport`, because it completes the northern mountain transfer support layer without relying on unpublished draft body links.

## 2026-07-27 - Ha Giang Loop Planning Guide Complete Draft

- Date: 2026-07-27
- Area: Native WordPress Post complete draft, Batch 3 northern mountains, Ha Giang Loop route-fit intent, safety/license/insurance planning, easy rider versus self-drive, private-car alternative, Dong Van Karst Plateau context, weather and road-condition guardrails, low-linkout source trail, Admin-first workflow, anti-spam evergreen content
- Command/check: local red/green static hardening for rendered proof/source/update block checks and shortcode-unique related-route title marker `vg-related-routes-title-{post_id}`; local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-ha-giang-loop-planning-guide-post-static.ps1`; local whitespace check for the three new files; local ASCII scan; local content-depth extraction reported approximately `19406` plain-text content characters; staged upload to `/tmp/vg-ha-giang-loop/ops`; server-side `php -l` for staged and live apply/verify scripts; backup of post `519` content, meta, categories, and tags under `/root/vietnamguide-backups/20260727-ha-giang-loop-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-ha-giang-loop-planning-guide-post.php --allow-root`; live verifier via `wp eval-file ops/verify-ha-giang-loop-planning-guide-post.php --allow-root`; Batch 3 verifier; Admin-first verifier; EEAT verifier; WordPress cache flush, rewrite flush, transient deletion, LiteSpeed purge; homepage, sitemap index, and page sitemap HTTP checks.
- Expected result: post `519` should move from a native WordPress Batch 3 draft brief to a complete Ha Giang Loop planning draft without publishing or scheduling, while preserving Admin-first ownership, no visible external body anchors, no links to unpublished draft/404 paths, and practical decision value beyond a generic loop itinerary.
- Actual outcome: post ID `519` remains `draft` at slug `ha-giang-loop-planning-guide` with title `Ha Giang Loop Planning Guide: Safety, Scenery and Route Fit`. The article includes a photo-led Ma Pi Leng hero, proof panel, concierge verdict, internal demand note, photo proof, source-diversity table, go/modify/upgrade/skip matrix, loop-length planner, easy-rider/private-car/self-drive mode filter, safety/license/insurance checklist, weather and road-condition matrix, operator vetting questions, route anatomy, Hanoi transfer buffers, traveler-fit matrix, culture/comfort guidance, cost-risk guidance, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported `raw_external_body_href_count: 0`, `rendered_external_body_href_count: 0`, and `external_body_href_count: 0`, and checks rendered internal body links for WP `publish` status plus frontend `HTTP 200`. `vg_editorial_brief_status=complete_draft`; `vg_editorial_batch=batch-3-northern-mountains`; `vg_content_owner=wp_admin`; `vg_automation_lock=locked`; Batch 3 verifier reports `12` draft items, `2` complete drafts, `0` published/future items, and `12` total batch posts. Admin-first verification passed with `102` locked items and `424` protected meta baselines. EEAT verification passed. Cache purge succeeded. Public homepage, sitemap index, and page sitemap returned `HTTP 200`; sitemap index currently lists page sitemap only, so `post-sitemap.xml` returned `404` because native posts remain draft-only.
- Evidence link/path: `ops/apply-ha-giang-loop-planning-guide-post.php`, `ops/verify-ha-giang-loop-planning-guide-post.php`, `ops/verify-ha-giang-loop-planning-guide-post-static.ps1`, WordPress post ID `519`, `/root/vietnamguide-backups/20260727-ha-giang-loop-post`
- Follow-up: open post ID `519` in WordPress Admin, preview desktop/mobile, verify image presentation and Rank Math/social preview, refresh current mountain weather, road-condition, operator, license, insurance, emergency, and image-license notes before publishing, and add any first-hand Ha Giang route notes if available. Strong next Batch 3 candidates are `Hanoi to Sapa Transport` and `Hanoi to Ha Giang Transport` because they support the mountain cluster without linking to unpublished drafts in public body content.

## 2026-07-26 - Vietnam in February Complete Draft

- Date: 2026-07-26
- Area: Native WordPress Post complete draft, Batch 2 month-by-month planning, Vietnam in February seasonal intent, Tet timing, post-Tet restart, north warming slowly, central and southern route value, low-linkout source trail, Admin-first workflow, anti-spam evergreen content
- Command/check: red/green static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-vietnam-in-february-post-static.ps1`; source availability spot checks returned `HTTP 200` for Vietnam.travel weather, Vietnam.travel Tet, Vietnam.travel transport, NCHMF, and WMO source URLs; local content depth check reported `20839` plain-text content characters; full local static sweep passed `50/50` `ops\verify-*-static.ps1` scripts; deployed February scripts to `/usr/local/lsws/vietnamguide.net/html/ops/`; server-side `php -l` for `ops/apply-vietnam-in-february-post.php` and `ops/verify-vietnam-in-february-post.php`; backup of post `495` content, meta, categories, and tags under `/root/vietnamguide-backups/20260726-vietnam-in-february-post`; controlled update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-vietnam-in-february-post.php --allow-root`; runtime verifier for February; Batch 2 verifier; original post editorial verifier; Admin-first verifier; EEAT verifier; cache flush, rewrite flush, transient deletion, LiteSpeed purge; homepage and sitemap HTTP checks.
- Expected result: post `495` should move from a native WordPress Batch 2 draft brief to a complete February article draft without publishing or scheduling, while preserving Admin-first ownership, target publish date, no visible external body anchors, and manual WordPress editorial control.
- Actual outcome: post ID `495` remains `draft` at slug `vietnam-in-february` with title `Vietnam in February: Weather, Tet Timing and Best Routes`. The post now has `vg_editorial_brief_status=complete_draft`, `vg_editorial_batch=batch-2-evergreen-planning`, `vg_content_owner=wp_admin`, `vg_automation_lock=locked`, and `vg_editorial_target_publish_date=2026-08-13`. The article includes a photo-led hero, proof panel, concierge verdict, at-a-glance matrix, photo proof grid, source-diversity table, region-by-region February weather logic, route chooser, Tet timing and post-Tet restart table, booking guidance, packing logic, fragile-plan traps, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported external body href count `0`. Batch 2 verification passed with `12` draft items, `3` complete drafts, and `0` published batch briefs. Original post editorial verification passed with the first `10` original posts still draft/complete-draft only. Admin-first verification passed with `90` locked items and `244` protected meta baselines. EEAT verification passed. Cache purge succeeded. Public homepage and sitemap returned `HTTP 200`.
- Evidence link/path: `ops/apply-vietnam-in-february-post.php`, `ops/verify-vietnam-in-february-post.php`, `ops/verify-vietnam-in-february-post-static.ps1`, WordPress post ID `495`, `/root/vietnamguide-backups/20260726-vietnam-in-february-post`
- Follow-up: open post ID `495` in WordPress Admin, preview desktop/mobile, confirm Rank Math/social preview, verify February/Tet wording, planned Hoi An/Hue route availability, image presentation, source-trail records, and add any first-hand February travel notes if available. Publish manually only after final review. Strong next Batch 2 candidate: `Tet in Vietnam Travel Guide`, because it supports both January and February seasonal pages and reduces repeated holiday caveats across the cluster.

## 2026-07-26 - Vietnam in January Complete Draft

- Date: 2026-07-26
- Area: Native WordPress Post complete draft, Batch 2 month-by-month planning, Vietnam in January seasonal intent, Tet lead-up travel friction, cool north / central coast improving / dry south route logic, low-linkout source trail, Admin-first workflow, anti-spam evergreen content
- Command/check: red/green static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-vietnam-in-january-post-static.ps1`; code-review follow-up hardening for Batch 2 and December placeholder checks; deploy of January scripts plus hardened December and Batch 2 verifiers; server-side `php -l` for `ops/apply-vietnam-in-january-post.php`, `ops/verify-vietnam-in-january-post.php`, `ops/apply-vietnam-in-december-post.php`, `ops/verify-vietnam-in-december-post.php`, and `ops/verify-wordpress-post-editorial-system-batch-2.php`; backup of post `494` content, meta, categories, and tags under `/root/vietnamguide-backups/20260726-vietnam-in-january-post`; controlled update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-vietnam-in-january-post.php --allow-root`; runtime verifiers for January and December; Batch 2 verifier; original post editorial verifier; Admin-first verifier; EEAT verifier; cache flush, rewrite flush, transient deletion, LiteSpeed purge; homepage and sitemap HTTP checks.
- Expected result: post `494` should move from a native WordPress Batch 2 draft brief to a complete January article draft without publishing or scheduling, while preserving Admin-first ownership, target publish date, no visible external body anchors, and manual WordPress editorial control.
- Actual outcome: post ID `494` remains `draft` at slug `vietnam-in-january`. The post now has `vg_editorial_brief_status=complete_draft`, `vg_editorial_batch=batch-2-evergreen-planning`, `vg_content_owner=wp_admin`, `vg_automation_lock=locked`, and `vg_editorial_target_publish_date=2026-08-12`. The article includes a photo-led hero, proof panel, concierge verdict, at-a-glance matrix, photo proof grid, source-diversity table, region-by-region January weather logic, route chooser, Tet lead-up watchouts, booking guidance, packing logic, fragile-plan traps, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported external body href count `0`. Batch 2 verification passed with `12` draft items, `2` complete drafts, and `0` published batch briefs. Original post editorial verification passed with the first `10` original posts still draft/complete-draft only. Admin-first verification passed with `90` locked items and `232` protected meta baselines. EEAT verification passed. Cache purge succeeded. Public homepage and sitemap returned `HTTP 200`.
- Evidence link/path: `ops/apply-vietnam-in-january-post.php`, `ops/verify-vietnam-in-january-post.php`, `ops/verify-vietnam-in-january-post-static.ps1`, WordPress post ID `494`, `/root/vietnamguide-backups/20260726-vietnam-in-january-post`
- Follow-up: open post ID `494` in WordPress Admin, preview desktop/mobile, confirm Rank Math/social preview, verify January and Tet wording, add any first-hand January travel notes if available, then publish manually only after final review. The next Batch 2 candidate is `Vietnam in February` or the fuller `Tet in Vietnam Travel Guide` because they complete the Jan/Feb/Tet seasonal cluster.

## 2026-07-26 - Batch 2 Complete-Draft Guard Hardening

- Date: 2026-07-26
- Area: Native WordPress Post complete draft verification hardening, Batch 2 placeholder checks, Admin-first overwrite safety
- Command/check: static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-vietnam-in-december-post-static.ps1`; Batch 2 static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-wordpress-post-editorial-system-batch-2-static.ps1`; server-side `php -l`; runtime verifier via `wp eval-file ops/verify-vietnam-in-december-post.php --allow-root`; Batch 2 verifier via `wp eval-file ops/verify-wordpress-post-editorial-system-batch-2.php --allow-root`.
- Expected result: complete draft verification should reject leftover brief placeholder sections beyond only `Draft status:`, and complete-draft apply scripts should prevalidate taxonomy terms before mutating post content/meta.
- Actual outcome: hardened `ops/verify-vietnam-in-december-post.php` and `ops/verify-wordpress-post-editorial-system-batch-2.php` to reject leftover `Editorial brief`, `Sources to check`, `Image plan`, and `Publish gate` placeholder sections in complete drafts. Hardened `ops/apply-vietnam-in-december-post.php` to resolve category/tag term IDs before `wp_update_post()` and to check `wp_set_object_terms()` errors. Live December apply was not rerun because post `493` was already `complete_draft`; the Admin-first target-meta guard correctly refused to overwrite it as a brief. Runtime verification passed for post `493`, with external body href count `0`. The same stricter prevalidation pattern was used in `ops/apply-vietnam-in-january-post.php`.
- Evidence link/path: `ops/apply-vietnam-in-december-post.php`, `ops/verify-vietnam-in-december-post.php`, `ops/verify-wordpress-post-editorial-system-batch-2.php`, WordPress post ID `493`
- Follow-up: reuse the stricter placeholder and prevalidation pattern for the remaining Batch 2 complete-draft scripts.

## 2026-07-26 - Vietnam in December Complete Draft

- Date: 2026-07-26
- Area: Native WordPress Post complete draft, Batch 2 month-by-month seasonal content, December weather and route intent, cool north, central-coast risk, south and island route value, Christmas/New Year booking pressure, low-linkout source trail, Admin-first workflow, anti-spam evergreen content
- Command/check: red/green static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-vietnam-in-december-post-static.ps1`; upgraded Batch 2 verifier to allow `brief` and `complete_draft`; batch 2 static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-wordpress-post-editorial-system-batch-2-static.ps1`; full local static sweep across `48` `ops\verify-*-static.ps1` scripts; remote upload to `/usr/local/lsws/vietnamguide.net/html/ops/`; server-side `php -l` for `ops/apply-vietnam-in-december-post.php`, `ops/verify-vietnam-in-december-post.php`, and `ops/verify-wordpress-post-editorial-system-batch-2.php`; backup of post `493` content, meta, categories, and tags under `/root/vietnamguide-backups/20260726-december-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-vietnam-in-december-post.php --allow-root`; live verifier via `wp eval-file ops/verify-vietnam-in-december-post.php --allow-root`; live Batch 2 verifier; original post editorial system verifier; Admin-first verifier; EEAT verifier; WordPress cache flush, rewrite flush, LiteSpeed purge; homepage and sitemap HTTP status checks.
- Expected result: post `493` should move from a native WordPress Batch 2 draft brief to a complete December travel draft without publishing, while preserving target publish date, Admin-first lock, batch metadata, no visible external body anchors, and manual WordPress editorial control.
- Actual outcome: post ID `493` remains `draft` at slug `vietnam-in-december`. The post now has `vg_editorial_brief_status=complete_draft`, `vg_editorial_batch=batch-2-evergreen-planning`, `vg_content_owner=wp_admin`, `vg_automation_lock=locked`, and `vg_editorial_target_publish_date=2026-08-11`. The article includes a photo-led hero, proof panel, concierge verdict, at-a-glance December route matrix, photo proof grid, source-diversity table, regional weather logic, route chooser, early-booking guidance, beach and bay trade-offs, packing notes, fragile-plan traps, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported external body href count `0`. Batch 2 verifier passed with `12` draft items, `1` complete draft, and `0` published batch briefs. Original post editorial verifier passed. Admin-first verification passed with `90` locked items and `220` protected meta baselines. EEAT verification passed. Cache purge succeeded. Homepage and sitemap returned `HTTP/2 200`.
- Evidence link/path: `ops/apply-vietnam-in-december-post.php`, `ops/verify-vietnam-in-december-post.php`, `ops/verify-vietnam-in-december-post-static.ps1`, WordPress post ID `493`, `/root/vietnamguide-backups/20260726-december-post`
- Follow-up: open post ID `493` in WordPress Admin, preview desktop/mobile, confirm Rank Math/social preview, verify December weather wording and image presentation, add any first-hand December notes if available, then publish manually only after final review. Strong next Batch 2 candidate: `Vietnam in January`, because it continues the month-by-month seasonal cluster and introduces Tet-adjacent travel pressure.

## 2026-07-25 - Native WordPress Posts Editorial System Batch 2

- Date: 2026-07-25
- Area: WordPress-native post management, second evergreen brief batch, month-by-month planning, Tet travel, city comparison, heritage decisions, beach planning, cave planning, mountain planning, Admin-first locked drafts, anti-spam evergreen content
- Command/check: local red/green static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-wordpress-post-editorial-system-batch-2-static.ps1`; remote upload via `pscp.exe` to `/usr/local/lsws/vietnamguide.net/html/ops/`; remote `php -l` for `ops/apply-wordpress-post-editorial-system-batch-2.php` and `ops/verify-wordpress-post-editorial-system-batch-2.php`; live apply via `wp eval-file ops/apply-wordpress-post-editorial-system-batch-2.php --allow-root`; live batch verifier via `wp eval-file ops/verify-wordpress-post-editorial-system-batch-2.php --allow-root`; original post editorial system verifier; Admin-first verifier.
- Expected result: create a second set of locked native WordPress draft briefs only, with no publishing or scheduling, and keep the batch separately auditable for future manual WordPress Admin expansion.
- Actual outcome: created `12` new draft briefs with slugs `vietnam-in-december`, `vietnam-in-january`, `vietnam-in-february`, `tet-in-vietnam-travel-guide`, `best-vietnam-cities-for-first-time-visitors`, `hanoi-vs-ho-chi-minh-city`, `hoi-an-ancient-town-guide`, `hue-imperial-city-guide`, `da-nang-beaches-guide`, `mekong-delta-overnight-vs-day-trip`, `phong-nha-travel-guide`, and `sapa-vs-ha-giang`. All remain `draft`, `vg_content_owner=wp_admin`, `vg_automation_lock=locked`, `vg_editorial_brief_status=brief`, and `vg_editorial_batch=batch-2-evergreen-planning`. Batch static verification passed. Remote PHP lint passed. Live batch verifier passed with `12` draft briefs and `0` published batch briefs. Original post editorial system verifier still passed for the first `10` original posts, and Admin-first workflow verification passed with `90` locked items, `22` automation content baselines, and `208` protected meta baselines.
- Evidence link/path: `ops/apply-wordpress-post-editorial-system-batch-2.php`, `ops/verify-wordpress-post-editorial-system-batch-2.php`, `ops/verify-wordpress-post-editorial-system-batch-2-static.ps1`
- Follow-up: open the new draft briefs in WordPress Admin one by one, expand them into complete articles with images/source trails/update logs, and keep them draft-only until the manual publish gate is satisfied.

## 2026-07-25 - Vietnam Rainy Season Flexible Route Complete Draft

- Date: 2026-07-25
- Area: Native WordPress Post complete draft, rainy-season route flexibility intent, region-by-region weather logic, cancellation posture, indoor pivots, transport caution, low-linkout source trail, Admin-first workflow, anti-spam evergreen content
- Command/check: red/green static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-vietnam-rainy-season-flexible-route-post-static.ps1`; full local static sweep across `46` `ops\verify-*-static.ps1` scripts; staged upload to `/tmp/vg-rainy-post-20260725/ops/`; server-side `php -l` for `ops/apply-vietnam-rainy-season-flexible-route.php` and `ops/verify-vietnam-rainy-season-flexible-route-post.php`; backup of post `482` content, meta, categories, and tags under `/root/vietnamguide-backups/20260725-rainy-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file /tmp/vg-rainy-post-20260725/ops/apply-vietnam-rainy-season-flexible-route.php --allow-root`; live verifier via staged and mirrored `ops/verify-vietnam-rainy-season-flexible-route-post.php`; post editorial system verifier, Admin-first verifier, EEAT verifier, cache purge, homepage HTTP check, and sitemap HTTP check.
- Expected result: post `482` should move from a native WordPress draft brief to a complete rainy-season route article without publishing, while preserving the Admin-first lock, target publish date, no visible external body anchors, and manual WordPress editorial control.
- Actual outcome: post ID `482` remains `draft` at slug `vietnam-rainy-season-flexible-route`. The post now has `vg_editorial_brief_status=complete_draft`, `vg_content_owner=wp_admin`, `vg_automation_lock=locked`, and `vg_editorial_target_publish_date=2026-08-10`. The article includes a photo-led hero, proof panel, concierge verdict, at-a-glance matrix, photo proof grid, source-diversity table, region-by-region weather logic, route flexibility and buffer logic, cancellation and refund posture, city-day / indoor pivots, transport and transfer caution, fragile-booking traps, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported external body href count `0`. WordPress post editorial verification passed with `10` draft items, `10` complete drafts, and `0` published editorial briefs. Admin-first verification passed, EEAT verification passed, cache purge succeeded, and public homepage/sitemap HTTP checks returned `200`.
- Evidence link/path: `ops/apply-vietnam-rainy-season-flexible-route.php`, `ops/verify-vietnam-rainy-season-flexible-route-post.php`, WordPress post ID `482`, `/root/vietnamguide-backups/20260725-rainy-post`
- Follow-up: open post ID `482` in WordPress Admin, preview desktop/mobile, confirm Rank Math/social preview, verify rainy-route wording and image presentation, add any first-hand rainy-season notes if available, then publish manually only after final review.

## 2026-07-25 - What to Pack for Vietnam Complete Draft

- Date: 2026-07-25
- Area: Native WordPress Post complete draft, packing-by-region-and-season intent, carry-on vs checked bag decision, temple/modesty notes, low-linkout source trail, Admin-first workflow, anti-spam evergreen content
- Command/check: red/green static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-what-to-pack-vietnam-region-season-post-static.ps1`; full local static sweep across `46` `ops\verify-*-static.ps1` scripts; staged upload to `/tmp/vg-packing-post-20260725/ops/`; server-side `php -l` for `ops/apply-what-to-pack-vietnam-region-season.php` and `ops/verify-what-to-pack-vietnam-region-season-post.php`; backup of post `475` content, meta, categories, and tags under `/root/vietnamguide-backups/20260725-packing-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file /tmp/vg-packing-post-20260725/ops/apply-what-to-pack-vietnam-region-season.php --allow-root`; live verifier via staged and mirrored `ops/verify-what-to-pack-vietnam-region-season-post.php`; post editorial system verifier, Admin-first verifier, EEAT verifier, cache purge, homepage HTTP check, and sitemap HTTP check.
- Expected result: post `475` should move from a native WordPress draft brief to a complete packing article without publishing, while preserving the Admin-first lock, target publish date, no visible external body anchors, and manual WordPress editorial control.
- Actual outcome: post ID `475` remains `draft` at slug `what-to-pack-for-vietnam-region-season`. The post now has `vg_editorial_brief_status=complete_draft`, `vg_content_owner=wp_admin`, `vg_automation_lock=locked`, and `vg_editorial_target_publish_date=2026-08-03`. The article includes a photo-led hero, proof panel, concierge verdict, at-a-glance packing matrix, photo proof grid, source-diversity table, region-season matrix, activity-specific packing logic, temple/modesty notes, carry-on vs checked bag tradeoff, overpack traps, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported external body href count `0`. WordPress post editorial verification passed with `10` draft items, `10` complete drafts, and `0` published editorial briefs. Admin-first verification passed, EEAT verification passed, cache purge succeeded, and public homepage/sitemap HTTP checks returned `200`.
- Evidence link/path: `ops/apply-what-to-pack-vietnam-region-season.php`, `ops/verify-what-to-pack-vietnam-region-season-post.php`, WordPress post ID `475`, `/root/vietnamguide-backups/20260725-packing-post`
- Follow-up: open post ID `475` in WordPress Admin, preview desktop/mobile, confirm Rank Math/social preview, verify packing recommendations and image presentation, add any first-hand packing notes if available, then publish manually only after final review.

## 2026-07-25 - Ha Long Bay Cruise Questions Complete Draft

- Date: 2026-07-25
- Area: Native WordPress Post complete draft, Ha Long Bay cruise-buying intent, port/cabin/weather/route/transfer questions, source trail, low-linkout, Admin-first workflow
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-ha-long-cruise-questions-post-static.ps1`; full local static verifier sweep across all `ops\verify-*-static.ps1` scripts; staged upload to `/tmp/vg-ha-long-cruise-questions-post-20260725-171718/ops/`; server-side `php -l` for `ops/apply-ha-long-cruise-questions-post.php` and `ops/verify-ha-long-cruise-questions-post.php`; backup to `/root/vietnamguide-backups/20260725-171718-ha-long-cruise-questions-post-draft`; live WP-CLI apply via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file /tmp/vg-ha-long-cruise-questions-post-20260725-171718/ops/apply-ha-long-cruise-questions-post.php --allow-root`; live verifier via `/tmp/vg-ha-long-cruise-questions-post-20260725-171718/ops/verify-ha-long-cruise-questions-post.php`.
- Expected result: `/wp-admin/post.php?post=479&action=edit` remains a manual WordPress Admin draft that expands the Ha Long cruise brief into a durable complete draft without visible external links in the body.
- Actual outcome: post ID `479` now holds the complete draft `Ha Long Bay Cruise Questions to Ask Before Booking` at slug `ha-long-bay-cruise-questions-before-booking`. The draft includes hero, proof panel, verdict, at-a-glance matrix, photo proof, source-diversity table, route-map questions, port and transfer checks, cabin and deck checks, weather/cancellation questions, family comfort checks, Bai Tu Long trade-offs, payment-risk questions, red flags, live checks, FAQ, related routes, source trail, and update log. Live verification passed, comments/pings stayed closed, `vg_editorial_brief_status=complete_draft`, `vg_content_owner=wp_admin`, `vg_automation_lock=locked`, `vg_editorial_target_publish_date=2026-08-07`, and external body href count remained `0`.
- Evidence link/path: `wp post get 479 --allow-root`, `/root/vietnamguide-backups/20260725-171718-ha-long-cruise-questions-post-draft`, `https://vietnam.travel/places-to-go/northern-vietnam/ha-long`, `https://vietnam.travel/things-to-do/weather-and-climate-vietnam`, `https://whc.unesco.org/en/list/672/`, `https://halongbay.com.vn/`, `https://catba.com.vn/`, `https://vuonquocgiabaitulong.vn/`
- Follow-up: mirror the three Ha Long-cruise scripts into the live WordPress `ops/` directory, keep the post as a draft until manual WordPress Admin review, and rotate any credentials that were exposed in chat history.

## 2026-07-25 - Ninh Binh Without Rushing Complete Draft

- Date: 2026-07-25
- Area: Native WordPress Post complete draft, Ninh Binh pacing intent, boat/base/transfer decision layer, photo proof, source trail, low-linkout, Admin-first workflow
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-ninh-binh-without-rushing-post-static.ps1`; full local static verifier sweep across all `ops\verify-*-static.ps1` scripts; staged upload to `/tmp/vg-ninh-binh-without-rushing-post-20260725-165619/ops/`; server-side `php -l` for `ops/apply-ninh-binh-without-rushing-post.php` and `ops/verify-ninh-binh-without-rushing-post.php`; backup to `/root/vietnamguide-backups/20260725-165619-ninh-binh-without-rushing-post-draft`; live WP-CLI apply via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file /tmp/vg-ninh-binh-without-rushing-post-20260725-165619/ops/apply-ninh-binh-without-rushing-post.php --allow-root`; live verifier via both staging path and mirrored `ops/verify-ninh-binh-without-rushing-post.php`.
- Expected result: `/wp-admin/post.php?post=478&action=edit` remains a manual WordPress Admin draft that expands the Ninh Binh brief into a durable complete draft without visible external links in the body.
- Actual outcome: post ID `478` now holds the complete draft `Ninh Binh Without Rushing: How to Choose Boat, Base and Transfer` at slug `ninh-binh-without-rushing`. The draft includes hero, proof panel, verdict, at-a-glance matrix, photo proof, source-diversity table, rush diagnosis, boat-choice framework, base-choice framework, transfer-pressure guidance, one-night and two-night plans, cut list, live checks, FAQ, related routes, source trail, and update log. Review hardening tightened the apply script to exact `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1`, exact target brief meta comparisons without trimming, static detection for protocol-relative external anchors, and runtime exact checks for long E-E-A-T/source/related-route metadata. Live verification passed, comments/pings stayed closed, `vg_editorial_brief_status=complete_draft`, `vg_content_owner=wp_admin`, `vg_automation_lock=locked`, `vg_editorial_target_publish_date=2026-08-06`, and external body href count remained `0`.
- Evidence link/path: `wp post get 478 --allow-root`, `/root/vietnamguide-backups/20260725-165619-ninh-binh-without-rushing-post-draft`, `https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh`, `https://vietnam.travel/things-to-do/guide-boat-tours-ninh-binh`, `https://whc.unesco.org/en/list/1438/`, `https://dulichninhbinh.com.vn/en/`, `https://dsvn.vn/`
- Follow-up: keep the post as a draft until manual WordPress Admin review, confirm desktop/mobile preview and Rank Math/social preview, add any first-hand editorial notes if available, and rotate any credentials that were exposed in chat history.

## 2026-07-25 - Hanoi First-Time Visitor Mistakes Complete Draft

- Date: 2026-07-25
- Area: Native WordPress Post complete draft, Hanoi first-time visitor mistakes intent, diagnostic mistake->symptom->correction structure, source trail, low-linkout, Admin-first workflow
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-hanoi-first-time-visitor-mistakes-post-static.ps1`; full local static verifier sweep across all `ops\verify-*-static.ps1` scripts; staged upload to `/tmp/vg-hanoi-mistakes-post-20260725-155103/ops/`; server-side `php -l` for `ops/apply-hanoi-first-time-visitor-mistakes-post.php` and `ops/verify-hanoi-first-time-visitor-mistakes-post.php`; backup to `/root/vietnamguide-backups/20260725-155103-hanoi-mistakes-post-draft`; live WP-CLI apply via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file /tmp/vg-hanoi-mistakes-post-20260725-155103/ops/apply-hanoi-first-time-visitor-mistakes-post.php --allow-root`; live verifier via `wp eval-file /tmp/vg-hanoi-mistakes-post-20260725-155103/ops/verify-hanoi-first-time-visitor-mistakes-post.php --allow-root`.
- Expected result: `/wp-admin/post.php?post=477&action=edit` remains a manual WordPress Admin draft that expands the Hanoi mistakes brief into a durable complete draft without visible external links in the body.
- Actual outcome: post ID `477` now holds the complete draft `Hanoi First-Time Visitor Mistakes to Avoid` at slug `hanoi-first-time-visitor-mistakes`. The draft includes hero, proof panel, verdict, at-a-glance matrix, photo proof, source-diversity note, base-choice correction, arrival overload, day-trip sequencing, route-crowding filter, weather-vs-pacing pivots, correction matrix, live checks, first-night rule, FAQ, related routes, source trail, and update log. The apply script now requires an exact `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1` match and a unique slug/post target with the expected brief metadata before updating. Live verification passed, comments/pings stayed closed, `vg_editorial_brief_status=complete_draft`, `vg_content_owner=wp_admin`, `vg_automation_lock=locked`, `vg_editorial_target_publish_date=2026-08-05`, and external body href count remained `0`.
- Evidence link/path: `wp post get 477 --allow-root`, `/root/vietnamguide-backups/20260725-155103-hanoi-mistakes-post-draft`, `https://vietnam.travel/places-to-go/northern-vietnam/ha-noi`, `https://vietnamairport.vn/en/noi-bai-airport`, `https://www.nchmf.gov.vn/kttv/en-US/1/index.html`, `https://whc.unesco.org/en/list/1328/`
- Follow-up: mirror the three Hanoi-mistakes scripts into the live WordPress `ops/` directory and rotate any credentials that were exposed in chat history.

## 2026-07-25 - Vietnam Airport Arrival Checklist Complete Draft

- Date: 2026-07-25
- Area: Native WordPress Post complete draft, airport-arrival first-hour intent, money/SIM/transport/scam sequence, source-trail rendering, Admin-first workflow
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-vietnam-airport-arrival-checklist-post-static.ps1`; full local static verifier sweep across all `ops\verify-*-static.ps1` scripts; staged upload to `/tmp/vg-airport-arrival-post-20260725-145750/ops/`; server-side `php -l` for `ops/apply-vietnam-airport-arrival-checklist-post.php` and `ops/verify-vietnam-airport-arrival-checklist-post.php`; backup to `/root/vietnamguide-backups/20260725-145750-airport-arrival-post-draft`; live WP-CLI apply via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file /tmp/vg-airport-arrival-post-20260725-145750/ops/apply-vietnam-airport-arrival-checklist-post.php --allow-root`; live verifier via `wp eval-file /tmp/vg-airport-arrival-post-20260725-145750/ops/verify-vietnam-airport-arrival-checklist-post.php --allow-root`; source-note verifier fix after the first live pass exposed an apostrophe-escaping mismatch in the rendered source trail.
- Expected result: `/wp-admin/post.php?post=476&action=edit` remains a manual WordPress Admin draft that expands the airport-arrival brief into a durable complete draft without visible external links in the body.
- Actual outcome: post ID `476` now holds the complete draft `Vietnam Airport Arrival Checklist: Money, SIM, Transport and Scams` at slug `vietnam-airport-arrival-checklist`. The draft includes hero, proof panel, concierge verdict, arrival sequence, do-now-vs-wait matrix, money section, SIM/eSIM section, transport matrix, red-flags section, first-night rules, FAQ, related routes, source trail, and update log. Initial runtime verification failed because the source-trail assertion looked for an apostrophe-containing phrase that was HTML-escaped in rendered output; the verifier was corrected to check the official source URL instead. After that fix, runtime verification passed, the post remained `draft`, comments/pings stayed closed, `vg_editorial_brief_status=complete_draft`, `vg_content_owner=wp_admin`, `vg_automation_lock=locked`, `vg_editorial_target_publish_date=2026-08-04`, and external body href count remained `0`.
- Evidence link/path: `wp post get 476 --allow-root`, `/root/vietnamguide-backups/20260725-145750-airport-arrival-post-draft`, `https://vietnam.travel/things-to-do/travellers-guide-vietnams-airports`, `https://vietnam.travel/things-to-do/currency-and-payments-vietnam`, `https://vietnam.travel/plan-your-trip/transport-within-vietnam`, `https://www.customs.gov.vn/index.jsp?cid=4203&id=55031&pageId=2311`
- Follow-up: mirror the three airport-arrival scripts into the live WordPress `ops/` directory, keep the post as a draft until manual WordPress Admin review, and rotate any credentials that were exposed in chat history.

## 2026-07-13 - Live Deployment Baseline

Source: live deployment verification/handoff facts captured for the
VietnamGuide.net WordPress launch. This record intentionally excludes
passwords, private keys, raw credentials, and other secret material.

### Access and Paths

- VPS host: `66.42.48.146`
- SSH port: `2209`
- SSH auth: key-based root authentication is available locally; no passwords or
  key material are recorded in this repository.
- WordPress path: `/usr/local/lsws/vietnamguide.net/html`
- Site owner/group: `vietnamguidewpnet:vietnamguidewpnet`
- Site URL/Home: `https://vietnamguide.net`

### Stack

| Component | Live value |
| --- | --- |
| Web server | OpenLiteSpeed |
| PHP CLI | 8.2.29 |
| MariaDB client | 11.8.3 |
| WP-CLI | 2.12.0 |
| WordPress core | 7.0.1 |

### Backup

- Backup created: `/root/vietnamguide-backups/20260713-161613`
- Backup contents verified for handoff: `db.sql` and
  `wp-content-theme-plugin.tar.gz`
- Follow-up: configure durable off-server backup storage and run a restore test
  before launch sign-off.

### WordPress Configuration

- Parent theme installed: GeneratePress `3.6.1`
- Active child theme: `vietnamguide-premium`
- Active MU plugin: `vietnamguide-core` `0.1.2`
- Active plugins:
  - `advanced-custom-fields` `6.8.5`
  - `litespeed-cache` `7.8.1`
  - `seo-by-rank-math` `1.0.273`
  - `redirection` `5.9.0`
  - `google-site-kit` `1.182.0`
  - `updraftplus` `1.26.5`
  - `wordfence` `8.2.2`
- Removed default plugins: `hello`, `akismet`
- Removed default content: `Hello world!` post and sample comment
- Default category renamed to `Travel Planning`
- Static front page: Home, ID `5`
- Primary navigation: created
- Foundation pages returning HTTP 200:
  - `/plan/`
  - `/destinations/`
  - `/itineraries/`
  - `/compare/`
  - `/costs/`

### SEO and Crawling

- `robots.txt` public source is a physical file in the webroot.
- `robots.txt` currently references
  `https://vietnamguide.net/sitemap_index.xml`.
- Rank Math sitemap `/sitemap_index.xml` returns HTTP 200.
- Rank Math page sitemap `/page-sitemap.xml` returns HTTP 200.
- WordPress core sitemap `/wp-sitemap.xml` redirects to Rank Math sitemap.
- Default `Hello world!` post was removed and the stale Rank Math sitemap cache
  in `wp-content/uploads/rank-math` was cleared.

### Remaining Launch Gates

- Admin account hardening and credential rotation.
- Wordfence 2FA and firewall configuration through the WordPress UI.
- UpdraftPlus remote storage configuration and restore test.
- Site Kit, GA4, Search Console, and Bing login verification.
- Publish deep SEO content for launch-critical search intents.

## Entry Template

### YYYY-MM-DD - Area

- Date:
- Area:
- Command/check:
- Expected result:
- Actual outcome:
- Evidence link/path:
- Follow-up:

## DNS

### 2026-07-13 - WWW DNS

- Date: 2026-07-13
- Area: DNS
- Command/check: DNS lookup for `www.vietnamguide.net`.
- Expected result: `www` resolves and can be served as an alias of the apex
  production domain.
- Actual outcome: `www.vietnamguide.net` resolves as a CNAME to
  `vietnamguide.net`.
- Evidence link/path: `https://www.vietnamguide.net/`
- Follow-up: keep apex `https://vietnamguide.net` as the canonical URL in
  WordPress and SEO metadata.

## SSL

### 2026-07-13 - WWW HTTPS Canonical Redirect

- Date: 2026-07-13
- Area: SSL
- Command/check: public HTTP checks for `https://www.vietnamguide.net/` and
  `http://www.vietnamguide.net/`.
- Expected result: both URLs complete successfully and land on the canonical
  apex URL.
- Actual outcome: both `www` variants return a final HTTP 200 at
  `https://vietnamguide.net/`.
- Evidence link/path: `https://vietnamguide.net/`
- Follow-up: recheck after any DNS, LiteSpeed virtual host, or certificate
  changes.

## WordPress Health

### 2026-07-13 - WordPress Baseline

- Date: 2026-07-13
- Area: WordPress Health
- Command/check: live WordPress configuration and HTTP checks from the launch
  handoff.
- Expected result: production site has the intended URL, theme, plugin set,
  static front page, navigation, and foundation pages available.
- Actual outcome: `https://vietnamguide.net` is set as Site URL/Home; Home ID
  `5` is the static front page; primary navigation exists; foundation pages
  `/plan/`, `/destinations/`, `/itineraries/`, `/compare/`, and `/costs/`
  return HTTP 200.
- Evidence link/path: `https://vietnamguide.net`
- Follow-up: complete visual QA, security hardening, analytics/search console
  verification, and deep SEO content publication.

## Theme

### 2026-07-13 - Theme and MU Plugin

- Date: 2026-07-13
- Area: Theme
- Command/check: live theme and MU plugin status from WP-CLI/admin handoff.
- Expected result: GeneratePress parent is installed, VietnamGuide child theme
  is active, and the project MU plugin is active.
- Actual outcome: GeneratePress `3.6.1` installed; `vietnamguide-premium` child
  theme active; `vietnamguide-core` `0.1.2` MU plugin active.
- Evidence link/path: `/usr/local/lsws/vietnamguide.net/html`
- Follow-up: complete Wordfence, backup, and analytics UI gates before launch
  sign-off.

### 2026-07-13 - Homepage Visual QA

- Date: 2026-07-13
- Area: Theme
- Command/check: Playwright desktop `1440x1200` and mobile `390x1200`
  checks against `https://vietnamguide.net/`.
- Expected result: homepage has one visible H1, no default page title/sidebar,
  no console errors, no failed requests, no horizontal overflow, and the hero
  image renders behind readable CTA text.
- Actual outcome: HTTP 200 on both viewports; one H1
  `Vietnam for travelers who choose well.`; no sidebar or entry title; WebP
  hero image rendered from the child theme; no console errors, failed requests,
  or horizontal overflow detected. Homepage content now uses the dynamic
  `vietnamguide/homepage-sections` pattern reference.
- Image/license note: hero image is Ha Long Bay, Vietnam by Vyacheslav
  Argenberg, CC BY 4.0, with public attribution link in the hero.
- Link audit: 8 unique internal homepage links checked; 0 HTTP 4xx/5xx
  failures. Draft deep SEO links are routed to published hub pages until the
  deep pages are ready to publish. Final Playwright link QA reported
  `oldDraftHrefCount: 0`.
- Evidence link/path: `.qa/home-desktop-hero-image-top.png` and
  `.qa/home-mobile-hero-image-top.png`
- Follow-up: run PageSpeed after final content/images are published.

### 2026-07-13 - Premium Footer and Navigation

- Date: 2026-07-13
- Area: Theme
- Command/check: child theme deployment, WP-CLI menu/location checks, public
  HTML checks, and footer screenshots.
- Expected result: GeneratePress default footer is replaced by a premium site
  footer with Plan, Explore, and Legal navigation groups; legal links are
  visible on desktop and mobile.
- Actual outcome: `vietnamguide-premium` versioned assets are at `0.1.7`;
  footer element `#vg-footer` renders on public pages; menu locations
  `footer_plan`, `footer_destinations`, and `footer_legal` are assigned; footer
  HTML includes `/privacy-policy/`, `/affiliate-disclosure/`, and the editorial
  trust line. Idempotency rerun kept footer menu counts at Plan `4`,
  Destinations `2`, and Legal `2`.
- Backup created before deployment:
  `/root/vietnamguide-backups/20260713-183448-legal-footer`.
- Review-fixes backup created before the `0.1.7` deployment:
  `/root/vietnamguide-backups/20260713-190222-footer-review-fixes`.
- Evidence link/path: `.qa/cdp-footer-desktop.png` and
  `.qa/cdp-footer-mobile.png`
- Follow-up: extend footer navigation only after new deep guides are published
  and internally linked.

## SEO

### 2026-07-13 - Sitemap and Robots

- Date: 2026-07-13
- Area: SEO
- Command/check: live sitemap and robots checks.
- Expected result: public robots file references a valid XML sitemap.
- Actual outcome: physical webroot `robots.txt` references
  `https://vietnamguide.net/sitemap_index.xml`; Rank Math
  `/sitemap_index.xml` returns HTTP 200; `/page-sitemap.xml` returns HTTP 200;
  `/wp-sitemap.xml` redirects to Rank Math; default `Hello world!` content is
  not present in the active sitemap index.
- Evidence link/path: `https://vietnamguide.net/sitemap_index.xml`
- Follow-up: submit sitemap after Search Console/Bing verification.

### 2026-07-13 - Foundation SEO and Locale

- Date: 2026-07-13
- Area: SEO
- Command/check: WP-CLI option checks and public HTML checks for language,
  Rank Math titles/descriptions, schema, robots rules, and breadcrumbs.
- Expected result: English-first public pages declare English language signals,
  have unique SEO titles/descriptions, use organization-style site schema, and
  keep thin archives out of the index.
- Actual outcome: public HTML now outputs `html lang="en-US"`, `og:locale`
  `en_US`, and Rank Math schema `inLanguage` `en-US`. Rank Math Knowledge Graph
  type is `company`; homepage and public foundation pages have explicit SEO
  titles/descriptions; breadcrumbs are `on`; search pages are noindexed; post
  tag and post format custom robots toggles are `on` with `noindex`; author
  archives are disabled/noindexed.
- Verified public titles:
  - `/`: `Vietnam Travel Guide for International Visitors`
  - `/plan/`: `Vietnam Travel Planning Guide for International Visitors`
  - `/destinations/`: `Vietnam Destinations Guide for International Travelers`
  - `/itineraries/`: `Vietnam Itineraries: 7, 10, 14 Day and Slower Routes`
  - `/compare/`: `Compare Vietnam Destinations, Routes and Trip Styles`
  - `/costs/`: `Vietnam Travel Cost Planning for International Visitors`
  - `/newsletter/`: `VietnamGuide Newsletter for Smarter Vietnam Travel Planning`
  - `/affiliate-disclosure/`: `Affiliate Disclosure`
- Evidence link/path: `https://vietnamguide.net/`
- Follow-up: connect Search Console, GA4/Site Kit, and Bing Webmaster Tools
  through their UI/account flows.

### 2026-07-13 - Foundation Hub Content

- Date: 2026-07-13
- Area: Content
- Command/check: WP-CLI content deployment through
  `ops/apply-foundation-seo-content.php`, followed by public HTTP and text
  checks.
- Expected result: public hub pages are no longer thin placeholders and avoid
  volatile visa/pricing claims until dedicated guides are sourced and reviewed.
- Actual outcome: `/plan/`, `/destinations/`, `/itineraries/`, `/compare/`,
  `/costs/`, `/newsletter/`, and `/affiliate-disclosure/` were expanded with
  evergreen English hub content, internal links to published pages only, and
  page-level SEO metadata. Draft deep guides remain unpublished.
- Post-deploy word counts from public HTML extraction:
  - `/`: 206 words
  - `/plan/`: 306 words
  - `/destinations/`: 277 words
  - `/itineraries/`: 240 words
  - `/compare/`: 221 words
  - `/costs/`: 269 words
  - `/newsletter/`: 128 words
  - `/affiliate-disclosure/`: 153 words
- Backup created before deployment:
  `/root/vietnamguide-backups/20260713-175722-foundation-seo`.
- Follow-up: publish dedicated deep SEO guides only after source review and
  editorial expansion.

### 2026-07-13 - Public Link Audit

- Date: 2026-07-13
- Area: SEO
- Command/check: Node fetch crawl of 8 public pages and all same-origin links
  found in their HTML.
- Expected result: published pages do not link visitors or crawlers to HTTP
  4xx/5xx URLs.
- Actual outcome: crawled 8 public pages, found 45 internal links, and found
  `0` HTTP 4xx/5xx failures. The legacy `xmlrpc.php?rsd` discovery link was
  removed from public head output before the final crawl.
- Evidence link/path: `https://vietnamguide.net/`
- Follow-up: rerun after each new public deep guide is added.

### 2026-07-13 - Public Link Audit Refresh

- Date: 2026-07-13
- Area: SEO
- Command/check: Node fetch crawl after legal/footer review fixes across Home,
  foundation hubs, newsletter, affiliate disclosure, and Privacy Policy.
- Expected result: published pages do not link visitors or crawlers to HTTP
  4xx/5xx URLs.
- Actual outcome: crawled 9 public pages, found 12 unique same-origin links,
  and found `0` HTTP 4xx/5xx failures.
- Evidence link/path: `https://vietnamguide.net/`
- Follow-up: rerun after each new public deep guide is added.

### 2026-07-13 - Privacy Policy SEO Baseline

- Date: 2026-07-13
- Area: SEO
- Command/check: WP-CLI deployment through
  `ops/apply-legal-footer-baseline.php`, followed by public HTTP, metadata, and
  text checks.
- Expected result: Privacy Policy is published, replaces the default WordPress
  suggested-text boilerplate, has basic Rank Math metadata, and is assigned as
  the WordPress privacy page.
- Actual outcome: `/privacy-policy/` returns HTTP 200; page title is
  `Privacy Policy`; meta description is present; public HTML contains
  `Last updated: July 13, 2026` and `contact@vietnamguide.net`; public HTML no
  longer contains `Suggested text`; WordPress option `wp_page_for_privacy_policy`
  is set to page ID `3`. The legal/footer script now stores baseline metadata
  version `v1`; a normal non-force rerun preserved existing Privacy Policy
  content and Rank Math metadata, while still ensuring menus and rewrite rules.
- Evidence link/path: `https://vietnamguide.net/privacy-policy/`
- Follow-up: have final legal wording reviewed before large-scale promotion or
  before enabling comments, user accounts, advanced analytics, or email
  marketing forms.

### 2026-07-13 - EEAT Content System Verification

- Date: 2026-07-13
- Area: SEO
- Command/check: WP-CLI EEAT verifier, public link crawl, public HTML marker
  checks, and Chrome CDP visual QA after trust pages and first deep guide
  deploy.
- Expected result: trust pages are published, Best Time guide has proof/source
  and update modules, public internal links return no 4xx/5xx, and guide pages
  have no horizontal overflow on desktop, tablet, and mobile.
- Actual outcome: Task 12 public checks passed; trust pages and Best Time guide
  returned HTTP 200; public marker checks found proof panel, source trail,
  update log, and concierge verdict; internal link crawl found `0` HTTP 4xx/5xx
  failures across 12 seeded pages and 15 internal links. The `/plan/` decision
  block links only to published hub/guide pages.
- Evidence link/path: `https://vietnamguide.net/plan/best-time-to-visit-vietnam/`
- Follow-up: rerun after each new deep guide.

### 2026-07-14 - Vietnam Travel Cost Guide Publication

- Date: 2026-07-14
- Area: SEO
- Command/check: WP-CLI publish through
  `/tmp/vg-eeat-cost/ops/apply-vietnam-travel-cost-guide.php`, followed by
  `ops/verify-eeat-content.php`, public HTTP checks, HTML marker checks, and
  internal link crawl.
- Expected result: `/costs/vietnam-travel-cost/` is published at the nested
  Costs URL, includes dated budget ranges, proof/source/update modules, and
  exposes no links to unpublished draft guides.
- Actual outcome: page ID `22` is published at
  `https://vietnamguide.net/costs/vietnam-travel-cost/`; public HTTP returns
  200; public HTML contains `USD 30-45/day`, `USD 70-120/day`,
  `USD 180-350+/day`, Editorial proof, Source trail, Update log, and
  `evisa.gov.vn`; public HTML does not link to draft `10 Days` or `SIM/eSIM`
  pages. The Costs hub now links to the guide and no longer says the dedicated
  cost guide is pending. EEAT verifier passed, and the public crawl checked
  7 seeded pages plus 15 unique internal links with `0` failures.
- Evidence link/path: `https://vietnamguide.net/costs/vietnam-travel-cost/`
- Follow-up: submit the URL in Search Console after account verification and
  continue publishing remaining launch-critical deep guides one at a time.

### 2026-07-14 - Vietnam Travel Cost Depth Expansion

- Date: 2026-07-14
- Area: SEO
- Command/check: expanded `ops/verify-eeat-content.php` with new cost-guide
  markers, confirmed the verifier failed on the pre-expansion live content,
  deployed `ops/apply-vietnam-travel-cost-guide.php` with
  `VG_FORCE_COST_GUIDE_REPUBLISH=1`, purged LiteSpeed cache, reran the EEAT
  verifier, public HTML marker checks, and navigational internal-link crawl.
- Expected result: `/costs/vietnam-travel-cost/` remains published at the
  nested Costs URL, adds deeper planning value without public links to draft
  pages, and includes trip-length scenarios, route cost archetypes,
  solo/couple/family budget behavior, hidden-cost traps, budget worksheet, and
  live booking checks.
- Actual outcome: remote PHP lint passed for the publish script and verifier.
  The verifier first failed on the pre-expansion live page for the six new
  markers, then passed after deployment. Public HTML returns HTTP 200 and
  contains `vg-cost-scenario-budgets`, `vg-cost-route-archetypes`,
  `vg-cost-traveler-mix`, `vg-cost-hidden-costs`, `vg-cost-worksheet`,
  `vg-cost-live-checks`, `USD 800-1,400`, `7/14/2026 6:07:17 PM`, Source
  trail, and Update log. Public HTML does not link to draft `10 Days`,
  `SIM/eSIM`, or connectivity pages. Navigational crawl checked 7 seed pages
  and 15 internal navigational URLs with `0` failures.
- Evidence link/path: `https://vietnamguide.net/costs/vietnam-travel-cost/`
- Follow-up: recheck official e-visa fees, exchange rate framing, rail lookup,
  airport transfer guidance, and volatile hotel/tour wording before changing
  the visible update date again.

### 2026-07-15 - 10 Days in Vietnam Itinerary Publication

- Date: 2026-07-15
- Area: SEO
- Command/check: WP-CLI publish through
  `ops/apply-10-days-itinerary-guide.php`, followed by
  `ops/verify-eeat-content.php`, public HTTP checks, HTML marker checks,
  internal link crawl, and code review hardening for author assignment and
  Gutenberg Cover serialization.
- Expected result: `/itineraries/10-days-in-vietnam/` is published under the
  Itineraries hub, includes photo-led hero media, proof panel, source trail,
  update log, concierge verdict, route matrix, day-by-day plan, swap/skip
  decisions, and before-booking checks, with public links only to published
  pages.
- Actual outcome: page ID `19` is published at
  `https://vietnamguide.net/itineraries/10-days-in-vietnam/`; public HTTP
  returns 200; public HTML contains `vg-itinerary-route-matrix`,
  `vg-itinerary-day-plan`, `vg-itinerary-swap-skip`,
  `vg-itinerary-booking-checks`, proof panel, source trail, update log, and an
  HTTPS hero image. The EEAT verifier passed live; the initial broad crawl
  fetched 30 internal URLs from 8 seed pages with `0` failures, and the final
  post-cleanup navigational crawl checked 16 public page URLs from the same
  8 seed pages with `0` failures. Post-review hardening added explicit
  `post_author`, Cover block `url` attributes, inner-container image-credit
  serialization, and truthy validation for
  `vg_eeat_reviewed_guide`.
- Evidence link/path:
  `https://vietnamguide.net/itineraries/10-days-in-vietnam/`
- Follow-up: use this as the itinerary template baseline for 7-day, 14-day,
  family, luxury, and north/central/south route variants; keep volatile entry,
  weather, transport, and cruise logistics source-checked before visible update
  date changes.

### 2026-07-15 - Vietnam E-Visa Guide Publication

- Date: 2026-07-15
- Area: SEO
- Command/check: WP-CLI publish through
  `ops/apply-vietnam-evisa-guide.php`, hub-link refresh through
  `ops/apply-eeat-home-hub-links.php`, red/green
  `ops/verify-eeat-content.php`, public HTTP checks, HTML marker checks,
  public navigational crawl, and source-sensitive copy review against official
  e-visa sources.
- Expected result: `/plan/vietnam-evisa/` is published under the Plan hub,
  includes official-source checks, e-visa basics table, decision table,
  mistake checks, before-apply checklist, proof panel, source trail, update
  log, and concierge verdict, with public links only to published internal
  pages.
- Actual outcome: page ID `13` moved from draft to publish at
  `https://vietnamguide.net/plan/vietnam-evisa/`. The verifier failed before
  publication because the E-Visa guide was still draft, then passed after
  publication. Public HTML returned HTTP 200 and contained
  `vg-evisa-official-basics`, `vg-evisa-decision-table`,
  `vg-evisa-mistake-checks`, `vg-evisa-before-apply`, Editorial proof, Source
  trail, Update log, `thithucdientu.gov.vn`, and the Ministry of Public
  Security public-service source domain. The Plan hub links to
  `/plan/vietnam-evisa/`. Public crawl checked 17 internal page URLs from
  10 seed pages with `0` failures.
- Evidence link/path: `https://vietnamguide.net/plan/vietnam-evisa/`
- Follow-up: review this page at least monthly and immediately after official
  visa notices change; avoid hard-coding border-gate counts unless the live
  official endpoint is rechecked during the same update.

### 2026-07-15 - Vietnam Travel Guide Pillar Publication

- Date: 2026-07-15
- Area: SEO
- Command/check: WP-CLI publish through
  `ops/apply-vietnam-travel-guide.php`, hub-link refresh through
  `ops/apply-eeat-home-hub-links.php`, red/green
  `ops/verify-eeat-content.php`, remote PHP lint, public HTTP checks, public
  marker checks, filtered internal page crawl, Chrome headless visual QA, and
  official-source research review.
- Expected result: `/plan/vietnam-travel-guide/` is published under the Plan
  hub, becomes the main long-term first-trip planning pillar, includes
  planning order, decision map, evergreen-versus-live checks, route lenses,
  official-check checklist, first-trip checklist, proof panel, source trail,
  update log, and concierge verdict, with public links only to published
  internal pages.
- Actual outcome: remote PHP lint passed for
  `/tmp/apply-vietnam-travel-guide.php`, `/tmp/verify-eeat-content.php`, and
  `/tmp/apply-eeat-home-hub-links.php`. The verifier failed before publication
  because `/plan/vietnam-travel-guide/` was missing, then passed after
  publication. Page ID `98` was published at
  `https://vietnamguide.net/plan/vietnam-travel-guide/`; Plan hub was refreshed
  with the Travel Guide note and decision links now place the guide as the
  first planning entry. Public marker checks returned HTTP 200 for the Travel
  Guide and Plan hub, found all 13 required markers/source anchors, and found
  the Plan hub Travel Guide link. Filtered public crawl checked 18 internal
  HTML page URLs from 10 seeds with `0` failures. Post-review hardening changed
  the EEAT verifier to inspect rendered content for proof/source/update module
  classes and added the alternate official e-visa domain
  `thithucdientu.gov.vn` to the Travel Guide required anchors; the hardened
  verifier was linted on the VPS and passed via WP-CLI after the change.
- Evidence link/path: `https://vietnamguide.net/plan/vietnam-travel-guide/`
- Follow-up: use this as the site pillar for future destination, comparison,
  and route-variant guides; avoid freezing volatile visa, transport, weather,
  exchange-rate, and holiday details into evergreen copy unless the visible
  update date is refreshed from official/live sources.

### 2026-07-15 - North/Central/South Vietnam Comparison Publication

- Date: 2026-07-15
- Area: SEO
- Command/check: WP-CLI publish through
  `ops/apply-north-central-south-comparison-guide.php`, hub-link refresh through
  `ops/apply-eeat-home-hub-links.php`, red/green
  `ops/verify-eeat-content.php`, remote PHP lint, LiteSpeed cache purge, public
  HTTP checks, public marker checks, filtered internal HTML crawl, Chrome
  headless visual QA, and official-source research review.
- Expected result: `/compare/north-central-south-vietnam/` is published under
  the Compare hub, includes a regional verdict matrix, heritage anchors,
  first-trip fit matrix, season/route reality checks, skip logic,
  official-source checklist, proof panel, source trail, update log, and
  concierge verdict, with public links only to published internal pages.
- Actual outcome: remote PHP lint passed for
  `/tmp/apply-north-central-south-comparison-guide.php`,
  `/tmp/verify-eeat-content.php`, and `/tmp/apply-eeat-home-hub-links.php`.
  The verifier failed before publication because
  `/compare/north-central-south-vietnam/` was missing, then passed after
  publication. Page ID `104` was published at
  `https://vietnamguide.net/compare/north-central-south-vietnam/`; the Compare
  hub was refreshed with the region comparison note, and hub decision links now
  point to the published comparison guide. Public marker checks returned HTTP
  200 for the guide and Compare hub, found all required
  `vg-region-compare-*` markers, official source anchors, UNESCO anchors, and
  image credit. Filtered public crawl checked 20 internal HTML page URLs from
  10 seeds with `0` failures. WordPress object cache and LiteSpeed cache were
  purged after publication.
- Evidence link/path:
  `https://vietnamguide.net/compare/north-central-south-vietnam/`
- Follow-up: use this comparison as the first regional decision pillar for
  future North, Central, South, beach, culture, family, and luxury route
  variants; keep weather, transport, UNESCO, flight, and entry claims tied to
  official/live source checks before visible update dates change.

### 2026-07-15 - Best Places to Visit in Vietnam Publication

- Date: 2026-07-15
- Area: SEO
- Command/check: red/green `ops/verify-eeat-content.php`, WP-CLI publish
  through `ops/apply-best-places-destination-guide.php`, hub-link refresh
  through `ops/apply-eeat-home-hub-links.php`, remote PHP lint, WordPress and
  LiteSpeed cache purge, public HTTP checks, public marker checks, filtered
  internal HTML crawl, Chrome headless visual QA, and official-source
  destination review.
- Expected result: `/destinations/best-places-to-visit-vietnam/` is published
  under the Destinations hub, includes a first-trip shortlist, destination
  verdict matrix, route-fit matrix, heritage/nature anchors, skip logic,
  official-source checklist, proof panel, source trail, update log, and
  concierge verdict, with public links only to published internal pages.
- Actual outcome: remote PHP lint passed for
  `/tmp/apply-best-places-destination-guide.php`,
  `/tmp/verify-eeat-content.php`, and `/tmp/apply-eeat-home-hub-links.php`.
  The hardened verifier failed before publication because
  `/destinations/best-places-to-visit-vietnam/` was missing, then passed after
  publication. Page ID `110` was published at
  `https://vietnamguide.net/destinations/best-places-to-visit-vietnam/`; the
  Destinations hub was refreshed with the Best Places note, and hub decision
  links now include the published destination guide. Public marker checks
  returned HTTP 200 for the guide and Destinations hub, found all required
  `vg-destination-guide-*` markers, Vietnam.travel anchors, UNESCO anchors, and
  image credit. Filtered public crawl checked 21 internal HTML page URLs from
  10 seeds with `0` failures. WordPress object cache and LiteSpeed cache were
  purged after publication.
- Evidence link/path:
  `https://vietnamguide.net/destinations/best-places-to-visit-vietnam/`
- Follow-up: use this destination pillar for future Hanoi, Hoi An, Da Nang,
  Hue, Ninh Binh, Ha Long/Lan Ha, Ho Chi Minh City, Mekong, Phu Quoc,
  Phong Nha, family, luxury, and beach-route variants; keep weather,
  transport, UNESCO, operator, and flight claims live-checked before changing
  visible update dates.

### 2026-07-15 - Rank Math Deep Guide Sitemap Refresh

- Date: 2026-07-15
- Area: SEO
- Command/check: public `/page-sitemap.xml` inspection after review found that
  Rank Math was serving stale cached sitemap XML from
  `wp-content/uploads/rank-math`; backed up the cache files, removed the stale
  XML cache, purged WordPress and LiteSpeed cache, requested the public sitemap
  again, and checked current deep guide URL inclusion.
- Expected result: Rank Math page sitemap returns HTTP 200 and includes all
  published deep guides, not just the original foundation pages.
- Actual outcome: `/page-sitemap.xml` regenerated from length `1671` to `4117`
  bytes and now includes `/plan/best-time-to-visit-vietnam/`,
  `/costs/vietnam-travel-cost/`, `/itineraries/10-days-in-vietnam/`,
  `/plan/vietnam-evisa/`, `/plan/vietnam-travel-guide/`,
  `/compare/north-central-south-vietnam/`, and
  `/destinations/best-places-to-visit-vietnam/`. The Best Places guide remains
  public `200`, canonical, and indexable after the sitemap refresh.
- Evidence link/path: `https://vietnamguide.net/page-sitemap.xml`
- Follow-up: after each new deep guide, verify sitemap inclusion rather than
  only sitemap endpoint availability; if missing, check Rank Math XML cache in
  `wp-content/uploads/rank-math`.

### 2026-07-13 - XML-RPC Hardening

- Date: 2026-07-13
- Area: Security
- Command/check: `POST https://vietnamguide.net/xmlrpc.php` with
  `system.listMethods` XML payload.
- Expected result: XML-RPC is blocked for launch unless a future integration
  explicitly requires it.
- Actual outcome: HTTP 403 Forbidden, zero response body.
- Evidence link/path: `https://vietnamguide.net/xmlrpc.php`
- Follow-up: keep disabled unless a vetted integration requires XML-RPC.

### 2026-07-13 - Author and REST User Hardening

- Date: 2026-07-13
- Area: Security
- Command/check: public author enumeration and anonymous REST users endpoint
  checks.
- Expected result: anonymous visitors cannot enumerate the administrator user
  through public author redirects or the REST users listing.
- Actual outcome: `https://vietnamguide.net/?author=1` returns HTTP 404;
  `https://vietnamguide.net/wp-json/wp/v2/users` returns HTTP 404; public head
  output no longer includes RSD or WLW manifest discovery links; comments and
  pings are closed on published pages and defaults are closed.
- Evidence link/path: `https://vietnamguide.net/`
- Follow-up: complete Wordfence 2FA/firewall and admin credential rotation in
  the WordPress UI.

## Performance

### 2026-07-13 - Chrome CDP Visual QA

- Date: 2026-07-13
- Area: Performance
- Command/check: Chrome DevTools Protocol screenshots and DOM metrics for Home,
  Plan, and Privacy Policy at desktop `1440x1200` and mobile `390x1200`, plus
  tablet `820x1200` coverage for Home, foundation hubs, and Privacy Policy.
- Expected result: no horizontal overflow, one visible H1 per tested page, and
  no obvious footer, text, or legal-page layout breakage on the tested
  viewports.
- Actual outcome: Home, Plan, and Privacy Policy returned no horizontal
  overflow in the CDP metrics; each tested page had one visible H1; footer
  scroll screenshots reported `footerVisible: true`; manual screenshot review
  found the footer readable on desktop, mobile, and tablet. Tablet QA covered
  7 pages with `failures: []`; footer grid columns measured
  `365.5px 365.5px` at `820px`.
- Evidence link/path: `.qa/cdp-home-desktop.png`,
  `.qa/cdp-home-mobile.png`, `.qa/cdp-plan-desktop.png`,
  `.qa/cdp-plan-mobile.png`, `.qa/cdp-privacy-desktop.png`,
  `.qa/cdp-privacy-mobile.png`, `.qa/cdp-footer-desktop.png`, and
  `.qa/cdp-footer-mobile.png`; tablet results are recorded in
  `.qa/cdp-tablet-foundation-results.json` and
  `.qa/cdp-home-tablet-footer.png`.
- Follow-up: run Lighthouse/PageSpeed after analytics, caching, and final
  launch content are settled.

## Backups and Recovery

### 2026-07-13 - Legal/Footer Deployment Backup

- Date: 2026-07-13
- Area: Backups and Recovery
- Command/check: direct VPS file listing for
  `/root/vietnamguide-backups/20260713-183448-legal-footer`.
- Expected result: deployment backup exists before legal/footer changes and
  contains the database plus the touched child theme files.
- Actual outcome: backup directory exists and contains `db.sql`,
  `files/functions.php`, and `files/vietnamguide-premium.css`.
- Evidence link/path:
  `/root/vietnamguide-backups/20260713-183448-legal-footer`
- Follow-up: still configure durable off-server backup storage and run a
  restore test before launch sign-off.

### 2026-07-13 - Footer Review-Fixes Deployment Backup

- Date: 2026-07-13
- Area: Backups and Recovery
- Command/check: direct VPS backup creation before deploying the review fixes
  for footer tablet layout, footer fallback behavior, and legal script guard.
- Expected result: deployment backup exists and contains the database plus the
  touched child theme files.
- Actual outcome: backup directory exists and contains `db.sql`,
  `files/functions.php`, and `files/vietnamguide-premium.css`.
- Evidence link/path:
  `/root/vietnamguide-backups/20260713-190222-footer-review-fixes`
- Follow-up: still configure durable off-server backup storage and run a
  restore test before launch sign-off.

### 2026-07-14 - Travel Cost Guide Deployment Backup

- Date: 2026-07-14
- Area: Backups and Recovery
- Command/check: direct VPS backup creation before publishing the Vietnam
  Travel Cost guide.
- Expected result: deployment backup exists before the cost guide publication
  and contains the database plus pre-change JSON snapshots for the cost guide
  draft and Costs hub.
- Actual outcome: backup directory exists at
  `/root/vietnamguide-backups/20260714-175655-travel-cost-guide`; `db.sql` is
  present at 378083 bytes; `post-22-before.json` and `post-10-before.json`
  are present.
- Evidence link/path:
  `/root/vietnamguide-backups/20260714-175655-travel-cost-guide`
- Follow-up: still configure durable off-server backup storage and run a
  restore test before launch sign-off.

### 2026-07-14 - Travel Cost Depth Expansion Backup

- Date: 2026-07-14
- Area: Backups and Recovery
- Command/check: direct VPS backup creation before overwriting the live Vietnam
  Travel Cost guide with deeper content sections.
- Expected result: deployment backup exists before the depth expansion and
  contains the database plus pre-change JSON snapshots for the cost guide and
  Costs hub.
- Actual outcome: backup directory exists at
  `/root/vietnamguide-backups/20260714-182210-cost-guide-depth-expansion`;
  `db.sql` is present at 425079 bytes; `post-22-before.json` and
  `post-10-before.json` are present.
- Evidence link/path:
  `/root/vietnamguide-backups/20260714-182210-cost-guide-depth-expansion`
- Follow-up: still configure durable off-server backup storage and run a
  restore test before launch sign-off.

### 2026-07-15 - 10 Days Itinerary Deployment Backup

- Date: 2026-07-15
- Area: Backups and Recovery
- Command/check: direct VPS backup creation before publishing the 10 Days in
  Vietnam itinerary guide and refreshing hub decision links.
- Expected result: deployment backup exists before the itinerary publication
  and contains the database plus pre-change snapshots for the itinerary page
  and related hub pages.
- Actual outcome: backup directory exists at
  `/root/vietnamguide-backups/20260715-154344-10-day-itinerary-guide`; the
  deployment completed after the backup and the guide remains publicly
  reachable.
- Evidence link/path:
  `/root/vietnamguide-backups/20260715-154344-10-day-itinerary-guide`
- Follow-up: still configure durable off-server backup storage and run a
  restore test before launch sign-off.

### 2026-07-15 - Vietnam E-Visa Deployment Backup

- Date: 2026-07-15
- Area: Backups and Recovery
- Command/check: direct VPS backup creation before publishing the Vietnam
  E-Visa guide and refreshing Plan hub decision links.
- Expected result: deployment backup exists before the E-Visa publication and
  contains the database plus a pre-change JSON snapshot for the Plan hub.
- Actual outcome: backup directory exists at
  `/root/vietnamguide-backups/20260715-165158-evisa-guide`; `db.sql` and
  `post-plan-before.json` are present. The database export contains the
  pre-publication draft state; no separate `post-evisa-before.json` file was
  present in the backup listing.
- Evidence link/path:
  `/root/vietnamguide-backups/20260715-165158-evisa-guide`
- Follow-up: still configure durable off-server backup storage and run a
  restore test before launch sign-off.

### 2026-07-15 - Vietnam Travel Guide Pillar Deployment Backup

- Date: 2026-07-15
- Area: Backups and Recovery
- Command/check: direct VPS backup creation before publishing the Vietnam
  Travel Guide pillar and refreshing Plan hub/decision links.
- Expected result: deployment backup exists before the Travel Guide
  publication and contains the database plus a pre-change JSON snapshot for
  the Plan hub, with any existing Travel Guide page snapshot included if
  present.
- Actual outcome: backup directory exists at
  `/root/vietnamguide-backups/20260715-173330-travel-guide`; `db.sql` and
  `post-plan-before.json` are present. No existing Travel Guide page snapshot
  was present because the page did not exist before publication.
- Evidence link/path:
  `/root/vietnamguide-backups/20260715-173330-travel-guide`
- Follow-up: still configure durable off-server backup storage and run a
  restore test before launch sign-off.

### 2026-07-15 - North/Central/South Comparison Deployment Backup

- Date: 2026-07-15
- Area: Backups and Recovery
- Command/check: direct VPS backup creation before publishing the
  North/Central/South Vietnam comparison guide and refreshing Compare hub /
  decision links.
- Expected result: deployment backup exists before the comparison publication
  and contains the database plus a pre-change JSON snapshot for the Compare
  hub, with any existing comparison page snapshot included if present.
- Actual outcome: backup directory exists at
  `/root/vietnamguide-backups/20260715-183658-region-comparison`; `db.sql` and
  `post-compare-before.json` are present. No existing comparison page snapshot
  was present because the page did not exist before publication.
- Evidence link/path:
  `/root/vietnamguide-backups/20260715-183658-region-comparison`
- Follow-up: still configure durable off-server backup storage and run a
  restore test before launch sign-off.

### 2026-07-15 - Best Places Destination Guide Deployment Backup

- Date: 2026-07-15
- Area: Backups and Recovery
- Command/check: direct VPS backup creation before publishing the Best Places
  to Visit in Vietnam destination guide and refreshing Destinations hub /
  decision links.
- Expected result: deployment backup exists before the destination guide
  publication and contains the database plus a pre-change JSON snapshot for the
  Destinations hub, with any existing destination guide snapshot included if
  present.
- Actual outcome: backup directory exists at
  `/root/vietnamguide-backups/20260715-223300-best-places-guide`; `db.sql` and
  `post-destinations-before.json` are present. No existing Best Places page
  snapshot was present because the page did not exist before publication.
- Evidence link/path:
  `/root/vietnamguide-backups/20260715-223300-best-places-guide`
- Follow-up: still configure durable off-server backup storage and run a
  restore test before launch sign-off.

### 2026-07-15 - Best Places Review-Fixes Backup

- Date: 2026-07-15
- Area: Backups and Recovery
- Command/check: direct VPS backup creation before applying review fixes for
  the Best Places guide source-label wording and stale Rank Math sitemap XML.
- Expected result: backup exists before the review fixes and contains the
  database, a pre-fix JSON snapshot for the Best Places page, and the old Rank
  Math sitemap XML cache files.
- Actual outcome: backup directory exists at
  `/root/vietnamguide-backups/20260715-230430-best-places-review-fixes`;
  `db.sql`, `post-best-places-before.json`, and the previous Rank Math XML
  cache files are present. The live Source trail now labels the UNESCO list 951
  source as `Phong Nha-Ke Bang National Park and Hin Nam No National Park`, and
  the exact older source label is no longer present in public HTML.
- Evidence link/path:
  `/root/vietnamguide-backups/20260715-230430-best-places-review-fixes`
- Follow-up: still configure durable off-server backup storage and run a
  restore test before launch sign-off.

## Accessibility

### 2026-07-13 - Foundation Hub QA Note

- Date: 2026-07-13
- Area: Accessibility
- Command/check: automated HTTP, metadata, link, and PHP syntax checks after
  foundation hub deployment.
- Expected result: no public URL failures, no internal broken links, no PHP
  syntax errors, and no obvious SEO language mismatch.
- Actual outcome: public foundation URLs and sitemaps return HTTP 200; PHP lint
  passes for `vietnamguide-core.php`, theme `functions.php`,
  `recommendation-row.php`, and `ops/apply-foundation-seo-content.php`; internal
  link crawl returns 0 failures; language signals are English. Browser screenshot
  automation was not rerun in this earlier pass because the local bundled
  Playwright package was missing `playwright-core`; the later Chrome CDP visual
  QA entry records the completed screenshot rerun.
- Follow-up: keep running visual screenshot QA after major layout, content, or
  navigation changes.

### 2026-07-13 - Legal/Footer Visual Accessibility Note

- Date: 2026-07-13
- Area: Accessibility
- Command/check: visual review of Chrome CDP screenshots for the footer and
  Privacy Policy at desktop `1440x1200` and mobile `390x1200`.
- Expected result: legal links are visible, footer groups remain readable, and
  Privacy Policy text does not overlap or cause horizontal scrolling.
- Actual outcome: footer links and the editorial trust line are readable on
  desktop and mobile; Privacy Policy headings and paragraph text reflow without
  overlap or horizontal overflow on the tested mobile viewport.
- Evidence link/path: `.qa/cdp-footer-desktop.png`,
  `.qa/cdp-footer-mobile.png`, `.qa/cdp-privacy-desktop.png`, and
  `.qa/cdp-privacy-mobile.png`
- Follow-up: retest if typography scale, footer menu volume, or legal content
  length changes.

### 2026-07-13 - EEAT Guide Visual QA

- Date: 2026-07-13
- Area: Accessibility
- Command/check: Chrome CDP screenshots and DOM metrics for Best Time guide and
  trust pages at desktop, tablet, and mobile widths.
- Expected result: one visible H1, no horizontal overflow, readable guide hero,
  visible proof/source/update modules, and readable footer links.
- Actual outcome: Chrome CDP visual QA passed at 1440x1200, 820x1200,
  and 390x1200 for the Best Time guide and trust policy pages; no horizontal
  overflow, text overlap, missing H1, or missing proof/source/update modules
  found during screenshot inspection.
- Evidence link/path: `.qa/cdp-best-time-desktop.png`,
  `.qa/cdp-best-time-tablet.png`, `.qa/cdp-best-time-mobile.png`
- Follow-up: rerun after each new guide template or media layout change.

### 2026-07-14 - Vietnam Travel Cost Visual QA

- Date: 2026-07-14
- Area: Accessibility
- Command/check: Chrome CDP screenshots and DOM metrics for the Vietnam Travel
  Cost guide and Costs hub at desktop `1440x1200`, tablet `820x1200`, and
  mobile `390x1200`.
- Expected result: one visible H1, no horizontal overflow, readable guide hero,
  visible proof/source/update modules on the guide, readable footer links, and
  no obvious text overlap.
- Actual outcome: CDP visual QA passed `6/6` page/viewport combinations with
  `0` failures. Manual screenshot review found the desktop and mobile hero
  readable, the mobile proof panel readable, and source URLs wrapping inside
  the Source trail module.
- Evidence link/path: `.qa/cdp-cost-guide-desktop.png`,
  `.qa/cdp-cost-guide-mobile.png`, `.qa/cdp-cost-guide-proof-mobile.png`,
  `.qa/cdp-cost-guide-source-update-mobile.png`, and
  `.qa/cdp-cost-guide-visual-qa-results.json`
- Follow-up: rerun after any budget-table layout, typography, or guide-template
  CSS changes.

### 2026-07-14 - Vietnam Travel Cost Depth Expansion Visual QA

- Date: 2026-07-14
- Area: Accessibility
- Command/check: Chrome CDP visual QA rerun for the expanded Vietnam Travel
  Cost guide and Costs hub at desktop `1440x1200`, tablet `820x1200`, and
  mobile `390x1200`, plus a mobile DOM module-width check for all cost tables
  and checklists.
- Expected result: one visible H1, no horizontal overflow, readable guide hero,
  visible proof/source/update modules on the guide, readable footer links, no
  obvious text overlap, and no self-overflow inside the expanded cost modules
  at `390px` width.
- Actual outcome: CDP visual QA passed `6/6` page/viewport combinations with
  `0` failures. The mobile module-width check found
  `.vg-cost-budget-ranges`, `.vg-cost-scenario-budgets`,
  `.vg-cost-route-archetypes`, `.vg-cost-traveler-mix`,
  `.vg-cost-hidden-costs`, `.vg-cost-worksheet`, and
  `.vg-cost-live-checks`, each with `overflowsSelf: false` at `390px`.
- Evidence link/path: `.qa/cdp-cost-guide-desktop.png`,
  `.qa/cdp-cost-guide-tablet.png`, `.qa/cdp-cost-guide-mobile.png`,
  `.qa/cdp-cost-guide-proof-mobile.png`,
  `.qa/cdp-cost-guide-source-update-mobile.png`, and
  `.qa/cdp-cost-guide-visual-qa-results.json`
- Follow-up: rerun after any additional long-table, cost-calculator, or
  guide-template changes.

### 2026-07-15 - 10 Days Itinerary Visual QA

- Date: 2026-07-15
- Area: Accessibility
- Command/check: Chrome headless visual/layout QA for the 10 Days in Vietnam
  itinerary guide at desktop `1440x1200` and mobile `390x1400`.
- Expected result: one visible H1, no horizontal overflow, photo hero image
  loads over HTTPS, route matrix and itinerary decision modules render on
  desktop and mobile, and the mobile route matrix does not overflow.
- Actual outcome: desktop and mobile checks passed. The guide has one visible
  H1, no horizontal overflow, a loaded HTTPS hero image, all four required
  itinerary markers, and the mobile route matrix displays as block layout.
- Evidence link/path:
  `C:\Users\NCTaam\AppData\Local\Temp\vg-10day-desktop.png` and
  `C:\Users\NCTaam\AppData\Local\Temp\vg-10day-mobile.png`
- Follow-up: rerun after guide-template CSS changes, additional photo-led hero
  variants, or any itinerary module that adds long table labels.

### 2026-07-15 - Vietnam E-Visa Visual QA

- Date: 2026-07-15
- Area: Accessibility
- Command/check: Chrome headless visual/layout QA for the Vietnam E-Visa guide
  at desktop `1440x1200` and mobile `390x1400`, plus screenshot inspection of
  the mobile hero and concierge verdict.
- Expected result: one visible H1, no horizontal overflow, photo hero image
  loads over HTTPS, e-visa tables/checklist render on desktop and mobile, and
  the mobile basics table does not overflow.
- Actual outcome: desktop and mobile checks passed. The guide has one visible
  H1, no horizontal overflow, a loaded HTTPS hero image, all four required
  e-visa markers, and the mobile basics table displays as block layout.
  Screenshot inspection found the mobile hero and first verdict section
  readable without obvious overlap.
- Evidence link/path:
  `C:\Users\NCTaam\AppData\Local\Temp\vg-evisa-desktop.png` and
  `C:\Users\NCTaam\AppData\Local\Temp\vg-evisa-mobile.png`
- Follow-up: rerun after visa-table layout changes, source-list expansion, or
  any new regulation-sensitive module with long official URLs.

### 2026-07-15 - Vietnam Travel Guide Pillar Visual QA

- Date: 2026-07-15
- Area: Accessibility
- Command/check: Chrome headless/CDP visual and DOM QA for the Vietnam Travel
  Guide pillar at desktop `1440x1300` and mobile `390x950`, plus filtered
  internal HTML crawl and public marker checks after LiteSpeed cache purge.
- Expected result: one visible H1, no horizontal overflow, photo hero image
  loads over HTTPS, all Travel Guide decision modules render on desktop and
  mobile, and mobile decision tables display as block layout.
- Actual outcome: desktop and mobile checks passed. The guide has one visible
  H1 `Vietnam Travel Guide`, no horizontal overflow, a loaded HTTPS hero image,
  all required Travel Guide markers, and mobile decision tables displaying as
  block layout. Public marker checks found 13 required markers/source anchors
  with `0` failures, and filtered crawl checked 18 internal HTML page URLs from
  10 seeds with `0` failures.
- Evidence link/path:
  `C:\Users\NCTaam\AppData\Local\Temp\vg-qa-a02741894a644ec78fccb864bd1324c4\desktop-travel-guide.png`
  and
  `C:\Users\NCTaam\AppData\Local\Temp\vg-qa-a02741894a644ec78fccb864bd1324c4\mobile-travel-guide.png`
- Follow-up: rerun after guide-template CSS changes, source-list expansion,
  additional photo-led hero variants, or any new comparison/calculator module
  with long table labels.

### 2026-07-15 - North/Central/South Comparison Visual QA

- Date: 2026-07-15
- Area: Accessibility
- Command/check: Chrome headless/CDP visual and DOM QA for the North vs Central
  vs South Vietnam comparison guide at desktop `1440x1300` and mobile
  `390x1200`, plus screenshot inspection of the mobile hero, verdict matrix,
  and Source trail.
- Expected result: one visible H1, no horizontal overflow, photo hero image
  loads over HTTPS, all comparison decision modules render on desktop and
  mobile, official source anchors are present, and mobile decision tables
  display as block layout without viewport overflow.
- Actual outcome: desktop and mobile checks passed. The guide has one visible
  H1 `North vs Central vs South Vietnam`, no horizontal overflow, a loaded
  HTTPS hero image, all required region comparison markers, official source
  anchors, UNESCO anchors, and image credit. Mobile decision tables for the
  verdict matrix, first-trip fit matrix, and season/route table display as
  `block`, remain within the viewport, and do not self-overflow. Screenshot
  inspection found the mobile hero, verdict matrix, and Source trail readable.
- Evidence link/path:
  `C:\Users\NCTaam\AppData\Local\Temp\vg-region-qa-1eb56f4c94384a6fb40f8d498b3dd42d\desktop-region-comparison.png`
  and
  `C:\Users\NCTaam\AppData\Local\Temp\vg-region-qa-1eb56f4c94384a6fb40f8d498b3dd42d\mobile-region-comparison.png`
- Follow-up: rerun after comparison-template CSS changes, source-list
  expansion, additional photo-led hero variants, or any new calculator/planning
  module with long table labels.

### 2026-07-15 - Best Places Destination Guide Visual QA

- Date: 2026-07-15
- Area: Accessibility
- Command/check: Chrome headless/CDP visual and DOM QA for the Best Places to
  Visit in Vietnam destination guide at desktop `1440x1300` and mobile
  `390x1200`, plus screenshot inspection of the mobile hero, verdict matrix,
  and Source trail.
- Expected result: one visible H1, no horizontal overflow, photo hero image
  loads over HTTPS, all destination decision modules render on desktop and
  mobile, official source anchors are present, and mobile decision tables
  display as block layout without viewport overflow or self-overflow.
- Actual outcome: desktop and mobile checks passed. The guide has one visible
  H1 `Best Places to Visit in Vietnam`, no horizontal overflow, a loaded HTTPS
  hero image, all required destination guide markers, Vietnam.travel anchors,
  UNESCO anchors, and image credit. Mobile decision tables for the verdict
  matrix, route-fit matrix, heritage/nature matrix, and skip-logic table
  display as `block`, remain within the viewport, and do not self-overflow.
  Screenshot inspection found the mobile hero, verdict matrix, and Source
  trail readable.
- Evidence link/path:
  `C:\Users\NCTaam\AppData\Local\Temp\vg-destination-qa-06a990e1af8a43afa81da48bc25fe358\desktop-best-places.png`
  and
  `C:\Users\NCTaam\AppData\Local\Temp\vg-destination-qa-06a990e1af8a43afa81da48bc25fe358\mobile-best-places.png`
- Follow-up: rerun after destination-template CSS changes, source-list
  expansion, additional photo-led hero variants, or any new destination
  comparison/planning module with long table labels.

### 2026-07-15 - 14 Days in Vietnam Itinerary Publication

- Date: 2026-07-15
- Area: SEO
- Command/check: WP-CLI publish through
  `ops/apply-14-days-itinerary-guide.php`, hub-link refresh through
  `ops/apply-eeat-home-hub-links.php`, red/green
  `ops/verify-eeat-content.php`, public HTTP marker checks, Rank Math sitemap
  refresh, and internal HTML crawl.
- Expected result: `/itineraries/14-days-in-vietnam/` is published under the
  Itineraries hub, includes route-shape framework, pacing map, extension
  matrix, slowdown rules, booking sequence, proof panel, source trail, update
  log, and concierge verdict, with public links only to published pages.
- Actual outcome: page ID `20` is published at
  `https://vietnamguide.net/itineraries/14-days-in-vietnam/`. Remote PHP lint
  passed for the publisher, hub-link refresher, and EEAT verifier. The verifier
  failed before publication because the 14 Days guide was draft, then passed
  after publication and cache purge. Public HTML returned HTTP 200 and contained
  all required 14-day markers plus `Vietnam.travel`, `whc.unesco.org`,
  `dsvn.vn`, `evisa.gov.vn`, and `Ha Long Bay - Cat Ba Archipelago`. Rank Math
  `/page-sitemap.xml` included all eight current deep guides, including the
  new 14-day URL, and the Itineraries hub linked to the guide. Public crawl
  checked 10 seed pages and 10 internal HTML URLs with `0` failures.
- Evidence link/path:
  `https://vietnamguide.net/itineraries/14-days-in-vietnam/`
- Follow-up: use this as the two-week itinerary baseline before publishing
  family, luxury, north-only, or central-slow variants; keep eSIM, safety, and
  airport-transfer links unlinked until those guides are reviewed and live.

### 2026-07-15 - 14 Days Itinerary Backup

- Date: 2026-07-15
- Area: Backups and Recovery
- Command/check: direct VPS backup creation around the 14 Days guide
  publication and hub refresh.
- Expected result: deployment backup exists before publication and contains a
  database export plus relevant page snapshots where available.
- Actual outcome: pre-publish backup exists at
  `/root/vietnamguide-backups/20260715-233341-14-day-itinerary-guide` with
  `db.sql` and `post-itineraries-before.json`. A post-publish snapshot backup
  exists at `/root/vietnamguide-backups/20260715-233427-14-day-itinerary-guide`
  with `db.sql`, `post-14-days-before.json`, and
  `post-itineraries-before.json`.
- Evidence link/path:
  `/root/vietnamguide-backups/20260715-233341-14-day-itinerary-guide` and
  `/root/vietnamguide-backups/20260715-233427-14-day-itinerary-guide`
- Follow-up: still configure durable off-server backup storage and run a
  restore test before launch sign-off.

### 2026-07-15 - 14 Days Itinerary Visual QA

- Date: 2026-07-15
- Area: Accessibility
- Command/check: Chrome DevTools Protocol visual and DOM QA for the 14 Days in
  Vietnam itinerary guide at desktop `1440x1300` and mobile `390x1200`, plus
  screenshot inspection of the mobile hero, route builder, and Source trail.
- Expected result: one visible H1, no horizontal overflow, photo hero image
  loads over HTTPS, all 14-day itinerary decision modules render on desktop and
  mobile, and mobile decision tables display as block layout without viewport
  overflow or self-overflow.
- Actual outcome: desktop and mobile checks passed. The guide has one visible
  H1 `14 Days in Vietnam`, no horizontal overflow, a loaded HTTPS hero image,
  all required 14-day markers, official source anchors, and image credit.
  Mobile tables for the route builder, extension matrix, and slowdown rules
  display as `block`, remain within the viewport, and do not self-overflow.
  Screenshot inspection found the mobile hero, route builder, and Source trail
  readable.
- Evidence link/path:
  `C:\Users\NCTaam\AppData\Local\Temp\vg-14day-qa-f3d30618fd8347eaa1bdd8cb5d69168b\desktop-14-days.png`,
  `C:\Users\NCTaam\AppData\Local\Temp\vg-14day-qa-f3d30618fd8347eaa1bdd8cb5d69168b\mobile-14-days.png`,
  `C:\Users\NCTaam\AppData\Local\Temp\vg-14day-qa-f3d30618fd8347eaa1bdd8cb5d69168b\mobile-route-builder.png`,
  and
  `C:\Users\NCTaam\AppData\Local\Temp\vg-14day-qa-f3d30618fd8347eaa1bdd8cb5d69168b\mobile-source-trail.png`
- Follow-up: rerun after itinerary-template CSS changes, source-list
  expansion, additional photo-led hero variants, or any new two-week planning
  module with long table labels.

### 2026-07-16 - 14 Days Itinerary Depth Expansion and QA

- Date: 2026-07-16
- Area: SEO, Editorial Depth, Accessibility
- Command/check: direct VPS backup, remote PHP lint, forced WP-CLI republish via
  `VG_FORCE_14_DAY_ITINERARY_REPUBLISH=1 wp eval-file
  ops/apply-14-days-itinerary-guide.php --allow-root`, EEAT verifier,
  WordPress object cache flush, LiteSpeed cache purge, public HTTP marker
  checks, Rank Math page sitemap check, filtered internal HTML crawl, and
  desktop/mobile Playwright visual QA.
- Expected result: `/itineraries/14-days-in-vietnam/` remains public and
  indexable, keeps the source trail and update log, and expands from the first
  two-week baseline into a deeper evidence-led guide with more route
  photography, extra-days value logic, transfer pressure checks, base/spend
  strategy, planning audit, and FAQ guidance without mobile overflow.
- Actual outcome: page ID `20` was republished at
  `https://vietnamguide.net/itineraries/14-days-in-vietnam/`. Remote PHP lint
  passed for `ops/apply-14-days-itinerary-guide.php`,
  `ops/verify-eeat-content.php`, and
  `wp-content/themes/vietnamguide-premium/functions.php`. EEAT verification
  passed after publication. Public HTML returned HTTP `200`, contained the new
  markers `What the extra four days actually buy`, `Transfer pressure map`,
  `Where to spend, save, and stay longer`, `Final planning audit before you
  book`, `Short answers for common two-week mistakes`, the new image-credit
  markers `Alex 69200 vx`, `Wolkenkratzer`, and `BacLuong`, and loaded theme
  CSS `vietnamguide-premium.css?ver=0.1.11`. Rank Math `/page-sitemap.xml`
  returned HTTP `200` and included the 14-day URL. Filtered public crawl checked
  10 seed pages and 31 internal HTML URLs with `0` failures. Playwright QA at
  desktop `1440x1300` and mobile `390x1200` found one visible H1, no horizontal
  overflow, the hero image loaded, all `8` route photos loaded, mobile decision
  tables displayed as block layout without self-overflow, and the FAQ opened
  correctly.
- Backup: `/root/vietnamguide-backups/20260716-083508-14-day-more-depth`
  contains `db.sql`, `post-14-days-before.json`,
  `post-itineraries-before.json`, prior theme file snapshots, and
  missing-before markers for newly deployed `ops` scripts.
- Evidence link/path:
  `https://vietnamguide.net/itineraries/14-days-in-vietnam/` and
  `C:\Users\NCTaam\AppData\Local\Temp\vg-14day-more-depth-pw-qa-1784166776516\visual-qa-results.json`
- Follow-up: use this richer two-week guide as the baseline for later family,
  luxury, north-only, central-slow, or island-extension variants; keep any
  narrower guide unpublished until it has its own source trail, update log,
  photo proof, and route-specific decision framework.

### 2026-07-16 - Premium Homepage Expansion and QA

- Date: 2026-07-16
- Area: Homepage, SEO, Editorial Depth, Accessibility
- Command/check: direct VPS backup, remote PHP lint, homepage publish via
  `wp eval-file ops/apply-homepage-premium.php --allow-root`, hub-link refresh
  via `wp eval-file ops/apply-eeat-home-hub-links.php --allow-root`,
  `ops/verify-eeat-content.php`, WordPress object cache flush, LiteSpeed cache
  purge, public homepage marker checks, Rank Math page sitemap check, homepage
  internal-link crawl, and desktop/tablet/mobile Playwright visual QA.
- Expected result: homepage becomes a premium Evidence-led Concierge entry
  point for international travelers, with one H1, decision-first hero,
  reviewed deep-guide shelf, route-photo proof, visible image credits,
  editorial-method proof, no internal links to unpublished pages, current theme
  CSS, and no desktop/mobile overflow.
- Actual outcome: Home page ID `5` was updated at `https://vietnamguide.net/`.
  Remote PHP lint passed for `ops/apply-homepage-premium.php`,
  `ops/apply-eeat-home-hub-links.php`, `ops/verify-eeat-content.php`,
  `wp-content/themes/vietnamguide-premium/functions.php`, and
  `wp-content/themes/vietnamguide-premium/patterns/homepage-sections.php`.
  EEAT verification passed after publication. Public HTML returned HTTP `200`,
  had one H1, loaded `vietnamguide-premium.css?ver=0.1.12`, and contained
  `vg-homepage-premium:v1`, `vg-home-guide-shelf`, `vg-home-photo-grid`,
  `vg-home-editorial-proof`, all four route image credits, and the new
  homepage title `Vietnam Travel Guide for International Visitors`. Rank Math
  `/page-sitemap.xml` returned HTTP `200` and still included Home plus current
  deep guide URLs. Homepage internal-link crawl checked `21` same-origin URLs
  with `0` failures. Playwright QA at desktop `1440x1300`, tablet `820x1200`,
  and mobile `390x1200` found one visible H1, no horizontal overflow, four
  loaded route photos, eight reviewed-guide rows, no failed requests, and no
  console errors. A follow-up verifier tightening pass split the homepage
  checks into stricter hero-proof, guide-shelf, and editorial-trust markers;
  remote PHP lint passed and the EEAT verifier passed again. Post-review
  hardening then moved reveal-motion activation behind a usable
  `IntersectionObserver` function check, converted the reusable homepage
  pattern to dynamic published-page fallback URLs, and bumped theme assets to
  `0.1.14`. Because SSH password authentication began closing connections after
  the main deploy, the hardening patch was applied through the WordPress Theme
  File Editor for `functions.php`, `assets/js/vietnamguide-premium.js`, and
  `patterns/homepage-sections.php`; LiteSpeed purge-all was triggered through
  the authenticated admin purge URL. Public HTML then loaded
  `vietnamguide-premium.css?ver=0.1.14` and
  `vietnamguide-premium.js?ver=0.1.14`. Follow-up Playwright QA at desktop
  `1440x1300`, tablet `820x1200`, and mobile `390x1200` found one visible H1,
  no horizontal overflow, four loaded route photos, eight reviewed-guide rows,
  no failed requests, and no console errors. A no-usable-IntersectionObserver
  fallback test found `vg-motion-ready` absent, reveal opacity `1`, and visible
  hero text.
- Backup: `/root/vietnamguide-backups/20260716-093338-homepage-premium`
  contains `db.sql`, `post-home-before.json`, previous homepage/theme file
  snapshots, previous verifier and hub-link script snapshots, and a
  missing-before note for the new homepage publisher.
- Evidence link/path:
  `https://vietnamguide.net/` and
  `C:\Users\NCTaam\AppData\Local\Temp\vg-homepage-premium-qa-scroll-1784169726866\visual-qa-results.json`;
  hardening QA:
  `C:\Users\NCTaam\AppData\Local\Temp\vg-homepage-014-qa-1784180825452\visual-qa-results.json`
- Follow-up: continue adding deep guides one at a time only after they have a
  proof panel, source trail, update log, photo proof where useful, and a clear
  decision framework; keep narrower eSIM, safety, airport-transfer, family,
  luxury, and destination variants unlinked until reviewed and live.

### 2026-07-16 - Homepage Planning Map Upgrade

- Date: 2026-07-16
- Area: Homepage, Premium UX, SEO Intent Routing, Editorial Depth
- Command/check: local diff review, subagent spec review, subagent code-quality
  review, `git diff --check`, static block-count checks, WordPress admin
  cookie flow, Theme File Editor updates for `functions.php`,
  `assets/css/vietnamguide-premium.css`, and `patterns/homepage-sections.php`,
  REST update for Home page ID `5`, LiteSpeed purge-all, public marker checks,
  internal link crawl, Rank Math page sitemap check, image URL checks, and
  desktop/tablet/mobile browser DOM QA.
- Expected result: homepage decision spine becomes a premium traveler-state
  planning map that routes international visitors by the decision blocking the
  trip: dates/no route, flights/entry checks, route/cost reality, region
  choice, and itinerary trimming. The module should not add generic SEO filler,
  should keep all links to published pages, should load asset version `0.1.15`,
  and should avoid horizontal overflow.
- Actual outcome: Home page ID `5` was updated at
  `https://vietnamguide.net/`. The first REST content deploy exposed unresolved
  PHP-style placeholders in live image URLs because the temporary HTML builder
  replaced `${key}` but not `{$key}`; browser image QA caught this before
  completion. The homepage content was regenerated with `0` unresolved
  placeholders and redeployed. Final public marker checks found
  `vg-home-planning-map:v1`, all five planning-state headings, `0` unresolved
  placeholders, one H1, eight reviewed-guide rows, CSS/JS asset version
  `0.1.15`, and `30` internal links with `0` failures. Rank Math
  `/page-sitemap.xml` returned HTTP `200` and still included Home, Travel
  Guide, Travel Cost, and 14 Days URLs. Browser DOM QA found no horizontal
  overflow, no overflowing planning-map/link text, no console errors, the hero
  WebP loaded, and lazy route images loaded after scroll on desktop/mobile.
  Direct HTTP checks returned `200` for the hero WebP/JPEG and all four route
  image URLs.
- Backup: local admin-flow backup stored in
  `C:\Users\NCTaam\AppData\Local\Temp\vg-homepage-015-6a041c6cce954c55af9f708bc24a00fb\backup-before`;
  cookie jars were removed after use. No remote PHP lint was run because SSH
  password auth remains unavailable and local PHP is not on PATH; Theme File
  Editor accepted the PHP/theme file updates.
- Follow-up: when SSH or WP-CLI access is restored, rerun
  `ops/verify-eeat-content.php` server-side and replace the temporary REST
  homepage deployment path with the normal `wp eval-file
  ops/apply-homepage-premium.php --allow-root` flow.

### 2026-07-16 - Homepage Default Route Verdict

- Date: 2026-07-16
- Area: Homepage, Premium UX, Editorial Judgment, First-Trip Route Intent
- Command/check: subagent exploration, local static block-count checks,
  `git diff --check`, subagent spec review, subagent code-quality review,
  Theme File Editor updates for `functions.php`,
  `assets/css/vietnamguide-premium.css`, and `patterns/homepage-sections.php`,
  REST update for Home page ID `5`, LiteSpeed purge-all, public marker checks,
  internal link crawl, and Rank Math page sitemap check.
- Expected result: homepage gains a compact Default Route Verdict band after
  the Planning Map and before the guide shelf, giving undecided first-time
  international visitors a clear editorial route default without adding a
  broad checklist, volatile claims, or links to staged topics.
- Actual outcome: Home page ID `5` was updated at
  `https://vietnamguide.net/`. Final public marker checks found
  `vg-home-route-verdict:v1`, `Default route verdict`, route-specific CTAs
  `Read the 10-day guide`, `Read the 14-day guide`, and `Region unsure`;
  the earlier draft booking-audit marker was absent. Public HTML had
  `3` verdict rows, `5` planning rows, `8` reviewed-guide rows, `1` H1,
  `0` unresolved placeholders, and theme CSS/JS version `0.1.16`. Internal
  link crawl checked `30` same-origin URLs with `0` failures. Rank Math
  `/page-sitemap.xml` included Home, 10 Days, 14 Days, and the regional
  comparison URL. A reviewer flagged broad verifier needles for `10 days` and
  `14 days`; the verifier was tightened to route-verdict CTA text and a fresh
  code-quality review approved.
- Backup: local admin-flow backup stored in
  `C:\Users\NCTaam\AppData\Local\Temp\vg-homepage-016-0e04df49de4f4c7f8f7dff4dbc839d4a\backup-before`;
  cookie jars were removed after use. No remote PHP lint was run because SSH
  password auth remains unavailable and local PHP is not on PATH; Theme File
  Editor accepted the PHP/theme file updates.
- Follow-up: keep future homepage additions narrow and verdict-led. Avoid
  adding eSIM, airport-transfer, safety, family, luxury, or destination-variant
  modules to the homepage until those pages are published with source trails
  and update logs.

### 2026-07-16 - Vietnam Travel Guide Depth Pass

- Date: 2026-07-16
- Area: Vietnam Travel Guide, Pillar SEO, Premium Editorial UX, EEAT
- Command/check: remote backup, SFTP upload for
  `ops/apply-vietnam-travel-guide.php`, `ops/verify-eeat-content.php`,
  `functions.php`, and `assets/css/vietnamguide-premium.css`; remote
  `php -l`; forced WP-CLI republish with
  `VG_FORCE_TRAVEL_GUIDE_REPUBLISH=1`; full
  `wp eval-file ops/verify-eeat-content.php --allow-root`; WordPress object
  cache flush; LiteSpeed purge-all; public HTML marker sweep; desktop/mobile
  Playwright DOM and image-load smoke.
- Expected result: the pillar guide should become more image-led and
  decision-rich without adding generic travel filler. It should add visible
  route-chapter photography, a transfer-value feature, a five-step planning
  flow, route-family comparison, and first-trip mistake checks, while keeping
  all links published, source/update metadata visible, no unresolved
  placeholders, and no horizontal overflow.
- Actual outcome: `https://vietnamguide.net/plan/vietnam-travel-guide/`
  republished successfully as page ID `98`. Public HTML returned HTTP `200`,
  one H1, asset version `0.1.19`, no unresolved `{$...}` placeholders, and
  all new markers: `vg-travel-guide-planning-flow:v1`,
  `vg-travel-guide-regional-photo-grid:v1`,
  `vg-travel-guide-transfer-feature:v1`,
  `vg-travel-guide-route-family:v1`, and
  `vg-travel-guide-mistakes:v1`. Full EEAT verification passed. Desktop and
  mobile browser smoke found no horizontal overflow; the new regional photo
  grid loaded `5/5` images and the transfer feature loaded `1/1` image after
  scroll.
- Backup: `/root/vietnamguide-backups/20260716-212505-pre-travel-guide-depth-pass`
  contains the database export and pre-change copies of the deployed files.
- Follow-up: continue applying the same template-first contract to the
  remaining weaker guide pages, especially photo-led proof, route trade-off
  modules, source trails, update logs, and visible mistake checks where search
  intent is decision-heavy.

### 2026-07-16 - 10 Days Itinerary Depth Pass

- Date: 2026-07-16
- Area: 10 Days in Vietnam, Itinerary SEO, Premium Editorial UX, EEAT
- Command/check: remote backup, SFTP upload for
  `ops/apply-10-days-itinerary-guide.php` and
  `ops/verify-eeat-content.php`; remote `php -l`; forced WP-CLI republish with
  `VG_FORCE_10_DAY_ITINERARY_REPUBLISH=1`; full
  `wp eval-file ops/verify-eeat-content.php --allow-root`; WordPress object
  cache flush; LiteSpeed purge-all; public HTML marker sweep; Rank Math page
  sitemap check; desktop/mobile Playwright DOM and image-load smoke.
- Expected result: `/itineraries/10-days-in-vietnam/` gains photo-led route
  proof, a 10-day planning flow, route-family visual comparison, a transfer
  value feature, and concise mistake checks without turning the itinerary into
  generic list content. The guide should keep one H1, no placeholders, visible
  source/update accountability, related routes, and no horizontal overflow.
- Actual outcome: page ID `19` republished successfully at
  `https://vietnamguide.net/itineraries/10-days-in-vietnam/`. Public HTML
  returned HTTP `200`, one H1, asset version `0.1.19`, no unresolved
  `{$...}` placeholders, and all new markers:
  `vg-itinerary-10day-photo-grid:v1`,
  `vg-itinerary-10day-planning-flow:v1`,
  `vg-itinerary-10day-route-family:v1`,
  `vg-itinerary-10day-transfer-feature:v1`, and
  `vg-itinerary-10day-mistakes:v1`. Rank Math `/page-sitemap.xml` returned
  HTTP `200` and included the 10-day URL. Full EEAT verification passed.
  Desktop and mobile browser smoke found no horizontal overflow; the new route
  photo grid loaded `5/5` images and the transfer feature loaded `1/1` image
  after scroll.
- Backup: `/root/vietnamguide-backups/20260716-214756-pre-10day-depth-pass`
  contains the database export and pre-change copies of the deployed files.
- Follow-up: continue with other strong intent pages only where the page can
  add original planning judgment: likely Best Time, region comparison, or
  destination guide depending on the next homepage/internal-link pressure.

### 2026-07-16 - 10 Days Itinerary Depth Extension

- Date: 2026-07-16
- Area: 10 Days in Vietnam, Route Decision UX, Premium Editorial UX, EEAT
- Command/check: local content patch to add route brief, southern-proof
  photography, season pivots, traveler-fit adaptations, prebook-versus-flexible
  guidance, booking sequence, and planning audit; upload to VPS; remote
  `php -l`; forced WP-CLI republish with
  `VG_FORCE_10_DAY_ITINERARY_REPUBLISH=1`; full
  `wp eval-file ops/verify-eeat-content.php --allow-root`; public HTML marker
  sweep; Rank Math sitemap check; image URL liveness checks with `curl.exe`.
- Expected result: `/itineraries/10-days-in-vietnam/` gains a quick route
  brief, stronger route-bias and traveler-fit logic, explicit booking order,
  and southern-route proof while preserving the existing premium editorial
  structure and EEAT contract.
- Actual outcome: page ID `19` republished successfully at
  `https://vietnamguide.net/itineraries/10-days-in-vietnam/`. Public HTML
  returned HTTP `200`, one H1, no unresolved `{$...}` placeholders, and all new
  markers were present: `vg-itinerary-10day-at-a-glance:v1`,
  `vg-itinerary-10day-season-pivots:v1`,
  `vg-itinerary-10day-traveler-fit:v1`,
  `vg-itinerary-10day-audience-adaptations:v1`,
  `vg-itinerary-10day-prebook-flex:v1`,
  `vg-itinerary-10day-booking-sequence:v1`,
  `vg-itinerary-10day-planning-audit:v1`, and
  `vg-itinerary-10day-southern-proof:v1`. Rank Math `/page-sitemap.xml`
  returned HTTP `200` and included the 10-day URL. Public image URLs for the
  new southern-proof and transfer visuals responded with HTTP `200`.
- Backup: `/root/vietnamguide-backups/20260716-220448-pre-10day-intent-depth-pass`
  contains the database export and pre-change copies of the deployed files.
- Follow-up: keep the 10-day route as the main pressure-test page for traffic
  routed from homepage and guide modules; any future extension should only add
  a new block when it changes a booking decision.

### 2026-07-16 - Best Time to Visit Vietnam Depth Pass

- Date: 2026-07-16
- Area: Best Time to Visit Vietnam, Seasonal SEO, Premium Editorial UX, EEAT
- Command/check: local rewrite of
  `ops/apply-best-time-guide.php` and `ops/verify-eeat-content.php`; remote
  backup, SFTP upload, remote `php -l`, forced WP-CLI republish with
  `VG_FORCE_BEST_TIME_REPUBLISH=1`, full EEAT verification, WordPress cache
  flush, LiteSpeed purge, public HTML marker sweep, Rank Math sitemap check,
  and image URL liveness checks with `curl.exe`.
- Expected result: `/plan/best-time-to-visit-vietnam/` becomes a route-first
  seasonal guide with a premium hero, at-a-glance summary, regional photo
  proof, route-style timing matrix, seasonal pivots, planning flow, failure
  modes, booking audit, and timing FAQ while keeping the content evergreen and
  anti-spam.
- Actual outcome: page ID `14` republished successfully at
  `https://vietnamguide.net/plan/best-time-to-visit-vietnam/`. Public HTML
  returned HTTP `200`, one H1, no unresolved `{$...}` placeholders, and all
  Best Time markers were present: `vg-best-time-at-a-glance:v1`,
  `vg-best-time-photo-grid:v1`, `vg-best-time-route-matrix:v1`,
  `vg-best-time-regional-pivots:v1`, `vg-best-time-planning-flow:v1`,
  `vg-best-time-route-examples:v1`, `vg-best-time-failure-modes:v1`,
  `vg-best-time-booking-audit:v1`, and `vg-best-time-mistakes:v1`. Rank Math
  `/page-sitemap.xml` returned HTTP `200` and included the Best Time URL.
  Public image URLs for the hero and four body images returned HTTP `200`.
- Backup: `/root/vietnamguide-backups/20260716-223025-pre-best-time-media-cleanup`
  contains the database export and pre-change copies of the deployed files.
- Follow-up: keep the Best Time guide as the weather-pressure page that routes
  users into 10 Days, 14 Days, and regional comparison pages instead of
  generic month advice.

### 2026-07-17 - Transport Within Vietnam Publication

- Date: 2026-07-17
- Area: Transport Within Vietnam, Route Decision UX, Premium Editorial UX, EEAT
- Command/check: remote backup to
  `/root/vietnamguide-backups/20260717-092254-pre-transport-guide`; SFTP upload
  of `ops/apply-transport-within-vietnam-guide.php` and
  `ops/verify-eeat-content.php`; remote `php -l`; forced WP-CLI publish via
  `VG_FORCE_TRANSPORT_GUIDE_REPUBLISH=1 wp eval-file
  ops/apply-transport-within-vietnam-guide.php --allow-root`; full
  `wp eval-file ops/verify-eeat-content.php --allow-root`; cache flush and
  LiteSpeed purge; public HTML marker sweep; Rank Math sitemap refresh and
  public sitemap check; image URL liveness checks.
- Expected result: `/plan/transport-within-vietnam/` publishes as the next
  evergreen planning page with premium transport-mode judgment, exact-path
  EEAT verification, source/update discipline, and no flaky media assets.
- Actual outcome: page ID `155` published successfully at
  `https://vietnamguide.net/plan/transport-within-vietnam/`. Remote PHP lint
  passed. EEAT verification passed. Public HTML returned HTTP `200`, one H1,
  no unresolved placeholders, and all transport markers rendered:
  `vg-transport-hero:v1`, `vg-transport-at-a-glance:v1`,
  `vg-transport-photo-grid:v1`, `vg-transport-route-proof:v1`,
  `vg-transport-corridor-table:v1`, `vg-transport-mode-matrix:v1`,
  `vg-transport-prebook-flex:v1`, `vg-transport-booking-audit:v1`,
  `vg-transport-mistakes:v1`, `vg-transport-faq:v1`, `vg-proof-panel`,
  `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Rank Math
  `/page-sitemap.xml` refreshed to 23 published page URLs and now includes the
  Transport URL. Public image checks returned HTTP `200` for the live hero,
  railway, airport, and hosted Ha Long Bay water image.
- Evidence link/path: `https://vietnamguide.net/plan/transport-within-vietnam/`
- Follow-up: keep Transport Within Vietnam as the route-pressure page for
  long north-south moves, central corridor handoffs, airport transfers, and
  ferry/boat decisions; add narrower transport-adjacent pages only when they
  materially change a booking choice.

### 2026-07-17 - Money in Vietnam Publication

- Date: 2026-07-17
- Area: Money in Vietnam, Payment Logistics, Premium Editorial UX, EEAT
- Command/check: remote backup to `/root/vietnamguide-backups/20260717-102004-pre-money-guide`; SFTP upload of `ops/apply-money-cash-cards-atms-guide.php`, `ops/apply-homepage-premium.php`, `ops/apply-vietnam-travel-cost-guide.php`, `ops/apply-vietnam-travel-guide.php`, and `ops/verify-eeat-content.php`; remote `php -l`; forced WP-CLI publish via `VG_FORCE_MONEY_GUIDE_REPUBLISH=1 wp eval-file ops/apply-money-cash-cards-atms-guide.php --allow-root`; forced republish of Vietnam Travel Cost and Vietnam Travel Guide; homepage refresh; full `wp eval-file ops/verify-eeat-content.php --allow-root`; cache flush and LiteSpeed purge; public HTML marker sweep; sitemap refresh; image URL liveness checks.
- Expected result: `/plan/money-cash-cards-atms/` publishes as the next evergreen planning page with premium mixed-payment judgment, exact-path EEAT verification, source/update discipline, refreshed hub notes, and no flaky media assets.
- Actual outcome: page ID `158` published successfully at `https://vietnamguide.net/plan/money-cash-cards-atms/`. Remote PHP lint passed for all five touched scripts. EEAT verification passed. Public HTML returned HTTP `200`, one H1, no unresolved placeholders, and all money markers rendered: `vg-money-hero:v1`, `vg-money-concierge-verdict`, `vg-money-currency-basics:v1`, `vg-money-photo-grid:v1`, `vg-money-payment-decision-table:v1`, `vg-money-arrival-cash-plan:v1`, `vg-money-exchange-atm-card:v1`, `vg-money-atm-checklist:v1`, `vg-money-card-acceptance:v1`, `vg-money-cash-declaration:v1`, `vg-money-safety-mistakes:v1`, `vg-money-live-checks:v1`, `vg-money-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Rank Math `/page-sitemap.xml` refreshed to 24 published page URLs and now includes the Money URL. Public image checks returned HTTP `200` for the Ben Thanh hero, 500,000 dong banknote, Dong Xuan market, ATM, and Noi Bai airport images.
- Evidence link/path: `https://vietnamguide.net/plan/money-cash-cards-atms/`
- Follow-up: keep Money in Vietnam as the payment-logistics page for arrival cash, card acceptance, ATMs, exchange-rate checks, and border cash declaration thresholds; add narrower payment-adjacent pages only when they materially change a booking choice.

### 2026-07-17 - SIM and eSIM in Vietnam Publication

- Date: 2026-07-17
- Area: SIM/eSIM, Connectivity, Premium Editorial UX, EEAT
- Command/check: remote backup to `/root/vietnamguide-backups/20260717-115127-pre-sim-esim`; SFTP upload of `ops/apply-sim-esim-vietnam-guide.php`, `ops/apply-homepage-premium.php`, `ops/apply-vietnam-travel-guide.php`, `ops/apply-vietnam-travel-cost-guide.php`, `ops/apply-money-cash-cards-atms-guide.php`, and `ops/verify-eeat-content.php`; remote `php -l`; forced WP-CLI publish via `wp eval-file /tmp/vg-eeat/ops/apply-sim-esim-vietnam-guide.php --allow-root`; republish of homepage, Vietnam Travel Guide, Vietnam Travel Cost, and Money in Vietnam; full `wp eval-file /tmp/vg-eeat/ops/verify-eeat-content.php --allow-root`; cache flush and LiteSpeed purge; public HTML marker sweep; sitemap refresh; image URL liveness checks.
- Expected result: `/plan/sim-esim-vietnam/` publishes as the next evergreen logistics page with premium connectivity judgment, exact-path EEAT verification, source/update discipline, refreshed hub links, and no flaky media assets.
- Actual outcome: page ID `15` published successfully at `https://vietnamguide.net/plan/sim-esim-vietnam/`. Remote PHP lint passed for all touched scripts. EEAT verification passed. Public HTML returned HTTP `200`, one H1, no content placeholders, and all SIM/eSIM markers rendered: `vg-sim-esim-hero:v1`, `vg-sim-esim-concierge-verdict`, `vg-sim-esim-basics:v1`, `vg-sim-esim-photo-grid:v1`, `vg-sim-esim-decision-table:v1`, `vg-sim-esim-compatibility-check:v1`, `vg-sim-esim-arrival-setup:v1`, `vg-sim-esim-coverage-matrix:v1`, `vg-sim-esim-local-number:v1`, `vg-sim-esim-hotspot-data:v1`, `vg-sim-esim-prebook-flex:v1`, `vg-sim-esim-failure-modes:v1`, `vg-sim-esim-live-checks:v1`, `vg-sim-esim-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage now shows a SIM/eSIM reviewed-guide row. Plan hub includes the SIM/eSIM note. The Cost guide links to SIM/eSIM logistics. Rank Math `/page-sitemap.xml` refreshed to 25 published page URLs and includes the SIM/eSIM URL. Public image checks returned HTTP `200` for the airport, nano SIM tray, eSIM screenshot, and Hai Van Pass images.
- Evidence link/path: `https://vietnamguide.net/plan/sim-esim-vietnam/`
- Follow-up: keep SIM/eSIM as the arrival-connectivity page for device compatibility, local-number needs, roaming fallback, and route coverage; add narrower carrier comparison content only when it materially changes the booking choice.

### 2026-07-17 - UNESCO Heritage Sites in Vietnam Publication

- Date: 2026-07-17
- Area: UNESCO heritage, Destinations cluster, Evergreen editorial calendar,
  Premium Editorial UX, EEAT
- Command/check: remote backup to
  `/root/vietnamguide-backups/20260717-125726-pre-unesco-heritage-guide`;
  SFTP upload of `ops/apply-unesco-heritage-sites-guide.php`,
  `ops/apply-homepage-premium.php`,
  `ops/apply-best-places-destination-guide.php`,
  `ops/verify-eeat-content.php`, and the homepage pattern; remote `php -l`;
  WP-CLI publish via `VG_FORCE_HERITAGE_GUIDE_REPUBLISH=1 wp eval-file
  /tmp/vg-eeat/ops/apply-unesco-heritage-sites-guide.php --allow-root`;
  homepage refresh; full `wp eval-file /tmp/vg-eeat/ops/verify-eeat-content.php
  --allow-root`; cache flush; LiteSpeed purge; public HTML marker sweep;
  sitemap check; image URL liveness checks.
- Expected result: `/destinations/unesco-heritage-sites-vietnam/` publishes as
  the first dedicated heritage-cluster guide, links from the Destinations hub
  and Best Places guide, appears on the homepage reviewed-guide shelf, and
  seeds the long-term editorial calendar for destination, heritage, and top-list
  content.
- Actual outcome: page ID `170` published successfully at
  `https://vietnamguide.net/destinations/unesco-heritage-sites-vietnam/`.
  Remote PHP lint passed for all touched scripts. EEAT verification passed.
  Public HTML returned HTTP `200`, one H1, no content placeholders, and all
  UNESCO heritage markers rendered: `vg-unesco-heritage-hero:v1`,
  `vg-unesco-heritage-concierge-verdict`,
  `vg-unesco-heritage-shortlist:v1`,
  `vg-unesco-heritage-route-map:v1`,
  `vg-unesco-heritage-photo-grid:v1`,
  `vg-unesco-heritage-skip-logic:v1`, and
  `vg-unesco-heritage-official-checks:v1`, plus `vg-proof-panel`,
  `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage now
  shows the UNESCO Heritage Sites row. Rank Math `/page-sitemap.xml` refreshed
  to 26 published page URLs and includes the UNESCO Heritage Sites URL. Public
  image checks returned HTTP `200` for the Ha Long hero, Hanoi, Trang An, Hue,
  Hoi An, and Son River images.
- Evidence link/path:
  `https://vietnamguide.net/destinations/unesco-heritage-sites-vietnam/`
- Follow-up: publish the next destination-cluster pages from
  `docs/editorial/evergreen-content-calendar-2026-2027.md`, starting with
  Best Things to Do in Hanoi, then Hoi An, Hue, beaches, islands, and day-trip
  guides.

### 2026-07-17 - Best Things to Do in Hanoi Publication

- Date: 2026-07-17
- Area: Hanoi destination pillar, Destinations cluster, reviewed-guide shelf,
  premium editorial UX, EEAT
- Command/check: remote backup to
  `/root/vietnamguide-backups/20260717-153352-pre-hanoi-guide`; SFTP upload of
  `ops/apply-best-things-hanoi-guide.php`, `ops/verify-eeat-content.php`,
  `ops/apply-homepage-premium.php`,
  `ops/apply-best-places-destination-guide.php`,
  `ops/apply-vietnam-travel-guide.php`,
  `ops/apply-unesco-heritage-sites-guide.php`, and the homepage pattern; remote
  `php -l`; forced WP-CLI publish via `VG_FORCE_HANOI_GUIDE_REPUBLISH=1 wp eval-file
  ops/apply-best-things-hanoi-guide.php --allow-root`; republish of Best Places,
  UNESCO Heritage Sites, Vietnam Travel Guide, and the homepage; full
  `wp eval-file ops/verify-eeat-content.php --allow-root`; cache flush; LiteSpeed
  purge; public HTML marker sweep; sitemap refresh; image URL liveness checks.
- Expected result: `/destinations/best-things-to-do-in-hanoi/` publishes as the
  next evergreen destination/top-list page with premium route-value judgment,
  exact-path EEAT verification, source/update discipline, refreshed hub links,
  and no flaky media assets.
- Actual outcome: page ID `173` published successfully at
  `https://vietnamguide.net/destinations/best-things-to-do-in-hanoi/`.
  Remote PHP lint passed for all touched scripts. EEAT verification passed.
  Public HTML returned HTTP `200`, one H1, no unresolved placeholders, and all
  Hanoi markers rendered: `vg-best-things-hanoi-hero:v1`,
  `vg-best-things-hanoi-concierge-verdict`,
  `vg-best-things-hanoi-priority-map:v1`,
  `vg-best-things-hanoi-photo-grid:v1`,
  `vg-best-things-hanoi-route-fit:v1`,
  `vg-best-things-hanoi-rain-heat-pivots:v1`,
  `vg-best-things-hanoi-skip-logic:v1`,
  `vg-best-things-hanoi-official-checks:v1`,
  `vg-best-things-hanoi-faq:v1`, plus `vg-proof-panel`,
  `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage now
  shows the Best Things to Do in Hanoi reviewed-guide row. Destinations hub now
  includes the Hanoi note. Rank Math `/page-sitemap.xml` refreshed to 27
  published page URLs and includes the Hanoi URL. Public image checks returned
  HTTP `200` for Hoan Kiem, Dong Xuan, Temple of Literature, Thang Long, and
  Train Street images.
- Evidence link/path:
  `https://vietnamguide.net/destinations/best-things-to-do-in-hanoi/`
- Follow-up: keep Hanoi as the capital-orientation page for the northern route,
  then publish the next evergreen destination pages from the calendar,
  especially Hoi An and Hue.

### 2026-07-18 - Best Things to Do in Hoi An Publication

- Date: 2026-07-18
- Area: Hoi An destination pillar, Central Vietnam route depth, reviewed-guide
  shelf, premium editorial UX, EEAT
- Command/check: remote backup to
  `/root/vietnamguide-backups/20260718-082310-pre-hoi-an-guide`; SFTP upload of
  `ops/apply-best-things-hoi-an-guide.php`, `ops/apply-homepage-premium.php`,
  `ops/apply-best-places-destination-guide.php`,
  `ops/apply-vietnam-travel-guide.php`, and `ops/verify-eeat-content.php`;
  remote `php -l`; forced WP-CLI publish via
  `VG_FORCE_HOI_AN_GUIDE_REPUBLISH=1 wp eval-file
  ops/apply-best-things-hoi-an-guide.php --allow-root`; homepage refresh; full
  `wp eval-file ops/verify-eeat-content.php --allow-root`; cache flush;
  LiteSpeed purge; public HTML marker sweep; sitemap refresh; image URL
  liveness checks.
- Expected result: `/destinations/best-things-to-do-in-hoi-an/` publishes as
  the next evergreen central Vietnam destination/top-list page with route-first
  Hoi An judgment, exact-path EEAT verification, source/update discipline,
  refreshed hub links, and no flaky media assets.
- Actual outcome: page ID `178` published successfully at
  `https://vietnamguide.net/destinations/best-things-to-do-in-hoi-an/`.
  Remote PHP lint passed for all touched scripts. EEAT verification passed.
  Public HTML returned HTTP `200`, one H1, no unresolved placeholders, and all
  Hoi An markers rendered: `vg-best-things-hoi-an-hero:v1`,
  `vg-best-things-hoi-an-concierge-verdict`,
  `vg-best-things-hoi-an-priority-map:v1`,
  `vg-best-things-hoi-an-photo-grid:v1`,
  `vg-best-things-hoi-an-route-fit:v1`,
  `vg-best-things-hoi-an-season-pivots:v1`,
  `vg-best-things-hoi-an-skip-logic:v1`,
  `vg-best-things-hoi-an-official-checks:v1`,
  `vg-best-things-hoi-an-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`,
  `vg-update-log`, and `vg-related-routes`. Homepage now shows the Hoi An
  reviewed-guide row. Destinations hub now includes the Hoi An note. Rank Math
  `/page-sitemap.xml` refreshed to 28 published page URLs and includes the Hoi
  An URL. Public image checks returned HTTP `200` for Hoi An Ancient Town,
  Japanese Covered Bridge, My Son Sanctuary, and An Bang Beach images.
- Evidence link/path:
  `https://vietnamguide.net/destinations/best-things-to-do-in-hoi-an/`
- Follow-up: keep Hoi An as the slower central Vietnam decision page; next
  adjacent work should stay within the region-comparison and central-route
  spine rather than adding broad listicles.

### 2026-07-18 - Safety and Scams in Vietnam Publication

- Date: 2026-07-18
- Area: Safety, scams, trust layer, practical logistics, premium editorial UX,
  EEAT
- Command/check: remote backup to
  `/root/vietnamguide-backups/20260718-pre-safety-guide`; SFTP upload of
  `ops/apply-safety-scams-vietnam-guide.php`,
  `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`;
  remote `php -l`; forced WP-CLI publish via
  `VG_FORCE_SAFETY_SCAMS_GUIDE_REPUBLISH=1 wp eval-file
  /tmp/vg-safety/ops/apply-safety-scams-vietnam-guide.php --allow-root`;
  homepage refresh; full `wp eval-file
  /tmp/vg-safety/ops/verify-eeat-content.php --allow-root`; cache flush;
  rewrite flush; LiteSpeed purge; Rank Math sitemap XML regeneration; public
  HTML marker sweep; homepage row check; sitemap check; image URL liveness
  checks; official source URL liveness checks.
- Expected result: `/plan/safety-scams-vietnam/` publishes as the first trust
  and risk-reduction page in the planning spine, with calm safety judgment,
  official-source discipline, source/update visibility, refreshed Plan hub and
  homepage links, and no fear-driven or thin scam-list content.
- Actual outcome: page ID `181` published successfully at
  `https://vietnamguide.net/plan/safety-scams-vietnam/`. Remote PHP lint
  passed for all three touched scripts. EEAT verification passed. Public HTML
  returned HTTP `200`, one H1, no unresolved placeholders, and all Safety
  markers rendered: `vg-safety-scams-hero:v1`,
  `vg-safety-scams-concierge-verdict`, `vg-safety-scams-risk-map:v1`,
  `vg-safety-scams-photo-grid:v1`,
  `vg-safety-scams-city-petty-crime:v1`,
  `vg-safety-scams-transport-road:v1`,
  `vg-safety-scams-water-nightlife:v1`,
  `vg-safety-scams-emergency-plan:v1`,
  `vg-safety-scams-live-checks:v1`, and `vg-safety-scams-faq:v1`, plus
  `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and
  `vg-related-routes`. Homepage now shows the Safety and Scams reviewed-guide
  row. Plan hub now includes the Safety note. Rank Math `/page-sitemap.xml`
  refreshed to 29 published page URLs and includes the Safety URL. Public image
  checks returned HTTP `200` for Noi Bai airport, Ben Thanh Market, Hanoi ATM,
  and Hai Van Pass images. Official source checks returned HTTP `200` for
  GOV.UK Vietnam safety/security, Canada Travel Advice Vietnam, CDC Travelers'
  Health Vietnam, Vietnam.travel health/safety, and Vietnam.travel transport
  pages.
- Evidence link/path:
  `https://vietnamguide.net/plan/safety-scams-vietnam/`
- Follow-up: build Best Things to Do in Hue next, then Health and Travel
  Insurance for Vietnam so the trust layer and central Vietnam cluster both
  continue to deepen.

### 2026-07-18 - Best Things to Do in Hue Publication

- Date: 2026-07-18
- Area: Hue destination pillar, central Vietnam heritage cluster, homepage shelf, destinations hub, internal-link spine, premium editorial UX, EEAT
- Command/check: remote backup to `/root/vietnamguide-backups/20260718-101929-pre-hue-guide`; SFTP upload of `ops/apply-best-things-hue-guide.php`, `ops/apply-homepage-premium.php`, `ops/verify-eeat-content.php`, `ops/apply-best-places-destination-guide.php`, `ops/apply-10-days-itinerary-guide.php`, `ops/apply-14-days-itinerary-guide.php`, and `ops/purge-litespeed-cache.php`; remote `php -l`; forced WP-CLI publish via `VG_FORCE_HUE_GUIDE_REPUBLISH=1 wp eval-file ops/apply-best-things-hue-guide.php --allow-root`; homepage refresh; full `wp eval-file ops/verify-eeat-content.php --allow-root`; WordPress object cache flush; rewrite flush; Rank Math XML cache delete; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTML marker sweep; homepage row check; destinations hub check; related-route checks on Best Places, 10 Days, and 14 Days; sitemap check; image URL liveness checks; official source checks.
- Expected result: `/destinations/best-things-to-do-in-hue/` publishes as the next evergreen central Vietnam destination/top-list page with serious imperial guidance, exact-path EEAT verification, source/update discipline, refreshed hub links, and no flaky media assets.
- Actual outcome: page ID `184` published successfully at `https://vietnamguide.net/destinations/best-things-to-do-in-hue/`. Remote PHP lint passed for all six touched scripts plus the purge helper. EEAT verification passed. Public HTML returned HTTP `200`, one H1, no unresolved placeholders, and all Hue markers rendered: `vg-best-things-hue-hero:v1`, `vg-best-things-hue-concierge-verdict`, `vg-best-things-hue-priority-map:v1`, `vg-best-things-hue-photo-grid:v1`, `vg-best-things-hue-route-fit:v1`, `vg-best-things-hue-weather-pivots:v1`, `vg-best-things-hue-skip-logic:v1`, `vg-best-things-hue-official-checks:v1`, `vg-best-things-hue-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage now shows the Best Things to Do in Hue reviewed-guide row. Destinations hub now includes the Hue note. Best Places, 10 Days, and 14 Days related routes now include Hue. Rank Math `/page-sitemap.xml` refreshed to 30 published page URLs and includes the Hue URL. Public image checks returned HTTP `200` for the Hue Citadel, Thien Mu Pagoda, Minh Mang Tomb, and Perfume River images. Official source checks returned HTTP `200` for Vietnam.travel Hue, Vietnam.travel Central Vietnam, Vietnam.travel weather and climate, and the Hue Monuments Conservation Centre price page.
- Evidence link/path: `https://vietnamguide.net/destinations/best-things-to-do-in-hue/`
- Follow-up: move to Health and Travel Insurance for Vietnam next, then Ninh Binh Travel Guide, while keeping the central heritage cluster and route spine synchronized.

### 2026-07-18 - Health and Travel Insurance for Vietnam Publication

- Date: 2026-07-18
- Area: health preparation, travel insurance, medical evacuation, route-risk planning, Plan hub, homepage shelf, internal-link spine, premium editorial UX, EEAT
- Command/check: SFTP upload of `ops/apply-health-travel-insurance-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l`; forced WP-CLI publish via `VG_FORCE_HEALTH_INSURANCE_GUIDE_REPUBLISH=1 wp eval-file ops/apply-health-travel-insurance-guide.php --allow-root`; homepage refresh; full `wp eval-file ops/verify-eeat-content.php --allow-root`; WordPress object cache flush; rewrite flush; Rank Math XML cache delete; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTML marker sweep; homepage row check; Plan hub note check; sitemap check; image URL liveness checks; official source checks.
- Expected result: `/plan/health-travel-insurance-vietnam/` publishes as the route-risk and insurance backstop guide for international travelers, with careful non-medical-advice framing, official source trail, route-risk tables, exclusion checks, visible update log, and inbound links from the planning spine.
- Actual outcome: page ID `187` published successfully at `https://vietnamguide.net/plan/health-travel-insurance-vietnam/`. Remote PHP lint passed for all three touched scripts. EEAT verification passed. Public HTML returned HTTP `200`, one H1, no unresolved editorial placeholders, and all Health/Insurance markers rendered: `vg-health-insurance-hero:v1`, `vg-health-insurance-concierge-verdict`, `vg-health-insurance-at-a-glance:v1`, `vg-health-insurance-photo-grid:v1`, `vg-health-insurance-coverage-table:v1`, `vg-health-insurance-route-risk-map:v1`, `vg-health-insurance-emergency-plan:v1`, `vg-health-insurance-pretravel-checks:v1`, `vg-health-insurance-policy-exclusions:v1`, `vg-health-insurance-live-checks:v1`, and `vg-health-insurance-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage now shows the Health and Travel Insurance reviewed-guide row. Plan hub now includes the Health/Insurance note. Vietnam Travel Guide, Safety and Scams, Vietnam Travel Cost, 10 Days, and 14 Days related-route metadata now include Health/Insurance. Rank Math `/page-sitemap.xml` includes the Health/Insurance URL. Image HEAD checks from the VPS returned HTTP `200` for Noi Bai airport, City International Hospital, FV Hospital, and Vietnam ambulance images. Official source checks returned HTTP `200` for CDC Travelers' Health Vietnam, Vietnam.travel health/safety, GOV.UK Vietnam health, and Canada Travel Advice Vietnam; Smartraveller timed out via local/VPS curl but was reachable through browser/web fetch during research.
- Evidence link/path: `https://vietnamguide.net/plan/health-travel-insurance-vietnam/`
- Follow-up: build Ninh Binh Travel Guide next, then Ha Long Bay vs Lan Ha Bay, keeping the northern landscape route spine linked from 10 Days, 14 Days, Best Places, UNESCO, homepage route proof, and Travel Cost.

### 2026-07-18 - Ninh Binh Travel Guide Publication

- Date: 2026-07-18
- Area: Ninh Binh destination pillar, northern landscape cluster, UNESCO/Trang An route fit, homepage shelf, destinations hub, internal-link spine, premium editorial UX, EEAT
- Command/check: remote backup to `/root/vietnamguide-backups/20260718-pre-ninh-binh-guide`; SFTP upload of `ops/apply-ninh-binh-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l`; forced WP-CLI publish via `VG_FORCE_NINH_BINH_GUIDE_REPUBLISH=1 wp eval-file ops/apply-ninh-binh-travel-guide.php --allow-root`; homepage refresh; full `wp eval-file ops/verify-eeat-content.php --allow-root`; WordPress object cache flush; rewrite flush; Rank Math XML cache delete; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTML marker sweep; homepage row check; Destinations hub note check; sitemap check; image URL liveness checks; official source checks.
- Expected result: `/destinations/ninh-binh-travel-guide/` publishes as the northern countryside and Trang An decision guide for international travelers, with a day-trip versus overnight verdict, licensed real images, source/update discipline, route-fit links, and no thin attraction-list framing.
- Actual outcome: page ID `190` published successfully at `https://vietnamguide.net/destinations/ninh-binh-travel-guide/`. Remote PHP lint passed for all three touched scripts. EEAT verification passed, then was tightened after review to require Ninh Binh inbound related-route metadata on nine strategic pages and visible author/license image credits; the stricter verifier also passed. Public HTML returned HTTP `200`, one H1, no unresolved editorial placeholders, and all Ninh Binh markers rendered: `vg-ninh-binh-hero:v1`, `vg-ninh-binh-concierge-verdict`, `vg-ninh-binh-at-a-glance:v1`, `vg-ninh-binh-photo-grid:v1`, `vg-ninh-binh-day-trip-overnight:v1`, `vg-ninh-binh-priority-map:v1`, `vg-ninh-binh-route-fit:v1`, `vg-ninh-binh-best-time-weather:v1`, `vg-ninh-binh-getting-around:v1`, `vg-ninh-binh-skip-logic:v1`, `vg-ninh-binh-live-checks:v1`, and `vg-ninh-binh-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage now shows the Ninh Binh Travel Guide reviewed-guide row. Destinations hub now includes the Ninh Binh note. Vietnam Travel Guide, Best Places, UNESCO Heritage Sites, North vs Central vs South, Best Time, Transport, Travel Cost, 10 Days, and 14 Days related-route metadata now include Ninh Binh. Rank Math `/page-sitemap.xml` includes the Ninh Binh URL. Image HEAD checks from the VPS returned HTTP `200` for Trang An, Tam Coc, Mua Cave, Mua Cave Dragon Mountain, Bai Dinh, Cuc Phuong, and Van Long images. Official source checks returned HTTP `200` for Vietnam.travel Ninh Binh, Vietnam.travel Northern Vietnam, Vietnam.travel weather/climate, Vietnam.travel transport, and Ninh Binh Tourism Department; UNESCO Trang An returned `403` to local curl but was reachable through web search/browser fetch during verification.
- Evidence link/path: `https://vietnamguide.net/destinations/ninh-binh-travel-guide/`
- Follow-up: build Ha Long Bay vs Lan Ha Bay next, then Ha Long Bay Travel Guide, because the northern scenery cluster now has homepage, Best Places, UNESCO, Ninh Binh, 10 Days, and 14 Days support.

### 2026-07-18 - Ha Long Bay and Lan Ha Bay Northern Cruise Cluster Publication

- Date: 2026-07-18
- Area: Ha Long Bay, Lan Ha Bay, northern scenery/cruise intent, comparison intent, destination pillar, homepage shelf, Compare hub, Destinations hub, internal-link spine, premium editorial UX, EEAT
- Command/check: remote backup to `/root/vietnamguide-backups/20260718-pre-ha-long-cluster`; SFTP upload of `ops/apply-ha-long-lan-ha-comparison-guide.php`, `ops/apply-ha-long-bay-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l` on all four scripts; forced WP-CLI publish of `Ha Long Bay vs Lan Ha Bay` first via `VG_FORCE_HA_LONG_LAN_HA_COMPARISON_REPUBLISH=1 wp eval-file ops/apply-ha-long-lan-ha-comparison-guide.php --allow-root`; forced WP-CLI publish of `Ha Long Bay Travel Guide` second via `VG_FORCE_HA_LONG_BAY_GUIDE_REPUBLISH=1 wp eval-file ops/apply-ha-long-bay-travel-guide.php --allow-root`; homepage refresh; full `wp eval-file ops/verify-eeat-content.php --allow-root`; rewrite flush; object cache flush; Rank Math XML cache deletion; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTML marker sweep; homepage row check; Compare and Destinations hub note checks; sitemap check; image URL liveness checks with Wikimedia rate-limit retry; official source URL liveness checks.
- Expected result: `/compare/ha-long-bay-vs-lan-ha-bay/` and `/destinations/ha-long-bay-travel-guide/` publish as the northern bay/cruise decision cluster for international travelers, with comparison-first buying judgment, cruise length/route logic, licensed real images, source/update discipline, route-fit links, and no generic cruise-list or OTA-led authority framing.
- Actual outcome: page ID `21` published successfully at `https://vietnamguide.net/compare/ha-long-bay-vs-lan-ha-bay/`; page ID `195` published successfully at `https://vietnamguide.net/destinations/ha-long-bay-travel-guide/`. Remote PHP lint passed for all four touched scripts. EEAT verification passed. Public HTML returned HTTP `200`, one H1 on each page, no unresolved editorial placeholders, and all comparison markers rendered: `vg-ha-long-lan-ha-hero:v1`, `vg-ha-long-lan-ha-concierge-verdict`, `vg-ha-long-lan-ha-at-a-glance:v1`, `vg-ha-long-lan-ha-photo-grid:v1`, `vg-ha-long-lan-ha-decision-matrix:v1`, `vg-ha-long-lan-ha-cruise-style:v1`, `vg-ha-long-lan-ha-route-fit:v1`, `vg-ha-long-lan-ha-weather-cancellation:v1`, `vg-ha-long-lan-ha-skip-logic:v1`, `vg-ha-long-lan-ha-live-checks:v1`, and `vg-ha-long-lan-ha-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. The Ha Long Bay pillar rendered all required markers: `vg-ha-long-bay-hero:v1`, `vg-ha-long-bay-concierge-verdict`, `vg-ha-long-bay-at-a-glance:v1`, `vg-ha-long-bay-photo-grid:v1`, `vg-ha-long-bay-days-cruise:v1`, `vg-ha-long-bay-where-to-base:v1`, `vg-ha-long-bay-priority-map:v1`, `vg-ha-long-bay-best-time-weather:v1`, `vg-ha-long-bay-route-fit:v1`, `vg-ha-long-bay-booking-checks:v1`, `vg-ha-long-bay-skip-logic:v1`, `vg-ha-long-bay-live-checks:v1`, and `vg-ha-long-bay-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage now shows both Ha Long guide rows and bay/cruise decision modules. Compare hub includes the Ha Long/Lan Ha note. Destinations hub includes the Ha Long Bay note. Vietnam Travel Guide, Best Places, UNESCO Heritage Sites, Ninh Binh, North vs Central vs South, Best Time, Transport, Travel Cost, 10 Days, 14 Days, Health/Insurance, and Safety related-route metadata now include the relevant Ha Long cluster links. Rank Math `/page-sitemap.xml` includes both new URLs. Image checks returned HTTP `200` for Ha Long aerial, Lan Ha Bay, Cat Ba Island, Ha Long cruise boats, Sung Sot Cave, Titov Island, Cat Ba National Park, and the homepage Lan Ha thumbnail; three Wikimedia HEAD checks initially returned temporary `429` locally, then returned `200` from the VPS with delay and a browser-like User-Agent. Official source checks returned HTTP `200` for Vietnam.travel Ha Long, Vietnam.travel Northern Vietnam, Vietnam.travel weather/climate, Vietnam.travel transport, Ha Long Bay Management, and Cat Ba tourism/service information. UNESCO Ha Long Bay-Cat Ba Archipelago returned `403` to automation because of its access challenge, so it remains logged as a source anchor with known curl limitation.
- Evidence link/path: `https://vietnamguide.net/compare/ha-long-bay-vs-lan-ha-bay/`
- Evidence link/path: `https://vietnamguide.net/destinations/ha-long-bay-travel-guide/`
- Follow-up: build Cat Ba Travel Guide next, then Bai Tu Long Bay Guide, because the Ha Long/Lan Ha cluster now has homepage, Compare hub, Destinations hub, Ninh Binh, Best Places, UNESCO, Best Time, Transport, Cost, 10 Days, and 14 Days support.

### 2026-07-18 - Cat Ba Travel Guide Publication

- Date: 2026-07-18
- Area: Cat Ba island-base intent, Lan Ha gateway planning, northern bay/island cluster, destination pillar, homepage shelf, Destinations hub, internal-link spine, premium editorial UX, EEAT
- Command/check: remote backup to `/root/vietnamguide-backups/20260718-pre-cat-ba-guide`; SFTP upload of `ops/apply-cat-ba-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l` on all three scripts; forced WP-CLI publish via `VG_FORCE_CAT_BA_GUIDE_REPUBLISH=1 wp eval-file ops/apply-cat-ba-travel-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; full `wp eval-file ops/verify-eeat-content.php --allow-root`; rewrite flush; object cache flush; Rank Math XML cache deletion; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTML marker sweep; homepage row/state check; Destinations hub note check; sitemap check; image URL liveness checks with Wikimedia rate-limit retry; official source URL liveness checks.
- Expected result: `/destinations/cat-ba-travel-guide/` publishes as the island-base and Lan Ha gateway guide for international travelers, with original route judgment, licensed real images, ferry/port and park planning logic, source/update discipline, and no generic island listicle or OTA-led authority framing.
- Actual outcome: page ID `198` published successfully at `https://vietnamguide.net/destinations/cat-ba-travel-guide/`. Remote PHP lint passed for `ops/apply-cat-ba-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. EEAT verification passed. Public HTML returned HTTP `200`, one H1, no unresolved editorial placeholders, and all Cat Ba markers rendered: `vg-cat-ba-hero:v1`, `vg-cat-ba-concierge-verdict`, `vg-cat-ba-at-a-glance:v1`, `vg-cat-ba-photo-grid:v1`, `vg-cat-ba-base-decision:v1`, `vg-cat-ba-lan-ha-gateway:v1`, `vg-cat-ba-access-logistics:v1`, `vg-cat-ba-national-park:v1`, `vg-cat-ba-route-fit:v1`, `vg-cat-ba-best-time-weather:v1`, `vg-cat-ba-booking-checks:v1`, `vg-cat-ba-skip-logic:v1`, `vg-cat-ba-live-checks:v1`, and `vg-cat-ba-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage now shows the Cat Ba guide href, the Cat Ba island-base planning state, a guide shelf row, route verdict support, related-route support, and Cat Ba photo proof. Destinations hub includes the Cat Ba note. Vietnam Travel Guide, Best Places, UNESCO Heritage Sites, Ninh Binh, Ha Long Bay Travel Guide, Ha Long Bay vs Lan Ha Bay, North vs Central vs South, Best Time, Transport, Travel Cost, 10 Days, 14 Days, Health/Insurance, and Safety related-route metadata now include Cat Ba. Rank Math `/page-sitemap.xml` includes the Cat Ba URL. Image checks returned HTTP `200` for Cat Ba Island, Arrival on Cat Ba, Lan Ha Bay, Ngu Lam/Cat Ba National Park 01, and Cat Ba beach images; Cat Ba National Park and hiking trail images returned `206` on GET retry after temporary Wikimedia `429` HEAD/range limits. Official source checks returned HTTP `200` for Cat Ba tourism/service information, Cat Ba National Park, Vietnam.travel Northern Vietnam, Vietnam.travel Ha Long, Vietnam.travel weather/climate, Vietnam.travel transport, and UNESCO Cat Ba biosphere reserve. UNESCO Ha Long Bay-Cat Ba Archipelago returned `403` to automation because of its access challenge, so it remains logged as a source anchor with known curl limitation.
- Evidence link/path: `https://vietnamguide.net/destinations/cat-ba-travel-guide/`
- Follow-up: build Bai Tu Long Bay Guide next, then Best Beaches in Vietnam, because the northern bay/island cluster now has Ha Long classic-cruise, Lan Ha comparison, Cat Ba island-base, Ninh Binh landscape, and homepage route-decision support.

### 2026-07-18 - Bai Tu Long Bay Guide Publication

- Date: 2026-07-18
- Area: Bai Tu Long quieter-bay intent, northern cruise decision support, destination pillar, homepage shelf, Destinations hub, internal-link spine, premium editorial UX, EEAT
- Command/check: remote backup to `ops/backups/2026-07-18-bai-tu-long-prepublish`; SFTP upload of `ops/apply-bai-tu-long-bay-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l` on all three scripts; forced WP-CLI publish via `VG_FORCE_BAI_TU_LONG_GUIDE_REPUBLISH=1 wp eval-file ops/apply-bai-tu-long-bay-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; full `wp eval-file ops/verify-eeat-content.php --allow-root`; rewrite flush; object cache flush; Rank Math XML cache deletion; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTML marker sweep; homepage row/state check; Destinations hub note check; sitemap check; image liveness checks; official source URL liveness checks; CDP browser visual QA on desktop and mobile.
- Expected result: `/destinations/bai-tu-long-bay-guide/` publishes as the quieter-bay decision guide for international travelers, with original route judgment, licensed real images, route/pier/operator/weather/return-detail buying checks, precise UNESCO context, visible source/update discipline, and no generic alternative-bay or OTA-led authority framing.
- Actual outcome: page ID `201` published successfully at `https://vietnamguide.net/destinations/bai-tu-long-bay-guide/`. Remote PHP lint passed for `ops/apply-bai-tu-long-bay-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. EEAT verification passed. Public HTML returned HTTP `200`, one H1, no unresolved editorial placeholders, eight rendered images, and all Bai Tu Long markers rendered: `vg-bai-tu-long-hero:v1`, `vg-bai-tu-long-concierge-verdict`, `vg-bai-tu-long-at-a-glance:v1`, `vg-bai-tu-long-photo-grid:v1`, `vg-bai-tu-long-bay-choice:v1`, `vg-bai-tu-long-route-logistics:v1`, `vg-bai-tu-long-cruise-length:v1`, `vg-bai-tu-long-route-fit:v1`, `vg-bai-tu-long-best-time-weather:v1`, `vg-bai-tu-long-booking-checks:v1`, `vg-bai-tu-long-skip-logic:v1`, `vg-bai-tu-long-live-checks:v1`, and `vg-bai-tu-long-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage now shows the Bai Tu Long guide href, the quieter-bay planning state, route verdict support, related-route support, guide shelf row, Bai Tu Long photo proof, and image credit. Destinations hub includes the Bai Tu Long note. Vietnam Travel Guide, Best Places, UNESCO Heritage Sites, Ninh Binh, Ha Long Bay Travel Guide, Ha Long Bay vs Lan Ha Bay, Cat Ba, North vs Central vs South, Best Time, Transport, Travel Cost, 10 Days, 14 Days, Health/Insurance, and Safety related-route metadata now include Bai Tu Long. Rank Math `/page-sitemap.xml` includes the Bai Tu Long URL and currently lists 36 page URLs. Image HEAD checks returned HTTP `200` for Bai Tu Long Bay 01 and 02; five additional Wikimedia HEAD checks returned temporary `429` locally, but CDP browser visual QA loaded all eight page images on desktop and mobile with no horizontal overflow. Official source checks returned HTTP `200` for Ha Long Bay Management route VHL4, Ha Long-Bai Tu Long route connection context, Bai Tu Long National Park, Vietnam.travel Ha Long, Vietnam.travel Northern Vietnam, Vietnam.travel weather/climate, Vietnam.travel transport, and Quang Ninh portal. UNESCO Ha Long Bay-Cat Ba Archipelago returned `403` to automation because of its access challenge, so it remains logged as a source anchor with known curl limitation.
- Evidence link/path: `https://vietnamguide.net/destinations/bai-tu-long-bay-guide/`
- Evidence path: `.qa/cdp-bai-tu-long-visual-qa-results.json`
- Follow-up: move next to Best Beaches in Vietnam, then Da Nang vs Hoi An, because the northern bay/island cluster now has Ha Long classic-cruise, Lan Ha comparison, Cat Ba island-base, Bai Tu Long quieter-bay, Ninh Binh countryside, and homepage route-decision support.

### 2026-07-18 - Best Beaches in Vietnam Publication Source Refresh

- Date: 2026-07-18
- Area: Best Beaches in Vietnam destination pillar, beach-route planning, homepage shelf, Destinations hub, internal-link spine, source liveness, premium editorial UX, EEAT
- Command/check: SFTP upload of `ops/apply-best-beaches-vietnam-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l` on all three scripts; forced WP-CLI republish via `VG_FORCE_BEST_BEACHES_GUIDE_REPUBLISH=1 wp eval-file ops/apply-best-beaches-vietnam-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; full `wp eval-file ops/verify-eeat-content.php --allow-root`; object cache flush; rewrite flush; Rank Math XML cache deletion; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTML marker sweep; homepage row check; Destinations hub note check; sitemap check; source URL liveness checks; image URL liveness checks.
- Expected result: `/destinations/best-beaches-in-vietnam/` remains published as a route-first beach decision guide with live official source anchors, licensed real images, visible proof/update bands, and no stale `vietnamtourism.gov.vn` or outdated Mui Ne source URLs.
- Actual outcome: page ID `204` republished successfully at `https://vietnamguide.net/destinations/best-beaches-in-vietnam/`. Remote PHP lint passed for all three uploaded scripts. EEAT verification passed. Public HTML returned HTTP `200`, one H1, `vg-best-beaches-hero:v1`, `vg-best-beaches-photo-grid:v1`, `vietnam.travel/node/708`, `vietnam.travel/node/1453`, and no stale `vietnamtourism.gov.vn/en/post/1528`, `vietnamtourism.gov.vn/en/post/7646`, or `wind-sand-and-sea-mui-nes-must-do-list` anchors. Homepage shows the Best Beaches guide row. Destinations hub includes the Best Beaches note. Rank Math `/page-sitemap.xml` includes the Best Beaches URL. Official source checks returned HTTP `200` for Phu Quoc, Con Dao, Da Nang, Hoi An, Nha Trang, Mui Ne `node/708`, Quy Nhon `node/1453`, Phu Quy, weather/climate, and transport. Public image checks found eight rendered Wikimedia image URLs and all returned HTTP `200`.
- Evidence link/path: `https://vietnamguide.net/destinations/best-beaches-in-vietnam/`
- Follow-up: build Da Nang vs Hoi An next, then deepen the central-coast and beach-routing cluster with Da Nang Travel Guide, Hoi An beaches/nearby day trips, and a beach-season comparison module linked from Best Time to Visit Vietnam.

### 2026-07-18 - Da Nang vs Hoi An Comparison Guide Publication

- Date: 2026-07-18
- Area: Da Nang vs Hoi An comparison intent, central Vietnam base-choice planning, Compare hub, homepage route-decision modules, internal-link spine, source liveness, premium editorial UX, EEAT
- Command/check: SFTP upload of `ops/apply-da-nang-vs-hoi-an-comparison-guide.php`, `ops/apply-homepage-premium.php`, `ops/verify-eeat-content.php`, and `ops/apply-best-things-hoi-an-guide.php`; remote `php -l` on all four scripts; forced WP-CLI publish via `VG_FORCE_DA_NANG_HOI_AN_COMPARISON_REPUBLISH=1 wp eval-file ops/apply-da-nang-vs-hoi-an-comparison-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; full `wp eval-file ops/verify-eeat-content.php --allow-root`; object cache flush; rewrite flush; Rank Math XML cache deletion; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTML marker sweep; stale-language sweep; homepage link check; Compare hub note check; sitemap check; source URL liveness checks; image URL liveness checks; subagent spec and quality review.
- Expected result: `/compare/da-nang-vs-hoi-an/` publishes as the central Vietnam base-choice comparison for international travelers, with a clear Da Nang/Hoi An verdict, licensed real images, source/update discipline, route-fit logic, transfer/cost checks, homepage support, Compare hub support, and no stale Ha Long/Lan Ha/cruise copy from earlier northern-bay drafts.
- Actual outcome: page ID `209` published successfully at `https://vietnamguide.net/compare/da-nang-vs-hoi-an/`. Remote PHP lint passed for all four uploaded scripts. EEAT verification passed. Public HTML returned HTTP `200`, one H1, and all Da Nang/Hoi An markers rendered: `vg-da-nang-hoi-an-hero:v1`, `vg-da-nang-hoi-an-concierge-verdict`, `vg-da-nang-hoi-an-at-a-glance:v1`, `vg-da-nang-hoi-an-photo-grid:v1`, `vg-da-nang-hoi-an-decision-matrix:v1`, `vg-da-nang-hoi-an-base-chooser:v1`, `vg-da-nang-hoi-an-route-fit:v1`, `vg-da-nang-hoi-an-season-weather:v1`, `vg-da-nang-hoi-an-transfer-cost:v1`, `vg-da-nang-hoi-an-skip-logic:v1`, `vg-da-nang-hoi-an-live-checks:v1`, and `vg-da-nang-hoi-an-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Public stale-language sweep returned `0` for Ha Long, Lan Ha, Cat Ba, cruise, and legacy bay-script markers on the Da Nang/Hoi An page. Homepage contains four links to the new guide. Compare hub includes `vg-da-nang-hoi-an-compare-hub-note:v1`. Rank Math `/page-sitemap.xml` includes the Da Nang vs Hoi An URL. Official source checks returned HTTP `200` for Vietnam.travel Da Nang, Vietnam.travel Hoi An, Vietnam.travel Central Vietnam, Vietnam.travel weather/climate, Vietnam.travel transport, Vietnam.travel An Bang Beach, and Da Nang Fantasticity. UNESCO Hoi An Ancient Town returned `403` to VPS curl because of a Cloudflare challenge, but the canonical UNESCO URL was reachable through browser/web verification, so it remains logged as a source anchor with known automation limitation. Public image checks returned HTTP `200` for My Khe Beach, Dragon Bridge, Hai Van Pass, Hoi An Ancient Town, and An Bang Beach images.
- Post-review hardening: added a local static check at `ops/verify-da-nang-hoi-an-static.ps1`, changed the homepage Da Nang/Hoi An link from fallback behavior to a required published-path guard, added pipe-delimited related-route validation before metadata writes, added `VG_REPAIR_DA_NANG_HOI_AN_COMPARISON_LINKS=1` repair mode for hub and inbound related-route side effects, and tightened the EEAT verifier to require the exact official Vietnam.travel transport URL. The static check passed locally. Remote PHP lint passed again for `ops/apply-da-nang-vs-hoi-an-comparison-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. Repair mode ran successfully without rewriting the guide. Forced republish then validated the related-route metadata and republished page ID `209`. Homepage refresh and full EEAT verification passed again. Cache, rewrite, Rank Math XML, and LiteSpeed purge were repeated. Final public QA returned marker `1`, H1 `1`, official transport source `2`, stale-language sweep `0`, homepage exact links `4`, and sitemap inclusion `1`.
- Evidence link/path: `https://vietnamguide.net/compare/da-nang-vs-hoi-an/`
- Follow-up: build Da Nang Travel Guide next, then deepen the central-coast cluster with Marble Mountains/Son Tra, Hoi An beaches and nearby day trips, and a central-coast beach-season module linked from Best Time to Visit Vietnam and Best Beaches in Vietnam.

### 2026-07-18 - Da Nang Travel Guide Publication

- Date: 2026-07-18
- Area: Da Nang destination pillar, central coast airport/beach base intent, Destinations hub, homepage shelf, internal-link spine, premium editorial UX, EEAT
- Command/check: remote backup to `/root/vietnamguide-backups/20260718-pre-da-nang-guide`; SFTP upload of `ops/apply-da-nang-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l`; forced WP-CLI publish via `VG_FORCE_DA_NANG_GUIDE_REPUBLISH=1 wp eval-file ops/apply-da-nang-travel-guide.php --allow-root`; homepage refresh; full `wp eval-file ops/verify-eeat-content.php --allow-root`; WordPress object cache flush; rewrite flush; Rank Math XML cache delete; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; local static Da Nang checker; public HTML marker sweep; homepage row check; Destinations hub note check; sitemap check; exact Central Vietnam source href check; image URL liveness checks; official source URL liveness checks; follow-up republish after strengthening related-route upsert and exact source matching.
- Expected result: `/destinations/da-nang-travel-guide/` publishes as the airport-and-beach city-base guide for international travelers, with a concierge verdict, at-a-glance decision table, licensed photo proof, base-area table, route-fit logic, season/weather pivots, day-trip and skip logic, live official checks, source/update discipline, and inbound related-route updates from the central planning spine.
- Actual outcome: page ID `213` published successfully at `https://vietnamguide.net/destinations/da-nang-travel-guide/`. Remote PHP lint passed for `ops/apply-da-nang-travel-guide.php` and `ops/verify-eeat-content.php`. Full EEAT verification passed after tightening related-route validation to require a structured `Label | /path/ | reason` line and exact `href="https://vietnam.travel/places-to-go/central-vietnam"` source matching. Public HTML returned HTTP `200`, one H1, no stale Ha Long/Lan Ha/Cat Ba/cruise terms, no editorial placeholder language, homepage exact Da Nang link count `4`, Destinations hub note present, and sitemap inclusion `1`. Public guide markers rendered: `vg-da-nang-guide-hero:v1`, `vg-da-nang-guide-concierge-verdict`, `vg-da-nang-guide-at-a-glance:v1`, `vg-da-nang-guide-photo-grid:v1`, `vg-da-nang-guide-priority-map:v1`, `vg-da-nang-guide-base-areas:v1`, `vg-da-nang-guide-route-fit:v1`, `vg-da-nang-guide-season-weather:v1`, `vg-da-nang-guide-day-trips:v1`, `vg-da-nang-guide-skip-logic:v1`, `vg-da-nang-guide-live-checks:v1`, `vg-da-nang-guide-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Public image QA returned HTTP `200` for My Khe Beach, Dragon Bridge, Lady Buddha, Marble Mountains, Hai Van Pass, and Golden Bridge thumbnails; earlier Wikimedia automation had transient `429` responses on some alternate image endpoints, so the final production images were switched to more stable thumbnail URLs. Official source checks returned HTTP `200` for Vietnam.travel Da Nang, Vietnam.travel Central Vietnam, Vietnam.travel weather and climate, and Vietnam.travel transport; Da Nang Fantasticity remained reachable through browser/web verification but returned a redirect/error on local curl. The page body now uses the cleaner field note phrasing, “filler night between famous names,” instead of placeholder language.
- Evidence link/path: `https://vietnamguide.net/destinations/da-nang-travel-guide/`
- Follow-up: keep the central-coast cluster synchronized with Da Nang vs Hoi An, Best Things to Do in Hoi An, Best Things to Do in Hue, Best Beaches in Vietnam, and the route-planning spine.

### 2026-07-18 - Da Nang Travel Guide Final QA Refresh

- Date: 2026-07-18
- Area: Da Nang guide final production refresh after 1280px Hai Van Pass / Golden Bridge image URL adjustment, homepage refresh, sitemap/cache recheck, public QA
- Command/check: forced WP-CLI republish via `VG_FORCE_DA_NANG_GUIDE_REPUBLISH=1 wp eval-file ops/apply-da-nang-travel-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; full `wp eval-file ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache cleanup; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTML sweep; stale-copy sweep; homepage link check; Destinations hub note check; sitemap check; exact Central Vietnam source href check; Wikimedia image liveness checks with retry.
- Expected result: the Da Nang guide remains published with the stable 1280px Hai Van Pass and Golden Bridge assets, all EEAT markers still render, and the homepage/destinations/sitemap wiring stays aligned.
- Actual outcome: production refresh completed successfully. Remote republish, homepage refresh, and full EEAT verification passed. Public QA returned HTTP `200`, one H1, all guide markers present, zero stale Ha Long/Lan Ha/Cat Ba/cruise matches under word-boundary checks, zero placeholder-content matches under content-only checks, homepage exact Da Nang link count `4`, Destinations hub note present, sitemap inclusion `1`, exact `href="https://vietnam.travel/places-to-go/central-vietnam"` present, and all six guide image URLs returned live `200` responses after retry logic.
- Evidence link/path: `https://vietnamguide.net/destinations/da-nang-travel-guide/`

### 2026-07-18 - 7 Days in Vietnam Publication

- Date: 2026-07-18
- Area: 7 Days in Vietnam itinerary pillar, one-week route intent, itinerary-length ladder, homepage route-decision modules, Itineraries hub, internal-link spine, source liveness, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops/verify-7-days-guide-static.ps1`; SFTP upload of `ops/apply-7-days-itinerary-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l` on all three scripts; forced WP-CLI republish via `VG_FORCE_7_DAY_ITINERARY_REPUBLISH=1 wp eval-file ops/apply-7-days-itinerary-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; full `wp eval-file ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache correction; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTML marker sweep; homepage link count check; sitemap index and page sitemap check; source URL liveness check; Wikimedia image liveness checks with retry; subagent content/EEAT QA.
- Expected result: `/itineraries/7-days-in-vietnam/` publishes as the one-week Vietnam itinerary decision guide for international travelers, with a clear anti-whole-country verdict, north compact default, central/southern alternatives, max-two-base discipline, photo-led proof, source/update trail, homepage support, Itineraries hub support, and no thin rewritten itinerary framing.
- Actual outcome: page ID `220` published successfully at `https://vietnamguide.net/itineraries/7-days-in-vietnam/`. Local static verifier passed. Remote PHP lint passed for `ops/apply-7-days-itinerary-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. Forced republish validated internal page links and related-route metadata, skipped already-current inbound related-route refreshes where appropriate, and published the guide. Homepage refresh and full EEAT verification passed. Public HTML returned HTTP `200`, `index, follow`, one H1, Rank Math title `7 Days in Vietnam: Best One-Week Itinerary`, canonical URL, and all 7-day markers rendered: `vg-itinerary-7day-hero:v1`, `vg-itinerary-7day-concierge-verdict`, `vg-itinerary-7day-at-a-glance:v1`, `vg-itinerary-7day-photo-grid:v1`, `vg-itinerary-7day-planning-flow:v1`, `vg-itinerary-7day-route-family:v1`, `vg-itinerary-7day-transfer-pressure:v1`, `vg-itinerary-7day-day-plan:v1`, `vg-itinerary-7day-season-pivots:v1`, `vg-itinerary-7day-swap-skip:v1`, `vg-itinerary-7day-prebook-flex:v1`, `vg-itinerary-7day-mistakes:v1`, `vg-itinerary-7day-booking-sequence:v1`, and `vg-itinerary-7day-planning-audit:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage contains four exact links to `/itineraries/7-days-in-vietnam/`. The source trail was corrected from the stale Vietnam.travel Ha Long Bay URL to the live `https://vietnam.travel/places-to-go/northern-vietnam/ha-long` URL. Rank Math initially served an older static XML cache from `wp-content/uploads/rank-math/rank_math_4532298b2bda6b14edfe4c5c7cd1eff8.xml`, so `/page-sitemap.xml` did not include the new URL; deleting the tracked `rank_math_*.xml` files and `rank_math_sitemap_cache_files` option, flushing cache, and requesting the sitemap regenerated it. Final public `/page-sitemap.xml` returned HTTP `200`, length `8002`, and includes `https://vietnamguide.net/itineraries/7-days-in-vietnam/`; sitemap index lastmod updated to `2026-07-18T13:39:00+00:00`. Public image checks returned HTTP `200` for the Lan Ha and Hue Wikimedia thumbnails after retry; earlier automation saw temporary Wikimedia `429` responses, so this remains logged as a known rate-limit behavior rather than a broken content asset.
- Evidence link/path: `https://vietnamguide.net/itineraries/7-days-in-vietnam/`
- Follow-up: deepen the itinerary-length ladder with 5 Days in Vietnam, Vietnam with Kids, Vietnam for Couples, first-time north-only variants, and decision modules that route one-week readers toward 10 days when the cut list is too painful.

### 2026-07-18 - 21 Days in Vietnam Publication

- Date: 2026-07-18
- Area: 21 Days in Vietnam itinerary pillar, three-week route intent, itinerary-length ladder, homepage route-decision modules, Itineraries hub, internal-link spine, source liveness, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops/verify-21-days-guide-static.ps1`; SFTP upload of `ops/apply-21-days-itinerary-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l` on all three scripts; forced WP-CLI republish via `VG_FORCE_21_DAY_ITINERARY_REPUBLISH=1 wp eval-file ops/apply-21-days-itinerary-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; full `wp eval-file ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache correction; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTML marker sweep; homepage link count check; sitemap index and page sitemap check; source URL liveness check; production robots/canonical check.
- Expected result: `/itineraries/21-days-in-vietnam/` publishes as the three-week Vietnam itinerary decision guide for international travelers, with a clear full-country route with margin, one deliberate extension, 14-day fallback logic, photo-led proof, source/update trail, homepage support, Itineraries hub support, and no thin rewritten itinerary framing.
- Actual outcome: page ID `224` published successfully at `https://vietnamguide.net/itineraries/21-days-in-vietnam/`. Local static verifier passed. Remote PHP lint passed for `ops/apply-21-days-itinerary-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. Forced republish validated internal page links and related-route metadata, refreshed the Itineraries hub 21-day note, upserted inbound related routes on Vietnam Travel Guide, Best Time, Transport, Cost, North vs Central vs South, Best Places, Best Beaches, Hanoi, Ninh Binh, Ha Long Bay, Ha Long Bay vs Lan Ha Bay, Cat Ba, Hoi An, Da Nang vs Hoi An, Da Nang Travel Guide, 10 Days, and 14 Days, then published the guide. Homepage refresh and full EEAT verification passed after one verifier fix to include `vg_eeat_related_routes` in public evidence text. Public HTML returned HTTP `200`, `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, one H1, Rank Math title `21 Days in Vietnam: Best Three-Week Route for First Trips`, canonical URL, and all 21-day markers rendered: `vg-itinerary-21day-hero:v1`, `vg-itinerary-21day-concierge-verdict`, `vg-itinerary-21day-at-a-glance:v1`, `vg-itinerary-21day-photo-grid:v1`, `vg-itinerary-21day-planning-flow:v1`, `vg-itinerary-21day-route-family:v1`, `vg-itinerary-21day-transfer-pressure:v1`, `vg-itinerary-21day-day-plan:v1`, `vg-itinerary-21day-extension-matrix:v1`, `vg-itinerary-21day-season-pivots:v1`, `vg-itinerary-21day-swap-skip:v1`, `vg-itinerary-21day-prebook-flex:v1`, `vg-itinerary-21day-mistakes:v1`, `vg-itinerary-21day-booking-sequence:v1`, and `vg-itinerary-21day-planning-audit:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage contains five exact links to `/itineraries/21-days-in-vietnam/`. Rank Math `/page-sitemap.xml` includes the new URL. WordPress cache flush, rewrite flush, Rank Math sitemap cache cleanup, and LiteSpeed purge-all all completed successfully. Public source QA confirmed canonical and robots meta, and the page body presents the full-country route with margin plus 14-day fallback logic rather than a thin copied two-week itinerary.
- Evidence link/path: `https://vietnamguide.net/itineraries/21-days-in-vietnam/`
- Follow-up: use this as the three-week itinerary ladder anchor, then publish Hoi An vs Hue and any 5-day short-trip page only if Search Console shows a real gap in short-form intent.

### 2026-07-18 - Hoi An vs Hue Comparison Guide Publication

- Date: 2026-07-18
- Area: Hoi An vs Hue comparison intent, central Vietnam heritage-base planning, Compare hub, homepage heritage route modules, internal-link spine, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops/verify-hoi-an-hue-static.ps1`; remote backup to `/root/vietnamguide-backups/20260718-222501-pre-hoi-an-hue`; SFTP upload of `ops/apply-hoi-an-vs-hue-comparison-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l` on all three scripts; forced WP-CLI publish via `VG_FORCE_HOI_AN_HUE_COMPARISON_REPUBLISH=1 wp eval-file ops/apply-hoi-an-vs-hue-comparison-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; full `wp eval-file ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public page QA; homepage link count check; Compare hub note check; sitemap check; source URL liveness checks; image URL liveness checks.
- Expected result: `/compare/hoi-an-vs-hue/` publishes as the central Vietnam heritage-base comparison for international travelers, with a clear Hoi An/Hue verdict, photo-led proof, route-fit logic, transfer-pressure checks, season pivots, keep-both and skip logic, booking/source checks, homepage support, Compare hub support, and no stale Da Nang/Hoi An or bay-cruise copy.
- Actual outcome: page ID `227` published successfully at `https://vietnamguide.net/compare/hoi-an-vs-hue/`. Remote PHP lint passed for `ops/apply-hoi-an-vs-hue-comparison-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. EEAT verification passed. Public HTML returned HTTP `200`, one H1, canonical `https://vietnamguide.net/compare/hoi-an-vs-hue/`, robots `index, follow`, and all guide markers rendered: `vg-hoi-an-hue-hero:v1`, `vg-hoi-an-hue-concierge-verdict`, `vg-hoi-an-hue-at-a-glance:v1`, `vg-hoi-an-hue-photo-grid:v1`, `vg-hoi-an-hue-decision-matrix:v1`, `vg-hoi-an-hue-base-choice:v1`, `vg-hoi-an-hue-heritage-fit:v1`, `vg-hoi-an-hue-route-fit:v1`, `vg-hoi-an-hue-transfer-pressure:v1`, `vg-hoi-an-hue-season-weather:v1`, `vg-hoi-an-hue-keep-both:v1`, `vg-hoi-an-hue-skip-logic:v1`, `vg-hoi-an-hue-booking-checks:v1`, and `vg-hoi-an-hue-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage contains four links to `/compare/hoi-an-vs-hue/`. Compare hub includes `vg-hoi-an-hue-compare-hub-note:v1`. Rank Math `/page-sitemap.xml` includes the Hoi An vs Hue URL. The guide upserted inbound related routes on Vietnam Travel Guide, Best Places, UNESCO Heritage Sites, Best Things to Do in Hoi An, Best Things to Do in Hue, Da Nang Travel Guide, Da Nang vs Hoi An, North vs Central vs South, Best Time, Transport, Travel Cost, 10 Days, 14 Days, and 21 Days. Official source checks returned HTTP `200` for Vietnam.travel Hoi An, Vietnam.travel Hue, Vietnam.travel weather/climate, Vietnam.travel transport, and Hue Monuments Conservation Centre. UNESCO Hoi An Ancient Town and UNESCO Complex of Hue Monuments returned `403` to automation because of access controls, so they remain logged as canonical source anchors with known curl limitation. Image checks returned HTTP `200` for Hoi An Ancient Town, Japanese Covered Bridge, Hue Citadel, Thien Mu Pagoda, and the final stable Perfume River original image URL. The Perfume River thumbnail was replaced with the original Wikimedia file URL after the thumbnail endpoint returned temporary `429` to automation.
- Evidence link/path: `https://vietnamguide.net/compare/hoi-an-vs-hue/`
- Follow-up: build Best Islands in Vietnam next, then Phu Quoc Travel Guide, because the central Vietnam comparison cluster is now strong enough to shift into island/winter-sun intent without weakening the homepage route spine.

### 2026-07-19 - Best Islands in Vietnam Publication

- Date: 2026-07-19
- Area: Best Islands in Vietnam destination pillar, island/winter-sun intent, source-diversity standard, Destinations hub, homepage shelf, internal-link spine, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops/verify-best-islands-static.ps1`; remote backup to `/root/vietnamguide-backups/20260719-pre-best-islands`; SFTP upload of `ops/apply-best-islands-vietnam-guide.php`, `ops/apply-homepage-premium.php`, `ops/verify-eeat-content.php`, and `ops/verify-best-islands-static.ps1`; remote `php -l` on the three PHP scripts; forced WP-CLI publish via `VG_FORCE_BEST_ISLANDS_GUIDE_REPUBLISH=1 wp eval-file ops/apply-best-islands-vietnam-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; full `wp eval-file ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache cleanup; LiteSpeed purge-all; public HTML marker sweep; homepage link count check; Destinations hub note check; sitemap check; source URL liveness checks; Wikimedia image liveness checks with VPS retry after local rate limits.
- Expected result: `/destinations/best-islands-in-vietnam/` publishes as the island route-fit guide for international travelers choosing between Phu Quoc, Con Dao, Cat Ba, Cham Islands, Ly Son, Phu Quy, Nam Du, and Co To, with a clear concierge verdict, diverse source trail, licensed real images, route/season/logistics/skip discipline, homepage support, and no thin prettiest-islands listicle framing.
- Actual outcome: page ID `231` published successfully at `https://vietnamguide.net/destinations/best-islands-in-vietnam/`. Local static verifier passed. Remote PHP lint passed for `ops/apply-best-islands-vietnam-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. Forced publish created the guide, refreshed the Destinations hub note, and added inbound related-route metadata on Vietnam Travel Guide, Best Places, Best Beaches, North vs Central vs South, Best Time, Transport, Travel Cost, 10 Days, 14 Days, 21 Days, Cat Ba, Ha Long Bay, Ha Long Bay vs Lan Ha Bay, Hoi An, Hoi An vs Hue, Da Nang, Health/Insurance, and Safety. Homepage refresh passed, and full EEAT verification passed. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/best-islands-in-vietnam/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, one H1, and all Best Islands markers rendered: `vg-best-islands-hero:v1`, `vg-best-islands-concierge-verdict`, `vg-best-islands-at-a-glance:v1`, `vg-best-islands-photo-grid:v1`, `vg-best-islands-source-diversity:v1`, `vg-best-islands-decision-matrix:v1`, `vg-best-islands-route-fit:v1`, `vg-best-islands-season-weather:v1`, `vg-best-islands-logistics:v1`, `vg-best-islands-island-profiles:v1`, `vg-best-islands-skip-logic:v1`, `vg-best-islands-booking-checks:v1`, `vg-best-islands-live-checks:v1`, and `vg-best-islands-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage contains four exact links to the new guide. Destinations hub includes `vg-best-islands-destinations-hub-note:v1`. Rank Math `/page-sitemap.xml` includes the Best Islands URL. Public source checks returned HTTP `200` for Vietnam.travel Phu Quoc, Con Dao, Phu Quy, weather, transport, UNESCO Kien Giang, UNESCO Cat Ba, Con Dao National Park, Cat Ba National Park, Cat Ba tourism, Hoi An heritage/Cu Lao Cham-Hoi An Biosphere, Quang Ngai/Ly Son, Quang Ninh, Sa Ky schedule page, Phu Quy Express, Superdong, and Phu Quoc Express. Image checks returned `206` for Phu Quoc, Con Dao, Phu Quy, and, after VPS retry, Cat Ba, Cham Islands, Ly Son, and Co To; local Wikimedia checks temporarily returned `429` for several thumbnails, so this remains logged as an automation rate-limit behavior rather than broken assets.
- Evidence link/path: `https://vietnamguide.net/destinations/best-islands-in-vietnam/`
- Follow-up: build Phu Quoc Travel Guide next, because Best Islands now gives the island decision hub, Best Beaches gives the beach-route frame, and homepage/route modules can push high-intent winter-sun and resort-planning traffic into a dedicated Phu Quoc pillar.

### 2026-07-19 - Phu Quoc Travel Guide Publication

- Date: 2026-07-19
- Area: Phu Quoc destination pillar, island/winter-sun resort intent, source-diversity standard, Destinations hub, homepage shelf, internal-link spine, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops/verify-phu-quoc-static.ps1`; SFTP upload of `ops/apply-phu-quoc-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l` on all three scripts; forced WP-CLI publish via `VG_FORCE_PHU_QUOC_GUIDE_REPUBLISH=1 wp eval-file ops/apply-phu-quoc-travel-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; full `wp eval-file ops/verify-eeat-content.php --allow-root`; WordPress object cache flush; rewrite flush; Rank Math XML cache cleanup; LiteSpeed purge-all; public HTML marker sweep; homepage link count check; Destinations hub note check; sitemap check; official source URL liveness checks; Wikimedia image liveness checks with VPS retry after local rate limits.
- Expected result: `/destinations/phu-quoc-travel-guide/` publishes as the dedicated island resort and winter-sun route-fit guide for international travelers, with a clear concierge verdict, area/stay logic, flight/ferry and visa live-check discipline, diverse source trail, licensed real images, homepage support, and no thin beach-list or resort-brochure framing.
- Actual outcome: page ID `234` published successfully at `https://vietnamguide.net/destinations/phu-quoc-travel-guide/`. Local static verifier passed. Remote PHP lint passed for `ops/apply-phu-quoc-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. Forced publish created the guide, refreshed the Destinations hub note, added inbound related-route metadata to strategic planning, itinerary, beach, island, logistics, and safety pages, then refreshed the homepage. Full EEAT verification passed. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/phu-quoc-travel-guide/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, one H1, and all Phu Quoc markers rendered: `vg-phu-quoc-hero:v1`, `vg-phu-quoc-concierge-verdict`, `vg-phu-quoc-at-a-glance:v1`, `vg-phu-quoc-photo-grid:v1`, `vg-phu-quoc-source-diversity:v1`, `vg-phu-quoc-route-fit:v1`, `vg-phu-quoc-where-to-stay:v1`, `vg-phu-quoc-beach-areas:v1`, `vg-phu-quoc-priority-map:v1`, `vg-phu-quoc-season-weather:v1`, `vg-phu-quoc-transport-logistics:v1`, `vg-phu-quoc-cost-booking:v1`, `vg-phu-quoc-skip-logic:v1`, `vg-phu-quoc-live-checks:v1`, and `vg-phu-quoc-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage contains three exact links to the Phu Quoc guide. Destinations hub includes the Phu Quoc note. Rank Math `/page-sitemap.xml` includes the Phu Quoc URL. Public source checks returned HTTP `200` for Vietnam.travel Phu Quoc, weather/climate, transport, getting-to-Vietnam, visa requirements, official eVisa portal, UNESCO Kien Giang, Vietnam NBCA Phu Quoc National Park, Phu Quoc local portal, An Giang context, NCHMF weather, Phu Quoc airport operator, Phu Quoc Express Ha Tien/Rach Gia, Superdong Ha Tien/Rach Gia, Thanh Thoi Ferry, Sun World Hon Thom, and VinWonders Phu Quoc. Image QA from the VPS returned HTTP `200` for all rendered page image URLs after local Wikimedia rate-limit behavior.
- Evidence link/path: `https://vietnamguide.net/destinations/phu-quoc-travel-guide/`
- Follow-up: build Con Dao Travel Guide next because the island cluster now has Best Islands, Best Beaches, Cat Ba, and Phu Quoc support; then evaluate Phu Quoc vs Nha Trang once Search Console shows enough beach comparison demand.

### 2026-07-20 - Con Dao Travel Guide Publication

- Date: 2026-07-20
- Area: Con Dao destination pillar, quiet-premium island intent, source-diversity standard, Destinations hub, homepage shelf, internal-link spine, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops/verify-con-dao-static.ps1`; SFTP upload of `ops/apply-con-dao-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l` on all three scripts; forced WP-CLI publish via `VG_FORCE_CON_DAO_GUIDE_REPUBLISH=1 wp eval-file ops/apply-con-dao-travel-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; full `wp eval-file ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache cleanup; LiteSpeed purge-all; public HTML marker sweep; homepage link count check; Destinations hub note check; sitemap check; official source URL liveness checks; rendered Wikimedia image liveness checks from the VPS.
- Expected result: `/destinations/con-dao-travel-guide/` publishes as the dedicated quiet-premium island guide for international travelers, with a clear concierge verdict, nature/history/stay-area logic, flight/ferry/weather live-check discipline, diverse source trail, licensed real images, homepage support, and no thin beach-list or resort-brochure framing.
- Actual outcome: page ID `237` published successfully at `https://vietnamguide.net/destinations/con-dao-travel-guide/`. Local static verifier passed. Remote PHP lint passed for `ops/apply-con-dao-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. Forced publish created and then refreshed the guide, refreshed the Destinations hub note, added inbound related-route metadata to strategic planning, itinerary, beach, island, logistics, safety, and Phu Quoc pages, then refreshed the homepage. Full EEAT verification passed. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/con-dao-travel-guide/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, one H1, three homepage links to `/destinations/con-dao-travel-guide/`, one homepage planning row, one homepage route-verdict row, one Destinations hub note, and sitemap inclusion. All Con Dao markers rendered: `vg-con-dao-hero:v1`, `vg-con-dao-concierge-verdict`, `vg-con-dao-at-a-glance:v1`, `vg-con-dao-photo-grid:v1`, `vg-con-dao-source-diversity:v1`, `vg-con-dao-route-fit:v1`, `vg-con-dao-where-to-stay:v1`, `vg-con-dao-priority-map:v1`, `vg-con-dao-season-weather:v1`, `vg-con-dao-transport-logistics:v1`, `vg-con-dao-cost-booking:v1`, `vg-con-dao-skip-logic:v1`, `vg-con-dao-live-checks:v1`, and `vg-con-dao-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Rendered image QA found six live Wikimedia image URLs and all returned HTTP `206` range responses from the VPS. Official source checks returned HTTP `200` for Vietnam.travel Con Dao, slow-travel Con Dao, beach-break Con Dao, history/eco-tourism Con Dao, cuisine, weather/climate, transport, getting to Vietnam, visa requirements, Con Dao National Park, Con Dao park home, Con Dao tourism portal, Con Dao national tourism-site context, Superdong Soc Trang-Con Dao, Phu Quoc Express Con Dao experience/cost context, and NCHMF. The official eVisa portal and ACV Con Dao Airport returned HTTP `200` from local curl, while VPS curl returned `000` automation reachability responses, so they remain logged as live-check source anchors with known server-side curl limitation.
- Evidence link/path: `https://vietnamguide.net/destinations/con-dao-travel-guide/`
- Follow-up: move next to Phu Quoc vs Nha Trang or a Cham Islands / Ly Son central-island add-on, depending on Search Console island and beach-comparison demand; keep 5 Days in Vietnam held until short-trip demand is clear.

### 2026-07-20 - Phu Quoc vs Nha Trang Comparison Guide Publication

- Date: 2026-07-20
- Area: Phu Quoc vs Nha Trang beach-comparison intent, island-resort vs city-beach decision support, Compare hub, homepage route modules, internal-link spine, source-diversity standard, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops/verify-phu-quoc-vs-nha-trang-static.ps1`; SFTP upload of `ops/apply-phu-quoc-vs-nha-trang-comparison-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l` on all three scripts; forced WP-CLI publish via `VG_FORCE_PHU_QUOC_NHA_TRANG_COMPARISON_REPUBLISH=1 wp eval-file /tmp/vg-con-dao/ops/apply-phu-quoc-vs-nha-trang-comparison-guide.php --allow-root`; homepage refresh via `wp eval-file /tmp/vg-con-dao/ops/apply-homepage-premium.php --allow-root`; full `wp eval-file /tmp/vg-con-dao/ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache-file deletion and option cleanup; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTML marker sweep; homepage link count check; Compare hub note check; sitemap regeneration check; official source URL liveness checks; Wikimedia image liveness checks from the VPS.
- Expected result: `/compare/phu-quoc-vs-nha-trang/` publishes as the beach-base comparison for international travelers deciding between Phu Quoc's resort/island-recovery role and Nha Trang's city-beach/coastal-base role, with photo-led proof, clear skip logic, live logistics checks, homepage and Compare hub support, licensed real images, and no thin rewritten beach-list framing.
- Actual outcome: page ID `241` published successfully at `https://vietnamguide.net/compare/phu-quoc-vs-nha-trang/`. Local static verifier passed. Remote PHP lint passed for `ops/apply-phu-quoc-vs-nha-trang-comparison-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. Forced publish created the guide, refreshed the Compare hub note, added inbound related-route metadata to strategic planning, itinerary, beach, island, logistics, and Phu Quoc pages, then refreshed the homepage. Full EEAT verification passed. Public HTML returned HTTP `200`, title `Phu Quoc vs Nha Trang: Which Beach Is Better?`, canonical `https://vietnamguide.net/compare/phu-quoc-vs-nha-trang/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, one H1, four homepage links to `/compare/phu-quoc-vs-nha-trang/`, one Compare hub note, and all comparison markers rendered: `vg-phu-quoc-nha-trang-hero:v1`, `vg-phu-quoc-nha-trang-concierge-verdict`, `vg-phu-quoc-nha-trang-at-a-glance:v1`, `vg-phu-quoc-nha-trang-photo-grid:v1`, `vg-phu-quoc-nha-trang-source-diversity:v1`, `vg-phu-quoc-nha-trang-decision-matrix:v1`, `vg-phu-quoc-nha-trang-route-fit:v1`, `vg-phu-quoc-nha-trang-season-weather:v1`, `vg-phu-quoc-nha-trang-logistics:v1`, `vg-phu-quoc-nha-trang-cost-booking:v1`, `vg-phu-quoc-nha-trang-skip-logic:v1`, `vg-phu-quoc-nha-trang-live-checks:v1`, and `vg-phu-quoc-nha-trang-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. The first public sitemap check reproduced stale Rank Math XML cache behavior: `/page-sitemap.xml` returned HTTP `200` but did not include the new URL while `wp-content/uploads/rank-math/rank_math_*.xml` files were older than the newly published page. Deleting those XML cache files plus `rank_math_sitemap_cache_files`, flushing WordPress cache, and purging LiteSpeed regenerated the sitemap; final `/page-sitemap.xml` includes the new URL. Rendered image QA from the VPS returned HTTP `206` for Nha Trang Beach 3, Kem Beach Phu Quoc, Po Nagar, Nha Trang coastline panorama, and Cam Ranh Terminal 2 images. Official source checks returned HTTP `200` for Vietnam.travel Phu Quoc, Vietnam.travel Nha Trang, Vietnam.travel weather/climate, Vietnam.travel transport, Cam Ranh International Airport, and NCHMF. The official eVisa portal returned HTTP `200` from local curl, while VPS curl returned `000`, so it remains logged as a live-check source anchor with known server-side curl limitation.
- Evidence link/path: `https://vietnamguide.net/compare/phu-quoc-vs-nha-trang/`
- Follow-up: build a Nha Trang Travel Guide if beach-city and Cam Ranh access demand grows, or build Cham Islands / Ly Son next if the editorial calendar needs a central-island add-on that supports Hoi An, Da Nang, Best Islands, and beach-routing clusters.

### 2026-07-20 - Nha Trang Travel Guide Publication

- Date: 2026-07-20
- Area: Nha Trang destination pillar, city-beach and Cam Ranh access intent, Destinations hub, homepage route modules, internal-link spine, source-diversity standard, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-nha-trang-static.ps1`; SFTP upload of `ops/apply-nha-trang-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l` on all three scripts; forced WP-CLI publish via `VG_FORCE_NHA_TRANG_GUIDE_REPUBLISH=1 wp eval-file /tmp/vg-con-dao/ops/apply-nha-trang-travel-guide.php --allow-root`; homepage refresh via `wp eval-file /tmp/vg-con-dao/ops/apply-homepage-premium.php --allow-root`; full `wp eval-file /tmp/vg-con-dao/ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion and option cleanup; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTTPS QA; homepage direct-link check; Destinations hub note check; sitemap inclusion check; official source URL checks; Wikimedia image and license URL checks.
- Expected result: `/destinations/nha-trang-travel-guide/` publishes as the active city-beach/coastal-base guide for international travelers deciding whether Nha Trang should earn nights by beach access, seafood, Po Nagar, Long Son, bay/boat options, Cam Ranh transfer logic, hotel-area trade-offs, cost checks, and skip logic rather than thin generic attraction copy.
- Actual outcome: page ID `244` published successfully at `https://vietnamguide.net/destinations/nha-trang-travel-guide/`. Local static verifier passed. Remote PHP lint passed for `ops/apply-nha-trang-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. Forced publish created the guide, refreshed the Destinations hub note, added inbound related-route metadata to strategic planning, itinerary, beach, island, comparison, logistics, money, SIM/eSIM, cost, insurance, and safety pages, then refreshed the homepage. Full EEAT verification passed. Cache/rewrite/Rank Math XML/LiteSpeed purge completed successfully. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/nha-trang-travel-guide/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, one H1, visible concierge verdict, photo-proof section, NBCA Nha Trang Bay Nature Reserve source anchor, Money and SIM/eSIM links, homepage direct link and planning row, Destinations hub note, and Rank Math `/page-sitemap.xml` inclusion. Source checks returned HTTP `200` for Vietnam.travel Nha Trang, Vietnam.travel weather/climate, Vietnam.travel transport, Cam Ranh International Airport, NBCA Nha Trang Bay Nature Reserve, official eVisa, NCHMF, Khanh Hoa tourism pages via curl, all six Wikimedia license pages, and all seven Wikimedia image URLs.
- Evidence link/path: `https://vietnamguide.net/destinations/nha-trang-travel-guide/`
- Follow-up: deepen the south-central coast cluster with Mui Ne vs Nha Trang, Quy Nhon Travel Guide, Cham Islands / Ly Son support pages, and a Best Beach Cities in Vietnam comparison only after Search Console shows enough beach-city demand.

### 2026-07-20 - Mui Ne vs Nha Trang Comparison Guide Publication

- Date: 2026-07-20
- Area: Mui Ne vs Nha Trang beach-base comparison intent, south-central coast cluster, wind-sport coast vs active city-beach decision support, Compare hub, homepage route modules, internal-link spine, source-diversity standard, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-mui-ne-vs-nha-trang-static.ps1`; SFTP upload of `ops/apply-mui-ne-vs-nha-trang-comparison-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l` on all three PHP scripts; forced WP-CLI publish via `VG_FORCE_MUI_NE_NHA_TRANG_COMPARISON_REPUBLISH=1 wp eval-file /tmp/vg-con-dao/ops/apply-mui-ne-vs-nha-trang-comparison-guide.php --allow-root`; homepage refresh via `wp eval-file /tmp/vg-con-dao/ops/apply-homepage-premium.php --allow-root`; full `wp eval-file /tmp/vg-con-dao/ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion and option cleanup; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTTPS QA; homepage direct-link check; Compare hub note check; sitemap inclusion check; official source URL checks; Wikimedia image checks with VPS retry for temporary automation rate limits.
- Expected result: `/compare/mui-ne-vs-nha-trang/` publishes as a route-first comparison guide for international travelers deciding whether south-central beach time should be a Phan Thiet/Mui Ne wind-sport/open-coast chapter, an active Nha Trang city-beach base, or a skipped detour, with photo-led proof, source/update discipline, homepage and Compare hub support, and no generic “best beach” spam framing.
- Actual outcome: page ID `247` published successfully at `https://vietnamguide.net/compare/mui-ne-vs-nha-trang/`. Local static verifier passed. Remote PHP lint passed for `ops/apply-mui-ne-vs-nha-trang-comparison-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. Forced publish created the guide, refreshed the Compare hub note, added inbound related-route metadata to Vietnam Travel Guide, Best Places, Best Beaches, Nha Trang, Phu Quoc vs Nha Trang, Da Nang, North/Central/South, Best Time, Transport, Cost, Money, SIM/eSIM, 10/14/21-day itinerary pages, E-Visa, Health/Insurance, and Safety, then refreshed the homepage. Code-review follow-up changed homepage wiring from required-path to fallback-path so homepage refresh is no longer fragile if run before the guide exists, and added all-of EEAT verifier checks for source anchors plus visible image credits. Full EEAT verification passed after those stricter checks. Cache/rewrite/Rank Math XML/LiteSpeed purge completed successfully. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/compare/mui-ne-vs-nha-trang/`, robots `index, follow`, one H1, all Mui Ne/Nha Trang markers rendered, visible `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, `vg-related-routes`, homepage direct link, Compare hub note `vg-mui-ne-nha-trang-compare-hub-note:v1`, and Rank Math `/page-sitemap.xml` inclusion. Source checks returned HTTP `200` for Vietnam.travel Binh Thuan, Vietnam.travel Mui Ne, Vietnam.travel Nha Trang, Cam Ranh International Airport, Vietnam.travel weather/climate, Vietnam.travel transport, Vietnam Railways, official eVisa, and NCHMF; Khanh Hoa tourism returned `200` with `curl -k`, matching the known TLS automation behavior logged for Nha Trang. Image checks returned HTTP `200` for Mui Ne kitesurfing, Mui Ne dunes, Nha Trang Beach, and Nha Trang coastline; Fairy Stream and Mui Ne fishing village returned temporary local Wikimedia `429` on HEAD but returned `206` range responses from the VPS, so they remain logged as live assets with known automation rate-limit behavior.
- Evidence link/path: `https://vietnamguide.net/compare/mui-ne-vs-nha-trang/`
- Follow-up: build Quy Nhon Travel Guide next to complete a quieter south-central coast pillar, then consider Cham Islands / Ly Son as central-island support for Hoi An/Da Nang/Best Islands before any broader Best Beach Cities comparison.

### 2026-07-22 - Quy Nhon Travel Guide Publication and Asset Verification

- Date: 2026-07-22
- Area: Quy Nhon destination pillar, quiet mainland coast intent, south-central coast cluster, Destinations hub, homepage route modules, internal-link spine, licensed photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-quy-nhon-static.ps1`; SFTP upload of `ops/apply-quy-nhon-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l` on all three PHP scripts; forced WP-CLI publish via `VG_FORCE_QUY_NHON_GUIDE_REPUBLISH=1 wp eval-file /tmp/vg-quy-nhon/ops/apply-quy-nhon-travel-guide.php --allow-root`; homepage refresh via `wp eval-file /tmp/vg-quy-nhon/ops/apply-homepage-premium.php --allow-root`; full `wp eval-file /tmp/vg-quy-nhon/ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion and option cleanup; LiteSpeed purge-all; public HTTPS QA with literal `.Contains()` checks; homepage direct-link check; Destinations hub note check; sitemap inclusion check; official source URL liveness checks; rendered Wikimedia image liveness checks from the VPS.
- Expected result: `/destinations/quy-nhon-travel-guide/` publishes as the quiet mainland coast guide for international travelers deciding whether Quy Nhon earns nights as a softer alternative to busier beach bases, with a clear concierge verdict, Ky Co/Eo Gio/city-beach proof, stay-area logic, Phu Cat airport and rail checks, cost/booking discipline, source/update trail, visible image credits, homepage and Destinations hub support, and no thin hidden-gem or generic beach-list framing.
- Actual outcome: page ID `250` published successfully at `https://vietnamguide.net/destinations/quy-nhon-travel-guide/`. Local static verifier passed. Remote PHP lint passed for `ops/apply-quy-nhon-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. Forced publish refreshed the guide, confirmed internal page links and related-route metadata, refreshed the homepage, and full EEAT verification passed. Public HTML returned HTTP `200`, title `Quy Nhon Travel Guide: Quiet Beaches, Ky Co, Eo Gio and Routes`, canonical `https://vietnamguide.net/destinations/quy-nhon-travel-guide/`, robots `index, follow`, one H1, all Quy Nhon markers rendered, visible `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, `vg-related-routes`, homepage direct CTA `Read the Quy Nhon guide`, Destinations hub note `vg-quy-nhon-destinations-hub-note:v1`, and Rank Math `/page-sitemap.xml` inclusion after XML cache regeneration. Source checks returned HTTP `200` for Vietnam.travel Quy Nhon, Quy Nhon tourism introduction, Ky Co, Eo Gio, Quy Nhon Beach, Thap Doi, ACV Phu Cat Airport, Vietnam.travel weather/climate, Vietnam.travel transport, Vietnam Railways, official eVisa, and NCHMF. Initial rendered image QA found two stale live thumbnail URLs returning `404`; republishing from the current worktree replaced the promenade image with the correct Commons thumbnail path and the Phu Cat airport image with the original Commons file URL. Final VPS image QA returned HTTP `206` for all seven rendered Wikimedia image URLs.
- Evidence link/path: `https://vietnamguide.net/destinations/quy-nhon-travel-guide/`
- Follow-up: build Cham Islands Travel Guide or Ly Son Travel Guide next if the editorial calendar continues the central/south-central island support cluster; otherwise deepen Quy Nhon with a future Quy Nhon vs Nha Trang comparison only after Search Console shows comparison demand.

### 2026-07-22 - Cham Islands Travel Guide Publication and QA

- Date: 2026-07-22
- Area: Cham Islands destination pillar, Hoi An marine day-trip vs overnight intent, central-coast island support cluster, Destinations hub, homepage shelf, internal-link spine, visible photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-cham-islands-static.ps1`; remote backup to `/root/vietnamguide-backups/20260722-pre-cham-islands`; SFTP upload of `ops/apply-cham-islands-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l` on all three PHP scripts; forced WP-CLI republish via `VG_FORCE_CHAM_ISLANDS_GUIDE_REPUBLISH=1 wp eval-file /tmp/vg-cham-islands/ops/apply-cham-islands-travel-guide.php --allow-root`; homepage refresh via `wp eval-file /tmp/vg-cham-islands/ops/apply-homepage-premium.php --allow-root`; full `wp eval-file /tmp/vg-cham-islands/ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion and option cleanup; LiteSpeed purge-all; public HTML QA; homepage direct-link check; Destinations hub note check; sitemap inclusion check; official source URL liveness checks; rendered Wikimedia image liveness checks from the VPS.
- Expected result: `/destinations/cham-islands-travel-guide/` publishes as the Hoi An marine day-trip decision guide for international travelers, with a clear day-trip vs overnight verdict, boat/logistics and weather checks, source/update trail, visible image credits, homepage and Destinations hub support, and no thin island-listicle framing.
- Actual outcome: page ID `254` published successfully at `https://vietnamguide.net/destinations/cham-islands-travel-guide/`. Local static verifier passed. Remote PHP lint passed for `ops/apply-cham-islands-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. Forced publish created and refreshed the guide, refreshed the homepage, and full EEAT verification passed. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/cham-islands-travel-guide/`, robots `index, follow`, one H1, all Cham Islands markers rendered, visible `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, `vg-related-routes`, homepage direct CTA `Read the Cham Islands guide`, Destinations hub note `vg-cham-islands-destinations-hub-note:v1`, and Rank Math `/page-sitemap.xml` inclusion after XML cache regeneration. Source checks returned HTTP `200` for UNESCO Cu Lao Cham biosphere reserve, Vietnam.travel Hoi An, Vietnam.travel Cham Islands, Da Nang Fantasticity, Hoi An World Heritage, Cu Lao Cham MPA, NBCA, Da Nang Airport, Vietnam.travel weather/climate, Vietnam.travel transport, official eVisa, and NCHMF. Final VPS image QA returned HTTP `206` for all seven rendered Wikimedia image URLs, with visible credits for the destination imagery.
- Evidence link/path: `https://vietnamguide.net/destinations/cham-islands-travel-guide/`
- Follow-up: build Ly Son Travel Guide next to extend the central-island support cluster for Hoi An, Da Nang, and Best Islands; then revisit a Hoi An marine day-trip comparison only if Search Console shows meaningful demand.

### 2026-07-22 - Ly Son Travel Guide Publication and QA

- Date: 2026-07-22
- Area: Ly Son destination pillar, Sa Ky ferry and volcanic central-island detour intent, Best Islands hub support, Destinations hub, homepage route modules, internal-link spine, licensed photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-ly-son-static.ps1`; remote backup to `/root/vietnamguide-backups/20260722-pre-ly-son`; SFTP upload of `ops/apply-ly-son-travel-guide.php`, `ops/apply-best-islands-vietnam-guide.php`, `ops/apply-homepage-premium.php`, `ops/verify-eeat-content.php`, and `ops/verify-ly-son-static.ps1`; remote `php -l` on the four PHP scripts; forced WP-CLI publish via `VG_FORCE_LY_SON_GUIDE_REPUBLISH=1 wp eval-file /tmp/vg-ly-son/ops/apply-ly-son-travel-guide.php --allow-root`; Best Islands republish via `VG_FORCE_BEST_ISLANDS_GUIDE_REPUBLISH=1 wp eval-file /tmp/vg-ly-son/ops/apply-best-islands-vietnam-guide.php --allow-root`; homepage refresh via `wp eval-file /tmp/vg-ly-son/ops/apply-homepage-premium.php --allow-root`; full `wp eval-file /tmp/vg-ly-son/ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion and option cleanup; LiteSpeed purge-all; public HTML QA; homepage direct-link check; Best Islands visible-link check; Destinations hub note check; sitemap inclusion check; official source URL liveness checks; rendered Wikimedia image liveness checks from the VPS.
- Expected result: `/destinations/ly-son-travel-guide/` publishes as the ferry-led central Vietnam island-detour guide for international travelers, with a clear 1-night, 2-night, or skip verdict, Sa Ky ferry and weather discipline, volcanic coast and Dao Be proof, source/update trail, visible image credits, homepage and Destinations hub support, Best Islands cross-linking, and no thin island-listicle framing.
- Actual outcome: page ID `257` published successfully at `https://vietnamguide.net/destinations/ly-son-travel-guide/`. Local static verifier passed. Remote PHP lint passed for `ops/apply-ly-son-travel-guide.php`, `ops/apply-best-islands-vietnam-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`. Forced publish created and refreshed the guide, refreshed the Destinations hub Ly Son note, added inbound related-route metadata across the planning spine, itinerary pages, central Vietnam pages, Best Beaches, Best Islands, Cham Islands, Quy Nhon, Nha Trang, health/insurance, and safety pages, then refreshed the homepage. Best Islands was republished to add the visible Ly Son planning-chain link and stronger structured related-route metadata for Phu Quoc, Con Dao, Nha Trang, Cham Islands, and Ly Son. Full EEAT verification passed after the Best Islands hub metadata was strengthened. Public HTML returned HTTP `200`, title `Ly Son Travel Guide: Is the Volcanic Island Worth the Detour?`, canonical `https://vietnamguide.net/destinations/ly-son-travel-guide/`, robots `index, follow`, one H1, all Ly Son markers rendered, visible `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, `vg-related-routes`, homepage direct CTA `Read the Ly Son guide`, homepage image alt `Ly Son islands seen from offshore`, Best Islands visible link to `/destinations/ly-son-travel-guide/`, Destinations hub note `vg-ly-son-destinations-hub-note:v1`, and Rank Math `/page-sitemap.xml` inclusion after XML cache regeneration. Source checks returned HTTP `200` for both Vietnam.travel Ly Son articles, Vietnam.travel Central Vietnam context, Vietnam.travel beach-islands context, Quang Ngai tourism, Quang Ngai To Vo scenic-relic news, Quang Ngai sea/island tourism development, Quang Ngai seaport planning, Quang Ngai Ly Son special-zone update, Sa Ky port, Sa Ky online ticket rules, Ly Son district portal, Vietnam.travel weather/climate, Vietnam.travel transport, official eVisa, and NCHMF, with ACV airport context retained as the official airport-support source. Local Wikimedia checks hit temporary `429` after several requests, so final image QA was run from the VPS; rendered Ly Son Islands, Dao Ly Son, Dao Be, fishing boats, temple, and panorama image URLs returned HTTP `206` range responses.
- Evidence link/path: `https://vietnamguide.net/destinations/ly-son-travel-guide/`
- Follow-up: next evergreen island/coast candidates are Phu Quy Travel Guide or a Hoi An day-trip comparison page, but the stronger strategic next build is probably Ho Chi Minh City Travel Guide or Mekong Delta Guide to balance the now-heavy central/northern/coast cluster.

### 2026-07-22 - Ho Chi Minh City Travel Guide Publication and QA

- Date: 2026-07-22
- Area: Ho Chi Minh City destination pillar, southern city-base intent, districts, food/history, Tan Son Nhat arrival/exit logic, Cu Chi and Mekong fit, Destinations hub, homepage route modules, internal-link spine, licensed photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-ho-chi-minh-city-static.ps1`; remote backup to `/root/vietnamguide-backups/20260722-pre-hcmc`; SFTP upload of `ops/apply-ho-chi-minh-city-travel-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; remote `php -l`; WP-CLI publish via `wp eval-file /tmp/vg-hcmc/ops/apply-ho-chi-minh-city-travel-guide.php --allow-root`; homepage refresh; full `wp eval-file /tmp/vg-hcmc/ops/verify-eeat-content.php --allow-root`; code-review follow-up to require the exact homepage HCMC href, add Destinations hub HCMC coverage to EEAT verification, and strengthen the HCMC static verifier; WordPress cache flush; rewrite flush; Rank Math XML cache deletion and option cleanup; LiteSpeed purge-all; public HTML QA; homepage direct-link check; Destinations hub note check; sitemap regeneration check; official source URL checks; rendered Wikimedia image liveness checks with VPS fallback for local automation rate limits.
- Expected result: `/destinations/ho-chi-minh-city-travel-guide/` publishes as the southern city-base guide for international travelers deciding whether Ho Chi Minh City deserves protected nights as a district, food, museum, airport, Cu Chi, Mekong, or southern-island connector chapter instead of becoming a token departure stop.
- Actual outcome: page ID `262` published successfully at `https://vietnamguide.net/destinations/ho-chi-minh-city-travel-guide/`. Local static verifier passed after being tightened to require the HCMC required homepage path, proof-panel pattern, source trail, update log, related routes shortcode, homepage HCMC URL coverage, and Destinations hub HCMC verifier marker. Remote PHP lint passed for `ops/apply-homepage-premium.php` and `ops/verify-eeat-content.php`; the earlier HCMC guide lint and publish were already successful. Homepage refresh validated all internal links as published. Full EEAT verification passed with the stricter homepage and Destinations hub checks. Public HTML returned HTTP `200`, title `Ho Chi Minh City Travel Guide: Districts, Food, Museums and Mekong Fit`, canonical `https://vietnamguide.net/destinations/ho-chi-minh-city-travel-guide/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, one H1, HCMC hero/verdict markers, visible `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage and Destinations hub both return HTTP `200` and include `/destinations/ho-chi-minh-city-travel-guide/`; the hub includes `vg-hcmc-destinations-hub-note:v1`. The first public sitemap check reproduced stale Rank Math XML cache behavior: `/page-sitemap.xml` returned HTTP `200` but did not include the HCMC URL while `wp-content/uploads/rank-math/rank_math_*.xml` and `rank_math_sitemap_cache_files` still pointed to older sitemap output. Deleting those XML cache files plus the option, flushing WordPress cache, flushing rewrites, and purging LiteSpeed regenerated the sitemap; final `/page-sitemap.xml` includes the HCMC URL. Official and credit source checks returned HTTP `200` for ACV Tan Son Nhat, ACV airport profile, Vietnam Airlines airport guide, Vietnam.travel HCMC, Vietnam.travel weather/climate, Vietnam.travel transport, Vietnam.travel getting to Vietnam, Visit HCMC, official eVisa, NCHMF, and seven Wikimedia Commons credit pages. Rendered image QA returned local HTTP `200` for City Hall/Nguyen Hue, Ben Thanh Market, Central Post Office, and War Remnants Museum thumbnails; Reunification Palace, Cu Chi Tunnels, and Mekong Delta thumbnails returned VPS HTTP `206` range responses after local automation returned `0`, matching the known Wikimedia local automation behavior.
- Evidence link/path: `https://vietnamguide.net/destinations/ho-chi-minh-city-travel-guide/`
- Follow-up: build Mekong Delta Travel Guide next to complete the HCMC southern gateway chain, then add a narrower Where to Stay in Ho Chi Minh City guide only when Search Console shows stay-area demand.

### 2026-07-23 - Mekong Delta Travel Guide Publication and QA

- Date: 2026-07-23
- Area: Mekong Delta destination pillar, HCMC south-gateway continuation, Can Tho/Cai Rang overnight intent, Ben Tre/Chau Doc/Tra Su routing, Destinations hub, homepage route modules, internal-link spine, licensed photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-mekong-delta-static.ps1`; remote backup to `/root/vietnamguide-backups/20260723-092333-pre-mekong-delta-guide`; SFTP upload of `ops/apply-mekong-delta-travel-guide.php`, `ops/apply-homepage-premium.php`, `ops/apply-ho-chi-minh-city-travel-guide.php`, `ops/verify-eeat-content.php`, and `ops/verify-mekong-delta-static.ps1`; remote `php -l` on the four PHP scripts; forced WP-CLI publish via `VG_FORCE_MEKONG_DELTA_GUIDE_REPUBLISH=1 wp eval-file /tmp/vg-mekong/ops/apply-mekong-delta-travel-guide.php --allow-root`; homepage refresh via `wp eval-file /tmp/vg-mekong/ops/apply-homepage-premium.php --allow-root`; HCMC repair via `VG_REPAIR_HCMC_GUIDE_LINKS=1 wp eval-file /tmp/vg-mekong/ops/apply-ho-chi-minh-city-travel-guide.php --allow-root`; full `wp eval-file /tmp/vg-mekong/ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math sitemap cache file deletion; Rank Math sitemap option refresh; LiteSpeed purge-all; public HTTPS QA; homepage direct-link check; HCMC direct-link check; Destinations hub note check; sitemap inclusion check; official source URL checks; Wikimedia image URL checks.
- Expected result: `/destinations/mekong-delta-travel-guide/` publishes as the evidence-led southern river decision guide for international travelers, with a clear day-trip vs overnight vs skip verdict, Can Tho/Cai Rang, Ben Tre, Chau Doc and Tra Su route logic, source/update discipline, visible licensed image credits, homepage and HCMC support, and no thin rewritten list framing.
- Actual outcome: page ID `265` published successfully at `https://vietnamguide.net/destinations/mekong-delta-travel-guide/`. Local static verifier passed after adding the Mekong runtime block expectations for season/weather, transport, and cost markers, rendered official-source checks, truthy headline-disable enforcement, and outbound related-route metadata coverage. Remote PHP lint passed for `ops/apply-mekong-delta-travel-guide.php`, `ops/apply-homepage-premium.php`, `ops/apply-ho-chi-minh-city-travel-guide.php`, and `ops/verify-eeat-content.php`. Forced publish created the guide, refreshed the Destinations hub Mekong note, and upserted inbound related-route metadata across the planning spine, then refreshed the homepage. HCMC repair mode upserted the Mekong related-route line on the HCMC page without overwriting the rest of the guide. Full EEAT verification passed after cache refresh and sitemap repair. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/mekong-delta-travel-guide/`, robots `index, follow`, one H1, and the expected Mekong hero/verdict/photo-grid/source-trail/update-log/related-routes markers. Homepage, HCMC, and Destinations hub all returned HTTP `200` with Mekong visible. The first sitemap checks showed the new page missing from `/page-sitemap.xml` while Rank Math XML cache files under `wp-content/uploads/rank-math/` were stale; deleting those cache files, refreshing the Rank Math sitemap option, flushing WordPress cache, and purging LiteSpeed regenerated the sitemap. Final `/page-sitemap.xml` includes `/destinations/mekong-delta-travel-guide/`. Official source checks returned HTTP `200` for Vietnam.travel Can Tho, Chau Doc, Ho Chi Minh City, weather/climate, transport, getting to Vietnam, tourismcantho.vn, and the official eVisa portal; ACV Can Tho airport returned HTTP `000` from VPS curl but `200` locally, while Vietnamairport Can Tho returned HTTP `403` from both automation paths, so those remain logged as live anchors with known automation reachability limits. Image QA returned HTTP `200` for the first three Wikimedia files and HTTP `429` on several later Wikimedia HEAD requests from the VPS, but the page renders the full image set and the site-level EEAT verifier passed with the rendered credits and image URLs.
- Evidence link/path: `https://vietnamguide.net/destinations/mekong-delta-travel-guide/`
- Follow-up: continue the southern route cluster with a Phnom Penh/Cambodia-adjacent river decision only if search demand appears, otherwise move to the next evergreen comparison or island guide that balances the site's travel-intent map.

### 2026-07-23 - Best Day Trips from Ho Chi Minh City Publication and QA

- Date: 2026-07-23
- Area: Ho Chi Minh City excursion decision intent, southern day-trip cluster, Cu Chi vs Can Gio vs Tay Ninh vs Mekong taste vs Vung Tau vs stay-in-city logic, Destinations hub, homepage route modules, HCMC guide planning-chain link, internal-link spine, licensed photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: tightened local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-best-day-trips-ho-chi-minh-city-static.ps1`; remote upload to `/tmp/vg-hcmc-day-trips/ops/`; remote `php -l` on `ops/apply-best-day-trips-ho-chi-minh-city.php`, `ops/apply-homepage-premium.php`, `ops/apply-ho-chi-minh-city-travel-guide.php`, `ops/verify-eeat-content.php`, and `ops/purge-litespeed-cache.php`; remote backup to `/root/vietnamguide-backups/2026-07-23-hcmc-day-trips-before-publish`; forced WP-CLI publish via `VG_FORCE_HCMC_DAY_TRIPS_GUIDE_REPUBLISH=1 wp eval-file /tmp/vg-hcmc-day-trips/ops/apply-best-day-trips-ho-chi-minh-city.php --allow-root`; homepage refresh via `wp eval-file /tmp/vg-hcmc-day-trips/ops/apply-homepage-premium.php --allow-root`; HCMC guide refresh via `VG_FORCE_HCMC_GUIDE_REPUBLISH=1 wp eval-file /tmp/vg-hcmc-day-trips/ops/apply-ho-chi-minh-city-travel-guide.php --allow-root`; full `wp eval-file /tmp/vg-hcmc-day-trips/ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion; LiteSpeed purge-all; public HTTPS QA; homepage direct-link check; HCMC visible-link check; Destinations hub note check; sitemap inclusion check; rendered Wikimedia image spot checks.
- Expected result: `/destinations/best-day-trips-from-ho-chi-minh-city/` publishes as the southern excursion decision guide for international travelers, with a clear verdict on when to stay in HCMC, choose Cu Chi, choose Can Gio, spend a long day in Tay Ninh, treat Mekong as a taste rather than an overnight substitute, or skip Vung Tau-style road time, while retaining source trail, update log, visible image credits, related routes, and no generic listicle framing.
- Actual outcome: page ID `268` published successfully at `https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/`. Local static verifier passed after being tightened to require the guide slug, destinations parent path guard, all day-trip markers, official/source anchors, visible image credits, HCMC visible planning-chain link, full EEAT marker coverage, rendered official-source groups, image-credit groups, and related-route verifier helpers. Remote PHP lint passed for all uploaded PHP scripts. Forced publish created the guide under `/destinations/`, refreshed the Destinations hub note, and upserted inbound related-route metadata into HCMC, Mekong, Vietnam Travel Guide, Best Places, North/Central/South, 10/14-day itinerary, Best Time, Transport, Cost, Health/Insurance, and Safety pages. Homepage refresh validated internal links and exposed the guide as a southern excursion decision. HCMC guide refresh added the visible Best Day Trips link and upserted the HCMC related route back into the new guide. Post-review hardening changed the day-trips inbound related-route refresh to href-based de-duping and changed the HCMC day-trips official-source EEAT check to rendered content only, then republished page ID `268`. Full EEAT verification passed. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/`, no `noindex`, exactly one H1, rendered official-source links, hero/source-trail markers, homepage link, HCMC visible link, Destinations hub note/link, and `/page-sitemap.xml` inclusion after cache purge and sitemap XML deletion. Rendered image QA returned HTTP `200` for the HCMC City Hall, Cu Chi, and Can Gio Wikimedia images before Wikimedia returned temporary automation `429` on later HEAD requests; the live page HTML retains all six Wikimedia image URLs and visible credits, so remaining image liveness is logged with the same known Wikimedia automation rate-limit caveat seen in prior releases.
- Evidence link/path: `https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/`
- Follow-up: deepen the southern decision cluster next with a narrower Where to Stay in Ho Chi Minh City or Cu Chi vs Mekong comparison only if Search Console shows query demand; otherwise continue evergreen destinations/top-list coverage to balance the site-wide route map.

### 2026-07-23 - Best Day Trips from Ho Chi Minh City Depth Pass

- Date: 2026-07-23
- Area: HCMC excursion decision depth pass, route mechanics, pacing windows, overnight-upgrade logic, operator due diligence, comfort/family fit, food/rest-stop quality, Destinations hub, homepage route modules, HCMC planning-chain link, internal-link spine, licensed photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: depth-pass static verifier updates via `powershell -ExecutionPolicy Bypass -File ops\verify-best-day-trips-ho-chi-minh-city-static.ps1`; remote upload to `/tmp/vg-hcmc-day-trips/ops/`; remote `php -l` on `ops/apply-best-day-trips-ho-chi-minh-city.php` and `ops/verify-eeat-content.php`; remote backup to `/root/vietnamguide-backups/2026-07-23-hcmc-day-trips-depth-pass`; forced WP-CLI republish via `VG_FORCE_HCMC_DAY_TRIPS_REPUBLISH=1 wp eval-file /tmp/vg-hcmc-day-trips/ops/apply-best-day-trips-ho-chi-minh-city.php --allow-root`; full `wp eval-file /tmp/vg-hcmc-day-trips/ops/verify-eeat-content.php --allow-root`; WordPress cache flush; LiteSpeed purge-all; public HTTPS QA for page canonical, H1 count, rendered depth modules, homepage link, HCMC guide link, and sitemap inclusion.
- Expected result: the HCMC day-trip page should stop reading like a strong editorial list and start reading like a durable concierge tool, with concrete route mechanics, time windows, fallback decisions, and operator-quality cues that keep it useful for years rather than a single algorithmic cycle.
- Actual outcome: page ID `268` republished successfully at `https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/`. Local static verifier passed with the new route-snapshot, sample-script, overnight-upgrade, operator-check, comfort-fit, and food-rest assertions. Remote PHP lint passed for both updated PHP scripts. Full EEAT verification passed after the rendered-depth-module check was added and the stale quoted-food-rest literal was replaced with the stable quote-free text. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/`, one H1, no `noindex`, rendered route snapshots with departure windows and door-to-door ranges, rendered sample day scripts with concrete pacing and fallback decisions, rendered food/rest quality table, homepage link, HCMC guide link, and `/page-sitemap.xml` inclusion after cache purge and sitemap refresh.
- Evidence link/path: `https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/`
- Follow-up: use the same depth-pass pattern on the next high-intent southern or northern comparison page, then keep balancing the evergreen calendar with destination and planning pages that add original judgment instead of generic list volume.

### 2026-07-23 - Best Day Trips from Ho Chi Minh City Decision-Tools Depth Pass

- Date: 2026-07-23
- Area: HCMC excursion decision tools, quick chooser, route pairings, day placement, transfer-to-experience ratio, booking mode, context ethics, red-flag filtering, FAQ expansion, public EEAT verification
- Command/check: test-first static verifier update via `powershell -ExecutionPolicy Bypass -File ops\verify-best-day-trips-ho-chi-minh-city-static.ps1` first failed on missing `vg-hcmc-day-trips-quick-chooser:v1`, then passed after implementation; remote upload to `/tmp/vg-hcmc-day-trips/ops/`; remote `php -l` on `ops/apply-best-day-trips-ho-chi-minh-city.php` and `ops/verify-eeat-content.php`; remote content backup to `/tmp/vg-hcmc-day-trips/backups/best-day-trips-hcmc-before-depth-pass-.html`; forced WP-CLI republish via `VG_FORCE_HCMC_DAY_TRIPS_REPUBLISH=1 wp eval-file /tmp/vg-hcmc-day-trips/ops/apply-best-day-trips-ho-chi-minh-city.php --allow-root`; full `wp eval-file /tmp/vg-hcmc-day-trips/ops/verify-eeat-content.php --allow-root`; WordPress cache flush; LiteSpeed purge-all; public HTTPS QA for canonical, one H1, no noindex, new rendered depth modules, homepage link, and sitemap inclusion.
- Expected result: the guide should move another step away from generic "best day trip" list content by helping international travelers decide whether the day should be used at all, where it belongs inside the HCMC stay, which route chapters it duplicates, which booking mode fits the job, and which operator claims should be rejected before payment.
- Actual outcome: page ID `268` republished successfully at `https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/`. Local static verification passed after adding expectations for `vg-hcmc-day-trips-quick-chooser:v1`, `vg-hcmc-day-trips-route-pairings:v1`, `vg-hcmc-day-trips-day-placement:v1`, `vg-hcmc-day-trips-transfer-value-scorecard:v1`, `vg-hcmc-day-trips-booking-mode:v1`, `vg-hcmc-day-trips-context-ethics:v1`, and `vg-hcmc-day-trips-failure-modes:v1`. Remote PHP lint passed for both updated scripts. Full EEAT verification passed. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/`, exactly one H1, no `noindex`, and rendered the new quick chooser, route-pairing, day-placement, transfer-to-experience, booking-mode, context-ethics, failure-mode, and Cu Chi/Mekong FAQ content. Homepage still links to the guide and `/page-sitemap.xml` includes the URL.
- Evidence link/path: `https://vietnamguide.net/destinations/best-day-trips-from-ho-chi-minh-city/`
- Follow-up: next useful evergreen expansion is either a narrow `Cu Chi Tunnels vs Mekong Delta Day Trip` comparison for comparison/PAA intent, or `Where to Stay in Ho Chi Minh City` if Search Console begins showing district/stay-area demand.

### 2026-07-23 - Cu Chi Tunnels vs Mekong Delta Day Trip Publication and QA

- Date: 2026-07-23
- Area: Cu Chi vs Mekong comparison intent, Compare hub, HCMC/Mekong inbound related-route refresh, public EEAT verification, sitemap inclusion, cache purge
- Command/check: remote backup to `/root/vietnamguide-backups/20260723-203625-pre-cu-chi-mekong`; SFTP upload of `ops/apply-cu-chi-vs-mekong-delta-day-trip.php`, `ops/verify-cu-chi-vs-mekong-delta-day-trip-static.ps1`, `ops/verify-eeat-content.php`, `ops/apply-best-day-trips-ho-chi-minh-city.php`, `ops/apply-mekong-delta-travel-guide.php`, and `ops/purge-litespeed-cache.php`; remote `php -l` on the five PHP scripts; forced WP-CLI publish via `VG_FORCE_CU_CHI_MEKONG_COMPARISON_REPUBLISH=1 wp eval-file /tmp/vg-cu-chi-mekong/ops/apply-cu-chi-vs-mekong-delta-day-trip.php --allow-root`; full `wp eval-file /tmp/vg-cu-chi-mekong/ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion and option cleanup; LiteSpeed purge-all via `ops/purge-litespeed-cache.php`; public HTTPS QA; compare hub note check; HCMC/Mekong related-route checks; sitemap inclusion check.
- Expected result: `/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/` publishes as the evidence-led southern comparison page for international travelers choosing history, river rhythm, route energy, operator quality, or whether to combine, skip, or overnight.
- Actual outcome: page ID `279` published successfully at `https://vietnamguide.net/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/`. Local static verifier passed. Remote PHP lint passed for `ops/apply-cu-chi-vs-mekong-delta-day-trip.php`, `ops/verify-cu-chi-vs-mekong-delta-day-trip-static.ps1`, `ops/verify-eeat-content.php`, `ops/apply-best-day-trips-ho-chi-minh-city.php`, `ops/apply-mekong-delta-travel-guide.php`, and `ops/purge-litespeed-cache.php`. Forced publish created the page, refreshed the Compare hub note `vg-cu-chi-mekong-compare-hub-note:v1`, and added the new compare related-route line to Best Day Trips from Ho Chi Minh City, Mekong Delta Travel Guide, Ho Chi Minh City Travel Guide, Vietnam Travel Guide, North vs Central vs South Vietnam, 10 Days in Vietnam, 14 Days in Vietnam, Best Time to Visit Vietnam, Transport Within Vietnam, Vietnam Travel Cost, Health and Travel Insurance for Vietnam, and Safety and Scams in Vietnam. Full EEAT verification passed. Public HTML QA returned HTTP `200`, one H1, no `noindex`, rendered hero/verdict/comparison markers, compare hub note marker on `/compare/`, and the exact canonical `https://vietnamguide.net/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/`. The compare page appeared once in `/page-sitemap.xml`, and both the HCMC and Mekong guide pages returned the new compare related-route link in public HTML after cache purge.
- Evidence link/path: `https://vietnamguide.net/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/`
- Follow-up: keep comparing southern short-day intent against HCMC, Mekong, and overnight-Delta logic when Search Console shows new demand, but avoid widening the page into a generic attraction list.

### 2026-07-23 - Where to Stay in Ho Chi Minh City Publication and QA

- Date: 2026-07-23
- Area: HCMC stay-area decision intent, District 1 vs District 3 vs Thao Dien vs riverside/Binh Thanh vs Bui Vien vs Tan Son Nhat airport, HCMC pillar support, Destinations hub, homepage route spine, internal related-route mesh, licensed photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: local static verifiers via `powershell -ExecutionPolicy Bypass -File ops\verify-where-to-stay-ho-chi-minh-city-static.ps1` and `powershell -ExecutionPolicy Bypass -File ops\verify-ho-chi-minh-city-static.ps1`; remote staging under `/tmp/vg-hcmc-stays/ops/`; remote `php -l` on `ops/apply-where-to-stay-ho-chi-minh-city.php`, `ops/apply-ho-chi-minh-city-travel-guide.php`, `ops/apply-homepage-premium.php`, `ops/verify-eeat-content.php`, and `ops/purge-litespeed-cache.php`; forced WP-CLI publish via `VG_FORCE_HCMC_STAYS_GUIDE_REPUBLISH=1 wp eval-file /tmp/vg-hcmc-stays/ops/apply-where-to-stay-ho-chi-minh-city.php --allow-root`; HCMC pillar refresh via `VG_FORCE_HCMC_GUIDE_REPUBLISH=1 wp eval-file /tmp/vg-hcmc-stays/ops/apply-ho-chi-minh-city-travel-guide.php --allow-root`; homepage refresh via `wp eval-file /tmp/vg-hcmc-stays/ops/apply-homepage-premium.php --allow-root`; full `wp eval-file /tmp/vg-hcmc-stays/ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion/option cleanup when present; LiteSpeed purge-all; public HTTPS QA; homepage link check; HCMC visible-link check; Destinations hub note check; sitemap inclusion check; official source URL checks; rendered Wikimedia image checks from the VPS.
- Expected result: `/destinations/where-to-stay-in-ho-chi-minh-city/` publishes as a decision-led HCMC base guide for international travelers choosing a hotel area by route job, sleep/noise tolerance, day-trip pickup, airport risk, longer-stay comfort, and practical city value rather than affiliate hotel rankings.
- Actual outcome: page ID `281` published successfully at `https://vietnamguide.net/destinations/where-to-stay-in-ho-chi-minh-city/`. Local static verification passed for the stay guide and the HCMC regression verifier. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/where-to-stay-in-ho-chi-minh-city/`, exactly one H1, no `noindex`, and rendered `vg-hcmc-stays-hero:v1`, `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage includes `/destinations/where-to-stay-in-ho-chi-minh-city/`, Destinations hub includes `vg-hcmc-stays-destinations-hub-note:v1`, HCMC guide includes the visible stay-area link, and `/page-sitemap.xml` includes the new URL exactly once after cache and sitemap refresh. A full EEAT run initially caught stale HCMC related-route metadata missing `/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/`; the current HCMC script was re-uploaded and republished, `ops/verify-ho-chi-minh-city-static.ps1` was tightened to require that comparison related route, and full EEAT verification then passed. Official/source checks returned HTTP `200` for Vietnam.travel HCMC, Visit HCMC, ACV Tan Son Nhat, ACV airport profile, Vietnam Airlines airport-to-city guide, Vietnam.travel weather/climate, NCHMF, eVisa, and all eight Wikimedia Commons credit records; the Vietnam Government Portal Metro Line 1 source returned `403` to automation while remaining the official source anchor. Rendered image QA from the VPS returned HTTP `206` for all eight Wikimedia image URLs used in the guide.
- Evidence link/path: `https://vietnamguide.net/destinations/where-to-stay-in-ho-chi-minh-city/`
- Follow-up: continue the HCMC accommodation cluster only if Search Console shows narrower demand such as District 1 vs District 3, Thao Dien family stays, airport hotels, or Bui Vien noise intent; otherwise move back to evergreen destination/top-list coverage to balance the site-wide content map.

### 2026-07-23 - Hanoi Travel Guide Publication and QA

- Date: 2026-07-23
- Area: Hanoi destination pillar, northern route-anchor intent, Noi Bai arrival logic, stay-area triage, Hanoi-to-Ninh Binh/bay/Cat Ba/mountain route fit, homepage route spine, Best Things Hanoi support page, Destinations hub, internal related-route mesh, licensed photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-hanoi-travel-guide-static.ps1`; remote staging under `/tmp/vg-hanoi-travel/ops/`; remote `php -l` on `ops/apply-hanoi-travel-guide.php`, `ops/apply-best-things-hanoi-guide.php`, `ops/apply-homepage-premium.php`, `ops/verify-eeat-content.php`, and `ops/purge-litespeed-cache.php`; forced WP-CLI publish via `VG_FORCE_HANOI_TRAVEL_GUIDE_REPUBLISH=1 wp eval-file /tmp/vg-hanoi-travel/ops/apply-hanoi-travel-guide.php --allow-root`; Best Things Hanoi republish via `VG_FORCE_HANOI_GUIDE_REPUBLISH=1 wp eval-file /tmp/vg-hanoi-travel/ops/apply-best-things-hanoi-guide.php --allow-root`; homepage refresh via `wp eval-file /tmp/vg-hanoi-travel/ops/apply-homepage-premium.php --allow-root`; full `wp eval-file /tmp/vg-hanoi-travel/ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion/option cleanup; LiteSpeed purge-all; public HTTPS QA; homepage link check; Best Things Hanoi support-link check; Destinations hub note check; sitemap inclusion check; official source URL checks; rendered Wikimedia image checks from the VPS.
- Expected result: `/destinations/hanoi-travel-guide/` publishes as the Hanoi route-role pillar for international travelers deciding how many Hanoi nights to protect, where to base, how Noi Bai arrival affects the first day, and when the city should launch Ninh Binh, Ha Long/Lan Ha, Cat Ba, mountains, or Central Vietnam, without duplicating the existing Best Things Hanoi attractions page.
- Actual outcome: page ID `287` published successfully at `https://vietnamguide.net/destinations/hanoi-travel-guide/`. Local static verification passed. Remote PHP lint passed for all uploaded PHP scripts. The first forced publish created the guide, refreshed the Destinations hub note `vg-hanoi-travel-destinations-hub-note:v1`, added a visible support block to Best Things Hanoi, and upserted Hanoi Travel Guide related-route metadata across the planning spine, itinerary pages, Ninh Binh, Ha Long Bay, Ha Long Bay vs Lan Ha Bay, Cat Ba, Bai Tu Long, cost, transport, money, SIM/eSIM, eVisa, insurance, and safety pages. Full EEAT initially caught missing 7-day and 21-day related-route lines on Best Things Hanoi; `ops/apply-best-things-hanoi-guide.php` was updated and republished, then full EEAT verification passed. Public HTML returned HTTP `200`, effective URL `https://vietnamguide.net/destinations/hanoi-travel-guide/`, canonical line with the exact URL, one H1, no `noindex`, and rendered all Hanoi Travel Guide markers: `vg-hanoi-travel-hero:v1`, `vg-hanoi-travel-concierge-verdict`, `vg-hanoi-travel-at-a-glance:v1`, `vg-hanoi-travel-photo-grid:v1`, `vg-hanoi-travel-source-diversity:v1`, `vg-hanoi-travel-route-role:v1`, `vg-hanoi-travel-stay-areas:v1`, `vg-hanoi-travel-day-plans:v1`, `vg-hanoi-travel-transport-logistics:v1`, `vg-hanoi-travel-weather-pivots:v1`, `vg-hanoi-travel-cost-comfort:v1`, `vg-hanoi-travel-mistakes-skip:v1`, `vg-hanoi-travel-live-checks:v1`, `vg-hanoi-travel-faq:v1`, plus `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage includes `/destinations/hanoi-travel-guide/`, Best Things Hanoi includes the support marker and two links to the pillar, Destinations hub includes the new marker, and `/page-sitemap.xml` includes the URL exactly once after cache and sitemap refresh. Image QA initially found stale Noi Bai and Long Bien Wikimedia thumbnails returning `404`; `ops/apply-hanoi-travel-guide.php` was republished with live Commons image URLs and corrected credits for Christakis Mina and TheRollo76, and final VPS image checks returned HTTP `200` for all six rendered Wikimedia image URLs. Source checks returned HTTP `200` for Vietnam.travel Hanoi, Northern Vietnam, weather/climate, NCHMF, Imperial Citadel of Thang Long, Temple of Literature, and Vietnamese Women's Museum; the Noi Bai airport URL redirects toward ACV and timed out from VPS automation, while UNESCO returned `403` to curl automation, so both remain logged as official source anchors with automation reachability limits rather than broken on-page assets.
- Evidence link/path: `https://vietnamguide.net/destinations/hanoi-travel-guide/`
- Follow-up: build `Where to Stay in Hanoi` next to deepen the accommodation/stay-area intent now that the Hanoi pillar is the northern route anchor.

### 2026-07-24 - Where to Stay in Hanoi Publication and QA

- Date: 2026-07-24
- Area: Hanoi stay-area decision intent, Hoan Kiem vs Old Quarter edge vs French Quarter vs Ba Dinh vs Tay Ho vs Noi Bai airport-side, Hanoi pillar support, Destinations hub, homepage route spine, internal related-route mesh, licensed photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-where-to-stay-in-hanoi-static.ps1`; remote staging under `/tmp/vg-hanoi-stays/ops/`; remote `php -l` on `ops/apply-where-to-stay-in-hanoi.php`, `ops/apply-hanoi-travel-guide.php`, `ops/apply-homepage-premium.php`, `ops/verify-eeat-content.php`, and `ops/purge-litespeed-cache.php`; forced WP-CLI publish via `VG_FORCE_HANOI_STAYS_GUIDE_REPUBLISH=1 wp eval-file /tmp/vg-hanoi-stays/ops/apply-where-to-stay-in-hanoi.php --allow-root`; Hanoi pillar republish via `VG_FORCE_HANOI_TRAVEL_GUIDE_REPUBLISH=1 wp eval-file /tmp/vg-hanoi-stays/ops/apply-hanoi-travel-guide.php --allow-root`; homepage refresh via `wp eval-file /tmp/vg-hanoi-stays/ops/apply-homepage-premium.php --allow-root`; full `wp eval-file /tmp/vg-hanoi-stays/ops/verify-eeat-content.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math sitemap cache deletion and option cleanup; LiteSpeed purge-all; public HTTPS QA; homepage link check; Hanoi Travel Guide link check; Destinations hub note check; sitemap inclusion check; official source URL checks; Wikimedia image checks from the VPS; browser fallback check for UNESCO.
- Expected result: `/destinations/where-to-stay-in-hanoi/` publishes as the evidence-led Hanoi base guide for international travelers choosing a hotel area by route job, first-night recovery, pickup clarity, sleep tolerance, and flight-risk buffer instead of affiliate hotel rankings.
- Actual outcome: page ID `294` published successfully at `https://vietnamguide.net/destinations/where-to-stay-in-hanoi/`. Local static verification passed. The first EEAT verification run failed on missing shared-template markers, so the guide script was patched to use the generic `vg-concierge-verdict` class and include the shared `vietnamguide/editorial-proof-panel` block, then republished; full EEAT verification passed on the second run. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/where-to-stay-in-hanoi/`, exactly one H1, no `noindex`, and rendered the expected Hanoi stay markers: `vg-hanoi-stays-hero:v1`, `vg-concierge-verdict`, `vg-hanoi-stays-concierge-verdict`, `vg-proof-panel`, `vg-hanoi-stays-at-a-glance:v1`, `vg-hanoi-stays-photo-grid:v1`, `vg-hanoi-stays-source-diversity:v1`, `vg-hanoi-stays-area-verdict:v1`, `vg-hanoi-stays-first-time-fit:v1`, `vg-hanoi-stays-noise-map:v1`, `vg-hanoi-stays-airport-buffer:v1`, `vg-hanoi-stays-day-trip-pickup:v1`, `vg-hanoi-stays-traveler-profiles:v1`, `vg-hanoi-stays-cost-booking:v1`, `vg-hanoi-stays-safety-comfort:v1`, `vg-hanoi-stays-live-checks:v1`, `vg-hanoi-stays-faq:v1`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage includes `/destinations/where-to-stay-in-hanoi/`, Hanoi Travel Guide includes the support link and the visible stay-area handoff, Destinations hub includes `vg-hanoi-stays-destinations-hub-note:v1`, and `/page-sitemap.xml` includes the new URL exactly once after cache refresh. Official source checks returned HTTP `200` for Vietnam.travel Ha Noi, Explore Old Quarter, Comfort meet culture, Vietnamese history primer, 11 must-see attractions, Noi Bai airport, and NCHMF; UNESCO opened in browser/Web context but returned `403` to curl automation, so it remains logged as an official source anchor with automation reachability limits rather than a broken link. Wikimedia image checks returned HTTP `206` or `200` after browser-like retry for all Hanoi proof images, including the hero, Old Quarter, Temple, Thang Long, Noi Bai, and Long Bien thumbnails.
- Evidence link/path: `https://vietnamguide.net/destinations/where-to-stay-in-hanoi/`
- Follow-up: continue the northern decision cluster with the next high-intent comparison or evergreen destination page, while keeping Where to Stay in Hanoi as the durable base-selection node for Hanoi routes.

### 2026-07-24 - Old Quarter vs French Quarter vs West Lake Link-Budget Hardening and QA

- Date: 2026-07-24
- Area: Hanoi neighborhood comparison page, visible external body-link budget, source-trail discipline, EEAT regression guard, cache/sitemap refresh, public QA
- Command/check: local `powershell -ExecutionPolicy Bypass -File .\.worktrees\codex-vietnamguide-premium-implementation\ops\verify-old-quarter-french-quarter-west-lake-static.ps1`; local static href count; remote upload of `ops/apply-old-quarter-french-quarter-west-lake.php`, `ops/verify-old-quarter-french-quarter-west-lake-static.ps1`, and `ops/verify-eeat-content.php` to `/tmp/vg-hanoi-neighborhood/ops/`; remote `php -l` on the uploaded PHP files; initial repair-mode run confirmed side effects but did not rewrite the page body because `VG_REPAIR_HANOI_NEIGHBORHOOD_COMPARISON_LINKS=1` returns before the publish branch; forced body republish via `VG_FORCE_HANOI_NEIGHBORHOOD_COMPARISON_REPUBLISH=1 wp eval-file /tmp/vg-hanoi-neighborhood/ops/apply-old-quarter-french-quarter-west-lake.php --allow-root`; full `wp eval-file /tmp/vg-hanoi-neighborhood/ops/verify-eeat-content.php --allow-root`; WordPress object cache flush; rewrite flush; LiteSpeed purge-all; Rank Math XML sitemap cache file deletion and `rank_math_sitemap_cache_files` option deletion; public HTTPS QA; sitemap count; rendered Wikimedia range checks from the VPS.
- Expected result: `/compare/old-quarter-vs-french-quarter-vs-west-lake/` keeps the premium, evidence-led comparison body while reducing visible external body links to the planned 2-4 range, with richer source and image-license records retained in `vg_eeat_sources_checked`.
- Actual outcome: page ID `301` republished successfully at `https://vietnamguide.net/compare/old-quarter-vs-french-quarter-vs-west-lake/`. The page body now exposes exactly four offsite visible links: Vietnam.travel Ha Noi, Vietnam.travel Old Quarter, Noi Bai airport, and NCHMF. Image credits remain visible as text, and the full license/source records remain in source trail metadata. Local static verification passed and now enforces the visible external href budget. Remote PHP lint passed for the updated apply script and EEAT verifier. Full EEAT verification passed after the force-only republish. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/compare/old-quarter-vs-french-quarter-vs-west-lake/`, robots `index, follow`, exactly one H1, all Hanoi neighborhood markers, `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage, Hanoi Travel Guide, Where to Stay in Hanoi, Best Things to Do in Hanoi, and the Compare hub all include the comparison URL; the Compare hub includes `vg-hanoi-neighborhood-compare-hub-note:v1`. `/page-sitemap.xml` includes the comparison URL exactly once after cache refresh. The live page renders five Wikimedia image URLs, and the VPS range check returned `206` for all five.
- Evidence link/path: `https://vietnamguide.net/compare/old-quarter-vs-french-quarter-vs-west-lake/`
- Follow-up: keep this page as the narrow decision node beneath Hanoi stay-area intent. Future Hanoi subpages should use the same pattern: photo proof, source trail metadata, four-or-fewer visible offsite links unless the page has a true calculation/comparison need.

### 2026-07-24 - Best Day Trips from Hanoi Publication and QA

- Date: 2026-07-24
- Area: Hanoi excursion decision intent, northern day-trip cluster, Ninh Binh vs Ha Long/Lan Ha vs Bat Trang vs Duong Lam vs Perfume Pagoda vs Ba Vi logic, Hanoi pillar support, homepage route spine, internal related-route mesh, licensed photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-best-day-trips-hanoi-guide-static.ps1`; remote upload to `/tmp/vg-hanoi-day-trips/ops/`; remote `php -l` for `ops/apply-best-day-trips-hanoi-guide.php`, `ops/apply-homepage-premium.php`, `ops/verify-eeat-content.php`, and all touched inbound/source-persistence scripts; forced WP-CLI publish via `VG_FORCE_HANOI_DAY_TRIPS_REPUBLISH=1 wp --allow-root eval-file ops/apply-best-day-trips-hanoi-guide.php`; homepage refresh via `wp --allow-root eval-file ops/apply-homepage-premium.php`; full `wp --allow-root eval-file ops/verify-eeat-content.php`; WordPress cache flush; rewrite flush; LiteSpeed purge-all; Rank Math sitemap cache deletion and option cleanup; public HTTPS QA; homepage direct-link check; Destinations hub check; Hanoi Travel Guide, Where to Stay in Hanoi, Best Things Hanoi, Ninh Binh, Ha Long Bay, and Ha Long vs Lan Ha inbound checks; sitemap inclusion check; rendered image URL range checks from the VPS.
- Expected result: `/destinations/best-day-trips-from-hanoi/` publishes as the evidence-led northern excursion decision guide for international travelers choosing whether to spend a Hanoi day on Ninh Binh, Ha Long/Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or stay in Hanoi, with a clear default verdict, transfer-scorecard logic, overnight-upgrade guidance, operator checks, skip logic, live checks, source/update discipline, visible photo proof, related routes, and no thin rewritten listicle framing.
- Actual outcome: page ID `309` published successfully at `https://vietnamguide.net/destinations/best-day-trips-from-hanoi/`. Local static verification passed after the verifier was expanded to catch source-persistence regressions. Remote PHP lint passed for the new page script, homepage script, EEAT verifier, and 17 source-persistence scripts. Forced WP-CLI publish created/refreshed the guide, homepage republish restored the premium route modules, and full EEAT verification passed. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/best-day-trips-from-hanoi/`, robots `index, follow`, one H1, and all expected markers: hero, verdict, photo grid, decision grid, transfer scorecard, overnight upgrade, operator checks, skip logic, live checks, FAQ, `vg-proof-panel`, `vg-source-trail`, `vg-update-log`, and `vg-related-routes`. Homepage, Destinations hub, Hanoi Travel Guide, Where to Stay in Hanoi, Best Things Hanoi, Ninh Binh, Ha Long Bay, and Ha Long Bay vs Lan Ha Bay all include the day-trips URL after cache purge. `/page-sitemap.xml` includes the URL exactly once. Final image QA fixed incorrect Wikimedia thumbnail paths by switching to stable original URLs; all six rendered image URLs returned HTTP `206` from the VPS.
- Evidence link/path: `https://vietnamguide.net/destinations/best-day-trips-from-hanoi/`
- Follow-up: continue the Hanoi arrival/short-stay cluster with `Hanoi Airport to Old Quarter` or `Hanoi in 2 Days`; both have strong evergreen international intent, but the airport-transfer page is the cleaner next utility node because it can receive links from Hanoi Travel Guide, Where to Stay in Hanoi, safety, transport, and itinerary pages without becoming generic attractions content.

### 2026-07-24 - Hanoi Airport to Old Quarter Publication and QA

- Date: 2026-07-24
- Area: Hanoi arrival utility intent, Noi Bai airport to Old Quarter transfer decision, hotel pickup vs verified taxi vs app ride vs airport bus vs public bus, Plan hub support, homepage route spine, inbound related-route mesh, licensed photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: local static verifiers via `powershell -ExecutionPolicy Bypass -File ops\verify-hanoi-airport-to-old-quarter-static.ps1`, `ops\verify-hanoi-travel-guide-static.ps1`, `ops\verify-where-to-stay-in-hanoi-static.ps1`, `ops\verify-old-quarter-french-quarter-west-lake-static.ps1`, and `ops\verify-best-day-trips-hanoi-guide-static.ps1`; code-review pass and re-review after tightening sitemap and exact-label verification; remote staging under `/tmp/vg-hanoi-airport-transfer/ops/`; remote `php -l` for `ops/apply-hanoi-airport-to-old-quarter.php`, `ops/apply-homepage-premium.php`, `ops/verify-eeat-content.php`, `ops/purge-litespeed-cache.php`, and all touched inbound/source-persistence scripts; forced WP-CLI publish via `VG_FORCE_HANOI_AIRPORT_TRANSFER_REPUBLISH=1 wp eval-file /tmp/vg-hanoi-airport-transfer/ops/apply-hanoi-airport-to-old-quarter.php --allow-root`; homepage refresh via `wp eval-file /tmp/vg-hanoi-airport-transfer/ops/apply-homepage-premium.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML sitemap cache deletion; LiteSpeed purge-all; full `wp eval-file /tmp/vg-hanoi-airport-transfer/ops/verify-eeat-content.php --allow-root`; public HTTPS QA; homepage and Plan hub checks; sitemap inclusion check; rendered Wikimedia image checks.
- Expected result: `/plan/hanoi-airport-to-old-quarter/` publishes as an evidence-led arrival guide for international travelers making the first Hanoi transfer decision, with a clear default verdict, source trail, update log, photo-led hero/grid, proof panel, live checks, low-friction related routes, and no thin taxi-list framing.
- Actual outcome: page ID `316` published successfully at `https://vietnamguide.net/plan/hanoi-airport-to-old-quarter/`. Local static verification passed. Reviewer found no Critical blockers and two Important verifier gaps; `ops/verify-eeat-content.php` now checks `/page-sitemap.xml` for the new URL exactly once and requires exact inbound related-route label matching for the Hanoi airport route only, while preserving legacy label tolerance for older route clusters. Remote PHP lint passed after the verifier patch. Full EEAT verification passed after cache and sitemap refresh. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/plan/hanoi-airport-to-old-quarter/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, one H1, and the expected hero, verdict, at-a-glance, photo grid, source-diversity, verdict matrix, arrival flow, option scorecard, live checks, proof panel, source trail, update log, and related-routes markers. Homepage includes `/plan/hanoi-airport-to-old-quarter/`, the Plan hub includes `vg-hanoi-airport-transfer-plan-hub-note:v1`, and `/page-sitemap.xml` includes the URL exactly once. Rendered Wikimedia image checks returned HTTP `200` for the Noi Bai T2, Hoan Kiem, and Old Quarter/Dong Xuan images after browser-like retry for Wikimedia automation rate limiting.
- Evidence link/path: `https://vietnamguide.net/plan/hanoi-airport-to-old-quarter/`
- Follow-up: build `Hanoi in 2 Days` next if the goal is short-stay itinerary depth, or a broader `Hanoi Airport Guide` if the goal is to expand the Vietnam arrival/transport spine before adding more attractions pages.

### 2026-07-24 - Hanoi in 2 Days Publication and QA

- Date: 2026-07-24
- Area: Hanoi 48-hour itinerary support, arrival recovery, Hoan Kiem/Old Quarter orientation, food-led evening, culture block selection, weather pivots, day-trip pressure test, homepage/Itineraries hub wiring, inbound related-route mesh, licensed photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-hanoi-in-2-days-static.ps1`; regression static verifiers via `ops\verify-hanoi-airport-to-old-quarter-static.ps1`, `ops\verify-hanoi-travel-guide-static.ps1`, `ops\verify-where-to-stay-in-hanoi-static.ps1`, `ops\verify-old-quarter-french-quarter-west-lake-static.ps1`, and `ops\verify-best-day-trips-hanoi-guide-static.ps1`; remote staging under `/tmp/vg-hanoi-2-days/ops/`; remote `php -l` for `ops/apply-hanoi-in-2-days-itinerary.php`, `ops/apply-homepage-premium.php`, `ops/verify-eeat-content.php`, and `ops/purge-litespeed-cache.php`; forced WP-CLI publish via `VG_FORCE_HANOI_2_DAYS_REPUBLISH=1 wp eval-file /tmp/vg-hanoi-2-days/ops/apply-hanoi-in-2-days-itinerary.php --allow-root`; homepage refresh via `wp eval-file /tmp/vg-hanoi-2-days/ops/apply-homepage-premium.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML sitemap cache deletion; transient deletion; LiteSpeed purge-all; full `wp eval-file /tmp/vg-hanoi-2-days/ops/verify-eeat-content.php --allow-root`; public HTTPS QA; homepage and Itineraries hub checks; sitemap inclusion check; related-route metadata checks; rendered Wikimedia image range checks.
- Expected result: `/itineraries/hanoi-in-2-days/` publishes as an evidence-led short-stay itinerary for international travelers who need Hanoi to feel complete in 48 hours without exhausting the next transfer, with a clear concierge verdict, source trail, update log, photo-led hero/grid, proof panel, live checks, related routes, and no duplicate generic Hanoi attractions framing.
- Actual outcome: page ID `320` published successfully at `https://vietnamguide.net/itineraries/hanoi-in-2-days/`. Local static verification passed for the new sprint, and Hanoi cluster regression static verification passed for airport transfer, Hanoi Travel Guide, Where to Stay in Hanoi, Old Quarter vs French Quarter vs West Lake, and Best Day Trips from Hanoi. Remote PHP lint passed for all uploaded PHP scripts. Forced WP-CLI publish created the page under `/itineraries/`, refreshed the Itineraries hub note `vg-hanoi-2-days-itineraries-hub-note:v1`, refreshed the homepage side-effect block, and upserted `Hanoi in 2 Days` related-route metadata across 19 planning, Hanoi, and itinerary pages. Homepage canonical refresh added the route to the planning map, route verdict/related-route row, and reviewed guide shelf. Full EEAT verification passed after cache and sitemap refresh. A post-review hardening pass added the rendered `vg-hanoi-2-days-source-trail-snapshot:v1` band so core source names and URLs are visible as text without increasing body linkout; the tightened EEAT verifier now checks that rendered source snapshot directly. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/itineraries/hanoi-in-2-days/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, exactly one H1, and rendered the expected hero, verdict, at-a-glance, photo grid, source-diversity, source-trail snapshot, sequence, arrival pivot, stay-area fit, day-one/day-two plans, weather pivots, day-trip pressure test, budget/comfort, mistakes/skip, live checks, FAQ, proof panel, source trail, update log, and related-routes markers. Homepage and Itineraries hub both include `/itineraries/hanoi-in-2-days/`, `/page-sitemap.xml` includes the URL exactly once, related-route metadata checks passed for page `320` and 10 key inbound pages, all five rendered Wikimedia image URLs returned HTTP `206`, and the final rendered source snapshot check passed with five external hrefs, all image-credit links.
- Evidence link/path: `https://vietnamguide.net/itineraries/hanoi-in-2-days/`
- Follow-up: keep building the evergreen itinerary support layer only where it solves a real decision. Strong next candidates are narrow Hanoi support pages such as `Hanoi food itinerary for first-timers`, `Hanoi with kids`, or a `Hanoi vs Ninh Binh base` comparison if Search Console shows demand; otherwise move back to broader destination/top-list coverage to avoid over-concentrating the cluster.

### 2026-07-24 - Ninh Binh Day Trip vs Overnight Publication and QA

- Date: 2026-07-24
- Area: Ninh Binh comparison intent, day trip vs one-night vs two-night vs skip decision, northern route pacing, Compare/Destinations hub support, homepage route spine, inbound related-route mesh, licensed photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: local red/green static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-ninh-binh-day-trip-overnight-static.ps1`; regression static verifiers for Hanoi in 2 Days, Best Day Trips from Hanoi, Hanoi Travel Guide, 7 Days, and 21 Days; remote DB backup to `/root/vietnamguide-backups/20260724-pre-ninh-binh-day-trip-overnight.sql`; upload to `/tmp/vg-ninh-binh-day-trip-overnight/ops/`; remote `php -l` for the new page script, homepage script, EEAT verifier, foundation hub source, and 23 source-persistence apply scripts; forced WP-CLI publish via `VG_FORCE_NINH_BINH_DAY_TRIP_OVERNIGHT_REPUBLISH=1 wp eval-file ops/apply-ninh-binh-day-trip-overnight.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion; transient deletion; LiteSpeed purge-all; full `wp eval-file ops/verify-eeat-content.php --allow-root`; public HTTPS QA; homepage, Compare hub, and Destinations hub checks; sitemap inclusion check; rendered Wikimedia image range checks from the VPS.
- Expected result: `/compare/ninh-binh-day-trip-vs-overnight/` publishes as the evidence-led Ninh Binh pacing decision page for international travelers choosing a Hanoi day trip, one protected countryside night, two slow nights, or a clean skip, without duplicating the broader Ninh Binh Travel Guide or creating a thin listicle.
- Actual outcome: page ID `326` published successfully at `https://vietnamguide.net/compare/ninh-binh-day-trip-vs-overnight/`. Local static verification passed after adding a foundation-source persistence check for the new Compare and Destinations hub markers. Remote PHP lint passed for all uploaded PHP files. Forced publish created the page under `/compare/`, refreshed Compare hub marker `vg-ninh-binh-day-trip-overnight-compare-hub-note:v1`, refreshed Destinations hub marker `vg-ninh-binh-day-trip-overnight-destinations-hub-note:v1`, refreshed the homepage side-effect block, and upserted the new comparison route across 23 key planning, Hanoi, Ninh Binh, bay, safety, cost, and itinerary pages. Homepage canonical refresh added the new page to the planning map, route verdict, related decision row, and reviewed guide shelf. Reviewer found a durable hub-source gap in `ops/apply-foundation-seo-content.php`; the source was patched so a future foundation reset preserves the new Ninh Binh compare/destinations hub notes, and the sprint static verifier now enforces that. Full EEAT verification passed after cache and sitemap refresh. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/compare/ninh-binh-day-trip-vs-overnight/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, exactly one H1, and rendered the expected hero, verdict, at-a-glance, photo grid, source-diversity, source-trail snapshot, decision matrix, route-fit table, sample schedules, transfer-pressure test, Trang An vs Tam Coc logic, base logic, weather/crowd pivots, cost/comfort matrix, mistakes/skip logic, live checks, FAQ, proof panel, source trail, update log, and related-routes markers. Homepage, Compare hub, and Destinations hub all include `/compare/ninh-binh-day-trip-vs-overnight/`, `/page-sitemap.xml` includes the URL exactly once, visible external href count is five and all are Wikimedia image-credit links, and VPS range checks returned HTTP `206` for all five rendered Wikimedia image URLs.
- Evidence link/path: `https://vietnamguide.net/compare/ninh-binh-day-trip-vs-overnight/`
- Follow-up: continue the northern scenery/route-pressure cluster with `Hanoi to Ninh Binh Transport` or `Trang An vs Tam Coc` only if the page can answer a distinct booking decision; otherwise move to broader evergreen destination/top-list coverage to keep publication volume useful rather than repetitive.

### 2026-07-24 - Hanoi to Ninh Binh Transport Publication and QA

- Date: 2026-07-24
- Area: Hanoi to Ninh Binh utility intent, train vs limousine van vs private car vs day tour vs onward transfer, Ninh Binh base/drop-off logic, Plan and Destinations hub support, homepage route spine, inbound related-route mesh, licensed photo proof, source-diversity standard, premium editorial UX, EEAT
- Command/check: local red/green static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-hanoi-to-ninh-binh-transport-static.ps1`; full local static verifier sweep across `ops\verify-*-static.ps1`; remote upload to `/tmp/hanoi-ninh-binh-transport/ops/`; remote `php -l` for the new page script, homepage script, foundation hub source, EEAT verifier, and touched source-persistence apply scripts; forced WP-CLI publish via `VG_FORCE_HANOI_NINH_BINH_TRANSPORT_REPUBLISH=1 wp eval-file ops/apply-hanoi-to-ninh-binh-transport.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion; transient deletion; LiteSpeed purge-all; full `wp eval-file ops/verify-eeat-content.php --allow-root`; public HTTPS QA; homepage, Plan hub, and Destinations hub checks; sitemap inclusion check; rendered Wikimedia image range checks.
- Expected result: `/plan/hanoi-to-ninh-binh-transport/` publishes as the evidence-led transfer decision page for international travelers who already know Ninh Binh belongs in the route and now need to choose train, limousine van, private car, day tour, or onward transfer by actual drop-off, luggage, hotel base, timing, weather, and next-route fragility without copying stale timetables, fares, or operator claims.
- Actual outcome: page ID `331` published successfully at `https://vietnamguide.net/plan/hanoi-to-ninh-binh-transport/`. Local static verification first failed correctly because the apply script did not exist, then passed after the guide, homepage, hub notes, EEAT verifier, and source-persistence updates were added. A full local static sweep across all existing static verifiers passed. Remote PHP lint passed for all uploaded PHP files. Forced publish created the page under `/plan/`, refreshed Plan hub marker `vg-hanoi-ninh-binh-transport-plan-hub-note:v1`, refreshed Destinations hub marker `vg-hanoi-ninh-binh-transport-destinations-hub-note:v1`, refreshed the homepage side-effect block, and upserted the transport route across Ninh Binh, Hanoi, transport, money, SIM/eSIM, safety, insurance, itinerary, and bay pages. The spec reviewer caught a source-persistence gap for `ops/apply-ha-long-bay-travel-guide.php` and `ops/apply-ha-long-lan-ha-comparison-guide.php`; both scripts and the sprint static verifier were patched so future bay republish operations preserve the new route line. Homepage canonical refresh added the new page to the planning map, route verdict row, and reviewed guide shelf. Full EEAT verification passed after cache and sitemap refresh. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/plan/hanoi-to-ninh-binh-transport/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, exactly one H1, homepage/Plan/Destinations links present, `/page-sitemap.xml` includes the URL exactly once, visible external href count is five and all are Wikimedia image-credit links, and range checks returned HTTP `206` for all four unique rendered Wikimedia image URLs.
- Evidence link/path: `https://vietnamguide.net/plan/hanoi-to-ninh-binh-transport/`
- Follow-up: continue the northern route-pressure cluster only with distinct decision pages, such as `Trang An vs Tam Coc`, `Ninh Binh to Ha Long Bay Transfer`, or a deeper `Where to Stay in Ninh Binh`, and avoid generic transport listicles unless live data and route judgment can add durable value.

### 2026-07-24 - Trang An vs Tam Coc Publication and QA

- Date: 2026-07-24
- Area: Ninh Binh boat-route comparison intent, Trang An vs Tam Coc decision, day-trip versus overnight fit, base rhythm, crowd timing, season/photography logic, Compare/Destinations hub support, homepage route spine, inbound related-route mesh, source-diversity standard, premium editorial UX, text-only image credits, EEAT
- Command/check: local red/green static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-trang-an-vs-tam-coc-static.ps1`; full local static verifier sweep across all `ops\verify-*-static.ps1`; Wikimedia Commons API image URL/license lookup for Trang An, Tam Coc, Tam Coc aerial, Trang An route, and Mua Cave images; remote upload to `/tmp/trang-an-tam-coc/ops/`; remote `php -l` for the new page script, homepage script, foundation hub source, EEAT verifier, and touched source-persistence apply scripts; forced WP-CLI publish via `VG_FORCE_TRANG_AN_TAM_COC_REPUBLISH=1 wp eval-file ops/apply-trang-an-vs-tam-coc.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion; transient deletion; LiteSpeed purge-all; full `wp eval-file ops/verify-eeat-content.php --allow-root`; public HTTPS QA; homepage, Compare hub, and Destinations hub checks; sitemap inclusion check; rendered Wikimedia image range checks with retry/backoff.
- Expected result: `/compare/trang-an-vs-tam-coc/` publishes as the evidence-led Ninh Binh boat-choice decision page for international travelers choosing one high-confidence Trang An route, a softer Tam Coc countryside/base day, both only when the second route changes the trip, or a cleaner skip if the northern route is already overfull.
- Actual outcome: page ID `336` published successfully at `https://vietnamguide.net/compare/trang-an-vs-tam-coc/`. The targeted static verifier first failed correctly because the apply script did not exist, then passed after the guide, homepage wiring, foundation hub persistence, EEAT verifier, and source-persistence updates were added. Full local static sweep passed across 28 static verifiers. Remote PHP lint passed for all 29 uploaded PHP files. Forced publish created the page under `/compare/`, refreshed Compare hub marker `vg-trang-an-tam-coc-compare-hub-note:v1`, refreshed Destinations hub marker `vg-trang-an-tam-coc-destinations-hub-note:v1`, refreshed the homepage side-effect block, and upserted the new comparison route across 25 key Ninh Binh, Hanoi, bay, planning, safety, cost, and itinerary pages. Homepage canonical refresh added the page to the planning map, default route verdict, related route decisions, and reviewed guide shelf. Full EEAT initially caught a verifier ownership bug where hub/homepage markers were checked against the page body; `ops/verify-eeat-content.php` was corrected to check those markers on the actual Compare hub, Destinations hub, and homepage, then EEAT verification passed. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/compare/trang-an-vs-tam-coc/`, robots `index, follow`, exactly one H1, all expected hero, verdict, at-a-glance, photo proof, source-diversity, source-trail snapshot, decision matrix, route comparison, traveler fit, season/photography, crowd timing, base logistics, day-trip/overnight, booking audit, mistakes/skip, live checks, FAQ, proof panel, source trail, update log, and related-routes markers. Homepage, Compare hub, and Destinations hub all include `/compare/trang-an-vs-tam-coc/`, `/page-sitemap.xml` includes the URL exactly once, visible external href count is `0` because image credits are text-only, and all five rendered Wikimedia image URLs returned HTTP `206` after backoff.
- Evidence link/path: `https://vietnamguide.net/compare/trang-an-vs-tam-coc/`
- Follow-up: next high-value Ninh Binh nodes are `Where to Stay in Ninh Binh` for accommodation/base intent or `Ninh Binh to Ha Long Bay Transfer` for route-pressure intent. Avoid another generic Ninh Binh attractions page unless it adds distinct decision value and original judgment.

### 2026-07-24 - Where to Stay in Ninh Binh Publication and QA

- Date: 2026-07-24
- Area: Ninh Binh accommodation/base intent, Tam Coc vs Trang An area vs Ninh Binh city vs Van Long/Gia Vien vs Cuc Phuong fringe, northern route stay-area judgment, Destinations hub support, homepage route spine, inbound related-route mesh, source-diversity standard, text-only image credits, premium editorial UX, EEAT
- Command/check: local targeted static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-where-to-stay-in-ninh-binh-static.ps1`; full local static verifier sweep across all `ops\verify-*-static.ps1`; guide-only subagent audit of exact guide-file markers, phrases, image credits, related-route dedupe logic, image count, and visible external href budget; remote upload to `/tmp/ninh-binh-stays/ops/`; remote `php -l` for 30 uploaded PHP files including the new page script, homepage script, foundation hub source, EEAT verifier, and all touched inbound/source-persistence scripts; forced WP-CLI publish via `VG_FORCE_NINH_BINH_STAYS_REPUBLISH=1 wp eval-file ops/apply-where-to-stay-in-ninh-binh.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion; transient deletion; LiteSpeed purge-all; full `wp eval-file ops/verify-eeat-content.php --allow-root`; public HTTPS QA via `powershell -ExecutionPolicy Bypass -File ops\verify-where-to-stay-in-ninh-binh-public-qa.ps1`.
- Expected result: `/destinations/where-to-stay-in-ninh-binh/` publishes as the evidence-led Ninh Binh stay-area decision page for international travelers choosing a base by route job, pickup timing, sleep tolerance, dinner choice, luggage, children, weather, boat timing, and next-transfer pressure instead of affiliate hotel rankings or generic best-hotel listicles.
- Actual outcome: page ID `341` published successfully at `https://vietnamguide.net/destinations/where-to-stay-in-ninh-binh/`. Targeted static verification passed, and the full local static sweep passed across 29 static verifiers. The subagent guide-only audit found `60` guide assertions checked, `0` missing or forbidden-string failures, `6` images, and `0` visible external body hrefs. Remote PHP lint passed for all 30 uploaded PHP files. Forced publish created the page under `/destinations/`, refreshed Destinations hub marker `vg-ninh-binh-stays-destinations-hub-note:v1`, refreshed the homepage side-effect block, and upserted the new route across 26 key Ninh Binh, Hanoi, bay, planning, cost, safety, insurance, and itinerary pages. Homepage canonical refresh added the page to the planning map, route verdict row, and reviewed guide shelf with the default verdict that Tam Coc is the default Ninh Binh base unless another job is clearer. Full EEAT verification passed after cache and sitemap refresh. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/where-to-stay-in-ninh-binh/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, exactly one H1, homepage and Destinations hub links present, `/page-sitemap.xml` includes the URL exactly once, visible external href count is `0`, and all five rendered Wikimedia image URLs returned HTTP `206` with retry/backoff support.
- Evidence link/path: `https://vietnamguide.net/destinations/where-to-stay-in-ninh-binh/`
- Follow-up: use this page as the durable accommodation/base node for the Ninh Binh cluster. Strong next evergreen candidates are `Ninh Binh to Ha Long Bay Transfer`, `Tam Coc Travel Guide`, or a broader `Northern Vietnam Itinerary` only if each page answers a distinct planning job and preserves the no-spam, evidence-led concierge standard.

### 2026-07-25 - Ninh Binh to Ha Long Bay Transfer Publication and QA

- Date: 2026-07-25
- Area: Ninh Binh to Ha Long Bay/Lan Ha/Cat Ba transfer intent, cruise-port handoff risk, Tuan Chau vs Ha Long International Cruise Port vs Hon Gai vs Got Pier logic, pickup-window and luggage planning, Plan/Destinations hub support, homepage route spine, inbound related-route mesh, licensed photo proof, text-only image credits, premium editorial UX, EEAT
- Command/check: local red/green targeted static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-ninh-binh-to-ha-long-transfer-static.ps1`; full local static verifier sweep across 30 `ops\verify-*-static.ps1` scripts; remote staging under `/tmp/ninh-binh-ha-long-transfer/ops/`; remote `php -l` for 30 uploaded PHP files; forced WP-CLI publish via `VG_FORCE_NINH_BINH_HA_LONG_TRANSFER_REPUBLISH=1 wp eval-file ops/apply-ninh-binh-to-ha-long-transfer.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; foundation refresh via `wp eval-file ops/apply-foundation-seo-content.php --allow-root`; hub-note repair via `wp eval-file ops/repair-hub-notes-from-apply-scripts.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion; transient deletion; LiteSpeed purge-all; full `wp eval-file ops/verify-eeat-content.php --allow-root`; public HTTPS QA via `powershell -ExecutionPolicy Bypass -File ops\verify-ninh-binh-to-ha-long-transfer-public-qa.ps1`.
- Expected result: `/plan/ninh-binh-to-ha-long-bay-transfer/` publishes as the evidence-led northern transfer handoff page for international travelers moving from Ninh Binh to a bay cruise or Cat Ba/Lan Ha endpoint, with route judgment based on exact port, pickup window, luggage, weather, cruise check-in risk, and whether an overnight buffer is safer than a brittle same-day ride.
- Actual outcome: page ID `345` published successfully at `https://vietnamguide.net/plan/ninh-binh-to-ha-long-bay-transfer/`. Targeted static verification passed after adding the page publisher, homepage/foundation wiring, EEAT verifier coverage, source-persistence route lines, and public QA script. Full local static sweep passed across 30 static verifiers. Remote PHP lint passed for the staged PHP bundle. Forced publish created the page under `/plan/`, refreshed Plan hub marker `vg-ninh-binh-ha-long-transfer-plan-hub-note:v1`, refreshed Destinations hub marker `vg-ninh-binh-ha-long-transfer-destinations-hub-note:v1`, refreshed the homepage route-spine block, and upserted the new route across 26 key Ninh Binh, Hanoi, bay, planning, cost, safety, insurance, and itinerary pages. A live EEAT run then exposed a foundation-reset risk: running `apply-foundation-seo-content.php` can rewrite hub pages and remove guide-specific hub notes. The new repair script restores only verifier-required hub-note blocks from existing `apply-*` sources, found the missing source scripts, restored 35 hub notes, and kept 8 notes unchanged. Final live EEAT verification passed after cache and sitemap refresh. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/plan/ninh-binh-to-ha-long-bay-transfer/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, exactly one H1, homepage/Plan/Destinations links present, `/page-sitemap.xml` includes the URL exactly once, visible external href count is `0`, five Wikimedia image URLs render, five image-credit mentions render, and all five image range checks returned HTTP `206`.
- Evidence link/path: `https://vietnamguide.net/plan/ninh-binh-to-ha-long-bay-transfer/`
- Follow-up: keep this as the northern scenery handoff utility node. Future foundation refreshes should be followed by `ops/repair-hub-notes-from-apply-scripts.php` or the foundation script should be upgraded to preserve all verifier-required hub notes directly.

### 2026-07-25 - Tam Coc Travel Guide Publication and QA

- Date: 2026-07-25
- Area: Tam Coc destination/base intent, Ninh Binh countryside rhythm, boat timing, Bich Dong, Mua Cave, cycling/walking logic, rice-season expectations, stay/eat/cost judgment, Destinations hub support, homepage route spine, inbound related-route mesh, text-only image credits, premium editorial UX, EEAT
- Command/check: targeted static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-tam-coc-travel-guide-static.ps1`; full local static verifier sweep across 31 `ops\verify-*-static.ps1` scripts; mechanical repair of 27 source-persistence apply scripts after remote PHP lint exposed a bare Tam Coc related-route line outside PHP strings; remote staging under `/tmp/tam-coc-travel-guide-20260725095906/ops/`; remote `php -l` for 33 staged PHP files; copy to `/usr/local/lsws/vietnamguide.net/html` with backup under `wp-content/vg-backups/tam-coc-20260725100024`; forced WP-CLI publish via `VG_FORCE_TAM_COC_TRAVEL_GUIDE_REPUBLISH=1 wp eval-file ops/apply-tam-coc-travel-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; foundation refresh via `wp eval-file ops/apply-foundation-seo-content.php --allow-root`; hub-note repair via `wp eval-file ops/repair-hub-notes-from-apply-scripts.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion; transient deletion; LiteSpeed purge-all; full `wp eval-file ops/verify-eeat-content.php --allow-root`; public HTTPS QA via `powershell -ExecutionPolicy Bypass -File ops\verify-tam-coc-travel-guide-public-qa.ps1`.
- Expected result: `/destinations/tam-coc-travel-guide/` publishes as the evidence-led Tam Coc base guide for international travelers deciding whether Tam Coc should be a soft Ninh Binh base, a boat stop, a cycling-and-evening chapter, or a clean skip when Trang An, the bay, Hanoi, or another northern scenery page already does the job.
- Actual outcome: page ID `418` published successfully at `https://vietnamguide.net/destinations/tam-coc-travel-guide/`. Targeted static verification passed, and the full local static sweep passed across 31 static verifiers. Remote PHP lint passed for all 33 staged PHP files after the Tam Coc related-route line was moved inside the relevant `vg_eeat_related_routes` strings and added to the Trang An vs Tam Coc metadata string instead of its inbound-refresh function. Forced publish created the page under `/destinations/`, refreshed Destinations hub marker `vg-tam-coc-destinations-hub-note:v1`, refreshed homepage marker `vg-tam-coc-homepage-route-spine:v1`, and upserted the Tam Coc route across 27 key Ninh Binh, Hanoi, bay, planning, cost, safety, insurance, and itinerary pages. Homepage and foundation refresh completed, hub-note repair restored 35 verifier-required notes and left 8 unchanged, and final live EEAT verification passed. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/tam-coc-travel-guide/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, exactly one H1, homepage and Destinations hub links present, `/page-sitemap.xml` includes the URL exactly once, visible external href count is `0`, five Wikimedia image URLs render, five image-credit mentions render, and all five range checks returned HTTP `206`. Post-review hardening updated the homepage metadata date/summary to include Tam Coc, Where to Stay in Ninh Binh, and the Ninh Binh-to-bay transfer, and added a repair-mode guard so inbound Tam Coc related-route refresh cannot run before the Tam Coc page is published.
- Evidence link/path: `https://vietnamguide.net/destinations/tam-coc-travel-guide/`
- Follow-up: use Tam Coc as the soft-base node in the Ninh Binh cluster. Strong next evergreen moves are a broader `Northern Vietnam itinerary` route page, a restrained `Best things to do in Ninh Binh` page only if it adds distinct decision value, or a `Bich Dong Pagoda` micro-guide if Search Console shows enough demand.

### 2026-07-25 - Best Beaches in Vietnam GSC CTR Hardening and QA

- Date: 2026-07-25
- Area: Best Beaches in Vietnam, GSC opportunity hardening, international traveler beach-selection intent, month-by-month beach planning, traveler-fit decision logic, homepage route spine, inbound related-route mesh, source-diversity standard, text-only image credits, premium editorial UX, EEAT
- Command/check: GSC CSV review identified `/destinations/best-beaches-in-vietnam/` as a high-position, zero-CTR opportunity; targeted static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-best-beaches-static.ps1`; full local static verifier sweep across 32 `ops\verify-*-static.ps1` scripts; remote staging under `/tmp/best-beaches-hardening-20260725104443`; remote `php -l` for staged PHP files; copy to `/usr/local/lsws/vietnamguide.net/html` with backup under `wp-content/vg-backups/best-beaches-hardening-20260725104518`; forced WP-CLI publish via `VG_FORCE_BEST_BEACHES_GUIDE_REPUBLISH=1 wp eval-file ops/apply-best-beaches-vietnam-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; WordPress cache flush; rewrite flush; Rank Math XML cache deletion; transient deletion; LiteSpeed purge-all; mini-fix staging under `/tmp/best-beaches-fix-20260725104844` after live QA tightened rendered marker checks; final `wp eval-file ops/verify-eeat-content.php --allow-root`; public HTTPS QA via `powershell -ExecutionPolicy Bypass -File ops\verify-best-beaches-public-qa.ps1`.
- Expected result: `/destinations/best-beaches-in-vietnam/` should move from a generic beach list toward a CTR-worthy decision guide for international travelers choosing the best Vietnam beach by month, route, weather risk, trip style, and first-trip safety, while keeping the page premium, evidence-led, low-linkout, and distinct from island or city destination guides.
- Actual outcome: page ID `204` republished successfully at `https://vietnamguide.net/destinations/best-beaches-in-vietnam/`. The page now uses the stronger H1 `Best Beaches in Vietnam: Where to Go by Month and Trip Style`, Rank Math title `Best Beaches in Vietnam: Where to Go by Month`, and description beginning `Choose the best beach in Vietnam by month, route, and trip style`. New rendered markers include `vg-best-beaches-source-diversity:v1`, `vg-best-beaches-source-trail-snapshot:v1`, `vg-best-beaches-chooser:v1`, `vg-best-beaches-month-planner:v1`, and `vg-best-beaches-traveler-fit:v1`. The article adds the first-trip default verdict for Da Nang/My Khe or Hoi An/An Bang, reframes the page as a beach decision guide instead of a prettiest-beach ranking, explains source limitations, uses text-only image credits, and strengthens related-route coverage to Best Islands, Phu Quoc, Da Nang, Da Nang vs Hoi An, Phu Quoc vs Nha Trang, Con Dao, Nha Trang, Quy Nhon, Mui Ne vs Nha Trang, Cham Islands, Ly Son, and 21 Days. Final live EEAT verification passed. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/best-beaches-in-vietnam/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, exactly one H1, homepage and Destinations hub links present, `/page-sitemap.xml` includes the URL exactly once, visible external href count is `0`, eight Wikimedia image URLs render, nine image-credit mentions render, and all eight range checks returned HTTP `206`.
- Evidence link/path: `https://vietnamguide.net/destinations/best-beaches-in-vietnam/`
- Follow-up: monitor GSC query mix for `best beaches in vietnam`, `vietnam beaches`, `best beach vietnam by month`, and city/island modifiers over the next 28 days. If impressions grow but CTR stays weak, test a tighter title such as `Best Beaches in Vietnam by Month: Da Nang, Phu Quoc, Nha Trang` and add a compact above-the-fold beach chooser table before writing another beach list.

### 2026-07-25 - 14 Days in Vietnam GSC CTR Hardening and QA

- Date: 2026-07-25
- Area: 14 Days in Vietnam, GSC opportunity hardening, two-week itinerary intent, route-by-month and travel-style decision logic, homepage route spine, itineraries hub support, related-route mesh, source-diversity standard, text-only image credits, premium editorial UX, EEAT
- Command/check: GSC CSV review identified `/itineraries/14-days-in-vietnam/` as a page with `85` impressions, average position `9.19`, and `0%` CTR; targeted static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-14-days-guide-static.ps1`; full local static verifier sweep across `33` `ops\verify-*-static.ps1` scripts; read-only subagent audit of the existing 14-day guide and post-implementation code review; remote staging under `/tmp/14-days-hardening-*`, `/tmp/14-days-related-fix-*`, `/tmp/14-days-final-hardening-*`, and `/tmp/14-days-eeat-text-credit-fix-*`; remote `php -l` for staged PHP files; copy to `/usr/local/lsws/vietnamguide.net/html` with backups under `wp-content/vg-backups/14-days-*`; forced WP-CLI publish via `VG_FORCE_14_DAY_ITINERARY_REPUBLISH=1 wp eval-file ops/apply-14-days-itinerary-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; WordPress cache flush; rewrite flush; transient deletion; Rank Math sitemap cache delete attempt; LiteSpeed purge-all; final `wp eval-file ops/verify-eeat-content.php --allow-root`; public HTTPS QA via `powershell -ExecutionPolicy Bypass -File ops\verify-14-days-guide-public-qa.ps1`.
- Expected result: `/itineraries/14-days-in-vietnam/` should move from a broad itinerary page toward a CTR-worthy two-week route decision guide for international travelers choosing by month, route shape, travel style, transfer pressure, and extension discipline, while preserving the Evidence-led Concierge template and avoiding generic maximum-coverage itinerary spam.
- Actual outcome: page ID `20` republished successfully at `https://vietnamguide.net/itineraries/14-days-in-vietnam/`. The page now uses the stronger H1 `14 Days in Vietnam: Best Two-Week Route by Month and Travel Style`, Rank Math title `14 Days in Vietnam Itinerary: Best Two-Week Route`, and description beginning `Plan 14 days in Vietnam by month, route shape, and travel style`. New or hardened rendered markers include `vg-itinerary-14day-hero:v1`, `vg-itinerary-14day-concierge-verdict`, `vg-itinerary-14day-at-a-glance:v1`, `vg-itinerary-14day-source-diversity:v1`, `vg-itinerary-14day-source-trail-snapshot:v1`, and `vg-itinerary-14day-route-chooser:v1`. The guide now adds a fast first-trip answer, an anti-spam maximum-coverage warning, a source-limitation explanation, a visible source snapshot, a traveler-job route chooser, text-only image credits, refreshed July 25, 2026 source metadata, and a fuller related-route mesh across itinerary, timing, beach, Hanoi, Ninh Binh, bay, central coast, island, and southern route-support pages. Homepage route links now require the 14-day page to be published, and homepage image captions were converted from visible Wikimedia credit anchors to text-only credits. Final live EEAT verification passed after updating the verifier to check the homepage Mui Ne credit in text-only format. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/itineraries/14-days-in-vietnam/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, exactly one H1, homepage and Itineraries hub links present, `/page-sitemap.xml` includes the URL exactly once, visible external href count is `0`, eight Wikimedia image URLs render, nine image-credit mentions render, and all eight image range checks returned HTTP `206`.
- Evidence link/path: `https://vietnamguide.net/itineraries/14-days-in-vietnam/`
- Follow-up: monitor GSC queries around `14 days in Vietnam`, `Vietnam 2 week itinerary`, `Vietnam itinerary 14 days`, and route-style modifiers for 28 days. If impressions rise but CTR stays weak, test a title variant with stronger entity coverage such as `14 Days in Vietnam Itinerary: Hanoi, Hoi An, Ha Long, HCMC` and consider adding a compact route chooser directly after the hero on mobile if scroll depth is weak.

### 2026-07-25 - UNESCO Heritage Sites in Vietnam GSC CTR Hardening and QA

- Date: 2026-07-25
- Area: UNESCO Heritage Sites in Vietnam, GSC CTR opportunity hardening, international traveler heritage-route intent, 2025 UNESCO freshness, source-diversity standard, text-only image credits, homepage required-path support, Destinations hub support, related-route mesh, premium editorial UX, EEAT
- Command/check: GSC CSV review identified `/destinations/unesco-heritage-sites-vietnam/` with `45` impressions, average position `5.84`, and `0%` CTR; targeted red/green static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-unesco-heritage-static.ps1`; full local static verifier sweep across `34` `ops\verify-*-static.ps1` scripts; read-only source/audit subagents for UNESCO 2025 facts and current implementation risks; remote staging under `/tmp/unesco-heritage-hardening-20260725114228`, `/tmp/unesco-heritage-related-fix-20260725114548`, and `/tmp/unesco-heritage-public-qa-sync-20260725115001`; remote `php -l` for `ops/apply-unesco-heritage-sites-guide.php`, `ops/apply-homepage-premium.php`, and `ops/verify-eeat-content.php`; copy to `/usr/local/lsws/vietnamguide.net/html` with backups under `wp-content/vg-backups/unesco-heritage-hardening-20260725114228`, `wp-content/vg-backups/unesco-heritage-related-fix-20260725114548`, and `wp-content/vg-backups/unesco-heritage-public-qa-sync-20260725115001`; forced WP-CLI republish via `VG_FORCE_HERITAGE_GUIDE_REPUBLISH=1 wp eval-file ops/apply-unesco-heritage-sites-guide.php --allow-root`; homepage refresh via `wp eval-file ops/apply-homepage-premium.php --allow-root`; WordPress cache flush; rewrite flush; transient deletion; LiteSpeed purge-all; live `wp eval-file ops/verify-eeat-content.php --allow-root`; public HTTPS QA via `powershell -ExecutionPolicy Bypass -File ops\verify-unesco-heritage-public-qa.ps1`.
- Expected result: `/destinations/unesco-heritage-sites-vietnam/` should move from a broad heritage list toward a CTR-worthy 2025 route decision guide for international travelers deciding which World Heritage properties deserve nights, day trips, context, or a clean skip, while preserving the Evidence-led Concierge template and avoiding visible source/link clutter.
- Actual outcome: page ID `170` republished successfully at `https://vietnamguide.net/destinations/unesco-heritage-sites-vietnam/`. The page now uses the stronger H1 `UNESCO Heritage Sites in Vietnam: Which Ones Belong in Your Route`, Rank Math title `UNESCO Heritage Sites in Vietnam: 2025 Route Guide`, and description beginning `Choose which UNESCO World Heritage Sites in Vietnam fit your route`. New or hardened rendered markers include `vg-unesco-heritage-at-a-glance:v1`, `vg-unesco-heritage-source-diversity:v1`, `vg-unesco-heritage-source-trail-snapshot:v1`, `vg-unesco-heritage-2025-updates:v1`, `vg-unesco-heritage-route-chooser:v1`, `vg-unesco-heritage-site-by-site:v1`, `vg-unesco-heritage-itinerary-length:v1`, `vg-unesco-heritage-live-checks:v1`, and `vg-unesco-heritage-faq:v1`. The guide now explains that Vietnam has `9` UNESCO World Heritage properties (`6` cultural, `2` natural, `1` mixed), highlights the 2025 Yen Tu inscription and the 2025 Phong Nha-Ke Bang/Hin Nam No transboundary update, warns not to count Phong Nha-Hin Nam No as a 10th Vietnam property, adds itinerary-length decision logic, and shows 9 Wikimedia images with text-only credits. Homepage now uses `vg_home_required_path('destinations/unesco-heritage-sites-vietnam')` for the UNESCO guide row. Live EEAT verification passed after restoring related-route lines required by the wider Da Nang, Cat Ba, Bai Tu Long, and Ninh Binh clusters. Post-review hardening added public QA checks for the rendered `<title>` and meta description so the CTR-facing SEO metadata cannot regress silently. Public HTML returned HTTP `200`, canonical `https://vietnamguide.net/destinations/unesco-heritage-sites-vietnam/`, robots `index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, SEO title `UNESCO Heritage Sites in Vietnam: 2025 Route Guide`, meta description `Choose which UNESCO World Heritage Sites in Vietnam fit your route, with 2025 updates, first-trip priorities, skip logic, source checks, and photo proof.`, exactly one H1, homepage and Destinations hub links present, `/page-sitemap.xml` includes the URL exactly once, visible external href count is `0`, nine Wikimedia image URLs render, ten image-credit mentions render, and all nine image range checks returned HTTP `206`.
- Evidence link/path: `https://vietnamguide.net/destinations/unesco-heritage-sites-vietnam/`
- Follow-up: monitor GSC queries around `unesco heritage sites in vietnam`, `unesco sites vietnam`, `vietnam world heritage sites`, `yen tu unesco`, and `phong nha hin nam no` over the next 28 days. If impressions rise but CTR stays weak, test a title variant with stronger entity coverage such as `UNESCO Sites in Vietnam: 2025 World Heritage Route Guide` and add a compact mobile-first 2025 update card immediately below the hero.

### 2026-07-25 - WordPress Admin-First Workflow Guard

- Date: 2026-07-25
- Area: WordPress Admin-first editorial operations, script overwrite protection, Pages vs Posts workflow, verification-only QA
- Command/check: red/green static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-admin-first-workflow-static.ps1`; full local static verifier sweep across all `ops\verify-*-static.ps1` scripts; read-only subagent inventory of `59` `ops/apply-*.php` write paths.
- Expected result: published Pages and Posts can be manually optimized in WordPress Admin without routine WP-CLI automation overwriting protected content, Rank Math metadata, EEAT fields, featured images, or manually drifted automation-owned content.
- Actual outcome: Admin-first workflow code was added to the MU plugin with visible ACF workflow fields, WP-CLI write guards, explicit override policy, automation baseline hashes, a dry-run-first ownership marker script, and a read-only runtime verifier. Static verification passed, and the full local static sweep passed across `35` static verifiers. Local PHP lint was not run because `php` is not installed in PATH and current batch SSH authentication was unavailable without interactive prompting.
- Evidence link/path: `ops/verify-admin-first-workflow-static.ps1`, `ops/verify-admin-first-workflow.php`, `ops/mark-wordpress-admin-owned-content.php`, `docs/editorial/wordpress-admin-first-operating-model.md`
- Follow-up: before production deployment, run server-side `php -l` on the touched PHP files, back up `wp-content/mu-plugins/vietnamguide-core.php`, deploy the MU plugin and scripts, then run `wp eval-file ops/verify-admin-first-workflow.php --allow-root`. Rotate exposed root/WP credentials after the workflow migration is stable.

### 2026-07-25 - WordPress Admin-First Live Deployment

- Date: 2026-07-25
- Area: Live Admin-first deployment, published content ownership migration, overwrite regression test, verification-only QA
- Command/check: staged file upload to `/tmp/vg-admin-first-20260725125156`; server-side `php -l` for `wp-content/mu-plugins/vietnamguide-core.php`, `ops/mark-wordpress-admin-owned-content.php`, and `ops/verify-admin-first-workflow.php`; backup to `/root/vietnamguide-backups/20260725-125340-admin-first-workflow`; deploy of the MU plugin, marker script, runtime verifier, static verifier, runbook, plan, and verification log; dry-run marker for published content; apply marker with `VG_ADMIN_FIRST_MARK_ALL=1 VG_ADMIN_FIRST_STATUSES=publish VG_ADMIN_FIRST_MARK_APPLY=1`; locked-post regression test using a temporary draft page; live `wp eval-file ops/verify-admin-first-workflow.php --allow-root`; live `wp eval-file ops/verify-eeat-content.php --allow-root`; public HTTP checks for the homepage, UNESCO guide, and Rank Math sitemap.
- Expected result: all currently published Pages/Posts are protected for WordPress Admin management, automation cannot overwrite locked content without an explicit override, and public SEO/EEAT checks continue to pass.
- Actual outcome: server-side PHP lint passed for all three touched PHP files. `68` published pages were marked `vg_content_owner=wp_admin` and `vg_automation_lock=locked`; draft content was left unlocked. Runtime verifier passed with `68` locked items and no owner/lock consistency failures. A temporary draft page regression test confirmed that WP-CLI content overwrite is blocked for locked content and the attempted content change did not persist. EEAT verification passed. Public checks returned `HTTP 200` for `https://vietnamguide.net/`, `https://vietnamguide.net/destinations/unesco-heritage-sites-vietnam/`, and `https://vietnamguide.net/sitemap_index.xml`.
- Evidence link/path: `https://vietnamguide.net/`, `https://vietnamguide.net/sitemap_index.xml`, `/root/vietnamguide-backups/20260725-125340-admin-first-workflow`
- Follow-up: future scripted content republishes for locked public pages must either be converted to WordPress Admin edits or run only after a backup with `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1`. Rotate root and WordPress administrator credentials because credentials were previously shared in chat.

### 2026-07-25 - Native WordPress Posts Editorial System

- Date: 2026-07-25
- Area: WordPress-native post management, content calendar setup, non-spam draft pipeline, categories/tags, Admin-first locked briefs
- Command/check: live inventory showed `0` Posts, `68` published Pages, one category, and no tags; local red/green static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-wordpress-post-system-static.ps1`; full local static verifier sweep across `36` static verifiers; staged upload to `/tmp/vg-post-system-20260725130457`; server-side `php -l` for `ops/apply-wordpress-post-editorial-system.php` and `ops/verify-wordpress-post-editorial-system.php`; backup to `/root/vietnamguide-backups/20260725-130524-post-editorial-system`; live apply via `wp eval-file ops/apply-wordpress-post-editorial-system.php --allow-root`; live verifier via `wp eval-file ops/verify-wordpress-post-editorial-system.php --allow-root`; Admin-first verifier, EEAT verifier, homepage HTTP, and sitemap HTTP checks.
- Expected result: WordPress Posts should no longer be empty, but the site should avoid spam by creating locked draft editorial briefs only, with no published or scheduled incomplete posts.
- Actual outcome: created `10` native WordPress draft post briefs, `10` editorial categories, and `12` tags. Published Posts remain `0`; future/scheduled Posts remain `0`; the post editorial verifier reported `10` draft briefs and `0` published editorial briefs. Admin-first verifier passed with `78` locked items, `10` automation content baselines, and `40` protected meta baselines. EEAT verification passed. Public homepage and sitemap returned `HTTP 200`.
- Evidence link/path: `ops/apply-wordpress-post-editorial-system.php`, `ops/verify-wordpress-post-editorial-system.php`, `docs/editorial/wordpress-post-editorial-system.md`, `/root/vietnamguide-backups/20260725-130524-post-editorial-system`
- Follow-up: open Posts in WordPress Admin, expand one draft brief into a complete article, add sources/images/Rank Math, remove brief language, then publish manually. Do not schedule one article per day until each draft passes the publish gate.

### 2026-07-25 - Vietnam First Trip Planning Checklist Complete Draft

- Date: 2026-07-25
- Area: Native WordPress Post complete draft, first-trip planning intent, anti-spam editorial value, route-shape checklist, source trail, low linkout, Admin-first workflow
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-vietnam-first-trip-planning-checklist-post-static.ps1`; full local static sweep across `37` static verifiers; staged upload to `/tmp/vg-first-trip-post-20260725131840`; server-side `php -l` for `ops/apply-vietnam-first-trip-planning-checklist-post.php` and `ops/verify-vietnam-first-trip-planning-checklist-post.php`; backup to `/root/vietnamguide-backups/20260725-131905-first-trip-post-draft`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-vietnam-first-trip-planning-checklist-post.php --allow-root`; live verifier via `wp eval-file ops/verify-vietnam-first-trip-planning-checklist-post.php --allow-root`; updated post-system verifier to recognize `complete_draft`; Admin-first verifier, EEAT verifier, post status table, homepage HTTP, and sitemap HTTP checks.
- Expected result: the first WordPress Post should move from a thin editorial brief to a complete high-quality draft without being published, while preserving Admin-first ownership and avoiding visible external link clutter.
- Actual outcome: post ID `473` remains `draft` at slug `vietnam-first-trip-planning-checklist`. The post now has a complete draft article with hero, proof panel, concierge verdict, 12-decision planning table, trip-length route matrix, weather framing, booking-order table, first-hour arrival checklist, photo proof, FAQ, source trail, update log, and related internal routes. `vg_editorial_brief_status` is `complete_draft`; `vg_content_owner=wp_admin`; `vg_automation_lock=locked`; external body href count is `0`; Rank Math and EEAT metadata are present. Post editorial verifier now reports `10` draft items, `1` complete draft, and `0` published editorial briefs. Admin-first verifier passed with `78` locked items, `10` automation content baselines, and `52` protected meta baselines. EEAT verification passed. Homepage and sitemap returned `HTTP 200`.
- Evidence link/path: `ops/apply-vietnam-first-trip-planning-checklist-post.php`, `ops/verify-vietnam-first-trip-planning-checklist-post.php`, WordPress post ID `473`, `/root/vietnamguide-backups/20260725-131905-first-trip-post-draft`
- Follow-up: open post ID `473` in WordPress Admin, preview desktop/mobile, adjust images if desired, confirm Rank Math/social preview, then publish manually only after final editorial review. The next complete draft candidate is `Vietnam Airport Arrival Checklist` because it has strong first-trip practical intent and supports money, SIM/eSIM, safety, and airport transfer pages.

### 2026-07-25 - Vietnam Food Safety and Street Food Etiquette Complete Draft

- Date: 2026-07-25
- Area: Native WordPress Post complete draft, food safety, street-food etiquette, first-time international traveler intent, low-linkout source trail, Admin-first workflow, anti-spam evergreen content
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-vietnam-food-safety-street-food-etiquette-post-static.ps1`; remote staging under `/tmp/vg-food-safety-20260725`; server-side `php -l` for `ops/apply-vietnam-food-safety-street-food-etiquette-post.php` and `ops/verify-vietnam-food-safety-street-food-etiquette-post.php`; backup of post `480` content, meta, categories, and tags under `/root/vietnamguide-backups/20260725-food-safety-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-vietnam-food-safety-street-food-etiquette-post.php --allow-root`; live verifier via `wp eval-file ops/verify-vietnam-food-safety-street-food-etiquette-post.php --allow-root`; post editorial system verifier, Admin-first verifier, EEAT verifier, homepage HTTP check, and sitemap HTTP check.
- Expected result: post `480` should move from a native WordPress draft brief to a complete draft article without publishing, while preserving the Admin-first lock, target publish date, no visible external body anchors, and manual WordPress editorial control.
- Actual outcome: post ID `480` remains `draft` at slug `vietnam-food-safety-street-food-etiquette`. The post now has the title `Vietnam Food Safety and Street Food Etiquette for First-Timers`, `vg_editorial_brief_status=complete_draft`, `vg_content_owner=wp_admin`, `vg_automation_lock=locked`, and `vg_editorial_target_publish_date=2026-08-08`. The article includes a photo-led hero, proof panel, concierge verdict, at-a-glance matrix, photo proof, source-diversity table, stall-choice framework, ordering etiquette, hygiene-risk guidance, drinks/ice/sauces/payment/seating guidance, flexible meal plan, city rhythm, red flags, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported external body href count `0`. WordPress post editorial verification passed with `10` draft items, `6` complete drafts, and `0` published editorial briefs. Admin-first verification passed, EEAT verification passed, and public homepage/sitemap HTTP checks returned `200`.
- Evidence link/path: `ops/apply-vietnam-food-safety-street-food-etiquette-post.php`, `ops/verify-vietnam-food-safety-street-food-etiquette-post.php`, WordPress post ID `480`, `/root/vietnamguide-backups/20260725-food-safety-post`
- Follow-up: open post ID `480` in WordPress Admin, preview desktop/mobile, confirm Rank Math/social preview, verify image presentation and food-health disclaimer tone, add any first-hand editorial notes if available, then publish manually only after final review. A strong next candidate is `Where to Stay in Vietnam: City Base Decisions Before Choosing Hotels`, because it can connect hotel-base intent with Hanoi, Ho Chi Minh City, Ninh Binh, Da Nang, Hoi An, transport, and itinerary pages without becoming a thin hotel list.

### 2026-07-25 - Where to Stay in Vietnam Base Decisions Complete Draft

- Date: 2026-07-25
- Area: Native WordPress Post complete draft, hotel-base decision intent, country-level accommodation planning, Hanoi/HCMC/Ninh Binh/Da Nang/Hoi An/Phu Quoc routing, low-linkout source trail, Admin-first workflow, anti-spam evergreen content
- Command/check: red/green static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-where-to-stay-in-vietnam-base-decisions-post-static.ps1`; full local static sweep across `43` `ops\verify-*-static.ps1` scripts; remote staging under `/tmp/vg-stay-vietnam-base-20260725`; server-side `php -l` for `ops/apply-where-to-stay-in-vietnam-base-decisions-post.php` and `ops/verify-where-to-stay-in-vietnam-base-decisions-post.php`; backup of post `481` content, meta, categories, and tags under `/root/vietnamguide-backups/20260725-stay-vietnam-base-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-where-to-stay-in-vietnam-base-decisions-post.php --allow-root`; live verifier via `wp eval-file ops/verify-where-to-stay-in-vietnam-base-decisions-post.php --allow-root`; post editorial system verifier, Admin-first verifier, EEAT verifier, homepage HTTP check, and sitemap HTTP check.
- Expected result: post `481` should move from a native WordPress draft brief to a complete base-decision article without publishing, while preserving the Admin-first lock, target publish date, no visible external body anchors, and manual WordPress editorial control.
- Actual outcome: post ID `481` remains `draft` at slug `where-to-stay-in-vietnam-base-decisions`. The post now has `vg_editorial_brief_status=complete_draft`, `vg_content_owner=wp_admin`, `vg_automation_lock=locked`, and `vg_editorial_target_publish_date=2026-08-09`. The article includes a photo-led hero, proof panel, concierge verdict, at-a-glance matrix, photo proof, source-diversity table, base-job framework, city-fit matrix, arrival/departure rules, family and sleep filters, food-access guidance, quiet-night filters, transfer-pickup logic, durable booking filters, skip traps, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported external body href count `0`. WordPress post editorial verification passed with `10` draft items, `7` complete drafts, and `0` published editorial briefs. Admin-first verification passed, EEAT verification passed, and public homepage/sitemap HTTP checks returned `200`.
- Evidence link/path: `ops/apply-where-to-stay-in-vietnam-base-decisions-post.php`, `ops/verify-where-to-stay-in-vietnam-base-decisions-post.php`, WordPress post ID `481`, `/root/vietnamguide-backups/20260725-stay-vietnam-base-post`
- Follow-up: open post ID `481` in WordPress Admin, preview desktop/mobile, confirm Rank Math/social preview, verify image presentation and stay-area tone, add any first-hand hotel-base notes if available, then publish manually only after final review. The strongest remaining draft candidates are `Best Vietnam Routes for First-Time Visitors`, `What to Pack for Vietnam by Region and Season`, and `Vietnam Rainy Season Travel`.

### 2026-07-25 - Best Vietnam Routes for First-Time Visitors Complete Draft

- Date: 2026-07-25
- Area: Native WordPress Post complete draft, first-time route-shape intent, north/central/south/open-jaw decision logic, trip-length ladder, movement cost, source trail, low-linkout, Admin-first workflow, anti-spam evergreen content
- Command/check: red/green static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-best-vietnam-routes-first-time-visitors-post-static.ps1`; full local static sweep across `44` `ops\verify-*-static.ps1` scripts; remote staging under `/tmp/vg-best-routes-20260725`; server-side `php -l` for `ops/apply-best-vietnam-routes-first-time-visitors-post.php` and `ops/verify-best-vietnam-routes-first-time-visitors-post.php`; backup of post `474` content, meta, categories, and tags under `/root/vietnamguide-backups/20260725-best-routes-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-best-vietnam-routes-first-time-visitors-post.php --allow-root`; live verifier via `wp eval-file ops/verify-best-vietnam-routes-first-time-visitors-post.php --allow-root`; post editorial system verifier, Admin-first verifier, EEAT verifier, homepage HTTP check, and sitemap HTTP check.
- Expected result: post `474` should move from a native WordPress draft brief to a complete route-shape article without publishing, while preserving the Admin-first lock, target publish date, no visible external body anchors, and manual WordPress editorial control.
- Actual outcome: post ID `474` remains `draft` at slug `best-vietnam-routes-first-time-visitors`. The post now has `vg_editorial_brief_status=complete_draft`, `vg_content_owner=wp_admin`, `vg_automation_lock=locked`, and `vg_editorial_target_publish_date=2026-08-02`. The article includes a photo-led hero, proof panel, concierge verdict, at-a-glance matrix, photo proof, source-diversity table, route archetypes, trip-length ladder, open-jaw logic, movement-cost guidance, regional spine table, season filter, traveler-fit table, red flags, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported external body href count `0`. WordPress post editorial verification passed with `10` draft items, `8` complete drafts, and `0` published editorial briefs. Admin-first verification passed, EEAT verification passed, and public homepage/sitemap HTTP checks returned `200`.
- Evidence link/path: `ops/apply-best-vietnam-routes-first-time-visitors-post.php`, `ops/verify-best-vietnam-routes-first-time-visitors-post.php`, WordPress post ID `474`, `/root/vietnamguide-backups/20260725-best-routes-post`
- Follow-up: open post ID `474` in WordPress Admin, preview desktop/mobile, confirm Rank Math/social preview, verify route-shape tone and image presentation, add any first-hand route or flight notes if available, then publish manually only after final review. The strongest remaining draft candidates are `What to Pack for Vietnam by Region and Season` and `Vietnam Rainy Season Travel`.

### 2026-07-26 - Tet in Vietnam Complete Draft Cleanup and QA

- Date: 2026-07-26
- Area: Native WordPress Post complete draft cleanup, Tet travel decision content, Admin-first replay of cleaned body, source trail, zero visible body anchors, batch-2 editorial brief maintenance
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-tet-in-vietnam-travel-guide-post-static.ps1`; staged upload to `/tmp/vg-tet/ops`; server-side `php -l` for staged and live Tet apply/verify scripts; backup to `/root/vietnamguide-backups/20260726-tet-in-vietnam-travel-guide-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file /tmp/vg-tet/ops/apply-tet-in-vietnam-travel-guide-post.php --allow-root`; live verifier via `wp eval-file ops/verify-tet-in-vietnam-travel-guide-post.php --allow-root`; batch-2 verifier; Admin-first verifier; EEAT verifier; homepage, sitemap, and page-sitemap HTTP checks.
- Expected result: post `496` should remain draft but stay a complete draft after removing visible January/February body links, while preserving Admin-first ownership and no visible external body anchors.
- Actual outcome: post ID `496` remains `draft` at slug `tet-in-vietnam-travel-guide` with title `Tet in Vietnam Travel Guide: What International Visitors Should Know`. The cleaned article keeps the photo-led hero, proof panel, concierge verdict, quick-decision table, source-diversity table, timing phases, booking guidance, operating rhythm, route chooser, city choice, respectful behavior, money/food/medicine prep, fragile-plan traps, live checks, FAQ, source trail, update log, and related routes, while visible external body anchors remain `0`. `vg_editorial_brief_status=complete_draft`; `vg_content_owner=wp_admin`; `vg_automation_lock=locked`; batch-2 verifier now reports `12` draft items and `4` complete drafts; Admin-first verification passed with `90` locked items and `256` protected meta baselines; EEAT verification passed; homepage, sitemap, and page sitemap returned `HTTP 200`.
- Evidence link/path: `ops/apply-tet-in-vietnam-travel-guide-post.php`, `ops/verify-tet-in-vietnam-travel-guide-post.php`, WordPress post ID `496`, `/root/vietnamguide-backups/20260726-tet-in-vietnam-travel-guide-post`

### 2026-07-26 - Best Vietnam Cities Complete Draft

- Date: 2026-07-26
- Area: Native WordPress Post complete draft, first-time city-base intent, route-job decision logic, source trail, low linkout, Admin-first workflow, anti-spam evergreen content
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-best-vietnam-cities-first-time-visitors-post-static.ps1`; public HTTP check for every required internal route; staged upload to `/tmp/vg-best-cities/ops`; server-side `php -l` for staged and live apply/verify scripts; backup of post `497` content, meta, categories, and tags under `/root/vietnamguide-backups/20260726-best-vietnam-cities-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-best-vietnam-cities-first-time-visitors-post.php --allow-root`; live verifier via `wp eval-file ops/verify-best-vietnam-cities-first-time-visitors-post.php --allow-root`; batch-2 verifier; Admin-first verifier; EEAT verifier; cache/rewrite/transient/LiteSpeed purge; homepage, sitemap, and page-sitemap HTTP checks.
- Expected result: post `497` should move from a native WordPress Batch 2 draft brief to a complete route-job city-base draft without publishing, while preserving Admin-first ownership, no visible external body anchors, and no links to unpublished draft posts.
- Actual outcome: post ID `497` remains `draft` at slug `best-vietnam-cities-for-first-time-visitors` with title `Best Vietnam Cities for First-Time Visitors: Which Base Fits Your Route?`. The article includes a photo-led hero, proof panel, concierge verdict, at-a-glance city-role matrix, photo proof, source-diversity table, city-by-city base logic, arrival/departure rules, traveler-fit filters, day-trip pressure, skip logic, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported `external_body_href_count: 0`. `vg_editorial_brief_status=complete_draft`; `vg_content_owner=wp_admin`; `vg_automation_lock=locked`; batch-2 verifier now reports `12` draft items and `5` complete drafts; Admin-first verification passed with `90` locked items and `268` protected meta baselines; EEAT verification passed; homepage, sitemap, and page sitemap returned `HTTP 200`.
- Evidence link/path: `ops/apply-best-vietnam-cities-first-time-visitors-post.php`, `ops/verify-best-vietnam-cities-first-time-visitors-post.php`, WordPress post ID `497`, `/root/vietnamguide-backups/20260726-best-vietnam-cities-post`
- Follow-up: open post ID `497` in WordPress Admin, preview desktop/mobile, verify image presentation and Rank Math/social preview, then publish manually only after final editorial review. Strong next candidates are `Hanoi vs Ho Chi Minh City`, `Hoi An Ancient Town Guide`, and `Hue Imperial City Guide` because they deepen the city-base cluster without creating a thin popularity list.

### 2026-07-26 - Hanoi vs Ho Chi Minh City Complete Draft

- Date: 2026-07-26
- Area: Native WordPress Post complete draft, first-city comparison intent, route-direction decision logic, GSC-informed phrasing, source trail, low linkout, Admin-first workflow, anti-spam evergreen content
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-hanoi-vs-ho-chi-minh-city-post-static.ps1`; image HEAD checks for all four body images; staged upload to `/tmp/vg-hanoi-hcmc/ops`; server-side `php -l` for staged and live apply/verify scripts; backup of post `498` content, meta, categories, and tags under `/root/vietnamguide-backups/20260726-hanoi-vs-hcmc-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-hanoi-vs-ho-chi-minh-city-post.php --allow-root`; live verifier via `wp eval-file ops/verify-hanoi-vs-ho-chi-minh-city-post.php --allow-root`; batch-2 verifier; Admin-first verifier; EEAT verifier; cache flush, rewrite flush, transient deletion, LiteSpeed purge; homepage, sitemap, and page-sitemap HTTP checks.
- Expected result: post `498` should move from a native WordPress Batch 2 draft brief to a complete route-direction comparison draft without publishing, while preserving Admin-first ownership, no visible external body anchors, and no links to unpublished draft posts.
- Actual outcome: post ID `498` remains `draft` at slug `hanoi-vs-ho-chi-minh-city` with title `Hanoi vs Ho Chi Minh City: Which Should You Visit First?`. The article includes a photo-led hero, proof panel, concierge verdict, GSC demand note, quick-answer matrix, photo proof, source-diversity table, arrival logic, route-direction guidance, weather and comfort trade-offs, day-trip consequences, food and culture framing, trip-length matrix, skip logic, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported `external_body_href_count: 0`. `vg_editorial_brief_status=complete_draft`; `vg_content_owner=wp_admin`; `vg_automation_lock=locked`; batch-2 verifier now reports `12` draft items and `6` complete drafts; Admin-first verification passed with `90` locked items and `280` protected meta baselines; EEAT verification passed; homepage, sitemap, and page-sitemap returned `HTTP 200`.
- Evidence link/path: `ops/apply-hanoi-vs-ho-chi-minh-city-post.php`, `ops/verify-hanoi-vs-ho-chi-minh-city-post.php`, WordPress post ID `498`, `/root/vietnamguide-backups/20260726-hanoi-vs-hcmc-post`
- Follow-up: open post ID `498` in WordPress Admin, preview desktop/mobile, verify image presentation and Rank Math/social preview, then publish manually only after final editorial review. The next strongest comparison and destination candidates are `Hoi An Ancient Town Guide` and `Hue Imperial City Guide`.

### 2026-07-26 - Hoi An Ancient Town Complete Draft

- Date: 2026-07-26
- Area: Native WordPress Post complete draft, Hoi An Ancient Town heritage intent, stay-visit-skip decision logic, Da Nang vs Hoi An comparison support, GSC-informed phrasing, source trail, low linkout, Admin-first workflow, anti-spam evergreen content
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-hoi-an-ancient-town-guide-post-static.ps1`; image HEAD checks for all four body images; staged upload to `/tmp/vg-hoi-an-ancient-town/ops`; server-side `php -l` for staged and live apply/verify scripts; backup of post `499` content, meta, categories, and tags under `/root/vietnamguide-backups/20260726-hoi-an-ancient-town-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-hoi-an-ancient-town-guide-post.php --allow-root`; live verifier via `wp eval-file ops/verify-hoi-an-ancient-town-guide-post.php --allow-root`; batch-2 verifier; Admin-first verifier; EEAT verifier; cache flush, rewrite flush, transient deletion, LiteSpeed purge; homepage, sitemap, and page-sitemap HTTP checks.
- Expected result: post `499` should move from a native WordPress Batch 2 draft brief to a complete Hoi An Ancient Town decision draft without publishing, while preserving Admin-first ownership, no visible external body anchors, and no links to unpublished draft posts.
- Actual outcome: post ID `499` remains `draft` at slug `hoi-an-ancient-town-guide` with title `Hoi An Ancient Town Guide: When to Stay, Visit or Skip`. The article includes a photo-led hero, proof panel, concierge verdict, stay-visit-skip matrix, GSC demand note, photo proof, source-diversity table, day-vs-evening pacing, overnight-vs-day-trip logic, Da Nang-vs-Hoi An base guidance, ticket and heritage live checks, heat/rain/crowd rhythm, central-route fit, food/tailoring/beach guidance, trip-length matrix, skip logic, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported `external_body_href_count: 0`. `vg_editorial_brief_status=complete_draft`; `vg_content_owner=wp_admin`; `vg_automation_lock=locked`; batch-2 verifier now reports `12` draft items and `7` complete drafts; Admin-first verification passed with `90` locked items and `292` protected meta baselines; EEAT verification passed; homepage, sitemap, and page-sitemap returned `HTTP 200`.
- Evidence link/path: `ops/apply-hoi-an-ancient-town-guide-post.php`, `ops/verify-hoi-an-ancient-town-guide-post.php`, WordPress post ID `499`, `/root/vietnamguide-backups/20260726-hoi-an-ancient-town-post`
- Follow-up: open post ID `499` in WordPress Admin, preview desktop/mobile, verify image presentation and Rank Math/social preview, then publish manually only after final editorial review. The next strongest Batch 2 candidate is `Hue Imperial City Guide` because it completes the central heritage decision pair after Hoi An.

### 2026-07-26 - Hue Imperial City Complete Draft

- Date: 2026-07-26
- Area: Native WordPress Post complete draft, Hue Imperial City heritage intent, stay-half-day-pass-through decision logic, GSC-informed phrasing, official source trail, low linkout, Admin-first workflow, anti-spam evergreen content
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-hue-imperial-city-guide-post-static.ps1`; local `git diff --check`; local ASCII check; staged upload to `/tmp/vg-hue-imperial-city/ops`; server-side `php -l` for staged and live apply/verify scripts; backup of post `500` content, meta, categories, and tags under `/root/vietnamguide-backups/20260726-hue-imperial-city-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-hue-imperial-city-guide-post.php --allow-root`; live verifier via `wp eval-file ops/verify-hue-imperial-city-guide-post.php --allow-root`; batch-2 verifier; Admin-first verifier; EEAT verifier; transient/cache/LiteSpeed purge; homepage, sitemap, and page-sitemap HTTP checks.
- Expected result: post `500` should move from a native WordPress Batch 2 draft brief to a complete Hue Imperial City decision draft without publishing, while preserving Admin-first ownership, no visible external body anchors, and no links to unpublished draft posts.
- Actual outcome: post ID `500` remains `draft` at slug `hue-imperial-city-guide` with title `Hue Imperial City Guide: How to Visit Without Rushing`. The article includes a photo-led hero, proof panel, concierge verdict, stay-half-day-pass-through matrix, GSC demand note, photo proof, source-diversity table, time-budget matrix, Imperial City pacing, tomb-choice logic, guide-value guidance, heat/rain rhythm, Da Nang/Hoi An route order, ticket/opening/access live checks, food and river pacing, trip-length matrix, skip logic, FAQ, related routes, source trail, and update log. Runtime verification reported `external_body_href_count: 0`. `vg_editorial_brief_status=complete_draft`; `vg_content_owner=wp_admin`; `vg_automation_lock=locked`; batch-2 verifier now reports `12` draft items and `8` complete drafts; Admin-first verification passed with `90` locked items and `304` protected meta baselines; EEAT verification passed; homepage, sitemap, and page-sitemap returned `HTTP 200`.
- Evidence link/path: `ops/apply-hue-imperial-city-guide-post.php`, `ops/verify-hue-imperial-city-guide-post.php`, WordPress post ID `500`, `/root/vietnamguide-backups/20260726-hue-imperial-city-post`
- Follow-up: open post ID `500` in WordPress Admin, preview desktop/mobile, verify image presentation and Rank Math/social preview, add any first-hand Hue notes if available, then publish manually only after final editorial review. Strong next candidates are `Da Nang Beaches Guide` and `Mekong Delta Overnight vs Day Trip` because they continue the central-coast and high-intent planning clusters.

### 2026-07-26 - Da Nang Beaches Complete Draft

- Date: 2026-07-26
- Area: Native WordPress Post complete draft, Da Nang beach-base decision intent, My Khe / Non Nuoc / Son Tra edge / Hoi An coast comparison, GSC-informed phrasing, official source trail, low linkout, Admin-first workflow, anti-spam evergreen content
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-da-nang-beaches-guide-post-static.ps1`; local `git diff --check`; local ASCII check; image HEAD checks for all four body images; staged upload to `/tmp/vg-da-nang-beaches/ops`; server-side `php -l` for staged and live apply/verify scripts; backup of post `501` content, meta, categories, and tags under `/root/vietnamguide-backups/20260726-da-nang-beaches-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-da-nang-beaches-guide-post.php --allow-root`; live verifier via `wp eval-file ops/verify-da-nang-beaches-guide-post.php --allow-root`; batch-2 verifier; Admin-first verifier; EEAT verifier; transient/cache/LiteSpeed purge; homepage, sitemap, and page-sitemap HTTP checks.
- Expected result: post `501` should move from a native WordPress Batch 2 draft brief to a complete Da Nang beaches decision draft without publishing, while preserving Admin-first ownership, no visible external body anchors, and no links to unpublished draft posts.
- Actual outcome: post ID `501` remains `draft` at slug `da-nang-beaches-guide` with title `Da Nang Beaches Guide: My Khe, Non Nuoc or Hoi An Coast?`. The article includes a photo-led hero, proof panel, concierge verdict, GSC demand note, quick chooser, photo proof, source-diversity table, north-to-south orientation map, My Khe/Non Nuoc/Hoi An coast comparison, base-map guidance, season and sea-condition logic, family and swimmer guidance, hotel-base decision matrix, beach-plus-culture route order, airport and transport reality checks, time-budget matrix, skip logic, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported `external_body_href_count: 0`. `vg_editorial_brief_status=complete_draft`; `vg_content_owner=wp_admin`; `vg_automation_lock=locked`; batch-2 verifier now reports `12` draft items and `9` complete drafts; Admin-first verification passed with `90` locked items and `316` protected meta baselines; EEAT verification passed; homepage, sitemap, and page-sitemap returned `HTTP 200`.
- Evidence link/path: `ops/apply-da-nang-beaches-guide-post.php`, `ops/verify-da-nang-beaches-guide-post.php`, WordPress post ID `501`, `/root/vietnamguide-backups/20260726-da-nang-beaches-post`
- Follow-up: open post ID `501` in WordPress Admin, preview desktop/mobile, verify image presentation and Rank Math/social preview, add any first-hand Da Nang beach or hotel-base notes if available, then publish manually only after final editorial review. Strong next candidate: `Mekong Delta Overnight vs Day Trip`, because it captures high-intent comparison traffic and complements the south/itinerary decision modules.

### 2026-07-26 - Mekong Delta Overnight vs Day Trip Complete Draft

- Date: 2026-07-26
- Area: Native WordPress Post complete draft, Mekong Delta day-trip vs overnight comparison intent, Can Tho / Cai Rang timing, Ben Tre / Cai Be / My Tho taste logic, Chau Doc deeper-route logic, GSC-informed planning language, source trail, low linkout, Admin-first workflow, anti-spam evergreen content
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-mekong-delta-overnight-vs-day-trip-post-static.ps1`; local `git diff --check`; local ASCII check; image HEAD checks for all four body images; spec-compliance subagent review; code-quality subagent review; verifier hardening for exact title/slug, published internal-link resolution, and raw/rendered external-link counts; staged upload to `/tmp/vg-mekong-overnight/ops`; server-side `php -l` for staged and live apply/verify scripts; backup of post `502` content, meta, categories, and tags under `/root/vietnamguide-backups/20260726-mekong-overnight-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-mekong-delta-overnight-vs-day-trip-post.php --allow-root`; live verifier via `wp eval-file ops/verify-mekong-delta-overnight-vs-day-trip-post.php --allow-root`; batch-2 verifier; Admin-first verifier; EEAT verifier; WordPress cache flush, rewrite flush, transient deletion, LiteSpeed purge; homepage, sitemap, and page-sitemap HTTP checks.
- Expected result: post `502` should move from a native WordPress Batch 2 draft brief to a complete Mekong Delta route-decision draft without publishing, while preserving Admin-first ownership, no visible external body anchors, no links to unpublished draft posts, and practical decision value beyond rewritten search results.
- Actual outcome: post ID `502` remains `draft` at slug `mekong-delta-overnight-vs-day-trip` with title `Mekong Delta Overnight vs Day Trip: Which Is Worth It?`. The article includes a photo-led hero, proof panel, concierge verdict, GSC demand note, day-trip/overnight/deeper-route/skip matrix, photo proof, source-diversity table, day-trip fit logic, overnight upgrade logic, Can Tho and Cai Rang timing, Ben Tre / Cai Be / My Tho taste logic, family/comfort/private-transfer filters, transport and flight-day guardrails, route-length matrix, upgrade/skip logic, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported `raw_external_body_href_count: 0`, `rendered_external_body_href_count: 0`, and `external_body_href_count: 0`. `vg_editorial_brief_status=complete_draft`; `vg_content_owner=wp_admin`; `vg_automation_lock=locked`; batch-2 verifier now reports `12` draft items and `10` complete drafts; Admin-first verification passed with `90` locked items and `328` protected meta baselines; EEAT verification passed; homepage, sitemap, and page-sitemap returned `HTTP 200`.
- Evidence link/path: `ops/apply-mekong-delta-overnight-vs-day-trip-post.php`, `ops/verify-mekong-delta-overnight-vs-day-trip-post.php`, WordPress post ID `502`, `/root/vietnamguide-backups/20260726-mekong-overnight-post`
- Follow-up: open post ID `502` in WordPress Admin, preview desktop/mobile, verify image presentation and Rank Math/social preview, add any first-hand Mekong transfer or guide notes if available, then publish manually only after final editorial review. Strong next candidates are `Phong Nha Travel Guide`, `Sapa vs Ha Giang`, or a southern route support piece if Search Console starts showing Mekong/Can Tho modifiers.

### 2026-07-26 - Phong Nha Travel Guide Complete Draft

- Date: 2026-07-26
- Area: Native WordPress Post complete draft, Phong Nha cave and route-fit intent, UNESCO 2025 transboundary context, central Vietnam nature extension, cave chooser, season/rain guardrails, safety and insurance filter, low linkout, Admin-first workflow, anti-spam evergreen content
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-phong-nha-travel-guide-post-static.ps1`; local `git diff --check`; local ASCII check; internal related-route HTTP checks; image/source reachability checks from the VPS; spec-compliance subagent review; verifier hardening for published internal links plus frontend `HTTP 200`; staged upload to `/tmp/vg-phong-nha/ops`; server-side `php -l` for staged and live apply/verify scripts; backup of post `503` content, meta, categories, and tags under `/root/vietnamguide-backups/20260726-phong-nha-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-phong-nha-travel-guide-post.php --allow-root`; live verifier via `wp eval-file ops/verify-phong-nha-travel-guide-post.php --allow-root`; batch-2 verifier; Admin-first verifier; EEAT verifier; WordPress cache flush, rewrite flush, transient deletion, LiteSpeed purge; homepage, sitemap, and page-sitemap HTTP checks.
- Expected result: post `503` should move from a native WordPress Batch 2 draft brief to a complete Phong Nha route-fit draft without publishing, while preserving Admin-first ownership, no visible external body anchors, no links to unpublished draft/404 paths, and practical decision value beyond a generic cave list.
- Actual outcome: post ID `503` remains `draft` at slug `phong-nha-travel-guide` with title `Phong Nha Travel Guide: Caves, Seasons and Route Fit`. The article includes a photo-led hero, proof panel, concierge verdict, internal demand note, photo proof, source-diversity table, add/shorten/skip matrix, night-count planner, cave-style chooser, season/rain caveats, safety/fitness/insurance filter, route-fit table, booking checks, itinerary-length matrix, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported `raw_external_body_href_count: 0`, `rendered_external_body_href_count: 0`, and `external_body_href_count: 0`, and now checks rendered internal body links for WP `publish` status plus frontend `HTTP 200`. `vg_editorial_brief_status=complete_draft`; `vg_content_owner=wp_admin`; `vg_automation_lock=locked`; batch-2 verifier now reports `12` draft items and `11` complete drafts; Admin-first verification passed with `90` locked items and `340` protected meta baselines; EEAT verification passed; homepage, sitemap, and page-sitemap returned `HTTP 200`.
- Evidence link/path: `ops/apply-phong-nha-travel-guide-post.php`, `ops/verify-phong-nha-travel-guide-post.php`, WordPress post ID `503`, `/root/vietnamguide-backups/20260726-phong-nha-post`
- Follow-up: open post ID `503` in WordPress Admin, preview desktop/mobile, verify image presentation and Rank Math/social preview, refresh cave access/operator/insurance/weather details before publishing, and add any first-hand Phong Nha transfer or cave notes if available. The last remaining Batch 2 draft brief is `Sapa vs Ha Giang`, which should close the first Batch 2 expansion run.

### 2026-07-26 - Sapa vs Ha Giang Complete Draft

- Date: 2026-07-26
- Area: Native WordPress Post complete draft, northern mountain comparison intent, Sapa terraces versus Ha Giang loop-road scenery, route pressure, weather, safety, license and insurance filters, source trail, low linkout, Admin-first workflow, anti-spam evergreen content
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-sapa-vs-ha-giang-post-static.ps1`; local `git diff --check`; local ASCII check; spec-compliance subagent review; staged upload to `/tmp/vg-sapa-ha-giang/ops`; server-side `php -l` for staged and live apply/verify scripts; backup of post `504` content, meta, categories, and tags under `/root/vietnamguide-backups/20260726-sapa-vs-ha-giang-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-sapa-vs-ha-giang-post.php --allow-root`; live verifier via `wp eval-file ops/verify-sapa-vs-ha-giang-post.php --allow-root`; batch-2 verifier; Admin-first verifier; EEAT verifier; WordPress cache flush, rewrite flush, transient deletion, LiteSpeed purge; homepage, sitemap, and page-sitemap HTTP checks.
- Expected result: post `504` should move from a native WordPress Batch 2 draft brief to a complete Sapa vs Ha Giang decision draft without publishing, while preserving Admin-first ownership, no visible external body anchors, no links to unpublished draft/404 paths, and practical decision value beyond a generic northern mountain comparison.
- Actual outcome: post ID `504` remains `draft` at slug `sapa-vs-ha-giang` with title `Sapa vs Ha Giang: Terraces, Loop Roads or Softer Mountain Travel?`. The article includes a photo-led hero, proof panel, concierge verdict, internal demand note, photo proof, source-diversity table, Sapa/Ha Giang/both/neither decision matrix, Sapa comfort and terrace fit, Ha Giang road-scenery fit, safety/license/insurance filter, season/fog/terrace timing, trip-length planner, comfort/mobility filters, transport route-pressure checks, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported `raw_external_body_href_count: 0`, `rendered_external_body_href_count: 0`, and `external_body_href_count: 0`, and checks rendered internal body links for WP `publish` status plus frontend `HTTP 200`. `vg_editorial_brief_status=complete_draft`; `vg_content_owner=wp_admin`; `vg_automation_lock=locked`; batch-2 verifier now reports `12` draft items and `12` complete drafts; Admin-first verification passed with `90` locked items and `352` protected meta baselines; EEAT verification passed; homepage, sitemap, and page-sitemap returned `HTTP 200`.
- Evidence link/path: `ops/apply-sapa-vs-ha-giang-post.php`, `ops/verify-sapa-vs-ha-giang-post.php`, WordPress post ID `504`, `/root/vietnamguide-backups/20260726-sapa-vs-ha-giang-post`
- Follow-up: open post ID `504` in WordPress Admin, preview desktop/mobile, verify image presentation and Rank Math/social preview, refresh current mountain weather, road, operator, license, and insurance notes before publishing, and add any first-hand Sapa/Ha Giang route notes if available. Batch 2 expansion is now complete as draft-only content; the next long-term cluster can move into deeper northern route support, central-coast base planning, or monthly/seasonal evergreen pages.

### 2026-07-26 - Batch 3 Northern Mountains Draft Briefs

- Date: 2026-07-26
- Area: Native WordPress Posts Batch 3, northern mountains and scenery editorial calendar, Sapa/Ha Giang/Pu Luong/rice terrace cluster, Admin-first draft-only workflow
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-wordpress-post-editorial-system-batch-3-static.ps1`; local `git diff --check`; local ASCII check; staged upload to `/tmp/vg-batch-3`; server-side `php -l` for `ops/apply-wordpress-post-editorial-system-batch-3.php` and `ops/verify-wordpress-post-editorial-system-batch-3.php`; backup of prior runbook to `/root/vietnamguide-backups/20260726-batch-3-post-briefs`; live apply via `wp eval-file ops/apply-wordpress-post-editorial-system-batch-3.php --allow-root`; live verifier via `wp eval-file ops/verify-wordpress-post-editorial-system-batch-3.php --allow-root`; Admin-first verifier; EEAT verifier.
- Expected result: create exactly `12` native WordPress draft briefs for Batch 3 without publishing or scheduling any post, while preserving WordPress Admin ownership, automation lock, source plan, image plan, what-not-to-write, and anti-spam evergreen tags.
- Actual outcome: Batch 3 created `12` draft briefs and preserved `0` existing items on first run. Live verifier was hardened after review to prove the total Batch 3 slug set exactly matches the expected `12` posts. After hardening, it reported `12` draft briefs, `0` complete drafts, `0` published/future items, and `12` total batch posts. Admin-first verification passed with `102` locked items and `400` protected meta baselines; EEAT verification passed.
- Evidence link/path: `ops/apply-wordpress-post-editorial-system-batch-3.php`, `ops/verify-wordpress-post-editorial-system-batch-3.php`, `ops/verify-wordpress-post-editorial-system-batch-3-static.ps1`, `docs/editorial/wordpress-post-editorial-system.md`, `/root/vietnamguide-backups/20260726-batch-3-post-briefs`

### 2026-07-26 - Sapa Travel Guide Complete Draft

- Date: 2026-07-26
- Area: Native WordPress Post complete draft, Sapa route-fit intent, terraces, trekking, town versus valley stays, weather/fog/terrace timing, Hanoi transfer friction, responsible local context, source trail, low linkout, Admin-first workflow, anti-spam evergreen content
- Command/check: local static verifier via `powershell -ExecutionPolicy Bypass -File ops\verify-sapa-travel-guide-post-static.ps1`; local `git diff --check`; local ASCII check; server-side `php -l` for staged and live apply/verify scripts; backup of post `518` content, meta, categories, and tags under `/root/vietnamguide-backups/20260726-sapa-travel-guide-post`; controlled one-post update via `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-sapa-travel-guide-post.php --allow-root`; live verifier via `wp eval-file ops/verify-sapa-travel-guide-post.php --allow-root`; batch-3 verifier; Admin-first verifier; EEAT verifier; WordPress cache flush, rewrite flush, transient deletion, LiteSpeed purge; homepage, sitemap, and page-sitemap HTTP checks.
- Expected result: post `518` should move from a native WordPress Batch 3 draft brief to a complete Sapa decision draft without publishing, while preserving Admin-first ownership, no visible external body anchors, no links to unpublished draft/404 paths, and practical decision value beyond a generic Sapa things-to-do list.
- Actual outcome: post ID `518` remains `draft` at slug `sapa-travel-guide` with title `Sapa Travel Guide: Terraces, Trekking and Softer Mountain Travel`. The article includes a photo-led hero, proof panel, concierge verdict, internal demand note, photo proof, source-diversity table, add/shorten/upgrade/skip matrix, route-length planner, town versus valley base logic, guided versus self-guided trekking filter, season/fog/rice-terrace caveats, Hanoi transport friction table, Fansipan optionality, comfort/culture/responsible travel guidance, live checks, FAQ, related routes, source trail, and update log. Runtime verification reported `raw_external_body_href_count: 0`, `rendered_external_body_href_count: 0`, and `external_body_href_count: 0`, and checks rendered internal body links for WP `publish` status plus frontend `HTTP 200`. `vg_editorial_brief_status=complete_draft`; `vg_content_owner=wp_admin`; `vg_automation_lock=locked`; batch-3 verifier now reports `12` draft items, `1` complete draft, `0` published/future items, and `12` total batch posts; Admin-first verification passed with `102` locked items and `412` protected meta baselines; EEAT verification passed; homepage, sitemap, and page-sitemap returned `HTTP 200`.
- Evidence link/path: `ops/apply-sapa-travel-guide-post.php`, `ops/verify-sapa-travel-guide-post.php`, WordPress post ID `518`, `/root/vietnamguide-backups/20260726-sapa-travel-guide-post`
- Follow-up: open post ID `518` in WordPress Admin, preview desktop/mobile, verify image presentation and Rank Math/social preview, refresh Sapa weather, terrace timing, guide/transport details, and image-license notes before publishing, and add any first-hand Sapa stay/trekking notes if available. Strong next Batch 3 candidate: `Ha Giang Loop Planning Guide`, because it pairs naturally with Sapa and supports the existing Sapa vs Ha Giang comparison.

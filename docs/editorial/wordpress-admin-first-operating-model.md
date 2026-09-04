# VietnamGuide WordPress Admin-First Operating Model

**Principle:** WordPress Admin is the source of truth for published editorial content. Automation may create drafts, bootstrap patterns, refresh navigation, and run verification-only checks, but it should not overwrite manually optimized content unless an explicit backup-and-override run is approved.

## Why This Model

VietnamGuide is moving from script-first publishing to a premium editorial workflow. The site still benefits from automation, but the lasting SEO advantage comes from human judgment, manual Rank Math tuning, original route decisions, source review, image choices, and update discipline.

The goal is not to remove scripts. The goal is to demote them from "content owner" to "quality infrastructure."

## Pages vs Posts

Use **Pages** for evergreen pillar and decision assets:

- Country-level guides: Vietnam Travel Guide, Best Time to Visit Vietnam, Cost, eVisa, Safety, Insurance.
- Route assets: 7 Days, 10 Days, 14 Days, 21 Days, northern route handoffs.
- Major destinations: Hanoi, Ninh Binh, Ha Long Bay, Da Nang, Hoi An, Hue, Ho Chi Minh City, Phu Quoc.
- Strong comparisons: North vs Central vs South, Ha Long Bay vs Lan Ha Bay, Da Nang vs Hoi An, Trang An vs Tam Coc.

Use **Posts** for calendar growth that should still feel editorial, not spam:

- Top lists with clear decision value.
- Smaller destinations, heritage sites, markets, cafes, neighborhoods, food topics, and local planning notes.
- Seasonal refreshes where the main pillar page should remain stable.
- Search Console response posts when a query deserves a separate answer.

## Ownership States

Each Page or Post can carry four workflow fields in WordPress Admin:

- `vg_content_owner`: `automation` or `wp_admin`.
- `vg_automation_lock`: `open` or `locked`.
- `vg_last_manual_review`: human-readable review date.
- `vg_admin_first_notes`: what future updates must preserve.

When a page becomes manually optimized, set:

- `Content owner`: WordPress Admin.
- `Automation lock`: Locked for WordPress Admin editing.
- `Last manual editorial review`: current review date.
- `Admin-first workflow notes`: what was customized.

## Guard Behavior

The MU plugin blocks WP-CLI automation from changing protected fields on Admin-first content:

- `post_title`
- `post_name`
- `post_content`
- `post_excerpt`
- `post_status`
- `post_parent`
- `menu_order`
- Rank Math metadata
- EEAT metadata
- featured image metadata
- GeneratePress headline suppression metadata

Manual editing through WordPress Admin remains allowed.

The guard also stores automation baselines:

- `_vg_last_automation_content_hash`
- `_vg_last_automation_meta_hash_*`

If WP-CLI automation tries to change protected content or protected metadata after the current value has drifted from the last automation baseline, the write is blocked. This protects pages that were manually edited but not yet marked `wp_admin` or `locked`.

Use `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1` only when all of these are true:

- A backup exists.
- The exact page/post is known.
- The manual editor accepts that script content may replace Admin edits.
- The run is documented in `ops/verification-log.md`.

Legacy alias: `VG_FORCE_WP_ADMIN_OVERWRITE=1` is also supported, but the preferred variable is `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1`.

## Safe Migration Flow

1. Open the page/post in WordPress Admin.
2. Manually tune the H1, intro, Rank Math title, Rank Math description, image choices, proof/source/update sections, and related routes.
3. Set Admin-first workflow fields to `wp_admin` and `locked`.
4. Run the verification-only checks.
5. Publish or update the content from WordPress Admin.

Optional WP-CLI marking flow:

```bash
VG_ADMIN_FIRST_INCLUDE_PATHS=plan/vietnam-travel-guide wp eval-file ops/mark-wordpress-admin-owned-content.php --allow-root
VG_ADMIN_FIRST_INCLUDE_PATHS=plan/vietnam-travel-guide VG_ADMIN_FIRST_MARK_APPLY=1 wp eval-file ops/mark-wordpress-admin-owned-content.php --allow-root
wp eval-file ops/verify-admin-first-workflow.php --allow-root
```

The first command is a dry-run. The second command applies the owner and lock metadata.

## Verification-Only Workflow

Run these checks after editorial updates:

```bash
wp eval-file ops/verify-admin-first-workflow.php --allow-root
wp eval-file ops/verify-eeat-content.php --allow-root
```

For local repository checks:

```powershell
powershell -ExecutionPolicy Bypass -File ops\verify-admin-first-workflow-static.ps1
```

Verification scripts should report problems. They should not rewrite content.

## Publishing Checklist

Before publishing a new evergreen Page or high-value Post:

- The article answers a real traveler decision, not just a keyword.
- The first screen contains a clear verdict or practical route answer.
- The page includes proof, source trail, update log, related routes, FAQ, and relevant images.
- External links are limited and purposeful; visible linkout clutter is avoided.
- Image credits are present in text.
- Rank Math title and description are written for international travelers.
- The page has internal links from its hub and from at least two relevant related guides.
- The content has a named update date and a reason for the update.
- The page can survive future Google quality reviews because it contains original judgment, not rewritten search results.

## Daily Publishing Rule

One post per day is acceptable only if quality does not collapse. The minimum bar is:

- One clear search intent.
- One original decision framework, route logic, field note, map logic, or comparison table.
- At least three source families checked when the topic is factual or volatile.
- No duplicate article angle already covered by a pillar page.
- A future refresh owner and review cadence.

If the article cannot meet that bar, update an existing page instead of publishing a thin new one.

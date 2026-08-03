# WordPress Admin-First Workflow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make WordPress Admin the operational source of truth for published VietnamGuide content while keeping automation as a guarded verification and bootstrap layer.

**Architecture:** Add ownership metadata and ACF fields in the site MU plugin, then use WordPress hooks to block WP-CLI automation from overwriting Admin-owned content. Add a safe marker script and verification-only checks so future editorial work can be managed in WordPress without losing the benefits of scripted QA.

**Tech Stack:** WordPress, PHP 8.2, WP-CLI, ACF local field groups, PowerShell static verification, Rank Math metadata, VietnamGuide MU plugin.

---

### Task 1: Static Verification Contract

**Files:**
- Create: `ops/verify-admin-first-workflow-static.ps1`

- [x] **Step 1: Write the failing static verifier**

The verifier must require the Admin-first MU plugin hooks, ACF fields, marker script, runtime verifier, and runbook.

```powershell
powershell -ExecutionPolicy Bypass -File ops\verify-admin-first-workflow-static.ps1
```

Expected before implementation: FAIL because the marker script and runtime verifier do not exist.

- [x] **Step 2: Confirm the red state**

Observed red state:

```text
Admin-first runtime verifier missing: ops/verify-admin-first-workflow.php
```

### Task 2: Runtime Guard

**Files:**
- Modify: `wordpress/wp-content/mu-plugins/vietnamguide-core.php`

- [x] **Step 1: Add Admin-first metadata keys**

Add `VG_ADMIN_FIRST_META_KEYS` with:

```php
const VG_ADMIN_FIRST_META_KEYS = [
    'content_owner'      => 'vg_content_owner',
    'automation_lock'    => 'vg_automation_lock',
    'last_manual_review' => 'vg_last_manual_review',
    'workflow_notes'     => 'vg_admin_first_notes',
];
```

- [x] **Step 2: Add guarded WP-CLI write protection**

Hook `wp_insert_post_data` and block protected field changes when a post/page is `wp_admin` owned or `locked`, unless `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1` is explicitly set.

Protected post fields:

```text
post_title, post_name, post_content, post_excerpt, post_status, post_parent, menu_order
```

- [x] **Step 3: Add guarded metadata protection**

Hook `update_post_metadata` and block Rank Math, EEAT, featured image, Yoast, and GeneratePress headline metadata changes for Admin-first content unless explicit override is set.

Protected metadata families:

```text
rank_math_*, _rank_math_*, vg_eeat_*, _thumbnail_id, _generate-disable-headline
```

- [x] **Step 4: Add manual drift detection for unlocked content**

Store automation baselines for content and protected metadata:

```text
_vg_last_automation_content_hash
_vg_last_automation_meta_hash_*
```

If WP-CLI automation tries to change protected content or metadata after the current value differs from the last automation baseline, abort unless `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1` is explicitly set.

### Task 3: WordPress Admin Fields

**Files:**
- Modify: `wordpress/wp-content/mu-plugins/vietnamguide-core.php`

- [x] **Step 1: Register the ACF workflow group**

Add local ACF group `group_vg_admin_first_workflow` for both Pages and Posts.

Required fields:

```text
vg_content_owner
vg_automation_lock
vg_last_manual_review
vg_admin_first_notes
```

- [x] **Step 2: Keep manual editing allowed**

Guard applies to WP-CLI automation only. WordPress Admin saves remain allowed so editors can tune Gutenberg blocks, Rank Math, featured images, categories, tags, and EEAT fields normally.

### Task 4: Safe Ownership Marker

**Files:**
- Create: `ops/mark-wordpress-admin-owned-content.php`

- [x] **Step 1: Add dry-run-first marker script**

Default behavior requires explicit scope and does not write unless `VG_ADMIN_FIRST_MARK_APPLY=1` is set.

Dry-run command:

```bash
VG_ADMIN_FIRST_INCLUDE_PATHS=plan/vietnam-travel-guide wp eval-file ops/mark-wordpress-admin-owned-content.php --allow-root
```

Apply command:

```bash
VG_ADMIN_FIRST_INCLUDE_PATHS=plan/vietnam-travel-guide VG_ADMIN_FIRST_MARK_APPLY=1 wp eval-file ops/mark-wordpress-admin-owned-content.php --allow-root
```

- [x] **Step 2: Support all-content migration with an explicit double opt-in**

All-content migration requires both:

```bash
VG_ADMIN_FIRST_MARK_ALL=1
VG_ADMIN_FIRST_MARK_APPLY=1
```

### Task 5: Runtime Verification

**Files:**
- Create: `ops/verify-admin-first-workflow.php`

- [x] **Step 1: Add read-only WP-CLI verifier**

The verifier must check:

```text
Admin-first functions exist
wp_insert_post_data guard is registered
update_post_metadata guard is registered
Admin-owned content count
owner/lock consistency
```

- [x] **Step 2: Keep it verification-only**

The verifier may report failures and notes, but it must not update post content or metadata.

### Task 6: Editorial Runbook

**Files:**
- Create: `docs/editorial/wordpress-admin-first-operating-model.md`

- [x] **Step 1: Document Pages vs Posts**

Pages are for evergreen pillars, major guides, itineraries, and comparisons. Posts are for calendar growth, smaller destinations, food, neighborhoods, top lists, and Search Console response pieces.

- [x] **Step 2: Document override policy**

Automation override requires backup, known scope, editorial approval, and verification-log entry.

### Task 7: Verification

**Files:**
- Read: `ops/verify-admin-first-workflow-static.ps1`
- Read: PHP files touched above

- [x] **Step 1: Run static workflow verification**

```powershell
powershell -ExecutionPolicy Bypass -File ops\verify-admin-first-workflow-static.ps1
```

Observed: PASS.

- [x] **Step 2: Run PHP lint**

```powershell
php -l wordpress\wp-content\mu-plugins\vietnamguide-core.php
php -l ops\mark-wordpress-admin-owned-content.php
php -l ops\verify-admin-first-workflow.php
```

Expected: all report `No syntax errors detected`.

Observed on the VPS after staging upload: all three PHP files reported `No syntax errors detected`.

- [x] **Step 3: Run existing static verifier sweep**

```powershell
Get-ChildItem ops -Filter 'verify-*-static.ps1' | Sort-Object Name | ForEach-Object { powershell -ExecutionPolicy Bypass -File $_.FullName }
```

Observed: all 35 static verifiers pass.

- [x] **Step 4: Deploy and verify live**

Commands/checks run on the VPS:

```bash
wp eval-file ops/verify-admin-first-workflow.php --allow-root
wp eval-file ops/verify-eeat-content.php --allow-root
```

Observed: `68` published pages were marked `wp_admin` and `locked`; Admin-first runtime verifier passed; EEAT verifier passed; homepage, UNESCO guide, and sitemap returned HTTP `200`.

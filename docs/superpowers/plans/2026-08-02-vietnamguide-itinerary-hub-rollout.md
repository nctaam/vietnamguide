# VietnamGuide Itinerary Hub Rollout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expand the Stitch + Editorial Spine Guide Experience System from the four pilots to every published itinerary page while avoiding broad or automated post-content or metadata rewrites and leaving non-itinerary templates unchanged.

**Architecture:** Keep `vg_guide_pilot_paths()` as the explicit rollout gate. Add the four compatible itinerary paths to that allowlist, update the local/public contract inventories so the expanded set is tested, and preserve representative non-pilot pages from destination, comparison, and practical hubs. The deployment remains theme-code/assets only except for the single controlled post-deployment repair of Hanoi post `320`, which synchronized semantic hero classes in JSON/HTML, set modified `2026-08-02T10:55:00`, and purged cache.

**Tech Stack:** WordPress PHP theme templates, PowerShell contract/mutation/public verifiers, SSH/WordPress admin deployment, browser QA.

---

### Task 1: Confirm itinerary inventory and baseline contract

**Files:**
- Read: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php`
- Read: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php`
- Read: `ops/verify-guide-experience.ps1`
- Read: `ops/verify-guide-experience-public.ps1`

- [x] **Step 1: Verify the current checkout and baseline tests**

Run from `D:\Documents\vietnamguide`:

```powershell
git status --short
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-mutations.ps1
```

Expected: only the seven known user-owned untracked paths are listed and both verifiers exit `0`.

- [x] **Step 2: Confirm all published itinerary URLs are compatible before editing**

Check these production URLs with HTTP 200, one existing hero marker, and no fatal text:

```text
https://vietnamguide.net/itineraries/10-days-in-vietnam/
https://vietnamguide.net/itineraries/7-days-in-vietnam/
https://vietnamguide.net/itineraries/14-days-in-vietnam/
https://vietnamguide.net/itineraries/21-days-in-vietnam/
https://vietnamguide.net/itineraries/hanoi-in-2-days/
```

Record the result in the implementation handoff; do not alter content if a URL fails the gate.

### Task 2: Expand the explicit itinerary allowlist and regression inventories

**Files:**
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php:6-14`
- Modify: `ops/verify-guide-experience.ps1` (pilot/non-pilot exact-set assertions)
- Modify: `ops/verify-guide-experience-public.ps1` (production pilot/non-pilot URL arrays)
- Modify: `ops/verify-guide-experience-mutations.ps1` (allowlist and inventory mutation fixtures)

- [x] **Step 1: Write the contract expectation before implementation**

The exact pilot set must become:

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

The exact public non-pilot regression set must become:

```text
destinations/hanoi-travel-guide
compare/da-nang-vs-hoi-an
plan/sim-esim-vietnam
plan/transport-within-vietnam
```

- [x] **Step 2: Add the four itinerary paths to `vg_guide_pilot_paths()`**

Keep strict path matching and the existing classifier unchanged; only append the four paths to the returned array.

- [x] **Step 3: Update local and public verifier inventories**

Make the exact-set assertions and the `$PilotPaths`/`$NonPilotPaths` arrays match Step 1. Keep one destination, comparison, and practical page in the non-pilot set to prove hub isolation.

- [x] **Step 4: Add mutation coverage for the expanded gate**

Retain the existing pilot-allowlist bypass mutation and add a mutation that replaces `itineraries/14-days-in-vietnam` in the routing allowlist with an unrelated path; the verifier must reject it. Update the non-pilot inventory mutation to target `destinations/hanoi-travel-guide` after the inventory change.

- [x] **Step 5: Run the focused contract and mutation verifiers**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-mutations.ps1
```

Expected: both exit `0`; the mutation verifier reports every mutation rejected.

### Task 3: Validate the expanded public surface locally and in production

**Files:**
- Read: `ops/verify-guide-experience-public.ps1`
- Read: `ops/verify-guide-experience-live.php`
- Read: `ops/verification-log.md`

- [x] **Step 1: Run public verifier fixtures before deployment**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-public.ps1 -FixturesOnly
```

Expected: fixture verification passes for every declared origin.

- [x] **Step 2: Build a deterministic artifact from the reviewed `master` HEAD**

Use the existing builder at `C:\Users\NCTaam\.codex\visualizations\2026\07\27\019fa252-28dd-7062-9e8e-bc4f52ab2b71\vg-guide-deployer-final-20260730`, require branch `master`, and confirm two builds produce identical ZIP and manifest hashes.

- [x] **Step 3: Deploy through the guarded WordPress workflow**

Upload the reviewed artifact through the existing WordPress admin/SSH deployment path, preserve a fresh backup, install only the 11 theme payload files, purge object/theme/LiteSpeed caches, and remove the transient helper after post-install checks.

- [x] **Step 4: Run the production public verifier**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-public.ps1
```

Expected: all eight pilot itinerary/destination/comparison/practical URLs return HTTP 200, exactly one H1, guide shell, TOC/jump navigation, content-versioned CSS/JS, valid fragments, and no fatal text; the four non-pilot URLs remain on the default template.

- [x] **Step 5: Perform browser QA at desktop and mobile widths**

Check `https://vietnamguide.net/itineraries/7-days-in-vietnam/`, `/14-days-in-vietnam/`, `/21-days-in-vietnam/`, and `/hanoi-in-2-days/` at 1280px and 390px, plus reduced motion. Confirm title hierarchy, sticky TOC containment, mobile jump navigation, trust rail, table scrolling/focus, related routes, no horizontal overflow, and no console errors.

- [x] **Step 6: Update the verification log with evidence**

Append the new production backup, artifact hashes, exact pilot/non-pilot inventories, verifier output, and browser QA results to `ops/verification-log.md`.

### Task 4: Final review and commit

**Files:**
- Review all modified files from Tasks 2–3.

- [x] **Step 1: Run the complete regression suite**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-block-patterns.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-mu-plugin.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-homepage-theme.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-mutations.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-public.ps1
git diff --check
```

Expected: every command exits `0`, the mutation verifier reports `111 rejected mutations`, production public verification passes, and no known user-owned untracked file is staged.

- [x] **Step 2: Commit the follow-up verifier hardening**

Source rollout commit `c26ff4f` and baseline verifier commit `e99de00` are complete. Commit only the three follow-up verifier files; do not stage the verification log, this plan, or the seven user-owned paths in this commit.

```powershell
git add ops/verify-guide-experience-tokenizer.php ops/verify-guide-experience.ps1 ops/verify-guide-experience-mutations.ps1
git commit -m "fix: harden guide inventory verification"
```

Completed as `3617b274b3b04150f480b28e0d997f2ae2840ee6`.

- [x] **Step 3: Commit the remaining rollout evidence**

After the verifier-hardening commit succeeds, commit only the rollout log and this implementation plan.

```powershell
git add ops/verification-log.md docs/superpowers/plans/2026-08-02-vietnamguide-itinerary-hub-rollout.md
git commit -m "docs: record itinerary hub rollout"
```

- [x] **Step 4: Verify the commits and working tree**

```powershell
git show --stat --oneline HEAD~1
git show --stat --oneline HEAD
git status --short
```

Expected: the verifier commit contains only the three verifier files, the documentation commit contains only the log and rollout plan, and the seven pre-existing user-owned paths remain untracked and untouched.

# VietnamGuide Comparison Evidence and Decision Rollout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Activate the Stitch + Editorial Spine Guide Experience on the nine remaining comparison pages and ship a deterministic, auditable Evidence + Decision System with balanced sources, explicit qualitative decision paths, safe server rendering, and a reversible two-stage production rollout.

**Architecture:** Keep the current eight Guide Experience pilots as the immutable baseline, declare the nine comparison targets in code, and use a fail-closed, atomically replaced activation snapshot to expose exactly 8, 11, or 17 paths by rollout stage. Store reviewed editorial inputs in canonical JSON registries and a page manifest, compile each active page into one versioned `vg_eeat_comparison_bundle`, and render from that single metadata snapshot through a later-loading comparison MU plugin plus one scoped theme module. Keep production mutation in a WP-CLI updater that owns locking, approvals, DNS-pinned source checks, checksummed backups, append-only ledgers, activation, compatibility sync, and rollback.

**Tech Stack:** WordPress/PHP 8.3, WP-CLI, PowerShell 7/Windows PowerShell 5.1 contract tests, JSON Schema 2020-12 data contracts, cURL with `CURLOPT_RESOLVE`, semantic server-rendered HTML, existing VietnamGuide theme CSS tokens, SSH to the production LiteSpeed host, and browser QA through the in-app browser.

---

## Approved Scope

The implementation must keep this exact comparison target order and post-ID mapping:

| Stage | Path | Post ID |
| --- | --- | ---: |
| Stage 2 | `compare/cu-chi-tunnels-vs-mekong-delta-day-trip` | 279 |
| Stage 2 | `compare/da-nang-vs-hoi-an` | 209 |
| Stage 2 | `compare/hoi-an-vs-hue` | 227 |
| Stage 2 | `compare/mui-ne-vs-nha-trang` | 247 |
| Canary | `compare/ninh-binh-day-trip-vs-overnight` | 326 |
| Canary | `compare/north-central-south-vietnam` | 104 |
| Canary | `compare/old-quarter-vs-french-quarter-vs-west-lake` | 301 |
| Stage 2 | `compare/phu-quoc-vs-nha-trang` | 241 |
| Stage 2 | `compare/trang-an-vs-tam-coc` | 336 |

The exact canary activation order is:

```text
compare/old-quarter-vs-french-quarter-vs-west-lake
compare/ninh-binh-day-trip-vs-overnight
compare/north-central-south-vietnam
```

The exact permanent public controls are:

```text
compare
destinations/hanoi-travel-guide
plan/sim-esim-vietnam
plan/transport-within-vietnam
```

The updater may change only the 17 approved `vg_eeat_*` fields named in the approved design and may insert only the missing source-trail and update-log shortcodes at the canonical article tail. It must not rewrite article prose, tables, headings, hero markup, media, SEO/schema fields, slugs, parents, authors, menu order, or publish state.

## File Responsibility Map

### Versioned editorial data

- `ops/comparison-rollout/schema.json`: strict schemas for registries, manifest, resolved bundle v1/v2, activation snapshot, approval artifact, backup, ledger event, coverage matrix, impact report, and source-probe result.
- `ops/comparison-rollout/organizations.json`: controlling organizations and their exact publisher/domain ownership records.
- `ops/comparison-rollout/sources.json`: stable source records that reference publisher and organization IDs.
- `ops/comparison-rollout/identities.json`: stable editorial identities mapped to active WordPress user IDs and allowed roles; no secret material.
- `ops/comparison-rollout/manifest.json`: exact nine-page editorial decisions, evidence mappings, rule paths, module insertion flags, fingerprints, and related routes.
- `ops/comparison-rollout/artifact.json`: deterministic validator output containing input hashes, resolved bundles, coverage matrices, impact indexes, exact stage inventories, and artifact version.

### Local validators and build tooling

- `ops/comparison-rollout-validator.psm1`: canonical JSON, schema traversal, evidence/diversity/freshness/provenance/decision/route checks, bundle compilation, and deterministic report generation.
- `ops/comparison-rollout/fixtures/minimal/organizations.json`: valid two-organization schema/resolution fixture.
- `ops/comparison-rollout/fixtures/minimal/sources.json`: valid primary/corroborating/live-check source fixture.
- `ops/comparison-rollout/fixtures/minimal/identities.json`: valid distinct author/reviewer fixture.
- `ops/comparison-rollout/fixtures/minimal/manifest.json`: valid two-option decision/provenance/route fixture.
- `ops/verify-comparison-rollout.ps1`: human-readable and JSON static verifier entry point.
- `ops/verify-comparison-rollout-mutations.ps1`: permanent isolated mutation suite.
- `ops/build-comparison-rollout-artifact.ps1`: deterministic `artifact.json`, payload manifest, and ZIP builder.
- `ops/verify-comparison-rollout-public.ps1`: stage-aware public HTML, asset, growth-budget, and control verifier.

### WordPress runtime

- `wordpress/wp-content/mu-plugins/vietnamguide-z-comparison.php`: later-loading bootstrap, version constants, module includes, shortcode replacement registration, and bounded logging.
- `wordpress/wp-content/mu-plugins/vietnamguide-comparison/bundle.php`: one-read bundle loading, schema/current-previous compatibility, activation checks, cache identity, and safe fallback.
- `wordpress/wp-content/mu-plugins/vietnamguide-comparison/render.php`: escaped decision, grouped source, grouped route, and update-log HTML.
- `wordpress/wp-content/mu-plugins/vietnamguide-comparison/shortcodes.php`: active-comparison wrappers that delegate unchanged non-comparison behavior to `vietnamguide-core.php`.
- `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-comparison.php`: exact target inventory, activation-snapshot parsing, decision-frame bridge, and context helpers.
- `wordpress/wp-content/themes/vietnamguide-premium/functions.php`: require `guide-comparison.php` before routing/context.
- `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php`: merge the immutable eight pilots with only the exact active comparison subset.
- `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php`: add and validate `comparison_decision_html` and use bundle source count for active comparisons.
- `wordpress/wp-content/themes/vietnamguide-premium/template-parts/guide-page.php`: render the decision frame before the long-form body.
- `wordpress/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css`: comparison-scoped styling within the 6 KB growth budget.

### Production operations

- `ops/comparison-rollout-lib.php`: PHP parity library for canonical JSON, schema checks, bundle compilation, approvals, safe source probes, content fingerprints, stage state, backup/ledger primitives, and rendering metrics.
- `ops/comparison-rollout-approve.php`: server-only approval-artifact generator using a dedicated environment secret and key ID.
- `ops/apply-comparison-rollout.php`: WP-CLI modes `validate`, `dry-run`, `apply`, `activate`, `compatibility-sync`, `rollback`, and `recovery-audit`.
- `ops/verify-comparison-rollout-live.php`: WordPress stored-state, query-count, render-time, schema-fallback, isolation, and rollback fixtures.
- `ops/verification-log.md`: append final artifact hashes, backup/ledger locations, stage results, browser results, and rollback evidence.

## Canonical Interfaces

The PowerShell module must export exactly these functions:

```powershell
Export-ModuleMember -Function @(
    'Read-VgJsonDocument',
    'ConvertTo-VgCanonicalJson',
    'Get-VgSha256Hex',
    'Test-VgSchemaDocument',
    'Resolve-VgComparisonPortfolio',
    'Test-VgComparisonPortfolio',
    'New-VgComparisonArtifact',
    'Compare-VgArtifactDeterminism'
)
```

`Test-VgComparisonPortfolio` must return one object with this exact shape:

```powershell
[pscustomobject]@{
    Ok = [bool]
    Errors = [string[]]
    Hashes = [ordered]@{}
    ResolvedBundles = [ordered]@{}
    CoverageMatrix = [object[]]
    ImpactIndex = [ordered]@{}
    StageInventories = [ordered]@{}
}
```

The PHP library must expose the equivalent public surface:

```php
vg_comparison_canonical_json(mixed $value): string
vg_comparison_sha256(mixed $value): string
vg_comparison_validate_schema(mixed $value, array $schema, string $pointer = '$'): array
vg_comparison_compile_portfolio(array $manifest, array $sources, array $organizations, array $identities, array $schema): array
vg_comparison_probe_source(array $source, array $limits): array
vg_comparison_verify_approval(array $artifact, array $identity_registry, string $manifest_hash): array
vg_comparison_content_fingerprint(string $content): string
vg_comparison_insert_modules(string $content, array $requirements): array
vg_comparison_render_metrics(string $html): array
```

All validator error messages use `CODE path-or-id: explanation`, for example:

```text
E_BALANCE compare/da-nang-vs-hoi-an option=hoi_an: 3 of 8 unique option-source edges is below 40 percent
```

The canonical stored identifiers are:

```text
source_class: national_official | local_official | operational | conditions_heritage | independent_corroboration
claim_group: access_transport | timing_duration | cost_booking | season_current_conditions | experience_fit | constraints_safety
evidence_label: primary | corroborating | live_check_required
freshness_tier: live | current | stable
rule_kind: hard_constraint | preference | tie_breaker
route_group: deepen_place | build_route | check_practical
change_reason: source_refresh | operational_change | decision_change | route_change | correction
approval_role: author | reviewer
activation_stage: baseline | canary | full
terminal_outcome: option | no_clear_winner | combine_or_sequence
```

Bundle schema v2 is authoritative for one active comparison page. Besides the fields listed in Task 2, it must include `editorial`, `field_note`, `evidence_moat`, `module_requirements`, and `provenance_hash`. `editorial` contains `reviewed_guide`, `written_by_identity_id`, `reviewed_by_identity_id`, `last_meaningful_update`, `update_summary`, `change_reason`, and `affected_public_labels`.

Compatibility sync derives exactly these 16 fields from the bundle; `vg_eeat_comparison_bundle` is the seventeenth controlled field and is never derived from a legacy field:

```text
vg_eeat_primary_decision
vg_eeat_reviewed_guide
vg_eeat_written_by
vg_eeat_reviewed_by
vg_eeat_last_meaningful_update
vg_eeat_update_summary
vg_eeat_sources_checked
vg_eeat_source_assignments
vg_eeat_field_note
vg_eeat_evidence_moat
vg_eeat_related_routes
vg_eeat_comparison_archetype
vg_eeat_compared_localities
vg_eeat_decision_axes
vg_eeat_traveler_lenses
vg_eeat_source_registry_version
```

## Editorial Allocation Matrix

Use these exact archetype, option, axis, lens, and source-allocation contracts. Each page resolves eight source assignments. The source allocation tuple is `national/local/operational/conditions-or-heritage/independent`.

| Path | Archetype | Options | Axes | Traveler lenses | Source allocation |
| --- | --- | --- | --- | --- | --- |
| `compare/cu-chi-tunnels-vs-mekong-delta-day-trip` | `competing_day_trips` | `cu_chi`, `mekong_delta` | `transfer_time`, `pace`, `experience_depth`, `weather_resilience`, `mobility_fit` | `first_time_visitor`, `short_on_time`, `culture_focused`, `mobility_sensitive` | `1/2/2/1/2` |
| `compare/da-nang-vs-hoi-an` | `city_or_heritage_base` | `da_nang`, `hoi_an` | `walkability`, `transfer_friction`, `evenings`, `day_trip_reach`, `heritage_immersion` | `first_time_visitor`, `family`, `comfort_focused`, `culture_focused`, `beach_focused` | `1/2/2/1/2` |
| `compare/hoi-an-vs-hue` | `city_or_heritage_base` | `hoi_an`, `hue` | `walkability`, `transfer_friction`, `evenings`, `day_trip_reach`, `heritage_immersion` | `short_on_time`, `slow_traveler`, `family`, `culture_focused` | `1/2/2/2/1` |
| `compare/mui-ne-vs-nha-trang` | `coast_and_island` | `mui_ne`, `nha_trang` | `seasonal_weather`, `beach_style`, `water_conditions`, `resort_versus_city_balance`, `transfer_friction` | `budget_focused`, `family`, `nightlife_oriented`, `beach_focused` | `1/2/2/2/1` |
| `compare/ninh-binh-day-trip-vs-overnight` | `time_allocation` | `day_trip`, `overnight` | `travel_overhead`, `crowd_timing`, `overnight_benefit`, `cost_delta`, `itinerary_fit` | `short_on_time`, `slow_traveler`, `family`, `budget_focused`, `culture_focused` | `1/2/2/2/1` |
| `compare/north-central-south-vietnam` | `macro_region` | `north`, `central`, `south` | `season`, `trip_length`, `route_cohesion`, `intercity_transport`, `experience_range` | `first_time_visitor`, `short_on_time`, `slow_traveler`, `beach_focused`, `culture_focused` | `2/2/2/1/1` |
| `compare/old-quarter-vs-french-quarter-vs-west-lake` | `neighborhood` | `old_quarter`, `french_quarter`, `west_lake` | `noise`, `walkability`, `price_level`, `atmosphere`, `transport_access` | `first_time_visitor`, `budget_focused`, `comfort_focused`, `nightlife_oriented`, `mobility_sensitive` | `1/2/2/1/2` |
| `compare/phu-quoc-vs-nha-trang` | `coast_and_island` | `phu_quoc`, `nha_trang` | `seasonal_weather`, `beach_style`, `water_conditions`, `resort_versus_city_balance`, `transfer_friction` | `family`, `comfort_focused`, `nightlife_oriented`, `beach_focused`, `budget_focused` | `1/2/2/2/1` |
| `compare/trang-an-vs-tam-coc` | `attraction_and_landscape` | `trang_an`, `tam_coc` | `access_mode`, `scenery`, `crowd_timing`, `activity_level`, `weather_sensitivity` | `first_time_visitor`, `short_on_time`, `slow_traveler`, `family`, `mobility_sensitive` | `1/2/2/2/1` |

Every page must include at least one Vietnamese-language local or operational assignment. Across the portfolio the resolver must prove at least 18 publisher IDs, at least 12 local/operational publisher IDs, at least 35 percent local/operational assignments, at least two publishers per required geographic group, and no national publisher/domain on more than six pages.

---

### Task 1: Baseline, Worktree, and Production Readiness Snapshot

**Files:**
- Read: `docs/superpowers/specs/2026-08-03-vietnamguide-comparison-diversity-rollout-design.md`
- Read: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php`
- Read: `ops/verify-guide-experience.ps1`
- Read: `ops/verify-guide-experience-mutations.ps1`
- Read: `ops/verify-guide-experience-public.ps1`

- [ ] **Step 1: Verify the approved documentation commit and preserve user-owned files**

Run from `D:\Documents\vietnamguide`:

```powershell
git status --short
git branch --show-current
git log -3 --oneline
```

Expected: branch `master`; HEAD contains the approved Revision 3 documentation; only the seven known user-owned untracked paths are listed.

- [ ] **Step 2: Create an isolated implementation worktree**

Use `superpowers:using-git-worktrees`, create branch `codex/comparison-evidence-decision-rollout`, and place the worktree at `D:\Documents\vietnamguide\.worktrees\comparison-evidence-decision-rollout` after verifying `.worktrees` is ignored.

```powershell
git check-ignore -q .worktrees
git worktree add '.worktrees\comparison-evidence-decision-rollout' -b 'codex/comparison-evidence-decision-rollout'
```

Expected: the new worktree is clean and points to the plan commit.

- [ ] **Step 3: Run the immutable baseline suite before feature edits**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-block-patterns.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-mu-plugin.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-homepage-theme.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-mutations.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-public.ps1 -FixturesOnly
```

Expected: every command exits `0`; the guide mutation suite reports `111 rejected mutations`.

- [ ] **Step 4: Capture the exact production baseline without writes**

First test SSH:

```powershell
Test-NetConnection 66.42.48.146 -Port 2209 -InformationLevel Detailed
ssh -p 2209 root@66.42.48.146 "cd /usr/local/lsws/vietnamguide.net/html && wp core version && wp option get home"
```

If SSH remains filtered, use the already approved one-time WordPress admin deployer only for the same read-only WP-CLI-equivalent report. Capture exact raw `post_content` SHA-256, parent ID, status, H1 count, hero marker, shortcode/module order, in-scope meta hashes, public HTML byte count, DOM-node count, LCP, and CLS for all nine targets. Store the resulting values directly in `manifest.json` during Tasks 10-12; do not save credentials or raw source bodies.

### Task 2: RED Static Contract and Strict Schemas

**Files:**
- Create: `ops/comparison-rollout/schema.json`
- Create: `ops/verify-comparison-rollout.ps1`

- [ ] **Step 1: Write the failing comparison contract entry point**

Create `ops/verify-comparison-rollout.ps1` with parameters `RepoRootOverride`, `Json`, `EmitArtifact`, `Scope`, and `AsOfDate`. It must fail with `E_FILE` in production scopes until the editorial inputs and PowerShell validator module exist. Hard-code the exact nine target mappings, 8/11/17 inventories, three canaries, and four permanent controls as ordinal arrays.

```powershell
param(
    [string]$RepoRootOverride = '',
    [switch]$Json,
    [switch]$EmitArtifact,
    [ValidateSet('fixtures', 'canary', 'six', 'portfolio')]
    [string]$Scope = 'portfolio',
    [datetime]$AsOfDate = [datetime]'2026-08-03T00:00:00Z'
)

$RequiredData = @(
    'ops/comparison-rollout/schema.json',
    'ops/comparison-rollout/organizations.json',
    'ops/comparison-rollout/sources.json',
    'ops/comparison-rollout/identities.json',
    'ops/comparison-rollout/manifest.json',
    'ops/comparison-rollout-validator.psm1'
)
```

- [ ] **Step 2: Run the verifier and confirm the RED state**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout.ps1
```

Expected: exit `1` and one deterministic `E_FILE` line per missing required file.

- [ ] **Step 3: Define the complete schema document**

Create a Draft 2020-12 schema with `$defs` for `stableId`, `sha256`, `isoDate`, `pagePath`, `sourceClass`, `claimGroup`, `evidenceLabel`, `freshnessTier`, `ruleKind`, `routeGroup`, `changeReason`, `manifest`, `sourceRegistry`, `organizationRegistry`, `identityRegistry`, `resolvedBundleV1`, `resolvedBundleV2`, `activationSnapshot`, `approvalArtifact`, `backup`, `ledgerEvent`, `sourceProbe`, `coverageRow`, and `impactReport`.

The bundle v2 schema must set `additionalProperties: false`, cap serialized data at 65,536 bytes in the verifier, and require these keys:

```json
[
  "schema_version",
  "bundle_hash",
  "manifest_version",
  "source_registry_version",
  "organization_registry_version",
  "activation_artifact_version",
  "path",
  "post_id",
  "editorial",
  "archetype",
  "localities",
  "options",
  "primary_decision",
  "field_note",
  "evidence_moat",
  "axes",
  "traveler_lenses",
  "sources",
  "related_routes",
  "update_log",
  "module_requirements",
  "provenance_hash",
  "render_contract"
]
```

`render_contract` must contain `decision_heading`, `source_heading`, `route_heading`, `update_heading`, `page_language`, and `max_visible_characters`; raw HTML fields are absent from every schema.

- [ ] **Step 4: Add schema-only positive and negative fixtures**

Use in-memory objects to prove required-key enforcement, scalar types, enumerations, item/character limits, `additionalProperties: false`, raw-HTML rejection, and the 64 KB serialized-bundle cap. The focused verifier must pass schema fixtures with `-Scope fixtures` even though production registry files are not created until Task 10.

- [ ] **Step 5: Commit the RED contract and schema**

```powershell
git add -- ops/comparison-rollout/schema.json ops/verify-comparison-rollout.ps1
git commit -m "test: define comparison rollout contracts"
```

### Task 3: Canonical Resolver, Evidence Balance, and Provenance Validator

**Files:**
- Create: `ops/comparison-rollout-validator.psm1`
- Create: `ops/comparison-rollout/fixtures/minimal/organizations.json`
- Create: `ops/comparison-rollout/fixtures/minimal/sources.json`
- Create: `ops/comparison-rollout/fixtures/minimal/identities.json`
- Create: `ops/comparison-rollout/fixtures/minimal/manifest.json`
- Modify: `ops/verify-comparison-rollout.ps1`

- [ ] **Step 1: Write failing fixtures for canonical serialization and strict keys**

Add in-memory fixtures proving ordinal key sorting, preserved array order, UTF-8 without BOM, lowercase SHA-256, duplicate-key rejection before `ConvertFrom-Json`, invalid UTF-8 rejection, unknown-key rejection, and identical hashes for semantically identical object key orders.

```powershell
$CanonicalA = ConvertTo-VgCanonicalJson ([ordered]@{ b = 2; a = 1 })
$CanonicalB = ConvertTo-VgCanonicalJson ([ordered]@{ a = 1; b = 2 })
if ($CanonicalA -cne '{"a":1,"b":2}' -or $CanonicalA -cne $CanonicalB) {
    throw 'E_CANONICAL canonical JSON ordering is not deterministic'
}
```

Run the verifier and expect `E_MODULE` because the exported functions do not exist yet.

- [ ] **Step 2: Implement the canonical JSON and schema primitives**

Implement every exported function from the Canonical Interfaces section. `Read-VgJsonDocument` must read bytes, reject BOM/control corruption and duplicate JSON object keys with a token scan, then call `ConvertFrom-Json`. `ConvertTo-VgCanonicalJson` must sort object keys with `StringComparer.Ordinal`, preserve array order, serialize booleans/null/numbers without culture-sensitive formatting, and use compact JSON escaping.

- [ ] **Step 3: Implement exact evidence and portfolio formulas**

The resolver must deduplicate `(page path, option ID, source ID)` edges, exclude `all_options`, and apply only these integer tests:

```powershell
$PassesTwoWay = ($OptionEdges * 100) -ge ($TotalOptionEdges * 40)
$PassesThreeWay = ($OptionEdges * 100) -ge ($TotalOptionEdges * 25)
$PassesDomainCap = ($DomainSources * 100) -le ($PageSources * 40)
```

It must also enforce current primary access/timing coverage per option, two-organization experience-fit coverage, symmetric decisive axes, negative-constraint corroboration, settled-outcome independence, explicit `not_applicable` reasons, source-class minimums, locality/language coverage, 18-publisher/12-local-first-party/35-percent portfolio gates, six-page national reuse cap, and controlling-organization alias collapse.

- [ ] **Step 4: Implement the acyclic provenance and impact index**

Build directed edges only in this order:

```text
source_id -> claim_id -> axis_id -> outcome_id
```

Reject orphan claims/axes/outcomes and cycles. Allow background-only sources only when `decisive=false`. Produce `ImpactIndex[source_id]` with sorted `claim_ids`, `axis_ids`, and `outcome_ids`; a source refresh never mutates an outcome.

- [ ] **Step 5: Implement qualitative rule-path replay**

Accept only `hard_constraint`, `preference`, and `tie_breaker`; preserve manifest array order; forbid weights/priorities/scores; require at most one first hard constraint, exactly one preference unless the hard constraint terminates, at most one later tie-breaker, context-tag intersection, terminal outcome, and one material trade-off or reversal condition. Rendered path data contains only the used rules.

- [ ] **Step 6: Create a complete minimal resolver fixture**

Create four fixture JSON files containing two options, the three mandatory claim groups, one decisive axis, three traveler lenses with different outcomes, eight source assignments, all three route groups, two controlling organizations, distinct author/reviewer identities, and one complete acyclic provenance path per public outcome. The fixture must be fully valid rather than bypassing evidence or route rules.

- [ ] **Step 7: Run focused fixtures and commit**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout.ps1 -Scope fixtures -AsOfDate '2026-08-03T00:00:00Z'
```

Expected: canonical, schema, evidence, provenance, decision-path, and route fixtures pass.

```powershell
git add -- ops/comparison-rollout-validator.psm1 ops/comparison-rollout/fixtures/minimal/*.json ops/verify-comparison-rollout.ps1
git commit -m "feat: validate comparison evidence graphs"
```

### Task 4: Identity Registry and Dedicated-Key Approval Contracts

**Files:**
- Create: `ops/comparison-rollout-lib.php`
- Create: `ops/comparison-rollout-approve.php`
- Create: `ops/comparison-rollout/identities.json`
- Modify: `ops/verify-comparison-rollout.ps1`

- [ ] **Step 1: Write RED approval vectors**

Add fixed test vectors with manifest hash `64` lowercase hex characters, change IDs `cmp-104-source-refresh` and `cmp-104-decision`, distinct `author` and `reviewer` identities, key ID `comparison-approval-2026-01`, and a secret supplied only through `VG_COMPARISON_APPROVAL_SECRET_2026_01`. Test valid HMAC, altered scope, altered hash, unknown key ID, inactive WordPress user, unauthorized role, duplicate identity, identical WordPress user ID, and a secret equal to any loaded WordPress authentication salt.

- [ ] **Step 2: Implement PHP canonical/schema parity**

Implement the PHP public functions listed in Canonical Interfaces. Add a `--self-test` execution path to `comparison-rollout-lib.php` so the server command below runs without WordPress writes:

```bash
php ops/comparison-rollout-lib.php --self-test
```

Expected: `Comparison rollout PHP self-tests passed.`

- [ ] **Step 3: Implement the server-only signer**

`comparison-rollout-approve.php` must run only under CLI/WP-CLI, load the exact active identity from WordPress, canonicalize this payload, and HMAC it with `hash_hmac('sha256', $canonical_payload, $secret)`:

```json
{
  "artifact_version": "1",
  "manifest_hash": "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa",
  "identity_id": "editorial_author_primary",
  "wp_user_id": 1,
  "role": "author",
  "timestamp_utc": "2026-08-03T00:00:00Z",
  "change_ids": ["cmp-104-decision"],
  "change_reason": "decision_change",
  "key_id": "comparison-approval-2026-01"
}
```

The output file is created with mode `0600` outside the repository and web root. Key ID `comparison-approval-2026-01` resolves only to `VG_COMPARISON_APPROVAL_SECRET_2026_01`; later key IDs use new environment variables, and old key variables remain available for at least the retention life of their ledgers and backups. The secret, payload body, and WordPress salts never enter a ledger or public attribute.

- [ ] **Step 4: Verify identity-role and four-eyes rules**

Source refresh with unchanged outcomes requires one reviewer artifact. `decision_change`, changes to hard constraints, negative recommendations, archetypes, or decisive axes require one author plus one reviewer with distinct identity IDs and WordPress user IDs, both bound to the same manifest hash and affected change IDs.

- [ ] **Step 5: Run locally available checks and commit governance support**

Run the PowerShell approval vectors locally. If `Get-Command php` returns no executable, record PHP CLI validation as pending and run the self-test and `php -l` on the production host before deployment in Task 15; do not claim local PHP execution.

```powershell
git add -- ops/comparison-rollout-lib.php ops/comparison-rollout-approve.php ops/comparison-rollout/identities.json ops/verify-comparison-rollout.ps1
git commit -m "feat: enforce comparison editorial approvals"
```

### Task 5: Versioned Single-Read Bundle Loader and Safe Fallback

**Files:**
- Create: `wordpress/wp-content/mu-plugins/vietnamguide-z-comparison.php`
- Create: `wordpress/wp-content/mu-plugins/vietnamguide-comparison/bundle.php`
- Modify: `ops/verify-comparison-rollout.ps1`

- [ ] **Step 1: Add failing runtime contract assertions**

Require bootstrap constants `VG_COMPARISON_BUNDLE_SCHEMA_CURRENT = '2'`, `VG_COMPARISON_BUNDLE_SCHEMA_PREVIOUS = '1'`, and `VG_COMPARISON_ACTIVATION_ARTIFACT_VERSION`. Require one `get_post_meta($post_id, 'vg_eeat_comparison_bundle', true)` call in the loader and forbid `wp_remote_get`, cURL, per-source meta reads, raw shortcode evaluation, or output of run IDs/local paths.

- [ ] **Step 2: Implement the bootstrap**

Load `bundle.php`, `render.php`, and `shortcodes.php`, register shortcode replacement at `init` priority `20`, and expose a rate-limited `vg_comparison_log_rejection(string $code, int $post_id): void` that logs only code, post ID, schema version, and bundle hash prefix.

- [ ] **Step 3: Implement one-read bundle loading**

Use a request-static cache keyed by post ID. Reject inactive paths before reading metadata. Decode JSON as an associative array, verify current or previous schema, exact path/post ID, registry/artifact versions, 64 KB cap, canonical self-hash, and allowlisted keys. Return this exact result shape:

```php
[
    'ok' => true,
    'bundle' => $bundle,
    'bundle_hash' => $bundle_hash,
    'cache_key' => implode(':', [$path, $bundle_hash, $schema_version, $source_registry_version, $organization_registry_version, $activation_version]),
]
```

On rejection return `['ok' => false, 'reason' => $code]`; never throw into the public template.

- [ ] **Step 4: Implement current/previous migration**

Support v1 only through a pure `vg_comparison_migrate_bundle_v1_to_v2(array $bundle): array` mapping. Unknown versions return baseline behavior. A rejected comparison module must not suppress hero, TOC, article body, existing source trail, existing related routes, or pagination.

- [ ] **Step 5: Commit the loader**

```powershell
git add -- wordpress/wp-content/mu-plugins/vietnamguide-z-comparison.php wordpress/wp-content/mu-plugins/vietnamguide-comparison/bundle.php ops/verify-comparison-rollout.ps1
git commit -m "feat: load versioned comparison bundles"
```

### Task 6: Escaped Comparison Renderers and Shortcode Wrappers

**Files:**
- Create: `wordpress/wp-content/mu-plugins/vietnamguide-comparison/render.php`
- Create: `wordpress/wp-content/mu-plugins/vietnamguide-comparison/shortcodes.php`
- Modify: `ops/verify-comparison-rollout.ps1`

- [ ] **Step 1: Write failing output-boundary assertions**

Require `esc_html()` for text, `esc_attr()` for data attributes and `lang`, and `esc_url()` for links. Reject `target="_blank"`, manifest HTML, event attributes, `<script`, `<style`, shortcode attributes on active comparison modules, numeric confidence, and source-count math in public output.

- [ ] **Step 2: Implement the decision-frame renderer**

Render one `<section class="vg-comparison-decision">` with non-sensitive `data-vg-bundle-version`, `data-vg-bundle-hash`, and `data-vg-render-hash` attributes, one H2, one primary statement at most 240 characters, 3-6 axis list rows, and 3-5 traveler-lens cards. Each lens renders only its reviewed constraint/preference/tie-breaker path, outcome, and trade-off/reversal. Enforce 1,800 visible characters and no table; failure returns an empty string.

- [ ] **Step 3: Implement grouped source, route, and update modules**

Source groups follow the five source classes and expose publisher, locality, optional `lang="vi"` title, checked date, claim coverage, evidence label text, descriptive link, and the same non-sensitive version/hash attributes. Route groups render exactly `Deepen place`, `Build route`, and `Check practical`. Update log exposes reviewed date, controlled change reason, public summary, and affected public labels without internal reviewer notes.

- [ ] **Step 4: Replace shortcodes only for active comparisons**

At `init` priority `20`, remove and re-add `vg_source_trail`, `vg_update_log`, and `vg_related_routes`. For inactive/non-comparison pages delegate directly to `vg_shortcode_source_trail`, `vg_shortcode_update_log`, and `vg_shortcode_related_routes`. For active valid bundles use the new renderers; for invalid bundles delegate baseline behavior. Any shortcode attributes on an active comparison return an empty string and one bounded log event.

- [ ] **Step 5: Prove module isolation and commit**

Add fixtures that reject one module at a time and confirm the other modules plus article body remain present.

```powershell
git add -- wordpress/wp-content/mu-plugins/vietnamguide-comparison/render.php wordpress/wp-content/mu-plugins/vietnamguide-comparison/shortcodes.php ops/verify-comparison-rollout.ps1
git commit -m "feat: render comparison evidence modules"
```

### Task 7: Theme Decision Frame and Stitch Editorial Styling

**Files:**
- Create: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-comparison.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/functions.php:3-7`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php:227-435`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/template-parts/guide-page.php:12-55`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css`
- Modify: `ops/verify-comparison-rollout.ps1`

- [ ] **Step 1: Write failing context and placement assertions**

Require `comparison_decision_html` as a string, empty for non-comparison/inactive/rejected bundles, and nonempty only for active valid comparison bundles. Require template order `trust metadata/jump navigation -> spine -> decision frame -> body_html` inside `.vg-guide-article` and exactly one decision frame.

- [ ] **Step 2: Implement comparison context helpers**

`guide-comparison.php` must define:

```php
vg_comparison_target_paths(): array
vg_comparison_stage_paths(string $stage): array
vg_comparison_activation_snapshot(): array
vg_is_comparison_rollout_active_path(string $path): bool
vg_get_comparison_decision_html(WP_Post $post): string
vg_get_comparison_source_count(WP_Post $post): ?int
vg_get_comparison_reviewed_at(WP_Post $post): ?string
```

The activation snapshot is read from the absolute directory in `VG_COMPARISON_STATE_DIR`; missing, malformed, wrong-version, wrong-artifact, duplicate, out-of-order, or non-exact paths yield the baseline eight pilots.

- [ ] **Step 3: Extend and validate guide context**

For active comparison pages, use the bundle's reviewed date, source count, and decision HTML; keep existing reviewed/source-count/related-route behavior for every other page. `vg_is_valid_guide_context()` must verify the decision-frame class, no H1/table/script/style/event attributes, and empty/nonempty state consistent with type and activation.

- [ ] **Step 4: Render before the article body**

Inside `.vg-guide-article`, emit `comparison_decision_html` immediately before `body_html`. Keep PHP pagination, TOC, hero, trust rail, and outer related-route fallback unchanged.

- [ ] **Step 5: Add comparison-scoped CSS**

Every selector starts with `.vg-guide-experience--comparison`. Reuse existing colors, typography, spacing, radius, focus, and motion tokens. Add responsive layout at 960/620 px, visible focus, `prefers-reduced-motion`, and `forced-colors` rules. CSS growth from the task baseline must be at most 6,144 bytes.

- [ ] **Step 6: Run static budgets and commit**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout.ps1
git diff --check
```

Expected: runtime/theme contracts pass; data curation gates remain the only failures.

```powershell
git add -- wordpress/wp-content/themes/vietnamguide-premium/inc/guide-comparison.php wordpress/wp-content/themes/vietnamguide-premium/functions.php wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php wordpress/wp-content/themes/vietnamguide-premium/template-parts/guide-page.php wordpress/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css ops/verify-comparison-rollout.ps1
git commit -m "feat: add comparison decision experience"
```

### Task 8: Exact 8/11/17 Activation and Verifier Inventories

**Files:**
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php:6-14`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-comparison.php`
- Modify: `ops/verify-guide-experience-live.php`
- Modify: `ops/verify-guide-experience-public.ps1`
- Create: `ops/verify-comparison-rollout-public.ps1`
- Modify: `ops/verify-comparison-rollout.ps1`

- [ ] **Step 1: Write RED stage fixtures**

Test exact ordered inventories for `baseline` (8), `canary` (11), and `full` (17); six deferred comparison controls in canary; four permanent controls in every stage; case changes, duplicates, `/compare/`, prefix matches, unknown stage, and unrelated paths must fail closed.

- [ ] **Step 2: Merge only the active exact comparison paths**

Change `vg_guide_pilot_paths()` to return the immutable current eight plus `vg_comparison_stage_paths($snapshot['stage'])`. The full nine target catalog remains hard-coded in `vg_comparison_target_paths()` in the approved order. No prefix or parent-page activation is allowed.

- [ ] **Step 3: Make live and public verification stage-aware**

`verify-comparison-rollout-public.ps1` accepts only `baseline`, `canary`, or `full`. The existing public verifier retains its established checks and imports the exact active inventory from the focused verifier contract. Canary checks all six deferred targets as default-template controls.

- [ ] **Step 4: Commit activation routing**

```powershell
git add -- wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php wordpress/wp-content/themes/vietnamguide-premium/inc/guide-comparison.php ops/verify-guide-experience-live.php ops/verify-guide-experience-public.ps1 ops/verify-comparison-rollout-public.ps1 ops/verify-comparison-rollout.ps1
git commit -m "feat: gate comparison rollout by exact stage"
```

### Task 9: Guarded Updater, Pinned Source Checks, Backup, Ledger, and Rollback

**Files:**
- Modify: `ops/comparison-rollout-lib.php`
- Create: `ops/apply-comparison-rollout.php`
- Create: `ops/verify-comparison-rollout-live.php`
- Modify: `ops/verify-comparison-rollout.ps1`

- [ ] **Step 1: Write RED source-probe and updater fixtures**

Cover credentials, nonstandard ports, loopback/private/link-local/multicast/reserved/cloud-metadata IPs, mixed public/private DNS answers, redirect downgrade, fourth redirect, cross-publisher redirect, oversized response, timeout, re-resolution, wrong connected peer, wrong media type/title/date/locator, concurrent lock, expired lock stealing, incomplete ledger, partial write, post-write failure, repeated apply, repeated compatibility sync, and stage-exact rollback.

- [ ] **Step 2: Implement the pinned cURL probe**

Resolve A/AAAA before every hop; reject the hop unless every address is public. Select one approved address and pin it with `CURLOPT_RESOLVE` while retaining the URL hostname for Host and TLS SNI. Disable automatic redirects; inspect at most three manually. Verify `CURLINFO_PRIMARY_IP` belongs to the approved set. Use connect timeout 5 seconds, total timeout 12 seconds, header cap 64 KB, body cap 1 MB, and no response-body persistence.

- [ ] **Step 3: Implement source drift checks**

Return final publisher/domain, media type, normalized document title, declared document date, evidence-locator presence, ETag, Last-Modified, resolved IPs, peer IP, and response-body SHA-256. Publisher, media type, title pattern, document date, or locator mismatch produces `E_SOURCE_DRIFT`; ETag/Last-Modified changes produce an advisory reinspection event.

- [ ] **Step 4: Implement lock, ledger, and backup primitives**

Require absolute `VG_COMPARISON_STATE_DIR` outside ABSPATH/document root. Use `lock.json` with run ID, operator, created/expiry timestamps, PID, host, and stage. An expired lock blocks until `recovery-audit` closes or removes it with an appended recovery event. Write JSON Lines ledger events with hash chaining: each event contains `previous_event_hash` and `event_hash`. Backups contain raw post content, modified timestamps where supported, every in-scope meta value, per-record hashes, complete stage hash, and SHA-256 sidecar.

- [ ] **Step 5: Implement exact modes**

The CLI shape is:

```bash
wp eval-file ops/apply-comparison-rollout.php -- validate --stage=canary --artifact=/var/lib/vietnamguide/comparison-rollout/artifact.json --approvals=/var/lib/vietnamguide/comparison-rollout/approvals --run-id=00000000-0000-4000-8000-000000000001
```

The first positional value is one of `validate`, `dry-run`, `apply`, `activate`, `compatibility-sync`, `rollback`, or `recovery-audit`; `--stage` is one of `baseline`, `canary`, or `full`. `validate` and `dry-run` never write. `apply` backs up, verifies stage-aware fingerprints, writes the pending bundle plus marker-aware modules, and verifies stored state while paths remain inactive. `activate` atomically replaces `active-paths.json` in the state directory only after all stage pages are ready. `compatibility-sync` derives the 16 compatibility fields from the authoritative bundle and proves structured module byte equivalence. `rollback` first disables the exact stage if active, purges caches, restores the stage backup, purges again, and verifies baseline hashes. Every mutating mode is idempotent.

- [ ] **Step 6: Implement marker-aware module insertion**

Require all nine existing related-route modules to precede any source/update module. Add source trail only to IDs `279`, `209`, `227`, and `104`; add update log to all nine. Refuse duplicates or out-of-order existing modules and never move an existing block. Inactive inserted shortcodes render no output.

- [ ] **Step 7: Commit updater infrastructure**

```powershell
git add -- ops/comparison-rollout-lib.php ops/apply-comparison-rollout.php ops/verify-comparison-rollout-live.php ops/verify-comparison-rollout.ps1
git commit -m "feat: guard comparison rollout operations"
```

### Task 10: Curate the Three Canary Manifests

**Files:**
- Create: `ops/comparison-rollout/organizations.json`
- Create: `ops/comparison-rollout/sources.json`
- Create: `ops/comparison-rollout/manifest.json`

- [ ] **Step 1: Curate Hanoi three-way neighborhood evidence**

Populate post `301` with eight sources in allocation `1/2/2/1/2`, explicit evidence for all three options on every decisive axis, 3-5 organizations per experience-fit decision, and a Vietnamese local/operational record. Use 6-8 routes split across all groups, including these published candidates where relevant: `/destinations/hanoi-travel-guide/`, `/destinations/where-to-stay-in-hanoi/`, `/itineraries/hanoi-in-2-days/`, `/plan/hanoi-airport-to-old-quarter/`, `/plan/transport-within-vietnam/`, and `/plan/vietnam-travel-guide/`.

- [ ] **Step 2: Curate Ninh Binh time-allocation evidence**

Populate post `326` with eight sources in allocation `1/2/2/2/1`, option-balanced day-trip/overnight edges, crowd/timing and cost evidence, and a sequence route when the outcome combines both. Use published route candidates `/destinations/ninh-binh-travel-guide/`, `/destinations/tam-coc-travel-guide/`, `/plan/hanoi-to-ninh-binh-transport/`, `/plan/ninh-binh-to-ha-long-bay-transfer/`, `/itineraries/10-days-in-vietnam/`, `/costs/vietnam-travel-cost/`, and `/compare/trang-an-vs-tam-coc/`.

- [ ] **Step 3: Curate macro-region evidence**

Populate post `104` with eight sources in allocation `2/2/2/1/1`, explicit source-option edges for north/central/south, all five axes, no option below 25 percent, and evidence for all three macro-regions. Use published route candidates `/plan/best-time-to-visit-vietnam/`, `/plan/transport-within-vietnam/`, `/destinations/best-places-to-visit-vietnam/`, `/itineraries/10-days-in-vietnam/`, `/itineraries/14-days-in-vietnam/`, `/itineraries/21-days-in-vietnam/`, and `/costs/vietnam-travel-cost/`.

- [ ] **Step 4: Run canary-only portfolio checks**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout.ps1 -Scope canary -AsOfDate '2026-08-03T00:00:00Z' -Json
```

Expected: all three canary page-level evidence, graph, rule, route, text-bound, and module gates pass.

- [ ] **Step 5: Commit canary editorial data**

```powershell
git add -- ops/comparison-rollout/organizations.json ops/comparison-rollout/sources.json ops/comparison-rollout/manifest.json
git commit -m "content: curate comparison canary evidence"
```

### Task 11: Curate Central and South-Central Coast Manifests

**Files:**
- Modify: `ops/comparison-rollout/organizations.json`
- Modify: `ops/comparison-rollout/sources.json`
- Modify: `ops/comparison-rollout/manifest.json`

- [ ] **Step 1: Curate Da Nang versus Hoi An**

Populate post `209` with eight sources in allocation `1/2/2/1/2`, source-option balance, airport/transfer evidence, heritage and evening-fit corroboration, and routes spanning `/destinations/da-nang-travel-guide/`, `/destinations/best-things-to-do-in-hoi-an/`, `/destinations/cham-islands-travel-guide/`, `/itineraries/14-days-in-vietnam/`, `/plan/transport-within-vietnam/`, and `/plan/best-time-to-visit-vietnam/`.

- [ ] **Step 2: Curate Hoi An versus Hue**

Populate post `227` with eight sources in allocation `1/2/2/2/1`, symmetric heritage/transport/timing evidence and routes spanning `/destinations/best-things-to-do-in-hoi-an/`, `/destinations/best-things-to-do-in-hue/`, `/destinations/unesco-heritage-sites-vietnam/`, `/itineraries/14-days-in-vietnam/`, `/plan/transport-within-vietnam/`, and `/costs/vietnam-travel-cost/`.

- [ ] **Step 3: Curate Mui Ne versus Nha Trang**

Populate post `247` with eight sources in allocation `1/2/2/2/1`, current weather/water-condition evidence, balanced transfer and experience-fit edges, and routes spanning `/destinations/nha-trang-travel-guide/`, `/destinations/best-beaches-in-vietnam/`, `/destinations/quy-nhon-travel-guide/`, `/itineraries/14-days-in-vietnam/`, `/plan/best-time-to-visit-vietnam/`, and `/plan/transport-within-vietnam/`.

- [ ] **Step 4: Run six-page checks and commit**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout.ps1 -Scope six -AsOfDate '2026-08-03T00:00:00Z'
```

Expected: all six curated pages pass page-level rules.

```powershell
git add -- ops/comparison-rollout/organizations.json ops/comparison-rollout/sources.json ops/comparison-rollout/manifest.json
git commit -m "content: curate central comparison evidence"
```

### Task 12: Curate Southern, Island, and Ninh Binh Attraction Manifests

**Files:**
- Modify: `ops/comparison-rollout/organizations.json`
- Modify: `ops/comparison-rollout/sources.json`
- Modify: `ops/comparison-rollout/manifest.json`

- [ ] **Step 1: Curate Cu Chi versus Mekong Delta**

Populate post `279` with eight sources in allocation `1/2/2/1/2`, separate access/timing/experience evidence for each day trip, mobility and weather constraints, and routes spanning `/destinations/ho-chi-minh-city-travel-guide/`, `/destinations/mekong-delta-travel-guide/`, `/destinations/best-day-trips-from-ho-chi-minh-city/`, `/itineraries/10-days-in-vietnam/`, `/plan/transport-within-vietnam/`, and `/plan/health-travel-insurance-vietnam/`.

- [ ] **Step 2: Curate Phu Quoc versus Nha Trang**

Populate post `241` with eight sources in allocation `1/2/2/2/1`, island/city-beach transfer and weather evidence, option-balanced water-condition edges, and routes spanning `/destinations/phu-quoc-travel-guide/`, `/destinations/nha-trang-travel-guide/`, `/destinations/best-islands-in-vietnam/`, `/destinations/best-beaches-in-vietnam/`, `/itineraries/14-days-in-vietnam/`, `/plan/best-time-to-visit-vietnam/`, and `/costs/vietnam-travel-cost/`.

- [ ] **Step 3: Curate Trang An versus Tam Coc**

Populate post `336` with eight sources in allocation `1/2/2/2/1`, explicit access/scenery/crowd/activity/weather evidence for both attractions, and routes spanning `/destinations/ninh-binh-travel-guide/`, `/destinations/tam-coc-travel-guide/`, `/compare/ninh-binh-day-trip-vs-overnight/`, `/plan/hanoi-to-ninh-binh-transport/`, `/itineraries/10-days-in-vietnam/`, and `/plan/best-time-to-visit-vietnam/`.

- [ ] **Step 4: Pass the complete portfolio gate**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout.ps1 -AsOfDate '2026-08-03T00:00:00Z'
```

Expected: `Comparison rollout static verification passed: 9 pages, 72 assignments, 3 canaries, portfolio diversity approved.`

- [ ] **Step 5: Commit final editorial data**

```powershell
git add -- ops/comparison-rollout/organizations.json ops/comparison-rollout/sources.json ops/comparison-rollout/manifest.json
git commit -m "content: complete comparison evidence portfolio"
```

### Task 13: Permanent Mutation, Security, Accessibility, and Performance Suite

**Files:**
- Create: `ops/verify-comparison-rollout-mutations.ps1`
- Modify: `ops/verify-comparison-rollout.ps1`
- Modify: `ops/verify-comparison-rollout-live.php`
- Modify: `ops/verify-comparison-rollout-public.ps1`

- [ ] **Step 1: Implement isolated mutations for every approved design category**

Copy only required contract files into a validated GUID temp directory. Each mutation changes one exact value and must produce a nonzero verifier exit with the intended code. Include every permanent mutation listed in Revision 3: routing, class/diversity/freshness, option-edge balance, axis symmetry, negative evidence, graph orphan/cycle, drift, ID/domain/organization identity, SSRF/pinning/redirect, lens/rule order/outcomes, routes/modules/content boundaries, schema/UTF-8/escaping, bundle/cache identity, queries/render time/CSS/HTML/DOM, staged no-op/activation order, lock/ledger/helper, and approval failures.

The production artifact contains no HTTP deployment helper. The helper mutations inject a fixture PHP file into the copied artifact and prove the verifier rejects GET writes, tokens older than ten minutes, reused tokens, missing rate limits, and any path set not identical to the declared stage.

- [ ] **Step 2: Lock exact mutation inventory**

Declare `$ExpectedMutationCount` from the final reviewed array and fail when the count or names change. Require unique names and one exact replacement per mutation. Baseline copied contracts must pass before the first mutation.

- [ ] **Step 3: Implement runtime performance fixtures**

On WordPress, warm metadata cache, record `$wpdb->num_queries`, render 100 iterations of the largest reviewed bundle, sort elapsed milliseconds, and calculate p95 at index `Ceiling(100 * 0.95) - 1`. Require warm query delta `0`, cold query delta at most `1`, p95 at most `8.0`, bundle at most `65,536` bytes, frame at most 20 KB/180 nodes, and no runtime network call.

- [ ] **Step 4: Implement public accessibility/growth checks**

Compare pre-rollout baseline metrics from Task 1 with active HTML. Require one H1, decision/source/route/update semantics, valid fragments, descriptive links, `lang="vi"`, no target blank, focusable table wrappers, no overflow marker, no fatal text, HTML growth at most 20,480 bytes, DOM growth at most 180 nodes, and no new asset/font/image/script request.

- [ ] **Step 5: Run and commit the suite**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout-mutations.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout-public.ps1 -Stage baseline -FixturesOnly
```

Expected: all static checks pass; every declared mutation is rejected; public fixtures pass.

```powershell
git add -- ops/verify-comparison-rollout-mutations.ps1 ops/verify-comparison-rollout.ps1 ops/verify-comparison-rollout-live.php ops/verify-comparison-rollout-public.ps1
git commit -m "test: harden comparison rollout verification"
```

### Task 14: Deterministic Artifact and Full Local Integration

**Files:**
- Create: `ops/build-comparison-rollout-artifact.ps1`
- Create: `ops/comparison-rollout/artifact.json`
- Modify: `ops/verify-guide-experience.ps1`
- Modify: `ops/verify-guide-experience-mutations.ps1`
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Build the resolved artifact twice**

`build-comparison-rollout-artifact.ps1` must run the static verifier, emit canonical `artifact.json`, write a payload manifest containing relative path/size/SHA-256/mode, and create a Git-archive ZIP from the exact committed paths. It must refuse a dirty tracked worktree.

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\build-comparison-rollout-artifact.ps1 -OutputDirectory .\build\comparison-a
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\build-comparison-rollout-artifact.ps1 -OutputDirectory .\build\comparison-b
```

Expected: both `artifact.json`, payload manifests, and ZIP SHA-256 values are byte-identical.

- [ ] **Step 2: Run every local regression**

Before running the suite, connect `verify-guide-experience.ps1` to the focused comparison verifier and require the exact new files, target arrays, strict schemas, current/previous bundle constants, `comparison_decision_html`, and comparison-scoped CSS marker. Keep deep registry logic in the focused verifier.

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-block-patterns.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-mu-plugin.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-homepage-theme.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-mutations.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout-mutations.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-public.ps1 -FixturesOnly
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout-public.ps1 -Stage baseline -FixturesOnly
git diff --check
```

Expected: every command exits `0` and the existing core MU-plugin fingerprint remains unchanged.

- [ ] **Step 3: Commit deterministic artifact support**

```powershell
git add -- ops/build-comparison-rollout-artifact.ps1 ops/comparison-rollout/artifact.json ops/verify-guide-experience-mutations.ps1 ops/verification-log.md
git commit -m "build: package comparison rollout artifact"
```

### Task 15: Production Canary Data, Activation, and Ten-Minute Observation

**Files:**
- Read: `ops/comparison-rollout/artifact.json`
- Read: `ops/apply-comparison-rollout.php`
- Read: `ops/verify-comparison-rollout-live.php`
- Read: `ops/verify-comparison-rollout-public.ps1`
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Upload and verify code while stage remains baseline**

Set `VG_ARTIFACT_HASH` to the SHA-256 printed by Task 14 and upload the reviewed ZIP to `/usr/local/lsws/vietnamguide.net/html/wp-content/.vietnamguide-comparison-staging/$VG_ARTIFACT_HASH/`. Verify every payload hash, lint every PHP file, run `comparison-rollout-lib.php --self-test`, and run the WordPress live verifier. Shared code deployment must leave `active-paths.json` at baseline and public output unchanged.

- [ ] **Step 2: Generate exact server approval artifacts**

Set `VG_COMPARISON_STATE_DIR=/var/lib/vietnamguide/comparison-rollout`, `VG_COMPARISON_APPROVAL_KEY_ID=comparison-approval-2026-01`, and `VG_COMPARISON_APPROVAL_SECRET_2026_01` in the server environment. Generate required author/reviewer artifacts outside the web root, then validate identities, roles, HMACs, manifest hash, and change scopes. Do not print the secret or artifact payloads.

- [ ] **Step 3: Validate and dry-run canary**

```bash
cd /usr/local/lsws/vietnamguide.net/html
VG_CANARY_VALIDATE_RUN_ID=$(uuidgen)
VG_CANARY_DRY_RUN_ID=$(uuidgen)
wp eval-file ops/apply-comparison-rollout.php -- validate --stage=canary --artifact=/var/lib/vietnamguide/comparison-rollout/artifact.json --approvals=/var/lib/vietnamguide/comparison-rollout/approvals --run-id="$VG_CANARY_VALIDATE_RUN_ID"
wp eval-file ops/apply-comparison-rollout.php -- dry-run --stage=canary --artifact=/var/lib/vietnamguide/comparison-rollout/artifact.json --approvals=/var/lib/vietnamguide/comparison-rollout/approvals --run-id="$VG_CANARY_DRY_RUN_ID"
```

Expected: exact three-page scope, matching pre-fingerprints, all nine portfolio contracts, source probes, approvals, and zero writes.

- [ ] **Step 4: Apply pending data, verify stored state, then activate atomically**

Run `apply` for canary, verify the checksummed backup and stored pending bundle/modules while all three paths remain default, then run `activate`. Purge object/theme/LiteSpeed caches only after the complete stage passes. Warm each canary URL and its content-versioned CSS/JS once through normal public requests.

- [ ] **Step 5: Run complete canary QA**

Run static, live, public, desktop 1280x900, tablet 768x1024, mobile 390x844, keyboard, 200-percent zoom/320 CSS px, reduced-motion, forced-colors, source freshness/drift, content diff, query/render time, CLS and three-run median LCP checks. The three canaries must have one H1, decision frame, grouped source/route/update modules, no overflow/errors, CLS at most 0.10, and LCP regression at most 10 percent.

- [ ] **Step 6: Observe for at least ten minutes**

Perform three spaced uncached request rounds and inspect PHP, LiteSpeed/web-server, and WordPress logs for new warnings/fatals correlated to the three paths. Use query parameters `vg_canary_round=1`, `2`, and `3` plus `Cache-Control: no-cache`; do not rely on passive traffic.

- [ ] **Step 7: Compatibility sync and canary ledger close**

After observation passes, run `compatibility-sync --stage=canary`, prove byte-equivalent structured modules, remove temporary helpers, finalize the hash-chained ledger, release the lock, and retain backup/approval evidence. On any failure, disable the exact canary paths, purge, rollback only the three pages, purge/warm baseline, and verify baseline hashes before releasing the lock.

### Task 16: Stage 2 Rollout, Final Browser Matrix, Rollback Drill, and Local Merge

**Files:**
- Modify: `ops/verification-log.md`
- Modify: `docs/superpowers/plans/2026-08-03-vietnamguide-comparison-evidence-decision-rollout.md` (check completed boxes only after evidence exists)

- [ ] **Step 1: Apply and activate the six Stage 2 pages**

Repeat approval validation, stage-aware fingerprint checks, checksummed backup, pending data/module writes, stored-state verification, atomic activation, cache purge, and warming for IDs `279`, `209`, `227`, `247`, `241`, and `336`. Preserve the three verified canaries if Stage 2 fails.

- [ ] **Step 2: Run the full public and browser matrix**

Run all nine comparison pages at 1280x900 and 390x844 for 18 browser runs. Run the three canaries at 768x1024, reduced motion, and forced colors. Verify keyboard table access, visible focus, language labels, no overflow, no console/page errors, one H1, guide shell, trust rail, decision frame bounds, grouped modules, content-versioned assets, source dates, non-sensitive hashes, and unchanged article prose.

- [ ] **Step 3: Run final budgets and compatibility sync**

Require all 17 active pilots and four permanent controls to pass. Run `compatibility-sync --stage=full` twice and require the second run to report zero writes and byte-equivalent modules. Confirm 20 KB/180-node/6 KB, warm zero-query/cold one-query, PHP p95 8 ms, CLS 0.10, and LCP 10-percent gates.

- [ ] **Step 4: Execute a rollback drill without losing the verified state**

Use a copied stage backup and isolated activation/state directory to execute disable, restore, purge, baseline-hash verification, and ledger close. Reapply the reviewed full artifact in the isolated drill and prove the final hashes return exactly. Production remains on the verified full state.

- [ ] **Step 5: Append final evidence and run final review**

Record artifact/input/bundle/render hashes, exact 8/11/17 inventories, source/portfolio summaries, approvals, backup checksums, ledger paths, canary rounds/log checks, all browser results, performance results, compatibility sync, and rollback drill in `ops/verification-log.md`. Dispatch one final whole-change spec reviewer and one final code-quality/security reviewer; resolve every finding and rerun affected suites.

- [ ] **Step 6: Merge locally into master**

Use `superpowers:finishing-a-development-branch`. From `D:\Documents\vietnamguide`, verify the original master worktree still contains only the seven user-owned untracked paths, then merge without rewriting them:

```powershell
git checkout master
git merge --no-ff codex/comparison-evidence-decision-rollout -m "merge: comparison evidence decision rollout"
git status --short
git log -5 --oneline
```

Expected: merge succeeds locally on `master`; production evidence is committed; the seven user-owned untracked paths remain untouched.

## Mandatory Per-Task Review Loop

For Tasks 2-14, use one fresh implementer subagent per task. The implementer follows `superpowers:test-driven-development`, runs the task commands, commits only task files, and self-reviews. Then dispatch a fresh spec-compliance reviewer; any gap returns to the same implementer and is re-reviewed. Only after spec approval dispatch a fresh code-quality/security reviewer; any finding returns to the same implementer and is re-reviewed. Do not start the next task with an open finding.

For production Tasks 15-16, keep one operations implementer responsible for the live run ID and lock lifecycle, but use fresh read-only reviewers after canary and final-stage evidence. Never dispatch concurrent implementers against the same shared worktree or production stage.

## Plan Self-Review Checklist

- [ ] Every approved spec section maps to at least one task: routing, inventories, registries, evidence balance, claim provenance, freshness/drift, governance, qualitative paths, SSR rendering, runtime safety, route/locality diversity, insertion, canary, budgets, rollback, static/live/public/browser verification.
- [ ] Every new or modified file has one named responsibility and appears in a task with exact commands and expected results.
- [ ] Function names, schema keys, stage names, evidence labels, claim groups, rule kinds, route groups, approval roles, and CLI modes are consistent across PowerShell, PHP, JSON, theme, MU plugin, and production steps.
- [ ] No code or data step relies on an undefined function, hidden numeric score, runtime source fetch, per-source query, raw HTML field, WordPress salt, prefix activation, or automatic recommendation rewrite.
- [ ] Source and route curation covers all nine pages, every compared option, all required geographic groups, three guide types, three route groups, and the portfolio minimums.
- [ ] Failure paths are explicit before writes, during partial apply, after activation, during compatibility sync, and during rollback.
- [ ] Production evidence proves data-before-routing, atomic exact-path activation, three canary rounds over at least ten minutes, cache purge/warm, stage-exact rollback, and final 18-run browser coverage.

# VietnamGuide Comparison Recovery Execution Addendum

Status: Current recovery and production-execution authority as of 2026-08-03.

## Authority and Scope

This addendum supersedes only stale environment, repository-location, branch, staging-directory, production-command, and rollback-drill instructions in `2026-08-03-vietnamguide-comparison-evidence-decision-rollout.md`. Revision 3 remains authoritative for product scope, data contracts, exact target ordering, the 8 -> 11 -> 17 activation model, evidence rules, render behavior, and acceptance budgets.

The only approved local repository for this work is:

```text
C:\Users\NCTaam\projects\vietnamguide
```

The recovery branch is `codex/recovery-production-baseline`. Its required lineage is:

```text
c8ecf3e174e6474ccfa2e76e9f5da8ee83ad6341  recovery production baseline
8dbbfd58073ae7ab9727c57df158dbe1f0c58695  mechanically restored Revision 3 documents
```

This addendum must be committed as a descendant of that lineage. Feature Task 2 may not start until the prerequisite recovery task below is committed, reviewed, and recorded as `RECOVERY_BASE_SHA`.

There is currently no local `master` branch. Do not create, switch to, or assume `master` during recovery or feature implementation. The requested eventual local merge remains required, but it occurs only through the finishing workflow after every recovery, implementation, production-evidence, and review gate passes.

## Task 0: Recover the Missing Verifier Stack

Run the recovery baseline first from Windows PowerShell:

```powershell
$RepoRoot = 'C:\Users\NCTaam\projects\vietnamguide'
$SnapshotRoot = 'C:\Users\NCTaam\projects\vietnamguide-production-snapshot-20260803'
Set-Location -LiteralPath $RepoRoot

powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\tests\verify-recovery-baseline.ps1 `
    -SnapshotRoot $SnapshotRoot `
    -RepositoryRoot $RepoRoot
if ($LASTEXITCODE -ne 0) { throw 'Recovery baseline failed.' }
```

The recovery audit identified these ten required local verifier files as missing from the production snapshot baseline:

```text
ops/verify-core-block-patterns.ps1
ops/verify-core-mu-plugin.ps1
ops/verify-core-mu-plugin-live.php
ops/verify-homepage-theme.ps1
ops/verify-guide-experience.ps1
ops/verify-guide-experience-mutations.ps1
ops/verify-guide-experience-public.ps1
ops/verify-guide-experience-live.php
ops/verify-guide-experience-tokenizer.php
ops/verify-guide-experience-js-runtime.js
```

Mechanically restore the seven independently preserved files from the approved local C-volume recovery sources. Reconstruct `verify-guide-experience.ps1`, `verify-guide-experience-mutations.ps1`, and `verify-guide-experience-tokenizer.php` from the approved pre-final mutation baseline by replaying only the complete target-specific successful forward patch suffix in chronological order, with exact hunk matching and no fuzz. The successful JSONL line suffixes are:

- `verify-guide-experience.ps1`: `1508, 1906, 2171, 2274, 2280, 2336, 2557, 2770, 2960, 3077, 3220, 3321, 3332, 3375, 3439, 3455`.
- `verify-guide-experience-mutations.ps1`: `1508, 1906, 2171, 2417, 2960, 3321`.
- `verify-guide-experience-tokenizer.php`: `2336, 2343, 2542, 2557, 2568, 2783, 3115`.

Pin the reconstruction audit to Codex thread `019fc2f2-d8b4-76b3-aa4a-1a2c63723fd8` and source artifact `C:\Users\NCTaam\.codex\sessions\2026\08\02\rollout-2026-08-02T21-48-42-019fc2f2-d8b4-76b3-aa4a-1a2c63723fd8.jsonl`: 4,208,602 bytes, 3,482 lines, SHA-256 `9f16b798e55f22cd199ee1c74fd596c7852e303c88880445568bb461fd5fc349`. The approved pre-final baseline root is `C:\Users\NCTaam\AppData\Local\Temp\vietnamguide-guide-mutations-6ea602bc81b84e0cb3dc8bb46f3dc441\baseline\ops`, with these exact identities:

| Baseline file | Raw bytes | Raw SHA-256 |
| --- | ---: | --- |
| `verify-guide-experience.ps1` | 111,269 | `736d9681b537de11daed170cdcec2f5cdffaf83d2408fcbf6bb1e3babd704b82` |
| `verify-guide-experience-mutations.ps1` | 39,407 | `f92b80ea23af1c97f753305ceddfad58540be9ea0a8c15bfb55b28075729463f` |
| `verify-guide-experience-tokenizer.php` | 21,263 | `a6446ded677c4cbdaa501f922c8cb8482485c01e69443e11cdbecd90210c34ba` |

The JSONL audit proves 54 successful, untruncated target changes and zero failed target changes: 24 static-verifier changes, 16 mutation-verifier changes, and 14 tokenizer changes. The approved baselines already contain the first 8, 10, and 7 successful changes. Their last skipped records are JSONL line 1151 at `02:16:46Z`, line 1181 at `02:18:26Z`, and line 1124 at `02:15:19Z`; the independent replay suffixes begin at line 1508 at `02:56:36Z`, line 1508 at `02:56:36Z`, and line 2336 at `04:19:08Z`. Therefore the line sets above replay exactly 16, 6, and 7 successful changes.

Treat the JSONL and temporary baseline locations as original provenance only. The durable, deterministic source for the original restored stack is commit `4ca75ab309f48124ef5d7383dceefc51d50aa85b` and its exact Git blobs; future recovery must use `git show 4ca75ab309f48124ef5d7383dceefc51d50aa85b:<path>`, then take the reviewed core-MU line-ending portability change from descendant commit `21b59a3711dfab80f125aa7b66b197b6a5561312`. It must not depend on the temporary directory surviving.

Never reconstruct file contents from memory. Pin the exact path set, raw byte lengths, and lowercase SHA-256 values in `tests/fixtures/recovery-local-ops-manifest.json`; only a complete validated ten-entry set may be excluded from the production-snapshot `ops` digest or admitted as local extras and approved Git/index targets.

| Path | Raw bytes | Raw SHA-256 |
| --- | ---: | --- |
| `ops/verify-core-block-patterns.ps1` | 14,206 | `30f4be5818b15e4cd8c3ad9b616aeddebd77ff97aa4922a3c89dfaaa35b23c85` |
| `ops/verify-core-mu-plugin.ps1` | 30,539 | `1334d35e895a8eac7d5122b482a188f19072ae5b636c46ce44f6257fd1a1419d` |
| `ops/verify-core-mu-plugin-live.php` | 9,743 | `280424139dc8b29ba2911d7d3caf1a203499c742efd6d6a243b0d98a7dc8e842` |
| `ops/verify-homepage-theme.ps1` | 30,042 | `0c58f228806da1d121bce622d991f738f3d4552c4bf9dbbb3a8640dbb474558b` |
| `ops/verify-guide-experience.ps1` | 121,518 | `60f2446f513dae8ff9ea84b6a90823bb8ab35b30dd3699602ca65d41f95b21ec` |
| `ops/verify-guide-experience-mutations.ps1` | 44,511 | `8bb44777a33bf89478e54189f7377155b4e17eba7de71f60fae27c88563d6108` |
| `ops/verify-guide-experience-public.ps1` | 55,723 | `03b018f463abe474d06b7006a5cdc952d757cd26825d0a1c9700f20f24873543` |
| `ops/verify-guide-experience-live.php` | 26,254 | `33bf8f3791bd6b8e5cc6aff1a7cc2dbfa8a7df8df9d639ba262f431879f30643` |
| `ops/verify-guide-experience-tokenizer.php` | 25,434 | `9b65e83952e5d82dc835d6c4d56e6f93c396d9334e877117f7def4bbc99c25f4` |
| `ops/verify-guide-experience-js-runtime.js` | 3,703 | `031feabe4d0934a13085f9066e42088c941d5dd60be0e649b2b89e2c67eeeb4b` |

`docs/RECOVERY.md` records the raw no-filter Git blobs and canonical-LF SHA-256/blob provenance for the three replayed files.

Before committing the recovered verifier stack:

```powershell
$RepoRoot = 'C:\Users\NCTaam\projects\vietnamguide'
Set-Location -LiteralPath $RepoRoot

$PowerShellFiles = @(
    'ops/verify-core-block-patterns.ps1',
    'ops/verify-core-mu-plugin.ps1',
    'ops/verify-homepage-theme.ps1',
    'ops/verify-guide-experience.ps1',
    'ops/verify-guide-experience-mutations.ps1',
    'ops/verify-guide-experience-public.ps1'
)
foreach ($File in $PowerShellFiles) {
    $Tokens = $null
    $Errors = $null
    [System.Management.Automation.Language.Parser]::ParseFile(
        (Join-Path $RepoRoot $File),
        [ref]$Tokens,
        [ref]$Errors
    ) | Out-Null
    if ($Errors.Count -ne 0) { throw "PowerShell parse failed: $File" }
}

$PhpExecutable = 'php'
foreach ($File in @(
    'ops/verify-core-mu-plugin-live.php',
    'ops/verify-guide-experience-live.php',
    'ops/verify-guide-experience-tokenizer.php'
)) {
    & $PhpExecutable -l (Join-Path $RepoRoot $File)
    if ($LASTEXITCODE -ne 0) { throw "PHP lint failed: $File" }
}

node --check .\ops\verify-guide-experience-js-runtime.js
if ($LASTEXITCODE -ne 0) { throw 'Node syntax check failed: ops/verify-guide-experience-js-runtime.js' }

powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-block-patterns.ps1
if ($LASTEXITCODE -ne 0) { throw 'Core block-pattern verification failed.' }
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\tests\verify-core-mu-plugin-portability.ps1
if ($LASTEXITCODE -ne 0) { throw 'Core MU-plugin LF/CRLF portability verification failed.' }
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-mu-plugin.ps1
if ($LASTEXITCODE -ne 0) { throw 'Core MU-plugin verification failed.' }
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-homepage-theme.ps1
if ($LASTEXITCODE -ne 0) { throw 'Homepage-theme verification failed.' }
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1 -PhpExecutable $PhpExecutable
if ($LASTEXITCODE -ne 0) { throw 'Guide Experience verification failed.' }
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-mutations.ps1 -PhpExecutable $PhpExecutable
if ($LASTEXITCODE -ne 0) { throw 'Guide Experience mutation verification failed.' }
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-public.ps1 -FixturesOnly
if ($LASTEXITCODE -ne 0) { throw 'Guide Experience public fixture verification failed.' }
```

The byte-exact recovery has two historical applicability findings that must remain fail-fast and visible:

- Exact recovery commit `4ca75ab309f48124ef5d7383dceefc51d50aa85b` preserves the original core MU verifier blob and its line-ending-sensitive RED. The follow-up TDD hardening changes only the multiline affiliate-loop fixture replacement to follow either LF or CRLF source content; the production plugin and source manifest remain unchanged.
- The exact homepage verifier passes against the approved later salvage homepage state. It requires two local image-build scripts, a QA preview, and Ha Long Bay credit metadata that are outside the recovered production baseline. Do not expand the ten-file local-ops contract or change the production-pinned theme README to make this historical verifier applicable. Independent review must decide the future homepage verification contract before Task 2.

The restored mutation harness must preserve the original inventory of exactly 111 unique named mutations and report all 111 rejected for their intended reasons. Run the recovery baseline again, run `git diff --check`, verify the recovered files against the Git index with `git hash-object --no-filters`, obtain independent spec and quality reviews, and commit only the recovered verifier stack. Record that reviewed commit as:

```powershell
$RecoveryBaseSha = (git rev-parse HEAD).Trim()
```

Feature Task 2 starts only from this recorded recovery base.

## Local Build and Release Preparation

These commands run on the Windows workstation in PowerShell. They do not access production until the explicit upload step.

```powershell
$RepoRoot = 'C:\Users\NCTaam\projects\vietnamguide'
$RecoveryBaseSha = '<verified-recovery-base-sha>'
$BuildRoot = Join-Path $RepoRoot 'build\comparison-rollout-release'
$ArtifactZip = Join-Path $BuildRoot 'comparison-rollout.zip'
$PayloadManifest = Join-Path $BuildRoot 'payload-manifest.json'

Set-Location -LiteralPath $RepoRoot
git merge-base --is-ancestor $RecoveryBaseSha HEAD
if ($LASTEXITCODE -ne 0) { throw 'HEAD does not descend from the verified recovery base.' }
if (-not [string]::IsNullOrWhiteSpace((git status --porcelain | Out-String))) {
    throw 'Tracked worktree must be clean before building.'
}

powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout.ps1
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout-mutations.ps1
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-mutations.ps1
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\build-comparison-rollout-artifact.ps1 -OutputDirectory $BuildRoot

if (-not (Test-Path -LiteralPath $ArtifactZip -PathType Leaf)) { throw 'Artifact ZIP missing.' }
if (-not (Test-Path -LiteralPath $PayloadManifest -PathType Leaf)) { throw 'Payload manifest missing.' }
$ArtifactHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $ArtifactZip).Hash.ToLowerInvariant()
if ($ArtifactHash -notmatch '^[0-9a-f]{64}$') { throw 'Artifact SHA-256 is invalid.' }
$ArtifactHash
```

Build twice in separate output directories and require byte-identical artifact, payload-manifest, and resolved-artifact hashes before upload.

## Artifact Upload

Run this block on the Windows workstation in PowerShell. Replace only the bracketed host/user placeholders and use the reviewed `$ArtifactHash` from the build output.

```powershell
$ProdHost = '<approved-production-host>'
$ProdUser = '<approved-production-user>'
$ArtifactHash = '<lowercase-artifact-sha256>'
$ArtifactZip = 'C:\Users\NCTaam\projects\vietnamguide\build\comparison-rollout-release\comparison-rollout.zip'
$RemoteIncomingDir = '/var/lib/vietnamguide/comparison-rollout/incoming'
$RemotePart = "$RemoteIncomingDir/$ArtifactHash.zip.part"
$RemoteArchive = "$RemoteIncomingDir/$ArtifactHash.zip"

ssh "$ProdUser@$ProdHost" "sudo install -d -o '$ProdUser' -g '$ProdUser' -m 0750 '$RemoteIncomingDir'"
if ($LASTEXITCODE -ne 0) { throw 'Unable to prepare remote incoming directory.' }
scp -- $ArtifactZip "${ProdUser}@${ProdHost}:$RemotePart"
if ($LASTEXITCODE -ne 0) { throw 'Artifact upload failed.' }
ssh "$ProdUser@$ProdHost" "sudo chown root:root '$RemotePart' && sudo chmod 0640 '$RemotePart' && sudo mv -- '$RemotePart' '$RemoteArchive'"
if ($LASTEXITCODE -ne 0) { throw 'Unable to finalize uploaded artifact.' }
```

The release is staged only under `/var/lib/vietnamguide/comparison-rollout`. No release or incoming archive is staged below the WordPress document root.

## Production Shell Initialization

Open one approved production session and become root. All following Linux blocks use Bash and the WordPress root shown here.

```powershell
$ProdHost = '<approved-production-host>'
$ProdUser = '<approved-production-user>'
ssh -t "$ProdUser@$ProdHost" 'sudo -i'
```

```bash
set -euo pipefail
set +x
umask 077

WP_ROOT='/usr/local/lsws/vietnamguide.net/html'
STATE_DIR='/var/lib/vietnamguide/comparison-rollout'
RELEASE_ROOT="$STATE_DIR/releases"
INCOMING_ROOT="$STATE_DIR/incoming"
APPROVAL_DIR="$STATE_DIR/approvals"
ENV_FILE='/etc/vietnamguide/comparison-rollout.env'
SITE_ORIGIN='https://vietnamguide.net'
VG_ARTIFACT_HASH='<lowercase-artifact-sha256>'
UPLOAD="$INCOMING_ROOT/$VG_ARTIFACT_HASH.zip"
RELEASE_DIR="$RELEASE_ROOT/$VG_ARTIFACT_HASH"

install -d -o root -g root -m 0750 "$STATE_DIR" "$RELEASE_ROOT" "$APPROVAL_DIR"
test -f "$UPLOAD"
printf '%s  %s\n' "$VG_ARTIFACT_HASH" "$UPLOAD" | sha256sum --check --strict -
test -f "$ENV_FILE"
test "$(stat -c '%U:%G:%a' "$ENV_FILE")" = 'root:root:600'

set -a
. "$ENV_FILE"
set +a
test -n "${VG_COMPARISON_APPROVAL_KEY_ID:-}"
test -n "${VG_COMPARISON_APPROVAL_SECRET_2026_01:-}"
```

Provision `/etc/vietnamguide/comparison-rollout.env` through the approved secret manager or an interactive root editor. Never place secret values in a command argument, terminal transcript, shell history, release archive, ledger, public file, or repository. Its shape is:

```bash
VG_COMPARISON_APPROVAL_KEY_ID='<approved-key-id>'
VG_COMPARISON_APPROVAL_SECRET_2026_01='<value-from-approved-secret-store>'
VG_COMPARISON_CACHE_NAMESPACE='<approved-production-cache-namespace>'
```

Keep shell tracing disabled while the environment is loaded and while approval commands run.

## Verify and Atomically Install the Release

Create one mutation run ID before the first installation or WordPress write. This ID owns the lock, backup, ledger, apply, activation, observation, compatibility sync, failure recovery, and final close for the canary stage.

```bash
VG_RUN_ID="$(uuidgen)"
INSTALL_ROOT="$RELEASE_ROOT/.install-$VG_ARTIFACT_HASH-$VG_RUN_ID"
test ! -e "$INSTALL_ROOT"
test ! -e "$RELEASE_DIR"
mkdir -m 0750 "$INSTALL_ROOT"
unzip -q "$UPLOAD" -d "$INSTALL_ROOT"

php "$INSTALL_ROOT/ops/install-comparison-rollout-release.php" verify-payload \
  --release-root="$INSTALL_ROOT" \
  --payload="$INSTALL_ROOT/payload-manifest.json" \
  --archive-sha256="$VG_ARTIFACT_HASH"

while IFS= read -r -d '' file; do
  php -l "$file"
done < <(find "$INSTALL_ROOT" -type f -name '*.php' -print0)

php "$INSTALL_ROOT/ops/install-comparison-rollout-release.php" self-test \
  --release-root="$INSTALL_ROOT" \
  --assert-cli-only
php "$INSTALL_ROOT/ops/comparison-rollout-lib.php" --self-test

mv -- "$INSTALL_ROOT" "$RELEASE_DIR"
cd "$RELEASE_DIR"

php "$RELEASE_DIR/ops/install-comparison-rollout-release.php" install \
  --release-root="$RELEASE_DIR" \
  --wordpress-root="$WP_ROOT" \
  --state-dir="$STATE_DIR" \
  --run-id="$VG_RUN_ID"

jq -e '.stage == "baseline" and (.paths | length) == 8' "$STATE_DIR/active-paths.json"
```

`install-comparison-rollout-release.php`, `comparison-rollout-lib.php`, `apply-comparison-rollout.php`, and every operational PHP entry point must check `PHP_SAPI === 'cli'` before reading arguments or changing state. Non-CLI execution must exit nonzero without emitting secrets, writing files, acquiring a lock, or contacting WordPress.

The installer must verify the payload manifest, write only reviewed destinations, create sibling temporary files on the destination filesystem, preserve owner/mode, and use atomic rename. If any installation step fails, it restores every replaced file from the checksummed installation backup before releasing the lock.

## Command Wrapper and Run-ID Rules

Define the wrapper once in the same root Bash session:

```bash
ARTIFACT="$RELEASE_DIR/ops/comparison-rollout/artifact.json"

run_rollout() {
  local mode="$1"
  local stage="$2"
  shift 2
  wp --path="$WP_ROOT" --allow-root eval-file \
    "$RELEASE_DIR/ops/apply-comparison-rollout.php" -- \
    "$mode" \
    --stage="$stage" \
    --artifact="$ARTIFACT" \
    --approvals="$APPROVAL_DIR" \
    --run-id="$VG_RUN_ID" \
    --state-dir="$STATE_DIR" \
    "$@"
}

verify_rollout() {
  local phase="$1"
  local stage="$2"
  wp --path="$WP_ROOT" --allow-root eval-file \
    "$RELEASE_DIR/ops/verify-comparison-rollout-live.php" -- \
    "$phase" \
    --stage="$stage" \
    --artifact="$ARTIFACT" \
    --run-id="$VG_RUN_ID" \
    --state-dir="$STATE_DIR"
}
```

Use `$VG_RUN_ID` unchanged until the canary ledger is closed and its lock is released. Do not generate separate IDs for dry-run, apply, stored-state verification, activation, cache work, observation, compatibility sync, or rollback.

A new validation-only ID is allowed only before a mutation run begins, when no deployment lock is held and the command is proven zero-write. It must be labeled `validation-only`, may run only `validate` or fixture/static verification, and may never be reused or promoted into a mutation run. Stage 2 receives a new mutation ID only after the canary run is closed successfully.

The lock must contain the run ID, stage, operator, host, PID, creation time, expiry time, and release hash. Renew it before every long QA or observation interval and at least every five minutes. Only the owning run may renew or release it. An expired lock is never silently stolen; `recovery-audit` must append a recovery decision before continuation.

## Canary Validate, Dry-Run, Apply, and Activate

```bash
CANARY_PATHS=(
  'compare/old-quarter-vs-french-quarter-vs-west-lake'
  'compare/ninh-binh-day-trip-vs-overnight'
  'compare/north-central-south-vietnam'
)

run_rollout validate canary
run_rollout dry-run canary
run_rollout apply canary
verify_rollout stored-state canary

jq -e '.stage == "baseline" and (.paths | length) == 8' "$STATE_DIR/active-paths.json"
test -f "$STATE_DIR/backups/$VG_RUN_ID/backup.json"
test -f "$STATE_DIR/backups/$VG_RUN_ID/backup.json.sha256"
(cd "$STATE_DIR/backups/$VG_RUN_ID" && sha256sum --check --strict backup.json.sha256)

run_rollout activate canary
jq -e '.stage == "canary" and (.paths | length) == 11' "$STATE_DIR/active-paths.json"
verify_rollout active-state canary

wp --path="$WP_ROOT" --allow-root cache flush
wp --path="$WP_ROOT" --allow-root eval-file "$RELEASE_DIR/ops/purge-litespeed-cache.php"
for path in "${CANARY_PATHS[@]}"; do
  curl --fail --silent --show-error --location --max-time 20 \
    --output /dev/null "$SITE_ORIGIN/$path/?vg_warm=$VG_RUN_ID"
done
verify_rollout cache-warm canary
```

The `apply` command must leave public routing at the eight-path baseline until stored-state verification passes for all three canary pages. `activate` must replace the exact active-path file atomically. Any count other than 8 before activation or 11 after activation is a stop-and-rollback failure.

Run the public checks from the Windows workstation after activation:

```powershell
$RepoRoot = 'C:\Users\NCTaam\projects\vietnamguide'
Set-Location -LiteralPath $RepoRoot
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout-public.ps1 `
    -Stage canary `
    -Origin 'https://vietnamguide.net'
if ($LASTEXITCODE -ne 0) { throw 'Canary public verification failed.' }
```

## Canary Observation, Compatibility Sync, and Close

Renew the same run lock, then perform exactly three uncached rounds over at least ten minutes:

```bash
run_rollout recovery-audit canary --action=renew-lock --ttl-seconds=900
VG_OBSERVE_START="$(date --utc +%Y-%m-%dT%H:%M:%SZ)"

for round in 1 2 3; do
  for path in "${CANARY_PATHS[@]}"; do
    curl --fail --silent --show-error --location --max-time 20 \
      --header 'Cache-Control: no-cache' \
      --output /dev/null \
      "$SITE_ORIGIN/$path/?vg_canary_round=$round&run_id=$VG_RUN_ID"
  done
  verify_rollout observation-round canary
  if [ "$round" -lt 3 ]; then sleep 300; fi
  run_rollout recovery-audit canary --action=renew-lock --ttl-seconds=900
done

journalctl --since "$VG_OBSERVE_START" --unit=lsws --unit=php8.3-fpm --no-pager \
  > "$STATE_DIR/evidence/$VG_RUN_ID/service-observation.log"
test ! -f "$WP_ROOT/wp-content/debug.log" || \
  tail -n 5000 "$WP_ROOT/wp-content/debug.log" \
    > "$STATE_DIR/evidence/$VG_RUN_ID/wordpress-observation.log"
verify_rollout observation-complete canary

run_rollout compatibility-sync canary
verify_rollout compatibility-equivalence canary
run_rollout recovery-audit canary --action=close-ledger --require-final-event=compatibility-sync
test ! -e "$STATE_DIR/lock.json"
verify_rollout closed canary
```

Do not close the ledger or release the lock if a public, browser, performance, log, cache, compatibility, backup, or hash gate remains open.

## Canary Failure and Rollback

On any failure after `apply`, use the same run ID and exact canary stage:

```bash
set +e
run_rollout rollback canary
ROLLBACK_EXIT=$?
set -e

wp --path="$WP_ROOT" --allow-root cache flush
wp --path="$WP_ROOT" --allow-root eval-file "$RELEASE_DIR/ops/purge-litespeed-cache.php"
for path in "${CANARY_PATHS[@]}"; do
  curl --fail --silent --show-error --location --max-time 20 \
    --output /dev/null "$SITE_ORIGIN/$path/?vg_rollback_warm=$VG_RUN_ID"
done

verify_rollout baseline-hashes canary
jq -e '.stage == "baseline" and (.paths | length) == 8' "$STATE_DIR/active-paths.json"
run_rollout recovery-audit canary --action=close-ledger --require-final-event=rollback
test ! -e "$STATE_DIR/lock.json"
test "$ROLLBACK_EXIT" -eq 0
```

If rollback, cache purge, baseline-hash verification, or ledger close fails, keep the lock, stop all rollout commands, preserve the release and backup, and escalate for recovery audit. Never release the lock merely to clear an error.

## Stage 2 Validate, Apply, Activate, and Close

Stage 2 starts only after the canary run is closed and its evidence is reviewed. Generate one new mutation run ID for the six-page stage and use it for every Stage 2 step.

```bash
VG_RUN_ID="$(uuidgen)"
STAGE2_PATHS=(
  'compare/cu-chi-tunnels-vs-mekong-delta-day-trip'
  'compare/da-nang-vs-hoi-an'
  'compare/hoi-an-vs-hue'
  'compare/mui-ne-vs-nha-trang'
  'compare/phu-quoc-vs-nha-trang'
  'compare/trang-an-vs-tam-coc'
)

run_rollout validate full
run_rollout dry-run full
run_rollout apply full
verify_rollout stored-state full
jq -e '.stage == "canary" and (.paths | length) == 11' "$STATE_DIR/active-paths.json"

run_rollout activate full
jq -e '.stage == "full" and (.paths | length) == 17' "$STATE_DIR/active-paths.json"
verify_rollout active-state full

wp --path="$WP_ROOT" --allow-root cache flush
wp --path="$WP_ROOT" --allow-root eval-file "$RELEASE_DIR/ops/purge-litespeed-cache.php"
for path in "${CANARY_PATHS[@]}" "${STAGE2_PATHS[@]}"; do
  curl --fail --silent --show-error --location --max-time 20 \
    --output /dev/null "$SITE_ORIGIN/$path/?vg_full_warm=$VG_RUN_ID"
done
verify_rollout cache-warm full

run_rollout compatibility-sync full
run_rollout compatibility-sync full
verify_rollout compatibility-equivalence full
run_rollout recovery-audit full --action=close-ledger --require-final-event=compatibility-sync
test ! -e "$STATE_DIR/lock.json"
verify_rollout closed full
```

The second compatibility sync must report zero writes. Run the complete 18-run desktop/mobile browser matrix and all permanent controls before ledger close.

## Stage 2 Failure and Rollback

Stage 2 rollback must restore only the six Stage 2 pages and preserve the three verified canaries:

```bash
set +e
run_rollout rollback full
ROLLBACK_EXIT=$?
set -e

wp --path="$WP_ROOT" --allow-root cache flush
wp --path="$WP_ROOT" --allow-root eval-file "$RELEASE_DIR/ops/purge-litespeed-cache.php"
for path in "${CANARY_PATHS[@]}" "${STAGE2_PATHS[@]}"; do
  curl --fail --silent --show-error --location --max-time 20 \
    --output /dev/null "$SITE_ORIGIN/$path/?vg_stage2_rollback_warm=$VG_RUN_ID"
done

verify_rollout baseline-hashes full
jq -e '.stage == "canary" and (.paths | length) == 11' "$STATE_DIR/active-paths.json"
run_rollout recovery-audit full --action=close-ledger --require-final-event=rollback
test ! -e "$STATE_DIR/lock.json"
test "$ROLLBACK_EXIT" -eq 0
```

If the canary paths, canary post/meta hashes, active-state hash, or baseline cache namespace differ after Stage 2 rollback, the rollback is incomplete and the lock must remain held for recovery audit.

## Isolated Fixture-Only Rollback Drill

The rollback drill must not use a production WordPress database, production state directory, or production cache namespace. The implementation must provide `ops/verify-comparison-rollout-rollback-fixture.ps1`; it must reject any fixture root, state root, backup path, artifact path, or cache namespace that resolves outside its validated GUID drill root, and it must not invoke `wp`.

Immediately before the drill, capture production sentinels in the production root Bash session, then end that session. These commands occur before the drill boundary:

```bash
DRILL_SENTINEL_DIR="$STATE_DIR/evidence/$VG_RUN_ID/rollback-drill"
install -d -o root -g root -m 0700 "$DRILL_SENTINEL_DIR"

wp --path="$WP_ROOT" --allow-root eval-file \
  "$RELEASE_DIR/ops/verify-comparison-rollout-live.php" -- \
  emit-sentinel \
  --stage=full \
  --artifact="$ARTIFACT" \
  --run-id="$VG_RUN_ID" \
  --state-dir="$STATE_DIR" \
  --output="$DRILL_SENTINEL_DIR/production.before.json"

find "$STATE_DIR" -maxdepth 1 -type f -print0 | sort -z | xargs -0 sha256sum \
  > "$DRILL_SENTINEL_DIR/active-state.before.sha256"
printf '%s\n' "$VG_COMPARISON_CACHE_NAMESPACE" \
  > "$DRILL_SENTINEL_DIR/cache-namespace.before.txt"
exit
```

Run the drill on the Windows workstation only:

```powershell
$RepoRoot = 'C:\Users\NCTaam\projects\vietnamguide'
$DrillRoot = Join-Path ([System.IO.Path]::GetTempPath()) ('vietnamguide-comparison-rollback-' + [guid]::NewGuid().ToString('N'))
$Artifact = Join-Path $RepoRoot 'build\comparison-rollout-release\artifact.json'
$BackupFixture = Join-Path $RepoRoot 'ops\comparison-rollout\fixtures\rollback\full-stage-backup.json'
$CacheNamespace = 'fixture-' + [guid]::NewGuid().ToString('N')

Set-Location -LiteralPath $RepoRoot
New-Item -ItemType Directory -Path $DrillRoot | Out-Null
try {
    powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout-rollback-fixture.ps1 `
        -Artifact $Artifact `
        -BackupFixture $BackupFixture `
        -DrillRoot $DrillRoot `
        -CacheNamespace $CacheNamespace
    if ($LASTEXITCODE -ne 0) { throw 'Fixture rollback drill failed.' }
} finally {
    $ResolvedDrill = [System.IO.Path]::GetFullPath($DrillRoot)
    $TempPrefix = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
    if ($ResolvedDrill.StartsWith($TempPrefix, [System.StringComparison]::OrdinalIgnoreCase)) {
        Remove-Item -LiteralPath $ResolvedDrill -Recurse -Force
    }
}
```

During the drill boundary, no command may connect to the production host, invoke WP-CLI, name the production WordPress root, use the production state directory, or use the production cache namespace. The fixture drill must prove disable, restore, cache-purge simulation, baseline-hash verification, ledger close, reapply, and exact final hash restoration entirely inside `$DrillRoot`.

## Reconnect After the Isolated Drill

After the local drill ends, reconnect to production and become root. Replace the placeholders with the same reviewed artifact hash and the same closed Stage 2 run ID used for the before sentinel; never generate a new run ID for this comparison.

```powershell
$ProdHost = '<approved-production-host>'
$ProdUser = '<approved-production-user>'
ssh -t "$ProdUser@$ProdHost" 'sudo -i'
```

Reinitialize every non-secret shell variable in the new root Bash session, then load the cache namespace from the protected environment file with tracing disabled:

```bash
set -euo pipefail
set +x
umask 077

WP_ROOT='/usr/local/lsws/vietnamguide.net/html'
STATE_DIR='/var/lib/vietnamguide/comparison-rollout'
RELEASE_ROOT="$STATE_DIR/releases"
ENV_FILE='/etc/vietnamguide/comparison-rollout.env'
VG_ARTIFACT_HASH='<same-lowercase-artifact-sha256>'
VG_RUN_ID='<same-closed-full-run-id>'
RELEASE_DIR="$RELEASE_ROOT/$VG_ARTIFACT_HASH"
ARTIFACT="$RELEASE_DIR/ops/comparison-rollout/artifact.json"
DRILL_SENTINEL_DIR="$STATE_DIR/evidence/$VG_RUN_ID/rollback-drill"

test -d "$RELEASE_DIR"
test -f "$ARTIFACT"
test -f "$DRILL_SENTINEL_DIR/production.before.json"
test -f "$DRILL_SENTINEL_DIR/active-state.before.sha256"
test -f "$DRILL_SENTINEL_DIR/cache-namespace.before.txt"
test -f "$ENV_FILE"
test "$(stat -c '%U:%G:%a' "$ENV_FILE")" = 'root:root:600'

set -a
. "$ENV_FILE"
set +a
test -n "${VG_COMPARISON_CACHE_NAMESPACE:-}"
```

Capture the post-drill sentinel only after reinitialization succeeds. These commands occur after the drill boundary:

```bash
wp --path="$WP_ROOT" --allow-root eval-file \
  "$RELEASE_DIR/ops/verify-comparison-rollout-live.php" -- \
  emit-sentinel \
  --stage=full \
  --artifact="$ARTIFACT" \
  --run-id="$VG_RUN_ID" \
  --state-dir="$STATE_DIR" \
  --output="$DRILL_SENTINEL_DIR/production.after.json"

cmp --silent \
  "$DRILL_SENTINEL_DIR/production.before.json" \
  "$DRILL_SENTINEL_DIR/production.after.json"
(cd / && sha256sum --check --strict "$DRILL_SENTINEL_DIR/active-state.before.sha256")
test "$(cat "$DRILL_SENTINEL_DIR/cache-namespace.before.txt")" = "$VG_COMPARISON_CACHE_NAMESPACE"
```

Acceptance requires identical production post/meta hashes, identical production active-state file hashes, identical production cache namespace identity, and no new production mutation-ledger event between the before and after sentinels. A byte difference or ledger event invalidates the drill and blocks finishing.

## Final Local Integration

Use `superpowers:finishing-a-development-branch` only after all recovery, implementation, production, browser, performance, rollback, and review gates pass. Record the verified recovery base created by Task 0; do not infer it from branch names.

```powershell
$RepoRoot = 'C:\Users\NCTaam\projects\vietnamguide'
$RecoveryBaseSha = '<verified-recovery-base-sha>'
$FeatureBranch = 'codex/comparison-evidence-decision-rollout'
Set-Location -LiteralPath $RepoRoot

git merge-base --is-ancestor $RecoveryBaseSha $FeatureBranch
if ($LASTEXITCODE -ne 0) { throw 'Feature branch does not descend from recovery base.' }
if (-not [string]::IsNullOrWhiteSpace((git status --porcelain | Out-String))) {
    throw 'Working tree must be clean before finishing.'
}

git show-ref --verify --quiet refs/heads/master
if ($LASTEXITCODE -eq 0) {
    $ExistingMaster = (git rev-parse master).Trim()
    if ($ExistingMaster -ne $RecoveryBaseSha) {
        throw 'Existing master is not the recorded recovery base; stop for review.'
    }
} else {
    git branch master $RecoveryBaseSha
    if ($LASTEXITCODE -ne 0) { throw 'Unable to create master at the verified recovery base.' }
}

git switch master
git merge --no-ff $FeatureBranch -m 'merge: comparison evidence decision rollout'
git status --short
git log -5 --oneline
```

Creating `master` is allowed only inside this finishing workflow and only at the exact reviewed `RECOVERY_BASE_SHA`. Never create it from an arbitrary current HEAD.

## Stop Conditions

Stop before any mutation when artifact/payload hashes, PHP lint, CLI-only self-tests, approvals, source probes, pre-fingerprints, backup checksums, or lock ownership fail. Stop before activation when stored state is incomplete. Roll back when activation counts, public checks, browser checks, logs, budgets, cache verification, or compatibility equivalence fail. Keep the lock for recovery audit if rollback or baseline verification is incomplete. Never continue by deleting evidence, changing the run ID, weakening a verifier, or bypassing an approval.

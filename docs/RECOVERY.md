# Production Baseline Recovery

Recovery date: 2026-08-03.

## Sources

- Active production theme: `vietnamguide-production-snapshot-20260803/live-theme/vietnamguide-premium`.
- Active production MU plugin: `vietnamguide-production-snapshot-20260803/live-mu-plugins/mu-plugins/vietnamguide-core.php`.
- Project documentation and operations scripts: `vietnamguide-production-snapshot-20260803/project-webroot/docs` and `project-webroot/ops`.
- The stale `project-webroot/wordpress` copy was not used.

The active theme and MU plugin were previously verified against production. The immutable expected counts and canonical SHA-256 section digests are checked in at `tests/fixtures/recovery-source-manifest.json`. The verifier recomputes both the external snapshot projection and repository targets from ordinal-sorted `relative-path<TAB>lowercase-file-sha256` lines joined with LF and encoded as UTF-8. The approved projection contains 46 theme files, 1 MU plugin, 4 source docs, and 157 ops files; `ops/backups/**` and SQL are excluded.

## Current Recovery Status

- The approved Revision 3 comparison design and implementation plan have been mechanically restored from surviving local Codex JSONL history.
- Their exact relative paths, raw byte lengths, and lowercase SHA-256 values are pinned by `tests/fixtures/recovery-local-history-manifest.json`; they are not a general documentation allowlist.
- The current recovery execution addendum is pinned separately by `tests/fixtures/recovery-local-authored-manifest.json` and supersedes only stale environment and operational instructions in the recovered plan.
- The Guide Experience/core/homepage verifier, mutation, live, and public-QA stack is still missing and must be mechanically restored before comparison feature Task 2 begins.
- The current authoritative recovery and rollout instructions are in `docs/superpowers/plans/2026-08-03-vietnamguide-comparison-recovery-execution-addendum.md`.

## Boundaries

- Do not use drive `D:` for this project or its recovery workflow.
- Production snapshots, databases, WXR exports, uploads, and backups remain outside Git.
- No credentials, secrets, `wp-config.php`, or local Codex configuration belong in Git.
- Git history was not recoverable from the empty remote repository.
- The Revision 3 comparison specification and plan are recovered and hash-pinned. The missing verifier/tests/QA stack and its original 111-mutation evidence remain pending recovery.

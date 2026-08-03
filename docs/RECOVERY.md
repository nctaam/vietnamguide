# Production Baseline Recovery

Recovery date: 2026-08-03.

## Sources

- Active production theme: `vietnamguide-production-snapshot-20260803/live-theme/vietnamguide-premium`.
- Active production MU plugin: `vietnamguide-production-snapshot-20260803/live-mu-plugins/mu-plugins/vietnamguide-core.php`.
- Project documentation and operations scripts: `vietnamguide-production-snapshot-20260803/project-webroot/docs` and `project-webroot/ops`.
- The stale `project-webroot/wordpress` copy was not used.

The active theme and MU plugin were previously verified against production. The immutable expected counts and canonical SHA-256 section digests are checked in at `tests/fixtures/recovery-source-manifest.json`. The verifier recomputes both the external snapshot projection and repository targets from ordinal-sorted `relative-path<TAB>lowercase-file-sha256` lines joined with LF and encoded as UTF-8. The approved projection contains 46 theme files, 1 MU plugin, 4 source docs, and 157 ops files; `ops/backups/**` and SQL are excluded.

## Boundaries

- Do not use drive `D:` for this project or its recovery workflow.
- Production snapshots, databases, WXR exports, uploads, and backups remain outside Git.
- No credentials, secrets, `wp-config.php`, or local Codex configuration belong in Git.
- Git history was not recoverable from the empty remote repository.
- The latest comparison specification, plan, tests, and QA evidence were not recovered; the included recovery verifier only validates this baseline.

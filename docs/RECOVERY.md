# Production Baseline Recovery

Recovery date: 2026-08-03.

## Sources

- Active production theme: `vietnamguide-production-snapshot-20260803/live-theme/vietnamguide-premium`.
- Active production MU plugin: `vietnamguide-production-snapshot-20260803/live-mu-plugins/mu-plugins/vietnamguide-core.php`.
- Project documentation and operations scripts: `vietnamguide-production-snapshot-20260803/project-webroot/docs` and `project-webroot/ops`.
- The stale `project-webroot/wordpress` copy was not used.

The active theme (46 files) and MU plugin were previously verified against production. Recovery verification compares every restored file to its source with SHA-256. Tree hashes are SHA-256 over sorted `relative-path<TAB>file-sha256` manifest lines:

- Theme manifest: `fd97ac4159fde652a9dee2362d34bda86007b458d1d54a53ae7b56886d10c449`
- MU plugin file: `76313bc2537a25decf743f5db7b2b93be1c1431e546efb96e48e50d99afd20cc`
- Docs source manifest: `79ef208184e7e7816fc9e50b33f5d9929229c8868336544018f3210ddfd8a612`
- Ops source manifest, excluding backups and SQL: `72686fc43c1faa46ce451e13979df4946de0e45d13b690e5bb71dc62ba542a4a`

## Boundaries

- Do not use drive `D:` for this project or its recovery workflow.
- Production snapshots, databases, WXR exports, uploads, and backups remain outside Git.
- No credentials, secrets, `wp-config.php`, or local Codex configuration belong in Git.
- Git history was not recoverable from the empty remote repository.
- The latest comparison specification, plan, tests, and QA evidence were not recovered; the included recovery verifier only validates this baseline.

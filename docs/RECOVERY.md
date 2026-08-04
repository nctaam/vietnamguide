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
- The ten-file Guide Experience/core/homepage verifier, mutation, live, public-QA, tokenizer, and JavaScript-runtime stack has been mechanically restored.
- Its exact path set, raw byte lengths, and lowercase SHA-256 values are pinned separately by `tests/fixtures/recovery-local-ops-manifest.json`; it is not a general `ops` allowlist.
- The recovery baseline authorizes those ten `ops` extras only when the entire manifest validates. Unsafe paths, malformed or duplicate entries, content drift, reparse points, Git mode `120000`, no-filter index drift, and an arbitrary eleventh file remain fail-closed.
- The current authoritative recovery and rollout instructions are in `docs/superpowers/plans/2026-08-03-vietnamguide-comparison-recovery-execution-addendum.md`.

## Recovered Local Verifier Contract

The seven independently preserved files came from the approved local recovery sources. The three Guide Experience verifier files were reconstructed from the approved pre-final mutation baseline by replaying the complete successful forward patch suffix with exact hunk matching and no fuzz. The raw files are pinned as follows:

The replay audit used Codex thread `019fc2f2-d8b4-76b3-aa4a-1a2c63723fd8`. Its source artifact was `C:\Users\NCTaam\.codex\sessions\2026\08\02\rollout-2026-08-02T21-48-42-019fc2f2-d8b4-76b3-aa4a-1a2c63723fd8.jsonl`: 4,208,602 bytes, 3,482 lines, SHA-256 `9f16b798e55f22cd199ee1c74fd596c7852e303c88880445568bb461fd5fc349`. The approved pre-final baseline root was `C:\Users\NCTaam\AppData\Local\Temp\vietnamguide-guide-mutations-6ea602bc81b84e0cb3dc8bb46f3dc441\baseline\ops`, with these exact input identities:

| Baseline file | Bytes | SHA-256 |
| --- | ---: | --- |
| `verify-guide-experience.ps1` | 111,269 | `736d9681b537de11daed170cdcec2f5cdffaf83d2408fcbf6bb1e3babd704b82` |
| `verify-guide-experience-mutations.ps1` | 39,407 | `f92b80ea23af1c97f753305ceddfad58540be9ea0a8c15bfb55b28075729463f` |
| `verify-guide-experience-tokenizer.php` | 21,263 | `a6446ded677c4cbdaa501f922c8cb8482485c01e69443e11cdbecd90210c34ba` |

Across the JSONL, the audit proved 54 successful, untruncated target changes and zero failed target changes: 24 for `verify-guide-experience.ps1`, 16 for `verify-guide-experience-mutations.ps1`, and 14 for `verify-guide-experience-tokenizer.php`. The approved baselines already contained the first 8, 10, and 7 successful changes respectively. The last skipped records were JSONL line 1151 at `02:16:46Z`, line 1181 at `02:18:26Z`, and line 1124 at `02:15:19Z`; replay began independently at line 1508 at `02:56:36Z`, line 1508 at `02:56:36Z`, and line 2336 at `04:19:08Z`. This leaves the exact replay suffixes documented in Task 0: 16, 6, and 7 changes.

| Path | Bytes | Raw SHA-256 | Git blob (`--no-filters`) |
| --- | ---: | --- | --- |
| `ops/verify-core-block-patterns.ps1` | 14,206 | `30f4be5818b15e4cd8c3ad9b616aeddebd77ff97aa4922a3c89dfaaa35b23c85` | `4d98e5bed9f8a28646fdaf71fe3e82a51923063b` |
| `ops/verify-core-mu-plugin.ps1` | 30,539 | `1334d35e895a8eac7d5122b482a188f19072ae5b636c46ce44f6257fd1a1419d` | `c342e9fd043a6581c4c27a9c528b87bdd36ac31e` |
| `ops/verify-core-mu-plugin-live.php` | 9,743 | `280424139dc8b29ba2911d7d3caf1a203499c742efd6d6a243b0d98a7dc8e842` | `1228812252c71e9b5ea9950b0a6ae5db4324c358` |
| `ops/verify-homepage-theme.ps1` | 30,042 | `0c58f228806da1d121bce622d991f738f3d4552c4bf9dbbb3a8640dbb474558b` | `726a8dc5215984653901667a1cf0d51c2c8a8ba1` |
| `ops/verify-guide-experience.ps1` | 121,518 | `60f2446f513dae8ff9ea84b6a90823bb8ab35b30dd3699602ca65d41f95b21ec` | `7f79b7fe0be66923c31d485075d705657f43ab93` |
| `ops/verify-guide-experience-mutations.ps1` | 44,511 | `8bb44777a33bf89478e54189f7377155b4e17eba7de71f60fae27c88563d6108` | `1631221dda0f28e84cd72b50e5984219a82fc32a` |
| `ops/verify-guide-experience-tokenizer.php` | 25,434 | `9b65e83952e5d82dc835d6c4d56e6f93c396d9334e877117f7def4bbc99c25f4` | `4b8162ecc8b989bddf0d86f8f9b1154fe5c1d976` |
| `ops/verify-guide-experience-public.ps1` | 55,723 | `03b018f463abe474d06b7006a5cdc952d757cd26825d0a1c9700f20f24873543` | `bf7f79033c6ddd9fe716c1706ad16a0730940d4a` |
| `ops/verify-guide-experience-live.php` | 26,254 | `33bf8f3791bd6b8e5cc6aff1a7cc2dbfa8a7df8df9d639ba262f431879f30643` | `dbfed12eb0b13b61d590682c8a50879ecbe69b0c` |
| `ops/verify-guide-experience-js-runtime.js` | 3,703 | `031feabe4d0934a13085f9066e42088c941d5dd60be0e649b2b89e2c67eeeb4b` | `ad6fb89eb37c55e68e8c687117cc6194b10e6912` |

For reconstruction provenance, canonical LF normalization of the three replayed files yields:

| Path | Canonical LF bytes | Canonical LF SHA-256 | Canonical LF Git blob |
| --- | ---: | --- | --- |
| `ops/verify-guide-experience.ps1` | 120,394 | `f148aedc5e8cb8bdd4ee572cc93c713bbe3f810fa2b3f199cccf7809796c5f07` | `acbab9e0c1bb0b28399e4177c647c69abfec1462` |
| `ops/verify-guide-experience-mutations.ps1` | 43,871 | `167a7e1fe56eab497dbf01851c53630c04157d210134e44dd2bd8434d33895bf` | `5cc8346a60f4f8702a3bd03aa98c5f84b0553568` |
| `ops/verify-guide-experience-tokenizer.php` | 25,434 | `9b65e83952e5d82dc835d6c4d56e6f93c396d9334e877117f7def4bbc99c25f4` | `4b8162ecc8b989bddf0d86f8f9b1154fe5c1d976` |

## Historical Applicability Findings

- Exact recovery commit `4ca75ab309f48124ef5d7383dceefc51d50aa85b` preserved the 30,218-byte core MU verifier with SHA-256 `8f2db448f7af3b62293c71fe44cbf415b3d5e4a14d3d198dd5be82d2cd8da65c`. It passes against the approved CRLF salvage state but its multiline affiliate-loop fixture cannot locate the production-pinned LF block. The follow-up TDD hardening changes only that fixture replacement to follow the plugin line ending; the production plugin and source manifest remain unchanged.
- The exact homepage verifier passes against the approved later salvage homepage state. It is not directly applicable to the recovered production baseline because it requires `ops/build-homepage-images.py`, `ops/test-homepage-images.py`, `qa/homepage-preview.html`, and Ha Long Bay credit metadata that are not in the production source manifest. Those unrelated assets must not be added to the strict ten-file local-ops contract, and the canonical theme README must not be changed to satisfy this historical verifier.

## Boundaries

- Do not use drive `D:` for this project or its recovery workflow.
- Production snapshots, databases, WXR exports, uploads, and backups remain outside Git.
- No credentials, secrets, `wp-config.php`, or local Codex configuration belong in Git.
- Git history was not recoverable from the empty remote repository.
- The Revision 3 comparison specification and plan remain hash-pinned and unchanged. Task 0 must be committed and reviewed as the recorded recovery base before comparison feature Task 2 begins.

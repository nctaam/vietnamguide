# Recovery Parser Core Refactor Design

## Context

The recovery baseline verifier currently mixes four responsibilities in one script:

1. Markdown section and fence parsing.
2. Bash lexical analysis and heredoc extraction.
3. PowerShell AST inspection and native-command guard validation.
4. Recovery rollout contract evaluation.

Successive hardening rounds closed individual false-accept paths, but each round exposed another interaction between raw-text searches, partial tokenizers, and contract rules. The cumulative change from the recovery baseline added more than 800 lines to `tests/verify-recovery-baseline.ps1`. This is an architectural signal: parser state and rollout policy need explicit boundaries rather than more local conditions.

This refactor is infrastructure work for safe recovery and production rollout. It does not directly change the VietnamGuide UI or content. UI and content work can continue in a separate worktree; this parser becomes a production-release gate, not a local-preview gate.

## Goals

- Replace mixed regex/tokenizer logic with a typed, fail-closed parser pipeline.
- Separate language parsing from recovery contract evaluation.
- Evaluate execution-sensitive contracts only from executable events, never raw text.
- Preserve Windows PowerShell 5.1 compatibility and offline recovery operation.
- Preserve the exact ordered mutation inventory: 77 total results, 30 runbook-safety results, 77 unique names.
- Add fast focused parser and contract tests so most changes do not require the 30-minute full mutation suite.
- Keep the existing verifier entry points and command-line contracts stable.

## Non-Goals

- Implement complete Bash, Markdown, or PowerShell language specifications.
- Add external runtime dependencies such as tree-sitter, Node packages, or online services.
- Change recovery runbook behavior, rollout policy, source manifests, theme files, or production content.
- Merge, push, deploy, or modify the dirty main worktree as part of this refactor.

## Considered Approaches

### 1. Typed internal parser pipeline (selected)

Parse Markdown into typed sections and fences, convert supported Bash and PowerShell content into executable event streams, return structured diagnostics for unsupported or ambiguous syntax, and evaluate rollout contracts over events.

Advantages:

- Clear boundaries and independently testable units.
- Fail-closed behavior is explicit.
- Recovery policies no longer depend on raw marker placement.
- No new runtime dependency.

Trade-off: the project still owns a deliberately limited grammar and must document supported constructs.

### 2. Exact-block or content-hash validation

Pin complete runbook blocks or documents and reject every textual change.

Advantages: small implementation and strong tamper detection.

Trade-off: legitimate runbook maintenance becomes unnecessarily brittle, and semantic validation is lost.

### 3. External general-purpose parser

Use tree-sitter or another full parser for Markdown and Bash.

Advantages: broader grammar coverage.

Trade-off: adds dependencies and weakens PS5.1/offline recovery portability. This is not acceptable for the recovery path.

## Architecture

```text
Markdown document
    -> typed sections and fences
    -> language-specific executable event streams
    -> structured fail-closed diagnostics
    -> recovery contract evaluator
```

The parser module is policy-free. It answers what structure and executable actions are present. The baseline verifier owns rollout-specific questions such as ordering, uniqueness, required gates, rollback placement, and publication sequence.

## File Responsibilities

### `tests/lib/RecoveryParser.psm1`

New parser module with no recovery rollout constants or global failure list.

Responsibilities:

- Normalize LF/CRLF input without losing source locations.
- Parse Markdown comments, headings, sections, and fenced blocks.
- Build Bash logical statements before token analysis.
- Track Bash comments, quotes, arithmetic contexts, continuations, and FIFO heredocs.
- Parse PowerShell fences with the Windows PowerShell 5.1 AST.
- Normalize literal native executable names and identify unsupported wrappers or dynamic invocation.
- Associate native commands with immediate fail-fast guards.
- Return typed events and diagnostics.

### `tests/verify-recovery-parser.ps1`

New focused test entry point.

Responsibilities:

- Exercise parser behavior using small in-memory documents and code blocks.
- Run in seconds without copying the 231-file snapshot.
- Cover supported syntax and every fail-closed boundary.
- Verify deterministic diagnostics and source locations.

### `tests/verify-recovery-baseline.ps1`

Existing public verifier entry point.

Responsibilities after migration:

- Import `RecoveryParser.psm1`.
- Load repository and recovery fixture data.
- Reject any parser diagnostic.
- Apply recovery-specific contracts to typed events.
- Retain source manifest, inventory, file-mode, secret, and repository-shape verification.

It must not contain its own Markdown, Bash, or PowerShell tokenizers after migration.

### `tests/verify-recovery-mutations.ps1`

Existing integration and regression suite.

Responsibilities:

- Preserve the exact ordered 77-result inventory and 30 runbook-safety names.
- Aggregate multiple internal fixtures under existing result names when more coverage is needed.
- Validate complete runbook and repository behavior.
- Remain the pre-merge integration gate, not the primary lexer development loop.

## Data Contracts

The module returns plain PS5.1-compatible objects. It does not rely on PowerShell classes.

### Parse result

```text
ParseResult
  IsValid: bool
  Events: object[]
  Diagnostics: object[]
```

`IsValid` is true only when `Diagnostics` is empty.

### Executable event

```text
ExecutableEvent
  Kind: string
  Language: string
  Text: string
  NormalizedCommand: string|null
  SourceLine: int
  SourceColumn: int
  SectionId: string|null
  FenceId: string
  StatementId: string
  Metadata: hashtable
```

Event identity and source positions must be deterministic for the same input.

### Diagnostic

```text
ParserDiagnostic
  Code: string
  Message: string
  Language: string
  SourceLine: int
  SourceColumn: int
  FenceId: string|null
```

Mutation assertions should prefer stable diagnostic codes over wrapped message text.

## Supported Grammar and Fail-Closed Policy

The module supports only constructs required by the authored recovery runbook and focused regression fixtures.

### Markdown

- ATX headings used by the runbook.
- Backtick and tilde fenced blocks with CommonMark closing-fence rules.
- Stateful HTML comments with source-column preservation.
- Required language aliases declared by the verifier.

Headings and fence markers inside comments or open fences are never structural. A closing fence may contain only trailing whitespace. A required section with missing or unsupported fences is invalid.

### Bash

- Physical-to-logical line reconstruction for unquoted backslash-newline continuation.
- Correct comment boundaries before continuation handling.
- Single and double quotes needed by the runbook.
- Arithmetic expansions and shifts used by the runbook.
- Normal, quoted, tab-stripped, and FIFO multi-heredocs.
- Exact here-string handling for `<<<`.

Ambiguous delimiters, unfinished quotes, unfinished continuations, invalid contiguous redirection operators, and unbalanced heredoc queues produce diagnostics. Heredoc bodies never emit executable events.

### PowerShell

- Windows PowerShell 5.1 AST parsing with parse-error rejection.
- Literal command names normalized by leaf name, case, and supported executable suffixes.
- Explicitly supported PowerShell host aliases.
- Immediate statement-level guard relationships.
- Direct and captured `$LASTEXITCODE` checks.
- `throw` or statically demonstrable nonzero `exit` as blocking outcomes.

Unknown wrappers, unsupported dynamic invocation, missing expected fences, same-line intervening statements, `exit 0`, and nested executable commands outside the supported contract produce diagnostics or failed guard relationships.

## Contract Evaluation

Execution-sensitive recovery rules consume executable events only.

Examples:

- Rollback gate ordering.
- Stage 2 gate ordering and uniqueness.
- Public verifier placement.
- Publication and installer ordering.
- Native command fail-fast coverage.

Raw `IndexOf`, regex marker ordering, and comment-visible text are forbidden for execution semantics. Raw text remains acceptable only for non-execution document metadata where explicitly documented.

## Error Handling

- Parser functions return results; they do not call the verifier's `Add-Failure` function.
- Unsupported or ambiguous syntax is a diagnostic, not an empty event list.
- The baseline verifier converts every diagnostic into a verification failure with stable code and source location.
- Multiple diagnostics may be reported in one run when doing so does not require guessing parser state.
- Once parser state is ambiguous, the affected fence fails closed and does not emit further executable events.

## Test Strategy

### Level 1: focused parser tests

Run `tests/verify-recovery-parser.ps1` during every parser change. These tests cover:

- Markdown comment and fence transitions.
- Bash comments, logical continuations, quotes, arithmetic, heredocs, and invalid operators.
- PowerShell parse errors, command normalization, wrappers, dynamic invocation, and guard semantics.
- Stable diagnostics and source positions.

Target runtime: seconds, not minutes.

### Level 2: focused contract tests

Feed synthetic executable events to contract functions and verify ordering, uniqueness, missing-event, and duplicate-event behavior without reparsing complete runbooks.

### Level 3: runbook-safety suite

Run the exact 32-case `-RunbookSafetyOnly` suite at task checkpoints and before spec review.

### Level 4: full mutation suite

Run the exact 77-case suite once per milestone commit and before integration review.

### Level 5: baseline snapshot

Run the 231-file baseline with all 24 ignore probes at integration gates.

## Migration Sequence

1. Add focused characterization tests for all currently supported valid constructs and all open quality findings.
2. Add `RecoveryParser.psm1` and the focused parser test entry point without changing the baseline verifier.
3. Run the new module in shadow mode against the authored recovery addendum and compare normalized events with the current accepted behavior.
4. Switch Markdown section and fence consumers to typed parser output.
5. Switch Bash execution and heredoc consumers to typed events.
6. Switch PowerShell native command and guard consumers to AST-derived events.
7. Convert every execution-sensitive recovery contract to event-only evaluation.
8. Remove legacy tokenizers and raw execution-marker ordering functions from the baseline verifier.
9. Run focused tests, 32/32 safety, 77/77 full mutations, 231/24 baseline, inventory checks, manifest invariants, and blob parity.
10. Complete fresh spec and code-quality reviews with no open Important findings.

Each migration step uses TDD, produces a small commit, and must leave the previous public verifier command line operational.

## Parallel Work Model

The project uses three independent tracks:

- Template and design system: approximately 50 percent of active effort.
- Content and data contract: approximately 30 percent.
- Parser and release safety: approximately 20 percent.

Each worktree has one implementation writer. Review agents are read-only. UI and content previews do not wait for parser completion; production rollout does.

The parser branch must not merge into the dirty main worktree until the unknown main-worktree changes are resolved. No production deployment is part of this design.

## Success Criteria

- `RecoveryParser.psm1` is the only Markdown/Bash/PowerShell parsing implementation used by the baseline verifier.
- No execution-sensitive contract relies on raw text marker ordering.
- Focused parser and contract tests cover every previously reported parser path.
- Windows PowerShell 5.1 parses all scripts and modules without errors.
- Baseline verification passes for 231 repository files and 24/24 ignore probes.
- Runbook safety passes 32/32.
- Full mutation verification passes 77/77.
- Static inventory is exactly 77 total, 30 runbook-safety, and 77 unique in the required order.
- Recovery source manifest Git blob and canonical SHA-256 remain unchanged.
- Fresh spec and quality reviews report no Critical or Important findings.
- The isolated worktree is clean and no main, remote, or production state is changed.

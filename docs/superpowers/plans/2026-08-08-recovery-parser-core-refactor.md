# Recovery Parser Core Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the recovery verifier's mixed Markdown, Bash, and PowerShell parsing logic with one typed, fail-closed parser module and event-only execution contract checks.

**Architecture:** `tests/lib/RecoveryParser.psm1` parses supported Markdown fences and language content into deterministic events and diagnostics. `tests/verify-recovery-baseline.ps1` retains repository validation and rollout policy but consumes parser events instead of raw execution markers; focused tests provide the fast development loop while the existing 32/77 mutation suites remain integration gates.

**Tech Stack:** Windows PowerShell 5.1, PowerShell AST APIs, Git, existing recovery snapshot fixtures, no external dependencies.

---

## File Map

- Create `tests/lib/RecoveryParser.psm1`: typed result/event/diagnostic constructors, Markdown parser, Bash lexer, PowerShell AST adapter, and generic event query helpers.
- Create `tests/verify-recovery-parser.ps1`: fast in-memory parser and generic event-query tests.
- Modify `tests/verify-recovery-baseline.ps1`: import the parser, reject diagnostics, require expected fences, and evaluate execution-sensitive contracts from events.
- Modify `tests/verify-recovery-mutations.ps1`: preserve the exact 77/30 ordered inventory while aggregating new internal regression fixtures under existing result names.
- Reference `docs/superpowers/specs/2026-08-08-recovery-parser-core-refactor-design.md`: approved architecture and acceptance criteria.

## Global Constraints

- Work only in `C:\Users\NCTaam\projects\vietnamguide-worktrees\recovery-parser-edge-hardening`.
- Do not inspect, modify, stage, or revert the dirty main worktree.
- Do not merge, push, deploy, or access production.
- Keep Windows PowerShell 5.1 compatibility; use plain functions and `pscustomobject`, not PowerShell classes.
- Do not change the recovery source manifest Git blob `4cd169cad33528189f88fcddca41e1db4fb802f0` or canonical SHA-256 `e388eca43f45289c9ce821febfc333ea6318b86893e7bb5f100b2b6151890c4e`.
- Keep the mutation result list byte-order identical: 77 total, 30 names starting with `runbook safety:`, 77 unique.
- Use TDD for every behavior change. A focused failing probe must be observed before implementation.

---

### Task 1: Establish the typed parser contract and focused test runner

**Files:**
- Create: `tests/lib/RecoveryParser.psm1`
- Create: `tests/verify-recovery-parser.ps1`

- [ ] **Step 1: Write a focused runner that expects typed constructors**

Create `tests/verify-recovery-parser.ps1` with a small assertion harness and three contract checks:

```powershell
[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$modulePath = Join-Path $PSScriptRoot 'lib\RecoveryParser.psm1'
Import-Module $modulePath -Force

$results = [System.Collections.Generic.List[object]]::new()
function Add-ParserResult {
    param([string]$Name, [bool]$Passed, [string]$Detail = '')
    $results.Add([pscustomobject]@{ Name = $Name; Passed = $Passed; Detail = $Detail })
}

$diagnostic = New-RecoveryParserDiagnostic -Code 'TEST_CODE' -Message 'test' -Language 'bash' -SourceLine 2 -SourceColumn 3
Add-ParserResult -Name 'diagnostic contract is stable' -Passed (
    $diagnostic.Code -eq 'TEST_CODE' -and $diagnostic.SourceLine -eq 2 -and $diagnostic.SourceColumn -eq 3
)

$event = New-RecoveryExecutableEvent -Kind 'command' -Language 'bash' -Text 'echo ok' -NormalizedCommand 'echo' -SourceLine 4 -SourceColumn 1 -FenceId 'f1' -StatementId 's1'
Add-ParserResult -Name 'event contract is stable' -Passed (
    $event.Kind -eq 'command' -and $event.FenceId -eq 'f1' -and $event.StatementId -eq 's1'
)

$parseResult = New-RecoveryParseResult -Events @($event) -Diagnostics @()
Add-ParserResult -Name 'parse result validity follows diagnostics' -Passed (
    $parseResult.IsValid -and @($parseResult.Events).Count -eq 1 -and @($parseResult.Diagnostics).Count -eq 0
)

$failed = @($results | Where-Object { -not $_.Passed })
$results | Format-Table -AutoSize
if ($failed.Count -gt 0) { throw "Recovery parser verification failed: $($failed.Count)/$($results.Count)" }
Write-Host "Recovery parser verification passed: $($results.Count)/$($results.Count)"
```

- [ ] **Step 2: Run the focused runner and verify RED**

Run:

```powershell
powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File .\tests\verify-recovery-parser.ps1
```

Expected: nonzero exit because `tests/lib/RecoveryParser.psm1` or its exported constructors do not exist.

- [ ] **Step 3: Implement the typed constructors**

Create `tests/lib/RecoveryParser.psm1` with these exact exported functions and property names:

```powershell
Set-StrictMode -Version 2.0

function New-RecoveryParserDiagnostic {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][string]$Code,
        [Parameter(Mandatory = $true)][string]$Message,
        [Parameter(Mandatory = $true)][string]$Language,
        [Parameter(Mandatory = $true)][int]$SourceLine,
        [Parameter(Mandatory = $true)][int]$SourceColumn,
        [string]$FenceId = $null
    )
    [pscustomobject]@{
        Code = $Code
        Message = $Message
        Language = $Language
        SourceLine = $SourceLine
        SourceColumn = $SourceColumn
        FenceId = $FenceId
    }
}

function New-RecoveryExecutableEvent {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][string]$Kind,
        [Parameter(Mandatory = $true)][string]$Language,
        [Parameter(Mandatory = $true)][string]$Text,
        [string]$NormalizedCommand = $null,
        [Parameter(Mandatory = $true)][int]$SourceLine,
        [Parameter(Mandatory = $true)][int]$SourceColumn,
        [string]$SectionId = $null,
        [Parameter(Mandatory = $true)][string]$FenceId,
        [Parameter(Mandatory = $true)][string]$StatementId,
        [hashtable]$Metadata = @{}
    )
    [pscustomobject]@{
        Kind = $Kind
        Language = $Language
        Text = $Text
        NormalizedCommand = $NormalizedCommand
        SourceLine = $SourceLine
        SourceColumn = $SourceColumn
        SectionId = $SectionId
        FenceId = $FenceId
        StatementId = $StatementId
        Metadata = $Metadata
    }
}

function New-RecoveryParseResult {
    [CmdletBinding()]
    param([object[]]$Events = @(), [object[]]$Diagnostics = @())
    $eventArray = @($Events | Where-Object { $null -ne $_ })
    $diagnosticArray = @($Diagnostics | Where-Object { $null -ne $_ })
    [pscustomobject]@{
        IsValid = ($diagnosticArray.Count -eq 0)
        Events = $eventArray
        Diagnostics = $diagnosticArray
    }
}

Export-ModuleMember -Function @(
    'New-RecoveryParserDiagnostic',
    'New-RecoveryExecutableEvent',
    'New-RecoveryParseResult'
)
```

- [ ] **Step 4: Verify GREEN and PS5 parsing**

Run:

```powershell
powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File .\tests\verify-recovery-parser.ps1
powershell.exe -NoProfile -NonInteractive -Command "$t=$null;$e=$null;[System.Management.Automation.Language.Parser]::ParseFile((Resolve-Path '.\tests\lib\RecoveryParser.psm1'),[ref]$t,[ref]$e)>$null;if($e.Count){$e|% Message;exit 1}"
```

Expected: focused runner exits 0 with `Recovery parser verification passed: 3/3`; module parse exits 0 with no output.

- [ ] **Step 5: Commit Task 1**

```powershell
git add -- tests/lib/RecoveryParser.psm1 tests/verify-recovery-parser.ps1
git diff --cached --check
git commit -m "test: establish recovery parser contract"
```

---

### Task 2: Move Markdown structure parsing into the module

**Files:**
- Modify: `tests/lib/RecoveryParser.psm1`
- Modify: `tests/verify-recovery-parser.ps1`
- Read from: `tests/verify-recovery-baseline.ps1:37-215`

- [ ] **Step 1: Add Markdown RED cases**

Append focused cases that call `ConvertFrom-RecoveryMarkdown` and assert:

```powershell
$markdown = @'
## Target
```bash
echo visible
```bash
## Hidden heading
echo hidden
```
## Next
'@
$document = ConvertFrom-RecoveryMarkdown -Text $markdown -RequiredSections @('## Target') -LanguageAliases @{ bash = @('bash','sh') }
Add-ParserResult -Name 'same-info fence is not a closing fence' -Passed (
    $document.IsValid -and
    @($document.Sections | Where-Object Id -eq '## Target').Count -eq 1 -and
    @($document.Fences | Where-Object Language -eq 'bash').Count -eq 1 -and
    $document.Fences[0].Body.Contains('## Hidden heading')
)

$commented = "<!-- ## Target -->`n```bash`necho hidden`n```"
$commentResult = ConvertFrom-RecoveryMarkdown -Text $commented -RequiredSections @('## Target') -LanguageAliases @{ bash = @('bash') }
Add-ParserResult -Name 'commented structure is not visible' -Passed (
    -not $commentResult.IsValid -and @($commentResult.Diagnostics | Where-Object Code -eq 'MD_REQUIRED_SECTION_MISSING').Count -eq 1
)

$missingFence = "## Target`nplain text"
$missingFenceResult = ConvertFrom-RecoveryMarkdown -Text $missingFence -RequiredSections @('## Target') -RequiredFenceLanguages @{ '## Target' = @('bash') }
Add-ParserResult -Name 'required fence coverage fails closed' -Passed (
    -not $missingFenceResult.IsValid -and @($missingFenceResult.Diagnostics | Where-Object Code -eq 'MD_REQUIRED_FENCE_MISSING').Count -eq 1
)
```

Add additional assertions for tilde fences, longer closing fences, HTML comments spanning lines, comment-spliced fence markers, and source line/column stability.

- [ ] **Step 2: Run focused tests and verify RED**

Expected: nonzero exit because `ConvertFrom-RecoveryMarkdown` is not exported.

- [ ] **Step 3: Implement typed Markdown parsing**

Move and adapt the proven logic from these existing functions:

```text
Get-MarkdownLineRecords
Get-MarkdownFenceMatch
Get-MarkdownVisibleLine
Get-MarkdownSectionText
Get-MarkdownFencedBlocks
```

Expose one public function:

```powershell
function ConvertFrom-RecoveryMarkdown {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][string]$Text,
        [string[]]$RequiredSections = @(),
        [hashtable]$RequiredFenceLanguages = @{},
        [hashtable]$LanguageAliases = @{}
    )
    # Return a pscustomobject with IsValid, Sections, Fences, Diagnostics.
}
```

Use deterministic IDs: `section-0001`, `fence-0001`. Each section has `Id`, `Heading`, `StartLine`, `EndLine`; each fence has `Id`, `SectionId`, `Language`, `RawInfo`, `Body`, `StartLine`, `EndLine`. Closing fences are recognized only when info text is empty.

Emit these diagnostic codes exactly:

```text
MD_REQUIRED_SECTION_MISSING
MD_REQUIRED_FENCE_MISSING
MD_UNCLOSED_FENCE
MD_UNCLOSED_HTML_COMMENT
MD_UNSUPPORTED_FENCE_LANGUAGE
```

- [ ] **Step 4: Run focused tests and verify GREEN**

Expected: all Markdown cases pass; no parser diagnostic appears for valid backtick/tilde/comment fixtures.

- [ ] **Step 5: Commit Task 2**

```powershell
git add -- tests/lib/RecoveryParser.psm1 tests/verify-recovery-parser.ps1
git diff --cached --check
git commit -m "test: add typed recovery markdown parser"
```

---

### Task 3: Move Bash logical-line and heredoc parsing into the module

**Files:**
- Modify: `tests/lib/RecoveryParser.psm1`
- Modify: `tests/verify-recovery-parser.ps1`
- Read from: `tests/verify-recovery-baseline.ps1:238-691`

- [ ] **Step 1: Add Bash RED cases**

Add table-driven cases for `ConvertFrom-RecoveryBashFence` covering:

```powershell
$bashCases = @(
    @{ Name='split heredoc operator'; Text="cat <\`n<EOF`nBODY_MARKER`nEOF`necho visible"; Valid=$true; Visible=@('cat <<EOF','echo visible'); Hidden='BODY_MARKER' },
    @{ Name='comment backslash does not continue'; Text="echo ok # comment \`ncat <<EOF`nBODY_MARKER`nEOF"; Valid=$true; Visible=@('echo ok # comment \','cat <<EOF'); Hidden='BODY_MARKER' },
    @{ Name='unfinished continuation'; Text='echo broken \'; Valid=$false; Code='BASH_UNFINISHED_CONTINUATION' },
    @{ Name='exact here string'; Text=': <<<EOF'; Valid=$true; HeredocCount=0 },
    @{ Name='invalid less run'; Text=': <<<<<EOF'; Valid=$false; Code='BASH_AMBIGUOUS_REDIRECTION' },
    @{ Name='arithmetic shift'; Text=': $((1 << 2))'; Valid=$true; HeredocCount=0 },
    @{ Name='fifo heredocs'; Text="cat <<A <<'B'`none`nA`ntwo`nB`necho visible"; Valid=$true; Visible=@('cat <<A <<''B''','echo visible') }
)
```

For each valid case, assert heredoc bodies are absent from `Events.Text`. Add explicit cases for single/double quotes, `<<-`, `E\OF`, unbalanced queues, and unterminated quotes.

- [ ] **Step 2: Run focused tests and verify RED**

Expected: nonzero exit because `ConvertFrom-RecoveryBashFence` does not exist.

- [ ] **Step 3: Implement Bash parsing as a state machine**

Move and adapt these existing functions into private module functions:

```text
Get-BashArithmeticExpansionEnd
Test-BashUnquotedLineContinuation
Get-BashLogicalLineRecords
Get-BashHeredocRedirections
```

Implement the public adapter:

```powershell
function ConvertFrom-RecoveryBashFence {
    [CmdletBinding()]
    param([Parameter(Mandatory = $true)]$Fence)

    # 1. Build logical lines while respecting quote and comment boundaries.
    # 2. Consume queued heredoc bodies FIFO.
    # 3. Parse heredoc operators only on executable logical lines.
    # 4. Emit one bash-command event per executable logical statement.
    # 5. Return New-RecoveryParseResult with stable diagnostic codes.
}
```

Use these diagnostic codes:

```text
BASH_UNFINISHED_CONTINUATION
BASH_UNTERMINATED_SINGLE_QUOTE
BASH_UNTERMINATED_DOUBLE_QUOTE
BASH_AMBIGUOUS_REDIRECTION
BASH_UNBALANCED_HEREDOC_QUEUE
BASH_INVALID_ARITHMETIC
```

A `#` starts a comment only when Bash token rules allow it; a backslash after comment start never continues the next physical line.

- [ ] **Step 4: Run focused tests and verify GREEN**

Expected: every Bash case passes; `BODY_MARKER` is absent from events in split/comment heredoc tests.

- [ ] **Step 5: Commit Task 3**

```powershell
git add -- tests/lib/RecoveryParser.psm1 tests/verify-recovery-parser.ps1
git diff --cached --check
git commit -m "test: add fail-closed recovery bash lexer"
```

---

### Task 4: Move PowerShell AST and native guard analysis into the module

**Files:**
- Modify: `tests/lib/RecoveryParser.psm1`
- Modify: `tests/verify-recovery-parser.ps1`
- Read from: `tests/verify-recovery-baseline.ps1:718-1011`

- [ ] **Step 1: Add PowerShell RED cases**

Add focused fixtures that call `ConvertFrom-RecoveryPowerShellFence` with `-NativeCommandNames @('powershell','pwsh','node','php','git','ssh','scp')`:

```powershell
$powerShellCases = @(
    @{ Name='bare command requires guard'; Text='git status'; Valid=$false; Code='PS_NATIVE_GUARD_MISSING' },
    @{ Name='exe suffix normalizes'; Text="scp.exe x host:y`nif (`$LASTEXITCODE -ne 0) { throw 'copy failed' }"; Valid=$true; Command='scp' },
    @{ Name='cmd suffix fails closed'; Text='git.cmd status'; Valid=$false; Code='PS_UNSUPPORTED_NATIVE_WRAPPER' },
    @{ Name='cmd host wrapper fails closed'; Text='cmd.exe /c git status'; Valid=$false; Code='PS_UNSUPPORTED_NATIVE_WRAPPER' },
    @{ Name='exit zero is not blocking'; Text="git status`nif (`$LASTEXITCODE -ne 0) { exit 0 }"; Valid=$false; Code='PS_NATIVE_GUARD_NONBLOCKING' },
    @{ Name='nonzero exit blocks'; Text="git status`nif (`$LASTEXITCODE -ne 0) { exit 1 }"; Valid=$true; Command='git' },
    @{ Name='captured nonzero exit blocks'; Text="git status`n`$gitExit = `$LASTEXITCODE`nif (`$gitExit -ne 0) { exit `$gitExit }"; Valid=$true; Command='git' },
    @{ Name='dynamic invocation fails closed'; Text='& $UnknownExecutable --version'; Valid=$false; Code='PS_DYNAMIC_NATIVE_UNSUPPORTED' }
)
```

Add parse-error, nested subexpression, same-line intervening statement, `powershell.exe`, `pwsh`, and supported `$PhpExecutable` fixtures.

- [ ] **Step 2: Run focused tests and verify RED**

Expected: nonzero exit because `ConvertFrom-RecoveryPowerShellFence` is not exported.

- [ ] **Step 3: Implement the PowerShell AST adapter**

Move and adapt these functions into private module functions:

```text
Get-PowerShellNativeCommandName
Get-PowerShellStatementContext
Test-PowerShellNativeStatementShape
Test-PowerShellCondition
Test-PowerShellBlockingStatementBlock
Test-PowerShellStandardNativeGuard
Get-PowerShellCapturedExitVariableName
Test-PowerShellCapturedNativeGuard
Test-PowerShellImmediateNativeGuard
Test-PowerShellNativeFailFast
```

Normalize literal leaf names as follows:

```powershell
$leaf = [System.IO.Path]::GetFileName($commandName).ToLowerInvariant()
if ($leaf.EndsWith('.exe')) { $leaf = $leaf.Substring(0, $leaf.Length - 4) }
```

Recognize only the configured native base names after `.exe` normalization. Treat `.cmd`, `.bat`, `.com`, `cmd.exe`, unsupported invocation operators, and unknown dynamic variables as diagnostics rather than ignored commands.

Accept blocking statements only when they contain exactly one `throw`, a literal nonzero integer `exit`, or a captured status variable proven to be the immediately assigned native exit code. Reject `exit 0` and unknown exit expressions.

Use stable codes:

```text
PS_PARSE_ERROR
PS_NATIVE_GUARD_MISSING
PS_NATIVE_GUARD_NONBLOCKING
PS_NATIVE_STATEMENT_AMBIGUOUS
PS_UNSUPPORTED_NATIVE_WRAPPER
PS_DYNAMIC_NATIVE_UNSUPPORTED
```

- [ ] **Step 4: Run focused tests and verify GREEN**

Expected: all PowerShell cases pass and valid events have normalized command names without `.exe`.

- [ ] **Step 5: Commit Task 4**

```powershell
git add -- tests/lib/RecoveryParser.psm1 tests/verify-recovery-parser.ps1
git diff --cached --check
git commit -m "test: add recovery powershell ast adapter"
```

---

### Task 5: Add generic executable-event queries and shadow integration

**Files:**
- Modify: `tests/lib/RecoveryParser.psm1`
- Modify: `tests/verify-recovery-parser.ps1`
- Modify: `tests/verify-recovery-baseline.ps1`

- [ ] **Step 1: Add event-query RED tests**

Add synthetic events and assert `Test-RecoveryEventSequence` rejects missing, duplicate, and reordered markers without reading raw document text:

```powershell
$events = @(
    (New-RecoveryExecutableEvent -Kind command -Language bash -Text 'gate one' -SourceLine 1 -SourceColumn 1 -FenceId f1 -StatementId s1),
    (New-RecoveryExecutableEvent -Kind command -Language bash -Text 'gate two' -SourceLine 2 -SourceColumn 1 -FenceId f1 -StatementId s2)
)
Add-ParserResult -Name 'event sequence accepts exact order' -Passed (Test-RecoveryEventSequence -Events $events -ExpectedTexts @('gate one','gate two') -RequireUnique)
Add-ParserResult -Name 'event sequence rejects duplicates' -Passed (-not (Test-RecoveryEventSequence -Events @($events + $events[1]) -ExpectedTexts @('gate one','gate two') -RequireUnique))
Add-ParserResult -Name 'event sequence rejects reorder' -Passed (-not (Test-RecoveryEventSequence -Events @($events[1],$events[0]) -ExpectedTexts @('gate one','gate two') -RequireUnique))
```

- [ ] **Step 2: Verify RED, then implement generic query helpers**

Export:

```powershell
function Find-RecoveryExecutableEvents {
    param([object[]]$Events, [string]$ExactText, [string]$Language = $null)
}

function Test-RecoveryEventSequence {
    param([object[]]$Events, [string[]]$ExpectedTexts, [switch]$RequireUnique)
}
```

Both helpers compare event `Text` with ordinal equality after documented LF normalization. They do not inspect comments or raw Markdown.

- [ ] **Step 3: Import the module in the baseline verifier and add shadow parsing**

At the top of `tests/verify-recovery-baseline.ps1`, add:

```powershell
$parserModulePath = Join-Path $PSScriptRoot 'lib\RecoveryParser.psm1'
Import-Module $parserModulePath -Force -ErrorAction Stop
```

After loading the authored execution addendum, call `ConvertFrom-RecoveryMarkdown`, parse each supported Bash/PowerShell fence, and add failures for diagnostics using this format:

```powershell
Add-Failure "Parser diagnostic [$($diagnostic.Code)] line $($diagnostic.SourceLine):$($diagnostic.SourceColumn): $($diagnostic.Message)"
```

Keep legacy contract evaluation active in this task. Add a shadow assertion that the authored baseline produces no diagnostics and contains executable events for every currently required gate.

- [ ] **Step 4: Run focused parser tests and direct baseline**

Run:

```powershell
powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File .\tests\verify-recovery-parser.ps1
powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File .\tests\verify-recovery-baseline.ps1 -SnapshotRoot 'C:\Users\NCTaam\projects\vietnamguide-production-snapshot-20260803'
```

Expected: focused tests exit 0; baseline exits 0 with `Ignore probes: 24/24` and `Recovery baseline verification passed for 231 repository files.`

- [ ] **Step 5: Commit Task 5**

```powershell
git add -- tests/lib/RecoveryParser.psm1 tests/verify-recovery-parser.ps1 tests/verify-recovery-baseline.ps1
git diff --cached --check
git commit -m "test: shadow recovery parser events"
```

---

### Task 6: Convert execution-sensitive contracts to parser events

**Files:**
- Modify: `tests/verify-recovery-baseline.ps1`
- Modify: `tests/verify-recovery-mutations.ps1`
- Modify: `tests/verify-recovery-parser.ps1`

- [ ] **Step 1: Strengthen existing mutation results for the open quality paths**

Without adding or renaming result rows, aggregate internal fixtures under the existing related names:

```text
runbook safety: canary rollback heredoc marker cannot spoof post-gate order
  - backslash-newline split heredoc
  - comment-ending backslash does not continue

runbook safety: recovered verifier native check removal is rejected
  - missing PowerShell fence
  - relabelled text fence

runbook safety: wrong-polarity native guard is rejected
  - exit 0 direct guard
  - exit 0 captured guard

runbook safety: nested markdown fence cannot spoof stage 2 gate
  - same-info fence line
  - commented executable gate marker

runbook safety: artifact upload native check removal is rejected
  - `.exe` normalization
  - `.cmd` rejection
  - `cmd.exe /c` rejection
```

Use variant arrays normalized with `@(...) | Where-Object { $null -ne $_ }` so scalar and missing variants behave deterministically.

- [ ] **Step 2: Run `-RunbookSafetyOnly` and verify RED**

Run:

```powershell
powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File .\tests\verify-recovery-mutations.ps1 -SnapshotRoot 'C:\Users\NCTaam\projects\vietnamguide-production-snapshot-20260803' -RunbookSafetyOnly
```

Expected: nonzero exit with only the newly strengthened aggregate cases false; inventory count remains 32 rows including the two baseline rows.

- [ ] **Step 3: Replace raw execution-marker contracts**

In `tests/verify-recovery-baseline.ps1`, replace raw `IndexOf`, `Test-OrderedMarkers`, and raw regex ordering for these areas with event queries:

```text
canary rollback gates
stage 2 rollback gates
stage 2 public/browser/performance/log gates
public verifier placement
atomic publication and installer sequence
native command fail-fast coverage
```

Require expected fence languages when parsing their sections. A required PowerShell section with zero PowerShell fences or a required Bash section with zero Bash fences must add a parser diagnostic failure.

Retain raw text checks only for non-executable document metadata and exact authored content pins; add an adjacent comment explaining why each retained raw check is non-execution-sensitive.

- [ ] **Step 4: Run focused tests, baseline, and safety GREEN**

Expected:

```text
Focused parser: exit 0
Baseline: 231 files, 24/24 probes
Safety: Recovery mutation verification passed: 32/32
```

- [ ] **Step 5: Commit Task 6**

```powershell
git add -- tests/verify-recovery-baseline.ps1 tests/verify-recovery-mutations.ps1 tests/verify-recovery-parser.ps1
git diff --cached --check
git commit -m "test: evaluate recovery contracts from events"
```

---

### Task 7: Remove legacy parsers and complete module-only migration

**Files:**
- Modify: `tests/verify-recovery-baseline.ps1`
- Modify: `tests/lib/RecoveryParser.psm1`
- Modify: `tests/verify-recovery-parser.ps1`

- [ ] **Step 1: Add a structural test that forbids legacy parser definitions**

In `tests/verify-recovery-parser.ps1`, read the baseline script and assert it no longer defines these functions:

```powershell
$baselineText = [System.IO.File]::ReadAllText((Join-Path $PSScriptRoot 'verify-recovery-baseline.ps1'))
$legacyFunctions = @(
    'Get-MarkdownLineRecords','Get-MarkdownFenceMatch','Get-MarkdownVisibleLine',
    'Get-MarkdownSectionText','Get-MarkdownFencedBlocks','Get-BashArithmeticExpansionEnd',
    'Get-BashHeredocRedirections','Test-BashUnquotedLineContinuation','Get-BashLogicalLineRecords',
    'Get-PowerShellNativeCommandName','Test-PowerShellNativeFailFast'
)
$legacyPresent = @($legacyFunctions | Where-Object { $baselineText -match "(?m)^function\s+$([regex]::Escape($_))\b" })
Add-ParserResult -Name 'baseline contains no legacy parsers' -Passed ($legacyPresent.Count -eq 0) -Detail ($legacyPresent -join ', ')
```

- [ ] **Step 2: Run focused tests and verify RED**

Expected: nonzero exit listing legacy functions still defined in the baseline verifier.

- [ ] **Step 3: Delete legacy parser implementations and raw execution ordering helpers**

Remove the baseline copies of all Markdown/Bash/PowerShell parser functions now provided by the module. Remove `Test-OrderedMarkers` wherever it was used for executable behavior. Keep only repository validation, manifest validation, failure aggregation, and rollout-specific event contracts.

Use `rg` to prove migration:

```powershell
rg -n "^function (Get-Markdown|Get-Bash|Get-PowerShell|Test-PowerShellNative|Test-OrderedMarkers)" tests/verify-recovery-baseline.ps1
```

Expected: no matches. An exit code of 1 from `rg` means the structural removal succeeded.

- [ ] **Step 4: Run parser, baseline, safety, and full mutation verification**

Run in this order:

```powershell
powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File .\tests\verify-recovery-parser.ps1
powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File .\tests\verify-recovery-baseline.ps1 -SnapshotRoot 'C:\Users\NCTaam\projects\vietnamguide-production-snapshot-20260803'
powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File .\tests\verify-recovery-mutations.ps1 -SnapshotRoot 'C:\Users\NCTaam\projects\vietnamguide-production-snapshot-20260803' -RunbookSafetyOnly
powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File .\tests\verify-recovery-mutations.ps1 -SnapshotRoot 'C:\Users\NCTaam\projects\vietnamguide-production-snapshot-20260803'
```

Expected: focused parser exit 0; baseline 231/24; safety 32/32; full 77/77.

- [ ] **Step 5: Commit Task 7**

```powershell
git add -- tests/lib/RecoveryParser.psm1 tests/verify-recovery-parser.ps1 tests/verify-recovery-baseline.ps1
git diff --cached --check
git commit -m "refactor: centralize recovery parser core"
```

---

### Task 8: Final invariants, documentation, and review gates

**Files:**
- Modify only if verification exposes a documented defect: `tests/lib/RecoveryParser.psm1`, `tests/verify-recovery-parser.ps1`, `tests/verify-recovery-baseline.ps1`, `tests/verify-recovery-mutations.ps1`
- Verify: `docs/superpowers/specs/2026-08-08-recovery-parser-core-refactor-design.md`

- [ ] **Step 1: Parse every PowerShell artifact with Windows PowerShell 5.1**

Run an AST parse over:

```text
tests/lib/RecoveryParser.psm1
tests/verify-recovery-parser.ps1
tests/verify-recovery-baseline.ps1
tests/verify-recovery-mutations.ps1
```

Expected: zero parse errors for all four files.

- [ ] **Step 2: Verify static inventory exactly**

Parse `$ExpectedMutationNames` from `tests/verify-recovery-mutations.ps1` and require:

```text
TOTAL=77
SAFETY=30
UNIQUE=77
```

Also compare the ordered name array byte-for-byte with commit `b78dfbd` so no result was renamed or reordered during the refactor.

- [ ] **Step 3: Verify source manifest and blob parity**

Require:

```text
Git blob: 4cd169cad33528189f88fcddca41e1db4fb802f0
Canonical SHA-256: e388eca43f45289c9ce821febfc333ea6318b86893e7bb5f100b2b6151890c4e
```

After staging, compare index and filtered-worktree Git object IDs for every changed script/module.

- [ ] **Step 4: Run the complete verification matrix fresh**

Required evidence:

```text
Focused parser: all rows true, exit 0
Direct baseline: 231 files, 24/24 probes, exit 0
Runbook safety: 32/32, exit 0
Full mutations: 77/77, exit 0
git diff --check: exit 0
Worktree: clean after commit
```

- [ ] **Step 5: Request fresh spec review**

Dispatch a read-only reviewer with base `b78dfbd` and final HEAD. The reviewer must check every design success criterion and return `SPEC COMPLIANT` before quality review starts.

- [ ] **Step 6: Request fresh quality review**

After spec compliance, dispatch a different read-only reviewer. Fix every Critical or Important finding with a new TDD commit and repeat both review gates as required.

- [ ] **Step 7: Preserve the isolated branch**

Do not merge into the dirty main worktree. Report the final branch, worktree path, commit chain, verification evidence, and main-worktree integration blocker.

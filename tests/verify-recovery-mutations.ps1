[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$SnapshotRoot,
    [switch]$RunbookSafetyOnly
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$verifier = Join-Path $PSScriptRoot 'verify-recovery-baseline.ps1'
$localHistoryManifestRelative = 'tests\fixtures\recovery-local-history-manifest.json'
$localAuthoredManifestRelative = 'tests\fixtures\recovery-local-authored-manifest.json'
$localOpsManifestRelative = 'tests\fixtures\recovery-local-ops-manifest.json'
$tempParent = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
$tempRoot = Join-Path $tempParent ("vietnamguide-recovery-mutations-" + [guid]::NewGuid().ToString('N'))
$results = [System.Collections.Generic.List[object]]::new()
$ExpectedMutationNames = @(
    'baseline fixture passes',
    'exact local ops stack passes',
    'runbook safety: recovered verifier native check removal is rejected',
    'runbook safety: local build moved native check is rejected',
    'runbook safety: artifact upload native check removal is rejected',
    'runbook safety: production ssh entry native check removal is rejected',
    'runbook safety: reconnect ssh native check removal is rejected',
    'runbook safety: fixture drill native check removal is rejected',
    'runbook safety: final integration native check removal is rejected',
    'runbook safety: stage 2 executable gates cannot swap order',
    'runbook safety: stage 2 ledger close before permanent controls is rejected',
    'runbook safety: exact 18-run browser marker removal is rejected',
    'runbook safety: unsafe 300-second wait before renewal is rejected',
    'runbook safety: exact active pilot and permanent-control inventory is enforced',
    'runbook safety: executable publication move before release directory creation is rejected',
    'runbook safety: publication move without no-target-directory is rejected',
    'runbook safety: installer execution from release container root is rejected',
    'runbook safety: missing post-publication identity verification is rejected',
    'runbook safety: full-stage public HTTP verifier in HTML comment is rejected',
    'runbook safety: wrong-polarity native guard is rejected',
    'runbook safety: nonblocking native guard is rejected',
    'runbook safety: canary rollback pre-gate cache action is rejected',
    'runbook safety: stage 2 rollback pre-gate verification is rejected',
    'runbook safety: canary sleep over 300 seconds is rejected',
    'runbook safety: unprovable canary sleep duration is rejected',
    'runbook safety: browser QA batch missing post-renewal is rejected',
    'runbook safety: early install from install root is rejected',
    'runbook safety: canary rollback heredoc marker cannot spoof post-gate order',
    'runbook safety: stage 2 rollback heredoc marker cannot spoof post-gate order',
    'runbook safety: stage 2 public verifier here-string cannot spoof pre-sync ordering',
    'runbook safety: nested markdown fence cannot spoof stage 2 gate',
    'runbook safety: duplicate stage 2 gate is rejected',
    'local ops manifest missing is rejected',
    'malformed local ops manifest is rejected',
    'local ops content tamper is rejected',
    'arbitrary eleventh ops file is rejected',
    'local ops traversal path is rejected',
    'local ops rooted path is rejected',
    'local ops manifest length mismatch is rejected',
    'local ops manifest hash mismatch is rejected',
    'local ops duplicate entry is rejected',
    'local ops extra manifest entry is rejected',
    'local ops Git mode 120000 is rejected',
    'local ops index no-filter mismatch is rejected',
    're-pinned local ops secret is rejected',
    're-pinned public verifier extra secret is rejected',
    'local history manifest missing is rejected',
    'local authored manifest missing is rejected',
    'local history manifest root shape is rejected',
    'local history schema version is rejected',
    'local authored entries must remain an array',
    'local history manifest entry shape is rejected',
    'local history duplicate entry is rejected',
    'local history extra entry is rejected',
    'local history traversal path is rejected',
    'local history rooted path is rejected',
    'local history length mismatch is rejected',
    'local history hash mismatch is rejected',
    'local history document tamper is rejected',
    'arbitrary third docs extra is rejected',
    'local history index EOL mismatch is rejected',
    'matching source and target tamper is rejected',
    'later key negation is rejected',
    'tracked secret directory is rejected',
    'private key signature is rejected',
    'untracked extensionless private key is rejected',
    'untracked extensionless encrypted private key is rejected',
    'untracked reparse path is rejected',
    'tracked non-ASCII private key path is rejected',
    'untracked non-ASCII secret path is rejected',
    'extensionless private key is rejected',
    'extensionless encrypted private key is rejected',
    'extensionless DSA private key is rejected',
    'recovered index EOL mismatch is rejected',
    'manifest backslash traversal is rejected',
    'manifest slash traversal remains rejected',
    'git mode 120000 is rejected'
)
$ExpectedRunbookSafetyNames = @($ExpectedMutationNames | Where-Object { $_ -like 'runbook safety:*' })
$expectedResultCount = if ($RunbookSafetyOnly) { $ExpectedRunbookSafetyNames.Count + 2 } else { $ExpectedMutationNames.Count }
$expectedRunbookSafetyResultCount = $ExpectedRunbookSafetyNames.Count

function Copy-DirectoryContent {
    param([string]$Source, [string]$Destination)

    New-Item -ItemType Directory -Path $Destination -Force | Out-Null
    Get-ChildItem -LiteralPath $Source -Force | ForEach-Object {
        Copy-Item -LiteralPath $_.FullName -Destination $Destination -Recurse -Force
    }
}

function New-CaseFixture {
    param([string]$Name)

    $caseRoot = Join-Path $tempRoot $Name
    $caseSnapshot = Join-Path $caseRoot 'snapshot'
    $caseRepo = Join-Path $caseRoot 'repo'

    Copy-DirectoryContent -Source (Join-Path $SnapshotRoot 'live-theme') -Destination (Join-Path $caseSnapshot 'live-theme')
    Copy-DirectoryContent -Source (Join-Path $SnapshotRoot 'live-mu-plugins') -Destination (Join-Path $caseSnapshot 'live-mu-plugins')
    Copy-DirectoryContent -Source (Join-Path $SnapshotRoot 'project-webroot\docs') -Destination (Join-Path $caseSnapshot 'project-webroot\docs')
    Copy-DirectoryContent -Source (Join-Path $SnapshotRoot 'project-webroot\ops') -Destination (Join-Path $caseSnapshot 'project-webroot\ops')

    New-Item -ItemType Directory -Path $caseRepo -Force | Out-Null
    $repoFiles = @(git -C $repoRoot ls-files --cached --others --exclude-standard)
    foreach ($relative in $repoFiles) {
        $source = Join-Path $repoRoot $relative
        if (-not (Test-Path -LiteralPath $source -PathType Leaf)) {
            continue
        }

        $destination = Join-Path $caseRepo $relative
        New-Item -ItemType Directory -Path (Split-Path -Parent $destination) -Force | Out-Null
        Copy-Item -LiteralPath $source -Destination $destination -Force
    }

    & git -C $caseRepo init -q
    & git -C $caseRepo config user.name 'recovery-test'
    & git -C $caseRepo config user.email 'recovery-test@example.invalid'
    & git -c core.autocrlf=false -C $caseRepo add -f --all 2>$null

    [pscustomobject]@{
        Snapshot = $caseSnapshot
        Repo = $caseRepo
    }
}

function Invoke-CaseVerifier {
    param(
        [object]$Fixture,
        [string]$AdditionalGitStageEntry = '',
        [string]$VerifierPath = $verifier
    )

    $arguments = @(
        '-NoProfile',
        '-ExecutionPolicy', 'Bypass',
        '-File', $VerifierPath,
        '-SnapshotRoot', $Fixture.Snapshot,
        '-RepositoryRoot', $Fixture.Repo
    )
    if ($AdditionalGitStageEntry) {
        $arguments += @('-AdditionalGitStageEntry', $AdditionalGitStageEntry)
    }

    $previousErrorAction = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $output = & powershell.exe @arguments 2>&1
        $exitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $previousErrorAction
    }
    [pscustomobject]@{
        ExitCode = $exitCode
        Output = ($output -join "`n")
    }
}

function Add-Result {
    param(
        [string]$Name,
        [bool]$Passed,
        [string]$Detail
    )

    $script:results.Add([pscustomobject]@{
        Name = $Name
        Passed = $Passed
        Detail = $Detail
    })
}

function Get-LocalHistoryManifestPath {
    param([object]$Fixture)

    return Join-Path $Fixture.Repo $localHistoryManifestRelative
}

function Get-LocalAuthoredManifestPath {
    param([object]$Fixture)

    return Join-Path $Fixture.Repo $localAuthoredManifestRelative
}

function Get-LocalOpsManifestPath {
    param([object]$Fixture)

    return Join-Path $Fixture.Repo $localOpsManifestRelative
}

function Read-LocalHistoryManifest {
    param([object]$Fixture)

    return Get-Content -Raw -LiteralPath (Get-LocalHistoryManifestPath -Fixture $Fixture) | ConvertFrom-Json
}

function Write-LocalHistoryManifest {
    param(
        [object]$Fixture,
        [object]$Manifest
    )

    $path = Get-LocalHistoryManifestPath -Fixture $Fixture
    [System.IO.File]::WriteAllText($path, ($Manifest | ConvertTo-Json -Depth 10), [System.Text.UTF8Encoding]::new($false))
    & git -c core.autocrlf=false -C $Fixture.Repo add -f -- $path 2>$null
}

function Read-LocalAuthoredManifest {
    param([object]$Fixture)

    return Get-Content -Raw -LiteralPath (Get-LocalAuthoredManifestPath -Fixture $Fixture) | ConvertFrom-Json
}

function Write-LocalAuthoredManifest {
    param(
        [object]$Fixture,
        [object]$Manifest
    )

    $path = Get-LocalAuthoredManifestPath -Fixture $Fixture
    [System.IO.File]::WriteAllText($path, ($Manifest | ConvertTo-Json -Depth 10), [System.Text.UTF8Encoding]::new($false))
    & git -c core.autocrlf=false -C $Fixture.Repo add -f -- $path 2>$null
}

function Get-ExecutionAddendumPath {
    param([object]$Fixture)

    return Join-Path $Fixture.Repo 'docs\superpowers\plans\2026-08-03-vietnamguide-comparison-recovery-execution-addendum.md'
}

function Set-AuthorizedExecutionAddendumText {
    param(
        [object]$Fixture,
        [string]$Text
    )

    $addendumPath = Get-ExecutionAddendumPath -Fixture $Fixture
    [System.IO.File]::WriteAllText($addendumPath, $Text, [System.Text.UTF8Encoding]::new($false))
    $addendumLength = (Get-Item -LiteralPath $addendumPath).Length
    $addendumHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $addendumPath).Hash.ToLowerInvariant()

    $manifest = Read-LocalAuthoredManifest -Fixture $Fixture
    $manifestEntries = @($manifest.entries | Where-Object relativePath -eq 'docs/superpowers/plans/2026-08-03-vietnamguide-comparison-recovery-execution-addendum.md')
    if ($manifestEntries.Count -ne 1) {
        throw 'Expected one local-authored manifest entry for the recovery execution addendum.'
    }
    $manifestEntries[0].length = $addendumLength
    $manifestEntries[0].sha256 = $addendumHash
    Write-LocalAuthoredManifest -Fixture $Fixture -Manifest $manifest

    $fixtureVerifier = Join-Path $Fixture.Repo 'tests\verify-recovery-baseline.ps1'
    $verifierText = [System.IO.File]::ReadAllText($fixtureVerifier)
    $expectedEntryPattern = "(?ms)(relativePath = 'docs/superpowers/plans/2026-08-03-vietnamguide-comparison-recovery-execution-addendum\.md'\r?\n\s+length = )\d+(\r?\n\s+sha256 = ')[0-9a-f]{64}(')"
    $expectedEntryRegex = [regex]::new($expectedEntryPattern)
    if ($expectedEntryRegex.Matches($verifierText).Count -ne 1) {
        throw 'Expected one hardcoded local-authored verifier entry for the recovery execution addendum.'
    }
    $updatedVerifierText = $expectedEntryRegex.Replace($verifierText, ('${1}' + $addendumLength + '${2}' + $addendumHash + '${3}'), 1)
    [System.IO.File]::WriteAllText($fixtureVerifier, $updatedVerifierText, [System.Text.UTF8Encoding]::new($false))

    & git -c core.autocrlf=false -C $Fixture.Repo add -f -- $addendumPath $fixtureVerifier 2>$null
    return $fixtureVerifier
}

function Add-ExecutionAddendumReplacementMutation {
    param(
        [string]$Name,
        [string]$FixtureName,
        [string[]]$OldText,
        [string[]]$NewText,
        [string]$ExpectedFailure,
        [object[]]$Variants = @()
    )

    $mutationCases = [System.Collections.Generic.List[object]]::new()
    $mutationCases.Add(
        [pscustomobject]@{
            FixtureName = $FixtureName
            OldText = $OldText
            NewText = $NewText
            ExpectedFailure = $ExpectedFailure
        }
    )
    foreach ($variant in @($Variants)) {
        if ($null -ne $variant) {
            $mutationCases.Add($variant)
        }
    }
    $passed = $true
    $caseDetails = [System.Collections.Generic.List[string]]::new()
    foreach ($mutationCase in $mutationCases) {
        $caseOldText = @($mutationCase.OldText)
        $caseNewText = @($mutationCase.NewText)
        if ($caseOldText.Count -ne $caseNewText.Count) {
            throw "Runbook mutation replacement count mismatch for $Name."
        }

        $fixture = New-CaseFixture -Name $mutationCase.FixtureName
        $addendumText = [System.IO.File]::ReadAllText((Get-ExecutionAddendumPath -Fixture $fixture))
        for ($replacementIndex = 0; $replacementIndex -lt $caseOldText.Count; $replacementIndex++) {
            $matchCount = ([regex]::Matches($addendumText, [regex]::Escape($caseOldText[$replacementIndex]))).Count
            if ($matchCount -ne 1) {
                throw "Expected exactly one runbook mutation target $replacementIndex for $Name; found $matchCount."
            }
            $addendumText = $addendumText.Replace($caseOldText[$replacementIndex], $caseNewText[$replacementIndex])
        }
        $fixtureVerifier = Set-AuthorizedExecutionAddendumText -Fixture $fixture -Text $addendumText
        $result = Invoke-CaseVerifier -Fixture $fixture -VerifierPath $fixtureVerifier
        if ($result.ExitCode -eq 0 -or $result.Output -notmatch $mutationCase.ExpectedFailure) {
            $passed = $false
        }
        $caseDetails.Add("$($mutationCase.FixtureName):`n$($result.Output)")
    }

    $detail = $caseDetails -join "`n---`n"
    Add-Result -Name $Name -Passed $passed -Detail $detail
}

function Read-LocalOpsManifest {
    param([object]$Fixture)

    return Get-Content -Raw -LiteralPath (Get-LocalOpsManifestPath -Fixture $Fixture) | ConvertFrom-Json
}

function Write-LocalOpsManifest {
    param(
        [object]$Fixture,
        [object]$Manifest
    )

    $path = Get-LocalOpsManifestPath -Fixture $Fixture
    [System.IO.File]::WriteAllText($path, ($Manifest | ConvertTo-Json -Depth 10), [System.Text.UTF8Encoding]::new($false))
    & git -c core.autocrlf=false -C $Fixture.Repo add -f -- $path 2>$null
}

function Add-AuthorizedLocalOpsMutation {
    param(
        [object]$Fixture,
        [string]$RelativePath,
        [string]$Content
    )

    $targetPath = Join-Path $Fixture.Repo $RelativePath
    [System.IO.File]::AppendAllText($targetPath, $Content, [System.Text.UTF8Encoding]::new($false))
    $targetLength = (Get-Item -LiteralPath $targetPath).Length
    $targetHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $targetPath).Hash.ToLowerInvariant()

    $manifest = Read-LocalOpsManifest -Fixture $Fixture
    $manifestEntries = @($manifest.entries | Where-Object relativePath -eq $RelativePath)
    if ($manifestEntries.Count -ne 1) {
        throw "Expected one local-ops manifest entry for $RelativePath."
    }
    $manifestEntries[0].length = $targetLength
    $manifestEntries[0].sha256 = $targetHash
    Write-LocalOpsManifest -Fixture $Fixture -Manifest $manifest

    $fixtureVerifier = Join-Path $Fixture.Repo 'tests\verify-recovery-baseline.ps1'
    $verifierText = [System.IO.File]::ReadAllText($fixtureVerifier)
    $escapedRelativePath = [regex]::Escape($RelativePath)
    $expectedEntryPattern = "(?ms)(relativePath = '$escapedRelativePath'\r?\n\s+length = )\d+(\r?\n\s+sha256 = ')[0-9a-f]{64}(')"
    $expectedEntryRegex = [regex]::new($expectedEntryPattern)
    if ($expectedEntryRegex.Matches($verifierText).Count -ne 1) {
        throw "Expected one hardcoded local-ops verifier entry for $RelativePath."
    }
    $updatedVerifierText = $expectedEntryRegex.Replace($verifierText, ('${1}' + $targetLength + '${2}' + $targetHash + '${3}'), 1)
    [System.IO.File]::WriteAllText($fixtureVerifier, $updatedVerifierText, [System.Text.UTF8Encoding]::new($false))

    & git -c core.autocrlf=false -C $Fixture.Repo add -f -- $targetPath $fixtureVerifier 2>$null
    return $fixtureVerifier
}

function New-VerifiedUntrackedJunction {
    param([object]$Fixture)

    $caseRoot = Split-Path -Parent $Fixture.Repo
    $targetPath = Join-Path $caseRoot 'outside-reparse-target'
    New-Item -ItemType Directory -Path $targetPath -Force | Out-Null
    [System.IO.File]::WriteAllText((Join-Path $targetPath 'payload.txt'), 'reparse fixture', [System.Text.UTF8Encoding]::new($false))

    $relativePath = 'untracked-reparse'
    $junctionPath = Join-Path $Fixture.Repo $relativePath
    New-Item -ItemType Junction -Path $junctionPath -Target $targetPath -Force | Out-Null
    $junction = Get-Item -LiteralPath $junctionPath -Force
    if (($junction.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -eq 0) {
        throw 'Untracked junction fixture is not a reparse point.'
    }

    $untrackedPaths = @(git -c core.quotePath=false -C $Fixture.Repo ls-files --others --exclude-standard --)
    if (@($untrackedPaths | Where-Object { $_ -eq $relativePath -or $_.StartsWith($relativePath + '/') }).Count -eq 0) {
        throw 'Untracked junction fixture is not visible to Git candidate enumeration.'
    }

    return $relativePath
}

try {
    New-Item -ItemType Directory -Path $tempRoot -Force | Out-Null

    $baseline = New-CaseFixture -Name 'baseline'
    $baselineResult = Invoke-CaseVerifier -Fixture $baseline
    Add-Result -Name 'baseline fixture passes' -Passed ($baselineResult.ExitCode -eq 0) -Detail $baselineResult.Output
    Add-Result -Name 'exact local ops stack passes' -Passed ($baselineResult.ExitCode -eq 0) -Detail $baselineResult.Output

    $lineContinuation = [char]96
    $markdownFence = ([string][char]96) * 3
    $closedHtmlCommentChain = '<!--closed--><!--also-closed-->'
    $splicedStage2Heading = '#' + $closedHtmlCommentChain + '# Stage 2 Validate, Apply, Activate, and Close'
    $splicedPowerShellFence = ([string][char]96) + $closedHtmlCommentChain + (([string][char]96) * 2) + 'powershell'
    $runbookSafetyMutations = @(
        [pscustomobject]@{
            Name = 'runbook safety: recovered verifier native check removal is rejected'
            FixtureName = 'runbook-recovered-verifier-native-check'
            OldText = "node --check .\ops\verify-guide-experience-js-runtime.js`nif (`$LASTEXITCODE -ne 0) { throw 'Node syntax check failed: ops/verify-guide-experience-js-runtime.js' }"
            NewText = "`$QuotedValue = 'abc``' # Backticks are literal in single-quoted strings.`nnode.exe --check .\ops\verify-guide-experience-js-runtime.js"
            ExpectedFailure = 'Recovered verifier block native fail-fast contract\s+failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: local build moved native check is rejected'
            FixtureName = 'runbook-local-build-native-check'
            OldText = "powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout.ps1`nif (`$LASTEXITCODE -ne 0) { throw 'Comparison rollout verification failed.' }`npowershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout-mutations.ps1"
            NewText = "`$NestedOutput = `"`$(pwsh -NoProfile -File .\ops\verify-comparison-rollout.ps1)`"`nif (`$LASTEXITCODE -ne 0) { throw 'Comparison rollout verification failed.' }`npowershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout-mutations.ps1"
            ExpectedFailure = 'Local build block native fail-fast contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: artifact upload native check removal is rejected'
            FixtureName = 'runbook-artifact-upload-native-check'
            OldText = "scp -- `$ArtifactZip `"`${ProdUser}@`${ProdHost}:`$RemotePart`"`nif (`$LASTEXITCODE -ne 0) { throw 'Artifact upload failed.' }"
            NewText = "scp.exe -- `$ArtifactZip `"`${ProdUser}@`${ProdHost}:`$RemotePart`"; cmd.exe /c exit 0`nif (`$LASTEXITCODE -ne 0) { throw 'Artifact upload failed.' }"
            ExpectedFailure = 'Artifact upload block native fail-fast contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: production ssh entry native check removal is rejected'
            FixtureName = 'runbook-production-ssh-native-check'
            OldText = "ssh -t `"`$ProdUser@`$ProdHost`" 'sudo -i'`nif (`$LASTEXITCODE -ne 0) { throw 'Unable to enter the approved production root shell.' }"
            NewText = 'ssh.exe -t "$ProdUser@$ProdHost" ''sudo -i'''
            ExpectedFailure = 'Production SSH entry native fail-fast contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: reconnect ssh native check removal is rejected'
            FixtureName = 'runbook-reconnect-ssh-native-check'
            OldText = "ssh -t `"`$ProdUser@`$ProdHost`" 'sudo -i'`nif (`$LASTEXITCODE -ne 0) { throw 'Unable to reconnect to the approved production root shell.' }"
            NewText = 'powershell -NoProfile -Command ''exit 0'''
            ExpectedFailure = 'Reconnect SSH block native fail-fast contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: fixture drill native check removal is rejected'
            FixtureName = 'runbook-fixture-drill-native-check'
            OldText = "    if (`$LASTEXITCODE -ne 0) { throw 'Fixture rollback drill failed.' }"
            NewText = ''
            ExpectedFailure = 'Fixture drill block native fail-fast contract failed'
            Variants = @(
                [pscustomobject]@{
                    FixtureName = 'runbook-unsupported-dynamic-native-check'
                    OldText = "node --check .\ops\verify-guide-experience-js-runtime.js`nif (`$LASTEXITCODE -ne 0) { throw 'Node syntax check failed: ops/verify-guide-experience-js-runtime.js' }"
                    NewText = '& $UnsupportedExecutable --check .\ops\verify-guide-experience-js-runtime.js'
                    ExpectedFailure = 'Recovered verifier block native fail-fast contract\s+failed'
                }
            )
        },
        [pscustomobject]@{
            Name = 'runbook safety: final integration native check removal is rejected'
            FixtureName = 'runbook-final-integration-native-check'
            OldText = "git switch master`nif (`$LASTEXITCODE -ne 0) { throw 'Unable to switch to master for final integration.' }"
            NewText = 'git.exe switch master'
            ExpectedFailure = 'Final integration block native fail-fast contract\s+failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: stage 2 executable gates cannot swap order'
            FixtureName = 'runbook-stage2-sync-before-browser'
            OldText = ('verify_rollout performance-budgets full \' + "`n" +
                '  --max-html-growth-bytes="$MAX_HTML_GROWTH_BYTES" \' + "`n" +
                '  --max-dom-nodes="$MAX_DOM_NODES" \' + "`n" +
                '  --max-scoped-css-bytes="$MAX_SCOPED_CSS_BYTES" \' + "`n" +
                '  --max-php-p95-ms="$MAX_PHP_P95_MS" \' + "`n" +
                '  --max-cls="$MAX_CLS" \' + "`n" +
                '  --max-lcp-regression-percent="$MAX_LCP_REGRESSION_PERCENT"' + "`n" +
                'verify_rollout cache-budgets full --max-warm-queries="$MAX_WARM_QUERIES" --max-cold-queries="$MAX_COLD_QUERIES"')
            NewText = ('verify_rollout cache-budgets full --max-warm-queries="$MAX_WARM_QUERIES" --max-cold-queries="$MAX_COLD_QUERIES"' + "`n" +
                'verify_rollout performance-budgets full \' + "`n" +
                '  --max-html-growth-bytes="$MAX_HTML_GROWTH_BYTES" \' + "`n" +
                '  --max-dom-nodes="$MAX_DOM_NODES" \' + "`n" +
                '  --max-scoped-css-bytes="$MAX_SCOPED_CSS_BYTES" \' + "`n" +
                '  --max-php-p95-ms="$MAX_PHP_P95_MS" \' + "`n" +
                '  --max-cls="$MAX_CLS" \' + "`n" +
                '  --max-lcp-regression-percent="$MAX_LCP_REGRESSION_PERCENT"')
            ExpectedFailure = 'Stage 2 gate ordering contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: stage 2 ledger close before permanent controls is rejected'
            FixtureName = 'runbook-stage2-close-before-controls'
            OldText = "verify_rollout permanent-controls full`nrun_rollout compatibility-sync full`nrun_rollout compatibility-sync full`nverify_rollout compatibility-equivalence full`nrun_rollout recovery-audit full --action=close-ledger --require-final-event=compatibility-sync"
            NewText = "run_rollout recovery-audit full --action=close-ledger --require-final-event=compatibility-sync`nverify_rollout permanent-controls full`nrun_rollout compatibility-sync full`nrun_rollout compatibility-sync full`nverify_rollout compatibility-equivalence full"
            ExpectedFailure = 'Stage 2 gate ordering contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: exact 18-run browser marker removal is rejected'
            FixtureName = 'runbook-browser-matrix-count'
            OldText = 'BROWSER_MATRIX_RUNS_EXPECTED=18'
            NewText = 'BROWSER_MATRIX_RUNS_EXPECTED=17'
            ExpectedFailure = 'Stage 2 browser matrix inventory contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: unsafe 300-second wait before renewal is rejected'
            FixtureName = 'runbook-canary-unsafe-renewal-gap'
            OldText = 'sleep "$LOCK_RENEWAL_INTERVAL_SECONDS"'
            NewText = 'sleep 300'
            ExpectedFailure = 'Canary lock renewal contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: exact active pilot and permanent-control inventory is enforced'
            FixtureName = 'runbook-stage-inventory'
            OldText = "  'compare'`n  'destinations/hanoi-travel-guide'"
            NewText = "  'destinations/hanoi-travel-guide'"
            ExpectedFailure = 'Stage inventory contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: executable publication move before release directory creation is rejected'
            FixtureName = 'runbook-publication-move-before-mkdir'
            OldText = "mkdir -m 0750 `"`$RELEASE_DIR`"`nRELEASE_PAYLOAD_DIR=`"`$RELEASE_DIR/payload`"`ntest ! -e `"`$RELEASE_PAYLOAD_DIR`"`nmv -T -- `"`$INSTALL_ROOT`" `"`$RELEASE_PAYLOAD_DIR`""
            NewText = "mv -T -- `"`$INSTALL_ROOT`" `"`$RELEASE_PAYLOAD_DIR`"`nmkdir -m 0750 `"`$RELEASE_DIR`"`nRELEASE_PAYLOAD_DIR=`"`$RELEASE_DIR/payload`"`ntest ! -e `"`$RELEASE_PAYLOAD_DIR`"`n# mv -T -- `"`$INSTALL_ROOT`" `"`$RELEASE_PAYLOAD_DIR`""
            ExpectedFailure = 'Atomic release publication contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: publication move without no-target-directory is rejected'
            FixtureName = 'runbook-publication-move-without-no-target-directory'
            OldText = 'mv -T -- "$INSTALL_ROOT" "$RELEASE_PAYLOAD_DIR"'
            NewText = 'mv -- "$INSTALL_ROOT" "$RELEASE_PAYLOAD_DIR"'
            ExpectedFailure = 'no-target-directory move is missing'
        },
        [pscustomobject]@{
            Name = 'runbook safety: installer execution from release container root is rejected'
            FixtureName = 'runbook-container-root-install'
            OldText = 'php "$RELEASE_PAYLOAD_DIR/ops/install-comparison-rollout-release.php" install \'
            NewText = 'php "$RELEASE_DIR/ops/install-comparison-rollout-release.php" install \'
            ExpectedFailure = 'Release payload execution-root contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: missing post-publication identity verification is rejected'
            FixtureName = 'runbook-post-publication-identity'
            OldText = "test -f `"`$RELEASE_PAYLOAD_DIR/.vietnamguide-release-sha256`"`ntest `"`$(cat `"`$RELEASE_PAYLOAD_DIR/.vietnamguide-release-sha256`")`" = `"`$VG_ARTIFACT_HASH`"`ntest -f `"`$RELEASE_PAYLOAD_DIR/payload-manifest.json`""
            NewText = "test -f `"`$RELEASE_PAYLOAD_DIR/.vietnamguide-release-sha256`"`ntest -f `"`$RELEASE_PAYLOAD_DIR/payload-manifest.json`""
            ExpectedFailure = 'Post-publication release identity contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: full-stage public HTTP verifier in HTML comment is rejected'
            FixtureName = 'runbook-full-public-verifier-html-comment'
            OldText = @(
                '## Stage 2 Validate, Apply, Activate, and Close',
                ($markdownFence + "powershell`n`$RepoRoot = 'C:\Users\NCTaam\projects\vietnamguide'`nSet-Location -LiteralPath `$RepoRoot`n" +
                    "powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout-public.ps1 $lineContinuation`n    -Stage full $lineContinuation`n    -Origin 'https://vietnamguide.net'`nif (`$LASTEXITCODE -ne 0) { throw 'Full-stage public HTTP verification failed.' }`n" + $markdownFence)
            )
            NewText = @(
                $splicedStage2Heading,
                ($splicedPowerShellFence + "`n`$RepoRoot = 'C:\Users\NCTaam\projects\vietnamguide'`nSet-Location -LiteralPath `$RepoRoot`n" +
                    "powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout-public.ps1 $lineContinuation`n    -Stage full $lineContinuation`n    -Origin 'https://vietnamguide.net'`nif (`$LASTEXITCODE -ne 0) { throw 'Full-stage public HTTP verification failed.' }`n" + $markdownFence)
            )
            ExpectedFailure = 'Stage 2 public HTTP verification contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: wrong-polarity native guard is rejected'
            FixtureName = 'runbook-native-wrong-polarity'
            OldText = "scp -- `$ArtifactZip `"`${ProdUser}@`${ProdHost}:`$RemotePart`"`nif (`$LASTEXITCODE -ne 0) { throw 'Artifact upload failed.' }"
            NewText = "scp -- `$ArtifactZip `"`${ProdUser}@`${ProdHost}:`$RemotePart`" <#closed#><#`nif (`$LASTEXITCODE -ne 0) { throw 'Artifact upload failed.' }`n#>`nif (`$LASTEXITCODE -eq 0) { throw 'Artifact upload failed.' }"
            ExpectedFailure = 'Artifact upload block native fail-fast contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: nonblocking native guard is rejected'
            FixtureName = 'runbook-native-nonblocking-guard'
            OldText = "ssh -t `"`$ProdUser@`$ProdHost`" 'sudo -i'`nif (`$LASTEXITCODE -ne 0) { throw 'Unable to enter the approved production root shell.' }"
            NewText = "ssh -t `"`$ProdUser@`$ProdHost`" 'sudo -i'`nif (`$LASTEXITCODE -ne 0) { exit 0 }"
            ExpectedFailure = 'Production SSH entry native fail-fast contract failed'
            Variants = @(
                [pscustomobject]@{
                    FixtureName = 'runbook-native-captured-exit-zero'
                    OldText = "} else {`n    throw 'Unable to inspect the local master branch.'`n}"
                    NewText = "} else {`n    exit 0`n}"
                    ExpectedFailure = 'Final integration block native fail-fast contract\s+failed'
                }
            )
        },
        [pscustomobject]@{
            Name = 'runbook safety: canary rollback pre-gate cache action is rejected'
            FixtureName = 'runbook-canary-rollback-pre-gate-action'
            OldText = "run_rollout rollback canary`nROLLBACK_EXIT=`$?`nset -e`n`nif [ `"`$ROLLBACK_EXIT`" -ne 0 ]; then"
            NewText = "run_rollout rollback canary`nROLLBACK_EXIT=`$?`nset -e`nwp --path=`"`$WP_ROOT`" --allow-root cache flush`n`nif [ `"`$ROLLBACK_EXIT`" -ne 0 ]; then"
            ExpectedFailure = 'Canary rollback ordering contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: stage 2 rollback pre-gate verification is rejected'
            FixtureName = 'runbook-stage2-rollback-pre-gate-action'
            OldText = "run_rollout rollback full`nROLLBACK_EXIT=`$?`nset -e`n`nif [ `"`$ROLLBACK_EXIT`" -ne 0 ]; then"
            NewText = "run_rollout rollback full`nROLLBACK_EXIT=`$?`nset -e`nverify_rollout baseline-hashes full`n`nif [ `"`$ROLLBACK_EXIT`" -ne 0 ]; then"
            ExpectedFailure = 'Stage 2 rollback ordering contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: canary sleep over 300 seconds is rejected'
            FixtureName = 'runbook-canary-sleep-301'
            OldText = "sleep `"`$LOCK_RENEWAL_INTERVAL_SECONDS`"`n    run_rollout recovery-audit canary --action=renew-lock --ttl-seconds=900`n    sleep `"`$((300 - LOCK_RENEWAL_INTERVAL_SECONDS))`""
            NewText = "sleep `"`$LOCK_RENEWAL_INTERVAL_SECONDS`"`n    run_rollout recovery-audit canary --action=renew-lock --ttl-seconds=900`n    sleep 301`n    sleep `"`$((300 - LOCK_RENEWAL_INTERVAL_SECONDS))`""
            ExpectedFailure = 'Canary lock renewal contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: unprovable canary sleep duration is rejected'
            FixtureName = 'runbook-canary-unprovable-sleep'
            OldText = "sleep `"`$LOCK_RENEWAL_INTERVAL_SECONDS`"`n    run_rollout recovery-audit canary --action=renew-lock --ttl-seconds=900`n    sleep `"`$((300 - LOCK_RENEWAL_INTERVAL_SECONDS))`""
            NewText = "sleep `"`$LOCK_RENEWAL_INTERVAL_SECONDS`"`n    run_rollout recovery-audit canary --action=renew-lock --ttl-seconds=900`n    sleep `"`$UNBOUNDED_WAIT_SECONDS`"`n    sleep `"`$((300 - LOCK_RENEWAL_INTERVAL_SECONDS))`""
            ExpectedFailure = 'Canary lock renewal contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: browser QA batch missing post-renewal is rejected'
            FixtureName = 'runbook-stage2-browser-batch-renewal'
            OldText = "for qa_batch in 1 2 3; do`n  run_rollout recovery-audit full --action=renew-lock --ttl-seconds=900`n  verify_rollout browser-matrix-batch full --batch=`"`$qa_batch`" --expected-runs=`"`$BROWSER_QA_RUNS_PER_BATCH`" --max-duration-seconds=`"`$MAX_QA_BATCH_SECONDS`"`n  run_rollout recovery-audit full --action=renew-lock --ttl-seconds=900`ndone"
            NewText = "for qa_batch in 1 2 3; do`n  run_rollout recovery-audit full --action=renew-lock --ttl-seconds=900`n  verify_rollout browser-matrix-batch full --batch=`"`$qa_batch`" --expected-runs=`"`$BROWSER_QA_RUNS_PER_BATCH`" --max-duration-seconds=`"`$MAX_QA_BATCH_SECONDS`"`ndone"
            ExpectedFailure = 'Stage 2 browser QA renewal contract failed'
        },
        [pscustomobject]@{
            Name = 'runbook safety: early install from install root is rejected'
            FixtureName = 'runbook-early-install-root-execution'
            OldText = "mkdir -m 0750 `"`$RELEASE_DIR`""
            NewText = "php `"`$INSTALL_ROOT/ops/install-comparison-rollout-release.php`" install $lineContinuation`n  --release-root=`"`$INSTALL_ROOT`" $lineContinuation`n  --wordpress-root=`"`$WP_ROOT`" $lineContinuation`n  --state-dir=`"`$STATE_DIR`" $lineContinuation`n  --run-id=`"`$VG_RUN_ID`"`n`nmkdir -m 0750 `"`$RELEASE_DIR`""
            ExpectedFailure = 'Release installer invocation contract failed'
        }
    )
    foreach ($mutation in $runbookSafetyMutations) {
        Add-ExecutionAddendumReplacementMutation `
            -Name $mutation.Name `
            -FixtureName $mutation.FixtureName `
            -OldText $mutation.OldText `
            -NewText $mutation.NewText `
            -ExpectedFailure $mutation.ExpectedFailure `
            -Variants $mutation.Variants
    }

    $canaryRollbackOrdering = New-CaseFixture -Name 'runbook-canary-rollback-ordering'
    $canaryRollbackText = [System.IO.File]::ReadAllText((Get-ExecutionAddendumPath -Fixture $canaryRollbackOrdering))
    $canaryCacheFlush = 'wp --path="$WP_ROOT" --allow-root cache flush'
    $canaryRollbackStart = "set +e`nrun_rollout rollback canary"
    $canaryHeredocMarker = "set +e`n: <<<EOF`n$canaryCacheFlush`nEOF`nrun_rollout rollback canary"
    if ($canaryRollbackText.Contains($canaryRollbackStart)) {
        $canaryRollbackText = $canaryRollbackText.Replace($canaryRollbackStart, $canaryHeredocMarker)
    }
    $canaryRollbackVerifier = Set-AuthorizedExecutionAddendumText -Fixture $canaryRollbackOrdering -Text $canaryRollbackText
    $canaryRollbackResult = Invoke-CaseVerifier -Fixture $canaryRollbackOrdering -VerifierPath $canaryRollbackVerifier

    $canaryLessRunOrdering = New-CaseFixture -Name 'runbook-canary-rollback-five-less-run'
    $canaryLessRunText = [System.IO.File]::ReadAllText((Get-ExecutionAddendumPath -Fixture $canaryLessRunOrdering))
    $canaryLessRunMarker = "set +e`n: <<<<<EOF`n$canaryCacheFlush`nEOF`nrun_rollout rollback canary"
    if ($canaryLessRunText.Contains($canaryRollbackStart)) {
        $canaryLessRunText = $canaryLessRunText.Replace($canaryRollbackStart, $canaryLessRunMarker)
    }
    $canaryLessRunVerifier = Set-AuthorizedExecutionAddendumText -Fixture $canaryLessRunOrdering -Text $canaryLessRunText
    $canaryLessRunResult = Invoke-CaseVerifier -Fixture $canaryLessRunOrdering -VerifierPath $canaryLessRunVerifier

    $canaryDoubleQuoteOrdering = New-CaseFixture -Name 'runbook-canary-rollback-unterminated-double-quote'
    $canaryDoubleQuoteText = [System.IO.File]::ReadAllText((Get-ExecutionAddendumPath -Fixture $canaryDoubleQuoteOrdering))
    $canaryDoubleQuoteMarker = "echo `"unterminated double quote`ncat <<`"E\OF`" <<'ROLLBACK_ORDER_TWO'`nignored first heredoc body`nEOF`nROLLBACK_ORDER_TWO`n$canaryCacheFlush`nE\OF`nignored second heredoc body`nROLLBACK_ORDER_TWO`n$canaryCacheFlush`n`""
    if ($canaryDoubleQuoteText.Contains($canaryCacheFlush)) {
        $canaryDoubleQuoteText = $canaryDoubleQuoteText.Replace($canaryCacheFlush, $canaryDoubleQuoteMarker)
    }
    $canaryDoubleQuoteVerifier = Set-AuthorizedExecutionAddendumText -Fixture $canaryDoubleQuoteOrdering -Text $canaryDoubleQuoteText
    $canaryDoubleQuoteResult = Invoke-CaseVerifier -Fixture $canaryDoubleQuoteOrdering -VerifierPath $canaryDoubleQuoteVerifier

    $canaryContinuedHeredocOrdering = New-CaseFixture -Name 'runbook-canary-rollback-continued-heredoc'
    $canaryContinuedHeredocText = [System.IO.File]::ReadAllText((Get-ExecutionAddendumPath -Fixture $canaryContinuedHeredocOrdering))
    $canaryContinuedHeredocMarker = "cat <\`n<ROLLBACK_CONTINUED`n$canaryCacheFlush`nROLLBACK_CONTINUED"
    if ($canaryContinuedHeredocText.Contains($canaryCacheFlush)) {
        $canaryContinuedHeredocText = $canaryContinuedHeredocText.Replace($canaryCacheFlush, $canaryContinuedHeredocMarker)
    }
    $canaryContinuedHeredocVerifier = Set-AuthorizedExecutionAddendumText -Fixture $canaryContinuedHeredocOrdering -Text $canaryContinuedHeredocText
    $canaryContinuedHeredocResult = Invoke-CaseVerifier -Fixture $canaryContinuedHeredocOrdering -VerifierPath $canaryContinuedHeredocVerifier

    $canaryRollbackPassed = (
        $canaryRollbackResult.ExitCode -ne 0 -and
        $canaryRollbackResult.Output -match 'Canary rollback ordering contract failed' -and
        $canaryLessRunResult.ExitCode -ne 0 -and
        $canaryLessRunResult.Output -match '(?s)Bash heredoc parsing failed:\s+ambiguous\s+redirection:.*?<<<<<EOF' -and
        $canaryDoubleQuoteResult.ExitCode -ne 0 -and
        $canaryDoubleQuoteResult.Output -match '(?s)Bash heredoc parsing failed:\s+ambiguous\s+redirection:.*?echo\s+"unterminated double quote' -and
        $canaryContinuedHeredocResult.ExitCode -ne 0 -and
        $canaryContinuedHeredocResult.Output -match 'Canary rollback ordering contract failed'
    )
    $canaryRollbackDetail = @(
        "exact here-string:`n$($canaryRollbackResult.Output)",
        "five-less run:`n$($canaryLessRunResult.Output)",
        "unterminated double quote:`n$($canaryDoubleQuoteResult.Output)",
        "continued heredoc:`n$($canaryContinuedHeredocResult.Output)"
    ) -join "`n---`n"
    Add-Result -Name 'runbook safety: canary rollback heredoc marker cannot spoof post-gate order' -Passed $canaryRollbackPassed -Detail $canaryRollbackDetail

    $stage2RollbackOrdering = New-CaseFixture -Name 'runbook-stage2-rollback-ordering'
    $stage2RollbackText = [System.IO.File]::ReadAllText((Get-ExecutionAddendumPath -Fixture $stage2RollbackOrdering))
    $stage2CacheFlush = 'wp --path="$WP_ROOT" --allow-root cache flush'
    $stage2HeredocMarker = "echo 'unterminated single quote`n: `$((1 << 2))`n$stage2CacheFlush`n2`n'"
    if ($stage2RollbackText.Contains($stage2CacheFlush)) {
        $stage2RollbackText = $stage2RollbackText.Replace($stage2CacheFlush, $stage2HeredocMarker)
    }
    $stage2RollbackVerifier = Set-AuthorizedExecutionAddendumText -Fixture $stage2RollbackOrdering -Text $stage2RollbackText
    $stage2RollbackResult = Invoke-CaseVerifier -Fixture $stage2RollbackOrdering -VerifierPath $stage2RollbackVerifier
    Add-Result -Name 'runbook safety: stage 2 rollback heredoc marker cannot spoof post-gate order' -Passed ($stage2RollbackResult.ExitCode -ne 0 -and $stage2RollbackResult.Output -match 'Stage 2 rollback ordering contract failed') -Detail $stage2RollbackResult.Output

    $fullPublicAfterSync = New-CaseFixture -Name 'runbook-full-public-after-sync'
    $fullPublicAfterSyncText = [System.IO.File]::ReadAllText((Get-ExecutionAddendumPath -Fixture $fullPublicAfterSync))
    $fullPublicBlock = "powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout-public.ps1 $lineContinuation`n    -Stage full $lineContinuation`n    -Origin 'https://vietnamguide.net'`nif (`$LASTEXITCODE -ne 0) { throw 'Full-stage public HTTP verification failed.' }"
    if ($fullPublicAfterSyncText.Contains($fullPublicBlock)) {
        $fullPublicAfterSyncText = $fullPublicAfterSyncText.Replace($fullPublicBlock, ("`$PublicVerifierSpoof = @'`n  '@`n$fullPublicBlock`n'@"))
    }
    $fullPublicAfterSyncVerifier = Set-AuthorizedExecutionAddendumText -Fixture $fullPublicAfterSync -Text $fullPublicAfterSyncText
    $fullPublicAfterSyncResult = Invoke-CaseVerifier -Fixture $fullPublicAfterSync -VerifierPath $fullPublicAfterSyncVerifier
    Add-Result -Name 'runbook safety: stage 2 public verifier here-string cannot spoof pre-sync ordering' -Passed ($fullPublicAfterSyncResult.ExitCode -ne 0 -and $fullPublicAfterSyncResult.Output -match 'Stage 2 public HTTP verification contract failed') -Detail $fullPublicAfterSyncResult.Output

    $nestedFenceStage2Gate = New-CaseFixture -Name 'runbook-stage2-nested-fence-spoofed-gate'
    $nestedFenceStage2GateText = [System.IO.File]::ReadAllText((Get-ExecutionAddendumPath -Fixture $nestedFenceStage2Gate))
    $outerMarkdownFence = ([string][char]96) * 4
    $splicedOuterFence = ([string][char]96) + '<!--closed-->' + (([string][char]96) * 3)
    $stage2Heading = '## Stage 2 Validate, Apply, Activate, and Close'
    if ($nestedFenceStage2GateText.Contains($stage2Heading)) {
        $nestedFenceStage2GateText = $nestedFenceStage2GateText.Replace(
            $stage2Heading,
            ($splicedOuterFence + "text`nunsafe Stage 2 text`n" + $outerMarkdownFence + "`n" + $stage2Heading)
        )
        $nestedFenceStage2GateText += "`n$outerMarkdownFence`n"
    }
    $nestedFenceStage2GateVerifier = Set-AuthorizedExecutionAddendumText -Fixture $nestedFenceStage2Gate -Text $nestedFenceStage2GateText
    $nestedFenceStage2GateResult = Invoke-CaseVerifier -Fixture $nestedFenceStage2Gate -VerifierPath $nestedFenceStage2GateVerifier

    $sameInfoFenceStage2Gate = New-CaseFixture -Name 'runbook-stage2-same-info-fence-spoofed-gate'
    $sameInfoFenceStage2GateText = [System.IO.File]::ReadAllText((Get-ExecutionAddendumPath -Fixture $sameInfoFenceStage2Gate))
    $bashMarkdownFence = (([string][char]96) * 3) + 'bash'
    if ($sameInfoFenceStage2GateText.Contains($stage2Heading)) {
        $sameInfoFenceStage2GateText = $sameInfoFenceStage2GateText.Replace(
            $stage2Heading,
            ($bashMarkdownFence + "`nignored outer fence text`n" + $bashMarkdownFence + "`n" + $stage2Heading)
        )
        $sameInfoFenceStage2GateText += "`n$markdownFence`n"
    }
    $sameInfoFenceStage2GateVerifier = Set-AuthorizedExecutionAddendumText -Fixture $sameInfoFenceStage2Gate -Text $sameInfoFenceStage2GateText
    $sameInfoFenceStage2GateResult = Invoke-CaseVerifier -Fixture $sameInfoFenceStage2Gate -VerifierPath $sameInfoFenceStage2GateVerifier

    $nestedFenceStage2GatePassed = (
        $nestedFenceStage2GateResult.ExitCode -ne 0 -and
        $nestedFenceStage2GateResult.Output -match 'Recovery execution addendum section missing:\s+##\s+Stage\s+2 Validate, Apply, Activate, and Close' -and
        $sameInfoFenceStage2GateResult.ExitCode -ne 0 -and
        $sameInfoFenceStage2GateResult.Output -match 'Recovery execution addendum section missing:\s+##\s+Stage\s+2 Validate, Apply, Activate, and Close'
    )
    $nestedFenceStage2GateDetail = @(
        "HTML-spliced fence:`n$($nestedFenceStage2GateResult.Output)",
        "same-info fence:`n$($sameInfoFenceStage2GateResult.Output)"
    ) -join "`n---`n"
    Add-Result -Name 'runbook safety: nested markdown fence cannot spoof stage 2 gate' -Passed $nestedFenceStage2GatePassed -Detail $nestedFenceStage2GateDetail

    $duplicateStage2Gate = New-CaseFixture -Name 'runbook-stage2-duplicate-gate'
    $duplicateStage2GateText = [System.IO.File]::ReadAllText((Get-ExecutionAddendumPath -Fixture $duplicateStage2Gate))
    $fullCloseMarker = 'verify_rollout closed full'
    if ($duplicateStage2GateText.Contains($fullCloseMarker)) {
        $duplicateStage2GateText = $duplicateStage2GateText.Replace($fullCloseMarker, ($fullCloseMarker + "`nverify_rollout log-observation full"))
    }
    $duplicateStage2GateVerifier = Set-AuthorizedExecutionAddendumText -Fixture $duplicateStage2Gate -Text $duplicateStage2GateText
    $duplicateStage2GateResult = Invoke-CaseVerifier -Fixture $duplicateStage2Gate -VerifierPath $duplicateStage2GateVerifier
    Add-Result -Name 'runbook safety: duplicate stage 2 gate is rejected' -Passed ($duplicateStage2GateResult.ExitCode -ne 0 -and $duplicateStage2GateResult.Output -match 'Stage 2 gate ordering contract failed') -Detail $duplicateStage2GateResult.Output

    if (-not $RunbookSafetyOnly) {
    $missingLocalOpsManifest = New-CaseFixture -Name 'local-ops-manifest-missing'
    Remove-Item -LiteralPath (Get-LocalOpsManifestPath -Fixture $missingLocalOpsManifest) -Force
    $missingLocalOpsManifestResult = Invoke-CaseVerifier -Fixture $missingLocalOpsManifest
    Add-Result -Name 'local ops manifest missing is rejected' -Passed ($missingLocalOpsManifestResult.ExitCode -ne 0 -and $missingLocalOpsManifestResult.Output -match 'Recovery local-ops manifest missing') -Detail $missingLocalOpsManifestResult.Output

    $malformedLocalOpsManifest = New-CaseFixture -Name 'local-ops-manifest-malformed'
    $malformedLocalOpsManifestPath = Get-LocalOpsManifestPath -Fixture $malformedLocalOpsManifest
    [System.IO.File]::WriteAllText($malformedLocalOpsManifestPath, '{', [System.Text.UTF8Encoding]::new($false))
    & git -c core.autocrlf=false -C $malformedLocalOpsManifest.Repo add -f -- $malformedLocalOpsManifestPath 2>$null
    $malformedLocalOpsManifestResult = Invoke-CaseVerifier -Fixture $malformedLocalOpsManifest
    Add-Result -Name 'malformed local ops manifest is rejected' -Passed ($malformedLocalOpsManifestResult.ExitCode -ne 0 -and $malformedLocalOpsManifestResult.Output -match 'Recovery local-ops manifest parse failed') -Detail $malformedLocalOpsManifestResult.Output

    $localOpsTamper = New-CaseFixture -Name 'local-ops-content-tamper'
    $localOpsTamperPath = Join-Path $localOpsTamper.Repo 'ops\verify-core-block-patterns.ps1'
    [System.IO.File]::AppendAllText($localOpsTamperPath, "`n# mutation`n", [System.Text.UTF8Encoding]::new($false))
    & git -c core.autocrlf=false -C $localOpsTamper.Repo add -f -- $localOpsTamperPath 2>$null
    $localOpsTamperResult = Invoke-CaseVerifier -Fixture $localOpsTamper
    Add-Result -Name 'local ops content tamper is rejected' -Passed ($localOpsTamperResult.ExitCode -ne 0 -and $localOpsTamperResult.Output -match 'Recovery local-ops byte length mismatch') -Detail $localOpsTamperResult.Output

    $eleventhOpsFile = New-CaseFixture -Name 'arbitrary-eleventh-ops-file'
    $eleventhOpsFilePath = Join-Path $eleventhOpsFile.Repo 'ops\verify-unapproved-local.ps1'
    [System.IO.File]::WriteAllText($eleventhOpsFilePath, 'Write-Host ''unapproved''', [System.Text.UTF8Encoding]::new($false))
    & git -c core.autocrlf=false -C $eleventhOpsFile.Repo add -f -- $eleventhOpsFilePath 2>$null
    $eleventhOpsFileResult = Invoke-CaseVerifier -Fixture $eleventhOpsFile
    Add-Result -Name 'arbitrary eleventh ops file is rejected' -Passed ($eleventhOpsFileResult.ExitCode -ne 0 -and $eleventhOpsFileResult.Output -match 'ops unexpected file:\s+verify-unapproved-local\.ps1') -Detail $eleventhOpsFileResult.Output

    $localOpsTraversal = New-CaseFixture -Name 'local-ops-traversal'
    $localOpsTraversalManifest = Read-LocalOpsManifest -Fixture $localOpsTraversal
    $localOpsTraversalManifest.entries[0].relativePath = '../outside.ps1'
    Write-LocalOpsManifest -Fixture $localOpsTraversal -Manifest $localOpsTraversalManifest
    $localOpsTraversalResult = Invoke-CaseVerifier -Fixture $localOpsTraversal
    Add-Result -Name 'local ops traversal path is rejected' -Passed ($localOpsTraversalResult.ExitCode -ne 0 -and $localOpsTraversalResult.Output -match 'Recovery local-ops manifest path\s+is\s+unsafe') -Detail $localOpsTraversalResult.Output

    $localOpsRooted = New-CaseFixture -Name 'local-ops-rooted-path'
    $localOpsRootedManifest = Read-LocalOpsManifest -Fixture $localOpsRooted
    $localOpsRootedManifest.entries[0].relativePath = 'C:/outside.ps1'
    Write-LocalOpsManifest -Fixture $localOpsRooted -Manifest $localOpsRootedManifest
    $localOpsRootedResult = Invoke-CaseVerifier -Fixture $localOpsRooted
    Add-Result -Name 'local ops rooted path is rejected' -Passed ($localOpsRootedResult.ExitCode -ne 0 -and $localOpsRootedResult.Output -match 'Recovery local-ops manifest path\s+is\s+unsafe') -Detail $localOpsRootedResult.Output

    $localOpsLength = New-CaseFixture -Name 'local-ops-length-mismatch'
    $localOpsLengthManifest = Read-LocalOpsManifest -Fixture $localOpsLength
    $localOpsLengthManifest.entries[0].length = [int64]$localOpsLengthManifest.entries[0].length + 1
    Write-LocalOpsManifest -Fixture $localOpsLength -Manifest $localOpsLengthManifest
    $localOpsLengthResult = Invoke-CaseVerifier -Fixture $localOpsLength
    Add-Result -Name 'local ops manifest length mismatch is rejected' -Passed ($localOpsLengthResult.ExitCode -ne 0 -and $localOpsLengthResult.Output -match 'Recovery local-ops manifest length\s+mismatch') -Detail $localOpsLengthResult.Output

    $localOpsHash = New-CaseFixture -Name 'local-ops-hash-mismatch'
    $localOpsHashManifest = Read-LocalOpsManifest -Fixture $localOpsHash
    $localOpsHashManifest.entries[0].sha256 = '0000000000000000000000000000000000000000000000000000000000000000'
    Write-LocalOpsManifest -Fixture $localOpsHash -Manifest $localOpsHashManifest
    $localOpsHashResult = Invoke-CaseVerifier -Fixture $localOpsHash
    Add-Result -Name 'local ops manifest hash mismatch is rejected' -Passed ($localOpsHashResult.ExitCode -ne 0 -and $localOpsHashResult.Output -match 'Recovery local-ops manifest SHA-256\s+mismatch') -Detail $localOpsHashResult.Output

    $localOpsDuplicate = New-CaseFixture -Name 'local-ops-duplicate-entry'
    $localOpsDuplicateManifest = Read-LocalOpsManifest -Fixture $localOpsDuplicate
    $localOpsDuplicateManifest.entries = @($localOpsDuplicateManifest.entries) + @($localOpsDuplicateManifest.entries[0])
    Write-LocalOpsManifest -Fixture $localOpsDuplicate -Manifest $localOpsDuplicateManifest
    $localOpsDuplicateResult = Invoke-CaseVerifier -Fixture $localOpsDuplicate
    Add-Result -Name 'local ops duplicate entry is rejected' -Passed ($localOpsDuplicateResult.ExitCode -ne 0 -and $localOpsDuplicateResult.Output -match 'Recovery local-ops manifest duplicate\s+relative\s+path') -Detail $localOpsDuplicateResult.Output

    $localOpsExtra = New-CaseFixture -Name 'local-ops-extra-entry'
    $localOpsExtraManifest = Read-LocalOpsManifest -Fixture $localOpsExtra
    $localOpsExtraManifest.entries = @($localOpsExtraManifest.entries) + @([pscustomobject]@{
        relativePath = 'ops/verify-unapproved-local.ps1'
        length = 0
        sha256 = '0000000000000000000000000000000000000000000000000000000000000000'
    })
    Write-LocalOpsManifest -Fixture $localOpsExtra -Manifest $localOpsExtraManifest
    $localOpsExtraResult = Invoke-CaseVerifier -Fixture $localOpsExtra
    Add-Result -Name 'local ops extra manifest entry is rejected' -Passed ($localOpsExtraResult.ExitCode -ne 0 -and $localOpsExtraResult.Output -match 'Recovery\s+local-ops\s+manifest\s+entry\s+set\s+is\s+invalid') -Detail $localOpsExtraResult.Output

    $localOpsGitMode = New-CaseFixture -Name 'local-ops-git-symlink-mode'
    $localOpsSymlinkEntry = '120000 ' + ('0' * 40) + ' 0' + "`t" + 'ops/verify-core-block-patterns.ps1'
    $localOpsGitModeResult = Invoke-CaseVerifier -Fixture $localOpsGitMode -AdditionalGitStageEntry $localOpsSymlinkEntry
    Add-Result -Name 'local ops Git mode 120000 is rejected' -Passed ($localOpsGitModeResult.ExitCode -ne 0 -and $localOpsGitModeResult.Output -match 'Git symlink mode 120000') -Detail $localOpsGitModeResult.Output

    $localOpsIndexMismatch = New-CaseFixture -Name 'local-ops-index-mismatch'
    $localOpsIndexRelative = 'ops/verify-core-block-patterns.ps1'
    $localOpsIndexFixture = Join-Path (Split-Path -Parent $localOpsIndexMismatch.Repo) 'local-ops-index-blob.tmp'
    [System.IO.File]::WriteAllText($localOpsIndexFixture, 'different index bytes', [System.Text.UTF8Encoding]::new($false))
    $localOpsIndexObject = (& git -C $localOpsIndexMismatch.Repo hash-object -w --no-filters -- $localOpsIndexFixture).Trim()
    & git -C $localOpsIndexMismatch.Repo update-index --cacheinfo 100644 $localOpsIndexObject $localOpsIndexRelative
    $localOpsIndexMismatchResult = Invoke-CaseVerifier -Fixture $localOpsIndexMismatch
    Add-Result -Name 'local ops index no-filter mismatch is rejected' -Passed ($localOpsIndexMismatchResult.ExitCode -ne 0 -and $localOpsIndexMismatchResult.Output -match 'Git index blob mismatch:\s+ops/verify-core-block-patterns\.ps1') -Detail $localOpsIndexMismatchResult.Output

    $rePinnedCoreSecret = New-CaseFixture -Name 're-pinned-local-ops-secret'
    $rePinnedCoreVerifier = Add-AuthorizedLocalOpsMutation -Fixture $rePinnedCoreSecret -RelativePath 'ops/verify-core-block-patterns.ps1' -Content ("`n" + ('api_' + 'key = "' + 'not-a-real-secret-123456' + '"') + "`n")
    $rePinnedCoreSecretResult = Invoke-CaseVerifier -Fixture $rePinnedCoreSecret -VerifierPath $rePinnedCoreVerifier
    Add-Result -Name 're-pinned local ops secret is rejected' -Passed ($rePinnedCoreSecretResult.ExitCode -ne 0 -and $rePinnedCoreSecretResult.Output -match 'Candidate secret pattern in:\s+ops/verify-core-block-patterns\.ps1') -Detail $rePinnedCoreSecretResult.Output

    $rePinnedPublicSecret = New-CaseFixture -Name 're-pinned-public-verifier-extra-secret'
    $rePinnedPublicVerifier = Add-AuthorizedLocalOpsMutation -Fixture $rePinnedPublicSecret -RelativePath 'ops/verify-guide-experience-public.ps1' -Content ("`n" + ('secret' + ' = "' + 'another-fake-secret-123456' + '"') + "`n")
    $rePinnedPublicSecretResult = Invoke-CaseVerifier -Fixture $rePinnedPublicSecret -VerifierPath $rePinnedPublicVerifier
    Add-Result -Name 're-pinned public verifier extra secret is rejected' -Passed ($rePinnedPublicSecretResult.ExitCode -ne 0 -and $rePinnedPublicSecretResult.Output -match 'Candidate secret pattern in:\s+ops/verify-guide-experience-public\.ps1') -Detail $rePinnedPublicSecretResult.Output

    $missingLocalManifest = New-CaseFixture -Name 'local-history-manifest-missing'
    Remove-Item -LiteralPath (Get-LocalHistoryManifestPath -Fixture $missingLocalManifest) -Force
    $missingLocalManifestResult = Invoke-CaseVerifier -Fixture $missingLocalManifest
    Add-Result -Name 'local history manifest missing is rejected' -Passed ($missingLocalManifestResult.ExitCode -ne 0 -and $missingLocalManifestResult.Output -match 'Recovery local-history manifest missing') -Detail $missingLocalManifestResult.Output

    $missingLocalAuthoredManifest = New-CaseFixture -Name 'local-authored-manifest-missing'
    Remove-Item -LiteralPath (Get-LocalAuthoredManifestPath -Fixture $missingLocalAuthoredManifest) -Force
    $missingLocalAuthoredManifestResult = Invoke-CaseVerifier -Fixture $missingLocalAuthoredManifest
    Add-Result -Name 'local authored manifest missing is rejected' -Passed ($missingLocalAuthoredManifestResult.ExitCode -ne 0 -and $missingLocalAuthoredManifestResult.Output -match 'Recovery local-authored manifest missing') -Detail $missingLocalAuthoredManifestResult.Output

    $localRootShape = New-CaseFixture -Name 'local-history-root-shape'
    $localRootShapeManifest = Read-LocalHistoryManifest -Fixture $localRootShape
    $localRootShapeManifest | Add-Member -NotePropertyName 'unexpected' -NotePropertyValue $true
    Write-LocalHistoryManifest -Fixture $localRootShape -Manifest $localRootShapeManifest
    $localRootShapeResult = Invoke-CaseVerifier -Fixture $localRootShape
    Add-Result -Name 'local history manifest root shape is rejected' -Passed ($localRootShapeResult.ExitCode -ne 0 -and $localRootShapeResult.Output -match 'Recovery local-history manifest root shape\s+is invalid') -Detail $localRootShapeResult.Output

    $localSchemaVersion = New-CaseFixture -Name 'local-history-schema-version'
    $localSchemaVersionManifest = Read-LocalHistoryManifest -Fixture $localSchemaVersion
    $localSchemaVersionManifest.schemaVersion = 2
    Write-LocalHistoryManifest -Fixture $localSchemaVersion -Manifest $localSchemaVersionManifest
    $localSchemaVersionResult = Invoke-CaseVerifier -Fixture $localSchemaVersion
    Add-Result -Name 'local history schema version is rejected' -Passed ($localSchemaVersionResult.ExitCode -ne 0 -and $localSchemaVersionResult.Output -match 'Recovery local-history manifest schema\s+version\s+is invalid') -Detail $localSchemaVersionResult.Output

    $localAuthoredEntriesShape = New-CaseFixture -Name 'local-authored-entries-object'
    $localAuthoredEntriesShapeManifest = Read-LocalAuthoredManifest -Fixture $localAuthoredEntriesShape
    $localAuthoredEntriesShapeManifest.entries = $localAuthoredEntriesShapeManifest.entries[0]
    Write-LocalAuthoredManifest -Fixture $localAuthoredEntriesShape -Manifest $localAuthoredEntriesShapeManifest
    $localAuthoredEntriesShapeResult = Invoke-CaseVerifier -Fixture $localAuthoredEntriesShape
    Add-Result -Name 'local authored entries must remain an array' -Passed ($localAuthoredEntriesShapeResult.ExitCode -ne 0 -and $localAuthoredEntriesShapeResult.Output -match 'Recovery local-authored manifest entries\s+shape\s+is invalid') -Detail $localAuthoredEntriesShapeResult.Output

    $localEntryShape = New-CaseFixture -Name 'local-history-entry-shape'
    $localEntryShapeManifest = Read-LocalHistoryManifest -Fixture $localEntryShape
    $localEntryShapeManifest.entries[0] | Add-Member -NotePropertyName 'unexpected' -NotePropertyValue $true
    Write-LocalHistoryManifest -Fixture $localEntryShape -Manifest $localEntryShapeManifest
    $localEntryShapeResult = Invoke-CaseVerifier -Fixture $localEntryShape
    Add-Result -Name 'local history manifest entry shape is rejected' -Passed ($localEntryShapeResult.ExitCode -ne 0 -and $localEntryShapeResult.Output -match 'Recovery local-history manifest entry shape\s+is invalid') -Detail $localEntryShapeResult.Output

    $localDuplicate = New-CaseFixture -Name 'local-history-duplicate-entry'
    $localDuplicateManifest = Read-LocalHistoryManifest -Fixture $localDuplicate
    $localDuplicateManifest.entries = @($localDuplicateManifest.entries) + @($localDuplicateManifest.entries[0])
    Write-LocalHistoryManifest -Fixture $localDuplicate -Manifest $localDuplicateManifest
    $localDuplicateResult = Invoke-CaseVerifier -Fixture $localDuplicate
    Add-Result -Name 'local history duplicate entry is rejected' -Passed ($localDuplicateResult.ExitCode -ne 0 -and $localDuplicateResult.Output -match 'Recovery local-history manifest duplicate\s+relative\s+path') -Detail $localDuplicateResult.Output

    $localExtra = New-CaseFixture -Name 'local-history-extra-entry'
    $localExtraManifest = Read-LocalHistoryManifest -Fixture $localExtra
    $localExtraManifest.entries = @($localExtraManifest.entries) + @([pscustomobject]@{
        relativePath = 'docs/superpowers/plans/unapproved-local-history.md'
        length = 0
        sha256 = '0000000000000000000000000000000000000000000000000000000000000000'
    })
    Write-LocalHistoryManifest -Fixture $localExtra -Manifest $localExtraManifest
    $localExtraResult = Invoke-CaseVerifier -Fixture $localExtra
    Add-Result -Name 'local history extra entry is rejected' -Passed ($localExtraResult.ExitCode -ne 0 -and $localExtraResult.Output -match 'Recovery local-history manifest entry set\s+is invalid') -Detail $localExtraResult.Output

    $localTraversal = New-CaseFixture -Name 'local-history-traversal'
    $localTraversalManifest = Read-LocalHistoryManifest -Fixture $localTraversal
    $localTraversalManifest.entries[0].relativePath = '../outside.md'
    Write-LocalHistoryManifest -Fixture $localTraversal -Manifest $localTraversalManifest
    $localTraversalResult = Invoke-CaseVerifier -Fixture $localTraversal
    Add-Result -Name 'local history traversal path is rejected' -Passed ($localTraversalResult.ExitCode -ne 0 -and $localTraversalResult.Output -match 'Recovery local-history manifest path\s+is\s+unsafe') -Detail $localTraversalResult.Output

    $localRooted = New-CaseFixture -Name 'local-history-rooted-path'
    $localRootedManifest = Read-LocalHistoryManifest -Fixture $localRooted
    $localRootedManifest.entries[0].relativePath = 'C:/outside.md'
    Write-LocalHistoryManifest -Fixture $localRooted -Manifest $localRootedManifest
    $localRootedResult = Invoke-CaseVerifier -Fixture $localRooted
    Add-Result -Name 'local history rooted path is rejected' -Passed ($localRootedResult.ExitCode -ne 0 -and $localRootedResult.Output -match 'Recovery local-history manifest path\s+is\s+unsafe') -Detail $localRootedResult.Output

    $localLength = New-CaseFixture -Name 'local-history-length-mismatch'
    $localLengthManifest = Read-LocalHistoryManifest -Fixture $localLength
    $localLengthManifest.entries[0].length = [int64]$localLengthManifest.entries[0].length + 1
    Write-LocalHistoryManifest -Fixture $localLength -Manifest $localLengthManifest
    $localLengthResult = Invoke-CaseVerifier -Fixture $localLength
    Add-Result -Name 'local history length mismatch is rejected' -Passed ($localLengthResult.ExitCode -ne 0 -and $localLengthResult.Output -match 'Recovery local-history manifest length\s+mismatch') -Detail $localLengthResult.Output

    $localHash = New-CaseFixture -Name 'local-history-hash-mismatch'
    $localHashManifest = Read-LocalHistoryManifest -Fixture $localHash
    $localHashManifest.entries[0].sha256 = '0000000000000000000000000000000000000000000000000000000000000000'
    Write-LocalHistoryManifest -Fixture $localHash -Manifest $localHashManifest
    $localHashResult = Invoke-CaseVerifier -Fixture $localHash
    Add-Result -Name 'local history hash mismatch is rejected' -Passed ($localHashResult.ExitCode -ne 0 -and $localHashResult.Output -match 'Recovery local-history manifest SHA-256\s+mismatch') -Detail $localHashResult.Output

    $localTamper = New-CaseFixture -Name 'local-history-doc-tamper'
    $localTamperRelative = 'docs/superpowers/specs/2026-08-03-vietnamguide-comparison-diversity-rollout-design.md'
    $localTamperPath = Join-Path $localTamper.Repo $localTamperRelative
    $localTamperBytes = [System.IO.File]::ReadAllBytes($localTamperPath)
    $localTamperBytes[0] = $localTamperBytes[0] -bxor 1
    [System.IO.File]::WriteAllBytes($localTamperPath, $localTamperBytes)
    & git -c core.autocrlf=false -C $localTamper.Repo add -f -- $localTamperPath 2>$null
    $localTamperResult = Invoke-CaseVerifier -Fixture $localTamper
    Add-Result -Name 'local history document tamper is rejected' -Passed ($localTamperResult.ExitCode -ne 0 -and $localTamperResult.Output -match 'Recovery local-history SHA-256 mismatch') -Detail $localTamperResult.Output

    $thirdDocsExtra = New-CaseFixture -Name 'arbitrary-third-docs-extra'
    $thirdDocsExtraPath = Join-Path $thirdDocsExtra.Repo 'docs\superpowers\plans\unapproved-extra.md'
    [System.IO.File]::WriteAllText($thirdDocsExtraPath, 'unapproved docs mutation', [System.Text.UTF8Encoding]::new($false))
    & git -c core.autocrlf=false -C $thirdDocsExtra.Repo add -f -- $thirdDocsExtraPath 2>$null
    $thirdDocsExtraResult = Invoke-CaseVerifier -Fixture $thirdDocsExtra
    Add-Result -Name 'arbitrary third docs extra is rejected' -Passed ($thirdDocsExtraResult.ExitCode -ne 0 -and $thirdDocsExtraResult.Output -match 'docs unexpected file:\s+superpowers/plans/unapproved-extra\.md') -Detail $thirdDocsExtraResult.Output

    $localIndexMismatch = New-CaseFixture -Name 'local-history-index-eol-mismatch'
    $localIndexRelative = 'docs/superpowers/specs/2026-08-03-vietnamguide-comparison-diversity-rollout-design.md'
    $localIndexPath = Join-Path $localIndexMismatch.Repo $localIndexRelative
    $localIndexNormalized = [System.IO.File]::ReadAllText($localIndexPath).Replace("`r`n", "`n").Replace("`n", "`r`n")
    $localIndexBlobFixture = Join-Path (Split-Path -Parent $localIndexMismatch.Repo) 'local-history-crlf-index-blob.tmp'
    [System.IO.File]::WriteAllText($localIndexBlobFixture, $localIndexNormalized, [System.Text.UTF8Encoding]::new($false))
    $localIndexBlobObject = (& git -C $localIndexMismatch.Repo hash-object -w --no-filters -- $localIndexBlobFixture).Trim()
    & git -C $localIndexMismatch.Repo update-index --cacheinfo 100644 $localIndexBlobObject $localIndexRelative
    $localIndexMismatchResult = Invoke-CaseVerifier -Fixture $localIndexMismatch
    Add-Result -Name 'local history index EOL mismatch is rejected' -Passed ($localIndexMismatchResult.ExitCode -ne 0 -and $localIndexMismatchResult.Output -match 'Git index blob mismatch') -Detail $localIndexMismatchResult.Output

    $tampered = New-CaseFixture -Name 'tampered-source-and-target'
    $sourceStyle = Join-Path $tampered.Snapshot 'live-theme\vietnamguide-premium\style.css'
    $targetStyle = Join-Path $tampered.Repo 'wordpress\wp-content\themes\vietnamguide-premium\style.css'
    [System.IO.File]::AppendAllText($sourceStyle, "`n/* mutation */`n")
    [System.IO.File]::AppendAllText($targetStyle, "`n/* mutation */`n")
    & git -c core.autocrlf=false -C $tampered.Repo add -f -- $targetStyle 2>$null
    $tamperedResult = Invoke-CaseVerifier -Fixture $tampered
    Add-Result -Name 'matching source and target tamper is rejected' -Passed ($tamperedResult.ExitCode -ne 0 -and $tamperedResult.Output -match 'manifest digest mismatch') -Detail $tamperedResult.Output

    $unignored = New-CaseFixture -Name 'unignore-key'
    [System.IO.File]::AppendAllText((Join-Path $unignored.Repo '.gitignore'), "`n!leaked.key`n")
    & git -c core.autocrlf=false -C $unignored.Repo add -f -- '.gitignore' 2>$null
    $unignoredResult = Invoke-CaseVerifier -Fixture $unignored
    Add-Result -Name 'later key negation is rejected' -Passed ($unignoredResult.ExitCode -ne 0 -and $unignoredResult.Output -match 'probe not ignored: leaked.key') -Detail $unignoredResult.Output

    $trackedSecret = New-CaseFixture -Name 'tracked-secret-directory'
    $trackedSecretPath = Join-Path $trackedSecret.Repo 'secrets\leaked.txt'
    New-Item -ItemType Directory -Path (Split-Path -Parent $trackedSecretPath) -Force | Out-Null
    [System.IO.File]::WriteAllText($trackedSecretPath, 'mutation fixture')
    & git -c core.autocrlf=false -C $trackedSecret.Repo add -f -- $trackedSecretPath 2>$null
    $trackedSecretResult = Invoke-CaseVerifier -Fixture $trackedSecret
    Add-Result -Name 'tracked secret directory is rejected' -Passed ($trackedSecretResult.ExitCode -ne 0 -and $trackedSecretResult.Output -match 'Forbidden repository path') -Detail $trackedSecretResult.Output

    $privateMarker = New-CaseFixture -Name 'private-key-signature'
    $privateMarkerPath = Join-Path $privateMarker.Repo 'private-material.txt'
    $marker = ('-' * 5) + 'BEGIN ' + 'PRIVATE KEY' + ('-' * 5)
    [System.IO.File]::WriteAllText($privateMarkerPath, $marker)
    & git -c core.autocrlf=false -C $privateMarker.Repo add -f -- $privateMarkerPath 2>$null
    $privateMarkerResult = Invoke-CaseVerifier -Fixture $privateMarker
    Add-Result -Name 'private key signature is rejected' -Passed ($privateMarkerResult.ExitCode -ne 0 -and $privateMarkerResult.Output -match 'Candidate secret pattern') -Detail $privateMarkerResult.Output

    $untrackedPlainKey = New-CaseFixture -Name 'untracked-extensionless-private-key'
    $untrackedPlainKeyRelative = 'untracked_private_identity'
    $untrackedPlainKeyPath = Join-Path $untrackedPlainKey.Repo $untrackedPlainKeyRelative
    [System.IO.File]::WriteAllText($untrackedPlainKeyPath, $marker, [System.Text.UTF8Encoding]::new($false))
    $untrackedPlainKeyResult = Invoke-CaseVerifier -Fixture $untrackedPlainKey
    Add-Result -Name 'untracked extensionless private key is rejected' -Passed ($untrackedPlainKeyResult.ExitCode -ne 0 -and $untrackedPlainKeyResult.Output -match ('Private key signature detected in candidate\s+file:\s+' + [regex]::Escape($untrackedPlainKeyRelative))) -Detail $untrackedPlainKeyResult.Output

    $untrackedEncryptedKey = New-CaseFixture -Name 'untracked-extensionless-encrypted-private-key'
    $untrackedEncryptedKeyRelative = 'untracked_encrypted_identity'
    $untrackedEncryptedKeyPath = Join-Path $untrackedEncryptedKey.Repo $untrackedEncryptedKeyRelative
    $untrackedEncryptedMarker = ('-' * 5) + 'BEGIN ' + 'ENCRYPTED PRIVATE KEY' + ('-' * 5)
    [System.IO.File]::WriteAllText($untrackedEncryptedKeyPath, $untrackedEncryptedMarker, [System.Text.UTF8Encoding]::new($false))
    $untrackedEncryptedKeyResult = Invoke-CaseVerifier -Fixture $untrackedEncryptedKey
    Add-Result -Name 'untracked extensionless encrypted private key is rejected' -Passed ($untrackedEncryptedKeyResult.ExitCode -ne 0 -and $untrackedEncryptedKeyResult.Output -match ('Private key signature detected in candidate\s+file:\s+' + [regex]::Escape($untrackedEncryptedKeyRelative))) -Detail $untrackedEncryptedKeyResult.Output

    $untrackedReparse = New-CaseFixture -Name 'untracked-reparse-path'
    $untrackedReparseRelative = New-VerifiedUntrackedJunction -Fixture $untrackedReparse
    $untrackedReparseResult = Invoke-CaseVerifier -Fixture $untrackedReparse
    Add-Result -Name 'untracked reparse path is rejected' -Passed ($untrackedReparseResult.ExitCode -ne 0 -and $untrackedReparseResult.Output -match ('Candidate reparse point rejected:\s+' + [regex]::Escape($untrackedReparseRelative))) -Detail $untrackedReparseResult.Output

    $trackedNonAscii = New-CaseFixture -Name 'tracked-non-ascii-private-key'
    $trackedNonAsciiRelative = 'tracked-ki' + [char]0x1ec3 + 'm.pem'
    $trackedNonAsciiPath = Join-Path $trackedNonAscii.Repo $trackedNonAsciiRelative
    [System.IO.File]::WriteAllText($trackedNonAsciiPath, $marker, [System.Text.UTF8Encoding]::new($false))
    & git -c core.autocrlf=false -C $trackedNonAscii.Repo add -f -- $trackedNonAsciiPath 2>$null
    $trackedNonAsciiResult = Invoke-CaseVerifier -Fixture $trackedNonAscii
    $trackedNonAsciiPattern = [regex]::Escape($trackedNonAsciiRelative)
    Add-Result -Name 'tracked non-ASCII private key path is rejected' -Passed ($trackedNonAsciiResult.ExitCode -ne 0 -and $trackedNonAsciiResult.Output -match "Private key signature detected in tracked\s+file:\s+$trackedNonAsciiPattern" -and $trackedNonAsciiResult.Output -match "Forbidden repository path:\s+$trackedNonAsciiPattern") -Detail $trackedNonAsciiResult.Output

    $untrackedNonAscii = New-CaseFixture -Name 'untracked-non-ascii-secret'
    $untrackedNonAsciiRelative = 'untracked-ki' + [char]0x1ec3 + 'm.txt'
    $untrackedNonAsciiPath = Join-Path $untrackedNonAscii.Repo $untrackedNonAsciiRelative
    $untrackedNonAsciiContent = 'api_' + 'key = "' + 'not-a-real-secret-123456' + '"'
    [System.IO.File]::WriteAllText($untrackedNonAsciiPath, $untrackedNonAsciiContent, [System.Text.UTF8Encoding]::new($false))
    $untrackedNonAsciiResult = Invoke-CaseVerifier -Fixture $untrackedNonAscii
    Add-Result -Name 'untracked non-ASCII secret path is rejected' -Passed ($untrackedNonAsciiResult.ExitCode -ne 0 -and $untrackedNonAsciiResult.Output -match ('Candidate secret pattern in:\s+' + [regex]::Escape($untrackedNonAsciiRelative))) -Detail $untrackedNonAsciiResult.Output

    $extensionlessKey = New-CaseFixture -Name 'extensionless-private-key'
    $extensionlessKeyPath = Join-Path $extensionlessKey.Repo 'id_rsa'
    [System.IO.File]::WriteAllText($extensionlessKeyPath, $marker)
    & git -c core.autocrlf=false -C $extensionlessKey.Repo add -f -- $extensionlessKeyPath 2>$null
    $extensionlessKeyResult = Invoke-CaseVerifier -Fixture $extensionlessKey
    Add-Result -Name 'extensionless private key is rejected' -Passed ($extensionlessKeyResult.ExitCode -ne 0 -and $extensionlessKeyResult.Output -match 'Private key signature') -Detail $extensionlessKeyResult.Output

    $encryptedKey = New-CaseFixture -Name 'extensionless-encrypted-private-key'
    $encryptedKeyPath = Join-Path $encryptedKey.Repo 'id_encrypted'
    $encryptedMarker = ('-' * 5) + 'BEGIN ' + 'ENCRYPTED PRIVATE KEY' + ('-' * 5)
    [System.IO.File]::WriteAllText($encryptedKeyPath, $encryptedMarker)
    & git -c core.autocrlf=false -C $encryptedKey.Repo add -f -- $encryptedKeyPath 2>$null
    $encryptedKeyResult = Invoke-CaseVerifier -Fixture $encryptedKey
    Add-Result -Name 'extensionless encrypted private key is rejected' -Passed ($encryptedKeyResult.ExitCode -ne 0 -and $encryptedKeyResult.Output -match 'Private key signature') -Detail $encryptedKeyResult.Output

    $dsaKey = New-CaseFixture -Name 'extensionless-dsa-private-key'
    $dsaKeyPath = Join-Path $dsaKey.Repo 'id_dsa'
    $dsaMarker = ('-' * 5) + 'BEGIN ' + 'DSA PRIVATE KEY' + ('-' * 5)
    [System.IO.File]::WriteAllText($dsaKeyPath, $dsaMarker)
    & git -c core.autocrlf=false -C $dsaKey.Repo add -f -- $dsaKeyPath 2>$null
    $dsaKeyResult = Invoke-CaseVerifier -Fixture $dsaKey
    Add-Result -Name 'extensionless DSA private key is rejected' -Passed ($dsaKeyResult.ExitCode -ne 0 -and $dsaKeyResult.Output -match 'Private key signature') -Detail $dsaKeyResult.Output

    $indexMismatch = New-CaseFixture -Name 'recovered-index-eol-mismatch'
    $recoveredRelative = 'ops/verification-log.md'
    $recoveredPath = Join-Path $indexMismatch.Repo $recoveredRelative
    $normalized = [System.IO.File]::ReadAllText($recoveredPath).Replace("`r`n", "`n").Replace("`n", "`r`n")
    $blobFixture = Join-Path (Split-Path -Parent $indexMismatch.Repo) 'crlf-index-blob.tmp'
    [System.IO.File]::WriteAllText($blobFixture, $normalized, [System.Text.UTF8Encoding]::new($false))
    $blobObject = (& git -C $indexMismatch.Repo hash-object -w --no-filters -- $blobFixture).Trim()
    & git -C $indexMismatch.Repo update-index --cacheinfo 100644 $blobObject $recoveredRelative
    $indexMismatchResult = Invoke-CaseVerifier -Fixture $indexMismatch
    Add-Result -Name 'recovered index EOL mismatch is rejected' -Passed ($indexMismatchResult.ExitCode -ne 0 -and $indexMismatchResult.Output -match 'Git index blob mismatch') -Detail $indexMismatchResult.Output

    $backslashTraversal = New-CaseFixture -Name 'manifest-backslash-traversal'
    $backslashManifestPath = Join-Path $backslashTraversal.Repo 'tests\fixtures\recovery-source-manifest.json'
    $backslashManifest = Get-Content -Raw -LiteralPath $backslashManifestPath | ConvertFrom-Json
    ($backslashManifest.sections | Where-Object name -eq 'theme').source = '..\outside'
    [System.IO.File]::WriteAllText($backslashManifestPath, ($backslashManifest | ConvertTo-Json -Depth 10), [System.Text.UTF8Encoding]::new($false))
    & git -c core.autocrlf=false -C $backslashTraversal.Repo add -f -- $backslashManifestPath 2>$null
    $backslashTraversalResult = Invoke-CaseVerifier -Fixture $backslashTraversal
    Add-Result -Name 'manifest backslash traversal is rejected' -Passed ($backslashTraversalResult.ExitCode -ne 0 -and $backslashTraversalResult.Output -match 'manifest path is unsafe') -Detail $backslashTraversalResult.Output

    $slashTraversal = New-CaseFixture -Name 'manifest-slash-traversal'
    $slashManifestPath = Join-Path $slashTraversal.Repo 'tests\fixtures\recovery-source-manifest.json'
    $slashManifest = Get-Content -Raw -LiteralPath $slashManifestPath | ConvertFrom-Json
    ($slashManifest.sections | Where-Object name -eq 'theme').source = '../outside'
    [System.IO.File]::WriteAllText($slashManifestPath, ($slashManifest | ConvertTo-Json -Depth 10), [System.Text.UTF8Encoding]::new($false))
    & git -c core.autocrlf=false -C $slashTraversal.Repo add -f -- $slashManifestPath 2>$null
    $slashTraversalResult = Invoke-CaseVerifier -Fixture $slashTraversal
    Add-Result -Name 'manifest slash traversal remains rejected' -Passed ($slashTraversalResult.ExitCode -ne 0 -and $slashTraversalResult.Output -match 'manifest path is unsafe') -Detail $slashTraversalResult.Output

    $gitMode = New-CaseFixture -Name 'synthetic-git-mode'
    $syntheticEntry = '120000 ' + ('0' * 40) + ' 0' + "`t" + 'synthetic-link'
    $gitModeResult = Invoke-CaseVerifier -Fixture $gitMode -AdditionalGitStageEntry $syntheticEntry
    Add-Result -Name 'git mode 120000 is rejected' -Passed ($gitModeResult.ExitCode -ne 0 -and $gitModeResult.Output -match 'Git symlink mode 120000') -Detail $gitModeResult.Output
    }
} finally {
    $resolvedTempRoot = [System.IO.Path]::GetFullPath($tempRoot)
    if ($resolvedTempRoot.StartsWith($tempParent, [System.StringComparison]::OrdinalIgnoreCase) -and (Test-Path -LiteralPath $resolvedTempRoot)) {
        Remove-Item -LiteralPath $resolvedTempRoot -Recurse -Force
    }
}

$results | Select-Object Name, Passed | Format-Table -AutoSize
$duplicateNames = @($results | Group-Object Name | Where-Object Count -gt 1)
if ($duplicateNames) {
    $duplicateNames | ForEach-Object { Write-Error "Duplicate recovery mutation case name: $($_.Name)" -ErrorAction Continue }
    exit 1
}
$expectedNamesForRun = if ($RunbookSafetyOnly) {
    @('baseline fixture passes', 'exact local ops stack passes') + $ExpectedRunbookSafetyNames
} else {
    @($ExpectedMutationNames)
}
$actualNames = @($results | ForEach-Object { $_.Name })
if ($actualNames.Count -ne $expectedNamesForRun.Count) {
    Write-Error "Recovery mutation exact name count mismatch: expected $($expectedNamesForRun.Count), got $($actualNames.Count)." -ErrorAction Continue
    exit 1
}
for ($nameIndex = 0; $nameIndex -lt $expectedNamesForRun.Count; $nameIndex++) {
    if ($actualNames[$nameIndex] -cne $expectedNamesForRun[$nameIndex]) {
        Write-Error "Recovery mutation exact name/order mismatch at index $nameIndex`: expected '$($expectedNamesForRun[$nameIndex])', got '$($actualNames[$nameIndex])'." -ErrorAction Continue
        exit 1
    }
}
$runbookSafetyResults = @($results | Where-Object Name -like 'runbook safety:*')
if ($runbookSafetyResults.Count -ne $expectedRunbookSafetyResultCount) {
    Write-Error "Runbook safety mutation case count mismatch: expected $expectedRunbookSafetyResultCount, got $($runbookSafetyResults.Count)." -ErrorAction Continue
    exit 1
}
if ($results.Count -ne $expectedResultCount) {
    Write-Error "Recovery mutation case count mismatch: expected $expectedResultCount, got $($results.Count)." -ErrorAction Continue
    exit 1
}
$failed = @($results | Where-Object { -not $_.Passed })
if ($failed) {
    foreach ($failure in $failed) {
        Write-Error "$($failure.Name) failed. $($failure.Detail)" -ErrorAction Continue
    }
    exit 1
}

Write-Host "Recovery mutation verification passed: $($results.Count)/$($results.Count)."
exit 0

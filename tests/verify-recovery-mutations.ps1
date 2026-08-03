[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$SnapshotRoot
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$verifier = Join-Path $PSScriptRoot 'verify-recovery-baseline.ps1'
$tempParent = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
$tempRoot = Join-Path $tempParent ("vietnamguide-recovery-mutations-" + [guid]::NewGuid().ToString('N'))
$results = [System.Collections.Generic.List[object]]::new()

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
        [string]$AdditionalGitStageEntry = ''
    )

    $arguments = @(
        '-NoProfile',
        '-ExecutionPolicy', 'Bypass',
        '-File', $verifier,
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

try {
    New-Item -ItemType Directory -Path $tempRoot -Force | Out-Null

    $baseline = New-CaseFixture -Name 'baseline'
    $baselineResult = Invoke-CaseVerifier -Fixture $baseline
    Add-Result -Name 'baseline fixture passes' -Passed ($baselineResult.ExitCode -eq 0) -Detail $baselineResult.Output

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

    $extensionlessKey = New-CaseFixture -Name 'extensionless-private-key'
    $extensionlessKeyPath = Join-Path $extensionlessKey.Repo 'id_rsa'
    [System.IO.File]::WriteAllText($extensionlessKeyPath, $marker)
    & git -c core.autocrlf=false -C $extensionlessKey.Repo add -f -- $extensionlessKeyPath 2>$null
    $extensionlessKeyResult = Invoke-CaseVerifier -Fixture $extensionlessKey
    Add-Result -Name 'extensionless private key is rejected' -Passed ($extensionlessKeyResult.ExitCode -ne 0 -and $extensionlessKeyResult.Output -match 'Private key signature') -Detail $extensionlessKeyResult.Output

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
} finally {
    $resolvedTempRoot = [System.IO.Path]::GetFullPath($tempRoot)
    if ($resolvedTempRoot.StartsWith($tempParent, [System.StringComparison]::OrdinalIgnoreCase) -and (Test-Path -LiteralPath $resolvedTempRoot)) {
        Remove-Item -LiteralPath $resolvedTempRoot -Recurse -Force
    }
}

$results | Select-Object Name, Passed | Format-Table -AutoSize
$failed = @($results | Where-Object { -not $_.Passed })
if ($failed) {
    foreach ($failure in $failed) {
        Write-Error "$($failure.Name) failed. $($failure.Detail)" -ErrorAction Continue
    }
    exit 1
}

Write-Host "Recovery mutation verification passed: $($results.Count)/$($results.Count)."
exit 0

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

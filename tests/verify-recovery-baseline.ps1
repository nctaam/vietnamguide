[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$SnapshotRoot,
    [string]$RepositoryRoot = '',
    [string[]]$AdditionalGitStageEntry = @()
)

$ErrorActionPreference = 'Stop'
if ([string]::IsNullOrWhiteSpace($RepositoryRoot)) {
    $RepositoryRoot = Split-Path -Parent $PSScriptRoot
}
$repoRoot = [System.IO.Path]::GetFullPath($RepositoryRoot)
$failures = [System.Collections.Generic.List[string]]::new()

function Add-Failure {
    param([string]$Message)

    $script:failures.Add($Message)
}

function Get-RelativeFileMap {
    param(
        [string]$Root,
        [scriptblock]$Include = { $true }
    )

    $map = @{}
    if (-not (Test-Path -LiteralPath $Root -PathType Container)) {
        Add-Failure "Missing directory: $Root"
        return $map
    }

    Get-ChildItem -LiteralPath $Root -Recurse -File -Force |
        Where-Object $Include |
        ForEach-Object {
            $relative = $_.FullName.Substring($Root.Length).TrimStart('\').Replace('\', '/')
            $map[$relative] = $_.FullName
        }

    return $map
}

function Compare-FileTree {
    param(
        [string]$Label,
        [string]$SourceRoot,
        [string]$DestinationRoot,
        [scriptblock]$SourceInclude = { $true },
        [string[]]$AllowedDestinationExtras = @()
    )

    $source = Get-RelativeFileMap -Root $SourceRoot -Include $SourceInclude
    $destination = Get-RelativeFileMap -Root $DestinationRoot

    foreach ($relative in $source.Keys) {
        if (-not $destination.ContainsKey($relative)) {
            Add-Failure "$Label missing file: $relative"
            continue
        }

        $sourceHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $source[$relative]).Hash
        $destinationHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $destination[$relative]).Hash
        if ($sourceHash -ne $destinationHash) {
            Add-Failure "$Label hash mismatch: $relative"
        }
    }

    foreach ($relative in $destination.Keys) {
        if (-not $source.ContainsKey($relative) -and $relative -notin $AllowedDestinationExtras) {
            Add-Failure "$Label unexpected file: $relative"
        }
    }

    [pscustomobject]@{
        Label = $Label
        SourceFiles = $source.Count
        DestinationFiles = $destination.Count
    }
}

function Test-ExcludedRelativePath {
    param(
        [string]$RelativePath,
        [string[]]$Exclude
    )

    foreach ($pattern in $Exclude) {
        if ($RelativePath -like $pattern) {
            return $true
        }
    }

    return $false
}

function Resolve-ContainedManifestPath {
    param(
        [string]$Root,
        [string]$RelativePath,
        [string]$Label
    )

    try {
        $components = @($RelativePath -split '[\\/]')
        if ([string]::IsNullOrWhiteSpace($RelativePath) -or [System.IO.Path]::IsPathRooted($RelativePath) -or '..' -in $components) {
            Add-Failure "Recovery source manifest path is unsafe: $Label"
            return $null
        }

        $rootFull = [System.IO.Path]::GetFullPath($Root).TrimEnd([char[]]'\/')
        $candidate = [System.IO.Path]::GetFullPath((Join-Path $rootFull ($RelativePath.Replace('/', '\'))))
        $rootPrefix = $rootFull + [System.IO.Path]::DirectorySeparatorChar
        if (-not $candidate.StartsWith($rootPrefix, [System.StringComparison]::OrdinalIgnoreCase)) {
            Add-Failure "Recovery source manifest path is unsafe: $Label"
            return $null
        }

        return $candidate
    } catch {
        Add-Failure "Recovery source manifest path is unsafe: $Label"
        return $null
    }
}

function Get-CanonicalSectionDigest {
    param(
        [string]$Path,
        [string[]]$Exclude = @()
    )

    if (-not (Test-Path -LiteralPath $Path)) {
        Add-Failure "Manifest path missing: $Path"
        return [pscustomobject]@{ Count = 0; Digest = ''; Files = @() }
    }

    $resolved = (Resolve-Path -LiteralPath $Path).Path
    if (Test-Path -LiteralPath $resolved -PathType Leaf) {
        $root = Split-Path -Parent $resolved
        $files = @(Get-Item -LiteralPath $resolved -Force)
    } else {
        $root = $resolved.TrimEnd('\')
        $files = @(Get-ChildItem -LiteralPath $resolved -Recurse -File -Force)
    }

    $lines = [System.Collections.Generic.List[string]]::new()
    $includedFiles = [System.Collections.Generic.List[string]]::new()
    foreach ($file in $files) {
        $relative = $file.FullName.Substring($root.Length).TrimStart('\').Replace('\', '/')
        if (Test-ExcludedRelativePath -RelativePath $relative -Exclude $Exclude) {
            continue
        }

        $fileHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $file.FullName).Hash.ToLowerInvariant()
        $lines.Add("$relative`t$fileHash")
        $includedFiles.Add($file.FullName)
    }

    $canonicalLines = $lines.ToArray()
    [Array]::Sort($canonicalLines, [System.StringComparer]::Ordinal)
    $bytes = [System.Text.UTF8Encoding]::new($false).GetBytes(($canonicalLines -join "`n"))
    $sha256 = [System.Security.Cryptography.SHA256]::Create()
    try {
        $digest = ([BitConverter]::ToString($sha256.ComputeHash($bytes))).Replace('-', '').ToLowerInvariant()
    } finally {
        $sha256.Dispose()
    }

    [pscustomobject]@{
        Count = $canonicalLines.Count
        Digest = $digest
        Files = $includedFiles.ToArray()
    }
}

function Test-PrivateKeyHeader {
    param([string]$Path)

    $stream = [System.IO.File]::Open($Path, [System.IO.FileMode]::Open, [System.IO.FileAccess]::Read, [System.IO.FileShare]::ReadWrite)
    try {
        $length = [int][Math]::Min(8192, $stream.Length)
        if ($length -eq 0) {
            return $false
        }
        $buffer = New-Object byte[] $length
        $read = $stream.Read($buffer, 0, $length)
    } finally {
        $stream.Dispose()
    }

    $leadingText = [System.Text.Encoding]::ASCII.GetString($buffer, 0, $read)
    return [regex]::IsMatch(
        $leadingText,
        '-----BEGIN (?:[A-Z0-9][A-Z0-9 -]* )?PRIVATE KEY-----',
        [System.Text.RegularExpressions.RegexOptions]::CultureInvariant
    )
}

function Assert-NoReparsePoint {
    param(
        [string]$Label,
        [string]$Path
    )

    if (-not (Test-Path -LiteralPath $Path)) {
        return
    }

    $items = @(Get-Item -LiteralPath $Path -Force)
    if (Test-Path -LiteralPath $Path -PathType Container) {
        $items += @(Get-ChildItem -LiteralPath $Path -Recurse -Force)
    }

    foreach ($item in $items) {
        if (($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) {
            Add-Failure "$Label reparse point rejected: $($item.FullName)"
        }
    }
}

function Read-ValidatedLocalArtifactManifest {
    param(
        [string]$ManifestPath,
        [string]$Label,
        [object[]]$ExpectedEntries
    )

    $validated = [System.Collections.Generic.List[object]]::new()
    if (-not (Test-Path -LiteralPath $ManifestPath -PathType Leaf)) {
        Add-Failure "$Label manifest missing: $ManifestPath"
        return $validated.ToArray()
    }

    try {
        $manifest = Get-Content -Raw -LiteralPath $ManifestPath | ConvertFrom-Json
    } catch {
        Add-Failure "$Label manifest parse failed: $($_.Exception.Message)"
        return $validated.ToArray()
    }

    $rootKeys = @($manifest.PSObject.Properties.Name)
    $expectedRootKeys = @('schemaVersion', 'entries')
    if (@(Compare-Object -ReferenceObject $expectedRootKeys -DifferenceObject $rootKeys).Count -ne 0) {
        Add-Failure "$Label manifest root shape is invalid."
        return $validated.ToArray()
    }
    if ($manifest.schemaVersion -isnot [int] -or $manifest.schemaVersion -ne 1) {
        Add-Failure "$Label manifest schema version is invalid."
        return $validated.ToArray()
    }
    if ($manifest.entries -isnot [System.Array]) {
        Add-Failure "$Label manifest entries shape is invalid."
        return $validated.ToArray()
    }

    $entries = @($manifest.entries)
    $expectedByPath = [System.Collections.Generic.Dictionary[string, object]]::new([System.StringComparer]::Ordinal)
    foreach ($expected in $ExpectedEntries) {
        $expectedByPath.Add([string]$expected.relativePath, $expected)
    }

    $actualByPath = [System.Collections.Generic.Dictionary[string, object]]::new([System.StringComparer]::Ordinal)
    $shapeValid = $true
    foreach ($entry in $entries) {
        $entryKeys = @($entry.PSObject.Properties.Name)
        $expectedEntryKeys = @('relativePath', 'length', 'sha256')
        if (@(Compare-Object -ReferenceObject $expectedEntryKeys -DifferenceObject $entryKeys).Count -ne 0) {
            Add-Failure "$Label manifest entry shape is invalid."
            $shapeValid = $false
            continue
        }
        if ($entry.relativePath -isnot [string] -or [string]::IsNullOrWhiteSpace($entry.relativePath) -or
            ($entry.length -isnot [int] -and $entry.length -isnot [long]) -or $entry.length -lt 0 -or
            $entry.sha256 -isnot [string] -or $entry.sha256 -notmatch '^[0-9a-f]{64}$') {
            Add-Failure "$Label manifest entry shape is invalid."
            $shapeValid = $false
            continue
        }

        $components = @($entry.relativePath -split '[\\/]')
        $unsafe = [System.IO.Path]::IsPathRooted($entry.relativePath) -or
            $entry.relativePath -match '^[A-Za-z]:' -or
            $entry.relativePath.Contains('\') -or
            $entry.relativePath.StartsWith('/') -or
            $entry.relativePath.EndsWith('/') -or
            '.' -in $components -or '..' -in $components -or '' -in $components
        if ($unsafe) {
            Add-Failure "$Label manifest path is unsafe: $($entry.relativePath)"
            $shapeValid = $false
            continue
        }
        if ($actualByPath.ContainsKey($entry.relativePath)) {
            Add-Failure "$Label manifest duplicate relative path: $($entry.relativePath)"
            $shapeValid = $false
            continue
        }
        $actualByPath.Add($entry.relativePath, $entry)
    }

    $setValid = $shapeValid -and $actualByPath.Count -eq $expectedByPath.Count
    if ($setValid) {
        foreach ($expectedPath in $expectedByPath.Keys) {
            if (-not $actualByPath.ContainsKey($expectedPath)) {
                $setValid = $false
                break
            }
        }
    }
    if (-not $setValid) {
        Add-Failure "$Label manifest entry set is invalid."
        return $validated.ToArray()
    }

    $rootPrefix = $repoRoot.TrimEnd([char[]]'\/') + [System.IO.Path]::DirectorySeparatorChar
    foreach ($relativePath in $expectedByPath.Keys) {
        $entry = $actualByPath[$relativePath]
        $expected = $expectedByPath[$relativePath]
        if ($entry.length -ne $expected.length) {
            Add-Failure "$Label manifest length mismatch: $relativePath"
            continue
        }
        if ($entry.sha256 -cne $expected.sha256) {
            Add-Failure "$Label manifest SHA-256 mismatch: $relativePath"
            continue
        }

        try {
            $fullPath = [System.IO.Path]::GetFullPath((Join-Path $repoRoot $relativePath.Replace('/', '\')))
        } catch {
            Add-Failure "$Label manifest path is unsafe: $relativePath"
            continue
        }
        if (-not $fullPath.StartsWith($rootPrefix, [System.StringComparison]::OrdinalIgnoreCase)) {
            Add-Failure "$Label manifest path is unsafe: $relativePath"
            continue
        }
        if (-not (Test-Path -LiteralPath $fullPath -PathType Leaf)) {
            Add-Failure "$Label file missing: $relativePath"
            continue
        }

        $reparseRejected = $false
        $walkPath = $repoRoot
        foreach ($component in @($relativePath -split '/')) {
            $walkPath = Join-Path $walkPath $component
            $item = Get-Item -LiteralPath $walkPath -Force
            if (($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) {
                Add-Failure "$Label reparse point rejected: $relativePath"
                $reparseRejected = $true
                break
            }
        }
        if ($reparseRejected) {
            continue
        }

        $file = Get-Item -LiteralPath $fullPath -Force
        if ($file.Length -ne [int64]$entry.length) {
            Add-Failure "$Label byte length mismatch: $relativePath"
            continue
        }
        $hash = (Get-FileHash -Algorithm SHA256 -LiteralPath $fullPath).Hash.ToLowerInvariant()
        if ($hash -cne $entry.sha256) {
            Add-Failure "$Label SHA-256 mismatch: $relativePath"
            continue
        }

        $validated.Add([pscustomobject]@{
            RelativePath = $relativePath
            FullPath = $fullPath
        })
    }

    return $validated.ToArray()
}

$themeSource = Join-Path $SnapshotRoot 'live-theme\vietnamguide-premium'
$muSource = Join-Path $SnapshotRoot 'live-mu-plugins\mu-plugins\vietnamguide-core.php'
$docsSource = Join-Path $SnapshotRoot 'project-webroot\docs'
$opsSource = Join-Path $SnapshotRoot 'project-webroot\ops'

$approvedTargetFiles = [System.Collections.Generic.List[string]]::new()
$localDocsExtras = [System.Collections.Generic.List[string]]::new()
$localOpsExtras = [System.Collections.Generic.List[string]]::new()
$validatedLocalOpsRelativePaths = [System.Collections.Generic.List[string]]::new()
$localHistoryManifestPath = Join-Path $repoRoot 'tests\fixtures\recovery-local-history-manifest.json'
$localHistoryExpected = @(
    [pscustomobject]@{
        relativePath = 'docs/superpowers/specs/2026-08-03-vietnamguide-comparison-diversity-rollout-design.md'
        length = 56809
        sha256 = '1a2dd7f387bb03f2b23a53a655f3db390f13e299cb468f171e88b2557f418ded'
    },
    [pscustomobject]@{
        relativePath = 'docs/superpowers/plans/2026-08-03-vietnamguide-comparison-evidence-decision-rollout.md'
        length = 63426
        sha256 = 'a1a874d51fd3ccd43c24c0ada5d0a16008acc54400cbfdced5297ca6877b9af3'
    }
)
$validatedLocalHistory = @(Read-ValidatedLocalArtifactManifest -ManifestPath $localHistoryManifestPath -Label 'Recovery local-history' -ExpectedEntries $localHistoryExpected)
foreach ($entry in $validatedLocalHistory) {
    $approvedTargetFiles.Add($entry.FullPath)
    $localDocsExtras.Add($entry.RelativePath.Substring('docs/'.Length))
}

$localAuthoredManifestPath = Join-Path $repoRoot 'tests\fixtures\recovery-local-authored-manifest.json'
$localAuthoredExpected = @(
    [pscustomobject]@{
        relativePath = 'docs/superpowers/plans/2026-08-03-vietnamguide-comparison-recovery-execution-addendum.md'
        length = 31940
        sha256 = '26118ef81b09941e1101a0effcf67ba1b7a638b5c5cd374ebf5397c47ddb4969'
    }
)
$validatedLocalAuthored = @(Read-ValidatedLocalArtifactManifest -ManifestPath $localAuthoredManifestPath -Label 'Recovery local-authored' -ExpectedEntries $localAuthoredExpected)
foreach ($entry in $validatedLocalAuthored) {
    $approvedTargetFiles.Add($entry.FullPath)
    $localDocsExtras.Add($entry.RelativePath.Substring('docs/'.Length))
}

if ($validatedLocalAuthored.Count -eq 1) {
    $executionAddendumPath = $validatedLocalAuthored[0].FullPath
    $executionAddendumText = [System.IO.File]::ReadAllText($executionAddendumPath)
    $requiredPostDrillMarkers = @(
        '## Reconnect After the Isolated Drill',
        "VG_ARTIFACT_HASH='<same-lowercase-artifact-sha256>'",
        "VG_RUN_ID='<same-closed-full-run-id>'",
        'test -f "$DRILL_SENTINEL_DIR/production.before.json"'
    )
    foreach ($marker in $requiredPostDrillMarkers) {
        if (-not $executionAddendumText.Contains($marker)) {
            Add-Failure "Recovery execution addendum missing copy-safe post-drill marker: $marker"
        }
    }
}

$localOpsManifestPath = Join-Path $repoRoot 'tests\fixtures\recovery-local-ops-manifest.json'
$localOpsExpected = @(
    [pscustomobject]@{
        relativePath = 'ops/verify-core-block-patterns.ps1'
        length = 14206
        sha256 = '30f4be5818b15e4cd8c3ad9b616aeddebd77ff97aa4922a3c89dfaaa35b23c85'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-core-mu-plugin.ps1'
        length = 30539
        sha256 = '1334d35e895a8eac7d5122b482a188f19072ae5b636c46ce44f6257fd1a1419d'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-core-mu-plugin-live.php'
        length = 9743
        sha256 = '280424139dc8b29ba2911d7d3caf1a203499c742efd6d6a243b0d98a7dc8e842'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-homepage-theme.ps1'
        length = 30042
        sha256 = '0c58f228806da1d121bce622d991f738f3d4552c4bf9dbbb3a8640dbb474558b'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-guide-experience.ps1'
        length = 121518
        sha256 = '60f2446f513dae8ff9ea84b6a90823bb8ab35b30dd3699602ca65d41f95b21ec'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-guide-experience-mutations.ps1'
        length = 44511
        sha256 = '8bb44777a33bf89478e54189f7377155b4e17eba7de71f60fae27c88563d6108'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-guide-experience-tokenizer.php'
        length = 25434
        sha256 = '9b65e83952e5d82dc835d6c4d56e6f93c396d9334e877117f7def4bbc99c25f4'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-guide-experience-public.ps1'
        length = 55723
        sha256 = '03b018f463abe474d06b7006a5cdc952d757cd26825d0a1c9700f20f24873543'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-guide-experience-live.php'
        length = 26254
        sha256 = '33bf8f3791bd6b8e5cc6aff1a7cc2dbfa8a7df8df9d639ba262f431879f30643'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-guide-experience-js-runtime.js'
        length = 3703
        sha256 = '031feabe4d0934a13085f9066e42088c941d5dd60be0e649b2b89e2c67eeeb4b'
    }
)
$validatedLocalOps = @(Read-ValidatedLocalArtifactManifest -ManifestPath $localOpsManifestPath -Label 'Recovery local-ops' -ExpectedEntries $localOpsExpected)
if ($validatedLocalOps.Count -eq $localOpsExpected.Count) {
    foreach ($entry in $validatedLocalOps) {
        $approvedTargetFiles.Add($entry.FullPath)
        $validatedLocalOpsRelativePaths.Add($entry.RelativePath)
        $localOpsExtras.Add($entry.RelativePath.Substring('ops/'.Length))
    }
}

$manifestPath = Join-Path $repoRoot 'tests\fixtures\recovery-source-manifest.json'
if (-not (Test-Path -LiteralPath $manifestPath -PathType Leaf)) {
    Add-Failure "Recovery source manifest missing: $manifestPath"
} else {
    try {
        $manifest = Get-Content -Raw -LiteralPath $manifestPath | ConvertFrom-Json
        $rootKeys = @($manifest.PSObject.Properties.Name)
        $expectedRootKeys = @('schemaVersion', 'canonicalFormat', 'sections')
        if (@(Compare-Object -ReferenceObject $expectedRootKeys -DifferenceObject $rootKeys).Count -ne 0) {
            Add-Failure 'Recovery source manifest has unexpected root shape.'
        }
        if ($manifest.schemaVersion -ne 1 -or $manifest.canonicalFormat -ne 'sha256-utf8-lf-ordinal-relative-path-tab-lowercase-file-sha256') {
            Add-Failure 'Recovery source manifest schema or canonical format is invalid.'
        }

        $expectedSectionNames = @('theme', 'mu-plugin', 'docs', 'ops')
        $actualSectionNames = @($manifest.sections | ForEach-Object name)
        if ($manifest.sections.Count -ne 4 -or @(Compare-Object -ReferenceObject $expectedSectionNames -DifferenceObject $actualSectionNames).Count -ne 0) {
            Add-Failure 'Recovery source manifest section set is invalid.'
        }

        foreach ($section in $manifest.sections) {
            $sectionKeys = @($section.PSObject.Properties.Name)
            $expectedSectionKeys = @('name', 'source', 'target', 'count', 'digest', 'exclude')
            if (@(Compare-Object -ReferenceObject $expectedSectionKeys -DifferenceObject $sectionKeys).Count -ne 0) {
                Add-Failure "Recovery source manifest section shape is invalid: $($section.name)"
                continue
            }
            if ($section.count -isnot [int] -or $section.count -lt 1 -or $section.digest -notmatch '^[0-9a-f]{64}$') {
                Add-Failure "Recovery source manifest count or digest is invalid: $($section.name)"
                continue
            }
            $sourcePath = Resolve-ContainedManifestPath -Root $SnapshotRoot -RelativePath $section.source -Label "$($section.name) source"
            $targetPath = Resolve-ContainedManifestPath -Root $repoRoot -RelativePath $section.target -Label "$($section.name) target"
            if (-not $sourcePath -or -not $targetPath) {
                continue
            }

            Assert-NoReparsePoint -Label "$($section.name) source" -Path $sourcePath
            Assert-NoReparsePoint -Label "$($section.name) repository" -Path $targetPath

            $sourceDigest = Get-CanonicalSectionDigest -Path $sourcePath -Exclude @($section.exclude)
            $targetExclude = @($section.exclude)
            if ($section.name -eq 'docs') {
                $targetExclude += @($localDocsExtras)
            } elseif ($section.name -eq 'ops') {
                $targetExclude += @($localOpsExtras)
            }
            $targetDigest = Get-CanonicalSectionDigest -Path $targetPath -Exclude $targetExclude
            if ($sourceDigest.Count -ne $section.count -or $sourceDigest.Digest -ne $section.digest) {
                Add-Failure "$($section.name) source manifest digest mismatch."
            }
            $targetDigestValid = $targetDigest.Count -eq $section.count -and $targetDigest.Digest -eq $section.digest
            if (-not $targetDigestValid) {
                Add-Failure "$($section.name) repository manifest digest mismatch."
            }
            if ($targetDigestValid) {
                foreach ($file in $targetDigest.Files) {
                    $approvedTargetFiles.Add($file)
                }
            }
        }
    } catch {
        Add-Failure "Recovery source manifest parse failed: $($_.Exception.Message)"
    }
}

$results = @()
$results += Compare-FileTree -Label 'theme' -SourceRoot $themeSource -DestinationRoot (Join-Path $repoRoot 'wordpress\wp-content\themes\vietnamguide-premium')
$results += Compare-FileTree -Label 'docs' -SourceRoot $docsSource -DestinationRoot (Join-Path $repoRoot 'docs') -AllowedDestinationExtras (@('RECOVERY.md') + @($localDocsExtras))
$results += Compare-FileTree -Label 'ops' -SourceRoot $opsSource -DestinationRoot (Join-Path $repoRoot 'ops') -SourceInclude {
    $_.FullName -notlike "$(Join-Path $opsSource 'backups')*" -and $_.Extension -ne '.sql'
} -AllowedDestinationExtras @($localOpsExtras)

$muDestination = Join-Path $repoRoot 'wordpress\wp-content\mu-plugins\vietnamguide-core.php'
if (-not (Test-Path -LiteralPath $muSource -PathType Leaf)) {
    Add-Failure "Missing source MU plugin: $muSource"
} elseif (-not (Test-Path -LiteralPath $muDestination -PathType Leaf)) {
    Add-Failure "Missing recovered MU plugin: $muDestination"
} elseif ((Get-FileHash -Algorithm SHA256 -LiteralPath $muSource).Hash -ne (Get-FileHash -Algorithm SHA256 -LiteralPath $muDestination).Hash) {
    Add-Failure 'MU plugin hash mismatch.'
}

$gitignorePath = Join-Path $repoRoot '.gitignore'
$requiredIgnoreRules = @(
    '.superpowers/',
    '.worktrees/',
    '.codex/config.toml',
    '*.sql',
    '*.wxr',
    '*.wpress',
    '**/backups/',
    '**/snapshots/',
    '**/exports/',
    '**/secret/',
    '**/secrets/',
    '**/credential/',
    '**/credentials/',
    'wordpress/wp-content/uploads/',
    '*production-snapshot*/',
    '*recovery-export*/',
    '.env',
    '.env.*',
    'wp-config.php',
    '*.pem',
    '*.key',
    '*.p12',
    '*.pfx',
    '*.jks',
    '*.keystore'
)

if (-not (Test-Path -LiteralPath $gitignorePath -PathType Leaf)) {
    Add-Failure 'Missing .gitignore.'
} else {
    $ignoreLines = Get-Content -LiteralPath $gitignorePath
    foreach ($rule in $requiredIgnoreRules) {
        if ($rule -notin $ignoreLines) {
            Add-Failure ".gitignore missing rule: $rule"
        }
    }
}

$gitattributesPath = Join-Path $repoRoot '.gitattributes'
$requiredAttributeRules = @(
    'wordpress/** -text',
    'ops/** -text',
    'docs/editorial/** -text',
    'docs/superpowers/** -text'
)
if (-not (Test-Path -LiteralPath $gitattributesPath -PathType Leaf)) {
    Add-Failure 'Missing .gitattributes.'
} else {
    $attributeRules = Get-Content -LiteralPath $gitattributesPath
    foreach ($rule in $requiredAttributeRules) {
        if ($rule -notin $attributeRules) {
            Add-Failure ".gitattributes missing rule: $rule"
        }
    }
}

$ignoreProbePaths = @(
    '.superpowers/state.json',
    '.worktrees/check/file',
    '.codex/config.toml',
    'database.sql',
    'export.wxr',
    'backup.wpress',
    'ops/backups/file.php',
    'wordpress/wp-content/uploads/file.jpg',
    'vietnamguide-production-snapshot-test/file',
    'recovery-export-test/file',
    '.env',
    'private.key',
    'leaked.key',
    'private.pem',
    'identity.p12',
    'identity.pfx',
    'identity.jks',
    'identity.keystore',
    'snapshots/artifact.bin',
    'nested/exports/artifact.bin',
    'nested/secret/artifact.bin',
    'nested/deeper/secrets/artifact.bin',
    'nested/credential/artifact.bin',
    'nested/deeper/credentials/artifact.bin'
)
$ignoredProbeCount = 0

foreach ($probe in $ignoreProbePaths) {
    & git -C $repoRoot check-ignore --no-index -q -- $probe
    if ($LASTEXITCODE -eq 0) {
        $ignoredProbeCount++
    } else {
        Add-Failure ".gitignore probe not ignored: $probe"
    }
}

Write-Host "Ignore probes: $ignoredProbeCount/$($ignoreProbePaths.Count)"

$candidatePaths = @(git -C $repoRoot ls-files --cached --others --exclude-standard) |
    Where-Object { $_ } |
    ForEach-Object { $_.Replace('\', '/') }

$gitStageEntries = @(git -C $repoRoot ls-files --stage) + @($AdditionalGitStageEntry)
$indexEntries = @{}
foreach ($entry in $gitStageEntries) {
    if ($entry -match '^(\d{6})\s+([0-9a-f]{40,64})\s+\d+\s+(.+)$') {
        $mode = $Matches[1]
        $objectId = $Matches[2]
        $path = $Matches[3].Replace('\', '/')
        $indexEntries[$path] = [pscustomobject]@{ Mode = $mode; ObjectId = $objectId }
        if ($mode -eq '120000') {
            Add-Failure "Git symlink mode 120000 rejected: $path"
        }
    }
}

$repoPrefix = $repoRoot.TrimEnd('\') + '\'
$approvedTargetPaths = @($approvedTargetFiles | ForEach-Object {
    $_.Substring($repoPrefix.Length).Replace('\', '/')
} | Sort-Object -Unique)
$bytePreservedIndexPaths = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::Ordinal)

if ($approvedTargetPaths.Count -gt 0) {
    $attributeResults = @(git -C $repoRoot check-attr text -- $approvedTargetPaths)
    $bytePreservedAttributePaths = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::Ordinal)
    if ($attributeResults.Count -ne $approvedTargetPaths.Count) {
        Add-Failure 'Unable to verify .gitattributes for every recovered file.'
    } else {
        foreach ($result in $attributeResults) {
            if ($result -notmatch ': text: unset$') {
                Add-Failure ".gitattributes does not preserve recovered bytes: $result"
            } else {
                $attributePath = $result.Substring(0, $result.Length - ': text: unset'.Length)
                $null = $bytePreservedAttributePaths.Add($attributePath)
            }
        }
    }

    $workingObjectIds = @(git -C $repoRoot hash-object --no-filters -- $approvedTargetPaths)
    if ($workingObjectIds.Count -ne $approvedTargetPaths.Count) {
        Add-Failure 'Unable to hash every recovered working-tree file.'
    } else {
        for ($index = 0; $index -lt $approvedTargetPaths.Count; $index++) {
            $path = $approvedTargetPaths[$index]
            if (-not $indexEntries.ContainsKey($path)) {
                Add-Failure "Recovered file missing from Git index: $path"
            } elseif ($indexEntries[$path].ObjectId -ne $workingObjectIds[$index]) {
                Add-Failure "Git index blob mismatch: $path"
            } elseif ($bytePreservedAttributePaths.Contains($path)) {
                $null = $bytePreservedIndexPaths.Add($path)
            }
        }
    }
}

$localOpsSecretScanExceptions = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::Ordinal)
if (
    $validatedLocalOpsRelativePaths.Count -eq $localOpsExpected.Count -and
    @($validatedLocalOpsRelativePaths | Where-Object { -not $bytePreservedIndexPaths.Contains($_) }).Count -eq 0
) {
    foreach ($relativePath in $validatedLocalOpsRelativePaths) {
        $null = $localOpsSecretScanExceptions.Add($relativePath)
    }
}

foreach ($relative in $indexEntries.Keys) {
    if ($indexEntries[$relative].Mode -notmatch '^100') {
        continue
    }
    $fullPath = Join-Path $repoRoot $relative
    if (-not (Test-Path -LiteralPath $fullPath -PathType Leaf)) {
        continue
    }
    $item = Get-Item -LiteralPath $fullPath -Force
    if (($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) {
        Add-Failure "Tracked reparse point rejected: $relative"
        continue
    }
    if (Test-PrivateKeyHeader -Path $fullPath) {
        Add-Failure "Private key signature detected in tracked file: $relative"
    }
}

$forbiddenPathPatterns = @(
    '(^|/)wp-config\.php$',
    '\.(sql|sqlite|sqlite3|db|dump)$',
    '\.wxr$',
    '\.wpress$',
    '\.(pem|key|p12|pfx|jks|keystore)$',
    '(^|/)uploads/',
    '(^|/)backups?/',
    '(^|/)(secret|secrets|credential|credentials)/',
    '(^|/)\.codex/config\.toml$',
    'production-snapshot',
    'recovery-export'
)

foreach ($path in $candidatePaths) {
    foreach ($pattern in $forbiddenPathPatterns) {
        if ($path -match $pattern) {
            Add-Failure "Forbidden repository path: $path"
            break
        }
    }
}

$textExtensions = @('.css', '.env', '.html', '.htm', '.ini', '.js', '.json', '.key', '.md', '.pem', '.php', '.ps1', '.svg', '.toml', '.txt', '.xml', '.yaml', '.yml')
$secretPatterns = @(
    ('X-' + 'Goog-' + 'Api-' + 'Key'),
    'BEGIN(?: [A-Z0-9]+)* PRIVATE KEY',
    'AKIA[0-9A-Z]{16}',
    'gh[pousr]_[A-Za-z0-9]{20,}',
    '(?i)\b(password|passwd|pwd|token)\b\s*(?::|=(?!>))\s*(?:["''][^"'']+["'']|[A-Za-z0-9._-]{8,})(?=\s*[,;#\r\n]|$)',
    '(?i)(api[_-]?key|secret|token)\s*[:=]\s*["''][A-Za-z0-9_-]{12,}["'']',
    '(?i)\b[a-z][a-z0-9+.-]*://[^/\s:@]+:[^/\s@]+@'
)

foreach ($relative in $candidatePaths) {
    if ($localOpsSecretScanExceptions.Contains($relative)) {
        continue
    }

    $fullPath = Join-Path $repoRoot $relative
    if (-not (Test-Path -LiteralPath $fullPath -PathType Leaf)) {
        continue
    }

    $extension = [System.IO.Path]::GetExtension($fullPath).ToLowerInvariant()
    if ($extension -notin $textExtensions -and [System.IO.Path]::GetFileName($fullPath) -ne '.gitignore') {
        continue
    }

    $matches = Select-String -LiteralPath $fullPath -Pattern $secretPatterns -AllMatches -ErrorAction SilentlyContinue
    if ($matches) {
        Add-Failure "Candidate secret pattern in: $relative"
    }
}

$results | Format-Table -AutoSize
if ($failures.Count -gt 0) {
    $failures | ForEach-Object { Write-Error $_ -ErrorAction Continue }
    exit 1
}

Write-Host "Recovery baseline verification passed for $($candidatePaths.Count) repository files."

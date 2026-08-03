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

function Test-ContainsByteSequence {
    param(
        [byte[]]$Buffer,
        [byte[]]$Needle,
        [int]$Length
    )

    if ($Needle.Length -eq 0 -or $Length -lt $Needle.Length) {
        return $false
    }

    for ($offset = 0; $offset -le $Length - $Needle.Length; $offset++) {
        $matched = $true
        for ($index = 0; $index -lt $Needle.Length; $index++) {
            if ($Buffer[$offset + $index] -ne $Needle[$index]) {
                $matched = $false
                break
            }
        }
        if ($matched) {
            return $true
        }
    }

    return $false
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

    foreach ($type in @('', 'RSA ', 'EC ', 'OPENSSH ')) {
        $marker = [System.Text.Encoding]::ASCII.GetBytes(('-----BEGIN ' + $type + 'PRIVATE KEY-----'))
        if (Test-ContainsByteSequence -Buffer $buffer -Needle $marker -Length $read) {
            return $true
        }
    }

    return $false
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

$themeSource = Join-Path $SnapshotRoot 'live-theme\vietnamguide-premium'
$muSource = Join-Path $SnapshotRoot 'live-mu-plugins\mu-plugins\vietnamguide-core.php'
$docsSource = Join-Path $SnapshotRoot 'project-webroot\docs'
$opsSource = Join-Path $SnapshotRoot 'project-webroot\ops'

$manifestPath = Join-Path $repoRoot 'tests\fixtures\recovery-source-manifest.json'
$approvedTargetFiles = [System.Collections.Generic.List[string]]::new()
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
            $targetDigest = Get-CanonicalSectionDigest -Path $targetPath -Exclude @($section.exclude)
            if ($sourceDigest.Count -ne $section.count -or $sourceDigest.Digest -ne $section.digest) {
                Add-Failure "$($section.name) source manifest digest mismatch."
            }
            if ($targetDigest.Count -ne $section.count -or $targetDigest.Digest -ne $section.digest) {
                Add-Failure "$($section.name) repository manifest digest mismatch."
            }
            foreach ($file in $targetDigest.Files) {
                $approvedTargetFiles.Add($file)
            }
        }
    } catch {
        Add-Failure "Recovery source manifest parse failed: $($_.Exception.Message)"
    }
}

$results = @()
$results += Compare-FileTree -Label 'theme' -SourceRoot $themeSource -DestinationRoot (Join-Path $repoRoot 'wordpress\wp-content\themes\vietnamguide-premium')
$results += Compare-FileTree -Label 'docs' -SourceRoot $docsSource -DestinationRoot (Join-Path $repoRoot 'docs') -AllowedDestinationExtras @('RECOVERY.md')
$results += Compare-FileTree -Label 'ops' -SourceRoot $opsSource -DestinationRoot (Join-Path $repoRoot 'ops') -SourceInclude {
    $_.FullName -notlike "$(Join-Path $opsSource 'backups')*" -and $_.Extension -ne '.sql'
}

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

if ($approvedTargetPaths.Count -gt 0) {
    $attributeResults = @($approvedTargetPaths | git -C $repoRoot check-attr --stdin text)
    if ($attributeResults.Count -ne $approvedTargetPaths.Count) {
        Add-Failure 'Unable to verify .gitattributes for every recovered file.'
    } else {
        foreach ($result in $attributeResults) {
            if ($result -notmatch ': text: unset$') {
                Add-Failure ".gitattributes does not preserve recovered bytes: $result"
            }
        }
    }

    $workingObjectIds = @($approvedTargetPaths | git -C $repoRoot hash-object --no-filters --stdin-paths)
    if ($workingObjectIds.Count -ne $approvedTargetPaths.Count) {
        Add-Failure 'Unable to hash every recovered working-tree file.'
    } else {
        for ($index = 0; $index -lt $approvedTargetPaths.Count; $index++) {
            $path = $approvedTargetPaths[$index]
            if (-not $indexEntries.ContainsKey($path)) {
                Add-Failure "Recovered file missing from Git index: $path"
            } elseif ($indexEntries[$path].ObjectId -ne $workingObjectIds[$index]) {
                Add-Failure "Git index blob mismatch: $path"
            }
        }
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

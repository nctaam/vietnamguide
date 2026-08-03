[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$SnapshotRoot
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
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

$themeSource = Join-Path $SnapshotRoot 'live-theme\vietnamguide-premium'
$muSource = Join-Path $SnapshotRoot 'live-mu-plugins\mu-plugins\vietnamguide-core.php'
$docsSource = Join-Path $SnapshotRoot 'project-webroot\docs'
$opsSource = Join-Path $SnapshotRoot 'project-webroot\ops'

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
    'wordpress/wp-content/uploads/',
    '*production-snapshot*/',
    '*recovery-export*/',
    '.env',
    '.env.*',
    'wp-config.php',
    '*.pem',
    '*.key'
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

$candidatePaths = @(git -C $repoRoot ls-files --cached --others --exclude-standard) |
    Where-Object { $_ } |
    ForEach-Object { $_.Replace('\', '/') }

$forbiddenPathPatterns = @(
    '(^|/)wp-config\.php$',
    '\.sql$',
    '\.wxr$',
    '\.wpress$',
    '(^|/)uploads/',
    '(^|/)backups?/',
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

$textExtensions = @('.css', '.env', '.html', '.htm', '.ini', '.js', '.json', '.md', '.php', '.ps1', '.svg', '.toml', '.txt', '.xml', '.yaml', '.yml')
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

param(
    [string]$RepoRootOverride = ''
)

$ErrorActionPreference = 'Stop'
$RepoRoot = if ($RepoRootOverride) { $RepoRootOverride } else { Split-Path -Parent $PSScriptRoot }
$ThemeRoot = 'wordpress/wp-content/themes/vietnamguide-premium'
$Failures = [System.Collections.Generic.List[string]]::new()

function Get-RepoContent {
    param([string]$RelativePath)
    $FullPath = Join-Path $RepoRoot $RelativePath
    if (-not (Test-Path -LiteralPath $FullPath -PathType Leaf)) { return $null }
    $Content = Get-Content -LiteralPath $FullPath -Raw
    if ($null -eq $Content) { return '' }
    return $Content
}

function Require-File {
    param([string]$RelativePath)
    if ($null -eq (Get-RepoContent $RelativePath)) { $Failures.Add("Missing file: $RelativePath") }
}

function Require-Contains {
    param([string]$RelativePath, [string]$Needle)
    $Content = Get-RepoContent $RelativePath
    if ($null -ne $Content -and -not $Content.Contains($Needle)) {
        $Failures.Add("Missing substring in ${RelativePath}: $Needle")
    }
}

function Require-NotContains {
    param([string]$RelativePath, [string]$Needle)
    $Content = Get-RepoContent $RelativePath
    if ($null -ne $Content -and $Content.Contains($Needle)) {
        $Failures.Add("Unexpected substring in ${RelativePath}: $Needle")
    }
}

$Routing = "$ThemeRoot/inc/guide-routing.php"
$Functions = "$ThemeRoot/functions.php"

Require-File $Routing
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-routing.php');"
Require-Contains $Routing 'function vg_guide_pilot_paths(): array'
Require-Contains $Routing 'function vg_classify_guide_path(string $path): ?string'
Require-Contains $Routing 'function vg_get_guide_path(?WP_Post $post = null): string'
Require-Contains $Routing 'function vg_get_guide_type(?WP_Post $post = null): ?string'
Require-Contains $Routing 'function vg_is_guide_experience_page(?WP_Post $post = null): bool'
Require-Contains $Routing "'destinations/ho-chi-minh-city-travel-guide'"
Require-Contains $Routing "'itineraries/10-days-in-vietnam'"
Require-Contains $Routing "'compare/ha-long-bay-vs-lan-ha-bay'"
Require-Contains $Routing "'plan/vietnam-evisa'"
Require-Contains $Routing "'destinations' => 'destination'"
Require-Contains $Routing "'itineraries' => 'itinerary'"
Require-Contains $Routing "'compare' => 'comparison'"
Require-Contains $Routing "'plan' => 'practical'"
Require-Contains $Routing 'if (! in_array($path, vg_guide_pilot_paths(), true)) {'

if ($Failures.Count -gt 0) {
    $Failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output 'VietnamGuide guide experience checks passed.'

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

function Get-FunctionContent {
    param([string]$RelativePath, [string]$FunctionName)
    $Content = Get-RepoContent $RelativePath
    if ($null -eq $Content) { return $null }

    $StartMarker = "function $FunctionName"
    $Start = $Content.IndexOf($StartMarker, [System.StringComparison]::Ordinal)
    if ($Start -lt 0) { return $null }

    $SearchStart = $Start + $StartMarker.Length
    $Remaining = $Content.Substring($SearchStart)
    $NextFunction = [regex]::Match($Remaining, '(?m)^function\s+')
    $End = if ($NextFunction.Success) { $SearchStart + $NextFunction.Index } else { $Content.Length }

    return $Content.Substring($Start, $End - $Start)
}

function Require-FunctionContains {
    param([string]$RelativePath, [string]$FunctionName, [string]$Needle)
    $FunctionContent = Get-FunctionContent $RelativePath $FunctionName
    if ($null -ne $FunctionContent -and -not $FunctionContent.Contains($Needle)) {
        $Failures.Add("Missing substring in ${FunctionName}(): $Needle")
    }
}

function Require-FunctionMatches {
    param([string]$RelativePath, [string]$FunctionName, [string]$Pattern, [string]$Description)
    $FunctionContent = Get-FunctionContent $RelativePath $FunctionName
    if ($null -ne $FunctionContent -and -not [regex]::IsMatch($FunctionContent, $Pattern)) {
        $Failures.Add("Missing pattern in ${FunctionName}(): $Description")
    }
}

function Require-FunctionOrder {
    param([string]$RelativePath, [string]$FunctionName, [string]$First, [string]$Second)
    $FunctionContent = Get-FunctionContent $RelativePath $FunctionName
    if ($null -eq $FunctionContent) { return }

    $FirstIndex = $FunctionContent.IndexOf($First, [System.StringComparison]::Ordinal)
    $SecondIndex = $FunctionContent.IndexOf($Second, [System.StringComparison]::Ordinal)
    if ($FirstIndex -lt 0 -or $SecondIndex -lt 0 -or $FirstIndex -ge $SecondIndex) {
        $Failures.Add("Expected order in ${FunctionName}(): $First before $Second")
    }
}

function Require-ExactSet {
    param([string]$Label, [string[]]$Actual, [string[]]$Expected)
    if ($Actual.Count -ne $Expected.Count) {
        $Failures.Add("Expected $Label count $($Expected.Count), found $($Actual.Count)")
    }

    foreach ($ExpectedItem in $Expected) {
        if ($Actual -notcontains $ExpectedItem) {
            $Failures.Add("Missing ${Label}: $ExpectedItem")
        }
    }

    foreach ($ActualItem in $Actual) {
        if ($Expected -notcontains $ActualItem) {
            $Failures.Add("Unexpected ${Label}: $ActualItem")
        }
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

$PilotFunction = Get-FunctionContent $Routing 'vg_guide_pilot_paths'
if ($null -ne $PilotFunction) {
    $PilotArray = [regex]::Match($PilotFunction, 'return\s*\[(?<items>.*?)\];', [System.Text.RegularExpressions.RegexOptions]::Singleline)
    if (-not $PilotArray.Success) {
        $Failures.Add('Missing pilot path array in vg_guide_pilot_paths()')
    } else {
        $PilotItems = @($PilotArray.Groups['items'].Value -split ',' | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne '' })
        $PilotPaths = @($PilotItems | ForEach-Object {
            $ItemMatch = [regex]::Match($_, '^[''"](?<value>[^''"]+)[''"]$')
            if ($ItemMatch.Success) { $ItemMatch.Groups['value'].Value } else { "invalid:$_" }
        })
        Require-ExactSet 'pilot path' $PilotPaths @(
            'destinations/ho-chi-minh-city-travel-guide'
            'itineraries/10-days-in-vietnam'
            'compare/ha-long-bay-vs-lan-ha-bay'
            'plan/vietnam-evisa'
        )
    }
}

$ClassifierFunction = Get-FunctionContent $Routing 'vg_classify_guide_path'
if ($null -ne $ClassifierFunction) {
    $ClassifierArray = [regex]::Match($ClassifierFunction, '\$types\s*=\s*\[(?<items>.*?)\];', [System.Text.RegularExpressions.RegexOptions]::Singleline)
    if (-not $ClassifierArray.Success) {
        $Failures.Add('Missing classifier mapping array in vg_classify_guide_path()')
    } else {
        $ClassifierItems = @($ClassifierArray.Groups['items'].Value -split ',' | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne '' })
        $ClassifierMappings = @($ClassifierItems | ForEach-Object {
            $ItemMatch = [regex]::Match($_, '^[''"](?<key>[^''"]+)[''"]\s*=>\s*[''"](?<value>[^''"]+)[''"]$')
            if ($ItemMatch.Success) { "$($ItemMatch.Groups['key'].Value)=$($ItemMatch.Groups['value'].Value)" } else { "invalid:$_" }
        })
        Require-ExactSet 'classifier mapping' $ClassifierMappings @(
            'destinations=destination'
            'itineraries=itinerary'
            'compare=comparison'
            'plan=practical'
        )
    }
}

Require-FunctionMatches $Routing 'vg_get_guide_type' 'if\s*\(!\s*\$post\s+instanceof\s+WP_Post\s+\|\|\s+\$post->post_type\s*!==\s*''page''\s*\)\s*\{\s*return\s+null;\s*\}' 'non-page guard returning null'
Require-FunctionOrder $Routing 'vg_get_guide_type' "if (! `$post instanceof WP_Post || `$post->post_type !== 'page') {" "apply_filters('vg_guide_type'"
Require-FunctionContains $Routing 'vg_is_guide_experience_page' 'is_page($post->ID)'
Require-FunctionOrder $Routing 'vg_is_guide_experience_page' 'if (! in_array($path, vg_guide_pilot_paths(), true)) {' 'vg_get_guide_type($post)'

if ($Failures.Count -gt 0) {
    $Failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output 'VietnamGuide guide experience checks passed.'

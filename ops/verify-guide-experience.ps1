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

function Require-FunctionNotContains {
    param([string]$RelativePath, [string]$FunctionName, [string]$Needle)
    $FunctionContent = Get-FunctionContent $RelativePath $FunctionName
    if ($null -ne $FunctionContent -and $FunctionContent.Contains($Needle)) {
        $Failures.Add("Unexpected substring in ${FunctionName}(): $Needle")
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
$ContentProvider = "$ThemeRoot/inc/guide-content.php"
$Functions = "$ThemeRoot/functions.php"

Require-File $Routing
Require-File $ContentProvider
Require-File $Functions
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-routing.php');"
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-content.php');"
Require-Contains $Routing 'function vg_guide_pilot_paths(): array'
Require-Contains $Routing 'function vg_classify_guide_path(string $path): ?string'
Require-Contains $Routing 'function vg_get_guide_path(?WP_Post $post = null): string'
Require-Contains $Routing 'function vg_get_guide_type(?WP_Post $post = null): ?string'
Require-Contains $Routing 'function vg_is_guide_experience_page(?WP_Post $post = null): bool'
Require-Contains $ContentProvider 'function vg_split_guide_blocks(string $postContent): ?array'
Require-Contains $ContentProvider 'function vg_prepare_guide_headings(string $html): array'
Require-Contains $ContentProvider 'function vg_render_guide_toc(array $headings, string $className = ''vg-guide-toc''): string'
Require-Contains $ContentProvider 'function vg_prepare_guide_content(WP_Post $post): ?array'
Require-Contains $ContentProvider "'hero_html'"
Require-Contains $ContentProvider "'body_html'"
Require-Contains $ContentProvider "'headings'"
Require-Contains $ContentProvider 'vg-guide-hero'
Require-Contains $ContentProvider '<h2\b'
Require-Contains $ContentProvider "preg_match_all('/<h1\b/i', `$heroSource) !== 1"
Require-Contains $ContentProvider 'data-vg-toc'
Require-Contains $ContentProvider 'sanitize_title'
Require-Contains $ContentProvider 'serialize_blocks'
Require-Contains $ContentProvider "apply_filters('the_content'"

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

$GuideTypeFunction = Get-FunctionContent $Routing 'vg_get_guide_type'
if ($null -ne $GuideTypeFunction) {
    $FilteredValidation = [regex]::Match($GuideTypeFunction, 'in_array\s*\(\s*\$filtered\s*,\s*\[(?<items>.*?)\](?<tail>.*?)\)', [System.Text.RegularExpressions.RegexOptions]::Singleline)
    if (-not $FilteredValidation.Success) {
        $Failures.Add('Missing filtered type validation in vg_get_guide_type()')
    } else {
        $FilteredTypeItems = @($FilteredValidation.Groups['items'].Value -split ',' | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne '' })
        $FilteredTypes = @($FilteredTypeItems | ForEach-Object {
            $ItemMatch = [regex]::Match($_, '^[''"](?<value>[^''"]+)[''"]$')
            if ($ItemMatch.Success) { $ItemMatch.Groups['value'].Value } else { "invalid:$_" }
        })
        Require-ExactSet 'filtered guide type' $FilteredTypes @(
            'destination'
            'itinerary'
            'comparison'
            'practical'
        )

        if (-not [regex]::IsMatch($FilteredValidation.Groups['tail'].Value, '^\s*,\s*true\s*$')) {
            $Failures.Add('Expected strict true validation for filtered guide types in vg_get_guide_type()')
        }
    }
}

Require-FunctionMatches $Routing 'vg_get_guide_type' 'if\s*\(!\s*\$post\s+instanceof\s+WP_Post\s+\|\|\s+\$post->post_type\s*!==\s*''page''\s*\)\s*\{\s*return\s+null;\s*\}' 'non-page guard returning null'
Require-FunctionOrder $Routing 'vg_get_guide_type' "if (! `$post instanceof WP_Post || `$post->post_type !== 'page') {" "apply_filters('vg_guide_type'"
Require-FunctionContains $Routing 'vg_is_guide_experience_page' 'is_page($post->ID)'
Require-FunctionOrder $Routing 'vg_is_guide_experience_page' 'if (! in_array($path, vg_guide_pilot_paths(), true)) {' 'vg_get_guide_type($post)'

Require-FunctionContains $ContentProvider 'vg_split_guide_blocks' 'parse_blocks($postContent)'
Require-FunctionContains $ContentProvider 'vg_split_guide_blocks' "preg_match_all('/<h1\b/i', `$heroSource) !== 1"
Require-FunctionContains $ContentProvider 'vg_split_guide_blocks' 'serialize_blocks($blocks)'
Require-FunctionContains $ContentProvider 'vg_prepare_guide_headings' "/<h2\b((?:[^>`"\']+|`"[^`"]*`"|\'[^\']*\')*)>(.*?)<\/h2>/is"
Require-FunctionNotContains $ContentProvider 'vg_prepare_guide_headings' '/<h2\b([^>]*)>'
Require-FunctionContains $ContentProvider 'vg_prepare_guide_headings' 'WP_HTML_Tag_Processor'
Require-FunctionContains $ContentProvider 'vg_prepare_guide_headings' "next_tag('H2')"
Require-FunctionContains $ContentProvider 'vg_prepare_guide_headings' "get_attribute('data-vg-toc')"
Require-FunctionContains $ContentProvider 'vg_prepare_guide_headings' "get_attribute('id')"
Require-FunctionContains $ContentProvider 'vg_prepare_guide_headings' "set_attribute('id'"
Require-FunctionContains $ContentProvider 'vg_prepare_guide_headings' 'get_updated_html()'
Require-FunctionContains $ContentProvider 'vg_prepare_guide_headings' 'strcasecmp(trim('
Require-FunctionNotContains $ContentProvider 'vg_prepare_guide_headings' "/(?:^|\s)data-vg-toc"
Require-FunctionNotContains $ContentProvider 'vg_prepare_guide_headings' "/(?:^|\s)id\s*="
Require-FunctionNotContains $ContentProvider 'vg_prepare_guide_headings' "/(^|\s)id\s*="
Require-FunctionNotContains $ContentProvider 'vg_prepare_guide_headings' "`$hasId = preg_match("
Require-FunctionNotContains $ContentProvider 'vg_prepare_guide_headings' 'preg_replace('
Require-FunctionContains $ContentProvider 'vg_prepare_guide_headings' 'sanitize_title'
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' "apply_filters('the_content', `$split['hero_source'])"
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' "apply_filters('the_content', `$split['body_source'])"

if ($Failures.Count -gt 0) {
    $Failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output 'VietnamGuide guide experience checks passed.'

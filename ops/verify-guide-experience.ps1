param(
    [string]$RepoRootOverride = ''
)

$ErrorActionPreference = 'Stop'
$RepoRoot = if ($RepoRootOverride) { $RepoRootOverride } else { Split-Path -Parent $PSScriptRoot }
$ThemeRoot = 'wordpress/wp-content/themes/vietnamguide-premium'
$GuideCss = "$ThemeRoot/assets/css/guide-experience.css"
$GuideJs = "$ThemeRoot/assets/js/guide-experience.js"
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

function Require-Matches {
    param([string]$RelativePath, [string]$Pattern, [string]$Description)
    $Content = Get-RepoContent $RelativePath
    if ($null -ne $Content -and -not [regex]::IsMatch($Content, $Pattern)) {
        $Failures.Add("Missing pattern in ${RelativePath}: $Description")
    }
}

function Get-CssBlockContent {
    param([string]$RelativePath, [string]$Marker)
    $Content = Get-RepoContent $RelativePath
    if ($null -eq $Content) { return $null }

    $MarkerIndex = $Content.IndexOf($Marker, [System.StringComparison]::Ordinal)
    if ($MarkerIndex -lt 0) { return $null }

    $OpenBrace = $Content.IndexOf('{', $MarkerIndex)
    if ($OpenBrace -lt 0) { return $null }

    $Depth = 0
    for ($Index = $OpenBrace; $Index -lt $Content.Length; $Index++) {
        if ($Content[$Index] -eq '{') {
            $Depth++
            continue
        }

        if ($Content[$Index] -eq '}') {
            $Depth--
            if ($Depth -eq 0) {
                return $Content.Substring($OpenBrace + 1, $Index - $OpenBrace - 1)
            }
        }
    }

    return $null
}

function Require-CssBlockContains {
    param([string]$RelativePath, [string]$Marker, [string]$Needle)
    $BlockContent = Get-CssBlockContent $RelativePath $Marker
    if ($null -eq $BlockContent) {
        $Failures.Add("Missing CSS block in ${RelativePath}: $Marker")
    } elseif (-not $BlockContent.Contains($Needle)) {
        $Failures.Add("Missing substring in CSS block ${Marker}: $Needle")
    }
}

function Require-GuideCssScoped {
    param([string]$RelativePath)
    $Content = Get-RepoContent $RelativePath
    if ($null -eq $Content) { return }

    $SelectorMatches = [regex]::Matches($Content, '(?ms)(?:^|[{}])\s*([^{};][^{};]*)\{')
    foreach ($SelectorMatch in $SelectorMatches) {
        foreach ($SelectorValue in $SelectorMatch.Groups[1].Value.Split(',')) {
            $Selector = $SelectorValue.Trim()
            if ($Selector -eq '' -or $Selector.StartsWith('@')) { continue }
            if ($Selector -notmatch '^\.vg-[A-Za-z0-9_-]+') {
                $Failures.Add("Unscoped guide CSS selector in ${RelativePath}: $Selector")
            }
        }
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
$ContextProvider = "$ThemeRoot/inc/guide-context.php"
$Functions = "$ThemeRoot/functions.php"
$PageTemplate = "$ThemeRoot/page.php"
$DefaultPart = "$ThemeRoot/template-parts/content-page.php"
$GuidePart = "$ThemeRoot/template-parts/guide-page.php"

Require-File $Routing
Require-File $ContentProvider
Require-File $ContextProvider
Require-File $Functions
Require-File $PageTemplate
Require-File $DefaultPart
Require-File $GuidePart
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-routing.php');"
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-content.php');"
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-context.php');"
Require-Contains $Routing 'function vg_guide_pilot_paths(): array'
Require-Contains $Routing 'function vg_classify_guide_path(string $path): ?string'
Require-Contains $Routing 'function vg_get_guide_path(?WP_Post $post = null): string'
Require-Contains $Routing 'function vg_get_guide_type(?WP_Post $post = null): ?string'
Require-Contains $Routing 'function vg_is_guide_experience_page(?WP_Post $post = null): bool'
Require-Contains $ContentProvider 'function vg_split_guide_blocks(string $postContent): ?array'
Require-Contains $ContentProvider 'function vg_inspect_guide_html(string $html): ?array'
Require-Contains $ContentProvider 'function vg_is_valid_guide_heading_id(string $id): bool'
Require-Contains $ContentProvider 'function vg_allocate_guide_heading_id(string $base, array $reservedIds, array $assignedIds): string'
Require-Contains $ContentProvider 'function vg_collect_guide_heading_plan(string $html): ?array'
Require-Contains $ContentProvider 'function vg_apply_guide_heading_plan(string $html, array $plan): ?string'
Require-Contains $ContentProvider 'function vg_prepare_guide_headings(string $html): array'
Require-Contains $ContentProvider 'function vg_render_guide_toc(array $headings, string $className = ''vg-guide-toc''): string'
Require-Contains $ContentProvider 'function vg_prepare_guide_content(WP_Post $post): ?array'
Require-Contains $ContentProvider "'hero_html'"
Require-Contains $ContentProvider "'body_html'"
Require-Contains $ContentProvider "'headings'"
Require-Contains $ContentProvider 'vg-guide-hero'
Require-Contains $ContentProvider "preg_match_all('/<h1\b/i', `$heroSource) !== 1"
Require-Contains $ContentProvider 'data-vg-toc'
Require-Contains $ContentProvider 'sanitize_title'
Require-Contains $ContentProvider 'serialize_blocks'
Require-Contains $ContentProvider "apply_filters('the_content'"
Require-NotContains $ContentProvider '<h2\b'
Require-Contains $ContextProvider 'function vg_estimate_guide_reading_time(string $html): int'
Require-Contains $ContextProvider 'function vg_extract_guide_data_value(string $html, string $attribute): string'
Require-Contains $ContextProvider 'function vg_count_guide_sources(string $html): int'
Require-Contains $ContextProvider 'function vg_normalize_guide_route_url(string $url): string'
Require-Contains $ContextProvider 'function vg_get_related_routes(WP_Post $post, bool $hasExisting): array'
Require-Contains $ContextProvider 'function vg_guide_body_has_related_routes(string $html): bool'
Require-Contains $ContextProvider 'function vg_is_valid_guide_context(array $context): bool'
Require-Contains $ContextProvider 'function vg_build_guide_context(WP_Post $post): ?array'
Require-Matches $ContextProvider '\A<\?php\s+if\s*\(!\s*defined\(''ABSPATH''\)\s*\)\s*\{\s*exit;\s*\}' 'ABSPATH guard at the start of the context provider'
Require-Contains $ContextProvider "'_vg_reviewed_at'"
Require-Contains $ContextProvider 'vg-related-routes'
Require-Contains $ContextProvider 'if ($hasExisting) {'
Require-Contains $ContextProvider "'has_existing_related_routes'"
Require-Contains $ContextProvider "'reading_time'"
Require-Contains $ContextProvider "'source_count'"
Require-Contains $ContextProvider "'best_for'"
Require-Contains $ContextProvider "'skip_if'"
Require-Contains $ContextProvider "'related_routes'"
Require-Matches $PageTemplate '\A<\?php\s+if\s*\(!\s*defined\(''ABSPATH''\)\s*\)\s*\{\s*exit;\s*\}' 'ABSPATH guard at the start of the page template'
Require-Contains $PageTemplate 'vg_is_guide_experience_page($post)'
Require-Contains $PageTemplate 'vg_build_guide_context($post)'
Require-Contains $PageTemplate '$guideContext = null;'
Require-Contains $PageTemplate 'if (is_array($guideContext) && vg_is_valid_guide_context($guideContext)) {'
Require-NotContains $PageTemplate 'if (is_array($guideContext)) {'
Require-Contains $PageTemplate "get_template_part('template-parts/guide', 'page', `$guideContext);"
Require-Contains $PageTemplate "get_template_part('template-parts/content', 'page');"
Require-Matches $DefaultPart '\A<\?php\s+if\s*\(!\s*defined\(''ABSPATH''\)\s*\)\s*\{\s*exit;\s*\}' 'ABSPATH guard at the start of the default page template part'
Require-Contains $DefaultPart '<h1>'
Require-Contains $DefaultPart 'the_content();'
Require-Contains $GuidePart '! is_array($args ?? null)'
Require-Contains $GuidePart '! vg_is_valid_guide_context($args)'
Require-Contains $GuidePart 'return;'
Require-Contains $GuidePart 'data-vg-guide'
Require-Contains $GuidePart 'data-vg-guide-type'
Require-Contains $GuidePart 'role="list"'
Require-Contains $GuidePart 'role="listitem"'
Require-Contains $GuidePart "'destination' => __('Destination', 'vietnamguide-premium')"
Require-Contains $GuidePart "'itinerary' => __('Itinerary', 'vietnamguide-premium')"
Require-Contains $GuidePart "'comparison' => __('Comparison', 'vietnamguide-premium')"
Require-Contains $GuidePart "'practical' => __('Practical', 'vietnamguide-premium')"
Require-Contains $GuidePart "_n('%d minute read', '%d minutes read', `$readingTime, 'vietnamguide-premium')"
Require-NotContains $GuidePart 'ucfirst('
Require-Contains $GuidePart 'vg-guide-jump'
Require-Contains $GuidePart 'vg-guide-spine'
Require-Contains $GuidePart 'vg-guide-article'
Require-Contains $GuidePart 'vg-guide-trust'
Require-Contains $GuidePart 'vg-guide-related'
Require-NotContains $GuidePart '<h1'

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
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' 'WP_HTML_Tag_Processor'
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' 'next_token()'
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' 'get_token_name()'
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' 'is_tag_closer()'
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' "has_class('vg-guide-hero')"
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' 'paused_at_incomplete_token()'
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' "'H1' === `$tokenName"
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' "'has_hero_class'"
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' "'h1_count'"
Require-FunctionContains $ContentProvider 'vg_is_valid_guide_heading_id' "preg_match('/\s/u', `$id) === 0"
Require-FunctionContains $ContentProvider 'vg_allocate_guide_heading_id' 'isset($reservedIds[$candidate])'
Require-FunctionContains $ContentProvider 'vg_allocate_guide_heading_id' 'isset($assignedIds[$candidate])'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'next_token()'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'get_token_name()'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'is_tag_closer()'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'get_modifiable_text()'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'paused_at_incomplete_token()'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' "get_attribute('data-vg-toc')"
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' "get_attribute('id')"
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'strcasecmp(trim('
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' "preg_replace('/\s+/u'"
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'if ($processor->paused_at_incomplete_token() || $currentHeading !== null) {'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'vg_is_valid_guide_heading_id($originalId)'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' '$reservedIds'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' '$assignedIds'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' '$plannedId = $originalId;'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'sanitize_title($label)'
Require-FunctionContains $ContentProvider 'vg_apply_guide_heading_plan' "next_tag('H2')"
Require-FunctionContains $ContentProvider 'vg_apply_guide_heading_plan' "get_attribute('id')"
Require-FunctionContains $ContentProvider 'vg_apply_guide_heading_plan' 'if ($plannedId !== $currentId) {'
Require-FunctionContains $ContentProvider 'vg_apply_guide_heading_plan' "set_attribute('id', `$plannedId)"
Require-FunctionContains $ContentProvider 'vg_apply_guide_heading_plan' 'paused_at_incomplete_token()'
Require-FunctionContains $ContentProvider 'vg_apply_guide_heading_plan' '$headingIndex !== count($plan)'
Require-FunctionContains $ContentProvider 'vg_apply_guide_heading_plan' 'get_updated_html()'
Require-FunctionContains $ContentProvider 'vg_prepare_guide_headings' 'vg_collect_guide_heading_plan($html)'
Require-FunctionContains $ContentProvider 'vg_prepare_guide_headings' 'vg_apply_guide_heading_plan($html, $plan)'
Require-FunctionNotContains $ContentProvider 'vg_prepare_guide_headings' '<h2\b'
Require-FunctionNotContains $ContentProvider 'vg_prepare_guide_headings' 'preg_replace_callback('
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' "apply_filters('the_content', `$split['hero_source'])"
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' "apply_filters('the_content', `$split['body_source'])"
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' 'vg_inspect_guide_html((string) $heroHtml)'
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' 'vg_inspect_guide_html((string) $bodyHtml)'
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' "`$heroStats['has_hero_class']"
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' "`$heroStats['h1_count'] !== 1"
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' "`$bodyStats['h1_count'] !== 0"
Require-FunctionOrder $ContentProvider 'vg_prepare_guide_content' "apply_filters('the_content', `$split['hero_source'])" 'vg_inspect_guide_html((string) $heroHtml)'
Require-FunctionOrder $ContentProvider 'vg_prepare_guide_content' "apply_filters('the_content', `$split['body_source'])" 'vg_inspect_guide_html((string) $bodyHtml)'

Require-FunctionContains $ContextProvider 'vg_estimate_guide_reading_time' 'str_word_count(wp_strip_all_tags($html))'
Require-FunctionContains $ContextProvider 'vg_estimate_guide_reading_time' 'max(1, (int) ceil($words / 220))'
Require-FunctionContains $ContextProvider 'vg_extract_guide_data_value' 'WP_HTML_Tag_Processor'
Require-FunctionContains $ContextProvider 'vg_extract_guide_data_value' 'next_token()'
Require-FunctionContains $ContextProvider 'vg_extract_guide_data_value' 'is_tag_closer()'
Require-FunctionContains $ContextProvider 'vg_extract_guide_data_value' 'get_attribute($attribute)'
Require-FunctionContains $ContextProvider 'vg_extract_guide_data_value' 'sanitize_text_field'
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' "class_exists('DOMDocument')"
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' "class_exists('DOMXPath')"
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' 'contains(concat(" ", normalize-space(@class), " "), " vg-pattern-source-block ")'
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' 'contains(concat(" ", normalize-space(@class), " "), " source-diversity ")'
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' 'contains(concat(" ", normalize-space(@class), " "), " source-trail ")'
Require-FunctionNotContains $ContextProvider 'vg_count_guide_sources' 'contains(@class,'
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' 'wp_parse_url(home_url(''/'')'
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' '$scheme = strtolower((string) wp_parse_url($href, PHP_URL_SCHEME));'
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' "in_array(`$scheme, ['http', 'https'], true)"
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' "str_starts_with(`$href, '//')"
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' '$isAllowedScheme'
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' '$sources[$href] = true;'
Require-FunctionOrder $ContextProvider 'vg_count_guide_sources' '$scheme = strtolower((string) wp_parse_url($href, PHP_URL_SCHEME));' '$isAllowedScheme'
Require-FunctionOrder $ContextProvider 'vg_count_guide_sources' '$isAllowedScheme' '$sources[$href] = true;'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' 'if ($url === '''') {'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "str_contains(`$url, '\\')"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "preg_match('/[\x00-\x20\x7F]/', `$url) === 1"
Require-FunctionNotContains $ContextProvider 'vg_normalize_guide_route_url' '$url = trim($url);'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' '$parts = wp_parse_url($url);'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' 'if (! is_array($parts)) {'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$scheme = strtolower((string) (`$parts['scheme'] ?? ''));"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$host = trim((string) (`$parts['host'] ?? ''));"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$isRootRelative = str_starts_with(`$url, '/') && ! str_starts_with(`$url, '//') && `$scheme === '' && `$host === '';"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$isProtocolRelative = str_starts_with(`$url, '//') && `$scheme === '' && `$host !== '';"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$isAbsoluteWeb = in_array(`$scheme, ['http', 'https'], true) && `$host !== '';"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "esc_url_raw(`$url, ['http', 'https'])"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' 'if ($sanitized === '''') {'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' '$sanitizedParts = wp_parse_url($sanitized);'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' 'if (! is_array($sanitizedParts)) {'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$sanitizedScheme = strtolower((string) (`$sanitizedParts['scheme'] ?? ''));"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$sanitizedHost = trim((string) (`$sanitizedParts['host'] ?? ''));"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$sanitizedIsRootRelative = str_starts_with(`$sanitized, '/') && ! str_starts_with(`$sanitized, '//') && `$sanitizedScheme === '' && `$sanitizedHost === '';"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$sanitizedIsProtocolRelative = str_starts_with(`$sanitized, '//') && `$sanitizedScheme === '' && `$sanitizedHost !== '';"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$sanitizedIsAbsoluteWeb = in_array(`$sanitizedScheme, ['http', 'https'], true) && `$sanitizedHost !== '';"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' '$hasMatchingShape'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' 'return $sanitized;'
Require-FunctionNotContains $ContextProvider 'vg_normalize_guide_route_url' 'javascript'
Require-FunctionNotContains $ContextProvider 'vg_normalize_guide_route_url' 'data:'
Require-FunctionNotContains $ContextProvider 'vg_normalize_guide_route_url' 'ftp'
Require-FunctionOrder $ContextProvider 'vg_normalize_guide_route_url' "str_contains(`$url, '\\')" '$parts = wp_parse_url($url);'
Require-FunctionOrder $ContextProvider 'vg_normalize_guide_route_url' "preg_match('/[\x00-\x20\x7F]/', `$url) === 1" '$parts = wp_parse_url($url);'
Require-FunctionOrder $ContextProvider 'vg_normalize_guide_route_url' '$isAbsoluteWeb' "esc_url_raw(`$url, ['http', 'https'])"
Require-FunctionOrder $ContextProvider 'vg_normalize_guide_route_url' "esc_url_raw(`$url, ['http', 'https'])" '$sanitizedParts = wp_parse_url($sanitized);'
Require-FunctionOrder $ContextProvider 'vg_normalize_guide_route_url' '$sanitizedParts = wp_parse_url($sanitized);' '$sanitizedIsRootRelative'
Require-FunctionOrder $ContextProvider 'vg_normalize_guide_route_url' '$sanitizedIsAbsoluteWeb' '$hasMatchingShape'
Require-FunctionOrder $ContextProvider 'vg_normalize_guide_route_url' '$hasMatchingShape' 'return $sanitized;'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' 'if ($hasExisting) {'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "function_exists('vg_eeat_get_field')"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "function_exists('vg_eeat_related_route_items')"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "vg_eeat_get_field(`$post->ID, 'related_routes')"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' 'vg_eeat_related_route_items('
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "`$title = trim((string) (`$item['label'] ?? ''));"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "`$rawUrl = `$item['url'] ?? '';"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$url = is_string($rawUrl) ? vg_normalize_guide_route_url($rawUrl) : '''';'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' 'if ($title === '''' || $url === '''') {'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$curatedRoutes[] = ['
Require-FunctionContains $ContextProvider 'vg_get_related_routes' 'if ($curatedRoutes !== []) {'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' 'return array_values($curatedRoutes);'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' 'if ($post->post_parent >= 0) {'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "'post_status' => 'publish'"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$title = get_the_title($sibling);'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$url = get_permalink($sibling);'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "if (`$title === '' || ! is_string(`$url) || `$url === '') {"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' 'if (count($routes) === 3) {'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$post->post_parent'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$parent = get_post($post->post_parent);'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$parent instanceof WP_Post'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$parent->post_type === ''page'''
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$parent->post_status === ''publish'''
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$parentTitle = get_the_title($parent);'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$parentUrl = get_permalink($parent);'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "'title' => `$parentTitle"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "'url' => `$parentUrl"
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' 'if ($hasExisting) {' "function_exists('vg_eeat_get_field')"
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' "vg_eeat_get_field(`$post->ID, 'related_routes')" 'get_pages(['
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$url = is_string($rawUrl) ? vg_normalize_guide_route_url($rawUrl) : '''';' 'if ($title === '''' || $url === '''') {'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' 'if ($title === '''' || $url === '''') {' '$curatedRoutes[] = ['
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$curatedRoutes[] = [' 'if ($curatedRoutes !== []) {'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' 'if ($curatedRoutes !== []) {' 'return array_values($curatedRoutes);'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' 'return array_values($curatedRoutes);' '$routes = [];'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$curatedRoutes[] = [' 'return array_values($curatedRoutes);'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$title = get_the_title($sibling);' "if (`$title === '' || ! is_string(`$url) || `$url === '') {"
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' "if (`$title === '' || ! is_string(`$url) || `$url === '') {" '$routes[] = ['
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$routes[] = [' 'if (count($routes) === 3) {'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' 'if (count($routes) === 3) {' 'if ($routes === [] && $post->post_parent > 0) {'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$parent = get_post($post->post_parent);' '$parent->post_status === ''publish'''
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$parent->post_status === ''publish''' '$parentTitle = get_the_title($parent);'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$parentTitle = get_the_title($parent);' "'title' => `$parentTitle"
Require-FunctionContains $ContextProvider 'vg_guide_body_has_related_routes' 'WP_HTML_Tag_Processor'
Require-FunctionContains $ContextProvider 'vg_guide_body_has_related_routes' 'next_token()'
Require-FunctionContains $ContextProvider 'vg_guide_body_has_related_routes' 'is_tag_closer()'
Require-FunctionContains $ContextProvider 'vg_guide_body_has_related_routes' "has_class('vg-related-routes')"
Require-FunctionContains $ContextProvider 'vg_guide_body_has_related_routes' 'return true;'
Require-FunctionContains $ContextProvider 'vg_guide_body_has_related_routes' 'return false;'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "array_key_exists('post_id', `$context)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "is_int(`$context['post_id'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$context['post_id'] <= 0"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "in_array(`$context['type'], ['destination', 'itinerary', 'comparison', 'practical'], true)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "array_key_exists('hero_html', `$context)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "is_string(`$context['hero_html'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "trim(`$context['hero_html']) === ''"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '$stringFields = ['
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "'body_html'"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "'toc_html'"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "'reviewed_at'"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "'best_for'"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "'skip_if'"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "'title'"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "'permalink'"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' 'foreach ($stringFields as $field) {'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '! array_key_exists($field, $context)'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '! is_string($context[$field])'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$heroStats = vg_inspect_guide_html(`$context['hero_html']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$bodyStats = vg_inspect_guide_html(`$context['body_html']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '$heroStats === null'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '$bodyStats === null'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! `$heroStats['has_hero_class']"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$heroStats['h1_count'] !== 1"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$bodyStats['h1_count'] !== 0"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' '! is_string($context[$field])' "`$heroStats = vg_inspect_guide_html(`$context['hero_html']);"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' '$heroStats === null' "! `$heroStats['has_hero_class']"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' "! `$heroStats['has_hero_class']" "`$heroStats['h1_count'] !== 1"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "array_key_exists('headings', `$context)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "is_array(`$context['headings'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '$headingIds = [];'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "foreach (`$context['headings'] as `$heading) {"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '! is_array($heading)'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! array_key_exists('id', `$heading)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! array_key_exists('label', `$heading)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! is_string(`$heading['id'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! is_string(`$heading['label'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$headingId = `$heading['id'];"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$headingLabel = trim(`$heading['label']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '! vg_is_valid_guide_heading_id($headingId)'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$headingLabel === ''"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' 'isset($headingIds[$headingId])'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '$headingIds[$headingId] = true;'
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' "foreach (`$context['headings'] as `$heading) {" '$headingIds[$headingId] = true;'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$preparedBody = vg_prepare_guide_headings(`$context['body_html']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$preparedBody['html'] !== `$context['body_html']"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$preparedBody['headings'] !== `$context['headings']"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' '$headingIds[$headingId] = true;' "`$preparedBody = vg_prepare_guide_headings(`$context['body_html']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "if (`$context['toc_html'] !== vg_render_guide_toc(`$context['headings'])) {"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' "`$preparedBody['headings'] !== `$context['headings']" "if (`$context['toc_html'] !== vg_render_guide_toc(`$context['headings'])) {"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' '$headingIds[$headingId] = true;' "if (`$context['toc_html'] !== vg_render_guide_toc(`$context['headings'])) {"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "array_key_exists('reading_time', `$context)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "is_int(`$context['reading_time'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$context['reading_time'] < 1"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "array_key_exists('source_count', `$context)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "is_int(`$context['source_count'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$context['source_count'] < 0"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "array_key_exists('related_routes', `$context)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "is_array(`$context['related_routes'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "array_key_exists('has_existing_related_routes', `$context)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "is_bool(`$context['has_existing_related_routes'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "foreach (`$context['related_routes'] as `$route) {"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '! is_array($route)'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! array_key_exists('title', `$route)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! array_key_exists('url', `$route)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! is_string(`$route['title'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! is_string(`$route['url'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "trim(`$route['title']) === ''"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "trim(`$route['url']) === ''"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$normalizedRouteUrl = vg_normalize_guide_route_url(`$route['url']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$normalizedRouteUrl === ''"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$route['url'] !== `$normalizedRouteUrl"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' "trim(`$route['url']) === ''" "`$normalizedRouteUrl = vg_normalize_guide_route_url(`$route['url']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$hasExistingRelated = vg_guide_body_has_related_routes(`$context['body_html']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$context['has_existing_related_routes'] !== `$hasExistingRelated"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$hasExistingRelated && `$context['related_routes'] !== []"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' "`$route['url'] !== `$normalizedRouteUrl" "`$hasExistingRelated = vg_guide_body_has_related_routes(`$context['body_html']);"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' "foreach (`$context['related_routes'] as `$route) {" 'return true;'
Require-FunctionContains $ContextProvider 'vg_build_guide_context' 'vg_prepare_guide_content($post)'
Require-FunctionContains $ContextProvider 'vg_build_guide_context' 'vg_get_guide_type($post)'
Require-FunctionContains $ContextProvider 'vg_build_guide_context' 'post_password_required($post)'
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "`$post->post_password !== ''"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "preg_match('/<!--\s*nextpage\s*-->/i', `$post->post_content) === 1"
Require-FunctionOrder $ContextProvider 'vg_build_guide_context' 'post_password_required($post)' 'vg_prepare_guide_content($post)'
Require-FunctionOrder $ContextProvider 'vg_build_guide_context' "`$post->post_password !== ''" 'vg_prepare_guide_content($post)'
Require-FunctionOrder $ContextProvider 'vg_build_guide_context' "preg_match('/<!--\s*nextpage\s*-->/i', `$post->post_content) === 1" 'vg_prepare_guide_content($post)'
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "function_exists('vg_eeat_get_field')"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "vg_eeat_get_field(`$post->ID, 'last_meaningful_update')"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "get_post_meta(`$post->ID, '_vg_reviewed_at', true)"
Require-FunctionOrder $ContextProvider 'vg_build_guide_context' "vg_eeat_get_field(`$post->ID, 'last_meaningful_update')" "get_post_meta(`$post->ID, '_vg_reviewed_at', true)"
Require-FunctionOrder $ContextProvider 'vg_build_guide_context' "get_post_meta(`$post->ID, '_vg_reviewed_at', true)" "get_the_modified_date('F j, Y', `$post)"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "function_exists('vg_eeat_lines')"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "vg_eeat_lines(vg_eeat_get_field(`$post->ID, 'sources_checked'))"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' '$sourceCount = count($sourcesChecked);'
Require-FunctionContains $ContextProvider 'vg_build_guide_context' 'if ($sourceCount === 0) {'
Require-FunctionOrder $ContextProvider 'vg_build_guide_context' "vg_eeat_lines(vg_eeat_get_field(`$post->ID, 'sources_checked'))" 'vg_count_guide_sources($content[''body_html''])'
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "`$hasExistingRelated = vg_guide_body_has_related_routes(`$content['body_html']);"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "vg_extract_guide_data_value(`$content['body_html'], 'data-vg-best-for')"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "vg_extract_guide_data_value(`$content['body_html'], 'data-vg-skip-if')"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'post_id' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'type' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'title' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'permalink' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'hero_html' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'body_html' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'headings' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'toc_html' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'reviewed_at' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'reading_time' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'source_count' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'best_for' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'skip_if' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'related_routes' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'has_existing_related_routes' =>"

Require-File $GuideCss
Require-File $GuideJs
Require-Contains $Functions 'if (vg_is_guide_experience_page())'
Require-Matches $Functions "(?s)if\s*\(\s*vg_is_guide_experience_page\(\)\s*\)\s*\{[^{}]*wp_enqueue_style\s*\(\s*'vietnamguide-guide-experience'[^{}]*wp_enqueue_script\s*\(\s*'vietnamguide-guide-experience'[^{}]*\}" 'guide assets conditionally enqueued for guide experience pages'
Require-Matches $Functions "wp_enqueue_style\s*\(\s*'vietnamguide-guide-experience'" 'guide experience style handle'
Require-Matches $Functions "wp_enqueue_script\s*\(\s*'vietnamguide-guide-experience'" 'guide experience script handle'
Require-Contains $Functions "`$version = wp_get_theme()->get('Version');"
Require-Matches $Functions '(?s)wp_enqueue_style\s*\(\s*''vietnamguide-guide-experience''\s*,\s*get_theme_file_uri\(\s*''/assets/css/guide-experience\.css''\s*\)\s*,\s*\[\s*''vietnamguide-guide-patterns''\s*\]\s*,\s*\$version\s*\)' 'guide style dependency and shared version'
Require-Matches $Functions '(?s)wp_enqueue_script\s*\(\s*''vietnamguide-guide-experience''\s*,\s*get_theme_file_uri\(\s*''/assets/js/guide-experience\.js''\s*\)\s*,\s*\[\s*\]\s*,\s*\$version\s*,\s*true\s*\)' 'guide script empty dependencies, shared version, and footer loading'

$KeyGuideSelectors = @(
    '.vg-guide-experience::before',
    '.vg-guide-experience .vg-guide-hero-cover',
    '.vg-guide-experience .vg-guide-hero-inner',
    '.vg-guide-experience .vg-guide-title',
    '.vg-guide-meta',
    '.vg-guide-jump',
    '.vg-guide-spine',
    '.vg-guide-trust',
    '.vg-guide-article h2',
    '.vg-guide-article p',
    '.vg-guide-related',
    '.vg-guide-article .vg-concierge-verdict',
    '.vg-guide-article .vg-at-a-glance',
    '.vg-guide-article .vg-field-note',
    '.vg-guide-article .vg-related-cards',
    '.vg-guide-article .vg-decision-table__scroll',
    '.vg-guide-article .vg-timeline',
    '.vg-guide-article .vg-guide-photo-grid',
    '.vg-guide-article .vg-check-list',
    '.vg-guide-article .vg-feature-list',
    '.vg-guide-article .vg-faq-list',
    '.vg-guide-article .vg-travel-guide-flow',
    '.vg-guide-article .vg-travel-route-family'
)
foreach ($Selector in $KeyGuideSelectors) {
    Require-Contains $GuideCss $Selector
}

Require-Contains $GuideCss 'grid-template-columns: minmax(148px, 190px) minmax(0, 760px) minmax(190px, 240px)'
Require-Contains $GuideCss '@media (max-width: 1100px)'
Require-Contains $GuideCss '@media (max-width: 960px)'
Require-Contains $GuideCss '@media (max-width: 620px)'
Require-Contains $GuideCss '@media (prefers-reduced-motion: reduce)'
Require-Contains $GuideCss ':focus-visible'
Require-CssBlockContains $GuideCss '.vg-guide-experience {' 'overflow-x: clip;'
Require-CssBlockContains $GuideCss '.vg-guide-experience::before {' 'height: 3px;'
Require-CssBlockContains $GuideCss '.vg-guide-experience::before {' 'background: var(--vg-gold);'
Require-CssBlockContains $GuideCss '.vg-guide-experience .vg-guide-hero-cover {' 'align-items: flex-end;'
Require-CssBlockContains $GuideCss '.vg-guide-experience .vg-guide-hero-cover {' 'min-height: clamp(520px, 70svh, 780px);'
Require-CssBlockContains $GuideCss '.vg-guide-meta {' 'text-transform: uppercase;'
Require-CssBlockContains $GuideCss '.vg-guide-article h2 {' 'font-size: clamp(34px, 4vw, 56px);'
Require-CssBlockContains $GuideCss '.vg-guide-related h2 {' 'font-size: clamp(36px, 5vw, 64px);'
Require-CssBlockContains $GuideCss '.vg-guide-experience a:focus-visible,' 'outline: 3px solid var(--vg-gold);'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll,' 'overflow-x: auto;'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll,' 'border: 1px solid var(--vg-line);'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll,' 'margin-block: 36px;'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll:focus-visible,' 'outline: 3px solid var(--vg-gold);'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll > table,' 'width: 100%;'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll > table,' 'min-width: 680px;'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll > table,' 'margin: 0;'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll > table,' 'border-collapse: collapse;'
Require-NotContains $GuideCss '.vg-guide-article .vg-decision-table,'
Require-CssBlockContains $GuideCss '@media (max-width: 1100px)' 'grid-template-columns: minmax(136px, 170px) minmax(0, 1fr);'
Require-CssBlockContains $GuideCss '@media (max-width: 1100px)' 'grid-column: 2;'
Require-CssBlockContains $GuideCss '@media (max-width: 960px)' '.vg-guide-spine__toc'
Require-CssBlockContains $GuideCss '@media (max-width: 960px)' 'grid-template-columns: minmax(0, 1fr);'
Require-CssBlockContains $GuideCss '@media (max-width: 620px)' '.vg-guide-article .vg-guide-photo-grid'
Require-CssBlockContains $GuideCss '@media (max-width: 620px)' 'grid-template-columns: 1fr;'
Require-CssBlockContains $GuideCss '@media (prefers-reduced-motion: reduce)' 'scroll-behavior: auto !important;'
Require-CssBlockContains $GuideCss '@media (prefers-reduced-motion: reduce)' 'transition: none !important;'
Require-CssBlockContains $GuideCss '@media (prefers-reduced-motion: reduce)' 'animation: none !important;'
Require-CssBlockContains $GuideCss '@media (prefers-reduced-motion: reduce)' 'transform: none;'
Require-GuideCssScoped $GuideCss

Require-Contains $GuideJs "document.querySelector('[data-vg-guide]')"
Require-Contains $GuideJs "guide.querySelectorAll('table.vg-decision-table')"
Require-Contains $GuideJs "table.parentElement.classList.contains('vg-decision-table__scroll')"
Require-Matches $GuideJs "(?s)if\s*\(\s*table.parentElement\s*&&\s*table.parentElement.classList.contains\('vg-decision-table__scroll'\)\s*\)\s*\{\s*return;\s*\}" 'already wrapped legacy tables are skipped'
Require-Contains $GuideJs "document.createElement('div')"
Require-Contains $GuideJs "wrapper.className = 'vg-decision-table__scroll';"
Require-Contains $GuideJs "wrapper.setAttribute('tabindex', '0');"
Require-Contains $GuideJs 'table.parentNode.insertBefore(wrapper, table);'
Require-Contains $GuideJs 'wrapper.appendChild(table);'
Require-Contains $GuideJs "guide.querySelectorAll('.vg-guide-toc a[href^=`"#`"], .vg-guide-jump a[href^=`"#`"]')"
Require-Contains $GuideJs 'document.getElementById(id)'
Require-Contains $GuideJs 'IntersectionObserver'
Require-Contains $GuideJs "classList.toggle('is-active'"
Require-Contains $GuideJs 'Math.max(0, Math.min(100'
Require-Contains $GuideJs 'window.requestAnimationFrame(updateProgress)'
Require-Contains $GuideJs '{ passive: true }'
Require-Contains $GuideJs "style.setProperty('--vg-guide-progress'"
Require-Contains $GuideJs 'updateProgress();'
Require-NotContains $GuideJs 'preventDefault()'
Require-NotContains $GuideJs 'innerHTML'
Require-NotContains $GuideJs 'outerHTML'
Require-NotContains $GuideJs 'cloneNode'

if ($Failures.Count -gt 0) {
    $Failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output 'VietnamGuide guide experience checks passed.'

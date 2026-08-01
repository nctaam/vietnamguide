param(
    [string]$RepoRootOverride = ''
)

$ErrorActionPreference = 'Stop'
$RepoRoot = if ($RepoRootOverride) { $RepoRootOverride } else { Split-Path -Parent $PSScriptRoot }
$ThemeRoot = 'wordpress/wp-content/themes/vietnamguide-premium'
$HomepageCss = "$ThemeRoot/assets/css/homepage.css"
$GuideCss = "$ThemeRoot/assets/css/guide-experience.css"
$GuideJs = "$ThemeRoot/assets/js/guide-experience.js"
$GuideJsRuntimeVerifier = 'ops/verify-guide-experience-js-runtime.js'
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

function Get-UnscopedGuideCssSelectors {
    param([string]$Content)
    $UnscopedSelectors = [System.Collections.Generic.List[string]]::new()
    $SelectorMatches = [regex]::Matches($Content, '(?ms)(?:^|[{}])\s*([^{};][^{};]*)\{')
    foreach ($SelectorMatch in $SelectorMatches) {
        foreach ($SelectorValue in $SelectorMatch.Groups[1].Value.Split(',')) {
            $Selector = $SelectorValue.Trim()
            if ($Selector -eq '' -or $Selector.StartsWith('@')) { continue }
            if ($Selector -match '^(from|to|(?:[0-9]+(?:\.[0-9]+)?|\.[0-9]+)%)$') { continue }
            if ($Selector -notmatch '^\.vg-[A-Za-z0-9_-]+') {
                $UnscopedSelectors.Add($Selector)
            }
        }
    }

    return $UnscopedSelectors.ToArray()
}

function Require-GuideCssScoped {
    param([string]$RelativePath)
    $Content = Get-RepoContent $RelativePath
    if ($null -eq $Content) { return }

    foreach ($Selector in (Get-UnscopedGuideCssSelectors $Content)) {
        $Failures.Add("Unscoped guide CSS selector in ${RelativePath}: $Selector")
    }
}

function Require-GuideCssScopeSelfTest {
    $KeyframeFixture = '@keyframes vg-scope-check { from { opacity: 0; } 50%, .5% { opacity: .5; } to { opacity: 1; } } .vg-scope-check { opacity: 1; }'
    $KeyframeFailures = @(Get-UnscopedGuideCssSelectors $KeyframeFixture)
    if ($KeyframeFailures.Count -ne 0) {
        $Failures.Add("Guide CSS scope helper rejected keyframe selectors: $($KeyframeFailures -join ', ')")
    }

    $LeakFixture = 'body { overflow-x: hidden; } .vg-scope-check { display: block; }'
    $LeakFailures = @(Get-UnscopedGuideCssSelectors $LeakFixture)
    if ($LeakFailures -notcontains 'body') {
        $Failures.Add('Guide CSS scope helper failed to reject an unscoped body selector')
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
$Footer = "$ThemeRoot/footer.php"
$MutationVerifier = 'ops/verify-guide-experience-mutations.ps1'
$LiveVerifier = 'ops/verify-guide-experience-live.php'
$PublicVerifier = 'ops/verify-guide-experience-public.ps1'

Require-File $Routing
Require-File $ContentProvider
Require-File $ContextProvider
Require-File $Functions
Require-File $PageTemplate
Require-File $DefaultPart
Require-File $GuidePart
Require-File $Footer
Require-File $MutationVerifier
Require-File $LiveVerifier
Require-File $PublicVerifier
Require-File $GuideJsRuntimeVerifier
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-routing.php');"
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-content.php');"
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-context.php');"
Require-Contains $Routing 'function vg_guide_pilot_paths(): array'
Require-Contains $Routing 'function vg_classify_guide_path(string $path): ?string'
Require-Contains $Routing 'function vg_get_guide_path(?WP_Post $post = null): string'
Require-Contains $Routing 'function vg_get_guide_type(?WP_Post $post = null): ?string'
Require-Contains $Routing 'function vg_is_guide_experience_page(?WP_Post $post = null): bool'
Require-Contains $ContentProvider 'function vg_is_empty_freeform_block(array $block): bool'
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
Require-Contains $PageTemplate '$guideFunctionsReady = function_exists(''vg_is_guide_experience_page'')'
Require-Contains $PageTemplate "&& function_exists('vg_build_guide_context')"
Require-Contains $PageTemplate "&& function_exists('vg_is_valid_guide_context');"
Require-Contains $PageTemplate '$post instanceof WP_Post && $guideFunctionsReady && vg_is_guide_experience_page($post)'
Require-Contains $PageTemplate '$guideFunctionsReady && is_array($guideContext) && vg_is_valid_guide_context($guideContext)'
Require-Matches $PageTemplate '(?s)\$guideFunctionsReady\s*=.*?if\s*\(\$post\s+instanceof\s+WP_Post\s+&&\s+\$guideFunctionsReady\s+&&\s+vg_is_guide_experience_page\(\$post\)' 'guide function availability is checked before guide routing'
Require-Matches $PageTemplate '(?s)\$guideFunctionsReady\s*=.*?if\s*\(\$guideFunctionsReady\s+&&\s+is_array\(\$guideContext\)\s+&&\s+vg_is_valid_guide_context\(\$guideContext\)' 'guide function availability is checked before context validation'
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
Require-Contains $Footer "esc_url(vg_home_url('source-update-policy'))"
Require-NotContains $Footer "vg_home_url('source-policy')"

Require-Contains $MutationVerifier '$RequiredContractPaths = @('
Require-Contains $MutationVerifier "[guid]::NewGuid().ToString('N')"
Require-Contains $MutationVerifier '$ValidatedTempRoot = (Resolve-Path -LiteralPath $TempRoot).Path'
Require-Contains $MutationVerifier '-RepoRootOverride $MutationRoot'
Require-Contains $MutationVerifier 'if ($LASTEXITCODE -eq 0) {'
Require-Contains $MutationVerifier 'finally {'
Require-Contains $MutationVerifier 'Remove-Item -LiteralPath $ValidatedTempRoot -Recurse -Force'
Require-Contains $MutationVerifier 'pilot allowlist bypass'
Require-Contains $MutationVerifier 'page guide function availability guard removal'
Require-Contains $MutationVerifier 'global reduced-motion scroll override removal'
Require-Contains $MutationVerifier 'guide fragment heading offset removal'
Require-Contains $MutationVerifier 'active guide aria-current assignment removal'
Require-Contains $MutationVerifier 'inactive guide aria-current cleanup removal'
Require-Contains $MutationVerifier 'non-H2 ID reservation removal'
Require-Contains $MutationVerifier 'public guide asset status guard removal'
Require-Contains $MutationVerifier 'public semantic H1 guard removal'
Require-Contains $MutationVerifier 'public semantic guide shell guard removal'
Require-Contains $MutationVerifier 'public semantic guide navigation guard removal'
Require-Contains $MutationVerifier 'public DOM snapshot reuse removal'
Require-Contains $MutationVerifier 'public inert raw-text tokenizer state removal'
Require-Contains $MutationVerifier 'public script double-escaped state removal'
Require-Contains $MutationVerifier 'public HTML tag-name delimiter validation removal'
Require-Contains $MutationVerifier 'public opening self-close delimiter rejection'
Require-Contains $MutationVerifier 'public HTML tag-prefix grammar relaxation'
Require-Contains $MutationVerifier 'public custom-origin fixture isolation removal'
Require-Contains $MutationVerifier 'public semantic H1 inventory removal'
Require-Contains $MutationVerifier 'public semantic guide shell inventory removal'
Require-Contains $MutationVerifier 'public semantic guide navigation inventory removal'
Require-Contains $MutationVerifier 'public semantic stylesheet element inventory removal'
Require-Contains $MutationVerifier 'public semantic script element inventory removal'
Require-Contains $MutationVerifier 'public semantic stylesheet relation guard removal'
Require-Contains $MutationVerifier 'public asset exact origin guard removal'
Require-Contains $MutationVerifier 'public asset credentials guard removal'
Require-Contains $MutationVerifier 'public asset exact path guard removal'
Require-Contains $MutationVerifier 'public asset cache query guard removal'
Require-Contains $MutationVerifier 'public asset fragment guard removal'
Require-Contains $MutationVerifier 'public asset redirect rejection removal'
Require-Contains $MutationVerifier 'public asset MIME guard removal'
Require-Contains $MutationVerifier 'public asset response URI guard removal'
Require-Contains $MutationVerifier 'public asset SHA-256 parity guard removal'
Require-Contains $MutationVerifier 'public local asset fail-closed guard removal'
Require-Contains $MutationVerifier 'public guide fragment target guard removal'
Require-Contains $MutationVerifier 'public non-pilot inventory regression'
Require-Contains $MutationVerifier 'leading comment-only freeform rejection'
Require-Contains $MutationVerifier 'meaningful leading freeform acceptance'
Require-Contains $MutationVerifier 'rendered hero H1 guard removal'
Require-Contains $MutationVerifier 'rendered body H1 guard removal'
Require-Contains $MutationVerifier 'required hero class guard removal'
Require-Contains $MutationVerifier 'heading HTML round-trip guard removal'
Require-Contains $MutationVerifier 'heading list round-trip guard removal'
Require-Contains $MutationVerifier 'unique heading ID guard removal'
Require-Contains $MutationVerifier 'opted-out heading inclusion'
Require-Contains $MutationVerifier 'reserved heading collision guard removal'
Require-Contains $MutationVerifier 'assigned heading collision guard removal'
Require-Contains $MutationVerifier 'incomplete rendered HTML guard removal'
Require-Contains $MutationVerifier 'incomplete heading-plan guard removal'
Require-Contains $MutationVerifier 'heading application count guard removal'
Require-Contains $MutationVerifier 'duplicate literal guide H1'
Require-Contains $MutationVerifier 'global guide asset enqueue'
Require-Contains $MutationVerifier 'legacy table wrapper removal'
Require-Contains $MutationVerifier 'legacy table idempotence guard removal'
Require-Contains $MutationVerifier 'legacy table focus guard removal'
Require-Contains $MutationVerifier 'legacy table preventDefault regression'
Require-Contains $MutationVerifier 'canonical TOC relationship removal'
Require-Contains $MutationVerifier 'EEAT reviewed precedence inversion'
Require-Contains $MutationVerifier 'invalid curated URL shape guard removal'
Require-Contains $MutationVerifier 'invalid curated route item guard removal'
Require-Contains $MutationVerifier 'existing related-route suppression removal'
Require-Contains $MutationVerifier 'existing related-route context invariant removal'
Require-Contains $MutationVerifier 'password-protected fallback removal'
Require-Contains $MutationVerifier 'multipage fallback removal'
Require-NotContains $MutationVerifier "<h2\b"
Require-FunctionContains $MutationVerifier 'Copy-ContractTree' 'Copy-Item -LiteralPath $SourcePath -Destination $DestinationPath'
Require-FunctionContains $MutationVerifier 'Set-ExactReplacement' '$SecondIndex'
Require-FunctionContains $MutationVerifier 'Set-ExactReplacement' '[System.IO.File]::WriteAllText'
Require-FunctionOrder $MutationVerifier 'Set-ExactReplacement' '$SecondIndex' '[System.IO.File]::WriteAllText'
Require-Matches $MutationVerifier '(?s)try\s*\{.*?\}\s*finally\s*\{.*?Remove-Item\s+-LiteralPath\s+\$ValidatedTempRoot\s+-Recurse\s+-Force' 'validated mutation temp cleanup in finally'

Require-Matches $LiveVerifier '(?s)\A<\?php\s+/\*\*.*?if\s*\(!\s*defined\(''ABSPATH''\)\s*\)\s*\{.*?WordPress is not loaded' 'live verifier ABSPATH failure guard'
Require-Contains $LiveVerifier "'vg_guide_pilot_paths'"
Require-Contains $LiveVerifier "'vg_get_guide_type'"
Require-Contains $LiveVerifier "'vg_is_guide_experience_page'"
Require-Contains $LiveVerifier "'vg_prepare_guide_content'"
Require-Contains $LiveVerifier "'vg_prepare_guide_headings'"
Require-Contains $LiveVerifier "'vg_render_guide_toc'"
Require-Contains $LiveVerifier "'vg_normalize_guide_route_url'"
Require-Contains $LiveVerifier "'vg_get_related_routes'"
Require-Contains $LiveVerifier "'vg_is_valid_guide_context'"
Require-Contains $LiveVerifier "'vg_build_guide_context'"
Require-Matches $LiveVerifier '(?s)\$required_functions\s*=\s*\[.*?''vg_eeat_get_field''.*?''vg_eeat_lines''.*?''vg_eeat_related_route_items''.*?\];' 'EEAT helpers in the required live function inventory'
Require-Contains $LiveVerifier 'WP_HTML_Tag_Processor'
Require-Contains $LiveVerifier "`$result['context']['type'] === `$expected_type"
Require-Contains $LiveVerifier 'destinations/ho-chi-minh-city-travel-guide'
Require-Contains $LiveVerifier 'itineraries/10-days-in-vietnam'
Require-Contains $LiveVerifier 'compare/ha-long-bay-vs-lan-ha-bay'
Require-Contains $LiveVerifier 'plan/vietnam-evisa'
Require-Contains $LiveVerifier '$pseudo_inspection = vg_inspect_guide_html($pseudo_html);'
Require-Contains $LiveVerifier 'vg_prepare_guide_content($pseudo_post)'
Require-Contains $LiveVerifier 'vg_is_valid_guide_context($pseudo_context)'
foreach ($FixtureLabel in @(
    'pilot strict context'
    'rendered hero/body H1 contract'
    'leading comment-only and whitespace-only freeform markers'
    'meaningful leading freeform rejection'
    'filter-generated H1 fails closed'
    'script and comment pseudo-headings ignored'
    'authored heading IDs and deterministic collisions'
    'non-H2 element IDs reserve heading slugs'
    'opted-out headings excluded without collisions'
    'incomplete markup fails closed'
    'canonical EEAT metadata precedence'
    'curated route URL normalization'
    'protected and multipage fallback'
    'malformed context rejection'
    'canonical heading and TOC relationship'
    'embedded related-route conflict rejection'
)) {
    Require-Contains $LiveVerifier $FixtureLabel
}
Require-Contains $LiveVerifier "add_filter('get_post_metadata'"
Require-Contains $LiveVerifier "remove_filter('get_post_metadata'"
Require-Contains $LiveVerifier 'finally {'
Require-Contains $LiveVerifier 'catch (Throwable $throwable)'
Require-Matches $LiveVerifier "(?s)add_filter\('get_post_metadata'.*?try\s*\{.*?\}\s*finally\s*\{\s*remove_filter\('get_post_metadata'" 'metadata filters restored in finally'
Require-NotContains $LiveVerifier "if (function_exists('vg_eeat_get_field') && function_exists('vg_eeat_lines')) {"
Require-NotContains $LiveVerifier "if (function_exists('vg_eeat_get_field') && function_exists('vg_eeat_related_route_items')) {"
Require-Contains $LiveVerifier '<script>window.fake = "<h1>script heading</h1>";</script>'
Require-Contains $LiveVerifier '<!-- <h1>comment heading</h1> -->'
Require-Contains $LiveVerifier '<!-- vg-hcmc-hero:v1 -->'
Require-Contains $LiveVerifier '<p>Meaningful introduction.</p>'
Require-Contains $LiveVerifier "'#tag' !== `$processor->get_token_type()"
Require-Contains $LiveVerifier "'all_ids'"
Require-Contains $LiveVerifier '<div id="arrival"><h3 id="local-transport">'
Require-Contains $LiveVerifier "vg_inspect_guide_html((string) `$pseudo_content['hero_html'])"
foreach ($EeatFunction in @(
    'vg_eeat_get_field'
    'vg_eeat_lines'
    'vg_eeat_related_route_items'
)) {
    Require-Matches $LiveVerifier "(?s)\`$required_functions\s*=\s*\[.*?'$EeatFunction'.*?\]" "required live EEAT helper inventory includes $EeatFunction"
}
Require-NotContains $LiveVerifier 'if ($runtime_ready && $pilot_posts !== []) {'
Require-Contains $LiveVerifier 'WP_CLI::error'
Require-Contains $LiveVerifier 'VietnamGuide guide experience live verification passed.'
Require-NotContains $LiveVerifier 'wp_insert_post('
Require-NotContains $LiveVerifier 'wp_update_post('
Require-NotContains $LiveVerifier 'update_post_meta('
Require-NotContains $LiveVerifier 'delete_post_meta('

Require-Contains $PublicVerifier "[string]`$BaseUrl = 'https://vietnamguide.net'"
Require-Contains $PublicVerifier '[switch]$FixturesOnly'
Require-Contains $PublicVerifier 'Invoke-WebRequest'
Require-Contains $PublicVerifier '-TimeoutSec'
Require-Contains $PublicVerifier 'try {'
Require-Contains $PublicVerifier 'catch {'
Require-Contains $PublicVerifier 'destinations/ho-chi-minh-city-travel-guide'
Require-Contains $PublicVerifier 'itineraries/10-days-in-vietnam'
Require-Contains $PublicVerifier 'compare/ha-long-bay-vs-lan-ha-bay'
Require-Contains $PublicVerifier 'plan/vietnam-evisa'
Require-Contains $PublicVerifier 'StatusCode -ne 200'
Require-Contains $PublicVerifier 'data-vg-guide'
Require-Contains $PublicVerifier 'guide-experience.css'
Require-Contains $PublicVerifier 'guide-experience.js'
Require-Contains $PublicVerifier 'vg-guide-toc'
Require-Contains $PublicVerifier 'fatal error'
Require-Contains $PublicVerifier 'source-update-policy'
Require-Contains $PublicVerifier 'function Get-PublicResource'
Require-Contains $PublicVerifier 'function Get-PublicAssetUrl'
Require-Contains $PublicVerifier 'function Get-PublicDomSnapshot'
Require-Contains $PublicVerifier 'function Find-PublicHtmlTagEnd'
Require-Contains $PublicVerifier 'function Test-PublicHtmlTagNameDelimiter'
Require-Contains $PublicVerifier 'function Find-PublicScriptEnd'
Require-Contains $PublicVerifier 'function Convert-PublicHtmlForMshtml'
Require-Contains $PublicVerifier 'function Get-NormalizedOriginKey'
Require-Contains $PublicVerifier 'function Test-PublicAssetUri'
Require-Contains $PublicVerifier 'function Test-PublicAssetResponse'
Require-Contains $PublicVerifier 'function Require-GuideFragmentTargets'
Require-Contains $PublicVerifier 'destinations/hanoi-travel-guide'
Require-Contains $PublicVerifier 'itineraries/14-days-in-vietnam'
Require-Contains $PublicVerifier 'compare/da-nang-vs-hoi-an'
Require-Contains $PublicVerifier 'plan/sim-esim-vietnam'
Require-NotContains $PublicVerifier "Get-PublicPage -Path 'about'"
Require-Contains $PublicVerifier "expected exactly one target ID"
Require-Contains $PublicVerifier "guide CSS asset"
Require-Contains $PublicVerifier "guide JavaScript asset"
Require-FunctionContains $PublicVerifier 'Get-PublicPage' 'Invoke-WebRequest'
Require-FunctionContains $PublicVerifier 'Get-PublicPage' '-TimeoutSec $RequestTimeoutSeconds'
Require-FunctionContains $PublicVerifier 'Get-PublicPage' 'Dom = $Dom'
Require-FunctionContains $PublicVerifier 'Get-PublicPage' 'catch {'
Require-FunctionContains $PublicVerifier 'Require-NoFatalText' "'fatal error'"
Require-FunctionContains $PublicVerifier 'Get-PublicResource' 'Invoke-WebRequest'
Require-FunctionContains $PublicVerifier 'Get-PublicResource' '-MaximumRedirection 0'
Require-FunctionContains $PublicVerifier 'Get-PublicResource' 'RawContentStream'
Require-FunctionContains $PublicVerifier 'Get-PublicResource' 'Test-PublicAssetResponse'
Require-FunctionContains $PublicVerifier 'Get-NormalizedOriginKey' 'UserInfo'
Require-FunctionContains $PublicVerifier 'Get-NormalizedOriginKey' 'IsDefaultPort'
Require-FunctionContains $PublicVerifier 'Get-PublicExpectedAssets' 'if (-not (Test-Path -LiteralPath $LocalPath -PathType Leaf)) {'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetUri' 'if (-not [string]::IsNullOrEmpty($Resolved.UserInfo)) {'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetUri' '(Get-NormalizedOriginKey $Resolved) -ne $BaseOriginKey'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetUri' '$Resolved.AbsolutePath -cne $ExpectedAsset.Path'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetUri' "-cnotmatch '^\?ver=[A-Za-z0-9._-]+$'"
Require-FunctionContains $PublicVerifier 'Test-PublicAssetUri' '$Resolved.Fragment -ne '''''
Require-FunctionContains $PublicVerifier 'Test-PublicAssetResponse' '$StatusCode -ne 200'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetResponse' '$AllowedContentTypes -notcontains $ContentType'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetResponse' '$ActualHash.Equals($ExpectedAsset.Sha256'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetResponse' 'if ($ResponseUri.AbsoluteUri -ne $RequestedUri.AbsoluteUri) {'
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' 'New-Object -ComObject HTMLFile'
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' 'Convert-PublicHtmlForMshtml'
Require-FunctionContains $PublicVerifier 'Find-PublicRawTextEnd' 'Test-PublicHtmlTagNameDelimiter'
Require-FunctionContains $PublicVerifier 'Find-PublicRawTextEnd' 'Find-PublicScriptEnd'
Require-FunctionContains $PublicVerifier 'Find-PublicScriptEnd' "`$State = 'double-escaped'"
Require-FunctionContains $PublicVerifier 'Find-PublicScriptEnd' "`$State = 'escaped'"
Require-FunctionContains $PublicVerifier 'Find-PublicScriptEnd' "`$State = 'data'"
Require-FunctionContains $PublicVerifier 'Find-PublicScriptEnd' 'Test-PublicHtmlTagNameDelimiter'
Require-FunctionContains $PublicVerifier 'Convert-PublicHtmlForMshtml' 'Test-PublicHtmlTagNameDelimiter'
Require-FunctionContains $PublicVerifier 'Convert-PublicHtmlForMshtml' "'^<(?<closing>/)?(?<name>[A-Za-z][A-Za-z0-9:-]*)'"
Require-FunctionNotContains $PublicVerifier 'Get-PublicDomSnapshot' "[regex]::Replace(`$Html, '(?is)<(?:template|noscript)"
Require-FunctionNotContains $PublicVerifier 'Get-PublicDomSnapshot' "[regex]::Replace(`$RenderableHtml, '(?is)<nav"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "getElementsByTagName('*')"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "getElementsByTagName('div')"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "getAttribute('data-vg-dom-nav')"
Require-FunctionContains $PublicVerifier 'Convert-PublicHtmlForMshtml' "`$TagName -in @('template', 'noscript')"
Require-FunctionContains $PublicVerifier 'Convert-PublicHtmlForMshtml' "`$TagName -eq 'nav'"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' 'vg-guide-(?:toc|jump)'
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "H1Count = @(`$Document.getElementsByTagName('h1')).Count"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "if (`$null -ne `$Element.getAttributeNode('data-vg-guide')) {"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "foreach (`$Navigation in `$Document.getElementsByTagName('div')) {"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "foreach (`$Link in `$Document.getElementsByTagName('link')) {"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "foreach (`$Script in `$Document.getElementsByTagName('script')) {"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "`$RelTokens -contains 'stylesheet'"
Require-FunctionContains $PublicVerifier 'Require-GuideFragmentTargets' 'HtmlDecode'
Require-FunctionContains $PublicVerifier 'Require-GuideFragmentTargets' 'UnescapeDataString'
Require-FunctionContains $PublicVerifier 'Require-GuideFragmentTargets' '$TargetCount -ne 1'
Require-FunctionContains $PublicVerifier 'Require-GuideFragmentTargets' '$Snapshot = $Page.Dom'
Require-FunctionNotContains $PublicVerifier 'Require-GuideFragmentTargets' 'Get-PublicDomSnapshot -Html $Page.Content'
Require-Contains $PublicVerifier 'if ($Page.Dom.H1Count -ne 1) {'
Require-Contains $PublicVerifier 'if (-not $Page.Dom.HasGuideShell) {'
Require-Contains $PublicVerifier 'if (-not $Page.Dom.HasGuideNavigation) {'
Require-Contains $PublicVerifier 'DOM parser fixture accepted inert pseudo guide markup'
Require-Contains $PublicVerifier 'inert raw-text fixture exposed template descendants'
Require-Contains $PublicVerifier 'script double-escaped fixture exposed inert guide markup'
Require-Contains $PublicVerifier 'ordinary script close fixture did not expose reviewed guide markup'
Require-Contains $PublicVerifier 'escaped script close fixture did not expose reviewed guide markup'
Require-Contains $PublicVerifier 'double-escaped script comment close did not return to script data'
Require-Contains $PublicVerifier 'malformed template closing delimiter exposed inert descendants'
Require-Contains $PublicVerifier 'malformed raw-text closing delimiter exposed inert descendants'
Require-Contains $PublicVerifier 'valid whitespace closing delimiter was rejected'
Require-Contains $PublicVerifier 'malformed template tag prefix exposed inert descendants'
Require-Contains $PublicVerifier 'malformed raw-text tag prefix exposed inert descendants'
Require-Contains $PublicVerifier 'malformed opening tag prefix was treated as an inert tag'
Require-Contains $PublicVerifier 'malformed raw-text opening tag changed semantic guide inventory'
Require-Contains $PublicVerifier 'valid opening self-close slash delimiter was rejected'
Require-Contains $PublicVerifier "`$OpeningSelfCloseProbe = '<vg-probe/>'"
Require-Contains $PublicVerifier '$FixtureOrigin = $BaseUri.GetLeftPart([System.UriPartial]::Authority).TrimEnd(''/'')'
Require-Contains $PublicVerifier 'asset URI fixture accepted external host'
Require-Contains $PublicVerifier 'asset URI fixture accepted scheme or port mismatch'
Require-Contains $PublicVerifier 'asset URI fixture accepted credentials'
Require-Contains $PublicVerifier 'asset URI fixture accepted wrong theme path'
Require-Contains $PublicVerifier 'asset URI fixture accepted invalid cache query or fragment'
Require-Contains $PublicVerifier 'asset response fixture accepted redirect status'
Require-Contains $PublicVerifier 'asset response fixture accepted HTML error body or MIME'
Require-Contains $PublicVerifier 'asset response fixture accepted wrong SHA-256'
Require-Contains $PublicVerifier "Join-Path `$PSScriptRoot '..\wordpress\wp-content\themes\vietnamguide-premium\assets'"
Require-Contains $PublicVerifier 'local reviewed asset is missing'
Require-Contains $PublicVerifier 'VietnamGuide public verifier fixtures passed for $BaseOriginKey.'
Require-NotContains $PublicVerifier '$H1Count = [regex]::Matches'
Require-NotContains $PublicVerifier "Require-PublicContains `$Page 'data-vg-guide'"
Require-NotContains $PublicVerifier "Require-PublicContains `$Page 'guide-experience.css'"
Require-NotContains $PublicVerifier "Require-PublicContains `$Page 'guide-experience.js'"
Require-NotContains $PublicVerifier "`$Page.Content.Contains('vg-guide-jump')"

$PublicFixtureOrigins = @(
    'https://vietnamguide.net'
    'http://staging.example:8081'
    'https://staging.example:444'
)
foreach ($PublicFixtureOrigin in $PublicFixtureOrigins) {
    $PreviousErrorActionPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $PublicFixtureOutput = & powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $RepoRoot $PublicVerifier) -BaseUrl $PublicFixtureOrigin -FixturesOnly 2>&1
        $PublicFixtureExitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $PreviousErrorActionPreference
    }
    if ($PublicFixtureExitCode -ne 0) {
        $Failures.Add("Public verifier fixtures failed for ${PublicFixtureOrigin}: $($PublicFixtureOutput -join ' ')")
    }
}

$PublicVerifierContent = Get-RepoContent $PublicVerifier
if ($null -ne $PublicVerifierContent) {
    $NonPilotArray = [regex]::Match($PublicVerifierContent, '(?s)\$NonPilotPaths\s*=\s*@\((?<items>.*?)\)')
    if (-not $NonPilotArray.Success) {
        $Failures.Add('Missing exact non-pilot public verification inventory')
    } else {
        $NonPilotPaths = @([regex]::Matches($NonPilotArray.Groups['items'].Value, "'([^']+)'") | ForEach-Object { $_.Groups[1].Value })
        Require-ExactSet 'public non-pilot path' $NonPilotPaths @(
            'destinations/hanoi-travel-guide'
            'itineraries/14-days-in-vietnam'
            'compare/da-nang-vs-hoi-an'
            'plan/sim-esim-vietnam'
        )
    }
}

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

Require-FunctionContains $ContentProvider 'vg_is_empty_freeform_block' "(`$block['blockName'] ?? null) !== null"
Require-FunctionContains $ContentProvider 'vg_is_empty_freeform_block' "trim(`$html) === ''"
Require-FunctionContains $ContentProvider 'vg_is_empty_freeform_block' "preg_replace('/<!--[\s\S]*?-->/', '', `$html)"
Require-FunctionContains $ContentProvider 'vg_is_empty_freeform_block' "is_string(`$without_comments) && trim(`$without_comments) === ''"
Require-FunctionOrder $ContentProvider 'vg_is_empty_freeform_block' "(`$block['blockName'] ?? null) !== null" "preg_replace('/<!--[\s\S]*?-->/', '', `$html)"
Require-FunctionOrder $ContentProvider 'vg_is_empty_freeform_block' "preg_replace('/<!--[\s\S]*?-->/', '', `$html)" "is_string(`$without_comments) && trim(`$without_comments) === ''"
Require-FunctionContains $ContentProvider 'vg_split_guide_blocks' 'parse_blocks($postContent)'
Require-FunctionContains $ContentProvider 'vg_split_guide_blocks' 'vg_is_empty_freeform_block($blocks[0])'
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
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' '$isTagCloser = $processor->is_tag_closer();'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'if (! $isTagCloser) {'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' '$elementId = $processor->get_attribute(''id'');'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'vg_is_valid_guide_heading_id($elementId)'
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
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' '$eligible = ! $heading[''opt_out''] && $label !== '''';'
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
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' 'if (! $isRootRelative && ! $isProtocolRelative && ! $isAbsoluteWeb) {'
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

Require-File $HomepageCss
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
Require-CssBlockContains $HomepageCss '@media (prefers-reduced-motion: reduce)' 'scroll-behavior: auto !important;'
Require-Matches $HomepageCss '(?s)@media\s*\(prefers-reduced-motion:\s*reduce\)\s*\{.*?html\s*\{\s*scroll-behavior:\s*auto\s*!important;' 'explicit reduced-motion override for root smooth scrolling'
Require-CssBlockContains $GuideCss '.vg-guide-experience {' 'overflow-x: clip;'
Require-CssBlockContains $GuideCss '.vg-guide-experience::before {' 'height: 3px;'
Require-CssBlockContains $GuideCss '.vg-guide-experience::before {' 'background: var(--vg-gold);'
Require-CssBlockContains $GuideCss '.vg-guide-experience .vg-guide-hero-cover {' 'align-items: flex-end;'
Require-CssBlockContains $GuideCss '.vg-guide-experience .vg-guide-hero-cover {' 'min-height: clamp(520px, 70svh, 780px);'
Require-CssBlockContains $GuideCss '.vg-guide-meta {' 'text-transform: uppercase;'
Require-CssBlockContains $GuideCss '.vg-guide-article h2 {' 'font-size: clamp(34px, 4vw, 56px);'
Require-CssBlockContains $GuideCss '.vg-guide-article h2 {' 'scroll-margin-top: calc(var(--vg-header-height) + 24px);'
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
Require-GuideCssScopeSelfTest

$HomepageCssContent = Get-RepoContent $HomepageCss
$GuideCssContent = Get-RepoContent $GuideCss
if ($null -ne $HomepageCssContent -and $null -ne $GuideCssContent) {
    $DesktopHeaderMatch = [regex]::Match($HomepageCssContent, '(?s):root\s*\{.*?--vg-header-height:\s*(?<height>\d+)px;')
    $MobileHeaderMatch = [regex]::Match($HomepageCssContent, '(?s)@media\s*\(max-width:\s*760px\)\s*\{.*?:root\s*\{.*?--vg-header-height:\s*(?<height>\d+)px;')
    $HeadingOffsetMatch = [regex]::Match($GuideCssContent, '(?s)\.vg-guide-article h2\s*\{.*?scroll-margin-top:\s*calc\(var\(--vg-header-height\)\s*\+\s*(?<gap>\d+)px\);')
    if (-not $DesktopHeaderMatch.Success -or -not $MobileHeaderMatch.Success -or -not $HeadingOffsetMatch.Success) {
        $Failures.Add('Guide fragment offset fixture could not resolve desktop, mobile, and heading offset values')
    } else {
        $DesktopHeader = [int]$DesktopHeaderMatch.Groups['height'].Value
        $MobileHeader = [int]$MobileHeaderMatch.Groups['height'].Value
        $HeadingGap = [int]$HeadingOffsetMatch.Groups['gap'].Value
        if ($HeadingGap -lt 16 -or ($DesktopHeader + $HeadingGap) -le $DesktopHeader -or ($MobileHeader + $HeadingGap) -le $MobileHeader) {
            $Failures.Add('Guide fragment offset fixture did not clear the sticky header with sufficient breathing room')
        }
    }
}

Require-Contains $GuideJs "document.querySelector('[data-vg-guide]')"
Require-Contains $GuideJs "guide.querySelectorAll('table.vg-decision-table')"
Require-Contains $GuideJs "table.parentElement.classList.contains('vg-decision-table__scroll')"
Require-Matches $GuideJs "(?s)if\s*\(\s*table.parentElement\s*&&\s*table.parentElement.classList.contains\('vg-decision-table__scroll'\)\s*\)\s*\{\s*return;\s*\}" 'already wrapped legacy tables are skipped'
Require-Contains $GuideJs "document.createElement('div')"
Require-Contains $GuideJs "wrapper.className = 'vg-decision-table__scroll';"
Require-Contains $GuideJs "wrapper.setAttribute('tabindex', '0');"
Require-Contains $GuideJs 'table.parentNode.insertBefore(wrapper, table);'
Require-Contains $GuideJs 'wrapper.appendChild(table);'
Require-Contains $GuideJs "guide.querySelectorAll('.vg-decision-table__scroll, .wp-block-table')"
Require-Contains $GuideJs "scrollContainer.hasAttribute('tabindex')"
Require-Contains $GuideJs "scrollContainer.setAttribute('tabindex', '0');"
Require-Contains $GuideJs "guide.querySelectorAll('.vg-guide-toc a[href^=`"#`"], .vg-guide-jump a[href^=`"#`"]')"
Require-Contains $GuideJs 'document.getElementById(id)'
Require-Contains $GuideJs 'IntersectionObserver'
Require-Contains $GuideJs "classList.toggle('is-active'"
Require-Contains $GuideJs "setAttribute('aria-current', 'location')"
Require-Contains $GuideJs "removeAttribute('aria-current')"
Require-Contains $GuideJs 'var activeId = null;'
Require-Contains $GuideJs 'if (id === activeId) {'
Require-Contains $GuideJs 'var lastSection = sections.length > 0 ? sections[sections.length - 1] : null;'
Require-Contains $GuideJs 'var nearGuideEnd = false;'
Require-Contains $GuideJs 'progress >= 99.5'
Require-Contains $GuideJs 'rect.bottom <= window.innerHeight * 1.15'
Require-Contains $GuideJs 'setActive(lastSection.id);'
Require-Contains $GuideJs 'Math.max(0, Math.min(100'
Require-Contains $GuideJs 'window.requestAnimationFrame(updateProgress)'
Require-Contains $GuideJs '{ passive: true }'
Require-Contains $GuideJs "style.setProperty('--vg-guide-progress'"
Require-Contains $GuideJs 'updateProgress();'
Require-NotContains $GuideJs 'preventDefault()'
Require-NotContains $GuideJs 'innerHTML'
Require-NotContains $GuideJs 'outerHTML'
Require-NotContains $GuideJs 'cloneNode'

$NodeCommand = Get-Command node -ErrorAction SilentlyContinue
if ($null -eq $NodeCommand) {
    $Failures.Add('Node.js is required for the guide JavaScript runtime fixture')
} else {
    $PreviousErrorActionPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $GuideJsRuntimeOutput = & $NodeCommand.Source (Join-Path $RepoRoot $GuideJsRuntimeVerifier) (Join-Path $RepoRoot $GuideJs) 2>&1
        $GuideJsRuntimeExitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $PreviousErrorActionPreference
    }
    if ($GuideJsRuntimeExitCode -ne 0) {
        $Failures.Add("Guide JavaScript runtime fixture failed: $($GuideJsRuntimeOutput -join ' ')")
    }
}

if ($Failures.Count -gt 0) {
    $Failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output 'VietnamGuide guide experience checks passed.'

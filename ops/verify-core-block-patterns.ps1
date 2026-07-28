$ErrorActionPreference = 'Stop'

$RepoRoot = Split-Path -Parent $PSScriptRoot
$ThemeRoot = 'wordpress/wp-content/themes/vietnamguide-premium'
$PatternRoot = "$ThemeRoot/patterns"
$Failures = [System.Collections.Generic.List[string]]::new()

function Get-RepoContent {
    param([string]$RelativePath)

    $FullPath = Join-Path $RepoRoot $RelativePath
    if (-not (Test-Path -LiteralPath $FullPath -PathType Leaf)) {
        return $null
    }

    $Content = Get-Content -LiteralPath $FullPath -Raw
    if ($null -eq $Content) {
        return ''
    }

    return $Content
}

function Require-File {
    param([string]$RelativePath)

    if ($null -eq (Get-RepoContent $RelativePath)) {
        $Failures.Add("Missing file: $RelativePath")
    }
}

function Require-Contains {
    param(
        [string]$RelativePath,
        [string]$Needle
    )

    $Content = Get-RepoContent $RelativePath
    if ($null -ne $Content -and -not $Content.Contains($Needle)) {
        $Failures.Add("Missing substring in ${RelativePath}: $Needle")
    }
}

function Require-Matches {
    param(
        [string]$RelativePath,
        [string]$Pattern
    )

    $Content = Get-RepoContent $RelativePath
    if ($null -ne $Content -and -not [regex]::IsMatch($Content, $Pattern)) {
        $Failures.Add("Missing pattern in ${RelativePath}: $Pattern")
    }
}

function Require-NotMatches {
    param(
        [string]$RelativePath,
        [string]$Pattern,
        [string]$Description
    )

    $Content = Get-RepoContent $RelativePath
    if ($null -ne $Content -and [regex]::IsMatch($Content, $Pattern, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)) {
        $Failures.Add("Forbidden $Description in ${RelativePath}")
    }
}

function Require-MatchCount {
    param(
        [string]$RelativePath,
        [string]$Pattern,
        [int]$ExpectedCount
    )

    $Content = Get-RepoContent $RelativePath
    if ($null -eq $Content) {
        return
    }

    $ActualCount = [regex]::Matches(
        $Content,
        $Pattern,
        [System.Text.RegularExpressions.RegexOptions]::IgnoreCase
    ).Count
    if ($ActualCount -ne $ExpectedCount) {
        $Failures.Add("Expected $ExpectedCount matches in ${RelativePath}, found ${ActualCount}: $Pattern")
    }
}

function Require-Sha256 {
    param(
        [string]$RelativePath,
        [string]$ExpectedHash
    )

    $FullPath = Join-Path $RepoRoot $RelativePath
    if (-not (Test-Path -LiteralPath $FullPath -PathType Leaf)) {
        $Failures.Add("Missing preserved pattern: $RelativePath")
        return
    }

    $ActualHash = (Get-FileHash -LiteralPath $FullPath -Algorithm SHA256).Hash.ToLowerInvariant()
    if ($ActualHash -ne $ExpectedHash) {
        $Failures.Add("Preserved homepage pattern changed: $RelativePath")
    }
}

function Require-BalancedBlockComments {
    param([string]$RelativePath)

    $Content = Get-RepoContent $RelativePath
    if ($null -eq $Content) {
        return
    }

    $Comments = [regex]::Matches(
        $Content,
        '<!--\s*(/?)wp:([a-z0-9-]+)\b.*?-->',
        [System.Text.RegularExpressions.RegexOptions]::IgnoreCase -bor [System.Text.RegularExpressions.RegexOptions]::Singleline
    )
    if ($Comments.Count -eq 0) {
        $Failures.Add("No WordPress block comments found in ${RelativePath}")
        return
    }

    $Stack = [System.Collections.Generic.List[string]]::new()
    foreach ($Comment in $Comments) {
        $IsClosing = $Comment.Groups[1].Value -eq '/'
        $BlockName = $Comment.Groups[2].Value.ToLowerInvariant()
        $IsSelfClosing = $Comment.Value -match '/\s*-->$'

        if ($IsSelfClosing) {
            continue
        }

        if (-not $IsClosing) {
            $Stack.Add($BlockName)
            continue
        }

        if ($Stack.Count -eq 0) {
            $Failures.Add("Closing wp:$BlockName has no opening comment in ${RelativePath}")
            continue
        }

        $Expected = $Stack[$Stack.Count - 1]
        if ($Expected -ne $BlockName) {
            $Failures.Add("Expected closing wp:$Expected but found wp:$BlockName in ${RelativePath}")
            continue
        }

        $Stack.RemoveAt($Stack.Count - 1)
    }

    if ($Stack.Count -gt 0) {
        $Failures.Add("Unclosed WordPress block comments in ${RelativePath}: $($Stack -join ', ')")
    }
}

function Require-ValidBlockJson {
    param([string]$RelativePath)

    $Content = Get-RepoContent $RelativePath
    if ($null -eq $Content) {
        return
    }

    $OpeningComments = [regex]::Matches(
        $Content,
        '<!--\s*wp:[a-z0-9-]+(?:\s+(\{.*?\}))?\s*(?:/)?-->',
        [System.Text.RegularExpressions.RegexOptions]::IgnoreCase -bor [System.Text.RegularExpressions.RegexOptions]::Singleline
    )
    foreach ($OpeningComment in $OpeningComments) {
        $Json = $OpeningComment.Groups[1].Value
        if ([string]::IsNullOrWhiteSpace($Json)) {
            continue
        }

        try {
            $null = $Json | ConvertFrom-Json
        }
        catch {
            $Failures.Add("Invalid WordPress block JSON in ${RelativePath}: $Json")
        }
    }
}

$Patterns = @(
    @{ File = 'hero-editorial.php'; Title = 'Editorial hero'; Slug = 'vietnamguide/hero-editorial'; Class = 'vg-pattern-hero' },
    @{ File = 'route-selector.php'; Title = 'Route selector'; Slug = 'vietnamguide/route-selector'; Class = 'vg-pattern-route-selector' },
    @{ File = 'quick-verdict.php'; Title = 'Quick verdict'; Slug = 'vietnamguide/quick-verdict'; Class = 'vg-pattern-quick-verdict' },
    @{ File = 'at-a-glance.php'; Title = 'At a glance'; Slug = 'vietnamguide/at-a-glance'; Class = 'vg-pattern-at-a-glance' },
    @{ File = 'decision-table.php'; Title = 'Decision table'; Slug = 'vietnamguide/decision-table'; Class = 'vg-pattern-decision-table' },
    @{ File = 'itinerary-timeline.php'; Title = 'Itinerary timeline'; Slug = 'vietnamguide/itinerary-timeline'; Class = 'vg-pattern-itinerary-timeline' },
    @{ File = 'source-block.php'; Title = 'Source block'; Slug = 'vietnamguide/source-block'; Class = 'vg-pattern-source-block' },
    @{ File = 'recommendation-row.php'; Title = 'Recommendation row'; Slug = 'vietnamguide/recommendation-row'; Class = 'vg-pattern-recommendation-row' },
    @{ File = 'newsletter-capture.php'; Title = 'Newsletter capture'; Slug = 'vietnamguide/newsletter-capture'; Class = 'vg-pattern-newsletter-capture' }
)

$PreservedPatterns = @{
    'planning-paths.php' = '6e859560a3e76a3f94cfbc2555cfd820e464fd728ffe579e67bc796484bfcef1'
    'editorial-itineraries.php' = '81f36d0c5cf3b41c3ac1f77bd2f30f5227d46ac4f6c70bb2e73abd550b148b80'
    'decision-guides.php' = 'df8353a9c33e0668ab45f24104eec15de38160a229123c5288235e59e167ad83'
    'practical-essentials.php' = 'a0a2b567f6f2aa99ff769a38d5bf2375688e62b6d6f5fc7574aa4082fa9721a4'
}

$ExpectedFiles = @($Patterns.File) + @($PreservedPatterns.Keys)
$AllowedFuturePatternFiles = @('homepage-sections.php')
$PatternDirectory = Join-Path $RepoRoot $PatternRoot
if (Test-Path -LiteralPath $PatternDirectory -PathType Container) {
    $ActualFiles = @(Get-ChildItem -LiteralPath $PatternDirectory -Filter '*.php' -File | Select-Object -ExpandProperty Name)
    $MissingFiles = @($ExpectedFiles | Where-Object { $_ -notin $ActualFiles })
    if ($MissingFiles.Count -gt 0) {
        $Failures.Add("Missing required PHP pattern files: $($MissingFiles -join ', ')")
    }

    $UnexpectedFiles = @($ActualFiles | Where-Object { $_ -notin $ExpectedFiles -and $_ -notin $AllowedFuturePatternFiles })
    if ($UnexpectedFiles.Count -gt 0) {
        $Failures.Add("Unexpected PHP pattern files: $($UnexpectedFiles -join ', ')")
    }

}

foreach ($Pattern in $Patterns) {
    $RelativePath = "$PatternRoot/$($Pattern.File)"
    Require-File $RelativePath
    Require-Matches $RelativePath ("\A<\?php\r?\n/\*\*\r?\n \* Title: {0}\r?\n \* Slug: {1}\r?\n \* Categories: vietnamguide\r?\n \* Inserter: true\r?\n(?: \* Note: [^\r\n]+\r?\n)? \*/\r?\n\?>" -f [regex]::Escape($Pattern.Title), [regex]::Escape($Pattern.Slug))
    Require-Contains $RelativePath $Pattern.Class
    Require-BalancedBlockComments $RelativePath
    Require-ValidBlockJson $RelativePath
    Require-NotMatches $RelativePath 'href\s*=\s*["'']\s*(?:#|javascript:|)["'']' 'placeholder link'
    Require-NotMatches $RelativePath '\bdata-[a-z0-9_-]+\s*=' 'arbitrary data attribute'
    Require-NotMatches $RelativePath '(?:affiliate|sponsored-link)' 'fake affiliate marker'
    Require-NotMatches $RelativePath 'ha-long-bay-vietnam-hero\.jpg|source-stitch-' 'non-pattern hero asset'
    Require-NotMatches $RelativePath '<script\b' 'script element'
}

foreach ($PreservedPattern in $PreservedPatterns.GetEnumerator()) {
    Require-Sha256 "$PatternRoot/$($PreservedPattern.Key)" $PreservedPattern.Value
}

$HeroPath = "$PatternRoot/hero-editorial.php"
Require-Contains $HeroPath '<!-- wp:html -->'
Require-Contains $HeroPath '<picture class="alignfull vg-pattern-hero__media">'
Require-Contains $HeroPath 'assets/images/home-hero.webp'
Require-Contains $HeroPath 'assets/images/home-hero.jpg'
Require-Contains $HeroPath 'type="image/webp"'
Require-Matches $HeroPath 'width="1376"\s+height="768"\s+alt="[^"\r\n]+"'
Require-Contains $HeroPath 'href="/plan/"'
Require-Contains $HeroPath 'href="/itineraries/"'
Require-Contains $HeroPath '<!-- wp:heading {"level":2} -->'
Require-Contains $HeroPath 'The page template owns the h1; this reusable hero intentionally uses h2.'
Require-Contains $HeroPath '<!-- wp:group {"align":"wide","className":"vg-pattern-hero__content","layout":{"type":"constrained"}} -->'
Require-Contains $HeroPath '<div class="wp-block-group alignwide vg-pattern-hero__content">'
Require-NotMatches $HeroPath '<h1\b' 'hero h1 heading'

$RoutePath = "$PatternRoot/route-selector.php"
foreach ($Route in @(
    '/itineraries/7-days-in-vietnam/',
    '/itineraries/10-days-in-vietnam/',
    '/itineraries/14-days-in-vietnam/',
    '/itineraries/21-days-in-vietnam/'
)) {
    Require-Contains $RoutePath "href=`"$Route`""
}
Require-NotMatches $RoutePath '<!--\s*wp:html\b' 'raw HTML block'
Require-Contains $RoutePath '<ul class="wp-block-list vg-pattern-route-selector__list">'

$AtAGlancePath = "$PatternRoot/at-a-glance.php"
Require-Contains $AtAGlancePath '<ul class="wp-block-list vg-pattern-at-a-glance__list">'

$SourcePath = "$PatternRoot/source-block.php"
Require-Contains $SourcePath '<ul class="wp-block-list vg-pattern-source-block__list">'

foreach ($CoreOnlyFile in @('quick-verdict.php', 'at-a-glance.php', 'source-block.php', 'recommendation-row.php', 'newsletter-capture.php')) {
    Require-NotMatches "$PatternRoot/$CoreOnlyFile" '<!--\s*wp:html\b' 'raw HTML block'
}

foreach ($RawHtmlFile in @('hero-editorial.php', 'decision-table.php', 'itinerary-timeline.php')) {
    Require-MatchCount "$PatternRoot/$RawHtmlFile" '<!--\s*wp:html\s*-->' 1
}

$TablePath = "$PatternRoot/decision-table.php"
Require-Contains $TablePath '<!-- wp:html -->'
Require-Contains $TablePath '<table>'
Require-Contains $TablePath '<caption>'
Require-Contains $TablePath '<thead>'
Require-Matches $TablePath '<th\s+scope="col">'

$TimelinePath = "$PatternRoot/itinerary-timeline.php"
Require-Contains $TimelinePath '<!-- wp:html -->'
Require-Contains $TimelinePath '<ol class="vg-pattern-itinerary-timeline__list" role="list">'
Require-Contains $TimelinePath 'role="list"'
Require-Matches $TimelinePath '<time\s+datetime="P[0-9]+D">'

Require-Contains $SourcePath 'https://vietnam.travel/plan-your-trip/visa-requirements'
Require-Contains $SourcePath 'https://evisa.gov.vn/'
Require-Contains $SourcePath 'Last reviewed: July 2026'

$RecommendationPath = "$PatternRoot/recommendation-row.php"
Require-Contains $RecommendationPath 'href="/plan/"'

$NewsletterPath = "$PatternRoot/newsletter-capture.php"
Require-Contains $NewsletterPath 'href="/newsletter/"'

$CssPath = "$ThemeRoot/assets/css/guide-patterns.css"
Require-File $CssPath
foreach ($Pattern in $Patterns) {
    Require-Contains $CssPath ".$($Pattern.Class)"
}
Require-Contains $CssPath '@media (max-width:'
Require-Contains $CssPath '@media (prefers-reduced-motion: reduce)'
Require-Matches $CssPath 'var\(--vg-(?:paper|limestone|jade|blue|gold|ink|line|display|body|wide|reading)\)'
Require-NotMatches $CssPath ':root\s*\{' 'duplicated root tokens'
Require-NotMatches $CssPath '(^|\r?\n)\s*(?:body|html|\*)\s*\{' 'global reset selector'
Require-NotMatches $CssPath '#(?:[a-f0-9]{3}|[a-f0-9]{6})\b' 'hard-coded color'
Require-NotMatches $CssPath 'purple|violet|magenta' 'purple color language'
Require-NotMatches $CssPath '\.vg-pattern-decision-table[^\{]*thead[^\{]*\{[^\}]*display\s*:\s*none' 'hidden responsive table headers'

$FunctionsPath = "$ThemeRoot/functions.php"
Require-Contains $FunctionsPath "add_editor_style(['assets/css/homepage.css', 'assets/css/guide-patterns.css']);"
Require-Contains $FunctionsPath "wp_enqueue_style('vietnamguide-guide-patterns', get_theme_file_uri('/assets/css/guide-patterns.css'), ['vietnamguide-homepage'], `$version);"
Require-Sha256 "$ThemeRoot/assets/js/homepage.js" '097b19d6e0802e2352e68362788b58b28537667a6d2cdfc6a6303b59170d1eb9'

$ThemePhpFiles = Get-ChildItem -LiteralPath (Join-Path $RepoRoot $ThemeRoot) -Filter '*.php' -File -Recurse
foreach ($ThemePhpFile in $ThemePhpFiles) {
    $ThemePhpContent = Get-Content -LiteralPath $ThemePhpFile.FullName -Raw
    if ($ThemePhpContent -match 'register_block_pattern_category|WP_Block_Pattern_Categories_Registry') {
        $Failures.Add("Theme must not register the pattern category: $($ThemePhpFile.FullName.Substring($RepoRoot.Length + 1))")
    }
}

if ($Failures.Count -gt 0) {
    foreach ($Failure in $Failures) {
        Write-Output "FAIL: $Failure"
    }

    exit 1
}

Write-Output 'VietnamGuide core block pattern checks passed.'

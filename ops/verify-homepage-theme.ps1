$ErrorActionPreference = 'Stop'

$RepoRoot = Split-Path -Parent $PSScriptRoot
$Failures = [System.Collections.Generic.List[string]]::new()

function Require-File {
    param(
        [string]$RelativePath
    )

    $FullPath = Join-Path $RepoRoot $RelativePath
    if (-not (Test-Path -LiteralPath $FullPath -PathType Leaf)) {
        $Failures.Add("Missing file: $RelativePath")
    }
}

function Require-Contains {
    param(
        [string]$RelativePath,
        [string]$Needle
    )

    $FullPath = Join-Path $RepoRoot $RelativePath
    if (-not (Test-Path -LiteralPath $FullPath -PathType Leaf)) {
        return
    }

    $Content = Get-Content -LiteralPath $FullPath -Raw
    if ($null -eq $Content) {
        $Content = ''
    }

    if (-not $Content.Contains($Needle)) {
        $Failures.Add("Missing substring in ${RelativePath}: $Needle")
    }
}

function Require-Matches {
    param(
        [string]$RelativePath,
        [string]$Pattern
    )

    $FullPath = Join-Path $RepoRoot $RelativePath
    if (-not (Test-Path -LiteralPath $FullPath -PathType Leaf)) {
        return
    }

    $Content = Get-Content -LiteralPath $FullPath -Raw
    if ($null -eq $Content) {
        $Content = ''
    }

    if (-not [regex]::IsMatch($Content, $Pattern)) {
        $Failures.Add("Missing pattern in ${RelativePath}: $Pattern")
    }
}

function Require-Occurrences {
    param(
        [string]$RelativePath,
        [string]$Needle,
        [int]$ExpectedCount
    )

    $FullPath = Join-Path $RepoRoot $RelativePath
    if (-not (Test-Path -LiteralPath $FullPath -PathType Leaf)) {
        return
    }

    $Content = Get-Content -LiteralPath $FullPath -Raw
    if ($null -eq $Content) {
        $Content = ''
    }

    $ActualCount = ([regex]::Matches($Content, [regex]::Escape($Needle))).Count
    if ($ActualCount -ne $ExpectedCount) {
        $Failures.Add("Expected $ExpectedCount occurrences in ${RelativePath}, found ${ActualCount}: $Needle")
    }
}

function Require-MatchCount {
    param(
        [string]$RelativePath,
        [string]$Pattern,
        [int]$ExpectedCount
    )

    $FullPath = Join-Path $RepoRoot $RelativePath
    if (-not (Test-Path -LiteralPath $FullPath -PathType Leaf)) {
        return
    }

    $Content = Get-Content -LiteralPath $FullPath -Raw
    if ($null -eq $Content) {
        $Content = ''
    }

    $ActualCount = ([regex]::Matches($Content, $Pattern, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)).Count
    if ($ActualCount -ne $ExpectedCount) {
        $Failures.Add("Expected $ExpectedCount matches in ${RelativePath}, found ${ActualCount}: $Pattern")
    }
}

function Require-NotContains {
    param(
        [string]$RelativePath,
        [string]$Needle
    )

    $FullPath = Join-Path $RepoRoot $RelativePath
    if (-not (Test-Path -LiteralPath $FullPath -PathType Leaf)) {
        return
    }

    $Content = Get-Content -LiteralPath $FullPath -Raw
    if ($null -eq $Content) {
        $Content = ''
    }

    if ($Content.Contains($Needle)) {
        $Failures.Add("Unexpected substring in ${RelativePath}: $Needle")
    }
}

$RequiredFiles = @(
    'wordpress/wp-content/themes/vietnamguide-premium/style.css'
    'wordpress/wp-content/themes/vietnamguide-premium/theme.json'
    'wordpress/wp-content/themes/vietnamguide-premium/functions.php'
    'wordpress/wp-content/themes/vietnamguide-premium/inc/homepage-data.php'
    'wordpress/wp-content/themes/vietnamguide-premium/header.php'
    'wordpress/wp-content/themes/vietnamguide-premium/footer.php'
    'wordpress/wp-content/themes/vietnamguide-premium/front-page.php'
    'wordpress/wp-content/themes/vietnamguide-premium/index.php'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/README.md'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero.jpg'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero.webp'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero-960.jpg'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero-960.webp'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero-640.jpg'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero-640.webp'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-editorial.jpg'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-editorial.webp'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-editorial-720.jpg'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-editorial-720.webp'
    'ops/build-homepage-images.py'
    'ops/test-homepage-images.py'
    'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php'
    'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php'
    'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php'
    'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php'
    'qa/homepage-preview.html'
)

foreach ($RequiredFile in $RequiredFiles) {
    Require-File $RequiredFile
}

Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/style.css' 'Theme Name: VietnamGuide Premium'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/functions.php' "require_once get_theme_file_path('/inc/homepage-data.php');"
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/functions.php' 'register_nav_menus'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/inc/homepage-data.php' 'function vg_homepage_data(): array'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/header.php' 'data-vg-menu-toggle'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/header.php' 'aria-expanded="false"'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'Vietnam for travelers who choose well.'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'data-vg-event="route_selector_click"'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'get_header();'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' '<main id="main"'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'have_posts()'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'post_class()'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'the_ID()'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'is_singular()'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' '<h1'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' '<h2'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'esc_url(get_permalink())'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'esc_html(get_the_title())'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'esc_html(get_search_query(false))'
Require-NotContains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'esc_html(get_search_query())'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'wp_kses_post(get_the_archive_title())'
Require-NotContains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'esc_html(get_the_archive_title())'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'the_content();'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'the_excerpt();'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'the_posts_pagination('
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/index.php' 'get_footer();'
Require-Matches 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'home-hero-640\.webp[^\r\n]*640w'
Require-Matches 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'home-hero-960\.webp[^\r\n]*960w'
Require-Matches 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'home-hero-640\.jpg[^\r\n]*640w'
Require-Matches 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'home-hero-960\.jpg[^\r\n]*960w'
Require-Matches 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'home-editorial-720\.webp[^\r\n]*720w'
Require-Matches 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'home-editorial-720\.jpg[^\r\n]*720w'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'sizes="100vw"'
Require-Occurrences 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'sizes="(max-width: 760px) calc(100vw - 64px), (max-width: 960px) calc(100vw - 96px), min(48vw, 656px)"' 2
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'fetchpriority="high"'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'loading="lazy"'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'Misty limestone karsts in Ha Long Bay at sunrise'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'Lanterns reflected on the river in Hoi An at night'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/images/README.md' '10120543989568032144'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/images/README.md' '85c390be32164a6e809f8affd0d442f7'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/images/README.md' '2026-07-27'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/images/README.md' 'Stitch-provided assets, not image generation.'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/images/README.md' 'No embedded text, logo, or third-party trademark.'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css' '@media (prefers-reduced-motion: reduce)'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css' ':focus-visible'
Require-Matches 'wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css' '(?s)\.vg-js \.vg-primary-navigation \{[^}]*justify-self: stretch;[^}]*width: 100%;'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js' "matchMedia('(prefers-reduced-motion: reduce)')"
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js' "event.key === 'Escape'"

# The static fixture mirrors the current PHP templates closely enough for meaningful browser QA.
Require-Matches 'qa/homepage-preview.html' '\A<!doctype html>'
Require-Contains 'qa/homepage-preview.html' '<html lang="en">'
Require-Contains 'qa/homepage-preview.html' '<meta charset="utf-8">'
Require-Contains 'qa/homepage-preview.html' '<meta name="viewport" content="width=device-width, initial-scale=1">'
Require-Contains 'qa/homepage-preview.html' '<title>VietnamGuide.net Homepage Preview'
Require-Contains 'qa/homepage-preview.html' '<link rel="icon" href="data:,">'
Require-Contains 'qa/homepage-preview.html' '../wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css'
Require-Contains 'qa/homepage-preview.html' '../wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js'
Require-Matches 'qa/homepage-preview.html' '<script[^>]+homepage\.js[^>]+defer'
Require-Contains 'qa/homepage-preview.html' '<body class="home vg-site">'
Require-Contains 'qa/homepage-preview.html' '<a class="vg-skip-link" href="#main">Skip to content</a>'
Require-Matches 'qa/homepage-preview.html' '<main id="main" tabindex="-1">'
Require-MatchCount 'qa/homepage-preview.html' '<h1(?:\s|>)' 1
Require-Contains 'qa/homepage-preview.html' 'class="vg-site-header" data-vg-header'
Require-Contains 'qa/homepage-preview.html' 'aria-controls="vg-primary-navigation"'
Require-Contains 'qa/homepage-preview.html' 'aria-expanded="false"'
Require-Contains 'qa/homepage-preview.html' 'data-vg-menu-toggle'
Require-Contains 'qa/homepage-preview.html' 'id="vg-primary-navigation"'
Require-Contains 'qa/homepage-preview.html' 'data-vg-navigation'
Require-Contains 'qa/homepage-preview.html' 'class="vg-hero"'
Require-Contains 'qa/homepage-preview.html' 'class="vg-section vg-planning-paths"'
Require-Contains 'qa/homepage-preview.html' 'class="vg-section vg-itineraries"'
Require-Contains 'qa/homepage-preview.html' 'class="vg-section vg-destinations"'
Require-Contains 'qa/homepage-preview.html' 'class="vg-section vg-comparisons"'
Require-Contains 'qa/homepage-preview.html' 'class="vg-section vg-essentials"'
Require-Contains 'qa/homepage-preview.html' 'class="vg-section vg-newsletter"'
Require-Contains 'qa/homepage-preview.html' 'class="vg-site-footer"'
Require-Contains 'qa/homepage-preview.html' 'home-hero-640.webp 640w'
Require-Contains 'qa/homepage-preview.html' 'home-hero-960.webp 960w'
Require-Contains 'qa/homepage-preview.html' 'home-hero.webp 1376w'
Require-Contains 'qa/homepage-preview.html' 'home-hero-640.jpg 640w'
Require-Contains 'qa/homepage-preview.html' 'home-hero-960.jpg 960w'
Require-Contains 'qa/homepage-preview.html' 'home-hero.jpg 1376w'
Require-Contains 'qa/homepage-preview.html' 'width="1376"'
Require-Contains 'qa/homepage-preview.html' 'height="768"'
Require-Contains 'qa/homepage-preview.html' 'alt="Misty limestone karsts in Ha Long Bay at sunrise"'
Require-Contains 'qa/homepage-preview.html' 'fetchpriority="high"'
Require-Contains 'qa/homepage-preview.html' 'home-editorial-720.webp 720w'
Require-Contains 'qa/homepage-preview.html' 'home-editorial.webp 1408w'
Require-Contains 'qa/homepage-preview.html' 'home-editorial-720.jpg 720w'
Require-Contains 'qa/homepage-preview.html' 'home-editorial.jpg 1408w'
Require-Contains 'qa/homepage-preview.html' 'width="1408"'
Require-Contains 'qa/homepage-preview.html' 'alt="Lanterns reflected on the river in Hoi An at night"'
Require-Contains 'qa/homepage-preview.html' 'loading="lazy"'
Require-NotContains 'qa/homepage-preview.html' 'Rice terraces, river, and limestone mountains in northern Vietnam at dawn'
Require-NotContains 'qa/homepage-preview.html' 'A wooden boat moving between limestone karsts in Lan Ha Bay'

# Reusable homepage patterns must expose their exact registration headers and semantic anchors.
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' ' * Title: Planning paths'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' ' * Slug: vietnamguide/planning-paths'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' ' * Categories: vietnamguide'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' ' * Inserter: true'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' 'vg-section vg-planning-paths'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' 'Choose your trip length'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '/itineraries/14-days-in-vietnam/'

Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' ' * Title: Editorial itineraries'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' ' * Slug: vietnamguide/editorial-itineraries'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' ' * Categories: vietnamguide'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' ' * Inserter: true'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' 'vg-section vg-itineraries'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' 'Routes built around pace, not a checklist.'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '/itineraries/northern-vietnam-itinerary/'

Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' ' * Title: Decision guides'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' ' * Slug: vietnamguide/decision-guides'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' ' * Categories: vietnamguide'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' ' * Inserter: true'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' 'vg-section vg-comparisons'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' 'Make the difficult choices quickly.'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' '/compare/ha-long-bay-vs-lan-ha-bay/'

Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' ' * Title: Practical essentials'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' ' * Slug: vietnamguide/practical-essentials'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' ' * Categories: vietnamguide'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' ' * Inserter: true'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' 'vg-section vg-essentials'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' 'Handle the details before they become problems.'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '/plan/vietnam-evisa/'

Require-Matches 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '\A<\?php\r?\n/\*\*\r?\n \* Title: Planning paths\r?\n \* Slug: vietnamguide/planning-paths\r?\n \* Categories: vietnamguide\r?\n \* Inserter: true\r?\n \*/\r?\n\?>'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '<!-- wp:group {"className":"vg-section vg-planning-paths","layout":{"type":"constrained"}} -->'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '<!-- /wp:group -->'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '<!-- wp:list {"className":"vg-link-list"} -->'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '<!-- /wp:list -->'
Require-Occurrences 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '<a href=' 9
Require-Occurrences 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '<span aria-hidden="true">&rarr;</span>' 9
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '/itineraries/7-days-in-vietnam/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '/itineraries/10-days-in-vietnam/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '/itineraries/14-days-in-vietnam/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '/itineraries/21-days-in-vietnam/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '/plan/vietnam-for-first-time-visitors/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '/itineraries/vietnam-food-itinerary/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '/itineraries/vietnam-beach-itinerary/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '/itineraries/vietnam-family-itinerary/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php' '/itineraries/vietnam-luxury-itinerary/'

Require-Matches 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '\A<\?php\r?\n/\*\*\r?\n \* Title: Editorial itineraries\r?\n \* Slug: vietnamguide/editorial-itineraries\r?\n \* Categories: vietnamguide\r?\n \* Inserter: true\r?\n \*/\r?\n\?>'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '<!-- wp:group {"className":"vg-section vg-itineraries","layout":{"type":"constrained"}} -->'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '<!-- /wp:group -->'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '<!-- wp:list {"ordered":true,"className":"vg-editorial-rows"} -->'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '<!-- /wp:list -->'
Require-Occurrences 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '<a href=' 4
Require-Occurrences 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' 'class="vg-editorial-rows__number" aria-hidden="true">' 4
Require-Occurrences 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '<span aria-hidden="true">&rarr;</span>' 4
Require-Occurrences 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '<small>' 4
Require-Occurrences 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '<strong>' 4
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '<span class="vg-editorial-rows__number" aria-hidden="true">01</span>'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '<span class="vg-editorial-rows__number" aria-hidden="true">02</span>'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '<span class="vg-editorial-rows__number" aria-hidden="true">03</span>'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '<span class="vg-editorial-rows__number" aria-hidden="true">04</span>'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '/itineraries/10-days-in-vietnam/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '/itineraries/14-days-in-vietnam/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '/itineraries/northern-vietnam-itinerary/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' '/itineraries/vietnam-luxury-itinerary/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' 'First journey'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' 'More breathing room'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' 'Northern route'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php' 'Premium stay'

Require-Matches 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' '\A<\?php\r?\n/\*\*\r?\n \* Title: Decision guides\r?\n \* Slug: vietnamguide/decision-guides\r?\n \* Categories: vietnamguide\r?\n \* Inserter: true\r?\n \*/\r?\n\?>'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' '<!-- wp:group {"className":"vg-section vg-comparisons","layout":{"type":"constrained"}} -->'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' '<!-- /wp:group -->'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' '<!-- wp:list {"className":"vg-comparison-list"} -->'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' '<!-- /wp:list -->'
Require-Occurrences 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' '<a href=' 3
Require-Occurrences 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' '<span aria-hidden="true">&rarr;</span>' 3
Require-Occurrences 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' '<strong>' 3
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' '/compare/ha-long-bay-vs-lan-ha-bay/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' '/compare/sapa-vs-ha-giang/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php' '/compare/hanoi-vs-ho-chi-minh-city/'

Require-Matches 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '\A<\?php\r?\n/\*\*\r?\n \* Title: Practical essentials\r?\n \* Slug: vietnamguide/practical-essentials\r?\n \* Categories: vietnamguide\r?\n \* Inserter: true\r?\n \*/\r?\n\?>'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '<!-- wp:group {"className":"vg-section vg-essentials","layout":{"type":"constrained"}} -->'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '<!-- /wp:group -->'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '<!-- wp:list {"className":"vg-essential-grid"} -->'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '<!-- /wp:list -->'
Require-Occurrences 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '<a href=' 6
Require-Occurrences 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '<span aria-hidden="true">&rarr;</span>' 6
Require-Occurrences 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '<strong>' 6
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '/plan/vietnam-evisa/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '/plan/best-time-to-visit-vietnam/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '/costs/vietnam-travel-cost/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '/plan/sim-esim-vietnam/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '/plan/getting-around-vietnam/'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php' '/plan/is-vietnam-safe/'

if ($Failures.Count -gt 0) {
    foreach ($Failure in $Failures) {
        Write-Output "FAIL: $Failure"
    }

    exit 1
}

Write-Output 'VietnamGuide homepage theme checks passed.'

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

$RequiredFiles = @(
    'wordpress/wp-content/themes/vietnamguide-premium/style.css'
    'wordpress/wp-content/themes/vietnamguide-premium/theme.json'
    'wordpress/wp-content/themes/vietnamguide-premium/functions.php'
    'wordpress/wp-content/themes/vietnamguide-premium/inc/homepage-data.php'
    'wordpress/wp-content/themes/vietnamguide-premium/header.php'
    'wordpress/wp-content/themes/vietnamguide-premium/footer.php'
    'wordpress/wp-content/themes/vietnamguide-premium/front-page.php'
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
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js' "matchMedia('(prefers-reduced-motion: reduce)')"
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js' "event.key === 'Escape'"
Require-Contains 'qa/homepage-preview.html' 'VietnamGuide.net'

if ($Failures.Count -gt 0) {
    foreach ($Failure in $Failures) {
        Write-Output "FAIL: $Failure"
    }

    exit 1
}

Write-Output 'VietnamGuide homepage theme checks passed.'

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
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero.webp'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-editorial.webp'
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

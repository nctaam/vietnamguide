$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$homepagePath = Join-Path $repoRoot 'ops/apply-homepage-premium.php'
$guidePath = Join-Path $repoRoot 'ops/apply-ho-chi-minh-city-travel-guide.php'
$verifierPath = Join-Path $repoRoot 'ops/verify-eeat-content.php'

$homepage = Get-Content -Raw -Path $homepagePath
$guide = if (Test-Path -LiteralPath $guidePath) { Get-Content -Raw -Path $guidePath } else { '' }
$verifier = Get-Content -Raw -Path $verifierPath

function Require-Contains([string] $Label, [string] $Haystack, [string] $Needle) {
    if (-not $Haystack.Contains($Needle)) {
        throw "$Label missing: $Needle"
    }
}

Require-Contains 'guide file exists' $guide 'ops/apply-ho-chi-minh-city-travel-guide.php'
Require-Contains 'guide title' $guide "'post_title'     => 'Ho Chi Minh City Travel Guide'"
Require-Contains 'hero marker' $guide 'vg-hcmc-hero:v1'
Require-Contains 'verdict marker' $guide 'vg-hcmc-concierge-verdict'
Require-Contains 'at-a-glance marker' $guide 'vg-hcmc-at-a-glance:v1'
Require-Contains 'photo grid marker' $guide 'vg-hcmc-photo-grid:v1'
Require-Contains 'source diversity marker' $guide 'vg-hcmc-source-diversity:v1'
Require-Contains 'districts marker' $guide 'vg-hcmc-districts:v1'
Require-Contains 'day trips marker' $guide 'vg-hcmc-day-trips:v1'
Require-Contains 'transport marker' $guide 'vg-hcmc-transport-logistics:v1'
Require-Contains 'FAQ marker' $guide 'vg-hcmc-faq:v1'
Require-Contains 'homepage HCMC variable' $homepage '$ho_chi_minh_city_guide_href'
Require-Contains 'homepage HCMC fallback path' $homepage "vg_home_path('destinations/ho-chi-minh-city-travel-guide', 'destinations')"
Require-Contains 'homepage HCMC row' $homepage 'Ho Chi Minh City Travel Guide'
Require-Contains 'homepage HCMC verdict' $homepage 'Use Ho Chi Minh City when the south needs a real city chapter, not just an airport stamp.'
Require-Contains 'EEAT verifier HCMC label' $verifier 'Ho Chi Minh City Travel Guide'
Require-Contains 'EEAT verifier HCMC marker' $verifier 'vg-hcmc-hero:v1'

Write-Output 'Ho Chi Minh City static checks passed.'

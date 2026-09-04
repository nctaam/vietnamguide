$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$homepage = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-homepage-premium.php')
$verifier = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/verify-eeat-content.php')
$foundation = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-foundation-seo-content.php')
$guidePath = Join-Path $repoRoot 'ops/apply-ninh-binh-day-trip-overnight.php'
$guide = if (Test-Path -LiteralPath $guidePath) { Get-Content -Raw -Path $guidePath } else { '' }
$routeLine = 'Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ |'

$sourcePersistenceFiles = @(
    'ops/apply-ninh-binh-travel-guide.php',
    'ops/apply-best-day-trips-hanoi-guide.php',
    'ops/apply-hanoi-in-2-days-itinerary.php',
    'ops/apply-hanoi-travel-guide.php',
    'ops/apply-best-things-hanoi-guide.php',
    'ops/apply-where-to-stay-in-hanoi.php',
    'ops/apply-ha-long-bay-travel-guide.php',
    'ops/apply-ha-long-lan-ha-comparison-guide.php',
    'ops/apply-cat-ba-travel-guide.php',
    'ops/apply-bai-tu-long-bay-guide.php',
    'ops/apply-vietnam-travel-guide.php',
    'ops/apply-best-places-destination-guide.php',
    'ops/apply-unesco-heritage-sites-guide.php',
    'ops/apply-north-central-south-comparison-guide.php',
    'ops/apply-best-time-guide.php',
    'ops/apply-transport-within-vietnam-guide.php',
    'ops/apply-vietnam-travel-cost-guide.php',
    'ops/apply-health-travel-insurance-guide.php',
    'ops/apply-safety-scams-vietnam-guide.php',
    'ops/apply-7-days-itinerary-guide.php',
    'ops/apply-10-days-itinerary-guide.php',
    'ops/apply-14-days-itinerary-guide.php',
    'ops/apply-21-days-itinerary-guide.php'
)

function Require-Contains([string] $Label, [string] $Haystack, [string] $Needle) {
    if (-not $Haystack.Contains($Needle)) {
        throw "$Label missing: $Needle"
    }
}

function Require-NotContains([string] $Label, [string] $Haystack, [string] $Needle) {
    if ($Haystack.Contains($Needle)) {
        throw "$Label should not contain: $Needle"
    }
}

function Get-VisibleExternalHrefs([string] $Content) {
    $matches = [regex]::Matches($Content, '\shref=(["''])(.*?)\1', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    $hrefs = New-Object System.Collections.Generic.List[string]

    foreach ($match in $matches) {
        $href = $match.Groups[2].Value.Trim()

        if ($href -eq '' -or $href.StartsWith('/') -or $href.StartsWith('#') -or $href -match '^(mailto|tel):') {
            continue
        }

        $hrefs.Add($href)
    }

    return $hrefs
}

Require-Contains 'guide file exists' $guide 'ops/apply-ninh-binh-day-trip-overnight.php'
Require-Contains 'guide force flag' $guide 'VG_FORCE_NINH_BINH_DAY_TRIP_OVERNIGHT_REPUBLISH'
Require-Contains 'guide repair flag' $guide 'VG_REPAIR_NINH_BINH_DAY_TRIP_OVERNIGHT_LINKS'
Require-Contains 'guide title' $guide "'post_title'     => 'Ninh Binh Day Trip vs Overnight'"
Require-Contains 'guide slug' $guide "'post_name'      => 'ninh-binh-day-trip-vs-overnight'"
Require-Contains 'parent compare lookup' $guide "get_page_by_path('compare'"
Require-Contains 'guide published path guard' $guide "vg_ninh_binh_day_trip_ops_published_page_exists('compare/ninh-binh-day-trip-vs-overnight')"
Require-Contains 'compare hub marker' $guide 'vg-ninh-binh-day-trip-overnight-compare-hub-note:v1'
Require-Contains 'destinations hub marker' $guide 'vg-ninh-binh-day-trip-overnight-destinations-hub-note:v1'
Require-Contains 'homepage marker' $guide 'vg-ninh-binh-day-trip-overnight-homepage-route-spine:v1'

Require-Contains 'hero marker' $guide 'vg-ninh-binh-day-trip-overnight-hero:v1'
Require-Contains 'concierge verdict' $guide 'vg-ninh-binh-day-trip-overnight-concierge-verdict'
Require-Contains 'shared concierge class' $guide 'vg-concierge-verdict'
Require-Contains 'editorial proof panel' $guide 'vietnamguide/editorial-proof-panel'
Require-Contains 'at a glance' $guide 'vg-ninh-binh-day-trip-overnight-at-a-glance:v1'
Require-Contains 'photo grid' $guide 'vg-ninh-binh-day-trip-overnight-photo-grid:v1'
Require-Contains 'source diversity' $guide 'vg-ninh-binh-day-trip-overnight-source-diversity:v1'
Require-Contains 'source trail snapshot' $guide 'vg-ninh-binh-day-trip-overnight-source-trail-snapshot:v1'
Require-Contains 'decision matrix' $guide 'vg-ninh-binh-day-trip-overnight-decision-matrix:v1'
Require-Contains 'route fit' $guide 'vg-ninh-binh-day-trip-overnight-route-fit:v1'
Require-Contains 'sample schedules' $guide 'vg-ninh-binh-day-trip-overnight-sample-schedules:v1'
Require-Contains 'transfer pressure' $guide 'vg-ninh-binh-day-trip-overnight-transfer-pressure:v1'
Require-Contains 'Trang An Tam Coc decision' $guide 'vg-ninh-binh-day-trip-overnight-trang-an-tam-coc:v1'
Require-Contains 'hotel base logic' $guide 'vg-ninh-binh-day-trip-overnight-base-logic:v1'
Require-Contains 'weather crowd pivots' $guide 'vg-ninh-binh-day-trip-overnight-weather-crowd-pivots:v1'
Require-Contains 'cost comfort' $guide 'vg-ninh-binh-day-trip-overnight-cost-comfort:v1'
Require-Contains 'mistakes skip' $guide 'vg-ninh-binh-day-trip-overnight-mistakes-skip:v1'
Require-Contains 'live checks' $guide 'vg-ninh-binh-day-trip-overnight-live-checks:v1'
Require-Contains 'FAQ' $guide 'vg-ninh-binh-day-trip-overnight-faq:v1'
Require-Contains 'related routes shortcode' $guide '[vg_related_routes]'
Require-Contains 'source trail pattern' $guide 'vietnamguide/source-trail'
Require-Contains 'update log pattern' $guide 'vietnamguide/update-log'

Require-Contains 'one-night default verdict' $guide 'For most first-time international travelers, Ninh Binh is better as one overnight than as a Hanoi day trip.'
Require-Contains 'day-trip valid exception' $guide 'A day trip is valid when Ninh Binh is the only countryside slot and the next morning is not fragile.'
Require-Contains 'overnight premium judgment' $guide 'The overnight premium is not another attraction; it is morning control, heat avoidance, softer evenings, and fewer transfer compromises.'
Require-Contains 'two-night anti spam judgment' $guide 'Two nights are valuable only when they buy a different rhythm, not when they become a bigger checklist.'
Require-Contains 'Trang An Tam Coc decision depth' $guide 'Choose Trang An when the page needs UNESCO-grade landscape certainty; choose Tam Coc when countryside texture, cycling, and softer base rhythm matter more.'
Require-Contains 'combo warning' $guide 'Do not combine Ninh Binh and a same-day bay product to rescue an overfull route.'
Require-Contains 'source-limit framing' $guide 'They cannot decide your jet lag, hotel lane, group patience, heat tolerance, or whether tomorrow morning is already spoken for.'

Require-Contains 'official Ninh Binh source' $guide 'vietnam.travel/places-to-go/northern-vietnam/ninh-binh'
Require-Contains 'official Northern Vietnam source' $guide 'vietnam.travel/places-to-go/northern-vietnam'
Require-Contains 'UNESCO Trang An source' $guide 'whc.unesco.org/en/list/1438'
Require-Contains 'Ninh Binh tourism source' $guide 'dulichninhbinh.com.vn/en'
Require-Contains 'ticket price source' $guide 'dulichninhbinh.com.vn/en/printer/1801'
Require-Contains 'weather source' $guide 'vietnam.travel/things-to-do/weather-and-climate-vietnam'
Require-Contains 'transport source' $guide 'vietnam.travel/plan-your-trip/transport-within-vietnam'
Require-Contains 'rail source' $guide 'dsvn.vn'

Require-Contains 'Trang An image credit' $guide 'Jakub Halun / CC BY 4.0'
Require-Contains 'Tam Coc image credit' $guide 'Andre Hospers / CC BY 4.0'
Require-Contains 'Mua Cave image credit' $guide 'Jakub Halun / CC BY 4.0'
Require-Contains 'Van Long image credit' $guide 'Andre Hospers / CC BY 4.0'
Require-Contains 'Cuc Phuong image credit' $guide 'hds / CC BY 2.0'
Require-Contains 'Trang An image record' $guide 'Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg'
Require-Contains 'Tam Coc image record' $guide 'Tam_Coc_Ninh_Binh_%2829079%29.jpg'
Require-Contains 'Mua Cave image record' $guide 'Mua_Cave%2C_Ninh_Binh%2C_Vietnam%2C_20240202_0926_4964.jpg'
Require-Contains 'Van Long image record' $guide 'Van_Long_Nature_Reserve_Riet_Grotten_Kalksteen_Ninh_Binh_%2894589%29.jpg'
Require-Contains 'Cuc Phuong image record' $guide 'Forest_in_Cuc_Phuong_National_Park_%2815706323528%29.jpg'

Require-Contains 'related-route href-based route parsing' $guide '$route_href = $route_parts[1];'
Require-Contains 'related-route href-based target detection' $guide '$is_target_line = $line_href === $route_href || str_contains($line, $route_href);'
Require-Contains 'related-route idempotent skip log' $guide 'line is already current'
Require-NotContains 'related-route label-prefix-only dedupe' $guide "str_starts_with(`$line, `$route_label . ' |')"

Require-Contains 'homepage required path' $homepage "`$ninh_binh_day_trip_overnight_href = vg_home_required_path('compare/ninh-binh-day-trip-vs-overnight');"
Require-Contains 'homepage URL' $homepage '/compare/ninh-binh-day-trip-vs-overnight/'
Require-Contains 'homepage planning row' $homepage 'I need to decide whether Ninh Binh is a day trip or an overnight.'
Require-Contains 'homepage route verdict row' $homepage 'Upgrade Ninh Binh to overnight when morning control is worth more than one more attraction.'
Require-Contains 'homepage guide shelf row' $homepage 'Ninh Binh Day Trip vs Overnight'
Require-Contains 'foundation destinations hub persistence marker' $foundation 'vg-ninh-binh-day-trip-overnight-destinations-hub-note:v1'
Require-Contains 'foundation destinations hub persistence href' $foundation '/compare/ninh-binh-day-trip-vs-overnight/'
Require-Contains 'foundation compare hub persistence marker' $foundation 'vg-ninh-binh-day-trip-overnight-compare-hub-note:v1'
Require-Contains 'foundation compare hub persistence decision copy' $foundation 'Decide if Ninh Binh needs a night'

foreach ($sourceFile in $sourcePersistenceFiles) {
    $path = Join-Path $repoRoot $sourceFile
    if (-not (Test-Path -LiteralPath $path)) {
        throw "source persistence file missing: $sourceFile"
    }

    $source = Get-Content -Raw -Path $path
    Require-Contains "$sourceFile inbound source persistence" $source $routeLine
}

Require-Contains 'EEAT verifier page label' $verifier 'Ninh Binh Day Trip vs Overnight'
Require-Contains 'EEAT verifier hero marker' $verifier 'vg-ninh-binh-day-trip-overnight-hero:v1'
Require-Contains 'EEAT verifier compare hub note' $verifier 'vg-ninh-binh-day-trip-overnight-compare-hub-note:v1'
Require-Contains 'EEAT verifier destinations hub note' $verifier 'vg-ninh-binh-day-trip-overnight-destinations-hub-note:v1'
Require-Contains 'EEAT verifier homepage href' $verifier 'href="/compare/ninh-binh-day-trip-vs-overnight/"'
Require-Contains 'EEAT verifier sitemap path call' $verifier "'compare/ninh-binh-day-trip-vs-overnight',"
Require-Contains 'EEAT verifier visible depth phrase' $verifier 'For most first-time international travelers, Ninh Binh is better as one overnight than as a Hanoi day trip.'
Require-Contains 'EEAT verifier rendered source snapshot' $verifier 'rendered Ninh Binh day trip vs overnight source trail snapshot'
Require-Contains 'EEAT verifier rendered source URL' $verifier 'https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh'
Require-Contains 'EEAT verifier external budget target' $verifier '$ninh_binh_day_trip_overnight_page'
Require-Contains 'EEAT verifier own route metadata' $verifier "vg_verify_eeat_require_own_related_route_meta(`n        `$ninh_binh_day_trip_overnight_page"
Require-Contains 'EEAT verifier inbound related-route check' $verifier '/compare/ninh-binh-day-trip-vs-overnight/'
Require-Contains 'EEAT verifier exact label enabled' $verifier "`$failures,`n        true"

$visibleExternalHrefs = Get-VisibleExternalHrefs $guide
if ($visibleExternalHrefs.Count -lt 4 -or $visibleExternalHrefs.Count -gt 6) {
    throw "visible external body-link budget should be between 4 and 6; found $($visibleExternalHrefs.Count): $($visibleExternalHrefs -join ' | ')"
}

Write-Output 'Ninh Binh Day Trip vs Overnight static checks passed.'

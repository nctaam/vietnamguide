$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$homepage = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-homepage-premium.php')
$verifier = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/verify-eeat-content.php')
$foundation = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-foundation-seo-content.php')
$guidePath = Join-Path $repoRoot 'ops/apply-hanoi-to-ninh-binh-transport.php'
$guide = if (Test-Path -LiteralPath $guidePath) { Get-Content -Raw -Path $guidePath } else { '' }
$routeLine = 'Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ |'

$sourcePersistenceFiles = @(
    'ops/apply-ninh-binh-travel-guide.php',
    'ops/apply-ninh-binh-day-trip-overnight.php',
    'ops/apply-best-day-trips-hanoi-guide.php',
    'ops/apply-hanoi-travel-guide.php',
    'ops/apply-hanoi-in-2-days-itinerary.php',
    'ops/apply-transport-within-vietnam-guide.php',
    'ops/apply-vietnam-travel-guide.php',
    'ops/apply-north-central-south-comparison-guide.php',
    'ops/apply-vietnam-travel-cost-guide.php',
    'ops/apply-money-cash-cards-atms-guide.php',
    'ops/apply-sim-esim-vietnam-guide.php',
    'ops/apply-health-travel-insurance-guide.php',
    'ops/apply-safety-scams-vietnam-guide.php',
    'ops/apply-7-days-itinerary-guide.php',
    'ops/apply-10-days-itinerary-guide.php',
    'ops/apply-14-days-itinerary-guide.php',
    'ops/apply-21-days-itinerary-guide.php',
    'ops/apply-ha-long-bay-travel-guide.php',
    'ops/apply-ha-long-lan-ha-comparison-guide.php'
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

Require-Contains 'guide file exists' $guide 'ops/apply-hanoi-to-ninh-binh-transport.php'
Require-Contains 'guide force flag' $guide 'VG_FORCE_HANOI_NINH_BINH_TRANSPORT_REPUBLISH'
Require-Contains 'guide repair flag' $guide 'VG_REPAIR_HANOI_NINH_BINH_TRANSPORT_LINKS'
Require-Contains 'guide title' $guide "'post_title'     => 'Hanoi to Ninh Binh Transport'"
Require-Contains 'guide slug' $guide "'post_name'      => 'hanoi-to-ninh-binh-transport'"
Require-Contains 'parent plan lookup' $guide "get_page_by_path('plan'"
Require-Contains 'guide published path guard' $guide "vg_hanoi_ninh_binh_transport_ops_published_page_exists('plan/hanoi-to-ninh-binh-transport')"
Require-Contains 'plan hub marker' $guide 'vg-hanoi-ninh-binh-transport-plan-hub-note:v1'
Require-Contains 'destinations hub marker' $guide 'vg-hanoi-ninh-binh-transport-destinations-hub-note:v1'
Require-Contains 'homepage marker' $guide 'vg-hanoi-ninh-binh-transport-homepage-route-spine:v1'

Require-Contains 'hero marker' $guide 'vg-hanoi-ninh-binh-transport-hero:v1'
Require-Contains 'concierge verdict' $guide 'vg-hanoi-ninh-binh-transport-concierge-verdict'
Require-Contains 'shared concierge class' $guide 'vg-concierge-verdict'
Require-Contains 'editorial proof panel' $guide 'vietnamguide/editorial-proof-panel'
Require-Contains 'at a glance' $guide 'vg-hanoi-ninh-binh-transport-at-a-glance:v1'
Require-Contains 'photo grid' $guide 'vg-hanoi-ninh-binh-transport-photo-grid:v1'
Require-Contains 'source diversity' $guide 'vg-hanoi-ninh-binh-transport-source-diversity:v1'
Require-Contains 'source trail snapshot' $guide 'vg-hanoi-ninh-binh-transport-source-trail-snapshot:v1'
Require-Contains 'mode verdict matrix' $guide 'vg-hanoi-ninh-binh-transport-mode-matrix:v1'
Require-Contains 'door to door equation' $guide 'vg-hanoi-ninh-binh-transport-door-to-door:v1'
Require-Contains 'departure arrival map' $guide 'vg-hanoi-ninh-binh-transport-departure-arrival:v1'
Require-Contains 'train guide' $guide 'vg-hanoi-ninh-binh-transport-train-guide:v1'
Require-Contains 'limousine van guide' $guide 'vg-hanoi-ninh-binh-transport-limousine-van:v1'
Require-Contains 'private car guide' $guide 'vg-hanoi-ninh-binh-transport-private-car:v1'
Require-Contains 'day tour onward transfer' $guide 'vg-hanoi-ninh-binh-transport-day-tour-onward:v1'
Require-Contains 'base luggage logic' $guide 'vg-hanoi-ninh-binh-transport-base-luggage:v1'
Require-Contains 'sample routing' $guide 'vg-hanoi-ninh-binh-transport-sample-routing:v1'
Require-Contains 'booking audit' $guide 'vg-hanoi-ninh-binh-transport-booking-audit:v1'
Require-Contains 'failure modes' $guide 'vg-hanoi-ninh-binh-transport-failure-modes:v1'
Require-Contains 'live checks' $guide 'vg-hanoi-ninh-binh-transport-live-checks:v1'
Require-Contains 'FAQ' $guide 'vg-hanoi-ninh-binh-transport-faq:v1'
Require-Contains 'related routes shortcode' $guide '[vg_related_routes]'
Require-Contains 'source trail pattern' $guide 'vietnamguide/source-trail'
Require-Contains 'update log pattern' $guide 'vietnamguide/update-log'

Require-Contains 'default verdict depth' $guide 'For most independent international travelers, the best default is a limousine van only when the exact drop-off works; otherwise choose train for rail-centered plans or private car when control protects the day.'
Require-Contains 'private car premium judgment' $guide 'Private car is the premium answer when luggage, children, a late arrival, a countryside hotel, or an onward bay/airport handoff would make shared transport brittle.'
Require-Contains 'train caveat judgment' $guide 'Train is a clean choice when Ninh Binh station is useful, not when the real destination is a Tam Coc lane without a final-transfer plan.'
Require-Contains 'day tour caveat' $guide 'A day tour is not a substitute for transport to an overnight base.'
Require-Contains 'unstable schedule warning' $guide 'Do not trust copied train times, van pickups, or tour return windows without a same-week live check.'
Require-Contains 'source limit framing' $guide 'Sources can confirm official destination context, rail portals, and tourism framing; they cannot decide your luggage, sleep debt, hotel lane, or tomorrow morning.'

Require-Contains 'Vietnam.travel Ninh Binh source' $guide 'vietnam.travel/places-to-go/northern-vietnam/ninh-binh'
Require-Contains 'Vietnam.travel transport source' $guide 'vietnam.travel/plan-your-trip/transport-within-vietnam'
Require-Contains 'Vietnam Railways ticket source' $guide 'dsvn.vn'
Require-Contains 'Vietnam Railways corporate source' $guide 'vr.com.vn/en'
Require-Contains 'Ninh Binh tourism source' $guide 'dulichninhbinh.com.vn/en'
Require-Contains 'UNESCO Trang An source' $guide 'whc.unesco.org/en/list/1438'

Require-Contains 'Hanoi railway image credit' $guide 'Alancrh / CC BY-SA 3.0'
Require-Contains 'Trang An image credit' $guide 'Jakub Halun / CC BY 4.0'
Require-Contains 'Tam Coc image credit' $guide 'Andre Hospers / CC BY 4.0'
Require-Contains 'Noi Bai image credit' $guide 'Sky 269 / CC BY-SA 4.0'
Require-Contains 'Hanoi railway image record' $guide 'Hanoi_Railway_Station_20130725.jpg'
Require-Contains 'Trang An image record' $guide 'Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg'
Require-Contains 'Tam Coc image record' $guide 'Tam_Coc_Ninh_Binh_%2829079%29.jpg'
Require-Contains 'Noi Bai terminal image record' $guide 'Noi_Bai_International_Airport_T2_Waiting_Area.jpg'

Require-Contains 'related-route href-based route parsing' $guide '$route_href = $route_parts[1];'
Require-Contains 'related-route href-based target detection' $guide '$is_target_line = $line_href === $route_href || str_contains($line, $route_href);'
Require-Contains 'related-route idempotent skip log' $guide 'line is already current'
Require-NotContains 'related-route label-prefix-only dedupe' $guide "str_starts_with(`$line, `$route_label . ' |')"

Require-Contains 'homepage required path' $homepage "`$hanoi_ninh_binh_transport_href = vg_home_required_path('plan/hanoi-to-ninh-binh-transport');"
Require-Contains 'homepage URL' $homepage '/plan/hanoi-to-ninh-binh-transport/'
Require-Contains 'homepage planning row' $homepage 'I know Ninh Binh belongs in the route and need the right transfer from Hanoi.'
Require-Contains 'homepage route verdict row' $homepage 'Choose Hanoi to Ninh Binh transport by drop-off control, not just ticket price.'
Require-Contains 'homepage guide shelf row' $homepage 'Hanoi to Ninh Binh Transport'
Require-Contains 'foundation plan hub persistence marker' $foundation 'vg-hanoi-ninh-binh-transport-plan-hub-note:v1'
Require-Contains 'foundation plan hub persistence href' $foundation '/plan/hanoi-to-ninh-binh-transport/'
Require-Contains 'foundation destinations hub persistence marker' $foundation 'vg-hanoi-ninh-binh-transport-destinations-hub-note:v1'
Require-Contains 'foundation destinations hub persistence href' $foundation '/plan/hanoi-to-ninh-binh-transport/'

foreach ($sourceFile in $sourcePersistenceFiles) {
    $path = Join-Path $repoRoot $sourceFile
    if (-not (Test-Path -LiteralPath $path)) {
        throw "source persistence file missing: $sourceFile"
    }

    $source = Get-Content -Raw -Path $path
    Require-Contains "$sourceFile inbound source persistence" $source $routeLine
}

Require-Contains 'EEAT verifier page label' $verifier 'Hanoi to Ninh Binh Transport'
Require-Contains 'EEAT verifier hero marker' $verifier 'vg-hanoi-ninh-binh-transport-hero:v1'
Require-Contains 'EEAT verifier plan hub note' $verifier 'vg-hanoi-ninh-binh-transport-plan-hub-note:v1'
Require-Contains 'EEAT verifier destinations hub note' $verifier 'vg-hanoi-ninh-binh-transport-destinations-hub-note:v1'
Require-Contains 'EEAT verifier homepage href' $verifier 'href="/plan/hanoi-to-ninh-binh-transport/"'
Require-Contains 'EEAT verifier sitemap path call' $verifier "'plan/hanoi-to-ninh-binh-transport',"
Require-Contains 'EEAT verifier visible depth phrase' $verifier 'For most independent international travelers, the best default is a limousine van only when the exact drop-off works; otherwise choose train for rail-centered plans or private car when control protects the day.'
Require-Contains 'EEAT verifier rendered source snapshot' $verifier 'rendered Hanoi to Ninh Binh transport source trail snapshot'
Require-Contains 'EEAT verifier rendered source URL' $verifier 'https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh'
Require-Contains 'EEAT verifier external budget target' $verifier '$hanoi_ninh_binh_transport_page'
Require-Contains 'EEAT verifier own route metadata' $verifier "vg_verify_eeat_require_own_related_route_meta(`n        `$hanoi_ninh_binh_transport_page"
Require-Contains 'EEAT verifier inbound related-route check' $verifier '/plan/hanoi-to-ninh-binh-transport/'
Require-Contains 'EEAT verifier exact label enabled' $verifier "`$failures,`n        true"

$visibleExternalHrefs = Get-VisibleExternalHrefs $guide
if ($visibleExternalHrefs.Count -lt 4 -or $visibleExternalHrefs.Count -gt 6) {
    throw "visible external body-link budget should be between 4 and 6; found $($visibleExternalHrefs.Count): $($visibleExternalHrefs -join ' | ')"
}

Write-Output 'Hanoi to Ninh Binh Transport static checks passed.'

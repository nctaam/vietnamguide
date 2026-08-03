$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$homepage = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-homepage-premium.php')
$verifier = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/verify-eeat-content.php')
$foundation = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-foundation-seo-content.php')
$guidePath = Join-Path $repoRoot 'ops/apply-ninh-binh-to-ha-long-transfer.php'
$guide = if (Test-Path -LiteralPath $guidePath) { Get-Content -Raw -Path $guidePath } else { '' }
$routeLine = 'Ninh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ |'

$sourcePersistenceFiles = @(
    'ops/apply-ninh-binh-travel-guide.php',
    'ops/apply-ninh-binh-day-trip-overnight.php',
    'ops/apply-trang-an-vs-tam-coc.php',
    'ops/apply-hanoi-to-ninh-binh-transport.php',
    'ops/apply-where-to-stay-in-ninh-binh.php',
    'ops/apply-best-day-trips-hanoi-guide.php',
    'ops/apply-hanoi-in-2-days-itinerary.php',
    'ops/apply-hanoi-travel-guide.php',
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

Require-Contains 'guide file exists' $guide 'ops/apply-ninh-binh-to-ha-long-transfer.php'
Require-Contains 'guide force flag' $guide 'VG_FORCE_NINH_BINH_HA_LONG_TRANSFER_REPUBLISH'
Require-Contains 'guide repair flag' $guide 'VG_REPAIR_NINH_BINH_HA_LONG_TRANSFER_LINKS'
Require-Contains 'guide title' $guide "'post_title'     => 'Ninh Binh to Ha Long Bay Transfer'"
Require-Contains 'guide slug' $guide "'post_name'      => 'ninh-binh-to-ha-long-bay-transfer'"
Require-Contains 'parent plan lookup' $guide "get_page_by_path('plan'"
Require-Contains 'guide published path guard' $guide "vg_ninh_binh_ha_long_transfer_ops_published_page_exists('plan/ninh-binh-to-ha-long-bay-transfer')"
Require-Contains 'plan hub marker' $guide 'vg-ninh-binh-ha-long-transfer-plan-hub-note:v1'
Require-Contains 'destinations hub marker' $guide 'vg-ninh-binh-ha-long-transfer-destinations-hub-note:v1'
Require-Contains 'homepage marker' $guide 'vg-ninh-binh-ha-long-transfer-homepage-route-spine:v1'

Require-Contains 'hero marker' $guide 'vg-ninh-binh-ha-long-transfer-hero:v1'
Require-Contains 'concierge verdict' $guide 'vg-ninh-binh-ha-long-transfer-concierge-verdict'
Require-Contains 'shared concierge class' $guide 'vg-concierge-verdict'
Require-Contains 'editorial proof panel' $guide 'vietnamguide/editorial-proof-panel'
Require-Contains 'at a glance' $guide 'vg-ninh-binh-ha-long-transfer-at-a-glance:v1'
Require-Contains 'photo proof' $guide 'vg-ninh-binh-ha-long-transfer-photo-proof:v1'
Require-Contains 'source diversity' $guide 'vg-ninh-binh-ha-long-transfer-source-diversity:v1'
Require-Contains 'source trail snapshot' $guide 'vg-ninh-binh-ha-long-transfer-source-trail-snapshot:v1'
Require-Contains 'route verdict' $guide 'vg-ninh-binh-ha-long-transfer-route-verdict:v1'
Require-Contains 'mode matrix' $guide 'vg-ninh-binh-ha-long-transfer-mode-matrix:v1'
Require-Contains 'port first audit' $guide 'vg-ninh-binh-ha-long-transfer-port-first:v1'
Require-Contains 'pickup window' $guide 'vg-ninh-binh-ha-long-transfer-pickup-window:v1'
Require-Contains 'base luggage logic' $guide 'vg-ninh-binh-ha-long-transfer-base-luggage:v1'
Require-Contains 'cruise handoff' $guide 'vg-ninh-binh-ha-long-transfer-cruise-handoff:v1'
Require-Contains 'Lan Ha Cat Ba exception' $guide 'vg-ninh-binh-ha-long-transfer-lan-ha-cat-ba:v1'
Require-Contains 'overnight buffer' $guide 'vg-ninh-binh-ha-long-transfer-overnight-buffer:v1'
Require-Contains 'season weather' $guide 'vg-ninh-binh-ha-long-transfer-season-weather:v1'
Require-Contains 'cost booking' $guide 'vg-ninh-binh-ha-long-transfer-cost-booking:v1'
Require-Contains 'booking audit' $guide 'vg-ninh-binh-ha-long-transfer-booking-audit:v1'
Require-Contains 'mistakes skip' $guide 'vg-ninh-binh-ha-long-transfer-mistakes-skip:v1'
Require-Contains 'live checks' $guide 'vg-ninh-binh-ha-long-transfer-live-checks:v1'
Require-Contains 'FAQ' $guide 'vg-ninh-binh-ha-long-transfer-faq:v1'
Require-Contains 'related routes shortcode' $guide '[vg_related_routes]'
Require-Contains 'source trail pattern' $guide 'vietnamguide/source-trail'
Require-Contains 'update log pattern' $guide 'vietnamguide/update-log'

Require-Contains 'default verdict' $guide 'For most international travelers, the safest default is a private transfer or cruise-arranged transfer only after the exact port and pickup window are confirmed.'
Require-Contains 'port first warning' $guide 'Do not book a generic Ha Long transfer until you know whether your cruise leaves from Tuan Chau, Ha Long International Cruise Port, Hon Gai, Got Pier, or a Cat Ba/Lan Ha pickup point.'
Require-Contains 'cheap seat warning' $guide 'The cheapest seat can become expensive if it reaches the wrong city, wrong pier, or too late for embarkation.'
Require-Contains 'anti stale timetable' $guide 'This guide does not publish stale timetables or scrape operator claims; it teaches the transfer decision that protects the cruise day.'
Require-Contains 'source limit framing' $guide 'Sources can confirm destination context, port and route systems, heritage status, weather pressure, and transport categories; they cannot decide your luggage, hotel lane, cruise check-in, child fatigue, or whether one more northern stop makes the route brittle.'

Require-Contains 'Vietnam.travel Ninh Binh source' $guide 'vietnam.travel/places-to-go/northern-vietnam/ninh-binh'
Require-Contains 'Vietnam.travel Ha Long source' $guide 'vietnam.travel/places-to-go/northern-vietnam/ha-long'
Require-Contains 'Vietnam.travel transport source' $guide 'vietnam.travel/plan-your-trip/transport-within-vietnam'
Require-Contains 'Vietnam.travel weather source' $guide 'weather-and-climate-vietnam'
Require-Contains 'UNESCO Ha Long source' $guide 'whc.unesco.org/en/list/672'
Require-Contains 'Ha Long Bay Management source' $guide 'halongbay.com.vn'
Require-Contains 'Ninh Binh tourism source' $guide 'dulichninhbinh.com.vn/en'
Require-Contains 'Cat Ba source' $guide 'catba.com.vn'

Require-Contains 'Tam Coc image credit' $guide 'Hoang Giang Hai / CC BY 2.0'
Require-Contains 'Ha Long aerial image credit' $guide 'Vyacheslav Argenberg / CC BY 4.0'
Require-Contains 'Lan Ha image credit' $guide 'Saaremees / CC BY-SA 4.0'
Require-Contains 'cruise boats image credit' $guide 'Shyamal L. / CC BY-SA 4.0'
Require-Contains 'Bai Chay image credit' $guide 'Pdhadam / CC BY-SA 4.0'
Require-Contains 'non-linked credit policy' $guide 'Image credits are listed as text to keep the route decision readable and reduce visible outbound clutter.'
Require-Contains 'Tam Coc image record' $guide 'Tam_Coc_Rice_Valley_%288756354342%29.jpg'
Require-Contains 'Ha Long image record' $guide 'Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg'
Require-Contains 'Lan Ha image record' $guide 'Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg'
Require-Contains 'cruise boats image record' $guide 'Halong_Bay_Cruise_Boats_01.jpg'
Require-Contains 'Bai Chay image record' $guide 'Bai_Chay_Pano_10-2023.jpg'
Require-NotContains 'no external image credit anchors' $guide 'rel="license noopener"'

Require-Contains 'related-route href-based route parsing' $guide '$route_href = $route_parts[1];'
Require-Contains 'related-route href-based target detection' $guide '$is_target_line = $line_href === $route_href || str_contains($line, $route_href);'
Require-Contains 'related-route idempotent skip log' $guide 'line is already current'
Require-NotContains 'related-route label-prefix-only dedupe' $guide "str_starts_with(`$line, `$route_label . ' |')"

Require-Contains 'homepage required path' $homepage "`$ninh_binh_ha_long_transfer_href = vg_home_required_path('plan/ninh-binh-to-ha-long-bay-transfer');"
Require-Contains 'homepage URL' $homepage '/plan/ninh-binh-to-ha-long-bay-transfer/'
Require-Contains 'homepage planning row' $homepage 'I need to get from Ninh Binh to Ha Long Bay without missing the cruise.'
Require-Contains 'homepage route verdict row' $homepage 'Confirm the exact bay port before choosing the Ninh Binh transfer.'
Require-Contains 'homepage guide shelf row' $homepage 'Ninh Binh to Ha Long Bay Transfer'
Require-Contains 'foundation plan hub persistence marker' $foundation 'vg-ninh-binh-ha-long-transfer-plan-hub-note:v1'
Require-Contains 'foundation plan hub persistence href' $foundation '/plan/ninh-binh-to-ha-long-bay-transfer/'
Require-Contains 'foundation destinations hub persistence marker' $foundation 'vg-ninh-binh-ha-long-transfer-destinations-hub-note:v1'
Require-Contains 'foundation destinations hub persistence href' $foundation '/plan/ninh-binh-to-ha-long-bay-transfer/'

foreach ($sourceFile in $sourcePersistenceFiles) {
    $path = Join-Path $repoRoot $sourceFile
    if (-not (Test-Path -LiteralPath $path)) {
        throw "source persistence file missing: $sourceFile"
    }

    $source = Get-Content -Raw -Path $path
    Require-Contains "$sourceFile inbound source persistence" $source $routeLine
}

Require-Contains 'EEAT verifier page label' $verifier 'Ninh Binh to Ha Long Bay Transfer'
Require-Contains 'EEAT verifier hero marker' $verifier 'vg-ninh-binh-ha-long-transfer-hero:v1'
Require-Contains 'EEAT verifier plan hub note' $verifier 'vg-ninh-binh-ha-long-transfer-plan-hub-note:v1'
Require-Contains 'EEAT verifier destinations hub note' $verifier 'vg-ninh-binh-ha-long-transfer-destinations-hub-note:v1'
Require-Contains 'EEAT verifier homepage href' $verifier 'href="/plan/ninh-binh-to-ha-long-bay-transfer/"'
Require-Contains 'EEAT verifier sitemap path call' $verifier "'plan/ninh-binh-to-ha-long-bay-transfer',"
Require-Contains 'EEAT verifier visible depth phrase' $verifier 'For most international travelers, the safest default is a private transfer or cruise-arranged transfer only after the exact port and pickup window are confirmed.'
Require-Contains 'EEAT verifier rendered source snapshot' $verifier 'rendered Ninh Binh to Ha Long Bay Transfer source trail snapshot'
Require-Contains 'EEAT verifier rendered source URL' $verifier 'https://vietnam.travel/places-to-go/northern-vietnam/ha-long'
Require-Contains 'EEAT verifier external budget target' $verifier '$ninh_binh_ha_long_transfer_page'
Require-Contains 'EEAT verifier own route metadata' $verifier "vg_verify_eeat_require_own_related_route_meta(`n        `$ninh_binh_ha_long_transfer_page"
Require-Contains 'EEAT verifier inbound related-route check' $verifier '/plan/ninh-binh-to-ha-long-bay-transfer/'
Require-Contains 'EEAT verifier exact label enabled' $verifier "`$failures,`n        true"

$visibleExternalHrefs = Get-VisibleExternalHrefs $guide
if ($visibleExternalHrefs.Count -lt 0 -or $visibleExternalHrefs.Count -gt 2) {
    throw "visible external body-link budget should be between 0 and 2; found $($visibleExternalHrefs.Count): $($visibleExternalHrefs -join ' | ')"
}

$imageCount = ([regex]::Matches($guide, '<img\s', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)).Count
if ($imageCount -lt 5) {
    throw "guide should include at least five body/hero images; found $imageCount"
}

Write-Output 'Ninh Binh to Ha Long Bay Transfer static checks passed.'

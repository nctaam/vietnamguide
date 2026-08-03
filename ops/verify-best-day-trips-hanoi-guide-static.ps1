$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$homepage = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-homepage-premium.php')
$hanoi = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-hanoi-travel-guide.php')
$hanoiStays = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-where-to-stay-in-hanoi.php')
$bestThingsHanoi = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-best-things-hanoi-guide.php')
$ninhBinh = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-ninh-binh-travel-guide.php')
$haLong = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-ha-long-bay-travel-guide.php')
$bayCompare = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-ha-long-lan-ha-comparison-guide.php')
$catBa = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-cat-ba-travel-guide.php')
$baiTuLong = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-bai-tu-long-bay-guide.php')
$vietnamTravel = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-vietnam-travel-guide.php')
$bestPlaces = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-best-places-destination-guide.php')
$regionCompare = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-north-central-south-comparison-guide.php')
$bestTime = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-best-time-guide.php')
$transport = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-transport-within-vietnam-guide.php')
$cost = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-vietnam-travel-cost-guide.php')
$money = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-money-cash-cards-atms-guide.php')
$simEsim = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-sim-esim-vietnam-guide.php')
$evisa = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-vietnam-evisa-guide.php')
$sevenDays = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-7-days-itinerary-guide.php')
$tenDays = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-10-days-itinerary-guide.php')
$fourteenDays = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-14-days-itinerary-guide.php')
$twentyOneDays = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-21-days-itinerary-guide.php')
$healthInsurance = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-health-travel-insurance-guide.php')
$safetyScams = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/apply-safety-scams-vietnam-guide.php')
$verifier = Get-Content -Raw -Path (Join-Path $repoRoot 'ops/verify-eeat-content.php')
$guidePath = Join-Path $repoRoot 'ops/apply-best-day-trips-hanoi-guide.php'
$guide = if (Test-Path -LiteralPath $guidePath) { Get-Content -Raw -Path $guidePath } else { '' }
$hanoiDayTripsRouteLine = 'Best Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ |'

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

Require-Contains 'guide file exists' $guide 'ops/apply-best-day-trips-hanoi-guide.php'
Require-Contains 'guide force flag' $guide 'VG_FORCE_HANOI_DAY_TRIPS_REPUBLISH'
Require-Contains 'guide repair flag' $guide 'VG_REPAIR_HANOI_DAY_TRIPS_LINKS'
Require-Contains 'guide title' $guide "'post_title'     => 'Best Day Trips from Hanoi'"
Require-Contains 'guide slug' $guide "'post_name'      => 'best-day-trips-from-hanoi'"
Require-Contains 'parent destinations lookup' $guide "get_page_by_path('destinations'"
Require-Contains 'guide published path guard' $guide "vg_hanoi_day_trips_ops_published_page_exists('destinations/best-day-trips-from-hanoi')"
Require-Contains 'destinations hub marker' $guide 'vg-hanoi-day-trips-destinations-hub-note:v1'
Require-Contains 'homepage marker' $guide 'vg-hanoi-day-trips-homepage-route-spine:v1'

Require-Contains 'hero marker' $guide 'vg-hanoi-day-trips-hero:v1'
Require-Contains 'concierge verdict' $guide 'vg-hanoi-day-trips-concierge-verdict'
Require-Contains 'shared concierge class' $guide 'vg-concierge-verdict'
Require-Contains 'editorial proof panel' $guide 'vietnamguide/editorial-proof-panel'
Require-Contains 'at a glance' $guide 'vg-hanoi-day-trips-at-a-glance:v1'
Require-Contains 'photo grid' $guide 'vg-hanoi-day-trips-photo-grid:v1'
Require-Contains 'source diversity' $guide 'vg-hanoi-day-trips-source-diversity:v1'
Require-Contains 'decision grid' $guide 'vg-hanoi-day-trips-decision-grid:v1'
Require-Contains 'quick chooser' $guide 'vg-hanoi-day-trips-quick-chooser:v1'
Require-Contains 'route fit' $guide 'vg-hanoi-day-trips-route-fit:v1'
Require-Contains 'day placement' $guide 'vg-hanoi-day-trips-day-placement:v1'
Require-Contains 'transfer value scorecard' $guide 'vg-hanoi-day-trips-transfer-value-scorecard:v1'
Require-Contains 'day trip vs overnight' $guide 'vg-hanoi-day-trips-overnight-upgrade:v1'
Require-Contains 'booking mode' $guide 'vg-hanoi-day-trips-booking-mode:v1'
Require-Contains 'operator checks' $guide 'vg-hanoi-day-trips-operator-checks:v1'
Require-Contains 'family comfort' $guide 'vg-hanoi-day-trips-comfort-fit:v1'
Require-Contains 'skip logic' $guide 'vg-hanoi-day-trips-skip-logic:v1'
Require-Contains 'live checks' $guide 'vg-hanoi-day-trips-live-checks:v1'
Require-Contains 'FAQ' $guide 'vg-hanoi-day-trips-faq:v1'
Require-Contains 'related routes shortcode' $guide '[vg_related_routes]'
Require-Contains 'source trail pattern' $guide 'vietnamguide/source-trail'
Require-Contains 'update log pattern' $guide 'vietnamguide/update-log'

Require-Contains 'Ninh Binh decision depth' $guide 'Ninh Binh is the strongest default day trip from Hanoi only when it has a calm pickup, one boat landscape, one viewpoint or temple, and no rushed same-night transfer afterward.'
Require-Contains 'bay day trip caution' $guide 'Ha Long or Lan Ha as a day trip is a premium logistics product first and a scenery product second.'
Require-Contains 'Bat Trang decision depth' $guide 'Bat Trang is not a replacement for Ninh Binh or the bay; it is the half-day craft answer when Hanoi needs texture without a punishing transfer.'
Require-Contains 'Duong Lam decision depth' $guide 'Duong Lam works when rural architecture and slow cultural context matter more than headline scenery.'
Require-Contains 'Perfume Pagoda caution' $guide 'Perfume Pagoda is worth choosing only when the boat, pilgrimage rhythm, and cave-temple journey are the point.'
Require-Contains 'Ba Vi weather depth' $guide 'Ba Vi is a weather-sensitive nature day; choose it for cooler air, forest, and hiking, not for a guaranteed postcard view.'
Require-Contains 'stay in Hanoi fallback' $guide 'Staying in Hanoi is the correct day-trip decision when the outside option only adds road time to an already thin capital chapter.'
Require-Contains 'transfer-to-experience wording' $guide 'transfer-to-experience ratio'
Require-Contains 'middle full day rule' $guide 'Use the middle full day, not arrival day or departure day'
Require-Contains 'operator checklist depth' $guide 'A premium operator answer should name the pickup zone, vehicle standard, guide language, ticket inclusions, lunch plan, cancellation terms, and realistic return window.'
Require-Contains 'combo warning' $guide 'A tour that promises Ninh Binh plus Ha Long Bay in one day is selling geography compression, not quality.'

Require-Contains 'official Hanoi day trips source' $guide 'vietnam.travel/things-to-do/5-hanoi-day-trips'
Require-Contains 'official Ninh Binh source' $guide 'vietnam.travel/places-to-go/northern-vietnam/ninh-binh'
Require-Contains 'official Ha Long source' $guide 'vietnam.travel/places-to-go/northern-vietnam/ha-long'
Require-Contains 'UNESCO bay source' $guide 'whc.unesco.org/en/list/672'
Require-Contains 'official Bat Trang source' $guide 'vietnam.travel/things-to-do/full-day-trip-bat-trang-pottery-village'
Require-Contains 'weather source' $guide 'vietnam.travel/things-to-do/weather-and-climate-vietnam'
Require-Contains 'NCHMF source' $guide 'nchmf.gov.vn'
Require-Contains 'transport source' $guide 'vietnam.travel/plan-your-trip/transport-within-vietnam'
Require-Contains 'Noi Bai source' $guide 'vietnamairport.vn/en/noi-bai-airport'
Require-Contains 'eVisa source' $guide 'evisa.gov.vn'

Require-Contains 'hero image credit' $guide 'Alex 69200 vx / CC BY-SA 4.0'
Require-Contains 'Trang An image credit' $guide 'Jakub Halun / CC BY 4.0'
Require-Contains 'Ha Long image credit' $guide 'Vyacheslav Argenberg / CC BY 4.0'
Require-Contains 'Bat Trang image credit' $guide 'Vuong Tri Binh / CC BY-SA 4.0'
Require-Contains 'Perfume Pagoda image credit' $guide 'Tango7174 / CC BY 3.0'
Require-Contains 'Ba Vi image credit' $guide 'Steven C. Price / CC BY-SA 4.0'
Require-Contains 'Wikimedia Hanoi image record' $guide 'Hanoi-lac-hoan-kiem.jpg'
Require-Contains 'Wikimedia Trang An image record' $guide 'Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg'
Require-Contains 'Wikimedia Ha Long image record' $guide 'Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg'
Require-Contains 'Wikimedia Bat Trang image record' $guide 'Bat_Trang_pottery_and_ceramics_village_in_2016_20.jpg'
Require-Contains 'Wikimedia Perfume Pagoda image record' $guide 'VN_Chua_Huong5_tango7174.jpg'
Require-Contains 'Wikimedia Ba Vi image record' $guide 'Ba-Vi2-Vietnam.jpg'

Require-Contains 'related-route href-based route parsing' $guide '$route_href = $route_parts[1];'
Require-Contains 'related-route href-based target detection' $guide '$is_target_line = $line_href === $route_href || str_contains($line, $route_href);'
Require-Contains 'related-route idempotent skip log' $guide 'line is already current'
Require-NotContains 'related-route label-prefix-only dedupe' $guide "str_starts_with(`$line, `$route_label . ' |')"

Require-Contains 'homepage required path' $homepage "`$hanoi_day_trips_guide_href = vg_home_required_path('destinations/best-day-trips-from-hanoi');"
Require-Contains 'homepage URL' $homepage '/destinations/best-day-trips-from-hanoi/'
Require-Contains 'homepage planning row' $homepage 'I need to choose the right day trip from Hanoi.'
Require-Contains 'homepage verdict row' $homepage 'Choose one Hanoi day trip only after the capital itself has protected time.'
Require-Contains 'homepage guide row' $homepage 'Best Day Trips from Hanoi'

Require-Contains 'Hanoi Travel Guide inbound' $hanoi 'Best Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ |'
Require-Contains 'Where to Stay in Hanoi inbound' $hanoiStays 'Best Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ |'
Require-Contains 'Best Things Hanoi inbound' $bestThingsHanoi 'Best Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ |'
Require-Contains 'Ninh Binh inbound' $ninhBinh 'Best Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ |'
Require-Contains 'Ha Long inbound' $haLong 'Best Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ |'
Require-Contains 'Ha Long vs Lan Ha inbound' $bayCompare 'Best Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ |'
Require-Contains 'Cat Ba inbound source persistence' $catBa $hanoiDayTripsRouteLine
Require-Contains 'Bai Tu Long inbound source persistence' $baiTuLong $hanoiDayTripsRouteLine
Require-Contains 'Vietnam Travel Guide inbound source persistence' $vietnamTravel $hanoiDayTripsRouteLine
Require-Contains 'Best Places inbound source persistence' $bestPlaces $hanoiDayTripsRouteLine
Require-Contains 'North/Central/South inbound source persistence' $regionCompare $hanoiDayTripsRouteLine
Require-Contains 'Best Time inbound source persistence' $bestTime $hanoiDayTripsRouteLine
Require-Contains 'Transport inbound source persistence' $transport $hanoiDayTripsRouteLine
Require-Contains 'Cost inbound source persistence' $cost $hanoiDayTripsRouteLine
Require-Contains 'Money inbound source persistence' $money $hanoiDayTripsRouteLine
Require-Contains 'SIM/eSIM inbound source persistence' $simEsim $hanoiDayTripsRouteLine
Require-Contains 'E-Visa inbound source persistence' $evisa $hanoiDayTripsRouteLine
Require-Contains '7 Days inbound source persistence' $sevenDays $hanoiDayTripsRouteLine
Require-Contains '10 Days inbound source persistence' $tenDays $hanoiDayTripsRouteLine
Require-Contains '14 Days inbound source persistence' $fourteenDays $hanoiDayTripsRouteLine
Require-Contains '21 Days inbound source persistence' $twentyOneDays $hanoiDayTripsRouteLine
Require-Contains 'Health/Insurance inbound source persistence' $healthInsurance $hanoiDayTripsRouteLine
Require-Contains 'Safety inbound source persistence' $safetyScams $hanoiDayTripsRouteLine

Require-Contains 'EEAT verifier page label' $verifier 'Best Day Trips from Hanoi'
Require-Contains 'EEAT verifier hero marker' $verifier 'vg-hanoi-day-trips-hero:v1'
Require-Contains 'EEAT verifier destinations hub note' $verifier 'vg-hanoi-day-trips-destinations-hub-note:v1'
Require-Contains 'EEAT verifier homepage href' $verifier 'href="/destinations/best-day-trips-from-hanoi/"'
Require-Contains 'EEAT verifier external budget function' $verifier 'vg_verify_eeat_require_visible_external_body_link_budget('
Require-Contains 'EEAT verifier external budget target' $verifier '$hanoi_day_trips_guide_page'
Require-Contains 'EEAT verifier inbound related-route check' $verifier '/destinations/best-day-trips-from-hanoi/'

$visibleExternalHrefs = Get-VisibleExternalHrefs $guide
if ($visibleExternalHrefs.Count -lt 3 -or $visibleExternalHrefs.Count -gt 5) {
    throw "visible external body-link budget should be between 3 and 5; found $($visibleExternalHrefs.Count): $($visibleExternalHrefs -join ' | ')"
}

Write-Output 'Best Day Trips from Hanoi static checks passed.'

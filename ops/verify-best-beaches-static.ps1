$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$homepagePath = Join-Path $repoRoot 'ops/apply-homepage-premium.php'
$guidePath = Join-Path $repoRoot 'ops/apply-best-beaches-vietnam-guide.php'
$verifierPath = Join-Path $repoRoot 'ops/verify-eeat-content.php'

$normalizedGuidePath = $guidePath.Replace('\', '/')
$homepage = Get-Content -Raw -Path $homepagePath
$guide = if (Test-Path -LiteralPath $guidePath) { Get-Content -Raw -Path $guidePath } else { '' }
$verifier = Get-Content -Raw -Path $verifierPath

$failures = New-Object System.Collections.Generic.List[string]

function Require-Contains {
    param(
        [string] $Label,
        [string] $Haystack,
        [string] $Needle
    )

    if (-not $Haystack.Contains($Needle)) {
        $script:failures.Add("$Label missing: $Needle")
    }
}

function Require-NotContains {
    param(
        [string] $Label,
        [string] $Haystack,
        [string] $Needle
    )

    if ($Haystack.Contains($Needle)) {
        $script:failures.Add("$Label should not contain: $Needle")
    }
}

Require-Contains 'guide file exists' $normalizedGuidePath 'ops/apply-best-beaches-vietnam-guide.php'
Require-Contains 'publish env' $guide 'VG_FORCE_BEST_BEACHES_GUIDE_REPUBLISH'
Require-Contains 'guide slug' $guide "get_page_by_path('destinations/best-beaches-in-vietnam'"
Require-Contains 'guide title' $guide "'post_title'     => 'Best Beaches in Vietnam'"
Require-Contains 'guide post name' $guide "'post_name'      => 'best-beaches-in-vietnam'"

Require-Contains 'rank math title upgraded' $guide 'Best Beaches in Vietnam: Where to Go by Month'
Require-Contains 'rank math description upgraded' $guide 'Choose the best beach in Vietnam by month, route, and trip style'
Require-Contains 'excerpt upgraded' $guide 'month-by-month beach planner'
Require-Contains 'last meaningful update' $guide "vg_eeat_last_meaningful_update', 'July 25, 2026'"

Require-Contains 'hero marker' $guide 'vg-best-beaches-hero:v1'
Require-Contains 'concierge verdict marker' $guide 'vg-best-beaches-concierge-verdict'
Require-Contains 'proof panel' $guide 'vietnamguide/editorial-proof-panel'
Require-Contains 'at a glance marker' $guide 'vg-best-beaches-at-a-glance:v1'
Require-Contains 'source diversity marker' $guide 'vg-best-beaches-source-diversity:v1'
Require-Contains 'source trail snapshot marker' $guide 'vg-best-beaches-source-trail-snapshot:v1'
Require-Contains 'beach chooser marker' $guide 'vg-best-beaches-chooser:v1'
Require-Contains 'month planner marker' $guide 'vg-best-beaches-month-planner:v1'
Require-Contains 'traveler fit marker' $guide 'vg-best-beaches-traveler-fit:v1'
Require-Contains 'photo grid marker' $guide 'vg-best-beaches-photo-grid:v1'
Require-Contains 'cluster matrix marker' $guide 'vg-best-beaches-cluster-matrix:v1'
Require-Contains 'route fit marker' $guide 'vg-best-beaches-route-fit:v1'
Require-Contains 'season fit marker' $guide 'vg-best-beaches-season-fit:v1'
Require-Contains 'booking checks marker' $guide 'vg-best-beaches-booking-checks:v1'
Require-Contains 'skip logic marker' $guide 'vg-best-beaches-skip-logic:v1'
Require-Contains 'live checks marker' $guide 'vg-best-beaches-live-checks:v1'
Require-Contains 'FAQ marker' $guide 'vg-best-beaches-faq:v1'
Require-Contains 'related routes shortcode' $guide '[vg_related_routes]'
Require-Contains 'source trail pattern' $guide 'vietnamguide/source-trail'
Require-Contains 'update log pattern' $guide 'vietnamguide/update-log'

Require-Contains 'CTR verdict phrase' $guide 'If you only need one safe first-trip beach answer, choose Da Nang/My Khe or Hoi An/An Bang.'
Require-Contains 'anti spam route phrase' $guide 'This is not a prettiest-beach ranking; it is a beach decision guide.'
Require-Contains 'source limitation phrase' $guide 'Sources can confirm destination context, season pressure, transport friction, and named beach clusters; they cannot decide whether your route needs rest, activity, quiet, nightlife, children-friendly logistics, or a cleaner skip.'
Require-Contains 'month planner January February' $guide 'January-February'
Require-Contains 'month planner September November' $guide 'September-November'
Require-Contains 'family fit' $guide 'Families with children'
Require-Contains 'quiet luxury fit' $guide 'Quiet luxury / honeymoon'
Require-Contains 'budget fit' $guide 'Budget-conscious beach time'
Require-Contains 'source snapshot title' $guide 'Source trail snapshot for this beach decision'
Require-Contains 'text-only image credits note' $guide 'Image credits are listed as text to keep the beach decision readable and reduce visible outbound clutter.'

Require-Contains 'source Phu Quoc' $guide 'https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc'
Require-Contains 'source Con Dao' $guide 'https://vietnam.travel/places-to-go/southern-vietnam/con-dao'
Require-Contains 'source Da Nang' $guide 'https://vietnam.travel/places-to-go/central-vietnam/da-nang'
Require-Contains 'source Hoi An' $guide 'https://vietnam.travel/places-to-go/central-vietnam/hoi-an'
Require-Contains 'source Nha Trang' $guide 'https://vietnam.travel/places-to-go/central-vietnam/nha-trang'
Require-Contains 'source Phu Quy' $guide 'https://vietnam.travel/things-to-do/phu-quy-vietnam-island-destination'
Require-Contains 'source weather' $guide 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam'
Require-Contains 'source transport' $guide 'https://vietnam.travel/plan-your-trip/transport-within-vietnam'

Require-Contains 'image Phu Quoc' $guide 'Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg'
Require-Contains 'image My Khe' $guide 'My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg'
Require-Contains 'image An Bang' $guide '2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg'
Require-Contains 'image Nha Trang' $guide 'Nha_Trang_Beach_3.jpg'
Require-Contains 'image Mui Ne' $guide 'Vietnam%2C_Mui_Ne_beach%2C_Kitesurfing_on_the_beach.jpg'
Require-Contains 'image Con Dao' $guide 'C%C3%B4n_%C4%90%E1%BA%A3o_National_Park.jpg'
Require-Contains 'image Quy Nhon' $guide 'Ky_Co_beach%2C_Quy_Nhon_city%2C_Binh_Dinh_province%2C_Vietnam.jpg'
Require-Contains 'image Cat Ba' $guide 'Cat_Ba_Island%2C_Vietnam.JPG'
Require-Contains 'text credit Vivu Vietnam' $guide 'Vivu Vietnam, CC BY-SA 4.0'
Require-Contains 'text credit Christophe95' $guide 'Christophe95, CC BY-SA 4.0'
Require-Contains 'text credit Alexkom000' $guide 'Alexkom000, CC BY 4.0'
Require-Contains 'text credit Vyacheslav Argenberg' $guide 'Vyacheslav Argenberg, CC BY 4.0'

Require-Contains 'related route self line' $guide 'Best Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ |'
Require-Contains 'related route Best Islands' $guide 'Best Islands in Vietnam | /destinations/best-islands-in-vietnam/ |'
Require-Contains 'related route Phu Quoc' $guide 'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ |'
Require-Contains 'related route Da Nang' $guide 'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ |'
Require-Contains 'related route Da Nang vs Hoi An' $guide 'Da Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ |'
Require-Contains 'related route Phu Quoc vs Nha Trang' $guide 'Phu Quoc vs Nha Trang | /compare/phu-quoc-vs-nha-trang/ |'
Require-Contains 'related route 21 days' $guide '21 Days in Vietnam | /itineraries/21-days-in-vietnam/ |'

Require-NotContains 'old linked image credit pattern' $guide 'class="vg-image-credit" href="{$phu_quoc_hero_credit_url}"'
Require-NotContains 'linked official source in cluster matrix' $guide '<a href="https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc"'
Require-NotContains 'linked official source in live checks' $guide '<a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam"'

Require-Contains 'homepage Best Beaches variable' $homepage '$best_beaches_href'
Require-Contains 'homepage Best Beaches required path' $homepage "vg_home_required_path('destinations/best-beaches-in-vietnam')"
Require-Contains 'homepage Best Beaches label' $homepage 'Best Beaches in Vietnam'
Require-Contains 'homepage Best Beaches href' $homepage 'href="{$best_beaches_href}"'

Require-Contains 'EEAT verifier Best Beaches label' $verifier 'Best Beaches in Vietnam guide'
Require-Contains 'EEAT verifier Best Beaches path' $verifier 'destinations/best-beaches-in-vietnam'
Require-Contains 'EEAT verifier source diversity marker' $verifier 'vg-best-beaches-source-diversity:v1'
Require-Contains 'EEAT verifier source trail snapshot marker' $verifier 'vg-best-beaches-source-trail-snapshot:v1'
Require-Contains 'EEAT verifier beach chooser marker' $verifier 'vg-best-beaches-chooser:v1'
Require-Contains 'EEAT verifier month planner marker' $verifier 'vg-best-beaches-month-planner:v1'
Require-Contains 'EEAT verifier destinations hub note' $verifier 'vg-best-beaches-destinations-hub-note:v1'

if ($failures.Count -gt 0) {
    $failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output 'Best Beaches in Vietnam static checks passed.'

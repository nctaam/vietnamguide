$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$homepagePath = Join-Path $repoRoot 'ops/apply-homepage-premium.php'
$guidePath = Join-Path $repoRoot 'ops/apply-best-islands-vietnam-guide.php'
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

Require-Contains 'guide file exists' $normalizedGuidePath 'ops/apply-best-islands-vietnam-guide.php'
Require-Contains 'publish env' $guide 'VG_FORCE_BEST_ISLANDS_GUIDE_REPUBLISH'
Require-Contains 'repair env' $guide 'VG_REPAIR_BEST_ISLANDS_GUIDE_LINKS'
Require-Contains 'guide slug' $guide "get_page_by_path('destinations/best-islands-in-vietnam'"
Require-Contains 'guide title' $guide "'post_title'     => 'Best Islands in Vietnam'"
Require-Contains 'guide post name' $guide "'post_name'      => 'best-islands-in-vietnam'"

Require-Contains 'hero marker' $guide 'vg-best-islands-hero:v1'
Require-Contains 'concierge verdict marker' $guide 'vg-best-islands-concierge-verdict'
Require-Contains 'at a glance marker' $guide 'vg-best-islands-at-a-glance:v1'
Require-Contains 'photo grid marker' $guide 'vg-best-islands-photo-grid:v1'
Require-Contains 'source diversity marker' $guide 'vg-best-islands-source-diversity:v1'
Require-Contains 'decision matrix marker' $guide 'vg-best-islands-decision-matrix:v1'
Require-Contains 'route fit marker' $guide 'vg-best-islands-route-fit:v1'
Require-Contains 'season weather marker' $guide 'vg-best-islands-season-weather:v1'
Require-Contains 'logistics marker' $guide 'vg-best-islands-logistics:v1'
Require-Contains 'island profiles marker' $guide 'vg-best-islands-island-profiles:v1'
Require-Contains 'skip logic marker' $guide 'vg-best-islands-skip-logic:v1'
Require-Contains 'booking checks marker' $guide 'vg-best-islands-booking-checks:v1'
Require-Contains 'live checks marker' $guide 'vg-best-islands-live-checks:v1'
Require-Contains 'faq marker' $guide 'vg-best-islands-faq:v1'
Require-Contains 'proof panel' $guide 'vietnamguide/editorial-proof-panel'
Require-Contains 'related routes shortcode' $guide '[vg_related_routes]'
Require-Contains 'source trail' $guide 'vietnamguide/source-trail'
Require-Contains 'update log' $guide 'vietnamguide/update-log'

Require-Contains 'source Phu Quoc' $guide 'https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc'
Require-Contains 'source Con Dao' $guide 'https://vietnam.travel/places-to-go/southern-vietnam/con-dao'
Require-Contains 'source Phu Quy' $guide 'https://vietnam.travel/things-to-do/phu-quy-vietnam-island-destination'
Require-Contains 'source weather' $guide 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam'
Require-Contains 'source transport' $guide 'https://vietnam.travel/plan-your-trip/transport-within-vietnam'
Require-Contains 'source UNESCO Kien Giang' $guide 'https://www.unesco.org/en/mab/kien-giang'
Require-Contains 'source UNESCO Cat Ba' $guide 'https://www.unesco.org/en/mab/cat-ba'
Require-Contains 'source Con Dao National Park' $guide 'https://condaopark.com.vn/en'
Require-Contains 'source Cat Ba National Park' $guide 'http://catbanationalpark.vn/'
Require-Contains 'source Cat Ba tourism' $guide 'https://catba.com.vn/'
Require-Contains 'source Hoi An heritage Cham' $guide 'https://hoianheritage.net/en/news/news/Cu-Lao-Cham-Hoi-An-World-Biosphere-Reserve'
Require-Contains 'source Quang Ngai Ly Son' $guide 'https://quangngai.gov.vn/web/portal-qni/xem-chi-tiet/-/asset_publisher/Content/ly-son-island'
Require-Contains 'source Quang Ninh portal' $guide 'https://quangninh.gov.vn/'
Require-Contains 'source Sa Ky schedule' $guide 'https://cangsaky.com.vn/lich-tau'
Require-Contains 'source Phu Quy Express' $guide 'https://phuquyexpress.com/'
Require-Contains 'source Superdong' $guide 'https://superdong.com.vn/'
Require-Contains 'source Phu Quoc Express' $guide 'https://phuquocexpress.com/'

Require-Contains 'image Phu Quoc' $guide 'Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg'
Require-Contains 'image Con Dao' $guide 'Beach_view_from_Six_Senses_Resort'
Require-Contains 'image Cat Ba' $guide 'Cat_Ba_Island.jpg'
Require-Contains 'image Cham Islands' $guide 'Cu_Lao_Cham_Marine_Park'
Require-Contains 'image Ly Son' $guide 'Ly_Son3.jpg'
Require-Contains 'image Co To' $guide 'Alone_front_of_Co_To_beach'
Require-Contains 'image Phu Quy' $guide 'Chualinhsonphuquy.jpg'
Require-Contains 'image credit Phu Quoc' $guide 'Vivu Vietnam / CC BY-SA 4.0'
Require-Contains 'image credit Con Dao' $guide 'Daeva Trac / CC BY-SA 4.0'
Require-Contains 'image credit Cham' $guide 'Kok Leng Yeo / CC BY 2.0'
Require-Contains 'image credit Ly Son' $guide 'BertholdD / CC BY-SA 3.0'
Require-Contains 'image credit Co To' $guide 'Tuan Nguyen / CC BY-SA 3.0'
Require-Contains 'image credit Phu Quy' $guide 'Thai Nhi / Public domain'

Require-Contains 'related route self line' $guide 'Best Islands in Vietnam | /destinations/best-islands-in-vietnam/ |'
Require-Contains 'related route Best Beaches' $guide 'Best Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ |'
Require-Contains 'related route Cat Ba' $guide 'Cat Ba Travel Guide | /destinations/cat-ba-travel-guide/ |'
Require-Contains 'related route Ha Long' $guide 'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ |'
Require-Contains 'related route Ha Long/Lan Ha' $guide 'Ha Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ |'
Require-Contains 'related route Hoi An' $guide 'Best Things to Do in Hoi An | /destinations/best-things-to-do-in-hoi-an/ |'
Require-Contains 'related route Hoi An vs Hue' $guide 'Hoi An vs Hue | /compare/hoi-an-vs-hue/ |'
Require-Contains 'related route Da Nang' $guide 'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ |'
Require-Contains 'related route 21 days' $guide '21 Days in Vietnam | /itineraries/21-days-in-vietnam/ |'

Require-NotContains 'stale Best Beaches env' $guide 'VG_FORCE_BEST_BEACHES_GUIDE_REPUBLISH'
Require-NotContains 'stale Best Beaches marker' $guide 'vg-best-beaches-hero:v1'
Require-NotContains 'stale Cat Ba marker' $guide 'vg-cat-ba-hero:v1'
Require-NotContains 'stale Da Nang marker' $guide 'vg-da-nang-guide-hero:v1'

Require-Contains 'homepage Best Islands variable' $homepage '$best_islands_href'
Require-Contains 'homepage Best Islands required path' $homepage "vg_home_required_path('destinations/best-islands-in-vietnam')"
Require-Contains 'homepage Best Islands label' $homepage 'Best Islands in Vietnam'
Require-Contains 'homepage Best Islands href' $homepage 'href="{$best_islands_href}"'
Require-Contains 'homepage Best Islands planning row' $homepage 'I am choosing which Vietnam island actually fits the trip.'
Require-Contains 'homepage Best Islands route proof' $homepage 'Island time should be chosen by route job'
Require-Contains 'homepage Con Dao proof image' $homepage 'Beach_view_from_Six_Senses_Resort'

Require-Contains 'EEAT verifier Best Islands label' $verifier 'Best Islands in Vietnam guide'
Require-Contains 'EEAT verifier Best Islands path' $verifier 'destinations/best-islands-in-vietnam'
Require-Contains 'EEAT verifier Best Islands hero marker' $verifier 'vg-best-islands-hero:v1'
Require-Contains 'EEAT verifier Best Islands source diversity marker' $verifier 'vg-best-islands-source-diversity:v1'
Require-Contains 'EEAT verifier Best Islands FAQ marker' $verifier 'vg-best-islands-faq:v1'
Require-Contains 'EEAT verifier homepage link' $verifier 'href="/destinations/best-islands-in-vietnam/"'
Require-Contains 'EEAT verifier destinations hub note' $verifier 'vg-best-islands-destinations-hub-note:v1'

if ($failures.Count -gt 0) {
    $failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output 'Best Islands in Vietnam static checks passed.'

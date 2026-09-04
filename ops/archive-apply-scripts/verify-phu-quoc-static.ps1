$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$homepagePath = Join-Path $repoRoot 'ops/apply-homepage-premium.php'
$guidePath = Join-Path $repoRoot 'ops/apply-phu-quoc-travel-guide.php'
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

Require-Contains 'guide file exists' $normalizedGuidePath 'ops/apply-phu-quoc-travel-guide.php'
Require-Contains 'publish env' $guide 'VG_FORCE_PHU_QUOC_GUIDE_REPUBLISH'
Require-Contains 'repair env' $guide 'VG_REPAIR_PHU_QUOC_GUIDE_LINKS'
Require-Contains 'guide slug' $guide "get_page_by_path('destinations/phu-quoc-travel-guide'"
Require-Contains 'guide title' $guide "'post_title'     => 'Phu Quoc Travel Guide'"
Require-Contains 'guide post name' $guide "'post_name'      => 'phu-quoc-travel-guide'"

Require-Contains 'hero marker' $guide 'vg-phu-quoc-hero:v1'
Require-Contains 'concierge verdict marker' $guide 'vg-phu-quoc-concierge-verdict'
Require-Contains 'at a glance marker' $guide 'vg-phu-quoc-at-a-glance:v1'
Require-Contains 'photo grid marker' $guide 'vg-phu-quoc-photo-grid:v1'
Require-Contains 'source diversity marker' $guide 'vg-phu-quoc-source-diversity:v1'
Require-Contains 'route fit marker' $guide 'vg-phu-quoc-route-fit:v1'
Require-Contains 'where to stay marker' $guide 'vg-phu-quoc-where-to-stay:v1'
Require-Contains 'beach areas marker' $guide 'vg-phu-quoc-beach-areas:v1'
Require-Contains 'priority map marker' $guide 'vg-phu-quoc-priority-map:v1'
Require-Contains 'season weather marker' $guide 'vg-phu-quoc-season-weather:v1'
Require-Contains 'transport logistics marker' $guide 'vg-phu-quoc-transport-logistics:v1'
Require-Contains 'cost booking marker' $guide 'vg-phu-quoc-cost-booking:v1'
Require-Contains 'skip logic marker' $guide 'vg-phu-quoc-skip-logic:v1'
Require-Contains 'live checks marker' $guide 'vg-phu-quoc-live-checks:v1'
Require-Contains 'faq marker' $guide 'vg-phu-quoc-faq:v1'
Require-Contains 'proof panel' $guide 'vietnamguide/editorial-proof-panel'
Require-Contains 'related routes shortcode' $guide '[vg_related_routes]'
Require-Contains 'source trail' $guide 'vietnamguide/source-trail'
Require-Contains 'update log' $guide 'vietnamguide/update-log'

Require-Contains 'source Phu Quoc official tourism' $guide 'https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc'
Require-Contains 'source weather' $guide 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam'
Require-Contains 'source transport' $guide 'https://vietnam.travel/plan-your-trip/transport-within-vietnam'
Require-Contains 'source getting to Vietnam' $guide 'https://vietnam.travel/plan-your-trip/getting-vietnam'
Require-Contains 'source visa requirements' $guide 'https://vietnam.travel/plan-your-trip/visa-requirements'
Require-Contains 'source evisa official' $guide 'https://evisa.gov.vn/'
Require-Contains 'source UNESCO Kien Giang' $guide 'https://www.unesco.org/en/mab/kien-giang'
Require-Contains 'source NBCA Phu Quoc National Park' $guide 'https://en.nbca.gov.vn/vuon-quoc-gia-phu-quoc-kien-giang/'
Require-Contains 'source local government' $guide 'https://phuquoc.angiang.gov.vn/'
Require-Contains 'source An Giang development context' $guide 'https://angiang.gov.vn/en/phu-quoc-tourism-expects-surge-international-visitors-late-2025'
Require-Contains 'source NCHMF weather' $guide 'https://www.nchmf.gov.vn/kttv/en-US/1/index.html'
Require-Contains 'source Phu Quoc airport operator' $guide 'https://sunairport.com/phuquoc/vi'
Require-Contains 'source Phu Quoc Express Ha Tien' $guide 'https://phuquocexpress.com/lich-tau-tuyen-ha-tien-phu-quoc-2024'
Require-Contains 'source Phu Quoc Express Rach Gia' $guide 'https://phuquocexpress.com/lich-tau-tuyen-rach-gia-phu-quoc'
Require-Contains 'source Superdong Ha Tien' $guide 'https://superdong.com.vn/dich-vu/ha-tien-phu-quoc'
Require-Contains 'source Superdong Rach Gia' $guide 'https://superdong.com.vn/dich-vu/rach-gia-phu-quoc'
Require-Contains 'source Thanh Thoi Ferry' $guide 'https://thanhthoi.vn/'
Require-Contains 'source Sun World Hon Thom ticketing' $guide 'https://ticket.sunworld.vn/khu-vui-choi/hon-thom-nature-park/'
Require-Contains 'source VinWonders Phu Quoc' $guide 'https://vinwonders.com/en/vinwonders-phu-quoc/'

Require-Contains 'image Kem Beach' $guide 'Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg'
Require-Contains 'image Long Beach sunset' $guide 'Sunset_on_the_Long_Beach_in_Phu_Quoc_Island'
Require-Contains 'image Hon Thom cable car' $guide 'Hon_Thom_Cable_Car_aerial_view_Phu_Quoc_Island_Vietnam.jpg'
Require-Contains 'image An Thoi harbour' $guide 'An_Thoi_fishing_harbour_Sunset_Town_Sun_World_Phu_Quoc_Vietnam.jpg'
Require-Contains 'image Bai Sao' $guide 'Bai-sao-phu-quoc-tuonglamphotos.jpg'
Require-Contains 'image Star Beach' $guide 'Star_Beach_%28B%C3%A3i_Sao%29.jpg'
Require-Contains 'image United Center' $guide 'Festival_in_Phu_Quoc_United_Center.jpg'
Require-Contains 'image credit Vivu Vietnam' $guide 'Vivu Vietnam / CC BY-SA 4.0'
Require-Contains 'image credit Alexey Komarov' $guide 'Alexey Komarov / CC BY-SA 4.0'
Require-Contains 'image credit Trantuonglam' $guide 'Trantuonglam / CC BY-SA 4.0'
Require-Contains 'image credit Vnecofriendly' $guide 'Vnecofriendly / CC BY-SA 4.0'
Require-Contains 'image credit Reb.vn' $guide 'Reb.vn / CC BY-SA 4.0'

Require-Contains 'related route self line' $guide 'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ |'
Require-Contains 'related route Best Islands' $guide 'Best Islands in Vietnam | /destinations/best-islands-in-vietnam/ |'
Require-Contains 'related route Best Beaches' $guide 'Best Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ |'
Require-Contains 'related route evisa' $guide 'Vietnam E-Visa | /plan/vietnam-evisa/ |'
Require-Contains 'related route 21 days' $guide '21 Days in Vietnam | /itineraries/21-days-in-vietnam/ |'
Require-Contains 'related route SIM/eSIM' $guide 'SIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ |'

Require-NotContains 'stale Best Islands env' $guide 'VG_FORCE_BEST_ISLANDS_GUIDE_REPUBLISH'
Require-NotContains 'stale Best Beaches marker' $guide 'vg-best-beaches-hero:v1'
Require-NotContains 'stale Cat Ba marker' $guide 'vg-cat-ba-hero:v1'
Require-NotContains 'stale Da Nang marker' $guide 'vg-da-nang-guide-hero:v1'

Require-Contains 'homepage Phu Quoc variable' $homepage '$phu_quoc_guide_href'
Require-Contains 'homepage Phu Quoc required path' $homepage "vg_home_required_path('destinations/phu-quoc-travel-guide')"
Require-Contains 'homepage Phu Quoc label' $homepage 'Phu Quoc Travel Guide'
Require-Contains 'homepage Phu Quoc href' $homepage 'href="{$phu_quoc_guide_href}"'
Require-Contains 'homepage Phu Quoc planning row' $homepage 'I need a Phu Quoc decision, not just a beach name.'
Require-Contains 'homepage Phu Quoc route verdict' $homepage 'Use Phu Quoc when the route needs winter sun, resort ease, or a southern recovery chapter.'

Require-Contains 'EEAT verifier Phu Quoc label' $verifier 'Phu Quoc Travel Guide'
Require-Contains 'EEAT verifier Phu Quoc path' $verifier 'destinations/phu-quoc-travel-guide'
Require-Contains 'EEAT verifier Phu Quoc hero marker' $verifier 'vg-phu-quoc-hero:v1'
Require-Contains 'EEAT verifier Phu Quoc source diversity marker' $verifier 'vg-phu-quoc-source-diversity:v1'
Require-Contains 'EEAT verifier Phu Quoc FAQ marker' $verifier 'vg-phu-quoc-faq:v1'
Require-Contains 'EEAT verifier homepage link' $verifier 'href="/destinations/phu-quoc-travel-guide/"'
Require-Contains 'EEAT verifier destinations hub note' $verifier 'vg-phu-quoc-destinations-hub-note:v1'

if ($failures.Count -gt 0) {
    $failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output 'Phu Quoc Travel Guide static checks passed.'

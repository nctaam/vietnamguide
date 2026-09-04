$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$homepagePath = Join-Path $repoRoot 'ops/apply-homepage-premium.php'
$guidePath = Join-Path $repoRoot 'ops/apply-quy-nhon-travel-guide.php'
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

Require-Contains 'guide file exists' $normalizedGuidePath 'ops/apply-quy-nhon-travel-guide.php'
Require-Contains 'publish env' $guide 'VG_FORCE_QUY_NHON_GUIDE_REPUBLISH'
Require-Contains 'repair env' $guide 'VG_REPAIR_QUY_NHON_GUIDE_LINKS'
Require-Contains 'guide slug' $guide "get_page_by_path('destinations/quy-nhon-travel-guide'"
Require-Contains 'guide title' $guide "'post_title'     => 'Quy Nhon Travel Guide'"
Require-Contains 'guide post name' $guide "'post_name'      => 'quy-nhon-travel-guide'"

Require-Contains 'hero marker' $guide 'vg-quy-nhon-hero:v1'
Require-Contains 'concierge verdict marker' $guide 'vg-quy-nhon-concierge-verdict'
Require-Contains 'proof panel' $guide 'vietnamguide/editorial-proof-panel'
Require-Contains 'at a glance marker' $guide 'vg-quy-nhon-at-a-glance:v1'
Require-Contains 'photo grid marker' $guide 'vg-quy-nhon-photo-grid:v1'
Require-Contains 'source diversity marker' $guide 'vg-quy-nhon-source-diversity:v1'
Require-Contains 'route fit marker' $guide 'vg-quy-nhon-route-fit:v1'
Require-Contains 'where to stay marker' $guide 'vg-quy-nhon-where-to-stay:v1'
Require-Contains 'priority map marker' $guide 'vg-quy-nhon-priority-map:v1'
Require-Contains 'season weather marker' $guide 'vg-quy-nhon-season-weather:v1'
Require-Contains 'transport logistics marker' $guide 'vg-quy-nhon-transport-logistics:v1'
Require-Contains 'cost booking marker' $guide 'vg-quy-nhon-cost-booking:v1'
Require-Contains 'skip logic marker' $guide 'vg-quy-nhon-skip-logic:v1'
Require-Contains 'live checks marker' $guide 'vg-quy-nhon-live-checks:v1'
Require-Contains 'faq marker' $guide 'vg-quy-nhon-faq:v1'
Require-Contains 'related routes shortcode' $guide '[vg_related_routes]'
Require-Contains 'source trail' $guide 'vietnamguide/source-trail'
Require-Contains 'update log' $guide 'vietnamguide/update-log'

Require-Contains 'source Vietnam.travel Quy Nhon' $guide 'https://vietnam.travel/node/1453'
Require-Contains 'source official introduction' $guide 'https://dulichquynhon.binhdinh.gov.vn/en/introduction'
Require-Contains 'source Ky Co official' $guide 'https://dulichquynhon.binhdinh.gov.vn/en/baikyco'
Require-Contains 'source Eo Gio official' $guide 'https://dulichquynhon.binhdinh.gov.vn/en/eogiolandscape'
Require-Contains 'source Quy Nhon beach official' $guide 'https://dulichquynhon.binhdinh.gov.vn/en/bienquynhon'
Require-Contains 'source Thap Doi official' $guide 'https://dulichquynhon.binhdinh.gov.vn/vi/thapdoiquynhon'
Require-Contains 'source Phu Cat ACV' $guide 'https://acv.vn/en/uih'
Require-Contains 'source weather' $guide 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam'
Require-Contains 'source transport' $guide 'https://vietnam.travel/plan-your-trip/transport-within-vietnam'
Require-Contains 'source Vietnam Railways' $guide 'https://dsvn.vn/'
Require-Contains 'source evisa official' $guide 'https://evisa.gov.vn/'
Require-Contains 'source NCHMF weather' $guide 'https://www.nchmf.gov.vn/kttv/en-US/1/index.html'

Require-Contains 'image Ky Co hero' $guide 'Ky_Co_-_Nhon_Ly_-_Quy_Nhon_-_Binh_Dinh_-_Viet_Nam.jpg'
Require-Contains 'image Ky Co secondary' $guide 'Ky_Co_beach%2C_Quy_Nhon_city%2C_Binh_Dinh_province%2C_Vietnam.jpg'
Require-Contains 'image Eo Gio' $guide 'Eo_Gi%C3%B3_-_Nh%C6%A1n_L%C3%BD.jpg'
Require-Contains 'image promenade' $guide 'Quy_Nhon_Beach_Promenade.jpg'
Require-Contains 'image Thap Doi' $guide '0040323_Thap_Doi_Cham_Hindu_complex%2C_Quy_Nhon%2C_Binh_Dinh_Vietnam_185.jpg'
Require-Contains 'image Thi Nai' $guide 'Qui_Nhon_and_Thi_Nai_Bridge_2007-10-03.jpg'
Require-Contains 'image Phu Cat' $guide 'PhuCatAirport_newterminal.jpg'
Require-Contains 'image credit Boconganh' $guide 'Boconganh Phan / Public domain'
Require-Contains 'image credit Le Ho Bac' $guide 'Le Ho Bac / CC BY-SA 4.0'
Require-Contains 'image credit Hung Ho Ba' $guide 'Hung Ho Ba / CC BY 2.0'
Require-Contains 'image credit VeeWin' $guide 'VeeWin / CC BY-SA 4.0'
Require-Contains 'image credit Ms Sarah Welch' $guide 'Ms Sarah Welch / CC0'
Require-Contains 'image credit Swaminathan' $guide 'Swaminathan / Teofilo / CC BY 2.0'
Require-Contains 'image credit Uranus2808' $guide 'Uranus2808 / CC BY-SA 4.0'

Require-Contains 'related route self line' $guide 'Quy Nhon Travel Guide | /destinations/quy-nhon-travel-guide/ |'
Require-Contains 'related route Best Beaches' $guide 'Best Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ |'
Require-Contains 'related route Nha Trang' $guide 'Nha Trang Travel Guide | /destinations/nha-trang-travel-guide/ |'
Require-Contains 'related route Mui Ne vs Nha Trang' $guide 'Mui Ne vs Nha Trang | /compare/mui-ne-vs-nha-trang/ |'
Require-Contains 'related route Da Nang' $guide 'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ |'
Require-Contains 'related route cost' $guide 'Vietnam Travel Cost | /costs/vietnam-travel-cost/ |'
Require-Contains 'related route transport' $guide 'Transport Within Vietnam | /plan/transport-within-vietnam/ |'
Require-Contains 'related route money' $guide 'Money in Vietnam | /plan/money-cash-cards-atms/ |'
Require-Contains 'related route SIM' $guide 'SIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ |'

Require-NotContains 'stale tourism.gov.vn post 7646' $guide 'vietnamtourism.gov.vn/en/post/7646'
Require-NotContains 'unsafe hidden gem claim' $guide 'hidden gem'
Require-NotContains 'unsafe pristine claim' $guide 'pristine'
Require-NotContains 'guaranteed weather claim' $guide 'guaranteed'

Require-Contains 'homepage Quy Nhon variable' $homepage '$quy_nhon_guide_href'
Require-Contains 'homepage Quy Nhon fallback path' $homepage "vg_home_path('destinations/quy-nhon-travel-guide', 'destinations')"
Require-Contains 'homepage Quy Nhon planning row' $homepage 'I need to know whether Quy Nhon is the quieter mainland coast answer.'
Require-Contains 'homepage Quy Nhon route verdict' $homepage 'Use Quy Nhon when the coast should be quieter without becoming remote.'
Require-Contains 'homepage Quy Nhon related route' $homepage 'Audit Quy Nhon'
Require-Contains 'homepage Quy Nhon guide shelf' $homepage 'Quy Nhon Travel Guide'

Require-Contains 'EEAT verifier label' $verifier 'Quy Nhon Travel Guide'
Require-Contains 'EEAT verifier path' $verifier 'destinations/quy-nhon-travel-guide'
Require-Contains 'EEAT verifier hero marker' $verifier 'vg-quy-nhon-hero:v1'
Require-Contains 'EEAT verifier source diversity marker' $verifier 'vg-quy-nhon-source-diversity:v1'
Require-Contains 'EEAT verifier FAQ marker' $verifier 'vg-quy-nhon-faq:v1'
Require-Contains 'EEAT verifier related route meta' $verifier '/destinations/quy-nhon-travel-guide/'
Require-Contains 'EEAT verifier visible source all-of' $verifier 'visible Quy Nhon official sources'
Require-Contains 'EEAT verifier visible image all-of' $verifier 'visible Quy Nhon image credits'

if ($failures.Count -gt 0) {
    $failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output 'Quy Nhon static checks passed.'

$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$homepagePath = Join-Path $repoRoot 'ops/apply-homepage-premium.php'
$guidePath = Join-Path $repoRoot 'ops/apply-14-days-itinerary-guide.php'
$verifierPath = Join-Path $repoRoot 'ops/verify-eeat-content.php'

$guide = if (Test-Path -LiteralPath $guidePath) { Get-Content -Raw -Path $guidePath } else { '' }
$homepage = Get-Content -Raw -Path $homepagePath
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

Require-Contains 'publish env' $guide 'VG_FORCE_14_DAY_ITINERARY_REPUBLISH'
Require-Contains 'guide slug' $guide "get_page_by_path('itineraries/14-days-in-vietnam'"
Require-Contains 'guide title' $guide "'post_title'     => '14 Days in Vietnam'"
Require-Contains 'guide post name' $guide "'post_name'      => '14-days-in-vietnam'"
Require-Contains 'required marker helper' $guide 'vg_ops_assert_required_content_markers'

Require-Contains 'H1 upgraded' $guide '14 Days in Vietnam: Best Two-Week Route by Month and Travel Style'
Require-Contains 'rank math title upgraded' $guide '14 Days in Vietnam Itinerary: Best Two-Week Route'
Require-Contains 'rank math description upgraded' $guide 'Plan 14 days in Vietnam by month, route shape, and travel style'
Require-Contains 'excerpt upgraded' $guide 'two-week route chooser'
Require-Contains 'last meaningful update' $guide "vg_eeat_last_meaningful_update', 'July 25, 2026'"
Require-Contains 'update summary hardened' $guide 'Hardened the 14-day itinerary for GSC opportunity'

Require-Contains 'hero marker' $guide 'vg-itinerary-14day-hero:v1'
Require-Contains 'concierge verdict marker' $guide 'vg-itinerary-14day-concierge-verdict'
Require-Contains 'proof panel' $guide 'vietnamguide/editorial-proof-panel'
Require-Contains 'at a glance marker' $guide 'vg-itinerary-14day-at-a-glance:v1'
Require-Contains 'source diversity marker' $guide 'vg-itinerary-14day-source-diversity:v1'
Require-Contains 'source trail snapshot marker' $guide 'vg-itinerary-14day-source-trail-snapshot:v1'
Require-Contains 'route chooser marker' $guide 'vg-itinerary-14day-route-chooser:v1'
Require-Contains 'photo grid marker' $guide 'vg-itinerary-14day-photo-grid:v1'
Require-Contains 'route builder marker' $guide 'vg-itinerary-14day-route-builder'
Require-Contains 'night allocation marker' $guide 'vg-itinerary-14day-night-allocation'
Require-Contains 'extra days value marker' $guide 'vg-itinerary-14day-extra-days-value'
Require-Contains 'pacing map marker' $guide 'vg-itinerary-14day-pacing-map'
Require-Contains 'transfer pressure marker' $guide 'vg-itinerary-14day-transfer-pressure'
Require-Contains 'route variants marker' $guide 'vg-itinerary-14day-route-variants'
Require-Contains 'extension matrix marker' $guide 'vg-itinerary-14day-extension-matrix'
Require-Contains 'base strategy marker' $guide 'vg-itinerary-14day-base-strategy'
Require-Contains 'season pivots marker' $guide 'vg-itinerary-14day-season-pivots'
Require-Contains 'slowdown rules marker' $guide 'vg-itinerary-14day-slowdown-rules'
Require-Contains 'audience adaptations marker' $guide 'vg-itinerary-14day-audience-adaptations'
Require-Contains 'prebook flex marker' $guide 'vg-itinerary-14day-prebook-flex'
Require-Contains 'booking sequence marker' $guide 'vg-itinerary-14day-booking-sequence'
Require-Contains 'planning audit marker' $guide 'vg-itinerary-14day-planning-audit'
Require-Contains 'FAQ marker' $guide 'vg-itinerary-14day-faq'
Require-Contains 'related routes shortcode' $guide '[vg_related_routes]'
Require-Contains 'source trail pattern' $guide 'vietnamguide/source-trail'
Require-Contains 'update log pattern' $guide 'vietnamguide/update-log'

Require-Contains 'CTR answer phrase' $guide 'If you only need one safe first-trip answer'
Require-Contains 'anti spam phrase' $guide 'This is not a maximum-coverage itinerary.'
Require-Contains 'source limitation phrase' $guide 'Sources can confirm official destination context, broad weather and transport patterns, heritage status, rail and entry references, and image-license records; they cannot decide'
Require-Contains 'source snapshot title' $guide 'Source trail snapshot for this two-week route'
Require-Contains 'text-only image credits note' $guide 'Image credits are listed as text to keep the itinerary readable and reduce visible outbound clutter.'
Require-Contains 'route chooser family phrase' $guide 'Family-friendly lower friction'
Require-Contains 'route chooser premium phrase' $guide 'Premium or honeymoon pace'
Require-Contains 'route chooser beach phrase' $guide 'Beach recovery inside a wider trip'

Require-Contains 'source planning' $guide 'https://vietnam.travel/plan-your-trip'
Require-Contains 'source weather' $guide 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam'
Require-Contains 'source transport' $guide 'https://vietnam.travel/plan-your-trip/transport-within-vietnam'
Require-Contains 'source UNESCO Ha Long Cat Ba' $guide 'https://whc.unesco.org/en/list/672/'
Require-Contains 'source UNESCO Hoi An' $guide 'https://whc.unesco.org/en/list/948/'
Require-Contains 'source UNESCO Hue' $guide 'https://whc.unesco.org/en/list/678/'
Require-Contains 'source UNESCO Trang An' $guide 'https://whc.unesco.org/en/list/1438/'
Require-Contains 'source rail' $guide 'https://dsvn.vn/'
Require-Contains 'source evisa' $guide 'https://evisa.gov.vn/e-visa/foreigners'

Require-Contains 'image Hanoi' $guide 'Hanoi-lac-hoan-kiem.jpg'
Require-Contains 'image Trang An' $guide 'Trang_An_Landscape_Complex'
Require-Contains 'image Hue' $guide 'Hue_Vietnam_Citadel'
Require-Contains 'image Hai Van' $guide 'Vietnam%2C_Hai-Van-Pass.jpg'
Require-Contains 'image Hoi An' $guide 'H%E1%BB%99i_An%2C_Ancient_Town'
Require-Contains 'image Ho Chi Minh City' $guide 'Ho_Chi_Minh_City%2C_City_Hall'
Require-Contains 'image Mekong' $guide 'Mekong_Delta%2C_River'
Require-Contains 'image Son River' $guide 'Son_River-Quang_Binh_province.jpg'
Require-Contains 'text credit hero' $guide 'Hero image: Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0.'
Require-Contains 'text credit Hanoi' $guide 'Alex 69200 vx, CC BY-SA 4.0'
Require-Contains 'text credit Trang An' $guide 'Jakub Halun, CC BY 4.0'
Require-Contains 'text credit Hue' $guide 'CEphoto, Uwe Aranas, CC BY-SA 3.0'
Require-Contains 'text credit Hai Van' $guide 'Wolkenkratzer, CC BY-SA 4.0'
Require-Contains 'text credit Hoi An/HCMC' $guide 'Steffen Schmitz, CC BY-SA 4.0'
Require-Contains 'text credit Mekong' $guide 'Vyacheslav Argenberg, CC BY 4.0'
Require-Contains 'text credit Son River' $guide 'BacLuong, CC BY-SA 4.0'

Require-Contains 'related route 7 days' $guide '7 Days in Vietnam | /itineraries/7-days-in-vietnam/ |'
Require-Contains 'related route 10 days' $guide '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ |'
Require-Contains 'related route 21 days' $guide '21 Days in Vietnam | /itineraries/21-days-in-vietnam/ |'
Require-Contains 'related route Best Time' $guide 'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ |'
Require-Contains 'related route Best Beaches' $guide 'Best Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ |'
Require-Contains 'related route Da Nang' $guide 'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ |'
Require-Contains 'related route Da Nang vs Hoi An' $guide 'Da Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ |'

Require-NotContains 'old review date' $guide 'Updated July 16, 2026'
Require-NotContains 'old linked hero credit' $guide 'class="vg-image-credit" href="https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg"'
Require-NotContains 'old linked Hanoi credit' $guide '<a href="{$hanoi_credit_url}"'
Require-NotContains 'old linked Trang An credit' $guide '<a href="{$trang_an_credit_url}"'
Require-NotContains 'old linked Hue credit' $guide '<a href="{$hue_credit_url}"'
Require-NotContains 'old linked Hai Van credit' $guide '<a href="{$hai_van_credit_url}"'
Require-NotContains 'old linked Hoi An credit' $guide '<a href="{$hoi_an_credit_url}"'
Require-NotContains 'old linked HCMC credit' $guide '<a href="{$hcmc_credit_url}"'
Require-NotContains 'old linked Mekong credit' $guide '<a href="{$mekong_credit_url}"'
Require-NotContains 'old linked Son River credit' $guide '<a href="{$son_river_credit_url}"'

Require-Contains 'homepage 14-day variable' $homepage '$fourteen_days_href'
Require-Contains 'homepage 14-day required path' $homepage "vg_home_required_path('itineraries/14-days-in-vietnam')"
Require-Contains 'homepage 14-day label' $homepage '14 Days in Vietnam'
Require-Contains 'homepage 14-day href' $homepage 'href="{$fourteen_days_href}"'
Require-Contains 'homepage text hero credit' $homepage 'Hero image: Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0.'
Require-Contains 'homepage text Hanoi credit' $homepage 'Image: Alex 69200 vx, CC BY-SA 4.0.'
Require-Contains 'homepage text Lan Ha credit' $homepage 'Image: Saaremees, CC BY-SA 4.0.'
Require-Contains 'homepage text Phu Quoc credit' $homepage 'Image: Vivu Vietnam, CC BY-SA 4.0.'
Require-Contains 'homepage text HCMC credit' $homepage 'Image: Steffen Schmitz, CC BY-SA 4.0.'
Require-NotContains 'homepage linked Wikimedia credit anchors' $homepage 'rel="license noopener"'
Require-NotContains 'homepage old hero credit anchor' $homepage 'class="vg-image-credit" href="https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg"'
Require-NotContains 'homepage old dynamic credit anchor' $homepage '<a href="{$hanoi_credit_url}"'

Require-Contains 'EEAT verifier 14-day label' $verifier '14 Days itinerary guide'
Require-Contains 'EEAT verifier 14-day path' $verifier 'itineraries/14-days-in-vietnam'
Require-Contains 'EEAT verifier hero marker' $verifier 'vg-itinerary-14day-hero:v1'
Require-Contains 'EEAT verifier at a glance marker' $verifier 'vg-itinerary-14day-at-a-glance:v1'
Require-Contains 'EEAT verifier source diversity marker' $verifier 'vg-itinerary-14day-source-diversity:v1'
Require-Contains 'EEAT verifier source snapshot marker' $verifier 'vg-itinerary-14day-source-trail-snapshot:v1'
Require-Contains 'EEAT verifier route chooser marker' $verifier 'vg-itinerary-14day-route-chooser:v1'

if ($failures.Count -gt 0) {
    $failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output '14 Days in Vietnam static checks passed.'

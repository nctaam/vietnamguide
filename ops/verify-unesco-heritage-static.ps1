$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$guidePath = Join-Path $repoRoot 'ops/apply-unesco-heritage-sites-guide.php'
$homepagePath = Join-Path $repoRoot 'ops/apply-homepage-premium.php'
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

Require-Contains 'guide file exists' $guide 'ops/apply-unesco-heritage-sites-guide.php'
Require-Contains 'guide force flag' $guide 'VG_FORCE_HERITAGE_GUIDE_REPUBLISH'
Require-Contains 'required marker helper' $guide 'vg_ops_assert_required_content_markers'
Require-Contains 'guide path' $guide "get_page_by_path('destinations/unesco-heritage-sites-vietnam'"
Require-Contains 'post title' $guide "'post_title'     => 'UNESCO Heritage Sites in Vietnam'"
Require-Contains 'post name' $guide "'post_name'      => 'unesco-heritage-sites-vietnam'"
Require-Contains 'destinations hub marker' $guide 'vg-unesco-heritage-destinations-hub-note:v1'

Require-Contains 'H1 upgraded' $guide 'UNESCO Heritage Sites in Vietnam: Which Ones Belong in Your Route'
Require-Contains 'Rank Math title upgraded' $guide 'UNESCO Heritage Sites in Vietnam: 2025 Route Guide'
Require-Contains 'Rank Math description upgraded' $guide 'Choose which UNESCO World Heritage Sites in Vietnam fit your route'
Require-Contains 'review date upgraded' $guide "vg_eeat_last_meaningful_update', 'July 25, 2026'"
Require-Contains 'update summary GSC' $guide 'Hardened the UNESCO Heritage Sites guide for GSC CTR opportunity'

Require-Contains 'hero marker' $guide 'vg-unesco-heritage-hero:v1'
Require-Contains 'concierge verdict marker' $guide 'vg-unesco-heritage-concierge-verdict'
Require-Contains 'proof panel' $guide 'vietnamguide/editorial-proof-panel'
Require-Contains 'at a glance marker' $guide 'vg-unesco-heritage-at-a-glance:v1'
Require-Contains 'source diversity marker' $guide 'vg-unesco-heritage-source-diversity:v1'
Require-Contains 'source trail snapshot marker' $guide 'vg-unesco-heritage-source-trail-snapshot:v1'
Require-Contains '2025 updates marker' $guide 'vg-unesco-heritage-2025-updates:v1'
Require-Contains 'route chooser marker' $guide 'vg-unesco-heritage-route-chooser:v1'
Require-Contains 'shortlist marker' $guide 'vg-unesco-heritage-shortlist:v1'
Require-Contains 'itinerary length marker' $guide 'vg-unesco-heritage-itinerary-length:v1'
Require-Contains 'site-by-site marker' $guide 'vg-unesco-heritage-site-by-site:v1'
Require-Contains 'photo grid marker' $guide 'vg-unesco-heritage-photo-grid:v1'
Require-Contains 'skip logic marker' $guide 'vg-unesco-heritage-skip-logic:v1'
Require-Contains 'live checks marker' $guide 'vg-unesco-heritage-live-checks:v1'
Require-Contains 'FAQ marker' $guide 'vg-unesco-heritage-faq:v1'
Require-Contains 'related routes shortcode' $guide '[vg_related_routes]'
Require-Contains 'source trail pattern' $guide 'vietnamguide/source-trail'
Require-Contains 'update log pattern' $guide 'vietnamguide/update-log'

Require-Contains '9 properties phrase' $guide '9 UNESCO World Heritage properties'
Require-Contains 'category phrase' $guide '6 cultural, 2 natural, and 1 mixed'
Require-Contains 'Yen Tu 2025 phrase' $guide 'Yen Tu - Vinh Nghiem - Con Son, Kiep Bac was inscribed in 2025'
Require-Contains 'Phong Nha 2025 phrase' $guide 'Phong Nha-Ke Bang National Park and Hin Nam No National Park is a 2025 transboundary update'
Require-Contains 'not tenth warning' $guide 'Do not count the Phong Nha-Hin Nam No update as a 10th Vietnam property.'
Require-Contains 'anti spam phrase' $guide 'This is not a trophy-wall list.'
Require-Contains 'source limitation phrase' $guide 'Sources can confirm official World Heritage status, categories, site names, inscription decisions, broad destination context, and image-license records; they cannot decide your pace, transfer tolerance, heat limit, children, mobility, cruise risk, or whether one more heritage stop makes the route better.'
Require-Contains 'first trip default phrase' $guide 'If this is your first Vietnam trip, start with Trang An, Ha Long Bay - Cat Ba Archipelago, Hue, Hoi An, and My Son only when central Vietnam has a protected half-day.'
Require-Contains 'Yen Tu route warning' $guide 'Treat Yen Tu as a spread-out northern cultural route, not one compact attraction.'

Require-Contains 'source UNESCO country' $guide 'https://whc.unesco.org/en/statesparties/vn'
Require-Contains 'source Yen Tu property' $guide 'https://whc.unesco.org/en/list/1732/'
Require-Contains 'source Yen Tu decision' $guide 'https://whc.unesco.org/en/decisions/8956/'
Require-Contains 'source Phong Nha property' $guide 'https://whc.unesco.org/en/list/951/'
Require-Contains 'source Phong Nha decision' $guide 'https://whc.unesco.org/en/decisions/8942/'
Require-Contains 'source Vietnam.travel Yen Tu' $guide 'vietnam.travel/things-to-do/vietnam'
Require-Contains 'source Phong Nha official' $guide 'https://phongnhakebang.vn/'
Require-Contains 'source Son Doong official' $guide 'https://sondoongcave.info/'

Require-Contains 'image Ha Long' $guide 'Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg'
Require-Contains 'image Trang An' $guide 'Trang_An_Landscape_Complex'
Require-Contains 'image Thang Long' $guide 'Doan_Mon_Gate_1.jpg'
Require-Contains 'image Ho Dynasty' $guide 'Ho_dynasty%27s_citadel'
Require-Contains 'image Hue' $guide 'Hue_Vietnam_Citadel'
Require-Contains 'image Hoi An' $guide 'H%E1%BB%99i_An%2C_Ancient_Town'
Require-Contains 'image My Son' $guide 'My_Son_Sanctuary_Vietnam_06.jpg'
Require-Contains 'image Phong Nha' $guide 'Son_River-Quang_Binh_province.jpg'
Require-Contains 'image Yen Tu' $guide 'Chua-yen-tu-ngay-nay.jpg'
Require-Contains 'text-only credit policy' $guide 'Image credits are listed as text to keep the heritage decision guide readable and reduce visible outbound clutter.'
Require-Contains 'hero credit text' $guide 'Hero image: Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0.'
Require-Contains 'Thang Long credit text' $guide 'Christophe95, CC BY-SA 4.0'
Require-Contains 'Ho Dynasty credit text' $guide 'Loi Nguyen Duc, CC BY 2.0'
Require-Contains 'My Son credit text' $guide 'Philip Nalangan, CC BY 4.0'
Require-Contains 'Yen Tu credit text' $guide 'Bach Giang Nguyen, CC BY-SA 4.0'
Require-NotContains 'no external image credit anchors' $guide 'rel="license noopener"'
Require-NotContains 'no visible UNESCO source anchor' $guide '<a href="https://whc.unesco.org'
Require-NotContains 'no visible Vietnam.travel source anchor' $guide '<a href="https://vietnam.travel'

Require-Contains 'related route Hanoi travel guide' $guide 'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ |'
Require-Contains 'related route Ninh Binh' $guide 'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ |'
Require-Contains 'related route Ha Long' $guide 'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ |'
Require-Contains 'related route Hoi An Hue' $guide 'Hoi An vs Hue | /compare/hoi-an-vs-hue/ |'
Require-Contains 'related route 21 days' $guide '21 Days in Vietnam | /itineraries/21-days-in-vietnam/ |'

Require-Contains 'homepage required path' $homepage "`$heritage_guide_href = vg_home_required_path('destinations/unesco-heritage-sites-vietnam');"
Require-Contains 'homepage heritage href variable' $homepage 'href="{$heritage_guide_href}"'
Require-Contains 'homepage heritage guide row' $homepage 'UNESCO Heritage Sites in Vietnam'

Require-Contains 'EEAT verifier H1 expected' $verifier 'UNESCO Heritage Sites in Vietnam: Which Ones Belong in Your Route'
Require-Contains 'EEAT verifier at a glance marker' $verifier 'vg-unesco-heritage-at-a-glance:v1'
Require-Contains 'EEAT verifier source diversity marker' $verifier 'vg-unesco-heritage-source-diversity:v1'
Require-Contains 'EEAT verifier source trail snapshot marker' $verifier 'vg-unesco-heritage-source-trail-snapshot:v1'
Require-Contains 'EEAT verifier 2025 marker' $verifier 'vg-unesco-heritage-2025-updates:v1'
Require-Contains 'EEAT verifier external budget' $verifier 'vg_verify_eeat_require_visible_external_body_link_budget('
Require-Contains 'EEAT verifier rendered source snapshot' $verifier 'rendered UNESCO Heritage source trail snapshot'
Require-Contains 'EEAT verifier own related route metadata' $verifier "vg_verify_eeat_require_own_related_route_meta(`n        `$heritage_guide_page"

$visibleExternalHrefs = Get-VisibleExternalHrefs $guide
if ($visibleExternalHrefs.Count -gt 2) {
    $script:failures.Add("visible external body-link budget should be <=2; found $($visibleExternalHrefs.Count): $($visibleExternalHrefs -join ' | ')")
}

if ($failures.Count -gt 0) {
    $failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output 'UNESCO Heritage Sites static checks passed.'

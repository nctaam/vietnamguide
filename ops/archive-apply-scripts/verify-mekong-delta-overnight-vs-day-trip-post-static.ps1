$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$staticPath = $MyInvocation.MyCommand.Path
$applyPath = Join-Path $repoRoot 'ops/apply-mekong-delta-overnight-vs-day-trip-post.php'
$verifyPath = Join-Path $repoRoot 'ops/verify-mekong-delta-overnight-vs-day-trip-post.php'
$batchVerifyPath = Join-Path $repoRoot 'ops/verify-wordpress-post-editorial-system-batch-2.php'

function Require-File {
    param([string] $Label, [string] $Path)

    if (-not (Test-Path -LiteralPath $Path)) {
        throw "$Label missing: $Path"
    }
}

function Require-Contains {
    param([string] $Label, [string] $Text, [string] $Needle)

    if (-not $Text.Contains($Needle)) {
        throw "$Label missing required text: $Needle"
    }
}

function Require-NotContains {
    param([string] $Label, [string] $Text, [string] $Needle)

    if ($Text.Contains($Needle)) {
        throw "$Label should not contain: $Needle"
    }
}

function Get-VisibleExternalHrefs {
    param([string] $Content)

    $matches = [regex]::Matches($Content, '\bhref\s*=\s*(["''])(.*?)\1', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    $hrefs = New-Object System.Collections.Generic.List[string]

    foreach ($match in $matches) {
        $href = $match.Groups[2].Value.Trim()

        if ($href -eq '' -or $href.StartsWith('#') -or $href -match '^(mailto|tel):') {
            continue
        }

        if ($href.StartsWith('//')) {
            $hrefs.Add($href)
            continue
        }

        if ($href.StartsWith('/')) {
            continue
        }

        if ($href -notmatch '^https?://') {
            continue
        }

        $hrefs.Add($href)
    }

    return $hrefs
}

Require-File 'Mekong Delta Overnight static verifier' $staticPath
Require-File 'Mekong Delta Overnight apply script' $applyPath
Require-File 'Mekong Delta Overnight runtime verifier' $verifyPath
Require-File 'Batch 2 runtime verifier' $batchVerifyPath

$apply = Get-Content -Raw -LiteralPath $applyPath
$verify = Get-Content -Raw -LiteralPath $verifyPath
$batchVerify = Get-Content -Raw -LiteralPath $batchVerifyPath

Require-Contains 'Apply script requires override' $apply 'VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE'
Require-Contains 'Apply script requires exact override value' $apply "`$value === '1'"
Require-Contains 'Apply script requires unique slug match' $apply 'count($posts) !== 1'
Require-Contains 'Apply script targets expected slug' $apply 'mekong-delta-overnight-vs-day-trip'
Require-Contains 'Apply script guards exact post ID' $apply '$post_id !== 502'
Require-Contains 'Runtime verifier guards exact post ID' $verify '$post_id !== 502'
Require-Contains 'Runtime verifier guards exact title' $verify "post_title !== 'Mekong Delta Overnight vs Day Trip: Which Is Worth It?'"
Require-Contains 'Runtime verifier guards exact slug' $verify "post_name !== 'mekong-delta-overnight-vs-day-trip'"
Require-Contains 'Apply script finds native posts only' $apply "'post_type' => 'post'"
Require-Contains 'Apply script refuses missing post' $apply 'Required draft post not found'
Require-Contains 'Apply script refuses non-draft post' $apply 'because it is not draft'
Require-Contains 'Apply script keeps draft status' $apply "'post_status' => 'draft'"
Require-Contains 'Apply script closes comments' $apply "'comment_status' => 'closed'"
Require-Contains 'Apply script closes pings' $apply "'ping_status' => 'closed'"
Require-Contains 'Apply script checks target date before overwrite' $apply "'vg_editorial_target_publish_date' => '2026-08-20'"
Require-Contains 'Apply script checks batch before overwrite' $apply "'vg_editorial_batch' => 'batch-2-evergreen-planning'"
Require-Contains 'Apply script accepts replayed complete draft' $apply "['brief', 'complete_draft']"
Require-Contains 'Apply script prevalidates categories' $apply '$category_term_ids = vg_mekong_overnight_post_term_ids'
Require-Contains 'Apply script prevalidates tags' $apply '$tag_term_ids = vg_mekong_overnight_post_term_ids'
Require-Contains 'Apply script checks category assignment' $apply '$category_result = wp_set_object_terms'
Require-Contains 'Apply script checks tag assignment' $apply '$tag_result = wp_set_object_terms'
Require-Contains 'Apply script sets complete status' $apply 'complete_draft'
Require-Contains 'Apply script keeps admin owner' $apply 'vg_content_owner'
Require-Contains 'Apply script keeps automation lock' $apply 'vg_automation_lock'
Require-Contains 'Apply script sets affiliate status none' $apply 'vg_eeat_affiliate_status'

foreach ($marker in @(
    'vg-mekong-overnight-hero:v1',
    'vg-mekong-overnight-concierge-verdict:v1',
    'vg-mekong-overnight-gsc-demand:v1',
    'vg-mekong-overnight-decision-matrix:v1',
    'vg-mekong-overnight-photo-proof:v1',
    'vg-mekong-overnight-source-diversity:v1',
    'vg-mekong-overnight-day-trip-fit:v1',
    'vg-mekong-overnight-upgrade-logic:v1',
    'vg-mekong-overnight-can-tho-cai-rang:v1',
    'vg-mekong-overnight-ben-tre-my-tho:v1',
    'vg-mekong-overnight-family-comfort:v1',
    'vg-mekong-overnight-transport-flight-day:v1',
    'vg-mekong-overnight-route-length:v1',
    'vg-mekong-overnight-upgrade-skip-logic:v1',
    'vg-mekong-overnight-live-checks:v1',
    'vg-mekong-overnight-faq:v1'
)) {
    Require-Contains "Apply script has marker $marker" $apply $marker
    Require-Contains "Runtime verifier checks marker $marker" $verify $marker
}

foreach ($shortcode in @('[vg_editorial_proof]', '[vg_related_routes]', '[vg_source_trail]', '[vg_update_log]')) {
    Require-Contains "Apply script includes shortcode $shortcode" $apply $shortcode
}

foreach ($needle in @(
    '/destinations/mekong-delta-travel-guide/',
    '/destinations/ho-chi-minh-city-travel-guide/',
    '/destinations/best-day-trips-from-ho-chi-minh-city/',
    '/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/',
    '/plan/vietnam-travel-guide/',
    '/compare/north-central-south-vietnam/',
    '/plan/transport-within-vietnam/',
    '/costs/vietnam-travel-cost/',
    '/plan/best-time-to-visit-vietnam/',
    '/itineraries/10-days-in-vietnam/',
    '/itineraries/14-days-in-vietnam/',
    '/destinations/phu-quoc-travel-guide/',
    '/plan/health-travel-insurance-vietnam/',
    '/plan/safety-scams-vietnam/'
)) {
    Require-Contains "Apply script includes internal route $needle" $apply $needle
    Require-Contains "Runtime verifier checks internal route $needle" $verify $needle
}

foreach ($unpublishedPath in @(
    '/destinations/mekong-delta-overnight-vs-day-trip/',
    '/destinations/hoi-an-ancient-town-guide/',
    '/destinations/hue-imperial-city-guide/',
    '/destinations/da-nang-beaches-guide/'
)) {
    Require-NotContains "Apply script avoids unpublished route $unpublishedPath" $apply $unpublishedPath
}

foreach ($needle in @(
    'Vietnam.travel - Day-tripping the Mekong Delta',
    'Vietnam.travel - 4 memorable days in the Mekong Delta',
    'Vietnam.travel - Towns in the Mekong Delta',
    'Vietnam.travel - Can Tho glimpse of river and garden',
    'Vietnam.travel - Floating markets in the Mekong Delta',
    'Vietnam.travel - How to travel the Mekong Delta',
    'Vietnam.travel - Cai Be',
    'Vietnam.travel - Can Tho destination page',
    'Vietnam.travel - Chau Doc destination page',
    'Vietnam.travel - Ho Chi Minh City destination page',
    'Vietnam.travel - Weather and climate',
    'Vietnam.travel - Transport within Vietnam',
    'Can Tho tourism portal',
    'National Center for Hydro-Meteorological Forecasting - Can Tho weather',
    'Google Search Console query export',
    'Wikimedia Commons image direct URL',
    'Cai Rang',
    'Can Tho',
    'Ben Tre',
    'My Tho',
    'Chau Doc',
    'flight-day',
    'Skip logic'
)) {
    Require-Contains "Apply script includes evidence phrase $needle" $apply $needle
}

Require-Contains 'Apply script uses Wikimedia images' $apply 'upload.wikimedia.org'
Require-Contains 'Apply script uses text-only image credits' $apply 'Image:'
Require-Contains 'Apply script stores hero image credit meta' $apply 'vg_eeat_hero_image_credit'

foreach ($unsafePhrase in @(
    'hidden gem',
    'ultimate Mekong list',
    'must see everything',
    'guaranteed floating market',
    'perfect Mekong tour',
    'copy this itinerary',
    'authentic local life guaranteed'
)) {
    Require-NotContains "Apply script avoids unsafe phrase $unsafePhrase" $apply $unsafePhrase
}

foreach ($metaKey in @(
    'rank_math_title',
    'rank_math_description',
    'rank_math_focus_keyword',
    'vg_eeat_primary_decision',
    'vg_eeat_reviewed_guide',
    'vg_eeat_written_by',
    'vg_eeat_reviewed_by',
    'vg_eeat_last_meaningful_update',
    'vg_eeat_update_summary',
    'vg_eeat_sources_checked',
    'vg_eeat_field_note',
    'vg_eeat_affiliate_status',
    'vg_eeat_evidence_moat',
    'vg_eeat_related_routes',
    'vg_eeat_hero_image_credit'
)) {
    Require-Contains "Apply script sets meta $metaKey" $apply $metaKey
    Require-Contains "Runtime verifier checks meta $metaKey" $verify $metaKey
}

foreach ($termSlug in @(
    'destinations',
    'transport-logistics',
    'route-planning',
    'first-time-vietnam',
    'family-travel',
    'anti-spam-evergreen'
)) {
    Require-Contains "Apply script assigns term $termSlug" $apply $termSlug
    Require-Contains "Runtime verifier checks term $termSlug" $verify $termSlug
}

Require-Contains 'Runtime verifier requires draft' $verify "post_status !== 'draft'"
Require-Contains 'Runtime verifier requires unique slug match' $verify 'count($posts) !== 1'
Require-Contains 'Runtime verifier checks no brief language' $verify 'Draft status:'
Require-Contains 'Runtime verifier checks additional placeholders' $verify 'Publish gate'
Require-Contains 'Runtime verifier checks rendered proof panel' $verify 'vg-proof-panel'
Require-Contains 'Runtime verifier checks rendered source trail' $verify 'vg-source-trail'
Require-Contains 'Runtime verifier checks rendered update log' $verify 'vg-update-log'
Require-Contains 'Runtime verifier checks external href count' $verify 'external_body_href_count'
Require-Contains 'Runtime verifier tracks raw external href count' $verify 'raw_external_body_href_count'
Require-Contains 'Runtime verifier tracks rendered external href count' $verify 'rendered_external_body_href_count'
Require-Contains 'Runtime verifier enforces zero raw external body links' $verify 'Mekong Delta Overnight raw_external_body_href_count should be 0; found {$raw_external_body_href_count}'
Require-Contains 'Runtime verifier enforces zero rendered external body links' $verify 'Mekong Delta Overnight rendered_external_body_href_count should be 0; found {$rendered_external_body_href_count}'
Require-Contains 'Runtime verifier enforces zero external body links' $verify 'Mekong Delta Overnight external_body_href_count should be 0; found {$external_body_href_count}'
Require-Contains 'Runtime verifier extracts internal hrefs' $verify 'vg_verify_mekong_overnight_internal_body_hrefs'
Require-Contains 'Runtime verifier resolves internal hrefs' $verify 'url_to_postid'
Require-Contains 'Runtime verifier falls back to path lookup' $verify 'get_page_by_path'
Require-Contains 'Runtime verifier requires resolved internal href targets' $verify 'internal body link does not resolve'
Require-Contains 'Runtime verifier requires published internal href targets' $verify "internal body link is not published"
Require-Contains 'Runtime verifier checks rendered internal href targets' $verify 'vg_verify_mekong_overnight_assert_internal_body_hrefs_published($failures, $rendered_content)'
Require-Contains 'Runtime verifier checks content depth' $verify '11000'
Require-Contains 'Runtime verifier checks target publish date' $verify '2026-08-20'
Require-Contains 'Batch verifier allows complete drafts' $batchVerify 'complete_draft'

$visibleExternalHrefs = Get-VisibleExternalHrefs $apply
if ($visibleExternalHrefs.Count -ne 0) {
    throw "Apply script should not create external body anchors; found $($visibleExternalHrefs.Count): $($visibleExternalHrefs -join ' | ')"
}

Write-Output 'Mekong Delta Overnight static checks passed.'

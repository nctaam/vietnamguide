$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$staticPath = $MyInvocation.MyCommand.Path
$applyPath = Join-Path $repoRoot 'ops/apply-sapa-vs-ha-giang-post.php'
$verifyPath = Join-Path $repoRoot 'ops/verify-sapa-vs-ha-giang-post.php'
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

Require-File 'Sapa vs Ha Giang static verifier' $staticPath
Require-File 'Sapa vs Ha Giang apply script' $applyPath
Require-File 'Sapa vs Ha Giang runtime verifier' $verifyPath
Require-File 'Batch 2 runtime verifier' $batchVerifyPath

$apply = Get-Content -Raw -LiteralPath $applyPath
$verify = Get-Content -Raw -LiteralPath $verifyPath
$batchVerify = Get-Content -Raw -LiteralPath $batchVerifyPath

Require-Contains 'Apply script requires override' $apply 'VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE'
Require-Contains 'Apply script requires exact override value' $apply "`$value === '1'"
Require-Contains 'Apply script requires unique slug match' $apply 'count($posts) !== 1'
Require-Contains 'Apply script targets expected slug' $apply 'sapa-vs-ha-giang'
Require-Contains 'Apply script guards exact post ID' $apply '$post_id !== 504'
Require-Contains 'Runtime verifier guards exact post ID' $verify '$post_id !== 504'
Require-Contains 'Runtime verifier guards exact title' $verify "post_title !== 'Sapa vs Ha Giang: Terraces, Loop Roads or Softer Mountain Travel?'"
Require-Contains 'Runtime verifier guards exact slug' $verify "post_name !== 'sapa-vs-ha-giang'"
Require-Contains 'Apply script finds native posts only' $apply "'post_type' => 'post'"
Require-Contains 'Apply script refuses missing post' $apply 'Required draft post not found'
Require-Contains 'Apply script refuses non-draft post' $apply 'because it is not draft'
Require-Contains 'Apply script keeps draft status' $apply "'post_status' => 'draft'"
Require-Contains 'Apply script closes comments' $apply "'comment_status' => 'closed'"
Require-Contains 'Apply script closes pings' $apply "'ping_status' => 'closed'"
Require-Contains 'Apply script checks target date before overwrite' $apply "'vg_editorial_target_publish_date' => '2026-08-22'"
Require-Contains 'Apply script checks batch before overwrite' $apply "'vg_editorial_batch' => 'batch-2-evergreen-planning'"
Require-Contains 'Apply script accepts replayed complete draft' $apply "['brief', 'complete_draft']"
Require-Contains 'Apply script prevalidates categories' $apply '$category_term_ids = vg_sapa_ha_giang_post_term_ids'
Require-Contains 'Apply script prevalidates tags' $apply '$tag_term_ids = vg_sapa_ha_giang_post_term_ids'
Require-Contains 'Apply script checks category assignment' $apply '$category_result = wp_set_object_terms'
Require-Contains 'Apply script checks tag assignment' $apply '$tag_result = wp_set_object_terms'
Require-Contains 'Apply script sets complete status' $apply 'complete_draft'
Require-Contains 'Apply script keeps admin owner' $apply 'vg_content_owner'
Require-Contains 'Apply script keeps automation lock' $apply 'vg_automation_lock'
Require-Contains 'Apply script sets affiliate status none' $apply 'vg_eeat_affiliate_status'

foreach ($marker in @(
    'vg-sapa-ha-giang-hero:v1',
    'vg-sapa-ha-giang-concierge-verdict:v1',
    'vg-sapa-ha-giang-internal-demand:v1',
    'vg-sapa-ha-giang-photo-proof:v1',
    'vg-sapa-ha-giang-source-diversity:v1',
    'vg-sapa-ha-giang-decision-matrix:v1',
    'vg-sapa-ha-giang-sapa-fit:v1',
    'vg-sapa-ha-giang-ha-giang-fit:v1',
    'vg-sapa-ha-giang-safety-license:v1',
    'vg-sapa-ha-giang-season-weather:v1',
    'vg-sapa-ha-giang-trip-length:v1',
    'vg-sapa-ha-giang-comfort-mobility:v1',
    'vg-sapa-ha-giang-transport-route-pressure:v1',
    'vg-sapa-ha-giang-live-checks:v1',
    'vg-sapa-ha-giang-faq:v1'
)) {
    Require-Contains "Apply script has marker $marker" $apply $marker
    Require-Contains "Runtime verifier checks marker $marker" $verify $marker
}

foreach ($shortcode in @('[vg_editorial_proof]', '[vg_related_routes]', '[vg_source_trail]', '[vg_update_log]')) {
    Require-Contains "Apply script includes shortcode $shortcode" $apply $shortcode
}

foreach ($needle in @(
    '/destinations/hanoi-travel-guide/',
    '/plan/best-time-to-visit-vietnam/',
    '/plan/transport-within-vietnam/',
    '/plan/health-travel-insurance-vietnam/',
    '/plan/safety-scams-vietnam/',
    '/compare/north-central-south-vietnam/',
    '/plan/vietnam-travel-guide/',
    '/costs/vietnam-travel-cost/',
    '/itineraries/10-days-in-vietnam/',
    '/itineraries/14-days-in-vietnam/',
    '/itineraries/21-days-in-vietnam/',
    '/destinations/ninh-binh-travel-guide/',
    '/destinations/ha-long-bay-travel-guide/',
    '/plan/sim-esim-vietnam/'
)) {
    Require-Contains "Apply script includes internal route $needle" $apply $needle
    Require-Contains "Runtime verifier checks internal route $needle" $verify $needle
}

foreach ($unpublishedPath in @(
    '/destinations/sapa-vs-ha-giang/',
    '/destinations/sapa-travel-guide/',
    '/destinations/ha-giang-travel-guide/',
    '/plan/ha-giang-safety-guide/',
    '/compare/ha-giang-easy-rider-vs-self-drive/',
    '/plan/hanoi-to-sapa-transport/',
    '/plan/hanoi-to-ha-giang-transport/'
)) {
    Require-NotContains "Apply script avoids unpublished route $unpublishedPath" $apply $unpublishedPath
}

foreach ($needle in @(
    'Vietnam.travel - Sapa',
    'Vietnam.travel - Ha Giang',
    'Vietnam.travel - Ha Giang Loop',
    'Vietnam.travel - Weather and climate in Vietnam',
    'Vietnam.travel - Transport within Vietnam',
    'Dong Van Karst Plateau Geopark official site',
    'UNESCO Global Geoparks - Dong Van Karst Plateau',
    'Global Geoparks Network - Dong Van Karst Plateau',
    'Vietnam National Center for Hydro-Meteorological Forecasting',
    'UK FCDO Vietnam travel advice',
    'Australian Smartraveller Vietnam advice',
    'Wikimedia Commons image direct URL',
    'Muong Hoa',
    'Ma Pi Leng',
    'Dong Van',
    'Tu San',
    'terrace',
    'loop-road',
    'self-drive',
    'easy rider',
    'private car',
    'license',
    'insurance',
    'fog',
    'rain',
    'recovery time',
    'Skip',
    'neither'
)) {
    Require-Contains "Apply script includes evidence phrase $needle" $apply $needle
}

Require-Contains 'Apply script uses Wikimedia images' $apply 'upload.wikimedia.org'
Require-Contains 'Apply script uses text-only image credits' $apply 'Image:'
Require-Contains 'Apply script stores hero image credit meta' $apply 'vg_eeat_hero_image_credit'

foreach ($unsafePhrase in @(
    'hidden gem',
    'ultimate Ha Giang loop',
    'best mountain route in Vietnam',
    'safe for everyone',
    'guaranteed clear views',
    'must-visit',
    'untouched paradise',
    'copy this route',
    'no license needed',
    'insurance always covers'
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
    'travel-planning',
    'route-planning',
    'first-time-vietnam',
    'mountain-planning',
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
Require-Contains 'Runtime verifier enforces zero raw external body links' $verify 'Sapa vs Ha Giang raw_external_body_href_count should be 0; found {$raw_external_body_href_count}'
Require-Contains 'Runtime verifier enforces zero rendered external body links' $verify 'Sapa vs Ha Giang rendered_external_body_href_count should be 0; found {$rendered_external_body_href_count}'
Require-Contains 'Runtime verifier enforces zero external body links' $verify 'Sapa vs Ha Giang external_body_href_count should be 0; found {$external_body_href_count}'
Require-Contains 'Runtime verifier extracts internal hrefs' $verify 'vg_verify_sapa_ha_giang_internal_body_hrefs'
Require-Contains 'Runtime verifier resolves internal hrefs' $verify 'url_to_postid'
Require-Contains 'Runtime verifier falls back to path lookup' $verify 'get_page_by_path'
Require-Contains 'Runtime verifier checks internal href HTTP status' $verify 'wp_remote_head'
Require-Contains 'Runtime verifier falls back to GET for internal href HTTP status' $verify 'wp_remote_get'
Require-Contains 'Runtime verifier retrieves internal href HTTP status' $verify 'wp_remote_retrieve_response_code'
Require-Contains 'Runtime verifier requires resolved internal href targets' $verify 'internal body link does not resolve'
Require-Contains 'Runtime verifier requires published internal href targets' $verify "internal body link is not published"
Require-Contains 'Runtime verifier requires HTTP 200 internal href targets' $verify 'internal body link does not return HTTP 200'
Require-Contains 'Runtime verifier checks rendered internal href targets' $verify 'vg_verify_sapa_ha_giang_assert_internal_body_hrefs_published($failures, $rendered_content)'
Require-Contains 'Runtime verifier checks content depth' $verify '11000'
Require-Contains 'Runtime verifier checks target publish date' $verify '2026-08-22'
Require-Contains 'Batch verifier allows complete drafts' $batchVerify 'complete_draft'

$visibleExternalHrefs = Get-VisibleExternalHrefs $apply
if ($visibleExternalHrefs.Count -ne 0) {
    throw "Apply script should not create external body anchors; found $($visibleExternalHrefs.Count): $($visibleExternalHrefs -join ' | ')"
}

Write-Output 'Sapa vs Ha Giang static checks passed.'

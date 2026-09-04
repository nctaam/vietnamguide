$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$staticPath = $MyInvocation.MyCommand.Path
$applyPath = Join-Path $repoRoot 'ops/apply-vietnam-in-december-post.php'
$verifyPath = Join-Path $repoRoot 'ops/verify-vietnam-in-december-post.php'
$batchVerifyPath = Join-Path $repoRoot 'ops/verify-wordpress-post-editorial-system-batch-2.php'

function Require-File {
    param(
        [string] $Label,
        [string] $Path
    )

    if (-not (Test-Path -LiteralPath $Path)) {
        throw "$Label missing: $Path"
    }
}

function Require-Contains {
    param(
        [string] $Label,
        [string] $Text,
        [string] $Needle
    )

    if (-not $Text.Contains($Needle)) {
        throw "$Label missing required text: $Needle"
    }
}

function Require-NotContains {
    param(
        [string] $Label,
        [string] $Text,
        [string] $Needle
    )

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

Require-File 'Vietnam in December static verifier' $staticPath
Require-File 'Vietnam in December post apply script' $applyPath
Require-File 'Vietnam in December post runtime verifier' $verifyPath
Require-File 'Batch 2 runtime verifier' $batchVerifyPath

$apply = Get-Content -Raw -LiteralPath $applyPath
$verify = Get-Content -Raw -LiteralPath $verifyPath
$batchVerify = Get-Content -Raw -LiteralPath $batchVerifyPath

Require-Contains 'Apply script requires override' $apply 'VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE'
Require-Contains 'Apply script requires exact override value' $apply "`$value === '1'"
Require-Contains 'Apply script requires unique slug match' $apply 'count($posts) !== 1'
Require-Contains 'Apply script targets expected slug' $apply 'vietnam-in-december'
Require-Contains 'Apply script finds native posts only' $apply "'post_type' => 'post'"
Require-Contains 'Apply script refuses missing post' $apply 'Required draft post not found'
Require-Contains 'Apply script refuses non-draft post' $apply 'because it is not draft'
Require-Contains 'Apply script keeps draft status' $apply "'post_status' => 'draft'"
Require-Contains 'Apply script checks exact target date before overwrite' $apply "'vg_editorial_target_publish_date' => '2026-08-11'"
Require-Contains 'Apply script checks batch before overwrite' $apply "'vg_editorial_batch' => 'batch-2-evergreen-planning'"
Require-Contains 'Apply script clears brief status' $apply 'delete_post_meta($post_id, ''vg_editorial_brief_status'')'
Require-Contains 'Apply script sets complete status' $apply 'update_post_meta($post_id, ''vg_editorial_brief_status'', ''complete_draft'')'
Require-Contains 'Apply script keeps admin owner' $apply 'update_post_meta($post_id, ''vg_content_owner'', ''wp_admin'')'
Require-Contains 'Apply script keeps automation lock' $apply 'update_post_meta($post_id, ''vg_automation_lock'', ''locked'')'
Require-Contains 'Apply script sets affiliate status none' $apply 'update_post_meta($post_id, ''vg_eeat_affiliate_status'', ''none'')'
Require-NotContains 'Apply script does not overwrite target publish date meta' $apply 'update_post_meta($post_id, ''vg_editorial_target_publish_date'''

foreach ($marker in @(
    'vg-december-hero:v1',
    'vg-december-concierge-verdict:v1',
    'vg-december-at-a-glance:v1',
    'vg-december-photo-proof:v1',
    'vg-december-source-diversity:v1',
    'vg-december-region-weather:v1',
    'vg-december-route-chooser:v1',
    'vg-december-book-early:v1',
    'vg-december-beach-bay:v1',
    'vg-december-packing:v1',
    'vg-december-fragile-plans:v1',
    'vg-december-live-checks:v1',
    'vg-december-faq:v1'
)) {
    Require-Contains "Apply script has marker $marker" $apply $marker
    Require-Contains "Runtime verifier checks marker $marker" $verify $marker
}

foreach ($shortcode in @(
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]'
)) {
    Require-Contains "Apply script includes shortcode $shortcode" $apply $shortcode
}

foreach ($needle in @(
    '/plan/vietnam-travel-guide/',
    '/plan/best-time-to-visit-vietnam/',
    '/compare/north-central-south-vietnam/',
    '/itineraries/10-days-in-vietnam/',
    '/itineraries/14-days-in-vietnam/',
    '/destinations/ha-long-bay-travel-guide/',
    '/destinations/best-beaches-in-vietnam/',
    '/destinations/phu-quoc-travel-guide/',
    '/destinations/da-nang-travel-guide/',
    '/costs/vietnam-travel-cost/',
    '/plan/transport-within-vietnam/'
)) {
    Require-Contains "Apply script includes internal route $needle" $apply $needle
}

foreach ($needle in @(
    'Vietnam.travel - Weather and climate in Vietnam',
    'Vietnam.travel - Northern Vietnam destinations',
    'Vietnam.travel - Central Vietnam destinations',
    'Vietnam.travel - Southern Vietnam destinations',
    'National Centre for Hydro-Meteorological Forecasting',
    'World Weather Information Service',
    'December route decision',
    'cool north',
    'central-coast risk',
    'south and island route'
)) {
    Require-Contains "Apply script includes evidence phrase $needle" $apply $needle
}

Require-Contains 'Apply script uses Wikimedia image URL' $apply 'upload.wikimedia.org'
Require-Contains 'Apply script uses text-only image credit wording' $apply 'Image:'
Require-Contains 'Apply script stores hero image credit meta' $apply 'vg_eeat_hero_image_credit'

foreach ($unsafePhrase in @(
    'perfect weather nationwide',
    'guaranteed sunshine',
    'best month for everyone',
    'top 10 December hacks'
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
    'seasonal-travel',
    'travel-planning',
    'month-by-month',
    'first-time-vietnam',
    'route-planning',
    'anti-spam-evergreen'
)) {
    Require-Contains "Apply script assigns term $termSlug" $apply $termSlug
    Require-Contains "Runtime verifier checks term $termSlug" $verify $termSlug
}

Require-Contains 'Runtime verifier requires draft' $verify "post_status !== 'draft'"
Require-Contains 'Runtime verifier requires unique slug match' $verify 'count($posts) !== 1'
Require-Contains 'Runtime verifier checks comments closed' $verify "comment_status !== 'closed'"
Require-Contains 'Runtime verifier checks pings closed' $verify "ping_status !== 'closed'"
Require-Contains 'Runtime verifier checks no brief language' $verify 'Draft status:'
Require-Contains 'Runtime verifier checks rendered proof panel' $verify 'vg-proof-panel'
Require-Contains 'Runtime verifier checks rendered source trail' $verify 'vg-source-trail'
Require-Contains 'Runtime verifier checks rendered update log' $verify 'vg-update-log'
Require-Contains 'Runtime verifier checks rendered related routes' $verify 'vg-related-routes'
Require-Contains 'Runtime verifier checks external href count' $verify 'external_body_href_count'
Require-Contains 'Runtime verifier enforces zero external body hrefs' $verify 'external_body_href_count should be 0'
Require-Contains 'Runtime verifier checks content depth' $verify '9500'
Require-Contains 'Runtime verifier checks target publish date' $verify '2026-08-11'

Require-Contains 'Batch verifier allows complete drafts' $batchVerify 'complete_draft'
Require-Contains 'Batch verifier counts complete drafts' $batchVerify 'complete_draft_count'
Require-Contains 'Batch verifier rejects brief placeholders in complete drafts' $batchVerify 'still contains draft-status placeholder guidance'

$visibleExternalHrefs = Get-VisibleExternalHrefs $apply
if ($visibleExternalHrefs.Count -ne 0) {
    throw "Apply script should not create external body anchors; found $($visibleExternalHrefs.Count): $($visibleExternalHrefs -join ' | ')"
}

Write-Output 'Vietnam in December static checks passed.'

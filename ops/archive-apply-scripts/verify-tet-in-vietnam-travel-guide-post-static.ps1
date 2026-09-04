$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$staticPath = $MyInvocation.MyCommand.Path
$applyPath = Join-Path $repoRoot 'ops/apply-tet-in-vietnam-travel-guide-post.php'
$verifyPath = Join-Path $repoRoot 'ops/verify-tet-in-vietnam-travel-guide-post.php'
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

Require-File 'Tet in Vietnam static verifier' $staticPath
Require-File 'Tet in Vietnam post apply script' $applyPath
Require-File 'Tet in Vietnam post runtime verifier' $verifyPath
Require-File 'Batch 2 runtime verifier' $batchVerifyPath

$apply = Get-Content -Raw -LiteralPath $applyPath
$verify = Get-Content -Raw -LiteralPath $verifyPath
$batchVerify = Get-Content -Raw -LiteralPath $batchVerifyPath

Require-Contains 'Apply script requires override' $apply 'VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE'
Require-Contains 'Apply script requires exact override value' $apply "`$value === '1'"
Require-Contains 'Apply script requires unique slug match' $apply 'count($posts) !== 1'
Require-Contains 'Apply script targets expected slug' $apply 'tet-in-vietnam-travel-guide'
Require-Contains 'Apply script finds native posts only' $apply "'post_type' => 'post'"
Require-Contains 'Apply script refuses missing post' $apply 'Required draft post not found'
Require-Contains 'Apply script refuses non-draft post' $apply 'because it is not draft'
Require-Contains 'Apply script keeps draft status' $apply "'post_status' => 'draft'"
Require-Contains 'Apply script closes comments' $apply "'comment_status' => 'closed'"
Require-Contains 'Apply script closes pings' $apply "'ping_status' => 'closed'"
Require-Contains 'Apply script checks exact target date before overwrite' $apply "'vg_editorial_target_publish_date' => '2026-08-14'"
Require-Contains 'Apply script checks batch before overwrite' $apply "'vg_editorial_batch' => 'batch-2-evergreen-planning'"
Require-Contains 'Apply script prevalidates categories' $apply '$category_term_ids = vg_tet_post_term_ids'
Require-Contains 'Apply script prevalidates tags' $apply '$tag_term_ids = vg_tet_post_term_ids'
Require-Contains 'Apply script checks category assignment' $apply '$category_result = wp_set_object_terms'
Require-Contains 'Apply script checks tag assignment' $apply '$tag_result = wp_set_object_terms'
Require-Contains 'Apply script sets complete status' $apply 'complete_draft'
Require-Contains 'Apply script keeps admin owner' $apply 'vg_content_owner'
Require-Contains 'Apply script keeps automation lock' $apply 'vg_automation_lock'
Require-Contains 'Apply script sets affiliate status none' $apply 'vg_eeat_affiliate_status'

foreach ($marker in @(
    'vg-tet-hero:v1',
    'vg-tet-concierge-verdict:v1',
    'vg-tet-quick-decision:v1',
    'vg-tet-photo-proof:v1',
    'vg-tet-source-diversity:v1',
    'vg-tet-timing-phases:v1',
    'vg-tet-book-early:v1',
    'vg-tet-operating-rhythm:v1',
    'vg-tet-route-chooser:v1',
    'vg-tet-city-choice:v1',
    'vg-tet-respectful-behavior:v1',
    'vg-tet-money-food-medicine:v1',
    'vg-tet-fragile-plans:v1',
    'vg-tet-live-checks:v1',
    'vg-tet-faq:v1'
)) {
    Require-Contains "Apply script has marker $marker" $apply $marker
    Require-Contains "Runtime verifier checks marker $marker" $verify $marker
}

foreach ($shortcode in @('[vg_editorial_proof]', '[vg_related_routes]', '[vg_source_trail]', '[vg_update_log]')) {
    Require-Contains "Apply script includes shortcode $shortcode" $apply $shortcode
}

foreach ($needle in @(
    '/plan/best-time-to-visit-vietnam/',
    '/plan/transport-within-vietnam/',
    '/travel-planning/vietnam-in-january/',
    '/travel-planning/vietnam-in-february/',
    '/travel-planning/vietnam-food-safety-street-food-etiquette/',
    '/travel-planning/vietnam-airport-arrival-checklist/',
    '/plan/money-cash-cards-atms/',
    '/plan/sim-esim-vietnam/',
    '/costs/vietnam-travel-cost/',
    '/itineraries/14-days-in-vietnam/'
)) {
    Require-Contains "Apply script includes internal route $needle" $apply $needle
}

foreach ($needle in @(
    'Vietnam.travel - A traveller''s guide to Tet holiday',
    'Vietnam.travel - Tet: Tradition, Reunion & Taste',
    'Vietnam.travel - Transport within Vietnam',
    'Vietnam.travel - Weather and climate in Vietnam',
    'Vietnam Labor Code 2019',
    'official public-holiday announcement',
    'World Weather Information Service',
    'Tet travel decision',
    'before, during, and after Tet',
    'post-Tet restart',
    'closures are uneven',
    'book transport early',
    'respectful cultural behavior',
    'exact Tet dates change yearly',
    '5 public holiday days'
)) {
    Require-Contains "Apply script includes evidence phrase $needle" $apply $needle
}

Require-Contains 'Apply script uses Wikimedia images' $apply 'upload.wikimedia.org'
Require-Contains 'Apply script uses text-only image credits' $apply 'Image:'
Require-Contains 'Apply script stores hero image credit meta' $apply 'vg_eeat_hero_image_credit'

foreach ($unsafePhrase in @(
    'everything is closed during Tet',
    'everything is open during Tet',
    'Tet dates are the same every year',
    'no need to check Tet dates',
    'guaranteed availability',
    'Tet travel hacks',
    'avoid Vietnam during Tet',
    'locals hate tourists during Tet',
    'public holiday means all businesses close'
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
    'food-culture',
    'travel-planning',
    'tet-travel',
    'international-travelers',
    'route-planning',
    'anti-spam-evergreen'
)) {
    Require-Contains "Apply script assigns term $termSlug" $apply $termSlug
    Require-Contains "Runtime verifier checks term $termSlug" $verify $termSlug
}

Require-Contains 'Runtime verifier requires draft' $verify "post_status !== 'draft'"
Require-Contains 'Runtime verifier requires unique slug match' $verify 'count($posts) !== 1'
Require-Contains 'Runtime verifier checks no brief language' $verify 'Draft status:'
Require-Contains 'Runtime verifier checks additional placeholders' $verify 'Publish gate'
Require-Contains 'Runtime verifier checks closed comments' $verify "comment_status !== 'closed'"
Require-Contains 'Runtime verifier checks closed pings' $verify "ping_status !== 'closed'"
Require-Contains 'Runtime verifier checks rendered proof panel' $verify 'vg-proof-panel'
Require-Contains 'Runtime verifier checks rendered source trail' $verify 'vg-source-trail'
Require-Contains 'Runtime verifier checks rendered update log' $verify 'vg-update-log'
Require-Contains 'Runtime verifier checks external href count' $verify 'external_body_href_count'
Require-Contains 'Runtime verifier enforces zero external body links' $verify 'external_body_href_count should be 0'
Require-Contains 'Runtime verifier checks content depth' $verify '11000'
Require-Contains 'Runtime verifier checks target publish date' $verify '2026-08-14'
Require-Contains 'Batch verifier allows complete drafts' $batchVerify 'complete_draft'

$visibleExternalHrefs = Get-VisibleExternalHrefs $apply
if ($visibleExternalHrefs.Count -ne 0) {
    throw "Apply script should not create external body anchors; found $($visibleExternalHrefs.Count): $($visibleExternalHrefs -join ' | ')"
}

Write-Output 'Tet in Vietnam static checks passed.'

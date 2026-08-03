$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$staticPath = $MyInvocation.MyCommand.Path
$applyPath = Join-Path $repoRoot 'ops/apply-best-vietnam-cities-first-time-visitors-post.php'
$verifyPath = Join-Path $repoRoot 'ops/verify-best-vietnam-cities-first-time-visitors-post.php'
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

Require-File 'Best Vietnam Cities static verifier' $staticPath
Require-File 'Best Vietnam Cities post apply script' $applyPath
Require-File 'Best Vietnam Cities post runtime verifier' $verifyPath
Require-File 'Batch 2 runtime verifier' $batchVerifyPath

$apply = Get-Content -Raw -LiteralPath $applyPath
$verify = Get-Content -Raw -LiteralPath $verifyPath
$batchVerify = Get-Content -Raw -LiteralPath $batchVerifyPath

Require-Contains 'Apply script requires override' $apply 'VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE'
Require-Contains 'Apply script requires exact override value' $apply "`$value === '1'"
Require-Contains 'Apply script requires unique slug match' $apply 'count($posts) !== 1'
Require-Contains 'Apply script targets expected slug' $apply 'best-vietnam-cities-for-first-time-visitors'
Require-Contains 'Apply script guards exact post ID' $apply '$post_id !== 497'
Require-Contains 'Runtime verifier guards exact post ID' $verify '$post_id !== 497'
Require-Contains 'Apply script finds native posts only' $apply "'post_type' => 'post'"
Require-Contains 'Apply script refuses missing post' $apply 'Required draft post not found'
Require-Contains 'Apply script refuses non-draft post' $apply 'because it is not draft'
Require-Contains 'Apply script keeps draft status' $apply "'post_status' => 'draft'"
Require-Contains 'Apply script closes comments' $apply "'comment_status' => 'closed'"
Require-Contains 'Apply script closes pings' $apply "'ping_status' => 'closed'"
Require-Contains 'Apply script checks target date before overwrite' $apply "'vg_editorial_target_publish_date' => '2026-08-15'"
Require-Contains 'Apply script checks batch before overwrite' $apply "'vg_editorial_batch' => 'batch-2-evergreen-planning'"
Require-Contains 'Apply script accepts replayed complete draft' $apply "['brief', 'complete_draft']"
Require-Contains 'Apply script prevalidates categories' $apply '$category_term_ids = vg_best_cities_post_term_ids'
Require-Contains 'Apply script prevalidates tags' $apply '$tag_term_ids = vg_best_cities_post_term_ids'
Require-Contains 'Apply script checks category assignment' $apply '$category_result = wp_set_object_terms'
Require-Contains 'Apply script checks tag assignment' $apply '$tag_result = wp_set_object_terms'
Require-Contains 'Apply script sets complete status' $apply 'complete_draft'
Require-Contains 'Apply script keeps admin owner' $apply 'vg_content_owner'
Require-Contains 'Apply script keeps automation lock' $apply 'vg_automation_lock'
Require-Contains 'Apply script sets affiliate status none' $apply 'vg_eeat_affiliate_status'

foreach ($marker in @(
    'vg-best-cities-hero:v1',
    'vg-best-cities-concierge-verdict:v1',
    'vg-best-cities-at-a-glance:v1',
    'vg-best-cities-city-role-matrix:v1',
    'vg-best-cities-photo-proof:v1',
    'vg-best-cities-source-diversity:v1',
    'vg-best-cities-base-roles:v1',
    'vg-best-cities-city-by-city-base-logic:v1',
    'vg-best-cities-arrival-departure:v1',
    'vg-best-cities-traveler-fit:v1',
    'vg-best-cities-day-trip-pressure:v1',
    'vg-best-cities-skip-logic:v1',
    'vg-best-cities-live-checks:v1',
    'vg-best-cities-faq:v1'
)) {
    Require-Contains "Apply script has marker $marker" $apply $marker
    Require-Contains "Runtime verifier checks marker $marker" $verify $marker
}

foreach ($shortcode in @('[vg_editorial_proof]', '[vg_related_routes]', '[vg_source_trail]', '[vg_update_log]')) {
    Require-Contains "Apply script includes shortcode $shortcode" $apply $shortcode
}

foreach ($needle in @(
    '/plan/vietnam-travel-guide/',
    '/destinations/best-places-to-visit-vietnam/',
    '/compare/north-central-south-vietnam/',
    '/plan/best-time-to-visit-vietnam/',
    '/destinations/hanoi-travel-guide/',
    '/destinations/ho-chi-minh-city-travel-guide/',
    '/destinations/da-nang-travel-guide/',
    '/destinations/best-things-to-do-in-hoi-an/',
    '/destinations/best-things-to-do-in-hue/',
    '/compare/da-nang-vs-hoi-an/',
    '/compare/hoi-an-vs-hue/',
    '/destinations/ninh-binh-travel-guide/',
    '/destinations/ha-long-bay-travel-guide/',
    '/destinations/phu-quoc-travel-guide/',
    '/plan/transport-within-vietnam/',
    '/itineraries/7-days-in-vietnam/',
    '/itineraries/10-days-in-vietnam/',
    '/itineraries/14-days-in-vietnam/',
    '/itineraries/21-days-in-vietnam/',
    '/costs/vietnam-travel-cost/'
)) {
    Require-Contains "Apply script includes internal route $needle" $apply $needle
}

foreach ($needle in @(
    'Vietnam.travel - Hanoi destination page',
    'Vietnam.travel - Ho Chi Minh City destination page',
    'Vietnam.travel - Hoi An destination page',
    'Vietnam.travel - Hue destination page',
    'Vietnam.travel - Da Nang destination page',
    'Vietnam.travel - Transport within Vietnam',
    'Vietnam.travel - Weather and climate',
    'Wikimedia Commons image direct URL',
    'city-base decision',
    'route job',
    'Skip logic'
)) {
    Require-Contains "Apply script includes evidence phrase $needle" $apply $needle
}

Require-Contains 'Apply script uses Wikimedia images' $apply 'upload.wikimedia.org'
Require-Contains 'Apply script uses text-only image credits' $apply 'Image:'
Require-Contains 'Apply script stores hero image credit meta' $apply 'vg_eeat_hero_image_credit'

foreach ($unsafePhrase in @(
    'top 100 cities',
    'ultimate city list',
    'guaranteed best city',
    'hidden gem city',
    'must visit every city',
    'one city is always best'
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
    'first-time-vietnam',
    'city-comparison',
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
Require-Contains 'Runtime verifier checks rendered proof panel' $verify 'vg-proof-panel'
Require-Contains 'Runtime verifier checks rendered source trail' $verify 'vg-source-trail'
Require-Contains 'Runtime verifier checks rendered update log' $verify 'vg-update-log'
Require-Contains 'Runtime verifier checks external href count' $verify 'external_body_href_count'
Require-Contains 'Runtime verifier enforces zero external body links' $verify 'external_body_href_count should be 0'
Require-Contains 'Runtime verifier checks content depth' $verify '9000'
Require-Contains 'Runtime verifier checks target publish date' $verify '2026-08-15'
Require-Contains 'Batch verifier allows complete drafts' $batchVerify 'complete_draft'

$visibleExternalHrefs = Get-VisibleExternalHrefs $apply
if ($visibleExternalHrefs.Count -ne 0) {
    throw "Apply script should not create external body anchors; found $($visibleExternalHrefs.Count): $($visibleExternalHrefs -join ' | ')"
}

Write-Output 'Best Vietnam Cities static checks passed.'

$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$staticPath = $MyInvocation.MyCommand.Path
$applyPath = Join-Path $repoRoot 'ops/apply-ninh-binh-without-rushing-post.php'
$verifyPath = Join-Path $repoRoot 'ops/verify-ninh-binh-without-rushing-post.php'

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

Require-File 'Ninh Binh without rushing static verifier' $staticPath
Require-File 'Ninh Binh without rushing post apply script' $applyPath
Require-File 'Ninh Binh without rushing post runtime verifier' $verifyPath

$apply = Get-Content -Raw -LiteralPath $applyPath
$verify = Get-Content -Raw -LiteralPath $verifyPath

Require-Contains 'Apply script requires override' $apply 'VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE'
Require-Contains 'Apply script requires exact override value' $apply "`$value === '1'"
Require-NotContains 'Apply script does not trim override value' $apply "trim(`$value) === '1'"
Require-Contains 'Apply script requires unique slug match' $apply 'count($posts) !== 1'
Require-Contains 'Apply script targets expected slug' $apply 'ninh-binh-without-rushing'
Require-Contains 'Apply script finds native posts only' $apply "'post_type' => 'post'"
Require-Contains 'Apply script refuses missing post' $apply 'Required draft post not found'
Require-Contains 'Apply script refuses non-draft post' $apply 'because it is not draft'
Require-Contains 'Apply script keeps draft status' $apply "'post_status' => 'draft'"
Require-Contains 'Apply script closes comments' $apply "'comment_status' => 'closed'"
Require-Contains 'Apply script closes pings' $apply "'ping_status' => 'closed'"
Require-Contains 'Apply script checks target meta' $apply 'vg_ninh_binh_rushing_post_assert_target_meta'
Require-Contains 'Apply script checks exact target date before overwrite' $apply "'vg_editorial_target_publish_date' => '2026-08-06'"
Require-NotContains 'Apply script does not trim target meta before comparison' $apply 'trim((string) get_post_meta'
Require-Contains 'Apply script clears brief status' $apply 'delete_post_meta($post_id, ''vg_editorial_brief_status'')'
Require-Contains 'Apply script sets complete status' $apply 'update_post_meta($post_id, ''vg_editorial_brief_status'', ''complete_draft'')'
Require-Contains 'Apply script sets content owner' $apply 'update_post_meta($post_id, ''vg_content_owner'', ''wp_admin'')'
Require-Contains 'Apply script sets automation lock' $apply 'update_post_meta($post_id, ''vg_automation_lock'', ''locked'')'
Require-Contains 'Apply script sets affiliate status none' $apply 'update_post_meta($post_id, ''vg_eeat_affiliate_status'', ''none'')'
Require-NotContains 'Apply script preserves target publish date meta' $apply 'delete_post_meta($post_id, ''vg_editorial_target_publish_date'')'
Require-NotContains 'Apply script does not overwrite target publish date meta' $apply 'update_post_meta($post_id, ''vg_editorial_target_publish_date'''

foreach ($marker in @(
    'vg-ninh-binh-without-rushing-hero:v1',
    'vg-ninh-binh-without-rushing-verdict:v1',
    'vg-ninh-binh-without-rushing-at-a-glance:v1',
    'vg-ninh-binh-without-rushing-photo-grid:v1',
    'vg-ninh-binh-without-rushing-source-diversity:v1',
    'vg-ninh-binh-without-rushing-rush-diagnosis:v1',
    'vg-ninh-binh-without-rushing-boat-choice:v1',
    'vg-ninh-binh-without-rushing-base-choice:v1',
    'vg-ninh-binh-without-rushing-transfer-pressure:v1',
    'vg-ninh-binh-without-rushing-one-night-plan:v1',
    'vg-ninh-binh-without-rushing-two-night-plan:v1',
    'vg-ninh-binh-without-rushing-cut-list:v1',
    'vg-ninh-binh-without-rushing-live-checks:v1',
    'vg-ninh-binh-without-rushing-faq:v1'
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
    '/destinations/ninh-binh-travel-guide/',
    '/compare/ninh-binh-day-trip-vs-overnight/',
    '/compare/trang-an-vs-tam-coc/',
    '/plan/ninh-binh-to-ha-long-bay-transfer/',
    '/plan/hanoi-to-ninh-binh-transport/',
    '/destinations/where-to-stay-in-ninh-binh/',
    '/destinations/tam-coc-travel-guide/',
    '/destinations/best-day-trips-from-hanoi/',
    '/itineraries/10-days-in-vietnam/',
    '/itineraries/14-days-in-vietnam/'
)) {
    Require-Contains "Apply script includes internal route $needle" $apply $needle
}

foreach ($needle in @(
    'Vietnam.travel - Ninh Binh destination page',
    'Vietnam.travel - A guide to the boat tours of Ninh Binh',
    'UNESCO World Heritage Centre - Trang An Landscape Complex',
    'Ninh Binh Tourism Department',
    'Vietnam Railways',
    'Wikimedia Commons image direct URL',
    'one long day, one protected night, or two slower nights',
    'source-diversity'
)) {
    Require-Contains "Apply script includes evidence phrase $needle" $apply $needle
}

Require-Contains 'Apply script uses Wikimedia image URL' $apply 'upload.wikimedia.org'
Require-Contains 'Apply script uses text-only image credit wording' $apply 'Image:'
Require-Contains 'Apply script stores hero image credit meta' $apply 'vg_eeat_hero_image_credit'

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
    'family-travel',
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
Require-Contains 'Runtime verifier checks shortcode-rendered related route id' $verify 'vg-related-routes-title-'
Require-Contains 'Runtime verifier checks external href count' $verify 'external_body_href_count'
Require-Contains 'Runtime verifier enforces zero external body hrefs' $verify 'external_body_href_count should be 0'
Require-Contains 'Runtime verifier checks content depth' $verify '9500'
Require-Contains 'Runtime verifier checks target publish date' $verify 'vg_editorial_target_publish_date'
Require-Contains 'Runtime verifier checks exact target date' $verify '2026-08-06'

$visibleExternalHrefs = Get-VisibleExternalHrefs $apply
if ($visibleExternalHrefs.Count -ne 0) {
    throw "Apply script should not create external body anchors; found $($visibleExternalHrefs.Count): $($visibleExternalHrefs -join ' | ')"
}

Write-Output 'Ninh Binh without rushing post static verification passed.'

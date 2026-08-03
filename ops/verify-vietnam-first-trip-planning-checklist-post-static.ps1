$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$applyPath = Join-Path $repoRoot 'ops/apply-vietnam-first-trip-planning-checklist-post.php'
$verifyPath = Join-Path $repoRoot 'ops/verify-vietnam-first-trip-planning-checklist-post.php'

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

Require-File 'First-trip checklist post apply script' $applyPath
Require-File 'First-trip checklist post runtime verifier' $verifyPath

$apply = Get-Content -Raw -LiteralPath $applyPath
$verify = Get-Content -Raw -LiteralPath $verifyPath

Require-Contains 'Apply script requires override' $apply 'VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE'
Require-Contains 'Apply script keeps draft status' $apply "'post_status' => 'draft'"
Require-Contains 'Apply script targets expected slug' $apply 'vietnam-first-trip-planning-checklist'
Require-Contains 'Apply script clears brief status' $apply 'delete_post_meta($post_id, ''vg_editorial_brief_status'')'
Require-Contains 'Apply script sets complete status' $apply 'update_post_meta($post_id, ''vg_editorial_brief_status'', ''complete_draft'')'
Require-Contains 'Apply script has hero marker' $apply 'vg-first-trip-checklist-hero:v1'
Require-Contains 'Apply script has verdict marker' $apply 'vg-first-trip-checklist-verdict:v1'
Require-Contains 'Apply script has planning order marker' $apply 'vg-first-trip-checklist-planning-order:v1'
Require-Contains 'Apply script has route shape marker' $apply 'vg-first-trip-checklist-route-shape:v1'
Require-Contains 'Apply script has booking order marker' $apply 'vg-first-trip-checklist-booking-order:v1'
Require-Contains 'Apply script has arrival checklist marker' $apply 'vg-first-trip-checklist-arrival:v1'
Require-Contains 'Apply script has FAQ marker' $apply 'vg-first-trip-checklist-faq:v1'
Require-Contains 'Apply script includes internal links' $apply '/plan/vietnam-travel-guide/'
Require-Contains 'Apply script includes official source trail' $apply 'Vietnam National Electronic Visa system'
Require-Contains 'Apply script includes CDC source' $apply 'CDC Travelers Health - Vietnam'
Require-Contains 'Apply script uses no external anchors in body' $apply 'Visible external links are intentionally avoided in the article body'

Require-Contains 'Runtime verifier requires draft' $verify "post_status !== 'draft'"
Require-Contains 'Runtime verifier checks no brief language' $verify 'Draft status:'
Require-Contains 'Runtime verifier checks external href budget' $verify 'external_body_href_count'
Require-Contains 'Runtime verifier checks complete draft meta' $verify 'complete_draft'

Write-Output 'Vietnam first-trip checklist post static verification passed.'

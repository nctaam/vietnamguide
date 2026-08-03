$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$applyPath = Join-Path $repoRoot 'ops/apply-wordpress-post-editorial-system.php'
$verifyPath = Join-Path $repoRoot 'ops/verify-wordpress-post-editorial-system.php'
$runbookPath = Join-Path $repoRoot 'docs/editorial/wordpress-post-editorial-system.md'

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

Require-File 'Post editorial system apply script' $applyPath
Require-File 'Post editorial system verifier' $verifyPath
Require-File 'Post editorial system runbook' $runbookPath

$apply = Get-Content -Raw -LiteralPath $applyPath
$verify = Get-Content -Raw -LiteralPath $verifyPath
$runbook = Get-Content -Raw -LiteralPath $runbookPath

Require-Contains 'Apply script is WP-CLI only' $apply 'This script must be run with WP-CLI.'
Require-Contains 'Apply script creates drafts only' $apply "'post_status'  => 'draft'"
Require-Contains 'Apply script locks briefs for WP Admin' $apply 'update_post_meta($post_id, ''vg_content_owner'', ''wp_admin'')'
Require-Contains 'Apply script locks automation' $apply 'update_post_meta($post_id, ''vg_automation_lock'', ''locked'')'
Require-Contains 'Apply script sets editorial brief status' $apply 'update_post_meta($post_id, ''vg_editorial_brief_status'', ''brief'')'
Require-Contains 'Apply script has first-trip checklist brief' $apply 'Vietnam First Trip Planning Checklist'
Require-Contains 'Apply script has airport arrival brief' $apply 'Vietnam Airport Arrival Checklist'
Require-Contains 'Apply script has Ha Long cruise brief' $apply 'Ha Long Bay Cruise Questions to Ask Before Booking'
Require-Contains 'Apply script avoids future publish scheduling' $apply 'No future posts are scheduled by this script.'

foreach ($slug in @(
    'travel-planning',
    'itineraries',
    'destinations',
    'transport-logistics',
    'food-culture',
    'beaches-islands',
    'practicalities',
    'hotels-neighborhoods',
    'seasonal-travel',
    'heritage-culture'
)) {
    Require-Contains "Apply script category $slug" $apply "'slug' => '$slug'"
    Require-Contains "Verifier category $slug" $verify "'$slug'"
}

Require-Contains 'Runtime verifier checks draft status' $verify "post_status !== 'draft'"
Require-Contains 'Runtime verifier checks admin owner' $verify "'vg_content_owner'"
Require-Contains 'Runtime verifier checks automation lock' $verify "'vg_automation_lock'"
Require-Contains 'Runtime verifier allows complete draft status' $verify 'complete_draft'
Require-Contains 'Runtime verifier reports complete drafts' $verify 'Editorial complete drafts'
Require-Contains 'Runtime verifier checks no published briefs' $verify 'Published editorial briefs found'

Require-Contains 'Runbook states Posts source' $runbook 'WordPress Posts are the source of truth'
Require-Contains 'Runbook publish gate' $runbook 'Publish gate'
Require-Contains 'Runbook one article per day rule' $runbook 'one article per day'

Write-Output 'WordPress post editorial system static verification passed.'

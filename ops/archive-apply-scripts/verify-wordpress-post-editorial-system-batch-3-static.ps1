$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$applyPath = Join-Path $repoRoot 'ops/apply-wordpress-post-editorial-system-batch-3.php'
$verifyPath = Join-Path $repoRoot 'ops/verify-wordpress-post-editorial-system-batch-3.php'
$runbookPath = Join-Path $repoRoot 'docs/editorial/wordpress-post-editorial-system.md'
$calendarPath = Join-Path $repoRoot 'docs/editorial/daily-publication-operating-plan-2026-2027.md'

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

Require-File 'Batch 3 post brief apply script' $applyPath
Require-File 'Batch 3 post brief runtime verifier' $verifyPath
Require-File 'Post editorial system runbook' $runbookPath
Require-File 'Daily publication operating plan' $calendarPath

$apply = Get-Content -Raw -LiteralPath $applyPath
$verify = Get-Content -Raw -LiteralPath $verifyPath
$runbook = Get-Content -Raw -LiteralPath $runbookPath
$calendar = Get-Content -Raw -LiteralPath $calendarPath

Require-Contains 'Apply script is WP-CLI only' $apply 'This script must be run with WP-CLI.'
Require-Contains 'Apply script creates drafts only' $apply "'post_status' => 'draft'"
Require-Contains 'Apply script avoids scheduling' $apply 'No posts are published or scheduled by this script.'
Require-Contains 'Apply script guards duplicate slugs' $apply 'Expected at most one post with slug'
Require-Contains 'Apply script preserves non-draft collisions' $apply 'is not draft'
Require-Contains 'Apply script refuses outside-batch collision' $apply 'Draft slug already exists outside the Batch 3 workflow'
Require-Contains 'Apply script locks owner' $apply 'vg_content_owner'
Require-Contains 'Apply script locks automation' $apply 'vg_automation_lock'
Require-Contains 'Apply script marks batch' $apply 'batch-3-northern-mountains'
Require-Contains 'Apply script stores source plan' $apply 'vg_editorial_sources_to_check'
Require-Contains 'Apply script stores image plan' $apply 'vg_editorial_image_plan'
Require-Contains 'Apply script stores brief status' $apply 'vg_editorial_brief_status'
Require-Contains 'Apply script names admin workflow' $apply 'Native WordPress Post Batch 3 editorial brief created for manual expansion'

$expectedSlugs = @(
    'sapa-travel-guide',
    'ha-giang-loop-planning-guide',
    'hanoi-to-sapa-transport',
    'hanoi-to-ha-giang-transport',
    'ha-giang-safety-guide',
    'ha-giang-easy-rider-vs-self-drive',
    'sapa-trekking-guided-vs-self-guided',
    'where-to-stay-in-sapa',
    'best-time-for-northern-vietnam',
    'vietnam-rice-terraces-guide',
    'mu-cang-chai-travel-guide',
    'pu-luong-travel-guide'
)

foreach ($slug in $expectedSlugs) {
    Require-Contains "Apply script batch 3 slug $slug" $apply "'slug' => '$slug'"
    Require-Contains "Runtime verifier batch 3 slug $slug" $verify "'$slug'"
}

foreach ($tagSlug in @(
    'mountain-planning',
    'northern-vietnam',
    'transport-planning',
    'trekking-planning',
    'safety-planning',
    'rice-terraces',
    'weather-planning',
    'anti-spam-evergreen'
)) {
    Require-Contains "Apply script creates tag $tagSlug" $apply "'$tagSlug'"
}

foreach ($internalPath in @(
    '/destinations/hanoi-travel-guide/',
    '/plan/best-time-to-visit-vietnam/',
    '/plan/transport-within-vietnam/',
    '/plan/health-travel-insurance-vietnam/',
    '/plan/safety-scams-vietnam/',
    '/destinations/sapa-vs-ha-giang/',
    '/destinations/ninh-binh-travel-guide/',
    '/destinations/ha-long-bay-travel-guide/'
)) {
    Require-Contains "Apply script includes internal path $internalPath" $apply $internalPath
}

foreach ($evidencePhrase in @(
    'terrace timing',
    'license',
    'insurance',
    'weather',
    'fog',
    'route pressure',
    'self-drive',
    'easy rider',
    'private car',
    'not a keyword variant'
)) {
    Require-Contains "Apply script includes evidence phrase $evidencePhrase" $apply $evidencePhrase
}

Require-Contains 'Runtime verifier checks draft status' $verify "post_status !== 'draft'"
Require-Contains 'Runtime verifier checks native post type' $verify "post_type !== 'post'"
Require-Contains 'Runtime verifier checks title' $verify 'title mismatch'
Require-Contains 'Runtime verifier checks slug' $verify 'slug mismatch'
Require-Contains 'Runtime verifier checks comments closed' $verify 'comment_status'
Require-Contains 'Runtime verifier checks pings closed' $verify 'ping_status'
Require-Contains 'Runtime verifier checks batch meta' $verify "'vg_editorial_batch' => `$batch"
Require-Contains 'Runtime verifier checks admin owner' $verify "'vg_content_owner' => 'wp_admin'"
Require-Contains 'Runtime verifier checks automation lock' $verify "'vg_automation_lock' => 'locked'"
Require-Contains 'Runtime verifier checks target publish date' $verify 'vg_editorial_target_publish_date'
Require-Contains 'Runtime verifier checks source plan' $verify "'vg_editorial_sources_to_check'"
Require-Contains 'Runtime verifier checks image plan' $verify "'vg_editorial_image_plan'"
Require-Contains 'Runtime verifier checks no published batch briefs' $verify 'Published batch 3 editorial briefs found'
Require-Contains 'Runtime verifier queries all batch posts' $verify '$batch_posts = get_posts'
Require-Contains 'Runtime verifier builds expected slug set' $verify '$expected_slugs = array_keys($expected_briefs)'
Require-Contains 'Runtime verifier checks batch slug set exactly' $verify 'Batch 3 editorial batch slug set mismatch'
Require-Contains 'Runtime verifier reports total batch posts' $verify 'Batch 3 editorial total batch posts'
Require-Contains 'Runtime verifier allows complete draft status' $verify 'complete_draft'
Require-Contains 'Runtime verifier reports complete drafts' $verify 'complete_draft_count'
Require-Contains 'Runtime verifier blocks complete draft placeholder text' $verify 'still contains draft-status placeholder guidance'
Require-Contains 'Runtime verifier checks exact categories' $verify 'category'
Require-Contains 'Runtime verifier checks exact tags' $verify 'post_tag'

Require-Contains 'Runbook mentions batch 3' $runbook 'Batch 3'
Require-Contains 'Runbook mentions northern mountains' $runbook 'northern mountains'
Require-Contains 'Calendar includes Sapa Travel Guide' $calendar 'Sapa Travel Guide'
Require-Contains 'Calendar includes Ha Giang Loop Planning Guide' $calendar 'Ha Giang Loop Planning Guide'
Require-Contains 'Calendar includes Pu Luong Travel Guide' $calendar 'Pu Luong Travel Guide'

Write-Output 'WordPress post editorial system batch 3 static verification passed.'

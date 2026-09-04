$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$corePath = Join-Path $repoRoot 'wordpress/wp-content/mu-plugins/vietnamguide-core.php'
$markPath = Join-Path $repoRoot 'ops/mark-wordpress-admin-owned-content.php'
$verifyPath = Join-Path $repoRoot 'ops/verify-admin-first-workflow.php'
$runbookPath = Join-Path $repoRoot 'docs/editorial/wordpress-admin-first-operating-model.md'
$planPath = Join-Path $repoRoot 'docs/superpowers/plans/2026-07-25-wordpress-admin-first-workflow.md'

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

Require-File 'MU plugin' $corePath
Require-File 'Admin-first marker script' $markPath
Require-File 'Admin-first runtime verifier' $verifyPath
Require-File 'Admin-first runbook' $runbookPath
Require-File 'Admin-first implementation plan' $planPath

$core = Get-Content -Raw -LiteralPath $corePath
$marker = Get-Content -Raw -LiteralPath $markPath
$runtimeVerifier = Get-Content -Raw -LiteralPath $verifyPath
$runbook = Get-Content -Raw -LiteralPath $runbookPath
$plan = Get-Content -Raw -LiteralPath $planPath

Require-Contains 'MU plugin version' $core 'Version: 0.1.5'
Require-Contains 'Admin-first meta keys' $core 'VG_ADMIN_FIRST_META_KEYS'
Require-Contains 'Content owner field' $core 'vg_content_owner'
Require-Contains 'Automation lock field' $core 'vg_automation_lock'
Require-Contains 'Manual review field' $core 'vg_last_manual_review'
Require-Contains 'Post write guard hook' $core "add_filter('wp_insert_post_data', 'vg_admin_first_protect_post_writes', 1, 4)"
Require-Contains 'Post meta guard hook' $core "add_filter('update_post_metadata', 'vg_admin_first_protect_post_meta', 1, 5)"
Require-Contains 'WP-CLI override environment' $core 'VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE'
Require-Contains 'Automation content hash' $core '_vg_last_automation_content_hash'
Require-Contains 'Automation meta hash prefix' $core '_vg_last_automation_meta_hash_'
Require-Contains 'Manual content drift detection' $core 'vg_admin_first_post_content_has_manual_drift'
Require-Contains 'Manual meta drift detection' $core 'vg_admin_first_meta_has_manual_drift'
Require-Contains 'Post automation hash action' $core "add_action('wp_after_insert_post', 'vg_admin_first_record_post_automation_hash', 20, 4)"
Require-Contains 'Meta automation hash action' $core "add_action('updated_post_meta', 'vg_admin_first_record_protected_meta_automation_hash', 20, 4)"
Require-Contains 'Rank Math meta protection' $core 'str_starts_with($meta_key, ''rank_math_'')'
Require-Contains 'EEAT meta protection' $core 'str_starts_with($meta_key, ''vg_eeat_'')'
Require-Contains 'ACF admin workflow group' $core 'group_vg_admin_first_workflow'

Require-Contains 'Marker defaults dry run' $marker "VG_ADMIN_FIRST_MARK_APPLY"
Require-Contains 'Marker requires explicit scope' $marker "VG_ADMIN_FIRST_INCLUDE_PATHS"
Require-Contains 'Marker supports all content flag' $marker "VG_ADMIN_FIRST_MARK_ALL"
Require-Contains 'Marker writes content owner' $marker 'update_post_meta($post_id, ''vg_content_owner'', ''wp_admin'')'
Require-Contains 'Marker writes automation lock' $marker 'update_post_meta($post_id, ''vg_automation_lock'', ''locked'')'

Require-Contains 'Runtime verifier checks post write guard' $runtimeVerifier "has_filter('wp_insert_post_data', 'vg_admin_first_protect_post_writes')"
Require-Contains 'Runtime verifier checks metadata guard' $runtimeVerifier "has_filter('update_post_metadata', 'vg_admin_first_protect_post_meta')"
Require-Contains 'Runtime verifier reports admin-owned content' $runtimeVerifier 'Admin-first locked content'
Require-Contains 'Runtime verifier reports automation content baselines' $runtimeVerifier 'Automation content baselines'
Require-Contains 'Runtime verifier reports automation meta baselines' $runtimeVerifier 'Automation protected meta baselines'

Require-Contains 'Runbook source of truth' $runbook 'WordPress Admin is the source of truth'
Require-Contains 'Runbook override warning' $runbook 'VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1'
Require-Contains 'Runbook Pages versus Posts' $runbook 'Pages vs Posts'
Require-Contains 'Runbook verification-only' $runbook 'verification-only'

Require-Contains 'Plan header' $plan '# WordPress Admin-First Workflow Implementation Plan'
Require-Contains 'Plan guard task' $plan 'Task 2: Runtime Guard'

Write-Output 'Admin-first static workflow verification passed.'

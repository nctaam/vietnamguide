$ErrorActionPreference = 'Stop'

$RepoRoot = Split-Path -Parent $PSScriptRoot
$PluginPath = 'wordpress/wp-content/mu-plugins/vietnamguide-core.php'
$ThemeFunctionsPath = 'wordpress/wp-content/themes/vietnamguide-premium/functions.php'
$Failures = [System.Collections.Generic.List[string]]::new()

function Get-RepoFileContent {
    param(
        [string]$RelativePath
    )

    $FullPath = Join-Path $RepoRoot $RelativePath
    $Content = Get-Content -LiteralPath $FullPath -Raw
    if ($null -eq $Content) {
        return ''
    }

    return $Content
}

function Require-Contains {
    param(
        [string]$RelativePath,
        [string]$Needle
    )

    $Content = Get-RepoFileContent $RelativePath
    if (-not $Content.Contains($Needle)) {
        $Failures.Add("Missing substring in ${RelativePath}: $Needle")
    }
}

function Require-NotContains {
    param(
        [string]$RelativePath,
        [string]$Needle
    )

    $Content = Get-RepoFileContent $RelativePath
    if ($Content.Contains($Needle)) {
        $Failures.Add("Unexpected substring in ${RelativePath}: $Needle")
    }
}

function Require-Matches {
    param(
        [string]$RelativePath,
        [string]$Pattern
    )

    $Content = Get-RepoFileContent $RelativePath
    if (-not [regex]::IsMatch($Content, $Pattern)) {
        $Failures.Add("Missing pattern in ${RelativePath}: $Pattern")
    }
}

function Require-NotMatches {
    param(
        [string]$RelativePath,
        [string]$Pattern
    )

    $Content = Get-RepoFileContent $RelativePath
    if ([regex]::IsMatch($Content, $Pattern)) {
        $Failures.Add("Unexpected pattern in ${RelativePath}: $Pattern")
    }
}

$PluginFullPath = Join-Path $RepoRoot $PluginPath
if (-not (Test-Path -LiteralPath $PluginFullPath -PathType Leaf)) {
    Write-Output "FAIL: Missing file: $PluginPath"
    exit 1
}

# Plugin identity and direct-access protection.
Require-Matches $PluginPath '(?m)^\s*\*\s*Plugin Name:\s*VietnamGuide Core\s*$'
Require-Matches $PluginPath '(?m)^\s*\*\s*Version:\s*0\.1\.6\s*$'
Require-Matches $PluginPath '(?s)defined\s*\(\s*[''"]ABSPATH[''"]\s*\).*?\b(?:exit|die)\b'

# Production compatibility contracts retained from the deployed plugin.
Require-Contains $PluginPath 'VG_EEAT_META_KEYS'
Require-Contains $PluginPath 'VG_ADMIN_FIRST_META_KEYS'

foreach ($Shortcode in @('vg_editorial_proof', 'vg_source_trail', 'vg_update_log', 'vg_related_routes')) {
    Require-Matches $PluginPath ('add_shortcode\s*\(\s*[''"]{0}[''"]' -f [regex]::Escape($Shortcode))
}

Require-Matches $PluginPath 'add_action\s*\(\s*[''"]acf/init[''"]\s*,\s*[''"]vg_register_eeat_acf_fields[''"]\s*\)'
Require-Matches $PluginPath 'add_action\s*\(\s*[''"]acf/init[''"]\s*,\s*[''"]vg_register_admin_first_acf_fields[''"]\s*\)'
Require-Matches $PluginPath 'add_filter\s*\(\s*[''"]wp_insert_post_data[''"]'
Require-Matches $PluginPath 'add_action\s*\(\s*[''"]wp_after_insert_post[''"]'
Require-Matches $PluginPath 'add_filter\s*\(\s*[''"]update_post_metadata[''"]'
Require-Matches $PluginPath 'add_action\s*\(\s*[''"]updated_post_meta[''"]'
Require-Matches $PluginPath 'add_action\s*\(\s*[''"]added_post_meta[''"]'

Require-Matches $PluginPath 'add_filter\s*\(\s*[''"]xmlrpc_enabled[''"]'
Require-Matches $PluginPath 'add_action\s*\(\s*[''"]init[''"]\s*,\s*[''"]vg_block_xmlrpc_request[''"]'
Require-Matches $PluginPath 'add_action\s*\(\s*[''"]template_redirect[''"]'
Require-Matches $PluginPath 'add_filter\s*\(\s*[''"]rest_endpoints[''"]'
Require-Matches $PluginPath 'is_user_logged_in\s*\(\s*\)'
Require-Contains $PluginPath "unset(`$endpoints['/wp/v2/users'], `$endpoints['/wp/v2/users/(?P<id>[\d]+)']);"
Require-Matches $PluginPath 'add_filter\s*\(\s*[''"]robots_txt[''"]'

# Site-level block pattern category ownership, with compatibility for older WordPress versions.
Require-Matches $PluginPath 'function\s+vg_register_pattern_category\s*\('
Require-Matches $PluginPath 'function_exists\s*\(\s*[''"]register_block_pattern_category[''"]\s*\)'
Require-Contains $PluginPath 'WP_Block_Pattern_Categories_Registry'
Require-Matches $PluginPath 'WP_Block_Pattern_Categories_Registry::get_instance\s*\(\s*\)'
Require-Matches $PluginPath 'is_registered\s*\(\s*[''"]vietnamguide[''"]\s*\)'
Require-Matches $PluginPath 'register_block_pattern_category\s*\(\s*[''"]vietnamguide[''"]'
Require-NotMatches $ThemeFunctionsPath 'register_block_pattern_category\s*\(\s*[''"]vietnamguide[''"]'

# Affiliate anchors are parsed structurally and retain their existing rel tokens.
Require-Contains $PluginPath 'WP_HTML_Tag_Processor'
Require-Matches $PluginPath 'new\s+WP_HTML_Tag_Processor\s*\('
Require-NotContains $PluginPath 'preg_replace_callback'
Require-Matches $PluginPath 'next_tag\s*\(\s*\[[^\]]*[''"]tag_name[''"]\s*=>\s*[''"]A[''"][^\]]*[''"]class_name[''"]\s*=>\s*[''"]vg-affiliate-link[''"]'
Require-Matches $PluginPath 'get_attribute\s*\(\s*[''"]rel[''"]\s*\)'
Require-Matches $PluginPath 'vg_merge_affiliate_rel_tokens\s*\('
Require-Matches $PluginPath 'set_attribute\s*\(\s*[''"]rel[''"]'
Require-Matches $PluginPath 'class_exists\s*\(\s*[''"]WP_HTML_Tag_Processor[''"]\s*\)'
Require-Matches $PluginPath '(?s)if\s*\(\s*!\s*class_exists\s*\(\s*[''"]WP_HTML_Tag_Processor[''"]\s*\)\s*\)\s*\{\s*return\s+\$content\s*;'
Require-Matches $PluginPath 'is_admin\s*\(\s*\)\s*\|\|\s*!\s*is_singular\s*\(\s*\)'
Require-Matches $PluginPath 'add_filter\s*\(\s*[''"]the_content[''"]\s*,[^;]+,\s*20\s*\)'

# The merge helper must preserve arbitrary tokens and add each disclosure token only once.
Require-Matches $PluginPath 'function\s+vg_merge_affiliate_rel_tokens\s*\('
Require-Matches $PluginPath 'preg_split\s*\(\s*[''"]/\\s\+/'
Require-Matches $PluginPath 'strtolower\s*\(\s*\$token\s*\)'
Require-Matches $PluginPath '\$merged_tokens\[\]\s*=\s*\$token\s*;'
Require-Matches $PluginPath 'foreach\s*\(\s*\[[''"]sponsored[''"]\s*,\s*[''"]nofollow[''"]\]\s+as\s+\$required_token\s*\)'
Require-Matches $PluginPath 'isset\s*\(\s*\$seen_tokens\[\$required_token\]\s*\)'
Require-Matches $PluginPath 'return\s+implode\s*\(\s*[''"] [''"]\s*,\s*\$merged_tokens\s*\)\s*;'

# Exact image contracts and lifecycle hook.
Require-Matches $PluginPath 'add_action\s*\(\s*[''"]after_setup_theme[''"]'
Require-Matches $PluginPath 'add_image_size\s*\(\s*[''"]vg-hero[''"]\s*,\s*1920\s*,\s*1080\s*,\s*true\s*\)'
Require-Matches $PluginPath 'add_image_size\s*\(\s*[''"]vg-editorial-wide[''"]\s*,\s*1440\s*,\s*900\s*,\s*true\s*\)'
Require-Matches $PluginPath 'add_image_size\s*\(\s*[''"]vg-card[''"]\s*,\s*720\s*,\s*540\s*,\s*true\s*\)'

if ($Failures.Count -gt 0) {
    foreach ($Failure in $Failures) {
        Write-Output "FAIL: $Failure"
    }

    exit 1
}

Write-Output 'VietnamGuide core mu-plugin checks passed.'

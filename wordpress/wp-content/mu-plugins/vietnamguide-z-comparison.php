<?php

declare(strict_types=1);

$vg_comparison_constants = [
    'VG_COMPARISON_BUNDLE_SCHEMA_CURRENT' => '2',
    'VG_COMPARISON_BUNDLE_SCHEMA_PREVIOUS' => '1',
    'VG_COMPARISON_ACTIVATION_ARTIFACT_VERSION' => 'activation-v2',
];
foreach ($vg_comparison_constants as $vg_comparison_name => $vg_comparison_value) {
    if (defined($vg_comparison_name) && constant($vg_comparison_name) !== $vg_comparison_value) {
        return;
    }
}
foreach ($vg_comparison_constants as $vg_comparison_name => $vg_comparison_value) {
    if (!defined($vg_comparison_name)) {
        define($vg_comparison_name, $vg_comparison_value);
    }
}
unset($vg_comparison_constants, $vg_comparison_name, $vg_comparison_value);

function vg_comparison_log_rejection(string $code, int $post_id): void
{
    static $logged = [];
    static $events = 0;

    $allowed = [
        'E_ACTIVATION', 'E_ACTIVATION_VERSION', 'E_BOM', 'E_BUNDLE_HASH', 'E_CONTROL',
        'E_INACTIVE', 'E_JSON', 'E_JSON_DUPLICATE', 'E_MANIFEST_VERSION', 'E_META',
        'E_MIGRATION', 'E_ORGANIZATION_REGISTRY_VERSION', 'E_PATH', 'E_POST', 'E_POST_ID',
        'E_RUNTIME', 'E_SCHEMA', 'E_SIZE', 'E_SOURCE_REGISTRY_VERSION', 'E_UTF8', 'E_VERSION',
    ];
    if (!in_array($code, $allowed, true) || $post_id < 1 || $events >= 8) {
        return;
    }
    $dedupe_key = $code . ':' . $post_id;
    if (isset($logged[$dedupe_key])) {
        return;
    }
    $logged[$dedupe_key] = true;
    ++$events;

    $schema = defined('VG_COMPARISON_BUNDLE_SCHEMA_CURRENT') ? VG_COMPARISON_BUNDLE_SCHEMA_CURRENT : '0';
    $hash_prefix = function_exists('vg_comparison_log_bundle_hash_prefix')
        ? vg_comparison_log_bundle_hash_prefix($post_id)
        : 'none';
    if (!is_string($hash_prefix) || preg_match('/^[a-f0-9]{8}$/D', $hash_prefix) !== 1) {
        $hash_prefix = 'none';
    }
    error_log(sprintf(
        'VG_COMPARISON_REJECT code=%s post_id=%d schema=%s hash_prefix=%s',
        $code,
        $post_id,
        $schema,
        $hash_prefix
    ));
}

$bundle_file = __DIR__ . '/vietnamguide-comparison/bundle.php';
require_once $bundle_file;

$render_file = __DIR__ . '/vietnamguide-comparison/render.php';
if (is_file($render_file)) {
    require_once $render_file;
}

$shortcodes_file = __DIR__ . '/vietnamguide-comparison/shortcodes.php';
if (is_file($shortcodes_file)) {
    require_once $shortcodes_file;
}

if (is_callable('vg_comparison_register_shortcode_replacements') && function_exists('add_action')) {
    add_action('init', 'vg_comparison_register_shortcode_replacements', 20);
}


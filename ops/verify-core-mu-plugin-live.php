<?php
/**
 * Runtime verification for the VietnamGuide core must-use plugin.
 *
 * Run with: wp eval-file /path/to/verify-core-mu-plugin-live.php
 */

if (! defined('ABSPATH')) {
    fwrite(STDERR, "FAIL: WordPress is not loaded. Run this file with wp eval-file.\n");
    exit(1);
}

$failures = [];

$fail = static function (string $message) use (&$failures): void {
    $failures[] = $message;
};

$plugin_path = WPMU_PLUGIN_DIR . '/vietnamguide-core.php';
$expected_plugin_sha256 = '76313bc2537a25decf743f5db7b2b93be1c1431e546efb96e48e50d99afd20cc';

if (! is_file($plugin_path)) {
    $fail('Core must-use plugin does not exist at the expected WordPress path.');
} elseif (! is_readable($plugin_path)) {
    $fail('Core must-use plugin is not readable at the expected WordPress path.');
} else {
    $actual_plugin_sha256 = hash_file('sha256', $plugin_path);

    if (! is_string($actual_plugin_sha256)) {
        $fail('Core must-use plugin SHA256 could not be calculated.');
    } elseif (! hash_equals($expected_plugin_sha256, $actual_plugin_sha256)) {
        $fail(sprintf(
            'Core must-use plugin SHA256 mismatch: expected %s, found %s.',
            $expected_plugin_sha256,
            $actual_plugin_sha256
        ));
    }

    $plugin_data = get_file_data($plugin_path, ['Version' => 'Version']);
    $version = isset($plugin_data['Version']) ? trim((string) $plugin_data['Version']) : '';

    if ($version !== '0.1.6') {
        $fail(sprintf('Core must-use plugin version mismatch: expected 0.1.6, found %s.', $version !== '' ? $version : '(missing)'));
    }
}

if (! class_exists('WP_HTML_Tag_Processor')) {
    $fail('WP_HTML_Tag_Processor is unavailable.');
}

$required_functions = [
    'vg_register_pattern_category',
    'vg_add_affiliate_link_attributes',
    'vg_merge_affiliate_rel_tokens',
    'vg_register_image_sizes',
    'vg_shortcode_editorial_proof_panel',
    'vg_shortcode_source_trail',
    'vg_shortcode_update_log',
    'vg_shortcode_related_routes',
    'vg_register_eeat_acf_fields',
    'vg_register_admin_first_acf_fields',
    'vg_admin_first_protect_post_writes',
    'vg_admin_first_record_post_automation_hash',
    'vg_admin_first_protect_post_meta',
    'vg_admin_first_record_protected_meta_automation_hash',
    'vg_block_xmlrpc_request',
    'vg_remove_legacy_discovery_links',
    'vg_block_author_enumeration',
    'vg_disable_public_user_rest_endpoints',
    'vg_add_sitemap_to_robots',
];

foreach ($required_functions as $function_name) {
    if (! function_exists($function_name)) {
        $fail(sprintf('Required core function is unavailable: %s.', $function_name));
    }
}

$required_shortcodes = [
    'vg_editorial_proof' => 'vg_shortcode_editorial_proof_panel',
    'vg_source_trail' => 'vg_shortcode_source_trail',
    'vg_update_log' => 'vg_shortcode_update_log',
    'vg_related_routes' => 'vg_shortcode_related_routes',
];

foreach ($required_shortcodes as $shortcode => $callback) {
    if (! shortcode_exists($shortcode)) {
        $fail(sprintf('Required shortcode is not registered: %s.', $shortcode));
        continue;
    }

    if (($GLOBALS['shortcode_tags'][$shortcode] ?? null) !== $callback) {
        $fail(sprintf('Shortcode %s is not registered to %s.', $shortcode, $callback));
    }
}

if (! class_exists('WP_Block_Pattern_Categories_Registry')) {
    $fail('Block pattern category registry is unavailable.');
} else {
    $pattern_registry = WP_Block_Pattern_Categories_Registry::get_instance();

    if (! $pattern_registry->is_registered('vietnamguide')) {
        $fail('The vietnamguide block pattern category is not registered.');
    }
}

$required_image_sizes = [
    'vg-hero' => ['width' => 1920, 'height' => 1080, 'crop' => true],
    'vg-editorial-wide' => ['width' => 1440, 'height' => 900, 'crop' => true],
    'vg-card' => ['width' => 720, 'height' => 540, 'crop' => true],
];
$additional_image_sizes = $GLOBALS['_wp_additional_image_sizes'] ?? [];

foreach ($required_image_sizes as $size_name => $expected) {
    $actual = $additional_image_sizes[$size_name] ?? null;

    if (! is_array($actual)) {
        $fail(sprintf('Required additional image size is not registered: %s.', $size_name));
        continue;
    }

    foreach ($expected as $property => $expected_value) {
        if (($actual[$property] ?? null) !== $expected_value) {
            $fail(sprintf(
                'Image size %s has incorrect %s: expected %s, found %s.',
                $size_name,
                $property,
                var_export($expected_value, true),
                var_export($actual[$property] ?? null, true)
            ));
        }
    }
}

if (function_exists('vg_add_affiliate_link_attributes') && class_exists('WP_HTML_Tag_Processor')) {
    $had_original_query = array_key_exists('wp_query', $GLOBALS);
    $original_query = $GLOBALS['wp_query'] ?? null;

    try {
        $singular_query = new WP_Query();
        $singular_query->is_singular = true;
        $singular_query->is_page = true;
        $singular_query->is_404 = false;
        $GLOBALS['wp_query'] = $singular_query;

        $editorial_anchor = '<a id="editorial-link" rel="noopener external" href="https://example.com/editorial">Editorial</a>';
        $input = '<p>'
            . '<a id="affiliate-one" class="vg-affiliate-link" href="https://example.com/first">First affiliate</a>'
            . '<a id="affiliate-two" class="offer vg-affiliate-link featured" rel="NoFoLlOw ugc SPONSORED nofollow UGC external" href="https://example.com/second">Second affiliate</a>'
            . $editorial_anchor
            . '</p>';
        $first_pass = vg_add_affiliate_link_attributes($input);
        $second_pass = vg_add_affiliate_link_attributes($first_pass);

        if ($second_pass !== $first_pass) {
            $fail('Affiliate link processing is not idempotent on a second call.');
        }

        if (substr_count($first_pass, $editorial_anchor) !== 1) {
            $fail('Non-affiliate anchor markup was modified, duplicated, or removed.');
        }

        $processor = new WP_HTML_Tag_Processor($first_pass);
        $anchor_rels = [];

        while ($processor->next_tag(['tag_name' => 'A'])) {
            $id = $processor->get_attribute('id');
            $rel = $processor->get_attribute('rel');

            if (is_string($id)) {
                $anchor_rels[$id] = is_string($rel) ? $rel : '';
            }
        }

        foreach (['affiliate-one', 'affiliate-two', 'editorial-link'] as $required_anchor) {
            if (! array_key_exists($required_anchor, $anchor_rels)) {
                $fail(sprintf('Affiliate runtime fixture anchor was lost: %s.', $required_anchor));
            }
        }

        foreach (['affiliate-one', 'affiliate-two'] as $affiliate_anchor) {
            if (! array_key_exists($affiliate_anchor, $anchor_rels)) {
                continue;
            }

            $tokens = preg_split('/\s+/', trim($anchor_rels[$affiliate_anchor])) ?: [];
            $lower_tokens = array_map('strtolower', array_values(array_filter($tokens, static fn (string $token): bool => $token !== '')));

            foreach (['sponsored', 'nofollow'] as $required_token) {
                if (count(array_keys($lower_tokens, $required_token, true)) !== 1) {
                    $fail(sprintf('Affiliate anchor %s must contain %s exactly once.', $affiliate_anchor, $required_token));
                }
            }
        }

        if (isset($anchor_rels['affiliate-one']) && strtolower($anchor_rels['affiliate-one']) !== 'sponsored nofollow') {
            $fail('Affiliate anchor without rel did not receive exactly sponsored and nofollow.');
        }

        if (isset($anchor_rels['affiliate-two'])) {
            $mixed_tokens = preg_split('/\s+/', trim($anchor_rels['affiliate-two'])) ?: [];
            $mixed_lower_tokens = array_map('strtolower', $mixed_tokens);

            foreach (['ugc', 'external'] as $unrelated_token) {
                if (count(array_keys($mixed_lower_tokens, $unrelated_token, true)) !== 1) {
                    $fail(sprintf('Affiliate processing did not preserve unrelated rel token exactly once: %s.', $unrelated_token));
                }
            }

            if (! in_array('NoFoLlOw', $mixed_tokens, true) || ! in_array('SPONSORED', $mixed_tokens, true)) {
                $fail('Affiliate processing did not preserve the original casing of existing required rel tokens.');
            }
        }

        if (isset($anchor_rels['editorial-link']) && $anchor_rels['editorial-link'] !== 'noopener external') {
            $fail('Non-affiliate anchor rel attributes were modified.');
        }
    } catch (Throwable $throwable) {
        $fail(sprintf('Affiliate runtime verification threw %s: %s', get_class($throwable), $throwable->getMessage()));
    } finally {
        if ($had_original_query) {
            $GLOBALS['wp_query'] = $original_query;
        } else {
            unset($GLOBALS['wp_query']);
        }
    }
}

if ($failures !== []) {
    $message = "VietnamGuide core runtime verification failed:\n- " . implode("\n- ", $failures);

    if (class_exists('WP_CLI')) {
        WP_CLI::error($message);
    }

    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

if (class_exists('WP_CLI')) {
    WP_CLI::success('VietnamGuide core runtime verification passed for version 0.1.6.');
} else {
    fwrite(STDOUT, "SUCCESS: VietnamGuide core runtime verification passed for version 0.1.6.\n");
}

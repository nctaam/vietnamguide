<?php
/**
 * Trigger LiteSpeed Cache purge-all through WordPress hooks.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/purge-litespeed-cache.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (has_action('litespeed_purge_all')) {
    do_action('litespeed_purge_all');

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('LiteSpeed purge-all action fired.');
        return;
    }

    echo 'LiteSpeed purge-all action fired.' . PHP_EOL;
    return;
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::warning('LiteSpeed purge-all action hook was not registered.');
    return;
}

echo 'LiteSpeed purge-all action hook was not registered.' . PHP_EOL;

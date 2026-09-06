<?php
/**
 * Publish Seasonal & Tet cluster drafts into live Guide Experience pages.
 *
 * Targets:
 * - ID 493: vietnam-in-december               -> plan (parent 6)
 * - ID 494: vietnam-in-january                -> plan (parent 6)
 * - ID 495: vietnam-in-february               -> plan (parent 6)
 * - ID 496: tet-in-vietnam-travel-guide       -> plan (parent 6)
 * - ID 482: vietnam-rainy-season-flexible-route -> plan (parent 6)
 */

if (! defined('ABSPATH')) {
    exit('Run via WP-CLI: wp eval-file ops/publish-seasonal-tet-cluster.php --allow-root' . PHP_EOL);
}

$targets = [
    493 => [
        'slug' => 'vietnam-in-december',
        'title' => 'Vietnam in December: Weather, Routes and What to Book Early',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
    ],
    494 => [
        'slug' => 'vietnam-in-january',
        'title' => 'Vietnam in January: Best Routes, Weather and Tet Watchouts',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
    ],
    495 => [
        'slug' => 'vietnam-in-february',
        'title' => 'Vietnam in February: Weather, Tet Timing and Best Routes',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
    ],
    496 => [
        'slug' => 'tet-in-vietnam-travel-guide',
        'title' => 'Tet in Vietnam Travel Guide: What International Visitors Should Know',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
    ],
    482 => [
        'slug' => 'vietnam-rainy-season-flexible-route',
        'title' => 'Vietnam Rainy Season Travel: How to Build a Flexible Route',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
    ],
];

echo "=== Publishing Seasonal & Tet Cluster Pages ===" . PHP_EOL;

$results = [];
foreach ($targets as $id => $target) {
    $post = get_post($id);
    if (! $post) {
        echo "[ERROR] Post ID {$id} not found!" . PHP_EOL;
        continue;
    }

    echo "Updating Post ID {$id} ({$target['slug']})..." . PHP_EOL;

    $parent = get_post($target['parent_id']);
    if (! $parent) {
        echo "[ERROR] Parent ID {$target['parent_id']} not found for {$target['slug']}!" . PHP_EOL;
        continue;
    }

    $update_data = [
        'ID' => $id,
        'post_type' => 'page',
        'post_parent' => $target['parent_id'],
        'post_status' => 'publish',
        'post_name' => $target['slug'],
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ];

    $updated_id = wp_update_post($update_data, true);
    if (is_wp_error($updated_id)) {
        echo "[ERROR] Failed to update ID {$id}: " . $updated_id->get_error_message() . PHP_EOL;
        continue;
    }

    update_post_meta($id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
    update_post_meta($id, 'vg_eeat_reviewed_guide', '1');
    update_post_meta($id, 'vg_editorial_brief_status', 'published');

    clean_post_cache($id);

    $fresh_post = get_post($id);
    $resolved_uri = get_page_uri($fresh_post);
    $expected_uri = $target['parent_slug'] . '/' . $target['slug'];

    $results[$id] = [
        'id' => $id,
        'title' => $fresh_post->post_title,
        'type' => $fresh_post->post_type,
        'status' => $fresh_post->post_status,
        'parent_id' => $fresh_post->post_parent,
        'resolved_uri' => $resolved_uri,
        'expected_uri' => $expected_uri,
        'uri_match' => ($resolved_uri === $expected_uri),
        'permalink' => get_permalink($id),
    ];

    echo "  -> OK: {$resolved_uri} (permalink: " . get_permalink($id) . ")" . PHP_EOL;
}

flush_rewrite_rules(false);
echo "Rewrites flushed." . PHP_EOL;

echo PHP_EOL . "=== Publication Summary ===" . PHP_EOL;
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

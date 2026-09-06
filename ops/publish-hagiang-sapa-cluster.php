<?php
/**
 * Publish Ha Giang & Sapa cluster drafts into live Guide Experience pages.
 *
 * Targets:
 * - ID 519: ha-giang-loop-planning-guide -> destinations (parent 7)
 * - ID 521: hanoi-to-ha-giang-transport   -> plan (parent 6)
 * - ID 518: sapa-travel-guide            -> destinations (parent 7)
 * - ID 520: hanoi-to-sapa-transport      -> plan (parent 6)
 * - ID 504: sapa-vs-ha-giang             -> compare (parent 9)
 */

if (! defined('ABSPATH')) {
    exit('Run via WP-CLI: wp eval-file ops/publish-hagiang-sapa-cluster.php --allow-root' . PHP_EOL);
}

$targets = [
    519 => [
        'slug' => 'ha-giang-loop-planning-guide',
        'title' => 'Ha Giang Loop Planning Guide: Safety, Scenery and Route Fit',
        'parent_id' => 7,
        'parent_slug' => 'destinations',
        'expected_type' => 'destination',
    ],
    521 => [
        'slug' => 'hanoi-to-ha-giang-transport',
        'title' => 'Hanoi to Ha Giang Transport: Bus, Private Car or Staged Route?',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
    ],
    518 => [
        'slug' => 'sapa-travel-guide',
        'title' => 'Sapa Travel Guide: Terraces, Trekking and Softer Travel',
        'parent_id' => 7,
        'parent_slug' => 'destinations',
        'expected_type' => 'destination',
    ],
    520 => [
        'slug' => 'hanoi-to-sapa-transport',
        'title' => 'Hanoi to Sapa Transport: Train, Cabin Bus or Private Car?',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
    ],
    504 => [
        'slug' => 'sapa-vs-ha-giang',
        'title' => 'Sapa vs Ha Giang: Terraces, Loop Roads or Softer Mountain Travel?',
        'parent_id' => 9,
        'parent_slug' => 'compare',
        'expected_type' => 'comparison',
    ],
];

echo "=== Publishing Ha Giang & Sapa Cluster Pages ===" . PHP_EOL;

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

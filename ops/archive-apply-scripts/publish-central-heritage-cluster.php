<?php
/**
 * Publish Central Heritage & Key Comparison cluster drafts into live Guide Experience pages.
 *
 * Targets:
 * - ID 499: hoi-an-ancient-town-guide               -> destinations (parent 7)
 * - ID 500: hue-imperial-city-guide                 -> destinations (parent 7)
 * - ID 501: da-nang-beaches-guide                   -> destinations (parent 7)
 * - ID 503: phong-nha-travel-guide                  -> destinations (parent 7)
 * - ID 498: hanoi-vs-ho-chi-minh-city               -> compare (parent 9)
 * - ID 502: mekong-delta-overnight-vs-day-trip      -> compare (parent 9)
 * - ID 474: best-vietnam-routes-first-time-visitors -> plan (parent 6)
 */

if (! defined('ABSPATH')) {
    exit('Run via WP-CLI: wp eval-file ops/publish-central-heritage-cluster.php --allow-root' . PHP_EOL);
}

$targets = [
    499 => [
        'slug' => 'hoi-an-ancient-town-guide',
        'title' => 'Hoi An Ancient Town Guide: When to Stay, Visit or Skip',
        'parent_id' => 7,
        'parent_slug' => 'destinations',
        'expected_type' => 'destination',
    ],
    500 => [
        'slug' => 'hue-imperial-city-guide',
        'title' => 'Hue Imperial City Guide: How to Visit Without Rushing',
        'parent_id' => 7,
        'parent_slug' => 'destinations',
        'expected_type' => 'destination',
    ],
    501 => [
        'slug' => 'da-nang-beaches-guide',
        'title' => 'Da Nang Beaches Guide: My Khe, Non Nuoc or Hoi An Coast?',
        'parent_id' => 7,
        'parent_slug' => 'destinations',
        'expected_type' => 'destination',
    ],
    503 => [
        'slug' => 'phong-nha-travel-guide',
        'title' => 'Phong Nha Travel Guide: Caves, Seasons and Route Fit',
        'parent_id' => 7,
        'parent_slug' => 'destinations',
        'expected_type' => 'destination',
    ],
    498 => [
        'slug' => 'hanoi-vs-ho-chi-minh-city',
        'title' => 'Hanoi vs Ho Chi Minh City: Which Should You Visit First?',
        'parent_id' => 9,
        'parent_slug' => 'compare',
        'expected_type' => 'comparison',
    ],
    502 => [
        'slug' => 'mekong-delta-overnight-vs-day-trip',
        'title' => 'Mekong Delta Overnight vs Day Trip: Which Is Worth It?',
        'parent_id' => 9,
        'parent_slug' => 'compare',
        'expected_type' => 'comparison',
    ],
    474 => [
        'slug' => 'best-vietnam-routes-first-time-visitors',
        'title' => 'Best Vietnam Routes for First-Time Visitors: North, Central, South or Open-Jaw?',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
    ],
];

echo "=== Publishing Central Heritage & Key Comparison Cluster Pages ===" . PHP_EOL;

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

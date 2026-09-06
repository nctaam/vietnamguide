<?php
/**
 * Publish Planning & Preparation cluster drafts into live Guide Experience pages.
 *
 * Targets:
 * - ID 473: vietnam-first-trip-planning-checklist            -> plan (parent 6)
 * - ID 475: what-to-pack-for-vietnam-region-season          -> plan (parent 6)
 * - ID 476: vietnam-airport-arrival-checklist               -> plan (parent 6)
 * - ID 480: vietnam-food-safety-street-food-etiquette       -> plan (parent 6)
 * - ID 481: where-to-stay-in-vietnam-base-decisions         -> plan (parent 6)
 * - ID 477: hanoi-first-time-visitor-mistakes               -> plan (parent 6)
 * - ID 478: ninh-binh-without-rushing                       -> plan (parent 6)
 * - ID 479: ha-long-bay-cruise-questions-before-booking     -> plan (parent 6)
 * - ID 497: best-vietnam-cities-for-first-time-visitors     -> plan (parent 6)
 */

if (! defined('ABSPATH')) {
    exit('Run via WP-CLI: wp eval-file ops/publish-planning-preparation-cluster.php --allow-root' . PHP_EOL);
}

$targets = [
    473 => [
        'slug' => 'vietnam-first-trip-planning-checklist',
        'title' => 'Vietnam First Trip Planning Checklist: What to Decide Before Booking',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
        'needs_h1' => true,
    ],
    475 => [
        'slug' => 'what-to-pack-for-vietnam-region-season',
        'title' => 'What to Pack for Vietnam by Region and Season',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
        'needs_h1' => false,
    ],
    476 => [
        'slug' => 'vietnam-airport-arrival-checklist',
        'title' => 'Vietnam Airport Arrival Checklist: Money, SIM, Transport and Scams',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
        'needs_h1' => true,
    ],
    480 => [
        'slug' => 'vietnam-food-safety-street-food-etiquette',
        'title' => 'Vietnam Food Safety and Street Food Etiquette for First-Timers',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
        'needs_h1' => false,
    ],
    481 => [
        'slug' => 'where-to-stay-in-vietnam-base-decisions',
        'title' => 'Where to Stay in Vietnam: City Base Decisions Before Choosing Hotels',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
        'needs_h1' => false,
    ],
    477 => [
        'slug' => 'hanoi-first-time-visitor-mistakes',
        'title' => 'Hanoi First-Time Visitor Mistakes to Avoid',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
        'needs_h1' => false,
    ],
    478 => [
        'slug' => 'ninh-binh-without-rushing',
        'title' => 'Ninh Binh Without Rushing: How to Choose Boat, Base and Transfer',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
        'needs_h1' => false,
    ],
    479 => [
        'slug' => 'ha-long-bay-cruise-questions-before-booking',
        'title' => 'Ha Long Bay Cruise Questions to Ask Before Booking',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
        'needs_h1' => false,
    ],
    497 => [
        'slug' => 'best-vietnam-cities-for-first-time-visitors',
        'title' => 'Best Vietnam Cities for First-Time Visitors: Which Base Fits Your Route?',
        'parent_id' => 6,
        'parent_slug' => 'plan',
        'expected_type' => 'practical',
        'needs_h1' => false,
    ],
];

echo "=== Publishing Planning & Preparation Cluster Pages ===" . PHP_EOL;

$results = [];
foreach ($targets as $id => $target) {
    $post = get_post($id);
    if (! $post) {
        WP_CLI::error("Target post $id not found!");
    }

    $update_data = [
        'ID' => $id,
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_name' => $target['slug'],
        'post_title' => $target['title'],
        'post_parent' => $target['parent_id'],
    ];

    if (! empty($target['needs_h1'])) {
        $content = $post->post_content;
        if (strpos($content, '<h1') === false) {
            $esc_title = esc_html($target['title']);
            $content = preg_replace(
                '/(<p class="vg-kicker">[^<]+<\\/p>)/i',
                "$1\n<h1>$esc_title</h1>",
                $content,
                1
            );
            $update_data['post_content'] = $content;
        }
    }

    $res = wp_update_post($update_data, true);
    if (is_wp_error($res)) {
        WP_CLI::error("Failed to update post $id: " . $res->get_error_message());
    }

    // Set schema author
    update_post_meta($id, '_vg_schema_author_override', 'VietnamGuide editorial team');

    $uri = get_page_uri($id);
    echo "Published ID $id: uri='$uri', type={$target['expected_type']}" . PHP_EOL;
    $results[$id] = $uri;
}

// Flush rewrites
flush_rewrite_rules(false);
echo "Flushed rewrite rules successfully." . PHP_EOL;

echo "All " . count($results) . " Planning & Preparation cluster pages published successfully." . PHP_EOL;

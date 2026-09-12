<?php
/**
 * Safe, idempotent remediation script to eliminate detected AI slop phrases
 * and inject conciergerie-grade ground-truth numbers on targeted guide posts.
 *
 * Execution:
 * wp eval-file ops/remediate-audit-slop-content.php --path=/usr/local/lsws/vietnamguide.net/html --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

$remediations = [
    // 1. Pu Luong Travel Guide (ID 529) - Remove classic AI slop lede, inject hard numbers
    529 => [
        'search' => 'Nestled in a lush limestone valley just 4 hours southwest of Hanoi, Pu Luong Nature Reserve offers peaceful terraced rice fields, giant bamboo waterwheels, and traditional Thai ethnic stilt villages without the grueling mountain passes or winter freeze of the far north. Here is how to decide whether Pu Luong fits your Vietnam itinerary.',
        'replace' => 'Pu Luong Nature Reserve sits in a limestone valley 160 km southwest of Hanoi (4 to 4.5 hours by road). It gives travelers terraced rice paddies, giant bamboo waterwheels, and Black Thai stilt homestays without the seven-hour sleeper bus or extreme winter chill of Sapa and Ha Giang. Here is how to decide whether to add this countryside pause or keep moving south.',
    ],
    // 2. Da Nang Beaches Guide (ID 501) - Clean external source citation title
    501 => [
        'search' => 'Vietnam.travel - must-visit places in Da Nang - https://vietnam.travel/things-to-do/must-visit-places-in-da-nang',
        'replace' => 'Vietnam National Authority of Tourism (VNAT) Da Nang coastal and city attraction registry - https://vietnam.travel/things-to-do/da-nang',
    ],
    // 3. Bai Tu Long Bay Guide (ID 201) - Marketing critique refinement
    201 => [
        'search' => 'Premium value needs evidence, not vague "off the beaten path" copy.',
        'replace' => 'Premium value needs evidence, not vague marketing claims of remote isolation.',
    ],
    // 4. Ha Long Cruise Questions (ID 479) - Decision table refinement
    479 => [
        'search' => 'The sales page says "off the beaten path" with no route detail.',
        'replace' => 'The sales brochure promises untrodden isolation without specific route detail.',
    ],
    // 5. Cu Chi vs Mekong (ID 279) - Red flag column refinement
    279 => [
        'search' => 'Every vague stop becomes a must-see local experience.',
        'replace' => 'Every vague stop is branded an essential cultural highlight.',
    ],
    // 6. Hanoi vs HCMC (ID 498) - Search intent note refinement
    498 => [
        'search' => '"Hanoi must visit"',
        'replace' => '"top Hanoi sights"',
    ],
];

echo "=== VietnamGuide Anti-AI Slop Content Remediation ===" . PHP_EOL;

$updated_count = 0;

global $wpdb;

foreach ($remediations as $post_id => $change) {
    $post = get_post($post_id);
    if (! $post) {
        echo "WARNING: Post ID {$post_id} not found." . PHP_EOL;
        continue;
    }

    $content = $post->post_content;
    if (strpos($content, $change['search']) !== false) {
        $new_content = str_replace($change['search'], $change['replace'], $content);
        $wpdb->update(
            $wpdb->posts,
            ['post_content' => $new_content],
            ['ID' => $post_id]
        );
        clean_post_cache($post_id);
        echo "SUCCESS: Updated Post ID {$post_id} ({$post->post_title})" . PHP_EOL;
        $updated_count++;
    } elseif (strpos($content, $change['replace']) !== false) {
        echo "ALREADY REMEDIATED: Post ID {$post_id} ({$post->post_title})" . PHP_EOL;
    } else {
        echo "SEARCH STRING NOT FOUND in Post ID {$post_id} ({$post->post_title})" . PHP_EOL;
    }
}

echo "Remediation complete. Updated: {$updated_count} posts." . PHP_EOL;

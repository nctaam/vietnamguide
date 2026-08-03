<?php
/**
 * Verify the third native WordPress Posts evergreen draft-brief batch.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-wordpress-post-editorial-system-batch-3.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_post_editorial_batch3_cli_available(): bool
{
    return defined('WP_CLI') && WP_CLI;
}

function vg_verify_post_editorial_batch3_finish(array $failures, array $notes): void
{
    foreach ($notes as $note) {
        if (vg_verify_post_editorial_batch3_cli_available()) {
            WP_CLI::log($note);
        } else {
            echo $note . PHP_EOL;
        }
    }

    if ($failures !== []) {
        foreach ($failures as $failure) {
            if (vg_verify_post_editorial_batch3_cli_available()) {
                WP_CLI::warning($failure);
            } else {
                echo 'Warning: ' . $failure . PHP_EOL;
            }
        }

        if (vg_verify_post_editorial_batch3_cli_available()) {
            WP_CLI::error('WordPress post editorial system batch 3 verification failed.');
        }

        echo 'WordPress post editorial system batch 3 verification failed.' . PHP_EOL;
        exit(1);
    }

    if (vg_verify_post_editorial_batch3_cli_available()) {
        WP_CLI::success('WordPress post editorial system batch 3 verification passed.');
        return;
    }

    echo 'WordPress post editorial system batch 3 verification passed.' . PHP_EOL;
}

function vg_verify_post_editorial_batch3_expected_briefs(): array
{
    return [
        'sapa-travel-guide' => [
            'title' => 'Sapa Travel Guide: Terraces, Trekking and Softer Mountain Travel',
            'target_date' => '2026-08-23',
            'categories' => ['destinations', 'travel-planning'],
            'tags' => ['mountain-planning', 'trekking-planning', 'first-time-vietnam', 'anti-spam-evergreen'],
        ],
        'ha-giang-loop-planning-guide' => [
            'title' => 'Ha Giang Loop Planning Guide: Safety, Scenery and Route Fit',
            'target_date' => '2026-08-24',
            'categories' => ['destinations', 'travel-planning'],
            'tags' => ['mountain-planning', 'safety-planning', 'route-planning', 'anti-spam-evergreen'],
        ],
        'hanoi-to-sapa-transport' => [
            'title' => 'Hanoi to Sapa Transport: Train, Cabin Bus or Private Car?',
            'target_date' => '2026-08-25',
            'categories' => ['transport-logistics', 'travel-planning'],
            'tags' => ['transport-planning', 'mountain-planning', 'first-time-vietnam', 'anti-spam-evergreen'],
        ],
        'hanoi-to-ha-giang-transport' => [
            'title' => 'Hanoi to Ha Giang Transport: Bus, Private Car or Staged Route?',
            'target_date' => '2026-08-26',
            'categories' => ['transport-logistics', 'travel-planning'],
            'tags' => ['transport-planning', 'mountain-planning', 'safety-planning', 'anti-spam-evergreen'],
        ],
        'ha-giang-safety-guide' => [
            'title' => 'Ha Giang Safety Guide: Easy Rider, Self-Drive and Insurance Reality',
            'target_date' => '2026-08-27',
            'categories' => ['practicalities', 'travel-planning'],
            'tags' => ['safety-planning', 'mountain-planning', 'international-travelers', 'anti-spam-evergreen'],
        ],
        'ha-giang-easy-rider-vs-self-drive' => [
            'title' => 'Ha Giang Easy Rider vs Self-Drive: Which Is Right for You?',
            'target_date' => '2026-08-28',
            'categories' => ['travel-planning', 'transport-logistics'],
            'tags' => ['mountain-planning', 'safety-planning', 'transport-planning', 'anti-spam-evergreen'],
        ],
        'sapa-trekking-guided-vs-self-guided' => [
            'title' => 'Sapa Trekking: Guided vs Self-Guided for First-Time Visitors',
            'target_date' => '2026-08-29',
            'categories' => ['destinations', 'travel-planning'],
            'tags' => ['trekking-planning', 'mountain-planning', 'first-time-vietnam', 'anti-spam-evergreen'],
        ],
        'where-to-stay-in-sapa' => [
            'title' => 'Where to Stay in Sapa: Town, Valley Lodge or Homestay?',
            'target_date' => '2026-08-30',
            'categories' => ['hotels-neighborhoods', 'destinations'],
            'tags' => ['hotel-base', 'mountain-planning', 'premium-travel', 'anti-spam-evergreen'],
        ],
        'best-time-for-northern-vietnam' => [
            'title' => 'Best Time for Northern Vietnam: Hanoi, Bay, Ninh Binh, Sapa and Ha Giang',
            'target_date' => '2026-08-31',
            'categories' => ['seasonal-travel', 'travel-planning'],
            'tags' => ['weather-planning', 'mountain-planning', 'route-planning', 'anti-spam-evergreen'],
        ],
        'vietnam-rice-terraces-guide' => [
            'title' => 'Vietnam Rice Terraces Guide: Sapa, Mu Cang Chai, Hoang Su Phi or Pu Luong?',
            'target_date' => '2026-09-01',
            'categories' => ['destinations', 'seasonal-travel'],
            'tags' => ['rice-terraces', 'mountain-planning', 'route-planning', 'anti-spam-evergreen'],
        ],
        'mu-cang-chai-travel-guide' => [
            'title' => 'Mu Cang Chai Travel Guide: Rice Terraces Without Forcing the Route',
            'target_date' => '2026-09-02',
            'categories' => ['destinations', 'travel-planning'],
            'tags' => ['rice-terraces', 'mountain-planning', 'route-planning', 'anti-spam-evergreen'],
        ],
        'pu-luong-travel-guide' => [
            'title' => 'Pu Luong Travel Guide: Softer Countryside or Mountain Detour?',
            'target_date' => '2026-09-03',
            'categories' => ['destinations', 'travel-planning'],
            'tags' => ['mountain-planning', 'rice-terraces', 'family-travel', 'anti-spam-evergreen'],
        ],
    ];
}

function vg_verify_post_editorial_batch3_find_post(string $slug): ?WP_Post
{
    $posts = get_posts(
        [
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'future', 'pending', 'private'],
            'name' => $slug,
            'posts_per_page' => 2,
        ]
    );

    if (count($posts) > 1) {
        vg_verify_post_editorial_batch3_finish(['Expected at most one post with slug ' . $slug . '; found ' . count($posts) . '.'], []);
    }

    return $posts[0] ?? null;
}

function vg_verify_post_editorial_batch3_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_post_editorial_batch3_assert_exact_terms(array &$failures, int $post_id, string $slug, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_post_editorial_batch3_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "Batch 3 editorial post {$slug} {$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

$failures = [];
$notes = [];
$batch = 'batch-3-northern-mountains';
$brief_count = 0;
$complete_draft_count = 0;
$expected_briefs = vg_verify_post_editorial_batch3_expected_briefs();
$expected_slugs = array_keys($expected_briefs);

foreach ($expected_briefs as $slug => $expected) {
    $post = vg_verify_post_editorial_batch3_find_post($slug);

    if (! $post instanceof WP_Post) {
        $failures[] = "Missing required batch 3 editorial post brief: {$slug}";
        continue;
    }

    $brief_count++;
    $post_id = (int) $post->ID;

    if ($post->post_type !== 'post') {
        $failures[] = "Batch 3 editorial post must use native post type: {$slug} (#{$post_id}, type {$post->post_type})";
    }

    if ($post->post_status !== 'draft') {
        $failures[] = "Batch 3 editorial post brief must stay draft until manually completed: {$slug} (#{$post_id}, status {$post->post_status})";
    }

    if ($post->post_title !== $expected['title']) {
        $failures[] = "Batch 3 editorial post {$slug} title mismatch: {$post->post_title}; expected {$expected['title']}";
    }

    if ($post->post_name !== $slug) {
        $failures[] = "Batch 3 editorial post slug mismatch: {$post->post_name}; expected {$slug}";
    }

    if ($post->comment_status !== 'closed') {
        $failures[] = "Batch 3 editorial post {$slug} comment_status !== closed.";
    }

    if ($post->ping_status !== 'closed') {
        $failures[] = "Batch 3 editorial post {$slug} ping_status !== closed.";
    }

    foreach (
        [
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
            'vg_editorial_batch' => $batch,
            'vg_editorial_target_publish_date' => $expected['target_date'],
        ] as $meta_key => $expected_value
    ) {
        $actual_value = trim((string) get_post_meta($post_id, $meta_key, true));

        if ($actual_value !== $expected_value) {
            $failures[] = "Batch 3 editorial post {$slug} has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
        }
    }

    $brief_status = trim((string) get_post_meta($post_id, 'vg_editorial_brief_status', true));

    if (! in_array($brief_status, ['brief', 'complete_draft'], true)) {
        $failures[] = "Batch 3 editorial post {$slug} has unexpected vg_editorial_brief_status: {$brief_status}; expected brief or complete_draft";
    }

    if ($brief_status === 'complete_draft') {
        $complete_draft_count++;
    }

    foreach (
        [
            'rank_math_title',
            'rank_math_description',
            'rank_math_focus_keyword',
            'vg_editorial_reader_job',
            'vg_editorial_evidence_moat',
            'vg_editorial_what_not_to_write',
            'vg_editorial_sources_to_check',
            'vg_editorial_image_plan',
            'vg_admin_first_notes',
        ] as $required_meta_key
    ) {
        if (trim((string) get_post_meta($post_id, $required_meta_key, true)) === '') {
            $failures[] = "Batch 3 editorial post {$slug} is missing {$required_meta_key}.";
        }
    }

    $content = (string) $post->post_content;
    $contains_brief_guidance = str_contains($content, 'Draft status:');

    if ($brief_status === 'brief' && ! $contains_brief_guidance) {
        $failures[] = "Batch 3 editorial post brief {$slug} is missing draft-status guidance.";
    }

    if ($brief_status === 'complete_draft' && $contains_brief_guidance) {
        $failures[] = "Batch 3 editorial complete draft {$slug} still contains draft-status placeholder guidance.";
    }

    if ($brief_status === 'brief') {
        foreach (['Editorial brief', 'Sources to check', 'Image plan', 'Publish gate'] as $placeholder_heading) {
            if (! str_contains($content, $placeholder_heading)) {
                $failures[] = "Batch 3 editorial post brief {$slug} is missing brief placeholder section: {$placeholder_heading}";
            }
        }
    }

    if ($brief_status === 'complete_draft') {
        foreach (['Editorial brief', 'Sources to check', 'Image plan', 'Publish gate'] as $placeholder_heading) {
            if (str_contains($content, $placeholder_heading)) {
                $failures[] = "Batch 3 editorial complete draft {$slug} still contains brief placeholder section: {$placeholder_heading}";
            }
        }
    }

    vg_verify_post_editorial_batch3_assert_exact_terms($failures, $post_id, $slug, 'category', $expected['categories']);
    vg_verify_post_editorial_batch3_assert_exact_terms($failures, $post_id, $slug, 'post_tag', $expected['tags']);
}

$published_briefs = get_posts(
    [
        'post_type' => 'post',
        'post_status' => ['publish', 'future'],
        'meta_key' => 'vg_editorial_batch',
        'meta_value' => $batch,
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]
);

if ($published_briefs !== []) {
    $failures[] = 'Published batch 3 editorial briefs found: ' . implode(', ', array_map('strval', $published_briefs));
}

$batch_posts = get_posts(
    [
        'post_type' => 'post',
        'post_status' => ['draft', 'pending', 'private', 'future', 'publish'],
        'meta_key' => 'vg_editorial_batch',
        'meta_value' => $batch,
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]
);
$batch_slugs = [];

foreach ($batch_posts as $batch_post_id) {
    $batch_post = get_post((int) $batch_post_id);

    if ($batch_post instanceof WP_Post) {
        $batch_slugs[] = (string) $batch_post->post_name;
    }
}

sort($batch_slugs);
sort($expected_slugs);

if ($batch_slugs !== $expected_slugs) {
    $failures[] = 'Batch 3 editorial batch slug set mismatch: ' . implode(', ', $batch_slugs) . '; expected ' . implode(', ', $expected_slugs);
}

$notes[] = 'Batch 3 editorial draft briefs: ' . $brief_count . ' item(s).';
$notes[] = 'Batch 3 editorial complete drafts: ' . $complete_draft_count . ' item(s).';
$notes[] = 'Published batch 3 editorial briefs found: ' . count($published_briefs) . ' item(s).';
$notes[] = 'Batch 3 editorial total batch posts: ' . count($batch_posts) . ' item(s).';

vg_verify_post_editorial_batch3_finish($failures, $notes);

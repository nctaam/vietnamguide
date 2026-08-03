<?php
/**
 * Verify the second native WordPress Posts evergreen draft-brief batch.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-wordpress-post-editorial-system-batch-2.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_post_editorial_batch2_cli_available(): bool
{
    return defined('WP_CLI') && WP_CLI;
}

function vg_verify_post_editorial_batch2_finish(array $failures, array $notes): void
{
    foreach ($notes as $note) {
        if (vg_verify_post_editorial_batch2_cli_available()) {
            WP_CLI::log($note);
        } else {
            echo $note . PHP_EOL;
        }
    }

    if ($failures !== []) {
        foreach ($failures as $failure) {
            if (vg_verify_post_editorial_batch2_cli_available()) {
                WP_CLI::warning($failure);
            } else {
                echo 'Warning: ' . $failure . PHP_EOL;
            }
        }

        if (vg_verify_post_editorial_batch2_cli_available()) {
            WP_CLI::error('WordPress post editorial system batch 2 verification failed.');
        }

        echo 'WordPress post editorial system batch 2 verification failed.' . PHP_EOL;
        exit(1);
    }

    if (vg_verify_post_editorial_batch2_cli_available()) {
        WP_CLI::success('WordPress post editorial system batch 2 verification passed.');
        return;
    }

    echo 'WordPress post editorial system batch 2 verification passed.' . PHP_EOL;
}

function vg_verify_post_editorial_batch2_expected_briefs(): array
{
    return [
        'vietnam-in-december',
        'vietnam-in-january',
        'vietnam-in-february',
        'tet-in-vietnam-travel-guide',
        'best-vietnam-cities-for-first-time-visitors',
        'hanoi-vs-ho-chi-minh-city',
        'hoi-an-ancient-town-guide',
        'hue-imperial-city-guide',
        'da-nang-beaches-guide',
        'mekong-delta-overnight-vs-day-trip',
        'phong-nha-travel-guide',
        'sapa-vs-ha-giang',
    ];
}

function vg_verify_post_editorial_batch2_find_post(string $slug): ?WP_Post
{
    $posts = get_posts(
        [
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'future', 'pending', 'private'],
            'name' => $slug,
            'posts_per_page' => 1,
        ]
    );

    return $posts[0] ?? null;
}

$failures = [];
$notes = [];
$batch = 'batch-2-evergreen-planning';
$brief_count = 0;
$complete_draft_count = 0;

foreach (vg_verify_post_editorial_batch2_expected_briefs() as $slug) {
    $post = vg_verify_post_editorial_batch2_find_post($slug);

    if (! $post instanceof WP_Post) {
        $failures[] = "Missing required batch 2 editorial post brief: {$slug}";
        continue;
    }

    $brief_count++;
    $post_id = (int) $post->ID;

    if ($post->post_status !== 'draft') {
        $failures[] = "Batch 2 editorial post brief must stay draft until manually completed: {$slug} (#{$post_id}, status {$post->post_status})";
    }

    foreach (
        [
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
            'vg_editorial_batch' => $batch,
        ] as $meta_key => $expected_value
    ) {
        $actual_value = trim((string) get_post_meta($post_id, $meta_key, true));

        if ($actual_value !== $expected_value) {
            $failures[] = "Batch 2 editorial post brief {$slug} has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
        }
    }

    $brief_status = trim((string) get_post_meta($post_id, 'vg_editorial_brief_status', true));

    if (! in_array($brief_status, ['brief', 'complete_draft'], true)) {
        $failures[] = "Batch 2 editorial post brief {$slug} has unexpected vg_editorial_brief_status: {$brief_status}; expected brief or complete_draft";
    }

    if ($brief_status === 'complete_draft') {
        $complete_draft_count++;
    }

    foreach (
        [
            'vg_editorial_target_publish_date',
            'vg_editorial_reader_job',
            'vg_editorial_evidence_moat',
            'vg_editorial_sources_to_check',
            'vg_editorial_image_plan',
        ] as $required_meta_key
    ) {
        if (trim((string) get_post_meta($post_id, $required_meta_key, true)) === '') {
            $failures[] = "Batch 2 editorial post brief {$slug} is missing {$required_meta_key}.";
        }
    }

    $contains_brief_guidance = str_contains((string) $post->post_content, 'Draft status:');

    if ($brief_status === 'brief' && ! $contains_brief_guidance) {
        $failures[] = "Batch 2 editorial post brief {$slug} is missing draft-status guidance.";
    }

    if ($brief_status === 'complete_draft' && $contains_brief_guidance) {
        $failures[] = "Batch 2 editorial complete draft {$slug} still contains draft-status placeholder guidance.";
    }

    if ($brief_status === 'complete_draft') {
        foreach (['Editorial brief', 'Sources to check', 'Image plan', 'Publish gate'] as $placeholder_heading) {
            if (str_contains((string) $post->post_content, $placeholder_heading)) {
                $failures[] = "Batch 2 editorial complete draft {$slug} still contains brief placeholder section: {$placeholder_heading}";
            }
        }
    }

    if ($brief_status === 'brief' && ! str_contains((string) $post->post_content, 'Sources to check')) {
        $failures[] = "Batch 2 editorial post brief {$slug} is missing source-check guidance.";
    }

    if ($brief_status === 'brief' && ! str_contains((string) $post->post_content, 'Image plan')) {
        $failures[] = "Batch 2 editorial post brief {$slug} is missing image-plan guidance.";
    }

    $category_slugs = wp_get_post_terms($post_id, 'category', ['fields' => 'slugs']);

    if (! is_array($category_slugs) || $category_slugs === []) {
        $failures[] = "Batch 2 editorial post brief {$slug} has no categories.";
    }

    $tag_slugs = wp_get_post_terms($post_id, 'post_tag', ['fields' => 'slugs']);

    if (! is_array($tag_slugs) || ! in_array('anti-spam-evergreen', $tag_slugs, true)) {
        $failures[] = "Batch 2 editorial post brief {$slug} is missing anti-spam-evergreen tag.";
    }
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
    $failures[] = 'Published batch 2 editorial briefs found: ' . implode(', ', array_map('strval', $published_briefs));
}

$notes[] = 'Batch 2 editorial draft briefs: ' . $brief_count . ' item(s).';
$notes[] = 'Batch 2 editorial complete drafts: ' . $complete_draft_count . ' item(s).';
$notes[] = 'Published batch 2 editorial briefs found: ' . count($published_briefs) . ' item(s).';

vg_verify_post_editorial_batch2_finish($failures, $notes);

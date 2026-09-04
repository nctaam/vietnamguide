<?php
/**
 * Verify the native WordPress Posts editorial system without changing data.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-wordpress-post-editorial-system.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_post_editorial_cli_available(): bool
{
    return defined('WP_CLI') && WP_CLI;
}

function vg_verify_post_editorial_finish(array $failures, array $notes): void
{
    foreach ($notes as $note) {
        if (vg_verify_post_editorial_cli_available()) {
            WP_CLI::log($note);
        } else {
            echo $note . PHP_EOL;
        }
    }

    if ($failures !== []) {
        foreach ($failures as $failure) {
            if (vg_verify_post_editorial_cli_available()) {
                WP_CLI::warning($failure);
            } else {
                echo 'Warning: ' . $failure . PHP_EOL;
            }
        }

        if (vg_verify_post_editorial_cli_available()) {
            WP_CLI::error('WordPress post editorial system verification failed.');
        }

        echo 'WordPress post editorial system verification failed.' . PHP_EOL;
        exit(1);
    }

    if (vg_verify_post_editorial_cli_available()) {
        WP_CLI::success('WordPress post editorial system verification passed.');
        return;
    }

    echo 'WordPress post editorial system verification passed.' . PHP_EOL;
}

function vg_verify_post_editorial_expected_categories(): array
{
    return [
        'travel-planning',
        'itineraries',
        'destinations',
        'transport-logistics',
        'food-culture',
        'beaches-islands',
        'practicalities',
        'hotels-neighborhoods',
        'seasonal-travel',
        'heritage-culture',
    ];
}

function vg_verify_post_editorial_expected_briefs(): array
{
    return [
        'vietnam-first-trip-planning-checklist',
        'best-vietnam-routes-first-time-visitors',
        'what-to-pack-for-vietnam-region-season',
        'vietnam-airport-arrival-checklist',
        'hanoi-first-time-visitor-mistakes',
        'ninh-binh-without-rushing',
        'ha-long-bay-cruise-questions-before-booking',
        'vietnam-food-safety-street-food-etiquette',
        'where-to-stay-in-vietnam-base-decisions',
        'vietnam-rainy-season-flexible-route',
    ];
}

function vg_verify_post_editorial_find_post(string $slug): ?WP_Post
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

foreach (vg_verify_post_editorial_expected_categories() as $slug) {
    $term = term_exists($slug, 'category');

    if ($term === 0 || $term === null) {
        $failures[] = "Missing required editorial category: {$slug}";
    }
}

$brief_count = 0;
$complete_draft_count = 0;

foreach (vg_verify_post_editorial_expected_briefs() as $slug) {
    $post = vg_verify_post_editorial_find_post($slug);

    if (! $post instanceof WP_Post) {
        $failures[] = "Missing required editorial post brief: {$slug}";
        continue;
    }

    $brief_count++;

    if ($post->post_status !== 'draft') {
        $failures[] = "Editorial post brief must stay draft until manually completed: {$slug} (#{$post->ID}, status {$post->post_status})";
    }

    foreach (
        [
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = trim((string) get_post_meta((int) $post->ID, $meta_key, true));

        if ($actual_value !== $expected_value) {
            $failures[] = "Editorial post brief {$slug} has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
        }
    }

    $brief_status = trim((string) get_post_meta((int) $post->ID, 'vg_editorial_brief_status', true));

    if (! in_array($brief_status, ['brief', 'complete_draft'], true)) {
        $failures[] = "Editorial post brief {$slug} has unexpected vg_editorial_brief_status: {$brief_status}; expected brief or complete_draft";
    }

    if ($brief_status === 'complete_draft') {
        $complete_draft_count++;
    }

    if (trim((string) get_post_meta((int) $post->ID, 'vg_editorial_target_publish_date', true)) === '') {
        $failures[] = "Editorial post brief {$slug} is missing vg_editorial_target_publish_date.";
    }

    $contains_brief_guidance = str_contains((string) $post->post_content, 'Draft status:');

    if ($brief_status === 'brief' && ! $contains_brief_guidance) {
        $failures[] = "Editorial post brief {$slug} is missing draft-status guidance.";
    }

    if ($brief_status === 'complete_draft' && $contains_brief_guidance) {
        $failures[] = "Editorial complete draft {$slug} still contains draft-status placeholder guidance.";
    }

    $category_slugs = wp_get_post_terms((int) $post->ID, 'category', ['fields' => 'slugs']);

    if (! is_array($category_slugs) || $category_slugs === []) {
        $failures[] = "Editorial post brief {$slug} has no categories.";
    }
}

$published_briefs = get_posts(
    [
        'post_type' => 'post',
        'post_status' => ['publish', 'future'],
        'meta_key' => 'vg_editorial_brief_status',
        'meta_value' => 'brief',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]
);

if ($published_briefs !== []) {
    $failures[] = 'Published editorial briefs found: ' . implode(', ', array_map('strval', $published_briefs));
}

$notes[] = 'Editorial draft briefs: ' . $brief_count . ' item(s).';
$notes[] = 'Editorial complete drafts: ' . $complete_draft_count . ' item(s).';
$notes[] = 'Published editorial briefs found: ' . count($published_briefs) . ' item(s).';

vg_verify_post_editorial_finish($failures, $notes);

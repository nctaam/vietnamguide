<?php
/**
 * Verify the Vietnam First Trip Planning Checklist complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-vietnam-first-trip-planning-checklist-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_first_trip_post_finish(array $failures, array $notes = []): void
{
    foreach ($notes as $note) {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::log($note);
        } else {
            echo $note . PHP_EOL;
        }
    }

    if ($failures !== []) {
        foreach ($failures as $failure) {
            if (defined('WP_CLI') && WP_CLI) {
                WP_CLI::warning($failure);
            } else {
                echo 'Warning: ' . $failure . PHP_EOL;
            }
        }

        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::error('Vietnam first-trip checklist post verification failed.');
        }

        echo 'Vietnam first-trip checklist post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Vietnam first-trip checklist post verification passed.');
        return;
    }

    echo 'Vietnam first-trip checklist post verification passed.' . PHP_EOL;
}

function vg_verify_first_trip_post_by_slug(string $slug): ?WP_Post
{
    $posts = get_posts(
        [
            'post_type' => 'post',
            'post_status' => ['draft', 'pending', 'private', 'future', 'publish'],
            'name' => $slug,
            'posts_per_page' => 1,
        ]
    );

    return $posts[0] ?? null;
}

function vg_verify_first_trip_post_rendered_content(WP_Post $post): string
{
    $previous_post = $GLOBALS['post'] ?? null;

    $GLOBALS['post'] = $post;
    setup_postdata($post);
    $content = apply_filters('the_content', (string) $post->post_content);
    wp_reset_postdata();

    if ($previous_post instanceof WP_Post) {
        $GLOBALS['post'] = $previous_post;
    } else {
        unset($GLOBALS['post']);
    }

    return is_string($content) ? $content : '';
}

function vg_verify_first_trip_external_body_href_count(string $content): int
{
    preg_match_all('/<a\b[^>]*\bhref=(["\'])(https?:\/\/[^"\']+)\1/i', $content, $matches);

    if ($matches[2] === []) {
        return 0;
    }

    $site_host = parse_url(home_url('/'), PHP_URL_HOST);
    $count = 0;

    foreach ($matches[2] as $href) {
        $href_host = parse_url($href, PHP_URL_HOST);

        if (! $site_host || ! $href_host || strcasecmp($site_host, $href_host) !== 0) {
            $count++;
        }
    }

    return $count;
}

$failures = [];
$notes = [];
$post = vg_verify_first_trip_post_by_slug('vietnam-first-trip-planning-checklist');

if (! $post instanceof WP_Post) {
    vg_verify_first_trip_post_finish(['Required post not found: vietnam-first-trip-planning-checklist']);
}

if ($post->post_status !== 'draft') {
    $failures[] = "First-trip checklist post_status !== 'draft': {$post->post_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_first_trip_post_rendered_content($post);

foreach (
    [
        'hero marker' => 'vg-first-trip-checklist-hero:v1',
        'verdict marker' => 'vg-first-trip-checklist-verdict:v1',
        'planning order marker' => 'vg-first-trip-checklist-planning-order:v1',
        'route shape marker' => 'vg-first-trip-checklist-route-shape:v1',
        'booking order marker' => 'vg-first-trip-checklist-booking-order:v1',
        'arrival marker' => 'vg-first-trip-checklist-arrival:v1',
        'FAQ marker' => 'vg-first-trip-checklist-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'official source note' => 'Vietnam National Electronic Visa system',
        'CDC source note' => 'CDC Travelers Health',
    ] as $label => $needle
) {
    $haystack = str_starts_with($label, 'proof') || str_contains($label, 'trail') || str_contains($label, 'log') || str_contains($label, 'routes')
        ? $rendered_content
        : $raw_content . "\n" . $rendered_content;

    if (! str_contains($haystack, $needle)) {
        $failures[] = "First-trip checklist post is missing {$label}: {$needle}";
    }
}

if (str_contains($raw_content, 'Draft status:')) {
    $failures[] = 'First-trip checklist post still contains brief placeholder text: Draft status:';
}

if (strlen(strip_tags($raw_content)) < 6500) {
    $failures[] = 'First-trip checklist post content is too thin for complete draft status.';
}

foreach (
    [
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_reviewed_guide' => '1',
    ] as $meta_key => $expected_value
) {
    $actual_value = trim((string) get_post_meta((int) $post->ID, $meta_key, true));

    if ($actual_value !== $expected_value) {
        $failures[] = "First-trip checklist post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
    }
}

foreach (['rank_math_title', 'rank_math_description', 'rank_math_focus_keyword', 'vg_eeat_sources_checked', 'vg_eeat_related_routes', 'vg_eeat_hero_image_credit'] as $meta_key) {
    $value = trim((string) get_post_meta((int) $post->ID, $meta_key, true));

    if ($value === '') {
        $failures[] = "First-trip checklist post is missing required meta: {$meta_key}";
    }
}

$external_body_href_count = vg_verify_first_trip_external_body_href_count($raw_content);

if ($external_body_href_count !== 0) {
    $failures[] = "First-trip checklist post external_body_href_count should be 0; found {$external_body_href_count}.";
}

$notes[] = 'First-trip checklist post ID: ' . (int) $post->ID;
$notes[] = 'First-trip checklist external_body_href_count: ' . $external_body_href_count;

vg_verify_first_trip_post_finish($failures, $notes);

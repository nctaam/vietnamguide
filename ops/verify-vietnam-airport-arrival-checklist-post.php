<?php
/**
 * Verify the Vietnam Airport Arrival Checklist complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-vietnam-airport-arrival-checklist-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_airport_arrival_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Vietnam airport arrival checklist post verification failed.');
        }

        echo 'Vietnam airport arrival checklist post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Vietnam airport arrival checklist post verification passed.');
        return;
    }

    echo 'Vietnam airport arrival checklist post verification passed.' . PHP_EOL;
}

function vg_verify_airport_arrival_post_by_slug(string $slug): ?WP_Post
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

function vg_verify_airport_arrival_post_rendered_content(WP_Post $post): string
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

function vg_verify_airport_arrival_external_body_href_count(string $content): int
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

function vg_verify_airport_arrival_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_airport_arrival_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_airport_arrival_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

$failures = [];
$notes = [];
$post = vg_verify_airport_arrival_post_by_slug('vietnam-airport-arrival-checklist');

if (! $post instanceof WP_Post) {
    vg_verify_airport_arrival_post_finish(['Required post not found: vietnam-airport-arrival-checklist']);
}

$post_id = (int) $post->ID;

if ($post->post_status !== 'draft') {
    $failures[] = "Airport arrival checklist post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Airport arrival checklist comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Airport arrival checklist ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_airport_arrival_post_rendered_content($post);

foreach (
    [
        'hero marker' => 'vg-airport-arrival-checklist-hero:v1',
        'verdict marker' => 'vg-airport-arrival-checklist-verdict:v1',
        'sequence marker' => 'vg-airport-arrival-checklist-sequence:v1',
        'do-now-wait marker' => 'vg-airport-arrival-checklist-do-now-wait:v1',
        'money marker' => 'vg-airport-arrival-checklist-money:v1',
        'connectivity marker' => 'vg-airport-arrival-checklist-connectivity:v1',
        'transport marker' => 'vg-airport-arrival-checklist-transport:v1',
        'red flags marker' => 'vg-airport-arrival-checklist-red-flags:v1',
        'first night marker' => 'vg-airport-arrival-checklist-first-night:v1',
        'FAQ marker' => 'vg-airport-arrival-checklist-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'airport source note' => 'vietnam.travel/things-to-do/travellers-guide-vietnams-airports',
        'customs source note' => 'USD 5,000',
        'connectivity route note' => 'SIM and eSIM in Vietnam',
    ] as $label => $needle
) {
    $haystack = str_contains($label, 'panel') || str_contains($label, 'trail') || str_contains($label, 'log') || str_contains($label, 'routes')
        ? $rendered_content
        : $raw_content . "\n" . $rendered_content;

    if (! str_contains($haystack, $needle)) {
        $failures[] = "Airport arrival checklist post is missing {$label}: {$needle}";
    }
}

if (str_contains($raw_content, 'Draft status:') || str_contains($rendered_content, 'Draft status:')) {
    $failures[] = 'Airport arrival checklist post still contains brief placeholder text: Draft status:';
}

if (strlen(wp_strip_all_tags($raw_content)) < 6500) {
    $failures[] = 'Airport arrival checklist post content is too thin for complete draft status.';
}

foreach (
    [
        'rank_math_title' => 'Vietnam Airport Arrival Checklist',
        'rank_math_description' => 'Use this Vietnam airport arrival checklist to handle documents, luggage, SIM or eSIM, small cash, verified transport, scams, and first-night decisions.',
        'rank_math_focus_keyword' => 'Vietnam airport arrival checklist',
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_reviewed_guide' => '1',
        'vg_eeat_affiliate_status' => 'none',
        'vg_editorial_target_publish_date' => '2026-08-04',
    ] as $meta_key => $expected_value
) {
    $actual_value = trim((string) get_post_meta($post_id, $meta_key, true));

    if ($actual_value !== $expected_value) {
        $failures[] = "Airport arrival checklist post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
    }
}

foreach (
    [
        'rank_math_title',
        'rank_math_description',
        'rank_math_focus_keyword',
        'vg_editorial_brief_status',
        'vg_editorial_target_publish_date',
        'vg_content_owner',
        'vg_automation_lock',
        'vg_eeat_primary_decision',
        'vg_eeat_reviewed_guide',
        'vg_eeat_written_by',
        'vg_eeat_reviewed_by',
        'vg_eeat_last_meaningful_update',
        'vg_eeat_update_summary',
        'vg_eeat_sources_checked',
        'vg_eeat_field_note',
        'vg_eeat_affiliate_status',
        'vg_eeat_evidence_moat',
        'vg_eeat_related_routes',
        'vg_eeat_hero_image_credit',
    ] as $meta_key
) {
    $value = get_post_meta($post_id, $meta_key, true);

    if ((is_string($value) && trim($value) === '') || $value === [] || $value === null) {
        $failures[] = "Airport arrival checklist post is missing required meta: {$meta_key}";
    }
}

vg_verify_airport_arrival_assert_exact_terms($failures, $post_id, 'category', ['transport-logistics', 'practicalities']);
vg_verify_airport_arrival_assert_exact_terms($failures, $post_id, 'post_tag', ['arrival-day', 'first-time-vietnam', 'international-travelers', 'anti-spam-evergreen']);

$external_body_href_count = vg_verify_airport_arrival_external_body_href_count($raw_content);

if ($external_body_href_count !== 0) {
    $failures[] = "Airport arrival checklist post external_body_href_count should be 0; found {$external_body_href_count}.";
}

$notes[] = 'Airport arrival checklist post ID: ' . $post_id;
$notes[] = 'Airport arrival checklist external_body_href_count: ' . $external_body_href_count;

vg_verify_airport_arrival_post_finish($failures, $notes);

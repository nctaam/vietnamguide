<?php
/**
 * Verify the Hanoi First-Time Visitor Mistakes complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-hanoi-first-time-visitor-mistakes-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_hanoi_mistakes_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Hanoi first-time visitor mistakes post verification failed.');
        }

        echo 'Hanoi first-time visitor mistakes post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Hanoi first-time visitor mistakes post verification passed.');
        return;
    }

    echo 'Hanoi first-time visitor mistakes post verification passed.' . PHP_EOL;
}

function vg_verify_hanoi_mistakes_post_by_slug(string $slug): ?WP_Post
{
    $posts = get_posts(
        [
            'post_type' => 'post',
            'post_status' => ['draft', 'pending', 'private', 'future', 'publish'],
            'name' => $slug,
            'posts_per_page' => 2,
        ]
    );

    if (count($posts) !== 1) {
        vg_verify_hanoi_mistakes_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_hanoi_mistakes_assert_target_meta(WP_Post $post, array &$failures): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-05',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = trim((string) get_post_meta($post_id, $meta_key, true));

        if ($actual_value !== $expected_value) {
            $failures[] = "Hanoi first-time visitor mistakes post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
        }
    }
}

function vg_verify_hanoi_mistakes_rendered_content(WP_Post $post): string
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

function vg_verify_hanoi_mistakes_external_body_href_count(string $content): int
{
    preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])([^"\']+)\1/i', $content, $matches);

    if ($matches[2] === []) {
        return 0;
    }

    $site_host = parse_url(home_url('/'), PHP_URL_HOST);
    $count = 0;

    foreach ($matches[2] as $href) {
        $href = trim($href);

        if ($href === '' || $href === '#' || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
            continue;
        }

        if (str_starts_with($href, '//')) {
            $count++;
            continue;
        }

        if (! preg_match('#^https?://#i', $href)) {
            continue;
        }

        $href_host = parse_url($href, PHP_URL_HOST);

        if (! $site_host || ! $href_host || strcasecmp($site_host, $href_host) !== 0) {
            $count++;
        }
    }

    return $count;
}

function vg_verify_hanoi_mistakes_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_hanoi_mistakes_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_hanoi_mistakes_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

$failures = [];
$notes = [];
$post = vg_verify_hanoi_mistakes_post_by_slug('hanoi-first-time-visitor-mistakes');

if (! $post instanceof WP_Post) {
    vg_verify_hanoi_mistakes_post_finish(['Required post not found: hanoi-first-time-visitor-mistakes']);
}

$post_id = (int) $post->ID;

if ($post->post_status !== 'draft') {
    $failures[] = "Hanoi first-time visitor mistakes post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Hanoi first-time visitor mistakes comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Hanoi first-time visitor mistakes ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_hanoi_mistakes_rendered_content($post);

foreach (
    [
        'hero marker' => 'vg-hanoi-mistakes-hero:v1',
        'concierge verdict marker' => 'vg-hanoi-mistakes-concierge-verdict',
        'at a glance marker' => 'vg-hanoi-mistakes-at-a-glance:v1',
        'photo grid marker' => 'vg-hanoi-mistakes-photo-grid:v1',
        'source diversity marker' => 'vg-hanoi-mistakes-source-diversity:v1',
        'base choice marker' => 'vg-hanoi-mistakes-base-choice:v1',
        'arrival overload marker' => 'vg-hanoi-mistakes-arrival-overload:v1',
        'day trip sequencing marker' => 'vg-hanoi-mistakes-day-trip-sequencing:v1',
        'route crowding marker' => 'vg-hanoi-mistakes-route-crowding:v1',
        'weather pacing marker' => 'vg-hanoi-mistakes-weather-vs-pacing:v1',
        'correction matrix marker' => 'vg-hanoi-mistakes-correction-matrix:v1',
        'live checks marker' => 'vg-hanoi-mistakes-live-checks:v1',
        'first night marker' => 'vg-hanoi-mistakes-first-night:v1',
        'FAQ marker' => 'vg-hanoi-mistakes-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'destination source note' => 'Vietnam.travel - Ha Noi destination page',
        'airport source note' => 'Noi Bai International Airport',
        'weather source note' => 'National Centre for Hydro-Meteorological Forecasting',
        'heritage source note' => 'UNESCO - Central Sector of the Imperial Citadel of Thang Long',
        'diagnostic phrase' => 'mistake -&gt; symptom -&gt; correction -&gt; cluster guide',
        'cluster route note' => 'Hanoi Airport to Old Quarter',
    ] as $label => $needle
) {
    $haystack = str_contains($label, 'panel') || str_contains($label, 'trail') || str_contains($label, 'log') || str_contains($label, 'routes') || str_contains($label, 'source note')
        ? $rendered_content
        : $raw_content . "\n" . $rendered_content;

    if (! str_contains($haystack, $needle)) {
        $failures[] = "Hanoi first-time visitor mistakes post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/destinations/hanoi-travel-guide/',
        '/destinations/where-to-stay-in-hanoi/',
        '/destinations/best-day-trips-from-hanoi/',
        '/itineraries/hanoi-in-2-days/',
        '/plan/hanoi-airport-to-old-quarter/',
    ] as $internal_path
) {
    if (! str_contains($raw_content . "\n" . $rendered_content, $internal_path)) {
        $failures[] = "Hanoi first-time visitor mistakes post is missing internal route: {$internal_path}";
    }
}

if (str_contains($raw_content, 'Draft status:') || str_contains($rendered_content, 'Draft status:')) {
    $failures[] = 'Hanoi first-time visitor mistakes post still contains brief placeholder text: Draft status:';
}

if (! str_contains($rendered_content, 'vg-related-routes-title-' . $post_id)) {
    $failures[] = 'Hanoi first-time visitor mistakes post is missing rendered related routes shortcode output.';
}

if (strlen(wp_strip_all_tags($raw_content)) < 8500) {
    $failures[] = 'Hanoi first-time visitor mistakes post content is too thin for complete draft status.';
}

foreach (
    [
        'post_title' => 'Hanoi First-Time Visitor Mistakes to Avoid',
        'post_name' => 'hanoi-first-time-visitor-mistakes',
    ] as $field => $expected_value
) {
    $actual_value = trim((string) $post->{$field});

    if ($actual_value !== $expected_value) {
        $failures[] = "Hanoi first-time visitor mistakes post has unexpected {$field}: {$actual_value}; expected {$expected_value}";
    }
}

vg_verify_hanoi_mistakes_assert_target_meta($post, $failures);

foreach (
    [
        'rank_math_title' => 'Hanoi First-Time Visitor Mistakes to Avoid',
        'rank_math_description' => 'Avoid common Hanoi first-time visitor mistakes around where to stay, arrival night, day trips, weather, and crowded northern Vietnam routes.',
        'rank_math_focus_keyword' => 'Hanoi first time visitor mistakes',
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_reviewed_guide' => '1',
        'vg_eeat_affiliate_status' => 'none',
    ] as $meta_key => $expected_value
) {
    $actual_value = trim((string) get_post_meta($post_id, $meta_key, true));

    if ($actual_value !== $expected_value) {
        $failures[] = "Hanoi first-time visitor mistakes post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
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
        $failures[] = "Hanoi first-time visitor mistakes post is missing required meta: {$meta_key}";
    }
}

vg_verify_hanoi_mistakes_assert_exact_terms($failures, $post_id, 'category', ['destinations', 'travel-planning']);
vg_verify_hanoi_mistakes_assert_exact_terms($failures, $post_id, 'post_tag', ['first-time-vietnam', 'route-planning', 'anti-spam-evergreen']);

$external_body_href_count = vg_verify_hanoi_mistakes_external_body_href_count($raw_content);

if ($external_body_href_count !== 0) {
    $failures[] = "Hanoi first-time visitor mistakes post external_body_href_count should be 0; found {$external_body_href_count}.";
}

$notes[] = 'Hanoi first-time visitor mistakes post ID: ' . $post_id;
$notes[] = 'Hanoi first-time visitor mistakes external_body_href_count: ' . $external_body_href_count;

vg_verify_hanoi_mistakes_post_finish($failures, $notes);

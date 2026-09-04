<?php
/**
 * Verify the Best Vietnam Routes for First-Time Visitors complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-best-vietnam-routes-first-time-visitors-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_best_routes_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Best Vietnam routes post verification failed.');
        }

        echo 'Best Vietnam routes post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Best Vietnam routes post verification passed.');
        return;
    }

    echo 'Best Vietnam routes post verification passed.' . PHP_EOL;
}

function vg_verify_best_routes_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_best_routes_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_best_routes_assert_target_meta(WP_Post $post, array &$failures): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-02',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if ($actual_value !== $expected_value) {
            $failures[] = "Best Vietnam routes post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
        }
    }
}

function vg_verify_best_routes_rendered_content(WP_Post $post): string
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

function vg_verify_best_routes_external_body_href_count(string $content): int
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

function vg_verify_best_routes_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_best_routes_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_best_routes_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

$failures = [];
$notes = [];
$post = vg_verify_best_routes_post_by_slug('best-vietnam-routes-first-time-visitors');

if (! $post instanceof WP_Post) {
    vg_verify_best_routes_post_finish(['Required post not found: best-vietnam-routes-first-time-visitors']);
}

$post_id = (int) $post->ID;

if ($post->post_status !== 'draft') {
    $failures[] = "Best Vietnam routes post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Best Vietnam routes comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Best Vietnam routes ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_best_routes_rendered_content($post);

foreach (
    [
        'hero marker' => 'vg-best-routes-hero:v1',
        'verdict marker' => 'vg-best-routes-concierge-verdict:v1',
        'at a glance marker' => 'vg-best-routes-at-a-glance:v1',
        'photo proof marker' => 'vg-best-routes-photo-proof:v1',
        'source diversity marker' => 'vg-best-routes-source-diversity:v1',
        'archetypes marker' => 'vg-best-routes-archetypes:v1',
        'trip length marker' => 'vg-best-routes-trip-length:v1',
        'open jaw marker' => 'vg-best-routes-open-jaw:v1',
        'movement cost marker' => 'vg-best-routes-movement-cost:v1',
        'regional spine marker' => 'vg-best-routes-regional-spine:v1',
        'season filter marker' => 'vg-best-routes-season-filter:v1',
        'traveler fit marker' => 'vg-best-routes-traveler-fit:v1',
        'red flags marker' => 'vg-best-routes-red-flags:v1',
        'live checks marker' => 'vg-best-routes-live-checks:v1',
        'FAQ marker' => 'vg-best-routes-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'Hanoi source URL' => 'https://vietnam.travel/places-to-go/northern-vietnam/ha-noi',
        'Transport source URL' => 'https://vietnam.travel/plan-your-trip/transport-within-vietnam',
        'Weather source URL' => 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam',
        'airport source URL' => 'https://vietnam.travel/things-to-do/travellers-guide-vietnams-airports',
        'open-jaw phrase' => 'open-jaw',
        'maximum-coverage phrase' => 'maximum-coverage itinerary',
    ] as $label => $needle
) {
    $haystack = str_contains($label, 'panel') || str_contains($label, 'trail') || str_contains($label, 'log') || str_contains($label, 'routes') || str_contains($label, 'source')
        ? $rendered_content
        : $raw_content . "\n" . $rendered_content;

    if (! str_contains($haystack, $needle)) {
        $failures[] = "Best Vietnam routes post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/plan/vietnam-travel-guide/',
        '/compare/north-central-south-vietnam/',
        '/itineraries/7-days-in-vietnam/',
        '/itineraries/10-days-in-vietnam/',
        '/itineraries/14-days-in-vietnam/',
        '/plan/transport-within-vietnam/',
        '/plan/best-time-to-visit-vietnam/',
        '/costs/vietnam-travel-cost/',
        '/destinations/hanoi-travel-guide/',
        '/destinations/ninh-binh-travel-guide/',
        '/destinations/ha-long-bay-travel-guide/',
        '/destinations/da-nang-travel-guide/',
        '/destinations/ho-chi-minh-city-travel-guide/',
        '/destinations/phu-quoc-travel-guide/',
        '/compare/da-nang-vs-hoi-an/',
    ] as $internal_path
) {
    if (! str_contains($raw_content . "\n" . $rendered_content, $internal_path)) {
        $failures[] = "Best Vietnam routes post is missing internal route: {$internal_path}";
    }
}

foreach (
    [
        'see everything',
        'guaranteed best route',
        'ultimate bucket list',
        'hidden gem route',
        'top 100 places',
    ] as $unsafe_phrase
) {
    if (str_contains($raw_content, $unsafe_phrase) || str_contains($rendered_content, $unsafe_phrase)) {
        $failures[] = "Best Vietnam routes post contains unsafe/spam phrase: {$unsafe_phrase}";
    }
}

if (str_contains($raw_content, 'Draft status:') || str_contains($rendered_content, 'Draft status:')) {
    $failures[] = 'Best Vietnam routes post still contains brief placeholder text: Draft status:';
}

if (! str_contains($rendered_content, 'vg-related-routes-title-' . $post_id)) {
    $failures[] = 'Best Vietnam routes post is missing rendered related routes shortcode output.';
}

if (strlen(wp_strip_all_tags($raw_content)) < 10500) {
    $failures[] = 'Best Vietnam routes post content is too thin for complete draft status.';
}

foreach (
    [
        'post_title' => 'Best Vietnam Routes for First-Time Visitors: North, Central, South or Open-Jaw?',
        'post_name' => 'best-vietnam-routes-first-time-visitors',
    ] as $field => $expected_value
) {
    $actual_value = (string) $post->{$field};

    if ($actual_value !== $expected_value) {
        $failures[] = "Best Vietnam routes post has unexpected {$field}: {$actual_value}; expected {$expected_value}";
    }
}

vg_verify_best_routes_assert_target_meta($post, $failures);

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
        $failures[] = "Best Vietnam routes post is missing required meta: {$meta_key}";
    }
}

foreach (
    [
        'vg_editorial_brief_status' => 'complete_draft',
        'rank_math_title' => 'Best Vietnam Routes for First-Time Visitors',
        'rank_math_focus_keyword' => 'best vietnam routes first time visitors',
        'vg_eeat_affiliate_status' => 'none',
        'vg_eeat_reviewed_guide' => '1',
    ] as $meta_key => $expected_value
) {
    $actual_value = (string) get_post_meta($post_id, $meta_key, true);

    if ($actual_value !== $expected_value) {
        $failures[] = "Best Vietnam routes post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
    }
}

vg_verify_best_routes_assert_exact_terms($failures, $post_id, 'category', ['travel-planning', 'itineraries']);
vg_verify_best_routes_assert_exact_terms($failures, $post_id, 'post_tag', ['route-planning', 'first-time-vietnam', 'anti-spam-evergreen']);

$external_body_href_count = vg_verify_best_routes_external_body_href_count($raw_content);

if ($external_body_href_count !== 0) {
    $failures[] = "Best Vietnam routes post external_body_href_count should be 0; found {$external_body_href_count}.";
}

$notes[] = 'Best Vietnam routes post ID: ' . $post_id;
$notes[] = 'Best Vietnam routes external_body_href_count: ' . $external_body_href_count;

vg_verify_best_routes_post_finish($failures, $notes);

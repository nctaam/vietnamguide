<?php
/**
 * Verify the Best Vietnam Cities for First-Time Visitors complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-best-vietnam-cities-first-time-visitors-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_best_cities_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Best Vietnam Cities post verification failed.');
        }

        echo 'Best Vietnam Cities post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Best Vietnam Cities post verification passed.');
        return;
    }

    echo 'Best Vietnam Cities post verification passed.' . PHP_EOL;
}

function vg_verify_best_cities_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_best_cities_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_best_cities_rendered_content(WP_Post $post): string
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

function vg_verify_best_cities_external_body_href_count(string $content): int
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

function vg_verify_best_cities_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_best_cities_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_best_cities_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

$failures = [];
$notes = [];
$post = vg_verify_best_cities_post_by_slug('best-vietnam-cities-for-first-time-visitors');

if (! $post instanceof WP_Post) {
    vg_verify_best_cities_post_finish(['Required post not found: best-vietnam-cities-for-first-time-visitors']);
}

$post_id = (int) $post->ID;

if ($post_id !== 497) {
    $failures[] = "Best Vietnam Cities post ID mismatch: {$post_id}; expected 497";
}

if ($post->post_status !== 'draft') {
    $failures[] = "Best Vietnam Cities post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Best Vietnam Cities comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Best Vietnam Cities ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_best_cities_rendered_content($post);
$combined = $raw_content . "\n" . $rendered_content . "\n" . implode(
    "\n",
    array_map(
        static fn (string $key): string => (string) get_post_meta($post_id, $key, true),
        [
            'vg_eeat_primary_decision',
            'vg_eeat_sources_checked',
            'vg_eeat_field_note',
            'vg_eeat_evidence_moat',
            'vg_eeat_related_routes',
            'vg_eeat_hero_image_credit',
            'vg_eeat_update_summary',
        ]
    )
);

foreach (
    [
        'hero marker' => 'vg-best-cities-hero:v1',
        'verdict marker' => 'vg-best-cities-concierge-verdict:v1',
        'at a glance marker' => 'vg-best-cities-at-a-glance:v1',
        'city role matrix marker' => 'vg-best-cities-city-role-matrix:v1',
        'photo proof marker' => 'vg-best-cities-photo-proof:v1',
        'source diversity marker' => 'vg-best-cities-source-diversity:v1',
        'base roles marker' => 'vg-best-cities-base-roles:v1',
        'city by city base logic marker' => 'vg-best-cities-city-by-city-base-logic:v1',
        'arrival departure marker' => 'vg-best-cities-arrival-departure:v1',
        'traveler fit marker' => 'vg-best-cities-traveler-fit:v1',
        'day trip pressure marker' => 'vg-best-cities-day-trip-pressure:v1',
        'skip logic marker' => 'vg-best-cities-skip-logic:v1',
        'live checks marker' => 'vg-best-cities-live-checks:v1',
        'FAQ marker' => 'vg-best-cities-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'Hanoi source URL' => 'https://vietnam.travel/places-to-go/northern-vietnam/ha-noi',
        'HCMC source URL' => 'https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city',
        'Hoi An source URL' => 'https://vietnam.travel/places-to-go/central-vietnam/hoi-an',
        'Hue source URL' => 'https://vietnam.travel/places-to-go/central-vietnam/hue',
        'Da Nang source URL' => 'https://vietnam.travel/places-to-go/central-vietnam/da-nang',
        'Transport source URL' => 'https://vietnam.travel/plan-your-trip/transport-within-vietnam',
        'Weather source URL' => 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam',
        'city-base phrase' => 'city-base decision',
        'route job phrase' => 'route job',
        'skip logic phrase' => 'Skip logic',
    ] as $label => $needle
) {
    if (! str_contains($combined, $needle)) {
        $failures[] = "Best Vietnam Cities post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/destinations/best-places-to-visit-vietnam/',
        '/plan/vietnam-travel-guide/',
        '/compare/north-central-south-vietnam/',
        '/plan/best-time-to-visit-vietnam/',
        '/destinations/hanoi-travel-guide/',
        '/destinations/ho-chi-minh-city-travel-guide/',
        '/destinations/da-nang-travel-guide/',
        '/destinations/best-things-to-do-in-hoi-an/',
        '/destinations/best-things-to-do-in-hue/',
        '/compare/da-nang-vs-hoi-an/',
        '/compare/hoi-an-vs-hue/',
        '/destinations/ninh-binh-travel-guide/',
        '/destinations/ha-long-bay-travel-guide/',
        '/destinations/phu-quoc-travel-guide/',
        '/plan/transport-within-vietnam/',
        '/itineraries/7-days-in-vietnam/',
        '/itineraries/10-days-in-vietnam/',
        '/itineraries/14-days-in-vietnam/',
        '/itineraries/21-days-in-vietnam/',
        '/costs/vietnam-travel-cost/',
    ] as $internal_path
) {
    if (! str_contains($combined, $internal_path)) {
        $failures[] = "Best Vietnam Cities post is missing internal route: {$internal_path}";
    }
}

foreach (
    [
        'top 100 cities',
        'ultimate city list',
        'guaranteed best city',
        'hidden gem city',
        'must visit every city',
        'one city is always best',
    ] as $unsafe_phrase
) {
    if (str_contains(strtolower($combined), strtolower($unsafe_phrase))) {
        $failures[] = "Best Vietnam Cities post contains unsafe phrase: {$unsafe_phrase}";
    }
}

if (strlen(wp_strip_all_tags($raw_content)) < 9000) {
    $failures[] = 'Best Vietnam Cities post content is too thin for a complete draft.';
}

foreach (
    [
        'rank_math_title',
        'rank_math_description',
        'rank_math_focus_keyword',
        'vg_editorial_brief_status',
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
        'vg_content_owner',
        'vg_automation_lock',
        'vg_editorial_target_publish_date',
    ] as $meta_key
) {
    $meta_value = get_post_meta($post_id, $meta_key, true);

    if ((is_string($meta_value) && trim($meta_value) === '') || $meta_value === [] || $meta_value === null) {
        $failures[] = "Best Vietnam Cities required meta missing: {$meta_key}";
    }
}

foreach (
    [
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_editorial_batch' => 'batch-2-evergreen-planning',
        'vg_editorial_target_publish_date' => '2026-08-15',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_affiliate_status' => 'none',
    ] as $meta_key => $expected_value
) {
    $actual_value = trim((string) get_post_meta($post_id, $meta_key, true));

    if ($actual_value !== $expected_value) {
        $failures[] = "Best Vietnam Cities {$meta_key} mismatch: {$actual_value}; expected {$expected_value}";
    }
}

foreach (['Draft status:', 'Editorial brief', 'Sources to check', 'Image plan', 'Publish gate'] as $placeholder) {
    if (str_contains($raw_content, $placeholder)) {
        $failures[] = "Best Vietnam Cities still contains brief placeholder: {$placeholder}";
    }
}

vg_verify_best_cities_assert_exact_terms($failures, $post_id, 'category', ['destinations', 'travel-planning']);
vg_verify_best_cities_assert_exact_terms($failures, $post_id, 'post_tag', ['first-time-vietnam', 'city-comparison', 'route-planning', 'anti-spam-evergreen']);

$external_body_href_count = vg_verify_best_cities_external_body_href_count($raw_content);
$notes[] = "Best Vietnam Cities post ID: {$post_id}";
$notes[] = "Best Vietnam Cities external_body_href_count: {$external_body_href_count}";

if ($external_body_href_count !== 0) {
    $failures[] = "Best Vietnam Cities external_body_href_count should be 0; found {$external_body_href_count}";
}

vg_verify_best_cities_post_finish($failures, $notes);

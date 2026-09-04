<?php
/**
 * Verify the Where to Stay in Vietnam base decisions complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-where-to-stay-in-vietnam-base-decisions-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_stay_vietnam_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Where to Stay in Vietnam base decisions post verification failed.');
        }

        echo 'Where to Stay in Vietnam base decisions post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Where to Stay in Vietnam base decisions post verification passed.');
        return;
    }

    echo 'Where to Stay in Vietnam base decisions post verification passed.' . PHP_EOL;
}

function vg_verify_stay_vietnam_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_stay_vietnam_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_stay_vietnam_assert_target_meta(WP_Post $post, array &$failures): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-09',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if ($actual_value !== $expected_value) {
            $failures[] = "Where to Stay in Vietnam post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
        }
    }
}

function vg_verify_stay_vietnam_rendered_content(WP_Post $post): string
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

function vg_verify_stay_vietnam_external_body_href_count(string $content): int
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

function vg_verify_stay_vietnam_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_stay_vietnam_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_stay_vietnam_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

$failures = [];
$notes = [];
$post = vg_verify_stay_vietnam_post_by_slug('where-to-stay-in-vietnam-base-decisions');

if (! $post instanceof WP_Post) {
    vg_verify_stay_vietnam_post_finish(['Required post not found: where-to-stay-in-vietnam-base-decisions']);
}

$post_id = (int) $post->ID;

if ($post->post_status !== 'draft') {
    $failures[] = "Where to Stay in Vietnam post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Where to Stay in Vietnam comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Where to Stay in Vietnam ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_stay_vietnam_rendered_content($post);

foreach (
    [
        'hero marker' => 'vg-stay-vietnam-hero:v1',
        'verdict marker' => 'vg-stay-vietnam-concierge-verdict:v1',
        'at a glance marker' => 'vg-stay-vietnam-at-a-glance:v1',
        'photo proof marker' => 'vg-stay-vietnam-photo-proof:v1',
        'source diversity marker' => 'vg-stay-vietnam-source-diversity:v1',
        'base jobs marker' => 'vg-stay-vietnam-base-jobs:v1',
        'city fit marker' => 'vg-stay-vietnam-city-fit:v1',
        'arrival departure marker' => 'vg-stay-vietnam-arrival-departure:v1',
        'family sleep marker' => 'vg-stay-vietnam-family-sleep:v1',
        'food access marker' => 'vg-stay-vietnam-food-access:v1',
        'quiet night marker' => 'vg-stay-vietnam-quiet-night:v1',
        'transfer pickup marker' => 'vg-stay-vietnam-transfer-pickup:v1',
        'booking filter marker' => 'vg-stay-vietnam-booking-filter:v1',
        'skip traps marker' => 'vg-stay-vietnam-skip-traps:v1',
        'live checks marker' => 'vg-stay-vietnam-live-checks:v1',
        'FAQ marker' => 'vg-stay-vietnam-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'Hanoi source URL' => 'https://vietnam.travel/places-to-go/northern-vietnam/ha-noi',
        'HCMC source URL' => 'https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city',
        'Ninh Binh source URL' => 'https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
        'transport source URL' => 'https://vietnam.travel/plan-your-trip/transport-within-vietnam',
        'airport source URL' => 'https://vietnam.travel/things-to-do/travellers-guide-vietnams-airports',
        'core decision phrase' => 'choose the city base before choosing the hotel',
    ] as $label => $needle
) {
    $haystack = str_contains($label, 'panel') || str_contains($label, 'trail') || str_contains($label, 'log') || str_contains($label, 'routes') || str_contains($label, 'source')
        ? $rendered_content
        : $raw_content . "\n" . $rendered_content;

    if (! str_contains($haystack, $needle)) {
        $failures[] = "Where to Stay in Vietnam post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/destinations/where-to-stay-in-hanoi/',
        '/destinations/where-to-stay-in-ho-chi-minh-city/',
        '/destinations/where-to-stay-in-ninh-binh/',
        '/compare/da-nang-vs-hoi-an/',
        '/destinations/hanoi-travel-guide/',
        '/destinations/ho-chi-minh-city-travel-guide/',
        '/destinations/da-nang-travel-guide/',
        '/destinations/ninh-binh-travel-guide/',
        '/destinations/phu-quoc-travel-guide/',
        '/plan/transport-within-vietnam/',
        '/plan/best-time-to-visit-vietnam/',
        '/itineraries/10-days-in-vietnam/',
    ] as $internal_path
) {
    if (! str_contains($raw_content . "\n" . $rendered_content, $internal_path)) {
        $failures[] = "Where to Stay in Vietnam post is missing internal route: {$internal_path}";
    }
}

foreach (
    [
        'best hotels in Vietnam',
        'top 100 hotels',
        'guaranteed best area',
        'hidden gem hotel',
        'book now',
    ] as $unsafe_phrase
) {
    if (str_contains($raw_content, $unsafe_phrase) || str_contains($rendered_content, $unsafe_phrase)) {
        $failures[] = "Where to Stay in Vietnam post contains unsafe/spam phrase: {$unsafe_phrase}";
    }
}

if (str_contains($raw_content, 'Draft status:') || str_contains($rendered_content, 'Draft status:')) {
    $failures[] = 'Where to Stay in Vietnam post still contains brief placeholder text: Draft status:';
}

if (! str_contains($rendered_content, 'vg-related-routes-title-' . $post_id)) {
    $failures[] = 'Where to Stay in Vietnam post is missing rendered related routes shortcode output.';
}

if (strlen(wp_strip_all_tags($raw_content)) < 10500) {
    $failures[] = 'Where to Stay in Vietnam post content is too thin for complete draft status.';
}

foreach (
    [
        'post_title' => 'Where to Stay in Vietnam: City Base Decisions Before Choosing Hotels',
        'post_name' => 'where-to-stay-in-vietnam-base-decisions',
    ] as $field => $expected_value
) {
    $actual_value = (string) $post->{$field};

    if ($actual_value !== $expected_value) {
        $failures[] = "Where to Stay in Vietnam post has unexpected {$field}: {$actual_value}; expected {$expected_value}";
    }
}

vg_verify_stay_vietnam_assert_target_meta($post, $failures);

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
        $failures[] = "Where to Stay in Vietnam post is missing required meta: {$meta_key}";
    }
}

foreach (
    [
        'vg_editorial_brief_status' => 'complete_draft',
        'rank_math_title' => 'Where to Stay in Vietnam: Base Decision Guide',
        'rank_math_focus_keyword' => 'where to stay in Vietnam',
        'vg_eeat_affiliate_status' => 'none',
        'vg_eeat_reviewed_guide' => '1',
    ] as $meta_key => $expected_value
) {
    $actual_value = (string) get_post_meta($post_id, $meta_key, true);

    if ($actual_value !== $expected_value) {
        $failures[] = "Where to Stay in Vietnam post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
    }
}

vg_verify_stay_vietnam_assert_exact_terms($failures, $post_id, 'category', ['hotels-neighborhoods', 'travel-planning']);
vg_verify_stay_vietnam_assert_exact_terms($failures, $post_id, 'post_tag', ['hotel-base', 'premium-travel', 'family-travel', 'anti-spam-evergreen']);

$external_body_href_count = vg_verify_stay_vietnam_external_body_href_count($raw_content);

if ($external_body_href_count !== 0) {
    $failures[] = "Where to Stay in Vietnam post external_body_href_count should be 0; found {$external_body_href_count}.";
}

$notes[] = 'Where to Stay in Vietnam post ID: ' . $post_id;
$notes[] = 'Where to Stay in Vietnam external_body_href_count: ' . $external_body_href_count;

vg_verify_stay_vietnam_post_finish($failures, $notes);

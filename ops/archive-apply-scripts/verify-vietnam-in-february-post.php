<?php
/**
 * Verify the Vietnam in February complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-vietnam-in-february-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_february_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Vietnam in February post verification failed.');
        }

        echo 'Vietnam in February post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Vietnam in February post verification passed.');
        return;
    }

    echo 'Vietnam in February post verification passed.' . PHP_EOL;
}

function vg_verify_february_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_february_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_february_rendered_content(WP_Post $post): string
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

function vg_verify_february_external_body_href_count(string $content): int
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

function vg_verify_february_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_february_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_february_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

$failures = [];
$notes = [];
$post = vg_verify_february_post_by_slug('vietnam-in-february');

if (! $post instanceof WP_Post) {
    vg_verify_february_post_finish(['Required post not found: vietnam-in-february']);
}

$post_id = (int) $post->ID;

if ($post->post_status !== 'draft') {
    $failures[] = "Vietnam in February post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Vietnam in February comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Vietnam in February ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_february_rendered_content($post);
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
        'hero marker' => 'vg-february-hero:v1',
        'verdict marker' => 'vg-february-concierge-verdict:v1',
        'at a glance marker' => 'vg-february-at-a-glance:v1',
        'photo proof marker' => 'vg-february-photo-proof:v1',
        'source diversity marker' => 'vg-february-source-diversity:v1',
        'region weather marker' => 'vg-february-region-weather:v1',
        'route chooser marker' => 'vg-february-route-chooser:v1',
        'Tet timing marker' => 'vg-february-tet-timing:v1',
        'booking marker' => 'vg-february-booking:v1',
        'packing marker' => 'vg-february-packing:v1',
        'fragile plans marker' => 'vg-february-fragile-plans:v1',
        'live checks marker' => 'vg-february-live-checks:v1',
        'FAQ marker' => 'vg-february-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'weather source URL' => 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam',
        'Tet source URL' => 'https://vietnam.travel/things-to-do/tet-vietnam-lunar-new-year',
        'transport source URL' => 'https://vietnam.travel/plan-your-trip/transport-within-vietnam',
        'NCHMF source URL' => 'https://nchmf.gov.vn/KttvsiteE/en-US/2/index.html',
        'WMO source URL' => 'https://worldweather.wmo.int/en/country.html?countryCode=82',
        'core decision phrase' => 'February route decision',
        'Tet timing phrase' => 'Tet timing',
        'post-Tet restart phrase' => 'post-Tet restart',
        'central and southern value phrase' => 'central and southern route value',
        'north warming phrase' => 'north warming slowly',
    ] as $label => $needle
) {
    if (! str_contains($combined, $needle)) {
        $failures[] = "Vietnam in February post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/plan/best-time-to-visit-vietnam/',
        '/travel-planning/tet-in-vietnam-travel-guide/',
        '/travel-planning/vietnam-in-january/',
        '/travel-planning/what-to-pack-for-vietnam-region-season/',
        '/plan/transport-within-vietnam/',
        '/itineraries/14-days-in-vietnam/',
        '/destinations/hoi-an-ancient-town-guide/',
        '/destinations/hue-imperial-city-guide/',
        '/destinations/phu-quoc-travel-guide/',
        '/costs/vietnam-travel-cost/',
    ] as $internal_path
) {
    if (! str_contains($combined, $internal_path)) {
        $failures[] = "Vietnam in February post is missing internal route: {$internal_path}";
    }
}

foreach (
    [
        'everything is open during Tet',
        'guaranteed beach weather',
        'no need to check Tet dates',
        'best month for everyone',
        'February travel hacks',
    ] as $unsafe_phrase
) {
    if (str_contains($combined, $unsafe_phrase)) {
        $failures[] = "Vietnam in February post contains unsafe/spam phrase: {$unsafe_phrase}";
    }
}

foreach (['Draft status:', 'Editorial brief', 'Sources to check', 'Image plan', 'Publish gate'] as $placeholder_text) {
    if (str_contains($raw_content, $placeholder_text) || str_contains($rendered_content, $placeholder_text)) {
        $failures[] = "Vietnam in February post still contains brief placeholder text: {$placeholder_text}";
    }
}

if (! str_contains($rendered_content, 'vg-related-routes-title-' . $post_id)) {
    $failures[] = 'Vietnam in February post is missing rendered related routes shortcode output.';
}

if (strlen(wp_strip_all_tags($raw_content)) < 9000) {
    $failures[] = 'Vietnam in February post content is too thin for complete draft status.';
}

foreach (
    [
        'post_title' => 'Vietnam in February: Weather, Tet Timing and Best Routes',
        'post_name' => 'vietnam-in-february',
    ] as $field => $expected_value
) {
    $actual_value = (string) $post->{$field};

    if ($actual_value !== $expected_value) {
        $failures[] = "Vietnam in February post has unexpected {$field}: {$actual_value}; expected {$expected_value}";
    }
}

foreach (
    [
        'rank_math_title' => 'Vietnam in February: Weather, Tet and Best Routes',
        'rank_math_description' => 'Plan Vietnam in February by Tet timing, post-Tet restart, regional weather, route shape, transport pressure, packing, and live checks.',
        'rank_math_focus_keyword' => 'Vietnam in February',
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_editorial_target_publish_date' => '2026-08-13',
        'vg_editorial_batch' => 'batch-2-evergreen-planning',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_reviewed_guide' => '1',
        'vg_eeat_written_by' => 'VietnamGuide editorial team',
        'vg_eeat_reviewed_by' => 'VietnamGuide editorial review',
        'vg_eeat_last_meaningful_update' => 'July 26, 2026',
        'vg_eeat_affiliate_status' => 'none',
    ] as $meta_key => $expected_value
) {
    $actual_value = (string) get_post_meta($post_id, $meta_key, true);

    if ($actual_value !== $expected_value) {
        $failures[] = "Vietnam in February post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
    }
}

foreach (
    [
        'rank_math_title',
        'rank_math_description',
        'rank_math_focus_keyword',
        'vg_editorial_brief_status',
        'vg_editorial_target_publish_date',
        'vg_editorial_batch',
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
        $failures[] = "Vietnam in February post is missing required meta: {$meta_key}";
    }
}

vg_verify_february_assert_exact_terms($failures, $post_id, 'category', ['seasonal-travel', 'travel-planning']);
vg_verify_february_assert_exact_terms($failures, $post_id, 'post_tag', ['month-by-month', 'tet-travel', 'route-planning', 'anti-spam-evergreen']);

$external_body_href_count = vg_verify_february_external_body_href_count($rendered_content);

if ($external_body_href_count !== 0) {
    $failures[] = "Vietnam in February post external_body_href_count should be 0; found {$external_body_href_count}.";
}

$notes[] = 'Vietnam in February post ID: ' . $post_id;
$notes[] = 'Vietnam in February external_body_href_count: ' . $external_body_href_count;

vg_verify_february_post_finish($failures, $notes);

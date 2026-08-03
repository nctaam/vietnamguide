<?php
/**
 * Verify the Tet in Vietnam Travel Guide complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-tet-in-vietnam-travel-guide-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_tet_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Tet in Vietnam Travel Guide post verification failed.');
        }

        echo 'Tet in Vietnam Travel Guide post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Tet in Vietnam Travel Guide post verification passed.');
        return;
    }

    echo 'Tet in Vietnam Travel Guide post verification passed.' . PHP_EOL;
}

function vg_verify_tet_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_tet_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_tet_rendered_content(WP_Post $post): string
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

function vg_verify_tet_external_body_href_count(string $content): int
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

function vg_verify_tet_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_tet_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_tet_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

$failures = [];
$notes = [];
$post = vg_verify_tet_post_by_slug('tet-in-vietnam-travel-guide');

if (! $post instanceof WP_Post) {
    vg_verify_tet_post_finish(['Required post not found: tet-in-vietnam-travel-guide']);
}

$post_id = (int) $post->ID;

if ($post->post_status !== 'draft') {
    $failures[] = "Tet in Vietnam post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Tet in Vietnam comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Tet in Vietnam ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_tet_rendered_content($post);
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
        'hero marker' => 'vg-tet-hero:v1',
        'verdict marker' => 'vg-tet-concierge-verdict:v1',
        'quick decision marker' => 'vg-tet-quick-decision:v1',
        'photo proof marker' => 'vg-tet-photo-proof:v1',
        'source diversity marker' => 'vg-tet-source-diversity:v1',
        'timing phases marker' => 'vg-tet-timing-phases:v1',
        'book early marker' => 'vg-tet-book-early:v1',
        'operating rhythm marker' => 'vg-tet-operating-rhythm:v1',
        'route chooser marker' => 'vg-tet-route-chooser:v1',
        'city choice marker' => 'vg-tet-city-choice:v1',
        'respectful behavior marker' => 'vg-tet-respectful-behavior:v1',
        'money food medicine marker' => 'vg-tet-money-food-medicine:v1',
        'fragile plans marker' => 'vg-tet-fragile-plans:v1',
        'live checks marker' => 'vg-tet-live-checks:v1',
        'FAQ marker' => 'vg-tet-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'Tet holiday source URL' => 'https://vietnam.travel/things-to-do/tet-vietnam-lunar-new-year',
        'Tet tradition source URL' => 'https://vietnam.travel/things-to-do/tet-tradition-reunion-taste',
        'transport source URL' => 'https://vietnam.travel/plan-your-trip/transport-within-vietnam',
        'weather source URL' => 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam',
        'Labor Code phrase' => 'Vietnam Labor Code 2019',
        'official announcement phrase' => 'official public-holiday announcement',
        'WMO phrase' => 'World Weather Information Service',
        'Tet travel decision phrase' => 'Tet travel decision',
        'before/during/after phrase' => 'before, during, and after Tet',
        'post-Tet restart phrase' => 'post-Tet restart',
        'uneven closures phrase' => 'closures are uneven',
        'transport phrase' => 'book transport early',
        'respect phrase' => 'respectful cultural behavior',
        'date caveat phrase' => 'exact Tet dates change yearly',
        'public holiday days phrase' => '5 public holiday days',
    ] as $label => $needle
) {
    if (! str_contains($combined, $needle)) {
        $failures[] = "Tet in Vietnam post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/plan/best-time-to-visit-vietnam/',
        '/plan/transport-within-vietnam/',
        '/travel-planning/vietnam-in-january/',
        '/travel-planning/vietnam-in-february/',
        '/travel-planning/vietnam-food-safety-street-food-etiquette/',
        '/travel-planning/vietnam-airport-arrival-checklist/',
        '/plan/money-cash-cards-atms/',
        '/plan/sim-esim-vietnam/',
        '/costs/vietnam-travel-cost/',
        '/itineraries/14-days-in-vietnam/',
    ] as $internal_path
) {
    if (! str_contains($combined, $internal_path)) {
        $failures[] = "Tet in Vietnam post is missing internal route: {$internal_path}";
    }
}

foreach (
    [
        'everything is closed during Tet',
        'everything is open during Tet',
        'Tet dates are the same every year',
        'no need to check Tet dates',
        'guaranteed availability',
        'Tet travel hacks',
        'avoid Vietnam during Tet',
        'locals hate tourists during Tet',
        'public holiday means all businesses close',
    ] as $unsafe_phrase
) {
    if (str_contains($combined, $unsafe_phrase)) {
        $failures[] = "Tet in Vietnam post contains unsafe/spam phrase: {$unsafe_phrase}";
    }
}

foreach (['Draft status:', 'Editorial brief', 'Sources to check', 'Image plan', 'Publish gate'] as $placeholder_text) {
    if (str_contains($raw_content, $placeholder_text) || str_contains($rendered_content, $placeholder_text)) {
        $failures[] = "Tet in Vietnam post still contains brief placeholder text: {$placeholder_text}";
    }
}

if (! str_contains($rendered_content, 'vg-related-routes-title-' . $post_id)) {
    $failures[] = 'Tet in Vietnam post is missing rendered related routes shortcode output.';
}

if (strlen(wp_strip_all_tags($raw_content)) < 11000) {
    $failures[] = 'Tet in Vietnam post content is too thin for complete draft status.';
}

foreach (
    [
        'post_title' => 'Tet in Vietnam Travel Guide: What International Visitors Should Know',
        'post_name' => 'tet-in-vietnam-travel-guide',
    ] as $field => $expected_value
) {
    $actual_value = (string) $post->{$field};

    if ($actual_value !== $expected_value) {
        $failures[] = "Tet in Vietnam post has unexpected {$field}: {$actual_value}; expected {$expected_value}";
    }
}

foreach (
    [
        'rank_math_title' => 'Tet in Vietnam Travel Guide: Dates, Closures and Routes',
        'rank_math_description' => 'Plan Tet travel in Vietnam by exact dates, transport pressure, changed openings, respectful behavior, money, meals, medicine, route choices, and live checks.',
        'rank_math_focus_keyword' => 'Tet in Vietnam travel guide',
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_editorial_target_publish_date' => '2026-08-14',
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
        $failures[] = "Tet in Vietnam post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
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
        $failures[] = "Tet in Vietnam post is missing required meta: {$meta_key}";
    }
}

vg_verify_tet_assert_exact_terms($failures, $post_id, 'category', ['seasonal-travel', 'food-culture', 'travel-planning']);
vg_verify_tet_assert_exact_terms($failures, $post_id, 'post_tag', ['tet-travel', 'international-travelers', 'route-planning', 'anti-spam-evergreen']);

$external_body_href_count = vg_verify_tet_external_body_href_count($rendered_content);

if ($external_body_href_count !== 0) {
    $failures[] = "Tet in Vietnam post external_body_href_count should be 0; found {$external_body_href_count}.";
}

$notes[] = 'Tet in Vietnam post ID: ' . $post_id;
$notes[] = 'Tet in Vietnam external_body_href_count: ' . $external_body_href_count;

vg_verify_tet_post_finish($failures, $notes);

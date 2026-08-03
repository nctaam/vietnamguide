<?php
/**
 * Verify the Hue Imperial City complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-hue-imperial-city-guide-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_hue_imperial_city_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Hue Imperial City post verification failed.');
        }

        echo 'Hue Imperial City post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Hue Imperial City post verification passed.');
        return;
    }

    echo 'Hue Imperial City post verification passed.' . PHP_EOL;
}

function vg_verify_hue_imperial_city_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_hue_imperial_city_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_hue_imperial_city_rendered_content(WP_Post $post): string
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

function vg_verify_hue_imperial_city_external_body_href_count(string $content): int
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

function vg_verify_hue_imperial_city_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_hue_imperial_city_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_hue_imperial_city_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

$failures = [];
$notes = [];
$post = vg_verify_hue_imperial_city_post_by_slug('hue-imperial-city-guide');

if (! $post instanceof WP_Post) {
    vg_verify_hue_imperial_city_post_finish(['Required post not found: hue-imperial-city-guide']);
}

$post_id = (int) $post->ID;

if ($post_id !== 500) {
    $failures[] = "Hue Imperial City post ID mismatch: {$post_id}; expected 500";
}

if ($post->post_status !== 'draft') {
    $failures[] = "Hue Imperial City post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Hue Imperial City comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Hue Imperial City ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_hue_imperial_city_rendered_content($post);
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
        'hero marker' => 'vg-hue-imperial-city-hero:v1',
        'verdict marker' => 'vg-hue-imperial-city-concierge-verdict:v1',
        'stay halfday pass marker' => 'vg-hue-imperial-city-stay-halfday-pass:v1',
        'decision matrix marker' => 'vg-hue-imperial-city-decision-matrix:v1',
        'GSC demand marker' => 'vg-hue-imperial-city-gsc-demand:v1',
        'photo proof marker' => 'vg-hue-imperial-city-photo-proof:v1',
        'source diversity marker' => 'vg-hue-imperial-city-source-diversity:v1',
        'time budget marker' => 'vg-hue-imperial-city-time-budget:v1',
        'pacing marker' => 'vg-hue-imperial-city-pacing:v1',
        'tombs marker' => 'vg-hue-imperial-city-tombs:v1',
        'guide value marker' => 'vg-hue-imperial-city-guide-value:v1',
        'heat rain marker' => 'vg-hue-imperial-city-heat-rain:v1',
        'route order marker' => 'vg-hue-imperial-city-route-order:v1',
        'ticket live check marker' => 'vg-hue-imperial-city-ticket-live-check:v1',
        'food river marker' => 'vg-hue-imperial-city-food-river:v1',
        'trip length marker' => 'vg-hue-imperial-city-trip-length:v1',
        'skip logic marker' => 'vg-hue-imperial-city-skip-logic:v1',
        'live checks marker' => 'vg-hue-imperial-city-live-checks:v1',
        'FAQ marker' => 'vg-hue-imperial-city-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'UNESCO source URL' => 'https://whc.unesco.org/en/list/678/',
        'UNESCO map source URL' => 'https://whc.unesco.org/en/list/678/maps/',
        'Hue ticket source URL' => 'https://hueworldheritage.org.vn/en-us/Tourism-information/Price',
        'Hue opening source URL' => 'https://hueworldheritage.org.vn/en-us/tabid/99/language/en-US/Default.aspx/tid/OpeningClosing-times-at-monumental-sites.html/pid/3839/cid/208/',
        'Hue Imperial City official source URL' => 'https://hueworldheritage.org.vn/en-us/Home/tid/Hue-Imperial-City/pid/6D26AD43-0DCF-48F7-8E7F-AF6000AC75D4',
        'Hue guide interpretation source URL' => 'https://hueworldheritage.org.vn/en-us/tabid/151/language/en-US/Default.aspx/tid/Guide-and-interpretation-at-Hue-Imperial-City/pid/2961CB09-F12F-4585-97A3-AF6000B2C10D',
        'Hue source URL' => 'https://vietnam.travel/places-to-go/central-vietnam/hue',
        'Hue itinerary source URL' => 'https://vietnam.travel/things-to-do/hue-itinerary',
        'Hue tombs source URL' => 'https://vietnam.travel/things-to-do/an-inside-guide-hue-tombs',
        'Central Vietnam source URL' => 'https://vietnam.travel/places-to-go/central-vietnam',
        'weather source URL' => 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam',
        'transport source URL' => 'https://vietnam.travel/plan-your-trip/transport-within-vietnam',
        'Hai Van route source URL' => 'https://vietnam.travel/things-to-do/motorbiking-hoi-an-hue-over-hai-van-pass',
        'GSC source phrase' => 'Google Search Console query export',
        'Imperial City phrase' => 'Imperial City',
        'ticket live-check phrase' => 'ticket',
        'opening live-check phrase' => 'opening',
        'guide value phrase' => 'guide',
        'time budget phrase' => 'Time budget',
        'route job phrase' => 'route job',
        'skip logic phrase' => 'Skip logic',
    ] as $label => $needle
) {
    if (! str_contains($combined, $needle)) {
        $failures[] = "Hue Imperial City post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/destinations/best-things-to-do-in-hue/',
        '/compare/hoi-an-vs-hue/',
        '/destinations/unesco-heritage-sites-vietnam/',
        '/compare/da-nang-vs-hoi-an/',
        '/destinations/da-nang-travel-guide/',
        '/destinations/best-things-to-do-in-hoi-an/',
        '/plan/vietnam-travel-guide/',
        '/compare/north-central-south-vietnam/',
        '/plan/best-time-to-visit-vietnam/',
        '/plan/transport-within-vietnam/',
        '/itineraries/7-days-in-vietnam/',
        '/itineraries/10-days-in-vietnam/',
        '/itineraries/14-days-in-vietnam/',
        '/costs/vietnam-travel-cost/',
    ] as $internal_path
) {
    if (! str_contains($combined, $internal_path)) {
        $failures[] = "Hue Imperial City post is missing internal route: {$internal_path}";
    }
}

foreach (
    [
        '/destinations/hoi-an-ancient-town-guide/',
        '/destinations/da-nang-beaches-guide/',
    ] as $unpublished_path
) {
    if (str_contains($combined, $unpublished_path)) {
        $failures[] = "Hue Imperial City post contains unpublished internal route: {$unpublished_path}";
    }
}

foreach (
    [
        'hidden gem',
        'ultimate Hue list',
        'must see everything',
        'perfect imperial route',
        'all tombs are mandatory',
        'copy this itinerary',
        'guaranteed best time',
    ] as $unsafe_phrase
) {
    if (str_contains(strtolower($combined), strtolower($unsafe_phrase))) {
        $failures[] = "Hue Imperial City post contains unsafe phrase: {$unsafe_phrase}";
    }
}

if (strlen(wp_strip_all_tags($raw_content)) < 11000) {
    $failures[] = 'Hue Imperial City post content is too thin for a complete draft.';
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
        $failures[] = "Hue Imperial City required meta missing: {$meta_key}";
    }
}

foreach (
    [
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_editorial_batch' => 'batch-2-evergreen-planning',
        'vg_editorial_target_publish_date' => '2026-08-18',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_affiliate_status' => 'none',
    ] as $meta_key => $expected_value
) {
    $actual_value = trim((string) get_post_meta($post_id, $meta_key, true));

    if ($actual_value !== $expected_value) {
        $failures[] = "Hue Imperial City {$meta_key} mismatch: {$actual_value}; expected {$expected_value}";
    }
}

foreach (['Draft status:', 'Editorial brief', 'Sources to check', 'Image plan', 'Publish gate'] as $placeholder) {
    if (str_contains($raw_content, $placeholder)) {
        $failures[] = "Hue Imperial City still contains brief placeholder: {$placeholder}";
    }
}

vg_verify_hue_imperial_city_assert_exact_terms($failures, $post_id, 'category', ['destinations', 'heritage-culture']);
vg_verify_hue_imperial_city_assert_exact_terms($failures, $post_id, 'post_tag', ['heritage-travel', 'route-planning', 'first-time-vietnam', 'anti-spam-evergreen']);

$external_body_href_count = vg_verify_hue_imperial_city_external_body_href_count($raw_content);
$notes[] = "Hue Imperial City post ID: {$post_id}";
$notes[] = "Hue Imperial City external_body_href_count: {$external_body_href_count}";

if ($external_body_href_count !== 0) {
    $failures[] = "Hue Imperial City external_body_href_count should be 0; found {$external_body_href_count}";
}

vg_verify_hue_imperial_city_post_finish($failures, $notes);

<?php
/**
 * Verify the Phong Nha Travel Guide complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-phong-nha-travel-guide-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_phong_nha_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Phong Nha post verification failed.');
        }

        echo 'Phong Nha post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Phong Nha post verification passed.');
        return;
    }

    echo 'Phong Nha post verification passed.' . PHP_EOL;
}

function vg_verify_phong_nha_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_phong_nha_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_phong_nha_rendered_content(WP_Post $post): string
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

function vg_verify_phong_nha_body_hrefs(string $content): array
{
    preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])([^"\']+)\1/i', $content, $matches);

    if ($matches[2] === []) {
        return [];
    }

    return array_values(array_unique(array_map('trim', array_map('strval', $matches[2]))));
}

function vg_verify_phong_nha_external_body_href_count(string $content): int
{
    $hrefs = vg_verify_phong_nha_body_hrefs($content);
    $site_host = parse_url(home_url('/'), PHP_URL_HOST);
    $count = 0;

    foreach ($hrefs as $href) {
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

function vg_verify_phong_nha_internal_path_from_href(string $href): ?string
{
    $href = html_entity_decode(trim($href), ENT_QUOTES);

    if ($href === '' || $href === '#' || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
        return null;
    }

    $site_host = parse_url(home_url('/'), PHP_URL_HOST);
    $path = null;

    if (str_starts_with($href, '//')) {
        $href_host = parse_url('https:' . $href, PHP_URL_HOST);

        if (! $site_host || ! $href_host || strcasecmp($site_host, $href_host) !== 0) {
            return null;
        }

        $path = parse_url('https:' . $href, PHP_URL_PATH);
    } elseif (preg_match('#^https?://#i', $href)) {
        $href_host = parse_url($href, PHP_URL_HOST);

        if (! $site_host || ! $href_host || strcasecmp($site_host, $href_host) !== 0) {
            return null;
        }

        $path = parse_url($href, PHP_URL_PATH);
    } elseif (str_starts_with($href, '/')) {
        $path = parse_url($href, PHP_URL_PATH);
    }

    if (! is_string($path) || trim($path) === '') {
        return null;
    }

    $normalized = '/' . trim(rawurldecode($path), '/');

    return $normalized === '/' ? '/' : trailingslashit($normalized);
}

function vg_verify_phong_nha_internal_body_hrefs(string $content): array
{
    $internal = [];

    foreach (vg_verify_phong_nha_body_hrefs($content) as $href) {
        $path = vg_verify_phong_nha_internal_path_from_href($href);

        if ($path === null || $path === '/') {
            continue;
        }

        $internal[$path] = $href;
    }

    ksort($internal);

    return $internal;
}

function vg_verify_phong_nha_post_id_for_internal_path(string $path): int
{
    $post_id = url_to_postid(home_url($path));

    if ($post_id > 0) {
        return (int) $post_id;
    }

    $post = get_page_by_path(trim($path, '/'), OBJECT, ['page', 'post']);

    if ($post instanceof WP_Post) {
        return (int) $post->ID;
    }

    return 0;
}

function vg_verify_phong_nha_internal_path_http_status(string $path): int
{
    $url = home_url($path);
    $response = wp_remote_head($url, ['timeout' => 12, 'redirection' => 5]);

    if (is_wp_error($response)) {
        $response = wp_remote_get($url, ['timeout' => 12, 'redirection' => 5]);
    }

    if (is_wp_error($response)) {
        return 0;
    }

    return (int) wp_remote_retrieve_response_code($response);
}

function vg_verify_phong_nha_assert_internal_body_hrefs_published(array &$failures, string $content): void
{
    foreach (vg_verify_phong_nha_internal_body_hrefs($content) as $path => $href) {
        $linked_post_id = vg_verify_phong_nha_post_id_for_internal_path($path);

        if ($linked_post_id <= 0) {
            $failures[] = "Phong Nha internal body link does not resolve: {$href}";
            continue;
        }

        $linked_post = get_post($linked_post_id);

        if (! $linked_post instanceof WP_Post || $linked_post->post_status !== 'publish') {
            $status = $linked_post instanceof WP_Post ? $linked_post->post_status : 'missing';
            $failures[] = "Phong Nha internal body link is not published: {$href}; status {$status}";
            continue;
        }

        $http_status = vg_verify_phong_nha_internal_path_http_status($path);

        if ($http_status !== 200) {
            $failures[] = "Phong Nha internal body link does not return HTTP 200: {$href}; status {$http_status}";
        }
    }
}

function vg_verify_phong_nha_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_phong_nha_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_phong_nha_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

$failures = [];
$notes = [];
$post = vg_verify_phong_nha_post_by_slug('phong-nha-travel-guide');

if (! $post instanceof WP_Post) {
    vg_verify_phong_nha_post_finish(['Required post not found: phong-nha-travel-guide']);
}

$post_id = (int) $post->ID;

if ($post_id !== 503) {
    $failures[] = "Phong Nha post ID mismatch: {$post_id}; expected 503";
}

if ($post->post_title !== 'Phong Nha Travel Guide: Caves, Seasons and Route Fit') {
    $failures[] = "Phong Nha post_title mismatch: {$post->post_title}";
}

if ($post->post_name !== 'phong-nha-travel-guide') {
    $failures[] = "Phong Nha post_name mismatch: {$post->post_name}";
}

if ($post->post_status !== 'draft') {
    $failures[] = "Phong Nha post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Phong Nha comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Phong Nha ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_phong_nha_rendered_content($post);
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
        'hero marker' => 'vg-phong-nha-hero:v1',
        'verdict marker' => 'vg-phong-nha-concierge-verdict:v1',
        'internal demand marker' => 'vg-phong-nha-internal-demand:v1',
        'photo proof marker' => 'vg-phong-nha-photo-proof:v1',
        'source diversity marker' => 'vg-phong-nha-source-diversity:v1',
        'add skip matrix marker' => 'vg-phong-nha-add-skip-matrix:v1',
        'night count marker' => 'vg-phong-nha-night-count:v1',
        'cave chooser marker' => 'vg-phong-nha-cave-chooser:v1',
        'season rain marker' => 'vg-phong-nha-season-rain:v1',
        'safety insurance marker' => 'vg-phong-nha-safety-insurance:v1',
        'route fit marker' => 'vg-phong-nha-route-fit:v1',
        'booking checks marker' => 'vg-phong-nha-booking-checks:v1',
        'itinerary length marker' => 'vg-phong-nha-itinerary-length:v1',
        'live checks marker' => 'vg-phong-nha-live-checks:v1',
        'FAQ marker' => 'vg-phong-nha-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'UNESCO source URL' => 'https://whc.unesco.org/en/list/951/',
        'UNESCO decision URL' => 'https://whc.unesco.org/en/decisions/8942/',
        'Vietnam.travel source URL' => 'https://vietnam.travel/places-to-go/central-vietnam/phong-nha',
        'official park source URL' => 'https://phongnhakebang.vn/',
        'official Phong Nha Cave URL' => 'https://phongnhakebang.vn/travel/phong-nha-ky-quan-de-nhat-dong1-en',
        'official expedition route URL' => 'https://phongnhakebang.vn/travel/tuyen-hang-dai-a-hang-over-hang-pygmy31-en',
        'Oxalis chooser URL' => 'https://oxalisadventure.com/how-tour-choose-the-right-cave-tour-oxalis/',
        'Oxalis Son Doong URL' => 'https://oxalisadventure.com/tour/son-doong-cave-expedition-4d3n/',
        'Oxalis Tu Lan URL' => 'https://oxalisadventure.com/tour/tu-lan-expedition/',
        'Oxalis booking URL' => 'https://oxalisadventure.com/booking-conditions/',
        'Dong Hoi airport URL' => 'https://acv.vn/en/airports/dong-hoi-airport',
        'Vietnam Railways URL' => 'https://dsvn.vn/',
        'NCHMF URL' => 'https://nchmf.gov.vn/Kttv/en-US/1/index.html',
        '2025 phrase' => '2025',
        'Hin Nam No phrase' => 'Hin Nam No',
        'Dong Hoi phrase' => 'Dong Hoi',
        'Son River phrase' => 'Son River',
        'Paradise Cave phrase' => 'Paradise Cave',
        'Son Doong phrase' => 'Son Doong',
        'fitness phrase' => 'fitness',
        'insurance phrase' => 'insurance',
        'Skip logic phrase' => 'Skip logic',
    ] as $label => $needle
) {
    if (! str_contains($combined, $needle)) {
        $failures[] = "Phong Nha post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/destinations/unesco-heritage-sites-vietnam/',
        '/compare/north-central-south-vietnam/',
        '/plan/best-time-to-visit-vietnam/',
        '/plan/transport-within-vietnam/',
        '/plan/health-travel-insurance-vietnam/',
        '/itineraries/10-days-in-vietnam/',
        '/itineraries/14-days-in-vietnam/',
        '/itineraries/21-days-in-vietnam/',
        '/destinations/da-nang-travel-guide/',
        '/compare/da-nang-vs-hoi-an/',
        '/compare/hoi-an-vs-hue/',
        '/destinations/ninh-binh-travel-guide/',
        '/destinations/hanoi-travel-guide/',
    ] as $internal_path
) {
    if (! str_contains($combined, $internal_path)) {
        $failures[] = "Phong Nha post is missing internal route: {$internal_path}";
    }
}

foreach (
    [
        '/destinations/phong-nha-travel-guide/',
        '/destinations/hue-travel-guide/',
        '/destinations/hoi-an-travel-guide/',
        '/destinations/best-places-to-visit-in-vietnam/',
        '/destinations/sapa-travel-guide/',
        '/destinations/ha-giang-travel-guide/',
        '/destinations/mekong-delta-overnight-vs-day-trip/',
    ] as $unpublished_path
) {
    if (str_contains($combined, $unpublished_path)) {
        $failures[] = "Phong Nha post contains unpublished internal route: {$unpublished_path}";
    }
}

foreach (
    [
        'hidden gem',
        'must-visit',
        'best caves in Vietnam',
        'worlds most beautiful caves',
        'safe for everyone',
        'year-round destination',
        'easy from Hoi An',
        'untouched paradise',
        'guaranteed cave access',
        'copy this itinerary',
    ] as $unsafe_phrase
) {
    if (str_contains(strtolower($combined), strtolower($unsafe_phrase))) {
        $failures[] = "Phong Nha post contains unsafe phrase: {$unsafe_phrase}";
    }
}

if (strlen(wp_strip_all_tags($raw_content)) < 11000) {
    $failures[] = 'Phong Nha post content is too thin for a complete draft.';
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
        $failures[] = "Phong Nha required meta missing: {$meta_key}";
    }
}

foreach (
    [
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_editorial_batch' => 'batch-2-evergreen-planning',
        'vg_editorial_target_publish_date' => '2026-08-21',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_affiliate_status' => 'none',
    ] as $meta_key => $expected_value
) {
    $actual_value = trim((string) get_post_meta($post_id, $meta_key, true));

    if ($actual_value !== $expected_value) {
        $failures[] = "Phong Nha {$meta_key} mismatch: {$actual_value}; expected {$expected_value}";
    }
}

foreach (['Draft status:', 'Editorial brief', 'Sources to check', 'Image plan', 'Publish gate'] as $placeholder) {
    if (str_contains($raw_content, $placeholder)) {
        $failures[] = "Phong Nha still contains brief placeholder: {$placeholder}";
    }
}

vg_verify_phong_nha_assert_exact_terms($failures, $post_id, 'category', ['destinations', 'heritage-culture']);
vg_verify_phong_nha_assert_exact_terms($failures, $post_id, 'post_tag', ['route-planning', 'heritage-travel', 'cave-planning', 'anti-spam-evergreen']);
vg_verify_phong_nha_assert_internal_body_hrefs_published($failures, $rendered_content);

$raw_external_body_href_count = vg_verify_phong_nha_external_body_href_count($raw_content);
$rendered_external_body_href_count = vg_verify_phong_nha_external_body_href_count($rendered_content);
$external_body_href_count = $raw_external_body_href_count + $rendered_external_body_href_count;
$notes[] = "Phong Nha post ID: {$post_id}";
$notes[] = "Phong Nha raw_external_body_href_count: {$raw_external_body_href_count}";
$notes[] = "Phong Nha rendered_external_body_href_count: {$rendered_external_body_href_count}";
$notes[] = "Phong Nha external_body_href_count: {$external_body_href_count}";

if ($raw_external_body_href_count !== 0) {
    $failures[] = "Phong Nha raw_external_body_href_count should be 0; found {$raw_external_body_href_count}";
}

if ($rendered_external_body_href_count !== 0) {
    $failures[] = "Phong Nha rendered_external_body_href_count should be 0; found {$rendered_external_body_href_count}";
}

if ($external_body_href_count !== 0) {
    $failures[] = "Phong Nha external_body_href_count should be 0; found {$external_body_href_count}";
}

vg_verify_phong_nha_post_finish($failures, $notes);

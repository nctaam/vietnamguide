<?php
/**
 * Verify the Mekong Delta Overnight vs Day Trip complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-mekong-delta-overnight-vs-day-trip-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_mekong_overnight_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Mekong Delta Overnight post verification failed.');
        }

        echo 'Mekong Delta Overnight post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Mekong Delta Overnight post verification passed.');
        return;
    }

    echo 'Mekong Delta Overnight post verification passed.' . PHP_EOL;
}

function vg_verify_mekong_overnight_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_mekong_overnight_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_mekong_overnight_rendered_content(WP_Post $post): string
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

function vg_verify_mekong_overnight_body_hrefs(string $content): array
{
    preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])([^"\']+)\1/i', $content, $matches);

    if ($matches[2] === []) {
        return [];
    }

    return array_values(array_unique(array_map('trim', array_map('strval', $matches[2]))));
}

function vg_verify_mekong_overnight_external_body_href_count(string $content): int
{
    $hrefs = vg_verify_mekong_overnight_body_hrefs($content);
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

function vg_verify_mekong_overnight_internal_path_from_href(string $href): ?string
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

function vg_verify_mekong_overnight_internal_body_hrefs(string $content): array
{
    $internal = [];

    foreach (vg_verify_mekong_overnight_body_hrefs($content) as $href) {
        $path = vg_verify_mekong_overnight_internal_path_from_href($href);

        if ($path === null || $path === '/') {
            continue;
        }

        $internal[$path] = $href;
    }

    ksort($internal);

    return $internal;
}

function vg_verify_mekong_overnight_post_id_for_internal_path(string $path): int
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

function vg_verify_mekong_overnight_assert_internal_body_hrefs_published(array &$failures, string $content): void
{
    foreach (vg_verify_mekong_overnight_internal_body_hrefs($content) as $path => $href) {
        $linked_post_id = vg_verify_mekong_overnight_post_id_for_internal_path($path);

        if ($linked_post_id <= 0) {
            $failures[] = "Mekong Delta Overnight internal body link does not resolve: {$href}";
            continue;
        }

        $linked_post = get_post($linked_post_id);

        if (! $linked_post instanceof WP_Post || $linked_post->post_status !== 'publish') {
            $status = $linked_post instanceof WP_Post ? $linked_post->post_status : 'missing';
            $failures[] = "Mekong Delta Overnight internal body link is not published: {$href}; status {$status}";
        }
    }
}

function vg_verify_mekong_overnight_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_mekong_overnight_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_mekong_overnight_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

$failures = [];
$notes = [];
$post = vg_verify_mekong_overnight_post_by_slug('mekong-delta-overnight-vs-day-trip');

if (! $post instanceof WP_Post) {
    vg_verify_mekong_overnight_post_finish(['Required post not found: mekong-delta-overnight-vs-day-trip']);
}

$post_id = (int) $post->ID;

if ($post_id !== 502) {
    $failures[] = "Mekong Delta Overnight post ID mismatch: {$post_id}; expected 502";
}

if ($post->post_title !== 'Mekong Delta Overnight vs Day Trip: Which Is Worth It?') {
    $failures[] = "Mekong Delta Overnight post_title mismatch: {$post->post_title}";
}

if ($post->post_name !== 'mekong-delta-overnight-vs-day-trip') {
    $failures[] = "Mekong Delta Overnight post_name mismatch: {$post->post_name}";
}

if ($post->post_status !== 'draft') {
    $failures[] = "Mekong Delta Overnight post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Mekong Delta Overnight comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Mekong Delta Overnight ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_mekong_overnight_rendered_content($post);
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
        'hero marker' => 'vg-mekong-overnight-hero:v1',
        'verdict marker' => 'vg-mekong-overnight-concierge-verdict:v1',
        'GSC demand marker' => 'vg-mekong-overnight-gsc-demand:v1',
        'decision matrix marker' => 'vg-mekong-overnight-decision-matrix:v1',
        'photo proof marker' => 'vg-mekong-overnight-photo-proof:v1',
        'source diversity marker' => 'vg-mekong-overnight-source-diversity:v1',
        'day trip fit marker' => 'vg-mekong-overnight-day-trip-fit:v1',
        'upgrade logic marker' => 'vg-mekong-overnight-upgrade-logic:v1',
        'Can Tho Cai Rang marker' => 'vg-mekong-overnight-can-tho-cai-rang:v1',
        'Ben Tre My Tho marker' => 'vg-mekong-overnight-ben-tre-my-tho:v1',
        'family comfort marker' => 'vg-mekong-overnight-family-comfort:v1',
        'transport flight day marker' => 'vg-mekong-overnight-transport-flight-day:v1',
        'route length marker' => 'vg-mekong-overnight-route-length:v1',
        'upgrade skip logic marker' => 'vg-mekong-overnight-upgrade-skip-logic:v1',
        'live checks marker' => 'vg-mekong-overnight-live-checks:v1',
        'FAQ marker' => 'vg-mekong-overnight-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'day trip source URL' => 'https://vietnam.travel/things-to-do/day-tripping-mekong-delta',
        '4 days source URL' => 'https://vietnam.travel/things-to-do/4-memorable-days-mekong-delta',
        'towns source URL' => 'https://vietnam.travel/things-to-do/towns-mekong-delta',
        'Can Tho river garden source URL' => 'https://vietnam.travel/things-to-do/can-tho-glimpse-river-and-garden',
        'floating markets source URL' => 'https://vietnam.travel/things-to-do/chasing-floating-flavour-mekong-deltas-floating-markets',
        'how to travel Mekong source URL' => 'https://vietnam.travel/things-to-do/how-to-travel-mekong-delta',
        'Cai Be source URL' => 'https://vietnam.travel/things-to-do/5-reasons-youll-love-cai-be',
        'Can Tho source URL' => 'https://vietnam.travel/places-to-go/southern-vietnam/can-tho',
        'Chau Doc source URL' => 'https://vietnam.travel/places-to-go/southern-vietnam/chau-doc',
        'HCMC source URL' => 'https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city',
        'weather source URL' => 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam',
        'transport source URL' => 'https://vietnam.travel/plan-your-trip/transport-within-vietnam',
        'Tourism Can Tho URL' => 'https://tourismcantho.vn',
        'NCHMF Can Tho URL' => 'https://nchmf.gov.vn/kttvsiteE/en-US/2/can-tho-w58.html',
        'GSC source phrase' => 'Google Search Console query export',
        'Cai Rang phrase' => 'Cai Rang',
        'Can Tho phrase' => 'Can Tho',
        'Ben Tre phrase' => 'Ben Tre',
        'My Tho phrase' => 'My Tho',
        'Chau Doc phrase' => 'Chau Doc',
        'flight day phrase' => 'flight-day',
        'skip logic phrase' => 'Skip logic',
    ] as $label => $needle
) {
    if (! str_contains($combined, $needle)) {
        $failures[] = "Mekong Delta Overnight post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/destinations/mekong-delta-travel-guide/',
        '/destinations/ho-chi-minh-city-travel-guide/',
        '/destinations/best-day-trips-from-ho-chi-minh-city/',
        '/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/',
        '/plan/vietnam-travel-guide/',
        '/compare/north-central-south-vietnam/',
        '/plan/transport-within-vietnam/',
        '/costs/vietnam-travel-cost/',
        '/plan/best-time-to-visit-vietnam/',
        '/itineraries/10-days-in-vietnam/',
        '/itineraries/14-days-in-vietnam/',
        '/destinations/phu-quoc-travel-guide/',
        '/plan/health-travel-insurance-vietnam/',
        '/plan/safety-scams-vietnam/',
    ] as $internal_path
) {
    if (! str_contains($combined, $internal_path)) {
        $failures[] = "Mekong Delta Overnight post is missing internal route: {$internal_path}";
    }
}

foreach (
    [
        '/destinations/mekong-delta-overnight-vs-day-trip/',
        '/destinations/hoi-an-ancient-town-guide/',
        '/destinations/hue-imperial-city-guide/',
        '/destinations/da-nang-beaches-guide/',
    ] as $unpublished_path
) {
    if (str_contains($combined, $unpublished_path)) {
        $failures[] = "Mekong Delta Overnight post contains unpublished internal route: {$unpublished_path}";
    }
}

foreach (
    [
        'hidden gem',
        'ultimate Mekong list',
        'must see everything',
        'guaranteed floating market',
        'perfect Mekong tour',
        'copy this itinerary',
        'authentic local life guaranteed',
    ] as $unsafe_phrase
) {
    if (str_contains(strtolower($combined), strtolower($unsafe_phrase))) {
        $failures[] = "Mekong Delta Overnight post contains unsafe phrase: {$unsafe_phrase}";
    }
}

if (strlen(wp_strip_all_tags($raw_content)) < 11000) {
    $failures[] = 'Mekong Delta Overnight post content is too thin for a complete draft.';
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
        $failures[] = "Mekong Delta Overnight required meta missing: {$meta_key}";
    }
}

foreach (
    [
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_editorial_batch' => 'batch-2-evergreen-planning',
        'vg_editorial_target_publish_date' => '2026-08-20',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_affiliate_status' => 'none',
    ] as $meta_key => $expected_value
) {
    $actual_value = trim((string) get_post_meta($post_id, $meta_key, true));

    if ($actual_value !== $expected_value) {
        $failures[] = "Mekong Delta Overnight {$meta_key} mismatch: {$actual_value}; expected {$expected_value}";
    }
}

foreach (['Draft status:', 'Editorial brief', 'Sources to check', 'Image plan', 'Publish gate'] as $placeholder) {
    if (str_contains($raw_content, $placeholder)) {
        $failures[] = "Mekong Delta Overnight still contains brief placeholder: {$placeholder}";
    }
}

vg_verify_mekong_overnight_assert_exact_terms($failures, $post_id, 'category', ['destinations', 'transport-logistics']);
vg_verify_mekong_overnight_assert_exact_terms($failures, $post_id, 'post_tag', ['route-planning', 'first-time-vietnam', 'family-travel', 'anti-spam-evergreen']);
vg_verify_mekong_overnight_assert_internal_body_hrefs_published($failures, $rendered_content);

$raw_external_body_href_count = vg_verify_mekong_overnight_external_body_href_count($raw_content);
$rendered_external_body_href_count = vg_verify_mekong_overnight_external_body_href_count($rendered_content);
$external_body_href_count = $raw_external_body_href_count + $rendered_external_body_href_count;
$notes[] = "Mekong Delta Overnight post ID: {$post_id}";
$notes[] = "Mekong Delta Overnight raw_external_body_href_count: {$raw_external_body_href_count}";
$notes[] = "Mekong Delta Overnight rendered_external_body_href_count: {$rendered_external_body_href_count}";
$notes[] = "Mekong Delta Overnight external_body_href_count: {$external_body_href_count}";

if ($raw_external_body_href_count !== 0) {
    $failures[] = "Mekong Delta Overnight raw_external_body_href_count should be 0; found {$raw_external_body_href_count}";
}

if ($rendered_external_body_href_count !== 0) {
    $failures[] = "Mekong Delta Overnight rendered_external_body_href_count should be 0; found {$rendered_external_body_href_count}";
}

if ($external_body_href_count !== 0) {
    $failures[] = "Mekong Delta Overnight external_body_href_count should be 0; found {$external_body_href_count}";
}

vg_verify_mekong_overnight_post_finish($failures, $notes);

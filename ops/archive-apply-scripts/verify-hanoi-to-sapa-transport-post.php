<?php
/**
 * Verify the Hanoi to Sapa Transport complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-hanoi-to-sapa-transport-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_hanoi_sapa_transport_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Hanoi to Sapa Transport post verification failed.');
        }

        echo 'Hanoi to Sapa Transport post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Hanoi to Sapa Transport post verification passed.');
        return;
    }

    echo 'Hanoi to Sapa Transport post verification passed.' . PHP_EOL;
}

function vg_verify_hanoi_sapa_transport_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_hanoi_sapa_transport_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_hanoi_sapa_transport_rendered_content(WP_Post $post): string
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

function vg_verify_hanoi_sapa_transport_body_hrefs(string $content): array
{
    preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])([^"\']+)\1/i', $content, $matches);

    if ($matches[2] === []) {
        return [];
    }

    return array_values(array_unique(array_map('trim', array_map('strval', $matches[2]))));
}

function vg_verify_hanoi_sapa_transport_external_body_href_count(string $content): int
{
    $hrefs = vg_verify_hanoi_sapa_transport_body_hrefs($content);
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

function vg_verify_hanoi_sapa_transport_internal_path_from_href(string $href): ?string
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

function vg_verify_hanoi_sapa_transport_internal_body_hrefs(string $content): array
{
    $internal = [];

    foreach (vg_verify_hanoi_sapa_transport_body_hrefs($content) as $href) {
        $path = vg_verify_hanoi_sapa_transport_internal_path_from_href($href);

        if ($path === null || $path === '/') {
            continue;
        }

        $internal[$path] = $href;
    }

    ksort($internal);

    return $internal;
}

function vg_verify_hanoi_sapa_transport_post_id_for_internal_path(string $path): int
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

function vg_verify_hanoi_sapa_transport_internal_path_http_status(string $path): int
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

function vg_verify_hanoi_sapa_transport_assert_internal_body_hrefs_published(array &$failures, string $content): void
{
    foreach (vg_verify_hanoi_sapa_transport_internal_body_hrefs($content) as $path => $href) {
        $linked_post_id = vg_verify_hanoi_sapa_transport_post_id_for_internal_path($path);

        if ($linked_post_id <= 0) {
            $failures[] = "Hanoi to Sapa Transport internal body link does not resolve: {$href}";
            continue;
        }

        $linked_post = get_post($linked_post_id);

        if (! $linked_post instanceof WP_Post || $linked_post->post_status !== 'publish') {
            $status = $linked_post instanceof WP_Post ? $linked_post->post_status : 'missing';
            $failures[] = "Hanoi to Sapa Transport internal body link is not published: {$href}; status {$status}";
            continue;
        }

        $http_status = vg_verify_hanoi_sapa_transport_internal_path_http_status($path);

        if ($http_status !== 200) {
            $failures[] = "Hanoi to Sapa Transport internal body link does not return HTTP 200: {$href}; status {$http_status}";
        }
    }
}

function vg_verify_hanoi_sapa_transport_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_hanoi_sapa_transport_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_hanoi_sapa_transport_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

$failures = [];
$notes = [];
$post = vg_verify_hanoi_sapa_transport_post_by_slug('hanoi-to-sapa-transport');

if (! $post instanceof WP_Post) {
    vg_verify_hanoi_sapa_transport_finish(['Required post not found: hanoi-to-sapa-transport']);
}

$post_id = (int) $post->ID;

if ($post_id !== 520) {
    $failures[] = "Hanoi to Sapa Transport post ID mismatch: {$post_id}; expected 520";
}

if ($post->post_title !== 'Hanoi to Sapa Transport: Train, Cabin Bus or Private Car?') {
    $failures[] = "Hanoi to Sapa Transport post_title mismatch: {$post->post_title}";
}

if ($post->post_name !== 'hanoi-to-sapa-transport') {
    $failures[] = "Hanoi to Sapa Transport post_name mismatch: {$post->post_name}";
}

if ($post->post_status !== 'draft') {
    $failures[] = "Hanoi to Sapa Transport post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Hanoi to Sapa Transport comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Hanoi to Sapa Transport ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_hanoi_sapa_transport_rendered_content($post);
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
        'hero marker' => 'vg-hanoi-sapa-transport-hero:v1',
        'verdict marker' => 'vg-hanoi-sapa-transport-concierge-verdict:v1',
        'internal demand marker' => 'vg-hanoi-sapa-transport-internal-demand:v1',
        'photo proof marker' => 'vg-hanoi-sapa-transport-photo-proof:v1',
        'source diversity marker' => 'vg-hanoi-sapa-transport-source-diversity:v1',
        'mode chooser marker' => 'vg-hanoi-sapa-transport-mode-chooser:v1',
        'sleep arrival marker' => 'vg-hanoi-sapa-transport-sleep-arrival:v1',
        'train Lao Cai transfer marker' => 'vg-hanoi-sapa-transport-train-lao-cai-transfer:v1',
        'cabin sleeper bus marker' => 'vg-hanoi-sapa-transport-cabin-sleeper-bus:v1',
        'limousine private car marker' => 'vg-hanoi-sapa-transport-limousine-private-car:v1',
        'luggage children motion marker' => 'vg-hanoi-sapa-transport-luggage-children-motion:v1',
        'valley dropoff marker' => 'vg-hanoi-sapa-transport-valley-dropoff:v1',
        'route pressure recovery marker' => 'vg-hanoi-sapa-transport-route-pressure-recovery:v1',
        'budget comfort marker' => 'vg-hanoi-sapa-transport-budget-comfort:v1',
        'live checks marker' => 'vg-hanoi-sapa-transport-live-checks:v1',
        'FAQ marker' => 'vg-hanoi-sapa-transport-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'Sapa source URL' => 'https://vietnam.travel/places-to-go/northern-vietnam/sapa',
        'transport source URL' => 'https://vietnam.travel/plan-your-trip/transport-within-vietnam',
        'weather source URL' => 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam',
        'Vietnam Railways URL' => 'https://vr.com.vn/en',
        'Vietnam Railways ticketing URL' => 'https://dsvn.vn',
        'NCHMF URL' => 'https://nchmf.gov.vn/Kttv/en-US/1/index.html',
        'UK FCDO URL' => 'https://www.gov.uk/foreign-travel-advice/vietnam/safety-and-security',
        'Smartraveller URL' => 'https://www.smartraveller.gov.au/destinations/asia/vietnam',
        'Hanoi Railway Station phrase' => 'Hanoi Railway Station',
        'Lao Cai phrase' => 'Lao Cai',
        'cabin bus phrase' => 'cabin bus',
        'sleeper bus phrase' => 'sleeper bus',
        'limousine van phrase' => 'limousine van',
        'private car phrase' => 'private car',
        'motion sickness phrase' => 'motion sickness',
        'luggage phrase' => 'luggage',
        'children phrase' => 'children',
        'valley lodge phrase' => 'valley lodge',
        'final drop-off phrase' => 'final drop-off',
        'early arrival phrase' => 'early arrival',
        'recovery time phrase' => 'recovery time',
        'live train phrase' => 'live train',
    ] as $label => $needle
) {
    if (! str_contains($combined, $needle)) {
        $failures[] = "Hanoi to Sapa Transport post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        'rendered proof panel' => 'vg-proof-panel',
        'rendered source trail' => 'vg-source-trail',
        'rendered update log' => 'vg-update-log',
    ] as $label => $needle
) {
    if (! str_contains($rendered_content, $needle)) {
        $failures[] = "Hanoi to Sapa Transport rendered output is missing {$label}: {$needle}";
    }
}

if (! str_contains($rendered_content, 'vg-related-routes-title-' . $post_id)) {
    $failures[] = 'Hanoi to Sapa Transport rendered output is missing related routes shortcode title id.';
}

foreach (
    [
        '/destinations/hanoi-travel-guide/',
        '/plan/best-time-to-visit-vietnam/',
        '/plan/transport-within-vietnam/',
        '/plan/health-travel-insurance-vietnam/',
        '/plan/safety-scams-vietnam/',
        '/compare/north-central-south-vietnam/',
        '/plan/vietnam-travel-guide/',
        '/costs/vietnam-travel-cost/',
        '/itineraries/10-days-in-vietnam/',
        '/itineraries/14-days-in-vietnam/',
        '/itineraries/21-days-in-vietnam/',
        '/destinations/ninh-binh-travel-guide/',
        '/destinations/ha-long-bay-travel-guide/',
        '/plan/sim-esim-vietnam/',
    ] as $internal_path
) {
    if (! str_contains($combined, $internal_path)) {
        $failures[] = "Hanoi to Sapa Transport post is missing internal route: {$internal_path}";
    }
}

foreach (
    [
        '/destinations/sapa-travel-guide/',
        '/destinations/sapa-vs-ha-giang/',
        '/destinations/ha-giang-loop-planning-guide/',
        '/plan/hanoi-to-sapa-transport/',
        '/plan/hanoi-to-ha-giang-transport/',
        '/destinations/ha-giang-safety-guide/',
        '/destinations/ha-giang-easy-rider-vs-self-drive/',
        '/destinations/sapa-trekking-guided-vs-self-guided/',
        '/destinations/where-to-stay-in-sapa/',
        '/destinations/best-time-for-northern-vietnam/',
        '/destinations/vietnam-rice-terraces-guide/',
        '/destinations/mu-cang-chai-travel-guide/',
        '/destinations/pu-luong-travel-guide/',
    ] as $unpublished_path
) {
    if (str_contains($combined, $unpublished_path)) {
        $failures[] = "Hanoi to Sapa Transport post contains unpublished internal route: {$unpublished_path}";
    }
}

foreach (
    [
        'hidden gem',
        'must-visit',
        'best bus company',
        'cheapest guaranteed',
        'guaranteed schedule',
        'always on time',
        'safe for everyone',
        'easy for everyone',
        'no weather risk',
        'copy this itinerary',
        'book this operator',
    ] as $unsafe_phrase
) {
    if (str_contains(strtolower($combined), strtolower($unsafe_phrase))) {
        $failures[] = "Hanoi to Sapa Transport post contains unsafe phrase: {$unsafe_phrase}";
    }
}

if (strlen(wp_strip_all_tags($raw_content)) < 12000) {
    $failures[] = 'Hanoi to Sapa Transport post content is too thin for a complete draft.';
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
        $failures[] = "Hanoi to Sapa Transport required meta missing: {$meta_key}";
    }
}

foreach (
    [
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_editorial_batch' => 'batch-3-northern-mountains',
        'vg_editorial_target_publish_date' => '2026-08-25',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_affiliate_status' => 'none',
    ] as $meta_key => $expected_value
) {
    $actual_value = trim((string) get_post_meta($post_id, $meta_key, true));

    if ($actual_value !== $expected_value) {
        $failures[] = "Hanoi to Sapa Transport {$meta_key} mismatch: {$actual_value}; expected {$expected_value}";
    }
}

foreach (['Draft status:', 'Editorial brief', 'Sources to check', 'Image plan', 'Publish gate'] as $placeholder) {
    if (str_contains($raw_content, $placeholder)) {
        $failures[] = "Hanoi to Sapa Transport still contains brief placeholder: {$placeholder}";
    }
}

vg_verify_hanoi_sapa_transport_assert_exact_terms($failures, $post_id, 'category', ['transport-logistics', 'travel-planning']);
vg_verify_hanoi_sapa_transport_assert_exact_terms($failures, $post_id, 'post_tag', ['transport-planning', 'mountain-planning', 'first-time-vietnam', 'anti-spam-evergreen']);
vg_verify_hanoi_sapa_transport_assert_internal_body_hrefs_published($failures, $rendered_content);

$raw_external_body_href_count = vg_verify_hanoi_sapa_transport_external_body_href_count($raw_content);
$rendered_external_body_href_count = vg_verify_hanoi_sapa_transport_external_body_href_count($rendered_content);
$external_body_href_count = $raw_external_body_href_count + $rendered_external_body_href_count;
$notes[] = "Hanoi to Sapa Transport post ID: {$post_id}";
$notes[] = "Hanoi to Sapa Transport raw_external_body_href_count: {$raw_external_body_href_count}";
$notes[] = "Hanoi to Sapa Transport rendered_external_body_href_count: {$rendered_external_body_href_count}";
$notes[] = "Hanoi to Sapa Transport external_body_href_count: {$external_body_href_count}";

if ($raw_external_body_href_count !== 0) {
    $failures[] = "Hanoi to Sapa Transport raw_external_body_href_count should be 0; found {$raw_external_body_href_count}";
}

if ($rendered_external_body_href_count !== 0) {
    $failures[] = "Hanoi to Sapa Transport rendered_external_body_href_count should be 0; found {$rendered_external_body_href_count}";
}

if ($external_body_href_count !== 0) {
    $failures[] = "Hanoi to Sapa Transport external_body_href_count should be 0; found {$external_body_href_count}";
}

vg_verify_hanoi_sapa_transport_finish($failures, $notes);

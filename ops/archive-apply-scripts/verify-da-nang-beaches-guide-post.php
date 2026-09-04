<?php
/**
 * Verify the Da Nang Beaches complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-da-nang-beaches-guide-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_da_nang_beaches_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Da Nang Beaches post verification failed.');
        }

        echo 'Da Nang Beaches post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Da Nang Beaches post verification passed.');
        return;
    }

    echo 'Da Nang Beaches post verification passed.' . PHP_EOL;
}

function vg_verify_da_nang_beaches_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_da_nang_beaches_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_da_nang_beaches_rendered_content(WP_Post $post): string
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

function vg_verify_da_nang_beaches_external_body_href_count(string $content): int
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

function vg_verify_da_nang_beaches_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_da_nang_beaches_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_da_nang_beaches_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

$failures = [];
$notes = [];
$post = vg_verify_da_nang_beaches_post_by_slug('da-nang-beaches-guide');

if (! $post instanceof WP_Post) {
    vg_verify_da_nang_beaches_post_finish(['Required post not found: da-nang-beaches-guide']);
}

$post_id = (int) $post->ID;

if ($post_id !== 501) {
    $failures[] = "Da Nang Beaches post ID mismatch: {$post_id}; expected 501";
}

if ($post->post_status !== 'draft') {
    $failures[] = "Da Nang Beaches post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Da Nang Beaches comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Da Nang Beaches ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_da_nang_beaches_rendered_content($post);
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
        'hero marker' => 'vg-da-nang-beaches-hero:v1',
        'verdict marker' => 'vg-da-nang-beaches-concierge-verdict:v1',
        'GSC demand marker' => 'vg-da-nang-beaches-gsc-demand:v1',
        'quick chooser marker' => 'vg-da-nang-beaches-quick-chooser:v1',
        'photo proof marker' => 'vg-da-nang-beaches-photo-proof:v1',
        'source diversity marker' => 'vg-da-nang-beaches-source-diversity:v1',
        'north south orientation marker' => 'vg-da-nang-beaches-north-south-orientation:v1',
        'base comparison marker' => 'vg-da-nang-beaches-my-khe-non-nuoc-hoi-an:v1',
        'base map marker' => 'vg-da-nang-beaches-base-map:v1',
        'season sea marker' => 'vg-da-nang-beaches-season-sea:v1',
        'family swimmer marker' => 'vg-da-nang-beaches-family-swimmer:v1',
        'hotel decision marker' => 'vg-da-nang-beaches-hotel-decision:v1',
        'route order marker' => 'vg-da-nang-beaches-route-order:v1',
        'airport transport marker' => 'vg-da-nang-beaches-airport-transport:v1',
        'time budget marker' => 'vg-da-nang-beaches-time-budget:v1',
        'skip logic marker' => 'vg-da-nang-beaches-skip-logic:v1',
        'live checks marker' => 'vg-da-nang-beaches-live-checks:v1',
        'FAQ marker' => 'vg-da-nang-beaches-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'Da Nang source URL' => 'https://vietnam.travel/places-to-go/central-vietnam/da-nang',
        'Da Nang must-visit source URL' => 'https://vietnam.travel/things-to-do/must-visit-places-in-da-nang',
        'Hoi An source URL' => 'https://vietnam.travel/places-to-go/central-vietnam/hoi-an',
        'Hoi An explore source URL' => 'https://vietnam.travel/things-to-do/the-best-ways-to-explore-the-ancient-town-of-hoi-an',
        'Hue source URL' => 'https://vietnam.travel/places-to-go/central-vietnam/hue',
        'Central Vietnam source URL' => 'https://vietnam.travel/places-to-go/central-vietnam',
        'family beach source URL' => 'https://vietnam.travel/things-to-do/beaches-perfect-for-families',
        'weather source URL' => 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam',
        'transport source URL' => 'https://vietnam.travel/plan-your-trip/transport-within-vietnam',
        'Da Nang Fantasticity URL' => 'https://danangfantasticity.com/en/',
        'Da Nang about source URL' => 'https://danangfantasticity.com/en/about-da-nang-city',
        'Da Nang weather source URL' => 'https://danangfantasticity.com/en/weather-da-nang',
        'Da Nang airport guide source URL' => 'https://danangfantasticity.com/en/travel-information/guide-for-visitors-arriving-at-and-departing-from-da-nang-international-airport',
        'Da Nang beaches source URL' => 'https://danangfantasticity.com/en/danang-beaches/da-nang-beaches-2',
        'NCHMF Da Nang source URL' => 'https://nchmf.gov.vn/kttvsiteE/en-US/2/hai-chau-tp-da-nang-w55.html',
        'Da Nang airport source URL' => 'https://danangairport.vn/',
        'GSC source phrase' => 'Google Search Console query export',
        'My Khe phrase' => 'My Khe',
        'Non Nuoc phrase' => 'Non Nuoc',
        'Man Thai phrase' => 'Man Thai',
        'Pham Van Dong phrase' => 'Pham Van Dong',
        'Hoi An coast phrase' => 'Hoi An coast',
        'Cua Dai phrase' => 'Cua Dai',
        'sea condition phrase' => 'sea conditions',
        'airport phrase' => 'Da Nang International Airport',
        'route friction phrase' => 'route friction',
        'skip logic phrase' => 'Skip logic',
    ] as $label => $needle
) {
    if (! str_contains($combined, $needle)) {
        $failures[] = "Da Nang Beaches post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/destinations/da-nang-travel-guide/',
        '/compare/da-nang-vs-hoi-an/',
        '/destinations/best-beaches-in-vietnam/',
        '/destinations/best-things-to-do-in-hoi-an/',
        '/plan/vietnam-travel-guide/',
        '/compare/north-central-south-vietnam/',
        '/plan/best-time-to-visit-vietnam/',
        '/plan/transport-within-vietnam/',
        '/costs/vietnam-travel-cost/',
        '/itineraries/10-days-in-vietnam/',
        '/itineraries/14-days-in-vietnam/',
        '/plan/health-travel-insurance-vietnam/',
        '/plan/safety-scams-vietnam/',
    ] as $internal_path
) {
    if (! str_contains($combined, $internal_path)) {
        $failures[] = "Da Nang Beaches post is missing internal route: {$internal_path}";
    }
}

foreach (
    [
        '/destinations/da-nang-beaches-guide/',
        '/destinations/hoi-an-ancient-town-guide/',
        '/destinations/hue-imperial-city-guide/',
        '/destinations/da-nang-beaches-guide',
    ] as $unpublished_path
) {
    if (str_contains($combined, $unpublished_path)) {
        $failures[] = "Da Nang Beaches post contains unpublished internal route: {$unpublished_path}";
    }
}

foreach (
    [
        'hidden gem',
        'ultimate Da Nang beach list',
        'must see everything',
        'perfect beach holiday',
        'guaranteed safe swimming',
        'best beach in the world',
        'copy this itinerary',
    ] as $unsafe_phrase
) {
    if (str_contains(strtolower($combined), strtolower($unsafe_phrase))) {
        $failures[] = "Da Nang Beaches post contains unsafe phrase: {$unsafe_phrase}";
    }
}

if (strlen(wp_strip_all_tags($raw_content)) < 11000) {
    $failures[] = 'Da Nang Beaches post content is too thin for a complete draft.';
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
        $failures[] = "Da Nang Beaches required meta missing: {$meta_key}";
    }
}

foreach (
    [
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_editorial_batch' => 'batch-2-evergreen-planning',
        'vg_editorial_target_publish_date' => '2026-08-19',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_affiliate_status' => 'none',
    ] as $meta_key => $expected_value
) {
    $actual_value = trim((string) get_post_meta($post_id, $meta_key, true));

    if ($actual_value !== $expected_value) {
        $failures[] = "Da Nang Beaches {$meta_key} mismatch: {$actual_value}; expected {$expected_value}";
    }
}

foreach (['Draft status:', 'Editorial brief', 'Sources to check', 'Image plan', 'Publish gate'] as $placeholder) {
    if (str_contains($raw_content, $placeholder)) {
        $failures[] = "Da Nang Beaches still contains brief placeholder: {$placeholder}";
    }
}

vg_verify_da_nang_beaches_assert_exact_terms($failures, $post_id, 'category', ['beaches-islands', 'destinations']);
vg_verify_da_nang_beaches_assert_exact_terms($failures, $post_id, 'post_tag', ['beach-planning', 'first-time-vietnam', 'route-planning', 'anti-spam-evergreen']);

$external_body_href_count = vg_verify_da_nang_beaches_external_body_href_count($raw_content);
$notes[] = "Da Nang Beaches post ID: {$post_id}";
$notes[] = "Da Nang Beaches external_body_href_count: {$external_body_href_count}";

if ($external_body_href_count !== 0) {
    $failures[] = "Da Nang Beaches external_body_href_count should be 0; found {$external_body_href_count}";
}

vg_verify_da_nang_beaches_post_finish($failures, $notes);

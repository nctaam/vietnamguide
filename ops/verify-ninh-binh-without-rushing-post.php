<?php
/**
 * Verify the Ninh Binh Without Rushing complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-ninh-binh-without-rushing-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_ninh_binh_rushing_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Ninh Binh without rushing post verification failed.');
        }

        echo 'Ninh Binh without rushing post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Ninh Binh without rushing post verification passed.');
        return;
    }

    echo 'Ninh Binh without rushing post verification passed.' . PHP_EOL;
}

function vg_verify_ninh_binh_rushing_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_ninh_binh_rushing_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_ninh_binh_rushing_assert_target_meta(WP_Post $post, array &$failures): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-06',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if ($actual_value !== $expected_value) {
            $failures[] = "Ninh Binh without rushing post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
        }
    }
}

function vg_verify_ninh_binh_rushing_rendered_content(WP_Post $post): string
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

function vg_verify_ninh_binh_rushing_external_body_href_count(string $content): int
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

function vg_verify_ninh_binh_rushing_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_ninh_binh_rushing_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_ninh_binh_rushing_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

$failures = [];
$notes = [];
$post = vg_verify_ninh_binh_rushing_post_by_slug('ninh-binh-without-rushing');

if (! $post instanceof WP_Post) {
    vg_verify_ninh_binh_rushing_post_finish(['Required post not found: ninh-binh-without-rushing']);
}

$post_id = (int) $post->ID;
$review_date = 'July 25, 2026';
$hero_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg';
$tam_coc_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Tam_Coc_Ninh_Binh_%2829079%29.jpg/1920px-Tam_Coc_Ninh_Binh_%2829079%29.jpg';
$mua_cave_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f4/Mua_Cave%2C_Ninh_Binh%2C_Vietnam%2C_20240202_0926_4964.jpg/1920px-Mua_Cave%2C_Ninh_Binh%2C_Vietnam%2C_20240202_0926_4964.jpg';
$van_long_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/41/Van_Long_Nature_Reserve_Riet_Grotten_Kalksteen_Ninh_Binh_%2894589%29.jpg/1920px-Van_Long_Nature_Reserve_Riet_Grotten_Kalksteen_Ninh_Binh_%2894589%29.jpg';
$cuc_phuong_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/98/Forest_in_Cuc_Phuong_National_Park_%2815706323528%29.jpg/1920px-Forest_in_Cuc_Phuong_National_Park_%2815706323528%29.jpg';

if ($post->post_status !== 'draft') {
    $failures[] = "Ninh Binh without rushing post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Ninh Binh without rushing comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Ninh Binh without rushing ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_ninh_binh_rushing_rendered_content($post);

foreach (
    [
        'hero marker' => 'vg-ninh-binh-without-rushing-hero:v1',
        'verdict marker' => 'vg-ninh-binh-without-rushing-verdict:v1',
        'at a glance marker' => 'vg-ninh-binh-without-rushing-at-a-glance:v1',
        'photo grid marker' => 'vg-ninh-binh-without-rushing-photo-grid:v1',
        'source diversity marker' => 'vg-ninh-binh-without-rushing-source-diversity:v1',
        'rush diagnosis marker' => 'vg-ninh-binh-without-rushing-rush-diagnosis:v1',
        'boat choice marker' => 'vg-ninh-binh-without-rushing-boat-choice:v1',
        'base choice marker' => 'vg-ninh-binh-without-rushing-base-choice:v1',
        'transfer pressure marker' => 'vg-ninh-binh-without-rushing-transfer-pressure:v1',
        'one night plan marker' => 'vg-ninh-binh-without-rushing-one-night-plan:v1',
        'two night plan marker' => 'vg-ninh-binh-without-rushing-two-night-plan:v1',
        'cut list marker' => 'vg-ninh-binh-without-rushing-cut-list:v1',
        'live checks marker' => 'vg-ninh-binh-without-rushing-live-checks:v1',
        'FAQ marker' => 'vg-ninh-binh-without-rushing-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'destination source note' => 'Vietnam.travel - Ninh Binh destination page',
        'boat tour source note' => 'Vietnam.travel - A guide to the boat tours of Ninh Binh',
        'UNESCO source note' => 'UNESCO World Heritage Centre - Trang An Landscape Complex',
        'tourism office source note' => 'Ninh Binh Tourism Department',
        'rail source note' => 'Vietnam Railways',
        'core decision phrase' => 'one long day, one protected night, or two slower nights',
    ] as $label => $needle
) {
    $haystack = str_contains($label, 'panel') || str_contains($label, 'trail') || str_contains($label, 'log') || str_contains($label, 'routes') || str_contains($label, 'source note')
        ? $rendered_content
        : $raw_content . "\n" . $rendered_content;

    if (! str_contains($haystack, $needle)) {
        $failures[] = "Ninh Binh without rushing post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/destinations/ninh-binh-travel-guide/',
        '/compare/ninh-binh-day-trip-vs-overnight/',
        '/compare/trang-an-vs-tam-coc/',
        '/plan/ninh-binh-to-ha-long-bay-transfer/',
        '/plan/hanoi-to-ninh-binh-transport/',
        '/destinations/where-to-stay-in-ninh-binh/',
        '/destinations/tam-coc-travel-guide/',
        '/destinations/best-day-trips-from-hanoi/',
        '/itineraries/10-days-in-vietnam/',
        '/itineraries/14-days-in-vietnam/',
    ] as $internal_path
) {
    if (! str_contains($raw_content . "\n" . $rendered_content, $internal_path)) {
        $failures[] = "Ninh Binh without rushing post is missing internal route: {$internal_path}";
    }
}

if (str_contains($raw_content, 'Draft status:') || str_contains($rendered_content, 'Draft status:')) {
    $failures[] = 'Ninh Binh without rushing post still contains brief placeholder text: Draft status:';
}

if (! str_contains($rendered_content, 'vg-related-routes-title-' . $post_id)) {
    $failures[] = 'Ninh Binh without rushing post is missing rendered related routes shortcode output.';
}

if (strlen(wp_strip_all_tags($raw_content)) < 9500) {
    $failures[] = 'Ninh Binh without rushing post content is too thin for complete draft status.';
}

foreach (
    [
        'post_title' => 'Ninh Binh Without Rushing: How to Choose Boat, Base and Transfer',
        'post_name' => 'ninh-binh-without-rushing',
    ] as $field => $expected_value
) {
    $actual_value = (string) $post->{$field};

    if ($actual_value !== $expected_value) {
        $failures[] = "Ninh Binh without rushing post has unexpected {$field}: {$actual_value}; expected {$expected_value}";
    }
}

vg_verify_ninh_binh_rushing_assert_target_meta($post, $failures);

foreach (
    [
        'rank_math_title' => 'Ninh Binh Without Rushing: Boat, Base, Transfer',
        'rank_math_description' => 'Plan Ninh Binh without rushing. Choose one long day, one protected night, or two slower nights by boat route, base, transfer pressure, weather, and bay timing.',
        'rank_math_focus_keyword' => 'Ninh Binh without rushing',
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_reviewed_guide' => '1',
        'vg_eeat_written_by' => 'VietnamGuide editorial team',
        'vg_eeat_reviewed_by' => 'VietnamGuide editorial review',
        'vg_eeat_last_meaningful_update' => $review_date,
        'vg_eeat_primary_decision' => 'Choose Ninh Binh by route pressure first: one focused day, one protected night, two slower nights, or a clean skip. Lock one boat route, the right base, and the onward transfer before adding viewpoints or nature side trips.',
        'vg_eeat_update_summary' => 'Expanded the native WordPress post brief into a complete decision-led draft with a photo-led hero, proof panel, concierge verdict, at-a-glance pacing matrix, photo proof, source-diversity table, rush diagnosis, boat-choice framework, base-choice framework, transfer-pressure guidance, one-night and two-night plans, cut list, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.',
        'vg_eeat_sources_checked' => implode("\n", [
            "Vietnam.travel - Ninh Binh destination page - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh - checked {$review_date}; used for high-level Ninh Binh destination framing, Hang Mua, boat routes, countryside, and route inspiration without replacing itinerary judgment.",
            "Vietnam.travel - A guide to the boat tours of Ninh Binh - https://vietnam.travel/things-to-do/guide-boat-tours-ninh-binh - checked {$review_date}; used for boat-tour prominence and the argument that the boat choice should lead the day.",
            "Vietnam.travel - A perfect day in Ninh Binh - https://vietnam.travel/things-to-do/perfect-day-ninh-binh - checked {$review_date}; used as a source contrast for one-day possibility while this draft adds anti-rush filters.",
            "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for weather-check discipline before boat, cycling, viewpoint, and transfer commitments.",
            "UNESCO World Heritage Centre - Trang An Landscape Complex - https://whc.unesco.org/en/list/1438/ - checked {$review_date}; used for Trang An mixed cultural/natural heritage context without making heritage a badge chase.",
            "Ninh Binh Tourism Department - https://dulichninhbinh.com.vn/en/ - checked {$review_date}; used for local tourism-office reference and near-travel notice discipline.",
            "Ninh Binh Department of Tourism - https://sodulich.ninhbinh.gov.vn/en - checked {$review_date}; used as an additional local source trail entry for tourism updates and local notices.",
            "Vietnam Railways - official online ticket portal - https://dsvn.vn/ - checked {$review_date}; used for train-check discipline before choosing Hanoi to Ninh Binh or onward logistics.",
            "Vietnam Railways - VNR English site - https://vr.com.vn/en - checked {$review_date}; used as a secondary rail source trail entry for official railway identity and support context.",
            "Wikimedia Commons image direct URL - Trang An Landscape Complex, Ninh Binh Province - {$hero_image} - credit Jakub Halun / CC BY 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Tam Coc Ninh Binh - {$tam_coc_image} - credit Andre Hospers / CC BY 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Mua Cave, Ninh Binh - {$mua_cave_image} - credit Jakub Halun / CC BY 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Van Long Nature Reserve - {$van_long_image} - credit Andre Hospers / CC BY 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Cuc Phuong National Park forest - {$cuc_phuong_image} - credit hds / CC BY 2.0 - license context checked {$review_date}.",
        ]),
        'vg_eeat_field_note' => 'This post is a Ninh Binh pacing layer for the native WordPress editorial calendar. It avoids duplicating the Ninh Binh pillar, Trang An vs Tam Coc comparison, stay-area guide, Hanoi to Ninh Binh transport guide, and Ninh Binh to bay transfer guide by focusing on the combined boat + base + transfer decision.',
        'vg_eeat_affiliate_status' => 'none',
        'vg_eeat_evidence_moat' => implode("\n", [
            'Decision-led Ninh Binh pacing article built around one long day, one protected night, two slower nights, or clean skip rather than a rewritten attraction list.',
            'Concierge verdict names the best default and the first cuts when the itinerary is too full.',
            'Photo-led proof makes each image do planning work: boat anchor, base feeling, viewpoint risk, wetland depth, and forest time.',
            'Source-diversity section separates what official sources can support from what still needs traveler-specific editorial judgment.',
            'Rush diagnosis turns common symptoms into corrections and specialist internal routes.',
            'Boat-choice framework prevents stacking Trang An and Tam Coc without a reason.',
            'Base-choice framework chooses Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side by route job rather than hotel photos.',
            'Transfer-pressure section connects Hanoi, Ninh Binh, Ha Long Bay, Lan Ha Bay, cruise ports, luggage, pickup, and weather into one chain.',
            'One-night and two-night plans explain when slower pacing creates value and when it becomes itinerary inflation.',
            'Cut list gives practical editing order so the guide has long-term value beyond generic tips.',
            'No visible external body anchors; official source trail and image-license records stay auditable through metadata and shortcode output.',
        ]),
        'vg_eeat_related_routes' => implode("\n", [
            'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Use for the full destination frame after the pacing decision is clear.',
            'Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should be one long day, one protected night, two slower nights, or a clean skip.',
            'Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the boat route by heritage logic, countryside rhythm, crowds, base fit, photography, and family comfort.',
            'Ninh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.',
            'Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, timing, and next-route fragility.',
            'Where to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side by route job and sleep/pickup logic.',
            'Tam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use when Tam Coc is the soft base for boat timing, cycling, Bich Dong, Mua Cave, dinner, and countryside rhythm.',
            'Best Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Compare whether Ninh Binh should remain a Hanoi excursion or become a protected countryside chapter.',
            '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a one-night Ninh Binh stop improves or weakens a tight first route.',
            '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when Ninh Binh can slow down without stealing from Central or South Vietnam.',
            'Ha Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Check bay route pressure before combining inland limestone and cruise scenery.',
            'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Use before committing Ninh Binh to a cruise handoff.',
            'Transport Within Vietnam | /plan/transport-within-vietnam/ | Put Ninh Binh road and rail choices inside the wider Vietnam movement plan.',
            'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check heat, rain, storm, cold, and northern season pressure before locking outdoor-heavy days.',
            'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the real difference between a day tour, one protected night, private car, rail, and transfer buffers.',
        ]),
        'vg_eeat_hero_image_credit' => 'Hero and Trang An image: Trang An Landscape Complex, Ninh Binh Province by Jakub Halun, CC BY 4.0. Body images: Tam Coc by Andre Hospers, CC BY 4.0; Mua Cave by Jakub Halun, CC BY 4.0; Van Long Nature Reserve by Andre Hospers, CC BY 4.0; Cuc Phuong National Park forest by hds, CC BY 2.0.',
        'vg_last_manual_review' => $review_date,
        'vg_admin_first_notes' => 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, confirms image presentation, checks Rank Math/social preview, verifies Ninh Binh, UNESCO, local tourism, rail, weather, and image-license sources, and decides whether the draft needs first-hand editorial notes before publishing.',
    ] as $meta_key => $expected_value
) {
    $actual_value = (string) get_post_meta($post_id, $meta_key, true);

    if ($actual_value !== $expected_value) {
        $failures[] = "Ninh Binh without rushing post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
    }
}

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
        $failures[] = "Ninh Binh without rushing post is missing required meta: {$meta_key}";
    }
}

vg_verify_ninh_binh_rushing_assert_exact_terms($failures, $post_id, 'category', ['destinations', 'transport-logistics']);
vg_verify_ninh_binh_rushing_assert_exact_terms($failures, $post_id, 'post_tag', ['route-planning', 'family-travel', 'anti-spam-evergreen']);

$external_body_href_count = vg_verify_ninh_binh_rushing_external_body_href_count($raw_content);

if ($external_body_href_count !== 0) {
    $failures[] = "Ninh Binh without rushing post external_body_href_count should be 0; found {$external_body_href_count}.";
}

$notes[] = 'Ninh Binh without rushing post ID: ' . $post_id;
$notes[] = 'Ninh Binh without rushing external_body_href_count: ' . $external_body_href_count;

vg_verify_ninh_binh_rushing_post_finish($failures, $notes);

<?php
/**
 * Verify the What to Pack for Vietnam by Region and Season complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-what-to-pack-vietnam-region-season-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_packing_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Vietnam packing post verification failed.');
        }

        echo 'Vietnam packing post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Vietnam packing post verification passed.');
        return;
    }

    echo 'Vietnam packing post verification passed.' . PHP_EOL;
}

function vg_verify_packing_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_packing_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_packing_rendered_content(WP_Post $post): string
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

function vg_verify_packing_external_body_href_count(string $content): int
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

function vg_verify_packing_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_packing_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_packing_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

function vg_verify_packing_expected_meta(): array
{
    $review_date = 'July 25, 2026';
    $hero_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg';
    $hoi_an_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg';
    $hai_van_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1920px-Vietnam%2C_Hai-Van-Pass.jpg';
    $hcmc_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg';
    $mekong_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1920px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg';
    $phu_quoc_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg';

    return [
        'rank_math_title' => 'What to Pack for Vietnam by Region and Season',
        'rank_math_description' => 'Pack for Vietnam by region and season with north, central, south, mountain, coast, rain, and carry-on logic for first-time travelers.',
        'rank_math_focus_keyword' => 'what to pack for Vietnam',
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_editorial_target_publish_date' => '2026-08-03',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_reviewed_guide' => '1',
        'vg_eeat_written_by' => 'VietnamGuide editorial team',
        'vg_eeat_reviewed_by' => 'VietnamGuide editorial review',
        'vg_eeat_last_meaningful_update' => $review_date,
        'vg_eeat_primary_decision' => 'Pack by region, season, and activity, then choose carry-on or checked bag only when the route and laundry access justify it.',
        'vg_eeat_update_summary' => 'Expanded the native WordPress post brief into a complete packing draft with a photo-led hero, proof panel, concierge verdict, at-a-glance packing matrix, photo proof grid, source-diversity table, region-season matrix, activity-specific packing logic, carry-on versus checked bag tradeoff, temple and modesty notes, overpack traps, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.',
        'vg_eeat_sources_checked' => implode("\n", [
            "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for broad regional packing context and season awareness.",
            "Vietnam.travel - Plan your trip - https://vietnam.travel/plan-your-trip - checked {$review_date}; used for route-order context before packing turns into overpacking.",
            "Vietnam.travel - Health and safety - https://vietnam.travel/plan-your-trip/health-safety - checked {$review_date}; used for sun, rain, comfort, and traveler health discipline.",
            "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked {$review_date}; used for north/cool-weather and highland layer framing.",
            "Vietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked {$review_date}; used for central-coast rain, heat, and heritage-day context.",
            "Vietnam.travel - Southern Vietnam destinations - https://vietnam.travel/places-to-go/southern-vietnam - checked {$review_date}; used for heat, humidity, island, and south-route clothing logic.",
            "National Centre for Hydro-Meteorological Forecasting - English forecast and warning pages - https://nchmf.gov.vn/KttvsiteE/en-US/2/index.html - checked {$review_date}; used for live weather and warning discipline close to travel.",
            "World Weather Information Service - Viet Nam official city forecasts - https://worldweather.wmo.int/en/country.html?countryCode=82 - checked {$review_date}; used for official city-level weather context.",
            "CDC Travelers' Health - Vietnam - https://wwwnc.cdc.gov/travel/destinations/traveler/none/vietnam - checked {$review_date}; used for traveler-health framing and personal medical judgment.",
            "GOV.UK - Vietnam health - https://www.gov.uk/foreign-travel-advice/vietnam/health - checked {$review_date}; used as an additional government travel-health source family.",
            "Wikimedia Commons image direct URL - Hanoi Hoan Kiem Lake - {$hero_image} - credit Alex 69200 vx / CC BY-SA 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Hoi An Ancient Town - {$hoi_an_image} - credit Jakub Halun / CC BY-SA 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Vietnam, Hai-Van-Pass - {$hai_van_image} - credit Wolkenkratzer / CC BY-SA 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Ho Chi Minh City Hall - {$hcmc_image} - credit Steffen Schmitz / CC BY-SA 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Vietnam, Phong Dien, Mekong Delta, River - {$mekong_image} - credit Vyacheslav Argenberg / CC BY 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Kem Beach aerial view Phu Quoc Island Vietnam - {$phu_quoc_image} - credit Vivu Vietnam / CC BY-SA 4.0 - license context checked {$review_date}.",
        ]),
        'vg_eeat_field_note' => 'This post is a region-and-season packing layer for the native WordPress editorial calendar. It deliberately avoids a generic tropical checklist and instead answers what to pack when the route is north, central, south, mountainous, coastal, rainy-season, or transfer-heavy.',
        'vg_eeat_affiliate_status' => 'none',
        'vg_eeat_evidence_moat' => implode("\n", [
            'Decision-led packing guide built around region, season, and activity instead of a universal tropical list.',
            'At-a-glance packing matrix turns north, central, south, mountain, and coast into concrete fabric and layer choices.',
            'Photo proof makes each image do route work: cool north, humid south, central rain, pass wind, Mekong water, and island sun.',
            'Source-diversity table separates broad climate guidance from live weather and traveler-health decisions.',
            'Activity-specific logic covers city walking, temples, boats, cruises, islands, mountain days, rainy transfer days, and family travel.',
            'Carry-on versus checked-bag table keeps route length, laundry, and transfer friction visible.',
            'Temple and modesty notes keep the guide useful for heritage days rather than just beaches and city walks.',
            'Overpack traps explain what slows the route down and what to skip.',
            'Live checks remind travelers to recheck weather, baggage limits, drying time, and exact activities before departure.',
            'No visible external body anchors; official source trail and image-license records stay auditable through metadata and shortcode output.',
        ]),
        'vg_eeat_related_routes' => implode("\n", [
            'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Use this for the broad route order before clothing starts competing with base choice.',
            'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Use this to match packing to the season and region before locking dates.',
            'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Use before departure for medication, personal health, and insurance preparation.',
            'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Keep the bag, laundry, and baggage decisions honest against the actual route budget.',
            'Money in Vietnam | /plan/money-cash-cards-atms/ | Keep cash, cards, small notes, and payment habits workable while packing the day bag.',
            'SIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, weather checks, translation, and hotel contact working before packing around first arrivals.',
            'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair city movement, bags, and valuables with practical street awareness.',
            'Transport Within Vietnam | /plan/transport-within-vietnam/ | Pack differently for train, flight, car, cruise, and transfer-heavy days.',
            'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use northern city detail to decide whether a jacket or light layer should live in the carry-on.',
            'Ho Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Use southern city rhythm to decide how light the wardrobe can stay.',
            'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use central-coast logic for rain shells, beachwear, and city-comfort packing.',
            'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Use island logic for swimwear, sun protection, and dry-bag choices.',
            'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Use countryside and boat-day logic when deciding on shoes, shells, and quick-dry layers.',
            '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Use the route length to decide whether a carry-on is enough or a checked bag is simpler.',
        ]),
        'vg_eeat_hero_image_credit' => 'Hero image: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0. Body images: Hoi An Ancient Town by Jakub Halun, CC BY-SA 4.0; Vietnam, Hai-Van-Pass by Wolkenkratzer, CC BY-SA 4.0; Ho Chi Minh City Hall by Steffen Schmitz, CC BY-SA 4.0; Vietnam, Phong Dien, Mekong Delta, River by Vyacheslav Argenberg, CC BY 4.0; Kem Beach aerial view Phu Quoc Island Vietnam by Vivu Vietnam, CC BY-SA 4.0.',
        'vg_last_manual_review' => $review_date,
        'vg_admin_first_notes' => 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, checks Rank Math/social preview, verifies image presentation, weather logic, packing logic, carry-on tradeoffs, temple modesty notes, source-trail records, and adds any first-hand packing notes before publishing. This article is not a universal packing list; it is a route-specific packing decision guide.',
    ];
}

$failures = [];
$notes = [];
$post = vg_verify_packing_post_by_slug('what-to-pack-for-vietnam-region-season');

if (! $post instanceof WP_Post) {
    vg_verify_packing_post_finish(['Required post not found: what-to-pack-for-vietnam-region-season']);
}

$post_id = (int) $post->ID;

if ($post->post_status !== 'draft') {
    $failures[] = "Vietnam packing post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Vietnam packing comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Vietnam packing ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_packing_rendered_content($post);

foreach (
    [
        'hero marker' => 'vg-packing-hero:v1',
        'verdict marker' => 'vg-packing-concierge-verdict:v1',
        'at a glance marker' => 'vg-packing-at-a-glance:v1',
        'photo proof marker' => 'vg-packing-photo-proof:v1',
        'source diversity marker' => 'vg-packing-source-diversity:v1',
        'region season marker' => 'vg-packing-region-season-matrix:v1',
        'activity logic marker' => 'vg-packing-activity-logic:v1',
        'temple modesty marker' => 'vg-packing-temple-modesty:v1',
        'carry on marker' => 'vg-packing-carry-on-vs-checked:v1',
        'overpack marker' => 'vg-packing-overpack-traps:v1',
        'live checks marker' => 'vg-packing-live-checks:v1',
        'FAQ marker' => 'vg-packing-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'weather source URL' => 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam',
        'plan your trip source URL' => 'https://vietnam.travel/plan-your-trip',
        'health source URL' => 'https://vietnam.travel/plan-your-trip/health-safety',
        'north destination source URL' => 'https://vietnam.travel/places-to-go/northern-vietnam',
        'central destination source URL' => 'https://vietnam.travel/places-to-go/central-vietnam',
        'south destination source URL' => 'https://vietnam.travel/places-to-go/southern-vietnam',
        'NCHMF source URL' => 'https://nchmf.gov.vn/KttvsiteE/en-US/2/index.html',
        'WMO source URL' => 'https://worldweather.wmo.int/en/country.html?countryCode=82',
        'CDC source URL' => 'https://wwwnc.cdc.gov/travel/destinations/traveler/none/vietnam',
        'GOV.UK source note' => 'GOV.UK - Vietnam health',
        'core decision phrase' => 'Pack by region, season, and activity',
    ] as $label => $needle
) {
    $haystack = str_contains($label, 'panel') || str_contains($label, 'trail') || str_contains($label, 'log') || str_contains($label, 'routes') || str_contains($label, 'source')
        ? $rendered_content
        : $raw_content . "\n" . $rendered_content;

    if (! str_contains($haystack, $needle)) {
        $failures[] = "Vietnam packing post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/plan/vietnam-travel-guide/',
        '/plan/best-time-to-visit-vietnam/',
        '/plan/health-travel-insurance-vietnam/',
        '/costs/vietnam-travel-cost/',
        '/plan/money-cash-cards-atms/',
        '/plan/sim-esim-vietnam/',
        '/plan/safety-scams-vietnam/',
        '/plan/transport-within-vietnam/',
        '/destinations/hanoi-travel-guide/',
        '/destinations/ho-chi-minh-city-travel-guide/',
        '/destinations/da-nang-travel-guide/',
        '/destinations/phu-quoc-travel-guide/',
        '/destinations/ninh-binh-travel-guide/',
        '/itineraries/10-days-in-vietnam/',
    ] as $internal_path
) {
    if (! str_contains($raw_content . "\n" . $rendered_content, $internal_path)) {
        $failures[] = "Vietnam packing post is missing internal route: {$internal_path}";
    }
}

foreach (
    [
        'one-size-fits-all packing list',
        'top 10 things to pack',
        'always pack',
        'must-pack every time',
    ] as $unsafe_phrase
) {
    if (str_contains($raw_content, $unsafe_phrase) || str_contains($rendered_content, $unsafe_phrase)) {
        $failures[] = "Vietnam packing post contains unsafe/spam phrase: {$unsafe_phrase}";
    }
}

if (str_contains($raw_content, 'Draft status:') || str_contains($rendered_content, 'Draft status:')) {
    $failures[] = 'Vietnam packing post still contains brief placeholder text: Draft status:';
}

if (! str_contains($rendered_content, 'vg-related-routes-title-' . $post_id)) {
    $failures[] = 'Vietnam packing post is missing rendered related routes shortcode output.';
}

if (strlen(wp_strip_all_tags($raw_content)) < 9500) {
    $failures[] = 'Vietnam packing post content is too thin for complete draft status.';
}

foreach (
    [
        'post_title' => 'What to Pack for Vietnam by Region and Season',
        'post_name' => 'what-to-pack-for-vietnam-region-season',
    ] as $field => $expected_value
) {
    $actual_value = (string) $post->{$field};

    if ($actual_value !== $expected_value) {
        $failures[] = "Vietnam packing post has unexpected {$field}: {$actual_value}; expected {$expected_value}";
    }
}

foreach (vg_verify_packing_expected_meta() as $meta_key => $expected_value) {
    $actual_value = (string) get_post_meta($post_id, $meta_key, true);

    if ($actual_value !== $expected_value) {
        $failures[] = "Vietnam packing post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
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
        $failures[] = "Vietnam packing post is missing required meta: {$meta_key}";
    }
}

vg_verify_packing_assert_exact_terms($failures, $post_id, 'category', ['practicalities', 'seasonal-travel']);
vg_verify_packing_assert_exact_terms($failures, $post_id, 'post_tag', ['first-time-vietnam', 'rainy-season', 'family-travel', 'anti-spam-evergreen']);

$external_body_href_count = vg_verify_packing_external_body_href_count($rendered_content);

if ($external_body_href_count !== 0) {
    $failures[] = "Vietnam packing post external_body_href_count should be 0; found {$external_body_href_count}.";
}

$notes[] = 'Vietnam packing post ID: ' . $post_id;
$notes[] = 'Vietnam packing external_body_href_count: ' . $external_body_href_count;

vg_verify_packing_post_finish($failures, $notes);

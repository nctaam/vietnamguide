<?php
/**
 * Verify the Vietnam Rainy Season Travel complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-vietnam-rainy-season-flexible-route-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_rainy_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Vietnam rainy season post verification failed.');
        }

        echo 'Vietnam rainy season post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Vietnam rainy season post verification passed.');
        return;
    }

    echo 'Vietnam rainy season post verification passed.' . PHP_EOL;
}

function vg_verify_rainy_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_rainy_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_rainy_rendered_content(WP_Post $post): string
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

function vg_verify_rainy_external_body_href_count(string $content): int
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

function vg_verify_rainy_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_rainy_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_rainy_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

function vg_verify_rainy_expected_meta(): array
{
    $review_date = 'July 25, 2026';
    $hero_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg';
    $hanoi_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg';
    $hai_van_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1920px-Vietnam%2C_Hai-Van-Pass.jpg';
    $hcmc_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg';
    $mekong_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1920px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg';
    $phu_quoc_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg';

    return [
        'rank_math_title' => 'Vietnam Rainy Season Travel: Flexible Route Guide',
        'rank_math_description' => 'Plan Vietnam rainy season travel with regional weather logic, route buffers, cancellation posture, indoor pivots, and transport checks.',
        'rank_math_focus_keyword' => 'Vietnam rainy season travel',
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_editorial_target_publish_date' => '2026-08-10',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_reviewed_guide' => '1',
        'vg_eeat_written_by' => 'VietnamGuide editorial team',
        'vg_eeat_reviewed_by' => 'VietnamGuide editorial review',
        'vg_eeat_last_meaningful_update' => $review_date,
        'vg_eeat_primary_decision' => 'Treat rainy-season Vietnam as a route-flexibility problem: choose a lead region, protect buffers, and keep weather-sensitive bookings movable.',
        'vg_eeat_update_summary' => 'Expanded the native WordPress post brief into a complete rainy-season route draft with a photo-led hero, proof panel, concierge verdict, at-a-glance rainy-season matrix, photo proof grid, source-diversity table, region-by-region weather logic, route flexibility and buffer logic, cancellation and refund posture, city-day / indoor pivots, transport and transfer caution, fragile-booking traps, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.',
        'vg_eeat_sources_checked' => implode("\n", [
            "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for broad rainy-season and regional weather framing.",
            "Vietnam.travel - Plan your trip - https://vietnam.travel/plan-your-trip - checked {$review_date}; used for planning order and route discipline before bookings harden.",
            "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for transport and transfer caution in wet weather.",
            "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked {$review_date}; used for northern route and landscape-planning context.",
            "Vietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked {$review_date}; used for central-coast weather sensitivity, Hoi An, Hue, and Da Nang logic.",
            "Vietnam.travel - Southern Vietnam destinations - https://vietnam.travel/places-to-go/southern-vietnam - checked {$review_date}; used for southern city, Mekong, and island route context.",
            "National Centre for Hydro-Meteorological Forecasting - English forecast and warning pages - https://nchmf.gov.vn/KttvsiteE/en-US/2/index.html - checked {$review_date}; used for live forecast and warning discipline.",
            "World Weather Information Service - Viet Nam official city forecasts - https://worldweather.wmo.int/en/country.html?countryCode=82 - checked {$review_date}; used for official city-level weather context.",
            "CDC Travelers' Health - Vietnam - https://wwwnc.cdc.gov/travel/destinations/traveler/none/vietnam - checked {$review_date}; used for traveler-health framing around wet weather, heat, and personal medical judgment.",
            "GOV.UK - Vietnam health - https://www.gov.uk/foreign-travel-advice/vietnam/health - checked {$review_date}; used as an additional government travel-health source family.",
            "Wikimedia Commons image direct URL - Hoi An Ancient Town - {$hero_image} - credit Jakub Halun / CC BY-SA 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Hanoi Hoan Kiem Lake - {$hanoi_image} - credit Alex 69200 vx / CC BY-SA 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Vietnam, Hai-Van-Pass - {$hai_van_image} - credit Wolkenkratzer / CC BY-SA 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Ho Chi Minh City Hall - {$hcmc_image} - credit Steffen Schmitz / CC BY-SA 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Vietnam, Phong Dien, Mekong Delta, River - {$mekong_image} - credit Vyacheslav Argenberg / CC BY 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Kem Beach aerial view Phu Quoc Island Vietnam - {$phu_quoc_image} - credit Vivu Vietnam / CC BY-SA 4.0 - license context checked {$review_date}.",
        ]),
        'vg_eeat_field_note' => 'This post is the rainy-season flexibility layer for the native WordPress editorial calendar. It avoids weather-roundup content by helping travelers choose buffers, refundable bookings, indoor pivots, and region-specific route moves.',
        'vg_eeat_affiliate_status' => 'none',
        'vg_eeat_evidence_moat' => implode("\n", [
            'Decision-led rainy-season guide built around route flexibility instead of a generic month table.',
            'At-a-glance matrix separates north, central, south, mountain, coast, and island pressure.',
            'Photo proof makes each image do planning work: central heritage streets, northern city rhythm, pass wind, southern city heat, river days, and island exposure.',
            'Source-diversity table separates broad climate guidance from live weather checks and traveler-health judgment.',
            'Region-by-region weather logic explains how rain changes the route differently in the north, central coast, south, mountains, and islands.',
            'Buffer logic teaches travelers where flexibility matters and where a rainy day can still be a good city day.',
            'Cancellation and refund posture keeps non-refundable hotels, cruises, ferries, domestic flights, and day tours from hardening too early.',
            'City-day and indoor pivots prevent weather from turning the trip into a blank day.',
            'Transport and transfer caution covers road, rail, flight, cruise, ferry, and luggage risk.',
            'No visible external body anchors; official source trail and image-license records stay auditable through metadata and shortcode output.',
        ]),
        'vg_eeat_related_routes' => implode("\n", [
            'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Use this for the broad first-trip order before deciding whether rainy-season flexibility belongs in the route.',
            'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Use this to understand seasonal trade-offs before locking dates.',
            'Transport Within Vietnam | /plan/transport-within-vietnam/ | Use this to protect roads, trains, flights, ferries, and cruise pickups in wet weather.',
            'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Use before departure for medical, policy, interruption, and personal-health preparation.',
            'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair rainy night movement, phone handling, bags, and street awareness with practical habits.',
            'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Keep flexible hotels, transfers, and backup plans inside the real route budget.',
            'North Central South Vietnam | /compare/north-central-south-vietnam/ | Use this when rain makes one region a better lead than another.',
            '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Use this when a short trip needs fewer regions and stronger buffers.',
            '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use this when two weeks allow a more flexible north-central-south route.',
            'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use Hanoi city texture as a rainy-day pivot rather than treating the day as lost.',
            'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use central-coast logic when rain or wind changes beach and pass plans.',
            'Ho Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Use HCMC for southern city energy, museums, cafes, and flexible rainy afternoons.',
            'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Use Ninh Binh with boat, cave, cycling, and transfer caution when rain rises.',
            'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Use island logic only when a wet-weather resort plan still has value.',
        ]),
        'vg_eeat_hero_image_credit' => 'Hero image: Hoi An Ancient Town by Jakub Halun, CC BY-SA 4.0. Body images: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0; Vietnam, Hai-Van-Pass by Wolkenkratzer, CC BY-SA 4.0; Ho Chi Minh City Hall by Steffen Schmitz, CC BY-SA 4.0; Vietnam, Phong Dien, Mekong Delta, River by Vyacheslav Argenberg, CC BY 4.0; Kem Beach aerial view Phu Quoc Island Vietnam by Vivu Vietnam, CC BY-SA 4.0.',
        'vg_last_manual_review' => $review_date,
        'vg_admin_first_notes' => 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, checks Rank Math/social preview, verifies weather logic, cancellation posture, image presentation, source-trail records, and adds any first-hand rainy-route notes before publishing. This article is not a live forecast and should never imply weather certainty.',
    ];
}

$failures = [];
$notes = [];
$post = vg_verify_rainy_post_by_slug('vietnam-rainy-season-flexible-route');

if (! $post instanceof WP_Post) {
    vg_verify_rainy_post_finish(['Required post not found: vietnam-rainy-season-flexible-route']);
}

$post_id = (int) $post->ID;

if ($post->post_status !== 'draft') {
    $failures[] = "Vietnam rainy season post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Vietnam rainy season comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Vietnam rainy season ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_rainy_rendered_content($post);

foreach (
    [
        'hero marker' => 'vg-rainy-hero:v1',
        'verdict marker' => 'vg-rainy-concierge-verdict:v1',
        'at a glance marker' => 'vg-rainy-at-a-glance:v1',
        'photo proof marker' => 'vg-rainy-photo-proof:v1',
        'source diversity marker' => 'vg-rainy-source-diversity:v1',
        'region logic marker' => 'vg-rainy-region-logic:v1',
        'flexibility marker' => 'vg-rainy-flexibility:v1',
        'cancellation refund marker' => 'vg-rainy-cancellation-refund:v1',
        'city indoor pivots marker' => 'vg-rainy-city-indoor-pivots:v1',
        'transport caution marker' => 'vg-rainy-transport-caution:v1',
        'fragile bookings marker' => 'vg-rainy-fragile-bookings:v1',
        'live checks marker' => 'vg-rainy-live-checks:v1',
        'FAQ marker' => 'vg-rainy-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'weather source URL' => 'https://vietnam.travel/things-to-do/weather-and-climate-vietnam',
        'transport source URL' => 'https://vietnam.travel/plan-your-trip/transport-within-vietnam',
        'north source URL' => 'https://vietnam.travel/places-to-go/northern-vietnam',
        'central source URL' => 'https://vietnam.travel/places-to-go/central-vietnam',
        'south source URL' => 'https://vietnam.travel/places-to-go/southern-vietnam',
        'NCHMF source URL' => 'https://nchmf.gov.vn/KttvsiteE/en-US/2/index.html',
        'WMO source URL' => 'https://worldweather.wmo.int/en/country.html?countryCode=82',
        'CDC source URL' => 'https://wwwnc.cdc.gov/travel/destinations/traveler/none/vietnam',
        'GOV.UK source note' => 'GOV.UK - Vietnam health',
        'core decision phrase' => 'route-flexibility problem',
    ] as $label => $needle
) {
    $haystack = str_contains($label, 'panel') || str_contains($label, 'trail') || str_contains($label, 'log') || str_contains($label, 'routes') || str_contains($label, 'source')
        ? $rendered_content
        : $raw_content . "\n" . $rendered_content;

    if (! str_contains($haystack, $needle)) {
        $failures[] = "Vietnam rainy season post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/plan/vietnam-travel-guide/',
        '/plan/best-time-to-visit-vietnam/',
        '/plan/transport-within-vietnam/',
        '/plan/health-travel-insurance-vietnam/',
        '/plan/safety-scams-vietnam/',
        '/costs/vietnam-travel-cost/',
        '/compare/north-central-south-vietnam/',
        '/itineraries/10-days-in-vietnam/',
        '/itineraries/14-days-in-vietnam/',
        '/destinations/hanoi-travel-guide/',
        '/destinations/da-nang-travel-guide/',
        '/destinations/ho-chi-minh-city-travel-guide/',
        '/destinations/ninh-binh-travel-guide/',
        '/destinations/phu-quoc-travel-guide/',
    ] as $internal_path
) {
    if (! str_contains($raw_content . "\n" . $rendered_content, $internal_path)) {
        $failures[] = "Vietnam rainy season post is missing internal route: {$internal_path}";
    }
}

foreach (
    [
        'one rainy season controls the whole country',
        'weather roulette',
        'guaranteed dry',
        'top 10 rainy-season hacks',
    ] as $unsafe_phrase
) {
    if (str_contains($raw_content, $unsafe_phrase) || str_contains($rendered_content, $unsafe_phrase)) {
        $failures[] = "Vietnam rainy season post contains unsafe/spam phrase: {$unsafe_phrase}";
    }
}

if (str_contains($raw_content, 'Draft status:') || str_contains($rendered_content, 'Draft status:')) {
    $failures[] = 'Vietnam rainy season post still contains brief placeholder text: Draft status:';
}

if (! str_contains($rendered_content, 'vg-related-routes-title-' . $post_id)) {
    $failures[] = 'Vietnam rainy season post is missing rendered related routes shortcode output.';
}

if (strlen(wp_strip_all_tags($raw_content)) < 9500) {
    $failures[] = 'Vietnam rainy season post content is too thin for complete draft status.';
}

foreach (
    [
        'post_title' => 'Vietnam Rainy Season Travel: How to Build a Flexible Route',
        'post_name' => 'vietnam-rainy-season-flexible-route',
    ] as $field => $expected_value
) {
    $actual_value = (string) $post->{$field};

    if ($actual_value !== $expected_value) {
        $failures[] = "Vietnam rainy season post has unexpected {$field}: {$actual_value}; expected {$expected_value}";
    }
}

foreach (vg_verify_rainy_expected_meta() as $meta_key => $expected_value) {
    $actual_value = (string) get_post_meta($post_id, $meta_key, true);

    if ($actual_value !== $expected_value) {
        $failures[] = "Vietnam rainy season post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
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
        $failures[] = "Vietnam rainy season post is missing required meta: {$meta_key}";
    }
}

vg_verify_rainy_assert_exact_terms($failures, $post_id, 'category', ['seasonal-travel', 'itineraries']);
vg_verify_rainy_assert_exact_terms($failures, $post_id, 'post_tag', ['rainy-season', 'route-planning', 'first-time-vietnam', 'anti-spam-evergreen']);

$external_body_href_count = vg_verify_rainy_external_body_href_count($rendered_content);

if ($external_body_href_count !== 0) {
    $failures[] = "Vietnam rainy season post external_body_href_count should be 0; found {$external_body_href_count}.";
}

$notes[] = 'Vietnam rainy season post ID: ' . $post_id;
$notes[] = 'Vietnam rainy season external_body_href_count: ' . $external_body_href_count;

vg_verify_rainy_post_finish($failures, $notes);

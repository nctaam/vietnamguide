<?php
/**
 * Verify the Ha Long Bay Cruise Questions complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-ha-long-cruise-questions-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_ha_long_cruise_questions_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Ha Long cruise questions post verification failed.');
        }

        echo 'Ha Long cruise questions post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Ha Long cruise questions post verification passed.');
        return;
    }

    echo 'Ha Long cruise questions post verification passed.' . PHP_EOL;
}

function vg_verify_ha_long_cruise_questions_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_ha_long_cruise_questions_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_ha_long_cruise_questions_assert_target_meta(WP_Post $post, array &$failures): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-07',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if ($actual_value !== $expected_value) {
            $failures[] = "Ha Long cruise questions post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
        }
    }
}

function vg_verify_ha_long_cruise_questions_rendered_content(WP_Post $post): string
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

function vg_verify_ha_long_cruise_questions_external_body_href_count(string $content): int
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

function vg_verify_ha_long_cruise_questions_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_ha_long_cruise_questions_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_ha_long_cruise_questions_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

function vg_verify_ha_long_cruise_questions_expected_meta(): array
{
    $review_date = 'July 25, 2026';
    $hero_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg/1920px-Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg';
    $cruise_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3e/Halong_Bay_Cruise_Boats_01.jpg/1920px-Halong_Bay_Cruise_Boats_01.jpg';
    $cabin_context_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/28/View_of_sea_from_Titov_Island%2C_Ha_Long_Bay%2C_Vietnam%2C_20240128_1337_3732.jpg/1920px-View_of_sea_from_Titov_Island%2C_Ha_Long_Bay%2C_Vietnam%2C_20240128_1337_3732.jpg';
    $cave_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/10/Sung_Sot_Cave%2C_Ha_Long_Bay%2C_Vietnam%2C_20240128_1549_3836.jpg/1920px-Sung_Sot_Cave%2C_Ha_Long_Bay%2C_Vietnam%2C_20240128_1549_3836.jpg';
    $lan_ha_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/ca/Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg/1920px-Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg';
    $bai_tu_long_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/46/B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg/1920px-B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg';

    return [
        'rank_math_title' => 'Ha Long Bay Cruise Questions to Ask Before Booking',
        'rank_math_description' => 'Ask these Ha Long Bay cruise questions before booking: port, cabin, route map, weather policy, Lan Ha vs Ha Long, Bai Tu Long, family comfort, payment, and transfer risk.',
        'rank_math_focus_keyword' => 'Ha Long Bay cruise questions',
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_reviewed_guide' => '1',
        'vg_eeat_written_by' => 'VietnamGuide editorial team',
        'vg_eeat_reviewed_by' => 'VietnamGuide editorial review',
        'vg_eeat_last_meaningful_update' => $review_date,
        'vg_eeat_primary_decision' => 'Before booking a Ha Long Bay cruise, require clear written answers about pier, pickup, route map, cabin, deck space, weather policy, cancellation, payment, activity inclusions, family comfort, and onward transfer risk.',
        'vg_eeat_update_summary' => 'Expanded the native WordPress post brief into a complete cruise-shopping draft with a photo-led hero, proof panel, concierge verdict, at-a-glance buying matrix, photo proof, source-diversity table, route-map questions, port and transfer checks, cabin and deck checks, weather/cancellation policy questions, family comfort checks, Bai Tu Long trade-offs, payment-risk questions, red flags, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.',
        'vg_eeat_sources_checked' => implode("\n", [
            "Vietnam.travel - Ha Long destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-long - checked {$review_date}; used for high-level Ha Long destination framing, seasonal references, and cruise context without replacing product-level judgment.",
            "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked {$review_date}; used for northern route context around Hanoi, Ninh Binh, Ha Long, Lan Ha, and Cat Ba.",
            "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for weather-check discipline before bay cruise deposits, exposed activities, and onward transfers.",
            "Vietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}; used for transfer and road/port planning context.",
            "UNESCO World Heritage Centre - Ha Long Bay-Cat Ba Archipelago - https://whc.unesco.org/en/list/672/ - checked {$review_date}; used for heritage framing without letting a UNESCO label replace cruise-product checks.",
            "Ha Long Bay Management - https://halongbay.com.vn/ - checked {$review_date}; used for bay management, route, ticket, and operational context close to travel.",
            "Ha Long Bay Management - route VHL4 - https://halongbay.com.vn/tours/4-hanh-trinh-vhl-4-cang-tau-hang-co-thien-canh-son-hang-thay-hang-cap-la-vong-vieng-khu-sinh-thai-tung-ang-dao-cong-do-cong-vien-hon-xep - checked {$review_date}; used as an example of why route names and route maps matter for Bai Tu Long claims.",
            "Cat Ba tourism/service information - https://catba.com.vn/ - checked {$review_date}; used for Lan Ha and Cat Ba route context when a cruise uses an island-linked gateway.",
            "Bai Tu Long National Park - https://vuonquocgiabaitulong.vn/ - checked {$review_date}; used for national-park context when a cruise claim mentions Bai Tu Long or nature depth.",
            "Wikimedia Commons image direct URL - Ha Long Bay, Vietnam, View from above - {$hero_image} - credit Vyacheslav Argenberg / CC BY 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Halong Bay Cruise Boats 01 - {$cruise_image} - credit Shyamal L. / CC BY-SA 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Titov Island view - {$cabin_context_image} - credit Jakub Halun / CC BY 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Sung Sot Cave - {$cave_image} - credit Jakub Halun / CC BY 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Lan Ha Bay-Cat Ba Vietnam - {$lan_ha_image} - credit Saaremees / CC BY-SA 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Bai Tu Long Bay 01 - {$bai_tu_long_image} - credit Benjamin Smith / CC BY-SA 4.0 - license context checked {$review_date}.",
        ]),
        'vg_eeat_field_note' => 'This post is a cruise-shopping decision layer for the native WordPress editorial calendar. It avoids duplicating the Ha Long Bay Travel Guide, Ha Long Bay vs Lan Ha Bay, Bai Tu Long Bay Guide, and Ninh Binh to Ha Long Bay Transfer by focusing on questions to ask before a traveler pays a cruise deposit.',
        'vg_eeat_affiliate_status' => 'none',
        'vg_eeat_evidence_moat' => implode("\n", [
            'Question-led cruise-shopping article built around port, cabin, weather, route, and transfer risk rather than cruise rankings.',
            'Concierge verdict tells travelers to buy the route quality and operating clarity, not only the prettiest photos or star rating.',
            'At-a-glance matrix turns the main buying questions into answer, why it matters, and weak-answer risk.',
            'Photo proof explains what each cruise promise should map to: seascape, cruise density, viewpoint, cave route, Lan Ha, and Bai Tu Long.',
            'Source-diversity section separates official destination and heritage context from product-level questions a traveler must ask an operator.',
            'Route-map section requires route name, stops, activity sequence, time on water, and what changes in bad weather.',
            'Port and transfer section connects Hanoi, Ninh Binh, Tuan Chau, Ha Long, Lan Ha, Cat Ba, Bai Tu Long, luggage, and onward timing.',
            'Cabin, deck, food, and noise checks give practical decision value beyond generic luxury cruise copy.',
            'Weather and cancellation policy checks explain reroute, refund, authority restriction, and onward flight risk without stale guarantees.',
            'Family comfort and mobility section makes the guide useful for parents, older travelers, and mixed-energy groups.',
            'Bai Tu Long trade-off section prevents vague quieter-bay marketing from replacing route verification.',
            'Payment-risk and red-flag tables avoid affiliate-style recommendations and support manual WordPress review.',
            'No visible external body anchors; official source trail and image-license records stay auditable through metadata and shortcode output.',
        ]),
        'vg_eeat_related_routes' => implode("\n", [
            'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Use for the full destination frame, cruise length, timing, port, route fit, and skip logic.',
            'Ha Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Choose the right bay, port, cruise style, Cat Ba access, crowd pressure, and overnight value before booking.',
            'Bai Tu Long Bay Guide | /destinations/bai-tu-long-bay-guide/ | Decide whether a quieter, route-specific bay cruise is worth the extra buying friction versus Ha Long, Lan Ha, or Cat Ba.',
            'Ninh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.',
            'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check northern visibility, mist, heat, rain, storm-season pressure, and cancellation risk before cruise deposits.',
            'Transport Within Vietnam | /plan/transport-within-vietnam/ | Put cruise pickup, pier, road transfer, luggage, and onward movement inside the wider Vietnam transport plan.',
            'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the real difference between cabin class, route quality, transfer certainty, flexibility, and deposit risk.',
            'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check boat, weather, activity, interruption, cancellation, evacuation, and medical coverage before a cruise.',
            'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair cruise deposits, transfer offers, payment channels, and activity promises with practical risk checks.',
            '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a bay cruise improves or overloads a short first route.',
            '14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when the bay can become a protected northern chapter without stealing from Central or South Vietnam.',
            'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide whether the northern landscape chapter should be countryside, bay, or both.',
            'Best Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Compare whether a bay cruise should be a day preview, overnight cruise, or clean skip.',
        ]),
        'vg_eeat_hero_image_credit' => 'Hero and Ha Long aerial image: Ha Long Bay, Vietnam, View from above by Vyacheslav Argenberg, CC BY 4.0. Body images: Halong Bay Cruise Boats 01 by Shyamal L., CC BY-SA 4.0; Titov Island view by Jakub Halun, CC BY 4.0; Sung Sot Cave by Jakub Halun, CC BY 4.0; Lan Ha Bay-Cat Ba by Saaremees, CC BY-SA 4.0; Bai Tu Long Bay 01 by Benjamin Smith, CC BY-SA 4.0.',
        'vg_last_manual_review' => $review_date,
        'vg_admin_first_notes' => 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, confirms image presentation, checks Rank Math/social preview, verifies Ha Long, Lan Ha, Cat Ba, Bai Tu Long, UNESCO, weather, route-management, transfer, and image-license sources, and decides whether first-hand cruise vetting notes should be added before publishing.',
    ];
}

$failures = [];
$notes = [];
$post = vg_verify_ha_long_cruise_questions_post_by_slug('ha-long-bay-cruise-questions-before-booking');

if (! $post instanceof WP_Post) {
    vg_verify_ha_long_cruise_questions_post_finish(['Required post not found: ha-long-bay-cruise-questions-before-booking']);
}

$post_id = (int) $post->ID;

if ($post->post_status !== 'draft') {
    $failures[] = "Ha Long cruise questions post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Ha Long cruise questions comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Ha Long cruise questions ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_ha_long_cruise_questions_rendered_content($post);

foreach (
    [
        'hero marker' => 'vg-ha-long-cruise-questions-hero:v1',
        'verdict marker' => 'vg-ha-long-cruise-questions-verdict:v1',
        'at a glance marker' => 'vg-ha-long-cruise-questions-at-a-glance:v1',
        'photo grid marker' => 'vg-ha-long-cruise-questions-photo-grid:v1',
        'source diversity marker' => 'vg-ha-long-cruise-questions-source-diversity:v1',
        'route map marker' => 'vg-ha-long-cruise-questions-route-map:v1',
        'port transfer marker' => 'vg-ha-long-cruise-questions-port-transfer:v1',
        'cabin deck marker' => 'vg-ha-long-cruise-questions-cabin-deck:v1',
        'weather policy marker' => 'vg-ha-long-cruise-questions-weather-policy:v1',
        'family comfort marker' => 'vg-ha-long-cruise-questions-family-comfort:v1',
        'Bai Tu Long marker' => 'vg-ha-long-cruise-questions-bai-tu-long:v1',
        'payment risk marker' => 'vg-ha-long-cruise-questions-payment-risk:v1',
        'red flags marker' => 'vg-ha-long-cruise-questions-red-flags:v1',
        'live checks marker' => 'vg-ha-long-cruise-questions-live-checks:v1',
        'FAQ marker' => 'vg-ha-long-cruise-questions-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'destination source note' => 'Vietnam.travel - Ha Long destination page',
        'UNESCO source note' => 'UNESCO World Heritage Centre - Ha Long Bay-Cat Ba Archipelago',
        'management source note' => 'Ha Long Bay Management',
        'Cat Ba source note' => 'Cat Ba tourism',
        'Bai Tu Long source note' => 'Bai Tu Long',
        'core risk phrase' => 'port, cabin, weather, route, and transfer risk',
    ] as $label => $needle
) {
    $haystack = str_contains($label, 'panel') || str_contains($label, 'trail') || str_contains($label, 'log') || str_contains($label, 'routes') || str_contains($label, 'source note')
        ? $rendered_content
        : $raw_content . "\n" . $rendered_content;

    if (! str_contains($haystack, $needle)) {
        $failures[] = "Ha Long cruise questions post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/destinations/ha-long-bay-travel-guide/',
        '/compare/ha-long-bay-vs-lan-ha-bay/',
        '/destinations/bai-tu-long-bay-guide/',
        '/plan/ninh-binh-to-ha-long-bay-transfer/',
        '/plan/best-time-to-visit-vietnam/',
        '/plan/transport-within-vietnam/',
        '/costs/vietnam-travel-cost/',
        '/plan/health-travel-insurance-vietnam/',
        '/plan/safety-scams-vietnam/',
        '/itineraries/10-days-in-vietnam/',
        '/itineraries/14-days-in-vietnam/',
    ] as $internal_path
) {
    if (! str_contains($raw_content . "\n" . $rendered_content, $internal_path)) {
        $failures[] = "Ha Long cruise questions post is missing internal route: {$internal_path}";
    }
}

if (str_contains($raw_content, 'Draft status:') || str_contains($rendered_content, 'Draft status:')) {
    $failures[] = 'Ha Long cruise questions post still contains brief placeholder text: Draft status:';
}

if (! str_contains($rendered_content, 'vg-related-routes-title-' . $post_id)) {
    $failures[] = 'Ha Long cruise questions post is missing rendered related routes shortcode output.';
}

if (strlen(wp_strip_all_tags($raw_content)) < 9500) {
    $failures[] = 'Ha Long cruise questions post content is too thin for complete draft status.';
}

foreach (
    [
        'post_title' => 'Ha Long Bay Cruise Questions to Ask Before Booking',
        'post_name' => 'ha-long-bay-cruise-questions-before-booking',
    ] as $field => $expected_value
) {
    $actual_value = (string) $post->{$field};

    if ($actual_value !== $expected_value) {
        $failures[] = "Ha Long cruise questions post has unexpected {$field}: {$actual_value}; expected {$expected_value}";
    }
}

vg_verify_ha_long_cruise_questions_assert_target_meta($post, $failures);

foreach (vg_verify_ha_long_cruise_questions_expected_meta() as $meta_key => $expected_value) {
    $actual_value = (string) get_post_meta($post_id, $meta_key, true);

    if ($actual_value !== $expected_value) {
        $failures[] = "Ha Long cruise questions post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
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
        $failures[] = "Ha Long cruise questions post is missing required meta: {$meta_key}";
    }
}

vg_verify_ha_long_cruise_questions_assert_exact_terms($failures, $post_id, 'category', ['beaches-islands', 'transport-logistics']);
vg_verify_ha_long_cruise_questions_assert_exact_terms($failures, $post_id, 'post_tag', ['cruise-planning', 'premium-travel', 'family-travel', 'anti-spam-evergreen']);

$external_body_href_count = vg_verify_ha_long_cruise_questions_external_body_href_count($raw_content);

if ($external_body_href_count !== 0) {
    $failures[] = "Ha Long cruise questions post external_body_href_count should be 0; found {$external_body_href_count}.";
}

$notes[] = 'Ha Long cruise questions post ID: ' . $post_id;
$notes[] = 'Ha Long cruise questions external_body_href_count: ' . $external_body_href_count;

vg_verify_ha_long_cruise_questions_post_finish($failures, $notes);

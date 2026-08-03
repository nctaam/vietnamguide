<?php
/**
 * Verify the Vietnam Food Safety and Street Food Etiquette complete draft.
 *
 * Run from the WordPress root:
 * wp eval-file ops/verify-vietnam-food-safety-street-food-etiquette-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_food_safety_post_finish(array $failures, array $notes = []): void
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
            WP_CLI::error('Vietnam food safety and street food etiquette post verification failed.');
        }

        echo 'Vietnam food safety and street food etiquette post verification failed.' . PHP_EOL;
        exit(1);
    }

    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success('Vietnam food safety and street food etiquette post verification passed.');
        return;
    }

    echo 'Vietnam food safety and street food etiquette post verification passed.' . PHP_EOL;
}

function vg_verify_food_safety_post_by_slug(string $slug): ?WP_Post
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
        vg_verify_food_safety_post_finish(['Expected exactly one post with slug ' . $slug . '; found ' . count($posts) . '.']);
    }

    return $posts[0] ?? null;
}

function vg_verify_food_safety_assert_target_meta(WP_Post $post, array &$failures): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-08',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = (string) get_post_meta($post_id, $meta_key, true);

        if ($actual_value !== $expected_value) {
            $failures[] = "Vietnam food safety post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
        }
    }
}

function vg_verify_food_safety_rendered_content(WP_Post $post): string
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

function vg_verify_food_safety_external_body_href_count(string $content): int
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

function vg_verify_food_safety_term_slugs(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);

    if (is_wp_error($terms)) {
        return [];
    }

    $slugs = array_map('strval', $terms);
    sort($slugs);

    return $slugs;
}

function vg_verify_food_safety_assert_exact_terms(array &$failures, int $post_id, string $taxonomy, array $expected_slugs): void
{
    $actual_slugs = vg_verify_food_safety_term_slugs($post_id, $taxonomy);
    $expected = $expected_slugs;
    sort($expected);

    if ($actual_slugs !== $expected) {
        $failures[] = "{$taxonomy} slugs mismatch: " . implode(', ', $actual_slugs) . '; expected ' . implode(', ', $expected);
    }
}

function vg_verify_food_safety_expected_meta(): array
{
    $review_date = 'July 25, 2026';
    $hero_image = 'https://upload.wikimedia.org/wikipedia/commons/c/ca/Street_food_in_Vietnam.jpg';
    $hanoi_vendor_image = 'https://upload.wikimedia.org/wikipedia/commons/7/7a/Street_Food_vendors_Soft_crab_Hanoi_Vietnam.jpg';
    $hue_stand_image = 'https://upload.wikimedia.org/wikipedia/commons/9/99/Street_food_stand_in_Hu%E1%BA%BF.jpg';
    $banh_trang_image = 'https://upload.wikimedia.org/wikipedia/commons/f/fd/B%C3%A1nh_tr%C3%A1ng_n%C6%B0%E1%BB%9Bng_TP._H%E1%BB%93_Ch%C3%AD_Minh_-_street_food_in_Ho_Chi_Minh_City%2C_Vietnam.jpg';
    $market_image = 'https://upload.wikimedia.org/wikipedia/commons/4/40/Vietnamise_Street_Food_Market_002.jpg';

    return [
        'rank_math_title' => 'Vietnam Food Safety and Street Food Etiquette',
        'rank_math_description' => 'Vietnam street food safety and etiquette for first-timers: choose busy stalls, order politely, handle sauces, ice, drinks, cash, seating, and flexible meals.',
        'rank_math_focus_keyword' => 'Vietnam food safety street food etiquette',
        'vg_editorial_brief_status' => 'complete_draft',
        'vg_content_owner' => 'wp_admin',
        'vg_automation_lock' => 'locked',
        'vg_eeat_reviewed_guide' => '1',
        'vg_eeat_written_by' => 'VietnamGuide editorial team',
        'vg_eeat_reviewed_by' => 'VietnamGuide editorial review',
        'vg_eeat_last_meaningful_update' => $review_date,
        'vg_eeat_primary_decision' => 'Enjoy Vietnam street food by choosing context well: busy stalls, hot turnover, visible prep, simple first orders, careful sauces and drinks, small cash, clean hands, and a flexible meal plan.',
        'vg_eeat_update_summary' => 'Expanded the native WordPress post brief into a complete food-safety and street-food etiquette draft with a photo-led hero, proof panel, concierge verdict, at-a-glance decision matrix, photo proof, source-diversity table, stall-choice framework, hygiene-risk guidance, sauces/ice/drinks section, ordering etiquette, seating/payment habits, flexible meal plan, city rhythm, red flags, live checks, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.',
        'vg_eeat_sources_checked' => implode("\n", [
            "Vietnam.travel - Beginner's guide to Vietnamese street food - https://vietnam.travel/things-to-do/beginners-guide-vietnamese-street-food - checked {$review_date}; used for destination-level street-food framing and the idea that food is part of everyday travel, not a fear topic.",
            "Vietnam.travel - Vietnamese etiquette for travellers - https://vietnam.travel/things-to-do/vietnamese-etiquette-travellers - checked {$review_date}; used for polite behavior, local rhythm, and cultural context around shared spaces.",
            "Vietnam.travel - Health and safety - https://vietnam.travel/plan-your-trip/health-safety - checked {$review_date}; used for broad health-safety framing without turning this article into medical advice.",
            "Vietnam.travel - Currency and payments in Vietnam - https://vietnam.travel/plan-your-trip/currency-vietnam - checked {$review_date}; used for small-cash and payment-context reminders around markets and informal meals.",
            "CDC Travelers' Health - Vietnam - https://wwwnc.cdc.gov/travel/destinations/traveler/none/vietnam - checked {$review_date}; used for traveler-health framing and the need for personal medical judgment.",
            "CDC - Food and Water Safety - https://wwwnc.cdc.gov/travel/page/food-water-safety - checked {$review_date}; used for safe-food and safe-drink behavior principles for travelers.",
            "CDC Yellow Book - Food and Water Precautions for Travelers - https://www.cdc.gov/yellow-book/hcp/preparing-international-travelers/food-and-water-precautions-for-travelers.html - checked {$review_date}; used for traveler food/water risk discipline and health disclaimer boundaries.",
            "WHO - Five keys to safer food - https://www.who.int/activities/promoting-safe-food-handling/five-key-to-safer-food - checked {$review_date}; used for heat, cleanliness, separation, and safer-food handling principles.",
            "GOV.UK - Vietnam health - https://www.gov.uk/foreign-travel-advice/vietnam/health - checked {$review_date}; used as an additional government travel-health source family.",
            "TravelHealthPro - Vietnam - https://travelhealthpro.org.uk/country/240/vietnam - checked {$review_date}; used as an optional UK travel-health source trail entry, not as the only health source.",
            "Wikimedia Commons image direct URL - Street food in Vietnam - {$hero_image} - credit Dieglop / CC BY-SA 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Street Food vendors Soft crab Hanoi Vietnam - {$hanoi_vendor_image} - credit Celestine M.C. Leroy / CC BY-SA 2.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Street food stand in Hue - {$hue_stand_image} - credit Christophe95 / CC BY-SA 4.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Banh trang nuong in Ho Chi Minh City - {$banh_trang_image} - credit Light Write / CC BY-SA 2.0 - license context checked {$review_date}.",
            "Wikimedia Commons image direct URL - Vietnamese Street Food Market 002 - {$market_image} - credit Dieglop / CC BY-SA 4.0 - license context checked {$review_date}.",
        ]),
        'vg_eeat_field_note' => 'This post is a first-timer behavior layer for the native WordPress editorial calendar. It avoids duplicating broad food, safety, money, health, Hanoi, and Ho Chi Minh City guides by focusing on how travelers choose stalls, behave politely, manage uncertainty, and keep meals flexible.',
        'vg_eeat_affiliate_status' => 'none',
        'vg_eeat_evidence_moat' => implode("\n", [
            'Decision-led street-food guide built around behavior, stall context, ordering, payment, and flexible meal planning rather than a dish ranking.',
            'Concierge verdict gives a calm first answer: eat street food, but choose context well.',
            'At-a-glance matrix turns first-timer questions into practical choices and weak-signal warnings.',
            'Photo proof makes each image do planning work: crowd rhythm, visible prep, seating, cooked-to-order food, and market context.',
            'Source-diversity table separates what official food/travel-health sources can support from what still needs traveler-specific judgment.',
            'Stall-choice framework uses turnover, local meal time, visible cooking, queue behavior, and menu complexity instead of viral recommendations.',
            'Hygiene-risk section gives practical risk reduction without fear framing or medical promises.',
            'Sauces, ice, drinks, herbs, utensils, and shared-table guidance help travelers make small decisions at the meal, not only before the trip.',
            'Ordering etiquette and payment sections make the guide culturally useful and reduce friction for first-time international travelers.',
            'Flexible meal plan prevents rigid dish-list itineraries and gives backup choices for heat, fatigue, stomach sensitivity, and travel days.',
            'City rhythm section distinguishes Hanoi, Hue, Hoi An, Ho Chi Minh City, and travel-day eating without stale restaurant claims.',
            'No visible external body anchors; official source trail and image-license records stay auditable through metadata and shortcode output.',
        ]),
        'vg_eeat_related_routes' => implode("\n", [
            'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Use this for the broad first-trip route before food choices start competing with transfer energy.',
            'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Use before departure for medical, policy, pharmacy, interruption, and personal-health preparation.',
            'Safety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair food streets, markets, night movement, phone handling, and payment habits with practical street awareness.',
            'Money in Vietnam | /plan/money-cash-cards-atms/ | Prepare small cash, change, cards, and payment backup before informal meals and markets.',
            'SIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, translation, ride-hailing, and hotel contact working before searching for meals in a new city.',
            'Transport Within Vietnam | /plan/transport-within-vietnam/ | Keep meal timing realistic around airport transfers, early pickups, train days, and long road legs.',
            'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Price food realistically without letting cheap meals hide transfer, health, or comfort costs.',
            'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Adjust food walks for heat, rain, humidity, and regional season pressure.',
            'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use Hanoi food streets as part of city orientation, not an arrival-night endurance test.',
            'Best Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Fold food, markets, and walking radius into a first-time Hanoi day without overloading it.',
            'Ho Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Use southern city rhythm, markets, cafes, and night food energy without losing airport or traffic control.',
            '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Place food experiences where they help the route breathe, not where they make early transfers harder.',
        ]),
        'vg_eeat_hero_image_credit' => 'Hero image: Street food in Vietnam by Dieglop, CC BY-SA 4.0. Body images: Street Food vendors Soft crab Hanoi Vietnam by Celestine M.C. Leroy, CC BY-SA 2.0; Street food stand in Hue by Christophe95, CC BY-SA 4.0; Banh trang nuong in Ho Chi Minh City by Light Write, CC BY-SA 2.0; Vietnamese Street Food Market 002 by Dieglop, CC BY-SA 4.0.',
        'vg_last_manual_review' => $review_date,
        'vg_admin_first_notes' => 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, checks Rank Math/social preview, verifies food, etiquette, health, payment, safety, image-license, and source-trail records, and adds any first-hand editorial notes or restaurant-locality observations before publishing. This article is not medical advice.',
    ];
}

$failures = [];
$notes = [];
$post = vg_verify_food_safety_post_by_slug('vietnam-food-safety-street-food-etiquette');

if (! $post instanceof WP_Post) {
    vg_verify_food_safety_post_finish(['Required post not found: vietnam-food-safety-street-food-etiquette']);
}

$post_id = (int) $post->ID;

if ($post->post_status !== 'draft') {
    $failures[] = "Vietnam food safety post_status !== 'draft': {$post->post_status}";
}

if ($post->comment_status !== 'closed') {
    $failures[] = "Vietnam food safety comment_status !== 'closed': {$post->comment_status}";
}

if ($post->ping_status !== 'closed') {
    $failures[] = "Vietnam food safety ping_status !== 'closed': {$post->ping_status}";
}

$raw_content = (string) $post->post_content;
$rendered_content = vg_verify_food_safety_rendered_content($post);

foreach (
    [
        'hero marker' => 'vg-food-safety-hero:v1',
        'verdict marker' => 'vg-food-safety-concierge-verdict:v1',
        'at a glance marker' => 'vg-food-safety-at-a-glance:v1',
        'photo proof marker' => 'vg-food-safety-photo-proof:v1',
        'source diversity marker' => 'vg-food-safety-source-diversity:v1',
        'choose stalls marker' => 'vg-food-safety-choose-stalls:v1',
        'order etiquette marker' => 'vg-food-safety-order-etiquette:v1',
        'hygiene risk marker' => 'vg-food-safety-hygiene-risk:v1',
        'drinks ice marker' => 'vg-food-safety-drinks-ice:v1',
        'sauces seating payment marker' => 'vg-food-safety-sauces-seating-payment:v1',
        'flex meal plan marker' => 'vg-food-safety-flex-meal-plan:v1',
        'city rhythm marker' => 'vg-food-safety-city-rhythm:v1',
        'red flags marker' => 'vg-food-safety-red-flags:v1',
        'live checks marker' => 'vg-food-safety-live-checks:v1',
        'FAQ marker' => 'vg-food-safety-faq:v1',
        'proof panel' => 'vg-proof-panel',
        'source trail' => 'vg-source-trail',
        'update log' => 'vg-update-log',
        'related routes' => 'vg-related-routes',
        'street food source URL' => 'https://vietnam.travel/things-to-do/beginners-guide-vietnamese-street-food',
        'health source URL' => 'https://vietnam.travel/plan-your-trip/health-safety',
        'CDC source URL' => 'https://wwwnc.cdc.gov/travel/page/food-water-safety',
        'WHO source URL' => 'https://www.who.int/activities/promoting-safe-food-handling/five-key-to-safer-food',
        'GOV.UK source note' => 'GOV.UK - Vietnam health',
        'core decision phrase' => 'eat street food, but choose context well',
    ] as $label => $needle
) {
    $haystack = str_contains($label, 'panel') || str_contains($label, 'trail') || str_contains($label, 'log') || str_contains($label, 'routes') || str_contains($label, 'source')
        ? $rendered_content
        : $raw_content . "\n" . $rendered_content;

    if (! str_contains($haystack, $needle)) {
        $failures[] = "Vietnam food safety post is missing {$label}: {$needle}";
    }
}

foreach (
    [
        '/plan/vietnam-travel-guide/',
        '/plan/safety-scams-vietnam/',
        '/plan/health-travel-insurance-vietnam/',
        '/plan/money-cash-cards-atms/',
        '/plan/sim-esim-vietnam/',
        '/plan/transport-within-vietnam/',
        '/costs/vietnam-travel-cost/',
        '/plan/best-time-to-visit-vietnam/',
        '/destinations/hanoi-travel-guide/',
        '/destinations/best-things-to-do-in-hanoi/',
        '/destinations/ho-chi-minh-city-travel-guide/',
        '/itineraries/10-days-in-vietnam/',
    ] as $internal_path
) {
    if (! str_contains($raw_content . "\n" . $rendered_content, $internal_path)) {
        $failures[] = "Vietnam food safety post is missing internal route: {$internal_path}";
    }
}

foreach (
    [
        'never eat street food',
        'guaranteed safe',
        'hidden gem',
        'top 50 dishes',
    ] as $unsafe_phrase
) {
    if (str_contains($raw_content, $unsafe_phrase) || str_contains($rendered_content, $unsafe_phrase)) {
        $failures[] = "Vietnam food safety post contains unsafe/spam phrase: {$unsafe_phrase}";
    }
}

if (str_contains($raw_content, 'Draft status:') || str_contains($rendered_content, 'Draft status:')) {
    $failures[] = 'Vietnam food safety post still contains brief placeholder text: Draft status:';
}

if (! str_contains($rendered_content, 'vg-related-routes-title-' . $post_id)) {
    $failures[] = 'Vietnam food safety post is missing rendered related routes shortcode output.';
}

if (strlen(wp_strip_all_tags($raw_content)) < 9500) {
    $failures[] = 'Vietnam food safety post content is too thin for complete draft status.';
}

foreach (
    [
        'post_title' => 'Vietnam Food Safety and Street Food Etiquette for First-Timers',
        'post_name' => 'vietnam-food-safety-street-food-etiquette',
    ] as $field => $expected_value
) {
    $actual_value = (string) $post->{$field};

    if ($actual_value !== $expected_value) {
        $failures[] = "Vietnam food safety post has unexpected {$field}: {$actual_value}; expected {$expected_value}";
    }
}

vg_verify_food_safety_assert_target_meta($post, $failures);

foreach (vg_verify_food_safety_expected_meta() as $meta_key => $expected_value) {
    $actual_value = (string) get_post_meta($post_id, $meta_key, true);

    if ($actual_value !== $expected_value) {
        $failures[] = "Vietnam food safety post has unexpected {$meta_key}: {$actual_value}; expected {$expected_value}";
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
        $failures[] = "Vietnam food safety post is missing required meta: {$meta_key}";
    }
}

vg_verify_food_safety_assert_exact_terms($failures, $post_id, 'category', ['food-culture', 'practicalities']);
vg_verify_food_safety_assert_exact_terms($failures, $post_id, 'post_tag', ['street-food', 'first-time-vietnam', 'international-travelers', 'anti-spam-evergreen']);

$external_body_href_count = vg_verify_food_safety_external_body_href_count($raw_content);

if ($external_body_href_count !== 0) {
    $failures[] = "Vietnam food safety post external_body_href_count should be 0; found {$external_body_href_count}.";
}

$notes[] = 'Vietnam food safety post ID: ' . $post_id;
$notes[] = 'Vietnam food safety external_body_href_count: ' . $external_body_href_count;

vg_verify_food_safety_post_finish($failures, $notes);

<?php
/**
 * Create the native WordPress Posts editorial system for VietnamGuide.
 *
 * Run from the WordPress root:
 * wp eval-file ops/apply-wordpress-post-editorial-system.php --allow-root
 *
 * No future posts are scheduled by this script. It creates draft editorial
 * briefs only, then locks them for WordPress Admin editing.
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_post_editorial_categories(): array
{
    return [
        ['name' => 'Travel Planning', 'slug' => 'travel-planning', 'description' => 'Evergreen Vietnam travel planning decisions for international visitors.'],
        ['name' => 'Itineraries', 'slug' => 'itineraries', 'description' => 'Route shapes, trip lengths, and pacing decisions.'],
        ['name' => 'Destinations', 'slug' => 'destinations', 'description' => 'Place-level decisions with route fit and skip logic.'],
        ['name' => 'Transport & Logistics', 'slug' => 'transport-logistics', 'description' => 'Transfers, airports, trains, roads, ferries, and arrival decisions.'],
        ['name' => 'Food & Culture', 'slug' => 'food-culture', 'description' => 'Food, etiquette, heritage, and everyday cultural context.'],
        ['name' => 'Beaches & Islands', 'slug' => 'beaches-islands', 'description' => 'Beach, island, cruise, and coastal route decisions.'],
        ['name' => 'Practicalities', 'slug' => 'practicalities', 'description' => 'Money, packing, safety, insurance, SIM, and pre-trip checks.'],
        ['name' => 'Hotels & Neighborhoods', 'slug' => 'hotels-neighborhoods', 'description' => 'Stay-area decisions before choosing specific accommodation.'],
        ['name' => 'Seasonal Travel', 'slug' => 'seasonal-travel', 'description' => 'Weather, holiday, and month-by-month planning.'],
        ['name' => 'Heritage & Culture', 'slug' => 'heritage-culture', 'description' => 'UNESCO, historic towns, museums, temples, and culture-first planning.'],
    ];
}

function vg_post_editorial_tags(): array
{
    return [
        'first-time-vietnam' => 'First-time Vietnam',
        'international-travelers' => 'International travelers',
        'route-planning' => 'Route planning',
        'family-travel' => 'Family travel',
        'premium-travel' => 'Premium travel',
        'budget-planning' => 'Budget planning',
        'rainy-season' => 'Rainy season',
        'arrival-day' => 'Arrival day',
        'street-food' => 'Street food',
        'cruise-planning' => 'Cruise planning',
        'hotel-base' => 'Hotel base',
        'anti-spam-evergreen' => 'Anti-spam evergreen',
    ];
}

function vg_post_editorial_briefs(): array
{
    return [
        [
            'title' => 'Vietnam First Trip Planning Checklist: What to Decide Before Booking',
            'slug' => 'vietnam-first-trip-planning-checklist',
            'target_date' => '2026-08-01',
            'categories' => ['travel-planning', 'practicalities'],
            'tags' => ['first-time-vietnam', 'international-travelers', 'route-planning', 'anti-spam-evergreen'],
            'intent' => 'Help first-time international visitors decide route shape, trip length, entry checks, budget, and booking order before paying for flights or hotels.',
            'reader_job' => 'Turn early excitement into a practical decision sequence.',
            'evidence_moat' => 'Use original route-order judgment, internal pillar links, update discipline, and a pre-booking checklist instead of rewriting generic Vietnam tips.',
            'internal_links' => [
                '/plan/vietnam-travel-guide/',
                '/plan/best-time-to-visit-vietnam/',
                '/itineraries/10-days-in-vietnam/',
                '/costs/vietnam-travel-cost/',
            ],
        ],
        [
            'title' => 'Best Vietnam Routes for First-Time Visitors: North, Central, South or Open-Jaw?',
            'slug' => 'best-vietnam-routes-first-time-visitors',
            'target_date' => '2026-08-02',
            'categories' => ['itineraries', 'travel-planning'],
            'tags' => ['first-time-vietnam', 'route-planning', 'premium-travel', 'anti-spam-evergreen'],
            'intent' => 'Compare route shapes before the traveler chooses exact cities.',
            'reader_job' => 'Choose fewer better bases and avoid a maximum-coverage itinerary.',
            'evidence_moat' => 'Add first-trip route archetypes, movement cost logic, open-jaw flight reasoning, and internal itinerary fit.',
            'internal_links' => [
                '/compare/north-central-south-vietnam/',
                '/itineraries/7-days-in-vietnam/',
                '/itineraries/14-days-in-vietnam/',
                '/plan/transport-within-vietnam/',
            ],
        ],
        [
            'title' => 'What to Pack for Vietnam by Region and Season',
            'slug' => 'what-to-pack-for-vietnam-region-season',
            'target_date' => '2026-08-03',
            'categories' => ['practicalities', 'seasonal-travel'],
            'tags' => ['first-time-vietnam', 'rainy-season', 'family-travel', 'anti-spam-evergreen'],
            'intent' => 'Help travelers pack by route and season, not by a generic tropical-country list.',
            'reader_job' => 'Avoid overpacking while still covering rain, heat, temples, beaches, cities, and mountain/cave conditions.',
            'evidence_moat' => 'Use region matrix, activity-specific packing logic, temple modesty notes, and mistakes that cause real friction.',
            'internal_links' => [
                '/plan/best-time-to-visit-vietnam/',
                '/plan/health-travel-insurance-vietnam/',
                '/plan/safety-scams-vietnam/',
                '/destinations/unesco-heritage-sites-vietnam/',
            ],
        ],
        [
            'title' => 'Vietnam Airport Arrival Checklist: Money, SIM, Transport and Scams',
            'slug' => 'vietnam-airport-arrival-checklist',
            'target_date' => '2026-08-04',
            'categories' => ['transport-logistics', 'practicalities'],
            'tags' => ['arrival-day', 'first-time-vietnam', 'international-travelers', 'anti-spam-evergreen'],
            'intent' => 'Give arriving travelers a calm first-hour sequence after landing in Vietnam.',
            'reader_job' => 'Know what to do before leaving the airport and what can wait until the hotel.',
            'evidence_moat' => 'Use arrival-order logic across cash, eSIM/SIM, ride pickup, taxi risk, luggage, and first meal choices.',
            'internal_links' => [
                '/plan/money-cash-cards-atms/',
                '/plan/sim-esim-vietnam/',
                '/plan/hanoi-airport-to-old-quarter/',
                '/plan/safety-scams-vietnam/',
            ],
        ],
        [
            'title' => 'Hanoi First-Time Visitor Mistakes to Avoid',
            'slug' => 'hanoi-first-time-visitor-mistakes',
            'target_date' => '2026-08-05',
            'categories' => ['destinations', 'travel-planning'],
            'tags' => ['first-time-vietnam', 'route-planning', 'anti-spam-evergreen'],
            'intent' => 'Support Hanoi destination pages with mistake-prevention content that has real planning value.',
            'reader_job' => 'Avoid bad base choice, arrival overload, weak day-trip sequencing, and route crowding.',
            'evidence_moat' => 'Use internal Hanoi cluster judgment, base-selection logic, transfer order, and day-trip trade-offs.',
            'internal_links' => [
                '/destinations/hanoi-travel-guide/',
                '/destinations/where-to-stay-in-hanoi/',
                '/destinations/best-day-trips-from-hanoi/',
                '/itineraries/hanoi-in-2-days/',
            ],
        ],
        [
            'title' => 'Ninh Binh Without Rushing: How to Choose Boat, Base and Transfer',
            'slug' => 'ninh-binh-without-rushing',
            'target_date' => '2026-08-06',
            'categories' => ['destinations', 'transport-logistics'],
            'tags' => ['route-planning', 'family-travel', 'anti-spam-evergreen'],
            'intent' => 'Help travelers decide whether Ninh Binh needs one long day, one protected night, or two slower nights.',
            'reader_job' => 'Prevent the common mistake of adding Trang An, Tam Coc, Mua Cave, and bay transfer pressure into one brittle day.',
            'evidence_moat' => 'Use route pressure, boat choice, base choice, and onward-transfer logic from the existing Ninh Binh cluster.',
            'internal_links' => [
                '/destinations/ninh-binh-travel-guide/',
                '/compare/ninh-binh-day-trip-vs-overnight/',
                '/compare/trang-an-vs-tam-coc/',
                '/plan/ninh-binh-to-ha-long-bay-transfer/',
            ],
        ],
        [
            'title' => 'Ha Long Bay Cruise Questions to Ask Before Booking',
            'slug' => 'ha-long-bay-cruise-questions-before-booking',
            'target_date' => '2026-08-07',
            'categories' => ['beaches-islands', 'transport-logistics'],
            'tags' => ['cruise-planning', 'premium-travel', 'family-travel', 'anti-spam-evergreen'],
            'intent' => 'Turn vague cruise shopping into a set of useful questions about port, cabin, weather, route, and transfer risk.',
            'reader_job' => 'Choose a bay experience without being led only by pretty photos and star ratings.',
            'evidence_moat' => 'Use cruise decision questions, port logic, Lan Ha vs Ha Long fit, Bai Tu Long trade-offs, and family comfort checks.',
            'internal_links' => [
                '/destinations/ha-long-bay-travel-guide/',
                '/compare/ha-long-bay-vs-lan-ha-bay/',
                '/destinations/bai-tu-long-bay-guide/',
                '/plan/ninh-binh-to-ha-long-bay-transfer/',
            ],
        ],
        [
            'title' => 'Vietnam Food Safety and Street Food Etiquette for First-Timers',
            'slug' => 'vietnam-food-safety-street-food-etiquette',
            'target_date' => '2026-08-08',
            'categories' => ['food-culture', 'practicalities'],
            'tags' => ['street-food', 'first-time-vietnam', 'international-travelers', 'anti-spam-evergreen'],
            'intent' => 'Help travelers enjoy street food without turning the article into fear content or generic dish list spam.',
            'reader_job' => 'Know how to choose busy stalls, order politely, manage hygiene risk, and keep a flexible meal plan.',
            'evidence_moat' => 'Use behavior-based judgment, practical etiquette, city rhythm, and health disclaimer discipline.',
            'internal_links' => [
                '/plan/health-travel-insurance-vietnam/',
                '/plan/safety-scams-vietnam/',
                '/destinations/hanoi-travel-guide/',
                '/destinations/ho-chi-minh-city-travel-guide/',
            ],
        ],
        [
            'title' => 'Where to Stay in Vietnam: City Base Decisions Before Choosing Hotels',
            'slug' => 'where-to-stay-in-vietnam-base-decisions',
            'target_date' => '2026-08-09',
            'categories' => ['hotels-neighborhoods', 'travel-planning'],
            'tags' => ['hotel-base', 'premium-travel', 'family-travel', 'anti-spam-evergreen'],
            'intent' => 'Create a country-level stay-area framework that sends readers to the right city/neighborhood guide.',
            'reader_job' => 'Decide which base job matters most before comparing individual hotels.',
            'evidence_moat' => 'Use city role, arrival/departure logistics, family needs, food access, quiet nights, and transfer pickup friction.',
            'internal_links' => [
                '/destinations/where-to-stay-in-hanoi/',
                '/destinations/where-to-stay-in-ho-chi-minh-city/',
                '/destinations/where-to-stay-in-ninh-binh/',
                '/compare/da-nang-vs-hoi-an/',
            ],
        ],
        [
            'title' => 'Vietnam Rainy Season Travel: How to Build a Flexible Route',
            'slug' => 'vietnam-rainy-season-flexible-route',
            'target_date' => '2026-08-10',
            'categories' => ['seasonal-travel', 'itineraries'],
            'tags' => ['rainy-season', 'route-planning', 'first-time-vietnam', 'anti-spam-evergreen'],
            'intent' => 'Help travelers plan around regional weather without pretending one Vietnam rainy season controls the whole country.',
            'reader_job' => 'Build buffers, cancellation logic, and alternate indoor/city/culture days into the route.',
            'evidence_moat' => 'Use region-by-region weather framing, route flexibility, cancellation terms, and internal season/itinerary links.',
            'internal_links' => [
                '/plan/best-time-to-visit-vietnam/',
                '/compare/north-central-south-vietnam/',
                '/itineraries/14-days-in-vietnam/',
                '/plan/transport-within-vietnam/',
            ],
        ],
    ];
}

function vg_post_editorial_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_post_editorial_term_id(string $taxonomy, string $name, string $slug, string $description = ''): int
{
    $existing = term_exists($slug, $taxonomy);

    if (is_array($existing) && isset($existing['term_id'])) {
        return (int) $existing['term_id'];
    }

    if (is_int($existing)) {
        return $existing;
    }

    $result = wp_insert_term(
        $name,
        $taxonomy,
        [
            'slug' => $slug,
            'description' => $description,
        ]
    );

    if (is_wp_error($result)) {
        vg_post_editorial_fail("Could not create {$taxonomy} term {$slug}: " . $result->get_error_message());
    }

    return (int) $result['term_id'];
}

function vg_post_editorial_author_id(): int
{
    $users = get_users(
        [
            'role' => 'Administrator',
            'number' => 1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'fields' => ['ID'],
        ]
    );

    if (isset($users[0]->ID)) {
        return (int) $users[0]->ID;
    }

    return 1;
}

function vg_post_editorial_find_post_by_slug(string $slug): ?WP_Post
{
    $posts = get_posts(
        [
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'future', 'pending', 'private'],
            'name' => $slug,
            'posts_per_page' => 1,
        ]
    );

    return $posts[0] ?? null;
}

function vg_post_editorial_brief_content(array $brief): string
{
    $links = '';

    foreach ($brief['internal_links'] as $link) {
        $links .= '<li>' . esc_html($link) . '</li>';
    }

    return '<!-- wp:paragraph -->'
        . '<p><strong>Draft status:</strong> This is an editorial brief, not a finished article. Expand, source-check, add images, tune Rank Math, and review in WordPress Admin before publishing.</p>'
        . '<!-- /wp:paragraph -->'
        . "\n\n"
        . '<!-- wp:heading --><h2 class="wp-block-heading">Editorial brief</h2><!-- /wp:heading -->'
        . "\n"
        . '<!-- wp:html -->'
        . '<table class="vg-decision-table vg-post-brief-table"><tbody>'
        . '<tr><th>Search intent</th><td>' . esc_html($brief['intent']) . '</td></tr>'
        . '<tr><th>Reader job</th><td>' . esc_html($brief['reader_job']) . '</td></tr>'
        . '<tr><th>Evidence moat</th><td>' . esc_html($brief['evidence_moat']) . '</td></tr>'
        . '<tr><th>Target publish date</th><td>' . esc_html($brief['target_date']) . '</td></tr>'
        . '</tbody></table>'
        . '<!-- /wp:html -->'
        . "\n\n"
        . '<!-- wp:heading --><h2 class="wp-block-heading">Internal link plan</h2><!-- /wp:heading -->'
        . "\n"
        . '<!-- wp:html --><ul class="vg-source-list">' . $links . '</ul><!-- /wp:html -->'
        . "\n\n"
        . '<!-- wp:heading --><h2 class="wp-block-heading">Publish gate</h2><!-- /wp:heading -->'
        . "\n"
        . '<!-- wp:list --><ul>'
        . '<li>Write the article as original judgment, not a rewritten search-result summary.</li>'
        . '<li>Add a concise verdict in the first screen.</li>'
        . '<li>Add at least one decision table or route framework.</li>'
        . '<li>Add image credits and useful internal links.</li>'
        . '<li>Set Rank Math title, description, focus keyword, and schema review before publishing.</li>'
        . '<li>Clear this brief language before moving the post from draft to publish.</li>'
        . '</ul><!-- /wp:list -->';
}

$category_ids_by_slug = [];

foreach (vg_post_editorial_categories() as $category) {
    $category_ids_by_slug[$category['slug']] = vg_post_editorial_term_id('category', $category['name'], $category['slug'], $category['description']);
}

$tag_ids_by_slug = [];

foreach (vg_post_editorial_tags() as $slug => $name) {
    $tag_ids_by_slug[$slug] = vg_post_editorial_term_id('post_tag', $name, $slug);
}

$author_id = vg_post_editorial_author_id();
$created = 0;
$preserved = 0;
$today = wp_date('F j, Y');

foreach (vg_post_editorial_briefs() as $brief) {
    $existing = vg_post_editorial_find_post_by_slug($brief['slug']);

    if ($existing instanceof WP_Post) {
        if ($existing->post_status !== 'draft') {
            vg_post_editorial_fail("Editorial brief slug already exists but is not draft: {$brief['slug']} (#{$existing->ID}, status {$existing->post_status})");
        }

        $post_id = (int) $existing->ID;
        $preserved++;
    } else {
        $post_id = wp_insert_post(
            [
                'post_type'    => 'post',
                'post_title'   => $brief['title'],
                'post_name'    => $brief['slug'],
                'post_status'  => 'draft',
                'post_author'  => $author_id,
                'post_content' => vg_post_editorial_brief_content($brief),
                'post_excerpt' => $brief['intent'],
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ],
            true
        );

        if (is_wp_error($post_id)) {
            vg_post_editorial_fail("Could not create editorial brief {$brief['slug']}: " . $post_id->get_error_message());
        }

        $post_id = (int) $post_id;
        update_post_meta($post_id, 'rank_math_title', $brief['title']);
        update_post_meta($post_id, 'rank_math_description', $brief['intent']);
        update_post_meta($post_id, 'rank_math_focus_keyword', str_replace('-', ' ', $brief['slug']));
        update_post_meta($post_id, 'vg_editorial_brief_status', 'brief');
        update_post_meta($post_id, 'vg_editorial_target_publish_date', $brief['target_date']);
        update_post_meta($post_id, 'vg_editorial_reader_job', $brief['reader_job']);
        update_post_meta($post_id, 'vg_editorial_evidence_moat', $brief['evidence_moat']);
        update_post_meta($post_id, 'vg_content_owner', 'wp_admin');
        update_post_meta($post_id, 'vg_automation_lock', 'locked');
        update_post_meta($post_id, 'vg_last_manual_review', $today);
        update_post_meta($post_id, 'vg_admin_first_notes', 'Native WordPress Post editorial brief created for manual expansion. Do not publish until the brief language is replaced with complete sourced article content.');
        $created++;
    }

    $category_ids = [];

    foreach ($brief['categories'] as $category_slug) {
        if (! isset($category_ids_by_slug[$category_slug])) {
            vg_post_editorial_fail("Missing category ID for {$category_slug}");
        }

        $category_ids[] = $category_ids_by_slug[$category_slug];
    }

    $tag_ids = [];

    foreach ($brief['tags'] as $tag_slug) {
        if (! isset($tag_ids_by_slug[$tag_slug])) {
            vg_post_editorial_fail("Missing tag ID for {$tag_slug}");
        }

        $tag_ids[] = $tag_ids_by_slug[$tag_slug];
    }

    wp_set_object_terms($post_id, $category_ids, 'category', false);
    wp_set_object_terms($post_id, $tag_ids, 'post_tag', false);
}

WP_CLI::success("WordPress post editorial system ready. Created {$created} draft brief(s); preserved {$preserved} existing draft brief(s).");

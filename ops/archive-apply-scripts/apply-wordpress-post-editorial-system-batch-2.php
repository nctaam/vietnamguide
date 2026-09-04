<?php
/**
 * Create the second native WordPress Posts evergreen draft-brief batch.
 *
 * Run from the WordPress root:
 * wp eval-file ops/apply-wordpress-post-editorial-system-batch-2.php --allow-root
 *
 * No posts are published or scheduled by this script. It creates draft
 * editorial briefs only, then locks them for WordPress Admin editing.
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_post_editorial_batch2_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_post_editorial_batch2_categories(): array
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

function vg_post_editorial_batch2_tags(): array
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
        'month-by-month' => 'Month-by-month',
        'tet-travel' => 'Tet travel',
        'city-comparison' => 'City comparison',
        'heritage-travel' => 'Heritage travel',
        'beach-planning' => 'Beach planning',
        'cave-planning' => 'Cave planning',
        'mountain-planning' => 'Mountain planning',
    ];
}

function vg_post_editorial_batch2_briefs(): array
{
    return [
        [
            'title' => 'Vietnam in December: Weather, Routes and What to Book Early',
            'slug' => 'vietnam-in-december',
            'target_date' => '2026-08-11',
            'categories' => ['seasonal-travel', 'travel-planning'],
            'tags' => ['month-by-month', 'first-time-vietnam', 'route-planning', 'anti-spam-evergreen'],
            'intent' => 'Help international travelers decide whether December should be a north-culture route, central-coast risk plan, south/island route, or mixed itinerary.',
            'reader_job' => 'Choose the right Vietnam route for December before booking hotels, bay cruises, beaches, or domestic flights.',
            'evidence_moat' => 'Use regional weather judgment, holiday crowd logic, cruise/beach trade-offs, and route alternatives instead of a generic weather paragraph.',
            'what_not_to_write' => 'Do not promise perfect weather nationwide, overstate beach certainty, or make December a keyword rewrite of the main best-time guide.',
            'source_plan' => [
                'Vietnam.travel weather and climate page for broad regional December context.',
                'Vietnam.travel monthly guide for official seasonal framing.',
                'National weather and WMO city forecast sources for live-check discipline.',
                'Internal Best Time, 10 Days, Best Beaches, Ha Long, Phu Quoc, and Central Vietnam route pages.',
            ],
            'image_plan' => [
                'Hanoi or Ninh Binh cool-season hero, text credit only.',
                'Southern beach or island support image if the article compares winter-sun options.',
                'Central coast rain/heritage image only when it helps explain risk.',
            ],
            'internal_links' => [
                '/plan/best-time-to-visit-vietnam/',
                '/compare/north-central-south-vietnam/',
                '/itineraries/10-days-in-vietnam/',
                '/destinations/best-beaches-in-vietnam/',
            ],
        ],
        [
            'title' => 'Vietnam in January: Best Routes, Weather and Tet Watchouts',
            'slug' => 'vietnam-in-january',
            'target_date' => '2026-08-12',
            'categories' => ['seasonal-travel', 'travel-planning'],
            'tags' => ['month-by-month', 'tet-travel', 'first-time-vietnam', 'anti-spam-evergreen'],
            'intent' => 'Help travelers decide whether January suits their route, budget, crowd tolerance, and possible pre-Tet logistics.',
            'reader_job' => 'Build a January route that handles cool north conditions, central/south trade-offs, and holiday lead-up pressure.',
            'evidence_moat' => 'Separate evergreen January route logic from exact Tet dates, then require current holiday checks before booking.',
            'what_not_to_write' => 'Do not treat January as identical to December or February, and do not publish fixed holiday advice without an update note.',
            'source_plan' => [
                'Vietnam.travel weather and climate page.',
                'Vietnam.travel Tet guide for cultural and travel context.',
                'Official public-holiday announcement when available for the relevant travel year.',
                'Internal Tet, Best Time, packing, airport-arrival, and cost pages.',
            ],
            'image_plan' => [
                'Northern city cool-season image.',
                'Tet preparation or flower-market image only with clear credit/source record.',
                'Beach image only if the section explains who should bias south.',
            ],
            'internal_links' => [
                '/plan/best-time-to-visit-vietnam/',
                '/travel-planning/vietnam-rainy-season-flexible-route/',
                '/costs/vietnam-travel-cost/',
                '/travel-planning/vietnam-airport-arrival-checklist/',
            ],
        ],
        [
            'title' => 'Vietnam in February: Tet Timing, Weather and Route Ideas',
            'slug' => 'vietnam-in-february',
            'target_date' => '2026-08-13',
            'categories' => ['seasonal-travel', 'travel-planning'],
            'tags' => ['month-by-month', 'tet-travel', 'route-planning', 'anti-spam-evergreen'],
            'intent' => 'Help February travelers decide whether to work around Tet, lean into cultural travel, or choose a less fragile route.',
            'reader_job' => 'Plan February Vietnam without being surprised by holiday closures, transport demand, or regional weather differences.',
            'evidence_moat' => 'Use Tet timing logic, reopen/recovery days, route buffers, and regional weather split instead of a generic month list.',
            'what_not_to_write' => 'Do not make Tet sound like a normal operating week or give undated closure claims.',
            'source_plan' => [
                'Vietnam.travel Tet articles for cultural context.',
                'Vietnam.travel monthly guide and weather page.',
                'Transport operator checks close to travel for holiday schedules.',
                'Internal Best Time, Tet, transport, and arrival pages.',
            ],
            'image_plan' => [
                'Tet street or flower display image with source credit.',
                'Hoi An/Hue heritage image for culture-first routing.',
                'Southern beach image only when comparing February winter-sun options.',
            ],
            'internal_links' => [
                '/plan/best-time-to-visit-vietnam/',
                '/plan/transport-within-vietnam/',
                '/itineraries/14-days-in-vietnam/',
                '/destinations/unesco-heritage-sites-vietnam/',
            ],
        ],
        [
            'title' => 'Tet in Vietnam Travel Guide: What International Visitors Should Know',
            'slug' => 'tet-in-vietnam-travel-guide',
            'target_date' => '2026-08-14',
            'categories' => ['seasonal-travel', 'food-culture', 'travel-planning'],
            'tags' => ['tet-travel', 'international-travelers', 'route-planning', 'anti-spam-evergreen'],
            'intent' => 'Explain whether travelers should visit Vietnam during Tet, avoid the peak days, or design a route that embraces the holiday.',
            'reader_job' => 'Decide what to book early, what to leave flexible, and which expectations change around Lunar New Year.',
            'evidence_moat' => 'Provide original traveler decision value: before/during/after Tet operating rhythm, closures, transport pressure, city mood, and respectful cultural behavior.',
            'what_not_to_write' => 'Do not turn Tet into a copied festival explainer, exact-date evergreen trap, or fear-based closure article.',
            'source_plan' => [
                'Vietnam.travel Tet holiday guide and Tet tradition article.',
                'Official public-holiday calendar or government announcement for the year being updated.',
                'Airline, railway, and local attraction checks only near the update date.',
                'Internal month-by-month, transport, airport-arrival, money, and food-safety pages.',
            ],
            'image_plan' => [
                'Tet flower market or family/public celebration image with human context.',
                'Street decoration image that supports practical crowd/closure advice.',
                'Food image only if it explains etiquette and not a dish list.',
            ],
            'internal_links' => [
                '/plan/best-time-to-visit-vietnam/',
                '/plan/transport-within-vietnam/',
                '/travel-planning/vietnam-food-safety-street-food-etiquette/',
                '/travel-planning/vietnam-airport-arrival-checklist/',
            ],
        ],
        [
            'title' => 'Best Vietnam Cities for First-Time Visitors: Which Base Fits Your Route?',
            'slug' => 'best-vietnam-cities-for-first-time-visitors',
            'target_date' => '2026-08-15',
            'categories' => ['destinations', 'travel-planning'],
            'tags' => ['first-time-vietnam', 'city-comparison', 'route-planning', 'anti-spam-evergreen'],
            'intent' => 'Help travelers choose city bases by route role, arrival logic, food, culture, beaches, day trips, and onward movement.',
            'reader_job' => 'Pick fewer better city bases instead of treating every famous city as mandatory.',
            'evidence_moat' => 'Rank cities by traveler job and route consequence, with skip logic and base roles instead of a generic best-cities list.',
            'what_not_to_write' => 'Do not publish a popularity ranking without route use cases, or duplicate Best Places to Visit in Vietnam.',
            'source_plan' => [
                'Vietnam.travel city and regional destination pages.',
                'Internal published city/destination guides and stay-area pages.',
                'Airport/transport pages for arrival and onward-movement friction.',
                'GSC query evidence for city/base modifiers when available.',
            ],
            'image_plan' => [
                'Hanoi, Hoi An/Da Nang, Hue, Ho Chi Minh City image set with text credits.',
                'One photo per city role, not decorative collage.',
                'Use captions to explain what the base solves.',
            ],
            'internal_links' => [
                '/destinations/best-places-to-visit-in-vietnam/',
                '/compare/north-central-south-vietnam/',
                '/destinations/hanoi-travel-guide/',
                '/destinations/ho-chi-minh-city-travel-guide/',
            ],
        ],
        [
            'title' => 'Hanoi vs Ho Chi Minh City: Which Should You Visit First?',
            'slug' => 'hanoi-vs-ho-chi-minh-city',
            'target_date' => '2026-08-16',
            'categories' => ['destinations', 'travel-planning'],
            'tags' => ['city-comparison', 'first-time-vietnam', 'route-planning', 'anti-spam-evergreen'],
            'intent' => 'Compare Hanoi and Ho Chi Minh City as first or final city bases for international visitors.',
            'reader_job' => 'Choose the city that best fits arrival, route direction, food, culture, weather, day trips, and departure plan.',
            'evidence_moat' => 'Use direct verdicts by trip type, airport routing, nearby day trips, first-night comfort, and region-spine consequences.',
            'what_not_to_write' => 'Do not reduce the comparison to north culture vs south modernity, and do not pretend one city is universally better.',
            'source_plan' => [
                'Vietnam.travel Hanoi and Ho Chi Minh City regional/destination pages.',
                'Internal Hanoi guide, HCMC guide, arrival, stay, transport, and itinerary pages.',
                'Airport and transfer source checks when logistics claims are added.',
                'Weather source checks for month-sensitive recommendations.',
            ],
            'image_plan' => [
                'One strong Hanoi street/lake/Old Quarter image.',
                'One strong Ho Chi Minh City street/landmark/river image.',
                'Captions explain first-night and route-role differences.',
            ],
            'internal_links' => [
                '/destinations/hanoi-travel-guide/',
                '/destinations/ho-chi-minh-city-travel-guide/',
                '/compare/north-central-south-vietnam/',
                '/travel-planning/where-to-stay-in-vietnam-base-decisions/',
            ],
        ],
        [
            'title' => 'Hoi An Ancient Town Guide: When to Stay, Visit or Skip',
            'slug' => 'hoi-an-ancient-town-guide',
            'target_date' => '2026-08-17',
            'categories' => ['destinations', 'heritage-culture'],
            'tags' => ['heritage-travel', 'first-time-vietnam', 'premium-travel', 'anti-spam-evergreen'],
            'intent' => 'Help travelers decide how Hoi An Ancient Town should fit into a central Vietnam route.',
            'reader_job' => 'Choose day, evening, overnight, or beach-plus-town pacing without turning Hoi An into a lantern checklist.',
            'evidence_moat' => 'Use time-of-day, crowd, heritage, food, tailoring, beach, and Da Nang/Hue route logic instead of a generic attraction list.',
            'what_not_to_write' => 'Do not over-romanticize lantern photos or duplicate the Hoi An things-to-do page.',
            'source_plan' => [
                'UNESCO Hoi An Ancient Town listing.',
                'Vietnam.travel Hoi An and central Vietnam resources.',
                'Local heritage/ticketing pages if current visitor rules are added.',
                'Internal Hoi An, Da Nang, Hue, UNESCO, and central-route pages.',
            ],
            'image_plan' => [
                'Ancient Town street/river hero image with text credit.',
                'Day vs evening image pair if available.',
                'Map/route graphic can be AI-generated if clearly labeled as illustration.',
            ],
            'internal_links' => [
                '/destinations/best-things-to-do-in-hoi-an/',
                '/compare/da-nang-vs-hoi-an/',
                '/compare/hoi-an-vs-hue/',
                '/destinations/unesco-heritage-sites-vietnam/',
            ],
        ],
        [
            'title' => 'Hue Imperial City Guide: How to Visit Without Rushing',
            'slug' => 'hue-imperial-city-guide',
            'target_date' => '2026-08-18',
            'categories' => ['destinations', 'heritage-culture'],
            'tags' => ['heritage-travel', 'route-planning', 'first-time-vietnam', 'anti-spam-evergreen'],
            'intent' => 'Help travelers decide whether Hue Imperial City needs a dedicated stay, a focused half-day, or a pass-through role.',
            'reader_job' => 'Plan Hue heritage time by route pace, heat, tombs, food, and onward central Vietnam movement.',
            'evidence_moat' => 'Use palace/tomb pacing, heat avoidance, guide value, route priority, and Hoi An/Da Nang trade-offs.',
            'what_not_to_write' => 'Do not create a copied monument history page or a thin checklist of gates and tombs.',
            'source_plan' => [
                'UNESCO Complex of Hue Monuments listing.',
                'Hue Monuments Conservation Centre or official local visitor source.',
                'Vietnam.travel Hue resources.',
                'Internal Hue, Hoi An vs Hue, UNESCO, and central-route pages.',
            ],
            'image_plan' => [
                'Imperial City gate/citadel hero image.',
                'Tomb or Perfume River image only if it supports route pacing.',
                'Captions explain time and heat logic.',
            ],
            'internal_links' => [
                '/destinations/best-things-to-do-in-hue/',
                '/compare/hoi-an-vs-hue/',
                '/destinations/unesco-heritage-sites-vietnam/',
                '/compare/da-nang-vs-hoi-an/',
            ],
        ],
        [
            'title' => 'Da Nang Beaches Guide: My Khe, Non Nuoc or Hoi An Coast?',
            'slug' => 'da-nang-beaches-guide',
            'target_date' => '2026-08-19',
            'categories' => ['beaches-islands', 'destinations'],
            'tags' => ['beach-planning', 'first-time-vietnam', 'route-planning', 'anti-spam-evergreen'],
            'intent' => 'Help travelers choose the best central-coast beach base around Da Nang and Hoi An for their route.',
            'reader_job' => 'Decide whether My Khe, Non Nuoc, Son Tra edge, An Bang, or a Hoi An town base makes more sense.',
            'evidence_moat' => 'Use beach-base decision logic, season/weather, airport access, food, family comfort, hotel style, and Hoi An/Da Nang trade-offs.',
            'what_not_to_write' => 'Do not make another broad Best Beaches list or rank beaches only by prettiness.',
            'source_plan' => [
                'Vietnam.travel Da Nang and central coast resources.',
                'Da Nang tourism or city beach/visitor source when available.',
                'Weather and sea-condition live checks close to travel.',
                'Internal Da Nang, Best Beaches, Da Nang vs Hoi An, and Hoi An pages.',
            ],
            'image_plan' => [
                'My Khe or Da Nang beach hero image with text credit.',
                'Hoi An coast comparison image if explaining base trade-offs.',
                'Use beach photos as proof of base feel, not decorative background.',
            ],
            'internal_links' => [
                '/destinations/da-nang-travel-guide/',
                '/compare/da-nang-vs-hoi-an/',
                '/destinations/best-beaches-in-vietnam/',
                '/destinations/best-things-to-do-in-hoi-an/',
            ],
        ],
        [
            'title' => 'Mekong Delta Overnight vs Day Trip: Which Is Worth It?',
            'slug' => 'mekong-delta-overnight-vs-day-trip',
            'target_date' => '2026-08-20',
            'categories' => ['destinations', 'transport-logistics'],
            'tags' => ['route-planning', 'first-time-vietnam', 'family-travel', 'anti-spam-evergreen'],
            'intent' => 'Help Ho Chi Minh City travelers decide whether the Mekong Delta deserves a day trip, one night, or a deeper route.',
            'reader_job' => 'Choose depth, comfort, market timing, transfer effort, and southern-route value before booking a tour.',
            'evidence_moat' => 'Use morning-market timing, Ben Tre/Can Tho role split, homestay comfort, private-transfer value, and skip logic.',
            'what_not_to_write' => 'Do not turn the Delta into a generic floating-market photo promise or imply every day trip delivers the same depth.',
            'source_plan' => [
                'Vietnam.travel Mekong Delta guides, Can Tho page, and southern Vietnam resources.',
                'Local market/tour operator checks close to travel if exact timing is mentioned.',
                'Internal HCMC, Mekong Delta, day-trip, transport, and cost pages.',
                'GSC query evidence for overnight/day-trip modifiers when available.',
            ],
            'image_plan' => [
                'River/canal image showing Delta texture.',
                'Can Tho or Ben Tre support image when explaining overnight value.',
                'Avoid staged floating-market claims unless source and timing are verified.',
            ],
            'internal_links' => [
                '/destinations/mekong-delta-travel-guide/',
                '/destinations/ho-chi-minh-city-travel-guide/',
                '/destinations/best-day-trips-from-ho-chi-minh-city/',
                '/costs/vietnam-travel-cost/',
            ],
        ],
        [
            'title' => 'Phong Nha Travel Guide: Caves, Seasons and Route Fit',
            'slug' => 'phong-nha-travel-guide',
            'target_date' => '2026-08-21',
            'categories' => ['destinations', 'heritage-culture'],
            'tags' => ['cave-planning', 'heritage-travel', 'route-planning', 'anti-spam-evergreen'],
            'intent' => 'Help travelers decide whether Phong Nha-Ke Bang is worth the transfer detour for caves, nature, and central Vietnam depth.',
            'reader_job' => 'Choose the right cave experience, season, length of stay, and route connection before booking.',
            'evidence_moat' => 'Use cave-access seasonality, comfort/risk split, tour intensity, UNESCO context, and Hue/Ninh Binh/central-route trade-offs.',
            'what_not_to_write' => 'Do not write a cave-superlative list without booking, season, safety, and route consequences.',
            'source_plan' => [
                'UNESCO Phong Nha-Ke Bang listing and 2025 transboundary update context.',
                'Vietnam.travel Phong Nha guide and national park coverage.',
                'Official park or operator pages for cave access, closures, and safety when writing exact details.',
                'Internal UNESCO, Central Vietnam, transport, health/insurance, and Ninh Binh comparison pages.',
            ],
            'image_plan' => [
                'Cave or karst hero image with verified license/credit.',
                'Town/river/jungle image if it explains route feel.',
                'AI diagram allowed only for route/season decision map, not cave proof.',
            ],
            'internal_links' => [
                '/destinations/unesco-heritage-sites-vietnam/',
                '/compare/north-central-south-vietnam/',
                '/plan/transport-within-vietnam/',
                '/plan/health-travel-insurance-vietnam/',
            ],
        ],
        [
            'title' => 'Sapa vs Ha Giang: Terraces, Loop Roads or Softer Mountain Travel?',
            'slug' => 'sapa-vs-ha-giang',
            'target_date' => '2026-08-22',
            'categories' => ['destinations', 'travel-planning'],
            'tags' => ['mountain-planning', 'first-time-vietnam', 'route-planning', 'anti-spam-evergreen'],
            'intent' => 'Help travelers choose Sapa, Ha Giang, both, or neither based on scenery, comfort, safety, season, and route pressure.',
            'reader_job' => 'Decide whether northern mountains belong in the itinerary and which style of mountain travel fits.',
            'evidence_moat' => 'Compare terraces/treks versus loop roads through safety, weather, license/insurance, transfer load, and traveler comfort.',
            'what_not_to_write' => 'Do not romanticize risky road travel, reduce Sapa to crowds, or publish motorbike advice without safety and insurance caveats.',
            'source_plan' => [
                'Vietnam.travel northern Vietnam, Sapa, Ha Giang, and mountain-route resources.',
                'Weather and road-condition checks close to travel.',
                'Health/insurance and safety pages for self-drive/easy-rider risk framing.',
                'Internal Hanoi, Best Time, transport, insurance, and route pages.',
            ],
            'image_plan' => [
                'Sapa or rice-terrace image with text credit.',
                'Ha Giang road/karst image with text credit.',
                'Captions must explain comfort, route pressure, and safety differences.',
            ],
            'internal_links' => [
                '/destinations/hanoi-travel-guide/',
                '/plan/best-time-to-visit-vietnam/',
                '/plan/transport-within-vietnam/',
                '/plan/health-travel-insurance-vietnam/',
            ],
        ],
    ];
}

function vg_post_editorial_batch2_term_id(string $taxonomy, string $name, string $slug, string $description = ''): int
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
        vg_post_editorial_batch2_fail("Could not create {$taxonomy} term {$slug}: " . $result->get_error_message());
    }

    return (int) $result['term_id'];
}

function vg_post_editorial_batch2_author_id(): int
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

function vg_post_editorial_batch2_find_post_by_slug(string $slug): ?WP_Post
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

function vg_post_editorial_batch2_list_items(array $items): string
{
    $html = '';

    foreach ($items as $item) {
        $html .= '<li>' . esc_html($item) . '</li>';
    }

    return $html;
}

function vg_post_editorial_batch2_brief_content(array $brief): string
{
    $links = vg_post_editorial_batch2_list_items($brief['internal_links']);
    $sources = vg_post_editorial_batch2_list_items($brief['source_plan']);
    $images = vg_post_editorial_batch2_list_items($brief['image_plan']);

    return '<!-- wp:paragraph -->'
        . '<p><strong>Draft status:</strong> This is a Batch 2 evergreen editorial brief, not a finished article. Expand inside WordPress Admin, source-check current facts, add useful images, tune Rank Math, and review manually before publishing.</p>'
        . '<!-- /wp:paragraph -->'
        . "\n\n"
        . '<!-- wp:heading --><h2 class="wp-block-heading">Editorial brief</h2><!-- /wp:heading -->'
        . "\n"
        . '<!-- wp:html -->'
        . '<table class="vg-decision-table vg-post-brief-table"><tbody>'
        . '<tr><th>Search intent</th><td>' . esc_html($brief['intent']) . '</td></tr>'
        . '<tr><th>Reader job</th><td>' . esc_html($brief['reader_job']) . '</td></tr>'
        . '<tr><th>Evidence moat</th><td>' . esc_html($brief['evidence_moat']) . '</td></tr>'
        . '<tr><th>What not to write</th><td>' . esc_html($brief['what_not_to_write']) . '</td></tr>'
        . '<tr><th>Target publish date</th><td>' . esc_html($brief['target_date']) . '</td></tr>'
        . '</tbody></table>'
        . '<!-- /wp:html -->'
        . "\n\n"
        . '<!-- wp:heading --><h2 class="wp-block-heading">Internal link plan</h2><!-- /wp:heading -->'
        . "\n"
        . '<!-- wp:html --><ul class="vg-source-list">' . $links . '</ul><!-- /wp:html -->'
        . "\n\n"
        . '<!-- wp:heading --><h2 class="wp-block-heading">Sources to check</h2><!-- /wp:heading -->'
        . "\n"
        . '<!-- wp:html --><ul class="vg-source-list">' . $sources . '</ul><!-- /wp:html -->'
        . "\n\n"
        . '<!-- wp:heading --><h2 class="wp-block-heading">Image plan</h2><!-- /wp:heading -->'
        . "\n"
        . '<!-- wp:html --><ul class="vg-source-list">' . $images . '</ul><!-- /wp:html -->'
        . "\n\n"
        . '<!-- wp:heading --><h2 class="wp-block-heading">Publish gate</h2><!-- /wp:heading -->'
        . "\n"
        . '<!-- wp:list --><ul>'
        . '<li>Replace all brief language with an original article before publishing.</li>'
        . '<li>Add a verdict band in the first screen and a clear best-for / skip-if section.</li>'
        . '<li>Add a decision table, route matrix, seasonal matrix, or friction map.</li>'
        . '<li>Use source-rich research but keep the article body linkout-light.</li>'
        . '<li>Add image credits as text and store full source/license records in the source trail.</li>'
        . '<li>Set Rank Math title, description, focus keyword, and schema manually.</li>'
        . '<li>Add update log, author/editor accountability, and date-sensitive caveats.</li>'
        . '<li>Confirm this article answers a distinct traveler decision and is not a keyword variant.</li>'
        . '</ul><!-- /wp:list -->';
}

$category_ids_by_slug = [];

foreach (vg_post_editorial_batch2_categories() as $category) {
    $category_ids_by_slug[$category['slug']] = vg_post_editorial_batch2_term_id('category', $category['name'], $category['slug'], $category['description']);
}

$tag_ids_by_slug = [];

foreach (vg_post_editorial_batch2_tags() as $slug => $name) {
    $tag_ids_by_slug[$slug] = vg_post_editorial_batch2_term_id('post_tag', $name, $slug);
}

$author_id = vg_post_editorial_batch2_author_id();
$created = 0;
$preserved = 0;
$today = wp_date('F j, Y');
$batch = 'batch-2-evergreen-planning';

foreach (vg_post_editorial_batch2_briefs() as $brief) {
    $existing = vg_post_editorial_batch2_find_post_by_slug($brief['slug']);

    if ($existing instanceof WP_Post) {
        if ($existing->post_status !== 'draft') {
            vg_post_editorial_batch2_fail("Editorial brief slug already exists but is not draft: {$brief['slug']} (#{$existing->ID}, status {$existing->post_status})");
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
                'post_content' => vg_post_editorial_batch2_brief_content($brief),
                'post_excerpt' => $brief['intent'],
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ],
            true
        );

        if (is_wp_error($post_id)) {
            vg_post_editorial_batch2_fail("Could not create editorial brief {$brief['slug']}: " . $post_id->get_error_message());
        }

        $post_id = (int) $post_id;
        update_post_meta($post_id, 'rank_math_title', $brief['title']);
        update_post_meta($post_id, 'rank_math_description', $brief['intent']);
        update_post_meta($post_id, 'rank_math_focus_keyword', str_replace('-', ' ', $brief['slug']));
        update_post_meta($post_id, 'vg_editorial_brief_status', 'brief');
        update_post_meta($post_id, 'vg_editorial_batch', $batch);
        update_post_meta($post_id, 'vg_editorial_target_publish_date', $brief['target_date']);
        update_post_meta($post_id, 'vg_editorial_reader_job', $brief['reader_job']);
        update_post_meta($post_id, 'vg_editorial_evidence_moat', $brief['evidence_moat']);
        update_post_meta($post_id, 'vg_editorial_what_not_to_write', $brief['what_not_to_write']);
        update_post_meta($post_id, 'vg_editorial_sources_to_check', implode("\n", $brief['source_plan']));
        update_post_meta($post_id, 'vg_editorial_image_plan', implode("\n", $brief['image_plan']));
        update_post_meta($post_id, 'vg_content_owner', 'wp_admin');
        update_post_meta($post_id, 'vg_automation_lock', 'locked');
        update_post_meta($post_id, 'vg_last_manual_review', $today);
        update_post_meta($post_id, 'vg_admin_first_notes', 'Native WordPress Post editorial brief created for manual expansion. Do not publish until the brief language is replaced with complete sourced article content.');
        $created++;
    }

    $category_ids = [];

    foreach ($brief['categories'] as $category_slug) {
        if (! isset($category_ids_by_slug[$category_slug])) {
            vg_post_editorial_batch2_fail("Missing category ID for {$category_slug}");
        }

        $category_ids[] = $category_ids_by_slug[$category_slug];
    }

    $tag_ids = [];

    foreach ($brief['tags'] as $tag_slug) {
        if (! isset($tag_ids_by_slug[$tag_slug])) {
            vg_post_editorial_batch2_fail("Missing tag ID for {$tag_slug}");
        }

        $tag_ids[] = $tag_ids_by_slug[$tag_slug];
    }

    wp_set_object_terms($post_id, $category_ids, 'category', false);
    wp_set_object_terms($post_id, $tag_ids, 'post_tag', false);
}

WP_CLI::success("WordPress post editorial system batch 2 ready. Created {$created} draft brief(s); preserved {$preserved} existing draft brief(s).");

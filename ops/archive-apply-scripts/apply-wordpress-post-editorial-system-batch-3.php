<?php
/**
 * Create the third native WordPress Posts evergreen draft-brief batch.
 *
 * Run from the WordPress root:
 * wp eval-file ops/apply-wordpress-post-editorial-system-batch-3.php --allow-root
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

function vg_post_editorial_batch3_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_post_editorial_batch3_categories(): array
{
    return [
        ['name' => 'Travel Planning', 'slug' => 'travel-planning', 'description' => 'Evergreen Vietnam travel planning decisions for international visitors.'],
        ['name' => 'Itineraries', 'slug' => 'itineraries', 'description' => 'Route shapes, trip lengths, and pacing decisions.'],
        ['name' => 'Destinations', 'slug' => 'destinations', 'description' => 'Place-level decisions with route fit and skip logic.'],
        ['name' => 'Transport & Logistics', 'slug' => 'transport-logistics', 'description' => 'Transfers, airports, trains, roads, ferries, and arrival decisions.'],
        ['name' => 'Practicalities', 'slug' => 'practicalities', 'description' => 'Money, packing, safety, insurance, SIM, and pre-trip checks.'],
        ['name' => 'Hotels & Neighborhoods', 'slug' => 'hotels-neighborhoods', 'description' => 'Stay-area decisions before choosing specific accommodation.'],
        ['name' => 'Seasonal Travel', 'slug' => 'seasonal-travel', 'description' => 'Weather, holiday, and month-by-month planning.'],
    ];
}

function vg_post_editorial_batch3_tags(): array
{
    return [
        'first-time-vietnam' => 'First-time Vietnam',
        'international-travelers' => 'International travelers',
        'route-planning' => 'Route planning',
        'family-travel' => 'Family travel',
        'premium-travel' => 'Premium travel',
        'budget-planning' => 'Budget planning',
        'hotel-base' => 'Hotel base',
        'anti-spam-evergreen' => 'Anti-spam evergreen',
        'mountain-planning' => 'Mountain planning',
        'northern-vietnam' => 'Northern Vietnam',
        'transport-planning' => 'Transport planning',
        'trekking-planning' => 'Trekking planning',
        'safety-planning' => 'Safety planning',
        'rice-terraces' => 'Rice terraces',
        'weather-planning' => 'Weather planning',
    ];
}

function vg_post_editorial_batch3_briefs(): array
{
    return [
        [
            'title' => 'Sapa Travel Guide: Terraces, Trekking and Softer Mountain Travel',
            'slug' => 'sapa-travel-guide',
            'target_date' => '2026-08-23',
            'categories' => ['destinations', 'travel-planning'],
            'tags' => ['mountain-planning', 'trekking-planning', 'first-time-vietnam', 'anti-spam-evergreen'],
            'intent' => 'Help international travelers decide whether Sapa fits their Vietnam route, season, comfort needs, and walking appetite.',
            'reader_job' => 'Choose Sapa, shorten it, upgrade it, or skip it before booking trains, cabin buses, valley stays, guides, or onward northern moves.',
            'evidence_moat' => 'Use terrace timing, valley-stay logic, guide value, fog/rain comfort, crowd management, Hanoi transfer load, and Sapa vs Ha Giang trade-offs.',
            'what_not_to_write' => 'Do not write a generic things-to-do list, dismiss Sapa as only crowded, promise golden terraces year-round, or treat trekking as risk-free.',
            'source_plan' => [
                'Vietnam.travel Sapa and northern Vietnam pages for official destination framing.',
                'Vietnam.travel weather and transport pages for route and season context.',
                'NCHMF or other weather source for live-check discipline before publishing.',
                'Internal Hanoi, Best Time, Transport, Insurance, Sapa vs Ha Giang, and route-length pages.',
            ],
            'image_plan' => [
                'Photo-led Sapa terrace or Muong Hoa valley hero with text credit.',
                'Valley stay, guided walk, or market image only when it teaches route choice.',
                'Optional original map/decision graphic for town versus valley base logic.',
            ],
            'internal_links' => [
                '/destinations/hanoi-travel-guide/',
                '/plan/best-time-to-visit-vietnam/',
                '/plan/transport-within-vietnam/',
                '/plan/health-travel-insurance-vietnam/',
                '/destinations/sapa-vs-ha-giang/',
            ],
        ],
        [
            'title' => 'Ha Giang Loop Planning Guide: Safety, Scenery and Route Fit',
            'slug' => 'ha-giang-loop-planning-guide',
            'target_date' => '2026-08-24',
            'categories' => ['destinations', 'travel-planning'],
            'tags' => ['mountain-planning', 'safety-planning', 'route-planning', 'anti-spam-evergreen'],
            'intent' => 'Help travelers decide whether the Ha Giang Loop is worth the logistics, road exposure, safety requirements, and recovery time.',
            'reader_job' => 'Choose loop length, easy rider, private car, self-drive, or skip based on skill, license, insurance, weather, and route pressure.',
            'evidence_moat' => 'Center the road-risk decision, Dong Van karst context, weather exposure, operator vetting, license/insurance reality, and Hanoi return buffers.',
            'what_not_to_write' => 'Do not romanticize unsafe motorbike travel, imply no license is needed, or publish a route as universally safe.',
            'source_plan' => [
                'Vietnam.travel Ha Giang and Ha Giang Loop pages.',
                'Dong Van Karst Plateau Geopark and UNESCO/Global Geoparks references.',
                'Government travel advice and insurance/safety sources for road-risk framing.',
                'Internal Sapa vs Ha Giang, Health Insurance, Safety, Transport, and Hanoi pages.',
            ],
            'image_plan' => [
                'Ma Pi Leng or karst-road hero with license/source record.',
                'Dong Van or Tu San canyon support image for landscape identity.',
                'Original route-risk checklist graphic instead of a decorative map.',
            ],
            'internal_links' => [
                '/destinations/sapa-vs-ha-giang/',
                '/plan/health-travel-insurance-vietnam/',
                '/plan/safety-scams-vietnam/',
                '/plan/transport-within-vietnam/',
                '/itineraries/14-days-in-vietnam/',
            ],
        ],
        [
            'title' => 'Hanoi to Sapa Transport: Train, Cabin Bus or Private Car?',
            'slug' => 'hanoi-to-sapa-transport',
            'target_date' => '2026-08-25',
            'categories' => ['transport-logistics', 'travel-planning'],
            'tags' => ['transport-planning', 'mountain-planning', 'first-time-vietnam', 'anti-spam-evergreen'],
            'intent' => 'Help travelers choose the best Hanoi to Sapa transfer mode by sleep, budget, luggage, children, arrival time, and valley stay logistics.',
            'reader_job' => 'Choose overnight train, cabin bus, limousine, private car, or skip Sapa when transfer friction weakens the route.',
            'evidence_moat' => 'Compare transfer modes by traveler job, not price alone: sleep quality, pickup/dropoff, Lao Cai transfer, luggage, motion sickness, and next-day fatigue.',
            'what_not_to_write' => 'Do not publish fragile timetables or affiliate transport lists as evergreen facts.',
            'source_plan' => [
                'Vietnam.travel transport within Vietnam for mode context.',
                'Vietnam Railways official booking for live train check discipline.',
                'Operator/route checks only in the source trail when close to publication.',
                'Internal Sapa, Hanoi, Best Time, Packing, and Insurance pages.',
            ],
            'image_plan' => [
                'Original transfer comparison graphic for train, bus, private car.',
                'Station or mountain-road image only when it helps explain friction.',
                'Avoid operator logo collage or affiliate-style photos.',
            ],
            'internal_links' => [
                '/destinations/hanoi-travel-guide/',
                '/destinations/sapa-travel-guide/',
                '/plan/transport-within-vietnam/',
                '/travel-planning/what-to-pack-for-vietnam-region-season/',
            ],
        ],
        [
            'title' => 'Hanoi to Ha Giang Transport: Bus, Private Car or Staged Route?',
            'slug' => 'hanoi-to-ha-giang-transport',
            'target_date' => '2026-08-26',
            'categories' => ['transport-logistics', 'travel-planning'],
            'tags' => ['transport-planning', 'mountain-planning', 'safety-planning', 'anti-spam-evergreen'],
            'intent' => 'Help travelers choose a Hanoi to Ha Giang transfer plan that does not sabotage the loop with fatigue or fragile timing.',
            'reader_job' => 'Choose sleeper bus, day bus, private car, staged route, or skip based on loop start, recovery, luggage, and return timing.',
            'evidence_moat' => 'Treat the transfer as part of the risk plan: arrival fatigue, night-road comfort, onward loop timing, return to Hanoi, and flight-day protection.',
            'what_not_to_write' => 'Do not treat Ha Giang as a casual same-day add-on or publish exact bus schedules without live-check labels.',
            'source_plan' => [
                'Vietnam.travel transport and Ha Giang resources.',
                'Ha Giang route/operator checks near publication for current pickup/dropoff logic.',
                'Weather and safety sources for road-condition caveats.',
                'Internal Ha Giang Loop, Sapa vs Ha Giang, Hanoi, Insurance, and Safety pages.',
            ],
            'image_plan' => [
                'Original transfer risk timeline graphic.',
                'Road/pass image only if caption explains fatigue and return buffers.',
                'No operator screenshots unless used internally for editor checking.',
            ],
            'internal_links' => [
                '/destinations/hanoi-travel-guide/',
                '/destinations/ha-giang-loop-planning-guide/',
                '/destinations/sapa-vs-ha-giang/',
                '/plan/safety-scams-vietnam/',
            ],
        ],
        [
            'title' => 'Ha Giang Safety Guide: Easy Rider, Self-Drive and Insurance Reality',
            'slug' => 'ha-giang-safety-guide',
            'target_date' => '2026-08-27',
            'categories' => ['practicalities', 'travel-planning'],
            'tags' => ['safety-planning', 'mountain-planning', 'international-travelers', 'anti-spam-evergreen'],
            'intent' => 'Help travelers decide whether their Ha Giang plan is safe enough to book, modify, or cancel.',
            'reader_job' => 'Evaluate easy rider, self-drive, private car, helmets, license, insurance, weather, fatigue, luggage, and medical fallback.',
            'evidence_moat' => 'Make risk reduction the main content, with practical decision filters and source-backed insurance/road caveats.',
            'what_not_to_write' => 'Do not normalize illegal self-drive, guarantee safety, or treat easy-rider tours as automatically low-risk.',
            'source_plan' => [
                'Government travel advice for Vietnam road and motorcycle risk.',
                'Insurance policy wording checks and internal insurance guidance.',
                'NCHMF weather checks for mountain rain, fog, storms, and cold snaps.',
                'Internal Ha Giang Loop, Safety, Insurance, and Transport pages.',
            ],
            'image_plan' => [
                'Practical road/pass image with cautionary caption.',
                'Original safety decision checklist graphic.',
                'Avoid glamor-only motorbike imagery.',
            ],
            'internal_links' => [
                '/plan/health-travel-insurance-vietnam/',
                '/plan/safety-scams-vietnam/',
                '/destinations/ha-giang-loop-planning-guide/',
                '/plan/transport-within-vietnam/',
            ],
        ],
        [
            'title' => 'Ha Giang Easy Rider vs Self-Drive: Which Is Right for You?',
            'slug' => 'ha-giang-easy-rider-vs-self-drive',
            'target_date' => '2026-08-28',
            'categories' => ['travel-planning', 'transport-logistics'],
            'tags' => ['mountain-planning', 'safety-planning', 'transport-planning', 'anti-spam-evergreen'],
            'intent' => 'Compare easy rider, self-drive, and private-car Ha Giang options by safety, control, cost, responsibility, and comfort.',
            'reader_job' => 'Choose the right Ha Giang travel mode before committing money or accepting road risk.',
            'evidence_moat' => 'Separate control from responsibility: license, insurance, guide authority, fatigue, weather, luggage, group size, and cancellation terms.',
            'what_not_to_write' => 'Do not frame self-drive as adventurous by default or easy rider as a universal safety solution.',
            'source_plan' => [
                'Government road-safety and motorcycle travel advice.',
                'Internal Ha Giang Safety and Insurance guides.',
                'Vietnam.travel Ha Giang Loop for route context.',
                'Operator policy checks only as examples, not endorsements.',
            ],
            'image_plan' => [
                'Mode-comparison graphic with easy rider, self-drive, private car.',
                'Road image captioned around responsibility and conditions.',
                'No affiliate operator imagery.',
            ],
            'internal_links' => [
                '/destinations/ha-giang-safety-guide/',
                '/destinations/ha-giang-loop-planning-guide/',
                '/plan/health-travel-insurance-vietnam/',
                '/plan/safety-scams-vietnam/',
            ],
        ],
        [
            'title' => 'Sapa Trekking: Guided vs Self-Guided for First-Time Visitors',
            'slug' => 'sapa-trekking-guided-vs-self-guided',
            'target_date' => '2026-08-29',
            'categories' => ['destinations', 'travel-planning'],
            'tags' => ['trekking-planning', 'mountain-planning', 'first-time-vietnam', 'anti-spam-evergreen'],
            'intent' => 'Help Sapa travelers decide when a guide improves safety, context, route quality, and village ethics.',
            'reader_job' => 'Choose guided trekking, short self-guided walks, private guide, homestay trek, or skip trekking based on weather and ability.',
            'evidence_moat' => 'Use mud, fog, shoes, trail ambiguity, local context, village visits, group pace, and ethical spending as practical filters.',
            'what_not_to_write' => 'Do not make trekking sound effortless, untouched, or culturally simple.',
            'source_plan' => [
                'Vietnam.travel Sapa and trekking context.',
                'Weather sources for fog, rain, cold, and muddy trail caveats.',
                'Internal Sapa, Packing, Insurance, and Safety pages.',
                'Local guide/operator checks only when source-labeled and non-promotional.',
            ],
            'image_plan' => [
                'Trail or terrace-walk image with footing/weather caption.',
                'Original guided-versus-self-guided decision graphic.',
                'Avoid staged village portraits unless license and ethics are clear.',
            ],
            'internal_links' => [
                '/destinations/sapa-travel-guide/',
                '/travel-planning/what-to-pack-for-vietnam-region-season/',
                '/plan/health-travel-insurance-vietnam/',
                '/plan/best-time-to-visit-vietnam/',
            ],
        ],
        [
            'title' => 'Where to Stay in Sapa: Town, Valley Lodge or Homestay?',
            'slug' => 'where-to-stay-in-sapa',
            'target_date' => '2026-08-30',
            'categories' => ['hotels-neighborhoods', 'destinations'],
            'tags' => ['hotel-base', 'mountain-planning', 'premium-travel', 'anti-spam-evergreen'],
            'intent' => 'Help travelers choose the right Sapa base by view, walking, comfort, transport, weather, and evening needs.',
            'reader_job' => 'Choose Sapa town, Muong Hoa valley, Ta Van/Lao Chai style stay, higher-comfort lodge, or skip Sapa when logistics do not fit.',
            'evidence_moat' => 'Compare base areas by traveler job: transfer convenience, terrace views, walking access, dinner options, mobility, fog, and family comfort.',
            'what_not_to_write' => 'Do not publish hotel affiliate lists or pretend one base is best for everyone.',
            'source_plan' => [
                'Vietnam.travel Sapa for destination framing.',
                'Internal Sapa guide and where-to-stay system principles.',
                'Weather and transport checks for valley access and pickup timing.',
                'Manual editor hotel-map review before publishing.',
            ],
            'image_plan' => [
                'Valley/town contrast image set with text credits.',
                'Original base-map graphic for town versus valley.',
                'No hotel-brand imagery unless editorially necessary and credited.',
            ],
            'internal_links' => [
                '/destinations/sapa-travel-guide/',
                '/travel-planning/where-to-stay-in-vietnam-base-decisions/',
                '/plan/transport-within-vietnam/',
                '/plan/best-time-to-visit-vietnam/',
            ],
        ],
        [
            'title' => 'Best Time for Northern Vietnam: Hanoi, Bay, Ninh Binh, Sapa and Ha Giang',
            'slug' => 'best-time-for-northern-vietnam',
            'target_date' => '2026-08-31',
            'categories' => ['seasonal-travel', 'travel-planning'],
            'tags' => ['weather-planning', 'mountain-planning', 'route-planning', 'anti-spam-evergreen'],
            'intent' => 'Help travelers plan northern Vietnam weather by route job rather than one best month claim.',
            'reader_job' => 'Choose timing and buffers for Hanoi walking, bay cruises, Ninh Binh boats, Sapa terraces, Ha Giang roads, and Pu Luong valleys.',
            'evidence_moat' => 'Separate city, bay, karst, mountain, terrace, and loop-road weather consequences with live-check discipline.',
            'what_not_to_write' => 'Do not claim a single perfect month for all northern Vietnam or guarantee clear views.',
            'source_plan' => [
                'Vietnam.travel weather and climate page.',
                'NCHMF live forecast source for refresh discipline.',
                'Internal Best Time, Sapa, Ha Giang, Ninh Binh, Ha Long, and route pages.',
                'Image/source records for seasonal examples.',
            ],
            'image_plan' => [
                'Original northern weather decision matrix graphic.',
                'Seasonal support images only when captions explain practical trade-offs.',
                'Avoid weather-stock decoration.',
            ],
            'internal_links' => [
                '/plan/best-time-to-visit-vietnam/',
                '/destinations/sapa-travel-guide/',
                '/destinations/ha-giang-loop-planning-guide/',
                '/destinations/ninh-binh-travel-guide/',
                '/destinations/ha-long-bay-travel-guide/',
            ],
        ],
        [
            'title' => 'Vietnam Rice Terraces Guide: Sapa, Mu Cang Chai, Hoang Su Phi or Pu Luong?',
            'slug' => 'vietnam-rice-terraces-guide',
            'target_date' => '2026-09-01',
            'categories' => ['destinations', 'seasonal-travel'],
            'tags' => ['rice-terraces', 'mountain-planning', 'route-planning', 'anti-spam-evergreen'],
            'intent' => 'Help travelers choose a rice-terrace destination by season, access, comfort, route fit, and photography expectations.',
            'reader_job' => 'Choose Sapa, Mu Cang Chai, Hoang Su Phi, Pu Luong, or skip terraces when timing/logistics do not support them.',
            'evidence_moat' => 'Use terrace-season caveats, transfer load, comfort range, crowd tolerance, photography goals, and route length instead of a prettiest-terraces ranking.',
            'what_not_to_write' => 'Do not promise green or golden terraces year-round or recycle photo-list content.',
            'source_plan' => [
                'Vietnam.travel Sapa and northern mountain context.',
                'Provincial/tourism sources for Mu Cang Chai, Hoang Su Phi, and Pu Luong when available.',
                'Weather and season checks before publishing.',
                'Internal Sapa, Best Time Northern Vietnam, Hanoi, transport, and route pages.',
            ],
            'image_plan' => [
                'Terrace comparison image set with clear credits.',
                'Original season/access matrix graphic.',
                'Captions must state timing and access caveats.',
            ],
            'internal_links' => [
                '/destinations/sapa-travel-guide/',
                '/plan/best-time-to-visit-vietnam/',
                '/destinations/sapa-vs-ha-giang/',
                '/itineraries/14-days-in-vietnam/',
            ],
        ],
        [
            'title' => 'Mu Cang Chai Travel Guide: Rice Terraces Without Forcing the Route',
            'slug' => 'mu-cang-chai-travel-guide',
            'target_date' => '2026-09-02',
            'categories' => ['destinations', 'travel-planning'],
            'tags' => ['rice-terraces', 'mountain-planning', 'route-planning', 'anti-spam-evergreen'],
            'intent' => 'Help travelers decide whether Mu Cang Chai is worth the extra northern logistics for rice terraces.',
            'reader_job' => 'Choose Mu Cang Chai, Sapa, Pu Luong, or skip based on season, transfer effort, accommodation comfort, and route priority.',
            'evidence_moat' => 'Treat Mu Cang Chai as a specific seasonal detour with access and comfort trade-offs, not a generic terrace photo target.',
            'what_not_to_write' => 'Do not sell it as easy from Hanoi for every traveler or as a guaranteed golden-terrace experience.',
            'source_plan' => [
                'Vietnam.travel or provincial tourism references for rice terrace context.',
                'Transport and weather checks close to publication.',
                'Internal rice terraces, Sapa, Best Time Northern Vietnam, and Hanoi pages.',
                'Image-license records for terrace photos.',
            ],
            'image_plan' => [
                'Mu Cang Chai terrace image with verified credit.',
                'Original route-friction graphic from Hanoi/Sapa/Pu Luong comparison.',
                'Use photo proof to explain season, not just scenery.',
            ],
            'internal_links' => [
                '/destinations/vietnam-rice-terraces-guide/',
                '/destinations/sapa-travel-guide/',
                '/plan/best-time-to-visit-vietnam/',
                '/plan/transport-within-vietnam/',
            ],
        ],
        [
            'title' => 'Pu Luong Travel Guide: Softer Countryside or Mountain Detour?',
            'slug' => 'pu-luong-travel-guide',
            'target_date' => '2026-09-03',
            'categories' => ['destinations', 'travel-planning'],
            'tags' => ['mountain-planning', 'rice-terraces', 'family-travel', 'anti-spam-evergreen'],
            'intent' => 'Help travelers decide whether Pu Luong offers a better countryside pause than Sapa, Ha Giang, Ninh Binh, or Mai Chau.',
            'reader_job' => 'Choose Pu Luong, Ninh Binh, Sapa, Mai Chau, or skip based on scenery, transfer load, lodge comfort, walking, and route length.',
            'evidence_moat' => 'Use softer countryside logic, base comfort, transfer reality, rice-field timing, family fit, and route duplication checks.',
            'what_not_to_write' => 'Do not frame Pu Luong as an untouched hidden gem or a universal replacement for Sapa/Ha Giang.',
            'source_plan' => [
                'Vietnam.travel or provincial sources for Pu Luong and Thanh Hoa context.',
                'Weather/road checks before publication.',
                'Internal Ninh Binh, Sapa, Sapa vs Ha Giang, Best Time, and route pages.',
                'Image-license records for lodge/valley/terrace images.',
            ],
            'image_plan' => [
                'Pu Luong valley or rice-field image with credit.',
                'Original compare-or-skip matrix for Pu Luong, Mai Chau, Sapa, Ninh Binh.',
                'Images should show actual countryside/base feel.',
            ],
            'internal_links' => [
                '/destinations/ninh-binh-travel-guide/',
                '/destinations/sapa-travel-guide/',
                '/destinations/sapa-vs-ha-giang/',
                '/plan/best-time-to-visit-vietnam/',
            ],
        ],
    ];
}

function vg_post_editorial_batch3_term_id(string $taxonomy, string $name, string $slug, string $description = ''): int
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
        vg_post_editorial_batch3_fail("Could not create {$taxonomy} term {$slug}: " . $result->get_error_message());
    }

    return (int) $result['term_id'];
}

function vg_post_editorial_batch3_author_id(): int
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

function vg_post_editorial_batch3_find_post_by_slug(string $slug): ?WP_Post
{
    $posts = get_posts(
        [
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'future', 'pending', 'private'],
            'name' => $slug,
            'posts_per_page' => 2,
        ]
    );

    if (count($posts) > 1) {
        vg_post_editorial_batch3_fail("Expected at most one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_post_editorial_batch3_list_items(array $items): string
{
    $html = '';

    foreach ($items as $item) {
        $html .= '<li>' . esc_html($item) . '</li>';
    }

    return $html;
}

function vg_post_editorial_batch3_brief_content(array $brief): string
{
    $links = vg_post_editorial_batch3_list_items($brief['internal_links']);
    $sources = vg_post_editorial_batch3_list_items($brief['source_plan']);
    $images = vg_post_editorial_batch3_list_items($brief['image_plan']);

    return '<!-- wp:paragraph -->'
        . '<p><strong>Draft status:</strong> This is a Batch 3 northern Vietnam editorial brief, not a finished article. Expand inside WordPress Admin, source-check current facts, add useful images, tune Rank Math, and review manually before publishing.</p>'
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

function vg_post_editorial_batch3_assert_existing_batch(WP_Post $post, string $batch, string $slug): void
{
    $post_id = (int) $post->ID;
    $brief_status = trim((string) get_post_meta($post_id, 'vg_editorial_brief_status', true));
    $existing_batch = trim((string) get_post_meta($post_id, 'vg_editorial_batch', true));

    if ($post->post_status !== 'draft') {
        vg_post_editorial_batch3_fail("Editorial brief slug already exists but is not draft: {$slug} (#{$post_id}, status {$post->post_status})");
    }

    if ($existing_batch !== $batch || ! in_array($brief_status, ['brief', 'complete_draft'], true)) {
        vg_post_editorial_batch3_fail("Draft slug already exists outside the Batch 3 workflow: {$slug} (#{$post_id})");
    }
}

$category_ids_by_slug = [];

foreach (vg_post_editorial_batch3_categories() as $category) {
    $category_ids_by_slug[$category['slug']] = vg_post_editorial_batch3_term_id('category', $category['name'], $category['slug'], $category['description']);
}

$tag_ids_by_slug = [];

foreach (vg_post_editorial_batch3_tags() as $slug => $name) {
    $tag_ids_by_slug[$slug] = vg_post_editorial_batch3_term_id('post_tag', $name, $slug);
}

$author_id = vg_post_editorial_batch3_author_id();
$created = 0;
$preserved = 0;
$today = wp_date('F j, Y');
$batch = 'batch-3-northern-mountains';

foreach (vg_post_editorial_batch3_briefs() as $brief) {
    $existing = vg_post_editorial_batch3_find_post_by_slug($brief['slug']);

    if ($existing instanceof WP_Post) {
        vg_post_editorial_batch3_assert_existing_batch($existing, $batch, $brief['slug']);
        $post_id = (int) $existing->ID;
        $preserved++;
    } else {
        $post_id = wp_insert_post(
            [
                'post_type' => 'post',
                'post_title' => $brief['title'],
                'post_name' => $brief['slug'],
                'post_status' => 'draft',
                'post_author' => $author_id,
                'post_content' => vg_post_editorial_batch3_brief_content($brief),
                'post_excerpt' => $brief['intent'],
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ],
            true
        );

        if (is_wp_error($post_id)) {
            vg_post_editorial_batch3_fail("Could not create editorial brief {$brief['slug']}: " . $post_id->get_error_message());
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
        update_post_meta($post_id, 'vg_admin_first_notes', 'Native WordPress Post Batch 3 editorial brief created for manual expansion. Do not publish until the brief language is replaced with complete sourced article content.');
        $created++;
    }

    $category_ids = [];

    foreach ($brief['categories'] as $category_slug) {
        if (! isset($category_ids_by_slug[$category_slug])) {
            vg_post_editorial_batch3_fail("Missing category ID for {$category_slug}");
        }

        $category_ids[] = $category_ids_by_slug[$category_slug];
    }

    $tag_ids = [];

    foreach ($brief['tags'] as $tag_slug) {
        if (! isset($tag_ids_by_slug[$tag_slug])) {
            vg_post_editorial_batch3_fail("Missing tag ID for {$tag_slug}");
        }

        $tag_ids[] = $tag_ids_by_slug[$tag_slug];
    }

    wp_set_object_terms($post_id, $category_ids, 'category', false);
    wp_set_object_terms($post_id, $tag_ids, 'post_tag', false);
}

WP_CLI::success("WordPress post editorial system batch 3 ready. Created {$created} draft brief(s); preserved {$preserved} existing draft brief(s).");

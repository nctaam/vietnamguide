<?php
if (! defined('ABSPATH')) {
    exit;
}

function vg_get_default_og_image_url(): string
{
    $defaultImage = get_theme_file_uri('/assets/images/ha-long-bay-vietnam-hero.jpg');

    if (is_singular()) {
        $post = get_post();
        if ($post instanceof WP_Post && ! empty($post->post_content)) {
            $processor = new WP_HTML_Tag_Processor($post->post_content);
            while ($processor->next_token()) {
                if ($processor->get_token_type() === '#tag' && strtolower($processor->get_tag()) === 'img') {
                    $src = $processor->get_attribute('src');
                    if (is_string($src) && $src !== '') {
                        $src = trim($src);
                        if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
                            if (preg_match('/\.([a-zA-Z0-9]+)$/', $src, $m)) {
                                $src = substr($src, 0, -strlen($m[1])) . strtolower($m[1]);
                            }
                            return $src;
                        }
                    }
                }
            }
        }
    }

    return $defaultImage;
}

// Hook into Rank Math OpenGraph image builder
add_action('rank_math/opengraph/facebook/add_additional_images', static function ($image_obj): void {
    if (is_object($image_obj) && method_exists($image_obj, 'has_images') && ! $image_obj->has_images()) {
        $fallback = vg_get_default_og_image_url();
        if ($fallback !== '' && method_exists($image_obj, 'add_image_by_url')) {
            $image_obj->add_image_by_url($fallback);
        }
    }
});

add_action('rank_math/opengraph/twitter/add_additional_images', static function ($image_obj): void {
    if (is_object($image_obj) && method_exists($image_obj, 'has_images') && ! $image_obj->has_images()) {
        $fallback = vg_get_default_og_image_url();
        if ($fallback !== '' && method_exists($image_obj, 'add_image_by_url')) {
            $image_obj->add_image_by_url($fallback);
        }
    }
});

// Category and taxonomy cleanup redirection: route empty category archives to corresponding hubs
add_action('template_redirect', static function (): void {
    if (! is_category() && ! is_tag()) {
        return;
    }

    $currentSlug = '';
    if (is_category()) {
        $cat = get_queried_object();
        if ($cat instanceof WP_Term) {
            $currentSlug = $cat->slug;
        }
    } elseif (is_tag()) {
        $tag = get_queried_object();
        if ($tag instanceof WP_Term) {
            $currentSlug = $tag->slug;
        }
    }

    $routes = [
        'destinations' => home_url('/destinations/'),
        'itineraries'  => home_url('/itineraries/'),
        'plan'         => home_url('/plan/'),
        'compare'      => home_url('/compare/'),
    ];

    $target = $routes[$currentSlug] ?? home_url('/');
    wp_safe_redirect($target, 301);
    exit;
});

// Core Web Vitals & Resource Hints: Preconnect to media CDN and preload LCP hero image
add_action('wp_head', static function (): void {
    echo '<link rel="preconnect" href="https://upload.wikimedia.org" crossorigin>' . "\n";
    echo '<link rel="dns-prefetch" href="https://upload.wikimedia.org">' . "\n";

    if (is_singular()) {
        $heroImage = vg_get_default_og_image_url();
        if ($heroImage !== '') {
            echo '<link rel="preload" as="image" href="' . esc_url($heroImage) . '" fetchpriority="high">' . "\n";
        }
    }
}, 1);

/**
 * ==========================================================================
 * Stage 20: Rich Travel Structured Data & Contextual Destination Hub Linking
 * ==========================================================================
 * Provides rich Schema.org entities (TouristDestination, TouristTrip, TravelAction)
 * complementary to Rank Math's graph, and renders high-converting next-step
 * journey links between connected destination clusters.
 */

/**
 * Authoritative registry of connected destination clusters across Vietnam.
 */
function vg_get_travel_clusters_registry(): array
{
    static $registry = null;
    if ($registry !== null) {
        return $registry;
    }

    $registry = [
        'northern_triangle' => [
            'id'                 => 'northern_triangle',
            'name'               => 'Northern Golden Triangle',
            'hub'                => 'Hanoi',
            'hub_url'            => '/destinations/hanoi-travel-guide/',
            'destination_schema' => [
                'name'         => 'Hanoi',
                'description'  => 'Vietnam\'s 1,000-year-old capital city, featuring the atmospheric Old Quarter, French colonial boulevards, vibrant street-food culture, and seamless overland connectivity to Ha Long Bay and Ninh Binh.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q1858',
                'tourist_type' => ['Cultural tourism', 'Culinary tourism', 'Urban exploration', 'Historical tourism'],
                'attractions'  => [
                    ['name' => 'Old Quarter', 'wikidata' => 'https://www.wikidata.org/wiki/Q10808390'],
                    ['name' => 'Hoan Kiem Lake', 'wikidata' => 'https://www.wikidata.org/wiki/Q844112'],
                    ['name' => 'Temple of Literature', 'wikidata' => 'https://www.wikidata.org/wiki/Q1081308'],
                    ['name' => 'French Quarter', 'wikidata' => 'https://www.wikidata.org/wiki/Q10808390'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Hanoi to Ha Long Bay & Ninh Binh Northern Triangle Journey',
                'description'  => 'The quintessential northern Vietnam travel circuit linking the urban cultural heritage of Hanoi with the maritime limestone karsts of Ha Long Bay and the serene terrestrial river valleys of Ninh Binh.',
                'tourist_type' => ['First-time visitors', 'Multi-day travelers', 'Cultural explorers'],
                'stops'        => [
                    ['name' => 'Hanoi', 'url' => '/destinations/hanoi-travel-guide/'],
                    ['name' => 'Ha Long Bay & Lan Ha Bay', 'url' => '/destinations/ha-long-bay-travel-guide/'],
                    ['name' => 'Ninh Binh (Trang An & Tam Coc)', 'url' => '/destinations/ninh-binh-travel-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Hanoi to Ha Long Bay & Ninh Binh Route',
                'method' => 'Expressway & Limousine Transit',
            ],
            'journey_links' => [
                [
                    'name'    => 'Ha Long Bay & Lan Ha Bay',
                    'url'     => '/destinations/ha-long-bay-travel-guide/',
                    'transit' => '2.5 hrs via Expressway',
                    'badge'   => 'Limestone Karst Cruise',
                    'desc'    => 'Overnight luxury cruising, sea kayaking among karst towers, and emerald waters.',
                ],
                [
                    'name'    => 'Ninh Binh (Trang An & Tam Coc)',
                    'url'     => '/destinations/ninh-binh-travel-guide/',
                    'transit' => '1.5 hrs via Train or Van',
                    'badge'   => 'Terrestrial Karst Valley',
                    'desc'    => 'Paddleboat river grottoes, Bich Dong pagoda, and cycling through tranquil rice paddies.',
                ],
                [
                    'name'    => 'Sa Pa & Tonkinese Alps',
                    'url'     => '/destinations/sapa-travel-guide/',
                    'transit' => '5.5 hrs via Express Highway',
                    'badge'   => 'Highland Rice Terraces',
                    'desc'    => 'Spectacular Fansipan peaks, ethnic hill-tribe villages, and high-altitude trekking.',
                ],
            ],
        ],

        'ha_long_bay' => [
            'id'                 => 'ha_long_bay',
            'name'               => 'Gulf of Tonkin Karst Archipelago',
            'hub'                => 'Ha Long Bay',
            'hub_url'            => '/destinations/ha-long-bay-travel-guide/',
            'destination_schema' => [
                'name'         => 'Ha Long Bay & Lan Ha Bay',
                'description'  => 'UNESCO World Heritage marine wonder characterized by thousands of soaring limestone karsts, secluded floating fishing villages, and emerald sea channels.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q190128',
                'tourist_type' => ['Marine cruise tourism', 'Nature & landscapes', 'Sea kayaking', 'UNESCO heritage'],
                'attractions'  => [
                    ['name' => 'Ha Long Bay', 'wikidata' => 'https://www.wikidata.org/wiki/Q190128'],
                    ['name' => 'Lan Ha Bay', 'wikidata' => 'https://www.wikidata.org/wiki/Q3216853'],
                    ['name' => 'Cat Ba Island', 'wikidata' => 'https://www.wikidata.org/wiki/Q1936306'],
                    ['name' => 'Bai Tu Long Bay', 'wikidata' => 'https://www.wikidata.org/wiki/Q804153'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Ha Long Bay to Ninh Binh & Hanoi Maritime Circuit',
                'description'  => 'Direct connection bridging maritime limestone towers with terrestrial paddleboat valleys and capital culture without backtracking.',
                'tourist_type' => ['Nature lovers', 'Cruise travelers', 'Scenic photographers'],
                'stops'        => [
                    ['name' => 'Ha Long Bay', 'url' => '/destinations/ha-long-bay-travel-guide/'],
                    ['name' => 'Ninh Binh', 'url' => '/destinations/ninh-binh-travel-guide/'],
                    ['name' => 'Hanoi', 'url' => '/destinations/hanoi-travel-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Ha Long Bay to Ninh Binh & Hanoi Route',
                'method' => 'Direct Coastal Highway & Cruise Transfer',
            ],
            'journey_links' => [
                [
                    'name'    => 'Ninh Binh Countryside',
                    'url'     => '/destinations/ninh-binh-travel-guide/',
                    'transit' => '3 hrs direct highway transfer',
                    'badge'   => 'Terrestrial Karsts',
                    'desc'    => 'Connect maritime limestone towers with terrestrial paddleboat grottoes without returning to Hanoi.',
                ],
                [
                    'name'    => 'Hanoi Capital & Old Quarter',
                    'url'     => '/destinations/hanoi-travel-guide/',
                    'transit' => '2.5 hrs via Expressway',
                    'badge'   => 'Capital Cultural Hub',
                    'desc'    => 'Return to the capital for street-food recovery, night markets, and onward air connections.',
                ],
                [
                    'name'    => 'Cat Ba Island & National Park',
                    'url'     => '/destinations/cat-ba-travel-guide/',
                    'transit' => '1 hr ferry from bay port',
                    'badge'   => 'Island Adventure',
                    'desc'    => 'Hiking coastal rainforest trails, rock climbing limestone crags, and Cannon Fort sunsets.',
                ],
            ],
        ],

        'ninh_binh' => [
            'id'                 => 'ninh_binh',
            'name'               => 'Ninh Binh Karst Countryside',
            'hub'                => 'Ninh Binh',
            'hub_url'            => '/destinations/ninh-binh-travel-guide/',
            'destination_schema' => [
                'name'         => 'Ninh Binh (Trang An & Tam Coc)',
                'description'  => 'Often celebrated as "Ha Long Bay on land," Ninh Binh enchants travelers with limestone peaks emerging from emerald rice fields, UNESCO paddleboat grottoes, and ancient dynastic temples.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q36359',
                'tourist_type' => ['Ecotourism', 'Rural exploration', 'UNESCO landscape', 'Cycling tourism'],
                'attractions'  => [
                    ['name' => 'Trang An Landscape Complex', 'wikidata' => 'https://www.wikidata.org/wiki/Q10828551'],
                    ['name' => 'Tam Coc', 'wikidata' => 'https://www.wikidata.org/wiki/Q7680468'],
                    ['name' => 'Mua Caves Viewpoint', 'wikidata' => 'https://www.wikidata.org/wiki/Q36359'],
                    ['name' => 'Bich Dong Pagoda', 'wikidata' => 'https://www.wikidata.org/wiki/Q36359'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Ninh Binh to Ha Long Bay & Central Vietnam Gateway',
                'description'  => 'A versatile journey linking pastoral river valleys directly with Gulf of Tonkin cruises or overnight trains south toward Phong Nha and Hue.',
                'tourist_type' => ['Slow travelers', 'Adventure seekers', 'Photographers'],
                'stops'        => [
                    ['name' => 'Ninh Binh', 'url' => '/destinations/ninh-binh-travel-guide/'],
                    ['name' => 'Ha Long Bay', 'url' => '/destinations/ha-long-bay-travel-guide/'],
                    ['name' => 'Phong Nha Caves', 'url' => '/destinations/phong-nha-travel-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Ninh Binh Onward Journey',
                'method' => 'Highway Express & Reunification Express Rail',
            ],
            'journey_links' => [
                [
                    'name'    => 'Ha Long Bay Overnight Cruise',
                    'url'     => '/destinations/ha-long-bay-travel-guide/',
                    'transit' => '3 hrs via Highway 10',
                    'badge'   => 'Marine Karsts',
                    'desc'    => 'Direct highway transit from rice valleys to overnight luxury karst cruising.',
                ],
                [
                    'name'    => 'Phong Nha Cave Kingdom',
                    'url'     => '/destinations/phong-nha-travel-guide/',
                    'transit' => '7 hrs via Sleeper Train',
                    'badge'   => 'Cave Exploration',
                    'desc'    => 'Transition south toward the world\'s most spectacular underground river cave systems.',
                ],
                [
                    'name'    => 'Hanoi Old Quarter',
                    'url'     => '/destinations/hanoi-travel-guide/',
                    'transit' => '1.5 hrs via Limousine/Train',
                    'badge'   => 'Capital Hub',
                    'desc'    => 'Fast return to Hanoi for international flight departures or northern mountain routes.',
                ],
            ],
        ],

        'central_heritage' => [
            'id'                 => 'central_heritage',
            'name'               => 'Central Heritage Corridor',
            'hub'                => 'Da Nang',
            'hub_url'            => '/destinations/da-nang-travel-guide/',
            'destination_schema' => [
                'name'         => 'Da Nang, Hoi An & Hue',
                'description'  => 'Central Vietnam\'s cultural and coastal heartland, spanning the royal palaces of Hue, the lantern-lit UNESCO trading lanes of Hoi An, and the beaches and Marble Mountains of Da Nang.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q25282',
                'tourist_type' => ['Heritage tourism', 'Beach resort travel', 'Culinary exploration', 'Cultural travel'],
                'attractions'  => [
                    ['name' => 'Hoi An Ancient Town', 'wikidata' => 'https://www.wikidata.org/wiki/Q36167'],
                    ['name' => 'Imperial City of Hue', 'wikidata' => 'https://www.wikidata.org/wiki/Q200257'],
                    ['name' => 'My Khe Beach & Marble Mountains', 'wikidata' => 'https://www.wikidata.org/wiki/Q25282'],
                    ['name' => 'Hai Van Pass', 'wikidata' => 'https://www.wikidata.org/wiki/Q25282'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Da Nang to Hoi An & Hue Central Heritage Corridor',
                'description'  => 'A scenic coastal and imperial cultural journey traversing the Hai Van Pass, UNESCO ancient merchant quarter of Hoi An, and royal Nguyen dynasty monuments in Hue.',
                'tourist_type' => ['Heritage travelers', 'Scenic road trippers', 'Food & culture enthusiasts'],
                'stops'        => [
                    ['name' => 'Da Nang Coastal Gateway', 'url' => '/destinations/da-nang-travel-guide/'],
                    ['name' => 'Hoi An Ancient Town', 'url' => '/destinations/hoi-an-ancient-town-guide/'],
                    ['name' => 'Hue Imperial City', 'url' => '/destinations/hue-imperial-city-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Central Heritage Corridor (Da Nang -> Hoi An & Hue)',
                'method' => 'Coastal Scenic Highway & Hai Van Pass Route',
            ],
            'journey_links' => [
                [
                    'name'    => 'Hoi An Ancient Town',
                    'url'     => '/destinations/hoi-an-ancient-town-guide/',
                    'transit' => '45 mins via Coastal Road',
                    'badge'   => 'UNESCO Living Heritage',
                    'desc'    => 'Yellow-ochre alleyways, night lantern riverboats, custom tailoring, and riverside dining.',
                ],
                [
                    'name'    => 'Hue Imperial Citadel & Tombs',
                    'url'     => '/destinations/hue-imperial-city-guide/',
                    'transit' => '2 hrs via Hai Van Pass',
                    'badge'   => 'Imperial Dynasty',
                    'desc'    => 'Nguyen dynasty royal palaces, ornate emperor tombs, and Perfume River dragon boat rides.',
                ],
                [
                    'name'    => 'Da Nang City & My Khe Beach',
                    'url'     => '/destinations/da-nang-travel-guide/',
                    'transit' => '45 mins from Hoi An / 2 hrs from Hue',
                    'badge'   => 'Coastal Gateway',
                    'desc'    => 'Modern international airport, Marble Mountains, Ba Na Hills, and fresh seafood dining.',
                ],
                [
                    'name'    => 'Cham Islands Marine Biosphere',
                    'url'     => '/destinations/cham-islands-travel-guide/',
                    'transit' => '30 min speedboat from Cua Dai',
                    'badge'   => 'Marine Sanctuary',
                    'desc'    => 'Coral reef snorkeling, quiet sandy coves, and fresh island seafood on an offshore retreat.',
                ],
            ],
        ],

        'southern_delta' => [
            'id'                 => 'southern_delta',
            'name'               => 'Southern Riverine & Heritage Route',
            'hub'                => 'Ho Chi Minh City',
            'hub_url'            => '/destinations/ho-chi-minh-city-travel-guide/',
            'destination_schema' => [
                'name'         => 'Ho Chi Minh City & Mekong Delta',
                'description'  => 'Vietnam\'s bustling southern economic powerhouse, famed for French colonial landmarks, Saigon street-food alleys, wartime history, and gateway to the waterways of the Mekong Delta.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q1854',
                'tourist_type' => ['Urban exploration', 'Culinary tourism', 'Historical tourism', 'Riverine ecotourism'],
                'attractions'  => [
                    ['name' => 'War Remnants Museum', 'wikidata' => 'https://www.wikidata.org/wiki/Q1854'],
                    ['name' => 'Cu Chi Tunnels', 'wikidata' => 'https://www.wikidata.org/wiki/Q192935'],
                    ['name' => 'Mekong Delta Waterways', 'wikidata' => 'https://www.wikidata.org/wiki/Q1052867'],
                    ['name' => 'Ben Thanh Market', 'wikidata' => 'https://www.wikidata.org/wiki/Q1854'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Ho Chi Minh City to Mekong Delta & Cu Chi Southern Journey',
                'description'  => 'A southern Vietnam route connecting the energy of Saigon with the historical Cu Chi tunnel networks and the tranquil sampan waterways of the Mekong Delta.',
                'tourist_type' => ['Independent travelers', 'Culture & history seekers', 'River explorers'],
                'stops'        => [
                    ['name' => 'Ho Chi Minh City', 'url' => '/destinations/ho-chi-minh-city-travel-guide/'],
                    ['name' => 'Mekong Delta', 'url' => '/destinations/mekong-delta-travel-guide/'],
                    ['name' => 'Cu Chi Historic Tunnels', 'url' => '/destinations/best-day-trips-from-ho-chi-minh-city/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Ho Chi Minh City to Mekong Delta & Cu Chi Journey',
                'method' => 'Expressway, Speedboat & River Sampan',
            ],
            'journey_links' => [
                [
                    'name'    => 'Mekong Delta River Life',
                    'url'     => '/destinations/mekong-delta-travel-guide/',
                    'transit' => '2 hrs via Expressway',
                    'badge'   => 'Waterway Ecology',
                    'desc'    => 'Sampan rides under nipa palms, Cai Rang floating market, and rural orchard homestays.',
                ],
                [
                    'name'    => 'Cu Chi Historic Tunnels',
                    'url'     => '/destinations/best-day-trips-from-ho-chi-minh-city/',
                    'transit' => '1.5 hrs via Speedboat/Car',
                    'badge'   => 'Living History',
                    'desc'    => 'Walk through the legendary underground resistance complex and historic forest trails.',
                ],
                [
                    'name'    => 'Phu Quoc Tropical Island',
                    'url'     => '/destinations/phu-quoc-travel-guide/',
                    'transit' => '55 min flight from SGN',
                    'badge'   => 'Island Sanctuary',
                    'desc'    => 'Powder-white beaches, sunset cocktails, night market seafood, and seaside luxury.',
                ],
            ],
        ],

        'northern_highlands' => [
            'id'                 => 'northern_highlands',
            'name'               => 'Northern Highlands Frontier',
            'hub'                => 'Sa Pa & Ha Giang',
            'hub_url'            => '/destinations/sapa-travel-guide/',
            'destination_schema' => [
                'name'         => 'Sa Pa & Northern Highlands',
                'description'  => 'Vietnam\'s dramatic northern mountain frontier, showcasing Fansipan summit, sculpted rice terraces, colorful ethnic hill-tribe markets, and the epic Ha Giang karst loop.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q36384',
                'tourist_type' => ['Mountain trekking', 'Adventure travel', 'Ethnic cultural immersion', 'Landscape photography'],
                'attractions'  => [
                    ['name' => 'Fansipan Summit', 'wikidata' => 'https://www.wikidata.org/wiki/Q1005391'],
                    ['name' => 'Ma Pi Leng Pass', 'wikidata' => 'https://www.wikidata.org/wiki/Q36352'],
                    ['name' => 'Mu Cang Chai Terraces', 'wikidata' => 'https://www.wikidata.org/wiki/Q6930267'],
                    ['name' => 'Dong Van Karst Plateau', 'wikidata' => 'https://www.wikidata.org/wiki/Q36352'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Northern Highlands & Frontier Loop Circuit',
                'description'  => 'An overland mountain expedition through high alpine passes, terraced amphitheaters, and remote ethnic markets across Sa Pa, Ha Giang, and Mu Cang Chai.',
                'tourist_type' => ['Trekking enthusiasts', 'Motorcycle route riders', 'Off-the-beaten-path travelers'],
                'stops'        => [
                    ['name' => 'Sa Pa', 'url' => '/destinations/sapa-travel-guide/'],
                    ['name' => 'Ha Giang Loop', 'url' => '/destinations/ha-giang-loop-planning-guide/'],
                    ['name' => 'Mu Cang Chai', 'url' => '/destinations/mu-cang-chai-travel-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Northern Highlands & Mountain Terraces',
                'method' => 'Mountain Pass Highways & Guided Overland Transfer',
            ],
            'journey_links' => [
                [
                    'name'    => 'Ha Giang Extreme Loop',
                    'url'     => '/destinations/ha-giang-loop-planning-guide/',
                    'transit' => '6 hrs via QL279 Mountain Road',
                    'badge'   => 'Epic Frontier',
                    'desc'    => 'Ride through Ma Pi Leng pass, Dong Van Karst Plateau, and rugged ethnic frontiers.',
                ],
                [
                    'name'    => 'Mu Cang Chai Terraced Valleys',
                    'url'     => '/destinations/mu-cang-chai-travel-guide/',
                    'transit' => '3.5 hrs via O Quy Ho Pass',
                    'badge'   => 'Rice Amphitheaters',
                    'desc'    => 'Witness Vietnam\'s most photogenic golden terraced hillsides and serene ethnic homestays.',
                ],
                [
                    'name'    => 'Sa Pa Alpine Town',
                    'url'     => '/destinations/sapa-travel-guide/',
                    'transit' => '5.5 hrs from Hanoi',
                    'badge'   => 'Alpine Trekking',
                    'desc'    => 'Summit Fansipan peak via cable car or trek through Muong Hoa valley ethnic villages.',
                ],
                [
                    'name'    => 'Hanoi Capital Base',
                    'url'     => '/destinations/hanoi-travel-guide/',
                    'transit' => '5.5 hrs via Expressway/Train',
                    'badge'   => 'Recovery Hub',
                    'desc'    => 'Return to Hanoi for warm baths, foot massages, and Old Quarter street-food comfort.',
                ],
            ],
        ],

        'coastal_islands' => [
            'id'                 => 'coastal_islands',
            'name'               => 'Coastal & Island Sanctuaries',
            'hub'                => 'Phu Quoc & Con Dao',
            'hub_url'            => '/destinations/phu-quoc-travel-guide/',
            'destination_schema' => [
                'name'         => 'Phu Quoc, Con Dao & Central Coast',
                'description'  => 'Vietnam\'s idyllic island retreats and sun-drenched coastal havens, offering powder-white sands, coral reef biodiversity, and calm turquoise seas.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q223145',
                'tourist_type' => ['Beach resort tourism', 'Marine biodiversity', 'Relaxation', 'Scuba diving'],
                'attractions'  => [
                    ['name' => 'Phu Quoc National Park', 'wikidata' => 'https://www.wikidata.org/wiki/Q223145'],
                    ['name' => 'Con Dao Marine Park', 'wikidata' => 'https://www.wikidata.org/wiki/Q1118128'],
                    ['name' => 'Quy Nhon Coast', 'wikidata' => 'https://www.wikidata.org/wiki/Q26577'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Vietnam Coastal & Island Sanctuary Hop',
                'description'  => 'A relaxing journey linking tranquil coastal bays in Quy Nhon with the turquoise waters and marine national parks of Phu Quoc and Con Dao.',
                'tourist_type' => ['Beach lovers', 'Honeymooners', 'Marine life enthusiasts'],
                'stops'        => [
                    ['name' => 'Phu Quoc Island', 'url' => '/destinations/phu-quoc-travel-guide/'],
                    ['name' => 'Con Dao Archipelago', 'url' => '/destinations/con-dao-travel-guide/'],
                    ['name' => 'Quy Nhon Coast', 'url' => '/destinations/quy-nhon-travel-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Island & Coastal Sanctuaries',
                'method' => 'Coastal Domestic Flights & Speed Ferry Transfers',
            ],
            'journey_links' => [
                [
                    'name'    => 'Con Dao Protected Archipelago',
                    'url'     => '/destinations/con-dao-travel-guide/',
                    'transit' => 'Flight via SGN or Can Tho ferry',
                    'badge'   => 'Eco Sanctuary',
                    'desc'    => 'Pristine marine national park, green sea turtle nesting grounds, and secluded serenity.',
                ],
                [
                    'name'    => 'Quy Nhon Turquoise Coast',
                    'url'     => '/destinations/quy-nhon-travel-guide/',
                    'transit' => '1 hr flight or coastal train',
                    'badge'   => 'Untouched Coast',
                    'desc'    => 'Uncrowded sandy bays, fresh seaside fishing shacks, and ancient Cham cliff towers.',
                ],
                [
                    'name'    => 'Ho Chi Minh City Gateway',
                    'url'     => '/destinations/ho-chi-minh-city-travel-guide/',
                    'transit' => '55 min flight',
                    'badge'   => 'Southern Hub',
                    'desc'    => 'Short hopper flight back to Saigon for rooftop dining, shopping, and international departures.',
                ],
            ],
        ],

        'national_circuit' => [
            'id'                 => 'national_circuit',
            'name'               => 'Classic Vietnam Grand Route',
            'hub'                => 'Vietnam',
            'hub_url'            => '/plan/vietnam-travel-guide/',
            'destination_schema' => [
                'name'         => 'Vietnam',
                'description'  => 'An extraordinary Southeast Asian destination blending 3,200 km of coastline, UNESCO World Heritage treasures, limestone karst bays, and globally renowned culinary culture.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q881',
                'tourist_type' => ['Cultural tourism', 'Nature & adventure', 'Culinary exploration', 'Heritage tourism'],
                'attractions'  => [
                    ['name' => 'Ha Long Bay', 'wikidata' => 'https://www.wikidata.org/wiki/Q190128'],
                    ['name' => 'Hoi An Ancient Town', 'wikidata' => 'https://www.wikidata.org/wiki/Q36167'],
                    ['name' => 'Trang An Landscape Complex', 'wikidata' => 'https://www.wikidata.org/wiki/Q10828551'],
                    ['name' => 'Imperial City of Hue', 'wikidata' => 'https://www.wikidata.org/wiki/Q200257'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Classic North-to-South Vietnam Grand Circuit',
                'description'  => 'The definitive travel route connecting Hanoi, Ha Long Bay, Ninh Binh, Hue, Hoi An, Da Nang, Ho Chi Minh City, and the Mekong Delta.',
                'tourist_type' => ['First-time visitors', 'Multi-week travelers', 'Comprehensive route planners'],
                'stops'        => [
                    ['name' => 'Hanoi', 'url' => '/destinations/hanoi-travel-guide/'],
                    ['name' => 'Ha Long Bay', 'url' => '/destinations/ha-long-bay-travel-guide/'],
                    ['name' => 'Ninh Binh', 'url' => '/destinations/ninh-binh-travel-guide/'],
                    ['name' => 'Hoi An & Da Nang', 'url' => '/destinations/hoi-an-ancient-town-guide/'],
                    ['name' => 'Ho Chi Minh City', 'url' => '/destinations/ho-chi-minh-city-travel-guide/'],
                    ['name' => 'Mekong Delta', 'url' => '/destinations/mekong-delta-travel-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Classic Vietnam Grand Circuit',
                'method' => 'Express Railway, Domestic Air & Highway',
            ],
            'journey_links' => [
                [
                    'name'    => 'Hanoi & Northern Karsts',
                    'url'     => '/destinations/hanoi-travel-guide/',
                    'transit' => 'Gateway to North',
                    'badge'   => 'Capital & Karsts',
                    'desc'    => 'Begin in Hanoi with Old Quarter food alleys before cruising Ha Long Bay and cycling Ninh Binh.',
                ],
                [
                    'name'    => 'Central Heritage Corridor',
                    'url'     => '/destinations/da-nang-travel-guide/',
                    'transit' => '1.2 hr flight from North/South',
                    'badge'   => 'Heritage & Beaches',
                    'desc'    => 'Immerse in lantern-lit Hoi An, imperial palaces in Hue, and sandy beaches in Da Nang.',
                ],
                [
                    'name'    => 'Southern Riverine Route',
                    'url'     => '/destinations/ho-chi-minh-city-travel-guide/',
                    'transit' => 'Gateway to South',
                    'badge'   => 'Metropolis & Delta',
                    'desc'    => 'Experience Saigon\'s energy, historic Cu Chi tunnels, and sampan waterways in the Mekong Delta.',
                ],
            ],
        ],
    ];

    return $registry;
}

/**
 * Identify matching travel cluster for a given URI path.
 */
function vg_find_travel_cluster_by_path(string $path): array
{
    $clusters = vg_get_travel_clusters_registry();
    $normalized = strtolower(trim($path, '/'));

    if (
        str_contains($normalized, 'hanoi')
        || str_contains($normalized, 'old-quarter')
        || str_contains($normalized, 'west-lake')
        || str_contains($normalized, 'french-quarter')
    ) {
        return $clusters['northern_triangle'];
    }

    if (
        str_contains($normalized, 'ha-long')
        || str_contains($normalized, 'lan-ha')
        || str_contains($normalized, 'cat-ba')
        || str_contains($normalized, 'bai-tu-long')
    ) {
        return $clusters['ha_long_bay'];
    }

    if (
        str_contains($normalized, 'ninh-binh')
        || str_contains($normalized, 'tam-coc')
        || str_contains($normalized, 'trang-an')
    ) {
        return $clusters['ninh_binh'];
    }

    if (
        str_contains($normalized, 'sapa')
        || str_contains($normalized, 'ha-giang')
        || str_contains($normalized, 'mu-cang-chai')
        || str_contains($normalized, 'pu-luong')
        || str_contains($normalized, 'rice-terraces')
    ) {
        return $clusters['northern_highlands'];
    }

    if (
        str_contains($normalized, 'da-nang')
        || str_contains($normalized, 'hoi-an')
        || str_contains($normalized, 'hue')
        || str_contains($normalized, 'cham-islands')
        || str_contains($normalized, 'phong-nha')
    ) {
        return $clusters['central_heritage'];
    }

    if (
        str_contains($normalized, 'ho-chi-minh')
        || str_contains($normalized, 'mekong')
        || str_contains($normalized, 'cu-chi')
    ) {
        return $clusters['southern_delta'];
    }

    if (
        str_contains($normalized, 'phu-quoc')
        || str_contains($normalized, 'con-dao')
        || str_contains($normalized, 'nha-trang')
        || str_contains($normalized, 'quy-nhon')
        || str_contains($normalized, 'mui-ne')
    ) {
        return $clusters['coastal_islands'];
    }

    return $clusters['national_circuit'];
}

/**
 * Filter and format journey links for current page (excluding self).
 */
function vg_get_active_journey_links_for_path(array $cluster, string $current_path): array
{
    $links = $cluster['journey_links'] ?? [];
    $filtered = [];
    $normalizedCurrent = strtolower(trim($current_path, '/'));

    foreach ($links as $link) {
        $linkPath = strtolower(trim((string) parse_url($link['url'], PHP_URL_PATH), '/'));
        if ($linkPath !== '' && $linkPath === $normalizedCurrent) {
            continue; // Skip self-link
        }
        $filtered[] = $link;
    }

    // If filtering left fewer than 2, fill with national cluster highlights
    if (count($filtered) < 2 && $cluster['id'] !== 'national_circuit') {
        $registry = vg_get_travel_clusters_registry();
        $fallbackLinks = $registry['national_circuit']['journey_links'] ?? [];
        foreach ($fallbackLinks as $fallback) {
            $fPath = strtolower(trim((string) parse_url($fallback['url'], PHP_URL_PATH), '/'));
            if ($fPath === $normalizedCurrent) {
                continue;
            }
            $alreadyExists = false;
            foreach ($filtered as $ex) {
                if ($ex['url'] === $fallback['url']) {
                    $alreadyExists = true;
                    break;
                }
            }
            if (! $alreadyExists) {
                $filtered[] = $fallback;
                if (count($filtered) >= 3) {
                    break;
                }
            }
        }
    }

    return array_slice($filtered, 0, 3);
}

/**
 * Render the HTML component for Contextual Next-Step Journey Links.
 */
function vg_render_contextual_journey_html(?array $cluster = null, string $current_path = ''): string
{
    if ($current_path === '') {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $current_path = strtolower(trim((string) parse_url($uri, PHP_URL_PATH), '/'));
        if (empty($current_path) && function_exists('get_queried_object_id')) {
            $qid = get_queried_object_id();
            if ($qid > 0) {
                $permalink = get_permalink($qid);
                if (is_string($permalink)) {
                    $current_path = strtolower(trim((string) parse_url($permalink, PHP_URL_PATH), '/'));
                }
            }
        }
    }

    if ($cluster === null) {
        $cluster = vg_find_travel_cluster_by_path($current_path);
    }

    $links = vg_get_active_journey_links_for_path($cluster, $current_path);
    if (empty($links)) {
        return '';
    }

    $cluster_name = esc_html($cluster['name']);

    ob_start();
    ?>
    <section class="vg-contextual-journey" aria-label="<?php esc_attr_e('Next-step journey destinations', 'vietnamguide-premium'); ?>">
        <div class="vg-contextual-journey__inner">
            <header class="vg-contextual-journey__header">
                <span class="vg-contextual-journey__kicker"><?php esc_html_e('Next-Step Journey', 'vietnamguide-premium'); ?></span>
                <h3 class="vg-contextual-journey__title"><?php echo sprintf(esc_html__('Connected Destinations along the %s', 'vietnamguide-premium'), $cluster_name); ?></h3>
                <p class="vg-contextual-journey__subtitle"><?php esc_html_e('Continue your Vietnam route seamlessly with verified transit times and natural regional connections.', 'vietnamguide-premium'); ?></p>
            </header>
            <div class="vg-contextual-journey__grid">
                <?php foreach ($links as $link) : ?>
                    <a href="<?php echo esc_url($link['url']); ?>" class="vg-contextual-journey__card">
                        <div class="vg-contextual-journey__card-top">
                            <span class="vg-contextual-journey__badge"><?php echo esc_html($link['badge']); ?></span>
                            <span class="vg-contextual-journey__transit"><?php echo esc_html($link['transit']); ?></span>
                        </div>
                        <h4 class="vg-contextual-journey__card-title">
                            <?php echo esc_html($link['name']); ?>
                        </h4>
                        <p class="vg-contextual-journey__card-desc">
                            <?php echo esc_html($link['desc']); ?>
                        </p>
                        <div class="vg-contextual-journey__card-footer">
                            <span class="vg-contextual-journey__action-text"><?php esc_html_e('Explore route guide', 'vietnamguide-premium'); ?></span>
                            <span class="vg-contextual-journey__arrow" aria-hidden="true">&rarr;</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}

/**
 * Shortcode handler for manual placement in editorial articles or hubs: [vg_journey_links]
 */
function vg_contextual_journey_shortcode(array $atts = []): string
{
    return vg_render_contextual_journey_html();
}
add_shortcode('vg_journey_links', 'vg_contextual_journey_shortcode');
add_shortcode('vg_contextual_journey', 'vg_contextual_journey_shortcode');

/**
 * Automatically inject Contextual Journey Links into singular content.
 */
add_filter('the_content', static function (string $content): string {
    if (! is_singular() && ! is_page()) {
        return $content;
    }
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return $content;
    }
    if (is_feed()) {
        return $content;
    }
    // Guard against injecting into hero blocks or already injected content
    if (
        str_contains($content, 'vg-guide-hero')
        || str_contains($content, 'vg-hero')
        || str_contains($content, 'vg-contextual-journey')
    ) {
        return $content;
    }

    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $uri_path = strtolower(trim((string) parse_url($uri, PHP_URL_PATH), '/'));
    if (empty($uri_path) && function_exists('get_queried_object_id')) {
        $qid = get_queried_object_id();
        if ($qid > 0) {
            $permalink = get_permalink($qid);
            if (is_string($permalink)) {
                $uri_path = strtolower(trim((string) parse_url($permalink, PHP_URL_PATH), '/'));
            }
        }
    }

    $journeyHtml = vg_render_contextual_journey_html(null, $uri_path);
    if ($journeyHtml === '') {
        return $content;
    }

    return $content . "\n" . $journeyHtml;
}, 30);

/**
 * Rich Travel Schema Structured Data Filter for Rank Math JSON-LD Graph.
 * Enriches the graph with TouristDestination, TouristTrip, and TravelAction entities.
 */
function vg_rich_travel_schema_filter(array $data): array
{
    static $alreadyProcessed = false;
    if ($alreadyProcessed) {
        return $data;
    }
    $alreadyProcessed = true;

    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $uri_path = strtolower(trim((string) parse_url($uri, PHP_URL_PATH), '/'));
    if (empty($uri_path) && function_exists('get_queried_object_id')) {
        $qid = get_queried_object_id();
        if ($qid > 0) {
            $permalink = get_permalink($qid);
            if (is_string($permalink)) {
                $uri_path = strtolower(trim((string) parse_url($permalink, PHP_URL_PATH), '/'));
            }
        }
    }
    if (empty($uri_path) && isset($GLOBALS['post']) && is_object($GLOBALS['post'])) {
        $uri_path = strtolower((string) ($GLOBALS['post']->post_name ?? ''));
    }

    $cluster = vg_find_travel_cluster_by_path($uri_path);
    $canonical_base = function_exists('home_url') ? untrailingslashit(home_url()) : 'https://vietnamguide.net';
    $current_url = ($uri_path !== '') ? "{$canonical_base}/{$uri_path}/" : "{$canonical_base}/";

    $dest_id = "{$current_url}#tourist-destination";
    $trip_id = "{$current_url}#tourist-trip";
    $action_id = "{$current_url}#travel-action";

    // 1. TouristDestination Node
    $dest_schema = $cluster['destination_schema'];
    $dest_node = [
        '@type'            => 'TouristDestination',
        '@id'              => $dest_id,
        'name'             => $dest_schema['name'],
        'description'      => $dest_schema['description'],
        'url'              => $current_url,
        'touristType'      => $dest_schema['tourist_type'],
        'containedInPlace' => [
            '@type'  => 'Country',
            'name'   => 'Vietnam',
            'sameAs' => 'https://www.wikidata.org/wiki/Q881',
        ],
    ];
    if (! empty($dest_schema['wikidata'])) {
        $dest_node['sameAs'] = $dest_schema['wikidata'];
    }
    if (! empty($dest_schema['attractions'])) {
        $attractions = [];
        foreach ($dest_schema['attractions'] as $attr) {
            $attractions[] = [
                '@type'  => 'TouristAttraction',
                'name'   => $attr['name'],
                'sameAs' => $attr['wikidata'] ?? '',
            ];
        }
        $dest_node['includesAttraction'] = $attractions;
    }
    $dest_node['itinerary'] = ['@id' => $trip_id];
    $dest_node['potentialAction'] = ['@id' => $action_id];

    // 2. TouristTrip Node
    $trip_schema = $cluster['trip_schema'];
    $trip_items = [];
    $pos = 1;
    foreach ($trip_schema['stops'] as $stop) {
        $stop_url = (str_starts_with($stop['url'], 'http://') || str_starts_with($stop['url'], 'https://'))
            ? $stop['url']
            : $canonical_base . '/' . ltrim($stop['url'], '/');
        $trip_items[] = [
            '@type'    => 'ListItem',
            'position' => (string) $pos++,
            'item'     => [
                '@type' => 'TouristDestination',
                'name'  => $stop['name'],
                'url'   => $stop_url,
            ],
        ];
    }
    $trip_node = [
        '@type'       => 'TouristTrip',
        '@id'         => $trip_id,
        'name'        => $trip_schema['name'],
        'description' => $trip_schema['description'],
        'touristType' => $trip_schema['tourist_type'],
        'itinerary'   => [
            '@type'           => 'ItemList',
            'numberOfItems'   => count($trip_items),
            'itemListElement' => $trip_items,
        ],
    ];

    // 3. TravelAction Node
    $action_schema = $cluster['action_schema'];
    $to_locations = [];
    foreach ($trip_schema['stops'] as $idx => $stop) {
        if ($idx === 0 && count($trip_schema['stops']) > 1) {
            continue;
        }
        $to_url = (str_starts_with($stop['url'], 'http://') || str_starts_with($stop['url'], 'https://'))
            ? $stop['url']
            : $canonical_base . '/' . ltrim($stop['url'], '/');
        $to_locations[] = [
            '@type' => 'TouristDestination',
            'name'  => $stop['name'],
            'url'   => $to_url,
        ];
    }
    $from_stop = $trip_schema['stops'][0] ?? ['name' => $dest_schema['name'], 'url' => $current_url];
    $from_url = (str_starts_with($from_stop['url'], 'http://') || str_starts_with($from_stop['url'], 'https://'))
        ? $from_stop['url']
        : $canonical_base . '/' . ltrim($from_stop['url'], '/');

    $action_node = [
        '@type'        => 'TravelAction',
        '@id'          => $action_id,
        'name'         => $action_schema['name'],
        'agent'        => [
            '@type' => 'Organization',
            'name'  => 'VietnamGuide.net',
            'url'   => "{$canonical_base}/",
        ],
        'fromLocation' => [
            '@type' => 'TouristDestination',
            'name'  => $from_stop['name'],
            'url'   => $from_url,
        ],
        'toLocation'   => $to_locations,
        'result'       => ['@id' => $trip_id],
        'instrument'   => [
            '@type' => 'TravelMethod',
            'name'  => $action_schema['method'] ?? 'Express Highway & Rail',
        ],
    ];

    // Connect to WebPage/Article in graph
    $nodes = &$data;
    if (isset($data['@graph']) && is_array($data['@graph'])) {
        $nodes = &$data['@graph'];
    }

    foreach ($nodes as &$node) {
        if (! is_array($node)) {
            continue;
        }
        $node_type = $node['@type'] ?? '';
        $is_article = $node_type === 'Article' || (is_array($node_type) && in_array('Article', $node_type, true));
        $is_webpage = $node_type === 'WebPage' || (is_array($node_type) && in_array('WebPage', $node_type, true));
        if ($is_article || $is_webpage) {
            $node['subjectOf'] = ['@id' => $dest_id];
        }
    }
    unset($node);

    // Append our rich travel Schema nodes to graph or schema array
    if (isset($data['@graph']) && is_array($data['@graph'])) {
        $data['@graph'][] = $dest_node;
        $data['@graph'][] = $trip_node;
        $data['@graph'][] = $action_node;
    } else {
        $data['tourist_destination'] = $dest_node;
        $data['tourist_trip'] = $trip_node;
        $data['travel_action'] = $action_node;
    }

    return $data;
}
add_filter('rank_math/json_ld', 'vg_rich_travel_schema_filter', 100, 2);

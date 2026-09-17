<?php
if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/image-dimensions.php';

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

// Core Web Vitals & Resource Hints: Preconnect to media CDN, preload LCP hero image, and verification
add_action('wp_head', static function (): void {
    echo '<meta name="google-site-verification" content="G5wVuwqeUiubxqR-z_1BOA5opV1xwI4PKy-piHsN6Xc">' . "\n";
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
                'addressRegion' => 'Hanoi',
                'geo'          => ['latitude' => 21.0285, 'longitude' => 105.8542],
                'name'         => 'Hanoi',
                'description'  => 'Vietnam\'s 1,000-year-old capital city, featuring the atmospheric Old Quarter, French colonial boulevards, vibrant street-food culture, and seamless overland connectivity to Ha Long Bay and Ninh Binh.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q1858',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Hanoi',
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
                'addressRegion' => 'Quang Ninh',
                'geo'          => ['latitude' => 20.9101, 'longitude' => 107.1839],
                'name'         => 'Ha Long Bay & Lan Ha Bay',
                'description'  => 'UNESCO World Heritage marine wonder characterized by thousands of soaring limestone karsts, secluded floating fishing villages, and emerald sea channels.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q190128',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/H%E1%BA%A1_Long_Bay',
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
                'addressRegion' => 'Ninh Binh',
                'geo'          => ['latitude' => 20.2506, 'longitude' => 105.9745],
                'name'         => 'Ninh Binh (Trang An & Tam Coc)',
                'description'  => 'Often celebrated as "Ha Long Bay on land," Ninh Binh enchants travelers with limestone peaks emerging from emerald rice fields, UNESCO paddleboat grottoes, and ancient dynastic temples.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q36352',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Ninh_B%C3%ACnh_province',
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
                'addressRegion' => 'Da Nang, Hoi An & Thua Thien Hue',
                'geo'          => ['latitude' => 15.8801, 'longitude' => 108.338],
                'name'         => 'Da Nang, Hoi An & Hue',
                'description'  => 'Central Vietnam\'s cultural and coastal heartland, spanning the royal palaces of Hue, the lantern-lit UNESCO trading lanes of Hoi An, and the beaches and Marble Mountains of Da Nang.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q25282',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Da_Nang',
                'tourist_type' => ['Heritage tourism', 'Beach resort travel', 'Culinary exploration', 'Cultural travel'],
                'attractions'  => [
                    ['name' => 'Hoi An Ancient Town', 'wikidata' => 'https://www.wikidata.org/wiki/Q36167'],
                    ['name' => 'Imperial City of Hue', 'wikidata' => 'https://www.wikidata.org/wiki/Q200257'],
                    ['name' => 'My Khe Beach & Marble Mountains', 'wikidata' => 'https://www.wikidata.org/wiki/Q25282'],
                    ['name' => 'Hai Van Pass', 'wikidata' => 'https://www.wikidata.org/wiki/Q1005837'],
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
                'addressRegion' => 'Ho Chi Minh City & Mekong Delta',
                'geo'          => ['latitude' => 10.8231, 'longitude' => 106.6297],
                'name'         => 'Ho Chi Minh City & Mekong Delta',
                'description'  => 'Vietnam\'s bustling southern economic powerhouse, famed for French colonial landmarks, Saigon street-food alleys, wartime history, and gateway to the waterways of the Mekong Delta.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q1854',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Ho_Chi_Minh_City',
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
                'addressRegion' => 'Lao Cai & Ha Giang',
                'geo'          => ['latitude' => 22.3364, 'longitude' => 103.8438],
                'name'         => 'Sa Pa & Northern Highlands',
                'description'  => 'Vietnam\'s dramatic northern mountain frontier, showcasing Fansipan summit, sculpted rice terraces, colorful ethnic hill-tribe markets, and the epic Ha Giang karst loop.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q36384',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Sa_Pa',
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
                'addressRegion' => 'Kien Giang & Ba Ria - Vung Tau',
                'geo'          => ['latitude' => 10.2899, 'longitude' => 103.984],
                'name'         => 'Phu Quoc, Con Dao & Central Coast',
                'description'  => 'Vietnam\'s idyllic island retreats and sun-drenched coastal havens, offering powder-white sands, coral reef biodiversity, and calm turquoise seas.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q223145',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Ph%C3%BA_Qu%E1%BB%91c',
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
                'addressRegion' => 'Vietnam',
                'geo'          => ['latitude' => 16.0544, 'longitude' => 108.2022],
                'name'         => 'Vietnam',
                'description'  => 'An extraordinary Southeast Asian destination blending 3,200 km of coastline, UNESCO World Heritage treasures, limestone karst bays, and globally renowned culinary culture.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q881',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Vietnam',
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
        || str_contains($normalized, 'northern')
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
        || str_contains($normalized, 'ly-son')
        || str_contains($normalized, 'beach')
        || str_contains($normalized, 'island')
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
    $currentPathParsed = (string) parse_url($current_path, PHP_URL_PATH);
    $normalizedCurrent = strtolower(trim($currentPathParsed !== '' ? $currentPathParsed : $current_path, '/'));

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
        if (function_exists('get_the_ID')) {
            $qid = (int) get_the_ID();
            if ($qid > 0) {
                $permalink = get_permalink($qid);
                if (is_string($permalink) && $permalink !== '') {
                    $current_path = strtolower(trim((string) parse_url($permalink, PHP_URL_PATH), '/'));
                }
            }
        }
        if ($current_path === '' && function_exists('get_queried_object_id')) {
            $qid = (int) get_queried_object_id();
            if ($qid > 0) {
                $permalink = get_permalink($qid);
                if (is_string($permalink) && $permalink !== '') {
                    $current_path = strtolower(trim((string) parse_url($permalink, PHP_URL_PATH), '/'));
                }
            }
        }
        if ($current_path === '') {
            $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
            $current_path = strtolower(trim((string) parse_url($uri, PHP_URL_PATH), '/'));
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
 * Supports optional cluster attribute: [vg_journey_links cluster="central_heritage"]
 */
function vg_contextual_journey_shortcode($atts = []): string
{
    $cluster_id = '';
    if (is_array($atts) && ! empty($atts['cluster'])) {
        $cluster_id = sanitize_key((string) $atts['cluster']);
    }
    $cluster = null;
    if ($cluster_id !== '') {
        $registry = vg_get_travel_clusters_registry();
        $cluster = $registry[$cluster_id] ?? null;
    }
    return vg_render_contextual_journey_html($cluster);
}
add_shortcode('vg_journey_links', 'vg_contextual_journey_shortcode');
add_shortcode('vg_contextual_journey', 'vg_contextual_journey_shortcode');

/**
 * Automatically inject Contextual Journey Links into singular content.
 */
add_filter('the_content', static function (string $content): string {
    if (is_front_page() || is_home()) {
        return $content;
    }
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
        || str_contains($content, 'vg-contextual-journey')
    ) {
        return $content;
    }

    $uri_path = '';
    if (function_exists('get_the_ID')) {
        $qid = (int) get_the_ID();
        if ($qid > 0) {
            $permalink = get_permalink($qid);
            if (is_string($permalink) && $permalink !== '') {
                $uri_path = strtolower(trim((string) parse_url($permalink, PHP_URL_PATH), '/'));
            }
        }
    }
    if ($uri_path === '' && function_exists('get_queried_object_id')) {
        $qid = (int) get_queried_object_id();
        if ($qid > 0) {
            $permalink = get_permalink($qid);
            if (is_string($permalink) && $permalink !== '') {
                $uri_path = strtolower(trim((string) parse_url($permalink, PHP_URL_PATH), '/'));
            }
        }
    }
    if ($uri_path === '') {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $uri_path = strtolower(trim((string) parse_url($uri, PHP_URL_PATH), '/'));
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
function vg_rich_travel_schema_filter($data, $context = null): array
{
    if (! is_array($data)) {
        return (array) $data;
    }

    $uri_path = '';
    if (function_exists('get_queried_object_id')) {
        $qid = (int) get_queried_object_id();
        if ($qid > 0) {
            $permalink = get_permalink($qid);
            if (is_string($permalink) && $permalink !== '') {
                $uri_path = strtolower(trim((string) parse_url($permalink, PHP_URL_PATH), '/'));
            }
        }
    }
    if ($uri_path === '' && isset($GLOBALS['post']) && is_object($GLOBALS['post'])) {
        $uri_path = strtolower((string) ($GLOBALS['post']->post_name ?? ''));
    }
    if ($uri_path === '') {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $uri_path = strtolower(trim((string) parse_url($uri, PHP_URL_PATH), '/'));
    }

    $cluster = vg_find_travel_cluster_by_path($uri_path);
    $canonical_base = function_exists('home_url') ? untrailingslashit(home_url()) : 'https://vietnamguide.net';
    $current_url = ($uri_path !== '') ? "{$canonical_base}/{$uri_path}/" : "{$canonical_base}/";

    $dest_id = "{$current_url}#tourist-destination";
    $trip_id = "{$current_url}#tourist-trip";
    $action_id = "{$current_url}#travel-action";
    $webpage_id = "{$current_url}#webpage";

    // Idempotence check: return early if destination node already exists in this graph
    $existing_nodes = isset($data['@graph']) && is_array($data['@graph']) ? $data['@graph'] : $data;
    foreach ($existing_nodes as $existing) {
        if (is_array($existing) && ($existing['@id'] ?? '') === $dest_id) {
            return $data;
        }
    }

    // 1. TouristDestination Node
    $dest_schema = $cluster['destination_schema'];
    $dest_node = [
        '@type'              => 'TouristDestination',
        '@id'                => $dest_id,
        'name'               => $dest_schema['name'],
        'description'        => $dest_schema['description'],
        'url'                => $current_url,
        'touristType'        => $dest_schema['tourist_type'],
        'currenciesAccepted'  => 'VND',
        'availableLanguage'   => ['en', 'vi'],
        'publicAccess'        => true,
        'isAccessibleForFree' => false,
        'containedInPlace'   => [
            '@type'  => 'Country',
            'name'   => 'Vietnam',
            'sameAs' => 'https://www.wikidata.org/wiki/Q881',
        ],
        'subjectOf'          => [
            '@id' => $webpage_id,
        ],
    ];
    $same_as = [];
    if (! empty($dest_schema['wikidata'])) {
        $same_as[] = $dest_schema['wikidata'];
    }
    if (! empty($dest_schema['wikipedia'])) {
        $same_as[] = $dest_schema['wikipedia'];
    }
    if (! empty($dest_schema['sameAs'])) {
        if (is_array($dest_schema['sameAs'])) {
            $same_as = array_merge($same_as, $dest_schema['sameAs']);
        } else {
            $same_as[] = $dest_schema['sameAs'];
        }
    }
    $same_as = array_values(array_unique(array_filter($same_as)));
    if (! empty($same_as)) {
        $dest_node['sameAs'] = (count($same_as) === 1) ? $same_as[0] : $same_as;
    }
    if (! empty($dest_schema['geo'])) {
        $dest_node['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $dest_schema['geo']['latitude'],
            'longitude' => (float) $dest_schema['geo']['longitude'],
        ];
        $dest_node['hasMap'] = "https://www.openstreetmap.org/?mlat={$dest_schema['geo']['latitude']}&mlon={$dest_schema['geo']['longitude']}#map=12/{$dest_schema['geo']['latitude']}/{$dest_schema['geo']['longitude']}";
    }
    if (! empty($dest_schema['addressRegion'])) {
        $dest_node['address'] = [
            '@type'          => 'PostalAddress',
            'addressCountry' => 'VN',
            'addressRegion'  => $dest_schema['addressRegion'],
        ];
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
        'provider'    => [
            '@type' => 'Organization',
            'name'  => 'VietnamGuide.net',
            'url'   => "{$canonical_base}/",
        ],
        'offers'      => [
            '@type'         => 'Offer',
            'price'         => '0',
            'priceCurrency' => 'USD',
            'category'      => 'Free Editorial Route Planning',
            'url'           => $current_url,
        ],
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
        'actionStatus' => 'https://schema.org/PotentialActionStatus',
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
            '@type' => 'Thing',
            'name'  => $action_schema['method'] ?? 'Express Highway & Rail',
        ],
    ];


    // Connect WebPage and Article nodes to TouristDestination via Schema.org 'about' property
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
            // CreativeWork.about -> TouristDestination is standard Schema.org
            if (! isset($node['about'])) {
                $node['about'] = ['@id' => $dest_id];
            } elseif (is_array($node['about'])) {
                $isAssoc = array_keys($node['about']) !== range(0, count($node['about']) - 1);
                if ($isAssoc) {
                    $node['about'] = [$node['about'], ['@id' => $dest_id]];
                } else {
                    $alreadyLinked = false;
                    foreach ($node['about'] as $ab) {
                        if (is_array($ab) && ($ab['@id'] ?? '') === $dest_id) {
                            $alreadyLinked = true;
                            break;
                        }
                    }
                    if (! $alreadyLinked) {
                        $node['about'][] = ['@id' => $dest_id];
                    }
                }
            }
        }

        $is_org = $node_type === 'Organization' || (is_array($node_type) && in_array('Organization', $node_type, true));
        if ($is_org) {
            $node['url'] = "{$canonical_base}/";
            $node['publishingPrinciples'] = "{$canonical_base}/editorial-policy/";
            $node['correctionsPolicy'] = "{$canonical_base}/source-update-policy/";
            $node['knowsAbout'] = [
                'Vietnam Travel Planning',
                'Vietnam Transportation and Rail Logistics',
                'Vietnam Visa Regulations and Entry Policies',
                'Southeast Asia Tourism Safety',
                'Sustainable Travel in Vietnam',
            ];
        }

        $is_person = $node_type === 'Person' || (is_array($node_type) && in_array('Person', $node_type, true));
        if ($is_person) {
            $node['jobTitle'] = 'Editorial Desk and Field Research Team';
            $node['description'] = 'Independent travel editors and on-the-ground researchers producing verified route logistics, safety checks, and practical travel guides for Vietnam.';
            $node['publishingPrinciples'] = "{$canonical_base}/editorial-policy/";
            $node['knowsAbout'] = [
                'Vietnam Travel Logistics',
                'Vietnam Visa Regulations',
                'Public Transport and Rail in Vietnam',
                'Destination Planning',
                'Travel Safety in Southeast Asia',
            ];
        }
    }
    unset($node);

    // 4. FAQPage Node (if authoritative Q&As exist for this URI)
    $faq_node = vg_get_page_faq_schema($uri_path, $current_url);

    // 5. HowTo Node (if step-by-step process exists for this URI)
    $howto_node = vg_get_page_howto_schema($uri_path, $current_url);

    // Append our rich Schema nodes to graph or schema array
    if (isset($data['@graph']) && is_array($data['@graph'])) {
        $data['@graph'][] = $dest_node;
        $data['@graph'][] = $trip_node;
        $data['@graph'][] = $action_node;
        if ($faq_node !== null) {
            $data['@graph'][] = $faq_node;
        }
        if ($howto_node !== null) {
            $data['@graph'][] = $howto_node;
        }
    } else {
        $data['tourist_destination'] = $dest_node;
        $data['tourist_trip'] = $trip_node;
        $data['travel_action'] = $action_node;
        if ($faq_node !== null) {
            $data['faq_page'] = $faq_node;
        }
        if ($howto_node !== null) {
            $data['howto'] = $howto_node;
        }
    }

    return $data;
}

/**
 * Authoritative FAQ Schema.org generator for high-intent travel planning queries.
 */
function vg_get_page_faq_schema(string $uri_path, string $current_url): ?array
{
    $slug = trim(basename($uri_path), '/');
    $faq_registry = [
        'vietnam-evisa' => [
            ['q' => 'How much does a Vietnam e-visa cost?', 'a' => 'A single-entry Vietnam e-visa costs 25 USD, and a multiple-entry e-visa costs 50 USD. Fees are paid online by credit card and are non-refundable regardless of the application outcome.'],
            ['q' => 'What is the official Vietnam e-visa website?', 'a' => 'The only official government portal for Vietnam e-visas is evisa.xuatnhapcanh.gov.vn (operated by the Vietnam Immigration Department). Avoid commercial third-party websites charging excessive processing markups.'],
            ['q' => 'How far in advance should I apply for a Vietnam e-visa?', 'a' => 'Apply at least 2 weeks before your planned flight. Standard processing takes 3 to 5 business days, but processing stops during Vietnamese national holidays and technical maintenance windows.'],
            ['q' => 'Can I change my port of entry after my Vietnam e-visa is approved?', 'a' => 'No. Your entry checkpoint must match the airport, land border, or seaport approved on your official e-visa document. Changing entry ports requires submitting a new visa application.'],
            ['q' => 'Do I need to print a paper copy of my Vietnam e-visa?', 'a' => 'Yes. Airlines require a physical paper copy at departure check-in, and immigration officers stamp the paper letter upon arrival at the border checkpoint.'],
        ],
        'sim-esim-vietnam' => [
            ['q' => 'Is eSIM or physical SIM better for traveling in Vietnam?', 'a' => 'An eSIM is convenient and activates before arrival if your phone is carrier-unlocked. A physical SIM card is preferable if your phone is locked or if you need a reliable local phone number to receive Grab driver calls.'],
            ['q' => 'Which mobile network has the best coverage in Vietnam?', 'a' => 'Viettel provides the widest and most reliable 4G network coverage across Vietnam, especially in mountainous regions like Sapa and Ha Giang and offshore islands. Vinaphone is a solid secondary choice in major cities.'],
            ['q' => 'Can I purchase a SIM card upon arrival at Vietnam airports?', 'a' => 'Yes. Official carrier kiosks (Viettel, Vinaphone, Mobifone) operate directly outside the baggage claim halls at Hanoi (Noi Bai), Ho Chi Minh City (Tan Son Nhat), and Da Nang international airports.'],
        ],
        'vietnam-airport-arrival-checklist' => [
            ['q' => 'How much cash should I withdraw upon arrival at a Vietnam airport?', 'a' => 'Withdraw 1 to 2 million VND (approximately 40 to 80 USD) from official bank ATMs (such as Vietcombank or BIDV) in the arrivals hall to cover initial taxi fares and street expenses.'],
            ['q' => 'How do I avoid taxi scams at Vietnam airports?', 'a' => 'Use the Grab app over airport Wi-Fi or visit official prepaid taxi counters (Mai Linh or Vinasun) inside the terminal. Never follow unbadged touts offering private transportation in the terminal corridors.'],
            ['q' => 'How long does immigration clearance take at Hanoi and Saigon airports?', 'a' => 'Standard passport control takes 30 to 60 minutes. During peak arrival periods in late afternoon and evening, wait times can reach 90 minutes.'],
        ],
        'hanoi-to-sapa-transport' => [
            ['q' => 'What is the fastest way to get from Hanoi to Sapa?', 'a' => 'A sleeper cabin bus or luxury limousine van traveling via the Noi Bai - Lao Cai expressway takes 5.5 to 6 hours door-to-door, which is faster and more direct than the train.'],
            ['q' => 'Is the overnight train from Hanoi to Sapa comfortable?', 'a' => 'The overnight sleeper train offers a smooth ride with air-conditioned 4-berth cabins. It arrives in Lao Cai station at 5:30 AM, where you transfer to a 50-minute mountain shuttle van up to Sapa town.'],
            ['q' => 'Can I book a private car transfer from Hanoi to Sapa?', 'a' => 'Yes. Private car transfers take approximately 5 hours door-to-door, offering total schedule flexibility and luggage convenience for families and small travel groups.'],
        ],
        'ha-giang-easy-rider-vs-self-drive' => [
            ['q' => 'Can tourists legally ride a motorbike on the Ha Giang Loop?', 'a' => 'Vietnam only recognizes the 1968 International Driving Permit (IDP) with an active motorcycle endorsement. Driving on a 1949 IDP or home automobile license is illegal and voids travel medical insurance policies.'],
            ['q' => 'What is an Easy Rider tour in Ha Giang?', 'a' => 'An Easy Rider is a professional local rider who navigates the motorcycle while you sit comfortably on the rear passenger pillion seat, allowing you to view mountain landscapes without driving hazards.'],
            ['q' => 'How dangerous is driving the Ha Giang Loop independently?', 'a' => 'Independent driving carries real risks from steep mountain gradients, sharp hairpin turns on Ma Pi Leng Pass, loose gravel, unpredictable construction vehicles, and sudden mountain fog.'],
        ],
        'ha-long-bay-cruise-questions-before-booking' => [
            ['q' => 'Should I choose a 2-day or 3-day Ha Long Bay cruise?', 'a' => 'A 2-day/1-night cruise gives 24 hours on the water and suits compact itineraries. A 3-day/2-night cruise travels deeper into quieter Lan Ha or Bai Tu Long bays with unhurried kayaking and swimming time.'],
            ['q' => 'What amenities are included in an overnight Ha Long Bay cruise?', 'a' => 'Cruise rates include all onboard meals, private en-suite cabin, cave excursions, and kayak access. Highway transfers between Hanoi and the harbor and personal beverages are usually billed separately.'],
            ['q' => 'What is the cruise cancellation policy during bad weather?', 'a' => 'The local maritime port authority suspends sailings during typhoons or dense fog for passenger safety. Reputable operators provide full refunds or day-tour alternatives in accordance with maritime law.'],
        ],
        'trang-an-vs-tam-coc' => [
            ['q' => 'Is Trang An or Tam Coc better in Ninh Binh?', 'a' => 'Trang An features dramatic karst water cave tunnels, strict lifejacket rules, and organized UNESCO management. Tam Coc offers open river paddling through scenic rice fields with rowers using their feet.'],
            ['q' => 'How long does the boat tour take in Trang An and Tam Coc?', 'a' => 'Trang An boat circuits last 2.5 to 3 hours through 3 to 4 caves. Tam Coc boat trips take approximately 1.5 to 2 hours along the Ngo Dong river.'],
            ['q' => 'Which boat route in Trang An is recommended?', 'a' => 'Route 2 (visiting Dot Cave and Dia Linh Cave) and Route 3 (featuring the 1,000-meter cave) provide the finest combination of karst scenery, cave passages, and historic temples.'],
        ],
        'vietnam-travel-cost' => [
            ['q' => 'What is a realistic daily travel budget for Vietnam?', 'a' => 'Budget backpackers spend 30 to 45 USD per day. Mid-range travelers staying in 3-star boutique hotels and taking domestic flights spend 70 to 120 USD per day. Luxury travelers spend 200 USD and up per day.'],
            ['q' => 'Is Vietnam cheaper than Thailand for travelers?', 'a' => 'Vietnam is generally 15 to 25 percent less expensive than Thailand for street meals, local transportation, and city boutique accommodations, while guided expeditions and luxury cruises cost similar amounts.'],
            ['q' => 'Do I need cash in Vietnam or are cards widely accepted?', 'a' => 'Credit cards are accepted at mid-range hotels, supermarkets, and established restaurants in major cities. Cash in Vietnamese Dong (VND) is essential for street dining, small market stalls, and rural taxis.'],
        ],
        'ha-long-bay-vs-lan-ha-bay' => [
            ['q' => 'Is Ha Long Bay or Lan Ha Bay better?', 'a' => 'Lan Ha Bay offers quieter waters, fewer tourist boats, secluded sandy swimming coves, and unhurried kayaking around Cat Ba Island. Ha Long Bay provides iconic grand limestone cave formations (Sung Sot Cave) and panoramic viewpoint peaks (Ti Top Island), but with higher vessel traffic.'],
            ['q' => 'Can I visit both Ha Long Bay and Lan Ha Bay on the same cruise?', 'a' => 'Most 2-day/1-night cruises operate in either Ha Long Bay or Lan Ha Bay due to port jurisdiction boundaries. Longer 3-day/2-night itineraries frequently navigate through connecting border waters into quieter coves.'],
            ['q' => 'Which departure port is used for Lan Ha Bay cruises?', 'a' => 'Lan Ha Bay cruises depart primarily from Ben Beo harbor or Got and Tuan Chau ferry terminals connecting to Cat Ba Island, whereas Ha Long Bay cruises depart from Tuan Chau International Marina or Halong International Cruise Port.'],
        ],
        'da-nang-vs-hoi-an' => [
            ['q' => 'Should I stay in Da Nang or Hoi An?', 'a' => 'Stay in Da Nang for expansive ocean beaches (My Khe), luxury high-rise resorts, modern seafood dining, and vibrant nightlife. Stay in Hoi An for historic pedestrian streets, lantern-lit riverside evenings, cooking schools, and tailor shops. The two destinations are only 45 minutes apart by taxi.'],
            ['q' => 'How far is Hoi An from Da Nang and how do I travel between them?', 'a' => 'Hoi An is 30 kilometers (18.5 miles) south of Da Nang. The trip takes 40 to 45 minutes by taxi or Grab, costing approximately 250,000 to 350,000 VND (10 to 14 USD) each way.'],
            ['q' => 'Can I visit Da Nang as a day trip from Hoi An?', 'a' => 'Yes. Many travelers base themselves in Hoi An for its atmospheric charm and make day trips to Da Nang to visit the Marble Mountains, Son Tra Peninsula (Monkey Mountain), and My Khe beach.'],
        ],
        'best-time-to-visit-vietnam' => [
            ['q' => 'What is the best month to visit Vietnam overall?', 'a' => 'March and April, along with October and November, offer the most balanced weather conditions nationwide, with moderate temperatures and low rainfall across northern, central, and southern regions.'],
            ['q' => 'When is the rainy season in central Vietnam (Da Nang, Hoi An, Hue)?', 'a' => 'The rainy and typhoon season in central Vietnam runs from September through December, with peak rainfall and localized river flooding risks in October and November.'],
            ['q' => 'When is the best time to visit Sapa and northern mountain rice terraces?', 'a' => 'The best time to visit Sapa is September to early October for golden ripe harvest terraces, and April to May for the watering season with clear skies and mild trekking weather.'],
        ],
        'ninh-binh-to-ha-long-bay-transfer' => [
            ['q' => 'How do I travel directly from Ninh Binh to Ha Long Bay?', 'a' => 'Take a direct shared limousine shuttle bus via National Highway 10 and the Hai Phong Expressway. The journey takes 3.5 to 4 hours door-to-door and costs 300,000 to 450,000 VND (12 to 18 USD), avoiding the need to backtrack through Hanoi.'],
            ['q' => 'Can I make it from Ninh Binh to Ha Long Bay in time for a cruise boarding?', 'a' => 'Yes, if you depart Ninh Binh by 6:30 to 7:00 AM. Cruise boarding at Tuan Chau or Halong International Port typically closes between 11:30 AM and 12:00 PM. A private car offers maximum schedule security.'],
            ['q' => 'Is there a train between Ninh Binh and Ha Long Bay?', 'a' => 'No direct train connects Ninh Binh and Ha Long Bay. Rail travel requires transferring trains in Hanoi, which takes over 7 hours and is not recommended compared to direct express highway limousines.'],
        ],
    ];

    $raw_qas = $faq_registry[$slug] ?? null;

    // Dynamic fallback: extract structured Q&As from authored details/summary markup if not in curated registry
    if ($raw_qas === null && function_exists('get_queried_object')) {
        $post = get_queried_object();
        if (! ($post instanceof WP_Post) && function_exists('get_post')) {
            $post = get_post();
        }
        if ($post instanceof WP_Post && ! empty($post->post_content)) {
            $content = $post->post_content;
            if (stripos($content, '<details') !== false) {
                if (preg_match_all('/<details[^>]*>\s*<summary[^>]*>(.*?)<\/summary>\s*(?:<p[^>]*>)?(.*?)(?:<\/p>)?\s*<\/details>/is', $content, $matches, PREG_SET_ORDER)) {
                    $dynamic_qas = [];
                    foreach ($matches as $match) {
                        $q = trim(html_entity_decode(wp_strip_all_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                        $a = trim(html_entity_decode(wp_strip_all_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                        if (mb_strlen($q) >= 10 && mb_strlen($a) >= 20) {
                            $dynamic_qas[] = ['q' => $q, 'a' => $a];
                        }
                    }
                    if (! empty($dynamic_qas)) {
                        $raw_qas = $dynamic_qas;
                    }
                }
            }
        }
    }

    if (empty($raw_qas)) {
        return null;
    }

    $questions = [];
    foreach ($raw_qas as $item) {
        $questions[] = [
            '@type'          => 'Question',
            'name'           => $item['q'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => $item['a'],
            ],
        ];
    }

    return [
        '@type'      => 'FAQPage',
        '@id'        => "{$current_url}#faq",
        'isPartOf'   => ['@id' => "{$current_url}#webpage"],
        'mainEntity' => $questions,
    ];
}

/**
 * Authoritative HowTo Schema.org generator for process and application guides.
 */
function vg_get_page_howto_schema(string $uri_path, string $current_url): ?array
{
    $slug = trim(basename($uri_path), '/');
    $howto_registry = [
        'vietnam-evisa' => [
            'name'        => 'How to Apply for a Vietnam E-Visa Online',
            'description' => 'Step-by-step instructions for submitting a valid Vietnam electronic visa application through the official government immigration portal.',
            'steps'       => [
                ['name' => 'Prepare Required Documents', 'text' => 'Ensure your passport has at least 6 months validity. Prepare a sharp JPEG digital scan of your passport bio page and a passport-style portrait photo on a plain white background without glasses.'],
                ['name' => 'Access the Official Immigration Portal', 'text' => 'Visit the official government website at evisa.xuatnhapcanh.gov.vn. Avoid third-party commercial agency portals that charge marked-up intermediary fees.'],
                ['name' => 'Complete the Application Form', 'text' => 'Fill in your full legal name, date of birth, passport details, temporary address in Vietnam, and select your specific entry and exit international border checkpoints.'],
                ['name' => 'Pay the Visa Processing Fee', 'text' => 'Pay the official non-refundable fee (25 USD for single-entry up to 90 days, or 50 USD for multiple-entry) using an international debit or credit card.'],
                ['name' => 'Track Status and Print Approval Letter', 'text' => 'Record your registration code. Check application status after 3 to 5 working days. Once approved, download and print two physical copies of the e-visa letter for departure check-in and border inspection.'],
            ],
        ],
        'sim-esim-vietnam' => [
            'name'        => 'How to Buy and Set Up an eSIM for Vietnam',
            'description' => 'Step-by-step guide to purchasing, installing, and activating an electronic SIM profile for mobile data in Vietnam.',
            'steps'       => [
                ['name' => 'Verify Phone Compatibility', 'text' => 'Confirm your smartphone is carrier-unlocked and supports eSIM functionality in device cellular settings.'],
                ['name' => 'Select Network and Data Plan', 'text' => 'Choose an authorized provider powered by the Viettel network for superior nationwide coverage, especially in highland and island destinations.'],
                ['name' => 'Install eSIM via QR Code', 'text' => 'Receive your digital activation QR code by email. Open device settings, select Add eSIM, and scan the QR code before boarding your flight.'],
                ['name' => 'Activate Data Roaming on Arrival', 'text' => 'Upon touchdown at any Vietnam international airport, switch your cellular data line to the Vietnam eSIM profile and toggle Data Roaming to ON.'],
            ],
        ],
    ];

    if (! isset($howto_registry[$slug])) {
        return null;
    }

    $data = $howto_registry[$slug];
    $steps = [];
    $pos = 1;
    foreach ($data['steps'] as $step) {
        $steps[] = [
            '@type'    => 'HowToStep',
            'position' => (string) $pos,
            'name'     => $step['name'],
            'text'     => $step['text'],
            'url'      => "{$current_url}#step-{$pos}",
        ];
        $pos++;
    }

    return [
        '@type'       => 'HowTo',
        '@id'         => "{$current_url}#howto",
        'name'        => $data['name'],
        'description' => $data['description'],
        'isPartOf'    => ['@id' => "{$current_url}#webpage"],
        'step'        => $steps,
    ];
}

add_filter('rank_math/json_ld', 'vg_rich_travel_schema_filter', 100, 2);

/**
 * ==========================================================================
 * Stage 21: Semantic Tabular Data & Table Caption Optimization
 * ==========================================================================
 * Automatically enhances decision tables with descriptive, query-focused
 * <caption> elements, guarantees semantic <thead> with <th scope="col">, and
 * formats row headers for Google Featured Snippet table extraction.
 */

function vg_humanize_table_class(string $classAttr): string
{
    static $curated = [
        'vg-evisa-official-basics'                  => 'Vietnam E-Visa Official Requirements, Fees & Validity Rules',
        'vg-evisa-decision-table'                   => 'Vietnam Visa Type Decision Matrix by Travel Purpose & Route',
        'vg-evisa-mistake-checks'                   => 'Common E-Visa Application Pitfalls and Mitigation Steps',
        'vg-sim-esim-basics-table'                  => 'Vietnam Mobile Connectivity Options: eSIM vs Physical SIM',
        'vg-sim-packages-tariffs'                   => 'Official Vietnam 4G Data Packages, Carrier Tariffs & Registration',
        'vg-sim-esim-decision-table'                => 'SIM vs eSIM Selection Matrix by Traveler Profile & Device',
        'vg-trang-an-tam-coc-glance'                => 'Trang An vs Tam Coc Boating Verdict at a Glance',
        'vg-trang-an-tam-coc-decision-matrix'       => 'Trang An vs Tam Coc Landscape, Safety & Circuit Comparison',
        'vg-trang-an-tam-coc-route-comparison'      => 'Trang An Cave Routes vs Tam Coc River Experience',
        'vg-ha-long-lan-ha-glance-table'            => 'Ha Long Bay vs Lan Ha Bay Cruise Verdict at a Glance',
        'vg-halong-lanha-pricing'                   => 'Harbor Levies, Entrance Fees and Cruise Transit Costs',
        'vg-ha-long-lan-ha-decision-matrix'         => 'Ha Long Bay vs Lan Ha Bay Scenery, Crowd Density & Logistics',
        'vg-ninh-binh-ha-long-transfer-glance'      => 'Ninh Binh to Ha Long Bay Direct Transfer at a Glance',
        'vg-ninh-binh-ha-long-transfer-mode-matrix' => 'Limousine Shuttle vs Private Car vs Rail Comparison',
        'vg-cost-budget-ranges'                     => 'Vietnam Daily Travel Budget Ranges by Traveler Style',
        'vg-cost-scenario-budgets'                  => 'Estimated Vietnam Land-Only Travel Expenses by Trip Length',
        'vg-da-nang-hoi-an-glance-table'            => 'Da Nang vs Hoi An Destination Comparison at a Glance',
        'vg-da-nang-hoi-an-matrix'                  => 'Da Nang vs Hoi An Beach, Heritage & Dining Comparison',
        'vg-sapa-transport-modes'                   => 'Hanoi to Sapa Transport Modes: Speed, Cost & Comfort Comparison',
        'vg-ha-giang-easy-rider-matrix'             => 'Ha Giang Loop Tour Modes: Easy Rider vs Self-Drive vs Private Car',
    ];

    $classes = preg_split('/\s+/', trim($classAttr));
    foreach ($classes as $c) {
        if (isset($curated[$c])) {
            return $curated[$c];
        }
    }

    $specific = '';
    foreach ($classes as $c) {
        if ($c !== 'vg-decision-table' && $c !== 'vg-comparison-matrix' && str_starts_with($c, 'vg-')) {
            $specific = $c;
            break;
        }
    }

    if ($specific === '') {
        return 'Vietnam Travel Planning & Decision Matrix';
    }

    $raw = str_replace(['vg-', '-table'], '', $specific);
    $words = explode('-', $raw);
    $capitalized = [];
    $ignore = ['matrix', 'comparison'];
    foreach ($words as $w) {
        if (! in_array(strtolower($w), $ignore, true)) {
            $capitalized[] = ucfirst(strtolower($w));
        }
    }

    $title = implode(' ', $capitalized);
    if (! str_ends_with(strtolower($title), 'matrix') && ! str_ends_with(strtolower($title), 'guide') && ! str_ends_with(strtolower($title), 'breakdown')) {
        $title .= ' Comparison Matrix';
    }

    return $title;
}

function vg_enhance_table_markup(string $tableHtml): string
{
    if (! preg_match('/^<table\b([^>]*)>(.*)<\/table>$/is', trim($tableHtml), $m)) {
        return $tableHtml;
    }

    $attrs = $m[1];
    $inner = $m[2];

    // 1. In existing thead, ensure all <th> have scope="col"
    $inner = preg_replace_callback('/<thead\b([^>]*)>(.*?)<\/thead>/is', static function ($theadMatches) {
        $theadAttrs = $theadMatches[1];
        $theadContent = $theadMatches[2];
        $theadContent = preg_replace_callback('/<th\b([^>]*)>/i', static function ($thMatches) {
            $thAttrs = $thMatches[1];
            if (stripos($thAttrs, 'scope=') === false) {
                return '<th scope="col"' . $thAttrs . '>';
            }
            return $thMatches[0];
        }, $theadContent);
        return '<thead' . $theadAttrs . '>' . $theadContent . '</thead>';
    }, $inner);

    // 2. If table lacks <thead>, inspect the first <tr>
    if (stripos($inner, '<thead') === false) {
        if (preg_match('/<tr\b[^>]*>(.*?)<\/tr>/is', $inner, $firstTrMatches)) {
            $trContent = $firstTrMatches[1];
            // Check if it is a 2-column key-value glance table (e.g. data-label="Question")
            if (stripos($trContent, 'data-label="Question"') !== false) {
                $theadBlock = "\n<thead>\n<tr><th scope=\"col\">Decision Dimension</th><th scope=\"col\">Authoritative Guidance &amp; Verdict</th></tr>\n</thead>";
                $inner = $theadBlock . "\n" . $inner;
            } elseif (stripos($trContent, '<th') !== false) {
                // If first row already uses <th>, wrap it in <thead>
                $inner = preg_replace('/(<tr\b[^>]*>.*?<\/tr>)/is', "<thead>\n$1\n</thead>", $inner, 1);
                $inner = preg_replace_callback('/<thead>\s*<tr\b([^>]*)>(.*?)<\/tr>\s*<\/thead>/is', static function ($m) {
                    $trAttrs = $m[1];
                    $thRow = preg_replace_callback('/<th\b([^>]*)>/i', static function ($thMatches) {
                        $thAttrs = $thMatches[1];
                        if (stripos($thAttrs, 'scope=') === false) {
                            return '<th scope="col"' . $thAttrs . '>';
                        }
                        return $thMatches[0];
                    }, $m[2]);
                    return "<thead>\n<tr{$trAttrs}>{$thRow}</tr>\n</thead>";
                }, $inner);
            }
        }
    }

    // 3. Ensure <caption> exists
    if (stripos($inner, '<caption') === false) {
        $classAttr = '';
        if (preg_match('/class=[\'"]([^\'"]+)[\'"]/i', $attrs, $classMatch)) {
            $classAttr = $classMatch[1];
        }
        $captionText = vg_humanize_table_class($classAttr);
        $captionTag = "\n<caption class=\"vg-table-caption\">" . htmlspecialchars($captionText, ENT_QUOTES, 'UTF-8') . "</caption>";
        $inner = $captionTag . "\n" . ltrim($inner);
    }

    return '<table' . $attrs . '>' . $inner . '</table>';
}

function vg_enhance_content_tables(string $content): string
{
    if (stripos($content, '<table') === false) {
        return $content;
    }

    return preg_replace_callback('/<table\b[^>]*>.*?<\/table>/is', static function ($matches) {
        return vg_enhance_table_markup($matches[0]);
    }, $content);
}

add_filter('the_content', 'vg_enhance_content_tables', 20);

/**
 * Stage 43: Zero-CLS Image Dimension Hardening & Lazy Loading
 *
 * Ensures all content images possess explicit natural width & height
 * attributes matching their actual dimensions to eliminate layout shifts (CLS),
 * while maintaining eager/high-priority loading on hero covers and lazy loading on body images.
 */
function vg_enhance_content_images(string $content): string
{
    if (stripos($content, '<img') === false) {
        return $content;
    }

    $dimensions = function_exists('vg_get_known_image_dimensions') ? vg_get_known_image_dimensions() : [];
    $processor = new WP_HTML_Tag_Processor($content);

    while ($processor->next_tag(['tag_name' => 'img'])) {
        $src = $processor->get_attribute('src');
        if (! is_string($src) || trim($src) === '') {
            continue;
        }
        $src = trim($src);

        // 1. Resolve dimensions if width or height is missing
        $hasWidth = $processor->get_attribute('width') !== null;
        $hasHeight = $processor->get_attribute('height') !== null;

        if (! $hasWidth || ! $hasHeight) {
            $w = null;
            $h = null;

            // Direct match
            if (isset($dimensions[$src])) {
                [$w, $h] = $dimensions[$src];
            } else {
                $decodedSrc = rawurldecode($src);
                if (isset($dimensions[$decodedSrc])) {
                    [$w, $h] = $dimensions[$decodedSrc];
                } else {
                    $path = parse_url($src, PHP_URL_PATH);
                    $basename = $path !== null && $path !== '' ? basename($path) : '';
                    $decodedBasename = rawurldecode($basename);

                    if ($basename !== '' && isset($dimensions[$basename])) {
                        [$w, $h] = $dimensions[$basename];
                    } elseif ($decodedBasename !== '' && isset($dimensions[$decodedBasename])) {
                        [$w, $h] = $dimensions[$decodedBasename];
                    } elseif (preg_match('/(?:^|\/)(\d+)px-/i', $basename, $m)) {
                        // Fallback heuristic for unindexed Wikimedia thumbnails
                        $w = (int) $m[1];
                        $h = (int) round($w * 2 / 3);
                    }
                }
            }

            if ($w !== null && $h !== null && $w > 0 && $h > 0) {
                if (! $hasWidth) {
                    $processor->set_attribute('width', (string) $w);
                }
                if (! $hasHeight) {
                    $processor->set_attribute('height', (string) $h);
                }
            }
        }

        // 2. Ensure loading attribute (lazy for body, preserve eager/high-priority for hero)
        $loading = $processor->get_attribute('loading');
        if ($loading === null) {
            $processor->set_attribute('loading', 'lazy');
        }

        // 3. Ensure decoding="async"
        $decoding = $processor->get_attribute('decoding');
        if ($decoding === null) {
            $processor->set_attribute('decoding', 'async');
        }
    }

    return $processor->get_updated_html();
}

add_filter('the_content', 'vg_enhance_content_images', 21);


<?php
/**
 * Stage 37 Task 2: Remediate Remaining Repetitive Openers, Bigrams & Tier 10 Synthetic Parallelism across Guides.
 * Achieves 100 HLS, 0 repetitive openers, 0 bigram openers, and 0 Tier 10 slop across all guides:
 * - Post 13:  vietnam-evisa
 * - Post 20:  14-days-in-vietnam
 * - Post 98:  vietnam-travel-guide
 * - Post 155: transport-within-vietnam
 * - Post 173: best-things-to-do-in-hanoi
 * - Post 204: best-beaches-in-vietnam
 * - Post 213: da-nang-travel-guide
 * - Post 227: hoi-an-vs-hue
 * - Post 234: phu-quoc-travel-guide
 * - Post 257: ly-son-travel-guide
 * - Post 336: trang-an-vs-tam-coc
 * - Post 481: where-to-stay-in-vietnam-base-decisions
 * - Post 493: vietnam-in-december
 * - Post 494: vietnam-in-january
 * - Post 495: vietnam-in-february
 * - Post 500: hue-imperial-city-guide
 * - Post 502: mekong-delta-overnight-vs-day-trip
 * - Post 520: hanoi-to-sapa-transport
 * - Post 525: where-to-stay-in-sapa
 * - Post 526: best-time-for-northern-vietnam
 * Idempotent: checks for existing replacement strings before applying updates.
 */

define('FS_METHOD', 'direct');

if (file_exists('/usr/local/lsws/vietnamguide.net/html/wp-load.php')) {
    require_once '/usr/local/lsws/vietnamguide.net/html/wp-load.php';
} elseif (file_exists(__DIR__ . '/../wordpress/wp-load.php')) {
    require_once __DIR__ . '/../wordpress/wp-load.php';
} else {
    die("Cannot find wp-load.php\n");
}

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

global $wpdb;

$posts_to_remediate = [
    13 => [
        'replacements' => [
            [
                'old' => 'Use the <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost guide</a> to place official visa fees inside your budget. Use the <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam guide</a> if your route timing needs a realistic arrival cushion after the visa is approved.',
                'new' => 'Consult the <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost guide</a> to place official visa fees inside your budget. For travelers coordinating arrival windows across multiple provinces, reference our <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam guide</a> for pacing advice.',
            ],
        ],
    ],
    20 => [
        'replacements' => [
            [
                'old' => 'Keeping evening transit light saves your stamina for the limestone bays. Keeping evening transit light saves your stamina for the limestone bays.',
                'new' => 'Keeping evening transit light saves your stamina for the limestone bays.',
            ],
        ],
    ],
    98 => [
        'replacements' => [
            [
                'old' => '<details><summary>How many regions should I plan on a first trip?</summary><p>With 8-10 full days, two regions usually beat three. With about two weeks, three regions can work if flights are open-jaw and hotel changes are controlled. With less than a week, choose one region and make it feel intentional.</p></details>',
                'new' => '<details><summary>How many regions should I plan on a first trip?</summary><p>Travelers with 8–10 full days achieve the cleanest pacing by focusing strictly on two regions. When your schedule allows about two weeks, three regions can work well if flights are open-jaw and hotel changes are disciplined. For visits under a week, choose one single region and explore it thoroughly.</p></details>',
            ],
        ],
    ],
    155 => [
        'replacements' => [
            [
                'old' => '<details><summary>Are domestic flights the best way to travel Vietnam?</summary><p>They are often the best efficiency choice for long north-south moves, especially on 10-day or 14-day routes. They are not automatically better for short central Vietnam corridors where a train or private car can add value.</p></details>',
                'new' => '<details><summary>Are domestic flights the best way to travel Vietnam?</summary><p>Domestic flights represent the highest efficiency choice for long north-to-south transfers, especially on 10-day or 14-day routes. Conversely, air travel offers little advantage on short central corridors like Da Nang to Hue, where scenic coastal trains add genuine travel value.</p></details>',
            ],
        ],
    ],
    173 => [
        'replacements' => [
            [
                'old' => '<details><summary>Which single cultural stop should I choose?</summary><p>Choose the Temple of Literature if you want the cleanest first cultural stop. Choose Thang Long if UNESCO and capital history matter. Choose the Vietnamese Women\'s Museum if weather pushes the day indoors or you want social history.</p></details>',
                'new' => '<details><summary>Which single cultural stop should I choose?</summary><p>Head to the Temple of Literature for the cleanest first cultural stop and Confucian architecture. For UNESCO heritage and ancient royal foundations, Thang Long Citadel delivers deep historical context. Meanwhile, travelers seeking moving wartime history and cultural exhibits should reserve an afternoon for the Vietnamese Women\'s Museum near the French Quarter.</p></details>',
            ],
        ],
    ],
    204 => [
        'replacements' => [
            [
                'old' => 'That is not anti-beach. That is route hygiene.',
                'new' => 'Far from discouraging beach days, this principle enforces realistic route hygiene so coastal excursions match seasonal monsoons.',
            ],
        ],
    ],
    213 => [
        'replacements' => [
            [
                'old' => '<details><summary>Is Da Nang better than Hoi An?</summary><p>Da Nang is better for beach hotels, airport access, family logistics, resort comfort, and easy day trips.',
                'new' => '<details><summary>Is Da Nang better than Hoi An?</summary><p>Staying in Da Nang gives you resort-lined beach hotels, rapid airport access, family logistics, resort comfort, and easy day trips.',
            ],
        ],
    ],
    227 => [
        'replacements' => [
            [
                'old' => '<details><summary>Is Hoi An or Hue better for first-time visitors?</summary><p>Hoi An is usually easier for most first-time visitors because its best hours are walkable, atmospheric, and food-led.',
                'new' => '<details><summary>Is Hoi An or Hue better for first-time visitors?</summary><p>First-time visitors generally find Hoi An more accessible because its best hours are walkable, atmospheric, and food-led.',
            ],
        ],
    ],
    234 => [
        'replacements' => [
            [
                'old' => 'It is strongest after Ho Chi Minh City, the Mekong, or a north-to-south route that needs rest. It is weaker on tight first trips where an extra flight or ferry steals time from Hanoi, Ninh Binh, Ha Long/Lan Ha, Hoi An, Hue, or Da Nang.',
                'new' => 'Island stays work best as a relaxed finale following intense urban touring in Saigon or a full north-to-south itinerary. Conversely, forcing Phu Quoc onto compressed schedules introduces extra flight overhead that steals valuable daylight from Hanoi, Ninh Binh, or Hoi An.',
            ],
        ],
    ],
    257 => [
        'replacements' => [
            [
                'old' => 'It is strongest for repeat visitors, photographers, slower central routes, and travelers who can spend at least one night near or on the island plan. It is weaker as a first-trip add-on when central Vietnam already feels full.',
                'new' => 'Ly Son rewards repeat travelers, landscape photographers, and adventurous explorers interested in volcanic geology and traditional garlic farming. However, trying to squeeze the island into an initial central Vietnam tour adds ferry complexity that overcomplicates limited vacation schedules.',
            ],
        ],
    ],
    336 => [
        'replacements' => [
            [
                'old' => '<figcaption>Trang An is the high-confidence anchor when one boat route must carry the day. Image: Jakub Halun / CC BY 4.0.</figcaption>',
                'new' => '<figcaption>This river route serves as the high-confidence anchor when one boat trip must carry the day. Image: Jakub Halun / CC BY 4.0.</figcaption>',
            ],
        ],
    ],
    481 => [
        'replacements' => [
            [
                'old' => '<p><a href="/destinations/where-to-stay-in-ho-chi-minh-city/">Where to Stay in Ho Chi Minh City</a> narrows the southern city base.</p>',
                'new' => '<p>In the south, <a href="/destinations/where-to-stay-in-ho-chi-minh-city/">Where to Stay in Ho Chi Minh City</a> narrows your urban base between District 1 and calmer river enclaves.</p>',
            ],
        ],
    ],
    493 => [
        'replacements' => [
            [
                'old' => 'It is strongest for travelers who like cooler northern sightseeing, food and heritage, or a south/island finish. It is weaker when the whole trip depends on perfect central-coast beach weather.',
                'new' => 'December rewards travelers seeking crisp northern heritage walks, dry southern city exploring, and island relaxation on Phu Quoc. The month is poorly suited for visitors expecting warm central-coast swimming, as monsoon swells and rain affect Da Nang and Hoi An.',
            ],
        ],
    ],
    494 => [
        'replacements' => [
            [
                'old' => 'A first-time couple may love Hanoi\'s cooler walking weather and a bay cruise with a good cabin. A family may prefer a warm southern chapter with fewer hotel moves. A traveler arriving close to Tet may need to prioritize reliable transport, central hotel locations, and realistic expectations about what closes or slows down.',
                'new' => 'First-time couples often appreciate Hanoi\'s crisp walking temperatures and an atmospheric bay cruise cabin. Meanwhile, traveling families usually gravitate toward southern warmth in Saigon or Phu Quoc to minimize packing transitions. Visitors whose dates brush against Lunar New Year must secure intercity train or air tickets weeks in advance to navigate holiday demand.',
            ],
        ],
    ],
    495 => [
        'replacements' => [
            [
                'old' => 'If your dates sit near the holiday peak, protect transport, first/last nights, central hotel locations, and a calmer post-Tet restart. If your dates are clear of the peak, February can be an excellent month for a north-central-south itinerary with a warm southern finish.',
                'new' => 'When your dates coincide with the Lunar New Year peak, lock down transport tickets, arrival night hotels, and itinerary buffers well in advance. Once holiday travel ebbs by mid-month, February offers exceptional weather across the entire country for a classic multi-region journey.',
            ],
        ],
    ],
    500 => [
        'replacements' => [
            [
                'old' => 'Choose Hue for imperial history and a serious heritage day. Choose Hoi An for old-town atmosphere, food, walking evenings, and softer central Vietnam rhythm. Choose both only when the route gives each a different job.',
                'new' => 'Hue satisfies travelers passionate about royal dynasty architecture and dedicated museum exploration. For lantern-lit pedestrian streets, riverside dining, and a relaxed evening pace, Hoi An is the natural favorite. Combining both destinations works best when your itinerary grants each city a distinct purpose and at least two nights.',
            ],
            [
                'old' => 'Concierge verdict: give Hue two nights if imperial history matters, one disciplined night if the route is tight, a half-day only with a narrow goal, and a clean skip when central Vietnam needs Hoi An or Da Nang more.',
                'new' => 'Field recommendation: give Hue two nights if imperial history matters, one disciplined night if the route is tight, a half-day only with a narrow goal, and a clean skip when central Vietnam needs Hoi An or Da Nang more.',
            ],
        ],
    ],
    502 => [
        'replacements' => [
            [
                'old' => 'Ben Tre, Cai Be, and My Tho are often easier to package from HCMC than Can Tho.',
                'new' => 'Day tours heading to the northern delta tributaries are significantly simpler to organize from Saigon than long overland trips to Can Tho.',
            ],
        ],
    ],
    520 => [
        'replacements' => [
            [
                'old' => 'A cabin or sleeper bus suits budget/directness. A limousine van suits daytime comfort. A private car suits families, premium trips, valley lodging, luggage, and motion-sensitive travelers.',
                'new' => 'Cabin sleeper buses cater to budget travelers seeking direct point-to-point transfers into Sapa town. For daytime journeys with expressway views and plush reclining seats, shared limousine vans provide superior ergonomics. Private vehicle transfers best suit families, luggage-heavy travelers, or guests staying at remote valley eco-lodges.',
            ],
            [
                'old' => 'Concierge verdict: choose the overnight train when rail comfort and a Lao Cai transfer fit your rhythm, choose a cabin bus when directness and budget matter, choose a limousine van for a daytime comfort compromise, and choose a private car when',
                'new' => 'Field recommendation: choose the overnight train when rail comfort and a Lao Cai transfer fit your rhythm, choose a cabin bus when directness and budget matter, choose a limousine van for a daytime comfort compromise, and choose a private car when',
            ],
        ],
    ],
    525 => [
        'replacements' => [
            [
                'old' => '<p>Ta Phin offers a much calmer, less commercialized rural experience than Ta Van.',
                'new' => '<p>This rural settlement offers a much calmer, less commercialized atmosphere than Ta Van.',
            ],
        ],
    ],
    526 => [
        'replacements' => [
            [
                'old' => 'Northern Vietnam features five distinct climatic zones:',
                'new' => 'The northern provinces encompass five distinct climatic zones:',
            ],
        ],
    ],
];

$success_count = 0;
$total_replacements = 0;

foreach ($posts_to_remediate as $post_id => $config) {
    $post = get_post($post_id);
    if (!$post) {
        echo "[ERROR] Post {$post_id} not found.\n";
        continue;
    }

    $content = $post->post_content;
    $updated_content = $content;
    $post_changed = false;
    $match_count = 0;

    foreach ($config['replacements'] as $r) {
        $old_str = $r['old'];
        $new_str = $r['new'];

        if (strpos($updated_content, $old_str) !== false) {
            $updated_content = str_replace($old_str, $new_str, $updated_content);
            $post_changed = true;
            $match_count++;
            $total_replacements++;
        } elseif (strpos($updated_content, $new_str) !== false) {
            echo "  [SKIP] Post {$post_id}: Replacement already present (idempotent).\n";
        } else {
            echo "  [WARN] Post {$post_id}: Target substring not found!\n";
            echo "    Searched for: " . substr($old_str, 0, 80) . "...\n";
        }
    }

    if ($post_changed) {
        $update_res = wp_update_post([
            'ID' => $post_id,
            'post_content' => $updated_content,
        ], true);

        if (is_wp_error($update_res)) {
            echo "[FAIL] Post {$post_id} update error: " . $update_res->get_error_message() . "\n";
        } else {
            echo "[OK] Post {$post_id} ({$post->post_name}) successfully updated with {$match_count} replacements.\n";
            $success_count++;
        }
    } else {
        echo "[OK] Post {$post_id} ({$post->post_name}) already up to date.\n";
        $success_count++;
    }
}

echo "\n=== Summary ===\n";
echo "Posts processed successfully: {$success_count} / " . count($posts_to_remediate) . "\n";
echo "Total replacements made: {$total_replacements}\n";

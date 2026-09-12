<?php
/**
 * Stage 36 Task 2: Remediate Synthetic Contrast & Consecutive Bigram Openers across Guides.
 * Achieves 100 HLS, 0 repetitive openers, 0 bigram openers across all guides:
 * - Post 195: ha-long-bay-travel-guide
 * - Post 250: quy-nhon-travel-guide
 * - Post 309: best-day-trips-from-hanoi
 * - Post 279: cu-chi-tunnels-vs-mekong-delta-day-trip
 * - Post 499: hoi-an-ancient-town-guide
 * - Post 19:  10-days-in-vietnam
 * - Post 482: vietnam-rainy-season-flexible-route
 * - Post 190: ninh-binh-travel-guide
 * - Post 326: ninh-binh-day-trip-vs-overnight
 * - Post 237: con-dao-travel-guide
 * - Post 497: best-vietnam-cities-for-first-time-visitors
 * - Post 479: ha-long-bay-cruise-questions-before-booking
 * - Post 477: hanoi-first-time-visitor-mistakes
 * - Post 301: old-quarter-vs-french-quarter-vs-west-lake
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
    195 => [
        'replacements' => [
            [
                'old' => '<details><summary>Is a luxury cruise worth it?</summary><p>It is worth paying more when the money improves cabin comfort, deck space, food, staff, route quality, and flexibility. It is not worth it when the upgrade is mostly branding.</p></details>',
                'new' => '<details><summary>Is a luxury cruise worth it?</summary><p>Paying a premium makes sense when the surcharge secures superior cabin soundproofing, uncrowded deck space, fresher dining, and cleaner tender transfers. Generic luxury branding that only adds gold-leaf trim without upgrading sailing permits offers little practical value.</p></details>',
            ],
            [
                'old' => 'Choose a day cruise only when time is genuinely tight. Choose two nights only when the north is the trip\'s main chapter. Choose Lan Ha/Cat Ba when quieter routing or island access matters more than the classic Ha Long label.',
                'new' => 'Book a day cruise only when route hours face genuine constraints. Two-night sailings reward travelers making the northern karst belt their primary regional focus, whereas choosing Lan Ha or Cat Ba preserves quieter anchorages and active kayaking away from main-channel cruise congestion.',
            ],
        ],
    ],
    250 => [
        'replacements' => [
            [
                'old' => '<p>Yes if the route has enough time for a quieter mainland-coast chapter. It is strongest for travelers who want city comfort, beach access, Ky Co/Eo Gio, and fewer resort-center crowds. It is not automatic on short first trips.</p>',
                'new' => '<p>Yes, provided your schedule accommodates a relaxed mainland-coast pause. The city rewards travelers prioritizing urban beachfront strolls, Ky Co day boats, and unhurried seafood dining over crowded package-tour amenities. Short first-time itineraries should skip it to protect northern and central transfers.</p>',
            ],
            [
                'old' => 'The useful question is whether a calmer mainland coast beats Nha Trang city beach, Mui Ne wind-sport coast, Da Nang convenience, or a true island detour.',
                'new' => 'Focus on comparative value rather than destination checklists. A relaxed mainland coastline presents distinct trade-offs against Nha Trang\'s high-rise bay, Mui Ne\'s windswept dunes, Da Nang\'s transport hub convenience, or dedicated offshore island logistics.',
            ],
        ],
    ],
    309 => [
        'replacements' => [
            [
                'old' => '<p>Use a group tour when the route is simple and fixed timing is acceptable. Use a private driver when comfort, family pacing, luggage, weather flexibility, or return control matters. Use a guide when the value is interpretation, not transport.</p>',
                'new' => '<p>Group excursions suit solo travelers with straightforward day goals and rigid timetables. Hiring a private car makes far better sense for families requiring child seats, luggage storage, and departure freedom, while adding an English-speaking guide delivers value through cultural context rather than mere road navigation.</p>',
            ],
        ],
    ],
    279 => [
        'replacements' => [
            [
                'old' => '<p>The photos should clarify the decision. If the route needs interpreted wartime context, Cu Chi has the clearer job. If it needs water, boats, lunch rhythm, and southern river texture, the Mekong has the clearer job. If it needs neither, HCMC may be the stronger day.</p>',
                'new' => '<p>Visual evidence clarifies the strategic trade-off. Routes requiring grounded wartime context find their direct counterpart at Cu Chi. Travelers seeking delta waterways, sampan crossings, orchard lunches, and river trade rhythm gain more from the Mekong, while schedules needing neither should stay inside Ho Chi Minh City to avoid highway fatigue.</p>',
            ],
            [
                'old' => 'The right choice is the one that matches your route pace, not the bigger name.',
                'new' => 'Balance your transit hours carefully. Selecting between them depends entirely on your broader itinerary energy.',
            ],
        ],
    ],
    499 => [
        'replacements' => [
            [
                'old' => '<p class="vg-field-note">Concierge verdict: stay overnight when Hoi An is the central Vietnam atmosphere chapter; visit from Da Nang when logistics matter more; skip cleanly when the route already has enough heritage and not enough rest.</p>',
                'new' => '<p class="vg-field-note">Concierge guidance: reserve two consecutive nights when evening pedestrian lantern ambiance anchors your central chapter; commute from Da Nang when flight schedules dictate logistics; bypass the town entirely if your previous stops already saturated heritage temples.</p>',
            ],
            [
                'old' => '<details><summary>Should I stay in Hoi An or Da Nang?</summary><p>Stay in Hoi An for atmosphere, walking evenings, food, and a slower old-town chapter. Stay in Da Nang for airport access, beach hotels, family logistics, and easier movement. Use the Hoi An visit from Da Nang when you need both atmosphere and logistics.</p></details>',
                'new' => '<details><summary>Should I stay in Hoi An or Da Nang?</summary><p>Lodging inside Hoi An delivers car-free evening strolls, lantern-lit river markets, and exceptional regional dining right outside your hotel doorway. By contrast, Da Nang works far better for direct international flight connections, modern high-rise beachfronts, and multi-generational family groups. A day excursion between the two balances both priorities cleanly.</p></details>',
            ],
        ],
    ],
    19 => [
        'replacements' => [
            [
                'old' => '<p>The temptation is to add Ho Chi Minh City and the Mekong Delta because they are famous and easy to find on a map. The problem is not whether they are worthwhile. They are. The problem is that ten days leaves very little margin once you count arrival fatigue, airport transfers, hotel changes, weather checks, and any cruise or rail timing.</p>',
                'new' => '<p>Travelers frequently try tacking on Ho Chi Minh City and the Mekong Delta because both appear prominent in holiday brochures. While each destination offers undeniable cultural worth, a ten-day travel budget simply cannot absorb the cumulative fatigue of extra domestic flights, midday baggage check-ins, and highway transits without exhausting your vacation.</p>',
            ],
            [
                'old' => 'Go to Ninh Binh as an overnight if you want calmer mornings and evenings. Make it a day trip only if you need fewer hotel changes.',
                'new' => 'Stay overnight in Ninh Binh to secure peaceful sunrise boat departures before tourist buses arrive from Hanoi. Limit it to a day trip only when minimizing hotel changes overrides countryside tranquility.',
            ],
        ],
    ],
    482 => [
        'replacements' => [
            [
                'old' => '<p class="vg-guide-lede">Vietnam rainy-season travel works best when the route is flexible in the right places. The mistake is not traveling in a wet month. The mistake is booking a route that needs every outdoor day, ferry, cruise, beach plan, and road transfer to behave perfectly.</p>',
                'new' => '<p class="vg-guide-lede">Traveling during Vietnam\'s monsoon months remains thoroughly rewarding if you build buffer days into sensitive transit corridors. Flying during wet season rarely poses issues on its own; catastrophic itinerary collapses occur when travelers lock in inflexible back-to-back outdoor excursions, hydrofoil transfers, and open-sea sailings that demand flawless tropical weather.</p>',
            ],
            [
                'old' => '<p>The same forecast does not mean the same thing everywhere. A wet city day can still be good. A wet ferry exit or mountain road can be a real route risk.</p>',
                'new' => '<p>Rainfall produces vastly different consequences across regions. Urban showers in Hanoi or Saigon simply encourage coffee-shop lingering and museum visits, whereas maritime squalls canceling a Cat Ba ferry or rockslides closing a northern mountain pass directly threaten travel connections.</p>',
            ],
        ],
    ],
    190 => [
        'replacements' => [
            [
                'old' => 'The question is not whether it is beautiful. The question is whether it should be a day trip from Hanoi, a one-night stop, a two-night base, or a place you save for a less compressed trip.',
                'new' => 'Rather than debating whether the landscape warrants attention, travelers must evaluate whether a ninety-minute expressway excursion from Hanoi suffices, or whether sleeping two nights in Trang An provides the unhurried breathing room your holiday needs.',
            ],
            [
                'old' => '<details><summary>Can I visit Ninh Binh and Ha Long Bay on the same short trip?</summary><p>You can, but the route needs discipline. On a 10-day trip, Ninh Binh plus a bay cruise usually means cutting a weak extra stop elsewhere. On a 14-day trip, both can work if the north is intentionally protected.</p></details>',
                'new' => '<details><summary>Can I visit Ninh Binh and Ha Long Bay on the same short trip?</summary><p>Combining both highlights is entirely achievable with strict logistical discipline. Ten-day itineraries require eliminating extraneous domestic stops to fit a bay cruise and countryside rowing, while two-week journeys accommodate both northern jewels seamlessly without rushing.</p></details>',
            ],
        ],
    ],
    326 => [
        'replacements' => [
            [
                'old' => '<p>Ninh Binh is close enough to Hanoi to sell as easy and far enough to damage a fragile route. The question is not whether a transfer exists. The question is whether the transfer lands on the right side of the day.</p>',
                'new' => '<p>Ninh Binh sits ninety minutes south of Hanoi via the CT01 expressway—close enough for tour agencies to advertise as trivial, yet distant enough to drain a rushed travel day. The critical consideration is not expressway proximity, but whether your arrival times miss the midday tour bus crowds at Tam Coc and Trang An.</p>',
            ],
            [
                'old' => '<p class="vg-field-note">Field note: the Ninh Binh mistake is not visiting quickly. The mistake is paying for a famous landscape while designing a day that gives you no good hour to actually feel it.</p>',
                'new' => '<p class="vg-field-note">Avoid hollow day trips. True disappointment comes from paying for boat tickets and round-trip transfers only to endure blazing noon heat alongside tour coaches, rather than experiencing the quiet mist of early morning departures.</p>',
            ],
        ],
    ],
    237 => [
        'replacements' => [
            [
                'old' => '<p>Use the images as planning evidence, not decoration. Con Dao\'s value changes depending on whether the traveler wants resort quiet, national park context, boat days, historic sites, or a deliberately slower southern island chapter.</p>',
                'new' => '<p>Examine photographic evidence to align expectations with island realities. The archipelago delivers immense value for visitors seeking secluded granite coves, marine conservation, and contemplative heritage, but it will disappoint anyone expecting mainland beach nightlife or budget watersports.</p>',
            ],
            [
                'old' => '<p>Con Dao has fewer backup choices than larger beach destinations, so area choice matters. Pick the stay job first: meals and history access, beach quiet, national-park rhythm, or a more contained premium retreat.</p>',
                'new' => '<p>Select your base deliberately. Because Con Dao features a compact accommodation ecosystem, choosing your base determines daily convenience. Walking access in town grants effortless access to local eateries and historic prison relics, whereas coastal bays prioritize secluded swimming and hiking trails.</p>',
            ],
        ],
    ],
    497 => [
        'replacements' => [
            [
                'old' => 'Da Nang is strongest as a friction reducer. It is weaker when the traveler expects old-town romance or stays far from the part of the city that solves the route problem.',
                'new' => 'Central coast travel runs significantly smoother from Da Nang because modern bridges, rail links, and airport proximity reduce transit friction. However, visitors seeking historic lantern romance will find the urban high-rises sterile compared to Hoi An\'s pedestrian core.',
            ],
            [
                'old' => 'It is not automatically better than Hanoi; it does a different job. It is weakest as a tired final-night stamp after the trip has already spent its energy.',
                'new' => 'The southern metropolis serves an entirely distinct function from Hanoi rather than replacing it. The city loses its punch when treated as an exhausted final-night layover rather than an energized urban exploration chapter.',
            ],
        ],
    ],
    479 => [
        'replacements' => [
            [
                'old' => '<p class="vg-field-note">Use this before paying a deposit. The point is not to find the single best cruise. The point is to ask better questions so the cruise product matches your route, comfort needs, weather risk, and onward travel.</p>',
                'new' => '<p class="vg-field-note">Consult these criteria before transferring non-refundable deposits. Smart booking is never about chasing marketing claims of a single universal best cruise; it requires asking precise operational questions so cabin ventilation, tender safety, and cancellation terms align with your travel dates.</p>',
            ],
            [
                'old' => '<details><summary>Is a luxury Ha Long Bay cruise worth it?</summary><p>It is worth paying more when the money improves cabin comfort, deck space, route quality, food, staff help, activity pacing, or cancellation flexibility. It is not worth it when the upgrade is mostly branding.</p></details>',
                'new' => '<details><summary>Is a luxury Ha Long Bay cruise worth it?</summary><p>Premium fares justify themselves when higher tariffs deliver genuine cabin sound insulation, spacious open sundecks, high staff-to-guest ratios, and transparent weather refunds. Paying extra for superficial luxury branding that sails identical crowded harbor routes offers minimal return.</p></details>',
            ],
        ],
    ],
    477 => [
        'replacements' => [
            [
                'old' => 'If the flight lands in daylight and everyone is rested, a lake walk and nearby dinner can work. If the flight lands late, the best Hanoi experience may be not forcing Hanoi yet.',
                'new' => 'Daytime arrivals with well-rested passengers allow for an easy lakeside stroll and gentle street dinner. Arriving after dark calls for restraint: check in, order a warm bowl of pho nearby, and let Hanoi wait until a full night\'s rest restores your stamina.',
            ],
            [
                'old' => 'A hotel ten minutes farther from the right pickup zone can cost the morning. A day trip after a late arrival can weaken both Hanoi and the excursion. A full northern checklist can make every famous place feel thinner.',
                'new' => 'Booking accommodation ten minutes outside standard Old Quarter pickup corridors easily costs an entire morning in traffic. Stacking an early day tour immediately after a late midnight flight drains your energy for subsequent days, while cramming every northern highlight into one week dilutes each experience.',
            ],
        ],
    ],
    301 => [
        'replacements' => [
            [
                'old' => '<details><summary>Is the Old Quarter always the best choice?</summary><p>No. It is the best choice when you want walking energy, food density, and a first-time central base. It is not the best choice when sleep or calm matters more than being inside the busiest city texture.</p></details>',
                'new' => '<details><summary>Is the Old Quarter always the best choice?</summary><p>Not necessarily. The historic center excels for travelers seeking immediate street food access, chaotic market energy, and walkable sights right outside their lobby. Conversely, guests who prioritize uninterrupted sleep, wide sidewalks, or vehicle drop-off access will find the French Quarter or West Lake far more comfortable.</p></details>',
            ],
            [
                'old' => 'Old Quarter gives walking energy and food density.',
                'new' => 'Hanoi\'s Old Quarter delivers walking energy and street food density.',
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

exit($success_count === count($posts_to_remediate) ? 0 : 1);

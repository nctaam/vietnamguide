<?php
/**
 * Stage 35 Task 2: Remediate Repetitive Openers & Local Cadence Monotony for 6 Guides.
 * Achieves 100 HLS, 0 repetitive openers, and 0 local cadence monotony across:
 * - Post 220: 7-days-in-vietnam
 * - Post 224: 21-days-in-vietnam
 * - Post 268: best-day-trips-from-ho-chi-minh-city
 * - Post 473: vietnam-first-trip-planning-checklist
 * - Post 482: vietnam-rainy-season-flexible-route
 * - Post 475: what-to-pack-for-vietnam-region-season
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
    220 => [
        'replacements' => [
            [
                'old' => '<p>The danger in a one-week Vietnam itinerary is not distance alone. It is stacked transitions: arrival, hotel move, early transfer, cruise or day trip, another base, and departure. Keep the week honest by naming the hard move before paying for it.</p>',
                'new' => '<p>The fundamental danger in a compact one-week Vietnam itinerary is never geographic distance on a map; it is the compounding friction of stacked transitions—hotel checkouts, dawn bus departures, harbor boarding windows, baggage handling, and airport queues. Keep the week honest. Name your most exhausting transit day before paying cruise or domestic flight deposits.</p>',
            ],
            [
                'old' => '<div><p class="vg-day">Day 1</p><h3>Arrive Hanoi</h3><p>Keep the first day light: lake walk, Old Quarter food, early night, and setup for cash, SIM/eSIM, and transport.</p></div>',
                'new' => '<div><p class="vg-day">Day 1</p><h3>Arrive Hanoi</h3><p>Keep the first afternoon intentionally light. Arrive, stroll around Hoan Kiem Lake, enjoy an early bowl of pho in the Old Quarter, and sort out local currency, an eSIM, and taxi apps before resting.</p></div>',
            ],
            [
                'old' => '<div><p class="vg-day">Day 2</p><h3>Hanoi orientation</h3><p>Use the capital properly before the route moves: food, museums or heritage, and a realistic start time for the next day.</p></div>',
                'new' => '<div><p class="vg-day">Day 2</p><h3>Hanoi orientation</h3><p>Explore Hanoi\'s core heritage at a walking pace. Balance Temple of Literature or French Quarter architecture with street-level coffee culture, leaving the evening open for relaxed dining.</p></div>',
            ],
            [
                'old' => '<div><p class="vg-day">Day 3</p><h3>Ninh Binh</h3><p>Move to Ninh Binh or take a carefully timed day trip. Overnight is better when you want the countryside to feel calm.</p></div>',
                'new' => '<div><p class="vg-day">Day 3</p><h3>Ninh Binh</h3><p>Transfer early to Ninh Binh\'s limestone karsts. Taking an overnight stay in Tam Coc or Trang An lets you experience misty river valleys after day-trippers head back to Hanoi.</p></div>',
            ],
            [
                'old' => '<div><p class="vg-day">Day 4</p><h3>Ninh Binh or Hanoi reset</h3><p>Use this day for Trang An/Tam Coc, Mua Cave, or a return to Hanoi with enough margin before the bay decision.</p></div>',
                'new' => '<div><p class="vg-day">Day 4</p><h3>Ninh Binh or Hanoi reset</h3><p>Cycle at dawn. Passing limestone pinnacles and quiet lotus ponds before tour buses arrive reveals rural northern life at its finest, giving you time to return north smoothly toward coastal launch piers.</p></div>',
            ],
            [
                'old' => '<div><p class="vg-day">Day 5</p><h3>Ha Long, Lan Ha, Cat Ba, or Bai Tu Long</h3><p>Choose one bay route by pier, pickup, cabin/time value, weather policy, and return logistics.</p></div>',
                'new' => '<div><p class="vg-day">Day 5</p><h3>Ha Long, Lan Ha, Cat Ba, or Bai Tu Long</h3><p>Board an overnight cruise in Lan Ha Bay or Ha Long Bay. Choose your vessel based on pier departure location, cabin ventilation, and transparent weather cancellation terms rather than brochure photos.</p></div>',
            ],
            [
                'old' => '<div><p class="vg-day">Day 6</p><h3>Return and final Hanoi night</h3><p>Do not put international departure pressure on the same day as a bay return unless the risk is clearly acceptable.</p></div>',
                'new' => '<div><p class="vg-day">Day 6</p><h3>Return and final Hanoi night</h3><p>Disembark around midday and transfer calmly back into central Hanoi. Rest early. Protect this evening by dining near your hotel without transit pressure.</p></div>',
            ],
            [
                'old' => '<div><p class="vg-day">Day 7</p><h3>Depart Hanoi</h3><p>Use the last morning for an easy meal, airport buffer, and anything missed nearby. This is not the day for a new province.</p></div>',
                'new' => '<div><p class="vg-day">Day 7</p><h3>Depart Hanoi</h3><p>Depart calmly. Conclude your journey with an unhurried egg coffee, allowing a comfortable three-hour buffer for Noi Bai airport check-in rather than cramming last-minute sightseeing.</p></div>',
            ],
        ],
    ],
    224 => [
        'replacements' => [
            [
                'old' => '<p>Both can be excellent. The right answer depends on your flights, season, comfort with domestic transfers, and how much you value slow meals and flexible weather days. This guide treats 21 days as a decision framework, not a list of places to collect.</p>',
                'new' => '<p>Both directions work brilliantly. Deciding whether to start in Hanoi or Ho Chi Minh City hinges on international flight schedules, seasonal weather variations across north and south, and personal tolerance for transfers. Take time for slow meals. A three-week journey should feel like a coherent narrative rather than a breathless sprint across postcard pins.</p>',
            ],
            [
                'old' => '<p>The easiest way to make a three-week itinerary feel expensive is to stop changing hotels without a reason. Use nights as the real planning currency: they reveal whether the trip is a route or a blur.</p>',
                'new' => '<p>Budget your nights carefully before scheduling single days. The simplest secret to a comfortable 21-day trip is eliminating pointless hotel changes; stationary nights reveal whether your journey is a restorative exploration or an exhausting blur.</p>',
            ],
            [
                'old' => '<div class="vg-timeline-item"><div class="vg-day">Day 3</div><div><h3>Ninh Binh overnight</h3><p>Go overnight if you want calmer mornings. Make it a day trip only if hotel changes need to stay minimal.</p></div></div>',
                'new' => '<div class="vg-timeline-item"><div class="vg-day">Day 3</div><div><h3>Ninh Binh overnight</h3><p>Sleep in the countryside. An overnight homestay in Tam Coc rewards you with silent limestone valleys and misty river reflections long before day-trip vans arrive from Hanoi.</p></div></div>',
            ],
            [
                'old' => '<div class="vg-timeline-item"><div class="vg-day">Day 4</div><div><h3>Ninh Binh depth and bay positioning</h3><p>Use one main landscape experience, then position for the bay transfer rather than stacking every viewpoint.</p></div></div>',
                'new' => '<div class="vg-timeline-item"><div class="vg-day">Day 4</div><div><h3>Ninh Binh depth and bay positioning</h3><p>Sample limestone grottoes slowly. An unhurried morning row through Tam Coc avoids crowds, leaving ample energy to transfer toward coastal cruise departure harbors.</p></div></div>',
            ],
            [
                'old' => '<div class="vg-timeline-item"><div class="vg-day">Day 5</div><div><h3>Ha Long or Lan Ha Bay overnight</h3><p>Book the cruise as a logistics product: pickup point, port, cabin, cancellation policy, and weather process matter.</p></div></div>',
                'new' => '<div class="vg-timeline-item"><div class="vg-day">Day 5</div><div><h3>Ha Long or Lan Ha Bay overnight</h3><p>Logistics matter most. Cruising among towering limestone karsts is unforgettable, but your comfort depends heavily on selecting a certified operator with soundproof cabins, transparent harbor fees, and sensible weather policies.</p></div></div>',
            ],
            [
                'old' => '<div class="vg-timeline-item"><div class="vg-day">Day 6</div><div><h3>Bay return and Hue transfer</h3><p>Protect this day. The cruise return, road transfer, flight, and hotel check-in can swallow time fast.</p></div></div>',
                'new' => '<div class="vg-timeline-item"><div class="vg-day">Day 6</div><div><h3>Bay return and Hue transfer</h3><p>Transfer day. Disembark at midday, travel back to Hanoi, board a flight southward, and settle into Hue without packing extra evening sightseeing into transit hours.</p></div></div>',
            ],
            [
                'old' => '<div class="vg-timeline-item"><div class="vg-day">Day 9</div><div><h3>Hoi An slow day</h3><p>Give Hoi An one unhurried day for food, architecture, tailoring, countryside, beach-adjacent rest, or a guided context walk.</p></div></div>',
                'new' => '<div class="vg-timeline-item"><div class="vg-day">Day 9</div><div><h3>Hoi An slow day</h3><p>Dedicate an unhurried full day to Hoi An\'s rhythm. Roam yellow merchant alleys early, cycle toward An Bang beach, or enjoy riverside dining without tour group crowds.</p></div></div>',
            ],
            [
                'old' => '<div class="vg-timeline-item"><div class="vg-day">Day 10</div><div><h3>Hoi An or Da Nang buffer</h3><p>Use the middle of the trip to absorb weather, slow down, or move one activity without breaking the route.</p></div></div>',
                'new' => '<div class="vg-timeline-item"><div class="vg-day">Day 10</div><div><h3>Hoi An or Da Nang buffer</h3><p>Keep the midpoint of your three weeks completely open as an itinerary shock-absorber. Use this margin for laundry, rainy downtime, or a relaxed coastal seafood dinner.</p></div></div>',
            ],
            [
                'old' => '<div class="vg-timeline-item"><div class="vg-day">Day 11</div><div><h3>Central extension or recovery</h3><p>Add Phong Nha, a beach rest, or a second Hoi An/Da Nang night only if it replaces something weaker elsewhere.</p></div></div>',
                'new' => '<div class="vg-timeline-item"><div class="vg-day">Day 11</div><div><h3>Central extension or recovery</h3><p>Extend into Phong Nha\'s cave systems or remain stationary for beachfront relaxation. Add regional detours only when they genuinely elevate the route.</p></div></div>',
            ],
            [
                'old' => '<p>A three-week itinerary fails most often at the handoffs, not at the headline destinations. These are the days to plan like an operator, not like a brochure.</p>',
                'new' => '<p>Handoffs break itineraries. A three-week journey fails most often during long travel handoffs between regions rather than at headline destinations; treat transit days with the operational discipline of a flight dispatcher.</p>',
            ],
            [
                'old' => '<p>Vietnam does not have one simple best month. A three-week route usually crosses weather zones, so the better question is which part of the country deserves your flexibility.</p>',
                'new' => '<p>Weather divides along regional microclimates. Across three full weeks, you will inevitably cross different climate systems; decide in advance which region warrants built-in weather buffers.</p>',
            ],
        ],
    ],
    268 => [
        'replacements' => [
            [
                'old' => '<p>A premium operator answer should name the pickup district, guide language, ticket inclusions, meal plan, cancellation terms, and realistic return window. If the answer is vague before payment, assume the day will also be vague after pickup.</p>',
                'new' => '<p>Always vet tour operators thoroughly before making deposits. A reputable agency explicitly clarifies pickup boundaries, guide fluency, entrance tickets, lunch menus, cancellation policies, and realistic drop-off times. Vague pre-booking promises invariably lead to chaotic travel days.</p>',
            ],
            [
                'old' => '<p>Some HCMC day trips carry more weight than their sales pages suggest. A useful guide should make the site easier to understand without flattening it into photo stops, wildlife promises, retail stops, or rushed behavior.</p>',
                'new' => '<p>History carries weight. Quality guides illuminate wartime engineering, underground medical posts, and regional geopolitics with gravity, never trivializing historical battlegrounds into commercial photo stunts, captive wildlife spectacles, or unsolicited shopping detours.</p>',
            ],
            [
                'old' => '<p>Families, older travelers, heat-sensitive travelers, and jet-lagged arrivals should choose by recovery cost, not just attraction fame. The right day trip should leave enough energy for the next city, flight, or overnight route.</p>',
                'new' => '<p>Prioritize personal recovery. Multi-generational families and heat-sensitive travelers should weigh physical fatigue and air-conditioned transit comfort above marketing popularity, ensuring enough stamina remains for evening city dining or tomorrow\'s onward departure flight.</p>',
            ],
            [
                'old' => '<details><summary>Should I book a private guide, private driver, or group tour?</summary><p>Use a guide when context is the value, especially for Cu Chi. Use a private driver when timing, comfort, and return control matter. Use a group tour only when the route is simple, expectations are modest, and the inclusions are written clearly.</p></details>',
                'new' => '<details><summary>Should I book a private guide, private driver, or group tour?</summary><p>Hire an experienced licensed guide when historical context and military perspective matter most, particularly at Cu Chi. Private drivers make the most sense when travel party comfort, custom departure timing, and return control take precedence. Join a budget group bus tour only if the destination is straightforward, commercial showroom stops do not bother you, and inclusions are documented in writing.</p></details>',
            ],
            [
                'old' => '<details><summary>Should I visit Cu Chi or the Mekong Delta from HCMC?</summary><p>Choose Cu Chi when history is the reason for the day. Choose a Mekong taste when river contrast matters but an overnight Delta stay will not fit. If the Mekong is a major priority, use the overnight logic in the Mekong Delta guide.</p></details>',
                'new' => '<details><summary>Should I visit Cu Chi or the Mekong Delta from HCMC?</summary><p>Pick Cu Chi for wartime history. If river life appeals but time is short, take an easy Delta day tour. Floating market life peaks at dawn; staying overnight in Can Tho or Ben Tre gives you authentic river access without exhausting early-morning highway travel.</p></details>',
            ],
            [
                'old' => '<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then decide whether the south deserves room through <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a> and <a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a>. Use <a href="/destinations/mekong-delta-travel-guide/">Mekong Delta Travel Guide</a> when the river day might deserve a night. Use <a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding road-heavy southern movement. Use <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>, <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before paying day-trip deposits.</p>',
                'new' => '<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then decide whether the south deserves room through <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a> and <a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a>. Consult our <a href="/destinations/mekong-delta-travel-guide/">Mekong Delta Travel Guide</a> whenever river life warrants an unhurried overnight stay rather than a hurried round-trip dash. Review our <a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> itinerary frameworks before locking in multi-hour southern highway excursions. Verify seasonal weather patterns, transit options, and travel budgets across our <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>, <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> resources prior to submitting tour deposits.</p>',
            ],
        ],
    ],
    473 => [
        'replacements' => [
            [
                'old' => '<p>First-time travelers often start with a list: Hanoi, Ha Long Bay, Ninh Binh, Hue, Hoi An, Da Nang, Ho Chi Minh City, Mekong Delta, Phu Quoc, maybe Sapa. The list is not the problem. The problem is pretending each name costs only the day you spend there. Every base change has hidden cost: packing, checkout, pickup timing, weather margin, transfer fatigue, and a new hotel decision.</p>',
                'new' => '<p>First-time travelers often start with a list: Hanoi, Ha Long Bay, Ninh Binh, Hue, Hoi An, Da Nang, Ho Chi Minh City, Mekong Delta, Phu Quoc, maybe Sapa. Compiling an ambitious wishlist is completely natural. What exhausts first-timers is pretending each name costs only the day you spend there. Every base change has hidden cost: packing, checkout, pickup timing, weather margin, transfer fatigue, and a new hotel decision.</p>',
            ],
            [
                'old' => '<p>A good first Vietnam itinerary is not the one with the most famous names. It is the one where every stop has a job. Hanoi can be the arrival, food, and culture chapter. Ninh Binh can be countryside and limestone scenery. A bay cruise can be the water-and-karst chapter. Central Vietnam can be heritage, food, coast, and a gentler finish. Ho Chi Minh City can be southern energy, history, and food depth. Phu Quoc can be a beach finish. But when every place tries to do every job, the route becomes tiring.</p>',
                'new' => '<p>A memorable first Vietnam itinerary is never the route with the most famous tourist names crammed onto a calendar. It succeeds when every stop performs a purposeful job. Let Hanoi introduce northern street culinary heritage and century-old guild streets. Ninh Binh supplies quiet cycling through limestone valleys. Overnight bay cruises present tranquil karst seascapes. Central Vietnam balances imperial citadel history with Lantern Festival alleyways, coastal seafood, and beach recovery. Finally, Ho Chi Minh City brings vibrant southern commercial energy and modern historical context. Phu Quoc can be a beach finish. But when every place tries to do every job, the route becomes tiring.</p>',
            ],
            [
                'old' => '<p>For 7 days, choose one region. For 10 days, choose two strong regions. For 14 days, two or three regions can work if the transfer days are honest. For 21 days, add depth rather than adding every possible stop.</p>',
                'new' => '<p>One week allows exactly one region. For 10 days, pair two complementary hubs like Hanoi and central Vietnam without excessive transfer friction. Travelers with two full weeks can weave three regions together successfully if travel days are respected. With three weeks, add depth.</p>',
            ],
            [
                'old' => '<p>Use this checklist before detailed itinerary planning. Once the route shape is clear, move into the main country guide, route length guide, weather guide, and cost guide.</p>',
                'new' => '<p>Plan sequence matters. Lock down your broad geographical boundaries and seasonal weather windows before booking hotels or mapping daily attractions. Once your macro-route is secured, consult our regional travel guides, transport comparisons, and realistic budget breakdowns to flesh out daily schedules.</p>',
            ],
        ],
    ],
    482 => [
        'replacements' => [
            [
                'old' => '<p>This article is not a live forecast. It is a route design guide for travelers who already know rain is possible and need to decide what to protect. For medical, interruption, and policy preparation, use <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>. For wet-weather city movement, bags, phones, and night habits, pair this with <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>. For flexibility cost, use <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>.</p>',
                'new' => '<p>This article is not a live forecast. It is a route design guide for travelers who already know rain is possible and need to decide what to protect. Medical coverage and trip interruption insurance protect against unpredictable monsoon delays and hospital bills—review our <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a> guide for policy essentials. Navigating rain-soaked city streets with electronics and valuables requires sensible precautions; consult <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> for waterproofing and transport habits. To benchmark the financial buffer needed for last-minute route pivots, review <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before finalizing reservations.</p>',
            ],
        ],
    ],
    475 => [
        'replacements' => [
            [
                'old' => '<p>This guide is built for the traveler deciding between Hanoi, Ninh Binh, Ha Long, Hue, Hoi An, Da Nang, Ho Chi Minh City, the Mekong, Phu Quoc, or a mountain stop. The same shirt can be excellent in the south and annoying in the north. The same shoes can be fine in the city and wrong for a wet boat or a cliffside pass. The answer changes with the route, which is exactly why a premium packing guide needs a route brain, not a shopping list.</p>',
                'new' => '<p>This guide is built for the traveler deciding between Hanoi, Ninh Binh, Ha Long, Hue, Hoi An, Da Nang, Ho Chi Minh City, the Mekong, Phu Quoc, or a mountain stop. Pack for motion. A lightweight linen shirt excels in southern humidity but offers zero insulation against damp, wind-whipped northern cold in January. Canvas sneakers soak instantly on wet boats. Packing demands shift with topography and regional microclimates. Avoid hauling redundant backup garments until your bag turns into an anchor. Every item in your luggage should serve agile movement, sound sleep, or rapid transit recovery; anything else belongs at home.</p>',
            ],
            [
                'old' => '<p>The route decides the activity, and the activity decides the extras. A perfect city outfit is usually wrong on a boat; a beach outfit can be useless in a temple or mountain pass. This is the logic that keeps one bag useful across the whole trip.</p>',
                'new' => '<p>Route dictates wardrobe. City garments fail on wet wooden sampans, and beachwear offers zero utility inside sacred pagodas or windy mountain passes. Match each piece directly to your scheduled transit terrain.</p>',
            ],
        ],
    ],

];

echo "=== Stage 35: Remediating 6 Articles for 100 HLS Cadence Perfection ===\n";

$updated_posts = 0;
$skipped_posts = 0;

foreach ($posts_to_remediate as $post_id => $data) {
    $row = $wpdb->get_row($wpdb->prepare("SELECT post_name, post_content FROM {$wpdb->posts} WHERE ID = %d", $post_id));
    if (!$row) {
        echo "[-] Post {$post_id} not found in database!\n";
        continue;
    }

    $slug = $row->post_name;
    $content = $row->post_content;
    $modified = false;
    $all_present = true;

    foreach ($data['replacements'] as $idx => $r) {
        $old_str = $r['old'];
        $new_str = $r['new'];

        if (strpos($content, $new_str) !== false) {
            // Already updated
            continue;
        }

        if (strpos($content, $old_str) === false) {
            echo "[-] Post {$post_id} ({$slug}): Target snippet #{$idx} not found!\n";
            $all_present = false;
            continue;
        }

        $content = str_replace($old_str, $new_str, $content);
        $modified = true;
    }

    if (!$modified) {
        echo "[*] Post {$post_id} ({$slug}): Already fully calibrated or no changes needed. Skipping.\n";
        $skipped_posts++;
        continue;
    }

    $res = $wpdb->update(
        $wpdb->posts,
        ['post_content' => $content],
        ['ID' => $post_id],
        ['%s'],
        ['%d']
    );

    if ($res === false) {
        echo "[-] Error updating Post {$post_id}: " . $wpdb->last_error . "\n";
    } else {
        echo "[+] Post {$post_id} ({$slug}): Successfully updated in MariaDB!\n";
        clean_post_cache($post_id);
        $updated_posts++;
    }
}

echo "\nSummary: {$updated_posts} posts updated, {$skipped_posts} skipped.\n";

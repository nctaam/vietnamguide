<?php
/**
 * Expand the Hanoi First-Time Visitor Mistakes post brief into a complete draft.
 *
 * Run from the WordPress root:
 * VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 wp eval-file ops/apply-hanoi-first-time-visitor-mistakes-post.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('WP_CLI') || ! WP_CLI) {
    echo 'This script must be run with WP-CLI.' . PHP_EOL;
    exit(1);
}

function vg_hanoi_mistakes_post_fail(string $message): void
{
    WP_CLI::error($message);
}

function vg_hanoi_mistakes_post_env_truthy(string $name): bool
{
    $value = getenv($name);

    return is_string($value) && trim($value) === '1';
}

if (! vg_hanoi_mistakes_post_env_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')) {
    vg_hanoi_mistakes_post_fail('This Admin-first draft is locked. Rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after confirming the exact target post and backup state.');
}

function vg_hanoi_mistakes_post_find_by_slug(string $slug): ?WP_Post
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
        vg_hanoi_mistakes_post_fail("Expected exactly one post with slug {$slug}; found " . count($posts) . '.');
    }

    return $posts[0] ?? null;
}

function vg_hanoi_mistakes_post_assert_target_meta(WP_Post $post): void
{
    $post_id = (int) $post->ID;

    foreach (
        [
            'vg_editorial_target_publish_date' => '2026-08-05',
            'vg_editorial_brief_status' => 'brief',
            'vg_content_owner' => 'wp_admin',
            'vg_automation_lock' => 'locked',
        ] as $meta_key => $expected_value
    ) {
        $actual_value = trim((string) get_post_meta($post_id, $meta_key, true));

        if ($actual_value !== $expected_value) {
            vg_hanoi_mistakes_post_fail("Target post meta mismatch for {$meta_key}: {$actual_value}; expected {$expected_value}.");
        }
    }
}

function vg_hanoi_mistakes_post_term_ids(string $taxonomy, array $slugs): array
{
    $ids = [];

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (! $term instanceof WP_Term) {
            vg_hanoi_mistakes_post_fail("Missing {$taxonomy} term: {$slug}");
        }

        $ids[] = (int) $term->term_id;
    }

    return $ids;
}

$slug = 'hanoi-first-time-visitor-mistakes';
$post = vg_hanoi_mistakes_post_find_by_slug($slug);

if (! $post instanceof WP_Post) {
    vg_hanoi_mistakes_post_fail("Required draft post not found: {$slug}");
}

if ($post->post_status !== 'draft') {
    vg_hanoi_mistakes_post_fail("Refusing to update {$slug} because it is not draft. Current status: {$post->post_status}");
}

$post_id = (int) $post->ID;
$review_date = 'July 25, 2026';
vg_hanoi_mistakes_post_assert_target_meta($post);

$hero_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg';
$old_quarter_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/Cho_Dong_Xuan%2C_Old_Quarter%2C_Hanoi%2C_Vietnam_%285245806573%29.jpg/1920px-Cho_Dong_Xuan%2C_Old_Quarter%2C_Hanoi%2C_Vietnam_%285245806573%29.jpg';
$noi_bai_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/38/Noi_Bai_International_Airport_Terminal_2_Night_View.JPG/1920px-Noi_Bai_International_Airport_Terminal_2_Night_View.JPG';
$temple_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/Main_gate_of_the_Temple_of_Literature%2C_Hanoi%2C_Vietnam%2C_20240123_0929_3068.jpg/1920px-Main_gate_of_the_Temple_of_Literature%2C_Hanoi%2C_Vietnam%2C_20240123_0929_3068.jpg';
$thang_long_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0f/Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg/1920px-Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg';
$long_bien_image = 'https://upload.wikimedia.org/wikipedia/commons/c/c5/Long_Bien_Bridge.jpg';

$content = <<<HTML
<!-- vg-hanoi-mistakes-hero:v1 -->
<!-- wp:html -->
<section class="vg-guide-hero vg-hanoi-mistakes-hero">
<div class="vg-guide-hero-copy">
<p class="vg-kicker">Hanoi first-time mistake prevention</p>
<h1>Hanoi First-Time Visitor Mistakes to Avoid</h1>
<p class="vg-guide-lede">Hanoi rarely fails because a traveler misses one famous stop. It fails when the first base, first night, day-trip order, weather plan, and northern route pressure are decided in the wrong sequence.</p>
<p class="vg-field-note">Use this as a diagnostic layer before booking: mistake -&gt; symptom -&gt; correction -&gt; cluster guide. The goal is not to make Hanoi smaller. It is to let Hanoi do the right job before Ninh Binh, Ha Long Bay, Lan Ha Bay, Cat Ba, Sapa, Ha Giang, or Central Vietnam starts pulling the route apart.</p>
</div>
<figure class="vg-guide-hero-image"><img src="{$hero_image}" alt="Hoan Kiem Lake in central Hanoi, Vietnam" loading="eager" decoding="async"><figcaption>Hoan Kiem is useful because it gives first-time travelers a readable center before the route becomes transfer-heavy. Image: Alex 69200 vx / CC BY-SA 4.0.</figcaption></figure>
</section>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_editorial_proof]
<!-- /wp:shortcode -->

<!-- vg-hanoi-mistakes-concierge-verdict -->
<!-- wp:html -->
<aside class="vg-concierge-verdict vg-hanoi-mistakes-verdict" aria-label="Hanoi first-time visitor mistake verdict">
<p class="vg-kicker">VietnamGuide verdict</p>
<h2>Most Hanoi mistakes are sequencing mistakes.</h2>
<p><strong>The better first-time plan is central base, calm arrival, one orientation block, one serious city block, then one clean northern decision.</strong> Do not treat Hanoi as just an airport night, do not book the room before naming the next transfer, and do not add Ninh Binh, Ha Long, Lan Ha, Cat Ba, Sapa, and Ha Giang simply because they all appear near Hanoi in search results.</p>
<ul>
<li><strong>Best first-time default:</strong> two or three central nights around Hoan Kiem, the Old Quarter edge, or a calmer French Quarter pocket.</li>
<li><strong>Best first-night rule:</strong> if Noi Bai arrival is late, solve hotel, food, cash/data if needed, and sleep before chasing atmosphere.</li>
<li><strong>Best day-trip rule:</strong> give the longest outside day a protected middle day, not arrival day or departure day.</li>
<li><strong>Best anti-spam rule:</strong> a place belongs in the route only if it has a job beyond being famous.</li>
</ul>
</aside>
<!-- /wp:html -->

<!-- wp:paragraph -->
<p>This guide is written for international visitors using Hanoi as a first Vietnam city, northern base, or short capital chapter. It is not another list of things to do in Hanoi. For attraction priority, use <a href="/destinations/best-things-to-do-in-hanoi/">Best Things to Do in Hanoi</a>. For the city pillar, start with <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>. This page focuses on the moments where first-time decisions usually go wrong.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The durable pattern is simple: first-time travelers overvalue coverage and undervalue friction. A hotel ten minutes farther from the right pickup zone can cost the morning. A day trip after a late arrival can weaken both Hanoi and the excursion. A full northern checklist can make every famous place feel thinner. The correction is to decide what Hanoi must protect before adding more movement.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Visible external links are intentionally avoided in the article body. Official source checks, image records, and update notes are stored in the source trail so the reading path stays focused on planning decisions rather than source clutter.</p>
<!-- /wp:paragraph -->

<!-- vg-hanoi-mistakes-at-a-glance:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hanoi mistakes at a glance</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-mistakes-at-a-glance">
<thead><tr><th>Mistake</th><th>Symptom on the trip</th><th>Better correction</th><th>Use this guide next</th></tr></thead>
<tbody>
<tr><td data-label="Mistake">Choosing a hotel because the area sounds famous</td><td data-label="Symptom on the trip">Sleep is poor, pickup is unclear, taxis cannot reach the door, or the first walk feels noisy instead of useful.</td><td data-label="Better correction">Choose the base by first-night job: orientation, calmer sleep, family space, history days, or airport buffer.</td><td data-label="Use this guide next"><a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a></td></tr>
<tr><td data-label="Mistake">Treating arrival night as a sightseeing night</td><td data-label="Symptom on the trip">Low battery, cash/data uncertainty, rushed dinner, missed check-in details, and weaker judgment around transport.</td><td data-label="Better correction">Make arrival night boring: verified transfer, nearby meal, short walk only if energy is real.</td><td data-label="Use this guide next"><a href="/plan/hanoi-airport-to-old-quarter/">Hanoi Airport to Old Quarter</a></td></tr>
<tr><td data-label="Mistake">Putting the hardest day trip too early</td><td data-label="Symptom on the trip">Ninh Binh, bay, or mountain movement becomes a recovery problem instead of a highlight.</td><td data-label="Better correction">Place the longest outside day in the middle of a Hanoi stay and keep the previous evening light.</td><td data-label="Use this guide next"><a href="/destinations/best-day-trips-from-hanoi/">Best Day Trips from Hanoi</a></td></tr>
<tr><td data-label="Mistake">Adding every northern icon</td><td data-label="Symptom on the trip">The map looks rich but every place feels rushed, transfer-heavy, and hard to remember.</td><td data-label="Better correction">Choose one city chapter, one countryside or bay chapter, then only one mountain or longer extension if days allow.</td><td data-label="Use this guide next"><a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a></td></tr>
<tr><td data-label="Mistake">Planning a walking-heavy day with no weather pivot</td><td data-label="Symptom on the trip">Heat, rain, cold, or poor air makes the schedule collapse into taxi fragments.</td><td data-label="Better correction">Hold one indoor culture stop, one cafe/food pivot, and a shorter loop for weak weather.</td><td data-label="Use this guide next"><a href="/itineraries/hanoi-in-2-days/">Hanoi in 2 Days</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-mistakes-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: the mistakes are about context, not icons</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-hanoi-mistakes-photo-grid" aria-label="Hanoi first-time visitor mistake context photography">
<figure class="vg-guide-photo"><img src="{$old_quarter_image}" alt="Dong Xuan Market in Hanoi Old Quarter" loading="lazy" decoding="async"><figcaption>The Old Quarter gives first-time energy, food, and pickup density, but exact block choice matters. Image: yeowatzup / CC BY 2.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$noi_bai_image}" alt="Noi Bai International Airport Terminal 2 at night" loading="lazy" decoding="async"><figcaption>A late Noi Bai arrival should shrink the first evening, not make it more ambitious. Image: Christakis Mina / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$temple_image}" alt="Main gate of the Temple of Literature in Hanoi" loading="lazy" decoding="async"><figcaption>One serious culture block is more valuable than rushing three named stops. Image: Jakub Halun / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$thang_long_image}" alt="Central Sector of the Imperial Citadel of Thang Long in Hanoi" loading="lazy" decoding="async"><figcaption>Thang Long is best when UNESCO and capital history get time to be understood. Image: katiebordner / CC BY 2.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$long_bien_image}" alt="Long Bien Bridge in Hanoi" loading="lazy" decoding="async"><figcaption>Hanoi becomes better when the route leaves room for slower observation. Image: TheRollo76 / CC BY-SA 4.0.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-hanoi-mistakes-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How the evidence should be used</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Official sources can confirm Hanoi's destination frame, airport context, weather reference points, and heritage status. They cannot decide your hotel block, fatigue level, child tolerance, exact pickup friction, or whether the route has enough recovery. That is where original editorial judgment matters.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-mistakes-source-diversity">
<thead><tr><th>Evidence layer</th><th>What it supports</th><th>What still needs judgment</th></tr></thead>
<tbody>
<tr><td data-label="Evidence layer">Official destination guidance</td><td data-label="What it supports">Hanoi as a northern Vietnam capital, culture, food, lake, and old-quarter chapter.</td><td data-label="What still needs judgment">How many nights the route can honestly give the city.</td></tr>
<tr><td data-label="Evidence layer">Noi Bai airport information</td><td data-label="What it supports">Arrival and departure planning, terminal context, and why first-night transfer clarity matters.</td><td data-label="What still needs judgment">Whether to pay for hotel pickup, official taxi, ride-hailing, bus, or an airport-side room.</td></tr>
<tr><td data-label="Evidence layer">Weather and forecast sources</td><td data-label="What it supports">Heat, rain, cold, storm, and comfort checks before walking days or northern transfers.</td><td data-label="What still needs judgment">How much outdoor walking and transfer pressure your party can absorb.</td></tr>
<tr><td data-label="Evidence layer">Heritage sources</td><td data-label="What it supports">Why Temple of Literature and Thang Long can be meaningful culture blocks.</td><td data-label="What still needs judgment">Whether the short stay should prioritize heritage, food, rest, or one outside day.</td></tr>
<tr><td data-label="Evidence layer">Image-license records</td><td data-label="What it supports">Photo-led context with public credits and durable visual proof.</td><td data-label="What still needs judgment">Current crowding, hotel construction, noise, and day-of opening changes.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-mistakes-base-choice:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mistake 1: choosing the wrong Hanoi base for the job</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A first-time Hanoi hotel is not only a room. It is the first dinner, the first street crossing, the first ride-hailing attempt, the first pickup conversation, and sometimes the first recovery night after a long-haul flight. The mistake is choosing the area by reputation alone: "Old Quarter is famous", "West Lake looks calmer", "airport-side is convenient", or "this room is cheaper". Those statements can all be true and still be the wrong answer.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>For most first-time visitors, Hoan Kiem or the calmer Old Quarter edge works because it shortens the learning curve. French Quarter or south Hoan Kiem works when cars, premium hotels, quieter nights, and calmer streets matter more. Ba Dinh works when history and sleep matter. Tay Ho works for longer stays, families, cafes, and apartments. Noi Bai airport-side works only when the flight chain is the job.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-mistakes-base-choice">
<thead><tr><th>Bad base logic</th><th>Trip symptom</th><th>Better filter</th><th>Read next</th></tr></thead>
<tbody>
<tr><td data-label="Bad base logic">"Old Quarter is always best."</td><td data-label="Trip symptom">Noise, stairs, narrow-lane luggage, and tired arrival stress.</td><td data-label="Better filter">Choose Old Quarter edge or Hoan Kiem when walking and food matter, then check block-level noise.</td><td data-label="Read next"><a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a></td></tr>
<tr><td data-label="Bad base logic">"West Lake looks nicer."</td><td data-label="Trip symptom">More taxi time than expected during a two-night first stay.</td><td data-label="Better filter">Use Tay Ho for longer, family, apartment, or repeat stays rather than a short first visit by default.</td><td data-label="Read next"><a href="/compare/old-quarter-vs-french-quarter-vs-west-lake/">Old Quarter vs French Quarter vs West Lake</a></td></tr>
<tr><td data-label="Bad base logic">"Airport-side saves stress."</td><td data-label="Trip symptom">A usable Hanoi evening disappears beside the airport.</td><td data-label="Better filter">Stay airport-side only for late arrivals, early flights, separate tickets, or fragile weather/connection risk.</td><td data-label="Read next"><a href="/plan/hanoi-airport-to-old-quarter/">Hanoi Airport to Old Quarter</a></td></tr>
<tr><td data-label="Bad base logic">"Cheapest central room wins."</td><td data-label="Trip symptom">Bad sleep, hard pickup, weak front desk, or a room that steals the first morning.</td><td data-label="Better filter">Price location, room quiet, elevator, late check-in, and front-desk competence as part of trip cost.</td><td data-label="Read next"><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-mistakes-arrival-overload:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mistake 2: overloading the arrival window</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Hanoi's first hour asks for more than it seems: immigration, luggage, phone data or Wi-Fi, cash or card confidence, airport transfer, hotel address, check-in, and a first meal. The mistake is adding a food tour, a distant bar, a full Old Quarter wander, or a next-morning dawn pickup before those basics are stable.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Late arrivals should be edited down. A calm airport-to-hotel transfer is not wasted money when it protects the first full day. If the flight lands in daylight and everyone is rested, a lake walk and nearby dinner can work. If the flight lands late, the best Hanoi experience may be not forcing Hanoi yet.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-mistakes-arrival-overload">
<thead><tr><th>Arrival condition</th><th>Common overreach</th><th>Better first-night move</th><th>Why it works</th></tr></thead>
<tbody>
<tr><td data-label="Arrival condition">Late long-haul arrival</td><td data-label="Common overreach">Food tour, beer street, or early Ninh Binh pickup.</td><td data-label="Better first-night move">Verified transfer, hotel check-in, nearby food, sleep.</td><td data-label="Why it works">The first full day gets real attention instead of inherited fatigue.</td></tr>
<tr><td data-label="Arrival condition">Daylight arrival with checked bags</td><td data-label="Common overreach">Dragging luggage into wandering before the hotel is stable.</td><td data-label="Better first-night move">Check in first, then one short orientation loop.</td><td data-label="Why it works">The city becomes readable after bags and documents are safe.</td></tr>
<tr><td data-label="Arrival condition">Family or older traveler arrival</td><td data-label="Common overreach">Optimizing the cheapest transfer while everyone waits.</td><td data-label="Better first-night move">Buy certainty if it reduces decisions and walking.</td><td data-label="Why it works">Comfort protects mood, safety, and the next morning.</td></tr>
<tr><td data-label="Arrival condition">Arrive before a bay or mountain route</td><td data-label="Common overreach">Treating Hanoi as just a bed before another transfer.</td><td data-label="Better first-night move">Stay central if the city has value, or airport-side only if flight risk is the point.</td><td data-label="Why it works">The hotel choice matches the real job of the night.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-mistakes-day-trip-sequencing:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mistake 3: sequencing day trips badly</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ninh Binh, Ha Long, Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, and Ba Vi are not interchangeable add-ons. A good outside day from Hanoi needs the right placement. The longest day usually belongs in the middle of the stay, after the arrival night is stable and before departure pressure begins.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>The biggest planning trap is believing a day trip is cheap because it does not require another hotel. Road time, pickup uncertainty, early starts, lunch quality, weather, return hour, and the next morning all have a cost. If the route already includes Ninh Binh overnight, a bay cruise, Cat Ba, or a mountain chapter, the best Hanoi day trip may be a better Hanoi city day.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-mistakes-day-trip-sequencing">
<thead><tr><th>Outside choice</th><th>Best placement</th><th>Common mistake</th><th>Correction</th></tr></thead>
<tbody>
<tr><td data-label="Outside choice">Ninh Binh</td><td data-label="Best placement">Protected middle day or overnight chapter.</td><td data-label="Common mistake">Adding too many stops and returning exhausted before another transfer.</td><td data-label="Correction">Use one boat route plus one major land stop, or sleep in Ninh Binh.</td></tr>
<tr><td data-label="Outside choice">Ha Long or Lan Ha</td><td data-label="Best placement">Usually overnight when the bay is a priority.</td><td data-label="Common mistake">Buying a vague same-day cruise because photos look iconic.</td><td data-label="Correction">Ask whether deck time, route, port, weather policy, and return window justify the day.</td></tr>
<tr><td data-label="Outside choice">Bat Trang</td><td data-label="Best placement">Light half-day, weak-weather day, family craft day, or late-start day.</td><td data-label="Common mistake">Treating it as filler shopping with no context.</td><td data-label="Correction">Use it when craft and short transfer are the point.</td></tr>
<tr><td data-label="Outside choice">Perfume Pagoda or Ba Vi</td><td data-label="Best placement">Only when the journey, pilgrimage, forest, or cooler-air day is the actual interest.</td><td data-label="Common mistake">Adding them after the route already has stronger scenery.</td><td data-label="Correction">Skip when weather, mobility, or route fatigue makes the day the wrong kind of hard.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-mistakes-route-crowding:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mistake 4: crowding the northern route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Search makes northern Vietnam look compact. Hanoi, Ninh Binh, Ha Long Bay, Lan Ha Bay, Cat Ba, Bai Tu Long, Sapa, Ha Giang, Pu Luong, and Central Vietnam all compete for the same first-trip days. The map does not show the real cost: early pickups, road fatigue, luggage handling, weather risk, hotel changes, and lost city evenings.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>A better route names each chapter. Hanoi can be capital rhythm and food. Ninh Binh can be inland karst and countryside. Ha Long, Lan Ha, Cat Ba, or Bai Tu Long can be water and limestone. Sapa, Ha Giang, or Pu Luong can be mountains and villages. Central Vietnam can be heritage, food, and coast. If two places are doing the same job in a short trip, one should probably be cut or upgraded into a real chapter.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-mistakes-route-crowding">
<thead><tr><th>Route pressure</th><th>What it feels like</th><th>Better route decision</th><th>Useful next guide</th></tr></thead>
<tbody>
<tr><td data-label="Route pressure">7 days in Vietnam</td><td data-label="What it feels like">Every day becomes a move if north, central, and south all appear.</td><td data-label="Better route decision">Choose one region and make Hanoi a real chapter if the north is the focus.</td><td data-label="Useful next guide"><a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a></td></tr>
<tr><td data-label="Route pressure">10 days in Vietnam</td><td data-label="What it feels like">Two regions can work; three often makes Hanoi thinner than expected.</td><td data-label="Better route decision">Use Hanoi plus one northern scenery decision, then one central or southern chapter.</td><td data-label="Useful next guide"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Route pressure">14 days in Vietnam</td><td data-label="What it feels like">Enough time to add depth, but not enough to treat every famous name equally.</td><td data-label="Better route decision">Upgrade one high-friction place into an overnight or cut it cleanly.</td><td data-label="Useful next guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Route pressure">21 days in Vietnam</td><td data-label="What it feels like">Longer trip still becomes thin when transfers are stacked without rest.</td><td data-label="Better route decision">Build depth by chapters instead of adding every possible stop.</td><td data-label="Useful next guide"><a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-mistakes-weather-vs-pacing:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mistake 5: treating weather as a footnote</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Hanoi plans often fail when they assume perfect walking conditions. Heat can make midday Old Quarter wandering feel harder than expected. Rain can make open-air photo stops weak. Cooler northern days can be pleasant in the city and still signal colder mountain conditions. Storm disruption can make bay or mountain movement more fragile than the city itself.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>The correction is not to memorize a month chart and stop thinking. It is to keep one weather pivot inside the day: an indoor culture stop, a cafe/food loop, a shorter lake walk, a taxi-supported heritage block, or a lighter evening before a transfer. For a short stay, weather flexibility is quality, not indecision.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-mistakes-weather-vs-pacing">
<thead><tr><th>Condition</th><th>Common mistake</th><th>Better Hanoi pivot</th><th>What it protects</th></tr></thead>
<tbody>
<tr><td data-label="Condition">Heat and humidity</td><td data-label="Common mistake">Long open-air route from late morning through afternoon.</td><td data-label="Better Hanoi pivot">Early lake/Old Quarter loop, midday cafe or museum, shorter evening food route.</td><td data-label="What it protects">Energy before Ninh Binh, bay, or mountain movement.</td></tr>
<tr><td data-label="Condition">Rain</td><td data-label="Common mistake">Keeping the same walking map and hoping it works.</td><td data-label="Better Hanoi pivot">Temple of Literature if manageable, Women's Museum, coffee, food, or hotel-area plan.</td><td data-label="What it protects">Mood, dry gear, and tomorrow's pickup.</td></tr>
<tr><td data-label="Condition">Cooler winter rhythm</td><td data-label="Common mistake">Packing only tropical assumptions.</td><td data-label="Better Hanoi pivot">Use longer walks in the city, then pack/check mountains separately.</td><td data-label="What it protects">Comfort across north-city and north-mountain contrast.</td></tr>
<tr><td data-label="Condition">Storm or disruption risk</td><td data-label="Common mistake">Locking fragile bay, mountain, and airport chains too tightly.</td><td data-label="Better Hanoi pivot">Keep Hanoi as a buffer and re-check operators close to travel.</td><td data-label="What it protects">The whole northern route, not just one day.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-mistakes-correction-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Correction matrix: fix the trip before it is booked</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this matrix when the itinerary looks exciting but fragile. The point is not to remove ambition. It is to move ambition to the right day, right base, and right guide.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-mistakes-correction-matrix">
<thead><tr><th>If you notice...</th><th>Likely hidden problem</th><th>Fix now</th><th>Cluster guide that solves it</th></tr></thead>
<tbody>
<tr><td data-label="If you notice...">Only one Hanoi night between two transfers</td><td data-label="Likely hidden problem">Hanoi is becoming a logistics placeholder.</td><td data-label="Fix now">Add a second night or accept that Hanoi is not a real chapter.</td><td data-label="Cluster guide that solves it"><a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a></td></tr>
<tr><td data-label="If you notice...">Hotel chosen before the next pickup is known</td><td data-label="Likely hidden problem">Morning friction may be hidden in a pretty room listing.</td><td data-label="Fix now">Ask pickup zone, vehicle access, late check-in, and noise questions before paying.</td><td data-label="Cluster guide that solves it"><a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a></td></tr>
<tr><td data-label="If you notice...">Ninh Binh day after late arrival</td><td data-label="Likely hidden problem">The countryside day will inherit airport fatigue.</td><td data-label="Fix now">Move Ninh Binh later, sleep there, or keep Day 1 in Hanoi.</td><td data-label="Cluster guide that solves it"><a href="/destinations/best-day-trips-from-hanoi/">Best Day Trips from Hanoi</a></td></tr>
<tr><td data-label="If you notice...">Ha Long or Lan Ha day cruise because overnight seems expensive</td><td data-label="Likely hidden problem">Transfer-to-experience ratio may be weak.</td><td data-label="Fix now">Compare day cruise against overnight value and weather policy.</td><td data-label="Cluster guide that solves it"><a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a></td></tr>
<tr><td data-label="If you notice...">Every Hanoi day starts early and ends late</td><td data-label="Likely hidden problem">No recovery, no weather pivot, no actual food rhythm.</td><td data-label="Fix now">Protect one city block, one food evening, and one lighter transfer eve.</td><td data-label="Cluster guide that solves it"><a href="/itineraries/hanoi-in-2-days/">Hanoi in 2 Days</a></td></tr>
<tr><td data-label="If you notice...">The plan depends on exact weather behaving</td><td data-label="Likely hidden problem">Outdoor and transfer risk are underpriced.</td><td data-label="Fix now">Add one indoor choice, one flexible meal, and a backup transport window.</td><td data-label="Cluster guide that solves it"><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-mistakes-first-night:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The first-night rule that prevents half the damage</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The first night should make tomorrow easier. If your plan makes tomorrow harder, it is probably not a good first night. Hanoi rewards food, walking, cafes, markets, and street life, but only after the basics are under control. The best first-night plan is often smaller than the traveler wants and better than the itinerary looks on paper.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-mistakes-first-night">
<thead><tr><th>First-night profile</th><th>Do</th><th>Do not</th><th>Next morning should feel...</th></tr></thead>
<tbody>
<tr><td data-label="First-night profile">Late flight</td><td data-label="Do">Use verified transfer, easy hotel, nearby dinner, sleep.</td><td data-label="Do not">Book paid evening plans or a hard dawn pickup.</td><td data-label="Next morning should feel...">Rested enough to read the city.</td></tr>
<tr><td data-label="First-night profile">Daylight arrival</td><td data-label="Do">Check in, solve phone/cash, take one orientation loop.</td><td data-label="Do not">Turn the first walk into a full attraction route.</td><td data-label="Next morning should feel...">Confident, not depleted.</td></tr>
<tr><td data-label="First-night profile">Family or premium short stay</td><td data-label="Do">Pay for smoother handoffs where they remove decisions.</td><td data-label="Do not">Make everyone wait while one person optimizes a small fare.</td><td data-label="Next morning should feel...">Organized and calm.</td></tr>
<tr><td data-label="First-night profile">Before Ninh Binh, bay, or mountains</td><td data-label="Do">Keep dinner close and confirm pickup/luggage before bed.</td><td data-label="Do not">Add late nightlife because this is the only Hanoi evening.</td><td data-label="Next morning should feel...">Ready for the outside chapter.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-mistakes-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before you publish your own itinerary</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-mistakes-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-mistakes-live-checks">
<li>Confirm the arrival hour, terminal, luggage situation, and hotel check-in before deciding whether arrival night can hold more than dinner.</li>
<li>Ask the hotel or operator whether pickup is hotel-door, district-limited, or meeting-point only before choosing a Hanoi base.</li>
<li>Check same-week weather before walking-heavy Old Quarter, Hoan Kiem, Temple of Literature, Thang Long, bay, mountain, or Ninh Binh plans.</li>
<li>Check current opening details for culture stops close to travel, then choose one serious stop rather than a museum inventory.</li>
<li>Review the route against the mistake -&gt; symptom -&gt; correction -&gt; cluster guide pattern before paying for non-refundable pieces.</li>
<li>Keep this post as a diagnostic layer, then use the specialist guide for the real decision: stay area, airport transfer, two-day itinerary, day trip, or wider Vietnam route.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this mistake guide fits next</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this page before the final booking pass. Then move to the exact cluster guide that matches the mistake you are trying to prevent.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-related-routes vg-hanoi-mistakes-related-manual">
<ol class="vg-related-route-list">
<li><span class="vg-related-route-step">01</span><a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a><span class="vg-related-route-note">Use for the city role, best length of stay, transport posture, food/culture balance, and northern route fit.</span></li>
<li><span class="vg-related-route-step">02</span><a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a><span class="vg-related-route-note">Use when the mistake is base choice, first-night sleep, exact block, pickup, or airport buffer.</span></li>
<li><span class="vg-related-route-step">03</span><a href="/destinations/best-day-trips-from-hanoi/">Best Day Trips from Hanoi</a><span class="vg-related-route-note">Use when the mistake is adding Ninh Binh, bay, craft, village, pilgrimage, or nature days without testing transfer value.</span></li>
<li><span class="vg-related-route-step">04</span><a href="/itineraries/hanoi-in-2-days/">Hanoi in 2 Days</a><span class="vg-related-route-note">Use when the short stay needs a practical sequence rather than an attraction inventory.</span></li>
<li><span class="vg-related-route-step">05</span><a href="/plan/hanoi-airport-to-old-quarter/">Hanoi Airport to Old Quarter</a><span class="vg-related-route-note">Use when the first mistake risk is the airport-to-hotel handoff.</span></li>
</ol>
</div>
<!-- /wp:html -->

<!-- vg-hanoi-mistakes-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hanoi first-time visitor mistakes FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-hanoi-mistakes-faq">
<details><summary>What is the biggest mistake first-time visitors make in Hanoi?</summary><p>The biggest mistake is treating Hanoi as a logistics stop instead of a route chapter. Most visitors need at least a calm arrival, one orientation block, one food or culture block, and a clean next-transfer plan.</p></details>
<details><summary>How many nights should I stay in Hanoi the first time?</summary><p>Two nights is the practical minimum for most first-time visitors. Three nights is stronger when the route includes Ninh Binh, Ha Long or Lan Ha, Cat Ba, Sapa, Ha Giang, or a flight after Hanoi.</p></details>
<details><summary>Is it a mistake to stay in the Old Quarter?</summary><p>No. The mistake is choosing an exact block without checking noise, stairs, car access, luggage, and pickup fit. The Old Quarter edge or Hoan Kiem is often the best first default when the exact hotel solves those issues.</p></details>
<details><summary>Should I book a day trip right after arriving in Hanoi?</summary><p>Usually no after a late long-haul arrival. Put the hardest day trip on a protected middle day, or make Ninh Binh, the bay, or the mountains an overnight chapter if the route depends on it.</p></details>
<details><summary>Can I do Hanoi, Ninh Binh, Ha Long Bay, Sapa, and Ha Giang on one first trip?</summary><p>Only with enough days and honest recovery. On a short trip, that list usually means too many northern chapters competing for the same energy. Choose by route job, not by name collection.</p></details>
<details><summary>How do I make Hanoi feel less overwhelming?</summary><p>Stay central but choose the exact block carefully, keep the arrival night simple, use one short orientation loop first, add indoor or cafe pivots for weather, and avoid stacking major transfers back to back.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[vg_source_trail]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[vg_update_log]
<!-- /wp:shortcode -->
HTML;

$required_markers = [
    'vg-hanoi-mistakes-hero:v1',
    'vg-hanoi-mistakes-concierge-verdict',
    'vg-hanoi-mistakes-at-a-glance:v1',
    'vg-hanoi-mistakes-photo-grid:v1',
    'vg-hanoi-mistakes-source-diversity:v1',
    'vg-hanoi-mistakes-base-choice:v1',
    'vg-hanoi-mistakes-arrival-overload:v1',
    'vg-hanoi-mistakes-day-trip-sequencing:v1',
    'vg-hanoi-mistakes-route-crowding:v1',
    'vg-hanoi-mistakes-weather-vs-pacing:v1',
    'vg-hanoi-mistakes-correction-matrix:v1',
    'vg-hanoi-mistakes-live-checks:v1',
    'vg-hanoi-mistakes-first-night:v1',
    'vg-hanoi-mistakes-faq:v1',
    '[vg_editorial_proof]',
    '[vg_related_routes]',
    '[vg_source_trail]',
    '[vg_update_log]',
];

foreach ($required_markers as $marker) {
    if (! str_contains($content, $marker)) {
        vg_hanoi_mistakes_post_fail("Missing content marker before update: {$marker}");
    }
}

$result = wp_update_post(
    [
        'ID' => $post_id,
        'post_type' => 'post',
        'post_title' => 'Hanoi First-Time Visitor Mistakes to Avoid',
        'post_name' => $slug,
        'post_status' => 'draft',
        'post_content' => $content,
        'post_excerpt' => 'A diagnostic Hanoi first-time visitor guide that helps international travelers avoid bad base choice, arrival overload, weak day-trip sequencing, route crowding, and weather-pacing mistakes.',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_hanoi_mistakes_post_fail('Could not update Hanoi first-time visitor mistakes post: ' . $result->get_error_message());
}

delete_post_meta($post_id, 'vg_editorial_brief_status');
update_post_meta($post_id, 'vg_editorial_brief_status', 'complete_draft');
update_post_meta($post_id, 'rank_math_title', 'Hanoi First-Time Visitor Mistakes to Avoid');
update_post_meta($post_id, 'rank_math_description', 'Avoid common Hanoi first-time visitor mistakes around where to stay, arrival night, day trips, weather, and crowded northern Vietnam routes.');
update_post_meta($post_id, 'rank_math_focus_keyword', 'Hanoi first time visitor mistakes');
update_post_meta($post_id, 'vg_eeat_primary_decision', 'Use Hanoi as a calm first northern base: choose the hotel by first-night and pickup job, keep arrival simple, sequence day trips carefully, avoid route crowding, and add weather pivots before booking.');
update_post_meta($post_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($post_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($post_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($post_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($post_id, 'vg_eeat_update_summary', 'Expanded the native WordPress post brief into a complete diagnostic draft with a Hanoi mistake verdict, at-a-glance matrix, photo proof, source-diversity explanation, base-choice correction, arrival-overload guidance, day-trip sequencing, route-crowding filter, weather-pacing pivots, correction matrix, live checks, first-night rules, FAQ, related routes, source trail, and update log. The post remains draft for WordPress Admin review.');
update_post_meta($post_id, 'vg_eeat_sources_checked', "Vietnam.travel - Ha Noi destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-noi - checked {$review_date}; used for high-level Hanoi destination framing without replacing route judgment.\nNoi Bai International Airport - https://vietnamairport.vn/en/noi-bai-airport - checked {$review_date}; used for airport-arrival and first-night logistics framing without promising live pickup rules.\nNational Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked {$review_date}; used for weather-check discipline before walking-heavy city days and northern transfers.\nUNESCO - Central Sector of the Imperial Citadel of Thang Long - https://whc.unesco.org/en/list/1328/ - checked {$review_date}; used for heritage context and the argument for one serious culture block.\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}; used for regional weather framing and traveler comfort caveats.\nWikimedia Commons image direct URL - Hoan Kiem Lake - {$hero_image} - credit Alex 69200 vx / CC BY-SA 4.0 - license context checked {$review_date}.\nWikimedia Commons image direct URL - Dong Xuan Market and Old Quarter - {$old_quarter_image} - credit yeowatzup / CC BY 2.0 - license context checked {$review_date}.\nWikimedia Commons image direct URL - Noi Bai International Airport Terminal 2 Night View - {$noi_bai_image} - credit Christakis Mina / CC BY-SA 4.0 - license context checked {$review_date}.\nWikimedia Commons image direct URL - Temple of Literature main gate - {$temple_image} - credit Jakub Halun / CC BY 4.0 - license context checked {$review_date}.\nWikimedia Commons image direct URL - Central Sector of the Imperial Citadel of Thang Long - {$thang_long_image} - credit katiebordner / CC BY 2.0 - license context checked {$review_date}.\nWikimedia Commons image direct URL - Long Bien Bridge - {$long_bien_image} - credit TheRollo76 / CC BY-SA 4.0 - license context checked {$review_date}.");
update_post_meta($post_id, 'vg_eeat_field_note', 'This draft is a diagnostic planning layer for the Hanoi cluster. It avoids duplicating the Hanoi pillar, stay-area guide, day-trip guide, airport-transfer guide, and two-day itinerary by using the mistake -> symptom -> correction -> cluster guide structure.');
update_post_meta($post_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($post_id, 'vg_eeat_evidence_moat', "Diagnostic mistake -> symptom -> correction -> cluster guide structure rather than a rewritten list of Hanoi tips.\nClear distinction between base choice, arrival overload, day-trip sequencing, route crowding, weather pacing, and first-night behavior.\nInternal Hanoi cluster judgment links the article to the existing Hanoi Travel Guide, Where to Stay in Hanoi, Best Day Trips from Hanoi, Hanoi in 2 Days, and Hanoi Airport to Old Quarter pages without replacing them.\nSource-diversity explanation states what official sources can prove and what still requires traveler-specific judgment.\nDurable advice avoids exact fares, opening-hour promises, package rankings, and volatile operator claims.\nPhoto-led proof uses licensed public images with text-only credits and no visible external anchors in the body.\nSource trail and update log keep official URLs auditable while keeping the reading path low-linkout and decision-led.");
update_post_meta($post_id, 'vg_eeat_related_routes', "Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Use for city role, nights, food/culture balance, transport posture, and northern route fit.\nWhere to Stay in Hanoi | /destinations/where-to-stay-in-hanoi/ | Use for hotel area, first-night sleep, noise, pickup, family fit, and airport buffer decisions.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Use for Ninh Binh, bay, Bat Trang, village, pilgrimage, nature, and stay-in-Hanoi trade-offs.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Use when the short stay needs a realistic sequence instead of an attraction inventory.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Use when arrival hour, luggage, phone data, and transport verification shape the first night.\nOld Quarter vs French Quarter vs West Lake | /compare/old-quarter-vs-french-quarter-vs-west-lake/ | Use when the real hotel choice is central energy, calmer central comfort, or lake-side space.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Use for weather, rain, heat, cold, and storm-risk decisions before walking-heavy or northern transfer days.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Use when the Hanoi mistake is trying to make a two-region first trip carry too many northern chapters.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Use when small room, transfer, or tour savings may create larger trip friction.");
update_post_meta($post_id, 'vg_eeat_hero_image_credit', 'Hero image: Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0. Body images: Dong Xuan Market and Old Quarter by yeowatzup, CC BY 2.0; Noi Bai International Airport Terminal 2 Night View by Christakis Mina, CC BY-SA 4.0; Temple of Literature main gate by Jakub Halun, CC BY 4.0; Central Sector of the Imperial Citadel of Thang Long by katiebordner, CC BY 2.0; Long Bien Bridge by TheRollo76, CC BY-SA 4.0.');
update_post_meta($post_id, 'vg_content_owner', 'wp_admin');
update_post_meta($post_id, 'vg_automation_lock', 'locked');
update_post_meta($post_id, 'vg_last_manual_review', $review_date);
update_post_meta($post_id, 'vg_admin_first_notes', 'Complete draft created by controlled automation for WordPress Admin review. Keep draft until a manual editor previews desktop/mobile, confirms image presentation, checks Rank Math/social preview, and re-checks Hanoi, Noi Bai, NCHMF, UNESCO, weather, and image-license sources before publishing.');

wp_set_object_terms($post_id, vg_hanoi_mistakes_post_term_ids('category', ['destinations', 'travel-planning']), 'category', false);
wp_set_object_terms($post_id, vg_hanoi_mistakes_post_term_ids('post_tag', ['first-time-vietnam', 'route-planning', 'anti-spam-evergreen']), 'post_tag', false);

$required_meta = [
    'rank_math_title',
    'rank_math_description',
    'rank_math_focus_keyword',
    'vg_editorial_brief_status',
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
    'vg_content_owner',
    'vg_automation_lock',
];

foreach ($required_meta as $meta_key) {
    $meta_value = get_post_meta($post_id, $meta_key, true);

    if ((is_string($meta_value) && trim($meta_value) === '') || $meta_value === [] || $meta_value === null) {
        vg_hanoi_mistakes_post_fail("Required metadata was empty after update: {$meta_key}");
    }
}

WP_CLI::success("Expanded Hanoi first-time visitor mistakes post to complete draft: {$post_id}");

<?php
/**
 * Publish the Best Places to Visit in Vietnam destination guide.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-best-places-destination-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_DESTINATION_GUIDE_REPUBLISH');

    if (! is_string($value)) {
        return false;
    }

    return trim($value) === '1';
}

function vg_ops_publish_author_id(?WP_Post $existing_page = null): int
{
    if ($existing_page instanceof WP_Post && (int) $existing_page->post_author > 0) {
        return (int) $existing_page->post_author;
    }

    $env_author_id = getenv('VG_PUBLISH_AUTHOR_ID');

    if (is_string($env_author_id) && preg_match('/^\d+$/', trim($env_author_id))) {
        $author_id = (int) trim($env_author_id);

        if ($author_id > 0 && get_user_by('id', $author_id) instanceof WP_User) {
            return $author_id;
        }
    }

    $current_user_id = (int) get_current_user_id();

    if ($current_user_id > 0 && get_user_by('id', $current_user_id) instanceof WP_User) {
        return $current_user_id;
    }

    $users = get_users(
        [
            'role__in' => ['administrator', 'editor'],
            'number'   => 1,
            'orderby'  => 'ID',
            'order'    => 'ASC',
            'fields'   => ['ID'],
        ]
    );

    if (isset($users[0]->ID) && (int) $users[0]->ID > 0) {
        return (int) $users[0]->ID;
    }

    vg_ops_fail('Could not resolve a valid WordPress author for the Best Places to Visit in Vietnam guide.');
}

function vg_ops_internal_path_from_href(string $href): ?string
{
    $href = trim(html_entity_decode($href, ENT_QUOTES, 'UTF-8'));

    if ($href === '' || str_starts_with($href, '#')) {
        return null;
    }

    if (str_starts_with($href, '/')) {
        $path = parse_url($href, PHP_URL_PATH);
    } elseif (preg_match('/^https?:\/\//i', $href)) {
        $site_host = parse_url(home_url('/'), PHP_URL_HOST);
        $href_host = parse_url($href, PHP_URL_HOST);

        if (! $site_host || ! $href_host || strcasecmp($site_host, $href_host) !== 0) {
            return null;
        }

        $path = parse_url($href, PHP_URL_PATH);
    } else {
        return null;
    }

    if (! is_string($path)) {
        return null;
    }

    $path = trim($path, '/');

    return $path === '' ? 'home' : $path;
}

function vg_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_ops_internal_path_from_href($href);

        if ($path === null) {
            continue;
        }

        $paths[$path] = true;
    }

    $failures = [];

    foreach (array_keys($paths) as $path) {
        if ($path === 'home') {
            $front_page_id = (int) get_option('page_on_front');
            $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
        } else {
            $page = get_page_by_path($path, OBJECT, 'page');
        }

        if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
            $failures[] = "{$label} links to unpublished page path: /{$path}/";
        }
    }

    if ($failures !== []) {
        vg_ops_fail(implode(PHP_EOL, $failures));
    }

    vg_ops_log("Validated {$label} internal page links are published.");
}

function vg_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post) {
        vg_ops_log('Skipped Destinations hub refresh: destinations page was not found.');
        return;
    }

    if ($hub->post_status !== 'publish') {
        vg_ops_log('Skipped Destinations hub refresh: destinations page is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('destinations/best-places-to-visit-vietnam')) {
        vg_ops_log('Skipped Destinations hub refresh: Best Places guide is not published.');
        return;
    }

    $marker = '<!-- vg-best-places-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose the right places, not the longest list</h3><p>Start with the <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a> guide to decide which destinations fit your route length, season, and travel style before adding more stops.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Destinations hub best places note', $block);

    $marked_block = $marker . "\n" . $block;

    if (str_contains($hub->post_content, $marked_block)) {
        vg_ops_log('Skipped Destinations hub refresh: current best places note already present.');
        return;
    }

    if (str_contains($hub->post_content, $marker)) {
        $updated_content = preg_replace(
            '/' . preg_quote($marker, '/') . '\R<!-- wp:group\b.*?<!-- \/wp:group -->/s',
            $marked_block,
            $hub->post_content,
            1,
            $replacement_count
        );

        if (! is_string($updated_content) || $replacement_count !== 1) {
            vg_ops_fail('Could not confidently refresh the Destinations hub best places note.');
        }
    } else {
        $updated_content = rtrim($hub->post_content) . "\n\n" . $marked_block;
    }

    $result = wp_update_post(
        [
            'ID'           => $hub->ID,
            'post_content' => $updated_content,
        ],
        true
    );

    if (is_wp_error($result)) {
        vg_ops_fail('Could not refresh Destinations hub note: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Destinations hub note: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Destinations hub best places note: {$hub->ID}");
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/best-places-to-visit-vietnam', OBJECT, 'page');
$allow_republish = vg_ops_force_republish_enabled();

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('Best Places to Visit in Vietnam guide is not a draft. Set VG_FORCE_DESTINATION_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    $permalink = get_permalink($page);
    vg_ops_log("Preflight Best Places guide: {$page->ID} {$page->post_status} {$permalink}");
} else {
    vg_ops_log('Preflight Best Places guide: no existing page found; creating a child page under /destinations/.');
}

$guide_hero_image = esc_url(set_url_scheme(get_stylesheet_directory_uri(), 'https') . '/assets/images/ha-long-bay-vietnam-hero.jpg');

$content = <<<HTML
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$guide_hero_image}","dimRatio":55,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Limestone islands in Ha Long Bay, Vietnam, used as a best places in Vietnam guide hero image" src="{$guide_hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 15, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Best Places to Visit in Vietnam</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">The best places to visit in Vietnam depend on route length, season, flight path, comfort level, and what you are willing to skip. This guide turns the usual destination list into a decision framework for international visitors planning a first or premium trip.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: a stronger Vietnam trip usually comes from choosing fewer places with better timing, not collecting every famous name.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most first-time visitors, the strongest Vietnam destination set is Hanoi, Ninh Binh, Ha Long or Lan Ha Bay, Hue or Hoi An, and Da Nang as the central access point.</strong> Add Ho Chi Minh City, Mekong Delta, Phu Quoc, Phong Nha, Sapa, or Ha Giang only when the route length, season, and transfer reality make the extra move worth it.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-destination-guide-first-trip-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-destination-guide-first-trip-shortlist">
<li><strong>Best first-trip core:</strong> Hanoi, Ninh Binh, Ha Long/Lan Ha Bay, Hue or Hoi An, and Da Nang access.</li>
<li><strong>Best southern add-on:</strong> Ho Chi Minh City with Mekong or Phu Quoc when flights, season, and days support it.</li>
<li><strong>Best nature extension:</strong> Phong Nha, Sapa, Ha Giang, or a deeper northern countryside route when you can slow down.</li>
<li><strong>Best premium move:</strong> fewer bases, better hotels, private transfers where they save stress, and one high-quality guide or cruise instead of more cities.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- wp:heading -->
<h2 class="wp-block-heading">Fast destination verdict matrix</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This matrix is deliberately selective. It includes places that commonly change the shape of an international visitor's trip. It does not try to name every worthwhile stop in Vietnam.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-destination-guide-verdict-matrix">
<thead>
<tr><th>Place or cluster</th><th>Why it matters</th><th>Best fit</th><th>Main trade-off</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Place or cluster">Hanoi</td><td data-label="Why it matters">Capital culture, old-quarter texture, food, museums, and the best launch point for northern landscapes.</td><td data-label="Best fit">Nearly every first-time route that starts in the north.</td><td data-label="Main trade-off">Traffic, air quality, and old-quarter intensity can tire travelers who overbook the first day.</td><td data-label="VietnamGuide verdict">Anchor the north here, then leave space for countryside or bay days.</td></tr>
<tr><td data-label="Place or cluster">Ninh Binh</td><td data-label="Why it matters">Limestone scenery, river landscapes, temples, cycling, and countryside close enough to fit many short routes.</td><td data-label="Best fit">Travelers who want nature without a remote mountain commitment.</td><td data-label="Main trade-off">Day trips can feel thin; an overnight often improves the experience.</td><td data-label="VietnamGuide verdict">One of the highest-value first-trip additions from Hanoi.</td></tr>
<tr><td data-label="Place or cluster">Ha Long or Lan Ha Bay</td><td data-label="Why it matters">Iconic karst seascape and a clear visual signature for Vietnam.</td><td data-label="Best fit">First-time visitors who want a cruise-style landscape highlight.</td><td data-label="Main trade-off">Weather, cruise quality, transfer time, and crowding matter more than the name alone.</td><td data-label="VietnamGuide verdict">Book for route fit and operator quality, not just the famous bay label.</td></tr>
<tr><td data-label="Place or cluster">Hue</td><td data-label="Why it matters">Imperial history, food, river setting, and cultural depth that gives central Vietnam context.</td><td data-label="Best fit">Food, heritage, and travelers who want more than beach-and-old-town Vietnam.</td><td data-label="Main trade-off">Needs slower pacing or a good guide to avoid becoming a shallow monument checklist.</td><td data-label="VietnamGuide verdict">The best central add-on when you want meaning, not just atmosphere.</td></tr>
<tr><td data-label="Place or cluster">Hoi An and Da Nang</td><td data-label="Why it matters">Old town, beaches, hotels, food, airport access, and a manageable central base.</td><td data-label="Best fit">Couples, families, premium travelers, food travelers, and lower-stress routes.</td><td data-label="Main trade-off">Hoi An can feel crowded; beach value depends on season.</td><td data-label="VietnamGuide verdict">Use this cluster to simplify the trip, especially when comfort matters.</td></tr>
<tr><td data-label="Place or cluster">Phong Nha</td><td data-label="Why it matters">Caves, national-park scenery, and a different kind of central Vietnam nature.</td><td data-label="Best fit">Active travelers with enough days and a specific cave/nature priority.</td><td data-label="Main trade-off">Transfer time is real, and it competes with easier central stops.</td><td data-label="VietnamGuide verdict">Excellent when it is the point, weak as a rushed trophy stop.</td></tr>
<tr><td data-label="Place or cluster">Ho Chi Minh City</td><td data-label="Why it matters">Southern city energy, food, museums, nightlife, business hotels, and strong flight access.</td><td data-label="Best fit">South-first routes, open-jaw flights, city lovers, and Mekong or Phu Quoc extensions.</td><td data-label="Main trade-off">It can feel less classically scenic after the north or central heritage towns.</td><td data-label="VietnamGuide verdict">Choose it intentionally; do not add it as a token final night unless flights require it.</td></tr>
<tr><td data-label="Place or cluster">Mekong Delta</td><td data-label="Why it matters">River life, markets, orchards, food, and a softer southern rhythm.</td><td data-label="Best fit">Travelers who want southern context beyond Ho Chi Minh City.</td><td data-label="Main trade-off">Rushed tours can flatten the experience into transit and photo stops.</td><td data-label="VietnamGuide verdict">Add it when the south has enough time to breathe.</td></tr>
<tr><td data-label="Place or cluster">Phu Quoc</td><td data-label="Why it matters">Island time, resorts, beaches, and a clean decompression ending when the season fits.</td><td data-label="Best fit">Beach-first or rest-first travelers using southern flight logic.</td><td data-label="Main trade-off">It is not a universal add-on; weather, flight path, and resort choice decide value.</td><td data-label="VietnamGuide verdict">Strong when it matches the season and flight plan, easy to skip otherwise.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Match places to the route you can actually travel</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The right destination is not always the most famous destination. It is the place that improves the trip after weather, transfers, hotel rhythm, and arrival/departure airports are accounted for.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-destination-guide-route-fit">
<thead>
<tr><th>Trip shape</th><th>Prioritize</th><th>Add carefully</th><th>Usually skip first</th><th>Why</th></tr>
</thead>
<tbody>
<tr><td data-label="Trip shape">7-8 days</td><td data-label="Prioritize">One strong region: north scenery or central heritage/coast.</td><td data-label="Add carefully">One nearby countryside, bay, or beach module.</td><td data-label="Usually skip first">Cross-country sampler routes.</td><td data-label="Why">Every flight or long transfer removes a meaningful day from a short trip.</td></tr>
<tr><td data-label="Trip shape">9-12 days</td><td data-label="Prioritize">North plus central for the cleanest first-trip arc.</td><td data-label="Add carefully">Ho Chi Minh City only with strong open-jaw flight logic.</td><td data-label="Usually skip first">Remote mountains plus south plus central in one push.</td><td data-label="Why">This length rewards contrast, but not unlimited coverage.</td></tr>
<tr><td data-label="Trip shape">13-16 days</td><td data-label="Prioritize">Two regions deeply or three regions with open-jaw flights and buffer.</td><td data-label="Add carefully">Mekong, Phu Quoc, Phong Nha, Sapa, or Ha Giang.</td><td data-label="Usually skip first">Any add-on that duplicates the same role.</td><td data-label="Why">Extra days should improve texture, not just increase check-ins.</td></tr>
<tr><td data-label="Trip shape">Family or premium</td><td data-label="Prioritize">Da Nang/Hoi An, Hue, Hanoi, Ninh Binh, and carefully chosen transfers.</td><td data-label="Add carefully">A cruise, island, or remote nature module if recovery time exists.</td><td data-label="Usually skip first">Nightly hotel changes and long same-day road moves.</td><td data-label="Why">Comfort and route rhythm often matter more than count of destinations.</td></tr>
<tr><td data-label="Trip shape">Food and culture</td><td data-label="Prioritize">Hanoi, Hue, Hoi An, Ho Chi Minh City, and one slower local context stop.</td><td data-label="Add carefully">Mekong or central countryside.</td><td data-label="Usually skip first">Beach-only extensions in weak weather windows.</td><td data-label="Why">Food and culture improve when the schedule leaves time for meals, walks, and guides.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Heritage, nature, and what they change</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>UNESCO and official tourism pages are useful for orientation, but a destination guide should not freeze brittle counts or imply every heritage place belongs in one trip. Use heritage and nature as route-shaping signals.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-destination-guide-heritage-nature">
<thead>
<tr><th>Anchor type</th><th>Examples to consider</th><th>What it changes</th><th>Planning caution</th></tr>
</thead>
<tbody>
<tr><td data-label="Anchor type">Northern landscapes</td><td data-label="Examples to consider">Ninh Binh, Ha Long or Lan Ha Bay, Sapa, Ha Giang, and northern countryside.</td><td data-label="What it changes">The north can justify a focused route without crossing the whole country.</td><td data-label="Planning caution">Remote mountain routes need weather, road, and time discipline.</td></tr>
<tr><td data-label="Anchor type">Central heritage</td><td data-label="Examples to consider">Hue, Hoi An, My Son, Da Nang, and nearby coast/countryside.</td><td data-label="What it changes">Central Vietnam can carry a calmer food, heritage, and hotel-led trip.</td><td data-label="Planning caution">Beach comfort and heritage sightseeing can have different weather needs.</td></tr>
<tr><td data-label="Anchor type">Caves and national-park nature</td><td data-label="Examples to consider">Phong Nha-Ke Bang and cave-focused extensions.</td><td data-label="What it changes">Adds a major nature reason to stay longer in central Vietnam.</td><td data-label="Planning caution">Do not force it into a short route unless it is a primary reason for the trip.</td></tr>
<tr><td data-label="Anchor type">Southern city, river, and island texture</td><td data-label="Examples to consider">Ho Chi Minh City, Mekong Delta, Phu Quoc, and southern food culture.</td><td data-label="What it changes">The south works best when chosen for its own energy, not as a final checkbox.</td><td data-label="Planning caution">Phu Quoc and Mekong add-ons need season, flight, and transfer checks.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Skipping is the quiet luxury in Vietnam planning. A better route often comes from removing the destination that weakens the trip.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-destination-guide-skip-logic">
<thead>
<tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you want...">Classic first Vietnam</td><td data-label="Protect this">Hanoi, Ninh Binh, bay scenery, Hue or Hoi An, and flight rhythm.</td><td data-label="Skip first">Ho Chi Minh City, Mekong, Phu Quoc, Sapa, and Ha Giang on short routes.</td><td data-label="Only add back when...">You have 13+ days, open-jaw flights, or a clear personal priority.</td></tr>
<tr><td data-label="If you want...">Beach and hotels</td><td data-label="Protect this">The right season, resort location, and fewer transfers.</td><td data-label="Skip first">Destinations chosen only because they are famous.</td><td data-label="Only add back when...">Weather and flight path support the beach window.</td></tr>
<tr><td data-label="If you want...">Landscape depth</td><td data-label="Protect this">Northern countryside, bay or mountain buffer, and realistic road time.</td><td data-label="Skip first">Central and southern add-ons that dilute the north.</td><td data-label="Only add back when...">The route has enough recovery days after remote moves.</td></tr>
<tr><td data-label="If you want...">Lower-stress family travel</td><td data-label="Protect this">Stable bases, shorter transfers, and predictable hotel comfort.</td><td data-label="Skip first">A destination that requires a long move for a one-night stay.</td><td data-label="Only add back when...">It removes stress or creates a true highlight, not just coverage.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Official checks before choosing places</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use official sources to confirm the broad destination frame, then verify live logistics close to travel. This page should not be treated as a live schedule, weather alert, cruise ranking, or hotel recommendation.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-destination-guide-official-checks"} -->
<ul class="wp-block-list vg-check-list vg-destination-guide-official-checks">
<li>Region frame: compare Vietnam.travel pages for <a href="https://vietnam.travel/places-to-go/northern-vietnam" target="_blank" rel="noopener">Northern Vietnam</a>, <a href="https://vietnam.travel/places-to-go/central-vietnam" target="_blank" rel="noopener">Central Vietnam</a>, and <a href="https://vietnam.travel/places-to-go/southern-vietnam" target="_blank" rel="noopener">Southern Vietnam</a>.</li>
<li>City and place context: use Vietnam.travel destination pages such as <a href="https://vietnam.travel/places-to-go/northern-vietnam/ha-noi" target="_blank" rel="noopener">Ha Noi</a>, <a href="https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh" target="_blank" rel="noopener">Ninh Binh</a>, <a href="https://vietnam.travel/places-to-go/central-vietnam/hoi-an" target="_blank" rel="noopener">Hoi An</a>, <a href="https://vietnam.travel/places-to-go/central-vietnam/hue" target="_blank" rel="noopener">Hue</a>, <a href="https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city" target="_blank" rel="noopener">Ho Chi Minh City</a>, and <a href="https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc" target="_blank" rel="noopener">Phu Quoc</a> for public-facing orientation.</li>
<li>Weather: check <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> before choosing beach, mountain, cruise, cave, or island-heavy routes.</li>
<li>Heritage: use the <a href="https://whc.unesco.org/en/statesparties/vn" target="_blank" rel="noopener">UNESCO Viet Nam state page</a> for current World Heritage context instead of copying a fixed count from older articles.</li>
<li>Planning order: start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before committing money.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">How to use this guide</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>First, decide the role each place plays: culture anchor, landscape anchor, beach/rest anchor, city/flight anchor, or food/heritage anchor. Second, remove any place that duplicates a role without improving the trip. Third, check season and transfers before paying for hotels, cruises, domestic flights, or private guides.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

vg_ops_assert_internal_page_links_are_published('Best Places to Visit in Vietnam guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Best Places to Visit in Vietnam',
    'post_name'      => 'best-places-to-visit-vietnam',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led guide to the best places to visit in Vietnam, with destination verdicts, route fit, skip logic, and official source checks.',
    'comment_status' => 'closed',
    'ping_status'    => 'closed',
];

if ($page instanceof WP_Post) {
    $post_args['ID'] = $page->ID;
    $result = wp_update_post($post_args, true);
} else {
    $result = wp_insert_post($post_args, true);
}

if (is_wp_error($result)) {
    vg_ops_fail('Could not publish Best Places to Visit in Vietnam guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Best Places to Visit in Vietnam guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Best Places to Visit in Vietnam: Where to Go First');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led guide to the best places to visit in Vietnam, with first-trip shortlist, route fit, seasonal trade-offs, skip logic, and official checks.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'best places to visit in Vietnam');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Choose which Vietnam destinations actually fit the route length, season, flight path, and traveler style before adding more stops.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 15, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Best Places to Visit in Vietnam guide with first-trip shortlist, destination verdict matrix, route-fit matrix, heritage/nature anchors, skip logic, and official source checks.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked July 15, 2026\nVietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked July 15, 2026\nVietnam.travel - Southern Vietnam destinations - https://vietnam.travel/places-to-go/southern-vietnam - checked July 15, 2026\nVietnam.travel - Ha Noi - https://vietnam.travel/places-to-go/northern-vietnam/ha-noi - checked July 15, 2026\nVietnam.travel - Ninh Binh - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh - checked July 15, 2026\nVietnam.travel - Hoi An - https://vietnam.travel/places-to-go/central-vietnam/hoi-an - checked July 15, 2026\nVietnam.travel - Hue - https://vietnam.travel/places-to-go/central-vietnam/hue - checked July 15, 2026\nVietnam.travel - Ho Chi Minh City - https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city - checked July 15, 2026\nVietnam.travel - Phu Quoc - https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc - checked July 15, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 15, 2026\nUNESCO World Heritage Centre - Viet Nam state page - https://whc.unesco.org/en/statesparties/vn - checked July 15, 2026\nUNESCO World Heritage Centre - Ha Long Bay-Cat Ba Archipelago - https://whc.unesco.org/en/list/672/ - checked July 15, 2026\nUNESCO World Heritage Centre - Complex of Hue Monuments - https://whc.unesco.org/en/list/678/ - checked July 15, 2026\nUNESCO World Heritage Centre - Hoi An Ancient Town - https://whc.unesco.org/en/list/948/ - checked July 15, 2026\nUNESCO World Heritage Centre - Phong Nha-Ke Bang National Park and Hin Nam No National Park - https://whc.unesco.org/en/list/951/ - checked July 15, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'A stronger Vietnam trip usually comes from choosing fewer places with better timing, not collecting every famous name.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Destination guide built as a decision framework instead of a generic listicle.\nFirst-trip shortlist that states what to choose and what to skip.\nDestination verdict matrix with original trade-off judgment for Hanoi, Ninh Binh, Ha Long/Lan Ha Bay, Hue, Hoi An/Da Nang, Phong Nha, Ho Chi Minh City, Mekong, and Phu Quoc.\nRoute-fit matrix by trip length, family/premium comfort, food/culture, and transfer pressure.\nHeritage and nature anchors using official tourism and UNESCO context without freezing brittle counts.\nSkip logic that helps readers remove weak add-ons before booking.\nRelated decision chain for region choice, route length, trip cost, and the UNESCO heritage cluster.\nOfficial source checks and internal links to the Travel Guide, Region Comparison, Best Time, 10 Days, Travel Cost, and UNESCO Heritage guides.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Choose the lead region before adding more places.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Convert the shortlist into a realistic first route.\nBest Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Decide how much capital time deserves before the north starts moving.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.\nBest Things to Do in Hoi An | /destinations/best-things-to-do-in-hoi-an/ | Decide how much slow central Vietnam time Hoi An deserves before adding My Son, beach days, or another transfer.\nBest Things to Do in Hue | /destinations/best-things-to-do-in-hue/ | Decide when Hue deserves a protected imperial-history day instead of a drive-through central Vietnam transfer.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price extra stops before keeping them.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Use the heritage shortlist before adding more stops.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0.');
update_post_meta($page_id, '_generate-disable-headline', 'true');

$required_meta = [
    'rank_math_title',
    'rank_math_description',
    'rank_math_focus_keyword',
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
    '_generate-disable-headline',
];

foreach ($required_meta as $meta_key) {
    $meta_value = get_post_meta($page_id, $meta_key, true);

    if ((is_string($meta_value) && trim($meta_value) === '') || $meta_value === [] || $meta_value === null) {
        vg_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_ops_fail('Best Places to Visit in Vietnam guide was updated but is not published.');
}

vg_ops_refresh_destinations_hub();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Best Places to Visit in Vietnam guide: {$page_id} {$updated_permalink}");

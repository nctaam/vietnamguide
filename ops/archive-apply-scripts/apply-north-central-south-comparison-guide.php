<?php
/**
 * Publish the North vs Central vs South Vietnam comparison guide.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-north-central-south-comparison-guide.php --allow-root
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
    $value = getenv('VG_FORCE_REGION_COMPARISON_REPUBLISH');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the North vs Central vs South Vietnam guide.');
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

function vg_ops_refresh_compare_hub(): void
{
    $hub = get_page_by_path('compare', OBJECT, 'page');

    if (! $hub instanceof WP_Post) {
        vg_ops_log('Skipped Compare hub refresh: compare page was not found.');
        return;
    }

    if ($hub->post_status !== 'publish') {
        vg_ops_log('Skipped Compare hub refresh: compare page is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('compare/north-central-south-vietnam')) {
        vg_ops_log('Skipped Compare hub refresh: North/Central/South comparison is not published.');
        return;
    }

    $marker = '<!-- vg-region-comparison-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Start with the regional choice</h3><p>If you are unsure where to focus, compare <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a> before adding more cities to the route.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Compare hub region note', $block);

    $marked_block = $marker . "\n" . $block;

    if (str_contains($hub->post_content, $marked_block)) {
        vg_ops_log('Skipped Compare hub refresh: current region comparison note already present.');
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
            vg_ops_fail('Could not confidently refresh the Compare hub region comparison note.');
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
        vg_ops_fail('Could not refresh Compare hub note: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Compare hub note: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Compare hub region comparison note: {$hub->ID}");
}

$parent = get_page_by_path('compare', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: compare');
}

$page = get_page_by_path('compare/north-central-south-vietnam', OBJECT, 'page');
$allow_republish = vg_ops_force_republish_enabled();

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('North vs Central vs South Vietnam guide is not a draft. Set VG_FORCE_REGION_COMPARISON_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    $permalink = get_permalink($page);
    vg_ops_log("Preflight North/Central/South comparison: {$page->ID} {$page->post_status} {$permalink}");
} else {
    vg_ops_log('Preflight North/Central/South comparison: no existing page found; creating a child page under /compare/.');
}

$guide_hero_image = esc_url(set_url_scheme(get_stylesheet_directory_uri(), 'https') . '/assets/images/ha-long-bay-vietnam-hero.jpg');

$content = <<<HTML
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$guide_hero_image}","dimRatio":55,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Limestone islands in Ha Long Bay, Vietnam, used as a Vietnam regional comparison hero image" src="{$guide_hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed comparison guide - Updated July 15, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">North vs Central vs South Vietnam</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Vietnam is easier to plan when you compare regions before comparing cities. This guide helps first-time visitors decide whether the north, central coast, south, or a focused two-region route gives the strongest trip.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: the best region is the one your season, route length, flight path, and traveler style can actually support.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>
<!-- /wp:html -->
</div>
</div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most first-time trips, North plus Central Vietnam is the strongest default.</strong> Choose the north if landscapes and Hanoi matter most, central Vietnam if heritage, food, beaches, and calmer pacing matter most, and the south if you want Ho Chi Minh City, Mekong life, islands, winter sun, or an easier southern arrival.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><strong>Best for:</strong> travelers deciding where to focus before choosing exact destinations, hotels, tours, or domestic flights.</li>
<li><strong>Avoid if:</strong> you already have fixed flights, fixed dates, and a single city question that needs a local guide instead.</li>
<li><strong>Verify before booking:</strong> regional weather, visa/entry timing, route length, domestic transport, and whether each added region earns its transfer cost.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- wp:heading -->
<h2 class="wp-block-heading">Fast verdict matrix</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This comparison is intentionally practical. It does not rank regions as if one is objectively better; it shows which region should lead the trip when time is limited.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-region-compare-verdict-matrix">
<thead>
<tr><th>Region choice</th><th>Best first-trip reason</th><th>Strongest fit</th><th>Main trade-off</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Region choice">Northern Vietnam</td><td data-label="Best first-trip reason">Hanoi, Ninh Binh, Ha Long or Lan Ha Bay, mountain scenery, older capital culture, and the strongest landscape drama.</td><td data-label="Strongest fit">Food, photography, countryside, culture, and cooler-weather ambitions.</td><td data-label="Main trade-off">Remote mountain add-ons and bay transfers can make short trips feel tight.</td><td data-label="VietnamGuide verdict">Best region to lead with when landscapes and Hanoi matter more than beaches.</td></tr>
<tr><td data-label="Region choice">Central Vietnam</td><td data-label="Best first-trip reason">Hue, Hoi An, Da Nang, central-coast access, heritage towns, food, and a calmer hotel rhythm.</td><td data-label="Strongest fit">Couples, families, premium travelers, food/heritage travelers, and slower routes.</td><td data-label="Main trade-off">Beach-first plans need careful weather checks, especially outside stronger beach windows.</td><td data-label="VietnamGuide verdict">Best region to simplify a first trip without making it feel thin.</td></tr>
<tr><td data-label="Region choice">Southern Vietnam</td><td data-label="Best first-trip reason">Ho Chi Minh City energy, Mekong Delta texture, southern food, Phu Quoc or island time, and winter-sun logic.</td><td data-label="Strongest fit">City lovers, island extensions, warm-weather trips, and travelers flying through Ho Chi Minh City.</td><td data-label="Main trade-off">It can feel less classically scenic if you expected northern limestone landscapes or central heritage towns.</td><td data-label="VietnamGuide verdict">Best when the south is the point, not just a rushed final add-on.</td></tr>
<tr><td data-label="Region choice">North plus Central</td><td data-label="Best first-trip reason">Balances Hanoi, countryside, bay scenery, Hue or Hoi An, and Da Nang departure if flights work.</td><td data-label="Strongest fit">Most first-time visitors with around 9-12 days.</td><td data-label="Main trade-off">Skips the south to protect pacing.</td><td data-label="VietnamGuide verdict">The best default for a complete-feeling first Vietnam trip.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Heritage and landscape anchors</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Heritage is one reason region choice matters. Use UNESCO and official tourism sources for the current list, then decide whether those places fit the route you can travel well.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-region-compare-heritage-anchors">
<thead>
<tr><th>Region</th><th>Strong anchor examples</th><th>What they mean for planning</th><th>Risk to avoid</th></tr>
</thead>
<tbody>
<tr><td data-label="Region">North</td><td data-label="Strong anchor examples">Ha Long Bay-Cat Ba Archipelago, Trang An, Hanoi, Ninh Binh, and northern cultural landscapes.</td><td data-label="What they mean for planning">The north can carry a trip through scenery, capital culture, and countryside even without crossing the country.</td><td data-label="Risk to avoid">Adding every remote mountain or bay option into one short route.</td></tr>
<tr><td data-label="Region">Central</td><td data-label="Strong anchor examples">Hue, Hoi An, My Son, Phong Nha-Ke Bang, Da Nang, and central-coast heritage corridors.</td><td data-label="What they mean for planning">Central Vietnam can feel rich with fewer long transfers because heritage, food, coast, and hotels sit closer together.</td><td data-label="Risk to avoid">Assuming heritage towns and beaches share the same weather comfort every month.</td></tr>
<tr><td data-label="Region">South</td><td data-label="Strong anchor examples">Ho Chi Minh City, Mekong river life, southern food culture, islands, and southern intangible cultural traditions.</td><td data-label="What they mean for planning">The south should be chosen for urban, river, food, and island texture rather than forced into a northern landscape comparison.</td><td data-label="Risk to avoid">Treating the south as a token final stop after the strongest days have already been spent elsewhere.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">First-trip fit by traveler type</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this table when two regions both sound appealing. The right answer often depends less on famous names and more on how you want the trip to feel each day.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-region-compare-first-trip-fit">
<thead>
<tr><th>Your priority</th><th>Lead with</th><th>Add if time allows</th><th>Usually skip first</th><th>Why</th></tr>
</thead>
<tbody>
<tr><td data-label="Your priority">A balanced first Vietnam trip</td><td data-label="Lead with">North plus Central</td><td data-label="Add if time allows">Ho Chi Minh City only with open-jaw flights or extra days.</td><td data-label="Usually skip first">Mekong or Phu Quoc on short trips.</td><td data-label="Why">This protects the strongest mix of culture, landscapes, heritage, food, and route logic.</td></tr>
<tr><td data-label="Your priority">Landscapes and photography</td><td data-label="Lead with">Northern Vietnam</td><td data-label="Add if time allows">Central Vietnam for Hue/Hoi An context.</td><td data-label="Usually skip first">Southern urban/island add-ons.</td><td data-label="Why">The north gives the clearest scenery payoff per transfer when planned with enough buffer.</td></tr>
<tr><td data-label="Your priority">Food, heritage, and low stress</td><td data-label="Lead with">Central Vietnam</td><td data-label="Add if time allows">Hanoi or Ninh Binh.</td><td data-label="Usually skip first">Far northern mountains or a rushed south.</td><td data-label="Why">Hue, Hoi An, and Da Nang can create a rich trip with fewer hotel moves.</td></tr>
<tr><td data-label="Your priority">City energy and southern life</td><td data-label="Lead with">Southern Vietnam</td><td data-label="Add if time allows">Phu Quoc, Mekong, or a north/central flight pair.</td><td data-label="Usually skip first">Remote northern add-ons.</td><td data-label="Why">Ho Chi Minh City and the Mekong work best when they are given enough time to breathe.</td></tr>
<tr><td data-label="Your priority">Family or premium comfort</td><td data-label="Lead with">Central Vietnam</td><td data-label="Add if time allows">One northern highlight or one southern beach/island extension.</td><td data-label="Usually skip first">A new region every two nights.</td><td data-label="Why">Shorter transfers, better hotel rhythm, and fewer forced early starts often matter more than coverage.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Season and route reality</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam.travel presents official climate guidance by region, and that is the right habit for planning. Do not treat Vietnam as one weather zone. North, Central, and South Vietnam can reward different months and punish different assumptions.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-region-compare-season-route">
<thead>
<tr><th>Question</th><th>North</th><th>Central</th><th>South</th><th>Planning move</th></tr>
</thead>
<tbody>
<tr><td data-label="Question">Is this a beach-first trip?</td><td data-label="North">Usually not the cleanest beach anchor for a first trip.</td><td data-label="Central">Often the strongest mainland beach-and-heritage combination, but weather matters.</td><td data-label="South">Phu Quoc and southern islands can be strong when the season fits.</td><td data-label="Planning move">Use the <a href="/plan/best-time-to-visit-vietnam/">Best Time guide</a> before paying for beach-heavy plans.</td></tr>
<tr><td data-label="Question">Is scenery the main reason?</td><td data-label="North">Strongest default for limestone landscapes, mountains, Hanoi, Ninh Binh, and bay scenery.</td><td data-label="Central">Good variety through heritage, coast, Hai Van Pass, caves, and countryside.</td><td data-label="South">More city, delta, island, and river-life texture than dramatic mountain scenery.</td><td data-label="Planning move">Lead north unless the trip is intentionally city/island focused.</td></tr>
<tr><td data-label="Question">Is the route only 7-10 days?</td><td data-label="North">Works well alone or with a compact central extension.</td><td data-label="Central">Works especially well as a slower focused route.</td><td data-label="South">Works when flights and priorities are southern, but do not force the whole country.</td><td data-label="Planning move">Use the <a href="/itineraries/10-days-in-vietnam/">10 Days guide</a> to protect pacing.</td></tr>
<tr><td data-label="Question">Are flights fixed?</td><td data-label="North">Hanoi arrival helps north-first plans.</td><td data-label="Central">Da Nang can simplify central routes and departures.</td><td data-label="South">Ho Chi Minh City arrival/departure helps southern routes.</td><td data-label="Planning move">Let open-jaw flights save real time only after checking transfer reality.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The anti-spam value in a comparison guide is not naming every possible stop. It is helping you remove the wrong stop before the itinerary becomes expensive, rushed, or weather-fragile.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-region-compare-skip-logic">
<thead>
<tr><th>If you choose...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you choose...">Northern Vietnam</td><td data-label="Protect this">Hanoi, one countryside/limestone module, one bay or mountain decision, and enough transfer buffer.</td><td data-label="Skip first">A rushed southern add-on and too many remote mountain nights.</td><td data-label="Only add back when...">You have extra days or open-jaw flights that reduce backtracking.</td></tr>
<tr><td data-label="If you choose...">Central Vietnam</td><td data-label="Protect this">Hue/Hoi An/Da Nang rhythm, local food, heritage context, and at least one flexible weather day.</td><td data-label="Skip first">A token Hanoi or Ho Chi Minh City night that adds airport pressure.</td><td data-label="Only add back when...">Flights are easy and the extra city clearly improves the trip.</td></tr>
<tr><td data-label="If you choose...">Southern Vietnam</td><td data-label="Protect this">Ho Chi Minh City, Mekong or island logic, and enough time for heat, traffic, and transfer recovery.</td><td data-label="Skip first">A far northern scenery detour that needs several days to justify itself.</td><td data-label="Only add back when...">You can add a true second region without turning the trip into transit.</td></tr>
<tr><td data-label="If you choose...">North plus Central</td><td data-label="Protect this">The clean first-trip arc from Hanoi to countryside/bay to Hue/Hoi An/Da Nang.</td><td data-label="Skip first">Ho Chi Minh City, Mekong, Sapa, Ha Giang, and Phu Quoc on short trips.</td><td data-label="Only add back when...">You have 14+ days or a very specific reason.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Official source checks</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use official sources to verify the underlying region logic, then use live operators for details that change. This page should not be treated as a live schedule, fare table, or weather alert.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-region-compare-official-checks"} -->
<ul class="wp-block-list vg-check-list vg-region-compare-official-checks">
<li>Region structure: compare Vietnam.travel pages for <a href="https://vietnam.travel/places-to-go/northern-vietnam" target="_blank" rel="noopener">Northern Vietnam</a>, <a href="https://vietnam.travel/places-to-go/central-vietnam" target="_blank" rel="noopener">Central Vietnam</a>, and <a href="https://vietnam.travel/places-to-go/southern-vietnam" target="_blank" rel="noopener">Southern Vietnam</a>.</li>
<li>Weather: check <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> by region before making a beach, mountain, cruise, or island decision.</li>
<li>Transport: use official or operator sources close to travel; Vietnam.travel transport guidance is useful for broad context, not fixed schedules.</li>
<li>Entry: use the <a href="/plan/vietnam-evisa/">Vietnam E-Visa guide</a> before buying non-refundable flights around a multi-region plan.</li>
<li>Cost: use the <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost guide</a> to price the route by transfers, hotels, guides, and buffer rather than one average day number.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">How to use this comparison</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> for the full planning order. Use this comparison when the route feels too wide. Then move to the <a href="/plan/best-time-to-visit-vietnam/">Best Time guide</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before committing money.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

vg_ops_assert_internal_page_links_are_published('North/Central/South comparison guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'North vs Central vs South Vietnam',
    'post_name'      => 'north-central-south-vietnam',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led comparison of North, Central, and South Vietnam for first-time international visitors choosing where to focus.',
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
    vg_ops_fail('Could not publish North vs Central vs South Vietnam guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish North vs Central vs South Vietnam guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'North vs Central vs South Vietnam: Where to Go First');
update_post_meta($page_id, 'rank_math_description', 'Compare North, Central and South Vietnam for a first trip: best region by season, route length, traveler style, pacing, and what to skip.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'North vs Central vs South Vietnam');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Choose which Vietnam region should lead a first trip before adding more cities, flights, or hotel bases.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 15, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the North vs Central vs South Vietnam comparison guide with regional verdict matrix, heritage anchors, first-trip fit table, season and route reality, skip logic, and official source checks.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked July 15, 2026\nVietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked July 15, 2026\nVietnam.travel - Southern Vietnam destinations - https://vietnam.travel/places-to-go/southern-vietnam - checked July 15, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 15, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 15, 2026\nVietnam.travel - traveller's guide to Vietnam's airports - https://vietnam.travel/things-to-do/travellers-guide-vietnams-airports - checked July 15, 2026\nVietnam.travel - Cities - https://vietnam.travel/things-to-do/cities - checked July 15, 2026\nUNESCO World Heritage Centre - Viet Nam state page - https://whc.unesco.org/en/statesparties/vn - checked July 15, 2026\nUNESCO World Heritage Centre - Ha Long Bay-Cat Ba Archipelago - https://whc.unesco.org/en/list/672/ - checked July 15, 2026\nUNESCO World Heritage Centre - Complex of Hue Monuments - https://whc.unesco.org/en/list/678/ - checked July 15, 2026\nUNESCO Intangible Cultural Heritage - Don ca tai tu music and song in southern Viet Nam - https://ich.unesco.org/en/RL/art-of-n-ca-tai-t-music-and-song-in-southern-viet-nam-00733 - checked July 15, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'The best region is the one your season, route length, flight path, and traveler style can actually support.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Region-first decision framework that compares North, Central, South, and North-plus-Central routes by practical fit instead of generic inspiration.\nHeritage and landscape anchor table that uses UNESCO and official tourism context without freezing a brittle heritage count into the guide.\nFirst-trip fit table by traveler priority.\nSeason and route reality table that separates regional weather logic from live forecasts.\nSkip logic that helps readers remove weak add-ons before booking.\nRelated decision chain for destinations, route length, and cost.\nOfficial source checks using Vietnam.travel regional, weather, transport, airport, and city guidance plus UNESCO heritage context.\nInternal links to the site's Travel Guide, Best Time, E-Visa, 10 Days, and Travel Cost guides.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Best Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Turn the region choice into a destination shortlist.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check whether the chosen region fits the season.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Build a tighter first route after choosing regions.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
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
    vg_ops_fail('North vs Central vs South Vietnam guide was updated but is not published.');
}

vg_ops_refresh_compare_hub();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published North vs Central vs South Vietnam guide: {$page_id} {$updated_permalink}");

<?php
/**
 * Publish the Old Quarter vs French Quarter vs West Lake comparison guide.
 *
 * Run from the WordPress root with:
 * VG_FORCE_HANOI_NEIGHBORHOOD_COMPARISON_REPUBLISH=1 wp eval-file ops/apply-old-quarter-french-quarter-west-lake.php --allow-root
 *
 * Repair only side effects with:
 * VG_REPAIR_HANOI_NEIGHBORHOOD_COMPARISON_LINKS=1 wp eval-file ops/apply-old-quarter-french-quarter-west-lake.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_hanoi_compare_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_hanoi_compare_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_hanoi_compare_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_HANOI_NEIGHBORHOOD_COMPARISON_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_hanoi_compare_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_HANOI_NEIGHBORHOOD_COMPARISON_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_hanoi_compare_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_hanoi_compare_ops_fail('Could not resolve a valid WordPress author for the Hanoi neighborhood comparison guide.');
}

function vg_hanoi_compare_ops_internal_path_from_href(string $href): ?string
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

function vg_hanoi_compare_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_hanoi_compare_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_hanoi_compare_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_compare_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_hanoi_compare_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];
    foreach ($matches[2] as $href) {
        $path = vg_hanoi_compare_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_hanoi_compare_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_hanoi_compare_ops_log("Validated {$label} internal page links are published.");
}

function vg_hanoi_compare_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_hanoi_compare_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_hanoi_compare_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_hanoi_compare_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_hanoi_compare_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_hanoi_compare_ops_log("Validated {$label} related-route links are published.");
}

function vg_hanoi_compare_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_hanoi_compare_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
{
    $marked_block = $marker . PHP_EOL . trim($block);
    $replacement_count = 0;

    if (str_contains($page->post_content, $marker)) {
        $updated_content = preg_replace(
            '/' . preg_quote($marker, '/') . '\R<!-- wp:group\b.*?<!-- \/wp:group -->/s',
            $marked_block,
            $page->post_content,
            1,
            $replacement_count
        );

        if (! is_string($updated_content) || $replacement_count !== 1) {
            vg_hanoi_compare_ops_fail("Could not confidently refresh {$label}.");
        }
    } else {
        $updated_content = rtrim($page->post_content) . "\n\n" . $marked_block;
    }

    $result = wp_update_post(
        [
            'ID'           => $page->ID,
            'post_content' => $updated_content,
        ],
        true
    );

    if (is_wp_error($result)) {
        vg_hanoi_compare_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_hanoi_compare_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_hanoi_compare_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_hanoi_compare_ops_refresh_compare_hub(): void
{
    $hub = get_page_by_path('compare', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_hanoi_compare_ops_log('Skipped Compare hub refresh: compare page was not found or is not published.');
        return;
    }

    if (! vg_hanoi_compare_ops_published_page_exists('compare/old-quarter-vs-french-quarter-vs-west-lake')) {
        vg_hanoi_compare_ops_log('Skipped Compare hub refresh: Old Quarter vs French Quarter vs West Lake is not published.');
        return;
    }

    $marker = '<!-- vg-hanoi-neighborhood-compare-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose the Hanoi sleep zone before booking the hotel</h3><p>The <a href="/compare/old-quarter-vs-french-quarter-vs-west-lake/">Old Quarter vs French Quarter vs West Lake</a> guide compares sleeping energy, calm premium central access, food-walkability, pickup clarity, and longer-stay comfort for travelers who already know Hanoi should anchor the route.</p></div>
<!-- /wp:group -->
HTML;

    vg_hanoi_compare_ops_assert_internal_page_links_are_published('Compare hub Hanoi neighborhood note', $block);
    vg_hanoi_compare_ops_upsert_marked_group($hub, 'Compare hub Hanoi neighborhood note', $marker, $block);
}

function vg_hanoi_compare_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_compare_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_hanoi_compare_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
    $route_parts = array_map('trim', explode('|', $route_line));
    $route_href = $route_parts[1];

    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);
    $lines = preg_split('/\R+/', trim($current)) ?: [];
    $next_lines = [];
    $found_route = false;
    $changed_route = false;

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        $parts = array_map('trim', explode('|', $line));
        $line_href = count($parts) >= 2 ? $parts[1] : '';
        $is_target_line = $line_href === $route_href || str_contains($line, $route_href);

        if (! $is_target_line) {
            $next_lines[] = $line;
            continue;
        }

        if ($found_route) {
            $changed_route = true;
            continue;
        }

        $found_route = true;

        if ($line !== $route_line) {
            $next_lines[] = $route_line;
            $changed_route = true;
        } else {
            $next_lines[] = $line;
        }
    }

    if (! $found_route) {
        $next_lines[] = $route_line;
        $changed_route = true;
    }

    if (! $changed_route) {
        vg_hanoi_compare_ops_log("Skipped related-route refresh for {$label}: Hanoi neighborhood comparison line is already current.");
        return;
    }

    update_post_meta($page->ID, 'vg_eeat_related_routes', implode("\n", $next_lines));

    vg_hanoi_compare_ops_log("Upserted Hanoi neighborhood comparison related route to {$label}: {$page->ID}");
}

function vg_hanoi_compare_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Old Quarter vs French Quarter vs West Lake | /compare/old-quarter-vs-french-quarter-vs-west-lake/ | Choose the Hanoi sleep zone by walking energy, premium calm, food access, pickup clarity, and longer-stay comfort.';

    foreach (
        [
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ] as $path => $label
    ) {
        vg_hanoi_compare_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

function vg_hanoi_compare_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_hanoi_compare_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

$parent = get_page_by_path('compare', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_hanoi_compare_ops_fail('Could not find published /compare/ parent page.');
}

$page = get_page_by_path('compare/old-quarter-vs-french-quarter-vs-west-lake', OBJECT, 'page');

if (vg_hanoi_compare_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_compare_ops_fail('Repair mode requires Old Quarter vs French Quarter vs West Lake to already be published.');
    }

    vg_hanoi_compare_ops_refresh_compare_hub();
    vg_hanoi_compare_ops_refresh_inbound_related_routes();
    vg_hanoi_compare_ops_log("Repaired Old Quarter vs French Quarter vs West Lake side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_hanoi_compare_ops_force_republish_enabled()) {
    vg_hanoi_compare_ops_fail('Old Quarter vs French Quarter vs West Lake is not a draft. Set VG_FORCE_HANOI_NEIGHBORHOOD_COMPARISON_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_hanoi_compare_ops_log("Preflight Old Quarter vs French Quarter vs West Lake: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_hanoi_compare_ops_log('Preflight Old Quarter vs French Quarter vs West Lake: no existing page found; creating a child page under /compare/.');
}

$review_date = 'July 24, 2026';
$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/5/52/Hanoi_Opera_House%2C_24_December_2016.jpg');
$old_quarter_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/c/cc/Old_Quarter_market%2C_Hanoi%2C_20240123_1811_3411.jpg');
$french_quarter_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/2/26/French_Quarter%2C_Hanoi_%281346056681%29.jpg');
$west_lake_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/4/4a/Around_West_Lake_%28Ho_Tay%29_01.jpg');
$tran_quoc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/1/17/Temple_on_West_Lake%2C_2003%2C_Hanoi_39.jpg');

$content = <<<HTML
<!-- vg-hanoi-neighborhood-compare-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":56,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover vg-hanoi-neighborhood-compare-hero"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover vg-hanoi-neighborhood-compare-hero" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hanoi Opera House in the French Quarter, Vietnam" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed Hanoi neighborhood comparison - Updated {$review_date}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Old Quarter vs French Quarter vs West Lake</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Choose the Hanoi sleep zone that matches your route job. Old Quarter gives walking energy and food density. The French Quarter gives calmer premium central access. West Lake gives space, longer-stay comfort, and a slower rhythm that suits travelers who do not need to sleep inside the city center's loudest lane.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: the best answer is not the area with the prettiest listing. It is the block that protects sleep, pickup clarity, and the first morning without overpaying in taxi time or noise.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<p class="vg-image-credit">Image: xiquinhosilva / CC BY 2.0. Full license and source details are recorded in the source trail.</p>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-hanoi-neighborhood-compare-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-hanoi-neighborhood-compare-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-hanoi-neighborhood-compare-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Choose the Old Quarter if you want the densest walking, food, and first-time city energy. Choose the French Quarter if you want central Hanoi with a calmer, more premium feel. Choose West Lake if you want more space, longer-stay comfort, and easier apartment-style living.</strong> If the hotel area itself will not change the route, stop comparing and book the best room in the best block.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-hanoi-neighborhood-compare-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-hanoi-neighborhood-compare-shortlist">
<li><strong>Best first-time base:</strong> Old Quarter edge or Hoan Kiem-adjacent blocks when food, walking, and pickup convenience matter most.</li>
<li><strong>Best calm premium base:</strong> French Quarter when the trip needs quieter central nights, better hotel texture, and easier car movement.</li>
<li><strong>Best longer-stay base:</strong> West Lake when cafes, apartments, families, and a lower-density rhythm matter more than instant old-town energy.</li>
<li><strong>Best skip rule:</strong> do not move hotels just to switch vibes if the move only buys more packing and a longer taxi ride.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-hanoi-neighborhood-compare-at-a-glance:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Fast answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-neighborhood-compare-glance">
<tbody>
<tr><td data-label="Question"><strong>Which area is best for first-time visitors?</strong></td><td data-label="Answer">Old Quarter edge is the best first-time default when the trip is short, food-heavy, and pickup convenience matters.</td></tr>
<tr><td data-label="Question"><strong>Which area is best for a calmer premium stay?</strong></td><td data-label="Answer">The French Quarter is the calmest central answer when you want a better room and less street pressure without leaving the core.</td></tr>
<tr><td data-label="Question"><strong>Which area is best for longer stays?</strong></td><td data-label="Answer">West Lake is strongest when you want cafes, apartments, more space, and a slower rhythm for several nights.</td></tr>
<tr><td data-label="Question"><strong>Which area is best for food walks?</strong></td><td data-label="Answer">Old Quarter, because the street-energy density is the point. French Quarter and West Lake can still work, but they are not the same food-walk engine.</td></tr>
<tr><td data-label="Question"><strong>What should I check before booking?</strong></td><td data-label="Answer">Sleep, exact block noise, luggage access, elevator, pickup clarity, and whether the next morning starts with a transfer or a city walk.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-neighborhood-compare-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: the areas feel different</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-photo-grid vg-hanoi-neighborhood-compare-photo-grid" aria-label="Hanoi neighborhood comparison photography">
<figure><img src="{$old_quarter_image}" alt="Old Quarter market street in Hanoi" loading="lazy" decoding="async"><figcaption>Old Quarter: the walking density and food energy are the point. Image: Jakub Halun / CC BY 4.0; license record kept in the source trail.</figcaption></figure>
<figure><img src="{$french_quarter_image}" alt="French Quarter streets in Hanoi" loading="lazy" decoding="async"><figcaption>French Quarter: quieter central blocks with more premium-feeling stays. Image: thalling55 / CC BY 2.0; license record kept in the source trail.</figcaption></figure>
<figure><img src="{$west_lake_image}" alt="West Lake in Hanoi" loading="lazy" decoding="async"><figcaption>West Lake: space, water, and a slower everyday rhythm. Image: Amenoc / CC BY-SA 4.0; license record kept in the source trail.</figcaption></figure>
<figure><img src="{$tran_quoc_image}" alt="Tran Quoc Pagoda on West Lake in Hanoi" loading="lazy" decoding="async"><figcaption>West Lake also gives quieter temple and lake-walk time. Image: Syced / CC0; license record kept in the source trail.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-hanoi-neighborhood-compare-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Source trail and judgment boundary</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-neighborhood-compare-source-diversity">
<thead><tr><th>Source type</th><th>What it helps verify</th><th>What still needs judgment</th></tr></thead>
<tbody>
<tr><td data-label="Source type">Official tourism</td><td data-label="What it helps verify">Hanoi orientation, Old Quarter context, premium central stays, and city-level framing.</td><td data-label="What still needs judgment">Which exact block is quiet enough for your sleep and luggage plan.</td></tr>
<tr><td data-label="Source type">Airport and weather</td><td data-label="What it helps verify">Arrival risk, pickup timing, and whether the trip should value centrality or a buffer.</td><td data-label="What still needs judgment">Whether the route can absorb a taxi-heavy or airport-side compromise.</td></tr>
<tr><td data-label="Source type">Heritage and history</td><td data-label="What it helps verify">Why French Quarter and the central heritage axis feel different from Old Quarter and West Lake.</td><td data-label="What still needs judgment">The traveler's actual tolerance for density, noise, and longer-stay comfort.</td></tr>
<tr><td data-label="Source type">Image proof</td><td data-label="What it helps verify">The visual difference between street energy, premium central calm, and lake-side space.</td><td data-label="What still needs judgment">Whether the block in the photo matches the block in the hotel listing.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-neighborhood-compare-decision-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Area-by-area decision matrix</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-neighborhood-compare-matrix">
<thead><tr><th>Area</th><th>Best job</th><th>Tradeoff</th><th>Verdict</th></tr></thead>
<tbody>
<tr><td data-label="Area">Old Quarter</td><td data-label="Best job">Food, first-time walking, night energy, and the densest city feel.</td><td data-label="Tradeoff">Noise, crowds, and block-by-block variation.</td><td data-label="Verdict">Best when you want the city to feel immediate.</td></tr>
<tr><td data-label="Area">French Quarter</td><td data-label="Best job">Premium central calm, better hotel texture, and easier car movement.</td><td data-label="Tradeoff">Less street-food intensity at the door.</td><td data-label="Verdict">Best when sleep and elegance matter more than raw density.</td></tr>
<tr><td data-label="Area">West Lake</td><td data-label="Best job">Longer stays, families, cafes, apartments, and a slower tempo.</td><td data-label="Tradeoff">More rides to the classic first-time core.</td><td data-label="Verdict">Best when comfort and space beat instant centrality.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-neighborhood-compare-first-night-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best area by night count</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-neighborhood-compare-first-night-fit">
<thead><tr><th>Trip shape</th><th>Recommended base</th><th>Why</th><th>Booking posture</th></tr></thead>
<tbody>
<tr><td data-label="Trip shape">One night</td><td data-label="Recommended base">French Quarter or Old Quarter edge</td><td data-label="Why">You need a clean first meal, an easy walk, and one simple pickup.</td><td data-label="Booking posture">Choose the room that protects sleep more than Instagram distance.</td></tr>
<tr><td data-label="Trip shape">Two nights</td><td data-label="Recommended base">Old Quarter edge</td><td data-label="Why">Two nights is where food, walking, and first-time orientation matter most.</td><td data-label="Booking posture">Avoid the loudest lanes and confirm elevator, quiet room, and vehicle access.</td></tr>
<tr><td data-label="Trip shape">Three nights</td><td data-label="Recommended base">Old Quarter or French Quarter</td><td data-label="Why">Three nights can support one louder orientation block and one calmer central block.</td><td data-label="Booking posture">Split only if the move clearly improves the route.</td></tr>
<tr><td data-label="Trip shape">Four or more nights</td><td data-label="Recommended base">West Lake or a split with the core</td><td data-label="Why">The stay starts to reward apartment space, cafes, and a lower-density daily rhythm.</td><td data-label="Booking posture">Only move if the second area adds a real trip job.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-neighborhood-compare-sleep-noise:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Sleep and noise</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-neighborhood-compare-sleep-noise">
<thead><tr><th>Area</th><th>Noise profile</th><th>Best sleep use</th><th>Warning</th></tr></thead>
<tbody>
<tr><td data-label="Area">Old Quarter</td><td data-label="Noise profile">Highest variance; some blocks are quiet, some are loud late.</td><td data-label="Best sleep use">Short stays when walking matters more than room serenity.</td><td data-label="Warning">Do not trust the neighborhood name alone; trust the exact street.</td></tr>
<tr><td data-label="Area">French Quarter</td><td data-label="Noise profile">Calmer and more controlled in most central pockets.</td><td data-label="Best sleep use">Better for premium or business-style sleep.</td><td data-label="Warning">Check whether the room faces traffic or a back lane.</td></tr>
<tr><td data-label="Area">West Lake</td><td data-label="Noise profile">Usually calmer overall, but far from the city core.</td><td data-label="Best sleep use">Longer stays, families, and travelers who prefer space.</td><td data-label="Warning">More calm can also mean more taxi dependence.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-neighborhood-compare-food-walks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Food, walking, and evening energy</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-neighborhood-compare-food-walks">
<thead><tr><th>Area</th><th>Best for</th><th>Why it works</th><th>When to skip</th></tr></thead>
<tbody>
<tr><td data-label="Area">Old Quarter</td><td data-label="Best for">Street food, first-time wandering, and people who want the city under their feet.</td><td data-label="Why it works">It gives the fastest Hanoi lesson.</td><td data-label="When to skip">When sleep and a calmer room are more valuable than immediate texture.</td></tr>
<tr><td data-label="Area">French Quarter</td><td data-label="Best for">Balanced food access, lakes, and a nicer central evening loop.</td><td data-label="Why it works">You keep central access without living in the loudest lanes.</td><td data-label="When to skip">When you want the densest old-street energy right outside the door.</td></tr>
<tr><td data-label="Area">West Lake</td><td data-label="Best for">Cafe time, lake walks, and a slower evening schedule.</td><td data-label="Why it works">It suits travelers who want Hanoi to feel livable, not only intense.</td><td data-label="When to skip">When you only have one or two nights and every walk needs to count.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-neighborhood-compare-transfer-pickup:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Pickup and transfer logic</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-neighborhood-compare-transfer-pickup">
<thead><tr><th>Transfer job</th><th>Old Quarter</th><th>French Quarter</th><th>West Lake</th></tr></thead>
<tbody>
<tr><td data-label="Transfer job">Ninh Binh or bay pickup</td><td data-label="Old Quarter">Usually strongest for shared-tour pickup convenience.</td><td data-label="French Quarter">Still good when the operator accepts central pickup.</td><td data-label="West Lake">Usually the least efficient of the three.</td></tr>
<tr><td data-label="Transfer job">Noi Bai arrival</td><td data-label="Old Quarter">Good if you want the city core immediately.</td><td data-label="French Quarter">Good balance of central access and calmer sleep.</td><td data-label="West Lake">Useful only if the first night is already a longer-stay setup.</td></tr>
<tr><td data-label="Transfer job">Airport departure</td><td data-label="Old Quarter">Fine if the departure time is not fragile.</td><td data-label="French Quarter">Often the easiest premium final-night choice.</td><td data-label="West Lake">Works, but often adds the most taxi drag.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-neighborhood-compare-traveler-profiles:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best match by traveler profile</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-neighborhood-compare-traveler-profiles">
<thead><tr><th>Traveler</th><th>Best base</th><th>Why</th></tr></thead>
<tbody>
<tr><td data-label="Traveler">First-time city explorer</td><td data-label="Best base">Old Quarter edge</td><td data-label="Why">It gives the fastest orientation and the easiest food/walk loop.</td></tr>
<tr><td data-label="Traveler">Couple or premium traveler</td><td data-label="Best base">French Quarter</td><td data-label="Why">It keeps central access while softening the city pressure.</td></tr>
<tr><td data-label="Traveler">Family or longer-stay visitor</td><td data-label="Best base">West Lake</td><td data-label="Why">It buys space, apartment comfort, and a slower daily rhythm.</td></tr>
<tr><td data-label="Traveler">Repeat visitor</td><td data-label="Best base">West Lake or French Quarter</td><td data-label="Why">Repeat visitors usually value comfort, cafes, and a more specific neighborhood job.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-neighborhood-compare-cost-booking:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cost and booking checks</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-neighborhood-compare-cost-booking">
<thead><tr><th>Cost issue</th><th>Old Quarter</th><th>French Quarter</th><th>West Lake</th></tr></thead>
<tbody>
<tr><td data-label="Cost issue">Room rate</td><td data-label="Old Quarter">Often wide range, from basic to boutique.</td><td data-label="French Quarter">Usually higher for similarly central comfort.</td><td data-label="West Lake">Can be strong value for larger rooms or apartments.</td></tr>
<tr><td data-label="Cost issue">Taxi dependency</td><td data-label="Old Quarter">Often lowest if you walk most things.</td><td data-label="French Quarter">Moderate.</td><td data-label="West Lake">Usually highest.</td></tr>
<tr><td data-label="Cost issue">Value test</td><td data-label="Old Quarter">Best when energy is the payoff.</td><td data-label="French Quarter">Best when calm is the payoff.</td><td data-label="West Lake">Best when space is the payoff.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-neighborhood-compare-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-neighborhood-compare-skip-logic">
<thead><tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr></thead>
<tbody>
<tr><td data-label="If you want...">A short first trip</td><td data-label="Protect this">Walking energy, food, and fast orientation.</td><td data-label="Skip first">A long taxi to buy a calmer block.</td><td data-label="Only add back when...">You have enough nights for the move to matter.</td></tr>
<tr><td data-label="If you want...">A calmer premium stay</td><td data-label="Protect this">Sleep and central access.</td><td data-label="Skip first">Old Quarter density that looks exciting but steals rest.</td><td data-label="Only add back when...">Street energy is the reason for the trip.</td></tr>
<tr><td data-label="If you want...">Longer-stay comfort</td><td data-label="Protect this">Space, apartment feel, and a livable rhythm.</td><td data-label="Skip first">A false promise that you need to live inside the old core.</td><td data-label="Only add back when...">You only have one or two Hanoi nights.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-neighborhood-compare-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before booking</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-neighborhood-compare-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-neighborhood-compare-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/northern-vietnam/ha-noi" target="_blank" rel="noopener">Vietnam.travel Ha Noi</a>, <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>, and <a href="/destinations/best-things-to-do-in-hanoi/">Best Things to Do in Hanoi</a> before deciding whether the city deserves more than one central night.</li>
<li>Use <a href="https://vietnam.travel/things-to-do/explore-old-quarter-your-way" target="_blank" rel="noopener">Explore Old Quarter your way</a> plus the checked luxury-hotel source trail to separate energy-first and comfort-first central ideas.</li>
<li>Use <a href="https://vietnamairport.vn/en/noi-bai-airport" target="_blank" rel="noopener">Noi Bai airport</a> and <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a> before making a taxi-heavy or weather-sensitive compromise.</li>
<li>Use the checked Vietnamese history source trail and <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a> before assuming French Quarter or West Lake should be chosen for style alone.</li>
<li>Before paying, read <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-neighborhood-compare-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hanoi neighborhood comparison FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-hanoi-neighborhood-compare-faq">
<details><summary>Is the Old Quarter always the best choice?</summary><p>No. It is the best choice when you want walking energy, food density, and a first-time central base. It is not the best choice when sleep or calm matters more than being inside the busiest city texture.</p></details>
<details><summary>Is the French Quarter worth paying more for?</summary><p>Usually yes if the trip needs calmer central access, better room texture, or more premium comfort without leaving the core. It is less useful if you want the loudest street-food loop outside the door.</p></details>
<details><summary>Is West Lake too far out?</summary><p>Not for a longer stay. West Lake becomes a strong choice when the route values cafes, space, and a slower rhythm more than instant old-town density. It is weaker for a short first visit.</p></details>
<details><summary>Which area is best for families?</summary><p>West Lake usually wins for families because the rooms, pace, and daily rhythm are easier. French Quarter is the central premium alternative if you want more city access.</p></details>
<details><summary>Which area is best for one or two nights?</summary><p>Old Quarter edge or the French Quarter usually gives the cleanest short-stay result. West Lake tends to pay off only when Hanoi has more time to breathe.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a> and the <a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a> guide, then use <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, and <a href="/destinations/best-things-to-do-in-hanoi/">Best Things to Do in Hanoi</a> before choosing whether the Hanoi base should stay central or move quieter.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-hanoi-neighborhood-compare-hero:v1',
    'concierge verdict' => 'vg-hanoi-neighborhood-compare-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-hanoi-neighborhood-compare-at-a-glance:v1',
    'photo grid' => 'vg-hanoi-neighborhood-compare-photo-grid:v1',
    'source diversity' => 'vg-hanoi-neighborhood-compare-source-diversity:v1',
    'decision matrix' => 'vg-hanoi-neighborhood-compare-decision-matrix:v1',
    'first night fit' => 'vg-hanoi-neighborhood-compare-first-night-fit:v1',
    'sleep noise' => 'vg-hanoi-neighborhood-compare-sleep-noise:v1',
    'food walks' => 'vg-hanoi-neighborhood-compare-food-walks:v1',
    'transfer pickup' => 'vg-hanoi-neighborhood-compare-transfer-pickup:v1',
    'traveler profiles' => 'vg-hanoi-neighborhood-compare-traveler-profiles:v1',
    'cost booking' => 'vg-hanoi-neighborhood-compare-cost-booking:v1',
    'skip logic' => 'vg-hanoi-neighborhood-compare-skip-logic:v1',
    'live checks' => 'vg-hanoi-neighborhood-compare-live-checks:v1',
    'FAQ' => 'vg-hanoi-neighborhood-compare-faq:v1',
    'related routes shortcode' => '[vg_related_routes]',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_hanoi_compare_ops_assert_required_content_markers($content, $required_content_markers);
vg_hanoi_compare_ops_assert_internal_page_links_are_published('Old Quarter vs French Quarter vs West Lake guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Old Quarter vs French Quarter vs West Lake',
    'post_name'      => 'old-quarter-vs-french-quarter-vs-west-lake',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_hanoi_compare_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Hanoi comparison for international travelers choosing between Old Quarter energy, French Quarter calm, and West Lake longer-stay comfort.',
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
    vg_hanoi_compare_ops_fail('Could not publish Old Quarter vs French Quarter vs West Lake: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_hanoi_compare_ops_fail('Could not publish Old Quarter vs French Quarter vs West Lake: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Old Quarter vs French Quarter vs West Lake: Best Hanoi Base');
update_post_meta($page_id, 'rank_math_description', 'Compare Hanoi\'s Old Quarter, French Quarter, and West Lake by sleep, walking, food, pickup clarity, family fit, premium calm, and longer-stay comfort.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Old Quarter vs French Quarter vs West Lake');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Choose the best Hanoi neighborhood by trip job: Old Quarter for energy and food density, French Quarter for calmer premium central access, or West Lake for space and longer-stay comfort.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Old Quarter vs French Quarter vs West Lake comparison with a concierge verdict, at-a-glance decision table, shared proof panel, photo proof, source-diversity panel, decision matrix, first-night fit, sleep/noise map, food and walkability guide, transfer/pickup logic, traveler profiles, cost checks, skip logic, live checks, FAQ, compare hub note, homepage route-spine support, inbound related routes, source trail, and update log.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Ha Noi destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-noi - checked {$review_date}\nVietnam.travel - Explore Old Quarter your way - https://vietnam.travel/things-to-do/explore-old-quarter-your-way - checked {$review_date}\nVietnam.travel - Comfort meet culture Hanoi luxury hotels - https://vietnam.travel/things-to-do/comfort-meet-culture-hanoi-luxury-hotels - checked {$review_date}\nVietnam.travel - Vietnamese history primer - https://vietnam.travel/things-to-do/vietnamese-history-primer - checked {$review_date}\nVietnam.travel - 11 must-see attractions Ha Noi - https://vietnam.travel/things-to-do/11-must-see-attractions-ha-noi - checked {$review_date}\nNoi Bai International Airport - https://vietnamairport.vn/en/noi-bai-airport - checked {$review_date}\nNational Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked {$review_date}\nUNESCO World Heritage Centre - Central Sector of the Imperial Citadel of Thang Long - Ha Noi - https://whc.unesco.org/en/list/1328/ - checked {$review_date}\nWikimedia Commons image record - Hanoi Opera House, 24 December 2016 - https://commons.wikimedia.org/wiki/File:Hanoi_Opera_House,_24_December_2016.jpg - license checked {$review_date}\nWikimedia Commons image record - Old Quarter market, Hanoi, 20240123 1811 3411 - https://commons.wikimedia.org/wiki/File:Old_Quarter_market,_Hanoi,_20240123_1811_3411.jpg - license checked {$review_date}\nWikimedia Commons image record - French Quarter, Hanoi (1346056681) - https://commons.wikimedia.org/wiki/File:French_Quarter,_Hanoi_(1346056681).jpg - license checked {$review_date}\nWikimedia Commons image record - Around West Lake (Ho Tay) 01 - https://commons.wikimedia.org/wiki/File:Around_West_Lake_(Ho_Tay)_01.jpg - license checked {$review_date}\nWikimedia Commons image record - Temple on West Lake, 2003, Hanoi 39 - https://commons.wikimedia.org/wiki/File:Temple_on_West_Lake,_2003,_Hanoi_39.jpg - license checked {$review_date}\nWikimedia Commons image record - Hanoi Hoan Kiem Lake - https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg - license checked {$review_date}");
update_post_meta($page_id, 'vg_eeat_field_note', 'This comparison is a Hanoi sleep-zone decision guide: use it after you know Hanoi should anchor the route, but before booking the exact block that will change sleep, walking, pickup, and first-morning rhythm.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Comparison guide built to solve the actual Hanoi neighborhood decision rather than to recycle a generic hotel list.\nConcierge verdict separates Old Quarter energy, French Quarter calm, and West Lake space by trip job.\nAt-a-glance table answers the fast booking questions.\nPhoto proof shows the three areas as different lived environments, not just map names.\nSource-diversity panel explains what official tourism, airport, weather, heritage, and image sources can prove.\nDecision matrix, first-night fit, sleep/noise map, food and walkability, pickup logic, traveler profiles, cost checks, and skip logic make the guide useful even after the first scan.\nLive checks keep the page connected to current official context without overlinking the visible body.\nSource trail, update log, and related routes keep the page durable and reviewable.");

$related_routes = "Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Decide Hanoi's route role, night count, and stay logic before choosing the exact neighborhood.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nWhere to Stay in Hanoi | /destinations/where-to-stay-in-hanoi/ | Compare the broader stay-area decision before narrowing from neighborhoods to blocks.\nBest Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Narrow the attraction list after choosing the sleep zone that protects the first night.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Put Hanoi neighborhood choice inside the full planning order.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Decide how Hanoi fits the broader destination shortlist.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether the north should lead the route before over-optimizing the base.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check north weather, heat, rain, and cold before relying on a long walking base.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Count Noi Bai arrival, day-trip pickups, trains, bay transfers, and onward flights.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price neighborhood choice against taxi time, room quality, and walkability.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Prepare arrival cash, cards, and ATMs before Hanoi check-in.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, ride-hailing, hotel contact, and pickup coordination from becoming avoidable friction.\nVietnam E-Visa | /plan/vietnam-evisa/ | Recheck official entry timing before building Hanoi around arrival assumptions.\n7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Decide whether a one-week route should stay north-focused.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Hanoi can support Ninh Binh, the bay, and a calmer central rhythm without rushing.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when two weeks can support a real Hanoi base and a northern side chapter.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use three weeks to choose deeper Hanoi, bay, mountains, and central or southern movement.\nNinh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide whether the first Hanoi side move should be a day trip or overnight countryside.\nHa Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Choose bay timing after Hanoi arrival and pickup logic are clear.\nCat Ba Travel Guide | /destinations/cat-ba-travel-guide/ | Use when the bay chapter needs island-base logic instead of a simple cruise.\nBai Tu Long Bay Guide | /destinations/bai-tu-long-bay-guide/ | Compare quieter bay choices after the core Hanoi route is protected.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check trip interruption, road transfers, heat/cold, and mountain/bay coverage.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair taxis, markets, phone handling, and night movement with practical risk habits.";
vg_hanoi_compare_ops_assert_related_route_meta_links_are_published('Old Quarter vs French Quarter vs West Lake related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero: Hanoi Opera House, 24 December 2016 by xiquinhosilva, CC BY 2.0. Body images: Old Quarter market, Hanoi, 20240123 1811 3411 by Jakub Halun, CC BY 4.0; French Quarter, Hanoi (1346056681) by thalling55, CC BY 2.0; Around West Lake (Ho Tay) 01 by Amenoc, CC BY-SA 4.0; Temple on West Lake, 2003, Hanoi 39 by Syced, CC0; Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0.');
update_post_meta($page_id, '_generate-disable-headline', 'true');

delete_post_meta($page_id, '_rank_math_title');
delete_post_meta($page_id, '_rank_math_description');
delete_post_meta($page_id, '_rank_math_focus_keyword');

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
        vg_hanoi_compare_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_hanoi_compare_ops_fail('Old Quarter vs French Quarter vs West Lake was updated but is not published.');
}

vg_hanoi_compare_ops_refresh_compare_hub();
vg_hanoi_compare_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_hanoi_compare_ops_log("Published Old Quarter vs French Quarter vs West Lake: {$page_id} {$updated_permalink}");

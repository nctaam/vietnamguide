<?php
/**
 * Publish the Cat Ba Travel Guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-cat-ba-travel-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_cat_ba_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_cat_ba_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_cat_ba_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_CAT_BA_GUIDE_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_cat_ba_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_cat_ba_ops_fail('Could not resolve a valid WordPress author for the Cat Ba Travel Guide.');
}

function vg_cat_ba_ops_internal_path_from_href(string $href): ?string
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

function vg_cat_ba_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_cat_ba_ops_internal_path_from_href($href);

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
        vg_cat_ba_ops_fail(implode(PHP_EOL, $failures));
    }

    vg_cat_ba_ops_log("Validated {$label} internal page links are published.");
}

function vg_cat_ba_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_cat_ba_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_cat_ba_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

function vg_cat_ba_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
{
    $marked_block = $marker . "\n" . $block;

    if (str_contains($page->post_content, $marked_block)) {
        vg_cat_ba_ops_log("Skipped {$label}: current block already present.");
        return;
    }

    if (str_contains($page->post_content, $marker)) {
        $updated_content = preg_replace(
            '/' . preg_quote($marker, '/') . '\R<!-- wp:group\b.*?<!-- \/wp:group -->/s',
            $marked_block,
            $page->post_content,
            1,
            $replacement_count
        );

        if (! is_string($updated_content) || $replacement_count !== 1) {
            vg_cat_ba_ops_fail("Could not confidently refresh {$label}.");
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
        vg_cat_ba_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_cat_ba_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_cat_ba_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_cat_ba_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_cat_ba_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_cat_ba_ops_published_page_exists('destinations/cat-ba-travel-guide')) {
        vg_cat_ba_ops_log('Skipped Destinations hub refresh: Cat Ba Travel Guide is not published.');
        return;
    }

    $marker = '<!-- vg-cat-ba-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use Cat Ba as an island-base decision, not a cruise footnote</h3><p>The <a href="/destinations/cat-ba-travel-guide/">Cat Ba Travel Guide</a> helps travelers decide whether to base on the island, use it as a Lan Ha gateway, add national park time, or skip the island when logistics would weaken the route.</p></div>
<!-- /wp:group -->
HTML;

    vg_cat_ba_ops_assert_internal_page_links_are_published('Destinations hub Cat Ba note', $block);
    vg_cat_ba_ops_upsert_marked_group($hub, 'Destinations hub Cat Ba note', $marker, $block);
}

function vg_cat_ba_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_cat_ba_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_cat_ba_ops_assert_internal_page_links_are_published("{$label} related route line", $route_line);

    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);

    if (str_contains($current, '/destinations/cat-ba-travel-guide/')) {
        vg_cat_ba_ops_log("Skipped related-route refresh for {$label}: Cat Ba guide is already present.");
        return;
    }

    $next = trim($current) === '' ? $route_line : rtrim($current) . "\n" . $route_line;
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_cat_ba_ops_log("Added Cat Ba guide related route to {$label}: {$page->ID}");
}

function vg_cat_ba_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Cat Ba Travel Guide | /destinations/cat-ba-travel-guide/ | Decide island base, Lan Ha gateway, ferry/port logistics, national park time, and skip logic before adding Cat Ba to a northern route.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ] as $path => $label
    ) {
        vg_cat_ba_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_cat_ba_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/cat-ba-travel-guide', OBJECT, 'page');

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_cat_ba_ops_force_republish_enabled()) {
    vg_cat_ba_ops_fail('Cat Ba Travel Guide is not a draft. Set VG_FORCE_CAT_BA_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_cat_ba_ops_log("Preflight Cat Ba Travel Guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_cat_ba_ops_log('Preflight Cat Ba Travel Guide: no existing page found; creating a child page under /destinations/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Cat_Ba_Island.jpg/1920px-Cat_Ba_Island.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cat_Ba_Island.jpg');
$arrival_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f9/Arrival_on_Cat_Ba.jpg/1920px-Arrival_on_Cat_Ba.jpg');
$arrival_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Arrival_on_Cat_Ba.jpg');
$lan_ha_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/ca/Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg/1920px-Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg');
$lan_ha_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg');
$park_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/fb/Cat_Ba_National_Park%2C_Vietnam%2C_20240131_0922_4534.jpg/1920px-Cat_Ba_National_Park%2C_Vietnam%2C_20240131_0922_4534.jpg');
$park_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cat_Ba_National_Park,_Vietnam,_20240131_0922_4534.jpg');
$ngu_lam_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/Cat_Ba_National_Park_01.jpg/1920px-Cat_Ba_National_Park_01.jpg');
$ngu_lam_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cat_Ba_National_Park_01.jpg');
$trail_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d3/Hiking_trail_in_Cat_Ba_National_Park_1.jpg/1920px-Hiking_trail_in_Cat_Ba_National_Park_1.jpg');
$trail_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hiking_trail_in_Cat_Ba_National_Park_1.jpg');
$beach_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Beach_of_Cat_Ba_in_2015_01.jpg/1920px-Beach_of_Cat_Ba_in_2015_01.jpg');
$beach_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Beach_of_Cat_Ba_in_2015_01.jpg');

$content = <<<HTML
<!-- vg-cat-ba-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":52,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Cat Ba Island and limestone bay scenery from an elevated viewpoint in northern Vietnam" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 18, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Cat Ba Travel Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Cat Ba is the northern island-base decision behind many Lan Ha Bay trips. Use it when you want slower bay access, national park texture, and an island night that improves the route. Skip it when ferry timing, luggage movement, or an already crowded itinerary turns the island into extra logistics for the same scenery.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: Cat Ba is strongest when it replaces a weak cruise night or gives Lan Ha room to breathe. It is weakest when it is added after Ha Long, Ninh Binh, and Hanoi are already fighting for the same three northern days.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-cat-ba-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-cat-ba-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-cat-ba-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most international travelers, Cat Ba is worth adding only when the island changes the route.</strong> Choose it as a base for Lan Ha Bay, national park time, and a slower northern landscape chapter. Do not add it merely because a cruise brochure mentions Cat Ba; the island has to earn the ferry, transfer, and overnight.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-cat-ba-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-cat-ba-shortlist">
<li><strong>Best default:</strong> one or two nights on Cat Ba when Lan Ha Bay and national park time are both part of the plan.</li>
<li><strong>Best short route:</strong> use a Lan Ha cruise or Ha Long cruise without sleeping on Cat Ba if luggage movement would cost too much time.</li>
<li><strong>Best slow route:</strong> Hanoi, Cat Ba, Lan Ha, then Ninh Binh only when the route protects enough northern days.</li>
<li><strong>Best skip rule:</strong> skip Cat Ba if the trip already has Ha Long Bay plus Ninh Binh and no slack for island logistics.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-cat-ba-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-cat-ba-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-cat-ba-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fastest useful answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-cat-ba-glance-table">
<tbody>
<tr><td data-label="Question"><strong>What is Cat Ba best for?</strong></td><td data-label="Answer">An island base for Lan Ha Bay, national park hiking, limestone views, and a slower northern route.</td></tr>
<tr><td data-label="Question"><strong>How long should I stay?</strong></td><td data-label="Answer">One night is a minimum. Two nights are better when you want both bay and park time. Day-only Cat Ba is usually fragile.</td></tr>
<tr><td data-label="Question"><strong>Cat Ba or Ha Long cruise?</strong></td><td data-label="Answer">Use Cat Ba when the island and Lan Ha access matter. Use Ha Long when you want the classic cruise with simpler buying logistics.</td></tr>
<tr><td data-label="Question"><strong>Main planning risk?</strong></td><td data-label="Answer">Underestimating ferry/port transfers, weather changes, luggage movement, and the way Cat Ba competes with Ninh Binh for northern route time.</td></tr>
<tr><td data-label="Question"><strong>Source posture?</strong></td><td data-label="Answer">Check official Cat Ba tourism, Cat Ba National Park, Vietnam.travel, UNESCO, and route operators close to travel. Do not treat stale ferry times as evergreen.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-cat-ba-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what Cat Ba actually adds</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Cat Ba should add something visible to the route: island arrival friction, Lan Ha water access, national park texture, elevated viewpoints, trail time, and simple beach recovery. If the trip only needs a bay photo, the island may be more work than value.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-cat-ba-photo-grid" aria-label="Cat Ba travel guide photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Elevated view of Cat Ba Island and surrounding limestone water" loading="lazy" decoding="async"><figcaption>The island-base decision is about protected time, not only scenery. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$arrival_image}" alt="Boat arrival on Cat Ba Island in northern Vietnam" loading="lazy" decoding="async"><figcaption>Arrival logistics are part of the Cat Ba experience; plan them before adding the island. Image: <a href="{$arrival_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$lan_ha_image}" alt="Lan Ha Bay scenery near Cat Ba Island" loading="lazy" decoding="async"><figcaption>Lan Ha is the strongest reason to consider Cat Ba as more than a transfer point. Image: <a href="{$lan_ha_credit_url}" target="_blank" rel="license noopener">Saaremees / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$park_image}" alt="Forest and limestone landscape inside Cat Ba National Park" loading="lazy" decoding="async"><figcaption>National park time gives Cat Ba a different texture from a standard cruise. Image: <a href="{$park_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$ngu_lam_image}" alt="View from Ngu Lam Peak in Cat Ba National Park" loading="lazy" decoding="async"><figcaption>Viewpoint hikes can justify the island when the route has enough daylight. Image: <a href="{$ngu_lam_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$trail_image}" alt="Hiking trail in Cat Ba National Park" loading="lazy" decoding="async"><figcaption>Trail conditions, heat, and timing matter more than a generic park checklist. Image: <a href="{$trail_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$beach_image}" alt="Beach on Cat Ba Island" loading="lazy" decoding="async"><figcaption>Beach time is a recovery bonus, not the main reason to cross the bay. Image: <a href="{$beach_credit_url}" target="_blank" rel="license noopener">Vuong Tri Binh / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-cat-ba-base-decision:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Should you base on Cat Ba?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The right answer depends on whether Cat Ba changes the trip. Treat the island as a base, gateway, or skip decision.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cat-ba-base-decision">
<thead>
<tr><th>Choice</th><th>Best when</th><th>What improves</th><th>What can go wrong</th></tr>
</thead>
<tbody>
<tr><td data-label="Choice"><strong>Sleep on Cat Ba</strong></td><td data-label="Best when">You want Lan Ha Bay plus park/viewpoint time.</td><td data-label="What improves">The northern chapter feels slower and more varied.</td><td data-label="What can go wrong">Transfers and luggage eat the time you meant to save.</td></tr>
<tr><td data-label="Choice"><strong>Use Cat Ba as a gateway</strong></td><td data-label="Best when">A Lan Ha cruise or operator uses Cat Ba/Hai Phong logistics cleanly.</td><td data-label="What improves">You get quieter bay routing without forcing an island stay.</td><td data-label="What can go wrong">Port details are unclear until late, making onward travel fragile.</td></tr>
<tr><td data-label="Choice"><strong>Stay with Ha Long</strong></td><td data-label="Best when">You want the classic cruise product with easier buying and pickup.</td><td data-label="What improves">Less decision friction for first-time visitors.</td><td data-label="What can go wrong">Cruise density, rushed stops, and weak route maps.</td></tr>
<tr><td data-label="Choice"><strong>Skip the bay/island chapter</strong></td><td data-label="Best when">Ninh Binh, Hanoi, and central Vietnam already need the time.</td><td data-label="What improves">The route becomes calmer and cheaper.</td><td data-label="What can go wrong">You miss the sea chapter if it was the trip's real priority.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-cat-ba-lan-ha-gateway:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cat Ba and Lan Ha Bay: the real decision</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Cat Ba is most useful when it helps you access Lan Ha Bay with better pacing. The island is not automatically quieter, cheaper, or more premium; the operator, route map, port, weather policy, and time on water still decide the experience.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-cat-ba-lan-ha-gateway"} -->
<ul class="wp-block-list vg-check-list vg-cat-ba-lan-ha-gateway">
<li>Ask whether the trip uses Lan Ha Bay, Ha Long Bay, or a mixed route, then ask for the route map.</li>
<li>Confirm pickup point, pier, ferry/speedboat leg, return point, and what happens if weather changes operations.</li>
<li>Do not assume Cat Ba is automatically less crowded; the exact route and departure timing matter.</li>
<li>Use the island when you want time before or after the bay, not only as a different label for a cruise.</li>
</ul>
<!-- /wp:list -->

<!-- vg-cat-ba-access-logistics:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Access logistics: the part most guides underplay</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Cat Ba planning is logistics-sensitive. Keep live schedule checks close to travel and avoid building the next leg around an optimistic island exit.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cat-ba-access-logistics">
<thead>
<tr><th>Route</th><th>Use it when</th><th>Planning note</th><th>Source/check</th></tr>
</thead>
<tbody>
<tr><td data-label="Route">Hanoi to Cat Ba</td><td data-label="Use it when">Cat Ba is a real base, not a daydream add-on.</td><td data-label="Planning note">Protect the transfer day and avoid important evening commitments.</td><td data-label="Source/check">Operator timing plus <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport</a>.</td></tr>
<tr><td data-label="Route">Hai Phong to Cat Ba</td><td data-label="Use it when">You want a cleaner island gateway or are coming from a flight/rail/car plan that suits Hai Phong.</td><td data-label="Planning note">Confirm terminal, boat/ferry type, luggage handling, and final drop-off.</td><td data-label="Source/check"><a href="https://catba.com.vn/" target="_blank" rel="noopener">Cat Ba tourism/service information</a>.</td></tr>
<tr><td data-label="Route">Ha Long/Lan Ha cruise link</td><td data-label="Use it when">The bay product handles transfers and return logistics clearly.</td><td data-label="Planning note">Do not buy by bay name alone; buy by route map, pier, and return point.</td><td data-label="Source/check"><a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>.</td></tr>
<tr><td data-label="Route">Cat Ba to Ninh Binh</td><td data-label="Use it when">The northern route has enough days to protect both landscapes.</td><td data-label="Planning note">Add buffer for transfers, weather, and arrival fatigue.</td><td data-label="Source/check"><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-cat-ba-national-park:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cat Ba National Park: when it earns the island stay</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The park is the main reason Cat Ba can feel like a destination instead of only a bay gateway. Keep the plan simple: one serious nature objective, enough daylight, heat-aware pacing, and no fragile onward transfer immediately after.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cat-ba-national-park">
<thead>
<tr><th>Priority</th><th>Best fit</th><th>Route value</th><th>Do not force it when...</th></tr>
</thead>
<tbody>
<tr><td data-label="Priority">Viewpoint hike</td><td data-label="Best fit">Travelers who want a land-based limestone view after or before the bay.</td><td data-label="Route value">Adds effort, perspective, and contrast to cruise scenery.</td><td data-label="Do not force it when...">Heat, rain, footwear, or timing make a rushed hike unsafe or unpleasant.</td></tr>
<tr><td data-label="Priority">Forest/trail time</td><td data-label="Best fit">Slow northern routes with two nights on Cat Ba.</td><td data-label="Route value">Turns Cat Ba into a nature chapter rather than a transfer hub.</td><td data-label="Do not force it when...">You only have a departure morning.</td></tr>
<tr><td data-label="Priority">Beach recovery</td><td data-label="Best fit">Travelers who need a soft day after Hanoi, bay, or transfer pressure.</td><td data-label="Route value">Adds rest without adding another destination.</td><td data-label="Do not force it when...">The trip is short and beach time would replace the stronger bay/park reason.</td></tr>
<tr><td data-label="Priority">Town base</td><td data-label="Best fit">Travelers who want simple food, hotels, and operator access.</td><td data-label="Route value">Makes the island practical.</td><td data-label="Do not force it when...">You expected remote luxury or a polished resort island.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-cat-ba-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where Cat Ba fits in a Vietnam itinerary</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Cat Ba is a route-shaping decision. It should usually replace something, deepen the northern chapter, or make Lan Ha work better.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cat-ba-route-fit">
<thead>
<tr><th>Itinerary shape</th><th>Best Cat Ba use</th><th>Risk</th><th>Related guide</th></tr>
</thead>
<tbody>
<tr><td data-label="Itinerary shape">10 days in Vietnam</td><td data-label="Best Cat Ba use">Only add Cat Ba when the trip is north-heavy or cuts another stop.</td><td data-label="Risk">Losing central Vietnam depth to chase one more northern landscape.</td><td data-label="Related guide"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Itinerary shape">14 days in Vietnam</td><td data-label="Best Cat Ba use">Two nights can work if the north is a protected chapter.</td><td data-label="Risk">Using the extra days to stack Ha Long, Cat Ba, Ninh Binh, and mountains without rest.</td><td data-label="Related guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Itinerary shape">Hanoi + bay</td><td data-label="Best Cat Ba use">Choose Cat Ba/Lan Ha when quieter island routing matters more than the classic Ha Long label.</td><td data-label="Risk">Forgetting that port logistics still decide the experience.</td><td data-label="Related guide"><a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a></td></tr>
<tr><td data-label="Itinerary shape">Hanoi + Ninh Binh + bay/island</td><td data-label="Best Cat Ba use">Add Cat Ba only if the route has enough northern nights for both countryside and sea.</td><td data-label="Risk">Limestone fatigue and transfer fatigue.</td><td data-label="Related guide"><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a></td></tr>
<tr><td data-label="Itinerary shape">UNESCO/nature focus</td><td data-label="Best Cat Ba use">Use Ha Long Bay-Cat Ba Archipelago as the natural heritage anchor, then pair it with park time if the route allows.</td><td data-label="Risk">Collecting heritage names without protecting daylight and weather flexibility.</td><td data-label="Related guide"><a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-cat-ba-best-time-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best time and weather pivots</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Cat Ba sits inside the same northern Vietnam planning problem as Ha Long and Lan Ha: visibility, mist, heat, rain, and operational changes can matter more than a generic month label. Use official weather context for the season, then confirm local operations near travel.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cat-ba-best-time-weather">
<thead>
<tr><th>Season pressure</th><th>What it changes</th><th>Planning move</th></tr>
</thead>
<tbody>
<tr><td data-label="Season pressure">Clearer shoulder-season windows</td><td data-label="What it changes">Better viewpoint and bay payoff when visibility cooperates.</td><td data-label="Planning move">Protect Cat Ba if it is a core northern landscape choice.</td></tr>
<tr><td data-label="Season pressure">Cooler or misty periods</td><td data-label="What it changes">Atmosphere can be beautiful, but beach/recovery assumptions may weaken.</td><td data-label="Planning move">Favor bay/park atmosphere over swimming expectations.</td></tr>
<tr><td data-label="Season pressure">Hot/rainy or storm-risk periods</td><td data-label="What it changes">Trails, boat operations, transfers, and cancellation policies become more important.</td><td data-label="Planning move">Keep buffers and avoid non-refundable onward plans after a tight island exit.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-cat-ba-booking-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Booking checks before you commit</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Cat Ba bookings can look simple online while hiding the exact transfer and port details that decide the day.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-cat-ba-booking-checks"} -->
<ul class="wp-block-list vg-check-list vg-cat-ba-booking-checks">
<li>Confirm whether your plan enters via Hai Phong, Ha Long, Hanoi transfer, or cruise operator logistics.</li>
<li>Ask for pickup point, final drop-off, ferry/speedboat leg, pier, and luggage handling.</li>
<li>For Lan Ha cruises, ask for the actual route map and what changes in bad weather.</li>
<li>For national park time, confirm open hours, trail conditions, transport to the park, and return timing close to travel.</li>
<li>Do not schedule a critical flight or long paid transfer immediately after a tight Cat Ba exit.</li>
<li>Read cancellation terms before paying deposits for cruises, private transfers, and bundled island packages.</li>
</ul>
<!-- /wp:list -->

<!-- vg-cat-ba-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Cat Ba is not a mandatory add-on to Ha Long. The premium move is to keep the island only when it improves the route.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cat-ba-skip-logic">
<thead>
<tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you want...">A classic first trip</td><td data-label="Protect this">Hanoi, one northern landscape chapter, central Vietnam, and enough transfer slack.</td><td data-label="Skip first">Cat Ba if Ha Long and Ninh Binh are already included.</td><td data-label="Only add back when...">You remove another stop or extend the north.</td></tr>
<tr><td data-label="If you want...">Lan Ha Bay</td><td data-label="Protect this">The route map, port, operator, and weather policy.</td><td data-label="Skip first">A standard island overnight that adds no better water time.</td><td data-label="Only add back when...">The island gives you park, viewpoint, or recovery value.</td></tr>
<tr><td data-label="If you want...">National park time</td><td data-label="Protect this">Daylight, footwear, heat-aware pacing, and an unhurried return.</td><td data-label="Skip first">Beach/photo add-ons that steal the main nature objective.</td><td data-label="Only add back when...">You have two nights or a genuinely slow day.</td></tr>
<tr><td data-label="If you want...">A premium route</td><td data-label="Protect this">Calm movement and fewer fragile handoffs.</td><td data-label="Skip first">Any Cat Ba package that cannot explain transfer details clearly.</td><td data-label="Only add back when...">The logistics are written, timed, and refundable enough for your route.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-cat-ba-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before travel</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-cat-ba-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-cat-ba-live-checks">
<li>Use <a href="https://catba.com.vn/" target="_blank" rel="noopener">Cat Ba tourism/service information</a> for island service and local visitor context close to travel.</li>
<li>Use <a href="http://catbanationalpark.vn/" target="_blank" rel="noopener">Cat Ba National Park</a> for park-facing context, then confirm open hours and trail details locally.</li>
<li>Use <a href="https://vietnam.travel/places-to-go/northern-vietnam" target="_blank" rel="noopener">Vietnam.travel Northern Vietnam</a> and <a href="https://vietnam.travel/places-to-go/northern-vietnam/ha-long" target="_blank" rel="noopener">Vietnam.travel Ha Long</a> for official destination framing.</li>
<li>Use the <a href="https://whc.unesco.org/en/list/672/" target="_blank" rel="noopener">UNESCO Ha Long Bay-Cat Ba Archipelago listing</a> and <a href="https://www.unesco.org/en/mab/cat-ba" target="_blank" rel="noopener">UNESCO Cat Ba biosphere reserve page</a> for heritage and biosphere context.</li>
<li>Use <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> and <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport</a> before locking ferry, cruise, or onward transfer assumptions.</li>
<li>Read <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>, <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before adding Cat Ba to a tight route.</li>
</ul>
<!-- /wp:list -->

<!-- vg-cat-ba-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cat Ba travel FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-cat-ba-faq">
<details><summary>Is Cat Ba worth visiting?</summary><p>Yes, when you want an island base for Lan Ha Bay, national park time, and a slower northern chapter. It is less compelling when it is added to an already packed Ha Long and Ninh Binh route.</p></details>
<details><summary>How many nights should I spend on Cat Ba?</summary><p>One night is the minimum if the transfer is clean. Two nights are better when you want both Lan Ha and national park time without rushing.</p></details>
<details><summary>Is Cat Ba better than Ha Long Bay?</summary><p>It is not a simple better/worse choice. Ha Long is the classic cruise label. Cat Ba is better when island access, Lan Ha routing, and park time matter more than the easiest cruise purchase.</p></details>
<details><summary>Can I visit Cat Ba and Ninh Binh?</summary><p>Yes, but the route needs enough northern days. Otherwise the two landscapes compete for the same time and both become rushed.</p></details>
<details><summary>Should I book Cat Ba before checking ferry and port details?</summary><p>No. Confirm pickup, pier, ferry or boat leg, luggage handling, return point, and cancellation policy before paying a non-refundable deposit.</p></details>
<details><summary>Is Cat Ba a beach destination?</summary><p>Beach time can be pleasant, but it should be treated as a recovery bonus. The stronger reasons to choose Cat Ba are Lan Ha access, island pacing, and national park texture.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> for the full planning order, compare the water route in <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>, check classic cruise fit in <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, then test the northern route against <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>, <a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-cat-ba-hero:v1',
    'concierge verdict' => 'vg-cat-ba-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-cat-ba-at-a-glance:v1',
    'photo grid' => 'vg-cat-ba-photo-grid:v1',
    'base decision' => 'vg-cat-ba-base-decision:v1',
    'Lan Ha gateway' => 'vg-cat-ba-lan-ha-gateway:v1',
    'access logistics' => 'vg-cat-ba-access-logistics:v1',
    'national park' => 'vg-cat-ba-national-park:v1',
    'route fit' => 'vg-cat-ba-route-fit:v1',
    'best time weather' => 'vg-cat-ba-best-time-weather:v1',
    'booking checks' => 'vg-cat-ba-booking-checks:v1',
    'skip logic' => 'vg-cat-ba-skip-logic:v1',
    'live checks' => 'vg-cat-ba-live-checks:v1',
    'FAQ' => 'vg-cat-ba-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_cat_ba_ops_assert_required_content_markers($content, $required_content_markers);
vg_cat_ba_ops_assert_internal_page_links_are_published('Cat Ba Travel Guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Cat Ba Travel Guide',
    'post_name'      => 'cat-ba-travel-guide',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_cat_ba_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Cat Ba travel guide for international travelers deciding island base, Lan Ha gateway, ferry logistics, national park time, route fit, and skip logic.',
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
    vg_cat_ba_ops_fail('Could not publish Cat Ba Travel Guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_cat_ba_ops_fail('Could not publish Cat Ba Travel Guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Cat Ba Travel Guide: Island Base, Lan Ha Bay, and Park Time');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Cat Ba travel guide: island base or skip, Lan Ha Bay gateway, ferry and port logistics, Cat Ba National Park, route fit, weather, costs, and booking checks.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Cat Ba travel guide');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Cat Ba should be an island base, Lan Ha gateway, national park stop, or skip before adding ferry and port logistics to the northern route.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 18, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Cat Ba Travel Guide with a concierge verdict, at-a-glance decision table, licensed photo proof, island-base matrix, Lan Ha gateway checks, access logistics table, national park priority map, route-fit table, weather pivots, booking checks, skip logic, live official checks, FAQ, Destinations hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Cat Ba tourism/service information - https://catba.com.vn/ - checked July 18, 2026\nCat Ba National Park - http://catbanationalpark.vn/ - checked July 18, 2026\nVietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked July 18, 2026\nVietnam.travel - Ha Long destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-long - checked July 18, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 18, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 18, 2026\nUNESCO World Heritage Centre - Ha Long Bay-Cat Ba Archipelago - https://whc.unesco.org/en/list/672/ - checked July 18, 2026; automation may receive 403 because of access challenge\nUNESCO - Cat Ba biosphere reserve - https://www.unesco.org/en/mab/cat-ba - checked July 18, 2026\nWikimedia Commons image record - Cat Ba Island - https://commons.wikimedia.org/wiki/File:Cat_Ba_Island.jpg - license checked July 18, 2026\nWikimedia Commons image record - Arrival on Cat Ba - https://commons.wikimedia.org/wiki/File:Arrival_on_Cat_Ba.jpg - license checked July 18, 2026\nWikimedia Commons image record - Lan Ha Bay-Cat Ba Vietnam - https://commons.wikimedia.org/wiki/File:Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg - license checked July 18, 2026\nWikimedia Commons image record - Cat Ba National Park - https://commons.wikimedia.org/wiki/File:Cat_Ba_National_Park,_Vietnam,_20240131_0922_4534.jpg - license checked July 18, 2026\nWikimedia Commons image record - Cat Ba National Park 01 - https://commons.wikimedia.org/wiki/File:Cat_Ba_National_Park_01.jpg - license checked July 18, 2026\nWikimedia Commons image record - Hiking trail in Cat Ba National Park 1 - https://commons.wikimedia.org/wiki/File:Hiking_trail_in_Cat_Ba_National_Park_1.jpg - license checked July 18, 2026\nWikimedia Commons image record - Beach of Cat Ba in 2015 01 - https://commons.wikimedia.org/wiki/File:Beach_of_Cat_Ba_in_2015_01.jpg - license checked July 18, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Cat Ba is strongest when it replaces a weak cruise night or adds real Lan Ha, park, and island-base value; it is weakest when added as a rushed logistics layer.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Cat Ba guide built around island-base and Lan Ha gateway decisions rather than generic attraction copy.\nConcierge verdict that separates sleep-on-island, gateway-only, Ha Long default, and skip cases.\nAt-a-glance table answering value, timing, Cat Ba versus Ha Long, logistics risk, and source posture.\nLicensed photo proof for island viewpoint, boat arrival, Lan Ha Bay, Cat Ba National Park, Ngu Lam Peak, hiking trail, and beach recovery.\nBase-decision matrix that prevents adding Cat Ba when it does not change the route.\nLan Ha gateway section that keeps route map, port, operator, and weather policy visible.\nAccess logistics table covering Hanoi, Hai Phong, bay/cruise link, and Ninh Binh handoff.\nNational park priority map focused on viewpoint, trail, beach recovery, and town-base usefulness.\nRoute-fit table linked to 10 Days, 14 Days, Ha Long/Lan Ha, Ninh Binh, and UNESCO content.\nBest-time section grounded in northern weather and live-operations discipline.\nBooking checks for transfer, pier, ferry/speedboat, luggage, park, cruise, and cancellation details.\nSkip logic that protects route value over island-completion pressure.\nLive-check list tied to Cat Ba tourism, Cat Ba National Park, Vietnam.travel, UNESCO, comparison, Ha Long, Ninh Binh, best time, transport, and cost pages.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Ha Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Choose the right bay, port, cruise style, Cat Ba access, and overnight value before booking.\nHa Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Decide whether the classic bay cruise or a Cat Ba/Lan Ha route better fits the northern chapter.\nNinh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide whether the northern landscape chapter should be countryside, bay, island, or a calmer combination.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before adding an island-base decision.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Compare Cat Ba against the broader destination shortlist.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Use Ha Long Bay-Cat Ba Archipelago as a natural heritage route decision.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check northern visibility, heat, rain, and storm-season pressure before locking the island.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Confirm ferry, port, pickup, luggage, and onward movement before booking.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget for transfers, cruise class, island nights, flexibility, and route buffers.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Cat Ba improves or overloads a short route.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when Cat Ba can become a protected northern island chapter.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check water, weather, hiking, transfer, and interruption coverage.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair cruise deposits, transfers, water activity, and cancellation rules with practical risk checks.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Cat Ba island image: Cat Ba Island by Christophe95, CC BY-SA 4.0. Body images: Arrival on Cat Ba by Christophe95, CC BY-SA 4.0; Lan Ha Bay-Cat Ba by Saaremees, CC BY-SA 4.0; Cat Ba National Park by Jakub Halun, CC BY 4.0; Cat Ba National Park 01 by Christophe95, CC BY-SA 4.0; Hiking trail in Cat Ba National Park 1 by Christophe95, CC BY-SA 4.0; Beach of Cat Ba in 2015 01 by Vuong Tri Binh, CC BY-SA 4.0.');
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
        vg_cat_ba_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_cat_ba_ops_fail('Cat Ba Travel Guide was updated but is not published.');
}

vg_cat_ba_ops_refresh_destinations_hub();
vg_cat_ba_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_cat_ba_ops_log("Published Cat Ba Travel Guide: {$page_id} {$updated_permalink}");

<?php
/**
 * Publish the Ha Long Bay vs Lan Ha Bay comparison guide.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-ha-long-lan-ha-comparison-guide.php --allow-root
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
    $value = getenv('VG_FORCE_HA_LONG_LAN_HA_COMPARISON_REPUBLISH');

    return is_string($value) && trim($value) === '1';
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

    vg_ops_fail('Could not resolve a valid WordPress author for the Ha Long Bay vs Lan Ha Bay guide.');
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

function vg_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

function vg_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
{
    $marked_block = $marker . "\n" . $block;

    if (str_contains($page->post_content, $marked_block)) {
        vg_ops_log("Skipped {$label}: current block already present.");
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
            vg_ops_fail("Could not confidently refresh {$label}.");
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
        vg_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_ops_refresh_compare_hub(): void
{
    $hub = get_page_by_path('compare', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_ops_log('Skipped Compare hub refresh: compare page was not found or is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('compare/ha-long-bay-vs-lan-ha-bay')) {
        vg_ops_log('Skipped Compare hub refresh: Ha Long/Lan Ha comparison is not published.');
        return;
    }

    $marker = '<!-- vg-ha-long-lan-ha-compare-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose the right bay before choosing the cruise</h3><p>The <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a> guide helps travelers compare iconic scenery, quieter routing, Cat Ba access, port logistics, overnight value, and crowd pressure before paying for a cruise.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Compare hub Ha Long/Lan Ha note', $block);
    vg_ops_upsert_marked_group($hub, 'Compare hub Ha Long/Lan Ha note', $marker, $block);
}

function vg_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_ops_assert_internal_page_links_are_published("{$label} related route line", $route_line);

    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);

    if (str_contains($current, '/compare/ha-long-bay-vs-lan-ha-bay/')) {
        vg_ops_log("Skipped related-route refresh for {$label}: Ha Long/Lan Ha comparison is already present.");
        return;
    }

    $next = trim($current) === '' ? $route_line : rtrim($current) . "\n" . $route_line;
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_ops_log("Added Ha Long/Lan Ha comparison related route to {$label}: {$page->ID}");
}

function vg_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Ha Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Choose the right bay, port, cruise style, and overnight value before paying a deposit.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
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
        vg_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('compare', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: compare');
}

$page = get_page_by_path('compare/ha-long-bay-vs-lan-ha-bay', OBJECT, 'page');

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_ops_force_republish_enabled()) {
    vg_ops_fail('Ha Long Bay vs Lan Ha Bay guide is not a draft. Set VG_FORCE_HA_LONG_LAN_HA_COMPARISON_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_ops_log("Preflight Ha Long/Lan Ha comparison: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_ops_log('Preflight Ha Long/Lan Ha comparison: no existing page found; creating a child page under /compare/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg/1920px-Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg');
$lan_ha_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/ca/Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg/1920px-Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg');
$lan_ha_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg');
$cat_ba_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Cat_Ba_Island.jpg/1920px-Cat_Ba_Island.jpg');
$cat_ba_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cat_Ba_Island.jpg');
$cruise_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3e/Halong_Bay_Cruise_Boats_01.jpg/1920px-Halong_Bay_Cruise_Boats_01.jpg');
$cruise_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Halong_Bay_Cruise_Boats_01.jpg');
$sung_sot_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/10/Sung_Sot_Cave%2C_Ha_Long_Bay%2C_Vietnam%2C_20240128_1549_3836.jpg/1920px-Sung_Sot_Cave%2C_Ha_Long_Bay%2C_Vietnam%2C_20240128_1549_3836.jpg');
$sung_sot_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Sung_Sot_Cave,_Ha_Long_Bay,_Vietnam,_20240128_1549_3836.jpg');

$content = <<<HTML
<!-- vg-ha-long-lan-ha-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":56,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Aerial view of limestone islands in Ha Long Bay, Vietnam, used for a Ha Long Bay vs Lan Ha Bay comparison guide" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed comparison guide - Updated July 18, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Ha Long Bay vs Lan Ha Bay</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Ha Long Bay and Lan Ha Bay are not two separate fantasies. They are neighboring parts of the same northern seascape decision: iconic name recognition and classic cruise infrastructure on one side, Cat Ba-linked quieter routing and softer bay time on the other.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: the best bay is the one whose port, cruise route, weather window, cabin quality, and pickup timing still make sense after you add Hanoi, Ninh Binh, and the next transfer.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-ha-long-lan-ha-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-ha-long-lan-ha-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-ha-long-lan-ha-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Choose Ha Long Bay if you want the iconic, easier-to-buy cruise with the broadest operator choice. Choose Lan Ha Bay if you want a quieter Cat Ba-linked route and are willing to check port logistics more carefully.</strong> For most first-time travelers, the better purchase is not the most famous bay; it is the cruise route that wastes the least time and gives the cabin, deck, food, kayaking, and weather backup you actually need.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-ha-long-lan-ha-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-ha-long-lan-ha-shortlist">
<li><strong>Best classic first-timer:</strong> Ha Long Bay overnight cruise from Hanoi when name recognition, cave stops, and operator choice matter.</li>
<li><strong>Best quieter feel:</strong> Lan Ha Bay or Cat Ba-linked cruise when lower crowd pressure and softer routing matter more than the famous label.</li>
<li><strong>Best route filter:</strong> choose the port and transfer first, then choose bay branding second.</li>
<li><strong>Best skip rule:</strong> do not pay for an overnight cruise if the itinerary only gives you late boarding, early disembarkation, and no real bay time.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-ha-long-lan-ha-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-ha-long-lan-ha-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-ha-long-lan-ha-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fast answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-lan-ha-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Which bay is better?</strong></td><td data-label="Answer">Ha Long is better for classic recognition and easy cruise choice. Lan Ha is better for quieter Cat Ba-linked routing when logistics are clean.</td></tr>
<tr><td data-label="Question"><strong>Which is less crowded?</strong></td><td data-label="Answer">Lan Ha often feels quieter, but the exact experience depends on the cruise route, date, operator, and port, not only the bay name.</td></tr>
<tr><td data-label="Question"><strong>Which is easier from Hanoi?</strong></td><td data-label="Answer">Ha Long is usually the simpler default because cruise infrastructure and pickup products are broader. Lan Ha can be easy too, but confirm the pier and transfer path.</td></tr>
<tr><td data-label="Question"><strong>Is an overnight cruise worth it?</strong></td><td data-label="Answer">Worth it when the route has two days and the operator gives real bay time. Not worth it if a tight itinerary turns it into two long transfer edges around one short cruise window.</td></tr>
<tr><td data-label="Question"><strong>What should I verify before paying?</strong></td><td data-label="Answer">Pier, pickup city, route map, cabin type, deck space, kayaking/cave stops, cancellation/weather rules, and what happens if the bay authority changes operations.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-ha-long-lan-ha-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what changes between the choices</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The photos are not decoration. They show the planning difference: iconic open seascape, Lan Ha's Cat Ba adjacency, cruise density, caves, and island-base options.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-ha-long-lan-ha-photo-grid" aria-label="Ha Long Bay and Lan Ha Bay comparison photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Aerial limestone seascape in Ha Long Bay, Vietnam" loading="lazy" decoding="async"><figcaption>Ha Long is the iconic image most travelers are trying to buy. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$lan_ha_image}" alt="Lan Ha Bay scenery near Cat Ba Island in Vietnam" loading="lazy" decoding="async"><figcaption>Lan Ha is strongest when Cat Ba access and quieter water matter more than the famous name. Image: <a href="{$lan_ha_credit_url}" target="_blank" rel="license noopener">Saaremees / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cat_ba_image}" alt="Cat Ba Island above Lan Ha Bay in northern Vietnam" loading="lazy" decoding="async"><figcaption>Cat Ba changes the bay decision because it can turn the cruise into an island-base route. Image: <a href="{$cat_ba_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cruise_image}" alt="Cruise boats in Ha Long Bay, Vietnam" loading="lazy" decoding="async"><figcaption>Cruise density is a product decision, not just a scenery decision. Image: <a href="{$cruise_credit_url}" target="_blank" rel="license noopener">Shyamal L. / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$sung_sot_image}" alt="Sung Sot Cave inside Ha Long Bay, Vietnam" loading="lazy" decoding="async"><figcaption>Caves can add value when they fit the route, but they should not be the only reason to pick a cruise. Image: <a href="{$sung_sot_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-ha-long-lan-ha-decision-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Ha Long Bay vs Lan Ha Bay decision matrix</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this as a buying filter. The wrong cruise can make either bay feel rushed, crowded, or weaker than Ninh Binh.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-lan-ha-decision-matrix">
<thead>
<tr><th>Decision factor</th><th>Ha Long Bay</th><th>Lan Ha Bay</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Decision factor">First-time recognition</td><td data-label="Ha Long Bay">The famous bucket-list name, broader cruise inventory, and easier traveler shorthand.</td><td data-label="Lan Ha Bay">Less famous internationally, often sold through Cat Ba or bay-combination routes.</td><td data-label="VietnamGuide verdict">Choose Ha Long if this is the one bay moment you have always pictured.</td></tr>
<tr><td data-label="Decision factor">Crowd pressure</td><td data-label="Ha Long Bay">Can be busier, especially on popular routes and peak dates.</td><td data-label="Lan Ha Bay">Often quieter, but not automatically empty or better.</td><td data-label="VietnamGuide verdict">Ask for the route map and pier, not just the bay label.</td></tr>
<tr><td data-label="Decision factor">Cruise choice</td><td data-label="Ha Long Bay">More operators, cabin levels, day cruises, and overnight products.</td><td data-label="Lan Ha Bay">More route-specific; quality depends heavily on operator and Cat Ba/Hai Phong logistics.</td><td data-label="VietnamGuide verdict">Ha Long is simpler to buy; Lan Ha rewards more careful selection.</td></tr>
<tr><td data-label="Decision factor">Cat Ba access</td><td data-label="Ha Long Bay">Can connect toward Cat Ba, but many products are not built around an island base.</td><td data-label="Lan Ha Bay">Stronger fit if Cat Ba, Viet Hai, cycling, or island time matters.</td><td data-label="VietnamGuide verdict">Choose Lan Ha when Cat Ba is a real chapter, not a name on a brochure.</td></tr>
<tr><td data-label="Decision factor">Short trip fit</td><td data-label="Ha Long Bay">Usually easier for a packaged Hanoi overnight or day-cruise decision.</td><td data-label="Lan Ha Bay">Can work, but transfer/pier details decide whether it saves or burns time.</td><td data-label="VietnamGuide verdict">On a tight 10-day route, choose the cleaner transfer over the prettier promise.</td></tr>
<tr><td data-label="Decision factor">Premium feel</td><td data-label="Ha Long Bay">Premium depends on cabin, deck space, food, staff, route, and crowd management.</td><td data-label="Lan Ha Bay">Quieter water can feel more premium if the boat and logistics match.</td><td data-label="VietnamGuide verdict">Premium is the product, not only the bay.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-lan-ha-cruise-style:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Choose by cruise style, not just bay name</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The best bay decision comes after you decide what type of cruise day you are buying.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-lan-ha-cruise-style">
<thead>
<tr><th>Cruise style</th><th>Best fit</th><th>Ask before booking</th><th>Skip when</th></tr>
</thead>
<tbody>
<tr><td data-label="Cruise style">Day cruise</td><td data-label="Best fit">Travelers with a very tight route or no spare overnight.</td><td data-label="Ask before booking">Total drive time, actual hours on water, lunch quality, cave/kayak timing, and pier.</td><td data-label="Skip when">It turns into more road than bay.</td></tr>
<tr><td data-label="Cruise style">One-night cruise</td><td data-label="Best fit">Most first-time travelers who want sunrise, sunset, deck time, and a cleaner bay memory.</td><td data-label="Ask before booking">Cabin type, route map, disembarkation time, activities, and weather policy.</td><td data-label="Skip when">The schedule boards late and exits early with little protected bay time.</td></tr>
<tr><td data-label="Cruise style">Two-night cruise</td><td data-label="Best fit">Slow travelers, premium routes, photographers, and people avoiding another one-night hotel move.</td><td data-label="Ask before booking">Whether day two goes meaningfully deeper or repeats filler activities.</td><td data-label="Skip when">Vietnam has only 10 days and Central Vietnam or Ninh Binh would suffer.</td></tr>
<tr><td data-label="Cruise style">Cat Ba base plus bay day</td><td data-label="Best fit">Independent travelers, active travelers, lower-cost planners, and Lan Ha-oriented routes.</td><td data-label="Ask before booking">Ferry/road access, hotel base, boat departure point, and onward movement.</td><td data-label="Skip when">You want the easiest packaged Hanoi cruise.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-lan-ha-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route fit: where the bay belongs</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A bay cruise competes directly with Ninh Binh, Hanoi recovery time, and Central Vietnam. Put it in the route only when it earns that space.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-lan-ha-route-fit">
<thead>
<tr><th>Route context</th><th>Better bay move</th><th>What to protect</th><th>Related guide</th></tr>
</thead>
<tbody>
<tr><td data-label="Route context">10 days in Vietnam</td><td data-label="Better bay move">One-night Ha Long or Lan Ha cruise only if you cut a weaker stop elsewhere.</td><td data-label="What to protect">Hanoi arrival energy, Ninh Binh timing, and one central Vietnam chapter.</td><td data-label="Related guide"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Route context">14 days in Vietnam</td><td data-label="Better bay move">One-night cruise is easy; two nights only if the north is the lead.</td><td data-label="What to protect">Domestic flight logic and avoiding a chain of one-night stops.</td><td data-label="Related guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Route context">Ninh Binh plus bay</td><td data-label="Better bay move">Choose the cleanest transfer and avoid duplicating every limestone experience.</td><td data-label="What to protect">Ninh Binh countryside pace and bay deck time.</td><td data-label="Related guide"><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a></td></tr>
<tr><td data-label="Route context">Heritage/nature route</td><td data-label="Better bay move">Treat Ha Long Bay-Cat Ba Archipelago as one serious natural heritage chapter.</td><td data-label="What to protect">Enough time for either the bay or Cat Ba to matter.</td><td data-label="Related guide"><a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a></td></tr>
<tr><td data-label="Route context">Premium route</td><td data-label="Better bay move">Pay for cabin comfort, deck space, route quality, and fewer rushed transfers.</td><td data-label="What to protect">The experience, not just the star rating.</td><td data-label="Related guide"><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-lan-ha-weather-cancellation:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Weather and cancellation reality</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam.travel frames Ha Long with clearer skies around September to November, mist from December to March, sunshine and breeze in April and May, and monsoon unpredictability from June to August. That does not make one bay immune. It means the cruise decision needs a backup plan.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-ha-long-lan-ha-weather-cancellation"} -->
<ul class="wp-block-list vg-check-list vg-ha-long-lan-ha-weather-cancellation">
<li>Ask what happens if weather or bay authority rules change the route, delay boarding, or cancel sailing.</li>
<li>Keep the night after a cruise flexible if an important flight or train connection follows.</li>
<li>Check whether a day cruise or one-night cruise can be refunded, moved, or replaced.</li>
<li>Do not judge the entire region from one grey or misty bay day; mist can be atmospheric, but storms are a logistics issue.</li>
</ul>
<!-- /wp:list -->

<!-- vg-ha-long-lan-ha-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Skip logic is where the bay decision becomes premium. A famous cruise can still be the wrong choice if it damages the route around it.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-lan-ha-skip-logic">
<thead>
<tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you want...">Classic first Vietnam</td><td data-label="Protect this">One strong bay night, Hanoi recovery, and one central Vietnam middle.</td><td data-label="Skip first">A second bay-like landscape day that weakens Ninh Binh or Hoi An.</td><td data-label="Only add back when...">The north is intentionally the main chapter.</td></tr>
<tr><td data-label="If you want...">Lower crowd pressure</td><td data-label="Protect this">Route map, pier, sailing lane, and operator behavior.</td><td data-label="Skip first">Assuming Lan Ha is quiet without checking the actual itinerary.</td><td data-label="Only add back when...">The operator explains where the boat goes and why.</td></tr>
<tr><td data-label="If you want...">Premium cruise value</td><td data-label="Protect this">Cabin, deck space, food, staff ratio, and unhurried activities.</td><td data-label="Skip first">Buying on bay name or discount percentage alone.</td><td data-label="Only add back when...">The product quality is visible beyond marketing photos.</td></tr>
<tr><td data-label="If you want...">Tight 10-day route</td><td data-label="Protect this">One clean northern landscape decision.</td><td data-label="Skip first">Doing Ninh Binh, Ha Long, Lan Ha, and Sapa because every name appears online.</td><td data-label="Only add back when...">Something else leaves the itinerary.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-lan-ha-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before you book</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-ha-long-lan-ha-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-ha-long-lan-ha-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/northern-vietnam/ha-long" target="_blank" rel="noopener">Vietnam.travel Ha Long</a> for official destination framing and seasonal context.</li>
<li>Use the <a href="https://whc.unesco.org/en/list/672/" target="_blank" rel="noopener">UNESCO Ha Long Bay-Cat Ba Archipelago listing</a> for the current world heritage frame.</li>
<li>Use <a href="https://halongbay.com.vn/" target="_blank" rel="noopener">Ha Long Bay Management</a> for local bay management context, route/ticket/service notices, and bay operating information.</li>
<li>Use <a href="https://catba.com.vn/" target="_blank" rel="noopener">Cat Ba tourism/service information</a> when a Lan Ha or Cat Ba-based route is part of the plan.</li>
<li>Check <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before paying a non-refundable cruise deposit.</li>
</ul>
<!-- /wp:list -->

<!-- vg-ha-long-lan-ha-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Ha Long Bay vs Lan Ha Bay FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-ha-long-lan-ha-faq">
<details><summary>Is Lan Ha Bay part of Ha Long Bay?</summary><p>They are neighboring bay areas in the larger Ha Long-Cat Ba seascape. For planning, treat them as different cruise and port decisions rather than completely different scenery types.</p></details>
<details><summary>Which bay is better for a first-time Vietnam trip?</summary><p>Ha Long Bay is the safer classic default. Lan Ha Bay is the better choice when you value a quieter Cat Ba-linked route and have checked the transfer and pier details carefully.</p></details>
<details><summary>Is Lan Ha Bay less touristy?</summary><p>Often, but not automatically. The exact route, date, operator, activity stops, and port determine crowd pressure more than the name alone.</p></details>
<details><summary>Should I book a day cruise or overnight cruise?</summary><p>Book overnight if the route can spare the time and the cruise gives real deck time, sunrise/sunset, and activities. Book a day cruise only when the transfer-to-bay ratio still feels acceptable.</p></details>
<details><summary>Can I combine Ninh Binh and a bay cruise?</summary><p>Yes, but treat them as two northern landscape chapters competing for time. If the route is only 10 days, adding both usually requires cutting something else.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then decide whether the bay belongs in your route with <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>, <a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-ha-long-lan-ha-hero:v1',
    'concierge verdict' => 'vg-ha-long-lan-ha-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-ha-long-lan-ha-at-a-glance:v1',
    'photo grid' => 'vg-ha-long-lan-ha-photo-grid:v1',
    'decision matrix' => 'vg-ha-long-lan-ha-decision-matrix:v1',
    'cruise style' => 'vg-ha-long-lan-ha-cruise-style:v1',
    'route fit' => 'vg-ha-long-lan-ha-route-fit:v1',
    'weather cancellation' => 'vg-ha-long-lan-ha-weather-cancellation:v1',
    'skip logic' => 'vg-ha-long-lan-ha-skip-logic:v1',
    'live checks' => 'vg-ha-long-lan-ha-live-checks:v1',
    'FAQ' => 'vg-ha-long-lan-ha-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('Ha Long Bay vs Lan Ha Bay guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Ha Long Bay vs Lan Ha Bay',
    'post_name'      => 'ha-long-bay-vs-lan-ha-bay',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Ha Long Bay vs Lan Ha Bay comparison for international travelers choosing cruise route, port, overnight value, Cat Ba access, crowd pressure, and weather risk.',
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
    vg_ops_fail('Could not publish Ha Long Bay vs Lan Ha Bay guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Ha Long Bay vs Lan Ha Bay guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Ha Long Bay vs Lan Ha Bay: Which Cruise Area Is Better?');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Ha Long Bay vs Lan Ha Bay comparison: iconic cruise, quieter Lan Ha, Cat Ba access, ports, overnight value, route fit, weather risk, and booking checks.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Ha Long Bay vs Lan Ha Bay');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Ha Long Bay or Lan Ha Bay is the better cruise choice before paying a deposit, based on port, route, crowd pressure, Cat Ba access, weather, and itinerary fit.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 18, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Ha Long Bay vs Lan Ha Bay comparison guide with a concierge verdict, at-a-glance decision table, licensed photo proof, bay decision matrix, cruise-style chooser, route-fit table, weather/cancellation checks, skip logic, live official checks, FAQ, Compare hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Ha Long destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-long - checked July 18, 2026\nVietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked July 18, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 18, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 18, 2026\nUNESCO World Heritage Centre - Ha Long Bay-Cat Ba Archipelago - https://whc.unesco.org/en/list/672/ - checked July 18, 2026\nHa Long Bay Management - https://halongbay.com.vn/ - checked July 18, 2026\nCat Ba tourism/service information - https://catba.com.vn/ - checked July 18, 2026\nWikimedia Commons image record - Ha Long Bay, Vietnam, View from above - https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg - license checked July 18, 2026\nWikimedia Commons image record - Lan Ha Bay-Cat Ba Vietnam - https://commons.wikimedia.org/wiki/File:Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg - license checked July 18, 2026\nWikimedia Commons image record - Cat Ba Island - https://commons.wikimedia.org/wiki/File:Cat_Ba_Island.jpg - license checked July 18, 2026\nWikimedia Commons image record - Halong Bay Cruise Boats 01 - https://commons.wikimedia.org/wiki/File:Halong_Bay_Cruise_Boats_01.jpg - license checked July 18, 2026\nWikimedia Commons image record - Sung Sot Cave - https://commons.wikimedia.org/wiki/File:Sung_Sot_Cave,_Ha_Long_Bay,_Vietnam,_20240128_1549_3836.jpg - license checked July 18, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'The bay choice is a cruise logistics decision first: port, route map, operator quality, weather policy, and onward movement decide whether Ha Long or Lan Ha feels premium.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Comparison guide built around a real purchase decision rather than a prettier-bay listicle.\nConcierge verdict separates Ha Long's classic/easy-buy value from Lan Ha's quieter/Cat Ba-linked value.\nAt-a-glance answers for crowd pressure, day versus overnight, and booking checks.\nLicensed photo proof with visible credits for Ha Long, Lan Ha, Cat Ba, cruise density, and Sung Sot Cave.\nDecision matrix that covers name recognition, crowd pressure, cruise choice, Cat Ba access, short-route fit, and premium feel.\nCruise-style chooser that compares day cruise, one-night cruise, two-night cruise, and Cat Ba base.\nRoute-fit table linked to 10 Days, 14 Days, Ninh Binh, UNESCO, and Cost.\nWeather/cancellation checks grounded in Vietnam.travel seasonal framing.\nSkip logic that protects route value over bay-name completion.\nLive-check list tied to Vietnam.travel, UNESCO, Ha Long Bay Management, Cat Ba, transport, season, insurance, and safety pages.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before choosing a bay cruise.\nNinh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide whether the northern landscape chapter should be countryside, bay, or both.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Decide whether Ha Long or Lan Ha should be a day trip, overnight cruise, or skip inside the Hanoi route.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Compare bay value against other destination choices.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Use Ha Long Bay-Cat Ba Archipelago as a natural heritage route decision.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check seasonal visibility, mist, monsoon risk, and cancellation pressure.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Confirm pier, pickup, transfer, and onward movement before booking.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget for cruise level, private transfers, cancellation flexibility, and route buffers.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a bay cruise improves or overloads a short route.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when the bay can become a protected northern chapter.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check boat, weather, activity, interruption, and evacuation coverage before a bay route.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair cruise deposits, transfers, water activity, and cancellation rules with practical risk checks.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Ha Long aerial image: Ha Long Bay, Vietnam, View from above by Vyacheslav Argenberg, CC BY 4.0. Body images: Lan Ha Bay-Cat Ba by Saaremees, CC BY-SA 4.0; Cat Ba Island by Christophe95, CC BY-SA 4.0; Halong Bay Cruise Boats 01 by Shyamal L., CC BY-SA 4.0; Sung Sot Cave by Jakub Halun, CC BY 4.0.');
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
    vg_ops_fail('Ha Long Bay vs Lan Ha Bay guide was updated but is not published.');
}

vg_ops_refresh_compare_hub();
vg_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Ha Long Bay vs Lan Ha Bay guide: {$page_id} {$updated_permalink}");

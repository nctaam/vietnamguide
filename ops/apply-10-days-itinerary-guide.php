<?php
/**
 * Publish the 10 Days in Vietnam guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-10-days-itinerary-guide.php --allow-root
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
    $value = getenv('VG_FORCE_10_DAY_ITINERARY_REPUBLISH');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the 10 Days itinerary guide.');
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

function vg_ops_refresh_itineraries_hub(): void
{
    $hub = get_page_by_path('itineraries', OBJECT, 'page');

    if (! $hub instanceof WP_Post) {
        vg_ops_log('Skipped Itineraries hub refresh: itineraries page was not found.');
        return;
    }

    if ($hub->post_status !== 'publish') {
        vg_ops_log('Skipped Itineraries hub refresh: itineraries page is not published.');
        return;
    }

    $marker = '<!-- vg-itinerary-10-day-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Start with the 10-day route</h3><p>For most first-time visitors, the <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam guide</a> is the strongest first deep route to read because it shows what to keep, what to cut, and when not to force the whole country into one trip.</p></div>
<!-- /wp:group -->
HTML;

    $marked_block = $marker . "\n" . $block;

    if (str_contains($hub->post_content, $marked_block)) {
        vg_ops_log('Skipped Itineraries hub refresh: current 10-day note already present.');
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
            vg_ops_fail('Could not confidently refresh the Itineraries hub 10-day note.');
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
        vg_ops_fail('Could not refresh Itineraries hub note: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Itineraries hub note: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Itineraries hub 10-day note: {$hub->ID}");
}

$parent = get_page_by_path('itineraries', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: itineraries');
}

$page = get_page_by_path('itineraries/10-days-in-vietnam', OBJECT, 'page');
$allow_republish = vg_ops_force_republish_enabled();

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('10 Days itinerary guide is not a draft. Set VG_FORCE_10_DAY_ITINERARY_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    $permalink = get_permalink($page);
    vg_ops_log("Preflight 10 Days itinerary guide: {$page->ID} {$page->post_status} {$permalink}");
} else {
    vg_ops_log('Preflight 10 Days itinerary guide: no existing page found; creating a child page under /itineraries/.');
}

$guide_hero_image = esc_url(set_url_scheme(get_stylesheet_directory_uri(), 'https') . '/assets/images/ha-long-bay-vietnam-hero.jpg');
$bay_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg');
$hanoi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$hanoi_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg');
$trang_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');
$trang_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg');
$hue_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg/1920px-Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$hue_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$hoi_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hoi_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg');
$hai_van_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1920px-Vietnam%2C_Hai-Van-Pass.jpg');
$hai_van_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg');
$hcmc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg');
$hcmc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg');
$mekong_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1920px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg');
$mekong_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg');

$content = <<<HTML
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$guide_hero_image}","dimRatio":50,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Cruise boats among limestone karsts in Ha Long Bay, Vietnam" src="{$guide_hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed itinerary - Updated July 16, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">10 Days in Vietnam</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">For most first-time visitors, the strongest 10-day Vietnam route is not the one that touches every region. It is a north-plus-central route with Hanoi, Ninh Binh, Lan Ha or Ha Long Bay, Hue, Hoi An, and a Da Nang departure if flights allow.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: in ten days, remove one famous stop before you add one difficult transfer.</p>
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
<p class="vg-verdict-lede"><strong>Use ten days to travel well through two regions, not thinly through three.</strong> The best default is Hanoi, Ninh Binh, one bay night, Hue, Hoi An, and Da Nang. Add Ho Chi Minh City only when your flights make it natural or you are willing to cut Hue, Ninh Binh, or the bay.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><strong>Best for:</strong> first-time travelers who want culture, food, scenery, heritage, and a route that still leaves room to breathe.</li>
<li><strong>Avoid if:</strong> your only goal is beach time, nightlife, remote mountains, or ticking off Hanoi, Hue, Hoi An, Ho Chi Minh City, Mekong, Sapa, and a bay cruise in one pass.</li>
<li><strong>Verify before booking:</strong> arrival and departure airports, regional weather, bay cruise logistics, domestic flight timing, rail availability, and hotel cancellation terms for your exact dates.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-itinerary-10day-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-itinerary-10day-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-itinerary-10day-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Route brief</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">10-day Vietnam itinerary at a glance</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table">
<tbody>
<tr><td data-label="Decision"><strong>Best default route</strong></td><td data-label="VietnamGuide recommendation">Hanoi, Ninh Binh, one Lan Ha or Ha Long Bay night, Hue or Hoi An, and a Da Nang departure if flights allow.</td></tr>
<tr><td data-label="Decision"><strong>Airport shape</strong></td><td data-label="VietnamGuide recommendation">Arrive in Hanoi and leave from Da Nang for the cleanest north-plus-central route. Use Ho Chi Minh City only when the south is a real priority or your open-jaw flights save time.</td></tr>
<tr><td data-label="Decision"><strong>Night logic</strong></td><td data-label="VietnamGuide recommendation">Protect the first Hanoi night, one countryside or bay logistics night, one hard transfer day, and at least one slower central Vietnam day.</td></tr>
<tr><td data-label="Decision"><strong>Cut first</strong></td><td data-label="VietnamGuide recommendation">Remove Mekong, Phu Quoc, Sapa, Ha Giang, or the weaker of Hue/Ninh Binh before compressing every morning.</td></tr>
<tr><td data-label="Decision"><strong>Do not use this route if</strong></td><td data-label="VietnamGuide recommendation">Your main goal is southern Vietnam, beaches, nightlife, remote mountains, or a low-movement family holiday. Choose a focused route instead.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-itinerary-10day-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Ten days by chapter, not by map coverage</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A good 10-day route should feel like a composed sequence: arrive well in Hanoi, slow down in Ninh Binh, let the bay carry one big landscape moment, then finish in central Vietnam with enough time for Hue, Hoi An, or a calmer version of both.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-travel-guide-regional-photo-grid vg-itinerary-10day-photo-grid" aria-label="Route photography for a 10-day Vietnam itinerary">
<figure class="vg-guide-photo"><img src="{$hanoi_image}" alt="Hoan Kiem Lake in central Hanoi, Vietnam" loading="lazy" decoding="async"><figcaption>Hanoi should be the route's orientation chapter, not just the airport before a rushed countryside transfer. Image: <a href="{$hanoi_credit_url}" target="_blank" rel="license noopener">Alex 69200 vx / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$trang_an_image}" alt="Boats and limestone scenery at Trang An in Ninh Binh, northern Vietnam" loading="lazy" decoding="async"><figcaption>Ninh Binh earns its place when it gives the itinerary a countryside reset instead of another late-night hotel change. Image: <a href="{$trang_an_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$guide_hero_image}" alt="Cruise boats among limestone karsts in Ha Long Bay, Vietnam" loading="lazy" decoding="async"><figcaption>The bay night should be treated as its own logistics module: transfer, weather, cabin, route, and return timing all matter. Image: <a href="{$bay_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hue_image}" alt="Historic architecture inside the Hue Citadel in central Vietnam" loading="lazy" decoding="async"><figcaption>Hue is worth keeping when heritage matters enough to protect a real central Vietnam day. Image: <a href="{$hue_credit_url}" target="_blank" rel="license noopener">CEphoto, Uwe Aranas / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hoi_an_image}" alt="Evening street scene in Hoi An Ancient Town, Vietnam" loading="lazy" decoding="async"><figcaption>Hoi An is the route's slower middle; if this day disappears, the itinerary often starts to feel like transport with meals between. Image: <a href="{$hoi_an_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-itinerary-10day-planning-flow:v1 -->
<!-- wp:group {"className":"vg-travel-guide-flow vg-itinerary-10day-planning-flow","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-travel-guide-flow vg-itinerary-10day-planning-flow">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">10-day planning flow</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The five decisions that decide whether ten days works</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this before choosing hotels. It keeps the route from becoming a list of famous names connected by tired transfer days.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<ol class="vg-travel-flow-list" aria-label="10-day Vietnam route planning order">
<li><span>01</span><strong>Flight shape</strong><p>Check whether you can arrive Hanoi and leave Da Nang. A clean exit can save more value than adding another stop.</p></li>
<li><span>02</span><strong>Region count</strong><p>Choose two strong regions by default. Add the south only when flight logic or personal priority justifies the movement.</p></li>
<li><span>03</span><strong>Night allocation</strong><p>Spend nights where they change the trip: arrival recovery, countryside depth, bay logistics, and central Vietnam pace.</p></li>
<li><span>04</span><strong>Transfer pressure</strong><p>Name the hardest movement day before booking. In this route, bay return plus central transfer is the stress point.</p></li>
<li><span>05</span><strong>Cut list</strong><p>Remove one famous add-on before compressing every morning. The skipped stop is often what saves the trip.</p></li>
</ol>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-itinerary-10day-route-family:v1 -->
<!-- wp:html -->
<div class="vg-travel-route-family vg-itinerary-10day-route-family" aria-label="10-day Vietnam route family comparison">
<article><p class="vg-route-family-label">Best default</p><h3>North plus central</h3><p>Hanoi, Ninh Binh, one bay night, Hue or Hoi An, and a Da Nang exit. This protects variety without pretending ten days is two weeks.</p><strong>Most balanced</strong></article>
<article><p class="vg-route-family-label">Open-jaw sampler</p><h3>North to south</h3><p>Use this only when flights genuinely save time and Ho Chi Minh City is a real chapter, not a city squeezed onto departure day.</p><strong>Higher movement</strong></article>
<article><p class="vg-route-family-label">Calmer route</p><h3>Central slow</h3><p>Da Nang, Hoi An, Hue, countryside, food, and rest. Better for families, couples, and travelers who want fewer hard logistics.</p><strong>Comfort first</strong></article>
<article><p class="vg-route-family-label">Landscape route</p><h3>North scenery</h3><p>Hanoi, Ninh Binh, bay, and one northern extension. Strong when landscapes matter more than a full-country introduction.</p><strong>Depth over coverage</strong></article>
</div>
<!-- /wp:html -->

<!-- vg-itinerary-10day-southern-proof:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">If the south matters, make it a real chapter</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ho Chi Minh City and the Mekong Delta are not weak choices. They become weak only when they are hidden inside a 10-day route that already needs Hanoi, countryside, bay logistics, and central Vietnam. If southern Vietnam is the reason for the trip, use a north-to-south route and remove something with honesty.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-itinerary-10day-southern-proof" aria-label="Southern Vietnam route trade-off photography">
<figure class="vg-guide-photo"><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall and Nguyen Hue walking street at night in Ho Chi Minh City, Vietnam" loading="lazy" decoding="async"><figcaption>Ho Chi Minh City works when it gets city time, food, history, and a clean airport role. It is not a good use of ten days as a late arrival plus departure stamp. Image: <a href="{$hcmc_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mekong_image}" alt="Small boat on a river in the Mekong Delta near Phong Dien, Vietnam" loading="lazy" decoding="async"><figcaption>The Mekong is strongest when it replaces a weaker stop and gets its own day, not when it is squeezed into the last morning before a flight. Image: <a href="{$mekong_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-itinerary-10day-transfer-feature:v1 -->
<!-- wp:html -->
<div class="vg-guide-photo-feature vg-travel-guide-transfer-feature vg-itinerary-10day-transfer-feature">
<figure><img src="{$hai_van_image}" alt="Mountain and coastal view from Hai Van Pass between Hue and Da Nang, Vietnam" loading="lazy" decoding="async"><figcaption>Hai Van Pass can make the Hue-to-Hoi An move part of the trip when timing, luggage, stops, and weather are planned. Image: <a href="{$hai_van_credit_url}" target="_blank" rel="license noopener">Wolkenkratzer / CC BY-SA 4.0</a>.</figcaption></figure>
<div class="vg-guide-photo-copy"><p class="vg-kicker">Transfer value</p><h2>The hard day should earn its place.</h2><p>In a 10-day route, the most fragile day is usually the handoff: bay return, airport or rail timing, then central Vietnam. Do not hide that pressure inside a beautiful itinerary map. Name it, buffer it, and make the next stop worth the movement.</p><p>When the route reaches central Vietnam, the Hue-to-Hoi An transfer can add scenery and context. If weather or fatigue makes it only functional, spend the money on a smoother move and protect the Hoi An day instead.</p></div>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">The route we would choose first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>If this is your first Vietnam trip and you have about ten days on the ground, build the trip around two strong regions. North Vietnam gives you Hanoi, countryside, and limestone-bay scenery. Central Vietnam gives you imperial history, Hoi An, coastal food, and an easier final airport if Da Nang flights work for you.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>The temptation is to add Ho Chi Minh City and the Mekong Delta because they are famous and easy to find on a map. The problem is not whether they are worthwhile. They are. The problem is that ten days leaves very little margin once you count arrival fatigue, airport transfers, hotel changes, weather checks, and any cruise or rail timing.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-route-matrix">
<thead>
<tr><th>10-day route family</th><th>Best for</th><th>What to keep</th><th>What to cut</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="10-day route family">Best default: north plus central</td><td data-label="Best for">First-time visitors who want balance without turning the trip into airport logistics.</td><td data-label="What to keep">Hanoi, Ninh Binh, Lan Ha or Ha Long Bay, Hue, Hoi An, Da Nang departure.</td><td data-label="What to cut">Ho Chi Minh City, Mekong, Sapa, Phu Quoc, and any extra one-night city stop.</td><td data-label="VietnamGuide verdict">The cleanest first itinerary for most international travelers.</td></tr>
<tr><td data-label="10-day route family">Classic north-to-south sampler</td><td data-label="Best for">Travelers with open-jaw flights into Hanoi and out of Ho Chi Minh City, or the reverse.</td><td data-label="What to keep">Hanoi, one bay or countryside module, Hoi An or Hue, Ho Chi Minh City.</td><td data-label="What to cut">Either Ninh Binh, Hue, or the Mekong unless you accept a faster trip.</td><td data-label="VietnamGuide verdict">Works only when flight routing saves time; otherwise it feels thinner than it looks.</td></tr>
<tr><td data-label="10-day route family">Central Vietnam slow route</td><td data-label="Best for">Food, heritage, calmer hotels, beach-adjacent time, and fewer intercity jumps.</td><td data-label="What to keep">Da Nang, Hoi An, Hue, countryside, a flexible beach or spa day.</td><td data-label="What to cut">Northern mountains, bay cruise, and southern add-ons.</td><td data-label="VietnamGuide verdict">Often better for families, couples, and premium travelers who value pace over coverage.</td></tr>
<tr><td data-label="10-day route family">North Vietnam scenery route</td><td data-label="Best for">Travelers who care most about Hanoi, landscapes, food, and cooler northern pacing.</td><td data-label="What to keep">Hanoi, Ninh Binh, Lan Ha or Ha Long Bay, one mountain or countryside extension.</td><td data-label="What to cut">Central and southern Vietnam.</td><td data-label="VietnamGuide verdict">Better than forcing Sapa or Ha Giang into a cross-country trip.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">A practical 10-day Vietnam itinerary</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This plan assumes a Hanoi arrival and a Da Nang departure. If you must return to Hanoi for your international flight, keep the final day conservative and do not place a long transfer before a non-refundable departure.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-timeline vg-itinerary-day-plan">
<div class="vg-timeline-item"><div class="vg-day">Day 1</div><div><h3>Arrive in Hanoi</h3><p>Stay central, keep dinner simple, and avoid scheduling a paid tour on arrival night. The first win is arriving safely and sleeping well.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 2</div><div><h3>Hanoi food, history, and walking pace</h3><p>Use this as the main Hanoi day: old streets, a museum or temple, a guided food walk if useful, and enough cafe time to recover from long-haul travel.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 3</div><div><h3>Ninh Binh countryside</h3><p>Go to Ninh Binh as an overnight if you want calmer mornings and evenings. Make it a day trip only if you need fewer hotel changes.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 4</div><div><h3>Ninh Binh depth, then position for the bay</h3><p>Choose one major landscape experience rather than stacking every viewpoint. Travel toward Hanoi or the bay gateway depending on your cruise transfer plan.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 5</div><div><h3>Lan Ha or Ha Long Bay overnight</h3><p>Treat the cruise as a separate logistics module. Check pickup point, transfer inclusions, weather policy, cabin type, and whether the itinerary suits your energy level.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 6</div><div><h3>Bay return and central Vietnam transfer</h3><p>This is the day most itineraries under-plan. Return from the bay, allow road and airport margin, then fly or continue toward Hue or Da Nang without adding sightseeing pressure.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 7</div><div><h3>Hue or a slower central landing</h3><p>Choose Hue if history matters. If you want a calmer route, skip Hue and spend this night in Hoi An or Da Nang instead.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 8</div><div><h3>Hai Van Pass to Hoi An</h3><p>Move to Hoi An by a route that lets the transfer earn its place. Do not turn this into a rushed checklist if weather, traffic, or luggage make it less pleasant.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 9</div><div><h3>Hoi An slow day</h3><p>Use Hoi An for food, tailoring, architecture, countryside, beach-adjacent rest, or a guided context walk. This is the day that keeps the trip from feeling over-engineered.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 10</div><div><h3>Depart from Da Nang</h3><p>Leave enough margin for the airport and avoid placing a must-do tour on departure day. If your flight is late, use Da Nang or Hoi An gently rather than adding a distant excursion.</p></div></div>
</div>
<!-- /wp:html -->

<!-- vg-itinerary-10day-season-pivots:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Season pivots for a 10-day route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ten days leaves less room to absorb bad routing. Use seasonal guidance to choose the route bias, then verify current forecasts and operator policies before paying for anything that depends on water, mountains, beaches, or tight transfers.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-10day-season-pivots">
<thead>
<tr><th>Planning season</th><th>Safer 10-day bias</th><th>What to protect</th><th>What not to pretend</th></tr>
</thead>
<tbody>
<tr><td data-label="Planning season">January-February</td><td data-label="Safer 10-day bias">North plus central can work, but keep warmer layers for the north and protect holiday flexibility around Tet.</td><td data-label="What to protect">Arrival recovery, bay weather policy, and hotel cancellation terms.</td><td data-label="What not to pretend">That every restaurant, transfer, or small operator behaves normally around major holidays.</td></tr>
<tr><td data-label="Planning season">March-April</td><td data-label="Safer 10-day bias">The default north-plus-central route is often at its easiest if flights into Hanoi and out of Da Nang cooperate.</td><td data-label="What to protect">Bay quality, Ninh Binh depth, and a real Hoi An day.</td><td data-label="What not to pretend">That good weather makes a third region free.</td></tr>
<tr><td data-label="Planning season">May-August</td><td data-label="Safer 10-day bias">Use slower mornings, better hotel locations, and fewer hard sightseeing stacks because heat and rain can change energy.</td><td data-label="What to protect">Midday rest, private transfer value, and flexible central Vietnam choices.</td><td data-label="What not to pretend">That a packed itinerary will feel comfortable because the map distances look manageable.</td></tr>
<tr><td data-label="Planning season">September-November</td><td data-label="Safer 10-day bias">Be more cautious with central-coast exposure; north-heavy or south-shifted routes can be wiser depending on exact dates.</td><td data-label="What to protect">Hoi An/Hue flexibility, bay cancellation terms, and alternate city/countryside days.</td><td data-label="What not to pretend">That a beach-first central plan is guaranteed in late-year weather.</td></tr>
<tr><td data-label="Planning season">December</td><td data-label="Safer 10-day bias">A balanced route can work, but pricing, holiday demand, and regional variation matter more than the month label.</td><td data-label="What to protect">Good hotel locations, early reservations where needed, and final-night logistics.</td><td data-label="What not to pretend">That late-December travel is only a weather question.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip, swap, or slow down</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The quality of a 10-day itinerary usually comes from what you remove. These are the swaps that prevent a strong route from becoming a fragile one.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-swap-skip">
<thead>
<tr><th>Pressure point</th><th>Better move</th><th>What not to force</th><th>Why it improves the trip</th></tr>
</thead>
<tbody>
<tr><td data-label="Pressure point">You want Ho Chi Minh City too</td><td data-label="Better move">Use open-jaw flights and remove Hue or Ninh Binh.</td><td data-label="What not to force">Hanoi, Ninh Binh, bay cruise, Hue, Hoi An, Ho Chi Minh City, and Mekong.</td><td data-label="Why it improves the trip">The route becomes honest about transfer cost and energy.</td></tr>
<tr><td data-label="Pressure point">Central coast weather looks weak</td><td data-label="Better move">Keep Hanoi, Ninh Binh, and the bay, then choose Hue/Hoi An flexibly or shift south if flights make sense.</td><td data-label="What not to force">A beach-first Hoi An plan in a poor weather window.</td><td data-label="Why it improves the trip">The itinerary responds to regional weather instead of pretending Vietnam has one climate.</td></tr>
<tr><td data-label="Pressure point">You are traveling with children or older relatives</td><td data-label="Better move">Make Ninh Binh a day trip or remove Hue, then pay for better transfers.</td><td data-label="What not to force">A new hotel every night.</td><td data-label="Why it improves the trip">Shared private transport can be better value than saving money and losing rest.</td></tr>
<tr><td data-label="Pressure point">You want a premium trip</td><td data-label="Better move">Upgrade fewer moments: one excellent guide, one stronger cruise, one better hotel location.</td><td data-label="What not to force">More cities because the budget allows it.</td><td data-label="Why it improves the trip">Premium Vietnam often means better timing and fewer dead hours.</td></tr>
<tr><td data-label="Pressure point">Budget feels high</td><td data-label="Better move">Reduce bases, avoid extra domestic flights, and compare rail only where the timing genuinely helps.</td><td data-label="What not to force">Cheap headline fares that add baggage, late transfers, or lost sightseeing time.</td><td data-label="Why it improves the trip">A simpler route protects both cost and experience.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-itinerary-10day-audience-adaptations:v1 -->
<!-- vg-itinerary-10day-traveler-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Adapt the route to the traveler, not the map</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The same 10-day outline should not be sold to every traveler. A premium couple, a family with children, a solo traveler, and a budget backpacker can all use Vietnam well, but they should not buy the same number of transfers.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-10day-audience-adaptations">
<thead>
<tr><th>Traveler type</th><th>Best route adjustment</th><th>Worth spending on</th><th>What to remove first</th></tr>
</thead>
<tbody>
<tr><td data-label="Traveler type">First-time couple</td><td data-label="Best route adjustment">Keep the default route but protect one slower Hoi An or Hanoi night.</td><td data-label="Worth spending on">A better-located hotel, one food/history guide, and a smoother central transfer.</td><td data-label="What to remove first">A rushed third region or a same-day Mekong add-on.</td></tr>
<tr><td data-label="Traveler type">Family with children</td><td data-label="Best route adjustment">Use fewer hotel changes; make Ninh Binh a day trip if overnight logistics are too much.</td><td data-label="Worth spending on">Private transfers, larger rooms, early check-in where possible, and simpler meal locations.</td><td data-label="What to remove first">Late-night arrivals, one-night stops, and long days after a cruise return.</td></tr>
<tr><td data-label="Traveler type">Older travelers</td><td data-label="Best route adjustment">Choose two or three bases and reduce stairs, heat exposure, and luggage friction.</td><td data-label="Worth spending on">Door-to-door transfers, better hotel lifts/location, and private context guiding.</td><td data-label="What to remove first">DIY transfer chains and tightly timed rail/flight connections.</td></tr>
<tr><td data-label="Traveler type">Solo traveler</td><td data-label="Best route adjustment">Keep the route social in Hanoi, Ninh Binh, bay, and Hoi An, but avoid isolated late arrivals.</td><td data-label="Worth spending on">Safe arrival transport, group food walks, and reputable shared day tours.</td><td data-label="What to remove first">Remote add-ons that create lonely transfer evenings.</td></tr>
<tr><td data-label="Traveler type">Premium/private trip</td><td data-label="Best route adjustment">Use the same route shape, but make fewer moments deeper rather than adding more cities.</td><td data-label="Worth spending on">A stronger bay cruise, private heritage guide, and boutique hotels in walkable locations.</td><td data-label="What to remove first">Extra flights added only because the budget allows them.</td></tr>
<tr><td data-label="Traveler type">Budget traveler</td><td data-label="Best route adjustment">Reduce bases and compare train/bus timing only after adding door-to-door reality.</td><td data-label="Worth spending on">One high-value landscape experience and safe arrival transport.</td><td data-label="What to remove first">Cheap flights that become expensive after bags, taxis, and lost time.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-itinerary-10day-prebook-flex:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Prebook, live-check, or keep flexible?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A 10-day itinerary is short enough that one weak booking can distort the whole route. Prebook the pieces that protect the spine, live-check anything volatile, and keep ordinary city time flexible.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-10day-prebook-flex">
<thead>
<tr><th>Decision</th><th>Prebook when...</th><th>Live-check close to travel when...</th><th>Keep flexible when...</th></tr>
</thead>
<tbody>
<tr><td data-label="Decision">Entry and first hotel</td><td data-label="Prebook when...">International arrival is late, family rooms matter, or the first night anchors the route.</td><td data-label="Live-check close to travel when...">Visa processing, flight time, or airport transfer assumptions change.</td><td data-label="Keep flexible when...">You already have a refundable first night and no same-night onward travel.</td></tr>
<tr><td data-label="Decision">Bay cruise</td><td data-label="Prebook when...">Cabin type, operator quality, transfer inclusions, or group size matter.</td><td data-label="Live-check close to travel when...">Weather, port logistics, and cancellation terms could change the value.</td><td data-label="Keep flexible when...">A day trip or Ninh Binh depth would satisfy the landscape goal if conditions weaken.</td></tr>
<tr><td data-label="Decision">Ninh Binh</td><td data-label="Prebook when...">You want an overnight countryside base or a private transfer chain linked to the bay.</td><td data-label="Live-check close to travel when...">Rain, heat, or arrival fatigue could make a full outdoor day less valuable.</td><td data-label="Keep flexible when...">A day trip is enough and hotel changes are already high.</td></tr>
<tr><td data-label="Decision">Central transfer</td><td data-label="Prebook when...">The route depends on a specific flight, train, or Hue-to-Hoi An private car.</td><td data-label="Live-check close to travel when...">Baggage, airport time, rail seats, or road weather could erase the convenient option.</td><td data-label="Keep flexible when...">Hue and Hoi An order can change without hurting hotels.</td></tr>
<tr><td data-label="Decision">Guides and experiences</td><td data-label="Prebook when...">A guide changes the trip: food, heritage, craft, photography, or family logistics.</td><td data-label="Live-check close to travel when...">Weather or opening hours could make the plan less useful.</td><td data-label="Keep flexible when...">The city is strong enough for self-guided wandering and meals.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-itinerary-10day-mistakes:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Short answers for common 10-day mistakes</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>These are the decisions that usually separate a satisfying 10-day Vietnam route from a trip that only looks complete on paper.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-faq-list vg-travel-guide-mistakes vg-itinerary-10day-mistakes">
<details><summary>Should I include Ho Chi Minh City in ten days?</summary><p>Only if your flights make the north-to-south line efficient or southern Vietnam is a genuine priority. If Ho Chi Minh City is just there because every route map includes it, cut it and make the north-plus-central route better.</p></details>
<details><summary>Is Ninh Binh or a bay cruise more important?</summary><p>For most first trips, keep both if you can protect the timing: Ninh Binh gives countryside depth, while the bay gives the headline landscape moment. If you must cut one, cut the one that creates the weaker logistics on your exact dates.</p></details>
<details><summary>Should Hue be kept or skipped?</summary><p>Keep Hue when heritage, food, and a more meaningful central chapter matter. Skip it when the route is already tight, the family pace is slower, or Hoi An needs the protected day more.</p></details>
<details><summary>What live checks matter most before paying?</summary><p>Check e-visa/entry status, regional weather, bay cruise transfer terms, domestic flight or rail times, hotel cancellation terms, and whether Da Nang departure really saves time.</p></details>
</div>
<!-- /wp:html -->

<!-- vg-itinerary-10day-booking-sequence:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Booking sequence for ten days</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Book in this order so the route does not get trapped by one attractive hotel, cheap domestic fare, or cruise date that makes the rest of the trip worse.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-itinerary-10day-booking-sequence"} -->
<ul class="wp-block-list vg-check-list vg-itinerary-10day-booking-sequence">
<li>Confirm entry permission and passport details first; use the <a href="/plan/vietnam-evisa/">Vietnam E-Visa guide</a> before paying for non-refundable travel.</li>
<li>Choose the route family: default north plus central, open-jaw north-to-south, central slow route, or north scenery route.</li>
<li>Check the month against the <a href="/plan/best-time-to-visit-vietnam/">Best Time guide</a>, then decide whether central Vietnam, the bay, or the south needs more flexibility.</li>
<li>Lock international flights only after checking whether Hanoi arrival and Da Nang departure save a real day.</li>
<li>Hold the first two nights, the bay/countryside sequence, and the final departure base before filling optional experiences.</li>
<li>Price the route with the <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost guide</a> before upgrading hotels or adding a third region.</li>
<li>Re-run the audit below before making the trip non-refundable.</li>
</ul>
<!-- /wp:list -->

<!-- vg-itinerary-10day-planning-audit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The 10-day planning audit</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>If an itinerary fails two or more lines in this audit, cut a stop before you spend more money trying to make it work.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-10day-planning-audit">
<thead>
<tr><th>Audit line</th><th>Healthy answer</th><th>Warning sign</th><th>Best correction</th></tr>
</thead>
<tbody>
<tr><td data-label="Audit line">First night</td><td data-label="Healthy answer">Central Hanoi hotel, simple dinner, no paid arrival-night tour.</td><td data-label="Warning sign">Late arrival plus an early countryside transfer.</td><td data-label="Best correction">Add Hanoi recovery or move Ninh Binh later.</td></tr>
<tr><td data-label="Audit line">Region count</td><td data-label="Healthy answer">Two strong regions by default.</td><td data-label="Warning sign">North, central, south, Mekong, and beach all included.</td><td data-label="Best correction">Choose the route family again and remove the weakest chapter.</td></tr>
<tr><td data-label="Audit line">Bay/countryside logic</td><td data-label="Healthy answer">Ninh Binh and bay both have a clear job, or one is intentionally cut.</td><td data-label="Warning sign">Both are included but neither gets enough time.</td><td data-label="Best correction">Keep the one that better fits weather, budget, and transfer order.</td></tr>
<tr><td data-label="Audit line">Hardest transfer</td><td data-label="Healthy answer">The bay-return or central-transfer day has buffer and no fragile sightseeing.</td><td data-label="Warning sign">Cruise return, flight, hotel change, and a must-do tour on one day.</td><td data-label="Best correction">Remove the tour or add a night before central Vietnam.</td></tr>
<tr><td data-label="Audit line">Final day</td><td data-label="Healthy answer">Departure base is close enough to the airport with a conservative plan.</td><td data-label="Warning sign">A distant excursion before an international flight.</td><td data-label="Best correction">Use Da Nang/Hoi An gently or depart the next morning.</td></tr>
<tr><td data-label="Audit line">Budget reality</td><td data-label="Healthy answer">Flights, trains, cars, cruise, guides, visa/admin, and buffer are listed separately.</td><td data-label="Warning sign">Everything is hidden inside a daily average.</td><td data-label="Best correction">Use line items and remove the costliest weak movement.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Before-booking checks</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this list before paying for non-refundable hotels, domestic transport, or a bay cruise. The guide is built as a planning framework, not a promise that every route detail will stay available on your dates.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-itinerary-booking-checks"} -->
<ul class="wp-block-list vg-check-list vg-itinerary-booking-checks">
<li>Confirm your entry rules and passport details before locking the route.</li>
<li>Check whether open-jaw flights into Hanoi and out of Da Nang or Ho Chi Minh City save a full travel day.</li>
<li>Price the route as land-only first, then compare with the <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost guide</a>.</li>
<li>Verify regional weather separately with the <a href="/plan/best-time-to-visit-vietnam/">Best Time guide</a>; northern, central, and southern Vietnam can behave differently.</li>
<li>Check bay cruise transfer inclusions, cancellation rules, port logistics, and weather policy.</li>
<li>Compare domestic flight timing with rail only after adding door-to-door transfer time, baggage, and hotel check-in reality.</li>
<li>Keep at least one flexible half-day before international departure or any high-value guided experience.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">How to connect this with the rest of VietnamGuide</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the <a href="/itineraries/">Itineraries hub</a> if you are choosing between 7, 10, 14, and slower routes. Use <a href="/compare/">Compare</a> when you are deciding whether two similar places both deserve space. Use the <a href="/plan/">Plan hub</a> for entry, timing, safety, and logistics checks before the route becomes final.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

vg_ops_assert_internal_page_links_are_published('10 Days in Vietnam guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => '10 Days in Vietnam',
    'post_name'      => '10-days-in-vietnam',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led 10-day Vietnam itinerary for international visitors, with route order, day-by-day pacing, swap decisions, booking checks, and source trail.',
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
    vg_ops_fail('Could not publish 10 Days itinerary guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish 10 Days itinerary guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', '10 Days in Vietnam: Best Route for First-Time Visitors');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led 10-day Vietnam itinerary for international visitors, with route order, photo proof, season pivots, traveler variants, booking sequence, and source trail.');
update_post_meta($page_id, 'rank_math_focus_keyword', '10 days in Vietnam');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Choose the strongest 10-day Vietnam route before booking hotels, domestic transport, or a bay cruise.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 16, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Expanded the 10 Days in Vietnam guide with southern-route proof photography, season pivots, traveler-type adaptations, prebook-versus-flexible guidance, a booking sequence, and a planning audit.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 16, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 16, 2026\nVietnam Railways - official rail fare lookup and booking reference - https://dsvn.vn/ - checked July 16, 2026\nVietnam Airlines - Hanoi airport to city guide - https://www.vietnamairlines.com/us/en/plan-book/travel/travel-guide/hanoi-airport-to-hanoi-city - checked July 16, 2026\nVietnam Airlines - Ho Chi Minh City airport to city guide - https://www.vietnamairlines.com/ca/en/plan-book/travel/travel-guide/airport-ho-chi-minh-to-city - checked July 16, 2026\nVietnam National Electronic Visa system - official e-visa portal for outside Viet Nam foreigners - https://evisa.gov.vn/e-visa/foreigners - checked July 16, 2026\nWikimedia Commons image record - Ha Long Bay, Vietnam, view from above - https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg - license checked July 16, 2026\nWikimedia Commons image record - Hanoi Hoan Kiem Lake - https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg - license checked July 16, 2026\nWikimedia Commons image record - Trang An Landscape Complex, Ninh Binh Province - https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg - license checked July 16, 2026\nWikimedia Commons image record - Hue Vietnam Citadel - https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg - license checked July 16, 2026\nWikimedia Commons image record - Hoi An Ancient Town - https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg - license checked July 16, 2026\nWikimedia Commons image record - Vietnam, Hai-Van-Pass - https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg - license checked July 16, 2026\nWikimedia Commons image record - Ho Chi Minh City Hall - https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg - license checked July 16, 2026\nWikimedia Commons image record - Vietnam, Phong Dien, Mekong Delta, River - https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg - license checked July 16, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'In ten days, remove one famous stop before adding one difficult transfer.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Original route-family matrix and visual route-family comparison that explain who should use each 10-day route.\nPhoto-led route-chapter framework that shows Hanoi, Ninh Binh, Hue, Hoi An, and Hai Van as planning decisions, not decorative stops.\nSouthern-route proof photography that explains why Ho Chi Minh City and the Mekong should be real chapters or cut honestly.\nFive-step planning flow for flight shape, region count, night allocation, transfer pressure, and cut-list discipline.\nDay-by-day pacing plan that names transfer risk instead of hiding it.\nTransfer-value feature that explains when Hai Van Pass and the central Vietnam handoff earn their place.\nSeason-pivot table for Jan-Feb, Mar-Apr, May-Aug, Sep-Nov, and December route bias.\nTraveler-type adaptation matrix for couples, families, older travelers, solo travelers, premium/private trips, and budget travelers.\nSwap/skip, prebook-versus-flexible, booking-sequence, and planning-audit frameworks that add original booking judgment.\nMistake checks for Ho Chi Minh City, Ninh Binh versus bay, Hue, and live verification before payment.\nRelated decision chain for weather, cost, and longer-route planning.\nBefore-booking checklist tied to official entry, weather, transport, rail, and airport source checks.\nLicensed route photography with visible credit links.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check whether the north-plus-central route suits your dates.\nBest Things to Do in Hue | /destinations/best-things-to-do-in-hue/ | Use this before deciding whether Hue or Hoi An gets the protected central Vietnam day.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price transport, cruise, and hotel decisions before booking.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use this if the shortlist needs more margin.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and body bay image: Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0. Body images: Hanoi by Alex 69200 vx, CC BY-SA 4.0; Trang An by Jakub Halun, CC BY 4.0; Hue by CEphoto, Uwe Aranas, CC BY-SA 3.0; Hoi An by Steffen Schmitz, CC BY-SA 4.0; Hai Van Pass by Wolkenkratzer, CC BY-SA 4.0; Ho Chi Minh City by Steffen Schmitz, CC BY-SA 4.0; Mekong Delta by Vyacheslav Argenberg, CC BY 4.0.');
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
    vg_ops_fail('10 Days itinerary guide was updated but is not published.');
}

vg_ops_refresh_itineraries_hub();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published 10 Days in Vietnam itinerary guide: {$page_id} {$updated_permalink}");

<?php
/**
 * Publish the 14 Days in Vietnam guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-14-days-itinerary-guide.php --allow-root
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
    $value = getenv('VG_FORCE_14_DAY_ITINERARY_REPUBLISH');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the 14 Days itinerary guide.');
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

function vg_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
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

    $marker = '<!-- vg-itinerary-14-day-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use 14 days to slow the route, not just add stops</h3><p>The <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam guide</a> helps first-time visitors choose between a balanced north-central-south route and a slower two-region route with better weather and transfer buffers.</p></div>
<!-- /wp:group -->
HTML;

    $marked_block = $marker . "\n" . $block;

    if (str_contains($hub->post_content, $marked_block)) {
        vg_ops_log('Skipped Itineraries hub refresh: current 14-day note already present.');
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
            vg_ops_fail('Could not confidently refresh the Itineraries hub 14-day note.');
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
        vg_ops_fail('Could not refresh Itineraries hub 14-day note: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Itineraries hub 14-day note: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Itineraries hub 14-day note: {$hub->ID}");
}

$parent = get_page_by_path('itineraries', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: itineraries');
}

$page = get_page_by_path('itineraries/14-days-in-vietnam', OBJECT, 'page');
$allow_republish = vg_ops_force_republish_enabled();

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('14 Days itinerary guide is not a draft. Set VG_FORCE_14_DAY_ITINERARY_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    $permalink = get_permalink($page);
    vg_ops_log("Preflight 14 Days itinerary guide: {$page->ID} {$page->post_status} {$permalink}");
} else {
    vg_ops_log('Preflight 14 Days itinerary guide: no existing page found; creating a child page under /itineraries/.');
}

$guide_hero_image = esc_url(set_url_scheme(get_stylesheet_directory_uri(), 'https') . '/assets/images/ha-long-bay-vietnam-hero.jpg');
$trang_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');
$trang_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg');
$hoi_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hoi_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg');
$hue_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg/1920px-Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$hue_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$mekong_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1920px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg');
$mekong_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg');
$hanoi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$hanoi_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg');
$hai_van_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1920px-Vietnam%2C_Hai-Van-Pass.jpg');
$hai_van_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg');
$hcmc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg');
$hcmc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg');
$son_river_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f1/Son_River-Quang_Binh_province.jpg/1920px-Son_River-Quang_Binh_province.jpg');
$son_river_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Son_River-Quang_Binh_province.jpg');

$content = <<<HTML
<!-- vg-itinerary-14day-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$guide_hero_image}","dimRatio":50,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Limestone islands in Ha Long Bay, Vietnam, used for a 14-day Vietnam itinerary guide" src="{$guide_hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed itinerary - Updated July 25, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">14 Days in Vietnam: Best Two-Week Route by Month and Travel Style</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Two weeks is enough for a graceful Vietnam route if you protect the transfer days. If you only need one safe first-trip answer, use Hanoi, Ninh Binh, a bay overnight, Hue, Hoi An or Da Nang, Ho Chi Minh City, then add only one extension. The best 14-day Vietnam itinerary is not the 10-day route with four extra pins; it is a route that uses the extra time for fewer rushed mornings, better weather choices, and one carefully chosen contrast.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: This is not a maximum-coverage itinerary. It is a two-week decision guide: the right route is the one that still feels good after weather, airport time, hotel changes, luggage, children, budget, and the final departure are counted.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<p class="vg-image-credit">Hero image: Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0.</p>
<!-- /wp:html -->
</div>
</div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-itinerary-14day-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-itinerary-14day-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Most first-time visitors should use 14 days for Hanoi, Ninh Binh, a bay overnight, Hue, Hoi An or Da Nang, Ho Chi Minh City, and one southern or central extension.</strong> Choose only one extra module: Mekong, Phong Nha, beach time, mountains, or a slower central stay. The mistake is treating two weeks as permission to add every famous place.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><strong>Best for:</strong> international travelers who want a complete first Vietnam route without rushing every morning.</li>
<li><strong>Avoid if:</strong> you prefer one-region depth, remote mountains, beach-heavy travel, or a trip with almost no domestic flights.</li>
<li><strong>Verify before booking:</strong> open-jaw international flights, regional weather, bay cruise transfer policy, rail or flight timing, and the exact number of nights you are willing to change hotels.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-itinerary-14day-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-itinerary-14day-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-itinerary-14day-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Route brief</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">14-day Vietnam itinerary at a glance</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-14day-glance-table">
<tbody>
<tr><td data-label="Decision"><strong>Best default route</strong></td><td data-label="VietnamGuide recommendation">Hanoi, Ninh Binh, Ha Long or Lan Ha Bay, Hue, Hoi An or Da Nang, Ho Chi Minh City, and one deliberate extension only if it replaces a weaker day.</td></tr>
<tr><td data-label="Decision"><strong>Best flight shape</strong></td><td data-label="VietnamGuide recommendation">Open-jaw is usually cleaner: arrive in Hanoi and depart from Ho Chi Minh City or Da Nang if the fare difference is reasonable.</td></tr>
<tr><td data-label="Decision"><strong>Best use of extra days</strong></td><td data-label="VietnamGuide recommendation">Buy margin, a slower central middle, one countryside overnight, or one beach/river/cave extension. Do not buy three extra transfer days.</td></tr>
<tr><td data-label="Decision"><strong>Cut first</strong></td><td data-label="VietnamGuide recommendation">Remove duplicate one-night stops, a second scenery module, or a fragile final-day excursion before cutting the route's core.</td></tr>
<tr><td data-label="Decision"><strong>Do not use this route if</strong></td><td data-label="VietnamGuide recommendation">You want a beach holiday, a north-only scenery trip, or a route with almost no domestic transfer risk.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-itinerary-14day-source-diversity:v1 -->
<!-- wp:group {"className":"vg-source-diversity vg-itinerary-14day-source-diversity","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-source-diversity vg-itinerary-14day-source-diversity">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Source discipline</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What sources can and cannot prove</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Sources can confirm official destination context, broad weather and transport patterns, heritage status, rail and entry references, and image-license records; they cannot decide whether your two weeks should buy coverage, comfort, fewer hotel changes, or a cleaner skip. This guide uses those sources as constraints, then makes an editorial route decision from the traveler's calendar, season, flight shape, and tolerance for movement.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- vg-itinerary-14day-source-trail-snapshot:v1 -->
<!-- wp:group {"className":"vg-source-snapshot vg-itinerary-14day-source-trail-snapshot","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-source-snapshot vg-itinerary-14day-source-trail-snapshot">
<!-- wp:heading -->
<h2 class="wp-block-heading">Source trail snapshot for this two-week route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Core planning sources checked: Vietnam.travel planning - https://vietnam.travel/plan-your-trip; weather - https://vietnam.travel/things-to-do/weather-and-climate-vietnam; transport - https://vietnam.travel/plan-your-trip/transport-within-vietnam; UNESCO Ha Long Bay - Cat Ba Archipelago - https://whc.unesco.org/en/list/672/; UNESCO Hoi An - https://whc.unesco.org/en/list/948/; UNESCO Hue - https://whc.unesco.org/en/list/678/; UNESCO Trang An - https://whc.unesco.org/en/list/1438/; Vietnam Railways - https://dsvn.vn/. Image credits are listed as text to keep the itinerary readable and reduce visible outbound clutter.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- vg-itinerary-14day-route-chooser:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Start here: which 14-day route should you choose?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The two-week route should start with a job, not a destination list. Choose the route shape that solves the trip's main problem, then remove anything that does not support that job.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-14day-route-chooser">
<thead>
<tr><th>Your real trip job</th><th>Best 14-day route</th><th>What to protect</th><th>What to remove first</th></tr>
</thead>
<tbody>
<tr><td data-label="Your real trip job">First Vietnam trip with open-jaw flights</td><td data-label="Best 14-day route">Hanoi, Ninh Binh, bay, Hue, Hoi An/Da Nang, Ho Chi Minh City, one extension.</td><td data-label="What to protect">Arrival recovery, central Vietnam middle, and a final city buffer.</td><td data-label="What to remove first">A second beach or scenery add-on.</td></tr>
<tr><td data-label="Your real trip job">Premium or honeymoon pace</td><td data-label="Best 14-day route">North plus central Vietnam, with a stronger cruise or one quiet beach chapter.</td><td data-label="What to protect">Better hotels, fewer checkouts, guided context, and downtime that feels intentional.</td><td data-label="What to remove first">A rushed Mekong or one-night southern detour.</td></tr>
<tr><td data-label="Your real trip job">Family-friendly lower friction</td><td data-label="Best 14-day route">Hanoi, one northern landscape choice, Da Nang/Hoi An, Ho Chi Minh City or a simple beach finish.</td><td data-label="What to protect">Pool backup, food access, private transfers on hard days, and earlier flights.</td><td data-label="What to remove first">Back-to-back one-night stops.</td></tr>
<tr><td data-label="Your real trip job">Landscape-first Vietnam</td><td data-label="Best 14-day route">Hanoi, Ninh Binh, bay, one mountain area, then central Vietnam only if the transfer load still works.</td><td data-label="What to protect">Morning light, weather flexibility, driver quality, and remote-route insurance comfort.</td><td data-label="What to remove first">The south, unless city/food history is a core goal.</td></tr>
<tr><td data-label="Your real trip job">Food, heritage, and old towns</td><td data-label="Best 14-day route">Hanoi, Hue, Hoi An, Ho Chi Minh City, with Ninh Binh or the bay as the main scenery contrast.</td><td data-label="What to protect">Guided context, meals, walkable bases, and unhurried central evenings.</td><td data-label="What to remove first">Beach time that requires another flight.</td></tr>
<tr><td data-label="Your real trip job">Beach recovery inside a wider trip</td><td data-label="Best 14-day route">Central coast beach time around Da Nang/Hoi An, or Phu Quoc only if the final flight path supports it.</td><td data-label="What to protect">Weather window, resort location, airport access, and final-departure buffer.</td><td data-label="What to remove first">A cave, mountain, or Mekong add-on that competes for the same extra days.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:html -->
<!-- vg-itinerary-14day-photo-grid:v1 -->
<div class="vg-guide-photo-grid vg-itinerary-14day-photo-grid" aria-label="Route photography for a 14-day Vietnam itinerary">
<figure class="vg-guide-photo"><img src="{$hanoi_image}" alt="Hoan Kiem Lake in central Hanoi, Vietnam" loading="lazy" decoding="async"><figcaption>Hanoi deserves a real arrival day before the route starts asking for energy. Image: Alex 69200 vx, CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$trang_an_image}" alt="Boats and limestone scenery at Trang An in Ninh Binh, northern Vietnam" loading="lazy" decoding="async"><figcaption>Ninh Binh works best as a route-calming landscape stop, not a rushed checklist. Image: Jakub Halun, CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hue_image}" alt="Historic architecture inside the Hue Citadel in central Vietnam" loading="lazy" decoding="async"><figcaption>Hue adds heritage context when the itinerary can protect a real central Vietnam day. Image: CEphoto, Uwe Aranas, CC BY-SA 3.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hai_van_image}" alt="Mountain and coastal view from Hai Van Pass between Hue and Da Nang, Vietnam" loading="lazy" decoding="async"><figcaption>The Hue-to-Hoi An transfer can be part of the trip when timing, luggage, and weather cooperate. Image: Wolkenkratzer, CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hoi_an_image}" alt="Evening street scene in Hoi An Ancient Town, Vietnam" loading="lazy" decoding="async"><figcaption>Hoi An is where a two-week route earns its slower middle. Image: Steffen Schmitz, CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall and Nguyen Hue walking street at night, Vietnam" loading="lazy" decoding="async"><figcaption>Ho Chi Minh City should be planned as a southern chapter, not just a departure airport. Image: Steffen Schmitz, CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mekong_image}" alt="Small boat on a river in the Mekong Delta near Phong Dien, Vietnam" loading="lazy" decoding="async"><figcaption>The Mekong is strongest when it replaces a weak final add-on, not when it is squeezed into departure day. Image: Vyacheslav Argenberg, CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$son_river_image}" alt="Son River landscape in Quang Binh province near Phong Nha, Vietnam" loading="lazy" decoding="async"><figcaption>Phong Nha can be excellent, but it needs to replace something, not hide inside an already full route. Image: BacLuong, CC BY-SA 4.0.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">The decision: complete Vietnam or slower Vietnam?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A 14-day Vietnam itinerary has two good shapes. The first is a balanced north-central-south route: Hanoi, countryside, bay, central heritage, Hoi An, Ho Chi Minh City, and one southern add-on. The second is a slower two-region route that drops the south or drops the far north so the trip feels calmer and more place-specific.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Both can be excellent. The right answer depends on your flights, season, comfort with domestic transfers, and how much you value slow meals and flexible weather days. This guide treats 14 days as a decision framework, not a list of places to collect.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-14day-route-builder">
<thead>
<tr><th>14-day route shape</th><th>Best for</th><th>Core route</th><th>Use the extra time for</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="14-day route shape">Balanced first-trip route</td><td data-label="Best for">Travelers who want north, central, and south in one trip without making every day a move day.</td><td data-label="Core route">Hanoi, Ninh Binh, Ha Long or Lan Ha Bay, Hue, Hoi An, Ho Chi Minh City.</td><td data-label="Use the extra time for">Mekong, one beach rest, or a slower Hoi An/Hue stay.</td><td data-label="VietnamGuide verdict">Best default for most first-time international visitors with open-jaw flights.</td></tr>
<tr><td data-label="14-day route shape">Slower north plus central route</td><td data-label="Best for">Couples, families, photographers, food travelers, and anyone who dislikes airports mid-trip.</td><td data-label="Core route">Hanoi, Ninh Binh, bay, Hue, Da Nang, Hoi An, countryside or beach buffer.</td><td data-label="Use the extra time for">Phong Nha, Hoi An countryside, a better bay cruise, or more recovery time.</td><td data-label="VietnamGuide verdict">Often better than the full-country route when comfort matters more than coverage.</td></tr>
<tr><td data-label="14-day route shape">North scenery route</td><td data-label="Best for">Travelers who care most about landscapes, Hanoi, rural stays, and cooler northern pacing.</td><td data-label="Core route">Hanoi, Ninh Binh, bay, Mai Chau or Pu Luong, Sapa or Ha Giang if logistics fit.</td><td data-label="Use the extra time for">One mountain area, not every mountain area.</td><td data-label="VietnamGuide verdict">Strong for scenery, but not a complete-country introduction.</td></tr>
<tr><td data-label="14-day route shape">Central and south route</td><td data-label="Best for">Beach-adjacent travel, food, heritage, warmer weather windows, and easier southern exits.</td><td data-label="Core route">Da Nang, Hoi An, Hue, Ho Chi Minh City, Mekong, optional beach or city extension.</td><td data-label="Use the extra time for">Con Dao, Phu Quoc, Da Lat, or a slower central coast stay.</td><td data-label="VietnamGuide verdict">Good when northern weather or flight pricing makes the classic route less attractive.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Night allocation before day planning</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The easiest way to make a two-week itinerary feel expensive is to stop changing hotels without a reason. Use nights as the real planning currency: they reveal whether the trip is a route or a blur.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-14day-night-allocation">
<thead>
<tr><th>Base</th><th>Recommended nights</th><th>Why this works</th><th>When to change it</th></tr>
</thead>
<tbody>
<tr><td data-label="Base">Hanoi</td><td data-label="Recommended nights">2-3 nights</td><td data-label="Why this works">Enough time to recover from arrival, eat well, and understand the north before countryside or bay logistics.</td><td data-label="When to change it">Use 2 nights if you land early and want more central Vietnam; use 3 if long-haul recovery matters.</td></tr>
<tr><td data-label="Base">Ninh Binh</td><td data-label="Recommended nights">1-2 nights</td><td data-label="Why this works">Overnighting turns the landscape into a calmer stop instead of a traffic-heavy day trip.</td><td data-label="When to change it">Make it a day trip only when luggage, family pace, or hotel changes are the bigger problem.</td></tr>
<tr><td data-label="Base">Bay cruise</td><td data-label="Recommended nights">1 night</td><td data-label="Why this works">One good overnight usually gives the bay enough weight without letting cruise logistics dominate the route.</td><td data-label="When to change it">Add a second night only for a stronger cabin, route, or rest reason; do not add it just because the bay is famous.</td></tr>
<tr><td data-label="Base">Hue</td><td data-label="Recommended nights">1-2 nights</td><td data-label="Why this works">Hue is strongest when you give it context, not when it becomes a lunch stop between airports.</td><td data-label="When to change it">Skip or shorten Hue if central heritage is less important than beach time, family rest, or Hoi An depth.</td></tr>
<tr><td data-label="Base">Hoi An / Da Nang</td><td data-label="Recommended nights">3-4 nights</td><td data-label="Why this works">This is the route's recovery middle: food, walking, countryside, beach-adjacent time, and flexible weather handling.</td><td data-label="When to change it">Use the fourth night for premium pace or tailoring; move one night south if flights are tight.</td></tr>
<tr><td data-label="Base">Ho Chi Minh City</td><td data-label="Recommended nights">2 nights</td><td data-label="Why this works">Two nights lets the south be more than a departure airport and keeps one day flexible for city context.</td><td data-label="When to change it">Add a third night only if the Mekong, Cu Chi, food, or city history is a genuine priority.</td></tr>
<tr><td data-label="Base">Mekong or final extension</td><td data-label="Recommended nights">0-1 night</td><td data-label="Why this works">The final extension should create contrast without endangering departure logistics.</td><td data-label="When to change it">Use 2 nights only if you remove something earlier and avoid a rushed international departure day.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">What the extra four days actually buy</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The difference between a good 10-day route and a good 14-day route is not four more pins on the map. The real value is a better start, a stronger middle, and one extension that has enough room to mean something.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-14day-extra-days-value">
<thead>
<tr><th>Extra time use</th><th>What it improves</th><th>Good sign</th><th>Bad sign</th></tr>
</thead>
<tbody>
<tr><td data-label="Extra time use">Arrival recovery</td><td data-label="What it improves">Hanoi feels like a place rather than a staging area.</td><td data-label="Good sign">You can sleep, walk, eat, and still keep the next morning calm.</td><td data-label="Bad sign">A paid long transfer or expensive guide is scheduled before you know how tired you are.</td></tr>
<tr><td data-label="Extra time use">Ninh Binh overnight</td><td data-label="What it improves">The landscape stop gets morning or evening atmosphere instead of being only a day-trip commute.</td><td data-label="Good sign">One main boat, bike, or viewpoint experience is enough.</td><td data-label="Bad sign">You are changing hotels just to collect more locations in one day.</td></tr>
<tr><td data-label="Extra time use">Central Vietnam buffer</td><td data-label="What it improves">Hue, Hoi An, food, beaches, and weather pivots stop competing for the same day.</td><td data-label="Good sign">You can move one activity without breaking the route.</td><td data-label="Bad sign">Every central day has a fixed tour, transfer, and dinner reservation.</td></tr>
<tr><td data-label="Extra time use">One southern or nature extension</td><td data-label="What it improves">The route gains contrast: Mekong, Phong Nha, beach rest, or Da Lat, not all of them.</td><td data-label="Good sign">The extension has a clear reason and a protected exit.</td><td data-label="Bad sign">The extension is added because two weeks sounds long enough.</td></tr>
<tr><td data-label="Extra time use">Departure protection</td><td data-label="What it improves">The final international flight is not dependent on a remote road transfer behaving perfectly.</td><td data-label="Good sign">The last night is close enough to the departure airport to absorb delays.</td><td data-label="Bad sign">Departure day includes Mekong, beach, mountain, or cave distance.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">A practical 14-day Vietnam itinerary</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This is the default route we would use for a broad first trip: arrive in Hanoi and depart from Ho Chi Minh City. If your flights are round-trip from one city, add a buffer night before the final long transfer or remove one extension.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-timeline vg-itinerary-14day-pacing-map">
<div class="vg-timeline-item"><div class="vg-day">Day 1</div><div><h3>Arrive in Hanoi</h3><p>Keep the first night simple. Stay central, walk lightly, and avoid a paid evening tour unless arrival time is certain.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 2</div><div><h3>Hanoi orientation</h3><p>Use a guided food walk, museum, old quarter route, or lake district plan to understand the city before leaving it.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 3</div><div><h3>Ninh Binh overnight</h3><p>Go overnight if you want calmer mornings. Make it a day trip only if you need fewer hotel changes or have limited luggage flexibility.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 4</div><div><h3>Ninh Binh depth or transfer positioning</h3><p>Choose one main landscape experience, then position for your bay transfer rather than stacking every viewpoint.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 5</div><div><h3>Ha Long or Lan Ha Bay overnight</h3><p>Book the cruise as a logistics product, not just a pretty photo: pickup point, port, cabin, cancellation policy, and weather process matter.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 6</div><div><h3>Bay return and central transfer</h3><p>Protect this day. Most rushed itineraries break here by pretending the cruise return, road transfer, airport, and hotel check-in are frictionless.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 7</div><div><h3>Hue</h3><p>Use Hue for imperial history, slower food, and context. If heritage is not your priority, swap this for an extra Hoi An or Da Nang night.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 8</div><div><h3>Hai Van Pass to Hoi An</h3><p>Let the transfer be part of the trip if weather and luggage make it pleasant. Otherwise, move simply and save energy for Hoi An.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 9</div><div><h3>Hoi An slow day</h3><p>Give Hoi An one unhurried day for food, architecture, tailoring, countryside, beach-adjacent rest, or a guided context walk.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 10</div><div><h3>Central buffer or chosen extension</h3><p>Use this as the hinge day. Add Phong Nha, a beach rest, a better guided day, or nothing at all if the trip already feels full.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 11</div><div><h3>Fly to Ho Chi Minh City</h3><p>Keep the first southern day urban and flexible. Late flights, baggage, and city traffic can shrink the useful part of the day.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 12</div><div><h3>Ho Chi Minh City</h3><p>Choose city history, food, markets, architecture, or a guided local lens. Do not treat the city as only a transit stop.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 13</div><div><h3>Mekong or southern alternative</h3><p>Pick one southern add-on. Mekong works for river life and food context; a beach or Da Lat extension needs more nights and should replace something earlier.</p></div></div>
<div class="vg-timeline-item"><div class="vg-day">Day 14</div><div><h3>Depart from Ho Chi Minh City</h3><p>Keep departure day conservative. If your flight is late, use a close city experience rather than a distant excursion.</p></div></div>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Transfer pressure map</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A two-week itinerary fails most often at the handoffs, not at the headline destinations. These are the days to plan like an operator, not like a brochure.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-14day-transfer-pressure">
<thead>
<tr><th>Route handoff</th><th>Why it is fragile</th><th>Safer planning move</th><th>When to pay for help</th></tr>
</thead>
<tbody>
<tr><td data-label="Route handoff">Arrival to first full day</td><td data-label="Why it is fragile">Long-haul fatigue, delayed luggage, and unfamiliar streets can make a clever plan feel heavy.</td><td data-label="Safer planning move">Keep the first paid commitment short, local, and easy to move.</td><td data-label="When to pay for help">Airport transfer and a central first hotel are usually worth more than an ambitious tour.</td></tr>
<tr><td data-label="Route handoff">Hanoi to Ninh Binh to bay</td><td data-label="Why it is fragile">The countryside and cruise logistics often depend on pickup points and road timing.</td><td data-label="Safer planning move">Confirm whether the cruise can collect from Ninh Binh, Hanoi, or only a fixed point.</td><td data-label="When to pay for help">Private transfer can make sense when luggage, children, or a narrow cruise check-in window are involved.</td></tr>
<tr><td data-label="Route handoff">Bay return to central Vietnam</td><td data-label="Why it is fragile">Cruise disembarkation, highway time, airport buffer, and flight delay risk stack into one day.</td><td data-label="Safer planning move">Treat this as a transfer day with a simple central arrival.</td><td data-label="When to pay for help">Use a better cruise/transfer bundle if it reduces uncertainty, not only because it looks premium.</td></tr>
<tr><td data-label="Route handoff">Hue to Hoi An</td><td data-label="Why it is fragile">The scenic route can become tiring in poor weather or with too many luggage stops.</td><td data-label="Safer planning move">Choose a direct transfer when energy matters; make the Hai Van Pass scenic only when conditions suit it.</td><td data-label="When to pay for help">A private car is useful if you want controlled stops without turning the day into a group-tour timetable.</td></tr>
<tr><td data-label="Route handoff">Central Vietnam to Ho Chi Minh City</td><td data-label="Why it is fragile">Domestic flights consume more real time than the flight duration suggests.</td><td data-label="Safer planning move">Keep the southern arrival day flexible and urban.</td><td data-label="When to pay for help">Pay for better flight timing before paying for another rushed attraction.</td></tr>
<tr><td data-label="Route handoff">Final extension to departure</td><td data-label="Why it is fragile">Mekong, beach, cave, or mountain distance can threaten the most expensive fixed point of the trip.</td><td data-label="Safer planning move">Sleep near the departure city before the international flight.</td><td data-label="When to pay for help">Private transfer is justified only if it protects a route you already simplified.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Four route variants that still fit 14 days</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use these variants when the default route is close but not quite right. The rule is simple: every added specialty needs a removal somewhere else.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-14day-route-variants">
<thead>
<tr><th>Variant</th><th>Who should choose it</th><th>Route shape</th><th>What it improves</th><th>What it gives up</th></tr>
</thead>
<tbody>
<tr><td data-label="Variant">Premium slow middle</td><td data-label="Who should choose it">Couples, honeymooners, older travelers, and anyone spending more for comfort.</td><td data-label="Route shape">Hanoi, Ninh Binh, bay, Hue, Hoi An/Da Nang, Ho Chi Minh City.</td><td data-label="What it improves">Better hotels, fewer weak meals, less luggage friction, and more usable central Vietnam time.</td><td data-label="What it gives up">A remote mountain or extra southern detour.</td></tr>
<tr><td data-label="Variant">Family lower-friction route</td><td data-label="Who should choose it">Families with children or travelers who need predictable mornings.</td><td data-label="Route shape">Hanoi, bay or Ninh Binh, Da Nang/Hoi An, Ho Chi Minh City, optional Mekong.</td><td data-label="What it improves">Fewer one-night stops, easier pool/beach breaks, and less transfer stacking.</td><td data-label="What it gives up">Some heritage depth in Hue or countryside overnight texture.</td></tr>
<tr><td data-label="Variant">Food and heritage route</td><td data-label="Who should choose it">Travelers who care more about context, old towns, imperial history, and meals than beaches.</td><td data-label="Route shape">Hanoi, Ninh Binh, bay, Hue, Hoi An, Ho Chi Minh City.</td><td data-label="What it improves">Stronger cultural arc from north to central to south.</td><td data-label="What it gives up">Beach-resort time and remote scenery.</td></tr>
<tr><td data-label="Variant">Landscape-heavy route</td><td data-label="Who should choose it">Photographers and scenery-first travelers willing to drop the full-country idea.</td><td data-label="Route shape">Hanoi, Ninh Binh, bay, one mountain area, optional central Vietnam.</td><td data-label="What it improves">More mornings in landscapes and fewer big-city compromises.</td><td data-label="What it gives up">Ho Chi Minh City, Mekong, and the clean north-central-south story.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where the extra four days should go</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The extra days in a two-week trip should create contrast or resilience. They should not create a second trip hidden inside the first one.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-14day-extension-matrix">
<thead>
<tr><th>Extension</th><th>Use it when</th><th>Remove or reduce</th><th>Watch before booking</th></tr>
</thead>
<tbody>
<tr><td data-label="Extension">Mekong Delta</td><td data-label="Use it when">You depart from Ho Chi Minh City and want river, food, and slower southern contrast.</td><td data-label="Remove or reduce">A rushed final central night, not your first Hanoi recovery day.</td><td data-label="Watch before booking">Drive time, one-day versus overnight value, and flight-day risk.</td></tr>
<tr><td data-label="Extension">Phong Nha</td><td data-label="Use it when">You want nature and caves and are already traveling through central Vietnam.</td><td data-label="Remove or reduce">Ho Chi Minh City or Mekong if the full-country route becomes too transfer-heavy.</td><td data-label="Watch before booking">Seasonal cave operations, road time, and whether the detour steals your Hoi An buffer.</td></tr>
<tr><td data-label="Extension">Northern mountains</td><td data-label="Use it when">Scenery is the core reason for the trip and you are willing to drop the south.</td><td data-label="Remove or reduce">Hue, Hoi An, or Ho Chi Minh City rather than squeezing mountains into the classic route.</td><td data-label="Watch before booking">Road duration, weather, driver quality, and travel insurance terms for remote travel.</td></tr>
<tr><td data-label="Extension">Beach rest</td><td data-label="Use it when">You are traveling with family, honeymoon pace, or premium hotels as a major goal.</td><td data-label="Remove or reduce">Extra city nights that do not change the trip's meaning.</td><td data-label="Watch before booking">Regional weather, airport access, resort transfer time, and cancellation flexibility.</td></tr>
<tr><td data-label="Extension">Slower Hoi An and Hue</td><td data-label="Use it when">Food, craft, heritage, biking, and calmer hotels matter more than country coverage.</td><td data-label="Remove or reduce">The final southern add-on.</td><td data-label="Watch before booking">Central Vietnam rain patterns and whether Da Nang departure flights are practical.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where to spend, save, and stay longer</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Fourteen days gives you enough room to spend deliberately. The premium move is not upgrading everything. It is putting money where the route becomes easier, safer, or more memorable.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-14day-base-strategy">
<thead>
<tr><th>Base or route moment</th><th>Spend here when</th><th>Save here when</th><th>Stay longer when</th></tr>
</thead>
<tbody>
<tr><td data-label="Base or route moment">First Hanoi hotel</td><td data-label="Spend here when">You arrive late, have children, or want low-friction food and walking access.</td><td data-label="Save here when">You land early, travel light, and know you only need a clean central base.</td><td data-label="Stay longer when">Jet lag recovery, food, museums, and old-quarter context matter.</td></tr>
<tr><td data-label="Base or route moment">Ninh Binh</td><td data-label="Spend here when">A rural lodge or better location gives you calmer mornings and less road time.</td><td data-label="Save here when">You are using it as a simple overnight between Hanoi and the bay.</td><td data-label="Stay longer when">Landscape, cycling, and slower photography are real priorities.</td></tr>
<tr><td data-label="Base or route moment">Bay cruise</td><td data-label="Spend here when">Cabin quality, route, transfer policy, and weather process are clearly better.</td><td data-label="Save here when">The itinerary is already scenic-heavy and the cruise is mainly a one-night highlight.</td><td data-label="Stay longer when">You want rest and the second night buys a better route, not only more time on board.</td></tr>
<tr><td data-label="Base or route moment">Hue to Hoi An transfer</td><td data-label="Spend here when">You want controlled Hai Van Pass stops, comfort, and luggage flexibility.</td><td data-label="Save here when">Weather is poor or the transfer is only functional.</td><td data-label="Stay longer when">You want one more central Vietnam morning instead of another destination.</td></tr>
<tr><td data-label="Base or route moment">Hoi An / Da Nang middle</td><td data-label="Spend here when">The hotel location lets you walk, rest, eat well, and avoid unnecessary local transfers.</td><td data-label="Save here when">You will be out most of the day and need only a practical base.</td><td data-label="Stay longer when">Food, tailoring, countryside, beach-adjacent rest, or family pace matters.</td></tr>
<tr><td data-label="Base or route moment">Ho Chi Minh City finale</td><td data-label="Spend here when">A central hotel reduces traffic friction before food, history, and departure logistics.</td><td data-label="Save here when">The south is only a short city stop and you already spent on the route's core experiences.</td><td data-label="Stay longer when">You want a real southern chapter, not just a flight home.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Season pivots for a two-week route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam does not have one simple best month. A two-week route usually crosses weather zones, so the better question is which part of the country deserves your flexibility.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-14day-season-pivots">
<thead>
<tr><th>Planning season</th><th>Safer route bias</th><th>What to protect</th><th>What to avoid pretending</th></tr>
</thead>
<tbody>
<tr><td data-label="Planning season">January-February</td><td data-label="Safer route bias">North and central can work well, but pack for cooler northern days and check holiday crowding around Tet.</td><td data-label="What to protect">Arrival recovery, transport buffers, and hotel flexibility around holiday periods.</td><td data-label="What to avoid pretending">That every restaurant, transfer, or small operator behaves normally around major holidays.</td></tr>
<tr><td data-label="Planning season">March-April</td><td data-label="Safer route bias">The classic north-central-south route is often at its easiest for first timers.</td><td data-label="What to protect">Bay cruise quality, central Vietnam pacing, and open-jaw flights.</td><td data-label="What to avoid pretending">That good weather means you can remove all transfer margin.</td></tr>
<tr><td data-label="Planning season">May-August</td><td data-label="Safer route bias">Heat and rain risk make a slower route, better hotels, and beach decisions more important.</td><td data-label="What to protect">Midday rest, air-conditioned transfers, cancellation terms, and family pace.</td><td data-label="What to avoid pretending">That a packed city-and-countryside schedule will feel comfortable in hotter weeks.</td></tr>
<tr><td data-label="Planning season">September-November</td><td data-label="Safer route bias">Be careful with central Vietnam weather exposure; north or south weighting may be wiser depending on exact dates.</td><td data-label="What to protect">Hoi An/Hue flexibility, bay weather policy, and alternate city days.</td><td data-label="What to avoid pretending">That central Vietnam beach or heritage days are guaranteed to behave like dry-season brochure images.</td></tr>
<tr><td data-label="Planning season">December</td><td data-label="Safer route bias">A balanced route can work, but pricing, holiday demand, and regional variation matter more.</td><td data-label="What to protect">Good hotel locations, earlier reservations, and sensible final-night logistics.</td><td data-label="What to avoid pretending">That late-December travel is only a weather question.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Slowdown rules that make two weeks feel premium</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Premium itinerary design is usually invisible. It shows up as better sleep, fewer weak meals, calmer hotel changes, and fewer expensive days lost to avoidable logistics.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-14day-slowdown-rules">
<thead>
<tr><th>Rule</th><th>Practical test</th><th>Why it matters</th></tr>
</thead>
<tbody>
<tr><td data-label="Rule">Never stack two fragile transfers back to back</td><td data-label="Practical test">If yesterday had cruise return, road transfer, or a domestic flight, make today slower.</td><td data-label="Why it matters">Vietnam routes often fail through accumulated friction, not one dramatic problem.</td></tr>
<tr><td data-label="Rule">Protect the day after arrival</td><td data-label="Practical test">Do not place your most expensive guide, cruise, or long road transfer on the first full day unless arrival is easy.</td><td data-label="Why it matters">Jet lag and delayed luggage can damage the most valuable start of the trip.</td></tr>
<tr><td data-label="Rule">Use one open-jaw route if possible</td><td data-label="Practical test">Price arrival in Hanoi and departure from Ho Chi Minh City, Da Nang, or the reverse before accepting a round-trip backtrack.</td><td data-label="Why it matters">A better flight path can save more trip quality than one cheaper hotel night.</td></tr>
<tr><td data-label="Rule">One extension means one extension</td><td data-label="Practical test">If you add Mekong, do not also add Phong Nha and northern mountains unless you drop a major region.</td><td data-label="Why it matters">The guide's value is in the editorial cut, not in proving every place is possible.</td></tr>
<tr><td data-label="Rule">Book weather-sensitive experiences with an escape plan</td><td data-label="Practical test">Know the cancellation or rerouting process before booking a bay cruise, cave day, mountain drive, or beach resort.</td><td data-label="Why it matters">Vietnam's regional weather can change what feels wise even when the itinerary looks good.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">How to adapt the route by traveler type</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The same 14 days should not be sold to every traveler. Use these adaptations before choosing hotels, tours, or domestic flights.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-14day-audience-adaptations">
<thead>
<tr><th>Traveler type</th><th>Best adjustment</th><th>Upgrade that actually helps</th><th>Cut first</th></tr>
</thead>
<tbody>
<tr><td data-label="Traveler type">First-time couple</td><td data-label="Best adjustment">Keep the balanced route but protect Hoi An and one better hotel stay.</td><td data-label="Upgrade that actually helps">A stronger cruise cabin, private Hue-Hoi An transfer, or one excellent food/context guide.</td><td data-label="Cut first">A same-day Mekong run if the final days already feel tight.</td></tr>
<tr><td data-label="Traveler type">Family with children</td><td data-label="Best adjustment">Reduce one-night stops and make Da Nang/Hoi An the soft middle of the trip.</td><td data-label="Upgrade that actually helps">Private transfers, family-sized rooms, pool access, and earlier flight times.</td><td data-label="Cut first">Hue or Ninh Binh overnight if hotel changes become the main stress.</td></tr>
<tr><td data-label="Traveler type">Solo traveler</td><td data-label="Best adjustment">Keep city bases strong and use small-group tours only where logistics are annoying alone.</td><td data-label="Upgrade that actually helps">Central hotels, reliable airport transfers, and guided food/context walks.</td><td data-label="Cut first">Private long-distance transfers that do not save meaningful time.</td></tr>
<tr><td data-label="Traveler type">Budget traveler</td><td data-label="Best adjustment">Simplify the route before downgrading every experience.</td><td data-label="Upgrade that actually helps">One better overnight transport choice or one better-located city hotel.</td><td data-label="Cut first">Extra domestic flights, one-night beach detours, and weak paid tours.</td></tr>
<tr><td data-label="Traveler type">Luxury or comfort-first traveler</td><td data-label="Best adjustment">Use the extra days for fewer moves, better guides, and premium downtime.</td><td data-label="Upgrade that actually helps">A better cruise, private car on transfer-heavy days, and hotels that reduce local transport friction.</td><td data-label="Cut first">Any famous stop that creates an early checkout without adding a clear memory.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Booking sequence for 14 days</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Do not book this route from day one forward. Book the constraints first, then let the softer days fit around them.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-14day-prebook-flex">
<thead>
<tr><th>Decision</th><th>Book earlier</th><th>Keep flexible</th><th>Why</th></tr>
</thead>
<tbody>
<tr><td data-label="Decision">International flights</td><td data-label="Book earlier">Open-jaw arrival/departure if the price is sensible.</td><td data-label="Keep flexible">Exact domestic route until the weather and route shape are clear.</td><td data-label="Why">The international flight path controls whether the itinerary wastes a day backtracking.</td></tr>
<tr><td data-label="Decision">Bay cruise</td><td data-label="Book earlier">A specific cabin or reputable cruise when it anchors the north.</td><td data-label="Keep flexible">The cheapest-looking option until you understand transfer and weather policy.</td><td data-label="Why">Cruise quality and logistics affect the whole northern half of the trip.</td></tr>
<tr><td data-label="Decision">Central Vietnam hotels</td><td data-label="Book earlier">High-demand Hoi An or Hue stays in good locations.</td><td data-label="Keep flexible">One soft buffer night if traveling in a rain-sensitive window.</td><td data-label="Why">A good central base can replace several small upgrades elsewhere.</td></tr>
<tr><td data-label="Decision">Mekong / final extension</td><td data-label="Book earlier">Only if it is a real priority and not on departure day.</td><td data-label="Keep flexible">Final city time, food walks, and short local experiences.</td><td data-label="Why">The last two days should protect the international departure, not gamble with distance.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
<!-- wp:list {"className":"vg-check-list vg-itinerary-14day-booking-sequence"} -->
<ul class="wp-block-list vg-check-list vg-itinerary-14day-booking-sequence">
<li>Start with the <a href="/plan/vietnam-evisa/">Vietnam E-Visa guide</a> and passport/entry checks before any non-refundable booking.</li>
<li>Use the <a href="/plan/best-time-to-visit-vietnam/">Best Time guide</a> to decide whether the route should lean north, central, south, or beach-light.</li>
<li>Choose the route shape, then compare it with the <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam guide</a>.</li>
<li>Price the itinerary against the <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost guide</a> before upgrading hotels, cruises, or guided days.</li>
<li>Check whether the <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam guide</a> supports your shortlist or reveals a place you should skip.</li>
<li>Book international flights and first/final nights before domestic transfers.</li>
<li>Book the bay cruise, any cave or mountain logistics, and high-demand central Vietnam hotels only after weather and transfer timing make sense.</li>
<li>Leave one half-day unscheduled in the middle third of the trip and one flexible evening before departure.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Final planning audit before you book</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this audit when your spreadsheet looks finished. A route can be technically possible and still be a poor two-week trip if it hides too many weak mornings, late transfers, and fragile assumptions.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-14day-planning-audit">
<thead>
<tr><th>Audit question</th><th>Green light</th><th>Revise the route if...</th><th>Editorial fix</th></tr>
</thead>
<tbody>
<tr><td data-label="Audit question">Do you have more than four hotel changes?</td><td data-label="Green light">Each change clearly improves the route.</td><td data-label="Revise the route if...">You are repacking almost every morning after day three.</td><td data-label="Editorial fix">Combine central Vietnam nights or make Ninh Binh a day trip.</td></tr>
<tr><td data-label="Audit question">Is one day carrying too much logistics?</td><td data-label="Green light">Only one major transfer happens that day.</td><td data-label="Revise the route if...">A cruise return, road transfer, airport, flight, and city arrival all sit together.</td><td data-label="Editorial fix">Remove the evening plan and treat the day as recovery.</td></tr>
<tr><td data-label="Audit question">Is the extension earning its place?</td><td data-label="Green light">It adds a new kind of memory: river, cave, beach, mountain, or slower central life.</td><td data-label="Revise the route if...">It exists only because another itinerary included it.</td><td data-label="Editorial fix">Drop the extension and spend the night where you already care.</td></tr>
<tr><td data-label="Audit question">Have you checked weather by region?</td><td data-label="Green light">The route has a pivot if central rain, northern chill, heat, or holiday crowding matters.</td><td data-label="Revise the route if...">You chose a national "best month" without checking each route zone.</td><td data-label="Editorial fix">Use the Best Time guide before final hotel and cruise payment.</td></tr>
<tr><td data-label="Audit question">Can the final 36 hours absorb delay?</td><td data-label="Green light">You are near the departure city with flexible activities.</td><td data-label="Revise the route if...">A remote tour or road transfer must go perfectly before an international flight.</td><td data-label="Editorial fix">Move the extension earlier or replace it with a city-based final day.</td></tr>
<tr><td data-label="Audit question">Does the budget match the route shape?</td><td data-label="Green light">Transport, cruise, guide, and hotel upgrades are priced as named decisions.</td><td data-label="Revise the route if...">The daily average hides a premium cruise, domestic flights, or private transfers.</td><td data-label="Editorial fix">Use the Travel Cost guide to separate route cost from comfort upgrades.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Short answers for common two-week mistakes</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>These are the questions that usually reveal whether a 14-day plan is designed for the traveler or copied from a search result.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-faq-list vg-itinerary-14day-faq">
<details><summary>Should 14 days include Sapa or Ha Giang?</summary><p>Only if northern landscapes are the point of the trip. Do not add Sapa or Ha Giang to the balanced north-central-south route unless you remove the south, reduce central Vietnam, or accept a much faster trip.</p></details>
<details><summary>Is one night in the Mekong Delta worth it?</summary><p>It can be, especially with a Ho Chi Minh City departure, but it should not sit on the final flight day. If the Mekong is only a box to tick, use the time for a stronger city day or slower Hoi An/Hue middle.</p></details>
<details><summary>Should I fly or take the train between regions?</summary><p>Use the train when the timing, comfort, and route experience genuinely help. Use flights when they protect the itinerary. Compare real station/airport transfer time, baggage, delay risk, and hotel check-in time rather than only the headline journey duration.</p></details>
<details><summary>Can I add Phu Quoc or Con Dao?</summary><p>Yes, but only by treating the island as the extension and removing another add-on. A two-night island stay after a full north-central-south route often spends too much time entering and exiting the beach experience.</p></details>
<details><summary>What is the most premium version of this itinerary?</summary><p>The most premium version is usually slower: better central hotels, a stronger bay cruise, private transfer on complex days, one excellent guide, and fewer forced one-night stops. It is not more destinations.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">When 10 days is actually better</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>More days do not automatically make the trip better. If the extra four days force awkward flights, poor weather, a remote detour you do not truly want, or a higher budget that does not buy comfort, use the <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam guide</a> and keep the route sharper.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>A strong 10-day trip plus a future return can be wiser than a 14-day trip that spends its advantage on movement. This is especially true for families, older travelers, honeymooners, and anyone visiting in a season where one region clearly has better conditions than the others.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-itinerary-14day-hero:v1',
    'concierge verdict' => 'vg-itinerary-14day-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-itinerary-14day-at-a-glance:v1',
    'source diversity' => 'vg-itinerary-14day-source-diversity:v1',
    'source trail snapshot' => 'vg-itinerary-14day-source-trail-snapshot:v1',
    'route chooser' => 'vg-itinerary-14day-route-chooser:v1',
    'photo grid' => 'vg-itinerary-14day-photo-grid:v1',
    'route builder' => 'vg-itinerary-14day-route-builder',
    'night allocation' => 'vg-itinerary-14day-night-allocation',
    'extra days value' => 'vg-itinerary-14day-extra-days-value',
    'pacing map' => 'vg-itinerary-14day-pacing-map',
    'transfer pressure' => 'vg-itinerary-14day-transfer-pressure',
    'route variants' => 'vg-itinerary-14day-route-variants',
    'extension matrix' => 'vg-itinerary-14day-extension-matrix',
    'base strategy' => 'vg-itinerary-14day-base-strategy',
    'season pivots' => 'vg-itinerary-14day-season-pivots',
    'slowdown rules' => 'vg-itinerary-14day-slowdown-rules',
    'audience adaptations' => 'vg-itinerary-14day-audience-adaptations',
    'prebook flex' => 'vg-itinerary-14day-prebook-flex',
    'booking sequence' => 'vg-itinerary-14day-booking-sequence',
    'planning audit' => 'vg-itinerary-14day-planning-audit',
    'FAQ' => 'vg-itinerary-14day-faq',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('14 Days in Vietnam guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => '14 Days in Vietnam',
    'post_name'      => '14-days-in-vietnam',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led 14-day Vietnam itinerary for international visitors, with a two-week route chooser, month and travel-style pivots, transfer checks, route photography, and booking sequence.',
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
    vg_ops_fail('Could not publish 14 Days itinerary guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish 14 Days itinerary guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', '14 Days in Vietnam Itinerary: Best Two-Week Route');
update_post_meta($page_id, 'rank_math_description', 'Plan 14 days in Vietnam by month, route shape, and travel style, with a two-week route chooser, transfer checks, route photos, and booking sequence.');
update_post_meta($page_id, 'rank_math_focus_keyword', '14 days in Vietnam');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Choose the strongest 14-day Vietnam route shape before booking flights, hotels, cruises, and domestic transfers.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 25, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Hardened the 14-day itinerary for GSC opportunity with a stronger H1 and metadata, a route-at-a-glance table, source-discipline and source-snapshot bands, a traveler-job route chooser, text-only image credits, and refreshed two-week decision logic.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Plan your trip - https://vietnam.travel/plan-your-trip - checked July 25, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 25, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 25, 2026\nUNESCO World Heritage Centre - Ha Long Bay - Cat Ba Archipelago - https://whc.unesco.org/en/list/672/ - checked July 25, 2026\nUNESCO World Heritage Centre - Hoi An Ancient Town - https://whc.unesco.org/en/list/948/ - checked July 25, 2026\nUNESCO World Heritage Centre - Complex of Hue Monuments - https://whc.unesco.org/en/list/678/ - checked July 25, 2026\nUNESCO World Heritage Centre - Trang An Landscape Complex - https://whc.unesco.org/en/list/1438/ - checked July 25, 2026\nVietnam Railways - official rail fare lookup and booking reference - https://dsvn.vn/ - checked July 25, 2026\nVietnam National Electronic Visa system - official e-visa portal for outside Viet Nam foreigners - https://evisa.gov.vn/e-visa/foreigners - checked July 25, 2026\nWikimedia Commons image record - Hanoi Hoan Kiem Lake - https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg - license checked July 25, 2026\nWikimedia Commons image record - Trang An Landscape Complex, Ninh Binh Province - https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg - license checked July 25, 2026\nWikimedia Commons image record - Hue Vietnam Citadel - https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg - license checked July 25, 2026\nWikimedia Commons image record - Vietnam, Hai-Van-Pass - https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg - license checked July 25, 2026\nWikimedia Commons image record - Hoi An Ancient Town - https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg - license checked July 25, 2026\nWikimedia Commons image record - Ho Chi Minh City Hall - https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg - license checked July 25, 2026\nWikimedia Commons image record - Vietnam, Phong Dien, Mekong Delta, River - https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg - license checked July 25, 2026\nWikimedia Commons image record - Son River, Quang Binh province - https://commons.wikimedia.org/wiki/File:Son_River-Quang_Binh_province.jpg - license checked July 25, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'This is not a maximum-coverage itinerary. It is a two-week decision guide that protects margin before adding another airport.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Original route-shape framework separating balanced first-trip, slower north-central, north scenery, central-south, family, premium, food/heritage, and beach-recovery route jobs.\nAt-a-glance route brief that answers the high-intent two-week itinerary question before the reader enters the long guide.\nSource-discipline and source-trail snapshot bands that separate official facts, weather/transport constraints, heritage records, and editorial judgment.\nNight-allocation framework that makes hotel changes and route friction visible before booking.\nExtra-days value matrix that explains what four additional days should buy.\nTwo-week pacing map that names transfer-pressure days instead of hiding them.\nTransfer pressure map for arrival, bay return, Hai Van, central-south flight, and final-extension handoffs.\nRoute-variant, base-strategy, and traveler-type matrices for premium, family, solo, budget, food, heritage, landscape-first, and comfort-first trips.\nSeason-pivot table that avoids pretending Vietnam has one uniform best-weather answer.\nExtension matrix that explains what each add-on should replace.\nPlanning audit and FAQ that catch copied-itinerary mistakes before booking.\nSlowdown rules for premium-feeling pacing, open-jaw flights, weather-sensitive experiences, and fragile transfers.\nBooking sequence tied to existing reviewed guides and official source checks.\nLicensed route photography with text-only image credits to reduce visible outbound clutter.\nVisible source trail and update log.");
$related_routes = implode(
    "\n",
    [
        '7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Use this if two weeks is actually a one-week route with too many add-ons.',
        '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Use this if the two-week plan starts feeling overloaded.',
        '21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use this if the trip needs a fuller country arc with more margin instead of a compressed two-week version.',
        'Best Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check the month and regional weather bias before locking the two-week route.',
        'Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Recheck the full planning spine before committing to domestic flights and hotel changes.',
        'Best Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Pressure-test whether each stop changes the trip or only adds a famous name.',
        'Best Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ | Choose one beach chapter only if it improves the route instead of adding another flight.',
        'Best Islands in Vietnam | /destinations/best-islands-in-vietnam/ | Use islands only when the access cost is justified by the route, season, and rest value.',
        'Best Things to Do in Hue | /destinations/best-things-to-do-in-hue/ | Use this when the two-week route can protect a serious central heritage chapter.',
        'Vietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the route shape before upgrading hotels or cruises.',
        'North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Recheck which region should lead the itinerary.',
        'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Anchor the first northern nights with food, culture, arrival recovery, and onward-route logic.',
        'Where to Stay in Hanoi | /destinations/where-to-stay-in-hanoi/ | Choose the first Hanoi base by arrival hour, walking tolerance, food access, and next-transfer needs.',
        'Old Quarter vs French Quarter vs West Lake | /compare/old-quarter-vs-french-quarter-vs-west-lake/ | Pick the Hanoi neighborhood before the first two nights create unnecessary friction.',
        'Best Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.',
        'Hanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.',
        'Hanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.',
        'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide what the countryside stop should actually do before adding more northern scenery.',
        'Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.',
        'Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.',
        'Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.',
        'Where to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.',
        'Tam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.',
        'Ninh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.',
        'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Choose the bay chapter by cruise quality, port, route, season, and transfer pressure.',
        'Ha Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Decide which bay experience fits the route before booking a cruise by photo alone.',
        'Cat Ba Travel Guide | /destinations/cat-ba-travel-guide/ | Use Cat Ba only when an island-and-bay base improves the northern chapter.',
        'Bai Tu Long Bay Guide | /destinations/bai-tu-long-bay-guide/ | Consider Bai Tu Long when a quieter bay route is worth the extra cruise filtering.',
        'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use Da Nang when the central coast needs airport logic, beach time, and a practical base.',
        'Da Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Choose the better central base before turning the middle of the route into hotel friction.',
        'Hoi An vs Hue | /compare/hoi-an-vs-hue/ | Decide whether central Vietnam should lean old-town softness, imperial history, or both.',
        'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Add Phu Quoc only when island time is the extension, not a rushed finale.',
        'Con Dao Travel Guide | /destinations/con-dao-travel-guide/ | Use Con Dao only when quiet premium island time is worth the access friction.',
        'Phu Quoc vs Nha Trang | /compare/phu-quoc-vs-nha-trang/ | Compare island-resort time with city-beach convenience before adding a beach chapter.',
        'Mui Ne vs Nha Trang | /compare/mui-ne-vs-nha-trang/ | Choose the south-central beach by wind, city activity, transfer time, and route fit.',
        'Nha Trang Travel Guide | /destinations/nha-trang-travel-guide/ | Use Nha Trang when city-beach activity fits the middle or finale better than quiet island time.',
        'Quy Nhon Travel Guide | /destinations/quy-nhon-travel-guide/ | Use Quy Nhon when quieter mainland beach value improves the route without island friction.',
        'Cham Islands Travel Guide | /destinations/cham-islands-travel-guide/ | Add Cham Islands only when Hoi An already has enough time and sea conditions cooperate.',
        'Ly Son Travel Guide | /destinations/ly-son-travel-guide/ | Use Ly Son only when the central-coast island detour has enough ferry and weather buffer.',
        'Ho Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Make the southern city a real chapter before treating it as only a departure airport.',
        'Where to Stay in Ho Chi Minh City | /destinations/where-to-stay-in-ho-chi-minh-city/ | Pick the final city base by traffic, food, history, nightlife, and departure logistics.',
        'Best Day Trips from Ho Chi Minh City | /destinations/best-day-trips-from-ho-chi-minh-city/ | Choose the southern add-on only when it improves the finale instead of risking the flight home.',
        'Mekong Delta Travel Guide | /destinations/mekong-delta-travel-guide/ | Use the Mekong as a southern contrast only when the final route has enough buffer.',
        'Cu Chi Tunnels vs Mekong Delta Day Trip | /compare/cu-chi-tunnels-vs-mekong-delta-day-trip/ | Choose the Ho Chi Minh City day trip by history, river context, road time, and departure risk.',
    ]
);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero: Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0. Body images: Hanoi by Alex 69200 vx, CC BY-SA 4.0; Trang An by Jakub Halun, CC BY 4.0; Hoi An and Ho Chi Minh City by Steffen Schmitz, CC BY-SA 4.0; Hue by CEphoto, Uwe Aranas, CC BY-SA 3.0; Hai Van Pass by Wolkenkratzer, CC BY-SA 4.0; Mekong Delta by Vyacheslav Argenberg, CC BY 4.0; Son River by BacLuong, CC BY-SA 4.0.');
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
    vg_ops_fail('14 Days itinerary guide was updated but is not published.');
}

vg_ops_refresh_itineraries_hub();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published 14 Days in Vietnam itinerary guide: {$page_id} {$updated_permalink}");

<?php
/**
 * Publish the 7 Days in Vietnam guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-7-days-itinerary-guide.php --allow-root
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
    $value = getenv('VG_FORCE_7_DAY_ITINERARY_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_ops_repair_side_effects_enabled(): bool
{
    $value = getenv('VG_REPAIR_7_DAY_ITINERARY_LINKS');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the 7 Days itinerary guide.');
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

function vg_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_ops_log("Validated {$label} internal page links are published.");
}

function vg_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_ops_log("Validated {$label} related-route links are published.");
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

function vg_ops_refresh_itineraries_hub(): void
{
    $hub = get_page_by_path('itineraries', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_ops_log('Skipped Itineraries hub refresh: itineraries page was not found or is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('itineraries/7-days-in-vietnam')) {
        vg_ops_log('Skipped Itineraries hub refresh: 7 Days guide is not published.');
        return;
    }

    $marker = '<!-- vg-itinerary-7-day-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use one week as a route discipline test</h3><p>The <a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam guide</a> helps travelers decide when to keep Vietnam north-focused, central-focused, or extend to the <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam guide</a> before the itinerary becomes a flight collection.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Itineraries hub 7-day note', $block);
    vg_ops_upsert_marked_group($hub, 'Itineraries hub 7-day note', $marker, $block);
}

function vg_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_ops_log("Skipped related-route refresh for {$label}: 7 Days line is already current.");
        return;
    }

    update_post_meta($page->ID, 'vg_eeat_related_routes', implode("\n", $next_lines));
    vg_ops_log("Upserted 7 Days related route to {$label}: {$page->ID}");
}

function vg_ops_refresh_inbound_related_routes(): void
{
    $route_line = '7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Decide whether one week should stay in one focused region, use max two bases, or become a 10-day route before adding a second high-friction move.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
            'compare/da-nang-vs-hoi-an' => 'Da Nang vs Hoi An guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        ] as $path => $label
    ) {
        vg_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('itineraries', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: itineraries');
}

$page = get_page_by_path('itineraries/7-days-in-vietnam', OBJECT, 'page');

if ($page instanceof WP_Post && $page->post_status === 'publish' && vg_ops_repair_side_effects_enabled()) {
    vg_ops_log('Repairing 7 Days guide hub and inbound related-route side effects without rewriting the guide.');
    vg_ops_refresh_itineraries_hub();
    vg_ops_refresh_inbound_related_routes();
    vg_ops_log("Repaired 7 Days guide side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_ops_force_republish_enabled()) {
    vg_ops_fail('7 Days itinerary guide is not a draft. Set VG_FORCE_7_DAY_ITINERARY_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_ops_log("Preflight 7 Days itinerary guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_ops_log('Preflight 7 Days itinerary guide: no existing page found; creating a child page under /itineraries/.');
}

$guide_hero_image = esc_url(set_url_scheme(get_stylesheet_directory_uri(), 'https') . '/assets/images/ha-long-bay-vietnam-hero.jpg');
$bay_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg');
$hanoi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1280px-Hanoi-lac-hoan-kiem.jpg');
$hanoi_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg');
$trang_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1280px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');
$trang_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg');
$lan_ha_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/ca/Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg/1280px-Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg');
$lan_ha_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg');
$hoi_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1280px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hoi_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg');
$hue_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg/1280px-Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$hue_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');

$content = <<<HTML
<!-- vg-itinerary-7day-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$guide_hero_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Limestone islands in Ha Long Bay, Vietnam, used for a 7-day Vietnam itinerary guide" src="{$guide_hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed itinerary - Updated July 18, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">7 Days in Vietnam</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">With seven days in Vietnam, the best itinerary is not a tiny version of a two-week trip. It is one focused region, max two bases, and one high-friction highlight chosen carefully: northern scenery, a central food-and-heritage base, or a southern city-and-river chapter.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: in one week, every hotel change must earn its place twice.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$bay_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-itinerary-7day-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-itinerary-7day-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-itinerary-7day-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>The shortest serious first-trip answer is North Vietnam: Hanoi, Ninh Binh, one bay decision, and a protected Hanoi departure buffer.</strong> Choose central Vietnam instead when food, Hoi An, Hue, beach ease, and lower movement matter more. Do not plan Hanoi, Hoi An, Ho Chi Minh City, Mekong, and a bay cruise in seven days.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-itinerary-7day-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-itinerary-7day-shortlist">
<li><strong>Best default:</strong> Hanoi arrival, Ninh Binh countryside, Ha Long or Lan Ha/Cat Ba bay choice, Hanoi final night.</li>
<li><strong>Best low-friction alternative:</strong> Da Nang plus Hoi An, with Hue only if it replaces another hard day.</li>
<li><strong>Best skip rule:</strong> skip the third region before cutting sleep, arrival recovery, or departure buffer.</li>
<li><strong>Upgrade move:</strong> buy cleaner transfers and better locations before adding another famous stop.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-itinerary-7day-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-itinerary-7day-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-itinerary-7day-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">7-day Vietnam itinerary at a glance</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table">
<tbody>
<tr><td data-label="Question"><strong>Can I see all of Vietnam in 7 days?</strong></td><td data-label="Answer">No. You can have a strong one-region trip or a tight two-base route. A full-country sampler is usually too thin.</td></tr>
<tr><td data-label="Question"><strong>Best first-time route</strong></td><td data-label="Answer">Hanoi, Ninh Binh, Ha Long/Lan Ha/Cat Ba decision, Hanoi departure buffer.</td></tr>
<tr><td data-label="Question"><strong>Best for less movement</strong></td><td data-label="Answer">Da Nang and Hoi An, with Hue as a protected day only if the route has room.</td></tr>
<tr><td data-label="Question"><strong>When to extend</strong></td><td data-label="Answer">If you want north plus central Vietnam without painful compression, move to the 10-day guide.</td></tr>
<tr><td data-label="Question"><strong>Main risk</strong></td><td data-label="Answer">Treating every famous stop as if the travel day between them is free.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-itinerary-7day-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">One week by route proof</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The photos are not decoration. They show the main decision: keep the week focused enough that each chapter has a job. Hanoi orients the trip, Ninh Binh provides countryside, the bay is the one high-friction northern highlight, and Hoi An/Hue show why central Vietnam should be an alternative route, not an extra add-on.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-itinerary-7day-photo-grid" aria-label="Route photography for a 7-day Vietnam itinerary">
<figure class="vg-guide-photo"><img src="{$hanoi_image}" alt="Hoan Kiem Lake in central Hanoi, Vietnam" loading="lazy" decoding="async"><figcaption>Hanoi earns real time in a one-week route because arrival energy, food, history, and day-trip logistics all start here. Image: <a href="{$hanoi_credit_url}" target="_blank" rel="license noopener">Alex 69200 vx / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$trang_an_image}" alt="Boats and limestone scenery at Trang An in Ninh Binh, Vietnam" loading="lazy" decoding="async"><figcaption>Ninh Binh works when it slows the north and replaces a weaker long-distance move. Image: <a href="{$trang_an_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$lan_ha_image}" alt="Lan Ha Bay scenery near Cat Ba Island in northern Vietnam" loading="lazy" decoding="async"><figcaption>Lan Ha, Ha Long, Cat Ba, or Bai Tu Long should be one clear bay decision, not a cluster of overlapping logistics. Image: <a href="{$lan_ha_credit_url}" target="_blank" rel="license noopener">Saaremees / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hoi_an_image}" alt="Evening street scene in Hoi An Ancient Town, Vietnam" loading="lazy" decoding="async"><figcaption>Hoi An can lead a one-week central route, but it should not be stapled onto a completed northern week. Image: <a href="{$hoi_an_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hue_image}" alt="Historic architecture inside the Hue Citadel in central Vietnam" loading="lazy" decoding="async"><figcaption>Hue belongs when central Vietnam gets protected time; otherwise it becomes the first honest cut. Image: <a href="{$hue_credit_url}" target="_blank" rel="license noopener">CEphoto, Uwe Aranas / CC BY-SA 3.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-itinerary-7day-planning-flow:v1 -->
<!-- wp:group {"className":"vg-travel-guide-flow vg-itinerary-7day-planning-flow","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-travel-guide-flow vg-itinerary-7day-planning-flow">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">7-day planning flow</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The five decisions that decide whether one week works</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<ol class="vg-travel-flow-list" aria-label="7-day Vietnam route planning order">
<li><span>01</span><strong>Region</strong><p>Choose north, central, or south before choosing attractions.</p></li>
<li><span>02</span><strong>Base count</strong><p>Keep the route to one or two sleeping bases unless a transfer replaces a weaker day.</p></li>
<li><span>03</span><strong>One hard highlight</strong><p>Pick the bay, Hue, Mekong, or island module. Do not stack them.</p></li>
<li><span>04</span><strong>Exit buffer</strong><p>Protect the night before international departure or the final domestic handoff.</p></li>
<li><span>05</span><strong>Upgrade or extend</strong><p>If the cut list hurts, use the 10-day route instead of forcing seven days.</p></li>
</ol>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-itinerary-7day-route-family:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Choose the right 7-day route family</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-travel-route-family vg-itinerary-7day-route-family" aria-label="7-day Vietnam route family comparison">
<article><p class="vg-route-family-label">Best default</p><h3>North compact</h3><p>Hanoi, Ninh Binh, one bay decision, and Hanoi departure. The strongest first-trip answer for many travelers.</p><strong>Most balanced</strong></article>
<article><p class="vg-route-family-label">Lower movement</p><h3>Central slow route</h3><p>Da Nang and Hoi An, with Hue only when it gets a protected day. Strong for food, heritage, families, and calmer hotels.</p><strong>Most relaxed</strong></article>
<article><p class="vg-route-family-label">Southern focus</p><h3>City and river</h3><p>Ho Chi Minh City plus Mekong, with flexible city time. Works when southern Vietnam is the reason for the trip.</p><strong>Most urban</strong></article>
<article><p class="vg-route-family-label">Rest-first</p><h3>Beach week</h3><p>Phu Quoc or central coast only when weather and flights make rest the main purpose.</p><strong>Most selective</strong></article>
</div>
<!-- /wp:html -->

<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-route-matrix">
<thead>
<tr><th>7-day route family</th><th>Best for</th><th>Keep</th><th>Cut first</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="7-day route family">North compact default</td><td data-label="Best for">First-time travelers who want Hanoi, countryside, and bay scenery without a cross-country sprint.</td><td data-label="Keep">Hanoi, Ninh Binh, one bay decision, Hanoi final night.</td><td data-label="Cut first">Hoi An, Ho Chi Minh City, Mekong, Sapa, Phu Quoc.</td><td data-label="VietnamGuide verdict">Best first answer.</td></tr>
<tr><td data-label="7-day route family">Central slow route</td><td data-label="Best for">Food, heritage, beach-adjacent hotels, families, and couples.</td><td data-label="Keep">Da Nang, Hoi An, one protected Hue or beach/countryside day.</td><td data-label="Cut first">Northern bay and southern city add-ons.</td><td data-label="VietnamGuide verdict">Best low-friction alternative.</td></tr>
<tr><td data-label="7-day route family">South city and river</td><td data-label="Best for">Travelers flying through Ho Chi Minh City or prioritizing urban energy and the Mekong.</td><td data-label="Keep">Ho Chi Minh City, Mekong, flexible food/history time.</td><td data-label="Cut first">North or central side trips.</td><td data-label="VietnamGuide verdict">Good when the south leads.</td></tr>
<tr><td data-label="7-day route family">Beach/rest week</td><td data-label="Best for">Travelers who actively want recovery more than coverage.</td><td data-label="Keep">One beach base, airport logic, weather slack.</td><td data-label="Cut first">Multi-region sightseeing.</td><td data-label="VietnamGuide verdict">Only when rest is the trip purpose.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-itinerary-7day-transfer-pressure:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Transfer pressure in one week</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The danger in a one-week Vietnam itinerary is not distance alone. It is stacked transitions: arrival, hotel move, early transfer, cruise or day trip, another base, and departure. Keep the week honest by naming the hard move before paying for it.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-7day-transfer-pressure">
<thead>
<tr><th>Move</th><th>When it earns its place</th><th>When to cut it</th><th>Better use of the day</th></tr>
</thead>
<tbody>
<tr><td data-label="Move">Hanoi to Ninh Binh</td><td data-label="When it earns its place">You sleep there or protect a high-quality full day.</td><td data-label="When to cut it">It becomes a rushed check-box between flights.</td><td data-label="Better use of the day">Stay in Hanoi and improve food, lake, museum, or Old Quarter time.</td></tr>
<tr><td data-label="Move">Bay cruise</td><td data-label="When it earns its place">The route has weather terms, clear pickup, and a final-night buffer.</td><td data-label="When to cut it">It creates a fragile return before a flight.</td><td data-label="Better use of the day">Ninh Binh overnight or Hanoi depth.</td></tr>
<tr><td data-label="Move">North to central flight</td><td data-label="When it earns its place">Flights save real time and central Vietnam is the main reason for the trip.</td><td data-label="When to cut it">It only adds Hoi An as a famous name.</td><td data-label="Better use of the day">Use the 10-day guide or stay north.</td></tr>
<tr><td data-label="Move">Hue from Hoi An/Da Nang</td><td data-label="When it earns its place">Heritage is a priority and the day is protected.</td><td data-label="When to cut it">It becomes a long return day after a late arrival.</td><td data-label="Better use of the day">Hoi An food, beach, My Son, or Da Nang recovery.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-itinerary-7day-day-plan:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">A practical 7-day Vietnam itinerary</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This default assumes international flights use Hanoi. If your flights are fixed elsewhere, use the route family table above rather than bending this plan until it breaks.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-timeline vg-itinerary-day-plan vg-itinerary-7day-day-plan">
<div><p class="vg-day">Day 1</p><h3>Arrive Hanoi</h3><p>Keep the first day light: lake walk, Old Quarter food, early night, and setup for cash, SIM/eSIM, and transport.</p></div>
<div><p class="vg-day">Day 2</p><h3>Hanoi orientation</h3><p>Use the capital properly before the route moves: food, museums or heritage, and a realistic start time for the next day.</p></div>
<div><p class="vg-day">Day 3</p><h3>Ninh Binh</h3><p>Move to Ninh Binh or take a carefully timed day trip. Overnight is better when you want the countryside to feel calm.</p></div>
<div><p class="vg-day">Day 4</p><h3>Ninh Binh or Hanoi reset</h3><p>Use this day for Trang An/Tam Coc, Mua Cave, or a return to Hanoi with enough margin before the bay decision.</p></div>
<div><p class="vg-day">Day 5</p><h3>Ha Long, Lan Ha, Cat Ba, or Bai Tu Long</h3><p>Choose one bay route by pier, pickup, cabin/time value, weather policy, and return logistics.</p></div>
<div><p class="vg-day">Day 6</p><h3>Return and final Hanoi night</h3><p>Do not put international departure pressure on the same day as a bay return unless the risk is clearly acceptable.</p></div>
<div><p class="vg-day">Day 7</p><h3>Depart Hanoi</h3><p>Use the last morning for an easy meal, airport buffer, and anything missed nearby. This is not the day for a new province.</p></div>
</div>
<!-- /wp:html -->

<!-- vg-itinerary-7day-season-pivots:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Season pivots for a 7-day route</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-7day-season-pivots">
<thead>
<tr><th>Travel window</th><th>Safer one-week bias</th><th>Protect</th><th>Do not pretend</th></tr>
</thead>
<tbody>
<tr><td data-label="Travel window">January-February</td><td data-label="Safer one-week bias">North compact can work, but keep weather and Tet flexibility realistic.</td><td data-label="Protect">Warm layers, cancellation terms, holiday transport.</td><td data-label="Do not pretend">That every small operator runs normally around major holidays.</td></tr>
<tr><td data-label="Travel window">March-April</td><td data-label="Safer one-week bias">Strong window for the northern default and central alternative.</td><td data-label="Protect">Bay quality, Ninh Binh depth, Hoi An/Hue balance.</td><td data-label="Do not pretend">Good weather makes a second region free.</td></tr>
<tr><td data-label="Travel window">May-August</td><td data-label="Safer one-week bias">Use slower days, better hotel locations, and heat-aware starts.</td><td data-label="Protect">Midday rest, private transfers, flexible sightseeing.</td><td data-label="Do not pretend">A dense schedule will feel premium in heat.</td></tr>
<tr><td data-label="Travel window">September-November</td><td data-label="Safer one-week bias">Be more careful with central-coast exposure; north or south can be better depending on exact dates.</td><td data-label="Protect">Weather alternatives and bay/city flexibility.</td><td data-label="Do not pretend">A beach-first plan is guaranteed late in the year.</td></tr>
<tr><td data-label="Travel window">December</td><td data-label="Safer one-week bias">North compact or south city-and-river can work well with price awareness.</td><td data-label="Protect">Good locations, early bookings, final-night logistics.</td><td data-label="Do not pretend">Holiday travel is only a weather question.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-itinerary-7day-swap-skip:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first in seven days</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-swap-skip vg-itinerary-7day-swap-skip">
<thead>
<tr><th>If you want...</th><th>Keep</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you want...">Northern scenery</td><td data-label="Keep">Hanoi, Ninh Binh, one bay decision.</td><td data-label="Skip first">Hoi An, Hue, Ho Chi Minh City, Mekong.</td><td data-label="Only add back when...">You extend to 10 days or more.</td></tr>
<tr><td data-label="If you want...">Central food and heritage</td><td data-label="Keep">Da Nang/Hoi An plus a protected Hue or My Son decision.</td><td data-label="Skip first">Northern bay and Ninh Binh.</td><td data-label="Only add back when...">The route becomes north plus central with enough nights.</td></tr>
<tr><td data-label="If you want...">Southern energy</td><td data-label="Keep">Ho Chi Minh City and Mekong, or HCMC depth with food/history.</td><td data-label="Skip first">A token Hanoi or Hoi An flight hop.</td><td data-label="Only add back when...">Flights and extra nights make it a real chapter.</td></tr>
<tr><td data-label="If you want...">Premium feeling</td><td data-label="Keep">Fewer bases, better location, smoother transfers, rest buffer.</td><td data-label="Skip first">Any attraction requiring an early start after a late transfer.</td><td data-label="Only add back when...">It improves the route more than recovery would.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-itinerary-7day-prebook-flex:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Prebook versus keep flexible</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-7day-prebook-flex">
<thead>
<tr><th>Item</th><th>Prebook when</th><th>Keep flexible when</th><th>Risk to verify</th></tr>
</thead>
<tbody>
<tr><td data-label="Item">International flights</td><td data-label="Prebook when">The route is built around one arrival/departure city.</td><td data-label="Keep flexible when">You may extend to 10 days or switch regions.</td><td data-label="Risk to verify">Entry permission and airport timing.</td></tr>
<tr><td data-label="Item">Bay cruise or Cat Ba module</td><td data-label="Prebook when">Cabin, route, pickup, and weather terms are clear.</td><td data-label="Keep flexible when">Weather is unstable or final-night pressure is high.</td><td data-label="Risk to verify">Cancellation and return time.</td></tr>
<tr><td data-label="Item">Ninh Binh hotel</td><td data-label="Prebook when">You want countryside calm and early starts.</td><td data-label="Keep flexible when">It might become a Hanoi day trip.</td><td data-label="Risk to verify">Transfer timing and luggage plan.</td></tr>
<tr><td data-label="Item">City food/walking time</td><td data-label="Prebook when">A special dinner or guide matters.</td><td data-label="Keep flexible when">Arrival energy is unknown.</td><td data-label="Risk to verify">Weather, sleep, and delay buffer.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-itinerary-7day-mistakes:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Short answers for common 7-day mistakes</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-travel-guide-mistakes vg-itinerary-7day-mistakes">
<details><summary>Should I include Hanoi, Hoi An, and Ho Chi Minh City in 7 days?</summary><p>No for most travelers. That route spends too much of the week in airports, transfers, and recovery. Choose one region or use the 10-day guide.</p></details>
<details><summary>Is Ha Long Bay worth it in one week?</summary><p>Yes only when the route protects pickup, weather policy, return timing, and a final-night buffer. If the bay makes departure fragile, use Ninh Binh or Hanoi depth instead.</p></details>
<details><summary>Is central Vietnam better for a 7-day trip?</summary><p>It can be, especially for lower movement, food, heritage, families, couples, and beach-adjacent hotels. Use Da Nang and Hoi An as the base decision, then add Hue only if the day is protected.</p></details>
<details><summary>When should I extend to 10 days?</summary><p>Extend when you want north plus central Vietnam, or when the cut list includes two places you genuinely care about. Adding nights is often better than adding speed.</p></details>
</div>
<!-- /wp:html -->

<!-- vg-itinerary-7day-booking-sequence:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Booking sequence for seven days</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-itinerary-7day-booking-sequence"} -->
<ul class="wp-block-list vg-check-list vg-itinerary-7day-booking-sequence">
<li>Confirm entry rules and passport details with the <a href="/plan/vietnam-evisa/">Vietnam E-Visa Guide</a>.</li>
<li>Choose the region using <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>.</li>
<li>Check route-season fit with <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>.</li>
<li>Price transfers, hotels, and the bay/driver/day-trip decision with <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>.</li>
<li>Book only the experiences that protect the spine: key hotels, bay/countryside module, and required intercity moves.</li>
<li>Keep ordinary city time flexible so delay, weather, heat, and sleep do not wreck the week.</li>
</ul>
<!-- /wp:list -->

<!-- vg-itinerary-7day-planning-audit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The 7-day planning audit</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-itinerary-7day-planning-audit">
<thead>
<tr><th>Audit question</th><th>Green flag</th><th>Red flag</th></tr>
</thead>
<tbody>
<tr><td data-label="Audit question">How many sleeping bases?</td><td data-label="Green flag">One or two.</td><td data-label="Red flag">Three or more before arrival/departure nights are counted.</td></tr>
<tr><td data-label="Audit question">How many high-friction highlights?</td><td data-label="Green flag">One: bay, Hue, Mekong, or island.</td><td data-label="Red flag">Bay plus Hue plus Mekong plus a cross-country flight.</td></tr>
<tr><td data-label="Audit question">Where is the final buffer?</td><td data-label="Green flag">Last night near the departure city or airport path.</td><td data-label="Red flag">Cruise return, long driver, or domestic flight on departure day.</td></tr>
<tr><td data-label="Audit question">What got cut?</td><td data-label="Green flag">The cut list is explicit and linked to a 10-day upgrade.</td><td data-label="Red flag">Nothing got cut; everything is just shorter.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits next</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> for the full planning order, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a> when one week feels too compressed, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a> when the south should become a real chapter, <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a> for the countryside decision, <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a> for bay timing, and <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a> when central Vietnam should lead.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-itinerary-7day-hero:v1',
    'concierge verdict' => 'vg-itinerary-7day-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-itinerary-7day-at-a-glance:v1',
    'photo grid' => 'vg-itinerary-7day-photo-grid:v1',
    'planning flow' => 'vg-itinerary-7day-planning-flow:v1',
    'route family' => 'vg-itinerary-7day-route-family:v1',
    'transfer pressure' => 'vg-itinerary-7day-transfer-pressure:v1',
    'day plan' => 'vg-itinerary-7day-day-plan:v1',
    'season pivots' => 'vg-itinerary-7day-season-pivots:v1',
    'swap skip' => 'vg-itinerary-7day-swap-skip:v1',
    'prebook flex' => 'vg-itinerary-7day-prebook-flex:v1',
    'mistakes' => 'vg-itinerary-7day-mistakes:v1',
    'booking sequence' => 'vg-itinerary-7day-booking-sequence:v1',
    'planning audit' => 'vg-itinerary-7day-planning-audit:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('7 Days in Vietnam guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => '7 Days in Vietnam',
    'post_name'      => '7-days-in-vietnam',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led 7-day Vietnam itinerary for international travelers choosing one focused region, max two bases, one high-friction highlight, route variants, booking order, and source trail.',
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
    vg_ops_fail('Could not publish 7 Days itinerary guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish 7 Days itinerary guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', '7 Days in Vietnam: Best One-Week Itinerary');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led 7-day Vietnam itinerary: choose a focused north, central, south, or beach route with max two bases, skip logic, photo proof, and booking checks.');
update_post_meta($page_id, 'rank_math_focus_keyword', '7 days in Vietnam');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether seven days in Vietnam should stay in one focused region, use max two bases, or become a 10-day route before adding a second high-friction highlight.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 18, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the 7 Days in Vietnam guide as the itinerary-length ladder entry for one-week trips, with a north compact default, central and southern alternatives, photo proof, transfer-pressure logic, skip rules, booking sequence, planning audit, Itineraries hub note, and inbound related-route support.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 18, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 18, 2026\nVietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked July 18, 2026\nVietnam.travel - Ninh Binh destination page - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh - checked July 18, 2026\nVietnam.travel - Ha Long destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-long - checked July 18, 2026\nVietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked July 18, 2026\nVietnam National Electronic Visa system - official e-visa portal for outside Viet Nam foreigners - https://evisa.gov.vn/e-visa/foreigners - checked July 18, 2026\nVietnam Railways - official rail fare lookup and booking reference - https://dsvn.vn/ - checked July 18, 2026\nWikimedia Commons image record - Ha Long Bay, Vietnam, view from above - https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg - license checked July 18, 2026\nWikimedia Commons image record - Hanoi Hoan Kiem Lake - https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg - license checked July 18, 2026\nWikimedia Commons image record - Trang An Landscape Complex, Ninh Binh Province - https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg - license checked July 18, 2026\nWikimedia Commons image record - Lan Ha Bay-Cat Ba Vietnam - https://commons.wikimedia.org/wiki/File:Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg - license checked July 18, 2026\nWikimedia Commons image record - Hoi An Ancient Town - https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg - license checked July 18, 2026\nWikimedia Commons image record - Hue Vietnam Citadel - https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg - license checked July 18, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'In one week, every hotel change must earn its place twice.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Original one-week route discipline framework: one focused region, max two bases, and one high-friction highlight.\nConcierge verdict rejects thin whole-country itineraries and gives a north compact default with central and southern alternatives.\nPhoto proof explains Hanoi, Ninh Binh, bay choice, Hoi An, and Hue as route decisions rather than decorative stops.\nAt-a-glance table answers whether a full-country week works, which route to choose, and when to extend.\nPlanning flow forces region, base count, hard highlight, exit buffer, and upgrade-versus-extend decisions before booking.\nRoute family and matrix separate north compact, central slow, southern city-river, and rest-first beach weeks.\nTransfer-pressure table names the fragile moves before they become sunk costs.\nDay-by-day default uses final-night buffer instead of risky same-day cruise returns.\nSeason pivots, swap-skip logic, prebook-versus-flexible table, mistake FAQ, booking sequence, and planning audit add practical judgment beyond rewritten itineraries.\nRelated decision chain links to Travel Guide, Best Time, Cost, 10 Days, 14 Days, Ninh Binh, Ha Long, and central-base comparison.\nLicensed route photography with visible credit links.\nVisible source trail and update log.");
$related_routes = "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before choosing a one-week route.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Use this when the one-week cut list hurts or north plus central Vietnam should both stay.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use this when the south should become a real chapter instead of a token add-on.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check whether your month supports the one-region route you are considering.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price hotels, bay/cruise choices, private transfers, and route buffers before booking.\nNinh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide whether countryside should be an overnight, day trip, or skip in a northern week.\nHa Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Audit whether the bay decision deserves the week before paying a cruise deposit.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.\nDa Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Use this when central Vietnam should replace the northern default.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.";
vg_ops_assert_related_route_meta_links_are_published('7 Days in Vietnam related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero: Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0. Body images: Hanoi by Alex 69200 vx, CC BY-SA 4.0; Trang An by Jakub Halun, CC BY 4.0; Lan Ha Bay-Cat Ba by Saaremees, CC BY-SA 4.0; Hoi An by Steffen Schmitz, CC BY-SA 4.0; Hue by CEphoto, Uwe Aranas, CC BY-SA 3.0.');
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
    vg_ops_fail('7 Days itinerary guide was updated but is not published.');
}

vg_ops_refresh_itineraries_hub();
vg_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published 7 Days in Vietnam itinerary guide: {$page_id} {$updated_permalink}");

<?php
/**
 * Publish the Hanoi Travel Guide pillar using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * VG_FORCE_HANOI_TRAVEL_GUIDE_REPUBLISH=1 wp eval-file ops/apply-hanoi-travel-guide.php --allow-root
 *
 * Repair only hub/related-route side effects with:
 * VG_REPAIR_HANOI_TRAVEL_GUIDE_LINKS=1 wp eval-file ops/apply-hanoi-travel-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_hanoi_travel_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_hanoi_travel_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_hanoi_travel_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_HANOI_TRAVEL_GUIDE_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_hanoi_travel_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_HANOI_TRAVEL_GUIDE_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_hanoi_travel_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_hanoi_travel_ops_fail('Could not resolve a valid WordPress author for the Hanoi Travel Guide.');
}

function vg_hanoi_travel_ops_internal_path_from_href(string $href): ?string
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

function vg_hanoi_travel_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_hanoi_travel_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_hanoi_travel_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_travel_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_hanoi_travel_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_hanoi_travel_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_hanoi_travel_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_hanoi_travel_ops_log("Validated {$label} internal page links are published.");
}

function vg_hanoi_travel_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_hanoi_travel_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_hanoi_travel_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_hanoi_travel_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_hanoi_travel_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_hanoi_travel_ops_log("Validated {$label} related-route links are published.");
}

function vg_hanoi_travel_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_hanoi_travel_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
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
            vg_hanoi_travel_ops_fail("Could not confidently refresh {$label}.");
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
        vg_hanoi_travel_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_hanoi_travel_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_hanoi_travel_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_hanoi_travel_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_hanoi_travel_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_hanoi_travel_ops_published_page_exists('destinations/hanoi-travel-guide')) {
        vg_hanoi_travel_ops_log('Skipped Destinations hub refresh: Hanoi Travel Guide is not published.');
        return;
    }

    $marker = '<!-- vg-hanoi-travel-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use Hanoi as the northern route anchor</h3><p>The <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a> helps international travelers decide how many nights the capital deserves, where to stay, what to prioritize, how to handle Noi Bai arrival, and when the city should feed into Ninh Binh, Ha Long Bay, Cat Ba, Sapa, Ha Giang, or Central Vietnam.</p></div>
<!-- /wp:group -->
HTML;

    vg_hanoi_travel_ops_assert_internal_page_links_are_published('Destinations hub Hanoi Travel Guide note', $block);
    vg_hanoi_travel_ops_upsert_marked_group($hub, 'Destinations hub Hanoi Travel Guide note', $marker, $block);
}

function vg_hanoi_travel_ops_refresh_best_things_hanoi_support(): void
{
    $page = get_page_by_path('destinations/best-things-to-do-in-hanoi', OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_travel_ops_log('Skipped Best Things Hanoi support refresh: page was not found or is not published.');
        return;
    }

    if (! vg_hanoi_travel_ops_published_page_exists('destinations/hanoi-travel-guide')) {
        vg_hanoi_travel_ops_log('Skipped Best Things Hanoi support refresh: Hanoi Travel Guide is not published.');
        return;
    }

    $marker = '<!-- vg-hanoi-travel-best-things-support:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-route-support"} -->
<div class="wp-block-group vg-route-support"><h2>Start with the Hanoi Travel Guide if you are still choosing the city role</h2><p>Use the <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a> before narrowing the attractions list. It answers the bigger route questions: how many Hanoi nights to protect, where to stay, how Noi Bai arrival affects the first day, and when Hanoi should connect to Ninh Binh, the bay, mountains, or Central Vietnam.</p></div>
<!-- /wp:group -->
HTML;

    vg_hanoi_travel_ops_assert_internal_page_links_are_published('Best Things Hanoi Travel Guide support block', $block);
    vg_hanoi_travel_ops_upsert_marked_group($page, 'Best Things Hanoi Travel Guide support block', $marker, $block);
}

function vg_hanoi_travel_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_travel_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_hanoi_travel_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_hanoi_travel_ops_log("Skipped related-route refresh for {$label}: Hanoi Travel Guide line is already current.");
        return;
    }

    update_post_meta($page->ID, 'vg_eeat_related_routes', implode("\n", $next_lines));

    vg_hanoi_travel_ops_log("Upserted Hanoi Travel Guide related route to {$label}: {$page->ID}");
}

function vg_hanoi_travel_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Decide how Hanoi should anchor the north, how many nights to protect, where to stay, and when to connect to Ninh Binh, the bay, mountains, or Central Vietnam.';

    foreach (
        [
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
        ] as $path => $label
    ) {
        vg_hanoi_travel_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

function vg_hanoi_travel_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_hanoi_travel_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_hanoi_travel_ops_fail('Could not find published /destinations/ parent page.');
}

$page = get_page_by_path('destinations/hanoi-travel-guide', OBJECT, 'page');

if (vg_hanoi_travel_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_hanoi_travel_ops_fail('Repair mode requires Hanoi Travel Guide to already be published.');
    }

    vg_hanoi_travel_ops_refresh_destinations_hub();
    vg_hanoi_travel_ops_refresh_best_things_hanoi_support();
    vg_hanoi_travel_ops_refresh_inbound_related_routes();
    vg_hanoi_travel_ops_log("Repaired Hanoi Travel Guide side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_hanoi_travel_ops_force_republish_enabled()) {
    vg_hanoi_travel_ops_fail('Hanoi Travel Guide is not a draft. Set VG_FORCE_HANOI_TRAVEL_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_hanoi_travel_ops_log("Preflight Hanoi Travel Guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_hanoi_travel_ops_log('Preflight Hanoi Travel Guide: no existing page found; creating a child page under /destinations/.');
}

$review_date = 'July 23, 2026';
$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg');
$old_quarter_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/Cho_Dong_Xuan%2C_Old_Quarter%2C_Hanoi%2C_Vietnam_%285245806573%29.jpg/1920px-Cho_Dong_Xuan%2C_Old_Quarter%2C_Hanoi%2C_Vietnam_%285245806573%29.jpg');
$old_quarter_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cho_Dong_Xuan,_Old_Quarter,_Hanoi,_Vietnam_(5245806573).jpg');
$temple_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/Main_gate_of_the_Temple_of_Literature%2C_Hanoi%2C_Vietnam%2C_20240123_0929_3068.jpg/1920px-Main_gate_of_the_Temple_of_Literature%2C_Hanoi%2C_Vietnam%2C_20240123_0929_3068.jpg');
$temple_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Main_gate_of_the_Temple_of_Literature,_Hanoi,_Vietnam,_20240123_0929_3068.jpg');
$thang_long_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0f/Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg/1920px-Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg');
$thang_long_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg');
$noi_bai_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/38/Noi_Bai_International_Airport_Terminal_2_Night_View.JPG/1920px-Noi_Bai_International_Airport_Terminal_2_Night_View.JPG');
$noi_bai_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_Terminal_2_Night_View.JPG');
$long_bien_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/c/c5/Long_Bien_Bridge.jpg');
$long_bien_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Long_Bien_Bridge.jpg');

$content = <<<HTML
<!-- vg-hanoi-travel-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover vg-hanoi-travel-hero"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover vg-hanoi-travel-hero" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hoan Kiem Lake in central Hanoi, Vietnam" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed Hanoi route pillar - Updated {$review_date}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Hanoi Travel Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Hanoi is the capital chapter that teaches the rhythm of northern Vietnam before the route starts moving. Use it to settle after arrival, choose the right central base, understand food and street life, add one or two culture stops, and decide whether the next move should be Ninh Binh, Ha Long Bay, Cat Ba, Sapa, Ha Giang, or Central Vietnam.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: Hanoi becomes more valuable when it is not treated as a checklist. The best first answer is usually a strong central stay, one orientation day, one deeper city block, and a clean outbound transfer.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">Alex 69200 vx / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-hanoi-travel-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-hanoi-travel-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-hanoi-travel-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Hanoi is the best first northern base for most international visitors.</strong> Stay central for two or three nights when the route includes Ninh Binh, a bay cruise, or a mountain extension. Use one night only for a late arrival or very tight itinerary, and add a fourth night only when the city itself, food, museums, or day trips deserve protected time.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-hanoi-travel-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-hanoi-travel-shortlist">
<li><strong>Best first-time default:</strong> two or three central nights before moving to Ninh Binh, Ha Long/Lan Ha, Cat Ba, Sapa, Ha Giang, or Central Vietnam.</li>
<li><strong>Best first base:</strong> Hoan Kiem or the calmer Old Quarter edge when walking, food, pickup, and orientation matter most.</li>
<li><strong>Best slower base:</strong> French Quarter, Ba Dinh, or West Lake only when comfort, quiet, embassy/business errands, or longer stays matter more than first-time density.</li>
<li><strong>Best avoid rule:</strong> do not stack Hanoi, Ninh Binh, bay cruise, Sapa, and Ha Giang into a short route because every name appears online.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-hanoi-travel-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-hanoi-travel-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-hanoi-travel-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Fast answer</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How to use Hanoi in a Vietnam route</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-travel-glance-table">
<tbody>
<tr><td data-label="Question"><strong>How many nights?</strong></td><td data-label="Answer">Two nights is the practical minimum. Three nights is the best first answer. Four nights works when food, museums, day trips, or slower pacing are part of the trip.</td></tr>
<tr><td data-label="Question"><strong>Where should first-timers stay?</strong></td><td data-label="Answer">Hoan Kiem, the Old Quarter edge, or a calm French Quarter pocket. Choose by pickup logistics, walking radius, sleep, and the next transfer.</td></tr>
<tr><td data-label="Question"><strong>What should Hanoi do in the route?</strong></td><td data-label="Answer">Orientation, food confidence, capital history, arrival recovery, and a clean northern launch point.</td></tr>
<tr><td data-label="Question"><strong>What is the biggest mistake?</strong></td><td data-label="Answer">Using Hanoi only as a staging night, then forcing too many northern landscape moves into the same short itinerary.</td></tr>
<tr><td data-label="Question"><strong>What should you verify close to travel?</strong></td><td data-label="Answer">Noi Bai arrival timing, weather, museum/heritage opening details, pickup point, and whether the next transfer needs a lighter previous evening.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-hanoi-travel-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: why Hanoi is a route anchor</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The photos show the jobs Hanoi can do: orientation, street rhythm, heritage, arrival logistics, and a slower bridge between city and northern landscapes.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-hanoi-travel-photo-grid" aria-label="Hanoi Travel Guide proof photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Hoan Kiem Lake in central Hanoi" loading="lazy" decoding="async"><figcaption>Hoan Kiem solves first-arrival orientation better than another long transfer. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Alex 69200 vx / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$old_quarter_image}" alt="Dong Xuan Market in Hanoi Old Quarter" loading="lazy" decoding="async"><figcaption>The Old Quarter is useful when food, walking, and pickup points matter more than silence. Image: <a href="{$old_quarter_credit_url}" target="_blank" rel="license noopener">yeowatzup / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$temple_image}" alt="Temple of Literature gate in Hanoi" loading="lazy" decoding="async"><figcaption>The Temple of Literature is a clean first culture stop when the city has limited time. Image: <a href="{$temple_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$thang_long_image}" alt="Imperial Citadel of Thang Long in Hanoi" loading="lazy" decoding="async"><figcaption>Thang Long adds capital-history depth when UNESCO context matters to the route. Image: <a href="{$thang_long_credit_url}" target="_blank" rel="license noopener">katiebordner / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$noi_bai_image}" alt="Noi Bai International Airport Terminal 2 in Hanoi" loading="lazy" decoding="async"><figcaption>Noi Bai arrival timing can change whether the first night should be active or recovery-only. Image: <a href="{$noi_bai_credit_url}" target="_blank" rel="license noopener">Christakis Mina / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$long_bien_image}" alt="Long Bien Bridge in Hanoi" loading="lazy" decoding="async"><figcaption>Hanoi rewards slower observation when the route gives the city room to breathe. Image: <a href="{$long_bien_credit_url}" target="_blank" rel="license noopener">TheRollo76 / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-hanoi-travel-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Source diversity: what the evidence can and cannot prove</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This guide uses official sources lightly in the body and keeps the fuller trail in metadata and the source log. Sources can prove destination frame, heritage status, airport context, and weather references; they cannot prove your jet lag, exact hotel noise, or whether a social-media stop deserves your limited first day.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-travel-source-diversity">
<thead><tr><th>Source job</th><th>Primary use</th><th>What it cannot prove</th></tr></thead>
<tbody>
<tr><td data-label="Source job">Official destination frame</td><td data-label="Primary use"><a href="https://vietnam.travel/places-to-go/northern-vietnam/ha-noi" target="_blank" rel="noopener">Vietnam.travel Ha Noi</a> and Northern Vietnam context.</td><td data-label="What it cannot prove">The right hotel block, arrival fatigue, or how much city time your route can hold.</td></tr>
<tr><td data-label="Source job">Heritage status</td><td data-label="Primary use"><a href="https://whc.unesco.org/en/list/1328/" target="_blank" rel="noopener">UNESCO Thang Long</a> and official site context.</td><td data-label="What it cannot prove">Whether a short first visit should choose Thang Long over food, rest, or a museum.</td></tr>
<tr><td data-label="Source job">Airport movement</td><td data-label="Primary use"><a href="https://vietnamairport.vn/en/noi-bai-airport" target="_blank" rel="noopener">Noi Bai airport</a> for official airport context.</td><td data-label="What it cannot prove">Your queue, app pickup, late-night traffic, or whether the first evening should stay light.</td></tr>
<tr><td data-label="Source job">Weather and comfort</td><td data-label="Primary use"><a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a> and Vietnam.travel weather references.</td><td data-label="What it cannot prove">Your heat tolerance, walking rhythm, or whether rain should move the day indoors.</td></tr>
<tr><td data-label="Source job">Image-license proof</td><td data-label="Primary use">Wikimedia records for Hoan Kiem, Old Quarter, Temple of Literature, Thang Long, Noi Bai, and Long Bien imagery.</td><td data-label="What it cannot prove">Current crowding, room quality, construction, or day-of access restrictions.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-travel-route-role:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Choose Hanoi by route role</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Hanoi is not the same page in every itinerary. It can be a soft landing, food/culture chapter, northern transport base, or final recovery stop before departure.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-travel-route-role">
<thead><tr><th>Route role</th><th>Best for</th><th>Protect</th><th>Do not do this</th></tr></thead>
<tbody>
<tr><td data-label="Route role">Arrival anchor</td><td data-label="Best for">Most first-time visitors arriving long-haul or crossing into the north.</td><td data-label="Protect">Central hotel, first meal, short walk, sleep, and a realistic second day.</td><td data-label="Do not do this">Book a long day trip immediately after arrival.</td></tr>
<tr><td data-label="Route role">Northern launch base</td><td data-label="Best for">Ninh Binh, Ha Long/Lan Ha, Cat Ba, Sapa, Ha Giang, or Pu Luong movement.</td><td data-label="Protect">Pickup clarity, luggage plan, early breakfast, and a lighter previous evening.</td><td data-label="Do not do this">Change hotel areas for tiny savings before a pickup-heavy route.</td></tr>
<tr><td data-label="Route role">Food and city chapter</td><td data-label="Best for">Travelers who want street food, cafes, museums, markets, and capital rhythm.</td><td data-label="Protect">Two full city blocks and one flexible evening.</td><td data-label="Do not do this">Turn every evening into recovery from daytime transfers.</td></tr>
<tr><td data-label="Route role">Return buffer</td><td data-label="Best for">Final Hanoi night before an international flight or train/flight chain.</td><td data-label="Protect">Airport timing, packing, cash/card cleanup, and easy dinner.</td><td data-label="Do not do this">Add a distant final-day excursion before a fragile departure.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-travel-stay-areas:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where to stay in Hanoi after you know the route role</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this filter for the quick answer, then read <a href="/destinations/where-to-stay-in-hanoi/">Where to Stay in Hanoi</a> when the exact base will affect first-night recovery, pickup timing, noise, walking, or the next move to Ninh Binh, the bay, Cat Ba, or the mountains. If the real choice is already narrowed to central energy, calmer central comfort, or lake-side space, compare <a href="/compare/old-quarter-vs-french-quarter-vs-west-lake/">Old Quarter vs French Quarter vs West Lake</a> before booking.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-travel-stay-areas">
<thead><tr><th>Area</th><th>Best job</th><th>Tradeoff</th><th>Verdict</th></tr></thead>
<tbody>
<tr><td data-label="Area">Hoan Kiem / Old Quarter edge</td><td data-label="Best job">First-time walking, food, pickup convenience, short Hanoi stays.</td><td data-label="Tradeoff">Noise and street density vary by exact block.</td><td data-label="Verdict">Best first default when the route has only two or three Hanoi nights.</td></tr>
<tr><td data-label="Area">French Quarter / south Hoan Kiem</td><td data-label="Best job">Calmer central rhythm, better hotels, lake access, lower street pressure.</td><td data-label="Tradeoff">Less intense Old Quarter texture at the doorstep.</td><td data-label="Verdict">Good premium or comfort-first central answer.</td></tr>
<tr><td data-label="Area">Ba Dinh</td><td data-label="Best job">History/embassy/business context, quieter nights, wider roads.</td><td data-label="Tradeoff">Less first-time food and nightlife density.</td><td data-label="Verdict">Useful when monuments or work errands matter more than street-life immersion.</td></tr>
<tr><td data-label="Area">West Lake</td><td data-label="Best job">Longer stays, families, apartments, cafes, softer pace.</td><td data-label="Tradeoff">More rides to classic first-time Hanoi.</td><td data-label="Verdict">Better for repeat/longer stays than a two-night first visit.</td></tr>
<tr><td data-label="Area">Noi Bai airport-side</td><td data-label="Best job">Late arrivals, early flights, separate tickets, fragile onward chain.</td><td data-label="Tradeoff">It sacrifices the city experience.</td><td data-label="Verdict">Use only when flight risk is the reason.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
<!-- wp:paragraph {"className":"vg-section-link"} -->
<p class="vg-section-link"><a href="/destinations/where-to-stay-in-hanoi/">Choose the Hanoi stay area</a></p>
<!-- /wp:paragraph -->

<!-- vg-hanoi-travel-day-plans:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hanoi day plans by available time</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-travel-day-plans">
<thead><tr><th>Time in Hanoi</th><th>Best plan</th><th>Optional add-on</th><th>Cut first</th></tr></thead>
<tbody>
<tr><td data-label="Time in Hanoi">Arrival evening</td><td data-label="Best plan">Hotel, nearby dinner, short lake or Old Quarter walk.</td><td data-label="Optional add-on">One cafe or dessert stop.</td><td data-label="Cut first">Paid food tour, distant museum, or next-day early day trip.</td></tr>
<tr><td data-label="Time in Hanoi">One full day</td><td data-label="Best plan">Hoan Kiem, Old Quarter, food route, one culture stop.</td><td data-label="Optional add-on">Temple of Literature or Women's Museum depending on heat/rain.</td><td data-label="Cut first">Multiple heritage stops and viral photo detours.</td></tr>
<tr><td data-label="Time in Hanoi">Two full days</td><td data-label="Best plan">Food/cafe rhythm, Temple or Thang Long, one museum, and one slower neighborhood block.</td><td data-label="Optional add-on">Craft/market walk, Long Bien, guided history, or a light half-day trip.</td><td data-label="Cut first">A day trip that makes Hanoi itself disappear.</td></tr>
<tr><td data-label="Time in Hanoi">Three or four nights</td><td data-label="Best plan">Two city days plus one cleaner northern launch or day-trip decision.</td><td data-label="Optional add-on">Ninh Binh day trip, craft village, or extra food/culture layer.</td><td data-label="Cut first">Trying to preview every northern landscape from Hanoi.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-travel-transport-logistics:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Arrival and transport logistics</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Hanoi transport planning is mostly about timing and energy. The airport is far enough from the center that a late arrival should not pretend to be a full city evening, and early pickups should shape the previous night.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-travel-transport-logistics">
<thead><tr><th>Movement</th><th>Best default</th><th>When to upgrade</th><th>What to verify</th></tr></thead>
<tbody>
<tr><td data-label="Movement">Noi Bai to central Hanoi</td><td data-label="Best default">Ride-hailing, official taxi, or hotel pickup depending on arrival time and phone confidence.</td><td data-label="When to upgrade">Late arrival, family luggage, premium route, or first-time anxiety.</td><td data-label="What to verify">Terminal, pickup point, SIM/eSIM readiness, payment, and hotel check-in.</td></tr>
<tr><td data-label="Movement">Hanoi to Ninh Binh</td><td data-label="Best default">Train, bus/limousine, private car, or organized day trip by route job.</td><td data-label="When to upgrade">Short trip, family route, early photography, or overnight luggage complexity.</td><td data-label="What to verify">Departure point, luggage, return timing, and whether Ninh Binh deserves a night.</td></tr>
<tr><td data-label="Movement">Hanoi to bay cruise</td><td data-label="Best default">Cruise pickup or coordinated transfer.</td><td data-label="When to upgrade">Premium cruise, tight itinerary, or airport/bay same-day chain.</td><td data-label="What to verify">Pickup district, port, traffic buffer, and cruise cancellation/weather terms.</td></tr>
<tr><td data-label="Movement">Hanoi to mountains</td><td data-label="Best default">Staged bus/train/private transfer by safety, sleep, and route length.</td><td data-label="When to upgrade">Ha Giang safety, family comfort, or fragile weather windows.</td><td data-label="What to verify">Insurance, road conditions, pickup, arrival time, and next-day recovery.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-travel-weather-pivots:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Weather pivots that keep Hanoi evergreen</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Weather details change, but the planning logic holds: protect indoor alternatives, shade, cafe breaks, and a lighter day before long transfers.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-travel-weather-pivots">
<thead><tr><th>Condition</th><th>Better Hanoi move</th><th>Route consequence</th></tr></thead>
<tbody>
<tr><td data-label="Condition">Heat and humidity</td><td data-label="Better Hanoi move">Early lake walk, midday indoor/cafe block, shorter walking loops.</td><td data-label="Route consequence">Do not spend all energy before Ninh Binh, bay, or mountain pickup.</td></tr>
<tr><td data-label="Condition">Rain</td><td data-label="Better Hanoi move">Women's Museum, Temple of Literature if manageable, food/cafe route, hotel-neighborhood plan.</td><td data-label="Route consequence">Keep outdoor photo stops flexible and watch same-week weather before bay/mountain movement.</td></tr>
<tr><td data-label="Condition">Cooler winter days</td><td data-label="Better Hanoi move">Longer walks, heritage stops, cafes, and northern food focus.</td><td data-label="Route consequence">Mountains may feel colder than Hanoi; pack and insure accordingly.</td></tr>
<tr><td data-label="Condition">Typhoon or storm disruption in the north</td><td data-label="Better Hanoi move">Use Hanoi as a buffer and verify bay/mountain transport before committing.</td><td data-label="Route consequence">A flexible Hanoi night can protect the whole northern route.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-travel-cost-comfort:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cost and comfort decisions</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Hanoi can be inexpensive, but the lowest room price is not always the lowest trip cost. A better location, smoother airport transfer, or private northern move can save more value than it costs.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-travel-cost-comfort">
<thead><tr><th>Decision</th><th>Cheap version</th><th>Better-value upgrade</th><th>When it is worth it</th></tr></thead>
<tbody>
<tr><td data-label="Decision">Hotel area</td><td data-label="Cheap version">Deeper Old Quarter or farther central edge.</td><td data-label="Better-value upgrade">Calmer Hoan Kiem/French Quarter block.</td><td data-label="When it is worth it">Short stays, early pickups, families, light sleepers, premium route.</td></tr>
<tr><td data-label="Decision">Airport transfer</td><td data-label="Cheap version">Public bus or self-managed app ride.</td><td data-label="Better-value upgrade">Hotel pickup or reliable car after late arrival.</td><td data-label="When it is worth it">Long-haul arrival, late night, family luggage, low phone confidence.</td></tr>
<tr><td data-label="Decision">Food/culture support</td><td data-label="Cheap version">Self-led wandering.</td><td data-label="Better-value upgrade">Selective food walk or guide for a single context-heavy block.</td><td data-label="When it is worth it">First night confidence, solo travelers, limited time, culture-led route.</td></tr>
<tr><td data-label="Decision">Northern transfer</td><td data-label="Cheap version">Bus or shared transfer.</td><td data-label="Better-value upgrade">Private car, cruise pickup, or staged route when luggage and timing matter.</td><td data-label="When it is worth it">Family, premium, short route, early flight, weather uncertainty.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-travel-mistakes-skip:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mistakes to avoid</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hanoi-travel-mistakes-skip">
<thead><tr><th>Mistake</th><th>Why it hurts</th><th>Better correction</th></tr></thead>
<tbody>
<tr><td data-label="Mistake">Treating Hanoi as one airport night</td><td data-label="Why it hurts">You miss the city that makes the north easier to read.</td><td data-label="Better correction">Protect two nights unless the route is extremely tight.</td></tr>
<tr><td data-label="Mistake">Staying far from the first-time center to save a small amount</td><td data-label="Why it hurts">Taxi friction, pickup confusion, and lost evenings can outweigh room savings.</td><td data-label="Better correction">Choose the hotel area by route job and sleep needs.</td></tr>
<tr><td data-label="Mistake">Adding every northern icon</td><td data-label="Why it hurts">Ninh Binh, bay, Cat Ba, Sapa, and Ha Giang cannot all be first priority on a short trip.</td><td data-label="Better correction">Choose one countryside/bay decision first, then one mountain decision only if time remains.</td></tr>
<tr><td data-label="Mistake">Booking a heavy first day after a late arrival</td><td data-label="Why it hurts">Jet lag weakens food, safety, and transfer judgment.</td><td data-label="Better correction">Make arrival night recovery-led and move serious sightseeing to the first full day.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hanoi-travel-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before booking Hanoi</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-hanoi-travel-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-hanoi-travel-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/northern-vietnam/ha-noi" target="_blank" rel="noopener">Vietnam.travel Ha Noi</a> and the <a href="/destinations/best-things-to-do-in-hanoi/">Best Things to Do in Hanoi</a> guide before turning the capital into a single staging night.</li>
<li>Use <a href="https://vietnamairport.vn/en/noi-bai-airport" target="_blank" rel="noopener">Noi Bai airport</a>, <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>, and <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before locking arrival logistics.</li>
<li>Use <a href="https://whc.unesco.org/en/list/1328/" target="_blank" rel="noopener">UNESCO Thang Long</a>, official attraction sites, and current opening information before building the day around a heritage stop.</li>
<li>Use <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, and same-week forecasts before bay, mountain, or storm-sensitive moves.</li>
<li>Use <a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding every northern extension.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hanoi-travel-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hanoi Travel Guide FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-hanoi-travel-faq">
<details><summary>Is Hanoi worth visiting for first-time travelers?</summary><p>Yes. Hanoi is the best first northern base for most international visitors because it gives arrival recovery, food confidence, capital context, and clean access to Ninh Binh, Ha Long Bay, Cat Ba, Sapa, Ha Giang, or Central Vietnam.</p></details>
<details><summary>How many days do you need in Hanoi?</summary><p>Two nights is the practical minimum. Three nights is the best first answer. Four nights works when you want both a real city chapter and a day trip or slower food/culture pacing.</p></details>
<details><summary>Where should I stay in Hanoi?</summary><p>Most first-time visitors should stay around Hoan Kiem, the Old Quarter edge, or a calmer French Quarter pocket. West Lake and Ba Dinh are better for longer stays, repeat travelers, business/embassy needs, or travelers who prioritize quiet over first-time density.</p></details>
<details><summary>Should I visit Hanoi before Ninh Binh or Ha Long Bay?</summary><p>Usually yes. Hanoi works best before the first major northern landscape move because it helps with orientation, sleep, phone/cash setup, food confidence, and transfer coordination.</p></details>
<details><summary>Can I skip Hanoi and go straight to the mountains or bay?</summary><p>You can, but it is rarely the cleanest first-trip choice after a long arrival. Skip Hanoi only when flight timing, a repeat visit, or a very specific landscape-first route makes the city a weak use of limited nights.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then use <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, and <a href="/destinations/best-things-to-do-in-hanoi/">Best Things to Do in Hanoi</a> before choosing day trips, mountain extensions, or a flight south.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-hanoi-travel-hero:v1',
    'concierge verdict' => 'vg-hanoi-travel-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-hanoi-travel-at-a-glance:v1',
    'photo proof' => 'vg-hanoi-travel-photo-grid:v1',
    'source diversity' => 'vg-hanoi-travel-source-diversity:v1',
    'route role' => 'vg-hanoi-travel-route-role:v1',
    'stay areas' => 'vg-hanoi-travel-stay-areas:v1',
    'day plans' => 'vg-hanoi-travel-day-plans:v1',
    'transport logistics' => 'vg-hanoi-travel-transport-logistics:v1',
    'weather pivots' => 'vg-hanoi-travel-weather-pivots:v1',
    'cost comfort' => 'vg-hanoi-travel-cost-comfort:v1',
    'mistakes skip logic' => 'vg-hanoi-travel-mistakes-skip:v1',
    'live checks' => 'vg-hanoi-travel-live-checks:v1',
    'FAQ' => 'vg-hanoi-travel-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_hanoi_travel_ops_assert_required_content_markers($content, $required_content_markers);
vg_hanoi_travel_ops_assert_internal_page_links_are_published('Hanoi Travel Guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Hanoi Travel Guide',
    'post_name'      => 'hanoi-travel-guide',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_hanoi_travel_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Hanoi Travel Guide for international visitors deciding how many nights to stay, where to base, what to prioritize, and how Hanoi connects to northern Vietnam.',
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
    vg_hanoi_travel_ops_fail('Could not publish Hanoi Travel Guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_hanoi_travel_ops_fail('Could not publish Hanoi Travel Guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Hanoi Travel Guide: Where to Stay, What to Do and Route Fit');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Hanoi Travel Guide for international visitors: how many nights, where to stay, Noi Bai arrival, weather, costs, mistakes, and north-route fit.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Hanoi Travel Guide');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide how Hanoi should anchor a Vietnam route: arrival base, food and culture chapter, northern launch point, final buffer, or skip.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Hanoi Travel Guide with a concierge verdict, at-a-glance decision table, licensed photo proof, source-diversity panel, route-role matrix, Where to Stay in Hanoi support link, stay-area filter, day-plan table, transport/logistics guide, weather pivots, cost/comfort decisions, mistakes/skip logic, live checks, FAQ, Best Things Hanoi support link, Destinations hub note, inbound related routes, source trail, and update log.');
$sources_checked = implode(
    "\n",
    [
        "Vietnam.travel - Ha Noi destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-noi - checked {$review_date}",
        "Vietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked {$review_date}",
        "Vietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}",
        "National Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked {$review_date}",
        "Noi Bai International Airport - https://vietnamairport.vn/en/noi-bai-airport - checked {$review_date}",
        "UNESCO World Heritage Centre - Central Sector of the Imperial Citadel of Thang Long - Ha Noi - https://whc.unesco.org/en/list/1328/ - checked {$review_date}",
        "Imperial Citadel of Thang Long official site - https://hoangthanhthanglong.vn/en/ - checked {$review_date}",
        "Temple of Literature official site - https://vanmieu.gov.vn/en/ - checked {$review_date}",
        "Vietnamese Women's Museum official site - https://baotangphunu.org.vn/en - checked {$review_date}",
        "Wikimedia Commons image record - Hanoi Hoan Kiem Lake - https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Cho Dong Xuan, Old Quarter, Hanoi - https://commons.wikimedia.org/wiki/File:Cho_Dong_Xuan,_Old_Quarter,_Hanoi,_Vietnam_(5245806573).jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Main gate of the Temple of Literature, Hanoi - https://commons.wikimedia.org/wiki/File:Main_gate_of_the_Temple_of_Literature,_Hanoi,_Vietnam,_20240123_0929_3068.jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Central Sector of the Imperial Citadel of Thang Long - Hanoi - https://commons.wikimedia.org/wiki/File:Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg - license checked {$review_date}",
        "Wikimedia Commons image record - Noi Bai International Airport Terminal 2 Night View - https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_Terminal_2_Night_View.JPG - license checked {$review_date}",
        "Wikimedia Commons image record - Long Bien Bridge - https://commons.wikimedia.org/wiki/File:Long_Bien_Bridge.jpg - license checked {$review_date}",
    ]
);
update_post_meta($page_id, 'vg_eeat_sources_checked', $sources_checked);
update_post_meta($page_id, 'vg_eeat_field_note', 'Hanoi should be planned as the north route anchor: first-arrival recovery, food confidence, central base choice, capital context, and transfer control before Ninh Binh, bay, mountain, or Central Vietnam movement.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Hanoi Travel Guide built as a route-role decision guide rather than a generic things-to-do article.\nConcierge verdict separates one-night staging, two- or three-night first default, and four-night deeper city use.\nAt-a-glance table answers nights, stay area, route job, biggest mistake, and live checks.\nLicensed photo proof covers Hoan Kiem, Old Quarter/Dong Xuan, Temple of Literature, Thang Long, Noi Bai, and Long Bien.\nSource-diversity panel explains what destination, heritage, airport, weather, and image-license sources can and cannot prove.\nRoute-role table separates arrival anchor, northern launch base, food/city chapter, and return buffer.\nWhere to Stay in Hanoi support keeps hotel-area choice in a deeper base-selection guide rather than overloading the pillar.\nDay-plan table separates arrival evening, one full day, two full days, and three/four-night Hanoi routes.\nTransport table ties Noi Bai, Ninh Binh, bay cruise, and mountain movement to route risk.\nWeather, cost, comfort, mistakes, FAQ, source trail, update log, and related routes keep the guide durable.");
$related_routes = "Where to Stay in Hanoi | /destinations/where-to-stay-in-hanoi/ | Choose Hoan Kiem, Old Quarter edge, French Quarter, Ba Dinh, West Lake, or Noi Bai airport-side by first-night recovery, pickup logic, sleep, and route launch.\nOld Quarter vs French Quarter vs West Lake | /compare/old-quarter-vs-french-quarter-vs-west-lake/ | Compare Hanoi's main sleep zones by walking energy, calmer premium central access, lake-side space, pickup clarity, and cost trade-offs.\nBest Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Narrow the attraction list after deciding Hanoi's route role.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before choosing northern route shape.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Decide whether Hanoi, Ninh Binh, bay, mountains, or Central Vietnam belong in the destination set.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether northern Vietnam should lead the trip.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check north weather, heat, rain, cold, and bay/mountain timing before locking Hanoi.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Count Noi Bai arrival, day-trip pickups, trains, cruise transfers, and onward flights.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price hotel area, airport transfer, food/culture blocks, and private northern movement.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Prepare arrival cash, cards, deposits, and ATM habits before Hanoi check-in.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, ride-hailing, hotel contact, and pickup coordination from becoming avoidable friction.\nVietnam E-Visa | /plan/vietnam-evisa/ | Recheck official entry timing before building Hanoi around arrival assumptions.\n7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Decide whether a one-week route should stay north-focused.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Hanoi can support Ninh Binh, bay, and Central Vietnam without rushing.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when two weeks can add a real northern base and a central chapter.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use three weeks to choose deeper Hanoi, bay, mountains, and central/southern movement.\nNinh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide whether the first Hanoi side move should be day trip or overnight countryside.\nHa Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Choose bay timing after Hanoi arrival and pickup logic are clear.\nCat Ba Travel Guide | /destinations/cat-ba-travel-guide/ | Use when the bay chapter needs island-base logic instead of a simple cruise.\nBai Tu Long Bay Guide | /destinations/bai-tu-long-bay-guide/ | Compare quieter bay choices after the core Hanoi route is protected.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check trip interruption, road transfers, heat/cold, and mountain/bay coverage.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair Hanoi traffic, taxis, markets, phone handling, and night movement with practical risk habits.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.";
vg_hanoi_travel_ops_assert_related_route_meta_links_are_published('Hanoi Travel Guide related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Hoan Kiem image: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0. Body images: Cho Dong Xuan, Old Quarter, Hanoi by yeowatzup, CC BY 2.0; Temple of Literature by Jakub Halun, CC BY 4.0; Central Sector of the Imperial Citadel of Thang Long by katiebordner, CC BY 2.0; Noi Bai International Airport Terminal 2 Night View by Christakis Mina, CC BY-SA 4.0; Long Bien Bridge by TheRollo76, CC BY-SA 4.0.');
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
        vg_hanoi_travel_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_hanoi_travel_ops_fail('Hanoi Travel Guide was updated but is not published.');
}

vg_hanoi_travel_ops_refresh_destinations_hub();
vg_hanoi_travel_ops_refresh_best_things_hanoi_support();
vg_hanoi_travel_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_hanoi_travel_ops_log("Published Hanoi Travel Guide: {$page_id} {$updated_permalink}");

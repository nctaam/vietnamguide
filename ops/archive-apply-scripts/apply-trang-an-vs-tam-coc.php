<?php
/**
 * Publish the Trang An vs Tam Coc comparison guide.
 *
 * Self-reference marker: ops/apply-trang-an-vs-tam-coc.php
 *
 * Run from the WordPress root with:
 * VG_FORCE_TRANG_AN_TAM_COC_REPUBLISH=1 wp eval-file ops/apply-trang-an-vs-tam-coc.php --allow-root
 *
 * Repair only hub/related-route/homepage side effects with:
 * VG_REPAIR_TRANG_AN_TAM_COC_LINKS=1 wp eval-file ops/apply-trang-an-vs-tam-coc.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_trang_an_tam_coc_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_trang_an_tam_coc_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_trang_an_tam_coc_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_TRANG_AN_TAM_COC_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_trang_an_tam_coc_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_TRANG_AN_TAM_COC_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_trang_an_tam_coc_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_trang_an_tam_coc_ops_fail('Could not resolve a valid WordPress author for Trang An vs Tam Coc.');
}

function vg_trang_an_tam_coc_ops_internal_path_from_href(string $href): ?string
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

function vg_trang_an_tam_coc_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_trang_an_tam_coc_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_trang_an_tam_coc_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_trang_an_tam_coc_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_trang_an_tam_coc_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_trang_an_tam_coc_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_trang_an_tam_coc_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_trang_an_tam_coc_ops_log("Validated {$label} internal page links are published.");
}

function vg_trang_an_tam_coc_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_trang_an_tam_coc_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_trang_an_tam_coc_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_trang_an_tam_coc_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_trang_an_tam_coc_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_trang_an_tam_coc_ops_log("Validated {$label} related-route links are published.");
}

function vg_trang_an_tam_coc_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_trang_an_tam_coc_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
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
            vg_trang_an_tam_coc_ops_fail("Could not confidently refresh {$label}.");
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
        vg_trang_an_tam_coc_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_trang_an_tam_coc_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_trang_an_tam_coc_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_trang_an_tam_coc_ops_homepage(): ?WP_Post
{
    $front_page_id = (int) get_option('page_on_front');
    $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        $page = get_page_by_path('home', OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        return null;
    }

    return $page;
}

function vg_trang_an_tam_coc_ops_refresh_compare_hub(): void
{
    $hub = get_page_by_path('compare', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_trang_an_tam_coc_ops_log('Skipped Compare hub refresh: compare page was not found or is not published.');
        return;
    }

    if (! vg_trang_an_tam_coc_ops_published_page_exists('compare/trang-an-vs-tam-coc')) {
        vg_trang_an_tam_coc_ops_log('Skipped Compare hub refresh: Trang An vs Tam Coc is not published.');
        return;
    }

    $marker = '<!-- vg-trang-an-tam-coc-compare-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose the right Ninh Binh boat trip</h3><p><a href="/compare/trang-an-vs-tam-coc/">Trang An vs Tam Coc</a> separates the high-confidence UNESCO boat route from the softer Tam Coc base rhythm by scenery, crowd timing, boat length, family comfort, photography, hotel logic, and day-trip versus overnight value.</p></div>
<!-- /wp:group -->
HTML;

    vg_trang_an_tam_coc_ops_assert_internal_page_links_are_published('Compare hub Trang An vs Tam Coc note', $block);
    vg_trang_an_tam_coc_ops_upsert_marked_group($hub, 'Compare hub Trang An vs Tam Coc note', $marker, $block);
}

function vg_trang_an_tam_coc_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_trang_an_tam_coc_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_trang_an_tam_coc_ops_published_page_exists('compare/trang-an-vs-tam-coc')) {
        vg_trang_an_tam_coc_ops_log('Skipped Destinations hub refresh: Trang An vs Tam Coc is not published.');
        return;
    }

    $marker = '<!-- vg-trang-an-tam-coc-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Make the Ninh Binh boat choice before adding more stops</h3><p>Use <a href="/compare/trang-an-vs-tam-coc/">Trang An vs Tam Coc</a> after the <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a> when the question is no longer whether Ninh Binh belongs, but which water route actually improves the day.</p></div>
<!-- /wp:group -->
HTML;

    vg_trang_an_tam_coc_ops_assert_internal_page_links_are_published('Destinations hub Trang An vs Tam Coc note', $block);
    vg_trang_an_tam_coc_ops_upsert_marked_group($hub, 'Destinations hub Trang An vs Tam Coc note', $marker, $block);
}

function vg_trang_an_tam_coc_ops_refresh_homepage_route_spine(): void
{
    $home = vg_trang_an_tam_coc_ops_homepage();

    if (! $home instanceof WP_Post) {
        vg_trang_an_tam_coc_ops_log('Skipped homepage route-spine refresh: homepage was not found or is not published.');
        return;
    }

    if (! vg_trang_an_tam_coc_ops_published_page_exists('compare/trang-an-vs-tam-coc')) {
        vg_trang_an_tam_coc_ops_log('Skipped homepage route-spine refresh: Trang An vs Tam Coc is not published.');
        return;
    }

    $marker = '<!-- vg-trang-an-tam-coc-homepage-route-spine:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-home-section vg-home-decision-spine vg-home-trang-an-tam-coc-spine"} -->
<div class="wp-block-group vg-home-section vg-home-decision-spine vg-home-trang-an-tam-coc-spine"><p class="vg-kicker">Ninh Binh boat decision</p><h2>Trang An vs Tam Coc</h2><p>Use this comparison when Ninh Binh is already in the route, but one water landscape must earn the day: Trang An for certainty and heritage scale, Tam Coc for base rhythm and countryside texture.</p><p class="vg-section-link"><a href="/compare/trang-an-vs-tam-coc/">Compare Trang An and Tam Coc</a></p></div>
<!-- /wp:group -->
HTML;

    vg_trang_an_tam_coc_ops_assert_internal_page_links_are_published('Homepage Trang An vs Tam Coc route-spine note', $block);
    vg_trang_an_tam_coc_ops_upsert_marked_group($home, 'Homepage Trang An vs Tam Coc route-spine note', $marker, $block);
}

function vg_trang_an_tam_coc_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_trang_an_tam_coc_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_trang_an_tam_coc_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_trang_an_tam_coc_ops_log("Skipped related-route refresh for {$label}: Trang An vs Tam Coc line is already current.");
        return;
    }

    update_post_meta($page->ID, 'vg_eeat_related_routes', implode("\n", $next_lines));

    vg_trang_an_tam_coc_ops_log("Upserted Trang An vs Tam Coc related route to {$label}: {$page->ID}");
}

function vg_trang_an_tam_coc_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.';

    foreach (
        [
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'compare/ninh-binh-day-trip-vs-overnight' => 'Ninh Binh Day Trip vs Overnight',
            'plan/hanoi-to-ninh-binh-transport' => 'Hanoi to Ninh Binh Transport',
            'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
            'itineraries/hanoi-in-2-days' => 'Hanoi in 2 Days',
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
        ] as $path => $label
    ) {
        vg_trang_an_tam_coc_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

function vg_trang_an_tam_coc_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_trang_an_tam_coc_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

$parent = get_page_by_path('compare', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_trang_an_tam_coc_ops_fail('Could not find published /compare/ parent page.');
}

$page = get_page_by_path('compare/trang-an-vs-tam-coc', OBJECT, 'page');

if (vg_trang_an_tam_coc_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_trang_an_tam_coc_ops_fail('Repair mode requires Trang An vs Tam Coc to already be published.');
    }

    vg_trang_an_tam_coc_ops_refresh_compare_hub();
    vg_trang_an_tam_coc_ops_refresh_destinations_hub();
    vg_trang_an_tam_coc_ops_refresh_homepage_route_spine();
    vg_trang_an_tam_coc_ops_refresh_inbound_related_routes();
    vg_trang_an_tam_coc_ops_log("Repaired Trang An vs Tam Coc side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_trang_an_tam_coc_ops_force_republish_enabled()) {
    vg_trang_an_tam_coc_ops_fail('Trang An vs Tam Coc is not a draft. Set VG_FORCE_TRANG_AN_TAM_COC_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_trang_an_tam_coc_ops_log("Preflight Trang An vs Tam Coc: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_trang_an_tam_coc_ops_log('Preflight Trang An vs Tam Coc: no existing page found; creating a child page under /compare/.');
}

$review_date = 'July 24, 2026';
$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');
$tam_coc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Tam_Coc_Ninh_Binh_%2829079%29.jpg/1920px-Tam_Coc_Ninh_Binh_%2829079%29.jpg');
$tam_coc_aerial_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/2/28/Tam_Coc_from_above.jpg');
$trang_an_route_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/29/Trang_An_-_04.jpg/1920px-Trang_An_-_04.jpg');
$mua_cave_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f4/Mua_Cave%2C_Ninh_Binh%2C_Vietnam%2C_20240202_0926_4964.jpg/1920px-Mua_Cave%2C_Ninh_Binh%2C_Vietnam%2C_20240202_0926_4964.jpg');

$content = <<<HTML
<!-- vg-trang-an-tam-coc-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":56,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover vg-trang-an-tam-coc-hero"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover vg-trang-an-tam-coc-hero" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Boats moving through limestone towers at Trang An in Ninh Binh" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Ninh Binh comparison guide - Updated {$review_date}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Trang An vs Tam Coc</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Both boat trips sell the same dream: limestone, water, and a quieter northern Vietnam chapter beyond Hanoi. The better choice depends on what the day needs to solve: one high-confidence heritage landscape, a softer Tam Coc base, a shorter family-friendly water slot, a stronger photo morning, or a route that should stop adding famous names.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: this is not a beauty contest. The right boat is the one that protects the rest of the day.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"vg-image-credit"} -->
<p class="vg-image-credit">Image: Trang An Landscape Complex by Jakub Halun / CC BY 4.0.</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-trang-an-tam-coc-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-trang-an-tam-coc-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-trang-an-tam-coc-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most first-time international travelers choosing one boat trip, choose Trang An when certainty matters and Tam Coc when base rhythm matters.</strong> Trang An is the premium default when you need one high-confidence Ninh Binh landscape answer. Tam Coc is not the weaker choice; it is the more lifestyle-led choice when rice fields, cycling, shorter water time, and a Tam Coc base are the reason you came.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-trang-an-tam-coc-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-trang-an-tam-coc-shortlist">
<li><strong>Choose Trang An</strong> for first-time certainty, UNESCO-linked landscape depth, caves, a stronger single-anchor day, or a group that wants the safest scenic bet.</li>
<li><strong>Choose Tam Coc</strong> for softer countryside texture, rice-field mood, shorter water time, easier Tam Coc-base evenings, cycling, and a less formal day.</li>
<li><strong>Do not force both</strong> unless the second boat has a different job: photography comparison, slow two-night stay, bad weather fallback, or a deliberate Ninh Binh deep dive.</li>
<li><strong>Cut the boat, not the route</strong> when Hanoi, the bay, and onward transport already make the northern chapter brittle.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-trang-an-tam-coc-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-trang-an-tam-coc-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-trang-an-tam-coc-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fastest useful answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-trang-an-tam-coc-glance">
<tbody>
<tr><td data-label="Question"><strong>Best default</strong></td><td data-label="Answer">Trang An for one first-time boat route because it has the strongest certainty: scale, caves, route structure, and heritage context.</td></tr>
<tr><td data-label="Question"><strong>Best lifestyle choice</strong></td><td data-label="Answer">Tam Coc when the day should feel like a countryside base rather than a single-ticket attraction.</td></tr>
<tr><td data-label="Question"><strong>Best for families</strong></td><td data-label="Answer">Tam Coc if shorter water time and easier post-boat recovery matter; Trang An if everyone is comfortable with a longer flagship route.</td></tr>
<tr><td data-label="Question"><strong>Best for photography</strong></td><td data-label="Answer">Trang An for dramatic limestone certainty; Tam Coc when rice fields, river curves, and viewpoint pairing matter more.</td></tr>
<tr><td data-label="Question"><strong>First thing to avoid</strong></td><td data-label="Answer">Booking both boat trips into a single rushed day because the map makes them look close.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-trang-an-tam-coc-photo-proof:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: two different jobs, not one checklist</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The images below are used as planning evidence, not decoration. Trang An usually reads as a grander, more controlled landscape sequence; Tam Coc reads as a softer countryside day that depends more on base rhythm, season, and surrounding lanes.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-trang-an-tam-coc-photo-proof" aria-label="Trang An and Tam Coc comparison photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Trang An limestone scenery and boats in Ninh Binh" loading="lazy" decoding="async"><figcaption>Trang An is the high-confidence anchor when one boat route must carry the day. Image: Jakub Halun / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$trang_an_route_image}" alt="Boat route scenery at Trang An in Ninh Binh" loading="lazy" decoding="async"><figcaption>Trang An's route structure feels more formal and destination-led. Image: Benjamin Smith / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$tam_coc_image}" alt="Tam Coc river boat and limestone scenery in Ninh Binh" loading="lazy" decoding="async"><figcaption>Tam Coc is strongest when the day includes countryside texture around the boat. Image: Andre Hospers / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$tam_coc_aerial_image}" alt="Tam Coc river and rice field landscape from above" loading="lazy" decoding="async"><figcaption>Tam Coc's value rises when rice-field season, cycling, and a nearby base are part of the plan. Image: Nomad Tales / CC BY-SA 2.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mua_cave_image}" alt="Mua Cave viewpoint over Ninh Binh karst landscape" loading="lazy" decoding="async"><figcaption>Hang Mua can pair beautifully with either boat, but only when heat, weather, and crowd timing are realistic. Image: Jakub Halun / CC BY 4.0.</figcaption></figure>
</div>
<!-- /wp:html -->
<!-- wp:paragraph {"className":"vg-source-note"} -->
<p class="vg-source-note">Image credits are listed as text to keep reader focus on the decision and reduce visible outbound clutter. Full image source records are retained in the source trail metadata.</p>
<!-- /wp:paragraph -->

<!-- vg-trang-an-tam-coc-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What sources prove, and what judgment must decide</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Official sources can confirm Ninh Binh's landscape frame, Trang An's heritage status, boat-tour context, weather patterns, and transport categories. They cannot decide your patience for crowds, your hotel base, your heat tolerance, or whether a shorter boat is actually a better day.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-trang-an-tam-coc-source-diversity">
<thead><tr><th>Source type</th><th>Useful for</th><th>VietnamGuide still decides</th></tr></thead>
<tbody>
<tr><td data-label="Source type">Vietnam.travel Ninh Binh pages</td><td data-label="Useful for">Destination framing, boat-tour context, scenery vocabulary, cycling and countryside positioning.</td><td data-label="VietnamGuide still decides">Which route solves a first-time traveler's actual day.</td></tr>
<tr><td data-label="Source type">UNESCO Trang An listing</td><td data-label="Useful for">Confirming Trang An's mixed cultural and natural heritage importance.</td><td data-label="VietnamGuide still decides">Whether heritage certainty should beat base atmosphere or shorter water time.</td></tr>
<tr><td data-label="Source type">Ninh Binh Tourism Department</td><td data-label="Useful for">Local tourism context, attraction notices, and destination grouping around Trang An, Tam Coc, Bich Dong, and nearby stops.</td><td data-label="VietnamGuide still decides">Which stops deserve limited route margin.</td></tr>
<tr><td data-label="Source type">Weather and transport sources</td><td data-label="Useful for">Heat, rain, exposed viewpoints, Hanoi transfer logic, and seasonal pressure.</td><td data-label="VietnamGuide still decides">Whether to choose one boat, both boats, overnight timing, or a clean skip.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-trang-an-tam-coc-source-trail-snapshot:v1 -->
<!-- wp:group {"className":"vg-source-snapshot vg-trang-an-tam-coc-source-trail-snapshot","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-source-snapshot vg-trang-an-tam-coc-source-trail-snapshot">
<!-- wp:heading -->
<h2 class="wp-block-heading">Source trail snapshot</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Checked for this decision page: Vietnam.travel Ninh Binh boat tours - https://vietnam.travel/things-to-do/guide-boat-tours-ninh-binh; Vietnam.travel Ninh Binh destination page - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh; UNESCO Trang An Landscape Complex - https://whc.unesco.org/en/list/1438/; Ninh Binh Tourism Department - https://dulichninhbinh.com.vn/en/; Vietnam.travel weather and climate - https://vietnam.travel/things-to-do/weather-and-climate-vietnam; Vietnam.travel transport - https://vietnam.travel/plan-your-trip/transport-within-vietnam.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- vg-trang-an-tam-coc-decision-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Decision matrix: choose the boat by the job</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-trang-an-tam-coc-decision-matrix">
<thead><tr><th>Decision job</th><th>Choose Trang An when</th><th>Choose Tam Coc when</th><th>Verdict</th></tr></thead>
<tbody>
<tr><td data-label="Decision job">One famous Ninh Binh answer</td><td data-label="Choose Trang An when">The day needs maximum scenery confidence and a clear heritage anchor.</td><td data-label="Choose Tam Coc when">The famous answer should feel village-led and softer.</td><td data-label="Verdict">Trang An wins for first-timer certainty.</td></tr>
<tr><td data-label="Decision job">Shorter, easier day</td><td data-label="Choose Trang An when">A longer route is acceptable and the group wants the main event.</td><td data-label="Choose Tam Coc when">Children, older travelers, heat, or appetite for a shorter boat matter.</td><td data-label="Verdict">Tam Coc can be the better comfort choice.</td></tr>
<tr><td data-label="Decision job">Photography</td><td data-label="Choose Trang An when">You want dramatic limestone, water, caves, and a more cinematic single sequence.</td><td data-label="Choose Tam Coc when">Rice fields, river curves, village lanes, and Hang Mua pairing are the point.</td><td data-label="Verdict">Pick by season and light, not by fame.</td></tr>
<tr><td data-label="Decision job">Overnight base</td><td data-label="Choose Trang An when">The hotel is closer to Trang An or a quieter landscape stay is the goal.</td><td data-label="Choose Tam Coc when">Restaurants, cycling, evening walking, and base atmosphere matter.</td><td data-label="Verdict">Tam Coc often wins the lifestyle night.</td></tr>
<tr><td data-label="Decision job">Two-night deep dive</td><td data-label="Choose Trang An when">One day should be the high-confidence heritage landscape.</td><td data-label="Choose Tam Coc when">The second day should be slower, less formal, and base-connected.</td><td data-label="Verdict">Both can work only when the days feel different.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-trang-an-tam-coc-route-comparison:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route feel: what each boat asks from the day</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-trang-an-tam-coc-route-comparison">
<thead><tr><th>Dimension</th><th>Trang An</th><th>Tam Coc</th><th>Planning implication</th></tr></thead>
<tbody>
<tr><td data-label="Dimension">Landscape certainty</td><td data-label="Trang An">More controlled flagship feeling with a clear main-attraction identity.</td><td data-label="Tam Coc">More dependent on season, base rhythm, and surrounding countryside.</td><td data-label="Planning implication">Choose Trang An when the boat must justify Ninh Binh alone.</td></tr>
<tr><td data-label="Dimension">Time on water</td><td data-label="Trang An">Usually a longer commitment and less casual to squeeze.</td><td data-label="Tam Coc">Easier to pair with a soft countryside block.</td><td data-label="Planning implication">Choose Tam Coc when the day has heat, family, or transfer pressure.</td></tr>
<tr><td data-label="Dimension">Base connection</td><td data-label="Trang An">Best when staying nearby or using private transport.</td><td data-label="Tam Coc">Best when Tam Coc village lanes, food, cycling, and evening rhythm matter.</td><td data-label="Planning implication">Do not separate the boat from the hotel choice.</td></tr>
<tr><td data-label="Dimension">Crowd sensitivity</td><td data-label="Trang An">Crowds are easier to absorb if the route still feels grand.</td><td data-label="Tam Coc">Crowds can flatten the village-river mood faster.</td><td data-label="Planning implication">Earlier starts and non-peak days matter for both.</td></tr>
<tr><td data-label="Dimension">Add-on temptation</td><td data-label="Trang An">Tempts travelers to add temples, viewpoints, and another boat because the route feels official.</td><td data-label="Tam Coc">Tempts travelers to add Hang Mua and cycling even when heat says no.</td><td data-label="Planning implication">One boat plus one supporting job is usually stronger than three shallow stops.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-trang-an-tam-coc-traveler-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Traveler fit</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-trang-an-tam-coc-traveler-fit">
<thead><tr><th>Traveler type</th><th>Better default</th><th>Why</th><th>Exception</th></tr></thead>
<tbody>
<tr><td data-label="Traveler type">First-time couple</td><td data-label="Better default">Trang An</td><td data-label="Why">It is the more reliable single scenic answer.</td><td data-label="Exception">Choose Tam Coc if staying in a beautiful Tam Coc property and using the evening well.</td></tr>
<tr><td data-label="Traveler type">Family with children</td><td data-label="Better default">Tam Coc</td><td data-label="Why">Shorter water time and easier recovery can matter more than absolute grandeur.</td><td data-label="Exception">Choose Trang An if the children tolerate longer seated sightseeing comfortably.</td></tr>
<tr><td data-label="Traveler type">Photographer</td><td data-label="Better default">Depends on season</td><td data-label="Why">Trang An gives dramatic limestone; Tam Coc depends more on rice, water, viewpoint, and weather timing.</td><td data-label="Exception">With two nights, shoot both only if each has a different light window.</td></tr>
<tr><td data-label="Traveler type">Luxury slow traveler</td><td data-label="Better default">Choose by hotel base</td><td data-label="Why">The room, meal, transfer, and quiet hours may decide more than the boat label.</td><td data-label="Exception">Use private sequencing to protect Trang An early and Tam Coc later only if the route is slow.</td></tr>
<tr><td data-label="Traveler type">Tight Hanoi day-tripper</td><td data-label="Better default">Trang An</td><td data-label="Why">It gives the strongest one-stop proof when Ninh Binh has only one outside day.</td><td data-label="Exception">Choose Tam Coc if the tour is cleaner, shorter, and avoids stop stuffing.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-trang-an-tam-coc-season-photography:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Season and photography logic</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Do not make the decision from a single perfect photo. Ninh Binh changes with rice season, heat, rain, haze, water levels, and how much of the day is lost to transfers. The right answer in a clean shoulder-season overnight can be different from the right answer on a hot Hanoi day trip.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-trang-an-tam-coc-season-photography">
<thead><tr><th>Condition</th><th>Trang An bias</th><th>Tam Coc bias</th><th>Move</th></tr></thead>
<tbody>
<tr><td data-label="Condition">Clear, comfortable day</td><td data-label="Trang An bias">Strong flagship choice.</td><td data-label="Tam Coc bias">Strong if rice/countryside texture is appealing.</td><td data-label="Move">Choose by route job, not weather fear.</td></tr>
<tr><td data-label="Condition">Hot midday pressure</td><td data-label="Trang An bias">Longer water time can feel harder.</td><td data-label="Tam Coc bias">Shorter and easier to pair with rest.</td><td data-label="Move">Start early, shorten the day, and make Hang Mua optional.</td></tr>
<tr><td data-label="Condition">Rain or low cloud</td><td data-label="Trang An bias">Can stay atmospheric if the route still runs safely.</td><td data-label="Tam Coc bias">Can lose charm if rice, visibility, and cycling are weak.</td><td data-label="Move">Use an overnight buffer if the boat is the reason for Ninh Binh.</td></tr>
<tr><td data-label="Condition">Rice-field priority</td><td data-label="Trang An bias">Less central to the experience.</td><td data-label="Tam Coc bias">More central to the mood.</td><td data-label="Move">Choose Tam Coc when the countryside look is the reason for the stop.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-trang-an-tam-coc-crowd-timing:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Crowd and timing strategy</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-trang-an-tam-coc-crowd-timing">
<thead><tr><th>Pressure</th><th>Trang An response</th><th>Tam Coc response</th><th>Decision value</th></tr></thead>
<tbody>
<tr><td data-label="Pressure">Weekend or holiday</td><td data-label="Trang An response">Still viable if you accept a popular flagship site and protect timing.</td><td data-label="Tam Coc response">Mood can suffer if the river feels busy and the base is crowded.</td><td data-label="Decision value">Avoid treating either as a quiet secret.</td></tr>
<tr><td data-label="Pressure">Late Hanoi arrival</td><td data-label="Trang An response">Better saved for next morning if staying overnight.</td><td data-label="Tam Coc response">Can work as a softer base day if the boat is not forced late.</td><td data-label="Decision value">Do not pay for the best landscape at the worst hour.</td></tr>
<tr><td data-label="Pressure">Group tour schedule</td><td data-label="Trang An response">Choose only if the itinerary does not overstuff supporting stops.</td><td data-label="Tam Coc response">Choose only if lunch, cycling, or viewpoint logic is realistic.</td><td data-label="Decision value">Audit the whole day, not only the boat name.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-trang-an-tam-coc-base-logistics:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Base and logistics: the hotel changes the answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-trang-an-tam-coc-base-logistics">
<thead><tr><th>Base</th><th>Boat bias</th><th>Why</th><th>Watch out</th></tr></thead>
<tbody>
<tr><td data-label="Base">Tam Coc</td><td data-label="Boat bias">Tam Coc first, Trang An as the higher-certainty alternative.</td><td data-label="Why">The village lanes, restaurants, and cycling make Tam Coc more than a pier.</td><td data-label="Watch out">Do not choose Tam Coc only because it is nearby if the scenery goal is grander.</td></tr>
<tr><td data-label="Base">Trang An area</td><td data-label="Boat bias">Trang An.</td><td data-label="Why">Morning control and quiet landscape lodging make the flagship route easier to protect.</td><td data-label="Watch out">Evening food choice may be narrower than Tam Coc.</td></tr>
<tr><td data-label="Base">Ninh Binh city</td><td data-label="Boat bias">Choose by transfer and onward rail.</td><td data-label="Why">The city is practical, not the romantic part of the decision.</td><td data-label="Watch out">A cheap city hotel can make the overnight feel logistical rather than countryside-led.</td></tr>
<tr><td data-label="Base">Hanoi day trip</td><td data-label="Boat bias">Trang An unless the Tam Coc itinerary is cleaner.</td><td data-label="Why">The day needs one strong proof point and a realistic return.</td><td data-label="Watch out">Avoid adding both boats, Mua Cave, temples, cycling, and a late return.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-trang-an-tam-coc-day-trip-overnight:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Day trip or overnight changes the boat choice</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-trang-an-tam-coc-day-trip-overnight">
<thead><tr><th>Route shape</th><th>Best boat logic</th><th>Supporting stop</th><th>Skip</th></tr></thead>
<tbody>
<tr><td data-label="Route shape">Hanoi day trip</td><td data-label="Best boat logic">Trang An if one flagship route must carry the day; Tam Coc if the operator keeps the schedule lighter.</td><td data-label="Supporting stop">One compact viewpoint or temple only if weather and return time work.</td><td data-label="Skip">Second boat, late transfer, and heavy cycling.</td></tr>
<tr><td data-label="Route shape">One night in Ninh Binh</td><td data-label="Best boat logic">Choose by base and morning: Trang An for certainty, Tam Coc for village rhythm.</td><td data-label="Supporting stop">Hang Mua, Bich Dong, cycling, or a slow meal.</td><td data-label="Skip">More stops that steal the good morning.</td></tr>
<tr><td data-label="Route shape">Two nights in Ninh Binh</td><td data-label="Best boat logic">Both can work if each day has a different job.</td><td data-label="Supporting stop">Van Long, Cuc Phuong, cycling, or photography windows.</td><td data-label="Skip">Doing both only because both names are famous.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
<!-- wp:paragraph -->
<p>Use <a href="/compare/ninh-binh-day-trip-vs-overnight/">Ninh Binh Day Trip vs Overnight</a> before this page if the night count is still unclear, then use <a href="/plan/hanoi-to-ninh-binh-transport/">Hanoi to Ninh Binh Transport</a> before locking a pickup, station, hotel, or onward bay handoff.</p>
<!-- /wp:paragraph -->

<!-- vg-trang-an-tam-coc-booking-audit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Booking audit before you pay</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-trang-an-tam-coc-booking-audit">
<thead><tr><th>Audit question</th><th>Good answer</th><th>Warning sign</th></tr></thead>
<tbody>
<tr><td data-label="Audit question">Which boat route is actually included?</td><td data-label="Good answer">The itinerary names Trang An or Tam Coc clearly and explains the rest of the day.</td><td data-label="Warning sign">The listing uses Ninh Binh generically and hides the route until pickup.</td></tr>
<tr><td data-label="Audit question">What is the stop count?</td><td data-label="Good answer">One boat plus one or two realistic supporting stops.</td><td data-label="Warning sign">Boat, viewpoint, temples, cycling, buffet, shopping, and late return packed together.</td></tr>
<tr><td data-label="Audit question">How is weather handled?</td><td data-label="Good answer">The operator or hotel explains rain, heat, route changes, and cancellation terms.</td><td data-label="Warning sign">The plan acts as if every boat and viewpoint works identically in all conditions.</td></tr>
<tr><td data-label="Audit question">Where does transport really end?</td><td data-label="Good answer">Drop-off matches the hotel lane, station, or onward transfer.</td><td data-label="Warning sign">A cheap transfer saves money but creates a final taxi problem with luggage.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-trang-an-tam-coc-mistakes-skip:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mistakes that make both choices worse</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-trang-an-tam-coc-mistakes-skip">
<thead><tr><th>Mistake</th><th>Why it hurts</th><th>Better move</th></tr></thead>
<tbody>
<tr><td data-label="Mistake">Choosing from a single photo</td><td data-label="Why it hurts">Photos hide season, crowd timing, water level, heat, and how much transfer time the day costs.</td><td data-label="Better move">Choose by route job and same-week conditions.</td></tr>
<tr><td data-label="Mistake">Doing both on a Hanoi day trip</td><td data-label="Why it hurts">Doing both Trang An and Tam Coc is useful only when the second boat changes the trip, not when it repeats the same limestone proof.</td><td data-label="Better move">Pick one boat and one supporting stop.</td></tr>
<tr><td data-label="Mistake">Treating Hang Mua as mandatory</td><td data-label="Why it hurts">Heat, haze, rain, wet steps, and crowding can make the viewpoint a poor trade.</td><td data-label="Better move">Make Hang Mua conditional, especially after a long boat.</td></tr>
<tr><td data-label="Mistake">Choosing Tam Coc only because the hotel is there</td><td data-label="Why it hurts">Convenience is real, but it should not override the landscape goal if this is the only Ninh Binh day.</td><td data-label="Better move">Use Tam Coc for base rhythm and Trang An for flagship certainty.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-trang-an-tam-coc-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before you go</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-trang-an-tam-coc-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-trang-an-tam-coc-live-checks">
<li>Check Vietnam.travel and Ninh Binh Tourism Department for current destination framing, attraction notices, and boat-tour context before treating any route as fixed.</li>
<li>Check same-week weather before choosing Hang Mua, exposed cycling, or a midday boat plan.</li>
<li>Check the hotel or operator for actual pickup, pier, route, expected duration, lunch timing, and return/drop-off before paying.</li>
<li>Check <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before using scooters, boats, rural roads, or tight onward transfers.</li>
</ul>
<!-- /wp:list -->

<!-- vg-trang-an-tam-coc-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Trang An vs Tam Coc FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-trang-an-tam-coc-faq">
<details><summary>Is Trang An better than Tam Coc?</summary><p>Trang An is the better default for first-time certainty, but not always the better day. Tam Coc can be better when the hotel base, shorter water time, rice-field texture, cycling, or softer countryside rhythm matter more.</p></details>
<details><summary>Can I do both Trang An and Tam Coc in one day?</summary><p>You can, but it is rarely the best first plan. One boat route plus one strong supporting stop usually creates a better day than two similar water routes squeezed together.</p></details>
<details><summary>Which is better for a Hanoi day trip?</summary><p>Trang An is usually the stronger one-stop day-trip answer. Tam Coc can be better when the operator keeps the day lighter and the group values comfort over maximum scenery scale.</p></details>
<details><summary>Which is better if I stay overnight in Ninh Binh?</summary><p>Choose by base. Tam Coc is often better for restaurants, cycling, and easy evenings; Trang An area is better when quiet landscape lodging and morning control matter more.</p></details>
<details><summary>Is Tam Coc still worth it outside rice season?</summary><p>Yes, but the reason changes. Outside a strong rice-field window, choose Tam Coc for base rhythm and shorter water time rather than expecting every photo to match peak-season imagery.</p></details>
<details><summary>Should I add Hang Mua after the boat?</summary><p>Only when weather, heat, visibility, steps, and energy support it. Hang Mua is a strong viewpoint, not a mandatory proof that the Ninh Binh day was complete.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, then choose pace with <a href="/compare/ninh-binh-day-trip-vs-overnight/">Ninh Binh Day Trip vs Overnight</a>. Use this Trang An vs Tam Coc comparison only after Ninh Binh has earned a real day, then check <a href="/plan/hanoi-to-ninh-binh-transport/">Hanoi to Ninh Binh Transport</a>, <a href="/destinations/best-day-trips-from-hanoi/">Best Day Trips from Hanoi</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, and <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a> before the north becomes overfull.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-trang-an-tam-coc-hero:v1',
    'concierge verdict' => 'vg-trang-an-tam-coc-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-trang-an-tam-coc-at-a-glance:v1',
    'photo proof' => 'vg-trang-an-tam-coc-photo-proof:v1',
    'source diversity' => 'vg-trang-an-tam-coc-source-diversity:v1',
    'source trail snapshot' => 'vg-trang-an-tam-coc-source-trail-snapshot:v1',
    'decision matrix' => 'vg-trang-an-tam-coc-decision-matrix:v1',
    'route comparison' => 'vg-trang-an-tam-coc-route-comparison:v1',
    'traveler fit' => 'vg-trang-an-tam-coc-traveler-fit:v1',
    'season photography' => 'vg-trang-an-tam-coc-season-photography:v1',
    'crowd timing' => 'vg-trang-an-tam-coc-crowd-timing:v1',
    'base logistics' => 'vg-trang-an-tam-coc-base-logistics:v1',
    'one day overnight' => 'vg-trang-an-tam-coc-day-trip-overnight:v1',
    'booking audit' => 'vg-trang-an-tam-coc-booking-audit:v1',
    'mistakes skip' => 'vg-trang-an-tam-coc-mistakes-skip:v1',
    'live checks' => 'vg-trang-an-tam-coc-live-checks:v1',
    'FAQ' => 'vg-trang-an-tam-coc-faq:v1',
    'related routes shortcode' => '[vg_related_routes]',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_trang_an_tam_coc_ops_assert_required_content_markers($content, $required_content_markers);
vg_trang_an_tam_coc_ops_assert_internal_page_links_are_published('Trang An vs Tam Coc', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Trang An vs Tam Coc',
    'post_name'      => 'trang-an-vs-tam-coc',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_trang_an_tam_coc_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led comparison for international travelers choosing Trang An or Tam Coc as the right Ninh Binh boat trip by route fit, crowd timing, base logic, photography, and family comfort.',
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
    vg_trang_an_tam_coc_ops_fail('Could not publish Trang An vs Tam Coc: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_trang_an_tam_coc_ops_fail('Could not publish Trang An vs Tam Coc: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Trang An vs Tam Coc: Which Ninh Binh Boat Trip?');
update_post_meta($page_id, 'rank_math_description', 'Trang An vs Tam Coc: choose the best Ninh Binh boat trip by scenery, base, crowds, timing, photography, family comfort, day trip vs overnight, and route fit.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Trang An vs Tam Coc');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Choose Trang An or Tam Coc as the right Ninh Binh boat trip before booking a Hanoi day trip, overnight base, private transfer, group tour, or northern scenery route.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published Trang An vs Tam Coc as an evidence-led Ninh Binh boat decision with photo-led hero, concierge verdict, proof panel, at-a-glance table, non-linked image credits, source-diversity module, rendered source trail snapshot, decision matrix, route-feel comparison, traveler-fit table, season and photography logic, crowd timing, base logistics, day-trip versus overnight guidance, booking audit, mistakes and skip logic, live checks, FAQ, Compare and Destinations hub notes, homepage support, and inbound related routes.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Guide to Ninh Binh boat tours - https://vietnam.travel/things-to-do/guide-boat-tours-ninh-binh - checked {$review_date}\nVietnam.travel - Ninh Binh destination page - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh - checked {$review_date}\nUNESCO World Heritage Centre - Trang An Landscape Complex - https://whc.unesco.org/en/list/1438/ - checked {$review_date}\nNinh Binh Tourism Department - Tourism Promotion Information Center - https://dulichninhbinh.com.vn/en/ - checked {$review_date}\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}\nWikimedia Commons image record - Trang An Landscape Complex, Ninh Binh Province - https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg - license checked {$review_date}\nWikimedia Commons image record - Trang An route - https://commons.wikimedia.org/wiki/File:Trang_An_-_04.jpg - license checked {$review_date}\nWikimedia Commons image record - Tam Coc Ninh Binh - https://commons.wikimedia.org/wiki/File:Tam_Coc_Ninh_Binh_(29079).jpg - license checked {$review_date}\nWikimedia Commons image record - Tam Coc from above - https://commons.wikimedia.org/wiki/File:Tam_Coc_from_above.jpg - license checked {$review_date}\nWikimedia Commons image record - Mua Cave, Ninh Binh - https://commons.wikimedia.org/wiki/File:Mua_Cave,_Ninh_Binh,_Vietnam,_20240202_0926_4964.jpg - license checked {$review_date}");
update_post_meta($page_id, 'vg_eeat_field_note', 'Trang An versus Tam Coc is a route-design decision, not a universal ranking. Trang An gives the stronger one-stop scenic proof; Tam Coc gives the stronger base-connected countryside rhythm when the hotel, season, and day shape support it.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Trang An vs Tam Coc built around a distinct high-intent decision rather than a recycled Ninh Binh attraction list.\nConcierge verdict names the default Trang An answer and the Tam Coc lifestyle exception.\nAt-a-glance table separates first-time certainty, lifestyle, families, photography, and the first mistake to avoid.\nPhoto proof uses licensed external images with text credits and source-trail metadata, avoiding visible outbound clutter.\nSource-diversity module separates official facts from VietnamGuide route judgment and explicitly limits what sources can decide.\nDecision matrix, route-feel comparison, traveler-fit table, season and photography logic, crowd timing, base logistics, day-trip versus overnight guidance, booking audit, mistakes/skip logic, live checks, FAQ, source trail, update log, and related routes provide practical decision value beyond rewritten search results.\nRelated routes connect the page to Ninh Binh Travel Guide, Ninh Binh Day Trip vs Overnight, Hanoi to Ninh Binh Transport, Hanoi day trips, itinerary lengths, bay guides, season, cost, transport, safety, and insurance.");

$related_routes = "Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Use the full destination guide before choosing the boat route.\nNinh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether the boat belongs in a Hanoi day trip, one-night stop, two-night slow base, or skip.\nHanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer before the boat day breaks.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Decide whether Ninh Binh should be the Hanoi excursion at all.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Protect the capital chapter before exporting a day to Ninh Binh.\nHanoi Travel Guide | /destinations/hanoi-travel-guide/ | Place Ninh Binh inside the northern route, not beside it.\nWhere to Stay in Hanoi | /destinations/where-to-stay-in-hanoi/ | Pick the launch base that makes pickup and sleep work.\nBest Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Protect Hanoi before adding outside scenery.\nHa Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Decide whether bay scenery competes with or complements Ninh Binh.\nHa Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Check the northern water-scenery decision before adding too many limestone chapters.\nCat Ba Travel Guide | /destinations/cat-ba-travel-guide/ | Use when Cat Ba changes the bay route after Ninh Binh.\nBai Tu Long Bay Guide | /destinations/bai-tu-long-bay-guide/ | Compare quieter bay logic only after the Ninh Binh boat choice is clear.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the planning order before locking northern details.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Decide whether Ninh Binh belongs in the destination shortlist.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Understand Trang An as heritage value, not just a scenery label.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm the north deserves enough nights before adding another water landscape.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check heat, rain, rice fields, and northern season pressure before choosing the boat.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Decide whether the transfer mode supports the actual boat day.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the difference between a rushed tour and a protected countryside day.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check road, boat, cycling, scooter, weather, and rural-transfer risk.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair deposits, transfers, boat tours, scooters, and rural movement with practical risk habits.\n7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Decide whether a one-week route can afford Ninh Binh.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether one Ninh Binh boat day improves or weakens the route.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when Ninh Binh should become a protected northern chapter.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use a longer route to slow Ninh Binh without collecting duplicate stops.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.";
vg_trang_an_tam_coc_ops_assert_related_route_meta_links_are_published('Trang An vs Tam Coc related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and body images: Trang An Landscape Complex by Jakub Halun, CC BY 4.0; Trang An route by Benjamin Smith, CC BY-SA 4.0; Tam Coc by Andre Hospers, CC BY 4.0; Tam Coc from above by Nomad Tales, CC BY-SA 2.0; Mua Cave by Jakub Halun, CC BY 4.0. Image credits are text-only on the page; source records are preserved in metadata.');
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
        vg_trang_an_tam_coc_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_trang_an_tam_coc_ops_fail('Trang An vs Tam Coc was updated but is not published.');
}

vg_trang_an_tam_coc_ops_refresh_compare_hub();
vg_trang_an_tam_coc_ops_refresh_destinations_hub();
vg_trang_an_tam_coc_ops_refresh_homepage_route_spine();
vg_trang_an_tam_coc_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_trang_an_tam_coc_ops_log("Published Trang An vs Tam Coc: {$page_id} {$updated_permalink}");

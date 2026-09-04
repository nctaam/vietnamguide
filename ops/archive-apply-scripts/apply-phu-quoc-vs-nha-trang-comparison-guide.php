<?php
/**
 * Publish the Phu Quoc vs Nha Trang comparison guide.
 *
 * Run from the WordPress root with:
 * VG_FORCE_PHU_QUOC_NHA_TRANG_COMPARISON_REPUBLISH=1 wp eval-file ops/apply-phu-quoc-vs-nha-trang-comparison-guide.php --allow-root
 *
 * Repair only hub/related-route side effects with:
 * VG_REPAIR_PHU_QUOC_NHA_TRANG_COMPARISON_LINKS=1 wp eval-file ops/apply-phu-quoc-vs-nha-trang-comparison-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_pq_nt_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_pq_nt_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_pq_nt_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_PHU_QUOC_NHA_TRANG_COMPARISON_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_pq_nt_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_PHU_QUOC_NHA_TRANG_COMPARISON_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_pq_nt_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_pq_nt_ops_fail('Could not resolve a valid WordPress author for the Phu Quoc vs Nha Trang guide.');
}

function vg_pq_nt_ops_internal_path_from_href(string $href): ?string
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

function vg_pq_nt_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_pq_nt_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
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
        vg_pq_nt_ops_fail(implode(PHP_EOL, $failures));
    }

    vg_pq_nt_ops_log("Validated {$label} internal page links are published.");
}

function vg_pq_nt_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_pq_nt_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_pq_nt_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_pq_nt_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_pq_nt_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_pq_nt_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_pq_nt_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_pq_nt_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_pq_nt_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_pq_nt_ops_log("Validated {$label} related-route links are published.");
}

function vg_pq_nt_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_pq_nt_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_pq_nt_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

function vg_pq_nt_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
{
    $marked_block = $marker . "\n" . $block;

    if (str_contains($page->post_content, $marked_block)) {
        vg_pq_nt_ops_log("Skipped {$label}: current block already present.");
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
            vg_pq_nt_ops_fail("Could not confidently refresh {$label}.");
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
        vg_pq_nt_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_pq_nt_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_pq_nt_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_pq_nt_ops_refresh_compare_hub(): void
{
    $hub = get_page_by_path('compare', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_pq_nt_ops_log('Skipped Compare hub refresh: compare page was not found or is not published.');
        return;
    }

    if (! vg_pq_nt_ops_published_page_exists('compare/phu-quoc-vs-nha-trang')) {
        vg_pq_nt_ops_log('Skipped Compare hub refresh: Phu Quoc vs Nha Trang guide is not published.');
        return;
    }

    $marker = '<!-- vg-phu-quoc-nha-trang-compare-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose the beach fork before booking the coast</h3><p>The <a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a> guide helps travelers choose island resort recovery or city-beach routing before paying for beach flights, Cam Ranh transfers, resort nights, boat days, and route buffers.</p></div>
<!-- /wp:group -->
HTML;

    vg_pq_nt_ops_assert_internal_page_links_are_published('Compare hub Phu Quoc/Nha Trang note', $block);
    vg_pq_nt_ops_upsert_marked_group($hub, 'Compare hub Phu Quoc/Nha Trang note', $marker, $block);
}

function vg_pq_nt_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_pq_nt_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_pq_nt_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_pq_nt_ops_log("Skipped related-route refresh for {$label}: Phu Quoc vs Nha Trang line is already current.");
        return;
    }

    $next = implode("\n", $next_lines);
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_pq_nt_ops_log("Upserted Phu Quoc vs Nha Trang related route to {$label}: {$page->ID}");
}

function vg_pq_nt_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Phu Quoc vs Nha Trang | /compare/phu-quoc-vs-nha-trang/ | Choose island-resort recovery or city-beach routing before booking beach flights, resorts, Cam Ranh transfers, boat days, and route buffers.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
            'destinations/best-islands-in-vietnam' => 'Best Islands in Vietnam guide',
            'destinations/phu-quoc-travel-guide' => 'Phu Quoc Travel Guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ] as $path => $label
    ) {
        vg_pq_nt_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('compare', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_pq_nt_ops_fail('Published parent page not found: compare');
}

$page = get_page_by_path('compare/phu-quoc-vs-nha-trang', OBJECT, 'page');

if (vg_pq_nt_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_pq_nt_ops_fail('Repair mode requires the Phu Quoc vs Nha Trang guide to already be published.');
    }

    vg_pq_nt_ops_refresh_compare_hub();
    vg_pq_nt_ops_refresh_inbound_related_routes();
    vg_pq_nt_ops_log("Repaired Phu Quoc vs Nha Trang side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_pq_nt_ops_force_republish_enabled()) {
    vg_pq_nt_ops_fail('Phu Quoc vs Nha Trang guide is not a draft. Set VG_FORCE_PHU_QUOC_NHA_TRANG_COMPARISON_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_pq_nt_ops_log("Preflight Phu Quoc vs Nha Trang: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_pq_nt_ops_log('Preflight Phu Quoc vs Nha Trang: no existing page found; creating a child page under /compare/.');
}

$nha_trang_hero = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Nha_Trang_Beach_3.jpg/1920px-Nha_Trang_Beach_3.jpg');
$nha_trang_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Nha_Trang_Beach_3.jpg');
$phu_quoc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$phu_quoc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$po_nagar_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d7/Po_Nagar_Nha_Trang_Vietnam.JPG/1280px-Po_Nagar_Nha_Trang_Vietnam.JPG');
$po_nagar_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Po_Nagar_Nha_Trang_Vietnam.JPG');
$nha_trang_coast_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Panorama_of_V%E1%BB%8Bnh_Nha_Trang_coastline.jpg/1920px-Panorama_of_V%E1%BB%8Bnh_Nha_Trang_coastline.jpg');
$nha_trang_coast_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Panorama_of_V%E1%BB%8Bnh_Nha_Trang_coastline.jpg');
$cam_ranh_terminal_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f7/Cam_Ranh_International_Airport_Terminal_2.jpg/1280px-Cam_Ranh_International_Airport_Terminal_2.jpg');
$cam_ranh_terminal_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cam_Ranh_International_Airport_Terminal_2.jpg');

$content = <<<HTML
<!-- vg-phu-quoc-nha-trang-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$nha_trang_hero}","dimRatio":55,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Nha Trang Beach in Vietnam used for a Phu Quoc vs Nha Trang comparison guide" src="{$nha_trang_hero}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed comparison guide - Updated July 20, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Phu Quoc vs Nha Trang</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Phu Quoc and Nha Trang both sell beach time, but they solve different trip problems. Phu Quoc is the island resort and recovery choice. Nha Trang is the city-beach and coastal-base choice. The right answer depends on whether the beach should end the trip, anchor a south-central chapter, or disappear so the route can breathe.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: this comparison is not a prettier-beach contest. It is a route decision about season, flights, transfer friction, hotel area, food rhythm, boat-day expectations, and what mainland stop the beach chapter replaces.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$nha_trang_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-phu-quoc-nha-trang-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-phu-quoc-nha-trang-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-phu-quoc-nha-trang-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Choose Phu Quoc when the trip needs an island resort finish, winter-sun recovery, family resort infrastructure, or a softer southern exit. Choose Nha Trang when the route needs a city beach with more dining, day-trip rhythm, and Cam Ranh airport access.</strong> If the beach would add another flight, airport transfer, ferry, or isolated hotel without improving the route, skip both and spend the nights inland.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-phu-quoc-nha-trang-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-phu-quoc-nha-trang-shortlist">
<li><strong>Best island resort answer:</strong> Phu Quoc, especially when the route ends in the south and rest matters more than urban texture.</li>
<li><strong>Best city-beach answer:</strong> Nha Trang, especially when you want restaurants, city movement, boat-day options, and a coastal base rather than a resort bubble.</li>
<li><strong>Best family resort logic:</strong> Phu Quoc for resort-contained ease; Nha Trang for city services and shorter activity pivots.</li>
<li><strong>Best route discipline:</strong> choose one beach job, protect transfer buffers, and cut the beach if it only decorates an overfull itinerary.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-phu-quoc-nha-trang-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-phu-quoc-nha-trang-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-phu-quoc-nha-trang-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fast useful answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-phu-quoc-nha-trang-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Which is better for most beach holidays?</strong></td><td data-label="Answer">Phu Quoc if the beach is the main resort chapter. Nha Trang if the beach should sit inside a more active coastal city stay.</td></tr>
<tr><td data-label="Question"><strong>Which is better for first-time Vietnam?</strong></td><td data-label="Answer">Neither is automatic. Phu Quoc fits south-ending or longer routes; Nha Trang fits travelers who want a central/south-central beach without a pure island detour.</td></tr>
<tr><td data-label="Question"><strong>Which is easier?</strong></td><td data-label="Answer">Phu Quoc can be easier once you are on the island. Nha Trang can be easier day to day because it behaves like a city beach, but Cam Ranh airport transfer still has to be counted.</td></tr>
<tr><td data-label="Question"><strong>Which feels more premium?</strong></td><td data-label="Answer">Phu Quoc is stronger for resort-led premium. Nha Trang is stronger when premium means better city convenience, seafood, day trips, and hotel choice without island isolation.</td></tr>
<tr><td data-label="Question"><strong>Main mistake?</strong></td><td data-label="Answer">Booking beach nights before deciding whether the route needs recovery, city energy, island mood, or simply fewer stops.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-phu-quoc-nha-trang-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what changes between the choices</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The photographs are used as evidence of route character, not decoration. Phu Quoc sells island-resort distance from the mainland. Nha Trang sells a beachfront city with culture, airport access, and coast/boat-day options close to the stay.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-phu-quoc-nha-trang-photo-grid" aria-label="Phu Quoc and Nha Trang comparison photography">
<figure class="vg-guide-photo"><img src="{$phu_quoc_image}" alt="Aerial view of Kem Beach on Phu Quoc Island" loading="lazy" decoding="async"><figcaption>Phu Quoc is strongest when the route needs a resort island finish. Image: <a href="{$phu_quoc_credit_url}" target="_blank" rel="license noopener">Vivu Vietnam / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$nha_trang_hero}" alt="Nha Trang Beach with city backdrop" loading="lazy" decoding="async"><figcaption>Nha Trang is a city-beach decision with hotels, food, and movement close together. Image: <a href="{$nha_trang_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$po_nagar_image}" alt="Po Nagar Cham towers in Nha Trang, Vietnam" loading="lazy" decoding="async"><figcaption>Po Nagar shows why Nha Trang can be more than beach time when the route wants culture close to the coast. Image: <a href="{$po_nagar_credit_url}" target="_blank" rel="license noopener">Tervlugt / CC BY 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$nha_trang_coast_image}" alt="Panorama of Nha Trang coastline and bay" loading="lazy" decoding="async"><figcaption>Nha Trang's value is the coastal-city spread, not a single isolated beach strip. Image: <a href="{$nha_trang_coast_credit_url}" target="_blank" rel="license noopener">Tony24986 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cam_ranh_terminal_image}" alt="Cam Ranh International Airport terminal serving Nha Trang" loading="lazy" decoding="async"><figcaption>Cam Ranh access can make Nha Trang practical, but the airport transfer still belongs in the cost and time budget. Image: <a href="{$cam_ranh_terminal_credit_url}" target="_blank" rel="license noopener">Ed Crystal / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-phu-quoc-nha-trang-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Source diversity: what each source can actually prove</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This page separates stable destination judgment from live checks. Official tourism pages can frame the places; airport, weather, entry, and image-license sources answer narrower questions.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-phu-quoc-nha-trang-source-diversity">
<thead><tr><th>Source job</th><th>Primary use</th><th>How to read it</th></tr></thead>
<tbody>
<tr><td data-label="Source job">Official destination framing</td><td data-label="Primary use">Vietnam.travel Phu Quoc and Vietnam.travel Nha Trang.</td><td data-label="How to read it">Good for evergreen orientation, but not enough to decide route value by itself.</td></tr>
<tr><td data-label="Source job">Weather and route season</td><td data-label="Primary use">Vietnam.travel weather and climate plus NCHMF live weather/marine checks.</td><td data-label="How to read it">Use official season framing for planning, then recheck live conditions before beach, ferry, or boat-day commitments.</td></tr>
<tr><td data-label="Source job">Transport and airport reality</td><td data-label="Primary use">Vietnam.travel transport and Cam Ranh International Airport.</td><td data-label="How to read it">Useful for access logic; flight times, transfer terms, and baggage assumptions remain booking-time checks.</td></tr>
<tr><td data-label="Source job">Entry and domestic connection risk</td><td data-label="Primary use">Official Vietnam e-visa portal and the site's e-visa guide.</td><td data-label="How to read it">Do not build domestic beach flights before official entry details, dates, and passport data are clean.</td></tr>
<tr><td data-label="Source job">Image proof</td><td data-label="Primary use">Wikimedia Commons image records for Phu Quoc, Nha Trang Beach, Po Nagar, Nha Trang coastline, and Cam Ranh airport.</td><td data-label="How to read it">Images support visible place character and route implications; they do not prove current beach, weather, crowd, or service conditions.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-phu-quoc-nha-trang-decision-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Phu Quoc vs Nha Trang decision matrix</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this as a buying filter before booking flights or hotels. The better beach is the one that improves the route enough to justify the movement.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-phu-quoc-nha-trang-decision-matrix">
<thead><tr><th>Decision</th><th>Choose Phu Quoc when...</th><th>Choose Nha Trang when...</th><th>Skip both when...</th></tr></thead>
<tbody>
<tr><td data-label="Decision">Trip job</td><td data-label="Choose Phu Quoc when...">The trip needs an island finish, resort rest, pool/beach days, and a clear recovery chapter.</td><td data-label="Choose Nha Trang when...">The trip needs a city beach with food, services, coast access, and optional boat/activity days.</td><td data-label="Skip both when...">The beach is only there because the map looks incomplete.</td></tr>
<tr><td data-label="Decision">Route shape</td><td data-label="Choose Phu Quoc when...">Ho Chi Minh City, Mekong, or a longer south-ending route already makes the island logical.</td><td data-label="Choose Nha Trang when...">A central or south-central coast route needs a beach base without turning into a separate island holiday.</td><td data-label="Skip both when...">The route already has Hanoi, Ninh Binh, a bay/cruise decision, Hoi An/Hue, and not enough spare nights.</td></tr>
<tr><td data-label="Decision">Traveler mood</td><td data-label="Choose Phu Quoc when...">You want lower-effort resort days, family ease, warmer island downtime, or a soft landing after a busy route.</td><td data-label="Choose Nha Trang when...">You want restaurants, nightlife, seafood, city walks, culture stops, boat trips, and a less isolated beach rhythm.</td><td data-label="Skip both when...">You actually want mountains, food cities, heritage depth, or fewer domestic transfers.</td></tr>
<tr><td data-label="Decision">Hotel strategy</td><td data-label="Choose Phu Quoc when...">You are willing to choose the exact beach/stay area carefully because resort location shapes the whole stay.</td><td data-label="Choose Nha Trang when...">You want more hotel price bands and city services close to the beach.</td><td data-label="Skip both when...">Hotel location is a guess and transfer/cancellation terms are vague.</td></tr>
<tr><td data-label="Decision">Premium value</td><td data-label="Choose Phu Quoc when...">Premium means resort setting, private transfer ease, quieter beach access, and days that do not need much planning.</td><td data-label="Choose Nha Trang when...">Premium means a stronger room, good food access, city convenience, and the option to do less or more each day.</td><td data-label="Skip both when...">The premium budget would protect a better route elsewhere.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-phu-quoc-nha-trang-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route fit: where each beach belongs</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Phu Quoc and Nha Trang compete with different parts of the itinerary. Phu Quoc competes with southern recovery time and other islands. Nha Trang competes with Da Nang/Hoi An, Quy Nhon, Mui Ne, and the question of whether the coast should be city-led.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-phu-quoc-nha-trang-route-fit">
<thead><tr><th>Route context</th><th>Better move</th><th>What to protect</th><th>Related guide</th></tr></thead>
<tbody>
<tr><td data-label="Route context">10 days in Vietnam</td><td data-label="Better move">Usually skip both unless the trip is intentionally south/central-coast focused.</td><td data-label="What to protect">Hanoi, one northern landscape, one central chapter, and departure slack.</td><td data-label="Related guide"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Route context">14 days in Vietnam</td><td data-label="Better move">Phu Quoc can work as a south-end recovery chapter; Nha Trang works if the coast is already part of the route.</td><td data-label="What to protect">Avoid adding both a beach city and an island.</td><td data-label="Related guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Route context">21 days in Vietnam</td><td data-label="Better move">Choose the beach that changes the trip most: island decompression or active coast base.</td><td data-label="What to protect">Extra time should add margin, not extra transfers for their own sake.</td><td data-label="Related guide"><a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a></td></tr>
<tr><td data-label="Route context">South-ending route</td><td data-label="Better move">Phu Quoc is usually the cleaner beach finish after Ho Chi Minh City or the Mekong.</td><td data-label="What to protect">Flights, ferries if relevant, resort area, and departure buffer.</td><td data-label="Related guide"><a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a></td></tr>
<tr><td data-label="Route context">Central/south-central coast route</td><td data-label="Better move">Nha Trang when a city beach fits better than a remote island detour.</td><td data-label="What to protect">Cam Ranh transfer time, hotel area, day-trip expectations, and weather.</td><td data-label="Related guide"><a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-phu-quoc-nha-trang-season-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Season and weather posture</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Do not use one Vietnam beach season for both places. Phu Quoc sits in the southern island logic; Nha Trang belongs to the south-central coast. Use official season framing early and live weather/marine checks near travel.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-phu-quoc-nha-trang-season-weather">
<thead><tr><th>Season question</th><th>Phu Quoc posture</th><th>Nha Trang posture</th><th>Booking move</th></tr></thead>
<tbody>
<tr><td data-label="Season question">Winter sun</td><td data-label="Phu Quoc posture">Often the stronger resort-island answer when the route wants a warm southern finish.</td><td data-label="Nha Trang posture">Can still work, but treat it as a coast-city choice with local weather checks.</td><td data-label="Booking move">Check cancellation terms before making beach imagery the whole trip.</td></tr>
<tr><td data-label="Season question">Central-coast-friendly months</td><td data-label="Phu Quoc posture">May be less route-efficient if it forces a far southern detour.</td><td data-label="Nha Trang posture">Can be a practical city-beach base when the coast is already logical.</td><td data-label="Booking move">Compare with Da Nang/Hoi An and Quy Nhon before adding extra flights.</td></tr>
<tr><td data-label="Season question">Rain or rough sea risk</td><td data-label="Phu Quoc posture">Flights may still run while beach quality, ferry logic, and water activities change.</td><td data-label="Nha Trang posture">Boat days and island activities should be treated as live-condition extras.</td><td data-label="Booking move">Use NCHMF and operator checks close to travel; keep plans flexible.</td></tr>
<tr><td data-label="Season question">High-demand dates</td><td data-label="Phu Quoc posture">Resort area and beachfront premium can rise quickly.</td><td data-label="Nha Trang posture">City hotel supply helps, but exact beachfront location still matters.</td><td data-label="Booking move">Pay for the area and cancellation terms, not only the brand name.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-phu-quoc-nha-trang-logistics:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Logistics: airport, ferry, and transfer reality</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The transport tax is different. Phu Quoc asks whether an island flight or ferry chain improves the route. Nha Trang asks whether Cam Ranh airport transfer and coast-city positioning beat a simpler central-coast base.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-phu-quoc-nha-trang-logistics">
<thead><tr><th>Logistics item</th><th>Phu Quoc check</th><th>Nha Trang check</th><th>Why it matters</th></tr></thead>
<tbody>
<tr><td data-label="Logistics item">Arrival airport</td><td data-label="Phu Quoc check">Confirm domestic/international connection timing, baggage assumptions, and resort transfer terms.</td><td data-label="Nha Trang check">Confirm Cam Ranh arrival, transfer mode, hotel area, and late-arrival plan.</td><td data-label="Why it matters">The beach starts only after the airport edge is absorbed.</td></tr>
<tr><td data-label="Logistics item">Ferry possibility</td><td data-label="Phu Quoc check">Useful only on some south-only routes; schedule and weather are live checks.</td><td data-label="Nha Trang check">Not the main access question; boat trips are experience choices, not core access.</td><td data-label="Why it matters">A ferry can make the island feel local or make the itinerary fragile.</td></tr>
<tr><td data-label="Logistics item">Hotel area</td><td data-label="Phu Quoc check">Choose the beach/resort zone by job: family, quiet, sunset, airport access, or polished resort time.</td><td data-label="Nha Trang check">Choose whether you want city-center beach access, quieter resort edge, or better day-trip logistics.</td><td data-label="Why it matters">The place name does not explain the real daily movement.</td></tr>
<tr><td data-label="Logistics item">Departure buffer</td><td data-label="Phu Quoc check">Protect a buffer before international departures or important domestic links.</td><td data-label="Nha Trang check">Protect transfer time to Cam Ranh and avoid late risky handoffs.</td><td data-label="Why it matters">Beach time loses value when the exit day becomes stressful.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-phu-quoc-nha-trang-cost-booking:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cost and booking checks</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Beach costs are not just room rates. Count the route friction around the room.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-phu-quoc-nha-trang-cost-booking"} -->
<ul class="wp-block-list vg-check-list vg-phu-quoc-nha-trang-cost-booking">
<li>For Phu Quoc, price the exact beach area, resort isolation, airport transfer, peak-date rate, meal access, and cancellation terms.</li>
<li>For Nha Trang, price the Cam Ranh transfer, beachfront versus city location, boat-day extras, seafood/activity budget, and late-arrival taxi plan.</li>
<li>Do not compare a premium Phu Quoc resort against a midrange Nha Trang hotel and call it a destination comparison.</li>
<li>If either beach requires an extra domestic flight, count the lost half-day and baggage/admin time as part of the beach cost.</li>
<li>Use <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a> before making beach nights non-refundable.</li>
</ul>
<!-- /wp:list -->

<!-- vg-phu-quoc-nha-trang-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Good beach planning includes the courage to remove beach time. A premium route is not the one with more names; it is the one where every movement earns its place.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-phu-quoc-nha-trang-skip-logic">
<thead><tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr></thead>
<tbody>
<tr><td data-label="If you want...">A classic first Vietnam route</td><td data-label="Protect this">Hanoi, one northern landscape, a central chapter, and calm departure logic.</td><td data-label="Skip first">A far beach add-on that forces another flight and a weak one-night stop.</td><td data-label="Only add back when...">The beach solves rest, season, or route shape better than another core stop.</td></tr>
<tr><td data-label="If you want...">Resort recovery</td><td data-label="Protect this">Enough nights for the resort to feel restful.</td><td data-label="Skip first">Nha Trang if city activity will distract from the recovery job.</td><td data-label="Only add back when...">You actually want city energy more than resort stillness.</td></tr>
<tr><td data-label="If you want...">Beach plus food and movement</td><td data-label="Protect this">Walkable meals, easy transport, and flexible day choices.</td><td data-label="Skip first">A remote Phu Quoc resort if it turns every meal or outing into logistics.</td><td data-label="Only add back when...">The resort itself is the reason for the beach chapter.</td></tr>
<tr><td data-label="If you want...">Quiet premium</td><td data-label="Protect this">Lower density, space, and fewer activity pressures.</td><td data-label="Skip first">Assuming either Phu Quoc or Nha Trang automatically means quiet.</td><td data-label="Only add back when...">The exact hotel area and season support the mood.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-phu-quoc-nha-trang-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before you book</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-phu-quoc-nha-trang-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-phu-quoc-nha-trang-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc" target="_blank" rel="noopener">Vietnam.travel Phu Quoc</a> and <a href="https://vietnam.travel/places-to-go/central-vietnam/nha-trang" target="_blank" rel="noopener">Vietnam.travel Nha Trang</a> for official destination orientation.</li>
<li>Use <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> plus the <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">National Centre for Hydro-Meteorological Forecasting</a> for live weather and marine checks before beach, ferry, or boat-day commitments.</li>
<li>Use <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport</a> and <a href="https://www.camranh.aero/en" target="_blank" rel="noopener">Cam Ranh International Airport</a> before treating Nha Trang as an easy beach add-on.</li>
<li>Use the <a href="https://evisa.gov.vn/" target="_blank" rel="noopener">official Vietnam e-visa portal</a> and <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a> before building domestic beach connections around international arrival assumptions.</li>
<li>Compare <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, and <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a> before choosing Phu Quoc over Nha Trang, Con Dao, Da Nang/Hoi An, or skipping beach time.</li>
<li>Cross-check <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before paying non-refundable beach money.</li>
</ul>
<!-- /wp:list -->

<!-- vg-phu-quoc-nha-trang-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Phu Quoc vs Nha Trang FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-phu-quoc-nha-trang-faq">
<details><summary>Is Phu Quoc better than Nha Trang?</summary><p>Phu Quoc is better for an island resort finish, winter-sun recovery, and resort-contained beach days. Nha Trang is better for a city beach with more food, movement, and day-trip options.</p></details>
<details><summary>Which is better for first-time visitors to Vietnam?</summary><p>Neither is automatic. First-time routes usually need Hanoi, one northern landscape, and a central chapter before beach add-ons. Choose Phu Quoc or Nha Trang only when the beach clearly improves the route.</p></details>
<details><summary>Which is better for families?</summary><p>Phu Quoc is stronger for resort-style family ease. Nha Trang is stronger when families want city services, shorter daily pivots, and more activity choice outside the hotel.</p></details>
<details><summary>Which is better for nightlife and restaurants?</summary><p>Nha Trang usually fits travelers who want more city-beach energy, seafood, bars, cafes, and walkable choices. Phu Quoc can work, but exact stay area matters more.</p></details>
<details><summary>Should I visit both Phu Quoc and Nha Trang?</summary><p>Most first trips should not. They solve overlapping beach needs and can add too much transfer friction. Consider both only on a longer coastal or beach-led itinerary where each has a different job.</p></details>
<details><summary>What is the biggest booking mistake?</summary><p>Booking the hotel before deciding the beach's job. The best choice changes if you need recovery, city energy, family ease, boat days, a southern finish, or a shorter route.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then compare beach value in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, island value in <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, and Phu Quoc-specific routing in <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a>. Use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> to decide whether beach time improves the itinerary or simply makes it longer.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-phu-quoc-nha-trang-hero:v1',
    'concierge verdict' => 'vg-phu-quoc-nha-trang-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-phu-quoc-nha-trang-at-a-glance:v1',
    'photo grid' => 'vg-phu-quoc-nha-trang-photo-grid:v1',
    'source diversity' => 'vg-phu-quoc-nha-trang-source-diversity:v1',
    'decision matrix' => 'vg-phu-quoc-nha-trang-decision-matrix:v1',
    'route fit' => 'vg-phu-quoc-nha-trang-route-fit:v1',
    'season weather' => 'vg-phu-quoc-nha-trang-season-weather:v1',
    'logistics' => 'vg-phu-quoc-nha-trang-logistics:v1',
    'cost booking' => 'vg-phu-quoc-nha-trang-cost-booking:v1',
    'skip logic' => 'vg-phu-quoc-nha-trang-skip-logic:v1',
    'live checks' => 'vg-phu-quoc-nha-trang-live-checks:v1',
    'FAQ' => 'vg-phu-quoc-nha-trang-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_pq_nt_ops_assert_required_content_markers($content, $required_content_markers);
vg_pq_nt_ops_assert_internal_page_links_are_published('Phu Quoc vs Nha Trang guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Phu Quoc vs Nha Trang',
    'post_name'      => 'phu-quoc-vs-nha-trang',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_pq_nt_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Phu Quoc vs Nha Trang comparison for international travelers choosing island resort recovery or city-beach routing by season, airport access, costs, hotel area, and skip logic.',
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
    vg_pq_nt_ops_fail('Could not publish Phu Quoc vs Nha Trang guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_pq_nt_ops_fail('Could not publish Phu Quoc vs Nha Trang guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Phu Quoc vs Nha Trang: Which Beach Is Better?');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Phu Quoc vs Nha Trang comparison: choose island resort recovery or city-beach routing by season, airport access, costs, hotel area, and route fit.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Phu Quoc vs Nha Trang');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Phu Quoc island resort recovery or Nha Trang city-beach routing is the better Vietnam beach chapter before booking flights, hotels, transfers, or boat days.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 20, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Phu Quoc vs Nha Trang comparison guide with a concierge verdict, at-a-glance decision table, licensed real photo proof, source-diversity panel, decision matrix, route-fit table, season/weather table, logistics table, cost and booking checks, skip logic, live checks, FAQ, Compare hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Phu Quoc - https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc - checked July 20, 2026\nVietnam.travel - Nha Trang - https://vietnam.travel/places-to-go/central-vietnam/nha-trang - checked July 20, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 20, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 20, 2026\nCam Ranh International Airport - https://www.camranh.aero/en - checked July 20, 2026; live flight and airport-service checks required close to travel\nOfficial Vietnam e-visa portal - https://evisa.gov.vn/ - checked July 20, 2026\nNational Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked July 20, 2026; live weather and marine checks required close to travel\nWikimedia Commons image record - Kem Beach aerial view Phu Quoc Island Vietnam - https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg - license checked July 20, 2026\nWikimedia Commons image record - Nha Trang Beach 3 - https://commons.wikimedia.org/wiki/File:Nha_Trang_Beach_3.jpg - license checked July 20, 2026\nWikimedia Commons image record - Po Nagar Nha Trang Vietnam - https://commons.wikimedia.org/wiki/File:Po_Nagar_Nha_Trang_Vietnam.JPG - license checked July 20, 2026\nWikimedia Commons image record - Panorama of Vinh Nha Trang coastline - https://commons.wikimedia.org/wiki/File:Panorama_of_V%E1%BB%8Bnh_Nha_Trang_coastline.jpg - license checked July 20, 2026\nWikimedia Commons image record - Cam Ranh International Airport Terminal 2 - https://commons.wikimedia.org/wiki/File:Cam_Ranh_International_Airport_Terminal_2.jpg - license checked July 20, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Phu Quoc vs Nha Trang is a route fork: island resort recovery versus city-beach mobility. The stronger choice is the one that improves the trip after season, airport transfer, hotel area, cost, and lost itinerary slack are counted.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Comparison guide built around a real beach-routing decision rather than a generic destination-versus-destination listicle.\nConcierge verdict separates Phu Quoc island resort recovery from Nha Trang city-beach mobility.\nAt-a-glance table answers first-useful questions for international travelers before booking.\nLicensed real photo proof for Phu Quoc, Nha Trang Beach, Po Nagar, Nha Trang coastline, and Cam Ranh airport access.\nSource-diversity panel explains what official tourism, weather, transport, airport, entry, and image-license sources can and cannot prove.\nDecision matrix covers route job, route shape, traveler mood, hotel strategy, and premium value.\nRoute-fit table links the choice to 10, 14, 21-day, south-ending, and central/south-central coast routes.\nSeason/weather table prevents treating Vietnam beach timing as one national rule.\nLogistics table counts Phu Quoc island flight/ferry logic against Nha Trang/Cam Ranh transfer logic.\nCost and booking checks separate room rate from route friction.\nSkip logic protects overfull itineraries from decorative beach add-ons.\nVisible source trail, related routes, and update log.");
$related_routes = "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before choosing any beach chapter.\nBest Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ | Decide whether the trip needs a beach chapter before choosing Phu Quoc or Nha Trang.\nBest Islands in Vietnam | /destinations/best-islands-in-vietnam/ | Compare island value before treating Phu Quoc as the default beach answer.\nPhu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Use this when the island resort finish is still the stronger route job.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Decide whether the route should move south, stay central, or skip the beach fork.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check beach season and regional weather before committing to either coast.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Count domestic flights, Cam Ranh transfer time, ferries, baggage, and departure buffers before booking.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget resort nights, city-beach hotels, airport transfers, boat days, cancellation terms, and route friction.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a short route should skip both beaches first.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide whether one beach chapter earns its place in a two-week route.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use longer timing to decide whether beach time should be island recovery or active coast base.\nVietnam E-Visa | /plan/vietnam-evisa/ | Recheck entry timing before building domestic beach connections around international flights.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check water, boat, scooter, transfer, interruption, and medical coverage before beach travel.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair airport taxis, beach deposits, boat activities, and resort transfers with practical risk checks.\nPhu Quoc vs Nha Trang | /compare/phu-quoc-vs-nha-trang/ | Choose island-resort recovery or city-beach routing before booking beach flights, hotels, transfers, or boat days.";
vg_pq_nt_ops_assert_related_route_meta_links_are_published('Phu Quoc vs Nha Trang related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Nha Trang Beach image: Nha Trang Beach 3 by Christophe95, CC BY-SA 4.0. Body images: Kem Beach aerial view Phu Quoc Island Vietnam by Vivu Vietnam, CC BY-SA 4.0; Po Nagar Nha Trang Vietnam by Tervlugt, CC BY 3.0; Panorama of Vinh Nha Trang coastline by Tony24986, CC BY-SA 4.0; Cam Ranh International Airport Terminal 2 by Ed Crystal, CC BY-SA 4.0.');
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
        vg_pq_nt_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_pq_nt_ops_fail('Phu Quoc vs Nha Trang guide was updated but is not published.');
}

vg_pq_nt_ops_refresh_compare_hub();
vg_pq_nt_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_pq_nt_ops_log("Published Phu Quoc vs Nha Trang guide: {$page_id} {$updated_permalink}");

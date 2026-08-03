<?php
/**
 * Publish the Mekong Delta Travel Guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * VG_FORCE_MEKONG_DELTA_GUIDE_REPUBLISH=1 wp eval-file ops/apply-mekong-delta-travel-guide.php --allow-root
 *
 * Repair only hub/related-route side effects with:
 * VG_REPAIR_MEKONG_DELTA_GUIDE_LINKS=1 wp eval-file ops/apply-mekong-delta-travel-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_mekong_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_mekong_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_mekong_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_MEKONG_DELTA_GUIDE_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_mekong_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_MEKONG_DELTA_GUIDE_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_mekong_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_mekong_ops_fail('Could not resolve a valid WordPress author for the Mekong Delta Travel Guide.');
}

function vg_mekong_ops_internal_path_from_href(string $href): ?string
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

function vg_mekong_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_mekong_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_mekong_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_mekong_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_mekong_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_mekong_ops_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_mekong_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_mekong_ops_log("Validated {$label} internal page links are published.");
}

function vg_mekong_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_mekong_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_mekong_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_mekong_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_mekong_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_mekong_ops_log("Validated {$label} related-route links are published.");
}

function vg_mekong_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_mekong_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
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
            vg_mekong_ops_fail("Could not confidently refresh {$label}.");
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
        vg_mekong_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_mekong_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_mekong_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_mekong_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_mekong_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_mekong_ops_published_page_exists('destinations/mekong-delta-travel-guide')) {
        vg_mekong_ops_log('Skipped Destinations hub refresh: Mekong Delta Travel Guide is not published.');
        return;
    }

    $marker = '<!-- vg-mekong-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use the Mekong Delta when the south needs river time</h3><p>The <a href="/destinations/mekong-delta-travel-guide/">Mekong Delta Travel Guide</a> helps travelers decide whether to use a HCMC day trip, overnight Can Tho and Cai Rang module, slower Ben Tre canal chapter, Chau Doc and Tra Su extension, or skip the Delta when the route is already too full.</p></div>
<!-- /wp:group -->
HTML;

    vg_mekong_ops_assert_internal_page_links_are_published('Destinations hub Mekong note', $block);
    vg_mekong_ops_upsert_marked_group($hub, 'Destinations hub Mekong note', $marker, $block);
}

function vg_mekong_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_mekong_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_mekong_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_mekong_ops_log("Skipped related-route refresh for {$label}: Mekong line is already current.");
        return;
    }

    update_post_meta($page->ID, 'vg_eeat_related_routes', implode("\n", $next_lines));

    vg_mekong_ops_log("Upserted Mekong Delta Travel Guide related route to {$label}: {$page->ID}");
}

function vg_mekong_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Mekong Delta Travel Guide | /destinations/mekong-delta-travel-guide/ | Decide whether the Delta should be a HCMC day trip, overnight Can Tho/Cai Rang module, slower Ben Tre canal chapter, Chau Doc extension, or skip.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
            'destinations/ho-chi-minh-city-travel-guide' => 'Ho Chi Minh City Travel Guide',
            'destinations/phu-quoc-travel-guide' => 'Phu Quoc Travel Guide',
            'destinations/con-dao-travel-guide' => 'Con Dao Travel Guide',
        ] as $path => $label
    ) {
        vg_mekong_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

function vg_mekong_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_mekong_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_mekong_ops_fail('Could not find published /destinations/ parent page.');
}

$page = get_page_by_path('destinations/mekong-delta-travel-guide', OBJECT, 'page');

if (vg_mekong_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_mekong_ops_fail('Repair mode requires the Mekong Delta Travel Guide to already be published.');
    }

    vg_mekong_ops_refresh_destinations_hub();
    vg_mekong_ops_refresh_inbound_related_routes();
    vg_mekong_ops_log("Repaired Mekong Delta Travel Guide side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_mekong_ops_force_republish_enabled()) {
    vg_mekong_ops_fail('Mekong Delta Travel Guide is not a draft. Set VG_FORCE_MEKONG_DELTA_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_mekong_ops_log("Preflight Mekong Delta Travel Guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_mekong_ops_log('Preflight Mekong Delta Travel Guide: no existing page found; creating a child page under /destinations/.');
}

$review_date = 'July 23, 2026';
$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1280px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg');
$cai_rang_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/fb/Cai_Rang_Floating_Market_1.jpg/1280px-Cai_Rang_Floating_Market_1.jpg');
$cai_rang_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cai_Rang_Floating_Market_1.jpg');
$can_tho_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/08/Can_Tho_Mekong_Delta_Virginia.jpg/1280px-Can_Tho_Mekong_Delta_Virginia.jpg');
$can_tho_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Can_Tho_Mekong_Delta_Virginia.jpg');
$ben_tre_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ben_Tre_-Bootsverkehr_auf_dem_Mekong%2C_Anleger.jpg/1280px-Ben_Tre_-Bootsverkehr_auf_dem_Mekong%2C_Anleger.jpg');
$ben_tre_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ben_Tre_-Bootsverkehr_auf_dem_Mekong,_Anleger.jpg');
$my_tho_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/29/Canal_in_Mekong_Delta_-_My_Tho_-_Vietnam_%2815912635245%29.jpg/1280px-Canal_in_Mekong_Delta_-_My_Tho_-_Vietnam_%2815912635245%29.jpg');
$my_tho_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Canal_in_Mekong_Delta_-_My_Tho_-_Vietnam_(15912635245).jpg');
$chau_doc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Vietnam%2C_Panorama_of_Chau_Doc.jpg/1280px-Vietnam%2C_Panorama_of_Chau_Doc.jpg');
$chau_doc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Panorama_of_Chau_Doc.jpg');
$tra_su_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/3/37/Wetland_vegetation_in_Tra_Su_Cajuput_Forest.jpg');
$tra_su_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Wetland_vegetation_in_Tra_Su_Cajuput_Forest.jpg');

$content = <<<HTML
<!-- vg-mekong-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<div class="vg-guide-hero__media"><img src="{$hero_image}" alt="Small boat on a river in the Mekong Delta near Phong Dien, Vietnam" loading="eager" decoding="async"><p class="vg-image-credit">Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>.</p></div>
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Evidence-led destination guide</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Mekong Delta Travel Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-hero-copy"} -->
<p class="vg-hero-copy">Use this guide to decide whether the Mekong Delta should be a HCMC day trip, overnight Can Tho and Cai Rang module, slower Ben Tre canal chapter, Chau Doc and Tra Su extension, or a stop you skip to protect the rest of the route.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-hero-proof-list"} -->
<ul class="wp-block-list vg-hero-proof-list">
<li>Best default: overnight Can Tho when the Delta is a real chapter.</li>
<li>Fast default: one day only when the route cannot protect a night.</li>
<li>Main mistake: squeezing the Delta into departure morning after HCMC.</li>
<li>Last meaningful update: {$review_date}</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-mekong-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-mekong-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-mekong-verdict">
<!-- wp:heading -->
<h2 class="wp-block-heading">Concierge verdict: overnight beats a token day unless the route is already full</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Use the Mekong Delta when the south needs river life, floating-market context, and enough time to avoid a staged final-morning rush.</strong> A HCMC day trip can work as a taste. The stronger first answer is usually one night in Can Tho for Cai Rang or a slower Ben Tre-style canal chapter. Skip the Delta when it steals the only calm day left in HCMC, breaks an island connection, or turns the end of the trip into road time.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-mekong-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-mekong-shortlist">
<li><strong>Choose a day trip if:</strong> you have two HCMC nights, no extra overnight margin, and want a low-commitment river sample.</li>
<li><strong>Choose overnight Can Tho if:</strong> Cai Rang floating market is the point and you can protect the early morning.</li>
<li><strong>Choose Ben Tre if:</strong> you want quieter canals, coconuts, cycling, homestay texture, or less checklist energy.</li>
<li><strong>Choose Chau Doc or Tra Su if:</strong> you have a longer south route, Cambodia angle, bird/wetland interest, or a deliberate far-Delta extension.</li>
<li><strong>Skip if:</strong> your Vietnam route already has Hanoi, Ninh Binh, bay logistics, Hue, Hoi An, HCMC, and a southern island with no buffer.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- vg-mekong-at-a-glance:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mekong Delta at a glance</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-mekong-at-a-glance">
<tbody>
<tr><td data-label="Decision"><strong>Best first use</strong></td><td data-label="VietnamGuide recommendation">One night in Can Tho if Cai Rang matters; one HCMC day trip only when the route cannot afford an overnight.</td></tr>
<tr><td data-label="Decision"><strong>Minimum time</strong></td><td data-label="VietnamGuide recommendation">One full day from HCMC for a taste; one night for a proper early-market or slower-canal chapter.</td></tr>
<tr><td data-label="Decision"><strong>Best base</strong></td><td data-label="VietnamGuide recommendation">Can Tho for Cai Rang and city logistics; Ben Tre for quieter canal/homestay texture; Chau Doc for a far-Delta extension.</td></tr>
<tr><td data-label="Decision"><strong>Best route fit</strong></td><td data-label="VietnamGuide recommendation">After Ho Chi Minh City, before Phu Quoc/Con Dao only if transfer buffers are protected.</td></tr>
<tr><td data-label="Decision"><strong>Main mistake</strong></td><td data-label="VietnamGuide recommendation">Booking a generic long day trip that returns late, then flying or changing hotels the next morning.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-mekong-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: the Delta is not one experience</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-mekong-photo-grid" aria-label="Mekong Delta route proof photography">
<figure class="vg-guide-photo"><img src="{$cai_rang_image}" alt="Boats at Cai Rang Floating Market in Can Tho, Vietnam" loading="lazy" decoding="async"><figcaption>Cai Rang is why many travelers overnight in Can Tho instead of treating the Delta as a generic day trip. Image: <a href="{$cai_rang_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$can_tho_image}" alt="Can Tho river scene in the Mekong Delta, Vietnam" loading="lazy" decoding="async"><figcaption>Can Tho works when the route protects early market timing and does not hide the return drive inside departure day. Image: <a href="{$can_tho_credit_url}" target="_blank" rel="license noopener">Andre Hospers / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$ben_tre_image}" alt="Boats on the Mekong in Ben Tre, Vietnam" loading="lazy" decoding="async"><figcaption>Ben Tre is the calmer canal answer when you want slower water, coconut country, and less attraction stacking. Image: <a href="{$ben_tre_credit_url}" target="_blank" rel="license noopener">Franzfoto / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$my_tho_image}" alt="Canal in the Mekong Delta near My Tho, Vietnam" loading="lazy" decoding="async"><figcaption>My Tho-style day trips are useful as a taste, but they are not the same decision as overnight Can Tho. Image: <a href="{$my_tho_credit_url}" target="_blank" rel="license noopener">Esin Ustun / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$chau_doc_image}" alt="Panorama of Chau Doc in the Mekong Delta, Vietnam" loading="lazy" decoding="async"><figcaption>Chau Doc is a far-Delta extension, not a casual add-on for short HCMC stays. Image: <a href="{$chau_doc_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$tra_su_image}" alt="Wetland vegetation in Tra Su Cajuput Forest, An Giang, Vietnam" loading="lazy" decoding="async"><figcaption>Tra Su belongs to longer south routes where wetland time is the point, not a rushed detour. Image: <a href="{$tra_su_credit_url}" target="_blank" rel="license noopener">Nevillenguyen310 / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-mekong-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Source trail: what each source can actually prove</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-mekong-source-diversity">
<thead><tr><th>Source type</th><th>Use it for</th><th>Do not overclaim</th></tr></thead>
<tbody>
<tr><td data-label="Source type">Vietnam.travel Can Tho and Chau Doc</td><td data-label="Use it for">Official tourism framing and southern destination context.</td><td data-label="Do not overclaim">It does not tell you your exact tour quality, operator honesty, or transfer time.</td></tr>
<tr><td data-label="Source type">Can Tho tourism portal</td><td data-label="Use it for">Current city tourism framing, local context, and destination names.</td><td data-label="Do not overclaim">It is not a substitute for checking hotel pickup, guide language, or boat timing.</td></tr>
<tr><td data-label="Source type">ACV / Can Tho Airport</td><td data-label="Use it for">Airport existence and logistics context if routing through Can Tho.</td><td data-label="Do not overclaim">Most international travelers still use HCMC as the gateway; flight schedules change.</td></tr>
<tr><td data-label="Source type">Vietnam.travel weather and transport</td><td data-label="Use it for">Climate and movement context before booking road-heavy days.</td><td data-label="Do not overclaim">Weather and traffic are live checks, not static promises.</td></tr>
<tr><td data-label="Source type">Wikimedia Commons image records</td><td data-label="Use it for">Licensed visual proof and credit accountability.</td><td data-label="Do not overclaim">Photos prove place texture, not current tour conditions.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-mekong-day-trip:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Day trip, overnight, or skip?</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-mekong-day-trip">
<thead><tr><th>Choice</th><th>Use it when</th><th>Cost to the route</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Choice"><strong>HCMC day trip</strong></td><td data-label="Use it when">You want a river sample and cannot protect an overnight.</td><td data-label="Cost to the route">Long road day, staged stops possible, limited early-market depth.</td><td data-label="VietnamGuide verdict">Acceptable taste; not the deepest Delta answer.</td></tr>
<tr><td data-label="Choice"><strong>Overnight Can Tho</strong></td><td data-label="Use it when">Cai Rang floating market is a real priority and you can start early.</td><td data-label="Cost to the route">One hotel change plus HCMC return or onward planning.</td><td data-label="VietnamGuide verdict">Best first answer when the Delta earns real time.</td></tr>
<tr><td data-label="Choice"><strong>Ben Tre slow chapter</strong></td><td data-label="Use it when">You want canals, coconuts, cycling, homestay pace, and less market-checklist energy.</td><td data-label="Cost to the route">More operator dependence; less iconic than Cai Rang.</td><td data-label="VietnamGuide verdict">Best for slower travelers and families who hate rushed mornings.</td></tr>
<tr><td data-label="Choice"><strong>Chau Doc / Tra Su</strong></td><td data-label="Use it when">You have a longer south route, wetland interest, or a Cambodia/An Giang angle.</td><td data-label="Cost to the route">Farther transfer chain and higher buffer need.</td><td data-label="VietnamGuide verdict">Strong extension; poor short-trip add-on.</td></tr>
<tr><td data-label="Choice"><strong>Skip</strong></td><td data-label="Use it when">HCMC, island, flight, or central Vietnam time is already tight.</td><td data-label="Cost to the route">You lose river contrast.</td><td data-label="VietnamGuide verdict">Often the honest move on 7-10 day trips.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-mekong-overnight:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How to choose the right Delta base</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-mekong-overnight">
<thead><tr><th>Base</th><th>Best for</th><th>Watch out for</th><th>Best route use</th></tr></thead>
<tbody>
<tr><td data-label="Base"><strong>Can Tho</strong></td><td data-label="Best for">Cai Rang, city hotels, straightforward logistics, first Delta overnight.</td><td data-label="Watch out for">Early market timing and return drive after the visit.</td><td data-label="Best route use">HCMC -> Can Tho -> HCMC or onward south with buffer.</td></tr>
<tr><td data-label="Base"><strong>Ben Tre</strong></td><td data-label="Best for">Quieter canals, coconut-country texture, cycling, families, slower pace.</td><td data-label="Watch out for">Operator quality and vague "authentic village" claims.</td><td data-label="Best route use">HCMC add-on when you want calmer water time.</td></tr>
<tr><td data-label="Base"><strong>My Tho / Cai Be</strong></td><td data-label="Best for">Shorter day-trip access and a taste of canals.</td><td data-label="Watch out for">Most likely to become a staged shopping-and-photo circuit.</td><td data-label="Best route use">Only when overnight is impossible.</td></tr>
<tr><td data-label="Base"><strong>Chau Doc</strong></td><td data-label="Best for">Far-Delta atmosphere, Sam Mountain, river life, Tra Su/An Giang extension.</td><td data-label="Watch out for">Distance, heat, and onward timing.</td><td data-label="Best route use">Longer south route, not first-trip compression.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-mekong-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where Mekong fits in a Vietnam route</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-mekong-route-fit">
<thead><tr><th>Trip length</th><th>Best Mekong answer</th><th>What to cut first</th><th>Related guide</th></tr></thead>
<tbody>
<tr><td data-label="Trip length">7 days</td><td data-label="Best Mekong answer">Usually skip unless the trip is south-only.</td><td data-label="What to cut first">Do not cut HCMC arrival buffer or central/northern core for a token Delta day.</td><td data-label="Related guide"><a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a></td></tr>
<tr><td data-label="Trip length">10 days</td><td data-label="Best Mekong answer">Add only if the south is the trip priority.</td><td data-label="What to cut first">Drop one northern/central stop before stacking HCMC and Mekong.</td><td data-label="Related guide"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Trip length">14 days</td><td data-label="Best Mekong answer">One night can work after HCMC if flights are open-jaw.</td><td data-label="What to cut first">Do not cut departure buffer or turn Hue/Hoi An into one-night stops.</td><td data-label="Related guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Trip length">21 days</td><td data-label="Best Mekong answer">Use Can Tho, Ben Tre, or Chau Doc as a deliberate southern chapter.</td><td data-label="What to cut first">Avoid adding every island and every mountain after the Delta.</td><td data-label="Related guide"><a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-mekong-season-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Season and weather posture</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The Delta is warm year-round, but the practical question is not just temperature. It is road time, rain timing, boat comfort, early starts, and whether a weather delay can damage a flight or island connection. Use <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> and <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a> before treating a boat or road-heavy day as fixed.</p>
<!-- /wp:paragraph -->

<!-- vg-mekong-transport-logistics:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Transport and booking logistics</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-mekong-transport-logistics"} -->
<ul class="wp-block-list vg-check-list vg-mekong-transport-logistics">
<li>Use <a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a> first because most Mekong decisions begin and end with HCMC airport, hotel, and road timing.</li>
<li>Check <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport within Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and the exact operator pickup/drop-off before paying.</li>
<li>Use <a href="https://vietnamairport.vn/en/can-tho-airport" target="_blank" rel="noopener">Can Tho Airport</a> and <a href="https://acv.vn/en/airports/can-tho-international-airport" target="_blank" rel="noopener">ACV Can Tho International Airport</a> only as routing context; do not assume your preferred flight exists without a current schedule check.</li>
<li>Protect phone data, cash, and messaging before leaving HCMC; use <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a> and <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>.</li>
</ul>
<!-- /wp:list -->

<!-- vg-mekong-cost-booking:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cost and booking discipline</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The cheapest Mekong product is often not the best route value. A low-cost day tour can spend the day solving operator economics instead of your travel decision. Before booking, compare total cost: HCMC hotel night, early pickup, private or group transfer, guide language, boat timing, meals, tipping, return time, and what happens if rain or traffic pushes the day late. Use <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>, and <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a> before locking non-refundable movement.</p>
<!-- /wp:paragraph -->

<!-- vg-mekong-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">When to skip the Mekong Delta</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-mekong-skip-logic">
<thead><tr><th>Pressure point</th><th>Better move</th><th>Why</th></tr></thead>
<tbody>
<tr><td data-label="Pressure point">You have one final HCMC night</td><td data-label="Better move">Stay in the city and protect departure logistics.</td><td data-label="Why">The Delta will become road time disguised as variety.</td></tr>
<tr><td data-label="Pressure point">You already booked Phu Quoc or Con Dao next</td><td data-label="Better move">Protect the island transfer and buffer.</td><td data-label="Why">Southern island logistics punish optimistic timing.</td></tr>
<tr><td data-label="Pressure point">You want Cai Rang but refuse an overnight</td><td data-label="Better move">Either overnight Can Tho or choose a different HCMC day.</td><td data-label="Why">The strongest market context depends on early timing.</td></tr>
<tr><td data-label="Pressure point">You dislike long road days</td><td data-label="Better move">Use HCMC food/history or choose a slower Ben Tre stay.</td><td data-label="Why">A group day trip can feel like transport with scenery breaks.</td></tr>
<tr><td data-label="Pressure point">The route is already north-central-south in 10 days</td><td data-label="Better move">Remove Mekong first.</td><td data-label="Why">Ninh Binh, bay, Hue, Hoi An, HCMC, and Mekong cannot all breathe in that calendar.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-mekong-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Official checks before booking Mekong</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-mekong-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-mekong-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/southern-vietnam/can-tho" target="_blank" rel="noopener">Vietnam.travel Can Tho</a>, <a href="https://vietnam.travel/places-to-go/southern-vietnam/chau-doc" target="_blank" rel="noopener">Vietnam.travel Chau Doc</a>, and <a href="https://tourismcantho.vn" target="_blank" rel="noopener">Can Tho Tourism</a> for official destination context.</li>
<li>Use <a href="https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city" target="_blank" rel="noopener">Vietnam.travel Ho Chi Minh City</a> and <a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a> before assuming HCMC has enough protected time.</li>
<li>Use <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> and <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">NCHMF</a> for weather posture before boat or road-heavy days.</li>
<li>Use <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport</a>, <a href="https://vietnamairport.vn/en/can-tho-airport" target="_blank" rel="noopener">Can Tho Airport</a>, and <a href="https://acv.vn/en/airports/can-tho-international-airport" target="_blank" rel="noopener">ACV Can Tho International Airport</a> for transport context.</li>
<li>Use <a href="https://vietnam.travel/plan-your-trip/getting-vietnam" target="_blank" rel="noopener">Vietnam.travel getting to Vietnam</a>, <a href="https://evisa.gov.vn/" target="_blank" rel="noopener">the official Vietnam e-visa portal</a>, and <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a> before building the south around international arrival assumptions.</li>
</ul>
<!-- /wp:list -->

<!-- vg-mekong-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mekong Delta Travel Guide FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-mekong-faq">
<details><summary>Is the Mekong Delta worth visiting from Ho Chi Minh City?</summary><p>Yes when it adds river life, floating-market context, or a slower southern chapter. It is weaker when squeezed into the final morning before a flight or added to an already full 7-10 day route.</p></details>
<details><summary>Is a Mekong Delta day trip enough?</summary><p>A day trip is enough for a taste, especially from HCMC, but not enough for the strongest Cai Rang floating-market timing. Overnight Can Tho is the better first answer when the Delta is a real priority.</p></details>
<details><summary>Should I stay in Can Tho or Ben Tre?</summary><p>Choose Can Tho for Cai Rang, easier hotels, and first-time logistics. Choose Ben Tre for quieter canals, coconut-country texture, cycling, and a slower pace.</p></details>
<details><summary>How many days do you need in the Mekong Delta?</summary><p>One full day gives a taste. One night gives a proper first chapter. Two or more nights make sense only when southern Vietnam is a route priority or you are adding Chau Doc, Tra Su, or a deeper homestay rhythm.</p></details>
<details><summary>Can I combine Mekong Delta and Phu Quoc?</summary><p>Yes, but only with transfer buffers. Do not stack HCMC, Mekong, island movement, and international departure too tightly.</p></details>
<details><summary>Should I skip Mekong on a first Vietnam trip?</summary><p>Skip it when the route already has north, central, HCMC, and an island or beach chapter with no flexible day left. Keep it when southern Vietnam is the point, not a late extra.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then decide whether the south deserves room through <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a> and <a href="/destinations/ho-chi-minh-city-travel-guide/">Ho Chi Minh City Travel Guide</a>. Use <a href="/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/">Cu Chi Tunnels vs Mekong Delta Day Trip</a> when one HCMC spare day has already collapsed into two famous names. Use <a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> to decide whether the Delta fits the calendar. Use <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>, <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>, <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a>, and <a href="/destinations/con-dao-travel-guide/">Con Dao Travel Guide</a> before locking the southern exit chain.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-mekong-hero:v1',
    'concierge verdict' => 'vg-mekong-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-mekong-at-a-glance:v1',
    'photo grid' => 'vg-mekong-photo-grid:v1',
    'source diversity' => 'vg-mekong-source-diversity:v1',
    'day trip' => 'vg-mekong-day-trip:v1',
    'overnight' => 'vg-mekong-overnight:v1',
    'route fit' => 'vg-mekong-route-fit:v1',
    'season weather' => 'vg-mekong-season-weather:v1',
    'transport logistics' => 'vg-mekong-transport-logistics:v1',
    'cost booking' => 'vg-mekong-cost-booking:v1',
    'skip logic' => 'vg-mekong-skip-logic:v1',
    'live checks' => 'vg-mekong-live-checks:v1',
    'FAQ' => 'vg-mekong-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_mekong_ops_assert_required_content_markers($content, $required_content_markers);
vg_mekong_ops_assert_internal_page_links_are_published('Mekong Delta Travel Guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Mekong Delta Travel Guide',
    'post_name'      => 'mekong-delta-travel-guide',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_mekong_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Mekong Delta Travel Guide for international travelers deciding day trip, overnight Can Tho, Ben Tre, Chau Doc, Tra Su, Phu Quoc connection, or skip from Ho Chi Minh City.',
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
    vg_mekong_ops_fail('Could not publish Mekong Delta Travel Guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_mekong_ops_fail('Could not publish Mekong Delta Travel Guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Mekong Delta Travel Guide: Day Trip, Overnight or Skip?');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Mekong Delta travel guide from HCMC: decide day trip, overnight Can Tho and Cai Rang, Ben Tre, Chau Doc, Tra Su, costs, transport, and skip logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Mekong Delta travel guide');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether the Mekong Delta should be a Ho Chi Minh City day trip, overnight Can Tho/Cai Rang module, Ben Tre slow chapter, Chau Doc/Tra Su extension, island connector, or skip.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Mekong Delta Travel Guide with a concierge verdict, at-a-glance decision table, licensed real photo proof, source-diversity panel, day-trip versus overnight decision table, base chooser, route-fit table, weather posture, transport logistics, cost and booking discipline, skip logic, live official checks, FAQ, Destinations hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Can Tho destination page - https://vietnam.travel/places-to-go/southern-vietnam/can-tho - checked {$review_date}\nVietnam.travel - Chau Doc destination page - https://vietnam.travel/places-to-go/southern-vietnam/chau-doc - checked {$review_date}\nCan Tho Tourism portal - https://tourismcantho.vn - checked {$review_date}\nVietnam.travel - Ho Chi Minh City destination page - https://vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city - checked {$review_date}\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}\nNational Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked {$review_date}\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}\nCan Tho Airport - https://vietnamairport.vn/en/can-tho-airport - checked {$review_date}\nACV - Can Tho International Airport - https://acv.vn/en/airports/can-tho-international-airport - checked {$review_date}\nVietnam.travel - Getting to Vietnam - https://vietnam.travel/plan-your-trip/getting-vietnam - checked {$review_date}\nOfficial Vietnam e-visa portal - https://evisa.gov.vn/ - checked {$review_date}\nWikimedia Commons image record - Vietnam, Phong Dien, Mekong Delta, River - https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg - license checked {$review_date}\nWikimedia Commons image record - Cai Rang Floating Market 1 - https://commons.wikimedia.org/wiki/File:Cai_Rang_Floating_Market_1.jpg - license checked {$review_date}\nWikimedia Commons image record - Can Tho Mekong Delta Virginia - https://commons.wikimedia.org/wiki/File:Can_Tho_Mekong_Delta_Virginia.jpg - license checked {$review_date}\nWikimedia Commons image record - Ben Tre - Bootsverkehr auf dem Mekong, Anleger - https://commons.wikimedia.org/wiki/File:Ben_Tre_-Bootsverkehr_auf_dem_Mekong,_Anleger.jpg - license checked {$review_date}\nWikimedia Commons image record - Canal in Mekong Delta - My Tho - Vietnam - https://commons.wikimedia.org/wiki/File:Canal_in_Mekong_Delta_-_My_Tho_-_Vietnam_(15912635245).jpg - license checked {$review_date}\nWikimedia Commons image record - Vietnam, Panorama of Chau Doc - https://commons.wikimedia.org/wiki/File:Vietnam,_Panorama_of_Chau_Doc.jpg - license checked {$review_date}\nWikimedia Commons image record - Wetland vegetation in Tra Su Cajuput Forest - https://commons.wikimedia.org/wiki/File:Wetland_vegetation_in_Tra_Su_Cajuput_Forest.jpg - license checked {$review_date}");
update_post_meta($page_id, 'vg_eeat_field_note', 'The Mekong Delta is strongest when it earns a real route job: Cai Rang overnight, slower Ben Tre canal time, Chau Doc/Tra Su extension, or a deliberate day-trip taste. It is weakest when added as a final-morning checklist from HCMC.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Mekong Delta guide built as a day-trip versus overnight versus skip decision guide rather than a generic attractions list.\nConcierge verdict separates HCMC day trip, overnight Can Tho, Ben Tre slow chapter, Chau Doc/Tra Su extension, and skip cases.\nAt-a-glance table answers best first use, minimum time, best base, route fit, and main mistake.\nLicensed real photo proof for Phong Dien river, Cai Rang floating market, Can Tho, Ben Tre, My Tho canal, Chau Doc, and Tra Su Cajuput Forest.\nSource-diversity panel explains what official tourism, airport, transport, weather, e-visa, and image-license sources can and cannot prove.\nDay-trip table prevents a generic tour from being treated as the same product as overnight Can Tho.\nBase chooser separates Can Tho, Ben Tre, My Tho/Cai Be, and Chau Doc by trip job.\nRoute-fit table ties the Delta to 7, 10, 14, and 21-day calendars.\nTransport/logistics and cost/booking sections count road time, airport assumptions, phone data, money, safety, and insurance.\nSkip logic protects HCMC, island transfers, and international departure timing.\nVisible source trail, related routes, and update log.");
$related_routes = "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before deciding whether the Delta belongs in the south.\nHo Chi Minh City Travel Guide | /destinations/ho-chi-minh-city-travel-guide/ | Decide whether HCMC has enough protected city time before adding a river chapter.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether southern Vietnam should be a real chapter or only an exit airport.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check southern weather posture before locking boat or road-heavy days.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Count HCMC road time, Can Tho routing, pickup/drop-off, and onward transfer risk.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price the real cost of a day trip, overnight Can Tho, private transfer, or far-Delta extension.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Prepare cash, cards, market payments, tips, and rural backup before leaving HCMC.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, pickup contact, operator messaging, and hotel coordination alive during road and boat days.\nVietnam E-Visa | /plan/vietnam-evisa/ | Recheck official entry timing before building HCMC and Mekong around international arrival assumptions.\n7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Decide when short trips should skip the Delta unless the south is the core route.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether HCMC and Mekong can fit without thinning north and central Vietnam.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide whether two weeks can support a real south chapter after north and central Vietnam.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use three weeks to give the Delta, HCMC, and one extension enough margin.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check road days, heat, medical access, trip interruption, and onward transfer coverage.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair rural tours, markets, taxis, cash, phone handling, and operator deposits with practical risk habits.\nPhu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Decide whether a southern island finish after HCMC and the Delta actually improves the route.\nCon Dao Travel Guide | /destinations/con-dao-travel-guide/ | Compare the Delta with a quieter premium island chapter when the south has limited nights.";
vg_mekong_ops_assert_related_route_meta_links_are_published('Mekong Delta Travel Guide related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero image: Vietnam, Phong Dien, Mekong Delta, River by Vyacheslav Argenberg, CC BY 4.0. Body images: Cai Rang Floating Market 1 by Christophe95, CC BY-SA 4.0; Can Tho Mekong Delta Virginia by Andre Hospers, CC BY 4.0; Ben Tre - Bootsverkehr auf dem Mekong, Anleger by Franzfoto, CC BY-SA 3.0; Canal in Mekong Delta - My Tho - Vietnam by Esin Ustun, CC BY 2.0; Vietnam, Panorama of Chau Doc by Vyacheslav Argenberg, CC BY 4.0; Wetland vegetation in Tra Su Cajuput Forest by Nevillenguyen310, CC BY-SA 4.0.');
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
        vg_mekong_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_mekong_ops_fail('Mekong Delta Travel Guide was updated but is not published.');
}

vg_mekong_ops_refresh_destinations_hub();
vg_mekong_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_mekong_ops_log("Published Mekong Delta Travel Guide: {$page_id} {$updated_permalink}");

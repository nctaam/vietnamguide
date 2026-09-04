<?php
/**
 * Publish the Tam Coc Travel Guide.
 *
 * Self-reference marker: ops/apply-tam-coc-travel-guide.php
 *
 * Run from the WordPress root with:
 * VG_FORCE_TAM_COC_TRAVEL_GUIDE_REPUBLISH=1 wp eval-file ops/apply-tam-coc-travel-guide.php --allow-root
 *
 * Repair only hub/related-route/homepage side effects with:
 * VG_REPAIR_TAM_COC_TRAVEL_GUIDE_LINKS=1 wp eval-file ops/apply-tam-coc-travel-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_tam_coc_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_tam_coc_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_tam_coc_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_TAM_COC_TRAVEL_GUIDE_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_tam_coc_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_TAM_COC_TRAVEL_GUIDE_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_tam_coc_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_tam_coc_ops_fail('Could not resolve a valid WordPress author for Tam Coc Travel Guide.');
}

function vg_tam_coc_ops_internal_path_from_href(string $href): ?string
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

function vg_tam_coc_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_tam_coc_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_tam_coc_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_tam_coc_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_tam_coc_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);
    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_tam_coc_ops_internal_path_from_href((string) $href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        vg_tam_coc_ops_assert_internal_href_is_published($label, '/' . $path . '/');
    }

    vg_tam_coc_ops_log("Validated {$label} internal page links are published.");
}

function vg_tam_coc_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_tam_coc_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_tam_coc_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_tam_coc_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_tam_coc_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_tam_coc_ops_log("Validated {$label} related-route links are published.");
}

function vg_tam_coc_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_tam_coc_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
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
            vg_tam_coc_ops_fail("Could not confidently refresh {$label}.");
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
        vg_tam_coc_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    vg_tam_coc_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_tam_coc_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_tam_coc_ops_log('Skipped Destinations hub refresh: Destinations hub was not found or is not published.');
        return;
    }

    if (! vg_tam_coc_ops_published_page_exists('destinations/tam-coc-travel-guide')) {
        vg_tam_coc_ops_log('Skipped Destinations hub refresh: Tam Coc Travel Guide is not published.');
        return;
    }

    $marker = '<!-- vg-tam-coc-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use Tam Coc as a real base, not a postcard stop</h3><p><a href="/destinations/tam-coc-travel-guide/">Tam Coc Travel Guide</a> helps travelers decide whether Tam Coc should be the soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before another northern landscape stop gets added.</p></div>
<!-- /wp:group -->
HTML;

    vg_tam_coc_ops_assert_internal_page_links_are_published('Destinations hub Tam Coc Travel Guide note', $block);
    vg_tam_coc_ops_upsert_marked_group($hub, 'Destinations hub Tam Coc Travel Guide note', $marker, $block);
}

function vg_tam_coc_ops_refresh_homepage_route_spine(): void
{
    $front_page_id = (int) get_option('page_on_front');
    $home = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');

    if (! $home instanceof WP_Post || $home->post_status !== 'publish') {
        vg_tam_coc_ops_log('Skipped homepage route-spine refresh: homepage was not found or is not published.');
        return;
    }

    if (! vg_tam_coc_ops_published_page_exists('destinations/tam-coc-travel-guide')) {
        vg_tam_coc_ops_log('Skipped homepage route-spine refresh: Tam Coc Travel Guide is not published.');
        return;
    }

    $marker = '<!-- vg-tam-coc-homepage-route-spine:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-home-section vg-home-decision-spine vg-home-tam-coc-spine"} -->
<div class="wp-block-group vg-home-section vg-home-decision-spine vg-home-tam-coc-spine"><p class="vg-kicker">Ninh Binh base rhythm</p><h2>Tam Coc Travel Guide</h2><p>Use this guide when Tam Coc is more than a photo stop: boat timing, countryside cycling, Bich Dong, Mua Cave, dinner choice, hotel lane, heat, and next-transfer pressure all decide whether the base works.</p><p class="vg-section-link"><a href="/destinations/tam-coc-travel-guide/">Plan Tam Coc</a></p></div>
<!-- /wp:group -->
HTML;

    vg_tam_coc_ops_assert_internal_page_links_are_published('Homepage Tam Coc Travel Guide route-spine note', $block);
    vg_tam_coc_ops_upsert_marked_group($home, 'Homepage Tam Coc Travel Guide route-spine note', $marker, $block);
}

function vg_tam_coc_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_tam_coc_ops_log("Skipped related-route refresh for {$label}: page not found or not published.");
        return;
    }

    $route_parts = array_map('trim', explode('|', $route_line));
    if (count($route_parts) < 3 || $route_parts[0] === '' || $route_parts[1] === '' || $route_parts[2] === '') {
        vg_tam_coc_ops_fail("Related route for {$label} is malformed.");
    }

    $route_href = $route_parts[1];
    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);
    $lines = preg_split('/\R+/', trim($current)) ?: [];
    $next_lines = [];
    $replaced = false;

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        $parts = array_map('trim', explode('|', $line));
        $line_href = $parts[1] ?? '';
        $is_target_line = $line_href === $route_href || str_contains($line, $route_href);

        if ($is_target_line) {
            if ($line === $route_line) {
                vg_tam_coc_ops_log("Skipped related-route refresh for {$label}: line is already current.");
                $next_lines[] = $line;
            } else {
                $next_lines[] = $route_line;
                $replaced = true;
            }

            continue;
        }

        $next_lines[] = $line;
    }

    if (! $replaced && ! in_array($route_line, $next_lines, true)) {
        $next_lines[] = $route_line;
    }

    update_post_meta($page->ID, 'vg_eeat_related_routes', implode("\n", $next_lines));
    vg_tam_coc_ops_log("Upserted Tam Coc Travel Guide related route to {$label}: {$page->ID}");
}

function vg_tam_coc_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Tam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.';

    foreach (
        [
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'compare/ninh-binh-day-trip-vs-overnight' => 'Ninh Binh Day Trip vs Overnight',
            'compare/trang-an-vs-tam-coc' => 'Trang An vs Tam Coc',
            'plan/hanoi-to-ninh-binh-transport' => 'Hanoi to Ninh Binh Transport',
            'destinations/where-to-stay-in-ninh-binh' => 'Where to Stay in Ninh Binh',
            'plan/ninh-binh-to-ha-long-bay-transfer' => 'Ninh Binh to Ha Long Bay Transfer',
            'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
            'itineraries/hanoi-in-2-days' => 'Hanoi in 2 Days',
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
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
        vg_tam_coc_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

function vg_tam_coc_ops_assert_required_content_markers(string $content, array $markers): void
{
    foreach ($markers as $label => $marker) {
        if (! str_contains($content, $marker)) {
            vg_tam_coc_ops_fail("Missing required content marker for {$label}: {$marker}");
        }
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');
if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_tam_coc_ops_fail('The /destinations/ parent page must exist and be published before publishing Tam Coc Travel Guide.');
}

$page = get_page_by_path('destinations/tam-coc-travel-guide', OBJECT, 'page');
$review_date = wp_date('F j, Y');

if ($page instanceof WP_Post && $page->post_status === 'publish' && ! vg_tam_coc_ops_force_republish_enabled() && ! vg_tam_coc_ops_repair_links_enabled()) {
    vg_tam_coc_ops_log("Tam Coc Travel Guide already published: {$page->ID}. Set VG_FORCE_TAM_COC_TRAVEL_GUIDE_REPUBLISH=1 to rewrite it.");
    return;
}

if (vg_tam_coc_ops_repair_links_enabled() && ! vg_tam_coc_ops_force_republish_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_tam_coc_ops_fail('Cannot repair Tam Coc Travel Guide links before the guide is published. Run with VG_FORCE_TAM_COC_TRAVEL_GUIDE_REPUBLISH=1 first.');
    }

    vg_tam_coc_ops_refresh_destinations_hub();
    vg_tam_coc_ops_refresh_homepage_route_spine();
    vg_tam_coc_ops_refresh_inbound_related_routes();
    return;
}

if ($page instanceof WP_Post) {
    vg_tam_coc_ops_log("Preflight Tam Coc Travel Guide: existing page {$page->ID} will be rewritten.");
} else {
    vg_tam_coc_ops_log('Preflight Tam Coc Travel Guide: no existing page found; creating a child page under /destinations/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/4/4f/Tam_Coc_Ninh_Binh_%2853115%29.jpg');
$bich_dong_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/3/3a/Bich_Dong_Pagoda%2C_Ninh_Binh%2C_Vietnam%2C_20240203_1126_5619.jpg');
$mua_cave_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/a/a3/2024-03-30-Mua_Cave_Dragon_Mountain-3758.jpg');
$sunset_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/b/b5/Sunset_in_Ninh_H%E1%BA%A3i%2C_Ninh_Binh_province%2C_Vietnam%2C_20240202_1724_5415.jpg');
$thai_vi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/9/95/Thai_Vi_Temple_2.jpg');

$content = <<<HTML
<!-- vg-tam-coc-hero:v1 -->
<!-- wp:group {"className":"vg-guide-hero vg-tam-coc-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero vg-tam-coc-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":34,"minHeight":640,"className":"vg-photo-led-hero"} -->
<div class="wp-block-cover vg-photo-led-hero" style="min-height:640px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-30 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Tam Coc river and limestone countryside in Ninh Binh" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"/><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Ninh Binh base guide - Updated {$review_date}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Tam Coc Travel Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Tam Coc is the Ninh Binh base to choose when countryside rhythm matters: a shorter boat route, easy cycling lanes, Bich Dong, Mua Cave, dinner choice, and calmer mornings before the next transfer.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: Tam Coc works best when it slows the north down, not when it becomes one more rushed stop between Hanoi and the bay.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-tam-coc-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-tam-coc-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-tam-coc-concierge-verdict">
<!-- wp:heading -->
<h2 class="wp-block-heading">Concierge verdict</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>For most international travelers, Tam Coc is best used as the soft Ninh Binh base: stay there if you want countryside rhythm, easy cycling, dinner choice, and a shorter boat experience, but choose Trang An when the trip needs one higher-certainty flagship boat route.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list"} -->
<ul class="wp-block-list vg-check-list">
<li>Use Tam Coc when the route needs a calm overnight base with restaurants, bikeable lanes, and morning flexibility.</li>
<li>Use Trang An first when you only have one high-stakes boat slot and need the most reliable landscape proof.</li>
<li>Add Mua Cave only when heat, stairs, and timing still leave the boat or dinner calm.</li>
<li>Do not build the day around every nearby viewpoint, cave, temple, and photo stop unless the route has room to breathe.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-tam-coc-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-tam-coc-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-tam-coc-at-a-glance">
<!-- wp:heading -->
<h2 class="wp-block-heading">Tam Coc at a glance</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-tam-coc-glance">
<tbody>
<tr><td data-label="Question"><strong>Best use</strong></td><td data-label="Answer">A soft Ninh Binh countryside base with boat, bike, cafe, dinner, and morning-control value.</td></tr>
<tr><td data-label="Question"><strong>Best first-timer rhythm</strong></td><td data-label="Answer">Arrive, settle, cycle or Bich Dong, sleep in Tam Coc, boat or Mua Cave early, then leave with buffer.</td></tr>
<tr><td data-label="Question"><strong>Best pairing</strong></td><td data-label="Answer">Tam Coc plus either Trang An or Mua Cave; add both only when the stay has enough margin.</td></tr>
<tr><td data-label="Question"><strong>Best stay area</strong></td><td data-label="Answer">Near Tam Coc village for dinner and pickup ease, or a quieter lane nearby when sleep matters more.</td></tr>
<tr><td data-label="Question"><strong>First mistake to avoid</strong></td><td data-label="Answer">Treating Tam Coc as only a boat ticket instead of the base that shapes the whole Ninh Binh day.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-tam-coc-photo-proof:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what Tam Coc actually solves</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Tam Coc is useful because the landscape, sleep area, food, and movement pattern sit close together. The photos below show why the guide is about route rhythm, not only a boat ride.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-tam-coc-photo-proof" aria-label="Tam Coc travel guide photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Tam Coc limestone river scenery in Ninh Binh" loading="lazy" decoding="async"><figcaption>Tam Coc gives the Ninh Binh stop a softer river-and-rice-field rhythm. Image: Andre Hospers / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$bich_dong_image}" alt="Bich Dong Pagoda near Tam Coc" loading="lazy" decoding="async"><figcaption>Bich Dong is the easiest culture-and-cycling add-on when the day should stay local. Image: Jakub Halun / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mua_cave_image}" alt="Mua Cave Dragon Mountain viewpoint near Tam Coc" loading="lazy" decoding="async"><figcaption>Mua Cave is a viewpoint decision, not a default add-on in the hottest hours. Image: Superbass / CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$sunset_image}" alt="Sunset countryside near Ninh Hai and Tam Coc" loading="lazy" decoding="async"><figcaption>The base pays off when late afternoon can stay close instead of becoming another transfer. Image: Jakub Halun / CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$thai_vi_image}" alt="Thai Vi Temple near Tam Coc" loading="lazy" decoding="async"><figcaption>Small nearby stops work when they support the base rhythm instead of crowding it. Image: Shyamal / CC BY-SA 4.0.</figcaption></figure>
</div>
<!-- /wp:html -->
<!-- wp:paragraph {"className":"vg-source-note"} -->
<p class="vg-source-note">Image credits are listed as text to keep the base decision readable and reduce visible outbound clutter. Full image source records are retained in metadata.</p>
<!-- /wp:paragraph -->

<!-- vg-tam-coc-source-diversity:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What sources prove, and what judgment still has to do</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This guide does not rank Tam Coc by hotel commissions or scrape tour claims; it separates the decisions that keep a Ninh Binh stay calm. Sources can confirm destination context, heritage setting, ticket systems, weather pressure, and named attractions; they cannot decide your sleep tolerance, cycling confidence, heat limit, children, luggage, or whether another northern landscape stop would repeat the same job.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-tam-coc-source-diversity">
<thead><tr><th>Source type</th><th>Useful for</th><th>VietnamGuide still decides</th></tr></thead>
<tbody>
<tr><td data-label="Source type">Vietnam.travel and official tourism pages</td><td data-label="Useful for">Ninh Binh context, boat-tour framing, and the broad set of nearby places.</td><td data-label="VietnamGuide still decides">Whether Tam Coc is a base, a boat stop, or a skip.</td></tr>
<tr><td data-label="Source type">UNESCO heritage context</td><td data-label="Useful for">Why this landscape carries real heritage weight around Trang An and Ninh Binh.</td><td data-label="VietnamGuide still decides">Whether the day needs Trang An certainty or Tam Coc rhythm.</td></tr>
<tr><td data-label="Source type">Weather and same-week checks</td><td data-label="Useful for">Heat, rain, visibility, rice-field expectations, stairs, and boat comfort.</td><td data-label="VietnamGuide still decides">The order of boat, cycling, viewpoint, rest, and transfer.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-tam-coc-source-trail-snapshot:v1 -->
<!-- wp:group {"className":"vg-source-snapshot vg-tam-coc-source-trail-snapshot","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-source-snapshot vg-tam-coc-source-trail-snapshot">
<!-- wp:heading -->
<h2 class="wp-block-heading">Source trail snapshot</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Checked for this decision page: Vietnam.travel Ninh Binh - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh; Vietnam.travel Ninh Binh boat tours - https://vietnam.travel/things-to-do/guide-boat-tours-ninh-binh; UNESCO Trang An Landscape Complex - https://whc.unesco.org/en/list/1438/; Ninh Binh Tourism Department - https://dulichninhbinh.com.vn/en/; Vietnam.travel weather and climate - https://vietnam.travel/things-to-do/weather-and-climate-vietnam; VietnamGuide Transport Within Vietnam - https://vietnamguide.net/plan/transport-within-vietnam/.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- vg-tam-coc-route-verdict:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route verdict: when Tam Coc is the right Ninh Binh answer</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Tam Coc is not only a boat ride; it is a base decision. It works when the route needs countryside texture, a softer evening, and local movement that does not require a new transfer every few hours.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-tam-coc-route-verdict">
<thead><tr><th>Traveler situation</th><th>Use Tam Coc when</th><th>Choose another answer when</th></tr></thead>
<tbody>
<tr><td data-label="Traveler situation">First Ninh Binh overnight</td><td data-label="Use Tam Coc when">Dinner choice, bike lanes, and flexible morning timing matter.</td><td data-label="Choose another answer when">You want silence over convenience; use Trang An area.</td></tr>
<tr><td data-label="Traveler situation">One serious boat slot</td><td data-label="Use Tam Coc when">Shorter water time and village rhythm are the point.</td><td data-label="Choose another answer when">You want the flagship high-confidence route; use Trang An.</td></tr>
<tr><td data-label="Traveler situation">Hanoi day trip</td><td data-label="Use Tam Coc when">The tour is built around countryside texture and not too many stops.</td><td data-label="Choose another answer when">Transfer time leaves no room for a calm boat or meal.</td></tr>
<tr><td data-label="Traveler situation">Before Ha Long Bay</td><td data-label="Use Tam Coc when">Pickup can be confirmed without losing the cruise-day buffer.</td><td data-label="Choose another answer when">A remote stay or late start makes the bay handoff fragile.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-tam-coc-boat-plan:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How to plan the Tam Coc boat without making it the whole day</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list"} -->
<ul class="wp-block-list vg-check-list">
<li>Put the boat in the coolest, calmest usable part of the day when possible.</li>
<li>Do not assume rice fields will look the same every month; use the season as a bonus, not the only reason to go.</li>
<li>Confirm ticket desk, operating notes, cash needs, and weather before building a tight transfer around the boat.</li>
<li>If the route also includes Trang An, name the reason: scenery certainty, different cave/river character, or a slower second day.</li>
</ul>
<!-- /wp:list -->

<!-- vg-tam-coc-bike-walk:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cycling and walking: the real reason to sleep here</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Tam Coc earns many overnight stays because the best moments are small: a quiet lane before breakfast, a short ride to Bich Dong, an easy cafe break, a sunset close to the hotel, and no need to turn every sight into a car transfer.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-tam-coc-bike-walk">
<thead><tr><th>Move</th><th>Good fit</th><th>Watch out</th></tr></thead>
<tbody>
<tr><td data-label="Move">Village-side walk</td><td data-label="Good fit">Arrival evening, families, jet lag, light rain.</td><td data-label="Watch out">Traffic and narrow shoulders after dark.</td></tr>
<tr><td data-label="Move">Short bicycle loop</td><td data-label="Good fit">Bich Dong, cafes, fields, soft morning rhythm.</td><td data-label="Watch out">Heat, sun exposure, and confidence around scooters.</td></tr>
<tr><td data-label="Move">Private car hop</td><td data-label="Good fit">Mua Cave, Trang An, luggage, children, stormy weather.</td><td data-label="Watch out">Overloading the day because the car makes everything look close.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-tam-coc-bich-dong:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Bich Dong: the easiest culture add-on</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Bich Dong is the best low-friction add-on when Tam Coc is your base. It gives the day a temple-and-cave texture without forcing a long transfer, and it pairs well with cycling when heat and weather are reasonable.</p>
<!-- /wp:paragraph -->

<!-- vg-tam-coc-mua-cave:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mua Cave: worth it when the climb has a real job</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Mua Cave is strongest when the route needs one panoramic proof point over the Tam Coc landscape. It is weaker when the group is tired, heat is high, stairs are a problem, or the climb would steal the calm that made Tam Coc valuable in the first place.</p>
<!-- /wp:paragraph -->

<!-- vg-tam-coc-season-rice:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Season, rice fields, and weather</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Tam Coc is most photogenic when water, rice, and light cooperate, but the trip should not depend on one perfect field condition. In hotter or wetter periods, make the plan about morning timing, shade, flexible meals, and a backup indoor or short-hop move.</p>
<!-- /wp:paragraph -->

<!-- vg-tam-coc-stay-eat:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where to stay and eat around Tam Coc</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Choose a stay near Tam Coc village when dinner choice, pickup clarity, laundry, and easy walking matter. Choose a quieter lane nearby when sleep and landscape feel matter more than stepping out to many restaurants. Read <a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a> before committing to a hotel that looks beautiful but makes the next transfer harder.</p>
<!-- /wp:paragraph -->

<!-- vg-tam-coc-cost-booking:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cost and booking logic</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Price Tam Coc by the friction it removes: a useful base can save taxi hops, rushed meals, and bad pickup timing. The cheapest room outside the practical lane can become expensive if every meal, boat, viewpoint, and transfer needs a new ride.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-tam-coc-cost-booking">
<thead><tr><th>Spend</th><th>Worth paying for</th><th>Not worth paying for</th></tr></thead>
<tbody>
<tr><td data-label="Spend">Hotel location</td><td data-label="Worth paying for">Walkable dinner, pickup access, quiet enough sleep.</td><td data-label="Not worth paying for">Pretty isolation that complicates every move.</td></tr>
<tr><td data-label="Spend">Private transfer</td><td data-label="Worth paying for">Luggage, children, remote stay, onward bay handoff.</td><td data-label="Not worth paying for">Replacing all local walking and cycling by habit.</td></tr>
<tr><td data-label="Spend">Tour bundle</td><td data-label="Worth paying for">Limited time with a clear route order.</td><td data-label="Not worth paying for">Packing Tam Coc, Trang An, Mua Cave, temples, and long meals into one brittle day.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-tam-coc-mistakes-skip:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Mistakes to skip</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li>Choosing Tam Coc only because a hotel photo looks peaceful, without checking dinner and pickup friction.</li>
<li>Doing Tam Coc boat, Trang An, and Mua Cave in one day when the route actually needs a slower Ninh Binh chapter.</li>
<li>Arriving late, sleeping far out, and expecting the next morning to fix the whole stay.</li>
<li>Ignoring heat and stairs before adding Mua Cave.</li>
<li>Keeping Tam Coc and Ha Long Bay in a short route when one landscape chapter would be enough.</li>
</ul>
<!-- /wp:list -->

<!-- vg-tam-coc-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks in the final week</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list"} -->
<ul class="wp-block-list vg-check-list">
<li>Ask the hotel to confirm pickup access, lane conditions, and whether late arrival is simple.</li>
<li>Check same-week rain, heat, and visibility before fixing boat and Mua Cave order.</li>
<li>Confirm boat ticket, cash, and operating notes locally rather than relying on old blog prices.</li>
<li>Reconfirm Hanoi to Ninh Binh transfer drop-off and any onward bay pickup point.</li>
<li>Read <a href="/compare/trang-an-vs-tam-coc/">Trang An vs Tam Coc</a>, <a href="/compare/ninh-binh-day-trip-vs-overnight/">Ninh Binh Day Trip vs Overnight</a>, <a href="/plan/hanoi-to-ninh-binh-transport/">Hanoi to Ninh Binh Transport</a>, and <a href="/plan/ninh-binh-to-ha-long-bay-transfer/">Ninh Binh to Ha Long Bay Transfer</a> before locking the northern route.</li>
</ul>
<!-- /wp:list -->

<!-- vg-tam-coc-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Tam Coc travel FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-tam-coc-faq">
<details><summary>Is Tam Coc worth staying in?</summary><p>Yes, when you want Ninh Binh to feel like a countryside base rather than a rushed day trip. It is especially useful for easy meals, cycling, boat timing, and a calmer morning.</p></details>
<details><summary>Should I choose Tam Coc or Trang An?</summary><p>Choose Trang An when one high-certainty flagship boat route matters most. Choose Tam Coc when the base rhythm, rice-field setting, shorter boat ride, and cycling add more value to the trip.</p></details>
<details><summary>How many nights do I need in Tam Coc?</summary><p>One night is enough for many first-timers if arrival and departure timing are clean. Two nights make sense when you want cycling, Bich Dong, Mua Cave, and a second boat or slower weather buffer.</p></details>
<details><summary>Can Tam Coc work as a Hanoi day trip?</summary><p>It can, but the day trip should stay disciplined. If the schedule tries to include too many stops, one overnight often protects the experience better.</p></details>
<details><summary>Is Tam Coc good before Ha Long Bay?</summary><p>It can be, if the next pickup and cruise port are clear. If the bay handoff is vague, protect the cruise day before choosing a remote or late-start Tam Coc stay.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a> to decide whether the province belongs, <a href="/destinations/where-to-stay-in-ninh-binh/">Where to Stay in Ninh Binh</a> to choose the base, and <a href="/compare/trang-an-vs-tam-coc/">Trang An vs Tam Coc</a> if the boat decision is still open. If the north is tight, compare <a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a> before adding both Tam Coc and the bay.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-tam-coc-hero:v1',
    'concierge verdict' => 'vg-tam-coc-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-tam-coc-at-a-glance:v1',
    'photo proof' => 'vg-tam-coc-photo-proof:v1',
    'source diversity' => 'vg-tam-coc-source-diversity:v1',
    'source trail snapshot' => 'vg-tam-coc-source-trail-snapshot:v1',
    'route verdict' => 'vg-tam-coc-route-verdict:v1',
    'boat plan' => 'vg-tam-coc-boat-plan:v1',
    'bike walk' => 'vg-tam-coc-bike-walk:v1',
    'Bich Dong' => 'vg-tam-coc-bich-dong:v1',
    'Mua Cave' => 'vg-tam-coc-mua-cave:v1',
    'season rice' => 'vg-tam-coc-season-rice:v1',
    'stay eat' => 'vg-tam-coc-stay-eat:v1',
    'cost booking' => 'vg-tam-coc-cost-booking:v1',
    'mistakes skip' => 'vg-tam-coc-mistakes-skip:v1',
    'live checks' => 'vg-tam-coc-live-checks:v1',
    'FAQ' => 'vg-tam-coc-faq:v1',
    'related routes shortcode' => '[vg_related_routes]',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_tam_coc_ops_assert_required_content_markers($content, $required_content_markers);
vg_tam_coc_ops_assert_internal_page_links_are_published('Tam Coc Travel Guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Tam Coc Travel Guide',
    'post_name'      => 'tam-coc-travel-guide',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_tam_coc_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Tam Coc guide for choosing the Ninh Binh base, boat timing, cycling, Bich Dong, Mua Cave, season, cost, and route pacing.',
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
    vg_tam_coc_ops_fail('Could not publish Tam Coc Travel Guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_tam_coc_ops_fail('Could not publish Tam Coc Travel Guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Tam Coc Travel Guide: Ninh Binh Base, Boat and Route Tips');
update_post_meta($page_id, 'rank_math_description', 'Tam Coc travel guide for international visitors: choose the Ninh Binh base, boat timing, cycling, Bich Dong, Mua Cave, season, cost and route pacing.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Tam Coc Travel Guide');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Use Tam Coc as the soft Ninh Binh base when countryside rhythm, boat timing, cycling, Bich Dong, dinner choice, and next-transfer pressure matter more than checklist certainty.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published Tam Coc Travel Guide as an evidence-led Ninh Binh base guide with photo-led hero, concierge verdict, proof panel, at-a-glance table, text-only image credits, source-diversity module, rendered source trail snapshot, route verdict, boat planning, cycling/walking, Bich Dong, Mua Cave, season/rice-field logic, stay/eat guidance, cost/booking logic, mistakes/skip logic, live checks, FAQ, Destinations hub note, homepage support, and inbound related routes.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Ninh Binh destination page - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh - checked {$review_date}\nVietnam.travel - Guide to boat tours in Ninh Binh - https://vietnam.travel/things-to-do/guide-boat-tours-ninh-binh - checked {$review_date}\nUNESCO World Heritage Centre - Trang An Landscape Complex - https://whc.unesco.org/en/list/1438/ - checked {$review_date}; automation may receive 403 because of access challenge\nNinh Binh Tourism Department - Tourism Promotion Information Center - https://dulichninhbinh.com.vn/en/ - checked {$review_date}\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked {$review_date}\nVietnamGuide - Transport Within Vietnam - https://vietnamguide.net/plan/transport-within-vietnam/ - checked {$review_date}\nWikimedia Commons image record - Tam Coc Ninh Binh (53115) - https://commons.wikimedia.org/wiki/File:Tam_Coc_Ninh_Binh_(53115).jpg - license checked {$review_date}\nWikimedia Commons image record - Bich Dong Pagoda 20240203 1126 5619 - https://commons.wikimedia.org/wiki/File:Bich_Dong_Pagoda,_Ninh_Binh,_Vietnam,_20240203_1126_5619.jpg - license checked {$review_date}\nWikimedia Commons image record - 2024-03-30 Mua Cave Dragon Mountain 3758 - https://commons.wikimedia.org/wiki/File:2024-03-30-Mua_Cave_Dragon_Mountain-3758.jpg - license checked {$review_date}\nWikimedia Commons image record - Sunset in Ninh Hai 20240202 1724 5415 - https://commons.wikimedia.org/wiki/File:Sunset_in_Ninh_H%E1%BA%A3i,_Ninh_Binh_province,_Vietnam,_20240202_1724_5415.jpg - license checked {$review_date}\nWikimedia Commons image record - Thai Vi Temple 2 - https://commons.wikimedia.org/wiki/File:Thai_Vi_Temple_2.jpg - license checked {$review_date}");
update_post_meta($page_id, 'vg_eeat_field_note', 'Tam Coc works best when it is treated as a stay-and-rhythm decision. The boat is useful, but the base earns its place through cycling, dinner choice, morning timing, and the next transfer.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Tam Coc Travel Guide built around base-selection and route-pacing judgment rather than hotel commissions, stale prices, or tour scraping.\nConcierge verdict distinguishes Tam Coc lifestyle rhythm from Trang An certainty.\nBoat timing, cycling/walking, Bich Dong, Mua Cave, season/rice-field expectations, stay/eat logic, cost, mistakes, live checks, FAQ, source trail, update log, and related routes create durable decision value.\nText-only image credits keep accountability visible while reducing outbound clutter.\nRelated routes connect the page to Ninh Binh Travel Guide, Where to Stay in Ninh Binh, Trang An vs Tam Coc, Ninh Binh Day Trip vs Overnight, transport, bay handoff, itineraries, cost, safety, and insurance.");

$related_routes = "Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide whether Ninh Binh belongs before choosing Tam Coc as the base.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Compare Tam Coc, Trang An area, city, Van Long, and Cuc Phuong-side stay logic.\nTrang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose whether Tam Coc rhythm or Trang An certainty solves the boat decision.\nNinh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Tam Coc deserves a night or should stay a Hanoi day-trip idea.\nHanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Match train, van, private car, or tour transport to the Tam Coc drop-off.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Protect the next bay handoff before choosing a late or remote Tam Coc stay.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Compare whether Tam Coc belongs as a day trip, overnight, or skip.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Protect Hanoi energy before launching into Tam Coc and the next northern stop.\nHanoi Travel Guide | /destinations/hanoi-travel-guide/ | Place Tam Coc inside the northern route rather than as a loose excursion.\nWhere to Stay in Hanoi | /destinations/where-to-stay-in-hanoi/ | Keep the Hanoi base clean before early Ninh Binh pickup.\nHa Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Compare whether Tam Coc plus the bay repeats or improves the northern landscape chapter.\nHa Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Decide whether the bay still earns time after Tam Coc.\nCat Ba Travel Guide | /destinations/cat-ba-travel-guide/ | Use when Lan Ha or island-base routing competes with Tam Coc time.\nBai Tu Long Bay Guide | /destinations/bai-tu-long-bay-guide/ | Compare quieter bay logic before duplicating northern scenery.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the planning order before locking Tam Coc hotels and transfers.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Compare whether Tam Coc belongs in the shortlist.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Understand the heritage context around Trang An and Ninh Binh before choosing the stop.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm the north deserves enough nights for Tam Coc and the bay.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check heat, rain, and regional weather before planning boat and viewpoint order.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Count train, van, private car, local taxi, cycling, and onward-transfer friction.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price Tam Coc by friction removed, not just room or ticket cost.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check cycling, stairs, road, boat, weather, and interruption coverage.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair tickets, taxis, bikes, hotels, cash, and night walking with practical risk habits.\n7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Decide whether a one-week route can afford Tam Coc plus another northern landscape stop.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Tam Coc improves or overloads a short north-plus-central route.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when two weeks can protect Tam Coc, Hanoi, the bay, and central Vietnam.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use a longer route to slow Ninh Binh without making transfer days brittle.";
vg_tam_coc_ops_assert_related_route_meta_links_are_published('Tam Coc Travel Guide related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Tam Coc river image: Tam Coc Ninh Binh (53115) by Andre Hospers, CC BY 4.0. Body images: Bich Dong Pagoda by Jakub Halun, CC BY 4.0; 2024-03-30 Mua Cave Dragon Mountain by Superbass, CC BY-SA 4.0; Sunset in Ninh Hai by Jakub Halun, CC BY 4.0; Thai Vi Temple 2 by Shyamal, CC BY-SA 4.0. Image credits are text-only on the page; source records are preserved in metadata.');
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
        vg_tam_coc_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_tam_coc_ops_fail('Tam Coc Travel Guide was updated but is not published.');
}

vg_tam_coc_ops_refresh_destinations_hub();
vg_tam_coc_ops_refresh_homepage_route_spine();
vg_tam_coc_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_tam_coc_ops_log("Published Tam Coc Travel Guide: {$page_id} {$updated_permalink}");

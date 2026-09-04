<?php
/**
 * Publish the Phu Quoc Travel Guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * VG_FORCE_PHU_QUOC_GUIDE_REPUBLISH=1 wp eval-file ops/apply-phu-quoc-travel-guide.php --allow-root
 *
 * Repair only hub/related-route side effects with:
 * VG_REPAIR_PHU_QUOC_GUIDE_LINKS=1 wp eval-file ops/apply-phu-quoc-travel-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_phu_quoc_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_phu_quoc_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_phu_quoc_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_PHU_QUOC_GUIDE_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_phu_quoc_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_PHU_QUOC_GUIDE_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_phu_quoc_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_phu_quoc_ops_fail('Could not resolve a valid WordPress author for the Phu Quoc Travel Guide.');
}

function vg_phu_quoc_ops_internal_path_from_href(string $href): ?string
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

function vg_phu_quoc_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_phu_quoc_ops_internal_path_from_href($href);

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
        vg_phu_quoc_ops_fail(implode(PHP_EOL, $failures));
    }

    vg_phu_quoc_ops_log("Validated {$label} internal page links are published.");
}

function vg_phu_quoc_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_phu_quoc_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_phu_quoc_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_phu_quoc_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_phu_quoc_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_phu_quoc_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_phu_quoc_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_phu_quoc_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_phu_quoc_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_phu_quoc_ops_log("Validated {$label} related-route links are published.");
}

function vg_phu_quoc_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_phu_quoc_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_phu_quoc_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

function vg_phu_quoc_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
{
    $marked_block = $marker . "\n" . $block;

    if (str_contains($page->post_content, $marked_block)) {
        vg_phu_quoc_ops_log("Skipped {$label}: current block already present.");
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
            vg_phu_quoc_ops_fail("Could not confidently refresh {$label}.");
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
        vg_phu_quoc_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_phu_quoc_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_phu_quoc_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_phu_quoc_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_phu_quoc_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_phu_quoc_ops_published_page_exists('destinations/phu-quoc-travel-guide')) {
        vg_phu_quoc_ops_log('Skipped Destinations hub refresh: Phu Quoc Travel Guide is not published.');
        return;
    }

    $marker = '<!-- vg-phu-quoc-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use Phu Quoc when the route needs a real island recovery chapter</h3><p>The <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a> helps travelers decide whether Vietnam's easiest island resort finish earns the nights by season, flight/ferry logic, stay area, beach value, booking risk, visa edge cases, and what mainland stop it replaces.</p></div>
<!-- /wp:group -->
HTML;

    vg_phu_quoc_ops_assert_internal_page_links_are_published('Destinations hub Phu Quoc note', $block);
    vg_phu_quoc_ops_upsert_marked_group($hub, 'Destinations hub Phu Quoc note', $marker, $block);
}

function vg_phu_quoc_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_phu_quoc_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_phu_quoc_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_phu_quoc_ops_log("Skipped related-route refresh for {$label}: Phu Quoc Travel Guide line is already current.");
        return;
    }

    $next = implode("\n", $next_lines);
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_phu_quoc_ops_log("Upserted Phu Quoc Travel Guide related route to {$label}: {$page->ID}");
}

function vg_phu_quoc_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Phu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Decide whether Phu Quoc should be a winter-sun, resort, family, or route-recovery island chapter before adding flights, ferries, resort nights, and beach time.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
            'destinations/best-islands-in-vietnam' => 'Best Islands in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ] as $path => $label
    ) {
        vg_phu_quoc_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_phu_quoc_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/phu-quoc-travel-guide', OBJECT, 'page');

if (vg_phu_quoc_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_phu_quoc_ops_fail('Repair mode requires the Phu Quoc Travel Guide to already be published.');
    }

    vg_phu_quoc_ops_refresh_destinations_hub();
    vg_phu_quoc_ops_refresh_inbound_related_routes();
    vg_phu_quoc_ops_log("Repaired Phu Quoc Travel Guide side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_phu_quoc_ops_force_republish_enabled()) {
    vg_phu_quoc_ops_fail('Phu Quoc Travel Guide is not a draft. Set VG_FORCE_PHU_QUOC_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_phu_quoc_ops_log("Preflight Phu Quoc Travel Guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_phu_quoc_ops_log('Preflight Phu Quoc Travel Guide: no existing page found; creating a child page under /destinations/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$long_beach_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/4/4f/Sunset_on_the_Long_Beach_in_Phu_Quoc_Island%2C_Vietnam%2C_5_March_2019.jpg/1280px-Sunset_on_the_Long_Beach_in_Phu_Quoc_Island%2C_Vietnam%2C_5_March_2019.jpg');
$long_beach_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Sunset_on_the_Long_Beach_in_Phu_Quoc_Island,_Vietnam,_5_March_2019.jpg');
$hon_thom_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/5/55/Hon_Thom_Cable_Car_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$hon_thom_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hon_Thom_Cable_Car_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$an_thoi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/8/8d/An_Thoi_fishing_harbour_Sunset_Town_Sun_World_Phu_Quoc_Vietnam.jpg');
$an_thoi_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:An_Thoi_fishing_harbour_Sunset_Town_Sun_World_Phu_Quoc_Vietnam.jpg');
$sao_beach_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/6/6e/Bai-sao-phu-quoc-tuonglamphotos.jpg');
$sao_beach_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Bai-sao-phu-quoc-tuonglamphotos.jpg');
$star_beach_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/a/ad/Star_Beach_%28B%C3%A3i_Sao%29.jpg');
$star_beach_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Star_Beach_(B%C3%A3i_Sao).jpg');
$united_center_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/f/f4/Festival_in_Phu_Quoc_United_Center.jpg');
$united_center_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Festival_in_Phu_Quoc_United_Center.jpg');

$content = <<<HTML
<!-- vg-phu-quoc-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Aerial view of Kem Beach on Phu Quoc Island, Vietnam" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 19, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Phu Quoc Travel Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Phu Quoc is Vietnam's easiest island resort answer for many international travelers, but it is not an automatic add-on. It earns the nights when beach rest, winter sun, direct flights, family ease, or a southern route recovery chapter improve the trip more than another mainland stop would.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: this guide treats Phu Quoc as a route decision. The question is not whether the island is famous; it is whether the season, arrival path, stay area, beach quality, and exit plan make the trip calmer.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">Vivu Vietnam / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-phu-quoc-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-phu-quoc-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-phu-quoc-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Choose Phu Quoc when the trip needs an easy island finish, not when the route already has too many moving parts.</strong> It is strongest after Ho Chi Minh City, the Mekong, or a north-to-south route that needs rest. It is weaker on tight first trips where an extra flight or ferry steals time from Hanoi, Ninh Binh, Ha Long/Lan Ha, Hoi An, Hue, or Da Nang.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-phu-quoc-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-phu-quoc-shortlist">
<li><strong>Best fit:</strong> 3 to 5 nights as a beach, resort, family, or decompression chapter.</li>
<li><strong>Best route pairing:</strong> Ho Chi Minh City + Mekong + Phu Quoc, or a longer full-country route ending in the south.</li>
<li><strong>Best first base:</strong> Long Beach or Duong Dong when walkable dinners, airport access, and sunset convenience matter.</li>
<li><strong>Best premium resort fit:</strong> Kem Beach, Sao Beach, Ong Lang, or the quieter northern coast depending on isolation tolerance.</li>
<li><strong>Skip first when:</strong> the only reason is "we should see an island" and the route has no protected buffer.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-phu-quoc-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-phu-quoc-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-phu-quoc-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fastest useful Phu Quoc answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-phu-quoc-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Is Phu Quoc worth visiting?</strong></td><td data-label="Answer">Yes when the route needs easy beach rest, resort infrastructure, warmer southern timing, or a soft finish after busier mainland days.</td></tr>
<tr><td data-label="Question"><strong>How many nights?</strong></td><td data-label="Answer">Three nights is the practical minimum for most international trips; four or five nights gives the island time to feel restful instead of like another transfer.</td></tr>
<tr><td data-label="Question"><strong>Best season?</strong></td><td data-label="Answer">Generally the drier southern window is the safest resort bet; rainy months can still work with flexible expectations, better cancellation terms, and a stronger hotel plan.</td></tr>
<tr><td data-label="Question"><strong>Best area for first-timers?</strong></td><td data-label="Answer">Long Beach/Duong Dong for convenience, Ong Lang for quieter evenings, Kem/Sao/An Thoi for polished resort beach stays, and the north for remote resort/nature pacing.</td></tr>
<tr><td data-label="Question"><strong>Main mistake?</strong></td><td data-label="Answer">Booking the cheapest pretty resort before checking beach stretch, transfer time, dinner access, weather month, cancellation terms, and exit flight/ferry logic.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-phu-quoc-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: Phu Quoc is several different trips</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the images below as a planning check. Phu Quoc can be an easy sunset base, a polished resort beach, a southern-islands outing, a local harbor day, a waterfall/nature detour, or a remote north-coast stay. Those are not the same decision.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-phu-quoc-photo-grid" aria-label="Phu Quoc travel guide photography">
<figure><img src="{$hero_image}" alt="Kem Beach aerial view on Phu Quoc Island" loading="lazy" decoding="async"><figcaption>Kem Beach shows why Phu Quoc can work as a polished island resort finish. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Vivu Vietnam / CC BY-SA 4.0</a>.</figcaption></figure>
<figure><img src="{$long_beach_image}" alt="Sunset on Long Beach in Phu Quoc" loading="lazy" decoding="async"><figcaption>Long Beach is the convenience answer when sunset, restaurants, and fewer transfers matter. Image: <a href="{$long_beach_credit_url}" target="_blank" rel="license noopener">Alexey Komarov / CC BY-SA 4.0</a>.</figcaption></figure>
<figure><img src="{$hon_thom_image}" alt="Hon Thom cable car and islands off southern Phu Quoc" loading="lazy" decoding="async"><figcaption>An Thoi and Hon Thom add spectacle, but they should not crowd out rest days. Image: <a href="{$hon_thom_credit_url}" target="_blank" rel="license noopener">Vivu Vietnam / CC BY-SA 4.0</a>.</figcaption></figure>
<figure><img src="{$an_thoi_image}" alt="An Thoi fishing harbour on Phu Quoc" loading="lazy" decoding="async"><figcaption>An Thoi works better when it supports a southern-island plan, not just a transfer-heavy photo stop. Image: <a href="{$an_thoi_credit_url}" target="_blank" rel="license noopener">Vivu Vietnam / CC BY-SA 4.0</a>.</figcaption></figure>
<figure><img src="{$sao_beach_image}" alt="Bai Sao beach on Phu Quoc Island" loading="lazy" decoding="async"><figcaption>Bai Sao is a beach-quality bet that still needs live checks for weather, crowding, and access. Image: <a href="{$sao_beach_credit_url}" target="_blank" rel="license noopener">Trantuonglam / CC BY-SA 4.0</a>.</figcaption></figure>
<figure><img src="{$star_beach_image}" alt="Star Beach or Bai Sao on Phu Quoc Island" loading="lazy" decoding="async"><figcaption>Star Beach reinforces the same lesson: choose the exact beach stretch, not just the island name. Image: <a href="{$star_beach_credit_url}" target="_blank" rel="license noopener">Vnecofriendly / CC BY-SA 4.0</a>.</figcaption></figure>
<figure><img src="{$united_center_image}" alt="Festival scene at Phu Quoc United Center" loading="lazy" decoding="async"><figcaption>North-island entertainment can help families, but ticketed attractions should be live-checked and not treated as default proof. Image: <a href="{$united_center_credit_url}" target="_blank" rel="license noopener">Reb.vn / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-phu-quoc-source-diversity:v1 -->
<!-- wp:group {"className":"vg-source-diversity vg-phu-quoc-source-diversity","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-source-diversity vg-phu-quoc-source-diversity">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Source diversity</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What each source is allowed to prove</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-source-role-table">
<thead><tr><th>Source type</th><th>Use it for</th><th>Do not overuse it for</th></tr></thead>
<tbody>
<tr><td data-label="Source type">Official tourism</td><td data-label="Use it for">Destination orientation, major place names, broad planning context, and official visitor framing.</td><td data-label="Do not overuse it for">Exact hotel quality, day-by-day weather, or whether a traveler should buy a specific resort.</td></tr>
<tr><td data-label="Source type">UNESCO / biosphere context</td><td data-label="Use it for">Why Kien Giang and Phu Quoc's nature setting matters beyond resort copy.</td><td data-label="Do not overuse it for">Guaranteeing that every beach or activity is pristine or conservation-led.</td></tr>
<tr><td data-label="Source type">National biodiversity agency</td><td data-label="Use it for">Phu Quoc National Park, biosphere core-zone context, and the reason north-island nature should be treated carefully.</td><td data-label="Do not overuse it for">Promising visitor access, trail condition, wildlife sightings, or conservation quality on a specific travel date.</td></tr>
<tr><td data-label="Source type">Visa and border sources</td><td data-label="Use it for">Live entry checks and narrow Phu Quoc exemption questions.</td><td data-label="Do not overuse it for">Universal advice across every passport without a recheck.</td></tr>
<tr><td data-label="Source type">Weather and marine sources</td><td data-label="Use it for">Live weather, storm, sea-condition, and ferry-day caution before boat-heavy plans.</td><td data-label="Do not overuse it for">Guaranteeing beach conditions from a generic monthly season label.</td></tr>
<tr><td data-label="Source type">Airport and ferry operators</td><td data-label="Use it for">Current route, timetable, port, weather, baggage, and booking checks close to travel.</td><td data-label="Do not overuse it for">Evergreen flight, ferry, or transfer promises months in advance.</td></tr>
<tr><td data-label="Source type">Attraction operators</td><td data-label="Use it for">Live ticket, maintenance, opening-day, and family-logistics checks for cable car/theme-park style days.</td><td data-label="Do not overuse it for">Editorial proof that an attraction deserves a day in every Phu Quoc route.</td></tr>
<tr><td data-label="Source type">Licensed photography</td><td data-label="Use it for">Showing real island environments and making stay-area differences visible.</td><td data-label="Do not overuse it for">Proof that current beach condition, construction, or crowding matches the image.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-phu-quoc-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where Phu Quoc fits in a Vietnam route</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-phu-quoc-route-fit">
<thead><tr><th>Route type</th><th>Phu Quoc fit</th><th>VietnamGuide judgment</th></tr></thead>
<tbody>
<tr><td data-label="Route type">7 days in Vietnam</td><td data-label="Phu Quoc fit">Usually too expensive in route time unless the whole trip is south-first and beach-led.</td><td data-label="VietnamGuide judgment">Use <a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a> to protect one region instead of adding a rushed island.</td></tr>
<tr><td data-label="Route type">10 days in Vietnam</td><td data-label="Phu Quoc fit">Good only as a south + island route: Ho Chi Minh City, Mekong, Phu Quoc, and one calm buffer.</td><td data-label="VietnamGuide judgment">Do not bolt Phu Quoc onto the default north + central route unless another chapter is removed.</td></tr>
<tr><td data-label="Route type">14 days in Vietnam</td><td data-label="Phu Quoc fit">Strong when the traveler wants a softer finish after Hanoi, Ninh Binh, bay, and central Vietnam.</td><td data-label="VietnamGuide judgment">Best for travelers who are comfortable flying south and protecting at least three island nights.</td></tr>
<tr><td data-label="Route type">21 days in Vietnam</td><td data-label="Phu Quoc fit">A credible final recovery chapter or family beach pause if the full-country route has enough margin.</td><td data-label="VietnamGuide judgment">Use the island to slow the trip down, not to add another box to a long route.</td></tr>
<tr><td data-label="Route type">South-only trip</td><td data-label="Phu Quoc fit">Often excellent with Ho Chi Minh City, Mekong, and a protected island finish.</td><td data-label="VietnamGuide judgment">This is the cleanest Phu Quoc use case for winter sun and lower transfer strain.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-phu-quoc-where-to-stay:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where to stay in Phu Quoc by trip job</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Choose the stay area before choosing a hotel. A beautiful resort in the wrong part of the island can make meals, tours, airport timing, and bad-weather days feel oddly difficult.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-phu-quoc-stay-table">
<thead><tr><th>Area</th><th>Best for</th><th>Watch before booking</th></tr></thead>
<tbody>
<tr><td data-label="Area">Long Beach / Duong Dong</td><td data-label="Best for">First-timers, sunset, shorter stays, airport access, dinners, errands, and travelers who do not want every meal inside a resort.</td><td data-label="Watch before booking">Beach stretch quality varies by exact property, and the most convenient parts can feel busier.</td></tr>
<tr><td data-label="Area">Ong Lang / Cua Can</td><td data-label="Best for">Quieter couples, slower evenings, boutique stays, and travelers who want calm without being fully remote.</td><td data-label="Watch before booking">Check restaurant distance, beach access, and whether taxis/private drivers are needed every day.</td></tr>
<tr><td data-label="Area">Kem Beach / Sao Beach / An Thoi</td><td data-label="Best for">Polished resort stays, clearer beach imagery, southern-island outings, and travelers who want a more contained beach chapter.</td><td data-label="Watch before booking">Not ideal if you want walkable local dinners every night; some stays are resort-centered by design.</td></tr>
<tr><td data-label="Area">Ganh Dau / north coast</td><td data-label="Best for">Families, quieter resort compounds, nature context, and travelers who want to deliberately disconnect.</td><td data-label="Watch before booking">Longer drives make spontaneous island-hopping and town meals less efficient.</td></tr>
<tr><td data-label="Area">Airport-side / Duong To</td><td data-label="Best for">Short stays, late arrivals, early departures, or one-night buffers between mainland and island legs.</td><td data-label="Watch before booking">Useful is not the same as atmospheric; check noise, beach access, and meal options.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-phu-quoc-beach-areas:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Beach and experience priority map</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-phu-quoc-priority-map">
<thead><tr><th>Priority</th><th>What it changes</th><th>Best traveler fit</th></tr></thead>
<tbody>
<tr><td data-label="Priority">Long Beach sunset base</td><td data-label="What it changes">Keeps the island easy: airport, food, sunset, and low-effort recovery.</td><td data-label="Best traveler fit">First-time visitors, 3-night stays, mixed budgets, and travelers who value convenience.</td></tr>
<tr><td data-label="Priority">Kem/Sao resort beach chapter</td><td data-label="What it changes">Turns Phu Quoc into a more polished beach-resort ending.</td><td data-label="Best traveler fit">Couples, families, premium travelers, and people who want fewer decisions once checked in.</td></tr>
<tr><td data-label="Priority">An Thoi / Hon Thom</td><td data-label="What it changes">Adds spectacle and southern-island context, but costs time and can become weather-dependent.</td><td data-label="Best traveler fit">Travelers staying in the south or families who want one structured outing.</td></tr>
<tr><td data-label="Priority">Duong Dong market / local errands</td><td data-label="What it changes">Gives the resort chapter some practical texture and makes dinner choices easier.</td><td data-label="Best traveler fit">Travelers who dislike eating every meal in a resort.</td></tr>
<tr><td data-label="Priority">North island and nature context</td><td data-label="What it changes">Adds biosphere/nature framing and quieter road time, but makes the route more spread out.</td><td data-label="Best traveler fit">Longer stays, repeat visitors, and travelers with a driver or relaxed day plan.</td></tr>
<tr><td data-label="Priority">Inland waterfall or non-beach day</td><td data-label="What it changes">Protects the trip when weather or beach fatigue appears.</td><td data-label="Best traveler fit">Families, rainy-season travelers, and people staying four nights or longer.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-phu-quoc-priority-map:v1 -->
<!-- wp:group {"className":"vg-priority-map-note","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-priority-map-note"><p><strong>Priority rule:</strong> choose one or two island jobs, not every island attraction. A better Phu Quoc trip often looks simpler on paper: one good stay area, one structured outing, one low-effort town or sunset rhythm, and enough weather flexibility to enjoy the beach days that work.</p></div>
<!-- /wp:group -->

<!-- vg-phu-quoc-season-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best time to visit Phu Quoc</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Phu Quoc belongs to the southern Vietnam weather logic, so do not apply Hanoi, central-coast, or Ha Long Bay timing to the island. Treat the month as a risk profile: beach reliability, resort pricing, flight demand, ferry disruption, and how much of the trip can flex.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-phu-quoc-weather-table">
<thead><tr><th>Season lens</th><th>What to expect</th><th>Booking posture</th></tr></thead>
<tbody>
<tr><td data-label="Season lens">Drier southern window</td><td data-label="What to expect">The safest fit for beach-first trips, winter sun, families, and travelers who want higher resort reliability.</td><td data-label="Booking posture">Book stronger stays earlier, but still check cancellation terms, flight timing, and exact beach stretch.</td></tr>
<tr><td data-label="Season lens">Shoulder months</td><td data-label="What to expect">Can offer good value and softer crowds, but weather certainty drops and beach days should not be over-programmed.</td><td data-label="Booking posture">Keep a flexible day and avoid prepaying too many weather-sensitive outings.</td></tr>
<tr><td data-label="Season lens">Rainier months</td><td data-label="What to expect">Still possible for travelers who value resort downtime over perfect beach imagery, but water activities and ferries need live checks.</td><td data-label="Booking posture">Prioritize cancellation terms, covered resort facilities, insurance, and a realistic plan for non-beach hours.</td></tr>
<tr><td data-label="Season lens">Holiday peaks</td><td data-label="What to expect">Availability, price, domestic demand, and beach crowding can change the value calculation.</td><td data-label="Booking posture">Decide whether the premium is worth it before locking flights and non-refundable resorts.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-phu-quoc-transport-logistics:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Flights, ferries, and entry checks</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The easiest Phu Quoc plan usually flies in or out. Ferries can be useful from Ha Tien or Rach Gia, especially on south-only or Mekong-adjacent routes, but they add port timing, weather exposure, and transfer buffers. Entry rules are also easy to misread: do not build a route around a Phu Quoc visa-free edge case unless your passport, transit path, and airline check-in rules all support it close to departure.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-phu-quoc-logistics-table">
<thead><tr><th>Logistics choice</th><th>Best use</th><th>Live check</th></tr></thead>
<tbody>
<tr><td data-label="Logistics choice">Domestic flight</td><td data-label="Best use">Most international itineraries, especially Hanoi/Central Vietnam/Ho Chi Minh City connections and premium time-sensitive trips.</td><td data-label="Live check">Flight times, baggage, airport transfer duration, and whether the departure day still has enough margin.</td></tr>
<tr><td data-label="Logistics choice">Ferry from Ha Tien or Rach Gia</td><td data-label="Best use">South-only trips, Mekong-adjacent routes, flexible travelers, and visitors who want to reduce domestic flying.</td><td data-label="Live check">Operator schedule, port, weather policy, seat class, luggage, pier transfer, and whether the next flight is too close.</td></tr>
<tr><td data-label="Logistics choice">Island taxis / private drivers</td><td data-label="Best use">Families, resort stays, north/south area changes, wet-weather days, and travelers carrying luggage.</td><td data-label="Live check">Driver cost, pickup location, remote resort fees, and whether a day plan is spread across the island.</td></tr>
<tr><td data-label="Logistics choice">Scooters</td><td data-label="Best use">Only for experienced, licensed, insured riders who understand local road and weather conditions.</td><td data-label="Live check">License validity, insurance wording, helmet quality, road condition, and night/rain risk.</td></tr>
<tr><td data-label="Logistics choice">Visa/exemption assumption</td><td data-label="Best use">Only after checking official sources for your passport and exact routing.</td><td data-label="Live check">If you enter mainland Vietnam, transit domestically, or leave Phu Quoc for another Vietnam stop, treat the broader Vietnam entry rules as the safer planning path.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-phu-quoc-cost-booking:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cost and booking checks that matter</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Phu Quoc can be good value, but it can also hide costs inside distance, resort isolation, private transfers, peak pricing, weather flexibility, and activity upsells. The right booking sequence protects the trip from becoming a pretty but expensive dead end.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-phu-quoc-cost-table">
<thead><tr><th>Before paying</th><th>What to verify</th><th>Why it protects the trip</th></tr></thead>
<tbody>
<tr><td data-label="Before paying">Exact beach stretch</td><td data-label="What to verify">Recent photos, seasonal beach condition, public/private access, shade, swimming suitability, and construction nearby.</td><td data-label="Why it protects the trip">A good room rate can still be a poor beach decision.</td></tr>
<tr><td data-label="Before paying">Meal access</td><td data-label="What to verify">Walkable restaurants, resort dining prices, taxi cost to town, breakfast inclusion, and family food needs.</td><td data-label="Why it protects the trip">Remote luxury can be relaxing or expensive depending on expectations.</td></tr>
<tr><td data-label="Before paying">Cancellation terms</td><td data-label="What to verify">Weather-sensitive nights, ferry/outings, non-refundable resort packages, flight-change penalties, and refund windows.</td><td data-label="Why it protects the trip">Island chapters need more weather humility than city stays.</td></tr>
<tr><td data-label="Before paying">Transfer chain</td><td data-label="What to verify">Airport or pier pickup, hotel area, check-in time, departure-day margin, and whether one extra night is cheaper than a missed connection.</td><td data-label="Why it protects the trip">A cheap route can become expensive when the island exit is too tight.</td></tr>
<tr><td data-label="Before paying">Insurance coverage</td><td data-label="What to verify">Medical care, water activities, scooter/motorbike exclusions, interruption, evacuation, and missed-connection clauses.</td><td data-label="Why it protects the trip">Beach chapters often include exactly the activities policies exclude.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-phu-quoc-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">When to skip Phu Quoc</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-phu-quoc-skip-logic"} -->
<ul class="wp-block-list vg-check-list vg-phu-quoc-skip-logic">
<li><strong>Skip on a tight first trip</strong> when Hanoi, Ninh Binh, bay time, Hoi An/Hue, and departure logistics already fill the calendar.</li>
<li><strong>Skip if the season is wrong for your expectations</strong> and the only acceptable outcome is perfect beach weather.</li>
<li><strong>Skip if the resort is the whole plan but the area is untested</strong> for meals, beach quality, transfer time, and cancellation terms.</li>
<li><strong>Skip if Con Dao better matches the emotional brief</strong>: quiet premium nature, lower density, and slower evenings can matter more than Phu Quoc convenience.</li>
<li><strong>Skip if Da Nang/Hoi An already solves the beach need</strong> with lower route friction on a central Vietnam trip.</li>
<li><strong>Skip if the island is only a visa workaround</strong> and the real route includes mainland Vietnam anyway.</li>
</ul>
<!-- /wp:list -->

<!-- vg-phu-quoc-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before booking Phu Quoc</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-phu-quoc-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-phu-quoc-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc" target="_blank" rel="noopener">Vietnam.travel Phu Quoc</a> for official destination orientation and <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> for the national weather frame.</li>
<li>Use <a href="https://www.unesco.org/en/mab/kien-giang" target="_blank" rel="noopener">UNESCO Kien Giang biosphere reserve</a>, the <a href="https://en.nbca.gov.vn/vuon-quoc-gia-phu-quoc-kien-giang/" target="_blank" rel="noopener">Vietnam NBCA Phu Quoc National Park page</a>, and the <a href="https://phuquoc.angiang.gov.vn/" target="_blank" rel="noopener">Phu Quoc local government portal</a> for nature and local context, not hotel selection. Source names may still reference Kien Giang while current local portals use An Giang/Phu Quoc administrative language.</li>
<li>Use the <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">National Centre for Hydro-Meteorological Forecasting</a>, ferry operators, and your hotel before boat days; monthly weather summaries do not replace live sea and storm checks.</li>
<li>Use <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport</a>, <a href="https://vietnam.travel/plan-your-trip/getting-vietnam" target="_blank" rel="noopener">Vietnam.travel getting to Vietnam</a>, the <a href="https://sunairport.com/phuquoc/vi" target="_blank" rel="noopener">Phu Quoc International Airport operator page</a>, <a href="https://phuquocexpress.com/lich-tau-tuyen-ha-tien-phu-quoc-2024" target="_blank" rel="noopener">Phu Quoc Express Ha Tien route</a>, <a href="https://phuquocexpress.com/lich-tau-tuyen-rach-gia-phu-quoc" target="_blank" rel="noopener">Phu Quoc Express Rach Gia route</a>, <a href="https://superdong.com.vn/dich-vu/ha-tien-phu-quoc" target="_blank" rel="noopener">Superdong Ha Tien route</a>, <a href="https://superdong.com.vn/dich-vu/rach-gia-phu-quoc" target="_blank" rel="noopener">Superdong Rach Gia route</a>, and <a href="https://thanhthoi.vn/" target="_blank" rel="noopener">Thanh Thoi Ferry</a> as live logistics checks.</li>
<li>Use the <a href="https://evisa.gov.vn/" target="_blank" rel="noopener">official Vietnam e-visa portal</a>, <a href="https://vietnam.travel/plan-your-trip/visa-requirements" target="_blank" rel="noopener">Vietnam.travel visa requirements</a>, your airline, and your passport authority before trusting any Phu Quoc visa-exemption summary.</li>
<li>Use <a href="https://ticket.sunworld.vn/khu-vui-choi/hon-thom-nature-park/" target="_blank" rel="noopener">Sun World Hon Thom ticketing</a> and <a href="https://vinwonders.com/en/vinwonders-phu-quoc/" target="_blank" rel="noopener">VinWonders Phu Quoc</a> only for live hours, price, maintenance, and family-logistics checks, not as proof that a ticketed attraction belongs in every itinerary.</li>
<li>Read <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a> and <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a> before choosing Phu Quoc over Con Dao, Da Nang/Hoi An, Cat Ba, or skipping beach time.</li>
<li>Cross-check <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before paying for non-refundable island nights.</li>
</ul>
<!-- /wp:list -->

<!-- vg-phu-quoc-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Phu Quoc Travel Guide FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-phu-quoc-faq">
<details><summary>Is Phu Quoc worth visiting for first-time visitors to Vietnam?</summary><p>Yes if the trip has enough time for a real island chapter. It is strongest as a beach, resort, family, or recovery finish, especially on south-first or longer routes.</p></details>
<details><summary>How many days do you need in Phu Quoc?</summary><p>Three nights is the practical minimum for most international travelers. Four or five nights is better if you want Phu Quoc to feel restful rather than like another transfer.</p></details>
<details><summary>What is the best area to stay in Phu Quoc?</summary><p>Long Beach and Duong Dong are the easiest first-time bases. Ong Lang is quieter, Kem/Sao/An Thoi suit polished resort stays, and the north suits travelers who accept longer drives for calmer resort settings.</p></details>
<details><summary>Is Phu Quoc better than Da Nang or Hoi An for beaches?</summary><p>Phu Quoc is better for an island resort finish. Da Nang and Hoi An are better when beach time should sit inside a central Vietnam route with lower transfer friction.</p></details>
<details><summary>Should I fly or take the ferry to Phu Quoc?</summary><p>Most international itineraries are simpler by flight. Ferries from Ha Tien or Rach Gia can work for south-only trips, but schedules, ports, weather, and departure-day buffers must be checked close to travel.</p></details>
<details><summary>Can I visit Phu Quoc without a Vietnam visa?</summary><p>Some Phu Quoc-only situations may have special entry treatment, but this is a live-check issue, not evergreen advice. If your trip includes mainland Vietnam or domestic transit, verify the broader Vietnam entry rules before booking.</p></details>
<details><summary>Is Phu Quoc good in rainy season?</summary><p>It can be, if you value resort downtime and flexible expectations more than guaranteed beach imagery. Use better cancellation terms, a good stay area, and live ferry/weather checks.</p></details>
<details><summary>Is Phu Quoc too touristy?</summary><p>Some areas are developed, which is also why the island is easy. Choose the area by job: convenience, quiet, resort polish, family ease, or nature context. Do not expect every part of the island to feel remote.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> for the full planning order, then compare island value in <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a> and beach value in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>. Use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> to decide whether Phu Quoc improves the route or simply makes it longer.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-phu-quoc-hero:v1',
    'concierge verdict' => 'vg-phu-quoc-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-phu-quoc-at-a-glance:v1',
    'photo grid' => 'vg-phu-quoc-photo-grid:v1',
    'source diversity' => 'vg-phu-quoc-source-diversity:v1',
    'route fit' => 'vg-phu-quoc-route-fit:v1',
    'where to stay' => 'vg-phu-quoc-where-to-stay:v1',
    'beach areas' => 'vg-phu-quoc-beach-areas:v1',
    'priority map' => 'vg-phu-quoc-priority-map:v1',
    'season weather' => 'vg-phu-quoc-season-weather:v1',
    'transport logistics' => 'vg-phu-quoc-transport-logistics:v1',
    'cost booking' => 'vg-phu-quoc-cost-booking:v1',
    'skip logic' => 'vg-phu-quoc-skip-logic:v1',
    'live checks' => 'vg-phu-quoc-live-checks:v1',
    'FAQ' => 'vg-phu-quoc-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_phu_quoc_ops_assert_required_content_markers($content, $required_content_markers);
vg_phu_quoc_ops_assert_internal_page_links_are_published('Phu Quoc Travel Guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Phu Quoc Travel Guide',
    'post_name'      => 'phu-quoc-travel-guide',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_phu_quoc_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Phu Quoc Travel Guide for international travelers deciding whether Vietnam island resort time fits the route by season, flights, ferries, stay area, costs, entry checks, and skip logic.',
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
    vg_phu_quoc_ops_fail('Could not publish Phu Quoc Travel Guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_phu_quoc_ops_fail('Could not publish Phu Quoc Travel Guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Phu Quoc Travel Guide: Where to Stay, Best Time, Costs and Routes');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Phu Quoc travel guide: decide if Vietnam island resort time fits your route, where to stay, best time, flights, ferries, costs, visa checks, and skip logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Phu Quoc travel guide');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Phu Quoc should be a winter-sun, resort, family, or recovery island chapter before adding flights, ferries, resort nights, and beach time to a Vietnam route.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 19, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Phu Quoc Travel Guide with a concierge verdict, at-a-glance decision table, licensed real photo proof, source-diversity panel, route-fit table, stay-area table, beach and experience priority map, season/weather table, transport/ferry/entry checks, cost and booking checks, skip logic, live checks, FAQ, Destinations hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Phu Quoc - https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc - checked July 19, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 19, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 19, 2026\nVietnam.travel - Getting to Vietnam - https://vietnam.travel/plan-your-trip/getting-vietnam - checked July 19, 2026\nVietnam.travel - Visa requirements - https://vietnam.travel/plan-your-trip/visa-requirements - checked July 19, 2026\nOfficial Vietnam e-visa portal - https://evisa.gov.vn/ - checked July 19, 2026\nUNESCO - Kien Giang biosphere reserve - https://www.unesco.org/en/mab/kien-giang - checked July 19, 2026\nVietnam NBCA - Phu Quoc National Park, Kien Giang - https://en.nbca.gov.vn/vuon-quoc-gia-phu-quoc-kien-giang/ - checked July 19, 2026\nPhu Quoc local government portal - https://phuquoc.angiang.gov.vn/ - checked July 19, 2026; current local portal uses An Giang/Phu Quoc administrative language while some conservation sources still reference Kien Giang\nAn Giang provincial portal - Phu Quoc tourism development context - https://angiang.gov.vn/en/phu-quoc-tourism-expects-surge-international-visitors-late-2025 - checked July 19, 2026; promotional development context only\nNational Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked July 19, 2026; live weather and marine checks required close to travel\nPhu Quoc International Airport operator page - https://sunairport.com/phuquoc/vi - checked July 19, 2026; heavy/redirecting page, use as live airport check only\nPhu Quoc Express - Ha Tien to Phu Quoc route - https://phuquocexpress.com/lich-tau-tuyen-ha-tien-phu-quoc-2024 - checked July 19, 2026; operator schedules must be rechecked close to travel\nPhu Quoc Express - Rach Gia to Phu Quoc route - https://phuquocexpress.com/lich-tau-tuyen-rach-gia-phu-quoc - checked July 19, 2026; operator schedules must be rechecked close to travel\nSuperdong - Ha Tien to Phu Quoc route - https://superdong.com.vn/dich-vu/ha-tien-phu-quoc - checked July 19, 2026; operator schedules must be rechecked close to travel\nSuperdong - Rach Gia to Phu Quoc route - https://superdong.com.vn/dich-vu/rach-gia-phu-quoc - checked July 19, 2026; operator schedules must be rechecked close to travel\nThanh Thoi Ferry - https://thanhthoi.vn/ - checked July 19, 2026; live car-ferry/schedule checks only\nSun World Hon Thom ticketing - https://ticket.sunworld.vn/khu-vui-choi/hon-thom-nature-park/ - checked July 19, 2026; attraction hours, pricing, and maintenance are volatile\nVinWonders Phu Quoc - https://vinwonders.com/en/vinwonders-phu-quoc/ - checked July 19, 2026; operator source for live opening and ticket checks only\nWikimedia Commons image record - Kem Beach aerial view Phu Quoc Island Vietnam - https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg - license checked July 19, 2026\nWikimedia Commons image record - Sunset on Long Beach in Phu Quoc Island - https://commons.wikimedia.org/wiki/File:Sunset_on_the_Long_Beach_in_Phu_Quoc_Island,_Vietnam,_5_March_2019.jpg - license checked July 19, 2026\nWikimedia Commons image record - Hon Thom Cable Car aerial view Phu Quoc Island Vietnam - https://commons.wikimedia.org/wiki/File:Hon_Thom_Cable_Car_aerial_view_Phu_Quoc_Island_Vietnam.jpg - license checked July 19, 2026\nWikimedia Commons image record - An Thoi fishing harbour Sunset Town Sun World Phu Quoc Vietnam - https://commons.wikimedia.org/wiki/File:An_Thoi_fishing_harbour_Sunset_Town_Sun_World_Phu_Quoc_Vietnam.jpg - license checked July 19, 2026\nWikimedia Commons image record - Bai-sao-phu-quoc-tuonglamphotos - https://commons.wikimedia.org/wiki/File:Bai-sao-phu-quoc-tuonglamphotos.jpg - license checked July 19, 2026\nWikimedia Commons image record - Star Beach (Bai Sao) - https://commons.wikimedia.org/wiki/File:Star_Beach_(B%C3%A3i_Sao).jpg - license checked July 19, 2026\nWikimedia Commons image record - Festival in Phu Quoc United Center - https://commons.wikimedia.org/wiki/File:Festival_in_Phu_Quoc_United_Center.jpg - license checked July 19, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Phu Quoc is strongest when it solves a route problem: rest, warmth, resort ease, family logistics, or a calm southern finish. It is weakest when added to a tight itinerary simply because an island sounds desirable.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Phu Quoc guide built around island route value rather than generic things-to-do copy.\nConcierge verdict separates strong fit, weak fit, stay-area logic, season posture, and skip cases.\nAt-a-glance table answers worth, nights, area choice, season, and main booking mistake.\nLicensed real photo proof for Kem Beach, Long Beach sunset, Hon Thom/An Thoi, Bai Sao, Star Beach, and north-island entertainment context.\nSource-diversity panel explains what official tourism, UNESCO, NBCA/biodiversity, visa/border, weather/marine, airport/ferry operator, attraction operator, and image-license sources can and cannot prove.\nRoute-fit table links Phu Quoc to 7, 10, 14, 21-day, and south-only itineraries.\nWhere-to-stay table compares Long Beach/Duong Dong, Ong Lang/Cua Can, Kem/Sao/An Thoi, Ganh Dau/north, and airport-side stays by trip job.\nBeach and experience priority map prevents attraction-list behavior by tying each choice to what it changes in the route.\nSeason/weather table keeps southern island timing separate from northern and central Vietnam assumptions.\nTransport/ferry/entry section avoids stale timetable and visa-exemption claims by pushing live checks across airport, ferry, weather, and e-visa sources.\nCost and booking checks cover beach stretch, meal access, cancellation, transfer chain, and insurance.\nSkip logic protects tight first trips and wrong-season expectations.\nVisible source trail, related routes, and update log.");
$related_routes = "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before adding Phu Quoc as a beach or resort chapter.\nBest Islands in Vietnam | /destinations/best-islands-in-vietnam/ | Compare Phu Quoc against Con Dao, Cat Ba, Cham Islands, Ly Son, Phu Quy, Nam Du, and Co To before choosing an island.\nBest Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ | Decide whether the trip needs a beach chapter before selecting an island resort.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check southern island timing and weather risk before booking resort nights.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Confirm flights, ferries, ports, transfers, luggage, and departure buffers before locking the island route.\nVietnam E-Visa | /plan/vietnam-evisa/ | Recheck entry rules and Phu Quoc visa edge cases before relying on any summary.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget for resort nights, island transfers, food isolation, weather flexibility, and premium beach stays.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Decide whether the route should become south-first before adding Phu Quoc.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Phu Quoc belongs only in a south + island version of a short trip.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide whether two weeks can protect a proper island finish without weakening the route.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use Phu Quoc as a slower final chapter only when the full-country route already has margin.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Prepare cash/card habits for island taxis, markets, small operators, and resort extras.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep a connectivity backup for airport, pier, remote resort, driver, and weather-plan changes.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check water, scooter, ferry, interruption, and medical coverage before island travel.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair island deposits, taxis, water activities, scooter rental, and cancellation terms with practical risk checks.";
vg_phu_quoc_ops_assert_related_route_meta_links_are_published('Phu Quoc Travel Guide related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Kem Beach image: Kem Beach aerial view Phu Quoc Island Vietnam by Vivu Vietnam, CC BY-SA 4.0. Body images: Long Beach sunset by Alexey Komarov, CC BY-SA 4.0; Hon Thom Cable Car and An Thoi fishing harbour by Vivu Vietnam, CC BY-SA 4.0; Bai Sao by Trantuonglam, CC BY-SA 4.0; Star Beach / Bai Sao by Vnecofriendly, CC BY-SA 4.0; Festival in Phu Quoc United Center by Reb.vn, CC BY-SA 4.0.');
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
        vg_phu_quoc_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_phu_quoc_ops_fail('Phu Quoc Travel Guide was updated but is not published.');
}

vg_phu_quoc_ops_refresh_destinations_hub();
vg_phu_quoc_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_phu_quoc_ops_log("Published Phu Quoc Travel Guide: {$page_id} {$updated_permalink}");

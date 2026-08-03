<?php
/**
 * Publish the Con Dao Travel Guide using the EEAT destination template.
 *
 * Run from the WordPress root with:
 * VG_FORCE_CON_DAO_GUIDE_REPUBLISH=1 wp eval-file ops/apply-con-dao-travel-guide.php --allow-root
 *
 * Repair only hub/related-route side effects with:
 * VG_REPAIR_CON_DAO_GUIDE_LINKS=1 wp eval-file ops/apply-con-dao-travel-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_con_dao_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_con_dao_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_con_dao_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_CON_DAO_GUIDE_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_con_dao_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_CON_DAO_GUIDE_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_con_dao_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_con_dao_ops_fail('Could not resolve a valid WordPress author for the Con Dao Travel Guide.');
}

function vg_con_dao_ops_internal_path_from_href(string $href): ?string
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

function vg_con_dao_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_con_dao_ops_internal_path_from_href($href);

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
        vg_con_dao_ops_fail(implode(PHP_EOL, $failures));
    }

    vg_con_dao_ops_log("Validated {$label} internal page links are published.");
}

function vg_con_dao_ops_assert_internal_href_is_published(string $label, string $href): void
{
    $path = vg_con_dao_ops_internal_path_from_href($href);

    if ($path === null) {
        vg_con_dao_ops_fail("{$label} does not contain a valid internal path: {$href}");
    }

    if ($path === 'home') {
        $front_page_id = (int) get_option('page_on_front');
        $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
    } else {
        $page = get_page_by_path($path, OBJECT, 'page');
    }

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_con_dao_ops_fail("{$label} links to unpublished page path: /{$path}/");
    }
}

function vg_con_dao_ops_assert_related_route_line_links_are_published(string $label, string $route_line): void
{
    $parts = array_map('trim', explode('|', $route_line));

    if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        vg_con_dao_ops_fail("{$label} related-route line must use: Label | /path/ | reason");
    }

    vg_con_dao_ops_assert_internal_href_is_published($label, $parts[1]);
}

function vg_con_dao_ops_assert_related_route_meta_links_are_published(string $label, string $related_routes): void
{
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];

    foreach ($lines as $index => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        vg_con_dao_ops_assert_related_route_line_links_are_published("{$label} line " . ((int) $index + 1), $line);
    }

    vg_con_dao_ops_log("Validated {$label} related-route links are published.");
}

function vg_con_dao_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_con_dao_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_con_dao_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

function vg_con_dao_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
{
    $marked_block = $marker . "\n" . $block;

    if (str_contains($page->post_content, $marked_block)) {
        vg_con_dao_ops_log("Skipped {$label}: current block already present.");
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
            vg_con_dao_ops_fail("Could not confidently refresh {$label}.");
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
        vg_con_dao_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_con_dao_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_con_dao_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_con_dao_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_con_dao_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_con_dao_ops_published_page_exists('destinations/con-dao-travel-guide')) {
        vg_con_dao_ops_log('Skipped Destinations hub refresh: Con Dao Travel Guide is not published.');
        return;
    }

    $marker = '<!-- vg-con-dao-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use Con Dao when the island needs to feel quiet, natural, and deliberate</h3><p>The <a href="/destinations/con-dao-travel-guide/">Con Dao Travel Guide</a> helps travelers decide whether Vietnam's quiet premium island earns the access friction by nature value, historical memory, season, flight/ferry checks, stay area, cost, and what easier beach chapter it replaces.</p></div>
<!-- /wp:group -->
HTML;

    vg_con_dao_ops_assert_internal_page_links_are_published('Destinations hub Con Dao note', $block);
    vg_con_dao_ops_upsert_marked_group($hub, 'Destinations hub Con Dao note', $marker, $block);
}

function vg_con_dao_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_con_dao_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_con_dao_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_con_dao_ops_log("Skipped related-route refresh for {$label}: Con Dao Travel Guide line is already current.");
        return;
    }

    $next = implode("\n", $next_lines);
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_con_dao_ops_log("Upserted Con Dao Travel Guide related route to {$label}: {$page->ID}");
}

function vg_con_dao_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Con Dao Travel Guide | /destinations/con-dao-travel-guide/ | Decide whether Con Dao should be a quiet premium nature, history, diving, or slow-island chapter before accepting flight, ferry, weather, cost, and buffer-time friction.';

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
        vg_con_dao_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_con_dao_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/con-dao-travel-guide', OBJECT, 'page');

if (vg_con_dao_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_con_dao_ops_fail('Repair mode requires the Con Dao Travel Guide to already be published.');
    }

    vg_con_dao_ops_refresh_destinations_hub();
    vg_con_dao_ops_refresh_inbound_related_routes();
    vg_con_dao_ops_log("Repaired Con Dao Travel Guide side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_con_dao_ops_force_republish_enabled()) {
    vg_con_dao_ops_fail('Con Dao Travel Guide is not a draft. Set VG_FORCE_CON_DAO_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_con_dao_ops_log("Preflight Con Dao Travel Guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_con_dao_ops_log('Preflight Con Dao Travel Guide: no existing page found; creating a child page under /destinations/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg/1920px-Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_(April_2022).jpg');
$park_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/C%C3%B4n_%C4%90%E1%BA%A3o_National_Park.jpg/1920px-C%C3%B4n_%C4%90%E1%BA%A3o_National_Park.jpg');
$park_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:C%C3%B4n_%C4%90%E1%BA%A3o_National_Park.jpg');
$pulo_beach_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/4/4f/Pulo_Condore_island_beach.jpg/1920px-Pulo_Condore_island_beach.jpg');
$pulo_beach_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Pulo_Condore_island_beach.jpg');
$dam_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/5d/ConDao_park_dam.jpg/1920px-ConDao_park_dam.jpg');
$dam_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:ConDao_park_dam.jpg');
$prison_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/92/Con_Dao_prison%2C_Vietnam.JPG/1920px-Con_Dao_prison%2C_Vietnam.JPG');
$prison_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Con_Dao_prison,_Vietnam.JPG');
$phu_hai_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Tr%E1%BA%A1i_Ph%C3%BA_H%E1%BA%A3i_C%C3%B4n_%C4%90%E1%BA%A3o_-_panoramio.jpg/1920px-Tr%E1%BA%A1i_Ph%C3%BA_H%E1%BA%A3i_C%C3%B4n_%C4%90%E1%BA%A3o_-_panoramio.jpg');
$phu_hai_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Tr%E1%BA%A1i_Ph%C3%BA_H%E1%BA%A3i_C%C3%B4n_%C4%90%E1%BA%A3o_-_panoramio.jpg');

$content = <<<HTML
<!-- vg-con-dao-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Quiet beach and headland view on Con Dao Island, Vietnam" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 20, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Con Dao Travel Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Con Dao is Vietnam's quiet premium island answer for travelers who want nature, space, historic memory, and a slower island mood more than easy resort infrastructure. It can be one of the country's most memorable beach chapters, but only when the route can afford its access friction.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: this guide treats Con Dao as a deliberate island decision. The question is whether the island's quiet, protected-area context, history, water conditions, and limited logistics improve the trip more than Phu Quoc convenience or a mainland beach chapter would.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">Daeva Trac / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-con-dao-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-con-dao-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-con-dao-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Choose Con Dao when quiet nature, historic depth, clear water potential, and lower-density evenings are the point of the island chapter.</strong> Choose Phu Quoc when the route needs easier flights, more resort choice, family infrastructure, and lower planning friction. Con Dao is stronger as a purposeful extension than as a last-minute beach add-on.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-con-dao-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-con-dao-shortlist">
<li><strong>Best fit:</strong> 3 to 5 nights for couples, repeat visitors, nature-focused travelers, divers/snorkelers in the right window, and quiet-premium itineraries.</li>
<li><strong>Best route pairing:</strong> Ho Chi Minh City + Mekong + Con Dao, or a 14/21-day route where the island replaces another extension.</li>
<li><strong>Best first base:</strong> central Con Son town or nearby beach areas when meals, airport transfers, history sites, and boat days need less friction.</li>
<li><strong>Best premium fit:</strong> a beach-facing stay when you are comfortable paying for calm, space, and a more contained island rhythm.</li>
<li><strong>Skip first when:</strong> the trip is short, flight/ferry timing is fragile, or the traveler mainly wants nightlife, abundant restaurants, and easy family attractions.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-con-dao-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-con-dao-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-con-dao-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fastest useful Con Dao answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-con-dao-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Is Con Dao worth visiting?</strong></td><td data-label="Answer">Yes when quiet, nature, historical memory, and a lower-density island feel are central to the trip. No when the route needs the easiest beach logistics.</td></tr>
<tr><td data-label="Question"><strong>How many nights?</strong></td><td data-label="Answer">Three nights is the practical minimum; four or five nights makes the island feel calmer and protects one weather or transport wrinkle.</td></tr>
<tr><td data-label="Question"><strong>Best season posture?</strong></td><td data-label="Answer">Treat Con Dao as a live-check island: monsoon direction, sea condition, diving/snorkeling windows, turtle-season ethics, and flight/ferry reliability matter more than a generic monthly label.</td></tr>
<tr><td data-label="Question"><strong>Best for first-timers?</strong></td><td data-label="Answer">Best for travelers who already know they want quiet and are happy with fewer services. Less ideal for first-timers who want broad resort choice and easy backup plans.</td></tr>
<tr><td data-label="Question"><strong>Main mistake?</strong></td><td data-label="Answer">Booking Con Dao as if it were a simpler Phu Quoc: the island is more rewarding when chosen for its limits, not despite them.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-con-dao-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: Con Dao is beach, forest, water, and memory</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the images as planning evidence, not decoration. Con Dao's value changes depending on whether the traveler wants resort quiet, national park context, boat days, historic sites, or a deliberately slower southern island chapter.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-con-dao-photo-grid" aria-label="Con Dao travel guide photography">
<figure><img src="{$hero_image}" alt="Beach view from a quiet Con Dao resort area" loading="lazy" decoding="async"><figcaption>Con Dao's beach value is quiet and premium, not maximum convenience. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Daeva Trac / CC BY-SA 4.0</a>.</figcaption></figure>
<figure><img src="{$park_image}" alt="Con Dao National Park landscape" loading="lazy" decoding="async"><figcaption>The national park context is why Con Dao needs a source trail beyond resort copy. Image: <a href="{$park_credit_url}" target="_blank" rel="license noopener">Tycho / CC BY-SA 3.0</a>.</figcaption></figure>
<figure><img src="{$pulo_beach_image}" alt="Pulo Condore island beach in Con Dao" loading="lazy" decoding="async"><figcaption>Beach days can be excellent, but they still depend on exact coast, season, and sea conditions. Image: <a href="{$pulo_beach_credit_url}" target="_blank" rel="license noopener">Tycho / CC BY-SA 3.0</a>.</figcaption></figure>
<figure><img src="{$dam_image}" alt="Dam and forest setting inside Con Dao National Park" loading="lazy" decoding="async"><figcaption>Non-beach nature time matters when weather or sea conditions change. Image: <a href="{$dam_credit_url}" target="_blank" rel="license noopener">Tycho / CC BY-SA 3.0</a>.</figcaption></figure>
<figure><img src="{$prison_image}" alt="Con Dao prison historical site in Vietnam" loading="lazy" decoding="async"><figcaption>Con Dao's historical memory should be handled as a serious travel reason, not a filler stop. Image: <a href="{$prison_credit_url}" target="_blank" rel="license noopener">Julian von Bredow / CC BY-SA 4.0</a>.</figcaption></figure>
<figure><img src="{$phu_hai_image}" alt="Phu Hai prison camp historical site on Con Dao" loading="lazy" decoding="async"><figcaption>Historic sites change the emotional tone of the island and deserve protected time. Image: <a href="{$phu_hai_credit_url}" target="_blank" rel="license noopener">Tuderna / CC BY 3.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-con-dao-source-diversity:v1 -->
<!-- wp:group {"className":"vg-source-diversity vg-con-dao-source-diversity","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-source-diversity vg-con-dao-source-diversity">
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
<tr><td data-label="Source type">Official destination tourism</td><td data-label="Use it for">Place orientation, broad travel themes, beach/history/nature framing, and official visitor language.</td><td data-label="Do not overuse it for">Exact current crowding, hotel quality, sea condition, or the best booking choice.</td></tr>
<tr><td data-label="Source type">National park / protected-area source</td><td data-label="Use it for">Why Con Dao's forest, marine, turtle, and biodiversity context matters beyond beach imagery.</td><td data-label="Do not overuse it for">Guaranteeing access, wildlife sightings, turtle nesting, trail condition, or ethical tour quality on a specific date.</td></tr>
<tr><td data-label="Source type">Airport operator</td><td data-label="Use it for">Live airport identity, access assumptions, and operational checks before flight-dependent routes.</td><td data-label="Do not overuse it for">Evergreen flight frequency or airline schedule promises.</td></tr>
<tr><td data-label="Source type">Ferry operators</td><td data-label="Use it for">Route availability, port, booking, weather, and timetable checks close to travel.</td><td data-label="Do not overuse it for">Long-term route reliability or same-day onward-flight safety.</td></tr>
<tr><td data-label="Source type">Weather and marine sources</td><td data-label="Use it for">Live rain, wind, storm, sea-condition, and boat-day caution before island plans.</td><td data-label="Do not overuse it for">A perfect-beach guarantee from a monthly season summary.</td></tr>
<tr><td data-label="Source type">Visa and border sources</td><td data-label="Use it for">Entry-rule checks before locking flights and domestic connections.</td><td data-label="Do not overuse it for">Passport-specific advice without checking the official portal, airline, and passport authority.</td></tr>
<tr><td data-label="Source type">Licensed photography</td><td data-label="Use it for">Making the island's beach, park, and history mix visible.</td><td data-label="Do not overuse it for">Proof that current beach, trail, heritage-site, or construction condition matches the image.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-con-dao-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where Con Dao fits in a Vietnam route</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-con-dao-route-fit">
<thead><tr><th>Route type</th><th>Con Dao fit</th><th>VietnamGuide judgment</th></tr></thead>
<tbody>
<tr><td data-label="Route type">7 days in Vietnam</td><td data-label="Con Dao fit">Usually too narrow unless the whole trip is south-first and island-led.</td><td data-label="VietnamGuide judgment">Use <a href="/itineraries/7-days-in-vietnam/">7 Days in Vietnam</a> to protect one region instead of adding a fragile island transfer.</td></tr>
<tr><td data-label="Route type">10 days in Vietnam</td><td data-label="Con Dao fit">Possible only as a Ho Chi Minh City + Mekong + Con Dao route, with one major mainland chapter removed.</td><td data-label="VietnamGuide judgment">Do not add Con Dao to a north + central first trip unless the island is the trip's emotional center.</td></tr>
<tr><td data-label="Route type">14 days in Vietnam</td><td data-label="Con Dao fit">Strong for travelers who want a quiet southern extension and can protect at least three island nights.</td><td data-label="VietnamGuide judgment">Use Con Dao when calm and nature matter more than broad resort convenience.</td></tr>
<tr><td data-label="Route type">21 days in Vietnam</td><td data-label="Con Dao fit">A credible premium extension if the full-country route has margin and the exit flight is protected.</td><td data-label="VietnamGuide judgment">The extra week can absorb access friction, but only if Con Dao replaces another optional add-on.</td></tr>
<tr><td data-label="Route type">South-only trip</td><td data-label="Con Dao fit">Often excellent with Ho Chi Minh City, Mekong, and a slower island finish.</td><td data-label="VietnamGuide judgment">This is the cleanest Con Dao use case for travelers who value quiet over convenience.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-con-dao-where-to-stay:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where to stay in Con Dao by trip job</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Con Dao has fewer backup choices than larger beach destinations, so area choice matters. Pick the stay job first: meals and history access, beach quiet, national-park rhythm, or a more contained premium retreat.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-con-dao-stay-table">
<thead><tr><th>Area style</th><th>Best for</th><th>Watch before booking</th></tr></thead>
<tbody>
<tr><td data-label="Area style">Con Son town / central island</td><td data-label="Best for">First-time logistics, meals, market access, airport transfers, historical sites, and shorter stays.</td><td data-label="Watch before booking">Less secluded than resort beaches; check exact walking distance and transport after dark.</td></tr>
<tr><td data-label="Area style">Beach-facing resort stay</td><td data-label="Best for">Quiet premium trips, couples, honeymoon-style rest, and travelers who want fewer daily decisions.</td><td data-label="Watch before booking">Meal pricing, transfer inclusions, cancellation terms, and whether the beach suits your month.</td></tr>
<tr><td data-label="Area style">National park / nature-adjacent rhythm</td><td data-label="Best for">Hikers, snorkelers/divers, bird/nature interest, and travelers who want the island to feel slower.</td><td data-label="Watch before booking">Guide availability, permitted access, weather, water visibility, and ethical wildlife/turtle rules.</td></tr>
<tr><td data-label="Area style">Airport/short-buffer stay</td><td data-label="Best for">Late arrival, early departure, or one-night protection before a mainland flight.</td><td data-label="Watch before booking">Useful location does not always mean atmospheric; check meal access and transfer timing.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-con-dao-priority-map:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Con Dao priority map</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-con-dao-priority-map">
<thead><tr><th>Priority</th><th>What it changes</th><th>Best traveler fit</th></tr></thead>
<tbody>
<tr><td data-label="Priority">Quiet beach rest</td><td data-label="What it changes">Makes Con Dao a premium recovery chapter rather than a touring checklist.</td><td data-label="Best traveler fit">Couples, honeymoon-style trips, repeat Vietnam visitors, and travelers who dislike crowded beach towns.</td></tr>
<tr><td data-label="Priority">National park and marine context</td><td data-label="What it changes">Gives the island meaning beyond resort time, especially when hikes, boat days, snorkeling, or conservation interest matter.</td><td data-label="Best traveler fit">Nature-first travelers who accept live checks and possible weather limitations.</td></tr>
<tr><td data-label="Priority">History and memory</td><td data-label="What it changes">Adds emotional depth and requires respectful pacing rather than a rushed photo stop.</td><td data-label="Best traveler fit">Travelers who want the island's history to shape the visit, not sit beside it.</td></tr>
<tr><td data-label="Priority">Diving / snorkeling / turtle interest</td><td data-label="What it changes">Turns Con Dao into a conditions-led trip where month, operator, ethics, and sea state matter.</td><td data-label="Best traveler fit">Water-focused travelers willing to keep flexible expectations.</td></tr>
<tr><td data-label="Priority">Low-friction family beach</td><td data-label="What it changes">Usually points away from Con Dao and toward Phu Quoc or Da Nang/Hoi An.</td><td data-label="Best traveler fit">Families who need more restaurants, activities, and backup plans.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-con-dao-season-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best time to visit Con Dao</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Con Dao should be planned as a conditions-led island, not a generic Vietnam beach. Weather month, wind direction, sea state, flight/ferry reliability, diving/snorkeling visibility, and turtle-season ethics can change the value of the same hotel night.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-con-dao-weather-table">
<thead><tr><th>Season lens</th><th>What to expect</th><th>Booking posture</th></tr></thead>
<tbody>
<tr><td data-label="Season lens">Beach-first window</td><td data-label="What to expect">The strongest fit for quiet resort stays, water clarity hopes, and travelers who want the island to feel calm.</td><td data-label="Booking posture">Book earlier, but still verify exact coast, cancellation terms, and flight/ferry exit margin.</td></tr>
<tr><td data-label="Season lens">Shoulder months</td><td data-label="What to expect">Can be rewarding with softer demand, but water activities and ferry days need more humility.</td><td data-label="Booking posture">Keep one flexible day and avoid overpaying for activities that depend on sea condition.</td></tr>
<tr><td data-label="Season lens">Rain/wind pressure</td><td data-label="What to expect">The island may still be atmospheric, but the trip should not depend on perfect beach images or every boat day running.</td><td data-label="Booking posture">Prioritize a strong hotel, travel insurance, interruption coverage, and rebookable movement.</td></tr>
<tr><td data-label="Season lens">Turtle or wildlife interest</td><td data-label="What to expect">Potentially meaningful, but highly sensitive and rules-led.</td><td data-label="Booking posture">Use the national park and responsible operators for live rules; do not treat wildlife as guaranteed entertainment.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-con-dao-transport-logistics:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Flights, ferries, and access checks</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The cleanest Con Dao plan is usually flight-first for international travelers. Ferries can work for south-only routes, but they add port timing, sea-condition exposure, luggage handling, and buffer-day pressure. This is the island where a cheap-looking route can become expensive if the exit day is too tight.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-con-dao-logistics-table">
<thead><tr><th>Logistics choice</th><th>Best use</th><th>Live check</th></tr></thead>
<tbody>
<tr><td data-label="Logistics choice">Domestic flight to Con Dao</td><td data-label="Best use">Most international itineraries, premium time-sensitive trips, short extensions, and travelers who need less port friction.</td><td data-label="Live check">ACV airport information, flight timing, baggage, weather disruption, hotel transfer, and whether the mainland connection has enough margin.</td></tr>
<tr><td data-label="Logistics choice">Ferry from the mainland</td><td data-label="Best use">Flexible south-only routes and travelers who can absorb port time and sea/weather uncertainty.</td><td data-label="Live check">Operator route, current timetable, cancellation/weather policy, port location, luggage rules, and onward-flight buffer.</td></tr>
<tr><td data-label="Logistics choice">Island taxi / hotel transfer</td><td data-label="Best use">Arrival/departure, resort stays, history sites, and non-beach days.</td><td data-label="Live check">Pickup inclusion, night availability, card/cash, remote resort fees, and whether the day plan is too spread out.</td></tr>
<tr><td data-label="Logistics choice">Scooter</td><td data-label="Best use">Only for experienced, licensed, insured riders with sober daylight plans.</td><td data-label="Live check">Insurance exclusions, license validity, helmet quality, rain/wind, and road condition.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-con-dao-cost-booking:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cost and booking checks that matter</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Con Dao often costs more in hidden ways: fewer flights, fewer rooms, fewer backups, weather-sensitive activities, and more expensive consequences when the route is too tight. The right booking sequence protects the island's quiet value.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-con-dao-cost-table">
<thead><tr><th>Before paying</th><th>What to verify</th><th>Why it protects the trip</th></tr></thead>
<tbody>
<tr><td data-label="Before paying">Access chain</td><td data-label="What to verify">Flight/ferry timing, mainland connection, arrival hour, hotel transfer, and departure-day margin.</td><td data-label="Why it protects the trip">Con Dao punishes tight connections more than easy beach towns do.</td></tr>
<tr><td data-label="Before paying">Exact stay job</td><td data-label="What to verify">Town convenience, beach access, meal plan, transfer costs, cancellation terms, and whether quiet is worth the rate.</td><td data-label="Why it protects the trip">The island has less room for a wrong-area booking.</td></tr>
<tr><td data-label="Before paying">Water activities</td><td data-label="What to verify">Season, sea condition, operator rules, refund policy, equipment, and conservation ethics.</td><td data-label="Why it protects the trip">Diving/snorkeling value changes quickly with weather and visibility.</td></tr>
<tr><td data-label="Before paying">History and nature days</td><td data-label="What to verify">Opening access, guide needs, transport, heat/rain, and respectful pacing.</td><td data-label="Why it protects the trip">Con Dao is stronger when history and nature are not squeezed between beach hours.</td></tr>
<tr><td data-label="Before paying">Insurance</td><td data-label="What to verify">Medical care, evacuation, water activities, scooter/motorbike clauses, trip interruption, and missed-connection terms.</td><td data-label="Why it protects the trip">Remote island logistics raise the cost of small problems.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-con-dao-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">When to skip Con Dao</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-con-dao-skip-logic"} -->
<ul class="wp-block-list vg-check-list vg-con-dao-skip-logic">
<li><strong>Skip on a tight first trip</strong> when the north, central Vietnam, Ho Chi Minh City, and departure logistics already fill the calendar.</li>
<li><strong>Skip when convenience matters more than quiet</strong>; Phu Quoc, Da Nang, or Hoi An usually give easier backup plans.</li>
<li><strong>Skip if the budget depends on perfect ferry/flight timing</strong> and a delay would break the mainland connection.</li>
<li><strong>Skip if nightlife, shopping, large resort choice, or many family attractions are important.</strong></li>
<li><strong>Skip if water activities are the only reason</strong> and your dates do not match live sea, visibility, and operator conditions.</li>
<li><strong>Skip if history sites would be treated as a quick checklist</strong>; Con Dao deserves respectful pacing.</li>
</ul>
<!-- /wp:list -->

<!-- vg-con-dao-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before booking Con Dao</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-con-dao-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-con-dao-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/southern-vietnam/con-dao" target="_blank" rel="noopener">Vietnam.travel Con Dao</a>, <a href="https://vietnam.travel/things-to-do/con-dao-sanctuary-sea-solitude-and-slow-travel" target="_blank" rel="noopener">Vietnam.travel's slow-travel Con Dao feature</a>, <a href="https://vietnam.travel/things-to-do/your-beach-break-guide-con-dao" target="_blank" rel="noopener">beach-break guide</a>, <a href="https://vietnam.travel/things-to-do/explore-con-dao-heroic-history-and-eco-tourism-experiences" target="_blank" rel="noopener">history and eco-tourism feature</a>, and <a href="https://vietnam.travel/things-to-do/con-dao-specialty-cuisine" target="_blank" rel="noopener">specialty cuisine feature</a> for official destination context, not live booking guarantees.</li>
<li>Use <a href="https://www.condaopark.com.vn/en/about-con-dao-national-park.html" target="_blank" rel="noopener">Con Dao National Park</a> and <a href="https://condaopark.com.vn/en" target="_blank" rel="noopener">the park home page</a> for protected-area, forest, marine, turtle, and conservation context; recheck access and rules close to travel.</li>
<li>Use <a href="https://condao.com.vn/en/news/news/about-con-dao-909.html" target="_blank" rel="noopener">the Con Dao tourism portal</a> and <a href="https://condao.com.vn/en/news/news/con-dao-tourism-site-recognized-as-a-national-tourism-site-1052.html" target="_blank" rel="noopener">national tourism-site context</a> for local tourism framing, not hotel selection.</li>
<li>Use <a href="https://acv.vn/en/vcs" target="_blank" rel="noopener">ACV Con Dao Airport</a>, <a href="https://superdong.com.vn/dich-vu/soc-trang-con-dao" target="_blank" rel="noopener">Superdong Soc Trang-Con Dao</a>, <a href="https://phuquocexpress.com/en/top-5-con-dao-travel-experiences-for-tourists" target="_blank" rel="noopener">Phu Quoc Express Con Dao travel context</a>, and <a href="https://phuquocexpress.com/en/travel-costs-con-dao-from-a-to-z" target="_blank" rel="noopener">Phu Quoc Express travel-cost context</a> as live logistics and route checks only.</li>
<li>Use <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> plus the <a href="https://www.nchmf.gov.vn/kttv/en-US/1/index.html" target="_blank" rel="noopener">National Centre for Hydro-Meteorological Forecasting</a> before flight, ferry, diving, snorkeling, or turtle-season plans.</li>
<li>Use <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport</a>, <a href="https://vietnam.travel/plan-your-trip/getting-vietnam" target="_blank" rel="noopener">getting to Vietnam</a>, <a href="https://vietnam.travel/plan-your-trip/visa-requirements" target="_blank" rel="noopener">visa requirements</a>, and the <a href="https://evisa.gov.vn/" target="_blank" rel="noopener">official Vietnam e-visa portal</a> before locking domestic connections.</li>
<li>Compare <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, and <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a> before choosing Con Dao over easier island or central-coast beach time.</li>
<li>Cross-check <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before paying for non-refundable island nights.</li>
</ul>
<!-- /wp:list -->

<!-- vg-con-dao-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Con Dao Travel Guide FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-con-dao-faq">
<details><summary>Is Con Dao worth visiting for first-time visitors to Vietnam?</summary><p>Yes if the traveler wants quiet nature, history, and a slower island chapter. No if the first trip already feels full or needs the easiest beach infrastructure.</p></details>
<details><summary>How many days do you need in Con Dao?</summary><p>Three nights is the practical minimum. Four or five nights is better because Con Dao's value depends on slower pacing and because transport or weather can shift plans.</p></details>
<details><summary>Is Con Dao better than Phu Quoc?</summary><p>Con Dao is better for quiet premium nature, lower density, historical depth, and a more deliberate island mood. Phu Quoc is better for convenience, family infrastructure, resort choice, and easier route recovery.</p></details>
<details><summary>Should I fly or take the ferry to Con Dao?</summary><p>Most international itineraries should start with flight checks. Ferries can work for flexible south-only trips, but schedules, ports, weather rules, and onward-flight buffers must be checked close to travel.</p></details>
<details><summary>Is Con Dao good for diving and snorkeling?</summary><p>It can be, but water activities are conditions-led. Month, sea state, visibility, operator quality, conservation rules, and refund terms matter more than a generic island recommendation.</p></details>
<details><summary>Is Con Dao too quiet?</summary><p>It can be too quiet for travelers who want nightlife, large restaurant choice, theme-park-style family activities, or many backup plans. That same quiet is the reason to choose it for the right trip.</p></details>
<details><summary>Do I need travel insurance for Con Dao?</summary><p>It is strongly recommended because remote island travel raises the importance of medical care, evacuation, missed connections, water activities, scooter exclusions, and trip interruption coverage.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then compare island and beach value in <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, and <a href="/destinations/phu-quoc-travel-guide/">Phu Quoc Travel Guide</a>. Use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> to decide whether Con Dao improves the route or simply makes it more fragile.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-con-dao-hero:v1',
    'concierge verdict' => 'vg-con-dao-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-con-dao-at-a-glance:v1',
    'photo grid' => 'vg-con-dao-photo-grid:v1',
    'source diversity' => 'vg-con-dao-source-diversity:v1',
    'route fit' => 'vg-con-dao-route-fit:v1',
    'where to stay' => 'vg-con-dao-where-to-stay:v1',
    'priority map' => 'vg-con-dao-priority-map:v1',
    'season weather' => 'vg-con-dao-season-weather:v1',
    'transport logistics' => 'vg-con-dao-transport-logistics:v1',
    'cost booking' => 'vg-con-dao-cost-booking:v1',
    'skip logic' => 'vg-con-dao-skip-logic:v1',
    'live checks' => 'vg-con-dao-live-checks:v1',
    'FAQ' => 'vg-con-dao-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_con_dao_ops_assert_required_content_markers($content, $required_content_markers);
vg_con_dao_ops_assert_internal_page_links_are_published('Con Dao Travel Guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Con Dao Travel Guide',
    'post_name'      => 'con-dao-travel-guide',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_con_dao_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Con Dao Travel Guide for international travelers deciding whether Vietnam quiet-premium island time fits the route by nature, history, season, flights, ferries, costs, and skip logic.',
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
    vg_con_dao_ops_fail('Could not publish Con Dao Travel Guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_con_dao_ops_fail('Could not publish Con Dao Travel Guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Con Dao Travel Guide: Quiet Island, Best Time, Costs and Routes');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Con Dao travel guide: decide if Vietnam quiet-premium island time fits your route, where to stay, best time, flights, ferries, history, nature, costs, and skip logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Con Dao travel guide');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Con Dao should be a quiet premium nature, history, diving, or slow-island chapter before accepting flight, ferry, weather, cost, and buffer-time friction.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 20, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Con Dao Travel Guide with a concierge verdict, at-a-glance decision table, licensed real photo proof, source-diversity panel, route-fit table, stay-area table, priority map, season/weather table, flight/ferry/access checks, cost and booking checks, skip logic, live checks, FAQ, Destinations hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Con Dao - https://vietnam.travel/places-to-go/southern-vietnam/con-dao - checked July 20, 2026\nVietnam.travel - Con Dao, sanctuary of sea, solitude and slow travel - https://vietnam.travel/things-to-do/con-dao-sanctuary-sea-solitude-and-slow-travel - checked July 20, 2026\nVietnam.travel - Your beach break guide to Con Dao - https://vietnam.travel/things-to-do/your-beach-break-guide-con-dao - checked July 20, 2026\nVietnam.travel - Explore Con Dao heroic history and eco-tourism experiences - https://vietnam.travel/things-to-do/explore-con-dao-heroic-history-and-eco-tourism-experiences - checked July 20, 2026\nVietnam.travel - Con Dao specialty cuisine - https://vietnam.travel/things-to-do/con-dao-specialty-cuisine - checked July 20, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 20, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 20, 2026\nVietnam.travel - Getting to Vietnam - https://vietnam.travel/plan-your-trip/getting-vietnam - checked July 20, 2026\nVietnam.travel - Visa requirements - https://vietnam.travel/plan-your-trip/visa-requirements - checked July 20, 2026\nOfficial Vietnam e-visa portal - https://evisa.gov.vn/ - checked July 20, 2026\nCon Dao National Park - about Con Dao National Park - https://www.condaopark.com.vn/en/about-con-dao-national-park.html - checked July 20, 2026\nCon Dao National Park home page - https://condaopark.com.vn/en - checked July 20, 2026\nCon Dao tourism portal - About Con Dao - https://condao.com.vn/en/news/news/about-con-dao-909.html - checked July 20, 2026\nCon Dao tourism portal - national tourism site context - https://condao.com.vn/en/news/news/con-dao-tourism-site-recognized-as-a-national-tourism-site-1052.html - checked July 20, 2026\nAirports Corporation of Vietnam - Con Dao Airport - https://acv.vn/en/vcs - checked July 20, 2026; live airport checks required close to travel\nSuperdong - Soc Trang to Con Dao route - https://superdong.com.vn/dich-vu/soc-trang-con-dao - checked July 20, 2026; operator schedules must be rechecked close to travel\nPhu Quoc Express - Con Dao travel experiences - https://phuquocexpress.com/en/top-5-con-dao-travel-experiences-for-tourists - checked July 20, 2026; operator context only\nPhu Quoc Express - Con Dao travel costs context - https://phuquocexpress.com/en/travel-costs-con-dao-from-a-to-z - checked July 20, 2026; operator context only\nNational Centre for Hydro-Meteorological Forecasting - https://www.nchmf.gov.vn/kttv/en-US/1/index.html - checked July 20, 2026; live weather and marine checks required close to travel\nWikimedia Commons image record - Beach view from Six Senses Resort in Con Dao - https://commons.wikimedia.org/wiki/File:Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_(April_2022).jpg - license checked July 20, 2026\nWikimedia Commons image record - Con Dao National Park - https://commons.wikimedia.org/wiki/File:C%C3%B4n_%C4%90%E1%BA%A3o_National_Park.jpg - license checked July 20, 2026\nWikimedia Commons image record - Pulo Condore island beach - https://commons.wikimedia.org/wiki/File:Pulo_Condore_island_beach.jpg - license checked July 20, 2026\nWikimedia Commons image record - ConDao park dam - https://commons.wikimedia.org/wiki/File:ConDao_park_dam.jpg - license checked July 20, 2026\nWikimedia Commons image record - Con Dao prison, Vietnam - https://commons.wikimedia.org/wiki/File:Con_Dao_prison,_Vietnam.JPG - license checked July 20, 2026\nWikimedia Commons image record - Trai Phu Hai Con Dao - panoramio - https://commons.wikimedia.org/wiki/File:Tr%E1%BA%A1i_Ph%C3%BA_H%E1%BA%A3i_C%C3%B4n_%C4%90%E1%BA%A3o_-_panoramio.jpg - license checked July 20, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Con Dao is strongest when the trip deliberately wants quiet, protected-area nature, historic memory, and a slower island mood. It is weakest when added as a convenience beach stop to a route that cannot absorb flight, ferry, weather, and buffer risk.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Con Dao guide built around island route value rather than generic beach-list copy.\nConcierge verdict separates quiet premium fit from Phu Quoc convenience.\nAt-a-glance table answers worth, nights, season posture, first-time fit, and main booking mistake.\nLicensed real photo proof for Con Dao beach, national park, Pulo Condore beach, park dam, Con Dao prison, and Phu Hai prison.\nSource-diversity panel explains what official tourism, national park, airport, ferry operator, weather/marine, visa, and image-license sources can and cannot prove.\nRoute-fit table links Con Dao to 7, 10, 14, 21-day, and south-only itineraries.\nWhere-to-stay table compares central town, beach-facing resort, national park/nature rhythm, and airport/buffer stay by trip job.\nPriority map prevents attraction-list behavior by tying beach, national park, historical memory, water activities, and family convenience to route consequences.\nSeason/weather table treats Con Dao as a conditions-led island with live sea, wind, and wildlife checks.\nTransport section avoids stale timetable claims by pushing ACV, ferry operator, weather, and buffer checks.\nCost and booking checks cover access chain, exact stay job, water activities, history/nature days, and insurance.\nSkip logic protects tight first trips and wrong-fit traveler expectations.\nVisible source trail, related routes, and update log.");
$related_routes = "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before adding Con Dao as a quiet island chapter.\nBest Islands in Vietnam | /destinations/best-islands-in-vietnam/ | Compare Con Dao against Phu Quoc, Cat Ba, Cham Islands, Ly Son, Phu Quy, Nam Du, and Co To before choosing an island.\nBest Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ | Decide whether the trip needs a beach chapter before accepting Con Dao's access friction.\nPhu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Compare quiet premium nature against easier resort infrastructure before choosing the southern island.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check southern island timing, wind, rain, and sea-condition risk before booking Con Dao.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Confirm flights, ferries, ports, transfers, luggage, and departure buffers before locking the island route.\nVietnam E-Visa | /plan/vietnam-evisa/ | Recheck entry rules before building domestic connections into a Con Dao route.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget for limited flights, ferry buffers, premium stays, water activities, and island backup plans.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Decide whether the route should become south-first before adding a remote island.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Con Dao belongs only in a south + island version of a short trip.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide whether two weeks can protect a proper quiet-island extension without weakening the route.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use Con Dao as a slower premium extension only when the full-country route already has margin.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Prepare cash/card habits for taxis, small operators, deposits, and island backup plans.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep a connectivity backup for airport, ferry, hotel transfer, driver, and weather changes.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check water, scooter, ferry, interruption, evacuation, and medical coverage before remote island travel.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair island deposits, scooter rental, taxis, water activities, and cancellation terms with practical risk checks.";
vg_con_dao_ops_assert_related_route_meta_links_are_published('Con Dao Travel Guide related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and body images: Con Dao beach by Daeva Trac, CC BY-SA 4.0; Con Dao National Park, Pulo Condore beach, and ConDao park dam by Tycho, CC BY-SA 3.0; Con Dao prison by Julian von Bredow, CC BY-SA 4.0; Trai Phu Hai Con Dao by Tuderna, CC BY 3.0.');
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
        vg_con_dao_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_con_dao_ops_fail('Con Dao Travel Guide was updated but is not published.');
}

vg_con_dao_ops_refresh_destinations_hub();
vg_con_dao_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_con_dao_ops_log("Published Con Dao Travel Guide: {$page_id} {$updated_permalink}");

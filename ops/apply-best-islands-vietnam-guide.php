<?php
/**
 * Publish the Best Islands in Vietnam guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * VG_FORCE_BEST_ISLANDS_GUIDE_REPUBLISH=1 wp eval-file ops/apply-best-islands-vietnam-guide.php --allow-root
 *
 * Repair only hub/related-route side effects with:
 * VG_REPAIR_BEST_ISLANDS_GUIDE_LINKS=1 wp eval-file ops/apply-best-islands-vietnam-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_best_islands_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_best_islands_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_best_islands_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_BEST_ISLANDS_GUIDE_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_best_islands_ops_repair_links_enabled(): bool
{
    $value = getenv('VG_REPAIR_BEST_ISLANDS_GUIDE_LINKS');

    return is_string($value) && trim($value) === '1';
}

function vg_best_islands_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_best_islands_ops_fail('Could not resolve a valid WordPress author for the Best Islands in Vietnam guide.');
}

function vg_best_islands_ops_internal_path_from_href(string $href): ?string
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

function vg_best_islands_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_best_islands_ops_internal_path_from_href($href);

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
        vg_best_islands_ops_fail(implode(PHP_EOL, $failures));
    }

    vg_best_islands_ops_log("Validated {$label} internal page links are published.");
}

function vg_best_islands_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_best_islands_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_best_islands_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

function vg_best_islands_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
{
    $marked_block = $marker . "\n" . $block;

    if (str_contains($page->post_content, $marked_block)) {
        vg_best_islands_ops_log("Skipped {$label}: current block already present.");
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
            vg_best_islands_ops_fail("Could not confidently refresh {$label}.");
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
        vg_best_islands_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_best_islands_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_best_islands_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_best_islands_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_best_islands_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_best_islands_ops_published_page_exists('destinations/best-islands-in-vietnam')) {
        vg_best_islands_ops_log('Skipped Destinations hub refresh: Best Islands in Vietnam guide is not published.');
        return;
    }

    $marker = '<!-- vg-best-islands-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose islands by route job, source confidence, and logistics</h3><p>The <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a> guide compares Phu Quoc, Con Dao, Cat Ba, Cham Islands, Ly Son, Phu Quy, Nam Du, and Co To by season, ferry/flight friction, source trail, and what each island actually adds to a Vietnam route.</p></div>
<!-- /wp:group -->
HTML;

    vg_best_islands_ops_assert_internal_page_links_are_published('Destinations hub Best Islands note', $block);
    vg_best_islands_ops_upsert_marked_group($hub, 'Destinations hub Best Islands note', $marker, $block);
}

function vg_best_islands_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_best_islands_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);

    if (str_contains($current, '/destinations/best-islands-in-vietnam/')) {
        vg_best_islands_ops_log("Skipped related-route refresh for {$label}: Best Islands guide is already present.");
        return;
    }

    $next = trim($current) === '' ? $route_line : rtrim($current) . "\n" . $route_line;
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_best_islands_ops_log("Added Best Islands guide related route to {$label}: {$page->ID}");
}

function vg_best_islands_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Best Islands in Vietnam | /destinations/best-islands-in-vietnam/ | Choose the island chapter by route job, season, source confidence, ferry or flight friction, and whether the island improves the trip more than another mainland stop.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
            'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
            'compare/hoi-an-vs-hue' => 'Hoi An vs Hue guide',
            'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ] as $path => $label
    ) {
        vg_best_islands_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_best_islands_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/best-islands-in-vietnam', OBJECT, 'page');

if (vg_best_islands_ops_repair_links_enabled()) {
    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_best_islands_ops_fail('Repair mode requires the Best Islands in Vietnam guide to already be published.');
    }

    vg_best_islands_ops_refresh_destinations_hub();
    vg_best_islands_ops_refresh_inbound_related_routes();
    vg_best_islands_ops_log("Repaired Best Islands in Vietnam guide side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_best_islands_ops_force_republish_enabled()) {
    vg_best_islands_ops_fail('Best Islands in Vietnam guide is not a draft. Set VG_FORCE_BEST_ISLANDS_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_best_islands_ops_log("Preflight Best Islands in Vietnam guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_best_islands_ops_log('Preflight Best Islands in Vietnam guide: no existing page found; creating a child page under /destinations/.');
}

$phu_quoc_hero = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$phu_quoc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$con_dao_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg/1280px-Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg');
$con_dao_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_(April_2022).jpg');
$cat_ba_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Cat_Ba_Island.jpg/1280px-Cat_Ba_Island.jpg');
$cat_ba_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cat_Ba_Island.jpg');
$cham_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/58/Cu_Lao_Cham_Marine_Park%2C_Vietnam.jpg/1280px-Cu_Lao_Cham_Marine_Park%2C_Vietnam.jpg');
$cham_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cu_Lao_Cham_Marine_Park,_Vietnam.jpg');
$ly_son_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/e/ef/Ly_Son3.jpg/1280px-Ly_Son3.jpg');
$ly_son_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ly_Son3.jpg');
$co_to_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/51/Alone_front_of_Co_To_beach%2C_Viet_Nam_%28160620021%29.jpg/1280px-Alone_front_of_Co_To_beach%2C_Viet_Nam_%28160620021%29.jpg');
$co_to_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Alone_front_of_Co_To_beach,_Viet_Nam_(160620021).jpg');
$phu_quy_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6a/Chualinhsonphuquy.jpg/1280px-Chualinhsonphuquy.jpg');
$phu_quy_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Chualinhsonphuquy.jpg');

$content = <<<HTML
<!-- vg-best-islands-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$phu_quoc_hero}","dimRatio":55,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Aerial view of Kem Beach on Phu Quoc Island, Vietnam" src="{$phu_quoc_hero}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 19, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Best Islands in Vietnam</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Vietnam's best island is not the one with the loudest resort brochure. It is the island that solves the route: winter-sun rest, quiet premium nature, northern bay access, a Hoi An sea-day, volcanic culture, or a local-feeling ferry escape that still makes sense after weather, transfers, and source confidence are counted.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: this guide treats islands as itinerary decisions. Original judgment, source trail, and logistics discipline matter more than a recycled prettiest-islands ranking.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$phu_quoc_credit_url}" target="_blank" rel="license noopener">Vivu Vietnam / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-best-islands-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-best-islands-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-best-islands-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most international travelers, Phu Quoc is the easiest island resort answer, Con Dao is the strongest quiet premium answer, and Cat Ba is the best northern island-base answer.</strong> Cham Islands, Ly Son, Phu Quy, Nam Du, and Co To can be excellent, but they should be chosen only when the route, season, ferry plan, and traveler patience match the island's real job.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-best-islands-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-best-islands-shortlist">
<li><strong>Best first-trip island default:</strong> Phu Quoc when the trip needs easy resort rest, direct flight logic, and winter-sun value.</li>
<li><strong>Best quiet premium island:</strong> Con Dao when space, nature, history, and slower evenings matter more than convenience.</li>
<li><strong>Best northern island base:</strong> Cat Ba when Lan Ha Bay, national park time, and island pacing improve the route.</li>
<li><strong>Best central add-on:</strong> Cham Islands from Hoi An only when sea conditions and boat timing are friendly.</li>
<li><strong>Best anti-spam rule:</strong> skip any island whose only job is to add another famous name to a route already short on time.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-best-islands-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-best-islands-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-best-islands-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fastest useful answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-islands-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Best island for most first trips?</strong></td><td data-label="Answer">Phu Quoc, if a beach/resort chapter belongs in the route and the season supports it.</td></tr>
<tr><td data-label="Question"><strong>Best island for quiet luxury?</strong></td><td data-label="Answer">Con Dao, when lower density, nature, and calm are worth higher access friction.</td></tr>
<tr><td data-label="Question"><strong>Best northern island?</strong></td><td data-label="Answer">Cat Ba, but treat it as a Lan Ha and national-park base, not a pure beach island.</td></tr>
<tr><td data-label="Question"><strong>Best central island add-on?</strong></td><td data-label="Answer">Cham Islands from Hoi An in the right sea window; Ly Son if volcanic/offbeat culture is the point.</td></tr>
<tr><td data-label="Question"><strong>Main mistake?</strong></td><td data-label="Answer">Booking an island because it looks beautiful before checking ferry/flight timing, weather risk, cancellation terms, and what the island replaces.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-best-islands-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: the islands do different jobs</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The photos below are not decoration. They show why the islands should be compared by trip role: resort beach, quiet premium coast, limestone island base, marine-park add-on, volcanic island, northern local beach, and offbeat local texture.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-best-islands-photo-grid" aria-label="Best Islands in Vietnam photography">
<figure class="vg-guide-photo"><img src="{$phu_quoc_hero}" alt="Aerial view of Kem Beach on Phu Quoc Island" loading="lazy" decoding="async"><figcaption>Phu Quoc works when the beach chapter is the trip's recovery engine. Image: <a href="{$phu_quoc_credit_url}" target="_blank" rel="license noopener">Vivu Vietnam / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$con_dao_image}" alt="Beach view on Con Dao Island in Vietnam" loading="lazy" decoding="async"><figcaption>Con Dao is the quieter premium island when access friction is acceptable. Image: <a href="{$con_dao_credit_url}" target="_blank" rel="license noopener">Daeva Trac / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cat_ba_image}" alt="Cat Ba Island limestone bay scenery in northern Vietnam" loading="lazy" decoding="async"><figcaption>Cat Ba earns its place when Lan Ha and national park time change the northern route. Image: <a href="{$cat_ba_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cham_image}" alt="Cu Lao Cham marine park scenery near Hoi An" loading="lazy" decoding="async"><figcaption>Cham Islands are strongest as a Hoi An sea-day, not a standalone beach promise. Image: <a href="{$cham_credit_url}" target="_blank" rel="license noopener">Kok Leng Yeo / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$ly_son_image}" alt="Ly Son Island volcanic coast in central Vietnam" loading="lazy" decoding="async"><figcaption>Ly Son is a volcanic and cultural detour; the ferry plan is part of the value test. Image: <a href="{$ly_son_credit_url}" target="_blank" rel="license noopener">BertholdD / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$co_to_image}" alt="Co To beach in northern Vietnam" loading="lazy" decoding="async"><figcaption>Co To belongs to a seasonal northern-island conversation, not a default first-trip route. Image: <a href="{$co_to_credit_url}" target="_blank" rel="license noopener">Tuan Nguyen / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$phu_quy_image}" alt="Linh Son pagoda on Phu Quy Island" loading="lazy" decoding="async"><figcaption>Phu Quy is useful when offbeat local texture is the point and ferry friction is acceptable. Image: <a href="{$phu_quy_credit_url}" target="_blank" rel="license noopener">Thai Nhi / Public domain</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-best-islands-source-diversity:v1 -->
<!-- wp:group {"className":"vg-source-diversity vg-best-islands-source-diversity","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-source-diversity vg-best-islands-source-diversity">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Source diversity</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How this guide avoids thin island-list copy</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Every island recommendation is tied to a different source job. Official destination pages help frame the place, UNESCO and national park sources add conservation context, local portals help with smaller-island reality, ferry/operator pages stay in live-check status, and image records prove that the photographs are licensed rather than invented.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-islands-source-table">
<thead><tr><th>Source job</th><th>Primary use</th><th>How to read it</th></tr></thead>
<tbody>
<tr><td data-label="Source job">Official destination framing</td><td data-label="Primary use">Vietnam.travel Phu Quoc, Con Dao, weather, transport, and Phu Quy orientation.</td><td data-label="How to read it">Good for evergreen context; still recheck season and transport close to travel.</td></tr>
<tr><td data-label="Source job">Conservation and protected-area context</td><td data-label="Primary use">UNESCO Kien Giang, UNESCO Cat Ba, Con Dao National Park, and Cat Ba National Park.</td><td data-label="How to read it">Useful for why an island matters beyond resorts; not a booking source.</td></tr>
<tr><td data-label="Source job">Local/provincial checks</td><td data-label="Primary use">Hoi An heritage for Cu Lao Cham, Quang Ngai province for Ly Son, Quang Ninh portal for northern island context.</td><td data-label="How to read it">Useful for smaller islands where generic travel blogs often outrank official context.</td></tr>
<tr><td data-label="Source job">Live logistics</td><td data-label="Primary use">Sa Ky port, Phu Quy Express, Superdong, and Phu Quoc Express checks.</td><td data-label="How to read it">Schedules, routes, and cancellations change; never treat them as evergreen facts.</td></tr>
<tr><td data-label="Source job">Image proof</td><td data-label="Primary use">Wikimedia Commons image records and visible license credits.</td><td data-label="How to read it">Proof images support place identity; they do not prove current beach conditions.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-best-islands-decision-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Island decision matrix</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the island's job. Then ask whether that job is worth the transfer cost and the itinerary space.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-islands-decision-matrix">
<thead><tr><th>Island</th><th>Best job</th><th>Best for</th><th>Not ideal for</th><th>Route reality</th></tr></thead>
<tbody>
<tr><td data-label="Island">Phu Quoc</td><td data-label="Best job">Easy resort island and winter-sun recovery.</td><td data-label="Best for">First-timers, families, couples, travelers ending in the south.</td><td data-label="Not ideal for">Travelers who want wild solitude or a culture-heavy island.</td><td data-label="Route reality">Works best when flights and season make the beach chapter easy.</td></tr>
<tr><td data-label="Island">Con Dao</td><td data-label="Best job">Quiet premium nature, history, and slower evenings.</td><td data-label="Best for">Couples, repeat visitors, quiet-luxury travelers, divers in the right window.</td><td data-label="Not ideal for">Budget routes, rushed itineraries, travelers needing lots of services.</td><td data-label="Route reality">Protect buffer time; access and weather can make the island expensive in time.</td></tr>
<tr><td data-label="Island">Cat Ba</td><td data-label="Best job">Northern island base for Lan Ha Bay and Cat Ba National Park.</td><td data-label="Best for">Northern routes choosing between bay cruise, island night, and park texture.</td><td data-label="Not ideal for">A pure beach holiday or a route that already has too many northern stops.</td><td data-label="Route reality">The ferry/port plan has to improve the bay chapter, not just complicate it.</td></tr>
<tr><td data-label="Island">Cham Islands</td><td data-label="Best job">Hoi An sea-day and marine-park add-on.</td><td data-label="Best for">Central Vietnam travelers with spare Hoi An time in a suitable sea window.</td><td data-label="Not ideal for">Standalone island holidays or stormy/rough-sea assumptions.</td><td data-label="Route reality">Treat boat operation and same-day return as live checks, not fixed promises.</td></tr>
<tr><td data-label="Island">Ly Son</td><td data-label="Best job">Volcanic coast, garlic-island culture, and offbeat central detour.</td><td data-label="Best for">Repeat visitors, photographers, slower central routes.</td><td data-label="Not ideal for">First-trip routes that still need Hoi An, Hue, Da Nang, and Ninh Binh.</td><td data-label="Route reality">The Sa Ky ferry plan and onward movement decide whether the island earns its nights.</td></tr>
<tr><td data-label="Island">Phu Quy</td><td data-label="Best job">Local-feeling island value and slower south-central escape.</td><td data-label="Best for">Flexible travelers who value texture over polish.</td><td data-label="Not ideal for">Travelers who need luxury certainty, lots of English service, or tight onward plans.</td><td data-label="Route reality">Use ferry/operator checks close to travel and keep cancellation buffers.</td></tr>
<tr><td data-label="Island">Nam Du</td><td data-label="Best job">Local Gulf island texture from the Mekong/Kien Giang side.</td><td data-label="Best for">Repeat visitors and domestic-style island routes.</td><td data-label="Not ideal for">First-time Vietnam travelers who need a smooth resort chapter.</td><td data-label="Route reality">Treat as a flexible route extension, not a core first-trip pillar.</td></tr>
<tr><td data-label="Island">Co To</td><td data-label="Best job">Seasonal northern island and local beach reset.</td><td data-label="Best for">Northern summer routes with flexible ferry/weather tolerance.</td><td data-label="Not ideal for">Winter-sun travelers or first trips with limited northern nights.</td><td data-label="Route reality">Co To competes with Ha Long, Lan Ha, Cat Ba, and Ninh Binh for the same northern calendar.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-islands-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route fit by trip length</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-islands-route-fit">
<thead><tr><th>Trip shape</th><th>Best island move</th><th>Usually skip first</th><th>Why</th></tr></thead>
<tbody>
<tr><td data-label="Trip shape">7 days</td><td data-label="Best island move">Usually no separate island unless the whole trip is Phu Quoc or a focused north/Cat Ba route.</td><td data-label="Usually skip first">Small-island ferry detours.</td><td data-label="Why">A short route needs fewer bases and fewer fragile exits.</td></tr>
<tr><td data-label="Trip shape">10 days</td><td data-label="Best island move">Phu Quoc as a south-end recovery chapter, or Cat Ba if the north is the main story.</td><td data-label="Usually skip first">Ly Son, Phu Quy, Nam Du, and Co To unless they are the reason for the trip.</td><td data-label="Why">Ten days can hold one island job, not a collector's list.</td></tr>
<tr><td data-label="Trip shape">14 days</td><td data-label="Best island move">One island chapter can work: Phu Quoc, Con Dao, Cat Ba, or a careful central add-on.</td><td data-label="Usually skip first">Two disconnected islands.</td><td data-label="Why">Two weeks still punishes cross-country island hopping.</td></tr>
<tr><td data-label="Trip shape">21 days</td><td data-label="Best island move">One premium island plus one small island is possible if the route has real slack.</td><td data-label="Usually skip first">Any ferry chapter with no buffer after it.</td><td data-label="Why">Extra time helps, but weather and ports still write the rules.</td></tr>
<tr><td data-label="Trip shape">Central Vietnam route</td><td data-label="Best island move">Cham Islands or Ly Son, only if sea/ferry timing is kind.</td><td data-label="Usually skip first">Phu Quoc or Con Dao unless the trip continues south.</td><td data-label="Why">A far southern island can steal the central route's slow value.</td></tr>
<tr><td data-label="Trip shape">Northern scenery route</td><td data-label="Best island move">Cat Ba when Lan Ha and park time replace a weaker cruise plan.</td><td data-label="Usually skip first">Co To unless the route is seasonal and flexible.</td><td data-label="Why">The north already has Hanoi, Ninh Binh, Ha Long, Lan Ha, and Cat Ba competing for nights.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-islands-season-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Season and weather pressure</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Island weather is more route-sensitive than city weather because ferries, boats, beach value, and cancellation terms are part of the product.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-islands-season-weather">
<thead><tr><th>Island group</th><th>Season logic</th><th>Planning move</th></tr></thead>
<tbody>
<tr><td data-label="Island group">Phu Quoc and southern Gulf islands</td><td data-label="Season logic">Often strongest as a dry-season/winter-sun chapter, but resort areas and sea conditions still vary.</td><td data-label="Planning move">Check weather, flight, ferry, and hotel cancellation terms before making the island the route anchor.</td></tr>
<tr><td data-label="Island group">Con Dao</td><td data-label="Season logic">Quiet appeal is strong, but access, sea conditions, and activity windows matter.</td><td data-label="Planning move">Protect buffers and verify flight/boat details close to travel.</td></tr>
<tr><td data-label="Island group">Cat Ba and Co To</td><td data-label="Season logic">Northern islands are not winter-sun substitutes; visibility, cool air, rain, and storms change the value.</td><td data-label="Planning move">Judge them as scenery/island-base choices more than beach guarantees.</td></tr>
<tr><td data-label="Island group">Cham Islands and Ly Son</td><td data-label="Season logic">Central coast sea windows and storm exposure matter; boat operation is a live condition.</td><td data-label="Planning move">Never buy a central-island plan without checking boats and weather near travel.</td></tr>
<tr><td data-label="Island group">Phu Quy and Nam Du</td><td data-label="Season logic">Smaller-island value depends heavily on ferry reliability and flexible expectations.</td><td data-label="Planning move">Add buffers after ferry exits and avoid critical flights the same day.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-islands-logistics:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Logistics: the hidden island tax</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-islands-logistics">
<thead><tr><th>Island</th><th>Main access pattern</th><th>Hidden cost</th><th>What to verify</th></tr></thead>
<tbody>
<tr><td data-label="Island">Phu Quoc</td><td data-label="Main access pattern">Flight-led resort island, with some ferry logic depending on route.</td><td data-label="Hidden cost">Resort area mismatch, peak-date pricing, airport transfers.</td><td data-label="What to verify">Arrival airport, hotel area, transfer terms, weather, and cancellation policy.</td></tr>
<tr><td data-label="Island">Con Dao</td><td data-label="Main access pattern">Flight-first for most international travelers.</td><td data-label="Hidden cost">Limited access, higher hotel pressure, activity weather.</td><td data-label="What to verify">Flight timings, medical/evacuation coverage, water activity terms, and buffers.</td></tr>
<tr><td data-label="Island">Cat Ba</td><td data-label="Main access pattern">Hanoi/Hai Phong/Ha Long transfer plus ferry/speedboat/cruise logic.</td><td data-label="Hidden cost">Port confusion and luggage movement.</td><td data-label="What to verify">Pickup, pier, ferry/speedboat leg, cruise route, drop-off, and bad-weather policy.</td></tr>
<tr><td data-label="Island">Cham Islands</td><td data-label="Main access pattern">Boat from the Hoi An area when conditions allow.</td><td data-label="Hidden cost">Same-day cancellations and rough sea assumptions.</td><td data-label="What to verify">Operator status, sea conditions, return timing, and whether overnight stays are realistic.</td></tr>
<tr><td data-label="Island">Ly Son</td><td data-label="Main access pattern">Sa Ky port ferry/speedboat from Quang Ngai side.</td><td data-label="Hidden cost">Port transfer plus schedule fragility.</td><td data-label="What to verify">Sa Ky schedules, weather, ticket terms, and onward transport.</td></tr>
<tr><td data-label="Island">Phu Quy, Nam Du, Co To</td><td data-label="Main access pattern">Ferry-led smaller-island travel.</td><td data-label="Hidden cost">Service variability, weather disruption, and thinner English support.</td><td data-label="What to verify">Operator schedules, ferry class, luggage, hotel flexibility, and buffer day.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-islands-island-profiles:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Island profiles with original judgment</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-best-islands-profiles">
<details open><summary>Phu Quoc: easiest island resort choice</summary><p>Choose Phu Quoc when the route needs clean beach rest, resort infrastructure, and a south-friendly finish. It is the least fragile answer for many first trips, but not automatically the most interesting island.</p></details>
<details><summary>Con Dao: quiet premium and nature-first</summary><p>Choose Con Dao when calm, nature, history, and a lower-density island matter more than price convenience. It is one of Vietnam's strongest premium island choices, but it punishes rushed planning.</p></details>
<details><summary>Cat Ba: northern island base, not beach substitute</summary><p>Choose Cat Ba when Lan Ha Bay, national park texture, and a slower northern base change the trip. If the island does not improve the bay decision, it becomes ferry friction.</p></details>
<details><summary>Cham Islands: Hoi An add-on with live sea conditions</summary><p>Choose Cham Islands when central Vietnam has spare Hoi An time and the boat window is friendly. Do not treat it as a guaranteed beach day months in advance.</p></details>
<details><summary>Ly Son: volcanic culture for flexible central routes</summary><p>Choose Ly Son when the island itself is the point: volcanic coast, garlic-island culture, and offbeat photography. Skip it when the central route still lacks protected Hoi An, Hue, and Da Nang time.</p></details>
<details><summary>Phu Quy, Nam Du, and Co To: local texture with ferry discipline</summary><p>These islands can be memorable because they are less polished. That is also the risk. They belong in flexible routes with live ferry checks, practical expectations, and room for disruption.</p></details>
</div>
<!-- /wp:html -->

<!-- vg-best-islands-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-islands-skip-logic">
<thead><tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Add back only when...</th></tr></thead>
<tbody>
<tr><td data-label="If you want...">A first Vietnam trip</td><td data-label="Protect this">Hanoi, one northern landscape, a central chapter, and a simple south or beach finish.</td><td data-label="Skip first">Small islands that require a ferry and buffer day.</td><td data-label="Add back only when...">The island is the reason for the trip or the route has real slack.</td></tr>
<tr><td data-label="If you want...">Beach rest</td><td data-label="Protect this">A beach that fits season, flight path, and hotel area.</td><td data-label="Skip first">Islands chosen only because they are less famous.</td><td data-label="Add back only when...">The less famous island also solves the route better.</td></tr>
<tr><td data-label="If you want...">Northern scenery</td><td data-label="Protect this">Ninh Binh, Ha Long/Lan Ha, Cat Ba, and Hanoi pacing.</td><td data-label="Skip first">Co To unless seasonal island time is a clear priority.</td><td data-label="Add back only when...">You remove another northern stop or extend the north.</td></tr>
<tr><td data-label="If you want...">Central Vietnam depth</td><td data-label="Protect this">Hoi An, Hue, Da Nang, weather, and transfer order.</td><td data-label="Skip first">Ly Son or Cham Islands when the sea window is uncertain.</td><td data-label="Add back only when...">Boat/ferry checks are good and central nights are protected.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-islands-booking-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Booking checks before you commit</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-best-islands-booking-checks"} -->
<ul class="wp-block-list vg-check-list vg-best-islands-booking-checks">
<li>Name the island's job before booking: resort rest, quiet premium, bay base, sea-day, volcanic culture, or local ferry escape.</li>
<li>Check whether the island replaces another stop or merely adds another transfer.</li>
<li>Verify flight/ferry schedules close to travel; do not rely on stale blog times.</li>
<li>Keep a buffer after ferry-led islands, especially before flights, long transfers, or prepaid tours.</li>
<li>Read hotel location carefully. On big islands, the island name is not enough to explain the actual beach or transport burden.</li>
<li>For water activities, check weather, cancellation terms, safety standards, and whether your insurance covers the activity.</li>
<li>For smaller islands, assume English support and card acceptance may be thinner than in major tourist bases.</li>
</ul>
<!-- /wp:list -->

<!-- vg-best-islands-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before travel</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-best-islands-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-best-islands-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc" target="_blank" rel="noopener">Vietnam.travel Phu Quoc</a>, <a href="https://vietnam.travel/places-to-go/southern-vietnam/con-dao" target="_blank" rel="noopener">Vietnam.travel Con Dao</a>, and <a href="https://vietnam.travel/things-to-do/phu-quy-vietnam-island-destination" target="_blank" rel="noopener">Vietnam.travel Phu Quy</a> for official destination orientation.</li>
<li>Use <a href="https://www.unesco.org/en/mab/kien-giang" target="_blank" rel="noopener">UNESCO Kien Giang biosphere reserve</a>, <a href="https://www.unesco.org/en/mab/cat-ba" target="_blank" rel="noopener">UNESCO Cat Ba biosphere reserve</a>, <a href="https://condaopark.com.vn/en" target="_blank" rel="noopener">Con Dao National Park</a>, and <a href="http://catbanationalpark.vn/" target="_blank" rel="noopener">Cat Ba National Park</a> for protected-area context.</li>
<li>Use <a href="https://hoianheritage.net/en/news/news/Cu-Lao-Cham-Hoi-An-World-Biosphere-Reserve-14-years-of-conservation-and-development-1216.html" target="_blank" rel="noopener">Hoi An heritage on Cu Lao Cham-Hoi An Biosphere Reserve</a>, <a href="https://quangngai.gov.vn/web/portal-qni/xem-chi-tiet/-/asset_publisher/Content/ly-son-island" target="_blank" rel="noopener">Quang Ngai province on Ly Son</a>, and <a href="https://quangninh.gov.vn/" target="_blank" rel="noopener">Quang Ninh portal</a> for smaller-island local context.</li>
<li>Use <a href="https://cangsaky.com.vn/lich-tau" target="_blank" rel="noopener">Sa Ky port schedules</a>, <a href="https://phuquyexpress.com/" target="_blank" rel="noopener">Phu Quy Express</a>, <a href="https://superdong.com.vn/" target="_blank" rel="noopener">Superdong</a>, and <a href="https://phuquocexpress.com/" target="_blank" rel="noopener">Phu Quoc Express</a> only as live logistics checks, not evergreen schedule evidence.</li>
<li>Use <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> and <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport</a> before locking island-heavy routes.</li>
<li>Read <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a>, and <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a> before paying for an island chapter.</li>
</ul>
<!-- /wp:list -->

<!-- vg-best-islands-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best Islands in Vietnam FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-best-islands-faq">
<details><summary>What is the best island in Vietnam for first-time visitors?</summary><p>Phu Quoc is usually the easiest first-trip island because it works as a resort and beach-rest chapter with simpler flight logic than smaller ferry-led islands.</p></details>
<details><summary>Is Con Dao better than Phu Quoc?</summary><p>Con Dao is better for quiet premium nature and slower travel. Phu Quoc is better for easier resort infrastructure, family logistics, and a smoother beach finish.</p></details>
<details><summary>Is Cat Ba one of the best islands in Vietnam?</summary><p>Yes, but for the right reason. Cat Ba is best as a northern island base for Lan Ha Bay and national park time, not as a pure beach-island substitute.</p></details>
<details><summary>Should I visit Cham Islands from Hoi An?</summary><p>Only when the sea window, boat operation, and central Vietnam schedule support it. Treat Cham Islands as a live-condition add-on, not a guaranteed evergreen day trip.</p></details>
<details><summary>Are Ly Son, Phu Quy, Nam Du, or Co To good for international travelers?</summary><p>They can be excellent for flexible travelers who want local texture and can tolerate ferry friction. They are weaker choices for rushed first trips or travelers who need polished resort certainty.</p></details>
<details><summary>How many islands should I include in one Vietnam trip?</summary><p>Most first trips should include zero or one island chapter. Two islands only make sense on longer, flexible routes where each island solves a different job.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> for the full planning order, compare the broader route shortlist in <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>, then test island value against <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a>. For northern island/bay decisions, use <a href="/destinations/cat-ba-travel-guide/">Cat Ba Travel Guide</a>, <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, and <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>. For central add-ons, cross-check <a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a>, <a href="/compare/hoi-an-vs-hue/">Hoi An vs Hue</a>, and <a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-best-islands-hero:v1',
    'concierge verdict' => 'vg-best-islands-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-best-islands-at-a-glance:v1',
    'photo grid' => 'vg-best-islands-photo-grid:v1',
    'source diversity' => 'vg-best-islands-source-diversity:v1',
    'decision matrix' => 'vg-best-islands-decision-matrix:v1',
    'route fit' => 'vg-best-islands-route-fit:v1',
    'season weather' => 'vg-best-islands-season-weather:v1',
    'logistics' => 'vg-best-islands-logistics:v1',
    'island profiles' => 'vg-best-islands-island-profiles:v1',
    'skip logic' => 'vg-best-islands-skip-logic:v1',
    'booking checks' => 'vg-best-islands-booking-checks:v1',
    'live checks' => 'vg-best-islands-live-checks:v1',
    'FAQ' => 'vg-best-islands-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_best_islands_ops_assert_required_content_markers($content, $required_content_markers);
vg_best_islands_ops_assert_internal_page_links_are_published('Best Islands in Vietnam guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Best Islands in Vietnam',
    'post_name'      => 'best-islands-in-vietnam',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_best_islands_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Best Islands in Vietnam guide for international travelers choosing Phu Quoc, Con Dao, Cat Ba, Cham Islands, Ly Son, Phu Quy, Nam Du, or Co To by route, season, source confidence, and logistics.',
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
    vg_best_islands_ops_fail('Could not publish Best Islands in Vietnam guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_best_islands_ops_fail('Could not publish Best Islands in Vietnam guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Best Islands in Vietnam: Phu Quoc, Con Dao, Cat Ba, and More');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led guide to the best islands in Vietnam: choose Phu Quoc, Con Dao, Cat Ba, Cham Islands, Ly Son, Phu Quy, Nam Du, or Co To by route, season, sources, and logistics.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'best islands in Vietnam');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Choose the best Vietnam island chapter by route job, season, ferry or flight friction, source confidence, and whether the island improves the trip more than another mainland stop.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 19, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Best Islands in Vietnam guide with a concierge verdict, at-a-glance island answer, licensed real photo proof, source-diversity panel, island decision matrix, route-fit table, season/weather table, logistics table, island profiles, skip logic, booking checks, live official/source checks, FAQ, Destinations hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Phu Quoc - https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc - checked July 19, 2026\nVietnam.travel - Con Dao - https://vietnam.travel/places-to-go/southern-vietnam/con-dao - checked July 19, 2026\nVietnam.travel - Phu Quy island destination - https://vietnam.travel/things-to-do/phu-quy-vietnam-island-destination - checked July 19, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 19, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 19, 2026\nUNESCO - Kien Giang biosphere reserve - https://www.unesco.org/en/mab/kien-giang - checked July 19, 2026\nUNESCO - Cat Ba biosphere reserve - https://www.unesco.org/en/mab/cat-ba - checked July 19, 2026\nCat Ba tourism/service information - https://catba.com.vn/ - checked July 19, 2026\nCat Ba National Park - http://catbanationalpark.vn/ - checked July 19, 2026\nCon Dao National Park - https://condaopark.com.vn/en - checked July 19, 2026\nHoi An Heritage - Cu Lao Cham-Hoi An World Biosphere Reserve - https://hoianheritage.net/en/news/news/Cu-Lao-Cham-Hoi-An-World-Biosphere-Reserve-14-years-of-conservation-and-development-1216.html - checked July 19, 2026\nQuang Ngai province - Ly Son Island - https://quangngai.gov.vn/web/portal-qni/xem-chi-tiet/-/asset_publisher/Content/ly-son-island - checked July 19, 2026\nQuang Ninh portal - https://quangninh.gov.vn/ - checked July 19, 2026\nSa Ky port schedule page - https://cangsaky.com.vn/lich-tau - checked July 19, 2026; schedule pages must be rechecked close to travel\nPhu Quy Express - https://phuquyexpress.com/ - checked July 19, 2026; operator schedules must be rechecked close to travel\nSuperdong - https://superdong.com.vn/ - checked July 19, 2026; operator schedules must be rechecked close to travel\nPhu Quoc Express - https://phuquocexpress.com/ - checked July 19, 2026; operator schedules must be rechecked close to travel\nWikimedia Commons image record - Kem Beach aerial view Phu Quoc Island Vietnam - https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg - license checked July 19, 2026\nWikimedia Commons image record - Beach view from Six Senses Resort in Con Dao - https://commons.wikimedia.org/wiki/File:Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_(April_2022).jpg - license checked July 19, 2026\nWikimedia Commons image record - Cat Ba Island - https://commons.wikimedia.org/wiki/File:Cat_Ba_Island.jpg - license checked July 19, 2026\nWikimedia Commons image record - Cu Lao Cham Marine Park, Vietnam - https://commons.wikimedia.org/wiki/File:Cu_Lao_Cham_Marine_Park,_Vietnam.jpg - license checked July 19, 2026\nWikimedia Commons image record - Ly Son3 - https://commons.wikimedia.org/wiki/File:Ly_Son3.jpg - license checked July 19, 2026\nWikimedia Commons image record - Alone front of Co To beach, Viet Nam - https://commons.wikimedia.org/wiki/File:Alone_front_of_Co_To_beach,_Viet_Nam_(160620021).jpg - license checked July 19, 2026\nWikimedia Commons image record - Chualinhsonphuquy - https://commons.wikimedia.org/wiki/File:Chualinhsonphuquy.jpg - license checked July 19, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Vietnam island planning is a route-fit decision: the right island still makes sense after weather, ferries, flights, buffers, source confidence, and the mainland stop it replaces are counted.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Best-islands guide built around island route jobs rather than a generic prettiest-islands ranking.\nConcierge verdict separates Phu Quoc resort ease, Con Dao quiet premium, Cat Ba northern island base, Cham Islands Hoi An add-on, Ly Son volcanic culture, and ferry-led smaller islands.\nAt-a-glance table answers the first useful island question for international travelers.\nLicensed real photo proof for Phu Quoc, Con Dao, Cat Ba, Cham Islands, Ly Son, Co To, and Phu Quy.\nVisible source-diversity panel distinguishes official tourism, UNESCO/biosphere, national park, local/provincial, ferry/operator, and image-license source jobs.\nDecision matrix compares eight island choices by best job, traveler fit, not-ideal cases, and route reality.\nRoute-fit table ties islands to 7, 10, 14, 21-day, central, and northern routes.\nSeason/weather table prevents treating Vietnam islands as one beach season.\nLogistics table highlights the hidden island tax: flights, ferries, ports, buffers, and cancellation terms.\nIsland profiles add original editorial judgment beyond source summaries.\nSkip logic protects the itinerary from collector-style island hopping.\nBooking checks and live checks separate evergreen recommendation from changing schedules and weather.\nVisible source trail, related routes, and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before adding a fragile island chapter.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Compare islands against mainland route value before adding transfers.\nBest Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ | Decide whether the trip needs a beach chapter before choosing an island.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Use region choice to decide whether the island should be northern, central, or southern.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check island season and weather pressure before booking ferries, flights, or resorts.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Price the ferry, port, flight, and transfer logic before adding island time.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget for resort nights, ferry buffers, private transfers, and weather flexibility.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether one island can fit a short route without weakening the core trip.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when a single island chapter can earn its keep in a two-week route.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Decide whether extra time should support a premium island or a smaller flexible island detour.\nCat Ba Travel Guide | /destinations/cat-ba-travel-guide/ | Use Cat Ba when the island base changes the northern bay route.\nHa Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Compare classic bay value against an island-base route.\nHa Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Test northern island and bay value before booking a cruise or Cat Ba chapter.\nBest Things to Do in Hoi An | /destinations/best-things-to-do-in-hoi-an/ | Compare Cham Islands against Hoi An old-town, food, and beach time.\nHoi An vs Hue | /compare/hoi-an-vs-hue/ | Protect central heritage time before adding a central island detour.\nDa Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use Da Nang airport and central-coast access to judge whether a central island add-on is practical.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check water, ferry, activity, interruption, and evacuation coverage before island travel.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair island deposits, water activities, transfers, and cancellation terms with practical risk checks.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Phu Quoc image: Kem Beach aerial view by Vivu Vietnam, CC BY-SA 4.0. Body images: Con Dao beach by Daeva Trac, CC BY-SA 4.0; Cat Ba Island by Christophe95, CC BY-SA 4.0; Cu Lao Cham Marine Park by Kok Leng Yeo, CC BY 2.0; Ly Son3 by BertholdD, CC BY-SA 3.0; Co To beach by Tuan Nguyen, CC BY-SA 3.0; Chualinhsonphuquy by Thai Nhi, Public domain.');
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
        vg_best_islands_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_best_islands_ops_fail('Best Islands in Vietnam guide was updated but is not published.');
}

vg_best_islands_ops_refresh_destinations_hub();
vg_best_islands_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_best_islands_ops_log("Published Best Islands in Vietnam guide: {$page_id} {$updated_permalink}");

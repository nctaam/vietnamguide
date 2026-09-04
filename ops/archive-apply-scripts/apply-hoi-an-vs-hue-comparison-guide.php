<?php
/**
 * Publish the Hoi An vs Hue comparison guide.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-hoi-an-vs-hue-comparison-guide.php --allow-root
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
    $value = getenv('VG_FORCE_HOI_AN_HUE_COMPARISON_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_ops_repair_side_effects_enabled(): bool
{
    $value = getenv('VG_REPAIR_HOI_AN_HUE_COMPARISON_LINKS');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the Hoi An vs Hue guide.');
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

function vg_ops_refresh_compare_hub(): void
{
    $hub = get_page_by_path('compare', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_ops_log('Skipped Compare hub refresh: compare page was not found or is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('compare/hoi-an-vs-hue')) {
        vg_ops_log('Skipped Compare hub refresh: Hoi An vs Hue comparison is not published.');
        return;
    }

    $marker = '<!-- vg-hoi-an-hue-compare-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose the central Vietnam heritage chapter before adding nights</h3><p>The <a href="/compare/hoi-an-vs-hue/">Hoi An vs Hue</a> guide helps travelers compare Hoi An atmosphere, Ancient Town evenings, food, My Son slack, Hue imperial depth, Citadel and tomb context, weather exposure, and the transfer order before the central Vietnam route gets too full.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Compare hub Hoi An/Hue note', $block);
    vg_ops_upsert_marked_group($hub, 'Compare hub Hoi An/Hue note', $marker, $block);
}

function vg_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);

    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);

    if (str_contains($current, '/compare/hoi-an-vs-hue/')) {
        vg_ops_log("Skipped related-route refresh for {$label}: Hoi An vs Hue comparison is already present.");
        return;
    }

    $next = trim($current) === '' ? $route_line : rtrim($current) . "\n" . $route_line;
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_ops_log("Added Hoi An vs Hue comparison related route to {$label}: {$page->ID}");
}

function vg_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Hoi An vs Hue | /compare/hoi-an-vs-hue/ | Decide whether Hoi An atmosphere or Hue imperial depth deserves protected central Vietnam time before locking the route.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
            'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
            'destinations/best-things-to-do-in-hue' => 'Best Things to Do in Hue guide',
            'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
            'compare/da-nang-vs-hoi-an' => 'Da Nang vs Hoi An guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
        ] as $path => $label
    ) {
        vg_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('compare', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: compare');
}

$page = get_page_by_path('compare/hoi-an-vs-hue', OBJECT, 'page');

if ($page instanceof WP_Post && $page->post_status === 'publish' && vg_ops_repair_side_effects_enabled()) {
    vg_ops_log('Repairing Hoi An vs Hue hub and inbound related-route side effects without rewriting the guide.');
    vg_ops_refresh_compare_hub();
    vg_ops_refresh_inbound_related_routes();
    vg_ops_log("Repaired Hoi An vs Hue side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_ops_force_republish_enabled()) {
    vg_ops_fail('Hoi An vs Hue guide is not a draft. Set VG_FORCE_HOI_AN_HUE_COMPARISON_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_ops_log("Preflight Hoi An vs Hue comparison: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_ops_log('Preflight Hoi An vs Hue comparison: no existing page found; creating a child page under /compare/.');
}

$hoi_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hoi_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg');
$japanese_bridge_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/c/cb/Hoi_An%2C_Vietnam%2C_Japanese_Covered_Bridge.jpg');
$japanese_bridge_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hoi_An,_Vietnam,_Japanese_Covered_Bridge.jpg');
$hue_citadel_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg/1920px-Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$hue_citadel_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$thien_mu_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/8/88/Hue_Vietnam_Thien-Mu-Temple-and-Pagoda-03.jpg');
$thien_mu_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Thien-Mu-Temple-and-Pagoda-03.jpg');
$perfume_river_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/8/88/Hue_Vietnam_Perfume-River-01.jpg');
$perfume_river_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Perfume-River-01.jpg');

$content = <<<HTML
<!-- vg-hoi-an-hue-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hoi_an_image}","dimRatio":56,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hoi An Ancient Town in central Vietnam, used for a Hoi An vs Hue comparison guide" src="{$hoi_an_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed comparison guide - Updated July 18, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Hoi An vs Hue</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Hoi An and Hue solve different central Vietnam problems. Hoi An gives atmosphere, old-town evenings, food, cafes, My Son or beach slack, and a softer finish. Hue gives imperial depth, the Citadel, tombs, river rhythm, local food, and a more serious heritage chapter.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: this is not a question of which city is prettier. It is a question of which place deserves protected time after airport pressure, weather, transfer order, and the rest of the route are counted.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hoi_an_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-hoi-an-hue-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-hoi-an-hue-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-hoi-an-hue-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Choose Hoi An when the central Vietnam chapter needs atmosphere, food, slow evenings, and lower-effort charm. Choose Hue when the route needs serious imperial history, a protected heritage day, and a stronger sense of Vietnam beyond the coast.</strong> Keep both only when the itinerary has enough nights to protect Hue's Citadel and tombs without stealing Hoi An's best evening and morning.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-hoi-an-hue-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-hoi-an-hue-shortlist">
<li><strong>Best default for a first central stay:</strong> Hoi An, if the trip only has time for one slower base after Da Nang airport.</li>
<li><strong>Best for heritage depth:</strong> Hue, when the route can protect the Citadel, one royal tomb, Thien Mu, food, and a clean handoff south.</li>
<li><strong>Best two-city sequence:</strong> Hue before Hoi An, then transfer through Da Nang or Hai Van so the trip moves from imperial weight to old-town release.</li>
<li><strong>Best skip rule:</strong> do not add Hue as a lunch stop or Hoi An as one tired night just to complete the central Vietnam map.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-hoi-an-hue-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-hoi-an-hue-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-hoi-an-hue-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fast answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-hoi-an-hue-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Which is better for most first-time visitors?</strong></td><td data-label="Answer">Hoi An is easier to love quickly because the best hours are walkable, atmospheric, and food-led. Hue is better when imperial history deserves a full protected day.</td></tr>
<tr><td data-label="Question"><strong>Which has deeper heritage?</strong></td><td data-label="Answer">Hue has deeper imperial scale and monument context. Hoi An has a more compact trading-port story and stronger evening atmosphere.</td></tr>
<tr><td data-label="Question"><strong>Which is easier with kids or mixed travel styles?</strong></td><td data-label="Answer">Hoi An is usually easier because walks, food, cafes, hotel downtime, and An Bang recovery are simpler to pace. Hue works with private transport, shade, and fewer monuments.</td></tr>
<tr><td data-label="Question"><strong>Should I visit both?</strong></td><td data-label="Answer">Yes with four or more central Vietnam nights, or when a private Hue-to-Hoi An transfer becomes part of the experience. With two or three nights, one place plus Da Nang airport logic is usually cleaner.</td></tr>
<tr><td data-label="Question"><strong>What should I verify before booking?</strong></td><td data-label="Answer">Da Nang airport timing, Hue-Hoi An transfer order, hotel location, heat and rain exposure, monument ticket needs, My Son or beach add-ons, and how the next region begins.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-hoi-an-hue-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what each choice changes</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The images are route evidence. Hoi An asks for protected evening light, food time, and slow walks. Hue asks for monument scale, guide context, shade, and a less rushed transfer day.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-hoi-an-hue-photo-grid" aria-label="Hoi An and Hue comparison photography">
<figure class="vg-guide-photo"><img src="{$hoi_an_image}" alt="Hoi An Ancient Town street scene in central Vietnam" loading="lazy" decoding="async"><figcaption>Hoi An earns the first protected evening and morning. Image: <a href="{$hoi_an_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$japanese_bridge_image}" alt="Japanese Covered Bridge in Hoi An, Vietnam" loading="lazy" decoding="async"><figcaption>Hoi An heritage works best as a slow walk, not a single bridge photo. Image: <a href="{$japanese_bridge_credit_url}" target="_blank" rel="license noopener">rapidacid / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hue_citadel_image}" alt="Historic architecture inside the Hue Citadel in central Vietnam" loading="lazy" decoding="async"><figcaption>The Citadel is Hue's argument for protected time and context. Image: <a href="{$hue_citadel_credit_url}" target="_blank" rel="license noopener">CEphoto, Uwe Aranas / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$thien_mu_image}" alt="Thien Mu Pagoda beside the Perfume River in Hue, Vietnam" loading="lazy" decoding="async"><figcaption>Thien Mu gives Hue the river-side pause that keeps the heritage day humane. Image: <a href="{$thien_mu_credit_url}" target="_blank" rel="license noopener">CEphoto, Uwe Aranas / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$perfume_river_image}" alt="Perfume River in Hue, Vietnam" loading="lazy" decoding="async"><figcaption>The Perfume River explains why Hue should not be treated as a roadside add-on. Image: <a href="{$perfume_river_credit_url}" target="_blank" rel="license noopener">CEphoto, Uwe Aranas / CC BY-SA 3.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-hoi-an-hue-decision-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Decision matrix: which place solves your trip?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this matrix before booking hotels. The right answer changes when you measure pace, transfer pressure, weather, family needs, and whether the route needs atmosphere or historical depth.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hoi-an-hue-decision-matrix">
<thead><tr><th>Decision lens</th><th>Hoi An is stronger when...</th><th>Hue is stronger when...</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Decision lens">First emotional payoff</td><td data-label="Hoi An is stronger when...">You want lantern evenings, walkability, food, cafes, and a softer central Vietnam base.</td><td data-label="Hue is stronger when...">You want the trip to feel more historical and less beach-town-led.</td><td data-label="VietnamGuide verdict">Hoi An wins easy atmosphere; Hue wins depth.</td></tr>
<tr><td data-label="Decision lens">Heritage value</td><td data-label="Hoi An is stronger when...">A compact UNESCO trading-port walk and My Son side trip are enough.</td><td data-label="Hue is stronger when...">The Citadel, royal tombs, pagodas, and imperial city context matter.</td><td data-label="VietnamGuide verdict">Hue needs more time but gives a bigger heritage arc.</td></tr>
<tr><td data-label="Decision lens">Food and slow time</td><td data-label="Hoi An is stronger when...">Cafes, markets, cooking classes, snacks, and dinner walks are the goal.</td><td data-label="Hue is stronger when...">You want a distinctive food city threaded through a heritage day.</td><td data-label="VietnamGuide verdict">Hoi An is easier; Hue is more rewarding when planned.</td></tr>
<tr><td data-label="Decision lens">Family and mixed groups</td><td data-label="Hoi An is stronger when...">Short walks, hotel downtime, beach recovery, and simple evenings matter.</td><td data-label="Hue is stronger when...">A private car, a guide, and selective monument choices keep the day comfortable.</td><td data-label="VietnamGuide verdict">Hoi An is the lower-friction default.</td></tr>
<tr><td data-label="Decision lens">Weather and heat</td><td data-label="Hoi An is stronger when...">You can move between town, cafes, hotel, and beach with flexible timing.</td><td data-label="Hue is stronger when...">You can start early, use shade, and avoid exposed monument walking in the harshest hours.</td><td data-label="VietnamGuide verdict">Both need central-weather checks; Hue punishes weak timing more.</td></tr>
<tr><td data-label="Decision lens">Premium feel</td><td data-label="Hoi An is stronger when...">The premium value is a calmer base, dining, spa, pool, old-town access, or An Bang recovery.</td><td data-label="Hue is stronger when...">The premium value is a guide, private transfer, better pacing, and a serious heritage day.</td><td data-label="VietnamGuide verdict">Spend where the city is strongest, not where the hotel listing is prettiest.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hoi-an-hue-base-choice:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Base choice: one city, both cities, or Da Nang support?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Central Vietnam works best when the sleeping base has a job. Hoi An, Hue, and Da Nang can all be correct, but each one solves a different weakness in the route.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hoi-an-hue-base-choice">
<thead><tr><th>Base pattern</th><th>Use it when</th><th>Protect this</th><th>Do not do this</th></tr></thead>
<tbody>
<tr><td data-label="Base pattern">Hoi An only</td><td data-label="Use it when">You have two or three central nights and want atmosphere, food, and low-friction evenings.</td><td data-label="Protect this">Ancient Town after dark, heritage morning, one food or cafe block, and transfer slack.</td><td data-label="Do not do this">Force Hue into the weakest half-day just because it is famous.</td></tr>
<tr><td data-label="Base pattern">Hue only</td><td data-label="Use it when">Imperial history is the central reason for the stop and the route can handle a more serious day.</td><td data-label="Protect this">Citadel, one tomb, Thien Mu or river, food, and clean onward transport.</td><td data-label="Do not do this">Treat Hoi An as a late-night drive-by after monuments.</td></tr>
<tr><td data-label="Base pattern">Hue then Hoi An</td><td data-label="Use it when">You have four or more central nights or want a strong heritage-to-old-town sequence.</td><td data-label="Protect this">A real Hue day, a scenic or efficient transfer, and the first Hoi An evening.</td><td data-label="Do not do this">Stack tombs, Hai Van stops, Da Nang, and Hoi An into one exhausted day.</td></tr>
<tr><td data-label="Base pattern">Hoi An then Hue</td><td data-label="Use it when">Flights, hotels, or weather make the reverse direction cleaner.</td><td data-label="Protect this">Departure logic and enough attention for Hue after Hoi An's softer rhythm.</td><td data-label="Do not do this">End central Vietnam with a rushed monument day before a fragile flight.</td></tr>
<tr><td data-label="Base pattern">Da Nang support night</td><td data-label="Use it when">Early flights, late arrivals, beach hotels, or airport access would make Hoi An or Hue feel forced.</td><td data-label="Protect this">Airport timing and the central chapter's best hours.</td><td data-label="Do not do this">Add a third sleeping base without a clear gain.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hoi-an-hue-heritage-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Heritage fit: atmosphere versus imperial depth</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Hoi An's heritage is compact, social, and best read through streets, houses, bridge, assembly halls, food, and evening light. Hue's heritage is larger, more formal, and easier to under-value if the route gives it only a transfer window.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hoi-an-hue-heritage-fit">
<thead><tr><th>Traveler type</th><th>Choose Hoi An when...</th><th>Choose Hue when...</th><th>Best move</th></tr></thead>
<tbody>
<tr><td data-label="Traveler type">First-time couple</td><td data-label="Choose Hoi An when...">Evening atmosphere, dining, and a slower base matter most.</td><td data-label="Choose Hue when...">History is a shared priority and a guide would improve the trip.</td><td data-label="Best move">Hoi An default; add Hue only with enough nights.</td></tr>
<tr><td data-label="Traveler type">History-led traveler</td><td data-label="Choose Hoi An when...">The trip also includes My Son and enough reading or guide context.</td><td data-label="Choose Hue when...">Imperial Vietnam, tomb landscapes, Citadel scale, and pagoda context are the point.</td><td data-label="Best move">Hue first, Hoi An second if time remains.</td></tr>
<tr><td data-label="Traveler type">Food-focused traveler</td><td data-label="Choose Hoi An when...">Food, cafes, markets, and soft evenings should structure the stay.</td><td data-label="Choose Hue when...">You want a distinctive food city linked to the heritage day.</td><td data-label="Best move">Choose the city where meals will not be squeezed around transfers.</td></tr>
<tr><td data-label="Traveler type">Family route</td><td data-label="Choose Hoi An when...">Short walks, beach or pool breaks, and predictable evenings matter.</td><td data-label="Choose Hue when...">You can afford private transport, shade, and a selective monument plan.</td><td data-label="Best move">Avoid too many exposed stops in either city.</td></tr>
<tr><td data-label="Traveler type">Luxury traveler</td><td data-label="Choose Hoi An when...">A calm hotel, old-town access, dining, spa, and An Bang recovery are the premium value.</td><td data-label="Choose Hue when...">A private guide, better timing, and deep context create the premium value.</td><td data-label="Best move">Buy fewer, better-timed experiences.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hoi-an-hue-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route fit by trip length</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The strongest central Vietnam plan depends on total trip length. A ten-day route cannot use central Vietnam the same way a three-week route can.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hoi-an-hue-route-fit">
<thead><tr><th>Trip shape</th><th>Best central choice</th><th>Why it works</th><th>Cut first</th></tr></thead>
<tbody>
<tr><td data-label="Trip shape">7 to 10 days</td><td data-label="Best central choice">Usually Hoi An or Da Nang plus Hoi An.</td><td data-label="Why it works">Short trips need one memorable central chapter and fewer hotel moves.</td><td data-label="Cut first">A rushed Hue add-on that weakens both north and central Vietnam.</td></tr>
<tr><td data-label="Trip shape">10 to 14 days</td><td data-label="Best central choice">Hoi An plus a deliberate Hue night if heritage is a priority.</td><td data-label="Why it works">There may be enough time for a real Citadel day and Hoi An evening.</td><td data-label="Cut first">Extra Da Nang filler, duplicate beach time, or weak half-day tours.</td></tr>
<tr><td data-label="Trip shape">14 to 21 days</td><td data-label="Best central choice">Hue then Hoi An, with Da Nang supporting airport or beach logic.</td><td data-label="Why it works">Longer routes can turn the central coast into a real middle chapter.</td><td data-label="Cut first">An extra base that only repeats the same experience.</td></tr>
<tr><td data-label="Trip shape">Heritage-first route</td><td data-label="Best central choice">Hue and Hoi An both, ideally with a guide or source-led planning.</td><td data-label="Why it works">Hue gives imperial depth; Hoi An gives trading-port atmosphere and My Son access.</td><td data-label="Cut first">Theme parks and thin beach add-ons.</td></tr>
<tr><td data-label="Trip shape">Beach and recovery route</td><td data-label="Best central choice">Hoi An with An Bang or Da Nang beach support.</td><td data-label="Why it works">The route needs rest, food, and soft evenings more than monument density.</td><td data-label="Cut first">Hue if no one in the group wants a serious heritage day.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hoi-an-hue-transfer-pressure:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Transfer pressure: where the hidden cost sits</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Hoi An and Hue both usually rely on Da Nang logistics. The mistake is treating the transfer as a thin line on the map instead of a day that can help or harm the route.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hoi-an-hue-transfer-pressure">
<thead><tr><th>Friction point</th><th>What to verify</th><th>Why it changes the answer</th></tr></thead>
<tbody>
<tr><td data-label="Friction point">Da Nang airport timing</td><td data-label="What to verify">Arrival time, immigration buffer, checked bags, hotel transfer, and next-day energy.</td><td data-label="Why it changes the answer">Late arrivals often make Da Nang or Hoi An easier than starting with Hue.</td></tr>
<tr><td data-label="Friction point">Hue to Hoi An transfer</td><td data-label="What to verify">Private car, train plus road transfer, Hai Van Pass route, luggage, stops, and weather.</td><td data-label="Why it changes the answer">A good transfer can make both cities elegant; a rushed one can flatten both.</td></tr>
<tr><td data-label="Friction point">Hotel location</td><td data-label="What to verify">Old-town access in Hoi An, Citadel/river access in Hue, and whether taxis become constant.</td><td data-label="Why it changes the answer">The best city choice can fail if the hotel makes its best hours hard to use.</td></tr>
<tr><td data-label="Friction point">Tickets and guide needs</td><td data-label="What to verify">Hue monument ticket rules, guide availability, opening changes, and restoration access close to travel.</td><td data-label="Why it changes the answer">Hue gains value with context but loses value when practical access is assumed.</td></tr>
<tr><td data-label="Friction point">Children, luggage, heat</td><td data-label="What to verify">Vehicle quality, shade, breaks, pool time, and whether the travel day is overloaded.</td><td data-label="Why it changes the answer">Family and premium routes should buy comfort by removing weak stops, not adding more.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hoi-an-hue-season-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Season and weather pivots</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Central Vietnam weather changes the value of both cities. Hoi An is easier to bend around cafe, food, hotel, and beach recovery. Hue needs more care because monuments, tombs, and river plans are exposed to heat and rain.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hoi-an-hue-season-weather">
<thead><tr><th>Travel window</th><th>Hoi An bias</th><th>Hue bias</th><th>Route note</th></tr></thead>
<tbody>
<tr><td data-label="Travel window">March to May</td><td data-label="Hoi An bias">Strong for old town, food, My Son, countryside, and beach recovery.</td><td data-label="Hue bias">Strong for Citadel, tombs, river, and the scenic central transfer.</td><td data-label="Route note">This is the best window to keep both if the itinerary has enough nights.</td></tr>
<tr><td data-label="Travel window">June to August</td><td data-label="Hoi An bias">Use early walks, shaded cafes, pool, beach, and lighter afternoons.</td><td data-label="Hue bias">Start monuments early, use a car, reduce tomb count, and protect lunch breaks.</td><td data-label="Route note">Heat makes fewer stops feel more premium.</td></tr>
<tr><td data-label="Travel window">September to November</td><td data-label="Hoi An bias">Keep plans flexible around rain, storm risk, and beach uncertainty.</td><td data-label="Hue bias">Use live weather checks before river plans, tombs, or scenic road routing.</td><td data-label="Route note">Refundable bookings and slack matter more than checklist coverage.</td></tr>
<tr><td data-label="Travel window">December to February</td><td data-label="Hoi An bias">Lean into atmosphere, food, cafes, tailoring, and softer beach expectations.</td><td data-label="Hue bias">Cooler heritage walking can be excellent when rain is manageable.</td><td data-label="Route note">Do not force a summer beach script onto a cooler central-coast trip.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hoi-an-hue-keep-both:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">When to keep both Hoi An and Hue</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Keeping both is the premium answer only when the route can give each city its real job. Hue should not be reduced to a photo stop; Hoi An should not be reduced to one exhausted dinner.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hoi-an-hue-keep-both">
<thead><tr><th>Keep both when...</th><th>Minimum shape</th><th>What each city does</th><th>Warning sign</th></tr></thead>
<tbody>
<tr><td data-label="Keep both when...">The trip has four central nights</td><td data-label="Minimum shape">Two nights Hue plus two nights Hoi An, or one Hue night plus three Hoi An nights with a selective Hue day.</td><td data-label="What each city does">Hue supplies imperial context; Hoi An supplies atmosphere and recovery.</td><td data-label="Warning sign">Every day starts with luggage or an early pickup.</td></tr>
<tr><td data-label="Keep both when...">Heritage is the central theme</td><td data-label="Minimum shape">Hue Citadel and tombs, Hoi An Ancient Town, and possibly My Son if the route breathes.</td><td data-label="What each city does">Hue gives royal scale; Hoi An and My Son add trading-port and Champa context.</td><td data-label="Warning sign">You are adding heritage labels without guide time or reading space.</td></tr>
<tr><td data-label="Keep both when...">A private transfer can carry value</td><td data-label="Minimum shape">A timed Hue-to-Hoi An or Hoi An-to-Hue transfer that does not steal the best city hours.</td><td data-label="What each city does">The transfer becomes the bridge, not the reason the day collapses.</td><td data-label="Warning sign">The transfer day also contains too many city stops.</td></tr>
<tr><td data-label="Keep both when...">The group wants contrast</td><td data-label="Minimum shape">One serious history day and one slower food or old-town day.</td><td data-label="What each city does">Hue deepens; Hoi An softens.</td><td data-label="Warning sign">Half the group wants beach recovery while the plan keeps adding monuments.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hoi-an-hue-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Skipping is what makes the central chapter feel intentional. Cut the thing that weakens the best hours before cutting the city that gives the route its identity.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-hoi-an-hue-skip-logic">
<thead><tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr></thead>
<tbody>
<tr><td data-label="If you want...">Classic first central Vietnam</td><td data-label="Protect this">One Hoi An evening, one Hoi An morning, and clean Da Nang airport logic.</td><td data-label="Skip first">Hue as a rushed half-day.</td><td data-label="Only add back when...">You can give Hue one real heritage block.</td></tr>
<tr><td data-label="If you want...">Serious heritage</td><td data-label="Protect this">Hue Citadel, one tomb, Thien Mu or river, Hoi An Ancient Town, and source-led context.</td><td data-label="Skip first">Beach filler and duplicate casual tours.</td><td data-label="Only add back when...">The heritage days still have shade, meals, and recovery.</td></tr>
<tr><td data-label="If you want...">Lower-stress family travel</td><td data-label="Protect this">Short walks, private transfers, hotel downtime, predictable meals, and fewer exposed stops.</td><td data-label="Skip first">Multiple tombs, long midday walks, and unnecessary hotel changes.</td><td data-label="Only add back when...">The day still works if energy drops.</td></tr>
<tr><td data-label="If you want...">Food and atmosphere</td><td data-label="Protect this">Hoi An dinner walks, cafes, markets, and a slower second evening.</td><td data-label="Skip first">A monument-heavy day that steals appetite and attention.</td><td data-label="Only add back when...">Food is a real block, not what remains after transport.</td></tr>
<tr><td data-label="If you want...">A premium route</td><td data-label="Protect this">The best hours in each chosen city and one clean transfer.</td><td data-label="Skip first">Any city added only because it appears on every list.</td><td data-label="Only add back when...">It changes the route enough to earn the friction.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-hoi-an-hue-booking-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Booking and official checks before you lock it</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use official and primary sources for framing, then verify live tickets, access, weather, transfer timing, and hotel location close to travel. This page is built to give durable judgment, not to replace same-week checks.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-hoi-an-hue-booking-checks"} -->
<ul class="wp-block-list vg-check-list vg-hoi-an-hue-booking-checks">
<li>Destination frame: start with <a href="https://vietnam.travel/places-to-go/central-vietnam/hoi-an" target="_blank" rel="noopener">Vietnam.travel Hoi An</a>, <a href="https://vietnam.travel/places-to-go/central-vietnam/hue" target="_blank" rel="noopener">Vietnam.travel Hue</a>, and <a href="https://vietnam.travel/places-to-go/central-vietnam" target="_blank" rel="noopener">Vietnam.travel Central Vietnam</a> before choosing the central chapter.</li>
<li>Heritage context: use the <a href="https://whc.unesco.org/en/list/948/" target="_blank" rel="noopener">UNESCO Hoi An Ancient Town listing</a> and the <a href="https://whc.unesco.org/en/list/678/" target="_blank" rel="noopener">UNESCO Complex of Hue Monuments listing</a> before reducing either place to a photo stop.</li>
<li>Hue access checks: use the <a href="https://hueworldheritage.org.vn/en-us/Tourism-information/Price" target="_blank" rel="noopener">Hue Monuments Conservation Centre tourism information and price page</a> close to travel for ticket and access context.</li>
<li>Weather and transfer checks: compare <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> with <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport within Vietnam</a>, then use <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a> and <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before paying for transfers or fixed tours.</li>
<li>Internal route checks: pair this guide with <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>, <a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a>, <a href="/destinations/best-things-to-do-in-hue/">Best Things to Do in Hue</a>, <a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a>, <a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a>.</li>
</ul>
<!-- /wp:list -->

<!-- vg-hoi-an-hue-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hoi An vs Hue FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-hoi-an-hue-faq">
<details><summary>Is Hoi An or Hue better for first-time visitors?</summary><p>Hoi An is usually easier for most first-time visitors because its best hours are walkable, atmospheric, and food-led. Hue is better when imperial history and a serious heritage day matter enough to protect time for the Citadel, tombs, river, and local food.</p></details>
<details><summary>Can I visit both Hoi An and Hue?</summary><p>Yes, but both cities need protected time. Keep both when the central Vietnam chapter has at least four nights, or when the transfer between them becomes a deliberate travel day rather than a squeezed commute.</p></details>
<details><summary>Should I do Hue before Hoi An?</summary><p>Hue before Hoi An is the cleaner default for many north-to-south routes because the trip moves from imperial depth to old-town atmosphere. Reverse the order when flights, hotels, or weather make that direction easier.</p></details>
<details><summary>Is Hue worth it as a day trip from Hoi An?</summary><p>It can work only as a selective private-transfer day, but it is often too thin for travelers who care about Hue's history. If the route cannot protect the Citadel, one tomb or pagoda, food, and transfer margin, choose Hoi An and skip Hue without guilt.</p></details>
<details><summary>Which is better in rainy season?</summary><p>Neither city is automatically better in poor central Vietnam weather. Hoi An is easier to bend toward cafes, food, and hotel downtime; Hue needs careful live checks for exposed monuments, river plans, and road transfers.</p></details>
<details><summary>Which should luxury travelers choose?</summary><p>Choose Hoi An when premium means a calm hotel, old-town access, food, spa, and beach recovery. Choose Hue when premium means a knowledgeable guide, private timing, fewer stops, and a deeper heritage day.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then test central Vietnam against <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>, <a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a>, <a href="/destinations/best-things-to-do-in-hue/">Best Things to Do in Hue</a>, <a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a>, <a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-hoi-an-hue-hero:v1',
    'concierge verdict' => 'vg-hoi-an-hue-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-hoi-an-hue-at-a-glance:v1',
    'photo grid' => 'vg-hoi-an-hue-photo-grid:v1',
    'decision matrix' => 'vg-hoi-an-hue-decision-matrix:v1',
    'base choice' => 'vg-hoi-an-hue-base-choice:v1',
    'heritage fit' => 'vg-hoi-an-hue-heritage-fit:v1',
    'route fit' => 'vg-hoi-an-hue-route-fit:v1',
    'transfer pressure' => 'vg-hoi-an-hue-transfer-pressure:v1',
    'season weather' => 'vg-hoi-an-hue-season-weather:v1',
    'keep both' => 'vg-hoi-an-hue-keep-both:v1',
    'skip logic' => 'vg-hoi-an-hue-skip-logic:v1',
    'booking checks' => 'vg-hoi-an-hue-booking-checks:v1',
    'FAQ' => 'vg-hoi-an-hue-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('Hoi An vs Hue guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Hoi An vs Hue',
    'post_name'      => 'hoi-an-vs-hue',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Hoi An vs Hue comparison for international visitors choosing the right central Vietnam heritage chapter by atmosphere, imperial depth, route fit, transfer pressure, season, and skip logic.',
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
    vg_ops_fail('Could not publish Hoi An vs Hue guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Hoi An vs Hue guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Hoi An vs Hue: Which Central Vietnam Heritage Stop Is Better?');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Hoi An vs Hue comparison: choose by atmosphere, imperial depth, food, family fit, transfer order, weather, route length, and whether to keep both.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Hoi An vs Hue');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Hoi An atmosphere or Hue imperial depth deserves protected central Vietnam time before booking hotels, transfers, tours, or a route that tries to keep both.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 18, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Hoi An vs Hue comparison guide with a concierge verdict, at-a-glance table, licensed photo proof, decision matrix, base-choice table, heritage-fit logic, route-fit table, transfer pressure checks, season and weather pivots, keep-both rules, skip logic, booking checks, FAQ, Compare hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Hoi An destination page - https://vietnam.travel/places-to-go/central-vietnam/hoi-an - checked July 18, 2026\nVietnam.travel - Hue destination page - https://vietnam.travel/places-to-go/central-vietnam/hue - checked July 18, 2026\nVietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked July 18, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 18, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 18, 2026\nUNESCO World Heritage Centre - Hoi An Ancient Town - https://whc.unesco.org/en/list/948/ - checked July 18, 2026\nUNESCO World Heritage Centre - Complex of Hue Monuments - https://whc.unesco.org/en/list/678/ - checked July 18, 2026\nHue Monuments Conservation Centre - Tourism information and ticket price reference - https://hueworldheritage.org.vn/en-us/Tourism-information/Price - checked July 18, 2026\nWikimedia Commons image record - Hoi An Ancient Town - https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg - license checked July 18, 2026\nWikimedia Commons image record - Hoi An, Vietnam, Japanese Covered Bridge - https://commons.wikimedia.org/wiki/File:Hoi_An,_Vietnam,_Japanese_Covered_Bridge.jpg - license checked July 18, 2026\nWikimedia Commons image record - Hue Vietnam Citadel - https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg - license checked July 18, 2026\nWikimedia Commons image record - Hue Vietnam Thien Mu Temple and Pagoda - https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Thien-Mu-Temple-and-Pagoda-03.jpg - license checked July 18, 2026\nWikimedia Commons image record - Hue Vietnam Perfume River - https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Perfume-River-01.jpg - license checked July 18, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Hoi An vs Hue is a central Vietnam time-allocation decision: Hoi An protects atmosphere, food, old-town evenings, and recovery; Hue protects imperial depth, context, and a serious heritage day.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Comparison guide built around an actual central Vietnam planning decision rather than a rewritten city-versus-city listicle.\nConcierge verdict separates Hoi An's atmosphere, food, Ancient Town evenings, and recovery value from Hue's imperial depth, Citadel, tombs, river, and guide value.\nAt-a-glance table answers the first booking questions for international travelers.\nLicensed photo proof with visible credits for Hoi An Ancient Town, Japanese Covered Bridge, Hue Citadel, Thien Mu Pagoda, and Perfume River.\nDecision matrix covers first emotional payoff, heritage value, food and slow time, family fit, heat/rain exposure, and premium feel.\nBase-choice table compares Hoi An only, Hue only, both-city order, and Da Nang support-night logic.\nHeritage-fit table turns traveler type into practical selection rather than generic attractions ranking.\nRoute-fit table ties the decision to 7 to 10 days, 10 to 14 days, 14 to 21 days, heritage-first trips, and beach/recovery trips.\nTransfer pressure checks cover Da Nang airport timing, Hue-Hoi An handoff, hotel location, ticket/guide needs, children, luggage, and heat.\nSeason pivots use official Vietnam weather framing and avoid stale same-week claims.\nKeep-both and skip-logic tables explain when to protect both cities and when to cut one cleanly.\nBooking checks tie the guide to Vietnam.travel, UNESCO, Hue Monuments Conservation Centre, weather, transport, cost, itinerary, and destination guides.\nVisible source trail and update log.");
$related_routes = "Hoi An vs Hue | /compare/hoi-an-vs-hue/ | Decide whether Hoi An atmosphere or Hue imperial depth deserves protected central Vietnam time before locking the route.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before choosing the central Vietnam chapter.\nDa Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Decide the airport, beach, and old-town base before comparing Hoi An with Hue.\nBest Things to Do in Hoi An | /destinations/best-things-to-do-in-hoi-an/ | Use this when Hoi An atmosphere, food, My Son, or An Bang recovery might lead the central stay.\nBest Things to Do in Hue | /destinations/best-things-to-do-in-hue/ | Use this when Hue's Citadel, tombs, river, and food need protected time.\nDa Nang Travel Guide | /destinations/da-nang-travel-guide/ | Check whether Da Nang should support the airport, beach, or transfer side of the decision.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Compare Hoi An and Hue within the broader heritage route.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm central Vietnam's role before adding more stops.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check central Vietnam heat, rain, and storm pressure before locking tours.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Confirm flight, train, road, and private-transfer logic before choosing the city order.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price guides, private cars, hotels, extra nights, and weather buffers before booking.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a short route can support one central chapter without becoming thin.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when two weeks can hold both Hoi An and Hue without losing route shape.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use three weeks to keep central Vietnam deeper without adding weak filler.";
vg_ops_assert_related_route_meta_links_are_published('Hoi An vs Hue guide related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Hoi An Ancient Town image: Hoi An, Ancient Town, 2020-01 CN-11.jpg by Steffen Schmitz, CC BY-SA 4.0. Body images: Japanese Covered Bridge by rapidacid, CC BY 2.0; Hue Citadel, Thien Mu Pagoda, and Perfume River by CEphoto, Uwe Aranas, CC BY-SA 3.0.');
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
    vg_ops_fail('Hoi An vs Hue guide was updated but is not published.');
}

vg_ops_refresh_compare_hub();
vg_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Hoi An vs Hue guide: {$page_id} {$updated_permalink}");

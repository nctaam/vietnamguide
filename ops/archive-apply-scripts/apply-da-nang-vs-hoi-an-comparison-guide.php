<?php
/**
 * Publish the Da Nang vs Hoi An comparison guide.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-da-nang-vs-hoi-an-comparison-guide.php --allow-root
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
    $value = getenv('VG_FORCE_DA_NANG_HOI_AN_COMPARISON_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_ops_repair_side_effects_enabled(): bool
{
    $value = getenv('VG_REPAIR_DA_NANG_HOI_AN_COMPARISON_LINKS');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the Da Nang vs Hoi An guide.');
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

    if (! vg_ops_published_page_exists('compare/da-nang-vs-hoi-an')) {
        vg_ops_log('Skipped Compare hub refresh: Da Nang vs Hoi An comparison is not published.');
        return;
    }

    $marker = '<!-- vg-da-nang-hoi-an-compare-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose the central Vietnam base before choosing hotels</h3><p>The <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a> guide helps travelers compare airport convenience, My Khe beach, Hoi An Ancient Town, An Bang, food, weather, transfer friction, and split-base logic before central Vietnam bookings harden.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Compare hub Da Nang/Hoi An note', $block);
    vg_ops_upsert_marked_group($hub, 'Compare hub Da Nang/Hoi An note', $marker, $block);
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

    if (str_contains($current, '/compare/da-nang-vs-hoi-an/')) {
        vg_ops_log("Skipped related-route refresh for {$label}: Da Nang vs Hoi An comparison is already present.");
        return;
    }

    $next = trim($current) === '' ? $route_line : rtrim($current) . "\n" . $route_line;
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_ops_log("Added Da Nang vs Hoi An comparison related route to {$label}: {$page->ID}");
}

function vg_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Da Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Choose the central Vietnam base by airport friction, beach value, Ancient Town rhythm, weather, and transfer load before booking hotels.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
            'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
            'destinations/best-things-to-do-in-hue' => 'Best Things to Do in Hue guide',
            'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ] as $path => $label
    ) {
        vg_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('compare', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: compare');
}

$page = get_page_by_path('compare/da-nang-vs-hoi-an', OBJECT, 'page');

if ($page instanceof WP_Post && $page->post_status === 'publish' && vg_ops_repair_side_effects_enabled()) {
    vg_ops_log('Repairing Da Nang vs Hoi An hub and inbound related-route side effects without rewriting the guide.');
    vg_ops_refresh_compare_hub();
    vg_ops_refresh_inbound_related_routes();
    vg_ops_log("Repaired Da Nang vs Hoi An side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_ops_force_republish_enabled()) {
    vg_ops_fail('Da Nang vs Hoi An guide is not a draft. Set VG_FORCE_DA_NANG_HOI_AN_COMPARISON_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_ops_log("Preflight Da Nang vs Hoi An comparison: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_ops_log('Preflight Da Nang vs Hoi An comparison: no existing page found; creating a child page under /compare/.');
}

$my_khe_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/a/af/My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg/1920px-My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg');
$my_khe_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg');
$dragon_bridge_image = esc_url('https://commons.wikimedia.org/wiki/Special:FilePath/Dragon_bridge_from_above.png');
$dragon_bridge_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Dragon_bridge_from_above.png');
$hai_van_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1920px-Vietnam%2C_Hai-Van-Pass.jpg');
$hai_van_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg');
$hoi_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hoi_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg');
$an_bang_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b0/2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg/1920px-2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg');
$an_bang_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg');

$content = <<<HTML
<!-- vg-da-nang-hoi-an-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$my_khe_image}","dimRatio":55,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="My Khe Beach in Da Nang, Vietnam, used for a Da Nang vs Hoi An comparison guide" src="{$my_khe_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed comparison guide - Updated July 18, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Da Nang vs Hoi An</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Da Nang and Hoi An are one central Vietnam decision with two different trip rhythms. Da Nang gives the airport, beach hotels, city scale, and cleaner logistics. Hoi An gives old-town evenings, food, heritage texture, and a slower base that can make the middle of the route feel more intentional.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: the better base is the one that protects your best central Vietnam hours after airport timing, beach expectations, Ancient Town evenings, heat, rain, and the next transfer are counted.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$my_khe_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-da-nang-hoi-an-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-da-nang-hoi-an-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-da-nang-hoi-an-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Choose Da Nang if beach, airport convenience, resorts, early flights, or a cleaner transfer spine matter most. Choose Hoi An if the trip needs old-town evenings, food, heritage, cafes, and a slower central chapter.</strong> Split the base only when you have enough nights to make the move improve the trip rather than consume the slack.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-da-nang-hoi-an-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-da-nang-hoi-an-shortlist">
<li><strong>Best airport-and-beach base:</strong> Da Nang, especially for short stays, families, resort travelers, and early or late flights.</li>
<li><strong>Best atmosphere base:</strong> Hoi An, especially when Ancient Town after dark, food, cafes, tailoring, My Son, or An Bang recovery are the point.</li>
<li><strong>Best split-base rule:</strong> split only with four or more central Vietnam nights, a clear beach/resort reason, or a flight time that genuinely rewards the move.</li>
<li><strong>Best skip rule:</strong> do not move hotels just to say you slept in both places; choose one base and buy the other as a clean day or evening block.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-da-nang-hoi-an-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-da-nang-hoi-an-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-da-nang-hoi-an-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fast answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-da-nang-hoi-an-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Which is better for first-time visitors?</strong></td><td data-label="Answer">Hoi An is usually more memorable if you only measure atmosphere. Da Nang is usually easier if you measure airport, beach hotel, and transfer simplicity.</td></tr>
<tr><td data-label="Question"><strong>Where should I stay for the beach?</strong></td><td data-label="Answer">Da Nang/My Khe is the cleaner beach base. Hoi An/An Bang works when beach time is a recovery valve beside old-town days, not the main resort holiday.</td></tr>
<tr><td data-label="Question"><strong>Where should I stay with kids?</strong></td><td data-label="Answer">Da Nang is often easier because hotels, roads, beach space, airport access, and resort infrastructure reduce friction. Hoi An works when short walks and slower evenings matter more.</td></tr>
<tr><td data-label="Question"><strong>Should I split bases?</strong></td><td data-label="Answer">Only if the stay is long enough. With two or three central nights, one base plus deliberate day/evening trips is usually cleaner.</td></tr>
<tr><td data-label="Question"><strong>What should I verify before booking?</strong></td><td data-label="Answer">Airport time, exact beach stretch, Ancient Town access, hotel location, storm/rain season, road transfer, luggage friction, and whether the next move leaves from Da Nang.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-da-nang-hoi-an-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what changes between the bases</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The images are not decoration. They show the central Vietnam trade-off: Da Nang's open beach and city infrastructure, the Hai Van transfer chapter, Hoi An's old-town rhythm, and An Bang as a softer beach add-on.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-da-nang-hoi-an-photo-grid" aria-label="Da Nang and Hoi An comparison photography">
<figure class="vg-guide-photo"><img src="{$my_khe_image}" alt="My Khe Beach in Da Nang seen from Son Tra Mountain" loading="lazy" decoding="async"><figcaption>Da Nang is the cleanest central-coast beach and airport base when logistics matter. Image: <a href="{$my_khe_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$dragon_bridge_image}" alt="Dragon Bridge in Da Nang viewed from above" loading="lazy" decoding="async"><figcaption>Da Nang adds city scale, bridges, restaurants, and easier movement around the coast. Image: <a href="{$dragon_bridge_credit_url}" target="_blank" rel="license noopener">Supanut Arunoprayote / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hai_van_image}" alt="Hai Van Pass coastal road between Hue and Da Nang" loading="lazy" decoding="async"><figcaption>Hai Van Pass can turn a transfer into route value if timing and weather cooperate. Image: <a href="{$hai_van_credit_url}" target="_blank" rel="license noopener">Wolkenkratzer / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hoi_an_image}" alt="Hoi An Ancient Town street scene in central Vietnam" loading="lazy" decoding="async"><figcaption>Hoi An earns protected evenings and slow mornings when the route can breathe. Image: <a href="{$hoi_an_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$an_bang_image}" alt="An Bang Beach in Hoi An, Vietnam" loading="lazy" decoding="async"><figcaption>An Bang works best as Hoi An recovery time, not as proof that every beach plan belongs in the route. Image: <a href="{$an_bang_credit_url}" target="_blank" rel="license noopener">Alexkom000 / CC BY 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-da-nang-hoi-an-decision-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Da Nang vs Hoi An decision matrix</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this as a booking filter. The wrong base can turn a beautiful central Vietnam chapter into repeated taxi time, early packing, beach disappointment, or old-town evenings that never happen.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-da-nang-hoi-an-decision-matrix">
<thead>
<tr><th>Decision factor</th><th>Da Nang</th><th>Hoi An</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Decision factor">Airport and onward travel</td><td data-label="Da Nang">Closest to the airport, easier for early flights, late arrivals, and central-coast movement.</td><td data-label="Hoi An">Usually adds a road transfer before or after every flight.</td><td data-label="VietnamGuide verdict">Choose Da Nang when flight timing is fragile or the stay is short.</td></tr>
<tr><td data-label="Decision factor">Beach and resort value</td><td data-label="Da Nang">My Khe and coastal resorts make the beach a real base decision.</td><td data-label="Hoi An">An Bang and nearby beaches work better as a relaxed add-on.</td><td data-label="VietnamGuide verdict">Choose Da Nang for beach-first central Vietnam; choose Hoi An when beach is secondary.</td></tr>
<tr><td data-label="Decision factor">Evening atmosphere</td><td data-label="Da Nang">More city scale, riverfront, restaurants, and nightlife spread.</td><td data-label="Hoi An">Ancient Town after dark is the stronger atmospheric memory.</td><td data-label="VietnamGuide verdict">Choose Hoi An when the evening is the reason for staying central.</td></tr>
<tr><td data-label="Decision factor">Food, cafes, and slow time</td><td data-label="Da Nang">Better for city variety and a less enclosed resort/city rhythm.</td><td data-label="Hoi An">Better for walkable food, cafes, market texture, and slower wandering.</td><td data-label="VietnamGuide verdict">Hoi An rewards protected time; Da Nang rewards easier movement.</td></tr>
<tr><td data-label="Decision factor">Day-trip logic</td><td data-label="Da Nang">Cleaner for Ba Na Hills, Son Tra, Marble Mountains, Hue road timing, and airport logistics.</td><td data-label="Hoi An">Cleaner for Ancient Town, My Son, An Bang, countryside, and cooking/cafe days.</td><td data-label="VietnamGuide verdict">Pick the base closest to the activities you will actually protect.</td></tr>
<tr><td data-label="Decision factor">Premium feel</td><td data-label="Da Nang">Premium usually means resort quality, beach frontage, pools, spa, and easier airport movement.</td><td data-label="Hoi An">Premium usually means boutique stay, old-town access, food, guide quality, and slower pacing.</td><td data-label="VietnamGuide verdict">Do not compare star ratings only; compare what the base lets you do well.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-da-nang-hoi-an-base-chooser:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Choose one base, split base, or day-trip pattern</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The base decision is a friction decision. A hotel move only makes sense when it saves more energy than it costs.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-da-nang-hoi-an-base-chooser">
<thead>
<tr><th>Base pattern</th><th>Best fit</th><th>What improves</th><th>Skip when</th></tr>
</thead>
<tbody>
<tr><td data-label="Base pattern">Stay in Da Nang, visit Hoi An</td><td data-label="Best fit">Two or three central nights, beach-first travelers, early flights, families, and resort stays.</td><td data-label="What improves">Airport access, beach time, luggage simplicity, and flexible day trips.</td><td data-label="Skip when">Ancient Town evenings are the emotional reason for central Vietnam.</td></tr>
<tr><td data-label="Base pattern">Stay in Hoi An, visit Da Nang</td><td data-label="Best fit">Food, heritage, cafes, slower evenings, My Son, An Bang, and boutique stays.</td><td data-label="What improves">Atmosphere, walkability, and less pressure to commute after dinner.</td><td data-label="Skip when">A very early flight, resort beach plan, or family logistics make the transfer annoying.</td></tr>
<tr><td data-label="Base pattern">Split Da Nang and Hoi An</td><td data-label="Best fit">Four or more central nights, a real resort chapter, or a late/early flight that rewards a Da Nang night.</td><td data-label="What improves">Cleaner separation between beach/resort and old-town chapters.</td><td data-label="Skip when">The move creates packing admin during a short stay.</td></tr>
<tr><td data-label="Base pattern">Airport night only in Da Nang</td><td data-label="Best fit">Travelers who want Hoi An but have a late arrival or early departure.</td><td data-label="What improves">Sleep, flight risk, and first/last-day stress.</td><td data-label="Skip when">The flight timing is comfortable and one base remains cleaner.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-da-nang-hoi-an-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route fit: where the central base belongs</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Da Nang and Hoi An should support the whole route, not compete for isolated checkmarks. Use the table to decide what the central chapter is meant to solve.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-da-nang-hoi-an-route-fit">
<thead>
<tr><th>Route context</th><th>Better base move</th><th>What to protect</th><th>Related guide</th></tr>
</thead>
<tbody>
<tr><td data-label="Route context">10 days in Vietnam</td><td data-label="Better base move">Choose one base; do not split unless flight timing forces it.</td><td data-label="What to protect">One strong central chapter and a clean handoff to the next region.</td><td data-label="Related guide"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Route context">14 days in Vietnam</td><td data-label="Better base move">One base plus selected day trips, or a short split if beach/resort time is real.</td><td data-label="What to protect">Enough nights for Hoi An and one central extension to breathe.</td><td data-label="Related guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Route context">Hue plus Hoi An</td><td data-label="Better base move">Use Hai Van transfer value, then choose the Da Nang/Hoi An side that protects the next morning.</td><td data-label="What to protect">Hue's imperial day and Hoi An's evening rhythm.</td><td data-label="Related guide"><a href="/destinations/best-things-to-do-in-hue/">Best Things to Do in Hue</a></td></tr>
<tr><td data-label="Route context">Beach-first central coast</td><td data-label="Better base move">Da Nang or a coastal resort, with Hoi An as a focused evening or food trip.</td><td data-label="What to protect">Beach quality, hotel location, and weather reality.</td><td data-label="Related guide"><a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a></td></tr>
<tr><td data-label="Route context">Heritage and food route</td><td data-label="Better base move">Hoi An, with Da Nang airport/shoreline used only when it improves the route.</td><td data-label="What to protect">Old Town evenings, My Son timing, cafes, market, and slower food blocks.</td><td data-label="Related guide"><a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-da-nang-hoi-an-season-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Season and weather pivots</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Central Vietnam weather is the shared constraint. The base can change the damage: Da Nang gives more hotel/beach infrastructure, while Hoi An gives more walkable indoor, food, and old-town alternatives when beach expectations weaken.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-da-nang-hoi-an-season-weather">
<thead>
<tr><th>Travel window</th><th>Da Nang bias</th><th>Hoi An bias</th><th>Planning move</th></tr>
</thead>
<tbody>
<tr><td data-label="Travel window">March-August</td><td data-label="Da Nang bias">Stronger beach and resort logic, with heat managed by hotel quality and early starts.</td><td data-label="Hoi An bias">Still strong for evenings, food, cafes, and An Bang if heat is respected.</td><td data-label="Planning move">Use the good window to keep days selective, not to overload the base.</td></tr>
<tr><td data-label="Travel window">September-November</td><td data-label="Da Nang bias">Better if you need airport flexibility and larger-hotel backup.</td><td data-label="Hoi An bias">More fragile for low-lying old-town and beach assumptions; keep plans flexible.</td><td data-label="Planning move">Avoid non-refundable outdoor extras and verify storm/rain conditions close to travel.</td></tr>
<tr><td data-label="Travel window">December-February</td><td data-label="Da Nang bias">City, food, and resort comfort can matter more than pure swimming.</td><td data-label="Hoi An bias">Atmospheric town walks, cafes, food, and heritage can carry the stay without beach certainty.</td><td data-label="Planning move">Do not force a summer beach script in a softer weather window.</td></tr>
<tr><td data-label="Travel window">Very tight arrival/departure days</td><td data-label="Da Nang bias">Usually safer because the airport and city are closer.</td><td data-label="Hoi An bias">Works when the road transfer is planned, paid for, and not attached to a critical flight.</td><td data-label="Planning move">Let flight risk choose the base when timing is not forgiving.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-da-nang-hoi-an-transfer-cost:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Transfer and cost checks before booking</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The expensive mistake is not the transfer fare alone. It is the lost evening, extra packing, awkward flight timing, or beach hotel that does not match the route.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-da-nang-hoi-an-transfer-cost">
<thead>
<tr><th>Cost or friction</th><th>What to verify</th><th>Why it changes the answer</th></tr>
</thead>
<tbody>
<tr><td data-label="Cost or friction">Airport arrival and departure</td><td data-label="What to verify">Landing time, departure time, immigration buffer, hotel check-in, and road transfer.</td><td data-label="Why it changes the answer">A late or early flight can make Da Nang the smarter first or last night even if Hoi An is the main base.</td></tr>
<tr><td data-label="Cost or friction">Beach hotel location</td><td data-label="What to verify">Exact beach stretch, walkability, family needs, road crossing, and whether the hotel is really near the sand.</td><td data-label="Why it changes the answer">Da Nang is strongest when beach access is real, not just a map label.</td></tr>
<tr><td data-label="Cost or friction">Hoi An old-town access</td><td data-label="What to verify">Distance to Ancient Town, shuttle/taxi need, pedestrian comfort, and evening return.</td><td data-label="Why it changes the answer">Hoi An loses value if the hotel makes its best hours hard to use.</td></tr>
<tr><td data-label="Cost or friction">Split-base hotel move</td><td data-label="What to verify">Checkout times, luggage storage, transfer cost, children, heat, and what the move replaces.</td><td data-label="Why it changes the answer">A split base is only premium when it creates a better trip, not more admin.</td></tr>
<tr><td data-label="Cost or friction">Private driver or day trip</td><td data-label="What to verify">Route, stops, waiting time, weather, and whether the transfer doubles as sightseeing.</td><td data-label="Why it changes the answer">A well-used transfer can make one base work better than two rushed bases.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-da-nang-hoi-an-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Skipping is what keeps central Vietnam elegant. Do not make Da Nang and Hoi An compete for every possible stop; choose the base that protects the chapter's purpose.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-da-nang-hoi-an-skip-logic">
<thead>
<tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you want...">A short first trip</td><td data-label="Protect this">One central base, one clean handoff, and a memorable evening or beach block.</td><td data-label="Skip first">Sleeping in both Da Nang and Hoi An for only two or three nights.</td><td data-label="Only add back when...">Flight timing or a real resort stay rewards the move.</td></tr>
<tr><td data-label="If you want...">Beach and resort time</td><td data-label="Protect this">Da Nang/My Khe hotel quality, pool, beach access, and weather reality.</td><td data-label="Skip first">Forcing Hoi An as the sleeping base because it sounds more charming.</td><td data-label="Only add back when...">You can still protect Ancient Town as an evening or separate night.</td></tr>
<tr><td data-label="If you want...">Old Town atmosphere</td><td data-label="Protect this">Hoi An after dark, food, cafes, and a slower morning.</td><td data-label="Skip first">A Da Nang resort commute that makes the best Hoi An hours feel like a tour.</td><td data-label="Only add back when...">The resort is the main reason for the central coast chapter.</td></tr>
<tr><td data-label="If you want...">Hue, Hai Van, and Hoi An</td><td data-label="Protect this">The transfer day and enough time on either side.</td><td data-label="Skip first">Extra Da Nang city stops that turn the Hai Van transfer into a checklist.</td><td data-label="Only add back when...">Weather, luggage, and timing support it.</td></tr>
<tr><td data-label="If you want...">Premium central Vietnam</td><td data-label="Protect this">A base that makes the best hours easy, not the hotel with the fanciest listing.</td><td data-label="Skip first">Changing hotels without a clear gain.</td><td data-label="Only add back when...">The split base creates real beach, food, or flight value.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-da-nang-hoi-an-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before you book</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-da-nang-hoi-an-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-da-nang-hoi-an-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/central-vietnam/da-nang" target="_blank" rel="noopener">Vietnam.travel Da Nang</a> for the official city, beach, airport, and central-coast orientation.</li>
<li>Use <a href="https://vietnam.travel/places-to-go/central-vietnam/hoi-an" target="_blank" rel="noopener">Vietnam.travel Hoi An</a> and the <a href="https://whc.unesco.org/en/list/948/" target="_blank" rel="noopener">UNESCO Hoi An Ancient Town listing</a> for old-town and heritage framing.</li>
<li>Use <a href="https://vietnam.travel/places-to-go/central-vietnam" target="_blank" rel="noopener">Vietnam.travel Central Vietnam</a>, <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a>, and <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport</a> before turning the base choice into fixed hotel bookings.</li>
<li>Use <a href="https://danangfantasticity.com/en/" target="_blank" rel="noopener">Da Nang Fantasticity</a> for local tourism context, events, and city updates when Da Nang is the working base.</li>
<li>Use <a href="https://vietnam.travel/node/1395" target="_blank" rel="noopener">Vietnam.travel An Bang Beach</a>, <a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a>, <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying for beach hotels, private transfers, or a split base.</li>
</ul>
<!-- /wp:list -->

<!-- vg-da-nang-hoi-an-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Da Nang vs Hoi An FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-da-nang-hoi-an-faq">
<details><summary>Is Da Nang or Hoi An better for first-time visitors?</summary><p>Hoi An is usually better for atmosphere and memorable evenings. Da Nang is better for airport convenience, beach hotels, resort stays, and lower-friction logistics. The right answer depends on whether the central chapter is meant to feel slow or easy.</p></details>
<details><summary>Can I stay in Da Nang and visit Hoi An?</summary><p>Yes. This is often the cleanest pattern for short stays, beach-first routes, families, or early flights. Protect one Hoi An evening rather than treating the town as a rushed daytime stop.</p></details>
<details><summary>Can I stay in Hoi An and use Da Nang airport?</summary><p>Yes. Hoi An is commonly reached through Da Nang airport, but the road transfer should be part of the plan, especially for late arrivals, early departures, children, or heavy luggage.</p></details>
<details><summary>Should I split my stay between Da Nang and Hoi An?</summary><p>Only when the trip has enough nights. With two or three central nights, splitting often creates more packing than value. With four or more nights, a resort/beach chapter plus Hoi An old-town chapter can work well.</p></details>
<details><summary>Which is better for beach time?</summary><p>Da Nang/My Khe is the cleaner beach base. Hoi An/An Bang is better when beach time is a relaxed complement to Ancient Town, food, cafes, and heritage rather than the main resort holiday.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then test the central Vietnam base against <a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a>, <a href="/destinations/best-things-to-do-in-hue/">Best Things to Do in Hue</a>, <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-da-nang-hoi-an-hero:v1',
    'concierge verdict' => 'vg-da-nang-hoi-an-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-da-nang-hoi-an-at-a-glance:v1',
    'photo grid' => 'vg-da-nang-hoi-an-photo-grid:v1',
    'decision matrix' => 'vg-da-nang-hoi-an-decision-matrix:v1',
    'base chooser' => 'vg-da-nang-hoi-an-base-chooser:v1',
    'route fit' => 'vg-da-nang-hoi-an-route-fit:v1',
    'season weather' => 'vg-da-nang-hoi-an-season-weather:v1',
    'transfer cost' => 'vg-da-nang-hoi-an-transfer-cost:v1',
    'skip logic' => 'vg-da-nang-hoi-an-skip-logic:v1',
    'live checks' => 'vg-da-nang-hoi-an-live-checks:v1',
    'FAQ' => 'vg-da-nang-hoi-an-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('Da Nang vs Hoi An guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Da Nang vs Hoi An',
    'post_name'      => 'da-nang-vs-hoi-an',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Da Nang vs Hoi An comparison for international travelers choosing the right central Vietnam base by airport friction, beach value, old-town rhythm, weather, transfer load, and split-base logic.',
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
    vg_ops_fail('Could not publish Da Nang vs Hoi An guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Da Nang vs Hoi An guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Da Nang vs Hoi An: Which Central Vietnam Base Is Better?');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Da Nang vs Hoi An comparison: choose the central Vietnam base by airport friction, beach value, old-town evenings, weather, transfers, and split-base logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Da Nang vs Hoi An');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Da Nang or Hoi An should anchor the central Vietnam chapter before booking hotels, based on airport timing, beach value, old-town rhythm, weather, transfers, and whether a split base earns its friction.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 18, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Da Nang vs Hoi An comparison guide with a concierge verdict, at-a-glance decision table, licensed photo proof, decision matrix, base chooser, route-fit table, season/weather pivots, transfer/cost checks, skip logic, live official checks, FAQ, Compare hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Da Nang destination page - https://vietnam.travel/places-to-go/central-vietnam/da-nang - checked July 18, 2026\nVietnam.travel - Hoi An destination page - https://vietnam.travel/places-to-go/central-vietnam/hoi-an - checked July 18, 2026\nVietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked July 18, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 18, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 18, 2026\nVietnam.travel - An Bang Beach / Hoi An beach context - https://vietnam.travel/node/1395 - checked July 18, 2026\nUNESCO World Heritage Centre - Hoi An Ancient Town - https://whc.unesco.org/en/list/948/ - checked July 18, 2026\nDa Nang Fantasticity - official local tourism portal - https://danangfantasticity.com/en/ - checked July 18, 2026\nWikimedia Commons image record - My Khe Beach seen from the Son Tra Mountain - https://commons.wikimedia.org/wiki/File:My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg - license checked July 18, 2026\nWikimedia Commons image record - Dragon bridge from above - https://commons.wikimedia.org/wiki/File:Dragon_bridge_from_above.png - license checked July 18, 2026\nWikimedia Commons image record - Vietnam, Hai-Van-Pass - https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg - license checked July 18, 2026\nWikimedia Commons image record - Hoi An Ancient Town - https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg - license checked July 18, 2026\nWikimedia Commons image record - 2024-11-23 An Bang Beach in Hoi An in November - https://commons.wikimedia.org/wiki/File:2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg - license checked July 18, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Da Nang vs Hoi An is a base-choice problem: the best central Vietnam stay is the one that protects airport timing, beach expectations, old-town evenings, heat/rain reality, and the next transfer with the least waste.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Comparison guide built around a real hotel-base decision rather than a generic city-versus-town listicle.\nConcierge verdict separates Da Nang's airport/beach/resort value from Hoi An's old-town/food/heritage rhythm.\nAt-a-glance table answers the first booking questions for international travelers.\nLicensed photo proof with visible credits for My Khe Beach, Dragon Bridge, Hai Van Pass, Hoi An Ancient Town, and An Bang Beach.\nDecision matrix covers airport, beach, evening atmosphere, food, day trips, and premium feel.\nBase chooser compares Da Nang base, Hoi An base, split base, and airport-night patterns.\nRoute-fit table ties the decision to 10 Days, 14 Days, Hue, Best Beaches, and Hoi An.\nSeason/weather pivots keep central Vietnam timing honest.\nTransfer/cost checks cover airport, beach-hotel location, old-town access, split-base moves, and private drivers.\nSkip logic protects the route when changing hotels does not earn its friction.\nLive-check list tied to Vietnam.travel, UNESCO, Da Nang Fantasticity, weather, transport, cost, beach, Hoi An, and internal planning guides.\nVisible source trail and update log.");
$related_routes = "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before choosing the central Vietnam base.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Compare Da Nang and Hoi An against the broader destination shortlist.\nBest Things to Do in Hoi An | /destinations/best-things-to-do-in-hoi-an/ | Use this when Hoi An atmosphere, food, My Son, or An Bang are the reason for staying central.\nBest Things to Do in Hue | /destinations/best-things-to-do-in-hue/ | Keep the Hue transfer and Hai Van Pass decision honest before choosing a base.\nBest Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ | Decide whether central-coast beach time should lead or support the route.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether central Vietnam should be the route's main chapter.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check central-coast season, heat, rain, and storm pressure before booking.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Confirm airport, road transfer, and private-driver logic before splitting bases.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price resort nights, private transfers, split-base admin, and route buffers before booking.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a short route has enough time for one central base or a split.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when Da Nang plus Hoi An can become two distinct central chapters.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check heat, water, transfer, and interruption risk for central Vietnam.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair taxis, private drivers, beach deposits, and night movement with practical risk checks.";
vg_ops_assert_related_route_meta_links_are_published('Da Nang vs Hoi An guide related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and My Khe image: My Khe Beach seen from the Son Tra Mountain by Christophe95, CC BY-SA 4.0. Body images: Dragon bridge from above by Supanut Arunoprayote, CC BY 4.0; Vietnam, Hai-Van-Pass by Wolkenkratzer, CC BY-SA 4.0; Hoi An Ancient Town by Steffen Schmitz, CC BY-SA 4.0; An Bang Beach in Hoi An in November by Alexkom000, CC BY 4.0.');
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
    vg_ops_fail('Da Nang vs Hoi An guide was updated but is not published.');
}

vg_ops_refresh_compare_hub();
vg_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Da Nang vs Hoi An guide: {$page_id} {$updated_permalink}");

<?php
/**
 * Publish the Da Nang Travel Guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-da-nang-travel-guide.php --allow-root
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
    $value = getenv('VG_FORCE_DA_NANG_GUIDE_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_ops_repair_side_effects_enabled(): bool
{
    $value = getenv('VG_REPAIR_DA_NANG_GUIDE_LINKS');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the Da Nang Travel Guide.');
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

function vg_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('destinations/da-nang-travel-guide')) {
        vg_ops_log('Skipped Destinations hub refresh: Da Nang Travel Guide is not published.');
        return;
    }

    $marker = '<!-- vg-da-nang-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use Da Nang as the central coast control room</h3><p>The <a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a> helps travelers decide when the airport, My Khe beach, riverfront, Son Tra, Marble Mountains, Hai Van access, and Hoi An/Hue day-trip logic make Da Nang the best central Vietnam base.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Destinations hub Da Nang note', $block);
    vg_ops_upsert_marked_group($hub, 'Destinations hub Da Nang note', $marker, $block);
}

function vg_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_ops_assert_related_route_line_links_are_published("{$label} related route line", $route_line);
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
        vg_ops_log("Skipped related-route refresh for {$label}: Da Nang Travel Guide line is already current.");
        return;
    }

    $next = implode("\n", $next_lines);
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_ops_log("Upserted Da Nang Travel Guide related route to {$label}: {$page->ID}");
}

function vg_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Da Nang Travel Guide | /destinations/da-nang-travel-guide/ | Decide whether Da Nang should be the airport-and-beach city base for central Vietnam before adding Hoi An, Hue, Ba Na Hills, or another hotel move.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
            'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
            'destinations/best-things-to-do-in-hue' => 'Best Things to Do in Hue guide',
            'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
            'compare/da-nang-vs-hoi-an' => 'Da Nang vs Hoi An guide',
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

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/da-nang-travel-guide', OBJECT, 'page');

if ($page instanceof WP_Post && $page->post_status === 'publish' && vg_ops_repair_side_effects_enabled()) {
    vg_ops_log('Repairing Da Nang Travel Guide hub and inbound related-route side effects without rewriting the guide.');
    vg_ops_refresh_destinations_hub();
    vg_ops_refresh_inbound_related_routes();
    vg_ops_log("Repaired Da Nang Travel Guide side effects: {$page->ID} " . get_permalink($page));
    return;
}

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_ops_force_republish_enabled()) {
    vg_ops_fail('Da Nang Travel Guide is not a draft. Set VG_FORCE_DA_NANG_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_ops_log("Preflight Da Nang Travel Guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_ops_log('Preflight Da Nang Travel Guide: no existing page found; creating a child page under /destinations/.');
}

$my_khe_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/a/af/My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg/1920px-My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg');
$my_khe_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg');
$dragon_bridge_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Dragon_bridge_from_above.png/1920px-Dragon_bridge_from_above.png');
$dragon_bridge_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Dragon_bridge_from_above.png');
$marble_mountains_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/1d/Da_Nang_Marble_Mountains_2020_IMG_4008.jpg/1920px-Da_Nang_Marble_Mountains_2020_IMG_4008.jpg');
$marble_mountains_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Da_Nang_Marble_Mountains_2020_IMG_4008.jpg');
$lady_buddha_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/54/Lady_Buddha_Da_Nang.jpg/1920px-Lady_Buddha_Da_Nang.jpg');
$lady_buddha_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Lady_Buddha_Da_Nang.jpg');
$hai_van_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1280px-Vietnam%2C_Hai-Van-Pass.jpg');
$hai_van_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg');
$golden_bridge_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/98/Aerial_view_of_the_Golden_Bridge%2C_Ba_Na_Hills%2C_Da_Nang%2C_Vietnam.jpg/1280px-Aerial_view_of_the_Golden_Bridge%2C_Ba_Na_Hills%2C_Da_Nang%2C_Vietnam.jpg');
$golden_bridge_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Aerial_view_of_the_Golden_Bridge,_Ba_Na_Hills,_Da_Nang,_Vietnam.jpg');

$content = <<<HTML
<!-- vg-da-nang-guide-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$my_khe_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="My Khe Beach and Da Nang coast seen from Son Tra Mountain in Vietnam" src="{$my_khe_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 18, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Da Nang Travel Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Da Nang is central Vietnam's cleanest airport, beach, and city base. Use it when the route needs My Khe beach, an easy arrival or departure, Son Tra and Marble Mountains, a riverfront city rhythm, and controlled access to Hoi An, Hue, Hai Van Pass, or Ba Na Hills without turning the middle of the trip into hotel admin.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: Da Nang is most valuable when convenience is not a compromise. It should make central Vietnam easier, sunnier, and more flexible, not become a filler night between famous names.</p>
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

<!-- vg-da-nang-guide-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-da-nang-guide-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-da-nang-guide-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Choose Da Nang when central Vietnam needs a lower-friction base: airport access, My Khe beach, family-friendly hotels, resort recovery, city restaurants, and easy day trips. Do not choose it only because it is between Hue and Hoi An.</strong> The city earns its place when it protects time and improves the route's logistics.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-da-nang-guide-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-da-nang-guide-shortlist">
<li><strong>Best for:</strong> beach-first central stays, late arrivals, early departures, families, resort travelers, and routes that need one practical base.</li>
<li><strong>Best first plan:</strong> My Khe or beach hotel, one Son Tra or riverfront block, Marble Mountains, one Hoi An evening, and one flexible recovery half-day.</li>
<li><strong>Best premium use:</strong> buy hotel quality, private timing, beach access, and a cleaner airport handoff instead of overloading every day trip.</li>
<li><strong>Best skip rule:</strong> skip Ba Na Hills, duplicate bridge stops, or a second base move before you cut beach/weather slack.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-da-nang-guide-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-da-nang-guide-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-da-nang-guide-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fast answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-da-nang-guide-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Is Da Nang worth visiting?</strong></td><td data-label="Answer">Yes when you want beach, airport convenience, resort comfort, city food, and easy access to central Vietnam day trips. It is weaker when you only need one atmospheric heritage stop.</td></tr>
<tr><td data-label="Question"><strong>How many nights?</strong></td><td data-label="Answer">Two nights works as an airport-and-beach reset. Three nights is the cleaner default. Four or more nights make sense when Da Nang is the main central base or beach chapter.</td></tr>
<tr><td data-label="Question"><strong>Where should I stay?</strong></td><td data-label="Answer">My Khe and the coastal strip for beach/resort rhythm, Han River for city access, or one airport-side night only when flight timing is the whole point.</td></tr>
<tr><td data-label="Question"><strong>Da Nang or Hoi An?</strong></td><td data-label="Answer">Da Nang for airport, beach, and logistics. Hoi An for Ancient Town evenings, food, cafes, and slower texture. Use the comparison before splitting bases.</td></tr>
<tr><td data-label="Question"><strong>What should I verify live?</strong></td><td data-label="Answer">Season, beach conditions, exact hotel location, flight timing, road transfers, attraction opening rules, and whether an optional day trip replaces better downtime.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-da-nang-guide-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what Da Nang actually adds</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The images are planning evidence. They show why Da Nang works as a beach-and-airport base, a bridge-and-river city, a Son Tra and Marble Mountains half-day, a Hai Van transfer chapter, and an optional theme-park day only when the route has room.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-da-nang-guide-photo-grid" aria-label="Da Nang travel guide photography">
<figure class="vg-guide-photo"><img src="{$my_khe_image}" alt="My Khe Beach in Da Nang seen from Son Tra Mountain" loading="lazy" decoding="async"><figcaption>My Khe is the reason Da Nang can be a real beach base, not just an airport stop. Image: <a href="{$my_khe_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$dragon_bridge_image}" alt="Dragon Bridge in Da Nang viewed from above" loading="lazy" decoding="async"><figcaption>The riverfront gives Da Nang an evening city rhythm that Hoi An cannot replace. Image: <a href="{$dragon_bridge_credit_url}" target="_blank" rel="license noopener">Supanut Arunoprayote / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$lady_buddha_image}" alt="Lady Buddha statue on Son Tra Peninsula in Da Nang" loading="lazy" decoding="async"><figcaption>Son Tra is strongest as a calm scenic half-day, especially when beach weather and transfer timing cooperate. Image: <a href="{$lady_buddha_credit_url}" target="_blank" rel="license noopener">Christophe95 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$marble_mountains_image}" alt="Marble Mountains in Da Nang, Vietnam" loading="lazy" decoding="async"><figcaption>Marble Mountains is the easiest high-value cultural and viewpoint stop from a Da Nang base. Image: <a href="{$marble_mountains_credit_url}" target="_blank" rel="license noopener">Kuroczynski / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hai_van_image}" alt="Hai Van Pass coastal road between Da Nang and Hue" loading="lazy" decoding="async"><figcaption>Hai Van Pass turns the Hue transfer into route value when weather, luggage, and timing support it. Image: <a href="{$hai_van_credit_url}" target="_blank" rel="license noopener">Wolkenkratzer / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$golden_bridge_image}" alt="Golden Bridge at Ba Na Hills near Da Nang" loading="lazy" decoding="async"><figcaption>Ba Na Hills is visually famous, but it should be an optional day by fit, not a compulsory Da Nang tax. Image: <a href="{$golden_bridge_credit_url}" target="_blank" rel="license noopener">Vivu Vietnam / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-da-nang-guide-priority-map:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to prioritize first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Da Nang gets better when the plan has hierarchy. Start with the base job, then add only the experiences that improve that job.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-da-nang-guide-priority-map">
<thead>
<tr><th>Experience</th><th>Why it matters</th><th>Best fit</th><th>Watch out for</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Experience">My Khe beach and coastal hotels</td><td data-label="Why it matters">This is Da Nang's clearest advantage over inland or old-town bases.</td><td data-label="Best fit">Families, resort stays, couples, summer recovery, and travelers who need an easier middle chapter.</td><td data-label="Watch out for">Hotels that say beach but sit on the wrong road or far from the stretch you expect.</td><td data-label="VietnamGuide verdict">Protect this first when Da Nang is the base.</td></tr>
<tr><td data-label="Experience">Son Tra and Linh Ung</td><td data-label="Why it matters">Adds coastal height, greenery, and a calmer scenic half-day without a long transfer.</td><td data-label="Best fit">Clear mornings, photography, families with short attention windows, and slower premium routes.</td><td data-label="Watch out for">Heat, traffic timing, and turning a simple half-day into a long checklist.</td><td data-label="VietnamGuide verdict">High-value if kept light.</td></tr>
<tr><td data-label="Experience">Marble Mountains</td><td data-label="Why it matters">Adds caves, viewpoints, religious sites, and a clean stop between Da Nang and Hoi An.</td><td data-label="Best fit">First-timers, Hoi An transfer days, and travelers wanting one easy cultural layer.</td><td data-label="Watch out for">Slippery steps, heat, and crowd timing.</td><td data-label="VietnamGuide verdict">The easiest day-trip win.</td></tr>
<tr><td data-label="Experience">Han River and Dragon Bridge</td><td data-label="Why it matters">Makes Da Nang feel like a city base rather than only a beach resort strip.</td><td data-label="Best fit">Evening walks, dinner plans, short stays, and travelers who do not want quiet every night.</td><td data-label="Watch out for">Chasing every bridge instead of using the riverfront as a low-pressure evening.</td><td data-label="VietnamGuide verdict">Use as atmosphere, not as a checklist.</td></tr>
<tr><td data-label="Experience">Hai Van Pass and Hue access</td><td data-label="Why it matters">Da Nang can make the Hue transfer a scenic route rather than dead time.</td><td data-label="Best fit">Routes moving north or south through Hue with a private driver or well-timed transfer.</td><td data-label="Watch out for">Poor weather, heavy luggage, and overloaded same-day plans.</td><td data-label="VietnamGuide verdict">Worth it when the transfer gets protected.</td></tr>
<tr><td data-label="Experience">Ba Na Hills and Golden Bridge</td><td data-label="Why it matters">A famous visual day that can work for theme-park travelers and families.</td><td data-label="Best fit">Longer Da Nang stays, family routes, and travelers who actively want a theme-park style day.</td><td data-label="Watch out for">Using it by default when the route needs beach, Hoi An, Hue, or rest more.</td><td data-label="VietnamGuide verdict">Optional, not core.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-da-nang-guide-base-areas:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where to stay in Da Nang</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The right area decides whether Da Nang feels elegant or scattered. Book by the job the city is doing in the route.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-da-nang-guide-base-areas">
<thead>
<tr><th>Base area</th><th>Best for</th><th>What improves</th><th>Trade-off</th><th>Booking check</th></tr>
</thead>
<tbody>
<tr><td data-label="Base area">My Khe and beach strip</td><td data-label="Best for">Beach-first stays, families, resort travelers, pool time, and lower-stress central chapters.</td><td data-label="What improves">Morning beach, hotel comfort, recovery, and easier taxi movement along the coast.</td><td data-label="Trade-off">Less old-town atmosphere and a taxi ride for some city meals.</td><td data-label="Booking check">Confirm exact beach access, road crossing, pool quality, shade, and room noise.</td></tr>
<tr><td data-label="Base area">Han River / city side</td><td data-label="Best for">Short stays, food, nightlife, river walks, business-style comfort, and travelers less focused on beach time.</td><td data-label="What improves">Restaurants, city movement, bridges, and a clearer urban evening.</td><td data-label="Trade-off">Beach becomes a ride rather than the default rhythm.</td><td data-label="Booking check">Check bridge access, walkability, and airport timing.</td></tr>
<tr><td data-label="Base area">Son Tra edge</td><td data-label="Best for">View-led stays, quieter beach hotels, and travelers who want a calmer coastal feel.</td><td data-label="What improves">Scenery, resort calm, and morning/evening atmosphere.</td><td data-label="Trade-off">Some meals and day trips need more planning.</td><td data-label="Booking check">Confirm transfer time, dining options, and whether isolation helps or hurts the trip.</td></tr>
<tr><td data-label="Base area">Airport-side practical night</td><td data-label="Best for">Late arrival, early departure, or one-night reset before Hoi An or Hue.</td><td data-label="What improves">Stress reduction and simpler logistics.</td><td data-label="Trade-off">It is rarely the most memorable Da Nang stay.</td><td data-label="Booking check">Use only when flight timing is the reason, then move with a clear plan.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-da-nang-guide-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How many Da Nang nights do you actually need?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Count nights by what Da Nang protects: beach, airport, a day trip, a transfer, or rest. Extra nights are valuable only when they make the route calmer or deeper.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-da-nang-guide-route-fit">
<thead>
<tr><th>Da Nang time</th><th>Keep</th><th>Add only if the route breathes</th><th>Skip first</th><th>Why</th></tr>
</thead>
<tbody>
<tr><td data-label="Da Nang time">One night</td><td data-label="Keep">Airport, beach walk, simple meal, and a clean onward transfer.</td><td data-label="Add only if the route breathes">Dragon Bridge or a short riverfront evening if arrival energy is good.</td><td data-label="Skip first">Ba Na Hills, multiple viewpoints, and any long day trip.</td><td data-label="Why">One night is logistics, not a full city chapter.</td></tr>
<tr><td data-label="Da Nang time">Two nights</td><td data-label="Keep">My Khe beach, Son Tra or Marble Mountains, riverfront dinner, and slack before the next move.</td><td data-label="Add only if the route breathes">Hoi An evening or Hai Van transfer if timing is clean.</td><td data-label="Skip first">Hotel split with Hoi An unless flight timing demands it.</td><td data-label="Why">This is a useful reset when the route stays selective.</td></tr>
<tr><td data-label="Da Nang time">Three nights</td><td data-label="Keep">Beach base, Marble Mountains, Son Tra, one Hoi An evening, and a flexible weather buffer.</td><td data-label="Add only if the route breathes">Hue transfer via Hai Van or a carefully chosen theme-park day.</td><td data-label="Skip first">A second distant day trip that steals the beach value.</td><td data-label="Why">Three nights is the best first-trip balance for many travelers.</td></tr>
<tr><td data-label="Da Nang time">Four or more nights</td><td data-label="Keep">Resort comfort, beach rhythm, city meals, Hoi An, one serious transfer day, and rest.</td><td data-label="Add only if the route breathes">Ba Na Hills, golf, spa, deeper food time, or a second central base.</td><td data-label="Skip first">Adding every nearby attraction simply because the base is convenient.</td><td data-label="Why">Longer Da Nang works when it remains a base, not a checklist engine.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-da-nang-guide-season-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Season and weather pivots</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Central Vietnam does not follow the same weather rhythm as the whole country. Use Da Nang's beach and transfer plans with flexible timing, especially around heat, rain, and storm-prone months.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-da-nang-guide-season-weather">
<thead>
<tr><th>Travel window</th><th>Best Da Nang bias</th><th>Move earlier</th><th>Move later or cut</th><th>Route note</th></tr>
</thead>
<tbody>
<tr><td data-label="Travel window">March to May</td><td data-label="Best Da Nang bias">Strong all-round window for beach, Son Tra, Marble Mountains, Hoi An, and Hai Van movement.</td><td data-label="Move earlier">Marble Mountains, Son Tra, and any exposed walking.</td><td data-label="Move later or cut">Duplicate city viewpoints if the beach conditions are good.</td><td data-label="Route note">Use the good window to protect quality, not to overload the middle.</td></tr>
<tr><td data-label="Travel window">June to August</td><td data-label="Best Da Nang bias">Beach, pool, early starts, shaded lunches, and lighter afternoons.</td><td data-label="Move earlier">Son Tra, Marble Mountains, Hoi An walking, and any transfer with stops.</td><td data-label="Move later or cut">Midday climbs, overlong city walks, and too many outdoor add-ons.</td><td data-label="Route note">Heat makes hotel quality and downtime part of the itinerary.</td></tr>
<tr><td data-label="Travel window">September to November</td><td data-label="Best Da Nang bias">Flexible bookings, stronger hotel buffer, indoor meals, and live weather checks.</td><td data-label="Move earlier">Outdoor blocks when the morning is clear.</td><td data-label="Move later or cut">Beach assumptions, mountain-road extras, and non-flexible day trips.</td><td data-label="Route note">Slack can be worth more than a prepaid attraction.</td></tr>
<tr><td data-label="Travel window">December to February</td><td data-label="Best Da Nang bias">Cooler city time, food, riverfront, scenic transfers, and softer beach expectations.</td><td data-label="Move earlier">Clearer outdoor windows and longer transfers.</td><td data-label="Move later or cut">Beach-first expectations if conditions are not cooperating.</td><td data-label="Route note">Let Da Nang be practical and atmospheric rather than forcing a summer script.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-da-nang-guide-day-trips:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best day trips and nearby decisions</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A Da Nang base gives you reach, but reach is not a reason to add everything. Each day trip should replace a weaker use of time.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-da-nang-guide-day-trips">
<thead>
<tr><th>Option</th><th>Best use</th><th>What to verify</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Option">Hoi An evening</td><td data-label="Best use">When Da Nang is the sleeping base but you still want Ancient Town after dark.</td><td data-label="What to verify">Driver timing, return comfort, dinner plan, and whether a late return hurts the next day.</td><td data-label="VietnamGuide verdict">The cleanest way to avoid unnecessary hotel moves.</td></tr>
<tr><td data-label="Option">Marble Mountains</td><td data-label="Best use">A compact cultural and viewpoint stop, especially between Da Nang and Hoi An.</td><td data-label="What to verify">Heat, steps, footwear, crowd timing, and route order.</td><td data-label="VietnamGuide verdict">High value for most first-time stays.</td></tr>
<tr><td data-label="Option">Son Tra and Linh Ung</td><td data-label="Best use">Scenic half-day, softer pace, views, and coastal atmosphere.</td><td data-label="What to verify">Weather, transport, road comfort, and whether the day still has beach time.</td><td data-label="VietnamGuide verdict">Best when kept simple.</td></tr>
<tr><td data-label="Option">Hue via Hai Van</td><td data-label="Best use">Transfer day that becomes scenic and historical rather than wasted movement.</td><td data-label="What to verify">Private timing, luggage, road weather, tomb/Citadel priorities, and overnight plan.</td><td data-label="VietnamGuide verdict">Worth planning as a route chapter, not a rushed round trip.</td></tr>
<tr><td data-label="Option">Ba Na Hills</td><td data-label="Best use">Theme-park travelers, families who want it, or longer Da Nang stays with a spare day.</td><td data-label="What to verify">Weather, ticket rules, cable-car timing, crowds, and whether it displaces better central Vietnam time.</td><td data-label="VietnamGuide verdict">Optional by taste, not required by destination quality.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-da-nang-guide-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first in Da Nang</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Da Nang becomes premium when it removes friction. Cut the things that make a convenient base feel busy again.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-da-nang-guide-skip-logic">
<thead>
<tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you want...">A short central stop</td><td data-label="Protect this">Airport timing, one beach block, one city meal, and a clean onward move.</td><td data-label="Skip first">Ba Na Hills, extra bridge stops, and any attraction that turns the stay into logistics.</td><td data-label="Only add back when...">You add another night or cut a weaker stop elsewhere.</td></tr>
<tr><td data-label="If you want...">Beach and resort recovery</td><td data-label="Protect this">My Khe access, pool time, shade, food, and flexible mornings.</td><td data-label="Skip first">Distant day trips that steal the reason you booked the beach.</td><td data-label="Only add back when...">The weather and route still leave real recovery time.</td></tr>
<tr><td data-label="If you want...">Hoi An atmosphere</td><td data-label="Protect this">A proper Ancient Town evening or an Hoi An overnight if atmosphere leads.</td><td data-label="Skip first">Sleeping in Da Nang purely for convenience while commuting to Hoi An every best hour.</td><td data-label="Only add back when...">Flights, family needs, or beach plans make Da Nang genuinely better.</td></tr>
<tr><td data-label="If you want...">Hue and Hai Van depth</td><td data-label="Protect this">A careful transfer day with selected Hue priorities.</td><td data-label="Skip first">Trying to do Hue as a shallow return trip from Da Nang.</td><td data-label="Only add back when...">You have private timing, weather, and enough energy for the day.</td></tr>
<tr><td data-label="If you want...">Premium central Vietnam</td><td data-label="Protect this">Hotel quality, private timing, fewer transitions, and strong meal/rest blocks.</td><td data-label="Skip first">Changing hotels or adding famous nearby stops without a clear gain.</td><td data-label="Only add back when...">The extra move makes the trip calmer, not merely fuller.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-da-nang-guide-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Official checks before locking Da Nang</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use official and primary sources for framing, then verify live weather, transport, hotel location, and attraction access close to travel. The editorial judgment here is designed to last; operational details still deserve a final check.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-da-nang-guide-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-da-nang-guide-live-checks">
<li>Destination frame: start with <a href="https://vietnam.travel/places-to-go/central-vietnam/da-nang" target="_blank" rel="noopener">Vietnam.travel Da Nang</a> and <a href="https://vietnam.travel/places-to-go/central-vietnam" target="_blank" rel="noopener">Vietnam.travel Central Vietnam</a> before deciding whether Da Nang is the base or a transit stop.</li>
<li>Season and weather: compare <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> with <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a> before paying for beach hotels, mountain-road transfers, or weather-sensitive extras.</li>
<li>Transport logic: use <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before adding airport nights, private drivers, or split bases.</li>
<li>Local updates: check <a href="https://danangfantasticity.com/en/" target="_blank" rel="noopener">Da Nang Fantasticity</a> for official local tourism context, events, and city updates when Da Nang is doing the heavy lifting in the route.</li>
<li>Planning order: read <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>, <a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a>, <a href="/destinations/best-things-to-do-in-hue/">Best Things to Do in Hue</a>, <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, <a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a> before stretching central Vietnam beyond the route's real slack.</li>
</ul>
<!-- /wp:list -->

<!-- vg-da-nang-guide-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Da Nang planning FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-da-nang-guide-faq">
<details><summary>Is Da Nang better than Hoi An?</summary><p>Da Nang is better for beach hotels, airport access, family logistics, resort comfort, and easy day trips. Hoi An is better for old-town evenings, food, cafes, heritage texture, and slower atmosphere. The best answer depends on what the central chapter needs to do.</p></details>
<details><summary>How many days should I spend in Da Nang?</summary><p>Two nights is enough for a practical airport-and-beach stop. Three nights is better if Da Nang is the central base. Four or more nights work when beach recovery, resort comfort, and day trips are the actual purpose.</p></details>
<details><summary>Is My Khe Beach a good place to stay?</summary><p>Yes when beach access, resort comfort, and a lower-stress middle chapter matter. Check the exact hotel position, road crossing, shade, room noise, pool quality, and whether the beach is still the best use of your season.</p></details>
<details><summary>Should I visit Ba Na Hills from Da Nang?</summary><p>Only if a theme-park style day and the Golden Bridge are things you actively want. It is not required for a strong Da Nang stay, and it should be cut before it displaces beach time, Hoi An, Hue, or recovery.</p></details>
<details><summary>Can I use Da Nang as a base for Hoi An and Hue?</summary><p>Yes for Hoi An evenings and selected Hue movement, but avoid turning Da Nang into a commute hub. If Hoi An atmosphere or Hue history is the main reason for central Vietnam, an overnight in that place may be better.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then test Da Nang against <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>, <a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a>, <a href="/destinations/best-things-to-do-in-hue/">Best Things to Do in Hue</a>, <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-da-nang-guide-hero:v1',
    'concierge verdict' => 'vg-da-nang-guide-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-da-nang-guide-at-a-glance:v1',
    'photo grid' => 'vg-da-nang-guide-photo-grid:v1',
    'priority map' => 'vg-da-nang-guide-priority-map:v1',
    'base areas' => 'vg-da-nang-guide-base-areas:v1',
    'route fit' => 'vg-da-nang-guide-route-fit:v1',
    'season weather' => 'vg-da-nang-guide-season-weather:v1',
    'day trips' => 'vg-da-nang-guide-day-trips:v1',
    'skip logic' => 'vg-da-nang-guide-skip-logic:v1',
    'live checks' => 'vg-da-nang-guide-live-checks:v1',
    'FAQ' => 'vg-da-nang-guide-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('Da Nang Travel Guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Da Nang Travel Guide',
    'post_name'      => 'da-nang-travel-guide',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Da Nang Travel Guide for international travelers choosing airport convenience, My Khe beach, city base areas, Son Tra, Marble Mountains, Hai Van/Hue/Hoi An access, season pivots, skip logic, and live official checks.',
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
    vg_ops_fail('Could not publish Da Nang Travel Guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Da Nang Travel Guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Da Nang Travel Guide: Airport, Beach, City Base and Day Trips');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Da Nang Travel Guide: My Khe beach, where to stay, Son Tra, Marble Mountains, Ba Na Hills, Hoi An/Hue access, seasons, costs, and skip logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Da Nang travel guide');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Da Nang should be the airport-and-beach city base for central Vietnam before booking beach hotels, day trips, a Hoi An split base, Hue movement, or Ba Na Hills.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 18, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Da Nang Travel Guide with an Evidence-led Concierge verdict, at-a-glance decision table, licensed photo proof, priority map, base-area table, route-fit table, season/weather pivots, day-trip decisions, skip logic, live official checks, FAQ, Destinations hub note, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Da Nang destination page - https://vietnam.travel/places-to-go/central-vietnam/da-nang - checked July 18, 2026\nVietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked July 18, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 18, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 18, 2026\nDa Nang Fantasticity - official local tourism portal - https://danangfantasticity.com/en/ - checked July 18, 2026\nWikimedia Commons image record - My Khe Beach seen from the Son Tra Mountain - https://commons.wikimedia.org/wiki/File:My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg - license checked July 18, 2026\nWikimedia Commons image record - Dragon bridge from above - https://commons.wikimedia.org/wiki/File:Dragon_bridge_from_above.png - license checked July 18, 2026\nWikimedia Commons image record - Da Nang Marble Mountains 2020 IMG 4008 - https://commons.wikimedia.org/wiki/File:Da_Nang_Marble_Mountains_2020_IMG_4008.jpg - license checked July 18, 2026\nWikimedia Commons image record - Lady Buddha Da Nang - https://commons.wikimedia.org/wiki/File:Lady_Buddha_Da_Nang.jpg - license checked July 18, 2026\nWikimedia Commons image record - Vietnam, Hai-Van-Pass - https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg - license checked July 18, 2026\nWikimedia Commons image record - Aerial view of the Golden Bridge, Ba Na Hills, Da Nang, Vietnam - https://commons.wikimedia.org/wiki/File:Aerial_view_of_the_Golden_Bridge,_Ba_Na_Hills,_Da_Nang,_Vietnam.jpg - license checked July 18, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Da Nang is most useful when it lowers central Vietnam friction: airport timing, beach access, family comfort, city meals, and controlled day trips should all make the route cleaner.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Da Nang guide built as a base-choice and route-friction guide rather than a generic attractions list.\nConcierge verdict separates airport-and-beach base value from old-town or imperial-history priorities.\nAt-a-glance table answers worth, nights, stay area, Da Nang vs Hoi An, and live checks.\nLicensed photo proof with visible credits for My Khe Beach, Dragon Bridge, Son Tra, Marble Mountains, Hai Van Pass, and Golden Bridge.\nPriority map explains what each Da Nang experience changes in the route.\nBase-area table separates My Khe, Han River, Son Tra edge, and airport-side nights.\nRoute-fit table compares one, two, three, and four-plus night Da Nang stays.\nSeason/weather pivots keep central-coast timing honest.\nDay-trip table covers Hoi An, Marble Mountains, Son Tra, Hue via Hai Van, and Ba Na Hills by fit.\nSkip logic protects Da Nang from becoming a convenient but overloaded checklist base.\nOfficial checks tied to Vietnam.travel, Da Nang Fantasticity, Best Time, Transport, Cost, Hoi An, Hue, Beaches, UNESCO, 10 Days, and 14 Days.\nVisible source trail and update log.");
$related_routes = "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before deciding whether Da Nang should anchor central Vietnam.\nDa Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Choose the central base by airport friction, beach value, old-town rhythm, weather, and transfer pressure.\nBest Things to Do in Hoi An | /destinations/best-things-to-do-in-hoi-an/ | Use this when Hoi An atmosphere, food, My Son, or An Bang should lead the central chapter.\nBest Things to Do in Hue | /destinations/best-things-to-do-in-hue/ | Decide whether Hue deserves a protected imperial-history day before treating it as a transfer add-on.\nBest Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ | Compare Da Nang beach value against island, quiet-premium, and south-coast alternatives.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Use nearby heritage context before turning central Vietnam into only beach and airport convenience.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether central Vietnam should be the main chapter of the route.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check central-coast heat, rain, and storm pressure before booking beach hotels or mountain-road transfers.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Confirm airport, private-driver, train, and road-transfer logic before adding a second central base.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price beach hotels, private transfers, day trips, route buffers, and optional premium extras before booking.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a short route has enough time for Da Nang, Hoi An, Hue, and a beach reset.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when Da Nang can become a real central coast chapter instead of a transit stop.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check heat, water, road, activity, and interruption coverage before central Vietnam extras.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair airport taxis, private drivers, beach movement, deposits, and night outings with practical risk checks.";
vg_ops_assert_related_route_meta_links_are_published('Da Nang Travel Guide related routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_related_routes', $related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and My Khe image: My Khe Beach seen from the Son Tra Mountain by Christophe95, CC BY-SA 4.0. Body images: Dragon bridge from above by Supanut Arunoprayote, CC BY 4.0; Lady Buddha Da Nang by Christophe95, CC BY-SA 4.0; Da Nang Marble Mountains 2020 IMG 4008 by Kuroczynski, CC BY-SA 4.0; Vietnam, Hai-Van-Pass by Wolkenkratzer, CC BY-SA 4.0; Aerial view of the Golden Bridge, Ba Na Hills, Da Nang, Vietnam by Vivu Vietnam, CC BY-SA 4.0.');
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
    vg_ops_fail('Da Nang Travel Guide was updated but is not published.');
}

vg_ops_refresh_destinations_hub();
vg_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Da Nang Travel Guide: {$page_id} {$updated_permalink}");

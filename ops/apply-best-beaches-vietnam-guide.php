<?php
/**
 * Publish the Best Beaches in Vietnam guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-best-beaches-vietnam-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_best_beaches_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_best_beaches_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_best_beaches_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_BEST_BEACHES_GUIDE_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_best_beaches_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_best_beaches_ops_fail('Could not resolve a valid WordPress author for the Best Beaches in Vietnam guide.');
}

function vg_best_beaches_ops_internal_path_from_href(string $href): ?string
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

function vg_best_beaches_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_best_beaches_ops_internal_path_from_href($href);

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
        vg_best_beaches_ops_fail(implode(PHP_EOL, $failures));
    }

    vg_best_beaches_ops_log("Validated {$label} internal page links are published.");
}

function vg_best_beaches_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_best_beaches_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_best_beaches_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

function vg_best_beaches_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
{
    $marked_block = $marker . "\n" . $block;

    if (str_contains($page->post_content, $marked_block)) {
        vg_best_beaches_ops_log("Skipped {$label}: current block already present.");
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
            vg_best_beaches_ops_fail("Could not confidently refresh {$label}.");
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
        vg_best_beaches_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_best_beaches_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_best_beaches_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_best_beaches_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_best_beaches_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_best_beaches_ops_published_page_exists('destinations/best-beaches-in-vietnam')) {
        vg_best_beaches_ops_log('Skipped Destinations hub refresh: Best Beaches in Vietnam guide is not published.');
        return;
    }

    $marker = '<!-- vg-best-beaches-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Choose beach time by route and season, not by sand alone</h3><p>The <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a> guide helps travelers decide whether beach time belongs in the route at all, and if so which beach cluster fits the season, flight path, and trip length.</p></div>
<!-- /wp:group -->
HTML;

    vg_best_beaches_ops_assert_internal_page_links_are_published('Destinations hub Best Beaches note', $block);
    vg_best_beaches_ops_upsert_marked_group($hub, 'Destinations hub Best Beaches note', $marker, $block);
}

function vg_best_beaches_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_best_beaches_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_best_beaches_ops_assert_internal_page_links_are_published("{$label} related route line", $route_line);

    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);

    if (str_contains($current, '/destinations/best-beaches-in-vietnam/')) {
        vg_best_beaches_ops_log("Skipped related-route refresh for {$label}: Best Beaches guide is already present.");
        return;
    }

    $next = trim($current) === '' ? $route_line : rtrim($current) . "\n" . $route_line;
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_best_beaches_ops_log("Added Best Beaches guide related route to {$label}: {$page->ID}");
}

function vg_best_beaches_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Best Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ | Decide whether beach time belongs in the route and which beach cluster fits the month, flight path, travel style, budget, and trip length.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/best-islands-in-vietnam' => 'Best Islands in Vietnam guide',
            'destinations/phu-quoc-travel-guide' => 'Phu Quoc Travel Guide',
            'compare/phu-quoc-vs-nha-trang' => 'Phu Quoc vs Nha Trang guide',
            'destinations/con-dao-travel-guide' => 'Con Dao Travel Guide',
            'destinations/nha-trang-travel-guide' => 'Nha Trang Travel Guide',
            'destinations/quy-nhon-travel-guide' => 'Quy Nhon Travel Guide',
            'compare/mui-ne-vs-nha-trang' => 'Mui Ne vs Nha Trang guide',
            'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
            'compare/da-nang-vs-hoi-an' => 'Da Nang vs Hoi An guide',
            'destinations/cham-islands-travel-guide' => 'Cham Islands Travel Guide',
            'destinations/ly-son-travel-guide' => 'Ly Son Travel Guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
            'destinations/best-things-to-do-in-hue' => 'Best Things to Do in Hue guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
            'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ] as $path => $label
    ) {
        vg_best_beaches_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_best_beaches_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/best-beaches-in-vietnam', OBJECT, 'page');

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_best_beaches_ops_force_republish_enabled()) {
    vg_best_beaches_ops_fail('Best Beaches in Vietnam guide is not a draft. Set VG_FORCE_BEST_BEACHES_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_best_beaches_ops_log("Preflight Best Beaches in Vietnam guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_best_beaches_ops_log('Preflight Best Beaches in Vietnam guide: no existing page found; creating a child page under /destinations/.');
}

$phu_quoc_hero = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$phu_quoc_hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$my_khe_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/a/af/My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg/1920px-My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg');
$my_khe_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg');
$an_bang_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b0/2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg/1920px-2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg');
$an_bang_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg');
$nha_trang_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Nha_Trang_Beach_3.jpg/1920px-Nha_Trang_Beach_3.jpg');
$nha_trang_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Nha_Trang_Beach_3.jpg');
$mui_ne_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/2d/Vietnam%2C_Mui_Ne_beach%2C_Kitesurfing_on_the_beach.jpg/1920px-Vietnam%2C_Mui_Ne_beach%2C_Kitesurfing_on_the_beach.jpg');
$mui_ne_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Mui_Ne_beach,_Kitesurfing_on_the_beach.jpg');
$con_dao_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/C%C3%B4n_%C4%90%E1%BA%A3o_National_Park.jpg/1920px-C%C3%B4n_%C4%90%E1%BA%A3o_National_Park.jpg');
$con_dao_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:C%C3%B4n_%C4%90%E1%BA%A3o_National_Park.jpg');
$quy_nhon_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6d/Ky_Co_beach%2C_Quy_Nhon_city%2C_Binh_Dinh_province%2C_Vietnam.jpg/1920px-Ky_Co_beach%2C_Quy_Nhon_city%2C_Binh_Dinh_province%2C_Vietnam.jpg');
$quy_nhon_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ky_Co_beach,_Quy_Nhon_city,_Binh_Dinh_province,_Vietnam.jpg');
$cat_ba_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/64/Cat_Ba_Island%2C_Vietnam.JPG/1920px-Cat_Ba_Island%2C_Vietnam.JPG');
$cat_ba_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cat_Ba_Island,_Vietnam.JPG');

$content = <<<HTML
<!-- vg-best-beaches-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$phu_quoc_hero}","dimRatio":55,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Kem Beach on Phu Quoc Island, Vietnam" src="{$phu_quoc_hero}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 25, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Best Beaches in Vietnam: Where to Go by Month and Trip Style</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">The best beach in Vietnam depends on month, route shape, and travel style. If you only need one safe first-trip beach answer, choose Da Nang/My Khe or Hoi An/An Bang. Choose Phu Quoc when the trip should become beach-led, Con Dao for quiet luxury, Nha Trang for city-beach activity, Mui Ne for wind sports, Quy Nhon for quieter value, and Cat Ba only when the north needs an island-and-bay base.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: This is not a prettiest-beach ranking; it is a beach decision guide. The right beach is the one that still works after season, flights, ferries, children, budget, and the next transfer are counted.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<p class="vg-image-credit">Hero image: Kem Beach, Phu Quoc by Vivu Vietnam, CC BY-SA 4.0.</p>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-best-beaches-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-best-beaches-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-best-beaches-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most first-time visitors, the easiest beach choice is the central coast: Da Nang/My Khe if you want the simplest beach base, or Hoi An/An Bang if you want beach time wrapped around old-town days.</strong> Choose Phu Quoc when the trip should feel beach-led and island-like, Con Dao when quiet premium matters more than convenience, Nha Trang when you want an urban beach with boat activity, Mui Ne when wind and kitesurfing are part of the point, and Quy Nhon or Phu Quy when you want a quieter mainland or island value play.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-best-beaches-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-best-beaches-shortlist">
<li><strong>Best first-trip default:</strong> Da Nang/My Khe plus Hoi An/An Bang when the beach needs to fit a broader central route.</li>
<li><strong>Best island resort pick:</strong> Phu Quoc when the beach itself is the trip, not an add-on.</li>
<li><strong>Best quiet premium pick:</strong> Con Dao when you care more about space and calm than convenience.</li>
<li><strong>Best watch-your-route rule:</strong> skip beach time first when it only adds a flight, ferry, or transfer without improving the route.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-best-beaches-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-best-beaches-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-best-beaches-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fastest useful answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-beaches-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Best beach for a first Vietnam trip?</strong></td><td data-label="Answer">Usually Da Nang/My Khe or Hoi An/An Bang, because they fit central routes without turning the trip into a pure beach holiday.</td></tr>
<tr><td data-label="Question"><strong>Best beach for island resort time?</strong></td><td data-label="Answer">Phu Quoc if the season and flight path support a beach-led trip.</td></tr>
<tr><td data-label="Question"><strong>Best beach for quiet premium?</strong></td><td data-label="Answer">Con Dao, if you are willing to pay for access and calm rather than convenience.</td></tr>
<tr><td data-label="Question"><strong>Best beach for wind sports?</strong></td><td data-label="Answer">Mui Ne, when you want kitesurfing or a sport-first beach rather than a still-water resort.</td></tr>
<tr><td data-label="Question"><strong>Main mistake?</strong></td><td data-label="Answer">Adding beach time to a route that is already full, then discovering the transfer cost was the real beach tax.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-best-beaches-source-diversity:v1 -->
<!-- wp:group {"className":"vg-source-diversity vg-best-beaches-source-diversity","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-source-diversity vg-best-beaches-source-diversity">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Source discipline</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What sources can and cannot prove</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Sources can confirm destination context, season pressure, transport friction, and named beach clusters; they cannot decide whether your route needs rest, activity, quiet, nightlife, children-friendly logistics, or a cleaner skip. This guide uses official destination context, weather and transport references, image-license records, and internal route pages as inputs, then makes an editorial beach decision from the traveler's constraints.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- vg-best-beaches-source-trail-snapshot:v1 -->
<!-- wp:group {"className":"vg-source-snapshot vg-best-beaches-source-trail-snapshot","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-source-snapshot vg-best-beaches-source-trail-snapshot">
<!-- wp:heading -->
<h2 class="wp-block-heading">Source trail snapshot for this beach decision</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Official destination context checked: Vietnam.travel Phu Quoc - https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc; Con Dao - https://vietnam.travel/places-to-go/southern-vietnam/con-dao; Da Nang - https://vietnam.travel/places-to-go/central-vietnam/da-nang; Hoi An - https://vietnam.travel/places-to-go/central-vietnam/hoi-an; Nha Trang - https://vietnam.travel/places-to-go/central-vietnam/nha-trang; Phu Quy - https://vietnam.travel/things-to-do/phu-quy-vietnam-island-destination; weather - https://vietnam.travel/things-to-do/weather-and-climate-vietnam; transport - https://vietnam.travel/plan-your-trip/transport-within-vietnam. Image credits are listed as text to keep the beach decision readable and reduce visible outbound clutter.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- vg-best-beaches-chooser:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Start here: choose the beach by job</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The best beach is the one with the clearest job. Start with the travel problem, then choose the coast or island that solves it with the least route damage.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-beaches-chooser">
<thead>
<tr><th>What you actually need</th><th>Best first choice</th><th>Why it fits</th><th>Avoid if...</th></tr>
</thead>
<tbody>
<tr><td data-label="What you actually need">One safe first-trip beach answer</td><td data-label="Best first choice">Da Nang/My Khe or Hoi An/An Bang</td><td data-label="Why it fits">Central Vietnam lets beach time sit beside Hoi An, Hue, food, airport access, and a realistic first route.</td><td data-label="Avoid if...">The trip is already north-only or south-only and central Vietnam would add a weak flight.</td></tr>
<tr><td data-label="What you actually need">Island resort holiday</td><td data-label="Best first choice">Phu Quoc</td><td data-label="Why it fits">The beach can become the main chapter, with resorts and flights doing the work.</td><td data-label="Avoid if...">You only have one spare night or need a cultural middle more than resort recovery.</td></tr>
<tr><td data-label="What you actually need">Quiet luxury / honeymoon</td><td data-label="Best first choice">Con Dao</td><td data-label="Why it fits">It earns its cost through calm, space, and a slower premium island mood.</td><td data-label="Avoid if...">Convenience, nightlife, or easy backup plans matter more than quiet.</td></tr>
<tr><td data-label="What you actually need">City beach with activity</td><td data-label="Best first choice">Nha Trang</td><td data-label="Why it fits">Hotels, dining, boat trips, and urban movement stay easy.</td><td data-label="Avoid if...">You want quiet nature more than an active beach city.</td></tr>
<tr><td data-label="What you actually need">Wind sports</td><td data-label="Best first choice">Mui Ne</td><td data-label="Why it fits">The beach is strongest when wind, kitesurfing, and open coast are part of the reason.</td><td data-label="Avoid if...">You expect calm-water resort swimming as the main value.</td></tr>
<tr><td data-label="What you actually need">Budget-conscious beach time</td><td data-label="Best first choice">Quy Nhon or Da Nang</td><td data-label="Why it fits">Both can keep beach days connected to mainland transport and simpler hotel logistics.</td><td data-label="Avoid if...">The cheaper room is far from the beach stretch you actually want.</td></tr>
<tr><td data-label="What you actually need">North-only route relief</td><td data-label="Best first choice">Cat Ba only if island/bay value is already needed</td><td data-label="Why it fits">Cat Ba can combine island base, bay access, and some beach texture.</td><td data-label="Avoid if...">You want a classic beach holiday rather than a mixed bay-and-island chapter.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-beaches-month-planner:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Month-by-month beach planner</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use months as a filter, not a promise. Weather is regional and live conditions still matter, but this planner keeps the first shortlist realistic.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-beaches-month-planner">
<thead>
<tr><th>Month window</th><th>Lean toward</th><th>Be careful with</th><th>Route move</th></tr>
</thead>
<tbody>
<tr><td data-label="Month window">January-February</td><td data-label="Lean toward">Phu Quoc, Con Dao, Mui Ne, and south-first beach chapters.</td><td data-label="Be careful with">Forcing central beaches if the trip mainly needs reliable sun.</td><td data-label="Route move">Let southern beach logic lead, then add north/central only when time allows.</td></tr>
<tr><td data-label="Month window">March-April</td><td data-label="Lean toward">Da Nang/My Khe, Hoi An/An Bang, Nha Trang, Quy Nhon, Phu Quoc, or Con Dao.</td><td data-label="Be careful with">Trying to compare every coast in one trip.</td><td data-label="Route move">Choose one beach region and protect the rest of the route.</td></tr>
<tr><td data-label="Month window">May-August</td><td data-label="Lean toward">Central coast and south-central beaches: Da Nang, Hoi An, Nha Trang, Quy Nhon, and Mui Ne if sport fits.</td><td data-label="Be careful with">Heat, exposed midday travel, and beach hotels far from food or shade.</td><td data-label="Route move">Use beach mornings/evenings and keep inland days flexible.</td></tr>
<tr><td data-label="Month window">September-November</td><td data-label="Lean toward">Flexible city, food, heritage, or south-first pivots rather than a fragile central beach promise.</td><td data-label="Be careful with">Central beach-first itineraries with no backup day.</td><td data-label="Route move">Treat beach time as optional until live weather confirms the plan.</td></tr>
<tr><td data-label="Month window">December</td><td data-label="Lean toward">Southern islands and warmer south-coast recovery.</td><td data-label="Be careful with">Expecting one national beach season across the whole country.</td><td data-label="Route move">Pick the beach after deciding whether the trip starts north, central, or south.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-beaches-traveler-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best beach by traveler type</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-beaches-traveler-fit">
<thead>
<tr><th>Traveler type</th><th>Best fit</th><th>Why</th><th>Watch before booking</th></tr>
</thead>
<tbody>
<tr><td data-label="Traveler type">Families with children</td><td data-label="Best fit">Da Nang/My Khe, Hoi An/An Bang, or Phu Quoc</td><td data-label="Why">Simpler hotel choice, food access, flight logic, and fewer fragile transfers.</td><td data-label="Watch before booking">Exact beach stretch, pool backup, shade, transfer time, and medical access.</td></tr>
<tr><td data-label="Traveler type">Quiet luxury / honeymoon</td><td data-label="Best fit">Con Dao or a well-chosen Phu Quoc resort</td><td data-label="Why">The beach chapter can become calm, spacious, and deliberate.</td><td data-label="Watch before booking">Flight/ferry backup, cancellation terms, and whether quiet means isolated.</td></tr>
<tr><td data-label="Traveler type">Budget-conscious beach time</td><td data-label="Best fit">Da Nang, Quy Nhon, or Nha Trang</td><td data-label="Why">Mainland logistics can keep costs lower than remote island routing.</td><td data-label="Watch before booking">Cheap rooms can sit far from the beach or the evening area.</td></tr>
<tr><td data-label="Traveler type">Solo travelers</td><td data-label="Best fit">Da Nang, Hoi An/An Bang, Nha Trang, or Mui Ne</td><td data-label="Why">Movement, food, social options, and activity booking are easier.</td><td data-label="Watch before booking">Night movement, ride-hailing coverage, and hotel location.</td></tr>
<tr><td data-label="Traveler type">Beach-plus-culture travelers</td><td data-label="Best fit">Hoi An/An Bang or Da Nang/My Khe</td><td data-label="Why">Beach time supports old town, food, Hue, My Son, and central route value.</td><td data-label="Watch before booking">Do not let beach hotels make heritage days awkward.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-beaches-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what each beach actually feels like</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Beach choice in Vietnam is not a sand-color contest. The photos below show the route types that matter: island resort, city beach, old-town beach, open wind, quiet premium, low-key value, and beach-adjacent island bases.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-best-beaches-photo-grid" aria-label="Best Beaches in Vietnam photography">
<figure class="vg-guide-photo"><img src="{$phu_quoc_hero}" alt="Kem Beach on Phu Quoc Island" loading="lazy" decoding="async"><figcaption>Phu Quoc works when the beach is the trip. Image: Vivu Vietnam, CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$my_khe_image}" alt="My Khe Beach in Da Nang" loading="lazy" decoding="async"><figcaption>My Khe is the easiest central-coast beach base when the route also needs a city and airport. Image: Christophe95, CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$an_bang_image}" alt="An Bang Beach in Hoi An" loading="lazy" decoding="async"><figcaption>An Bang works best when beach time sits beside Hoi An, not instead of it. Image: Alexkom000, CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$nha_trang_image}" alt="Nha Trang Beach in central Vietnam" loading="lazy" decoding="async"><figcaption>Nha Trang is a city-beach decision, not a quiet-island one. Image: Christophe95, CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mui_ne_image}" alt="Kitesurfing at Mui Ne beach" loading="lazy" decoding="async"><figcaption>Mui Ne matters when wind sports are part of the plan. Image: Vyacheslav Argenberg, CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$con_dao_image}" alt="Con Dao National Park on Con Dao Islands" loading="lazy" decoding="async"><figcaption>Con Dao should be bought as a quiet island escape, not a casual beach stop. Image: Tycho, CC BY-SA 3.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$quy_nhon_image}" alt="Ky Co beach near Quy Nhon in Binh Dinh province" loading="lazy" decoding="async"><figcaption>Quy Nhon and Ky Co are for travelers who want quieter value without going all the way to a remote island. Image: Le Ho Bac, CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cat_ba_image}" alt="Cat Ba Island, a beach-adjacent northern base in Vietnam" loading="lazy" decoding="async"><figcaption>Cat Ba is a beach chapter only when island and bay value already justify the north. Image: Pather alexiy, CC BY-SA 3.0.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-best-beaches-cluster-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Which beach cluster fits which trip?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Choose the beach cluster by the problem it solves. A beach that requires a separate flight or ferry should earn its place by changing the route, not just by looking good in a brochure.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-beaches-cluster-matrix">
<thead>
<tr><th>Beach cluster</th><th>Best when</th><th>What improves</th><th>What to verify</th><th>Source/check</th></tr>
</thead>
<tbody>
<tr><td data-label="Beach cluster">Phu Quoc</td><td data-label="Best when">You want the beach to be the trip and are happy to build around island flights.</td><td data-label="What improves">Resort time, easy rest, and a clear beach-led finish.</td><td data-label="What to verify">Flight path, season, beach condition, and resort location.</td><td data-label="Source/check">Vietnam.travel Phu Quoc - https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc</td></tr>
<tr><td data-label="Beach cluster">Con Dao</td><td data-label="Best when">You want a quiet premium escape and do not mind access friction.</td><td data-label="What improves">Space, calm, and a less crowded island feel.</td><td data-label="What to verify">Flights/ferries, weather windows, and total trip cost.</td><td data-label="Source/check">Vietnam.travel Con Dao - https://vietnam.travel/places-to-go/southern-vietnam/con-dao</td></tr>
<tr><td data-label="Beach cluster">Da Nang / My Khe</td><td data-label="Best when">You want the easiest beach base that still fits a central route.</td><td data-label="What improves">Airport convenience, hotel choice, and route simplicity.</td><td data-label="What to verify">Hotel position on the beach strip, swim conditions, and crowds.</td><td data-label="Source/check">Vietnam.travel Da Nang - https://vietnam.travel/places-to-go/central-vietnam/da-nang</td></tr>
<tr><td data-label="Beach cluster">Hoi An / An Bang</td><td data-label="Best when">You want beach time wrapped around old town, food, and slower central days.</td><td data-label="What improves">A more balanced central Vietnam chapter.</td><td data-label="What to verify">Whether the beach is a main goal or a bonus, and how weather affects it.</td><td data-label="Source/check">Vietnam.travel Hoi An - https://vietnam.travel/places-to-go/central-vietnam/hoi-an</td></tr>
<tr><td data-label="Beach cluster">Nha Trang</td><td data-label="Best when">You want a city beach with boat trips and more activity.</td><td data-label="What improves">Easy movement, dining, and urban convenience.</td><td data-label="What to verify">Beach segment quality, weather, and boat-day expectations.</td><td data-label="Source/check">Vietnam.travel Nha Trang - https://vietnam.travel/places-to-go/central-vietnam/nha-trang</td></tr>
<tr><td data-label="Beach cluster">Mui Ne</td><td data-label="Best when">Wind sports and open sand matter more than quiet swimming.</td><td data-label="What improves">Kitesurfing, open space, and a sport-first beach mood.</td><td data-label="What to verify">Wind season, sport operator quality, and transfer time.</td><td data-label="Source/check">Vietnam.travel Mui Ne source - https://vietnam.travel/node/708</td></tr>
<tr><td data-label="Beach cluster">Quy Nhon / Ky Co</td><td data-label="Best when">You want a quieter mainland value play with fewer crowds.</td><td data-label="What improves">Lower-key beach days and a less overbuilt feel.</td><td data-label="What to verify">Which beach stretch you are actually staying near and how you reach it.</td><td data-label="Source/check">Vietnam.travel Quy Nhon source - https://vietnam.travel/node/1453</td></tr>
<tr><td data-label="Beach cluster">Cat Ba / Lan Ha</td><td data-label="Best when">Beach time is secondary to island, bay, and north-route value.</td><td data-label="What improves">A mixed beach-and-bay chapter.</td><td data-label="What to verify">Whether you really want a beach or actually want an island/bay base.</td><td data-label="Source/check"><a href="/destinations/cat-ba-travel-guide/">Cat Ba Travel Guide</a></td></tr>
<tr><td data-label="Beach cluster">Phu Quy</td><td data-label="Best when">You want an off-radar island beach and can tolerate ferry/weather friction.</td><td data-label="What improves">A quieter island pattern with strong local texture.</td><td data-label="What to verify">Sea state, ferry schedule, and buffer days.</td><td data-label="Source/check">Vietnam.travel Phu Quy - https://vietnam.travel/things-to-do/phu-quy-vietnam-island-destination</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-beaches-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where beach time fits in the wider route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Beach time is easiest to justify when it either shortens the route or becomes the emotional middle of the trip. It is weakest when it only adds another flight, ferry, or transfer day.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-beaches-route-fit">
<thead>
<tr><th>Route shape</th><th>Best beach use</th><th>Risk</th><th>Related guide</th></tr>
</thead>
<tbody>
<tr><td data-label="Route shape">7-8 days</td><td data-label="Best beach use">Choose one beach cluster only, or skip beaches entirely if they add too much movement.</td><td data-label="Risk">Turning a short route into a transfer schedule.</td><td data-label="Related guide"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">10 days in Vietnam</td><td data-label="Best beach use">Central coast beach time can work if Hanoi/Ninh Binh/bay time are already simplified.</td><td data-label="Risk">Adding both an island and a central-coast beach to the same short trip.</td><td data-label="Related guide"><a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a></td></tr>
<tr><td data-label="Route shape">14 days in Vietnam</td><td data-label="Best beach use">Add one beach chapter as contrast, not as a separate mini-holiday.</td><td data-label="Risk">Beach fatigue from trying to compare too many stretches.</td><td data-label="Related guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">Central Vietnam first</td><td data-label="Best beach use">Da Nang/My Khe or Hoi An/An Bang can anchor the route cleanly.</td><td data-label="Risk">Treating weather as if all central beaches behave the same way.</td><td data-label="Related guide"><a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a></td></tr>
<tr><td data-label="Route shape">South-first or island-led</td><td data-label="Best beach use">Phu Quoc, Con Dao, or Phu Quy can carry the trip if the schedule supports it.</td><td data-label="Risk">Using an island beach as a last-minute add-on when flights are already fixed.</td><td data-label="Related guide"><a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a></td></tr>
<tr><td data-label="Route shape">Island or bay route</td><td data-label="Best beach use">Cat Ba, Lan Ha, and Ha Long are beach-adjacent options rather than pure beach holidays.</td><td data-label="Risk">Expecting quiet resort beach time from a bay itinerary.</td><td data-label="Related guide"><a href="/destinations/cat-ba-travel-guide/">Cat Ba Travel Guide</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-beaches-season-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best time and weather pivots</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Beach timing in Vietnam is regional, not national. Central coasts, southern islands, and city beaches can all behave differently in the same month.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-beaches-season-fit">
<thead>
<tr><th>Season pressure</th><th>What it changes</th><th>Planning move</th></tr>
</thead>
<tbody>
<tr><td data-label="Season pressure">December-April</td><td data-label="What it changes">Southern island and beach value usually improves.</td><td data-label="Planning move">Favor Phu Quoc, Con Dao, or a south-first beach chapter.</td></tr>
<tr><td data-label="Season pressure">March-August</td><td data-label="What it changes">Central coast beaches often become easier to use.</td><td data-label="Planning move">Favor Da Nang/My Khe, Hoi An/An Bang, Nha Trang, or Quy Nhon.</td></tr>
<tr><td data-label="Season pressure">September-November</td><td data-label="What it changes">Central Vietnam beach plans need more flexibility.</td><td data-label="Planning move">Do not let a beach day force a weak route; keep an alternate city or heritage day ready.</td></tr>
<tr><td data-label="Season pressure">Wind or sport windows</td><td data-label="What it changes">Mui Ne and some open-beach days become sport decisions, not calm-swim decisions.</td><td data-label="Planning move">Check live conditions and the operator's actual sport setup before paying.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-beaches-booking-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Booking checks before you commit</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The beach should improve the route, not just the mood board.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-best-beaches-booking-checks"} -->
<ul class="wp-block-list vg-check-list vg-best-beaches-booking-checks">
<li>Ask what the beach is doing for the route: rest, resort time, sport, island calm, or city convenience.</li>
<li>Confirm the beach stretch you are actually staying near, not just the city or island name.</li>
<li>Check whether the season supports swimming, sun, wind sports, or only a scenic stop.</li>
<li>Read flight, ferry, weather, and cancellation terms before paying a deposit for island or offshore beaches.</li>
<li>Do not use a beach add-on to hide an overfull route.</li>
<li>If you need a central beach, compare it against Hoi An, Hue, and the rest of the route before booking.</li>
</ul>
<!-- /wp:list -->

<!-- vg-best-beaches-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Beach time is often the first thing to remove when the route is too tight. That is not anti-beach. That is route hygiene.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-beaches-skip-logic">
<thead>
<tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you want...">A first Vietnam trip</td><td data-label="Protect this">Hanoi, one northern landscape stop, and one central or southern beach chapter.</td><td data-label="Skip first">Two beach regions plus a bay plus a mountain stop.</td><td data-label="Only add back when...">You have enough nights to keep the route breathing.</td></tr>
<tr><td data-label="If you want...">Quiet beach time</td><td data-label="Protect this">A calmer island or lower-key coast.</td><td data-label="Skip first">Urban beach choices if crowds are the main complaint.</td><td data-label="Only add back when...">The city beach is the only one that fits the route.</td></tr>
<tr><td data-label="If you want...">Water sports</td><td data-label="Protect this">Wind, operator quality, and the right stretch of coast.</td><td data-label="Skip first">Calm-resort beach expectations.</td><td data-label="Only add back when...">You actually want sport-first beach time.</td></tr>
<tr><td data-label="If you want...">Beach with culture</td><td data-label="Protect this">Hoi An, Hue, or a central route that leaves room for the beach.</td><td data-label="Skip first">A far island that steals days from the cultural middle.</td><td data-label="Only add back when...">The beach improves the route more than another inland stop would.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-beaches-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before travel</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-best-beaches-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-best-beaches-live-checks">
<li>Check Vietnam.travel Phu Quoc and Con Dao pages for southern island orientation before booking a beach-led route.</li>
<li>Check Vietnam.travel Da Nang, Hoi An, and Nha Trang pages for central-coast and city-beach context before choosing a base.</li>
<li>Check Vietnam.travel Mui Ne and Quy Nhon source pages when the beach decision depends on wind sports, quieter value, or south-central coast routing.</li>
<li>Check Vietnam.travel Phu Quy if you want a more off-radar island beach and can accept ferry friction.</li>
<li>Check Vietnam.travel weather and climate plus transport references before locking an island, ferry, or beach-heavy route.</li>
<li>Read <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a> before committing beach money.</li>
</ul>
<!-- /wp:list -->

<!-- vg-best-beaches-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best Beaches in Vietnam FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-best-beaches-faq">
<details><summary>What is the best beach in Vietnam for first-time visitors?</summary><p>Usually a central-coast beach base such as Da Nang/My Khe or Hoi An/An Bang, because it fits more routes without requiring a beach-only trip.</p></details>
<details><summary>What is the best beach in Vietnam for an island holiday?</summary><p>Phu Quoc if the season and flight path support it, with Con Dao as the quieter premium alternative.</p></details>
<details><summary>What beach is best for quiet luxury?</summary><p>Con Dao, if you are comfortable paying for access and space rather than convenience.</p></details>
<details><summary>What beach is best for kitesurfing or wind sports?</summary><p>Mui Ne, when the wind and operator setup match the sport you actually want.</p></details>
<details><summary>What beach is best for a short route?</summary><p>Pick one beach chapter only, or skip beaches first if they would force another flight or ferry.</p></details>
<details><summary>Should I choose a beach or a bay?</summary><p>Choose the chapter that changes the route more. If the bay already gives you the scenic chapter, a beach may be unnecessary unless it adds rest or a new climate.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> for the full planning order, compare the broader destination shortlist in <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>, then check route pressure in <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>. For beach-adjacent northern or central routes, cross-check <a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a>, <a href="/destinations/best-things-to-do-in-hue/">Best Things to Do in Hue</a>, <a href="/destinations/cat-ba-travel-guide/">Cat Ba Travel Guide</a>, <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>, and <a href="/destinations/bai-tu-long-bay-guide/">Bai Tu Long Bay Guide</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-best-beaches-hero:v1',
    'concierge verdict' => 'vg-best-beaches-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-best-beaches-at-a-glance:v1',
    'source diversity' => 'vg-best-beaches-source-diversity:v1',
    'source trail snapshot' => 'vg-best-beaches-source-trail-snapshot:v1',
    'beach chooser' => 'vg-best-beaches-chooser:v1',
    'month planner' => 'vg-best-beaches-month-planner:v1',
    'traveler fit' => 'vg-best-beaches-traveler-fit:v1',
    'photo grid' => 'vg-best-beaches-photo-grid:v1',
    'cluster matrix' => 'vg-best-beaches-cluster-matrix:v1',
    'route fit' => 'vg-best-beaches-route-fit:v1',
    'season fit' => 'vg-best-beaches-season-fit:v1',
    'booking checks' => 'vg-best-beaches-booking-checks:v1',
    'skip logic' => 'vg-best-beaches-skip-logic:v1',
    'live checks' => 'vg-best-beaches-live-checks:v1',
    'FAQ' => 'vg-best-beaches-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_best_beaches_ops_assert_required_content_markers($content, $required_content_markers);
vg_best_beaches_ops_assert_internal_page_links_are_published('Best Beaches in Vietnam guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Best Beaches in Vietnam',
    'post_name'      => 'best-beaches-in-vietnam',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_best_beaches_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led best beaches guide with a month-by-month beach planner for choosing Phu Quoc, Con Dao, Da Nang, Hoi An, Nha Trang, Mui Ne, Quy Nhon, Cat Ba, or Phu Quy.',
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
    vg_best_beaches_ops_fail('Could not publish Best Beaches in Vietnam guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_best_beaches_ops_fail('Could not publish Best Beaches in Vietnam guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Best Beaches in Vietnam: Where to Go by Month');
update_post_meta($page_id, 'rank_math_description', 'Choose the best beach in Vietnam by month, route, and trip style: Phu Quoc, Da Nang, Hoi An, Nha Trang, Con Dao, Mui Ne, Quy Nhon, Cat Ba, or Phu Quy.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'best beaches in Vietnam');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether beach time belongs in the route and which Vietnamese beach cluster fits the season, flight path, and trip length.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 25, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Hardened the Best Beaches in Vietnam guide after Google Search Console showed high impressions and top-five average page position but weak CTR. Added a clearer month-and-trip-style title/meta, source-diversity and source-snapshot bands, a beach chooser, month-by-month planner, traveler-fit table, text-only image credits, lower visible outbound clutter, richer related routes, and stronger verifier coverage.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Phu Quoc - https://vietnam.travel/places-to-go/southern-vietnam/phu-quoc - checked July 18, 2026\nVietnam.travel - Con Dao - https://vietnam.travel/places-to-go/southern-vietnam/con-dao - checked July 18, 2026\nVietnam.travel - Da Nang - https://vietnam.travel/places-to-go/central-vietnam/da-nang - checked July 18, 2026\nVietnam.travel - Hoi An - https://vietnam.travel/places-to-go/central-vietnam/hoi-an - checked July 18, 2026\nVietnam.travel - Nha Trang - https://vietnam.travel/places-to-go/central-vietnam/nha-trang - checked July 18, 2026\nVietnam.travel - Phu Quy island destination - https://vietnam.travel/things-to-do/phu-quy-vietnam-island-destination - checked July 18, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 18, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 18, 2026\nVietnam.travel Mui Ne article - https://vietnam.travel/node/708 - checked July 18, 2026\nVietnam.travel Quy Nhon article - https://vietnam.travel/node/1453 - checked July 18, 2026\nWikimedia Commons image record - Kem Beach aerial view Phu Quoc Island Vietnam - https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg - license checked July 18, 2026\nWikimedia Commons image record - My Khe Beach seen from the Son Tra Mountain - https://commons.wikimedia.org/wiki/File:My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg - license checked July 18, 2026\nWikimedia Commons image record - 2024-11-23 An Bang Beach in Hoi An in November - https://commons.wikimedia.org/wiki/File:2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg - license checked July 18, 2026\nWikimedia Commons image record - Nha Trang Beach 3 - https://commons.wikimedia.org/wiki/File:Nha_Trang_Beach_3.jpg - license checked July 18, 2026\nWikimedia Commons image record - Vietnam, Mui Ne beach, Kitesurfing on the beach - https://commons.wikimedia.org/wiki/File:Vietnam,_Mui_Ne_beach,_Kitesurfing_on_the_beach.jpg - license checked July 18, 2026\nWikimedia Commons image record - Con Dao National Park - https://commons.wikimedia.org/wiki/File:C%C3%B4n_%C4%90%E1%BA%A3o_National_Park.jpg - license checked July 18, 2026\nWikimedia Commons image record - Ky Co beach, Quy Nhon city, Binh Dinh province, Vietnam - https://commons.wikimedia.org/wiki/File:Ky_Co_beach,_Quy_Nhon_city,_Binh_Dinh_province,_Vietnam.jpg - license checked July 18, 2026\nWikimedia Commons image record - Cat Ba Island, Vietnam - https://commons.wikimedia.org/wiki/File:Cat_Ba_Island,_Vietnam.JPG - license checked July 18, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Beach decisions in Vietnam are route decisions: the best beach is the one that still makes sense after season, flights, ferries, and route length are counted.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Best-beach guide built around route-fit judgment rather than generic ranking copy.\nConcierge verdict that separates island resort, quiet premium, central-coast base, city beach, wind-sport, and value beach choices.\nAt-a-glance table answering the first useful beach question for international travelers.\nSource-diversity module that explains what official sources can and cannot prove.\nRendered source-trail snapshot with official source URLs as text, reducing visible outbound clutter while preserving auditability.\nBeach chooser table for first-trip, island resort, quiet luxury, city beach, wind sports, budget, and north-only route decisions.\nMonth-by-month beach planner for January-February, March-April, May-August, September-November, and December.\nTraveler-fit table for families with children, quiet luxury / honeymoon, budget-conscious beach time, solo travelers, and beach-plus-culture travelers.\nText-only image credits for island, city, old-town, sport, quiet, value, and island-base beach contexts.\nBeach cluster matrix comparing Phu Quoc, Con Dao, Da Nang/My Khe, Hoi An/An Bang, Nha Trang, Mui Ne, Quy Nhon, Cat Ba/Lan Ha, and Phu Quy by route role.\nRoute-fit table for short, 10-day, 14-day, central-first, south-first, and island/bay routes.\nSeason-fit table grounded in central, southern, and wind-sensitive beach planning.\nBooking checks for route purpose, beach stretch, season, transfer, and weather reality.\nSkip logic that protects the route when beach time is only decorative.\nLive-check list tied to official tourism, weather, transport, and internal planning checks without excessive visible linkout.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Best Beaches in Vietnam | /destinations/best-beaches-in-vietnam/ | Use this page as the beach decision hub before choosing an island, city beach, central-coast base, or clean skip.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before deciding whether a beach chapter belongs.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Compare beach destinations against the broader shortlist before adding them.\nBest Islands in Vietnam | /destinations/best-islands-in-vietnam/ | Decide whether the beach should become a true island chapter or stay on the mainland.\nPhu Quoc Travel Guide | /destinations/phu-quoc-travel-guide/ | Use Phu Quoc when the route needs island resort recovery and beach-led time.\nPhu Quoc vs Nha Trang | /compare/phu-quoc-vs-nha-trang/ | Compare island resort recovery with active city-beach convenience before booking beach flights or Cam Ranh transfers.\nDa Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use Da Nang when central-coast beach time should stay easy, urban, and airport-connected.\nDa Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Decide whether central Vietnam should be beach-first, old-town-first, or split carefully.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Use region choice to decide whether the beach should be north, central, or south.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check season fit before booking beach flights, ferries, or resorts.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Price the transfer and ferry logic before adding a beach chapter.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget for resort nights, island flights, private transfers, and weather buffers.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a beach add-on still fits a short route.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when a beach chapter can earn its keep in a fuller route.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use a longer route to decide whether beach time deserves a real chapter instead of a rushed add-on.\nBest Things to Do in Hoi An | /destinations/best-things-to-do-in-hoi-an/ | Compare Hoi An's beach-adjacent days with central-coast alternatives.\nBest Things to Do in Hue | /destinations/best-things-to-do-in-hue/ | Keep central Vietnam beach timing honest against heritage days.\nCat Ba Travel Guide | /destinations/cat-ba-travel-guide/ | Use this when beach time is secondary to island and bay value.\nHa Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Compare beach intent with a northern bay cruise chapter.\nHa Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Compare route and island-bay value before treating Cat Ba as a beach substitute.\nBai Tu Long Bay Guide | /destinations/bai-tu-long-bay-guide/ | Keep a beach chapter separate from a quieter bay chapter.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check water, island, and transfer risk before the beach goes live.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair deposits, ferries, and water activities with practical risk checks.");
$best_beaches_related_routes = (string) get_post_meta($page_id, 'vg_eeat_related_routes', true);
$best_beaches_related_routes .= "\nCon Dao Travel Guide | /destinations/con-dao-travel-guide/ | Use Con Dao when quiet premium island time matters more than convenience.\nNha Trang Travel Guide | /destinations/nha-trang-travel-guide/ | Use Nha Trang when the beach chapter should stay urban, active, and easy to move around.\nQuy Nhon Travel Guide | /destinations/quy-nhon-travel-guide/ | Use Quy Nhon when quieter mainland beach value beats a remote island detour.\nMui Ne vs Nha Trang | /compare/mui-ne-vs-nha-trang/ | Choose between wind-sport beach logic and active city-beach convenience before booking the south-central coast.\nCham Islands Travel Guide | /destinations/cham-islands-travel-guide/ | Use Cham Islands only when Hoi An has enough slack for a marine day.\nLy Son Travel Guide | /destinations/ly-son-travel-guide/ | Use Ly Son when a central island detour earns its ferry friction.";
update_post_meta($page_id, 'vg_eeat_related_routes', $best_beaches_related_routes);
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and body images: Phu Quoc by Vivu Vietnam, My Khe and Nha Trang by Christophe95, An Bang by Alexkom000, Mui Ne by Vyacheslav Argenberg, Con Dao by Tycho, Quy Nhon by Le Ho Bac, and Cat Ba by Pather alexiy. Image credits are listed as text in captions to reduce visible outbound clutter.');
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
        vg_best_beaches_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_best_beaches_ops_fail('Best Beaches in Vietnam guide was updated but is not published.');
}

vg_best_beaches_ops_refresh_destinations_hub();
vg_best_beaches_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_best_beaches_ops_log("Published Best Beaches in Vietnam guide: {$page_id} {$updated_permalink}");

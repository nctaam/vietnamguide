<?php
/**
 * Publish the UNESCO Heritage Sites in Vietnam guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-unesco-heritage-sites-guide.php --allow-root
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
    $value = getenv('VG_FORCE_HERITAGE_GUIDE_REPUBLISH');

    if (! is_string($value)) {
        return false;
    }

    return trim($value) === '1';
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

    vg_ops_fail('Could not resolve a valid WordPress author for the UNESCO Heritage Sites in Vietnam guide.');
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

function vg_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

function vg_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post) {
        vg_ops_log('Skipped Destinations hub refresh: destinations page was not found.');
        return;
    }

    if ($hub->post_status !== 'publish') {
        vg_ops_log('Skipped Destinations hub refresh: destinations page is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('destinations/unesco-heritage-sites-vietnam')) {
        vg_ops_log('Skipped Destinations hub refresh: UNESCO Heritage Sites guide is not published.');
        return;
    }

    $marker = '<!-- vg-unesco-heritage-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Use heritage as a route filter</h3><p>The <a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a> guide helps travelers decide which heritage places deserve a real stop, which ones belong as context, and which ones should be skipped on short routes.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Destinations hub UNESCO heritage note', $block);

    $marked_block = $marker . "\n" . $block;

    if (str_contains($hub->post_content, $marked_block)) {
        vg_ops_log('Skipped Destinations hub refresh: current UNESCO heritage note already present.');
        return;
    }

    if (str_contains($hub->post_content, $marker)) {
        $updated_content = preg_replace(
            '/' . preg_quote($marker, '/') . '\R<!-- wp:group\b.*?<!-- \/wp:group -->/s',
            $marked_block,
            $hub->post_content,
            1,
            $replacement_count
        );

        if (! is_string($updated_content) || $replacement_count !== 1) {
            vg_ops_fail('Could not confidently refresh the Destinations hub UNESCO heritage note.');
        }
    } else {
        $updated_content = rtrim($hub->post_content) . "\n\n" . $marked_block;
    }

    $result = wp_update_post(
        [
            'ID'           => $hub->ID,
            'post_content' => $updated_content,
        ],
        true
    );

    if (is_wp_error($result)) {
        vg_ops_fail('Could not refresh Destinations hub note: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Destinations hub note: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Destinations hub UNESCO heritage note: {$hub->ID}");
}

function vg_ops_refresh_best_places_related_routes(): void
{
    $page = get_page_by_path('destinations/best-places-to-visit-vietnam', OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ops_log('Skipped Best Places related routes refresh: guide was not found or is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('destinations/unesco-heritage-sites-vietnam')) {
        vg_ops_log('Skipped Best Places related routes refresh: UNESCO Heritage Sites guide is not published.');
        return;
    }

    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);
    $needle = '/destinations/unesco-heritage-sites-vietnam/';

    if (str_contains($current, $needle)) {
        vg_ops_log('Skipped Best Places related routes refresh: heritage link already present.');
        return;
    }

    $updated = rtrim($current);

    if ($updated !== '') {
        $updated .= "\n";
    }

    $updated .= "UNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Use the heritage shortlist before adding more stops.";

    update_post_meta($page->ID, 'vg_eeat_related_routes', $updated);
    vg_ops_log("Refreshed Best Places related routes: {$page->ID}");
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/unesco-heritage-sites-vietnam', OBJECT, 'page');
$allow_republish = vg_ops_force_republish_enabled();

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('UNESCO Heritage Sites in Vietnam guide is not a draft. Set VG_FORCE_HERITAGE_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    $permalink = get_permalink($page);
    vg_ops_log("Preflight UNESCO Heritage Sites guide: {$page->ID} {$page->post_status} {$permalink}");
} else {
    vg_ops_log('Preflight UNESCO Heritage Sites guide: no existing page found; creating a child page under /destinations/.');
}

$ha_long_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg/1920px-Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg');
$guide_hero_image = $ha_long_image;
$thang_long_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Doan_Mon_Gate_1.jpg/1920px-Doan_Mon_Gate_1.jpg');
$ho_dynasty_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/06/Ho_dynasty%27s_citadel%2C_Vietnam.jpg/1920px-Ho_dynasty%27s_citadel%2C_Vietnam.jpg');
$trang_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');
$hue_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg/1920px-Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$hoi_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$my_son_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/7/7d/My_Son_Sanctuary_Vietnam_06.jpg/1920px-My_Son_Sanctuary_Vietnam_06.jpg');
$son_river_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f1/Son_River-Quang_Binh_province.jpg/1920px-Son_River-Quang_Binh_province.jpg');
$yen_tu_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/0/0b/Chua-yen-tu-ngay-nay.jpg');

$content = <<<HTML
<!-- vg-unesco-heritage-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$guide_hero_image}","dimRatio":55,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Limestone islands in Ha Long Bay, Vietnam, used as a UNESCO heritage guide hero image" src="{$guide_hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 25, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">UNESCO Heritage Sites in Vietnam: Which Ones Belong in Your Route</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Vietnam has 9 UNESCO World Heritage properties: 6 cultural, 2 natural, and 1 mixed. The useful question is not how many you can name, but which ones change a real itinerary for an international traveler with limited nights, weather risk, transfers, and a budget to protect.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">This is not a trophy-wall list. It is a route filter with 2025 source freshness, original skip logic, and practical judgment about which heritage stops deserve protected time.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<p class="vg-image-credit">Hero image: Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0.</p>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-unesco-heritage-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>If this is your first Vietnam trip, start with Trang An, Ha Long Bay - Cat Ba Archipelago, Hue, Hoi An, and My Son only when central Vietnam has a protected half-day.</strong> Add Thang Long inside a Hanoi stay, then consider Phong Nha, the Ho Dynasty citadel, or Yen Tu only when the route already has enough breathing room to make the detour worth it.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-unesco-heritage-first-trip-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-unesco-heritage-first-trip-shortlist">
<li><strong>Best first-trip core:</strong> Ha Long Bay - Cat Ba Archipelago, Trang An, Hue, Hoi An, and My Son when Hoi An or Da Nang already anchors central Vietnam.</li>
<li><strong>Best Hanoi low-friction add-on:</strong> Thang Long, because it can add capital-history context without changing bases.</li>
<li><strong>Best nature chapter:</strong> Phong Nha-Ke Bang and Hin Nam No when caves, karst, and national-park landscapes are a primary reason to stop.</li>
<li><strong>Best new-for-2025 watchlist:</strong> Yen Tu - Vinh Nghiem - Con Son, Kiep Bac, but only for return visitors or north-heavy routes with spiritual and historical interest.</li>
<li><strong>Best skip rule:</strong> do not add a World Heritage property if it steals the time needed to enjoy the sites already on the route.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-unesco-heritage-at-a-glance:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">At a glance: the route answer</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the UNESCO label after the route has a job. Vietnam's 9 UNESCO World Heritage properties are powerful anchors, but they do different work: some justify nights, some work as day trips, and some are better saved for a specialist return trip.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-unesco-heritage-at-a-glance">
<thead>
<tr><th>Traveler decision</th><th>Best heritage answer</th><th>Why it works</th><th>What to avoid</th></tr>
</thead>
<tbody>
<tr><td data-label="Traveler decision">I have 7-10 days</td><td data-label="Best heritage answer">Pick Trang An plus Ha Long Bay - Cat Ba, then choose either Hue/Hoi An or a cleaner regional route.</td><td data-label="Why it works">It gives the trip visible natural and cultural depth without turning every day into transport.</td><td data-label="What to avoid">Trying to collect north, central, and cave heritage in one short route.</td></tr>
<tr><td data-label="Traveler decision">I have 14 days</td><td data-label="Best heritage answer">Build around Hanoi, Trang An, the bay, Hue, Hoi An, and My Son.</td><td data-label="Why it works">This is the cleanest first-time heritage arc for many international visitors.</td><td data-label="What to avoid">Adding Phong Nha or Yen Tu unless the route has a real spare chapter.</td></tr>
<tr><td data-label="Traveler decision">I want nature first</td><td data-label="Best heritage answer">Choose Phong Nha-Ke Bang and Hin Nam No, Trang An, or Ha Long Bay - Cat Ba.</td><td data-label="Why it works">These are landscape-first World Heritage choices with different transfer and weather risks.</td><td data-label="What to avoid">Treating a cave, boat route, and cruise as interchangeable scenery.</td></tr>
<tr><td data-label="Traveler decision">I want cultural depth</td><td data-label="Best heritage answer">Choose Hue, Hoi An, My Son, Thang Long, and possibly Yen Tu or Ho Dynasty.</td><td data-label="Why it works">The route can move from imperial, trading-port, Champa, capital, Buddhist, and stone-citadel history.</td><td data-label="What to avoid">Reducing cultural sites to a single photo stop between transfers.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-unesco-heritage-source-diversity:v1 -->
<!-- wp:group {"className":"vg-source-diversity vg-unesco-heritage-source-diversity","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-source-diversity vg-unesco-heritage-source-diversity">
<!-- wp:heading -->
<h2 class="wp-block-heading">What the source trail can and cannot prove</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The evidence base starts with UNESCO World Heritage Centre records, then uses official Vietnam tourism and site-level sources for travel context. Sources can confirm official World Heritage status, categories, site names, inscription decisions, broad destination context, and image-license records; they cannot decide your pace, transfer tolerance, heat limit, children, mobility, cruise risk, or whether one more heritage stop makes the route better.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-unesco-heritage-source-diversity-table">
<thead>
<tr><th>Source type</th><th>What it is good for</th><th>How this guide uses it</th></tr>
</thead>
<tbody>
<tr><td data-label="Source type">UNESCO country, property, and decision pages</td><td data-label="What it is good for">Official list count, category, inscription status, names, criteria, and recent decisions.</td><td data-label="How this guide uses it">To keep the 9-property count, 2025 Yen Tu inscription, and Phong Nha-Hin Nam No update current.</td></tr>
<tr><td data-label="Source type">Vietnam.travel and official park/site pages</td><td data-label="What it is good for">Destination context, regional planning, park framing, and visitor-facing official context.</td><td data-label="How this guide uses it">To translate heritage facts into route decisions without pretending every source is a booking recommendation.</td></tr>
<tr><td data-label="Source type">VietnamGuide internal route pages</td><td data-label="What it is good for">Pace, base, weather, transfer, and itinerary logic already built for international visitors.</td><td data-label="How this guide uses it">To connect heritage ambition to real trip length, route pressure, and what each extra stop replaces.</td></tr>
<tr><td data-label="Source type">Wikimedia Commons image records</td><td data-label="What it is good for">License and attribution checks for route texture photography.</td><td data-label="How this guide uses it">To show what the stops feel like while keeping visible outbound credit clutter low.</td></tr>
</tbody>
</table>
</div>
<!-- /wp:group -->

<!-- vg-unesco-heritage-source-trail-snapshot:v1 -->
<!-- wp:group {"className":"vg-source-snapshot vg-unesco-heritage-source-trail-snapshot","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-source-snapshot vg-unesco-heritage-source-trail-snapshot">
<!-- wp:heading -->
<h2 class="wp-block-heading">Source trail snapshot for this heritage guide</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Checked July 25, 2026: UNESCO Viet Nam country page (https://whc.unesco.org/en/statesparties/vn), Yen Tu property page (https://whc.unesco.org/en/list/1732/), Yen Tu decision 47 COM 8B.22 (https://whc.unesco.org/en/decisions/8956/), Phong Nha-Ke Bang and Hin Nam No property page (https://whc.unesco.org/en/list/951/), Phong Nha-Hin Nam No decision 47 COM 8B.6 (https://whc.unesco.org/en/decisions/8942/), Vietnam.travel's 2025 Yen Tu article, Phong Nha-Ke Bang official park site, Son Doong official information portal, Vietnam.travel regional planning pages, and Wikimedia Commons image-license records.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Image credits are listed as text to keep the heritage decision guide readable and reduce visible outbound clutter. Full source URLs remain in the page's editorial metadata and update trail.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- vg-unesco-heritage-2025-updates:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What changed in 2025</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The 2025 changes matter because many older articles still imply the old count, old property names, or a simpler Phong Nha story. This guide treats those details as live-source facts, then keeps the travel advice evergreen.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-unesco-heritage-2025-updates">
<thead>
<tr><th>2025 update</th><th>What changed</th><th>Traveler meaning</th><th>Planning caution</th></tr>
</thead>
<tbody>
<tr><td data-label="2025 update">Yen Tu - Vinh Nghiem - Con Son, Kiep Bac</td><td data-label="What changed">Yen Tu - Vinh Nghiem - Con Son, Kiep Bac was inscribed in 2025 as a serial cultural World Heritage property.</td><td data-label="Traveler meaning">It gives north Vietnam a newer pilgrimage, Buddhist, Tran-dynasty, and landscape-history angle for travelers beyond the classic first trip.</td><td data-label="Planning caution">Treat Yen Tu as a spread-out northern cultural route, not one compact attraction.</td></tr>
<tr><td data-label="2025 update">Phong Nha-Ke Bang and Hin Nam No</td><td data-label="What changed">Phong Nha-Ke Bang National Park and Hin Nam No National Park is a 2025 transboundary update to the existing natural property.</td><td data-label="Traveler meaning">The property name now reflects a broader karst and biodiversity landscape across Vietnam and Laos.</td><td data-label="Planning caution">Do not count the Phong Nha-Hin Nam No update as a 10th Vietnam property.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-unesco-heritage-route-chooser:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Start here: choose by route job</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A UNESCO stop should earn its place by changing the route: a base, a protected day, a weather-resilient chapter, a nature reason, or a cultural sequence you would miss otherwise.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-unesco-heritage-route-chooser">
<thead>
<tr><th>Route job</th><th>Best sites</th><th>Decision rule</th></tr>
</thead>
<tbody>
<tr><td data-label="Route job">Iconic first impression</td><td data-label="Best sites">Ha Long Bay - Cat Ba Archipelago, Trang An</td><td data-label="Decision rule">Choose one bay chapter and one land-karst chapter only if transfers stay calm.</td></tr>
<tr><td data-label="Route job">Central cultural middle</td><td data-label="Best sites">Hue, Hoi An, My Son</td><td data-label="Decision rule">Protect Hue for depth, Hoi An for atmosphere, and My Son only when the half-day is not stolen from rest.</td></tr>
<tr><td data-label="Route job">Low-friction Hanoi culture</td><td data-label="Best sites">Thang Long</td><td data-label="Decision rule">Use it when Hanoi has room for context beyond food, lakes, and arrival recovery.</td></tr>
<tr><td data-label="Route job">Specialist history</td><td data-label="Best sites">Ho Dynasty, Yen Tu</td><td data-label="Decision rule">Add only when the traveler values quieter historical depth more than another famous stop.</td></tr>
<tr><td data-label="Route job">Active cave and karst nature</td><td data-label="Best sites">Phong Nha-Ke Bang and Hin Nam No</td><td data-label="Decision rule">Add when caves are a core trip reason, not when the route simply wants more scenery.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-unesco-heritage-shortlist:v1 -->
<!-- vg-unesco-heritage-site-by-site:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The 9 UNESCO properties and what they do for a route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This is a trip-planning list, not a certificate wall. The names matter because they help you decide where to spend nights, where to take a day trip, and where to skip the extra move.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-unesco-heritage-table">
<thead>
<tr><th>Heritage property</th><th>Type</th><th>Why it matters</th><th>Best fit</th><th>Skip when</th></tr>
</thead>
<tbody>
<tr><td data-label="Heritage property">Ha Long Bay - Cat Ba Archipelago</td><td data-label="Type">Natural</td><td data-label="Why it matters">Vietnam's most famous bay landscape and the clearest cruise-style heritage chapter.</td><td data-label="Best fit">First-timers who want one iconic seascape stop.</td><td data-label="Skip when">Weather or transfer time makes the cruise too compressed to feel special.</td></tr>
<tr><td data-label="Heritage property">Trang An Landscape Complex</td><td data-label="Type">Mixed</td><td data-label="Why it matters">Near-Hanoi landscape heritage that rewards an overnight instead of a rushed day trip.</td><td data-label="Best fit">Routes that want scenery without a remote mountain commitment.</td><td data-label="Skip when">You only have a single rushed window from Hanoi.</td></tr>
<tr><td data-label="Heritage property">Central Sector of the Imperial Citadel of Thang Long - Ha Noi</td><td data-label="Type">Cultural</td><td data-label="Why it matters">Adds north capital history to a Hanoi base instead of leaving the city as only a flight stop.</td><td data-label="Best fit">Culture-led first trips and return visitors.</td><td data-label="Skip when">You need the Hanoi days for food, markets, or rest instead.</td></tr>
<tr><td data-label="Heritage property">Citadel of the Ho Dynasty</td><td data-label="Type">Cultural</td><td data-label="Why it matters">A deeper north-central heritage stop that works best on a deliberate route.</td><td data-label="Best fit">Heritage-focused travelers building a broader north loop.</td><td data-label="Skip when">The trip is short enough that the detour would only become an add-on.</td></tr>
<tr><td data-label="Heritage property">Complex of Hue Monuments</td><td data-label="Type">Cultural</td><td data-label="Why it matters">The strongest imperial-history chapter on the standard north-to-central route.</td><td data-label="Best fit">Travelers who want central Vietnam with context, not just scenery.</td><td data-label="Skip when">Hue would become a one-meal monument stop.</td></tr>
<tr><td data-label="Heritage property">Hoi An Ancient Town</td><td data-label="Type">Cultural</td><td data-label="Why it matters">The easiest heritage town to experience slowly and well.</td><td data-label="Best fit">Families, couples, food travelers, and premium routes.</td><td data-label="Skip when">The route is too full to protect a real evening or morning there.</td></tr>
<tr><td data-label="Heritage property">My Son Sanctuary</td><td data-label="Type">Cultural</td><td data-label="Why it matters">Adds Champa context and gives central Vietnam a stronger heritage arc.</td><td data-label="Best fit">Hoi An routes with one protected half-day.</td><td data-label="Skip when">You are already stretching central Vietnam time too thin.</td></tr>
<tr><td data-label="Heritage property">Phong Nha-Ke Bang National Park and Hin Nam No National Park</td><td data-label="Type">Natural</td><td data-label="Why it matters">Cave and national-park authority for travelers who want a genuine nature chapter.</td><td data-label="Best fit">Active travelers with enough days and a clear cave priority.</td><td data-label="Skip when">It would force a weak transfer or replace a better heritage stop.</td></tr>
<tr><td data-label="Heritage property">Yen Tu - Vinh Nghiem - Con Son, Kiep Bac Complex of Monuments and Landscapes</td><td data-label="Type">Cultural</td><td data-label="Why it matters">A slower northern cultural and spiritual detour that rewards intention.</td><td data-label="Best fit">Return visitors or north-heavy routes with room for a pilgrimage-style day.</td><td data-label="Skip when">You still need the basics of Hanoi, Ninh Binh, and Ha Long to breathe.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-unesco-heritage-route-map:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Heritage route maps that actually work</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Think in circuits, not a badge checklist. A good heritage route protects a middle chapter, not just a photo stop.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-unesco-heritage-route-fit">
<thead>
<tr><th>Route shape</th><th>Include</th><th>Add carefully</th><th>Skip first</th><th>Why</th></tr>
</thead>
<tbody>
<tr><td data-label="Route shape">7-8 day heritage sampler</td><td data-label="Include">Hanoi, Trang An, and Ha Long or Cat Ba.</td><td data-label="Add carefully">One central heritage stop if flights are very clean.</td><td data-label="Skip first">Ho Dynasty, Yen Tu, and Phong Nha.</td><td data-label="Why">Short trips should protect the most visible heritage value, not the longest list.</td></tr>
<tr><td data-label="Route shape">North-to-central classic</td><td data-label="Include">Thang Long in Hanoi, Trang An, Ha Long, Hue, Hoi An, and My Son.</td><td data-label="Add carefully">A single north-east or cave detour if the route has extra nights.</td><td data-label="Skip first">Any second detour that would steal time from Hue or Hoi An.</td><td data-label="Why">This is the cleanest first heritage circuit for many international visitors.</td></tr>
<tr><td data-label="Route shape">Central heritage focus</td><td data-label="Include">Hue, Hoi An, and My Son.</td><td data-label="Add carefully">Phong Nha if caves are a top priority.</td><td data-label="Skip first">Southern add-ons that dilute the central middle chapter.</td><td data-label="Why">Central Vietnam can carry a slower, richer heritage trip by itself.</td></tr>
<tr><td data-label="Route shape">Northern depth route</td><td data-label="Include">Thang Long, Ho Dynasty, Yen Tu, Trang An, and Ha Long.</td><td data-label="Add carefully">A central extension only if the trip is long enough.</td><td data-label="Skip first">Beach-only add-ons that do not improve the heritage story.</td><td data-label="Why">The north can support a detailed cultural route when you give it time.</td></tr>
<tr><td data-label="Route shape">Nature plus heritage route</td><td data-label="Include">Phong Nha, Hue, Hoi An, and one north heritage anchor.</td><td data-label="Add carefully">A bay or Ninh Binh stop if flights allow.</td><td data-label="Skip first">A south-city chapter that would only become transit.</td><td data-label="Why">Nature and heritage work best when one of them is clearly the lead.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-unesco-heritage-itinerary-length:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Which sites fit 7, 10, 14, or 21 days?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Trip length is the first quality filter. The same heritage stop can be brilliant on a slow route and wasteful on a compressed one.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-unesco-heritage-itinerary-length">
<thead>
<tr><th>Trip length</th><th>Strong default</th><th>Possible upgrade</th><th>Usually skip</th></tr>
</thead>
<tbody>
<tr><td data-label="Trip length">7 days</td><td data-label="Strong default">Hanoi with Thang Long context, Trang An, and Ha Long Bay - Cat Ba if weather and transfers line up.</td><td data-label="Possible upgrade">Hoi An only if you intentionally choose a north-plus-central route.</td><td data-label="Usually skip">Ho Dynasty, Yen Tu, Phong Nha, and trying to include both Hue and Hoi An.</td></tr>
<tr><td data-label="Trip length">10 days</td><td data-label="Strong default">Trang An, Ha Long Bay - Cat Ba, Hue or Hoi An.</td><td data-label="Possible upgrade">My Son from Hoi An or Thang Long inside Hanoi.</td><td data-label="Usually skip">Yen Tu and Ho Dynasty unless heritage is the main trip theme.</td></tr>
<tr><td data-label="Trip length">14 days</td><td data-label="Strong default">Hanoi/Thang Long, Trang An, Ha Long Bay - Cat Ba, Hue, Hoi An, and My Son.</td><td data-label="Possible upgrade">Phong Nha if caves outrank beach or city time.</td><td data-label="Usually skip">A second specialist detour after Phong Nha.</td></tr>
<tr><td data-label="Trip length">21 days</td><td data-label="Strong default">A full north-to-central heritage arc with enough slack to protect bases.</td><td data-label="Possible upgrade">Yen Tu, Ho Dynasty, or Phong Nha depending on traveler style.</td><td data-label="Usually skip">Adding all three specialist detours unless the trip is explicitly heritage-led.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-unesco-heritage-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The photos are there to show route texture, not to decorate the page. A heritage route should feel different in each chapter.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-unesco-heritage-photo-grid" aria-label="Vietnam UNESCO heritage photography">
<figure class="vg-guide-photo"><img src="{$ha_long_image}" alt="Limestone islands and water in Ha Long Bay, Vietnam" loading="lazy" decoding="async"><figcaption>Ha Long Bay - Cat Ba is the clearest seascape chapter for a first trip. Image: Vyacheslav Argenberg, CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$trang_an_image}" alt="Boats and limestone scenery at Trang An in Ninh Binh, Vietnam" loading="lazy" decoding="async"><figcaption>Trang An is the easiest mixed heritage-and-landscape stop to insert cleanly into a north route. Image: Jakub Halun, CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$thang_long_image}" alt="Doan Mon gate at the Imperial Citadel of Thang Long in Hanoi" loading="lazy" decoding="async"><figcaption>Thang Long adds capital context without changing bases. Image: Christophe95, CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$ho_dynasty_image}" alt="Stone gate of the Ho Dynasty citadel in Thanh Hoa, Vietnam" loading="lazy" decoding="async"><figcaption>The Ho Dynasty citadel is quieter, specialist, and most useful on a deliberate heritage route. Image: Loi Nguyen Duc, CC BY 2.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hue_image}" alt="Historic architecture inside the Hue Citadel in central Vietnam" loading="lazy" decoding="async"><figcaption>Hue needs a protected day, not a token pass-through. Image: CEphoto, Uwe Aranas, CC BY-SA 3.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hoi_an_image}" alt="Evening street scene in Hoi An Ancient Town, Vietnam" loading="lazy" decoding="async"><figcaption>Hoi An is the easiest heritage town to keep slow. Image: Steffen Schmitz, CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$my_son_image}" alt="Brick tower temples at My Son Sanctuary in central Vietnam" loading="lazy" decoding="async"><figcaption>My Son gives the central route Champa context when the half-day is protected. Image: Philip Nalangan, CC BY 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$son_river_image}" alt="Son River landscape in Quang Binh province near Phong Nha, Vietnam" loading="lazy" decoding="async"><figcaption>Phong Nha earns its place when caves are a primary reason to stop. Image: BacLuong, CC BY-SA 4.0.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$yen_tu_image}" alt="Pagoda landscape at Yen Tu in northern Vietnam" loading="lazy" decoding="async"><figcaption>Yen Tu is a pilgrimage landscape, not a quick checkbox attraction. Image: Bach Giang Nguyen, CC BY-SA 4.0.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-unesco-heritage-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Skipping is the premium move. Heritage becomes richer when you stop forcing every badge into the same trip.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-unesco-heritage-skip-logic">
<thead>
<tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you want...">Classic first Vietnam</td><td data-label="Protect this">Hanoi, Trang An, Ha Long or Cat Ba, Hue, and Hoi An.</td><td data-label="Skip first">Ho Dynasty, Yen Tu, and Phong Nha.</td><td data-label="Only add back when...">You have enough nights for a true north or central detour.</td></tr>
<tr><td data-label="If you want...">Central heritage depth</td><td data-label="Protect this">Hue, Hoi An, and My Son.</td><td data-label="Skip first">Any southern add-on that only creates a transit day.</td><td data-label="Only add back when...">The central chapter has room to breathe.</td></tr>
<tr><td data-label="If you want...">Cave and landscape focus</td><td data-label="Protect this">Phong Nha and one supporting route anchor.</td><td data-label="Skip first">A second heritage detour that would cut the cave time too much.</td><td data-label="Only add back when...">The cave chapter is the reason for the trip.</td></tr>
<tr><td data-label="If you want...">Lower-stress family travel</td><td data-label="Protect this">Stable bases, shorter transfers, and one heritage day with slack.</td><td data-label="Skip first">A place that only works if you arrive, check in, and leave again.</td><td data-label="Only add back when...">It improves the trip more than it complicates it.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-unesco-heritage-official-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Official checks before you commit time</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Heritage status is not the same as route value. Use official pages to confirm the list, then judge each site by transfer time, weather, and what it replaces in your trip.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-unesco-heritage-official-checks"} -->
<ul class="wp-block-list vg-check-list vg-unesco-heritage-official-checks">
<li>UNESCO status: start with the UNESCO Viet Nam state page, which currently shows 9 properties inscribed on the World Heritage List.</li>
<li>2025 count check: confirm Yen Tu on the UNESCO property page and decision 47 COM 8B.22 before relying on older articles.</li>
<li>2025 name check: confirm Phong Nha-Ke Bang National Park and Hin Nam No National Park on the UNESCO property page and decision 47 COM 8B.6.</li>
<li>North context: use Vietnam.travel Northern Vietnam, Hanoi, Ninh Binh, and official site pages for route framing.</li>
<li>Central context: use Vietnam.travel Central Vietnam plus Hue, Hoi An, My Son, and Phong Nha context for heritage pacing.</li>
<li>Nature context: use official park, cave, cruise, and weather sources when Phong Nha, Ha Long, Cat Ba, or Trang An changes the route.</li>
<li>Planning order: compare the heritage shortlist with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a> before booking.</li>
</ul>
<!-- /wp:list -->

<!-- vg-unesco-heritage-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before you book around a heritage stop</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The official heritage list is stable, but visitor logistics are not. Check the moving parts close to booking, especially when the heritage stop controls a hotel night, cruise date, cave permit, or long transfer.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-unesco-heritage-live-checks">
<thead>
<tr><th>Check</th><th>Where the risk shows up</th><th>What to do</th></tr>
</thead>
<tbody>
<tr><td data-label="Check">Official name and count</td><td data-label="Where the risk shows up">Older content may still show 8 properties or use the old Phong Nha name.</td><td data-label="What to do">Use UNESCO's country and property pages for the count and names; treat this guide's route advice as the planning layer.</td></tr>
<tr><td data-label="Check">Weather and water</td><td data-label="Where the risk shows up">Ha Long cruises, Trang An boats, Hoi An flooding, central heat, and Phong Nha cave access.</td><td data-label="What to do">Use the <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a> guide before locking a date-driven route.</td></tr>
<tr><td data-label="Check">Transfers and bases</td><td data-label="Where the risk shows up">Yen Tu, Ho Dynasty, Phong Nha, and bay cruises can create fragile one-night moves.</td><td data-label="What to do">Use the <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> guide before adding a remote heritage stop.</td></tr>
<tr><td data-label="Check">Tickets, closures, and permits</td><td data-label="Where the risk shows up">Museums, citadels, cave routes, festivals, conservation limits, and premium cave access.</td><td data-label="What to do">Confirm locally before payment, especially for Son Doong-style cave products and festival-season Yen Tu travel.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-unesco-heritage-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">UNESCO heritage planning FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-unesco-heritage-faq">
<h3>How many UNESCO World Heritage properties does Vietnam have?</h3>
<p>Vietnam has 9 UNESCO World Heritage properties: 6 cultural, 2 natural, and 1 mixed. This count is for World Heritage properties, not every UNESCO designation in Vietnam.</p>
<h3>Which UNESCO sites should a first-time visitor prioritize?</h3>
<p>Most first-time routes should prioritize Trang An, Ha Long Bay - Cat Ba Archipelago, Hue, Hoi An, and My Son if central Vietnam has enough time. Thang Long can fit inside Hanoi without changing bases.</p>
<h3>Is Yen Tu worth adding now that it is a 2025 World Heritage property?</h3>
<p>It can be, but mainly for return visitors, Buddhist-history travelers, and north-heavy routes. Treat it as a spread-out cultural landscape rather than a quick day-trip checkbox.</p>
<h3>Does Phong Nha-Hin Nam No make Vietnam have 10 World Heritage properties?</h3>
<p>No. The 2025 Phong Nha-Hin Nam No update is a transboundary update to the existing natural World Heritage property, not a separate 10th Vietnam property.</p>
<h3>Can I see every Vietnam World Heritage property in one trip?</h3>
<p>You can on a long specialist route, but most travelers should not. The better question is which sites make the route richer without turning the trip into a transfer schedule.</p>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">How this guide fits the site</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This page is the heritage filter for the destinations cluster. Use it after the main travel guide and regional comparison, then use the destination shortlist to decide which sites deserve nights or a protected day trip.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-unesco-heritage-hero:v1',
    'concierge verdict' => 'vg-unesco-heritage-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-unesco-heritage-at-a-glance:v1',
    'source diversity' => 'vg-unesco-heritage-source-diversity:v1',
    'source trail snapshot' => 'vg-unesco-heritage-source-trail-snapshot:v1',
    '2025 updates' => 'vg-unesco-heritage-2025-updates:v1',
    'route chooser' => 'vg-unesco-heritage-route-chooser:v1',
    'shortlist' => 'vg-unesco-heritage-shortlist:v1',
    'site by site' => 'vg-unesco-heritage-site-by-site:v1',
    'route map' => 'vg-unesco-heritage-route-map:v1',
    'itinerary length' => 'vg-unesco-heritage-itinerary-length:v1',
    'photo grid' => 'vg-unesco-heritage-photo-grid:v1',
    'skip logic' => 'vg-unesco-heritage-skip-logic:v1',
    'official checks' => 'vg-unesco-heritage-official-checks:v1',
    'live checks' => 'vg-unesco-heritage-live-checks:v1',
    'FAQ' => 'vg-unesco-heritage-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('UNESCO Heritage Sites in Vietnam guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'UNESCO Heritage Sites in Vietnam',
    'post_name'      => 'unesco-heritage-sites-vietnam',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led 2025 route guide to UNESCO World Heritage Sites in Vietnam, with first-trip priorities, 2025 updates, site-by-site judgment, skip logic, source checks, and photo proof.',
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
    vg_ops_fail('Could not publish UNESCO Heritage Sites in Vietnam guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish UNESCO Heritage Sites in Vietnam guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'UNESCO Heritage Sites in Vietnam: 2025 Route Guide');
update_post_meta($page_id, 'rank_math_description', 'Choose which UNESCO World Heritage Sites in Vietnam fit your route, with 2025 updates, first-trip priorities, skip logic, source checks, and photo proof.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'UNESCO heritage sites in Vietnam');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide which UNESCO World Heritage Sites in Vietnam deserve protected route time before adding another famous name.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 25, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Hardened the UNESCO Heritage Sites guide for GSC CTR opportunity with a stronger route-intent H1, 2025 UNESCO updates, source diversity, source snapshot, itinerary-length logic, expanded site-by-site decision value, text-only image credits, and low visible outbound link clutter.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "UNESCO World Heritage Centre - Viet Nam state page - https://whc.unesco.org/en/statesparties/vn - checked July 25, 2026\nUNESCO World Heritage Centre - Yen Tu - Vinh Nghiem - Con Son, Kiep Bac - https://whc.unesco.org/en/list/1732/ - checked July 25, 2026\nUNESCO World Heritage Centre decision 47 COM 8B.22 - Yen Tu inscription - https://whc.unesco.org/en/decisions/8956/ - checked July 25, 2026\nUNESCO World Heritage Centre - Phong Nha-Ke Bang National Park and Hin Nam No National Park - https://whc.unesco.org/en/list/951/ - checked July 25, 2026\nUNESCO World Heritage Centre decision 47 COM 8B.6 - Phong Nha/Hin Nam No update - https://whc.unesco.org/en/decisions/8942/ - checked July 25, 2026\nVietnam.travel - Vietnam's 9th World Heritage Site spotted - https://vietnam.travel/things-to-do/vietnam%E2%80%99s-9th-world-heritage-site-spotted - checked July 25, 2026\nPhong Nha-Ke Bang official park site - https://phongnhakebang.vn/ - checked July 25, 2026\nSon Doong official information portal - https://sondoongcave.info/ - checked July 25, 2026\nVietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked July 25, 2026\nVietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked July 25, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 25, 2026\nWikimedia Commons image record - Ha Long Bay, Vietnam, View from above - https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg - license checked July 25, 2026\nWikimedia Commons image record - Trang An Landscape Complex, Ninh Binh Province - https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg - license checked July 25, 2026\nWikimedia Commons image record - Doan Mon Gate 1 - https://commons.wikimedia.org/wiki/File:Doan_Mon_Gate_1.jpg - license checked July 25, 2026\nWikimedia Commons image record - Ho dynasty's citadel, Vietnam - https://commons.wikimedia.org/wiki/File:Ho_dynasty%27s_citadel,_Vietnam.jpg - license checked July 25, 2026\nWikimedia Commons image record - Hue Vietnam Citadel - https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg - license checked July 25, 2026\nWikimedia Commons image record - Hoi An Ancient Town - https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg - license checked July 25, 2026\nWikimedia Commons image record - My Son Sanctuary Vietnam 06 - https://commons.wikimedia.org/wiki/File:My_Son_Sanctuary_Vietnam_06.jpg - license checked July 25, 2026\nWikimedia Commons image record - Son River, Quang Binh province - https://commons.wikimedia.org/wiki/File:Son_River-Quang_Binh_province.jpg - license checked July 25, 2026\nWikimedia Commons image record - Chua-yen-tu-ngay-nay - https://commons.wikimedia.org/wiki/File:Chua-yen-tu-ngay-nay.jpg - license checked July 25, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'World Heritage status is only useful when it changes the route. This guide separates sites that deserve nights, sites that work as context, and sites that should wait.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Heritage guide built as a route filter, not a trophy-wall list.\nCurrent 9-property count grounded in UNESCO Viet Nam country and property pages.\nYen Tu - Vinh Nghiem - Con Son, Kiep Bac was inscribed in 2025 as a serial cultural World Heritage property.\n2025 source freshness for Yen Tu - Vinh Nghiem - Con Son, Kiep Bac and the Phong Nha-Ke Bang/Hin Nam No transboundary update.\nCategory framing: 6 cultural, 2 natural, and 1 mixed World Heritage properties.\nAt-a-glance route answer, source-diversity explanation, and source-trail snapshot.\nItinerary-length matrix that separates 7-, 10-, 14-, and 21-day heritage decisions.\nSite-by-site judgment that says what each property does for a route and when to skip.\nPhoto-led proof across all 9 properties with text-only image credits.\nLive-check discipline for names, weather, transfer friction, tickets, closures, and cave or cruise constraints.\nInternal links to the Travel Guide, region comparison, best time, itinerary, Ninh Binh, Ha Long, Hanoi, and central heritage pages.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Best Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Turn the heritage list into a broader destination shortlist.\nHanoi Travel Guide | /destinations/hanoi-travel-guide/ | Decide whether Thang Long belongs inside a Hanoi base before the northern route starts moving.\nBest Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Decide whether capital heritage deserves protected city time.\nNinh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Use Ninh Binh as the practical base for Trang An and the Hanoi-side heritage landscape.\nNinh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Trang An should be a rushed day trip or one protected countryside night.\nTrang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, and family comfort.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose the Ninh Binh base before turning Trang An into a rushed side stop.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Check the bay handoff before pairing Trang An with a cruise chapter.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as the softer Ninh Binh base when countryside rhythm matters.\nHa Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Decide how the bay chapter should work before choosing a cruise or Cat Ba base.\nHa Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Choose the bay experience before treating UNESCO status as the only decision.\nCat Ba Travel Guide | /destinations/cat-ba-travel-guide/ | Decide whether Cat Ba should be a bay base, island stay, or skip.\nBai Tu Long Bay Guide | /destinations/bai-tu-long-bay-guide/ | Compare the quieter bay chapter before adding extra cruise friction.\nDa Nang Travel Guide | /destinations/da-nang-travel-guide/ | Use Da Nang as the practical airport-and-beach base for central heritage planning.\nDa Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Choose the central base before adding Hoi An, My Son, Hue, or beach time.\nHoi An vs Hue | /compare/hoi-an-vs-hue/ | Choose the central heritage chapter by atmosphere, imperial depth, food, family fit, and transfer order.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Choose the region before choosing the heritage stops.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check weather and season risk before locking a heritage-heavy route.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | See which heritage sites still fit a shorter first route.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Add heritage depth without crushing the two-week pace.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Build a longer heritage route with specialist detours and breathing room.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Check transfer friction before adding a remote heritage site.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero image: Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0. Body images: Ha Long Bay by Vyacheslav Argenberg, CC BY 4.0; Trang An by Jakub Halun, CC BY 4.0; Thang Long by Christophe95, CC BY-SA 4.0; Ho Dynasty citadel by Loi Nguyen Duc, CC BY 2.0; Hue by CEphoto, Uwe Aranas, CC BY-SA 3.0; Hoi An by Steffen Schmitz, CC BY-SA 4.0; My Son by Philip Nalangan, CC BY 4.0; Son River by BacLuong, CC BY-SA 4.0; Yen Tu by Bach Giang Nguyen, CC BY-SA 4.0.');
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
    vg_ops_fail('UNESCO Heritage Sites in Vietnam guide was updated but is not published.');
}

vg_ops_refresh_destinations_hub();
vg_ops_refresh_best_places_related_routes();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published UNESCO Heritage Sites in Vietnam guide: {$page_id} {$updated_permalink}");

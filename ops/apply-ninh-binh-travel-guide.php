<?php
/**
 * Publish the Ninh Binh Travel Guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-ninh-binh-travel-guide.php --allow-root
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
    $value = getenv('VG_FORCE_NINH_BINH_GUIDE_REPUBLISH');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the Ninh Binh Travel Guide.');
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

    if (! vg_ops_published_page_exists('destinations/ninh-binh-travel-guide')) {
        vg_ops_log('Skipped Destinations hub refresh: Ninh Binh guide is not published.');
        return;
    }

    $marker = '<!-- vg-ninh-binh-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Turn Ninh Binh into a route-calming landscape stop</h3><p>The <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a> helps travelers choose between a day trip, one-night countryside stop, two-night slow base, or skipping it when the northern route is already too tight.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Destinations hub Ninh Binh note', $block);
    vg_ops_upsert_marked_group($hub, 'Destinations hub Ninh Binh note', $marker, $block);
}

function vg_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_ops_assert_internal_page_links_are_published("{$label} related route line", $route_line);

    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);

    if (str_contains($current, '/destinations/ninh-binh-travel-guide/')) {
        vg_ops_log("Skipped related-route refresh for {$label}: Ninh Binh is already present.");
        return;
    }

    $next = trim($current) === '' ? $route_line : rtrim($current) . "\n" . $route_line;
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_ops_log("Added Ninh Binh related route to {$label}: {$page->ID}");
}

function vg_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Ninh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide whether Ninh Binh should be a day trip, one-night countryside stop, two-night slow base, or a skip.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        ] as $path => $label
    ) {
        vg_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/ninh-binh-travel-guide', OBJECT, 'page');
$allow_republish = vg_ops_force_republish_enabled();

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('Ninh Binh Travel Guide is not a draft. Set VG_FORCE_NINH_BINH_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    $permalink = get_permalink($page);
    vg_ops_log("Preflight Ninh Binh Travel Guide: {$page->ID} {$page->post_status} {$permalink}");
} else {
    vg_ops_log('Preflight Ninh Binh Travel Guide: no existing page found; creating a child page under /destinations/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg');
$tam_coc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Tam_Coc_Ninh_Binh_%2829079%29.jpg/1920px-Tam_Coc_Ninh_Binh_%2829079%29.jpg');
$tam_coc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Tam_Coc_Ninh_Binh_(29079).jpg');
$mua_cave_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f4/Mua_Cave%2C_Ninh_Binh%2C_Vietnam%2C_20240202_0926_4964.jpg/1920px-Mua_Cave%2C_Ninh_Binh%2C_Vietnam%2C_20240202_0926_4964.jpg');
$mua_cave_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Mua_Cave,_Ninh_Binh,_Vietnam,_20240202_0926_4964.jpg');
$dragon_mountain_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/a/a3/2024-03-30-Mua_Cave_Dragon_Mountain-3758.jpg/1920px-2024-03-30-Mua_Cave_Dragon_Mountain-3758.jpg');
$dragon_mountain_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:2024-03-30-Mua_Cave_Dragon_Mountain-3758.jpg');
$bai_dinh_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/56/Ch%C3%B9a_B%C3%A1i_%C4%90%C3%ADnh.jpg/1920px-Ch%C3%B9a_B%C3%A1i_%C4%90%C3%ADnh.jpg');
$bai_dinh_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ch%C3%B9a_B%C3%A1i_%C4%90%C3%ADnh.jpg');
$cuc_phuong_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/98/Forest_in_Cuc_Phuong_National_Park_%2815706323528%29.jpg/1920px-Forest_in_Cuc_Phuong_National_Park_%2815706323528%29.jpg');
$cuc_phuong_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Forest_in_Cuc_Phuong_National_Park_(15706323528).jpg');
$van_long_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/4/41/Van_Long_Nature_Reserve_Riet_Grotten_Kalksteen_Ninh_Binh_%2894589%29.jpg/1920px-Van_Long_Nature_Reserve_Riet_Grotten_Kalksteen_Ninh_Binh_%2894589%29.jpg');
$van_long_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Van_Long_Nature_Reserve_Riet_Grotten_Kalksteen_Ninh_Binh_(94589).jpg');

$content = <<<HTML
<!-- vg-ninh-binh-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Boats and limestone scenery at Trang An in Ninh Binh, Vietnam, used for a Ninh Binh travel guide" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 18, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Ninh Binh Travel Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Ninh Binh is the northern Vietnam stop where limestone, rivers, temples, rice fields, and slow countryside time can make a route feel less crowded. The question is not whether it is beautiful. The question is whether it should be a day trip from Hanoi, a one-night stop, a two-night base, or a place you save for a less compressed trip.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: Ninh Binh is at its best when it calms the route. If the plan turns it into a very early pickup, rushed boat ride, hot viewpoint climb, and late return before another transfer, the famous scenery may still be beautiful while the trip gets weaker.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-ninh-binh-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-ninh-binh-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-ninh-binh-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most first-time international travelers with 10 to 14 days, Ninh Binh deserves one night rather than a rushed day trip.</strong> Use Trang An or Tam Coc as the landscape anchor, add Hang Mua only when heat and knees allow, and choose Hoa Lu, Bai Dinh, Van Long, or Cuc Phuong based on what the route lacks: history, scale, wildlife, or forest.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-ninh-binh-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-ninh-binh-shortlist">
<li><strong>Best first route shape:</strong> Hanoi, one night in Ninh Binh, then Ha Long/Lan Ha or back through Hanoi for a cleaner onward move.</li>
<li><strong>Best base for feel:</strong> Tam Coc or Trang An area if you want cycling, countryside, boat docks, and easier soft evenings.</li>
<li><strong>Best base for logistics:</strong> Ninh Binh city if trains, budget hotels, or late arrival matter more than scenery at the door.</li>
<li><strong>Best skip rule:</strong> do not stack Trang An, Tam Coc, Hang Mua, Hoa Lu, Bai Dinh, and Cuc Phuong into one day and call it premium.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-ninh-binh-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-ninh-binh-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-ninh-binh-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fastest useful answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Should I do Ninh Binh as a day trip?</strong></td><td data-label="Answer">Only if the route is short or you cannot add a night. A day trip can show the scenery, but it often removes the best part: slow morning and evening countryside rhythm.</td></tr>
<tr><td data-label="Question"><strong>How many nights are ideal?</strong></td><td data-label="Answer">One night is the best default. Two nights suit photographers, families, premium slow routes, and travelers adding Cuc Phuong or Van Long.</td></tr>
<tr><td data-label="Question"><strong>Trang An or Tam Coc?</strong></td><td data-label="Answer">Choose Trang An for the strongest UNESCO-landscape logic and cave route system. Choose Tam Coc when rice-field scenery and a softer local base matter more.</td></tr>
<tr><td data-label="Question"><strong>Is Hang Mua essential?</strong></td><td data-label="Answer">No. It is high impact if visibility, heat, crowds, knees, and timing cooperate. It is easy to skip if the day already has a boat route and a transfer.</td></tr>
<tr><td data-label="Question"><strong>What should I book first?</strong></td><td data-label="Answer">Lock the Hanoi-Ninh Binh transfer, base area, and one main boat experience before adding temples, viewpoints, or forest side trips.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-ninh-binh-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: why Ninh Binh changes the route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The images are planning evidence. They show why Ninh Binh works best as a slower landscape stop, not just another attraction name between Hanoi and the bay.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-ninh-binh-photo-grid" aria-label="Ninh Binh travel guide photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Trang An limestone landscape and river boats in Ninh Binh" loading="lazy" decoding="async"><figcaption>Trang An is the strongest argument for treating Ninh Binh as a real northern landscape chapter. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$tam_coc_image}" alt="Tam Coc river and limestone scenery in Ninh Binh" loading="lazy" decoding="async"><figcaption>Tam Coc is the softer base when rice fields, cycling, and countryside texture matter. Image: <a href="{$tam_coc_credit_url}" target="_blank" rel="license noopener">Andre Hospers / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mua_cave_image}" alt="Mua Cave viewpoint stairs and Ninh Binh landscape" loading="lazy" decoding="async"><figcaption>Hang Mua gives the clearest viewpoint payoff, but only when timing and weather protect the climb. Image: <a href="{$mua_cave_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$dragon_mountain_image}" alt="Dragon statue viewpoint above Hang Mua in Ninh Binh" loading="lazy" decoding="async"><figcaption>The dragon viewpoint is a visual win, not a reason to overload a fragile day. Image: <a href="{$dragon_mountain_credit_url}" target="_blank" rel="license noopener">Superbass / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$bai_dinh_image}" alt="Bai Dinh Pagoda complex in Ninh Binh" loading="lazy" decoding="async"><figcaption>Bai Dinh adds scale and temple architecture when the route wants a more structured cultural stop. Image: <a href="{$bai_dinh_credit_url}" target="_blank" rel="license noopener">Viethavvh / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cuc_phuong_image}" alt="Forest in Cuc Phuong National Park near Ninh Binh" loading="lazy" decoding="async"><figcaption>Cuc Phuong belongs to a two-night or return-visitor plan, not a squeezed first-day sampler. Image: <a href="{$cuc_phuong_credit_url}" target="_blank" rel="license noopener">hds / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$van_long_image}" alt="Van Long Nature Reserve limestone and wetland scenery in Ninh Binh" loading="lazy" decoding="async"><figcaption>Van Long is a quieter nature pivot when the traveler wants wetlands and wildlife over another busy headline stop. Image: <a href="{$van_long_credit_url}" target="_blank" rel="license noopener">Andre Hospers / CC BY 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-ninh-binh-day-trip-overnight:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Day trip, one night, or two nights?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This is the core Ninh Binh decision. The same places can feel either restorative or punishing depending on how much time the route gives them.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-day-trip-overnight">
<thead>
<tr><th>Time in Ninh Binh</th><th>Best for</th><th>Keep</th><th>Skip first</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Time in Ninh Binh">Hanoi day trip</td><td data-label="Best for">Travelers with 7-9 days, no spare night, or a route that must stay Hanoi-based.</td><td data-label="Keep">One boat route plus either Hang Mua or Hoa Lu, not both if the day is hot or crowded.</td><td data-label="Skip first">Cuc Phuong, Bai Dinh, Van Long, and any plan that adds three headline stops after the boat.</td><td data-label="VietnamGuide verdict">Acceptable, but not the premium version.</td></tr>
<tr><td data-label="Time in Ninh Binh">One night</td><td data-label="Best for">Most 10-14 day first trips and travelers moving between Hanoi and the bay/central Vietnam.</td><td data-label="Keep">Arrival transfer, one slow countryside evening, Trang An or Tam Coc, and one supporting stop.</td><td data-label="Skip first">A second boat route and a full temple/forest extension.</td><td data-label="VietnamGuide verdict">Best default.</td></tr>
<tr><td data-label="Time in Ninh Binh">Two nights</td><td data-label="Best for">Families, photographers, slow travelers, premium countryside stays, and nature-led routes.</td><td data-label="Keep">Two mornings, one main boat route, Hang Mua at the right time, and either Van Long, Bai Dinh, Hoa Lu, or Cuc Phuong.</td><td data-label="Skip first">Repeating similar scenery just because another dock is famous.</td><td data-label="VietnamGuide verdict">Best version if the wider route can spare the time.</td></tr>
<tr><td data-label="Time in Ninh Binh">Skip this trip</td><td data-label="Best for">Very short routes, weather-fragile trips, travelers already doing a bay cruise and central Vietnam with little slack.</td><td data-label="Keep">Hanoi and the next anchor destination properly paced.</td><td data-label="Skip first">A prestige day trip that weakens sleep, transfers, or weather resilience.</td><td data-label="VietnamGuide verdict">A valid premium choice when the calendar is tight.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-priority-map:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to prioritize in Ninh Binh</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Do not rank Ninh Binh by fame alone. Rank it by what each stop changes in the trip.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-priority-map">
<thead>
<tr><th>Stop</th><th>Why it matters</th><th>Best fit</th><th>Watch out for</th><th>Verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Stop">Trang An boat route</td><td data-label="Why it matters">The clearest UNESCO-linked landscape experience and the strongest single reason to come.</td><td data-label="Best fit">First-timers, mixed heritage/nature routes, one-night stays.</td><td data-label="Watch out for">Peak-day crowds and choosing too many add-ons afterward.</td><td data-label="Verdict">Anchor the plan here if you want the most reliable first Ninh Binh chapter.</td></tr>
<tr><td data-label="Stop">Tam Coc boat route</td><td data-label="Why it matters">Rice-field scenery, river rhythm, and an easier connection to a relaxed Tam Coc base.</td><td data-label="Best fit">Slow travelers, cyclists, photographers, and couples.</td><td data-label="Watch out for">Water/harvest season variability and overlap with Trang An if time is short.</td><td data-label="Verdict">Choose it over Trang An when softness and base feel matter more than maximum official-site logic.</td></tr>
<tr><td data-label="Stop">Hang Mua viewpoint</td><td data-label="Why it matters">The high viewpoint explains the landscape in one image.</td><td data-label="Best fit">Early morning or late afternoon plans, photographers, active travelers.</td><td data-label="Watch out for">Heat, crowds, slippery steps, and overconfidence before transfers.</td><td data-label="Verdict">High value when conditions are kind; optional when they are not.</td></tr>
<tr><td data-label="Stop">Hoa Lu ancient capital</td><td data-label="Why it matters">Adds a short history layer to a mostly landscape-focused stop.</td><td data-label="Best fit">Day trips that need a compact cultural pairing or travelers who skipped Hanoi history.</td><td data-label="Watch out for">Treating it as a deep heritage day when the visit is brief.</td><td data-label="Verdict">Good supporting stop, rarely the reason to stay.</td></tr>
<tr><td data-label="Stop">Bai Dinh Pagoda</td><td data-label="Why it matters">Scale, temple architecture, and a more formal spiritual/cultural stop.</td><td data-label="Best fit">Two-night stays, private-car days, travelers who want a structured temple complex.</td><td data-label="Watch out for">Large grounds, heat, and time cost compared with a quiet countryside day.</td><td data-label="Verdict">Add only when the route wants scale, not just another pin.</td></tr>
<tr><td data-label="Stop">Van Long Nature Reserve</td><td data-label="Why it matters">Wetland scenery and a calmer wildlife-oriented alternative to the busiest headline stops.</td><td data-label="Best fit">Slow travelers, birders, families who want a gentler boat experience.</td><td data-label="Watch out for">Expectations: this is quieter nature, not instant spectacle.</td><td data-label="Verdict">Best as a two-night or second-visit pivot.</td></tr>
<tr><td data-label="Stop">Cuc Phuong National Park</td><td data-label="Why it matters">Forest, conservation, and a true nature extension beyond karst-and-river scenery.</td><td data-label="Best fit">Two-night stays, return visitors, families with nature interest.</td><td data-label="Watch out for">Transfer time and making a first Ninh Binh day too broad.</td><td data-label="Verdict">Worth it only when the trip has room for a nature chapter.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How Ninh Binh fits common Vietnam routes</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ninh Binh is not just a place choice. It affects Hanoi nights, bay timing, train plans, central Vietnam energy, and the number of hotel moves a traveler can tolerate.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-route-fit">
<thead>
<tr><th>Route shape</th><th>Use Ninh Binh as...</th><th>Better order</th><th>Risk</th><th>Related guide</th></tr>
</thead>
<tbody>
<tr><td data-label="Route shape">10-day first trip</td><td data-label="Use Ninh Binh as...">A one-night countryside reset if the route is north + central, or a day trip if the schedule is already tight.</td><td data-label="Better order">Hanoi, Ninh Binh, bay or central Vietnam.</td><td data-label="Risk">Adding both Ninh Binh and a bay cruise without cutting something else.</td><td data-label="Related guide"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">14-day first trip</td><td data-label="Use Ninh Binh as...">A protected one-night or two-night chapter depending on southern ambition.</td><td data-label="Better order">Hanoi, Ninh Binh, Ha Long/Lan Ha, then fly or train south.</td><td data-label="Risk">Using the extra days to add more stops instead of making existing stops better.</td><td data-label="Related guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">Northern Vietnam focus</td><td data-label="Use Ninh Binh as...">A lower-friction landscape stop before mountains, bay, or Hanoi return.</td><td data-label="Better order">Hanoi, Ninh Binh, bay/mountains, Hanoi buffer.</td><td data-label="Risk">Treating every northern landscape as interchangeable.</td><td data-label="Related guide"><a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a></td></tr>
<tr><td data-label="Route shape">Heritage-led route</td><td data-label="Use Ninh Binh as...">The mixed landscape-and-heritage stop after Hanoi's capital layer.</td><td data-label="Better order">Hanoi, Trang An/Ninh Binh, Hue, Hoi An/My Son.</td><td data-label="Risk">Collecting UNESCO labels without enough time at any one place.</td><td data-label="Related guide"><a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a></td></tr>
<tr><td data-label="Route shape">Premium slow route</td><td data-label="Use Ninh Binh as...">A countryside base with fewer stops, better timing, and a stronger hotel choice.</td><td data-label="Better order">Hanoi, two nights Ninh Binh, then one clean onward move.</td><td data-label="Risk">Buying a luxury stay and still running it like a day tour.</td><td data-label="Related guide"><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-best-time-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best time to visit Ninh Binh</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam.travel frames March to May and September to November as the ideal temperature windows for Ninh Binh, with October highlighted for harvest views. Use that as a planning base, then adjust for heat, rain, visibility, and crowd pressure.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-weather-table">
<thead>
<tr><th>Season window</th><th>What it helps</th><th>What to protect</th><th>Best move</th></tr>
</thead>
<tbody>
<tr><td data-label="Season window">March to May</td><td data-label="What it helps">Comfortable temperatures, scenery, and active days before summer heat gets heavier.</td><td data-label="What to protect">Early starts and a plan that still works if visibility is soft.</td><td data-label="Best move">Use one night or two if the north is a priority.</td></tr>
<tr><td data-label="Season window">June to August</td><td data-label="What it helps">Long days and lush landscapes.</td><td data-label="What to protect">Heat, humidity, sudden rain, storm disruption, and exposed climbs.</td><td data-label="Best move">Keep Hang Mua flexible and choose hotels with recovery space.</td></tr>
<tr><td data-label="Season window">September to November</td><td data-label="What it helps">Strong scenery, more comfortable touring, and October harvest appeal.</td><td data-label="What to protect">Weekend crowds and changing weather after rain.</td><td data-label="Best move">This is one of the best windows for a one-night countryside stop.</td></tr>
<tr><td data-label="Season window">December to February</td><td data-label="What it helps">Cooler travel days and less heat stress.</td><td data-label="What to protect">Grey skies, chill on boats, and weaker photo conditions.</td><td data-label="Best move">Prioritize comfort and route fit over chasing perfect images.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-getting-around:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Getting there and getting around</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ninh Binh is close enough to Hanoi to tempt travelers into underplanning. The smart move is to decide the transfer style before choosing the base.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-transport-table">
<thead>
<tr><th>Transport choice</th><th>Best for</th><th>Watch out for</th><th>Verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Transport choice">Private car from Hanoi</td><td data-label="Best for">Families, premium travelers, one-night stays, late arrivals, and travelers carrying luggage to a countryside hotel.</td><td data-label="Watch out for">Cost, pickup timing, and adding too many stops to justify the car.</td><td data-label="Verdict">Best comfort choice when time matters.</td></tr>
<tr><td data-label="Transport choice">Limousine van or bus</td><td data-label="Best for">Good value from Hanoi, especially to Tam Coc or central Ninh Binh drop-offs.</td><td data-label="Watch out for">Hotel pickup claims, exact drop-off, luggage, and departure time reliability.</td><td data-label="Verdict">Best default for many independent travelers.</td></tr>
<tr><td data-label="Transport choice">Train</td><td data-label="Best for">Travelers who prefer rail, want Ninh Binh city logistics, or are connecting onward by train.</td><td data-label="Watch out for">Station transfers to Tam Coc/Trang An and limited flexibility after arrival.</td><td data-label="Verdict">Good when the station fits the route, less scenic than people imagine.</td></tr>
<tr><td data-label="Transport choice">Taxi or car inside Ninh Binh</td><td data-label="Best for">Hot days, families, temple complexes, Hang Mua timing, and luggage moves.</td><td data-label="Watch out for">Waiting charges and weak route design.</td><td data-label="Verdict">Use selectively to protect the best hours.</td></tr>
<tr><td data-label="Transport choice">Cycling</td><td data-label="Best for">Tam Coc countryside, short distances, slow travelers, and cooler parts of the day.</td><td data-label="Watch out for">Heat, darkness, rain, mixed road conditions, and inexperienced riders.</td><td data-label="Verdict">Excellent as a base experience, not as a logistics cure-all.</td></tr>
<tr><td data-label="Transport choice">Motorbike or scooter</td><td data-label="Best for">Confident, properly licensed riders with clear insurance and local-road comfort.</td><td data-label="Watch out for">Insurance exclusions, helmet quality, rain, traffic, alcohol, and unfamiliar roads.</td><td data-label="Verdict">Do not ride unless the risk and paperwork are honestly solved.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first in Ninh Binh</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Skipping is what lets Ninh Binh feel expensive in the good way: calm, intentional, and worth the transfer.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ninh-binh-skip-logic">
<thead>
<tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you want...">Classic first Ninh Binh</td><td data-label="Protect this">One boat route, one countryside base, one slow meal, and either a viewpoint or compact culture stop.</td><td data-label="Skip first">A second boat route on the same short stay.</td><td data-label="Only add back when...">You have two nights and genuinely want comparison, not completion.</td></tr>
<tr><td data-label="If you want...">Best photos</td><td data-label="Protect this">Early or late light, weather flexibility, and enough rest before Hang Mua.</td><td data-label="Skip first">Midday viewpoint climbs and transfer-day photo chasing.</td><td data-label="Only add back when...">Visibility and energy make the climb worthwhile.</td></tr>
<tr><td data-label="If you want...">Family-friendly pace</td><td data-label="Protect this">Short hops, a comfortable base, a boat route, and one low-stress land stop.</td><td data-label="Skip first">Overlong temple grounds in heat and too many transit changes.</td><td data-label="Only add back when...">The group still has attention and weather is kind.</td></tr>
<tr><td data-label="If you want...">Nature depth</td><td data-label="Protect this">Two nights and a real Cuc Phuong or Van Long window.</td><td data-label="Skip first">Trying to bolt forest and wetland onto a Hanoi day trip.</td><td data-label="Only add back when...">Nature is the reason for the extra night.</td></tr>
<tr><td data-label="If you want...">A cleaner 10-day route</td><td data-label="Protect this">Hanoi recovery, one Ninh Binh anchor, and one clean move afterward.</td><td data-label="Skip first">Any extra stop that creates a one-night chain.</td><td data-label="Only add back when...">The next destination loses nothing important.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ninh-binh-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before you book</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The verdicts above are evergreen. These details can move, so verify them near travel before paying for transfers, hotels, or tours.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-ninh-binh-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-ninh-binh-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh" target="_blank" rel="noopener">Vietnam.travel Ninh Binh</a> and <a href="https://vietnam.travel/places-to-go/northern-vietnam" target="_blank" rel="noopener">Vietnam.travel Northern Vietnam</a> for official destination framing.</li>
<li>Use the <a href="https://whc.unesco.org/en/list/1438/" target="_blank" rel="noopener">UNESCO Trang An Landscape Complex listing</a> when the mixed heritage/nature status is part of the route decision.</li>
<li>Use <a href="https://dulichninhbinh.com.vn/en/" target="_blank" rel="noopener">Ninh Binh Tourism Department</a> for local tourism notices, tourist assistance, ticket-price pages, destination information, and service listings.</li>
<li>Check <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> plus a local forecast before treating Hang Mua, boat routes, or cycling as fixed commitments.</li>
<li>Read <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before choosing train, van, private car, or a mixed Ninh Binh-to-bay transfer.</li>
<li>Pair this with <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a> and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> if cycling, scooters, boat time, wet steps, or rural transfers are in the plan.</li>
</ul>
<!-- /wp:list -->

<!-- vg-ninh-binh-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Ninh Binh travel FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-ninh-binh-faq">
<details><summary>Is Ninh Binh worth visiting?</summary><p>Yes, when the route gives it enough time to feel like countryside rather than a long errand. One night is the best default for most first-time Vietnam routes. Skip it without guilt if adding it would make the trip too rushed.</p></details>
<details><summary>Is Trang An better than Tam Coc?</summary><p>Trang An is usually the stronger first choice for a single boat route because it carries the clearest UNESCO-linked landscape logic. Tam Coc can be the better fit when you want rice-field scenery, cycling, and a softer base around the village.</p></details>
<details><summary>Can I visit Ninh Binh and Ha Long Bay on the same short trip?</summary><p>You can, but the route needs discipline. On a 10-day trip, Ninh Binh plus a bay cruise usually means cutting a weak extra stop elsewhere. On a 14-day trip, both can work if the north is intentionally protected.</p></details>
<details><summary>Where should I stay in Ninh Binh?</summary><p>Stay around Tam Coc or Trang An for the most atmospheric countryside base. Stay in Ninh Binh city when train access, late arrival, or budget practicality matters more than scenery outside the door.</p></details>
<details><summary>How long is enough for Hang Mua?</summary><p>Plan it as a focused climb and viewpoint, not a casual afterthought. Go early or late when possible, avoid exposed midday heat, and skip it if wet steps, crowding, or energy make the climb a poor trade.</p></details>
<details><summary>Should I rent a scooter in Ninh Binh?</summary><p>Only if you are properly licensed, insured, sober, confident on local roads, and clear on the policy wording. Cycling, taxi, private car, and guided transport can all be better choices for many travelers.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> for the full planning order, compare route length in <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a> and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, then check <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>, <a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, and <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a> before locking hotels and transfers.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-ninh-binh-hero:v1',
    'concierge verdict' => 'vg-ninh-binh-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-ninh-binh-at-a-glance:v1',
    'photo grid' => 'vg-ninh-binh-photo-grid:v1',
    'day trip overnight table' => 'vg-ninh-binh-day-trip-overnight:v1',
    'priority map' => 'vg-ninh-binh-priority-map:v1',
    'route fit' => 'vg-ninh-binh-route-fit:v1',
    'best time weather' => 'vg-ninh-binh-best-time-weather:v1',
    'getting around' => 'vg-ninh-binh-getting-around:v1',
    'skip logic' => 'vg-ninh-binh-skip-logic:v1',
    'live checks' => 'vg-ninh-binh-live-checks:v1',
    'FAQ' => 'vg-ninh-binh-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('Ninh Binh Travel Guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Ninh Binh Travel Guide',
    'post_name'      => 'ninh-binh-travel-guide',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Ninh Binh travel guide for international visitors, with day-trip versus overnight judgment, Trang An and Tam Coc route fit, images, transport, weather, skip logic, and source checks.',
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
    vg_ops_fail('Could not publish Ninh Binh Travel Guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Ninh Binh Travel Guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Ninh Binh Travel Guide: Day Trip, Overnight, or Skip?');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Ninh Binh travel guide for international visitors: Trang An, Tam Coc, Hang Mua, Hoa Lu, Bai Dinh, Cuc Phuong, route fit, weather, transport, and skip logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Ninh Binh travel guide');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Ninh Binh should be a Hanoi day trip, one-night countryside stop, two-night slow base, or a skip before booking transfers and hotels.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 18, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Ninh Binh Travel Guide with an evidence-led concierge verdict, at-a-glance decision table, licensed photo proof, day-trip versus overnight matrix, priority map, route-fit guidance, weather pivots, transport table, skip logic, live checks, FAQ, Destinations hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Ninh Binh destination page - https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh - checked July 18, 2026\nVietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked July 18, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 18, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 18, 2026\nUNESCO World Heritage Centre - Trang An Landscape Complex - https://whc.unesco.org/en/list/1438/ - checked July 18, 2026\nNinh Binh Tourism Department - Tourism Promotion Information Center - https://dulichninhbinh.com.vn/en/ - checked July 18, 2026\nWikimedia Commons image record - Trang An Landscape Complex, Ninh Binh Province - https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg - license checked July 18, 2026\nWikimedia Commons image record - Tam Coc Ninh Binh - https://commons.wikimedia.org/wiki/File:Tam_Coc_Ninh_Binh_(29079).jpg - license checked July 18, 2026\nWikimedia Commons image record - Mua Cave, Ninh Binh - https://commons.wikimedia.org/wiki/File:Mua_Cave,_Ninh_Binh,_Vietnam,_20240202_0926_4964.jpg - license checked July 18, 2026\nWikimedia Commons image record - Mua Cave Dragon Mountain - https://commons.wikimedia.org/wiki/File:2024-03-30-Mua_Cave_Dragon_Mountain-3758.jpg - license checked July 18, 2026\nWikimedia Commons image record - Bai Dinh Pagoda - https://commons.wikimedia.org/wiki/File:Ch%C3%B9a_B%C3%A1i_%C4%90%C3%ADnh.jpg - license checked July 18, 2026\nWikimedia Commons image record - Cuc Phuong National Park forest - https://commons.wikimedia.org/wiki/File:Forest_in_Cuc_Phuong_National_Park_(15706323528).jpg - license checked July 18, 2026\nWikimedia Commons image record - Van Long Nature Reserve - https://commons.wikimedia.org/wiki/File:Van_Long_Nature_Reserve_Riet_Grotten_Kalksteen_Ninh_Binh_(94589).jpg - license checked July 18, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Ninh Binh is strongest when it calms the northern route: one landscape anchor, one supporting stop, and enough time for the countryside to do its work.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Ninh Binh guide built around the real planning decision: day trip, one-night stop, two-night slow base, or skip.\nConcierge verdict that names the best default route shape and the first things to cut.\nAt-a-glance table that answers day trip, night count, Trang An vs Tam Coc, Hang Mua, and booking order.\nPhoto-led proof grid using licensed images for Trang An, Tam Coc, Hang Mua, Bai Dinh, Cuc Phuong, and Van Long with visible credits.\nDay-trip versus overnight matrix that prevents one-day stuffing.\nPriority map that explains what each Ninh Binh stop changes in the route.\nRoute-fit table linked to 10 Days, 14 Days, UNESCO, region comparison, and cost planning.\nSeason and weather pivots grounded in official Vietnam.travel Ninh Binh guidance.\nTransport table that separates private car, van, train, taxi, cycling, and scooter use.\nSkip logic that protects route value over attraction completion.\nLive-check list tied to Vietnam.travel, UNESCO, Ninh Binh Tourism Department, transport, safety, and insurance pages.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start here for the full first-trip planning order before adding northern landscape stops.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Compare whether Ninh Binh belongs in the destination shortlist.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Use Trang An as a heritage-and-landscape route decision, not a badge chase.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Decide whether the north deserves more of the trip before adding more regions.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check season pressure before locking boat routes, Hang Mua, or cycling days.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Choose train, van, private car, or onward transfer before choosing a base.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget for transfers, private cars, hotels, boat routes, and route buffers.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Decide whether Ninh Binh should stay a day trip or become a protected overnight before comparing it with bay and city time.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Ninh Binh improves a tight route or makes it brittle.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide if Ninh Binh should become a protected northern chapter.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check road, cycling, scooter, boat, and rural-transfer risk before travel.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair Ninh Binh transport and activity choices with practical safety habits.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Trang An image: Trang An Landscape Complex, Ninh Binh Province by Jakub Halun, CC BY 4.0. Body images: Tam Coc by Andre Hospers, CC BY 4.0; Mua Cave by Jakub Halun, CC BY 4.0; Mua Cave Dragon Mountain by Superbass, CC BY-SA 4.0; Bai Dinh Pagoda by Viethavvh, CC BY-SA 3.0; Cuc Phuong National Park forest by hds, CC BY 2.0; Van Long Nature Reserve by Andre Hospers, CC BY 4.0.');
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
    vg_ops_fail('Ninh Binh Travel Guide was updated but is not published.');
}

vg_ops_refresh_destinations_hub();
vg_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Ninh Binh Travel Guide: {$page_id} {$updated_permalink}");

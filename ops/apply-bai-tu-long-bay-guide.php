<?php
/**
 * Publish the Bai Tu Long Bay Guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-bai-tu-long-bay-guide.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_bai_tu_long_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_bai_tu_long_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_bai_tu_long_ops_force_republish_enabled(): bool
{
    $value = getenv('VG_FORCE_BAI_TU_LONG_GUIDE_REPUBLISH');

    return is_string($value) && trim($value) === '1';
}

function vg_bai_tu_long_ops_publish_author_id(?WP_Post $existing_page = null): int
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

    vg_bai_tu_long_ops_fail('Could not resolve a valid WordPress author for the Bai Tu Long Bay Guide.');
}

function vg_bai_tu_long_ops_internal_path_from_href(string $href): ?string
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

function vg_bai_tu_long_ops_assert_internal_page_links_are_published(string $label, string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];

    foreach ($matches[2] as $href) {
        $path = vg_bai_tu_long_ops_internal_path_from_href($href);

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
        vg_bai_tu_long_ops_fail(implode(PHP_EOL, $failures));
    }

    vg_bai_tu_long_ops_log("Validated {$label} internal page links are published.");
}

function vg_bai_tu_long_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_status === 'publish';
}

function vg_bai_tu_long_ops_assert_required_content_markers(string $content, array $required_markers): void
{
    foreach ($required_markers as $label => $needle) {
        if (! str_contains($content, $needle)) {
            vg_bai_tu_long_ops_fail("Required content marker missing before publish: {$label}");
        }
    }
}

function vg_bai_tu_long_ops_upsert_marked_group(WP_Post $page, string $label, string $marker, string $block): void
{
    $marked_block = $marker . "\n" . $block;

    if (str_contains($page->post_content, $marked_block)) {
        vg_bai_tu_long_ops_log("Skipped {$label}: current block already present.");
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
            vg_bai_tu_long_ops_fail("Could not confidently refresh {$label}.");
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
        vg_bai_tu_long_ops_fail("Could not refresh {$label}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_bai_tu_long_ops_fail("Could not refresh {$label}: WordPress returned an empty post ID.");
    }

    vg_bai_tu_long_ops_log("Refreshed {$label}: {$page->ID}");
}

function vg_bai_tu_long_ops_refresh_destinations_hub(): void
{
    $hub = get_page_by_path('destinations', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_bai_tu_long_ops_log('Skipped Destinations hub refresh: destinations page was not found or is not published.');
        return;
    }

    if (! vg_bai_tu_long_ops_published_page_exists('destinations/bai-tu-long-bay-guide')) {
        vg_bai_tu_long_ops_log('Skipped Destinations hub refresh: Bai Tu Long Bay Guide is not published.');
        return;
    }

    $marker = '<!-- vg-bai-tu-long-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Separate quieter bay value from generic cruise copy</h3><p>The <a href="/destinations/bai-tu-long-bay-guide/">Bai Tu Long Bay Guide</a> helps travelers decide whether a quieter, route-specific bay cruise is worth the extra buying friction compared with Ha Long, Lan Ha, or a Cat Ba island base.</p></div>
<!-- /wp:group -->
HTML;

    vg_bai_tu_long_ops_assert_internal_page_links_are_published('Destinations hub Bai Tu Long note', $block);
    vg_bai_tu_long_ops_upsert_marked_group($hub, 'Destinations hub Bai Tu Long note', $marker, $block);
}

function vg_bai_tu_long_ops_add_related_route_to_page(string $path, string $label, string $route_line): void
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_bai_tu_long_ops_log("Skipped related-route refresh for {$label}: page was not found or is not published.");
        return;
    }

    vg_bai_tu_long_ops_assert_internal_page_links_are_published("{$label} related route line", $route_line);

    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);

    if (str_contains($current, '/destinations/bai-tu-long-bay-guide/')) {
        vg_bai_tu_long_ops_log("Skipped related-route refresh for {$label}: Bai Tu Long guide is already present.");
        return;
    }

    $next = trim($current) === '' ? $route_line : rtrim($current) . "\n" . $route_line;
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_bai_tu_long_ops_log("Added Bai Tu Long guide related route to {$label}: {$page->ID}");
}

function vg_bai_tu_long_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Bai Tu Long Bay Guide | /destinations/bai-tu-long-bay-guide/ | Decide whether a quieter, route-specific bay cruise is worth the extra buying friction versus Ha Long, Lan Ha, or Cat Ba.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
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
        vg_bai_tu_long_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_bai_tu_long_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/bai-tu-long-bay-guide', OBJECT, 'page');

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_bai_tu_long_ops_force_republish_enabled()) {
    vg_bai_tu_long_ops_fail('Bai Tu Long Bay Guide is not a draft. Set VG_FORCE_BAI_TU_LONG_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_bai_tu_long_ops_log("Preflight Bai Tu Long Bay Guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_bai_tu_long_ops_log('Preflight Bai Tu Long Bay Guide: no existing page found; creating a child page under /destinations/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/4/46/B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg/1920px-B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg');
$boat_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d2/B%C3%A1i_T%E1%BB%AD_Long_Bay_-_02.jpg/1920px-B%C3%A1i_T%E1%BB%AD_Long_Bay_-_02.jpg');
$boat_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_02.jpg');
$panorama_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b4/B%C3%A1i_T%E1%BB%AD_Long_Bay_-_03.jpg/1920px-B%C3%A1i_T%E1%BB%AD_Long_Bay_-_03.jpg');
$panorama_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_03.jpg');
$fishing_boat_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/1a/B%C3%A1i_T%E1%BB%AD_Long_Bay_-_04.jpg/1920px-B%C3%A1i_T%E1%BB%AD_Long_Bay_-_04.jpg');
$fishing_boat_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_04.jpg');
$floating_village_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f5/B%C3%A1i_T%E1%BB%AD_Long_Bay_-_05.jpg/1920px-B%C3%A1i_T%E1%BB%AD_Long_Bay_-_05.jpg');
$floating_village_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_05.jpg');
$floating_village_two_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/14/B%C3%A1i_T%E1%BB%AD_Long_Bay_-_06.jpg/1920px-B%C3%A1i_T%E1%BB%AD_Long_Bay_-_06.jpg');
$floating_village_two_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_06.jpg');
$tourist_boat_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/a/a4/B%C3%A1i_T%E1%BB%AD_Long_Bay_-_07.jpg/1920px-B%C3%A1i_T%E1%BB%AD_Long_Bay_-_07.jpg');
$tourist_boat_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_07.jpg');

$content = <<<HTML
<!-- vg-bai-tu-long-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Limestone islands and calm water in Bai Tu Long Bay, Vietnam" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 18, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Bai Tu Long Bay Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Bai Tu Long Bay is the quieter-bay question travelers ask after learning that Ha Long can be busy and Lan Ha can be more logistics-sensitive. The right answer is not automatically "choose the quieter bay." Choose Bai Tu Long when the cruise route, port, operator, weather policy, and extra travel time are clear enough to protect the experience.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: Bai Tu Long can feel more spacious than the classic Ha Long lanes, but spaciousness is not a guarantee. The route map and operating details matter more than the word "alternative" in a sales description.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">Benjamin Smith / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-bai-tu-long-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-bai-tu-long-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-bai-tu-long-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Bai Tu Long Bay is best for travelers who value a quieter route enough to verify the route details before paying.</strong> It is not the safest default for every first visit. Use it when the operator can explain the exact itinerary, pickup, pier, activities, cancellation policy, and onward movement. Choose Ha Long or Lan Ha instead when buying clarity, classic sightseeing, or Cat Ba island access matters more.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-bai-tu-long-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-bai-tu-long-shortlist">
<li><strong>Best default:</strong> one-night or two-night cruise only when the route really enters Bai Tu Long and still protects deck time.</li>
<li><strong>Best premium case:</strong> fewer boats, clearer route map, strong cabin/deck quality, and honest weather/cancellation terms.</li>
<li><strong>Best short-route case:</strong> skip Bai Tu Long if the extra buying friction makes a 10-day itinerary fragile.</li>
<li><strong>Best honesty rule:</strong> do not borrow the UNESCO label from Ha Long Bay-Cat Ba Archipelago unless the specific route and source support it.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-bai-tu-long-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-bai-tu-long-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-bai-tu-long-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fastest useful answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-bai-tu-long-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Is Bai Tu Long Bay worth it?</strong></td><td data-label="Answer">Yes, when you are buying a specific quieter route with clear logistics. No, when the promise is only "less touristy" with vague route details.</td></tr>
<tr><td data-label="Question"><strong>How long should I go?</strong></td><td data-label="Answer">One night is the practical default; two nights are best when the north is a slow chapter. Day-only plans often lose the point.</td></tr>
<tr><td data-label="Question"><strong>Bai Tu Long or Ha Long?</strong></td><td data-label="Answer">Ha Long is easier to buy and more classic. Bai Tu Long can feel calmer but requires stronger route verification.</td></tr>
<tr><td data-label="Question"><strong>Bai Tu Long or Lan Ha/Cat Ba?</strong></td><td data-label="Answer">Use Lan Ha/Cat Ba when island access matters. Use Bai Tu Long when the cruise itself is the quieter-bay goal.</td></tr>
<tr><td data-label="Question"><strong>Main risk?</strong></td><td data-label="Answer">Paying more for a vague "alternative bay" product whose route, pier, timing, and weather terms are not clear.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-bai-tu-long-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what the quieter-bay claim should mean</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Bai Tu Long should be judged by the experience these photographs point to: open water, limestone scale, working boats, floating community context, natural arches, and enough unhurried deck time to feel the difference from a rushed standard cruise.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-bai-tu-long-photo-grid" aria-label="Bai Tu Long Bay guide photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Limestone islands in Bai Tu Long Bay" loading="lazy" decoding="async"><figcaption>Choose Bai Tu Long for protected water time, not just a quieter adjective. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Benjamin Smith / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$boat_image}" alt="Fishing boat in Bai Tu Long Bay" loading="lazy" decoding="async"><figcaption>Working boats and route texture are part of the difference from a purely scenic loop. Image: <a href="{$boat_credit_url}" target="_blank" rel="license noopener">Benjamin Smith / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$panorama_image}" alt="Panoramic limestone scenery in Bai Tu Long Bay" loading="lazy" decoding="async"><figcaption>Wide water only matters if the itinerary gives you time to feel it. Image: <a href="{$panorama_credit_url}" target="_blank" rel="license noopener">Benjamin Smith / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$fishing_boat_image}" alt="Boat and limestone karst in Bai Tu Long Bay" loading="lazy" decoding="async"><figcaption>Route density should be checked through the actual operator, not assumed from the bay name. Image: <a href="{$fishing_boat_credit_url}" target="_blank" rel="license noopener">Benjamin Smith / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$floating_village_image}" alt="Floating village context in Bai Tu Long Bay" loading="lazy" decoding="async"><figcaption>Community and aquaculture context can add depth when the stop is paced respectfully. Image: <a href="{$floating_village_credit_url}" target="_blank" rel="license noopener">Benjamin Smith / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$floating_village_two_image}" alt="Floating village structures in Bai Tu Long Bay" loading="lazy" decoding="async"><figcaption>Stops should support the route, not become a rushed queue of checkboxes. Image: <a href="{$floating_village_two_credit_url}" target="_blank" rel="license noopener">Benjamin Smith / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$tourist_boat_image}" alt="Tourist boat and natural arch in Bai Tu Long Bay" loading="lazy" decoding="async"><figcaption>Activity value depends on timing, weather, and how much quiet water remains. Image: <a href="{$tourist_boat_credit_url}" target="_blank" rel="license noopener">Benjamin Smith / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-bai-tu-long-bay-choice:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Bai Tu Long, Ha Long, Lan Ha, or Cat Ba?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The bay choice should be a route decision, not a slogan. Each option solves a different problem.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-bai-tu-long-bay-choice">
<thead>
<tr><th>Choice</th><th>Best when</th><th>What improves</th><th>What to verify</th></tr>
</thead>
<tbody>
<tr><td data-label="Choice"><strong>Bai Tu Long</strong></td><td data-label="Best when">You want a quieter cruise route and can verify details.</td><td data-label="What improves">Space, route mood, and a less standard bay story.</td><td data-label="What to verify">Exact route, pier, timing, activities, cancellation terms, and return point.</td></tr>
<tr><td data-label="Choice"><strong>Ha Long Bay</strong></td><td data-label="Best when">You want the classic, easier-to-buy first-visit cruise.</td><td data-label="What improves">Buying clarity and iconic name recognition.</td><td data-label="What to verify">Cruise density, route map, cabin, and rushed stop risk.</td></tr>
<tr><td data-label="Choice"><strong>Lan Ha Bay</strong></td><td data-label="Best when">You want Cat Ba-linked routing and quieter water access.</td><td data-label="What improves">Island connection and often calmer routing.</td><td data-label="What to verify">Port logistics, Cat Ba role, and weather rerouting.</td></tr>
<tr><td data-label="Choice"><strong>Cat Ba base</strong></td><td data-label="Best when">You want island nights, national park texture, and Lan Ha access.</td><td data-label="What improves">The northern chapter becomes more varied.</td><td data-label="What to verify">Ferry/port logistics, luggage, and onward travel.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-bai-tu-long-route-logistics:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Route and logistics checks before you buy</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Bai Tu Long products can be excellent, but the quality lives in the operational detail. The official Ha Long Bay Management route information is useful because it keeps route names and bay operations close to the planning conversation.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-bai-tu-long-route-logistics">
<thead>
<tr><th>Planning item</th><th>Ask this</th><th>Why it matters</th><th>Source/check</th></tr>
</thead>
<tbody>
<tr><td data-label="Planning item">Route name</td><td data-label="Ask this">Does the cruise use a Bai Tu Long route such as VHL4 or a mixed Ha Long route?</td><td data-label="Why it matters">The bay name alone does not prove the experience.</td><td data-label="Source/check"><a href="https://halongbay.com.vn/tours/4-hanh-trinh-vhl-4-cang-tau-hang-co-thien-canh-son-hang-thay-hang-cap-la-vong-vieng-khu-sinh-thai-tung-ang-dao-cong-do-cong-vien-hon-xep" target="_blank" rel="noopener">Ha Long Bay Management route VHL4</a>.</td></tr>
<tr><td data-label="Planning item">Pier and return</td><td data-label="Ask this">Where do I board, where do I return, and how does this affect Hanoi/Ninh Binh/airport timing?</td><td data-label="Why it matters">A quiet cruise can still break the route if transfer timing is vague.</td><td data-label="Source/check"><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>.</td></tr>
<tr><td data-label="Planning item">Weather policy</td><td data-label="Ask this">What happens if local authorities restrict operations or the route changes?</td><td data-label="Why it matters">Bay trips are weather-sensitive and can be rerouted.</td><td data-label="Source/check"><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>.</td></tr>
<tr><td data-label="Planning item">Operator clarity</td><td data-label="Ask this">Can the operator explain what is quieter, and where the cruise goes?</td><td data-label="Why it matters">Premium value needs evidence, not vague "off the beaten path" copy.</td><td data-label="Source/check"><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-bai-tu-long-cruise-length:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Day cruise, one night, or two nights?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A quieter bay is most valuable when the schedule lets the quiet show up.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-bai-tu-long-cruise-length">
<thead>
<tr><th>Length</th><th>Best for</th><th>Verdict</th><th>Common failure</th></tr>
</thead>
<tbody>
<tr><td data-label="Length">Day cruise</td><td data-label="Best for">Travelers who cannot spare a night but still want bay time.</td><td data-label="Verdict">Usually weaker for Bai Tu Long because transfer-to-water ratio can erase the point.</td><td data-label="Common failure">Buying the alternative label without enough time in the alternative bay.</td></tr>
<tr><td data-label="Length">One night</td><td data-label="Best for">Most first-time travelers who want quieter scenery and one protected bay chapter.</td><td data-label="Verdict">Best default if route details are clear.</td><td data-label="Common failure">Late boarding, early checkout, and too many scheduled stops.</td></tr>
<tr><td data-label="Length">Two nights</td><td data-label="Best for">Slow northern routes, premium comfort, and travelers prioritizing water time.</td><td data-label="Verdict">Best experience when the north leads the trip.</td><td data-label="Common failure">Spending too much route budget while central/south Vietnam gets squeezed.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-bai-tu-long-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where Bai Tu Long fits in the wider route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Bai Tu Long works best as a deliberate bay choice, not as an extra stop after every northern icon has already been added.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-bai-tu-long-route-fit">
<thead>
<tr><th>Route shape</th><th>Best Bai Tu Long use</th><th>Risk</th><th>Related guide</th></tr>
</thead>
<tbody>
<tr><td data-label="Route shape">10 days in Vietnam</td><td data-label="Best Bai Tu Long use">Choose it only if the bay is one of the trip's main memories.</td><td data-label="Risk">Overpaying for a quieter route while the rest of the itinerary becomes too tight.</td><td data-label="Related guide"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">14 days in Vietnam</td><td data-label="Best Bai Tu Long use">One night fits well; two nights fit when the north is intentionally slow.</td><td data-label="Risk">Adding Bai Tu Long, Cat Ba, Ha Long, and Ninh Binh without removing anything.</td><td data-label="Related guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">Hanoi + bay only</td><td data-label="Best Bai Tu Long use">Use it when a quieter cruise is the point of the northern chapter.</td><td data-label="Risk">Missing Cat Ba or Ninh Binh if those were actually better fits.</td><td data-label="Related guide"><a href="/destinations/cat-ba-travel-guide/">Cat Ba Travel Guide</a></td></tr>
<tr><td data-label="Route shape">Hanoi + Ninh Binh + bay</td><td data-label="Best Bai Tu Long use">Pick one bay style and protect it; do not collect bay variants.</td><td data-label="Risk">Limestone fatigue and transfer fatigue.</td><td data-label="Related guide"><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a></td></tr>
<tr><td data-label="Route shape">Heritage/nature focus</td><td data-label="Best Bai Tu Long use">Use Bai Tu Long as a quieter natural landscape chapter, while keeping UNESCO claims precise.</td><td data-label="Risk">Treating adjacent heritage context as a blanket label for every cruise.</td><td data-label="Related guide"><a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-bai-tu-long-best-time-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best time and weather pivots</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Bai Tu Long shares northern bay weather pressures with Ha Long and Lan Ha: visibility, mist, heat, rain, storm risk, and authority decisions can change the experience. Use season guidance for planning, then confirm live operations close to travel.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-bai-tu-long-best-time-weather">
<thead>
<tr><th>Season pressure</th><th>What it changes</th><th>Planning move</th></tr>
</thead>
<tbody>
<tr><td data-label="Season pressure">Clearer shoulder-season windows</td><td data-label="What it changes">Better open-water and photography payoff.</td><td data-label="Planning move">Protect deck time and avoid overpacking activities.</td></tr>
<tr><td data-label="Season pressure">Cooler or misty periods</td><td data-label="What it changes">Atmosphere can be strong but visibility may soften.</td><td data-label="Planning move">Treat moody scenery as a feature, not a failure, if expectations are right.</td></tr>
<tr><td data-label="Season pressure">Hot/rainy or storm-risk periods</td><td data-label="What it changes">Cancellation, rerouting, and transfer buffers matter more.</td><td data-label="Planning move">Avoid critical flights or prepaid onward transfers immediately after disembarkation.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-bai-tu-long-booking-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Booking checks before you commit</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The quieter-bay claim should survive basic questions. If it does not, choose a clearer product.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-bai-tu-long-booking-checks"} -->
<ul class="wp-block-list vg-check-list vg-bai-tu-long-booking-checks">
<li>Ask for the actual route map and route name, not only the phrase Bai Tu Long Bay.</li>
<li>Confirm pier, pickup, return point, transfer duration, luggage handling, and onward timing.</li>
<li>Check whether kayaking, cave, beach, floating village, or small-boat activities are included or optional.</li>
<li>Read cancellation, weather, rerouting, and refund terms before paying a deposit.</li>
<li>Compare cabin/deck quality and staff ratio against the price premium.</li>
<li>Do not schedule a critical international flight or non-refundable domestic move immediately after the cruise.</li>
</ul>
<!-- /wp:list -->

<!-- vg-bai-tu-long-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The premium move is often not choosing the quietest-sounding bay. It is choosing the bay product whose proof matches the route.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-bai-tu-long-skip-logic">
<thead>
<tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you want...">Classic first visit</td><td data-label="Protect this">A simple, reliable bay chapter.</td><td data-label="Skip first">Bai Tu Long if route clarity is weaker than Ha Long.</td><td data-label="Only add back when...">The operator can prove the quieter route.</td></tr>
<tr><td data-label="If you want...">Quiet water</td><td data-label="Protect this">Route map, deck time, and fewer rushed stops.</td><td data-label="Skip first">Any product selling "less touristy" without detail.</td><td data-label="Only add back when...">The itinerary explains where the time is protected.</td></tr>
<tr><td data-label="If you want...">Island/nature depth</td><td data-label="Protect this">Cat Ba or national park value if that is the real goal.</td><td data-label="Skip first">A cruise that does not add island or land texture.</td><td data-label="Only add back when...">The bay cruise is the main experience, not a substitute for Cat Ba.</td></tr>
<tr><td data-label="If you want...">A calm route</td><td data-label="Protect this">Fewer fragile handoffs.</td><td data-label="Skip first">Two-night Bai Tu Long if central/south Vietnam loses its shape.</td><td data-label="Only add back when...">The north is intentionally the main chapter.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-bai-tu-long-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before travel</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-bai-tu-long-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-bai-tu-long-live-checks">
<li>Use <a href="https://halongbay.com.vn/tours/4-hanh-trinh-vhl-4-cang-tau-hang-co-thien-canh-son-hang-thay-hang-cap-la-vong-vieng-khu-sinh-thai-tung-ang-dao-cong-do-cong-vien-hon-xep" target="_blank" rel="noopener">Ha Long Bay Management route VHL4</a> for official route naming and operating context.</li>
<li>Use <a href="https://halongbay.com.vn/en/p/385-khao-sat-thuc-dia-hanh-trinh-ket-noi-vinh-ha-long-bai-tu-long" target="_blank" rel="noopener">Ha Long Bay Management coverage of Ha Long-Bai Tu Long route connection work</a> for current management context.</li>
<li>Use <a href="https://vuonquocgiabaitulong.vn/" target="_blank" rel="noopener">Bai Tu Long National Park</a> for national-park context if a plan claims park or island nature value.</li>
<li>Use <a href="https://vietnam.travel/places-to-go/northern-vietnam/ha-long" target="_blank" rel="noopener">Vietnam.travel Ha Long</a>, <a href="https://vietnam.travel/places-to-go/northern-vietnam" target="_blank" rel="noopener">Vietnam.travel Northern Vietnam</a>, <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a>, and <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport</a> for official planning context.</li>
<li>Use the <a href="https://whc.unesco.org/en/list/672/" target="_blank" rel="noopener">UNESCO Ha Long Bay-Cat Ba Archipelago listing</a> carefully as neighboring heritage context, not a blanket claim for every Bai Tu Long cruise.</li>
<li>Read <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a>, <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>, <a href="/destinations/cat-ba-travel-guide/">Cat Ba Travel Guide</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying a non-refundable cruise deposit.</li>
</ul>
<!-- /wp:list -->

<!-- vg-bai-tu-long-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Bai Tu Long Bay FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-bai-tu-long-faq">
<details><summary>Is Bai Tu Long Bay better than Ha Long Bay?</summary><p>Not automatically. Bai Tu Long can be calmer and more spacious, but Ha Long is easier to buy and more classic. The route map, operator, timing, and weather policy decide which is better for your trip.</p></details>
<details><summary>Is Bai Tu Long Bay less crowded?</summary><p>Often, but do not buy on that claim alone. Ask where the cruise goes, how long it stays there, and what happens if weather or local rules change the route.</p></details>
<details><summary>How many nights do I need?</summary><p>One night is the usual default. Two nights make sense when the north is the main chapter. Day-only trips usually weaken the quiet-bay value.</p></details>
<details><summary>Is Bai Tu Long Bay part of the UNESCO site?</summary><p>Use official sources carefully. UNESCO's current listing is Ha Long Bay-Cat Ba Archipelago. Treat Bai Tu Long as neighboring northern bay context unless the specific route and source support a heritage claim.</p></details>
<details><summary>Should I choose Bai Tu Long or Lan Ha Bay?</summary><p>Choose Bai Tu Long when the cruise itself is the quieter-bay objective. Choose Lan Ha or Cat Ba when island access, Cat Ba logistics, or national park texture matter more.</p></details>
<details><summary>What should I ask before booking?</summary><p>Ask for the route map, route name, pier, pickup/drop-off, activity inclusions, weather policy, cancellation terms, and how the cruise connects to your next destination.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> for the full planning order, compare the classic and quieter bay choices in <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a> and <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>, then test the island alternative in <a href="/destinations/cat-ba-travel-guide/">Cat Ba Travel Guide</a>. Stress-test the wider route with <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>, <a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-bai-tu-long-hero:v1',
    'concierge verdict' => 'vg-bai-tu-long-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-bai-tu-long-at-a-glance:v1',
    'photo grid' => 'vg-bai-tu-long-photo-grid:v1',
    'bay choice' => 'vg-bai-tu-long-bay-choice:v1',
    'route logistics' => 'vg-bai-tu-long-route-logistics:v1',
    'cruise length' => 'vg-bai-tu-long-cruise-length:v1',
    'route fit' => 'vg-bai-tu-long-route-fit:v1',
    'best time weather' => 'vg-bai-tu-long-best-time-weather:v1',
    'booking checks' => 'vg-bai-tu-long-booking-checks:v1',
    'skip logic' => 'vg-bai-tu-long-skip-logic:v1',
    'live checks' => 'vg-bai-tu-long-live-checks:v1',
    'FAQ' => 'vg-bai-tu-long-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_bai_tu_long_ops_assert_required_content_markers($content, $required_content_markers);
vg_bai_tu_long_ops_assert_internal_page_links_are_published('Bai Tu Long Bay Guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Bai Tu Long Bay Guide',
    'post_name'      => 'bai-tu-long-bay-guide',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_bai_tu_long_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Bai Tu Long Bay guide for international travelers deciding quieter bay cruise value, route details, Ha Long/Lan Ha/Cat Ba alternatives, weather, costs, and booking checks.',
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
    vg_bai_tu_long_ops_fail('Could not publish Bai Tu Long Bay Guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_bai_tu_long_ops_fail('Could not publish Bai Tu Long Bay Guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Bai Tu Long Bay Guide: Quieter Cruise or Skip?');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Bai Tu Long Bay guide: decide quieter bay cruise value, Ha Long vs Lan Ha vs Cat Ba alternatives, route logistics, weather, costs, booking checks, and skip logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Bai Tu Long Bay guide');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Bai Tu Long Bay is worth choosing as a quieter, route-specific cruise instead of Ha Long, Lan Ha, or a Cat Ba island base.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 18, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Bai Tu Long Bay Guide with a concierge verdict, at-a-glance decision table, licensed photo proof, bay-choice matrix, route/logistics table, cruise-length matrix, route-fit table, weather pivots, booking checks, skip logic, live official checks, FAQ, Destinations hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Ha Long Bay Management - route VHL4 - https://halongbay.com.vn/tours/4-hanh-trinh-vhl-4-cang-tau-hang-co-thien-canh-son-hang-thay-hang-cap-la-vong-vieng-khu-sinh-thai-tung-ang-dao-cong-do-cong-vien-hon-xep - checked July 18, 2026\nHa Long Bay Management - Ha Long-Bai Tu Long route connection context - https://halongbay.com.vn/en/p/385-khao-sat-thuc-dia-hanh-trinh-ket-noi-vinh-ha-long-bai-tu-long - checked July 18, 2026\nBai Tu Long National Park - https://vuonquocgiabaitulong.vn/ - checked July 18, 2026\nVietnam.travel - Ha Long destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-long - checked July 18, 2026\nVietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked July 18, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 18, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 18, 2026\nQuang Ninh portal - https://www.quangninh.gov.vn/ - checked July 18, 2026\nUNESCO World Heritage Centre - Ha Long Bay-Cat Ba Archipelago - https://whc.unesco.org/en/list/672/ - checked July 18, 2026; automation may receive 403 because of access challenge\nWikimedia Commons image record - Bai Tu Long Bay 01 - https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg - license checked July 18, 2026\nWikimedia Commons image record - Bai Tu Long Bay 02 - https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_02.jpg - license checked July 18, 2026\nWikimedia Commons image record - Bai Tu Long Bay 03 - https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_03.jpg - license checked July 18, 2026\nWikimedia Commons image record - Bai Tu Long Bay 04 - https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_04.jpg - license checked July 18, 2026\nWikimedia Commons image record - Bai Tu Long Bay 05 - https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_05.jpg - license checked July 18, 2026\nWikimedia Commons image record - Bai Tu Long Bay 06 - https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_06.jpg - license checked July 18, 2026\nWikimedia Commons image record - Bai Tu Long Bay 07 - https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_07.jpg - license checked July 18, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Bai Tu Long Bay is compelling when the route proves the quieter-bay claim; it is weak when sold as a vague alternative without pier, route, weather, and return details.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Bai Tu Long guide built around quieter-bay buying judgment rather than generic cruise-list copy.\nConcierge verdict that separates premium quiet-route cases from weak alternative-bay claims.\nAt-a-glance table answering worth, timing, Ha Long, Lan Ha/Cat Ba, and route-detail risk.\nLicensed photo proof for Bai Tu Long scenery, fishing boats, panorama, floating village context, and tourist boat/natural arch.\nBay-choice matrix comparing Bai Tu Long, Ha Long, Lan Ha, and Cat Ba by traveler objective.\nRoute/logistics section anchored to Ha Long Bay Management route and management context.\nCruise-length matrix that discourages weak day-only Bai Tu Long plans.\nRoute-fit table linked to 10 Days, 14 Days, Cat Ba, Ninh Binh, and UNESCO content.\nBest-time section grounded in northern bay weather and operations discipline.\nBooking checks for route map, pier, pickup, return point, activities, weather, refund, and onward buffer.\nSkip logic that protects itinerary value over quiet-bay marketing pressure.\nLive-check list tied to official management, Bai Tu Long National Park, Vietnam.travel, UNESCO, Ha Long, Lan Ha, Cat Ba, best time, transport, and cost pages.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Compare the classic bay default before paying extra for a quieter route.\nHa Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Compare route, port, cruise style, Cat Ba access, and weather risk before choosing a bay.\nCat Ba Travel Guide | /destinations/cat-ba-travel-guide/ | Decide whether island-base and Lan Ha access matter more than a quieter cruise route.\nNinh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide whether the northern landscape chapter should be countryside, bay, island, or a calmer combination.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before adding a quieter-bay decision.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Compare Bai Tu Long against the broader destination shortlist.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Keep heritage claims precise and route-filter natural landscapes.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check northern visibility, heat, rain, and storm-season pressure before locking the bay.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Confirm pier, pickup, luggage, and onward movement before booking.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget for cruise class, private transfers, flexibility, and route buffers.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Bai Tu Long improves or overloads a short route.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when Bai Tu Long can become a protected northern bay chapter.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check water, weather, transfer, and interruption coverage.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair cruise deposits, transfers, water activity, and cancellation rules with practical risk checks.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and body images: Bai Tu Long Bay 01, 02, 03, 04, 05, 06, and 07 by Benjamin Smith, CC BY-SA 4.0.');
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
        vg_bai_tu_long_ops_fail("Required metadata was empty after update: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_bai_tu_long_ops_fail('Bai Tu Long Bay Guide was updated but is not published.');
}

vg_bai_tu_long_ops_refresh_destinations_hub();
vg_bai_tu_long_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_bai_tu_long_ops_log("Published Bai Tu Long Bay Guide: {$page_id} {$updated_permalink}");

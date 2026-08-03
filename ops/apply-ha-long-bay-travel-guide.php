<?php
/**
 * Publish the Ha Long Bay Travel Guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-ha-long-bay-travel-guide.php --allow-root
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
    $value = getenv('VG_FORCE_HA_LONG_BAY_GUIDE_REPUBLISH');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the Ha Long Bay Travel Guide.');
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

    if (! vg_ops_published_page_exists('destinations/ha-long-bay-travel-guide')) {
        vg_ops_log('Skipped Destinations hub refresh: Ha Long Bay guide is not published.');
        return;
    }

    $marker = '<!-- vg-ha-long-bay-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Make the bay a real northern chapter</h3><p>The <a href="/destinations/ha-long-bay-travel-guide/">Ha Long Bay Travel Guide</a> helps travelers choose cruise length, route type, port, season, Cat Ba/Lan Ha fit, and skip logic before the bay becomes an expensive rushed transfer.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Destinations hub Ha Long Bay note', $block);
    vg_ops_upsert_marked_group($hub, 'Destinations hub Ha Long Bay note', $marker, $block);
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

    if (str_contains($current, '/destinations/ha-long-bay-travel-guide/')) {
        vg_ops_log("Skipped related-route refresh for {$label}: Ha Long Bay guide is already present.");
        return;
    }

    $next = trim($current) === '' ? $route_line : rtrim($current) . "\n" . $route_line;
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_ops_log("Added Ha Long Bay guide related route to {$label}: {$page->ID}");
}

function vg_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Ha Long Bay Travel Guide | /destinations/ha-long-bay-travel-guide/ | Choose cruise length, bay route, port, season, and booking checks before adding the bay to the itinerary.';

    foreach (
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
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
        vg_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/ha-long-bay-travel-guide', OBJECT, 'page');

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_ops_force_republish_enabled()) {
    vg_ops_fail('Ha Long Bay Travel Guide is not a draft. Set VG_FORCE_HA_LONG_BAY_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    vg_ops_log("Preflight Ha Long Bay Travel Guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_ops_log('Preflight Ha Long Bay Travel Guide: no existing page found; creating a child page under /destinations/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg/1920px-Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg');
$cruise_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3e/Halong_Bay_Cruise_Boats_01.jpg/1920px-Halong_Bay_Cruise_Boats_01.jpg');
$cruise_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Halong_Bay_Cruise_Boats_01.jpg');
$titov_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/28/View_of_sea_from_Titov_Island%2C_Ha_Long_Bay%2C_Vietnam%2C_20240128_1337_3732.jpg/1920px-View_of_sea_from_Titov_Island%2C_Ha_Long_Bay%2C_Vietnam%2C_20240128_1337_3732.jpg');
$titov_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:View_of_sea_from_Titov_Island,_Ha_Long_Bay,_Vietnam,_20240128_1337_3732.jpg');
$sung_sot_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/10/Sung_Sot_Cave%2C_Ha_Long_Bay%2C_Vietnam%2C_20240128_1549_3836.jpg/1920px-Sung_Sot_Cave%2C_Ha_Long_Bay%2C_Vietnam%2C_20240128_1549_3836.jpg');
$sung_sot_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Sung_Sot_Cave,_Ha_Long_Bay,_Vietnam,_20240128_1549_3836.jpg');
$lan_ha_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/ca/Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg/1920px-Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg');
$lan_ha_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg');
$cat_ba_park_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/fb/Cat_Ba_National_Park%2C_Vietnam%2C_20240131_0922_4534.jpg/1920px-Cat_Ba_National_Park%2C_Vietnam%2C_20240131_0922_4534.jpg');
$cat_ba_park_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cat_Ba_National_Park,_Vietnam,_20240131_0922_4534.jpg');

$content = <<<HTML
<!-- vg-ha-long-bay-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":55,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Aerial view of limestone islands in Ha Long Bay, Vietnam, used for a Ha Long Bay travel guide" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 18, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Ha Long Bay Travel Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Ha Long Bay is Vietnam's classic northern seascape, but the best trip is not built by simply buying the first cruise that says UNESCO. This guide helps international travelers decide whether the bay deserves a day cruise, one-night cruise, two-night cruise, Cat Ba/Lan Ha route, or a skip in favor of a calmer northern itinerary.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: Ha Long Bay becomes premium when the route gives it protected water time. It becomes forgettable when it is squeezed between Hanoi traffic, a late boarding, an early checkout, and a flight that should never have been that close.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-ha-long-bay-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-ha-long-bay-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-ha-long-bay-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most first-time visitors, Ha Long Bay is worth one night if the route has enough slack and the cruise product is strong.</strong> Choose a day cruise only when time is genuinely tight. Choose two nights only when the north is the trip's main chapter. Choose Lan Ha/Cat Ba when quieter routing or island access matters more than the classic Ha Long label.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-ha-long-bay-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-ha-long-bay-shortlist">
<li><strong>Best default:</strong> one-night cruise with real sunset, deck time, one morning on the bay, and a transfer plan that does not threaten the next leg.</li>
<li><strong>Best budget/short route:</strong> day cruise only when the road-to-water ratio still feels honest.</li>
<li><strong>Best premium route:</strong> fewer stops, better cabin/deck space, clean pier logistics, and a weather/cancellation policy you understand.</li>
<li><strong>Best skip rule:</strong> skip the bay if the plan only protects the brand name, not the experience.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-ha-long-bay-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-ha-long-bay-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-ha-long-bay-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fastest useful answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-bay-glance-table">
<tbody>
<tr><td data-label="Question"><strong>Is Ha Long Bay worth it?</strong></td><td data-label="Answer">Yes, if you can protect real bay time and choose a cruise carefully. No, if the route turns it into an expensive transit sandwich.</td></tr>
<tr><td data-label="Question"><strong>How many days do I need?</strong></td><td data-label="Answer">One night is the best default. Day cruise is a compromise. Two nights are for slow or premium northern routes.</td></tr>
<tr><td data-label="Question"><strong>Ha Long or Lan Ha?</strong></td><td data-label="Answer">Use the comparison guide if you are deciding between the classic bay and a quieter Cat Ba-linked route.</td></tr>
<tr><td data-label="Question"><strong>Best season?</strong></td><td data-label="Answer">Vietnam.travel frames September-November as clearer, December-March as misty, April-May as sunny/breezy, and June-August as monsoon-risk months.</td></tr>
<tr><td data-label="Question"><strong>What can go wrong?</strong></td><td data-label="Answer">Weather changes, authority restrictions, weak cruise routing, overlong transfers, poor cabin choice, and booking an early onward departure.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-ha-long-bay-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what you are actually buying</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A Ha Long plan should be judged by the experience these images represent: open seascape, cruise density, viewpoint payoff, caves, Lan Ha alternatives, and Cat Ba nature access.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-ha-long-bay-photo-grid" aria-label="Ha Long Bay travel guide photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Aerial limestone seascape in Ha Long Bay, Vietnam" loading="lazy" decoding="async"><figcaption>The iconic Ha Long image is real, but the itinerary has to protect time to experience it. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cruise_image}" alt="Cruise boats in Ha Long Bay, Vietnam" loading="lazy" decoding="async"><figcaption>Cruise density makes route and operator choice matter. Image: <a href="{$cruise_credit_url}" target="_blank" rel="license noopener">Shyamal L. / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$titov_image}" alt="View of Ha Long Bay from Titov Island" loading="lazy" decoding="async"><figcaption>Viewpoint stops are high-impact when they do not steal the only calm water time. Image: <a href="{$titov_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$sung_sot_image}" alt="Sung Sot Cave in Ha Long Bay, Vietnam" loading="lazy" decoding="async"><figcaption>Caves can add depth, but a cruise should not become a queue of rushed stops. Image: <a href="{$sung_sot_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$lan_ha_image}" alt="Lan Ha Bay scenery near Cat Ba Island" loading="lazy" decoding="async"><figcaption>Lan Ha is the quieter-route question every Ha Long buyer should understand. Image: <a href="{$lan_ha_credit_url}" target="_blank" rel="license noopener">Saaremees / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$cat_ba_park_image}" alt="Forest in Cat Ba National Park, Vietnam" loading="lazy" decoding="async"><figcaption>Cat Ba changes the bay from a cruise-only decision into an island-and-nature route. Image: <a href="{$cat_ba_park_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-ha-long-bay-days-cruise:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Day cruise, one night, or two nights?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This is the main Ha Long Bay decision. The right answer depends on whether the bay is a protected chapter or a name you are trying to squeeze in.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-bay-days-cruise">
<thead>
<tr><th>Plan</th><th>Best for</th><th>Keep</th><th>Skip first</th><th>Verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Plan">Day cruise</td><td data-label="Best for">Very short trips, Hanoi-based travelers, or routes where an overnight would break the next chapter.</td><td data-label="Keep">Clean transfer, realistic water time, lunch, and one strong activity.</td><td data-label="Skip first">Long detours, too many stops, and any tour with more road than bay.</td><td data-label="Verdict">Acceptable compromise, not the best version.</td></tr>
<tr><td data-label="Plan">One-night cruise</td><td data-label="Best for">Most first-time visitors with 10-14 days.</td><td data-label="Keep">Sunset, cabin comfort, deck time, one morning, and a sane checkout.</td><td data-label="Skip first">Weak cabins, vague route maps, and tight onward flights.</td><td data-label="Verdict">Best default.</td></tr>
<tr><td data-label="Plan">Two-night cruise</td><td data-label="Best for">Premium, slow, photography, honeymoon, and north-heavy trips.</td><td data-label="Keep">A real second-day route that goes deeper or quieter.</td><td data-label="Skip first">Paying for an extra night that repeats the same busy lane.</td><td data-label="Verdict">Worth it only when the second day changes the experience.</td></tr>
<tr><td data-label="Plan">Cat Ba/Lan Ha base</td><td data-label="Best for">Independent travelers, quieter-route seekers, and active island/nature plans.</td><td data-label="Keep">Cat Ba logistics, Lan Ha boat time, and island recovery.</td><td data-label="Skip first">Confusing cheaper with simpler.</td><td data-label="Verdict">Strong alternative when you want more than a packaged cruise.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-bay-where-to-base:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where to base the bay decision</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Most travelers should choose the cruise/pier first, not the hotel city first. The port decides how much of the day disappears before the water starts.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-bay-base-table">
<thead>
<tr><th>Base or port logic</th><th>Best for</th><th>Watch out for</th><th>Verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Base or port logic">Hanoi pickup</td><td data-label="Best for">First-timers who want a packaged, low-admin cruise.</td><td data-label="Watch out for">Pickup timing, highway stops, late boarding, and next-day return time.</td><td data-label="Verdict">Best simple default.</td></tr>
<tr><td data-label="Base or port logic">Ha Long / Tuan Chau area</td><td data-label="Best for">Travelers already in the area, early boarding, or a pre-cruise hotel night.</td><td data-label="Watch out for">Adding an extra hotel move that does not improve the cruise.</td><td data-label="Verdict">Useful when timing improves meaningfully.</td></tr>
<tr><td data-label="Base or port logic">Hai Phong / Cat Ba</td><td data-label="Best for">Lan Ha, Cat Ba, active travelers, and routes connecting via Hai Phong.</td><td data-label="Watch out for">Ferry/road details, pier location, and onward travel.</td><td data-label="Verdict">Best when Cat Ba is a chapter, not just a cheaper gateway.</td></tr>
<tr><td data-label="Base or port logic">Direct airport or same-day flight</td><td data-label="Best for">Rare cases with a very controlled private transfer and generous buffer.</td><td data-label="Watch out for">Weather, late disembarkation, traffic, and missed connections.</td><td data-label="Verdict">Usually too fragile for a premium route.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-bay-priority-map:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to prioritize in Ha Long Bay</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The bay has plenty of famous activities. Prioritize the ones that change the experience instead of stacking everything into a noisy schedule.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-bay-priority-map">
<thead>
<tr><th>Experience</th><th>Why it matters</th><th>Best fit</th><th>Watch out for</th><th>Verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Experience">Deck time</td><td data-label="Why it matters">The bay is most memorable when you actually sit with the seascape.</td><td data-label="Best fit">Every cruise type.</td><td data-label="Watch out for">Schedules that over-program the only calm hours.</td><td data-label="Verdict">Protect this first.</td></tr>
<tr><td data-label="Experience">Kayaking or small-boat time</td><td data-label="Why it matters">It brings the limestone scale closer than the main boat can.</td><td data-label="Best fit">Active travelers, couples, older children, and fair weather.</td><td data-label="Watch out for">Safety briefings, weather, crowds, and vague inclusions.</td><td data-label="Verdict">High value when conditions are right.</td></tr>
<tr><td data-label="Experience">Cave visit</td><td data-label="Why it matters">Adds geological context and a break from deck-only scenery.</td><td data-label="Best fit">First-timers and travelers who want structured stops.</td><td data-label="Watch out for">Queues, slippery paths, heat, and losing quiet bay time.</td><td data-label="Verdict">Worth one strong cave, not a cave marathon.</td></tr>
<tr><td data-label="Experience">Viewpoint or island stop</td><td data-label="Why it matters">Gives the bay a sense of scale from above.</td><td data-label="Best fit">Photographers and active travelers.</td><td data-label="Watch out for">Crowded peaks and exposed heat.</td><td data-label="Verdict">Add if timing is good, skip if rushed.</td></tr>
<tr><td data-label="Experience">Floating village context</td><td data-label="Why it matters">Adds human geography to a landscape that can otherwise feel purely scenic.</td><td data-label="Best fit">Culture-curious travelers and slower cruises.</td><td data-label="Watch out for">Overstated marketing or rushed token visits.</td><td data-label="Verdict">Good when handled respectfully and not as filler.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-bay-best-time-weather:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Best time to visit Ha Long Bay</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam.travel's Ha Long guidance is a useful starting point: clearer skies are common from September to November, mist can shape December to March, April and May often bring sun and breeze, and June to August can be monsoon-fragile. Build flexibility around that instead of chasing a perfect guarantee.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-bay-weather-table">
<thead>
<tr><th>Window</th><th>What it helps</th><th>What to protect</th><th>Best move</th></tr>
</thead>
<tbody>
<tr><td data-label="Window">September to November</td><td data-label="What it helps">Clearer skies and stronger classic views.</td><td data-label="What to protect">Peak-date availability and price.</td><td data-label="Best move">Book a strong one-night cruise early enough to choose well.</td></tr>
<tr><td data-label="Window">December to March</td><td data-label="What it helps">Mist, atmosphere, cooler days, and less heat stress.</td><td data-label="What to protect">Expectation: not every day is postcard clear.</td><td data-label="Best move">Choose cabin/deck comfort and a route that still works in mist.</td></tr>
<tr><td data-label="Window">April to May</td><td data-label="What it helps">Sun, breeze, and attractive shoulder-season conditions.</td><td data-label="What to protect">Holiday/weekend crowd pressure.</td><td data-label="Best move">Good window for first-timers and premium routes.</td></tr>
<tr><td data-label="Window">June to August</td><td data-label="What it helps">Summer energy and green scenery.</td><td data-label="What to protect">Monsoon disruption, heat, storms, and cancellation policy.</td><td data-label="Best move">Keep buffers and avoid crucial onward travel right after disembarkation.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-bay-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How Ha Long Bay fits the wider route</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ha Long Bay works best when it is a deliberate northern chapter, not a famous name pasted between transfers.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-bay-route-fit">
<thead>
<tr><th>Route shape</th><th>Best bay choice</th><th>Risk</th><th>Related guide</th></tr>
</thead>
<tbody>
<tr><td data-label="Route shape">10 days in Vietnam</td><td data-label="Best bay choice">One-night cruise only if you keep the rest of the route tight.</td><td data-label="Risk">Doing Hanoi, Ninh Binh, Ha Long, Hue, Hoi An, and the south without slack.</td><td data-label="Related guide"><a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">14 days in Vietnam</td><td data-label="Best bay choice">One-night cruise is easy; two nights if the north leads the trip.</td><td data-label="Risk">Using extra days to add too many regions instead of making key stops better.</td><td data-label="Related guide"><a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a></td></tr>
<tr><td data-label="Route shape">Hanoi + Ninh Binh + bay</td><td data-label="Best bay choice">Protect one countryside chapter and one bay chapter, then cut duplicate add-ons.</td><td data-label="Risk">Limestone fatigue and transfer fatigue.</td><td data-label="Related guide"><a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a></td></tr>
<tr><td data-label="Route shape">UNESCO/nature focus</td><td data-label="Best bay choice">Treat Ha Long Bay-Cat Ba Archipelago as a major natural heritage stop.</td><td data-label="Risk">Collecting heritage names without enough time.</td><td data-label="Related guide"><a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a></td></tr>
<tr><td data-label="Route shape">Bay choice unclear</td><td data-label="Best bay choice">Compare Ha Long and Lan Ha before choosing the cruise product.</td><td data-label="Risk">Buying the famous label when the quieter route would fit better.</td><td data-label="Related guide"><a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-bay-booking-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Booking checks before you pay</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Cruise pages often look similar. The details below decide whether the bay feels polished or generic.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-ha-long-bay-booking-checks"} -->
<ul class="wp-block-list vg-check-list vg-ha-long-bay-booking-checks">
<li>Confirm pier, pickup point, drop-off point, and real driving time.</li>
<li>Ask for the route map, not only the bay name.</li>
<li>Check cabin size, window/balcony type, deck space, food style, and noise risk.</li>
<li>Verify kayaking, cave, viewpoint, swimming, and small-boat activities are included or optional.</li>
<li>Read weather, cancellation, refund, and rerouting terms before paying a deposit.</li>
<li>Keep an emergency contact and payment fallback, especially if the cruise requires a deposit outside a major booking platform.</li>
<li>Do not schedule a critical international flight immediately after disembarkation.</li>
</ul>
<!-- /wp:list -->

<!-- vg-ha-long-bay-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The premium move is often cutting the bay version that does not fit, not adding more activities to justify the booking.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-ha-long-bay-skip-logic">
<thead>
<tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you want...">Classic first visit</td><td data-label="Protect this">One-night cruise, deck time, and clean return to Hanoi or onward travel.</td><td data-label="Skip first">Extra cave/viewpoint stops that remove quiet time.</td><td data-label="Only add back when...">They are paced well and weather cooperates.</td></tr>
<tr><td data-label="If you want...">Short route efficiency</td><td data-label="Protect this">A single strong northern landscape decision.</td><td data-label="Skip first">Doing both bay and Ninh Binh if neither gets enough time.</td><td data-label="Only add back when...">The wider itinerary loses a weaker stop.</td></tr>
<tr><td data-label="If you want...">Premium comfort</td><td data-label="Protect this">Cabin, deck, food, staff, route map, and cancellation clarity.</td><td data-label="Skip first">Cheap upgrades that do not improve the actual route.</td><td data-label="Only add back when...">The operator can explain exactly what gets better.</td></tr>
<tr><td data-label="If you want...">Quieter scenery</td><td data-label="Protect this">Lan Ha/Cat Ba or less crowded sailing lanes.</td><td data-label="Skip first">A busy standard route sold as exclusive.</td><td data-label="Only add back when...">The route map supports the claim.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-ha-long-bay-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before travel</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-ha-long-bay-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-ha-long-bay-live-checks">
<li>Use <a href="https://vietnam.travel/places-to-go/northern-vietnam/ha-long" target="_blank" rel="noopener">Vietnam.travel Ha Long</a> and <a href="https://vietnam.travel/places-to-go/northern-vietnam" target="_blank" rel="noopener">Vietnam.travel Northern Vietnam</a> for official destination context.</li>
<li>Use the <a href="https://whc.unesco.org/en/list/672/" target="_blank" rel="noopener">UNESCO Ha Long Bay-Cat Ba Archipelago listing</a> for current world heritage framing.</li>
<li>Use <a href="https://halongbay.com.vn/" target="_blank" rel="noopener">Ha Long Bay Management</a> for local bay route, ticket, service, and operational context close to travel.</li>
<li>Use <a href="https://catba.com.vn/" target="_blank" rel="noopener">Cat Ba tourism/service information</a> if your plan uses Lan Ha Bay, Cat Ba Island, or a Hai Phong/Cat Ba gateway.</li>
<li>Read <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a>, and <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> before paying a non-refundable cruise deposit.</li>
</ul>
<!-- /wp:list -->

<!-- vg-ha-long-bay-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Ha Long Bay travel FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-ha-long-bay-faq">
<details><summary>Is Ha Long Bay too touristy?</summary><p>It can be, especially on popular routes and peak dates. That does not mean skip it automatically; it means choose the cruise route, operator, and date carefully.</p></details>
<details><summary>Is one night enough?</summary><p>For most first-time travelers, yes. One night gives a better bay memory than a day cruise without taking as much route space as two nights.</p></details>
<details><summary>Should I choose Ha Long Bay or Lan Ha Bay?</summary><p>Choose Ha Long for the classic easier-to-buy cruise. Choose Lan Ha when you want quieter Cat Ba-linked routing and have checked port logistics. Use the comparison guide before booking.</p></details>
<details><summary>Can I visit Ha Long Bay after Ninh Binh?</summary><p>Yes, but it needs a clean transfer plan. Ninh Binh plus the bay is strong when each has enough time; weak when both become rushed limestone checkboxes.</p></details>
<details><summary>Can weather cancel a cruise?</summary><p>It can. Cruise operations can change because of weather or local authority decisions. Read the cancellation and rerouting policy before paying.</p></details>
<details><summary>Is a luxury cruise worth it?</summary><p>It is worth paying more when the money improves cabin comfort, deck space, food, staff, route quality, and flexibility. It is not worth it when the upgrade is mostly branding.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> for the full planning order, compare the bay choice in <a href="/compare/ha-long-bay-vs-lan-ha-bay/">Ha Long Bay vs Lan Ha Bay</a>, then test the route with <a href="/destinations/ninh-binh-travel-guide/">Ninh Binh Travel Guide</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>, <a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-ha-long-bay-hero:v1',
    'concierge verdict' => 'vg-ha-long-bay-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-ha-long-bay-at-a-glance:v1',
    'photo grid' => 'vg-ha-long-bay-photo-grid:v1',
    'days cruise' => 'vg-ha-long-bay-days-cruise:v1',
    'where to base' => 'vg-ha-long-bay-where-to-base:v1',
    'priority map' => 'vg-ha-long-bay-priority-map:v1',
    'best time weather' => 'vg-ha-long-bay-best-time-weather:v1',
    'route fit' => 'vg-ha-long-bay-route-fit:v1',
    'booking checks' => 'vg-ha-long-bay-booking-checks:v1',
    'skip logic' => 'vg-ha-long-bay-skip-logic:v1',
    'live checks' => 'vg-ha-long-bay-live-checks:v1',
    'FAQ' => 'vg-ha-long-bay-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('Ha Long Bay Travel Guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Ha Long Bay Travel Guide',
    'post_name'      => 'ha-long-bay-travel-guide',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Ha Long Bay travel guide for international travelers choosing cruise length, route type, port, season, Cat Ba/Lan Ha fit, booking checks, and skip logic.',
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
    vg_ops_fail('Could not publish Ha Long Bay Travel Guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Ha Long Bay Travel Guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Ha Long Bay Travel Guide: Cruise, Timing, and Route Fit');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Ha Long Bay travel guide: day cruise vs overnight, Lan Ha/Cat Ba choices, best time, transport, booking checks, route fit, cost pressure, and skip logic.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Ha Long Bay travel guide');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Ha Long Bay deserves a day cruise, one-night cruise, two-night cruise, Lan Ha/Cat Ba route, or a skip before paying a cruise deposit.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 18, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Ha Long Bay Travel Guide with a concierge verdict, at-a-glance decision table, licensed photo proof, day/overnight/two-night matrix, base/port table, priority map, weather section, route-fit table, booking checks, skip logic, live official checks, FAQ, Destinations hub refresh, and inbound related-route links.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Ha Long destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-long - checked July 18, 2026\nVietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked July 18, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 18, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 18, 2026\nUNESCO World Heritage Centre - Ha Long Bay-Cat Ba Archipelago - https://whc.unesco.org/en/list/672/ - checked July 18, 2026\nHa Long Bay Management - https://halongbay.com.vn/ - checked July 18, 2026\nCat Ba tourism/service information - https://catba.com.vn/ - checked July 18, 2026\nWikimedia Commons image record - Ha Long Bay, Vietnam, View from above - https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg - license checked July 18, 2026\nWikimedia Commons image record - Halong Bay Cruise Boats 01 - https://commons.wikimedia.org/wiki/File:Halong_Bay_Cruise_Boats_01.jpg - license checked July 18, 2026\nWikimedia Commons image record - Titov Island view - https://commons.wikimedia.org/wiki/File:View_of_sea_from_Titov_Island,_Ha_Long_Bay,_Vietnam,_20240128_1337_3732.jpg - license checked July 18, 2026\nWikimedia Commons image record - Sung Sot Cave - https://commons.wikimedia.org/wiki/File:Sung_Sot_Cave,_Ha_Long_Bay,_Vietnam,_20240128_1549_3836.jpg - license checked July 18, 2026\nWikimedia Commons image record - Lan Ha Bay-Cat Ba Vietnam - https://commons.wikimedia.org/wiki/File:Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg - license checked July 18, 2026\nWikimedia Commons image record - Cat Ba National Park - https://commons.wikimedia.org/wiki/File:Cat_Ba_National_Park,_Vietnam,_20240131_0922_4534.jpg - license checked July 18, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Ha Long Bay is worth protecting when the itinerary buys real water time, clear logistics, and a cruise product whose route quality is visible before payment.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Ha Long guide built around route and cruise decisions rather than generic attraction copy.\nConcierge verdict that separates day cruise, one-night cruise, two-night cruise, Lan Ha/Cat Ba route, and skip cases.\nAt-a-glance table answering worth, nights, Ha Long vs Lan Ha, season, and failure risks.\nLicensed photo proof for bay aerial, cruise density, viewpoint, cave, Lan Ha, and Cat Ba nature.\nDay/overnight/two-night matrix that prevents expensive rushed bookings.\nBase/port table that turns transfer logic into a planning decision.\nPriority map covering deck time, kayaking, caves, viewpoints, and floating village context.\nBest-time section grounded in Vietnam.travel seasonal framing.\nRoute-fit table linked to 10 Days, 14 Days, Ninh Binh, UNESCO, and comparison content.\nBooking checks for pier, route map, cabin, activities, weather, refund, and onward buffer.\nSkip logic that protects itinerary value over bucket-list pressure.\nLive-check list tied to Vietnam.travel, UNESCO, Ha Long Bay Management, Cat Ba, comparison, transport, cost, insurance, and safety pages.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Ha Long Bay vs Lan Ha Bay | /compare/ha-long-bay-vs-lan-ha-bay/ | Choose the right bay, port, cruise style, and overnight value before booking.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start with the full planning order before adding the bay.\nNinh Binh Travel Guide | /destinations/ninh-binh-travel-guide/ | Decide whether the northern landscape chapter should be countryside, bay, or both.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Decide whether the bay should be a same-day preview, overnight cruise, or skip before buying a long transfer.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Compare bay value against the broader destination shortlist.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Use Ha Long Bay-Cat Ba Archipelago as a natural heritage route decision.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check visibility, mist, monsoon risk, and cancellation pressure.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Confirm pier, pickup, transfer, and onward movement before booking.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget for cruise class, private transfers, flexibility, and route buffers.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether the bay improves or overloads a short route.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Decide when the bay can become a protected northern chapter.\nHealth and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Check water, weather, transfer, and interruption coverage.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair cruise deposits, transfers, and activity choices with practical risk checks.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Ha Long aerial image: Ha Long Bay, Vietnam, View from above by Vyacheslav Argenberg, CC BY 4.0. Body images: Halong Bay Cruise Boats 01 by Shyamal L., CC BY-SA 4.0; Titov Island view by Jakub Halun, CC BY 4.0; Sung Sot Cave by Jakub Halun, CC BY 4.0; Lan Ha Bay-Cat Ba by Saaremees, CC BY-SA 4.0; Cat Ba National Park by Jakub Halun, CC BY 4.0.');
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
    vg_ops_fail('Ha Long Bay Travel Guide was updated but is not published.');
}

vg_ops_refresh_destinations_hub();
vg_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Ha Long Bay Travel Guide: {$page_id} {$updated_permalink}");

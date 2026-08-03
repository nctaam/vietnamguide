<?php
/**
 * Publish the Best Things to Do in Hanoi guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-best-things-hanoi-guide.php --allow-root
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
    $value = getenv('VG_FORCE_HANOI_GUIDE_REPUBLISH');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the Best Things to Do in Hanoi guide.');
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

    if (! vg_ops_published_page_exists('destinations/best-things-to-do-in-hanoi')) {
        vg_ops_log('Skipped Destinations hub refresh: Hanoi guide is not published.');
        return;
    }

    $marker = '<!-- vg-best-things-hanoi-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Make Hanoi a real first chapter</h3><p>The <a href="/destinations/best-things-to-do-in-hanoi/">Best Things to Do in Hanoi</a> guide helps travelers choose the capital experiences that deserve limited first-trip time, and which ones to skip when the route is already tight.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Destinations hub Hanoi note', $block);

    $marked_block = $marker . "\n" . $block;

    if (str_contains($hub->post_content, $marked_block)) {
        vg_ops_log('Skipped Destinations hub refresh: current Hanoi note already present.');
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
            vg_ops_fail('Could not confidently refresh the Destinations hub Hanoi note.');
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
        vg_ops_fail('Could not refresh Destinations hub Hanoi note: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Destinations hub Hanoi note: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Destinations hub Hanoi note: {$hub->ID}");
}

function vg_ops_refresh_best_places_related_routes(): void
{
    $page = get_page_by_path('destinations/best-places-to-visit-vietnam', OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ops_log('Skipped Best Places related routes refresh: guide was not found or is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('destinations/best-things-to-do-in-hanoi')) {
        vg_ops_log('Skipped Best Places related routes refresh: Hanoi guide is not published.');
        return;
    }

    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);
    $needle = '/destinations/best-things-to-do-in-hanoi/';

    if (str_contains($current, $needle)) {
        vg_ops_log('Skipped Best Places related routes refresh: Hanoi link already present.');
        return;
    }

    $updated = rtrim($current);

    if ($updated !== '') {
        $updated .= "\n";
    }

    $updated .= "Best Things to Do in Hanoi | /destinations/best-things-to-do-in-hanoi/ | Decide which Hanoi experiences deserve first-trip time before adding another day trip.";

    update_post_meta($page->ID, 'vg_eeat_related_routes', $updated);
    vg_ops_log("Refreshed Best Places related routes with Hanoi: {$page->ID}");
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/best-things-to-do-in-hanoi', OBJECT, 'page');
$allow_republish = vg_ops_force_republish_enabled();

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('Best Things to Do in Hanoi guide is not a draft. Set VG_FORCE_HANOI_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    $permalink = get_permalink($page);
    vg_ops_log("Preflight Best Things to Do in Hanoi guide: {$page->ID} {$page->post_status} {$permalink}");
} else {
    vg_ops_log('Preflight Best Things to Do in Hanoi guide: no existing page found; creating a child page under /destinations/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg');
$dong_xuan_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/Cho_Dong_Xuan%2C_Old_Quarter%2C_Hanoi%2C_Vietnam_%285245806573%29.jpg/1920px-Cho_Dong_Xuan%2C_Old_Quarter%2C_Hanoi%2C_Vietnam_%285245806573%29.jpg');
$dong_xuan_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cho_Dong_Xuan,_Old_Quarter,_Hanoi,_Vietnam_(5245806573).jpg');
$temple_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/Main_gate_of_the_Temple_of_Literature%2C_Hanoi%2C_Vietnam%2C_20240123_0929_3068.jpg/1920px-Main_gate_of_the_Temple_of_Literature%2C_Hanoi%2C_Vietnam%2C_20240123_0929_3068.jpg');
$temple_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Main_gate_of_the_Temple_of_Literature,_Hanoi,_Vietnam,_20240123_0929_3068.jpg');
$thang_long_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0f/Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg/1920px-Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg');
$thang_long_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg');
$train_street_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/7/70/Train_street_in_Hanoi.jpg/1920px-Train_street_in_Hanoi.jpg');
$train_street_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Train_street_in_Hanoi.jpg');

$content = <<<HTML
<!-- vg-best-things-hanoi-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":56,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hoan Kiem Lake in central Hanoi, Vietnam, used for a Hanoi things to do guide" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 17, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Best Things to Do in Hanoi</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Hanoi is not just the airport before Ninh Binh, Ha Long Bay, or the mountains. The best things to do in Hanoi help you understand the north before the route starts moving: old-quarter rhythm, lake walks, food, capital history, museums, and one or two carefully chosen heritage stops.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: a strong Hanoi plan is selective. Give the city one clean orientation day before adding day trips, and do not let a checklist steal the first real meal, walk, or rest window.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">Alex 69200 vx / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-best-things-hanoi-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-best-things-hanoi-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-best-things-hanoi-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most first-time visitors, the best Hanoi sequence is Hoan Kiem and the Old Quarter, one food-led evening, one serious culture stop, and one calmer museum or cafe block.</strong> Add Thang Long, the Temple of Literature, the Vietnamese Women's Museum, or a day trip only when it improves the route more than it compresses the first two nights.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-best-things-hanoi-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-best-things-hanoi-shortlist">
<li><strong>Best first walk:</strong> Hoan Kiem Lake into the Old Quarter, early or late enough to feel the city without wasting energy in midday heat.</li>
<li><strong>Best first evening:</strong> a guided or self-led food route that keeps you close to the hotel and teaches ordering, pace, and street-level confidence.</li>
<li><strong>Best heritage choice:</strong> Thang Long for capital history, or the Temple of Literature when you want a quieter, more legible cultural stop.</li>
<li><strong>Best skip rule:</strong> do not chase every famous attraction before a long countryside, bay, or mountain transfer the next morning.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- vg-hanoi-travel-best-things-support:v1 -->
<!-- wp:group {"className":"vg-route-support vg-hanoi-travel-support","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-route-support vg-hanoi-travel-support">
<!-- wp:heading -->
<h2 class="wp-block-heading">Use the Hanoi Travel Guide before choosing attractions</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>If you are still deciding how many Hanoi nights to protect, where to stay, how Noi Bai arrival changes the first day, or whether the capital should feed into Ninh Binh, Ha Long Bay, Cat Ba, mountains, or Central Vietnam, start with the <a href="/destinations/hanoi-travel-guide/">Hanoi Travel Guide</a>. Use this page after that bigger route role is clear.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- vg-hanoi-neighborhood-compare-best-things-support:v1 -->
<!-- wp:group {"className":"vg-route-support vg-hanoi-neighborhood-compare-support","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-route-support vg-hanoi-neighborhood-compare-support">
<!-- wp:heading -->
<h2 class="wp-block-heading">Compare the Hanoi sleep zone if walking time will change the attraction plan</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>If your attractions are already clear but the hotel area is still fuzzy, compare <a href="/compare/old-quarter-vs-french-quarter-vs-west-lake/">Old Quarter vs French Quarter vs West Lake</a>. The right base can protect the first food walk, museum day, pickup morning, and sleep better than adding one more sight.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-best-things-hanoi-priority-map:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to prioritize first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This is not a universal ranking. It is a route-value filter for international visitors deciding how much Hanoi deserves before the rest of northern Vietnam starts asking for time.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-things-hanoi-priority-map">
<thead>
<tr><th>Experience</th><th>Why it matters</th><th>Best fit</th><th>Watch out for</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Experience">Hoan Kiem Lake and the Old Quarter</td><td data-label="Why it matters">The quickest way to feel Hanoi's scale, street rhythm, cafe culture, and first-night energy.</td><td data-label="Best fit">Every first-time route with at least one central night.</td><td data-label="Watch out for">Trying to turn the first walk into a full sightseeing sprint.</td><td data-label="VietnamGuide verdict">Make this the orientation chapter, not an afterthought.</td></tr>
<tr><td data-label="Experience">Hanoi food walk</td><td data-label="Why it matters">Food is the most efficient cultural entry point when jet lag and traffic make formal sightseeing feel heavy.</td><td data-label="Best fit">Arrival evening, first full evening, solo travelers, couples, and nervous first-timers.</td><td data-label="Watch out for">Overbooking if the flight arrives late or the hotel check-in is uncertain.</td><td data-label="VietnamGuide verdict">High value when it lowers friction for the rest of the trip.</td></tr>
<tr><td data-label="Experience">Imperial Citadel of Thang Long</td><td data-label="Why it matters">UNESCO-listed capital history that gives Hanoi more depth than food and streets alone.</td><td data-label="Best fit">Culture-led travelers, heritage routes, and return visitors.</td><td data-label="Watch out for">Going without context and leaving it as a flat ruins stop.</td><td data-label="VietnamGuide verdict">Choose it when history is part of the reason you are in Hanoi.</td></tr>
<tr><td data-label="Experience">Temple of Literature</td><td data-label="Why it matters">A calmer cultural stop with readable courtyards, architecture, and education history.</td><td data-label="Best fit">First-timers who want one accessible heritage stop without a heavy museum block.</td><td data-label="Watch out for">Crowded midday visits and pairing it with too many nearby stops.</td><td data-label="VietnamGuide verdict">Often the cleanest single culture stop for a short Hanoi stay.</td></tr>
<tr><td data-label="Experience">Vietnamese Women's Museum</td><td data-label="Why it matters">Adds social history, family context, textiles, and war-era perspective beyond the usual capital monuments.</td><td data-label="Best fit">Rainy days, heat pivots, families with older children, and travelers who want story over spectacle.</td><td data-label="Watch out for">Treating it as filler rather than giving it enough attention.</td><td data-label="VietnamGuide verdict">One of Hanoi's strongest indoor choices when the day needs depth.</td></tr>
<tr><td data-label="Experience">Coffee, cafes, and lake pauses</td><td data-label="Why it matters">Hanoi rewards unhurried observation; the pauses make the city easier to read.</td><td data-label="Best fit">Premium, couples, solo travelers, families needing recovery, and anyone arriving long-haul.</td><td data-label="Watch out for">Confusing downtime with wasted time.</td><td data-label="VietnamGuide verdict">Protect at least one pause before a heavy transfer day.</td></tr>
<tr><td data-label="Experience">Train Street and viral stops</td><td data-label="Why it matters">They are visible in search and social feeds, so travelers need a clear filter.</td><td data-label="Best fit">Only when access is legal, managed, and not replacing a stronger Hanoi block.</td><td data-label="Watch out for">Unsafe access, unclear local rules, and treating a photo as a reason to bend the day.</td><td data-label="VietnamGuide verdict">Optional and easy to skip; do not build the Hanoi day around it.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-things-hanoi-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what each Hanoi chapter feels like</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The images are route evidence. They show why Hanoi works best as a layered first chapter rather than a rushed airport city.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-best-things-hanoi-photo-grid" aria-label="Hanoi guide photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Hoan Kiem Lake in central Hanoi, Vietnam" loading="lazy" decoding="async"><figcaption>Hoan Kiem is the natural orientation walk for first-time visitors staying central. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Alex 69200 vx / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$dong_xuan_image}" alt="Dong Xuan Market and Old Quarter street texture in Hanoi" loading="lazy" decoding="async"><figcaption>Old Quarter value comes from street rhythm, markets, food, and short walks, not from racing between landmarks. Image: <a href="{$dong_xuan_credit_url}" target="_blank" rel="license noopener">yeowatzup / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$temple_image}" alt="Main gate of the Temple of Literature in Hanoi, Vietnam" loading="lazy" decoding="async"><figcaption>The Temple of Literature is a clean cultural choice when Hanoi has only one serious heritage stop. Image: <a href="{$temple_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$thang_long_image}" alt="Central Sector of the Imperial Citadel of Thang Long in Hanoi" loading="lazy" decoding="async"><figcaption>Thang Long earns time when the trip wants capital history and UNESCO context. Image: <a href="{$thang_long_credit_url}" target="_blank" rel="license noopener">katiebordner / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$train_street_image}" alt="Train street in Hanoi" loading="lazy" decoding="async"><figcaption>Train Street is better treated as an optional visual stop than as the spine of a Hanoi day. Image: <a href="{$train_street_credit_url}" target="_blank" rel="license noopener">Kris Martyn / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-best-things-hanoi-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How many Hanoi days do you actually need?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The right Hanoi plan depends on the next move. A traveler leaving for Ninh Binh, Ha Long Bay, Sapa, Ha Giang, or Central Vietnam should protect energy as carefully as attractions.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-things-hanoi-route-fit">
<thead>
<tr><th>Hanoi time</th><th>Keep</th><th>Add if energy is good</th><th>Skip first</th><th>Why</th></tr>
</thead>
<tbody>
<tr><td data-label="Hanoi time">Arrival evening only</td><td data-label="Keep">Central hotel, nearby dinner, short Hoan Kiem or Old Quarter walk.</td><td data-label="Add if energy is good">One low-pressure cafe or dessert stop.</td><td data-label="Skip first">Paid tours, far transfers, and early-morning next-day commitments.</td><td data-label="Why">The first win is recovery and orientation.</td></tr>
<tr><td data-label="Hanoi time">One full day</td><td data-label="Keep">Hoan Kiem, Old Quarter, food walk, and one culture stop.</td><td data-label="Add if energy is good">Temple of Literature or Women's Museum depending on weather.</td><td data-label="Skip first">Multiple museums plus a viral photo detour.</td><td data-label="Why">One day should give confidence, not exhaustion.</td></tr>
<tr><td data-label="Hanoi time">Two full days</td><td data-label="Keep">Food, lake walks, one heritage stop, one museum, and one slow neighborhood/cafe block.</td><td data-label="Add if energy is good">Thang Long, a craft/market walk, or a guided history layer.</td><td data-label="Skip first">A rushed day trip that makes the city itself disappear.</td><td data-label="Why">Two days let Hanoi become a chapter rather than a staging city.</td></tr>
<tr><td data-label="Hanoi time">Three nights before the north</td><td data-label="Keep">Two city days plus a cleaner outbound transfer plan.</td><td data-label="Add if energy is good">A selective day trip only if it does not weaken Ninh Binh, bay, or mountain days.</td><td data-label="Skip first">Adding every nearby excursion because the hotel is already booked.</td><td data-label="Why">Extra Hanoi time is most valuable when it improves the whole northern route.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-things-hanoi-rain-heat-pivots:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Rain, heat, and arrival-energy pivots</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Hanoi planning fails when the day ignores weather and arrival fatigue. Keep a primary plan and a softer pivot so you do not waste paid time fighting the city.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-things-hanoi-pivots">
<thead>
<tr><th>If the day feels...</th><th>Better move</th><th>What to postpone</th><th>Why it works</th></tr>
</thead>
<tbody>
<tr><td data-label="If the day feels...">Hot and exposed</td><td data-label="Better move">Early lake walk, shaded cafe break, museum or Temple of Literature later.</td><td data-label="What to postpone">Long midday Old Quarter wandering.</td><td data-label="Why it works">You still get Hanoi texture without turning the day into heat management.</td></tr>
<tr><td data-label="If the day feels...">Rainy</td><td data-label="Better move">Women's Museum, coffee, food route with covered stops, or a slower hotel-neighborhood plan.</td><td data-label="What to postpone">Photo-led lake and street blocks.</td><td data-label="Why it works">Indoor story and food keep value when outdoor sightseeing weakens.</td></tr>
<tr><td data-label="If the day feels...">Jet-lagged</td><td data-label="Better move">One gentle walk, one good meal, and an early night.</td><td data-label="What to postpone">Thang Long, long museum blocks, and anything prepaid far from the hotel.</td><td data-label="Why it works">Northern routes often become better because the first night stays honest.</td></tr>
<tr><td data-label="If the day feels...">Overfull before a transfer</td><td data-label="Better move">Cut the weakest attraction and protect packing, pickup, and sleep.</td><td data-label="What to postpone">Viral stops and duplicate heritage visits.</td><td data-label="Why it works">The next route chapter is part of the Hanoi decision.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-things-hanoi-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first in Hanoi</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Skipping is what keeps Hanoi premium. A better first visit usually comes from fewer stops with more context, not a longer map of pins.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-things-hanoi-skip-logic">
<thead>
<tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you want...">Classic first Hanoi</td><td data-label="Protect this">Hoan Kiem, Old Quarter, food, one culture stop, and a rested next morning.</td><td data-label="Skip first">Train Street, duplicate markets, and far cross-city errands.</td><td data-label="Only add back when...">Access is clear and the core day still breathes.</td></tr>
<tr><td data-label="If you want...">Heritage depth</td><td data-label="Protect this">Thang Long or Temple of Literature with time to read and reflect.</td><td data-label="Skip first">A second heritage stop that would make both shallow.</td><td data-label="Only add back when...">You have two full city days or a guide adding context.</td></tr>
<tr><td data-label="If you want...">Food and street life</td><td data-label="Protect this">Evening food, market texture, and cafe downtime.</td><td data-label="Skip first">Museum stacking and early day-trip pickups.</td><td data-label="Only add back when...">The route has a second Hanoi day or a late departure.</td></tr>
<tr><td data-label="If you want...">Lower-stress family travel</td><td data-label="Protect this">Short walks, shaded breaks, one museum, and predictable meals.</td><td data-label="Skip first">Long unstructured Old Quarter wandering in heat or rain.</td><td data-label="Only add back when...">The group still has attention and the next transfer is light.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-things-hanoi-official-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Official checks before locking the Hanoi day</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use official and primary sources for public framing, then verify live opening rules, special closures, access restrictions, weather, and transport close to travel. This guide is designed to hold its judgment, not to replace same-week checks.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-best-things-hanoi-official-checks"} -->
<ul class="wp-block-list vg-check-list vg-best-things-hanoi-official-checks">
<li>Destination frame: start with <a href="https://vietnam.travel/places-to-go/northern-vietnam/ha-noi" target="_blank" rel="noopener">Vietnam.travel Ha Noi</a> and <a href="https://vietnam.travel/places-to-go/northern-vietnam" target="_blank" rel="noopener">Vietnam.travel Northern Vietnam</a> before deciding how much city time belongs in the north route.</li>
<li>Heritage context: use the <a href="https://whc.unesco.org/en/list/1328/" target="_blank" rel="noopener">UNESCO Thang Long listing</a> and the <a href="https://hoangthanhthanglong.vn/en/" target="_blank" rel="noopener">Imperial Citadel of Thang Long official site</a> when Thang Long is a serious part of the day.</li>
<li>Culture stops: check the <a href="https://vanmieu.gov.vn/en/" target="_blank" rel="noopener">Temple of Literature official site</a> and the <a href="https://baotangphunu.org.vn/en" target="_blank" rel="noopener">Vietnamese Women's Museum official site</a> before relying on opening details from old articles.</li>
<li>Weather and movement: compare <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> with the <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> guide before putting a full Hanoi day before a long transfer.</li>
<li>Planning order: read <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, and <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a> before adding more northern stops.</li>
</ul>
<!-- /wp:list -->

<!-- vg-best-things-hanoi-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hanoi planning FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-best-things-hanoi-faq">
<details><summary>Is one day in Hanoi enough?</summary><p>One full day is enough for a useful first taste if you stay central and keep the plan selective: Hoan Kiem, Old Quarter, food, and one culture stop. It is not enough if you also expect a day trip, several museums, and a rested departure.</p></details>
<details><summary>Should I do Hanoi before or after Ha Long Bay or Ninh Binh?</summary><p>For most first-time visitors, Hanoi works best before the first major northern landscape stop because it gives orientation and recovery. Keep the day before an early bay or countryside transfer lighter than a normal sightseeing day.</p></details>
<details><summary>Is Train Street a must-do?</summary><p>No. Treat it as optional and access-dependent. If local rules, safety, or logistics are unclear, skip it without guilt and use the time for food, a lake walk, or a museum that adds more durable value.</p></details>
<details><summary>Which single cultural stop should I choose?</summary><p>Choose the Temple of Literature if you want the cleanest first cultural stop. Choose Thang Long if UNESCO and capital history matter. Choose the Vietnamese Women's Museum if weather pushes the day indoors or you want social history.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-best-things-hanoi-hero:v1',
    'concierge verdict' => 'vg-best-things-hanoi-concierge-verdict',
    'Hanoi Travel Guide support' => 'vg-hanoi-travel-best-things-support:v1',
    'Hanoi neighborhood comparison support' => 'vg-hanoi-neighborhood-compare-best-things-support:v1',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'priority map' => 'vg-best-things-hanoi-priority-map:v1',
    'photo grid' => 'vg-best-things-hanoi-photo-grid:v1',
    'route fit' => 'vg-best-things-hanoi-route-fit:v1',
    'rain and heat pivots' => 'vg-best-things-hanoi-rain-heat-pivots:v1',
    'skip logic' => 'vg-best-things-hanoi-skip-logic:v1',
    'official checks' => 'vg-best-things-hanoi-official-checks:v1',
    'FAQ' => 'vg-best-things-hanoi-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('Best Things to Do in Hanoi guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Best Things to Do in Hanoi',
    'post_name'      => 'best-things-to-do-in-hanoi',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led guide to the best things to do in Hanoi, with route-fit judgment, photo proof, skip logic, rain and heat pivots, official checks, and first-trip planning value.',
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
    vg_ops_fail('Could not publish Best Things to Do in Hanoi guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Best Things to Do in Hanoi guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Best Things to Do in Hanoi: What Is Actually Worth Your Time');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Hanoi guide for international visitors: what to prioritize, what to skip, route-fit timing, food, heritage, museums, weather pivots, and official checks.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'best things to do in Hanoi');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide which Hanoi experiences deserve limited first-trip time before adding day trips, extra heritage stops, or viral photo detours.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 17, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Best Things to Do in Hanoi guide with a route-value verdict, first-timer priority map, photo proof, Hanoi day-count matrix, rain and heat pivots, skip logic, official source checks, FAQ, source trail, and update log.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Ha Noi destination page - https://vietnam.travel/places-to-go/northern-vietnam/ha-noi - checked July 17, 2026\nVietnam.travel - Northern Vietnam destinations - https://vietnam.travel/places-to-go/northern-vietnam - checked July 17, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 17, 2026\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked July 17, 2026\nUNESCO World Heritage Centre - Central Sector of the Imperial Citadel of Thang Long - Ha Noi - https://whc.unesco.org/en/list/1328/ - checked July 17, 2026\nImperial Citadel of Thang Long official site - https://hoangthanhthanglong.vn/en/ - checked July 17, 2026\nTemple of Literature official site - https://vanmieu.gov.vn/en/ - checked July 17, 2026\nVietnamese Women's Museum official site - https://baotangphunu.org.vn/en - checked July 17, 2026\nWikimedia Commons image record - Hanoi Hoan Kiem Lake - https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg - license checked July 17, 2026\nWikimedia Commons image record - Cho Dong Xuan, Old Quarter, Hanoi - https://commons.wikimedia.org/wiki/File:Cho_Dong_Xuan,_Old_Quarter,_Hanoi,_Vietnam_(5245806573).jpg - license checked July 17, 2026\nWikimedia Commons image record - Main gate of the Temple of Literature, Hanoi - https://commons.wikimedia.org/wiki/File:Main_gate_of_the_Temple_of_Literature,_Hanoi,_Vietnam,_20240123_0929_3068.jpg - license checked July 17, 2026\nWikimedia Commons image record - Central Sector of the Imperial Citadel of Thang Long - Hanoi - https://commons.wikimedia.org/wiki/File:Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg - license checked July 17, 2026\nWikimedia Commons image record - Train street in Hanoi - https://commons.wikimedia.org/wiki/File:Train_street_in_Hanoi.jpg - license checked July 17, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Hanoi is strongest when it becomes the first route chapter: orientation, food confidence, capital history, one museum or heritage stop, and enough rest before the north starts moving.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Hanoi guide built as a route-value filter rather than a generic attractions list.\nConcierge verdict that names the strongest first-time sequence and the first things to skip.\nPriority map that explains what each Hanoi experience changes in the route.\nPhoto-led proof using licensed images with visible credits for Hoan Kiem, Old Quarter, Temple of Literature, Thang Long, and Train Street.\nDay-count matrix that separates arrival evening, one full day, two full days, and three-night Hanoi routes.\nRain, heat, and arrival-energy pivots that protect practical traveler value.\nSkip logic for viral stops, duplicate heritage visits, and overfull pre-transfer days.\nOfficial source checks tied to Vietnam.travel, UNESCO, Thang Long, Temple of Literature, Vietnamese Women's Museum, weather, and transport.\nInternal links to the Travel Guide, Best Places, regional comparison, Best Time, 10 Days, and Transport guide.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Hanoi Travel Guide | /destinations/hanoi-travel-guide/ | Decide the capital's route role, night count, stay area, Noi Bai arrival logic, and northern launch plan before choosing attractions.\nWhere to Stay in Hanoi | /destinations/where-to-stay-in-hanoi/ | Choose the base that makes the first night, first food walk, and first transfer realistic before adding attractions.\nOld Quarter vs French Quarter vs West Lake | /compare/old-quarter-vs-french-quarter-vs-west-lake/ | Compare Hanoi's main sleep zones when walking energy, museum access, first-night food, pickup clarity, or calmer sleep changes the attraction plan.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose whether the spare day should leave Hanoi for Ninh Binh, the bay, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or stay in the city.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nVietnam Travel Guide | /plan/vietnam-travel-guide/ | Start here if you still need the full first-trip planning order.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Decide how Hanoi fits the broader destination shortlist.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Use this if Thang Long changes your heritage priorities.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Compare whether the north should lead the route.\n7 Days in Vietnam | /itineraries/7-days-in-vietnam/ | Decide whether a one-week route should stay north-focused before adding a heavy Hanoi attraction list.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether Hanoi has enough time before Ninh Binh, the bay, or Central Vietnam.\n21 Days in Vietnam | /itineraries/21-days-in-vietnam/ | Use three weeks to decide whether Hanoi deserves deeper city time or should remain a northern launch base.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Check transfers before adding a day trip or early pickup.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', "Hero and Hoan Kiem image: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0. Body images: Dong Xuan Market by yeowatzup, CC BY 2.0; Temple of Literature by Jakub Halun, CC BY 4.0; Central Sector of the Imperial Citadel of Thang Long by katiebordner, CC BY 2.0; Train street in Hanoi by Kris Martyn, CC BY-SA 4.0.");
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
    vg_ops_fail('Best Things to Do in Hanoi guide was updated but is not published.');
}

vg_ops_refresh_destinations_hub();
vg_ops_refresh_best_places_related_routes();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Best Things to Do in Hanoi guide: {$page_id} {$updated_permalink}");

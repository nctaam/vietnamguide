<?php
/**
 * Publish the Best Things to Do in Hoi An guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-best-things-hoi-an-guide.php --allow-root
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
    $value = getenv('VG_FORCE_HOI_AN_GUIDE_REPUBLISH');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the Best Things to Do in Hoi An guide.');
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

    if (! vg_ops_published_page_exists('destinations/best-things-to-do-in-hoi-an')) {
        vg_ops_log('Skipped Destinations hub refresh: Hoi An guide is not published.');
        return;
    }

    $marker = '<!-- vg-best-things-hoi-an-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Protect the slower central chapter</h3><p>The <a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a> guide helps travelers keep Ancient Town, food, river, cafe, My Son, and beach time in the right order instead of turning Hoi An into a one-night checklist.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Destinations hub Hoi An note', $block);

    $marked_block = $marker . "\n" . $block;

    if (str_contains($hub->post_content, $marked_block)) {
        vg_ops_log('Skipped Destinations hub refresh: current Hoi An note already present.');
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
            vg_ops_fail('Could not confidently refresh the Destinations hub Hoi An note.');
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
        vg_ops_fail('Could not refresh Destinations hub Hoi An note: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Destinations hub Hoi An note: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Destinations hub Hoi An note: {$hub->ID}");
}

function vg_ops_refresh_best_places_related_routes(): void
{
    $page = get_page_by_path('destinations/best-places-to-visit-vietnam', OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ops_log('Skipped Best Places related routes refresh: guide was not found or is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('destinations/best-things-to-do-in-hoi-an')) {
        vg_ops_log('Skipped Best Places related routes refresh: Hoi An guide is not published.');
        return;
    }

    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);
    $needle = '/destinations/best-things-to-do-in-hoi-an/';

    if (str_contains($current, $needle)) {
        vg_ops_log('Skipped Best Places related routes refresh: Hoi An link already present.');
        return;
    }

    $updated = rtrim($current);

    if ($updated !== '') {
        $updated .= "\n";
    }

    $updated .= "Best Things to Do in Hoi An | /destinations/best-things-to-do-in-hoi-an/ | Decide how much slow central Vietnam time Hoi An deserves before adding My Son, beach days, or another transfer.";

    update_post_meta($page->ID, 'vg_eeat_related_routes', $updated);
    vg_ops_log("Refreshed Best Places related routes with Hoi An: {$page->ID}");
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/best-things-to-do-in-hoi-an', OBJECT, 'page');
$allow_republish = vg_ops_force_republish_enabled();

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('Best Things to Do in Hoi An guide is not a draft. Set VG_FORCE_HOI_AN_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    $permalink = get_permalink($page);
    vg_ops_log("Preflight Best Things to Do in Hoi An guide: {$page->ID} {$page->post_status} {$permalink}");
} else {
    vg_ops_log('Preflight Best Things to Do in Hoi An guide: no existing page found; creating a child page under /destinations/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg');
$japanese_bridge_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/c/cb/Hoi_An%2C_Vietnam%2C_Japanese_Covered_Bridge.jpg');
$japanese_bridge_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hoi_An,_Vietnam,_Japanese_Covered_Bridge.jpg');
$my_son_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/7/7d/My_Son_Sanctuary_Vietnam_06.jpg/1920px-My_Son_Sanctuary_Vietnam_06.jpg');
$my_son_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:My_Son_Sanctuary_Vietnam_06.jpg');
$an_bang_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b0/2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg/1920px-2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg');
$an_bang_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg');

$content = <<<HTML
<!-- vg-best-things-hoi-an-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hoi An Ancient Town street scene in central Vietnam, used for a Hoi An things to do guide" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 18, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Best Things to Do in Hoi An</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Hoi An is the central Vietnam chapter that works best when the trip slows down. The strongest plan protects Ancient Town after dark, one heritage morning, one food, river, or cafe block, and only adds My Son or An Bang Beach when the route has enough slack.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: treat Hoi An as a rhythm decision, not a photo stop. If the route gives it only one tired night, the smartest move is usually to cut an add-on before cutting the town's best hours.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-best-things-hoi-an-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-best-things-hoi-an-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-best-things-hoi-an-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most international visitors, the best Hoi An sequence is Ancient Town after dark, a calm heritage morning, one food, river, market, or cafe block, and a protected pause before the next transfer.</strong> Add My Son, An Bang Beach, countryside cycling, or a cooking class only when the route still has breathing room after the core town hours are safe.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-best-things-hoi-an-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-best-things-hoi-an-shortlist">
<li><strong>Best first evening:</strong> Ancient Town after dark, kept close to the hotel and light enough to enjoy lanterns, dinner, and the river without chasing every lane.</li>
<li><strong>Best heritage morning:</strong> Japanese Covered Bridge, assembly halls, old houses, and a guided or self-led walk before heat and groups flatten the town.</li>
<li><strong>Best add-on:</strong> My Son when Champa history matters and the day can start early; An Bang when the route needs recovery more than another monument.</li>
<li><strong>Best skip rule:</strong> do not add My Son, beach time, a basket boat, a cooking class, and a transfer day to the same short Hoi An stay.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-best-things-hoi-an-priority-map:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to prioritize first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This is a route-first priority map, not a generic attractions ranking. Hoi An's value comes from sequence: protect the town's best hours, then decide whether the route can afford a heritage, beach, food, or countryside extension.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-things-hoi-an-priority-map">
<thead>
<tr><th>Experience</th><th>Why it matters</th><th>Best fit</th><th>Watch out for</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Experience">Ancient Town after dark</td><td data-label="Why it matters">The town's river, lanterns, low light, and food pace are the clearest reason Hoi An should not be rushed.</td><td data-label="Best fit">Every first-time Hoi An stay with at least one central evening.</td><td data-label="Watch out for">Turning the evening into a photo circuit instead of dinner, walking, and observation.</td><td data-label="VietnamGuide verdict">Protect this before adding anything outside town.</td></tr>
<tr><td data-label="Experience">Japanese Covered Bridge and heritage walk</td><td data-label="Why it matters">A morning route gives the UNESCO town texture and makes the old trading-port story legible.</td><td data-label="Best fit">Culture-led travelers, couples, families with older children, and premium routes that value context.</td><td data-label="Watch out for">Going too late, when heat and crowds make the heritage feel thin.</td><td data-label="VietnamGuide verdict">The cleanest core sightseeing block.</td></tr>
<tr><td data-label="Experience">Food, market, and cafe block</td><td data-label="Why it matters">Hoi An is strongest when food and downtime are treated as substance, not filler between sights.</td><td data-label="Best fit">First-timers, solo travelers, couples, families, and anyone needing a lower-stress central day.</td><td data-label="Watch out for">Stacking a class, market tour, river cruise, and dinner until none of them breathe.</td><td data-label="VietnamGuide verdict">Make this the second protected block after Ancient Town.</td></tr>
<tr><td data-label="Experience">My Son Sanctuary</td><td data-label="Why it matters">Adds Champa history and gives central Vietnam a more serious heritage arc beyond Hoi An's trading-port fabric.</td><td data-label="Best fit">Two- or three-night Hoi An stays, heritage routes, and travelers already interested in UNESCO context.</td><td data-label="Watch out for">Adding it to a one-night Hoi An stop or pairing it with an overfull afternoon.</td><td data-label="VietnamGuide verdict">High value with slack; easy to skip when the town itself is under-protected.</td></tr>
<tr><td data-label="Experience">An Bang Beach</td><td data-label="Why it matters">A simple recovery valve when heat, transfers, or city fatigue make another attraction less valuable.</td><td data-label="Best fit">Families, couples, summer heat, premium decompression, and longer central stays.</td><td data-label="Watch out for">Treating beach time as guaranteed value in poor weather or rough-sea windows.</td><td data-label="VietnamGuide verdict">Use it as recovery, not as proof that Hoi An needs another day.</td></tr>
<tr><td data-label="Experience">Countryside cycling and river time</td><td data-label="Why it matters">The rice fields, river edges, and quieter lanes make Hoi An feel like a central base rather than a compact old town.</td><td data-label="Best fit">Slow routes, food travelers, active families, and travelers with at least two mornings.</td><td data-label="Watch out for">Riding in heat, rain, or after the route is already tired.</td><td data-label="VietnamGuide verdict">Add when it creates calm, not when it compresses heritage.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-things-hoi-an-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what each Hoi An chapter feels like</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The images are evidence for route decisions. They show why Hoi An is stronger as a slow central chapter than as a one-night checklist between Hue, Da Nang, and the next flight.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-best-things-hoi-an-photo-grid" aria-label="Hoi An guide photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Hoi An Ancient Town street scene in central Vietnam" loading="lazy" decoding="async"><figcaption>Ancient Town earns the first protected evening and morning. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$japanese_bridge_image}" alt="Japanese Covered Bridge in Hoi An, Vietnam" loading="lazy" decoding="async"><figcaption>The Japanese Covered Bridge works best as part of a calm heritage walk, not a single photo stop. Image: <a href="{$japanese_bridge_credit_url}" target="_blank" rel="license noopener">rapidacid / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$my_son_image}" alt="Brick temple ruins at My Son Sanctuary in central Vietnam" loading="lazy" decoding="async"><figcaption>My Son adds Champa context when the Hoi An stay has enough protected slack. Image: <a href="{$my_son_credit_url}" target="_blank" rel="license noopener">Philip Nalangan / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$an_bang_image}" alt="An Bang Beach in Hoi An, Vietnam" loading="lazy" decoding="async"><figcaption>An Bang is a recovery choice when the route needs air more than another stop. Image: <a href="{$an_bang_credit_url}" target="_blank" rel="license noopener">Alexkom000 / CC BY 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-best-things-hoi-an-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How many Hoi An nights do you actually need?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Hoi An is less about how many attractions fit and more about whether the route protects the right light, meal, weather window, and transfer energy. Use nights as the unit, not checkmarks.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-things-hoi-an-route-fit">
<thead>
<tr><th>Hoi An time</th><th>Keep</th><th>Add only if the route breathes</th><th>Skip first</th><th>Why</th></tr>
</thead>
<tbody>
<tr><td data-label="Hoi An time">Arrival evening only</td><td data-label="Keep">Central hotel, easy dinner, short Ancient Town and river walk.</td><td data-label="Add only if the route breathes">One cafe or dessert stop close to the hotel.</td><td data-label="Skip first">My Son, beach transfers, cooking classes, and anything with an early pickup.</td><td data-label="Why">The win is orientation and one honest Hoi An evening.</td></tr>
<tr><td data-label="Hoi An time">One full day and two nights</td><td data-label="Keep">Ancient Town evening, heritage morning, food or cafe block, and a quieter second evening.</td><td data-label="Add only if the route breathes">An Bang Beach or a short countryside ride if weather supports it.</td><td data-label="Skip first">My Son plus a full beach afternoon plus a class.</td><td data-label="Why">One full day can be excellent if it stays selective.</td></tr>
<tr><td data-label="Hoi An time">Two full days and three nights</td><td data-label="Keep">Town, food, cafe downtime, one beach or countryside block, and one serious add-on.</td><td data-label="Add only if the route breathes">My Son, a guided food market block, or a low-pressure river evening.</td><td data-label="Skip first">A duplicate theme park, shopping sprint, or rushed Da Nang/Hue add-on.</td><td data-label="Why">This is the sweet spot for most premium first trips.</td></tr>
<tr><td data-label="Hoi An time">Four nights or central Vietnam base</td><td data-label="Keep">Slow town time, My Son or countryside, beach recovery, and clean transfer buffers.</td><td data-label="Add only if the route breathes">A spa day, tailoring time, Cham Islands, or a Da Nang meal block.</td><td data-label="Skip first">Any add-on that makes Hoi An feel busy again.</td><td data-label="Why">Extra time is valuable only if it stays slow.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-things-hoi-an-season-pivots:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Season and weather pivots</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Central Vietnam weather changes the value of Hoi An add-ons. Keep the Ancient Town core, then pivot My Son, beach, cycling, and cafe time around heat, rain, storm risk, and the next transfer.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-things-hoi-an-season-pivots">
<thead>
<tr><th>Travel window</th><th>Best Hoi An bias</th><th>Move earlier</th><th>Move later or cut</th><th>Route note</th></tr>
</thead>
<tbody>
<tr><td data-label="Travel window">March to May</td><td data-label="Best Hoi An bias">Best all-round window for town, food, countryside, and My Son.</td><td data-label="Move earlier">Heritage walk and My Son if the day is warming fast.</td><td data-label="Move later or cut">Overlong shopping and duplicate photo stops.</td><td data-label="Route note">Use the good conditions to protect quality, not to overload the day.</td></tr>
<tr><td data-label="Travel window">June to August</td><td data-label="Best Hoi An bias">Early heritage, shaded cafes, beach recovery, and lighter afternoons.</td><td data-label="Move earlier">Ancient Town walk, cycling, and My Son.</td><td data-label="Move later or cut">Midday old-town wandering and exposed countryside time.</td><td data-label="Route note">Heat makes downtime a serious planning tool.</td></tr>
<tr><td data-label="Travel window">September to November</td><td data-label="Best Hoi An bias">Flexible town blocks, indoor food, cafes, hotel quality, and live weather checks.</td><td data-label="Move earlier">Any outdoor plan with a clear morning window.</td><td data-label="Move later or cut">Beach assumptions, distant day trips, and non-refundable extras.</td><td data-label="Route note">Storms and rain can make slack more valuable than another booking.</td></tr>
<tr><td data-label="Travel window">December to February</td><td data-label="Best Hoi An bias">Cooler walks, food, cafes, tailoring, and heritage with softer beach expectations.</td><td data-label="Move earlier">Town and market time on clearer days.</td><td data-label="Move later or cut">Beach-first plans if conditions do not support them.</td><td data-label="Route note">Let Hoi An be atmospheric rather than forcing a summer script.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-things-hoi-an-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first in Hoi An</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Skipping is what keeps Hoi An premium. The town gets weaker when every optional add-on is treated as compulsory; it gets stronger when the best evening, morning, and meal are protected.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-things-hoi-an-skip-logic">
<thead>
<tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you want...">Classic first Hoi An</td><td data-label="Protect this">Ancient Town after dark, heritage morning, one food block, and one unhurried pause.</td><td data-label="Skip first">My Son, beach, basket boat, tailoring, and cooking class all in the same short stay.</td><td data-label="Only add back when...">The core evening and morning still have space.</td></tr>
<tr><td data-label="If you want...">Heritage depth</td><td data-label="Protect this">A real town walk plus My Son with enough context and recovery time.</td><td data-label="Skip first">A beach afternoon that makes both heritage blocks shallow.</td><td data-label="Only add back when...">You have two full days or a third night.</td></tr>
<tr><td data-label="If you want...">Food and cafes</td><td data-label="Protect this">Market, local dishes, coffee, river time, and a slower second evening.</td><td data-label="Skip first">Early pickup tours that steal the best food day rhythm.</td><td data-label="Only add back when...">The food block is not just squeezed around logistics.</td></tr>
<tr><td data-label="If you want...">Lower-stress family travel</td><td data-label="Protect this">Central base, short walks, shaded breaks, beach or pool recovery, and predictable meals.</td><td data-label="Skip first">Long exposed cycling, duplicate tours, and tight same-day transfers.</td><td data-label="Only add back when...">Weather, attention, and transport all support it.</td></tr>
<tr><td data-label="If you want...">Premium central Vietnam</td><td data-label="Protect this">Hotel quality, private timing, one excellent heritage block, and generous evenings.</td><td data-label="Skip first">Checklist touring that turns premium time into admin.</td><td data-label="Only add back when...">The add-on makes the trip calmer or deeper.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-things-hoi-an-official-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Official checks before locking the Hoi An stay</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use official and primary sources for framing, then verify live opening rules, restoration access, weather, storm alerts, beach conditions, and transport close to travel. This guide is designed to hold its editorial judgment, not to replace same-week checks.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-best-things-hoi-an-official-checks"} -->
<ul class="wp-block-list vg-check-list vg-best-things-hoi-an-official-checks">
<li>Destination frame: start with <a href="https://vietnam.travel/places-to-go/central-vietnam/hoi-an" target="_blank" rel="noopener">Vietnam.travel Hoi An</a> and <a href="https://vietnam.travel/places-to-go/central-vietnam" target="_blank" rel="noopener">Vietnam.travel Central Vietnam</a> before deciding whether Hoi An is the central chapter or just a transfer stop.</li>
<li>Heritage context: use the <a href="https://whc.unesco.org/en/list/948/" target="_blank" rel="noopener">UNESCO Hoi An Ancient Town listing</a> before reducing the town to lantern photos, and the <a href="https://whc.unesco.org/en/list/949/" target="_blank" rel="noopener">UNESCO My Son Sanctuary listing</a> before adding the half-day ruins trip.</li>
<li>Season and weather: compare <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> with <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a> before paying for beach, countryside, or My Son extras.</li>
<li>Route logistics: use <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before adding early pickups, private transfers, or extra central Vietnam nights.</li>
<li>Planning order: read <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a> before stretching Hoi An beyond the route's real slack.</li>
</ul>
<!-- /wp:list -->

<!-- vg-best-things-hoi-an-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hoi An planning FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-best-things-hoi-an-faq">
<details><summary>How many nights do I need in Hoi An?</summary><p>Two nights can work if you keep the plan selective: one Ancient Town evening, one heritage morning, and one food or cafe block. Three nights is the cleaner default for most premium first trips because it lets you add My Son, beach time, countryside, or recovery without hollowing out the town itself.</p></details>
<details><summary>Is My Son worth it from Hoi An?</summary><p>Yes, when Champa history and UNESCO context matter and the route has a real half-day to spare. Skip it on a one-night Hoi An stop or when heat, rain, or an early transfer would turn the visit into a rushed add-on.</p></details>
<details><summary>Should I stay in Hoi An or Da Nang?</summary><p>Stay in Hoi An when Ancient Town evenings, food, cafes, and slower walking are the point. Stay closer to Da Nang when flights, resort beach time, golf, or a very early airport move matter more. Do not split bases unless the route gains more than it loses.</p></details>
<details><summary>Is An Bang Beach a must-do?</summary><p>No. An Bang is valuable when the trip needs recovery, summer heat relief, or a gentler family block. It is easy to skip when weather is poor, the stay is short, or Ancient Town and My Son already fill the best hours.</p></details>
<details><summary>What should I book ahead in Hoi An?</summary><p>Book the central hotel and any must-have private guide, cooking class, or transfer once the route is fixed. Keep weather-sensitive beach, cycling, and some food/cafe time flexible so Hoi An can respond to heat, rain, and arrival energy.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-best-things-hoi-an-hero:v1',
    'concierge verdict' => 'vg-best-things-hoi-an-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'priority map' => 'vg-best-things-hoi-an-priority-map:v1',
    'photo grid' => 'vg-best-things-hoi-an-photo-grid:v1',
    'route fit' => 'vg-best-things-hoi-an-route-fit:v1',
    'season pivots' => 'vg-best-things-hoi-an-season-pivots:v1',
    'skip logic' => 'vg-best-things-hoi-an-skip-logic:v1',
    'official checks' => 'vg-best-things-hoi-an-official-checks:v1',
    'FAQ' => 'vg-best-things-hoi-an-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('Best Things to Do in Hoi An guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Best Things to Do in Hoi An',
    'post_name'      => 'best-things-to-do-in-hoi-an',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led guide to the best things to do in Hoi An, with route-first judgment, Ancient Town priorities, My Son and beach trade-offs, season pivots, skip logic, official checks, and licensed photo proof.',
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
    vg_ops_fail('Could not publish Best Things to Do in Hoi An guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Best Things to Do in Hoi An guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Best Things to Do in Hoi An: What Is Actually Worth Your Time');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Hoi An guide for international visitors: Ancient Town, food, My Son, beach time, route fit, season pivots, skip logic, official checks, and photo proof.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'best things to do in Hoi An');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide how to protect Hoi An as a slower central Vietnam chapter before adding My Son, beach time, countryside, classes, or another transfer.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 18, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Best Things to Do in Hoi An guide with an Evidence-led Concierge verdict, Ancient Town priority map, licensed photo proof, route-fit table, season and weather pivots, skip logic, official checks, FAQ, source trail, update log, and related-route refresh helpers.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked July 18, 2026\nVietnam.travel - Hoi An destination page - https://vietnam.travel/places-to-go/central-vietnam/hoi-an - checked July 18, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 18, 2026\nUNESCO World Heritage Centre - Hoi An Ancient Town - https://whc.unesco.org/en/list/948/ - checked July 18, 2026\nUNESCO World Heritage Centre - My Son Sanctuary - https://whc.unesco.org/en/list/949/ - checked July 18, 2026\nWikimedia Commons image record - Hoi An Ancient Town - https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg - license checked July 18, 2026\nWikimedia Commons image record - Hoi An, Vietnam, Japanese Covered Bridge - https://commons.wikimedia.org/wiki/File:Hoi_An,_Vietnam,_Japanese_Covered_Bridge.jpg - license checked July 18, 2026\nWikimedia Commons image record - My Son Sanctuary Vietnam 06 - https://commons.wikimedia.org/wiki/File:My_Son_Sanctuary_Vietnam_06.jpg - license checked July 18, 2026\nWikimedia Commons image record - 2024-11-23 An Bang Beach in Hoi An in November - https://commons.wikimedia.org/wiki/File:2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg - license checked July 18, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Hoi An is strongest when the route protects the town after dark, one calm heritage morning, one food or cafe block, and only then decides whether My Son, beach time, or countryside improves the trip.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Hoi An guide built as an English-first, evergreen Evidence-led Concierge route filter rather than a generic attractions list.\nConcierge verdict that protects Ancient Town after dark, a heritage morning, food or cafe time, and transfer slack before optional add-ons.\nPriority map that explains what each Hoi An experience changes in the route.\nPhoto-led proof using licensed images with visible credits for Ancient Town, Japanese Covered Bridge, My Son Sanctuary, and An Bang Beach.\nRoute-fit table that separates arrival evening, two-night, three-night, and longer central Vietnam stays.\nSeason and weather pivots for March-May, June-August, September-November, and December-February using official Vietnam weather framing.\nSkip logic that helps travelers remove weak add-ons before Hoi An turns into admin.\nOfficial source checks tied to Vietnam.travel, UNESCO Hoi An, UNESCO My Son, weather, transport, cost, and route planning.\nInternal links to the Travel Guide, Best Places, regional comparison, Best Time, 10 Days, 14 Days, UNESCO Heritage, Transport, and Cost guides.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start here if you still need the full first-trip planning order.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Decide how Hoi An fits the broader destination shortlist.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether central Vietnam should lead the route.\nDa Nang vs Hoi An | /compare/da-nang-vs-hoi-an/ | Decide whether Hoi An should be the base or the add-on before you commit to the central chapter.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check whether Hoi An weather supports beach, cycling, or My Son extras.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a short route has enough time to protect Hoi An properly.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use the longer route when Hoi An needs My Son, beach, and recovery time.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Use this if Hoi An and My Son change the central heritage priority.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Check transfers before adding early pickups or a second central base.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price private transfers, hotels, guides, classes, and extra nights before booking.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and Ancient Town image: Hoi An, Ancient Town, 2020-01 CN-11.jpg by Steffen Schmitz, CC BY-SA 4.0. Body images: Japanese Covered Bridge by rapidacid, CC BY 2.0; My Son Sanctuary Vietnam 06.jpg by Philip Nalangan, CC BY 4.0; An Bang Beach in Hoi An in November by Alexkom000, CC BY 4.0.');
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
    vg_ops_fail('Best Things to Do in Hoi An guide was updated but is not published.');
}

vg_ops_refresh_destinations_hub();
vg_ops_refresh_best_places_related_routes();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Best Things to Do in Hoi An guide: {$page_id} {$updated_permalink}");

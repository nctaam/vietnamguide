<?php
/**
 * Publish the Best Things to Do in Hue guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-best-things-hue-guide.php --allow-root
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
    $value = getenv('VG_FORCE_HUE_GUIDE_REPUBLISH');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the Best Things to Do in Hue guide.');
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

    if (! vg_ops_published_page_exists('destinations/best-things-to-do-in-hue')) {
        vg_ops_log('Skipped Destinations hub refresh: Hue guide is not published.');
        return;
    }

    $marker = '<!-- vg-best-things-hue-destinations-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Give central Vietnam a serious imperial chapter</h3><p>The <a href="/destinations/best-things-to-do-in-hue/">Best Things to Do in Hue</a> guide helps travelers decide when Hue deserves its own protected day instead of becoming a drive-through stop between Hoi An, Da Nang, and the next flight.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Destinations hub Hue note', $block);

    $marked_block = $marker . "\n" . $block;

    if (str_contains($hub->post_content, $marked_block)) {
        vg_ops_log('Skipped Destinations hub refresh: current Hue note already present.');
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
            vg_ops_fail('Could not confidently refresh the Destinations hub Hue note.');
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
        vg_ops_fail('Could not refresh Destinations hub Hue note: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Destinations hub Hue note: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Destinations hub Hue note: {$hub->ID}");
}

function vg_ops_append_related_route(string $page_path, string $label, string $route_line): void
{
    $page = get_page_by_path($page_path, OBJECT, 'page');

    if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
        vg_ops_log("Skipped {$label} related routes refresh: page was not found or is not published.");
        return;
    }

    if (! vg_ops_published_page_exists('destinations/best-things-to-do-in-hue')) {
        vg_ops_log("Skipped {$label} related routes refresh: Hue guide is not published.");
        return;
    }

    $current = (string) get_post_meta($page->ID, 'vg_eeat_related_routes', true);
    $needle = '/destinations/best-things-to-do-in-hue/';

    if (str_contains($current, $needle)) {
        vg_ops_log("Skipped {$label} related routes refresh: Hue link already present.");
        return;
    }

    $updated = rtrim($current);

    if ($updated !== '') {
        $updated .= "\n";
    }

    $updated .= $route_line;

    update_post_meta($page->ID, 'vg_eeat_related_routes', $updated);
    vg_ops_log("Refreshed {$label} related routes with Hue: {$page->ID}");
}

$parent = get_page_by_path('destinations', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: destinations');
}

$page = get_page_by_path('destinations/best-things-to-do-in-hue', OBJECT, 'page');
$allow_republish = vg_ops_force_republish_enabled();

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('Best Things to Do in Hue guide is not a draft. Set VG_FORCE_HUE_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    $permalink = get_permalink($page);
    vg_ops_log("Preflight Best Things to Do in Hue guide: {$page->ID} {$page->post_status} {$permalink}");
} else {
    vg_ops_log('Preflight Best Things to Do in Hue guide: no existing page found; creating a child page under /destinations/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg/1920px-Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$thien_mu_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/8/88/Hue_Vietnam_Thien-Mu-Temple-and-Pagoda-03.jpg');
$thien_mu_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Thien-Mu-Temple-and-Pagoda-03.jpg');
$minh_mang_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Hue_Vietnam_Tomb-of-Emperor-Minh-Mang-01.jpg/1920px-Hue_Vietnam_Tomb-of-Emperor-Minh-Mang-01.jpg');
$minh_mang_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Tomb-of-Emperor-Minh-Mang-01.jpg');
$perfume_river_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/Hue_Vietnam_Perfume-River-01.jpg/1920px-Hue_Vietnam_Perfume-River-01.jpg');
$perfume_river_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Perfume-River-01.jpg');

$content = <<<HTML
<!-- vg-best-things-hue-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":56,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Historic architecture inside the Hue Citadel in central Vietnam, used for a Hue things to do guide" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed destination guide - Updated July 18, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Best Things to Do in Hue</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Hue is central Vietnam's serious imperial chapter: Citadel, tombs, pagodas, river, food, and quiet historical weight. The best Hue plan protects one full heritage day, chooses tombs with intention, and uses the Hue-to-Hoi An handoff only when it improves the route.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: Hue is not a checklist city. It rewards travelers who slow down enough for context, shade, food, and the river between monuments.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">CEphoto, Uwe Aranas / CC BY-SA 3.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-best-things-hue-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-best-things-hue-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-best-things-hue-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most international visitors, Hue earns its place when the route can protect the Citadel, one royal tomb, Thien Mu Pagoda, a Perfume River or slower food block, and a clean transfer onward.</strong> If the schedule only gives Hue a rushed lunch stop between Da Nang and Hoi An, the better move is usually to cut it or add a night.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-best-things-hue-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-best-things-hue-shortlist">
<li><strong>Best first morning:</strong> the Citadel and Imperial City, ideally early, with enough context to understand why Hue matters beyond the walls.</li>
<li><strong>Best tomb choice:</strong> Minh Mang for landscape, symmetry, and slower imperial atmosphere; Khai Dinh when ornate architecture matters more than calm.</li>
<li><strong>Best softener:</strong> Thien Mu Pagoda, Perfume River, local food, and a slower evening that stops the day becoming a monument marathon.</li>
<li><strong>Best skip rule:</strong> do not stack Citadel, three tombs, a river cruise, Dong Ba Market, and the Hai Van Pass transfer into one thin day.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-best-things-hue-priority-map:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to prioritize first</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This is a route-value filter, not a generic attractions ranking. Hue becomes memorable when the imperial story, river setting, food, and transfer logic work together; it becomes forgettable when every stop is treated as equally compulsory.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-things-hue-priority-map">
<thead>
<tr><th>Experience</th><th>Why it matters</th><th>Best fit</th><th>Watch out for</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Experience">Hue Citadel and Imperial City</td><td data-label="Why it matters">The Citadel is the clearest reason Hue belongs in the route: scale, imperial history, restoration context, and a sense of place no beach base can replace.</td><td data-label="Best fit">Every first Hue stay with one real morning or late-afternoon block.</td><td data-label="Watch out for">Going at the hottest exposed hour or moving too fast to read the space.</td><td data-label="VietnamGuide verdict">Protect this first, then decide what the rest of the day can hold.</td></tr>
<tr><td data-label="Experience">Minh Mang Tomb</td><td data-label="Why it matters">The most balanced tomb choice for landscape, ceremony, water, symmetry, and quiet pacing.</td><td data-label="Best fit">First-timers choosing one tomb, heritage travelers, couples, photographers, and slower premium routes.</td><td data-label="Watch out for">Adding it after the Citadel without food, shade, or transport planning.</td><td data-label="VietnamGuide verdict">Best default tomb when you only choose one.</td></tr>
<tr><td data-label="Experience">Thien Mu Pagoda</td><td data-label="Why it matters">Connects Hue's religious, river, and visual identity without demanding the intensity of another major tomb.</td><td data-label="Best fit">Most first-time routes, especially when paired with a river or west-bank transfer.</td><td data-label="Watch out for">Treating it as a five-minute photo stop instead of a calm reset.</td><td data-label="VietnamGuide verdict">High value when it softens the day.</td></tr>
<tr><td data-label="Experience">Perfume River time</td><td data-label="Why it matters">The river gives Hue its rhythm and helps the city feel less like separate monuments connected by vehicles.</td><td data-label="Best fit">Two-night stays, couples, families, slower evenings, and travelers who need a lower-effort block.</td><td data-label="Watch out for">Poor weather, weak boat routing, or booking a cruise just because the river is famous.</td><td data-label="VietnamGuide verdict">Use it for atmosphere and pacing, not as a compulsory tour.</td></tr>
<tr><td data-label="Experience">Khai Dinh Tomb</td><td data-label="Why it matters">Adds a very different, more ornate visual chapter from Minh Mang and the Citadel.</td><td data-label="Best fit">Architecture-led travelers, photographers, and routes with a private car or enough tomb time.</td><td data-label="Watch out for">Combining too many tombs until the differences blur.</td><td data-label="VietnamGuide verdict">Add as the second tomb only when the day still breathes.</td></tr>
<tr><td data-label="Experience">Hue food and Dong Ba Market</td><td data-label="Why it matters">Hue's food is part of the destination, not filler between heritage stops: small dishes, rice cakes, noodle bowls, royal-influenced meals, and market context.</td><td data-label="Best fit">Food travelers, low-stress evenings, rainy-day pivots, and anyone staying central.</td><td data-label="Watch out for">Letting a long monument day erase the meal that would make Hue feel local.</td><td data-label="VietnamGuide verdict">Protect one food block before adding a third monument.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-things-hue-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo proof: what each Hue chapter feels like</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The images are evidence for planning, not decoration. They show why Hue needs pacing: large imperial spaces, river calm, religious stops, and tomb landscapes each ask for a different kind of time.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-best-things-hue-photo-grid" aria-label="Hue guide photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Historic architecture inside the Hue Citadel in central Vietnam" loading="lazy" decoding="async"><figcaption>The Citadel is Hue's core route argument and should not be squeezed into the weakest hour of the day. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">CEphoto, Uwe Aranas / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$thien_mu_image}" alt="Thien Mu Pagoda beside the Perfume River in Hue, Vietnam" loading="lazy" decoding="async"><figcaption>Thien Mu Pagoda works best as a calm river-side chapter, not another rushed monument pin. Image: <a href="{$thien_mu_credit_url}" target="_blank" rel="license noopener">CEphoto, Uwe Aranas / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$minh_mang_image}" alt="Minh Mang Tomb landscape and imperial architecture in Hue, Vietnam" loading="lazy" decoding="async"><figcaption>Minh Mang is the strongest default tomb when the route has room for one thoughtful royal landscape. Image: <a href="{$minh_mang_credit_url}" target="_blank" rel="license noopener">CEphoto, Uwe Aranas / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$perfume_river_image}" alt="Perfume River in Hue, Vietnam" loading="lazy" decoding="async"><figcaption>The Perfume River explains why Hue should feel slower than a road transfer between Central Vietnam bases. Image: <a href="{$perfume_river_credit_url}" target="_blank" rel="license noopener">CEphoto, Uwe Aranas / CC BY-SA 3.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-best-things-hue-route-fit:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">How many Hue days do you actually need?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Hue is often damaged by bad night allocation. One protected day can be excellent; one rushed drive-through stop can make the city feel thinner than it is.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-things-hue-route-fit">
<thead>
<tr><th>Hue time</th><th>Keep</th><th>Add only if the route breathes</th><th>Skip first</th><th>Why</th></tr>
</thead>
<tbody>
<tr><td data-label="Hue time">Drive-through or lunch stop</td><td data-label="Keep">A single focused stop only if logistics are already clean.</td><td data-label="Add only if the route breathes">Almost nothing; use the transfer for comfort rather than false depth.</td><td data-label="Skip first">Citadel plus tomb plus river plus market in a few hours.</td><td data-label="Why">Hue without context can feel like admin, not heritage.</td></tr>
<tr><td data-label="Hue time">One night</td><td data-label="Keep">Late arrival meal, early Citadel, one tomb or Thien Mu, then a clean onward move.</td><td data-label="Add only if the route breathes">A short food or river block if departure is late.</td><td data-label="Skip first">Second tomb, long cruise, and multiple roadside stops before Hoi An.</td><td data-label="Why">One night works only when the plan is disciplined.</td></tr>
<tr><td data-label="Hue time">Two nights</td><td data-label="Keep">Citadel, one royal tomb, Thien Mu, Perfume River or food, and a slower evening.</td><td data-label="Add only if the route breathes">Khai Dinh, Dong Ba Market, a guided food route, or a scenic Hai Van transfer.</td><td data-label="Skip first">A third major monument that weakens meals and rest.</td><td data-label="Why">This is the clean default for most heritage-minded first trips.</td></tr>
<tr><td data-label="Hue time">Three nights or central heritage base</td><td data-label="Keep">Deeper tomb selection, museum or craft context, better meals, and transfer slack.</td><td data-label="Add only if the route breathes">Bach Ma, DMZ history, or slower countryside if those are personal priorities.</td><td data-label="Skip first">Adding distant trips because the hotel is already booked.</td><td data-label="Why">Extra Hue time should buy depth, not a scattered radius map.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-things-hue-weather-pivots:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Weather and season pivots</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Hue planning depends on central Vietnam weather more than many generic itineraries admit. Keep the imperial core, then move tombs, river time, markets, and the Hue-to-Hoi An transfer around heat, rain, and storm exposure.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-things-hue-weather-pivots">
<thead>
<tr><th>Travel window</th><th>Best Hue bias</th><th>Move earlier</th><th>Move later or cut</th><th>Route note</th></tr>
</thead>
<tbody>
<tr><td data-label="Travel window">March to May</td><td data-label="Best Hue bias">Strong all-round heritage window with better odds for Citadel, tombs, river, and the scenic central transfer.</td><td data-label="Move earlier">Citadel and exposed tomb walking if the day is warming fast.</td><td data-label="Move later or cut">Duplicate monument stops that add little after the core is protected.</td><td data-label="Route note">Use the good window for better pacing, not more checklist pressure.</td></tr>
<tr><td data-label="Travel window">June to August</td><td data-label="Best Hue bias">Early sightseeing, shaded pauses, private transport, food breaks, and lighter afternoons.</td><td data-label="Move earlier">Citadel, Minh Mang, Khai Dinh, and any exposed walking.</td><td data-label="Move later or cut">Midday ruins, long uncovered transfers, and overlong market wandering.</td><td data-label="Route note">Heat turns hotel location and transport quality into real planning decisions.</td></tr>
<tr><td data-label="Travel window">September to November</td><td data-label="Best Hue bias">Flexible heritage blocks, live weather checks, refundable add-ons, and realistic expectations for river and road plans.</td><td data-label="Move earlier">Any clear outdoor window, especially tombs and transfer viewpoints.</td><td data-label="Move later or cut">Non-refundable river plans, beach assumptions, and scenic road stops in poor weather.</td><td data-label="Route note">Slack is more valuable than another fixed booking in storm-sensitive months.</td></tr>
<tr><td data-label="Travel window">December to February</td><td data-label="Best Hue bias">Cooler walking, food, pagoda, tomb, and atmospheric city time with softer beach expectations.</td><td data-label="Move earlier">Citadel or tombs on clearer mornings.</td><td data-label="Move later or cut">Plans that depend on warm beach weather after Hue.</td><td data-label="Route note">Let Hue be a heritage chapter, not a beach-weather substitute.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-things-hue-skip-logic:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to skip first in Hue</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Skipping is what makes Hue feel premium. The city is strongest when each stop changes the story; it gets weaker when the day becomes a sequence of gates, tomb courtyards, and vehicle doors.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-best-things-hue-skip-logic">
<thead>
<tr><th>If you want...</th><th>Protect this</th><th>Skip first</th><th>Only add back when...</th></tr>
</thead>
<tbody>
<tr><td data-label="If you want...">Classic first Hue</td><td data-label="Protect this">Citadel, one tomb, Thien Mu or river, one good meal, and enough transfer margin.</td><td data-label="Skip first">Third monument, long cruise, and distant detours.</td><td data-label="Only add back when...">The core day still has shade, food, and recovery space.</td></tr>
<tr><td data-label="If you want...">Heritage depth</td><td data-label="Protect this">A guide or enough reading time at the Citadel plus one tomb chosen for contrast.</td><td data-label="Skip first">Market wandering that steals the only serious heritage block.</td><td data-label="Only add back when...">The second day is not dominated by a transfer.</td></tr>
<tr><td data-label="If you want...">Food and atmosphere</td><td data-label="Protect this">Dong Ba Market, local dishes, river or cafe downtime, and a calmer evening.</td><td data-label="Skip first">A tomb marathon that leaves no appetite or attention.</td><td data-label="Only add back when...">The food block is a real part of the day, not leftovers.</td></tr>
<tr><td data-label="If you want...">Lower-stress family travel</td><td data-label="Protect this">Private transport, short exposed walks, clear lunch timing, and one major heritage stop at a time.</td><td data-label="Skip first">Long uncovered sightseeing in heat or rain.</td><td data-label="Only add back when...">Weather, attention, and onward logistics are all forgiving.</td></tr>
<tr><td data-label="If you want...">Premium central Vietnam</td><td data-label="Protect this">Good hotel location, a knowledgeable guide, one excellent tomb, and a relaxed Hue-to-Hoi An handoff.</td><td data-label="Skip first">Cheap transfer timing that makes the whole day feel rushed.</td><td data-label="Only add back when...">The add-on makes the route calmer or more meaningful.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-best-things-hue-official-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Official checks before locking the Hue day</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use official and primary sources for framing, then verify live tickets, opening rules, restoration access, weather, river plans, and transfer timing close to travel. This guide is designed to hold its editorial judgment, not to replace same-week checks.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-best-things-hue-official-checks"} -->
<ul class="wp-block-list vg-check-list vg-best-things-hue-official-checks">
<li>Destination frame: start with <a href="https://vietnam.travel/places-to-go/central-vietnam/hue" target="_blank" rel="noopener">Vietnam.travel Hue</a> and <a href="https://vietnam.travel/places-to-go/central-vietnam" target="_blank" rel="noopener">Vietnam.travel Central Vietnam</a> before deciding whether Hue is a real central chapter or a pass-through stop.</li>
<li>Heritage context: use the <a href="https://whc.unesco.org/en/list/678/" target="_blank" rel="noopener">UNESCO Complex of Hue Monuments listing</a> before reducing the city to a Citadel photo stop.</li>
<li>Ticket and access checks: use the <a href="https://hueworldheritage.org.vn/en-us/Tourism-information/Price" target="_blank" rel="noopener">Hue Monuments Conservation Centre price page</a> close to travel, especially when choosing multi-site tickets, guides, or tomb combinations.</li>
<li>Season and weather: compare <a href="https://vietnam.travel/things-to-do/weather-and-climate-vietnam" target="_blank" rel="noopener">Vietnam.travel weather and climate</a> with <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a> before paying for river, tomb, or scenic transfer extras.</li>
<li>Route logistics: use <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before choosing train, flight, private car, or Hue-to-Hoi An transfer timing.</li>
<li>Planning order: read <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, <a href="/destinations/best-places-to-visit-vietnam/">Best Places to Visit in Vietnam</a>, <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, <a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a>, and <a href="/destinations/best-things-to-do-in-hoi-an/">Best Things to Do in Hoi An</a> before stretching central Vietnam beyond its real slack.</li>
</ul>
<!-- /wp:list -->

<!-- vg-best-things-hue-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hue planning FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-best-things-hue-faq">
<details><summary>Is Hue worth visiting on a first trip to Vietnam?</summary><p>Yes, when imperial history, food, and a more serious central Vietnam chapter matter. Skip or shorten Hue when the route is already tight, beach recovery is the priority, or Hoi An needs the protected day more.</p></details>
<details><summary>How many nights should I stay in Hue?</summary><p>Two nights is the clean default for heritage-minded first trips because it protects the Citadel, one tomb, Thien Mu or river time, food, and a less fragile onward transfer. One night can work if the plan is selective. A drive-through stop is usually too thin.</p></details>
<details><summary>Which royal tomb should I choose first?</summary><p>Choose Minh Mang first if you want the strongest balance of landscape, water, symmetry, and calm. Add Khai Dinh when ornate architecture or photography matters and the day still has enough time. Do not add several tombs simply because a driver package allows it.</p></details>
<details><summary>Should I do Hue before or after Hoi An?</summary><p>Either order can work. Hue before Hoi An often creates a strong heritage-to-old-town sequence and can make the Hai Van transfer meaningful. Hoi An before Hue can work when flights, hotels, or weather make that direction cleaner. Choose the order that reduces transfer pressure.</p></details>
<details><summary>Is a Perfume River cruise a must-do?</summary><p>No. The river is important to Hue's feel, but a cruise is not automatically better than a well-timed pagoda visit, riverside walk, or food block. Add a boat when weather, route, and timing support it; skip it when it would make the heritage day rushed.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-best-things-hue-hero:v1',
    'concierge verdict' => 'vg-best-things-hue-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'priority map' => 'vg-best-things-hue-priority-map:v1',
    'photo grid' => 'vg-best-things-hue-photo-grid:v1',
    'route fit' => 'vg-best-things-hue-route-fit:v1',
    'weather pivots' => 'vg-best-things-hue-weather-pivots:v1',
    'skip logic' => 'vg-best-things-hue-skip-logic:v1',
    'official checks' => 'vg-best-things-hue-official-checks:v1',
    'FAQ' => 'vg-best-things-hue-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('Best Things to Do in Hue guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Best Things to Do in Hue',
    'post_name'      => 'best-things-to-do-in-hue',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led guide to the best things to do in Hue, with Citadel and tomb priorities, route-fit judgment, licensed photo proof, weather pivots, skip logic, official checks, and central Vietnam planning links.',
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
    vg_ops_fail('Could not publish Best Things to Do in Hue guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Best Things to Do in Hue guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Best Things to Do in Hue: What Is Actually Worth Your Time');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Hue guide for international visitors: Citadel, tombs, Thien Mu, Perfume River, food, route fit, weather pivots, skip logic, official checks, and photo proof.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'best things to do in Hue');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether Hue deserves a protected central Vietnam heritage day before adding tombs, river plans, food blocks, or the Hue-to-Hoi An transfer.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 18, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Best Things to Do in Hue guide with an Evidence-led Concierge verdict, Citadel and tomb priority map, licensed photo proof, route-fit table, weather pivots, skip logic, official checks, FAQ, source trail, update log, and related-route refresh helpers.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Hue destination page - https://vietnam.travel/places-to-go/central-vietnam/hue - checked July 18, 2026\nVietnam.travel - Central Vietnam destinations - https://vietnam.travel/places-to-go/central-vietnam - checked July 18, 2026\nVietnam.travel - Weather and climate in Vietnam - https://vietnam.travel/things-to-do/weather-and-climate-vietnam - checked July 18, 2026\nUNESCO World Heritage Centre - Complex of Hue Monuments - https://whc.unesco.org/en/list/678/ - checked July 18, 2026\nHue Monuments Conservation Centre - Tourism information and ticket price reference - https://hueworldheritage.org.vn/en-us/Tourism-information/Price - checked July 18, 2026\nWikimedia Commons image record - Hue Vietnam Citadel - https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg - license checked July 18, 2026\nWikimedia Commons image record - Hue Vietnam Thien Mu Temple and Pagoda - https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Thien-Mu-Temple-and-Pagoda-03.jpg - license checked July 18, 2026\nWikimedia Commons image record - Hue Vietnam Tomb of Emperor Minh Mang - https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Tomb-of-Emperor-Minh-Mang-01.jpg - license checked July 18, 2026\nWikimedia Commons image record - Hue Vietnam Perfume River - https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Perfume-River-01.jpg - license checked July 18, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Hue is strongest when the route protects one imperial day with context, shade, food, river rhythm, and enough transfer margin for central Vietnam to stay calm.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Hue guide built as an English-first, evergreen Evidence-led Concierge route filter rather than a generic monument list.\nConcierge verdict that says when Hue deserves a protected heritage day and when it should be cut or lengthened.\nPriority map that separates Citadel, Minh Mang, Thien Mu, Perfume River, Khai Dinh, and Hue food by route value.\nPhoto-led proof using licensed images with visible credits for the Citadel, Thien Mu Pagoda, Minh Mang Tomb, and Perfume River.\nRoute-fit table that separates drive-through, one-night, two-night, and three-night Hue decisions.\nWeather and season pivots for March-May, June-August, September-November, and December-February using official Vietnam weather framing.\nSkip logic that helps travelers remove weak monument stacking, poor-weather river plans, and fragile transfers.\nOfficial source checks tied to Vietnam.travel, UNESCO, Hue Monuments Conservation Centre, weather, transport, cost, and route planning.\nInternal links to the Travel Guide, Best Places, regional comparison, Best Time, 10 Days, 14 Days, UNESCO Heritage, Hoi An, Transport, and Cost guides.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start here if you still need the full first-trip planning order.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Decide whether Hue belongs in the broader destination shortlist.\nNorth vs Central vs South Vietnam | /compare/north-central-south-vietnam/ | Confirm whether central Vietnam should lead the route.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check whether Hue weather supports tombs, river time, and the scenic transfer.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Test whether a short route has enough time to keep Hue without thinning Hoi An or the bay.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Use the longer route when Hue, Hoi An, and a southern chapter all need real space.\nUNESCO Heritage Sites in Vietnam | /destinations/unesco-heritage-sites-vietnam/ | Use this if the Complex of Hue Monuments changes the heritage priority.\nBest Things to Do in Hoi An | /destinations/best-things-to-do-in-hoi-an/ | Pair Hue with Hoi An only when central Vietnam has enough protected slack.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Check train, flight, private car, and Hue-to-Hoi An transfer choices before booking.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price guides, private transfers, hotels, extra nights, and ticket choices before committing.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and body images: Hue Citadel, Thien Mu Pagoda, Minh Mang Tomb, and Perfume River by CEphoto, Uwe Aranas, CC BY-SA 3.0.');
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
    vg_ops_fail('Best Things to Do in Hue guide was updated but is not published.');
}

vg_ops_refresh_destinations_hub();
vg_ops_append_related_route(
    'destinations/best-places-to-visit-vietnam',
    'Best Places',
    'Best Things to Do in Hue | /destinations/best-things-to-do-in-hue/ | Decide when Hue deserves a protected imperial-history day instead of a drive-through central Vietnam transfer.'
);
vg_ops_append_related_route(
    'itineraries/10-days-in-vietnam',
    '10 Days itinerary',
    'Best Things to Do in Hue | /destinations/best-things-to-do-in-hue/ | Use this before deciding whether Hue or Hoi An gets the protected central Vietnam day.'
);
vg_ops_append_related_route(
    'itineraries/14-days-in-vietnam',
    '14 Days itinerary',
    'Best Things to Do in Hue | /destinations/best-things-to-do-in-hue/ | Use this when the two-week route can protect a serious central heritage chapter.'
);

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Best Things to Do in Hue guide: {$page_id} {$updated_permalink}");

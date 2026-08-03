<?php
/**
 * Publish the Safety and Scams in Vietnam guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-safety-scams-vietnam-guide.php --allow-root
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
    $value = getenv('VG_FORCE_SAFETY_SCAMS_GUIDE_REPUBLISH');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the Safety and Scams in Vietnam guide.');
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

function vg_ops_refresh_plan_hub(): void
{
    $hub = get_page_by_path('plan', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_ops_log('Skipped Plan hub refresh: plan page was not found or is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('plan/safety-scams-vietnam')) {
        vg_ops_log('Skipped Plan hub refresh: Safety and Scams guide is not published.');
        return;
    }

    $marker = '<!-- vg-safety-scams-plan-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Reduce avoidable risk without over-planning fear</h3><p>The <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> guide helps travelers separate common friction from real trip-changing risks: traffic, petty theft, taxis, money, nightlife, water, health, and emergency checks.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Plan hub safety note', $block);
    vg_ops_upsert_marked_group($hub, 'Plan hub safety note', $marker, $block);
}

$parent = get_page_by_path('plan', OBJECT, 'page');

if (! $parent instanceof WP_Post) {
    vg_ops_fail('Plan parent page was not found.');
}

if ($parent->post_status !== 'publish') {
    vg_ops_fail('Plan parent page is not published.');
}

$page = get_page_by_path('plan/safety-scams-vietnam', OBJECT, 'page');

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_ops_force_republish_enabled()) {
    $permalink = get_permalink($page);
    vg_ops_fail("Safety and Scams in Vietnam guide is not a draft. Set VG_FORCE_SAFETY_SCAMS_GUIDE_REPUBLISH=1 to overwrite existing published content. Current page: {$page->ID} {$page->post_status} {$permalink}");
}

if ($page instanceof WP_Post) {
    vg_ops_log("Preflight Safety and Scams in Vietnam guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_ops_log('Preflight Safety and Scams in Vietnam guide: no existing page found; creating a child page under /plan/.');
}

$review_date = 'July 18, 2026';
$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/56/Noi_Bai_International_Airport_T2_Waiting_Area.jpg/1920px-Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$market_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/c8/Ben_Thanh_Market%2C_2023_%2803%29.jpg/1920px-Ben_Thanh_Market%2C_2023_%2803%29.jpg');
$market_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ben_Thanh_Market,_2023_(03).jpg');
$atm_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f1/ATM_VPBank%2C_Li%E1%BB%85u_Giai%2C_H%C3%A0_N%E1%BB%99i_001.JPG/1920px-ATM_VPBank%2C_Li%E1%BB%85u_Giai%2C_H%C3%A0_N%E1%BB%99i_001.JPG');
$atm_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:ATM_VPBank,_Li%E1%BB%85u_Giai,_H%C3%A0_N%E1%BB%99i_001.JPG');
$road_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1920px-Vietnam%2C_Hai-Van-Pass.jpg');
$road_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg');

$content = <<<HTML
<!-- vg-safety-scams-hero:v1 -->
<!-- wp:cover {"url":"{$hero_image}","dimRatio":62,"overlayColor":"ink","minHeight":620,"className":"alignfull is-dark vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Noi Bai International Airport arrival hall, used for Vietnam safety and arrival-risk planning" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Vietnam planning safety</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Safety and Scams in Vietnam</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Vietnam is a manageable first-trip destination when travelers treat safety as a planning system, not a fear list. The biggest avoidable problems are usually road exposure, petty theft in crowded places, weak taxi/payment habits, nightlife decisions, water/weather assumptions, and skipping health or emergency preparation.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: the goal is not to make the route anxious. It is to remove the predictable friction before it becomes expensive, embarrassing, or trip-changing.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-image-credit"} -->
<p class="vg-image-credit">Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Sky 269 / CC BY-SA 4.0</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div></div>
<!-- /wp:cover -->

<!-- vg-safety-scams-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-safety-scams-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-safety-scams-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>For most international visitors, Vietnam safety is less about avoiding Vietnam and more about controlling the first 48 hours: airport transfer, cash, phone data, road crossings, hotel location, crowded markets, nightlife limits, and source checks before fragile routes.</strong> Plan those correctly and the trip becomes calmer without becoming cautious to the point of dull.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-safety-scams-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-safety-scams-shortlist">
<li><strong>Highest everyday risk:</strong> road exposure, especially motorbikes, road crossings, self-driving, and long transfers.</li>
<li><strong>Most common money risk:</strong> weak taxi/payment habits, poor ATM choices, distracted wallets, and not agreeing the basis of a price before service.</li>
<li><strong>Most useful buffer:</strong> hotel-backed arrival transfer, working mobile data, small cash, offline maps, and an emergency contact note.</li>
<li><strong>Best skip rule:</strong> do not add a late-night transfer, alcohol-heavy evening, scooter rental, and early departure to the same tired travel day.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-safety-scams-risk-map:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-safety-scams-risk-map","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-safety-scams-risk-map">
<!-- wp:heading -->
<h2 class="wp-block-heading">Vietnam safety risk map: what actually changes the trip?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this table before locking hotels, transfers, activities, and nightlife. It keeps the guide practical: the point is not to list every possible scam, but to identify the decisions that prevent the most likely problems.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-safety-scams-risk-map">
<thead><tr><th>Risk area</th><th>Most likely friction</th><th>Best prevention</th><th>Trip-changing trigger</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Risk area">Roads and crossings</td><td data-label="Most likely friction">Stress, unsafe crossings, weak helmet decisions, rushed transfers.</td><td data-label="Best prevention">Use reputable transfers, avoid self-driving, cross slowly and predictably, check route timing door to door.</td><td data-label="Trip-changing trigger">Long road moves in rain, fatigue, mountain routes, or tight same-day connections.</td><td data-label="VietnamGuide verdict">Treat road exposure as the main safety planning variable.</td></tr>
<tr><td data-label="Risk area">Taxis and arrival transfers</td><td data-label="Most likely friction">Overcharging, wrong pickup points, confusing airport exits, tired first-night mistakes.</td><td data-label="Best prevention">Use a hotel-arranged pickup, official app ride, or clearly identified taxi queue; keep hotel address offline.</td><td data-label="Trip-changing trigger">Late arrival, no mobile data, remote hotel, or children/luggage.</td><td data-label="VietnamGuide verdict">Buy certainty on arrival, then be flexible later.</td></tr>
<tr><td data-label="Risk area">Crowded markets and streets</td><td data-label="Most likely friction">Phone snatch risk, pocket pressure, distraction pricing, bag handling.</td><td data-label="Best prevention">Keep phone inside traffic-side pockets less often, carry crossbody, separate cash, pause before paying.</td><td data-label="Trip-changing trigger">Solo night walks, distracted photography, or carrying passports/cards together.</td><td data-label="VietnamGuide verdict">Use simple habits; do not make markets feel forbidden.</td></tr>
<tr><td data-label="Risk area">Money and ATMs</td><td data-label="Most likely friction">ATM fees, exchange confusion, card fallback failure, cash carried poorly.</td><td data-label="Best prevention">Use bank ATMs, split cards, keep small notes, and confirm exchange/payment terms before service.</td><td data-label="Trip-changing trigger">Remote route, island stay, airport arrival, or cash-only tour balance.</td><td data-label="VietnamGuide verdict">Money safety is mostly logistics discipline.</td></tr>
<tr><td data-label="Risk area">Nightlife and drinks</td><td data-label="Most likely friction">Poor judgment, inflated bills, late transport, separation from group.</td><td data-label="Best prevention">Set the way home before the night starts and avoid carrying all cards/passports.</td><td data-label="Trip-changing trigger">Alcohol plus scooter, beach nightlife, solo return, or early transfer next morning.</td><td data-label="VietnamGuide verdict">The safest nightlife plan is decided before the first drink.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-safety-scams-photo-grid:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-safety-scams-photo-grid-section","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-safety-scams-photo-grid-section">
<!-- wp:heading -->
<h2 class="wp-block-heading">Photo context: where safety decisions happen</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>These images are not scare evidence. They show the ordinary places where the right habit matters: arrival halls, markets, ATMs, and road transfers.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-safety-scams-photo-grid" aria-label="Vietnam safety and scams guide photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Noi Bai International Airport T2 waiting area" loading="lazy" decoding="async"><figcaption>Arrival is where a clean transfer, working phone, and hotel address remove the first risk stack. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Sky 269 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$market_image}" alt="Ben Thanh Market in Ho Chi Minh City, Vietnam" loading="lazy" decoding="async"><figcaption>Markets are better with simple bag, phone, and price-confirmation habits. Image: <a href="{$market_credit_url}" target="_blank" rel="license noopener">Bahnfrend / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$atm_image}" alt="ATM in Hanoi, Vietnam" loading="lazy" decoding="async"><figcaption>ATM safety is about machine choice, card separation, and not carrying the full trip budget at once. Image: <a href="{$atm_credit_url}" target="_blank" rel="license noopener">Phan Minh Tuan / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$road_image}" alt="Hai Van Pass road in central Vietnam" loading="lazy" decoding="async"><figcaption>Road decisions matter most when weather, fatigue, luggage, and scenery compete for attention. Image: <a href="{$road_credit_url}" target="_blank" rel="license noopener">Wolkenkratzer / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-safety-scams-city-petty-crime:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-safety-scams-city-petty-crime","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-safety-scams-city-petty-crime">
<!-- wp:heading -->
<h2 class="wp-block-heading">Petty theft and common scam friction</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Official travel advisories tend to frame Vietnam's everyday traveler risk around petty crime, road safety, and situational awareness rather than a need to avoid normal tourist areas. The practical response is boring in the best way: reduce distraction, separate valuables, and remove ambiguity before paying.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-safety-scams-petty-crime">
<thead><tr><th>Situation</th><th>Weak habit</th><th>Better habit</th><th>When to change plan</th></tr></thead>
<tbody>
<tr><td data-label="Situation">Street phone use</td><td data-label="Weak habit">Standing at curbside with phone out for maps or photos.</td><td data-label="Better habit">Step back from the road, check the route, then walk with the phone away.</td><td data-label="When to change plan">Night arrival, heavy traffic, crowded event, or solo walk.</td></tr>
<tr><td data-label="Situation">Markets and bargaining</td><td data-label="Weak habit">Asking for several items, accepting a service, then clarifying price after.</td><td data-label="Better habit">Confirm price, currency, quantity, and inclusion before the service starts.</td><td data-label="When to change plan">If pressure rises or the terms keep changing, leave calmly.</td></tr>
<tr><td data-label="Situation">Taxi or ride pickup</td><td data-label="Weak habit">Getting into the closest car while tired or offline.</td><td data-label="Better habit">Match plate/name/app details or use the hotel/official queue.</td><td data-label="When to change plan">Late arrival, remote hotel, children, or heavy luggage.</td></tr>
<tr><td data-label="Situation">ATM cash</td><td data-label="Weak habit">Withdrawing at a hidden or isolated machine and storing all cash together.</td><td data-label="Better habit">Use bank/indoor ATMs, count discreetly, split cash and cards.</td><td data-label="When to change plan">If the route moves to islands, rural areas, or cash-only stays.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-safety-scams-transport-road:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-safety-scams-transport-road","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-safety-scams-transport-road">
<!-- wp:heading -->
<h2 class="wp-block-heading">Road, transport, and self-driving decisions</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Road safety is the risk category most likely to change the itinerary. A beautiful road can still be a poor decision when the rider is tired, rain is active, luggage is awkward, or the next transfer has no slack.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-safety-scams-transport-road">
<thead><tr><th>Transport choice</th><th>Safer default</th><th>Use caution when</th><th>Related guide</th></tr></thead>
<tbody>
<tr><td data-label="Transport choice">Airport transfer</td><td data-label="Safer default">Prearranged hotel pickup or verified app/official taxi queue for late arrivals.</td><td data-label="Use caution when">No mobile data, unfamiliar pickup point, or long first-night transfer.</td><td data-label="Related guide"><a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a></td></tr>
<tr><td data-label="Transport choice">Scooter or motorbike</td><td data-label="Safer default">Skip self-driving unless licensed, insured, experienced, sober, and rested.</td><td data-label="Use caution when">Mountain roads, rain, poor light, luggage, or social pressure are involved.</td><td data-label="Related guide"><a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a></td></tr>
<tr><td data-label="Transport choice">Private car</td><td data-label="Safer default">Use for scenic transfers, families, luggage, or fragile timing.</td><td data-label="Use caution when">The quote hides stops, waiting time, late arrival, or weather detours.</td><td data-label="Related guide"><a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a></td></tr>
<tr><td data-label="Transport choice">Boat or water route</td><td data-label="Safer default">Check weather, operator, life jackets, return timing, and transfer buffers.</td><td data-label="Use caution when">Storm risk, rough seas, remote island stays, or non-refundable onward travel.</td><td data-label="Related guide"><a href="/destinations/unesco-heritage-sites-vietnam/">UNESCO Heritage Sites in Vietnam</a></td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-safety-scams-water-nightlife:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-safety-scams-water-nightlife","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-safety-scams-water-nightlife">
<!-- wp:heading -->
<h2 class="wp-block-heading">Water, nightlife, and fatigue risks</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The weak point in many trips is not a dramatic scam. It is the combination of heat, alcohol, late transport, unfamiliar water, and an early move the next morning. Build slack around those moments.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-safety-scams-water-nightlife"} -->
<ul class="wp-block-list vg-check-list vg-safety-scams-water-nightlife">
<li>Choose beaches, boats, waterfalls, and island transfers by current conditions, not by fixed itinerary pride.</li>
<li>Do not mix alcohol, scooter riding, unfamiliar roads, and late-night hotel changes.</li>
<li>Keep one card, passport, and full cash reserve out of the nightlife wallet.</li>
<li>For families, make the return transport plan before dinner or beach time starts.</li>
<li>Use travel insurance and health preparation as planning tools, not paperwork after something goes wrong.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- vg-safety-scams-emergency-plan:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-safety-scams-emergency-plan","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-safety-scams-emergency-plan">
<!-- wp:heading -->
<h2 class="wp-block-heading">The five-minute emergency plan</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Do this before the first transfer day, not after a problem starts. Canada Travel Advice lists Vietnam emergency numbers, including police, fire, and ambulance contacts; verify them again before departure because emergency handling and local response quality can vary by location.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-safety-scams-emergency-plan">
<thead><tr><th>Prepare</th><th>What to store</th><th>Why it matters</th></tr></thead>
<tbody>
<tr><td data-label="Prepare">Offline identity note</td><td data-label="What to store">Passport copy, visa screenshot, insurance policy, hotel address, emergency contact.</td><td data-label="Why it matters">It reduces panic if phone data, bag, or wallet access fails.</td></tr>
<tr><td data-label="Prepare">Local help path</td><td data-label="What to store">Hotel front desk, insurer hotline, embassy/consulate contact, local emergency numbers.</td><td data-label="Why it matters">Most minor problems are easier with a trusted local bridge.</td></tr>
<tr><td data-label="Prepare">Money fallback</td><td data-label="What to store">One backup card, some separate cash, and a contact who can help if cards fail.</td><td data-label="Why it matters">Lost wallet should be an inconvenience, not a trip collapse.</td></tr>
<tr><td data-label="Prepare">Medical fallback</td><td data-label="What to store">Medication names, allergies, insurance claim path, nearest credible clinic for the first city.</td><td data-label="Why it matters">CDC-style health prep is most useful when it is accessible offline.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-safety-scams-live-checks:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-safety-scams-live-checks","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-safety-scams-live-checks">
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before booking fragile parts</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Safety guidance changes when a rule, weather event, health notice, route condition, or local incident changes the booking decision. Use official and primary sources before non-refundable moves.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-safety-scams-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-safety-scams-live-checks">
<li>Check <a href="https://www.gov.uk/foreign-travel-advice/vietnam/safety-and-security" target="_blank" rel="noopener">GOV.UK Vietnam safety and security advice</a> before high-risk route changes, road-heavy plans, or nightlife-heavy trips.</li>
<li>Check <a href="https://travel.gc.ca/destinations/vietnam" target="_blank" rel="noopener">Canada Travel Advice for Vietnam</a> for safety/security framing, emergency contacts, and official risk language.</li>
<li>Check <a href="https://wwwnc.cdc.gov/travel/destinations/traveler/none/vietnam" target="_blank" rel="noopener">CDC Travelers' Health for Vietnam</a> before health, vaccination, mosquito, animal, or medication-sensitive plans.</li>
<li>Check <a href="https://vietnam.travel/plan-your-trip/health-safety" target="_blank" rel="noopener">Vietnam.travel health and safety</a> for official traveler-facing preparation, then compare it with your insurer and home-country advisory.</li>
<li>Check <a href="https://vietnam.travel/plan-your-trip/transport-within-vietnam" target="_blank" rel="noopener">Vietnam.travel transport within Vietnam</a> and the <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> guide before road, ferry, train, or airport-transfer decisions.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- vg-safety-scams-faq:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-safety-scams-faq","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-safety-scams-faq">
<!-- wp:heading -->
<h2 class="wp-block-heading">Vietnam safety FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-safety-scams-faq">
<details><summary>Is Vietnam safe for first-time international travelers?</summary><p>Yes for many travelers, if the route is planned calmly. The more useful question is whether the trip controls traffic exposure, arrival transfer, money fallback, mobile data, nightlife decisions, health prep, and weather-sensitive movement.</p></details>
<details><summary>What scams should I worry about most?</summary><p>Focus less on memorizing scam names and more on habits: agree price and currency before service, match app/taxi details, keep phone and wallet handling low-distraction, use bank ATMs, and avoid pressure situations when tired.</p></details>
<details><summary>Should I ride a scooter in Vietnam?</summary><p>Only if you are licensed, insured, experienced, sober, rested, and comfortable with local traffic. For most short first trips, private cars, walks, taxis, and guided transfers are better than learning Vietnamese traffic under pressure.</p></details>
<details><summary>Do I need travel insurance for Vietnam?</summary><p>Yes. Insurance is part of the safety plan, especially for health, scooter or road incidents, baggage, delays, island/boat routes, and medical evacuation. Read the policy exclusions before assuming it covers motorbikes, alcohol-related incidents, adventure activities, or pre-existing conditions.</p></details>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-safety-scams-hero:v1',
    'concierge verdict' => 'vg-safety-scams-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'risk map' => 'vg-safety-scams-risk-map:v1',
    'photo grid' => 'vg-safety-scams-photo-grid:v1',
    'city petty crime' => 'vg-safety-scams-city-petty-crime:v1',
    'transport road' => 'vg-safety-scams-transport-road:v1',
    'water nightlife' => 'vg-safety-scams-water-nightlife:v1',
    'emergency plan' => 'vg-safety-scams-emergency-plan:v1',
    'live checks' => 'vg-safety-scams-live-checks:v1',
    'FAQ' => 'vg-safety-scams-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('Safety and Scams in Vietnam guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Safety and Scams in Vietnam',
    'post_name'      => 'safety-scams-vietnam',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Vietnam safety and scams guide for international travelers, with road-risk judgment, petty theft habits, taxi and money safety, nightlife and water cautions, emergency preparation, live checks, source trail, and update log.',
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
    vg_ops_fail('Could not publish Safety and Scams in Vietnam guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Safety and Scams in Vietnam guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Safety and Scams in Vietnam: Calm Risk Guide for Visitors');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Vietnam safety guide for international visitors: scams, road safety, taxis, ATMs, nightlife, water risk, emergency plan, live checks, and source trail.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Vietnam safety and scams');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide how to reduce avoidable Vietnam travel risk without over-planning fear: road exposure, arrival transfer, petty theft, money, nightlife, water, health, and emergency checks.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($page_id, 'vg_eeat_update_summary', 'Initial Safety and Scams in Vietnam guide published with an Evidence-led Concierge verdict, risk map, photo context grid, petty theft and scam habits, transport and road-risk decision table, water/nightlife cautions, emergency plan, live-check source list, FAQ, source trail, update log, and Plan hub refresh.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "GOV.UK - Vietnam safety and security - https://www.gov.uk/foreign-travel-advice/vietnam/safety-and-security - checked {$review_date}\nGovernment of Canada Travel Advice - Vietnam - https://travel.gc.ca/destinations/vietnam - checked {$review_date}\nCDC Travelers' Health - Vietnam - https://wwwnc.cdc.gov/travel/destinations/traveler/none/vietnam - checked {$review_date}\nVietnam.travel - Health and safety - https://vietnam.travel/plan-your-trip/health-safety - checked {$review_date}\nVietnam.travel - Transport within Vietnam - https://vietnam.travel/plan-your-trip/transport-within-vietnam - checked {$review_date}\nWikimedia Commons image record - Noi Bai International Airport T2 Waiting Area - https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg - license checked {$review_date}\nWikimedia Commons image record - Ben Thanh Market, 2023 (03) - https://commons.wikimedia.org/wiki/File:Ben_Thanh_Market,_2023_(03).jpg - license checked {$review_date}\nWikimedia Commons image record - ATM VPBank, Lieu Giai, Ha Noi - https://commons.wikimedia.org/wiki/File:ATM_VPBank,_Li%E1%BB%85u_Giai,_H%C3%A0_N%E1%BB%99i_001.JPG - license checked {$review_date}\nWikimedia Commons image record - Vietnam, Hai-Van-Pass - https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg - license checked {$review_date}");
update_post_meta($page_id, 'vg_eeat_field_note', 'Vietnam safety is best handled as a calm arrival, road, money, health, and emergency-prep system rather than a fear-driven list of every possible scam.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Guide built around practical risk decisions rather than generic alarm or rewritten advisory content.\nConcierge verdict that frames Vietnam as manageable for international visitors when avoidable friction is handled early.\nRisk map that ranks road exposure, airport transfers, crowded markets, money, taxis, nightlife, water, and health by trip impact.\nPhoto context grid using licensed airport, market, ATM, and road photography with visible credits.\nPetty theft and scam guidance that focuses on repeatable habits: price confirmation, phone handling, taxi matching, bank ATMs, and card/cash separation.\nTransport and road table linked to existing VietnamGuide transport, cost, best-time, and heritage route pages.\nEmergency plan tied to official advisory sources and offline-prep behavior.\nLive-check list using GOV.UK, Canada Travel Advice, CDC, Vietnam.travel health/safety, and Vietnam.travel transport sources.\nFAQ that answers first-time safety, scams, scooter, and travel insurance decisions without fear language.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start here if you still need the full planning order before risk checks.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Use this before long road moves, airport transfers, ferries, or scenic routes.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Reduce ATM, cash, exchange, and payment friction before arrival.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, ride apps, hotel contact, and emergency backup available.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check weather windows before road, boat, island, and mountain plans.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Price safety buffers such as insurance, private transfers, better hotels, and emergency reserves.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and arrival image: Noi Bai International Airport T2 waiting area by Sky 269, CC BY-SA 4.0. Body images: Ben Thanh Market by Bahnfrend, CC BY-SA 4.0; Hanoi ATM by Phan Minh Tuan, CC BY-SA 4.0; Hai Van Pass by Wolkenkratzer, CC BY-SA 4.0.');
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
    vg_ops_fail('Safety and Scams in Vietnam guide was updated but is not published.');
}

vg_ops_refresh_plan_hub();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Safety and Scams in Vietnam guide: {$page_id} {$updated_permalink}");

<?php
/**
 * Publish the Health and Travel Insurance for Vietnam guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-health-travel-insurance-guide.php --allow-root
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
    $value = getenv('VG_FORCE_HEALTH_INSURANCE_GUIDE_REPUBLISH');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the Health and Travel Insurance for Vietnam guide.');
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

    if (! vg_ops_published_page_exists('plan/health-travel-insurance-vietnam')) {
        vg_ops_log('Skipped Plan hub refresh: Health and Travel Insurance guide is not published.');
        return;
    }

    $marker = '<!-- vg-health-insurance-plan-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Protect the trip before the first non-refundable booking</h3><p>The <a href="/plan/health-travel-insurance-vietnam/">Health and Travel Insurance for Vietnam</a> guide helps travelers match insurance, medical care, evacuation, vaccines, and policy exclusions to the route they are actually taking.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Plan hub health note', $block);
    vg_ops_upsert_marked_group($hub, 'Plan hub health note', $marker, $block);
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

    if (str_contains($current, '/plan/health-travel-insurance-vietnam/')) {
        vg_ops_log("Skipped related-route refresh for {$label}: Health and Travel Insurance is already present.");
        return;
    }

    $next = trim($current) === '' ? $route_line : rtrim($current) . "\n" . $route_line;
    update_post_meta($page->ID, 'vg_eeat_related_routes', $next);

    vg_ops_log("Added Health and Travel Insurance related route to {$label}: {$page->ID}");
}

function vg_ops_refresh_inbound_related_routes(): void
{
    $route_line = 'Health and Travel Insurance for Vietnam | /plan/health-travel-insurance-vietnam/ | Match emergency medical, evacuation, route-risk, and exclusion checks to the trip before non-refundable bookings.';

    foreach (
        [
            'plan/vietnam-travel-guide'  => 'Vietnam Travel Guide',
            'plan/safety-scams-vietnam'  => 'Safety and Scams in Vietnam guide',
            'costs/vietnam-travel-cost'  => 'Vietnam Travel Cost guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        ] as $path => $label
    ) {
        vg_ops_add_related_route_to_page($path, $label, $route_line);
    }
}

$parent = get_page_by_path('plan', OBJECT, 'page');

if (! $parent instanceof WP_Post) {
    vg_ops_fail('Plan parent page was not found.');
}

if ($parent->post_status !== 'publish') {
    vg_ops_fail('Plan parent page is not published.');
}

$page = get_page_by_path('plan/health-travel-insurance-vietnam', OBJECT, 'page');

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! vg_ops_force_republish_enabled()) {
    $permalink = get_permalink($page);
    vg_ops_fail("Health and Travel Insurance for Vietnam guide is not a draft. Set VG_FORCE_HEALTH_INSURANCE_GUIDE_REPUBLISH=1 to overwrite existing published content. Current page: {$page->ID} {$page->post_status} {$permalink}");
}

if ($page instanceof WP_Post) {
    vg_ops_log("Preflight Health and Travel Insurance for Vietnam guide: {$page->ID} {$page->post_status} " . get_permalink($page));
} else {
    vg_ops_log('Preflight Health and Travel Insurance for Vietnam guide: no existing page found; creating a child page under /plan/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/56/Noi_Bai_International_Airport_T2_Waiting_Area.jpg/1920px-Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$hospital_one_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/8e/City_International_Hospital_Vietnam.jpg/1920px-City_International_Hospital_Vietnam.jpg');
$hospital_one_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:City_International_Hospital_Vietnam.jpg');
$hospital_two_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/c4/FV_hospital.JPG/1920px-FV_hospital.JPG');
$hospital_two_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:FV_hospital.JPG');
$ambulance_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d5/Vietnam_ambulance.jpg/1920px-Vietnam_ambulance.jpg');
$ambulance_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam_ambulance.jpg');

$content = <<<HTML
<!-- vg-health-insurance-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":60,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Noi Bai International Airport T2 waiting area in Hanoi, used as a Vietnam health and travel insurance planning hero image" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed planning guide - Updated July 18, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Health and Travel Insurance for Vietnam</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Travel insurance is not a box to tick after the route is finished. It is the financial and medical backstop that keeps a single hospital visit, evacuation, scooter mistake, weather disruption, or pre-existing condition from turning a Vietnam trip into a bigger event than the trip itself.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: this page is about planning, not diagnosis. For vaccines, prescriptions, pregnancy, chronic conditions, children, or animal exposure, speak with a qualified clinician or travel clinic before you go.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">Sky 269 / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-health-insurance-concierge-verdict -->
<!-- wp:group {"className":"vg-concierge-verdict vg-health-insurance-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-health-insurance-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Buy the policy that would still feel obvious if the worst day happened in a city hospital, on a road transfer, or after an island, cruise, or motorbike day.</strong> For most travelers, the right answer is emergency medical cover, medical evacuation, repatriation, trip interruption, and a clean read on what activities and pre-existing conditions are actually included.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list vg-health-insurance-shortlist"} -->
<ul class="wp-block-list vg-feature-list vg-health-insurance-shortlist">
<li><strong>Buy before the first non-refundable booking</strong> if the route includes meaningful deposits, remote movement, or any activity that would be expensive to recover from.</li>
<li><strong>Read the exclusions, not only the headline limit.</strong> Motorbike use, adventure activities, alcohol, pre-existing conditions, and evacuation wording matter more than the marketing page.</li>
<li><strong>Use a travel clinic early.</strong> CDC and Vietnam.travel both point travelers toward advance health planning, and that works best when it starts weeks before departure.</li>
<li><strong>Keep the plan tied to the route.</strong> A city-only stay, a north rural loop, a central coast trip, and an island/cruise route do not carry the same medical or evacuation risk.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-health-insurance-at-a-glance:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-health-insurance-at-a-glance","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-health-insurance-at-a-glance">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">At a glance</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The fastest useful answer</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-health-insurance-table">
<tbody>
<tr><td data-label="Question"><strong>What should the policy definitely include?</strong></td><td data-label="Answer">Emergency medical care, hospital treatment, medical evacuation, and repatriation are the core protections that matter most for Vietnam.</td></tr>
<tr><td data-label="Question"><strong>When should I buy it?</strong></td><td data-label="Answer">Before the first non-refundable flight, hotel, cruise, or tour deposit that would be painful to lose.</td></tr>
<tr><td data-label="Question"><strong>When should I talk to a clinician?</strong></td><td data-label="Answer">Ideally 6-8 weeks before travel if vaccines, prescriptions, pregnancy, chronic conditions, or rural exposure are in play.</td></tr>
<tr><td data-label="Question"><strong>What is the most common mistake?</strong></td><td data-label="Answer">Buying a cheap policy without checking motorbike, adventure, pre-existing condition, deductible, or evacuation wording.</td></tr>
<tr><td data-label="Question"><strong>What changes the risk profile most?</strong></td><td data-label="Answer">Rural roads, islands, boats, scooters, family travel, long-haul fatigue, and anything that makes the nearest capable hospital harder to reach.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-health-insurance-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where the decision becomes real</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>These images are practical cues, not scare tactics. They show the ordinary places where health preparation and insurance become useful: arrival, hospital access, and emergency transport.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-health-insurance-photo-grid" aria-label="Vietnam health and travel insurance photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Noi Bai International Airport T2 waiting area in Hanoi" loading="lazy" decoding="async"><figcaption>Arrival is where tired judgment, delayed flights, and luggage can make health planning matter sooner than expected. Image: <a href="{$hero_credit_url}" target="_blank" rel="license noopener">Sky 269 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hospital_one_image}" alt="City International Hospital in Vietnam" loading="lazy" decoding="async"><figcaption>Major-city hospitals are the right benchmark for what your insurer should be able to cover quickly. Image: <a href="{$hospital_one_credit_url}" target="_blank" rel="license noopener">Keronii / CC0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hospital_two_image}" alt="FV Hospital in Ho Chi Minh City, Vietnam" loading="lazy" decoding="async"><figcaption>Private hospitals can improve access and comfort, but the policy still needs to pay the bill cleanly. Image: <a href="{$hospital_two_credit_url}" target="_blank" rel="license noopener">Nguyen Thanh Quang / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$ambulance_image}" alt="Vietnam ambulance vehicle" loading="lazy" decoding="async"><figcaption>Emergency transport is a coverage question, a route question, and sometimes a speed question. Image: <a href="{$ambulance_credit_url}" target="_blank" rel="license noopener">Dickelbers / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-health-insurance-coverage-table:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-health-insurance-coverage","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-health-insurance-coverage">
<!-- wp:heading -->
<h2 class="wp-block-heading">What the policy should cover</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Do not start with price. Start with the things that can break a trip and ask whether the policy actually pays for them.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-health-insurance-coverage-table">
<thead><tr><th>Coverage area</th><th>Why it matters in Vietnam</th><th>What to verify live</th><th>VietnamGuide verdict</th></tr></thead>
<tbody>
<tr><td data-label="Coverage area">Emergency medical care</td><td data-label="Why it matters in Vietnam">A clinic visit, ER consult, or inpatient stay is the most basic financial shock to prevent.</td><td data-label="What to verify live">Per-incident limit, deductible, direct billing, and whether hospitals in the cities you are visiting are in network.</td><td data-label="VietnamGuide verdict">Mandatory.</td></tr>
<tr><td data-label="Coverage area">Medical evacuation and repatriation</td><td data-label="Why it matters in Vietnam">Remote routes, islands, or a difficult case can require transfer to a better facility or back home.</td><td data-label="What to verify live">Evacuation limit, trigger language, and whether it covers transfer to the nearest capable hospital or to your home country.</td><td data-label="VietnamGuide verdict">Mandatory for any non-city-only route.</td></tr>
<tr><td data-label="Coverage area">Trip interruption and delay</td><td data-label="Why it matters in Vietnam">Weather, illness, missed connections, and recovery time can break a carefully built itinerary.</td><td data-label="What to verify live">Delay hours, interruption reasons, and whether a missed domestic leg or cruise departure is covered.</td><td data-label="VietnamGuide verdict">Strongly recommended.</td></tr>
<tr><td data-label="Coverage area">Baggage and gear</td><td data-label="Why it matters in Vietnam">If medication, camera gear, or a scooter day bag is delayed, the trip becomes harder fast.</td><td data-label="What to verify live">Per-item caps, electronics rules, delay thresholds, and receipt requirements.</td><td data-label="VietnamGuide verdict">Useful, but secondary to medical cover.</td></tr>
<tr><td data-label="Coverage area">Adventure and motorbike use</td><td data-label="Why it matters in Vietnam">A lot of traveler risk comes from scooters, road exposure, and optional activities around water or mountains.</td><td data-label="What to verify live">Whether riding, passenger use, engine size, helmet, license, alcohol, and activity exclusions are acceptable under the policy.</td><td data-label="VietnamGuide verdict">Only acceptable if the policy clearly allows what you are doing.</td></tr>
<tr><td data-label="Coverage area">Pre-existing conditions</td><td data-label="Why it matters in Vietnam">A trip can be ruined by a condition that was already known before you left.</td><td data-label="What to verify live">Disclosure rules, look-back period, stability requirement, and whether the policy needs an add-on.</td><td data-label="VietnamGuide verdict">Never assume it is covered.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-health-insurance-route-risk-map:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-health-insurance-risk-map","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-health-insurance-risk-map">
<!-- wp:heading -->
<h2 class="wp-block-heading">Route risk map: how the trip changes the insurance question</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Insurance gets more important when the route gets more fragile. This is the shortest way to decide how much coverage you actually need.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-health-insurance-risk-table">
<thead><tr><th>Trip style</th><th>Main health or insurance pressure</th><th>Coverage to prioritize</th><th>Best live check</th></tr></thead>
<tbody>
<tr><td data-label="Trip style">City-only stay</td><td data-label="Main health or insurance pressure">Ordinary medical care, delayed baggage, and a simple but expensive hospital visit.</td><td data-label="Coverage to prioritize">Emergency medical, direct billing, trip delay, baggage.</td><td data-label="Best live check">Can the insurer pay a known hospital directly if needed?</td></tr>
<tr><td data-label="Trip style">North scenic route</td><td data-label="Main health or insurance pressure">Road exposure, weather, altitude or long transfers, and limited backup on some stretches.</td><td data-label="Coverage to prioritize">Medical evacuation, road accident cover, interruption, roadside support if offered.</td><td data-label="Best live check">Does the policy allow the exact transport and activity mix?</td></tr>
<tr><td data-label="Trip style">Central coast and heritage route</td><td data-label="Main health or insurance pressure">Heat, scooter use, river or boat time, flooding or rain, and a lot of moving parts in one region.</td><td data-label="Coverage to prioritize">Medical care, evacuation, weather interruption, scooter clause.</td><td data-label="Best live check">Are water and road activities covered the way you plan to use them?</td></tr>
<tr><td data-label="Trip style">Island, bay, or cruise route</td><td data-label="Main health or insurance pressure">Evacuation logistics, weather disruption, and missed sailing or return timing.</td><td data-label="Coverage to prioritize">Evacuation, trip interruption, missed departure, water activity cover.</td><td data-label="Best live check">Does the policy cover island transfer or cruise-related changes?</td></tr>
<tr><td data-label="Trip style">Scooter or motorbike route</td><td data-label="Main health or insurance pressure">Accident risk, roadside injuries, and policy exclusions if the trip is not compliant.</td><td data-label="Coverage to prioritize">Motorbike clause, medical care, liability if offered, evacuation.</td><td data-label="Best live check">Do the rider conditions, license, and engine-size limits match your plan?</td></tr>
<tr><td data-label="Trip style">Family, older traveler, or chronic condition</td><td data-label="Main health or insurance pressure">More moving parts, more medication, and less room for a slow claim process.</td><td data-label="Coverage to prioritize">Pre-existing condition handling, direct billing, assistance hotline, medication support.</td><td data-label="Best live check">Can the insurer handle your condition before you leave?</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-health-insurance-emergency-plan:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-health-insurance-emergency-plan","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-health-insurance-emergency-plan">
<!-- wp:heading -->
<h2 class="wp-block-heading">Five-minute emergency plan</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Build this before departure so a health problem is a process, not a panic.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-health-insurance-emergency-table">
<thead><tr><th>Prepare</th><th>What to store</th><th>Why it matters</th></tr></thead>
<tbody>
<tr><td data-label="Prepare">Insurance file</td><td data-label="What to store">Policy number, insurer hotline, claim app or email, and emergency assistance instructions.</td><td data-label="Why it matters">You want the first call path ready before the problem is happening.</td></tr>
<tr><td data-label="Prepare">Medical snapshot</td><td data-label="What to store">Allergies, regular medication names, prescription photos, key conditions, and blood type if known.</td><td data-label="Why it matters">It speeds up care when language, fatigue, or stress make memory unreliable.</td></tr>
<tr><td data-label="Prepare">Identity backup</td><td data-label="What to store">Passport copy, visa copy, hotel address, next-of-kin contact, and local SIM/WhatsApp backup.</td><td data-label="Why it matters">A hospital or insurer can act faster when the trip details are not trapped on one phone.</td></tr>
<tr><td data-label="Prepare">Payment fallback</td><td data-label="What to store">One separate card and some reserve cash in case the provider wants payment before reimbursement.</td><td data-label="Why it matters">Even with insurance, some expenses may need to be paid first.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-health-insurance-pretravel-checks:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-health-insurance-pretravel-checks","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-health-insurance-pretravel-checks">
<!-- wp:heading -->
<h2 class="wp-block-heading">What to discuss with a travel clinic</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>CDC and Vietnam.travel both push travelers toward advance health planning. Use that advice as a prompt to get professional guidance early rather than trying to self-diagnose from search results.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-health-insurance-clinic-checks"} -->
<ul class="wp-block-list vg-check-list vg-health-insurance-clinic-checks">
<li>Routine vaccines and whether your normal schedule is current.</li>
<li>Typhoid, rabies, Japanese encephalitis, and other destination-specific questions based on the exact route and exposure.</li>
<li>Malaria risk in the rural or remote parts of the trip, not just in Vietnam as a whole.</li>
<li>What to do about mosquito bite prevention and dengue risk during the months and places you will actually visit.</li>
<li>Prescription continuity, medication names, and whether any medicines are restricted, unavailable, or hard to replace on the route.</li>
<li>Pregnancy, children, chronic conditions, and any activity that might change the acceptable risk level.</li>
<li>Whether you should carry a doctor letter, extra copies of prescriptions, or an emergency medication plan.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- vg-health-insurance-policy-exclusions:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-health-insurance-policy-exclusions","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-health-insurance-policy-exclusions">
<!-- wp:heading -->
<h2 class="wp-block-heading">Policy traps to read before you pay</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The cheapest policy is not a bargain if the claim is denied for the exact thing you planned to do.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-health-insurance-exclusions-table">
<thead><tr><th>Common trap</th><th>Why it hurts</th><th>Better move</th></tr></thead>
<tbody>
<tr><td data-label="Common trap">Motorbike riding without reading the clause</td><td data-label="Why it hurts">A lot of policies only cover this under strict conditions or not at all.</td><td data-label="Better move">Check engine size, license, helmet, passenger, and alcohol wording before deciding to ride.</td></tr>
<tr><td data-label="Common trap">Assuming pre-existing conditions are automatically covered</td><td data-label="Why it hurts">Stability periods and disclosure rules can make a claim fail later.</td><td data-label="Better move">Disclose early and buy the add-on if needed.</td></tr>
<tr><td data-label="Common trap">Leaving out adventure or water activities</td><td data-label="Why it hurts">Boat trips, scuba, trekking, canyoning, and similar activities may need specific cover.</td><td data-label="Better move">Match the policy to the activity, not the brochure image.</td></tr>
<tr><td data-label="Common trap">Buying after the risk is already locked</td><td data-label="Why it hurts">Some trip-cancellation protections only work if you buy early enough.</td><td data-label="Better move">Buy before the expensive, non-refundable parts are set.</td></tr>
<tr><td data-label="Common trap">Ignoring deductible and direct-billing wording</td><td data-label="Why it hurts">A low premium can hide a high out-of-pocket bill when you need care.</td><td data-label="Better move">Read the deductible, cashless/direct-billing process, and claim documents now.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-health-insurance-live-checks:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-health-insurance-live-checks","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-health-insurance-live-checks">
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before you buy</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>These are the questions that matter when a policy looks fine at first glance but may not behave well in the real world.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-health-insurance-live-checks-list"} -->
<ul class="wp-block-list vg-check-list vg-health-insurance-live-checks-list">
<li>What is the emergency medical limit, deductible, and direct-billing process?</li>
<li>What is the evacuation limit, and does it cover transfer from remote areas, islands, or the nearest hospital to a higher-level facility?</li>
<li>Are motorbike, scooter, boat, trekking, diving, or other planned activities explicitly covered?</li>
<li>Are pre-existing conditions covered, excluded, or only covered with an add-on?</li>
<li>Is there 24/7 assistance in English and a claim process you can actually use while traveling?</li>
<li>Does the policy cover trip interruption, missed connection, and baggage delay if weather or medical problems change the route?</li>
<li>Will the insurer work with the hospitals you expect to use in Hanoi, Da Nang, Ho Chi Minh City, or any other city on the route?</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- vg-health-insurance-faq:v1 -->
<!-- wp:group {"className":"vg-guide-section vg-health-insurance-faq","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-section vg-health-insurance-faq">
<!-- wp:heading -->
<h2 class="wp-block-heading">Vietnam health and insurance FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-health-insurance-faq">
<details><summary>Do I really need travel insurance for Vietnam?</summary><p>For most international travelers, yes. Even a modest medical issue, road incident, or evacuation can cost far more than the premium. The policy also protects the route logic around delays, interruption, and baggage.</p></details>
<details><summary>When should I buy travel insurance?</summary><p>Before the first non-refundable booking that would be painful to lose. If the route includes cruises, islands, scooters, or remote areas, buy earlier rather than later.</p></details>
<details><summary>Should I get vaccines before Vietnam?</summary><p>Use a travel clinic to decide. CDC and Vietnam.travel both point travelers toward advance planning for routine vaccines and destination-specific questions such as typhoid, rabies, Japanese encephalitis, and malaria risk in some rural areas.</p></details>
<details><summary>Is a city trip safer than a rural trip?</summary><p>Usually yes from a medical-access standpoint, but city trips still need insurance. The risk shape changes, it does not disappear.</p></details>
<details><summary>What if I plan to ride a scooter?</summary><p>Read the policy before you ride. Many claims fail because the rider did not meet the insurer's conditions for motorbike use, even if the trip itself looked normal.</p></details>
<details><summary>Can I rely on public hospitals alone?</summary><p>You should know where the credible hospitals are, but insurance still matters. Major cities have better options than rural routes, and evacuation or direct billing can turn a bad day into a manageable one.</p></details>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> for the full planning order, use <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a> before weather-sensitive or remote routes, confirm <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before road-heavy days, and use <a href="/plan/safety-scams-vietnam/">Safety and Scams in Vietnam</a> to line up the route-risk habits that insurance is meant to backstop. Pair this with <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a>, <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, and <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-health-insurance-hero:v1',
    'concierge verdict' => 'vg-health-insurance-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'at a glance' => 'vg-health-insurance-at-a-glance:v1',
    'photo grid' => 'vg-health-insurance-photo-grid:v1',
    'coverage table' => 'vg-health-insurance-coverage-table:v1',
    'route risk map' => 'vg-health-insurance-route-risk-map:v1',
    'emergency plan' => 'vg-health-insurance-emergency-plan:v1',
    'pretravel checks' => 'vg-health-insurance-pretravel-checks:v1',
    'policy exclusions' => 'vg-health-insurance-policy-exclusions:v1',
    'live checks' => 'vg-health-insurance-live-checks:v1',
    'FAQ' => 'vg-health-insurance-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('Health and Travel Insurance for Vietnam guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Health and Travel Insurance for Vietnam',
    'post_name'      => 'health-travel-insurance-vietnam',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Vietnam health and travel insurance guide for international travelers, with medical care, evacuation, coverage, vaccine planning, exclusions, and live checks.',
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
    vg_ops_fail('Could not publish Health and Travel Insurance for Vietnam guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Health and Travel Insurance for Vietnam guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Health and Travel Insurance for Vietnam: What to Cover');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Vietnam health and travel insurance guide for international travelers, with medical care, evacuation, coverage, vaccines, exclusions, and live checks.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Vietnam travel insurance');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide what Vietnam health and travel insurance must cover before non-refundable booking, with emergency medical, evacuation, route risk, and policy exclusion checks.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 18, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the Health and Travel Insurance for Vietnam guide with an evidence-led concierge verdict, coverage table, route risk map, emergency file, travel clinic checklist, policy exclusion table, live checks, FAQ, source trail, update log, and Plan hub refresh.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "CDC Travelers' Health - Vietnam - https://wwwnc.cdc.gov/travel/destinations/traveler/none/vietnam - checked July 18, 2026\nVietnam.travel - Health and safety - https://vietnam.travel/plan-your-trip/health-safety - checked July 18, 2026\nGOV.UK foreign travel advice - Vietnam health - https://www.gov.uk/foreign-travel-advice/vietnam/health - checked July 18, 2026\nGovernment of Canada Travel Advice - Vietnam - https://travel.gc.ca/destinations/vietnam - checked July 18, 2026\nSmartraveller - Vietnam - https://www.smartraveller.gov.au/destinations/asia/vietnam - checked July 18, 2026\nWikimedia Commons image record - Noi Bai International Airport T2 Waiting Area - https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg - license checked July 18, 2026\nWikimedia Commons image record - City International Hospital Vietnam - https://commons.wikimedia.org/wiki/File:City_International_Hospital_Vietnam.jpg - license checked July 18, 2026\nWikimedia Commons image record - FV hospital - https://commons.wikimedia.org/wiki/File:FV_hospital.JPG - license checked July 18, 2026\nWikimedia Commons image record - Vietnam ambulance - https://commons.wikimedia.org/wiki/File:Vietnam_ambulance.jpg - license checked July 18, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Travel insurance is not a diagnosis or a guarantee; it is the backstop that keeps a medical or transport problem from becoming a trip-ending bill.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Evidence-led insurance framework built around the actual trip risk rather than a generic cheap-policy list.\nConcierge verdict that prioritizes emergency medical care, evacuation, repatriation, and activity-specific coverage before non-refundable booking.\nAt-a-glance decision table that answers when to buy, what to cover, and when to talk to a travel clinic.\nPhoto-led proof grid using licensed airport, hospital, and ambulance imagery with visible credits.\nCoverage table that separates the core policy items from secondary benefits.\nRoute risk map that shows how city-only, north scenic, central coast, island, scooter, family, older-traveler, and chronic-condition routes change the insurance question.\nFive-minute emergency plan for hotline, medical snapshot, identity backup, and payment fallback.\nPre-travel clinic checklist shaped by CDC, Vietnam.travel, and public-travel-advice guidance.\nPolicy exclusion table that catches motorbike, pre-existing condition, activity, timing, and deductible traps before purchase.\nLive-check list for direct billing, evacuation, cashless support, activity clauses, and hospital fit.\nOfficial source trail and visible update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Start here for the full planning order before locking the route.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check season pressure before paying for weather-sensitive or remote plans.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Read before road-heavy, boat, island, or airport-transfer days.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.\nSafety and Scams in Vietnam | /plan/safety-scams-vietnam/ | Pair risk habits with the insurance and emergency plan.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Budget for premiums, evacuation protection, and route buffers.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Keep payment fallback separate from medical and insurance planning.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Keep maps, insurer contacts, and emergency messaging available.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Tight routes make coverage and exclusions more important.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Longer routes create more chances for activity, weather, and transport claims.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero image: Noi Bai International Airport T2 waiting area by Sky 269, CC BY-SA 4.0. Body images: City International Hospital Vietnam by Keronii, CC0; FV hospital by Nguyen Thanh Quang, CC BY-SA 3.0; Vietnam ambulance by Dickelbers, CC BY-SA 4.0.');
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
    vg_ops_fail('Health and Travel Insurance for Vietnam guide was updated but is not published.');
}

vg_ops_refresh_plan_hub();
vg_ops_refresh_inbound_related_routes();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Health and Travel Insurance for Vietnam guide: {$page_id} {$updated_permalink}");

<?php
/**
 * Publish the SIM and eSIM in Vietnam guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-sim-esim-vietnam-guide.php --allow-root
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
    $value = getenv('VG_FORCE_SIM_ESIM_GUIDE_REPUBLISH');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the SIM and eSIM in Vietnam guide.');
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

    if (! vg_ops_published_page_exists('plan/sim-esim-vietnam')) {
        vg_ops_log('Skipped Plan hub refresh: SIM and eSIM guide is not published.');
        return;
    }

    $marker = '<!-- vg-sim-esim-plan-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Protect arrival connectivity</h3><p>The <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam guide</a> helps travelers choose between pre-trip eSIMs, airport SIM counters, local carrier stores, roaming fallback, and offline backup before ride-hailing, maps, and hotel contact details matter.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Plan hub SIM/eSIM note', $block);
    vg_ops_upsert_marked_group($hub, 'Plan hub SIM/eSIM note', $marker, $block);
}

function vg_ops_refresh_cost_guide_connectivity_link(): void
{
    $cost_guide = get_page_by_path('costs/vietnam-travel-cost', OBJECT, 'page');

    if (! $cost_guide instanceof WP_Post || $cost_guide->post_status !== 'publish') {
        vg_ops_log('Skipped Cost guide SIM/eSIM link refresh: guide was not found or is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('plan/sim-esim-vietnam')) {
        vg_ops_log('Skipped Cost guide SIM/eSIM link refresh: SIM/eSIM guide is not published.');
        return;
    }

    $old = '<p>For payment logistics, use <a href="/plan/money-cash-cards-atms/">Money in Vietnam: Cash, Cards and ATMs</a> after you have a budget shape. SIM/eSIM remains a separate logistics page in the roadmap.</p>';
    $new = '<p>For payment logistics, use <a href="/plan/money-cash-cards-atms/">Money in Vietnam: Cash, Cards and ATMs</a> after you have a budget shape. For connectivity costs and arrival friction, use <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a> before comparing roaming, local SIMs, and pre-trip eSIMs.</p>';

    if (str_contains($cost_guide->post_content, $new)) {
        vg_ops_log('Skipped Cost guide SIM/eSIM link refresh: current link already present.');
        return;
    }

    $updated_content = str_replace($old, $new, $cost_guide->post_content, $replacement_count);

    if ($replacement_count < 1) {
        vg_ops_log('Skipped Cost guide SIM/eSIM link refresh: stale placeholder paragraph was not found.');
        return;
    }

    vg_ops_assert_internal_page_links_are_published('Cost guide SIM/eSIM link', $new);

    $result = wp_update_post(
        [
            'ID'           => $cost_guide->ID,
            'post_content' => $updated_content,
        ],
        true
    );

    if (is_wp_error($result)) {
        vg_ops_fail('Could not refresh Cost guide SIM/eSIM link: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Cost guide SIM/eSIM link: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Cost guide SIM/eSIM link: {$cost_guide->ID}");
}

$parent = get_page_by_path('plan', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: plan');
}

$page = get_page_by_path('plan/sim-esim-vietnam', OBJECT, 'page');
$allow_republish = vg_ops_force_republish_enabled();

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('SIM and eSIM in Vietnam guide is not a draft. Set VG_FORCE_SIM_ESIM_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    $permalink = get_permalink($page);
    vg_ops_log("Preflight SIM and eSIM in Vietnam guide: {$page->ID} {$page->post_status} {$permalink}");
} else {
    vg_ops_log('Preflight SIM and eSIM in Vietnam guide: no existing page found; creating a child page under /plan/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/56/Noi_Bai_International_Airport_T2_Waiting_Area.jpg/1920px-Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$airport_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$nano_sim_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/9/98/Nano_SIM_card_and_tray.jpg');
$nano_sim_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Nano_SIM_card_and_tray.jpg');
$esim_phone_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/a/a3/ESim_usage_on_iPhone_13_Pro_Max.jpg');
$esim_phone_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:ESim_usage_on_iPhone_13_Pro_Max.jpg');
$hai_van_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1920px-Vietnam%2C_Hai-Van-Pass.jpg');
$hai_van_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg');

$content = <<<HTML
<!-- vg-sim-esim-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":55,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Noi Bai International Airport Terminal 2 waiting area in Hanoi, used for a Vietnam SIM and eSIM arrival guide" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed connectivity guide - Updated July 17, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">SIM and eSIM in Vietnam</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Most international travelers should treat Vietnam connectivity as an arrival-risk decision, not a hunt for the cheapest data plan. Choose a pre-trip eSIM for first-hour convenience, a local SIM or carrier eSIM for stronger local practicality, and roaming only as a controlled backup.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: the best plan is the one that works before you need maps, ride-hailing, hotel contact, ticket screenshots, or a rural transfer.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$airport_credit_url}" target="_blank" rel="license noopener">Sky 269 / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-sim-esim-concierge-verdict:v1 -->
<!-- wp:group {"className":"vg-concierge-verdict vg-sim-esim-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-sim-esim-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Buy convenience before landing only if you have proven compatibility.</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>The safest Vietnam connectivity plan is layered.</strong> Confirm your phone is unlocked and eSIM-capable before departure, keep offline maps and hotel details available, use airport Wi-Fi only as a bridge, and choose the plan that fits your route: city-only, rural, island, family hotspot, or OTP/local-number needs.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><strong>For simple city trips:</strong> a reputable pre-trip eSIM can be enough if you only need data.</li>
<li><strong>For ride-hailing, local calls, tours, and longer stays:</strong> a properly registered local SIM or carrier eSIM is usually cleaner.</li>
<li><strong>For rural, mountain, island, or motorbike routes:</strong> prioritize network coverage and support over a small price difference.</li>
<li><strong>For families and work travelers:</strong> check hotspot/tethering, data throttling, and backup roaming before relying on one phone.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- vg-sim-esim-editorial-proof:v1 -->
<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-sim-esim-basics:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-sim-esim-basics","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-sim-esim-basics">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Connectivity basics</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Separate the SIM type from the traveler problem.</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A physical SIM, a carrier eSIM, a travel eSIM, and international roaming are different answers. The right choice depends on whether your phone can use the plan, whether you need a Vietnamese number, whether you will leave major cities, and whether you can tolerate activation friction after a long flight.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-sim-esim-basics-table">
<thead>
<tr><th>Connectivity choice</th><th>Best use</th><th>Watch the risk</th><th>Live check</th></tr>
</thead>
<tbody>
<tr><td data-label="Connectivity choice">Pre-trip travel eSIM</td><td data-label="Best use">Landing data, maps, hotel messages, and first ride-hailing attempt.</td><td data-label="Watch the risk">Data-only plans, no Vietnamese number, phone incompatibility, QR activation rules, refund limits.</td><td data-label="Live check">Supported device, unlocked status, activation country, hotspot terms, and exact data cap.</td></tr>
<tr><td data-label="Connectivity choice">Physical tourist SIM</td><td data-label="Best use">Travelers who want a local number, counter support, or a straightforward setup at airport or city stores.</td><td data-label="Watch the risk">Passport registration, unofficial counters, plan confusion, cut-down cards, and top-up rules.</td><td data-label="Live check">Carrier counter, registration requirement, plan inclusions, expiry, and support contact.</td></tr>
<tr><td data-label="Connectivity choice">Carrier eSIM in Vietnam</td><td data-label="Best use">Travelers with compatible phones who want local carrier service without swapping a physical card.</td><td data-label="Watch the risk">Identity verification, QR-code reuse limits, app/store process, device lock, and failed activation.</td><td data-label="Live check">Carrier instructions, passport or ID needs, store/app process, and whether the QR can be reissued.</td></tr>
<tr><td data-label="Connectivity choice">Home roaming</td><td data-label="Best use">Emergency bridge, business continuity, banking OTP, or short trips where cost matters less than reliability.</td><td data-label="Watch the risk">High cost, speed limits, daily caps, no local number, and accidental background data.</td><td data-label="Live check">Your carrier's Vietnam roaming rate, data cap, fair-use rule, and SMS/call pricing.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-sim-esim-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Connectivity proof: where the decision shows up</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>These images are practical cues. Arrival halls, SIM trays, eSIM settings, and remote roads create different connectivity needs.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-sim-esim-photo-grid" aria-label="Vietnam SIM and eSIM planning photography">
<figure class="vg-guide-photo"><img src="{$hero_image}" alt="Noi Bai International Airport waiting area in Hanoi" loading="lazy" decoding="async"><figcaption>Arrival is the first stress test: Wi-Fi, maps, ride-hailing, hotel messages, and ticket screenshots need a backup plan. Image: <a href="{$airport_credit_url}" target="_blank" rel="license noopener">Sky 269 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$nano_sim_image}" alt="Nano SIM card and smartphone SIM tray" loading="lazy" decoding="async"><figcaption>Physical SIMs still matter when a local number, counter support, or easy phone-to-phone transfer matters. Image: <a href="{$nano_sim_credit_url}" target="_blank" rel="license noopener">BwDraco / CC BY-SA 3.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$esim_phone_image}" alt="eSIM settings screen on an iPhone" loading="lazy" decoding="async"><figcaption>eSIM convenience depends on phone support, unlock status, QR rules, and a setup path that still works when you are tired. Image: <a href="{$esim_phone_credit_url}" target="_blank" rel="license noopener">Kramiks / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hai_van_image}" alt="Hai Van Pass road in central Vietnam" loading="lazy" decoding="async"><figcaption>Remote, mountain, island, and road-transfer plans should prioritize coverage and fallback, not just headline gigabytes. Image: <a href="{$hai_van_credit_url}" target="_blank" rel="license noopener">Wolkenkratzer / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-sim-esim-decision-table:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The SIM/eSIM decision table</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this table before buying. The cheapest plan can become expensive if it fails during airport pickup, rural navigation, banking authentication, or a family hotspot day.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-sim-esim-decision-table">
<thead>
<tr><th>Traveler situation</th><th>Best first answer</th><th>Why</th><th>What to live-check</th></tr>
</thead>
<tbody>
<tr><td data-label="Traveler situation">Late arrival, first time in Vietnam, hotel transfer uncertain</td><td data-label="Best first answer">Pre-trip eSIM or roaming bridge plus offline backup.</td><td data-label="Why">You need data before you have the patience to troubleshoot a counter or taxi queue.</td><td data-label="What to live-check">Activation after landing, QR email access, airport Wi-Fi, hotel address, and ride-hailing requirements.</td></tr>
<tr><td data-label="Traveler situation">Longer trip with tours, local calls, or ride-hailing every day</td><td data-label="Best first answer">Registered local SIM or carrier eSIM.</td><td data-label="Why">A local number can reduce friction with drivers, hotels, guides, and delivery or ride apps.</td><td data-label="What to live-check">Passport registration, number validity, plan expiry, top-up process, and store support.</td></tr>
<tr><td data-label="Traveler situation">Unlocked eSIM-capable phone, city-only route</td><td data-label="Best first answer">Travel eSIM can be enough.</td><td data-label="Why">Data-only works when communication can happen through apps and Wi-Fi is common.</td><td data-label="What to live-check">Device compatibility, data cap, hotspot, throttling, and customer support.</td></tr>
<tr><td data-label="Traveler situation">Ha Giang, Phong Nha, islands, border roads, or motorbike days</td><td data-label="Best first answer">Carrier coverage first; keep backup offline.</td><td data-label="Why">Rural signal quality and support matter more than a small price saving.</td><td data-label="What to live-check">Carrier coverage for the exact route, offline maps, hotel contact, and weather/road contingencies.</td></tr>
<tr><td data-label="Traveler situation">Banking OTP or home-number dependence</td><td data-label="Best first answer">Keep home SIM/roaming ability available.</td><td data-label="Why">Some banks, work systems, and two-factor prompts may still depend on the home number.</td><td data-label="What to live-check">SMS roaming cost, Wi-Fi calling, two-factor backup codes, and app-based authentication.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-sim-esim-compatibility-check:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Compatibility check before you buy an eSIM</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Do this before paying for any Vietnam eSIM. A plan can be legitimate and still useless if your device, carrier lock, software version, or activation location does not match the rules.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-sim-esim-compatibility-check"} -->
<ul class="wp-block-list vg-check-list vg-sim-esim-compatibility-check">
<li>Confirm the phone is unlocked or that your home carrier allows a second carrier/eSIM while abroad.</li>
<li>Confirm the exact model supports eSIM in the country or region where it was sold; do not rely on the model family alone.</li>
<li>Update iOS or Android before departure, then avoid major OS changes on travel day unless support tells you to do so.</li>
<li>Check whether the plan activates only after arrival in Vietnam or can be installed before departure.</li>
<li>Save the QR code, order number, support contact, and installation steps offline before the flight.</li>
<li>Know whether deleting the eSIM requires a new QR code, store visit, or support reissue.</li>
</ul>
<!-- /wp:list -->

<!-- vg-sim-esim-arrival-setup:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Arrival setup: what to do before the first ride</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-sim-esim-arrival-setup">
<thead>
<tr><th>Moment</th><th>Do this</th><th>Why it protects the trip</th></tr>
</thead>
<tbody>
<tr><td data-label="Moment">Before departure</td><td data-label="Do this">Download offline maps, hotel address, visa approval, first-night booking, and eSIM/SIM instructions.</td><td data-label="Why it protects the trip">You can still move if the QR email, Wi-Fi login, or carrier setup stalls.</td></tr>
<tr><td data-label="Moment">On landing</td><td data-label="Do this">Use airport Wi-Fi as a bridge only; keep passport, phone battery, and payment method ready.</td><td data-label="Why it protects the trip">Arrival counters, apps, and ride-hailing can require verification steps.</td></tr>
<tr><td data-label="Moment">At a SIM counter or store</td><td data-label="Do this">Ask what is included: data, calls, local number, hotspot, validity, top-up, and support.</td><td data-label="Why it protects the trip">Tourist plan names and reseller explanations can blur the real limits.</td></tr>
<tr><td data-label="Moment">After activation</td><td data-label="Do this">Test mobile data, calls if included, hotspot if needed, map routing, ride-hailing, and hotel messaging.</td><td data-label="Why it protects the trip">Find failures while support is still nearby, not on the roadside.</td></tr>
<tr><td data-label="Moment">At the first hotel</td><td data-label="Do this">Store the QR/support details, label SIM lines, and disable expensive background roaming.</td><td data-label="Why it protects the trip">You reduce accidental charges and preserve the path to repair or reinstall.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-sim-esim-coverage-matrix:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Coverage and route fit matter more than headline data</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam is highly connected, but travel routes are not equal. Cities, beaches, mountain passes, islands, national parks, and border roads create different coverage risks. Use official and carrier sources for the current network picture, then check your exact route when connectivity is safety-critical or work-critical.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-sim-esim-coverage-matrix">
<thead>
<tr><th>Route type</th><th>Connectivity priority</th><th>Better plan</th><th>Backup habit</th></tr>
</thead>
<tbody>
<tr><td data-label="Route type">Hanoi, Ho Chi Minh City, Da Nang, Hoi An</td><td data-label="Connectivity priority">Convenience and activation speed.</td><td data-label="Better plan">Travel eSIM, carrier eSIM, physical SIM, or roaming all can work if priced sensibly.</td><td data-label="Backup habit">Keep hotel addresses offline and test ride-hailing before leaving Wi-Fi.</td></tr>
<tr><td data-label="Route type">Airport transfers and first night</td><td data-label="Connectivity priority">Working data within the first hour.</td><td data-label="Better plan">Pre-trip eSIM/roaming bridge or an official airport counter with clear registration.</td><td data-label="Backup habit">Save pickup instructions and have cash/card backup.</td></tr>
<tr><td data-label="Route type">Mountain roads, motorbike loops, caves, remote homestays</td><td data-label="Connectivity priority">Coverage and offline resilience.</td><td data-label="Better plan">Carrier/plan chosen for route coverage rather than only price.</td><td data-label="Backup habit">Download offline maps, share route, and keep accommodation contacts saved.</td></tr>
<tr><td data-label="Route type">Islands, ferries, bay cruises, boats</td><td data-label="Connectivity priority">Expectation management.</td><td data-label="Better plan">Assume gaps are possible and confirm with operator/hotel.</td><td data-label="Backup habit">Keep tickets, pickup time, and emergency contacts offline.</td></tr>
<tr><td data-label="Route type">Work, family, or medical-contact travel</td><td data-label="Connectivity priority">Redundancy.</td><td data-label="Better plan">Two independent options: local data plus home roaming or a second device.</td><td data-label="Backup habit">Keep Wi-Fi calling/app authentication ready and test hotspot before remote days.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-sim-esim-local-number:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Do you need a Vietnamese phone number?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A data-only eSIM can be excellent for maps, messaging apps, email, and web searches. It may be weaker when a driver, guide, hotel, bank, delivery app, or tour operator wants a local number, SMS, or voice call. This is the main reason not to treat eSIM as automatically better than a registered local SIM.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-sim-esim-local-number"} -->
<ul class="wp-block-list vg-check-list vg-sim-esim-local-number">
<li>Choose data-only if you communicate mainly through WhatsApp, iMessage, email, hotel apps, and maps.</li>
<li>Choose a local number if ride-hailing, local calls, guide coordination, or long-stay logistics matter.</li>
<li>Keep home-number access if banking OTP, work authentication, or emergency contacts depend on your original SIM.</li>
<li>Ask whether the plan includes domestic calls/SMS, not just data.</li>
<li>Test the number immediately if a tour, transfer, or hotel will contact you by phone.</li>
</ul>
<!-- /wp:list -->

<!-- vg-sim-esim-hotspot-data:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Hotspot, data caps, and family travel</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Families and couples often underestimate hotspot rules. A large-sounding data plan can disappoint if tethering is blocked, daily high-speed data is throttled, or one phone becomes the only working map for everyone.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-sim-esim-hotspot-data">
<thead>
<tr><th>Need</th><th>Question to ask before buying</th><th>Better practice</th></tr>
</thead>
<tbody>
<tr><td data-label="Need">Family maps and messaging</td><td data-label="Question to ask before buying">Does the plan allow hotspot/tethering, and at what speed after the daily cap?</td><td data-label="Better practice">Give at least two adults independent data or offline maps.</td></tr>
<tr><td data-label="Need">Remote work or calls</td><td data-label="Question to ask before buying">Is the plan stable enough for video calls, and what happens after fair-use limits?</td><td data-label="Better practice">Use hotel Wi-Fi for heavy work and mobile data as backup, not the only connection.</td></tr>
<tr><td data-label="Need">Navigation day</td><td data-label="Question to ask before buying">Will the route have coverage and battery access?</td><td data-label="Better practice">Download offline maps and carry a power bank.</td></tr>
<tr><td data-label="Need">Kids' devices</td><td data-label="Question to ask before buying">Will streaming burn the data cap before transport or hotel check-in?</td><td data-label="Better practice">Reserve mobile data for logistics and use Wi-Fi for entertainment downloads.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-sim-esim-prebook-flex:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">What to prebook and what to keep flexible</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-sim-esim-prebook-flex">
<thead>
<tr><th>Decision</th><th>Prebook when</th><th>Keep flexible when</th><th>Record before travel</th></tr>
</thead>
<tbody>
<tr><td data-label="Decision">Travel eSIM</td><td data-label="Prebook when">You need immediate landing data, arrive late, or dislike airport-counter friction.</td><td data-label="Keep flexible when">You are unsure whether your phone is unlocked/eSIM-capable.</td><td data-label="Record before travel">QR code, install steps, support email, activation country, refund rule.</td></tr>
<tr><td data-label="Decision">Airport SIM</td><td data-label="Prebook when">The provider has an official pickup process and your flight timing is simple.</td><td data-label="Keep flexible when">You can buy at a reputable counter after comparing inclusions.</td><td data-label="Record before travel">Counter location, passport requirement, plan name, operating hours.</td></tr>
<tr><td data-label="Decision">City carrier store</td><td data-label="Prebook when">Rarely; better treated as a support option after arrival.</td><td data-label="Keep flexible when">You can use Wi-Fi or temporary data for the first day.</td><td data-label="Record before travel">Nearby official store address and opening hours.</td></tr>
<tr><td data-label="Decision">Roaming package</td><td data-label="Prebook when">OTP, work continuity, or high-stakes arrival contact depends on your home number.</td><td data-label="Keep flexible when">Local data will cover ordinary travel needs.</td><td data-label="Record before travel">Daily cap, SMS cost, call cost, activation method, cancellation rule.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-sim-esim-failure-modes:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Failure modes to plan around</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Most connectivity failures are predictable. Build your plan so none of them can trap you at the airport or on a transfer day.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-sim-esim-failure-modes">
<thead>
<tr><th>Failure</th><th>Why it happens</th><th>Prevention</th><th>Fallback</th></tr>
</thead>
<tbody>
<tr><td data-label="Failure">eSIM will not activate</td><td data-label="Why it happens">Locked phone, unsupported model, weak Wi-Fi, activation location rule, or QR already used.</td><td data-label="Prevention">Check device support and install rules before departure.</td><td data-label="Fallback">Airport Wi-Fi, roaming bridge, carrier support, or physical SIM counter.</td></tr>
<tr><td data-label="Failure">No local number</td><td data-label="Why it happens">Many travel eSIMs are data-only.</td><td data-label="Prevention">Buy a plan with number/calls if drivers or guides need it.</td><td data-label="Fallback">Use app messaging, hotel phone, or local SIM.</td></tr>
<tr><td data-label="Failure">Data works in cities but not on the route</td><td data-label="Why it happens">Coverage differences, terrain, island/boat routes, or reseller network choice.</td><td data-label="Prevention">Prioritize carrier coverage and offline maps for rural routes.</td><td data-label="Fallback">Operator/hotel contact, offline navigation, and a second network if critical.</td></tr>
<tr><td data-label="Failure">Roaming bill shock</td><td data-label="Why it happens">Background data, daily-pass auto-renewal, or calls/SMS outside bundle.</td><td data-label="Prevention">Set data line, disable background roaming, and read the home-carrier rule.</td><td data-label="Fallback">Use local data and keep home SIM only for OTP or urgent calls.</td></tr>
<tr><td data-label="Failure">Lost QR or deleted eSIM</td><td data-label="Why it happens">Factory reset, device change, or accidental deletion.</td><td data-label="Prevention">Save support info and understand reissue rules.</td><td data-label="Fallback">Carrier store, support reissue, or physical SIM replacement.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-sim-esim-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before buying a Vietnam SIM or eSIM</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-sim-esim-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-sim-esim-live-checks">
<li>Phone unlocked status and exact eSIM support for the device model and sales region.</li>
<li>Whether the plan is data-only or includes a Vietnamese number, calls, and SMS.</li>
<li>Activation timing: before departure, after landing, or only inside Vietnam.</li>
<li>Passport, portrait photo, or identity-registration steps for carrier SIM/eSIM purchases.</li>
<li>Coverage and support for the exact route, especially mountains, islands, ferries, caves, and remote homestays.</li>
<li>Hotspot/tethering terms, fair-use policy, high-speed data cap, and throttling behavior.</li>
<li>QR-code reuse, refund, reinstall, deletion, and phone-change rules.</li>
<li>Home carrier roaming rates for OTP, emergency calls, and accidental background data.</li>
</ul>
<!-- /wp:list -->

<!-- vg-sim-esim-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">SIM and eSIM in Vietnam FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-sim-esim-faq">
<details><summary>Should I get a SIM or eSIM for Vietnam?</summary><p>Use an eSIM if your phone is unlocked, compatible, and you mostly need data. Use a local SIM or carrier eSIM if you want a Vietnamese number, counter support, or a longer trip with more local coordination.</p></details>
<details><summary>Can I buy a SIM card at Vietnam airports?</summary><p>Vietnam.travel says prepaid SIM cards can be bought on arrival at major airports and from many shops across the country, with passport registration required. Treat airport counters as convenient, then verify plan inclusions before paying.</p></details>
<details><summary>Do I need a passport to buy a SIM in Vietnam?</summary><p>Yes, plan on showing your passport for local SIM registration. Some official carrier flows may also ask for portrait or identity verification. Do not hand documents to unofficial sellers unless you are comfortable with the registration process.</p></details>
<details><summary>Will a data-only eSIM work for Grab or ride-hailing?</summary><p>Often, but not always smoothly. Data lets you use apps, but a local number can reduce friction if a driver, hotel, or guide needs to call or verify you. Keep hotel pickup details offline as backup.</p></details>
<details><summary>Which Vietnam carrier is best?</summary><p>Do not choose from a generic ranking alone. Viettel, VinaPhone, and MobiFone are the major operators named by Vietnam's official tourism site. For cities, several options may work; for rural, mountain, island, or work-critical routes, check current coverage and support for the exact route.</p></details>
<details><summary>Can I keep my home SIM active?</summary><p>Yes, and many travelers should keep it available for banking OTP, emergency contact, or work messages. Just check roaming rates and set the correct data line so the home SIM does not create surprise charges.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> for the full planning order, confirm <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a> before locked bookings, use <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a> for cash and payment friction, and check <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before relying on phone-based pickup, maps, or rural transfer coordination.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-sim-esim-hero:v1',
    'concierge verdict' => 'vg-sim-esim-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'connectivity basics' => 'vg-sim-esim-basics:v1',
    'photo grid' => 'vg-sim-esim-photo-grid:v1',
    'decision table' => 'vg-sim-esim-decision-table:v1',
    'compatibility check' => 'vg-sim-esim-compatibility-check:v1',
    'arrival setup' => 'vg-sim-esim-arrival-setup:v1',
    'coverage matrix' => 'vg-sim-esim-coverage-matrix:v1',
    'local number' => 'vg-sim-esim-local-number:v1',
    'hotspot data' => 'vg-sim-esim-hotspot-data:v1',
    'prebook flexible' => 'vg-sim-esim-prebook-flex:v1',
    'failure modes' => 'vg-sim-esim-failure-modes:v1',
    'live checks' => 'vg-sim-esim-live-checks:v1',
    'FAQ' => 'vg-sim-esim-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('SIM and eSIM in Vietnam guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'SIM and eSIM in Vietnam',
    'post_name'      => 'sim-esim-vietnam',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Vietnam SIM and eSIM guide for international travelers, including pre-trip eSIMs, physical SIMs, local numbers, coverage, hotspot, arrival setup, and live checks.',
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
    vg_ops_fail('Could not publish SIM and eSIM in Vietnam guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish SIM and eSIM in Vietnam guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'SIM and eSIM in Vietnam: Traveler Connectivity Guide');
update_post_meta($page_id, 'rank_math_description', 'Choose SIM or eSIM in Vietnam with device checks, local number needs, airport setup, coverage, hotspot, roaming backup, failure modes, and live checks.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'SIM and eSIM in Vietnam');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide whether to use a pre-trip eSIM, physical local SIM, carrier eSIM, or roaming fallback for a Vietnam trip based on arrival risk, phone compatibility, local-number needs, route coverage, and support.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 17, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Initial SIM and eSIM in Vietnam guide publish with decision-first verdict, connectivity basics, photo proof, SIM/eSIM decision table, compatibility checklist, arrival setup, coverage matrix, local-number guidance, hotspot/data planning, prebook-versus-flexible guidance, failure modes, FAQ, source trail, and update log.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Plan your trip, SIM cards and helpful numbers section - https://vietnam.travel/plan-your-trip - checked July 17, 2026\nVietnam.travel - A traveller's guide to Vietnam's airports, ride-hailing and Vietnamese SIM context - https://vietnam.travel/things-to-do/travellers-guide-vietnams-airports - checked July 17, 2026\nMinistry of Information and Communications - Vietnam has 110.5 million mobile internet subscriptions - https://beta-en.mic.gov.vn/vietnam-has-1105-million-mobile-internet-subscriptions-197260506151607177.htm - checked July 17, 2026\nViettel Telecom tourist SIM/Card official flow - https://tourist.viettel.vn/ - checked July 17, 2026\nMobiFone - eSIM information, device support, registration and FAQ - https://www.mobifone.vn/esim - checked July 17, 2026\nApple Support - If you can't set up an eSIM on your iPhone - https://support.apple.com/en-us/102478 - checked July 17, 2026\nApple Support - Using Dual SIM with an eSIM - https://support.apple.com/en-us/109317 - checked July 17, 2026\nGoogle Pixel Help - How to use dual SIMs on your Google Pixel phone - https://support.google.com/pixelphone/answer/9449293 - checked July 17, 2026\nWikimedia Commons image record - Noi Bai International Airport T2 Waiting Area - https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg - license checked July 17, 2026\nWikimedia Commons image record - Nano SIM card and tray - https://commons.wikimedia.org/wiki/File:Nano_SIM_card_and_tray.jpg - license checked July 17, 2026\nWikimedia Commons image record - ESim usage on iPhone 13 Pro Max - https://commons.wikimedia.org/wiki/File:ESim_usage_on_iPhone_13_Pro_Max.jpg - license checked July 17, 2026\nWikimedia Commons image record - Vietnam, Hai-Van-Pass - https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg - license checked July 17, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'The best Vietnam connectivity plan is the one that works before maps, ride-hailing, hotel contact, ticket screenshots, or a rural transfer become urgent.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Decision-first SIM/eSIM framework rather than a stale affiliate-style provider ranking.\nConcierge verdict that separates pre-trip eSIM convenience, local SIM practicality, carrier eSIM support, and home roaming fallback.\nCompatibility checklist for unlocked phone status, device model, software, activation location, QR retention, and deletion risk.\nPhoto-led proof grid using licensed airport, SIM tray, eSIM settings, and route imagery with visible credits.\nDecision table for late arrivals, long stays, city-only routes, rural routes, and OTP/home-number dependency.\nArrival setup table that protects the first ride, hotel contact, and troubleshooting window.\nCoverage matrix that distinguishes cities, airport transfers, mountains, islands, boats, and work-critical redundancy.\nLocal-number, hotspot/data, prebook/flexible, failure-mode, live-check, and FAQ modules focused on original traveler judgment.\nOfficial tourism, ministry, carrier, Apple, Google, and image-license source checks.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Use this first if you still need the full planning order.\nVietnam E-Visa Guide | /plan/vietnam-evisa/ | Confirm entry before paying for arrival logistics.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Plan cash/card friction before airport counters, taxi backup, and first-night spending.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Check phone-dependent pickup, maps, transfers, and rural route coordination.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Put SIM, eSIM, roaming, hotspot, and backup data inside the travel budget.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and arrival image: Noi Bai International Airport T2 waiting area by Sky 269, CC BY-SA 4.0. Body images: Nano SIM card and tray by BwDraco, CC BY-SA 3.0; eSIM usage on iPhone 13 Pro Max by Kramiks, CC BY-SA 4.0; Hai Van Pass by Wolkenkratzer, CC BY-SA 4.0.');
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
        vg_ops_fail("Required metadata missing after publish: {$meta_key}");
    }
}

if (get_post_status($page_id) !== 'publish') {
    vg_ops_fail('SIM and eSIM in Vietnam guide was updated but is not published.');
}

vg_ops_refresh_plan_hub();
vg_ops_refresh_cost_guide_connectivity_link();

clean_post_cache($page_id);

$updated_permalink = get_permalink($page_id);

vg_ops_log("Published SIM and eSIM in Vietnam guide: {$page_id} {$updated_permalink}");

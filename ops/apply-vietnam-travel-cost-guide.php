<?php
/**
 * Publish the Vietnam Travel Cost guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-vietnam-travel-cost-guide.php --allow-root
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
    $value = getenv('VG_FORCE_COST_GUIDE_REPUBLISH');

    if (! is_string($value)) {
        return false;
    }

    return trim($value) === '1';
}

function vg_ops_refresh_costs_hub(): void
{
    $costs_page = get_page_by_path('costs', OBJECT, 'page');

    if (! $costs_page instanceof WP_Post) {
        vg_ops_log('Skipped Costs hub refresh: costs page was not found.');
        return;
    }

    if ($costs_page->post_status !== 'publish') {
        vg_ops_log('Skipped Costs hub refresh: costs page is not published.');
        return;
    }

    $old_note = '<div class="wp-block-group vg-hub-note"><h3>Why exact numbers wait</h3><p>Public price ranges should be dated and reviewed often because exchange rates, hotel demand, and transport pricing move. Until the dedicated cost guide is ready, this hub focuses on the budget structure that stays useful.</p></div>';
    $new_note = '<div class="wp-block-group vg-hub-note"><h3>Use the dated cost guide</h3><p>For current ranges, start with the <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost guide</a>, then return here to structure hotels, transfers, tours, and buffers.</p></div>';

    if (str_contains($costs_page->post_content, $new_note)) {
        vg_ops_log('Skipped Costs hub refresh: current guide note already present.');
        return;
    }

    $updated_content = str_replace($old_note, $new_note, $costs_page->post_content, $replacement_count);

    if ($replacement_count < 1) {
        vg_ops_log('Skipped Costs hub refresh: legacy guide-waiting note was not found.');
        return;
    }

    $result = wp_update_post(
        [
            'ID'           => $costs_page->ID,
            'post_content' => $updated_content,
        ],
        true
    );

    if (is_wp_error($result)) {
        vg_ops_fail('Could not refresh Costs hub note: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Costs hub note: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Costs hub guide note: {$costs_page->ID}");
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

$page = get_page_by_path('costs/vietnam-travel-cost', OBJECT, 'page');

if (! $page instanceof WP_Post) {
    vg_ops_fail('Draft page not found: costs/vietnam-travel-cost');
}

$allow_republish = vg_ops_force_republish_enabled();
$permalink = get_permalink($page);

if ($page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('Vietnam Travel Cost guide is not a draft. Set VG_FORCE_COST_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

vg_ops_log("Preflight Vietnam Travel Cost guide: {$page->ID} {$page->post_status} {$permalink}");

$guide_hero_image = esc_url(set_url_scheme(get_stylesheet_directory_uri(), 'https') . '/assets/images/ha-long-bay-vietnam-hero.jpg');
$hanoi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg');
$hanoi_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg');
$trang_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1920px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');
$trang_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg');
$hoi_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hoi_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg');
$hai_van_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1920px-Vietnam%2C_Hai-Van-Pass.jpg');
$hai_van_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg');
$hcmc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg');
$hcmc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg');
$mekong_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1920px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg');
$mekong_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg');
$rail_station_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/95/Hanoi_Railway_Station_20130725.jpg/1920px-Hanoi_Railway_Station_20130725.jpg');
$rail_station_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi_Railway_Station_20130725.jpg');
$airport_arrival_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/56/Noi_Bai_International_Airport_T2_Waiting_Area.jpg/1920px-Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$airport_arrival_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$atm_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f1/ATM_VPBank%2C_Li%E1%BB%85u_Giai%2C_H%C3%A0_N%E1%BB%99i_001.JPG/1920px-ATM_VPBank%2C_Li%E1%BB%85u_Giai%2C_H%C3%A0_N%E1%BB%99i_001.JPG');
$atm_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:ATM_VPBank,_Li%E1%BB%85u_Giai,_H%C3%A0_N%E1%BB%99i_001.JPG');

$content = <<<HTML
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$guide_hero_image}","dimRatio":50,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Cruise boats among limestone karsts in Ha Long Bay, Vietnam, used for a Vietnam travel cost guide hero image" src="{$guide_hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed guide - Updated July 16, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Vietnam Travel Cost</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Vietnam travel cost for international visitors is best planned as a land-only budget, excluding international flights. As of July 14, 2026, use USD 30-45/day for a basic backpacker trip, USD 70-120/day for comfortable mid-range travel, and USD 180-350+/day for premium or private arrangements. Route, comfort, and season decide the final budget.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Ranges assume one person, shared rooms where relevant, and exchange-rate math rounded around VND 26,000 for planning.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Most first-time visitors should budget the route before the daily number.</strong> A classic 10-day land-only trip can be planned around USD 550-850 basic, USD 1,100-1,900 comfortable, or USD 2,600-5,500+ premium/private, excluding international flights.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><strong>Best value:</strong> fewer bases, trains or sensible domestic flights, simple food, and hotels booked early.</li>
<li><strong>Biggest movers:</strong> solo rooms, domestic flights, cruises, private guides, beach resorts, and holiday peaks.</li>
<li><strong>Verify before booking:</strong> hotels, tours, rail seats, airport transfers, and exchange rates for your exact dates.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- wp:html -->
<div class="vg-guide-photo-feature vg-cost-photo-feature" aria-label="Vietnam cost planning visual">
<figure><img src="{$hai_van_image}" alt="Coastal and mountain road view from Hai Van Pass in central Vietnam" loading="lazy" decoding="async"><figcaption>Route cost is not abstract: every scenic handoff still needs transport, timing, luggage tolerance, and weather judgment. Image: <a href="{$hai_van_credit_url}" target="_blank" rel="license noopener">Wolkenkratzer / CC BY-SA 4.0</a>.</figcaption></figure>
<div class="vg-guide-photo-copy">
<p class="vg-kicker">Cost planning lens</p>
<h2>Price the trip as a sequence of decisions.</h2>
<p>The cleanest Vietnam budget is built from named choices: where you sleep, how often you move, which transfers protect energy, and which one or two experiences deserve a higher spend. A daily average is useful only after those choices are visible.</p>
</div>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Dated budget ranges</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>These ranges are per person, land-only, and exclude international flights. They are rounded for planning, not a quote or conversion promise. Shared rooms are assumed where relevant; travelers taking solo rooms should lift the hotel line before comparing totals.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cost-budget-ranges">
<thead>
<tr><th>Travel style</th><th>Daily land-only budget</th><th>10-day land-only budget</th><th>What it usually means</th><th>What can push it higher</th></tr>
</thead>
<tbody>
<tr><td data-label="Travel style">Basic/backpacker</td><td data-label="Daily land-only budget">USD 30-45/day</td><td data-label="10-day land-only budget">USD 550-850</td><td data-label="What it usually means">Hostels or simple shared rooms, local meals, buses or trains, selective paid sights.</td><td data-label="What can push it higher">Solo rooms, last-minute transport, paid tours, beach stays, holiday demand.</td></tr>
<tr><td data-label="Travel style">Comfortable/mid-range</td><td data-label="Daily land-only budget">USD 70-120/day</td><td data-label="10-day land-only budget">USD 1,100-1,900</td><td data-label="What it usually means">Private room in practical hotels, better transfers, some tours, more relaxed city choices.</td><td data-label="What can push it higher">Domestic flights, better hotel locations, cruises, food tours, peak-season rooms.</td></tr>
<tr><td data-label="Travel style">Premium/private</td><td data-label="Daily land-only budget">USD 180-350+/day</td><td data-label="10-day land-only budget">USD 2,600-5,500+</td><td data-label="What it usually means">Boutique or luxury hotels, private transfers, private guides, curated cruises, slower pace.</td><td data-label="What can push it higher">Beach resorts, holiday peaks, suites, premium cruises, private regional touring.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Sample budgets by trip length</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use these as planning scenarios, not fixed quotes. Shorter trips often cost more per day because airport transfers, domestic flights, and one-time tours are spread across fewer nights. Longer trips can lower the daily average only when the route slows down.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cost-scenario-budgets">
<thead>
<tr><th>Travel style</th><th>7-day land-only planning range</th><th>10-day land-only planning range</th><th>14-day land-only planning range</th><th>Editorial note</th></tr>
</thead>
<tbody>
<tr><td data-label="Travel style">Basic/backpacker</td><td data-label="7-day land-only planning range">USD 420-700</td><td data-label="10-day land-only planning range">USD 550-850</td><td data-label="14-day land-only planning range">USD 800-1,350</td><td data-label="Editorial note">Works best with fewer bases, simple rooms, buses or trains, and only one higher-cost highlight.</td></tr>
<tr><td data-label="Travel style">Comfortable/mid-range</td><td data-label="7-day land-only planning range">USD 800-1,400</td><td data-label="10-day land-only planning range">USD 1,100-1,900</td><td data-label="14-day land-only planning range">USD 1,600-2,900</td><td data-label="Editorial note">This is the sweet spot for most first-time visitors who want private rooms, better routing, and some guided days.</td></tr>
<tr><td data-label="Travel style">Premium/private</td><td data-label="7-day land-only planning range">USD 1,900-4,000+</td><td data-label="10-day land-only planning range">USD 2,600-5,500+</td><td data-label="14-day land-only planning range">USD 3,700-8,000+</td><td data-label="Editorial note">Private support, premium cruises, and resort nights should be priced as named line items, not blended into a daily average.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Why the math is not just daily rate times nights: arrival/departure transfers, visa, intercity moves, cruises, and one-off tours can make a short trip look expensive and a slower trip look better value.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Line items to check before booking</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table">
<thead>
<tr><th>Cost line</th><th>Planning note checked July 14, 2026</th><th>VietnamGuide guidance</th></tr>
</thead>
<tbody>
<tr><td data-label="Cost line">E-visa</td><td data-label="Planning note checked July 14, 2026">Official fee is USD 25 single-entry or USD 50 multiple-entry.</td><td data-label="VietnamGuide guidance">Use the official portal, then confirm eligibility, passport details, and processing time before booking tight arrivals.</td></tr>
<tr><td data-label="Cost line">Exchange rate</td><td data-label="Planning note checked July 14, 2026">Vietcombank XML data returned DateTime 7/14/2026 6:07:17 PM with USD buy VND 26,040.00, transfer VND 26,070.00, and sell VND 26,450.00.</td><td data-label="VietnamGuide guidance">For this guide, think roughly USD 1 = VND 26,000-26,500, rounded around VND 26,000 for planning. Use live rates when paying hotels, withdrawals, or large cash expenses.</td></tr>
<tr><td data-label="Cost line">Rail fares</td><td data-label="Planning note checked July 14, 2026">Official rail fare lookup was checked via dsvn.vn.</td><td data-label="VietnamGuide guidance">Train cost depends on route, seat or berth, train number, and availability. Re-check before choosing rail over a flight.</td></tr>
<tr><td data-label="Cost line">Hanoi Noi Bai arrival</td><td data-label="Planning note checked July 14, 2026">Airport public transport examples include buses and airport services into Hanoi.</td><td data-label="VietnamGuide guidance">Use public transport for low-budget daylight arrivals; choose taxi or ride-hail for late landings, luggage, or families.</td></tr>
<tr><td data-label="Cost line">Ho Chi Minh City airport arrival</td><td data-label="Planning note checked July 14, 2026">Tan Son Nhat examples include airport bus, taxi, and ride-hail options into the city.</td><td data-label="VietnamGuide guidance">Check pickup rules and current fares on arrival, especially when landing at busy hours.</td></tr>
<tr><td data-label="Cost line">Hotels and tours</td><td data-label="Planning note checked July 14, 2026">Hotel and tour prices are volatile and require live checks by date.</td><td data-label="VietnamGuide guidance">Compare refundable rates, cancellation terms, holiday peaks, and whether a tour replaces a transfer cost.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Practical cost visuals to price before arrival</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>These images are practical prompts, not decoration. Use them to decide which transport and cash lines need live checks before you pay for rooms, trains, flights, tours, or arrival transfers.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-cost-practical-visuals" aria-label="Vietnam practical cost checks">
<figure class="vg-guide-photo"><img src="{$rail_station_image}" alt="Hanoi Railway Station exterior in Vietnam" loading="lazy" decoding="async"><figcaption>Rail is not one price: station transfer, seat or berth type, luggage, and departure hour decide whether the train is actually cheaper than flying. Image: <a href="{$rail_station_credit_url}" target="_blank" rel="license noopener">Alancrh / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$airport_arrival_image}" alt="Noi Bai International Airport Terminal 2 waiting area in Hanoi" loading="lazy" decoding="async"><figcaption>Arrival cost changes after dark or with luggage; decide between bus, taxi or ride-hail, and prebooked transfer before landing. Image: <a href="{$airport_arrival_credit_url}" target="_blank" rel="license noopener">Sky 269 / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$atm_image}" alt="VPBank ATM on Lieu Giai street in Hanoi, Vietnam" loading="lazy" decoding="async"><figcaption>Cash planning matters because small vendors and day trips may need VND; separate ATM cash from hotel, card, and tour balances. Image: <a href="{$atm_credit_url}" target="_blank" rel="license noopener">Phan Minh Tuan / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Choose your budget by route, not just by day</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A Vietnam budget becomes clearer when the route is named. The same daily comfort level can behave very differently on a compact northern loop, a north-to-south route, or a beach extension with resort nights.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cost-route-archetypes">
<thead>
<tr><th>Route archetype</th><th>Main cost driver</th><th>When it saves money</th><th>When it gets expensive</th><th>VietnamGuide verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Route archetype">Hanoi, Ninh Binh, Ha Long or Lan Ha</td><td data-label="Main cost driver">Bay cruise quality, transfer style, and whether you add a private car.</td><td data-label="When it saves money">You keep one Hanoi base and avoid extra domestic flights.</td><td data-label="When it gets expensive">Premium cruises, private transfers, and single cabins lift the total quickly.</td><td data-label="VietnamGuide verdict">Strong value for first-timers if the cruise is treated as a separate line item.</td></tr>
<tr><td data-label="Route archetype">Classic north-to-south</td><td data-label="Main cost driver">Domestic flights, airport transfers, and too many one-night stops.</td><td data-label="When it saves money">You choose three or four bases and avoid backtracking.</td><td data-label="When it gets expensive">You add Sapa, Ha Long, Hoi An, Mekong, and beach nights into one short trip.</td><td data-label="VietnamGuide verdict">Worth paying for smarter routing before paying for nicer rooms.</td></tr>
<tr><td data-label="Route archetype">Central Vietnam slow trip</td><td data-label="Main cost driver">Hotel location, food experiences, day trips, and weather flexibility.</td><td data-label="When it saves money">Da Nang, Hoi An, and Hue are linked in a compact route with fewer flights.</td><td data-label="When it gets expensive">You use private cars every day or stay in high-demand heritage/beach locations.</td><td data-label="VietnamGuide verdict">Often the cleanest mid-range value if the season fits.</td></tr>
<tr><td data-label="Route archetype">Beach or island add-on</td><td data-label="Main cost driver">Resort nights, flight timing, airport transfers, and weather risk.</td><td data-label="When it saves money">You add enough nights to justify the transfer and choose refundable rooms.</td><td data-label="When it gets expensive">You fly in for one or two resort nights during a peak window.</td><td data-label="VietnamGuide verdict">Budget it as a premium module, not a normal city day.</td></tr>
<tr><td data-label="Route archetype">Private family route</td><td data-label="Main cost driver">Room configuration, private vehicles, guide days, and child pacing.</td><td data-label="When it saves money">Transfers and guides are shared across the group.</td><td data-label="When it gets expensive">You need connecting rooms, flexible cancellations, and last-minute vehicle changes.</td><td data-label="VietnamGuide verdict">Private support can be better value for families than forcing a low daily budget.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Visual route-cost cues</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>These are not pretty extras. They show where the budget usually bends: arrival city pressure, countryside add-ons, cruise and transfer intensity, central Vietnam pacing, southern city balance, and river or island add-ons that deserve their own line item.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-cost-photo-grid" aria-label="Vietnam cost route photography">
<figure class="vg-guide-photo"><img src="{$hanoi_image}" alt="Hoan Kiem Lake in Hanoi, Vietnam" loading="lazy" decoding="async"><figcaption>Hanoi often anchors the trip before any route cost starts to spread. Image: <a href="{$hanoi_credit_url}" target="_blank" rel="license noopener">Alex 69200 vx / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$trang_an_image}" alt="Boats and limestone scenery at Trang An in Ninh Binh, Vietnam" loading="lazy" decoding="async"><figcaption>Ninh Binh is the kind of add-on that can be excellent value when it replaces a weak extra stop. Image: <a href="{$trang_an_credit_url}" target="_blank" rel="license noopener">Jakub Halun / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hoi_an_image}" alt="Evening street scene in Hoi An Ancient Town, Vietnam" loading="lazy" decoding="async"><figcaption>Hoi An rewards slower routing, not a one-night checkbox. Image: <a href="{$hoi_an_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall and Nguyen Hue walking street in Ho Chi Minh City, Vietnam" loading="lazy" decoding="async"><figcaption>Ho Chi Minh City works best as a planned southern chapter, not just a departure stamp. Image: <a href="{$hcmc_credit_url}" target="_blank" rel="license noopener">Steffen Schmitz / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$mekong_image}" alt="River scene in the Mekong Delta near Phong Dien, Vietnam" loading="lazy" decoding="async"><figcaption>The Mekong is strongest when it replaces a weak final add-on, not when it is squeezed into departure day. Image: <a href="{$mekong_credit_url}" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$guide_hero_image}" alt="Ha Long Bay cruise boats and limestone karsts used as a budget planning reference" loading="lazy" decoding="async"><figcaption>Ha Long Bay or Lan Ha Bay is a named premium line item, especially once cruise quality and transfers are included. Image: <a href="https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">The decisions that change the estimate</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<table class="vg-decision-table vg-comparison-matrix">
<thead>
<tr><th>Decision</th><th>Lower-cost version</th><th>Higher-cost version</th><th>Verdict</th></tr>
</thead>
<tbody>
<tr><td data-label="Decision">Route shape</td><td data-label="Lower-cost version">Fewer bases, overnight rail where sensible, limited one-way hops.</td><td data-label="Higher-cost version">Many bases, extra domestic flights, private cars, tight cruise connections.</td><td data-label="Verdict">Simpler routes usually cost less and feel calmer.</td></tr>
<tr><td data-label="Decision">Comfort level</td><td data-label="Lower-cost version">Simple rooms and local meals with a few paid highlights.</td><td data-label="Higher-cost version">Central boutique hotels, private guides, upgraded transfers, premium dining.</td><td data-label="Verdict">Hotels and private support move the total more than everyday meals.</td></tr>
<tr><td data-label="Decision">Season</td><td data-label="Lower-cost version">Shoulder dates, flexible cancellations, fewer holiday nights.</td><td data-label="Higher-cost version">Tet, public holidays, beach peaks, school-holiday resort demand.</td><td data-label="Verdict">Season can change both room rates and the cost of fixing bad routing.</td></tr>
<tr><td data-label="Decision">Cruises and beaches</td><td data-label="Lower-cost version">Day trips or simple overnight cruises, modest beach hotels.</td><td data-label="Higher-cost version">Premium bay cruises, island resorts, private transfers.</td><td data-label="Verdict">Treat these as separate line items, not normal daily spend.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Solo, couple, and family budgets behave differently</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Per-person budget advice can mislead solo travelers and families. Some costs are individual, while others become cheaper per person when shared.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cost-traveler-mix">
<thead>
<tr><th>Cost behavior</th><th>Solo traveler</th><th>Couple or two friends</th><th>Family or small group</th><th>Planning implication</th></tr>
</thead>
<tbody>
<tr><td data-label="Cost behavior">Hotel rooms</td><td data-label="Solo traveler">Private rooms raise the daily average; dorms or simple guesthouses control it.</td><td data-label="Couple or two friends">Room cost is shared, so mid-range comfort can become better value.</td><td data-label="Family or small group">Connecting rooms, family rooms, or apartments need earlier booking.</td><td data-label="Planning implication">Do not compare solo and couple budgets without adjusting the room line.</td></tr>
<tr><td data-label="Cost behavior">Transfers and private cars</td><td data-label="Solo traveler">Often expensive unless replacing a difficult public transfer.</td><td data-label="Couple or two friends">Can be sensible for late arrivals or luggage-heavy moves.</td><td data-label="Family or small group">Often worth pricing because the cost is shared and logistics are calmer.</td><td data-label="Planning implication">Private transport is a comfort decision, not always a luxury mistake.</td></tr>
<tr><td data-label="Cost behavior">Visa, tickets, meals, and flights</td><td data-label="Solo traveler">Mostly individual costs.</td><td data-label="Couple or two friends">Mostly individual costs, except shared taxis and rooms.</td><td data-label="Family or small group">Multiply carefully and check age rules for attractions and hotels.</td><td data-label="Planning implication">Shared rooms cannot offset every per-person cost.</td></tr>
<tr><td data-label="Cost behavior">Guides and private experiences</td><td data-label="Solo traveler">Best saved for one or two moments where context matters.</td><td data-label="Couple or two friends">Can improve food, history, craft, or countryside days without dominating the trip.</td><td data-label="Family or small group">Can prevent wasted time and keep pacing realistic.</td><td data-label="Planning implication">Spend where the guide changes the outcome, not where the route is self-explanatory.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Costs that do not fit a daily budget</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The easiest way to under-budget Vietnam is to hide one-off costs inside a daily number. Pull these out before you decide whether your trip is basic, comfortable, or premium.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cost-hidden-costs">
<thead>
<tr><th>Hidden cost or trap</th><th>Why it happens</th><th>How to budget it</th></tr>
</thead>
<tbody>
<tr><td data-label="Hidden cost or trap">Domestic-flight baggage</td><td data-label="Why it happens">The cheapest fare may not match your luggage, route, or timing.</td><td data-label="How to budget it">Check baggage rules before comparing a flight with a train or private transfer.</td></tr>
<tr><td data-label="Hidden cost or trap">Late arrival or early departure</td><td data-label="Why it happens">Public transport may be less practical with luggage, children, or fatigue.</td><td data-label="How to budget it">Price a safer airport transfer before deciding the arrival city is cheap.</td></tr>
<tr><td data-label="Hidden cost or trap">Holiday and Tet demand</td><td data-label="Why it happens">Rooms, transport, and driver availability can tighten during public-holiday windows.</td><td data-label="How to budget it">Book refundable rooms early and hold more buffer for route changes.</td></tr>
<tr><td data-label="Hidden cost or trap">Cruise and tour inclusions</td><td data-label="Why it happens">Some prices include transfers, meals, entrance fees, or guide time; others do not.</td><td data-label="How to budget it">Compare the full inclusion list, not just the headline price.</td></tr>
<tr><td data-label="Hidden cost or trap">Weather rerouting</td><td data-label="Why it happens">Bad weather can make beach time, mountain roads, or bay trips less efficient.</td><td data-label="How to budget it">Keep one flexible night or a cash buffer rather than locking every transfer tightly.</td></tr>
<tr><td data-label="Hidden cost or trap">Cash, card, and ATM friction</td><td data-label="Why it happens">Small vendors may prefer cash, while hotels and higher-value bookings may use card or transfer rules.</td><td data-label="How to budget it">Separate daily cash from larger hotel, transport, and tour payments, then check live exchange rates before withdrawals.</td></tr>
<tr><td data-label="Hidden cost or trap">Poor hotel location</td><td data-label="Why it happens">A cheaper room can create extra taxi time, missed meals, and less rest.</td><td data-label="How to budget it">Pay more for location on short city stays; save on room style when you have more time.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where to spend more, and where to save</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The premium choice is not always the expensive one. Spend where the money removes friction, protects time, or improves context; save where the cheaper version gives almost the same travel outcome.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><strong>Spend more:</strong> late-arrival airport transfers, better hotel location on short stays, a reputable bay cruise, a guide for history/food/craft context, and refundable hotels during weather-sensitive periods.</li>
<li><strong>Save:</strong> overbuilt city tours, one-night resort upgrades, unnecessary domestic hops, daily Western meals, and private cars where trains or direct flights solve the route cleanly.</li>
<li><strong>Pause before upgrading:</strong> if the upgrade does not protect time, sleep, safety, context, or cancellation flexibility, it may just make the itinerary heavier.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Example budget builds by trip style</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use these as route sketches, not promises. The right answer depends on your exact dates, room category, transfer style, and how many premium decisions are hiding inside the route.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cost-example-builds">
<thead>
<tr><th>Trip style</th><th>Budget shape</th><th>What is included</th><th>What you should watch</th></tr>
</thead>
<tbody>
<tr><td data-label="Trip style">10-day north + central value route</td><td data-label="Budget shape">Usually lands near the middle of the comfortable band when hotels are practical and transfers are simple.</td><td data-label="What is included">Hanoi, Ninh Binh, one bay or Lan Ha cruise, Hue or Hoi An, and a smart airport plan.</td><td data-label="What you should watch">A domestic flight or premium cruise can add more than a hotel upgrade.</td></tr>
<tr><td data-label="Trip style">14-day north-to-south route</td><td data-label="Budget shape">Comfortable to premium depending on how many cities, flights, and private days you add.</td><td data-label="What is included">Three or four bases, at least one slower heritage or countryside stop, and one sensible buffer night.</td><td data-label="What you should watch">Too many one-night stops make the route expensive without improving the trip.</td></tr>
<tr><td data-label="Trip style">Premium couple or family route</td><td data-label="Budget shape">Usually rises faster in rooms, transfers, and guide support than in food.</td><td data-label="What is included">Better hotel locations, private cars when they protect energy, one or two curated experiences, and cancellation flexibility.</td><td data-label="What you should watch">Suite pricing, holiday peaks, and beach or island modules can inflate the route quickly.</td></tr>
<tr><td data-label="Trip style">Backpacker with one highlight spend</td><td data-label="Budget shape">Can stay inside the basic band if the highlight is treated as a named splurge.</td><td data-label="What is included">Simple rooms, local food, public transport where sensible, and one paid cruise, tour, or transfer upgrade.</td><td data-label="What you should watch">The highlight should replace a weaker day, not stack on top of an already expensive route.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Concrete line-item budget builds</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>To calculate your own number, add the row instead of copying the headline range. The examples below are land-only and exclude international flights; rooms assume typical sharing for the style, so solo private-room travelers should raise the room line first.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cost-line-item-builds">
<thead>
<tr><th>Example trip</th><th>Rooms</th><th>Intercity transport</th><th>Cruise/tours</th><th>Food/local transport</th><th>Visa/admin</th><th>Buffer</th><th>Example total</th></tr>
</thead>
<tbody>
<tr><td data-label="Example trip">10-day basic with one highlight</td><td data-label="Rooms">USD 120-180</td><td data-label="Intercity transport">USD 90-140</td><td data-label="Cruise/tours">USD 100-170</td><td data-label="Food/local transport">USD 160-230</td><td data-label="Visa/admin">USD 25-50</td><td data-label="Buffer">USD 55-80</td><td data-label="Example total">USD 550-850</td></tr>
<tr><td data-label="Example trip">10-day comfortable north + central</td><td data-label="Rooms">USD 400-650</td><td data-label="Intercity transport">USD 160-280</td><td data-label="Cruise/tours">USD 220-360</td><td data-label="Food/local transport">USD 200-320</td><td data-label="Visa/admin">USD 25-60</td><td data-label="Buffer">USD 95-230</td><td data-label="Example total">USD 1,100-1,900</td></tr>
<tr><td data-label="Example trip">14-day comfortable north-to-south</td><td data-label="Rooms">USD 650-1,100</td><td data-label="Intercity transport">USD 280-520</td><td data-label="Cruise/tours">USD 260-520</td><td data-label="Food/local transport">USD 300-520</td><td data-label="Visa/admin">USD 25-60</td><td data-label="Buffer">USD 85-180</td><td data-label="Example total">USD 1,600-2,900</td></tr>
<tr><td data-label="Example trip">10-day premium/private</td><td data-label="Rooms">USD 1,100-2,300</td><td data-label="Intercity transport">USD 350-750</td><td data-label="Cruise/tours">USD 550-1,250</td><td data-label="Food/local transport">USD 350-700</td><td data-label="Visa/admin">USD 50-120</td><td data-label="Buffer">USD 200-380</td><td data-label="Example total">USD 2,600-5,500</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">How to build your own Vietnam budget</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p class="vg-cost-worksheet">Use this worksheet before booking anything non-refundable: <strong>rooms + intercity transport + tours/cruise + food/day + local transport + visa + buffer</strong>. The buffer is not a luxury line; it protects the trip when a transfer, room, weather decision, or exchange rate moves.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cost-worksheet">
<thead>
<tr><th>Worksheet line</th><th>How to estimate</th><th>What to live-check</th></tr>
</thead>
<tbody>
<tr><td data-label="Worksheet line">Rooms</td><td data-label="How to estimate">Price each city by exact date, room count, cancellation rule, and location.</td><td data-label="What to live-check">Refundable vs non-refundable rate, taxes/fees, breakfast, and distance from the area you will actually use.</td></tr>
<tr><td data-label="Worksheet line">Intercity transport</td><td data-label="How to estimate">Separate flights, trains, private cars, ferries, cruises, and airport transfers.</td><td data-label="What to live-check">Baggage, seat or berth type, pickup rule, travel time, and whether the transfer replaces a hotel night.</td></tr>
<tr><td data-label="Worksheet line">Tours and cruise</td><td data-label="How to estimate">List the few experiences that change the trip outcome.</td><td data-label="What to live-check">Meal inclusions, entrance fees, group size, guide language, transfer inclusion, and cancellation policy.</td></tr>
<tr><td data-label="Worksheet line">Food and daily local transport</td><td data-label="How to estimate">Use your travel style as a range, then adjust for dining habits and city pace.</td><td data-label="What to live-check">Whether hotel location lets you walk, and whether your planned meals are local, mixed, or premium.</td></tr>
<tr><td data-label="Worksheet line">Visa and admin</td><td data-label="How to estimate">Add official e-visa fee where applicable and any document/admin costs from your own country.</td><td data-label="What to live-check">Eligibility, entry type, passport details, processing time, and the official portal before payment.</td></tr>
<tr><td data-label="Worksheet line">Buffer</td><td data-label="How to estimate">Hold extra room for weather changes, late arrivals, better rooms, health needs, or a simpler transfer.</td><td data-label="What to live-check">Forecast-sensitive route points, holiday dates, and any non-refundable bookings.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
<!-- wp:list {"ordered":true,"className":"vg-check-list"} -->
<ol class="wp-block-list vg-check-list">
<li>Choose your route and number of bases before picking a daily target.</li>
<li>Price hotels by city, room type, and exact date.</li>
<li>Add intercity transport separately: domestic flights, trains, cars, airport transfers, and cruises.</li>
<li>Choose where private guides or tours genuinely improve the trip.</li>
<li>Hold a buffer for weather changes, better rooms, luggage, late arrivals, and rest days.</li>
</ol>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Prebook, live-check, or stay flexible?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam is not a trip where every cost should be locked early. Prebook the expensive or fragile pieces, live-check moving prices, and leave ordinary local decisions flexible enough to protect the route.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-cost-prebook-flex">
<thead>
<tr><th>Cost decision</th><th>Prebook when...</th><th>Live-check close to travel when...</th><th>Stay flexible when...</th></tr>
</thead>
<tbody>
<tr><td data-label="Cost decision">Hotels</td><td data-label="Prebook when...">Dates hit holidays, beach peaks, family room needs, or premium locations.</td><td data-label="Live-check close to travel when...">You are comparing refundable rates, taxes, breakfast, and location value.</td><td data-label="Stay flexible when...">You have a longer route with extra nights and no special room requirement.</td></tr>
<tr><td data-label="Cost decision">Domestic flights</td><td data-label="Prebook when...">The route depends on a specific open-jaw or tight connection.</td><td data-label="Live-check close to travel when...">Baggage, schedule changes, and airport transfer time could erase the cheap fare.</td><td data-label="Stay flexible when...">A train, private car, or slower route can solve the same movement.</td></tr>
<tr><td data-label="Cost decision">Cruises and guided days</td><td data-label="Prebook when...">Operator quality, cabin type, group size, or language matters.</td><td data-label="Live-check close to travel when...">Weather, transfer inclusions, cancellation terms, and entrance fees are unclear.</td><td data-label="Stay flexible when...">A simple day trip gives almost the same outcome as an expensive overnight.</td></tr>
<tr><td data-label="Cost decision">Local meals and city transport</td><td data-label="Prebook when...">A food tour or airport pickup protects a short stay.</td><td data-label="Live-check close to travel when...">Ride-hail availability, cash needs, or late-night arrival rules affect comfort.</td><td data-label="Stay flexible when...">The hotel location gives you easy walking choices and no hard schedule.</td></tr>
<tr><td data-label="Cost decision">Weather-sensitive add-ons</td><td data-label="Prebook when...">Limited permits, scarce rooms, or a premium operator is the whole reason for the stop.</td><td data-label="Live-check close to travel when...">Mountain roads, caves, cruises, beaches, or islands could be weakened by weather.</td><td data-label="Stay flexible when...">The add-on is optional and a better city or countryside day would be less stressful.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Live-check checklist before you pay</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-check-list vg-cost-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-cost-live-checks">
<li>Hotel exact dates, room type, location, cancellation terms, taxes, and breakfast.</li>
<li>Domestic flight fare rules, checked baggage, airport transfer time, and delay tolerance.</li>
<li>Rail seat or berth type, train number, departure time, and whether the station transfer is practical.</li>
<li>Cruise inclusions: transfer, meals, entrance fees, cabin type, cancellation, and weather handling.</li>
<li>Airport arrival plan for Hanoi or Ho Chi Minh City, especially after dark or with luggage.</li>
<li>Official e-visa fee, entry type, passport data, processing time, and arrival date cushion.</li>
<li>Live exchange rate before large cash withdrawals, hotel payments, or tour balances.</li>
<li>One flexible buffer decision: a refundable room, slower transfer, or spare night where the route is most fragile.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Vietnam travel cost FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-cost-faq">
<details><summary>How much money do I need for 10 days in Vietnam?</summary><p>For a land-only 10-day trip, plan roughly USD 550-850 basic, USD 1,100-1,900 comfortable, or USD 2,600-5,500+ premium/private before international flights. The real number depends on route shape, hotel style, domestic flights, cruises, and private support.</p></details>
<details><summary>Is Vietnam cheap for international tourists?</summary><p>Vietnam can be good value, but it is not automatically cheap if the route is overloaded. Hotels, domestic flights, cruises, private transfers, resort nights, and holiday dates can move the budget faster than everyday meals.</p></details>
<details><summary>Should I budget in USD or Vietnamese dong?</summary><p>Use USD for high-level planning if that is your home budgeting language, but expect most local spending to happen in Vietnamese dong. Re-check live exchange rates before large withdrawals, hotel payments, or tour balances.</p></details>
<details><summary>What is the easiest way to lower my Vietnam trip cost?</summary><p>Reduce the number of bases before cutting meaningful experiences. Fewer transfers usually lowers cost, protects energy, and makes mid-range hotels or one strong guided day easier to justify.</p></details>
<details><summary>What should I not hide inside a daily budget?</summary><p>Do not bury e-visa fees, airport transfers, domestic flights, trains, cruises, private cars, peak-date hotel jumps, and weather buffers inside an average day. List them separately before deciding whether the trip is basic, comfortable, or premium.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where to go next</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the <a href="/costs/">Costs hub</a> for the broader planning method, the <a href="/itineraries/">Itineraries hub</a> to reduce hidden transfer costs, the <a href="/plan/best-time-to-visit-vietnam/">Best Time guide</a> to avoid seasonal mistakes, the <a href="/plan/">Plan hub</a> for entry and logistics checks, and <a href="/compare/">Compare</a> when a route decision can save both time and money.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>For payment logistics, use <a href="/plan/money-cash-cards-atms/">Money in Vietnam: Cash, Cards and ATMs</a> after you have a budget shape. For connectivity costs and arrival friction, use <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a> before comparing roaming, local SIMs, and pre-trip eSIMs.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

vg_ops_assert_internal_page_links_are_published('Vietnam Travel Cost guide', $content);

$result = wp_update_post(
    [
        'ID'             => $page->ID,
        'post_title'     => 'Vietnam Travel Cost',
        'post_name'      => 'vietnam-travel-cost',
        'post_status'    => 'publish',
        'post_content'   => $content,
        'post_excerpt'   => 'Dated Vietnam travel cost guide for international visitors, with land-only budget ranges, trip-length scenarios, route photos, concrete line-item budget builds, hidden traps, and practical line items to verify before booking.',
        'comment_status' => 'closed',
        'ping_status'    => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    vg_ops_fail('Could not publish Vietnam Travel Cost guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Vietnam Travel Cost guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Vietnam Travel Cost: 2026 Budget Guide by Style');
update_post_meta($page_id, 'rank_math_description', 'Dated Vietnam travel cost ranges for international visitors, with land-only daily budgets, 7/10/14-day scenarios, line-item budget builds, route photos, hidden costs, visa fees, and booking checks.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Vietnam travel cost');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Estimate a realistic land-only Vietnam trip budget by route, comfort level, season, and private support.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 16, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Expanded Vietnam Travel Cost guide with concrete line-item budget builds, a practical rail/airport/ATM visual section, prebook-versus-flexible guidance, FAQ answers, updated image credits/source trail, and direct links to Money in Vietnam and SIM/eSIM logistics guides.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam National Electronic Visa system - official e-visa portal for outside Viet Nam foreigners - https://evisa.gov.vn/e-visa/foreigners - checked July 14, 2026\nVietcombank - foreign exchange rates; XML endpoint returned DateTime 7/14/2026 6:07:17 PM with USD buy VND 26,040.00, transfer VND 26,070.00, sell VND 26,450.00 - https://www.vietcombank.com.vn/en/Personal/Cong-cu-Tien-ich/Ty-gia - checked July 14, 2026\nVietnam Railways - official rail fare lookup - https://dsvn.vn/ - checked July 14, 2026\nNoi Bai International Airport - public transport information - https://noibaiairport.vn/en/public-transportations-nid1.html - checked July 14, 2026\nVietnam Airlines - Ho Chi Minh City airport to city guide - https://www.vietnamairlines.com/ca/en/plan-book/travel/travel-guide/airport-ho-chi-minh-to-city - checked July 14, 2026\nWikimedia Commons image record - Ha Long Bay, Vietnam, View from above - https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg - license checked July 16, 2026\nWikimedia Commons image record - Hanoi Hoan Kiem Lake - https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg - license checked July 16, 2026\nWikimedia Commons image record - Trang An Landscape Complex, Ninh Binh Province - https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg - license checked July 16, 2026\nWikimedia Commons image record - Hoi An Ancient Town - https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg - license checked July 16, 2026\nWikimedia Commons image record - Vietnam, Hai-Van-Pass - https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg - license checked July 16, 2026\nWikimedia Commons image record - Ho Chi Minh City Hall - https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg - license checked July 16, 2026\nWikimedia Commons image record - Vietnam, Phong Dien, Mekong Delta, River - https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg - license checked July 16, 2026\nWikimedia Commons image record - Hanoi Railway Station 20130725 - https://commons.wikimedia.org/wiki/File:Hanoi_Railway_Station_20130725.jpg - license checked July 16, 2026\nWikimedia Commons image record - Noi Bai International Airport T2 Waiting Area - https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg - license checked July 16, 2026\nWikimedia Commons image record - ATM VPBank, Lieu Giai, Hanoi - https://commons.wikimedia.org/wiki/File:ATM_VPBank,_Li%E1%BB%85u_Giai,_H%C3%A0_N%E1%BB%99i_001.JPG - license checked July 16, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Budget Vietnam by route and comfort first; treat hotels, intercity moves, cruises, and private support as separate live-price line items.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Dated land-only budget bands by travel style.\nScenario budgets by trip length without pretending to be live quotes.\nConcrete line-item budget builds for rooms, intercity transport, cruise/tours, food/local transport, visa/admin, buffer, and example totals.\nPhoto-led route-cost cues plus practical rail, airport-arrival, and ATM/cash visuals that connect image evidence to budget pressure.\nRoute archetype, traveler mix, hidden-cost, example-build, prebook-versus-flexible, FAQ, and worksheet frameworks that add original planning judgment.\nOfficial e-visa fee, exchange-rate, rail, airport transport, and Wikimedia image-license source checks.\nDecision framework that separates daily spend from route, season, and comfort drivers.\nRelated decision chain connecting cost back to route length and destination pruning.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Use this to pressure-test route cost against time.\n14 Days in Vietnam | /itineraries/14-days-in-vietnam/ | Compare what extra days add before upgrading.\nMoney in Vietnam | /plan/money-cash-cards-atms/ | Use this after the budget shape is clear and payment friction becomes the next decision.\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Put data, roaming, hotspot, and arrival connectivity inside the budget.\nBest Places to Visit in Vietnam | /destinations/best-places-to-visit-vietnam/ | Remove weak stops before adding cost.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.
Ninh Binh Day Trip vs Overnight | /compare/ninh-binh-day-trip-vs-overnight/ | Decide whether Ninh Binh should stay a Hanoi day trip, become one protected countryside night, slow down for two nights, or be skipped cleanly.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.
Trang An vs Tam Coc | /compare/trang-an-vs-tam-coc/ | Choose the Ninh Binh boat route by certainty, countryside rhythm, crowd timing, base logic, photography, family comfort, and whether the second boat changes the trip.\nWhere to Stay in Ninh Binh | /destinations/where-to-stay-in-ninh-binh/ | Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.\nNinh Binh to Ha Long Bay Transfer | /plan/ninh-binh-to-ha-long-bay-transfer/ | Confirm the exact bay port, pickup window, luggage plan, weather buffer, and cruise check-in risk before leaving Ninh Binh.\nTam Coc Travel Guide | /destinations/tam-coc-travel-guide/ | Use Tam Coc as a soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and route pacing before adding another northern landscape stop.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero and route image: Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0. Body images: Hanoi by Alex 69200 vx, CC BY-SA 4.0; Trang An by Jakub Halun, CC BY 4.0; Hoi An and Ho Chi Minh City by Steffen Schmitz, CC BY-SA 4.0; Hai Van Pass by Wolkenkratzer, CC BY-SA 4.0; Mekong Delta by Vyacheslav Argenberg, CC BY 4.0. Practical cost images: Hanoi Railway Station by Alancrh, CC BY-SA 4.0; Noi Bai T2 waiting area by Sky 269, CC BY-SA 4.0; Hanoi ATM by Phan Minh Tuan, CC BY-SA 4.0.');
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
    vg_ops_fail('Vietnam Travel Cost guide was updated but is not published.');
}

vg_ops_refresh_costs_hub();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Vietnam Travel Cost guide: {$page_id} {$updated_permalink}");

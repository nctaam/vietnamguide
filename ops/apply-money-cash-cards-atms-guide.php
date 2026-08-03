<?php
/**
 * Publish the Money in Vietnam guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-money-cash-cards-atms-guide.php --allow-root
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
    $value = getenv('VG_FORCE_MONEY_GUIDE_REPUBLISH');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the Money in Vietnam guide.');
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

    if (! vg_ops_published_page_exists('plan/money-cash-cards-atms')) {
        vg_ops_log('Skipped Plan hub refresh: Money in Vietnam guide is not published.');
        return;
    }

    $marker = '<!-- vg-money-plan-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Plan cash before arrival</h3><p>The <a href="/plan/money-cash-cards-atms/">Money in Vietnam guide</a> helps international travelers decide how much VND to carry, when to use cards, how to approach ATMs, and what cash declaration thresholds to check before crossing the border.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Plan hub money note', $block);
    vg_ops_upsert_marked_group($hub, 'Plan hub money note', $marker, $block);
}

function vg_ops_refresh_costs_hub(): void
{
    $hub = get_page_by_path('costs', OBJECT, 'page');

    if (! $hub instanceof WP_Post || $hub->post_status !== 'publish') {
        vg_ops_log('Skipped Costs hub refresh: costs page was not found or is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('plan/money-cash-cards-atms')) {
        vg_ops_log('Skipped Costs hub refresh: Money in Vietnam guide is not published.');
        return;
    }

    $marker = '<!-- vg-money-costs-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Separate budget from payment friction</h3><p>Use the <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost guide</a> for the trip budget, then use <a href="/plan/money-cash-cards-atms/">Money in Vietnam</a> for cash, cards, ATMs, exchange-rate checks, and payment-risk planning.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Costs hub money note', $block);
    vg_ops_upsert_marked_group($hub, 'Costs hub money note', $marker, $block);
}

function vg_ops_refresh_cost_guide_money_link(): void
{
    $cost_guide = get_page_by_path('costs/vietnam-travel-cost', OBJECT, 'page');

    if (! $cost_guide instanceof WP_Post || $cost_guide->post_status !== 'publish') {
        vg_ops_log('Skipped Cost guide money link refresh: guide was not found or is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('plan/money-cash-cards-atms')) {
        vg_ops_log('Skipped Cost guide money link refresh: Money in Vietnam guide is not published.');
        return;
    }

    $old = '<p>Cash/ATMs, SIM/eSIM, and 10 Days are related planning topics to check as the guide library expands; they are intentionally not linked here until the dedicated pages are published.</p>';
    $stale_new = '<p>For payment logistics, use <a href="/plan/money-cash-cards-atms/">Money in Vietnam: Cash, Cards and ATMs</a> after building the broad budget. SIM/eSIM remains a separate logistics page in the roadmap.</p>';
    $new = vg_ops_published_page_exists('plan/sim-esim-vietnam')
        ? '<p>For payment logistics, use <a href="/plan/money-cash-cards-atms/">Money in Vietnam: Cash, Cards and ATMs</a> after building the broad budget. For phone data, local-number, roaming, and first-hour arrival friction, use <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a>.</p>'
        : $stale_new;

    if (str_contains($cost_guide->post_content, $new)) {
        vg_ops_log('Skipped Cost guide money link refresh: current link already present.');
        return;
    }

    $updated_content = str_replace([$old, $stale_new], $new, $cost_guide->post_content, $replacement_count);

    if ($replacement_count < 1) {
        vg_ops_log('Skipped Cost guide money link refresh: stale placeholder paragraph was not found.');
        return;
    }

    vg_ops_assert_internal_page_links_are_published('Cost guide money link', $new);

    $result = wp_update_post(
        [
            'ID'           => $cost_guide->ID,
            'post_content' => $updated_content,
        ],
        true
    );

    if (is_wp_error($result)) {
        vg_ops_fail('Could not refresh Cost guide money link: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Cost guide money link: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Cost guide money link: {$cost_guide->ID}");
}

$parent = get_page_by_path('plan', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: plan');
}

$page = get_page_by_path('plan/money-cash-cards-atms', OBJECT, 'page');
$allow_republish = vg_ops_force_republish_enabled();

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('Money in Vietnam guide is not a draft. Set VG_FORCE_MONEY_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    $permalink = get_permalink($page);
    vg_ops_log("Preflight Money in Vietnam guide: {$page->ID} {$page->post_status} {$permalink}");
} else {
    vg_ops_log('Preflight Money in Vietnam guide: no existing page found; creating a child page under /plan/.');
}

$hero_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/c8/Ben_Thanh_Market%2C_2023_%2803%29.jpg/1920px-Ben_Thanh_Market%2C_2023_%2803%29.jpg');
$hero_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ben_Thanh_Market,_2023_(03).jpg');
$banknote_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/12/500.000_Dong_Hell_Banknote.jpg/1920px-500.000_Dong_Hell_Banknote.jpg');
$banknote_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:500.000_Dong_Hell_Banknote.jpg');
$atm_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f1/ATM_VPBank%2C_Li%E1%BB%85u_Giai%2C_H%C3%A0_N%E1%BB%99i_001.JPG/1920px-ATM_VPBank%2C_Li%E1%BB%85u_Giai%2C_H%C3%A0_N%E1%BB%99i_001.JPG');
$atm_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:ATM_VPBank,_Li%E1%BB%85u_Giai,_H%C3%A0_N%E1%BB%99i_001.JPG');
$dong_xuan_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/Cho_Dong_Xuan%2C_Old_Quarter%2C_Hanoi%2C_Vietnam_%285245806573%29.jpg/1920px-Cho_Dong_Xuan%2C_Old_Quarter%2C_Hanoi%2C_Vietnam_%285245806573%29.jpg');
$dong_xuan_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cho_Dong_Xuan,_Old_Quarter,_Hanoi,_Vietnam_(5245806573).jpg');
$airport_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/56/Noi_Bai_International_Airport_T2_Waiting_Area.jpg/1920px-Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$airport_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg');
$sim_esim_sentence = vg_ops_published_page_exists('plan/sim-esim-vietnam')
    ? ' Add <a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a> before arrival if airport counters, ride-hailing, hotel contact, or home-number OTP could affect payment or transport.'
    : '';
$sim_esim_related_route = vg_ops_published_page_exists('plan/sim-esim-vietnam')
    ? "\nSIM and eSIM in Vietnam | /plan/sim-esim-vietnam/ | Plan phone data, local-number, roaming, and first-hour arrival friction before payment or transport problems compound."
    : '';

$content = <<<HTML
<!-- vg-money-hero:v1 -->
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$hero_image}","dimRatio":54,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Ben Thanh Market stalls in Ho Chi Minh City, Vietnam, used for a cash and payment planning guide" src="{$hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed guide - Updated July 17, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Money in Vietnam: Cash, Cards and ATMs</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">Vietnam is not a card-only destination for most international travelers. Plan to use Vietnamese dong for small local spending, cards for higher-value merchants that clearly accept them, and bank ATMs or official exchange counters when you need more cash.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">This guide separates durable payment judgment from live exchange rates, ATM fees, bank limits, and card rules that must be checked close to travel.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="{$hero_credit_url}" target="_blank" rel="license noopener">Bahnfrend / CC BY-SA 4.0</a>
<!-- /wp:html -->
</div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- vg-money-concierge-verdict:v1 -->
<!-- wp:group {"className":"vg-concierge-verdict vg-money-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict vg-money-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Use VND for friction, cards for value, and ATMs as a controlled refill.</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>The best Vietnam money plan is mixed.</strong> Carry enough Vietnamese dong for arrival, street-level spending, tips, markets, small restaurants, day trips, taxis, and cash-only moments. Use cards for hotels, larger restaurants, reputable tours, and bigger purchases when the terminal charges in VND and the merchant is clear.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><strong>Do not rely on one payment method.</strong> Keep at least two cards in separate places and a small reserve of clean foreign cash only as backup.</li>
<li><strong>Withdraw or exchange deliberately.</strong> Use bank ATMs, bank branches, or official exchange counters; avoid rushed street exchange.</li>
<li><strong>Reject dynamic currency conversion.</strong> If a terminal or ATM offers to charge your home currency, choose VND unless your bank has a rare, confirmed better rule.</li>
<li><strong>Check declaration thresholds before flying.</strong> Travelers crossing the border with over USD 5,000 or over VND 15,000,000 should review official declaration rules.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- vg-money-editorial-proof:v1 -->
<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- vg-money-currency-basics:v1 -->
<!-- wp:group {"className":"vg-at-a-glance vg-money-currency-basics","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-at-a-glance vg-money-currency-basics">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Currency basics</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Vietnamese dong is the everyday working currency.</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Plan in your home currency if it helps the budget, but pay attention to Vietnamese dong when you are actually in Vietnam. Most ordinary local spending happens in VND. Foreign cash is mainly useful as a backup or for intentional exchange, not for normal daily purchases.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-money-currency-basics-table">
<thead>
<tr><th>Money detail</th><th>VietnamGuide guidance</th><th>Why it matters</th></tr>
</thead>
<tbody>
<tr><td data-label="Money detail">Currency</td><td data-label="VietnamGuide guidance">Vietnamese dong, usually written as VND or with the dong symbol.</td><td data-label="Why it matters">Card terminals, menus, taxis, markets, and tickets may show large numbers; count zeros calmly before paying.</td></tr>
<tr><td data-label="Money detail">Cash notes</td><td data-label="VietnamGuide guidance">Keep large notes separate from small notes and check the value before handing over cash.</td><td data-label="Why it matters">Note-size and color confusion is one of the easiest avoidable payment mistakes after a long travel day.</td></tr>
<tr><td data-label="Money detail">Damaged notes</td><td data-label="VietnamGuide guidance">Avoid accepting heavily torn, taped, or badly worn notes when you can politely ask for another.</td><td data-label="Why it matters">Small vendors may refuse poor-condition notes, leaving you with cash that is harder to spend.</td></tr>
<tr><td data-label="Money detail">Exchange rate</td><td data-label="VietnamGuide guidance">Use live bank rates for large withdrawals, exchange, hotel balances, or tour balances.</td><td data-label="Why it matters">A copied rate in an old guide is useful only as historical context, not as a payment promise.</td></tr>
<tr><td data-label="Money detail">Foreign cash</td><td data-label="VietnamGuide guidance">Bring clean, newer notes only if you plan to exchange cash or want an emergency reserve.</td><td data-label="Why it matters">Wrinkled or marked foreign notes may receive worse treatment or be refused by some counters.</td></tr>
</tbody>
</table>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- vg-money-photo-grid:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Payment proof: where each money choice shows up</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>These visuals are practical prompts. Vietnam money planning is different at an ATM, a local market, an airport arrival hall, and a street-level cash purchase.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-guide-photo-grid vg-money-photo-grid" aria-label="Vietnam money and payment proof photography">
<figure class="vg-guide-photo"><img src="{$banknote_image}" alt="Vietnamese dong banknote reference image" loading="lazy" decoding="async"><figcaption>Know the note value before paying, especially after long flights or in busy markets. Image: <a href="{$banknote_credit_url}" target="_blank" rel="license noopener">Cookie Nguyen / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$atm_image}" alt="Bank ATM on Lieu Giai street in Hanoi, Vietnam" loading="lazy" decoding="async"><figcaption>Use bank ATMs as controlled refills, then check fees, limits, card return, and receipt before walking away. Image: <a href="{$atm_credit_url}" target="_blank" rel="license noopener">Phan Minh Tuan / CC BY-SA 4.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$dong_xuan_image}" alt="Dong Xuan Market in Hanoi Old Quarter, Vietnam" loading="lazy" decoding="async"><figcaption>Markets, street snacks, small local restaurants, tips, and short rides are the places where VND cash still protects the day. Image: <a href="{$dong_xuan_credit_url}" target="_blank" rel="license noopener">yeowatzup / CC BY 2.0</a>.</figcaption></figure>
<figure class="vg-guide-photo"><img src="{$airport_image}" alt="Noi Bai International Airport Terminal 2 waiting area in Hanoi" loading="lazy" decoding="async"><figcaption>Arrival cash is about friction: SIM pickup, taxis, snacks, tips, and the first hotel transfer should not depend on one card working. Image: <a href="{$airport_credit_url}" target="_blank" rel="license noopener">Sky 269 / CC BY-SA 4.0</a>.</figcaption></figure>
</div>
<!-- /wp:html -->

<!-- vg-money-payment-decision-table:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cash, card or ATM: the decision table</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use this table before you leave for Vietnam and again after you know your actual route. The goal is not to carry more money; it is to keep the right money available at the right moment.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-money-payment-decision-table">
<thead>
<tr><th>Situation</th><th>Best first answer</th><th>Why</th><th>Live check</th></tr>
</thead>
<tbody>
<tr><td data-label="Situation">Airport arrival and first night</td><td data-label="Best first answer">Small VND cash buffer plus backup card.</td><td data-label="Why">Arrival is when fatigue, luggage, SIM setup, and transfer confusion collide.</td><td data-label="Live check">Airport ATM/exchange availability, hotel transfer terms, and late-night transport.</td></tr>
<tr><td data-label="Situation">Hotels and larger restaurants</td><td data-label="Best first answer">Card when accepted and charged in VND.</td><td data-label="Why">It reduces cash handling for larger amounts and creates a payment record.</td><td data-label="Live check">Surcharge, currency selection, deposit rule, and whether the terminal works.</td></tr>
<tr><td data-label="Situation">Markets, street food, small shops, local cafes</td><td data-label="Best first answer">VND cash in smaller notes.</td><td data-label="Why">Cash keeps the interaction simple and avoids asking tiny vendors to solve card friction.</td><td data-label="Live check">Exact price, change, and note value before handing over cash.</td></tr>
<tr><td data-label="Situation">Guides, drivers, spas, porter help</td><td data-label="Best first answer">VND cash for discretionary tips when service is strong.</td><td data-label="Why">Tipping is not the core payment method, but small notes make it easier to thank people without awkwardness.</td><td data-label="Live check">Whether service charge is already included and what feels appropriate locally.</td></tr>
<tr><td data-label="Situation">Rural, island, mountain, or late-night travel</td><td data-label="Best first answer">More cash reserve than city days.</td><td data-label="Why">ATM access, card acceptance, weather, and transport disruption can be thinner outside major city centers.</td><td data-label="Live check">Next ATM location, ferry/transfer rules, hotel cash policy, and holiday pressure.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-money-arrival-cash-plan:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Arrival cash plan by trip shape</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Do not arrive with all your trip money in cash. Do not arrive with no cash plan either. Use these ranges as practical starting buffers, then adjust for group size, arrival time, hotel transfer, and whether the first two days leave the city.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-money-arrival-cash-plan">
<thead>
<tr><th>First 24-48 hours</th><th>Practical cash posture</th><th>Why</th><th>What to avoid</th></tr>
</thead>
<tbody>
<tr><td data-label="First 24-48 hours">Solo or couple, easy city arrival</td><td data-label="Practical cash posture">A modest VND buffer for meals, local rides, tips, and small purchases.</td><td data-label="Why">Cities usually offer ATMs and card options, but the first evening should not depend on one terminal.</td><td data-label="What to avoid">Withdrawing a large amount before seeing hotel safe, route, and spending rhythm.</td></tr>
<tr><td data-label="First 24-48 hours">Family or group arrival</td><td data-label="Practical cash posture">Higher small-note buffer plus confirmed card backup.</td><td data-label="Why">Multiple taxis, snacks, luggage help, child needs, and split costs can create cash friction fast.</td><td data-label="What to avoid">Keeping all group cash in one wallet or one bag.</td></tr>
<tr><td data-label="First 24-48 hours">Late-night arrival</td><td data-label="Practical cash posture">Prearranged transfer where possible and enough VND for backup transport or food.</td><td data-label="Why">Late arrival reduces the margin for ATM problems, card declines, and unclear pickup points.</td><td data-label="What to avoid">Testing a new card for the first time at midnight.</td></tr>
<tr><td data-label="First 24-48 hours">Day trip, rural stay, island, or mountain route</td><td data-label="Practical cash posture">Withdraw or exchange before leaving the city.</td><td data-label="Why">ATM access and card acceptance can thin out when the route becomes scenic or remote.</td><td data-label="What to avoid">Assuming the next town, port, or homestay has the same payment options as Hanoi or Ho Chi Minh City.</td></tr>
<tr><td data-label="First 24-48 hours">Premium or private itinerary</td><td data-label="Practical cash posture">Card for major balances, VND for tips, small stops, guide/driver discretion, and emergencies.</td><td data-label="Why">Private support removes some friction, but cash still solves human-scale moments.</td><td data-label="What to avoid">Confusing prepaid itinerary value with having zero local cash needs.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-money-exchange-atm-card:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Exchange, ATM or card: which payment rail to use</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The cheapest-looking method is not always the best method. Compare safety, convenience, fees, exchange spread, route access, and the size of the payment.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-money-exchange-atm-card">
<thead>
<tr><th>Payment rail</th><th>Use it for</th><th>Strength</th><th>Weakness</th><th>VietnamGuide rule</th></tr>
</thead>
<tbody>
<tr><td data-label="Payment rail">Bank ATM withdrawal</td><td data-label="Use it for">Routine cash refills in cities and larger towns.</td><td data-label="Strength">Convenient, traceable, and usually simpler than carrying large cash from home.</td><td data-label="Weakness">ATM owner fee, card issuer fee, withdrawal limit, and occasional card/terminal issues.</td><td data-label="VietnamGuide rule">Use bank-attached or indoor ATMs, withdraw deliberately, and keep a backup card separate.</td></tr>
<tr><td data-label="Payment rail">Bank or official exchange counter</td><td data-label="Use it for">Converting clean foreign cash when you intentionally brought it.</td><td data-label="Strength">Useful for backup cash and larger planned exchange.</td><td data-label="Weakness">Requires clean notes, time, ID sometimes, and live rate comparison.</td><td data-label="VietnamGuide rule">Exchange enough for the next stage, not the whole trip unless your route justifies it.</td></tr>
<tr><td data-label="Payment rail">Card payment</td><td data-label="Use it for">Hotels, reputable restaurants, larger shops, tours, and some transport providers.</td><td data-label="Strength">Less cash risk and better record keeping for larger amounts.</td><td data-label="Weakness">Merchant surcharge, card block, DCC, terminal outage, or limited acceptance.</td><td data-label="VietnamGuide rule">Ask to be charged in VND and check the receipt before leaving.</td></tr>
<tr><td data-label="Payment rail">Airport exchange or ATM</td><td data-label="Use it for">Arrival buffer only.</td><td data-label="Strength">Solves first-night friction after landing.</td><td data-label="Weakness">May not offer the best rate or most convenient withdrawal limit.</td><td data-label="VietnamGuide rule">Get enough to move calmly, then handle larger cash needs after sleep.</td></tr>
<tr><td data-label="Payment rail">Home-currency charge</td><td data-label="Use it for">Almost never by default.</td><td data-label="Strength">Shows a familiar number.</td><td data-label="Weakness">Dynamic currency conversion can hide a poor rate.</td><td data-label="VietnamGuide rule">Choose VND at ATMs and card terminals unless you have confirmed a better card-specific rule.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-money-atm-checklist:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">ATM checklist before and after withdrawing</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>ATM advice gets stale when guides pretend one bank or one limit is permanent. Use this checklist instead: it works even when fee screens, limits, and card networks change.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-money-atm-checklist"} -->
<ul class="wp-block-list vg-check-list vg-money-atm-checklist">
<li>Prefer bank-attached, indoor, hotel-lobby, mall, or well-lit ATMs over isolated machines on an empty street.</li>
<li>Check the on-screen fee and withdrawal limit before accepting the transaction.</li>
<li>Decline home-currency conversion when the machine offers a choice; choose to be charged in VND unless your bank has instructed otherwise.</li>
<li>Cover the keypad, do not accept help from strangers, and cancel if the card slot or keypad looks tampered with.</li>
<li>Wait for card, cash, and receipt; do not walk away while the machine is still processing.</li>
<li>Count cash discreetly and separate large notes from small notes before leaving the area.</li>
<li>Keep a second card and a small emergency cash reserve in a different bag or hotel safe.</li>
<li>Use the Visa ATM locator or your card network/bank app as a backup search tool, then verify the location on the ground.</li>
</ul>
<!-- /wp:list -->

<!-- vg-money-card-acceptance:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Where cards work well and where cash still wins</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Card acceptance is strongest where the merchant is formal and the transaction is larger. Cash remains useful where spending is small, fast, local, or outside the main tourist infrastructure.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-money-card-acceptance">
<thead>
<tr><th>Place or payment</th><th>Likely payment fit</th><th>Traveler risk</th><th>Best practice</th></tr>
</thead>
<tbody>
<tr><td data-label="Place or payment">International hotels and higher-end city restaurants</td><td data-label="Likely payment fit">Card often works.</td><td data-label="Traveler risk">Surcharge, deposit hold, DCC, or terminal problem.</td><td data-label="Best practice">Confirm currency, ask about surcharge, and keep receipt.</td></tr>
<tr><td data-label="Place or payment">Boutique hotels, homestays, and small tour operators</td><td data-label="Likely payment fit">Mixed: card, bank transfer, or cash.</td><td data-label="Traveler risk">Balance due rules can surprise travelers at checkout or pickup.</td><td data-label="Best practice">Confirm payment method before arrival and keep enough VND if cash is required.</td></tr>
<tr><td data-label="Place or payment">Street food, markets, cafes, small shops</td><td data-label="Likely payment fit">Cash is safest.</td><td data-label="Traveler risk">Large notes, no change, price misunderstanding.</td><td data-label="Best practice">Carry smaller notes and agree price before paying.</td></tr>
<tr><td data-label="Place or payment">Ride-hailing, taxis, local buses, tips</td><td data-label="Likely payment fit">Cash backup even when app/card is available.</td><td data-label="Traveler risk">Phone battery, app mismatch, driver payment preference, late-night problems.</td><td data-label="Best practice">Keep enough VND for a ride back to the hotel.</td></tr>
<tr><td data-label="Place or payment">Rural, mountain, island, ferry, and market routes</td><td data-label="Likely payment fit">Cash first.</td><td data-label="Traveler risk">Sparse ATMs, weather disruption, weak signal, or cash-only lodging.</td><td data-label="Best practice">Withdraw before leaving the city and split cash between adults.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-money-cash-declaration:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cash declaration: the official threshold to check</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vietnam's cash declaration rule is not a budgeting suggestion. It is a border-control rule. As checked on July 17, 2026, official references to Circular 15/2011/TT-NHNN point travelers to declaration requirements when carrying foreign currency cash over USD 5,000 or equivalent, or Vietnamese dong cash over VND 15,000,000, through international border gates.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-money-cash-declaration"} -->
<ul class="wp-block-list vg-check-list vg-money-cash-declaration">
<li>Most leisure travelers should not need to carry anywhere near that much cash for ordinary tourism.</li>
<li>If you do carry large cash for a specific reason, check the official rule before departure and again before leaving Vietnam.</li>
<li>Keep bank receipts or withdrawal/exchange records when moving meaningful amounts of cash.</li>
<li>Do not split large cash across companions to avoid a rule you have not checked; read the official wording and ask the airline or customs authority when unsure.</li>
</ul>
<!-- /wp:list -->

<!-- vg-money-safety-mistakes:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Money mistakes that make Vietnam harder than it needs to be</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Most money problems are not dramatic scams. They are planning gaps: one card, no small notes, a remote route with no cash, a home-currency ATM conversion, or a large note handed over too fast.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-money-safety-mistakes">
<thead>
<tr><th>Mistake</th><th>Why it happens</th><th>Better move</th><th>What it protects</th></tr>
</thead>
<tbody>
<tr><td data-label="Mistake">Arriving with no VND plan</td><td data-label="Why it happens">The traveler assumes cards work everywhere after landing.</td><td data-label="Better move">Arrange a modest arrival cash buffer or an airport ATM/exchange plan.</td><td data-label="What it protects">First-night transport, food, tips, and SIM setup.</td></tr>
<tr><td data-label="Mistake">Carrying too much cash at once</td><td data-label="Why it happens">A traveler wants to avoid ATM fees and overcorrects.</td><td data-label="Better move">Withdraw or exchange in stages and split cash between secure places.</td><td data-label="What it protects">Loss, theft, and border declaration confusion.</td></tr>
<tr><td data-label="Mistake">Accepting home-currency conversion</td><td data-label="Why it happens">The familiar amount feels reassuring on the screen.</td><td data-label="Better move">Choose VND and let your card issuer handle conversion unless you know your card is different.</td><td data-label="What it protects">Exchange spread and hidden conversion cost.</td></tr>
<tr><td data-label="Mistake">Depending on one card</td><td data-label="Why it happens">The card worked at home and during flight booking.</td><td data-label="Better move">Bring a second card from a different network or issuer and store it separately.</td><td data-label="What it protects">ATM outages, fraud holds, damaged cards, and misplaced wallets.</td></tr>
<tr><td data-label="Mistake">Leaving cash until the remote leg</td><td data-label="Why it happens">The city routine gives false confidence.</td><td data-label="Better move">Withdraw before islands, mountain roads, homestays, ferries, and rural day trips.</td><td data-label="What it protects">Transport, meals, tips, and backup accommodation.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- vg-money-live-checks:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Live checks before you pay, withdraw or exchange</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Evergreen advice can tell you what to check. It should not pretend to freeze the rate, fee, limit, or acceptance rule forever.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-money-live-checks"} -->
<ul class="wp-block-list vg-check-list vg-money-live-checks">
<li>Live exchange rate from a bank or trusted financial source before large withdrawals, exchange, or card balances.</li>
<li>Your card issuer's foreign transaction fee, ATM fee, daily withdrawal limit, fraud-notice process, and emergency contact method.</li>
<li>ATM owner fee and withdrawal cap on the actual screen before accepting a transaction.</li>
<li>Hotel, tour, cruise, and transport provider payment method before arrival or pickup.</li>
<li>Whether a card terminal is charging in VND or your home currency before you enter the PIN or sign.</li>
<li>Cash needs before leaving cities for islands, mountain routes, ferries, homestays, and early departures.</li>
<li>Cash declaration rules before carrying large foreign currency or Vietnamese dong across the border.</li>
</ul>
<!-- /wp:list -->

<!-- vg-money-faq:v1 -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Money in Vietnam FAQ</h2>
<!-- /wp:heading -->
<!-- wp:html -->
<div class="vg-faq-list vg-money-faq">
<details><summary>Should I bring cash or use ATMs in Vietnam?</summary><p>Use both. Bring a small clean foreign-cash backup if it makes sense for your country, but do routine refills through bank ATMs or official exchange counters instead of carrying the whole trip budget in cash.</p></details>
<details><summary>Can I use credit cards in Vietnam?</summary><p>Yes, especially at many hotels, larger restaurants, shops, and formal travel providers. Do not assume cards work for street food, markets, small cafes, rural routes, tips, taxis, ferries, or homestays.</p></details>
<details><summary>Should I pay in VND or my home currency?</summary><p>Choose VND at card terminals and ATMs unless you have confirmed that your specific card gives a better outcome another way. Home-currency conversion can hide an unfavorable rate.</p></details>
<details><summary>How much cash should I carry in Vietnam?</summary><p>Carry enough VND for the next practical stage, not the whole trip: first-night arrival, small daily spending, tips, local transport, and any rural, island, or early-start segment before the next reliable ATM.</p></details>
<details><summary>Do I need to declare cash when entering or leaving Vietnam?</summary><p>Check official sources before travel. As reviewed on July 17, 2026, official references to Circular 15/2011/TT-NHNN point to declaration requirements above USD 5,000 or equivalent in foreign currency cash, or above VND 15,000,000 in Vietnamese dong cash.</p></details>
</div>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where this guide fits in the planning chain</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a> for the full planning order, use <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> for the total budget, check <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before cash-heavy transfers, confirm <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a> before non-refundable bookings, and use <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a> before weather-sensitive rural or island routes.{$sim_esim_sentence}</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

$required_content_markers = [
    'hero marker' => 'vg-money-hero:v1',
    'concierge verdict' => 'vg-money-concierge-verdict',
    'editorial proof panel' => 'vietnamguide/editorial-proof-panel',
    'currency basics' => 'vg-money-currency-basics:v1',
    'photo grid' => 'vg-money-photo-grid:v1',
    'payment decision table' => 'vg-money-payment-decision-table:v1',
    'arrival cash plan' => 'vg-money-arrival-cash-plan:v1',
    'exchange ATM card table' => 'vg-money-exchange-atm-card:v1',
    'ATM checklist' => 'vg-money-atm-checklist:v1',
    'card acceptance' => 'vg-money-card-acceptance:v1',
    'cash declaration' => 'vg-money-cash-declaration:v1',
    'safety mistakes' => 'vg-money-safety-mistakes:v1',
    'live checks' => 'vg-money-live-checks:v1',
    'FAQ' => 'vg-money-faq:v1',
    'related routes shortcode' => 'vg_related_routes',
    'source trail pattern' => 'vietnamguide/source-trail',
    'update log pattern' => 'vietnamguide/update-log',
];

vg_ops_assert_required_content_markers($content, $required_content_markers);
vg_ops_assert_internal_page_links_are_published('Money in Vietnam guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Money in Vietnam: Cash, Cards and ATMs',
    'post_name'      => 'money-cash-cards-atms',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led guide to money in Vietnam for international travelers, including VND cash, cards, ATMs, exchange, arrival buffers, cash declaration thresholds, and live payment checks.',
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
    vg_ops_fail('Could not publish Money in Vietnam guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Money in Vietnam guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Money in Vietnam: Cash, Cards and ATMs Guide');
update_post_meta($page_id, 'rank_math_description', 'Plan money in Vietnam with VND cash, card use, ATM withdrawals, exchange checks, arrival cash, declaration thresholds, payment safety, and live checks.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'money in Vietnam');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Decide how to combine Vietnamese dong cash, cards, ATMs, exchange, and backup payment methods for a Vietnam trip without relying on stale fees or one payment rail.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 17, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Initial Money in Vietnam guide publish with mixed-payment verdict, currency basics, photo proof, cash-card-ATM decision tables, arrival cash planning, ATM checklist, card acceptance matrix, cash declaration thresholds, mistakes, FAQ, source trail, and update log.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam.travel - Currency and payments in Vietnam - https://vietnam.travel/things-to-do/currency-and-payments-vietnam - checked July 17, 2026\nState Bank of Vietnam - Vietnamese Currency - https://sbv.gov.vn/en/vietnamese-currency - checked July 17, 2026\nState Bank of Vietnam - exchange rates reference - https://www.sbv.gov.vn/webcenter/portal/en/home/rm/er - checked July 17, 2026\nVietcombank - foreign exchange rates - https://www.vietcombank.com.vn/en/Personal/Cong-cu-Tien-ich/Ty-gia - checked July 17, 2026\nVietnam Trade Portal - Circular 15/2011/TT-NHNN cash carrying reference - https://www.vietnamtradeportal.gov.vn/index.php?id=663&r=site%2Fdisplay - checked July 17, 2026\nVietnam Customs - cash declaration reference - https://www.customs.gov.vn/index.jsp?aid=206381&cid=4102&pageId=2281 - checked July 17, 2026\nVisa - Global ATM locator - https://www.visa.com/locator/atm - checked July 17, 2026\nWikimedia Commons image record - Ben Thanh Market, 2023 - https://commons.wikimedia.org/wiki/File:Ben_Thanh_Market,_2023_(03).jpg - license checked July 17, 2026\nWikimedia Commons image record - 500.000 Dong Hell Banknote - https://commons.wikimedia.org/wiki/File:500.000_Dong_Hell_Banknote.jpg - license checked July 17, 2026\nWikimedia Commons image record - ATM VPBank, Lieu Giai, Hanoi - https://commons.wikimedia.org/wiki/File:ATM_VPBank,_Li%E1%BB%85u_Giai,_H%C3%A0_N%E1%BB%99i_001.JPG - license checked July 17, 2026\nWikimedia Commons image record - Cho Dong Xuan, Old Quarter, Hanoi - https://commons.wikimedia.org/wiki/File:Cho_Dong_Xuan,_Old_Quarter,_Hanoi,_Vietnam_(5245806573).jpg - license checked July 17, 2026\nWikimedia Commons image record - Noi Bai International Airport T2 Waiting Area - https://commons.wikimedia.org/wiki/File:Noi_Bai_International_Airport_T2_Waiting_Area.jpg - license checked July 17, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'Plan Vietnam payments as a mixed system: VND cash for friction, cards for accepted larger payments, ATMs as controlled refills, and official checks for live rates or border cash rules.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Decision-first mixed-payment framework instead of a stale list of ATM fees.\nConcierge verdict that separates VND cash, cards, bank ATMs, official exchange, arrival cash, and declaration thresholds.\nCurrency basics that teach note handling, damaged-note caution, live exchange-rate discipline, and foreign-cash backup judgment.\nPhoto-led proof grid using licensed market, banknote, ATM, and airport imagery with visible credits.\nPayment decision table for airport arrivals, hotels, markets, tips, rural routes, and late-night cash needs.\nArrival cash plan by traveler situation, including city arrivals, families, late arrivals, rural routes, and premium/private itineraries.\nExchange-versus-ATM-versus-card framework with dynamic currency conversion guidance.\nATM checklist that stays useful even as fees, limits, and bank rules move.\nCard acceptance matrix that protects readers from assuming Vietnam is card-only.\nOfficial cash declaration threshold section citing Circular 15/2011/TT-NHNN context for over USD 5,000 and over VND 15,000,000.\nMoney safety mistakes and FAQ focused on original planning judgment.\nOfficial tourism, State Bank, Vietcombank, Vietnam Trade Portal, customs, Visa ATM locator, and image-license source checks.\nVisible source trail and update log.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Use this first if you still need the full planning order.\nVietnam Travel Cost | /costs/vietnam-travel-cost/ | Build the trip budget before deciding cash and card tactics.\nTransport Within Vietnam | /plan/transport-within-vietnam/ | Check cash needs before airport transfers, ferries, rural movement, and late arrivals.\nVietnam E-Visa | /plan/vietnam-evisa/ | Confirm entry rules before non-refundable bookings.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check season pressure before rural, island, ferry, or weather-sensitive routes.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.{$sim_esim_related_route}\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.\nHanoi in 2 Days | /itineraries/hanoi-in-2-days/ | Build a tight Hanoi stay with arrival recovery, food, culture, weather pivots, and next-transfer energy protected.
Hanoi to Ninh Binh Transport | /plan/hanoi-to-ninh-binh-transport/ | Choose train, limousine van, private car, day tour, or onward transfer by final drop-off, luggage, hotel base, timing, weather, and next-route fragility.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Hero image: Ben Thanh Market by Bahnfrend, CC BY-SA 4.0. Body images: 500.000 dong banknote by Cookie Nguyen, CC BY-SA 4.0; Hanoi ATM by Phan Minh Tuan, CC BY-SA 4.0; Dong Xuan Market by yeowatzup, CC BY 2.0; Noi Bai International Airport T2 waiting area by Sky 269, CC BY-SA 4.0.');
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

vg_ops_refresh_plan_hub();
vg_ops_refresh_costs_hub();
vg_ops_refresh_cost_guide_money_link();

clean_post_cache($page_id);

$updated_permalink = get_permalink($page_id);

vg_ops_log("Published Money in Vietnam guide: {$page_id} {$updated_permalink}");

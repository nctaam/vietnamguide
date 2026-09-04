<?php
/**
 * Publish the Vietnam E-Visa guide using the EEAT template direction.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-vietnam-evisa-guide.php --allow-root
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
    $value = getenv('VG_FORCE_EVISA_GUIDE_REPUBLISH');

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

    vg_ops_fail('Could not resolve a valid WordPress author for the Vietnam E-Visa guide.');
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

function vg_ops_refresh_plan_hub(): void
{
    $hub = get_page_by_path('plan', OBJECT, 'page');

    if (! $hub instanceof WP_Post) {
        vg_ops_log('Skipped Plan hub refresh: plan page was not found.');
        return;
    }

    if ($hub->post_status !== 'publish') {
        vg_ops_log('Skipped Plan hub refresh: plan page is not published.');
        return;
    }

    if (! vg_ops_published_page_exists('plan/vietnam-evisa')) {
        vg_ops_log('Skipped Plan hub refresh: Vietnam E-Visa guide is not published.');
        return;
    }

    $marker = '<!-- vg-evisa-plan-hub-note:v1 -->';
    $block = <<<'HTML'
<!-- wp:group {"className":"vg-hub-note"} -->
<div class="wp-block-group vg-hub-note"><h3>Check entry rules before the route</h3><p>For regulation-sensitive planning, start with the <a href="/plan/vietnam-evisa/">Vietnam E-Visa guide</a> before paying for tight flights, domestic transfers, or non-refundable hotels.</p></div>
<!-- /wp:group -->
HTML;

    vg_ops_assert_internal_page_links_are_published('Plan hub e-visa note', $block);

    $marked_block = $marker . "\n" . $block;

    if (str_contains($hub->post_content, $marked_block)) {
        vg_ops_log('Skipped Plan hub refresh: current e-visa note already present.');
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
            vg_ops_fail('Could not confidently refresh the Plan hub e-visa note.');
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
        vg_ops_fail('Could not refresh Plan hub note: ' . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail('Could not refresh Plan hub note: WordPress returned an empty post ID.');
    }

    vg_ops_log("Refreshed Plan hub e-visa note: {$hub->ID}");
}

$parent = get_page_by_path('plan', OBJECT, 'page');

if (! $parent instanceof WP_Post || $parent->post_status !== 'publish') {
    vg_ops_fail('Published parent page not found: plan');
}

$page = get_page_by_path('plan/vietnam-evisa', OBJECT, 'page');
$allow_republish = vg_ops_force_republish_enabled();

if ($page instanceof WP_Post && $page->post_status !== 'draft' && ! $allow_republish) {
    vg_ops_fail('Vietnam E-Visa guide is not a draft. Set VG_FORCE_EVISA_GUIDE_REPUBLISH=1 to overwrite existing published content.');
}

if ($page instanceof WP_Post) {
    $permalink = get_permalink($page);
    vg_ops_log("Preflight Vietnam E-Visa guide: {$page->ID} {$page->post_status} {$permalink}");
} else {
    vg_ops_log('Preflight Vietnam E-Visa guide: no existing page found; creating a child page under /plan/.');
}

$guide_hero_image = esc_url(set_url_scheme(get_stylesheet_directory_uri(), 'https') . '/assets/images/ha-long-bay-vietnam-hero.jpg');

$content = <<<HTML
<!-- wp:group {"align":"full","className":"vg-guide-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-hero">
<!-- wp:cover {"url":"{$guide_hero_image}","dimRatio":55,"overlayColor":"ink","minHeight":620,"minHeightUnit":"px","isDark":true,"align":"full","className":"vg-guide-hero-cover"} -->
<div class="wp-block-cover alignfull is-dark vg-guide-hero-cover" style="min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="Limestone islands in Ha Long Bay, Vietnam, used as a Vietnam travel planning hero image" src="{$guide_hero_image}" data-object-fit="cover" loading="eager" decoding="async" fetchpriority="high"><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"vg-guide-hero-inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-guide-hero-inner">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Reviewed entry guide - Updated July 15, 2026</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-display vg-guide-title"} -->
<h1 class="wp-block-heading vg-display vg-guide-title">Vietnam E-Visa Guide</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-guide-lede"} -->
<p class="vg-guide-lede">For most international visitors who need a visa, Vietnam e-visa planning should start on the official portal, not with a third-party checkout page. Confirm eligibility, entry type, passport details, border gate, processing time, and your arrival cushion before you pay for anything non-refundable.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-field-note"} -->
<p class="vg-field-note">Field note: the e-visa is an entry document, not a promise that every travel plan around it is sensible.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<a class="vg-image-credit" href="https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg" target="_blank" rel="license noopener">Vyacheslav Argenberg / CC BY 4.0</a>
<!-- /wp:html -->
</div>
</div>
<!-- /wp:cover -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-concierge-verdict","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-concierge-verdict">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge verdict</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-verdict-lede"} -->
<p class="vg-verdict-lede"><strong>Use the official e-visa portal first, then build the route around confirmed entry permission.</strong> The safest planning move is to apply early, match every passport detail exactly, choose the correct entry type and border gate, and avoid arriving on a tight connection before approval is in hand.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><strong>Best for:</strong> travelers who need a Vietnam visa and can use the official electronic visa process for their passport and itinerary.</li>
<li><strong>Avoid assuming:</strong> that a screenshot, payment receipt, agency email, or previous trip rule means you can board or enter this time.</li>
<li><strong>Verify before booking:</strong> official portal URL, eligibility, passport validity and data, entry type, border gate, processing time, fee, and public holiday timing.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/editorial-proof-panel"} /-->

<!-- wp:heading -->
<h2 class="wp-block-heading">What official sources say now</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This guide is deliberately conservative because visa rules can change and individual cases can depend on passport, purpose of travel, route, and data accuracy. As checked on July 15, 2026, official Vietnamese sources point travelers to the newer e-visa domains <a href="https://evisa.gov.vn/" target="_blank" rel="noopener">evisa.gov.vn</a> and <a href="https://thithucdientu.gov.vn/" target="_blank" rel="noopener">thithucdientu.gov.vn</a>. Vietnam's official policy allows e-visas for citizens of all countries and territories, but the live portal and your own passport situation still decide what you can submit.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-evisa-official-basics">
<thead>
<tr><th>Question</th><th>Planning answer</th><th>What to verify live</th></tr>
</thead>
<tbody>
<tr><td data-label="Question">Where should I start?</td><td data-label="Planning answer">Use the official Vietnam National Electronic Visa system domains, not a paid intermediary as your first source of truth.</td><td data-label="What to verify live">The domain, application form, fee screen, status check, and any official notices shown during your session.</td></tr>
<tr><td data-label="Question">How long can an e-visa be valid?</td><td data-label="Planning answer">Official policy records allow e-visas up to 90 days, with single-entry or multiple-entry options.</td><td data-label="What to verify live">Whether that rule applies to your passport, purpose, and entry plan at the time you apply.</td></tr>
<tr><td data-label="Question">What are the official fees?</td><td data-label="Planning answer">The Ministry of Public Security public-service procedure lists USD 25 for single entry and USD 50 for multiple entry.</td><td data-label="What to verify live">The final fee shown on the official payment step, because fees and payment handling can change.</td></tr>
<tr><td data-label="Question">How long does processing take?</td><td data-label="Planning answer">The official procedure states not more than 3 working days after sufficient information and fee are received.</td><td data-label="What to verify live">The current processing notice on the official portal and the status of your exact application.</td></tr>
<tr><td data-label="Question">What do I need to prepare?</td><td data-label="Planning answer">The official procedure names online form information, a portrait image, and a passport bio-page image.</td><td data-label="What to verify live">Current file requirements, image quality rules, passport validity logic, and any portal validation messages.</td></tr>
<tr><td data-label="Question">Can I enter anywhere?</td><td data-label="Planning answer">No. E-visa planning must match an eligible entry and exit point.</td><td data-label="What to verify live">Your exact airport, land border, or seaport on the official entry/exit list before booking the route.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">The e-visa decision table</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The right visa choice is not only a price question. It is a route, timing, passport-data, and risk question. Use this table before you decide whether to apply now, wait, change entry airports, or add travel buffer.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-evisa-decision-table">
<thead>
<tr><th>Situation</th><th>VietnamGuide verdict</th><th>Why it matters</th><th>Before you pay</th></tr>
</thead>
<tbody>
<tr><td data-label="Situation">You have flights but no visa approval</td><td data-label="VietnamGuide verdict">Apply as early as your details are stable.</td><td data-label="Why it matters">Visa timing is a gate on the whole trip, not a last-minute admin task.</td><td data-label="Before you pay">Check passport details, arrival date, entry type, and official processing guidance.</td></tr>
<tr><td data-label="Situation">You may leave and re-enter Vietnam</td><td data-label="VietnamGuide verdict">Consider whether multiple entry is required.</td><td data-label="Why it matters">Side trips to Cambodia, Laos, Thailand, or a cruise routing can make single entry the wrong choice.</td><td data-label="Before you pay">Map every exit and re-entry date before choosing the entry type.</td></tr>
<tr><td data-label="Situation">Your route uses a land border or seaport</td><td data-label="VietnamGuide verdict">Verify the exact border gate before booking.</td><td data-label="Why it matters">A valid e-visa still needs a compatible entry point.</td><td data-label="Before you pay">Match the official border-gate name to your ticket, transfer, or cruise itinerary.</td></tr>
<tr><td data-label="Situation">Your passport expires soon or details changed</td><td data-label="VietnamGuide verdict">Resolve passport risk before the e-visa.</td><td data-label="Why it matters">Wrong passport data can create boarding or arrival problems even when payment succeeded.</td><td data-label="Before you pay">Confirm passport number, nationality, date of birth, name order, expiry, and scan quality.</td></tr>
<tr><td data-label="Situation">You are booking a premium/private trip</td><td data-label="VietnamGuide verdict">Buy flexibility until approval is confirmed.</td><td data-label="Why it matters">Luxury hotels, cruises, guides, and domestic flights can be expensive to move.</td><td data-label="Before you pay">Keep cancellation windows open and avoid arrival-day pressure.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Mistakes to avoid</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Most e-visa problems are not glamorous. They come from small data mismatches, optimistic timing, unclear entry points, or using the wrong website because it looked official enough.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<table class="vg-decision-table vg-evisa-mistake-checks">
<thead>
<tr><th>Mistake</th><th>Why it hurts</th><th>Safer move</th></tr>
</thead>
<tbody>
<tr><td data-label="Mistake">Applying through an agency page before checking the official portal</td><td data-label="Why it hurts">You may pay more, follow stale guidance, or misunderstand what is actually approved.</td><td data-label="Safer move">Start at the official portal, then decide whether you need paid help.</td></tr>
<tr><td data-label="Mistake">Typing passport data manually without a second check</td><td data-label="Why it hurts">A typo can become a travel-day problem.</td><td data-label="Safer move">Compare every field against the passport before payment and before submitting corrections.</td></tr>
<tr><td data-label="Mistake">Choosing single entry when the route exits Vietnam mid-trip</td><td data-label="Why it hurts">A side trip can accidentally break the visa plan.</td><td data-label="Safer move">Build a mini calendar of every Vietnam entry and exit first.</td></tr>
<tr><td data-label="Mistake">Planning to arrive immediately after the expected processing window</td><td data-label="Why it hurts">Working days, holidays, payment issues, or correction requests can remove your cushion.</td><td data-label="Safer move">Keep a buffer and avoid locking non-refundable arrival-night plans too tightly.</td></tr>
<tr><td data-label="Mistake">Assuming all border gates are interchangeable</td><td data-label="Why it hurts">Your approval and actual arrival point need to make sense together.</td><td data-label="Safer move">Verify the official entry/exit point list for your exact airport, land border, or seaport.</td></tr>
</tbody>
</table>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Before-apply checklist</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Run this checklist before payment. It is intentionally practical because the cost of a mistake is not the visa fee; it is the disrupted flight, hotel night, cruise, guide, or family plan around it.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-check-list vg-evisa-before-apply"} -->
<ul class="wp-block-list vg-check-list vg-evisa-before-apply">
<li>Open the official portal directly and confirm the URL before entering passport or payment data.</li>
<li>Check whether your passport and trip purpose can use the official e-visa process.</li>
<li>Match name, date of birth, nationality, passport number, passport expiry, and portrait/passport image quality.</li>
<li>Choose single or multiple entry based on every Vietnam entry and exit in the route.</li>
<li>Verify the exact entry and exit point against your flight, land transfer, cruise, or seaport plan.</li>
<li>Check the official processing target and keep extra days for weekends, public holidays, correction requests, or payment issues.</li>
<li>After approval, save offline copies and verify the approval data against the passport and route before departure.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">How to connect this with your Vietnam plan</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Use the <a href="/plan/">Plan hub</a> for the wider pre-departure sequence. Use the <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost guide</a> to place official visa fees inside your budget. Use the <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam guide</a> if your route timing needs a realistic arrival cushion after the visa is approved.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vg_related_routes]
<!-- /wp:shortcode -->

<!-- wp:pattern {"slug":"vietnamguide/source-trail"} /-->

<!-- wp:pattern {"slug":"vietnamguide/update-log"} /-->
HTML;

vg_ops_assert_internal_page_links_are_published('Vietnam E-Visa guide', $content);

$post_args = [
    'post_type'      => 'page',
    'post_title'     => 'Vietnam E-Visa Guide',
    'post_name'      => 'vietnam-evisa',
    'post_parent'    => (int) $parent->ID,
    'post_status'    => 'publish',
    'post_author'    => vg_ops_publish_author_id($page instanceof WP_Post ? $page : null),
    'post_content'   => $content,
    'post_excerpt'   => 'Evidence-led Vietnam e-visa guide for international visitors, with official-source checks, decision table, mistakes to avoid, and before-apply checklist.',
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
    vg_ops_fail('Could not publish Vietnam E-Visa guide: ' . $result->get_error_message());
}

if ((int) $result <= 0) {
    vg_ops_fail('Could not publish Vietnam E-Visa guide: WordPress returned an empty post ID.');
}

$page_id = (int) $result;

update_post_meta($page_id, 'rank_math_title', 'Vietnam E-Visa Guide: Official Portal, Fees and Mistakes');
update_post_meta($page_id, 'rank_math_description', 'Evidence-led Vietnam e-visa guide for international visitors, with official portal checks, fees, entry type decisions, mistakes to avoid, and before-apply checklist.');
update_post_meta($page_id, 'rank_math_focus_keyword', 'Vietnam e-visa');
update_post_meta($page_id, 'vg_eeat_primary_decision', 'Confirm whether, when, and how to apply for a Vietnam e-visa before booking non-refundable travel around entry permission.');
update_post_meta($page_id, 'vg_eeat_reviewed_guide', '1');
update_post_meta($page_id, 'vg_eeat_written_by', 'VietnamGuide editorial team');
update_post_meta($page_id, 'vg_eeat_reviewed_by', 'VietnamGuide editorial review');
update_post_meta($page_id, 'vg_eeat_last_meaningful_update', 'July 15, 2026');
update_post_meta($page_id, 'vg_eeat_update_summary', 'Published the first Vietnam E-Visa guide with official-source checks, decision table, mistake framework, and before-apply checklist.');
update_post_meta($page_id, 'vg_eeat_sources_checked', "Vietnam National Electronic Visa system - official e-visa portal - https://evisa.gov.vn/ - checked July 15, 2026\nVietnam National Electronic Visa system - alternate official domain - https://thithucdientu.gov.vn/ - checked July 15, 2026\nVietnam Immigration Department - notice directing users to the newer e-visa domains from November 11, 2024 - https://evisa.immigration.gov.vn/web/guest/trang-chu-ttdt - checked July 15, 2026\nMinistry of Public Security public service procedure - e-visa fee, processing target, and application materials - https://dichvucong.bocongan.gov.vn/public/link-to/chi-tiet-thu-tuc?ma-thu-tuc=26277 - checked July 15, 2026\nResolution 127 record - e-visa eligibility policy for all countries and territories - https://vanban.chinhphu.vn/?docid=208477&pageid=27160 - checked July 15, 2026\nVietnam.travel - Vietnam visa requirements page, checked for public-facing policy context and noted as containing some stale legacy e-visa links - https://vietnam.travel/plan-your-trip/visa-requirements - checked July 15, 2026");
update_post_meta($page_id, 'vg_eeat_field_note', 'The e-visa is an entry document, not a promise that every travel plan around it is sensible.');
update_post_meta($page_id, 'vg_eeat_affiliate_status', 'none');
update_post_meta($page_id, 'vg_eeat_evidence_moat', "Official-source-first guidance that separates portal facts from traveler-specific checks.\nSource note that avoids relying on stale legacy e-visa application links.\nDecision table that maps entry type, border gate, passport risk, and route timing.\nMistake framework focused on preventable data, timing, and third-party-site errors.\nBefore-apply checklist designed for real booking decisions, not generic visa copy.\nRelated decision chain that places entry permission inside route, timing, and budget planning.\nVisible source trail and monthly-review discipline for regulation-sensitive content.");
update_post_meta($page_id, 'vg_eeat_related_routes', "Vietnam Travel Guide | /plan/vietnam-travel-guide/ | Place entry permission before route and hotel decisions.\nBest Time to Visit Vietnam | /plan/best-time-to-visit-vietnam/ | Check whether your approved window fits the route.\n10 Days in Vietnam | /itineraries/10-days-in-vietnam/ | Use the route only after entry timing is realistic.\nBest Day Trips from Hanoi | /destinations/best-day-trips-from-hanoi/ | Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.\nHanoi Airport to Old Quarter | /plan/hanoi-airport-to-old-quarter/ | Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, first-night risk, and Old Quarter walking distance.");
update_post_meta($page_id, 'vg_eeat_hero_image_credit', 'Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0.');
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
    vg_ops_fail('Vietnam E-Visa guide was updated but is not published.');
}

vg_ops_refresh_plan_hub();

$updated_permalink = get_permalink($page_id);
vg_ops_log("Published Vietnam E-Visa guide: {$page_id} {$updated_permalink}");

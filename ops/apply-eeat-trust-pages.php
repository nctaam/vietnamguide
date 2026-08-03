<?php
/**
 * Publish VietnamGuide EEAT trust pages and footer links.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-eeat-trust-pages.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_ops_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_ops_fail(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }

    throw new RuntimeException($message);
}

function vg_ops_upsert_page(string $slug, string $title, string $content, string $description): int
{
    $page = get_page_by_path($slug, OBJECT, 'page');
    $post_data = [
        'post_title'     => $title,
        'post_name'      => $slug,
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'post_content'   => $content,
        'post_excerpt'   => $description,
        'comment_status' => 'closed',
        'ping_status'    => 'closed',
    ];

    if ($page instanceof WP_Post) {
        $post_data['ID'] = (int) $page->ID;
        $result = wp_update_post($post_data, true);
    } else {
        $result = wp_insert_post($post_data, true);
    }

    if (is_wp_error($result)) {
        vg_ops_fail("Could not publish {$title}: " . $result->get_error_message());
    }

    if ((int) $result <= 0) {
        vg_ops_fail("Could not publish {$title}: WordPress returned an empty post ID.");
    }

    update_post_meta((int) $result, 'rank_math_title', $title);
    update_post_meta((int) $result, 'rank_math_description', $description);

    vg_ops_log("Published page: {$title}");

    return (int) $result;
}

$pages = [
    'about' => [
        'title' => 'About VietnamGuide.net',
        'description' => 'About VietnamGuide.net, an editorial travel planning guide for international visitors to Vietnam.',
        'content' => '<!-- wp:paragraph {"className":"vg-kicker"} --><p class="vg-kicker">About VietnamGuide.net</p><!-- /wp:paragraph --><!-- wp:paragraph {"className":"vg-hub-lede"} --><p class="vg-hub-lede">VietnamGuide.net helps international visitors plan calmer, better-informed Vietnam trips with clear route logic, destination trade-offs, and practical travel checks.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">What we publish</h2><!-- /wp:heading --><!-- wp:paragraph --><p>We focus on planning decisions that matter before and during a Vietnam trip: when to go, where to go, how long to stay, how to compare routes, how to avoid wasted transfer days, and what to verify before booking.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">How we want to be useful</h2><!-- /wp:heading --><!-- wp:list {"className":"vg-feature-list"} --><ul class="wp-block-list vg-feature-list"><li>Give a verdict before long explanations.</li><li>Show trade-offs, not generic inspiration.</li><li>Separate stable advice from facts that need rechecking.</li><li>Disclose affiliate relationships where they exist.</li></ul><!-- /wp:list -->',
    ],
    'editorial-policy' => [
        'title' => 'Editorial Policy',
        'description' => 'VietnamGuide.net editorial policy for people-first travel planning content, source checks, AI assistance, and review standards.',
        'content' => '<!-- wp:paragraph {"className":"vg-kicker"} --><p class="vg-kicker">Editorial Policy</p><!-- /wp:paragraph --><!-- wp:paragraph {"className":"vg-hub-lede"} --><p class="vg-hub-lede">VietnamGuide.net publishes people-first travel planning guidance. Every important guide should help a real traveler make a clearer decision, not exist only to target a keyword.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Our editorial standards</h2><!-- /wp:heading --><!-- wp:list {"className":"vg-feature-list"} --><ul class="wp-block-list vg-feature-list"><li>Guides should include original judgment, trade-offs, and practical next steps.</li><li>Volatile facts such as visa, safety, transport, prices, and connectivity must be checked against primary or official sources where available.</li><li>Visible update dates should reflect meaningful review or content changes.</li><li>AI tools may assist drafting, organization, and QA, but human editors remain responsible for accuracy, recommendations, and final publication.</li></ul><!-- /wp:list -->',
    ],
    'source-update-policy' => [
        'title' => 'Source and Update Policy',
        'description' => 'How VietnamGuide.net checks sources, handles changing travel facts, and records meaningful content updates.',
        'content' => '<!-- wp:paragraph {"className":"vg-kicker"} --><p class="vg-kicker">Source and Update Policy</p><!-- /wp:paragraph --><!-- wp:paragraph {"className":"vg-hub-lede"} --><p class="vg-hub-lede">Travel facts change. This page explains how VietnamGuide.net treats sources, uncertainty, and updates across guides.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Source priorities</h2><!-- /wp:heading --><!-- wp:list {"className":"vg-feature-list"} --><ul class="wp-block-list vg-feature-list"><li>Official government, airport, transport, tourism, and provider sources are preferred for rules and logistics.</li><li>Commercial sources may be used for availability and product context, but they do not replace editorial judgment.</li><li>When facts cannot be confirmed, guides should say what travelers need to verify before booking.</li></ul><!-- /wp:list --><!-- wp:heading --><h2 class="wp-block-heading">Update dates</h2><!-- /wp:heading --><!-- wp:paragraph --><p>We aim to change visible update dates only after a meaningful review, source check, recommendation change, or content improvement. Typo-only edits do not need to be presented as fresh travel guidance.</p><!-- /wp:paragraph -->',
    ],
    'contact' => [
        'title' => 'Contact',
        'description' => 'Contact VietnamGuide.net for editorial corrections, source updates, partnership questions, or reader feedback.',
        'content' => '<!-- wp:paragraph {"className":"vg-kicker"} --><p class="vg-kicker">Contact</p><!-- /wp:paragraph --><!-- wp:paragraph {"className":"vg-hub-lede"} --><p class="vg-hub-lede">For corrections, source updates, editorial questions, or partnership inquiries, contact VietnamGuide.net by email.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Email: <a href="mailto:contact@vietnamguide.net">contact@vietnamguide.net</a></p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Please include the page URL and the specific fact, recommendation, or source you want us to review.</p><!-- /wp:paragraph -->',
    ],
    'affiliate-review-policy' => [
        'title' => 'Affiliate and Review Policy',
        'description' => 'How VietnamGuide.net handles affiliate links, recommendation criteria, and editorial independence.',
        'content' => '<!-- wp:paragraph {"className":"vg-kicker"} --><p class="vg-kicker">Affiliate and Review Policy</p><!-- /wp:paragraph --><!-- wp:paragraph {"className":"vg-hub-lede"} --><p class="vg-hub-lede">VietnamGuide.net may use affiliate links where they are useful to readers. Affiliate relationships should not decide what we recommend.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Recommendation standards</h2><!-- /wp:heading --><!-- wp:list {"className":"vg-feature-list"} --><ul class="wp-block-list vg-feature-list"><li>Recommendations need independent selection criteria.</li><li>Merchant descriptions should not be copied as editorial reviews.</li><li>Trade-offs and alternatives should be visible where relevant.</li><li>Affiliate links should use sponsored and nofollow attributes through the site helper.</li></ul><!-- /wp:list -->',
    ],
];

foreach ($pages as $slug => $page) {
    vg_ops_upsert_page($slug, $page['title'], $page['content'], $page['description']);
}

$menu = wp_get_nav_menu_object('Footer Legal');

if (! $menu) {
    $menu_id = wp_create_nav_menu('Footer Legal');

    if (is_wp_error($menu_id)) {
        vg_ops_fail('Could not create Footer Legal: ' . $menu_id->get_error_message());
    }
} else {
    $menu_id = (int) $menu->term_id;
}

$existing_items = wp_get_nav_menu_items($menu_id) ?: [];
$existing_paths = [];

foreach ($existing_items as $item) {
    $path = wp_parse_url((string) $item->url, PHP_URL_PATH);
    $existing_paths[untrailingslashit('/' . ltrim((string) $path, '/'))] = true;
}

$footer_items = [
    ['title' => 'Privacy Policy', 'path' => '/privacy-policy/'],
    ['title' => 'Affiliate Disclosure', 'path' => '/affiliate-disclosure/'],
    ['title' => 'Editorial Policy', 'path' => '/editorial-policy/'],
    ['title' => 'Source and Update Policy', 'path' => '/source-update-policy/'],
    ['title' => 'Contact', 'path' => '/contact/'],
];

$position = count($existing_items);

foreach ($footer_items as $item) {
    $path_key = untrailingslashit($item['path']);

    if (isset($existing_paths[$path_key])) {
        continue;
    }

    $position++;
    $item_id = wp_update_nav_menu_item(
        $menu_id,
        0,
        [
            'menu-item-title' => $item['title'],
            'menu-item-url' => home_url($item['path']),
            'menu-item-status' => 'publish',
            'menu-item-type' => 'custom',
            'menu-item-position' => $position,
        ]
    );

    if (is_wp_error($item_id)) {
        vg_ops_fail("Could not add footer item {$item['title']}: " . $item_id->get_error_message());
    }

    if ((int) $item_id <= 0) {
        vg_ops_fail("Could not add footer item {$item['title']}: WordPress returned an empty menu item ID.");
    }
}

$locations = get_nav_menu_locations();
$locations['footer_legal'] = $menu_id;
set_theme_mod('nav_menu_locations', $locations);

flush_rewrite_rules(false);
vg_ops_log('Ensured EEAT trust pages and footer links.');

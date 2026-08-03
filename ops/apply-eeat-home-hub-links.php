<?php
/**
 * Add EEAT trust bands and decision links to published foundation pages.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-eeat-home-hub-links.php --allow-root
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

function vg_ops_page_by_slug(string $slug): WP_Post
{
    $page = get_page_by_path($slug, OBJECT, 'page');

    if (! $page instanceof WP_Post) {
        throw new RuntimeException("Missing page: {$slug}");
    }

    if ('publish' !== $page->post_status) {
        throw new RuntimeException("Page is not published: {$slug}");
    }

    return $page;
}

function vg_ops_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && 'publish' === $page->post_status;
}

function vg_ops_internal_path_from_href(string $href): ?string
{
    $href = trim(html_entity_decode($href, ENT_QUOTES, 'UTF-8'));

    if ('' === $href || str_starts_with($href, '#')) {
        return null;
    }

    if (str_starts_with($href, '/')) {
        $path = parse_url($href, PHP_URL_PATH);
    } elseif (preg_match('/^https?:\/\//i', $href)) {
        $site_host = parse_url(home_url('/'), PHP_URL_HOST);
        $href_host = parse_url($href, PHP_URL_HOST);

        if (! $site_host || ! $href_host || 0 !== strcasecmp($site_host, $href_host)) {
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

    return '' === $path ? 'home' : $path;
}

function vg_ops_assert_internal_page_links_are_published(array $blocks): void
{
    $paths = [];

    foreach ($blocks as $label => $block) {
        preg_match_all('/\shref=(["\'])(.*?)\1/i', $block, $matches);

        foreach ($matches[2] as $href) {
            $path = vg_ops_internal_path_from_href($href);

            if (null === $path) {
                continue;
            }

            $paths[$path] = $label;
        }
    }

    $failures = [];

    foreach ($paths as $path => $label) {
        if ('home' === $path) {
            $front_page_id = (int) get_option('page_on_front');
            $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
        } else {
            $page = get_page_by_path($path, OBJECT, 'page');
        }

        if (! $page instanceof WP_Post || 'publish' !== $page->post_status) {
            $failures[] = "{$label} links to unpublished page path: /{$path}/";
        }
    }

    if ($failures) {
        throw new RuntimeException(implode(PHP_EOL, $failures));
    }

    vg_ops_log('Validated internal page links are published.');
}

function vg_ops_append_once(WP_Post $page, string $marker, string $block): void
{
    if (str_contains($page->post_content, $marker)) {
        vg_ops_log("Skipped existing marker on {$page->post_name}");
        return;
    }

    $result = wp_update_post(
        [
            'ID' => $page->ID,
            'post_content' => rtrim($page->post_content) . "\n\n" . $marker . "\n" . $block,
        ],
        true
    );

    if (is_wp_error($result)) {
        throw new RuntimeException($result->get_error_message());
    }

    if (empty($result)) {
        throw new RuntimeException("Failed to update {$page->post_name}: empty post ID returned");
    }

    vg_ops_log("Updated {$page->post_name}");
}

function vg_ops_content_with_upserted_marked_group(string $content, string $marker, string $block): string
{
    $new_section = $marker . "\n" . $block;

    if (! str_contains($content, $marker)) {
        return rtrim($content) . "\n\n" . $new_section;
    }

    if (1 !== substr_count($content, $marker)) {
        throw new RuntimeException("Unexpected duplicate marker: {$marker}");
    }

    if (str_contains($content, $new_section)) {
        return $content;
    }

    $pattern = '/' . preg_quote($marker, '/') . '\R<!-- wp:group\b.*?<!-- \/wp:group -->/s';
    $updated_content = preg_replace_callback(
        $pattern,
        static fn (): string => $new_section,
        $content,
        1,
        $replacement_count
    );

    if (! is_string($updated_content) || 1 !== $replacement_count) {
        throw new RuntimeException("Could not confidently replace marked group: {$marker}");
    }

    return $updated_content;
}

function vg_ops_upsert_marked_block(WP_Post $page, string $marker, string $block): void
{
    $updated_content = vg_ops_content_with_upserted_marked_group(
        $page->post_content,
        $marker,
        $block
    );

    if ($updated_content === $page->post_content) {
        vg_ops_log("Skipped current marker on {$page->post_name}");
        return;
    }

    $result = wp_update_post(
        [
            'ID' => $page->ID,
            'post_content' => $updated_content,
        ],
        true
    );

    if (is_wp_error($result)) {
        throw new RuntimeException($result->get_error_message());
    }

    if (empty($result)) {
        throw new RuntimeException("Failed to update {$page->post_name}: empty post ID returned");
    }

    vg_ops_log(str_contains($page->post_content, $marker) ? "Refreshed {$page->post_name}" : "Updated {$page->post_name}");
}

$trust_band = <<<'HTML'
<!-- wp:group {"className":"vg-section vg-trust-band","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-section vg-trust-band">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">How VietnamGuide builds trust</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Verdicts first. Sources visible. Updates meaningful.</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Our major guides are designed to show what was checked, what changed, and which trade-offs matter for international visitors planning Vietnam.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><a href="/editorial-policy/">Editorial Policy</a></li>
<li><a href="/source-update-policy/">Source and Update Policy</a></li>
<li><a href="/affiliate-review-policy/">Affiliate and Review Policy</a></li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->
HTML;

$cost_decision_href = vg_ops_published_page_exists('costs/vietnam-travel-cost')
    ? '/costs/vietnam-travel-cost/'
    : '/costs/';

$travel_guide_decision_href = vg_ops_published_page_exists('plan/vietnam-travel-guide')
    ? '/plan/vietnam-travel-guide/'
    : '/plan/';

$travel_guide_decision_label = '/plan/vietnam-travel-guide/' === $travel_guide_decision_href
    ? 'Vietnam travel guide'
    : 'Vietnam trip planning';

$destination_guide_decision_href = vg_ops_published_page_exists('destinations/best-places-to-visit-vietnam')
    ? '/destinations/best-places-to-visit-vietnam/'
    : '/destinations/';

$destination_guide_decision_label = '/destinations/best-places-to-visit-vietnam/' === $destination_guide_decision_href
    ? 'Best places to visit in Vietnam'
    : 'Vietnam destinations';

$evisa_decision_href = vg_ops_published_page_exists('plan/vietnam-evisa')
    ? '/plan/vietnam-evisa/'
    : '/plan/';

$evisa_decision_label = $evisa_decision_href === '/plan/vietnam-evisa/'
    ? 'Vietnam e-visa guide'
    : 'Vietnam entry planning';

$itinerary_decision_href = vg_ops_published_page_exists('itineraries/10-days-in-vietnam')
    ? '/itineraries/10-days-in-vietnam/'
    : '/itineraries/';

$itinerary_decision_label = $itinerary_decision_href === '/itineraries/10-days-in-vietnam/'
    ? '10-day Vietnam itinerary'
    : 'Vietnam itinerary planning';

$itinerary_14_decision_item = vg_ops_published_page_exists('itineraries/14-days-in-vietnam')
    ? '<li><a href="/itineraries/14-days-in-vietnam/">14-day Vietnam itinerary</a></li>'
    : '';

$region_comparison_decision_href = vg_ops_published_page_exists('compare/north-central-south-vietnam')
    ? '/compare/north-central-south-vietnam/'
    : '/compare/';

$region_comparison_decision_label = '/compare/north-central-south-vietnam/' === $region_comparison_decision_href
    ? 'North vs Central vs South Vietnam'
    : 'Compare routes and destinations';

$decision_links = <<<HTML
<!-- wp:group {"className":"vg-section vg-decision-links","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-section vg-decision-links">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Start with a decision</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><a href="{$travel_guide_decision_href}">{$travel_guide_decision_label}</a></li>
<li><a href="{$destination_guide_decision_href}">{$destination_guide_decision_label}</a></li>
<li><a href="{$evisa_decision_href}">{$evisa_decision_label}</a></li>
<li><a href="/plan/best-time-to-visit-vietnam/">Best time to visit Vietnam</a></li>
<li><a href="{$cost_decision_href}">Vietnam travel cost planning</a></li>
<li><a href="{$itinerary_decision_href}">{$itinerary_decision_label}</a></li>
{$itinerary_14_decision_item}
<li><a href="{$region_comparison_decision_href}">{$region_comparison_decision_label}</a></li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->
HTML;

$targets = [
    [
        'slug' => 'home',
        'marker' => '<!-- vg-eeat-trust-band:v1 -->',
        'block' => $trust_band,
        'mode' => 'append_once',
    ],
    [
        'slug' => 'plan',
        'marker' => '<!-- vg-eeat-decision-links:v1 -->',
        'block' => $decision_links,
        'mode' => 'replace_group',
    ],
    [
        'slug' => 'destinations',
        'marker' => '<!-- vg-eeat-trust-band:v1 -->',
        'block' => $trust_band,
        'mode' => 'append_once',
    ],
    [
        'slug' => 'itineraries',
        'marker' => '<!-- vg-eeat-decision-links:v1 -->',
        'block' => $decision_links,
        'mode' => 'replace_group',
    ],
    [
        'slug' => 'compare',
        'marker' => '<!-- vg-eeat-decision-links:v1 -->',
        'block' => $decision_links,
        'mode' => 'replace_group',
    ],
    [
        'slug' => 'costs',
        'marker' => '<!-- vg-eeat-decision-links:v1 -->',
        'block' => $decision_links,
        'mode' => 'replace_group',
    ],
];

$resolved_pages = [];

vg_ops_assert_internal_page_links_are_published(
    [
        'trust band' => $trust_band,
        'decision links' => $decision_links,
    ]
);

foreach ($targets as $target) {
    $resolved_pages[$target['slug']] = vg_ops_page_by_slug($target['slug']);
}

foreach ($targets as $target) {
    if ('replace_group' === $target['mode']) {
        vg_ops_upsert_marked_block(
            $resolved_pages[$target['slug']],
            $target['marker'],
            $target['block']
        );
        continue;
    }

    vg_ops_append_once($resolved_pages[$target['slug']], $target['marker'], $target['block']);
}

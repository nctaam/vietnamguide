<?php
/**
 * Verify public EEAT content requirements.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/verify-eeat-content.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_verify_eeat_wp_cli_available(): bool
{
    return defined('WP_CLI') && WP_CLI;
}

function vg_verify_eeat_finish(array $failures): void
{
    if ($failures !== []) {
        if (vg_verify_eeat_wp_cli_available()) {
            foreach ($failures as $failure) {
                WP_CLI::warning($failure);
            }

            WP_CLI::error('EEAT verification failed.');
        }

        foreach ($failures as $failure) {
            echo 'Warning: ' . $failure . PHP_EOL;
        }

        echo 'EEAT verification failed.' . PHP_EOL;
        exit(1);
    }

    if (vg_verify_eeat_wp_cli_available()) {
        WP_CLI::success('EEAT verification passed.');
        return;
    }

    echo 'EEAT verification passed.' . PHP_EOL;
}

function vg_verify_eeat_value_is_present(mixed $value): bool
{
    if (is_array($value)) {
        return $value !== [];
    }

    if (is_string($value)) {
        return trim($value) !== '';
    }

    return $value !== null && $value !== false;
}

function vg_verify_eeat_value_is_truthy(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return (float) $value > 0;
    }

    if (is_string($value)) {
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    return false;
}

function vg_verify_eeat_page_label(WP_Post $page): string
{
    $path = get_page_uri($page);

    if (! is_string($path) || trim($path) === '') {
        $path = (string) $page->post_name;
    }

    return "{$path} (#{$page->ID}, status: {$page->post_status})";
}

function vg_verify_eeat_add_page_candidate(array &$pages, array &$seen_ids, mixed $page): void
{
    if (! $page instanceof WP_Post) {
        return;
    }

    $page_id = (int) $page->ID;

    if (isset($seen_ids[$page_id])) {
        return;
    }

    $seen_ids[$page_id] = true;
    $pages[] = $page;
}

function vg_verify_eeat_find_page_candidates(array $paths, string $slug): array
{
    $pages = [];
    $seen_ids = [];

    foreach ($paths as $path) {
        vg_verify_eeat_add_page_candidate(
            $pages,
            $seen_ids,
            get_page_by_path($path, OBJECT, 'page')
        );
    }

    $slug_matches = get_posts(
        [
            'post_name__in'  => [$slug],
            'post_type'      => 'page',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ]
    );

    foreach ($slug_matches as $page) {
        vg_verify_eeat_add_page_candidate($pages, $seen_ids, $page);
    }

    return $pages;
}

function vg_verify_eeat_select_published_page(array $pages): ?WP_Post
{
    foreach ($pages as $page) {
        if ($page instanceof WP_Post && $page->post_status === 'publish') {
            return $page;
        }
    }

    return null;
}

function vg_verify_eeat_require_published_page(array $paths, string $slug, string $label, array &$failures): ?WP_Post
{
    $candidates = vg_verify_eeat_find_page_candidates($paths, $slug);
    $page = vg_verify_eeat_select_published_page($candidates);

    if ($page instanceof WP_Post) {
        return $page;
    }

    if ($candidates === []) {
        $failures[] = "Required published page not found: {$slug}";
    } else {
        $candidate_labels = array_map('vg_verify_eeat_page_label', $candidates);
        $failures[] = "{$label} was found but no candidate is published: " . implode('; ', $candidate_labels);
    }

    return null;
}

function vg_verify_eeat_require_published_path(string $path, string $label, array &$failures): ?WP_Post
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post) {
        $failures[] = "Required published page not found: {$path}";
        return null;
    }

    if ($page->post_status !== 'publish') {
        $failures[] = "{$label} is not published: " . vg_verify_eeat_page_label($page);
        return null;
    }

    return $page;
}

function vg_verify_eeat_rendered_content_text(WP_Post $page): string
{
    $previous_post = $GLOBALS['post'] ?? null;

    $GLOBALS['post'] = $page;
    setup_postdata($page);

    $content = apply_filters('the_content', (string) $page->post_content);

    wp_reset_postdata();

    if ($previous_post instanceof WP_Post) {
        $GLOBALS['post'] = $previous_post;
    } else {
        unset($GLOBALS['post']);
    }

    return is_string($content) ? $content : '';
}

function vg_verify_eeat_public_evidence_text(WP_Post $page): string
{
    $parts = [vg_verify_eeat_rendered_content_text($page)];

    foreach (
        [
            'vg_eeat_primary_decision',
            'vg_eeat_sources_checked',
            'vg_eeat_field_note',
            'vg_eeat_evidence_moat',
            'vg_eeat_related_routes',
            'vg_eeat_hero_image_credit',
            'vg_eeat_update_summary',
            'vg_eeat_last_meaningful_update',
        ] as $meta_key
    ) {
        $meta_value = get_post_meta((int) $page->ID, $meta_key, true);

        if (is_string($meta_value) && trim($meta_value) !== '') {
            $parts[] = $meta_value;
        }
    }

    return implode("\n", $parts);
}

function vg_verify_eeat_require_content_groups(WP_Post $page, string $label, array $required_content_groups, array &$failures): void
{
    $content = vg_verify_eeat_public_evidence_text($page);

    foreach ($required_content_groups as $group_label => $needles) {
        $found = false;

        foreach ($needles as $needle) {
            if (str_contains($content, $needle)) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            $failures[] = "{$label} is missing required EEAT content: {$group_label}";
        }
    }
}

function vg_verify_eeat_require_public_evidence_all(WP_Post $page, string $label, string $group_label, array $needles, array &$failures): void
{
    $content = vg_verify_eeat_public_evidence_text($page);

    foreach ($needles as $needle) {
        if (! str_contains($content, $needle)) {
            $failures[] = "{$label} is missing required EEAT evidence for {$group_label}: {$needle}";
        }
    }
}

function vg_verify_eeat_require_rendered_content_all(WP_Post $page, string $label, string $group_label, array $needles, array &$failures): void
{
    $content = vg_verify_eeat_rendered_content_text($page);

    foreach ($needles as $needle) {
        if (! str_contains($content, $needle)) {
            $failures[] = "{$label} is missing required visible content for {$group_label}: {$needle}";
        }
    }
}

function vg_verify_eeat_require_content_occurrence_count(WP_Post $page, string $label, string $needle, int $minimum_count, array &$failures): void
{
    $content = vg_verify_eeat_rendered_content_text($page);
    $count = substr_count($content, $needle);

    if ($count < $minimum_count) {
        $failures[] = "{$label} should contain {$needle} at least {$minimum_count} times; found {$count}.";
    }
}

function vg_verify_eeat_require_raw_content(WP_Post $page, string $label, string $needle, array &$failures): void
{
    if (! str_contains((string) $page->post_content, $needle)) {
        $failures[] = "{$label} is missing required raw content: {$needle}";
    }
}

function vg_verify_eeat_meta_contains_noindex(mixed $value): bool
{
    if (is_array($value)) {
        foreach ($value as $item) {
            if (vg_verify_eeat_meta_contains_noindex($item)) {
                return true;
            }
        }

        return false;
    }

    return is_string($value) && str_contains(strtolower($value), 'noindex');
}

function vg_verify_eeat_require_url_title_canonical_index(WP_Post $page, string $label, string $expected_path, string $expected_title, array &$failures): void
{
    $expected_path = trim($expected_path, '/');
    $page_path = trim((string) get_page_uri($page), '/');

    if ($page_path !== $expected_path) {
        $failures[] = "{$label} has unexpected page URL path: /{$page_path}/; expected /{$expected_path}/";
    }

    if ((string) $page->post_title !== $expected_title) {
        $failures[] = "{$label} has unexpected page title: {$page->post_title}; expected {$expected_title}";
    }

    $permalink = get_permalink($page);
    $permalink_path = is_string($permalink) ? trim((string) parse_url($permalink, PHP_URL_PATH), '/') : '';

    if ($permalink_path !== $expected_path) {
        $failures[] = "{$label} canonical permalink resolves to /{$permalink_path}/; expected /{$expected_path}/";
    }

    foreach (['rank_math_canonical_url', '_rank_math_canonical_url'] as $canonical_meta_key) {
        $canonical_url = trim((string) get_post_meta((int) $page->ID, $canonical_meta_key, true));

        if ($canonical_url === '') {
            continue;
        }

        $canonical_path = trim((string) parse_url($canonical_url, PHP_URL_PATH), '/');

        if ($canonical_path !== $expected_path) {
            $failures[] = "{$label} {$canonical_meta_key} points to /{$canonical_path}/; expected /{$expected_path}/";
        }
    }

    foreach (['rank_math_robots', '_rank_math_robots', '_yoast_wpseo_meta-robots-noindex', '_genesis_noindex'] as $robots_meta_key) {
        $robots_meta = get_post_meta((int) $page->ID, $robots_meta_key, true);
        $is_legacy_noindex_flag = in_array($robots_meta_key, ['_yoast_wpseo_meta-robots-noindex', '_genesis_noindex'], true)
            && vg_verify_eeat_value_is_truthy($robots_meta);

        if ($is_legacy_noindex_flag || vg_verify_eeat_meta_contains_noindex($robots_meta)) {
            $failures[] = "{$label} is marked noindex by metadata: {$robots_meta_key}";
        }
    }
}

function vg_verify_eeat_require_page_sitemap_url(string $expected_path, string $label, array &$failures): void
{
    static $page_sitemap_body = null;
    static $page_sitemap_error = null;

    $expected_url = home_url('/' . trim($expected_path, '/') . '/');

    if ($page_sitemap_body === null && $page_sitemap_error === null) {
        $sitemap_url = home_url('/page-sitemap.xml');
        $response = wp_remote_get(
            $sitemap_url,
            [
                'timeout'     => 20,
                'redirection' => 3,
            ]
        );

        if (is_wp_error($response)) {
            $page_sitemap_error = $response->get_error_message();
        } else {
            $status_code = (int) wp_remote_retrieve_response_code($response);
            $body = (string) wp_remote_retrieve_body($response);

            if ($status_code !== 200 || trim($body) === '') {
                $page_sitemap_error = "HTTP {$status_code} or empty body from {$sitemap_url}";
            } else {
                $page_sitemap_body = $body;
            }
        }
    }

    if ($page_sitemap_error !== null) {
        $failures[] = "{$label} page sitemap could not be fetched: {$page_sitemap_error}";
        return;
    }

    $count = substr_count((string) $page_sitemap_body, $expected_url);

    if ($count !== 1) {
        $failures[] = "{$label} page sitemap should include {$expected_url} exactly once; found {$count}.";
    }
}

function vg_verify_eeat_require_meta(WP_Post $page, string $label, array $required_meta, array &$failures): void
{
    foreach ($required_meta as $meta_key) {
        $meta_value = get_post_meta((int) $page->ID, $meta_key, true);

        if (in_array($meta_key, ['vg_eeat_reviewed_guide', '_generate-disable-headline'], true) && ! vg_verify_eeat_value_is_truthy($meta_value)) {
            $failures[] = "{$label} metadata must be truthy: {$meta_key}";
            continue;
        }

        if (! vg_verify_eeat_value_is_present($meta_value)) {
            $failures[] = "{$label} metadata is empty: {$meta_key}";
        }
    }
}

function vg_verify_eeat_require_related_route_meta(array $targets, string $route_href, string $route_label, array &$failures, bool $require_exact_label = false): void
{
    foreach ($targets as $path => $label) {
        $page = get_page_by_path((string) $path, OBJECT, 'page');

        if (! $page instanceof WP_Post) {
            $failures[] = "{$route_label} inbound related-route target was not found: {$path}";
            continue;
        }

        if ($page->post_status !== 'publish') {
            $failures[] = "{$route_label} inbound related-route target is not published: " . vg_verify_eeat_page_label($page);
            continue;
        }

        $related_routes = (string) get_post_meta((int) $page->ID, 'vg_eeat_related_routes', true);
        $lines = preg_split('/\R+/', trim($related_routes)) ?: [];
        $matching_line = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));

            if (count($parts) >= 2 && $parts[1] === $route_href) {
                $matching_line = $line;
                break;
            }
        }

        if ($matching_line === null) {
            $failures[] = "{$label} related-route metadata is missing structured {$route_label} line with href: {$route_href}";
            continue;
        }

        $matching_parts = array_map('trim', explode('|', $matching_line));

        if (count($matching_parts) < 3 || $matching_parts[0] === '' || $matching_parts[2] === '') {
            $failures[] = "{$label} related-route metadata has malformed {$route_label} line. Expected: Label | {$route_href} | reason";
            continue;
        }

        if ($require_exact_label && $matching_parts[0] !== $route_label) {
            $failures[] = "{$label} related-route metadata has unexpected label for {$route_href}: {$matching_parts[0]}; expected {$route_label}";
        }
    }
}

function vg_verify_eeat_require_own_related_route_meta(WP_Post $page, string $label, array $expected_routes, array &$failures): void
{
    $related_routes = (string) get_post_meta((int) $page->ID, 'vg_eeat_related_routes', true);
    $lines = preg_split('/\R+/', trim($related_routes)) ?: [];
    $seen_hrefs = [];

    foreach ($lines as $line_number => $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        $parts = array_map('trim', explode('|', $line));

        if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
            $failures[] = "{$label} outbound related-route metadata line " . ((int) $line_number + 1) . ' is malformed. Expected: Label | /path/ | reason';
            continue;
        }

        $href = $parts[1];

        if (isset($seen_hrefs[$href])) {
            $failures[] = "{$label} outbound related-route metadata duplicates href: {$href}";
            continue;
        }

        $seen_hrefs[$href] = $line;

        $path = vg_verify_eeat_internal_path_from_href($href);

        if ($path === null) {
            $failures[] = "{$label} outbound related-route metadata uses a non-internal href: {$href}";
            continue;
        }

        $linked_page = $path === 'home'
            ? vg_verify_eeat_homepage($failures)
            : get_page_by_path($path, OBJECT, 'page');

        if (! $linked_page instanceof WP_Post || $linked_page->post_status !== 'publish') {
            $failures[] = "{$label} outbound related-route metadata links to unpublished internal path: /{$path}/";
        }
    }

    foreach ($expected_routes as $href => $route_label) {
        if (! isset($seen_hrefs[$href])) {
            $failures[] = "{$label} outbound related-route metadata is missing {$route_label} href: {$href}";
        }
    }
}

function vg_verify_eeat_require_affiliate_consistency(WP_Post $page, string $label, array &$failures): void
{
    $content = vg_verify_eeat_rendered_content_text($page);

    if (! str_contains($content, 'vg-affiliate-link')) {
        return;
    }

    $affiliate_status = trim((string) get_post_meta((int) $page->ID, 'vg_eeat_affiliate_status', true));

    if ($affiliate_status === 'none') {
        $failures[] = "{$label} has affiliate-link markup while affiliate status is none.";
    }

    preg_match_all('/<a\b(?=[^>]*class=(["\'][^"\']*\bvg-affiliate-link\b[^"\']*["\']))[^>]*>/i', $content, $matches);

    foreach ($matches[0] as $anchor) {
        if (! preg_match('/\brel\s*=\s*(["\'])(.*?)\1/i', $anchor, $rel_match)) {
            $failures[] = "{$label} affiliate link is missing rel disclosure attributes.";
            continue;
        }

        $rel_tokens = preg_split('/\s+/', strtolower(trim((string) $rel_match[2]))) ?: [];

        foreach (['sponsored', 'nofollow'] as $required_rel) {
            if (! in_array($required_rel, $rel_tokens, true)) {
                $failures[] = "{$label} affiliate link is missing rel token: {$required_rel}";
            }
        }
    }
}

function vg_verify_eeat_required_guide_content_groups(): array
{
    return [
        'editorial proof panel' => [
            'vg-proof-panel',
        ],
        'source trail' => [
            'vg-source-trail',
        ],
        'update log' => [
            'vg-update-log',
        ],
        'related routes' => [
            'vg-related-routes',
        ],
        'concierge verdict' => [
            'vg-concierge-verdict',
        ],
    ];
}

function vg_verify_eeat_required_guide_meta(): array
{
    return [
        'rank_math_title',
        'rank_math_description',
        'rank_math_focus_keyword',
        'vg_eeat_primary_decision',
        'vg_eeat_reviewed_guide',
        'vg_eeat_written_by',
        'vg_eeat_reviewed_by',
        'vg_eeat_sources_checked',
        'vg_eeat_field_note',
        'vg_eeat_affiliate_status',
        'vg_eeat_evidence_moat',
        'vg_eeat_related_routes',
        'vg_eeat_update_summary',
        'vg_eeat_last_meaningful_update',
        '_generate-disable-headline',
    ];
}

function vg_verify_eeat_verify_published_guide(
    array $paths,
    string $slug,
    string $label,
    array $required_content_groups,
    array $required_meta,
    array &$failures
): ?WP_Post {
    $page = vg_verify_eeat_require_published_page($paths, $slug, $label, $failures);

    if (! $page instanceof WP_Post) {
        return null;
    }

    vg_verify_eeat_require_content_groups($page, $label, $required_content_groups, $failures);
    vg_verify_eeat_require_meta($page, $label, $required_meta, $failures);

    return $page;
}

function vg_verify_eeat_homepage(array &$failures): ?WP_Post
{
    $front_page_id = (int) get_option('page_on_front');
    $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');

    if (! $page instanceof WP_Post) {
        $failures[] = 'Homepage is not configured or Home page is missing.';
        return null;
    }

    if ($page->post_status !== 'publish') {
        $failures[] = 'Homepage is not published: ' . vg_verify_eeat_page_label($page);
        return null;
    }

    return $page;
}

function vg_verify_eeat_internal_path_from_href(string $href): ?string
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

function vg_verify_eeat_is_external_href(string $href): bool
{
    $href = trim(html_entity_decode($href, ENT_QUOTES, 'UTF-8'));

    if ($href === '' || str_starts_with($href, '#') || preg_match('/^(mailto|tel):/i', $href)) {
        return false;
    }

    if (str_starts_with($href, '/')) {
        return false;
    }

    if (! preg_match('/^https?:\/\//i', $href)) {
        return false;
    }

    $site_host = parse_url(home_url('/'), PHP_URL_HOST);
    $href_host = parse_url($href, PHP_URL_HOST);

    if (! $href_host) {
        return false;
    }

    if ($site_host && strcasecmp($site_host, $href_host) === 0) {
        return false;
    }

    return true;
}

function vg_verify_eeat_require_visible_external_body_link_budget(WP_Post $page, string $label, int $minimum, int $maximum, array &$failures): void
{
    $content = vg_verify_eeat_rendered_content_text($page);
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $hrefs = [];

    foreach ($matches[2] as $href) {
        if (vg_verify_eeat_is_external_href($href)) {
            $hrefs[] = trim(html_entity_decode($href, ENT_QUOTES, 'UTF-8'));
        }
    }

    $count = count($hrefs);

    if ($count < $minimum || $count > $maximum) {
        $failures[] = sprintf(
            '%s visible external body-link budget should be between %d and %d; found %d: %s',
            $label,
            $minimum,
            $maximum,
            $count,
            implode(' | ', array_slice($hrefs, 0, 8))
        );
    }
}

function vg_verify_eeat_require_internal_links_published(WP_Post $page, string $label, array &$failures): void
{
    $content = vg_verify_eeat_rendered_content_text($page);

    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];
    foreach ($matches[2] as $href) {
        $path = vg_verify_eeat_internal_path_from_href($href);

        if ($path !== null) {
            $paths[$path] = true;
        }
    }

    foreach (array_keys($paths) as $path) {
        if ($path === 'home') {
            $linked_page = vg_verify_eeat_homepage($failures);
        } else {
            $linked_page = get_page_by_path($path, OBJECT, 'page');
        }

        if (! $linked_page instanceof WP_Post || $linked_page->post_status !== 'publish') {
            $failures[] = "{$label} links to unpublished internal path: /{$path}/";
        }
    }
}

$failures = [];

$required_page_paths = [
    'about',
    'editorial-policy',
    'source-update-policy',
    'contact',
    'affiliate-review-policy',
];

foreach ($required_page_paths as $path) {
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post) {
        $failures[] = "Required published page not found: {$path}";
        continue;
    }

    if ($page->post_status !== 'publish') {
        $failures[] = 'Required page is not published: ' . vg_verify_eeat_page_label($page);
    }
}

$homepage = vg_verify_eeat_homepage($failures);

if ($homepage instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $homepage,
        'Homepage',
        [
            'premium homepage marker' => [
                'vg-homepage-premium:v1',
            ],
            'decision-first hero' => [
                'Vietnam Travel Guide for International Visitors',
            ],
            'hero proof list' => [
                'vg-home-hero-proof',
            ],
            'source update snapshot marker' => [
                'vg-home-source-update-snapshot:v1',
            ],
            'source update snapshot framing' => [
                'Source and update snapshot',
            ],
            'source policy link' => [
                'Source and Update Policy',
            ],
            'image credit policy' => [
                'Image credit policy',
            ],
            'homepage update summary' => [
                'Homepage systemized as a template-first concierge hub',
            ],
            'decision spine' => [
                'vg-home-decision-spine',
            ],
            'planning map marker' => [
                'vg-home-planning-map:v1',
            ],
            'planning map dates state' => [
                'I have dates, but no route.',
            ],
            'planning map entry state' => [
                'I have flights and need entry checks.',
            ],
            'planning map cost state' => [
                'I know the route, but not the real cost.',
            ],
            'planning map region state' => [
                'I am choosing between north, central, and south.',
            ],
            'planning map one-week route state' => [
                'I have seven days and need a one-week route that does not feel thin.',
            ],
            'planning map central base state' => [
                'I am choosing whether Da Nang or Hoi An should anchor central Vietnam.',
            ],
            'planning map heritage base state' => [
                'I am choosing whether Hoi An or Hue deserves protected central Vietnam time.',
            ],
            'planning map Da Nang guide state' => [
                'I am deciding whether Da Nang should be the airport-and-beach city base.',
            ],
            'planning map bay cruise state' => [
                'I am deciding whether the northern bay cruise is worth the time.',
            ],
            'planning map Ninh Binh day trip overnight state' => [
                'I need to decide whether Ninh Binh is a day trip or an overnight.',
            ],
            'planning map Ninh Binh stays state' => [
                'I need to choose where to stay in Ninh Binh.',
            ],
            'planning map Hanoi to Ninh Binh transport state' => [
                'I know Ninh Binh belongs in the route and need the right transfer from Hanoi.',
            ],
            'planning map Cat Ba island state' => [
                'I am deciding whether Cat Ba should be a base or a skip.',
            ],
            'planning map Bai Tu Long quieter bay state' => [
                'I am deciding whether Bai Tu Long is worth the extra cruise friction.',
            ],
            'planning map beach chapter state' => [
                'I am deciding whether beach time actually belongs in the route.',
            ],
            'planning map island shortlist state' => [
                'I am choosing which Vietnam island actually fits the trip.',
            ],
            'planning map trimming state' => [
                'I am trimming an overfull itinerary.',
            ],
            'reviewed guide shelf' => [
                'vg-home-guide-shelf',
            ],
            'guide shelf framing' => [
                'Reviewed guides for the decisions most likely to cost time or money.',
            ],
            'homepage transport guide row' => [
                'Transport Within Vietnam',
            ],
            'homepage money guide row' => [
                'Money in Vietnam',
            ],
            'homepage SIM/eSIM guide row' => [
                'SIM and eSIM in Vietnam',
            ],
            'homepage safety guide row' => [
                'Safety and Scams in Vietnam',
            ],
            'homepage health insurance guide row' => [
                'Health and Travel Insurance for Vietnam',
            ],
            'homepage heritage guide row' => [
                'UNESCO Heritage Sites in Vietnam',
            ],
            'homepage Ninh Binh guide row' => [
                'Ninh Binh Travel Guide',
            ],
            'homepage Ninh Binh guide href' => [
                'href="/destinations/ninh-binh-travel-guide/"',
            ],
            'homepage Ninh Binh day trip overnight guide row' => [
                'Ninh Binh Day Trip vs Overnight',
            ],
            'homepage Ninh Binh day trip overnight guide href' => [
                'href="/compare/ninh-binh-day-trip-vs-overnight/"',
            ],
            'homepage Ninh Binh day trip overnight route verdict' => [
                'Upgrade Ninh Binh to overnight when morning control is worth more than one more attraction.',
            ],
            'homepage Ninh Binh stays guide row' => [
                'Where to Stay in Ninh Binh',
            ],
            'homepage Ninh Binh stays guide href' => [
                'href="/destinations/where-to-stay-in-ninh-binh/"',
            ],
            'homepage Ninh Binh stays route verdict' => [
                'Use Tam Coc as the default Ninh Binh base unless another job is clearer.',
            ],
            'homepage Hanoi to Ninh Binh transport guide row' => [
                'Hanoi to Ninh Binh Transport',
            ],
            'homepage Hanoi to Ninh Binh transport guide href' => [
                'href="/plan/hanoi-to-ninh-binh-transport/"',
            ],
            'homepage Hanoi to Ninh Binh transport route verdict' => [
                'Choose Hanoi to Ninh Binh transport by drop-off control, not just ticket price.',
            ],
            'planning map Tam Coc guide state' => [
                'I need Tam Coc to be a calm Ninh Binh base, not just a boat stop.',
            ],
            'homepage Tam Coc guide row' => [
                'Tam Coc Travel Guide',
            ],
            'homepage Tam Coc guide href' => [
                'href="/destinations/tam-coc-travel-guide/"',
            ],
            'homepage Tam Coc route verdict' => [
                'Use Tam Coc when countryside rhythm matters more than checklist certainty.',
            ],
            'planning map Ninh Binh to Ha Long transfer state' => [
                'I need to get from Ninh Binh to Ha Long Bay without missing the cruise.',
            ],
            'homepage Ninh Binh to Ha Long transfer guide row' => [
                'Ninh Binh to Ha Long Bay Transfer',
            ],
            'homepage Ninh Binh to Ha Long transfer guide href' => [
                'href="/plan/ninh-binh-to-ha-long-bay-transfer/"',
            ],
            'homepage Ninh Binh to Ha Long transfer route verdict' => [
                'Confirm the exact bay port before choosing the Ninh Binh transfer.',
            ],
            'homepage Ha Long Bay guide row' => [
                'Ha Long Bay Travel Guide',
            ],
            'homepage Ha Long Bay guide href' => [
                'href="/destinations/ha-long-bay-travel-guide/"',
            ],
            'homepage Ha Long/Lan Ha comparison row' => [
                'Ha Long Bay vs Lan Ha Bay',
            ],
            'homepage Ha Long/Lan Ha comparison href' => [
                'href="/compare/ha-long-bay-vs-lan-ha-bay/"',
            ],
            'homepage Da Nang/Hoi An comparison row' => [
                'Da Nang vs Hoi An',
            ],
            'homepage Da Nang/Hoi An comparison href' => [
                'href="/compare/da-nang-vs-hoi-an/"',
            ],
            'homepage Hoi An/Hue comparison row' => [
                'Hoi An vs Hue',
            ],
            'homepage Hoi An/Hue comparison href' => [
                'href="/compare/hoi-an-vs-hue/"',
            ],
            'homepage Cat Ba guide row' => [
                'Cat Ba Travel Guide',
            ],
            'homepage Cat Ba guide href' => [
                'href="/destinations/cat-ba-travel-guide/"',
            ],
            'homepage Bai Tu Long guide row' => [
                'Bai Tu Long Bay Guide',
            ],
            'homepage Bai Tu Long guide href' => [
                'href="/destinations/bai-tu-long-bay-guide/"',
            ],
            'homepage Best Beaches guide row' => [
                'Best Beaches in Vietnam',
            ],
            'homepage Best Beaches guide href' => [
                'href="/destinations/best-beaches-in-vietnam/"',
            ],
            'homepage Best Islands guide row' => [
                'Best Islands in Vietnam',
            ],
            'homepage Best Islands guide href' => [
                'href="/destinations/best-islands-in-vietnam/"',
            ],
            'homepage Con Dao guide row' => [
                'Con Dao Travel Guide',
            ],
            'homepage Con Dao guide href' => [
                'href="/destinations/con-dao-travel-guide/"',
            ],
            'homepage Con Dao planning row' => [
                'I need a Con Dao decision, not just a beach name.',
            ],
            'homepage Con Dao route verdict' => [
                'Use Con Dao when the route needs quiet premium nature, historic memory, or a southern slow chapter.',
            ],
            'homepage Phu Quoc guide row' => [
                'Phu Quoc Travel Guide',
            ],
            'homepage Phu Quoc guide href' => [
                'href="/destinations/phu-quoc-travel-guide/"',
            ],
            'homepage Phu Quoc/Nha Trang comparison row' => [
                'Phu Quoc vs Nha Trang',
            ],
            'homepage Phu Quoc/Nha Trang comparison href' => [
                'href="/compare/phu-quoc-vs-nha-trang/"',
            ],
            'homepage Phu Quoc/Nha Trang planning row' => [
                'I am choosing between Phu Quoc island time and Nha Trang city-beach time.',
            ],
            'homepage Phu Quoc/Nha Trang route verdict' => [
                'Compare Phu Quoc and Nha Trang when beach time is the fork.',
            ],
            'homepage Mui Ne/Nha Trang comparison row' => [
                'Mui Ne vs Nha Trang',
            ],
            'homepage Mui Ne/Nha Trang comparison href' => [
                'href="/compare/mui-ne-vs-nha-trang/"',
            ],
            'homepage Mui Ne/Nha Trang planning row' => [
                'I am choosing between Mui Ne wind-sport coast and Nha Trang city beach.',
            ],
            'homepage Mui Ne/Nha Trang route verdict' => [
                'Use Mui Ne vs Nha Trang when south-central beach time needs a sport-or-city answer.',
            ],
            'homepage Nha Trang planning row' => [
                'I need to know whether Nha Trang should be the active coast base.',
            ],
            'homepage Nha Trang route verdict' => [
                'Use Nha Trang when the beach should remain active, urban, and connected.',
            ],
            'homepage Nha Trang guide row' => [
                'Nha Trang Travel Guide',
            ],
            'homepage Nha Trang guide href' => [
                'href="/destinations/nha-trang-travel-guide/"',
            ],
            'homepage Phu Quoc planning row' => [
                'I need a Phu Quoc decision, not just a beach name.',
            ],
            'homepage Phu Quoc route verdict' => [
                'Use Phu Quoc when the route needs winter sun, resort ease, or a southern recovery chapter.',
            ],
            'homepage Da Nang guide row' => [
                'Da Nang Travel Guide',
            ],
            'homepage Da Nang guide href' => [
                'href="/destinations/da-nang-travel-guide/"',
            ],
            'homepage 7 Days guide row' => [
                '7 Days in Vietnam',
            ],
            'homepage 7 Days guide href' => [
                'href="/itineraries/7-days-in-vietnam/"',
            ],
            'homepage 21 Days guide row' => [
                '21 Days in Vietnam',
            ],
            'homepage 21 Days guide href' => [
                'href="/itineraries/21-days-in-vietnam/"',
            ],
            'homepage three-week phrasing' => [
                'three weeks',
            ],
            'homepage full-country route phrasing' => [
                'full-country route',
            ],
            'homepage one-week route framing' => [
                'one-week route',
                'The shortest serious first-trip answer',
            ],
            'homepage Hanoi guide row' => [
                'Best Things to Do in Hanoi',
            ],
            'homepage Hanoi Travel Guide row' => [
                'Hanoi Travel Guide',
            ],
            'homepage Hanoi Travel Guide href' => [
                'href="/destinations/hanoi-travel-guide/"',
            ],
            'homepage Hoi An guide row' => [
                'Best Things to Do in Hoi An',
            ],
            'homepage Hoi An guide href' => [
                'href="/destinations/best-things-to-do-in-hoi-an/"',
            ],
            'homepage Hue guide row' => [
                'Best Things to Do in Hue',
            ],
            'homepage Hue guide href' => [
                'href="/destinations/best-things-to-do-in-hue/"',
            ],
            'concierge filter marker' => [
                'vg-home-concierge-filter:v1',
            ],
            'concierge filter framing' => [
                'A premium Vietnam route earns its movement.',
            ],
            'route verdict marker' => [
                'vg-home-route-verdict:v1',
            ],
            'route verdict framing' => [
                'Default route verdict',
            ],
            'route verdict 7 days' => [
                'Read the 7-day guide',
            ],
            'route verdict 10 days' => [
                'Read the 10-day guide',
            ],
            'route verdict 14 days' => [
                'Read the 14-day guide',
            ],
            'route verdict region unsure' => [
                'Region unsure',
            ],
            'route verdict northern scenery' => [
                'Northern scenery',
            ],
            'route verdict beach chapter' => [
                'Choose a beach only when it improves the route more than another inland stop would.',
            ],
            'route verdict island chapter' => [
                'Choose an island by route job, not by a prettiest-islands list.',
            ],
            'route verdict Da Nang guide' => [
                'Use Da Nang when central Vietnam needs beach, airport, city, and day-trip control.',
            ],
            'route verdict Hoi An/Hue guide' => [
                'Choose Hoi An or Hue by whether the route needs atmosphere or imperial depth.',
            ],
            'route verdict first-trip guidance' => [
                'Protect the route first, then decide whether extra places still earn their keep.',
            ],
            'related route hub marker' => [
                'vg-home-related-routes:v1',
            ],
            'related route hub framing' => [
                'Use the next guide by route pressure, not by curiosity.',
            ],
            'related route one-week route' => [
                'Triage one week',
            ],
            'related route bay cruise' => [
                'Audit the cruise',
            ],
            'related route beach chapter' => [
                'Audit the beach route',
            ],
            'related route island chapter' => [
                'Audit the island route',
            ],
            'related route Mui Ne/Nha Trang guide' => [
                'Audit the south-central fork',
            ],
            'related route Da Nang guide' => [
                'airport-and-beach city base judgment',
            ],
            'related route Hoi An/Hue guide' => [
                'Use Hoi An vs Hue when central Vietnam needs a real heritage answer.',
            ],
            'route photo proof' => [
                'vg-home-photo-grid',
            ],
            'homepage Hanoi image credit' => [
                'Alex 69200 vx',
            ],
            'homepage Trang An image credit' => [
                'Jakub Halun',
            ],
            'homepage Lan Ha image credit' => [
                'Saaremees',
                'CC BY-SA 4.0',
            ],
            'homepage Cat Ba image credit' => [
                'Christophe95',
                'CC BY-SA 4.0',
            ],
            'homepage Bai Tu Long image credit' => [
                'Benjamin Smith',
                'CC BY-SA 4.0',
            ],
            'homepage Hue image credit' => [
                'CEphoto',
                'Uwe Aranas',
            ],
            'homepage Hai Van image credit' => [
                'Wolkenkratzer',
            ],
            'homepage Hoi An image credit' => [
                'Steffen Schmitz',
            ],
            'homepage HCMC route proof' => [
                'Ho Chi Minh City should be planned as a southern chapter',
            ],
            'homepage HCMC guide href' => [
                'href="/destinations/ho-chi-minh-city-travel-guide/"',
            ],
            'homepage Hanoi stay area planning row' => [
                'I need to choose the actual Hanoi base before Ninh Binh, the bay, or a mountain move.',
            ],
            'homepage Hanoi stay area guide row' => [
                'Where to Stay in Hanoi',
            ],
            'homepage Hanoi stay area guide href' => [
                'href="/destinations/where-to-stay-in-hanoi/"',
            ],
            'homepage Hanoi stay base verdict' => [
                'Choose the Hanoi stay area by first-night recovery, pickup clarity, sleep, and route launch.',
            ],
            'homepage Hanoi neighborhood comparison planning row' => [
                'I am choosing between Old Quarter, French Quarter, and West Lake.',
            ],
            'homepage Hanoi neighborhood comparison guide row' => [
                'Old Quarter vs French Quarter vs West Lake',
            ],
            'homepage Hanoi neighborhood comparison guide href' => [
                'href="/compare/old-quarter-vs-french-quarter-vs-west-lake/"',
            ],
            'homepage Hanoi neighborhood comparison verdict' => [
                'Choose Old Quarter, French Quarter, or West Lake by sleep, walking, and first-night rhythm.',
            ],
            'homepage Hanoi day trips planning row' => [
                'I need to choose the right day trip from Hanoi.',
            ],
            'homepage Hanoi day trips guide row' => [
                'Best Day Trips from Hanoi',
            ],
            'homepage Hanoi day trips guide href' => [
                'href="/destinations/best-day-trips-from-hanoi/"',
            ],
            'homepage Hanoi day trips verdict' => [
                'Choose one Hanoi day trip only after the capital itself has protected time.',
            ],
            'homepage Hanoi airport transfer planning row' => [
                'I have landed in Hanoi and need the first transfer to be calm.',
            ],
            'homepage Hanoi airport transfer guide row' => [
                'Hanoi Airport to Old Quarter',
            ],
            'homepage Hanoi airport transfer guide href' => [
                'href="/plan/hanoi-airport-to-old-quarter/"',
            ],
            'homepage Hanoi airport transfer verdict' => [
                'Treat Noi Bai arrival as the first route decision, not a taxi errand.',
            ],
            'homepage Hanoi in 2 Days planning row' => [
                'I have two days in Hanoi and need it to feel complete without exhausting the next transfer.',
            ],
            'homepage Hanoi in 2 Days href' => [
                'href="/itineraries/hanoi-in-2-days/"',
            ],
            'homepage Hanoi in 2 Days guide row' => [
                'Hanoi in 2 Days',
            ],
            'homepage Hanoi in 2 Days verdict' => [
                'Use Hanoi in 2 Days when the capital needs a tight city chapter, not a checklist.',
            ],
            'homepage HCMC stay area planning row' => [
                'I need to choose the actual HCMC base, not just the city.',
            ],
            'homepage HCMC stay area guide row' => [
                'Where to Stay in Ho Chi Minh City',
            ],
            'homepage HCMC stay area guide href' => [
                'href="/destinations/where-to-stay-in-ho-chi-minh-city/"',
            ],
            'homepage HCMC stay base verdict' => [
                'Choose the hotel area by the job it has to do in the route.',
            ],
            'homepage HCMC day trips guide row' => [
                'Best Day Trips from Ho Chi Minh City',
            ],
            'homepage HCMC day trips guide href' => [
                'href="/destinations/best-day-trips-from-ho-chi-minh-city/"',
            ],
            'homepage HCMC day trips verdict' => [
                'Use Best Day Trips from Ho Chi Minh City when the south needs an excursion decision, not another generic attractions list.',
            ],
            'homepage Mekong guide row' => [
                'Mekong Delta Travel Guide',
            ],
            'homepage Mekong guide href' => [
                'href="/destinations/mekong-delta-travel-guide/"',
            ],
            'homepage Mekong planning row' => [
                'I need to know whether the Mekong Delta deserves a real river chapter.',
            ],
            'homepage Mekong route proof' => [
                'Use the Mekong Delta when the south needs river life, floating-market context, and enough time to avoid a staged final-morning rush.',
            ],
            'homepage Mekong image credit' => [
                'Vyacheslav Argenberg',
            ],
            'homepage Best Beaches image proof' => [
                'Beach time should earn its place in the route.',
            ],
            'homepage Best Beaches image credit' => [
                'Vivu Vietnam',
                'CC BY-SA 4.0',
            ],
            'homepage Best Beaches image record' => [
                'Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg',
            ],
            'homepage Mui Ne image proof' => [
                'Mui Ne is the wind-sport and open-coast counterpoint',
            ],
            'homepage Mui Ne image credit' => [
                'Vyacheslav Argenberg',
                'CC BY 4.0',
            ],
            'homepage Mui Ne image record' => [
                'Vietnam,_Mui_Ne_beach,_Kitesurfing_on_the_beach.jpg',
            ],
            'homepage Best Islands image proof' => [
                'Island time should be chosen by route job',
            ],
            'homepage Best Islands image credit' => [
                'Daeva Trac',
                'CC BY-SA 4.0',
            ],
            'homepage Best Islands image record' => [
                'Beach_view_from_Six_Senses_Resort',
            ],
            'editorial trust method' => [
                'vg-home-editorial-proof',
            ],
            'editorial trust phrase' => [
                'Trust is built into the page architecture',
            ],
            'next action sequence' => [
                'vg-home-sequence',
            ],
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $homepage,
        'Homepage',
        'visible Mui Ne homepage image credit',
        [
            'Vietnam%2C_Mui_Ne_beach%2C_Kitesurfing_on_the_beach.jpg',
            'Vyacheslav Argenberg, CC BY 4.0',
            'Mui Ne is the wind-sport and open-coast counterpoint',
        ],
        $failures
    );

    vg_verify_eeat_require_content_occurrence_count(
        $homepage,
        'Homepage',
        'href="/itineraries/21-days-in-vietnam/"',
        4,
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $homepage,
        'Homepage',
        'visible Hanoi Travel Guide homepage row',
        [
            'Hanoi Travel Guide',
            'href="/destinations/hanoi-travel-guide/"',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $homepage,
        'Homepage',
        [
            'rank_math_title',
            'rank_math_description',
            'rank_math_focus_keyword',
            '_generate-disable-headline',
            'vg_eeat_primary_decision',
            'vg_eeat_sources_checked',
            'vg_eeat_update_summary',
            'vg_eeat_last_meaningful_update',
            'vg_eeat_hero_image_credit',
        ],
        $failures
    );

    vg_verify_eeat_require_internal_links_published($homepage, 'Homepage', $failures);
    vg_verify_eeat_require_affiliate_consistency($homepage, 'Homepage', $failures);
}

$itineraries_hub = vg_verify_eeat_require_published_path('itineraries', 'Itineraries hub', $failures);

if ($itineraries_hub instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $itineraries_hub,
        'Itineraries hub',
        [
            'itineraries hub 21-day note' => [
                'vg-itinerary-21-day-hub-note:v1',
                '21 Days in Vietnam guide',
                'href="/itineraries/21-days-in-vietnam/"',
            ],
            'itineraries hub Hanoi in 2 Days note' => [
                'vg-hanoi-2-days-itineraries-hub-note:v1',
                'Hanoi in 2 Days',
                'href="/itineraries/hanoi-in-2-days/"',
            ],
        ],
        $failures
    );
}

$plan_hub = vg_verify_eeat_require_published_path('plan', 'Plan hub', $failures);

if ($plan_hub instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $plan_hub,
        'Plan hub',
        [
            'transport plan hub note' => [
                'vg-transport-plan-hub-note:v1',
            ],
            'money plan hub note' => [
                'vg-money-plan-hub-note:v1',
            ],
            'sim/eSIM plan hub note' => [
                'vg-sim-esim-plan-hub-note:v1',
            ],
            'safety plan hub note' => [
                'vg-safety-scams-plan-hub-note:v1',
            ],
            'health insurance plan hub note' => [
                'vg-health-insurance-plan-hub-note:v1',
            ],
            'Hanoi airport transfer plan hub note' => [
                'vg-hanoi-airport-transfer-plan-hub-note:v1',
            ],
            'Hanoi to Ninh Binh transport plan hub note' => [
                'vg-hanoi-ninh-binh-transport-plan-hub-note:v1',
            ],
            'Ninh Binh to Ha Long Bay Transfer plan hub note' => [
                'vg-ninh-binh-ha-long-transfer-plan-hub-note:v1',
            ],
        ],
        $failures
    );
}

$destinations_hub = vg_verify_eeat_require_published_path('destinations', 'Destinations hub', $failures);

if ($destinations_hub instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $destinations_hub,
        'Destinations hub',
        [
            'Hanoi destinations hub note' => [
                'vg-best-things-hanoi-destinations-hub-note:v1',
            ],
            'Hanoi Travel Guide destinations hub note' => [
                'vg-hanoi-travel-destinations-hub-note:v1',
            ],
            'Where to Stay in Hanoi destinations hub note' => [
                'vg-hanoi-stays-destinations-hub-note:v1',
            ],
            'Best Day Trips from Hanoi destinations hub note' => [
                'vg-hanoi-day-trips-destinations-hub-note:v1',
            ],
            'Hoi An destinations hub note' => [
                'vg-best-things-hoi-an-destinations-hub-note:v1',
            ],
            'Hue destinations hub note' => [
                'vg-best-things-hue-destinations-hub-note:v1',
            ],
            'Ninh Binh destinations hub note' => [
                'vg-ninh-binh-destinations-hub-note:v1',
            ],
            'Ninh Binh day trip overnight destinations hub note' => [
                'vg-ninh-binh-day-trip-overnight-destinations-hub-note:v1',
            ],
            'Hanoi to Ninh Binh transport destinations hub note' => [
                'vg-hanoi-ninh-binh-transport-destinations-hub-note:v1',
            ],
            'Where to Stay in Ninh Binh destinations hub note' => [
                'vg-ninh-binh-stays-destinations-hub-note:v1',
            ],
            'Tam Coc destinations hub note' => [
                'vg-tam-coc-destinations-hub-note:v1',
            ],
            'Ninh Binh to Ha Long Bay Transfer destinations hub note' => [
                'vg-ninh-binh-ha-long-transfer-destinations-hub-note:v1',
            ],
            'Ha Long Bay destinations hub note' => [
                'vg-ha-long-bay-destinations-hub-note:v1',
            ],
            'Cat Ba destinations hub note' => [
                'vg-cat-ba-destinations-hub-note:v1',
            ],
            'Bai Tu Long destinations hub note' => [
                'vg-bai-tu-long-destinations-hub-note:v1',
            ],
            'Best Beaches destinations hub note' => [
                'vg-best-beaches-destinations-hub-note:v1',
            ],
            'Best Islands destinations hub note' => [
                'vg-best-islands-destinations-hub-note:v1',
            ],
            'Con Dao destinations hub note' => [
                'vg-con-dao-destinations-hub-note:v1',
            ],
            'Phu Quoc destinations hub note' => [
                'vg-phu-quoc-destinations-hub-note:v1',
            ],
            'Nha Trang destinations hub note' => [
                'vg-nha-trang-destinations-hub-note:v1',
            ],
            'Da Nang destinations hub note' => [
                'vg-da-nang-destinations-hub-note:v1',
            ],
            'Ho Chi Minh City destinations hub note' => [
                'vg-hcmc-destinations-hub-note:v1',
            ],
            'Best Day Trips from Ho Chi Minh City destinations hub note' => [
                'vg-hcmc-day-trips-destinations-hub-note:v1',
            ],
            'Mekong Delta destinations hub note' => [
                'vg-mekong-destinations-hub-note:v1',
            ],
        ],
        $failures
    );
}

$compare_hub = vg_verify_eeat_require_published_path('compare', 'Compare hub', $failures);

if ($compare_hub instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $compare_hub,
        'Compare hub',
        [
            'Ha Long/Lan Ha compare hub note' => [
                'vg-ha-long-lan-ha-compare-hub-note:v1',
            ],
            'Da Nang/Hoi An compare hub note' => [
                'vg-da-nang-hoi-an-compare-hub-note:v1',
            ],
            'Hoi An/Hue compare hub note' => [
                'vg-hoi-an-hue-compare-hub-note:v1',
            ],
            'Phu Quoc/Nha Trang compare hub note' => [
                'vg-phu-quoc-nha-trang-compare-hub-note:v1',
            ],
            'Mui Ne/Nha Trang compare hub note' => [
                'vg-mui-ne-nha-trang-compare-hub-note:v1',
            ],
            'Cu Chi/Mekong compare hub note' => [
                'vg-cu-chi-mekong-compare-hub-note:v1',
            ],
            'Hanoi neighborhood compare hub note' => [
                'vg-hanoi-neighborhood-compare-hub-note:v1',
            ],
            'Ninh Binh day trip overnight compare hub note' => [
                'vg-ninh-binh-day-trip-overnight-compare-hub-note:v1',
            ],
        ],
        $failures
    );
}

$required_guide_content_groups = vg_verify_eeat_required_guide_content_groups();
$required_guide_meta = vg_verify_eeat_required_guide_meta();

$itinerary_21_day_page = vg_verify_eeat_require_published_path(
    'itineraries/21-days-in-vietnam',
    '21 Days itinerary guide',
    $failures
);

if ($itinerary_21_day_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $itinerary_21_day_page,
        '21 Days itinerary guide',
        'itineraries/21-days-in-vietnam',
        '21 Days in Vietnam',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $itinerary_21_day_page,
        '21 Days itinerary guide',
        array_merge(
            $required_guide_content_groups,
            [
                '21-day hero marker' => [
                    'vg-itinerary-21day-hero:v1',
                ],
                '21-day concierge verdict' => [
                    'vg-itinerary-21day-concierge-verdict',
                ],
                '21-day at a glance' => [
                    'vg-itinerary-21day-at-a-glance:v1',
                ],
                '21-day photo grid' => [
                    'vg-itinerary-21day-photo-grid:v1',
                    'vg-itinerary-21day-photo-grid',
                ],
                '21-day planning flow' => [
                    'vg-itinerary-21day-planning-flow:v1',
                ],
                '21-day route family' => [
                    'vg-itinerary-21day-route-family:v1',
                ],
                '21-day transfer pressure' => [
                    'vg-itinerary-21day-transfer-pressure:v1',
                    'vg-itinerary-21day-transfer-pressure',
                ],
                '21-day day plan' => [
                    'vg-itinerary-21day-day-plan:v1',
                ],
                '21-day extension matrix' => [
                    'vg-itinerary-21day-extension-matrix:v1',
                    'vg-itinerary-21day-extension-matrix',
                ],
                '21-day season pivots' => [
                    'vg-itinerary-21day-season-pivots:v1',
                    'vg-itinerary-21day-season-pivots',
                ],
                '21-day swap skip' => [
                    'vg-itinerary-21day-swap-skip:v1',
                    'vg-itinerary-21day-slowdown-rules',
                    'vg-itinerary-21day-faq',
                ],
                '21-day prebook flex' => [
                    'vg-itinerary-21day-prebook-flex:v1',
                    'vg-itinerary-21day-prebook-flex',
                ],
                '21-day mistakes' => [
                    'vg-itinerary-21day-mistakes:v1',
                    'common three-week mistakes',
                    'vg-itinerary-21day-faq',
                ],
                '21-day booking sequence' => [
                    'vg-itinerary-21day-booking-sequence:v1',
                    'vg-itinerary-21day-booking-sequence',
                ],
                '21-day planning audit' => [
                    'vg-itinerary-21day-planning-audit:v1',
                    'vg-itinerary-21day-planning-audit',
                ],
                '21-day route brief' => [
                    '21-day Vietnam itinerary at a glance',
                ],
                '21-day route family framing' => [
                    'Full-country with margin',
                ],
                '21-day three-week phrasing' => [
                    'three weeks',
                ],
                '21-day full-country route phrasing' => [
                    'full-country route',
                ],
                '21-day related-route metadata 10-day' => [
                    '10 Days in Vietnam | /itineraries/10-days-in-vietnam/ |',
                ],
                '21-day related-route metadata Hue' => [
                    'Best Things to Do in Hue | /destinations/best-things-to-do-in-hue/ |',
                ],
                '21-day related-route metadata cost' => [
                    'Vietnam Travel Cost | /costs/vietnam-travel-cost/ |',
                ],
                '21-day related-route metadata region compare' => [
                    'North vs Central vs South Vietnam | /compare/north-central-south-vietnam/ |',
                ],
                '21-day proof panel' => [
                    'vg-proof-panel',
                ],
                '21-day source trail' => [
                    'vg-source-trail',
                ],
                '21-day update log' => [
                    'vg-update-log',
                ],
                '21-day related routes shortcode' => [
                    'vg-related-routes',
                    '[vg_related_routes]',
                ],
            ]
        ),
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $itinerary_21_day_page,
        '21 Days itinerary guide',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $itinerary_21_day_page,
        '21 Days itinerary guide',
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
            'compare/da-nang-vs-hoi-an' => 'Da Nang vs Hoi An guide',
            'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        ],
        '/itineraries/21-days-in-vietnam/',
        '21 Days in Vietnam guide',
        $failures
    );
}

$best_time_paths = [
    'plan/best-time-to-visit-vietnam',
    'best-time-to-visit-vietnam',
];

$best_time_page = vg_verify_eeat_verify_published_guide(
    $best_time_paths,
    'best-time-to-visit-vietnam',
    'Best Time guide',
    array_merge(
        $required_guide_content_groups,
        [
            'best time route brief' => [
                'vg-best-time-at-a-glance:v1',
            ],
            'best time photo grid' => [
                'vg-best-time-photo-grid:v1',
            ],
            'best time route matrix' => [
                'vg-best-time-route-matrix:v1',
            ],
            'best time regional pivots' => [
                'vg-best-time-regional-pivots:v1',
            ],
            'best time planning flow' => [
                'vg-best-time-planning-flow:v1',
            ],
            'best time route examples' => [
                'vg-best-time-route-examples:v1',
            ],
            'best time failure modes' => [
                'vg-best-time-failure-modes:v1',
            ],
            'best time booking audit' => [
                'vg-best-time-booking-audit:v1',
            ],
            'best time mistakes FAQ' => [
                'vg-best-time-mistakes:v1',
            ],
            'best time Hanoi image credit' => [
                'Alex 69200 vx',
            ],
            'best time Hoi An image credit' => [
                'Steffen Schmitz',
            ],
            'best time Ho Chi Minh City image credit' => [
                'Ho Chi Minh City',
            ],
            'best time Mekong image credit' => [
                'Mekong Delta',
                'Vyacheslav Argenberg',
            ],
            'best time official weather source anchor' => [
                'weather-and-climate-vietnam',
            ],
            'best time official warning source anchor' => [
                'nchmf.gov.vn',
            ],
            'best time official city forecast anchor' => [
                'worldweather.wmo.int',
            ],
            'best time northern destination anchor' => [
                'northern-vietnam',
            ],
            'best time central destination anchor' => [
                'central-vietnam',
            ],
            'best time southern destination anchor' => [
                'southern-vietnam',
            ],
        ]
    ),
    array_merge(
        $required_guide_meta,
        [
            'vg_eeat_hero_image_credit',
        ]
    ),
    $failures
);

$required_travel_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'planning order' => [
            'vg-travel-guide-planning-order',
        ],
        'planning flow marker' => [
            'vg-travel-guide-planning-flow:v1',
        ],
        'regional photo grid marker' => [
            'vg-travel-guide-regional-photo-grid:v1',
        ],
        'transfer feature marker' => [
            'vg-travel-guide-transfer-feature:v1',
        ],
        'route-family marker' => [
            'vg-travel-guide-route-family:v1',
        ],
        'planning mistakes marker' => [
            'vg-travel-guide-mistakes:v1',
        ],
        'travel guide Hanoi image URL' => [
            'Hanoi-lac-hoan-kiem.jpg',
        ],
        'travel guide Hanoi image credit page' => [
            'https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg',
        ],
        'travel guide Hanoi image credit text' => [
            'Alex 69200 vx / CC BY-SA 4.0',
        ],
        'travel guide Trang An image URL' => [
            'Trang_An_Landscape_Complex',
        ],
        'travel guide Trang An image credit page' => [
            'https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg',
        ],
        'travel guide Trang An image credit text' => [
            'Jakub Halun / CC BY 4.0',
        ],
        'travel guide Hue Citadel image URL' => [
            'Hue_Vietnam_Citadel-of-Hu',
        ],
        'travel guide Hue Citadel image credit page' => [
            'https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg',
        ],
        'travel guide Hue Citadel image credit text' => [
            'CEphoto, Uwe Aranas / CC BY-SA 3.0',
        ],
        'travel guide Hoi An image URL' => [
            'H%E1%BB%99i_An%2C_Ancient_Town',
        ],
        'travel guide Hoi An image credit page' => [
            'https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg',
        ],
        'travel guide Hoi An image credit text' => [
            'Steffen Schmitz / CC BY-SA 4.0',
        ],
        'travel guide HCMC image URL' => [
            'Ho_Chi_Minh_City%2C_City_Hall',
        ],
        'travel guide HCMC image credit page' => [
            'https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg',
        ],
        'travel guide HCMC image credit text' => [
            'Steffen Schmitz / CC BY-SA 4.0',
        ],
        'travel guide Hai Van Pass image URL' => [
            'Vietnam%2C_Hai-Van-Pass',
        ],
        'travel guide Hai Van Pass image credit page' => [
            'https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg',
        ],
        'travel guide Hai Van Pass image credit text' => [
            'Wolkenkratzer / CC BY-SA 4.0',
        ],
        'travel guide photo-led chapter meta' => [
            'Photo-led regional chapter framework',
        ],
        'travel guide route-family meta' => [
            'Route family visual module',
        ],
        'travel guide Wikimedia source meta' => [
            'Wikimedia Commons image record',
        ],
        'travel guide July 16 meaningful update' => [
            'July 16, 2026',
        ],
        'decision map' => [
            'vg-travel-guide-decision-map',
        ],
        'route lenses' => [
            'vg-travel-guide-route-lenses',
        ],
        'evergreen live checks' => [
            'vg-travel-guide-evergreen-checks',
        ],
        'official checks checklist' => [
            'vg-travel-guide-official-checks',
        ],
        'first-trip checklist' => [
            'vg-travel-guide-first-trip-checklist',
        ],
        'official weather source anchor' => [
            'Vietnam.travel',
        ],
        'official e-visa source anchor' => [
            'evisa.gov.vn',
        ],
        'alternate official e-visa source anchor' => [
            'thithucdientu.gov.vn',
        ],
        'official rail source anchor' => [
            'dsvn.vn',
        ],
        'official currency source anchor' => [
            'State Bank of Vietnam',
            'sbv.gov.vn',
        ],
        'official weather warning source anchor' => [
            'National Center for Hydro-Meteorological Forecasting',
            'nchmf.gov.vn',
        ],
    ]
);

$required_travel_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$travel_guide_page = vg_verify_eeat_require_published_path(
    'plan/vietnam-travel-guide',
    'Vietnam Travel Guide',
    $failures
);

if ($travel_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $travel_guide_page,
        'Vietnam Travel Guide',
        $required_travel_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $travel_guide_page,
        'Vietnam Travel Guide',
        $required_travel_guide_meta,
        $failures
    );
}

$required_region_comparison_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'regional verdict matrix' => [
            'vg-region-compare-verdict-matrix',
        ],
        'heritage anchors' => [
            'vg-region-compare-heritage-anchors',
        ],
        'first-trip fit matrix' => [
            'vg-region-compare-first-trip-fit',
        ],
        'season and route reality' => [
            'vg-region-compare-season-route',
        ],
        'skip logic' => [
            'vg-region-compare-skip-logic',
        ],
        'official checks checklist' => [
            'vg-region-compare-official-checks',
        ],
        'northern official source anchor' => [
            'Northern Vietnam',
            'northern-vietnam',
        ],
        'central official source anchor' => [
            'Central Vietnam',
            'central-vietnam',
        ],
        'southern official source anchor' => [
            'Southern Vietnam',
            'southern-vietnam',
        ],
        'official weather source anchor' => [
            'Weather and climate in Vietnam',
            'weather-and-climate-vietnam',
        ],
        'official transport source anchor' => [
            'Transport within Vietnam',
            'transport-within-vietnam',
        ],
        'unesco source anchor' => [
            'UNESCO',
            'whc.unesco.org',
        ],
    ]
);

$required_region_comparison_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$region_comparison_page = vg_verify_eeat_require_published_path(
    'compare/north-central-south-vietnam',
    'North vs Central vs South Vietnam guide',
    $failures
);

if ($region_comparison_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $region_comparison_page,
        'North vs Central vs South Vietnam guide',
        $required_region_comparison_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $region_comparison_page,
        'North vs Central vs South Vietnam guide',
        $required_region_comparison_meta,
        $failures
    );
}

$required_destination_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'first-trip shortlist' => [
            'vg-destination-guide-first-trip-shortlist',
        ],
        'destination verdict matrix' => [
            'vg-destination-guide-verdict-matrix',
        ],
        'route fit matrix' => [
            'vg-destination-guide-route-fit',
        ],
        'heritage and nature anchors' => [
            'vg-destination-guide-heritage-nature',
        ],
        'skip logic' => [
            'vg-destination-guide-skip-logic',
        ],
        'official checks checklist' => [
            'vg-destination-guide-official-checks',
        ],
        'northern official source anchor' => [
            'Northern Vietnam',
            'northern-vietnam',
        ],
        'central official source anchor' => [
            'Central Vietnam',
            'central-vietnam',
        ],
        'southern official source anchor' => [
            'Southern Vietnam',
            'southern-vietnam',
        ],
        'hanoi official destination anchor' => [
            'Ha Noi',
            'ha-noi',
        ],
        'ninh binh official destination anchor' => [
            'Ninh Binh',
            'ninh-binh',
        ],
        'hoi an official destination anchor' => [
            'Hoi An',
            'hoi-an',
        ],
        'hue official destination anchor' => [
            'Hue',
            'hue',
        ],
        'ho chi minh city official destination anchor' => [
            'Ho Chi Minh City',
            'ho-chi-minh-city',
        ],
        'phu quoc official destination anchor' => [
            'Phu Quoc',
            'phu-quoc',
        ],
        'official weather source anchor' => [
            'Weather and climate in Vietnam',
            'weather-and-climate-vietnam',
        ],
        'unesco source anchor' => [
            'UNESCO',
            'whc.unesco.org',
        ],
    ]
);

$required_destination_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$destination_guide_page = vg_verify_eeat_require_published_path(
    'destinations/best-places-to-visit-vietnam',
    'Best Places to Visit in Vietnam guide',
    $failures
);

if ($destination_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $destination_guide_page,
        'Best Places to Visit in Vietnam guide',
        $required_destination_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $destination_guide_page,
        'Best Places to Visit in Vietnam guide',
        $required_destination_guide_meta,
        $failures
    );
}

$required_heritage_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'heritage hero marker' => [
            'vg-unesco-heritage-hero:v1',
        ],
        'heritage concierge verdict' => [
            'vg-unesco-heritage-concierge-verdict',
        ],
        'heritage upgraded H1' => [
            'UNESCO Heritage Sites in Vietnam: Which Ones Belong in Your Route',
        ],
        'heritage at a glance' => [
            'vg-unesco-heritage-at-a-glance:v1',
        ],
        'heritage source diversity' => [
            'vg-unesco-heritage-source-diversity:v1',
        ],
        'heritage source trail snapshot' => [
            'vg-unesco-heritage-source-trail-snapshot:v1',
        ],
        'heritage 2025 updates' => [
            'vg-unesco-heritage-2025-updates:v1',
        ],
        'heritage route chooser' => [
            'vg-unesco-heritage-route-chooser:v1',
        ],
        'heritage shortlist' => [
            'vg-unesco-heritage-shortlist:v1',
        ],
        'heritage site-by-site' => [
            'vg-unesco-heritage-site-by-site:v1',
        ],
        'heritage route map' => [
            'vg-unesco-heritage-route-map:v1',
        ],
        'heritage itinerary length' => [
            'vg-unesco-heritage-itinerary-length:v1',
        ],
        'heritage photo grid' => [
            'vg-unesco-heritage-photo-grid:v1',
        ],
        'heritage skip logic' => [
            'vg-unesco-heritage-skip-logic:v1',
        ],
        'heritage official checks' => [
            'vg-unesco-heritage-official-checks:v1',
        ],
        'heritage live checks' => [
            'vg-unesco-heritage-live-checks:v1',
        ],
        'heritage FAQ' => [
            'vg-unesco-heritage-faq:v1',
        ],
        'heritage UNESCO source anchor' => [
            'whc.unesco.org',
        ],
        'heritage Vietnam.travel source anchor' => [
            'Vietnam.travel',
        ],
        'heritage 9-property anchor' => [
            '9 UNESCO World Heritage properties',
        ],
        'heritage category anchor' => [
            '6 cultural, 2 natural, and 1 mixed',
        ],
        'heritage Yen Tu 2025 anchor' => [
            'Yen Tu - Vinh Nghiem - Con Son, Kiep Bac was inscribed in 2025',
        ],
        'heritage Phong Nha 2025 anchor' => [
            'Phong Nha-Ke Bang National Park and Hin Nam No National Park is a 2025 transboundary update',
        ],
        'heritage source limitation anchor' => [
            'Sources can confirm official World Heritage status, categories, site names, inscription decisions, broad destination context, and image-license records; they cannot decide your pace, transfer tolerance, heat limit, children, mobility, cruise risk, or whether one more heritage stop makes the route better.',
        ],
        'heritage text credit anchor' => [
            'Image credits are listed as text to keep the heritage decision guide readable and reduce visible outbound clutter.',
        ],
    ]
);

$required_heritage_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$heritage_guide_page = vg_verify_eeat_require_published_path(
    'destinations/unesco-heritage-sites-vietnam',
    'UNESCO Heritage Sites in Vietnam guide',
    $failures
);

if ($heritage_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $heritage_guide_page,
        'UNESCO Heritage Sites in Vietnam guide',
        'destinations/unesco-heritage-sites-vietnam',
        'UNESCO Heritage Sites in Vietnam',
        $failures
    );

    vg_verify_eeat_require_page_sitemap_url(
        'destinations/unesco-heritage-sites-vietnam',
        'UNESCO Heritage Sites in Vietnam guide',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $heritage_guide_page,
        'UNESCO Heritage Sites in Vietnam guide',
        $required_heritage_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $heritage_guide_page,
        'UNESCO Heritage Sites in Vietnam guide',
        $required_heritage_guide_meta,
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $heritage_guide_page,
        'UNESCO Heritage Sites in Vietnam guide',
        'rendered UNESCO Heritage source trail snapshot',
        [
            'Source trail snapshot for this heritage guide',
            'https://whc.unesco.org/en/list/1732/',
            'https://whc.unesco.org/en/decisions/8942/',
            'Image credits are listed as text to keep the heritage decision guide readable and reduce visible outbound clutter.',
        ],
        $failures
    );

    vg_verify_eeat_require_visible_external_body_link_budget(
        $heritage_guide_page,
        'UNESCO Heritage Sites in Vietnam guide',
        0,
        2,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $heritage_guide_page,
        'UNESCO Heritage Sites in Vietnam guide',
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $heritage_guide_page,
        'UNESCO Heritage Sites in Vietnam guide',
        [
            '/destinations/best-places-to-visit-vietnam/' => 'Best Places to Visit in Vietnam',
            '/destinations/hanoi-travel-guide/' => 'Hanoi Travel Guide',
            '/destinations/ninh-binh-travel-guide/' => 'Ninh Binh Travel Guide',
            '/destinations/ha-long-bay-travel-guide/' => 'Ha Long Bay Travel Guide',
            '/compare/ha-long-bay-vs-lan-ha-bay/' => 'Ha Long Bay vs Lan Ha Bay',
            '/compare/hoi-an-vs-hue/' => 'Hoi An vs Hue',
            '/compare/north-central-south-vietnam/' => 'North vs Central vs South Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
        ],
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        ],
        '/destinations/unesco-heritage-sites-vietnam/',
        'UNESCO Heritage Sites in Vietnam',
        $failures,
        true
    );
}

$required_hanoi_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Hanoi hero marker' => [
            'vg-best-things-hanoi-hero:v1',
        ],
        'Hanoi concierge verdict' => [
            'vg-best-things-hanoi-concierge-verdict',
        ],
        'Hanoi priority map' => [
            'vg-best-things-hanoi-priority-map:v1',
        ],
        'Hanoi photo grid' => [
            'vg-best-things-hanoi-photo-grid:v1',
        ],
        'Hanoi route fit' => [
            'vg-best-things-hanoi-route-fit:v1',
        ],
        'Hanoi rain and heat pivots' => [
            'vg-best-things-hanoi-rain-heat-pivots:v1',
        ],
        'Hanoi skip logic' => [
            'vg-best-things-hanoi-skip-logic:v1',
        ],
        'Hanoi official checks' => [
            'vg-best-things-hanoi-official-checks:v1',
        ],
        'Hanoi FAQ' => [
            'vg-best-things-hanoi-faq:v1',
        ],
        'Hanoi Travel Guide support block' => [
            'vg-hanoi-travel-best-things-support:v1',
            '/destinations/hanoi-travel-guide/',
        ],
        'Hanoi neighborhood comparison support block' => [
            'vg-hanoi-neighborhood-compare-best-things-support:v1',
            '/compare/old-quarter-vs-french-quarter-vs-west-lake/',
        ],
        'Hanoi Vietnam.travel source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam/ha-noi',
        ],
        'Hanoi Northern Vietnam source anchor' => [
            'northern-vietnam',
        ],
        'Hanoi UNESCO source anchor' => [
            'whc.unesco.org/en/list/1328',
        ],
        'Hanoi Thang Long official source anchor' => [
            'hoangthanhthanglong.vn',
        ],
        'Hanoi Temple official source anchor' => [
            'vanmieu.gov.vn',
        ],
        'Hanoi Women Museum official source anchor' => [
            'baotangphunu.org.vn',
        ],
        'Hanoi weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Hanoi transport source anchor' => [
            'transport-within-vietnam',
        ],
        'Hanoi Hoan Kiem image credit' => [
            'Alex 69200 vx',
        ],
        'Hanoi Old Quarter image credit' => [
            'yeowatzup',
        ],
        'Hanoi Temple image credit' => [
            'Jakub Halun',
        ],
        'Hanoi Thang Long image credit' => [
            'katiebordner',
        ],
        'Hanoi Train Street image credit' => [
            'Kris Martyn',
        ],
    ]
);

$required_hanoi_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$hanoi_guide_page = vg_verify_eeat_require_published_path(
    'destinations/best-things-to-do-in-hanoi',
    'Best Things to Do in Hanoi guide',
    $failures
);

if ($hanoi_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $hanoi_guide_page,
        'Best Things to Do in Hanoi guide',
        $required_hanoi_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $hanoi_guide_page,
        'Best Things to Do in Hanoi guide',
        $required_hanoi_guide_meta,
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $hanoi_guide_page,
        'Best Things to Do in Hanoi guide',
        'visible Hanoi support links',
        [
            '/destinations/hanoi-travel-guide/',
            '/compare/old-quarter-vs-french-quarter-vs-west-lake/',
        ],
        $failures
    );
}

$required_hanoi_travel_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Hanoi Travel Guide hero marker' => [
            'vg-hanoi-travel-hero:v1',
        ],
        'Hanoi Travel Guide concierge verdict' => [
            'vg-hanoi-travel-concierge-verdict',
        ],
        'Hanoi Travel Guide at a glance' => [
            'vg-hanoi-travel-at-a-glance:v1',
        ],
        'Hanoi Travel Guide photo grid' => [
            'vg-hanoi-travel-photo-grid:v1',
        ],
        'Hanoi Travel Guide source diversity' => [
            'vg-hanoi-travel-source-diversity:v1',
        ],
        'Hanoi Travel Guide route role' => [
            'vg-hanoi-travel-route-role:v1',
        ],
        'Hanoi Travel Guide stay areas' => [
            'vg-hanoi-travel-stay-areas:v1',
        ],
        'Hanoi Travel Guide day plans' => [
            'vg-hanoi-travel-day-plans:v1',
        ],
        'Hanoi Travel Guide transport logistics' => [
            'vg-hanoi-travel-transport-logistics:v1',
        ],
        'Hanoi Travel Guide weather pivots' => [
            'vg-hanoi-travel-weather-pivots:v1',
        ],
        'Hanoi Travel Guide cost comfort' => [
            'vg-hanoi-travel-cost-comfort:v1',
        ],
        'Hanoi Travel Guide mistakes skip' => [
            'vg-hanoi-travel-mistakes-skip:v1',
        ],
        'Hanoi Travel Guide live checks' => [
            'vg-hanoi-travel-live-checks:v1',
        ],
        'Hanoi Travel Guide FAQ' => [
            'vg-hanoi-travel-faq:v1',
        ],
    ]
);

$required_hanoi_travel_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$hanoi_travel_guide_page = vg_verify_eeat_require_published_path(
    'destinations/hanoi-travel-guide',
    'Hanoi Travel Guide',
    $failures
);

if ($hanoi_travel_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $hanoi_travel_guide_page,
        'Hanoi Travel Guide',
        'destinations/hanoi-travel-guide',
        'Hanoi Travel Guide',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $hanoi_travel_guide_page,
        'Hanoi Travel Guide',
        $required_hanoi_travel_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $hanoi_travel_guide_page,
        'Hanoi Travel Guide',
        'Hanoi Travel Guide source and evidence anchors',
        [
            'vietnam.travel/places-to-go/northern-vietnam/ha-noi',
            'whc.unesco.org/en/list/1328',
            'vietnamairport.vn/en/noi-bai-airport',
            'nchmf.gov.vn',
            'Noi Bai',
            'Long Bien',
            '/destinations/where-to-stay-in-hanoi/',
            '/compare/old-quarter-vs-french-quarter-vs-west-lake/',
            'Hanoi is the best first northern base for most international visitors',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $hanoi_travel_guide_page,
        'Hanoi Travel Guide',
        $required_hanoi_travel_guide_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hanoi_travel_guide_page,
        'Hanoi Travel Guide',
        'vietnamguide/source-trail',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hanoi_travel_guide_page,
        'Hanoi Travel Guide',
        'vietnamguide/update-log',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hanoi_travel_guide_page,
        'Hanoi Travel Guide',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $hanoi_travel_guide_page,
        'Hanoi Travel Guide',
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $hanoi_travel_guide_page,
        'Hanoi Travel Guide',
        [
            '/destinations/where-to-stay-in-hanoi/' => 'Where to Stay in Hanoi',
            '/compare/old-quarter-vs-french-quarter-vs-west-lake/' => 'Old Quarter vs French Quarter vs West Lake',
            '/destinations/best-things-to-do-in-hanoi/' => 'Best Things to Do in Hanoi',
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/destinations/best-places-to-visit-vietnam/' => 'Best Places to Visit in Vietnam',
            '/compare/north-central-south-vietnam/' => 'North vs Central vs South Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/money-cash-cards-atms/' => 'Money in Vietnam',
            '/plan/sim-esim-vietnam/' => 'SIM and eSIM in Vietnam',
            '/plan/vietnam-evisa/' => 'Vietnam E-Visa',
            '/itineraries/7-days-in-vietnam/' => '7 Days in Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
            '/destinations/ninh-binh-travel-guide/' => 'Ninh Binh Travel Guide',
            '/destinations/ha-long-bay-travel-guide/' => 'Ha Long Bay Travel Guide',
            '/destinations/cat-ba-travel-guide/' => 'Cat Ba Travel Guide',
            '/destinations/bai-tu-long-bay-guide/' => 'Bai Tu Long Bay Guide',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
        ],
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
        ],
        '/destinations/hanoi-travel-guide/',
        'Hanoi Travel Guide',
        $failures
    );
}

$required_ninh_binh_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Ninh Binh hero marker' => [
            'vg-ninh-binh-hero:v1',
        ],
        'Ninh Binh concierge verdict' => [
            'vg-ninh-binh-concierge-verdict',
        ],
        'Ninh Binh at a glance' => [
            'vg-ninh-binh-at-a-glance:v1',
        ],
        'Ninh Binh photo grid' => [
            'vg-ninh-binh-photo-grid:v1',
        ],
        'Ninh Binh day trip overnight matrix' => [
            'vg-ninh-binh-day-trip-overnight:v1',
        ],
        'Ninh Binh priority map' => [
            'vg-ninh-binh-priority-map:v1',
        ],
        'Ninh Binh route fit' => [
            'vg-ninh-binh-route-fit:v1',
        ],
        'Ninh Binh best time weather' => [
            'vg-ninh-binh-best-time-weather:v1',
        ],
        'Ninh Binh getting around' => [
            'vg-ninh-binh-getting-around:v1',
        ],
        'Ninh Binh skip logic' => [
            'vg-ninh-binh-skip-logic:v1',
        ],
        'Ninh Binh live checks' => [
            'vg-ninh-binh-live-checks:v1',
        ],
        'Ninh Binh FAQ' => [
            'vg-ninh-binh-faq:v1',
        ],
        'Ninh Binh Vietnam.travel source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
        ],
        'Ninh Binh Northern Vietnam source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam',
        ],
        'Ninh Binh UNESCO Trang An source anchor' => [
            'whc.unesco.org/en/list/1438',
        ],
        'Ninh Binh Tourism Department source anchor' => [
            'dulichninhbinh.com.vn/en',
        ],
        'Ninh Binh weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Ninh Binh transport source anchor' => [
            'transport-within-vietnam',
        ],
        'Ninh Binh Trang An image credit' => [
            'Jakub Halun',
        ],
        'Ninh Binh Trang An license credit' => [
            'Jakub Halun / CC BY 4.0',
        ],
        'Ninh Binh Trang An image record' => [
            'commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg',
        ],
        'Ninh Binh Tam Coc image credit' => [
            'Andre Hospers',
        ],
        'Ninh Binh Tam Coc license credit' => [
            'Andre Hospers / CC BY 4.0',
        ],
        'Ninh Binh Tam Coc image record' => [
            'commons.wikimedia.org/wiki/File:Tam_Coc_Ninh_Binh_(29079).jpg',
        ],
        'Ninh Binh Mua Cave license credit' => [
            'Jakub Halun / CC BY 4.0',
        ],
        'Ninh Binh Mua Cave image record' => [
            'commons.wikimedia.org/wiki/File:Mua_Cave,_Ninh_Binh,_Vietnam,_20240202_0926_4964.jpg',
        ],
        'Ninh Binh Dragon Mountain image credit' => [
            'Superbass',
        ],
        'Ninh Binh Dragon Mountain license credit' => [
            'Superbass / CC BY-SA 4.0',
        ],
        'Ninh Binh Bai Dinh image credit' => [
            'Viethavvh',
        ],
        'Ninh Binh Bai Dinh license credit' => [
            'Viethavvh / CC BY-SA 3.0',
        ],
        'Ninh Binh Cuc Phuong image credit' => [
            'hds',
        ],
        'Ninh Binh Cuc Phuong license credit' => [
            'hds / CC BY 2.0',
        ],
        'Ninh Binh Van Long license credit' => [
            'Andre Hospers / CC BY 4.0',
        ],
        'Ninh Binh Van Long image record' => [
            'commons.wikimedia.org/wiki/File:Van_Long_Nature_Reserve_Riet_Grotten_Kalksteen_Ninh_Binh_(94589).jpg',
        ],
        'Ninh Binh 10-day route anchor' => [
            '10-days-in-vietnam',
        ],
        'Ninh Binh 14-day route anchor' => [
            '14-days-in-vietnam',
        ],
        'Ninh Binh insurance route anchor' => [
            'health-travel-insurance-vietnam',
        ],
    ]
);

$required_ninh_binh_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$ninh_binh_guide_page = vg_verify_eeat_require_published_path(
    'destinations/ninh-binh-travel-guide',
    'Ninh Binh Travel Guide',
    $failures
);

if ($ninh_binh_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $ninh_binh_guide_page,
        'Ninh Binh Travel Guide',
        $required_ninh_binh_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $ninh_binh_guide_page,
        'Ninh Binh Travel Guide',
        $required_ninh_binh_guide_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $ninh_binh_guide_page,
        'Ninh Binh Travel Guide',
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
    ],
    '/destinations/ninh-binh-travel-guide/',
    'Ninh Binh Travel Guide',
    $failures
);

$required_ninh_binh_stays_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Where to Stay in Ninh Binh hero marker' => [
            'vg-ninh-binh-stays-hero:v1',
        ],
        'Where to Stay in Ninh Binh concierge verdict' => [
            'vg-ninh-binh-stays-concierge-verdict',
        ],
        'Where to Stay in Ninh Binh at a glance' => [
            'vg-ninh-binh-stays-at-a-glance:v1',
        ],
        'Where to Stay in Ninh Binh photo proof' => [
            'vg-ninh-binh-stays-photo-proof:v1',
        ],
        'Where to Stay in Ninh Binh source diversity' => [
            'vg-ninh-binh-stays-source-diversity:v1',
        ],
        'Where to Stay in Ninh Binh source trail snapshot' => [
            'vg-ninh-binh-stays-source-trail-snapshot:v1',
        ],
        'Where to Stay in Ninh Binh base verdict' => [
            'vg-ninh-binh-stays-base-verdict:v1',
        ],
        'Where to Stay in Ninh Binh first-time fit' => [
            'vg-ninh-binh-stays-first-time-fit:v1',
        ],
        'Where to Stay in Ninh Binh traveler profiles' => [
            'vg-ninh-binh-stays-traveler-profiles:v1',
        ],
        'Where to Stay in Ninh Binh day trip overnight' => [
            'vg-ninh-binh-stays-day-trip-overnight:v1',
        ],
        'Where to Stay in Ninh Binh transfer pickup' => [
            'vg-ninh-binh-stays-transfer-pickup:v1',
        ],
        'Where to Stay in Ninh Binh sleep comfort' => [
            'vg-ninh-binh-stays-sleep-comfort:v1',
        ],
        'Where to Stay in Ninh Binh season weather' => [
            'vg-ninh-binh-stays-season-weather:v1',
        ],
        'Where to Stay in Ninh Binh cost booking' => [
            'vg-ninh-binh-stays-cost-booking:v1',
        ],
        'Where to Stay in Ninh Binh booking audit' => [
            'vg-ninh-binh-stays-booking-audit:v1',
        ],
        'Where to Stay in Ninh Binh mistakes skip' => [
            'vg-ninh-binh-stays-mistakes-skip:v1',
        ],
        'Where to Stay in Ninh Binh live checks' => [
            'vg-ninh-binh-stays-live-checks:v1',
        ],
        'Where to Stay in Ninh Binh FAQ' => [
            'vg-ninh-binh-stays-faq:v1',
        ],
        'Where to Stay in Ninh Binh default verdict' => [
            'For most first-time international travelers, Tam Coc is the best default base in Ninh Binh.',
        ],
        'Where to Stay in Ninh Binh Trang An exception' => [
            'Choose Trang An area when landscape silence and morning control matter more than restaurant choice.',
        ],
        'Where to Stay in Ninh Binh city logistics judgment' => [
            'Ninh Binh city is a logistics base, not the romantic default.',
        ],
        'Where to Stay in Ninh Binh anti-affiliate judgment' => [
            'This guide does not rank hotels by commission or scrape booking widgets; it chooses the base by the job it has to do in the route.',
        ],
        'Where to Stay in Ninh Binh source-limit framing' => [
            'They cannot decide your sleep tolerance, hotel lane, breakfast timing, luggage, children, or whether a beautiful room solves the next transfer.',
        ],
        'Where to Stay in Ninh Binh official destination source' => [
            'vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
        ],
        'Where to Stay in Ninh Binh boat source' => [
            'vietnam.travel/things-to-do/guide-boat-tours-ninh-binh',
        ],
        'Where to Stay in Ninh Binh UNESCO source' => [
            'whc.unesco.org/en/list/1438',
        ],
        'Where to Stay in Ninh Binh local tourism source' => [
            'dulichninhbinh.com.vn/en',
        ],
        'Where to Stay in Ninh Binh weather source' => [
            'weather-and-climate-vietnam',
        ],
        'Where to Stay in Ninh Binh transport source' => [
            'transport-within-vietnam',
        ],
        'Where to Stay in Ninh Binh rail source' => [
            'dsvn.vn',
        ],
        'Where to Stay in Ninh Binh hero image credit' => [
            'Hoang Giang Hai / CC BY 2.0',
        ],
        'Where to Stay in Ninh Binh Tam Coc image credit' => [
            'Franzfoto / CC BY-SA 3.0',
        ],
        'Where to Stay in Ninh Binh Trang An image credit' => [
            'Jakub Halun / CC BY 4.0',
        ],
        'Where to Stay in Ninh Binh Van Long image credit' => [
            'Andre Hospers / CC BY 4.0',
        ],
        'Where to Stay in Ninh Binh Cuc Phuong image credit' => [
            'hds / CC BY 2.0',
        ],
        'Where to Stay in Ninh Binh text-only credit policy' => [
            'Image credits are listed as text to keep reader focus on the base decision and reduce visible outbound clutter.',
        ],
    ]
);

$required_ninh_binh_stays_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$ninh_binh_stays_page = vg_verify_eeat_require_published_path(
    'destinations/where-to-stay-in-ninh-binh',
    'Where to Stay in Ninh Binh',
    $failures
);

if ($ninh_binh_stays_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $ninh_binh_stays_page,
        'Where to Stay in Ninh Binh',
        'destinations/where-to-stay-in-ninh-binh',
        'Where to Stay in Ninh Binh',
        $failures
    );

    vg_verify_eeat_require_page_sitemap_url(
        'destinations/where-to-stay-in-ninh-binh',
        'Where to Stay in Ninh Binh',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $ninh_binh_stays_page,
        'Where to Stay in Ninh Binh',
        $required_ninh_binh_stays_content_groups,
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $ninh_binh_stays_page,
        'Where to Stay in Ninh Binh',
        'rendered Where to Stay in Ninh Binh source trail snapshot',
        [
            'Source trail snapshot',
            'https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
            'https://vietnam.travel/things-to-do/guide-boat-tours-ninh-binh',
            'https://whc.unesco.org/en/list/1438/',
            'https://dulichninhbinh.com.vn/en/',
            'https://dsvn.vn/',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $ninh_binh_stays_page,
        'Where to Stay in Ninh Binh',
        'visible Where to Stay in Ninh Binh image credits',
        [
            'Tam_Coc_Rice_Valley_%288756354342%29.jpg',
            'Ninh_Binh-Tam_Coc.jpg',
            'Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg',
            'Van_Long_Nature_Reserve_Riet_Grotten_Kalksteen_Ninh_Binh_%2894589%29.jpg',
            'Forest_in_Cuc_Phuong_National_Park_%2815706323528%29.jpg',
            'Hoang Giang Hai / CC BY 2.0',
            'Franzfoto / CC BY-SA 3.0',
            'Jakub Halun / CC BY 4.0',
            'Andre Hospers / CC BY 4.0',
            'hds / CC BY 2.0',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $ninh_binh_stays_page,
        'Where to Stay in Ninh Binh',
        $required_ninh_binh_stays_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $ninh_binh_stays_page,
        'Where to Stay in Ninh Binh',
        'vietnamguide/source-trail',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $ninh_binh_stays_page,
        'Where to Stay in Ninh Binh',
        'vietnamguide/update-log',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $ninh_binh_stays_page,
        'Where to Stay in Ninh Binh',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_visible_external_body_link_budget(
        $ninh_binh_stays_page,
        'Where to Stay in Ninh Binh',
        0,
        2,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $ninh_binh_stays_page,
        'Where to Stay in Ninh Binh',
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $ninh_binh_stays_page,
        'Where to Stay in Ninh Binh',
        [
            '/destinations/ninh-binh-travel-guide/' => 'Ninh Binh Travel Guide',
            '/compare/ninh-binh-day-trip-vs-overnight/' => 'Ninh Binh Day Trip vs Overnight',
            '/compare/trang-an-vs-tam-coc/' => 'Trang An vs Tam Coc',
            '/plan/hanoi-to-ninh-binh-transport/' => 'Hanoi to Ninh Binh Transport',
            '/destinations/best-day-trips-from-hanoi/' => 'Best Day Trips from Hanoi',
            '/itineraries/hanoi-in-2-days/' => 'Hanoi in 2 Days',
            '/destinations/hanoi-travel-guide/' => 'Hanoi Travel Guide',
            '/destinations/where-to-stay-in-hanoi/' => 'Where to Stay in Hanoi',
            '/destinations/best-things-to-do-in-hanoi/' => 'Best Things to Do in Hanoi',
            '/destinations/ha-long-bay-travel-guide/' => 'Ha Long Bay Travel Guide',
            '/compare/ha-long-bay-vs-lan-ha-bay/' => 'Ha Long Bay vs Lan Ha Bay',
            '/destinations/cat-ba-travel-guide/' => 'Cat Ba Travel Guide',
            '/destinations/bai-tu-long-bay-guide/' => 'Bai Tu Long Bay Guide',
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/destinations/best-places-to-visit-vietnam/' => 'Best Places to Visit in Vietnam',
            '/destinations/unesco-heritage-sites-vietnam/' => 'UNESCO Heritage Sites in Vietnam',
            '/compare/north-central-south-vietnam/' => 'North vs Central vs South Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
            '/itineraries/7-days-in-vietnam/' => '7 Days in Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
        ],
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'compare/ninh-binh-day-trip-vs-overnight' => 'Ninh Binh Day Trip vs Overnight',
            'compare/trang-an-vs-tam-coc' => 'Trang An vs Tam Coc',
            'plan/hanoi-to-ninh-binh-transport' => 'Hanoi to Ninh Binh Transport',
            'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
            'itineraries/hanoi-in-2-days' => 'Hanoi in 2 Days',
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
        ],
        '/destinations/where-to-stay-in-ninh-binh/',
        'Where to Stay in Ninh Binh',
        $failures,
        true
    );
}

$required_ninh_binh_day_trip_overnight_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Ninh Binh day trip overnight hero marker' => [
            'vg-ninh-binh-day-trip-overnight-hero:v1',
        ],
        'Ninh Binh day trip overnight concierge verdict' => [
            'vg-ninh-binh-day-trip-overnight-concierge-verdict',
        ],
        'Ninh Binh day trip overnight at a glance' => [
            'vg-ninh-binh-day-trip-overnight-at-a-glance:v1',
        ],
        'Ninh Binh day trip overnight photo grid' => [
            'vg-ninh-binh-day-trip-overnight-photo-grid:v1',
        ],
        'Ninh Binh day trip overnight source diversity' => [
            'vg-ninh-binh-day-trip-overnight-source-diversity:v1',
        ],
        'Ninh Binh day trip overnight source trail snapshot' => [
            'vg-ninh-binh-day-trip-overnight-source-trail-snapshot:v1',
        ],
        'Ninh Binh day trip overnight decision matrix' => [
            'vg-ninh-binh-day-trip-overnight-decision-matrix:v1',
        ],
        'Ninh Binh day trip overnight route fit' => [
            'vg-ninh-binh-day-trip-overnight-route-fit:v1',
        ],
        'Ninh Binh day trip overnight sample schedules' => [
            'vg-ninh-binh-day-trip-overnight-sample-schedules:v1',
        ],
        'Ninh Binh day trip overnight transfer pressure' => [
            'vg-ninh-binh-day-trip-overnight-transfer-pressure:v1',
        ],
        'Ninh Binh day trip overnight Trang An Tam Coc' => [
            'vg-ninh-binh-day-trip-overnight-trang-an-tam-coc:v1',
        ],
        'Ninh Binh day trip overnight base logic' => [
            'vg-ninh-binh-day-trip-overnight-base-logic:v1',
        ],
        'Ninh Binh day trip overnight weather crowd pivots' => [
            'vg-ninh-binh-day-trip-overnight-weather-crowd-pivots:v1',
        ],
        'Ninh Binh day trip overnight cost comfort' => [
            'vg-ninh-binh-day-trip-overnight-cost-comfort:v1',
        ],
        'Ninh Binh day trip overnight mistakes skip' => [
            'vg-ninh-binh-day-trip-overnight-mistakes-skip:v1',
        ],
        'Ninh Binh day trip overnight live checks' => [
            'vg-ninh-binh-day-trip-overnight-live-checks:v1',
        ],
        'Ninh Binh day trip overnight FAQ' => [
            'vg-ninh-binh-day-trip-overnight-faq:v1',
        ],
        'Ninh Binh day trip overnight default verdict' => [
            'For most first-time international travelers, Ninh Binh is better as one overnight than as a Hanoi day trip.',
        ],
        'Ninh Binh day trip overnight day-trip exception' => [
            'A day trip is valid when Ninh Binh is the only countryside slot and the next morning is not fragile.',
        ],
        'Ninh Binh day trip overnight source limit framing' => [
            'They cannot decide your jet lag, hotel lane, group patience, heat tolerance, or whether tomorrow morning is already spoken for.',
        ],
        'Ninh Binh day trip overnight Vietnam.travel source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
        ],
        'Ninh Binh day trip overnight Northern Vietnam source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam',
        ],
        'Ninh Binh day trip overnight UNESCO Trang An source anchor' => [
            'whc.unesco.org/en/list/1438',
        ],
        'Ninh Binh day trip overnight tourism source anchor' => [
            'dulichninhbinh.com.vn/en',
        ],
        'Ninh Binh day trip overnight ticket source anchor' => [
            'dulichninhbinh.com.vn/en/printer/1801',
        ],
        'Ninh Binh day trip overnight weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Ninh Binh day trip overnight transport source anchor' => [
            'transport-within-vietnam',
        ],
        'Ninh Binh day trip overnight rail source anchor' => [
            'dsvn.vn',
        ],
        'Ninh Binh day trip overnight Trang An image credit' => [
            'Jakub Halun / CC BY 4.0',
        ],
        'Ninh Binh day trip overnight Tam Coc image credit' => [
            'Andre Hospers / CC BY 4.0',
        ],
        'Ninh Binh day trip overnight Mua Cave image credit' => [
            'Mua_Cave,_Ninh_Binh',
            'Jakub Halun / CC BY 4.0',
        ],
        'Ninh Binh day trip overnight Van Long image credit' => [
            'Van_Long_Nature_Reserve_Riet_Grotten',
            'Andre Hospers / CC BY 4.0',
        ],
        'Ninh Binh day trip overnight Cuc Phuong image credit' => [
            'Forest_in_Cuc_Phuong_National_Park',
            'hds / CC BY 2.0',
        ],
    ]
);

$required_ninh_binh_day_trip_overnight_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$ninh_binh_day_trip_overnight_page = vg_verify_eeat_require_published_path(
    'compare/ninh-binh-day-trip-vs-overnight',
    'Ninh Binh Day Trip vs Overnight',
    $failures
);

if ($ninh_binh_day_trip_overnight_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $ninh_binh_day_trip_overnight_page,
        'Ninh Binh Day Trip vs Overnight',
        'compare/ninh-binh-day-trip-vs-overnight',
        'Ninh Binh Day Trip vs Overnight',
        $failures
    );

    vg_verify_eeat_require_page_sitemap_url(
        'compare/ninh-binh-day-trip-vs-overnight',
        'Ninh Binh Day Trip vs Overnight',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $ninh_binh_day_trip_overnight_page,
        'Ninh Binh Day Trip vs Overnight',
        $required_ninh_binh_day_trip_overnight_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $ninh_binh_day_trip_overnight_page,
        'Ninh Binh Day Trip vs Overnight',
        $required_ninh_binh_day_trip_overnight_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $ninh_binh_day_trip_overnight_page,
        'Ninh Binh Day Trip vs Overnight',
        'vietnamguide/source-trail',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $ninh_binh_day_trip_overnight_page,
        'Ninh Binh Day Trip vs Overnight',
        'vietnamguide/update-log',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $ninh_binh_day_trip_overnight_page,
        'Ninh Binh Day Trip vs Overnight',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $ninh_binh_day_trip_overnight_page,
        'Ninh Binh Day Trip vs Overnight',
        $failures
    );

    vg_verify_eeat_require_visible_external_body_link_budget(
        $ninh_binh_day_trip_overnight_page,
        'Ninh Binh Day Trip vs Overnight',
        4,
        6,
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $ninh_binh_day_trip_overnight_page,
        'Ninh Binh Day Trip vs Overnight',
        'rendered Ninh Binh day trip vs overnight source trail snapshot',
        [
            'Source trail snapshot',
            'https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
            'https://whc.unesco.org/en/list/1438/',
            'https://dulichninhbinh.com.vn/en/printer/1801',
            'https://dsvn.vn/',
        ],
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $ninh_binh_day_trip_overnight_page,
        'Ninh Binh Day Trip vs Overnight',
        [
            '/destinations/ninh-binh-travel-guide/' => 'Ninh Binh Travel Guide',
            '/destinations/best-day-trips-from-hanoi/' => 'Best Day Trips from Hanoi',
            '/itineraries/hanoi-in-2-days/' => 'Hanoi in 2 Days',
            '/destinations/hanoi-travel-guide/' => 'Hanoi Travel Guide',
            '/destinations/ha-long-bay-travel-guide/' => 'Ha Long Bay Travel Guide',
            '/compare/ha-long-bay-vs-lan-ha-bay/' => 'Ha Long Bay vs Lan Ha Bay',
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/itineraries/7-days-in-vietnam/' => '7 Days in Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
        ],
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
        'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
        'itineraries/hanoi-in-2-days' => 'Hanoi in 2 Days',
        'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
        'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
        'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
        'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
        'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
        'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
        'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
    ],
    '/compare/ninh-binh-day-trip-vs-overnight/',
    'Ninh Binh Day Trip vs Overnight',
    $failures,
        true
);

$required_trang_an_tam_coc_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Trang An vs Tam Coc hero marker' => [
            'vg-trang-an-tam-coc-hero:v1',
        ],
        'Trang An vs Tam Coc concierge verdict' => [
            'vg-trang-an-tam-coc-concierge-verdict',
        ],
        'Trang An vs Tam Coc at a glance' => [
            'vg-trang-an-tam-coc-at-a-glance:v1',
        ],
        'Trang An vs Tam Coc photo proof' => [
            'vg-trang-an-tam-coc-photo-proof:v1',
        ],
        'Trang An vs Tam Coc source diversity' => [
            'vg-trang-an-tam-coc-source-diversity:v1',
        ],
        'Trang An vs Tam Coc source trail snapshot' => [
            'vg-trang-an-tam-coc-source-trail-snapshot:v1',
        ],
        'Trang An vs Tam Coc decision matrix' => [
            'vg-trang-an-tam-coc-decision-matrix:v1',
        ],
        'Trang An vs Tam Coc route comparison' => [
            'vg-trang-an-tam-coc-route-comparison:v1',
        ],
        'Trang An vs Tam Coc traveler fit' => [
            'vg-trang-an-tam-coc-traveler-fit:v1',
        ],
        'Trang An vs Tam Coc season photography' => [
            'vg-trang-an-tam-coc-season-photography:v1',
        ],
        'Trang An vs Tam Coc crowd timing' => [
            'vg-trang-an-tam-coc-crowd-timing:v1',
        ],
        'Trang An vs Tam Coc base logistics' => [
            'vg-trang-an-tam-coc-base-logistics:v1',
        ],
        'Trang An vs Tam Coc day trip overnight' => [
            'vg-trang-an-tam-coc-day-trip-overnight:v1',
        ],
        'Trang An vs Tam Coc booking audit' => [
            'vg-trang-an-tam-coc-booking-audit:v1',
        ],
        'Trang An vs Tam Coc mistakes skip' => [
            'vg-trang-an-tam-coc-mistakes-skip:v1',
        ],
        'Trang An vs Tam Coc live checks' => [
            'vg-trang-an-tam-coc-live-checks:v1',
        ],
        'Trang An vs Tam Coc FAQ' => [
            'vg-trang-an-tam-coc-faq:v1',
        ],
        'Trang An vs Tam Coc default verdict' => [
            'For most first-time international travelers choosing one boat trip, choose Trang An when certainty matters and Tam Coc when base rhythm matters.',
        ],
        'Trang An vs Tam Coc Tam Coc exception' => [
            'Tam Coc is not the weaker choice; it is the more lifestyle-led choice when rice fields, cycling, shorter water time, and a Tam Coc base are the reason you came.',
        ],
        'Trang An vs Tam Coc both warning' => [
            'Doing both Trang An and Tam Coc is useful only when the second boat changes the trip, not when it repeats the same limestone proof.',
        ],
        'Trang An vs Tam Coc source limit framing' => [
            'They cannot decide your patience for crowds, your hotel base, your heat tolerance, or whether a shorter boat is actually a better day.',
        ],
        'Trang An vs Tam Coc boat source anchor' => [
            'vietnam.travel/things-to-do/guide-boat-tours-ninh-binh',
        ],
        'Trang An vs Tam Coc Ninh Binh source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
        ],
        'Trang An vs Tam Coc UNESCO source anchor' => [
            'whc.unesco.org/en/list/1438',
        ],
        'Trang An vs Tam Coc tourism source anchor' => [
            'dulichninhbinh.com.vn/en',
        ],
        'Trang An vs Tam Coc weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Trang An vs Tam Coc transport source anchor' => [
            'transport-within-vietnam',
        ],
        'Trang An vs Tam Coc Trang An image credit' => [
            'Jakub Halun / CC BY 4.0',
        ],
        'Trang An vs Tam Coc Tam Coc image credit' => [
            'Andre Hospers / CC BY 4.0',
        ],
        'Trang An vs Tam Coc Tam Coc aerial image credit' => [
            'Tam_Coc_from_above.jpg',
            'Nomad Tales / CC BY-SA 2.0',
        ],
        'Trang An vs Tam Coc Trang An route image credit' => [
            'Trang_An_-_04.jpg',
            'Benjamin Smith / CC BY-SA 4.0',
        ],
        'Trang An vs Tam Coc Mua Cave image credit' => [
            'Mua_Cave,_Ninh_Binh',
            'Jakub Halun / CC BY 4.0',
        ],
        'Trang An vs Tam Coc text-only image credit policy' => [
            'Image credits are listed as text to keep reader focus on the decision and reduce visible outbound clutter.',
        ],
    ]
);

$required_trang_an_tam_coc_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$trang_an_tam_coc_page = vg_verify_eeat_require_published_path(
    'compare/trang-an-vs-tam-coc',
    'Trang An vs Tam Coc',
    $failures
);

if ($trang_an_tam_coc_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $trang_an_tam_coc_page,
        'Trang An vs Tam Coc',
        'compare/trang-an-vs-tam-coc',
        'Trang An vs Tam Coc',
        $failures
    );

    vg_verify_eeat_require_page_sitemap_url(
        'compare/trang-an-vs-tam-coc',
        'Trang An vs Tam Coc',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $trang_an_tam_coc_page,
        'Trang An vs Tam Coc',
        $required_trang_an_tam_coc_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $trang_an_tam_coc_page,
        'Trang An vs Tam Coc',
        $required_trang_an_tam_coc_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $trang_an_tam_coc_page,
        'Trang An vs Tam Coc',
        'vietnamguide/source-trail',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $trang_an_tam_coc_page,
        'Trang An vs Tam Coc',
        'vietnamguide/update-log',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $trang_an_tam_coc_page,
        'Trang An vs Tam Coc',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $trang_an_tam_coc_page,
        'Trang An vs Tam Coc',
        $failures
    );

    vg_verify_eeat_require_visible_external_body_link_budget(
        $trang_an_tam_coc_page,
        'Trang An vs Tam Coc',
        0,
        2,
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $trang_an_tam_coc_page,
        'Trang An vs Tam Coc',
        'rendered Trang An vs Tam Coc source trail snapshot',
        [
            'Source trail snapshot',
            'https://vietnam.travel/things-to-do/guide-boat-tours-ninh-binh',
            'https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
            'https://whc.unesco.org/en/list/1438/',
            'https://dulichninhbinh.com.vn/en/',
        ],
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $trang_an_tam_coc_page,
        'Trang An vs Tam Coc',
        [
            '/destinations/ninh-binh-travel-guide/' => 'Ninh Binh Travel Guide',
            '/compare/ninh-binh-day-trip-vs-overnight/' => 'Ninh Binh Day Trip vs Overnight',
            '/plan/hanoi-to-ninh-binh-transport/' => 'Hanoi to Ninh Binh Transport',
            '/destinations/best-day-trips-from-hanoi/' => 'Best Day Trips from Hanoi',
            '/itineraries/hanoi-in-2-days/' => 'Hanoi in 2 Days',
            '/destinations/hanoi-travel-guide/' => 'Hanoi Travel Guide',
            '/destinations/ha-long-bay-travel-guide/' => 'Ha Long Bay Travel Guide',
            '/compare/ha-long-bay-vs-lan-ha-bay/' => 'Ha Long Bay vs Lan Ha Bay',
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/itineraries/7-days-in-vietnam/' => '7 Days in Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
        ],
        $failures
    );
}

$trang_an_tam_coc_compare_hub = vg_verify_eeat_require_published_path(
    'compare',
    'Compare hub',
    $failures
);

if ($trang_an_tam_coc_compare_hub instanceof WP_Post) {
    vg_verify_eeat_require_raw_content(
        $trang_an_tam_coc_compare_hub,
        'Compare hub',
        'vg-trang-an-tam-coc-compare-hub-note:v1',
        $failures
    );
}

$trang_an_tam_coc_destinations_hub = vg_verify_eeat_require_published_path(
    'destinations',
    'Destinations hub',
    $failures
);

if ($trang_an_tam_coc_destinations_hub instanceof WP_Post) {
    vg_verify_eeat_require_raw_content(
        $trang_an_tam_coc_destinations_hub,
        'Destinations hub',
        'vg-trang-an-tam-coc-destinations-hub-note:v1',
        $failures
    );
}

$trang_an_tam_coc_homepage = vg_verify_eeat_homepage($failures);

if ($trang_an_tam_coc_homepage instanceof WP_Post) {
    vg_verify_eeat_require_raw_content(
        $trang_an_tam_coc_homepage,
        'Homepage',
        'href="/compare/trang-an-vs-tam-coc/"',
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
        'compare/ninh-binh-day-trip-vs-overnight' => 'Ninh Binh Day Trip vs Overnight',
        'plan/hanoi-to-ninh-binh-transport' => 'Hanoi to Ninh Binh Transport',
        'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
        'itineraries/hanoi-in-2-days' => 'Hanoi in 2 Days',
        'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
        'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
        'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
        'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
        'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
        'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
        'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
    ],
    '/compare/trang-an-vs-tam-coc/',
    'Trang An vs Tam Coc',
    $failures,
        true
);

$required_tam_coc_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Tam Coc Travel Guide hero marker' => [
            'vg-tam-coc-hero:v1',
        ],
        'Tam Coc Travel Guide concierge verdict' => [
            'vg-tam-coc-concierge-verdict',
        ],
        'Tam Coc Travel Guide at a glance' => [
            'vg-tam-coc-at-a-glance:v1',
        ],
        'Tam Coc Travel Guide photo proof' => [
            'vg-tam-coc-photo-proof:v1',
        ],
        'Tam Coc Travel Guide source diversity' => [
            'vg-tam-coc-source-diversity:v1',
        ],
        'Tam Coc Travel Guide source trail snapshot' => [
            'vg-tam-coc-source-trail-snapshot:v1',
        ],
        'Tam Coc Travel Guide route verdict' => [
            'vg-tam-coc-route-verdict:v1',
        ],
        'Tam Coc Travel Guide boat plan' => [
            'vg-tam-coc-boat-plan:v1',
        ],
        'Tam Coc Travel Guide bike walk' => [
            'vg-tam-coc-bike-walk:v1',
        ],
        'Tam Coc Travel Guide Bich Dong' => [
            'vg-tam-coc-bich-dong:v1',
        ],
        'Tam Coc Travel Guide Mua Cave' => [
            'vg-tam-coc-mua-cave:v1',
        ],
        'Tam Coc Travel Guide season rice' => [
            'vg-tam-coc-season-rice:v1',
        ],
        'Tam Coc Travel Guide stay eat' => [
            'vg-tam-coc-stay-eat:v1',
        ],
        'Tam Coc Travel Guide cost booking' => [
            'vg-tam-coc-cost-booking:v1',
        ],
        'Tam Coc Travel Guide mistakes skip' => [
            'vg-tam-coc-mistakes-skip:v1',
        ],
        'Tam Coc Travel Guide live checks' => [
            'vg-tam-coc-live-checks:v1',
        ],
        'Tam Coc Travel Guide FAQ' => [
            'vg-tam-coc-faq:v1',
        ],
        'Tam Coc Travel Guide default verdict' => [
            'For most international travelers, Tam Coc is best used as the soft Ninh Binh base: stay there if you want countryside rhythm, easy cycling, dinner choice, and a shorter boat experience, but choose Trang An when the trip needs one higher-certainty flagship boat route.',
        ],
        'Tam Coc Travel Guide base decision framing' => [
            'Tam Coc is not only a boat ride; it is a base decision.',
        ],
        'Tam Coc Travel Guide overstuffed day warning' => [
            'Do not build the day around every nearby viewpoint, cave, temple, and photo stop unless the route has room to breathe.',
        ],
        'Tam Coc Travel Guide anti-affiliate framing' => [
            'This guide does not rank Tam Coc by hotel commissions or scrape tour claims; it separates the decisions that keep a Ninh Binh stay calm.',
        ],
        'Tam Coc Travel Guide source-limit framing' => [
            'Sources can confirm destination context, heritage setting, ticket systems, weather pressure, and named attractions; they cannot decide your sleep tolerance, cycling confidence, heat limit, children, luggage, or whether another northern landscape stop would repeat the same job.',
        ],
        'Tam Coc Travel Guide Vietnam.travel Ninh Binh source' => [
            'vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
        ],
        'Tam Coc Travel Guide Vietnam.travel boat source' => [
            'vietnam.travel/things-to-do/guide-boat-tours-ninh-binh',
        ],
        'Tam Coc Travel Guide UNESCO source' => [
            'whc.unesco.org/en/list/1438',
        ],
        'Tam Coc Travel Guide Ninh Binh tourism source' => [
            'dulichninhbinh.com.vn/en',
        ],
        'Tam Coc Travel Guide weather source' => [
            'weather-and-climate-vietnam',
        ],
        'Tam Coc Travel Guide transport source' => [
            'transport-within-vietnam',
        ],
        'Tam Coc Travel Guide Tam Coc image credit' => [
            'Andre Hospers / CC BY 4.0',
        ],
        'Tam Coc Travel Guide Bich Dong image credit' => [
            'Jakub Halun / CC BY 4.0',
        ],
        'Tam Coc Travel Guide Mua Cave image credit' => [
            'Superbass / CC BY-SA 4.0',
        ],
        'Tam Coc Travel Guide Ninh Hai sunset image credit' => [
            'Jakub Halun / CC BY 4.0',
        ],
        'Tam Coc Travel Guide Thai Vi image credit' => [
            'Shyamal / CC BY-SA 4.0',
        ],
        'Tam Coc Travel Guide text-only credit policy' => [
            'Image credits are listed as text to keep the base decision readable and reduce visible outbound clutter.',
        ],
    ]
);

$required_tam_coc_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$tam_coc_guide_page = vg_verify_eeat_require_published_path(
    'destinations/tam-coc-travel-guide',
    'Tam Coc Travel Guide',
    $failures
);

if ($tam_coc_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $tam_coc_guide_page,
        'Tam Coc Travel Guide',
        'destinations/tam-coc-travel-guide',
        'Tam Coc Travel Guide',
        $failures
    );

    vg_verify_eeat_require_page_sitemap_url(
        'destinations/tam-coc-travel-guide',
        'Tam Coc Travel Guide',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $tam_coc_guide_page,
        'Tam Coc Travel Guide',
        $required_tam_coc_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $tam_coc_guide_page,
        'Tam Coc Travel Guide',
        'rendered Tam Coc Travel Guide source trail snapshot',
        [
            'Source trail snapshot',
            'https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
            'https://vietnam.travel/things-to-do/guide-boat-tours-ninh-binh',
            'https://whc.unesco.org/en/list/1438/',
            'https://dulichninhbinh.com.vn/en/',
            'https://vietnam.travel/things-to-do/weather-and-climate-vietnam',
            'https://vietnamguide.net/plan/transport-within-vietnam/',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $tam_coc_guide_page,
        'Tam Coc Travel Guide',
        'visible Tam Coc Travel Guide image credits',
        [
            'Tam_Coc_Ninh_Binh_%2853115%29.jpg',
            'Bich_Dong_Pagoda%2C_Ninh_Binh%2C_Vietnam%2C_20240203_1126_5619.jpg',
            '2024-03-30-Mua_Cave_Dragon_Mountain-3758.jpg',
            'Sunset_in_Ninh_H%E1%BA%A3i%2C_Ninh_Binh_province%2C_Vietnam%2C_20240202_1724_5415.jpg',
            'Thai_Vi_Temple_2.jpg',
            'Andre Hospers / CC BY 4.0',
            'Jakub Halun / CC BY 4.0',
            'Superbass / CC BY-SA 4.0',
            'Shyamal / CC BY-SA 4.0',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $tam_coc_guide_page,
        'Tam Coc Travel Guide',
        $required_tam_coc_guide_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $tam_coc_guide_page,
        'Tam Coc Travel Guide',
        'vietnamguide/source-trail',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $tam_coc_guide_page,
        'Tam Coc Travel Guide',
        'vietnamguide/update-log',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $tam_coc_guide_page,
        'Tam Coc Travel Guide',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_visible_external_body_link_budget(
        $tam_coc_guide_page,
        'Tam Coc Travel Guide',
        0,
        2,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $tam_coc_guide_page,
        'Tam Coc Travel Guide',
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $tam_coc_guide_page,
        'Tam Coc Travel Guide',
        [
            '/destinations/ninh-binh-travel-guide/' => 'Ninh Binh Travel Guide',
            '/destinations/where-to-stay-in-ninh-binh/' => 'Where to Stay in Ninh Binh',
            '/compare/trang-an-vs-tam-coc/' => 'Trang An vs Tam Coc',
            '/compare/ninh-binh-day-trip-vs-overnight/' => 'Ninh Binh Day Trip vs Overnight',
            '/plan/hanoi-to-ninh-binh-transport/' => 'Hanoi to Ninh Binh Transport',
            '/plan/ninh-binh-to-ha-long-bay-transfer/' => 'Ninh Binh to Ha Long Bay Transfer',
            '/destinations/best-day-trips-from-hanoi/' => 'Best Day Trips from Hanoi',
            '/itineraries/hanoi-in-2-days/' => 'Hanoi in 2 Days',
            '/destinations/hanoi-travel-guide/' => 'Hanoi Travel Guide',
            '/destinations/where-to-stay-in-hanoi/' => 'Where to Stay in Hanoi',
            '/destinations/ha-long-bay-travel-guide/' => 'Ha Long Bay Travel Guide',
            '/compare/ha-long-bay-vs-lan-ha-bay/' => 'Ha Long Bay vs Lan Ha Bay',
            '/destinations/cat-ba-travel-guide/' => 'Cat Ba Travel Guide',
            '/destinations/bai-tu-long-bay-guide/' => 'Bai Tu Long Bay Guide',
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/destinations/best-places-to-visit-vietnam/' => 'Best Places to Visit in Vietnam',
            '/destinations/unesco-heritage-sites-vietnam/' => 'UNESCO Heritage Sites in Vietnam',
            '/compare/north-central-south-vietnam/' => 'North vs Central vs South Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
            '/itineraries/7-days-in-vietnam/' => '7 Days in Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
        ],
        $failures
    );
}

$tam_coc_destinations_hub = vg_verify_eeat_require_published_path(
    'destinations',
    'Destinations hub',
    $failures
);

if ($tam_coc_destinations_hub instanceof WP_Post) {
    vg_verify_eeat_require_raw_content(
        $tam_coc_destinations_hub,
        'Destinations hub',
        'vg-tam-coc-destinations-hub-note:v1',
        $failures
    );
}

$tam_coc_homepage = vg_verify_eeat_homepage($failures);

if ($tam_coc_homepage instanceof WP_Post) {
    vg_verify_eeat_require_raw_content(
        $tam_coc_homepage,
        'Homepage',
        'href="/destinations/tam-coc-travel-guide/"',
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
        'compare/ninh-binh-day-trip-vs-overnight' => 'Ninh Binh Day Trip vs Overnight',
        'compare/trang-an-vs-tam-coc' => 'Trang An vs Tam Coc',
        'plan/hanoi-to-ninh-binh-transport' => 'Hanoi to Ninh Binh Transport',
        'destinations/where-to-stay-in-ninh-binh' => 'Where to Stay in Ninh Binh',
        'plan/ninh-binh-to-ha-long-bay-transfer' => 'Ninh Binh to Ha Long Bay Transfer',
        'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
        'itineraries/hanoi-in-2-days' => 'Hanoi in 2 Days',
        'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
        'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
        'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
        'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
        'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
        'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
    ],
    '/destinations/tam-coc-travel-guide/',
    'Tam Coc Travel Guide',
    $failures,
        true
);

$required_hanoi_ninh_binh_transport_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Hanoi to Ninh Binh transport hero marker' => [
            'vg-hanoi-ninh-binh-transport-hero:v1',
        ],
        'Hanoi to Ninh Binh transport concierge verdict' => [
            'vg-hanoi-ninh-binh-transport-concierge-verdict',
        ],
        'Hanoi to Ninh Binh transport at a glance' => [
            'vg-hanoi-ninh-binh-transport-at-a-glance:v1',
        ],
        'Hanoi to Ninh Binh transport photo grid' => [
            'vg-hanoi-ninh-binh-transport-photo-grid:v1',
        ],
        'Hanoi to Ninh Binh transport source diversity' => [
            'vg-hanoi-ninh-binh-transport-source-diversity:v1',
        ],
        'Hanoi to Ninh Binh transport source trail snapshot' => [
            'vg-hanoi-ninh-binh-transport-source-trail-snapshot:v1',
        ],
        'Hanoi to Ninh Binh transport mode matrix' => [
            'vg-hanoi-ninh-binh-transport-mode-matrix:v1',
        ],
        'Hanoi to Ninh Binh transport door to door' => [
            'vg-hanoi-ninh-binh-transport-door-to-door:v1',
        ],
        'Hanoi to Ninh Binh transport departure arrival' => [
            'vg-hanoi-ninh-binh-transport-departure-arrival:v1',
        ],
        'Hanoi to Ninh Binh transport train guide' => [
            'vg-hanoi-ninh-binh-transport-train-guide:v1',
        ],
        'Hanoi to Ninh Binh transport limousine van' => [
            'vg-hanoi-ninh-binh-transport-limousine-van:v1',
        ],
        'Hanoi to Ninh Binh transport private car' => [
            'vg-hanoi-ninh-binh-transport-private-car:v1',
        ],
        'Hanoi to Ninh Binh transport day tour onward' => [
            'vg-hanoi-ninh-binh-transport-day-tour-onward:v1',
        ],
        'Hanoi to Ninh Binh transport base luggage' => [
            'vg-hanoi-ninh-binh-transport-base-luggage:v1',
        ],
        'Hanoi to Ninh Binh transport sample routing' => [
            'vg-hanoi-ninh-binh-transport-sample-routing:v1',
        ],
        'Hanoi to Ninh Binh transport booking audit' => [
            'vg-hanoi-ninh-binh-transport-booking-audit:v1',
        ],
        'Hanoi to Ninh Binh transport failure modes' => [
            'vg-hanoi-ninh-binh-transport-failure-modes:v1',
        ],
        'Hanoi to Ninh Binh transport live checks' => [
            'vg-hanoi-ninh-binh-transport-live-checks:v1',
        ],
        'Hanoi to Ninh Binh transport FAQ' => [
            'vg-hanoi-ninh-binh-transport-faq:v1',
        ],
        'Hanoi to Ninh Binh transport default verdict' => [
            'For most independent international travelers, the best default is a limousine van only when the exact drop-off works; otherwise choose train for rail-centered plans or private car when control protects the day.',
        ],
        'Hanoi to Ninh Binh transport private car premium judgment' => [
            'Private car is the premium answer when luggage, children, a late arrival, a countryside hotel, or an onward bay/airport handoff would make shared transport brittle.',
        ],
        'Hanoi to Ninh Binh transport train caveat' => [
            'Train is a clean choice when Ninh Binh station is useful, not when the real destination is a Tam Coc lane without a final-transfer plan.',
        ],
        'Hanoi to Ninh Binh transport source limit framing' => [
            'Sources can confirm official destination context, rail portals, and tourism framing; they cannot decide your luggage, sleep debt, hotel lane, or tomorrow morning.',
        ],
        'Hanoi to Ninh Binh transport Vietnam.travel source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
        ],
        'Hanoi to Ninh Binh transport transport source anchor' => [
            'vietnam.travel/plan-your-trip/transport-within-vietnam',
        ],
        'Hanoi to Ninh Binh transport rail ticket source anchor' => [
            'dsvn.vn',
        ],
        'Hanoi to Ninh Binh transport rail corporate source anchor' => [
            'vr.com.vn/en',
        ],
        'Hanoi to Ninh Binh transport tourism source anchor' => [
            'dulichninhbinh.com.vn/en',
        ],
        'Hanoi to Ninh Binh transport UNESCO source anchor' => [
            'whc.unesco.org/en/list/1438',
        ],
        'Hanoi to Ninh Binh transport Hanoi railway image credit' => [
            'Alancrh / CC BY-SA 3.0',
        ],
        'Hanoi to Ninh Binh transport Trang An image credit' => [
            'Jakub Halun / CC BY 4.0',
        ],
        'Hanoi to Ninh Binh transport Tam Coc image credit' => [
            'Andre Hospers / CC BY 4.0',
        ],
        'Hanoi to Ninh Binh transport Noi Bai image credit' => [
            'Sky 269 / CC BY-SA 4.0',
        ],
    ]
);

$required_hanoi_ninh_binh_transport_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$hanoi_ninh_binh_transport_page = vg_verify_eeat_require_published_path(
    'plan/hanoi-to-ninh-binh-transport',
    'Hanoi to Ninh Binh Transport',
    $failures
);

if ($hanoi_ninh_binh_transport_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $hanoi_ninh_binh_transport_page,
        'Hanoi to Ninh Binh Transport',
        'plan/hanoi-to-ninh-binh-transport',
        'Hanoi to Ninh Binh Transport',
        $failures
    );

    vg_verify_eeat_require_page_sitemap_url(
        'plan/hanoi-to-ninh-binh-transport',
        'Hanoi to Ninh Binh Transport',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $hanoi_ninh_binh_transport_page,
        'Hanoi to Ninh Binh Transport',
        $required_hanoi_ninh_binh_transport_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $hanoi_ninh_binh_transport_page,
        'Hanoi to Ninh Binh Transport',
        $required_hanoi_ninh_binh_transport_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hanoi_ninh_binh_transport_page,
        'Hanoi to Ninh Binh Transport',
        'vietnamguide/source-trail',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hanoi_ninh_binh_transport_page,
        'Hanoi to Ninh Binh Transport',
        'vietnamguide/update-log',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hanoi_ninh_binh_transport_page,
        'Hanoi to Ninh Binh Transport',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $hanoi_ninh_binh_transport_page,
        'Hanoi to Ninh Binh Transport',
        $failures
    );

    vg_verify_eeat_require_visible_external_body_link_budget(
        $hanoi_ninh_binh_transport_page,
        'Hanoi to Ninh Binh Transport',
        4,
        6,
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $hanoi_ninh_binh_transport_page,
        'Hanoi to Ninh Binh Transport',
        'rendered Hanoi to Ninh Binh transport source trail snapshot',
        [
            'Source trail snapshot',
            'https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
            'https://dsvn.vn/',
            'https://vr.com.vn/en',
            'https://dulichninhbinh.com.vn/en/',
        ],
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $hanoi_ninh_binh_transport_page,
        'Hanoi to Ninh Binh Transport',
        [
            '/destinations/ninh-binh-travel-guide/' => 'Ninh Binh Travel Guide',
            '/compare/ninh-binh-day-trip-vs-overnight/' => 'Ninh Binh Day Trip vs Overnight',
            '/destinations/best-day-trips-from-hanoi/' => 'Best Day Trips from Hanoi',
            '/destinations/hanoi-travel-guide/' => 'Hanoi Travel Guide',
            '/itineraries/hanoi-in-2-days/' => 'Hanoi in 2 Days',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/compare/north-central-south-vietnam/' => 'North vs Central vs South Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/money-cash-cards-atms/' => 'Money in Vietnam',
            '/plan/sim-esim-vietnam/' => 'SIM and eSIM in Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
            '/destinations/ha-long-bay-travel-guide/' => 'Ha Long Bay Travel Guide',
            '/compare/ha-long-bay-vs-lan-ha-bay/' => 'Ha Long Bay vs Lan Ha Bay',
            '/itineraries/7-days-in-vietnam/' => '7 Days in Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
        ],
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
        'compare/ninh-binh-day-trip-vs-overnight' => 'Ninh Binh Day Trip vs Overnight',
        'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
        'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
        'itineraries/hanoi-in-2-days' => 'Hanoi in 2 Days',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
        'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
        'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
        'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
        'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
    ],
    '/plan/hanoi-to-ninh-binh-transport/',
    'Hanoi to Ninh Binh Transport',
    $failures,
        true
);

$required_ninh_binh_ha_long_transfer_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Ninh Binh to Ha Long Bay Transfer hero marker' => [
            'vg-ninh-binh-ha-long-transfer-hero:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer concierge verdict' => [
            'vg-ninh-binh-ha-long-transfer-concierge-verdict',
        ],
        'Ninh Binh to Ha Long Bay Transfer at a glance' => [
            'vg-ninh-binh-ha-long-transfer-at-a-glance:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer photo proof' => [
            'vg-ninh-binh-ha-long-transfer-photo-proof:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer source diversity' => [
            'vg-ninh-binh-ha-long-transfer-source-diversity:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer source trail snapshot' => [
            'vg-ninh-binh-ha-long-transfer-source-trail-snapshot:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer route verdict' => [
            'vg-ninh-binh-ha-long-transfer-route-verdict:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer mode matrix' => [
            'vg-ninh-binh-ha-long-transfer-mode-matrix:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer port first audit' => [
            'vg-ninh-binh-ha-long-transfer-port-first:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer pickup window' => [
            'vg-ninh-binh-ha-long-transfer-pickup-window:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer base luggage logic' => [
            'vg-ninh-binh-ha-long-transfer-base-luggage:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer cruise handoff' => [
            'vg-ninh-binh-ha-long-transfer-cruise-handoff:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer Lan Ha Cat Ba exception' => [
            'vg-ninh-binh-ha-long-transfer-lan-ha-cat-ba:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer overnight buffer' => [
            'vg-ninh-binh-ha-long-transfer-overnight-buffer:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer season weather' => [
            'vg-ninh-binh-ha-long-transfer-season-weather:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer cost booking' => [
            'vg-ninh-binh-ha-long-transfer-cost-booking:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer booking audit' => [
            'vg-ninh-binh-ha-long-transfer-booking-audit:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer mistakes skip' => [
            'vg-ninh-binh-ha-long-transfer-mistakes-skip:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer live checks' => [
            'vg-ninh-binh-ha-long-transfer-live-checks:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer FAQ' => [
            'vg-ninh-binh-ha-long-transfer-faq:v1',
        ],
        'Ninh Binh to Ha Long Bay Transfer default verdict' => [
            'For most international travelers, the safest default is a private transfer or cruise-arranged transfer only after the exact port and pickup window are confirmed.',
        ],
        'Ninh Binh to Ha Long Bay Transfer port first warning' => [
            'Do not book a generic Ha Long transfer until you know whether your cruise leaves from Tuan Chau, Ha Long International Cruise Port, Hon Gai, Got Pier, or a Cat Ba/Lan Ha pickup point.',
        ],
        'Ninh Binh to Ha Long Bay Transfer cheap seat warning' => [
            'The cheapest seat can become expensive if it reaches the wrong city, wrong pier, or too late for embarkation.',
        ],
        'Ninh Binh to Ha Long Bay Transfer anti-stale framing' => [
            'This guide does not publish stale timetables or scrape operator claims; it teaches the transfer decision that protects the cruise day.',
        ],
        'Ninh Binh to Ha Long Bay Transfer source-limit framing' => [
            'Sources can confirm destination context, port and route systems, heritage status, weather pressure, and transport categories; they cannot decide your luggage, hotel lane, cruise check-in, child fatigue, or whether one more northern stop makes the route brittle.',
        ],
        'Ninh Binh to Ha Long Bay Transfer Vietnam.travel Ninh Binh source' => [
            'vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
        ],
        'Ninh Binh to Ha Long Bay Transfer Vietnam.travel Ha Long source' => [
            'vietnam.travel/places-to-go/northern-vietnam/ha-long',
        ],
        'Ninh Binh to Ha Long Bay Transfer transport source' => [
            'vietnam.travel/plan-your-trip/transport-within-vietnam',
        ],
        'Ninh Binh to Ha Long Bay Transfer weather source' => [
            'weather-and-climate-vietnam',
        ],
        'Ninh Binh to Ha Long Bay Transfer UNESCO source' => [
            'whc.unesco.org/en/list/672',
        ],
        'Ninh Binh to Ha Long Bay Transfer Ha Long management source' => [
            'halongbay.com.vn',
        ],
        'Ninh Binh to Ha Long Bay Transfer local Ninh Binh source' => [
            'dulichninhbinh.com.vn/en',
        ],
        'Ninh Binh to Ha Long Bay Transfer Cat Ba source' => [
            'catba.com.vn',
        ],
        'Ninh Binh to Ha Long Bay Transfer Tam Coc image credit' => [
            'Hoang Giang Hai / CC BY 2.0',
        ],
        'Ninh Binh to Ha Long Bay Transfer Ha Long aerial image credit' => [
            'Vyacheslav Argenberg / CC BY 4.0',
        ],
        'Ninh Binh to Ha Long Bay Transfer Lan Ha image credit' => [
            'Saaremees / CC BY-SA 4.0',
        ],
        'Ninh Binh to Ha Long Bay Transfer cruise boats image credit' => [
            'Shyamal L. / CC BY-SA 4.0',
        ],
        'Ninh Binh to Ha Long Bay Transfer Bai Chay image credit' => [
            'Pdhadam / CC BY-SA 4.0',
        ],
        'Ninh Binh to Ha Long Bay Transfer text-only credit policy' => [
            'Image credits are listed as text to keep the route decision readable and reduce visible outbound clutter.',
        ],
    ]
);

$required_ninh_binh_ha_long_transfer_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$ninh_binh_ha_long_transfer_page = vg_verify_eeat_require_published_path(
    'plan/ninh-binh-to-ha-long-bay-transfer',
    'Ninh Binh to Ha Long Bay Transfer',
    $failures
);

if ($ninh_binh_ha_long_transfer_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $ninh_binh_ha_long_transfer_page,
        'Ninh Binh to Ha Long Bay Transfer',
        'plan/ninh-binh-to-ha-long-bay-transfer',
        'Ninh Binh to Ha Long Bay Transfer',
        $failures
    );

    vg_verify_eeat_require_page_sitemap_url(
        'plan/ninh-binh-to-ha-long-bay-transfer',
        'Ninh Binh to Ha Long Bay Transfer',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $ninh_binh_ha_long_transfer_page,
        'Ninh Binh to Ha Long Bay Transfer',
        $required_ninh_binh_ha_long_transfer_content_groups,
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $ninh_binh_ha_long_transfer_page,
        'Ninh Binh to Ha Long Bay Transfer',
        'rendered Ninh Binh to Ha Long Bay Transfer source trail snapshot',
        [
            'Source trail snapshot',
            'https://vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
            'https://vietnam.travel/places-to-go/northern-vietnam/ha-long',
            'https://vietnam.travel/plan-your-trip/transport-within-vietnam',
            'https://vietnam.travel/things-to-do/weather-and-climate-vietnam',
            'https://whc.unesco.org/en/list/672/',
            'https://halongbay.com.vn/',
            'https://dulichninhbinh.com.vn/en/',
            'https://catba.com.vn/',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $ninh_binh_ha_long_transfer_page,
        'Ninh Binh to Ha Long Bay Transfer',
        'visible Ninh Binh to Ha Long Bay Transfer image credits',
        [
            'Tam_Coc_Rice_Valley_%288756354342%29.jpg',
            'Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg',
            'Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg',
            'Halong_Bay_Cruise_Boats_01.jpg',
            'Bai_Chay_Pano_10-2023.jpg',
            'Hoang Giang Hai / CC BY 2.0',
            'Vyacheslav Argenberg / CC BY 4.0',
            'Saaremees / CC BY-SA 4.0',
            'Shyamal L. / CC BY-SA 4.0',
            'Pdhadam / CC BY-SA 4.0',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $ninh_binh_ha_long_transfer_page,
        'Ninh Binh to Ha Long Bay Transfer',
        $required_ninh_binh_ha_long_transfer_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $ninh_binh_ha_long_transfer_page,
        'Ninh Binh to Ha Long Bay Transfer',
        'vietnamguide/source-trail',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $ninh_binh_ha_long_transfer_page,
        'Ninh Binh to Ha Long Bay Transfer',
        'vietnamguide/update-log',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $ninh_binh_ha_long_transfer_page,
        'Ninh Binh to Ha Long Bay Transfer',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_visible_external_body_link_budget(
        $ninh_binh_ha_long_transfer_page,
        'Ninh Binh to Ha Long Bay Transfer',
        0,
        2,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $ninh_binh_ha_long_transfer_page,
        'Ninh Binh to Ha Long Bay Transfer',
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $ninh_binh_ha_long_transfer_page,
        'Ninh Binh to Ha Long Bay Transfer',
        [
            '/destinations/ninh-binh-travel-guide/' => 'Ninh Binh Travel Guide',
            '/destinations/where-to-stay-in-ninh-binh/' => 'Where to Stay in Ninh Binh',
            '/compare/ninh-binh-day-trip-vs-overnight/' => 'Ninh Binh Day Trip vs Overnight',
            '/compare/trang-an-vs-tam-coc/' => 'Trang An vs Tam Coc',
            '/plan/hanoi-to-ninh-binh-transport/' => 'Hanoi to Ninh Binh Transport',
            '/destinations/ha-long-bay-travel-guide/' => 'Ha Long Bay Travel Guide',
            '/compare/ha-long-bay-vs-lan-ha-bay/' => 'Ha Long Bay vs Lan Ha Bay',
            '/destinations/cat-ba-travel-guide/' => 'Cat Ba Travel Guide',
            '/destinations/bai-tu-long-bay-guide/' => 'Bai Tu Long Bay Guide',
            '/destinations/best-day-trips-from-hanoi/' => 'Best Day Trips from Hanoi',
            '/itineraries/hanoi-in-2-days/' => 'Hanoi in 2 Days',
            '/destinations/hanoi-travel-guide/' => 'Hanoi Travel Guide',
            '/destinations/where-to-stay-in-hanoi/' => 'Where to Stay in Hanoi',
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/destinations/best-places-to-visit-vietnam/' => 'Best Places to Visit in Vietnam',
            '/destinations/unesco-heritage-sites-vietnam/' => 'UNESCO Heritage Sites in Vietnam',
            '/compare/north-central-south-vietnam/' => 'North vs Central vs South Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
            '/itineraries/7-days-in-vietnam/' => '7 Days in Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
        ],
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
        'compare/ninh-binh-day-trip-vs-overnight' => 'Ninh Binh Day Trip vs Overnight',
        'compare/trang-an-vs-tam-coc' => 'Trang An vs Tam Coc',
        'plan/hanoi-to-ninh-binh-transport' => 'Hanoi to Ninh Binh Transport',
        'destinations/where-to-stay-in-ninh-binh' => 'Where to Stay in Ninh Binh',
        'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
        'itineraries/hanoi-in-2-days' => 'Hanoi in 2 Days',
        'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
        'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
        'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
        'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
        'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
        'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
    ],
    '/plan/ninh-binh-to-ha-long-bay-transfer/',
    'Ninh Binh to Ha Long Bay Transfer',
    $failures,
        true
);

$required_ha_long_lan_ha_comparison_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Ha Long/Lan Ha hero marker' => [
            'vg-ha-long-lan-ha-hero:v1',
        ],
        'Ha Long/Lan Ha concierge verdict' => [
            'vg-ha-long-lan-ha-concierge-verdict',
        ],
        'Ha Long/Lan Ha at a glance' => [
            'vg-ha-long-lan-ha-at-a-glance:v1',
        ],
        'Ha Long/Lan Ha photo grid' => [
            'vg-ha-long-lan-ha-photo-grid:v1',
        ],
        'Ha Long/Lan Ha decision matrix' => [
            'vg-ha-long-lan-ha-decision-matrix:v1',
        ],
        'Ha Long/Lan Ha cruise style' => [
            'vg-ha-long-lan-ha-cruise-style:v1',
        ],
        'Ha Long/Lan Ha route fit' => [
            'vg-ha-long-lan-ha-route-fit:v1',
        ],
        'Ha Long/Lan Ha weather cancellation' => [
            'vg-ha-long-lan-ha-weather-cancellation:v1',
        ],
        'Ha Long/Lan Ha skip logic' => [
            'vg-ha-long-lan-ha-skip-logic:v1',
        ],
        'Ha Long/Lan Ha live checks' => [
            'vg-ha-long-lan-ha-live-checks:v1',
        ],
        'Ha Long/Lan Ha FAQ' => [
            'vg-ha-long-lan-ha-faq:v1',
        ],
        'Ha Long official source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam/ha-long',
        ],
        'Ha Long/Lan Ha UNESCO source anchor' => [
            'whc.unesco.org/en/list/672',
        ],
        'Ha Long Bay Management source anchor' => [
            'halongbay.com.vn',
        ],
        'Cat Ba source anchor' => [
            'catba.com.vn',
        ],
        'Ha Long/Lan Ha weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Ha Long/Lan Ha transport source anchor' => [
            'transport-within-vietnam',
        ],
        'Ha Long/Lan Ha hero image credit' => [
            'Vyacheslav Argenberg / CC BY 4.0',
        ],
        'Lan Ha image credit' => [
            'Saaremees / CC BY-SA 4.0',
        ],
        'Cat Ba image credit' => [
            'Christophe95 / CC BY-SA 4.0',
        ],
        'Ha Long cruise image credit' => [
            'Shyamal L. / CC BY-SA 4.0',
        ],
        'Sung Sot image credit' => [
            'Jakub Halun / CC BY 4.0',
        ],
    ]
);

$required_ha_long_lan_ha_comparison_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$ha_long_lan_ha_comparison_page = vg_verify_eeat_require_published_path(
    'compare/ha-long-bay-vs-lan-ha-bay',
    'Ha Long Bay vs Lan Ha Bay guide',
    $failures
);

if ($ha_long_lan_ha_comparison_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $ha_long_lan_ha_comparison_page,
        'Ha Long Bay vs Lan Ha Bay guide',
        $required_ha_long_lan_ha_comparison_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $ha_long_lan_ha_comparison_page,
        'Ha Long Bay vs Lan Ha Bay guide',
        $required_ha_long_lan_ha_comparison_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $ha_long_lan_ha_comparison_page,
        'Ha Long Bay vs Lan Ha Bay guide',
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
        'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
        'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
    ],
    '/compare/ha-long-bay-vs-lan-ha-bay/',
    'Ha Long Bay vs Lan Ha Bay guide',
    $failures
);

$required_da_nang_hoi_an_comparison_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Da Nang/Hoi An hero marker' => [
            'vg-da-nang-hoi-an-hero:v1',
        ],
        'Da Nang/Hoi An concierge verdict' => [
            'vg-da-nang-hoi-an-concierge-verdict',
        ],
        'Da Nang/Hoi An at a glance' => [
            'vg-da-nang-hoi-an-at-a-glance:v1',
        ],
        'Da Nang/Hoi An photo grid' => [
            'vg-da-nang-hoi-an-photo-grid:v1',
        ],
        'Da Nang/Hoi An decision matrix' => [
            'vg-da-nang-hoi-an-decision-matrix:v1',
        ],
        'Da Nang/Hoi An base chooser' => [
            'vg-da-nang-hoi-an-base-chooser:v1',
        ],
        'Da Nang/Hoi An route fit' => [
            'vg-da-nang-hoi-an-route-fit:v1',
        ],
        'Da Nang/Hoi An season weather' => [
            'vg-da-nang-hoi-an-season-weather:v1',
        ],
        'Da Nang/Hoi An transfer cost' => [
            'vg-da-nang-hoi-an-transfer-cost:v1',
        ],
        'Da Nang/Hoi An skip logic' => [
            'vg-da-nang-hoi-an-skip-logic:v1',
        ],
        'Da Nang/Hoi An live checks' => [
            'vg-da-nang-hoi-an-live-checks:v1',
        ],
        'Da Nang/Hoi An FAQ' => [
            'vg-da-nang-hoi-an-faq:v1',
        ],
        'Da Nang official source anchor' => [
            'vietnam.travel/places-to-go/central-vietnam/da-nang',
        ],
        'Da Nang Central Vietnam source anchor' => [
            'href="https://vietnam.travel/places-to-go/central-vietnam"',
        ],
        'Da Nang weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Da Nang transport source anchor' => [
            'vietnam.travel/plan-your-trip/transport-within-vietnam',
        ],
        'Da Nang tourism source anchor' => [
            'danangfantasticity.com/en',
        ],
        'Hoi An official source anchor' => [
            'vietnam.travel/places-to-go/central-vietnam/hoi-an',
        ],
        'Hoi An UNESCO source anchor' => [
            'whc.unesco.org/en/list/948',
        ],
        'Hoi An beach source anchor' => [
            'vietnam.travel/node/1395',
        ],
        'Da Nang My Khe image credit' => [
            'Christophe95 / CC BY-SA 4.0',
        ],
        'Da Nang Dragon Bridge image credit' => [
            'Supanut Arunoprayote / CC BY 4.0',
        ],
        'Hai Van Pass image credit' => [
            'Wolkenkratzer / CC BY-SA 4.0',
        ],
        'Hoi An Ancient Town image credit' => [
            'Steffen Schmitz / CC BY-SA 4.0',
        ],
        'An Bang Beach image credit' => [
            'Alexkom000 / CC BY 4.0',
        ],
    ]
);

$required_da_nang_hoi_an_comparison_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$da_nang_hoi_an_comparison_page = vg_verify_eeat_require_published_path(
    'compare/da-nang-vs-hoi-an',
    'Da Nang vs Hoi An guide',
    $failures
);

if ($da_nang_hoi_an_comparison_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $da_nang_hoi_an_comparison_page,
        'Da Nang vs Hoi An guide',
        $required_da_nang_hoi_an_comparison_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $da_nang_hoi_an_comparison_page,
        'Da Nang vs Hoi An guide',
        $required_da_nang_hoi_an_comparison_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $da_nang_hoi_an_comparison_page,
        'Da Nang vs Hoi An guide',
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
        'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
        'destinations/best-things-to-do-in-hue' => 'Best Things to Do in Hue guide',
        'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
    ],
    '/compare/da-nang-vs-hoi-an/',
    'Da Nang vs Hoi An guide',
    $failures
);

$required_hoi_an_hue_comparison_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Hoi An/Hue hero marker' => [
            'vg-hoi-an-hue-hero:v1',
        ],
        'Hoi An/Hue concierge verdict' => [
            'vg-hoi-an-hue-concierge-verdict',
        ],
        'Hoi An/Hue at a glance' => [
            'vg-hoi-an-hue-at-a-glance:v1',
        ],
        'Hoi An/Hue photo grid' => [
            'vg-hoi-an-hue-photo-grid:v1',
        ],
        'Hoi An/Hue decision matrix' => [
            'vg-hoi-an-hue-decision-matrix:v1',
        ],
        'Hoi An/Hue base choice' => [
            'vg-hoi-an-hue-base-choice:v1',
        ],
        'Hoi An/Hue heritage fit' => [
            'vg-hoi-an-hue-heritage-fit:v1',
        ],
        'Hoi An/Hue route fit' => [
            'vg-hoi-an-hue-route-fit:v1',
        ],
        'Hoi An/Hue transfer pressure' => [
            'vg-hoi-an-hue-transfer-pressure:v1',
        ],
        'Hoi An/Hue season weather' => [
            'vg-hoi-an-hue-season-weather:v1',
        ],
        'Hoi An/Hue keep both' => [
            'vg-hoi-an-hue-keep-both:v1',
        ],
        'Hoi An/Hue skip logic' => [
            'vg-hoi-an-hue-skip-logic:v1',
        ],
        'Hoi An/Hue booking checks' => [
            'vg-hoi-an-hue-booking-checks:v1',
        ],
        'Hoi An/Hue FAQ' => [
            'vg-hoi-an-hue-faq:v1',
        ],
        'Hoi An official source anchor' => [
            'vietnam.travel/places-to-go/central-vietnam/hoi-an',
        ],
        'Hue official source anchor' => [
            'vietnam.travel/places-to-go/central-vietnam/hue',
        ],
        'Hoi An UNESCO source anchor' => [
            'whc.unesco.org/en/list/948',
        ],
        'Hue UNESCO source anchor' => [
            'whc.unesco.org/en/list/678',
        ],
        'Hoi An/Hue weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Hoi An/Hue transport source anchor' => [
            'vietnam.travel/plan-your-trip/transport-within-vietnam',
        ],
        'Hue monuments source anchor' => [
            'hueworldheritage.org.vn',
        ],
        'Hoi An Ancient Town image credit' => [
            'Steffen Schmitz / CC BY-SA 4.0',
        ],
        'Hoi An Japanese Bridge image credit' => [
            'rapidacid / CC BY 2.0',
        ],
        'Hue image credit' => [
            'CEphoto, Uwe Aranas / CC BY-SA 3.0',
        ],
    ]
);

$required_hoi_an_hue_comparison_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$hoi_an_hue_comparison_page = vg_verify_eeat_require_published_path(
    'compare/hoi-an-vs-hue',
    'Hoi An vs Hue comparison guide',
    $failures
);

if ($hoi_an_hue_comparison_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $hoi_an_hue_comparison_page,
        'Hoi An vs Hue comparison guide',
        'compare/hoi-an-vs-hue',
        'Hoi An vs Hue',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $hoi_an_hue_comparison_page,
        'Hoi An vs Hue comparison guide',
        $required_hoi_an_hue_comparison_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $hoi_an_hue_comparison_page,
        'Hoi An vs Hue comparison guide',
        $required_hoi_an_hue_comparison_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $hoi_an_hue_comparison_page,
        'Hoi An vs Hue comparison guide',
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
        'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
        'destinations/best-things-to-do-in-hue' => 'Best Things to Do in Hue guide',
        'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
        'compare/da-nang-vs-hoi-an' => 'Da Nang vs Hoi An guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
    ],
    '/compare/hoi-an-vs-hue/',
    'Hoi An vs Hue comparison guide',
    $failures
);

$required_da_nang_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Da Nang hero marker' => [
            'vg-da-nang-guide-hero:v1',
        ],
        'Da Nang concierge verdict' => [
            'vg-da-nang-guide-concierge-verdict',
        ],
        'Da Nang at a glance' => [
            'vg-da-nang-guide-at-a-glance:v1',
        ],
        'Da Nang photo grid' => [
            'vg-da-nang-guide-photo-grid:v1',
        ],
        'Da Nang priority map' => [
            'vg-da-nang-guide-priority-map:v1',
        ],
        'Da Nang base areas' => [
            'vg-da-nang-guide-base-areas:v1',
        ],
        'Da Nang route fit' => [
            'vg-da-nang-guide-route-fit:v1',
        ],
        'Da Nang season weather' => [
            'vg-da-nang-guide-season-weather:v1',
        ],
        'Da Nang day trips' => [
            'vg-da-nang-guide-day-trips:v1',
        ],
        'Da Nang skip logic' => [
            'vg-da-nang-guide-skip-logic:v1',
        ],
        'Da Nang live checks' => [
            'vg-da-nang-guide-live-checks:v1',
        ],
        'Da Nang FAQ' => [
            'vg-da-nang-guide-faq:v1',
        ],
        'Da Nang official source anchor' => [
            'vietnam.travel/places-to-go/central-vietnam/da-nang',
        ],
        'Da Nang Central Vietnam source anchor' => [
            'vietnam.travel/places-to-go/central-vietnam',
        ],
        'Da Nang weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Da Nang transport source anchor' => [
            'vietnam.travel/plan-your-trip/transport-within-vietnam',
        ],
        'Da Nang local tourism source anchor' => [
            'danangfantasticity.com/en',
        ],
        'Da Nang comparison related route anchor' => [
            '/compare/da-nang-vs-hoi-an/',
        ],
        'Da Nang Hoi An related route anchor' => [
            'best-things-to-do-in-hoi-an',
        ],
        'Da Nang Hue related route anchor' => [
            'best-things-to-do-in-hue',
        ],
        'Da Nang My Khe image credit' => [
            'Christophe95 / CC BY-SA 4.0',
        ],
        'Da Nang My Khe image record' => [
            'commons.wikimedia.org/wiki/File:My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg',
        ],
        'Da Nang Dragon Bridge image credit' => [
            'Supanut Arunoprayote / CC BY 4.0',
        ],
        'Da Nang Marble Mountains image record' => [
            'commons.wikimedia.org/wiki/File:Da_Nang_Marble_Mountains_2020_IMG_4008.jpg',
        ],
        'Da Nang Lady Buddha image record' => [
            'commons.wikimedia.org/wiki/File:Lady_Buddha_Da_Nang.jpg',
        ],
        'Da Nang Hai Van image credit' => [
            'Wolkenkratzer / CC BY-SA 4.0',
        ],
        'Da Nang Golden Bridge image record' => [
            'commons.wikimedia.org/wiki/File:Aerial_view_of_the_Golden_Bridge,_Ba_Na_Hills,_Da_Nang,_Vietnam.jpg',
        ],
        'Da Nang Golden Bridge image credit' => [
            'Vivu Vietnam / CC BY-SA 4.0',
        ],
    ]
);

$required_da_nang_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$da_nang_guide_page = vg_verify_eeat_require_published_path(
    'destinations/da-nang-travel-guide',
    'Da Nang Travel Guide',
    $failures
);

if ($da_nang_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $da_nang_guide_page,
        'Da Nang Travel Guide',
        $required_da_nang_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $da_nang_guide_page,
        'Da Nang Travel Guide',
        $required_da_nang_guide_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $da_nang_guide_page,
        'Da Nang Travel Guide',
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
        'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
        'destinations/best-things-to-do-in-hue' => 'Best Things to Do in Hue guide',
        'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
        'compare/da-nang-vs-hoi-an' => 'Da Nang vs Hoi An guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
    ],
    '/destinations/da-nang-travel-guide/',
    'Da Nang Travel Guide',
    $failures
);

$required_ha_long_bay_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Ha Long Bay hero marker' => [
            'vg-ha-long-bay-hero:v1',
        ],
        'Ha Long Bay concierge verdict' => [
            'vg-ha-long-bay-concierge-verdict',
        ],
        'Ha Long Bay at a glance' => [
            'vg-ha-long-bay-at-a-glance:v1',
        ],
        'Ha Long Bay photo grid' => [
            'vg-ha-long-bay-photo-grid:v1',
        ],
        'Ha Long Bay cruise length matrix' => [
            'vg-ha-long-bay-days-cruise:v1',
        ],
        'Ha Long Bay base/port table' => [
            'vg-ha-long-bay-where-to-base:v1',
        ],
        'Ha Long Bay priority map' => [
            'vg-ha-long-bay-priority-map:v1',
        ],
        'Ha Long Bay best time weather' => [
            'vg-ha-long-bay-best-time-weather:v1',
        ],
        'Ha Long Bay route fit' => [
            'vg-ha-long-bay-route-fit:v1',
        ],
        'Ha Long Bay booking checks' => [
            'vg-ha-long-bay-booking-checks:v1',
        ],
        'Ha Long Bay skip logic' => [
            'vg-ha-long-bay-skip-logic:v1',
        ],
        'Ha Long Bay live checks' => [
            'vg-ha-long-bay-live-checks:v1',
        ],
        'Ha Long Bay FAQ' => [
            'vg-ha-long-bay-faq:v1',
        ],
        'Ha Long Bay official source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam/ha-long',
        ],
        'Ha Long Bay Northern Vietnam source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam',
        ],
        'Ha Long Bay UNESCO source anchor' => [
            'whc.unesco.org/en/list/672',
        ],
        'Ha Long Bay Management source anchor' => [
            'halongbay.com.vn',
        ],
        'Ha Long Bay Cat Ba source anchor' => [
            'catba.com.vn',
        ],
        'Ha Long Bay weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Ha Long Bay transport source anchor' => [
            'transport-within-vietnam',
        ],
        'Ha Long Bay hero image credit' => [
            'Vyacheslav Argenberg / CC BY 4.0',
        ],
        'Ha Long Bay cruise image credit' => [
            'Shyamal L. / CC BY-SA 4.0',
        ],
        'Ha Long Bay Titov image credit' => [
            'Jakub Halun / CC BY 4.0',
        ],
        'Ha Long Bay Lan Ha image credit' => [
            'Saaremees / CC BY-SA 4.0',
        ],
        'Ha Long Bay guide comparison anchor' => [
            'ha-long-bay-vs-lan-ha-bay',
        ],
    ]
);

$required_ha_long_bay_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$ha_long_bay_guide_page = vg_verify_eeat_require_published_path(
    'destinations/ha-long-bay-travel-guide',
    'Ha Long Bay Travel Guide',
    $failures
);

if ($ha_long_bay_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $ha_long_bay_guide_page,
        'Ha Long Bay Travel Guide',
        $required_ha_long_bay_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $ha_long_bay_guide_page,
        'Ha Long Bay Travel Guide',
        $required_ha_long_bay_guide_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $ha_long_bay_guide_page,
        'Ha Long Bay Travel Guide',
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
        'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
        'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
    ],
    '/destinations/ha-long-bay-travel-guide/',
    'Ha Long Bay Travel Guide',
    $failures
);

$required_cat_ba_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Cat Ba hero marker' => [
            'vg-cat-ba-hero:v1',
        ],
        'Cat Ba concierge verdict' => [
            'vg-cat-ba-concierge-verdict',
        ],
        'Cat Ba at a glance' => [
            'vg-cat-ba-at-a-glance:v1',
        ],
        'Cat Ba photo grid' => [
            'vg-cat-ba-photo-grid:v1',
        ],
        'Cat Ba base decision' => [
            'vg-cat-ba-base-decision:v1',
        ],
        'Cat Ba Lan Ha gateway' => [
            'vg-cat-ba-lan-ha-gateway:v1',
        ],
        'Cat Ba access logistics' => [
            'vg-cat-ba-access-logistics:v1',
        ],
        'Cat Ba national park' => [
            'vg-cat-ba-national-park:v1',
        ],
        'Cat Ba route fit' => [
            'vg-cat-ba-route-fit:v1',
        ],
        'Cat Ba best time weather' => [
            'vg-cat-ba-best-time-weather:v1',
        ],
        'Cat Ba booking checks' => [
            'vg-cat-ba-booking-checks:v1',
        ],
        'Cat Ba skip logic' => [
            'vg-cat-ba-skip-logic:v1',
        ],
        'Cat Ba live checks' => [
            'vg-cat-ba-live-checks:v1',
        ],
        'Cat Ba FAQ' => [
            'vg-cat-ba-faq:v1',
        ],
        'Cat Ba official tourism source anchor' => [
            'catba.com.vn',
        ],
        'Cat Ba National Park source anchor' => [
            'catbanationalpark.vn',
        ],
        'Cat Ba Northern Vietnam source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam',
        ],
        'Cat Ba Ha Long source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam/ha-long',
        ],
        'Cat Ba UNESCO world heritage source anchor' => [
            'whc.unesco.org/en/list/672',
        ],
        'Cat Ba UNESCO biosphere source anchor' => [
            'unesco.org/en/mab/cat-ba',
        ],
        'Cat Ba weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Cat Ba transport source anchor' => [
            'transport-within-vietnam',
        ],
        'Cat Ba hero image credit' => [
            'Christophe95 / CC BY-SA 4.0',
        ],
        'Cat Ba arrival image credit' => [
            'Christophe95 / CC BY-SA 4.0',
        ],
        'Cat Ba Lan Ha image credit' => [
            'Saaremees / CC BY-SA 4.0',
        ],
        'Cat Ba park image credit' => [
            'Jakub Halun / CC BY 4.0',
        ],
        'Cat Ba beach image credit' => [
            'Vuong Tri Binh / CC BY-SA 4.0',
        ],
        'Cat Ba guide comparison anchor' => [
            'ha-long-bay-vs-lan-ha-bay',
        ],
        'Cat Ba guide Ha Long anchor' => [
            'ha-long-bay-travel-guide',
        ],
        'Cat Ba guide Ninh Binh anchor' => [
            'ninh-binh-travel-guide',
        ],
    ]
);

$required_cat_ba_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$cat_ba_guide_page = vg_verify_eeat_require_published_path(
    'destinations/cat-ba-travel-guide',
    'Cat Ba Travel Guide',
    $failures
);

if ($cat_ba_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $cat_ba_guide_page,
        'Cat Ba Travel Guide',
        $required_cat_ba_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $cat_ba_guide_page,
        'Cat Ba Travel Guide',
        $required_cat_ba_guide_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $cat_ba_guide_page,
        'Cat Ba Travel Guide',
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
        'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
        'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
        'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
    ],
    '/destinations/cat-ba-travel-guide/',
    'Cat Ba Travel Guide',
    $failures
);

$required_bai_tu_long_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Bai Tu Long hero marker' => [
            'vg-bai-tu-long-hero:v1',
        ],
        'Bai Tu Long concierge verdict' => [
            'vg-bai-tu-long-concierge-verdict',
        ],
        'Bai Tu Long at a glance' => [
            'vg-bai-tu-long-at-a-glance:v1',
        ],
        'Bai Tu Long photo grid' => [
            'vg-bai-tu-long-photo-grid:v1',
        ],
        'Bai Tu Long bay choice' => [
            'vg-bai-tu-long-bay-choice:v1',
        ],
        'Bai Tu Long route logistics' => [
            'vg-bai-tu-long-route-logistics:v1',
        ],
        'Bai Tu Long cruise length' => [
            'vg-bai-tu-long-cruise-length:v1',
        ],
        'Bai Tu Long route fit' => [
            'vg-bai-tu-long-route-fit:v1',
        ],
        'Bai Tu Long best time weather' => [
            'vg-bai-tu-long-best-time-weather:v1',
        ],
        'Bai Tu Long booking checks' => [
            'vg-bai-tu-long-booking-checks:v1',
        ],
        'Bai Tu Long skip logic' => [
            'vg-bai-tu-long-skip-logic:v1',
        ],
        'Bai Tu Long live checks' => [
            'vg-bai-tu-long-live-checks:v1',
        ],
        'Bai Tu Long FAQ' => [
            'vg-bai-tu-long-faq:v1',
        ],
        'Bai Tu Long Ha Long Management route anchor' => [
            'halongbay.com.vn/tours/4-hanh-trinh-vhl-4',
        ],
        'Bai Tu Long Ha Long Management connection anchor' => [
            'halongbay.com.vn/en/p/385',
        ],
        'Bai Tu Long National Park source anchor' => [
            'vuonquocgiabaitulong.vn',
        ],
        'Bai Tu Long Ha Long official source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam/ha-long',
        ],
        'Bai Tu Long Northern Vietnam source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam',
        ],
        'Bai Tu Long weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Bai Tu Long transport source anchor' => [
            'transport-within-vietnam',
        ],
        'Bai Tu Long Quang Ninh source anchor' => [
            'quangninh.gov.vn',
        ],
        'Bai Tu Long UNESCO precision anchor' => [
            'whc.unesco.org/en/list/672',
        ],
        'Bai Tu Long hero image credit' => [
            'Benjamin Smith / CC BY-SA 4.0',
        ],
        'Bai Tu Long guide Ha Long anchor' => [
            'ha-long-bay-travel-guide',
        ],
        'Bai Tu Long guide comparison anchor' => [
            'ha-long-bay-vs-lan-ha-bay',
        ],
        'Bai Tu Long guide Cat Ba anchor' => [
            'cat-ba-travel-guide',
        ],
    ]
);

$required_bai_tu_long_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$bai_tu_long_guide_page = vg_verify_eeat_require_published_path(
    'destinations/bai-tu-long-bay-guide',
    'Bai Tu Long Bay Guide',
    $failures
);

if ($bai_tu_long_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $bai_tu_long_guide_page,
        'Bai Tu Long Bay Guide',
        $required_bai_tu_long_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $bai_tu_long_guide_page,
        'Bai Tu Long Bay Guide',
        $required_bai_tu_long_guide_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $bai_tu_long_guide_page,
        'Bai Tu Long Bay Guide',
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/unesco-heritage-sites-vietnam' => 'UNESCO Heritage Sites in Vietnam guide',
        'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
        'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
        'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
        'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
    ],
    '/destinations/bai-tu-long-bay-guide/',
    'Bai Tu Long Bay Guide',
    $failures
);

$required_best_beaches_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Best Beaches hero marker' => [
            'vg-best-beaches-hero:v1',
        ],
        'Best Beaches concierge verdict' => [
            'vg-best-beaches-concierge-verdict',
        ],
        'Best Beaches at a glance' => [
            'vg-best-beaches-at-a-glance:v1',
        ],
        'Best Beaches source diversity' => [
            'vg-best-beaches-source-diversity:v1',
        ],
        'Best Beaches source trail snapshot' => [
            'vg-best-beaches-source-trail-snapshot:v1',
        ],
        'Best Beaches beach chooser' => [
            'vg-best-beaches-chooser:v1',
        ],
        'Best Beaches month planner' => [
            'vg-best-beaches-month-planner:v1',
        ],
        'Best Beaches traveler fit' => [
            'vg-best-beaches-traveler-fit:v1',
        ],
        'Best Beaches photo grid' => [
            'vg-best-beaches-photo-grid:v1',
        ],
        'Best Beaches cluster matrix' => [
            'vg-best-beaches-cluster-matrix:v1',
        ],
        'Best Beaches route fit' => [
            'vg-best-beaches-route-fit:v1',
        ],
        'Best Beaches season fit' => [
            'vg-best-beaches-season-fit:v1',
        ],
        'Best Beaches booking checks' => [
            'vg-best-beaches-booking-checks:v1',
        ],
        'Best Beaches skip logic' => [
            'vg-best-beaches-skip-logic:v1',
        ],
        'Best Beaches live checks' => [
            'vg-best-beaches-live-checks:v1',
        ],
        'Best Beaches FAQ' => [
            'vg-best-beaches-faq:v1',
        ],
        'Best Beaches Phu Quoc source anchor' => [
            'vietnam.travel/places-to-go/southern-vietnam/phu-quoc',
        ],
        'Best Beaches Con Dao source anchor' => [
            'vietnam.travel/places-to-go/southern-vietnam/con-dao',
        ],
        'Best Beaches Da Nang source anchor' => [
            'vietnam.travel/places-to-go/central-vietnam/da-nang',
        ],
        'Best Beaches Hoi An source anchor' => [
            'vietnam.travel/places-to-go/central-vietnam/hoi-an',
        ],
        'Best Beaches Nha Trang source anchor' => [
            'vietnam.travel/places-to-go/central-vietnam/nha-trang',
        ],
        'Best Beaches Phu Quy source anchor' => [
            'vietnam.travel/things-to-do/phu-quy-vietnam-island-destination',
        ],
        'Best Beaches weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Best Beaches transport source anchor' => [
            'transport-within-vietnam',
        ],
        'Best Beaches Mui Ne source anchor' => [
            'vietnam.travel/node/708',
        ],
        'Best Beaches Quy Nhon source anchor' => [
            'vietnam.travel/node/1453',
        ],
        'Best Beaches Hoi An related route anchor' => [
            'best-things-to-do-in-hoi-an',
        ],
        'Best Beaches Hue related route anchor' => [
            'best-things-to-do-in-hue',
        ],
        'Best Beaches Cat Ba related route anchor' => [
            'cat-ba-travel-guide',
        ],
        'Best Beaches Ha Long related route anchor' => [
            'ha-long-bay-travel-guide',
        ],
        'Best Beaches Ha Long/Lan Ha related route anchor' => [
            'ha-long-bay-vs-lan-ha-bay',
        ],
        'Best Beaches Bai Tu Long related route anchor' => [
            'bai-tu-long-bay-guide',
        ],
        'Best Beaches Phu Quoc image credit' => [
            'Vivu Vietnam, CC BY-SA 4.0',
        ],
        'Best Beaches My Khe image credit' => [
            'Christophe95, CC BY-SA 4.0',
        ],
        'Best Beaches An Bang image credit' => [
            'Alexkom000, CC BY 4.0',
        ],
        'Best Beaches Nha Trang image credit' => [
            'Christophe95, CC BY-SA 4.0',
        ],
        'Best Beaches Mui Ne image credit' => [
            'Vyacheslav Argenberg, CC BY 4.0',
        ],
        'Best Beaches Con Dao image credit' => [
            'Tycho, CC BY-SA 3.0',
        ],
        'Best Beaches Quy Nhon image credit' => [
            'Le Ho Bac, CC BY-SA 4.0',
        ],
        'Best Beaches Cat Ba image credit' => [
            'Pather alexiy, CC BY-SA 3.0',
        ],
    ]
);

$required_best_beaches_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$best_beaches_guide_page = vg_verify_eeat_require_published_path(
    'destinations/best-beaches-in-vietnam',
    'Best Beaches in Vietnam guide',
    $failures
);

if ($best_beaches_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $best_beaches_guide_page,
        'Best Beaches in Vietnam guide',
        $required_best_beaches_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $best_beaches_guide_page,
        'Best Beaches in Vietnam guide',
        $required_best_beaches_guide_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $best_beaches_guide_page,
        'Best Beaches in Vietnam guide',
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/best-islands-in-vietnam' => 'Best Islands in Vietnam guide',
        'destinations/phu-quoc-travel-guide' => 'Phu Quoc Travel Guide',
        'compare/phu-quoc-vs-nha-trang' => 'Phu Quoc vs Nha Trang guide',
        'destinations/con-dao-travel-guide' => 'Con Dao Travel Guide',
        'destinations/nha-trang-travel-guide' => 'Nha Trang Travel Guide',
        'destinations/quy-nhon-travel-guide' => 'Quy Nhon Travel Guide',
        'compare/mui-ne-vs-nha-trang' => 'Mui Ne vs Nha Trang guide',
        'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
        'compare/da-nang-vs-hoi-an' => 'Da Nang vs Hoi An guide',
        'destinations/cham-islands-travel-guide' => 'Cham Islands Travel Guide',
        'destinations/ly-son-travel-guide' => 'Ly Son Travel Guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
        'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
        'destinations/best-things-to-do-in-hue' => 'Best Things to Do in Hue guide',
        'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
        'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
        'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
        'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
    ],
    '/destinations/best-beaches-in-vietnam/',
    'Best Beaches in Vietnam guide',
    $failures
);

vg_verify_eeat_require_related_route_meta(
    [
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
        'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
        'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
        'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
        'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
        'compare/hoi-an-vs-hue' => 'Hoi An vs Hue guide',
        'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
    ],
    '/destinations/best-islands-in-vietnam/',
    'Best Islands in Vietnam guide',
    $failures
);

$required_best_islands_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Best Islands hero marker' => [
            'vg-best-islands-hero:v1',
        ],
        'Best Islands concierge verdict' => [
            'vg-best-islands-concierge-verdict',
        ],
        'Best Islands at a glance' => [
            'vg-best-islands-at-a-glance:v1',
        ],
        'Best Islands photo grid' => [
            'vg-best-islands-photo-grid:v1',
        ],
        'Best Islands source diversity' => [
            'vg-best-islands-source-diversity:v1',
        ],
        'Best Islands decision matrix' => [
            'vg-best-islands-decision-matrix:v1',
        ],
        'Best Islands route fit' => [
            'vg-best-islands-route-fit:v1',
        ],
        'Best Islands season weather' => [
            'vg-best-islands-season-weather:v1',
        ],
        'Best Islands logistics' => [
            'vg-best-islands-logistics:v1',
        ],
        'Best Islands island profiles' => [
            'vg-best-islands-island-profiles:v1',
        ],
        'Best Islands skip logic' => [
            'vg-best-islands-skip-logic:v1',
        ],
        'Best Islands booking checks' => [
            'vg-best-islands-booking-checks:v1',
        ],
        'Best Islands live checks' => [
            'vg-best-islands-live-checks:v1',
        ],
        'Best Islands FAQ' => [
            'vg-best-islands-faq:v1',
        ],
        'Best Islands Phu Quoc source anchor' => [
            'vietnam.travel/places-to-go/southern-vietnam/phu-quoc',
        ],
        'Best Islands Con Dao source anchor' => [
            'vietnam.travel/places-to-go/southern-vietnam/con-dao',
        ],
        'Best Islands Phu Quy source anchor' => [
            'vietnam.travel/things-to-do/phu-quy-vietnam-island-destination',
        ],
        'Best Islands weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Best Islands transport source anchor' => [
            'transport-within-vietnam',
        ],
        'Best Islands UNESCO Kien Giang anchor' => [
            'unesco.org/en/mab/kien-giang',
        ],
        'Best Islands UNESCO Cat Ba anchor' => [
            'unesco.org/en/mab/cat-ba',
        ],
        'Best Islands Con Dao National Park anchor' => [
            'condaopark.com.vn',
        ],
        'Best Islands Cat Ba National Park anchor' => [
            'catbanationalpark.vn',
        ],
        'Best Islands Hoi An heritage anchor' => [
            'hoianheritage.net/en/news/news/Cu-Lao-Cham-Hoi-An-World-Biosphere-Reserve',
        ],
        'Best Islands Quang Ngai anchor' => [
            'quangngai.gov.vn/web/portal-qni',
        ],
        'Best Islands Sa Ky anchor' => [
            'cangsaky.com.vn/lich-tau',
        ],
        'Best Islands Phu Quy Express anchor' => [
            'phuquyexpress.com',
        ],
        'Best Islands Superdong anchor' => [
            'superdong.com.vn',
        ],
        'Best Islands Phu Quoc Express anchor' => [
            'phuquocexpress.com',
        ],
        'Best Islands Phu Quoc image credit' => [
            'Vivu Vietnam / CC BY-SA 4.0',
        ],
        'Best Islands Con Dao image credit' => [
            'Daeva Trac / CC BY-SA 4.0',
        ],
        'Best Islands Cat Ba image credit' => [
            'Christophe95 / CC BY-SA 4.0',
        ],
        'Best Islands Cham image credit' => [
            'Kok Leng Yeo / CC BY 2.0',
        ],
        'Best Islands Ly Son image credit' => [
            'BertholdD / CC BY-SA 3.0',
        ],
        'Best Islands Co To image credit' => [
            'Tuan Nguyen / CC BY-SA 3.0',
        ],
        'Best Islands Phu Quy image credit' => [
            'Thai Nhi / Public domain',
        ],
        'Best Islands guide Best Beaches anchor' => [
            'best-beaches-in-vietnam',
        ],
        'Best Islands guide Cat Ba anchor' => [
            'cat-ba-travel-guide',
        ],
        'Best Islands guide Ha Long anchor' => [
            'ha-long-bay-travel-guide',
        ],
        'Best Islands guide Ha Long/Lan Ha anchor' => [
            'ha-long-bay-vs-lan-ha-bay',
        ],
        'Best Islands guide Hoi An anchor' => [
            'best-things-to-do-in-hoi-an',
        ],
        'Best Islands guide Hoi An/Hue anchor' => [
            'hoi-an-vs-hue',
        ],
        'Best Islands guide Da Nang anchor' => [
            'da-nang-travel-guide',
        ],
    ]
);

$required_best_islands_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$best_islands_guide_page = vg_verify_eeat_require_published_path(
    'destinations/best-islands-in-vietnam',
    'Best Islands in Vietnam guide',
    $failures
);

if ($best_islands_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $best_islands_guide_page,
        'Best Islands in Vietnam guide',
        $required_best_islands_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $best_islands_guide_page,
        'Best Islands in Vietnam guide',
        $required_best_islands_guide_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $best_islands_guide_page,
        'Best Islands in Vietnam guide',
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
        'destinations/best-islands-in-vietnam' => 'Best Islands in Vietnam guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
        'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
        'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
    ],
    '/destinations/phu-quoc-travel-guide/',
    'Phu Quoc Travel Guide',
    $failures
);

vg_verify_eeat_require_related_route_meta(
    [
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
        'destinations/best-islands-in-vietnam' => 'Best Islands in Vietnam guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
        'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
        'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
    ],
    '/destinations/con-dao-travel-guide/',
    'Con Dao Travel Guide',
    $failures
);

vg_verify_eeat_require_related_route_meta(
    [
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
        'destinations/best-islands-in-vietnam' => 'Best Islands in Vietnam guide',
        'destinations/phu-quoc-travel-guide' => 'Phu Quoc Travel Guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
        'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
        'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
        'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
        'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
    ],
    '/compare/phu-quoc-vs-nha-trang/',
    'Phu Quoc vs Nha Trang guide',
    $failures
);

$required_phu_quoc_nha_trang_comparison_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Phu Quoc/Nha Trang hero marker' => [
            'vg-phu-quoc-nha-trang-hero:v1',
        ],
        'Phu Quoc/Nha Trang concierge verdict' => [
            'vg-phu-quoc-nha-trang-concierge-verdict',
        ],
        'Phu Quoc/Nha Trang at a glance' => [
            'vg-phu-quoc-nha-trang-at-a-glance:v1',
        ],
        'Phu Quoc/Nha Trang photo grid' => [
            'vg-phu-quoc-nha-trang-photo-grid:v1',
        ],
        'Phu Quoc/Nha Trang source diversity' => [
            'vg-phu-quoc-nha-trang-source-diversity:v1',
        ],
        'Phu Quoc/Nha Trang decision matrix' => [
            'vg-phu-quoc-nha-trang-decision-matrix:v1',
        ],
        'Phu Quoc/Nha Trang route fit' => [
            'vg-phu-quoc-nha-trang-route-fit:v1',
        ],
        'Phu Quoc/Nha Trang season weather' => [
            'vg-phu-quoc-nha-trang-season-weather:v1',
        ],
        'Phu Quoc/Nha Trang logistics' => [
            'vg-phu-quoc-nha-trang-logistics:v1',
        ],
        'Phu Quoc/Nha Trang cost booking' => [
            'vg-phu-quoc-nha-trang-cost-booking:v1',
        ],
        'Phu Quoc/Nha Trang skip logic' => [
            'vg-phu-quoc-nha-trang-skip-logic:v1',
        ],
        'Phu Quoc/Nha Trang live checks' => [
            'vg-phu-quoc-nha-trang-live-checks:v1',
        ],
        'Phu Quoc/Nha Trang FAQ' => [
            'vg-phu-quoc-nha-trang-faq:v1',
        ],
        'Phu Quoc official source anchor' => [
            'vietnam.travel/places-to-go/southern-vietnam/phu-quoc',
        ],
        'Nha Trang official source anchor' => [
            'vietnam.travel/places-to-go/central-vietnam/nha-trang',
        ],
        'Cam Ranh airport anchor' => [
            'camranh.aero/en',
        ],
        'weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'transport source anchor' => [
            'transport-within-vietnam',
        ],
        'evisa source anchor' => [
            'evisa.gov.vn',
        ],
        'NCHMF source anchor' => [
            'kttv/en-US/1/index.html',
        ],
        'Phu Quoc image credit' => [
            'Vivu Vietnam / CC BY-SA 4.0',
        ],
        'Nha Trang Beach image credit' => [
            'Christophe95 / CC BY-SA 4.0',
        ],
        'Po Nagar image credit' => [
            'Tervlugt / CC BY 3.0',
        ],
        'Nha Trang coastline image credit' => [
            'Tony24986 / CC BY-SA 4.0',
        ],
        'Cam Ranh image credit' => [
            'Ed Crystal / CC BY-SA 4.0',
        ],
    ]
);

$required_phu_quoc_nha_trang_comparison_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$phu_quoc_nha_trang_comparison_page = vg_verify_eeat_require_published_path(
    'compare/phu-quoc-vs-nha-trang',
    'Phu Quoc vs Nha Trang guide',
    $failures
);

if ($phu_quoc_nha_trang_comparison_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $phu_quoc_nha_trang_comparison_page,
        'Phu Quoc vs Nha Trang guide',
        $required_phu_quoc_nha_trang_comparison_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $phu_quoc_nha_trang_comparison_page,
        'Phu Quoc vs Nha Trang guide',
        $required_phu_quoc_nha_trang_comparison_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $phu_quoc_nha_trang_comparison_page,
        'Phu Quoc vs Nha Trang guide',
        $failures
    );
}

$required_mui_ne_nha_trang_comparison_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Mui Ne/Nha Trang hero marker' => [
            'vg-mui-ne-nha-trang-hero:v1',
        ],
        'Mui Ne/Nha Trang concierge verdict' => [
            'vg-mui-ne-nha-trang-concierge-verdict',
        ],
        'Mui Ne/Nha Trang at a glance' => [
            'vg-mui-ne-nha-trang-at-a-glance:v1',
        ],
        'Mui Ne/Nha Trang photo grid' => [
            'vg-mui-ne-nha-trang-photo-grid:v1',
        ],
        'Mui Ne/Nha Trang source diversity' => [
            'vg-mui-ne-nha-trang-source-diversity:v1',
        ],
        'Mui Ne/Nha Trang decision matrix' => [
            'vg-mui-ne-nha-trang-decision-matrix:v1',
        ],
        'Mui Ne/Nha Trang route fit' => [
            'vg-mui-ne-nha-trang-route-fit:v1',
        ],
        'Mui Ne/Nha Trang season weather' => [
            'vg-mui-ne-nha-trang-season-weather:v1',
        ],
        'Mui Ne/Nha Trang logistics' => [
            'vg-mui-ne-nha-trang-logistics:v1',
        ],
        'Mui Ne/Nha Trang cost booking' => [
            'vg-mui-ne-nha-trang-cost-booking:v1',
        ],
        'Mui Ne/Nha Trang skip logic' => [
            'vg-mui-ne-nha-trang-skip-logic:v1',
        ],
        'Mui Ne/Nha Trang live checks' => [
            'vg-mui-ne-nha-trang-live-checks:v1',
        ],
        'Mui Ne/Nha Trang FAQ' => [
            'vg-mui-ne-nha-trang-faq:v1',
        ],
        'Binh Thuan official tourism anchor' => [
            'vietnam.travel/places-to-go/southern-vietnam/binh-thuan',
        ],
        'Mui Ne official source anchor' => [
            'vietnam.travel/node/708',
        ],
        'Nha Trang official source anchor for Mui Ne comparison' => [
            'vietnam.travel/places-to-go/central-vietnam/nha-trang',
        ],
        'Khanh Hoa tourism anchor for Mui Ne comparison' => [
            'dulich.khanhhoa.gov.vn/en',
        ],
        'Cam Ranh airport anchor for Mui Ne comparison' => [
            'camranh.aero/en',
        ],
        'weather source anchor for Mui Ne comparison' => [
            'weather-and-climate-vietnam',
        ],
        'transport source anchor for Mui Ne comparison' => [
            'transport-within-vietnam',
        ],
        'Vietnam Railways anchor for Mui Ne comparison' => [
            'dsvn.vn',
        ],
        'evisa source anchor for Mui Ne comparison' => [
            'evisa.gov.vn',
        ],
        'NCHMF source anchor for Mui Ne comparison' => [
            'kttv/en-US/1/index.html',
        ],
        'Mui Ne kitesurfing image credit' => [
            'Vyacheslav Argenberg / CC BY 4.0',
        ],
        'Mui Ne dunes image credit' => [
            'Vyacheslav Argenberg / CC BY 4.0',
        ],
        'Mui Ne Fairy Stream image credit' => [
            'Ddubbert / CC BY-SA 3.0',
        ],
        'Mui Ne fishing village image credit' => [
            'Michel Coutty / Public domain',
        ],
        'Nha Trang Beach image credit for Mui Ne comparison' => [
            'Christophe95 / CC BY-SA 4.0',
        ],
        'Nha Trang coastline image credit for Mui Ne comparison' => [
            'Tony24986 / CC BY-SA 4.0',
        ],
    ]
);

$required_mui_ne_nha_trang_comparison_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$mui_ne_nha_trang_comparison_page = vg_verify_eeat_require_published_path(
    'compare/mui-ne-vs-nha-trang',
    'Mui Ne vs Nha Trang guide',
    $failures
);

if ($mui_ne_nha_trang_comparison_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $mui_ne_nha_trang_comparison_page,
        'Mui Ne vs Nha Trang guide',
        'compare/mui-ne-vs-nha-trang',
        'Mui Ne vs Nha Trang',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $mui_ne_nha_trang_comparison_page,
        'Mui Ne vs Nha Trang guide',
        $required_mui_ne_nha_trang_comparison_content_groups,
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $mui_ne_nha_trang_comparison_page,
        'Mui Ne vs Nha Trang guide',
        'official source trail anchors',
        [
            'vietnam.travel/places-to-go/southern-vietnam/binh-thuan',
            'vietnam.travel/node/708',
            'vietnam.travel/places-to-go/central-vietnam/nha-trang',
            'dulich.khanhhoa.gov.vn/en',
            'camranh.aero/en',
            'weather-and-climate-vietnam',
            'transport-within-vietnam',
            'dsvn.vn',
            'evisa.gov.vn',
            'kttv/en-US/1/index.html',
        ],
        $failures
    );

    $mui_ne_nha_trang_visible_image_contracts = [
        'visible Mui Ne kitesurfing image credit' => [
            'Vietnam%2C_Mui_Ne_beach%2C_Kitesurfing_on_the_beach.jpg',
            'commons.wikimedia.org/wiki/File:Vietnam,_Mui_Ne_beach,_Kitesurfing_on_the_beach.jpg',
            'Vyacheslav Argenberg / CC BY 4.0',
        ],
        'visible Mui Ne dunes image credit' => [
            'Vietnam%2C_Mui_Ne_sand_dunes.jpg',
            'commons.wikimedia.org/wiki/File:Vietnam,_Mui_Ne_sand_dunes.jpg',
            'Vyacheslav Argenberg / CC BY 4.0',
        ],
        'visible Mui Ne Fairy Stream image credit' => [
            'Mui_Ne_Fairy_Stream.jpg',
            'commons.wikimedia.org/wiki/File:Mui_Ne_Fairy_Stream.jpg',
            'Ddubbert / CC BY-SA 3.0',
        ],
        'visible Mui Ne fishing village image credit' => [
            'Mui_Ne_fishing_village.jpg',
            'commons.wikimedia.org/wiki/File:Mui_Ne_fishing_village.jpg',
            'Michel Coutty / Public domain',
        ],
        'visible Nha Trang Beach image credit' => [
            'Nha_Trang_Beach_3.jpg',
            'commons.wikimedia.org/wiki/File:Nha_Trang_Beach_3.jpg',
            'Christophe95 / CC BY-SA 4.0',
        ],
        'visible Nha Trang coastline image credit' => [
            'Panorama_of_V%E1%BB%8Bnh_Nha_Trang_coastline.jpg',
            'commons.wikimedia.org/wiki/File:Panorama_of_V%E1%BB%8Bnh_Nha_Trang_coastline.jpg',
            'Tony24986 / CC BY-SA 4.0',
        ],
    ];

    foreach ($mui_ne_nha_trang_visible_image_contracts as $group_label => $needles) {
        vg_verify_eeat_require_rendered_content_all(
            $mui_ne_nha_trang_comparison_page,
            'Mui Ne vs Nha Trang guide',
            $group_label,
            $needles,
            $failures
        );
    }

    vg_verify_eeat_require_public_evidence_all(
        $mui_ne_nha_trang_comparison_page,
        'Mui Ne vs Nha Trang guide',
        'hero image credit metadata',
        [
            'Hero and Mui Ne kitesurfing image',
            'Vietnam, Mui Ne beach, Kitesurfing on the beach by Vyacheslav Argenberg, CC BY 4.0',
            'Mui Ne Fairy Stream by Ddubbert, CC BY-SA 3.0',
            'Mui Ne fishing village by Michel Coutty, Public domain',
            'Nha Trang Beach 3 by Christophe95, CC BY-SA 4.0',
            'Panorama of Vinh Nha Trang coastline by Tony24986, CC BY-SA 4.0',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $mui_ne_nha_trang_comparison_page,
        'Mui Ne vs Nha Trang guide',
        $required_mui_ne_nha_trang_comparison_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $mui_ne_nha_trang_comparison_page,
        'Mui Ne vs Nha Trang guide',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $mui_ne_nha_trang_comparison_page,
        'Mui Ne vs Nha Trang guide',
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
            'destinations/nha-trang-travel-guide' => 'Nha Trang Travel Guide',
            'compare/phu-quoc-vs-nha-trang' => 'Phu Quoc vs Nha Trang guide',
            'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM/eSIM in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ],
        '/compare/mui-ne-vs-nha-trang/',
        'Mui Ne vs Nha Trang guide',
        $failures
    );
}

$required_phu_quoc_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Phu Quoc hero marker' => [
            'vg-phu-quoc-hero:v1',
        ],
        'Phu Quoc concierge verdict' => [
            'vg-phu-quoc-concierge-verdict',
        ],
        'Phu Quoc at a glance' => [
            'vg-phu-quoc-at-a-glance:v1',
        ],
        'Phu Quoc photo grid' => [
            'vg-phu-quoc-photo-grid:v1',
        ],
        'Phu Quoc source diversity' => [
            'vg-phu-quoc-source-diversity:v1',
        ],
        'Phu Quoc route fit' => [
            'vg-phu-quoc-route-fit:v1',
        ],
        'Phu Quoc where to stay' => [
            'vg-phu-quoc-where-to-stay:v1',
        ],
        'Phu Quoc beach areas' => [
            'vg-phu-quoc-beach-areas:v1',
        ],
        'Phu Quoc priority map' => [
            'vg-phu-quoc-priority-map:v1',
        ],
        'Phu Quoc season weather' => [
            'vg-phu-quoc-season-weather:v1',
        ],
        'Phu Quoc transport logistics' => [
            'vg-phu-quoc-transport-logistics:v1',
        ],
        'Phu Quoc cost booking' => [
            'vg-phu-quoc-cost-booking:v1',
        ],
        'Phu Quoc skip logic' => [
            'vg-phu-quoc-skip-logic:v1',
        ],
        'Phu Quoc live checks' => [
            'vg-phu-quoc-live-checks:v1',
        ],
        'Phu Quoc FAQ' => [
            'vg-phu-quoc-faq:v1',
        ],
        'Phu Quoc official tourism anchor' => [
            'vietnam.travel/places-to-go/southern-vietnam/phu-quoc',
        ],
        'Phu Quoc weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Phu Quoc transport source anchor' => [
            'transport-within-vietnam',
        ],
        'Phu Quoc getting to Vietnam anchor' => [
            'getting-vietnam',
        ],
        'Phu Quoc visa source anchor' => [
            'visa-requirements',
        ],
        'Phu Quoc evisa source anchor' => [
            'evisa.gov.vn',
        ],
        'Phu Quoc UNESCO Kien Giang anchor' => [
            'unesco.org/en/mab/kien-giang',
        ],
        'Phu Quoc NBCA National Park anchor' => [
            'en.nbca.gov.vn/vuon-quoc-gia-phu-quoc-kien-giang',
        ],
        'Phu Quoc local government anchor' => [
            'phuquoc.angiang.gov.vn',
        ],
        'Phu Quoc An Giang source anchor' => [
            'angiang.gov.vn/en/phu-quoc-tourism-expects-surge-international-visitors-late-2025',
        ],
        'Phu Quoc NCHMF weather anchor' => [
            'nchmf.gov.vn',
        ],
        'Phu Quoc airport operator anchor' => [
            'sunairport.com/phuquoc',
        ],
        'Phu Quoc Express Ha Tien anchor' => [
            'phuquocexpress.com/lich-tau-tuyen-ha-tien-phu-quoc-2024',
        ],
        'Phu Quoc Express Rach Gia anchor' => [
            'phuquocexpress.com/lich-tau-tuyen-rach-gia-phu-quoc',
        ],
        'Phu Quoc Superdong Ha Tien anchor' => [
            'superdong.com.vn/dich-vu/ha-tien-phu-quoc',
        ],
        'Phu Quoc Superdong Rach Gia anchor' => [
            'superdong.com.vn/dich-vu/rach-gia-phu-quoc',
        ],
        'Phu Quoc Thanh Thoi Ferry anchor' => [
            'thanhthoi.vn',
        ],
        'Phu Quoc Sun World anchor' => [
            'ticket.sunworld.vn/khu-vui-choi/hon-thom-nature-park',
        ],
        'Phu Quoc VinWonders anchor' => [
            'vinwonders.com/en/vinwonders-phu-quoc',
        ],
        'Phu Quoc Kem Beach image credit' => [
            'Vivu Vietnam / CC BY-SA 4.0',
        ],
        'Phu Quoc Long Beach image credit' => [
            'Alexey Komarov / CC BY-SA 4.0',
        ],
        'Phu Quoc Bai Sao image credit' => [
            'Trantuonglam / CC BY-SA 4.0',
        ],
        'Phu Quoc Star Beach image credit' => [
            'Vnecofriendly / CC BY-SA 4.0',
        ],
        'Phu Quoc United Center image credit' => [
            'Reb.vn / CC BY-SA 4.0',
        ],
        'Phu Quoc guide Best Islands anchor' => [
            'best-islands-in-vietnam',
        ],
        'Phu Quoc guide Best Beaches anchor' => [
            'best-beaches-in-vietnam',
        ],
        'Phu Quoc guide evisa anchor' => [
            'vietnam-evisa',
        ],
        'Phu Quoc guide insurance anchor' => [
            'health-travel-insurance-vietnam',
        ],
    ]
);

$required_phu_quoc_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$phu_quoc_guide_page = vg_verify_eeat_require_published_path(
    'destinations/phu-quoc-travel-guide',
    'Phu Quoc Travel Guide',
    $failures
);

if ($phu_quoc_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $phu_quoc_guide_page,
        'Phu Quoc Travel Guide',
        $required_phu_quoc_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $phu_quoc_guide_page,
        'Phu Quoc Travel Guide',
        $required_phu_quoc_guide_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $phu_quoc_guide_page,
        'Phu Quoc Travel Guide',
        $failures
    );
}

$required_nha_trang_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Nha Trang hero marker' => [
            'vg-nha-trang-hero:v1',
        ],
        'Nha Trang concierge verdict' => [
            'vg-nha-trang-concierge-verdict',
        ],
        'Nha Trang at a glance' => [
            'vg-nha-trang-at-a-glance:v1',
        ],
        'Nha Trang photo grid' => [
            'vg-nha-trang-photo-grid:v1',
        ],
        'Nha Trang source diversity' => [
            'vg-nha-trang-source-diversity:v1',
        ],
        'Nha Trang route fit' => [
            'vg-nha-trang-route-fit:v1',
        ],
        'Nha Trang where to stay' => [
            'vg-nha-trang-where-to-stay:v1',
        ],
        'Nha Trang beach areas' => [
            'vg-nha-trang-beach-areas:v1',
        ],
        'Nha Trang priority map' => [
            'vg-nha-trang-priority-map:v1',
        ],
        'Nha Trang season weather' => [
            'vg-nha-trang-season-weather:v1',
        ],
        'Nha Trang transport logistics' => [
            'vg-nha-trang-transport-logistics:v1',
        ],
        'Nha Trang cost booking' => [
            'vg-nha-trang-cost-booking:v1',
        ],
        'Nha Trang skip logic' => [
            'vg-nha-trang-skip-logic:v1',
        ],
        'Nha Trang live checks' => [
            'vg-nha-trang-live-checks:v1',
        ],
        'Nha Trang FAQ' => [
            'vg-nha-trang-faq:v1',
        ],
        'Nha Trang official tourism anchor' => [
            'vietnam.travel/places-to-go/central-vietnam/nha-trang',
        ],
        'Nha Trang Khanh Hoa tourism anchor' => [
            'dulich.khanhhoa.gov.vn/en',
        ],
        'Nha Trang city page anchor' => [
            'nha-trang-city.html',
        ],
        'Nha Trang Cam Ranh anchor' => [
            'camranh.aero/en',
        ],
        'Nha Trang weather anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Nha Trang transport anchor' => [
            'transport-within-vietnam',
        ],
        'Nha Trang eVisa anchor' => [
            'evisa.gov.vn',
        ],
        'Nha Trang NCHMF anchor' => [
            'kttv/en-US/1/index.html',
        ],
        'Nha Trang Bay reserve anchor' => [
            'khu-du-tru-thien-nhien-vinh-nha-trang-khanh-hoa',
        ],
        'Nha Trang Beach image credit' => [
            'Christophe95 / CC BY-SA 4.0',
        ],
        'Nha Trang coastline image credit' => [
            'Tony24986 / CC BY-SA 4.0',
        ],
        'Nha Trang Po Nagar image credit' => [
            'Tervlugt / CC BY 3.0',
        ],
        'Nha Trang Long Son image credit' => [
            'Christophe95 / CC BY-SA 4.0',
        ],
        'Nha Trang Hon Mun image credit' => [
            'Nguyen Hung Vu / CC BY 2.0',
        ],
        'Nha Trang Cam Ranh image credit' => [
            'Ed Crystal / CC BY-SA 4.0',
        ],
        'Nha Trang Cathedral image credit' => [
            'Vinhtantran / CC BY-SA 3.0',
        ],
    ]
);

$required_nha_trang_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$nha_trang_guide_page = vg_verify_eeat_require_published_path(
    'destinations/nha-trang-travel-guide',
    'Nha Trang Travel Guide',
    $failures
);

if ($nha_trang_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $nha_trang_guide_page,
        'Nha Trang Travel Guide',
        'destinations/nha-trang-travel-guide',
        'Nha Trang Travel Guide',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $nha_trang_guide_page,
        'Nha Trang Travel Guide',
        $required_nha_trang_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $nha_trang_guide_page,
        'Nha Trang Travel Guide',
        $required_nha_trang_guide_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $nha_trang_guide_page,
        'Nha Trang Travel Guide',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $nha_trang_guide_page,
        'Nha Trang Travel Guide',
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
            'destinations/best-islands-in-vietnam' => 'Best Islands in Vietnam guide',
            'destinations/phu-quoc-travel-guide' => 'Phu Quoc Travel Guide',
            'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
            'compare/phu-quoc-vs-nha-trang' => 'Phu Quoc vs Nha Trang guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ],
        '/destinations/nha-trang-travel-guide/',
        'Nha Trang Travel Guide',
        $failures
    );
}

$required_quy_nhon_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Quy Nhon hero marker' => [
            'vg-quy-nhon-hero:v1',
        ],
        'Quy Nhon concierge verdict' => [
            'vg-quy-nhon-concierge-verdict',
        ],
        'Quy Nhon at a glance' => [
            'vg-quy-nhon-at-a-glance:v1',
        ],
        'Quy Nhon photo grid' => [
            'vg-quy-nhon-photo-grid:v1',
        ],
        'Quy Nhon source diversity' => [
            'vg-quy-nhon-source-diversity:v1',
        ],
        'Quy Nhon route fit' => [
            'vg-quy-nhon-route-fit:v1',
        ],
        'Quy Nhon where to stay' => [
            'vg-quy-nhon-where-to-stay:v1',
        ],
        'Quy Nhon priority map' => [
            'vg-quy-nhon-priority-map:v1',
        ],
        'Quy Nhon season weather' => [
            'vg-quy-nhon-season-weather:v1',
        ],
        'Quy Nhon transport logistics' => [
            'vg-quy-nhon-transport-logistics:v1',
        ],
        'Quy Nhon cost booking' => [
            'vg-quy-nhon-cost-booking:v1',
        ],
        'Quy Nhon skip logic' => [
            'vg-quy-nhon-skip-logic:v1',
        ],
        'Quy Nhon live checks' => [
            'vg-quy-nhon-live-checks:v1',
        ],
        'Quy Nhon FAQ' => [
            'vg-quy-nhon-faq:v1',
        ],
        'Quy Nhon official tourism anchor' => [
            'vietnam.travel/node/1453',
        ],
        'Quy Nhon local tourism intro anchor' => [
            'dulichquynhon.binhdinh.gov.vn/en/introduction',
        ],
        'Quy Nhon Ky Co anchor' => [
            'dulichquynhon.binhdinh.gov.vn/en/baikyco',
        ],
        'Quy Nhon Eo Gio anchor' => [
            'dulichquynhon.binhdinh.gov.vn/en/eogiolandscape',
        ],
        'Quy Nhon beach anchor' => [
            'dulichquynhon.binhdinh.gov.vn/en/bienquynhon',
        ],
        'Quy Nhon Thap Doi anchor' => [
            'dulichquynhon.binhdinh.gov.vn/vi/thapdoiquynhon',
        ],
        'Quy Nhon Phu Cat airport anchor' => [
            'acv.vn/en/uih',
        ],
        'Quy Nhon weather anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Quy Nhon transport anchor' => [
            'transport-within-vietnam',
        ],
        'Quy Nhon rail anchor' => [
            'dsvn.vn',
        ],
        'Quy Nhon eVisa anchor' => [
            'evisa.gov.vn',
        ],
        'Quy Nhon NCHMF anchor' => [
            'kttv/en-US/1/index.html',
        ],
        'Quy Nhon hero image credit' => [
            'Boconganh Phan / Public domain',
        ],
        'Quy Nhon Ky Co image credit' => [
            'Le Ho Bac / CC BY-SA 4.0',
        ],
        'Quy Nhon Eo Gio image credit' => [
            'Hung Ho Ba / CC BY 2.0',
        ],
        'Quy Nhon promenade image credit' => [
            'VeeWin / CC BY-SA 4.0',
        ],
        'Quy Nhon Thap Doi image credit' => [
            'Ms Sarah Welch / CC0',
        ],
        'Quy Nhon Thi Nai image credit' => [
            'Swaminathan / Teofilo / CC BY 2.0',
        ],
        'Quy Nhon Phu Cat image credit' => [
            'Uranus2808 / CC BY-SA 4.0',
        ],
    ]
);

$required_quy_nhon_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$quy_nhon_guide_page = vg_verify_eeat_require_published_path(
    'destinations/quy-nhon-travel-guide',
    'Quy Nhon Travel Guide',
    $failures
);

if ($quy_nhon_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $quy_nhon_guide_page,
        'Quy Nhon Travel Guide',
        'destinations/quy-nhon-travel-guide',
        'Quy Nhon Travel Guide',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $quy_nhon_guide_page,
        'Quy Nhon Travel Guide',
        $required_quy_nhon_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $quy_nhon_guide_page,
        'Quy Nhon Travel Guide',
        'visible Quy Nhon official sources',
        [
            'vietnam.travel/node/1453',
            'dulichquynhon.binhdinh.gov.vn/en/introduction',
            'dulichquynhon.binhdinh.gov.vn/en/baikyco',
            'dulichquynhon.binhdinh.gov.vn/en/eogiolandscape',
            'dulichquynhon.binhdinh.gov.vn/en/bienquynhon',
            'dulichquynhon.binhdinh.gov.vn/vi/thapdoiquynhon',
            'acv.vn/en/uih',
            'weather-and-climate-vietnam',
            'transport-within-vietnam',
            'dsvn.vn',
            'evisa.gov.vn',
            'kttv/en-US/1/index.html',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $quy_nhon_guide_page,
        'Quy Nhon Travel Guide',
        'visible Quy Nhon image credits',
        [
            'Ky_Co_-_Nhon_Ly_-_Quy_Nhon_-_Binh_Dinh_-_Viet_Nam.jpg',
            'Ky_Co_beach%2C_Quy_Nhon_city%2C_Binh_Dinh_province%2C_Vietnam.jpg',
            'Eo_Gi%C3%B3_-_Nh%C6%A1n_L%C3%BD.jpg',
            'Quy_Nhon_Beach_Promenade.jpg',
            '0040323_Thap_Doi_Cham_Hindu_complex%2C_Quy_Nhon%2C_Binh_Dinh_Vietnam_185.jpg',
            'Qui_Nhon_and_Thi_Nai_Bridge_2007-10-03.jpg',
            'PhuCatAirport_newterminal.jpg',
            'Boconganh Phan / Public domain',
            'Le Ho Bac / CC BY-SA 4.0',
            'Hung Ho Ba / CC BY 2.0',
            'VeeWin / CC BY-SA 4.0',
            'Ms Sarah Welch / CC0',
            'Swaminathan / Teofilo / CC BY 2.0',
            'Uranus2808 / CC BY-SA 4.0',
        ],
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $quy_nhon_guide_page,
        'Quy Nhon Travel Guide',
        'hero image credit metadata',
        [
            'Hero image: Ky Co - Nhon Ly - Quy Nhon - Binh Dinh - Viet Nam by Boconganh Phan, Public domain',
            'Ky Co beach by Le Ho Bac, CC BY-SA 4.0',
            'Eo Gio - Nhon Ly by Hung Ho Ba, CC BY 2.0',
            'Quy Nhon Beach Promenade by VeeWin, CC BY-SA 4.0',
            'Thap Doi Cham Hindu complex by Ms Sarah Welch, CC0',
            'Qui Nhon and Thi Nai Bridge by Swaminathan / Teofilo, CC BY 2.0',
            'PhuCatAirport newterminal by Uranus2808, CC BY-SA 4.0',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $quy_nhon_guide_page,
        'Quy Nhon Travel Guide',
        $required_quy_nhon_guide_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $quy_nhon_guide_page,
        'Quy Nhon Travel Guide',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $quy_nhon_guide_page,
        'Quy Nhon Travel Guide',
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
            'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
            'destinations/nha-trang-travel-guide' => 'Nha Trang Travel Guide',
            'compare/mui-ne-vs-nha-trang' => 'Mui Ne vs Nha Trang guide',
            'compare/phu-quoc-vs-nha-trang' => 'Phu Quoc vs Nha Trang guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ],
        '/destinations/quy-nhon-travel-guide/',
        'Quy Nhon Travel Guide',
        $failures
    );
}

$required_cham_islands_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Cham Islands hero marker' => [
            'vg-cham-islands-hero:v1',
        ],
        'Cham Islands concierge verdict' => [
            'vg-cham-islands-concierge-verdict',
        ],
        'Cham Islands at a glance' => [
            'vg-cham-islands-at-a-glance:v1',
        ],
        'Cham Islands photo grid' => [
            'vg-cham-islands-photo-grid:v1',
        ],
        'Cham Islands source diversity' => [
            'vg-cham-islands-source-diversity:v1',
        ],
        'Cham Islands route fit' => [
            'vg-cham-islands-route-fit:v1',
        ],
        'Cham Islands day trip overnight' => [
            'vg-cham-islands-day-trip-overnight:v1',
        ],
        'Cham Islands priority map' => [
            'vg-cham-islands-priority-map:v1',
        ],
        'Cham Islands season weather' => [
            'vg-cham-islands-season-weather:v1',
        ],
        'Cham Islands transport logistics' => [
            'vg-cham-islands-transport-logistics:v1',
        ],
        'Cham Islands cost booking' => [
            'vg-cham-islands-cost-booking:v1',
        ],
        'Cham Islands responsible visit' => [
            'vg-cham-islands-responsible-visit:v1',
        ],
        'Cham Islands skip logic' => [
            'vg-cham-islands-skip-logic:v1',
        ],
        'Cham Islands live checks' => [
            'vg-cham-islands-live-checks:v1',
        ],
        'Cham Islands FAQ' => [
            'vg-cham-islands-faq:v1',
        ],
        'Cham Islands UNESCO anchor' => [
            'unesco.org/en/mab/cu-lao-cham-hoi',
        ],
        'Cham Islands Vietnam.travel Hoi An anchor' => [
            'vietnam.travel/places-to-go/central-vietnam/hoi-an',
        ],
        'Cham Islands Vietnam.travel guide anchor' => [
            'vietnam.travel/things-to-do/cu-lao-cham-an-eco-paradise-near-hoi-an',
        ],
        'Cham Islands Danang Fantasticity anchor' => [
            'danangfantasticity.com/en/heritage-landscape/cu-lao-cham-hoi-an-world-biosphere-reserve',
        ],
        'Cham Islands Hoi An World Heritage anchor' => [
            'hoianworldheritage.org.vn/en/news/Tours/cu-lao-cham-island-was-recognized-by-unesco-as-a-world-biosphere-reserve-701.hwh',
        ],
        'Cham Islands MPA anchor' => [
            'culaochammpa.com.vn/?lang=en',
        ],
        'Cham Islands NBCA anchor' => [
            'en.nbca.gov.vn/khu-bao-ve-canh-quan-cu-lao-cham-quang-nam-2',
        ],
        'Cham Islands Da Nang airport anchor' => [
            'danangairport.vn/en',
        ],
        'Cham Islands weather anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Cham Islands transport anchor' => [
            'transport-within-vietnam',
        ],
        'Cham Islands eVisa anchor' => [
            'evisa.gov.vn',
        ],
        'Cham Islands NCHMF anchor' => [
            'kttv/en-US/1/index.html',
        ],
        'Cham Islands Soupybev image credit' => [
            'Soupybev / CC BY 4.0',
        ],
        'Cham Islands seaside image credit' => [
            'Theguywithkpdomain / CC0',
        ],
        'Cham Islands marine image credit' => [
            'Kok Leng Yeo / CC BY 2.0',
        ],
        'Cham Islands marine second image credit' => [
            'yeowatzup / CC BY 2.0',
        ],
        'Cham Islands Blon image credit' => [
            'Blon / CC BY-SA 3.0',
        ],
        'Cham Islands Viethavvh image credit' => [
            'Viethavvh / CC BY-SA 3.0',
        ],
    ]
);

$required_cham_islands_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$cham_islands_guide_page = vg_verify_eeat_require_published_path(
    'destinations/cham-islands-travel-guide',
    'Cham Islands Travel Guide',
    $failures
);

if ($cham_islands_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $cham_islands_guide_page,
        'Cham Islands Travel Guide',
        'destinations/cham-islands-travel-guide',
        'Cham Islands Travel Guide',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $cham_islands_guide_page,
        'Cham Islands Travel Guide',
        $required_cham_islands_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $cham_islands_guide_page,
        'Cham Islands Travel Guide',
        'visible Cham Islands official sources',
        [
            'unesco.org/en/mab/cu-lao-cham-hoi',
            'vietnam.travel/places-to-go/central-vietnam/hoi-an',
            'vietnam.travel/things-to-do/cu-lao-cham-an-eco-paradise-near-hoi-an',
            'danangfantasticity.com/en/heritage-landscape/cu-lao-cham-hoi-an-world-biosphere-reserve',
            'hoianworldheritage.org.vn/en/news/Tours/cu-lao-cham-island-was-recognized-by-unesco-as-a-world-biosphere-reserve-701.hwh',
            'culaochammpa.com.vn/?lang=en',
            'en.nbca.gov.vn/khu-bao-ve-canh-quan-cu-lao-cham-quang-nam-2',
            'danangairport.vn/en',
            'weather-and-climate-vietnam',
            'transport-within-vietnam',
            'evisa.gov.vn',
            'kttv/en-US/1/index.html',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $cham_islands_guide_page,
        'Cham Islands Travel Guide',
        'visible Cham Islands image credits',
        [
            'Cham_Island_%28C%C3%B9_Lao_Ch%C3%A0m%29_seen_from_M%E1%BB%B9_Kh%C3%AA_Beach',
            'A_seaside_shot_in_C%C3%B9_Lao_Ch%C3%A0m',
            'Cu_Lao_Cham_Marine_Park%2C_Vietnam.jpg',
            'Cu_Lao_Cham_Marine_Park%2C_Vietnam_%282594704519%29.jpg',
            'Ph%C6%A1i_m%E1%BB%B1c_t%E1%BA%A1i_c%C3%B9_lao_Ch%C3%A0m',
            'C%E1%BB%95ng_ch%C3%B9a_c%E1%BB%95_t%E1%BA%A1i_C%C3%B9_lao_Ch%C3%A0m',
            'Tr%C6%B0ng_b%C3%A0y_hi%E1%BB%87n_v%E1%BA%ADt_t%E1%BA%A1i_C%C3%B9_lao_Ch%C3%A0m',
            'Soupybev / CC BY 4.0',
            'Theguywithkpdomain / CC0',
            'Kok Leng Yeo / CC BY 2.0',
            'yeowatzup / CC BY 2.0',
            'Blon / CC BY-SA 3.0',
            'Viethavvh / CC BY-SA 3.0',
        ],
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $cham_islands_guide_page,
        'Cham Islands Travel Guide',
        'hero image credit metadata',
        [
            'Hero image: Cham Island seen from My Khe Beach by Soupybev, CC BY 4.0',
            'A seaside shot in Cu Lao Cham by Theguywithkpdomain, CC0',
            'Cu Lao Cham Marine Park by Kok Leng Yeo, CC BY 2.0',
            'Cu Lao Cham Marine Park (2594704519) by yeowatzup, CC BY 2.0',
            'dried squid on Cu Lao Cham and old pagoda gate by Blon, CC BY-SA 3.0',
            'artifact display on Cu Lao Cham by Viethavvh, CC BY-SA 3.0',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $cham_islands_guide_page,
        'Cham Islands Travel Guide',
        $required_cham_islands_guide_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $cham_islands_guide_page,
        'Cham Islands Travel Guide',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $cham_islands_guide_page,
        'Cham Islands Travel Guide',
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
            'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
            'compare/da-nang-vs-hoi-an' => 'Da Nang vs Hoi An guide',
            'destinations/best-islands-in-vietnam' => 'Best Islands in Vietnam guide',
            'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ],
        '/destinations/cham-islands-travel-guide/',
        'Cham Islands Travel Guide',
        $failures
    );
}

$required_ly_son_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Ly Son hero marker' => [
            'vg-ly-son-hero:v1',
        ],
        'Ly Son concierge verdict' => [
            'vg-ly-son-concierge-verdict',
        ],
        'Ly Son at a glance' => [
            'vg-ly-son-at-a-glance:v1',
        ],
        'Ly Son photo grid' => [
            'vg-ly-son-photo-grid:v1',
        ],
        'Ly Son source diversity' => [
            'vg-ly-son-source-diversity:v1',
        ],
        'Ly Son route fit' => [
            'vg-ly-son-route-fit:v1',
        ],
        'Ly Son one or two nights' => [
            'vg-ly-son-nights-skip:v1',
        ],
        'Ly Son priority map' => [
            'vg-ly-son-priority-map:v1',
        ],
        'Ly Son season weather' => [
            'vg-ly-son-season-weather:v1',
        ],
        'Ly Son transport logistics' => [
            'vg-ly-son-transport-logistics:v1',
        ],
        'Ly Son cost booking' => [
            'vg-ly-son-cost-booking:v1',
        ],
        'Ly Son responsible visit' => [
            'vg-ly-son-responsible-visit:v1',
        ],
        'Ly Son skip logic' => [
            'vg-ly-son-skip-logic:v1',
        ],
        'Ly Son live checks' => [
            'vg-ly-son-live-checks:v1',
        ],
        'Ly Son FAQ' => [
            'vg-ly-son-faq:v1',
        ],
        'Ly Son Vietnam.travel main anchor' => [
            'vietnam.travel/things-to-do/ly-son-island-vietnams-next-must-visit-island-destination',
        ],
        'Ly Son Vietnam.travel overview anchor' => [
            'vietnam.travel/things-to-do/visit-ly-son-island-for-pristine-beaches-and-more',
        ],
        'Ly Son Vietnam.travel central Vietnam anchor' => [
            'vietnam.travel/things-to-do/top-things-do-central-vietnam',
        ],
        'Ly Son Vietnam.travel beach islands anchor' => [
            'vietnam.travel/things-to-do/nature-culture-and-adventure-vietnam-top-5-beach-islands',
        ],
        'Ly Son Quang Ngai tourism anchor' => [
            'quangngai.gov.vn/en/tourism',
        ],
        'Ly Son To Vo anchor' => [
            'to-vo-gate-on-ly-son-island-recognized-as-national-scenic-relic.html',
        ],
        'Ly Son tourism center anchor' => [
            'quang-ngai-to-develop-ly-son-into-a-center-of-sea-and-island-tourism.html',
        ],
        'Ly Son seaport planning anchor' => [
            'quang-ngai-province-implements-detailed-planning-for-seaport-land-and-water-development-through-2050.html',
        ],
        'Ly Son special zone anchor' => [
            'quang-ngai-reviews-socio-economic-performance-in-ly-son-special-zone.html',
        ],
        'Ly Son ACV airport anchor' => [
            'vietnamairport.vn',
        ],
        'Ly Son Sa Ky port anchor' => [
            'cangsaky.com.vn',
        ],
        'Ly Son ticket rules anchor' => [
            'qui-dinh-mua-ve-truc-tuyen.html',
        ],
        'Ly Son district portal anchor' => [
            'lyson.quangngai.gov.vn',
        ],
        'Ly Son weather anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Ly Son transport anchor' => [
            'transport-within-vietnam',
        ],
        'Ly Son eVisa anchor' => [
            'evisa.gov.vn',
        ],
        'Ly Son NCHMF anchor' => [
            'kttv/en-US/1/index.html',
        ],
        'Ly Son hero image credit' => [
            'minhphuc_99kdd / Public domain',
        ],
        'Ly Son Tu Khoi image credit' => [
            'Tu Khoi, Dai Doan Ket / CC BY 3.0',
        ],
        'Ly Son Dao Be image credit' => [
            'Doi Ta Di / CC BY-SA 4.0',
        ],
        'Ly Son temple image credit' => [
            'BertholdD / CC BY-SA 3.0',
        ],
        'Ly Son panorama image credit' => [
            'Hoang Sa Data Center / CC BY 3.0',
        ],
    ]
);

$required_ly_son_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$ly_son_guide_page = vg_verify_eeat_require_published_path(
    'destinations/ly-son-travel-guide',
    'Ly Son Travel Guide',
    $failures
);

if ($ly_son_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $ly_son_guide_page,
        'Ly Son Travel Guide',
        'destinations/ly-son-travel-guide',
        'Ly Son Travel Guide',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $ly_son_guide_page,
        'Ly Son Travel Guide',
        $required_ly_son_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $ly_son_guide_page,
        'Ly Son Travel Guide',
        'visible Ly Son official sources',
        [
            'vietnam.travel/things-to-do/ly-son-island-vietnams-next-must-visit-island-destination',
            'vietnam.travel/things-to-do/visit-ly-son-island-for-pristine-beaches-and-more',
            'vietnam.travel/things-to-do/top-things-do-central-vietnam',
            'vietnam.travel/things-to-do/nature-culture-and-adventure-vietnam-top-5-beach-islands',
            'quangngai.gov.vn/en/tourism',
            'to-vo-gate-on-ly-son-island-recognized-as-national-scenic-relic.html',
            'quang-ngai-to-develop-ly-son-into-a-center-of-sea-and-island-tourism.html',
            'quang-ngai-province-implements-detailed-planning-for-seaport-land-and-water-development-through-2050.html',
            'quang-ngai-reviews-socio-economic-performance-in-ly-son-special-zone.html',
            'vietnamairport.vn',
            'cangsaky.com.vn',
            'qui-dinh-mua-ve-truc-tuyen.html',
            'lyson.quangngai.gov.vn',
            'weather-and-climate-vietnam',
            'transport-within-vietnam',
            'evisa.gov.vn',
            'kttv/en-US/1/index.html',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $ly_son_guide_page,
        'Ly Son Travel Guide',
        'visible Ly Son image credits',
        [
            'Ly_Son_Islands_%2814817868968%29.jpg',
            'Dao_Ly_Son_nhin_tu_xa.jpg',
            'B%C3%A3i_t%E1%BA%AFm_%C4%91%E1%BA%A3o_b%C3%A9_L%C3%BD_S%C6%A1n.jpg',
            'Tau_danh_ca_tai_Ly_Son.jpg',
            'Temple_in_Ly_Son.jpg',
            'Mot_phan_quang_canh_Ly_Son.jpg',
            'minhphuc_99kdd / Public domain',
            'Tu Khoi, Dai Doan Ket / CC BY 3.0',
            'Doi Ta Di / CC BY-SA 4.0',
            'BertholdD / CC BY-SA 3.0',
            'Hoang Sa Data Center / CC BY 3.0',
        ],
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $ly_son_guide_page,
        'Ly Son Travel Guide',
        'hero image credit metadata',
        [
            'Hero image: Ly Son Islands (14817868968) by minhphuc_99kdd, Public domain',
            'Dao Ly Son nhin tu xa and Tau danh ca tai Ly Son by Tu Khoi, Dai Doan Ket, CC BY 3.0',
            'Bai tam dao be Ly Son by Doi Ta Di, CC BY-SA 4.0',
            'Temple in Ly Son by BertholdD, CC BY-SA 3.0',
            'Mot phan quang canh Ly Son by Hoang Sa Data Center, CC BY 3.0',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $ly_son_guide_page,
        'Ly Son Travel Guide',
        $required_ly_son_guide_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $ly_son_guide_page,
        'Ly Son Travel Guide',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $ly_son_guide_page,
        'Ly Son Travel Guide',
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-islands-in-vietnam' => 'Best Islands in Vietnam guide',
            'destinations/cham-islands-travel-guide' => 'Cham Islands Travel Guide',
            'destinations/da-nang-travel-guide' => 'Da Nang Travel Guide',
            'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
            'compare/da-nang-vs-hoi-an' => 'Da Nang vs Hoi An guide',
            'destinations/best-beaches-in-vietnam' => 'Best Beaches in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ],
        '/destinations/ly-son-travel-guide/',
        'Ly Son Travel Guide',
        $failures
    );
}

$required_hcmc_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'HCMC hero marker' => [
            'vg-hcmc-hero:v1',
        ],
        'HCMC concierge verdict' => [
            'vg-hcmc-concierge-verdict',
        ],
        'HCMC at a glance' => [
            'vg-hcmc-at-a-glance:v1',
        ],
        'HCMC photo grid' => [
            'vg-hcmc-photo-grid:v1',
        ],
        'HCMC source diversity' => [
            'vg-hcmc-source-diversity:v1',
        ],
        'HCMC districts' => [
            'vg-hcmc-districts:v1',
        ],
        'HCMC route fit' => [
            'vg-hcmc-route-fit:v1',
        ],
        'HCMC priority map' => [
            'vg-hcmc-priority-map:v1',
        ],
        'HCMC day trips' => [
            'vg-hcmc-day-trips:v1',
        ],
        'HCMC season weather' => [
            'vg-hcmc-season-weather:v1',
        ],
        'HCMC transport logistics' => [
            'vg-hcmc-transport-logistics:v1',
        ],
        'HCMC cost booking' => [
            'vg-hcmc-cost-booking:v1',
        ],
        'HCMC skip logic' => [
            'vg-hcmc-skip-logic:v1',
        ],
        'HCMC live checks' => [
            'vg-hcmc-live-checks:v1',
        ],
        'HCMC FAQ' => [
            'vg-hcmc-faq:v1',
        ],
        'HCMC where to stay support' => [
            'Where to Stay in Ho Chi Minh City',
        ],
        'HCMC official destination anchor' => [
            'vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city',
        ],
        'HCMC official tourism portal anchor' => [
            'visithcmc.vn',
        ],
        'HCMC ACV Tan Son Nhat anchor' => [
            'vietnamairport.vn/en/tan-son-nhat-airport',
        ],
        'HCMC ACV airport profile anchor' => [
            'acv.vn/en/airports/tan-son-nhat-international-airport',
        ],
        'HCMC Vietnam Airlines airport guide anchor' => [
            'vietnamairlines.com/ca/en/plan-book/travel/travel-guide/airport-ho-chi-minh-to-city',
        ],
        'HCMC weather anchor' => [
            'weather-and-climate-vietnam',
        ],
        'HCMC NCHMF anchor' => [
            'kttv/en-US/1/index.html',
        ],
        'HCMC getting Vietnam anchor' => [
            'getting-vietnam',
        ],
        'HCMC transport anchor' => [
            'transport-within-vietnam',
        ],
        'HCMC eVisa anchor' => [
            'evisa.gov.vn',
        ],
        'HCMC hero image credit' => [
            'Steffen Schmitz / CC BY-SA 4.0',
        ],
        'HCMC market image credit' => [
            'Bahnfrend / CC BY-SA 4.0',
        ],
        'HCMC War Remnants image credit' => [
            'Prenn / CC BY-SA 3.0',
        ],
        'HCMC Reunification image credit' => [
            'Eustaquio Santimano / CC BY 2.0',
        ],
        'HCMC Cu Chi image credit' => [
            'flowcomm / CC BY 2.0',
        ],
        'HCMC Mekong image credit' => [
            'Vyacheslav Argenberg / CC BY 4.0',
        ],
    ]
);

$required_hcmc_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$hcmc_guide_page = vg_verify_eeat_require_published_path(
    'destinations/ho-chi-minh-city-travel-guide',
    'Ho Chi Minh City Travel Guide',
    $failures
);

if ($hcmc_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $hcmc_guide_page,
        'Ho Chi Minh City Travel Guide',
        'destinations/ho-chi-minh-city-travel-guide',
        'Ho Chi Minh City Travel Guide',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $hcmc_guide_page,
        'Ho Chi Minh City Travel Guide',
        $required_hcmc_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $hcmc_guide_page,
        'Ho Chi Minh City Travel Guide',
        'visible HCMC official sources',
        [
            'vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city',
            'visithcmc.vn',
            'vietnamairport.vn/en/tan-son-nhat-airport',
            'acv.vn/en/airports/tan-son-nhat-international-airport',
            'vietnamairlines.com/ca/en/plan-book/travel/travel-guide/airport-ho-chi-minh-to-city',
            'weather-and-climate-vietnam',
            'kttv/en-US/1/index.html',
            'getting-vietnam',
            'transport-within-vietnam',
            'evisa.gov.vn',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $hcmc_guide_page,
        'Ho Chi Minh City Travel Guide',
        'visible HCMC image credits',
        [
            'Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg',
            'Ben_Thanh_Market%2C_2023_%2803%29.jpg',
            'Ho_Chi_Minh_City%2C_Central_Post_Office%2C_2020-01_CN-01.jpg',
            'War_Remnants_Museum%2C_HCMC%2C_front.JPG',
            'Reunification_Palace%2C_Ho_Chi_Minh_City%2C_Vietnam.jpg',
            'Cu_Chi_Tunnels%2C_Ho_Chi_Minh_City%2C_Vietnam_%2849579798986%29.jpg',
            'Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg',
            'Steffen Schmitz / CC BY-SA 4.0',
            'Bahnfrend / CC BY-SA 4.0',
            'Prenn / CC BY-SA 3.0',
            'Eustaquio Santimano / CC BY 2.0',
            'flowcomm / CC BY 2.0',
            'Vyacheslav Argenberg / CC BY 4.0',
        ],
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $hcmc_guide_page,
        'Ho Chi Minh City Travel Guide',
        'hero image credit metadata',
        [
            'Hero and Nguyen Hue/City Hall image: Ho Chi Minh City, City Hall, 2020-01 CN-02 by Steffen Schmitz, CC BY-SA 4.0',
            'Ben Thanh Market, 2023 (03) by Bahnfrend, CC BY-SA 4.0',
            'Ho Chi Minh City Central Post Office by Steffen Schmitz, CC BY-SA 4.0',
            'War Remnants Museum, HCMC, front by Prenn, CC BY-SA 3.0',
            'Reunification Palace by Eustaquio Santimano, CC BY 2.0',
            'Cu Chi Tunnels by flowcomm, CC BY 2.0',
            'Vietnam, Phong Dien, Mekong Delta, River by Vyacheslav Argenberg, CC BY 4.0',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $hcmc_guide_page,
        'Ho Chi Minh City Travel Guide',
        $required_hcmc_guide_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hcmc_guide_page,
        'Ho Chi Minh City Travel Guide',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $hcmc_guide_page,
        'Ho Chi Minh City Travel Guide',
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'destinations/best-day-trips-from-ho-chi-minh-city' => 'Best Day Trips from Ho Chi Minh City',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
            'destinations/phu-quoc-travel-guide' => 'Phu Quoc Travel Guide',
            'destinations/con-dao-travel-guide' => 'Con Dao Travel Guide',
            'destinations/where-to-stay-in-ho-chi-minh-city' => 'Where to Stay in Ho Chi Minh City',
        ],
        '/destinations/ho-chi-minh-city-travel-guide/',
        'Ho Chi Minh City Travel Guide',
        $failures
    );
}

$required_hanoi_stays_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Hanoi stay hero marker' => [
            'vg-hanoi-stays-hero:v1',
        ],
        'Hanoi stay concierge verdict' => [
            'vg-hanoi-stays-concierge-verdict',
        ],
        'Hanoi stay at a glance' => [
            'vg-hanoi-stays-at-a-glance:v1',
        ],
        'Hanoi stay photo grid' => [
            'vg-hanoi-stays-photo-grid:v1',
        ],
        'Hanoi stay source diversity' => [
            'vg-hanoi-stays-source-diversity:v1',
        ],
        'Hanoi stay area verdict' => [
            'vg-hanoi-stays-area-verdict:v1',
        ],
        'Hanoi stay first time fit' => [
            'vg-hanoi-stays-first-time-fit:v1',
        ],
        'Hanoi stay noise map' => [
            'vg-hanoi-stays-noise-map:v1',
        ],
        'Hanoi stay airport buffer' => [
            'vg-hanoi-stays-airport-buffer:v1',
        ],
        'Hanoi stay day trip pickup' => [
            'vg-hanoi-stays-day-trip-pickup:v1',
        ],
        'Hanoi stay traveler profiles' => [
            'vg-hanoi-stays-traveler-profiles:v1',
        ],
        'Hanoi stay cost booking' => [
            'vg-hanoi-stays-cost-booking:v1',
        ],
        'Hanoi stay safety comfort' => [
            'vg-hanoi-stays-safety-comfort:v1',
        ],
        'Hanoi stay live checks' => [
            'vg-hanoi-stays-live-checks:v1',
        ],
        'Hanoi stay FAQ' => [
            'vg-hanoi-stays-faq:v1',
        ],
        'Hanoi stay support link' => [
            'Hanoi Travel Guide',
        ],
        'Hanoi stay neighborhood comparison link' => [
            '/compare/old-quarter-vs-french-quarter-vs-west-lake/',
        ],
        'Hanoi stay official destination anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam/ha-noi',
        ],
        'Hanoi stay Old Quarter anchor' => [
            'explore-old-quarter-your-way',
        ],
        'Hanoi stay West Lake/luxury context anchor' => [
            'comfort-meet-culture-hanoi-luxury-hotels',
        ],
        'Hanoi stay French Quarter/history anchor' => [
            'vietnamese-history-primer',
        ],
        'Hanoi stay attractions/city-break anchor' => [
            '11-must-see-attractions-ha-noi',
        ],
        'Hanoi stay Noi Bai anchor' => [
            'vietnamairport.vn/en/noi-bai-airport',
        ],
        'Hanoi stay NCHMF anchor' => [
            'nchmf.gov.vn',
        ],
        'Hanoi stay UNESCO anchor' => [
            'whc.unesco.org/en/list/1328',
        ],
    ]
);

$required_hanoi_stays_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$hanoi_stays_guide_page = vg_verify_eeat_require_published_path(
    'destinations/where-to-stay-in-hanoi',
    'Where to Stay in Hanoi',
    $failures
);

if ($hanoi_stays_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $hanoi_stays_guide_page,
        'Where to Stay in Hanoi',
        'destinations/where-to-stay-in-hanoi',
        'Where to Stay in Hanoi',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $hanoi_stays_guide_page,
        'Where to Stay in Hanoi',
        $required_hanoi_stays_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $hanoi_stays_guide_page,
        'Where to Stay in Hanoi',
        'visible Hanoi stay official sources',
        [
            'vietnam.travel/places-to-go/northern-vietnam/ha-noi',
            'explore-old-quarter-your-way',
            'comfort-meet-culture-hanoi-luxury-hotels',
            'vietnamese-history-primer',
            '11-must-see-attractions-ha-noi',
            'vietnamairport.vn/en/noi-bai-airport',
            'nchmf.gov.vn',
            'whc.unesco.org/en/list/1328',
            '/compare/old-quarter-vs-french-quarter-vs-west-lake/',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $hanoi_stays_guide_page,
        'Where to Stay in Hanoi',
        $required_hanoi_stays_guide_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hanoi_stays_guide_page,
        'Where to Stay in Hanoi',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $hanoi_stays_guide_page,
        'Where to Stay in Hanoi',
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $hanoi_stays_guide_page,
        'Where to Stay in Hanoi',
        [
            '/destinations/hanoi-travel-guide/' => 'Hanoi Travel Guide',
            '/compare/old-quarter-vs-french-quarter-vs-west-lake/' => 'Old Quarter vs French Quarter vs West Lake',
            '/destinations/best-things-to-do-in-hanoi/' => 'Best Things to Do in Hanoi',
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/destinations/best-places-to-visit-vietnam/' => 'Best Places to Visit in Vietnam',
            '/compare/north-central-south-vietnam/' => 'North vs Central vs South Vietnam',
            '/itineraries/7-days-in-vietnam/' => '7 Days in Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/money-cash-cards-atms/' => 'Money in Vietnam',
            '/plan/sim-esim-vietnam/' => 'SIM and eSIM in Vietnam',
            '/plan/vietnam-evisa/' => 'Vietnam E-Visa',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
            '/destinations/ninh-binh-travel-guide/' => 'Ninh Binh Travel Guide',
            '/destinations/ha-long-bay-travel-guide/' => 'Ha Long Bay Travel Guide',
            '/destinations/cat-ba-travel-guide/' => 'Cat Ba Travel Guide',
            '/destinations/bai-tu-long-bay-guide/' => 'Bai Tu Long Bay Guide',
        ],
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
        ],
        '/destinations/where-to-stay-in-hanoi/',
        'Where to Stay in Hanoi',
        $failures
    );
}

$required_hanoi_neighborhood_comparison_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Hanoi neighborhood comparison hero marker' => [
            'vg-hanoi-neighborhood-compare-hero:v1',
        ],
        'Hanoi neighborhood comparison concierge verdict' => [
            'vg-hanoi-neighborhood-compare-concierge-verdict',
        ],
        'Hanoi neighborhood comparison at a glance' => [
            'vg-hanoi-neighborhood-compare-at-a-glance:v1',
        ],
        'Hanoi neighborhood comparison photo grid' => [
            'vg-hanoi-neighborhood-compare-photo-grid:v1',
        ],
        'Hanoi neighborhood comparison source diversity' => [
            'vg-hanoi-neighborhood-compare-source-diversity:v1',
        ],
        'Hanoi neighborhood comparison decision matrix' => [
            'vg-hanoi-neighborhood-compare-decision-matrix:v1',
        ],
        'Hanoi neighborhood comparison first night fit' => [
            'vg-hanoi-neighborhood-compare-first-night-fit:v1',
        ],
        'Hanoi neighborhood comparison sleep noise' => [
            'vg-hanoi-neighborhood-compare-sleep-noise:v1',
        ],
        'Hanoi neighborhood comparison food walks' => [
            'vg-hanoi-neighborhood-compare-food-walks:v1',
        ],
        'Hanoi neighborhood comparison transfer pickup' => [
            'vg-hanoi-neighborhood-compare-transfer-pickup:v1',
        ],
        'Hanoi neighborhood comparison traveler profiles' => [
            'vg-hanoi-neighborhood-compare-traveler-profiles:v1',
        ],
        'Hanoi neighborhood comparison cost booking' => [
            'vg-hanoi-neighborhood-compare-cost-booking:v1',
        ],
        'Hanoi neighborhood comparison skip logic' => [
            'vg-hanoi-neighborhood-compare-skip-logic:v1',
        ],
        'Hanoi neighborhood comparison live checks' => [
            'vg-hanoi-neighborhood-compare-live-checks:v1',
        ],
        'Hanoi neighborhood comparison FAQ' => [
            'vg-hanoi-neighborhood-compare-faq:v1',
        ],
        'Hanoi neighborhood comparison official destination anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam/ha-noi',
        ],
        'Hanoi neighborhood comparison Old Quarter source anchor' => [
            'explore-old-quarter-your-way',
        ],
        'Hanoi neighborhood comparison comfort source anchor' => [
            'comfort-meet-culture-hanoi-luxury-hotels',
        ],
        'Hanoi neighborhood comparison airport source anchor' => [
            'vietnamairport.vn/en/noi-bai-airport',
        ],
        'Hanoi neighborhood comparison weather source anchor' => [
            'nchmf.gov.vn',
        ],
        'Hanoi neighborhood comparison hero credit' => [
            'xiquinhosilva / CC BY 2.0',
        ],
        'Hanoi neighborhood comparison Old Quarter image credit' => [
            'Jakub Halun / CC BY 4.0',
        ],
        'Hanoi neighborhood comparison French Quarter image credit' => [
            'thalling55 / CC BY 2.0',
        ],
        'Hanoi neighborhood comparison West Lake image credit' => [
            'Amenoc / CC BY-SA 4.0',
        ],
        'Hanoi neighborhood comparison Tran Quoc image credit' => [
            'Syced / CC0',
        ],
    ]
);

$required_hanoi_neighborhood_comparison_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$hanoi_neighborhood_comparison_page = vg_verify_eeat_require_published_path(
    'compare/old-quarter-vs-french-quarter-vs-west-lake',
    'Old Quarter vs French Quarter vs West Lake',
    $failures
);

if ($hanoi_neighborhood_comparison_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $hanoi_neighborhood_comparison_page,
        'Old Quarter vs French Quarter vs West Lake',
        'compare/old-quarter-vs-french-quarter-vs-west-lake',
        'Old Quarter vs French Quarter vs West Lake',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $hanoi_neighborhood_comparison_page,
        'Old Quarter vs French Quarter vs West Lake',
        $required_hanoi_neighborhood_comparison_content_groups,
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $hanoi_neighborhood_comparison_page,
        'Old Quarter vs French Quarter vs West Lake',
        'visible Hanoi neighborhood comparison evidence',
        [
            'Old Quarter',
            'French Quarter',
            'West Lake',
            'vietnam.travel/places-to-go/northern-vietnam/ha-noi',
            'explore-old-quarter-your-way',
            'comfort-meet-culture-hanoi-luxury-hotels',
            'vietnamairport.vn/en/noi-bai-airport',
            'nchmf.gov.vn',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $hanoi_neighborhood_comparison_page,
        'Old Quarter vs French Quarter vs West Lake',
        $required_hanoi_neighborhood_comparison_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hanoi_neighborhood_comparison_page,
        'Old Quarter vs French Quarter vs West Lake',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $hanoi_neighborhood_comparison_page,
        'Old Quarter vs French Quarter vs West Lake',
        $failures
    );

    vg_verify_eeat_require_visible_external_body_link_budget(
        $hanoi_neighborhood_comparison_page,
        'Old Quarter vs French Quarter vs West Lake',
        2,
        4,
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $hanoi_neighborhood_comparison_page,
        'Old Quarter vs French Quarter vs West Lake',
        [
            '/destinations/hanoi-travel-guide/' => 'Hanoi Travel Guide',
            '/destinations/where-to-stay-in-hanoi/' => 'Where to Stay in Hanoi',
            '/destinations/best-things-to-do-in-hanoi/' => 'Best Things to Do in Hanoi',
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/destinations/best-places-to-visit-vietnam/' => 'Best Places to Visit in Vietnam',
            '/compare/north-central-south-vietnam/' => 'North vs Central vs South Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/money-cash-cards-atms/' => 'Money in Vietnam',
            '/plan/sim-esim-vietnam/' => 'SIM and eSIM in Vietnam',
            '/plan/vietnam-evisa/' => 'Vietnam E-Visa',
            '/itineraries/7-days-in-vietnam/' => '7 Days in Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
            '/destinations/ninh-binh-travel-guide/' => 'Ninh Binh Travel Guide',
            '/destinations/ha-long-bay-travel-guide/' => 'Ha Long Bay Travel Guide',
            '/destinations/cat-ba-travel-guide/' => 'Cat Ba Travel Guide',
            '/destinations/bai-tu-long-bay-guide/' => 'Bai Tu Long Bay Guide',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
        ],
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ],
        '/compare/old-quarter-vs-french-quarter-vs-west-lake/',
        'Old Quarter vs French Quarter vs West Lake',
        $failures
    );
}

$required_hanoi_airport_transfer_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Hanoi airport transfer hero marker' => [
            'vg-hanoi-airport-transfer-hero:v1',
        ],
        'Hanoi airport transfer concierge verdict' => [
            'vg-hanoi-airport-transfer-concierge-verdict',
        ],
        'Hanoi airport transfer at a glance' => [
            'vg-hanoi-airport-transfer-at-a-glance:v1',
        ],
        'Hanoi airport transfer photo grid' => [
            'vg-hanoi-airport-transfer-photo-grid:v1',
        ],
        'Hanoi airport transfer source diversity' => [
            'vg-hanoi-airport-transfer-source-diversity:v1',
        ],
        'Hanoi airport transfer verdict matrix' => [
            'vg-hanoi-airport-transfer-verdict-matrix:v1',
        ],
        'Hanoi airport transfer arrival flow' => [
            'vg-hanoi-airport-transfer-arrival-flow:v1',
        ],
        'Hanoi airport transfer option scorecard' => [
            'vg-hanoi-airport-transfer-option-scorecard:v1',
        ],
        'Hanoi airport transfer late arrivals' => [
            'vg-hanoi-airport-transfer-late-arrivals:v1',
        ],
        'Hanoi airport transfer bus guide' => [
            'vg-hanoi-airport-transfer-bus-guide:v1',
        ],
        'Hanoi airport transfer taxi app checks' => [
            'vg-hanoi-airport-transfer-taxi-app-checks:v1',
        ],
        'Hanoi airport transfer hotel pickup' => [
            'vg-hanoi-airport-transfer-hotel-pickup:v1',
        ],
        'Hanoi airport transfer cost buffer' => [
            'vg-hanoi-airport-transfer-cost-buffer:v1',
        ],
        'Hanoi airport transfer safety scams' => [
            'vg-hanoi-airport-transfer-safety-scams:v1',
        ],
        'Hanoi airport transfer live checks' => [
            'vg-hanoi-airport-transfer-live-checks:v1',
        ],
        'Hanoi airport transfer FAQ' => [
            'vg-hanoi-airport-transfer-faq:v1',
        ],
        'Hanoi airport transfer public transport anchor' => [
            'noibaiairport.vn/en/public-transportations-nid1.html',
        ],
        'Hanoi airport transfer airport profile anchor' => [
            'vietnamairport.vn/en/noi-bai-airport',
        ],
        'Hanoi airport transfer airline city guide anchor' => [
            'vietnamairlines.com/us/en/plan-book/travel/travel-guide/hanoi-airport-to-hanoi-city',
        ],
        'Hanoi airport transfer transport source anchor' => [
            'vietnam.travel/plan-your-trip/transport-within-vietnam',
        ],
        'Hanoi airport transfer NCHMF anchor' => [
            'nchmf.gov.vn',
        ],
        'Hanoi airport transfer eVisa anchor' => [
            'evisa.gov.vn',
        ],
        'Hanoi airport transfer hero image credit' => [
            'Sky 269 / CC BY-SA 4.0',
        ],
        'Hanoi airport transfer Hoan Kiem image credit' => [
            'Alex 69200 vx / CC BY-SA 4.0',
        ],
        'Hanoi airport transfer Old Quarter image credit' => [
            'yeowatzup / CC BY 2.0',
        ],
    ]
);

$required_hanoi_airport_transfer_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$hanoi_airport_transfer_page = vg_verify_eeat_require_published_path(
    'plan/hanoi-airport-to-old-quarter',
    'Hanoi Airport to Old Quarter',
    $failures
);

if ($hanoi_airport_transfer_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $hanoi_airport_transfer_page,
        'Hanoi Airport to Old Quarter',
        'plan/hanoi-airport-to-old-quarter',
        'Hanoi Airport to Old Quarter',
        $failures
    );

    vg_verify_eeat_require_page_sitemap_url(
        'plan/hanoi-airport-to-old-quarter',
        'Hanoi Airport to Old Quarter',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $hanoi_airport_transfer_page,
        'Hanoi Airport to Old Quarter',
        $required_hanoi_airport_transfer_content_groups,
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $hanoi_airport_transfer_page,
        'Hanoi Airport to Old Quarter',
        'rendered Hanoi airport transfer depth modules',
        [
            'For most first-time international arrivals, the best default is a prearranged hotel pickup or a verified app/official airport taxi after dark, and the airport bus only when you land rested, light, and central.',
            'Do not make your first Hanoi decision at the curb.',
            'arrival stack',
            'airport-to-hotel handoff',
            'Route 86 is useful when the savings matter and your luggage, phone data, and walking distance are all under control.',
            'A late arrival is not the moment to test every cheap option.',
            'Families and first-time long-haul travelers should buy certainty before savings.',
            'Noi Bai public transport page lists Route No.86 at 50,000 VND/trip',
            'the airport states taxi services run from the first flight to the final flight of the day',
            'Ignore unsolicited arrivals-hall approaches and choose the queue, the app, or the hotel name on a written confirmation.',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $hanoi_airport_transfer_page,
        'Hanoi Airport to Old Quarter',
        'visible Hanoi airport transfer image credits',
        [
            'Noi_Bai_International_Airport_T2_Waiting_Area.jpg',
            'Hanoi-lac-hoan-kiem.jpg',
            'Cho_Dong_Xuan%2C_Old_Quarter%2C_Hanoi%2C_Vietnam_%285245806573%29.jpg',
            'Sky 269 / CC BY-SA 4.0',
            'Alex 69200 vx / CC BY-SA 4.0',
            'yeowatzup / CC BY 2.0',
        ],
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $hanoi_airport_transfer_page,
        'Hanoi Airport to Old Quarter',
        'hero image credit metadata',
        [
            'Hero and arrival image: Noi Bai International Airport T2 waiting area by Sky 269, CC BY-SA 4.0',
            'Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0',
            'Cho Dong Xuan, Old Quarter, Hanoi by yeowatzup, CC BY 2.0',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $hanoi_airport_transfer_page,
        'Hanoi Airport to Old Quarter',
        $required_hanoi_airport_transfer_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hanoi_airport_transfer_page,
        'Hanoi Airport to Old Quarter',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $hanoi_airport_transfer_page,
        'Hanoi Airport to Old Quarter',
        $failures
    );

    vg_verify_eeat_require_visible_external_body_link_budget(
        $hanoi_airport_transfer_page,
        'Hanoi Airport to Old Quarter',
        3,
        5,
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $hanoi_airport_transfer_page,
        'Hanoi Airport to Old Quarter',
        [
            '/destinations/hanoi-travel-guide/' => 'Hanoi Travel Guide',
            '/destinations/where-to-stay-in-hanoi/' => 'Where to Stay in Hanoi',
            '/compare/old-quarter-vs-french-quarter-vs-west-lake/' => 'Old Quarter vs French Quarter vs West Lake',
            '/destinations/best-things-to-do-in-hanoi/' => 'Best Things to Do in Hanoi',
            '/destinations/best-day-trips-from-hanoi/' => 'Best Day Trips from Hanoi',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/plan/money-cash-cards-atms/' => 'Money in Vietnam',
            '/plan/sim-esim-vietnam/' => 'SIM and eSIM in Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/vietnam-evisa/' => 'Vietnam E-Visa',
            '/itineraries/7-days-in-vietnam/' => '7 Days in Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/compare/north-central-south-vietnam/' => 'North vs Central vs South Vietnam',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
        ],
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
            'compare/old-quarter-vs-french-quarter-vs-west-lake' => 'Old Quarter vs French Quarter vs West Lake',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ],
        '/plan/hanoi-airport-to-old-quarter/',
        'Hanoi Airport to Old Quarter',
        $failures,
        true
    );
}

$required_hanoi_two_days_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Hanoi in 2 Days hero marker' => [
            'vg-hanoi-2-days-hero:v1',
        ],
        'Hanoi in 2 Days concierge verdict' => [
            'vg-hanoi-2-days-concierge-verdict',
        ],
        'Hanoi in 2 Days at a glance' => [
            'vg-hanoi-2-days-at-a-glance:v1',
        ],
        'Hanoi in 2 Days photo grid' => [
            'vg-hanoi-2-days-photo-grid:v1',
        ],
        'Hanoi in 2 Days source diversity' => [
            'vg-hanoi-2-days-source-diversity:v1',
        ],
        'Hanoi in 2 Days rendered source trail snapshot' => [
            'vg-hanoi-2-days-source-trail-snapshot:v1',
        ],
        'Hanoi in 2 Days sequence' => [
            'vg-hanoi-2-days-sequence:v1',
        ],
        'Hanoi in 2 Days arrival pivot' => [
            'vg-hanoi-2-days-arrival-pivot:v1',
        ],
        'Hanoi in 2 Days stay area fit' => [
            'vg-hanoi-2-days-stay-area-fit:v1',
        ],
        'Hanoi in 2 Days day one plan' => [
            'vg-hanoi-2-days-day-one-plan:v1',
        ],
        'Hanoi in 2 Days day two plan' => [
            'vg-hanoi-2-days-day-two-plan:v1',
        ],
        'Hanoi in 2 Days weather pivots' => [
            'vg-hanoi-2-days-weather-pacing-pivots:v1',
        ],
        'Hanoi in 2 Days day trip pressure test' => [
            'vg-hanoi-2-days-day-trip-pressure-test:v1',
        ],
        'Hanoi in 2 Days budget comfort' => [
            'vg-hanoi-2-days-budget-comfort:v1',
        ],
        'Hanoi in 2 Days mistakes skip' => [
            'vg-hanoi-2-days-mistakes-skip:v1',
        ],
        'Hanoi in 2 Days live checks' => [
            'vg-hanoi-2-days-live-checks:v1',
        ],
        'Hanoi in 2 Days FAQ' => [
            'vg-hanoi-2-days-faq:v1',
        ],
        'Hanoi in 2 Days Vietnam.travel source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam/ha-noi',
        ],
        'Hanoi in 2 Days northern Vietnam source anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam',
        ],
        'Hanoi in 2 Days weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Hanoi in 2 Days NCHMF anchor' => [
            'nchmf.gov.vn',
        ],
        'Hanoi in 2 Days Noi Bai anchor' => [
            'vietnamairport.vn/en/noi-bai-airport',
        ],
        'Hanoi in 2 Days UNESCO anchor' => [
            'whc.unesco.org/en/list/1328',
        ],
        'Hanoi in 2 Days Thang Long official anchor' => [
            'hoangthanhthanglong.vn',
        ],
        'Hanoi in 2 Days Temple official anchor' => [
            'vanmieu.gov.vn',
        ],
        'Hanoi in 2 Days Women Museum anchor' => [
            'baotangphunu.org.vn',
        ],
        'Hanoi in 2 Days Hoan Kiem image credit' => [
            'Alex 69200 vx / CC BY-SA 4.0',
        ],
        'Hanoi in 2 Days Old Quarter image credit' => [
            'yeowatzup / CC BY 2.0',
        ],
        'Hanoi in 2 Days Temple image credit' => [
            'Jakub Halun / CC BY 4.0',
        ],
        'Hanoi in 2 Days Thang Long image credit' => [
            'katiebordner / CC BY 2.0',
        ],
        'Hanoi in 2 Days Long Bien image credit' => [
            'TheRollo76 / CC BY-SA 4.0',
        ],
    ]
);

$required_hanoi_two_days_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$hanoi_two_days_page = vg_verify_eeat_require_published_path(
    'itineraries/hanoi-in-2-days',
    'Hanoi in 2 Days',
    $failures
);

if ($hanoi_two_days_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $hanoi_two_days_page,
        'Hanoi in 2 Days',
        'itineraries/hanoi-in-2-days',
        'Hanoi in 2 Days',
        $failures
    );

    vg_verify_eeat_require_page_sitemap_url(
        'itineraries/hanoi-in-2-days',
        'Hanoi in 2 Days',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $hanoi_two_days_page,
        'Hanoi in 2 Days',
        $required_hanoi_two_days_content_groups,
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $hanoi_two_days_page,
        'Hanoi in 2 Days',
        'rendered Hanoi in 2 Days depth modules',
        [
            'For most first-time travelers, the best two-day Hanoi plan is arrival recovery plus Hoan Kiem and Old Quarter on Day 1',
            'skip Train Street, duplicate markets, and a long day trip',
            'A late Noi Bai arrival should make the first evening smaller',
            'two days is not enough for every Hanoi highlight',
            'The images are not decoration.',
            'Day 2 is where two-day Hanoi either becomes memorable or turns into a checklist.',
            'Check Best Day Trips from Hanoi only after the two city blocks are protected.',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $hanoi_two_days_page,
        'Hanoi in 2 Days',
        'rendered Hanoi in 2 Days source trail snapshot',
        [
            'Source trail snapshot',
            'Vietnam.travel Ha Noi destination page',
            'https://vietnam.travel/places-to-go/northern-vietnam/ha-noi',
            'National Centre for Hydro-Meteorological Forecasting',
            'https://www.nchmf.gov.vn/kttv/en-US/1/index.html',
            'Noi Bai International Airport',
            'https://vietnamairport.vn/en/noi-bai-airport',
            'UNESCO Thang Long and official Hanoi culture sites',
            'https://whc.unesco.org/en/list/1328/',
            'hoangthanhthanglong.vn, vanmieu.gov.vn, and baotangphunu.org.vn',
            'Wikimedia Commons image records',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $hanoi_two_days_page,
        'Hanoi in 2 Days',
        'visible Hanoi in 2 Days image credits',
        [
            'Hanoi-lac-hoan-kiem.jpg',
            'Cho_Dong_Xuan%2C_Old_Quarter%2C_Hanoi%2C_Vietnam_%285245806573%29.jpg',
            'Main_gate_of_the_Temple_of_Literature%2C_Hanoi%2C_Vietnam%2C_20240123_0929_3068.jpg',
            'Central_Sector_of_the_Imperial_Citadel_of_Thang_Long_-_Hanoi.jpg',
            'Long_Bien_Bridge.jpg',
            'Alex 69200 vx / CC BY-SA 4.0',
            'yeowatzup / CC BY 2.0',
            'Jakub Halun / CC BY 4.0',
            'katiebordner / CC BY 2.0',
            'TheRollo76 / CC BY-SA 4.0',
        ],
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $hanoi_two_days_page,
        'Hanoi in 2 Days',
        'hero image credit metadata',
        [
            'Hero and Hoan Kiem image: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0',
            'Cho Dong Xuan, Old Quarter, Hanoi by yeowatzup, CC BY 2.0',
            'Temple of Literature by Jakub Halun, CC BY 4.0',
            'Central Sector of the Imperial Citadel of Thang Long by katiebordner, CC BY 2.0',
            'Long Bien Bridge by TheRollo76, CC BY-SA 4.0',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $hanoi_two_days_page,
        'Hanoi in 2 Days',
        $required_hanoi_two_days_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hanoi_two_days_page,
        'Hanoi in 2 Days',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hanoi_two_days_page,
        'Hanoi in 2 Days',
        'vietnamguide/source-trail',
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hanoi_two_days_page,
        'Hanoi in 2 Days',
        'vietnamguide/update-log',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $hanoi_two_days_page,
        'Hanoi in 2 Days',
        $failures
    );

    vg_verify_eeat_require_visible_external_body_link_budget(
        $hanoi_two_days_page,
        'Hanoi in 2 Days',
        4,
        6,
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $hanoi_two_days_page,
        'Hanoi in 2 Days',
        [
            '/destinations/hanoi-travel-guide/' => 'Hanoi Travel Guide',
            '/destinations/best-things-to-do-in-hanoi/' => 'Best Things to Do in Hanoi',
            '/destinations/where-to-stay-in-hanoi/' => 'Where to Stay in Hanoi',
            '/plan/hanoi-airport-to-old-quarter/' => 'Hanoi Airport to Old Quarter',
            '/compare/old-quarter-vs-french-quarter-vs-west-lake/' => 'Old Quarter vs French Quarter vs West Lake',
            '/destinations/best-day-trips-from-hanoi/' => 'Best Day Trips from Hanoi',
            '/itineraries/7-days-in-vietnam/' => '7 Days in Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/sim-esim-vietnam/' => 'SIM and eSIM in Vietnam',
            '/plan/money-cash-cards-atms/' => 'Money in Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
        ],
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
            'compare/old-quarter-vs-french-quarter-vs-west-lake' => 'Old Quarter vs French Quarter vs West Lake',
            'plan/hanoi-airport-to-old-quarter' => 'Hanoi Airport to Old Quarter',
            'destinations/best-day-trips-from-hanoi' => 'Best Day Trips from Hanoi',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
        ],
        '/itineraries/hanoi-in-2-days/',
        'Hanoi in 2 Days',
        $failures,
        true
    );
}

$required_hanoi_day_trips_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Hanoi day trips hero marker' => [
            'vg-hanoi-day-trips-hero:v1',
        ],
        'Hanoi day trips concierge verdict' => [
            'vg-hanoi-day-trips-concierge-verdict',
        ],
        'Hanoi day trips at a glance' => [
            'vg-hanoi-day-trips-at-a-glance:v1',
        ],
        'Hanoi day trips photo grid' => [
            'vg-hanoi-day-trips-photo-grid:v1',
        ],
        'Hanoi day trips source diversity' => [
            'vg-hanoi-day-trips-source-diversity:v1',
        ],
        'Hanoi day trips decision grid' => [
            'vg-hanoi-day-trips-decision-grid:v1',
        ],
        'Hanoi day trips quick chooser' => [
            'vg-hanoi-day-trips-quick-chooser:v1',
        ],
        'Hanoi day trips route fit' => [
            'vg-hanoi-day-trips-route-fit:v1',
        ],
        'Hanoi day trips day placement' => [
            'vg-hanoi-day-trips-day-placement:v1',
        ],
        'Hanoi day trips transfer value scorecard' => [
            'vg-hanoi-day-trips-transfer-value-scorecard:v1',
        ],
        'Hanoi day trips overnight upgrade' => [
            'vg-hanoi-day-trips-overnight-upgrade:v1',
        ],
        'Hanoi day trips booking mode' => [
            'vg-hanoi-day-trips-booking-mode:v1',
        ],
        'Hanoi day trips operator checks' => [
            'vg-hanoi-day-trips-operator-checks:v1',
        ],
        'Hanoi day trips comfort fit' => [
            'vg-hanoi-day-trips-comfort-fit:v1',
        ],
        'Hanoi day trips skip logic' => [
            'vg-hanoi-day-trips-skip-logic:v1',
        ],
        'Hanoi day trips live checks' => [
            'vg-hanoi-day-trips-live-checks:v1',
        ],
        'Hanoi day trips FAQ' => [
            'vg-hanoi-day-trips-faq:v1',
        ],
        'Hanoi day trips official overview anchor' => [
            'vietnam.travel/things-to-do/5-hanoi-day-trips',
        ],
        'Hanoi day trips Ninh Binh anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam/ninh-binh',
        ],
        'Hanoi day trips Ha Long anchor' => [
            'vietnam.travel/places-to-go/northern-vietnam/ha-long',
        ],
        'Hanoi day trips UNESCO bay anchor' => [
            'whc.unesco.org/en/list/672',
        ],
        'Hanoi day trips Bat Trang anchor' => [
            'vietnam.travel/things-to-do/full-day-trip-bat-trang-pottery-village',
        ],
        'Hanoi day trips weather anchor' => [
            'vietnam.travel/things-to-do/weather-and-climate-vietnam',
        ],
        'Hanoi day trips NCHMF anchor' => [
            'nchmf.gov.vn',
        ],
        'Hanoi day trips transport anchor' => [
            'vietnam.travel/plan-your-trip/transport-within-vietnam',
        ],
        'Hanoi day trips Noi Bai anchor' => [
            'vietnamairport.vn/en/noi-bai-airport',
        ],
        'Hanoi day trips eVisa anchor' => [
            'evisa.gov.vn',
        ],
        'Hanoi day trips hero image credit' => [
            'Alex 69200 vx / CC BY-SA 4.0',
        ],
        'Hanoi day trips Trang An image credit' => [
            'Jakub Halun / CC BY 4.0',
        ],
        'Hanoi day trips Ha Long image credit' => [
            'Vyacheslav Argenberg / CC BY 4.0',
        ],
        'Hanoi day trips Bat Trang image credit' => [
            'Vuong Tri Binh / CC BY-SA 4.0',
        ],
        'Hanoi day trips Perfume Pagoda image credit' => [
            'Tango7174 / CC BY 3.0',
        ],
        'Hanoi day trips Ba Vi image credit' => [
            'Steven C. Price / CC BY-SA 4.0',
        ],
    ]
);

$required_hanoi_day_trips_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$hanoi_day_trips_guide_page = vg_verify_eeat_require_published_path(
    'destinations/best-day-trips-from-hanoi',
    'Best Day Trips from Hanoi',
    $failures
);

if ($hanoi_day_trips_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $hanoi_day_trips_guide_page,
        'Best Day Trips from Hanoi',
        'destinations/best-day-trips-from-hanoi',
        'Best Day Trips from Hanoi',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $hanoi_day_trips_guide_page,
        'Best Day Trips from Hanoi',
        $required_hanoi_day_trips_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $hanoi_day_trips_guide_page,
        'Best Day Trips from Hanoi',
        'rendered Hanoi day trips depth modules',
        [
            'Ninh Binh is the strongest default day trip from Hanoi only when it has a calm pickup, one boat landscape, one viewpoint or temple, and no rushed same-night transfer afterward.',
            'Ha Long or Lan Ha as a day trip is a premium logistics product first and a scenery product second.',
            'Bat Trang is not a replacement for Ninh Binh or the bay; it is the half-day craft answer when Hanoi needs texture without a punishing transfer.',
            'Duong Lam works when rural architecture and slow cultural context matter more than headline scenery.',
            'Perfume Pagoda is worth choosing only when the boat, pilgrimage rhythm, and cave-temple journey are the point.',
            'Ba Vi is a weather-sensitive nature day; choose it for cooler air, forest, and hiking, not for a guaranteed postcard view.',
            'Staying in Hanoi is the correct day-trip decision when the outside option only adds road time to an already thin capital chapter.',
            'transfer-to-experience ratio',
            'Use the middle full day, not arrival day or departure day',
            'A premium operator answer should name the pickup zone, vehicle standard, guide language, ticket inclusions, lunch plan, cancellation terms, and realistic return window.',
            'A tour that promises Ninh Binh plus Ha Long Bay in one day is selling geography compression, not quality.',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $hanoi_day_trips_guide_page,
        'Best Day Trips from Hanoi',
        'visible Hanoi day trips image credits',
        [
            'Hanoi-lac-hoan-kiem.jpg',
            'Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg',
            'Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg',
            'Bat_Trang_pottery_and_ceramics_village_in_2016_20.jpg',
            'VN_Chua_Huong5_tango7174.jpg',
            'Ba-Vi2-Vietnam.jpg',
            'Alex 69200 vx / CC BY-SA 4.0',
            'Jakub Halun / CC BY 4.0',
            'Vyacheslav Argenberg / CC BY 4.0',
            'Vuong Tri Binh / CC BY-SA 4.0',
            'Tango7174 / CC BY 3.0',
            'Steven C. Price / CC BY-SA 4.0',
        ],
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $hanoi_day_trips_guide_page,
        'Best Day Trips from Hanoi',
        'hero image credit metadata',
        [
            'Hero image: Hanoi Hoan Kiem Lake by Alex 69200 vx, CC BY-SA 4.0',
            'Trang An Landscape Complex, Ninh Binh Province by Jakub Halun, CC BY 4.0',
            'Ha Long Bay, Vietnam, View from above by Vyacheslav Argenberg, CC BY 4.0',
            'Bat Trang pottery and ceramics village in 2016 20 by Vuong Tri Binh, CC BY-SA 4.0',
            'VN Chua Huong5 tango7174 by Tango7174, CC BY 3.0',
            'Ba-Vi2-Vietnam by Steven C. Price, CC BY-SA 4.0',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $hanoi_day_trips_guide_page,
        'Best Day Trips from Hanoi',
        $required_hanoi_day_trips_guide_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hanoi_day_trips_guide_page,
        'Best Day Trips from Hanoi',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $hanoi_day_trips_guide_page,
        'Best Day Trips from Hanoi',
        $failures
    );

    vg_verify_eeat_require_visible_external_body_link_budget(
        $hanoi_day_trips_guide_page,
        'Best Day Trips from Hanoi',
        3,
        5,
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $hanoi_day_trips_guide_page,
        'Best Day Trips from Hanoi',
        [
            '/destinations/hanoi-travel-guide/' => 'Hanoi Travel Guide',
            '/destinations/where-to-stay-in-hanoi/' => 'Where to Stay in Hanoi',
            '/compare/old-quarter-vs-french-quarter-vs-west-lake/' => 'Old Quarter vs French Quarter vs West Lake',
            '/destinations/best-things-to-do-in-hanoi/' => 'Best Things to Do in Hanoi',
            '/destinations/ninh-binh-travel-guide/' => 'Ninh Binh Travel Guide',
            '/destinations/ha-long-bay-travel-guide/' => 'Ha Long Bay Travel Guide',
            '/compare/ha-long-bay-vs-lan-ha-bay/' => 'Ha Long Bay vs Lan Ha Bay',
            '/destinations/cat-ba-travel-guide/' => 'Cat Ba Travel Guide',
            '/destinations/bai-tu-long-bay-guide/' => 'Bai Tu Long Bay Guide',
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/compare/north-central-south-vietnam/' => 'North vs Central vs South Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/money-cash-cards-atms/' => 'Money in Vietnam',
            '/plan/sim-esim-vietnam/' => 'SIM and eSIM in Vietnam',
            '/plan/vietnam-evisa/' => 'Vietnam E-Visa',
            '/itineraries/7-days-in-vietnam/' => '7 Days in Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
        ],
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'destinations/hanoi-travel-guide' => 'Hanoi Travel Guide',
            'destinations/where-to-stay-in-hanoi' => 'Where to Stay in Hanoi',
            'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
            'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
            'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
            'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
            'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
            'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay Guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ],
        '/destinations/best-day-trips-from-hanoi/',
        'Best Day Trips from Hanoi',
        $failures
    );
}

$required_hcmc_stays_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'HCMC stay hero marker' => [
            'vg-hcmc-stays-hero:v1',
        ],
        'HCMC stay concierge verdict' => [
            'vg-hcmc-stays-concierge-verdict',
        ],
        'HCMC stay at a glance' => [
            'vg-hcmc-stays-at-a-glance:v1',
        ],
        'HCMC stay photo grid' => [
            'vg-hcmc-stays-photo-grid:v1',
        ],
        'HCMC stay source diversity' => [
            'vg-hcmc-stays-source-diversity:v1',
        ],
        'HCMC stay area verdict' => [
            'vg-hcmc-stays-area-verdict:v1',
        ],
        'HCMC stay first time fit' => [
            'vg-hcmc-stays-first-time-fit:v1',
        ],
        'HCMC stay noise map' => [
            'vg-hcmc-stays-noise-map:v1',
        ],
        'HCMC stay airport buffer' => [
            'vg-hcmc-stays-airport-buffer:v1',
        ],
        'HCMC stay day trip pickup' => [
            'vg-hcmc-stays-day-trip-pickup:v1',
        ],
        'HCMC stay traveler profiles' => [
            'vg-hcmc-stays-traveler-profiles:v1',
        ],
        'HCMC stay cost booking' => [
            'vg-hcmc-stays-cost-booking:v1',
        ],
        'HCMC stay safety comfort' => [
            'vg-hcmc-stays-safety-comfort:v1',
        ],
        'HCMC stay live checks' => [
            'vg-hcmc-stays-live-checks:v1',
        ],
        'HCMC stay FAQ' => [
            'vg-hcmc-stays-faq:v1',
        ],
        'HCMC stay support link' => [
            'Where to Stay in Ho Chi Minh City',
        ],
        'HCMC stay official destination anchor' => [
            'vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city',
        ],
        'HCMC stay official tourism portal anchor' => [
            'visithcmc.vn',
        ],
        'HCMC stay ACV Tan Son Nhat anchor' => [
            'vietnamairport.vn/en/tan-son-nhat-airport',
        ],
        'HCMC stay ACV airport profile anchor' => [
            'acv.vn/en/airports/tan-son-nhat-international-airport',
        ],
        'HCMC stay Vietnam Airlines airport guide anchor' => [
            'vietnamairlines.com/ca/en/plan-book/travel/travel-guide/airport-ho-chi-minh-to-city',
        ],
        'HCMC stay Metro Line 1 anchor' => [
            'commercial-operation-begins-on-hcmcs-first-metro-line',
        ],
        'HCMC stay weather anchor' => [
            'weather-and-climate-vietnam',
        ],
        'HCMC stay NCHMF anchor' => [
            'kttv/en-US/1/index.html',
        ],
        'HCMC stay eVisa anchor' => [
            'evisa.gov.vn',
        ],
        'HCMC stay hero image credit' => [
            'Steffen Schmitz / CC BY-SA 4.0',
        ],
        'HCMC stay Ben Thanh image credit' => [
            'Bahnfrend / CC BY-SA 4.0',
        ],
        'HCMC stay post office image credit' => [
            'Steffen Schmitz / CC BY-SA 4.0',
        ],
        'HCMC stay war museum image credit' => [
            'Prenn / CC BY-SA 3.0',
        ],
        'HCMC stay Bui Vien image credit' => [
            'Bahnfrend / CC BY-SA 4.0',
        ],
        'HCMC stay Landmark 81 image credit' => [
            'Josemite / CC BY-SA 4.0',
        ],
        'HCMC stay Thao Dien image credit' => [
            'ekkun / CC BY-SA 4.0',
        ],
        'HCMC stay airport image credit' => [
            'Luu Ly / CC BY 3.0',
        ],
    ]
);

$required_hcmc_stays_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$hcmc_stays_guide_page = vg_verify_eeat_require_published_path(
    'destinations/where-to-stay-in-ho-chi-minh-city',
    'Where to Stay in Ho Chi Minh City',
    $failures
);

if ($hcmc_stays_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $hcmc_stays_guide_page,
        'Where to Stay in Ho Chi Minh City',
        'destinations/where-to-stay-in-ho-chi-minh-city',
        'Where to Stay in Ho Chi Minh City',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $hcmc_stays_guide_page,
        'Where to Stay in Ho Chi Minh City',
        $required_hcmc_stays_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $hcmc_stays_guide_page,
        'Where to Stay in Ho Chi Minh City',
        'visible HCMC stay official sources',
        [
            'vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city',
            'visithcmc.vn',
            'vietnamairport.vn/en/tan-son-nhat-airport',
            'acv.vn/en/airports/tan-son-nhat-international-airport',
            'vietnamairlines.com/ca/en/plan-book/travel/travel-guide/airport-ho-chi-minh-to-city',
            'commercial-operation-begins-on-hcmcs-first-metro-line',
            'weather-and-climate-vietnam',
            'kttv/en-US/1/index.html',
            'evisa.gov.vn',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $hcmc_stays_guide_page,
        'Where to Stay in Ho Chi Minh City',
        'visible HCMC stay image credits',
        [
            'Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg',
            'Ben_Thanh_Market%2C_2023_%2803%29.jpg',
            'Ho_Chi_Minh_City%2C_Central_Post_Office%2C_2020-01_CN-01.jpg',
            'War_Remnants_Museum%2C_HCMC%2C_front.JPG',
            'Bui_Vien_Street%2C_Ho_Chi_Minh_City%2C_at_night%2C_2023_%2818%29.jpg',
            'Landmark_81_view_from_Saigon_River.jpg',
            'Thao_Dien_Station_%28MRT_1%29',
            'Tan_Son_Nhat_International_Airport.jpg',
            'Steffen Schmitz / CC BY-SA 4.0',
            'Bahnfrend / CC BY-SA 4.0',
            'Prenn / CC BY-SA 3.0',
            'Josemite / CC BY-SA 4.0',
            'ekkun / CC BY-SA 4.0',
            'Luu Ly / CC BY 3.0',
        ],
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $hcmc_stays_guide_page,
        'Where to Stay in Ho Chi Minh City',
        'hero image credit metadata',
        [
            'Hero and Nguyen Hue/City Hall image: Ho Chi Minh City, City Hall, 2020-01 CN-02 by Steffen Schmitz, CC BY-SA 4.0',
            'Ben Thanh Market, 2023 (03) by Bahnfrend, CC BY-SA 4.0',
            'Ho Chi Minh City Central Post Office by Steffen Schmitz, CC BY-SA 4.0',
            'War Remnants Museum, HCMC, front by Prenn, CC BY-SA 3.0',
            'Bui Vien Street, Ho Chi Minh City, at night, 2023 (18) by Bahnfrend, CC BY-SA 4.0',
            'Landmark 81 view from Saigon River by Josemite, CC BY-SA 4.0',
            'Thao Dien Station MRT 1 by ekkun, CC BY-SA 4.0',
            'Tan Son Nhat International Airport by Luu Ly, CC BY 3.0',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $hcmc_stays_guide_page,
        'Where to Stay in Ho Chi Minh City',
        $required_hcmc_stays_guide_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hcmc_stays_guide_page,
        'Where to Stay in Ho Chi Minh City',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $hcmc_stays_guide_page,
        'Where to Stay in Ho Chi Minh City',
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $hcmc_stays_guide_page,
        'Where to Stay in Ho Chi Minh City',
        [
            '/destinations/ho-chi-minh-city-travel-guide/' => 'Ho Chi Minh City Travel Guide',
            '/destinations/best-day-trips-from-ho-chi-minh-city/' => 'Best Day Trips from Ho Chi Minh City',
            '/destinations/mekong-delta-travel-guide/' => 'Mekong Delta Travel Guide',
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/destinations/best-places-to-visit-vietnam/' => 'Best Places to Visit in Vietnam',
            '/compare/north-central-south-vietnam/' => 'North vs Central vs South Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/money-cash-cards-atms/' => 'Money in Vietnam',
            '/plan/sim-esim-vietnam/' => 'SIM and eSIM in Vietnam',
            '/plan/vietnam-evisa/' => 'Vietnam E-Visa',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
        ],
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'destinations/ho-chi-minh-city-travel-guide' => 'Ho Chi Minh City Travel Guide',
            'destinations/best-day-trips-from-ho-chi-minh-city' => 'Best Day Trips from Ho Chi Minh City',
            'destinations/mekong-delta-travel-guide' => 'Mekong Delta Travel Guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ],
        '/destinations/where-to-stay-in-ho-chi-minh-city/',
        'Where to Stay in Ho Chi Minh City',
        $failures
    );
}

$required_hcmc_day_trips_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'HCMC day trips hero marker' => [
            'vg-hcmc-day-trips-hero:v1',
        ],
        'HCMC day trips concierge verdict' => [
            'vg-hcmc-day-trips-concierge-verdict',
        ],
        'HCMC day trips at a glance' => [
            'vg-hcmc-day-trips-at-a-glance:v1',
        ],
        'HCMC day trips photo grid' => [
            'vg-hcmc-day-trips-photo-grid:v1',
        ],
        'HCMC day trips source diversity' => [
            'vg-hcmc-day-trips-source-diversity:v1',
        ],
        'HCMC day trips decision grid' => [
            'vg-hcmc-day-trips-decision-grid:v1',
        ],
        'HCMC day trips quick chooser' => [
            'vg-hcmc-day-trips-quick-chooser:v1',
        ],
        'HCMC day trips route fit' => [
            'vg-hcmc-day-trips-route-fit:v1',
        ],
        'HCMC day trips route pairings' => [
            'vg-hcmc-day-trips-route-pairings:v1',
        ],
        'HCMC day trips day placement' => [
            'vg-hcmc-day-trips-day-placement:v1',
        ],
        'HCMC day trips transfer value scorecard' => [
            'vg-hcmc-day-trips-transfer-value-scorecard:v1',
        ],
        'HCMC day trips season weather' => [
            'vg-hcmc-day-trips-season-weather:v1',
        ],
        'HCMC day trips transport logistics' => [
            'vg-hcmc-day-trips-transport-logistics:v1',
        ],
        'HCMC day trips cost booking' => [
            'vg-hcmc-day-trips-cost-booking:v1',
        ],
        'HCMC day trips booking mode' => [
            'vg-hcmc-day-trips-booking-mode:v1',
        ],
        'HCMC day trips context ethics' => [
            'vg-hcmc-day-trips-context-ethics:v1',
        ],
        'HCMC day trips failure modes' => [
            'vg-hcmc-day-trips-failure-modes:v1',
        ],
        'HCMC day trips skip logic' => [
            'vg-hcmc-day-trips-skip-logic:v1',
        ],
        'HCMC day trips live checks' => [
            'vg-hcmc-day-trips-live-checks:v1',
        ],
        'HCMC day trips FAQ' => [
            'vg-hcmc-day-trips-faq:v1',
        ],
        'HCMC day trips official destination anchor' => [
            'vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city',
        ],
        'HCMC day trips Visit HCMC anchor' => [
            'visithcmc.vn',
        ],
        'HCMC day trips Cu Chi anchor' => [
            'map3d.visithcmc.vn/?startscene=scene_1_1_2_dia-dao-cu-chi_(1)',
        ],
        'HCMC day trips Can Gio anchor' => [
            'rung-sac-can-gio-diem-den-hoang-da-day-hap-dan',
        ],
        'HCMC day trips Tay Ninh anchor' => [
            'eng.tayninh.gov.vn/travel/tay-ninh-creates-breakthroughs-in-tourism-development-992982',
        ],
        'HCMC day trips Vung Tau anchor' => [
            'diemden.baria-vungtau.gov.vn/about/33-trai-nghiem-nhat-dinh-phai-thu-o-vung-tau',
        ],
        'HCMC day trips weather anchor' => [
            'vietnam.travel/things-to-do/weather-and-climate-vietnam',
        ],
        'HCMC day trips transport anchor' => [
            'vietnam.travel/plan-your-trip/transport-within-vietnam',
        ],
        'HCMC day trips ACV anchor' => [
            'acv.vn/en/airports/tan-son-nhat-international-airport',
        ],
        'HCMC day trips eVisa anchor' => [
            'evisa.gov.vn',
        ],
        'HCMC day trips hero image credit' => [
            'Steffen Schmitz / CC BY-SA 4.0',
        ],
        'HCMC day trips Cu Chi image credit' => [
            'Andre Hospers / CC BY-SA 4.0',
        ],
        'HCMC day trips Can Gio image credit' => [
            'Tho nau / CC BY-SA 3.0',
        ],
        'HCMC day trips Ba Den image credit' => [
            'Minh Ming / CC BY-SA 4.0',
        ],
        'HCMC day trips Mekong image credit' => [
            'Vyacheslav Argenberg / CC BY 4.0',
        ],
        'HCMC day trips Vung Tau image credit' => [
            'Hoangvantoanajc / CC BY-SA 3.0',
        ],
    ]
);

$required_hcmc_day_trips_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$hcmc_day_trips_guide_page = vg_verify_eeat_require_published_path(
    'destinations/best-day-trips-from-ho-chi-minh-city',
    'Best Day Trips from Ho Chi Minh City',
    $failures
);

if ($hcmc_day_trips_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $hcmc_day_trips_guide_page,
        'Best Day Trips from Ho Chi Minh City',
        'destinations/best-day-trips-from-ho-chi-minh-city',
        'Best Day Trips from Ho Chi Minh City',
        $failures
    );

        vg_verify_eeat_require_content_groups(
            $hcmc_day_trips_guide_page,
            'Best Day Trips from Ho Chi Minh City',
            $required_hcmc_day_trips_guide_content_groups,
            $failures
        );

        vg_verify_eeat_require_rendered_content_all(
            $hcmc_day_trips_guide_page,
            'Best Day Trips from Ho Chi Minh City',
            'rendered HCMC day trips depth modules',
            [
                'vg-hcmc-day-trips-route-snapshots:v1',
                'Recommended departure window',
                '06:30-07:30 departure, 3.5-5.5 hours door-to-door',
                '3-hour return buffer',
                'vg-hcmc-day-trips-quick-chooser:v1',
                'Two HCMC nights and no protected city day',
                'vg-hcmc-day-trips-route-pairings:v1',
                'the day-trip choice should stop duplicating that stronger chapter',
                'vg-hcmc-day-trips-day-placement:v1',
                'Use the middle full day, not arrival day or departure day',
                'vg-hcmc-day-trips-transfer-value-scorecard:v1',
                'transfer-to-experience ratio',
                'vg-hcmc-day-trips-booking-mode:v1',
                'transport-only is not the same as guided context',
                'vg-hcmc-day-trips-context-ethics:v1',
                'War-history context, wildlife expectations, and respectful pacing',
                'vg-hcmc-day-trips-failure-modes:v1',
                'A tour that promises Cu Chi, Mekong, lunch, shopping, and an early dinner return is selling compression, not quality.',
                'vg-hcmc-day-trips-sample-day-scripts:v1',
                '07:00 depart, 09:00 site context, 12:15 lunch/rest, 15:30 return decision, 18:00 flexible dinner',
                'If pickup slips by more than 30 minutes, cut the add-on stop instead of compressing the main site.',
                'vg-hcmc-day-trips-overnight-upgrade:v1',
                'Upgrade the Mekong from day trip to overnight when Cai Rang sunrise or a real river-town evening is the reason.',
            'vg-hcmc-day-trips-operator-checks:v1',
            'A premium operator answer should name the pickup district, guide language, ticket inclusions, meal plan, cancellation terms, and realistic return window.',
            'vg-hcmc-day-trips-comfort-fit:v1',
            'Families, older travelers, heat-sensitive travelers, and jet-lagged arrivals should choose by recovery cost, not just attraction fame.',
            'vg-hcmc-day-trips-food-rest:v1',
            'If lunch is described only as local restaurant with no timing or menu control, assume the operator is optimizing throughput.',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $hcmc_day_trips_guide_page,
        'Best Day Trips from Ho Chi Minh City',
        'rendered HCMC day trips official sources',
        [
            'vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city',
            'visithcmc.vn',
            'map3d.visithcmc.vn/?startscene=scene_1_1_2_dia-dao-cu-chi_(1)',
            'rung-sac-can-gio-diem-den-hoang-da-day-hap-dan',
            'tay-ninh-creates-breakthroughs-in-tourism-development-992982',
            '33-trai-nghiem-nhat-dinh-phai-thu-o-vung-tau',
            'weather-and-climate-vietnam',
            'kttv/en-US/1/index.html',
            'transport-within-vietnam',
            'acv.vn/en/airports/tan-son-nhat-international-airport',
            'evisa.gov.vn',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $hcmc_day_trips_guide_page,
        'Best Day Trips from Ho Chi Minh City',
        'visible HCMC day trips image credits',
        [
            'Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg',
            'Cu_Chi_Tunnels_Vietnam_war.jpg',
            'Can_Gio_mangrove_forest.jpg',
            'Ba_Den_cable_car_2019.jpg',
            'Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg',
            'H%E1%BA%A3i_%C4%91%C4%83ng_V%C5%A9ng_T%C3%A0u.JPG',
            'Steffen Schmitz / CC BY-SA 4.0',
            'Andre Hospers / CC BY-SA 4.0',
            'Tho nau / CC BY-SA 3.0',
            'Minh Ming / CC BY-SA 4.0',
            'Vyacheslav Argenberg / CC BY 4.0',
            'Hoangvantoanajc / CC BY-SA 3.0',
        ],
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $hcmc_day_trips_guide_page,
        'Best Day Trips from Ho Chi Minh City',
        'hero image credit metadata',
        [
            'Hero image: Ho Chi Minh City Hall by Steffen Schmitz, CC BY-SA 4.0',
            'Cu Chi Tunnels Vietnam war by Andre Hospers, CC BY-SA 4.0',
            'Can Gio mangrove forest by Tho nau, CC BY-SA 3.0',
            'Ba Den cable car 2019 by Minh Ming, CC BY-SA 4.0',
            'Vietnam, Phong Dien, Mekong Delta, River by Vyacheslav Argenberg, CC BY 4.0',
            'Vung Tau lighthouse by Hoangvantoanajc, CC BY-SA 3.0',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $hcmc_day_trips_guide_page,
        'Best Day Trips from Ho Chi Minh City',
        $required_hcmc_day_trips_guide_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $hcmc_day_trips_guide_page,
        'Best Day Trips from Ho Chi Minh City',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $hcmc_day_trips_guide_page,
        'Best Day Trips from Ho Chi Minh City',
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $hcmc_day_trips_guide_page,
        'Best Day Trips from Ho Chi Minh City',
        [
            '/destinations/ho-chi-minh-city-travel-guide/' => 'Ho Chi Minh City Travel Guide',
            '/destinations/mekong-delta-travel-guide/' => 'Mekong Delta Travel Guide',
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/compare/north-central-south-vietnam/' => 'North vs Central vs South Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/money-cash-cards-atms/' => 'Money in Vietnam',
            '/plan/sim-esim-vietnam/' => 'SIM and eSIM in Vietnam',
            '/plan/vietnam-evisa/' => 'Vietnam E-Visa',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
        ],
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'destinations/ho-chi-minh-city-travel-guide' => 'Ho Chi Minh City Travel Guide',
            'destinations/mekong-delta-travel-guide' => 'Mekong Delta Travel Guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ],
        '/destinations/best-day-trips-from-ho-chi-minh-city/',
        'Best Day Trips from Ho Chi Minh City',
        $failures
    );
}

$required_mekong_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Mekong hero marker' => [
            'vg-mekong-hero:v1',
        ],
        'Mekong concierge verdict' => [
            'vg-mekong-concierge-verdict',
        ],
        'Mekong at a glance' => [
            'vg-mekong-at-a-glance:v1',
        ],
        'Mekong photo grid' => [
            'vg-mekong-photo-grid:v1',
        ],
        'Mekong source diversity' => [
            'vg-mekong-source-diversity:v1',
        ],
        'Mekong day trip decision' => [
            'vg-mekong-day-trip:v1',
        ],
        'Mekong overnight base chooser' => [
            'vg-mekong-overnight:v1',
        ],
        'Mekong route fit' => [
            'vg-mekong-route-fit:v1',
        ],
        'Mekong season weather' => [
            'vg-mekong-season-weather:v1',
        ],
        'Mekong transport logistics' => [
            'vg-mekong-transport-logistics:v1',
        ],
        'Mekong cost booking' => [
            'vg-mekong-cost-booking:v1',
        ],
        'Mekong skip logic' => [
            'vg-mekong-skip-logic:v1',
        ],
        'Mekong live checks' => [
            'vg-mekong-live-checks:v1',
        ],
        'Mekong FAQ' => [
            'vg-mekong-faq:v1',
        ],
        'Mekong official Can Tho anchor' => [
            'vietnam.travel/places-to-go/southern-vietnam/can-tho',
        ],
        'Mekong official Chau Doc anchor' => [
            'vietnam.travel/places-to-go/southern-vietnam/chau-doc',
        ],
        'Mekong Can Tho tourism anchor' => [
            'tourismcantho.vn',
        ],
        'Mekong HCMC official anchor' => [
            'vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city',
        ],
        'Mekong weather anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Mekong NCHMF anchor' => [
            'kttv/en-US/1/index.html',
        ],
        'Mekong transport anchor' => [
            'transport-within-vietnam',
        ],
        'Mekong airport anchor' => [
            'vietnamairport.vn/en/can-tho-airport',
        ],
        'Mekong ACV airport anchor' => [
            'acv.vn/en/airports/can-tho-international-airport',
        ],
        'Mekong getting Vietnam anchor' => [
            'getting-vietnam',
        ],
        'Mekong eVisa anchor' => [
            'evisa.gov.vn',
        ],
        'Mekong hero image credit' => [
            'Vyacheslav Argenberg / CC BY 4.0',
        ],
        'Mekong Cai Rang image credit' => [
            'Christophe95 / CC BY-SA 4.0',
        ],
        'Mekong Can Tho image credit' => [
            'Andre Hospers / CC BY 4.0',
        ],
        'Mekong Ben Tre image credit' => [
            'Franzfoto / CC BY-SA 3.0',
        ],
        'Mekong My Tho image credit' => [
            'Esin Ustun / CC BY 2.0',
        ],
        'Mekong Tra Su image credit' => [
            'Nevillenguyen310 / CC BY-SA 4.0',
        ],
    ]
);

$required_mekong_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$mekong_guide_page = vg_verify_eeat_require_published_path(
    'destinations/mekong-delta-travel-guide',
    'Mekong Delta Travel Guide',
    $failures
);

if ($mekong_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $mekong_guide_page,
        'Mekong Delta Travel Guide',
        'destinations/mekong-delta-travel-guide',
        'Mekong Delta Travel Guide',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $mekong_guide_page,
        'Mekong Delta Travel Guide',
        $required_mekong_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $mekong_guide_page,
        'Mekong Delta Travel Guide',
        'visible Mekong official sources',
        [
            'vietnam.travel/places-to-go/southern-vietnam/can-tho',
            'vietnam.travel/places-to-go/southern-vietnam/chau-doc',
            'tourismcantho.vn',
            'vietnam.travel/places-to-go/southern-vietnam/ho-chi-minh-city',
            'weather-and-climate-vietnam',
            'kttv/en-US/1/index.html',
            'transport-within-vietnam',
            'vietnamairport.vn/en/can-tho-airport',
            'acv.vn/en/airports/can-tho-international-airport',
            'getting-vietnam',
            'evisa.gov.vn',
        ],
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $mekong_guide_page,
        'Mekong Delta Travel Guide',
        [
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/destinations/ho-chi-minh-city-travel-guide/' => 'Ho Chi Minh City Travel Guide',
            '/compare/north-central-south-vietnam/' => 'North vs Central vs South Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/plan/money-cash-cards-atms/' => 'Money in Vietnam',
            '/plan/sim-esim-vietnam/' => 'SIM and eSIM in Vietnam',
            '/plan/vietnam-evisa/' => 'Vietnam E-Visa',
            '/itineraries/7-days-in-vietnam/' => '7 Days in Vietnam',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/itineraries/21-days-in-vietnam/' => '21 Days in Vietnam',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
            '/destinations/phu-quoc-travel-guide/' => 'Phu Quoc Travel Guide',
            '/destinations/con-dao-travel-guide/' => 'Con Dao Travel Guide',
        ],
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $mekong_guide_page,
        'Mekong Delta Travel Guide',
        'visible Mekong image credits',
        [
            'Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg',
            'Cai_Rang_Floating_Market_1.jpg',
            'Can_Tho_Mekong_Delta_Virginia.jpg',
            'Ben_Tre_-Bootsverkehr_auf_dem_Mekong%2C_Anleger.jpg',
            'Canal_in_Mekong_Delta_-_My_Tho_-_Vietnam_%2815912635245%29.jpg',
            'Vietnam%2C_Panorama_of_Chau_Doc.jpg',
            'Wetland_vegetation_in_Tra_Su_Cajuput_Forest.jpg',
            'Vyacheslav Argenberg / CC BY 4.0',
            'Christophe95 / CC BY-SA 4.0',
            'Andre Hospers / CC BY 4.0',
            'Franzfoto / CC BY-SA 3.0',
            'Esin Ustun / CC BY 2.0',
            'Nevillenguyen310 / CC BY-SA 4.0',
        ],
        $failures
    );

    vg_verify_eeat_require_public_evidence_all(
        $mekong_guide_page,
        'Mekong Delta Travel Guide',
        'hero image credit metadata',
        [
            'Hero image: Vietnam, Phong Dien, Mekong Delta, River by Vyacheslav Argenberg, CC BY 4.0',
            'Cai Rang Floating Market 1 by Christophe95, CC BY-SA 4.0',
            'Can Tho Mekong Delta Virginia by Andre Hospers, CC BY 4.0',
            'Ben Tre - Bootsverkehr auf dem Mekong, Anleger by Franzfoto, CC BY-SA 3.0',
            'Canal in Mekong Delta - My Tho - Vietnam by Esin Ustun, CC BY 2.0',
            'Vietnam, Panorama of Chau Doc by Vyacheslav Argenberg, CC BY 4.0',
            'Wetland vegetation in Tra Su Cajuput Forest by Nevillenguyen310, CC BY-SA 4.0',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $mekong_guide_page,
        'Mekong Delta Travel Guide',
        $required_mekong_guide_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $mekong_guide_page,
        'Mekong Delta Travel Guide',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $mekong_guide_page,
        'Mekong Delta Travel Guide',
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/money-cash-cards-atms' => 'Money in Vietnam guide',
            'plan/sim-esim-vietnam' => 'SIM and eSIM in Vietnam guide',
            'plan/vietnam-evisa' => 'Vietnam E-Visa guide',
            'itineraries/7-days-in-vietnam' => '7 Days in Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'itineraries/21-days-in-vietnam' => '21 Days in Vietnam guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
            'destinations/ho-chi-minh-city-travel-guide' => 'Ho Chi Minh City Travel Guide',
            'destinations/phu-quoc-travel-guide' => 'Phu Quoc Travel Guide',
            'destinations/con-dao-travel-guide' => 'Con Dao Travel Guide',
        ],
        '/destinations/mekong-delta-travel-guide/',
        'Mekong Delta Travel Guide',
        $failures
    );
}

$required_cu_chi_mekong_comparison_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Cu Chi/Mekong hero marker' => [
            'vg-cu-chi-mekong-hero:v1',
        ],
        'Cu Chi/Mekong concierge verdict' => [
            'vg-cu-chi-mekong-concierge-verdict',
        ],
        'Cu Chi/Mekong at a glance' => [
            'vg-cu-chi-mekong-at-a-glance:v1',
        ],
        'Cu Chi/Mekong photo grid' => [
            'vg-cu-chi-mekong-photo-grid:v1',
        ],
        'Cu Chi/Mekong comparison matrix' => [
            'vg-cu-chi-mekong-comparison-matrix:v1',
        ],
        'Cu Chi/Mekong transfer ratio' => [
            'vg-cu-chi-mekong-transfer-ratio:v1',
        ],
        'Cu Chi/Mekong booking mode' => [
            'vg-cu-chi-mekong-booking-mode:v1',
        ],
        'Cu Chi/Mekong context filter' => [
            'vg-cu-chi-mekong-context-filter:v1',
        ],
        'Cu Chi/Mekong live checks' => [
            'vg-cu-chi-mekong-live-checks:v1',
        ],
        'Cu Chi/Mekong FAQ' => [
            'vg-cu-chi-mekong-faq:v1',
        ],
        'Cu Chi official source' => [
            'map3d.visithcmc.vn/?startscene=scene_1_1_2_dia-dao-cu-chi_(1)',
        ],
        'Cu Chi Vietnam.travel source' => [
            'vietnam.travel/things-to-do/7-must-see-attractions-hcmc',
        ],
        'Mekong official source' => [
            'vietnam.travel/places-to-go/mekong-delta',
        ],
        'Mekong day trip source' => [
            'day-tripping-in-the-mekong-delta',
        ],
        'Cu Chi image credit' => [
            'Andre Hospers / CC BY-SA 4.0',
        ],
        'Mekong image credit' => [
            'Vyacheslav Argenberg / CC BY 4.0',
        ],
        'HCMC image credit' => [
            'Steffen Schmitz / CC BY-SA 4.0',
        ],
    ]
);

$required_cu_chi_mekong_comparison_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$cu_chi_mekong_comparison_page = vg_verify_eeat_require_published_path(
    'compare/cu-chi-tunnels-vs-mekong-delta-day-trip',
    'Cu Chi Tunnels vs Mekong Delta Day Trip',
    $failures
);

if ($cu_chi_mekong_comparison_page instanceof WP_Post) {
    vg_verify_eeat_require_url_title_canonical_index(
        $cu_chi_mekong_comparison_page,
        'Cu Chi Tunnels vs Mekong Delta Day Trip',
        'compare/cu-chi-tunnels-vs-mekong-delta-day-trip',
        'Cu Chi Tunnels vs Mekong Delta Day Trip',
        $failures
    );

    vg_verify_eeat_require_content_groups(
        $cu_chi_mekong_comparison_page,
        'Cu Chi Tunnels vs Mekong Delta Day Trip',
        $required_cu_chi_mekong_comparison_content_groups,
        $failures
    );

    vg_verify_eeat_require_rendered_content_all(
        $cu_chi_mekong_comparison_page,
        'Cu Chi Tunnels vs Mekong Delta Day Trip',
        'rendered Cu Chi/Mekong decision depth',
        [
            'Do not combine them in one day',
            'Use Cu Chi when history is the reason for the day.',
            'Use the Mekong Delta when river rhythm is the reason for the day.',
            'Cu Chi is a short, high-friction history stop, not a full-day anchor.',
            'Operator quality matters more on Mekong because pickup timing, boat sequence, and lunch handling shape the day.',
            'Combining Cu Chi and Mekong is a compromise route, not the best version of either stop.',
            'If the route already has one strong river chapter, choose Cu Chi or stay in HCMC instead of compressing both.',
            'Can I do Cu Chi and the Mekong Delta in one day?',
            'What if neither Cu Chi nor Mekong feels right?',
        ],
        $failures
    );

    vg_verify_eeat_require_meta(
        $cu_chi_mekong_comparison_page,
        'Cu Chi Tunnels vs Mekong Delta Day Trip',
        $required_cu_chi_mekong_comparison_meta,
        $failures
    );

    vg_verify_eeat_require_raw_content(
        $cu_chi_mekong_comparison_page,
        'Cu Chi Tunnels vs Mekong Delta Day Trip',
        '[vg_related_routes]',
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $cu_chi_mekong_comparison_page,
        'Cu Chi Tunnels vs Mekong Delta Day Trip',
        $failures
    );

    vg_verify_eeat_require_own_related_route_meta(
        $cu_chi_mekong_comparison_page,
        'Cu Chi Tunnels vs Mekong Delta Day Trip',
        [
            '/destinations/best-day-trips-from-ho-chi-minh-city/' => 'Best Day Trips from Ho Chi Minh City',
            '/destinations/ho-chi-minh-city-travel-guide/' => 'Ho Chi Minh City Travel Guide',
            '/destinations/mekong-delta-travel-guide/' => 'Mekong Delta Travel Guide',
            '/plan/vietnam-travel-guide/' => 'Vietnam Travel Guide',
            '/compare/north-central-south-vietnam/' => 'North vs Central vs South Vietnam',
            '/plan/best-time-to-visit-vietnam/' => 'Best Time to Visit Vietnam',
            '/plan/transport-within-vietnam/' => 'Transport Within Vietnam',
            '/costs/vietnam-travel-cost/' => 'Vietnam Travel Cost',
            '/itineraries/10-days-in-vietnam/' => '10 Days in Vietnam',
            '/itineraries/14-days-in-vietnam/' => '14 Days in Vietnam',
            '/plan/health-travel-insurance-vietnam/' => 'Health and Travel Insurance for Vietnam',
            '/plan/safety-scams-vietnam/' => 'Safety and Scams in Vietnam',
        ],
        $failures
    );

    vg_verify_eeat_require_related_route_meta(
        [
            'destinations/best-day-trips-from-ho-chi-minh-city' => 'Best Day Trips from Ho Chi Minh City',
            'destinations/mekong-delta-travel-guide' => 'Mekong Delta Travel Guide',
            'destinations/ho-chi-minh-city-travel-guide' => 'Ho Chi Minh City Travel Guide',
            'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
            'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
            'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
            'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
            'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
            'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
            'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
            'plan/health-travel-insurance-vietnam' => 'Health and Travel Insurance for Vietnam guide',
            'plan/safety-scams-vietnam' => 'Safety and Scams in Vietnam guide',
        ],
        '/compare/cu-chi-tunnels-vs-mekong-delta-day-trip/',
        'Cu Chi Tunnels vs Mekong Delta Day Trip',
        $failures
    );
}

$required_con_dao_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Con Dao hero marker' => [
            'vg-con-dao-hero:v1',
        ],
        'Con Dao concierge verdict' => [
            'vg-con-dao-concierge-verdict',
        ],
        'Con Dao at a glance' => [
            'vg-con-dao-at-a-glance:v1',
        ],
        'Con Dao photo grid' => [
            'vg-con-dao-photo-grid:v1',
        ],
        'Con Dao source diversity' => [
            'vg-con-dao-source-diversity:v1',
        ],
        'Con Dao route fit' => [
            'vg-con-dao-route-fit:v1',
        ],
        'Con Dao where to stay' => [
            'vg-con-dao-where-to-stay:v1',
        ],
        'Con Dao priority map' => [
            'vg-con-dao-priority-map:v1',
        ],
        'Con Dao season weather' => [
            'vg-con-dao-season-weather:v1',
        ],
        'Con Dao transport logistics' => [
            'vg-con-dao-transport-logistics:v1',
        ],
        'Con Dao cost booking' => [
            'vg-con-dao-cost-booking:v1',
        ],
        'Con Dao skip logic' => [
            'vg-con-dao-skip-logic:v1',
        ],
        'Con Dao live checks' => [
            'vg-con-dao-live-checks:v1',
        ],
        'Con Dao FAQ' => [
            'vg-con-dao-faq:v1',
        ],
        'Con Dao official tourism anchor' => [
            'vietnam.travel/places-to-go/southern-vietnam/con-dao',
        ],
        'Con Dao slow travel anchor' => [
            'con-dao-sanctuary-sea-solitude-and-slow-travel',
        ],
        'Con Dao beach break anchor' => [
            'your-beach-break-guide-con-dao',
        ],
        'Con Dao history eco anchor' => [
            'explore-con-dao-heroic-history-and-eco-tourism-experiences',
        ],
        'Con Dao cuisine anchor' => [
            'con-dao-specialty-cuisine',
        ],
        'Con Dao weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Con Dao transport source anchor' => [
            'transport-within-vietnam',
        ],
        'Con Dao getting to Vietnam anchor' => [
            'getting-vietnam',
        ],
        'Con Dao visa source anchor' => [
            'visa-requirements',
        ],
        'Con Dao evisa source anchor' => [
            'evisa.gov.vn',
        ],
        'Con Dao National Park anchor' => [
            'condaopark.com.vn',
        ],
        'Con Dao portal anchor' => [
            'condao.com.vn',
        ],
        'Con Dao airport anchor' => [
            'acv.vn/en/vcs',
        ],
        'Con Dao Superdong anchor' => [
            'superdong.com.vn/dich-vu/soc-trang-con-dao',
        ],
        'Con Dao Phu Quoc Express experience anchor' => [
            'phuquocexpress.com/en/top-5-con-dao-travel-experiences-for-tourists',
        ],
        'Con Dao Phu Quoc Express cost anchor' => [
            'phuquocexpress.com/en/travel-costs-con-dao-from-a-to-z',
        ],
        'Con Dao NCHMF anchor' => [
            'nchmf.gov.vn',
        ],
        'Con Dao beach image credit' => [
            'Daeva Trac / CC BY-SA 4.0',
        ],
        'Con Dao park image credit' => [
            'Tycho / CC BY-SA 3.0',
        ],
        'Con Dao prison image credit' => [
            'Julian von Bredow / CC BY-SA 4.0',
        ],
        'Con Dao Phu Hai image credit' => [
            'Tuderna / CC BY 3.0',
        ],
    ]
);

$required_con_dao_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$con_dao_guide_page = vg_verify_eeat_require_published_path(
    'destinations/con-dao-travel-guide',
    'Con Dao Travel Guide',
    $failures
);

if ($con_dao_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $con_dao_guide_page,
        'Con Dao Travel Guide',
        $required_con_dao_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $con_dao_guide_page,
        'Con Dao Travel Guide',
        $required_con_dao_guide_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $con_dao_guide_page,
        'Con Dao Travel Guide',
        $failures
    );
}

$required_hoi_an_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Hoi An hero marker' => [
            'vg-best-things-hoi-an-hero:v1',
        ],
        'Hoi An concierge verdict' => [
            'vg-best-things-hoi-an-concierge-verdict',
        ],
        'Hoi An priority map' => [
            'vg-best-things-hoi-an-priority-map:v1',
        ],
        'Hoi An photo grid' => [
            'vg-best-things-hoi-an-photo-grid:v1',
        ],
        'Hoi An route fit' => [
            'vg-best-things-hoi-an-route-fit:v1',
        ],
        'Hoi An season pivots' => [
            'vg-best-things-hoi-an-season-pivots:v1',
        ],
        'Hoi An skip logic' => [
            'vg-best-things-hoi-an-skip-logic:v1',
        ],
        'Hoi An official checks' => [
            'vg-best-things-hoi-an-official-checks:v1',
        ],
        'Hoi An FAQ' => [
            'vg-best-things-hoi-an-faq:v1',
        ],
        'Hoi An Vietnam.travel source anchor' => [
            'vietnam.travel/places-to-go/central-vietnam/hoi-an',
        ],
        'Hoi An Central Vietnam source anchor' => [
            'vietnam.travel/places-to-go/central-vietnam',
        ],
        'Hoi An UNESCO source anchor' => [
            'whc.unesco.org/en/list/948',
        ],
        'Hoi An My Son UNESCO source anchor' => [
            'whc.unesco.org/en/list/949',
        ],
        'Hoi An weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Hoi An transport source anchor' => [
            'transport-within-vietnam',
        ],
        'Hoi An Da Nang comparison related route anchor' => [
            '/compare/da-nang-vs-hoi-an/',
        ],
        'Hoi An Ancient Town image credit' => [
            'Steffen Schmitz',
        ],
        'Hoi An Ancient Town image record' => [
            'commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg',
        ],
        'Hoi An Japanese Covered Bridge image credit' => [
            'rapidacid',
        ],
        'Hoi An Japanese Covered Bridge image record' => [
            'commons.wikimedia.org/wiki/File:Hoi_An,_Vietnam,_Japanese_Covered_Bridge.jpg',
        ],
        'Hoi An My Son image credit' => [
            'Philip Nalangan',
        ],
        'Hoi An My Son image record' => [
            'commons.wikimedia.org/wiki/File:My_Son_Sanctuary_Vietnam_06.jpg',
        ],
        'Hoi An An Bang Beach image credit' => [
            'Alexkom000',
        ],
        'Hoi An An Bang Beach image record' => [
            'commons.wikimedia.org/wiki/File:2024-11-23_An_Bang_Beach_in_Hoi_An_in_November.jpg',
        ],
    ]
);

$required_hoi_an_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$hoi_an_guide_page = vg_verify_eeat_require_published_path(
    'destinations/best-things-to-do-in-hoi-an',
    'Best Things to Do in Hoi An guide',
    $failures
);

if ($hoi_an_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $hoi_an_guide_page,
        'Best Things to Do in Hoi An guide',
        $required_hoi_an_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $hoi_an_guide_page,
        'Best Things to Do in Hoi An guide',
        $required_hoi_an_guide_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $hoi_an_guide_page,
        'Best Things to Do in Hoi An guide',
        $failures
    );
}

$required_hue_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'Hue hero marker' => [
            'vg-best-things-hue-hero:v1',
        ],
        'Hue concierge verdict' => [
            'vg-best-things-hue-concierge-verdict',
        ],
        'Hue priority map' => [
            'vg-best-things-hue-priority-map:v1',
        ],
        'Hue photo grid' => [
            'vg-best-things-hue-photo-grid:v1',
        ],
        'Hue route fit' => [
            'vg-best-things-hue-route-fit:v1',
        ],
        'Hue weather pivots' => [
            'vg-best-things-hue-weather-pivots:v1',
        ],
        'Hue skip logic' => [
            'vg-best-things-hue-skip-logic:v1',
        ],
        'Hue official checks' => [
            'vg-best-things-hue-official-checks:v1',
        ],
        'Hue FAQ' => [
            'vg-best-things-hue-faq:v1',
        ],
        'Hue Vietnam.travel source anchor' => [
            'vietnam.travel/places-to-go/central-vietnam/hue',
        ],
        'Hue Central Vietnam source anchor' => [
            'vietnam.travel/places-to-go/central-vietnam',
        ],
        'Hue UNESCO source anchor' => [
            'whc.unesco.org/en/list/678',
        ],
        'Hue Monuments official source anchor' => [
            'hueworldheritage.org.vn/en-us/Tourism-information/Price',
        ],
        'Hue weather source anchor' => [
            'weather-and-climate-vietnam',
        ],
        'Hue transport source anchor' => [
            'transport-within-vietnam',
        ],
        'Hue Hoi An related route anchor' => [
            'best-things-to-do-in-hoi-an',
        ],
        'Hue Citadel image credit' => [
            'CEphoto, Uwe Aranas',
        ],
        'Hue Citadel image record' => [
            'commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg',
        ],
        'Hue Thien Mu image record' => [
            'commons.wikimedia.org/wiki/File:Hue_Vietnam_Thien-Mu-Temple-and-Pagoda-03.jpg',
        ],
        'Hue Minh Mang image record' => [
            'commons.wikimedia.org/wiki/File:Hue_Vietnam_Tomb-of-Emperor-Minh-Mang-01.jpg',
        ],
        'Hue Perfume River image record' => [
            'commons.wikimedia.org/wiki/File:Hue_Vietnam_Perfume-River-01.jpg',
        ],
    ]
);

$required_hue_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$hue_guide_page = vg_verify_eeat_require_published_path(
    'destinations/best-things-to-do-in-hue',
    'Best Things to Do in Hue guide',
    $failures
);

if ($hue_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $hue_guide_page,
        'Best Things to Do in Hue guide',
        $required_hue_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $hue_guide_page,
        'Best Things to Do in Hue guide',
        $required_hue_guide_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $hue_guide_page,
        'Best Things to Do in Hue guide',
        $failures
    );
}

$required_itinerary_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        '10-day route photography' => [
            'vg-itinerary-10day-photo-grid:v1',
        ],
        '10-day route brief' => [
            'vg-itinerary-10day-at-a-glance:v1',
        ],
        '10-day planning flow' => [
            'vg-itinerary-10day-planning-flow:v1',
        ],
        '10-day route-family visual' => [
            'vg-itinerary-10day-route-family:v1',
        ],
        '10-day southern proof' => [
            'vg-itinerary-10day-southern-proof:v1',
        ],
        '10-day transfer feature' => [
            'vg-itinerary-10day-transfer-feature:v1',
        ],
        '10-day season pivots' => [
            'vg-itinerary-10day-season-pivots:v1',
        ],
        '10-day traveler fit' => [
            'vg-itinerary-10day-traveler-fit:v1',
        ],
        '10-day audience adaptations' => [
            'vg-itinerary-10day-audience-adaptations:v1',
        ],
        '10-day prebook versus flexible' => [
            'vg-itinerary-10day-prebook-flex:v1',
        ],
        '10-day mistake checks' => [
            'vg-itinerary-10day-mistakes:v1',
        ],
        '10-day booking sequence' => [
            'vg-itinerary-10day-booking-sequence:v1',
        ],
        '10-day planning audit' => [
            'vg-itinerary-10day-planning-audit:v1',
        ],
        'itinerary route matrix' => [
            'vg-itinerary-route-matrix',
        ],
        'itinerary day plan' => [
            'vg-itinerary-day-plan',
        ],
        'swap and skip decisions' => [
            'vg-itinerary-swap-skip',
        ],
        'before-booking checks' => [
            'vg-itinerary-booking-checks',
        ],
        'image credit for Hanoi' => [
            'Alex 69200 vx',
        ],
        'image credit for Ha Long Bay' => [
            'Vyacheslav Argenberg',
        ],
        'image credit for Trang An' => [
            'Jakub Halun',
        ],
        'image credit for Hue' => [
            'CEphoto',
            'Uwe Aranas',
        ],
        'image credit for Hoi An' => [
            'Steffen Schmitz',
        ],
        'image credit for Hai Van Pass' => [
            'Wolkenkratzer',
        ],
        'image credit for Ho Chi Minh City' => [
            'Ho Chi Minh City',
            'Steffen Schmitz',
        ],
        'image credit for Mekong' => [
            'Mekong Delta',
            'Vyacheslav Argenberg',
        ],
        '10-day Wikimedia source meta' => [
            'Wikimedia Commons image record',
        ],
        'official transport source anchor' => [
            'Transport within Vietnam',
            'transport-within-vietnam',
        ],
        '10-day July 16 meaningful update' => [
            'July 16, 2026',
        ],
    ]
);

$required_itinerary_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$required_7_day_itinerary_content_groups = array_merge(
    $required_guide_content_groups,
    [
        '7-day route brief' => [
            'vg-itinerary-7day-at-a-glance:v1',
        ],
        '7-day route photography' => [
            'vg-itinerary-7day-photo-grid:v1',
        ],
        '7-day planning flow' => [
            'vg-itinerary-7day-planning-flow:v1',
        ],
        '7-day route-family visual' => [
            'vg-itinerary-7day-route-family:v1',
        ],
        '7-day transfer pressure' => [
            'vg-itinerary-7day-transfer-pressure:v1',
        ],
        '7-day day plan' => [
            'vg-itinerary-7day-day-plan:v1',
        ],
        '7-day season pivots' => [
            'vg-itinerary-7day-season-pivots:v1',
        ],
        '7-day swap and skip' => [
            'vg-itinerary-7day-swap-skip:v1',
        ],
        '7-day prebook versus flexible' => [
            'vg-itinerary-7day-prebook-flex:v1',
        ],
        '7-day mistake checks' => [
            'vg-itinerary-7day-mistakes:v1',
        ],
        '7-day booking sequence' => [
            'vg-itinerary-7day-booking-sequence:v1',
        ],
        '7-day planning audit' => [
            'vg-itinerary-7day-planning-audit:v1',
        ],
        'one-week route discipline' => [
            'one focused region',
            'max two bases',
        ],
        'image credit for Hanoi' => [
            'Alex 69200 vx',
        ],
        'image credit for Ha Long Bay' => [
            'Vyacheslav Argenberg',
        ],
        'image credit for Trang An' => [
            'Jakub Halun',
        ],
        'image credit for Lan Ha' => [
            'Saaremees',
            'CC BY-SA 4.0',
        ],
        'image credit for Hoi An' => [
            'Steffen Schmitz',
        ],
        'image credit for Hue' => [
            'CEphoto',
            'Uwe Aranas',
        ],
        'official weather source anchor' => [
            'Weather and climate in Vietnam',
        ],
        'official transport source anchor' => [
            'Transport within Vietnam',
            'transport-within-vietnam',
        ],
        'official northern Vietnam source anchor' => [
            'Northern Vietnam',
        ],
        'official e-visa source anchor' => [
            'evisa.gov.vn',
        ],
        'official rail source anchor' => [
            'dsvn.vn',
        ],
        '7-day July 18 meaningful update' => [
            'July 18, 2026',
        ],
    ]
);

$itinerary_7_day_page = vg_verify_eeat_require_published_path(
    'itineraries/7-days-in-vietnam',
    '7 Days itinerary guide',
    $failures
);

if ($itinerary_7_day_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $itinerary_7_day_page,
        '7 Days itinerary guide',
        $required_7_day_itinerary_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $itinerary_7_day_page,
        '7 Days itinerary guide',
        $required_itinerary_guide_meta,
        $failures
    );

    vg_verify_eeat_require_internal_links_published(
        $itinerary_7_day_page,
        '7 Days itinerary guide',
        $failures
    );
}

vg_verify_eeat_require_related_route_meta(
    [
        'plan/vietnam-travel-guide' => 'Vietnam Travel Guide',
        'plan/best-time-to-visit-vietnam' => 'Best Time to Visit Vietnam guide',
        'plan/transport-within-vietnam' => 'Transport Within Vietnam guide',
        'costs/vietnam-travel-cost' => 'Vietnam Travel Cost guide',
        'compare/north-central-south-vietnam' => 'North vs Central vs South Vietnam guide',
        'destinations/best-places-to-visit-vietnam' => 'Best Places to Visit in Vietnam guide',
        'destinations/best-things-to-do-in-hanoi' => 'Best Things to Do in Hanoi guide',
        'destinations/ninh-binh-travel-guide' => 'Ninh Binh Travel Guide',
        'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay Travel Guide',
        'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay guide',
        'destinations/cat-ba-travel-guide' => 'Cat Ba Travel Guide',
        'destinations/best-things-to-do-in-hoi-an' => 'Best Things to Do in Hoi An guide',
        'compare/da-nang-vs-hoi-an' => 'Da Nang vs Hoi An guide',
        'itineraries/10-days-in-vietnam' => '10 Days in Vietnam guide',
        'itineraries/14-days-in-vietnam' => '14 Days in Vietnam guide',
    ],
    '/itineraries/7-days-in-vietnam/',
    '7 Days in Vietnam guide',
    $failures
);

$itinerary_guide_page = vg_verify_eeat_require_published_path(
    'itineraries/10-days-in-vietnam',
    '10 Days itinerary guide',
    $failures
);

if ($itinerary_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $itinerary_guide_page,
        '10 Days itinerary guide',
        $required_itinerary_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $itinerary_guide_page,
        '10 Days itinerary guide',
        $required_itinerary_guide_meta,
        $failures
    );
}

$required_14_day_itinerary_content_groups = array_merge(
    $required_guide_content_groups,
    [
        '14-day hero marker' => [
            'vg-itinerary-14day-hero:v1',
        ],
        '14-day concierge verdict' => [
            'vg-itinerary-14day-concierge-verdict',
        ],
        '14-day route brief' => [
            'vg-itinerary-14day-at-a-glance:v1',
        ],
        '14-day source diversity' => [
            'vg-itinerary-14day-source-diversity:v1',
        ],
        '14-day source trail snapshot' => [
            'vg-itinerary-14day-source-trail-snapshot:v1',
        ],
        '14-day route chooser' => [
            'vg-itinerary-14day-route-chooser:v1',
        ],
        '14-day route builder' => [
            'vg-itinerary-14day-route-builder',
        ],
        '14-day route photography' => [
            'vg-itinerary-14day-photo-grid',
        ],
        '14-day night allocation' => [
            'vg-itinerary-14day-night-allocation',
        ],
        '14-day extra days value' => [
            'vg-itinerary-14day-extra-days-value',
        ],
        '14-day pacing map' => [
            'vg-itinerary-14day-pacing-map',
        ],
        '14-day transfer pressure' => [
            'vg-itinerary-14day-transfer-pressure',
        ],
        '14-day route variants' => [
            'vg-itinerary-14day-route-variants',
        ],
        '14-day extension matrix' => [
            'vg-itinerary-14day-extension-matrix',
        ],
        '14-day base strategy' => [
            'vg-itinerary-14day-base-strategy',
        ],
        '14-day season pivots' => [
            'vg-itinerary-14day-season-pivots',
        ],
        '14-day slowdown rules' => [
            'vg-itinerary-14day-slowdown-rules',
        ],
        '14-day audience adaptations' => [
            'vg-itinerary-14day-audience-adaptations',
        ],
        '14-day prebook versus flexible table' => [
            'vg-itinerary-14day-prebook-flex',
        ],
        '14-day booking sequence' => [
            'vg-itinerary-14day-booking-sequence',
        ],
        '14-day planning audit' => [
            'vg-itinerary-14day-planning-audit',
        ],
        '14-day FAQ' => [
            'vg-itinerary-14day-faq',
        ],
        'image credit for Hanoi' => [
            'Alex 69200 vx',
        ],
        'image credit for Trang An' => [
            'Jakub Halun',
        ],
        'image credit for Hai Van Pass' => [
            'Wolkenkratzer',
        ],
        'image credit for Hoi An' => [
            'Steffen Schmitz',
        ],
        'image credit for Ho Chi Minh City' => [
            'Ho Chi Minh City',
        ],
        'image credit for Hue' => [
            'CEphoto',
            'Uwe Aranas',
        ],
        'image credit for Mekong' => [
            'Vyacheslav Argenberg',
        ],
        'image credit for Son River' => [
            'BacLuong',
        ],
        'official travel source anchor' => [
            'Vietnam.travel',
        ],
        'official transport source anchor' => [
            'Transport within Vietnam',
            'transport-within-vietnam',
        ],
        'unesco source anchor' => [
            'UNESCO',
            'whc.unesco.org',
        ],
        'updated Ha Long source label' => [
            'Ha Long Bay - Cat Ba Archipelago',
        ],
        'official rail source anchor' => [
            'dsvn.vn',
        ],
        'official e-visa source anchor' => [
            'evisa.gov.vn',
        ],
    ]
);

$itinerary_14_day_page = vg_verify_eeat_require_published_path(
    'itineraries/14-days-in-vietnam',
    '14 Days itinerary guide',
    $failures
);

if ($itinerary_14_day_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $itinerary_14_day_page,
        '14 Days itinerary guide',
        $required_14_day_itinerary_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $itinerary_14_day_page,
        '14 Days itinerary guide',
        $required_itinerary_guide_meta,
        $failures
    );
}

$required_evisa_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'official e-visa basics' => [
            'vg-evisa-official-basics',
        ],
        'e-visa decision table' => [
            'vg-evisa-decision-table',
        ],
        'e-visa mistake checks' => [
            'vg-evisa-mistake-checks',
        ],
        'before-apply checklist' => [
            'vg-evisa-before-apply',
        ],
        'official e-visa domain' => [
            'evisa.gov.vn',
        ],
        'alternate official e-visa domain' => [
            'thithucdientu.gov.vn',
        ],
        'ministry public service source' => [
            'dichvucong.bocongan.gov.vn',
        ],
        'single-entry fee anchor' => [
            'USD 25',
        ],
        'multiple-entry fee anchor' => [
            'USD 50',
        ],
        'processing target anchor' => [
            '3 working days',
        ],
    ]
);

$required_evisa_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$evisa_guide_page = vg_verify_eeat_require_published_path(
    'plan/vietnam-evisa',
    'Vietnam E-Visa guide',
    $failures
);

if ($evisa_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $evisa_guide_page,
        'Vietnam E-Visa guide',
        $required_evisa_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $evisa_guide_page,
        'Vietnam E-Visa guide',
        $required_evisa_guide_meta,
        $failures
    );
}

$required_cost_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'budget range marker' => [
            'vg-cost-budget-ranges',
        ],
        'basic daily budget range' => [
            'USD 30-45/day',
        ],
        'comfortable daily budget range' => [
            'USD 70-120/day',
        ],
        'premium daily budget range' => [
            'USD 180-350+/day',
        ],
        'budget range date' => [
            'July 14, 2026',
        ],
        'trip length scenario budgets' => [
            'vg-cost-scenario-budgets',
        ],
        'route cost photo feature' => [
            'vg-cost-photo-feature',
        ],
        'route cost photography' => [
            'vg-cost-photo-grid',
        ],
        'practical cost visuals' => [
            'vg-cost-practical-visuals',
        ],
        'route cost archetypes' => [
            'vg-cost-route-archetypes',
        ],
        'example budget builds' => [
            'vg-cost-example-builds',
        ],
        'line item budget builds' => [
            'vg-cost-line-item-builds',
        ],
        'traveler mix budget behavior' => [
            'vg-cost-traveler-mix',
        ],
        'hidden cost traps' => [
            'vg-cost-hidden-costs',
        ],
        'prebook versus flexible' => [
            'vg-cost-prebook-flex',
        ],
        'budget worksheet' => [
            'vg-cost-worksheet',
        ],
        'live booking checks' => [
            'vg-cost-live-checks',
        ],
        'cost faq' => [
            'vg-cost-faq',
        ],
        'official e-visa fee source anchor' => [
            'evisa.gov.vn',
        ],
        'exchange source anchor' => [
            'Vietcombank',
        ],
        'official rail source anchor' => [
            'dsvn.vn',
        ],
        'airport arrival source anchor' => [
            'Noi Bai',
        ],
        'Ho Chi Minh City airport source anchor' => [
            'Vietnam Airlines',
        ],
        'practical visual rail credit' => [
            'Alancrh',
        ],
        'practical visual airport credit' => [
            'Sky 269',
        ],
        'practical visual ATM credit' => [
            'Phan Minh Tuan',
        ],
    ]
);

$cost_guide_page = vg_verify_eeat_require_published_path(
    'costs/vietnam-travel-cost',
    'Vietnam Travel Cost guide',
    $failures
);

if ($cost_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $cost_guide_page,
        'Vietnam Travel Cost guide',
        $required_cost_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $cost_guide_page,
        'Vietnam Travel Cost guide',
        array_merge(
            $required_guide_meta,
            [
                'vg_eeat_hero_image_credit',
            ]
        ),
        $failures
    );
}

$required_money_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'money hero marker' => [
            'vg-money-hero:v1',
        ],
        'money concierge verdict' => [
            'vg-money-concierge-verdict',
        ],
        'currency basics' => [
            'vg-money-currency-basics:v1',
        ],
        'money photo grid' => [
            'vg-money-photo-grid:v1',
        ],
        'payment decision table' => [
            'vg-money-payment-decision-table:v1',
        ],
        'arrival cash plan' => [
            'vg-money-arrival-cash-plan:v1',
        ],
        'exchange ATM card table' => [
            'vg-money-exchange-atm-card:v1',
        ],
        'ATM checklist' => [
            'vg-money-atm-checklist:v1',
        ],
        'card acceptance matrix' => [
            'vg-money-card-acceptance:v1',
        ],
        'cash declaration' => [
            'vg-money-cash-declaration:v1',
        ],
        'safety mistakes' => [
            'vg-money-safety-mistakes:v1',
        ],
        'live checks' => [
            'vg-money-live-checks:v1',
        ],
        'FAQ' => [
            'vg-money-faq:v1',
        ],
        'cash and payment source anchor' => [
            'currency-and-payments-vietnam',
        ],
        'Vietnamese currency source anchor' => [
            'Vietnamese Currency',
        ],
        'exchange rates source anchor' => [
            'Vietcombank',
        ],
        'cash declaration source anchor' => [
            'Circular 15/2011/TT-NHNN',
            '5,000 USD',
            '15,000,000',
        ],
        'ATM locator source anchor' => [
            'Visa',
            'locator/atm',
        ],
    ]
);

$required_money_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$money_guide_page = vg_verify_eeat_require_published_path(
    'plan/money-cash-cards-atms',
    'Money in Vietnam guide',
    $failures
);

if ($money_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $money_guide_page,
        'Money in Vietnam guide',
        $required_money_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $money_guide_page,
        'Money in Vietnam guide',
        $required_money_guide_meta,
        $failures
    );
}

$required_sim_esim_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'SIM/eSIM hero marker' => [
            'vg-sim-esim-hero:v1',
        ],
        'SIM/eSIM concierge verdict' => [
            'vg-sim-esim-concierge-verdict',
        ],
        'SIM/eSIM basics' => [
            'vg-sim-esim-basics:v1',
        ],
        'SIM/eSIM photo grid' => [
            'vg-sim-esim-photo-grid:v1',
        ],
        'SIM/eSIM decision table' => [
            'vg-sim-esim-decision-table:v1',
        ],
        'SIM/eSIM compatibility check' => [
            'vg-sim-esim-compatibility-check:v1',
        ],
        'SIM/eSIM arrival setup' => [
            'vg-sim-esim-arrival-setup:v1',
        ],
        'SIM/eSIM coverage matrix' => [
            'vg-sim-esim-coverage-matrix:v1',
        ],
        'SIM/eSIM local number' => [
            'vg-sim-esim-local-number:v1',
        ],
        'SIM/eSIM hotspot data' => [
            'vg-sim-esim-hotspot-data:v1',
        ],
        'SIM/eSIM prebook flexible' => [
            'vg-sim-esim-prebook-flex:v1',
        ],
        'SIM/eSIM failure modes' => [
            'vg-sim-esim-failure-modes:v1',
        ],
        'SIM/eSIM live checks' => [
            'vg-sim-esim-live-checks:v1',
        ],
        'SIM/eSIM FAQ' => [
            'vg-sim-esim-faq:v1',
        ],
        'official tourism SIM source anchor' => [
            'Plan your trip',
            'SIM cards',
        ],
        'official airport source anchor' => [
            'travellers-guide-vietnams-airports',
        ],
        'official mobile internet source anchor' => [
            '110.5 million mobile internet subscriptions',
        ],
        'official carrier source anchor' => [
            'tourist.viettel.vn',
        ],
        'official eSIM carrier source anchor' => [
            'MobiFone',
            'eSIM',
        ],
        'device compatibility source anchor' => [
            'Apple Support',
            'Google Pixel Help',
        ],
        'SIM/eSIM airport image credit text' => [
            'Sky 269 / CC BY-SA 4.0',
        ],
        'SIM/eSIM nano SIM image credit text' => [
            'BwDraco / CC BY-SA 3.0',
        ],
        'SIM/eSIM Hai Van image credit text' => [
            'Wolkenkratzer / CC BY-SA 4.0',
        ],
    ]
);

$required_sim_esim_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$sim_esim_guide_page = vg_verify_eeat_require_published_path(
    'plan/sim-esim-vietnam',
    'SIM and eSIM in Vietnam guide',
    $failures
);

if ($sim_esim_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $sim_esim_guide_page,
        'SIM and eSIM in Vietnam guide',
        $required_sim_esim_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $sim_esim_guide_page,
        'SIM and eSIM in Vietnam guide',
        $required_sim_esim_guide_meta,
        $failures
    );
}

$required_transport_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'transport hero marker' => [
            'vg-transport-hero:v1',
        ],
        'transport concierge verdict' => [
            'vg-transport-concierge-verdict',
        ],
        'transport at a glance' => [
            'vg-transport-at-a-glance:v1',
        ],
        'transport photo grid' => [
            'vg-transport-photo-grid:v1',
        ],
        'transport route proof' => [
            'vg-transport-route-proof:v1',
        ],
        'transport corridor table' => [
            'vg-transport-corridor-table:v1',
        ],
        'transport mode matrix' => [
            'vg-transport-mode-matrix:v1',
        ],
        'transport prebook flexible' => [
            'vg-transport-prebook-flex:v1',
        ],
        'transport booking audit' => [
            'vg-transport-booking-audit:v1',
        ],
        'transport mistakes' => [
            'vg-transport-mistakes:v1',
        ],
        'transport FAQ' => [
            'vg-transport-faq:v1',
        ],
        'transport hero image credit text' => [
            'Wolkenkratzer / CC BY-SA 4.0',
        ],
        'transport Hanoi railway image credit text' => [
            'Alancrh / CC BY-SA 3.0',
        ],
        'transport Noi Bai airport image credit text' => [
            'Sky 269 / CC BY-SA 4.0',
        ],
        'transport Ha Long Bay image credit text' => [
            'Vyacheslav Argenberg / CC BY 4.0',
        ],
        'transport official transport source anchor' => [
            'transport-within-vietnam',
        ],
        'transport official airports source anchor' => [
            'travellers-guide-vietnams-airports',
        ],
        'transport official rail website source anchor' => [
            'vr.com.vn',
        ],
        'transport official rail ticket source anchor' => [
            'dsvn.vn',
        ],
        'transport official airport transfer source anchor' => [
            'noibaiairport.vn',
        ],
        'transport official city transfer source anchor' => [
            'Vietnam Airlines',
        ],
        'transport official Phu Quy source anchor' => [
            'Phu Quy',
        ],
        'transport official Nam Du source anchor' => [
            'Nam Du',
        ],
        'transport Ha Long Bay image source anchor' => [
            'Ha_Long_Bay',
        ],
    ]
);

$required_transport_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$transport_guide_page = vg_verify_eeat_require_published_path(
    'plan/transport-within-vietnam',
    'Transport Within Vietnam guide',
    $failures
);

if ($transport_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $transport_guide_page,
        'Transport Within Vietnam guide',
        $required_transport_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $transport_guide_page,
        'Transport Within Vietnam guide',
        $required_transport_guide_meta,
        $failures
    );
}

$required_safety_scams_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'safety hero marker' => [
            'vg-safety-scams-hero:v1',
        ],
        'safety concierge verdict' => [
            'vg-safety-scams-concierge-verdict',
        ],
        'safety risk map' => [
            'vg-safety-scams-risk-map:v1',
        ],
        'safety photo grid' => [
            'vg-safety-scams-photo-grid:v1',
        ],
        'safety city petty crime' => [
            'vg-safety-scams-city-petty-crime:v1',
        ],
        'safety transport road' => [
            'vg-safety-scams-transport-road:v1',
        ],
        'safety water nightlife' => [
            'vg-safety-scams-water-nightlife:v1',
        ],
        'safety emergency plan' => [
            'vg-safety-scams-emergency-plan:v1',
        ],
        'safety live checks' => [
            'vg-safety-scams-live-checks:v1',
        ],
        'safety FAQ' => [
            'vg-safety-scams-faq:v1',
        ],
        'GOV.UK safety source anchor' => [
            'foreign-travel-advice/vietnam/safety-and-security',
        ],
        'Canada safety source anchor' => [
            'travel.gc.ca/destinations/vietnam',
        ],
        'CDC health source anchor' => [
            'wwwnc.cdc.gov/travel/destinations/traveler/none/vietnam',
        ],
        'Vietnam.travel health safety source anchor' => [
            'vietnam.travel/plan-your-trip/health-safety',
        ],
        'Vietnam.travel transport source anchor' => [
            'vietnam.travel/plan-your-trip/transport-within-vietnam',
        ],
        'safety airport image credit text' => [
            'Sky 269 / CC BY-SA 4.0',
        ],
        'safety market image credit text' => [
            'Bahnfrend / CC BY-SA 4.0',
        ],
        'safety ATM image credit text' => [
            'Phan Minh Tuan / CC BY-SA 4.0',
        ],
        'safety Hai Van image credit text' => [
            'Wolkenkratzer / CC BY-SA 4.0',
        ],
    ]
);

$required_safety_scams_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$safety_scams_guide_page = vg_verify_eeat_require_published_path(
    'plan/safety-scams-vietnam',
    'Safety and Scams in Vietnam guide',
    $failures
);

if ($safety_scams_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $safety_scams_guide_page,
        'Safety and Scams in Vietnam guide',
        $required_safety_scams_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $safety_scams_guide_page,
        'Safety and Scams in Vietnam guide',
        $required_safety_scams_guide_meta,
        $failures
    );
}

$required_health_insurance_guide_content_groups = array_merge(
    $required_guide_content_groups,
    [
        'health insurance hero marker' => [
            'vg-health-insurance-hero:v1',
        ],
        'health insurance concierge verdict' => [
            'vg-health-insurance-concierge-verdict',
        ],
        'health insurance at a glance' => [
            'vg-health-insurance-at-a-glance:v1',
        ],
        'health insurance photo grid' => [
            'vg-health-insurance-photo-grid:v1',
        ],
        'health insurance coverage table' => [
            'vg-health-insurance-coverage-table:v1',
        ],
        'health insurance route risk map' => [
            'vg-health-insurance-route-risk-map:v1',
        ],
        'health insurance emergency plan' => [
            'vg-health-insurance-emergency-plan:v1',
        ],
        'health insurance pretravel checks' => [
            'vg-health-insurance-pretravel-checks:v1',
        ],
        'health insurance policy exclusions' => [
            'vg-health-insurance-policy-exclusions:v1',
        ],
        'health insurance live checks' => [
            'vg-health-insurance-live-checks:v1',
        ],
        'health insurance FAQ' => [
            'vg-health-insurance-faq:v1',
        ],
        'medical disclaimer and clinician framing' => [
            'qualified clinician',
            'travel clinic',
        ],
        'emergency medical coverage anchor' => [
            'Emergency medical care',
            'medical evacuation',
            'repatriation',
        ],
        'policy exclusion anchor' => [
            'Motorbike',
            'pre-existing conditions',
        ],
        'CDC health source anchor' => [
            'wwwnc.cdc.gov/travel/destinations/traveler/none/vietnam',
        ],
        'Vietnam.travel health safety source anchor' => [
            'vietnam.travel/plan-your-trip/health-safety',
        ],
        'GOV.UK health source anchor' => [
            'foreign-travel-advice/vietnam/health',
        ],
        'Canada health source anchor' => [
            'travel.gc.ca/destinations/vietnam',
        ],
        'Smartraveller health source anchor' => [
            'smartraveller.gov.au/destinations/asia/vietnam',
        ],
        'health insurance airport image credit text' => [
            'Sky 269 / CC BY-SA 4.0',
        ],
        'health insurance hospital image credit text' => [
            'Keronii / CC0',
        ],
        'health insurance FV hospital image credit text' => [
            'Nguyen Thanh Quang / CC BY-SA 3.0',
        ],
        'health insurance ambulance image credit text' => [
            'Dickelbers / CC BY-SA 4.0',
        ],
    ]
);

$required_health_insurance_guide_meta = array_merge(
    $required_guide_meta,
    [
        'vg_eeat_hero_image_credit',
    ]
);

$health_insurance_guide_page = vg_verify_eeat_require_published_path(
    'plan/health-travel-insurance-vietnam',
    'Health and Travel Insurance for Vietnam guide',
    $failures
);

if ($health_insurance_guide_page instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $health_insurance_guide_page,
        'Health and Travel Insurance for Vietnam guide',
        $required_health_insurance_guide_content_groups,
        $failures
    );

    vg_verify_eeat_require_meta(
        $health_insurance_guide_page,
        'Health and Travel Insurance for Vietnam guide',
        $required_health_insurance_guide_meta,
        $failures
    );
}

$costs_hub = vg_verify_eeat_require_published_path('costs', 'Costs hub', $failures);

if ($costs_hub instanceof WP_Post) {
    vg_verify_eeat_require_content_groups(
        $costs_hub,
        'Costs hub',
        [
            'money costs hub note' => [
                'vg-money-costs-hub-note:v1',
            ],
        ],
        $failures
    );
}

foreach (
    [
        'Best Time guide' => $best_time_page ?? null,
        'Vietnam Travel Guide' => $travel_guide_page ?? null,
        'North vs Central vs South Vietnam guide' => $region_comparison_page ?? null,
        'Best Places to Visit in Vietnam guide' => $destination_guide_page ?? null,
        'UNESCO Heritage Sites in Vietnam guide' => $heritage_guide_page ?? null,
        'Best Things to Do in Hanoi guide' => $hanoi_guide_page ?? null,
        'Hanoi Travel Guide' => $hanoi_travel_guide_page ?? null,
        'Where to Stay in Hanoi' => $hanoi_stays_guide_page ?? null,
        'Old Quarter vs French Quarter vs West Lake' => $hanoi_neighborhood_comparison_page ?? null,
        'Hanoi Airport to Old Quarter' => $hanoi_airport_transfer_page ?? null,
        'Hanoi in 2 Days' => $hanoi_two_days_page ?? null,
        'Best Day Trips from Hanoi' => $hanoi_day_trips_guide_page ?? null,
        'Ninh Binh Travel Guide' => $ninh_binh_guide_page ?? null,
        'Ha Long Bay vs Lan Ha Bay guide' => $ha_long_lan_ha_comparison_page ?? null,
        'Da Nang vs Hoi An guide' => $da_nang_hoi_an_comparison_page ?? null,
        'Hoi An vs Hue comparison guide' => $hoi_an_hue_comparison_page ?? null,
        'Da Nang Travel Guide' => $da_nang_guide_page ?? null,
        'Ha Long Bay Travel Guide' => $ha_long_bay_guide_page ?? null,
        'Cat Ba Travel Guide' => $cat_ba_guide_page ?? null,
        'Bai Tu Long Bay Guide' => $bai_tu_long_guide_page ?? null,
        'Best Beaches in Vietnam guide' => $best_beaches_guide_page ?? null,
        'Best Islands in Vietnam guide' => $best_islands_guide_page ?? null,
        'Quy Nhon Travel Guide' => $quy_nhon_guide_page ?? null,
        'Ly Son Travel Guide' => $ly_son_guide_page ?? null,
        'Ho Chi Minh City Travel Guide' => $hcmc_guide_page ?? null,
        'Where to Stay in Ho Chi Minh City' => $hcmc_stays_guide_page ?? null,
        'Phu Quoc Travel Guide' => $phu_quoc_guide_page ?? null,
        'Phu Quoc vs Nha Trang guide' => $phu_quoc_nha_trang_comparison_page ?? null,
        'Cu Chi Tunnels vs Mekong Delta Day Trip guide' => $cu_chi_mekong_comparison_page ?? null,
        'Best Things to Do in Hoi An guide' => $hoi_an_guide_page ?? null,
        'Best Things to Do in Hue guide' => $hue_guide_page ?? null,
        '7 Days itinerary guide' => $itinerary_7_day_page ?? null,
        '10 Days itinerary guide' => $itinerary_guide_page ?? null,
        '14 Days itinerary guide' => $itinerary_14_day_page ?? null,
        '21 Days itinerary guide' => $itinerary_21_day_page ?? null,
        'Vietnam E-Visa guide' => $evisa_guide_page ?? null,
        'Vietnam Travel Cost guide' => $cost_guide_page ?? null,
        'Money in Vietnam guide' => $money_guide_page ?? null,
        'SIM and eSIM in Vietnam guide' => $sim_esim_guide_page ?? null,
        'Transport Within Vietnam guide' => $transport_guide_page ?? null,
        'Safety and Scams in Vietnam guide' => $safety_scams_guide_page ?? null,
        'Health and Travel Insurance for Vietnam guide' => $health_insurance_guide_page ?? null,
    ] as $affiliate_label => $affiliate_page
) {
    if ($affiliate_page instanceof WP_Post) {
        vg_verify_eeat_require_affiliate_consistency($affiliate_page, $affiliate_label, $failures);
    }
}

vg_verify_eeat_finish($failures);

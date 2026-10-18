<?php
if (! defined('ABSPATH')) {
    exit;
}

function vg_guide_pilot_paths(): array
{
    return [
        'destinations/ho-chi-minh-city-travel-guide',
        'itineraries/10-days-in-vietnam',
        'itineraries/7-days-in-vietnam',
        'itineraries/14-days-in-vietnam',
        'itineraries/21-days-in-vietnam',
        'itineraries/hanoi-in-2-days',
        'compare/ha-long-bay-vs-lan-ha-bay',
        'plan/vietnam-evisa',
        'compare/cu-chi-tunnels-vs-mekong-delta-day-trip',
        'compare/da-nang-vs-hoi-an',
        'compare/hoi-an-vs-hue',
        'compare/mui-ne-vs-nha-trang',
        'compare/ninh-binh-day-trip-vs-overnight',
        'compare/north-central-south-vietnam',
        'compare/old-quarter-vs-french-quarter-vs-west-lake',
        'compare/phu-quoc-vs-nha-trang',
        'compare/trang-an-vs-tam-coc',
        'destinations/unesco-heritage-sites-vietnam',
        'destinations/best-beaches-in-vietnam',
        'destinations/best-places-to-visit-vietnam',
        'destinations/best-things-to-do-in-hanoi',
        'destinations/best-things-to-do-in-hoi-an',
        'destinations/best-things-to-do-in-hue',
        'destinations/ninh-binh-travel-guide',
        'destinations/ha-long-bay-travel-guide',
        'destinations/cat-ba-travel-guide',
        'destinations/bai-tu-long-bay-guide',
        'destinations/da-nang-travel-guide',
        'destinations/best-islands-in-vietnam',
        'destinations/phu-quoc-travel-guide',
        'destinations/con-dao-travel-guide',
        'destinations/nha-trang-travel-guide',
        'destinations/quy-nhon-travel-guide',
        'destinations/cham-islands-travel-guide',
        'destinations/ly-son-travel-guide',
        'destinations/mekong-delta-travel-guide',
        'destinations/best-day-trips-from-ho-chi-minh-city',
        'destinations/where-to-stay-in-ho-chi-minh-city',
        'destinations/hanoi-travel-guide',
        'destinations/where-to-stay-in-hanoi',
        'destinations/best-day-trips-from-hanoi',
        'destinations/where-to-stay-in-ninh-binh',
        'destinations/tam-coc-travel-guide',
        'plan/best-time-to-visit-vietnam',
        'plan/vietnam-travel-guide',
        'plan/transport-within-vietnam',
        'plan/money-cash-cards-atms',
        'plan/sim-esim-vietnam',
        'plan/safety-scams-vietnam',
        'plan/health-travel-insurance-vietnam',
        'plan/hanoi-airport-to-old-quarter',
        'plan/hanoi-to-ninh-binh-transport',
        'plan/ninh-binh-to-ha-long-bay-transfer',
        'destinations/ha-giang-loop-planning-guide',
        'destinations/sapa-travel-guide',
        'plan/hanoi-to-ha-giang-transport',
        'plan/hanoi-to-sapa-transport',
        'compare/sapa-vs-ha-giang',
        'destinations/hoi-an-ancient-town-guide',
        'destinations/hue-imperial-city-guide',
        'destinations/da-nang-beaches-guide',
        'destinations/phong-nha-travel-guide',
        'compare/hanoi-vs-ho-chi-minh-city',
        'compare/mekong-delta-overnight-vs-day-trip',
        'plan/best-vietnam-routes-first-time-visitors',
        'plan/vietnam-in-december',
        'plan/vietnam-in-january',
        'plan/vietnam-in-february',
        'plan/tet-in-vietnam-travel-guide',
        'plan/vietnam-rainy-season-flexible-route',
        'plan/vietnam-first-trip-planning-checklist',
        'plan/what-to-pack-for-vietnam-region-season',
        'plan/vietnam-airport-arrival-checklist',
        'plan/vietnam-food-safety-street-food-etiquette',
        'plan/where-to-stay-in-vietnam-base-decisions',
        'plan/hanoi-first-time-visitor-mistakes',
        'plan/ninh-binh-without-rushing',
        'plan/ha-long-bay-cruise-questions-before-booking',
        'plan/best-vietnam-cities-for-first-time-visitors',
        'plan/ha-giang-safety-guide',
        'compare/ha-giang-easy-rider-vs-self-drive',
        'plan/sapa-trekking-guided-vs-self-guided',
        'destinations/where-to-stay-in-sapa',
        'plan/best-time-for-northern-vietnam',
        'compare/vietnam-rice-terraces-guide',
        'destinations/mu-cang-chai-travel-guide',
        'destinations/pu-luong-travel-guide',
    ];
}

function vg_guide_pilot_paths_map(): array
{
    static $map = null;
    if ($map === null) {
        $map = array_fill_keys(vg_guide_pilot_paths(), true);
    }
    return $map;
}

/**
 * Global telemetry storage and persistent warning log for route fallbacks.
 */
function vg_record_route_fallback(string $path, string $reason): void
{
    $entry = [
        'path' => $path,
        'reason' => $reason,
        'timestamp' => microtime(true),
    ];
    $GLOBALS['vg_route_fallback_telemetry'] = $GLOBALS['vg_route_fallback_telemetry'] ?? [];
    $GLOBALS['vg_route_fallback_telemetry'][] = $entry;

    $sanitizedPath = str_replace(["\r", "\n", '"'], [' ', ' ', "'"], $path);
    $sanitizedReason = str_replace(["\r", "\n", '"'], [' ', ' ', "'"], $reason);
    $formattedMessage = sprintf(
        '[VietnamGuide Route Fallback] path="%s" reason="%s" ts=%.6f',
        $sanitizedPath,
        $sanitizedReason,
        $entry['timestamp']
    );
    $GLOBALS['vg_route_fallback_last_log_message'] = $formattedMessage;

    if (function_exists('do_action')) {
        do_action('vg_route_fallback_recorded', $entry, $formattedMessage);
    }

    $customLogFile = defined('VG_ROUTE_FALLBACK_LOG_FILE') && is_string(VG_ROUTE_FALLBACK_LOG_FILE)
        ? VG_ROUTE_FALLBACK_LOG_FILE
        : '';
    if ($customLogFile !== '') {
        @error_log($formattedMessage . PHP_EOL, 3, $customLogFile);
    } elseif (! defined('VG_SUPPRESS_FALLBACK_ERROR_LOG') || ! VG_SUPPRESS_FALLBACK_ERROR_LOG) {
        if (PHP_SAPI !== 'cli' || (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG)) {
            @error_log($formattedMessage);
        }
    }
}

function vg_get_route_fallback_telemetry(): array
{
    return $GLOBALS['vg_route_fallback_telemetry'] ?? [];
}

/**
 * Loads the shared route registry without changing the current pilot contract.
 *
 * The registry is shipped inside the theme so the runtime does not depend on
 * the repository's ops directory. Invalid or unavailable registry data fails
 * closed; the existing literal pilot list remains the compatibility fallback.
 */
function vg_guide_route_registry(): array
{
    static $registry = null;
    if ($registry !== null) {
        return $registry;
    }

    $registry = [];
    $registryPath = get_theme_file_path('/inc/guide-route-registry.json');
    if (! is_readable($registryPath)) {
        return $registry;
    }

    $rolloutPath = __DIR__ . '/guide-rollout.php';
    if (! function_exists('vg_get_compiled_route_registry_payload') && is_readable($rolloutPath)) {
        require_once $rolloutPath;
    }
    if (
        (! defined('VG_DISABLE_COMPILED_ROUTE_REGISTRY') || ! VG_DISABLE_COMPILED_ROUTE_REGISTRY)
        && function_exists('vg_get_compiled_route_registry_payload')
    ) {
        $compiled = vg_get_compiled_route_registry_payload();
        $isCanonicalRegistryPath = @realpath($registryPath) === @realpath(__DIR__ . '/guide-route-registry.json');
        if (
            is_array($compiled)
            && ($compiled['schema_version'] ?? null) === 1
            && isset($compiled['route_count'], $compiled['json_size'], $compiled['json_sha256'], $compiled['routes'])
            && is_int($compiled['route_count'])
            && $compiled['route_count'] >= 87
            && is_int($compiled['json_size'])
            && is_string($compiled['json_sha256'])
            && is_array($compiled['routes'])
            && count($compiled['routes']) === $compiled['route_count']
            && @filesize($registryPath) === $compiled['json_size']
            && (
                ($isCanonicalRegistryPath && (! defined('VG_VERIFY_COMPILED_REGISTRY_HASH') || ! VG_VERIFY_COMPILED_REGISTRY_HASH))
                || @hash_file('sha256', $registryPath) === $compiled['json_sha256']
            )
        ) {
            $registry = $compiled['routes'];
            return $registry;
        }
    }

    $raw = file_get_contents($registryPath);
    if (! is_string($raw) || trim($raw) === '') {
        return $registry;
    }

    $payload = json_decode($raw, true);
    if (
        ! is_array($payload)
        || ($payload['schema_version'] ?? null) !== 1
        || ! isset($payload['route_count'])
        || ! is_int($payload['route_count'])
        || $payload['route_count'] < 87
        || ! isset($payload['routes'])
        || ! is_array($payload['routes'])
        || count($payload['routes']) !== $payload['route_count']
    ) {
        return $registry;
    }

    $expectedRouteCount = $payload['route_count'];

    $allowedTypes = ['destination', 'itinerary', 'comparison', 'practical'];
    $routeTypesByPrefix = [
        'destinations' => 'destination',
        'itineraries' => 'itinerary',
        'compare' => 'comparison',
        'plan' => 'practical',
    ];
    $allowedStatuses = ['published', 'draft', 'archived'];
    $allowedTemplates = ['guide', 'legacy', 'hub', 'tool'];

    foreach ($payload['routes'] as $record) {
        if (! is_array($record)) {
            continue;
        }

        $path = isset($record['path']) && is_string($record['path'])
            ? trim($record['path'], '/')
            : '';
        $type = isset($record['type']) && is_string($record['type'])
            ? trim($record['type'])
            : '';
        $status = isset($record['status']) && is_string($record['status'])
            ? trim($record['status'])
            : '';
        $template = isset($record['template']) && is_string($record['template'])
            ? trim($record['template'])
            : '';
        $currentTemplate = isset($record['current_template']) && is_string($record['current_template'])
            ? trim($record['current_template'])
            : '';
        $title = isset($record['title']) && is_string($record['title'])
            ? trim($record['title'])
            : '';
        $description = isset($record['description']) && is_string($record['description'])
            ? trim($record['description'])
            : '';
        $parent = isset($record['parent']) && is_string($record['parent'])
            ? trim($record['parent'])
            : '';
        $lastReviewed = isset($record['last_reviewed']) && is_string($record['last_reviewed'])
            ? trim($record['last_reviewed'])
            : '';
        $source = isset($record['source']) && is_string($record['source'])
            ? trim($record['source'])
            : '';
        $contentOwner = isset($record['content_owner']) && is_string($record['content_owner'])
            ? trim($record['content_owner'])
            : '';
        $segments = $path === '' ? [] : explode('/', $path);
        $hasAmbiguousSegment = $segments === [] || in_array('', $segments, true)
            || in_array('.', $segments, true) || in_array('..', $segments, true);
        $pathPrefix = $segments[0] ?? '';
        $typeMatchesPath = isset($segments[1], $routeTypesByPrefix[$pathPrefix])
            && $routeTypesByPrefix[$pathPrefix] === $type;

        if (
            $path === ''
            || str_contains($path, '\\')
            || str_contains($path, '?')
            || str_contains($path, '#')
            || preg_match('/\s/', $path) === 1
            || $hasAmbiguousSegment
            || ! $typeMatchesPath
            || ! in_array($type, $allowedTypes, true)
            || ! in_array($status, $allowedStatuses, true)
            || ! in_array($template, $allowedTemplates, true)
            || ! in_array($currentTemplate, $allowedTemplates, true)
            || $title === ''
            || $description === ''
            || $parent === ''
            || $lastReviewed === ''
            || $source === ''
            || $contentOwner === ''
            || isset($registry[$path])
        ) {
            continue;
        }

        $record['path'] = $path;
        $record['type'] = $type;
        $record['status'] = $status;
        $record['template'] = $template;
        $record['current_template'] = $currentTemplate;
        $record['title'] = $title;
        $record['description'] = $description;
        $registry[$path] = $record;
    }

    if (count($registry) !== $expectedRouteCount) {
        return [];
    }

    return $registry;
}

function vg_guide_registry_record(string $path): ?array
{
    $normalized = trim($path, '/');
    if ($normalized === '') {
        return null;
    }

    $registry = vg_guide_route_registry();
    return $registry[$normalized] ?? null;
}

/**
 * Returns published registry paths by their current runtime template.
 *
 * The existing vg_guide_pilot_paths() function intentionally remains a
 * literal compatibility contract until the public verifier is migrated to
 * consume this registry. New rollout code should use this function instead.
 */
function vg_guide_registry_paths(string $currentTemplate = 'guide'): array
{
    $paths = [];
    foreach (vg_guide_route_registry() as $path => $record) {
        if (
            ($record['status'] ?? '') === 'published'
            && ($record['current_template'] ?? '') === $currentTemplate
        ) {
            $paths[] = $path;
        }
    }

    return $paths;
}

/**
 * Returns an explicit registry rollout set without changing the pilot default.
 *
 * Set VG_GUIDE_REGISTRY_ROLLOUT to an array of route paths for a canary batch,
 * or to true to enable every published record whose target template is guide.
 * An undefined, malformed, or empty value leaves the existing pilot unchanged.
 */
function vg_guide_registry_rollout_paths(): array
{
    static $paths = null;
    if ($paths !== null) {
        return $paths;
    }

    $paths = [];
    if (! defined('VG_GUIDE_REGISTRY_ROLLOUT')) {
        $rolloutConfigPath = function_exists('get_theme_file_path')
            ? get_theme_file_path('/inc/guide-rollout.php')
            : __DIR__ . '/guide-rollout.php';
        if (is_readable($rolloutConfigPath)) {
            require_once $rolloutConfigPath;
        }
    }

    if (! defined('VG_GUIDE_REGISTRY_ROLLOUT')) {
        return $paths;
    }

    $rollout = constant('VG_GUIDE_REGISTRY_ROLLOUT');
    $registry = vg_guide_route_registry();
    if ($rollout === true) {
        foreach ($registry as $path => $record) {
            if (
                is_array($record)
                && ($record['status'] ?? '') === 'published'
                && ($record['template'] ?? '') === 'guide'
            ) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }

    if (! is_array($rollout)) {
        return $paths;
    }

    foreach ($rollout as $candidate) {
        if (! is_string($candidate)) {
            continue;
        }

        $normalized = trim($candidate, '/');
        $record = $registry[$normalized] ?? null;
        if (
            is_array($record)
            && ($record['status'] ?? '') === 'published'
            && ($record['template'] ?? '') === 'guide'
        ) {
            $paths[] = $normalized;
        }
    }

    return array_values(array_unique($paths));
}

function vg_guide_registry_rollout_paths_map(): array
{
    static $map = null;
    if ($map === null) {
        $map = array_fill_keys(vg_guide_registry_rollout_paths(), true);
    }
    return $map;
}

function vg_get_registry_rollout_type(?WP_Post $post, string $path): ?string
{
    $type = vg_classify_guide_path($path);
    $filtered = apply_filters('vg_guide_type', $type, $post, $path);

    return in_array($filtered, ['destination', 'itinerary', 'comparison', 'practical'], true)
        ? $filtered
        : null;
}

function vg_classify_guide_path(string $path): ?string
{
    $normalized = trim($path, '/');
    if ($normalized === '') {
        return null;
    }

    $segments = explode('/', $normalized);
    $types = [
        'destinations' => 'destination',
        'itineraries' => 'itinerary',
        'compare' => 'comparison',
        'plan' => 'practical',
    ];

    return $types[$segments[0]] ?? null;
}

function vg_get_guide_path(?WP_Post $post = null): string
{
    $post = $post ?: get_post();
    if (! $post instanceof WP_Post || $post->post_type !== 'page') {
        return '';
    }

    return trim((string) get_page_uri($post), '/');
}

function vg_get_guide_type(?WP_Post $post = null): ?string
{
    $post = $post ?: get_post();
    if (! $post instanceof WP_Post || $post->post_type !== 'page') {
        return null;
    }

    $path = vg_get_guide_path($post);
    $type = vg_classify_guide_path($path);
    $filtered = apply_filters('vg_guide_type', $type, $post, $path);

    return in_array($filtered, ['destination', 'itinerary', 'comparison', 'practical'], true)
        ? $filtered
        : null;
}

function vg_is_guide_experience_page(?WP_Post $post = null): bool
{
    $post = $post ?: get_post();
    if (! $post instanceof WP_Post || ! is_page($post->ID)) {
        return false;
    }

    $path = vg_get_guide_path($post);
    $rolloutMap = vg_guide_registry_rollout_paths_map();
    if (isset($rolloutMap[$path])) {
        return vg_get_registry_rollout_type($post, $path) !== null;
    }

    $pilotMap = vg_guide_pilot_paths_map();
    if (! isset($pilotMap[$path])) {
        return false;
    }

    return vg_get_guide_type($post) !== null;
}

/**
 * Canonical alias and destination short-slug redirects map.
 * Guarantees direct 301 redirection to flagship pillar guides, preventing 404 guess traps.
 */
function vg_get_canonical_alias_redirect_map(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $map = [
        // Vanity & toolkit aliases
        'vietnam-travel-cost'          => '/costs/vietnam-travel-cost/',
        'vietnam-visa-checker'         => '/plan/vietnam-evisa/',
        'vietnam-season-weather'       => '/plan/best-time-to-visit-vietnam/',
        'affiliate-review-policy'      => '/affiliate-disclosure/',

        // Northern Vietnam Pillar Destinations
        'destinations/hanoi'           => '/destinations/hanoi-travel-guide/',
        'destinations/ha-long-bay'     => '/destinations/ha-long-bay-travel-guide/',
        'destinations/halong-bay'      => '/destinations/ha-long-bay-travel-guide/',
        'destinations/halong'          => '/destinations/ha-long-bay-travel-guide/',
        'destinations/bai-tu-long-bay' => '/destinations/bai-tu-long-bay-guide/',
        'destinations/bai-tu-long'     => '/destinations/bai-tu-long-bay-guide/',
        'destinations/cat-ba'          => '/destinations/cat-ba-travel-guide/',
        'destinations/catba'           => '/destinations/cat-ba-travel-guide/',
        'destinations/sapa'            => '/destinations/sapa-travel-guide/',
        'destinations/sa-pa'           => '/destinations/sapa-travel-guide/',
        'destinations/ninh-binh'       => '/destinations/ninh-binh-travel-guide/',
        'destinations/ninhbinh'        => '/destinations/ninh-binh-travel-guide/',
        'destinations/tam-coc'         => '/destinations/tam-coc-travel-guide/',
        'destinations/ha-giang'        => '/destinations/ha-giang-loop-planning-guide/',
        'destinations/hagiang'         => '/destinations/ha-giang-loop-planning-guide/',
        'destinations/cao-bang'        => '/destinations/cao-bang-travel-guide/',
        'destinations/caobang'         => '/destinations/cao-bang-travel-guide/',
        'destinations/mu-cang-chai'    => '/destinations/mu-cang-chai-travel-guide/',
        'destinations/mucangchai'      => '/destinations/mu-cang-chai-travel-guide/',
        'destinations/pu-luong'        => '/destinations/pu-luong-travel-guide/',
        'destinations/puluong'         => '/destinations/pu-luong-travel-guide/',
        'destinations/ba-be-lake'      => '/destinations/ba-be-lake-travel-guide/',
        'destinations/ba-be'           => '/destinations/ba-be-lake-travel-guide/',

        // Central Vietnam Pillar Destinations
        'destinations/da-nang'         => '/destinations/da-nang-travel-guide/',
        'destinations/danang'          => '/destinations/da-nang-travel-guide/',
        'destinations/hoi-an'          => '/destinations/hoi-an-ancient-town-guide/',
        'destinations/hoian'           => '/destinations/hoi-an-ancient-town-guide/',
        'destinations/hue'             => '/destinations/hue-imperial-city-guide/',
        'destinations/phong-nha'       => '/destinations/phong-nha-travel-guide/',
        'destinations/phongnha'        => '/destinations/phong-nha-travel-guide/',
        'destinations/quy-nhon'        => '/destinations/quy-nhon-travel-guide/',
        'destinations/quynhon'         => '/destinations/quy-nhon-travel-guide/',
        'destinations/nha-trang'       => '/destinations/nha-trang-travel-guide/',
        'destinations/nhatrang'        => '/destinations/nha-trang-travel-guide/',
        'destinations/da-lat'          => '/destinations/da-lat-travel-guide/',
        'destinations/dalat'           => '/destinations/da-lat-travel-guide/',
        'destinations/mang-den'        => '/destinations/mang-den-travel-guide/',
        'destinations/mangden'         => '/destinations/mang-den-travel-guide/',
        'destinations/phu-yen'         => '/destinations/phu-yen-travel-guide/',
        'destinations/phuyen'          => '/destinations/phu-yen-travel-guide/',
        'destinations/cham-islands'    => '/destinations/cham-islands-travel-guide/',
        'destinations/ly-son'          => '/destinations/ly-son-travel-guide/',
        'destinations/lyson'           => '/destinations/ly-son-travel-guide/',
        'destinations/ba-na-hills'     => '/destinations/ba-na-hills-golden-bridge-guide/',
        'destinations/bana-hills'      => '/destinations/ba-na-hills-golden-bridge-guide/',

        // Southern Vietnam Pillar Destinations
        'destinations/ho-chi-minh-city' => '/destinations/ho-chi-minh-city-travel-guide/',
        'destinations/saigon'           => '/destinations/ho-chi-minh-city-travel-guide/',
        'destinations/hcmc'             => '/destinations/ho-chi-minh-city-travel-guide/',
        'destinations/cu-chi'           => '/compare/cu-chi-tunnels-ben-duoc-vs-ben-dinh/',
        'destinations/cuchi'            => '/compare/cu-chi-tunnels-ben-duoc-vs-ben-dinh/',
        'destinations/mekong-delta'     => '/destinations/mekong-delta-travel-guide/',
        'destinations/mekong'           => '/destinations/mekong-delta-travel-guide/',
        'destinations/phu-quoc'         => '/destinations/phu-quoc-travel-guide/',
        'destinations/phuquoc'          => '/destinations/phu-quoc-travel-guide/',
        'destinations/con-dao'          => '/destinations/con-dao-travel-guide/',
        'destinations/condao'           => '/destinations/con-dao-travel-guide/',
        'destinations/can-tho'          => '/destinations/where-to-stay-in-can-tho/',
        'destinations/cantho'           => '/destinations/where-to-stay-in-can-tho/',
        'destinations/mui-ne'           => '/destinations/best-things-to-do-in-mui-ne/',
        'destinations/muine'            => '/destinations/best-things-to-do-in-mui-ne/',

        // Common Navigation & Cross-Category Aliases
        'itineraries/7-days'           => '/itineraries/7-days-in-vietnam/',
        'itineraries/10-days'          => '/itineraries/10-days-in-vietnam/',
        'itineraries/14-days'          => '/itineraries/14-days-in-vietnam/',
        'itineraries/21-days'          => '/itineraries/21-days-in-vietnam/',
        'plan/visa'                    => '/plan/vietnam-evisa/',
        'plan/best-time'               => '/plan/best-time-to-visit-vietnam/',
        'compare/hanoi-vs-saigon'      => '/compare/hanoi-vs-ho-chi-minh-city/',
    ];

    return $map;
}

/**
 * Redirects legacy, vanity toolkit, and short destination slug URLs to their canonical guide permalinks.
 */
function vg_handle_toolkit_vanity_redirects(): void
{
    if (is_admin()) {
        return;
    }

    $req_uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = strtolower(trim((string) (parse_url($req_uri, PHP_URL_PATH) ?? ''), '/'));

    $redirect_map = vg_get_canonical_alias_redirect_map();

    if (isset($redirect_map[$path])) {
        wp_safe_redirect(home_url($redirect_map[$path]), 301);
        exit;
    }
}
add_action('template_redirect', 'vg_handle_toolkit_vanity_redirects', 1);

/**
 * Suppress WordPress 404 permalink guessing on destination routes to prevent
 * unintentional 301 redirects to unrelated sub-pages or transport guides.
 */
add_filter('do_redirect_guess_404_permalink', static function ($do_redirect) {
    if (! empty($_SERVER['REQUEST_URI'])) {
        $path = strtolower(trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
        if (str_starts_with($path, 'destinations/')) {
            return false;
        }
    }
    return $do_redirect;
});

/**
 * Adds semantic body class when page renders the full Guide Shell experience.
 */
function vg_guide_experience_body_class(array $classes): array
{
    if (vg_is_guide_experience_page()) {
        $classes[] = 'vg-has-guide-shell';
    }
    return $classes;
}
add_filter('body_class', 'vg_guide_experience_body_class');

/**
 * Ensures public site-wide search queries include 'page' (where all 282 canonical guides live)
 * alongside 'post'.
 *
 * @param object $query WP_Query instance.
 */
function vg_include_pages_in_search_results($query): void
{
    if (function_exists('is_admin') && is_admin()) {
        return;
    }

    if (is_object($query) && method_exists($query, 'is_main_query') && method_exists($query, 'is_search') && method_exists($query, 'set')) {
        if ($query->is_main_query() && $query->is_search()) {
            $current_types = method_exists($query, 'get') ? $query->get('post_type') : '';
            if (empty($current_types) || $current_types === 'post' || $current_types === 'any') {
                $query->set('post_type', ['page', 'post']);
            }
        }
    }
}
add_action('pre_get_posts', 'vg_include_pages_in_search_results', 10);

/**
 * Searches the 282-route canonical registry for matching travel guides by title, path, and description.
 * Provides deterministic search results even before full MySQL fulltext indexing or in standalone environments.
 *
 * @param string $search_query Raw user search query.
 * @param int    $limit        Maximum number of matched routes to return.
 * @return array<int, array<string, string>>
 */
function vg_search_canonical_registry(string $search_query, int $limit = 12): array
{
    $q_clean = strtolower(trim($search_query));
    if ($q_clean === '' || $limit <= 0) {
        return [];
    }

    $registry = vg_guide_route_registry();
    if ($registry === []) {
        return [];
    }

    $raw_tokens = preg_split('/[\s\-_\/,.;:?!]+/', $q_clean) ?: [];
    $stop_words = ['in' => true, 'to' => true, 'the' => true, 'of' => true, 'and' => true, 'for' => true, 'or' => true, 'vs' => true, 'a' => true, 'an' => true];
    $tokens = [];
    foreach ($raw_tokens as $tok) {
        if (strlen($tok) >= 2 && ! isset($stop_words[$tok])) {
            $tokens[] = $tok;
        }
    }
    if ($tokens === [] && strlen($q_clean) >= 2) {
        $tokens[] = $q_clean;
    }
    if ($tokens === []) {
        return [];
    }

    $scored = [];
    foreach ($registry as $path => $record) {
        if (($record['status'] ?? '') !== 'published') {
            continue;
        }

        $title_lower = strtolower((string) ($record['title'] ?? ''));
        $desc_lower  = strtolower((string) ($record['description'] ?? ''));
        $path_lower  = strtolower((string) $path);
        $slug_lower  = strtolower(basename($path_lower));

        $score = 0;
        if (str_contains($title_lower, $q_clean)) {
            $score += 20;
        }
        if (str_contains($slug_lower, str_replace(' ', '-', $q_clean))) {
            $score += 18;
        }
        if (str_contains($desc_lower, $q_clean)) {
            $score += 8;
        }

        $matched_tokens = 0;
        foreach ($tokens as $tok) {
            $tok_matched = false;
            if (str_contains($title_lower, $tok)) {
                $score += 6;
                $tok_matched = true;
            }
            if (str_contains($path_lower, $tok)) {
                $score += 5;
                $tok_matched = true;
            }
            if (str_contains($desc_lower, $tok)) {
                $score += 2;
                $tok_matched = true;
            }
            if ($tok_matched) {
                $matched_tokens++;
            }
        }

        if ($score > 0 && $matched_tokens > 0) {
            if ($matched_tokens === count($tokens)) {
                $score += 10;
            }
            $scored[] = [
                'score'         => $score,
                'path'          => (string) $path,
                'url'           => function_exists('home_url') ? home_url('/' . trim((string) $path, '/') . '/') : '/' . trim((string) $path, '/') . '/',
                'title'         => (string) ($record['title'] ?? ''),
                'description'   => (string) ($record['description'] ?? ''),
                'type'          => (string) ($record['type'] ?? 'destination'),
                'last_reviewed' => (string) ($record['last_reviewed'] ?? '2026-10-18'),
            ];
        }
    }

    usort($scored, static function (array $a, array $b): int {
        if ($b['score'] !== $a['score']) {
            return $b['score'] <=> $a['score'];
        }
        return strcmp($a['path'], $b['path']);
    });

    $results = array_slice($scored, 0, $limit);
    foreach ($results as &$item) {
        unset($item['score']);
    }
    unset($item);

    return $results;
}

/**
 * Processes and stores newsletter subscriptions submitted via admin-post.php or REST API.
 *
 * @param string $email       Raw email input.
 * @param string $honeypot    Honeypot field value (must be empty).
 * @param string $source_path Page path where subscription originated.
 * @return array{ok: bool, status: string, email?: string}
 */
function vg_process_newsletter_subscription_payload(string $email, string $honeypot = '', string $source_path = 'newsletter'): array
{
    if (trim($honeypot) !== '') {
        return ['ok' => true, 'status' => 'honeypot_discarded'];
    }

    $clean_email = function_exists('sanitize_email') ? sanitize_email(trim($email)) : filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    $is_valid = function_exists('is_email') ? (bool) is_email($clean_email) : (bool) filter_var($clean_email, FILTER_VALIDATE_EMAIL);

    if (! $is_valid || $clean_email === '') {
        return ['ok' => false, 'status' => 'invalid_email'];
    }

    $normalized_email = strtolower((string) $clean_email);
    $email_hash = hash('sha256', $normalized_email);
    $subscribers = function_exists('get_option') ? get_option('vg_newsletter_subscribers', []) : [];
    if (! is_array($subscribers)) {
        $subscribers = [];
    }

    $subscribers[$email_hash] = [
        'email_hash'    => $email_hash,
        'email_masked'  => substr($normalized_email, 0, 2) . '***@' . substr((string) strrchr($normalized_email, '@'), 1),
        'subscribed_at' => gmdate('c'),
        'source_path'   => preg_replace('/[^a-z0-9\/_-]/i', '', strtolower(trim($source_path, '/'))) ?: 'newsletter',
    ];

    if (count($subscribers) > 500) {
        $subscribers = array_slice($subscribers, -500, null, true);
    }

    if (function_exists('update_option')) {
        update_option('vg_newsletter_subscribers', $subscribers, false);
    }

    return [
        'ok'     => true,
        'status' => 'subscribed',
        'email'  => $normalized_email,
    ];
}

/**
 * Handles POST /wp-admin/admin-post.php?action=vg_newsletter_subscribe
 */
function vg_handle_newsletter_subscription(): void
{
    $nonce = isset($_POST['vg_newsletter_nonce']) ? (string) $_POST['vg_newsletter_nonce'] : '';
    if (function_exists('wp_verify_nonce') && ! wp_verify_nonce($nonce, 'vg_newsletter_subscribe_action')) {
        $redirect_err = function_exists('home_url') ? home_url('/newsletter/?vg_subscribed=invalid_nonce') : '/newsletter/?vg_subscribed=invalid_nonce';
        if (function_exists('wp_safe_redirect')) {
            wp_safe_redirect($redirect_err, 303);
            exit;
        }
        return;
    }

    $email = isset($_POST['vg_subscriber_email']) ? (string) $_POST['vg_subscriber_email'] : '';
    $honeypot = isset($_POST['vg_hp_website']) ? (string) $_POST['vg_hp_website'] : '';
    $source = isset($_POST['vg_source_path']) ? (string) $_POST['vg_source_path'] : 'newsletter';

    $result = vg_process_newsletter_subscription_payload($email, $honeypot, $source);
    $status_param = $result['ok'] ? '1' : 'error';
    $redirect_url = function_exists('home_url') ? home_url('/newsletter/?vg_subscribed=' . $status_param) : '/newsletter/?vg_subscribed=' . $status_param;

    if (function_exists('wp_safe_redirect')) {
        wp_safe_redirect($redirect_url, 303);
        exit;
    }
}
add_action('admin_post_nopriv_vg_newsletter_subscribe', 'vg_handle_newsletter_subscription');
add_action('admin_post_vg_newsletter_subscribe', 'vg_handle_newsletter_subscription');

/**
 * Processes and stores reader contact or route correction dispatches.
 *
 * @param string $name     Sender name.
 * @param string $email    Sender email.
 * @param string $topic    Dispatch topic.
 * @param string $message  Message body.
 * @param string $honeypot Honeypot field value.
 * @return array{ok: bool, status: string}
 */
function vg_process_contact_submission_payload(string $name, string $email, string $topic, string $message, string $honeypot = ''): array
{
    if (trim($honeypot) !== '') {
        return ['ok' => true, 'status' => 'honeypot_discarded'];
    }

    $clean_email = function_exists('sanitize_email') ? sanitize_email(trim($email)) : filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    $is_valid_email = function_exists('is_email') ? (bool) is_email($clean_email) : (bool) filter_var($clean_email, FILTER_VALIDATE_EMAIL);
    $clean_message = trim(strip_tags($message));

    if (! $is_valid_email || $clean_email === '' || strlen($clean_message) < 10) {
        return ['ok' => false, 'status' => 'validation_failed'];
    }

    $clean_name = trim(strip_tags($name)) ?: 'Reader';
    $clean_topic = preg_replace('/[^a-z0-9_-]/i', '', $topic) ?: 'route_correction';

    $submissions = function_exists('get_option') ? get_option('vg_contact_submissions', []) : [];
    if (! is_array($submissions)) {
        $submissions = [];
    }

    $submissions[] = [
        'name'         => substr($clean_name, 0, 120),
        'email_hash'   => hash('sha256', strtolower((string) $clean_email)),
        'topic'        => substr($clean_topic, 0, 60),
        'message'      => substr($clean_message, 0, 2000),
        'submitted_at' => gmdate('c'),
    ];

    if (count($submissions) > 200) {
        $submissions = array_slice($submissions, -200);
    }

    if (function_exists('update_option')) {
        update_option('vg_contact_submissions', $submissions, false);
    }

    return ['ok' => true, 'status' => 'received'];
}

/**
 * Handles POST /wp-admin/admin-post.php?action=vg_contact_submit
 */
function vg_handle_contact_submission(): void
{
    $nonce = isset($_POST['vg_contact_nonce']) ? (string) $_POST['vg_contact_nonce'] : '';
    if (function_exists('wp_verify_nonce') && ! wp_verify_nonce($nonce, 'vg_contact_submit_action')) {
        $redirect_err = function_exists('home_url') ? home_url('/contact/?vg_contact_sent=invalid_nonce') : '/contact/?vg_contact_sent=invalid_nonce';
        if (function_exists('wp_safe_redirect')) {
            wp_safe_redirect($redirect_err, 303);
            exit;
        }
        return;
    }

    $name     = isset($_POST['vg_contact_name']) ? (string) $_POST['vg_contact_name'] : '';
    $email    = isset($_POST['vg_contact_email']) ? (string) $_POST['vg_contact_email'] : '';
    $topic    = isset($_POST['vg_contact_topic']) ? (string) $_POST['vg_contact_topic'] : 'route_correction';
    $message  = isset($_POST['vg_contact_message']) ? (string) $_POST['vg_contact_message'] : '';
    $honeypot = isset($_POST['vg_hp_company']) ? (string) $_POST['vg_hp_company'] : '';

    $result = vg_process_contact_submission_payload($name, $email, $topic, $message, $honeypot);
    $status_param = $result['ok'] ? '1' : 'error';
    $redirect_url = function_exists('home_url') ? home_url('/contact/?vg_contact_sent=' . $status_param) : '/contact/?vg_contact_sent=' . $status_param;

    if (function_exists('wp_safe_redirect')) {
        wp_safe_redirect($redirect_url, 303);
        exit;
    }
}
add_action('admin_post_nopriv_vg_contact_submit', 'vg_handle_contact_submission');
add_action('admin_post_vg_contact_submit', 'vg_handle_contact_submission');

/**
 * Registers REST API endpoints for headless/AJAX newsletter and contact submissions.
 */
function vg_register_conversion_rest_routes(): void
{
    if (! function_exists('register_rest_route')) {
        return;
    }

    register_rest_route('vietnamguide/v1', '/subscribe', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => static function ($request) {
            $email    = is_object($request) && method_exists($request, 'get_param') ? (string) $request->get_param('email') : '';
            $honeypot = is_object($request) && method_exists($request, 'get_param') ? (string) $request->get_param('website') : '';
            $source   = is_object($request) && method_exists($request, 'get_param') ? (string) $request->get_param('source') : 'rest_api';
            return vg_process_newsletter_subscription_payload($email, $honeypot, $source);
        },
    ]);

    register_rest_route('vietnamguide/v1', '/contact', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => static function ($request) {
            $name     = is_object($request) && method_exists($request, 'get_param') ? (string) $request->get_param('name') : '';
            $email    = is_object($request) && method_exists($request, 'get_param') ? (string) $request->get_param('email') : '';
            $topic    = is_object($request) && method_exists($request, 'get_param') ? (string) $request->get_param('topic') : 'route_correction';
            $message  = is_object($request) && method_exists($request, 'get_param') ? (string) $request->get_param('message') : '';
            $honeypot = is_object($request) && method_exists($request, 'get_param') ? (string) $request->get_param('company') : '';
            return vg_process_contact_submission_payload($name, $email, $topic, $message, $honeypot);
        },
    ]);
}
add_action('rest_api_init', 'vg_register_conversion_rest_routes');

/**
 * Renders the functional Field Notes newsletter subscription form (Zero-H2 compliant).
 */
function vg_render_newsletter_subscription_form(string $source_path = 'newsletter'): string
{
    $action_url = function_exists('admin_url') ? admin_url('admin-post.php') : '/wp-admin/admin-post.php';
    $nonce_val  = function_exists('wp_create_nonce') ? wp_create_nonce('vg_newsletter_subscribe_action') : 'vg_static_nonce';
    $status     = isset($_GET['vg_subscribed']) ? (string) $_GET['vg_subscribed'] : '';

    ob_start();
    ?>
    <section class="vg-conversion-desk vg-newsletter-desk" aria-label="VietnamGuide Field Notes Subscription">
        <div class="vg-conversion-desk__inner">
            <span class="vg-conversion-desk__kicker">QUARTERLY DISPATCH // ZERO SPONSORED PLACEMENTS</span>
            <h3 class="vg-conversion-desk__title">Subscribe to VietnamGuide Field Notes</h3>
            <p class="vg-conversion-desk__lede">Receive verified updates when Vietnam e-visa checkpoints change, Reunification Express rail timetables shift, or seasonal typhoon warnings affect central and northern transit corridors.</p>

            <?php if ($status === '1') : ?>
                <div class="vg-conversion-alert vg-conversion-alert--success" role="status" aria-live="polite">
                    <strong>Subscription confirmed.</strong> Your email is registered for the next quarterly VietnamGuide Field Notes dispatch.
                </div>
            <?php elseif ($status !== '') : ?>
                <div class="vg-conversion-alert vg-conversion-alert--error" role="alert">
                    <strong>Unable to complete subscription.</strong> Please enter a valid email address and submit the form again.
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url($action_url); ?>" class="vg-newsletter-form" data-vg-newsletter-form>
                <input type="hidden" name="action" value="vg_newsletter_subscribe">
                <input type="hidden" name="vg_newsletter_nonce" value="<?php echo esc_attr($nonce_val); ?>">
                <input type="hidden" name="vg_source_path" value="<?php echo esc_attr($source_path); ?>">
                <div class="vg-hp-field" aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
                    <label for="vg-hp-website">Leave this field empty</label>
                    <input type="text" id="vg-hp-website" name="vg_hp_website" value="" tabindex="-1" autocomplete="off">
                </div>
                <div class="vg-newsletter-form__row">
                    <label for="vg-subscriber-email" class="screen-reader-text">Email address</label>
                    <input
                        type="email"
                        id="vg-subscriber-email"
                        name="vg_subscriber_email"
                        class="vg-newsletter-form__input"
                        placeholder="you@example.com"
                        required
                        autocomplete="email"
                    >
                    <button type="submit" class="vg-button vg-newsletter-form__submit">Subscribe to Field Notes</button>
                </div>
                <p class="vg-newsletter-form__privacy">Sent at most once per month. Unsubscribe in one click. Read our <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy Policy</a>.</p>
            </form>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}

/**
 * Renders the functional Editorial & Route Correction Contact form (Zero-H2 compliant).
 */
function vg_render_contact_dispatch_form(): string
{
    $action_url = function_exists('admin_url') ? admin_url('admin-post.php') : '/wp-admin/admin-post.php';
    $nonce_val  = function_exists('wp_create_nonce') ? wp_create_nonce('vg_contact_submit_action') : 'vg_static_nonce';
    $status     = isset($_GET['vg_contact_sent']) ? (string) $_GET['vg_contact_sent'] : '';

    ob_start();
    ?>
    <section class="vg-conversion-desk vg-contact-desk" aria-label="Editorial Desk and Route Correction Form">
        <div class="vg-conversion-desk__inner">
            <span class="vg-conversion-desk__kicker">EDITORIAL DESK // FIELD CORRECTIONS &amp; INQUIRIES</span>
            <h3 class="vg-conversion-desk__title">Send a Field Update or Editorial Inquiry</h3>
            <p class="vg-conversion-desk__lede">Report an updated station fare, border checkpoint change, or factual correction directly to our research desk. Every verified submission is logged against the route registry.</p>

            <?php if ($status === '1') : ?>
                <div class="vg-conversion-alert vg-conversion-alert--success" role="status" aria-live="polite">
                    <strong>Dispatch received.</strong> Thank you—our editorial team has logged your note for verification.
                </div>
            <?php elseif ($status !== '') : ?>
                <div class="vg-conversion-alert vg-conversion-alert--error" role="alert">
                    <strong>Submission incomplete.</strong> Please provide a valid email address and a message of at least 10 characters.
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url($action_url); ?>" class="vg-contact-form" data-vg-contact-form>
                <input type="hidden" name="action" value="vg_contact_submit">
                <input type="hidden" name="vg_contact_nonce" value="<?php echo esc_attr($nonce_val); ?>">
                <div class="vg-hp-field" aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
                    <label for="vg-hp-company">Leave this field empty</label>
                    <input type="text" id="vg-hp-company" name="vg_hp_company" value="" tabindex="-1" autocomplete="off">
                </div>
                <div class="vg-contact-form__grid">
                    <div class="vg-contact-form__field">
                        <label for="vg-contact-name">Your Name</label>
                        <input type="text" id="vg-contact-name" name="vg_contact_name" required autocomplete="name" placeholder="Alex Nguyen">
                    </div>
                    <div class="vg-contact-form__field">
                        <label for="vg-contact-email">Email Address</label>
                        <input type="email" id="vg-contact-email" name="vg_contact_email" required autocomplete="email" placeholder="alex@example.com">
                    </div>
                </div>
                <div class="vg-contact-form__field">
                    <label for="vg-contact-topic">Inquiry Topic</label>
                    <select id="vg-contact-topic" name="vg_contact_topic">
                        <option value="route_correction">Route Fare / Timetable Correction</option>
                        <option value="visa_update">Visa or Border Entry Checkpoint Update</option>
                        <option value="editorial_question">General Travel Planning Question</option>
                        <option value="press_media">Press &amp; Editorial Licensing</option>
                    </select>
                </div>
                <div class="vg-contact-form__field">
                    <label for="vg-contact-message">Field Details &amp; Route URL</label>
                    <textarea id="vg-contact-message" name="vg_contact_message" rows="4" required placeholder="Include the route URL, date observed, and updated VND fare or transit schedule..."></textarea>
                </div>
                <button type="submit" class="vg-button vg-contact-form__submit">Submit to Editorial Desk</button>
            </form>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}

/**
 * Replaces dead-end placeholder text on /newsletter/ and /contact/ pages with live functional forms.
 */
function vg_inject_utility_conversion_forms(string $content): string
{
    if (function_exists('is_admin') && is_admin()) {
        return $content;
    }
    if (strpos($content, 'vg-guide-hero') !== false) {
        return $content;
    }

    $post = function_exists('get_post') ? get_post() : null;
    $slug = ($post instanceof WP_Post && isset($post->post_name)) ? strtolower((string) $post->post_name) : '';
    if ($slug === '' && ! empty($_SERVER['REQUEST_URI'])) {
        $slug = strtolower(trim((string) parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
    }

    if ($slug === 'newsletter' || (function_exists('is_page') && is_page('newsletter'))) {
        if (strpos($content, 'data-vg-newsletter-form') === false) {
            $cleaned = preg_replace('/<p[^>]*>[^<]*email capture will be connected[^<]*<\/p>/i', '', $content);
            return ($cleaned ?? $content) . "\n\n" . vg_render_newsletter_subscription_form('newsletter');
        }
    }

    if ($slug === 'contact' || (function_exists('is_page') && is_page('contact'))) {
        if (strpos($content, 'data-vg-contact-form') === false) {
            return $content . "\n\n" . vg_render_contact_dispatch_form();
        }
    }

    return $content;
}
add_filter('the_content', 'vg_inject_utility_conversion_forms', 25);



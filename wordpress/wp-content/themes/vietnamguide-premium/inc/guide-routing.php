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
 * Global telemetry storage for route fallbacks (observability without database drag).
 */
function vg_record_route_fallback(string $path, string $reason): void
{
    $GLOBALS['vg_route_fallback_telemetry'] = $GLOBALS['vg_route_fallback_telemetry'] ?? [];
    $GLOBALS['vg_route_fallback_telemetry'][] = [
        'path' => $path,
        'reason' => $reason,
        'timestamp' => microtime(true),
    ];
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
 * Redirects legacy and vanity toolkit URLs to their canonical guide permalinks.
 */
function vg_handle_toolkit_vanity_redirects(): void
{
    if (is_admin()) {
        return;
    }

    $req_uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = trim((string) (parse_url($req_uri, PHP_URL_PATH) ?? ''), '/');

    $vanity_map = [
        'vietnam-travel-cost'    => '/costs/vietnam-travel-cost/',
        'vietnam-visa-checker'   => '/plan/vietnam-evisa/',
        'vietnam-season-weather' => '/plan/best-time-to-visit-vietnam/',
    ];

    if (isset($vanity_map[$path])) {
        wp_safe_redirect(home_url($vanity_map[$path]), 301);
        exit;
    }
}
add_action('template_redirect', 'vg_handle_toolkit_vanity_redirects', 1);

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


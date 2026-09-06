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
    ];
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
    if (! in_array($path, vg_guide_pilot_paths(), true)) {
        return false;
    }

    return vg_get_guide_type($post) !== null;
}

<?php
if (! defined('ABSPATH')) {
    exit;
}

function vg_guide_pilot_paths(): array
{
    return [
        'destinations/ho-chi-minh-city-travel-guide',
        'itineraries/10-days-in-vietnam',
        'compare/ha-long-bay-vs-lan-ha-bay',
        'plan/vietnam-evisa',
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
    if (! $post instanceof WP_Post || ! is_page($post)) {
        return false;
    }

    $path = vg_get_guide_path($post);
    if (! in_array($path, vg_guide_pilot_paths(), true)) {
        return false;
    }

    return vg_get_guide_type($post) !== null;
}

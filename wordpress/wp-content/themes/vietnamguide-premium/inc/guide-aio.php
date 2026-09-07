<?php
/**
 * VietnamGuide AIO (AI Optimization & Agent Discoverability)
 * Provides /llms.txt endpoint and AI crawler permissions.
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_handle_llms_txt_request(): void
{
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = trim((string) parse_url($request_uri, PHP_URL_PATH), '/');

    if ($path !== 'llms.txt') {
        return;
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: all');

    $site_url = untrailingslashit(home_url());

    echo "# VietnamGuide — Independent Vietnam Travel Intelligence\n\n";
    echo "> High-evidence, logistics-first curated travel guides for international travelers visiting Vietnam.\n";
    echo "> Published by the VietnamGuide editorial team. All routes independently vetted with verified pricing, transit times, and route maps.\n\n";
    echo "Canonical Base: {$site_url}/\n";
    echo "Sitemap: {$site_url}/sitemap_index.xml\n\n";

    $paths = function_exists('vg_guide_pilot_paths') ? vg_guide_pilot_paths() : [];

    $categories = [
        'destinations' => 'Core Destination Guides',
        'itineraries'  => 'Curated Route Itineraries',
        'compare'      => 'Strategic Route & Base Comparisons',
        'plan'         => 'Logistics & Practical Preparation',
    ];

    $grouped = [];
    foreach ($paths as $guide_path) {
        $parts = explode('/', $guide_path);
        $prefix = $parts[0] ?? 'destinations';
        if (! isset($grouped[$prefix])) {
            $grouped[$prefix] = [];
        }
        $raw_title = basename($guide_path);
        $title = ucwords(str_replace('-', ' ', $raw_title));
        $grouped[$prefix][] = "- [{$title}]({$site_url}/{$guide_path}/)";
    }

    foreach ($categories as $key => $cat_name) {
        if (! empty($grouped[$key])) {
            echo "## {$cat_name}\n\n";
            echo implode("\n", $grouped[$key]) . "\n\n";
        }
    }

    exit;
}
add_action('init', 'vg_handle_llms_txt_request', 0);

function vg_add_ai_crawler_directives(string $output, bool $public): string
{
    if (! $public) {
        return $output;
    }

    $site_url = untrailingslashit(home_url());
    $ai_directives = "\n# AI Agent & Answer Engine Discoverability\n"
        . "User-agent: GPTBot\nAllow: /\n\n"
        . "User-agent: PerplexityBot\nAllow: /\n\n"
        . "User-agent: ClaudeBot\nAllow: /\n\n"
        . "User-agent: Google-Extended\nAllow: /\n\n"
        . "User-agent: Applebot-Extended\nAllow: /\n\n"
        . "LLMs-Txt: {$site_url}/llms.txt\n";

    return rtrim($output) . "\n" . $ai_directives;
}
add_filter('robots_txt', 'vg_add_ai_crawler_directives', 30, 2);

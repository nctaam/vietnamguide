<?php
if (! defined('ABSPATH')) { exit; }
require_once get_theme_file_path('/inc/homepage-data.php');
require_once get_theme_file_path('/inc/guide-routing.php');
require_once get_theme_file_path('/inc/guide-content.php');
require_once get_theme_file_path('/inc/guide-context.php');
require_once get_theme_file_path('/inc/guide-aio.php');
require_once get_theme_file_path('/inc/guide-seo.php');
require_once get_theme_file_path('/inc/guide-itinerary-finder.php');
require_once get_theme_file_path('/inc/guide-cost-calculator.php');
require_once get_theme_file_path('/inc/guide-season-matrix.php');
require_once get_theme_file_path('/inc/guide-visa-checker.php');
require_once get_theme_file_path('/inc/guide-airport-navigator.php');
require_once get_theme_file_path('/inc/guide-packing-checklist.php');
require_once get_theme_file_path('/inc/guide-interactive-map.php');
require_once get_theme_file_path('/inc/guide-analytics.php');

function vg_theme_asset_version(string $relativePath): string
{
    static $versions = [];
    static $fallbackVersion = null;

    $normalizedPath = ltrim(str_replace('\\', '/', $relativePath), '/');
    $allowedPaths = [
        'assets/css/homepage.css' => true,
        'assets/css/guide-patterns.css' => true,
        'assets/js/homepage.js' => true,
        'assets/css/guide-experience.css' => true,
        'assets/js/guide-experience.js' => true,
    ];

    if ($fallbackVersion === null) {
        $fallbackVersion = (string) wp_get_theme()->get('Version');
    }
    if (! isset($allowedPaths[$normalizedPath])) {
        return $fallbackVersion;
    }
    if (array_key_exists($normalizedPath, $versions)) {
        return $versions[$normalizedPath];
    }

    $assetPath = get_theme_file_path('/' . $normalizedPath);
    if (! function_exists('hash_file') || ! is_file($assetPath) || ! is_readable($assetPath)) {
        $versions[$normalizedPath] = $fallbackVersion;
        return $versions[$normalizedPath];
    }

    $hash = @hash_file('sha256', $assetPath);
    $versions[$normalizedPath] = is_string($hash) && preg_match('/\A[a-f0-9]{64}\z/', $hash) === 1
        ? $hash
        : $fallbackVersion;

    return $versions[$normalizedPath];
}

add_action('after_setup_theme', static function (): void {
    add_theme_support('automatic-feed-links');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_editor_style(['assets/css/homepage.css', 'assets/css/guide-patterns.css']);
    register_nav_menus([
        'primary' => __('Primary navigation', 'vietnamguide-premium'),
        'footer' => __('Footer navigation', 'vietnamguide-premium'),
    ]);
});
add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style('vietnamguide-homepage', get_theme_file_uri('/assets/css/homepage.css'), [], vg_theme_asset_version('/assets/css/homepage.css'));
    wp_enqueue_style('vietnamguide-guide-patterns', get_theme_file_uri('/assets/css/guide-patterns.css'), ['vietnamguide-homepage'], vg_theme_asset_version('/assets/css/guide-patterns.css'));
    wp_enqueue_script('vietnamguide-homepage', get_theme_file_uri('/assets/js/homepage.js'), [], vg_theme_asset_version('/assets/js/homepage.js'), true);
    if (vg_is_guide_experience_page()) {
        wp_enqueue_style(
            'vietnamguide-guide-experience',
            get_theme_file_uri('/assets/css/guide-experience.css'),
            ['vietnamguide-guide-patterns'],
            vg_theme_asset_version('/assets/css/guide-experience.css')
        );
        wp_enqueue_script(
            'vietnamguide-guide-experience',
            get_theme_file_uri('/assets/js/guide-experience.js'),
            [],
            vg_theme_asset_version('/assets/js/guide-experience.js'),
            true
        );
    }
});
function vg_primary_menu_fallback(): void
{
    $data = vg_homepage_data();
    echo '<ul class="vg-nav-list">';
    foreach ($data['navigation'] as $item) {
        $label = $item['label'];
        if ($label === 'Destinations') {
            echo '<li class="vg-nav-item vg-has-megamenu">';
            printf('<a href="%1$s" class="vg-nav-link">%2$s <span class="vg-nav-caret" aria-hidden="true">&dtrif;</span></a>', esc_url($item['url']), esc_html($label));
            echo '<div class="vg-megamenu-panel" role="region" aria-label="Destinations Directory">';
            echo '<div class="vg-megamenu-grid">';
            echo '<div class="vg-megamenu-col">';
            echo '<span class="vg-megamenu-heading">Northern Region</span>';
            echo '<ul class="vg-megamenu-links">';
            echo '<li><a href="' . esc_url(vg_home_url('destinations/hanoi-travel-guide')) . '"><strong>Hanoi</strong> <span>Old Quarter & Street Food</span></a></li>';
            echo '<li><a href="' . esc_url(vg_home_url('destinations/ha-long-bay-travel-guide')) . '"><strong>Ha Long Bay</strong> <span>Limestone Seascape</span></a></li>';
            echo '<li><a href="' . esc_url(vg_home_url('destinations/ninh-binh-travel-guide')) . '"><strong>Ninh Binh</strong> <span>River Karsts & Cycling</span></a></li>';
            echo '<li><a href="' . esc_url(vg_home_url('destinations/sapa-travel-guide')) . '"><strong>Sa Pa</strong> <span>Highland Terraces & Valleys</span></a></li>';
            echo '</ul>';
            echo '</div>';
            echo '<div class="vg-megamenu-col">';
            echo '<span class="vg-megamenu-heading">Central & Coast</span>';
            echo '<ul class="vg-megamenu-links">';
            echo '<li><a href="' . esc_url(vg_home_url('destinations/best-things-to-do-in-hoi-an')) . '"><strong>Hoi An</strong> <span>Ancient Town & Coastal Base</span></a></li>';
            echo '<li><a href="' . esc_url(vg_home_url('compare/da-nang-vs-hoi-an')) . '"><strong>Da Nang</strong> <span>Coastal Hub & Flight Transit</span></a></li>';
            echo '<li><a href="' . esc_url(vg_home_url('destinations/phong-nha-travel-guide')) . '"><strong>Phong Nha</strong> <span>Cave Systems & Karsts</span></a></li>';
            echo '<li><a href="' . esc_url(vg_home_url('destinations/hue-travel-guide')) . '"><strong>Hue</strong> <span>Imperial Citadels & Cuisine</span></a></li>';
            echo '</ul>';
            echo '</div>';
            echo '<div class="vg-megamenu-col">';
            echo '<span class="vg-megamenu-heading">South & Islands</span>';
            echo '<ul class="vg-megamenu-links">';
            echo '<li><a href="' . esc_url(vg_home_url('destinations/ho-chi-minh-city-travel-guide')) . '"><strong>Ho Chi Minh City</strong> <span>Metropolis & History</span></a></li>';
            echo '<li><a href="' . esc_url(vg_home_url('destinations/phu-quoc-travel-guide')) . '"><strong>Phu Quoc</strong> <span>Island Finish & Beaches</span></a></li>';
            echo '<li><a href="' . esc_url(vg_home_url('destinations/con-dao-travel-guide')) . '"><strong>Con Dao</strong> <span>Secluded Coastal Retreat</span></a></li>';
            echo '<li><a href="' . esc_url(vg_home_url('destinations/mekong-delta-travel-guide')) . '"><strong>Mekong Delta</strong> <span>Riverways & Distributaries</span></a></li>';
            echo '</ul>';
            echo '</div>';
            echo '</div>';
            echo '<div class="vg-megamenu-footer">';
            echo '<span class="vg-megamenu-meta">GAZETTEER NO. 45 // 282 VETTED FIELD ROUTES</span>';
            echo '<a href="' . esc_url(vg_home_url('destinations')) . '" class="vg-megamenu-action">View Complete Gazetteer &rarr;</a>';
            echo '</div>';
            echo '</div>';
            echo '</li>';
        } elseif ($label === 'Itineraries') {
            echo '<li class="vg-nav-item vg-has-megamenu">';
            printf('<a href="%1$s" class="vg-nav-link">%2$s <span class="vg-nav-caret" aria-hidden="true">&dtrif;</span></a>', esc_url($item['url']), esc_html($label));
            echo '<div class="vg-megamenu-panel vg-megamenu-panel--compact" role="region" aria-label="Curated Itineraries">';
            echo '<div class="vg-megamenu-grid vg-megamenu-grid--2col">';
            echo '<div class="vg-megamenu-col">';
            echo '<span class="vg-megamenu-heading">By Journey Duration</span>';
            echo '<ul class="vg-megamenu-links">';
            echo '<li><a href="' . esc_url(vg_home_url('itineraries/7-days-in-vietnam')) . '"><strong>7 Days</strong> <span>Sprint: North or Central Highlights</span></a></li>';
            echo '<li><a href="' . esc_url(vg_home_url('itineraries/10-days-in-vietnam')) . '"><strong>10 Days</strong> <span>Classic: North, Central, and South</span></a></li>';
            echo '<li><a href="' . esc_url(vg_home_url('itineraries/14-days-in-vietnam')) . '"><strong>14 Days</strong> <span>Measured: Regional Depth with Rest Buffers</span></a></li>';
            echo '<li><a href="' . esc_url(vg_home_url('itineraries/21-days-in-vietnam')) . '"><strong>21 Days</strong> <span>Comprehensive: Mountain Pass to Island Finish</span></a></li>';
            echo '</ul>';
            echo '</div>';
            echo '<div class="vg-megamenu-col">';
            echo '<span class="vg-megamenu-heading">Route Intelligence</span>';
            echo '<div class="vg-megamenu-card">';
            echo '<strong>Route Matcher in 3 Clicks</strong>';
            echo '<p>Calculate real transit hours and realistic VND budgets between regional corridors.</p>';
            echo '<a href="' . esc_url(vg_home_url('itineraries')) . '" class="vg-megamenu-action">Open Itinerary Finder &rarr;</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</li>';
        } else {
            printf('<li class="vg-nav-item"><a href="%1$s" class="vg-nav-link">%2$s</a></li>', esc_url($item['url']), esc_html($label));
        }
    }
    echo '</ul>';
}

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
    echo '<ul class="vg-nav-list">';
    foreach (vg_homepage_data()['navigation'] as $item) {
        printf('<li><a href="%1$s">%2$s</a></li>', esc_url($item['url']), esc_html($item['label']));
    }
    echo '</ul>';
}

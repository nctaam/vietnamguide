<?php
if (! defined('ABSPATH')) { exit; }
require_once get_theme_file_path('/inc/homepage-data.php');
require_once get_theme_file_path('/inc/guide-routing.php');
require_once get_theme_file_path('/inc/guide-content.php');
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
    $version = wp_get_theme()->get('Version');
    wp_enqueue_style('vietnamguide-homepage', get_theme_file_uri('/assets/css/homepage.css'), [], $version);
    wp_enqueue_style('vietnamguide-guide-patterns', get_theme_file_uri('/assets/css/guide-patterns.css'), ['vietnamguide-homepage'], $version);
    wp_enqueue_script('vietnamguide-homepage', get_theme_file_uri('/assets/js/homepage.js'), [], $version, true);
});
function vg_primary_menu_fallback(): void
{
    echo '<ul class="vg-nav-list">';
    foreach (vg_homepage_data()['navigation'] as $item) {
        printf('<li><a href="%1$s">%2$s</a></li>', esc_url($item['url']), esc_html($item['label']));
    }
    echo '</ul>';
}

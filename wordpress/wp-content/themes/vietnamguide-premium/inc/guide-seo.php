<?php
if (! defined('ABSPATH')) {
    exit;
}

function vg_get_default_og_image_url(): string
{
    $defaultImage = get_theme_file_uri('/assets/images/ha-long-bay-vietnam-hero.jpg');

    if (is_singular()) {
        $post = get_post();
        if ($post instanceof WP_Post && ! empty($post->post_content)) {
            $processor = new WP_HTML_Tag_Processor($post->post_content);
            while ($processor->next_token()) {
                if ($processor->get_token_type() === '#tag' && strtolower($processor->get_tag()) === 'img') {
                    $src = $processor->get_attribute('src');
                    if (is_string($src) && $src !== '') {
                        $src = trim($src);
                        if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
                            if (preg_match('/\.([a-zA-Z0-9]+)$/', $src, $m)) {
                                $src = substr($src, 0, -strlen($m[1])) . strtolower($m[1]);
                            }
                            return $src;
                        }
                    }
                }
            }
        }
    }

    return $defaultImage;
}

// Hook into Rank Math OpenGraph image builder
add_action('rank_math/opengraph/facebook/add_additional_images', static function ($image_obj): void {
    if (is_object($image_obj) && method_exists($image_obj, 'has_images') && ! $image_obj->has_images()) {
        $fallback = vg_get_default_og_image_url();
        if ($fallback !== '' && method_exists($image_obj, 'add_image_by_url')) {
            $image_obj->add_image_by_url($fallback);
        }
    }
});

add_action('rank_math/opengraph/twitter/add_additional_images', static function ($image_obj): void {
    if (is_object($image_obj) && method_exists($image_obj, 'has_images') && ! $image_obj->has_images()) {
        $fallback = vg_get_default_og_image_url();
        if ($fallback !== '' && method_exists($image_obj, 'add_image_by_url')) {
            $image_obj->add_image_by_url($fallback);
        }
    }
});

// Category and taxonomy cleanup redirection: route empty category archives to corresponding hubs
add_action('template_redirect', static function (): void {
    if (! is_category() && ! is_tag()) {
        return;
    }

    $currentSlug = '';
    if (is_category()) {
        $cat = get_queried_object();
        if ($cat instanceof WP_Term) {
            $currentSlug = $cat->slug;
        }
    } elseif (is_tag()) {
        $tag = get_queried_object();
        if ($tag instanceof WP_Term) {
            $currentSlug = $tag->slug;
        }
    }

    $routes = [
        'destinations' => home_url('/destinations/'),
        'itineraries'  => home_url('/itineraries/'),
        'plan'         => home_url('/plan/'),
        'compare'      => home_url('/compare/'),
    ];

    $target = $routes[$currentSlug] ?? home_url('/');
    wp_safe_redirect($target, 301);
    exit;
});

// Core Web Vitals & Resource Hints: Preconnect to media CDN and preload LCP hero image
add_action('wp_head', static function (): void {
    echo '<link rel="preconnect" href="https://upload.wikimedia.org" crossorigin>' . "\n";
    echo '<link rel="dns-prefetch" href="https://upload.wikimedia.org">' . "\n";

    if (is_singular()) {
        $heroImage = vg_get_default_og_image_url();
        if ($heroImage !== '') {
            echo '<link rel="preload" as="image" href="' . esc_url($heroImage) . '" fetchpriority="high">' . "\n";
        }
    }
}, 1);


<?php
if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/image-dimensions.php';

/**
 * Resolve natural [width, height] for an image URL from vg_get_known_image_dimensions().
 * Guarantees accurate intrinsic dimensions for Schema.org ImageObject and Google Discover.
 *
 * @return array{0:int,1:int}
 */
function vg_resolve_image_dimensions_by_url(string $url): array
{
    $dimensions = function_exists('vg_get_known_image_dimensions') ? vg_get_known_image_dimensions() : [];
    $cleanUrl = trim($url);

    if ($cleanUrl !== '') {
        if (isset($dimensions[$cleanUrl])) {
            return [(int) $dimensions[$cleanUrl][0], (int) $dimensions[$cleanUrl][1]];
        }
        $decodedUrl = rawurldecode($cleanUrl);
        if (isset($dimensions[$decodedUrl])) {
            return [(int) $dimensions[$decodedUrl][0], (int) $dimensions[$decodedUrl][1]];
        }
        $path = parse_url($cleanUrl, PHP_URL_PATH);
        $basename = is_string($path) && $path !== '' ? basename($path) : '';
        $decodedBasename = rawurldecode($basename);

        if ($basename !== '' && isset($dimensions[$basename])) {
            return [(int) $dimensions[$basename][0], (int) $dimensions[$basename][1]];
        }
        if ($decodedBasename !== '' && isset($dimensions[$decodedBasename])) {
            return [(int) $dimensions[$decodedBasename][0], (int) $dimensions[$decodedBasename][1]];
        }
        if ($basename !== '' && preg_match('/(?:^|\/)(\d+)px-/i', $basename, $m)) {
            $w = (int) $m[1];
            $h = (int) round($w * 2 / 3);
            if ($w > 0 && $h > 0) {
                return [$w, $h];
            }
        }
        if (str_contains($cleanUrl, 'ha-long-bay-vietnam-hero.jpg')) {
            return [1920, 1080];
        }
        if (is_string($path) && str_starts_with($path, '/wp-content/') && defined('ABSPATH') && is_string(ABSPATH) && ABSPATH !== '') {
            $localCandidate = rtrim(ABSPATH, '/\\') . '/' . ltrim(rawurldecode($path), '/');
            if (is_file($localCandidate)) {
                $size = @getimagesize($localCandidate);
                if (is_array($size) && isset($size[0], $size[1]) && (int) $size[0] > 0 && (int) $size[1] > 0) {
                    return [(int) $size[0], (int) $size[1]];
                }
            }
        }
    }

    return [1280, 853];
}

/**
 * Return a verified >= 1920px high-resolution Google Discover image for each of the 12 travel clusters.
 *
 * @return array{url:string,width:int,height:int,caption:string}
 */
function vg_get_cluster_default_discover_image(string $clusterId): array
{
    $heroFallback = get_theme_file_uri('/assets/images/ha-long-bay-vietnam-hero.jpg');
    $map = [
        'northern_triangle' => [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg',
            'width'   => 1920,
            'height'  => 1284,
            'caption' => 'Hoan Kiem Lake and Red River Delta gateway in Hanoi, Vietnam',
        ],
        'ha_long_bay' => [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg/1920px-Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg',
            'width'   => 1920,
            'height'  => 1280,
            'caption' => 'Aerial view of limestone karst formations in Ha Long Bay, Vietnam',
        ],
        'ninh_binh' => [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f4/Mua_Cave%2C_Ninh_Binh%2C_Vietnam%2C_20240202_0926_4964.jpg/1920px-Mua_Cave%2C_Ninh_Binh%2C_Vietnam%2C_20240202_0926_4964.jpg',
            'width'   => 1920,
            'height'  => 1278,
            'caption' => 'Limestone karst peaks and river valley from Mua Cave viewpoint in Ninh Binh, Vietnam',
        ],
        'central_heritage' => [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1920px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg',
            'width'   => 1920,
            'height'  => 1575,
            'caption' => 'UNESCO merchant shophouses and Thu Bon riverfront in Hoi An Ancient Town, Vietnam',
        ],
        'southern_delta' => [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg',
            'width'   => 1920,
            'height'  => 1220,
            'caption' => 'Nguyen Hue walking boulevard and historic City Hall in District 1, Ho Chi Minh City, Vietnam',
        ],
        'northern_highlands' => [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/ff/Rice_terraces_in_Sapa%2C_Vietnam.jpg/1920px-Rice_terraces_in_Sapa%2C_Vietnam.jpg',
            'width'   => 1920,
            'height'  => 1280,
            'caption' => 'Stepped mountain rice terraces in Muong Hoa Valley, Sa Pa, Northern Vietnam',
        ],
        'coastal_islands' => [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg',
            'width'   => 1920,
            'height'  => 1440,
            'caption' => 'Turquoise coastal waters and white sand shoreline at Kem Beach, Phu Quoc Island, Vietnam',
        ],
        'central_highlands' => [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/aa/Da_Lat_-_Xuan_Huong_Lake.jpg/1920px-Da_Lat_-_Xuan_Huong_Lake.jpg',
            'width'   => 1920,
            'height'  => 1275,
            'caption' => 'Crescent waters of Xuan Huong Lake and pine-forested hills on the Langbiang Plateau in Da Lat, Central Highlands, Vietnam',
        ],
        'phong_nha_caves' => [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f1/Son_River-Quang_Binh_province.jpg/1920px-Son_River-Quang_Binh_province.jpg',
            'width'   => 1920,
            'height'  => 1354,
            'caption' => 'Son River and limestone karst mountains in Phong Nha-Ke Bang National Park, Quang Binh, Vietnam',
        ],
        'mekong_delta' => [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1920px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg',
            'width'   => 1920,
            'height'  => 1280,
            'caption' => 'Riverine canal network and wooden sampan boats in Phong Dien, Mekong Delta, Vietnam',
        ],
        'far_north_frontier' => [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/TuSan_Canyon.jpg/1920px-TuSan_Canyon.jpg',
            'width'   => 1920,
            'height'  => 1080,
            'caption' => 'Nho Que River winding through Tu San Canyon on the northern frontier of Vietnam',
        ],
        'national_circuit' => [
            'url'     => $heroFallback,
            'width'   => 1920,
            'height'  => 1080,
            'caption' => 'Vietnam independent travel route planning and logistics guide',
        ],
    ];

    return $map[$clusterId] ?? $map['national_circuit'];
}

/**
 * Resolve the primary Google Discover / Schema.org ImageObject for a route or singular post.
 * Prefers inline content images meeting the >= 1200x675px Discover threshold, falling back to
 * the route's regional travel cluster 1920px hero image.
 *
 * @return array{url:string,width:int,height:int,caption:string}
 */
function vg_resolve_discover_image_object(?string $uriPath = null): array
{
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
                            [$w, $h] = vg_resolve_image_dimensions_by_url($src);
                            if ($w >= 1200 && $h >= 675) {
                                $alt = $processor->get_attribute('alt');
                                $caption = (is_string($alt) && trim($alt) !== '')
                                    ? trim($alt)
                                    : ($post->post_title !== '' ? (string) $post->post_title : 'Vietnam Travel Field Guide');
                                return [
                                    'url'     => $src,
                                    'width'   => $w,
                                    'height'  => $h,
                                    'caption' => $caption,
                                ];
                            }
                        }
                    }
                }
            }
        }
    }

    $resolvedPath = is_string($uriPath) ? trim($uriPath, '/') : '';
    if ($resolvedPath === '' && is_singular()) {
        $post = get_post();
        if ($post instanceof WP_Post && function_exists('vg_get_guide_path') && function_exists('get_page_uri')) {
            $resolvedPath = vg_get_guide_path($post);
        }
        if ($resolvedPath === '' && isset($_SERVER['REQUEST_URI'])) {
            $parsed = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if (is_string($parsed)) {
                $resolvedPath = trim($parsed, '/');
            }
        }
        if ($resolvedPath === '' && $post instanceof WP_Post && ! empty($post->post_name)) {
            $resolvedPath = trim((string) $post->post_name, '/');
        }
    }

    $clusterId = 'national_circuit';
    if ($resolvedPath !== '' && function_exists('vg_find_travel_cluster_by_path')) {
        $cluster = vg_find_travel_cluster_by_path($resolvedPath);
        if (is_array($cluster) && ! empty($cluster['id'])) {
            $clusterId = (string) $cluster['id'];
        }
    }

    return vg_get_cluster_default_discover_image($clusterId);
}

function vg_get_default_og_image_url(?string $uriPath = null): string
{
    $img = vg_resolve_discover_image_object($uriPath);
    return $img['url'];
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

// Enforce Google Search Central snippet directives and defensive indexing.
add_filter('wp_robots', static function (array $robots): array {
    if (is_404() || is_search()) {
        return [
            'noindex'  => true,
            'nofollow' => is_404(),
            'follow'   => is_search(),
        ];
    }

    if (is_singular() || is_front_page() || is_home()) {
        $robots['index']             = true;
        $robots['follow']            = true;
        $robots['max-snippet']       = -1;
        $robots['max-image-preview'] = 'large';
        $robots['max-video-preview'] = -1;
    }

    return $robots;
}, 99);

add_filter('rank_math/frontend/robots', static function ($robots) {
    if (is_404() || is_search()) {
        return [
            'noindex'  => 'noindex',
            'nofollow' => is_404() ? 'nofollow' : 'follow',
        ];
    }

    if (is_array($robots)) {
        unset(
            $robots['index'],
            $robots['follow'],
            $robots['noindex'],
            $robots['nofollow'],
            $robots['max-snippet'],
            $robots['max-image-preview'],
            $robots['max-video-preview']
        );
        $robots = array_merge([
            'index'             => 'index',
            'follow'            => 'follow',
            'max-snippet'       => 'max-snippet:-1',
            'max-image-preview' => 'max-image-preview:large',
            'max-video-preview' => 'max-video-preview:-1',
        ], $robots);
    }

    return $robots;
}, 99);

/**
 * Resolve the canonical route registry record for the current singular request or an explicit path.
 */
function vg_get_current_registry_seo_record(?string $overridePath = null): ?array
{
    if (! function_exists('vg_guide_registry_record')) {
        return null;
    }

    $candidates = [];
    if (is_string($overridePath) && trim($overridePath) !== '') {
        $parsed = parse_url($overridePath, PHP_URL_PATH);
        $candidates[] = trim(is_string($parsed) && $parsed !== '' ? $parsed : $overridePath, '/');
    } elseif (is_singular()) {
        $post = get_post();
        if ($post instanceof WP_Post && function_exists('vg_get_guide_path') && function_exists('get_page_uri')) {
            $candidates[] = vg_get_guide_path($post);
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $parsed = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if (is_string($parsed)) {
                $candidates[] = trim($parsed, '/');
            }
        }
        if (function_exists('get_permalink')) {
            $permalink = get_permalink();
            if (is_string($permalink) && $permalink !== '') {
                $parsed = parse_url($permalink, PHP_URL_PATH);
                if (is_string($parsed)) {
                    $candidates[] = trim($parsed, '/');
                }
            }
        }
    }

    foreach ($candidates as $path) {
        if (! is_string($path) || $path === '') {
            continue;
        }
        $record = vg_guide_registry_record($path);
        if (is_array($record)) {
            return $record;
        }
    }

    return null;
}

// Synchronize frontend SERP <title>, <meta name="description">, and social cards with the 282-route canonical registry.
function vg_filter_canonical_registry_title(string $title): string
{
    $record = vg_get_current_registry_seo_record();
    if (is_array($record) && ! empty($record['title']) && is_string($record['title'])) {
        return $record['title'];
    }
    return $title;
}
add_filter('pre_get_document_title', 'vg_filter_canonical_registry_title', 99);
add_filter('rank_math/frontend/title', 'vg_filter_canonical_registry_title', 99);
add_filter('rank_math/opengraph/facebook/title', 'vg_filter_canonical_registry_title', 99);
add_filter('rank_math/opengraph/twitter/title', 'vg_filter_canonical_registry_title', 99);

function vg_filter_canonical_registry_description(string $description): string
{
    $record = vg_get_current_registry_seo_record();
    if (is_array($record) && ! empty($record['description']) && is_string($record['description'])) {
        return $record['description'];
    }
    return $description;
}
add_filter('rank_math/frontend/description', 'vg_filter_canonical_registry_description', 99);
add_filter('rank_math/opengraph/facebook/description', 'vg_filter_canonical_registry_description', 99);
add_filter('rank_math/opengraph/twitter/description', 'vg_filter_canonical_registry_description', 99);

// Synchronize XML Sitemap <lastmod> with the canonical route registry last_reviewed timestamp.
function vg_format_registry_iso8601(string $dateStr): string
{
    $trimmed = trim($dateStr);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed)) {
        return $trimmed . 'T12:00:00+07:00';
    }
    return date('Y-m-d\TH:i:sP', strtotime($trimmed . ' 12:00:00 +07:00'));
}

function vg_sync_sitemap_entry_lastmod($url, string $type = '', $object = null)
{
    if (! is_array($url) || empty($url['loc']) || ! is_string($url['loc'])) {
        return $url;
    }
    $record = vg_get_current_registry_seo_record($url['loc']);
    if (is_array($record) && ! empty($record['last_reviewed']) && is_string($record['last_reviewed'])) {
        $url['mod'] = vg_format_registry_iso8601($record['last_reviewed']);
    }
    return $url;
}
add_filter('rank_math/sitemap/entry', 'vg_sync_sitemap_entry_lastmod', 20, 3);
add_filter('rank_math/sitemap/enable_caching', '__return_false');

function vg_sync_wp_sitemap_entry_lastmod(array $entry, $post = null): array
{
    if (empty($entry['loc']) || ! is_string($entry['loc'])) {
        return $entry;
    }
    $record = vg_get_current_registry_seo_record($entry['loc']);
    if (is_array($record) && ! empty($record['last_reviewed']) && is_string($record['last_reviewed'])) {
        $entry['lastmod'] = vg_format_registry_iso8601($record['last_reviewed']);
    }
    return $entry;
}
add_filter('wp_sitemaps_posts_entry', 'vg_sync_wp_sitemap_entry_lastmod', 20, 2);

// Core Web Vitals & Resource Hints: Preconnect to media CDN, preload LCP hero image, verification, and PWA manifest
add_action('wp_head', static function (): void {
    $themeUrl = get_stylesheet_directory_uri();
    $googleVerification = defined('VG_GOOGLE_SITE_VERIFICATION')
        ? (string) VG_GOOGLE_SITE_VERIFICATION
        : (string) get_option('vg_google_site_verification', 'G5wVuwqeUiubxqR-z_1BOA5opV1xwI4PKy-piHsN6Xc');

    $headTags = [];
    if (! defined('RANK_MATH_VERSION') && ! class_exists('RankMath')) {
        if (is_404() || is_search()) {
            $headTags[] = '<meta name="robots" content="' . (is_404() ? 'noindex, nofollow' : 'noindex, follow') . '">';
        } elseif (is_singular() || is_front_page() || is_home()) {
            $headTags[] = '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">';
            $seoRecord = vg_get_current_registry_seo_record();
            if (is_array($seoRecord) && ! empty($seoRecord['description'])) {
                $headTags[] = '<meta name="description" content="' . esc_attr((string) $seoRecord['description']) . '">';
            }
        }
    }

    if ($googleVerification !== '') {
        $headTags[] = '<meta name="google-site-verification" content="' . esc_attr($googleVerification) . '">';
    }

    $bingId = defined('VG_BING_VERIFICATION_ID')
        ? (string) VG_BING_VERIFICATION_ID
        : (string) get_option('vg_bing_verification_id', '852EF594B29D4DA5A639612DA3430B0F');
    if ($bingId !== '') {
        $headTags[] = '<meta name="msvalidate.01" content="' . esc_attr($bingId) . '">';
    }

    $headTags = array_merge($headTags, [
        '<meta name="theme-color" content="#0e6f5c">',
        '<meta name="apple-mobile-web-app-capable" content="yes">',
        '<meta name="apple-mobile-web-app-status-bar-style" content="default">',
        '<meta name="apple-mobile-web-app-title" content="VietnamGuide">',
        '<link rel="manifest" href="' . esc_url($themeUrl . '/site.webmanifest') . '">',
        '<link rel="apple-touch-icon" href="' . esc_url($themeUrl . '/assets/images/vg-icon-192.png') . '">',
        '<link rel="icon" type="image/svg+xml" href="' . esc_url($themeUrl . '/assets/images/vg-icon.svg') . '">',
        '<link rel="preconnect" href="https://upload.wikimedia.org" crossorigin>',
        '<link rel="dns-prefetch" href="https://upload.wikimedia.org">',
        '<link rel="alternate" type="application/rss+xml" title="' . esc_attr__('VietnamGuide - Travel Planning Advisory Feed', 'vietnamguide-premium') . '" href="' . esc_url(home_url('/feed/')) . '">',
    ]);

    if (is_singular()) {
        $heroImage = vg_get_default_og_image_url();
        if ($heroImage !== '') {
            $headTags[] = '<link rel="preload" as="image" href="' . esc_url($heroImage) . '" fetchpriority="high">';
        }
    } elseif (is_front_page() || is_home()) {
        $homeHeroWebp = $themeUrl . '/assets/images/home-hero.webp';
        $headTags[] = '<link rel="preload" as="image" type="image/webp" href="' . esc_url($homeHeroWebp) . '" fetchpriority="high">';
    }

    echo implode("\n", $headTags) . "\n";
}, 1);

// Register PWA Service Worker for offline field guide, mobile install prompt, and print handling
add_action('wp_footer', static function (): void {
    $swUrl = esc_url(home_url('/sw.js'));
    ?>
    <aside id="vg-pwa-banner" class="vg-pwa-banner" role="region" aria-label="<?php esc_attr_e('Install Vietnam Travel Field Guide', 'vietnamguide-premium'); ?>" style="display:none;">
        <div class="vg-pwa-banner-inner">
            <div class="vg-pwa-banner-text">
                <span class="vg-pwa-badge"><?php esc_html_e('Offline Travel Guide', 'vietnamguide-premium'); ?></span>
                <strong class="vg-pwa-title"><?php esc_html_e('Install VietnamGuide Field Guide', 'vietnamguide-premium'); ?></strong>
                <p class="vg-pwa-desc"><?php esc_html_e('Access offline route itineraries, arrival checklists, and emergency tools without cellular coverage.', 'vietnamguide-premium'); ?></p>
            </div>
            <div class="vg-pwa-banner-actions">
                <button type="button" id="vg-pwa-install-btn" class="vg-pwa-btn-install"><?php esc_html_e('Install App', 'vietnamguide-premium'); ?></button>
                <button type="button" id="vg-pwa-dismiss-btn" class="vg-pwa-btn-dismiss" aria-label="<?php esc_attr_e('Dismiss install banner for 30 days', 'vietnamguide-premium'); ?>"><?php esc_html_e('Dismiss', 'vietnamguide-premium'); ?></button>
            </div>
        </div>
    </aside>
    <style>
    .vg-pwa-banner {
        position: fixed;
        bottom: 16px;
        left: 16px;
        right: 16px;
        max-width: 520px;
        margin: 0 auto;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        padding: 16px;
        z-index: 99999;
        font-family: inherit;
        animation: vgSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes vgSlideUp {
        from { transform: translateY(100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .vg-pwa-banner-inner {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .vg-pwa-badge {
        display: inline-block;
        background: #f3d484;
        color: #7e5802;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 2px 8px;
        border-radius: 9999px;
        margin-bottom: 4px;
    }
    .vg-pwa-title {
        display: block;
        font-size: 0.95rem;
        color: #1a365d;
        line-height: 1.3;
    }
    .vg-pwa-desc {
        margin: 4px 0 0 0;
        font-size: 0.8rem;
        color: #475569;
        line-height: 1.4;
    }
    .vg-pwa-banner-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: flex-end;
    }
    .vg-pwa-btn-install {
        background: #1a365d;
        color: #ffffff;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .vg-pwa-btn-install:hover,
    .vg-pwa-btn-install:focus {
        background: #2a4365;
    }
    .vg-pwa-btn-dismiss {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 0.82rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .vg-pwa-btn-dismiss:hover,
    .vg-pwa-btn-dismiss:focus {
        background: #e2e8f0;
        color: #1e293b;
    }
    @media (min-width: 640px) {
        .vg-pwa-banner-inner {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
        .vg-pwa-banner-actions {
            flex-shrink: 0;
        }
    }
    </style>
    <script>
    (function () {
        if ("serviceWorker" in navigator && (window.location.protocol === "https:" || window.location.hostname === "localhost")) {
            window.addEventListener("load", function () {
                navigator.serviceWorker.register("<?php echo $swUrl; ?>", { scope: "/" }).catch(function () {});
            });
        }

        document.addEventListener("click", function (e) {
            var btn = e.target && e.target.closest ? e.target.closest("[data-vg-print]") : null;
            if (btn) { window.print(); }
        });

        var deferredPrompt = null;
        var banner = document.getElementById("vg-pwa-banner");
        var installBtn = document.getElementById("vg-pwa-install-btn");
        var dismissBtn = document.getElementById("vg-pwa-dismiss-btn");
        var STORAGE_KEY = "vg_pwa_dismissed";
        var DISMISS_DAYS = 30;

        function isDismissed() {
            try {
                var dismissedAt = localStorage.getItem(STORAGE_KEY);
                if (!dismissedAt) return false;
                var ageMs = Date.now() - parseInt(dismissedAt, 10);
                return ageMs < DISMISS_DAYS * 86400000;
            } catch (err) {
                return false;
            }
        }

        function setDismissed() {
            try {
                localStorage.setItem(STORAGE_KEY, Date.now().toString());
            } catch (err) {}
        }

        window.addEventListener("beforeinstallprompt", function (e) {
            e.preventDefault();
            deferredPrompt = e;
            if (banner && !isDismissed()) {
                banner.style.display = "block";
            }
        });

        if (installBtn) {
            installBtn.addEventListener("click", function () {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then(function (choice) {
                        if (choice.outcome === "accepted") {
                            setDismissed();
                        }
                        deferredPrompt = null;
                        if (banner) banner.style.display = "none";
                    });
                }
            });
        }

        if (dismissBtn) {
            dismissBtn.addEventListener("click", function () {
                setDismissed();
                if (banner) banner.style.display = "none";
            });
        }
    })();
    </script>
    <?php
}, 99);

/**
 * ==========================================================================
 * Stage 20: Rich Travel Structured Data & Contextual Destination Hub Linking
 * ==========================================================================
 * Provides rich Schema.org entities (TouristDestination, TouristTrip, TravelAction)
 * complementary to Rank Math's graph, and renders high-converting next-step
 * journey links between connected destination clusters.
 */

/**
 * Authoritative registry of connected destination clusters across Vietnam.
 */
function vg_get_travel_clusters_registry(): array
{
    static $registry = null;
    if ($registry !== null) {
        return $registry;
    }

    $registry = [
        'northern_triangle' => [
            'id'                 => 'northern_triangle',
            'name'               => 'Northern Golden Triangle',
            'hub'                => 'Hanoi',
            'hub_url'            => '/destinations/hanoi-travel-guide/',
            'destination_schema' => [
                'addressRegion' => 'Hanoi',
                'geo'          => ['latitude' => 21.0285, 'longitude' => 105.8542],
                'name'         => 'Hanoi',
                'description'  => 'Vietnam\'s 1,000-year-old capital city, featuring the atmospheric Old Quarter, French colonial boulevards, vibrant street-food culture, and seamless overland connectivity to Ha Long Bay and Ninh Binh.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q1858',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Hanoi',
                'tourist_type' => ['Cultural tourism', 'Culinary tourism', 'Urban exploration', 'Historical tourism'],
                'attractions'  => [
                    ['name' => 'Old Quarter', 'wikidata' => 'https://www.wikidata.org/wiki/Q10808390'],
                    ['name' => 'Hoan Kiem Lake', 'wikidata' => 'https://www.wikidata.org/wiki/Q844112'],
                    ['name' => 'Temple of Literature', 'wikidata' => 'https://www.wikidata.org/wiki/Q1081308'],
                    ['name' => 'French Quarter', 'wikidata' => 'https://www.wikidata.org/wiki/Q10808390'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Hanoi to Ha Long Bay & Ninh Binh Northern Triangle Journey',
                'description'  => 'The quintessential northern Vietnam travel circuit linking the urban cultural heritage of Hanoi with the maritime limestone karsts of Ha Long Bay and the serene terrestrial river valleys of Ninh Binh.',
                'tourist_type' => ['First-time visitors', 'Multi-day travelers', 'Cultural explorers'],
                'stops'        => [
                    ['name' => 'Hanoi', 'url' => '/destinations/hanoi-travel-guide/'],
                    ['name' => 'Ha Long Bay & Lan Ha Bay', 'url' => '/destinations/ha-long-bay-travel-guide/'],
                    ['name' => 'Ninh Binh (Trang An & Tam Coc)', 'url' => '/destinations/ninh-binh-travel-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Hanoi to Ha Long Bay & Ninh Binh Route',
                'method' => 'Expressway & Limousine Transit',
            ],
            'journey_links' => [
                [
                    'name'    => 'Ha Long Bay & Lan Ha Bay',
                    'url'     => '/destinations/ha-long-bay-travel-guide/',
                    'transit' => '2.5 hrs via Expressway',
                    'badge'   => 'Limestone Karst Cruise',
                    'desc'    => 'Overnight luxury cruising, sea kayaking among karst towers, and emerald waters.',
                ],
                [
                    'name'    => 'Ninh Binh (Trang An & Tam Coc)',
                    'url'     => '/destinations/ninh-binh-travel-guide/',
                    'transit' => '1.5 hrs via Train or Van',
                    'badge'   => 'Terrestrial Karst Valley',
                    'desc'    => 'Paddleboat river grottoes, Bich Dong pagoda, and cycling through tranquil rice paddies.',
                ],
                [
                    'name'    => 'Sa Pa & Tonkinese Alps',
                    'url'     => '/destinations/sapa-travel-guide/',
                    'transit' => '5.5 hrs via Express Highway',
                    'badge'   => 'Highland Rice Terraces',
                    'desc'    => 'Spectacular Fansipan peaks, ethnic hill-tribe villages, and high-altitude trekking.',
                ],
            ],
        ],

        'ha_long_bay' => [
            'id'                 => 'ha_long_bay',
            'name'               => 'Gulf of Tonkin Karst Archipelago',
            'hub'                => 'Ha Long Bay',
            'hub_url'            => '/destinations/ha-long-bay-travel-guide/',
            'destination_schema' => [
                'addressRegion' => 'Quang Ninh',
                'geo'          => ['latitude' => 20.9101, 'longitude' => 107.1839],
                'name'         => 'Ha Long Bay & Lan Ha Bay',
                'description'  => 'UNESCO World Heritage marine wonder characterized by thousands of soaring limestone karsts, secluded floating fishing villages, and emerald sea channels.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q190128',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/H%E1%BA%A1_Long_Bay',
                'tourist_type' => ['Marine cruise tourism', 'Nature & landscapes', 'Sea kayaking', 'UNESCO heritage'],
                'attractions'  => [
                    ['name' => 'Ha Long Bay', 'wikidata' => 'https://www.wikidata.org/wiki/Q190128'],
                    ['name' => 'Lan Ha Bay', 'wikidata' => 'https://www.wikidata.org/wiki/Q3216853'],
                    ['name' => 'Cat Ba Island', 'wikidata' => 'https://www.wikidata.org/wiki/Q1936306'],
                    ['name' => 'Bai Tu Long Bay', 'wikidata' => 'https://www.wikidata.org/wiki/Q804153'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Ha Long Bay to Ninh Binh & Hanoi Maritime Circuit',
                'description'  => 'Direct connection bridging maritime limestone towers with terrestrial paddleboat valleys and capital culture without backtracking.',
                'tourist_type' => ['Nature lovers', 'Cruise travelers', 'Scenic photographers'],
                'stops'        => [
                    ['name' => 'Ha Long Bay', 'url' => '/destinations/ha-long-bay-travel-guide/'],
                    ['name' => 'Ninh Binh', 'url' => '/destinations/ninh-binh-travel-guide/'],
                    ['name' => 'Hanoi', 'url' => '/destinations/hanoi-travel-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Ha Long Bay to Ninh Binh & Hanoi Route',
                'method' => 'Direct Coastal Highway & Cruise Transfer',
            ],
            'journey_links' => [
                [
                    'name'    => 'Ninh Binh Countryside',
                    'url'     => '/destinations/ninh-binh-travel-guide/',
                    'transit' => '3 hrs direct highway transfer',
                    'badge'   => 'Terrestrial Karsts',
                    'desc'    => 'Connect maritime limestone towers with terrestrial paddleboat grottoes without returning to Hanoi.',
                ],
                [
                    'name'    => 'Hanoi Capital & Old Quarter',
                    'url'     => '/destinations/hanoi-travel-guide/',
                    'transit' => '2.5 hrs via Expressway',
                    'badge'   => 'Capital Cultural Hub',
                    'desc'    => 'Return to the capital for street-food recovery, night markets, and onward air connections.',
                ],
                [
                    'name'    => 'Cat Ba Island & National Park',
                    'url'     => '/destinations/cat-ba-travel-guide/',
                    'transit' => '1 hr ferry from bay port',
                    'badge'   => 'Island Adventure',
                    'desc'    => 'Hiking coastal rainforest trails, rock climbing limestone crags, and Cannon Fort sunsets.',
                ],
                [
                    'name'    => 'Bai Tu Long Bay Pristine Karsts',
                    'url'     => '/destinations/bai-tu-long-bay-guide/',
                    'transit' => '1.5 hrs luxury cruise transfer',
                    'badge'   => 'Pristine Karst Bay',
                    'desc'    => 'Serene uncrowded waters, Thien Canh Son cave, and tranquil kayaking.',
                ],
            ],
        ],

        'ninh_binh' => [
            'id'                 => 'ninh_binh',
            'name'               => 'Ninh Binh Karst Countryside',
            'hub'                => 'Ninh Binh',
            'hub_url'            => '/destinations/ninh-binh-travel-guide/',
            'destination_schema' => [
                'addressRegion' => 'Ninh Binh',
                'geo'          => ['latitude' => 20.2506, 'longitude' => 105.9745],
                'name'         => 'Ninh Binh (Trang An & Tam Coc)',
                'description'  => 'Often celebrated as "Ha Long Bay on land," Ninh Binh enchants travelers with limestone peaks emerging from emerald rice fields, UNESCO paddleboat grottoes, and ancient dynastic temples.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q36352',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Ninh_B%C3%ACnh_province',
                'tourist_type' => ['Ecotourism', 'Rural exploration', 'UNESCO landscape', 'Cycling tourism'],
                'attractions'  => [
                    ['name' => 'Trang An Landscape Complex', 'wikidata' => 'https://www.wikidata.org/wiki/Q10828551'],
                    ['name' => 'Tam Coc', 'wikidata' => 'https://www.wikidata.org/wiki/Q7680468'],
                    ['name' => 'Mua Caves Viewpoint', 'wikidata' => 'https://www.wikidata.org/wiki/Q36359'],
                    ['name' => 'Bich Dong Pagoda', 'wikidata' => 'https://www.wikidata.org/wiki/Q36359'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Ninh Binh to Ha Long Bay & Central Vietnam Gateway',
                'description'  => 'A versatile journey linking pastoral river valleys directly with Gulf of Tonkin cruises or overnight trains south toward Phong Nha and Hue.',
                'tourist_type' => ['Slow travelers', 'Adventure seekers', 'Photographers'],
                'stops'        => [
                    ['name' => 'Ninh Binh', 'url' => '/destinations/ninh-binh-travel-guide/'],
                    ['name' => 'Ha Long Bay', 'url' => '/destinations/ha-long-bay-travel-guide/'],
                    ['name' => 'Phong Nha Caves', 'url' => '/destinations/phong-nha-travel-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Ninh Binh Onward Journey',
                'method' => 'Highway Express & Reunification Express Rail',
            ],
            'journey_links' => [
                [
                    'name'    => 'Ha Long Bay Overnight Cruise',
                    'url'     => '/destinations/ha-long-bay-travel-guide/',
                    'transit' => '3 hrs via Highway 10',
                    'badge'   => 'Marine Karsts',
                    'desc'    => 'Direct highway transit from rice valleys to overnight luxury karst cruising.',
                ],
                [
                    'name'    => 'Phong Nha Cave Kingdom',
                    'url'     => '/destinations/phong-nha-travel-guide/',
                    'transit' => '7 hrs via Sleeper Train',
                    'badge'   => 'Cave Exploration',
                    'desc'    => 'Transition south toward the world\'s most spectacular underground river cave systems.',
                ],
                [
                    'name'    => 'Hanoi Old Quarter',
                    'url'     => '/destinations/hanoi-travel-guide/',
                    'transit' => '1.5 hrs via Limousine/Train',
                    'badge'   => 'Capital Hub',
                    'desc'    => 'Fast return to Hanoi for international flight departures or northern mountain routes.',
                ],
                [
                    'name'    => 'Pu Luong Nature Reserve',
                    'url'     => '/destinations/pu-luong-travel-guide/',
                    'transit' => '3 hrs via scenic highway',
                    'badge'   => 'Terraced Eco Valley',
                    'desc'    => 'Escape crowds into lush terraced rice valleys, traditional stilt houses, and natural bamboo waterwheels.',
                ],
            ],
        ],

        'central_heritage' => [
            'id'                 => 'central_heritage',
            'name'               => 'Central Heritage Corridor',
            'hub'                => 'Da Nang',
            'hub_url'            => '/destinations/da-nang-travel-guide/',
            'destination_schema' => [
                'addressRegion' => 'Da Nang, Hoi An & Thua Thien Hue',
                'geo'          => ['latitude' => 15.8801, 'longitude' => 108.338],
                'name'         => 'Da Nang, Hoi An & Hue',
                'description'  => 'Central Vietnam\'s cultural and coastal heartland, spanning the royal palaces of Hue, the lantern-lit UNESCO trading lanes of Hoi An, and the beaches and Marble Mountains of Da Nang.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q25282',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Da_Nang',
                'tourist_type' => ['Heritage tourism', 'Beach resort travel', 'Culinary exploration', 'Cultural travel'],
                'attractions'  => [
                    ['name' => 'Hoi An Ancient Town', 'wikidata' => 'https://www.wikidata.org/wiki/Q36167'],
                    ['name' => 'Imperial City of Hue', 'wikidata' => 'https://www.wikidata.org/wiki/Q200257'],
                    ['name' => 'My Khe Beach & Marble Mountains', 'wikidata' => 'https://www.wikidata.org/wiki/Q25282'],
                    ['name' => 'Hai Van Pass', 'wikidata' => 'https://www.wikidata.org/wiki/Q1005837'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Da Nang to Hoi An & Hue Central Heritage Corridor',
                'description'  => 'A scenic coastal and imperial cultural journey traversing the Hai Van Pass, UNESCO ancient merchant quarter of Hoi An, and royal Nguyen dynasty monuments in Hue.',
                'tourist_type' => ['Heritage travelers', 'Scenic road trippers', 'Food & culture enthusiasts'],
                'stops'        => [
                    ['name' => 'Da Nang Coastal Gateway', 'url' => '/destinations/da-nang-travel-guide/'],
                    ['name' => 'Hoi An Ancient Town', 'url' => '/destinations/hoi-an-ancient-town-guide/'],
                    ['name' => 'Hue Imperial City', 'url' => '/destinations/hue-imperial-city-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Central Heritage Corridor (Da Nang -> Hoi An & Hue)',
                'method' => 'Coastal Scenic Highway & Hai Van Pass Route',
            ],
            'journey_links' => [
                [
                    'name'    => 'Hoi An Ancient Town',
                    'url'     => '/destinations/hoi-an-ancient-town-guide/',
                    'transit' => '45 mins via Coastal Road',
                    'badge'   => 'UNESCO Living Heritage',
                    'desc'    => 'Yellow-ochre alleyways, night lantern riverboats, custom tailoring, and riverside dining.',
                ],
                [
                    'name'    => 'Hue Imperial Citadel & Tombs',
                    'url'     => '/destinations/hue-imperial-city-guide/',
                    'transit' => '2 hrs via Hai Van Pass',
                    'badge'   => 'Imperial Dynasty',
                    'desc'    => 'Nguyen dynasty royal palaces, ornate emperor tombs, and Perfume River dragon boat rides.',
                ],
                [
                    'name'    => 'Da Nang City & My Khe Beach',
                    'url'     => '/destinations/da-nang-travel-guide/',
                    'transit' => '45 mins from Hoi An / 2 hrs from Hue',
                    'badge'   => 'Coastal Gateway',
                    'desc'    => 'Modern international airport, Marble Mountains, Ba Na Hills, and fresh seafood dining.',
                ],
                [
                    'name'    => 'Cham Islands Marine Biosphere',
                    'url'     => '/destinations/cham-islands-travel-guide/',
                    'transit' => '30 min speedboat from Cua Dai',
                    'badge'   => 'Marine Sanctuary',
                    'desc'    => 'Coral reef snorkeling, quiet sandy coves, and fresh island seafood on an offshore retreat.',
                ],
                [
                    'name'    => 'Phong Nha Caves & Karsts',
                    'url'     => '/destinations/phong-nha-travel-guide/',
                    'transit' => '4 hrs north of Hue via Rail/Bus',
                    'badge'   => 'Cave Kingdom',
                    'desc'    => 'Venture underground into cavernous river grottos, Paradise Cave, and Son Doong expedition territory.',
                ],
            ],
        ],

        'southern_delta' => [
            'id'                 => 'southern_delta',
            'name'               => 'Southern Riverine & Heritage Route',
            'hub'                => 'Ho Chi Minh City',
            'hub_url'            => '/destinations/ho-chi-minh-city-travel-guide/',
            'destination_schema' => [
                'addressRegion' => 'Ho Chi Minh City & Mekong Delta',
                'geo'          => ['latitude' => 10.8231, 'longitude' => 106.6297],
                'name'         => 'Ho Chi Minh City & Mekong Delta',
                'description'  => 'Vietnam\'s bustling southern economic powerhouse, famed for French colonial landmarks, Saigon street-food alleys, wartime history, and gateway to the waterways of the Mekong Delta.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q1854',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Ho_Chi_Minh_City',
                'tourist_type' => ['Urban exploration', 'Culinary tourism', 'Historical tourism', 'Riverine ecotourism'],
                'attractions'  => [
                    ['name' => 'War Remnants Museum', 'wikidata' => 'https://www.wikidata.org/wiki/Q1854'],
                    ['name' => 'Cu Chi Tunnels', 'wikidata' => 'https://www.wikidata.org/wiki/Q192935'],
                    ['name' => 'Mekong Delta Waterways', 'wikidata' => 'https://www.wikidata.org/wiki/Q1052867'],
                    ['name' => 'Ben Thanh Market', 'wikidata' => 'https://www.wikidata.org/wiki/Q1854'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Ho Chi Minh City to Mekong Delta & Cu Chi Southern Journey',
                'description'  => 'A southern Vietnam route connecting the energy of Saigon with the historical Cu Chi tunnel networks and the tranquil sampan waterways of the Mekong Delta.',
                'tourist_type' => ['Independent travelers', 'Culture & history seekers', 'River explorers'],
                'stops'        => [
                    ['name' => 'Ho Chi Minh City', 'url' => '/destinations/ho-chi-minh-city-travel-guide/'],
                    ['name' => 'Mekong Delta', 'url' => '/destinations/mekong-delta-travel-guide/'],
                    ['name' => 'Cu Chi Historic Tunnels', 'url' => '/destinations/best-day-trips-from-ho-chi-minh-city/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Ho Chi Minh City to Mekong Delta & Cu Chi Journey',
                'method' => 'Expressway, Speedboat & River Sampan',
            ],
            'journey_links' => [
                [
                    'name'    => 'Mekong Delta River Life',
                    'url'     => '/destinations/mekong-delta-travel-guide/',
                    'transit' => '2 hrs via Expressway',
                    'badge'   => 'Waterway Ecology',
                    'desc'    => 'Sampan rides under nipa palms, Cai Rang floating market, and rural orchard homestays.',
                ],
                [
                    'name'    => 'Cu Chi Historic Tunnels',
                    'url'     => '/destinations/best-day-trips-from-ho-chi-minh-city/',
                    'transit' => '1.5 hrs via Speedboat/Car',
                    'badge'   => 'Living History',
                    'desc'    => 'Walk through the legendary underground resistance complex and historic forest trails.',
                ],
                [
                    'name'    => 'Phu Quoc Tropical Island',
                    'url'     => '/destinations/phu-quoc-travel-guide/',
                    'transit' => '55 min flight from SGN',
                    'badge'   => 'Island Sanctuary',
                    'desc'    => 'Powder-white beaches, sunset cocktails, night market seafood, and seaside luxury.',
                ],
                [
                    'name'    => 'Ho Chi Minh City Gateway',
                    'url'     => '/destinations/ho-chi-minh-city-travel-guide/',
                    'transit' => 'Return overland transfer',
                    'badge'   => 'Southern Hub',
                    'desc'    => 'Return from the delta to Saigon for rooftop dining, heritage museums, and night markets.',
                ],
            ],
        ],

        'northern_highlands' => [
            'id'                 => 'northern_highlands',
            'name'               => 'Northern Highlands Frontier',
            'hub'                => 'Sa Pa & Ha Giang',
            'hub_url'            => '/destinations/sapa-travel-guide/',
            'destination_schema' => [
                'addressRegion' => 'Lao Cai & Ha Giang',
                'geo'          => ['latitude' => 22.3364, 'longitude' => 103.8438],
                'name'         => 'Sa Pa & Northern Highlands',
                'description'  => 'Vietnam\'s dramatic northern mountain frontier, showcasing Fansipan summit, sculpted rice terraces, colorful ethnic hill-tribe markets, and the epic Ha Giang karst loop.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q36384',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Sa_Pa',
                'tourist_type' => ['Mountain trekking', 'Adventure travel', 'Ethnic cultural immersion', 'Landscape photography'],
                'attractions'  => [
                    ['name' => 'Fansipan Summit', 'wikidata' => 'https://www.wikidata.org/wiki/Q1005391'],
                    ['name' => 'Ma Pi Leng Pass', 'wikidata' => 'https://www.wikidata.org/wiki/Q36352'],
                    ['name' => 'Mu Cang Chai Terraces', 'wikidata' => 'https://www.wikidata.org/wiki/Q6930267'],
                    ['name' => 'Dong Van Karst Plateau', 'wikidata' => 'https://www.wikidata.org/wiki/Q36352'],
                    ['name' => 'Pu Luong Nature Reserve', 'wikidata' => 'https://www.wikidata.org/wiki/Q7257418'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Northern Highlands & Frontier Loop Circuit',
                'description'  => 'An overland mountain expedition through high alpine passes, terraced amphitheaters, and remote ethnic markets across Sa Pa, Ha Giang, and Mu Cang Chai.',
                'tourist_type' => ['Trekking enthusiasts', 'Motorcycle route riders', 'Off-the-beaten-path travelers'],
                'stops'        => [
                    ['name' => 'Sa Pa', 'url' => '/destinations/sapa-travel-guide/'],
                    ['name' => 'Ha Giang Loop', 'url' => '/destinations/ha-giang-loop-planning-guide/'],
                    ['name' => 'Mu Cang Chai', 'url' => '/destinations/mu-cang-chai-travel-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Northern Highlands & Mountain Terraces',
                'method' => 'Mountain Pass Highways & Guided Overland Transfer',
            ],
            'journey_links' => [
                [
                    'name'    => 'Ha Giang Extreme Loop',
                    'url'     => '/destinations/ha-giang-loop-planning-guide/',
                    'transit' => '6 hrs via QL279 Mountain Road',
                    'badge'   => 'Epic Frontier',
                    'desc'    => 'Ride through Ma Pi Leng pass, Dong Van Karst Plateau, and rugged ethnic frontiers.',
                ],
                [
                    'name'    => 'Mu Cang Chai Terraced Valleys',
                    'url'     => '/destinations/mu-cang-chai-travel-guide/',
                    'transit' => '3.5 hrs via O Quy Ho Pass',
                    'badge'   => 'Rice Amphitheaters',
                    'desc'    => 'Witness Vietnam\'s most photogenic golden terraced hillsides and serene ethnic homestays.',
                ],
                [
                    'name'    => 'Pu Luong Nature Reserve',
                    'url'     => '/destinations/pu-luong-travel-guide/',
                    'transit' => '3.5 hrs via Mai Chau valley',
                    'badge'   => 'Eco Valley Retreat',
                    'desc'    => 'Tranquil waterwheels, terraced limestone valleys, and traditional Thai stilt house homestays.',
                ],
                [
                    'name'    => 'Sa Pa Alpine Town',
                    'url'     => '/destinations/sapa-travel-guide/',
                    'transit' => '5.5 hrs from Hanoi',
                    'badge'   => 'Alpine Trekking',
                    'desc'    => 'Summit Fansipan peak via cable car or trek through Muong Hoa valley ethnic villages.',
                ],
                [
                    'name'    => 'Hanoi Capital Base',
                    'url'     => '/destinations/hanoi-travel-guide/',
                    'transit' => '5.5 hrs via Expressway/Train',
                    'badge'   => 'Recovery Hub',
                    'desc'    => 'Return to Hanoi for warm baths, foot massages, and Old Quarter street-food comfort.',
                ],
            ],
        ],

        'coastal_islands' => [
            'id'                 => 'coastal_islands',
            'name'               => 'Coastal & Island Sanctuaries',
            'hub'                => 'Phu Quoc & Con Dao',
            'hub_url'            => '/destinations/phu-quoc-travel-guide/',
            'destination_schema' => [
                'addressRegion' => 'Kien Giang & Ba Ria - Vung Tau',
                'geo'          => ['latitude' => 10.2899, 'longitude' => 103.984],
                'name'         => 'Phu Quoc, Con Dao & Central Coast',
                'description'  => 'Vietnam\'s idyllic island retreats and sun-drenched coastal havens, offering powder-white sands, coral reef biodiversity, and calm turquoise seas.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q223145',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Ph%C3%BA_Qu%E1%BB%91c',
                'tourist_type' => ['Beach resort tourism', 'Marine biodiversity', 'Relaxation', 'Scuba diving'],
                'attractions'  => [
                    ['name' => 'Phu Quoc National Park', 'wikidata' => 'https://www.wikidata.org/wiki/Q223145'],
                    ['name' => 'Con Dao Marine Park', 'wikidata' => 'https://www.wikidata.org/wiki/Q1118128'],
                    ['name' => 'Quy Nhon Coast', 'wikidata' => 'https://www.wikidata.org/wiki/Q26577'],
                    ['name' => 'Ly Son Island', 'wikidata' => 'https://www.wikidata.org/wiki/Q10788220'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Vietnam Coastal & Island Sanctuary Hop',
                'description'  => 'A relaxing journey linking tranquil coastal bays in Quy Nhon with the turquoise waters and marine national parks of Phu Quoc and Con Dao.',
                'tourist_type' => ['Beach lovers', 'Honeymooners', 'Marine life enthusiasts'],
                'stops'        => [
                    ['name' => 'Phu Quoc Island', 'url' => '/destinations/phu-quoc-travel-guide/'],
                    ['name' => 'Con Dao Archipelago', 'url' => '/destinations/con-dao-travel-guide/'],
                    ['name' => 'Quy Nhon Coast', 'url' => '/destinations/quy-nhon-travel-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Island & Coastal Sanctuaries',
                'method' => 'Coastal Domestic Flights & Speed Ferry Transfers',
            ],
            'journey_links' => [
                [
                    'name'    => 'Phu Quoc Tropical Island',
                    'url'     => '/destinations/phu-quoc-travel-guide/',
                    'transit' => '55 min flight from SGN',
                    'badge'   => 'Tropical Sanctuary',
                    'desc'    => 'Powder-white beaches, sunset cocktails, night market seafood, and seaside luxury.',
                ],
                [
                    'name'    => 'Con Dao Protected Archipelago',
                    'url'     => '/destinations/con-dao-travel-guide/',
                    'transit' => 'Flight via SGN or Can Tho ferry',
                    'badge'   => 'Eco Sanctuary',
                    'desc'    => 'Pristine marine national park, green sea turtle nesting grounds, and secluded serenity.',
                ],
                [
                    'name'    => 'Quy Nhon Turquoise Coast',
                    'url'     => '/destinations/quy-nhon-travel-guide/',
                    'transit' => '1 hr flight or coastal train',
                    'badge'   => 'Untouched Coast',
                    'desc'    => 'Uncrowded sandy bays, fresh seaside fishing shacks, and ancient Cham cliff towers.',
                ],
                [
                    'name'    => 'Ly Son Volcanic Island',
                    'url'     => '/destinations/ly-son-travel-guide/',
                    'transit' => '35 min speedboat from Sa Ky Port',
                    'badge'   => 'Volcanic Island',
                    'desc'    => 'Extinct volcanic craters, garlic fields, turquoise crater seas, and Hang Pagoda sea cliff.',
                ],
                [
                    'name'    => 'Ho Chi Minh City Gateway',
                    'url'     => '/destinations/ho-chi-minh-city-travel-guide/',
                    'transit' => '55 min flight',
                    'badge'   => 'Southern Hub',
                    'desc'    => 'Short hopper flight back to Saigon for rooftop dining, shopping, and international departures.',
                ],
            ],
        ],

        'central_highlands' => [
            'id'                 => 'central_highlands',
            'name'               => 'Central Highlands & Pine Plateaus',
            'hub'                => 'Da Lat & Buon Ma Thuot',
            'hub_url'            => '/destinations/da-lat-travel-guide/',
            'destination_schema' => [
                'addressRegion' => 'Lam Dong, Dak Lak, Gia Lai & Kon Tum',
                'geo'          => ['latitude' => 11.9404, 'longitude' => 108.4583],
                'name'         => 'Da Lat & Central Highlands',
                'description'  => 'Vietnam\'s cool-climate coffee country, linking French alpine villas in Da Lat with Buon Ma Thuot\'s volcanic basalt plantations, Kon Tum\'s indigenous communal houses, and the pine forests of Mang Den.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q1140648',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Central_Highlands_(Vietnam)',
                'tourist_type' => ['Highland agriculture & coffee tourism', 'Cool-climate alpine retreats', 'Indigenous ethnic culture & longhouses', 'Waterfalls & adventure trekking'],
                'attractions'  => [
                    ['name' => 'Da Lat French Villas & Tuyen Lam Lake', 'wikidata' => 'https://www.wikidata.org/wiki/Q25281'],
                    ['name' => 'Mang Den Pine Plateau & Pa Sy Waterfall', 'wikidata' => 'https://www.wikidata.org/wiki/Q10795764'],
                    ['name' => 'Buon Ma Thuot Coffee Plantations & Dray Nur Falls', 'wikidata' => 'https://www.wikidata.org/wiki/Q25302'],
                    ['name' => 'Kon Tum Wooden Church & Bahnar Communal Rong Houses', 'wikidata' => 'https://www.wikidata.org/wiki/Q10784269'],
                    ['name' => 'Bao Loc Tea & Coffee Hills', 'wikidata' => 'https://www.wikidata.org/wiki/Q25301'],
                    ['name' => 'Pleiku Sea Lake (Bien Ho)', 'wikidata' => 'https://www.wikidata.org/wiki/Q4904576'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Da Lat to Buon Ma Thuot & Mang Den Central Highlands Overland Journey',
                'description'  => 'An overland traverse through Vietnam\'s cool-climate coffee country, linking French alpine villas in Da Lat with Buon Ma Thuot\'s volcanic basalt plantations, Kon Tum\'s communal houses, and the pine forests of Mang Den.',
                'tourist_type' => ['Independent road trippers', 'Coffee enthusiasts', 'Nature & ecotourism travelers'],
                'stops'        => [
                    ['name' => 'Da Lat Alpine Retreat', 'url' => '/destinations/da-lat-travel-guide/'],
                    ['name' => 'Buon Ma Thuot Coffee Heart', 'url' => '/destinations/where-to-stay-in-buon-ma-thuot/'],
                    ['name' => 'Pleiku & Bien Ho Crater Lake', 'url' => '/destinations/where-to-stay-in-pleiku/'],
                    ['name' => 'Kon Tum & Mang Den Pine Plateau', 'url' => '/destinations/mang-den-travel-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Central Highlands Overland Route (Da Lat -> Buon Ma Thuot -> Kon Tum -> Mang Den)',
                'method' => 'Highland Highway QL20/QL27/QL14 & Scenic Mountain Passes',
            ],
            'journey_links' => [
                [
                    'name'    => 'Mang Den Pine Plateau',
                    'url'     => '/destinations/mang-den-travel-guide/',
                    'transit' => '4 hrs north of Pleiku via QL24',
                    'badge'   => 'Pristine Eco Plateau',
                    'desc'    => 'Misty pine forests, cool year-round climate, untouched waterfalls, and serene mountain homestays.',
                ],
                [
                    'name'    => 'Buon Ma Thuot Coffee Capital',
                    'url'     => '/destinations/where-to-stay-in-buon-ma-thuot/',
                    'transit' => '5 hrs via QL27 mountain road',
                    'badge'   => 'Coffee Capital',
                    'desc'    => 'Volcanic basalt soil, shade-grown robusta plantations, world coffee museum, and Dray Nur waterfalls.',
                ],
                [
                    'name'    => 'Kon Tum & Wooden Church',
                    'url'     => '/destinations/where-to-stay-in-kon-tum/',
                    'transit' => '1 hr north of Pleiku via QL14',
                    'badge'   => 'Indigenous Heritage',
                    'desc'    => 'Century-old wooden Romanesque cathedral, Bahnar communal Rong houses, and peaceful Dak Bla river.',
                ],
                [
                    'name'    => 'Nha Trang Coastal Descent',
                    'url'     => '/destinations/where-to-stay-in-da-lat/',
                    'transit' => '3.5 hrs via Khanh Le Pass',
                    'badge'   => 'Mountain-to-Sea',
                    'desc'    => 'Descend scenic mountain hairpins from 1,500m pine forests to turquoise coastal beaches in Nha Trang.',
                ],
                [
                    'name'    => 'Ho Chi Minh City Gateway',
                    'url'     => '/destinations/ho-chi-minh-city-travel-guide/',
                    'transit' => '6 hrs via expressway & QL20 / 50 min flight',
                    'badge'   => 'Metropolis Hub',
                    'desc'    => 'Overland bus or quick domestic flight back to Saigon for urban dining and international connections.',
                ],
            ],
        ],

        'phong_nha_caves' => [
            'id'                 => 'phong_nha_caves',
            'name'               => 'Phong Nha & Quang Binh Cave Kingdom',
            'hub'                => 'Phong Nha (Son Trach) & Dong Hoi',
            'hub_url'            => '/destinations/phong-nha-travel-guide/',
            'destination_schema' => [
                'addressRegion' => 'Quang Binh Province',
                'geo'          => ['latitude' => 17.5906, 'longitude' => 106.2831],
                'name'         => 'Phong Nha-Ke Bang & Quang Binh',
                'description'  => 'An expedition circuit exploring karst formations, subterranean river grottos, primary tropical rainforests, and spelunking in UNESCO-listed Phong Nha-Ke Bang.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q192135',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Phong_Nha-K%E1%BA%BB_B%C3%A0ng_National_Park',
                'tourist_type' => ['Spelunking & cave exploration', 'Subterranean river kayaking', 'UNESCO World Heritage nature', 'Jungle trekking & expedition travel'],
                'attractions'  => [
                    ['name' => 'Hang Son Doong (World\'s Largest Cave)', 'wikidata' => 'https://www.wikidata.org/wiki/Q212668'],
                    ['name' => 'Paradise Cave (Thien Duong)', 'wikidata' => 'https://www.wikidata.org/wiki/Q43224'],
                    ['name' => 'Phong Nha Wet Cave & Underground River', 'wikidata' => 'https://www.wikidata.org/wiki/Q192135'],
                    ['name' => 'Dark Cave (Hang Toi) & Chay River', 'wikidata' => 'https://www.wikidata.org/wiki/Q192135'],
                    ['name' => 'Hang En Cave', 'wikidata' => 'https://www.wikidata.org/wiki/Q192135'],
                    ['name' => 'Dong Hoi Coastal Gateway & Nhat Le Beach', 'wikidata' => 'https://www.wikidata.org/wiki/Q1879'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Quang Binh Subterranean Cave Kingdom & Annamite Jungle Circuit',
                'description'  => 'An expedition circuit exploring karst formations, subterranean river grottos, primary tropical rainforests, and spelunking in UNESCO-listed Phong Nha-Ke Bang.',
                'tourist_type' => ['Active adventurers', 'Spelunkers & trekkers', 'Ecotourists & photographers'],
                'stops'        => [
                    ['name' => 'Dong Hoi Rail & Airport Gateway', 'url' => '/destinations/where-to-stay-in-dong-hoi/'],
                    ['name' => 'Phong Nha Village & Wet Cave', 'url' => '/destinations/phong-nha-travel-guide/'],
                    ['name' => 'Paradise Cave Karst Plateau', 'url' => '/destinations/phong-nha-travel-guide/'],
                    ['name' => 'Tu Lan & Jungle Expedition Base', 'url' => '/plan/phong-nha-cave-treks/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Phong Nha & Quang Binh Cave Expeditions',
                'method' => 'Subterranean Boat, Jungle Trekking & Local Transfer Shuttle',
            ],
            'journey_links' => [
                [
                    'name'    => 'Paradise Cave & Dark Cave',
                    'url'     => '/destinations/phong-nha-travel-guide/',
                    'transit' => '30 mins via National Park Loop Road',
                    'badge'   => 'Karst Wonder',
                    'desc'    => 'Walk through 1km of soaring cathedral stalactites in Paradise Cave, then zipline and kayak at Dark Cave.',
                ],
                [
                    'name'    => 'Dong Hoi Coastal Gateway',
                    'url'     => '/destinations/where-to-stay-in-dong-hoi/',
                    'transit' => '45 mins via transfer van / taxi',
                    'badge'   => 'Coastal Rail Base',
                    'desc'    => 'Comfortable beachside hotels on Nhat Le beach, local seafood, domestic airport flights, and railway connections.',
                ],
                [
                    'name'    => 'Hue Imperial Citadel (Southward)',
                    'url'     => '/destinations/hue-imperial-city-guide/',
                    'transit' => '3.5 hrs via Reunification Express Train',
                    'badge'   => 'Imperial Heritage',
                    'desc'    => 'Ride the railway south through the DMZ to the royal Nguyen dynasty palaces and tombs in Hue.',
                ],
                [
                    'name'    => 'Ninh Binh Terrestrial Karsts (Northward)',
                    'url'     => '/destinations/ninh-binh-travel-guide/',
                    'transit' => '7 hrs via Overnight Sleeper Train',
                    'badge'   => 'Terrestrial Karsts',
                    'desc'    => 'Board the night train north toward the peaceful paddleboat limestone valleys and rice fields of Tam Coc.',
                ],
            ],
        ],

        'mekong_delta' => [
            'id'                 => 'mekong_delta',
            'name'               => 'Mekong Delta Riverine Network',
            'hub'                => 'Can Tho & Chau Doc',
            'hub_url'            => '/destinations/mekong-delta-travel-guide/',
            'destination_schema' => [
                'addressRegion' => 'Can Tho, Ben Tre, An Giang, Kien Giang & Ca Mau',
                'geo'          => ['latitude' => 10.0452, 'longitude' => 105.7469],
                'name'         => 'Mekong Delta Riverways',
                'description'  => 'A waterway route across the delta, navigating morning wholesale markets, mangrove boat passages, traditional fruit orchards, and cross-border river routes into Cambodia.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q1052867',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Mekong_Delta',
                'tourist_type' => ['Riverine ecotourism', 'Floating market culture', 'Fruit orchard homestays', 'Mangrove biosphere exploration', 'Cross-border river transit'],
                'attractions'  => [
                    ['name' => 'Cai Rang Floating Market', 'wikidata' => 'https://www.wikidata.org/wiki/Q5017482'],
                    ['name' => 'Tra Su Cajuput Melaleuca Forest', 'wikidata' => 'https://www.wikidata.org/wiki/Q10828236'],
                    ['name' => 'Ben Tre Coconut Waterways', 'wikidata' => 'https://www.wikidata.org/wiki/Q25298'],
                    ['name' => 'Sam Mountain & Chau Doc Border Temples', 'wikidata' => 'https://www.wikidata.org/wiki/Q36384'],
                    ['name' => 'Mui Ca Mau Biosphere (Southernmost Tip)', 'wikidata' => 'https://www.wikidata.org/wiki/Q33932'],
                    ['name' => 'Ha Tien Coastal Frontier', 'wikidata' => 'https://www.wikidata.org/wiki/Q36384'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Can Tho to Chau Doc, Ben Tre & Ca Mau Mekong Riverine Circuit',
                'description'  => 'A waterway route across the delta, navigating morning wholesale markets, mangrove boat passages, traditional fruit orchards, and cross-border river routes into Cambodia.',
                'tourist_type' => ['Cultural explorers', 'Slow travelers', 'Culinary & riverine adventurers'],
                'stops'        => [
                    ['name' => 'Can Tho & Cai Rang Floating Market', 'url' => '/destinations/where-to-stay-in-can-tho/'],
                    ['name' => 'Ben Tre Coconut Country', 'url' => '/destinations/where-to-stay-in-ben-tre/'],
                    ['name' => 'Chau Doc & Tra Su Melaleuca Forest', 'url' => '/destinations/where-to-stay-in-chau-doc/'],
                    ['name' => 'Ha Tien & Rach Gia Coastal Ports', 'url' => '/destinations/where-to-stay-in-ha-tien/'],
                    ['name' => 'Ca Mau Southernmost Mangrove Tip', 'url' => '/destinations/where-to-stay-in-ca-mau/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Navigate Mekong Delta Waterway & Cross-Border Network',
                'method' => 'Wooden Sampan, River Speedboat & Delta Highway Van',
            ],
            'journey_links' => [
                [
                    'name'    => 'Cai Rang Floating Market (Can Tho)',
                    'url'     => '/destinations/mekong-delta-floating-markets-guide/',
                    'transit' => '30 min dawn sampan from Ninh Kieu wharf',
                    'badge'   => 'Floating Market',
                    'desc'    => 'Experience authentic morning trading from wooden riverboats with fresh noodle soup and iced coffee on the water.',
                ],
                [
                    'name'    => 'Chau Doc & Tra Su Forest',
                    'url'     => '/destinations/where-to-stay-in-chau-doc/',
                    'transit' => '3 hrs northwest of Can Tho via QL91',
                    'badge'   => 'Border Waterways',
                    'desc'    => 'Glide along canal passages under emerald melaleuca canopies, visit stilt-house Cham villages.',
                ],
                [
                    'name'    => 'Phu Quoc Island Speed Ferry Connection',
                    'url'     => '/destinations/phu-quoc-travel-guide/',
                    'transit' => '1 hr 15 min speedboat from Ha Tien / 2 hrs 15 min from Rach Gia',
                    'badge'   => 'Island Ferry Link',
                    'desc'    => 'Transition directly from delta riverways to the beaches and seaside resorts of Phu Quoc.',
                ],
                [
                    'name'    => 'Phnom Penh International Riverboat (Cambodia)',
                    'url'     => '/plan/vietnam-to-cambodia-boat-guide/',
                    'transit' => '4.5 hrs daily express boat from Chau Doc',
                    'badge'   => 'International River Crossing',
                    'desc'    => 'River journey up the Mekong River from Chau Doc to Phnom Penh with streamlined river border clearance.',
                ],
                [
                    'name'    => 'Ho Chi Minh City Urban Hub',
                    'url'     => '/destinations/ho-chi-minh-city-travel-guide/',
                    'transit' => '2.5 hrs via Trung Luong - My Thuan Expressway',
                    'badge'   => 'Southern Metropolis',
                    'desc'    => 'Expressway return to Saigon for rooftop dining, heritage museums, and flight departures.',
                ],
            ],
        ],

        'far_north_frontier' => [
            'id'                 => 'far_north_frontier',
            'name'               => 'North-Central & Far North Frontier',
            'hub'                => 'Cao Bang & Ba Be',
            'hub_url'            => '/destinations/cao-bang-travel-guide/',
            'destination_schema' => [
                'addressRegion' => 'Cao Bang, Bac Kan, Dien Bien, Lao Cai & Thanh Hoa',
                'geo'          => ['latitude' => 22.6667, 'longitude' => 106.2500],
                'name'         => 'Cao Bang & Far North Frontier',
                'description'  => 'An overland frontier circuit spanning the freshwater lakes of Ba Be, the tiered cascades of Ban Gioc Waterfall on the northern border, and the mountain valleys of Mai Chau and Dien Bien Phu.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q36384',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Cao_B%E1%BA%B1ng_province',
                'tourist_type' => ['Geopark geological exploration', 'Waterfall & freshwater lake ecotourism', 'Frontier motorcycle route touring', 'Ethnic minority homestay immersion', 'Historical battleground tourism'],
                'attractions'  => [
                    ['name' => 'Ban Gioc Waterfall (UNESCO Global Geopark)', 'wikidata' => 'https://www.wikidata.org/wiki/Q806148'],
                    ['name' => 'Ba Be National Park & Freshwater Lake', 'wikidata' => 'https://www.wikidata.org/wiki/Q10828551'],
                    ['name' => 'Nguom Ngao Tiger Cave', 'wikidata' => 'https://www.wikidata.org/wiki/Q10798734'],
                    ['name' => 'Bac Ha Sunday Ethnic Market', 'wikidata' => 'https://www.wikidata.org/wiki/Q36384'],
                    ['name' => 'Dien Bien Phu Historic Valley', 'wikidata' => 'https://www.wikidata.org/wiki/Q25298'],
                    ['name' => 'Mai Chau Valley & Stilt Villages', 'wikidata' => 'https://www.wikidata.org/wiki/Q10788220'],
                ],
            ],
            'trip_schema' => [
                'name'         => 'Northeast to Northwest Frontier Expedition (Ba Be, Ban Gioc & Dien Bien Phu)',
                'description'  => 'An overland frontier circuit spanning the freshwater lakes of Ba Be, the tiered cascades of Ban Gioc Waterfall on the northern border, and the mountain valleys of Mai Chau and Dien Bien Phu.',
                'tourist_type' => ['Adventure motorcycle riders', 'Off-the-beaten-track travelers', 'Landscape photographers'],
                'stops'        => [
                    ['name' => 'Ba Be National Park', 'url' => '/destinations/ba-be-lake-travel-guide/'],
                    ['name' => 'Cao Bang Geopark & Ban Gioc', 'url' => '/destinations/cao-bang-travel-guide/'],
                    ['name' => 'Bac Ha Highland Market', 'url' => '/destinations/where-to-stay-in-bac-ha/'],
                    ['name' => 'Mai Chau Valley', 'url' => '/destinations/where-to-stay-in-mai-chau/'],
                    ['name' => 'Dien Bien Phu Historical Basin', 'url' => '/destinations/where-to-stay-in-dien-bien-phu/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Northeast & Far North Frontier Circuit',
                'method' => 'Mountain Ridge Highways QL3/QL4A/QL6 & Local Boat Ferries',
            ],
            'journey_links' => [
                [
                    'name'    => 'Ban Gioc Waterfall & Nguom Ngao Cave',
                    'url'     => '/destinations/cao-bang-travel-guide/',
                    'transit' => '2 hrs northeast of Cao Bang city via QL3',
                    'badge'   => 'UNESCO Geopark',
                    'desc'    => 'Southeast Asia\'s largest border waterfall, boat on the Quay Son river, and explore stalactite caverns.',
                ],
                [
                    'name'    => 'Ba Be Lake Pristine Waters',
                    'url'     => '/destinations/ba-be-lake-travel-guide/',
                    'transit' => '3.5 hrs southwest of Cao Bang via scenic mountain pass',
                    'badge'   => 'Alpine Lake Sanctuary',
                    'desc'    => 'Kayak through limestone gorges, stay with Tay families in Pac Ngoi stilt houses, and swim in clear freshwater.',
                ],
                [
                    'name'    => 'Ha Giang Karst Plateau Connection',
                    'url'     => '/destinations/ha-giang-loop-planning-guide/',
                    'transit' => '6 hrs west via Bao Lac & Meo Vac (QL34)',
                    'badge'   => 'Frontier Loop Link',
                    'desc'    => 'Connect the Northeast waterfall loop directly to the dramatic passes of Ma Pi Leng and Dong Van.',
                ],
                [
                    'name'    => 'Mai Chau & Pu Luong Terraces',
                    'url'     => '/destinations/where-to-stay-in-mai-chau/',
                    'transit' => '3.5 hrs southwest of Hanoi via QL6',
                    'badge'   => 'Valley Sanctuary',
                    'desc'    => 'Cycle through White Thai villages, emerald rice paddies, and connect south into Pu Luong Nature Reserve.',
                ],
                [
                    'name'    => 'Hanoi Capital Gateway',
                    'url'     => '/destinations/hanoi-travel-guide/',
                    'transit' => '4.5 hrs via Thai Nguyen Expressway',
                    'badge'   => 'Northern Hub',
                    'desc'    => 'Return overland to Hanoi for recovery, street-food comforts, and international flight onward connections.',
                ],
            ],
        ],

        'national_circuit' => [
            'id'                 => 'national_circuit',
            'name'               => 'Classic Vietnam Grand Route',
            'hub'                => 'Vietnam',
            'hub_url'            => '/plan/vietnam-travel-guide/',
            'destination_schema' => [
                'addressRegion' => 'Vietnam',
                'geo'          => ['latitude' => 16.0544, 'longitude' => 108.2022],
                'name'         => 'Vietnam',
                'description'  => 'An extraordinary Southeast Asian destination blending 3,200 km of coastline, UNESCO World Heritage treasures, limestone karst bays, and globally renowned culinary culture.',
                'wikidata'     => 'https://www.wikidata.org/wiki/Q881',
                'wikipedia'    => 'https://en.wikipedia.org/wiki/Vietnam',
                'tourist_type' => ['Cultural tourism', 'Nature & adventure', 'Culinary exploration', 'Heritage tourism'],
                'attractions'  => [
                    ['name' => 'Ha Long Bay', 'wikidata' => 'https://www.wikidata.org/wiki/Q190128'],
                    ['name' => 'Hoi An Ancient Town', 'wikidata' => 'https://www.wikidata.org/wiki/Q36167'],
                    ['name' => 'Trang An Landscape Complex', 'wikidata' => 'https://www.wikidata.org/wiki/Q10828551'],
                    ['name' => 'Imperial City of Hue', 'wikidata' => 'https://www.wikidata.org/wiki/Q200257'],
                ],
                'containedInPlace' => [
                    '@type'  => 'Place',
                    'name'   => 'Southeast Asia',
                    'sameAs' => 'https://www.wikidata.org/wiki/Q11708',
                ],
            ],
            'trip_schema' => [
                'name'         => 'Classic North-to-South Vietnam Grand Circuit',
                'description'  => 'The definitive travel route connecting Hanoi, Ha Long Bay, Ninh Binh, Hue, Hoi An, Da Nang, Ho Chi Minh City, and the Mekong Delta.',
                'tourist_type' => ['First-time visitors', 'Multi-week travelers', 'Comprehensive route planners'],
                'stops'        => [
                    ['name' => 'Hanoi', 'url' => '/destinations/hanoi-travel-guide/'],
                    ['name' => 'Ha Long Bay', 'url' => '/destinations/ha-long-bay-travel-guide/'],
                    ['name' => 'Ninh Binh', 'url' => '/destinations/ninh-binh-travel-guide/'],
                    ['name' => 'Hoi An & Da Nang', 'url' => '/destinations/hoi-an-ancient-town-guide/'],
                    ['name' => 'Ho Chi Minh City', 'url' => '/destinations/ho-chi-minh-city-travel-guide/'],
                    ['name' => 'Mekong Delta', 'url' => '/destinations/mekong-delta-travel-guide/'],
                ],
            ],
            'action_schema' => [
                'name'   => 'Explore Classic Vietnam Grand Circuit',
                'method' => 'Express Railway, Domestic Air & Highway',
            ],
            'journey_links' => [
                [
                    'name'    => 'Hanoi & Northern Karsts',
                    'url'     => '/destinations/hanoi-travel-guide/',
                    'transit' => 'Gateway to North',
                    'badge'   => 'Capital & Karsts',
                    'desc'    => 'Begin in Hanoi with Old Quarter food alleys before cruising Ha Long Bay and cycling Ninh Binh.',
                ],
                [
                    'name'    => 'Central Heritage Corridor',
                    'url'     => '/destinations/da-nang-travel-guide/',
                    'transit' => '1.2 hr flight from North/South',
                    'badge'   => 'Heritage & Beaches',
                    'desc'    => 'Immerse in lantern-lit Hoi An, imperial palaces in Hue, and sandy beaches in Da Nang.',
                ],
                [
                    'name'    => 'Southern Riverine Route',
                    'url'     => '/destinations/ho-chi-minh-city-travel-guide/',
                    'transit' => 'Gateway to South',
                    'badge'   => 'Metropolis & Delta',
                    'desc'    => 'Experience Saigon\'s energy, historic Cu Chi tunnels, and sampan waterways in the Mekong Delta.',
                ],
            ],
        ],
    ];

    return $registry;
}

/**
 * Identify matching travel cluster for a given URI path.
 */
function vg_find_travel_cluster_by_path(string $path): array
{
    $clusters = vg_get_travel_clusters_registry();
    $normalized = strtolower(trim($path, '/'));

    // 1. Central Highlands / Mang Den / Da Lat
    if (
        str_contains($normalized, 'da-lat')
        || str_contains($normalized, 'mang-den')
        || str_contains($normalized, 'kon-tum')
        || str_contains($normalized, 'pleiku')
        || str_contains($normalized, 'buon-ma-thuot')
        || str_contains($normalized, 'bao-loc')
        || str_contains($normalized, 'central-highlands')
    ) {
        return $clusters['central_highlands'] ?? $clusters['national_circuit'];
    }

    // 2. Phong Nha & Quang Binh Cave Kingdom
    if (
        str_contains($normalized, 'phong-nha')
        || str_contains($normalized, 'dong-hoi')
        || str_contains($normalized, 'quang-binh')
    ) {
        return $clusters['phong_nha_caves'] ?? $clusters['central_heritage'];
    }

    // 3. Mekong Delta Riverine Network
    if (
        str_contains($normalized, 'mekong')
        || str_contains($normalized, 'can-tho')
        || str_contains($normalized, 'ben-tre')
        || str_contains($normalized, 'chau-doc')
        || str_contains($normalized, 'an-giang')
        || str_contains($normalized, 'ca-mau')
        || str_contains($normalized, 'bac-lieu')
        || str_contains($normalized, 'soc-trang')
        || str_contains($normalized, 'ha-tien')
        || str_contains($normalized, 'rach-gia')
        || str_contains($normalized, 'cambodia-boat')
        || str_contains($normalized, 'border-crossings')
    ) {
        return $clusters['mekong_delta'] ?? $clusters['southern_delta'];
    }

    // 4. North-Central & Far North Frontier
    if (
        str_contains($normalized, 'cao-bang')
        || str_contains($normalized, 'ba-be')
        || str_contains($normalized, 'bac-ha')
        || str_contains($normalized, 'bao-lac')
        || str_contains($normalized, 'dien-bien')
        || str_contains($normalized, 'mai-chau')
        || str_contains($normalized, 'tam-dao')
        || str_contains($normalized, 'northeast-vietnam')
        || str_contains($normalized, 'northwest-vietnam')
        || str_contains($normalized, 'lang-co')
        || str_contains($normalized, 'quang-ngai')
    ) {
        return $clusters['far_north_frontier'] ?? $clusters['national_circuit'];
    }

    // 5. Northern Golden Triangle (Hanoi)
    if (
        str_contains($normalized, 'hanoi')
        || str_contains($normalized, 'old-quarter')
        || str_contains($normalized, 'west-lake')
        || str_contains($normalized, 'french-quarter')
        || str_contains($normalized, 'northern')
    ) {
        return $clusters['northern_triangle'];
    }

    // 6. Gulf of Tonkin Karst Archipelago (Ha Long Bay)
    if (
        str_contains($normalized, 'ha-long')
        || str_contains($normalized, 'lan-ha')
        || str_contains($normalized, 'cat-ba')
        || str_contains($normalized, 'bai-tu-long')
        || str_contains($normalized, 'hai-phong')
    ) {
        return $clusters['ha_long_bay'];
    }

    // 7. Ninh Binh Karst Countryside
    if (
        str_contains($normalized, 'ninh-binh')
        || str_contains($normalized, 'tam-coc')
        || str_contains($normalized, 'trang-an')
    ) {
        return $clusters['ninh_binh'];
    }

    // 8. Northern Highlands (Sa Pa, Ha Giang, Mu Cang Chai, Pu Luong)
    if (
        str_contains($normalized, 'sapa')
        || str_contains($normalized, 'ha-giang')
        || str_contains($normalized, 'mu-cang-chai')
        || str_contains($normalized, 'pu-luong')
        || str_contains($normalized, 'rice-terraces')
        || str_contains($normalized, 'dong-van')
        || str_contains($normalized, 'meo-vac')
        || str_contains($normalized, 'du-gia')
        || str_contains($normalized, 'yen-minh')
    ) {
        return $clusters['northern_highlands'];
    }

    // 9. Central Heritage Corridor (Da Nang, Hoi An, Hue)
    if (
        str_contains($normalized, 'da-nang')
        || str_contains($normalized, 'hoi-an')
        || str_contains($normalized, 'hue')
        || str_contains($normalized, 'cham-islands')
        || str_contains($normalized, 'ba-na')
        || str_contains($normalized, 'central-vietnam')
    ) {
        return $clusters['central_heritage'];
    }

    // 10. Southern Delta / Saigon Urban Hub
    if (
        str_contains($normalized, 'ho-chi-minh')
        || str_contains($normalized, 'cu-chi')
        || str_contains($normalized, 'saigon')
        || str_contains($normalized, 'vung-tau')
        || str_contains($normalized, 'southern-vietnam')
    ) {
        return $clusters['southern_delta'];
    }

    // 11. Coastal & Island Sanctuaries
    if (
        str_contains($normalized, 'phu-quoc')
        || str_contains($normalized, 'con-dao')
        || str_contains($normalized, 'nha-trang')
        || str_contains($normalized, 'quy-nhon')
        || str_contains($normalized, 'mui-ne')
        || str_contains($normalized, 'ly-son')
        || str_contains($normalized, 'phu-yen')
        || str_contains($normalized, 'cam-ranh')
        || str_contains($normalized, 'phan-rang')
        || str_contains($normalized, 'vinh-hy')
        || str_contains($normalized, 'beach')
        || str_contains($normalized, 'island')
    ) {
        return $clusters['coastal_islands'];
    }

    return $clusters['national_circuit'];
}

/**
 * Filter and format journey links for current page (excluding self).
 */
function vg_get_active_journey_links_for_path(array $cluster, string $current_path): array
{
    $links = $cluster['journey_links'] ?? [];
    $filtered = [];
    $currentPathParsed = (string) parse_url($current_path, PHP_URL_PATH);
    $normalizedCurrent = strtolower(trim($currentPathParsed !== '' ? $currentPathParsed : $current_path, '/'));

    foreach ($links as $link) {
        $linkPath = strtolower(trim((string) parse_url($link['url'], PHP_URL_PATH), '/'));
        if ($linkPath !== '' && $linkPath === $normalizedCurrent) {
            continue; // Skip self-link
        }
        $filtered[] = $link;
    }

    // If filtering left fewer than 2, fill with national cluster highlights
    if (count($filtered) < 2 && $cluster['id'] !== 'national_circuit') {
        $registry = vg_get_travel_clusters_registry();
        $fallbackLinks = $registry['national_circuit']['journey_links'] ?? [];
        foreach ($fallbackLinks as $fallback) {
            $fPath = strtolower(trim((string) parse_url($fallback['url'], PHP_URL_PATH), '/'));
            if ($fPath === $normalizedCurrent) {
                continue;
            }
            $alreadyExists = false;
            foreach ($filtered as $ex) {
                if ($ex['url'] === $fallback['url']) {
                    $alreadyExists = true;
                    break;
                }
            }
            if (! $alreadyExists) {
                $filtered[] = $fallback;
                if (count($filtered) >= 3) {
                    break;
                }
            }
        }
    }

    return array_slice($filtered, 0, 3);
}

/**
 * Render the HTML component for Contextual Next-Step Journey Links.
 */
function vg_render_contextual_journey_html(?array $cluster = null, string $current_path = ''): string
{
    if ($current_path === '') {
        if (function_exists('get_the_ID')) {
            $qid = (int) get_the_ID();
            if ($qid > 0) {
                $permalink = get_permalink($qid);
                if (is_string($permalink) && $permalink !== '') {
                    $current_path = strtolower(trim((string) parse_url($permalink, PHP_URL_PATH), '/'));
                }
            }
        }
        if ($current_path === '' && function_exists('get_queried_object_id')) {
            $qid = (int) get_queried_object_id();
            if ($qid > 0) {
                $permalink = get_permalink($qid);
                if (is_string($permalink) && $permalink !== '') {
                    $current_path = strtolower(trim((string) parse_url($permalink, PHP_URL_PATH), '/'));
                }
            }
        }
        if ($current_path === '') {
            $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
            $current_path = strtolower(trim((string) parse_url($uri, PHP_URL_PATH), '/'));
        }
    }

    if ($cluster === null) {
        $cluster = vg_find_travel_cluster_by_path($current_path);
    }

    $links = vg_get_active_journey_links_for_path($cluster, $current_path);
    if (empty($links)) {
        return '';
    }

    $cluster_name = esc_html($cluster['name']);

    ob_start();
    ?>
    <section class="vg-contextual-journey" aria-label="<?php esc_attr_e('Next-step journey destinations', 'vietnamguide-premium'); ?>">
        <div class="vg-contextual-journey__inner">
            <header class="vg-contextual-journey__header">
                <span class="vg-contextual-journey__kicker"><?php esc_html_e('Next-Step Journey', 'vietnamguide-premium'); ?></span>
                <h3 class="vg-contextual-journey__title"><?php echo sprintf(esc_html__('Connected Destinations along the %s', 'vietnamguide-premium'), $cluster_name); ?></h3>
                <p class="vg-contextual-journey__subtitle"><?php esc_html_e('Continue your Vietnam route seamlessly with verified transit times and natural regional connections.', 'vietnamguide-premium'); ?></p>
            </header>
            <div class="vg-contextual-journey__grid">
                <?php foreach ($links as $link) : ?>
                    <a href="<?php echo esc_url($link['url']); ?>" class="vg-contextual-journey__card">
                        <div class="vg-contextual-journey__card-top">
                            <span class="vg-contextual-journey__badge"><?php echo esc_html($link['badge']); ?></span>
                            <span class="vg-contextual-journey__transit"><?php echo esc_html($link['transit']); ?></span>
                        </div>
                        <h4 class="vg-contextual-journey__card-title">
                            <?php echo esc_html($link['name']); ?>
                        </h4>
                        <p class="vg-contextual-journey__card-desc">
                            <?php echo esc_html($link['desc']); ?>
                        </p>
                        <div class="vg-contextual-journey__card-footer">
                            <span class="vg-contextual-journey__action-text"><?php esc_html_e('Explore route guide', 'vietnamguide-premium'); ?></span>
                            <span class="vg-contextual-journey__arrow" aria-hidden="true">&rarr;</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}

/**
 * Shortcode handler for manual placement in editorial articles or hubs: [vg_journey_links]
 * Supports optional cluster attribute: [vg_journey_links cluster="central_heritage"]
 */
function vg_contextual_journey_shortcode($atts = []): string
{
    $cluster_id = '';
    if (is_array($atts) && ! empty($atts['cluster'])) {
        $cluster_id = sanitize_key((string) $atts['cluster']);
    }
    $cluster = null;
    if ($cluster_id !== '') {
        $registry = vg_get_travel_clusters_registry();
        $cluster = $registry[$cluster_id] ?? null;
    }
    return vg_render_contextual_journey_html($cluster);
}
add_shortcode('vg_journey_links', 'vg_contextual_journey_shortcode');
add_shortcode('vg_contextual_journey', 'vg_contextual_journey_shortcode');

/**
 * Automatically inject Contextual Journey Links into singular content.
 */
add_filter('the_content', static function (string $content): string {
    if (is_front_page() || is_home()) {
        return $content;
    }
    if (! is_singular() && ! is_page()) {
        return $content;
    }
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return $content;
    }
    if (is_feed()) {
        return $content;
    }
    // Guard against injecting into hero blocks or already injected content
    if (
        str_contains($content, 'vg-guide-hero')
        || str_contains($content, 'vg-contextual-journey')
    ) {
        return $content;
    }

    $uri_path = '';
    if (function_exists('get_the_ID')) {
        $qid = (int) get_the_ID();
        if ($qid > 0) {
            $permalink = get_permalink($qid);
            if (is_string($permalink) && $permalink !== '') {
                $uri_path = strtolower(trim((string) parse_url($permalink, PHP_URL_PATH), '/'));
            }
        }
    }
    if ($uri_path === '' && function_exists('get_queried_object_id')) {
        $qid = (int) get_queried_object_id();
        if ($qid > 0) {
            $permalink = get_permalink($qid);
            if (is_string($permalink) && $permalink !== '') {
                $uri_path = strtolower(trim((string) parse_url($permalink, PHP_URL_PATH), '/'));
            }
        }
    }
    if ($uri_path === '') {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $uri_path = strtolower(trim((string) parse_url($uri, PHP_URL_PATH), '/'));
    }

    $journeyHtml = vg_render_contextual_journey_html(null, $uri_path);
    if ($journeyHtml === '') {
        return $content;
    }

    return $content . "\n" . $journeyHtml;
}, 30);

/**
 * Recursively cleans Schema.org data: decodes HTML entities (&amp; -> &),
 * enforces native booleans for publicAccess/isAccessibleForFree, formats position/numberOfItems/width/height as integers,
 * and formats latitude/longitude as floats.
 */
function vg_schema_clean_data(mixed $value, int $depth = 0): mixed
{
    if ($depth >= 64) {
        return $value;
    }
    if (is_string($value)) {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if (is_array($value)) {
        foreach ($value as $k => $v) {
            if ($k === 'publicAccess' || $k === 'isAccessibleForFree') {
                $value[$k] = ($v === true || $v === '1' || $v === 1 || $v === 'true');
            } elseif ($k === 'position' || $k === 'numberOfItems' || $k === 'width' || $k === 'height') {
                if (is_numeric($v)) {
                    $value[$k] = (int) $v;
                } else {
                    $value[$k] = vg_schema_clean_data($v, $depth + 1);
                }
            } elseif ($k === 'latitude' || $k === 'longitude') {
                if (is_numeric($v)) {
                    $value[$k] = (float) $v;
                } else {
                    $value[$k] = vg_schema_clean_data($v, $depth + 1);
                }
            } else {
                $value[$k] = vg_schema_clean_data($v, $depth + 1);
            }
        }
    }
    return $value;
}

/**
 * Rich Travel Schema Structured Data Filter for Rank Math JSON-LD Graph.
 * Enriches the graph with TouristDestination, TouristTrip, and TravelAction entities.
 */
function vg_rich_travel_schema_filter($data, $context = null): array
{
    if (! is_array($data)) {
        return (array) $data;
    }

    $is_search_or_404 = (function_exists('is_search') && is_search()) || (function_exists('is_404') && is_404());
    $uri_path = '';
    $resolved_path = false;
    if ($is_search_or_404) {
        $uri_path = '';
        $resolved_path = true;
    } elseif (isset($_SERVER['REQUEST_URI'])) {
        $req_path = strtolower(trim((string) parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
        if ($req_path !== '') {
            $uri_path = $req_path;
            $resolved_path = true;
        }
    }

    if (! $resolved_path) {
        if (function_exists('is_front_page') && is_front_page()) {
            $uri_path = '';
            $resolved_path = true;
        } elseif (function_exists('get_queried_object_id')) {
            $qid = (int) get_queried_object_id();
            if ($qid > 0) {
                $permalink = get_permalink($qid);
                if (is_string($permalink) && $permalink !== '') {
                    $parsed_perm = strtolower(trim((string) parse_url($permalink, PHP_URL_PATH), '/'));
                    if ($parsed_perm !== '') {
                        $uri_path = $parsed_perm;
                        $resolved_path = true;
                    }
                }
            }
        }
    }
    if (! $resolved_path && $uri_path === '' && isset($GLOBALS['post']) && is_object($GLOBALS['post'])) {
        $slug = strtolower((string) ($GLOBALS['post']->post_name ?? ''));
        $uri_path = ($slug === 'home') ? '' : $slug;
        if ($slug !== '') {
            $resolved_path = true;
        }
    }
    if (! $resolved_path && $uri_path === '') {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $uri_path = strtolower(trim((string) parse_url($uri, PHP_URL_PATH), '/'));
    }

    // Exclude utility, legal, trust, search, and 404 pages from tourist travel graph entities while retaining E-E-A-T enrichment
    $non_travel_paths = [
        'about',
        'editorial-policy',
        'privacy-policy',
        'terms-of-service',
        'source-update-policy',
        'affiliate-disclosure',
        'contact',
        'newsletter',
    ];
    $is_non_travel_page = $is_search_or_404 || in_array($uri_path, $non_travel_paths, true);

    $cluster = vg_find_travel_cluster_by_path($uri_path);
    $canonical_base = function_exists('home_url') ? untrailingslashit(home_url()) : 'https://vietnamguide.net';
    $current_url = ($uri_path !== '') ? "{$canonical_base}/{$uri_path}/" : "{$canonical_base}/";

    $dest_id = "{$current_url}#tourist-destination";
    $trip_id = "{$current_url}#tourist-trip";
    $action_id = "{$current_url}#travel-action";
    $webpage_id = "{$current_url}#webpage";
    $primary_image_id = "{$current_url}#primaryimage";
    $discover_image = vg_resolve_discover_image_object($uri_path);

    // Idempotence check: return early if destination node already exists in this graph
    $existing_nodes = isset($data['@graph']) && is_array($data['@graph']) ? $data['@graph'] : $data;
    foreach ($existing_nodes as $existing) {
        if (is_array($existing) && ($existing['@id'] ?? '') === $dest_id) {
            return $data;
        }
    }

    // 1. TouristDestination Node
    $dest_schema = $cluster['destination_schema'];
    $dest_node = [
        '@type'              => 'TouristDestination',
        '@id'                => $dest_id,
        'name'               => $dest_schema['name'],
        'description'        => $dest_schema['description'],
        'url'                => $current_url,
        'image'              => ['@id' => $primary_image_id],
        'touristType'        => $dest_schema['tourist_type'],
        'currenciesAccepted'  => 'VND',
        'availableLanguage'   => ['en', 'vi'],
        'publicAccess'        => true,
        'isAccessibleForFree' => false,
        'containedInPlace'   => ! empty($dest_schema['containedInPlace']) ? $dest_schema['containedInPlace'] : (($cluster['id'] === 'national_circuit' || ($dest_schema['name'] ?? '') === 'Vietnam') ? [
            '@type'  => 'Place',
            'name'   => 'Southeast Asia',
            'sameAs' => 'https://www.wikidata.org/wiki/Q11708',
        ] : [
            '@type'  => 'Country',
            'name'   => 'Vietnam',
            'sameAs' => 'https://www.wikidata.org/wiki/Q881',
        ]),
        'subjectOf'          => [
            '@id' => $webpage_id,
        ],
    ];
    $same_as = [];
    if (! empty($dest_schema['wikidata'])) {
        $same_as[] = $dest_schema['wikidata'];
    }
    if (! empty($dest_schema['wikipedia'])) {
        $same_as[] = $dest_schema['wikipedia'];
    }
    if (! empty($dest_schema['sameAs'])) {
        if (is_array($dest_schema['sameAs'])) {
            $same_as = array_merge($same_as, $dest_schema['sameAs']);
        } else {
            $same_as[] = $dest_schema['sameAs'];
        }
    }
    $same_as = array_values(array_unique(array_filter($same_as)));
    if (! empty($same_as)) {
        $dest_node['sameAs'] = (count($same_as) === 1) ? $same_as[0] : $same_as;
    }
    if (! empty($dest_schema['geo'])) {
        $dest_node['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $dest_schema['geo']['latitude'],
            'longitude' => (float) $dest_schema['geo']['longitude'],
        ];
        $dest_node['hasMap'] = "https://www.openstreetmap.org/?mlat={$dest_schema['geo']['latitude']}&mlon={$dest_schema['geo']['longitude']}#map=12/{$dest_schema['geo']['latitude']}/{$dest_schema['geo']['longitude']}";
    }
    if (! empty($dest_schema['addressRegion'])) {
        $dest_node['address'] = [
            '@type'          => 'PostalAddress',
            'addressCountry' => 'VN',
            'addressRegion'  => $dest_schema['addressRegion'],
        ];
    }
    if (! empty($dest_schema['attractions'])) {
        $attractions = [];
        foreach ($dest_schema['attractions'] as $attr) {
            $attractions[] = [
                '@type'  => 'TouristAttraction',
                'name'   => $attr['name'],
                'sameAs' => $attr['wikidata'] ?? '',
            ];
        }
        $dest_node['includesAttraction'] = $attractions;
    }
    $dest_node['itinerary'] = ['@id' => $trip_id];
    $dest_node['potentialAction'] = ['@id' => $action_id];

    // 2. TouristTrip Node
    $trip_schema = $cluster['trip_schema'];
    $trip_items = [];
    $pos = 1;
    foreach ($trip_schema['stops'] as $stop) {
        $stop_url = (str_starts_with($stop['url'], 'http://') || str_starts_with($stop['url'], 'https://'))
            ? $stop['url']
            : $canonical_base . '/' . ltrim($stop['url'], '/');
        $trip_items[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'item'     => [
                '@type' => 'TouristDestination',
                'name'  => $stop['name'],
                'url'   => $stop_url,
            ],
        ];
    }
    $trip_node = [
        '@type'       => 'TouristTrip',
        '@id'         => $trip_id,
        'name'        => $trip_schema['name'],
        'description' => $trip_schema['description'],
        'touristType' => $trip_schema['tourist_type'],
        'provider'    => [
            '@type' => 'Organization',
            'name'  => 'VietnamGuide.net',
            'url'   => "{$canonical_base}/",
        ],
        'offers'      => [
            '@type'         => 'Offer',
            'price'         => '0',
            'priceCurrency' => 'USD',
            'category'      => 'Free Editorial Route Planning',
            'url'           => $current_url,
        ],
        'itinerary'   => [
            '@type'           => 'ItemList',
            'numberOfItems'   => count($trip_items),
            'itemListElement' => $trip_items,
        ],
    ];

    // 3. TravelAction Node
    $action_schema = $cluster['action_schema'];
    $to_locations = [];
    foreach ($trip_schema['stops'] as $idx => $stop) {
        if ($idx === 0 && count($trip_schema['stops']) > 1) {
            continue;
        }
        $to_url = (str_starts_with($stop['url'], 'http://') || str_starts_with($stop['url'], 'https://'))
            ? $stop['url']
            : $canonical_base . '/' . ltrim($stop['url'], '/');
        $to_locations[] = [
            '@type' => 'TouristDestination',
            'name'  => $stop['name'],
            'url'   => $to_url,
        ];
    }
    $from_stop = $trip_schema['stops'][0] ?? ['name' => $dest_schema['name'], 'url' => $current_url];
    $from_url = (str_starts_with($from_stop['url'], 'http://') || str_starts_with($from_stop['url'], 'https://'))
        ? $from_stop['url']
        : $canonical_base . '/' . ltrim($from_stop['url'], '/');

    $action_node = [
        '@type'        => 'TravelAction',
        '@id'          => $action_id,
        'name'         => $action_schema['name'],
        'actionStatus' => 'https://schema.org/PotentialActionStatus',
        'agent'        => [
            '@type' => 'Organization',
            'name'  => 'VietnamGuide.net',
            'url'   => "{$canonical_base}/",
        ],
        'fromLocation' => [
            '@type' => 'TouristDestination',
            'name'  => $from_stop['name'],
            'url'   => $from_url,
        ],
        'toLocation'   => $to_locations,
        'result'       => ['@id' => $trip_id],
        'instrument'   => [
            '@type' => 'Thing',
            'name'  => $action_schema['method'] ?? 'Express Highway & Rail',
        ],
    ];


    // Connect WebPage and Article nodes to TouristDestination via Schema.org 'about' property
    $nodes = &$data;
    if (isset($data['@graph']) && is_array($data['@graph'])) {
        $nodes = &$data['@graph'];
    }

    $found_org = false;
    $found_primary_image = false;
    $registry_record = vg_get_current_registry_seo_record($uri_path) ?? vg_get_current_registry_seo_record();
    foreach ($nodes as &$node) {
        if (! is_array($node)) {
            continue;
        }
        $node_type = $node['@type'] ?? '';
        $is_article = $node_type === 'Article' || (is_array($node_type) && in_array('Article', $node_type, true));
        $is_webpage = $node_type === 'WebPage' || (is_array($node_type) && in_array('WebPage', $node_type, true));
        $is_image_obj = $node_type === 'ImageObject' || (is_array($node_type) && in_array('ImageObject', $node_type, true));
        $is_breadcrumb = $node_type === 'BreadcrumbList' || (is_array($node_type) && in_array('BreadcrumbList', $node_type, true));

        if ($is_image_obj && ! empty($discover_image['url'])) {
            $img_id = isset($node['@id']) && is_string($node['@id']) ? $node['@id'] : '';
            if (! str_ends_with($img_id, '#logo') && ! str_contains($img_id, 'gravatar.com')) {
                $found_primary_image = true;
                $node['@id'] = $primary_image_id;
                $node['url'] = $discover_image['url'];
                $node['width'] = (int) $discover_image['width'];
                $node['height'] = (int) $discover_image['height'];
                if (! empty($discover_image['caption'])) {
                    $node['caption'] = $discover_image['caption'];
                }
            }
        }

        if ($is_breadcrumb && is_array($registry_record) && ! empty($registry_record['title']) && isset($node['itemListElement']) && is_array($node['itemListElement']) && $node['itemListElement'] !== []) {
            $last_idx = array_key_last($node['itemListElement']);
            if ($last_idx !== null && isset($node['itemListElement'][$last_idx]['item']) && is_array($node['itemListElement'][$last_idx]['item'])) {
                $node['itemListElement'][$last_idx]['item']['name'] = (string) $registry_record['title'];
            }
        }

        if (! $is_non_travel_page && ($is_article || $is_webpage)) {
            // CreativeWork.about -> TouristDestination is standard Schema.org
            if (! isset($node['about'])) {
                $node['about'] = ['@id' => $dest_id];
            } elseif (is_array($node['about'])) {
                $isAssoc = array_keys($node['about']) !== range(0, count($node['about']) - 1);
                if ($isAssoc) {
                    $node['about'] = [$node['about'], ['@id' => $dest_id]];
                } else {
                    $alreadyLinked = false;
                    foreach ($node['about'] as $ab) {
                        if (is_array($ab) && ($ab['@id'] ?? '') === $dest_id) {
                            $alreadyLinked = true;
                            break;
                        }
                    }
                    if (! $alreadyLinked) {
                        $node['about'][] = ['@id' => $dest_id];
                    }
                }
            }
        }

        if ($is_webpage) {
            $node['primaryImageOfPage'] = ['@id' => $primary_image_id];
            if (is_array($registry_record)) {
                if (! empty($registry_record['title'])) {
                    $node['name'] = (string) $registry_record['title'];
                }
                if (! empty($registry_record['description'])) {
                    $node['description'] = (string) $registry_record['description'];
                }
                if (! empty($registry_record['last_reviewed'])) {
                    $node['dateModified'] = vg_format_registry_iso8601((string) $registry_record['last_reviewed']);
                }
            }
        }

        if ($is_article) {
            $last_reviewed = '';
            if (is_array($registry_record)) {
                if (! empty($registry_record['title'])) {
                    $node['headline'] = (string) $registry_record['title'];
                    $node['name'] = (string) $registry_record['title'];
                }
                if (! empty($registry_record['description'])) {
                    $node['description'] = (string) $registry_record['description'];
                }
                if (! empty($registry_record['last_reviewed'])) {
                    $last_reviewed = (string) $registry_record['last_reviewed'];
                }
            }
            if ($last_reviewed !== '') {
                $node['dateModified'] = vg_format_registry_iso8601($last_reviewed);
            } elseif (isset($GLOBALS['post']) && ! empty($GLOBALS['post']->post_modified_gmt)) {
                $node['dateModified'] = date('Y-m-d\TH:i:sP', strtotime($GLOBALS['post']->post_modified_gmt . ' +00:00'));
            } elseif (empty($node['dateModified'])) {
                $node['dateModified'] = date('Y-m-d\TH:i:sP');
            }

            if (! empty($discover_image['url'])) {
                $node['image'] = [
                    '@type'   => 'ImageObject',
                    '@id'     => $primary_image_id,
                    'url'     => $discover_image['url'],
                    'width'   => (int) $discover_image['width'],
                    'height'  => (int) $discover_image['height'],
                    'caption' => $discover_image['caption'],
                ];
            }

            $node['reviewedBy'] = [
                '@type'    => 'Person',
                'name'     => 'VietnamGuide editorial team',
                'url'      => "{$canonical_base}/editorial-policy/",
                'worksFor' => [
                    '@id' => "{$canonical_base}/#organization",
                ],
            ];
            if (isset($node['author']) && is_array($node['author'])) {
                if (empty($node['author']['name'])) {
                    $node['author']['name'] = function_exists('vg_public_editorial_author_name')
                        ? vg_public_editorial_author_name()
                        : 'VietnamGuide editorial team';
                }
                if (! isset($node['author']['url'])) {
                    $node['author']['url'] = "{$canonical_base}/editorial-policy/#team";
                }
                if (! isset($node['author']['worksFor'])) {
                    $node['author']['worksFor'] = [
                        '@type' => 'Organization',
                        '@id'   => "{$canonical_base}/#organization",
                        'name'  => 'VietnamGuide.net',
                    ];
                }
            }
            if (isset($node['publisher']) && is_array($node['publisher']) && ! isset($node['publisher']['logo'])) {
                $node['publisher']['logo'] = [
                    '@type'      => 'ImageObject',
                    '@id'        => "{$canonical_base}/#logo",
                    'url'        => "{$canonical_base}/wp-content/themes/vietnamguide-premium/assets/images/vg-icon-512.png",
                    'caption'    => 'VietnamGuide.net',
                    'inLanguage' => 'en-US',
                    'width'      => 512,
                    'height'     => 512,
                ];
            }
        }

        $is_org = $node_type === 'Organization' || (is_array($node_type) && in_array('Organization', $node_type, true));
        if ($is_org) {
            $found_org = true;
            $node['url'] = "{$canonical_base}/";
            $node['publishingPrinciples'] = "{$canonical_base}/editorial-policy/";
            $node['correctionsPolicy'] = "{$canonical_base}/source-update-policy/";
            $node['knowsAbout'] = [
                'Vietnam Travel Planning',
                'Vietnam Transportation and Rail Logistics',
                'Vietnam Visa Regulations and Entry Policies',
                'Southeast Asia Tourism Safety',
                'Sustainable Travel in Vietnam',
            ];
            $node['logo'] = [
                '@type'      => 'ImageObject',
                '@id'        => "{$canonical_base}/#logo",
                'url'        => "{$canonical_base}/wp-content/themes/vietnamguide-premium/assets/images/vg-icon-512.png",
                'caption'    => 'VietnamGuide.net',
                'inLanguage' => 'en-US',
                'width'      => 512,
                'height'     => 512,
            ];
        }

        $is_person = $node_type === 'Person' || (is_array($node_type) && in_array('Person', $node_type, true));
        if ($is_person) {
            if (empty($node['name'])) {
                $node['name'] = function_exists('vg_public_editorial_author_name')
                    ? vg_public_editorial_author_name()
                    : 'VietnamGuide editorial team';
            }
            $node['jobTitle'] = 'Editorial Desk and Field Research Team';
            $node['description'] = 'Independent travel editors and on-the-ground researchers producing verified route logistics, safety checks, and practical travel guides for Vietnam.';
            $node['url'] = "{$canonical_base}/editorial-policy/#team";
            $node['worksFor'] = [
                '@type' => 'Organization',
                '@id'   => "{$canonical_base}/#organization",
                'name'  => 'VietnamGuide.net',
            ];
            $node['publishingPrinciples'] = "{$canonical_base}/editorial-policy/";
            $node['knowsAbout'] = [
                'Vietnam Travel Logistics',
                'Vietnam Visa Regulations',
                'Public Transport and Rail in Vietnam',
                'Destination Planning',
                'Travel Safety in Southeast Asia',
            ];
        }
    }
    unset($node);

    if (! $is_search_or_404 && ! $found_primary_image && ! empty($discover_image['url'])) {
        $primary_image_node = [
            '@type'      => 'ImageObject',
            '@id'        => $primary_image_id,
            'url'        => $discover_image['url'],
            'width'      => (int) $discover_image['width'],
            'height'     => (int) $discover_image['height'],
            'caption'    => $discover_image['caption'],
            'inLanguage' => 'en-US',
        ];
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            $data['@graph'][] = $primary_image_node;
        } else {
            $data['primary_image'] = $primary_image_node;
        }
    }

    if (! $found_org) {
        $org_node = [
            '@type'                => 'Organization',
            '@id'                  => "{$canonical_base}/#organization",
            'name'                 => 'VietnamGuide.net',
            'url'                  => "{$canonical_base}/",
            'publishingPrinciples' => "{$canonical_base}/editorial-policy/",
            'correctionsPolicy'    => "{$canonical_base}/source-update-policy/",
            'knowsAbout'           => [
                'Vietnam Travel Planning',
                'Vietnam Transportation and Rail Logistics',
                'Vietnam Visa Regulations and Entry Policies',
                'Southeast Asia Tourism Safety',
                'Sustainable Travel in Vietnam',
            ],
            'logo'                 => [
                '@type'      => 'ImageObject',
                '@id'        => "{$canonical_base}/#logo",
                'url'        => "{$canonical_base}/wp-content/themes/vietnamguide-premium/assets/images/vg-icon-512.png",
                'caption'    => 'VietnamGuide.net',
                'inLanguage' => 'en-US',
                'width'      => 512,
                'height'     => 512,
            ],
        ];
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            $data['@graph'][] = $org_node;
        } else {
            $data['organization'] = $org_node;
        }
    }

    if ($is_non_travel_page) {
        return vg_schema_clean_data($data);
    }

    // 4. FAQPage Node (if authoritative Q&As exist for this URI)
    $faq_node = vg_get_page_faq_schema($uri_path, $current_url);

    // 5. HowTo Node (if step-by-step process exists for this URI)
    $howto_node = vg_get_page_howto_schema($uri_path, $current_url);

    // 6. WebApplication Node (if interactive toolkit exists for this URI)
    $webapp_node = vg_get_page_webapplication_schema($uri_path, $current_url);

    // Append our rich Schema nodes to graph or schema array
    if (isset($data['@graph']) && is_array($data['@graph'])) {
        $data['@graph'][] = $dest_node;
        $data['@graph'][] = $trip_node;
        $data['@graph'][] = $action_node;
        if ($faq_node !== null) {
            $data['@graph'][] = $faq_node;
        }
        if ($howto_node !== null) {
            $data['@graph'][] = $howto_node;
        }
        if ($webapp_node !== null) {
            $data['@graph'][] = $webapp_node;
        }
    } else {
        $data['tourist_destination'] = $dest_node;
        $data['tourist_trip'] = $trip_node;
        $data['travel_action'] = $action_node;
        if ($faq_node !== null) {
            $data['faq_page'] = $faq_node;
        }
        if ($howto_node !== null) {
            $data['howto'] = $howto_node;
        }
        if ($webapp_node !== null) {
            $data['web_application'] = $webapp_node;
        }
    }

    $data = vg_schema_clean_data($data);

    return $data;
}

/**
 * Returns curated route-specific FAQ Q&A pairs for both visible page rendering and FAQPage JSON-LD schema.
 */
function vg_get_curated_faq_items(string $uri_path): array
{
    $slug = trim(basename($uri_path), '/');
    $faq_registry = [
        'vietnam-evisa' => [
            ['q' => 'How much does a Vietnam e-visa cost?', 'a' => 'A single-entry Vietnam e-visa costs 25 USD, and a multiple-entry e-visa costs 50 USD. Fees are paid online by credit card and are non-refundable regardless of the application outcome.'],
            ['q' => 'What is the official Vietnam e-visa website?', 'a' => 'The only official government portal for Vietnam e-visas is evisa.xuatnhapcanh.gov.vn (operated by the Vietnam Immigration Department). Avoid commercial third-party websites charging excessive processing markups.'],
            ['q' => 'How far in advance should I apply for a Vietnam e-visa?', 'a' => 'Apply at least 2 weeks before your planned flight. Standard processing takes 3 to 5 business days, but processing stops during Vietnamese national holidays and technical maintenance windows.'],
            ['q' => 'Can I change my port of entry after my Vietnam e-visa is approved?', 'a' => 'No. Your entry checkpoint must match the airport, land border, or seaport approved on your official e-visa document. Changing entry ports requires submitting a new visa application.'],
            ['q' => 'Do I need to print a paper copy of my Vietnam e-visa?', 'a' => 'Yes. Airlines require a physical paper copy at departure check-in, and immigration officers stamp the paper letter upon arrival at the border checkpoint.'],
        ],
        'sim-esim-vietnam' => [
            ['q' => 'Is eSIM or physical SIM better for traveling in Vietnam?', 'a' => 'An eSIM is convenient and activates before arrival if your phone is carrier-unlocked. A physical SIM card is preferable if your phone is locked or if you need a reliable local phone number to receive Grab driver calls.'],
            ['q' => 'Which mobile network has the best coverage in Vietnam?', 'a' => 'Viettel provides the widest and most reliable 4G network coverage across Vietnam, especially in mountainous regions like Sapa and Ha Giang and offshore islands. Vinaphone is a solid secondary choice in major cities.'],
            ['q' => 'Can I purchase a SIM card upon arrival at Vietnam airports?', 'a' => 'Yes. Official carrier kiosks (Viettel, Vinaphone, Mobifone) operate directly outside the baggage claim halls at Hanoi (Noi Bai), Ho Chi Minh City (Tan Son Nhat), and Da Nang international airports.'],
        ],
        'vietnam-airport-arrival-checklist' => [
            ['q' => 'How much cash should I withdraw upon arrival at a Vietnam airport?', 'a' => 'Withdraw 1 to 2 million VND (approximately 40 to 80 USD) from official bank ATMs (such as Vietcombank or BIDV) in the arrivals hall to cover initial taxi fares and street expenses.'],
            ['q' => 'How do I avoid taxi scams at Vietnam airports?', 'a' => 'Use the Grab app over airport Wi-Fi or visit official prepaid taxi counters (Mai Linh or Vinasun) inside the terminal. Never follow unbadged touts offering private transportation in the terminal corridors.'],
            ['q' => 'How long does immigration clearance take at Hanoi and Saigon airports?', 'a' => 'Standard passport control takes 30 to 60 minutes. During peak arrival periods in late afternoon and evening, wait times can reach 90 minutes.'],
        ],
        'hanoi-to-sapa-transport' => [
            ['q' => 'What is the fastest way to get from Hanoi to Sapa?', 'a' => 'A sleeper cabin bus or luxury limousine van traveling via the Noi Bai - Lao Cai expressway takes 5.5 to 6 hours door-to-door, which is faster and more direct than the train.'],
            ['q' => 'Is the overnight train from Hanoi to Sapa comfortable?', 'a' => 'The overnight sleeper train offers a smooth ride with air-conditioned 4-berth cabins. It arrives in Lao Cai station at 5:30 AM, where you transfer to a 50-minute mountain shuttle van up to Sapa town.'],
            ['q' => 'Can I book a private car transfer from Hanoi to Sapa?', 'a' => 'Yes. Private car transfers take approximately 5 hours door-to-door, offering total schedule flexibility and luggage convenience for families and small travel groups.'],
        ],
        'ha-giang-easy-rider-vs-self-drive' => [
            ['q' => 'Can tourists legally ride a motorbike on the Ha Giang Loop?', 'a' => 'Vietnam only recognizes the 1968 International Driving Permit (IDP) with an active motorcycle endorsement. Driving on a 1949 IDP or home automobile license is illegal and voids travel medical insurance policies.'],
            ['q' => 'What is an Easy Rider tour in Ha Giang?', 'a' => 'An Easy Rider is a professional local rider who navigates the motorcycle while you sit comfortably on the rear passenger pillion seat, allowing you to view mountain landscapes without driving hazards.'],
            ['q' => 'How dangerous is driving the Ha Giang Loop independently?', 'a' => 'Independent driving carries real risks from steep mountain gradients, sharp hairpin turns on Ma Pi Leng Pass, loose gravel, unpredictable construction vehicles, and sudden mountain fog.'],
        ],
        'ha-long-bay-cruise-questions-before-booking' => [
            ['q' => 'Should I choose a 2-day or 3-day Ha Long Bay cruise?', 'a' => 'A 2-day/1-night cruise gives 24 hours on the water and suits compact itineraries. A 3-day/2-night cruise travels deeper into quieter Lan Ha or Bai Tu Long bays with unhurried kayaking and swimming time.'],
            ['q' => 'What amenities are included in an overnight Ha Long Bay cruise?', 'a' => 'Cruise rates include all onboard meals, private en-suite cabin, cave excursions, and kayak access. Highway transfers between Hanoi and the harbor and personal beverages are usually billed separately.'],
            ['q' => 'What is the cruise cancellation policy during bad weather?', 'a' => 'The local maritime port authority suspends sailings during typhoons or dense fog for passenger safety. Reputable operators provide full refunds or day-tour alternatives in accordance with maritime law.'],
        ],
        'trang-an-vs-tam-coc' => [
            ['q' => 'Is Trang An or Tam Coc better in Ninh Binh?', 'a' => 'Trang An features dramatic karst water cave tunnels, strict lifejacket rules, and organized UNESCO management. Tam Coc offers open river paddling through scenic rice fields with rowers using their feet.'],
            ['q' => 'How long does the boat tour take in Trang An and Tam Coc?', 'a' => 'Trang An boat circuits last 2.5 to 3 hours through 3 to 4 caves. Sampan excursions along the Ngo Dong river take approximately 1.5 to 2 hours.'],
            ['q' => 'Which boat route in Trang An is recommended?', 'a' => 'Route 2 (visiting Dot Cave and Dia Linh Cave) and Route 3 (featuring the 1,000-meter cave) provide the finest combination of karst scenery, cave passages, and historic temples.'],
        ],
        'vietnam-travel-cost' => [
            ['q' => 'What is a realistic daily travel budget for Vietnam?', 'a' => 'Budget backpackers spend 30 to 45 USD per day. Mid-range travelers staying in 3-star boutique hotels and taking domestic flights spend 70 to 120 USD per day. Luxury travelers spend 200 USD and up per day.'],
            ['q' => 'Is Vietnam cheaper than Thailand for travelers?', 'a' => 'Vietnam is generally 15 to 25 percent less expensive than Thailand for street meals, local transportation, and city boutique accommodations, while guided expeditions and luxury cruises cost similar amounts.'],
            ['q' => 'Do I need cash in Vietnam or are cards widely accepted?', 'a' => 'Credit cards are accepted at mid-range hotels, supermarkets, and established restaurants in major cities. Cash in Vietnamese Dong (VND) is essential for street dining, small market stalls, and rural taxis.'],
        ],
        'ha-long-bay-vs-lan-ha-bay' => [
            ['q' => 'Is Ha Long Bay or Lan Ha Bay better?', 'a' => 'Lan Ha Bay offers quieter waters, fewer tourist boats, secluded sandy swimming coves, and unhurried kayaking around Cat Ba Island. Ha Long Bay provides iconic grand limestone cave formations (Sung Sot Cave) and panoramic viewpoint peaks (Ti Top Island), but with higher vessel traffic.'],
            ['q' => 'Can I visit both Ha Long Bay and Lan Ha Bay on the same cruise?', 'a' => 'Most 2-day/1-night cruises operate in either Ha Long Bay or Lan Ha Bay due to port jurisdiction boundaries. Longer 3-day/2-night itineraries frequently navigate through connecting border waters into quieter coves.'],
            ['q' => 'Which departure port is used for Lan Ha Bay cruises?', 'a' => 'Lan Ha Bay cruises depart primarily from Ben Beo harbor or Got and Tuan Chau ferry terminals connecting to Cat Ba Island, whereas Ha Long Bay cruises depart from Tuan Chau International Marina or Halong International Cruise Port.'],
        ],
        'da-nang-vs-hoi-an' => [
            ['q' => 'Should I stay in Da Nang or Hoi An?', 'a' => 'Stay in Da Nang for expansive ocean beaches (My Khe), luxury high-rise resorts, modern seafood dining, and evening riverside entertainment. Stay in Hoi An for historic pedestrian streets, lantern-lit riverside evenings, cooking schools, and tailor shops. The two destinations are only 45 minutes apart by taxi.'],
            ['q' => 'How far is Hoi An from Da Nang and how do I travel between them?', 'a' => 'Hoi An is 30 kilometers (18.5 miles) south of Da Nang. The trip takes 40 to 45 minutes by taxi or Grab, costing approximately 250,000 to 350,000 VND (10 to 14 USD) each way.'],
            ['q' => 'Can I visit Da Nang as a day trip from Hoi An?', 'a' => 'Yes. Many travelers base themselves in Hoi An for its atmospheric charm and make day trips to Da Nang to visit the Marble Mountains, Son Tra Peninsula (Monkey Mountain), and My Khe beach.'],
        ],
        'best-time-to-visit-vietnam' => [
            ['q' => 'What is the best month to visit Vietnam overall?', 'a' => 'March and April, along with October and November, offer the most balanced weather conditions nationwide, with moderate temperatures and low rainfall across northern, central, and southern regions.'],
            ['q' => 'When is the rainy season in central Vietnam (Da Nang, Hoi An, Hue)?', 'a' => 'The rainy and typhoon season in central Vietnam runs from September through December, with peak rainfall and localized river flooding risks in October and November.'],
            ['q' => 'When is the best time to visit Sapa and northern mountain rice terraces?', 'a' => 'The best time to visit Sapa is September to early October for golden ripe harvest terraces, and April to May for the watering season with clear skies and mild trekking weather.'],
        ],
        'ninh-binh-to-ha-long-bay-transfer' => [
            ['q' => 'How do I travel directly from Ninh Binh to Ha Long Bay?', 'a' => 'Take a direct shared limousine shuttle bus via National Highway 10 and the Hai Phong Expressway. The journey takes 3.5 to 4 hours door-to-door and costs 300,000 to 450,000 VND (12 to 18 USD), avoiding the need to backtrack through Hanoi.'],
            ['q' => 'Can I make it from Ninh Binh to Ha Long Bay in time for a cruise boarding?', 'a' => 'Yes, if you depart Ninh Binh by 6:30 to 7:00 AM. Cruise boarding at Tuan Chau or Halong International Port typically closes between 11:30 AM and 12:00 PM. A private car offers maximum schedule security.'],
            ['q' => 'Is there a train between Ninh Binh and Ha Long Bay?', 'a' => 'No direct train connects Ninh Binh and Ha Long Bay. Rail travel requires transferring trains in Hanoi, which takes over 7 hours and is not recommended compared to direct express highway limousines.'],
        ],
        'vietnam-first-trip-planning-checklist' => [
            ['q' => 'What travel documents are mandatory when entering Vietnam?', 'a' => 'You must present a valid passport with at least 6 months remaining validity from your entry date, an approved electronic visa (e-visa) printed copy or proof of visa exemption, and an onward or return flight ticket.'],
            ['q' => 'Do electrical outlets in Vietnam require a plug adapter?', 'a' => 'Vietnam primarily uses Type A, Type C, and Type G electrical outlets running at 220V/50Hz. Most modern hotel sockets accept both flat two-prong US plugs (Type A) and round two-prong Euro plugs (Type C) without an adapter.'],
            ['q' => 'Do I need prescription malaria medication for traveling in Vietnam?', 'a' => 'Major tourist cities and resort destinations (Hanoi, HCMC, Da Nang, Hoi An, Nha Trang, Phu Quoc) have negligible malaria risk. Antimalarial prophylaxis is generally only considered for deep remote jungle trekking along border zones. Dengue fever prevention via DEET insect repellent is the primary health priority.'],
        ],
        'itineraries' => [
            ['q' => 'How many days do you need for a comprehensive first trip to Vietnam?', 'a' => 'A 10 to 14-day trip is the ideal duration for a first visit. Ten days allows you to explore the classic highlights (Hanoi, Ha Long Bay, Hoi An, and Ho Chi Minh City). Fourteen days provides enough breathing room to add Ninh Binh, Hue, or the Mekong Delta with realistic transit pacing.'],
            ['q' => 'Is it better to travel North to South or South to North in Vietnam?', 'a' => 'Both directions work equally well because domestic flights and overnight sleeper trains connect all major hubs. Starting in Hanoi (North) offers an immediate immersion in historic culture and karst landscapes, finishing with relaxed tropical energy in the South.'],
            ['q' => 'What is the most time-efficient way to travel between regions in Vietnam?', 'a' => 'Domestic flights on Vietnam Airlines or Vietjet between Hanoi, Da Nang, and Ho Chi Minh City take only 1 hour 20 minutes each, saving entire days compared to 16-to-30-hour overland bus or train journeys across the 1,600 km length of the country.'],
        ],
        'hanoi-travel-guide' => [
            ['q' => 'How many days should I spend in Hanoi?', 'a' => 'Allocate 2 to 3 full days inside Hanoi to explore the Old Quarter, Hoan Kiem Lake, Temple of Literature, and Ba Dinh historic district, plus 1 to 2 extra days if taking day trips to Ninh Binh or Ha Long Bay.'],
            ['q' => 'How do I get from Noi Bai Airport (HAN) to Hanoi Old Quarter?', 'a' => 'Route 86 Express Bus runs every 25 to 30 minutes from Terminal 1 and Terminal 2 directly to Hoan Kiem Lake for 45,000 VND (1.80 USD) in 45 to 55 minutes. Metered taxis and Grab cars cost 300,000 to 400,000 VND (12 to 16 USD).'],
            ['q' => 'Which neighborhood is best for first-time visitors in Hanoi?', 'a' => 'Hoan Kiem (Old Quarter and French Quarter) puts you within a 10-minute walk of major museums, water puppet theaters, and street food alleys. Tay Ho (West Lake) suits longer stays seeking quieter lakeside cafes.'],
        ],
        'ho-chi-minh-city-travel-guide' => [
            ['q' => 'How far is Tan Son Nhat Airport (SGN) from District 1 in Ho Chi Minh City?', 'a' => 'Tan Son Nhat Airport sits 8 kilometers north of District 1. Bus 109 or Bus 152 reaches Ben Thanh Market in 30 to 45 minutes for 5,000 to 20,000 VND, while a Grab car or Vinasun/Mai Linh metered taxi costs 120,000 to 180,000 VND (5 to 7.50 USD).'],
            ['q' => 'How many days are needed for Ho Chi Minh City?', 'a' => 'Two full days cover District 1 colonial landmarks, the War Remnants Museum, Ben Thanh Market, and Chinatown (Cholon), while a third or fourth day allows day trips to the Cu Chi Tunnels or the Mekong Delta.'],
            ['q' => 'Which district is best to stay in Ho Chi Minh City?', 'a' => 'District 1 is best for first-time visitors wanting walkable access to museums, Nguyen Hue Walking Street, and Ben Thanh Metro Station. District 3 offers tree-lined streets and local dining, while Thao Dien (District 2) suits boutique cafe stays.'],
        ],
        'da-lat-travel-guide' => [
            ['q' => 'How do I get to Da Lat from Ho Chi Minh City or Nha Trang?', 'a' => 'Direct flights land at Lien Khuong Airport (DLI), located 30 km south of Da Lat center (40 minutes by shuttle bus for 40,000 to 50,000 VND or taxi for 250,000 to 300,000 VND). Overland limousine sleeper buses take 6.5 to 7.5 hours from Ho Chi Minh City (300 km) or 3.5 to 4 hours from Nha Trang (135 km via Khanh Le Pass).'],
            ['q' => 'What is the weather like in Da Lat year-round?', 'a' => 'Seated at 1,500 meters elevation on the Langbiang Plateau, Da Lat maintains cool temperatures of 14°C to 24°C year-round. The dry pine season runs from November to March, while afternoon rains occur from May to October.'],
            ['q' => 'How many days should I plan for Da Lat?', 'a' => 'Plan 3 days in Da Lat to cover Xuan Huong Lake, Datanla or Pongour waterfalls, Langbiang Peak trekking, and highland Arabica coffee plantations in Cau Dat.'],
        ],
        'mang-den-travel-guide' => [
            ['q' => 'How do I travel to Mang Den in Kon Tum Province?', 'a' => 'Fly into Pleiku Airport (PXU), located 100 kilometers southwest of Mang Den, then take a 2.5-hour taxi (900,000 to 1,100,000 VND) or local bus via Kon Tum city along National Highway 24. Overnight sleeper buses also run from Ho Chi Minh City and Da Nang.'],
            ['q' => 'What is the elevation and best season to visit Mang Den?', 'a' => 'Mang Den sits at 1,200 meters elevation with cool pine forests and temperatures between 15°C and 22°C. The dry season from December to April brings clear skies, cherry blossoms in January, and dry forest trails around Pa Sy Waterfall and Dak Ke Lake.'],
            ['q' => 'How does Mang Den compare to Da Lat?', 'a' => 'Mang Den is much quieter and less urbanized than Da Lat, retaining dense primary pine canopy, lower traffic density, and small-scale eco-lodges rather than large commercial shopping districts.'],
        ],
        'phong-nha-travel-guide' => [
            ['q' => 'How do I reach Phong Nha-Ke Bang National Park?', 'a' => 'Arrive at Dong Hoi Airport (VDH) or Dong Hoi Railway Station, both located 45 kilometers (50 minutes by taxi, 450,000 to 550,000 VND, or local B4 bus for 35,000 VND) east of Phong Nha town. Direct sleeper buses also stop in Phong Nha from Hue (4 hours), Ninh Binh (7 hours), and Hanoi (9 hours).'],
            ['q' => 'Which caves in Phong Nha can I visit without booking months in advance?', 'a' => 'Paradise Cave (31 km dry karst system, 250,000 VND entry), Phong Nha Cave (underground boat ride, 150,000 VND entry plus 550,000 VND shared boat for up to 12 people), and Dark Cave can be visited on arrival without multi-month expedition permits.'],
            ['q' => 'When is Phong Nha affected by seasonal flooding?', 'a' => 'Heavy autumn rains in October and November raise the Son River level, occasionally submerging the boat entrance to Phong Nha Cave and river caverns, while elevated dry caverns like Paradise Cave usually remain open.'],
        ],
        'mekong-delta-travel-guide' => [
            ['q' => 'Is Can Tho or Ben Tre better for visiting the Mekong Delta?', 'a' => 'Ben Tre (85 km from Ho Chi Minh City, 2 hours by car) is ideal for a 1-day or 2-day trip focusing on shaded coconut canals, brick kilns, and quiet cycling paths. Can Tho (170 km, 3.5 hours by bus) is essential for seeing the Cai Rang Floating Market at dawn.'],
            ['q' => 'What time should I arrive at Cai Rang Floating Market in Can Tho?', 'a' => 'Depart Ninh Kieu Wharf by sampan between 5:15 AM and 5:45 AM to reach Cai Rang Floating Market at peak wholesale trading between 6:00 AM and 7:30 AM before tropical heat disperses the merchant boats.'],
            ['q' => 'Can I take a boat from the Mekong Delta to Phnom Penh, Cambodia?', 'a' => 'Yes. After traveling overland to Chau Doc on the Cambodian border, high-speed passenger boats depart daily at 7:00 AM to 7:30 AM up the Mekong River to Phnom Penh, taking 5 hours including Vinh Xuong river border immigration.'],
        ],
        'cao-bang-travel-guide' => [
            ['q' => 'How do I get from Hanoi to Cao Bang and Ban Gioc Waterfall?', 'a' => 'Overnight sleeper buses and VIP limousine vans depart Hanoi (My Dinh or Gia Lam stations) for Cao Bang city, covering 280 km in 6 to 7 hours for 300,000 to 450,000 VND. From Cao Bang city, local buses or motorbikes cover the remaining 85 km to Ban Gioc Waterfall in 2 to 2.5 hours.'],
            ['q' => 'When does Ban Gioc Waterfall have the best water flow?', 'a' => 'September and October offer the strongest turquoise water volume combined with golden rice harvests and clear skies, immediately following the June-to-August summer rains.'],
            ['q' => 'Do foreign tourists need a border permit for Ban Gioc Waterfall?', 'a' => 'Walking visits to the main Ban Gioc Waterfall viewpoint and Nguom Ngao Cave (45,000 VND ticket each) only require carrying your original passport for hotel registration and checkpoint verification.'],
        ],
        'sapa-travel-guide' => [
            ['q' => 'How do I reach Fansipan Peak in Sapa?', 'a' => 'Take the Muong Hoa funicular train from Sapa Plaza to the cable car station, ride the 15-minute three-rope cable car to 3,000 meters, and climb 600 stone steps (or take the summit funicular) to the 3,143-meter roof of Indochina. Round-trip cable car tickets cost 800,000 to 850,000 VND.'],
            ['q' => 'What is the best trekking route in Sapa?', 'a' => 'The Muong Hoa Valley trek from Y Linh Ho through Lao Chai to Ta Van village (12 to 15 km, 4 to 5 hours) follows terraced rice slopes and suspension bridges with Black Hmong and Giay local guides.'],
            ['q' => 'What months have the clearest views in Sapa?', 'a' => 'September to October (golden rice harvest) and March to May offer the highest visibility and pleasant 15°C to 24°C trekking weather, whereas December to February often brings dense freezing fog.'],
        ],
        'ha-giang-loop-planning-guide' => [
            ['q' => 'How many days are needed for the Ha Giang Loop?', 'a' => 'Plan 3 to 4 full days on the loop (plus 2 travel nights on the 6-hour sleeper bus from Hanoi) to cover the 350-kilometer circuit across Quan Ba, Yen Minh, Dong Van, Ma Pi Leng Pass, and Du Gia safely without rushing mountain passes after dark.'],
            ['q' => 'Do I need a permit to visit the Ha Giang Loop?', 'a' => 'Foreign travelers entering border districts (Quan Ba, Yen Minh, Dong Van, Meo Vac) require a Ha Giang border area permit costing 230,000 VND (approx. 10 USD), obtainable in 10 minutes at the Ha Giang Immigration Office or via your homestay/tour operator.'],
            ['q' => 'What is the Nho Que River boat ride cost on the Ha Giang Loop?', 'a' => 'Boats through Tu San Alley canyon on the Nho Que River below Ma Pi Leng Pass cost 120,000 to 150,000 VND per person, including the electric shuttle van transfer down to the boat dock.'],
        ],
        'ninh-binh-travel-guide' => [
            ['q' => 'How long is the train or bus ride from Hanoi to Ninh Binh?', 'a' => 'Reunification Express trains (SE1, SE3, SE5, SE7) from Hanoi Railway Station to Ninh Binh Station take 2 hours and 15 minutes (100,000 to 180,000 VND). Shared limousine vans via the Phap Van - Cau Gie Expressway take 1.5 to 2 hours door-to-door to Tam Coc or Trang An (150,000 to 250,000 VND).'],
            ['q' => 'Should I stay in Tam Coc or Trang An in Ninh Binh?', 'a' => 'Stay in Tam Coc or Trang An rather than Ninh Binh city center. Tam Coc offers walkable restaurants, bicycle rentals, and boat pier access, while Trang An and Hoa Lu provide quiet valley eco-lodges surrounded by limestone karsts.'],
            ['q' => 'How many steps is the climb at Hang Mua (Mua Cave) viewpoint?', 'a' => 'Hang Mua viewpoint features 486 stone steps ascending Ngoa Long Mountain above the Tam Coc river valley. Entry costs 100,000 VND per person, and arriving before 7:30 AM avoids midday heat and tour crowds.'],
        ],
        'ha-long-bay-travel-guide' => [
            ['q' => 'How long does it take to travel from Hanoi to Ha Long Bay?', 'a' => 'Via the Hanoi - Hai Phong - Ha Long Expressway (CT04/CT06), shared limousine vans and private cars cover the 155-kilometer distance from Hanoi Old Quarter to Tuan Chau or Halong International Cruise Port in 2.5 hours.'],
            ['q' => 'What is the difference between Ha Long Bay, Lan Ha Bay, and Bai Tu Long Bay?', 'a' => 'All three share the same Gulf of Tonkin limestone karst geology. Ha Long Bay is the central UNESCO zone with Sung Sot Cave and Ti Top Island; Lan Ha Bay (south, off Cat Ba Island) favors kayaking and quieter coves; Bai Tu Long Bay (northeast) features the lowest cruise vessel density.'],
            ['q' => 'How much does an overnight Ha Long Bay cruise cost?', 'a' => 'Reputable 3-star to 4-star overnight cruises cost 130 to 190 USD per person for a 2-day/1-night package (including meals, cabin, and kayaking), while 5-star luxury steel vessels range from 220 to 400 USD per person.'],
        ],
        'da-nang-travel-guide' => [
            ['q' => 'How far is Da Nang Airport (DAD) from My Khe Beach?', 'a' => 'Da Nang International Airport is situated right inside the city, only 5.5 kilometers (12 to 15 minutes by Grab or metered taxi, costing 80,000 to 120,000 VND) west of My Khe Beach across the Han River bridges.'],
            ['q' => 'What time does the Dragon Bridge breathe fire in Da Nang?', 'a' => 'The Dragon Bridge (Cau Rong) breathes fire and sprays water every Friday, Saturday, and Sunday night at exactly 9:00 PM for 15 minutes; vehicle traffic across the bridge closes at 8:45 PM.'],
            ['q' => 'How much does it cost to visit Marble Mountains vs Ba Na Hills?', 'a' => 'Marble Mountains (Ngu Hanh Son) costs 40,000 VND entry plus 15,000 VND for the glass elevator and 40,000 VND for Am Phu Cave. Sun World Ba Na Hills cable car and Golden Bridge admission costs 900,000 VND for international adult visitors.'],
        ],
        'hoi-an-ancient-town-guide' => [
            ['q' => 'How does the Hoi An Ancient Town entrance ticket work?', 'a' => 'An Ancient Town heritage ticket costs 120,000 VND (approx. 5 USD) and includes 5 entry coupons to choose from 21 historic assembly halls, merchant houses, museums, and the Japanese Covered Bridge. Pedestrian streets are closed to motorbikes daily from 9:00 AM–11:00 AM and 3:00 PM–9:30 PM.'],
            ['q' => 'How long does custom tailoring take in Hoi An?', 'a' => 'Allow at least 48 to 72 hours in Hoi An for custom tailoring so your tailor can complete the initial measurement, a first basted fitting within 24 hours, and final garment adjustments before departure.'],
            ['q' => 'Which beach is closest to Hoi An Ancient Town?', 'a' => 'An Bang Beach and Cua Dai Beach sit 4.5 kilometers (15 minutes by bicycle or 10 minutes by Grab taxi for 60,000 to 80,000 VND) east of Hoi An Ancient Town along Hai Ba Trung or Cua Dai roads.'],
        ],
        'hue-imperial-city-guide' => [
            ['q' => 'How much is the entrance ticket for the Hue Imperial Citadel and Royal Tombs?', 'a' => 'Adult admission to the Hue Imperial Citadel costs 200,000 VND. Individual tickets for the Royal Tombs of Minh Mang, Khai Dinh, or Tu Duc cost 150,000 VND each, while a 3-site combo ticket (Citadel + 2 Tombs) costs 420,000 VND and a 4-site combo costs 530,000 VND.'],
            ['q' => 'Should I take the train or car over the Hai Van Pass between Da Nang and Hue?', 'a' => 'Both offer coastal views: the heritage train (HD1/HD2 or Reunification Express) hugs the cliffside tracks above Lang Co Bay for 2.5 to 3 hours (110,000 to 180,000 VND), while a private car or motorbike over the 21-kilometer Hai Van Pass allows stops at Hai Van Quan summit fort and Lap An Lagoon.'],
            ['q' => 'How much time is needed inside the Hue Imperial City?', 'a' => 'Plan 2.5 to 3.5 hours to walk through Ngo Mon Gate, Thai Hoa Palace, the Purple Forbidden City, Dien Tho Residence, and the Royal Theatre within the 520-hectare walled Citadel.'],
        ],
        'phu-quoc-travel-guide' => [
            ['q' => 'Do foreign tourists need a visa for Phu Quoc Island?', 'a' => 'All foreign nationalities receive a 30-day visa exemption exclusively for Phu Quoc Island if flying directly into or out of Phu Quoc International Airport (PQC) from an international origin, or transiting a domestic Vietnamese airport airside without entering mainland Vietnam.'],
            ['q' => 'When is the dry beach season in Phu Quoc?', 'a' => 'The dry, calm beach season on Phu Quoc west coast (Long Beach, Ong Lang, Sao Beach) runs from November through April with 26°C to 31°C sunshine and clear snorkeling visibility around the An Thoi archipelago.'],
            ['q' => 'How long is the Hon Thom Cable Car in Phu Quoc?', 'a' => 'The Hon Thom 3-wire sea-crossing cable car spans 7,899.9 meters from An Thoi town across the southern islands to Hon Thom (Pineapple Island) in 15 minutes; round-trip tickets cost 650,000 VND.'],
        ],
        'con-dao-travel-guide' => [
            ['q' => 'How do I get to Con Dao Islands from Ho Chi Minh City or Can Tho?', 'a' => 'Direct flights from Ho Chi Minh City (SGN) to Con Dao Airport (VCS) take 45 to 55 minutes. High-speed catamarans depart Tran De Port (Soc Trang/Can Tho, 2.5 hours, 320,000 to 500,000 VND) or Vung Tau across the open sea during calm months (March to September).'],
            ['q' => 'When is sea turtle nesting season on Con Dao?', 'a' => 'Green sea turtles (Chelonia mydas) nest on Bay Canh Island inside Con Dao National Park from May through October, with peak egg-laying and hatchling releases occurring between June and September under ranger supervision.'],
            ['q' => 'How do I visit the historic prison sites on Con Son Island?', 'a' => 'Purchase a 50,000 VND combined heritage ticket at the Con Dao Museum to visit Phu Hai Prison, the French and American Tiger Cages, and Phu Tuong Camp during morning hours (7:30 AM–11:30 AM) or afternoon hours (1:30 PM–4:30 PM).'],
        ],
    ];

    return $faq_registry[$slug] ?? [];
}

/**
 * Renders visible HTML5 <details>/<summary> FAQ accordion matching FAQPage JSON-LD schema.
 * Strictly uses level-3 heading to preserve the Zero-H2 TOC invariant across inc/*.php.
 */
function vg_render_curated_faq_accordion(string $uri_path, string $existing_html = ''): string
{
    $items = vg_get_curated_faq_items($uri_path);
    if (empty($items)) {
        return '';
    }

    if ($existing_html !== '' && stripos($existing_html, 'vg-curated-faq') !== false) {
        return '';
    }

    $visible_items = [];
    foreach ($items as $item) {
        $question = trim((string) ($item['q'] ?? ''));
        $answer   = trim((string) ($item['a'] ?? ''));
        if ($question === '' || $answer === '') {
            continue;
        }
        if ($existing_html !== '' && stripos($existing_html, $question) !== false) {
            continue;
        }
        $visible_items[] = [
            'q' => $question,
            'a' => $answer,
        ];
    }

    if (empty($visible_items)) {
        return '';
    }

    $rows_html = '';
    foreach ($visible_items as $idx => $qa) {
        $open_attr = $idx === 0 ? ' open' : '';
        $rows_html .= sprintf(
            '<details class="vg-faq-item"%1$s><summary class="vg-faq-question">%2$s</summary><div class="vg-faq-answer"><p>%3$s</p></div></details>',
            $open_attr,
            esc_html($qa['q']),
            esc_html($qa['a'])
        );
    }

    return sprintf(
        '<section class="vg-faq-section vg-curated-faq" id="vg-curated-faq" aria-label="Frequently Asked Questions">'
        . '<div class="vg-faq-header">'
        . '<span class="vg-faq-kicker">Verified Field Answers</span>'
        . '<h3 class="vg-faq-heading">Frequently Asked Questions</h3>'
        . '</div>'
        . '<div class="vg-faq-list">%s</div>'
        . '</section>',
        $rows_html
    );
}

/**
 * Appends the visible curated FAQ accordion on non-Guide-Shell pages (e.g. /costs/vietnam-travel-cost/, /itineraries/)
 * so every page emitting FAQPage JSON-LD schema displays matching visible Q&A markup.
 */
function vg_inject_curated_faq_on_non_guide_pages(string $content): string
{
    if (function_exists('is_admin') && is_admin()) {
        return $content;
    }

    if (strpos($content, 'vg-guide-hero') !== false) {
        return $content;
    }

    if (function_exists('vg_is_guide_experience_page') && vg_is_guide_experience_page()) {
        return $content;
    }

    $uri_path = function_exists('vg_get_current_uri_path') ? vg_get_current_uri_path() : '';
    if ($uri_path === '' && function_exists('get_post')) {
        $post = get_post();
        if ($post instanceof WP_Post && function_exists('get_page_uri')) {
            $uri_path = trim((string) get_page_uri($post), '/');
        }
    }

    if ($uri_path === '') {
        return $content;
    }

    $faq_html = vg_render_curated_faq_accordion($uri_path, $content);
    if ($faq_html === '') {
        return $content;
    }

    return $content . "\n\n" . $faq_html;
}
if (function_exists('add_filter')) {
    add_filter('the_content', 'vg_inject_curated_faq_on_non_guide_pages', 30);
}

/**
 * Authoritative FAQ Schema.org generator for high-intent travel planning queries.
 */
function vg_get_page_faq_schema(string $uri_path, string $current_url): ?array
{
    $curated_qas = vg_get_curated_faq_items($uri_path);
    $raw_qas = ! empty($curated_qas) ? $curated_qas : null;

    // Dynamic fallback: extract structured Q&As from authored details/summary markup if not in curated registry
    if ($raw_qas === null && function_exists('get_queried_object')) {
        $post = get_queried_object();
        if (! ($post instanceof WP_Post) && function_exists('get_post')) {
            $post = get_post();
        }
        if ($post instanceof WP_Post && ! empty($post->post_content)) {
            $content = $post->post_content;
            if (stripos($content, '<details') !== false) {
                if (preg_match_all('/<details[^>]*>\s*<summary[^>]*>(.*?)<\/summary>\s*(?:<p[^>]*>)?(.*?)(?:<\/p>)?\s*<\/details>/is', $content, $matches, PREG_SET_ORDER)) {
                    $dynamic_qas = [];
                    foreach ($matches as $match) {
                        $q = trim(html_entity_decode(wp_strip_all_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                        $a = trim(html_entity_decode(wp_strip_all_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                        if (mb_strlen($q) >= 10 && mb_strlen($a) >= 20) {
                            $dynamic_qas[] = ['q' => $q, 'a' => $a];
                        }
                    }
                    if (! empty($dynamic_qas)) {
                        $raw_qas = $dynamic_qas;
                    }
                }
            }
        }
    }

    if (empty($raw_qas)) {
        return null;
    }

    $questions = [];
    foreach ($raw_qas as $item) {
        $questions[] = [
            '@type'          => 'Question',
            'name'           => $item['q'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => $item['a'],
            ],
        ];
    }

    return [
        '@type'      => 'FAQPage',
        '@id'        => "{$current_url}#faq",
        'isPartOf'   => ['@id' => "{$current_url}#webpage"],
        'mainEntity' => $questions,
    ];
}

/**
 * Authoritative HowTo Schema.org generator for process and application guides.
 */
function vg_get_page_howto_schema(string $uri_path, string $current_url): ?array
{
    $slug = trim(basename($uri_path), '/');
    $howto_registry = [
        'vietnam-evisa' => [
            'name'        => 'How to Apply for a Vietnam E-Visa Online',
            'description' => 'Step-by-step instructions for submitting a valid Vietnam electronic visa application through the official government immigration portal.',
            'steps'       => [
                ['name' => 'Prepare Required Documents', 'text' => 'Ensure your passport has at least 6 months validity. Prepare a sharp JPEG digital scan of your passport bio page and a passport-style portrait photo on a plain white background without glasses.'],
                ['name' => 'Access the Official Immigration Portal', 'text' => 'Visit the official government website at evisa.xuatnhapcanh.gov.vn. Avoid third-party commercial agency portals that charge marked-up intermediary fees.'],
                ['name' => 'Complete the Application Form', 'text' => 'Fill in your full legal name, date of birth, passport details, temporary address in Vietnam, and select your specific entry and exit international border checkpoints.'],
                ['name' => 'Pay the Visa Processing Fee', 'text' => 'Pay the official non-refundable fee (25 USD for single-entry up to 90 days, or 50 USD for multiple-entry) using an international debit or credit card.'],
                ['name' => 'Track Status and Print Approval Letter', 'text' => 'Record your registration code. Check application status after 3 to 5 working days. Once approved, download and print two physical copies of the e-visa letter for departure check-in and border inspection.'],
            ],
        ],
        'sim-esim-vietnam' => [
            'name'        => 'How to Buy and Set Up an eSIM for Vietnam',
            'description' => 'Step-by-step guide to purchasing, installing, and activating an electronic SIM profile for mobile data in Vietnam.',
            'steps'       => [
                ['name' => 'Verify Phone Compatibility', 'text' => 'Confirm your smartphone is carrier-unlocked and supports eSIM functionality in device cellular settings.'],
                ['name' => 'Select Network and Data Plan', 'text' => 'Choose an authorized provider powered by the Viettel network for superior nationwide coverage, especially in highland and island destinations.'],
                ['name' => 'Install eSIM via QR Code', 'text' => 'Receive your digital activation QR code by email. Open device settings, select Add eSIM, and scan the QR code before boarding your flight.'],
                ['name' => 'Activate Data Roaming on Arrival', 'text' => 'Upon touchdown at any Vietnam international airport, switch your cellular data line to the Vietnam eSIM profile and toggle Data Roaming to ON.'],
            ],
        ],
        'vietnam-airport-arrival-checklist' => [
            'name'        => 'How to Clear Vietnam Airport Arrival and Transfer to the City Safely',
            'description' => 'Step-by-step arrival workflow at Hanoi (HAN), Ho Chi Minh City (SGN), and Da Nang (DAD) international airports.',
            'steps'       => [
                ['name' => 'Present Passport and Printed E-Visa at Immigration', 'text' => 'Proceed to the All Passports queue with your passport (minimum 6 months validity), printed e-visa approval letter, and boarding pass. Verify that the officer entry stamp matches your approved visa validity date before leaving the booth.'],
                ['name' => 'Collect Checked Baggage and Clear Customs', 'text' => 'Verify your flight belt number on the arrivals monitor, retrieve checked luggage, and pass through the Green Channel if carrying standard personal allowances under 5,000 USD cash.'],
                ['name' => 'Withdraw Local VND Cash at Official Bank ATMs', 'text' => 'Exit into the public arrivals hall and withdraw 1,000,000 to 2,000,000 VND from official bank ATMs (Vietcombank, BIDV, Techcombank, or VPBank) rather than high-spread currency exchange kiosks.'],
                ['name' => 'Activate Viettel SIM/eSIM and Hail Verified Transport', 'text' => 'Turn on your Viettel eSIM or purchase a physical SIM at an official carrier kiosk, then book a Grab car at the designated pillar or board the official airport express bus (Bus 86 in Hanoi or Bus 109 in Ho Chi Minh City).'],
            ],
        ],
        'hanoi-airport-to-old-quarter' => [
            'name'        => 'How to Travel from Noi Bai Airport (HAN) to Hanoi Old Quarter',
            'description' => 'Step-by-step instructions for taking Express Bus 86 or a verified taxi from Noi Bai International Airport to Hoan Kiem District.',
            'steps'       => [
                ['name' => 'Exit Terminal 1 or Terminal 2 Arrivals Hall', 'text' => 'Walk straight out of the public arrivals sliding doors. For international flights (Terminal 2), turn left toward Pillar 12–14 for Grab pickup or Pillar 1–2 for the orange Express Bus 86 stop.'],
                ['name' => 'Board Express Bus 86 or Book Grab via Airport Wi-Fi', 'text' => 'For Bus 86, board directly and pay the conductor 45,000 VND cash per ticket (luggage racks and free Wi-Fi included). For Grab, verify the license plate in the app before loading your bags.'],
                ['name' => 'Cross Nhat Tan Bridge into Central Hanoi', 'text' => 'Follow the Vo Nguyen Giap Expressway across the Red River via Nhat Tan Bridge (30 km, 45 to 55 minutes). For Hoan Kiem and Old Quarter hotels, alight from Bus 86 at the Long Bien Transit Hub or Hanoi Opera House / Melia Hotel stops.'],
            ],
        ],
        'vietnam-first-trip-planning-checklist' => [
            'name'        => 'How to Plan a First Trip to Vietnam Step by Step',
            'description' => 'Chronological pre-departure workflow covering route pacing, e-visa submission, domestic flights, and arrival logistics.',
            'steps'       => [
                ['name' => 'Lock Regional Pacing Based on Month of Travel', 'text' => 'Match your travel dates to Vietnam three distinct climate zones (North, Central, South) and limit a 10-to-14-day itinerary to 3 or 4 regional hubs to avoid losing entire days in overland transit.'],
                ['name' => 'Submit Official Vietnam E-Visa 14–21 Days Before Departure', 'text' => 'Apply on evisa.xuatnhapcanh.gov.vn with a 6-month valid passport, selecting your exact entry airport or land checkpoint, and print two physical copies once approved.'],
                ['name' => 'Book Domestic Flights and Overnight Sleeper Trains Early', 'text' => 'Reserve trunk flights (Hanoi, Da Nang, Ho Chi Minh City) and Reunification Express sleeper berths (Hanoi to Lao Cai/Ninh Binh/Dong Hoi/Hue) 3 to 6 weeks ahead, especially near Tet or national holidays.'],
                ['name' => 'Install Offline Maps, Grab, and Multi-Currency Cards', 'text' => 'Download offline Google Maps for your target cities, install the Grab app linked to a zero-foreign-transaction-fee card, and prepare an unlocked phone for a Viettel eSIM.'],
            ],
        ],
    ];

    if (! isset($howto_registry[$slug])) {
        return null;
    }

    $data = $howto_registry[$slug];
    $steps = [];
    $pos = 1;
    foreach ($data['steps'] as $step) {
        $steps[] = [
            '@type'    => 'HowToStep',
            'position' => (string) $pos,
            'name'     => $step['name'],
            'text'     => $step['text'],
            'url'      => "{$current_url}#step-{$pos}",
        ];
        $pos++;
    }

    return [
        '@type'       => 'HowTo',
        '@id'         => "{$current_url}#howto",
        'name'        => $data['name'],
        'description' => $data['description'],
        'isPartOf'    => ['@id' => "{$current_url}#webpage"],
        'step'        => $steps,
    ];
}

/**
 * Authoritative WebApplication Schema.org generator for interactive travel toolkits.
 */
function vg_get_page_webapplication_schema(string $uri_path, string $current_url): ?array
{
    $slug = trim(basename($uri_path), '/');
    $webapp_registry = [
        'vietnam-travel-cost' => [
            'name'                => 'Vietnam Travel Cost Calculator',
            'description'         => 'Interactive real-time budget calculator for Vietnam travel with 5-currency conversion (USD, VND, EUR, GBP, AUD) across backpacker, flashpacker, mid-range, and luxury tiers for 10+ destinations.',
            'applicationCategory' => 'TravelApplication',
            'featureList'         => [
                '5-currency real-time exchange conversion (USD, VND, EUR, GBP, AUD)',
                'Itemized expense breakdown for accommodation, transport, meals, and activities',
                '4 traveler spending tiers (Backpacker, Flashpacker, Mid-Range, Luxury)',
                '1-click formatted clipboard summary export',
                'URL state persistence and local storage synchronization',
            ],
        ],
        'vietnam-evisa' => [
            'name'                => 'Vietnam Visa Requirement & Exemption Checker',
            'description'         => 'Interactive nationality-based visa requirements, 45-day unilateral exemption rules, and official 90-day e-visa validation engine for international travelers.',
            'applicationCategory' => 'TravelApplication',
            'featureList'         => [
                'Instant nationality lookup across 80+ countries',
                '45-day unilateral exemption vs 30-day bilateral exemption status indicator',
                '6-month passport validity expiration calculator',
                '33 official international entry port gate inspector',
                'Official government immigration portal link shield',
            ],
        ],
        'best-time-to-visit-vietnam' => [
            'name'                => 'Vietnam Weather & Regional Season Matrix',
            'description'         => 'Interactive month-by-month regional climate matrix across North, Central, and South Vietnam with monsoon and route-fit indicators.',
            'applicationCategory' => 'TravelApplication',
            'featureList'         => [
                '12-month climate navigator across Northern, Central, and Southern Vietnam',
                'Regional rainfall, temperature, and sea swimming conditions',
                'Mountain elevation temperature drop calculator',
                'Seasonal weather risk radar and route pivot alternatives',
            ],
        ],
        'vietnam-first-trip-planning-checklist' => [
            'name'                => 'Vietnam Route Packing & Preparation Checklist',
            'description'         => 'Interactive 24-item travel preparation checklist covering official documents, electronics, clothing, medical essentials, and motorbike gear with browser state persistence.',
            'applicationCategory' => 'TravelApplication',
            'featureList'         => [
                '24 curated logistics and packing items with contextual explanations',
                'Category filters for Documents, Electronics, Clothing, Medical, and Motorbike gear',
                'Progress counter with completion status badge',
                'Browser localStorage state persistence across sessions',
                'Reset and completion management controls',
            ],
        ],
        'vietnam-airport-arrival-checklist' => [
            'name'                => 'Vietnam Gateway Airport Navigator & Scam Shield',
            'description'         => 'Interactive terminal transit guide, verified Grab pickup bay locators, and metered taxi fare estimators for HAN, SGN, DAD, CXR, and PQC.',
            'applicationCategory' => 'TravelApplication',
            'featureList'         => [
                'Terminal-to-curb arrival navigation for 5 international airports (HAN, SGN, DAD, CXR, PQC)',
                'Verified Grab pickup island lanes and driver verification protocols',
                'Official metered taxi fleet brands and dispatch queue locations',
                'Zone-based transit cost and duration estimator to central city districts',
                'Common airport scam shields and arrival cash ATM guide',
            ],
        ],
        'itineraries' => [
            'name'                => 'Vietnam Interactive Itinerary Finder & Route Matcher',
            'description'         => 'Filterable route catalog matching traveler styles, duration (7d, 10d, 14d, 21d), and arrival gateways with zero wasted transit days.',
            'applicationCategory' => 'TravelApplication',
            'featureList'         => [
                'Duration filters for 7-day, 10-day, 14-day, and 21-day routes',
                'Travel pace and style filters (Classic, Slow Travel, Adventure, Beach)',
                'Gateway arrival filters for Hanoi and Ho Chi Minh City',
                'Step-by-step route highlights and seasonal booking recommendations',
            ],
        ],
    ];

    if (! isset($webapp_registry[$slug])) {
        return null;
    }

    $app = $webapp_registry[$slug];

    return [
        '@type'               => 'WebApplication',
        '@id'                 => "{$current_url}#webapp",
        'name'                => $app['name'],
        'description'         => $app['description'],
        'url'                 => $current_url,
        'applicationCategory' => $app['applicationCategory'],
        'operatingSystem'     => 'All',
        'browserRequirements' => 'Requires JavaScript. Supports all modern browsers.',
        'offers'              => [
            '@type'         => 'Offer',
            'price'         => '0',
            'priceCurrency' => 'USD',
            'category'      => 'Free Travel Planning Software',
        ],
        'featureList'         => $app['featureList'],
        'provider'            => [
            '@type' => 'Organization',
            'name'  => 'VietnamGuide.net',
            'url'   => function_exists('home_url') ? untrailingslashit(home_url()) . '/' : 'https://vietnamguide.net/',
        ],
        'isPartOf'            => [
            '@id' => "{$current_url}#webpage",
        ],
    ];
}

add_filter('rank_math/json_ld', 'vg_rich_travel_schema_filter', 100, 2);

/**
 * Normalize serialized Rank Math JSON-LD <script> blocks (or raw JSON strings) after Rank Math's
 * internal Helper::sanitize_schema_values() runs at rank_math/head priority 90, restoring unescaped
 * '&' entities and native JSON booleans, integers, and floats.
 */
function vg_normalize_serialized_json_ld_html(string $html): string
{
    $trimmed = trim($html);
    if ($trimmed === '') {
        return $html;
    }

    if (str_contains($html, 'application/ld+json')) {
        $normalized = preg_replace_callback(
            '/(<script\b[^>]*type=["\']application\/ld\+json["\'][^>]*>)(.*?)(<\/script>)/is',
            static function (array $m): string {
                $rawJson = trim($m[2]);
                if ($rawJson === '') {
                    return $m[0];
                }
                $decoded = json_decode($rawJson, true);
                if (! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                    return $m[0];
                }
                $cleaned = vg_schema_clean_data($decoded);
                $encoded = function_exists('wp_json_encode')
                    ? wp_json_encode($cleaned, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : json_encode($cleaned, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if (! is_string($encoded) || $encoded === '') {
                    return $m[0];
                }
                return $m[1] . $encoded . $m[3];
            },
            $html
        );
        return is_string($normalized) ? $normalized : $html;
    }

    if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
        $decoded = json_decode($trimmed, true);
        if (is_array($decoded) && json_last_error() === JSON_ERROR_NONE) {
            $cleaned = vg_schema_clean_data($decoded);
            $encoded = function_exists('wp_json_encode')
                ? wp_json_encode($cleaned, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : json_encode($cleaned, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (is_string($encoded) && $encoded !== '') {
                return $encoded;
            }
        }
    }

    return $html;
}

$GLOBALS['vg_rm_json_ld_ob_level'] = null;

add_action('rank_math/head', static function (): void {
    ob_start();
    $GLOBALS['vg_rm_json_ld_ob_level'] = ob_get_level();
}, 89);

add_action('rank_math/head', static function (): void {
    $expectedLevel = $GLOBALS['vg_rm_json_ld_ob_level'] ?? null;
    if ($expectedLevel !== null && ob_get_level() === $expectedLevel) {
        $GLOBALS['vg_rm_json_ld_ob_level'] = null;
        $buffered = ob_get_clean();
        if (is_string($buffered) && $buffered !== '') {
            echo vg_normalize_serialized_json_ld_html($buffered);
        }
    }
}, 91);

add_filter('rank_math/schema/json_ld/output', static function ($output) {
    if (! is_string($output)) {
        return $output;
    }
    return vg_normalize_serialized_json_ld_html($output);
}, 99);

/**
 * Decode raw HTML entities (&amp; -> &) in document title across all pages.
 */
add_filter('document_title_parts', static function ($parts) {
    if (is_array($parts)) {
        foreach ($parts as $key => $val) {
            if (is_string($val)) {
                $parts[$key] = html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
    }
    return $parts;
}, 99);

add_filter('rank_math/frontend/title', static function ($title) {
    return is_string($title) ? html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $title;
}, 99);

/**
 * Enrich thin meta descriptions on legal and utility pages for optimal SERP snippets.
 */
add_filter('rank_math/frontend/description', static function ($desc) {
    if (! is_string($desc) || trim($desc) === '' || trim($desc) === 'Terms of Service' || strlen(trim($desc)) < 30) {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = strtolower(trim((string) parse_url($uri, PHP_URL_PATH), '/'));
        if ($path === 'terms-of-service') {
            return 'Official terms of service, editorial policies, and user agreements for VietnamGuide.net independent travel advisories, route guides, and planning tools.';
        }
    }
    return $desc;
}, 99);

/**
 * ==========================================================================
 * Stage 21: Semantic Tabular Data & Table Caption Optimization
 * ==========================================================================
 * Automatically enhances decision tables with descriptive, query-focused
 * <caption> elements, guarantees semantic <thead> with <th scope="col">, and
 * formats row headers for Google Featured Snippet table extraction.
 */

function vg_humanize_table_class(string $classAttr): string
{
    static $curated = [
        'vg-evisa-official-basics'                  => 'Vietnam E-Visa Official Requirements, Fees & Validity Rules',
        'vg-evisa-decision-table'                   => 'Vietnam Visa Type Decision Matrix by Travel Purpose & Route',
        'vg-evisa-mistake-checks'                   => 'Common E-Visa Application Pitfalls and Mitigation Steps',
        'vg-sim-esim-basics-table'                  => 'Vietnam Mobile Connectivity Options: eSIM vs Physical SIM',
        'vg-sim-packages-tariffs'                   => 'Official Vietnam 4G Data Packages, Carrier Tariffs & Registration',
        'vg-sim-esim-decision-table'                => 'SIM vs eSIM Selection Matrix by Traveler Profile & Device',
        'vg-trang-an-tam-coc-glance'                => 'Trang An vs Tam Coc Boating Verdict at a Glance',
        'vg-trang-an-tam-coc-decision-matrix'       => 'Trang An vs Tam Coc Landscape, Safety & Circuit Comparison',
        'vg-trang-an-tam-coc-route-comparison'      => 'Trang An Cave Routes vs Tam Coc River Experience',
        'vg-ha-long-lan-ha-glance-table'            => 'Ha Long Bay vs Lan Ha Bay Cruise Verdict at a Glance',
        'vg-halong-lanha-pricing'                   => 'Harbor Levies, Entrance Fees and Cruise Transit Costs',
        'vg-ha-long-lan-ha-decision-matrix'         => 'Ha Long Bay vs Lan Ha Bay Scenery, Crowd Density & Logistics',
        'vg-ninh-binh-ha-long-transfer-glance'      => 'Ninh Binh to Ha Long Bay Direct Transfer at a Glance',
        'vg-ninh-binh-ha-long-transfer-mode-matrix' => 'Limousine Shuttle vs Private Car vs Rail Comparison',
        'vg-cost-budget-ranges'                     => 'Vietnam Daily Travel Budget Ranges by Traveler Style',
        'vg-cost-scenario-budgets'                  => 'Estimated Vietnam Land-Only Travel Expenses by Trip Length',
        'vg-da-nang-hoi-an-glance-table'            => 'Da Nang vs Hoi An Destination Comparison at a Glance',
        'vg-da-nang-hoi-an-matrix'                  => 'Da Nang vs Hoi An Beach, Heritage & Dining Comparison',
        'vg-sapa-transport-modes'                   => 'Hanoi to Sapa Transport Modes: Speed, Cost & Comfort Comparison',
        'vg-ha-giang-easy-rider-matrix'             => 'Ha Giang Loop Tour Modes: Easy Rider vs Self-Drive vs Private Car',
    ];

    $classes = preg_split('/\s+/', trim($classAttr));
    foreach ($classes as $c) {
        if (isset($curated[$c])) {
            return $curated[$c];
        }
    }

    $specific = '';
    foreach ($classes as $c) {
        if ($c !== 'vg-decision-table' && $c !== 'vg-comparison-matrix' && str_starts_with($c, 'vg-')) {
            $specific = $c;
            break;
        }
    }

    if ($specific === '') {
        return 'Vietnam Travel Planning & Decision Matrix';
    }

    $raw = str_replace(['vg-', '-table'], '', $specific);
    $words = explode('-', $raw);
    $capitalized = [];
    $ignore = ['matrix', 'comparison'];
    foreach ($words as $w) {
        if (! in_array(strtolower($w), $ignore, true)) {
            $capitalized[] = ucfirst(strtolower($w));
        }
    }

    $title = implode(' ', $capitalized);
    if (! str_ends_with(strtolower($title), 'matrix') && ! str_ends_with(strtolower($title), 'guide') && ! str_ends_with(strtolower($title), 'breakdown')) {
        $title .= ' Comparison Matrix';
    }

    return $title;
}

function vg_enhance_table_markup(string $tableHtml): string
{
    if (! preg_match('/^<table\b([^>]*)>(.*)<\/table>$/is', trim($tableHtml), $m)) {
        return $tableHtml;
    }

    $attrs = $m[1];
    $inner = $m[2];

    // 1. In existing thead, ensure all <th> have scope="col"
    $inner = preg_replace_callback('/<thead\b([^>]*)>(.*?)<\/thead>/is', static function ($theadMatches) {
        $theadAttrs = $theadMatches[1];
        $theadContent = $theadMatches[2];
        $theadContent = preg_replace_callback('/<th\b([^>]*)>/i', static function ($thMatches) {
            $thAttrs = $thMatches[1];
            if (stripos($thAttrs, 'scope=') === false) {
                return '<th scope="col"' . $thAttrs . '>';
            }
            return $thMatches[0];
        }, $theadContent);
        return '<thead' . $theadAttrs . '>' . $theadContent . '</thead>';
    }, $inner);

    // 2. If table lacks <thead>, inspect the first <tr>
    if (stripos($inner, '<thead') === false) {
        if (preg_match('/<tr\b[^>]*>(.*?)<\/tr>/is', $inner, $firstTrMatches)) {
            $trContent = $firstTrMatches[1];
            // Check if it is a 2-column key-value glance table (e.g. data-label="Question")
            if (stripos($trContent, 'data-label="Question"') !== false) {
                $theadBlock = "\n<thead>\n<tr><th scope=\"col\">Decision Dimension</th><th scope=\"col\">Authoritative Guidance &amp; Verdict</th></tr>\n</thead>";
                $inner = $theadBlock . "\n" . $inner;
            } elseif (stripos($trContent, '<th') !== false) {
                // If first row already uses <th>, wrap it in <thead>
                $inner = preg_replace('/(<tr\b[^>]*>.*?<\/tr>)/is', "<thead>\n$1\n</thead>", $inner, 1);
                $inner = preg_replace_callback('/<thead>\s*<tr\b([^>]*)>(.*?)<\/tr>\s*<\/thead>/is', static function ($m) {
                    $trAttrs = $m[1];
                    $thRow = preg_replace_callback('/<th\b([^>]*)>/i', static function ($thMatches) {
                        $thAttrs = $thMatches[1];
                        if (stripos($thAttrs, 'scope=') === false) {
                            return '<th scope="col"' . $thAttrs . '>';
                        }
                        return $thMatches[0];
                    }, $m[2]);
                    return "<thead>\n<tr{$trAttrs}>{$thRow}</tr>\n</thead>";
                }, $inner);
            }
        }
    }

    // 3. Ensure <caption> exists
    if (stripos($inner, '<caption') === false) {
        $classAttr = '';
        if (preg_match('/class=[\'"]([^\'"]+)[\'"]/i', $attrs, $classMatch)) {
            $classAttr = $classMatch[1];
        }
        $captionText = vg_humanize_table_class($classAttr);
        $captionTag = "\n<caption class=\"vg-table-caption\">" . htmlspecialchars($captionText, ENT_QUOTES, 'UTF-8') . "</caption>";
        $inner = $captionTag . "\n" . ltrim($inner);
    }

    // 4. In <tbody><tr> (or body <tr> outside <thead>), convert leading <td> to <th scope="row">
    $transformFirstCell = static function (string $trHtml): string {
        return preg_replace_callback('/(<tr\b[^>]*>\s*)(<td\b([^>]*)>(.*?)<\/td>|<th\b([^>]*)>(.*?)<\/th>)/is', static function ($m) {
            $trOpen = $m[1];
            if (stripos($m[2], '<td') === 0) {
                $attrs = $m[3] ?? '';
                $content = $m[4] ?? '';
                if (stripos($attrs, 'scope=') === false) {
                    return $trOpen . '<th scope="row"' . $attrs . '>' . $content . '</th>';
                }
                return $trOpen . '<th' . $attrs . '>' . $content . '</th>';
            }
            $thAttrs = $m[5] ?? '';
            $thContent = $m[6] ?? '';
            if (stripos($thAttrs, 'scope=') === false) {
                return $trOpen . '<th scope="row"' . $thAttrs . '>' . $thContent . '</th>';
            }
            return $trOpen . $m[2];
        }, $trHtml);
    };

    if (stripos($inner, '<tbody') !== false) {
        $inner = preg_replace_callback('/<tbody\b([^>]*)>(.*?)<\/tbody>/is', static function ($tbMatches) use ($transformFirstCell) {
            $tbAttrs = $tbMatches[1];
            $tbContent = $tbMatches[2];
            $tbContent = preg_replace_callback('/<tr\b[^>]*>.*?<\/tr>/is', static function ($trMatches) use ($transformFirstCell) {
                return $transformFirstCell($trMatches[0]);
            }, $tbContent);
            return '<tbody' . $tbAttrs . '>' . $tbContent . '</tbody>';
        }, $inner);
    } else {
        if (preg_match('/^(.*<\/thead>)(.*)$/is', $inner, $split)) {
            $head = $split[1];
            $body = preg_replace_callback('/<tr\b[^>]*>.*?<\/tr>/is', static function ($trMatches) use ($transformFirstCell) {
                return $transformFirstCell($trMatches[0]);
            }, $split[2]);
            $inner = $head . $body;
        }
    }

    return '<table' . $attrs . '>' . $inner . '</table>';
}

function vg_enhance_content_tables(string $content): string
{
    if (stripos($content, '<table') === false) {
        return $content;
    }

    return preg_replace_callback('/<table\b[^>]*>.*?<\/table>/is', static function ($matches) {
        return vg_enhance_table_markup($matches[0]);
    }, $content);
}

add_filter('the_content', 'vg_enhance_content_tables', 20);

/**
 * Stage 43: Zero-CLS Image Dimension Hardening & Lazy Loading
 *
 * Ensures all content images possess explicit natural width & height
 * attributes matching their actual dimensions to eliminate layout shifts (CLS),
 * while maintaining eager/high-priority loading on hero covers and lazy loading on body images.
 */
function vg_enhance_content_images(string $content): string
{
    if (stripos($content, '<img') === false) {
        return $content;
    }

    $dimensions = function_exists('vg_get_known_image_dimensions') ? vg_get_known_image_dimensions() : [];
    $isEntireHero = (stripos($content, 'vg-guide-hero') !== false && stripos($content, '<h1') !== false);
    $processor = new WP_HTML_Tag_Processor($content);
    $inHero = false;
    $heroDepth = 0;

    while ($processor->next_tag()) {
        $tag = $processor->get_tag();

        if (! $processor->is_tag_closer()) {
            if ($inHero) {
                $heroDepth++;
            } elseif (
                $processor->has_class('vg-guide-hero')
                || $processor->has_class('vg-guide-hero-cover')
                || $processor->get_attribute('data-vg-hero') !== null
            ) {
                $inHero = true;
                $heroDepth = 1;
            }
        } else {
            if ($inHero) {
                $heroDepth--;
                if ($heroDepth <= 0) {
                    $inHero = false;
                    $heroDepth = 0;
                }
            }
        }

        if ($tag !== 'IMG') {
            continue;
        }

        $src = $processor->get_attribute('src');
        if (! is_string($src) || trim($src) === '') {
            continue;
        }
        $src = trim($src);

        // 1. Resolve dimensions if width or height is missing
        $hasWidth = $processor->get_attribute('width') !== null;
        $hasHeight = $processor->get_attribute('height') !== null;

        if (! $hasWidth || ! $hasHeight) {
            $w = null;
            $h = null;

            // Direct match
            if (isset($dimensions[$src])) {
                [$w, $h] = $dimensions[$src];
            } else {
                $decodedSrc = rawurldecode($src);
                if (isset($dimensions[$decodedSrc])) {
                    [$w, $h] = $dimensions[$decodedSrc];
                } else {
                    $path = parse_url($src, PHP_URL_PATH);
                    $basename = $path !== null && $path !== '' ? basename($path) : '';
                    $decodedBasename = rawurldecode($basename);

                    if ($basename !== '' && isset($dimensions[$basename])) {
                        [$w, $h] = $dimensions[$basename];
                    } elseif ($decodedBasename !== '' && isset($dimensions[$decodedBasename])) {
                        [$w, $h] = $dimensions[$decodedBasename];
                    } elseif (preg_match('/(?:^|\/)(\d+)px-/i', $basename, $m)) {
                        // Fallback heuristic for unindexed Wikimedia thumbnails
                        $w = (int) $m[1];
                        $h = (int) round($w * 2 / 3);
                    }
                }
            }

            if ($w !== null && $h !== null && $w > 0 && $h > 0) {
                if (! $hasWidth) {
                    $processor->set_attribute('width', (string) $w);
                }
                if (! $hasHeight) {
                    $processor->set_attribute('height', (string) $h);
                }
            }
        }

        // 2. Ensure loading attribute (eager for hero to prevent LCP delay, lazy for body)
        $isHeroImg = $inHero
            || $processor->get_attribute('data-vg-hero') !== null
            || $processor->has_class('vg-guide-hero')
            || $processor->has_class('vg-guide-hero-cover')
            || $processor->has_class('wp-block-cover__image-background')
            || ($isEntireHero && $heroDepth === 0);

        if ($isHeroImg) {
            $processor->set_attribute('loading', 'eager');
            $processor->set_attribute('fetchpriority', 'high');
        } else {
            $loading = $processor->get_attribute('loading');
            if ($loading === null) {
                $processor->set_attribute('loading', 'lazy');
            }
        }

        // 3. Ensure decoding="async"
        $decoding = $processor->get_attribute('decoding');
        if ($decoding === null) {
            $processor->set_attribute('decoding', 'async');
        }

        // 4. Ensure descriptive fallback alt text when missing or empty
        $alt = $processor->get_attribute('alt');
        if ($alt === null || trim($alt) === '') {
            $path = parse_url($src, PHP_URL_PATH);
            $basename = $path !== null && $path !== '' ? pathinfo($path, PATHINFO_FILENAME) : '';
            $cleanAlt = trim(preg_replace('/[_-]+/', ' ', preg_replace('/^\d+px-/', '', $basename)));
            $generatedAlt = $cleanAlt !== ''
                ? ucwords($cleanAlt) . ' - Vietnam Travel Field Guide'
                : 'Vietnam Travel Logistics & Advisory Overview';
            $processor->set_attribute('alt', esc_attr($generatedAlt));
        }
    }

    return $processor->get_updated_html();
}

add_filter('the_content', 'vg_enhance_content_images', 21);

/**
 * Include pages in the main RSS feed so search engines, feed aggregators,
 * and discovery bots syndicate all Vietnam travel field guides and toolkits.
 */
add_filter('request', static function (array $query_vars): array {
    if (isset($query_vars['feed']) && ! isset($query_vars['post_type'])) {
        $query_vars['post_type'] = ['page', 'post'];
    }
    return $query_vars;
});

/**
 * Serve Webmaster verification files (BingSiteAuth.xml and IndexNow key).
 */
function vg_handle_webmaster_verification_endpoints(): void
{
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $path = trim((string) parse_url($requestUri, PHP_URL_PATH), '/');

    // 1. Bing Webmaster XML verification file (/BingSiteAuth.xml)
    if (strtolower($path) === 'bingsiteauth.xml') {
        $bingId = defined('VG_BING_VERIFICATION_ID')
            ? (string) VG_BING_VERIFICATION_ID
            : (string) get_option('vg_bing_verification_id', '852EF594B29D4DA5A639612DA3430B0F');
        header('Content-Type: application/xml; charset=utf-8');
        header('X-Robots-Tag: noindex');
        echo "<?xml version=\"1.0\"?>\n<users>\n    <user>" . esc_html(strtoupper($bingId)) . "</user>\n</users>\n";
        exit;
    }

    // 2. IndexNow Key plain-text verification file (/{key}.txt)
    $indexNowKey = defined('VG_INDEXNOW_KEY')
        ? (string) VG_INDEXNOW_KEY
        : (string) get_option('vg_indexnow_key', '852ef594b29d4da5a639612da3430b0f');
    if ($indexNowKey !== '' && strtolower($path) === strtolower($indexNowKey . '.txt')) {
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Robots-Tag: noindex');
        echo esc_html($indexNowKey) . "\n";
        exit;
    }
}
add_action('init', 'vg_handle_webmaster_verification_endpoints', 1);

/**
 * Dispatch IndexNow API notifications for updated URLs.
 *
 * @param string[] $urls
 * @return bool True if dispatched.
 */
function vg_dispatch_indexnow(array $urls): bool
{
    if (empty($urls)) {
        return false;
    }

    $host = 'vietnamguide.net';
    $key = defined('VG_INDEXNOW_KEY')
        ? (string) VG_INDEXNOW_KEY
        : (string) get_option('vg_indexnow_key', '852ef594b29d4da5a639612da3430b0f');
    $keyLocation = "https://{$host}/{$key}.txt";

    $payload = [
        'host' => $host,
        'key' => $key,
        'keyLocation' => $keyLocation,
        'urlList' => array_values(array_unique($urls)),
    ];

    $endpoints = [
        'https://api.indexnow.org/indexnow',
        'https://www.bing.com/indexnow',
    ];

    $args = [
        'method'      => 'POST',
        'timeout'     => 5,
        'redirection' => 2,
        'httpversion' => '1.1',
        'blocking'    => false, // Non-blocking: zero latency for editorial UI
        'headers'     => [
            'Content-Type' => 'application/json; charset=utf-8',
            'User-Agent'   => 'VietnamGuide-Pinger/1.0',
        ],
        'body'        => wp_json_encode($payload),
        'data_format' => 'body',
    ];

    foreach ($endpoints as $endpoint) {
        wp_remote_post($endpoint, $args);
    }

    return true;
}

/**
 * Dispatch real-time WebSub / PubSubHubbub publish pings to Google & RSS aggregators.
 */
function vg_dispatch_websub_pings(string $feedUrl = ''): void
{
    if ($feedUrl === '') {
        $feedUrl = home_url('/feed/');
    }

    $hubs = [
        'https://pubsubhubbub.appspot.com/',
        'https://superfeedr.com/hubbub',
    ];

    $args = [
        'method'   => 'POST',
        'timeout'  => 5,
        'blocking' => false,
        'headers'  => [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'User-Agent'   => 'VietnamGuide-Pinger/1.0',
        ],
        'body'     => [
            'hub.mode' => 'publish',
            'hub.url'  => $feedUrl,
        ],
    ];

    foreach ($hubs as $hub) {
        wp_remote_post($hub, $args);
    }
}

/**
 * Invalidate LiteSpeed cache tags for sitemaps and feed so crawlers fetch fresh data immediately.
 */
function vg_purge_search_discovery_caches(string $postUrl): void
{
    if (function_exists('do_action')) {
        do_action('litespeed_purge_url', $postUrl);
        do_action('litespeed_purge_url', home_url('/feed/'));
        do_action('litespeed_purge_url', home_url('/sitemap_index.xml'));
        do_action('litespeed_purge_url', home_url('/page-sitemap.xml'));
    }
}

/**
 * Hook into post status transitions to automatically broadcast publishing signals to search engines.
 *
 * @param string  $new_status
 * @param string  $old_status
 * @param WP_Post $post
 */
function vg_handle_post_publish_discovery(string $new_status, string $old_status, WP_Post $post): void
{
    // 1. Only trigger when post is currently published
    if ($new_status !== 'publish') {
        return;
    }

    // 2. Only process public post types ('page' or 'post')
    if (! in_array($post->post_type, ['page', 'post'], true)) {
        return;
    }

    // 3. Ignore autosaves, revisions, or password-protected posts
    if (wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID) || ! empty($post->post_password)) {
        return;
    }

    // 4. Debounce protection: prevent duplicate pings within 120 seconds for the same post ID
    $lockKey = 'vg_ping_lock_' . $post->ID;
    if (get_transient($lockKey) !== false) {
        return;
    }
    set_transient($lockKey, time(), 120);

    $permalink = get_permalink($post->ID);
    if (! is_string($permalink) || $permalink === '') {
        return;
    }

    // 5. Invalidate sitemap & feed cache
    vg_purge_search_discovery_caches($permalink);

    // 6. Broadcast to IndexNow (Bing, Yandex, Naver, Seznam)
    vg_dispatch_indexnow([$permalink]);

    // 7. Broadcast to WebSub / PubSubHubbub (Google & RSS feed crawlers)
    vg_dispatch_websub_pings();

    // 8. Record telemetry in WordPress option for monitoring
    update_option('vg_last_search_engine_ping', [
        'timestamp' => current_time('mysql', true),
        'post_id'   => $post->ID,
        'post_type' => $post->post_type,
        'url'       => $permalink,
        'event'     => ($old_status === 'publish') ? 'post_updated' : 'post_published',
    ], false);
}
add_action('transition_post_status', 'vg_handle_post_publish_discovery', 10, 3);


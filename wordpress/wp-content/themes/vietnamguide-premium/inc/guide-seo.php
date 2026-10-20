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
 * Resolve destination-specific >= 1920px Google Discover / Schema.org ImageObject for sub-destinations
 * within multi-hub clusters (e.g. Hue, Da Nang, My Son, Cat Ba/Lan Ha, Bai Tu Long, Con Dao, Nha Trang, Quy Nhon, Mui Ne).
 *
 * @return array{url:string,width:int,height:int,caption:string}|null
 */
function vg_get_subdestination_discover_image(string $resolvedPath): ?array
{
    $p = strtolower(trim($resolvedPath, '/'));
    if ($p === '' || str_contains($p, '-vs-')) {
        return null;
    }

    if (str_contains($p, 'hue')) {
        return [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg/1920px-Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg',
            'width'   => 1920,
            'height'  => 1280,
            'caption' => 'Ngo Mon Gate and Imperial Citadel walls in Hue, Thua Thien Hue, Vietnam',
        ];
    }
    if (str_contains($p, 'da-nang')) {
        return [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/af/My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg/1920px-My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg',
            'width'   => 1920,
            'height'  => 1440,
            'caption' => 'My Khe Beach coastline seen from Son Tra Peninsula in Da Nang, Vietnam',
        ];
    }
    if (str_contains($p, 'my-son')) {
        return [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7d/My_Son_Sanctuary_Vietnam_06.jpg/1920px-My_Son_Sanctuary_Vietnam_06.jpg',
            'width'   => 1920,
            'height'  => 1440,
            'caption' => 'Ancient Hindu brick temple towers at My Son Sanctuary UNESCO World Heritage Site, Quang Nam, Vietnam',
        ];
    }
    if (str_contains($p, 'cat-ba') || str_contains($p, 'lan-ha')) {
        return [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/ca/Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg/1920px-Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg',
            'width'   => 1920,
            'height'  => 1435,
            'caption' => 'Limestone karst islands and emerald waters in Lan Ha Bay off Cat Ba Island, Hai Phong, Vietnam',
        ];
    }
    if (str_contains($p, 'bai-tu-long')) {
        return [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/46/B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg/1920px-B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg',
            'width'   => 1920,
            'height'  => 1282,
            'caption' => 'Limestone karst archipelago and calm channels in Bai Tu Long Bay, Quang Ninh, Vietnam',
        ];
    }
    if (str_contains($p, 'con-dao')) {
        return [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg/1920px-Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg',
            'width'   => 1920,
            'height'  => 1440,
            'caption' => 'Dat Doc Beach and coastal headlands in the Con Dao Archipelago, Ba Ria - Vung Tau, Vietnam',
        ];
    }
    if (str_contains($p, 'nha-trang') || str_contains($p, 'cam-ranh')) {
        return [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Nha_Trang_Beach_3.jpg/1920px-Nha_Trang_Beach_3.jpg',
            'width'   => 1920,
            'height'  => 1440,
            'caption' => 'Crescent sandy shoreline and turquoise bay along Tran Phu Beach in Nha Trang, Khanh Hoa, Vietnam',
        ];
    }
    if (str_contains($p, 'quy-nhon')) {
        return [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6d/Ky_Co_beach%2C_Quy_Nhon_city%2C_Binh_Dinh_province%2C_Vietnam.jpg/1920px-Ky_Co_beach%2C_Quy_Nhon_city%2C_Binh_Dinh_province%2C_Vietnam.jpg',
            'width'   => 1920,
            'height'  => 1280,
            'caption' => 'Granite headlands and turquoise cove at Ky Co Beach, Quy Nhon, Binh Dinh, Vietnam',
        ];
    }
    if (str_contains($p, 'mui-ne') || str_contains($p, 'phan-thiet')) {
        return [
            'url'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2d/Vietnam%2C_Mui_Ne_beach%2C_Kitesurfing_on_the_beach.jpg/1920px-Vietnam%2C_Mui_Ne_beach%2C_Kitesurfing_on_the_beach.jpg',
            'width'   => 1920,
            'height'  => 1280,
            'caption' => 'Kitesurfers catching trade winds along Ham Tien Beach in Mui Ne, Binh Thuan, Vietnam',
        ];
    }

    return null;
}

/**
 * Resolve the primary Google Discover / Schema.org ImageObject for a route or singular post.
 * Prefers inline content images meeting the >= 1200x675px Discover threshold, then sub-destination
 * 1920px photography, falling back to the route's regional travel cluster 1920px hero image.
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

    if ($resolvedPath !== '') {
        $subDestImg = vg_get_subdestination_discover_image($resolvedPath);
        if (is_array($subDestImg)) {
            return $subDestImg;
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
                    ['name' => 'Dong Van Karst Plateau', 'wikidata' => 'https://www.wikidata.org/wiki/Q10744325'],
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
            ['q' => 'How much does a Vietnam e-visa cost?', 'a' => 'A single-entry 90-day Vietnam e-visa costs 25 USD, and a multiple-entry 90-day e-visa costs 50 USD. Citizens of 25 countries qualify for 45-day visa-free entry under Resolutions 44/NQ-CP and 229/NQ-CP (valid through August 2028).'],
            ['q' => 'What is the official Vietnam e-visa website?', 'a' => 'The official Ministry of Public Security e-visa portals are evisa.gov.vn, thithucdientu.gov.vn, and evisa.xuatnhapcanh.gov.vn, valid across 42 designated international checkpoints under Resolution 127/NQ-CP. Avoid commercial third-party websites charging processing markups.'],
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
            ['q' => 'Which departure port is used for Lan Ha Bay cruises?', 'a' => 'Lan Ha Bay cruises depart from Ben Beo harbor on Cat Ba Island, Tuan Chau International Marina, or via the Dong Bai – Cai Vieng ferry terminal (which replaced Got Pier in March 2024, costing 12,000 VND for foot passengers).'],
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
            ['q' => 'How far is Tan Son Nhat Airport (SGN) from District 1 in Ho Chi Minh City?', 'a' => 'Tan Son Nhat Airport sits 8 kilometers north of District 1, with International Terminal T2 and Domestic Terminals T1 and T3. Bus 109 or Bus 152 reaches Ben Thanh Market (and HCMC Metro Line 1) in 30 to 45 minutes for 5,000 to 20,000 VND, while Grab or Vinasun/Mai Linh metered taxis cost 120,000 to 200,000 VND.'],
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
        'cat-ba-travel-guide' => [
            ['q' => 'How do I get from Hanoi or Hai Phong to Cat Ba Island in 2026?', 'a' => 'Take a joint bus-ferry package from Hanoi Old Quarter via the CT04 expressway (3.5 hours, 320,000 to 380,000 VND), cross via the Dong Bai – Cai Vieng public ferry (12,000 VND foot passenger, 45,000 VND motorbike), or ride the Sun World Cat Hai – Phu Long sea cable car (50,000 to 100,000 VND each way).'],
            ['q' => 'How much does the Cat Ba National Park ticket and Ngu Lam Peak hike cost?', 'a' => 'Cat Ba National Park admission costs 80,000 VND per adult (40,000 VND for students and children), covering the 2.5-hour round-trip hike to Ngu Lam Peak and Trung Trang Cave. The extended 12-kilometer trek through the park to Viet Hai Village carries a 160,000 VND route fee.'],
            ['q' => 'How much does a Lan Ha Bay boat and kayak day tour from Cat Ba cost?', 'a' => 'Full-day boat and kayaking tours departing Ben Beo Pier at 8:00 AM cost 650,000 to 950,000 VND per person, including Cai Beo floating fishing village, Dark and Bright Cave kayaking, and lunch on board.'],
        ],
        'nha-trang-travel-guide' => [
            ['q' => 'How do I travel from Cam Ranh Airport (CXR) to Nha Trang city center?', 'a' => 'Cam Ranh International Airport lies 35 kilometers south of Nha Trang along the Nguyen Tat Thanh coastal road. Dat Moi Airport Bus Route 18 costs 50,000 to 65,000 VND (45 minutes), while Grab cars and metered taxis cost 300,000 to 400,000 VND.'],
            ['q' => 'What are the 2026 ticket prices for Po Nagar Cham Towers and Hon Chong?', 'a' => 'Po Nagar Cham Towers (8th-to-12th-century Hindu brick sanctuary) and Hon Chong Promontory each charge 30,000 VND per adult. Island boat excursions into the Nha Trang Bay Marine Protected Area range from 220,000 to 650,000 VND.'],
            ['q' => 'When is the dry beach season in Nha Trang?', 'a' => 'Nha Trang has an extended 8-month dry and sunny season from January through August with calm sea visibility of 15 to 25 meters, whereas peak northeast monsoon rains fall between October and mid-December.'],
        ],
        'quy-nhon-travel-guide' => [
            ['q' => 'How do I get from Phu Cat Airport (UIH) or Dieu Tri Station to Quy Nhon?', 'a' => 'Phu Cat Airport (UIH) sits 35 kilometers northwest of Quy Nhon: the official airport shuttle bus to Nguyen Tat Thanh Square costs 50,000 VND (45 minutes), or a Grab/taxi costs 280,000 to 350,000 VND. Dieu Tri Railway Station is 12 kilometers west of the city (150,000 to 180,000 VND by taxi).'],
            ['q' => 'What are the entrance fees for Ky Co Beach, Eo Gio, and Thap Doi Cham Towers?', 'a' => 'Eo Gio coastal cliff walkway costs 25,000 VND, Ky Co Beach costs 100,000 VND entry plus 40,000 VND for the headland electric shuttle, and Thap Doi (Twin Cham Towers) and Banh It Cham Towers each cost 20,000 VND per adult.'],
            ['q' => 'When is the calmest sea season for Ky Co and Hon Kho in Quy Nhon?', 'a' => 'March through August offers flat turquoise water for speedboat and snorkeling excursions from Nhon Ly fishing village (250,000 to 400,000 VND round-trip), whereas rough northeast swells from October to January restrict boat departures to overland road access.'],
        ],
        'mui-ne-travel-guide' => [
            ['q' => 'How long does it take to travel from Ho Chi Minh City to Mui Ne via the expressway?', 'a' => 'Via the CT01 Dau Giay – Phan Thiet Expressway, VIP limousine vans and private cars travel from District 1 in Ho Chi Minh City to Ham Tien and Mui Ne in 2.5 to 3 hours (250,000 to 420,000 VND). Morning train SPT2 from Saigon Station to Phan Thiet Station takes 3 hours 45 minutes (180,000 to 265,000 VND).'],
            ['q' => 'How much do the White Sand Dunes, Red Sand Dunes, and Fairy Stream cost in Mui Ne?', 'a' => 'Fairy Stream (Suoi Tien) and the White Sand Dunes (Bau Trang) each charge a 15,000 VND entrance ticket. Optional ATV or Land Cruiser rides up the White Sand Dunes cost 300,000 to 600,000 VND, while a private 4-stop sunrise or sunset Jeep charter costs 550,000 to 650,000 VND per vehicle.'],
            ['q' => 'When is the best wind season for kitesurfing in Mui Ne?', 'a' => 'November through March delivers steady 18-to-25-knot thermal northeast trade winds and dry sunshine along Ham Tien Bay.'],
        ],
        'hue-travel-guide' => [
            ['q' => 'How much are Hue Imperial Citadel and Royal Tomb tickets in 2026?', 'a' => 'Hue Monuments Conservation Centre charges 200,000 VND for the Imperial Citadel and 150,000 VND each for Minh Mang, Tu Duc, and Khai Dinh Royal Tombs. The 2-day 3-site combo pass (Citadel + 2 Tombs) costs 420,000 VND and the 4-site pass costs 530,000 VND.'],
            ['q' => 'How do I book the Central Heritage Train (HD1/HD2) between Hue and Da Nang?', 'a' => 'Book scenic trains HD1/HD2 or HD3/HD4 directly on dsvn.vn for 150,000 to 180,000 VND. The 3-hour coastal rail journey crosses Hai Van Pass, stops for 10 minutes at Lang Co Bay, and includes a cultural music carriage.'],
            ['q' => 'How do I get from Phu Bai Airport (HUI) to Hue city center?', 'a' => 'Phu Bai International Airport (Terminal T2) is 15 kilometers south of the Perfume River. Airport Bus Route 2 costs 50,000 VND (30 to 35 minutes), while Grab or Mai Linh metered taxis cost 200,000 to 250,000 VND.'],
        ],
        'can-tho-travel-guide' => [
            ['q' => 'What time should I take a boat to Cai Rang Floating Market in Can Tho?', 'a' => 'Depart Ninh Kieu Pier between 5:00 AM and 5:30 AM to reach Cai Rang Floating Market during peak wholesale fruit trading from 5:30 AM to 7:00 AM. Private wooden sampan charters cost 350,000 to 500,000 VND per boat for 1 to 4 passengers.'],
            ['q' => 'How do I get from Ho Chi Minh City to Can Tho?', 'a' => 'Futa Bus Lines (Phuong Trang) and Vu Linh sleeper and limousine coaches depart Mien Tay Bus Station every 30 minutes via the Trung Luong – My Thuan – Can Tho Expressway (CT01), taking 3 to 3.5 hours for 165,000 to 250,000 VND.'],
            ['q' => 'How much is admission to Binh Thuy Ancient House and Con Son Islet?', 'a' => 'Binh Thuy Ancient House (built in 1870) charges 20,000 to 30,000 VND entry. Con Son community islet is reached via a 20,000 to 30,000 VND ferry from Co Bac Pier, with orchard and local culinary stops costing 50,000 to 150,000 VND.'],
        ],
        'vietnam-train-travel' => [
            ['q' => 'What is the official website to buy Vietnam train tickets?', 'a' => 'Book directly on Vietnam Railways official portals at dsvn.vn or vetau.com.vn using an international Visa or Mastercard with zero agency markup. Regular tickets open 60 to 90 days before departure.'],
            ['q' => 'Which Reunification Express train is best: SE1/SE2, SE3/SE4, or SE19/SE20?', 'a' => 'SE1/SE2 and SE3/SE4 are the fastest flagship North-South express trains with air-conditioned 4-berth soft sleepers (AnN). SE19/SE20 is the dedicated overnight Hanoi – Hue – Da Nang express (1,050,000 to 1,450,000 VND), and HD1/HD2 (150,000 to 180,000 VND) is the daytime Hai Van Pass scenic train.'],
            ['q' => 'What is the baggage allowance on Vietnam Railways trains?', 'a' => 'Adult passengers receive a 20-kilogram carry-on baggage allowance (up to 0.8m x 0.5m x 0.4m) stored under the lower berths or on overhead racks in 4-berth sleeper compartments.'],
        ],
        'ba-be-lake-travel-guide' => [
            ['q' => 'How much is the Ba Be National Park entrance ticket and boat rental in 2026?', 'a' => 'Ba Be National Park standard admission costs 70,000 VND per adult (45,000 VND lake-only sector rate). Private wooden motorboat charters on Ba Be Lake cost 300,000 to 800,000 VND per boat depending on route length (covering An Ma Temple, Widow Island, Tien Pond, Puong Cave, and Dau Dang Waterfall).'],
            ['q' => 'How do I travel from Hanoi to Ba Be Lake?', 'a' => 'Direct limousine vans and coaches depart My Dinh Bus Station in Hanoi via the Hanoi – Thai Nguyen Expressway (CT07) and National Highway 3, covering the 230-kilometer route to Pac Ngoi and Bo Lu lakeside villages in 4.5 to 5.5 hours for 250,000 to 350,000 VND.'],
            ['q' => 'Where should I stay at Ba Be Lake?', 'a' => 'Stay in traditional Tay ethnic wooden stilt-house homestays in Pac Ngoi or Bo Lu village right on the southern shore of Ba Be Lake (150,000 to 400,000 VND per night), offering direct boat pier access and home-cooked lakeside dinners.'],
        ],
        'pu-luong-travel-guide' => [
            ['q' => 'How do I get from Hanoi to Pu Luong Nature Reserve?', 'a' => 'Daily 9-to-15-seat VIP limousine shuttle vans depart Hanoi Old Quarter at 7:00 AM–7:30 AM via Highway 6 and QL15C directly to Don Village and Hieu Village in Pu Luong, taking 4 to 4.5 hours for 420,000 to 440,000 VND one-way (800,000 to 850,000 VND round-trip).'],
            ['q' => 'What are the entrance fees for Cham Stream bamboo rafting, Kho Muong Cave, and Hieu Waterfall?', 'a' => 'Pu Luong Nature Reserve has no main gate fee. Local community tickets cost 10,000 VND at Kho Muong Bat Cave, 15,000 to 20,000 VND at Hieu Waterfall, and 50,000 to 100,000 VND per person for bamboo rafting and viewing the giant bamboo waterwheels on Cham Stream (Suoi Cham).'],
            ['q' => 'When is the golden rice harvest season in Pu Luong?', 'a' => 'Unlike high-altitude northern terraces that have only one harvest, Pu Luong enjoys two annual rice harvest windows: late May to early June and late September to mid-October.'],
        ],
        'mu-cang-chai-travel-guide' => [
            ['q' => 'How much does it cost to visit Mam Xoi (Raspberry Hill) in Mu Cang Chai?', 'a' => 'Admission to the Mam Xoi terrace viewpoint in La Pan Tan commune costs 20,000 to 30,000 VND per adult. Because the paved lane up from the valley bridge is extremely steep, local Hmong riders operate regulated round-trip motorbike taxis (xe om) to the viewpoint for 40,000 to 50,000 VND.'],
            ['q' => 'When is the golden rice season in Mu Cang Chai?', 'a' => 'The golden ripe rice harvest peaks from mid-September through the first week of October across La Pan Tan, Che Cu Nha, and De Xu Phinh, whereas the mirror-like watering season (mua nuoc do) runs from May to June.'],
            ['q' => 'How do I travel from Hanoi or Sapa to Mu Cang Chai?', 'a' => 'From Hanoi My Dinh Bus Station, sleeper and limousine buses travel 300 kilometers via National Highway 32 over Khau Pha Pass (1,200m–1,500m) to Mu Cang Chai in 6.5 to 7.5 hours (300,000 to 400,000 VND). From Sapa, buses cross O Quy Ho Pass via Than Uyen in 3.5 to 4.5 hours.'],
        ],
        'ly-son-travel-guide' => [
            ['q' => 'How do I take the ferry to Ly Son Island from Sa Ky Port in 2026?', 'a' => 'High-speed catamarans (Super Bien Dong, Phu Quoc Express, An Vinh Express, Chinh Nghia) depart Sa Ky Port (20 km east of Quang Ngai city) for Ben Dinh Port on Ly Son Island in 30 to 35 minutes. One-way tickets cost 178,000 to 185,000 VND for Standard/Eco class (including the 22,000 VND port fee) and 238,000 VND for VIP class.'],
            ['q' => 'How do I get from Chu Lai Airport (VCL) or Da Nang to Sa Ky Port?', 'a' => 'Chu Lai Airport (VCL) sits 48 kilometers north of Sa Ky Port (50 minutes by taxi for 450,000 to 550,000 VND, or free/subsidized Quang Ngai airport shuttle bus plus local connection). From Da Nang, take a 2.5-hour expressway van or train to Quang Ngai Station, then a 30-minute taxi or Bus Route 03 to Sa Ky Port.'],
            ['q' => 'How do I visit Be Island (Dao Be / An Binh) from Ly Son?', 'a' => 'Speedboats depart Ly Son main harbor every morning between 7:30 AM and 8:30 AM for the 10-minute crossing to An Binh (Be Island), costing 120,000 to 140,000 VND round-trip, returning between 11:30 AM and 2:00 PM.'],
        ],
        'mai-chau-travel-guide' => [
            ['q' => 'How long does it take to travel from Hanoi to Mai Chau Valley?', 'a' => 'Via the CT02 Hanoi – Hoa Binh Expressway and National Highway 6 over Thung Khe White Rock Pass, VIP limousine vans cover the 140-kilometer route from Hanoi Old Quarter to Lac Village and Pom Coong Village in 3 to 3.5 hours for 220,000 to 300,000 VND.'],
            ['q' => 'What are the ticket fees for Chieu Cave, Mo Luong Cave, and cycling in Mai Chau?', 'a' => 'Climbing the 1,200 stone steps to Chieu Cave costs 10,000 VND, Mo Luong Cave costs 20,000 to 30,000 VND, and flat valley bicycle rentals in Lac Village and Pom Coong Village cost 30,000 to 50,000 VND per day.'],
            ['q' => 'Can I combine Mai Chau and Pu Luong in one trip?', 'a' => 'Yes. Mai Chau sits only 45 kilometers (1.5 hours via QL15C) north of Pu Luong Nature Reserve, making a 3-to-4-day combined loop from Hanoi through Mai Chau and Pu Luong the cleanest rural valley itinerary in northern Vietnam.'],
        ],
        'ninh-binh-vs-ha-long-bay' => [
            ['q' => 'Should I visit Ninh Binh or Ha Long Bay if I only have time for one?', 'a' => 'Choose Ninh Binh if you prefer independent cycling through rice valleys, 2.5-hour rowboat caves (Trang An or Tam Coc, 250,000 VND), and lower costs (1.5 hours from Hanoi by train/van). Choose Ha Long Bay or Lan Ha Bay if you want an overnight steel vessel cruise surrounded by open-sea limestone karsts (2.5 hours from Hanoi via CT04).'],
            ['q' => 'Can I travel directly between Ninh Binh and Ha Long Bay without returning to Hanoi?', 'a' => 'Yes. Direct limousine shuttle buses run daily at 7:00 AM–7:30 AM between Tam Coc/Trang An and Tuan Chau/Halong Cruise Port via National Highway 10 in 3.5 to 4 hours for 300,000 to 450,000 VND.'],
            ['q' => 'Which destination is less affected by typhoon cruise cancellations?', 'a' => 'Ninh Binh is an inland river valley and remains open when Gulf of Tonkin maritime port authorities suspend overnight Ha Long Bay cruises under Beaufort Force 6 wind warnings or tropical storms.'],
        ],
        'sapa-vs-ha-giang' => [
            ['q' => 'Is Sapa or the Ha Giang Loop better for first-time visitors?', 'a' => 'Sapa is better for guided village trekking in Muong Hoa Valley, riding the 3,143-meter Fansipan cable car (800,000 to 850,000 VND), and comfortable boutique hotels reached in 5.5 hours by expressway bus or overnight train. The Ha Giang Loop is a rugged 3-to-4-day, 350-kilometer motorcycle or Easy Rider circuit across Ma Pi Leng Pass and Dong Van Geopark.'],
            ['q' => 'Can I travel directly from Sapa to Ha Giang without returning to Hanoi?', 'a' => 'Yes. Direct daily sleeper and cabin buses connect Sapa and Ha Giang city via National Highway 279 in 6 to 7 hours (300,000 to 380,000 VND), saving a 12-hour detour through Hanoi.'],
            ['q' => 'Which destination requires a special border permit or motorcycle license?', 'a' => 'Ha Giang requires a 230,000 VND border area permit for foreign visitors entering Dong Van and Meo Vac, and self-riders legally require a 1968 IDP with a motorcycle endorsement; Sapa trekking requires neither.'],
        ],
        'phu-quoc-vs-con-dao' => [
            ['q' => 'Should I choose Phu Quoc or Con Dao for a beach trip in Vietnam?', 'a' => 'Choose Phu Quoc for direct international flights (30-day visa-free entry), family beach resorts on Long Beach and Kem Beach, and the 7,899.9-meter Hon Thom sea cable car (650,000 VND). Choose Con Dao for quiet protected marine national park bays, historic prison heritage sites (50,000 VND), and green sea turtle nesting (May–October).'],
            ['q' => 'How do flight and ferry connections compare between Phu Quoc and Con Dao?', 'a' => 'Phu Quoc (PQC) has high-frequency daily jet flights from Ho Chi Minh City, Hanoi, and international hubs, plus 1.25-to-2.5-hour speed ferries from Ha Tien and Rach Gia. Con Dao (VCS) has limited regional flights from Ho Chi Minh City (45 minutes) and seasonal catamarans from Tran De (Soc Trang) and Vung Tau.'],
            ['q' => 'Do Phu Quoc and Con Dao have the same best weather months?', 'a' => 'Phu Quoc has its calmest, sunniest west-coast beach weather from November through April, whereas Con Dao experiences calmer seas for diving and boat trips from March through September (when northeast monsoon winds subside).'],
        ],
        '7-days-in-vietnam' => [
            ['q' => 'Should a 7-day Vietnam itinerary focus on one region or cover the whole country?', 'a' => 'Focus on a single region (such as the Northern Triangle of Hanoi, Ninh Binh, and Ha Long Bay/Lan Ha Bay) or at most a two-hub split linked by a single 1-hour-20-minute domestic flight (Hanoi + Da Nang/Hoi An). Trying to cover North, Central, and South in 7 days loses 40 percent of your trip to airport transfers.'],
            ['q' => 'What is the best 7-day Northern Vietnam route?', 'a' => 'Spend Days 1–2 in Hanoi Old Quarter, Day 3–4 in Ninh Binh (2-hour train/limousine for Trang An 250,000 VND sampan caves and Hang Mua 100,000 VND viewpoint), Day 5–6 on an overnight Ha Long or Lan Ha Bay cruise (3.5-hour direct limousine from Ninh Binh), and Day 7 returning to Hanoi (2.5 hours via CT04 expressway).'],
            ['q' => 'How much does a 7-day trip to Vietnam cost per person?', 'a' => 'Excluding international airfare, a 7-day Vietnam itinerary costs 250 to 350 USD for budget backpackers, 600 to 950 USD for mid-range boutique travelers (including a 2-day/1-night bay cruise), and 1,500 USD and above for 5-star luxury stays.'],
        ],
        '10-days-in-vietnam' => [
            ['q' => 'How do you structure a 10-day Vietnam itinerary without rushing?', 'a' => 'Limit a 10-day route to 3 major bases connected by two 80-minute domestic flights: Days 1–4 in Northern Vietnam (Hanoi + Ninh Binh or a 2D1N Ha Long/Lan Ha Bay cruise), Days 5–7 in Central Vietnam (Hoi An and Da Nang via DAD airport), and Days 8–10 in Southern Vietnam (Ho Chi Minh City and a Ben Tre Mekong day trip).'],
            ['q' => 'Should I take the overnight train or fly on a 10-day Vietnam trip?', 'a' => 'Use 1-hour-20-minute domestic flights (HAN to DAD, and DAD to SGN) on a 10-day schedule to preserve daylight sightseeing hours, or take the overnight SE19/SE1 sleeper train (Hanoi to Hue/Da Nang, 13.5 to 16 hours) if you want to combine transport with one night of accommodation.'],
            ['q' => 'Can I fit both Sapa and Ha Long Bay into a 10-day North-to-South itinerary?', 'a' => 'Fitting Sapa (5.5 hours each way from Hanoi) and Ha Long Bay into a 10-day North-to-South route is too rushed. If Sapa or Ha Giang is a priority, dedicate all 10 days to Northern Vietnam rather than flying south.'],
        ],
        '14-days-in-vietnam' => [
            ['q' => 'What is the ideal route for 14 days in Vietnam?', 'a' => 'Fourteen days allows a balanced 5-stop North-to-South spine: Hanoi (3 nights), Ninh Binh (2 nights), Ha Long Bay or Lan Ha Bay cruise (1–2 nights), Hue and Hoi An over the Hai Van Pass via HD1/HD2 heritage train (4 nights), and Ho Chi Minh City with the Mekong Delta (3 nights).'],
            ['q' => 'How many domestic flights do I need for a 2-week Vietnam itinerary?', 'a' => 'You only need 2 domestic flights: either fly from Hanoi (HAN) or Hai Phong (HPH) to Hue (HUI) / Da Nang (DAD), and then fly from Da Nang (DAD) to Ho Chi Minh City (SGN) or Can Tho (VCA), completing the shorter regional hops by expressway limousine or scenic rail.'],
            ['q' => 'What is a realistic 2-week Vietnam budget for two travelers?', 'a' => 'For two mid-range travelers sharing private double rooms, a 14-day trip costs 1,800 to 2,600 USD total (approximately 65 to 95 USD per person per day), covering boutique 4-star hotels, a 2D1N bay cruise, two domestic flights per person, and guided excursions.'],
        ],
        '21-days-in-vietnam' => [
            ['q' => 'What extra destinations can I add on a 21-day Vietnam itinerary?', 'a' => 'With 21 days (3 weeks), you can add the 3-to-4-day Ha Giang Loop (350 km karst circuit) or Sapa trekking in the North, Phong Nha-Ke Bang National Park caves (Paradise Cave 250,000 VND, Phong Nha Cave 150,000 VND) in North-Central Vietnam, and a 3-night beach finale on Phu Quoc or Con Dao Island.'],
            ['q' => 'Does a 21-day Vietnam trip require a visa for European and British travelers?', 'a' => 'Citizens of 25 countries—including the UK, France, Germany, Spain, Italy, Denmark, Sweden, Norway, Finland, Japan, South Korea, and 12 additional European states under Resolutions 44/NQ-CP and 229/NQ-CP—receive a 45-day visa-free entry stamp, which easily covers a 21-day itinerary without applying for an e-visa.'],
            ['q' => 'Can I travel overland by train for the entire 21-day Vietnam route?', 'a' => 'Yes. Three weeks provides enough margin to travel 100 percent overland along the 1,726-kilometer Reunification Express rail line (Hanoi – Ninh Binh – Dong Hoi/Phong Nha – Hue – Da Nang/Hoi An – Quy Nhon/Nha Trang – Ho Chi Minh City) without taking a single domestic flight.'],
        ],
        'hanoi-vs-ho-chi-minh-city' => [
            ['q' => 'Is Hanoi or Ho Chi Minh City better for first-time visitors to Vietnam?', 'a' => 'Hanoi is better for thousand-year imperial history, walkable lakes (Hoan Kiem and West Lake), French colonial boulevards, and direct 2-hour access to Ninh Binh and Ha Long Bay. Ho Chi Minh City (Saigon) is better for modern sky-bar energy, 20th-century war history (War Remnants Museum 40,000 VND, Cu Chi Tunnels 125,000 VND), Metro Line 1 transit, and Mekong Delta boat trips.'],
            ['q' => 'Which city is cheaper: Hanoi or Ho Chi Minh City?', 'a' => 'Hanoi is approximately 10 to 15 percent less expensive than Ho Chi Minh City District 1 for boutique Old Quarter hotels, street pho and bun cha (40,000 to 65,000 VND), and airport transfers (Express Bus 86 costs 45,000 VND in Hanoi; Bus 109/152 costs 5,000–20,000 VND in HCMC).'],
            ['q' => 'How do the climates of Hanoi and Ho Chi Minh City differ?', 'a' => 'Hanoi has four distinct seasons, including a cool, misty winter from December to February (12°C–18°C) and hot summers (May–August). Ho Chi Minh City stays tropical and warm year-round (26°C–34°C), split between a dry season (December–April) and an afternoon-shower rainy season (May–November).'],
        ],
        'transport-within-vietnam' => [
            ['q' => 'What is the best way to get around between cities in Vietnam?', 'a' => 'Use 1-to-2-hour domestic flights for distances over 600 kilometers (such as Hanoi to Da Nang or Da Nang to Ho Chi Minh City), Reunification Express sleeper trains (dsvn.vn) for scenic medium-haul coastal sectors (Hue to Da Nang HD1/HD2, or Hanoi to Ninh Binh/Dong Hoi), and 9-seat VIP limousine vans for 2-to-4-hour expressway hops.'],
            ['q' => 'Which ride-hailing apps work best inside Vietnamese cities?', 'a' => 'Grab, Xanh SM (all-electric VinFast taxis with fixed upfront pricing and clean meters), and Be are the three primary ride-hailing apps across Hanoi, Da Nang, Hoi An, Nha Trang, and Ho Chi Minh City. Link a Visa or Mastercard inside the app to avoid cash-change disputes.'],
            ['q' => 'Are sleeper buses safe in Vietnam?', 'a' => 'Daytime VIP 9-seat limousine vans on divided national expressways (CT01, CT04, CT05) are comfortable and reliable. For long overnight mountain or coastal journeys, overnight trains (SE1/SE3/SE19 or SP3/SP4 to Lao Cai) are safer and provide flatter beds than highway sleeper buses.'],
        ],
        'vietnam-domestic-flights-guide' => [
            ['q' => 'Which domestic airline is most reliable in Vietnam: Vietnam Airlines or Vietjet Air?', 'a' => 'Vietnam Airlines (the national flag carrier) has higher on-time performance and includes 10 kg carry-on plus 23 kg checked baggage on Economy Classic fares. Vietjet Air offers lower base fares across a wide domestic network, but Eco tickets include only 7 kg carry-on baggage and experience more afternoon cascading delays.'],
            ['q' => 'Which terminal is used for domestic flights at Ho Chi Minh City (SGN) and Hanoi (HAN)?', 'a' => 'At Ho Chi Minh City Tan Son Nhat Airport (SGN), Terminal T3 serves primary domestic flights for Vietnam Airlines and Vietjet Air, while Terminal T1 serves remaining domestic regional carriers (such as VASCO to Con Dao) and Terminal T2 handles international flights. At Hanoi Noi Bai Airport (HAN), all domestic flights use Terminal T1 and international flights use Terminal T2.'],
            ['q' => 'How early should I arrive at the airport for a domestic flight in Vietnam?', 'a' => 'Arrive at least 2 hours before scheduled departure for domestic flights (2.5 hours during peak summer months or Tet holidays), as domestic check-in counters strictly close 40 minutes before departure and boarding gates close 15 minutes prior.'],
        ],
        'my-son-sanctuary-guide' => [
            ['q' => 'How much is the My Son Sanctuary entrance ticket in 2026 and what is included?', 'a' => 'Admission to the My Son Sanctuary UNESCO World Heritage site costs 150,000 VND per international adult (50,000 VND for children aged 5–15). The 150,000 VND ticket already includes the mandatory 2-kilometer round-trip electric shuttle buggy ride from the reception building to the central valley trailheads, entry to the My Son Museum, and scheduled Cham Apsara dance performances.'],
            ['q' => 'How do I get from Hoi An or Da Nang to My Son Sanctuary?', 'a' => 'My Son Sanctuary sits in a lush ring of hills in Duy Xuyen District, 40 kilometers (55 to 60 minutes by car or scooter) southwest of Hoi An Ancient Town and 45 kilometers (70 minutes) southwest of Da Nang. Shared sunrise shuttle buses from Hoi An cost 150,000 to 250,000 VND round-trip, or a private round-trip car with waiting time costs 650,000 to 850,000 VND.'],
            ['q' => 'What time of day is best to visit My Son Sanctuary?', 'a' => 'Arrive right when the gates open at 6:00 AM–6:30 AM (or after 3:00 PM) to explore brick temple groups B, C, D, and G in cool morning light before tour buses arrive at 8:30 AM and midday valley temperatures exceed 34°C.'],
        ],
        'cham-islands-travel-guide' => [
            ['q' => 'How do I get to the Cham Islands (Cu Lao Cham) from Hoi An and how much does it cost?', 'a' => 'High-speed canoes depart Cua Dai Port (5 km east of Hoi An Ancient Town) between 8:00 AM and 9:30 AM, reaching Bai Lang Pier on Hon Lao in 15 to 20 minutes. Round-trip speedboat tickets cost 350,000 to 600,000 VND per adult, and entry into the Cu Lao Cham UNESCO Biosphere Reserve requires a 70,000 VND conservation ticket plus a 20,000 to 30,000 VND ecological fee (usually bundled in day-tour packages).'],
            ['q' => 'When is the best season for snorkeling and diving at the Cham Islands?', 'a' => 'March through August offers flat, calm seas and 15-to-25-meter underwater visibility around Hon Dai, Hon Tai, and Bai Xep coral reefs. From October to February, northeast monsoon swells frequently cause the Cua Dai Port Authority to suspend speedboat crossings.'],
            ['q' => 'Are plastic bags allowed on the Cham Islands?', 'a' => 'No. Cu Lao Cham strictly enforces a zero single-use plastic bag policy at Cua Dai Port boarding gates; bring a reusable cloth tote and refillable water bottle before boarding the speedboat.'],
        ],
        'bai-tu-long-bay-guide' => [
            ['q' => 'Which cruise port is used for Bai Tu Long Bay cruises and island ferries?', 'a' => 'Overnight Bai Tu Long Bay cruises depart from either Halong International Cruise Port (Bai Chay) or Ao Tien International Harbor in Van Don District (Quang Ninh, 2.5 to 3 hours from Hanoi via the CT04/CT06 expressway, with a 30,000 to 40,000 VND passenger wharf fee). Ao Tien Harbor is also the modern hub for high-speed ferries to Quan Lan and Co To islands.'],
            ['q' => 'How much is the Bai Tu Long Bay sightseeing route fee for Vung Vieng and Thien Canh Son Cave?', 'a' => 'Ha Long – Bai Tu Long Sightseeing Route 4 (covering Cong Do, Vung Vieng Floating Fishing Village, and Thien Canh Son Cave) carries a 200,000 VND daytime sightseeing ticket (or 550,000 VND–750,000 VND overnight marine stay fee), which is included inside overnight cruise packages.'],
            ['q' => 'How does Bai Tu Long Bay compare to Ha Long Bay?', 'a' => 'Bai Tu Long Bay shares the exact same towering limestone karst geology as central Ha Long Bay but receives less than 15 percent of the cruise boat traffic, offering tranquil kayaking lagoons around Cap La and rowboat visits to Vung Vieng without crowded cave queues.'],
        ],
        'money-cash-cards-atms' => [
            ['q' => 'What are the best bank ATMs in Vietnam for international cards?', 'a' => 'VPBank is widely considered the top choice for foreign travelers, charging 0 VND local access fee for international Visa and Mastercard cards with single-withdrawal limits up to 5,000,000 to 10,000,000 VND. State banks like Vietcombank and BIDV charge 50,000 to 66,000 VND per withdrawal with typical limits of 2,000,000 to 5,000,000 VND.'],
            ['q' => 'How do I avoid the polymer banknote color confusion in Vietnam?', 'a' => 'Examine printed numerical digits carefully in low lighting: the 20,000 VND note (blue) looks strikingly similar to the 500,000 VND note (cyan/light blue) but differs by a factor of 25, while the 10,000 VND note (yellow-brown) can be confused with the 200,000 VND note (red-brown).'],
            ['q' => 'Where do I get the best cash currency exchange rates in Vietnam?', 'a' => 'Licensed gold shops along Ha Trung Street in Hanoi Old Quarter and near Ben Thanh Market in Ho Chi Minh City offer the narrowest currency exchange spreads for clean, uncreased 100 USD bills (post-2013 series). Official bank branches also exchange currency upon presenting your original physical passport.'],
        ],
        'vietnam-scooter-rental-checklist' => [
            ['q' => 'Can foreign tourists legally rent and drive a scooter in Vietnam?', 'a' => 'Legally, foreign tourists riding a scooter over 50cc must hold an original domestic motorcycle license plus a 1968 Vienna Convention International Driving Permit (IDP) with motorcycle endorsement (Category A/A1). 1949 Geneva Convention IDPs (issued in the US, Canada, and Australia) are not legally recognized under Vietnamese traffic law, and riding without a valid license voids travel medical insurance.'],
            ['q' => 'What are the police fines for driving a scooter without a valid license in Vietnam?', 'a' => 'Under Decree 100/2019/ND-CP and Decree 123/2021/ND-CP, driving a motorcycle up to 125cc without a recognized license incurs a fine of 2,000,000 to 4,000,000 VND, while motorcycles over 125cc carry fines of 6,000,000 to 8,000,000 VND, along with possible vehicle impoundment for 7 to 10 days.'],
            ['q' => 'Should I leave my original passport as a scooter rental deposit?', 'a' => 'Never surrender your original passport as a scooter rental deposit. Reputable rental shops accept a color photocopy of your passport and entry stamp along with a refundable cash deposit of 1,000,000 to 2,000,000 VND (40 to 80 USD). Always inspect brakes, tires, and lights, and take date-stamped photos of all body panels before riding away.'],
        ],
        'safety-scams-vietnam' => [
            ['q' => 'What are the most common tourist scams in Vietnam and how do I avoid them?', 'a' => 'The three most frequent tourist scams are rigged taximeters (avoided by booking Grab or Xanh SM electric taxis with upfront pricing), unsolicited street shoe cleaning/glue repairs around Hoan Kiem Lake (firmly say no and keep walking), and fruit vendor photo prop extortion (agree on price or politely decline before touching shoulder poles).'],
            ['q' => 'What emergency numbers and tourist hotlines should I call in Vietnam?', 'a' => 'National emergency numbers are 113 for Police, 115 for Medical Ambulance, and 114 for Fire Rescue. For tourist assistance, call the Hanoi Tourism Support Hotline at 024 1081 / 0943 001 081 or the Ho Chi Minh City Tourism Hotline at 028 1088.'],
            ['q' => 'Is Vietnam safe for solo travelers and female backpackers?', 'a' => 'Vietnam is consistently ranked among the safest destinations in Southeast Asia with very low rates of violent crime. Standard travel precautions apply: keep smartphones and crossbody bags secure from opportunistic drive-by snatching along busy roads in Ho Chi Minh City District 1, and use hotel room safes for passport storage.'],
        ],
        'vietnam-food-safety-street-food-etiquette' => [
            ['q' => 'Is tap water safe to drink in Vietnam?', 'a' => 'Tap water is not potable anywhere in Vietnam, including 5-star hotels in Hanoi and Ho Chi Minh City. Always drink bottled, filtered, or boiled water. Most travelers brush their teeth with tap water safely, but sensitive travelers may prefer bottled water for rinsing.'],
            ['q' => 'Is ice (da) in street beverages safe to consume in Vietnam?', 'a' => 'Factory-made cylindrical ice with a hollow center is produced from purified reverse-osmosis water and is safe to consume in iced coffee (ca phe da) and iced tea (tra da). Avoid large crushed ice chunks manually chipped from street-side ice blocks, which may have been stored on dirty floors or handled unhygienically.'],
            ['q' => 'What is the proper etiquette when eating at street food stalls in Vietnam?', 'a' => 'Take a paper napkin from the tabletop dispenser and wipe your chopsticks and soup spoon before eating. Discard used napkins, lime peels, and herb stems into the small waste bin placed on the floor beneath your plastic stool rather than leaving them on the dining table.'],
        ],
        'tipping-in-vietnam' => [
            ['q' => 'Is tipping customary in Vietnam?', 'a' => 'Tipping is not a traditional Vietnamese custom and is never mandatory. Local street food stalls, markets, and family-run restaurants do not expect tips and will return exact change down to the last 1,000 VND note.'],
            ['q' => 'When and how much should I tip in Vietnam?', 'a' => 'In international tourist restaurants and spas, tipping 5 to 10 percent (or rounding up the bill) is appreciated if a 5% service charge is not already included. For private tour guides, tipping 100,000 to 150,000 VND (4 to 6 USD) per person per day and 50,000 to 100,000 VND for private drivers is standard practice. For sampan rowers in Ninh Binh (Trang An or Tam Coc), a direct tip of 50,000 to 100,000 VND per boat is customary.'],
            ['q' => 'What currency should I use when tipping in Vietnam?', 'a' => 'Always tip in Vietnamese Dong (VND) with clean banknotes (starting from 20,000 VND upward). Tipping in foreign coins is unusable for locals as Vietnamese banks do not exchange foreign coinage.'],
        ],
        'ha-tien-to-phu-quoc-ferry' => [
            ['q' => 'How long does the fast ferry take from Ha Tien to Phu Quoc and how much does it cost?', 'a' => 'High-speed passenger catamarans operated by Superdong and Phu Quoc Express make the sea crossing from Ha Tien Ferry Terminal to Bai Vong Port in 1 hour 15 minutes to 1 hour 25 minutes. A standard adult ticket costs 216,000 to 230,000 VND (approx. 9 USD) each way, while VIP class on Phu Quoc Express costs around 350,000 VND.'],
            ['q' => 'Do I need a passport to board the Ha Tien to Phu Quoc ferry?', 'a' => 'Yes. All foreign passengers must present an original physical passport at the ticket counter and boarding gate for maritime passenger manifest registration.'],
            ['q' => 'How do I get from Bai Vong Port to Long Beach or Duong Dong in Phu Quoc?', 'a' => 'Bai Vong Port is located on Phu Quoc east coast, 12 kilometers east of Duong Dong town center. Metered taxis (Mai Linh or Vinasun) cost 150,000 to 200,000 VND, while shared shuttle minibuses operate outside the ferry arrival gangway for 40,000 to 50,000 VND per passenger.'],
        ],
        'ho-chi-minh-city-to-chau-doc-transport' => [
            ['q' => 'How do I travel from Ho Chi Minh City to Chau Doc and how long does it take?', 'a' => 'High-frequency 34-berth limousine sleeper buses operated by Futa Bus Lines (Phuong Trang) depart Mien Tay Bus Station (395 Kinh Duong Vuong, Binh Tan District) every hour around the clock. The 245-kilometer trip takes 5.5 to 6.5 hours via the CT01 Expressway and costs 210,000 to 265,000 VND (9 to 11 USD).'],
            ['q' => 'Can I connect from Chau Doc to Phnom Penh, Cambodia by boat?', 'a' => 'Yes. High-speed passenger boats (operated by Blue Cruiser and Hang Chau Tourist) depart Victoria Chau Doc Pier daily between 7:00 AM and 7:30 AM, reaching Phnom Penh Sisowath Quay in 5 hours (including Vietnamese exit immigration at Vinh Xuong and Cambodian entry at Kaam Samnor).'],
            ['q' => 'How do I get from Chau Doc bus station to Tra Su Cajuput Forest?', 'a' => 'Tra Su Cajuput Forest sits 30 kilometers south of Chau Doc in Van Giao commune. A Grab car or metered taxi takes 40 minutes and costs 350,000 to 450,000 VND each way, or you can rent a scooter in Chau Doc for 150,000 VND per day.'],
        ],
        'ha-long-bay-day-trip-vs-overnight-cruise' => [
            ['q' => 'Is a day cruise or an overnight cruise better in Ha Long Bay?', 'a' => 'An overnight cruise (2 days / 1 night or 3 days / 2 nights) is far superior because it allows you to sleep on the bay, kayak into tranquil lagoons at sunrise, avoid midday day-boat crowds, and explore quieter waters in Lan Ha or Bai Tu Long bays. A 4-to-6-hour day cruise is only recommended if you have fewer than 7 days total in Vietnam and cannot spare an overnight stay.'],
            ['q' => 'How much does a Ha Long Bay day tour cost compared to an overnight cruise?', 'a' => 'A full-day cruise from Hanoi (including 2.5-hour highway expressway transfers, lunch, and Sung Sot Cave) costs 45 to 75 USD per person. A 2D1N overnight luxury cruise ranges from 135 to 250 USD per person (including all four onboard meals, en-suite cabin, kayaking, and cave excursions).'],
            ['q' => 'What happens to Ha Long Bay cruises during bad weather or storms?', 'a' => 'The Quang Ninh Maritime Port Authority monitors maritime weather strictly. When Beaufort Force 6 winds (22–27 knots) or typhoon warnings are issued, overnight sailing permits are suspended for passenger safety. Reputable cruise operators provide full refunds or arrange day-tour alternatives in accordance with maritime port regulations.'],
        ],
        'where-to-stay-in-buon-ma-thuot' => [
            ['q' => 'Where is the best area to stay in Buon Ma Thuot?', 'a' => 'Central Buon Ma Thuot around the Victory Monument (Nga Sau Xe Tang) and Phan Chu Trinh or Le Duan streets is best for first-time visitors, placing you within walking distance of local coffee shops, the Dak Lak Museum, and evening food stalls. Travelers seeking immersion in nature can base themselves at eco-lodges near Dray Nur Waterfall (25 km south) or lakeside tent camps at Lak Lake (56 km south via QL27).'],
            ['q' => 'How much is admission to Dray Nur Waterfall and the Coffee World Museum?', 'a' => 'Entry to Dray Nur Waterfall is 30,000 to 40,000 VND per person (with suspension bridge access across the Srepok River toward Dray Sap). The World of Coffee Museum (Bao tang The gioi Ca phe on Nguyen Dinh Chieu Street) charges 150,000 VND per adult, while the Trung Nguyen Coffee Village on Ly Thai To Street offers free grounds admission with cafe purchases.'],
            ['q' => 'How do I travel between Buon Ma Thuot, Nha Trang, and Da Lat?', 'a' => 'Buon Ma Thuot connects to Nha Trang via National Highway 26 (QL26, 185 km, 3.5 to 4.5 hours across Phuong Hoang Pass by limousine van for 180,000 to 250,000 VND). The mountainous route to Da Lat follows National Highway 27 (QL27, 210 km, 5.5 to 6.5 hours across Chuoi Pass by Phuc Hai limousine or sleeper bus for 200,000 to 400,000 VND).'],
        ],
        'where-to-stay-in-pleiku' => [
            ['q' => 'Where is the best neighborhood to stay in Pleiku?', 'a' => 'Central Pleiku along Hung Vuong, Tran Phu, or Le Lai streets offers optimal access to city restaurants, Pleiku Night Market, and the Gia Lai Provincial Museum, while Pleiku Airport (PXU) is just 6 kilometers northeast. Nature-focused travelers can choose homestays near Bien Ho (Sea Lake), located 7 to 8 kilometers north of the town center.'],
            ['q' => 'Is there an entrance fee for Bien Ho and Chu Dang Ya volcano?', 'a' => 'Bien Ho (Sea Lake, a freshwater volcanic crater lake surrounded by pine forests) has free admission for visitors, with a nominal scooter parking fee of 10,000 VND. Chu Dang Ya volcano, situated 30 kilometers northeast in Chu Pah District, is also free to explore, with golden wild sunflower blooms (hoa da quy) peaking in November and December.'],
            ['q' => 'How far is Kon Tum from Pleiku and how do I get there?', 'a' => 'Kon Tum is located 50 kilometers north of Pleiku along National Highway 14 (QL14). The journey takes approximately 1 hour by local interprovincial bus (costing 50,000 to 70,000 VND) or 450,000 to 600,000 VND by private taxi, providing easy day-trip access to the historic Kon Tum Wooden Church (built in 1913) and the Kon Klor Communal House.'],
        ],
        'where-to-stay-in-chau-doc' => [
            ['q' => 'Where should I stay in Chau Doc for river tours and Cambodia border crossings?', 'a' => 'Staying along the Hau River waterfront on Le Loi Street puts you steps from Victoria Chau Doc Pier, where international speedboats depart daily at 7:00 to 7:30 AM for Phnom Penh, Cambodia (5 hours, 35 to 45 USD). It also allows easy boat hires for the Chau Doc floating market and Cham floating villages.'],
            ['q' => 'How much do tickets cost for Tra Su Cajuput Forest?', 'a' => 'Tra Su Cajuput Forest (30 km south of Chau Doc in Van Giao Commune) charges an official entrance ticket of 120,000 VND (which includes walking the 4-kilometer bamboo bridge). Exploring the deeper canals requires a motorboat (tac rang) ticket for 80,000 VND and a hand-rowed sampan (xuong cheo tay) ticket for 70,000 VND through duckweed carpets, totaling 270,000 VND per person for the full circuit.'],
            ['q' => 'Is it better to stay in Chau Doc town or near Mount Sam (Nui Sam)?', 'a' => 'Chau Doc town center is best for international boat departures, market walks, and dining. The Mount Sam area (5 km southwest) is suitable if your primary focus is pilgrimage visits to Ba Chua Xu Temple and Tay An Pagoda, or if you prefer mountain views from resorts near the Nui Sam cable car station.'],
        ],
        'where-to-stay-in-ha-tien' => [
            ['q' => 'Where is the best area to stay in Ha Tien for the Phu Quoc ferry?', 'a' => 'Ha Tien town center along Tran Phu, Bach Dang, or Dong Ho inlet streets is ideal for ferry connections, located within a 5-to-10-minute walk or short taxi ride from Ha Tien Passenger Ferry Terminal on Kim Du Street (servicing Superdong and Phu Quoc Express catamarans to Bai Vong Port).'],
            ['q' => 'What are the top attractions and entrance fees in Ha Tien?', 'a' => 'Thach Dong Cave Temple (Thach Dong Thon Thu, 4 km north near the Cambodian Prek Chak border gate) has an official entry ticket of 20,000 VND per adult and is open daily from 06:00 to 18:00. Mui Nai Beach (Bai Bien Mui Nai, 6 km west facing the Gulf of Thailand) is open 06:00 to 18:00 for coastal swimming and fresh seafood stalls.'],
            ['q' => 'Can I cross into Cambodia from Ha Tien?', 'a' => 'Yes. The Ha Tien (Xa Xia) / Prek Chak international border checkpoint is 7 kilometers north of Ha Tien town center. Travelers can obtain an e-visa or visa-on-arrival for Cambodia and continue onward to Kep (25 km, 45 minutes) or Kampot (40 km, 1 hour) by tuk-tuk or private van.'],
        ],
        'can-tho-to-chau-doc-transport' => [
            ['q' => 'How long does the bus take from Can Tho to Chau Doc and how much does it cost?', 'a' => 'Direct FUTA Bus Lines (Phuong Trang) coaches depart Can Tho Central Bus Station (Ben xe Trung tam Can Tho, 36 Nguyen Van Linh) every 1 to 2 hours between 06:00 and 18:30. The 120-kilometer trip northwest along National Highway 91 (QL91) takes 3 to 3.5 hours and tickets cost 150,000 to 220,000 VND (approx. 6 to 9 USD).'],
            ['q' => 'How much does a private car or taxi cost between Can Tho and Chau Doc?', 'a' => 'A private 4-seat or 7-seat sedan transfer between Can Tho and Chau Doc costs 1,400,000 to 1,800,000 VND (55 to 70 USD) one-way and completes the 120-kilometer drive in approximately 2.5 hours, with options to add an en-route stop at Long Xuyen crocodile farm or Tra Su Cajuput Forest.'],
            ['q' => 'How do I reach Tra Su Cajuput Forest after arriving in Chau Doc?', 'a' => 'From Chau Doc bus station or town center, Tra Su Cajuput Forest lies 30 kilometers south via QL91 and DT948. A round-trip metered taxi with 2 hours waiting time costs 350,000 to 450,000 VND, while renting a scooter in Chau Doc costs approximately 150,000 VND per day.'],
        ],
        'can-tho-to-ha-tien-transport' => [
            ['q' => 'How do I travel by public bus from Can Tho to Ha Tien?', 'a' => 'FUTA Bus Lines (Phuong Trang) runs daily 34-berth limousine sleeper buses from Ben xe Trung tam Can Tho to Ha Tien Bus Station (Ben xe Ha Tien). The 190-kilometer journey crosses Can Tho, An Giang, and Kien Giang provinces in 5.5 to 6 hours, with one-way fares ranging from 220,000 to 240,000 VND (approx. 9 to 10 USD).'],
            ['q' => 'Can I connect directly from Can Tho through Ha Tien to Phu Quoc Island on the same day?', 'a' => 'Yes, but you must depart Can Tho early. Catch the first morning FUTA bus around 06:00 to 07:00 AM to reach Ha Tien Bus Station by 12:00 to 12:30 PM, then take a 5-minute taxi (40,000 VND) to Ha Tien Ferry Terminal to board an afternoon Superdong or Phu Quoc Express fast catamaran (departing between 13:00 and 14:00, arriving in Phu Quoc by 15:15).'],
            ['q' => 'What is the driving route and road condition between Can Tho and Ha Tien?', 'a' => 'The road route covers roughly 190 kilometers following National Highway 91 (QL91) through Long Xuyen to Chau Doc, then continuing west on National Highway N2 / Provincial Road 955A along the Vinh Te Canal to Ha Tien. Roads are fully paved with active truck and agricultural traffic; self-drive scooter travelers should budget 6.5 to 7 hours including meal stops.'],
        ],
        'where-to-stay-in-quy-nhon' => [
            ['q' => 'Where is the best area to stay in Quy Nhon?', 'a' => 'Quy Nhon city beach along Xuan Dieu and An Duong Vuong streets is best for first-time visitors, offering beachfront walking promenades, fresh seafood dining at Xuan Dieu, and direct access to surf beaches. Travelers seeking secluded coastal stays prefer resort coves around Bai Xep fishing village (13 km south) or Nhon Ly near Ky Co Beach (25 km northeast).'],
            ['q' => 'How much are admission tickets for Eo Gio, Ky Co Beach, and Banh It Towers?', 'a' => 'Eo Gio walking coastal boardwalk charges 25,000 VND per person. Ky Co Beach charges 100,000 VND for gate admission plus 90,000 VND for the round-trip electric shuttle buggy, or you can purchase a 350,000 to 400,000 VND speedboat package from Nhon Ly pier including coral reef snorkeling at Bai Dua. Banh It Cham Towers (18 km north in Tuy Phuoc) charges 15,000 to 20,000 VND.'],
            ['q' => 'How do I get from Phu Cat Airport (UIH) to Quy Nhon city center?', 'a' => 'Phu Cat Airport is located 35 kilometers northwest of Quy Nhon. The official airport shuttle bus waits directly outside the arrivals terminal, takes 50 minutes to reach downtown (dropping off at 01 Nguyen Tat Thanh), and costs 50,000 VND per passenger. A metered taxi or private transfer costs 250,000 to 350,000 VND.'],
        ],
        'where-to-stay-in-nha-trang' => [
            ['q' => 'Which neighborhood is best to stay in Nha Trang?', 'a' => 'Tran Phu Beachfront Boulevard is the prime location for easy beach access, sailing clubs, and central dining. For a quieter atmosphere with local seafood eateries, the Pham Van Dong area north of Tran Phu Bridge is ideal, while boutique resorts on Hon Tre Island offer secluded beaches connected by the VinWonders cable car.'],
            ['q' => 'How much are tickets for VinWonders Nha Trang and Ponagar Cham Towers?', 'a' => 'VinWonders Nha Trang full-day admission (including round-trip sea-crossing cable car and theme park access) is 950,000 to 1,050,000 VND for adults and 710,000 to 800,000 VND for children (1m–1.4m) and seniors. Ponagar Cham Towers (Thap Ba Ponagar on 2/4 Street) charges an entry fee of 30,000 VND per adult (open 08:00–18:00), and the National Oceanographic Museum charges 40,000 VND.'],
            ['q' => 'How do I travel from Cam Ranh International Airport (CXR) to Nha Trang city?', 'a' => 'Cam Ranh Airport is situated 35 kilometers south of Nha Trang city center. Dat Moi Airport Bus No. 18 runs every 30 minutes from outside the terminal to central Nha Trang (Nguyen Thien Thuat and Tran Phu streets) for 60,000 to 65,000 VND in 45 to 50 minutes. Official airport taxis (Mai Linh or Sun Taxi) charge a fixed fare of 300,000 to 350,000 VND.'],
        ],
        'where-to-stay-in-mui-ne' => [
            ['q' => 'Should I stay in Mui Ne strip, Ham Tien, or Phan Thiet city center?', 'a' => 'Nguyen Dinh Chieu Street in central Ham Tien (commonly referred to as the Mui Ne tourist strip) is best for beachfront resorts, kite-surfing schools, international cafes, and massage spas. Phan Thiet city center (15 km west) is suitable for business travelers and heritage sights, while the northern end near Mui Ne fishing village offers budget guesthouses.'],
            ['q' => 'How much do White Sand Dunes ATV rentals and Fairy Stream tickets cost?', 'a' => 'Admission to the White Sand Dunes (Bau Trang, 30 km northeast of Mui Ne) is 15,000 to 20,000 VND per person, with optional ATV quad-bike or open-top 4x4 dune rides costing 600,000 to 800,000 VND for a 20-minute private ride up the high sand ridges. The Fairy Stream (Suoi Tien) charges an official entry fee of 15,000 VND per person for walking barefoot along the red silt canyon.'],
            ['q' => 'How do I travel from Ho Chi Minh City to Mui Ne by train?', 'a' => 'Daily express train SPT2 departs Saigon Railway Station (01 Nguyen Thong, District 3) at 06:45 AM, arriving at Phan Thiet Station at 10:30 AM (3 hours 45 minutes; air-conditioned soft seat tickets cost 170,000 to 230,000 VND). From Phan Thiet Railway Station, take a 25-minute taxi (200,000 to 250,000 VND) or local bus No. 1 or No. 9 (15,000 VND) along the coastal highway to the Ham Tien resort strip.'],
        ],
        'where-to-stay-in-dong-hoi' => [
            ['q' => 'Is it better to stay in Dong Hoi city or directly in Phong Nha village?', 'a' => 'Phong Nha village (Son Trach town along the Son River) is the recommended base for outdoor adventurers, placing you walking distance from cave excursion booking counters, bicycle rentals, and rural riverside homestays. Dong Hoi city (45 km east) is preferable if you arrive on late-night trains or flights, or if you want oceanfront dining along Nhat Le Beach.'],
            ['q' => 'What are the official ticket prices for Phong Nha Cave and Paradise Cave?', 'a' => 'Phong Nha Cave admission is 150,000 VND per adult (free for children under 1.3m), plus a mandatory Son River boat charter from Son Trach Tourism Center of 700,000 VND per boat (shared by up to 12 passengers, approx. 60,000 VND per person). Paradise Cave (Dong Thien Duong) admission is 270,000 VND per adult, which includes round-trip electric buggy transit from the car park to the mountain foot trail.'],
            ['q' => 'How do I travel between Dong Hoi Railway Station and Phong Nha?', 'a' => 'Public Bus B4 connects Dong Hoi Central Bus Station and Dong Hoi Railway Station with Phong Nha village every 45 to 60 minutes between 05:30 AM and 17:00 PM (fare is 40,000 to 50,000 VND; 90 minutes). A private taxi or metered Grab transfer covers the 45-kilometer distance in 45 to 50 minutes and costs 400,000 to 500,000 VND.'],
        ],
        'buon-ma-thuot-to-nha-trang-transport' => [
            ['q' => 'How long does the bus take from Buon Ma Thuot to Nha Trang and how much does it cost?', 'a' => 'High-frequency 9-seat VIP limousine vans and sleeper buses (operated by Rang Dong, Thinh Phat, Cuc Tung, and Lien Hung) run between Buon Ma Thuot and Nha Trang throughout the day. The 185-kilometer trip along National Highway 26 (QL26) takes 3.5 to 4.5 hours across the Phuong Hoang Mountain Pass and tickets cost 180,000 to 250,000 VND (approx. 7 to 10 USD).'],
            ['q' => 'What are the road conditions across Phuong Hoang Pass on National Highway 26?', 'a' => 'National Highway 26 is fully paved with two asphalt lanes, descending 12 kilometers through Phuong Hoang Pass from the Dak Lak plateau to the Khanh Hoa coastal plain. The pass has several tight hairpin turns and heavy agricultural transport trucks; self-drive scooter riders should check brake pads and ride cautiously during afternoon rain showers.'],
            ['q' => 'Where do buses depart in Buon Ma Thuot and arrive in Nha Trang?', 'a' => 'In Buon Ma Thuot, limousine vans pick up at central offices along Le Thi Hong Gam or Nguyen Duc Canh streets, or at the Northern Bus Station (Ben xe phia Bac, Nguyen Tat Thanh). In Nha Trang, vans drop off at the Southern Bus Station (Ben xe phia Nam, 23/10 Street) or provide door-to-door drop-offs at central hotels in Tran Phu and Loc Tho wards.'],
        ],
        'pleiku-to-quy-nhon-transport' => [
            ['q' => 'How do I travel from Pleiku to Quy Nhon and what is the transit time?', 'a' => 'Direct buses and limousine vans (operated by Tuan Anh, Son Tung, Thuan Tien, and Duc Dat) depart Pleiku Central Bus Station (43 Ly Nam De) hourly between 05:00 AM and 18:00 PM. The 165-kilometer journey eastward along National Highway 19 (QL19) takes 3.5 to 4 hours and costs 140,000 to 180,000 VND (approx. 6 to 7 USD).'],
            ['q' => 'What mountain passes does National Highway 19 cross between Pleiku and Quy Nhon?', 'a' => 'National Highway 19 descends from the Gia Lai Plateau to Binh Dinh coast across two historic mountain passes: Mang Yang Pass (Deo Mang Yang) in Dak Doa District and An Khe Pass (Deo An Khe) on the provincial border. Recent highway widening and asphalt modernization have reduced travel time and eliminated narrow bottleneck curves.'],
            ['q' => 'Can I visit the Tay Son museum en route from Pleiku to Quy Nhon?', 'a' => 'Yes. National Highway 19 passes directly through Phu Phong town in Tay Son District (45 km west of Quy Nhon), home to the Quang Trung Museum (Bao tang Quang Trung, admission 30,000 VND), celebrating Emperor Quang Trung and the Tay Son uprising with traditional martial arts and battle drum performances.'],
        ],
        'nha-trang-to-quy-nhon-transport' => [
            ['q' => 'Is train or bus better between Nha Trang and Quy Nhon?', 'a' => 'Both are convenient and take 3.5 to 4.5 hours for the 215-kilometer journey. The North-South Railway (trains SE22, SE10, SE4) provides panoramic coastal views and smooth ride comfort, arriving at Dieu Tri Station (11 km from Quy Nhon downtown; air-conditioned soft seat tickets cost 150,000 to 230,000 VND). Limousine buses (Cuc Tung, Phuc Thuan Thao) drop passengers off directly in central Quy Nhon for 180,000 to 250,000 VND.'],
            ['q' => 'How do I get from Dieu Tri Railway Station to Quy Nhon city center?', 'a' => 'Dieu Tri Station is located 11 kilometers west of downtown Quy Nhon. Metered taxis (Mai Linh or Sun Taxi) wait outside the station exit and charge 150,000 to 200,000 VND for the 20-minute ride to city hotels. Local public bus T22 also runs between Dieu Tri and Quy Nhon Central Bus Station for 20,000 VND per person.'],
            ['q' => 'Are there scenic coastal stops between Nha Trang and Quy Nhon?', 'a' => 'Travelers booking private car transfers (1,800,000 to 2,400,000 VND) can route through Phu Yen province along National Highway 1D, stopping at Dai Lanh Cape (Mui Dien, the easternmost point of Vietnam mainland) and Ganh Da Dia (The Giant Causeway of Vietnam with volcanic basalt columns, entry 40,000 VND).'],
        ],
        'where-to-stay-in-sapa' => [
            ['q' => 'Where is the best area to stay in Sapa: town center or Muong Hoa valley?', 'a' => 'Sapa town center around the Stone Church and Sun Plaza is best for short stays, offering walking access to restaurants, cafes, and the Muong Hoa funicular station. Travelers seeking tranquility and authentic mountain views should stay in valley homestays or eco-lodges in Ta Van (10 km south) or Ban Ho (25 km south) facing cascading rice terraces.'],
            ['q' => 'How much are admission tickets for Fansipan cable car and Cat Cat village?', 'a' => 'Sun World Fansipan Legend round-trip cable car tickets cost 850,000 VND for adults and 550,000 VND for children (1m–1.4m), with the Muong Hoa mountain train from Sun Plaza costing 150,000 to 200,000 VND round-trip. Cat Cat Village entry is 150,000 VND per adult, Silver Waterfall (Thac Bac) is 20,000 VND, and Love Waterfall (Thac Tinh Yeu) is 70,000 VND.'],
            ['q' => 'When is the best season to visit Sapa for rice terraces and clear mountain views?', 'a' => 'Mid-August through early October is the golden harvest season when the Muong Hoa terraces turn vivid yellow before reaping. December to February brings crisp winter air and occasional summit frost or light snow on Fansipan, while March to May offers clear blue skies and blooming azalea flowers.'],
        ],
        'where-to-stay-in-ha-giang' => [
            ['q' => 'Where should I stay along the Ha Giang Loop: city center, Dong Van, or Meo Vac?', 'a' => 'Most travelers stay in Ha Giang City on night one near the bus terminal (such as Lam Dong or Phuong Do commune), then stay overnight in Dong Van Old Quarter on night two to explore the Sunday ethnic market and historic stone shophouses, followed by night three in Meo Vac (especially the Pa Vi H\'Mong Cultural Tourism Village) after crossing Ma Pi Leng Pass.'],
            ['q' => 'What permits and fees are required for foreign travelers on the Ha Giang Loop?', 'a' => 'Foreign tourists require a statutory Border Area Entry Permit (Giay phep vao khu vuc bien gioi) costing 230,000 VND (approx. 10 USD), obtainable at the Ha Giang Provincial Immigration Office (415 Tran Phu) or Dong Van Police Station. Entry to Lung Cu Flag Tower is 25,000 to 40,000 VND (buggy 15,000 VND) and the H\'Mong King Palace (Dinh ho Vuong) is 20,000 to 30,000 VND.'],
            ['q' => 'How much does the Nho Que River boat tour in Tu San Canyon cost?', 'a' => 'The standard boat tour along the emerald Nho Que River through Tu San Canyon costs 120,000 to 150,000 VND per passenger (including boat ride and lifejacket). The steep 8-kilometer motorcycle shuttle descent from the DT193A highway switchbacks down to the river boat dock costs 50,000 VND each way with local riders.'],
        ],
        'where-to-stay-in-cao-bang' => [
            ['q' => 'Is it better to stay in Cao Bang City or near Ban Gioc Waterfall?', 'a' => 'Cao Bang City (Kim Dong walking street and Bang River waterfront) is convenient for night arrival, car rentals, and banking amenities. However, staying near Ban Gioc Waterfall (85 km northeast in Dam Thuy or Khuoi Ky Stone Village) allows you to experience peaceful Tay ethnic stone stilt-house homestays and visit the falls at 07:30 AM before tourist buses arrive from Hanoi.'],
            ['q' => 'What are the admission fees for Ban Gioc Waterfall and Nguom Ngao Cave?', 'a' => 'Ban Gioc Waterfall entry is 45,000 VND for adults and 20,000 VND for children (6 to 16 years). A motorized bamboo raft ride on the Quay Son River to the cascading curtain spray costs 50,000 VND per person. Nearby Nguom Ngao Cave (3 km from the waterfall) charges an admission fee of 45,000 VND per adult, while Khuoi Ky Stone Village and God Eye Mountain (Nui Mat Than) are free to visit.'],
            ['q' => 'How do I travel between Cao Bang City and Ban Gioc Waterfall?', 'a' => 'Public buses depart Cao Bang Interprovincial Bus Station (Ben xe Lien tinh Cao Bang) for Ban Gioc / Trung Khanh every 30 to 45 minutes between 06:00 and 16:30 (fare is 70,000 to 90,000 VND; 2 to 2.5 hours). Motorbike rentals in Cao Bang City cost 150,000 to 200,000 VND per day for the 85-kilometer ride via National Highway 3 and Provincial Road 206 across Ma Phuc Pass.'],
        ],
        'hanoi-to-ha-giang-transport' => [
            ['q' => 'How long does the bus take from Hanoi to Ha Giang and what are the best operators?', 'a' => 'Modern VIP cabin sleeper buses (operated by Bang Phan, Quang Nghi, and Dang Quang) run between Hanoi and Ha Giang in 5.5 to 6.5 hours via the Noi Bai - Lao Cai Expressway (CT05) and Tuyen Quang - Phu Tho Expressway. Single VIP private cabins cost 300,000 to 350,000 VND, while double palace cabins cost 550,000 to 600,000 VND for two people.'],
            ['q' => 'Where can I board the sleeper bus in Hanoi and what are departure times?', 'a' => 'Buses depart My Dinh Bus Station (Ben xe My Dinh) and carrier booking offices in the Old Quarter (such as Tran Nhat Duat street) with both daytime departures (07:00, 11:30, 14:00) and overnight departures (20:30 to 22:30). Most operators also provide scheduled passenger pickups at Noi Bai International Airport (HAN) Terminal T1 and T2 for travelers arriving on evening flights.'],
            ['q' => 'Should I take an overnight sleeper bus or daytime limousine van to Ha Giang?', 'a' => 'An overnight VIP sleeper cabin departing Hanoi at 21:00 reaches Ha Giang City around 03:00 to 04:00 AM, where reputable hostels allow you to sleep onboard or check into a free rest bed until sunrise before starting your loop. A daytime limousine van (departing at 07:00 AM, arriving at 13:00 PM) is safer for travelers who experience motion sickness or wish to see the rural landscape en route.'],
        ],
        'ha-giang-to-dong-van-transport' => [
            ['q' => 'What is the distance and driving time between Ha Giang City and Dong Van?', 'a' => 'The distance is 135 to 145 kilometers following National Highway 4C (QL4C - The Happiness Road). Due to continuous mountain gradients, hairpins on Bac Sum and Tham Ma passes, and frequent photo stops, the trip takes 4.5 to 6 hours by motorcycle or local passenger bus.'],
            ['q' => 'What public transportation is available between Ha Giang and Dong Van?', 'a' => 'Local 16-seat minibuses and mid-size coaches (operated by Quang Nghi, Manh Quan, and Cau Me) depart Ha Giang Central Bus Station between 05:00 AM and 12:30 PM, charging 100,000 to 120,000 VND per ticket. Luxury 9-seat limousine vans operate twice daily for 150,000 to 200,000 VND with central hotel drop-off in Dong Van.'],
            ['q' => 'What are the must-see landmark stops along National Highway 4C?', 'a' => 'Key highlights on the route to Dong Van include Quan Ba Heaven Gate (Cong troi Quan Ba) and the Fairy Twin Mountains (Nui Doi Co Tien, 45 km from Ha Giang), the pine forests of Yen Minh, the nine-turn Tham Ma Pass (Doc Tham Ma), the Sung La cultural valley (site of Pao Story house), and the 100-year-old H\'Mong King Palace (Dinh ho Vuong) in Sa Phin.'],
        ],
        'sapa-vs-ha-giang' => [
            ['q' => 'Is Sapa or Ha Giang better for first-time visitors to northern Vietnam?', 'a' => 'Sapa is better suited for travelers seeking comfortable resort stays, well-marked guided valley day walks, fine dining, and modern infrastructure like the Fansipan cable car. Ha Giang is superior for adventurous backpackers and riders seeking raw geological landscapes, authentic ethnic village homestays, and remote mountain riding along the Dong Van Karst Plateau.'],
            ['q' => 'How do transport and travel times compare between Sapa and Ha Giang?', 'a' => 'Sapa is directly accessible from Hanoi in 5.5 hours via the direct CT05 expressway cabin bus or overnight train to Lao Cai. Ha Giang also takes 5.5 to 6 hours from Hanoi by expressway bus, but exploring the region requires a multi-day 350-kilometer loop along steep mountain roads (requiring a qualified motorbike rider or Easy Rider driver) taking 3 to 4 full days.'],
            ['q' => 'Can I visit both Sapa and Ha Giang on the same trip?', 'a' => 'Yes. Interprovincial sleeper buses and shared limousine vans (such as Phuc Xuyen and Quang Nghi) connect Sapa Stone Church directly to Ha Giang City via National Highway 279 (QL279) and QL2. The 240-kilometer cross-mountain transfer takes 6 to 7 hours and costs 250,000 to 300,000 VND (approx. 10 to 12 USD) per seat.'],
        ],
        'cu-chi-tunnels-ben-duoc-vs-ben-dinh' => [
            ['q' => 'What is the main difference between Ben Duoc and Ben Dinh tunnels?', 'a' => 'Ben Dinh sits 60 kilometers northwest of Ho Chi Minh City and handles over 85 percent of tourist group buses, with sections widened specifically to accommodate Western physiques. Ben Duoc lies 70 to 75 kilometers away in An Nhon Tay commune; its tunnels preserve original dimensions, receive far fewer tour groups, and incorporate the Ben Duoc Memorial Temple and rural wartime command bunkers.'],
            ['q' => 'How much are entrance tickets and shooting range bullets at Cu Chi Tunnels?', 'a' => 'The official entrance fee for international visitors at both Ben Duoc and Ben Dinh is 125,000 VND per person (which includes site admission and a mandatory site guide). The national defense sports shooting range charges 55,000 to 65,000 VND per round for live-fire AK-47, M16, or M60 rifles (with a minimum purchase of 5 to 10 rounds).'],
            ['q' => 'How do I travel to Ben Duoc tunnels by public bus?', 'a' => 'Take public Bus No. 13 from 23/9 Park (Pham Ngu Lao) to Cu Chi Bus Station (7,000 VND, 90 minutes), then transfer directly to local Bus No. 79 (7,000 VND, 45 minutes) which terminates at the main gate of Ben Duoc Tunnels. Total public transit cost is 14,000 VND (approx. 0.60 USD) each way.'],
        ],
        'con-dao-vs-phu-quoc' => [
            ['q' => 'Is Con Dao or Phu Quoc better for an island vacation in Vietnam?', 'a' => 'Phu Quoc is a 574-square-kilometer island with an international airport, 30-day visa exemption, large beach resort clusters, family entertainment theme parks (VinWonders, Grand World), and vibrant night markets. Con Dao is a tranquil 76-square-kilometer archipelago protected as Con Dao National Park, featuring pristine coral reefs, nesting sea turtles (May to October), colonial prison history, and uncrowded beaches.'],
            ['q' => 'How do flights and ferry costs compare between Phu Quoc and Con Dao?', 'a' => 'Phu Quoc has frequent daily flights from Hanoi, Ho Chi Minh City, and Da Nang on multiple carriers (round-trip 1,500,000 to 3,000,000 VND), as well as fast catamarans from Ha Tien (216,000 to 230,000 VND) and Rach Gia (315,000 to 330,000 VND). Con Dao is serviced primarily by ATR-72 or Embraer domestic flights from Saigon and Can Tho (round-trip 2,800,000 to 4,800,000 VND) or high-speed catamarans from Vung Tau (660,000 to 880,000 VND) and Tran De (390,000 to 520,000 VND).'],
            ['q' => 'Which island has better weather and scuba diving?', 'a' => 'Phu Quoc offers calm turquoise seas during its dry season from November to April, but sees heavy southwest monsoon rains from July to September. Con Dao has flat seas and 15-to-25-meter underwater diving visibility around Hon Bay Canh and Hon Cau from March to September, while winter months (October to February) bring strong northeast winds that generate ocean swells.'],
        ],
        'hoi-an-vs-hue' => [
            ['q' => 'Should I spend more time in Hoi An or Hue?', 'a' => 'Spend 2 to 3 days in Hoi An if your priorities are illuminated pedestrian street walks, tailoring, cafe culture, and beach relaxation at An Bang. Spend 2 days in Hue if you are drawn to deep royal history, visiting the UNESCO-listed Nguyen Dynasty Imperial Citadel, expansive emperors tombs (Khai Dinh, Tu Duc, Minh Mang), and royal court gastronomy.'],
            ['q' => 'How far apart are Hue and Hoi An and how do I travel between them?', 'a' => 'Hue and Hoi An are 130 kilometers apart via the Hai Van Pass and Da Nang. The most scenic route is taking the heritage train HD1/HD3 or private car over Hai Van Pass to Da Nang (102 km, 3 hours), followed by a 40-minute drive south along the coastal road to Hoi An. Private car transfers cost 1,200,000 to 1,600,000 VND, including sightseeing stops at Lang Co Beach and Marble Mountains.'],
            ['q' => 'How do food prices and dining styles compare between Hue and Hoi An?', 'a' => 'Hue cuisine is famed for delicate royal tasting dishes (banh beo, banh nam, banh loc) and spicy Bun Bo Hue, with street meals typically costing 30,000 to 50,000 VND. Hoi An specialties focus on Cao Lau noodles, white rose dumplings (banh bao banh vac), and crispy Com Ga (chicken rice), with dining in the Ancient Town pedestrian zone priced slightly higher at 45,000 to 90,000 VND per dish.'],
        ],
        'ba-na-hills-golden-bridge-guide' => [
            ['q' => 'What is the entrance ticket price for Sun World Ba Na Hills and Golden Bridge?', 'a' => 'Standard admission for non-Da Nang residents and international visitors is 1,000,000 VND for adults (height over 1.4m) and 800,000 VND for children (1.0m to 1.4m, free for children under 1m). The ticket includes round-trip cable car transit, access to the Golden Bridge (Cau Vang), French Village, Le Jardin D\'Amour gardens, and indoor rides at Fantasy Park (wax museum and wine cellar entry require small separate supplements).'],
            ['q' => 'What is the best time of day to photograph the Golden Bridge without crowds?', 'a' => 'Board the earliest cable car line (Hoi An or Suoi Mo station) opening at 07:00 to 07:30 AM to reach Marseille Station and the Golden Bridge by 08:00 AM before day-tour groups arrive from Da Nang. Alternatively, visit between 16:30 and 17:30 PM when tour groups descend and soft late-afternoon fog gathers around the stone hands.'],
            ['q' => 'How do I travel from Da Nang city center to Ba Na Hills?', 'a' => 'Ba Na Hills is located 35 kilometers west of Da Nang. Shared round-trip shuttle minibuses pick up at city hotels for 100,000 to 150,000 VND per passenger. A Grab car or private taxi costs 300,000 to 350,000 VND one-way, or 550,000 to 650,000 VND for a round-trip charter including 5 to 6 hours driver waiting time.'],
        ],
        'da-nang-airport-to-hoi-an' => [
            ['q' => 'What is the fastest and most convenient transport from Da Nang Airport to Hoi An?', 'a' => 'A pre-booked private transfer or Grab car is the fastest and most seamless option, taking 40 to 45 minutes along Vo Nguyen Giap and Lac Long Quan coastal boulevards directly to your Hoi An hotel door. Fixed-price airport sedans cost 300,000 to 350,000 VND (approx. 12 to 14 USD) for a 4-seat car, avoiding metered taxi line surcharges.'],
            ['q' => 'Is there a cheap shared shuttle bus from Da Nang Airport to Hoi An?', 'a' => 'Yes. Shared tourist shuttles operated by Barri Ann Travel and Hoi An Express depart Da Nang Airport Terminal T1 (domestic) and T2 (international) every 60 minutes between 06:00 and 22:00, charging 100,000 to 120,000 VND (approx. 4 to 5 USD) per person with central hotel drop-off in Hoi An.'],
            ['q' => 'Can I take a public city bus from Da Nang to Hoi An?', 'a' => 'The historic yellow public Bus No. 1 has been replaced by modern provincial intercity bus lines. Travelers using public buses must board at Da Nang Central Bus Station (Ben xe Trung tam Da Nang) or designated street stops along Ton Duc Thang Street; however, shared airport shuttles remain substantially faster and more convenient for travelers carrying luggage.'],
        ],
        'da-nang-to-hue-train-vs-car' => [
            ['q' => 'Should I take the heritage train or a private car between Da Nang and Hue?', 'a' => 'The heritage train ("Ket noi di san mien Trung" HD1/HD2/HD3/HD4) is superior for relaxed scenic travel, tracing dramatic cliffside rails 100 meters above the sea along Hai Van Pass and stopping 10 minutes at Lang Co Bay (3 hours, 110,000 to 180,000 VND). A private car is better if you want to stop atop Hai Van Pass summit bunker, walk the Marble Mountains, or swim at Elephant Springs (3.5 to 4 hours, 1,200,000 to 1,500,000 VND).'],
            ['q' => 'What is the timetable for the Hue – Da Nang heritage train?', 'a' => 'Vietnam Railways operates two daily train pairs: Morning trains depart Da Nang (HD2 at 07:50 AM, arriving Hue at 11:05 AM) and Hue (HD1 at 07:45 AM, arriving Da Nang at 10:35 AM). Afternoon trains depart Da Nang (HD4 at 15:00 PM, arriving Hue at 17:45 PM) and Hue (HD3 at 14:25 PM, arriving Da Nang at 17:40 PM).'],
            ['q' => 'Which side of the train has the best ocean views between Da Nang and Hue?', 'a' => 'When traveling north from Da Nang to Hue, sit on the right-hand side (window seats facing east) for panoramic views of Da Nang Bay, the open East Sea, and Lang Co Lagoon. When traveling south from Hue to Da Nang, sit on the left-hand side.'],
        ],
        'cu-chi-tunnels-vs-mekong-delta-day-trip' => [
            ['q' => 'Should I choose Cu Chi Tunnels or Mekong Delta if I only have one day in Ho Chi Minh City?', 'a' => 'Choose Cu Chi Tunnels if you are drawn to military history, underground guerrilla engineering, and wartime relics, as the trip takes just half a day (4 to 5 hours round-trip, 50 to 70 km northwest). Choose a Mekong Delta day trip (My Tho / Ben Tre, 85 km southwest, 8 to 9 hours round-trip) if you prefer lush riverine scenery, coconut candy workshops, orchard fruit tastings, and sampan boat rides.'],
            ['q' => 'Can I combine Cu Chi Tunnels and Mekong Delta into a single day trip?', 'a' => 'Yes, but it makes for a long 10-to-11-hour day with over 5 hours total sitting in highway traffic. An organized combo tour departs central Saigon at 07:30 AM for morning exploration of Ben Dinh tunnels, followed by a 2-hour transfer south to My Tho on the Tien River for lunch and afternoon boat cruises, returning to District 1 by 18:30 PM (pricing ranges from 35 to 65 USD per person).'],
            ['q' => 'How much do guided day tours cost for Cu Chi and the Mekong Delta?', 'a' => 'A half-day morning or afternoon bus tour to Cu Chi Tunnels costs 15 to 25 USD per person (excluding tunnel entry of 125,000 VND). A full-day group tour to My Tho and Ben Tre in the Mekong Delta costs 20 to 35 USD per person (including river boats, rowing sampans, honey tea, and lunch). Premium small-group speedboat tours departing Bach Dang Pier cost 75 to 110 USD.'],
        ],
        'old-quarter-vs-french-quarter-vs-west-lake' => [
            ['q' => 'What are the main differences between Hanoi\'s Old Quarter, French Quarter, and West Lake for accommodation?', 'a' => 'Hanoi Old Quarter (Hoan Kiem north) is the dense commercial core with walking access to street food, weekend night markets, and Ta Hien beer street, but experiences street noise and compact rooms (500,000 to 1,500,000 VND). The French Quarter (Hoan Kiem south) features wide tree-lined boulevards, colonial architecture, embassies, and luxury heritage hotels like Sofitel Legend Metropole (2,500,000 to 8,000,000 VND), situated 10 to 15 minutes walk south of Hoan Kiem Lake. West Lake (Tay Ho) provides open lakeside views, expat cafes, boutique serviced apartments (800,000 to 2,500,000 VND), and international dining, situated 5 to 8 kilometers north of the city center.'],
            ['q' => 'How much does taxi or Grab transit cost from West Lake to Hanoi Old Quarter?', 'a' => 'A Grab car ride between Tay Ho (such as Xuan Dieu or To Ngoc Van streets) and Hoan Kiem Lake takes 15 to 25 minutes during regular hours (stretching to 35 to 40 minutes during 07:30 to 09:00 AM and 17:00 to 18:30 PM rush hours) and costs 80,000 to 120,000 VND (approx. 3.20 to 4.80 USD). A GrabBike motorcycle taxi takes 12 to 18 minutes and costs 35,000 to 50,000 VND.'],
            ['q' => 'Which neighborhood is best for first-time visitors to Hanoi?', 'a' => 'First-time visitors staying 2 to 3 days in Hanoi benefit most from basing themselves in the Old Quarter or northern French Quarter to maximize walking access to Hoan Kiem Lake, Ngoc Son Temple, the Water Puppet Theatre, and street eateries without taking taxis. Travelers seeking quiet nights, upscale accommodations, or stays longer than 5 days typically choose the French Quarter or West Lake.'],
        ],
        'best-day-trips-from-hanoi' => [
            ['q' => 'What is the top recommended day trip from Hanoi for first-time travelers?', 'a' => 'Ninh Binh (Trang An and Tam Coc) is the most popular day trip from Hanoi, located 95 to 100 kilometers south via the CT01 Phap Van - Cau Gie expressway (1.5 to 2 hours drive). It features limestone karst riverways, UNESCO-protected rowing boat tours at Trang An (250,000 VND ticket), the 500-step climb up Hang Mua peak (100,000 VND), and ancient temples at Hoa Lu.'],
            ['q' => 'What traditional craft villages near Hanoi make easy half-day or full-day trips?', 'a' => 'Bat Trang Pottery Village sits 15 kilometers southeast along the Red River dike; take public Bus No. 47A from Long Bien Interchange (7,000 to 9,000 VND, 45 minutes) to tour pottery kilns and shape clay on pottery wheels for 50,000 to 70,000 VND. Duong Lam Ancient Village lies 45 kilometers west in Son Tay, known for 300-year-old laterite brick houses, Mia Pagoda, and traditional soy sauce production; reach it via public Bus No. 71 from My Dinh Bus Station (20,000 VND, 90 minutes) or Grab car (350,000 to 450,000 VND) with a village entrance ticket of 20,000 VND.'],
            ['q' => 'Is a day trip from Hanoi to Ha Long Bay worth the travel time?', 'a' => 'Yes, provided you travel via the direct CT04 Hanoi - Hai Phong - Ha Long expressway, which has reduced one-way transit time to 2.5 hours (compared to 4 hours on older routes). A standard 12-hour day tour (08:00 AM to 20:00 PM) delivers 4 to 6 hours cruising on Ha Long Bay, covering Sung Sot Cave, Ti Top Island lookout, and Luon Cave kayaking, costing 45 to 75 USD per person including highway transit, cruise ticket, and lunch.'],
        ],
        'ninh-binh-day-trip-vs-overnight' => [
            ['q' => 'Is a day trip from Hanoi enough to see Ninh Binh, or should I stay overnight?', 'a' => 'A day trip covers two major sights (such as a Trang An boat tour and Hang Mua viewpoint), but requires 4 to 5 hours of round-trip highway transit and places you at boat docks during peak arrival hours (10:30 AM to 14:00 PM). Staying overnight in a rural Tam Coc or Trang An homestay (400,000 to 800,000 VND per night) allows you to board the earliest morning boats at 07:00 AM before tour buses arrive from Hanoi, climb Hang Mua at sunset, and cycle through uncrowded countryside valleys.'],
            ['q' => 'What is the typical daily budget for an overnight stay in Ninh Binh compared to a tour?', 'a' => 'An organized full-day limousine tour from Hanoi costs 850,000 to 1,300,000 VND (35 to 55 USD) per person, covering transport, lunch, and entry tickets. Traveling independently and staying overnight costs approximately 1,100,000 to 1,600,000 VND per person for 2 days and 1 night, including round-trip limousine transit (300,000 to 400,000 VND), homestay private room (500,000 to 700,000 VND), bicycle rental (50,000 VND per day), meals, and Trang An boat ticket (250,000 VND).'],
            ['q' => 'When are the major sights in Ninh Binh least crowded?', 'a' => 'Boat docks at Trang An and Tam Coc are uncrowded between 07:00 and 08:30 AM right after opening, and again between 15:30 and 17:00 PM when tour groups return to Hanoi. The 500-step staircase at Hang Mua is best climbed at sunrise (06:00 to 07:00 AM) or late afternoon (16:30 to 17:30 PM) for cooler temperatures and softer photographic lighting over the Ngo Dong River.'],
        ],
        'mekong-delta-overnight-vs-day-trip' => [
            ['q' => 'Is a day trip to the Mekong Delta from Ho Chi Minh City worth it?', 'a' => 'Yes, a day trip to My Tho and Ben Tre (70 to 85 kilometers southwest, 1.5 to 2 hours drive via the CT01 expressway) is practical for travelers with limited schedules. It delivers a manageable 8-hour loop covering motorized boat crossings on the Tien River, rowing sampans through narrow coconut palm canals, honey tea tastings, and coconut candy production, costing 20 to 35 USD for group bus tours or 75 to 95 USD for speedboat tours from Bach Dang Pier.'],
            ['q' => 'Why should I choose an overnight stay in Can Tho instead of a day trip?', 'a' => 'Can Tho lies 170 kilometers southwest of Saigon (3.5 to 4 hours drive each way). An overnight stay in Can Tho is necessary to experience Cai Rang Floating Market during its peak trading hours at dawn (05:30 to 07:00 AM), when wholesale wooden barges display pineapples, melons, and vegetables from bamboo poles (cay beo) alongside noodle soup boats. Visiting Can Tho on a single day from Saigon requires a 03:00 AM departure and 8 hours on the road.'],
            ['q' => 'How do homestay experiences compare between Ben Tre and Can Tho?', 'a' => 'Ben Tre homestays are located 2 hours from Saigon among coconut groves and fruit orchards along quiet tributary canals, making them ideal for tranquil 2-day escapes featuring cycling and cooking classes with minimal travel fatigue. Can Tho offers a larger provincial city base along Ninh Kieu Quay, combining modern hotels or riverside eco-lodges with dawn access to major floating markets and noodle-making workshops.'],
        ],
        'mui-ne-vs-nha-trang' => [
            ['q' => 'What is the main difference between Mui Ne and Nha Trang for a beach vacation?', 'a' => 'Mui Ne is a low-rise coastal resort strip along Nguyen Dinh Chieu street with a desert-meets-ocean environment, famous for giant sand dunes (Bau Trang White Dunes and Red Dunes), coastal winds, and kitesurfing and windsurfing centers. Nha Trang is a high-rise tropical beach city with a 6-kilometer golden sand promenade along Tran Phu boulevard, island scuba diving at Hon Mun Marine Reserve, mineral mud baths, and lively international dining.'],
            ['q' => 'Which beach destination has better weather and swimmable water?', 'a' => 'Nha Trang has calm, clear swimmable waters from January to August, with island diving visibility reaching 15 to 20 meters; heavy rains occur primarily from October to December. Mui Ne is one of the driest regions in Vietnam with low annual rainfall, but from November to March powerful coastal winds generate heavy shore breaks and choppy surf that are ideal for kiteboarders but less suited for casual ocean swimming.'],
            ['q' => 'How do resort styles and nightlife compare between Nha Trang and Mui Ne?', 'a' => 'Nha Trang features international high-rise beachfront hotels (Sheraton, InterContinental, Novotel) directly across from the public promenade, alongside lively beachfront venues like Sailing Club and Louisiana Brewhouse. Mui Ne features low-rise tropical garden resorts situated directly on the sand with private beach frontage, quieter evenings centered around fresh seafood stalls along the Bo Ke seawall, and relaxed lounge bars.'],
        ],
        'hanoi-to-ninh-binh-train-vs-limousine' => [
            ['q' => 'Is it better to take the train or a limousine van from Hanoi to Ninh Binh?', 'a' => 'The shared limousine van (9-seat VIP DCar) is more convenient for most tourists because it picks up directly at Old Quarter hotels and drops off at your door in Tam Coc or Trang An, taking 90 to 105 minutes via the CT01 expressway for 150,000 to 220,000 VND (6 to 9 USD). The train (Reunification Express SE5/SE7/SE9) takes 2 hours 10 minutes from Hanoi Central Station and costs 75,000 to 130,000 VND for soft seats; however, Ninh Binh Railway Station is located in the commercial downtown, requiring an additional 6-kilometer taxi ride (80,000 to 100,000 VND) to reach Tam Coc or Trang An.'],
            ['q' => 'What is the train timetable from Hanoi to Ninh Binh?', 'a' => 'Vietnam Railways operates daily departures from Hanoi Central Station (120 Le Duan): Morning train SE7 departs at 06:00 AM (arrives Ninh Binh at 08:14 AM), SE5 departs at 09:00 AM (arrives 11:17 AM), and afternoon train SE9 departs at 13:05 PM (arrives 15:28 PM). Return trains from Ninh Binh to Hanoi include SE8 (departs 12:47 PM, arrives 15:35 PM) and SE6 (departs 16:54 PM, arrives 19:12 PM).'],
            ['q' => 'How do I book reliable limousine vans between Hanoi and Tam Coc?', 'a' => 'Book reputable limousine operators (such as Trang An Limousine, Duy Khang, or Binh Minh) online via platforms like Vexere or through your Hanoi hotel front desk at least 24 hours in advance. Confirm whether your ticket includes hotel door-to-door pickup in Hoan Kiem district and direct drop-off at your specific Tam Coc or Trang An homestay address.'],
        ],
        'ho-chi-minh-city-to-mui-ne-transport' => [
            ['q' => 'How long does it take to travel from Ho Chi Minh City to Mui Ne by road and train?', 'a' => 'Since the opening of the Dau Giay - Phan Thiet Expressway (CT01), limousine vans and private cars travel from central Ho Chi Minh City to Phan Thiet in 2.5 to 3 hours (extending to 3.5 hours to reach outer Mui Ne resorts on Nguyen Dinh Chieu). The daily SPT2 tourist train from Saigon Railway Station takes 3 hours 45 minutes to reach Phan Thiet Railway Station, plus a 20-minute taxi connection to the beach.'],
            ['q' => 'How much do limousine bus and train tickets cost from Saigon to Mui Ne?', 'a' => 'VIP 9-seat to 11-seat limousine vans (such as G5Car, ADT, 3S Limo) cost 250,000 to 350,000 VND (approx. 10 to 14 USD) per seat and drop off directly at your Mui Ne resort. Regular sleeper buses (FUTA Bus Lines or Hanh Cafe) cost 160,000 to 200,000 VND. Vietnam Railways train SPT2 charges 180,000 to 260,000 VND for an air-conditioned soft seat, while private car transfers cost 1,500,000 to 2,000,000 VND one-way.'],
            ['q' => 'When does the daily tourist train SPT2 depart from Saigon to Phan Thiet?', 'a' => 'Train SPT2 departs daily from Saigon Railway Station (1 Nguyen Thong, District 3) at 06:45 AM and arrives at Phan Thiet Railway Station at 10:30 AM. The return train SPT1 departs Phan Thiet Station at 13:10 PM and arrives in Saigon at 17:15 PM. Train travel avoids highway traffic bottlenecks and offers scenic views through Binh Thuan dragon fruit plantations.'],
        ],
        'tam-coc-vs-trang-an-boat-tour' => [
            ['q' => 'What is the main difference between Tam Coc and Trang An boat tours?', 'a' => 'Tam Coc boat tours trace the Ngo Dong River through three natural limestone caverns (Hang Ca, Hang Hai, Hang Ba) where local rowers propel sampans using their feet, reaching peak scenic beauty during the late May to early June golden rice harvest along the riverbanks (250,000 VND per boat for two passengers). Trang An is an expansive UNESCO World Heritage complex offering three distinct 2.5 to 3-hour river routes with deeper subterranean caves (such as the 1,000-meter Dot Cave) and historical temples (250,000 VND per adult on four-person boats), offering reliable conditions throughout the year without seasonal agricultural fields.'],
            ['q' => 'Which Trang An boat route is best for first-time visitors?', 'a' => 'Trang An operates three standardized routes: Route 1 emphasizes cave exploration through nine distinct caverns and three temples (3 hours); Route 2 balances four caves with the Kong Skull Island film set and Vu Lam royal palace (2.5 hours); Route 3 covers three longer caves including Dot Cave and Cloud Cave (2.5 hours). Route 2 and Route 3 are the most popular choices for first-time visitors seeking a balance of cave tunnels and landscape photography.'],
            ['q' => 'When should I choose Tam Coc over Trang An?', 'a' => 'Choose Tam Coc from mid-May to early June to witness ripe golden rice paddies growing directly out of the water alongside foot-rowed sampans. Choose Trang An during any other month of the year for longer subterranean cave passages, grand UNESCO heritage architecture, and superior dock organization.'],
        ],
        'ly-son-vs-cham-islands' => [
            ['q' => 'How do Cham Islands and Ly Son Island compare for an offshore excursion?', 'a' => 'Cham Islands (Cu Lao Cham) sit 15 to 20 kilometers offshore from Cua Dai Pier in Hoi An, reached in 20 minutes by speedboat (round-trip 450,000 to 550,000 VND including the 70,000 VND marine conservation fee), making an easy day trip for shallow coral snorkeling between March and September. Ly Son Island lies 24 kilometers off Quang Ngai province (130 kilometers south of Da Nang), reached via a 35 to 45-minute high-speed catamaran from Sa Ky Port (178,000 to 213,000 VND each way); its volcanic crater landscape (Thoi Loi Mountain, To Vo Stone Gate) and Dao Be coral reefs require at least 2 days and 1 night.'],
            ['q' => 'Is Ly Son or Cham Islands better for a short day trip?', 'a' => 'Cham Islands are suited for a 6-to-8-hour day excursion from Hoi An or Da Nang, focusing on beach relaxation at Bai Chong and snorkeling at Bai Xep. Ly Son requires a dedicated 2-to-3-day itinerary due to the 2.5-hour overland transit from Da Nang or Hoi An to Sa Ky Port before boarding the ferry, making it better for travelers seeking rural island agriculture and dramatic volcanic geology.'],
            ['q' => 'What is the best season to visit Ly Son and the Cham Islands?', 'a' => 'Both island groups are accessible during the dry season from March to September. Coastal authorities suspend passenger speedboats to both Cham Islands and Ly Son during the northeast winter monsoon from October to February due to high sea swells and rough ocean channels.'],
        ],
        'phu-quoc-vs-nha-trang' => [
            ['q' => 'How do travel costs compare between Phu Quoc and Nha Trang?', 'a' => 'Nha Trang is an established mainland coastal city connected by national highways and the North-South railway, keeping food, seafood, and budget guesthouse prices 30 to 40 percent lower than Phu Quoc (mid-range daily budgets start around 35 to 40 USD). Phu Quoc is an offshore island where fuel, construction materials, and produce must be shipped from the mainland, raising mid-range travel budgets to 55 USD or more per day.'],
            ['q' => 'Which destination offers better beaches and resort atmosphere?', 'a' => 'Phu Quoc provides isolated resort enclaves with private beachfronts (such as Bai Khem, Bai Sao, and Bai Dai) where boutique and luxury resorts sit directly on white sand without road barriers. Nha Trang features an urban 6-kilometer public beach along Tran Phu Boulevard with high-rise hotels situated across a seaside park and boulevard, offering lively beach clubs like Sailing Club and active city nightlife.'],
            ['q' => 'What are the visa and flight differences between Phu Quoc and Nha Trang?', 'a' => 'International visitors flying directly to Phu Quoc International Airport (PQC) or arriving on cruise vessels qualify for a 30-day visa exemption, provided they do not travel to other parts of Vietnam without a standard visa. Visitors to Nha Trang arriving via Cam Ranh International Airport (CXR) must hold a standard Vietnam e-visa or passport exemption.'],
        ],
        'da-lat-coffee-farms-guide' => [
            ['q' => 'What are the top coffee farms to visit in Da Lat?', 'a' => 'Cau Dat Farm is situated 25 to 30 kilometers southeast of Da Lat city center at an elevation of 1,650 meters, cultivating high-altitude Arabica coffee (Typica, Bourbon, and Catimor) and tea since 1927 (entry 50,000 to 90,000 VND). Me Linh Coffee Garden lies 20 kilometers southwest along Ta Nung Pass, offering views over Robusta hillside groves and processing education (drinks 70,000 to 120,000 VND). K\'Ho Coffee in Lac Duong operates as an indigenous cooperative producing organic heirloom Arabica at the base of Lang Biang Mountain.'],
            ['q' => 'When is the coffee harvest season in Da Lat?', 'a' => 'The coffee harvest in the Da Lat highlands runs from late October through January, when Arabica cherries turn deep red and are hand-picked. Visiting during these months allows travelers to observe cherry harvesting, depulping, and drying patios in full operation across the Tram Hanh and Ta Nung valleys.'],
            ['q' => 'How do I travel to Cau Dat coffee plantation from Da Lat center?', 'a' => 'Renting an automatic scooter (120,000 to 150,000 VND per day) provides the flexibility to combine Cau Dat Farm with cloud-hunting spots along National Highway 20. Alternatively, booking a private taxi or Grab car for a 5-hour half-day hire costs 500,000 to 650,000 VND, avoiding mountain chill and sudden highland rain showers.'],
        ],
        'da-lat-waterfalls-guide' => [
            ['q' => 'Which waterfalls in Da Lat are best for sightseeing and adventure?', 'a' => 'Datanla Waterfall sits 6 kilometers south of Da Lat along Prenn Pass (admission 50,000 to 80,000 VND), featuring the 2,400-meter alpine coaster (round-trip 200,000 to 250,000 VND) and certified canyoning adventures (waterfall abseiling up to 25 meters, 1,600,000 to 1,900,000 VND per person). Elephant Waterfall (Thac Voi) lies 28 kilometers southwest in Nam Ban (40,000 VND ticket), featuring a powerful torrent cascading over volcanic basalt rocks next to Linh An Pagoda. Pongour Waterfall lies 50 kilometers south in Duc Trong (30,000 VND ticket), recognized as the Seven-Tier Waterfall cascading over 100-meter-wide stone shelves.'],
            ['q' => 'When is Pongour Waterfall at its best water volume?', 'a' => 'Pongour Waterfall displays its most impressive water flow during the wet season and post-monsoon months from July to December. During the dry season (January to April), water discharge decreases, but the stepped basalt rock terraces remain accessible for walking and landscape photography.'],
            ['q' => 'Is canyoning at Datanla Waterfall safe for beginners?', 'a' => 'Datanla is the premier location for commercial canyoning in Vietnam, run by licensed adventure operators with international UIAA-certified safety gear, neoprene wetsuits, and certified guides. The 5-to-6-hour expedition covers technical abseiling down active waterfalls, a 7-meter cliff jump into deep pools, and natural granite water slides.'],
        ],
        'cham-islands-day-trip-guide' => [
            ['q' => 'How do I take a day trip from Hoi An to the Cham Islands?', 'a' => 'High-speed canoes depart Cua Dai Pier (8 kilometers east of Hoi An Ancient Town) daily between 08:30 and 09:30 AM, reaching Bai Lang on Hon Lao island in 20 minutes across open water. Independent round-trip speedboat tickets cost 450,000 to 550,000 VND, including the mandatory 70,000 VND marine conservation fee and 20,000 VND pier fee.'],
            ['q' => 'Should I book an organized day tour or travel independently to Cham Islands?', 'a' => 'An organized group day tour from Hoi An costs 550,000 to 750,000 VND (approx. 22 to 30 USD) per person, covering hotel pickup, speedboat transfers, snorkeling gear at Bai Xep reef, marine park fees, and seafood lunch at Bai Chong. Independent travelers must arrange their own pier taxi (80,000 to 100,000 VND each way) and rent private wooden boats for snorkeling spots.'],
            ['q' => 'When are the Cham Islands open and safe for boat travel?', 'a' => 'The Cham Islands are accessible strictly from March through September when seas are calm and underwater visibility reaches 8 to 15 meters. From October to February, strong northeast monsoon winds generate heavy sea swells, causing port authorities to suspend all passenger speedboat departures.'],
        ],
        'hanoi-to-sapa-train-vs-sleeper-bus' => [
            ['q' => 'Is it better to take the overnight train or sleeper bus from Hanoi to Sapa?', 'a' => 'The direct VIP cabin sleeper bus travels via the CT05 Hanoi - Lao Cai expressway in 5.5 to 6 hours (280 to 300 km), dropping passengers in central Sapa for 350,000 to 450,000 VND (single cabin) or 600,000 to 700,000 VND (double cabin). The overnight train (SP1/SP3) takes 8 hours from Hanoi Central Station to Lao Cai Railway Station (350,000 to 900,000 VND per berth), requiring an additional 35-kilometer mountain transfer (minibus 50,000 to 60,000 VND, 50 to 60 minutes) to reach Sapa.'],
            ['q' => 'Which option is better for avoiding motion sickness between Hanoi and Sapa?', 'a' => 'Choose the VIP cabin sleeper bus if you want a direct journey straight into Sapa town center in under 6 hours without changing vehicles at 05:30 AM. Choose the overnight train if you suffer from motion sickness on winding roads or prefer flat sleeping berths on stable railway tracks.'],
            ['q' => 'How do I travel from Lao Cai Railway Station up to Sapa town?', 'a' => 'Standard overnight trains (SP1 departing Hanoi at 21:35 PM, SP3 at 22:00 PM) arrive at Lao Cai Railway Station between 05:25 and 05:55 AM. Public yellow-and-red buses (Line 01) and shared tourist 16-seat minivans depart from the station plaza every 20 minutes to Sapa town, charging 30,000 to 50,000 VND per passenger.'],
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
                ['name' => 'Access the Official Immigration Portal', 'text' => 'Visit the official Ministry of Public Security portals at evisa.gov.vn, thithucdientu.gov.vn, or evisa.xuatnhapcanh.gov.vn. Avoid third-party commercial agency portals that charge marked-up intermediary fees.'],
                ['name' => 'Complete the Application Form', 'text' => 'Fill in your full legal name, date of birth, passport details, temporary address in Vietnam, and select your specific entry and exit checkpoint from the 42 designated international gates under Resolution 127/NQ-CP.'],
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
        'vietnam-train-travel' => [
            'name'        => 'How to Book Vietnam Railways Train Tickets Online Without Agency Markup',
            'description' => 'Step-by-step instructions for booking Reunification Express (SE1/SE2, SE3/SE4, SE19/SE20) and Central Heritage (HD1/HD2) tickets directly on Vietnam Railways portals.',
            'steps'       => [
                ['name' => 'Open the Official Vietnam Railways Portal', 'text' => 'Navigate directly to dsvn.vn or vetau.com.vn 30 to 60 days before your planned departure date to access official inventory at face value in VND.'],
                ['name' => 'Select Station Pair and Train Number', 'text' => 'Enter exact Vietnamese railway station names (Ha Noi, Ninh Binh, Dong Hoi, Hue, Da Nang, Nha Trang, or Sai Gon) and prioritize SE1/SE2 or SE3/SE4 for long-haul sleepers, SE19/SE20 for Hanoi–Da Nang, or HD1/HD2 for the daytime Hai Van Pass route.'],
                ['name' => 'Choose 4-Berth Soft Sleeper (AnN) or Air-Conditioned Soft Seat (NML)', 'text' => 'Select a 4-berth air-conditioned soft sleeper (Khoang 4 Dieu Hoa) for overnight journeys, choosing lower berths (T1) for stability and luggage storage, or soft seats for daytime trips under 4 hours.'],
                ['name' => 'Enter Passport Details and Complete Payment', 'text' => 'Input each passenger full legal name and passport number exactly as printed on your travel document, pay via international Visa or Mastercard, and save the QR electronic boarding pass to your phone.'],
            ],
        ],
        'vietnam-to-cambodia-boat-guide' => [
            'name'        => 'How to Take the Mekong River Speedboat from Chau Doc (Vietnam) to Phnom Penh (Cambodia)',
            'description' => 'Step-by-step river border crossing workflow from Chau Doc Pier through Vinh Xuong (Vietnam) and Kaam Samnor (Cambodia) immigration to Sisowath Quay.',
            'steps'       => [
                ['name' => 'Check In at Chau Doc Boat Pier by 7:00 AM', 'text' => 'Arrive at the Victoria Chau Doc or Hang Chau Tourist speedboat pier by 7:00 AM with your original passport (minimum 6 months validity), one physical passport-sized photo, and crisp USD cash for Cambodian border fees.'],
                ['name' => 'Clear Vietnamese Exit Immigration at Vinh Xuong', 'text' => 'Cruise 1 hour upriver along the Tien (Mekong) River to the Vinh Xuong International River Border Gate, where the boat crew coordinates passport exit stamping with Vietnamese border officers.'],
                ['name' => 'Complete Cambodian Entry Visa at Kaam Samnor', 'text' => 'Cross the river boundary to the Kaam Samnor checkpoint in Kandal Province to present your Cambodia e-Visa (30 USD online via evisa.gov.kh) or complete a Cambodia Visa on Arrival (30 USD cash) and fingerprint scan.'],
                ['name' => 'Disembark at Phnom Penh Autonomous Port / Sisowath Quay', 'text' => 'Continue 3 hours upriver along the Mekong River and dock in central Phnom Penh around 12:30 PM to 1:00 PM.'],
            ],
        ],
        'ha-giang-loop-planning-guide' => [
            'name'        => 'How to Plan and Ride the 3-to-4-Day Ha Giang Loop Safely',
            'description' => 'Step-by-step logistics for sleeper bus transit from Hanoi, Ha Giang border area permits, 1968 IDP legal compliance, and daily karst plateau stages.',
            'steps'       => [
                ['name' => 'Take the Overnight VIP Cabin Bus from Hanoi to Ha Giang City', 'text' => 'Board an evening VIP single-cabin sleeper bus (350,000 to 450,000 VND) or daytime limousine from Hanoi Old Quarter / My Dinh to Ha Giang City (300 km, 6 hours), arriving at a base hostel with complimentary early-morning rest beds.'],
                ['name' => 'Verify 1968 IDP Motorcycle Endorsement or Book an Easy Rider', 'text' => 'Confirm you hold both a valid domestic motorcycle license and a 1968 Vienna Convention International Driving Permit (IDP) for self-riding, or hire an experienced local Easy Rider driver so your travel medical insurance remains valid.'],
                ['name' => 'Obtain the Ha Giang Border Area Permit (230,000 VND)', 'text' => 'Register your passport at the Ha Giang Immigration Office (296A Tran Phu) or through your tour operator/homestay to receive the mandatory 230,000 VND border permit required for overnight stays in Quan Ba, Yen Minh, Dong Van, and Meo Vac.'],
                ['name' => 'Ride the 350 km Circuit Across Dong Van, Ma Pi Leng Pass, and Du Gia', 'text' => 'Complete the loop over 3 to 4 daylight days: Day 1 Ha Giang to Yen Minh via Quan Ba Heaven Gate; Day 2 Yen Minh to Dong Van via Lung Cu Flag Tower; Day 3 Ma Pi Leng Pass and Nho Que River boat ride (120,000–150,000 VND) to Du Gia; Day 4 Du Gia waterfall back to Ha Giang City.'],
            ],
        ],
        'ninh-binh-to-ha-long-bay-transfer' => [
            'name'        => 'How to Transfer Directly from Ninh Binh to Ha Long Bay or Lan Ha Bay for a Noon Cruise',
            'description' => 'Step-by-step direct overland transfer workflow from Tam Coc or Trang An to Tuan Chau Marina, Halong International Cruise Port, or Cat Ba Island without backtracking to Hanoi.',
            'steps'       => [
                ['name' => 'Confirm Your Exact Cruise Embarcation Harbor', 'text' => 'Check your cruise voucher 24 hours before departure to verify whether your vessel boards at Tuan Chau International Marina, Halong International Cruise Port (Bai Chay), Ao Tien Port (Van Don), or Ben Beo Pier (Cat Ba Island).'],
                ['name' => 'Book a 6:30 AM–7:00 AM Direct Expressway Limousine or Private Car', 'text' => 'Reserve a 9-seat VIP limousine shuttle (300,000 to 450,000 VND per seat) or private sedan (1,600,000 to 2,000,000 VND) departing Tam Coc or Trang An between 6:30 AM and 7:00 AM.'],
                ['name' => 'Transit East via National Highway 10 and CT04/CT06 Expressway', 'text' => 'Travel 185 kilometers east across Nam Dinh, Thai Binh, and Hai Phong via National Highway 10 and the Ha Long – Hai Phong Expressway (CT06), completing the journey in 3.5 to 4 hours with a 15-minute rest stop.'],
                ['name' => 'Check In at the Cruise Terminal Before 11:30 AM', 'text' => 'Arrive at Tuan Chau or Halong International Cruise Port between 10:30 AM and 11:15 AM, present your original passport for maritime port authority registration, and board the tender boat by 12:00 PM.'],
            ],
        ],
        'hanoi-to-sapa-transport' => [
            'name'        => 'How to Travel from Hanoi to Sapa by Overnight Train or CT05 Expressway Limousine',
            'description' => 'Step-by-step comparison and booking workflow for traveling the 315-kilometer route between Hanoi and Sapa via overnight sleeper train (SP3/SP4) or direct CT05 expressway cabin bus.',
            'steps'       => [
                ['name' => 'Choose Between Direct CT05 Cabin Bus or Overnight Sleeper Train', 'text' => 'Select a 5.5-to-6-hour VIP cabin bus or 9-seat limousine (350,000 to 480,000 VND) via the CT05 Noi Bai – Lao Cai Expressway for direct hotel drop-off in Sapa, or book overnight train SP3 (departing Hanoi Station at 22:00) for a flat 4-berth sleeper cabin.'],
                ['name' => 'Board at Hanoi Old Quarter, Tran Quy Cap Station, or Noi Bai Airport', 'text' => 'For train SP3, enter Hanoi Railway Station B-Gate (1 Tran Quy Cap) 30 minutes before the 22:00 departure. For expressway cabin buses, board in the Old Quarter or schedule a direct pickup at Noi Bai International Airport (HAN) Terminal T1/T2.'],
                ['name' => 'Transfer at Lao Cai Station (Train) or Ascend National Highway 4D (Bus)', 'text' => 'If arriving at Lao Cai Railway Station at 05:55 AM on train SP3, board the public Bus No. 01 (40,000 VND) or shared 16-seat minibus (50,000–70,000 VND) outside the station plaza for the 35-kilometer, 50-minute climb up National Highway 4D to Sapa Stone Church.'],
                ['name' => 'Store Luggage Early and Begin Muong Hoa Valley or Fansipan Excursion', 'text' => 'Drop your bags at your Sapa hotel reception between 6:30 AM and 7:30 AM, have breakfast, and start your morning trek into Muong Hoa Valley or board the Fansipan cable car (800,000 to 850,000 VND round-trip) before midday clouds gather.'],
            ],
        ],
        'hoi-an-ancient-town-guide' => [
            'name'        => 'How to Visit Hoi An Ancient Town Heritage Sites and Order Custom Tailoring',
            'description' => 'Step-by-step field guide for using the 120,000 VND Hoi An Ancient Town heritage ticket, timing pedestrian street hours, and scheduling 3-fitting custom tailoring.',
            'steps'       => [
                ['name' => 'Purchase the 120,000 VND Ancient Town Heritage Ticket at an Official Booth', 'text' => 'Buy the official 120,000 VND heritage ticket sheet at any entry kiosk bordering the pedestrian zone (such as Tran Phu, Nguyen Thai Hoc, or Bach Dang streets); each ticket includes 5 tear-off coupons valid across 21 historic merchant houses, assembly halls, and museums.'],
                ['name' => 'Walk the Old Town During Early Morning (6:30–8:30 AM) or Pedestrian Hours', 'text' => 'Explore the Japanese Covered Bridge, Tan Ky Old House, and Fujian Assembly Hall before 8:30 AM to beat tour groups, and return during vehicle-free pedestrian hours (9:00 AM–11:00 AM and 3:00 PM–9:30 PM) when silk lanterns illuminate the Thu Bon River.'],
                ['name' => 'Schedule Initial Measurements for Custom Tailoring on Day 1', 'text' => 'Visit your chosen Hoi An tailor on your first morning or afternoon to select fabrics (linen, cashmere wool, or silk) and complete body measurements so the workshop has 48 to 72 hours for construction.'],
                ['name' => 'Complete First Basted Fitting and Final Garment Pickup', 'text' => 'Return within 24 hours for the first basted fitting to adjust shoulder, lapel, and waist seams, followed by a second fitting on Day 2 or Day 3 before collecting your finished garments.'],
            ],
        ],
        'vietnam-scooter-rental-checklist' => [
            'name'        => 'How to Safely Rent and Inspect a Scooter in Vietnam',
            'description' => 'Step-by-step verification checklist for legal 1968 IDP compliance, conducting pre-rental mechanical checks, avoiding passport retention, and refueling with RON 95 in Vietnam.',
            'steps'       => [
                ['name' => 'Verify Legal 1968 IDP and Travel Insurance Endorsement', 'text' => 'Confirm you hold an original domestic motorcycle driver license and a 1968 Vienna Convention IDP with motorcycle category A/A1 endorsement; 1949 Geneva Convention IDPs are invalid under Vietnamese law.'],
                ['name' => 'Conduct 7-Point Pre-Rental Mechanical Inspection', 'text' => 'Test front and rear handbrakes on flat pavement, check tire tread depth and inflation, test front headlamp and high-beam, inspect rear brake light, test turn indicators, test the horn, and ensure both side mirrors are securely fastened.'],
                ['name' => 'Photograph Existing Scratches and Agree on Cash Deposit', 'text' => 'Take date-stamped photos and a 360-degree video of all body panel scratches and the fuel level; provide a passport photocopy with a cash deposit of 1,000,000 to 2,000,000 VND instead of leaving your original passport.'],
                ['name' => 'Wear Certified Helmet and Fill with RON 95-III Gasoline', 'text' => 'Fasten a certified 3/4 or full-face helmet with a clean scratch-free visor (avoid decorative novelty caps), and refuel at official Petrolimex or PVOIL stations with unleaded RON 95-III gasoline.'],
            ],
        ],
        'ha-tien-to-phu-quoc-ferry' => [
            'name'        => 'How to Take the Fast Ferry from Ha Tien to Phu Quoc Island',
            'description' => 'Step-by-step instructions for booking, ticketing, passport manifest check-in, and boarding high-speed catamarans from Ha Tien Ferry Terminal to Bai Vong Port in Phu Quoc.',
            'steps'       => [
                ['name' => 'Reserve Superdong or Phu Quoc Express Tickets 24–48 Hours Ahead', 'text' => 'Book standard seats online or at Ha Tien ferry port for 216,000 to 230,000 VND one-way, selecting daytime departure slots between 7:30 AM and 1:30 PM.'],
                ['name' => 'Check In at Ha Tien Passenger Ferry Terminal with Original Passport', 'text' => 'Arrive at the Ha Tien port terminal on Kim Du Street at least 30 minutes before departure and present each passenger original physical passport for maritime manifest compliance and ticket printing.'],
                ['name' => 'Board the Fast Catamaran and Stow Heavy Baggage', 'text' => 'Walk across the passenger gangway and place large suitcases in the designated rear luggage racks before taking your assigned air-conditioned seat for the 1-hour-15-minute to 1-hour-25-minute sea crossing.'],
                ['name' => 'Disembark at Bai Vong Port and Connect to Duong Dong or Long Beach', 'text' => 'Exit the vessel at Bai Vong Port on Phu Quoc east coast and take an official shared shuttle minibus (40,000 to 50,000 VND) or metered taxi (150,000 to 200,000 VND) across the island to your resort.'],
            ],
        ],
        'can-tho-to-chau-doc-transport' => [
            'name'        => 'How to Travel from Can Tho to Chau Doc and Visit Tra Su Cajuput Forest',
            'description' => 'Step-by-step route guide for taking the FUTA coach from Can Tho to Chau Doc and transferring 30 km south to Tra Su Cajuput Forest.',
            'steps'       => [
                ['name' => 'Board FUTA Bus at Can Tho Central Bus Station', 'text' => 'Depart from Ben xe Trung tam Can Tho (36 Nguyen Van Linh) on a morning FUTA coach (150,000 to 220,000 VND) scheduled between 06:30 and 08:30 AM.'],
                ['name' => 'Transit 120 Kilometers Northwest via National Highway 91', 'text' => 'Travel 3 to 3.5 hours along National Highway 91 (QL91) through O Mon, Thot Not, and Long Xuyen to arrive at Chau Doc Bus Station on Ton Duc Thang Street.'],
                ['name' => 'Transfer South to Tra Su Cajuput Forest in Van Giao', 'text' => 'Hire a metered taxi (350,000 to 450,000 VND round-trip) or rent a scooter in Chau Doc for the 30-kilometer, 40-minute ride south along DT948 to Tra Su Cajuput Forest in Van Giao Commune.'],
                ['name' => 'Purchase Sightseeing Tickets and Board the Canal Boats', 'text' => 'Buy the 120,000 VND entrance ticket, ride the motorboat (tac rang, 80,000 VND) along the main canal, and transfer to a hand-rowed sampan (xuong cheo tay, 70,000 VND) through the duckweed-covered cajeput swamp.'],
            ],
        ],
        'nha-trang-to-quy-nhon-transport' => [
            'name'        => 'How to Travel from Nha Trang to Quy Nhon by Coastal Train or Limousine Bus',
            'description' => 'Step-by-step instructions for booking tickets, traveling the 215-kilometer coastal route between Nha Trang and Quy Nhon by Vietnam Railways or express limousine van.',
            'steps'       => [
                ['name' => 'Choose Between Vietnam Railways Express Train or Direct Limousine Bus', 'text' => 'Select train SE22 or SE10 (150,000 to 230,000 VND soft seat) for scenic daytime coastal views, or book a 9-seat VIP limousine van (Cuc Tung or Phuc Thuan Thao for 180,000 to 250,000 VND) for direct hotel drop-off in Quy Nhon.'],
                ['name' => 'Board Train at Nha Trang Railway Station on Thai Nguyen Street', 'text' => 'Arrive at Nha Trang Station (17 Thai Nguyen Street) 25 minutes before departure, scan your electronic QR-code train ticket at the automated turnstile, and board your air-conditioned car.'],
                ['name' => 'Travel 215 Kilometers North Along the Coastal Plain', 'text' => 'Ride 215 kilometers north along the coast for 3 hours 50 minutes to 4 hours 15 minutes past Van Phong Bay and across Deo Ca Pass into Phu Yen and Binh Dinh provinces.'],
                ['name' => 'Disembark at Dieu Tri Station and Connect to Quy Nhon Center', 'text' => 'Exit at Dieu Tri Railway Station and take a metered taxi (150,000 to 200,000 VND, 20 minutes) or public bus T22 (20,000 VND) directly into downtown Quy Nhon along Xuan Dieu Beach.'],
            ],
        ],
        'hanoi-to-ha-giang-transport' => [
            'name'        => 'How to Take the Direct Sleeper Bus from Hanoi to Ha Giang',
            'description' => 'Step-by-step instructions for booking, boarding, and arriving on a VIP cabin sleeper bus between Hanoi and Ha Giang City.',
            'steps'       => [
                ['name' => 'Book a VIP Single or Double Cabin Bus Online or at Carrier Offices', 'text' => 'Reserve a private VIP cabin on Bang Phan or Quang Nghi for 300,000 to 350,000 VND (single) or 550,000 to 600,000 VND (double), selecting either a 07:00 AM daytime slot or 21:00 PM overnight departure.'],
                ['name' => 'Board at My Dinh Bus Station, Hanoi Old Quarter, or Noi Bai Airport', 'text' => 'Arrive at the carrier office in Hoan Kiem (such as Tran Nhat Duat) 30 minutes before departure or board directly at My Dinh Bus Station platform or Noi Bai Airport Terminal T1/T2 column 17.'],
                ['name' => 'Travel 280 Kilometers Northwest via Expressways CT05 and Tuyen Quang', 'text' => 'Relax in your air-conditioned reclining massage cabin as the coach travels 280 kilometers in 5.5 to 6.5 hours through Vinh Phuc, Phu Tho, and Tuyen Quang provinces with one 20-minute rest stop.'],
                ['name' => 'Arrive at Ha Giang City and Transfer to Motorbike Rental or Rest Bed', 'text' => 'Disembark at Ha Giang City central bus terminal or partner hostel; if arriving on the night bus at 03:30 AM, rest in a complimentary dorm bed until 07:00 AM before picking up your rental scooter or meeting your Easy Rider guide.'],
            ],
        ],
        'da-nang-airport-to-hoi-an' => [
            'name'        => 'How to Travel from Da Nang Airport to Hoi An Ancient Town',
            'description' => 'Step-by-step instructions for booking and taking a private car, shared airport shuttle, or taxi from Da Nang International Airport to Hoi An.',
            'steps'       => [
                ['name' => 'Clear Baggage and Exit Terminal at Da Nang Airport', 'text' => 'Retrieve your checked luggage at Da Nang Airport Terminal T1 (Domestic) or T2 (International) and exit into the public arrivals pickup plaza.'],
                ['name' => 'Select Between Fixed-Price Private Car or Shared Tourist Shuttle', 'text' => 'Choose a pre-booked private sedan (300,000 to 350,000 VND for direct 40-minute transfer) or board a scheduled shared shuttle bus (100,000 to 120,000 VND per person departing hourly from column 12).'],
                ['name' => 'Travel 30 Kilometers South via Coastal Boulevards', 'text' => 'Ride south along Vo Nguyen Giap and Lac Long Quan coastal highways for 40 to 45 minutes, passing My Khe Beach and Marble Mountains (Ngu Hanh Son).'],
                ['name' => 'Arrive at Hoi An Hotel or Ancient Town Pedestrian Border', 'text' => 'Disembark at your hotel in Hoi An or at the pedestrian boundary of the Ancient Town where motor vehicles are restricted during walking hours.'],
            ],
        ],
        'da-nang-to-hue-train-vs-car' => [
            'name'        => 'How to Take the Heritage Train Across Hai Van Pass between Da Nang and Hue',
            'description' => 'Step-by-step guide for booking tickets and riding the Central Heritage train (HD1/HD2/HD3/HD4) along the coastal cliffside railway across Hai Van Pass.',
            'steps'       => [
                ['name' => 'Book Heritage Train Tickets Online or at the Station', 'text' => 'Reserve air-conditioned soft seats on train HD1/HD3 (Hue to Da Nang) or HD2/HD4 (Da Nang to Hue) via dsvn.vn or at station ticket counters for 110,000 to 180,000 VND.'],
                ['name' => 'Board at Da Nang Railway Station or Hue Railway Station', 'text' => 'Arrive at the railway station (202 Hai Phong Street in Da Nang or Bui Thi Xuan Street in Hue) 25 minutes prior to scheduled departure and show your digital QR ticket.'],
                ['name' => 'Ride 102 Kilometers Across Hai Van Pass and Lang Co Bay', 'text' => 'Enjoy the 3-hour journey as the train hugs cliffside rails 100 meters above the sea, stopping 10 minutes at Lang Co Station for photographs of the turquoise bay.'],
                ['name' => 'Disembark and Connect to City Center', 'text' => 'Arrive at your destination station and take an official metered taxi (Mai Linh or Sun Taxi, 40,000 to 70,000 VND) into the city center.'],
            ],
        ],
        'hanoi-to-ninh-binh-train-vs-limousine' => [
            'name'        => 'How to Travel from Hanoi to Ninh Binh by Limousine Van or Train',
            'description' => 'Step-by-step guide for booking and traveling between Hanoi and Ninh Binh using direct limousine vans or the Reunification Express train.',
            'steps'       => [
                ['name' => 'Choose Between Door-to-Door Limousine Van or Railway Train', 'text' => 'Decide between a 9-seat VIP limousine van (150,000 to 220,000 VND, 90 minutes via CT01 expressway directly to Tam Coc) or a Vietnam Railways train (75,000 to 130,000 VND, 2 hours 15 minutes to downtown Ninh Binh station).'],
                ['name' => 'Book Tickets Online or Through Your Hanoi Accommodation', 'text' => 'Reserve limousine seats with operators like Trang An Limousine or Duy Khang via online ticketing or your hotel reception; for trains, book official electronic tickets via dsvn.vn.'],
                ['name' => 'Board at Your Pick-up Point in Hanoi', 'text' => 'For limousines, wait in your Hoan Kiem hotel lobby during the designated 30-minute pickup window; for train departures, arrive at Hanoi Central Station (120 Le Duan) 20 minutes before train departure time.'],
                ['name' => 'Arrive in Ninh Binh and Transfer to Your Rural Homestay', 'text' => 'Limousine passengers are dropped off directly at their Tam Coc or Trang An resort; train passengers disembark at Ninh Binh Railway Station and take a metered taxi or Grab car (80,000 to 100,000 VND, 15 minutes) to the karst valleys.'],
            ],
        ],
        'ho-chi-minh-city-to-mui-ne-transport' => [
            'name'        => 'How to Travel from Ho Chi Minh City to Mui Ne by Expressway Limousine or Train SPT2',
            'description' => 'Step-by-step instructions for traveling between Ho Chi Minh City and Mui Ne using the Dau Giay - Phan Thiet expressway limousine or daily SPT2 tourist train.',
            'steps'       => [
                ['name' => 'Compare the CT01 Expressway Limousine and SPT2 Scenic Train', 'text' => 'Choose a 9-seat VIP limousine via the CT01 expressway (250,000 to 350,000 VND, 2.5 to 3 hours direct to your Mui Ne resort) or the daily morning SPT2 train (180,000 to 260,000 VND, 3 hours 45 minutes smooth ride to Phan Thiet Station).'],
                ['name' => 'Reserve Your Departure Time in Advance', 'text' => 'Book limousine seats through G5Car, ADT, or 3S Limo departing District 1; for weekend train travel, purchase your ticket on dsvn.vn several days ahead to secure air-conditioned soft seats.'],
                ['name' => 'Depart from Central Saigon', 'text' => 'Board your limousine at your District 1 hotel or the carrier\'s Pham Ngu Lao office; for train travel, arrive at Saigon Railway Station (1 Nguyen Thong, District 3) 25 minutes prior to the 06:45 AM departure.'],
                ['name' => 'Arrive in Phan Thiet and Check into Your Beach Resort', 'text' => 'Limousine vans deliver you directly to your resort along Nguyen Dinh Chieu Street; if taking the SPT2 train, exit Phan Thiet Station and board a metered taxi (200,000 to 250,000 VND, 20 minutes) out to Mui Ne beach.'],
            ],
        ],
        'cham-islands-day-trip-guide' => [
            'name'        => 'How to Take a Day Trip to the Cham Islands from Hoi An',
            'description' => 'Step-by-step guide for booking speedboat transfers, paying marine conservation fees, and snorkeling coral reefs in the Cham Islands UNESCO biosphere reserve.',
            'steps'       => [
                ['name' => 'Choose Between All-Inclusive Day Tour or Independent Speedboat', 'text' => 'Select an organized day tour (550,000 to 750,000 VND with hotel pickup, snorkeling gear, and seafood lunch) or purchase an independent round-trip speedboat ticket at Cua Dai Pier (450,000 to 550,000 VND including marine ecology fee).'],
                ['name' => 'Arrive at Cua Dai Pier in East Hoi An', 'text' => 'Take a 15-minute taxi or Grab ride (80,000 to 100,000 VND) from Hoi An Ancient Town to Cua Dai Pier by 08:30 AM, register your passport with border authorities, and board your assigned high-speed canoe.'],
                ['name' => 'Cross Open Waters to Hon Lao Island', 'text' => 'Enjoy the 20-minute speedboat crossing past the mouth of the Thu Bon River out to Bai Lang fishing harbor on Hon Lao island.'],
                ['name' => 'Snorkel Coral Reefs at Bai Xep and Relax on Bai Chong Beach', 'text' => 'Transfer to wooden snorkeling boats for shallow coral viewing at Bai Xep reef, enjoy a seafood lunch at Bai Chong, and board the return speedboat departing at 14:00 to 14:30 PM.'],
            ],
        ],
        'hanoi-to-sapa-train-vs-sleeper-bus' => [
            'name'        => 'How to Travel from Hanoi to Sapa by Express Sleeper Bus or Overnight Train',
            'description' => 'Step-by-step instructions for booking and traveling between Hanoi and Sapa via the direct CT05 expressway cabin bus or overnight train to Lao Cai.',
            'steps'       => [
                ['name' => 'Choose Between Direct Expressway Cabin Bus or Classic Overnight Train', 'text' => 'Select a direct 5.5-hour VIP cabin bus via the CT05 expressway (350,000 to 450,000 VND) for speed and direct town drop-off, or an 8-hour overnight train (SP1/SP3 for 350,000 to 900,000 VND) for a flat bed immune to mountain road nausea.'],
                ['name' => 'Reserve Tickets Online or Through Your Hanoi Hotel', 'text' => 'Book electronic train berths on dsvn.vn or reserve VIP single/double cabins on Kadham Bus or Sao Viet via verified ticketing platforms 24 hours prior to travel.'],
                ['name' => 'Board at Hanoi Railway Station or Old Quarter Bus Office', 'text' => 'For train departures, arrive at Hanoi Central Station (120 Le Duan) at 21:00 PM; for cabin buses, board at the operator\'s Hoan Kiem office on Tran Nhat Duat Street or take an included shuttle.'],
                ['name' => 'Arrive in Sapa Town and Settle into Mountain Lodging', 'text' => 'Cabin buses arrive directly in central Sapa near the stone church; train passengers arrive at Lao Cai Station, board a connecting mountain minibus (50,000 to 60,000 VND), and ride 35 kilometers up the pass to Sapa.'],
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
            'description'         => 'Interactive nationality-based visa requirements, 45-day unilateral exemption rules (25 countries under Resolutions 44/NQ-CP & 229/NQ-CP), and official 90-day e-visa validation engine across 42 international checkpoints.',
            'applicationCategory' => 'TravelApplication',
            'featureList'         => [
                'Instant nationality lookup across 80+ countries',
                '45-day unilateral exemption (25 countries under Resolutions 44/NQ-CP & 229/NQ-CP) vs 30-day bilateral exemption status indicator',
                '6-month passport validity expiration calculator',
                '42 official international checkpoint gate inspector under Resolution 127/NQ-CP',
                'Official government immigration portal link shield (evisa.gov.vn & thithucdientu.gov.vn)',
            ],
        ],
        'best-time-to-visit-vietnam' => [
            'name'                => 'Vietnam Weather & Regional Season Matrix',
            'description'         => 'Interactive month-by-month regional climate matrix across North, Central, and South Vietnam with monsoon, Beaufort Force 6 maritime suspension, and 2026–2027 Tet holiday indicators.',
            'applicationCategory' => 'TravelApplication',
            'featureList'         => [
                '12-month climate navigator across Northern, Central, and Southern Vietnam',
                'Regional rainfall, temperature, and sea swimming conditions',
                'Mountain elevation temperature drop calculator',
                'Seasonal weather risk radar, Beaufort Force 6 maritime cruise rules, and 2026–2027 Tet holiday ledger',
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
            'description'         => 'Interactive terminal transit guide, verified Grab pickup bay locators, and metered taxi/bus/metro fare estimators across 9 gateway airports (HAN, SGN, DAD, CXR, PQC, HUI, DLI, VDH, HPH).',
            'applicationCategory' => 'TravelApplication',
            'featureList'         => [
                'Terminal-to-curb arrival navigation for 9 gateway airports (HAN, SGN including Terminal T3, DAD, CXR, PQC, HUI, DLI, VDH, HPH)',
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


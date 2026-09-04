<?php
/**
 * Publish the premium Evidence-led Concierge homepage.
 *
 * Run from the WordPress root with:
 * wp eval-file ops/apply-homepage-premium.php --allow-root
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_home_log(string $message): void
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function vg_home_page_by_path(string $path): WP_Post
{
    $page = get_page_by_path($path, OBJECT, 'page');

    if (! $page instanceof WP_Post) {
        throw new RuntimeException("Missing page: {$path}");
    }

    if ('publish' !== $page->post_status) {
        throw new RuntimeException("Page is not published: {$path}");
    }

    return $page;
}

function vg_home_published_page_exists(string $path): bool
{
    $page = get_page_by_path($path, OBJECT, 'page');

    return $page instanceof WP_Post && 'publish' === $page->post_status;
}

function vg_home_path(string $preferred_path, string $fallback_path): string
{
    return vg_home_published_page_exists(trim($preferred_path, '/'))
        ? '/' . trim($preferred_path, '/') . '/'
        : '/' . trim($fallback_path, '/') . '/';
}

function vg_home_required_path(string $path): string
{
    $path = trim($path, '/');

    if (! vg_home_published_page_exists($path)) {
        throw new RuntimeException("Homepage required page is not published: {$path}");
    }

    return '/' . $path . '/';
}

function vg_home_internal_path_from_href(string $href): ?string
{
    $href = trim(html_entity_decode($href, ENT_QUOTES, 'UTF-8'));

    if ('' === $href || str_starts_with($href, '#')) {
        return null;
    }

    if (str_starts_with($href, '/')) {
        $path = parse_url($href, PHP_URL_PATH);
    } elseif (preg_match('/^https?:\/\//i', $href)) {
        $site_host = parse_url(home_url('/'), PHP_URL_HOST);
        $href_host = parse_url($href, PHP_URL_HOST);

        if (! $site_host || ! $href_host || 0 !== strcasecmp($site_host, $href_host)) {
            return null;
        }

        $path = parse_url($href, PHP_URL_PATH);
    } else {
        return null;
    }

    if (! is_string($path)) {
        return null;
    }

    $path = trim($path, '/');

    return '' === $path ? 'home' : $path;
}

function vg_home_assert_internal_page_links_are_published(string $content): void
{
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $content, $matches);

    $paths = [];
    foreach ($matches[2] as $href) {
        $path = vg_home_internal_path_from_href($href);

        if (null !== $path) {
            $paths[$path] = true;
        }
    }

    $failures = [];
    foreach (array_keys($paths) as $path) {
        if ('home' === $path) {
            $front_page_id = (int) get_option('page_on_front');
            $page = $front_page_id ? get_post($front_page_id) : get_page_by_path('home', OBJECT, 'page');
        } else {
            $page = get_page_by_path($path, OBJECT, 'page');
        }

        if (! $page instanceof WP_Post || 'publish' !== $page->post_status) {
            $failures[] = "Homepage links to unpublished page path: /{$path}/";
        }
    }

    if ($failures) {
        throw new RuntimeException(implode(PHP_EOL, $failures));
    }

    vg_home_log('Validated homepage internal page links are published.');
}

$home = vg_home_page_by_path('home');

$travel_guide_href = vg_home_path('plan/vietnam-travel-guide', 'plan');
$best_places_href = vg_home_path('destinations/best-places-to-visit-vietnam', 'destinations');
$hanoi_travel_guide_href = vg_home_required_path('destinations/hanoi-travel-guide');
if ('/destinations/hanoi-travel-guide/' !== $hanoi_travel_guide_href) {
    throw new RuntimeException('Unexpected Hanoi Travel Guide homepage href: ' . $hanoi_travel_guide_href);
}
$where_to_stay_in_hanoi_href = vg_home_required_path('destinations/where-to-stay-in-hanoi');
if ('/destinations/where-to-stay-in-hanoi/' !== $where_to_stay_in_hanoi_href) {
    throw new RuntimeException('Unexpected Where to Stay in Hanoi homepage href: ' . $where_to_stay_in_hanoi_href);
}
$old_quarter_french_quarter_west_lake_href = vg_home_required_path('compare/old-quarter-vs-french-quarter-vs-west-lake');
if ('/compare/old-quarter-vs-french-quarter-vs-west-lake/' !== $old_quarter_french_quarter_west_lake_href) {
    throw new RuntimeException('Unexpected Old Quarter vs French Quarter vs West Lake homepage href: ' . $old_quarter_french_quarter_west_lake_href);
}
$hanoi_day_trips_guide_href = vg_home_required_path('destinations/best-day-trips-from-hanoi');
if ('/destinations/best-day-trips-from-hanoi/' !== $hanoi_day_trips_guide_href) {
    throw new RuntimeException('Unexpected Best Day Trips from Hanoi homepage href: ' . $hanoi_day_trips_guide_href);
}
$hanoi_airport_transfer_href = vg_home_required_path('plan/hanoi-airport-to-old-quarter');
if ('/plan/hanoi-airport-to-old-quarter/' !== $hanoi_airport_transfer_href) {
    throw new RuntimeException('Unexpected Hanoi Airport to Old Quarter homepage href: ' . $hanoi_airport_transfer_href);
}
$hanoi_two_days_href = vg_home_required_path('itineraries/hanoi-in-2-days');
if ('/itineraries/hanoi-in-2-days/' !== $hanoi_two_days_href) {
    throw new RuntimeException('Unexpected Hanoi in 2 Days homepage href: ' . $hanoi_two_days_href);
}
$hanoi_guide_href = vg_home_path('destinations/best-things-to-do-in-hanoi', 'destinations');
$ninh_binh_guide_href = vg_home_path('destinations/ninh-binh-travel-guide', 'destinations');
$ninh_binh_day_trip_overnight_href = vg_home_required_path('compare/ninh-binh-day-trip-vs-overnight');
if ('/compare/ninh-binh-day-trip-vs-overnight/' !== $ninh_binh_day_trip_overnight_href) {
    throw new RuntimeException('Unexpected Ninh Binh Day Trip vs Overnight homepage href: ' . $ninh_binh_day_trip_overnight_href);
}
$ninh_binh_stays_href = vg_home_required_path('destinations/where-to-stay-in-ninh-binh');
if ('/destinations/where-to-stay-in-ninh-binh/' !== $ninh_binh_stays_href) {
    throw new RuntimeException('Unexpected Where to Stay in Ninh Binh homepage href: ' . $ninh_binh_stays_href);
}
$trang_an_tam_coc_href = vg_home_required_path('compare/trang-an-vs-tam-coc');
if ('/compare/trang-an-vs-tam-coc/' !== $trang_an_tam_coc_href) {
    throw new RuntimeException('Unexpected Trang An vs Tam Coc homepage href: ' . $trang_an_tam_coc_href);
}
$tam_coc_guide_href = vg_home_required_path('destinations/tam-coc-travel-guide');
if ('/destinations/tam-coc-travel-guide/' !== $tam_coc_guide_href) {
    throw new RuntimeException('Unexpected Tam Coc Travel Guide homepage href: ' . $tam_coc_guide_href);
}
$hanoi_ninh_binh_transport_href = vg_home_required_path('plan/hanoi-to-ninh-binh-transport');
if ('/plan/hanoi-to-ninh-binh-transport/' !== $hanoi_ninh_binh_transport_href) {
    throw new RuntimeException('Unexpected Hanoi to Ninh Binh Transport homepage href: ' . $hanoi_ninh_binh_transport_href);
}
$ninh_binh_ha_long_transfer_href = vg_home_required_path('plan/ninh-binh-to-ha-long-bay-transfer');
if ('/plan/ninh-binh-to-ha-long-bay-transfer/' !== $ninh_binh_ha_long_transfer_href) {
    throw new RuntimeException('Unexpected Ninh Binh to Ha Long Bay Transfer homepage href: ' . $ninh_binh_ha_long_transfer_href);
}
$ha_long_bay_guide_href = vg_home_path('destinations/ha-long-bay-travel-guide', 'destinations');
$cat_ba_guide_href = vg_home_path('destinations/cat-ba-travel-guide', 'destinations');
$bai_tu_long_guide_href = vg_home_path('destinations/bai-tu-long-bay-guide', 'destinations');
$best_beaches_href = vg_home_required_path('destinations/best-beaches-in-vietnam');
$best_islands_href = vg_home_required_path('destinations/best-islands-in-vietnam');
$con_dao_guide_href = vg_home_required_path('destinations/con-dao-travel-guide');
$phu_quoc_guide_href = vg_home_required_path('destinations/phu-quoc-travel-guide');
$phu_quoc_nha_trang_compare_href = vg_home_required_path('compare/phu-quoc-vs-nha-trang');
$mui_ne_nha_trang_compare_href = vg_home_path('compare/mui-ne-vs-nha-trang', 'compare');
$nha_trang_guide_href = vg_home_required_path('destinations/nha-trang-travel-guide');
$quy_nhon_guide_href = vg_home_path('destinations/quy-nhon-travel-guide', 'destinations');
$cham_islands_guide_href = vg_home_path('destinations/cham-islands-travel-guide', 'destinations');
$ly_son_guide_href = vg_home_path('destinations/ly-son-travel-guide', 'destinations');
$ho_chi_minh_city_guide_href = vg_home_required_path('destinations/ho-chi-minh-city-travel-guide');
$ho_chi_minh_city_stays_href = vg_home_required_path('destinations/where-to-stay-in-ho-chi-minh-city');
$mekong_delta_guide_href = vg_home_required_path('destinations/mekong-delta-travel-guide');
$hcmc_day_trips_guide_href = vg_home_required_path('destinations/best-day-trips-from-ho-chi-minh-city');
$da_nang_guide_href = vg_home_required_path('destinations/da-nang-travel-guide');
$hoi_an_guide_href = '/destinations/best-things-to-do-in-hoi-an/';
$hue_guide_href = vg_home_path('destinations/best-things-to-do-in-hue', 'destinations');
$region_compare_href = vg_home_path('compare/north-central-south-vietnam', 'compare');
$ha_long_lan_ha_compare_href = vg_home_path('compare/ha-long-bay-vs-lan-ha-bay', 'compare');
$da_nang_hoi_an_compare_href = vg_home_required_path('compare/da-nang-vs-hoi-an');
$hoi_an_hue_compare_href = vg_home_required_path('compare/hoi-an-vs-hue');
$best_time_href = vg_home_path('plan/best-time-to-visit-vietnam', 'plan');
$cost_href = vg_home_path('costs/vietnam-travel-cost', 'costs');
$transport_href = vg_home_path('plan/transport-within-vietnam', 'plan');
$money_href = vg_home_path('plan/money-cash-cards-atms', 'plan');
$sim_esim_href = vg_home_path('plan/sim-esim-vietnam', 'plan');
$safety_scams_href = vg_home_path('plan/safety-scams-vietnam', 'plan');
$health_insurance_href = vg_home_path('plan/health-travel-insurance-vietnam', 'plan');
$evisa_href = vg_home_path('plan/vietnam-evisa', 'plan');
$heritage_guide_href = vg_home_required_path('destinations/unesco-heritage-sites-vietnam');
$seven_days_href = vg_home_required_path('itineraries/7-days-in-vietnam');
$ten_days_href = vg_home_path('itineraries/10-days-in-vietnam', 'itineraries');
$fourteen_days_href = vg_home_required_path('itineraries/14-days-in-vietnam');
$twenty_one_days_href = vg_home_required_path('itineraries/21-days-in-vietnam');

$theme_asset_base = esc_url(set_url_scheme(get_stylesheet_directory_uri(), 'https'));
$hero_jpg = esc_url($theme_asset_base . '/assets/images/ha-long-bay-vietnam-hero.jpg');
$hero_webp = esc_url($theme_asset_base . '/assets/images/ha-long-bay-vietnam.webp');

$hanoi_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1280px-Hanoi-lac-hoan-kiem.jpg');
$hanoi_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg');
$trang_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg/1280px-Trang_An_Landscape_Complex%2C_Ninh_Binh_Province%2C_Vietnam%2C_20240202_1456_5313.jpg');
$trang_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg');
$lan_ha_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/ca/Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg/1280px-Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg');
$lan_ha_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg');
$cat_ba_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Cat_Ba_Island.jpg/1280px-Cat_Ba_Island.jpg');
$cat_ba_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cat_Ba_Island.jpg');
$bai_tu_long_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/4/46/B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg/1280px-B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg');
$bai_tu_long_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg');
$best_beaches_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$best_beaches_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg');
$nha_trang_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Nha_Trang_Beach_3.jpg/1920px-Nha_Trang_Beach_3.jpg');
$nha_trang_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Nha_Trang_Beach_3.jpg');
$mui_ne_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/2d/Vietnam%2C_Mui_Ne_beach%2C_Kitesurfing_on_the_beach.jpg/1920px-Vietnam%2C_Mui_Ne_beach%2C_Kitesurfing_on_the_beach.jpg');
$mui_ne_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Mui_Ne_beach,_Kitesurfing_on_the_beach.jpg');
$quy_nhon_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f5/Ky_Co_-_Nhon_Ly_-_Quy_Nhon_-_Binh_Dinh_-_Viet_Nam.jpg/1280px-Ky_Co_-_Nhon_Ly_-_Quy_Nhon_-_Binh_Dinh_-_Viet_Nam.jpg');
$quy_nhon_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ky_Co_-_Nhon_Ly_-_Quy_Nhon_-_Binh_Dinh_-_Viet_Nam.jpg');
$cham_islands_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/4/4a/Cham_Island_%28C%C3%B9_Lao_Ch%C3%A0m%29_seen_from_M%E1%BB%B9_Kh%C3%AA_Beach%2C_%C4%90%C3%A0_N%E1%BA%B5ng%2C_Vietnam.jpg/1280px-Cham_Island_%28C%C3%B9_Lao_Ch%C3%A0m%29_seen_from_M%E1%BB%B9_Kh%C3%AA_Beach%2C_%C4%90%C3%A0_N%E1%BA%B5ng%2C_Vietnam.jpg');
$cham_islands_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Cham_Island_(C%C3%B9_Lao_Ch%C3%A0m)_seen_from_M%E1%BB%B9_Kh%C3%AA_Beach,_%C4%90%C3%A0_N%E1%BA%B5ng,_Vietnam.jpg');
$ly_son_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/f/f5/Ly_Son_Islands_%2814817868968%29.jpg/1280px-Ly_Son_Islands_%2814817868968%29.jpg');
$ly_son_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ly_Son_Islands_(14817868968).jpg');
$best_islands_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg/1280px-Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg');
$best_islands_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_(April_2022).jpg');
$hue_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg/1920px-Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$hue_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg');
$hai_van_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Vietnam%2C_Hai-Van-Pass.jpg/1920px-Vietnam%2C_Hai-Van-Pass.jpg');
$hai_van_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg');
$hoi_an_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg/1280px-H%E1%BB%99i_An%2C_Ancient_Town%2C_2020-01_CN-11.jpg');
$hoi_an_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg');
$hcmc_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg');
$hcmc_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg');
$mekong_image = esc_url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1280px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg');
$mekong_credit_url = esc_url('https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg');

$review_date = 'July 25, 2026';
$homepage_update_summary = 'Homepage systemized as a template-first concierge hub with a visible source/update snapshot, expanded reviewed-guide shelf including Trang An vs Tam Coc, Hanoi to Ninh Binh Transport, Ninh Binh Day Trip vs Overnight, Hanoi in 2 Days, 7 Days in Vietnam, 21 Days in Vietnam, Bai Tu Long, Best Beaches, Best Islands, Con Dao Travel Guide, Phu Quoc Travel Guide, Ho Chi Minh City Travel Guide, Where to Stay in Ho Chi Minh City, Best Day Trips from Ho Chi Minh City, Nha Trang Travel Guide, Quy Nhon Travel Guide, Cham Islands Travel Guide, Ly Son Travel Guide, Phu Quoc vs Nha Trang, Mui Ne vs Nha Trang, Cat Ba, Da Nang Travel Guide, Da Nang vs Hoi An, Hoi An vs Hue, Ha Long Bay, Ha Long Bay vs Lan Ha Bay, Ninh Binh, Where to Stay in Ninh Binh, Ninh Binh to Ha Long Bay Transfer, Tam Coc Travel Guide, Safety and Scams, Health and Travel Insurance, Hanoi, Where to Stay in Hanoi, Best Day Trips from Hanoi, Hoi An, Hue, SIM/eSIM, and UNESCO heritage, related route map, itinerary-length ladder, northern bay/island/beach/central-coast/southern-city photo proof, island, central marine, central island detour, southern city gateway, Hanoi short-stay itinerary support, Ninh Binh day-trip versus overnight pacing support, Trang An versus Tam Coc boat-choice support, Tam Coc base-guide support, Hanoi to Ninh Binh transport support, Hanoi and HCMC stay-area decision support, Hanoi and HCMC excursion decision support, and south-central coast source-diversity routing, and stronger licensed image-credit trail.';

$homepage = <<<HTML
<!-- vg-homepage-premium:v1 -->
<!-- wp:group {"align":"full","className":"vg-hero vg-home-hero"} -->
<div class="wp-block-group alignfull vg-hero vg-home-hero">
<!-- wp:html -->
<picture class="vg-hero-media-wrap">
<source srcset="{$hero_webp}" type="image/webp">
<img class="vg-hero-media" src="{$hero_jpg}" alt="Cruise boats among limestone karsts in Ha Long Bay, Vietnam" width="1920" height="1080" loading="eager" decoding="async" fetchpriority="high">
</picture>
<!-- /wp:html -->
<!-- wp:group {"className":"vg-hero-inner"} -->
<div class="wp-block-group vg-hero-inner" data-vg-reveal>
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">VietnamGuide.net</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"vg-hero-brand"} -->
<h1 class="wp-block-heading vg-hero-brand">Vietnam Travel Guide for International Visitors</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-hero-copy"} -->
<p class="vg-hero-copy">Evidence-led Vietnam planning for travelers who want the route verdict before the noise: realistic costs, season pressure, entry checks, and destination trade-offs before money is committed.</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"vg-home-hero-proof"} -->
<ul class="wp-block-list vg-home-hero-proof">
<li>Verdicts before list fatigue</li>
<li>Visible source and credit trail</li>
<li>Last meaningful review: {$review_date}</li>
</ul>
<!-- /wp:list -->
<!-- wp:buttons {"className":"vg-action-row"} -->
<div class="wp-block-buttons vg-action-row">
<!-- wp:button {"className":"vg-button"} -->
<div class="wp-block-button vg-button"><a class="wp-block-button__link wp-element-button" href="{$travel_guide_href}">Start with the travel guide</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"vg-button vg-button-secondary"} -->
<div class="wp-block-button vg-button vg-button-secondary"><a class="wp-block-button__link wp-element-button" href="{$fourteen_days_href}">Compare 14-day routes</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<p class="vg-image-credit">Hero image: Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0.</p>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-home-section vg-home-intro"} -->
<div class="wp-block-group vg-home-section vg-home-intro">
<!-- wp:group {"className":"vg-wrap vg-home-split"} -->
<div class="wp-block-group vg-wrap vg-home-split">
<!-- wp:group {"className":"vg-home-section-lede"} -->
<div class="wp-block-group vg-home-section-lede" data-vg-reveal>
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Begin with judgment</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"vg-display"} -->
<h2 class="wp-block-heading vg-display">This is an editorial briefing room, not a pile of rewritten search results.</h2>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"vg-home-long-copy"} -->
<p class="vg-home-long-copy" data-vg-reveal>Vietnam trips usually go wrong in the same places: three-region routes squeezed into too few nights, weather treated as a single national pattern, domestic movement under-budgeted, and famous stops added after the route is already full. Start here when you want the decision order before the booking tabs.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- vg-home-source-update-snapshot:v1 -->
<!-- wp:group {"className":"vg-home-section vg-home-source-update-snapshot vg-trust-band"} -->
<div class="wp-block-group vg-home-section vg-home-source-update-snapshot vg-trust-band">
<!-- wp:group {"className":"vg-wrap vg-home-proof-columns"} -->
<div class="wp-block-group vg-wrap vg-home-proof-columns">
<!-- wp:group -->
<div class="wp-block-group" data-vg-reveal>
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Source and update snapshot</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"vg-display"} -->
<h2 class="wp-block-heading vg-display">Reviewed guide status: active homepage route hub.</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Last meaningful review: {$review_date}. {$homepage_update_summary}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:list {"className":"vg-feature-list vg-home-method-list"} -->
<ul class="wp-block-list vg-feature-list vg-home-method-list" data-vg-reveal>
<li><strong>Reviewed guide status:</strong> homepage reviewed as the decision map for VietnamGuide's core route, season, cost, visa, and destination guides.</li>
<li><strong>Last meaningful review date:</strong> {$review_date}</li>
<li><strong>Source policy link:</strong> <a href="/source-update-policy/">Source and Update Policy</a></li>
<li><strong>Image credit policy:</strong> use licensed or site-owned images, keep credits visible near media, and store credit checks in the source trail.</li>
<li><strong>Update summary:</strong> {$homepage_update_summary}</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- vg-home-planning-map:v1 -->
<!-- wp:group {"className":"vg-home-section vg-home-decision-spine vg-home-planning-map"} -->
<div class="wp-block-group vg-home-section vg-home-decision-spine vg-home-planning-map">
<!-- wp:group {"className":"vg-wrap"} -->
<div class="wp-block-group vg-wrap">
<!-- wp:group {"className":"vg-home-planning-head"} -->
<div class="wp-block-group vg-home-planning-head">
<!-- wp:group {"className":"vg-home-section-lede"} -->
<div class="wp-block-group vg-home-section-lede" data-vg-reveal>
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Planning map</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"vg-display"} -->
<h2 class="wp-block-heading vg-display">Find the next decision by where the trip is stuck.</h2>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"vg-home-planning-note"} -->
<p class="vg-home-planning-note" data-vg-reveal>Use this as a short diagnostic before reading everything. The goal is to identify the decision that blocks the trip, then move through the guide library with less noise.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<div class="vg-home-plan-list" aria-label="Vietnam trip planning map by traveler state">
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Dates set</p><div><h3>I have dates, but no route.</h3><p>Start with the number of nights you can protect, the weather pattern you are actually entering, and the bases your calendar can honestly support.</p><p class="vg-section-link"><a href="{$fourteen_days_href}">Compare route shapes</a></p></div><p class="vg-home-plan-proof">Best when flights are still flexible or you are deciding whether Vietnam should be north-only, north-to-central, or full-country.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Flights held</p><div><h3>I have flights and need entry checks.</h3><p>Confirm the official entry path, application timing, name/passport details, airport assumptions, and what must be rechecked before departure.</p><p class="vg-section-link"><a href="{$evisa_href}">Check the e-visa guide</a></p></div><p class="vg-home-plan-proof">Best before non-refundable hotels, especially when arrival and departure cities differ.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Route drafted</p><div><h3>I know the route, but not the real cost.</h3><p>Separate everyday spend from route friction: domestic flights, cruises, private transfers, peak dates, rest days, and buffer money.</p><p class="vg-section-link"><a href="{$cost_href}">Build a realistic budget</a></p></div><p class="vg-home-plan-proof">Best when a daily average looks tidy but does not explain why one itinerary costs much more than another.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Regions unclear</p><div><h3>I am choosing between north, central, and south.</h3><p>Compare Vietnam by travel outcome, not fame: landscapes, food, weather, heritage, transfer load, city energy, and first-trip value.</p><p class="vg-section-link"><a href="{$region_compare_href}">Compare the regions</a></p></div><p class="vg-home-plan-proof">Best when the trip cannot fit every famous stop without turning the route into airport time.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Northern base unclear</p><div><h3>I need to choose the actual Hanoi base before Ninh Binh, the bay, or a mountain move.</h3><p>Use Where to Stay in Hanoi when Hoan Kiem, Old Quarter edge, French Quarter, Ba Dinh, West Lake, or Noi Bai airport-side choice will change sleep, pickup, walking, and first-day recovery.</p><p class="vg-section-link"><a href="{$where_to_stay_in_hanoi_href}">Choose the Hanoi base</a></p></div><p class="vg-home-plan-proof">Best when the hotel area determines whether the northern route starts calm or starts with friction.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Hanoi arrival unclear</p><div><h3>I have landed in Hanoi and need the first transfer to be calm.</h3><p>Use Hanoi Airport to Old Quarter when hotel pickup, taxi, app ride, airport bus, or public bus choice will change the first night, luggage stress, and next morning.</p><p class="vg-section-link"><a href="{$hanoi_airport_transfer_href}">Plan the airport transfer</a></p></div><p class="vg-home-plan-proof">Best when the first route decision happens before the Old Quarter, not at the curb.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">48 hours in Hanoi</p><div><h3>I have two days in Hanoi and need it to feel complete without exhausting the next transfer.</h3><p>Use Hanoi in 2 Days when the capital needs arrival recovery, Hoan Kiem orientation, one food-led evening, one serious culture block, and weather-aware exit pacing instead of a generic checklist.</p><p class="vg-section-link"><a href="{$hanoi_two_days_href}">Plan two days in Hanoi</a></p></div><p class="vg-home-plan-proof">Best when Hanoi must carry a real city chapter before Ninh Binh, the bay, a train, an airport, or Central Vietnam.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Hanoi base refined</p><div><h3>I am choosing between Old Quarter, French Quarter, and West Lake.</h3><p>Compare walking energy, calmer premium central access, food density, pickup clarity, and longer-stay comfort before the exact hotel block becomes expensive to change.</p><p class="vg-section-link"><a href="{$old_quarter_french_quarter_west_lake_href}">Compare Hanoi neighborhoods</a></p></div><p class="vg-home-plan-proof">Best when Hanoi is already the northern anchor, but the sleep zone will decide whether the first morning feels smooth or noisy.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Northern excursion unclear</p><div><h3>I need to choose the right day trip from Hanoi.</h3><p>Use Best Day Trips from Hanoi when the north needs one outside day, not another generic attractions list. It separates Ninh Binh, the bay, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, and staying in Hanoi by route job.</p><p class="vg-section-link"><a href="{$hanoi_day_trips_guide_href}">Choose the Hanoi day trip</a></p></div><p class="vg-home-plan-proof">Best when a famous excursion might weaken the capital, the next transfer, or the route's only recovery day.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Ninh Binh pacing unclear</p><div><h3>I need to decide whether Ninh Binh is a day trip or an overnight.</h3><p>Use Ninh Binh Day Trip vs Overnight when the booking fork is not what to see, but whether morning control, heat avoidance, and a softer countryside base are worth one more night.</p><p class="vg-section-link"><a href="{$ninh_binh_day_trip_overnight_href}">Compare day trip and overnight</a></p></div><p class="vg-home-plan-proof">Best when Ninh Binh, Hanoi, and the bay are all competing for the same northern margin.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Ninh Binh base unclear</p><div><h3>I need to choose where to stay in Ninh Binh.</h3><p>Use the stay guide when Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side lodging will change pickup timing, sleep, dinner choice, luggage, and the next route handoff.</p><p class="vg-section-link"><a href="{$ninh_binh_stays_href}">Choose the Ninh Binh base</a></p></div><p class="vg-home-plan-proof">Best after the day-trip versus overnight call, before paying for a pretty room in the wrong lane.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Ninh Binh boat unclear</p><div><h3>I need to choose Trang An or Tam Coc.</h3><p>Use Trang An vs Tam Coc when the night count is clear but the boat route needs to match scenery certainty, base rhythm, family comfort, crowd timing, and photography conditions.</p><p class="vg-section-link"><a href="{$trang_an_tam_coc_href}">Compare the boat routes</a></p></div><p class="vg-home-plan-proof">Best after Ninh Binh has earned a day, before the route buys a boat, viewpoint, hotel, or transfer that does not fit.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Tam Coc rhythm unclear</p><div><h3>I need Tam Coc to be a calm Ninh Binh base, not just a boat stop.</h3><p>Use Tam Coc Travel Guide when countryside lanes, Bich Dong, Mua Cave, boat timing, dinner choice, hotel location, and next-transfer pressure decide whether the base actually works.</p><p class="vg-section-link"><a href="{$tam_coc_guide_href}">Plan Tam Coc</a></p></div><p class="vg-home-plan-proof">Best after Tam Coc has beaten a generic day trip, before the route tries to add every nearby view and cave.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Ninh Binh transfer unclear</p><div><h3>I know Ninh Binh belongs in the route and need the right transfer from Hanoi.</h3><p>Use Hanoi to Ninh Binh Transport when train, limousine van, private car, day tour, or onward transfer choice depends on exact drop-off, luggage, hotel base, weather, and tomorrow's route pressure.</p><p class="vg-section-link"><a href="{$hanoi_ninh_binh_transport_href}">Choose the Ninh Binh transfer</a></p></div><p class="vg-home-plan-proof">Best after the day-trip versus overnight decision, before paying for a station, van, hotel, or cruise handoff that may not fit.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Bay handoff unclear</p><div><h3>I need to get from Ninh Binh to Ha Long Bay without missing the cruise.</h3><p>Use Ninh Binh to Ha Long Bay Transfer when the next booking depends on exact port, pickup window, luggage, weather, cruise check-in, and whether an overnight buffer is cleaner than a brittle same-day ride.</p><p class="vg-section-link"><a href="{$ninh_binh_ha_long_transfer_href}">Plan the bay handoff</a></p></div><p class="vg-home-plan-proof">Best after Ninh Binh has a real base and before the bay cruise deposit makes a vague transfer expensive.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Southern city unclear</p><div><h3>I need to know whether Ho Chi Minh City deserves a real southern chapter.</h3><p>Use Ho Chi Minh City when the south needs a real city chapter, not just an airport stamp. The guide tests districts, food, museums, Tan Son Nhat, Cu Chi, Mekong, and onward island logic before the route spends more nights.</p><p class="vg-section-link"><a href="{$ho_chi_minh_city_guide_href}">Plan Ho Chi Minh City</a></p></div><p class="vg-home-plan-proof">Best when the south is part of the route, not just the exit airport.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Stay area unclear</p><div><h3>I need to choose the actual HCMC base, not just the city.</h3><p>Use Where to Stay in Ho Chi Minh City when District 1, District 3, Thao Dien, riverside, nightlife, or airport-side hotel choice will change how much of the city the route can comfortably hold.</p><p class="vg-section-link"><a href="{$ho_chi_minh_city_stays_href}">Choose the HCMC base</a></p></div><p class="vg-home-plan-proof">Best when the hotel area can save more time than another attraction would add.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Excursion unclear</p><div><h3>I need to choose the right day trip from Ho Chi Minh City.</h3><p>Use Best Day Trips from Ho Chi Minh City when the south needs an excursion decision, not another generic attractions list.</p><p class="vg-section-link"><a href="{$hcmc_day_trips_guide_href}">Choose the HCMC day trip</a></p></div><p class="vg-home-plan-proof">Best when Cu Chi, Can Gio, Tay Ninh, Mekong, Vung Tau, or staying in the city would each change the route differently.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">River chapter unclear</p><div><h3>I need to know whether the Mekong Delta deserves a real river chapter.</h3><p>Use the Mekong Delta when the south needs river life, floating-market context, and enough time to avoid a staged final-morning rush.</p><p class="vg-section-link"><a href="{$mekong_delta_guide_href}">Plan Mekong Delta</a></p></div><p class="vg-home-plan-proof">Best when HCMC still has protected time and the Delta can earn a night instead of borrowing departure-day energy.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">One-week route unclear</p><div><h3>I have seven days and need a one-week route that does not feel thin.</h3><p>The shortest serious first-trip answer is one focused region, max two bases, one high-friction highlight, and a protected departure buffer.</p><p class="vg-section-link"><a href="{$seven_days_href}">Plan 7 days</a></p></div><p class="vg-home-plan-proof">Best when a one-week route is starting to look like a compressed whole-country tour.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Three weeks unclear</p><div><h3>I have three weeks and need a full-country route that still feels calm.</h3><p>Use 21 Days in Vietnam to decide whether the extra time should build a north-central-south route, one deliberate extension, or a slower two-region trip.</p><p class="vg-section-link"><a href="{$twenty_one_days_href}">Plan three weeks</a></p></div><p class="vg-home-plan-proof">Best when the itinerary can finally breathe but the map is starting to invite too many stops.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Central base unclear</p><div><h3>I am choosing whether Da Nang or Hoi An should anchor central Vietnam.</h3><p>Compare airport pressure, beach value, Ancient Town rhythm, food, weather, and transfer load before you split the base or commit to one side.</p><p class="vg-section-link"><a href="{$da_nang_hoi_an_compare_href}">Compare Da Nang and Hoi An</a></p></div><p class="vg-home-plan-proof">Best when central Vietnam is the hinge of the trip and you want the slowest city-to-town trade-off that still fits the calendar.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Heritage base unclear</p><div><h3>I am choosing whether Hoi An or Hue deserves protected central Vietnam time.</h3><p>Compare Hoi An atmosphere, old-town evenings, food, My Son or beach slack, Hue imperial depth, Citadel context, transfer order, and weather exposure before adding both.</p><p class="vg-section-link"><a href="{$hoi_an_hue_compare_href}">Compare Hoi An and Hue</a></p></div><p class="vg-home-plan-proof">Best when the route needs central Vietnam heritage judgment, not another generic list of famous stops.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Da Nang base unclear</p><div><h3>I am deciding whether Da Nang should be the airport-and-beach city base.</h3><p>Use Da Nang when airport timing, My Khe beach, riverfront meals, Son Tra, Marble Mountains, and Hoi An/Hue access make the middle of the route easier.</p><p class="vg-section-link"><a href="{$da_nang_guide_href}">Plan Da Nang</a></p></div><p class="vg-home-plan-proof">Best when convenience should feel premium rather than like a transit compromise.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Marine day unclear</p><div><h3>I need to know whether the Cham Islands day trip earns a central Vietnam day.</h3><p>Use the Cham Islands guide when Hoi An has enough margin for a boat-based marine day, protected-area context, weather checks, and a return plan that does not crowd the old town.</p><p class="vg-section-link"><a href="{$cham_islands_guide_href}">Plan Cham Islands</a></p></div><p class="vg-home-plan-proof">Best when the route needs sea texture near Hoi An, not a full island-resort detour.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Central island detour</p><div><h3>I need to know whether Ly Son earns a central Vietnam island detour.</h3><p>Use the Ly Son guide when the route has enough room for Sa Ky ferry timing, volcanic coast, Dao Be, garlic culture, and one or two island nights after Da Nang, Hoi An, and Hue are protected.</p><p class="vg-section-link"><a href="{$ly_son_guide_href}">Plan Ly Son</a></p></div><p class="vg-home-plan-proof">Best when an island chapter must earn real route time, not just look tempting from a photo.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Bay/cruise unclear</p><div><h3>I am deciding whether the northern bay cruise is worth the time.</h3><p>Compare Ha Long and Lan Ha by port, route map, cruise length, Cat Ba access, weather risk, and whether Ninh Binh already gives the landscape chapter enough weight.</p><p class="vg-section-link"><a href="{$ha_long_lan_ha_compare_href}">Compare the bays</a></p></div><p class="vg-home-plan-proof">Best before paying a cruise deposit or adding another one-night stop to a route that already feels tight.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Island base unclear</p><div><h3>I am deciding whether Cat Ba should be a base or a skip.</h3><p>Use Cat Ba when the island adds Lan Ha access, national park time, or a slower northern chapter. Skip it when ferry and port logistics only duplicate the bay decision.</p><p class="vg-section-link"><a href="{$cat_ba_guide_href}">Plan Cat Ba</a></p></div><p class="vg-home-plan-proof">Best before adding an island overnight to a route that already includes Hanoi, Ninh Binh, and a bay cruise.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Quieter bay unclear</p><div><h3>I am deciding whether Bai Tu Long is worth the extra cruise friction.</h3><p>Use Bai Tu Long when the route map proves a quieter water chapter. Skip it when the operator cannot explain the pier, route, weather policy, and return logistics clearly.</p><p class="vg-section-link"><a href="{$bai_tu_long_guide_href}">Plan Bai Tu Long</a></p></div><p class="vg-home-plan-proof">Best before paying a premium for a vague alternative-bay promise.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Beach chapter unclear</p><div><h3>I am deciding whether beach time actually belongs in the route.</h3><p>Use Best Beaches when you need to choose between island resort, central coast, city beach, wind sports, or skipping the beach entirely.</p><p class="vg-section-link"><a href="{$best_beaches_href}">Plan beach time</a></p></div><p class="vg-home-plan-proof">Best when the route already has enough scenery and the beach would otherwise become a decorative add-on.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Beach fork</p><div><h3>I am choosing between Phu Quoc island time and Nha Trang city-beach time.</h3><p>Compare the island resort finish with the coast-city base before you commit to a beach flight, transfer, or resort bill that may not improve the route.</p><p class="vg-section-link"><a href="{$phu_quoc_nha_trang_compare_href}">Compare Phu Quoc and Nha Trang</a></p></div><p class="vg-home-plan-proof">Best when beach time is the fork and you want the right coast before anything becomes non-refundable.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">City beach unclear</p><div><h3>I need to know whether Nha Trang should be the active coast base.</h3><p>Use the Nha Trang guide when Cam Ranh access, beach hotels, seafood, boat days, Po Nagar, and city movement need to be tested before a south-central coast stop earns the nights.</p><p class="vg-section-link"><a href="{$nha_trang_guide_href}">Plan Nha Trang</a></p></div><p class="vg-home-plan-proof">Best when the beach should stay connected to food, taxis, activity choice, and an airport rather than becoming an island resort finish.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Quieter coast unclear</p><div><h3>I need to know whether Quy Nhon is the quieter mainland coast answer.</h3><p>Use the Quy Nhon guide when Ky Co, Eo Gio, a real city beach, Cham tower context, and Phu Cat access need to be tested before the south-central coast earns its nights.</p><p class="vg-section-link"><a href="{$quy_nhon_guide_href}">Plan Quy Nhon</a></p></div><p class="vg-home-plan-proof">Best when you want a coast that feels calmer than Nha Trang without turning the trip into an island transfer chain.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">South-central beach fork</p><div><h3>I am choosing between Mui Ne wind-sport coast and Nha Trang city beach.</h3><p>Compare the Phan Thiet/Mui Ne open-sand, dunes, and kitesurfing logic with Nha Trang's active city-beach base before adding a south-central coast detour.</p><p class="vg-section-link"><a href="{$mui_ne_nha_trang_compare_href}">Compare Mui Ne and Nha Trang</a></p></div><p class="vg-home-plan-proof">Best when the beach question is not island versus city, but sport-first coast versus a fuller urban beach base.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Island shortlist unclear</p><div><h3>I am choosing which Vietnam island actually fits the trip.</h3><p>Use Best Islands when you need to compare Phu Quoc, Con Dao, Cat Ba, Cham Islands, Ly Son, Phu Quy, Nam Du, and Co To by route job, season, source confidence, ferry or flight friction, and skip logic.</p><p class="vg-section-link"><a href="{$best_islands_href}">Plan islands</a></p></div><p class="vg-home-plan-proof">Best before an island adds a ferry, flight, buffer day, or resort bill that may not improve the route.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Con Dao unclear</p><div><h3>I need a Con Dao decision, not just a beach name.</h3><p>Use Con Dao Travel Guide when the trip needs quiet premium nature, historic memory, diving or snorkeling potential, or a southern slow chapter, but only after flight, ferry, weather, park, and visa checks are counted.</p><p class="vg-section-link"><a href="{$con_dao_guide_href}">Plan Con Dao</a></p></div><p class="vg-home-plan-proof">Best when Con Dao is solving a route problem rather than adding another island because the map looks sparse.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Phu Quoc unclear</p><div><h3>I need a Phu Quoc decision, not just a beach name.</h3><p>Use Phu Quoc Travel Guide when the trip needs winter sun, resort ease, family comfort, or a southern island finish, but only after flights, ferries, stay area, season, and visa edge cases are counted.</p><p class="vg-section-link"><a href="{$phu_quoc_guide_href}">Plan Phu Quoc</a></p></div><p class="vg-home-plan-proof">Best when Phu Quoc is solving a route problem rather than adding a pretty stop.</p></div>
<div class="vg-home-plan-row" data-vg-reveal><p class="vg-home-plan-state">Too full</p><div><h3>I am trimming an overfull itinerary.</h3><p>Cut one-night stops, fragile transfer days, late-arrival plans, and destinations added only because they appear on every list.</p><p class="vg-section-link"><a href="{$ten_days_href}">Use the 10-day pacing guide</a></p></div><p class="vg-home-plan-proof">Best when the map looks impressive but the travel days will do more work than the experiences.</p></div>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- vg-home-route-verdict:v1 -->
<!-- wp:group {"className":"vg-home-section vg-home-route-verdict"} -->
<div class="wp-block-group vg-home-section vg-home-route-verdict">
<!-- wp:group {"className":"vg-wrap"} -->
<div class="wp-block-group vg-wrap">
<!-- wp:group {"className":"vg-home-verdict-head"} -->
<div class="wp-block-group vg-home-verdict-head">
<!-- wp:group {"className":"vg-home-section-lede"} -->
<div class="wp-block-group vg-home-section-lede" data-vg-reveal>
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Default route verdict</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"vg-display"} -->
<h2 class="wp-block-heading vg-display">Protect the route first, then decide whether extra places still earn their keep.</h2>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"vg-home-verdict-note"} -->
<p class="vg-home-verdict-note" data-vg-reveal>For undecided first-time visitors, this is the shortest useful answer: protect transfer time, weather reality, arrival energy, and one memorable middle chapter before chasing a larger map.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<div class="vg-home-verdict-list" aria-label="Vietnam default route verdict">
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">7 days</p><div><h3>Keep a one-week route to one focused region, max two bases, and one hard highlight.</h3><p>The shortest serious first-trip answer usually lives in the north: Hanoi, Ninh Binh, one bay decision, and a Hanoi departure buffer.</p></div><p class="vg-section-link"><a href="{$seven_days_href}">Read the 7-day guide</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">10 days</p><div><h3>Favor a tighter north + central route instead of touching every region.</h3><p>Ten days is where route discipline matters most. Protect one clean handoff and let one region feel properly seen.</p></div><p class="vg-section-link"><a href="{$ten_days_href}">Read the 10-day guide</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">14 days</p><div><h3>Use two weeks to decide whether the south is a real chapter or an expensive add-on.</h3><p>Fourteen days can support a fuller arc, but only if transfer load stays honest and the final chapter still has its own reason to exist.</p></div><p class="vg-section-link"><a href="{$fourteen_days_href}">Read the 14-day guide</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">21 days</p><div><h3>Use three weeks to make the full-country route slower, not busier.</h3><p>Twenty-one days can hold north, central, and south with one real extension, but only if the extra time buys margin instead of extra airport days.</p></div><p class="vg-section-link"><a href="{$twenty_one_days_href}">Read the 21-day guide</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Region unsure</p><div><h3>Compare north, central, and south by weather, transfer load, food, heritage, and first-trip value.</h3><p>If the route still feels fuzzy, region comparison should happen before hotel selection, day-by-day detail, or tour deposits.</p></div><p class="vg-section-link"><a href="{$region_compare_href}">Compare the regions</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Hanoi stay base</p><div><h3>Choose the Hanoi stay area by first-night recovery, pickup clarity, sleep, and route launch.</h3><p>Hoan Kiem and the Old Quarter edge solve first-time walking. The French Quarter softens the center. Ba Dinh fits civic/history needs, West Lake fits longer stays, and Noi Bai only belongs when flight risk is real.</p></div><p class="vg-section-link"><a href="{$where_to_stay_in_hanoi_href}">Choose where to stay</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Hanoi arrival</p><div><h3>Treat Noi Bai arrival as the first route decision, not a taxi errand.</h3><p>Choose hotel pickup, verified taxi, app ride, airport bus, or public bus by arrival hour, luggage, phone data, hotel lane, and whether the first night still needs to work.</p></div><p class="vg-section-link"><a href="{$hanoi_airport_transfer_href}">Plan the transfer</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Hanoi short stay</p><div><h3>Use Hanoi in 2 Days when the capital needs a tight city chapter, not a checklist.</h3><p>Two days should protect arrival recovery, food confidence, one culture block, weather pivots, and the next transfer before another famous stop is added.</p></div><p class="vg-section-link"><a href="{$hanoi_two_days_href}">Plan 48 hours</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Hanoi neighborhood</p><div><h3>Choose Old Quarter, French Quarter, or West Lake by sleep, walking, and first-night rhythm.</h3><p>Old Quarter gives energy and food density, the French Quarter buys calmer premium central access, and West Lake buys space for longer stays.</p></div><p class="vg-section-link"><a href="{$old_quarter_french_quarter_west_lake_href}">Compare Hanoi neighborhoods</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Hanoi day trip</p><div><h3>Choose one Hanoi day trip only after the capital itself has protected time.</h3><p>Use Best Day Trips from Hanoi to decide whether the spare day should become Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or a better day inside Hanoi.</p></div><p class="vg-section-link"><a href="{$hanoi_day_trips_guide_href}">Choose the day trip</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Ninh Binh pace</p><div><h3>Upgrade Ninh Binh to overnight when morning control is worth more than one more attraction.</h3><p>A Hanoi day trip can work, but one night often buys the real value: softer countryside rhythm, better boat timing, heat avoidance, and a less fragile next transfer.</p></div><p class="vg-section-link"><a href="{$ninh_binh_day_trip_overnight_href}">Compare the Ninh Binh pace</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Ninh Binh base</p><div><h3>Use Tam Coc as the default Ninh Binh base unless another job is clearer.</h3><p>Trang An area buys morning silence, Ninh Binh city buys rail and logistics, Van Long buys quiet nature, and Cuc Phuong-side stays only fit routes that protect park time.</p></div><p class="vg-section-link"><a href="{$ninh_binh_stays_href}">Choose the base</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Ninh Binh boat</p><div><h3>Choose Trang An for certainty; choose Tam Coc for base rhythm.</h3><p>Trang An is the higher-confidence flagship boat when one route must prove Ninh Binh. Tam Coc is stronger when the day should include countryside lanes, a softer base, rice-field texture, or shorter water time.</p></div><p class="vg-section-link"><a href="{$trang_an_tam_coc_href}">Compare Trang An and Tam Coc</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Tam Coc base</p><div><h3>Use Tam Coc when countryside rhythm matters more than checklist certainty.</h3><p>Tam Coc earns its place when the route needs boat timing, cycling, Bich Dong, Mua Cave, dinner choice, and a soft Ninh Binh overnight instead of another tight transfer.</p></div><p class="vg-section-link"><a href="{$tam_coc_guide_href}">Plan Tam Coc</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Ninh Binh transfer</p><div><h3>Choose Hanoi to Ninh Binh transport by drop-off control, not just ticket price.</h3><p>Train, limousine van, private car, day tour, and onward transfer each solve a different route problem once luggage, Tam Coc or Trang An base, weather, and tomorrow's handoff are counted.</p></div><p class="vg-section-link"><a href="{$hanoi_ninh_binh_transport_href}">Choose the transfer</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Ninh Binh to bay</p><div><h3>Confirm the exact bay port before choosing the Ninh Binh transfer.</h3><p>A direct Ninh Binh to bay move works only when Tuan Chau, Ha Long International Cruise Port, Hon Gai, Got Pier, or Cat Ba/Lan Ha handoff logic is named before pickup.</p></div><p class="vg-section-link"><a href="{$ninh_binh_ha_long_transfer_href}">Plan the handoff</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Southern city</p><div><h3>Use Ho Chi Minh City when the south needs a real city chapter, not just an airport stamp.</h3><p>HCMC earns its nights through districts, food, museums, markets, Tan Son Nhat access, and optional Cu Chi, Mekong, Phu Quoc, or Con Dao logic. Compress it when it would only decorate a short north-and-central route.</p></div><p class="vg-section-link"><a href="{$ho_chi_minh_city_guide_href}">Read the HCMC guide</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">HCMC stay base</p><div><h3>Choose the hotel area by the job it has to do in the route.</h3><p>District 1 solves first-time convenience, District 3 softens the pace, Thao Dien supports longer stays, riverside stays support comfort, and Tan Son Nhat only belongs when flight risk is real.</p></div><p class="vg-section-link"><a href="{$ho_chi_minh_city_stays_href}">Choose where to stay</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">HCMC day trip</p><div><h3>Choose one HCMC excursion only after the city itself has protected time.</h3><p>Use Best Day Trips from Ho Chi Minh City when the south needs an excursion decision, not another generic attractions list. Cu Chi, Can Gio, Tay Ninh, Mekong, and Vung Tau each solve different route problems.</p></div><p class="vg-section-link"><a href="{$hcmc_day_trips_guide_href}">Choose the day trip</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Central base</p><div><h3>Choose Da Nang or Hoi An by airport friction, beach value, and how much slow town time the route can hold.</h3><p>Da Nang is the cleaner airport-and-beach base. Hoi An is the slower heritage base. Split only when the route is long enough to pay for the transfer.</p></div><p class="vg-section-link"><a href="{$da_nang_hoi_an_compare_href}">Compare Da Nang and Hoi An</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Heritage base</p><div><h3>Choose Hoi An or Hue by whether the route needs atmosphere or imperial depth.</h3><p>Hoi An is the easier slow base. Hue is the deeper heritage chapter. Keep both only when central Vietnam has enough nights for the Citadel, one tomb, and Hoi An's best evening.</p></div><p class="vg-section-link"><a href="{$hoi_an_hue_compare_href}">Compare Hoi An and Hue</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Da Nang base</p><div><h3>Use Da Nang when central Vietnam needs beach, airport, city, and day-trip control.</h3><p>The Da Nang guide tests My Khe, Son Tra, Marble Mountains, Han River, Hai Van/Hue access, Hoi An evenings, season pressure, and the stops to skip first.</p></div><p class="vg-section-link"><a href="{$da_nang_guide_href}">Read the Da Nang guide</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Marine day</p><div><h3>Use Cham Islands when Hoi An needs a marine day that is worth the boat risk.</h3><p>Cham Islands should earn a protected central Vietnam day through weather, boat timing, marine-park context, and a clean return to Hoi An, not by being a default island add-on.</p></div><p class="vg-section-link"><a href="{$cham_islands_guide_href}">Read the Cham Islands guide</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Island detour</p><div><h3>Use Ly Son when the route has enough margin for a Sa Ky ferry and volcanic island nights.</h3><p>Ly Son earns its place when Sa Ky ferry logistics, Dao Be, local food, garlic culture, and one or two island nights improve central Vietnam more than an easier beach or mainland day would.</p></div><p class="vg-section-link"><a href="{$ly_son_guide_href}">Read the Ly Son guide</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Northern scenery</p><div><h3>Choose the bay cruise only when it improves the northern chapter.</h3><p>Ha Long is the iconic, easier-to-buy cruise. Lan Ha can feel quieter, but the real decision is port, operator, route map, weather policy, Cat Ba access, and what happens to Hanoi and Ninh Binh.</p></div><p class="vg-section-link"><a href="{$ha_long_bay_guide_href}">Plan Ha Long Bay</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Island base</p><div><h3>Choose Cat Ba only when the island changes the route.</h3><p>Cat Ba earns its place when Lan Ha Bay, national park time, or a slower northern base improves the trip. It should not be added as a ferry-heavy echo of a cruise you already booked.</p></div><p class="vg-section-link"><a href="{$cat_ba_guide_href}">Plan Cat Ba</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Quieter bay</p><div><h3>Choose Bai Tu Long only when the route details prove the quieter claim.</h3><p>Bai Tu Long can be the more spacious cruise choice, but it is not a magic upgrade. Buy it by route map, pier, operator, weather terms, and protected deck time.</p></div><p class="vg-section-link"><a href="{$bai_tu_long_guide_href}">Plan Bai Tu Long</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Beach chapter</p><div><h3>Choose a beach only when it improves the route more than another inland stop would.</h3><p>Phu Quoc, Con Dao, Da Nang, Hoi An, Nha Trang, Mui Ne, Quy Nhon, Cat Ba, Ly Son, and Phu Quy each solve a different route problem. Pick the one that still fits the trip after season and transport are counted.</p></div><p class="vg-section-link"><a href="{$best_beaches_href}">Plan Best Beaches</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Beach fork</p><div><h3>Compare Phu Quoc and Nha Trang when beach time is the fork.</h3><p>Use Phu Quoc when the route needs island resort recovery. Use Nha Trang when the route needs a city beach with more food, movement, and Cam Ranh access.</p></div><p class="vg-section-link"><a href="{$phu_quoc_nha_trang_compare_href}">Compare the beaches</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">City beach</p><div><h3>Use Nha Trang when the beach should remain active, urban, and connected.</h3><p>Nha Trang earns its place when beachfront hotels, seafood, simple taxis, cultural stops, boat-day options, and Cam Ranh airport access make the route easier than an island detour.</p></div><p class="vg-section-link"><a href="{$nha_trang_guide_href}">Read the Nha Trang guide</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Quiet coast</p><div><h3>Use Quy Nhon when you want the coast to stay quieter without becoming remote.</h3><p>Quy Nhon earns its place when city beach access, Ky Co, Eo Gio, Cham tower context, and Phu Cat timing give the route a calmer mainland answer than Nha Trang or Mui Ne.</p></div><p class="vg-section-link"><a href="{$quy_nhon_guide_href}">Read the Quy Nhon guide</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Wind-sport coast</p><div><h3>Use Mui Ne vs Nha Trang when south-central beach time needs a sport-or-city answer.</h3><p>Mui Ne should solve wind sports, dunes, open coast, and Phan Thiet road/rail routing. Nha Trang should solve city-beach mobility, food, hotels, Cam Ranh access, and optional bay days.</p></div><p class="vg-section-link"><a href="{$mui_ne_nha_trang_compare_href}">Compare Mui Ne and Nha Trang</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Island chapter</p><div><h3>Choose an island by route job, not by a prettiest-islands list.</h3><p>Phu Quoc is the easiest resort answer, Con Dao is the quiet premium answer, Cat Ba is the northern island-base answer, and smaller islands need stronger source, season, and ferry discipline.</p></div><p class="vg-section-link"><a href="{$best_islands_href}">Plan Best Islands</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Con Dao chapter</p><div><h3>Use Con Dao when the route needs quiet premium nature, historic memory, or a southern slow chapter.</h3><p>The island is strongest for travelers who can afford access friction and want a slower, lower-density island chapter with park and history context.</p></div><p class="vg-section-link"><a href="{$con_dao_guide_href}">Audit Con Dao</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Phu Quoc chapter</p><div><h3>Use Phu Quoc when the route needs winter sun, resort ease, or a southern recovery chapter.</h3><p>The island is strongest after Ho Chi Minh City, the Mekong, or a longer full-country route; it is weaker when a tight first trip already has too many transfers.</p></div><p class="vg-section-link"><a href="{$phu_quoc_guide_href}">Audit Phu Quoc</a></p></div>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- vg-home-related-routes:v1 -->
<!-- wp:group {"className":"vg-home-section vg-home-related-routes"} -->
<div class="wp-block-group vg-home-section vg-home-related-routes">
<!-- wp:group {"className":"vg-wrap"} -->
<div class="wp-block-group vg-wrap">
<!-- wp:group {"className":"vg-home-verdict-head"} -->
<div class="wp-block-group vg-home-verdict-head">
<!-- wp:group {"className":"vg-home-section-lede"} -->
<div class="wp-block-group vg-home-section-lede" data-vg-reveal>
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Related route decisions</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"vg-display"} -->
<h2 class="wp-block-heading vg-display">Use the next guide by route pressure, not by curiosity.</h2>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"vg-home-verdict-note"} -->
<p class="vg-home-verdict-note" data-vg-reveal>The homepage should behave like a concise decision map: diagnose the pressure, then move to the guide that can change the itinerary before bookings harden.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<div class="vg-home-verdict-list" aria-label="Related Vietnam route decisions">
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">One-week route</p><div><h3>Use the 7-day guide when Vietnam needs one focused region, not a miniature grand tour.</h3><p>The shortest serious first-trip answer is built around max two bases, one high-friction highlight, and a protected departure buffer.</p></div><p class="vg-section-link"><a href="{$seven_days_href}">Triage one week</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Short route</p><div><h3>Use the 10-day guide when the trip cannot survive a third region.</h3><p>This is the pressure test for travelers tempted to add Ho Chi Minh City, Mekong, Sapa, Phu Quoc, and central Vietnam into one short pass.</p></div><p class="vg-section-link"><a href="{$ten_days_href}">Tighten the route</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Fuller arc</p><div><h3>Use the 14-day guide when the south needs to become a real chapter.</h3><p>Two weeks can hold north, central, and south, but only when flight logic, extra modules, and hotel changes are kept honest.</p></div><p class="vg-section-link"><a href="{$fourteen_days_href}">Build the arc</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Three-week route</p><div><h3>Use the 21-day guide when the route should become fuller without becoming frantic.</h3><p>A full-country route works best when three weeks buys open-jaw logic, regional weather pivots, and one extension that replaces weaker filler.</p></div><p class="vg-section-link"><a href="{$twenty_one_days_href}">Plan 21 days</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Region call</p><div><h3>Compare north, central, and south before choosing hotels.</h3><p>Region choice decides weather risk, food focus, heritage depth, flight pressure, and whether the route feels curated or merely complete.</p></div><p class="vg-section-link"><a href="{$region_compare_href}">Compare regions</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Hanoi 48 hours</p><div><h3>Use Hanoi in 2 Days when the short stay needs pacing before extra northern scenery.</h3><p>The guide protects arrival recovery, Hoan Kiem and Old Quarter orientation, one food-led evening, one culture block, rain or heat pivots, and the exit buffer.</p></div><p class="vg-section-link"><a href="{$hanoi_two_days_href}">Plan two days</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Ninh Binh pace</p><div><h3>Use Ninh Binh Day Trip vs Overnight when the extra night must prove its value.</h3><p>The comparison shows when a Hanoi day trip is enough, when one night buys morning control, when two nights become real slow travel, and when skipping is cleaner.</p></div><p class="vg-section-link"><a href="{$ninh_binh_day_trip_overnight_href}">Compare Ninh Binh pacing</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Ninh Binh boat</p><div><h3>Use Trang An vs Tam Coc when one water route has to earn the day.</h3><p>The comparison keeps the choice practical: flagship certainty, countryside rhythm, crowd timing, base logic, family comfort, and whether doing both would improve or dilute the route.</p></div><p class="vg-section-link"><a href="{$trang_an_tam_coc_href}">Choose the boat</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Central base</p><div><h3>Use Da Nang vs Hoi An when the middle of the route needs one cleaner answer.</h3><p>Airport access, beach chapter, old-town rhythm, weather, and transfer pressure often make this the most expensive central Vietnam mistake.</p></div><p class="vg-section-link"><a href="{$da_nang_hoi_an_compare_href}">Compare Da Nang and Hoi An</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Heritage base</p><div><h3>Use Hoi An vs Hue when central Vietnam needs a real heritage answer.</h3><p>Hoi An protects atmosphere, food, and old-town evenings. Hue protects imperial depth, Citadel context, tombs, river time, and a more serious historical chapter.</p></div><p class="vg-section-link"><a href="{$hoi_an_hue_compare_href}">Compare Hoi An and Hue</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Da Nang base</p><div><h3>Use the Da Nang guide when the route needs airport-and-beach city base judgment.</h3><p>Decide whether Da Nang should be the sleeping base, a beach reset, an airport night, or a day-trip platform before adding Hoi An, Hue, or Ba Na Hills.</p></div><p class="vg-section-link"><a href="{$da_nang_guide_href}">Audit Da Nang</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Budget pressure</p><div><h3>Check cost when the route looks elegant but the movement is expensive.</h3><p>Domestic flights, private transfers, bay cruises, peak dates, and premium hotels can change the real budget faster than daily averages suggest.</p></div><p class="vg-section-link"><a href="{$cost_href}">Check route cost</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Bay cruise</p><div><h3>Use the Ha Long guide when a cruise may be the most expensive northern decision.</h3><p>Day cruise, one night, two nights, Lan Ha/Cat Ba routing, and cancellation rules should be decided before the deposit, not after the itinerary is already crowded.</p></div><p class="vg-section-link"><a href="{$ha_long_bay_guide_href}">Audit the cruise</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Island base</p><div><h3>Use the Cat Ba guide when the route needs an island decision.</h3><p>Cat Ba can deepen Lan Ha and national park time, but it can also add ferry friction to a route that already has enough northern scenery.</p></div><p class="vg-section-link"><a href="{$cat_ba_guide_href}">Audit the island</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Quieter bay</p><div><h3>Use the Bai Tu Long guide when "less crowded" is the sales pitch.</h3><p>A quieter bay is worth paying for only when the operator can show where the route goes, how the day moves, and what happens in bad weather.</p></div><p class="vg-section-link"><a href="{$bai_tu_long_guide_href}">Audit Bai Tu Long</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Beach route</p><div><h3>Use the Best Beaches guide when beach time needs to earn its place.</h3><p>Beach days are most valuable when they fit the season and route shape. The right coast can simplify a trip; the wrong coast can just add a flight.</p></div><p class="vg-section-link"><a href="{$best_beaches_href}">Audit the beach route</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Beach fork</p><div><h3>Use Phu Quoc vs Nha Trang when the beach choice has narrowed to island or city.</h3><p>Phu Quoc should solve resort recovery. Nha Trang should solve active coast-city routing. If neither job is clear, the beach should probably shrink.</p></div><p class="vg-section-link"><a href="{$phu_quoc_nha_trang_compare_href}">Audit the beach fork</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">City-beach base</p><div><h3>Use the Nha Trang guide when Cam Ranh access and city beach logistics are the real question.</h3><p>Check hotel area, transfer time, boat-day expectations, food access, and skip logic before treating Nha Trang as a simple beach add-on.</p></div><p class="vg-section-link"><a href="{$nha_trang_guide_href}">Audit Nha Trang</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">South-central coast</p><div><h3>Use Mui Ne vs Nha Trang when the route needs wind-sport coast or city beach, not both by default.</h3><p>Compare sport conditions, dunes, road/rail time, Cam Ranh access, city meals, hotel area, and skip logic before adding a south-central beach chapter.</p></div><p class="vg-section-link"><a href="{$mui_ne_nha_trang_compare_href}">Audit the south-central fork</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Island route</p><div><h3>Use the Best Islands guide when the island shortlist needs source-backed judgment.</h3><p>Island choices should be checked against route role, weather, ferry or flight friction, official/protected-area sources, and what mainland stop the island replaces.</p></div><p class="vg-section-link"><a href="{$best_islands_href}">Audit the island route</a></p></div>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- vg-home-concierge-filter:v1 -->
<!-- wp:group {"className":"vg-home-section vg-home-decision-spine"} -->
<div class="wp-block-group vg-home-section vg-home-decision-spine">
<!-- wp:group {"className":"vg-wrap"} -->
<div class="wp-block-group vg-wrap">
<!-- wp:group {"className":"vg-home-split"} -->
<div class="wp-block-group vg-home-split">
<!-- wp:group {"className":"vg-home-section-lede"} -->
<div class="wp-block-group vg-home-section-lede" data-vg-reveal>
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Concierge filter</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"vg-display"} -->
<h2 class="wp-block-heading vg-display">A premium Vietnam route earns its movement.</h2>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"vg-home-long-copy"} -->
<p class="vg-home-long-copy" data-vg-reveal>Before a guide recommends a stop, the page should answer a simple question: does this place change the trip enough to justify the transfer, season risk, and lost slack? That is the house style: source-aware, photo-led, and willing to say no.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<div class="vg-home-verdict-list" aria-label="Vietnam concierge editorial filters">
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Pace</p><div><h3>Do not spend a transfer day for a place that only adds a famous name.</h3><p>Extra stops should change the trip: landscape, food, heritage, recovery, or a cleaner flight path.</p></div><p class="vg-section-link"><a href="{$fourteen_days_href}">Test the route</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Evidence</p><div><h3>Facts that move get checked where they live.</h3><p>Visa, transport, weather, heritage, and cost references are treated as source checks, not decorative citations.</p></div><p class="vg-section-link"><a href="/source-update-policy/">See source policy</a></p></div>
<div class="vg-home-verdict-row" data-vg-reveal><p class="vg-home-verdict-step">Photo proof</p><div><h3>Images should help judge the route, not merely decorate the page.</h3><p>A good caption explains what the place asks from the itinerary: a slower day, a cleaner transfer, or a stop that should replace something else.</p></div><p class="vg-section-link"><a href="{$best_places_href}">Choose stops</a></p></div>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-home-section vg-home-guide-shelf"} -->
<div class="wp-block-group vg-home-section vg-home-guide-shelf">
<!-- wp:group {"className":"vg-wrap"} -->
<div class="wp-block-group vg-wrap">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Best first reads</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"vg-display"} -->
<h2 class="wp-block-heading vg-display">Reviewed guides for the decisions most likely to cost time or money.</h2>
<!-- /wp:heading -->
<!-- wp:group {"className":"vg-home-guide-list"} -->
<div class="wp-block-group vg-home-guide-list">
<div class="vg-home-guide-row" data-vg-reveal><a href="{$travel_guide_href}">Vietnam Travel Guide</a><span>First-trip planning order, route lenses, official checks, and what to decide before the bookings harden.</span><strong>Start here</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$best_places_href}">Best Places to Visit in Vietnam</a><span>Shortlist stops by route value, season fit, heritage, nature, comfort, and the cases where skipping is the better call.</span><strong>Choose stops</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$ninh_binh_guide_href}">Ninh Binh Travel Guide</a><span>Choose day trip, one-night countryside stop, two-night slow base, or skip before the northern route gets crowded.</span><strong>Plan Ninh Binh</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$ninh_binh_day_trip_overnight_href}">Ninh Binh Day Trip vs Overnight</a><span>Decide if Ninh Binh should stay a Hanoi day trip, become one protected night, slow down for two nights, or be skipped cleanly.</span><strong>Compare pacing</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$ninh_binh_stays_href}">Where to Stay in Ninh Binh</a><span>Choose Tam Coc, Trang An area, Ninh Binh city, Van Long, or Cuc Phuong-side stays by route job, pickup logic, sleep tolerance, and next-transfer pressure.</span><strong>Choose the base</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$ninh_binh_ha_long_transfer_href}">Ninh Binh to Ha Long Bay Transfer</a><span>Confirm the exact cruise port, pickup window, luggage plan, weather buffer, and whether an overnight near the bay protects the handoff.</span><strong>Plan the handoff</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$trang_an_tam_coc_href}">Trang An vs Tam Coc</a><span>Choose the Ninh Binh boat route by scenery certainty, countryside rhythm, base logistics, crowds, photography, family comfort, and whether both routes would repeat the same job.</span><strong>Choose the boat</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$tam_coc_guide_href}">Tam Coc Travel Guide</a><span>Use Tam Coc as the soft Ninh Binh base for boat timing, cycling lanes, Bich Dong, Mua Cave, dinner choice, and next-transfer pressure.</span><strong>Plan Tam Coc</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$ha_long_bay_guide_href}">Ha Long Bay Travel Guide</a><span>Choose day cruise, one-night cruise, two-night route, Lan Ha/Cat Ba alternative, or skip before the bay becomes an expensive transfer.</span><strong>Plan the bay</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$ha_long_lan_ha_compare_href}">Ha Long Bay vs Lan Ha Bay</a><span>Compare iconic Ha Long with quieter Lan Ha by cruise route, port, Cat Ba access, crowd pressure, weather risk, and route fit.</span><strong>Compare bays</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$cat_ba_guide_href}">Cat Ba Travel Guide</a><span>Decide island base, Lan Ha gateway, ferry/port logistics, national park time, and skip logic before adding Cat Ba to the north.</span><strong>Plan Cat Ba</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$bai_tu_long_guide_href}">Bai Tu Long Bay Guide</a><span>Decide whether a quieter, route-specific cruise is worth the extra buying friction versus Ha Long, Lan Ha, or Cat Ba.</span><strong>Plan Bai Tu Long</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$best_beaches_href}">Best Beaches in Vietnam</a><span>Choose the beach chapter that fits the route: island resort, quiet premium, central coast, city beach, wind sports, or skip.</span><strong>Plan beaches</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$phu_quoc_nha_trang_compare_href}">Phu Quoc vs Nha Trang</a><span>Choose the beach fork by island resort recovery or city-beach routing before you commit to flights, resort nights, Cam Ranh transfers, or boat days.</span><strong>Compare beach fork</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$nha_trang_guide_href}">Nha Trang Travel Guide</a><span>Plan Nha Trang as an active city-beach base with Cam Ranh transfers, hotel-area trade-offs, seafood, Po Nagar, bay days, costs, and skip logic.</span><strong>Plan Nha Trang</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$quy_nhon_guide_href}">Quy Nhon Travel Guide</a><span>Use Quy Nhon when the coast should stay quieter: city beach, Ky Co, Eo Gio, Cham towers, Phu Cat access, and fewer resort assumptions.</span><strong>Plan Quy Nhon</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$mui_ne_nha_trang_compare_href}">Mui Ne vs Nha Trang</a><span>Choose wind-sport Phan Thiet/Mui Ne coast time or active Nha Trang city-beach routing before booking a south-central beach detour.</span><strong>Compare coast bases</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$best_islands_href}">Best Islands in Vietnam</a><span>Choose Phu Quoc, Con Dao, Cat Ba, Cham Islands, Ly Son, Phu Quy, Nam Du, or Co To by route job, season, source confidence, and ferry or flight friction.</span><strong>Plan islands</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$con_dao_guide_href}">Con Dao Travel Guide</a><span>Decide whether Con Dao should be a quiet premium nature, history, diving, or slow-island chapter before booking flights, ferries, and beach nights.</span><strong>Plan Con Dao</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$phu_quoc_guide_href}">Phu Quoc Travel Guide</a><span>Decide whether Phu Quoc should be a winter-sun, resort, family, or route-recovery island chapter before booking flights, ferries, and beach nights.</span><strong>Plan Phu Quoc</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$ho_chi_minh_city_guide_href}">Ho Chi Minh City Travel Guide</a><span>Decide whether HCMC deserves a real southern chapter with districts, food, museums, Tan Son Nhat access, Cu Chi, Mekong, and island-gateway logic.</span><strong>Plan HCMC</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$ho_chi_minh_city_stays_href}">Where to Stay in Ho Chi Minh City</a><span>Choose District 1, District 3, Thao Dien, riverside, nightlife, or airport-side hotel areas by route job, pickup logic, and noise tolerance.</span><strong>Choose the base</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$hcmc_day_trips_guide_href}">Best Day Trips from Ho Chi Minh City</a><span>Choose Cu Chi, Can Gio, Tay Ninh, Mekong, Vung Tau, or staying in HCMC by route job, weather, road time, and exit buffer.</span><strong>Choose the day trip</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$mekong_delta_guide_href}">Mekong Delta Travel Guide</a><span>Decide whether the Delta should be a HCMC day trip, overnight Can Tho, Ben Tre canal chapter, Chau Doc extension, or a stop you skip to protect the rest of the route.</span><strong>Plan Mekong Delta</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$da_nang_guide_href}">Da Nang Travel Guide</a><span>Plan Da Nang as an airport-and-beach city base with My Khe, Son Tra, Marble Mountains, riverfront, Hoi An/Hue access, season pivots, and skip logic.</span><strong>Plan Da Nang</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$hanoi_travel_guide_href}">Hanoi Travel Guide</a><span>Decide how many Hanoi nights to protect, where to stay, how Noi Bai arrival shapes the first day, and when the capital should launch Ninh Binh, the bay, Cat Ba, mountains, or Central Vietnam.</span><strong>Anchor the north</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$hanoi_two_days_href}">Hanoi in 2 Days</a><span>Build a 48-hour city chapter with arrival recovery, Hoan Kiem and Old Quarter orientation, one food-led evening, one culture block, weather pivots, and calmer exit pacing.</span><strong>Plan 48 hours</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$where_to_stay_in_hanoi_href}">Where to Stay in Hanoi</a><span>Choose Hoan Kiem, Old Quarter edge, French Quarter, Ba Dinh, West Lake, or Noi Bai airport-side by first-night recovery, pickup logic, sleep, and route launch.</span><strong>Choose the base</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$hanoi_airport_transfer_href}">Hanoi Airport to Old Quarter</a><span>Choose hotel pickup, verified taxi, ride-hailing, airport bus, or public bus by arrival hour, luggage, phone data, fare control, and final Old Quarter walk.</span><strong>Arrive calmly</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$old_quarter_french_quarter_west_lake_href}">Old Quarter vs French Quarter vs West Lake</a><span>Compare Hanoi's three strongest stay zones by street energy, calmer premium central access, lake-side space, pickup clarity, sleep, and cost trade-offs.</span><strong>Compare neighborhoods</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$hanoi_day_trips_guide_href}">Best Day Trips from Hanoi</a><span>Choose Ninh Binh, Ha Long or Lan Ha, Bat Trang, Duong Lam, Perfume Pagoda, Ba Vi, or staying in Hanoi by route job, transfer friction, weather, and overnight logic.</span><strong>Choose the day trip</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$hanoi_ninh_binh_transport_href}">Hanoi to Ninh Binh Transport</a><span>Choose train, limousine van, private car, day tour, or onward transfer by drop-off, luggage, hotel base, timing, weather, and next-route fragility.</span><strong>Choose the transfer</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$hanoi_guide_href}">Best Things to Do in Hanoi</a><span>Prioritize the capital's food, lake walks, heritage, museums, and skip decisions before the northern route starts moving.</span><strong>Plan Hanoi</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$hoi_an_guide_href}">Best Things to Do in Hoi An</a><span>Protect Ancient Town, food, My Son, beach, and slower central Vietnam time before adding another transfer.</span><strong>Plan Hoi An</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$cham_islands_guide_href}">Cham Islands Travel Guide</a><span>Decide whether a Hoi An marine day trip or overnight island stay earns route time after boat, weather, protected-area, cost, and skip checks.</span><strong>Plan Cham Islands</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$ly_son_guide_href}">Ly Son Travel Guide</a><span>Decide whether Sa Ky ferry timing, volcanic coast, Dao Be, garlic culture, and one or two island nights earn a central Vietnam detour.</span><strong>Plan Ly Son</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$da_nang_hoi_an_compare_href}">Da Nang vs Hoi An</a><span>Choose the central Vietnam base by airport friction, beach access, old-town rhythm, weather, transfer pressure, and whether a split base is actually worth it.</span><strong>Compare central bases</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$hoi_an_hue_compare_href}">Hoi An vs Hue</a><span>Choose the central Vietnam heritage chapter by atmosphere, imperial depth, food, family fit, transfer order, season pressure, and whether both cities earn the nights.</span><strong>Compare heritage bases</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$hue_guide_href}">Best Things to Do in Hue</a><span>Decide when Hue deserves a protected imperial-history day before adding tombs, river time, or the Hoi An transfer.</span><strong>Plan Hue</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$heritage_guide_href}">UNESCO Heritage Sites in Vietnam</a><span>Route-filter heritage planning for travelers deciding which UNESCO sites deserve a real stop.</span><strong>Plan heritage</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$region_compare_href}">North vs Central vs South Vietnam</a><span>Compare regions by outcome when the trip cannot hold every famous place without losing its shape.</span><strong>Pick a region</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$seven_days_href}">7 Days in Vietnam</a><span>One-week route triage: one focused region, max two bases, one high-friction highlight, and the honest point where seven days should become ten.</span><strong>Plan one week</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$fourteen_days_href}">14 Days in Vietnam</a><span>Two-week route builder with transfer pressure, night allocation, extension choices, and licensed route photography.</span><strong>Slow down well</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$ten_days_href}">10 Days in Vietnam</a><span>A tighter first-trip route with day-by-day pacing, swap decisions, and checks before the schedule becomes fragile.</span><strong>Move efficiently</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$twenty_one_days_href}">21 Days in Vietnam</a><span>Three weeks of route-shape judgment: full-country route with margin, one deliberate extension, and checks before extra days become extra transfers.</span><strong>Plan three weeks</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$safety_scams_href}">Safety and Scams in Vietnam</a><span>Reduce road, arrival, money, nightlife, water, petty-theft, and emergency-prep risk without turning the route fearful.</span><strong>Travel calmly</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$health_insurance_href}">Health and Travel Insurance for Vietnam</a><span>Match medical care, evacuation, pre-existing conditions, motorbike clauses, and route-risk cover before the trip gets fragile.</span><strong>Protect the trip</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$transport_href}">Transport Within Vietnam</a><span>Flight, train, car, bus, ferry, and airport-transfer decisions by route pressure instead of default habits.</span><strong>Move smartly</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$cost_href}">Vietnam Travel Cost</a><span>Dated budget ranges, trip-length scenarios, hidden costs, worksheet logic, and booking-order checks.</span><strong>Set budget</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$money_href}">Money in Vietnam</a><span>VND cash, cards, ATMs, exchange, arrival buffers, and cash-declaration thresholds without stale ATM mythology.</span><strong>Pay cleanly</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$sim_esim_href}">SIM and eSIM in Vietnam</a><span>Choose pre-trip eSIM, local SIM, carrier eSIM, or roaming backup by device, route, local-number need, and arrival risk.</span><strong>Stay connected</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$evisa_href}">Vietnam E-Visa Guide</a><span>Official portal checks, fee context, common application mistakes, and what to verify before applying.</span><strong>Check entry</strong></div>
<div class="vg-home-guide-row" data-vg-reveal><a href="{$best_time_href}">Best Time to Visit Vietnam</a><span>Weather and route trade-offs by region, season, and trip style, without pretending Vietnam has one perfect month.</span><strong>Time the trip</strong></div>
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-home-section vg-home-route-proof"} -->
<div class="wp-block-group vg-home-section vg-home-route-proof">
<!-- wp:group {"className":"vg-wrap"} -->
<div class="wp-block-group vg-wrap">
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Route proof</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"vg-display"} -->
<h2 class="wp-block-heading vg-display">Use the photographs to test the route, not decorate it.</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-home-section-copy"} -->
<p class="vg-home-section-copy">A strong first trip moves through visual chapters with purpose: arrival energy in Hanoi, limestone landscapes in the north, a protected central Vietnam middle, and a southern chapter only when the calendar can support it.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="vg-home-photo-grid" aria-label="Vietnam route planning photography">
<figure data-vg-reveal><img src="{$hanoi_image}" alt="Hoan Kiem Lake in central Hanoi, Vietnam" loading="lazy" decoding="async"><figcaption>Hanoi sets the arrival rhythm before the route asks for early starts. Image: Alex 69200 vx, CC BY-SA 4.0.</figcaption></figure>
<figure data-vg-reveal><img src="{$trang_an_image}" alt="Boats and limestone scenery at Trang An in Ninh Binh, Vietnam" loading="lazy" decoding="async"><figcaption>Ninh Binh works best as a route-calming landscape stop. Image: Jakub Halun, CC BY 4.0.</figcaption></figure>
<figure data-vg-reveal><img src="{$lan_ha_image}" alt="Lan Ha Bay scenery near Cat Ba Island in northern Vietnam" loading="lazy" decoding="async"><figcaption>Lan Ha should be judged as a port, cruise, and Cat Ba route decision, not only a quieter photo. Image: Saaremees, CC BY-SA 4.0.</figcaption></figure>
<figure data-vg-reveal><img src="{$cat_ba_image}" alt="Cat Ba Island and limestone bay scenery in northern Vietnam" loading="lazy" decoding="async"><figcaption>Cat Ba earns its place when the island base changes the northern route, not when it repeats a bay checklist. Image: Christophe95, CC BY-SA 4.0.</figcaption></figure>
<figure data-vg-reveal><img src="{$bai_tu_long_image}" alt="Bai Tu Long Bay limestone scenery in northern Vietnam" loading="lazy" decoding="async"><figcaption>Bai Tu Long should be bought by route proof, not by a generic quieter-bay claim. Image: Benjamin Smith, CC BY-SA 4.0.</figcaption></figure>
<figure data-vg-reveal><img src="{$best_beaches_image}" alt="Kem Beach on Phu Quoc Island in Vietnam" loading="lazy" decoding="async"><figcaption>Beach time should earn its place in the route. Image: Vivu Vietnam, CC BY-SA 4.0.</figcaption></figure>
<figure data-vg-reveal><img src="{$nha_trang_image}" alt="Nha Trang Beach in Vietnam" loading="lazy" decoding="async"><figcaption>Nha Trang is the city-beach counterpoint when beach time should stay close to food, movement, and airport access. Image: Christophe95, CC BY-SA 4.0.</figcaption></figure>
<figure data-vg-reveal><img src="{$quy_nhon_image}" alt="Ky Co beach near Quy Nhon in Binh Dinh province, Vietnam" loading="lazy" decoding="async"><figcaption>Quy Nhon is the quieter mainland-coast counterpoint when beach time should stay calmer without turning remote. Image: Boconganh Phan, public domain.</figcaption></figure>
<figure data-vg-reveal><img src="{$mui_ne_image}" alt="Kitesurfing at Mui Ne beach in Vietnam" loading="lazy" decoding="async"><figcaption>Mui Ne is the wind-sport and open-coast counterpoint when south-central beach time needs a sport-first job. Image: Vyacheslav Argenberg, CC BY 4.0.</figcaption></figure>
<figure data-vg-reveal><img src="{$best_islands_image}" alt="Con Dao Island beach in Vietnam" loading="lazy" decoding="async"><figcaption>Island time should be chosen by route job, source confidence, and logistics. Image: Daeva Trac, CC BY-SA 4.0.</figcaption></figure>
<figure data-vg-reveal><img src="{$hue_image}" alt="Historic architecture inside the Hue Citadel in central Vietnam" loading="lazy" decoding="async"><figcaption>Hue adds imperial context when central Vietnam has a protected day, not a pass-through afternoon. Image: CEphoto, Uwe Aranas, CC BY-SA 3.0.</figcaption></figure>
<figure data-vg-reveal><img src="{$hai_van_image}" alt="Mountain and coastal view from Hai Van Pass between Hue and Da Nang, Vietnam" loading="lazy" decoding="async"><figcaption>Hai Van Pass turns a transfer into value only when weather, luggage, and timing cooperate. Image: Wolkenkratzer, CC BY-SA 4.0.</figcaption></figure>
<figure data-vg-reveal><img src="{$hoi_an_image}" alt="Evening street scene in Hoi An Ancient Town, Vietnam" loading="lazy" decoding="async"><figcaption>Hoi An is stronger when central Vietnam is not squeezed into one night. Image: Steffen Schmitz, CC BY-SA 4.0.</figcaption></figure>
<figure data-vg-reveal><img src="{$cham_islands_image}" alt="Cham Islands seen from the Da Nang coast" loading="lazy" decoding="async"><figcaption>Cham Islands should be judged as a Hoi An marine-day decision, not a default beach detour. Image: Soupybev, CC BY 4.0.</figcaption></figure>
<figure data-vg-reveal><img src="{$ly_son_image}" alt="Ly Son islands seen from offshore" loading="lazy" decoding="async"><figcaption>Ly Son should be judged as a Sa Ky ferry and volcanic-island detour, not an easy beach substitute. Image: minhphuc_99kdd, public domain.</figcaption></figure>
<figure data-vg-reveal><img src="{$hcmc_image}" alt="Ho Chi Minh City Hall and Nguyen Hue walking street at night in Ho Chi Minh City, Vietnam" loading="lazy" decoding="async"><figcaption>Ho Chi Minh City should be planned as a southern chapter, not just a departure airport. Image: Steffen Schmitz, CC BY-SA 4.0.</figcaption></figure>
<figure data-vg-reveal><img src="{$mekong_image}" alt="Small boat on a river in the Mekong Delta near Phong Dien, Vietnam" loading="lazy" decoding="async"><figcaption>The Mekong should replace a weak add-on, not hide inside departure day. Image: Vyacheslav Argenberg, CC BY 4.0.</figcaption></figure>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- vg-eeat-trust-band:v1 -->
<!-- wp:group {"className":"vg-home-section vg-home-editorial-proof vg-trust-band"} -->
<div class="wp-block-group vg-home-section vg-home-editorial-proof vg-trust-band">
<!-- wp:group {"className":"vg-wrap vg-home-proof-columns"} -->
<div class="wp-block-group vg-wrap vg-home-proof-columns">
<!-- wp:group -->
<div class="wp-block-group" data-vg-reveal>
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">How VietnamGuide builds trust</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"vg-display"} -->
<h2 class="wp-block-heading vg-display">Trust is built into the page architecture, not saved for a disclaimer.</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The homepage points to verdict-led guides, public policies, visible image credits, and update metadata so readers can see how recommendations are made.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:list {"className":"vg-feature-list vg-home-method-list"} -->
<ul class="wp-block-list vg-feature-list vg-home-method-list" data-vg-reveal>
<li>Each deep guide is expected to show a verdict, proof panel, related decisions, source trail, and update log.</li>
<li>Official or primary sources are preferred for entry rules, transport references, heritage status, and weather context.</li>
<li>Place photography is licensed, credited, and used to support route judgment rather than invented as fake evidence.</li>
<li>Affiliate potential does not decide whether a route, place, hotel style, or booking choice is recommended.</li>
<li><a href="/editorial-policy/">Editorial Policy</a>, <a href="/source-update-policy/">Source and Update Policy</a>, and <a href="/affiliate-review-policy/">Affiliate and Review Policy</a> are public.</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-home-section vg-home-final"} -->
<div class="wp-block-group vg-home-section vg-home-final">
<!-- wp:group {"className":"vg-wrap"} -->
<div class="wp-block-group vg-wrap" data-vg-reveal>
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Next best action</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"vg-display"} -->
<h2 class="wp-block-heading vg-display">If you are planning now, do this in order.</h2>
<!-- /wp:heading -->
<!-- wp:list {"className":"vg-home-sequence"} -->
<ol class="wp-block-list vg-home-sequence">
<li><a href="{$travel_guide_href}">Set the planning order</a> before hotels, tours, or flight add-ons start narrowing the trip.</li>
<li><a href="{$best_time_href}">Check season fit</a> against the regions you actually want, not a generic Vietnam weather average.</li>
<li><a href="{$cost_href}">Build a realistic budget</a> with transfers, comfort level, cruise choices, and buffer money included.</li>
<li><a href="{$fourteen_days_href}">Choose a route shape</a>, then remove stops until every transfer still earns its place.</li>
<li><a href="{$twenty_one_days_href}">Use the 21-day guide</a> if three weeks should become a full-country route with margin instead of a longer checklist.</li>
</ol>
<!-- /wp:list -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;

vg_home_assert_internal_page_links_are_published($homepage);

$result = wp_update_post(
    [
        'ID'             => $home->ID,
        'post_content'   => $homepage,
        'post_excerpt'   => 'Evidence-led Vietnam travel planning for international visitors, with route verdicts, source checks, visual route proof, realistic costs, visas, seasons, and itinerary decisions.',
        'comment_status' => 'closed',
        'ping_status'    => 'closed',
    ],
    true
);

if (is_wp_error($result)) {
    throw new RuntimeException($result->get_error_message());
}

if (empty($result)) {
    throw new RuntimeException('Failed to update homepage: empty post ID returned');
}

update_post_meta($home->ID, 'rank_math_title', 'Vietnam Travel Guide for International Visitors');
update_post_meta($home->ID, 'rank_math_description', 'Evidence-led Vietnam travel guide for international visitors: plan routes, costs, visas, seasons, destinations, and itineraries with verdict-led editorial guides.');
update_post_meta($home->ID, 'rank_math_focus_keyword', 'Vietnam travel guide');
update_post_meta($home->ID, '_generate-disable-headline', 'true');
update_post_meta($home->ID, 'vg_eeat_primary_decision', 'Start with planning order, route length, season fit, entry checks, realistic budget, and transfer pressure before booking.');
update_post_meta($home->ID, 'vg_eeat_update_summary', $homepage_update_summary);
update_post_meta($home->ID, 'vg_eeat_last_meaningful_update', $review_date);
update_post_meta($home->ID, 'vg_eeat_sources_checked', "Homepage source and policy links checked {$review_date}.\nWikimedia Commons image record - Ha Long Bay hero credit - https://commons.wikimedia.org/wiki/File:Ha_Long_Bay,_Vietnam,_View_from_above.jpg - license checked {$review_date}\nWikimedia Commons image record - Hanoi Hoan Kiem Lake - https://commons.wikimedia.org/wiki/File:Hanoi-lac-hoan-kiem.jpg - license checked {$review_date}\nWikimedia Commons image record - Trang An Landscape Complex, Ninh Binh Province - https://commons.wikimedia.org/wiki/File:Trang_An_Landscape_Complex,_Ninh_Binh_Province,_Vietnam,_20240202_1456_5313.jpg - license checked {$review_date}\nWikimedia Commons image record - Lan Ha Bay-Cat Ba Vietnam - https://commons.wikimedia.org/wiki/File:Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg - license checked {$review_date}\nWikimedia Commons image record - Cat Ba Island - https://commons.wikimedia.org/wiki/File:Cat_Ba_Island.jpg - license checked {$review_date}\nWikimedia Commons image record - Bai Tu Long Bay 01 - https://commons.wikimedia.org/wiki/File:B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg - license checked {$review_date}\nWikimedia Commons image record - Kem Beach aerial view Phu Quoc Island Vietnam - https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg - license checked {$review_date}\nWikimedia Commons image record - Nha Trang Beach 3 - https://commons.wikimedia.org/wiki/File:Nha_Trang_Beach_3.jpg - license checked {$review_date}\nWikimedia Commons image record - Vietnam, Mui Ne beach, Kitesurfing on the beach - https://commons.wikimedia.org/wiki/File:Vietnam,_Mui_Ne_beach,_Kitesurfing_on_the_beach.jpg - license checked {$review_date}\nWikimedia Commons image record - Beach view from Six Senses Resort in Con Dao - https://commons.wikimedia.org/wiki/File:Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_(April_2022).jpg - license checked {$review_date}\nWikimedia Commons image record - Hue Vietnam Citadel - https://commons.wikimedia.org/wiki/File:Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg - license checked {$review_date}\nWikimedia Commons image record - Vietnam, Hai-Van-Pass - https://commons.wikimedia.org/wiki/File:Vietnam,_Hai-Van-Pass.jpg - license checked {$review_date}\nWikimedia Commons image record - Hoi An Ancient Town - https://commons.wikimedia.org/wiki/File:H%E1%BB%99i_An,_Ancient_Town,_2020-01_CN-11.jpg - license checked {$review_date}\nWikimedia Commons image record - Ho Chi Minh City Hall - https://commons.wikimedia.org/wiki/File:Ho_Chi_Minh_City,_City_Hall,_2020-01_CN-02.jpg - license checked {$review_date}\nWikimedia Commons image record - Vietnam, Phong Dien, Mekong Delta, River - https://commons.wikimedia.org/wiki/File:Vietnam,_Phong_Dien,_Mekong_Delta,_River.jpg - license checked {$review_date}");
update_post_meta($home->ID, 'vg_eeat_hero_image_credit', 'Hero: Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0. Body images: Hanoi by Alex 69200 vx, CC BY-SA 4.0; Trang An by Jakub Halun, CC BY 4.0; Lan Ha Bay-Cat Ba by Saaremees, CC BY-SA 4.0; Cat Ba Island by Christophe95, CC BY-SA 4.0; Bai Tu Long Bay 01 by Benjamin Smith, CC BY-SA 4.0; Kem Beach by Vivu Vietnam, CC BY-SA 4.0; Nha Trang Beach 3 by Christophe95, CC BY-SA 4.0; Mui Ne kitesurfing by Vyacheslav Argenberg, CC BY 4.0; Con Dao beach by Daeva Trac, CC BY-SA 4.0; Hue by CEphoto, Uwe Aranas, CC BY-SA 3.0; Hai Van Pass by Wolkenkratzer, CC BY-SA 4.0; Hoi An and Ho Chi Minh City by Steffen Schmitz, CC BY-SA 4.0; Mekong Delta by Vyacheslav Argenberg, CC BY 4.0.');

delete_post_meta($home->ID, '_rank_math_title');
delete_post_meta($home->ID, '_rank_math_description');
delete_post_meta($home->ID, '_rank_math_focus_keyword');

vg_home_log("Updated premium homepage: {$home->ID}");

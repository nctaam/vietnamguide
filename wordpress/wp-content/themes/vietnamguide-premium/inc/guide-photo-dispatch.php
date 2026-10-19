<?php
/**
 * VietnamGuide Visual Dispatch & Photo Storytelling Component
 *
 * Implements an accessible, FT Weekend-grade photo essay shortcode
 * with responsive srcset, EXIF field notes, editorial captions,
 * zero-H2 TOC protection, and lightweight vanilla JS lightbox modal.
 *
 * Shortcode: [vg_photo_dispatch slug="..."]
 */

if (!defined('ABSPATH')) {
    exit;
}

function vg_get_visual_dispatches_data(): array
{
    return [
        'ha-long-dawn' => [
            'id' => 'ha-long-dawn',
            'title' => 'Dawn Over the Karst Labyrinth',
            'location' => 'Bái Tử Long & Hạ Long Bay',
            'province' => 'Quảng Ninh',
            'coordinates' => '20°54\'N 107°12\'E',
            'lead' => 'First light cuts through maritime mist, outlining limestone pillars formed over 500 million years of tropical karst evolution.',
            'base_image_slug' => 'ha-long-bay-vietnam-hero',
            'image_fallback' => 'assets/images/ha-long-bay-vietnam-hero.jpg',
            'wikimedia_commons_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg/1920px-Ha_Long_Bay%2C_Vietnam%2C_View_from_above.jpg',
            'aspect_ratio' => '16/9',
            'alt_text' => 'Misty limestone karsts rising from calm waters in Ha Long Bay at dawn',
            'exif' => [
                'focal_length' => '24mm',
                'aperture' => 'f/8.0',
                'shutter' => '1/250s',
                'iso' => '100',
                'elevation' => '142m ASL',
                'time_of_day' => '05:48 ICT',
            ],
            'field_notes' => 'Day-trip Route 1/2 entry costs 310,000 VND (250,000 VND bay fee + 60,000 VND port fee); overnight cruises include 550,000–750,000 VND permits. Adjacent Cát Bà Island connects via the Đồng Bài – Cái Viềng ferry (12,000 VND foot passenger) and Cát Bà National Park (80,000 VND entry).',
            'photographer' => 'Vyacheslav Argenberg',
            'license' => 'CC BY 4.0',
            'related_route' => '/destinations/ha-long-bay-travel-guide/',
            'related_label' => 'Read Ha Long Bay Field Guide',
        ],
        'hoi-an-dusk' => [
            'id' => 'hoi-an-dusk',
            'title' => 'Nocturnal Currents on the Hoài River',
            'location' => 'Old Quarter Waterfront, Hội An',
            'province' => 'Quảng Nam',
            'coordinates' => '15°52\'N 108°19\'E',
            'lead' => 'Silk lanterns illuminate timber shophouses preserved since the 16th-century international trading port era.',
            'base_image_slug' => 'home-editorial',
            'image_fallback' => 'assets/images/home-editorial.jpg',
            'wikimedia_commons_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/68/2024-12-20_Hoi_An_Old_Town_at_night_3.jpg/1920px-2024-12-20_Hoi_An_Old_Town_at_night_3.jpg',
            'aspect_ratio' => '16/9',
            'alt_text' => 'Lantern reflections dancing across the calm surface of the Hoai River in Hoi An at night',
            'exif' => [
                'focal_length' => '35mm',
                'aperture' => 'f/2.8',
                'shutter' => '1/60s',
                'iso' => '800',
                'elevation' => '4m ASL',
                'time_of_day' => '19:15 ICT',
            ],
            'field_notes' => 'Old Town heritage tickets cost 120,000 VND (5 sight coupons) and Hoài River wooden sampans charge 150,000–200,000 VND per 20-minute loop. Nearby Mỹ Sơn UNESCO Sanctuary costs 150,000 VND (including electric buggy), and Cù Lao Chàm Biosphere Reserve costs 70,000 VND + 100,000 VND marine fee.',
            'photographer' => 'VietnamGuide Field Desk',
            'license' => 'Editorial Use',
            'related_route' => '/destinations/best-things-to-do-in-hoi-an/',
            'related_label' => 'Read Hoi An Field Guide',
        ],
        'sapa-terraces' => [
            'id' => 'sapa-terraces',
            'title' => 'Terraced Amphitheaters of Mường Hoa',
            'location' => 'Mường Hoa Valley & Fansipan Ridge',
            'province' => 'Lào Cai',
            'coordinates' => '22°20\'N 103°50\'E',
            'lead' => 'Steep mountain contours sculpted into hydraulic rice staircases descending through morning alpine cloud banks.',
            'base_image_slug' => 'sapa-rice-terraces-hero',
            'image_fallback' => 'assets/images/sapa-rice-terraces-hero.jpg',
            'wikimedia_commons_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/ff/Rice_terraces_in_Sapa%2C_Vietnam.jpg/1920px-Rice_terraces_in_Sapa%2C_Vietnam.jpg',
            'aspect_ratio' => '16/9',
            'alt_text' => 'Stepped mountain rice terraces in Muong Hoa Valley, Sa Pa, Northern Vietnam',
            'exif' => [
                'focal_length' => '50mm',
                'aperture' => 'f/5.6',
                'shutter' => '1/500s',
                'iso' => '200',
                'elevation' => '1,480m ASL',
                'time_of_day' => '06:30 ICT',
            ],
            'field_notes' => 'Sun World Fansipan Legend cable car costs 850,000 VND round-trip (950,000 VND Saturdays/holidays) plus 150,000 VND Mường Hoa monorail and 150,000–170,000 VND peak funicular. Cát Cát Village entry is 150,000 VND and Tả Van – Lao Chải valley tickets cost 75,000 VND.',
            'photographer' => 'Wikimedia Commons Archive',
            'license' => 'CC0 1.0 Public Domain',
            'related_route' => '/destinations/sapa-travel-guide/',
            'related_label' => 'Read Sa Pa Field Guide',
        ],
        'ninh-binh-karst' => [
            'id' => 'ninh-binh-karst',
            'title' => 'Karst Pinnacles Above Tam Cốc',
            'location' => 'Hang Múa & Tràng An Valley',
            'province' => 'Ninh Bình',
            'coordinates' => '20°14\'N 105°50\'E',
            'lead' => 'Vertical limestone towers rise directly from flooded alluvial paddies along the Ngô Đồng river corridor.',
            'base_image_slug' => 'ninh-binh-mua-cave-hero',
            'image_fallback' => 'assets/images/ninh-binh-mua-cave-hero.jpg',
            'wikimedia_commons_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f4/Mua_Cave%2C_Ninh_Binh%2C_Vietnam%2C_20240202_0926_4964.jpg/1920px-Mua_Cave%2C_Ninh_Binh%2C_Vietnam%2C_20240202_0926_4964.jpg',
            'aspect_ratio' => '16/9',
            'alt_text' => 'Limestone karst peaks and river valley from Mua Cave viewpoint in Ninh Binh, Vietnam',
            'exif' => [
                'focal_length' => '28mm',
                'aperture' => 'f/7.1',
                'shutter' => '1/320s',
                'iso' => '125',
                'elevation' => '145m ASL',
                'time_of_day' => '09:26 ICT',
            ],
            'field_notes' => 'Climbing the 486 stone steps to Hang Múa Lying Dragon ridge (100,000 VND entry) before 08:30 avoids midday tour coaches. Official Tràng An sampan boat tickets cost 250,000–300,000 VND per adult, and Tam Cốc combined sightseeing plus boat tickets cost 250,000 VND.',
            'photographer' => 'Wikimedia Commons Contributor',
            'license' => 'CC BY 4.0',
            'related_route' => '/destinations/ninh-binh-travel-guide/',
            'related_label' => 'Read Ninh Bình Field Guide',
        ],
        'da-lat-highlands' => [
            'id' => 'da-lat-highlands',
            'title' => 'Pine-Framed Waters of Xuân Hương',
            'location' => 'Xuân Hương Basin & Langbiang Plateau',
            'province' => 'Lâm Đồng',
            'coordinates' => '11°56\'N 108°26\'E',
            'lead' => 'Temperate highland air and Khasi pine ridges ring the 5-kilometer crescent lake at the center of Đà Lạt.',
            'base_image_slug' => 'da-lat-xuan-huong-hero',
            'image_fallback' => 'assets/images/da-lat-xuan-huong-hero.jpg',
            'wikimedia_commons_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/aa/Da_Lat_-_Xuan_Huong_Lake.jpg/1920px-Da_Lat_-_Xuan_Huong_Lake.jpg',
            'aspect_ratio' => '16/9',
            'alt_text' => 'Crescent waters of Xuan Huong Lake and pine-forested hills on the Langbiang Plateau in Da Lat, Vietnam',
            'exif' => [
                'focal_length' => '35mm',
                'aperture' => 'f/8.0',
                'shutter' => '1/400s',
                'iso' => '100',
                'elevation' => '1,500m ASL',
                'time_of_day' => '07:10 ICT',
            ],
            'field_notes' => 'At 1,500m elevation, night temperatures drop to 14°C–16°C. Datanla Waterfall costs 50,000 VND entry (250,000 VND round-trip New Alpine Coaster), Langbiang Mountain costs 50,000 VND + 120,000 VND Jeep to Radar Peak, and northern Măng Đen Pa Sỹ Waterfall costs 20,000 VND.',
            'photographer' => 'Wikimedia Commons Contributor',
            'license' => 'CC BY 4.0',
            'related_route' => '/destinations/da-lat-travel-guide/',
            'related_label' => 'Read Đà Lạt Field Guide',
        ],
        'mekong-riverine' => [
            'id' => 'mekong-riverine',
            'title' => 'Tidal Commerce on the Phong Điền Reach',
            'location' => 'Phong Điền & Cái Răng Waterways',
            'province' => 'Cần Thơ',
            'coordinates' => '10°00\'N 105°40\'E',
            'lead' => 'Timber sampans and orchard barges navigate distributor canals off the Hậu River shortly after dawn.',
            'base_image_slug' => 'mekong-delta-phong-dien-hero',
            'image_fallback' => 'assets/images/mekong-delta-phong-dien-hero.jpg',
            'wikimedia_commons_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg/1920px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg',
            'aspect_ratio' => '16/9',
            'alt_text' => 'Riverine canal network and wooden sampan boats in Phong Dien, Mekong Delta, Vietnam',
            'exif' => [
                'focal_length' => '40mm',
                'aperture' => 'f/5.6',
                'shutter' => '1/500s',
                'iso' => '200',
                'elevation' => '3m ASL',
                'time_of_day' => '06:15 ICT',
            ],
            'field_notes' => 'Wholesale fruit barges at Cái Răng and Phong Điền peak between 05:30 and 07:00 ICT. Private wooden sampan charters from Ninh Kiều Pier average 350,000–500,000 VND (1–4 passengers), and FUTA sleeper coaches from HCMC take 3–3.5 hours via the CT01 expressway (165,000–250,000 VND).',
            'photographer' => 'Vyacheslav Argenberg',
            'license' => 'CC BY 4.0',
            'related_route' => '/destinations/mekong-delta-travel-guide/',
            'related_label' => 'Read Mekong Delta Field Guide',
        ],
        'phong-nha-caves' => [
            'id' => 'phong-nha-caves',
            'title' => 'Subterranean Portals of the Son River',
            'location' => 'Phong Nha-Kẻ Bàng Karst Basin',
            'province' => 'Quảng Bình',
            'coordinates' => '17°35\'N 106°17\'E',
            'lead' => 'Emerald waters of the Son River flow directly into Paleozoic limestone cave mouths carved over 400 million years.',
            'base_image_slug' => 'phong-nha-son-river-hero',
            'image_fallback' => 'assets/images/phong-nha-son-river-hero.jpg',
            'wikimedia_commons_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f1/Son_River-Quang_Binh_province.jpg/1920px-Son_River-Quang_Binh_province.jpg',
            'aspect_ratio' => '16/9',
            'alt_text' => 'Boats on the emerald Son River framed by limestone karst mountains in Phong Nha, Quang Binh, Vietnam',
            'exif' => [
                'focal_length' => '28mm',
                'aperture' => 'f/8.0',
                'shutter' => '1/320s',
                'iso' => '100',
                'elevation' => '25m ASL',
                'time_of_day' => '08:15 ICT',
            ],
            'field_notes' => 'Phong Nha Cave entrance tickets cost 150,000 VND per adult plus 550,000–700,000 VND per round-trip dragon boat shared across up to 12 passengers. Paradise Cave admission is 250,000 VND per adult, and Dark Cave zip-line/kayak/mud-bath packages cost 450,000 VND.',
            'photographer' => 'BacLuong',
            'license' => 'CC BY-SA 4.0',
            'related_route' => '/destinations/phong-nha-travel-guide/',
            'related_label' => 'Read Phong Nha Field Guide',
        ],
        'cao-bang-frontier' => [
            'id' => 'cao-bang-frontier',
            'title' => 'Emerald Gorge Below Mã Pí Lèng',
            'location' => 'Tu Sản Canyon & Nho Quế River',
            'province' => 'Hà Giang & Cao Bằng',
            'coordinates' => '23°14\'N 105°25\'E',
            'lead' => 'Vertical limestone walls rise 700 to 800 meters above the jade Nho Quế River channel along the northern frontier.',
            'base_image_slug' => 'cao-bang-tu-san-canyon-hero',
            'image_fallback' => 'assets/images/cao-bang-tu-san-canyon-hero.jpg',
            'wikimedia_commons_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/TuSan_Canyon.jpg/1920px-TuSan_Canyon.jpg',
            'aspect_ratio' => '16/9',
            'alt_text' => 'Steep limestone cliffs of Tu San Canyon rising above the emerald Nho Que River in northern Vietnam',
            'exif' => [
                'focal_length' => '24mm',
                'aperture' => 'f/7.1',
                'shutter' => '1/400s',
                'iso' => '125',
                'elevation' => '1,180m ASL',
                'time_of_day' => '09:40 ICT',
            ],
            'field_notes' => 'Motorboat and kayak passages through Tu Sản Canyon from Ta Lang or Xín Cái pier cost 120,000–150,000 VND per person. Further east in Cao Bằng, Bản Giốc Waterfall entrance is 45,000 VND (50,000 VND bamboo raft) and Ngườm Ngao Cave costs 45,000 VND.',
            'photographer' => 'NKSTTSSHNVN',
            'license' => 'CC BY-SA 4.0',
            'related_route' => '/destinations/cao-bang-travel-guide/',
            'related_label' => 'Read Northern Frontier Field Guide',
        ],
        'phu-quoc-archipelago' => [
            'id' => 'phu-quoc-archipelago',
            'title' => 'Crescent Bays of the Southern Gulf',
            'location' => 'Bãi Khem & South-Central Coast',
            'province' => 'Kiên Giang & Khánh Hòa',
            'coordinates' => '10°02\'N 104°01\'E',
            'lead' => 'Sheltered white-sand arcs and granite headlands face calm turquoise waters during the dry coastal window.',
            'base_image_slug' => 'phu-quoc-kem-beach-hero',
            'image_fallback' => 'assets/images/phu-quoc-kem-beach-hero.jpg',
            'wikimedia_commons_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg/1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg',
            'aspect_ratio' => '16/9',
            'alt_text' => 'Aerial view of white sand and turquoise water at Kem Beach on Phu Quoc Island, Vietnam',
            'exif' => [
                'focal_length' => '24mm',
                'aperture' => 'f/5.6',
                'shutter' => '1/800s',
                'iso' => '100',
                'elevation' => '120m ASL',
                'time_of_day' => '10:20 ICT',
            ],
            'field_notes' => 'Rạch Giá – Phú Quốc high-speed ferries cost 315,000–340,000 VND and the 7.9km Hòn Thơm sea cable car costs 650,000–700,000 VND. Along the South-Central coast, Nha Trang Po Nagar Cham Towers cost 30,000 VND, Quy Nhơn Eo Gió costs 25,000 VND, and Kỳ Co Beach costs 100,000 VND.',
            'photographer' => 'Vivu Vietnam',
            'license' => 'CC BY-SA 4.0',
            'related_route' => '/destinations/phu-quoc-travel-guide/',
            'related_label' => 'Read Phú Quốc Field Guide',
        ],
        'hanoi-old-quarter' => [
            'id' => 'hanoi-old-quarter',
            'title' => 'Morning Mist Across Hoàn Kiếm',
            'location' => 'Hoàn Kiếm Lake & Old Quarter',
            'province' => 'Hà Nội',
            'coordinates' => '21°01\'N 105°51\'E',
            'lead' => 'Banyan canopies frame the 19th-century Turtle Tower at the historic civic core of Vietnam\'s capital.',
            'base_image_slug' => 'hanoi-hoan-kiem-hero',
            'image_fallback' => 'assets/images/hanoi-hoan-kiem-hero.jpg',
            'wikimedia_commons_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Hanoi-lac-hoan-kiem.jpg/1920px-Hanoi-lac-hoan-kiem.jpg',
            'aspect_ratio' => '16/9',
            'alt_text' => 'Turtle Tower on Hoan Kiem Lake surrounded by morning mist and green trees in Hanoi, Vietnam',
            'exif' => [
                'focal_length' => '50mm',
                'aperture' => 'f/5.6',
                'shutter' => '1/250s',
                'iso' => '200',
                'elevation' => '12m ASL',
                'time_of_day' => '06:45 ICT',
            ],
            'field_notes' => 'Đinh Tiên Hoàng Boulevard closes to motor traffic from Friday 19:00 through Sunday 24:00. Verified monument admissions: Temple of Literature 70,000 VND, Thăng Long Imperial Citadel 100,000 VND, Hỏa Lò Prison 50,000 VND, and Ngọc Sơn Temple 50,000 VND.',
            'photographer' => 'Alex 69200 vx',
            'license' => 'CC BY-SA 4.0',
            'related_route' => '/destinations/hanoi-travel-guide/',
            'related_label' => 'Read Hanoi Field Guide',
        ],
        'hue-imperial-citadel' => [
            'id' => 'hue-imperial-citadel',
            'title' => 'Meridian Gate of the Nguyễn Dynasty',
            'location' => 'Imperial Citadel & Perfume River',
            'province' => 'Thừa Thiên Huế',
            'coordinates' => '16°28\'N 107°34\'E',
            'lead' => 'Five-phoenix watchtowers crown the stone ramparts of Ngọ Môn Gate inside the 1804 UNESCO Imperial Enclosure.',
            'base_image_slug' => 'hue-imperial-citadel-hero',
            'image_fallback' => 'assets/images/hue-imperial-citadel-hero.jpg',
            'wikimedia_commons_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg/1920px-Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg',
            'aspect_ratio' => '16/9',
            'alt_text' => 'Ngo Mon Meridian Gate and lotus moat at the UNESCO Imperial Citadel in Hue, Vietnam',
            'exif' => [
                'focal_length' => '35mm',
                'aperture' => 'f/8.0',
                'shutter' => '1/320s',
                'iso' => '100',
                'elevation' => '8m ASL',
                'time_of_day' => '08:30 ICT',
            ],
            'field_notes' => 'Hue Monuments Conservation Centre tickets cost 200,000 VND for the Imperial City and 150,000 VND per Royal Tomb (Minh Mạng, Tự Đức, Khải Định), or 420,000 VND for the 2-day 3-site combo pass. Scenic Heritage Trains HĐ1/HĐ2 link Huế to Đà Nẵng over Hải Vân Pass for 150,000–180,000 VND.',
            'photographer' => 'CEphoto, Uwe Aranas',
            'license' => 'CC BY-SA 3.0',
            'related_route' => '/destinations/hue-travel-guide/',
            'related_label' => 'Read Huế Field Guide',
        ],
        'saigon-metropolis' => [
            'id' => 'saigon-metropolis',
            'title' => 'Colonial Axis on Nguyễn Huệ Boulevard',
            'location' => 'District 1 Civic Core, Saigon',
            'province' => 'Hồ Chí Minh City',
            'coordinates' => '10°46\'N 106°42\'E',
            'lead' => '1898 French Third Republic façades anchor the pedestrian promenade linking City Hall to the Saigon River.',
            'base_image_slug' => 'saigon-city-hall-hero',
            'image_fallback' => 'assets/images/saigon-city-hall-hero.jpg',
            'wikimedia_commons_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg/1920px-Ho_Chi_Minh_City%2C_City_Hall%2C_2020-01_CN-02.jpg',
            'aspect_ratio' => '16/9',
            'alt_text' => 'Historic City Hall facade and Nguyen Hue pedestrian plaza in District 1, Ho Chi Minh City, Vietnam',
            'exif' => [
                'focal_length' => '28mm',
                'aperture' => 'f/7.1',
                'shutter' => '1/400s',
                'iso' => '100',
                'elevation' => '9m ASL',
                'time_of_day' => '09:05 ICT',
            ],
            'field_notes' => 'HCMC Metro Line 1 connects Bến Thành Market to Suối Tiên for 6,000–20,000 VND per single ride. Domestic flights at Tan Son Nhat (SGN) operate across Terminal T3 and Terminal T1, and District 1 civic monuments (War Remnants Museum, Independence Palace) charge 40,000–65,000 VND.',
            'photographer' => 'Steffen Schmitz',
            'license' => 'CC BY-SA 4.0',
            'related_route' => '/destinations/ho-chi-minh-city-travel-guide/',
            'related_label' => 'Read Ho Chi Minh City Field Guide',
        ],
    ];
}

function vg_render_photo_dispatch(array $atts): string
{
    $atts = shortcode_atts([
        'slug' => 'ha-long-dawn',
        'layout' => 'standard', // 'standard' | 'fullwidth' | 'compact'
    ], $atts, 'vg_photo_dispatch');

    $dispatches = vg_get_visual_dispatches_data();
    $slug = sanitize_key($atts['slug']);

    if (!isset($dispatches[$slug])) {
        // Fallback to first item if requested slug not found
        $slug = 'ha-long-dawn';
    }

    if (function_exists('vg_enqueue_interactive_shortcode_assets')) {
        vg_enqueue_interactive_shortcode_assets();
    }

    $active_slug = $slug;

    ob_start();
    ?>
    <section class="vg-photo-dispatch vg-photo-dispatch--<?php echo esc_attr($atts['layout']); ?>" data-vg-photo-dispatch-suite aria-label="Visual Dispatches and Optical Telemetry">
        <div id="vg-dispatch-aria-status" class="screen-reader-text" role="status" aria-live="polite"></div>
        <!-- Visual Dispatch Switcher Tabs -->
        <div class="vg-dispatch-pill-selector" role="tablist" aria-label="<?php echo esc_attr('Visual dispatch locations'); ?>">
            <?php foreach ($dispatches as $d_slug => $d_item):
                $is_tab_active = ($d_slug === $active_slug);
            ?>
                <button
                    type="button"
                    class="vg-dispatch-pill-btn <?php echo $is_tab_active ? 'is-active' : ''; ?>"
                    role="tab"
                    aria-selected="<?php echo $is_tab_active ? 'true' : 'false'; ?>"
                    aria-controls="vg-dispatch-panel-<?php echo esc_attr($d_slug); ?>"
                    id="vg-dispatch-tab-<?php echo esc_attr($d_slug); ?>"
                    data-dispatch-target="<?php echo esc_attr($d_slug); ?>"
                >
                    <span class="vg-pill-bullet" aria-hidden="true"></span>
                    <?php echo esc_html($d_item['location']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($dispatches as $d_slug => $item):
            $is_active = ($d_slug === $active_slug);
            $theme_uri = get_template_directory_uri();
            $img_src = esc_url($theme_uri . '/' . $item['image_fallback']);
            $related_url = function_exists('vg_home_url') ? esc_url(vg_home_url(ltrim($item['related_route'], '/'))) : esc_url(home_url($item['related_route']));
        ?>
        <article
            class="vg-dispatch-card <?php echo $is_active ? 'is-active' : ''; ?>"
            id="vg-dispatch-panel-<?php echo esc_attr($d_slug); ?>"
            role="tabpanel"
            aria-labelledby="vg-dispatch-tab-<?php echo esc_attr($d_slug); ?>"
            data-dispatch-panel="<?php echo esc_attr($d_slug); ?>"
            data-vg-dispatch-id="<?php echo esc_attr($item['id']); ?>"
            <?php echo $is_active ? '' : 'hidden'; ?>
        >
            <div class="vg-dispatch-container">
                <header class="vg-dispatch-header">
                    <div class="vg-dispatch-kicker">
                        <span class="vg-dispatch-badge">VISUAL DISPATCH</span>
                        <span class="vg-dispatch-coords" aria-label="GPS Coordinates"><?php echo esc_html($item['coordinates']); ?> &bull; <?php echo esc_html($item['province']); ?></span>
                    </div>
                    <div class="vg-dispatch-title" role="heading" aria-level="3"><?php echo esc_html($item['title']); ?></div>
                    <p class="vg-dispatch-lead"><?php echo esc_html($item['lead']); ?></p>
                </header>

                <figure class="vg-dispatch-figure">
                    <div class="vg-dispatch-media-frame">
                        <img src="<?php echo $img_src; ?>"
                             alt="<?php echo esc_attr($item['alt_text']); ?>"
                             class="vg-dispatch-img"
                             width="1920"
                             height="1080"
                             loading="lazy"
                             decoding="async"
                             data-full-src="<?php echo $img_src; ?>"
                             data-caption="<?php echo esc_attr($item['title'] . ' &mdash; ' . $item['location']); ?>"
                             tabindex="0"
                             role="button"
                             aria-haspopup="dialog"
                             aria-label="Enlarge photograph: <?php echo esc_attr($item['title']); ?>" />
                        <button type="button" class="vg-dispatch-expand-btn" aria-label="Open fullscreen photo inspection">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <polyline points="9 21 3 21 3 15"></polyline>
                                <line x1="21" y1="3" x2="14" y2="10"></line>
                                <line x1="3" y1="21" x2="10" y2="14"></line>
                            </svg>
                            <span>Expand View</span>
                        </button>
                    </div>

                    <figcaption class="vg-dispatch-meta-strip">
                        <div class="vg-dispatch-field-context">
                            <p class="vg-dispatch-field-notes"><strong>Field Context:</strong> <?php echo esc_html($item['field_notes']); ?></p>
                            <div class="vg-dispatch-credits">
                                <span class="vg-dispatch-attribution">Photo: <?php echo esc_html($item['photographer']); ?> (<?php echo esc_html($item['license']); ?>)</span>
                                <a href="<?php echo $related_url; ?>" class="vg-dispatch-link"><?php echo esc_html($item['related_label']); ?> &rarr;</a>
                            </div>
                        </div>

                        <details class="vg-dispatch-telemetry-toggle">
                            <summary class="vg-dispatch-telemetry-summary">
                                <span class="vg-telemetry-toggle-icon" aria-hidden="true">&#9881;</span>
                                <span><?php echo esc_html__('Technical Optical Telemetry (EXIF & Altitude)', 'vietnamguide-premium'); ?></span>
                                <span class="vg-telemetry-toggle-hint" aria-hidden="true">&dtrif;</span>
                            </summary>
                            <div class="vg-dispatch-exif-grid">
                                <div class="vg-exif-pill">
                                    <span class="vg-exif-label">FOCAL</span>
                                    <span class="vg-exif-val"><?php echo esc_html($item['exif']['focal_length']); ?></span>
                                </div>
                                <div class="vg-exif-pill">
                                    <span class="vg-exif-label">APERTURE</span>
                                    <span class="vg-exif-val"><?php echo esc_html($item['exif']['aperture']); ?></span>
                                </div>
                                <div class="vg-exif-pill">
                                    <span class="vg-exif-label">SHUTTER</span>
                                    <span class="vg-exif-val"><?php echo esc_html($item['exif']['shutter']); ?></span>
                                </div>
                                <div class="vg-exif-pill">
                                    <span class="vg-exif-label">ISO</span>
                                    <span class="vg-exif-val"><?php echo esc_html($item['exif']['iso']); ?></span>
                                </div>
                                <div class="vg-exif-pill">
                                    <span class="vg-exif-label">ELEVATION</span>
                                    <span class="vg-exif-val"><?php echo esc_html($item['exif']['elevation']); ?></span>
                                </div>
                                <div class="vg-exif-pill">
                                    <span class="vg-exif-label">LOCAL TIME</span>
                                    <span class="vg-exif-val"><?php echo esc_html($item['exif']['time_of_day']); ?></span>
                                </div>
                            </div>
                        </details>
                    </figcaption>
                </figure>
            </div>
        </article>
        <?php endforeach; ?>

        <details class="vg-dispatch-monument-ledger" id="vg-dispatch-monument-ledger">
            <summary class="vg-dispatch-monument-summary">
                <span class="vg-dispatch-monument-badge">VERIFIED MONUMENT &amp; BIOSPHERE TARIFFS</span>
                <span class="vg-dispatch-monument-title">2025–2026 UNESCO Heritage, National Park &amp; Island Ferry Reference Ledger</span>
            </summary>
            <div class="vg-dispatch-monument-body">
                <div class="vg-table-scroll-wrapper" role="region" aria-label="2025-2026 Verified Monument, Biosphere and Island Ferry Tariff Reference Ledger" tabindex="0">
                    <table class="vg-dispatch-monument-table">
                        <caption>Verified 2025–2026 UNESCO monument admissions, biosphere conservation fees, and island ferry tariffs across Vietnam</caption>
                        <thead>
                            <tr>
                                <th scope="col">Heritage Site / Island Corridor</th>
                                <th scope="col">Province / Cluster</th>
                                <th scope="col">Official 2026 Tariff (VND)</th>
                                <th scope="col">Field Operational Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row">Mỹ Sơn UNESCO Sanctuary &amp; Cù Lao Chàm Biosphere</th>
                                <td>Quảng Nam (Hội An Hub)</td>
                                <td>150,000 VND (Mỹ Sơn) / 170,000 VND (Cù Lao Chàm)</td>
                                <td>Mỹ Sơn ticket includes electric buggy shuttle and Cham dance performance; Cù Lao Chàm combines 70,000 VND island entry + 100,000 VND marine fee (20-min Cửa Đại speedboat 400,000–650,000 VND round-trip).</td>
                            </tr>
                            <tr>
                                <th scope="row">Cát Bà National Park &amp; Đồng Bài – Cái Viềng Ferry</th>
                                <td>Hải Phòng (Lan Hạ Bay)</td>
                                <td>80,000 VND (Park) / 12,000 VND (Ferry)</td>
                                <td>80,000 VND adult park ticket covers Ngự Lâm Peak trail and Trung Trang Cave (160,000 VND Việt Hải trek); Đồng Bài – Cái Viềng public ferry is 12,000 VND walk-on (45,000 VND motorbike), or Sun World Phù Long cable car 50,000–100,000 VND.</td>
                            </tr>
                            <tr>
                                <th scope="row">Po Nagar Cham Towers, Kỳ Co Beach &amp; Eo Gió</th>
                                <td>Khánh Hòa &amp; Bình Định</td>
                                <td>30,000 VND (Po Nagar) / 25,000–140,000 VND (Quy Nhơn)</td>
                                <td>Nha Trang Po Nagar Cham Towers and Hòn Chồng each cost 30,000 VND; Quy Nhơn Eo Gió cliff walk costs 25,000 VND, Tháp Đôi costs 20,000 VND, and Kỳ Co Beach costs 100,000 VND + 40,000 VND headland shuttle.</td>
                            </tr>
                            <tr>
                                <th scope="row">Măng Đen Pa Sỹ Waterfall &amp; Đà Lạt Highland Sites</th>
                                <td>Kon Tum &amp; Lâm Đồng</td>
                                <td>20,000 VND (Pa Sỹ) / 50,000–250,000 VND (Đà Lạt)</td>
                                <td>Pa Sỹ Waterfall Eco-Park (1,200m ASL Kon Plông plateau, 100 km from Pleiku Airport PXU via QL24) costs 20,000 VND; Đà Lạt Datanla Waterfall costs 50,000 VND entry (250,000 VND New Alpine Coaster) and Langbiang costs 50,000 VND + 120,000 VND Jeep.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
    </section>
    <?php
    vg_enqueue_photo_dispatch_assets_once();
    return (string) ob_get_clean();
}
add_shortcode('vg_photo_dispatch', 'vg_render_photo_dispatch');

function vg_enqueue_photo_dispatch_assets_once(): void
{
    if (function_exists('vg_enqueue_interactive_shortcode_assets')) {
        vg_enqueue_interactive_shortcode_assets();
    }

    static $rendered = false;
    if ($rendered) {
        return;
    }
    $rendered = true;

    // Render modal lightbox template and script into wp_footer
    add_action('wp_footer', 'vg_render_photo_dispatch_lightbox_template', 30);
}

function vg_render_photo_dispatch_lightbox_template(): void
{
    ?>
    <div id="vg-dispatch-modal" class="vg-dispatch-lightbox" role="dialog" aria-modal="true" aria-label="Photograph Inspection Modal" hidden>
        <div class="vg-lightbox-backdrop" data-vg-close-modal></div>
        <div class="vg-lightbox-dialog">
            <button type="button" class="vg-lightbox-close" aria-label="Close dialog" data-vg-close-modal>&times;</button>
            <div class="vg-lightbox-body">
                <img id="vg-lightbox-img" src="" alt="" class="vg-lightbox-target" width="1920" height="1080" />
                <div class="vg-lightbox-caption" id="vg-lightbox-caption"></div>
            </div>
        </div>
    </div>
    <script>
    (function() {
        if (window.__vgDispatchInit) return;
        window.__vgDispatchInit = true;

        function initPhotoDispatch() {
            var modal = document.getElementById('vg-dispatch-modal');
            if (!modal) return;

            var modalImg = document.getElementById('vg-lightbox-img');
            var modalCaption = document.getElementById('vg-lightbox-caption');
            var prevFocus = null;

            function openModal(img) {
                if (!img) return;
                prevFocus = document.activeElement;
                modalImg.src = img.getAttribute('data-full-src') || img.src;
                modalImg.alt = img.alt || '';
                modalCaption.textContent = img.getAttribute('data-caption') || '';
                modal.removeAttribute('hidden');
                modal.classList.add('is-active');
                document.body.style.overflow = 'hidden';

                var closeBtn = modal.querySelector('.vg-lightbox-close');
                if (closeBtn) closeBtn.focus();
            }

            function closeModal() {
                modal.classList.remove('is-active');
                modal.setAttribute('hidden', '');
                document.body.style.overflow = '';
                if (prevFocus && typeof prevFocus.focus === 'function') {
                    prevFocus.focus();
                }
            }

            // Dispatch tab switcher logic
            var suiteContainers = document.querySelectorAll('[data-vg-photo-dispatch-suite]');
            suiteContainers.forEach(function(suite) {
                var tabBtns = suite.querySelectorAll('[data-dispatch-target]');
                var panels = suite.querySelectorAll('[data-dispatch-panel]');

                function switchDispatch(targetId) {
                    tabBtns.forEach(function(btn) {
                        var match = btn.getAttribute('data-dispatch-target') === targetId;
                        btn.classList.toggle('is-active', match);
                        btn.setAttribute('aria-selected', match ? 'true' : 'false');
                    });
                    panels.forEach(function(panel) {
                        var match = panel.getAttribute('data-dispatch-panel') === targetId;
                        panel.classList.toggle('is-active', match);
                        if (match) {
                            panel.removeAttribute('hidden');
                        } else {
                            panel.setAttribute('hidden', '');
                        }
                    });
                }

                tabBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var targetId = btn.getAttribute('data-dispatch-target');
                        if (targetId) switchDispatch(targetId);
                    });
                });
            });

            document.querySelectorAll('.vg-photo-dispatch .vg-dispatch-card').forEach(function(card) {
                var img = card.querySelector('.vg-dispatch-img');
                var btn = card.querySelector('.vg-dispatch-expand-btn');

                if (img) {
                    img.addEventListener('click', function() { openModal(img); });
                    img.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            openModal(img);
                        }
                    });
                }
                if (btn && img) {
                    btn.addEventListener('click', function() { openModal(img); });
                }
            });

            modal.querySelectorAll('[data-vg-close-modal]').forEach(function(el) {
                el.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', function(e) {
                if (!modal.hasAttribute('hidden') && e.key === 'Escape') {
                    closeModal();
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initPhotoDispatch);
        } else {
            initPhotoDispatch();
        }
    })();
    </script>
    <?php
}

function vg_photo_dispatch_shortcode(array $atts = []): string
{
    $seriesAlias = [
        'all'               => 'ha-long-dawn',
        'halong'            => 'ha-long-dawn',
        'hoian'             => 'hoi-an-dusk',
        'highlands'         => 'sapa-terraces',
        'ninhbinh'          => 'ninh-binh-karst',
        'central-highlands' => 'da-lat-highlands',
        'mekong'            => 'mekong-riverine',
        'phongnha'          => 'phong-nha-caves',
        'caobang'           => 'cao-bang-frontier',
        'phuquoc'           => 'phu-quoc-archipelago',
        'hanoi'             => 'hanoi-old-quarter',
        'hue'               => 'hue-imperial-citadel',
        'saigon'            => 'saigon-metropolis',
    ];
    if (isset($atts['series']) && ! isset($atts['slug'])) {
        $seriesKey = sanitize_key((string) $atts['series']);
        $atts['slug'] = $seriesAlias[$seriesKey] ?? $seriesKey;
    }
    return vg_render_photo_dispatch($atts);
}

/**
 * Automatically injects the contextual Photo Dispatch visual gallery on matching
 * pillar destination guides across all 12 regional clusters and the Destinations hub.
 */
function vg_inject_photo_dispatch_on_pillar_destinations(string $content): string
{
    if (function_exists('is_admin') && is_admin()) {
        return $content;
    }

    if (strpos($content, 'vg-guide-hero') !== false) {
        return $content;
    }

    static $injectedPosts = [];
    $postId = function_exists('get_the_ID') ? (int) get_the_ID() : 0;
    if ($postId && isset($injectedPosts[$postId])) {
        return $content;
    }

    $slug = '';
    if (function_exists('get_post')) {
        $post = get_post();
        if ($post instanceof WP_Post) {
            $slug = (string) ($post->post_name ?? '');
        }
    }
    if ($slug === '' && function_exists('get_post_field')) {
        $slug = (string) get_post_field('post_name');
    }

    $seriesMap = [
        'destinations'                  => 'all',
        'ha-long-bay-travel-guide'      => 'halong',
        'lan-ha-bay-guide'              => 'halong',
        'bai-tu-long-bay-guide'         => 'halong',
        'cat-ba-travel-guide'           => 'halong',
        'ha-long-bay-vs-lan-ha-bay'     => 'halong',
        'ninh-binh-vs-ha-long-bay'      => 'ninhbinh',
        'hoi-an-ancient-town-guide'     => 'hoian',
        'best-things-to-do-in-hoi-an'   => 'hoian',
        'da-nang-vs-hoi-an'             => 'hoian',
        'hoi-an-vs-hue'                 => 'hue',
        'hue-travel-guide'              => 'hue',
        'da-nang-travel-guide'          => 'hoian',
        'sapa-travel-guide'             => 'highlands',
        'ha-giang-loop-planning-guide'  => 'caobang',
        'mu-cang-chai-travel-guide'     => 'highlands',
        'pu-luong-travel-guide'         => 'highlands',
        'mai-chau-travel-guide'         => 'highlands',
        'cao-bang-travel-guide'         => 'caobang',
        'ba-be-lake-travel-guide'       => 'caobang',
        'vietnam-rice-terraces-guide'   => 'highlands',
        'sapa-vs-ha-giang'              => 'highlands',
        'ninh-binh-travel-guide'        => 'ninhbinh',
        'tam-coc-vs-trang-an'           => 'ninhbinh',
        'da-lat-travel-guide'           => 'central-highlands',
        'mang-den-travel-guide'         => 'central-highlands',
        'da-lat-vs-sapa'                => 'central-highlands',
        'mekong-delta-travel-guide'     => 'mekong',
        'can-tho-travel-guide'          => 'mekong',
        'phong-nha-travel-guide'        => 'phongnha',
        'quang-binh-travel-guide'       => 'phongnha',
        'phu-quoc-travel-guide'         => 'phuquoc',
        'con-dao-travel-guide'          => 'phuquoc',
        'phu-quoc-vs-con-dao'           => 'phuquoc',
        'nha-trang-travel-guide'        => 'phuquoc',
        'quy-nhon-travel-guide'         => 'phuquoc',
        'mui-ne-travel-guide'           => 'phuquoc',
        'best-beaches-in-vietnam'       => 'phuquoc',
        'hanoi-travel-guide'            => 'hanoi',
        'ho-chi-minh-city-travel-guide' => 'saigon',
    ];

    $resolvedSeries = $seriesMap[$slug] ?? '';

    if ($resolvedSeries === '' && $slug !== '') {
        $guidePath = '';
        if (isset($post) && $post instanceof WP_Post && function_exists('vg_get_guide_path') && function_exists('get_page_uri')) {
            $guidePath = trim((string) vg_get_guide_path($post), '/');
        }
        if ($guidePath === '' && isset($_SERVER['REQUEST_URI'])) {
            $parsedPath = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if (is_string($parsedPath)) {
                $guidePath = trim($parsedPath, '/');
            }
        }

        $isDestinationOrCompare = str_starts_with($guidePath, 'destinations/')
            || str_starts_with($guidePath, 'compare/')
            || str_ends_with($slug, '-travel-guide')
            || str_contains($slug, '-vs-');

        if ($isDestinationOrCompare && function_exists('vg_find_travel_cluster_by_path')) {
            $lookupPath = $guidePath !== '' ? $guidePath : ('destinations/' . $slug);
            $cluster = vg_find_travel_cluster_by_path($lookupPath);
            $clusterId = is_array($cluster) && ! empty($cluster['id']) ? (string) $cluster['id'] : 'national_circuit';
            $clusterToSeriesMap = [
                'northern_triangle'  => 'hanoi',
                'ha_long_bay'        => 'halong',
                'ninh_binh'          => 'ninhbinh',
                'central_heritage'   => 'hoian',
                'southern_delta'     => 'saigon',
                'northern_highlands' => 'highlands',
                'coastal_islands'    => 'phuquoc',
                'central_highlands'  => 'central-highlands',
                'phong_nha_caves'    => 'phongnha',
                'mekong_delta'       => 'mekong',
                'far_north_frontier' => 'caobang',
                'national_circuit'   => 'all',
            ];
            $resolvedSeries = $clusterToSeriesMap[$clusterId] ?? 'all';
        }
    }

    if ($resolvedSeries === '') {
        return $content;
    }

    if ((function_exists('has_shortcode') && has_shortcode($content, 'vg_photo_dispatch')) || strpos($content, 'vg-photo-dispatch') !== false) {
        return $content;
    }

    if ($postId) {
        $injectedPosts[$postId] = true;
    }

    $dispatchHtml = vg_photo_dispatch_shortcode(['series' => $resolvedSeries]);

    return $content . "\n\n" . $dispatchHtml;
}
if (function_exists('add_filter')) {
    add_filter('the_content', 'vg_inject_photo_dispatch_on_pillar_destinations', 24);
}



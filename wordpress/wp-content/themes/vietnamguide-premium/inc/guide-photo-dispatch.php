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
            'field_notes' => 'Dawn maritime conditions offer maximum atmospheric clarity before thermal convection brings afternoon haze. Sailing permits require calm conditions below Beaufort Force 4.',
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
            'field_notes' => 'Motorized traffic is prohibited in the central old quarter after 17:30. River wooden sampans charge standard regulated rates of 150,000 VND for a 20-minute water circuit.',
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
            'field_notes' => 'Early morning mountain light cuts through valley condensation before dense alpine thermals form at noon. Unpaved trail descents require sturdy boots with traction lug soles.',
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
            'field_notes' => 'Climbing the 486 stone steps to the Lying Dragon ridge before 08:30 avoids midday tour coach arrivals. Official Tràng An sampan boat tickets cost 300,000 VND per adult.',
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
            'field_notes' => 'At 1,500 meters elevation, night temperatures drop to 14°C–16°C while coastal plains remain tropical. Morning mist clears across Xuân Hương Lake between 06:30 and 08:00.',
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
            'field_notes' => 'Wholesale fruit barges at Cái Răng and Phong Điền peak between 05:30 and 07:00 ICT according to tidal flow. Private wooden sampan charters from Ninh Kiều Pier average 350,000–500,000 VND.',
            'photographer' => 'Wikimedia Commons Contributor',
            'license' => 'CC BY 4.0',
            'related_route' => '/destinations/mekong-delta-travel-guide/',
            'related_label' => 'Read Mekong Delta Field Guide',
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
    ];
    if (isset($atts['series']) && ! isset($atts['slug'])) {
        $seriesKey = sanitize_key((string) $atts['series']);
        $atts['slug'] = $seriesAlias[$seriesKey] ?? $seriesKey;
    }
    return vg_render_photo_dispatch($atts);
}

/**
 * Automatically injects the contextual Photo Dispatch visual gallery on matching
 * pillar destination guides (Ha Long/Lan Ha, Hoi An, Sapa/Ha Giang, Ninh Binh, Da Lat, Mekong) and the Destinations hub.
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
        'hoi-an-ancient-town-guide'     => 'hoian',
        'best-things-to-do-in-hoi-an'   => 'hoian',
        'da-nang-vs-hoi-an'             => 'hoian',
        'hoi-an-vs-hue'                 => 'hoian',
        'sapa-travel-guide'             => 'highlands',
        'ha-giang-loop-planning-guide'  => 'highlands',
        'mu-cang-chai-travel-guide'     => 'highlands',
        'pu-luong-travel-guide'         => 'highlands',
        'cao-bang-travel-guide'         => 'highlands',
        'vietnam-rice-terraces-guide'   => 'highlands',
        'sapa-vs-ha-giang'              => 'highlands',
        'ninh-binh-travel-guide'        => 'ninhbinh',
        'tam-coc-vs-trang-an'           => 'ninhbinh',
        'da-lat-travel-guide'           => 'central-highlands',
        'mang-den-travel-guide'         => 'central-highlands',
        'mekong-delta-travel-guide'     => 'mekong',
        'can-tho-travel-guide'          => 'mekong',
    ];

    if (! isset($seriesMap[$slug])) {
        return $content;
    }

    if ((function_exists('has_shortcode') && has_shortcode($content, 'vg_photo_dispatch')) || strpos($content, 'vg-photo-dispatch') !== false) {
        return $content;
    }

    if ($postId) {
        $injectedPosts[$postId] = true;
    }

    $dispatchHtml = vg_photo_dispatch_shortcode(['series' => $seriesMap[$slug]]);

    return $content . "\n\n" . $dispatchHtml;
}
if (function_exists('add_filter')) {
    add_filter('the_content', 'vg_inject_photo_dispatch_on_pillar_destinations', 24);
}



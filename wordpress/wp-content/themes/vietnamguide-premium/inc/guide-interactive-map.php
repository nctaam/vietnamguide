<?php
/**
 * VietnamGuide Interactive Cartography & Regional Exploration Desk
 *
 * Implements an accessible, editorial-grade interactive map and regional
 * corridor selector. Conforms to docs/DESIGN.md tokens, Gate 5 zero-H2
 * TOC protection rule, and Anti-AI Slop linguistic constitution.
 *
 * Shortcode: [vg_interactive_map]
 */

if (!defined('ABSPATH')) {
    exit;
}

function vg_get_regional_corridors_data(): array
{
    return [
        'northern-highlands' => [
            'id' => 'northern-highlands',
            'name' => 'Northern Highlands & Karst Valleys',
            'vietnamese' => 'Miền Núi Phía Bắc',
            'focal_nodes' => ['Hà Nội', 'Sa Pa', 'Hà Giang', 'Cao Bằng', 'Ba Bể'],
            'distance_from_hanoi' => '0–320 km',
            'transit_summary' => 'Sleeper trains SP3/SP4 to Lào Cai Station (GPS 22.4842, 103.9786); expressway limousines via CT05; motorbikes with licensed mountain drivers on National Route 4C crossing Mã Pí Lèng Pass.',
            'transit_hours' => '4.5–7.0 hours',
            'typical_daily_spend' => '850,000–1,850,000 VND',
            'spend_usd' => '~$33–$72 USD',
            'optimal_dry_window' => 'September–November & March–May',
            'microclimate_verdict' => 'Crisp dry visibility in autumn; heavy winter fog and mountain cold (down to 2°C) between December and February.',
            'field_caution' => 'Mountain passes (Mã Pí Lèng, Ô Quy Hồ) require high ground clearance and experienced mountain drivers. Avoid unpaved spurs during summer rains.',
            'primary_route_slug' => '/destinations/sapa-travel-guide/',
            'primary_route_label' => 'Explore Sa Pa & Northern Routes',
            'coordinates' => ['x' => 125, 'y' => 65],
        ],
        'red-river-maritime' => [
            'id' => 'red-river-maritime',
            'name' => 'Red River Delta & Maritime Seascape',
            'vietnamese' => 'Đồng Bằng Sông Hồng & Vịnh Bắc Bộ',
            'focal_nodes' => ['Hà Nội', 'Hạ Long Bay', 'Ninh Bình', 'Cát Bà'],
            'distance_from_hanoi' => '95–165 km',
            'transit_summary' => 'Expressway buses via CT04 (Hà Nội – Hải Phòng – Quảng Ninh); commuter trains to Ninh Bình; direct ferries from Tuần Châu and Bến Bèo (GPS 20.7258, 107.0503) to Cát Bà.',
            'transit_hours' => '1.5–2.5 hours',
            'typical_daily_spend' => '1,450,000–3,500,000 VND',
            'spend_usd' => '~$57–$137 USD',
            'optimal_dry_window' => 'October–December & March–April',
            'microclimate_verdict' => 'Stable autumn skies and minimal rain; high humidity (nồm) and mist during February–March; occasional tropical depressions July–August.',
            'field_caution' => 'Harbor authorities at Tuần Châu and Bến Bèo suspend ferry and overnight sailing permits during storm warnings with short advance notice.',
            'primary_route_slug' => '/destinations/ha-long-bay-travel-guide/',
            'primary_route_label' => 'Explore Ha Long & Delta Guides',
            'coordinates' => ['x' => 165, 'y' => 95],
        ],
        'central-heritage' => [
            'id' => 'central-heritage',
            'name' => 'Central Heritage Coast',
            'vietnamese' => 'Duyên Hải Miền Trung',
            'focal_nodes' => ['Huế', 'Đà Nẵng', 'Hội An', 'Phong Nha'],
            'distance_from_hanoi' => '500–785 km',
            'transit_summary' => 'Reunification Express trains SE1–SE8 crossing Hải Vân Pass (Huế–Đà Nẵng sector); daily flights into DAD and HUI; Đồng Hới Station (GPS 17.4722, 106.6042) shuttles to Phong Nha-Kẻ Bàng karst network.',
            'transit_hours' => '1.2h flight // 14–16h rail',
            'typical_daily_spend' => '1,150,000–2,650,000 VND',
            'spend_usd' => '~$45–$104 USD',
            'optimal_dry_window' => 'February–August',
            'microclimate_verdict' => 'Consistent sunshine and calm seas spring through mid-summer; heavy seasonal rainfall and high tides (triều cường) from October through late November.',
            'field_caution' => 'Low-lying quarters in Hội An Ancient Town face localized river flooding during late autumn monsoons. Confirm hotel elevations when booking October–November.',
            'primary_route_slug' => '/compare/da-nang-vs-hoi-an/',
            'primary_route_label' => 'Explore Da Nang & Hoi An Decisions',
            'coordinates' => ['x' => 205, 'y' => 210],
        ],
        'south-central-highlands' => [
            'id' => 'south-central-highlands',
            'name' => 'South-Central Coast & Pine Plateau',
            'vietnamese' => 'Nam Trung Bộ & Cao Nguyên Lâm Viên',
            'focal_nodes' => ['Quy Nhơn', 'Nha Trang', 'Đà Lạt'],
            'distance_from_hanoi' => '1,050–1,300 km',
            'transit_summary' => 'Coastal main-line rail stopping at Diêu Trì Station (GPS 13.8118, 109.1558 for Quy Nhơn); domestic flights into UIH, CXR, DLI; mountain switchback coaches via Khánh Lê Pass (National Route 27C).',
            'transit_hours' => '1.0h flight from HCMC // 3.5h coach',
            'typical_daily_spend' => '1,200,000–2,800,000 VND',
            'spend_usd' => '~$47–$110 USD',
            'optimal_dry_window' => 'December–April',
            'microclimate_verdict' => 'Subtropical highland climate in Đà Lạt (14–24°C year-round); sheltered marine conditions with clear coastal visibility along Quy Nhơn beaches.',
            'field_caution' => 'The mountain highway between Nha Trang and Đà Lạt (Khánh Lê Pass) is prone to rockfalls and dense cloud cover during rainy afternoons.',
            'primary_route_slug' => '/destinations/best-beaches-in-vietnam/',
            'primary_route_label' => 'Explore Coastal & Plateau Routes',
            'coordinates' => ['x' => 235, 'y' => 310],
        ],
        'southern-metropolis' => [
            'id' => 'southern-metropolis',
            'name' => 'Southern Metropolis & Mekong Waterways',
            'vietnamese' => 'Đông Nam Bộ & Đồng Bằng Sông Cửu Long',
            'focal_nodes' => ['Hồ Chí Minh City', 'Cần Thơ', 'Bến Tre', 'Châu Đốc'],
            'distance_from_hanoi' => '1,720 km',
            'transit_summary' => 'Tan Son Nhat (SGN, GPS 10.8188, 106.6518) international hub; Trung Lương – Mỹ Thuận Expressway coaches; passenger riverboats on Mekong distributary channels.',
            'transit_hours' => '2.1h flight from Hanoi // 2.0h coach to Delta',
            'typical_daily_spend' => '1,100,000–2,700,000 VND',
            'spend_usd' => '~$43–$106 USD',
            'optimal_dry_window' => 'December–April',
            'microclimate_verdict' => 'Tropical savanna with steady warm temperatures (27–34°C); short afternoon showers during the southwest monsoon between May and November.',
            'field_caution' => 'Late-afternoon high tide cycles combined with heavy rains can cause localized street flooding in low-lying riverside districts. Plan transfers around peak tides.',
            'primary_route_slug' => '/plan/transport-within-vietnam/',
            'primary_route_label' => 'Explore Saigon & Mekong Routes',
            'coordinates' => ['x' => 195, 'y' => 415],
        ],
        'maritime-archipelagos' => [
            'id' => 'maritime-archipelagos',
            'name' => 'Maritime Archipelagos & Outlying Islands',
            'vietnamese' => 'Hải Đảo (Phú Quốc & Côn Đảo)',
            'focal_nodes' => ['Phú Quốc', 'Côn Đảo'],
            'distance_from_hanoi' => '1,850–2,050 km',
            'transit_summary' => 'Direct flights to PQC (GPS 10.1699, 103.9931) or VCS; high-speed catamarans (Phú Quốc Express, Superdong) operating from Rạch Giá, Hà Tiên, and Vũng Tàu.',
            'transit_hours' => '50m flight from HCMC // 2.5h fast ferry',
            'typical_daily_spend' => '1,600,000–4,200,000 VND',
            'spend_usd' => '~$63–$165 USD',
            'optimal_dry_window' => 'November–April',
            'microclimate_verdict' => 'Calm turquoise waters and minimal sea swell in winter and spring; southwest monsoon brings choppy crossings and surf from July to September.',
            'field_caution' => 'Ferry crossings to both Phú Quốc and Côn Đảo are suspended when winds exceed Beaufort scale 6. For tight itineraries, prioritize air travel.',
            'primary_route_slug' => '/destinations/phu-quoc-travel-guide/',
            'primary_route_label' => 'Explore Island Retraite Guides',
            'coordinates' => ['x' => 135, 'y' => 460],
        ],
    ];
}

function vg_interactive_map_shortcode(array $atts = []): string
{
    $corridors = vg_get_regional_corridors_data();
    $default_id = 'central-heritage';
    $active_corridor = $corridors[$default_id] ?? reset($corridors);

    ob_start();
    ?>
    <section class="vg-interactive-map-desk" data-vg-interactive-map aria-label="<?php esc_attr_e('Vietnam Interactive Cartography & Regional Corridors', 'vietnamguide-premium'); ?>">
        <div class="vg-interactive-map-header">
            <span class="vg-interactive-map-tag">
                <?php esc_html_e('CARTOGRAPHIC INTELLIGENCE // 6 TRANSIT CORRIDORS', 'vietnamguide-premium'); ?>
            </span>
            <h3 class="vg-interactive-map-title">
                <?php esc_html_e('Vietnam Regional Exploration Desk', 'vietnamguide-premium'); ?>
            </h3>
            <p class="vg-interactive-map-lede">
                <?php esc_html_e('Select any transit corridor to inspect distance telemetry, seasonal windows, verified daily spend, and transport connectivity across Vietnam.', 'vietnamguide-premium'); ?>
            </p>
        </div>

        <div class="vg-interactive-map-layout">
            <!-- Left: Vector Cartography Column -->
            <div class="vg-interactive-map-canvas-col">
                <div class="vg-map-canvas-wrapper" role="region" aria-label="<?php esc_attr_e('Interactive Vietnam corridor map', 'vietnamguide-premium'); ?>">
                    <svg class="vg-map-svg" viewBox="0 0 320 540" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <!-- Stylized S-curve coast path -->
                        <path class="vg-map-outline" d="M 120,40 Q 150,55 170,85 Q 185,110 185,145 Q 180,180 195,220 Q 215,260 235,300 Q 250,335 240,370 Q 220,410 180,440 Q 150,460 130,475 Q 120,490 140,500" fill="none" stroke="#E5DFD6" stroke-width="24" stroke-linecap="round" stroke-linejoin="round" />
                        <path class="vg-map-spine" d="M 120,40 Q 150,55 170,85 Q 185,110 185,145 Q 180,180 195,220 Q 215,260 235,300 Q 250,335 240,370 Q 220,410 180,440 Q 150,460 130,475 Q 120,490 140,500" fill="none" stroke="#0F2537" stroke-width="4" stroke-dasharray="6,4" opacity="0.45" />

                        <!-- Corridor Waypoint Anchors -->
                        <?php foreach ($corridors as $corridor):
                            $cx = (int) $corridor['coordinates']['x'];
                            $cy = (int) $corridor['coordinates']['y'];
                            $is_active = ($corridor['id'] === $default_id);
                        ?>
                            <g class="vg-map-pin-group <?php echo $is_active ? 'is-active' : ''; ?>" data-corridor-pin="<?php echo esc_attr($corridor['id']); ?>">
                                <circle class="vg-map-pin-pulse" cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="14" fill="#C99446" opacity="0.2" />
                                <circle class="vg-map-pin" cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="7" fill="<?php echo $is_active ? '#C99446' : '#0F2537'; ?>" stroke="#FFFFFF" stroke-width="2.5" />
                                <text class="vg-map-pin-label" x="<?php echo $cx + 12; ?>" y="<?php echo $cy + 4; ?>" font-family="'JetBrains Mono', monospace" font-size="10" font-weight="600" fill="#1A202C"><?php echo esc_html($corridor['focal_nodes'][0]); ?></text>
                            </g>
                        <?php endforeach; ?>
                    </svg>

                    <!-- Corridor Selector Tab Strip -->
                    <div class="vg-map-pill-selector" role="tablist" aria-label="<?php esc_attr_e('Regional corridor tabs', 'vietnamguide-premium'); ?>">
                        <?php foreach ($corridors as $corridor):
                            $is_active = ($corridor['id'] === $default_id);
                        ?>
                            <button
                                type="button"
                                class="vg-map-pill-btn <?php echo $is_active ? 'is-active' : ''; ?>"
                                role="tab"
                                aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                                aria-controls="vg-dossier-<?php echo esc_attr($corridor['id']); ?>"
                                id="vg-tab-<?php echo esc_attr($corridor['id']); ?>"
                                data-corridor-target="<?php echo esc_attr($corridor['id']); ?>"
                            >
                                <span class="vg-pill-bullet" aria-hidden="true"></span>
                                <?php echo esc_html($corridor['name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Live Telemetry & Dossier Column -->
            <div class="vg-interactive-map-dossier-col">
                <?php foreach ($corridors as $corridor):
                    $is_active = ($corridor['id'] === $default_id);
                ?>
                    <article
                        class="vg-corridor-dossier <?php echo $is_active ? 'is-active' : ''; ?>"
                        id="vg-dossier-<?php echo esc_attr($corridor['id']); ?>"
                        role="tabpanel"
                        aria-labelledby="vg-tab-<?php echo esc_attr($corridor['id']); ?>"
                        data-corridor-panel="<?php echo esc_attr($corridor['id']); ?>"
                        <?php echo $is_active ? '' : 'hidden'; ?>
                    >
                        <header class="vg-dossier-header">
                            <div class="vg-dossier-meta-rail">
                                <span class="vg-dossier-badge"><?php echo esc_html($corridor['vietnamese']); ?></span>
                                <span class="vg-dossier-telemetry">
                                    <span class="vg-dossier-k">DIST:</span> <?php echo esc_html($corridor['distance_from_hanoi']); ?>
                                </span>
                            </div>
                            <h4 class="vg-dossier-title"><?php echo esc_html($corridor['name']); ?></h4>
                            <div class="vg-dossier-nodes">
                                <?php foreach ($corridor['focal_nodes'] as $node): ?>
                                    <span class="vg-node-tag"><?php echo esc_html($node); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </header>

                        <!-- Telemetry Grid -->
                        <div class="vg-dossier-grid">
                            <div class="vg-dossier-stat">
                                <span class="vg-stat-label"><?php esc_html_e('Transit Duration', 'vietnamguide-premium'); ?></span>
                                <strong class="vg-stat-value"><?php echo esc_html($corridor['transit_hours']); ?></strong>
                                <span class="vg-stat-sub"><?php echo esc_html($corridor['transit_summary']); ?></span>
                            </div>
                            <div class="vg-dossier-stat">
                                <span class="vg-stat-label"><?php esc_html_e('Daily Spend (Per Traveler)', 'vietnamguide-premium'); ?></span>
                                <strong class="vg-stat-value"><?php echo esc_html($corridor['typical_daily_spend']); ?></strong>
                                <span class="vg-stat-sub"><?php echo esc_html($corridor['spend_usd']); ?> (Mid-range benchmark)</span>
                            </div>
                            <div class="vg-dossier-stat">
                                <span class="vg-stat-label"><?php esc_html_e('Optimal Weather Window', 'vietnamguide-premium'); ?></span>
                                <strong class="vg-stat-value"><?php echo esc_html($corridor['optimal_dry_window']); ?></strong>
                                <span class="vg-stat-sub"><?php echo esc_html($corridor['microclimate_verdict']); ?></span>
                            </div>
                            <div class="vg-dossier-stat vg-dossier-caution">
                                <span class="vg-stat-label"><?php esc_html_e('Field Caution', 'vietnamguide-premium'); ?></span>
                                <p class="vg-caution-text"><?php echo esc_html($corridor['field_caution']); ?></p>
                            </div>
                        </div>

                        <footer class="vg-dossier-actions">
                            <a href="<?php echo esc_url(vg_home_url($corridor['primary_route_slug'])); ?>" class="vg-dossier-btn">
                                <?php echo esc_html($corridor['primary_route_label']); ?>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
        (function() {
            'use strict';
            function initInteractiveMap() {
                var mapDesks = document.querySelectorAll('[data-vg-interactive-map]');
                Array.prototype.forEach.call(mapDesks, function(mapDesk) {
                    var tabBtns = mapDesk.querySelectorAll('[data-corridor-target]');
                    var panels = mapDesk.querySelectorAll('[data-corridor-panel]');
                    var pinGroups = mapDesk.querySelectorAll('[data-corridor-pin]');

                    function activate(id) {
                        Array.prototype.forEach.call(tabBtns, function(btn) {
                            var isActive = btn.getAttribute('data-corridor-target') === id;
                            btn.classList.toggle('is-active', isActive);
                            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                        });
                        Array.prototype.forEach.call(panels, function(panel) {
                            var isActive = panel.getAttribute('data-corridor-panel') === id;
                            panel.classList.toggle('is-active', isActive);
                            if (isActive) {
                                panel.removeAttribute('hidden');
                            } else {
                                panel.setAttribute('hidden', '');
                            }
                        });
                        Array.prototype.forEach.call(pinGroups, function(pin) {
                            var isActive = pin.getAttribute('data-corridor-pin') === id;
                            pin.classList.toggle('is-active', isActive);
                            var circle = pin.querySelector('.vg-map-pin');
                            if (circle) {
                                circle.setAttribute('fill', isActive ? '#C99446' : '#0F2537');
                            }
                        });
                    }

                    Array.prototype.forEach.call(tabBtns, function(btn) {
                        btn.addEventListener('click', function() {
                            var targetId = this.getAttribute('data-corridor-target');
                            if (targetId) {
                                activate(targetId);
                            }
                        });
                    });

                    Array.prototype.forEach.call(pinGroups, function(pin) {
                        pin.addEventListener('click', function() {
                            var targetId = this.getAttribute('data-corridor-pin');
                            if (targetId) {
                                activate(targetId);
                            }
                        });
                        pin.setAttribute('tabindex', '0');
                        pin.setAttribute('role', 'button');
                        pin.addEventListener('keydown', function(event) {
                            if (event.key === 'Enter' || event.key === ' ') {
                                event.preventDefault();
                                var targetId = this.getAttribute('data-corridor-pin');
                                if (targetId) {
                                    activate(targetId);
                                }
                            }
                        });
                    });
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initInteractiveMap);
            } else {
                initInteractiveMap();
            }
        })();
        </script>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode('vg_interactive_map', 'vg_interactive_map_shortcode');

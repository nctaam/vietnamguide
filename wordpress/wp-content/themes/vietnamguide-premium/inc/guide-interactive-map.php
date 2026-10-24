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
            'focal_nodes' => ['Sa Pa', 'Hà Giang', 'Đồng Văn', 'Cao Bằng', 'Ba Bể'],
            'distance_from_hanoi' => '295–320 km',
            'transit_summary' => 'Sleeper trains SP3/SP4 to Lào Cai Station (GPS 22.4842, 103.9786); expressway limousines via CT05; motorbikes with licensed mountain drivers on National Route 4C crossing Mã Pí Lèng Pass (GPS 23.2389, 105.4183, 1,500m ASL) and Ô Quy Hồ Pass (GPS 22.3551, 103.7749, 2,000m ASL).',
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
            'transit_summary' => 'Hanoi Metro Line 2A (Cát Linh – Hà Đông) & Line 3 (Nhổn – Cầu Giấy, 9,000–19,000 VND single ride, 40,000 VND 1-day pass, 160,000 VND weekly pass); expressway coaches via CT04/CT06 (Hà Nội – Hải Phòng – Hạ Long); direct ferries from Tuần Châu and Đồng Bài / Bến Bèo (GPS 20.7258, 107.0503) to Cát Bà.',
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
            'transit_summary' => 'Reunification Express trains SE1–SE8 and Central Heritage Train HĐ1/HĐ2 (150,000–210,000 VND, 10-min Lăng Cô Bay stop) crossing Hải Vân Pass; daily flights into DAD, HUI (Terminal T2), and VDH; Đồng Hới Station (GPS 17.4722, 106.6042) shuttles to Phong Nha-Kẻ Bàng karst network.',
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
            'focal_nodes' => ['Quy Nhơn', 'Nha Trang', 'Đà Lạt', 'Măng Đen', 'Kon Tum'],
            'distance_from_hanoi' => '1,050–1,300 km',
            'transit_summary' => 'Coastal main-line rail stopping at Diêu Trì Station (GPS 13.8118, 109.1558 for Quy Nhơn); domestic flights into UIH, CXR, DLI; mountain switchback coaches via Khánh Lê Pass (National Route 27C) and Măng Đen pine plateau via National Route 24 (GPS 14.6041, 108.2882).',
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
            'transit_summary' => 'Tan Son Nhat (SGN, GPS 10.8188, 106.6518) international hub; HCMC Metro Line 1 (Bến Thành – Suối Tiên, 19.7 km, 14 stations: 3 underground Bến Thành, Nhà hát Thành phố, Ba Son + 11 elevated, 6,000–20,000 VND single ride, 40,000 VND 1-day pass, 90,000 VND 3-day pass); CT01/CT02 Trung Lương – Mỹ Thuận – Cần Thơ Expressway coaches and Mekong riverboats.',
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
            'focal_nodes' => ['Phú Quốc', 'Côn Đảo', 'Phú Quý'],
            'distance_from_hanoi' => '1,850–2,050 km',
            'transit_summary' => 'Direct flights to PQC (GPS 10.1699, 103.9931) or VCS; high-speed catamarans (Phú Quốc Express, Superdong) operating from Rạch Giá, Hà Tiên, and Vũng Tàu; express sea routes to Phú Quý from Phan Thiết harbor (GPS 10.9234, 108.1062).',
            'transit_hours' => '50m flight from HCMC // 2.5h fast ferry',
            'typical_daily_spend' => '1,600,000–4,200,000 VND',
            'spend_usd' => '~$63–$165 USD',
            'optimal_dry_window' => 'November–April',
            'microclimate_verdict' => 'Calm turquoise waters and minimal sea swell in winter and spring; southwest monsoon brings choppy crossings and surf from July to September.',
            'field_caution' => 'Ferry crossings to both Phú Quốc, Côn Đảo, and Phú Quý are suspended when winds exceed Beaufort scale 6. For tight itineraries, prioritize air travel.',
            'primary_route_slug' => '/destinations/phu-quoc-travel-guide/',
            'primary_route_label' => 'Explore Island Retraite Guides',
            'coordinates' => ['x' => 135, 'y' => 460],
        ],
    ];
}

function vg_interactive_map_shortcode(array $atts = []): string
{
    if (function_exists('vg_enqueue_interactive_shortcode_assets')) {
        vg_enqueue_interactive_shortcode_assets();
    }

    $corridors = vg_get_regional_corridors_data();
    $default_id = 'central-heritage';
    $active_corridor = $corridors[$default_id] ?? reset($corridors);

    ob_start();
    ?>
    <section class="vg-interactive-map-desk" data-vg-interactive-map aria-label="<?php esc_attr_e('Vietnam Interactive Cartography & Regional Corridors', 'vietnamguide-premium'); ?>">
        <div id="vg-map-aria-status" class="screen-reader-text" role="status" aria-live="polite"></div>
        <div class="vg-interactive-map-header">
            <span class="vg-interactive-map-tag">
                <?php esc_html_e('CARTOGRAPHIC INTELLIGENCE // 6 TRANSIT CORRIDORS', 'vietnamguide-premium'); ?>
            </span>
            <h3 class="vg-interactive-map-title" role="heading" aria-level="3">
                <?php esc_html_e('Vietnam Regional Exploration Desk', 'vietnamguide-premium'); ?>
            </h3>
            <p class="vg-interactive-map-lede">
                <?php esc_html_e('Select any transit corridor to inspect distance telemetry, seasonal windows, verified daily spend, and transport connectivity across Vietnam.', 'vietnamguide-premium'); ?>
            </p>
        </div>

        <noscript>
            <div class="vg-map-noscript-fallback">
                <h4 class="vg-map-fallback-title"><?php esc_html_e('Vietnam Regional Transit Corridors (Data Overview)', 'vietnamguide-premium'); ?></h4>
                <table class="vg-guide-table">
                    <caption class="vg-table-caption"><?php esc_html_e('Vietnam Regional Exploration Corridors and Telemetry', 'vietnamguide-premium'); ?></caption>
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Transit Corridor', 'vietnamguide-premium'); ?></th>
                            <th scope="col"><?php esc_html_e('Focal Destinations', 'vietnamguide-premium'); ?></th>
                            <th scope="col"><?php esc_html_e('Distance from Hanoi', 'vietnamguide-premium'); ?></th>
                            <th scope="col"><?php esc_html_e('Transit Duration', 'vietnamguide-premium'); ?></th>
                            <th scope="col"><?php esc_html_e('Typical Daily Spend', 'vietnamguide-premium'); ?></th>
                            <th scope="col"><?php esc_html_e('Optimal Season', 'vietnamguide-premium'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($corridors as $corridor): ?>
                            <tr>
                                <th scope="row"><?php echo esc_html($corridor['name']); ?></th>
                                <td><?php echo esc_html(implode(', ', $corridor['focal_nodes'])); ?></td>
                                <td><?php echo esc_html($corridor['distance_from_hanoi']); ?></td>
                                <td><?php echo esc_html($corridor['transit_hours']); ?></td>
                                <td><?php echo esc_html($corridor['typical_daily_spend']); ?></td>
                                <td><?php echo esc_html($corridor['optimal_dry_window']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </noscript>

        <div class="vg-interactive-map-layout">
            <!-- Left: Vector Cartography Column -->
            <div class="vg-interactive-map-canvas-col">
                <div class="vg-map-canvas-wrapper" role="region" aria-label="<?php esc_attr_e('Interactive Vietnam corridor map', 'vietnamguide-premium'); ?>">
                    <svg class="vg-map-svg" viewBox="0 0 320 540" xmlns="http://www.w3.org/2000/svg" role="group" aria-label="<?php esc_attr_e('Vietnam regional transit corridors map', 'vietnamguide-premium'); ?>">
                        <!-- Stylized S-curve coast path -->
                        <path class="vg-map-outline" d="M 120,40 Q 150,55 170,85 Q 185,110 185,145 Q 180,180 195,220 Q 215,260 235,300 Q 250,335 240,370 Q 220,410 180,440 Q 150,460 130,475 Q 120,490 140,500" fill="none" stroke="#E5DFD6" stroke-width="24" stroke-linecap="round" stroke-linejoin="round" />
                        <path class="vg-map-spine" d="M 120,40 Q 150,55 170,85 Q 185,110 185,145 Q 180,180 195,220 Q 215,260 235,300 Q 250,335 240,370 Q 220,410 180,440 Q 150,460 130,475 Q 120,490 140,500" fill="none" stroke="#0F2537" stroke-width="4" stroke-dasharray="6,4" opacity="0.45" />

                        <!-- Corridor Waypoint Anchors -->
                        <?php foreach ($corridors as $corridor):
                            $cx = (int) $corridor['coordinates']['x'];
                            $cy = (int) $corridor['coordinates']['y'];
                            $is_active = ($corridor['id'] === $default_id);
                        ?>
                            <g class="vg-map-pin-group <?php echo $is_active ? 'is-active' : ''; ?>" data-corridor-pin="<?php echo esc_attr($corridor['id']); ?>" tabindex="0" role="button" aria-label="<?php echo esc_attr($corridor['name'] . ' — ' . $corridor['focal_nodes'][0]); ?>">
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

        <details class="vg-map-metro-expressway-ledger" id="vg-map-metro-expressway-ledger" style="margin-top:1.35rem;background:#f8fafc;border:1px solid #cbd5e1;border-radius:10px;padding:0.95rem 1.15rem;">
            <summary class="vg-map-ledger-summary" style="cursor:pointer;font-weight:700;color:#0f2537;font-size:0.95rem;">
                <?php esc_html_e('🚇 2025–2026 Urban Metro Networks (HCMC Line 1 & Hanoi Lines 2A/3) & National Expressway Telemetry', 'vietnamguide-premium'); ?>
            </summary>
            <div class="vg-map-ledger-body" style="margin-top:0.85rem;">
                <p style="margin:0 0 0.75rem;font-size:0.86rem;color:#334155;line-height:1.55;">
                    <?php esc_html_e('Verified against HURC1 (HCMC Urban Railway No. 1), Hanoi Metro Company (HMC), and Ministry of Transport (MOT) North–South Expressway (CT01) commissioning schedules:', 'vietnamguide-premium'); ?>
                </p>
                <div class="vg-map-ledger-table-wrap" style="overflow-x:auto;">
                    <table class="vg-guide-table" style="width:100%;border-collapse:collapse;font-size:0.84rem;text-align:left;">
                        <caption class="vg-table-caption screen-reader-text"><?php esc_html_e('2025–2026 Vietnam Urban Metro Lines and National Expressway Corridors', 'vietnamguide-premium'); ?></caption>
                        <thead>
                            <tr style="border-bottom:2px solid #cbd5e1;color:#0f2537;">
                                <th scope="col" style="padding:0.5rem 0.6rem;"><?php esc_html_e('Metro / Expressway Corridor', 'vietnamguide-premium'); ?></th>
                                <th scope="col" style="padding:0.5rem 0.6rem;"><?php esc_html_e('Route & Key Stations / Nodes', 'vietnamguide-premium'); ?></th>
                                <th scope="col" style="padding:0.5rem 0.6rem;"><?php esc_html_e('Verified Fare / Travel Time & Operating Window', 'vietnamguide-premium'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <th scope="row" style="padding:0.5rem 0.6rem;"><strong>HCMC Metro Line 1 (Bến Thành – Suối Tiên)</strong></th>
                                <td style="padding:0.5rem 0.6rem;"><strong>19.7 km</strong> across 14 stations: 3 underground (<strong>Bến Thành</strong>, <strong>Nhà hát Thành phố</strong>, <strong>Ba Son</strong>) + 11 elevated to <strong>Bến xe Suối Tiên</strong> (New Eastern Bus Station).</td>
                                <td style="padding:0.5rem 0.6rem;"><strong>6,000–19,000 VND</strong> single ride (cashless EMV/QR) or <strong>7,000–20,000 VND</strong> cash; <strong>40,000 VND</strong> 1-day pass; <strong>90,000 VND</strong> 3-day pass; operates <strong>05:00–22:00</strong> (Mon–Thu) &amp; <strong>05:00–23:00</strong> (Fri–Sun).</td>
                            </tr>
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <th scope="row" style="padding:0.5rem 0.6rem;"><strong>Hanoi Metro Line 2A (Cát Linh – Hà Đông)</strong></th>
                                <td style="padding:0.5rem 0.6rem;"><strong>13.05 km</strong> elevated corridor linking <strong>Cát Linh</strong> (Ba Đình / Old Quarter west edge) with <strong>Yên Nghĩa</strong> (Hà Đông southwest bus hub) across 12 stations.</td>
                                <td style="padding:0.5rem 0.6rem;"><strong>9,000–19,000 VND</strong> single ride (<strong>8,595–18,625 VND</strong> cashless); <strong>40,000 VND</strong> unlimited 1-day pass; <strong>160,000 VND</strong> 7-day pass; <strong>05:30–22:00</strong> daily (6–10 min headway).</td>
                            </tr>
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <th scope="row" style="padding:0.5rem 0.6rem;"><strong>Hanoi Metro Line 3 (Nhổn – Cầu Giấy Elevated)</strong></th>
                                <td style="padding:0.5rem 0.6rem;"><strong>8.5 km</strong> elevated section connecting <strong>Nhổn</strong> through <strong>Quốc Gia University</strong> and <strong>Chùa Hà</strong> to <strong>Cầu Giấy</strong> interchange (8 operational stations).</td>
                                <td style="padding:0.5rem 0.6rem;"><strong>9,000–15,000 VND</strong> single ride; <strong>40,000 VND</strong> 1-day pass; <strong>05:30–22:00</strong> daily; direct bus transfer at Cầu Giấy to Hoan Kiem Lake &amp; Lotte Center.</td>
                            </tr>
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <th scope="row" style="padding:0.5rem 0.6rem;"><strong>CT04 / CT06 Northern Maritime Expressway</strong></th>
                                <td style="padding:0.5rem 0.6rem;"><strong>Hà Nội – Hải Phòng – Hạ Long – Vân Đồn – Móng Cái</strong> high-speed toll corridor linking Red River Delta to Tuần Châu &amp; Đồng Bài (Cát Bà) piers.</td>
                                <td style="padding:0.5rem 0.6rem;">Hanoi to <strong>Hạ Long Bay in 2.0–2.5 hours</strong> (down from 4h); Hanoi to <strong>Đồng Bài Ferry (Cát Bà) in 1.5–2.0 hours</strong>; Dcar limousine fare <strong>250,000–450,000 VND</strong>.</td>
                            </tr>
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <th scope="row" style="padding:0.5rem 0.6rem;"><strong>CT01 South-Central Expressway (Dầu Giây – Phan Thiết – Vĩnh Hảo – Cam Lâm – Nha Trang)</strong></th>
                                <td style="padding:0.5rem 0.6rem;">Continuous 4-lane expressway linking <strong>Ho Chi Minh City</strong> directly to <strong>Mũi Né / Phan Thiết</strong>, <strong>Vĩnh Hy Bay</strong>, <strong>Cam Ranh</strong>, and <strong>Nha Trang</strong>.</td>
                                <td style="padding:0.5rem 0.6rem;">HCMC to <strong>Mũi Né / Phan Thiết in 2.5–3.0 hours</strong> (down from 5h); HCMC to <strong>Nha Trang in 4.5–5.0 hours</strong> by highway limousine coach (<strong>280,000–480,000 VND</strong>).</td>
                            </tr>
                            <tr>
                                <th scope="row" style="padding:0.5rem 0.6rem;"><strong>CT01 / CT02 Mekong Delta Expressway (Trung Lương – Mỹ Thuận – Cần Thơ)</strong></th>
                                <td style="padding:0.5rem 0.6rem;">Expressway spine and <strong>Mỹ Thuận 2 Bridge</strong> connecting HCMC (Bến xe Miền Tây) across Tiền Giang and Vĩnh Long directly to <strong>Cần Thơ</strong>.</td>
                                <td style="padding:0.5rem 0.6rem;">HCMC to <strong>Cần Thơ (Cái Răng Floating Market hub) in 2.0–2.5 hours</strong> (down from 3.5–4h); Phương Trang (Futa) &amp; limousine fare <strong>165,000–250,000 VND</strong>.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
        <script>
        (function() {
            'use strict';
            function initInteractiveMap() {
                if (window.__vgInteractiveMapInit) {
                    return;
                }
                var mapDesks = document.querySelectorAll('[data-vg-interactive-map]');
                if (!mapDesks.length) {
                    return;
                }
                window.__vgInteractiveMapInit = true;
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
                        var statusEl = mapDesk.querySelector('[role="status"]');
                        if (statusEl) {
                            var activeBtn = mapDesk.querySelector('[data-corridor-target="' + id + '"]');
                            var corridorName = activeBtn ? activeBtn.textContent.trim() : id;
                            statusEl.textContent = 'Active transit corridor: ' + corridorName;
                        }
                    }

                    Array.prototype.forEach.call(tabBtns, function(btn, idx) {
                        btn.addEventListener('click', function() {
                            var targetId = this.getAttribute('data-corridor-target');
                            if (targetId) {
                                activate(targetId);
                            }
                        });
                        btn.addEventListener('keydown', function(e) {
                            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                                e.preventDefault();
                                var nextIdx = e.key === 'ArrowRight'
                                    ? (idx + 1) % tabBtns.length
                                    : (idx - 1 + tabBtns.length) % tabBtns.length;
                                var nextBtn = tabBtns[nextIdx];
                                if (nextBtn) {
                                    nextBtn.focus();
                                    var targetId = nextBtn.getAttribute('data-corridor-target');
                                    if (targetId) {
                                        activate(targetId);
                                    }
                                }
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
                        pin.addEventListener('keydown', function(e) {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
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
    if (function_exists('vg_enqueue_interactive_shortcode_assets')) {
        vg_enqueue_interactive_shortcode_assets();
    }
    return ob_get_clean();
}
add_shortcode('vg_interactive_map', 'vg_interactive_map_shortcode');

/**
 * Automatically injects the Interactive Corridor Map on hub pages, itinerary routes,
 * and inter-regional transit/comparison guides.
 */
function vg_inject_interactive_map_on_hub_and_itineraries(string $content): string
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
    $pageUri = '';
    if (function_exists('get_post')) {
        $post = get_post();
        if ($post instanceof WP_Post) {
            $slug = (string) ($post->post_name ?? '');
            if (function_exists('get_page_uri')) {
                $pageUri = trim((string) get_page_uri($post), '/');
            }
        }
    }
    if ($slug === '' && function_exists('get_post_field')) {
        $slug = (string) get_post_field('post_name');
    }

    $mapTargetSlugs = [
        'destinations',
        'itineraries',
        '7-days-in-vietnam',
        '10-days-in-vietnam',
        '14-days-in-vietnam',
        '21-days-in-vietnam',
        '3-weeks-in-vietnam-route',
        'northern-vietnam-7-day-itinerary',
        'central-vietnam-5-day-itinerary',
        'southern-vietnam-7-day-itinerary',
        'central-vietnam-itinerary',
        'northeast-vietnam-itinerary',
        'northwest-vietnam-itinerary',
        'southern-vietnam-itinerary',
        'central-highlands-vietnam-itinerary',
        'north-central-south-vietnam',
        'hanoi-vs-ho-chi-minh-city',
        'best-vietnam-routes-first-time-visitors',
        'best-places-to-visit-vietnam',
        'best-vietnam-cities-for-first-time-visitors',
        'transport-within-vietnam',
        'vietnam-train-travel',
        'vietnam-domestic-flights-guide',
    ];

    $isTarget = in_array($slug, $mapTargetSlugs, true)
        || str_starts_with($pageUri, 'itineraries/')
        || (function_exists('is_page') && (is_page('destinations') || is_page('itineraries')));

    if (! $isTarget) {
        return $content;
    }

    if ((function_exists('has_shortcode') && has_shortcode($content, 'vg_interactive_map')) || strpos($content, 'vg-interactive-map') !== false) {
        return $content;
    }

    if ($postId) {
        $injectedPosts[$postId] = true;
    }

    $mapHtml = vg_interactive_map_shortcode();

    return $content . "\n\n" . $mapHtml;
}
if (function_exists('add_filter')) {
    add_filter('the_content', 'vg_inject_interactive_map_on_hub_and_itineraries', 22);
}


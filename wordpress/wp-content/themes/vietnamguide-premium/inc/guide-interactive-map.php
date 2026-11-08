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
            'coordinates' => ['x' => 85, 'y' => 67],
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
            'coordinates' => ['x' => 142, 'y' => 108],
        ],
        'central-heritage' => [
            'id' => 'central-heritage',
            'name' => 'Central Heritage Coast',
            'vietnamese' => 'Duyên Hải Miền Trung',
            'focal_nodes' => ['Huế', 'Đà Nẵng', 'Hội An', 'Phong Nha'],
            'distance_from_hanoi' => '500–785 km',
            'transit_summary' => 'Reunification Express trains SE1–SE8 and Central Heritage Train HĐ1/HĐ2 (180,000–231,000 VND, 10-min Lăng Cô Bay stop) crossing Hải Vân Pass; daily flights into DAD, HUI (Terminal T2), and VDH; Đồng Hới Station (GPS 17.4722, 106.6042) shuttles to Phong Nha-Kẻ Bàng karst network.',
            'transit_hours' => '1.2h flight // 14–16h rail',
            'typical_daily_spend' => '1,150,000–2,650,000 VND',
            'spend_usd' => '~$45–$104 USD',
            'optimal_dry_window' => 'February–August',
            'microclimate_verdict' => 'Consistent sunshine and calm seas spring through mid-summer; heavy seasonal rainfall and high tides (triều cường) from October through late November.',
            'field_caution' => 'Low-lying quarters in Hội An Ancient Town face localized river flooding during late autumn monsoons. Confirm hotel elevations when booking October–November.',
            'primary_route_slug' => '/compare/da-nang-vs-hoi-an/',
            'primary_route_label' => 'Explore Da Nang & Hoi An Decisions',
            'coordinates' => ['x' => 192, 'y' => 249],
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
            'coordinates' => ['x' => 238, 'y' => 332],
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
            'coordinates' => ['x' => 164, 'y' => 424],
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
            'coordinates' => ['x' => 88, 'y' => 443],
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
                        <defs>
                            <linearGradient id="vgSeaGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#EEF4F8" />
                                <stop offset="100%" stop-color="#E2EDF5" />
                            </linearGradient>
                            <linearGradient id="vgLandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#F3EDE0" />
                                <stop offset="100%" stop-color="#E5DCCB" />
                            </linearGradient>
                        </defs>

                        <!-- Cartographic Maritime Frame & Graticule Grid (WGS84 8N-23.8N, 101.5E-112.5E) -->
                        <rect x="2" y="2" width="316" height="536" rx="6" fill="url(#vgSeaGrad)" stroke="#CBD5E1" stroke-width="1" />
                        <g class="vg-map-graticule" stroke="#94A3B8" stroke-width="0.45" stroke-dasharray="2,3" opacity="0.55">
                            <line x1="2" y1="77.8" x2="318" y2="77.8" />
                            <line x1="2" y1="201.8" x2="318" y2="201.8" />
                            <line x1="2" y1="325.8" x2="318" y2="325.8" />
                            <line x1="2" y1="449.8" x2="318" y2="449.8" />
                            <line x1="89.3" y1="2" x2="89.3" y2="538" />
                            <line x1="203.3" y1="2" x2="203.3" y2="538" />
                        </g>
                        <g class="vg-map-graticule-labels" font-family="'JetBrains Mono', monospace" font-size="7" fill="#64748B" opacity="0.8">
                            <text x="6" y="75">22°N</text>
                            <text x="6" y="199">18°N</text>
                            <text x="6" y="323">14°N</text>
                            <text x="6" y="447">10°N</text>
                            <text x="78" y="533">104°E</text>
                            <text x="192" y="533">108°E</text>
                        </g>

                        <!-- Continental Shelf & True Natural Earth 10m Geographic Boundary of Vietnam -->
                        <path d="M189.6,304.0 L188.7,300.9 L190.7,298.3 L187.9,296.1 L187.4,294.0 L191.5,293.0 L193.7,286.5 L191.1,282.1 L188.8,281.8 L188.6,279.9 L185.3,279.4 L179.0,270.8 L180.1,268.2 L185.5,266.3 L187.4,263.5 L187.2,261.7 L183.3,261.8 L179.1,258.7 L177.3,255.1 L173.5,254.3 L170.6,250.9 L170.4,247.4 L167.1,250.9 L165.4,250.0 L164.5,245.6 L162.6,244.8 L161.5,242.6 L161.2,233.1 L160.4,233.9 L158.2,233.2 L154.4,224.0 L151.5,224.8 L141.1,213.5 L138.4,211.9 L135.0,206.3 L134.6,201.9 L131.1,196.2 L129.4,195.5 L128.2,197.2 L126.5,196.2 L126.0,193.9 L124.5,194.1 L122.5,192.3 L122.4,190.5 L120.0,187.7 L121.0,183.8 L122.6,182.7 L121.0,180.2 L109.5,177.1 L103.0,171.5 L95.3,167.7 L93.5,165.1 L84.9,161.5 L87.9,158.7 L90.4,158.0 L91.8,155.8 L89.3,149.7 L91.8,150.6 L95.8,148.9 L97.7,150.5 L99.5,149.5 L103.7,152.1 L106.8,152.0 L108.5,148.3 L112.2,146.3 L112.5,144.5 L110.8,143.9 L111.5,142.6 L116.3,139.9 L116.7,137.4 L115.1,134.4 L112.6,132.9 L108.9,133.7 L107.8,132.2 L108.7,129.2 L105.7,127.0 L99.5,126.2 L101.9,123.2 L104.7,123.2 L106.8,119.8 L103.3,118.0 L102.1,116.1 L100.3,115.9 L97.5,112.3 L91.9,110.0 L83.1,113.6 L80.3,119.4 L74.7,116.5 L73.3,114.5 L71.0,115.4 L64.0,112.9 L61.2,107.5 L59.4,106.7 L57.4,103.3 L57.6,101.8 L55.1,100.9 L57.2,99.3 L58.4,94.8 L56.5,95.6 L60.0,91.0 L59.3,85.9 L56.4,87.0 L55.2,82.9 L53.7,87.7 L50.9,88.5 L49.1,80.9 L45.9,79.1 L46.0,77.1 L38.1,70.2 L35.6,65.5 L38.5,65.1 L44.9,54.1 L46.3,54.2 L47.5,56.2 L49.0,55.6 L56.0,59.2 L58.0,63.0 L61.6,64.5 L69.6,53.4 L72.2,54.9 L73.5,58.6 L75.1,59.8 L76.3,56.1 L79.2,53.0 L88.1,62.1 L91.3,53.5 L96.1,52.2 L96.0,54.8 L99.4,56.5 L105.0,51.9 L106.0,52.8 L109.6,52.2 L112.7,49.2 L112.0,44.1 L114.0,41.7 L115.9,42.0 L119.5,39.6 L123.5,38.9 L126.6,35.5 L132.6,41.7 L133.5,44.9 L137.4,45.4 L142.1,49.8 L148.5,47.4 L152.0,48.4 L153.5,51.3 L159.3,50.2 L160.2,49.1 L168.8,53.1 L165.3,60.2 L162.7,59.3 L161.3,64.2 L161.7,67.1 L164.3,68.2 L165.1,70.9 L165.0,79.0 L167.0,77.5 L172.5,80.4 L175.0,80.0 L174.8,83.7 L179.7,86.6 L182.5,86.5 L184.7,90.2 L186.9,88.6 L189.3,90.8 L196.4,88.4 L198.5,89.0 L203.0,93.7 L201.2,95.1 L200.7,92.5 L199.3,92.4 L198.6,93.7 L197.1,92.5 L196.2,92.9 L197.3,93.9 L196.1,96.1 L193.8,96.1 L191.6,99.7 L189.6,98.0 L189.8,99.5 L186.6,98.6 L187.4,99.9 L185.2,100.3 L184.8,102.9 L185.8,103.1 L185.4,107.9 L180.8,109.0 L180.4,110.7 L176.6,110.1 L179.0,107.5 L177.1,108.6 L176.3,107.8 L176.1,108.8 L175.5,107.8 L174.8,110.3 L170.6,109.0 L173.1,111.6 L171.2,110.7 L171.0,112.4 L168.3,110.1 L169.6,109.5 L169.0,108.0 L164.5,108.2 L165.7,108.6 L164.9,108.6 L165.5,110.1 L167.8,110.7 L167.5,112.4 L166.2,112.4 L169.2,118.8 L168.1,117.7 L165.1,118.2 L166.3,119.4 L164.4,120.3 L163.0,119.2 L164.1,121.4 L161.0,123.0 L162.8,122.8 L162.8,125.0 L161.0,126.2 L163.2,125.9 L162.2,128.1 L163.1,131.3 L160.4,130.2 L162.5,132.6 L157.1,133.2 L151.3,140.0 L149.3,140.9 L149.3,138.8 L148.6,140.9 L147.0,139.8 L145.0,142.1 L144.2,146.2 L142.5,146.8 L144.1,147.0 L141.1,152.6 L140.3,152.1 L140.9,157.7 L139.7,158.3 L140.7,162.4 L138.9,163.6 L138.8,167.4 L136.8,168.0 L135.2,171.2 L135.8,173.6 L138.2,175.3 L137.0,175.3 L137.4,176.2 L139.2,177.4 L139.1,181.3 L140.1,178.6 L142.8,186.0 L144.8,187.2 L148.9,193.8 L151.4,193.7 L154.0,195.5 L156.1,197.7 L155.0,198.7 L156.1,200.0 L156.5,198.7 L158.6,198.0 L158.4,199.5 L161.2,203.4 L158.7,205.5 L160.4,210.8 L154.4,208.9 L158.5,210.1 L157.2,210.5 L157.9,211.6 L158.1,210.7 L160.6,211.6 L168.0,222.4 L177.9,230.1 L180.6,235.3 L178.8,236.9 L180.9,235.7 L192.9,245.6 L187.4,243.7 L191.7,245.6 L192.9,246.5 L192.1,246.9 L193.6,246.7 L193.6,248.4 L194.6,247.7 L196.4,249.4 L198.7,252.0 L197.4,252.0 L198.2,254.4 L200.3,255.2 L201.3,252.9 L203.6,253.9 L204.5,253.1 L205.9,256.5 L204.7,255.7 L204.8,256.5 L209.1,257.1 L207.3,259.2 L208.9,261.6 L209.8,261.0 L208.9,263.7 L210.6,263.5 L210.2,258.8 L212.8,259.1 L212.2,260.7 L210.8,260.5 L211.2,263.9 L214.3,267.0 L213.9,267.8 L214.9,267.5 L216.2,271.6 L220.9,278.4 L222.7,278.7 L221.2,280.7 L223.1,279.6 L225.3,282.8 L225.3,284.7 L226.7,284.1 L225.7,283.2 L226.8,281.5 L228.5,285.9 L230.4,287.0 L227.8,290.1 L228.8,291.1 L229.2,290.3 L231.3,299.9 L234.1,303.2 L233.5,308.3 L238.6,321.7 L236.7,320.9 L240.5,329.6 L240.4,333.3 L239.4,333.4 L238.9,329.2 L238.0,331.8 L239.1,332.7 L238.2,334.4 L240.1,340.4 L237.7,337.2 L241.4,342.4 L240.5,344.2 L239.5,343.4 L240.3,342.5 L238.7,341.8 L238.4,344.6 L240.5,345.9 L240.1,347.6 L239.1,347.6 L240.1,349.5 L240.4,347.4 L240.9,348.1 L240.5,352.7 L245.1,360.0 L244.7,361.5 L244.2,360.6 L242.3,362.2 L243.7,366.4 L245.2,367.5 L244.6,370.1 L243.2,366.5 L242.5,368.0 L241.5,367.3 L242.9,365.7 L242.0,363.1 L237.6,367.8 L237.6,370.7 L238.8,369.6 L238.5,371.6 L241.6,375.5 L240.2,376.9 L237.9,374.2 L236.0,374.1 L238.7,378.6 L236.8,379.2 L238.2,381.6 L237.0,381.8 L240.1,391.8 L238.2,391.6 L238.0,387.3 L236.4,384.3 L237.8,387.6 L235.5,393.1 L237.6,391.9 L238.7,396.0 L235.6,401.1 L232.3,399.2 L232.3,407.8 L230.4,409.2 L227.1,408.5 L224.0,413.2 L218.4,414.0 L216.7,417.2 L213.8,418.1 L211.8,421.5 L210.6,420.3 L206.3,421.3 L203.2,428.1 L201.0,427.4 L198.2,428.3 L189.9,433.9 L184.3,435.7 L182.5,438.1 L180.2,437.2 L180.9,436.3 L179.8,435.0 L176.5,434.9 L176.5,434.0 L174.5,434.9 L175.7,431.0 L174.3,428.1 L175.5,433.2 L174.5,430.4 L175.1,433.6 L173.7,435.3 L173.1,431.2 L171.4,429.6 L169.9,430.3 L167.6,428.9 L168.3,431.1 L167.2,433.6 L164.9,433.6 L165.3,434.5 L163.2,436.2 L162.8,434.7 L162.8,435.8 L163.8,436.3 L164.9,435.1 L165.9,436.4 L167.4,435.3 L168.8,441.0 L158.3,440.0 L168.9,444.8 L167.3,448.1 L166.3,448.3 L166.1,447.2 L164.8,450.5 L163.1,450.4 L159.6,448.0 L154.6,441.7 L159.4,448.7 L166.1,453.0 L165.3,454.7 L165.1,453.4 L164.4,455.2 L163.0,455.3 L149.5,442.5 L162.6,457.9 L161.7,462.7 L160.4,463.8 L157.2,463.8 L153.4,461.2 L144.3,451.2 L141.1,449.6 L148.3,457.6 L151.9,464.0 L151.8,469.4 L133.1,476.8 L129.4,480.8 L126.1,487.4 L123.0,488.8 L120.6,492.1 L113.7,494.3 L109.9,493.1 L113.1,491.3 L113.0,490.1 L115.2,490.6 L116.7,489.5 L113.4,489.4 L115.7,488.6 L115.5,486.7 L112.5,487.9 L111.3,486.4 L112.4,482.8 L113.7,457.9 L114.7,454.4 L116.9,454.3 L119.3,451.9 L120.2,451.8 L121.0,454.2 L120.8,451.3 L117.8,446.9 L114.9,446.8 L111.3,442.8 L109.4,442.9 L106.7,445.3 L106.0,441.7 L103.6,438.7 L104.4,437.1 L102.6,438.1 L102.1,436.8 L105.4,433.4 L112.6,433.8 L119.0,428.4 L119.6,426.7 L118.1,422.4 L120.0,420.4 L120.5,421.5 L126.8,423.8 L129.4,419.9 L131.3,420.6 L139.2,418.3 L142.3,423.8 L143.4,424.1 L144.3,422.0 L146.8,424.4 L151.3,426.0 L149.7,421.1 L150.0,419.8 L151.6,419.5 L150.8,417.1 L149.5,416.1 L148.7,417.1 L141.8,409.8 L142.7,405.5 L142.1,401.8 L140.3,400.6 L140.7,399.4 L142.2,398.3 L144.4,398.9 L146.7,394.9 L158.4,398.3 L157.6,395.2 L158.8,392.0 L157.5,388.7 L166.2,388.7 L168.3,385.8 L173.4,385.3 L180.8,378.3 L183.9,377.6 L186.0,380.0 L189.4,377.1 L190.9,369.0 L187.9,356.1 L192.0,345.3 L192.0,341.3 L187.2,332.0 L187.0,326.3 L184.5,325.2 L183.9,322.1 L184.8,315.2 L185.8,312.7 L188.0,311.9 Z M91.6,438.3 L91.7,442.1 L90.2,446.8 L90.7,449.6 L89.6,448.9 L87.5,441.6 L85.2,439.9 L84.9,438.3 L87.4,438.4 L89.1,435.8 Z M165.1,488.9 L164.5,488.9 L164.9,490.1 L163.7,490.1 L163.4,491.5 L162.7,491.5 L162.5,489.9 L165.0,488.0 Z M177.0,115.0 L177.4,115.4 L176.6,114.6 L176.5,115.7 L175.7,115.4 L176.8,117.1 L176.1,118.0 L176.1,117.3 L175.3,117.3 L175.7,116.7 L174.7,116.4 L174.9,117.1 L172.2,114.4 L173.5,112.6 L173.7,113.3 L174.9,113.7 L175.8,113.2 L176.3,114.3 L177.8,115.0 Z M191.3,102.2 L192.0,102.1 L191.6,102.8 L189.7,103.8 L188.2,105.8 L185.8,107.3 L185.4,107.0 L186.7,102.7 L187.1,103.0 L187.6,102.4 L187.8,100.2 L189.7,101.6 L189.9,102.6 Z M241.1,380.9 L241.7,381.6 L240.5,382.0 L238.7,380.7 Z M230.1,432.7 L230.6,434.2 L229.6,433.4 Z M170.2,112.5 L171.4,114.3 L171.1,115.3 L170.4,115.1 L169.6,112.6 L169.6,113.7 L168.2,113.2 L168.6,110.9 L169.7,111.4 Z M189.7,111.6 L188.0,114.6 L188.2,112.4 Z M189.9,108.2 L190.7,107.7 L189.3,111.2 L186.0,112.0 L188.8,109.9 L189.1,108.2 L189.6,108.4 L189.9,107.5 Z" fill="none" stroke="#CBD5E1" stroke-width="3.2" stroke-linejoin="round" opacity="0.65" />
                        <path class="vg-map-outline" d="M189.6,304.0 L188.7,300.9 L190.7,298.3 L187.9,296.1 L187.4,294.0 L191.5,293.0 L193.7,286.5 L191.1,282.1 L188.8,281.8 L188.6,279.9 L185.3,279.4 L179.0,270.8 L180.1,268.2 L185.5,266.3 L187.4,263.5 L187.2,261.7 L183.3,261.8 L179.1,258.7 L177.3,255.1 L173.5,254.3 L170.6,250.9 L170.4,247.4 L167.1,250.9 L165.4,250.0 L164.5,245.6 L162.6,244.8 L161.5,242.6 L161.2,233.1 L160.4,233.9 L158.2,233.2 L154.4,224.0 L151.5,224.8 L141.1,213.5 L138.4,211.9 L135.0,206.3 L134.6,201.9 L131.1,196.2 L129.4,195.5 L128.2,197.2 L126.5,196.2 L126.0,193.9 L124.5,194.1 L122.5,192.3 L122.4,190.5 L120.0,187.7 L121.0,183.8 L122.6,182.7 L121.0,180.2 L109.5,177.1 L103.0,171.5 L95.3,167.7 L93.5,165.1 L84.9,161.5 L87.9,158.7 L90.4,158.0 L91.8,155.8 L89.3,149.7 L91.8,150.6 L95.8,148.9 L97.7,150.5 L99.5,149.5 L103.7,152.1 L106.8,152.0 L108.5,148.3 L112.2,146.3 L112.5,144.5 L110.8,143.9 L111.5,142.6 L116.3,139.9 L116.7,137.4 L115.1,134.4 L112.6,132.9 L108.9,133.7 L107.8,132.2 L108.7,129.2 L105.7,127.0 L99.5,126.2 L101.9,123.2 L104.7,123.2 L106.8,119.8 L103.3,118.0 L102.1,116.1 L100.3,115.9 L97.5,112.3 L91.9,110.0 L83.1,113.6 L80.3,119.4 L74.7,116.5 L73.3,114.5 L71.0,115.4 L64.0,112.9 L61.2,107.5 L59.4,106.7 L57.4,103.3 L57.6,101.8 L55.1,100.9 L57.2,99.3 L58.4,94.8 L56.5,95.6 L60.0,91.0 L59.3,85.9 L56.4,87.0 L55.2,82.9 L53.7,87.7 L50.9,88.5 L49.1,80.9 L45.9,79.1 L46.0,77.1 L38.1,70.2 L35.6,65.5 L38.5,65.1 L44.9,54.1 L46.3,54.2 L47.5,56.2 L49.0,55.6 L56.0,59.2 L58.0,63.0 L61.6,64.5 L69.6,53.4 L72.2,54.9 L73.5,58.6 L75.1,59.8 L76.3,56.1 L79.2,53.0 L88.1,62.1 L91.3,53.5 L96.1,52.2 L96.0,54.8 L99.4,56.5 L105.0,51.9 L106.0,52.8 L109.6,52.2 L112.7,49.2 L112.0,44.1 L114.0,41.7 L115.9,42.0 L119.5,39.6 L123.5,38.9 L126.6,35.5 L132.6,41.7 L133.5,44.9 L137.4,45.4 L142.1,49.8 L148.5,47.4 L152.0,48.4 L153.5,51.3 L159.3,50.2 L160.2,49.1 L168.8,53.1 L165.3,60.2 L162.7,59.3 L161.3,64.2 L161.7,67.1 L164.3,68.2 L165.1,70.9 L165.0,79.0 L167.0,77.5 L172.5,80.4 L175.0,80.0 L174.8,83.7 L179.7,86.6 L182.5,86.5 L184.7,90.2 L186.9,88.6 L189.3,90.8 L196.4,88.4 L198.5,89.0 L203.0,93.7 L201.2,95.1 L200.7,92.5 L199.3,92.4 L198.6,93.7 L197.1,92.5 L196.2,92.9 L197.3,93.9 L196.1,96.1 L193.8,96.1 L191.6,99.7 L189.6,98.0 L189.8,99.5 L186.6,98.6 L187.4,99.9 L185.2,100.3 L184.8,102.9 L185.8,103.1 L185.4,107.9 L180.8,109.0 L180.4,110.7 L176.6,110.1 L179.0,107.5 L177.1,108.6 L176.3,107.8 L176.1,108.8 L175.5,107.8 L174.8,110.3 L170.6,109.0 L173.1,111.6 L171.2,110.7 L171.0,112.4 L168.3,110.1 L169.6,109.5 L169.0,108.0 L164.5,108.2 L165.7,108.6 L164.9,108.6 L165.5,110.1 L167.8,110.7 L167.5,112.4 L166.2,112.4 L169.2,118.8 L168.1,117.7 L165.1,118.2 L166.3,119.4 L164.4,120.3 L163.0,119.2 L164.1,121.4 L161.0,123.0 L162.8,122.8 L162.8,125.0 L161.0,126.2 L163.2,125.9 L162.2,128.1 L163.1,131.3 L160.4,130.2 L162.5,132.6 L157.1,133.2 L151.3,140.0 L149.3,140.9 L149.3,138.8 L148.6,140.9 L147.0,139.8 L145.0,142.1 L144.2,146.2 L142.5,146.8 L144.1,147.0 L141.1,152.6 L140.3,152.1 L140.9,157.7 L139.7,158.3 L140.7,162.4 L138.9,163.6 L138.8,167.4 L136.8,168.0 L135.2,171.2 L135.8,173.6 L138.2,175.3 L137.0,175.3 L137.4,176.2 L139.2,177.4 L139.1,181.3 L140.1,178.6 L142.8,186.0 L144.8,187.2 L148.9,193.8 L151.4,193.7 L154.0,195.5 L156.1,197.7 L155.0,198.7 L156.1,200.0 L156.5,198.7 L158.6,198.0 L158.4,199.5 L161.2,203.4 L158.7,205.5 L160.4,210.8 L154.4,208.9 L158.5,210.1 L157.2,210.5 L157.9,211.6 L158.1,210.7 L160.6,211.6 L168.0,222.4 L177.9,230.1 L180.6,235.3 L178.8,236.9 L180.9,235.7 L192.9,245.6 L187.4,243.7 L191.7,245.6 L192.9,246.5 L192.1,246.9 L193.6,246.7 L193.6,248.4 L194.6,247.7 L196.4,249.4 L198.7,252.0 L197.4,252.0 L198.2,254.4 L200.3,255.2 L201.3,252.9 L203.6,253.9 L204.5,253.1 L205.9,256.5 L204.7,255.7 L204.8,256.5 L209.1,257.1 L207.3,259.2 L208.9,261.6 L209.8,261.0 L208.9,263.7 L210.6,263.5 L210.2,258.8 L212.8,259.1 L212.2,260.7 L210.8,260.5 L211.2,263.9 L214.3,267.0 L213.9,267.8 L214.9,267.5 L216.2,271.6 L220.9,278.4 L222.7,278.7 L221.2,280.7 L223.1,279.6 L225.3,282.8 L225.3,284.7 L226.7,284.1 L225.7,283.2 L226.8,281.5 L228.5,285.9 L230.4,287.0 L227.8,290.1 L228.8,291.1 L229.2,290.3 L231.3,299.9 L234.1,303.2 L233.5,308.3 L238.6,321.7 L236.7,320.9 L240.5,329.6 L240.4,333.3 L239.4,333.4 L238.9,329.2 L238.0,331.8 L239.1,332.7 L238.2,334.4 L240.1,340.4 L237.7,337.2 L241.4,342.4 L240.5,344.2 L239.5,343.4 L240.3,342.5 L238.7,341.8 L238.4,344.6 L240.5,345.9 L240.1,347.6 L239.1,347.6 L240.1,349.5 L240.4,347.4 L240.9,348.1 L240.5,352.7 L245.1,360.0 L244.7,361.5 L244.2,360.6 L242.3,362.2 L243.7,366.4 L245.2,367.5 L244.6,370.1 L243.2,366.5 L242.5,368.0 L241.5,367.3 L242.9,365.7 L242.0,363.1 L237.6,367.8 L237.6,370.7 L238.8,369.6 L238.5,371.6 L241.6,375.5 L240.2,376.9 L237.9,374.2 L236.0,374.1 L238.7,378.6 L236.8,379.2 L238.2,381.6 L237.0,381.8 L240.1,391.8 L238.2,391.6 L238.0,387.3 L236.4,384.3 L237.8,387.6 L235.5,393.1 L237.6,391.9 L238.7,396.0 L235.6,401.1 L232.3,399.2 L232.3,407.8 L230.4,409.2 L227.1,408.5 L224.0,413.2 L218.4,414.0 L216.7,417.2 L213.8,418.1 L211.8,421.5 L210.6,420.3 L206.3,421.3 L203.2,428.1 L201.0,427.4 L198.2,428.3 L189.9,433.9 L184.3,435.7 L182.5,438.1 L180.2,437.2 L180.9,436.3 L179.8,435.0 L176.5,434.9 L176.5,434.0 L174.5,434.9 L175.7,431.0 L174.3,428.1 L175.5,433.2 L174.5,430.4 L175.1,433.6 L173.7,435.3 L173.1,431.2 L171.4,429.6 L169.9,430.3 L167.6,428.9 L168.3,431.1 L167.2,433.6 L164.9,433.6 L165.3,434.5 L163.2,436.2 L162.8,434.7 L162.8,435.8 L163.8,436.3 L164.9,435.1 L165.9,436.4 L167.4,435.3 L168.8,441.0 L158.3,440.0 L168.9,444.8 L167.3,448.1 L166.3,448.3 L166.1,447.2 L164.8,450.5 L163.1,450.4 L159.6,448.0 L154.6,441.7 L159.4,448.7 L166.1,453.0 L165.3,454.7 L165.1,453.4 L164.4,455.2 L163.0,455.3 L149.5,442.5 L162.6,457.9 L161.7,462.7 L160.4,463.8 L157.2,463.8 L153.4,461.2 L144.3,451.2 L141.1,449.6 L148.3,457.6 L151.9,464.0 L151.8,469.4 L133.1,476.8 L129.4,480.8 L126.1,487.4 L123.0,488.8 L120.6,492.1 L113.7,494.3 L109.9,493.1 L113.1,491.3 L113.0,490.1 L115.2,490.6 L116.7,489.5 L113.4,489.4 L115.7,488.6 L115.5,486.7 L112.5,487.9 L111.3,486.4 L112.4,482.8 L113.7,457.9 L114.7,454.4 L116.9,454.3 L119.3,451.9 L120.2,451.8 L121.0,454.2 L120.8,451.3 L117.8,446.9 L114.9,446.8 L111.3,442.8 L109.4,442.9 L106.7,445.3 L106.0,441.7 L103.6,438.7 L104.4,437.1 L102.6,438.1 L102.1,436.8 L105.4,433.4 L112.6,433.8 L119.0,428.4 L119.6,426.7 L118.1,422.4 L120.0,420.4 L120.5,421.5 L126.8,423.8 L129.4,419.9 L131.3,420.6 L139.2,418.3 L142.3,423.8 L143.4,424.1 L144.3,422.0 L146.8,424.4 L151.3,426.0 L149.7,421.1 L150.0,419.8 L151.6,419.5 L150.8,417.1 L149.5,416.1 L148.7,417.1 L141.8,409.8 L142.7,405.5 L142.1,401.8 L140.3,400.6 L140.7,399.4 L142.2,398.3 L144.4,398.9 L146.7,394.9 L158.4,398.3 L157.6,395.2 L158.8,392.0 L157.5,388.7 L166.2,388.7 L168.3,385.8 L173.4,385.3 L180.8,378.3 L183.9,377.6 L186.0,380.0 L189.4,377.1 L190.9,369.0 L187.9,356.1 L192.0,345.3 L192.0,341.3 L187.2,332.0 L187.0,326.3 L184.5,325.2 L183.9,322.1 L184.8,315.2 L185.8,312.7 L188.0,311.9 Z" fill="url(#vgLandGrad)" stroke="#0F2537" stroke-width="1.25" stroke-linejoin="round" />
                        <path class="vg-map-islands" d="M91.6,438.3 L91.7,442.1 L90.2,446.8 L90.7,449.6 L89.6,448.9 L87.5,441.6 L85.2,439.9 L84.9,438.3 L87.4,438.4 L89.1,435.8 Z M165.1,488.9 L164.5,488.9 L164.9,490.1 L163.7,490.1 L163.4,491.5 L162.7,491.5 L162.5,489.9 L165.0,488.0 Z M177.0,115.0 L177.4,115.4 L176.6,114.6 L176.5,115.7 L175.7,115.4 L176.8,117.1 L176.1,118.0 L176.1,117.3 L175.3,117.3 L175.7,116.7 L174.7,116.4 L174.9,117.1 L172.2,114.4 L173.5,112.6 L173.7,113.3 L174.9,113.7 L175.8,113.2 L176.3,114.3 L177.8,115.0 Z M191.3,102.2 L192.0,102.1 L191.6,102.8 L189.7,103.8 L188.2,105.8 L185.8,107.3 L185.4,107.0 L186.7,102.7 L187.1,103.0 L187.6,102.4 L187.8,100.2 L189.7,101.6 L189.9,102.6 Z M241.1,380.9 L241.7,381.6 L240.5,382.0 L238.7,380.7 Z M230.1,432.7 L230.6,434.2 L229.6,433.4 Z M170.2,112.5 L171.4,114.3 L171.1,115.3 L170.4,115.1 L169.6,112.6 L169.6,113.7 L168.2,113.2 L168.6,110.9 L169.7,111.4 Z M189.7,111.6 L188.0,114.6 L188.2,112.4 Z M189.9,108.2 L190.7,107.7 L189.3,111.2 L186.0,112.0 L188.8,109.9 L189.1,108.2 L189.6,108.4 L189.9,107.5 Z" fill="url(#vgLandGrad)" stroke="#0F2537" stroke-width="1.0" stroke-linejoin="round" />

                        <!-- Major Hydrography: Red River (Song Hong) & Mekong Delta (Song Tien / Song Hau) -->
                        <path d="M 85,67 Q 112,86 142,108 Q 154,118 160,127" fill="none" stroke="#6096BA" stroke-width="1.1" opacity="0.7" />
                        <path d="M 122,428 Q 138,442 156,456 M 118,435 Q 136,452 151,466" fill="none" stroke="#6096BA" stroke-width="1.1" opacity="0.7" />

                        <!-- Sovereign Vietnamese Archipelagos: Quan Dao Hoang Sa & Quan Dao Truong Sa -->
                        <g class="vg-map-archipelago" aria-label="Quần đảo Hoàng Sa (Đà Nẵng, Việt Nam)">
                            <rect x="242" y="220" width="70" height="44" rx="4" fill="#E8F1F7" fill-opacity="0.75" stroke="#0F2537" stroke-width="0.75" stroke-dasharray="3,2" />
                            <circle cx="262" cy="235" r="2.2" fill="#0F2537" />
                            <circle cx="271" cy="232" r="1.8" fill="#0F2537" />
                            <circle cx="279" cy="238" r="2.0" fill="#0F2537" />
                            <circle cx="267" cy="242" r="1.6" fill="#0F2537" />
                            <circle cx="286" cy="243" r="1.5" fill="#0F2537" />
                            <text x="277" y="254" text-anchor="middle" font-family="'JetBrains Mono', monospace" font-size="6.8" font-weight="700" fill="#0F2537">QĐ. HOÀNG SA</text>
                            <text x="277" y="261" text-anchor="middle" font-family="'JetBrains Mono', monospace" font-size="5.8" fill="#334155">(Đà Nẵng)</text>
                        </g>

                        <g class="vg-map-archipelago" aria-label="Quần đảo Trường Sa (Khánh Hòa, Việt Nam)">
                            <rect x="236" y="442" width="76" height="52" rx="4" fill="#E8F1F7" fill-opacity="0.75" stroke="#0F2537" stroke-width="0.75" stroke-dasharray="3,2" />
                            <circle cx="255" cy="456" r="2.0" fill="#0F2537" />
                            <circle cx="268" cy="453" r="1.8" fill="#0F2537" />
                            <circle cx="282" cy="459" r="2.2" fill="#0F2537" />
                            <circle cx="262" cy="466" r="1.7" fill="#0F2537" />
                            <circle cx="275" cy="470" r="2.0" fill="#0F2537" />
                            <circle cx="290" cy="467" r="1.6" fill="#0F2537" />
                            <text x="274" y="483" text-anchor="middle" font-family="'JetBrains Mono', monospace" font-size="6.8" font-weight="700" fill="#0F2537">QĐ. TRƯỜNG SA</text>
                            <text x="274" y="490" text-anchor="middle" font-family="'JetBrains Mono', monospace" font-size="5.8" fill="#334155">(Khánh Hòa)</text>
                        </g>

                        <!-- East Sea (Bien Dong) & Compass / Scale Cartographic Legend -->
                        <text x="255" y="176" text-anchor="middle" font-family="'Fraunces', Georgia, serif" font-style="italic" font-size="10.5" font-weight="600" letter-spacing="1.4" fill="#3B6990" opacity="0.7">BIỂN ĐÔNG</text>
                        <text x="255" y="187" text-anchor="middle" font-family="'JetBrains Mono', monospace" font-size="6.5" letter-spacing="0.8" fill="#475569" opacity="0.65">EAST SEA</text>

                        <g class="vg-map-compass" transform="translate(286, 38)">
                            <circle cx="0" cy="0" r="12" fill="#FFFFFF" fill-opacity="0.85" stroke="#0F2537" stroke-width="0.8" />
                            <polygon points="0,-10 3,0 0,-2 -3,0" fill="#C99446" />
                            <polygon points="0,10 3,0 0,2 -3,0" fill="#0F2537" />
                            <text x="0" y="-14" text-anchor="middle" font-family="'JetBrains Mono', monospace" font-size="7.5" font-weight="700" fill="#0F2537">B</text>
                        </g>

                        <g class="vg-map-scale" transform="translate(14, 514)">
                            <line x1="0" y1="0" x2="48" y2="0" stroke="#0F2537" stroke-width="1.5" />
                            <line x1="0" y1="-3" x2="0" y2="3" stroke="#0F2537" stroke-width="1.5" />
                            <line x1="24" y1="-2" x2="24" y2="2" stroke="#0F2537" stroke-width="1" />
                            <line x1="48" y1="-3" x2="48" y2="3" stroke="#0F2537" stroke-width="1.5" />
                            <text x="0" y="-5" font-family="'JetBrains Mono', monospace" font-size="6.5" fill="#0F2537">0</text>
                            <text x="48" y="-5" text-anchor="end" font-family="'JetBrains Mono', monospace" font-size="6.5" fill="#0F2537">200 km</text>
                        </g>

                        <!-- True Geographic North-South Rail & Highway Transit Spine -->
                        <path class="vg-map-spine" d="M 85,67 L 142,108 L 146,132 L 165,218 L 192,249 L 209,262 L 238,332 L 237,380 L 206,420 L 164,424 L 139,448 L 88,443" fill="none" stroke="#C99446" stroke-width="2.2" stroke-dasharray="4,3" opacity="0.9" />

                        <!-- Secondary Geographic Reference Nodes -->
                        <g class="vg-map-secondary-nodes" font-family="'JetBrains Mono', monospace" font-size="7.5" fill="#334155">
                            <circle cx="117" cy="52" r="2.2" fill="#0F2537" opacity="0.65" />
                            <text x="122" y="54" class="vg-map-city-halo">Hà Giang</text>
                            <circle cx="177" cy="110" r="2.2" fill="#0F2537" opacity="0.65" />
                            <text x="183" y="113" class="vg-map-city-halo">Hạ Long</text>
                            <circle cx="209" cy="262" r="2.2" fill="#0F2537" opacity="0.65" />
                            <text x="204" y="266" text-anchor="end" class="vg-map-city-halo">Đà Nẵng</text>
                            <circle cx="216" cy="390" r="2.2" fill="#0F2537" opacity="0.65" />
                            <text x="176" y="392" class="vg-map-city-halo">Đà Lạt</text>
                            <circle cx="139" cy="448" r="2.2" fill="#0F2537" opacity="0.65" />
                            <text x="145" y="458" class="vg-map-city-halo">Cần Thơ</text>
                            <circle cx="164" cy="491" r="2.2" fill="#0F2537" opacity="0.65" />
                            <text x="171" y="494" class="vg-map-city-halo">Côn Đảo</text>
                        </g>

                        <!-- Corridor Waypoint Anchors -->
                        <?php foreach ($corridors as $corridor):
                            $cx = (int) $corridor['coordinates']['x'];
                            $cy = (int) $corridor['coordinates']['y'];
                            $is_active = ($corridor['id'] === $default_id);
                            $is_left_label = in_array($corridor['id'], ['maritime-archipelagos', 'red-river-maritime'], true);
                            $label_x = $is_left_label ? ($cx - 10) : ($cx + 11);
                            $label_anchor = $is_left_label ? 'end' : 'start';
                        ?>
                            <g class="vg-map-pin-group <?php echo $is_active ? 'is-active' : ''; ?>" data-corridor-pin="<?php echo esc_attr($corridor['id']); ?>" tabindex="0" role="button" aria-label="<?php echo esc_attr($corridor['name'] . ' — ' . $corridor['focal_nodes'][0]); ?>">
                                <circle class="vg-map-pin-pulse" cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="13" fill="#C99446" opacity="0.22" />
                                <circle class="vg-map-pin" cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="6.5" fill="<?php echo $is_active ? '#C99446' : '#0F2537'; ?>" stroke="#FFFFFF" stroke-width="2.2" />
                                <text class="vg-map-pin-label" x="<?php echo $label_x; ?>" y="<?php echo $cy + 3.5; ?>" text-anchor="<?php echo esc_attr($label_anchor); ?>" font-family="'JetBrains Mono', monospace" font-size="9.5" font-weight="700" fill="#0F2537"><?php echo esc_html($corridor['focal_nodes'][0]); ?></text>
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

        <details class="vg-map-metro-expressway-ledger vg-ref-ledger" id="vg-map-metro-expressway-ledger">
            <summary class="vg-map-ledger-summary vg-ref-summary">
                <?php esc_html_e('2025–2026 Urban Metro Networks (HCMC Line 1 & Hanoi Lines 2A/3) & National Expressway Telemetry', 'vietnamguide-premium'); ?>
            </summary>
            <div class="vg-map-ledger-body vg-ref-body">
                <p class="vg-ref-lead">
                    <?php esc_html_e('Verified against HURC1 (HCMC Urban Railway No. 1), Hanoi Metro Company (HMC), and Ministry of Transport (MOT) North–South Expressway (CT01) commissioning schedules:', 'vietnamguide-premium'); ?>
                </p>
                <div class="vg-map-ledger-table-wrap vg-ref-table-wrap">
                    <table class="vg-guide-table vg-ref-table">
                        <caption class="vg-table-caption screen-reader-text"><?php esc_html_e('2025–2026 Vietnam Urban Metro Lines and National Expressway Corridors', 'vietnamguide-premium'); ?></caption>
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('Metro / Expressway Corridor', 'vietnamguide-premium'); ?></th>
                                <th scope="col"><?php esc_html_e('Route & Key Stations / Nodes', 'vietnamguide-premium'); ?></th>
                                <th scope="col"><?php esc_html_e('Verified Fare / Travel Time & Operating Window', 'vietnamguide-premium'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row"><strong>HCMC Metro Line 1 (Bến Thành – Suối Tiên)</strong></th>
                                <td><strong>19.7 km</strong> across 14 stations: 3 underground (<strong>Bến Thành</strong>, <strong>Nhà hát Thành phố</strong>, <strong>Ba Son</strong>) + 11 elevated to <strong>Bến xe Suối Tiên</strong> (New Eastern Bus Station).</td>
                                <td><strong>6,000–19,000 VND</strong> single ride (cashless EMV/QR) or <strong>7,000–20,000 VND</strong> cash; <strong>40,000 VND</strong> 1-day pass; <strong>90,000 VND</strong> 3-day pass; operates <strong>05:00–22:00</strong> (Mon–Thu) &amp; <strong>05:00–23:00</strong> (Fri–Sun).</td>
                            </tr>
                            <tr>
                                <th scope="row"><strong>Hanoi Metro Line 2A (Cát Linh – Hà Đông)</strong></th>
                                <td><strong>13.05 km</strong> elevated corridor linking <strong>Cát Linh</strong> (Ba Đình / Old Quarter west edge) with <strong>Yên Nghĩa</strong> (Hà Đông southwest bus hub) across 12 stations.</td>
                                <td><strong>9,000–19,000 VND</strong> single ride (<strong>8,595–18,625 VND</strong> cashless); <strong>40,000 VND</strong> unlimited 1-day pass; <strong>160,000 VND</strong> 7-day pass; <strong>05:30–22:00</strong> daily (6–10 min headway).</td>
                            </tr>
                            <tr>
                                <th scope="row"><strong>Hanoi Metro Line 3 (Nhổn – Cầu Giấy Elevated)</strong></th>
                                <td><strong>8.5 km</strong> elevated section connecting <strong>Nhổn</strong> through <strong>Quốc Gia University</strong> and <strong>Chùa Hà</strong> to <strong>Cầu Giấy</strong> interchange (8 operational stations).</td>
                                <td><strong>9,000–15,000 VND</strong> single ride; <strong>40,000 VND</strong> 1-day pass; <strong>05:30–22:00</strong> daily; direct bus transfer at Cầu Giấy to Hoan Kiem Lake &amp; Lotte Center.</td>
                            </tr>
                            <tr>
                                <th scope="row"><strong>CT04 / CT06 Northern Maritime Expressway</strong></th>
                                <td><strong>Hà Nội – Hải Phòng – Hạ Long – Vân Đồn – Móng Cái</strong> high-speed toll corridor linking Red River Delta to Tuần Châu &amp; Đồng Bài (Cát Bà) piers.</td>
                                <td>Hanoi to <strong>Hạ Long Bay in 2.0–2.5 hours</strong> (down from 4h); Hanoi to <strong>Đồng Bài Ferry (Cát Bà) in 1.5–2.0 hours</strong>; Dcar limousine fare <strong>250,000–450,000 VND</strong>.</td>
                            </tr>
                            <tr>
                                <th scope="row"><strong>CT01 South-Central Expressway (Dầu Giây – Phan Thiết – Vĩnh Hảo – Cam Lâm – Nha Trang)</strong></th>
                                <td>Continuous 4-lane expressway linking <strong>Ho Chi Minh City</strong> directly to <strong>Mũi Né / Phan Thiết</strong>, <strong>Vĩnh Hy Bay</strong>, <strong>Cam Ranh</strong>, and <strong>Nha Trang</strong>.</td>
                                <td>HCMC to <strong>Mũi Né / Phan Thiết in 2.5–3.0 hours</strong> (down from 5h); HCMC to <strong>Nha Trang in 4.5–5.0 hours</strong> by highway limousine coach (<strong>280,000–480,000 VND</strong>).</td>
                            </tr>
                            <tr>
                                <th scope="row"><strong>CT01 / CT02 Mekong Delta Expressway (Trung Lương – Mỹ Thuận – Cần Thơ)</strong></th>
                                <td>Expressway spine and <strong>Mỹ Thuận 2 Bridge</strong> connecting HCMC (Bến xe Miền Tây) across Tiền Giang and Vĩnh Long directly to <strong>Cần Thơ</strong>.</td>
                                <td>HCMC to <strong>Cần Thơ (Cái Răng Floating Market hub) in 2.0–2.5 hours</strong> (down from 3.5–4h); Phương Trang (Futa) &amp; limousine fare <strong>165,000–250,000 VND</strong>.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
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


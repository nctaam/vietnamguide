<?php
/**
 * VietnamGuide Interactive Itinerary Finder Component
 *
 * Provides an interactive, zero-dependency client-side filterable route catalog
 * for travelers choosing Vietnam itineraries by duration, travel style, and gateway.
 *
 * @package VietnamGuide
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Returns the curated catalog of Vietnam itineraries.
 *
 * @return array<int, array<string, mixed>>
 */
function vg_get_itinerary_finder_catalog(): array
{
    return [
        [
            'id'              => 'classic-10d',
            'title'           => '10-Day Classic Vietnam',
            'duration_label'  => '10 Days',
            'duration_bucket' => '10d',
            'style_labels'    => ['Classic', 'First-Time'],
            'style_values'    => ['classic'],
            'gateway_label'   => 'Hanoi or HCMC',
            'gateway_value'   => 'both',
            'route_summary'   => 'Hanoi &rarr; Ha Long Bay &rarr; Hoi An &rarr; Ho Chi Minh City',
            'description'     => 'The signature 3-hub introductory route connecting northern limestone karsts (2.5h via CT04 expressway), lantern-lit heritage alleys in Hoi An, and southern commercial energy via two 80-minute domestic flights (HAN–DAD and DAD–SGN).',
            'highlights'      => [
                'Overnight 2D1N karst cruise on Ha Long or Lan Ha Bay ($135–$185 USD)',
                'Walking tour and 48-hour custom tailoring in Hoi An Ancient Town (120,000 VND heritage ticket)',
                'District 1 historic landmarks, Metro Line 1, and Ben Tre Mekong canals from Ho Chi Minh City',
            ],
            'url'             => '/itineraries/10-days-in-vietnam/',
            'season_url'      => '/plan/best-time-to-visit-vietnam/#month-nov',
            'cta_label'       => 'View 10-Day Itinerary',
        ],
        [
            'id'              => 'slow-14d',
            'title'           => '14-Day Slow & Balanced Route',
            'duration_label'  => '14 Days',
            'duration_bucket' => '14d',
            'style_labels'    => ['Slow Travel', 'Culture'],
            'style_values'    => ['slow', 'culture'],
            'gateway_label'   => 'Hanoi',
            'gateway_value'   => 'hanoi',
            'route_summary'   => 'Hanoi &rarr; Ninh Binh &rarr; Ha Long &rarr; Hue &rarr; Hoi An &rarr; HCMC',
            'description'     => 'Designed for travelers who prioritize cultural depth over rushed transits. Adds Ninh Binh karst river valleys (2h from Hanoi), direct 3.5h transfers to Ha Long Bay, and the scenic HĐ1/HĐ2 Hai Van Pass train between Hue and Da Nang.',
            'highlights'      => [
                '2.5-hour sampan paddle through limestone caves in Trang An or Tam Coc (250,000 VND)',
                'Hue Imperial Citadel and Royal Tombs (420,000 VND 3-site combo pass)',
                'Cycling through Tra Que vegetable village and Hoi An paddies to An Bang Beach (4.5 km)',
            ],
            'url'             => '/itineraries/14-days-in-vietnam/',
            'season_url'      => '/plan/best-time-to-visit-vietnam/#month-feb',
            'cta_label'       => 'View 14-Day Itinerary',
        ],
        [
            'id'              => 'highlights-7d',
            'title'           => '7-Day Northern Highlights',
            'duration_label'  => '7 Days',
            'duration_bucket' => '7d',
            'style_labels'    => ['Highlights', 'Classic'],
            'style_values'    => ['highlights', 'classic'],
            'gateway_label'   => 'Hanoi',
            'gateway_value'   => 'hanoi',
            'route_summary'   => 'Hanoi &rarr; Ninh Binh &rarr; Ha Long Bay &rarr; Hanoi',
            'description'     => 'A compact, zero-domestic-flight Northern Triangle circuit linking Hanoi (Noi Bai Express Bus 86, 45,000 VND), Ninh Binh (95 km south), and Ha Long / Lan Ha Bay via 2-to-3.5-hour expressway limousine hops.',
            'highlights'      => [
                'Old Quarter 36 Guilds street food walk and Temple of Literature (70,000 VND) in Hanoi',
                '486-step Hang Mua viewpoint (100,000 VND) and Trang An cave sampan (250,000 VND) in Ninh Binh',
                'Sunrise kayaking and cave excursions across Ha Long or Lan Ha Bay',
            ],
            'url'             => '/itineraries/7-days-in-vietnam/',
            'season_url'      => '/plan/best-time-to-visit-vietnam/#month-oct',
            'cta_label'       => 'View 7-Day Itinerary',
        ],
        [
            'id'              => 'grand-21d',
            'title'           => '21-Day Grand Vietnam Journey',
            'duration_label'  => '21 Days',
            'duration_bucket' => '21d',
            'style_labels'    => ['Grand Explorer', 'In-Depth'],
            'style_values'    => ['grand', 'slow'],
            'gateway_label'   => 'Hanoi',
            'gateway_value'   => 'hanoi',
            'route_summary'   => 'Hanoi &rarr; Sa Pa &rarr; Ha Long &rarr; Central Coast &rarr; Highlands &rarr; Mekong',
            'description'     => 'The comprehensive 3-week expedition spanning 1,726 km from northern highland terraces (Sapa SP3/SP4 sleeper or Ha Giang Loop) and Phong Nha caves down to Central Heritage cities and Mekong Delta floating waterways.',
            'highlights'      => [
                'Trekking 12–15 km through Muong Hoa Valley and ethnic homestays in Sapa',
                'Hai Van Pass coastal railway (HĐ1/HĐ2, 150,000–180,000 VND) between Hue and Da Nang',
                '5:30 AM Cai Rang Floating Market sampan (350,000–500,000 VND) in Can Tho, Mekong Delta',
            ],
            'url'             => '/itineraries/21-days-in-vietnam/',
            'season_url'      => '/plan/best-time-to-visit-vietnam/#month-mar',
            'cta_label'       => 'View 21-Day Itinerary',
        ],
        [
            'id'              => 'city-break-2d',
            'title'           => '2-3 Days Hanoi City Break',
            'duration_label'  => '2-3 Days',
            'duration_bucket' => 'short',
            'style_labels'    => ['City Break', 'Culture'],
            'style_values'    => ['classic', 'culture'],
            'gateway_label'   => 'Hanoi',
            'gateway_value'   => 'hanoi',
            'route_summary'   => 'Hanoi Old Quarter &rarr; French Quarter &rarr; West Lake &rarr; Street Food',
            'description'     => 'A focused 48-to-72-hour urban route covering Hoan Kiem Lake, Ba Dinh imperial landmarks, French Quarter tree-lined boulevards, and Old Quarter culinary alleys (30 km from HAN via Bus 86).',
            'highlights'      => [
                'Traditional egg coffee (ca phe trung, 35,000–45,000 VND) overlooking Hoan Kiem Lake',
                'Morning walk through the Temple of Literature (70,000 VND), Hoa Lo Prison (50,000 VND), and Opera House',
                'Evening pho bo and charcoal bun cha trail (50,000–70,000 VND) in Hoan Kiem District',
            ],
            'url'             => '/itineraries/hanoi-in-2-days/',
            'season_url'      => '/plan/best-time-to-visit-vietnam/#month-oct',
            'cta_label'       => 'View Hanoi 2-Day Guide',
        ],
        [
            'id'              => 'ha-giang-loop',
            'title'           => '3-5 Days Ha Giang Loop Adventure',
            'duration_label'  => '3-5 Days',
            'duration_bucket' => 'short',
            'style_labels'    => ['Adventure', 'Motorbike'],
            'style_values'    => ['adventure'],
            'gateway_label'   => 'Hanoi',
            'gateway_value'   => 'hanoi',
            'route_summary'   => 'Hanoi &rarr; Ha Giang &rarr; Dong Van &rarr; Ma Pi Leng Pass &rarr; Du Gia',
            'description'     => "Vietnam's 350 km northernmost karst circuit across Quan Ba, Yen Minh, Dong Van, and Meo Vac (reached by 6-hour VIP cabin bus from Hanoi; requires the 230,000 VND border permit and 1968 IDP or local Easy Rider).",
            'highlights'      => [
                'Riding Ma Pi Leng Pass (1,500m) and boating Tu San Canyon on the Nho Que River (120,000–150,000 VND)',
                'Exploring Dong Van UNESCO Global Karst Plateau Geopark and Hmong King Palace (30,000 VND)',
                'Overnight Tay and Dao stilt-house homestays and Du Gia mountain waterfall swim',
            ],
            'url'             => '/destinations/ha-giang-loop-planning-guide/',
            'season_url'      => '/plan/best-time-to-visit-vietnam/#month-oct',
            'cta_label'       => 'View Ha Giang Loop Guide',
        ],
        [
            'id'              => 'sapa-trekking',
            'title'           => '3-4 Days Sa Pa Mountain & Terraces',
            'duration_label'  => '3-4 Days',
            'duration_bucket' => 'short',
            'style_labels'    => ['Trekking', 'Nature'],
            'style_values'    => ['adventure', 'nature'],
            'gateway_label'   => 'Hanoi',
            'gateway_value'   => 'hanoi',
            'route_summary'   => 'Hanoi &rarr; Sa Pa Town &rarr; Muong Hoa Valley &rarr; Ta Van Village',
            'description'     => "Tiered rice terraces carved into Mount Fansipan's foothills at 1,500m–3,143m elevation, reached in 5.5 hours by CT05 Noi Bai – Lao Cai expressway cabin bus or overnight sleeper train SP3/SP4.",
            'highlights'      => [
                'Guided 12–15 km valley trek through Y Linh Ho, Lao Chai, and Ta Van villages',
                'Red Dao herbal bath and Black Hmong / Giay homestays overlooking Muong Hoa stream',
                'Ascending Mount Fansipan (3,143m) via the 3-wire cable car (800,000–850,000 VND round-trip)',
            ],
            'url'             => '/destinations/sapa-travel-guide/',
            'season_url'      => '/plan/best-time-to-visit-vietnam/#month-sep',
            'cta_label'       => 'View Sa Pa Travel Guide',
        ],
        [
            'id'              => 'coastal-island',
            'title'           => '4-7 Days Con Dao Coastal & Island Finish',
            'duration_label'  => '4-7 Days',
            'duration_bucket' => 'short',
            'style_labels'    => ['Beach', 'Island Finish'],
            'style_values'    => ['beach'],
            'gateway_label'   => 'Ho Chi Minh City',
            'gateway_value'   => 'hcmc',
            'route_summary'   => 'Ho Chi Minh City &rarr; Con Dao Island (or Phu Quoc)',
            'description'     => 'An unhurried southern island extension reached via a 45-minute flight from Ho Chi Minh City (SGN to VCS/PQC) or 2.5-hour high-speed catamaran (320,000–500,000 VND), featuring marine national parks and quiet bays.',
            'highlights'      => [
                'Secluded golden-sand coves at Dam Trau Beach and sunset views along Bai Nhat',
                'Con Dao National Park coral reef snorkeling and Bay Canh sea turtle nesting (May–Oct)',
                'Historic Con Son heritage sites and Tiger Cages (50,000 VND combined museum ticket)',
            ],
            'url'             => '/destinations/con-dao-travel-guide/',
            'season_url'      => '/plan/best-time-to-visit-vietnam/#month-dec',
            'cta_label'       => 'View Con Dao Island Guide',
        ],
    ];
}

/**
 * Renders the Interactive Itinerary Finder HTML markup.
 *
 * @return string
 */
function vg_render_itinerary_finder_html(): string
{
    $catalog = vg_get_itinerary_finder_catalog();
    $totalCount = count($catalog);

    ob_start();
    ?>
    <section class="vg-itinerary-finder" id="itinerary-finder" aria-label="<?php esc_attr_e('Interactive Vietnam Itinerary Finder', 'vietnamguide-premium'); ?>">
        <div class="vg-finder-intro">
            <span class="vg-finder-kicker"><?php esc_html_e('Interactive Route Selector', 'vietnamguide-premium'); ?></span>
            <div class="vg-finder-title" role="heading" aria-level="2"><?php esc_html_e('Find Your Perfect Vietnam Route', 'vietnamguide-premium'); ?></div>
            <p class="vg-finder-subtitle"><?php esc_html_e('Filter Vietnam itineraries by trip duration, travel style, and starting airport to match your travel rhythm.', 'vietnamguide-premium'); ?></p>
        </div>

        <noscript>
            <div class="vg-itinerary-noscript-card" style="background:#f8f9fa;border:1px solid #cbd5e1;border-radius:8px;padding:20px;margin-bottom:24px;">
                <p style="font-weight:700;margin-bottom:8px;color:#1a365d;">🗺️ 2026 Recommended Classic Vietnam Routes (No-JavaScript Reference):</p>
                <p style="font-size:0.9rem;margin-bottom:12px;color:#475569;">Interactive itinerary finder requires JavaScript. Core route benchmarks for first-timers:</p>
                <ul style="margin-bottom:0;padding-left:20px;font-size:0.9rem;line-height:1.6;">
                    <li><strong>7-Day Golden Highlights:</strong> Hanoi (2d) &rarr; Ha Long Bay Cruise (1d) &rarr; Hoi An Ancient Town (3d).</li>
                    <li><strong>10-Day Classic Route:</strong> Hanoi (2d) &rarr; Ha Long (1d) &rarr; Da Nang/Hoi An (3d) &rarr; HCMC &amp; Mekong Delta (3d).</li>
                    <li><strong>14-Day Complete Cross-Country:</strong> Hanoi (2d) &rarr; Ninh Binh (2d) &rarr; Ha Long (1d) &rarr; Hue (2d) &rarr; Hoi An (3d) &rarr; HCMC &amp; Mekong (3d).</li>
                    <li><strong>21-Day In-Depth Northern Loop &amp; Islands:</strong> Adds Ha Giang Loop (4d) and Phu Quoc island beaches (3d).</li>
                </ul>
            </div>
        </noscript>

        <div class="vg-finder-controls">
            <div class="vg-finder-group" role="group" aria-labelledby="vg-filter-duration-label">
                <span class="vg-finder-label" id="vg-filter-duration-label"><?php esc_html_e('Trip Length', 'vietnamguide-premium'); ?></span>
                <div class="vg-finder-pills">
                    <button type="button" class="vg-finder-pill is-active" data-group="duration" data-value="all" aria-pressed="true"><?php esc_html_e('All Lengths', 'vietnamguide-premium'); ?></button>
                    <button type="button" class="vg-finder-pill" data-group="duration" data-value="short" aria-pressed="false"><?php esc_html_e('1–5 Days', 'vietnamguide-premium'); ?></button>
                    <button type="button" class="vg-finder-pill" data-group="duration" data-value="7d" aria-pressed="false"><?php esc_html_e('7 Days', 'vietnamguide-premium'); ?></button>
                    <button type="button" class="vg-finder-pill" data-group="duration" data-value="10d" aria-pressed="false"><?php esc_html_e('10 Days', 'vietnamguide-premium'); ?></button>
                    <button type="button" class="vg-finder-pill" data-group="duration" data-value="14d" aria-pressed="false"><?php esc_html_e('14 Days', 'vietnamguide-premium'); ?></button>
                    <button type="button" class="vg-finder-pill" data-group="duration" data-value="21d" aria-pressed="false"><?php esc_html_e('21 Days', 'vietnamguide-premium'); ?></button>
                </div>
            </div>

            <div class="vg-finder-group" role="group" aria-labelledby="vg-filter-style-label">
                <span class="vg-finder-label" id="vg-filter-style-label"><?php esc_html_e('Travel Style', 'vietnamguide-premium'); ?></span>
                <div class="vg-finder-pills">
                    <button type="button" class="vg-finder-pill is-active" data-group="style" data-value="all" aria-pressed="true"><?php esc_html_e('All Styles', 'vietnamguide-premium'); ?></button>
                    <button type="button" class="vg-finder-pill" data-group="style" data-value="classic" aria-pressed="false"><?php esc_html_e('Classic & Highlights', 'vietnamguide-premium'); ?></button>
                    <button type="button" class="vg-finder-pill" data-group="style" data-value="slow" aria-pressed="false"><?php esc_html_e('Slow & Culture', 'vietnamguide-premium'); ?></button>
                    <button type="button" class="vg-finder-pill" data-group="style" data-value="adventure" aria-pressed="false"><?php esc_html_e('Adventure & Nature', 'vietnamguide-premium'); ?></button>
                    <button type="button" class="vg-finder-pill" data-group="style" data-value="beach" aria-pressed="false"><?php esc_html_e('Beach & Islands', 'vietnamguide-premium'); ?></button>
                </div>
            </div>

            <div class="vg-finder-group" role="group" aria-labelledby="vg-filter-gateway-label">
                <span class="vg-finder-label" id="vg-filter-gateway-label"><?php esc_html_e('Starting Airport', 'vietnamguide-premium'); ?></span>
                <div class="vg-finder-pills">
                    <button type="button" class="vg-finder-pill is-active" data-group="gateway" data-value="all" aria-pressed="true"><?php esc_html_e('Any Gateway', 'vietnamguide-premium'); ?></button>
                    <button type="button" class="vg-finder-pill" data-group="gateway" data-value="hanoi" aria-pressed="false"><?php esc_html_e('Hanoi (North)', 'vietnamguide-premium'); ?></button>
                    <button type="button" class="vg-finder-pill" data-group="gateway" data-value="hcmc" aria-pressed="false"><?php esc_html_e('HCMC (South)', 'vietnamguide-premium'); ?></button>
                </div>
            </div>
        </div>

        <div class="vg-finder-status-bar">
            <p class="vg-finder-count-text" aria-live="polite" aria-atomic="true">
                <?php
                /* translators: %d: number of itineraries matching */
                printf(
                    esc_html__('Showing %s curated itineraries', 'vietnamguide-premium'),
                    '<strong class="vg-finder-count">' . (int) $totalCount . '</strong>'
                );
                ?>
            </p>
            <button type="button" class="vg-finder-reset-btn" hidden aria-label="<?php esc_attr_e('Reset all itinerary filters', 'vietnamguide-premium'); ?>">
                <?php esc_html_e('Reset filters', 'vietnamguide-premium'); ?> &times;
            </button>
        </div>

        <div class="vg-finder-grid">
            <?php foreach ($catalog as $item) : ?>
                <article class="vg-finder-card"
                         data-duration="<?php echo esc_attr($item['duration_bucket']); ?>"
                         data-styles="<?php echo esc_attr(implode(',', $item['style_values'])); ?>"
                         data-gateway="<?php echo esc_attr($item['gateway_value']); ?>">
                    <div class="vg-finder-card-header">
                        <span class="vg-finder-duration"><?php echo esc_html($item['duration_label']); ?></span>
                        <div class="vg-finder-badges">
                            <?php foreach ($item['style_labels'] as $badge) : ?>
                                <span class="vg-finder-badge"><?php echo esc_html($badge); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="vg-finder-card-title" role="heading" aria-level="3">
                        <a href="<?php echo esc_url($item['url']); ?>">
                            <?php echo esc_html($item['title']); ?>
                        </a>
                    </div>
                    <div class="vg-finder-route">
                        <span class="vg-finder-route-icon" aria-hidden="true">&#9678;</span>
                        <span><?php echo wp_kses($item['route_summary'], ['rarr' => []]); ?></span>
                    </div>
                    <p class="vg-finder-card-desc"><?php echo esc_html($item['description']); ?></p>
                    <ul class="vg-finder-highlights">
                        <?php foreach ($item['highlights'] as $highlight) : ?>
                            <li><?php echo esc_html($highlight); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="vg-finder-card-footer">
                        <span class="vg-finder-gateway-note">
                            <span aria-hidden="true">&#9992;</span> <?php echo esc_html($item['gateway_label']); ?>
                        </span>
                        <div class="vg-finder-card-links">
                            <a href="<?php echo esc_url(home_url($item['season_url'] ?? '/plan/best-time-to-visit-vietnam/')); ?>" class="vg-finder-weather-link vg-synergy-bridge" title="<?php esc_attr_e('Check best month and weather for this route', 'vietnamguide-premium'); ?>">
                                <span aria-hidden="true">🌤️</span> <?php esc_html_e('Weather', 'vietnamguide-premium'); ?>
                            </a>
                            <a href="<?php echo esc_url($item['url']); ?>" class="vg-finder-cta">
                                <?php echo esc_html($item['cta_label']); ?> <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="vg-finder-empty" hidden>
            <div class="vg-finder-empty-inner">
                <span class="vg-finder-empty-icon" aria-hidden="true">&#128269;</span>
                <div class="vg-finder-empty-title" role="heading" aria-level="3"><?php esc_html_e('No itineraries match all selected filters', 'vietnamguide-premium'); ?></div>
                <p class="vg-finder-empty-desc"><?php esc_html_e('Try expanding your duration window or choosing "All Styles" to explore complementary routes.', 'vietnamguide-premium'); ?></p>
                <button type="button" class="vg-finder-empty-reset">
                    <?php esc_html_e('Reset All Filters', 'vietnamguide-premium'); ?>
                </button>
            </div>
        </div>

        <!-- 2025–2026 Verified Vietnam Railways (dsvn.vn) & Domestic Aviation Reference Ledger -->
        <details class="vg-finder-rail-air-ledger" id="vg-finder-rail-air-ledger" style="margin:24px 0;padding:18px 20px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:12px;">
            <summary style="cursor:pointer;font-weight:700;color:#0f172a;font-size:0.96rem;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <span>🚆✈️ <?php esc_html_e('2025–2026 Verified Intercity Rail (dsvn.vn) & Domestic Flight Logistics Reference', 'vietnamguide-premium'); ?></span>
                <span style="font-size:0.78rem;font-weight:600;color:#0e6f5c;background:#ecfdf5;padding:3px 10px;border-radius:999px;border:1px solid #a7f3d0;"><?php esc_html_e('Field-Audited Schedules & Tariffs', 'vietnamguide-premium'); ?></span>
            </summary>
            <div class="vg-finder-ledger-body" style="margin-top:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;font-size:0.86rem;line-height:1.55;color:#334155;">
                <div class="vg-finder-ledger-card" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:14px;">
                    <div role="heading" aria-level="3" style="font-weight:700;color:#0f172a;margin-bottom:6px;font-size:0.92rem;">🏔️ <?php esc_html_e('Central Heritage Train (HĐ1/HĐ2/HĐ3/HĐ4: Hue ↔ Da Nang)', 'vietnamguide-premium'); ?></div>
                    <p style="margin:0 0 8px;"><strong><?php esc_html_e('Official DSVN Schedule:', 'vietnamguide-premium'); ?></strong> <?php esc_html_e('From Hue: HĐ1 departs 07:45 (arrives Da Nang 10:35) & HĐ3 departs 14:25 (arrives 17:40). From Da Nang: HĐ2 departs 07:50 (arrives Hue 11:05) & HĐ4 departs 15:00 (arrives 17:45).', 'vietnamguide-premium'); ?></p>
                    <p style="margin:0;"><strong><?php esc_html_e('Fare & Stopover:', 'vietnamguide-premium'); ?></strong> <?php esc_html_e('Includes a 10-minute scenic stop at Lang Co Station (09:12 / 16:22) and a live Ca Hue music carriage over Hai Van Pass. Official dsvn.vn ticket: 180,000–207,000 VND ($7.20–$8.30 USD) Mon–Fri; 210,000–231,000 VND ($8.40–$9.25 USD) Sat–Sun.', 'vietnamguide-premium'); ?></p>
                </div>
                <div class="vg-finder-ledger-card" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:14px;">
                    <div role="heading" aria-level="3" style="font-weight:700;color:#0f172a;margin-bottom:6px;font-size:0.92rem;">🌙 <?php esc_html_e('Reunification Express Overnight (SE1/SE2 & SE3/SE4)', 'vietnamguide-premium'); ?></div>
                    <p style="margin:0 0 8px;"><strong><?php esc_html_e('Hanoi ↔ Hue / Da Nang:', 'vietnamguide-premium'); ?></strong> <?php esc_html_e('SE3 departs Hanoi 19:20 (arrives Hue 08:48, Da Nang 11:36); SE1 departs Hanoi 20:55; SE19 departs Hanoi 19:40. 4-berth AC soft sleeper (Khoang 4 điều hòa): 950,000–1,350,000 VND ($38–$54 USD); private VIP 2-berth/Lotus cabin: 1,650,000–2,450,000 VND ($65–$96 USD).', 'vietnamguide-premium'); ?></p>
                    <p style="margin:0;"><strong><?php esc_html_e('Full Hanoi ↔ Saigon (1,726 km):', 'vietnamguide-premium'); ?></strong> <?php esc_html_e('SE2 departs Saigon 20:35 / SE4 departs 19:25 (32–34 hours total). 4-berth AC soft sleeper: 2,300,000–2,800,000 VND ($92–$112 USD). Book directly at dsvn.vn.', 'vietnamguide-premium'); ?></p>
                </div>
                <div class="vg-finder-ledger-card" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:14px;">
                    <div role="heading" aria-level="3" style="font-weight:700;color:#0f172a;margin-bottom:6px;font-size:0.92rem;">🌾 <?php esc_html_e('Northern Highlands Sleeper (SP3/SP4: Hanoi ↔ Lao Cai / Sa Pa)', 'vietnamguide-premium'); ?></div>
                    <p style="margin:0 0 8px;"><strong><?php esc_html_e('Overnight Rail Schedule:', 'vietnamguide-premium'); ?></strong> <?php esc_html_e('SP3 departs Hanoi Station (Tran Quy Cap Gate B) at 22:00 → arrives Lao Cai Station at 05:55 (7h 55m). Return SP4 departs Lao Cai at 21:30 → arrives Hanoi at 05:25.', 'vietnamguide-premium'); ?></p>
                    <p style="margin:0;"><strong><?php esc_html_e('Cabin Fares & Mountain Transfer:', 'vietnamguide-premium'); ?></strong> <?php esc_html_e('DSVN 4-berth sleeper: 450,000–750,000 VND ($18–$30 USD); private wood-paneled tourist cabin (Chapa / Victoria / Sapaly): 1,450,000–1,950,000 VND ($58–$78 USD). From Lao Cai Station to Sa Pa (35 km, 50 mins): Public Bus #01 costs 50,000 VND ($2 USD) or shared minivan costs 350,000 VND ($14 USD).', 'vietnamguide-premium'); ?></p>
                </div>
                <div class="vg-finder-ledger-card" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:14px;">
                    <div role="heading" aria-level="3" style="font-weight:700;color:#0f172a;margin-bottom:6px;font-size:0.92rem;">✈️ <?php esc_html_e('Domestic Aviation Baggage & Check-in Rules (VN vs VJ)', 'vietnamguide-premium'); ?></div>
                    <p style="margin:0 0 8px;"><strong><?php esc_html_e('Vietnam Airlines (VN):', 'vietnamguide-premium'); ?></strong> <?php esc_html_e('Carry-on allows 10kg main bag + 2kg personal item (12kg total Economy / 18kg Business). Economy Classic & Flex include 1 × 23kg checked bag (<= 158 cm); Economy Lite promotional fares exclude free checked baggage on domestic routes.', 'vietnamguide-premium'); ?></p>
                    <p style="margin:0;"><strong><?php esc_html_e('Vietjet Air (VJ) & Check-in Cutoff:', 'vietnamguide-premium'); ?></strong> <?php esc_html_e('Strict 7kg total carry-on limit; pre-book 15–30kg checked baggage online >= 3 hours before departure (160,000–350,000 VND) to avoid 450,000–700,000 VND gate penalties. Domestic check-in counters close strictly 40–50 minutes before scheduled departure; lithium power banks (<= 100Wh) must stay in carry-on.', 'vietnamguide-premium'); ?></p>
                </div>
            </div>
        </details>

        <!-- Viral Social & Deep-Link Sharing Bar -->
        <div class="vg-tool-share-bar">
            <span class="vg-share-label"><?php esc_html_e('Share Route:', 'vietnamguide-premium'); ?></span>
            <button type="button" class="vg-btn-share" id="vg-finder-share-link">
                <span>🔗 <?php esc_html_e('Copy Route Link', 'vietnamguide-premium'); ?></span>
            </button>
            <a href="#" class="vg-btn-share vg-btn-share--wa" id="vg-finder-share-wa" target="_blank" rel="noopener noreferrer">
                <span>💬 WhatsApp</span>
            </a>
            <a href="#" class="vg-btn-share vg-btn-share--tg" id="vg-finder-share-tg" target="_blank" rel="noopener noreferrer">
                <span>✈️ Telegram</span>
            </a>
            <button type="button" class="vg-btn-share vg-btn-share--qr" id="vg-finder-share-qr" aria-label="<?php esc_attr_e('Show QR code for mobile', 'vietnamguide-premium'); ?>">
                <span>📱 <?php esc_html_e('QR to Phone', 'vietnamguide-premium'); ?></span>
            </button>
        </div>

        <div class="vg-finder-toolkit vg-tool-synergy-bar">
            <div class="vg-finder-tk-title"><?php esc_html_e('Connected Travel Planning Toolkits', 'vietnamguide-premium'); ?></div>
            <div class="vg-finder-tk-grid">
                <a href="<?php echo esc_url(home_url('/costs/vietnam-travel-cost/')); ?>" class="vg-finder-tk-card vg-synergy-bridge">
                    <span class="vg-finder-tk-badge">💰 <?php esc_html_e('Budget', 'vietnamguide-premium'); ?></span>
                    <strong><?php esc_html_e('Trip Cost Calculator', 'vietnamguide-premium'); ?></strong>
                    <span><?php esc_html_e('Estimate 3–30 day spending by comfort tier &rarr;', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/vietnam-evisa/')); ?>" class="vg-finder-tk-card vg-synergy-bridge">
                    <span class="vg-finder-tk-badge">🛂 <?php esc_html_e('Visa', 'vietnamguide-premium'); ?></span>
                    <strong><?php esc_html_e('Visa & E-Visa Checker', 'vietnamguide-premium'); ?></strong>
                    <span><?php esc_html_e('Verify 45-day exemption (25 countries) vs $25/$50 90-day E-Visa across 42 checkpoints &rarr;', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/vietnam-airport-arrival-checklist/')); ?>" class="vg-finder-tk-card vg-synergy-bridge">
                    <span class="vg-finder-tk-badge">✈️ <?php esc_html_e('Transit', 'vietnamguide-premium'); ?></span>
                    <strong><?php esc_html_e('Airport Transit & Arrival Guide', 'vietnamguide-premium'); ?></strong>
                    <span><?php esc_html_e('Grab pick-up zones, bus routes, and taxi fares across 9 gateway airports (HAN, SGN, DAD, CXR, PQC, HUI, DLI, VDH, HPH) &rarr;', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/best-time-to-visit-vietnam/')); ?>" class="vg-finder-tk-card vg-synergy-bridge">
                    <span class="vg-finder-tk-badge">☀️ <?php esc_html_e('Climate', 'vietnamguide-premium'); ?></span>
                    <strong><?php esc_html_e('Seasonality & Weather Matrix', 'vietnamguide-premium'); ?></strong>
                    <span><?php esc_html_e('12-month regional weather & climate patterns &rarr;', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/vietnam-first-trip-planning-checklist/')); ?>" class="vg-finder-tk-card vg-synergy-bridge">
                    <span class="vg-finder-tk-badge">🎒 <?php esc_html_e('Packing', 'vietnamguide-premium'); ?></span>
                    <strong><?php esc_html_e('Route Packing Checklist', 'vietnamguide-premium'); ?></strong>
                    <span><?php esc_html_e('Interactive 24-item packing checklist with browser progress saving &rarr;', 'vietnamguide-premium'); ?></span>
                </a>
            </div>
        </div>

        <div id="vg-finder-aria-status" class="screen-reader-text" role="status" aria-live="polite" aria-atomic="true"></div>
    </section>
    <script>
    (function () {
        'use strict';

        // Resilient safe storage helpers with localStorage fallback
        function getSafeStorage(key) {
            try {
                return sessionStorage.getItem(key) || localStorage.getItem(key);
            } catch(e) {
                try { return localStorage.getItem(key); } catch(e2) { return null; }
            }
        }

        function setSafeStorage(key, val) {
            try { sessionStorage.setItem(key, val); } catch(e) {}
            try { localStorage.setItem(key, val); } catch(e) {}
        }

        // Bidirectional URL query state synchronization
        function syncUrlParams(params) {
            if (!window.history || !window.history.replaceState) return;
            try {
                var url = new URL(window.location.href);
                Object.keys(params).forEach(function(k) {
                    if (params[k] !== undefined && params[k] !== null && params[k] !== '' && params[k] !== 'all') {
                        url.searchParams.set(k, params[k]);
                    } else {
                        url.searchParams.delete(k);
                    }
                });
                window.history.replaceState(null, '', url.toString());
            } catch(e) {}
        }

        function initItineraryFinder() {
            var root = document.getElementById('itinerary-finder');
            if (!root || root.hasAttribute('data-vg-initialized')) return;
            root.setAttribute('data-vg-initialized', 'true');

            var cards = root.querySelectorAll('.vg-finder-card');
            var countEl = root.querySelector('.vg-finder-count');
            var resetBtn = root.querySelector('.vg-finder-reset-btn');
            var emptyState = root.querySelector('.vg-finder-empty');
            var emptyResetBtn = root.querySelector('.vg-finder-empty-reset');
            var pills = root.querySelectorAll('.vg-finder-pill');
            var ariaStatus = document.getElementById('vg-finder-aria-status');

            var filters = {
                duration: 'all',
                style: 'all',
                gateway: 'all'
            };

            // Restore from URL query params first, then safe storage
            try {
                var urlParams = new URLSearchParams(window.location.search);
                var qDuration = urlParams.get('duration');
                if (qDuration) {
                    var validDurations = ['short', '7d', '10d', '14d', '21d'];
                    if (validDurations.indexOf(qDuration) !== -1) {
                        filters.duration = qDuration;
                    } else {
                        var numD = parseInt(qDuration, 10);
                        if (!isNaN(numD)) {
                            if (numD <= 5) filters.duration = 'short';
                            else if (numD <= 8) filters.duration = '7d';
                            else if (numD <= 12) filters.duration = '10d';
                            else if (numD <= 18) filters.duration = '14d';
                            else filters.duration = '21d';
                        }
                    }
                } else {
                    var savedDuration = parseInt(getSafeStorage('vg_user_duration'), 10);
                    if (!isNaN(savedDuration) && savedDuration) {
                        if (savedDuration <= 5) filters.duration = 'short';
                        else if (savedDuration <= 8) filters.duration = '7d';
                        else if (savedDuration <= 12) filters.duration = '10d';
                        else if (savedDuration <= 18) filters.duration = '14d';
                        else filters.duration = '21d';
                    }
                }

                var qStyle = urlParams.get('style');
                var validStyles = ['classic', 'adventure', 'culture', 'nature', 'food'];
                if (qStyle && validStyles.indexOf(qStyle) !== -1) {
                    filters.style = qStyle;
                }

                var qGateway = urlParams.get('gateway');
                if (qGateway === 'hanoi' || qGateway === 'hcmc') {
                    filters.gateway = qGateway;
                } else {
                    var savedAirport = getSafeStorage('vg_user_airport');
                    if (savedAirport === 'HAN') filters.gateway = 'hanoi';
                    else if (savedAirport === 'SGN') filters.gateway = 'hcmc';
                }
            } catch(e) {}

            function syncPillUI() {
                for (var i = 0; i < pills.length; i++) {
                    var pill = pills[i];
                    var group = pill.getAttribute('data-group');
                    var val = pill.getAttribute('data-value');
                    var active = (filters[group] === val);
                    pill.classList.toggle('is-active', active);
                    pill.setAttribute('aria-pressed', active ? 'true' : 'false');
                }
            }

            function update() {
                var visibleCount = 0;
                var hasActiveFilters = filters.duration !== 'all' || filters.style !== 'all' || filters.gateway !== 'all';

                for (var i = 0; i < cards.length; i++) {
                    var card = cards[i];
                    var cardDuration = card.getAttribute('data-duration');
                    var cardStyles = (card.getAttribute('data-styles') || '').split(',');
                    var cardGateway = card.getAttribute('data-gateway');

                    var durationMatch = (filters.duration === 'all') || (cardDuration === filters.duration);
                    var styleMatch = (filters.style === 'all') || (cardStyles.indexOf(filters.style) !== -1);
                    var gatewayMatch = (filters.gateway === 'all') || (cardGateway === 'both') || (cardGateway === filters.gateway);

                    if (durationMatch && styleMatch && gatewayMatch) {
                        card.hidden = false;
                        visibleCount++;
                    } else {
                        card.hidden = true;
                    }
                }

                if (countEl) {
                    countEl.textContent = visibleCount.toString();
                }

                if (resetBtn) {
                    resetBtn.hidden = !hasActiveFilters;
                }

                if (emptyState) {
                    emptyState.hidden = (visibleCount > 0);
                }

                if (ariaStatus) {
                    ariaStatus.textContent = 'Showing ' + visibleCount + ' curated itineraries matching selected filters.';
                }

                // Sync URL query parameters
                syncUrlParams({ duration: filters.duration, style: filters.style, gateway: filters.gateway });

                // Update WhatsApp and Telegram share links dynamically
                var shareUrl = window.location.href;
                var shareMsg = 'Vietnam Curated Travel Itinerary (' + (visibleCount > 0 ? visibleCount + ' route matches' : 'Curated routes') + '): ' + shareUrl;
                var waBtn = document.getElementById('vg-finder-share-wa');
                var tgBtn = document.getElementById('vg-finder-share-tg');
                if (waBtn) waBtn.href = 'https://api.whatsapp.com/send?text=' + encodeURIComponent(shareMsg);
                if (tgBtn) tgBtn.href = 'https://t.me/share/url?url=' + encodeURIComponent(shareUrl) + '&text=' + encodeURIComponent(shareMsg);

                // Telemetry dispatch
                if (typeof window.vgTrack === 'function') {
                    window.vgTrack('vg_itinerary_filter', {
                        duration: filters.duration,
                        style: filters.style,
                        gateway: filters.gateway,
                        results: visibleCount
                    });
                }
            }

            function resetAll() {
                filters.duration = 'all';
                filters.style = 'all';
                filters.gateway = 'all';
                syncUrlParams({ duration: 'all', style: 'all', gateway: 'all' });
                syncPillUI();
                update();
            }

            for (var i = 0; i < pills.length; i++) {
                (function (pill, idx) {
                    pill.addEventListener('click', function () {
                        var group = pill.getAttribute('data-group');
                        var value = pill.getAttribute('data-value');
                        if (!group || !value) return;

                        filters[group] = value;

                        // Save to safe storage for cross-tool continuity
                        try {
                            if (group === 'duration') {
                                var dMap = { '7d': 7, '10d': 10, '14d': 14, '21d': 21, 'short': 3 };
                                if (dMap[value]) setSafeStorage('vg_user_duration', dMap[value]);
                            } else if (group === 'gateway') {
                                if (value === 'hanoi') setSafeStorage('vg_user_airport', 'HAN');
                                else if (value === 'hcmc') setSafeStorage('vg_user_airport', 'SGN');
                            }
                        } catch(err) {}

                        var groupPills = root.querySelectorAll('.vg-finder-pill[data-group="' + group + '"]');
                        for (var j = 0; j < groupPills.length; j++) {
                            var p = groupPills[j];
                            var active = (p === pill);
                            p.classList.toggle('is-active', active);
                            p.setAttribute('aria-pressed', active ? 'true' : 'false');
                        }
                        update();
                    });

                    pill.addEventListener('keydown', function (e) {
                        var group = pill.getAttribute('data-group');
                        var groupPills = root.querySelectorAll('.vg-finder-pill[data-group="' + group + '"]');
                        var currentIdxInGroup = Array.prototype.indexOf.call(groupPills, pill);
                        var targetIdx = -1;

                        if (e.key === 'ArrowRight') targetIdx = (currentIdxInGroup + 1) % groupPills.length;
                        else if (e.key === 'ArrowLeft') targetIdx = (currentIdxInGroup - 1 + groupPills.length) % groupPills.length;

                        if (targetIdx !== -1) {
                            e.preventDefault();
                            groupPills[targetIdx].focus();
                            groupPills[targetIdx].click();
                        }
                    });
                })(pills[i], i);
            }

            if (resetBtn) {
                resetBtn.addEventListener('click', resetAll);
            }
            if (emptyResetBtn) {
                emptyResetBtn.addEventListener('click', resetAll);
            }

            function showToast(msg) {
                var toast = document.getElementById('vg-global-toast');
                if (!toast) {
                    toast = document.createElement('div');
                    toast.id = 'vg-global-toast';
                    toast.className = 'vg-toast';
                    document.body.appendChild(toast);
                }
                toast.textContent = msg;
                toast.classList.add('is-active');
                setTimeout(function () { toast.classList.remove('is-active'); }, 3000);
            }

            var shareLinkBtn = document.getElementById('vg-finder-share-link');
            if (shareLinkBtn) {
                shareLinkBtn.addEventListener('click', function () {
                    var url = window.location.href;
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(url).then(function () {
                            showToast('✓ Link copied to clipboard - Share with your travel companion!');
                            if (typeof window.vgTrack === 'function') {
                                window.vgTrack('vg_share_plan', { tool: 'itinerary_finder', channel: 'copy_link' });
                            }
                        }).catch(function () {
                            fallbackCopy(url);
                        });
                    } else {
                        fallbackCopy(url);
                    }
                });
            }

            var waBtnEl = document.getElementById('vg-finder-share-wa');
            if (waBtnEl) {
                waBtnEl.addEventListener('click', function () {
                    if (typeof window.vgTrack === 'function') {
                        window.vgTrack('vg_share_plan', { tool: 'itinerary_finder', channel: 'whatsapp' });
                    }
                });
            }

            var tgBtnEl = document.getElementById('vg-finder-share-tg');
            if (tgBtnEl) {
                tgBtnEl.addEventListener('click', function () {
                    if (typeof window.vgTrack === 'function') {
                        window.vgTrack('vg_share_plan', { tool: 'itinerary_finder', channel: 'telegram' });
                    }
                });
            }

            var qrBtnEl = document.getElementById('vg-finder-share-qr');
            if (qrBtnEl) {
                qrBtnEl.addEventListener('click', function () {
                    if (typeof window.vgOpenQrModal === 'function') {
                        window.vgOpenQrModal(window.location.href, '<?php esc_attr_e('Vietnam Curated Route Itinerary', 'vietnamguide-premium'); ?>');
                    }
                    if (typeof window.vgTrack === 'function') {
                        window.vgTrack('vg_share_plan', { tool: 'itinerary_finder', channel: 'qr_code' });
                    }
                });
            }

            function fallbackCopy(text) {
                var textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    showToast('✓ Link copied to clipboard!');
                } catch (err) {}
                document.body.removeChild(textArea);
            }

            syncPillUI();
            update();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initItineraryFinder);
        } else {
            initItineraryFinder();
        }
    })();
    </script>
    <?php
    return (string) ob_get_clean();
}

/**
 * Shortcode handler for [vg_itinerary_finder].
 *
 * @return string
 */
function vg_itinerary_finder_shortcode(): string
{
    return vg_render_itinerary_finder_html();
}
add_shortcode('vg_itinerary_finder', 'vg_itinerary_finder_shortcode');

/**
 * Injects the Itinerary Finder component on the /itineraries/ hub page right after the lede hero.
 *
 * @param string $content Post content.
 * @return string
 */
function vg_inject_itinerary_finder_on_hub(string $content): string
{
    if (is_admin()) {
        return $content;
    }

    // Never inject into hero block
    if (strpos($content, 'vg-guide-hero') !== false) {
        return $content;
    }

    static $injectedPosts = [];
    $postId = get_the_ID();
    if ($postId && isset($injectedPosts[$postId])) {
        return $content;
    }

    $currentSlug = (string) get_post_field('post_name');
    $itinerarySlugs = [
        'itineraries',
        'best-vietnam-routes-first-time-visitors',
        'best-vietnam-cities-for-first-time-visitors',
        '5-days-in-vietnam',
        '7-days-in-vietnam',
        '10-days-in-vietnam',
        '14-days-in-vietnam',
        '21-days-in-vietnam',
        '3-weeks-in-vietnam-route',
        'north-to-south-vietnam-itinerary',
        'south-to-north-vietnam-itinerary',
        'northern-vietnam-itinerary',
        'central-vietnam-itinerary',
        'southern-vietnam-itinerary',
        'vietnam-train-travel-guide',
        'reunification-express-train-guide',
        'hanoi-to-hue-night-train-guide',
        'hue-to-da-nang-train-guide',
    ];

    $isTargetPage = in_array($currentSlug, $itinerarySlugs, true)
        || is_page('itineraries')
        || (is_singular('page') && get_post_field('post_name') === 'itineraries')
        || is_page('best-vietnam-routes-first-time-visitors')
        || (is_singular('page') && get_post_field('post_name') === 'best-vietnam-routes-first-time-visitors');

    if (! $isTargetPage) {
        return $content;
    }

    if (has_shortcode($content, 'vg_itinerary_finder') || strpos($content, 'vg-itinerary-finder') !== false) {
        return $content;
    }

    if ($postId) {
        $injectedPosts[$postId] = true;
    }

    $finderHtml = vg_render_itinerary_finder_html();
    $heroClose = '<!-- /wp:group -->';
    $pos = strpos($content, $heroClose);

    if ($pos !== false) {
        $insertAt = $pos + strlen($heroClose);
        return substr($content, 0, $insertAt) . "\n\n" . $finderHtml . "\n\n" . substr($content, $insertAt);
    }

    return $finderHtml . "\n\n" . $content;
}
add_filter('the_content', 'vg_inject_itinerary_finder_on_hub', 20);

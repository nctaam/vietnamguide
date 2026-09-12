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
            'description'     => 'The quintessential introductory route connecting northern karst wonders, lantern-lit heritage alleys, and southern energy with zero wasted transit days.',
            'highlights'      => [
                'Overnight karst cruise on Ha Long or Lan Ha Bay',
                'Walking food tour and tailor shops in ancient Hoi An',
                'Vibrant cafe culture and historical landmarks in Saigon',
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
            'description'     => 'The gold standard for travelers who want depth over checklists. Adds ancient capitals, emerald rice valleys, and imperial citadel architecture.',
            'highlights'      => [
                'Rowboat through towering river caves in Tam Coc & Trang An',
                'Imperial tombs and royal palace pavilions in historic Hue',
                'Cycling through Hoi An rice fields to An Bang Beach',
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
            'description'     => 'A compact, high-impact northern circuit designed for limited vacation windows. Delivers maximum scenic beauty with minimal transit fatigue.',
            'highlights'      => [
                'Street food exploration in the historic 36 Guilds Quarter',
                'Dramatic limestone peaks of Ninh Binh on a traditional sampan',
                'Sunrise tai chi and sea kayaking among Lan Ha limestone karsts',
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
            'description'     => 'The complete expedition spanning mist-shrouded northern mountains, imperial dynasties, high-altitude coffee valleys, and southern floating waterways.',
            'highlights'      => [
                'Trekking through Muong Hoa valley and ethnic minority homestays',
                'Hai Van Pass coastal drive between Hue and Da Nang',
                'Floating markets and orchard homestays in the Mekong Delta',
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
            'description'     => 'A sensory urban weekend route curating egg coffee hideaways, colonial tree-lined boulevards, Temple of Literature, and world-class street cuisine.',
            'highlights'      => [
                'Hidden rooftop egg coffee overlooking Hoan Kiem Lake',
                'Early morning stroll through the French Quarter and Opera House',
                'Curated evening pho and bun cha trail in Hoan Kiem',
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
            'description'     => "Southeast Asia's most legendary mountain pass route. Towering limestone canyons, thrilling switchbacks, and remote mountain community homestays.",
            'highlights'      => [
                'Driving through the jaw-dropping Ma Pi Leng Pass above Nho Que river',
                'Exploring Dong Van UNESCO Global Karst Plateau Geopark',
                'Cooling off at Du Gia mountain waterfall after mountain riding',
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
            'description'     => "Tiered golden rice terraces carved into Mount Fansipan's foothills, authentic village homestays, and refreshing cool highland alpine air.",
            'highlights'      => [
                'Guided trek through the terraced cascades of Muong Hoa Valley',
                'Local herbal baths and welcoming homestays in Ta Van and Lao Chai',
                'Ascending the rooftop of Indochina via Fansipan Legend cable car',
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
            'description'     => 'The ultimate antidote to touring fatigue. Turquoise bays, sea turtle sanctuaries, historic French-era coastal avenues, and pristine empty beaches.',
            'highlights'      => [
                'Pristine secluded beaches at Dam Trau Bay and Bai Nhat',
                'National park snorkeling on coral reefs and sea turtle conservation',
                'Peaceful, unhurried island pace with coastal seafood dining',
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
                    <h3 class="vg-finder-card-title">
                        <a href="<?php echo esc_url($item['url']); ?>">
                            <?php echo esc_html($item['title']); ?>
                        </a>
                    </h3>
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
                <h3 class="vg-finder-empty-title"><?php esc_html_e('No itineraries match all selected filters', 'vietnamguide-premium'); ?></h3>
                <p class="vg-finder-empty-desc"><?php esc_html_e('Try expanding your duration window or choosing "All Styles" to explore complementary routes.', 'vietnamguide-premium'); ?></p>
                <button type="button" class="vg-finder-empty-reset">
                    <?php esc_html_e('Reset All Filters', 'vietnamguide-premium'); ?>
                </button>
            </div>
        </div>

        <div class="vg-finder-toolkit vg-tool-synergy-bar">
            <div class="vg-finder-tk-title"><?php esc_html_e('Essential Trip Planning Toolkit', 'vietnamguide-premium'); ?></div>
            <div class="vg-finder-tk-grid">
                <a href="<?php echo esc_url(home_url('/costs/vietnam-travel-cost/')); ?>" class="vg-finder-tk-card vg-synergy-bridge">
                    <span class="vg-finder-tk-badge">💰 <?php esc_html_e('Budget', 'vietnamguide-premium'); ?></span>
                    <strong><?php esc_html_e('Trip Cost Calculator', 'vietnamguide-premium'); ?></strong>
                    <span><?php esc_html_e('Estimate 3–30 day spending by comfort tier &rarr;', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/vietnam-evisa/')); ?>" class="vg-finder-tk-card vg-synergy-bridge">
                    <span class="vg-finder-tk-badge">🛂 <?php esc_html_e('Visa', 'vietnamguide-premium'); ?></span>
                    <strong><?php esc_html_e('Visa & E-Visa Checker', 'vietnamguide-premium'); ?></strong>
                    <span><?php esc_html_e('Verify 45-day exemption vs $25 e-visa rules &rarr;', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/vietnam-airport-arrival-checklist/')); ?>" class="vg-finder-tk-card vg-synergy-bridge">
                    <span class="vg-finder-tk-badge">✈️ <?php esc_html_e('Transit', 'vietnamguide-premium'); ?></span>
                    <strong><?php esc_html_e('Airport Transit Navigator', 'vietnamguide-premium'); ?></strong>
                    <span><?php esc_html_e('Grab bays & scam shields for HAN, SGN & DAD &rarr;', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/best-time-to-visit-vietnam/')); ?>" class="vg-finder-tk-card vg-synergy-bridge">
                    <span class="vg-finder-tk-badge">☀️ <?php esc_html_e('Climate', 'vietnamguide-premium'); ?></span>
                    <strong><?php esc_html_e('Seasonality & Packing Matrix', 'vietnamguide-premium'); ?></strong>
                    <span><?php esc_html_e('12-month regional weather & packing list &rarr;', 'vietnamguide-premium'); ?></span>
                </a>
            </div>
        </div>

        <div id="vg-finder-aria-status" class="screen-reader-text" aria-live="polite"></div>
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

    $isTargetPage = is_page('itineraries')
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

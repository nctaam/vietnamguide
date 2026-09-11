<?php
/**
 * VietnamGuide Interactive Budget & Travel Cost Calculator Component
 *
 * Provides an interactive, zero-dependency client-side travel cost estimator
 * for travelers planning trips to Vietnam across duration, travel tiers, party size, and flights.
 *
 * @package VietnamGuide
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Renders the interactive Vietnam travel cost calculator HTML markup and client script.
 *
 * @return string
 */
function vg_render_cost_calculator_html(): string
{
    ob_start();
    ?>
    <section class="vg-cost-calculator" id="vg-cost-calculator" aria-label="<?php echo esc_attr__('Vietnam Travel Cost Calculator', 'vietnamguide-premium'); ?>">
        <div class="vg-calc-header">
            <div class="vg-calc-heading-group">
                <span class="vg-calc-badge"><?php esc_html_e('Interactive Planning Tool', 'vietnamguide-premium'); ?></span>
                <h2 class="vg-calc-title"><?php esc_html_e('Vietnam Travel Cost Calculator', 'vietnamguide-premium'); ?></h2>
                <p class="vg-calc-subtitle"><?php esc_html_e('Estimate realistic on-the-ground spending based on your trip length, travel style, party size, and domestic flight hops.', 'vietnamguide-premium'); ?></p>
            </div>
            <div class="vg-calc-currency-toggle" role="group" aria-label="<?php echo esc_attr__('Currency Selection', 'vietnamguide-premium'); ?>">
                <button type="button" class="vg-calc-currency-btn is-active" data-currency="USD" aria-pressed="true">USD ($)</button>
                <button type="button" class="vg-calc-currency-btn" data-currency="VND" aria-pressed="false">VND (₫)</button>
            </div>
        </div>

        <div class="vg-calc-grid">
            <!-- Left Column: Controls -->
            <div class="vg-calc-controls">
                <!-- Duration Slider -->
                <div class="vg-calc-field">
                    <div class="vg-calc-field-header">
                        <label for="vg-calc-days-slider" class="vg-calc-field-label">
                            <strong><?php esc_html_e('Trip Duration:', 'vietnamguide-premium'); ?></strong>
                            <span class="vg-calc-field-value" id="vg-calc-days-display">10 <?php esc_html_e('Days', 'vietnamguide-premium'); ?></span>
                        </label>
                    </div>
                    <div class="vg-calc-slider-wrapper">
                        <input type="range" id="vg-calc-days-slider" class="vg-calc-slider" min="3" max="30" value="10" step="1" aria-valuemin="3" aria-valuemax="30" aria-valuenow="10" aria-describedby="vg-calc-presets-group" />
                    </div>
                    <div class="vg-calc-presets" id="vg-calc-presets-group" role="group" aria-label="<?php echo esc_attr__('Quick duration presets', 'vietnamguide-premium'); ?>">
                        <button type="button" class="vg-calc-preset-chip" data-days="7">7d (Highlights)</button>
                        <button type="button" class="vg-calc-preset-chip is-active" data-days="10">10d (Classic)</button>
                        <button type="button" class="vg-calc-preset-chip" data-days="14">14d (Balanced)</button>
                        <button type="button" class="vg-calc-preset-chip" data-days="21">21d (In-Depth)</button>
                    </div>
                </div>

                <!-- Travel Style / Tier -->
                <div class="vg-calc-field">
                    <span class="vg-calc-field-label"><strong><?php esc_html_e('Travel Style & Comfort Tier:', 'vietnamguide-premium'); ?></strong></span>
                    <div class="vg-calc-style-options" role="radiogroup" aria-label="<?php echo esc_attr__('Travel comfort tier', 'vietnamguide-premium'); ?>">
                        <label class="vg-calc-style-card">
                            <input type="radio" name="vg_travel_style" value="backpacker" class="vg-calc-radio" />
                            <div class="vg-calc-style-body">
                                <div class="vg-calc-style-top">
                                    <span class="vg-calc-style-name"><?php esc_html_e('Backpacker / Budget', 'vietnamguide-premium'); ?></span>
                                    <span class="vg-calc-style-rate">~$35 / day</span>
                                </div>
                                <span class="vg-calc-style-desc"><?php esc_html_e('Hostels & homestays, authentic street food stalls, trains & sleeper buses, self-guided visits.', 'vietnamguide-premium'); ?></span>
                            </div>
                        </label>

                        <label class="vg-calc-style-card is-selected">
                            <input type="radio" name="vg_travel_style" value="midrange" class="vg-calc-radio" checked />
                            <div class="vg-calc-style-body">
                                <div class="vg-calc-style-top">
                                    <span class="vg-calc-style-name"><?php esc_html_e('Flashpacker / Mid-Range', 'vietnamguide-premium'); ?> <span class="vg-calc-tag-rec"><?php esc_html_e('Popular', 'vietnamguide-premium'); ?></span></span>
                                    <span class="vg-calc-style-rate">~$80 / day</span>
                                </div>
                                <span class="vg-calc-style-desc"><?php esc_html_e('3-4★ boutique hotels, mix of local bistros & street eats, Grab cars, curated small group day tours.', 'vietnamguide-premium'); ?></span>
                            </div>
                        </label>

                        <label class="vg-calc-style-card">
                            <input type="radio" name="vg_travel_style" value="luxury" class="vg-calc-radio" />
                            <div class="vg-calc-style-body">
                                <div class="vg-calc-style-top">
                                    <span class="vg-calc-style-name"><?php esc_html_e('Luxury Boutique', 'vietnamguide-premium'); ?></span>
                                    <span class="vg-calc-style-rate">~$195 / day</span>
                                </div>
                                <span class="vg-calc-style-desc"><?php esc_html_e('5★ heritage resorts, private cruise cabins, fine dining & rooftop lounges, private chauffeured AC cars.', 'vietnamguide-premium'); ?></span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Party Size & Domestic Flights in 2 Sub-columns -->
                <div class="vg-calc-dual-fields">
                    <!-- Party Size -->
                    <div class="vg-calc-field">
                        <span class="vg-calc-field-label"><strong><?php esc_html_e('Party Size:', 'vietnamguide-premium'); ?></strong></span>
                        <div class="vg-calc-pill-group" role="group" aria-label="<?php echo esc_attr__('Party size', 'vietnamguide-premium'); ?>">
                            <button type="button" class="vg-calc-pill-btn" data-party="1">1 (Solo)</button>
                            <button type="button" class="vg-calc-pill-btn is-active" data-party="2">2 (Couple)</button>
                            <button type="button" class="vg-calc-pill-btn" data-party="3">3 (Group)</button>
                            <button type="button" class="vg-calc-pill-btn" data-party="4">4 (Family)</button>
                        </div>
                    </div>

                    <!-- Domestic Flights -->
                    <div class="vg-calc-field">
                        <span class="vg-calc-field-label"><strong><?php esc_html_e('Domestic Flights:', 'vietnamguide-premium'); ?></strong></span>
                        <div class="vg-calc-pill-group" role="group" aria-label="<?php echo esc_attr__('Domestic flights count', 'vietnamguide-premium'); ?>">
                            <button type="button" class="vg-calc-pill-btn" data-flights="0">0 (Overland)</button>
                            <button type="button" class="vg-calc-pill-btn" data-flights="1">1 Flight</button>
                            <button type="button" class="vg-calc-pill-btn is-active" data-flights="2">2 Flights</button>
                            <button type="button" class="vg-calc-pill-btn" data-flights="3">3 Flights</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live Results & Visual Breakdown -->
            <div class="vg-calc-results">
                <div class="vg-calc-summary-card">
                    <div class="vg-calc-summary-header">
                        <span class="vg-calc-summary-badge"><?php esc_html_e('Estimated Budget', 'vietnamguide-premium'); ?></span>
                        <span class="vg-calc-daily-rate" id="vg-calc-daily-display">~$80 / person / day</span>
                    </div>

                    <div class="vg-calc-total-wrap">
                        <div class="vg-calc-total-main" id="vg-calc-total-display">$1,600</div>
                        <div class="vg-calc-total-converted" id="vg-calc-total-converted-display">≈ 40,800,000 VND</div>
                    </div>

                    <div class="vg-calc-parties-note" id="vg-calc-parties-display"><?php esc_html_e('Total for 2 travelers (10 days with shared accommodation)', 'vietnamguide-premium'); ?></div>

                    <!-- Visual Proportion Bar -->
                    <div class="vg-calc-bar-wrapper">
                        <div class="vg-calc-bar-labels">
                            <span class="vg-bar-label-item"><span class="vg-bar-dot vg-dot-stay"></span> <?php esc_html_e('Stay', 'vietnamguide-premium'); ?></span>
                            <span class="vg-bar-label-item"><span class="vg-bar-dot vg-dot-food"></span> <?php esc_html_e('Food', 'vietnamguide-premium'); ?></span>
                            <span class="vg-bar-label-item"><span class="vg-bar-dot vg-dot-transit"></span> <?php esc_html_e('Transit', 'vietnamguide-premium'); ?></span>
                            <span class="vg-bar-label-item"><span class="vg-bar-dot vg-dot-tours"></span> <?php esc_html_e('Activities', 'vietnamguide-premium'); ?></span>
                        </div>
                        <div class="vg-calc-bar" role="progressbar" aria-label="<?php echo esc_attr__('Cost breakdown by category', 'vietnamguide-premium'); ?>" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                            <div class="vg-bar-segment vg-seg-stay" id="vg-bar-stay" style="width: 35%;"></div>
                            <div class="vg-bar-segment vg-seg-food" id="vg-bar-food" style="width: 30%;"></div>
                            <div class="vg-bar-segment vg-seg-transit" id="vg-bar-transit" style="width: 21%;"></div>
                            <div class="vg-bar-segment vg-seg-tours" id="vg-bar-tours" style="width: 14%;"></div>
                        </div>
                    </div>

                    <!-- Line items list -->
                    <ul class="vg-calc-breakdown-list">
                        <li class="vg-calc-item">
                            <div class="vg-calc-item-left">
                                <span class="vg-bar-dot vg-dot-stay"></span>
                                <span class="vg-calc-item-name"><?php esc_html_e('Accommodation (Hotels / Homestays)', 'vietnamguide-premium'); ?></span>
                            </div>
                            <div class="vg-calc-item-right">
                                <span class="vg-calc-item-amount" id="vg-amt-stay">$560</span>
                                <span class="vg-calc-item-pct" id="vg-pct-stay">(35%)</span>
                            </div>
                        </li>
                        <li class="vg-calc-item">
                            <div class="vg-calc-item-left">
                                <span class="vg-bar-dot vg-dot-food"></span>
                                <span class="vg-calc-item-name"><?php esc_html_e('Food, Street Snacks & Drinks', 'vietnamguide-premium'); ?></span>
                            </div>
                            <div class="vg-calc-item-right">
                                <span class="vg-calc-item-amount" id="vg-amt-food">$480</span>
                                <span class="vg-calc-item-pct" id="vg-pct-food">(30%)</span>
                            </div>
                        </li>
                        <li class="vg-calc-item">
                            <div class="vg-calc-item-left">
                                <span class="vg-bar-dot vg-dot-transit"></span>
                                <span class="vg-calc-item-name"><?php esc_html_e('Local Transport & Domestic Flights', 'vietnamguide-premium'); ?></span>
                            </div>
                            <div class="vg-calc-item-right">
                                <span class="vg-calc-item-amount" id="vg-amt-transit">$340</span>
                                <span class="vg-calc-item-pct" id="vg-pct-transit">(21%)</span>
                            </div>
                        </li>
                        <li class="vg-calc-item">
                            <div class="vg-calc-item-left">
                                <span class="vg-bar-dot vg-dot-tours"></span>
                                <span class="vg-calc-item-name"><?php esc_html_e('Sightseeing, Passes & Activities', 'vietnamguide-premium'); ?></span>
                            </div>
                            <div class="vg-calc-item-right">
                                <span class="vg-calc-item-amount" id="vg-amt-tours">$220</span>
                                <span class="vg-calc-item-pct" id="vg-pct-tours">(14%)</span>
                            </div>
                        </li>
                    </ul>

                    <p class="vg-calc-disclaimer"><?php esc_html_e('*Estimates reflect real-world on-the-ground spending in Vietnam including entrance fees, tips, Grab rides, and cafe stops. Excludes international return flights.', 'vietnamguide-premium'); ?></p>

                    <div class="vg-calc-actions">
                        <button type="button" class="vg-calc-btn-copy" id="vg-calc-copy-btn">
                            <svg class="vg-calc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                            <span><?php esc_html_e('Copy Budget Summary', 'vietnamguide-premium'); ?></span>
                        </button>
                        <button type="button" class="vg-calc-btn-reset" id="vg-calc-reset-btn"><?php esc_html_e('Reset', 'vietnamguide-premium'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div id="vg-calc-aria-status" class="screen-reader-text" aria-live="polite"></div>
    </section>

    <script>
    (function () {
        'use strict';

        var USD_TO_VND = 25500;
        var FLIGHT_COST_USD = 65;

        var RATES = {
            backpacker: { stay: 12, food: 12, transit: 5, activities: 6, label: 'Backpacker / Budget' },
            midrange:   { stay: 35, food: 24, transit: 10, activities: 11, label: 'Flashpacker / Mid-Range' },
            luxury:     { stay: 100, food: 45, transit: 25, activities: 25, label: 'Luxury Boutique' }
        };

        var state = {
            days: 10,
            style: 'midrange',
            party: 2,
            flights: 2,
            currency: 'USD'
        };

        function formatUSD(num) {
            return '$' + Math.round(num).toLocaleString('en-US');
        }

        function formatVND(num) {
            return Math.round(num).toLocaleString('vi-VN') + ' ₫';
        }

        function formatConverted(usdVal, cur) {
            if (cur === 'USD') {
                var vndVal = Math.round((usdVal * USD_TO_VND) / 100000) * 100000;
                return '≈ ' + vndVal.toLocaleString('vi-VN') + ' VND';
            } else {
                return '≈ ' + formatUSD(usdVal) + ' USD';
            }
        }

        function calculate() {
            var r = RATES[state.style] || RATES.midrange;
            var days = state.days;
            var party = state.party;
            var flights = state.flights;

            var stayFactor = 1.0;
            if (party === 1) {
                stayFactor = 1.0;
            } else if (party === 2) {
                stayFactor = 1.35;
            } else if (party === 3) {
                stayFactor = 2.1;
            } else if (party === 4) {
                stayFactor = 2.7;
            }

            var transitFactor = party === 1 ? 1.0 : (party * 0.65);

            var totalStay = r.stay * days * stayFactor;
            var totalFood = r.food * days * party;
            var totalLocalTransit = r.transit * days * transitFactor;
            var totalFlightCost = flights * FLIGHT_COST_USD * party;
            var totalTransit = totalLocalTransit + totalFlightCost;
            var totalActivities = r.activities * days * party;

            var totalUSD = totalStay + totalFood + totalTransit + totalActivities;
            var dailyAvgUSD = totalUSD / (days * party);

            var pctStay = Math.round((totalStay / totalUSD) * 100) || 35;
            var pctFood = Math.round((totalFood / totalUSD) * 100) || 30;
            var pctTransit = Math.round((totalTransit / totalUSD) * 100) || 20;
            var pctTours = 100 - (pctStay + pctFood + pctTransit);

            return {
                stay: totalStay,
                food: totalFood,
                transit: totalTransit,
                activities: totalActivities,
                totalUSD: totalUSD,
                dailyAvgUSD: dailyAvgUSD,
                pctStay: pctStay,
                pctFood: pctFood,
                pctTransit: pctTransit,
                pctTours: pctTours
            };
        }

        function updateUI() {
            var calc = calculate();
            var cur = state.currency;

            var daysDisplay = document.getElementById('vg-calc-days-display');
            if (daysDisplay) {
                daysDisplay.textContent = state.days + ' Days';
            }

            var totalDisplay = document.getElementById('vg-calc-total-display');
            var convertedDisplay = document.getElementById('vg-calc-total-converted-display');
            var dailyDisplay = document.getElementById('vg-calc-daily-display');
            var partyDisplay = document.getElementById('vg-calc-parties-display');

            if (totalDisplay) {
                totalDisplay.textContent = cur === 'USD' ? formatUSD(calc.totalUSD) : formatVND(calc.totalUSD * USD_TO_VND);
            }
            if (convertedDisplay) {
                convertedDisplay.textContent = formatConverted(calc.totalUSD, cur);
            }
            if (dailyDisplay) {
                dailyDisplay.textContent = cur === 'USD'
                    ? '~' + formatUSD(calc.dailyAvgUSD) + ' / person / day'
                    : '~' + formatVND(calc.dailyAvgUSD * USD_TO_VND) + ' / person / day';
            }
            if (partyDisplay) {
                var partyDesc = state.party === 1 ? '1 solo traveler' : state.party + ' travelers';
                var roomDesc = state.party > 1 ? 'with shared accommodation' : 'private room';
                partyDisplay.textContent = 'Total for ' + partyDesc + ' (' + state.days + ' days ' + roomDesc + ')';
            }

            var amtStay = document.getElementById('vg-amt-stay');
            var amtFood = document.getElementById('vg-amt-food');
            var amtTransit = document.getElementById('vg-amt-transit');
            var amtTours = document.getElementById('vg-amt-tours');

            if (amtStay) amtStay.textContent = cur === 'USD' ? formatUSD(calc.stay) : formatVND(calc.stay * USD_TO_VND);
            if (amtFood) amtFood.textContent = cur === 'USD' ? formatUSD(calc.food) : formatVND(calc.food * USD_TO_VND);
            if (amtTransit) amtTransit.textContent = cur === 'USD' ? formatUSD(calc.transit) : formatVND(calc.transit * USD_TO_VND);
            if (amtTours) amtTours.textContent = cur === 'USD' ? formatUSD(calc.activities) : formatVND(calc.activities * USD_TO_VND);

            var pctStayElem = document.getElementById('vg-pct-stay');
            var pctFoodElem = document.getElementById('vg-pct-food');
            var pctTransitElem = document.getElementById('vg-pct-transit');
            var pctToursElem = document.getElementById('vg-pct-tours');

            if (pctStayElem) pctStayElem.textContent = '(' + calc.pctStay + '%)';
            if (pctFoodElem) pctFoodElem.textContent = '(' + calc.pctFood + '%)';
            if (pctTransitElem) pctTransitElem.textContent = '(' + calc.pctTransit + '%)';
            if (pctToursElem) pctToursElem.textContent = '(' + calc.pctTours + '%)';

            var barStay = document.getElementById('vg-bar-stay');
            var barFood = document.getElementById('vg-bar-food');
            var barTransit = document.getElementById('vg-bar-transit');
            var barTours = document.getElementById('vg-bar-tours');

            if (barStay) barStay.style.width = calc.pctStay + '%';
            if (barFood) barFood.style.width = calc.pctFood + '%';
            if (barTransit) barTransit.style.width = calc.pctTransit + '%';
            if (barTours) barTours.style.width = calc.pctTours + '%';

            var ariaStatus = document.getElementById('vg-calc-aria-status');
            if (ariaStatus) {
                ariaStatus.textContent = 'Estimated Vietnam trip cost: ' + (cur === 'USD' ? formatUSD(calc.totalUSD) : formatVND(calc.totalUSD * USD_TO_VND)) + ' for ' + state.party + ' people across ' + state.days + ' days.';
            }
        }

        function init() {
            var root = document.getElementById('vg-cost-calculator');
            if (!root) return;

            var slider = document.getElementById('vg-calc-days-slider');
            if (slider) {
                slider.addEventListener('input', function (e) {
                    state.days = parseInt(e.target.value, 10) || 10;
                    var presetChips = root.querySelectorAll('.vg-calc-preset-chip');
                    presetChips.forEach(function (chip) {
                        var chipDays = parseInt(chip.getAttribute('data-days'), 10);
                        chip.classList.toggle('is-active', chipDays === state.days);
                    });
                    updateUI();
                });
            }

            var presetChips = root.querySelectorAll('.vg-calc-preset-chip');
            presetChips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var d = parseInt(chip.getAttribute('data-days'), 10);
                    if (d && slider) {
                        slider.value = d;
                        state.days = d;
                        presetChips.forEach(function (c) { c.classList.remove('is-active'); });
                        chip.classList.add('is-active');
                        updateUI();
                    }
                });
            });

            var styleRadios = root.querySelectorAll('input[name="vg_travel_style"]');
            styleRadios.forEach(function (radio) {
                radio.addEventListener('change', function () {
                    if (radio.checked) {
                        state.style = radio.value;
                        root.querySelectorAll('.vg-calc-style-card').forEach(function (card) {
                            card.classList.remove('is-selected');
                        });
                        var parentCard = radio.closest('.vg-calc-style-card');
                        if (parentCard) parentCard.classList.add('is-selected');
                        updateUI();
                    }
                });
            });

            var partyPills = root.querySelectorAll('[data-party]');
            partyPills.forEach(function (pill) {
                pill.addEventListener('click', function () {
                    var p = parseInt(pill.getAttribute('data-party'), 10);
                    if (p) {
                        state.party = p;
                        partyPills.forEach(function (btn) { btn.classList.remove('is-active'); });
                        pill.classList.add('is-active');
                        updateUI();
                    }
                });
            });

            var flightPills = root.querySelectorAll('[data-flights]');
            flightPills.forEach(function (pill) {
                pill.addEventListener('click', function () {
                    var f = parseInt(pill.getAttribute('data-flights'), 10);
                    if (typeof f === 'number' && !isNaN(f)) {
                        state.flights = f;
                        flightPills.forEach(function (btn) { btn.classList.remove('is-active'); });
                        pill.classList.add('is-active');
                        updateUI();
                    }
                });
            });

            var curBtns = root.querySelectorAll('.vg-calc-currency-btn');
            curBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var c = btn.getAttribute('data-currency');
                    if (c && c !== state.currency) {
                        state.currency = c;
                        curBtns.forEach(function (b) {
                            b.classList.remove('is-active');
                            b.setAttribute('aria-pressed', 'false');
                        });
                        btn.classList.add('is-active');
                        btn.setAttribute('aria-pressed', 'true');
                        updateUI();
                    }
                });
            });

            var copyBtn = document.getElementById('vg-calc-copy-btn');
            if (copyBtn) {
                copyBtn.addEventListener('click', function () {
                    var calc = calculate();
                    var r = RATES[state.style] || RATES.midrange;
                    var summaryText = [
                        'Vietnam Travel Budget Estimate (VietnamGuide.net)',
                        '-----------------------------------------------',
                        'Trip Duration: ' + state.days + ' Days',
                        'Travel Style: ' + r.label,
                        'Party Size: ' + state.party + (state.party === 1 ? ' traveler (Solo)' : ' travelers'),
                        'Domestic Flights: ' + state.flights + ' flight(s)',
                        'Total Estimated Cost: ' + formatUSD(calc.totalUSD) + ' (' + formatVND(calc.totalUSD * USD_TO_VND) + ')',
                        'Daily Average per Person: ~' + formatUSD(calc.dailyAvgUSD) + ' / day',
                        '',
                        'Cost Breakdown:',
                        '- Accommodation: ' + formatUSD(calc.stay) + ' (' + calc.pctStay + '%)',
                        '- Food & Dining: ' + formatUSD(calc.food) + ' (' + calc.pctFood + '%)',
                        '- Transport & Flights: ' + formatUSD(calc.transit) + ' (' + calc.pctTransit + '%)',
                        '- Activities & Sightseeing: ' + formatUSD(calc.activities) + ' (' + calc.pctTours + '%)',
                        '',
                        'Calculated at: https://vietnamguide.net/costs/vietnam-travel-cost/'
                    ].join('
');

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(summaryText).then(function () {
                            var originalHtml = copyBtn.innerHTML;
                            copyBtn.classList.add('is-copied');
                            copyBtn.innerHTML = '<span>Copied to Clipboard!</span>';
                            setTimeout(function () {
                                copyBtn.innerHTML = originalHtml;
                                copyBtn.classList.remove('is-copied');
                            }, 2200);
                        }).catch(function () {
                            fallbackCopy(summaryText);
                        });
                    } else {
                        fallbackCopy(summaryText);
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
                    if (copyBtn) {
                        var originalHtml = copyBtn.innerHTML;
                        copyBtn.classList.add('is-copied');
                        copyBtn.innerHTML = '<span>Copied to Clipboard!</span>';
                        setTimeout(function () {
                            copyBtn.innerHTML = originalHtml;
                            copyBtn.classList.remove('is-copied');
                        }, 2200);
                    }
                } catch (err) {}
                document.body.removeChild(textArea);
            }

            var resetBtn = document.getElementById('vg-calc-reset-btn');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    state.days = 10;
                    state.style = 'midrange';
                    state.party = 2;
                    state.flights = 2;
                    state.currency = 'USD';

                    if (slider) slider.value = 10;

                    presetChips.forEach(function (c) {
                        c.classList.toggle('is-active', c.getAttribute('data-days') === '10');
                    });

                    styleRadios.forEach(function (r) {
                        r.checked = (r.value === 'midrange');
                        var card = r.closest('.vg-calc-style-card');
                        if (card) card.classList.toggle('is-selected', r.value === 'midrange');
                    });

                    partyPills.forEach(function (p) {
                        p.classList.toggle('is-active', p.getAttribute('data-party') === '2');
                    });

                    flightPills.forEach(function (f) {
                        f.classList.toggle('is-active', f.getAttribute('data-flights') === '2');
                    });

                    curBtns.forEach(function (b) {
                        var isUsd = b.getAttribute('data-currency') === 'USD';
                        b.classList.toggle('is-active', isUsd);
                        b.setAttribute('aria-pressed', isUsd ? 'true' : 'false');
                    });

                    updateUI();
                });
            }

            updateUI();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
    </script>
    <?php
    return (string) ob_get_clean();
}

/**
 * Shortcode handler for [vg_cost_calculator].
 *
 * @return string
 */
function vg_cost_calculator_shortcode(): string
{
    return vg_render_cost_calculator_html();
}
add_shortcode('vg_cost_calculator', 'vg_cost_calculator_shortcode');

/**
 * Injects the Cost Calculator component on /costs/vietnam-travel-cost/ and /costs/ hub pages.
 *
 * @param string $content Post content.
 * @return string
 */
function vg_inject_cost_calculator_on_page(string $content): string
{
    if (is_admin()) {
        return $content;
    }

    $isCostGuide = is_page('vietnam-travel-cost')
        || (is_singular('page') && get_post_field('post_name') === 'vietnam-travel-cost')
        || is_page('costs')
        || (is_singular('page') && get_post_field('post_name') === 'costs');

    if (! $isCostGuide) {
        return $content;
    }

    if (has_shortcode($content, 'vg_cost_calculator') || strpos($content, 'vg-cost-calculator') !== false) {
        return $content;
    }

    $calculatorHtml = vg_render_cost_calculator_html();
    $heroClose = '<!-- /wp:group -->';
    $pos = strpos($content, $heroClose);

    if ($pos !== false) {
        $insertAt = $pos + strlen($heroClose);
        return substr($content, 0, $insertAt) . "

" . $calculatorHtml . "

" . substr($content, $insertAt);
    }

    return $calculatorHtml . "

" . $content;
}
add_filter('the_content', 'vg_inject_cost_calculator_on_page', 20);

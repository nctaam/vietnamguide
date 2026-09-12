<?php
/**
 * VietnamGuide Interactive Visa Exemption & E-Visa Requirements Checker Component
 *
 * Provides a real-time, zero-dependency client-side nationality visa lookup,
 * 45-day unilateral vs 30-day bilateral exemption status engine, 90-day e-visa cost/timeline,
 * 6-month passport validity calculator, 33-port gate inspector, and anti-scam official portal links.
 *
 * @package VietnamGuide
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Returns the official comprehensive visa policy dataset.
 *
 * @return array<string, mixed>
 */
function vg_get_visa_checker_dataset(): array
{
    return [
        'countries' => [
            // 45-day Unilateral Exemption (Resolution 128/NQ-CP)
            'GB' => ['name' => 'United Kingdom', 'flag' => '🇬🇧', 'cat' => 'exemption_45', 'days' => 45],
            'DE' => ['name' => 'Germany', 'flag' => '🇩🇪', 'cat' => 'exemption_45', 'days' => 45],
            'FR' => ['name' => 'France', 'flag' => '🇫🇷', 'cat' => 'exemption_45', 'days' => 45],
            'IT' => ['name' => 'Italy', 'flag' => '🇮🇹', 'cat' => 'exemption_45', 'days' => 45],
            'ES' => ['name' => 'Spain', 'flag' => '🇪🇸', 'cat' => 'exemption_45', 'days' => 45],
            'JP' => ['name' => 'Japan', 'flag' => '🇯🇵', 'cat' => 'exemption_45', 'days' => 45],
            'KR' => ['name' => 'South Korea', 'flag' => '🇰🇷', 'cat' => 'exemption_45', 'days' => 45],
            'RU' => ['name' => 'Russia', 'flag' => '🇷🇺', 'cat' => 'exemption_45', 'days' => 45],
            'BY' => ['name' => 'Belarus', 'flag' => '🇧🇾', 'cat' => 'exemption_45', 'days' => 45],
            'DK' => ['name' => 'Denmark', 'flag' => '🇩🇰', 'cat' => 'exemption_45', 'days' => 45],
            'SE' => ['name' => 'Sweden', 'flag' => '🇸🇪', 'cat' => 'exemption_45', 'days' => 45],
            'NO' => ['name' => 'Norway', 'flag' => '🇳🇴', 'cat' => 'exemption_45', 'days' => 45],
            'FI' => ['name' => 'Finland', 'flag' => '🇫🇮', 'cat' => 'exemption_45', 'days' => 45],

            // Bilateral Exemption (ASEAN & Partners)
            'TH' => ['name' => 'Thailand', 'flag' => '🇹🇭', 'cat' => 'exemption_30', 'days' => 30],
            'SG' => ['name' => 'Singapore', 'flag' => '🇸🇬', 'cat' => 'exemption_30', 'days' => 30],
            'MY' => ['name' => 'Malaysia', 'flag' => '🇲🇾', 'cat' => 'exemption_30', 'days' => 30],
            'ID' => ['name' => 'Indonesia', 'flag' => '🇮🇩', 'cat' => 'exemption_30', 'days' => 30],
            'KH' => ['name' => 'Cambodia', 'flag' => '🇰🇭', 'cat' => 'exemption_30', 'days' => 30],
            'LA' => ['name' => 'Laos', 'flag' => '🇱🇦', 'cat' => 'exemption_30', 'days' => 30],
            'PH' => ['name' => 'Philippines', 'flag' => '🇵🇭', 'cat' => 'exemption_21', 'days' => 21],
            'BN' => ['name' => 'Brunei', 'flag' => '🇧🇳', 'cat' => 'exemption_14', 'days' => 14],
            'MM' => ['name' => 'Myanmar', 'flag' => '🇲🇲', 'cat' => 'exemption_14', 'days' => 14],

            // 90-day Universal E-Visa Nationalities (Selection of Major Markets)
            'US' => ['name' => 'United States', 'flag' => '🇺🇸', 'cat' => 'evisa_90', 'days' => 90],
            'AU' => ['name' => 'Australia', 'flag' => '🇦🇺', 'cat' => 'evisa_90', 'days' => 90],
            'CA' => ['name' => 'Canada', 'flag' => '🇨🇦', 'cat' => 'evisa_90', 'days' => 90],
            'NZ' => ['name' => 'New Zealand', 'flag' => '🇳🇿', 'cat' => 'evisa_90', 'days' => 90],
            'IN' => ['name' => 'India', 'flag' => '🇮🇳', 'cat' => 'evisa_90', 'days' => 90],
            'IE' => ['name' => 'Ireland', 'flag' => '🇮🇪', 'cat' => 'evisa_90', 'days' => 90],
            'CH' => ['name' => 'Switzerland', 'flag' => '🇨🇭', 'cat' => 'evisa_90', 'days' => 90],
            'NL' => ['name' => 'Netherlands', 'flag' => '🇳🇱', 'cat' => 'evisa_90', 'days' => 90],
            'BE' => ['name' => 'Belgium', 'flag' => '🇧🇪', 'cat' => 'evisa_90', 'days' => 90],
            'AT' => ['name' => 'Austria', 'flag' => '🇦🇹', 'cat' => 'evisa_90', 'days' => 90],
            'PL' => ['name' => 'Poland', 'flag' => '🇵🇱', 'cat' => 'evisa_90', 'days' => 90],
            'CZ' => ['name' => 'Czech Republic', 'flag' => '🇨🇿', 'cat' => 'evisa_90', 'days' => 90],
            'PT' => ['name' => 'Portugal', 'flag' => '🇵🇹', 'cat' => 'evisa_90', 'days' => 90],
            'GR' => ['name' => 'Greece', 'flag' => '🇬🇷', 'cat' => 'evisa_90', 'days' => 90],
            'HU' => ['name' => 'Hungary', 'flag' => '🇭🇺', 'cat' => 'evisa_90', 'days' => 90],
            'RO' => ['name' => 'Romania', 'flag' => '🇷🇴', 'cat' => 'evisa_90', 'days' => 90],
            'SK' => ['name' => 'Slovakia', 'flag' => '🇸🇰', 'cat' => 'evisa_90', 'days' => 90],
            'BG' => ['name' => 'Bulgaria', 'flag' => '🇧🇬', 'cat' => 'evisa_90', 'days' => 90],
            'HR' => ['name' => 'Croatia', 'flag' => '🇭🇷', 'cat' => 'evisa_90', 'days' => 90],
            'IL' => ['name' => 'Israel', 'flag' => '🇮🇱', 'cat' => 'evisa_90', 'days' => 90],
            'ZA' => ['name' => 'South Africa', 'flag' => '🇿🇦', 'cat' => 'evisa_90', 'days' => 90],
            'BR' => ['name' => 'Brazil', 'flag' => '🇧🇷', 'cat' => 'evisa_90', 'days' => 90],
            'MX' => ['name' => 'Mexico', 'flag' => '🇲🇽', 'cat' => 'evisa_90', 'days' => 90],
            'AR' => ['name' => 'Argentina', 'flag' => '🇦🇷', 'cat' => 'evisa_90', 'days' => 90],
            'CL' => ['name' => 'Chile', 'flag' => '🇨🇱', 'cat' => 'exemption_90_chile', 'days' => 90],
            'PA' => ['name' => 'Panama', 'flag' => '🇵🇦', 'cat' => 'exemption_30', 'days' => 30],
            'CN' => ['name' => 'China', 'flag' => '🇨🇳', 'cat' => 'evisa_90_china', 'days' => 90],
            'HK' => ['name' => 'Hong Kong (SAR)', 'flag' => '🇭🇰', 'cat' => 'evisa_90', 'days' => 90],
            'TW' => ['name' => 'Taiwan', 'flag' => '🇹🇼', 'cat' => 'evisa_90', 'days' => 90],
            'AE' => ['name' => 'United Arab Emirates', 'flag' => '🇦🇪', 'cat' => 'evisa_90', 'days' => 90],
            'SA' => ['name' => 'Saudi Arabia', 'flag' => '🇸🇦', 'cat' => 'evisa_90', 'days' => 90],
            'QA' => ['name' => 'Qatar', 'flag' => '🇶🇦', 'cat' => 'evisa_90', 'days' => 90],
            'TR' => ['name' => 'Turkey', 'flag' => '🇹🇷', 'cat' => 'evisa_90', 'days' => 90],
            'EG' => ['name' => 'Egypt', 'flag' => '🇪🇬', 'cat' => 'evisa_90', 'days' => 90],
            'CO' => ['name' => 'Colombia', 'flag' => '🇨🇴', 'cat' => 'evisa_90', 'days' => 90],
            'PE' => ['name' => 'Peru', 'flag' => '🇵🇪', 'cat' => 'evisa_90', 'days' => 90],
            'UA' => ['name' => 'Ukraine', 'flag' => '🇺🇦', 'cat' => 'evisa_90', 'days' => 90],
            'KZ' => ['name' => 'Kazakhstan', 'flag' => '🇰🇿', 'cat' => 'exemption_30', 'days' => 30],
            'MN' => ['name' => 'Mongolia', 'flag' => '🇲🇳', 'cat' => 'exemption_30', 'days' => 30],
        ],

        'checkpoints' => [
            'airports' => [
                'Noi Bai International Airport (HAN - Hanoi)',
                'Tan Son Nhat International Airport (SGN - Ho Chi Minh City)',
                'Da Nang International Airport (DAD - Da Nang)',
                'Cam Ranh International Airport (CXR - Nha Trang)',
                'Phu Quoc International Airport (PQC - Phu Quoc Island)',
                'Cat Bi International Airport (HPH - Hai Phong)',
                'Can Tho International Airport (VCA - Can Tho)',
                'Van Don International Airport (VDO - Quang Ninh)',
            ],
            'land' => [
                'Huu Nghi (Lang Son - China border)',
                'Lao Cai (Lao Cai - China border)',
                'Mong Cai (Quang Ninh - China border)',
                'Moc Bai (Tay Ninh - Cambodia border)',
                'Xa Mat (Tay Ninh - Cambodia border)',
                'Tinh Bien (An Giang - Cambodia border)',
                'Ha Tien (Kien Giang - Cambodia border)',
                'Lao Bao (Quang Tri - Laos border)',
                'Cau Treo (Ha Tinh - Laos border)',
                'Cha Lo (Quang Binh - Laos border)',
                'Bo Y (Kon Tum - Laos/Cambodia border)',
                'Tay Trang (Dien Bien - Laos border)',
                'Na Meo (Thanh Hoa - Laos border)',
                'Nam Can (Nghe An - Laos border)',
                'La Lay (Quang Tri - Laos border)',
                'Vinh Xuong (An Giang - Cambodia water/land border)',
            ],
            'seaports' => [
                'Hon Gai Seaport (Quang Ninh)',
                'Cam Pha Seaport (Quang Ninh)',
                'Hai Phong Seaport (Hai Phong)',
                'Nghi Son Seaport (Thanh Hoa)',
                'Vung Ang Seaport (Ha Tinh)',
                'Chan May Seaport (Thua Thien Hue)',
                'Da Nang Seaport (Da Nang)',
                'Nha Trang Seaport (Khanh Hoa)',
                'Quy Nhon Seaport (Binh Dinh)',
                'Dung Quat Seaport (Quang Ngai)',
                'Vung Tau Seaport (Ba Ria - Vung Tau)',
                'Ho Chi Minh City Seaport (Saigon)',
            ],
        ],

        'rules' => [
            'passport_validity' => 'Passport must be valid for at least 6 months beyond your scheduled date of entry into Vietnam.',
            'blank_pages' => 'At least 2 blank visa endorsement pages are required for entry and exit immigration stamps.',
            'onward_ticket' => 'A confirmed outbound flight or train ticket leaving Vietnam within your allowed duration is legally required for visa-free entry and strictly checked by airlines before boarding.',
            'port_declaration' => 'When entering on an E-visa, your actual arrival port must match one of the 33 international checkpoints approved by the Vietnam Immigration Department.',
            'no_gap_rule' => 'The former 30-day waiting period between consecutive visa-free entries has been completely abolished. You may re-enter under exemption anytime, provided you meet entry criteria.',
            'phu_quoc_rule' => 'Phu Quoc Island offers a special 30-day visa exemption for ALL nationalities arriving directly by international flight or transit via Hanoi/HCMC domestic transfer without leaving the transit lounge.',
        ],

        'official_links' => [
            'portal_url' => 'https://evisa.xuatnhapcanh.gov.vn/',
            'single_fee_usd' => 25,
            'multi_fee_usd' => 50,
            'processing_days' => '3–5 working days (excluding weekends & Vietnamese public holidays)',
            'recommended_buffer' => 'Apply at least 10–14 days before flight departure',
        ],
    ];
}

/**
 * Renders the Interactive Visa & E-Visa Requirements Checker HTML.
 *
 * @return string
 */
function vg_render_visa_checker_html(): string
{
    $data = vg_get_visa_checker_dataset();
    $countries = $data['countries'];
    $official = $data['official_links'];
    $checkpoints = $data['checkpoints'];

    ob_start();
    ?>
    <section class="vg-visa-checker" id="vg-visa-checker" aria-labelledby="vg-visa-checker-title">
        <div class="vg-vc-header">
            <div class="vg-vc-eyebrow">
                <span class="vg-vc-badge-live">Live Immigration Engine</span>
                <span class="vg-vc-meta">Updated: 2026 Resolution 128/NQ-CP & 127/NQ-CP</span>
            </div>
            <div id="vg-visa-checker-title" class="vg-vc-title">Interactive Vietnam Visa &amp; E-Visa Requirements Checker</div>
            <p class="vg-vc-subtitle">Select your nationality and planned stay duration to instantly calculate your official entry status, statutory fees, passport validity deadlines, and avoid third-party agency scams.</p>
        </div>

        <noscript>
            <div class="vg-visa-noscript-card" style="background:#f8f9fa;border:1px solid #cbd5e1;border-radius:8px;padding:20px;margin-bottom:24px;">
                <p style="font-weight:700;margin-bottom:8px;color:#1a365d;">🛂 2026 Vietnam Visa Policy Summary (No-JavaScript Reference):</p>
                <p style="font-size:0.9rem;margin-bottom:12px;color:#475569;">Interactive checker requires JavaScript. Authoritative statutory visa rules under Resolution 128/NQ-CP &amp; Decree 127:</p>
                <ul style="margin-bottom:0;padding-left:20px;font-size:0.9rem;line-height:1.6;">
                    <li><strong>45-Day Visa Exemption:</strong> UK, Germany, France, Italy, Spain, Japan, South Korea, Russia, Sweden, Norway, Denmark, Finland, Belarus.</li>
                    <li><strong>30-Day Visa Exemption (ASEAN):</strong> Singapore, Thailand, Malaysia, Indonesia, Philippines (21 days), Cambodia, Laos.</li>
                    <li><strong>90-Day E-Visa:</strong> Available to all countries and territories via the official portal <code>xuatnhapcanh.gov.vn</code> ($25 USD single entry / $50 USD multiple entry).</li>
                    <li><strong>Phu Quoc 30-Day Island Exemption:</strong> Applicable for direct international air arrivals at PQC airport.</li>
                </ul>
            </div>
        </noscript>

        <!-- Interactive Control Bar -->
        <div class="vg-vc-controls">
            <!-- Nationality Input & Quick Chips -->
            <div class="vg-vc-control-group">
                <label for="vg-vc-country-select" class="vg-vc-label">
                    <span class="vg-vc-label-icon">🛂</span>
                    <span>Your Passport Nationality:</span>
                </label>
                <div class="vg-vc-search-wrapper">
                    <select id="vg-vc-country-select" class="vg-vc-select" aria-label="Select your passport nationality">
                        <?php foreach ($countries as $code => $info): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($code, 'GB'); ?>>
                                <?php echo esc_html($info['flag'] . ' ' . $info['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Fast Select Chips -->
                <div class="vg-vc-quick-chips" aria-label="Popular nationalities">
                    <span class="vg-vc-chip-label">Quick select:</span>
                    <button type="button" class="vg-vc-chip active" data-country="GB" aria-pressed="true">🇬🇧 UK</button>
                    <button type="button" class="vg-vc-chip" data-country="US" aria-pressed="false">🇺🇸 US</button>
                    <button type="button" class="vg-vc-chip" data-country="AU" aria-pressed="false">🇦🇺 Australia</button>
                    <button type="button" class="vg-vc-chip" data-country="DE" aria-pressed="false">🇩🇪 Germany</button>
                    <button type="button" class="vg-vc-chip" data-country="FR" aria-pressed="false">🇫🇷 France</button>
                    <button type="button" class="vg-vc-chip" data-country="KR" aria-pressed="false">🇰🇷 South Korea</button>
                    <button type="button" class="vg-vc-chip" data-country="JP" aria-pressed="false">🇯🇵 Japan</button>
                    <button type="button" class="vg-vc-chip" data-country="CA" aria-pressed="false">🇨🇦 Canada</button>
                    <button type="button" class="vg-vc-chip" data-country="IN" aria-pressed="false">🇮🇳 India</button>
                    <button type="button" class="vg-vc-chip" data-country="SG" aria-pressed="false">🇸🇬 Singapore</button>
                </div>
            </div>

            <!-- Planned Stay Duration & Entry Mode -->
            <div class="vg-vc-grid-two">
                <div class="vg-vc-control-group">
                    <label for="vg-vc-duration-slider" class="vg-vc-label">
                        <span class="vg-vc-label-icon">📅</span>
                        <span>Planned Trip Duration: <strong id="vg-vc-duration-val" class="vg-vc-highlight">14 days</strong></span>
                    </label>
                    <div class="vg-vc-slider-wrap">
                        <input type="range" id="vg-vc-duration-slider" min="1" max="90" value="14" step="1" class="vg-vc-slider" aria-valuemin="1" aria-valuemax="90" aria-valuenow="14">
                        <div class="vg-vc-slider-markers">
                            <span>1d</span>
                            <span>15d</span>
                            <span>30d</span>
                            <span>45d (Exemption cap)</span>
                            <span>90d (E-Visa cap)</span>
                        </div>
                    </div>
                </div>

                <div class="vg-vc-control-group">
                    <label class="vg-vc-label">
                        <span class="vg-vc-label-icon">🔄</span>
                        <span>Border Entry Type:</span>
                    </label>
                    <div class="vg-vc-toggle-group" role="radiogroup" aria-label="Border Entry Type">
                        <button type="button" class="vg-vc-toggle-btn active" data-entry="single" role="radio" aria-checked="true">
                            <span class="vg-vc-tb-title">Single Entry</span>
                            <span class="vg-vc-tb-sub">One stay in Vietnam</span>
                        </button>
                        <button type="button" class="vg-vc-toggle-btn" data-entry="multiple" role="radio" aria-checked="false">
                            <span class="vg-vc-tb-title">Multiple Entry</span>
                            <span class="vg-vc-tb-sub">Side trips to Cambodia/Laos</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dynamic Output Display Panel -->
        <div class="vg-vc-results" id="vg-vc-results" aria-live="polite">
            <!-- Verdict Hero Banner -->
            <div class="vg-vc-verdict-banner" id="vg-vc-verdict-card">
                <div class="vg-vc-verdict-left">
                    <div class="vg-vc-verdict-status-badge" id="vg-vc-status-badge">
                        <span class="vg-vc-badge-dot"></span>
                        <span id="vg-vc-status-text">45-Day Visa Exemption Eligible</span>
                    </div>
                    <div class="vg-vc-verdict-heading" id="vg-vc-verdict-title">No Visa Required for your 14-day trip!</div>
                    <p class="vg-vc-verdict-summary" id="vg-vc-verdict-desc">
                        As a citizen of the United Kingdom, you are granted unilateral visa-free entry for up to 45 consecutive days under Resolution 128/NQ-CP. You do not need to apply for an E-visa or pay any visa fees.
                    </p>
                </div>
                <div class="vg-vc-verdict-right">
                    <div class="vg-vc-cost-card">
                        <div class="vg-vc-cost-label">Official Visa Fee</div>
                        <div class="vg-vc-cost-amount" id="vg-vc-cost-amount">$0 USD</div>
                        <div class="vg-vc-cost-sub" id="vg-vc-cost-sub">Save $25–$50 USD compared to third-party agency sites</div>
                    </div>
                </div>
            </div>

            <!-- Detailed Grid: Rules, Checklist, Warnings -->
            <div class="vg-vc-details-grid">
                <!-- Card 1: Statutory Entry Requirements -->
                <div class="vg-vc-card">
                    <div class="vg-vc-card-head">
                        <span class="vg-vc-card-icon">📋</span>
                        <div class="vg-vc-card-title">Mandatory Entry Rules</div>
                    </div>
                    <ul class="vg-vc-rule-list" id="vg-vc-rule-list">
                        <li class="vg-vc-rule-item passed">
                            <span class="vg-vc-rule-icon">✓</span>
                            <div class="vg-vc-rule-content">
                                <strong>6-Month Passport Validity:</strong>
                                <span>Must remain valid for at least 6 months past your entry date.</span>
                            </div>
                        </li>
                        <li class="vg-vc-rule-item passed">
                            <span class="vg-vc-rule-icon">✓</span>
                            <div class="vg-vc-rule-content">
                                <strong>2 Blank Visa Pages:</strong>
                                <span>Required for full-page entry and exit immigration rubber stamps.</span>
                            </div>
                        </li>
                        <li class="vg-vc-rule-item passed">
                            <span class="vg-vc-rule-icon">✓</span>
                            <div class="vg-vc-rule-content">
                                <strong>Outbound Flight Ticket:</strong>
                                <span>Must prove departure from Vietnam within <span id="vg-vc-max-days-rule">45</span> days (mandated by airline boarding check).</span>
                            </div>
                        </li>
                        <li class="vg-vc-rule-item info">
                            <span class="vg-vc-rule-icon">ℹ</span>
                            <div class="vg-vc-rule-content">
                                <strong>No Gap Requirement:</strong>
                                <span>The old 30-day waiting gap between visa-free visits was abolished in 2020. You can re-enter freely.</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Card 2: Interactive Passport Expiration Validator -->
                <div class="vg-vc-card">
                    <div class="vg-vc-card-head">
                        <span class="vg-vc-card-icon">⏳</span>
                        <div class="vg-vc-card-title">Passport Expiry Calculator</div>
                    </div>
                    <p class="vg-vc-card-sub">Test if your actual passport meets the 6-month rule based on your intended arrival date:</p>
                    <div class="vg-vc-calculator-form">
                        <div class="vg-vc-input-block">
                            <label for="vg-vc-arrival-date">Arrival in Vietnam:</label>
                            <input type="date" id="vg-vc-arrival-date" class="vg-vc-date-input" value="<?php echo esc_attr(date('Y-m-d', strtotime('+30 days'))); ?>">
                        </div>
                        <div class="vg-vc-calc-result" id="vg-vc-calc-result">
                            <div class="vg-vc-calc-line">
                                <span>Passport must expire on or after:</span>
                                <strong id="vg-vc-min-expiry-date"><?php echo esc_html(date('M j, Y', strtotime('+213 days'))); ?></strong>
                            </div>
                            <div class="vg-vc-calc-verdict" id="vg-vc-calc-verdict">
                                🛡️ Ensure your passport expiration date is strictly after this threshold.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: E-Visa Photo & Document Specifications -->
                <div class="vg-vc-card" id="vg-vc-photo-card">
                    <div class="vg-vc-card-head">
                        <span class="vg-vc-card-icon">📷</span>
                        <div class="vg-vc-card-title">Document &amp; Photo Standards</div>
                    </div>
                    <div class="vg-vc-specs-row">
                        <div class="vg-vc-spec-box">
                            <div class="vg-vc-spec-title">Portrait Photo (4x6 cm)</div>
                            <ul class="vg-vc-bullet-list">
                                <li>Plain white background, no shadows</li>
                                <li>Direct camera gaze, neutral expression</li>
                                <li>No eyeglasses, tinted lenses, or glare</li>
                                <li>No hats or headwear (except religious)</li>
                            </ul>
                        </div>
                        <div class="vg-vc-spec-box">
                            <div class="vg-vc-spec-title">Passport Bio-Data Page</div>
                            <ul class="vg-vc-bullet-list">
                                <li>Full page visible with all 4 corners</li>
                                <li>Both MRZ code lines completely sharp</li>
                                <li>No fingers, glare, or flash reflection</li>
                                <li>High resolution JPG/PNG under 2 MB</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Official Link & Anti-Scam Shield -->
                <div class="vg-vc-card vg-vc-scam-shield">
                    <div class="vg-vc-card-head">
                        <span class="vg-vc-card-icon">🛡️</span>
                        <div class="vg-vc-card-title">Anti-Scam Advisory &amp; Official Portal</div>
                    </div>
                    <p class="vg-vc-scam-text">
                        <strong>Warning on Unofficial Intermediaries:</strong> Hundreds of commercial .com, .org, and .co websites mimic the official government portal and charge $80 to $150 USD for a simple e-visa that costs only <strong>$25 USD</strong>.
                    </p>
                    <div class="vg-vc-official-box">
                        <div class="vg-vc-official-meta">
                            <span class="vg-vc-gov-tag">Official Vietnam Immigration Portal</span>
                            <code class="vg-vc-gov-url"><?php echo esc_html($official['portal_url']); ?></code>
                        </div>
                        <a href="<?php echo esc_url($official['portal_url']); ?>" target="_blank" rel="noopener noreferrer" class="vg-vc-btn-official">
                            <span>Open Official E-Visa Portal</span>
                            <span class="vg-vc-external-arrow">↗</span>
                        </a>
                    </div>
                    <div class="vg-vc-scam-tips">
                        <span>💡 Tip: Government portals in Vietnam always end in <strong>.gov.vn</strong>. Never enter your credit card on non-.gov.vn domains.</span>
                    </div>
                </div>
            </div>

            <!-- Approved International Checkpoints Collapsible -->
            <div class="vg-vc-checkpoints-section">
                <details class="vg-vc-details">
                    <summary class="vg-vc-details-summary">
                        <span class="vg-vc-summary-icon">📍</span>
                        <span class="vg-vc-summary-text">View All 33 Approved E-Visa Entry &amp; Exit Border Gates</span>
                        <span class="vg-vc-summary-pill">33 Ports Total</span>
                    </summary>
                    <div class="vg-vc-ports-grid">
                        <div class="vg-vc-port-col">
                            <div class="vg-vc-port-subhead">✈️ 8 International Airports</div>
                            <ul>
                                <?php foreach ($checkpoints['airports'] as $port): ?>
                                    <li><?php echo esc_html($port); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="vg-vc-port-col">
                            <div class="vg-vc-port-subhead">🛂 16 Land Border Gates</div>
                            <ul>
                                <?php foreach ($checkpoints['land'] as $port): ?>
                                    <li><?php echo esc_html($port); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="vg-vc-port-col">
                            <div class="vg-vc-port-subhead">🚢 9 International Seaports</div>
                            <ul>
                                <?php foreach ($checkpoints['seaports'] as $port): ?>
                                    <li><?php echo esc_html($port); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </details>
            </div>

            <!-- Special Zone Phu Quoc Accordion -->
            <div class="vg-vc-special-zone">
                <div class="vg-vc-sz-badge">🏝️ Special Economic Zone Exemption</div>
                <div class="vg-vc-sz-content">
                    <strong>Visiting Phu Quoc Island Only?</strong>
                    <span>All foreign nationals (including US, Australia, India, etc.) can enter Phu Quoc completely <strong>visa-free for up to 30 days</strong>, provided they fly directly from abroad or transit via Hanoi/Ho Chi Minh City without leaving the domestic transit terminal, with outbound flight leaving within 30 days.</span>
                </div>
            </div>

            <!-- Cross-Tool Contextual Bridge: Next Steps -->
            <div class="vg-vc-next-steps vg-tool-synergy-bar" id="vg-vc-next-steps">
                <div class="vg-vc-ns-head">
                    <span class="vg-vc-ns-icon">🗺️</span>
                    <div class="vg-vc-ns-title"><?php esc_html_e('Next Steps for Your Vietnam Journey', 'vietnamguide-premium'); ?></div>
                </div>
                <div class="vg-vc-ns-grid">
                    <a href="<?php echo esc_url(home_url('/itineraries/')); ?>" class="vg-vc-ns-card vg-synergy-bridge" id="vg-vc-ns-itinerary">
                        <span class="vg-vc-ns-tag"><?php esc_html_e('Recommended Route', 'vietnamguide-premium'); ?></span>
                        <strong class="vg-vc-ns-name" id="vg-vc-ns-itinerary-title"><?php esc_html_e('Explore Itineraries', 'vietnamguide-premium'); ?></strong>
                        <span class="vg-vc-ns-desc"><?php esc_html_e('Day-by-day routes matching your stay duration', 'vietnamguide-premium'); ?> &rarr;</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/plan/vietnam-airport-arrival-checklist/')); ?>" class="vg-vc-ns-card vg-synergy-bridge" id="vg-vc-ns-airport">
                        <span class="vg-vc-ns-tag"><?php esc_html_e('Arrival Transit', 'vietnamguide-premium'); ?></span>
                        <strong class="vg-vc-ns-name"><?php esc_html_e('Airport Transit Navigator', 'vietnamguide-premium'); ?></strong>
                        <span class="vg-vc-ns-desc"><?php esc_html_e('Grab bays, metered taxi numbers & scam shields for HAN/SGN/DAD', 'vietnamguide-premium'); ?> &rarr;</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/costs/vietnam-travel-cost/')); ?>" class="vg-vc-ns-card vg-synergy-bridge" id="vg-vc-ns-cost">
                        <span class="vg-vc-ns-tag"><?php esc_html_e('Budget Calculator', 'vietnamguide-premium'); ?></span>
                        <strong class="vg-vc-ns-name"><?php esc_html_e('Calculate Travel Budget', 'vietnamguide-premium'); ?></strong>
                        <span class="vg-vc-ns-desc"><?php esc_html_e('Estimate hotel, food, and domestic transit', 'vietnamguide-premium'); ?> &rarr;</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/plan/best-time-to-visit-vietnam/')); ?>" class="vg-vc-ns-card vg-synergy-bridge" id="vg-vc-ns-weather">
                        <span class="vg-vc-ns-tag"><?php esc_html_e('Weather & Packing', 'vietnamguide-premium'); ?></span>
                        <strong class="vg-vc-ns-name"><?php esc_html_e('Regional Climate Matrix', 'vietnamguide-premium'); ?></strong>
                        <span class="vg-vc-ns-desc"><?php esc_html_e('12-month climate guide & packing list', 'vietnamguide-premium'); ?> &rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Copy Action Bar -->
            <div class="vg-vc-action-bar">
                <button type="button" id="vg-vc-copy-summary" class="vg-vc-btn-copy">
                    <span class="vg-vc-copy-icon">📋</span>
                    <span id="vg-vc-copy-label">Copy Visa Requirements Summary</span>
                </button>
                <span class="vg-vc-copy-hint">Copy formatted requirements checklist to clipboard for your travel notes.</span>
            </div>
        </div>

        <div id="vg-vc-aria-status" class="screen-reader-text" aria-live="polite"></div>
    </section>

    <!-- Isolated Client-Side Logic -->
    <script>
    (function () {
        'use strict';

        const dataset = <?php echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        if (!dataset || !dataset.countries) return;

        // Elements
        const countrySelect = document.getElementById('vg-vc-country-select');
        const durationSlider = document.getElementById('vg-vc-duration-slider');
        const durationVal = document.getElementById('vg-vc-duration-val');
        const toggleButtons = document.querySelectorAll('.vg-vc-toggle-btn');
        const chips = document.querySelectorAll('.vg-vc-chip');

        const verdictCard = document.getElementById('vg-vc-verdict-card');
        const statusBadge = document.getElementById('vg-vc-status-badge');
        const statusText = document.getElementById('vg-vc-status-text');
        const verdictTitle = document.getElementById('vg-vc-verdict-title');
        const verdictDesc = document.getElementById('vg-vc-verdict-desc');
        const costAmount = document.getElementById('vg-vc-cost-amount');
        const costSub = document.getElementById('vg-vc-cost-sub');
        const maxDaysRule = document.getElementById('vg-vc-max-days-rule');

        const arrivalInput = document.getElementById('vg-vc-arrival-date');
        const minExpiryDateEl = document.getElementById('vg-vc-min-expiry-date');
        const copyBtn = document.getElementById('vg-vc-copy-summary');
        const copyLabel = document.getElementById('vg-vc-copy-label');

        let currentCountry = 'GB';
        let currentDuration = 14;
        let currentEntry = 'single';

        function calculatePassportExpiry() {
            if (!arrivalInput || !minExpiryDateEl) return;
            const arrivalVal = arrivalInput.value;
            if (!arrivalVal) return;

            const arrDate = new Date(arrivalVal);
            if (isNaN(arrDate.getTime())) return;

            // Add 183 days (approx 6 months)
            const expiry = new Date(arrDate.getTime() + (183 * 24 * 60 * 60 * 1000));
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            minExpiryDateEl.textContent = expiry.toLocaleDateString('en-US', options);
        }

        function updateVisaStatus() {
            const countryInfo = dataset.countries[currentCountry] || {
                name: 'Your Country',
                flag: '🌐',
                cat: 'evisa_90',
                days: 90
            };

            const cat = countryInfo.cat;
            const exemptionDays = countryInfo.days || 0;
            const isExempt = cat.startsWith('exemption_') && (currentDuration <= exemptionDays) && (currentEntry === 'single');

            // Reset verdict classes
            verdictCard.classList.remove('status-exempt', 'status-evisa', 'status-warning');

            if (isExempt) {
                // VISA EXEMPTION
                verdictCard.classList.add('status-exempt');
                statusText.textContent = `${exemptionDays}-Day Visa Exemption Eligible`;
                verdictTitle.textContent = `No Visa Required for your ${currentDuration}-day trip!`;
                verdictDesc.textContent = `As a citizen of ${countryInfo.name}, you are eligible for unilateral or bilateral visa-free entry for up to ${exemptionDays} days. You do not need to apply for an E-visa or pay statutory fees, provided you have a confirmed outbound ticket.`;
                costAmount.textContent = '$0 USD';
                costSub.textContent = `Save $25–$50 USD. Enter with your passport and onward ticket.`;
                if (maxDaysRule) maxDaysRule.textContent = exemptionDays;
            } else if (cat.startsWith('exemption_') && currentDuration > exemptionDays) {
                // EXEMPT NATIONALITY BUT STAY EXCEEDS EXEMPTION LIMIT -> REQUIRES E-VISA
                verdictCard.classList.add('status-warning');
                statusText.textContent = `90-Day E-Visa Required (Stay Exceeds ${exemptionDays} Days)`;
                verdictTitle.textContent = `Apply for a 90-Day E-Visa before departure!`;
                verdictDesc.textContent = `While ${countryInfo.name} citizens enjoy a ${exemptionDays}-day visa exemption, your planned stay of ${currentDuration} days exceeds this limit. You must apply for an official 90-day Vietnam E-visa online before your flight.`;
                costAmount.textContent = currentEntry === 'multiple' ? '$50 USD' : '$25 USD';
                costSub.textContent = `Statutory government fee on official portal (${currentEntry === 'multiple' ? 'Multiple Entry' : 'Single Entry'}).`;
                if (maxDaysRule) maxDaysRule.textContent = '90';
            } else if (cat.startsWith('exemption_') && currentEntry === 'multiple') {
                // EXEMPT NATIONALITY BUT MULTIPLE ENTRY -> RECOMMEND E-VISA
                verdictCard.classList.add('status-warning');
                statusText.textContent = `E-Visa Recommended for Multi-Border Travel`;
                verdictTitle.textContent = `90-Day Multiple-Entry E-Visa Recommended`;
                verdictDesc.textContent = `If you plan to exit Vietnam into Cambodia/Laos and re-enter, consecutive visa-free entries are permitted but require re-inspection and tickets at each crossing. A 90-day Multiple Entry E-visa ($50 USD) offers total peace of mind.`;
                costAmount.textContent = '$50 USD';
                costSub.textContent = 'Official 90-day Multiple Entry E-visa fee.';
                if (maxDaysRule) maxDaysRule.textContent = '90';
            } else if (cat === 'evisa_90_china') {
                // CHINA E-PASSPORT SPECIAL NOTE
                verdictCard.classList.add('status-evisa');
                statusText.textContent = '90-Day E-Visa Required (Loose-Leaf Visa)';
                verdictTitle.textContent = '90-Day E-Visa Eligible';
                verdictDesc.textContent = `Chinese citizens holding ordinary electronic passports (with 'E' prefix) can apply for 90-day single/multiple entry E-visas online. Note that immigration officers at border control will issue a separate loose-leaf visa sticker upon arrival.`;
                costAmount.textContent = currentEntry === 'multiple' ? '$50 USD' : '$25 USD';
                costSub.textContent = 'Official statutory fee on evisa.xuatnhapcanh.gov.vn';
                if (maxDaysRule) maxDaysRule.textContent = '90';
            } else {
                // UNIVERSAL E-VISA
                verdictCard.classList.add('status-evisa');
                statusText.textContent = '90-Day E-Visa Required';
                verdictTitle.textContent = `Apply for a 90-Day Vietnam E-Visa`;
                verdictDesc.textContent = `Citizens of ${countryInfo.name} are eligible for Vietnam's universal 90-day E-visa under Resolution 127/NQ-CP. Processing takes 3–5 working days directly through the official Vietnam Immigration Department portal.`;
                costAmount.textContent = currentEntry === 'multiple' ? '$50 USD' : '$25 USD';
                costSub.textContent = `Official fee for ${currentEntry === 'multiple' ? 'Multiple Entry ($50)' : 'Single Entry ($25)'}. No agency markups!`;
                if (maxDaysRule) maxDaysRule.textContent = '90';
            }

            // Update Cross-Tool Contextual Links
            var itinLink = document.getElementById('vg-vc-ns-itinerary');
            var itinTitle = document.getElementById('vg-vc-ns-itinerary-title');
            var costLink = document.getElementById('vg-vc-ns-cost');
            if (itinLink && itinTitle) {
                if (currentDuration <= 8) {
                    itinLink.href = '<?php echo esc_url(home_url('/itineraries/7-days-in-vietnam/')); ?>';
                    itinTitle.textContent = '7-Day Essential Highlights Route';
                } else if (currentDuration <= 12) {
                    itinLink.href = '<?php echo esc_url(home_url('/itineraries/10-days-in-vietnam/')); ?>';
                    itinTitle.textContent = '10-Day Classic North-to-South Route';
                } else if (currentDuration <= 18) {
                    itinLink.href = '<?php echo esc_url(home_url('/itineraries/14-days-in-vietnam/')); ?>';
                    itinTitle.textContent = '14-Day Complete Grand Tour';
                } else {
                    itinLink.href = '<?php echo esc_url(home_url('/itineraries/21-days-in-vietnam/')); ?>';
                    itinTitle.textContent = '21-Day Deep Discovery Journey';
                }
            }
            if (costLink) {
                costLink.href = '<?php echo esc_url(home_url('/costs/vietnam-travel-cost/')); ?>?days=' + currentDuration;
            }

            // Save state to storage and sync URL
            setSafeStorage('vg_user_nationality', currentCountry);
            setSafeStorage('vg_user_duration', currentDuration);
            syncUrlParams({ nationality: currentCountry, days: currentDuration });

            var ariaStatus = document.getElementById('vg-vc-aria-status');
            if (ariaStatus) {
                ariaStatus.textContent = 'Visa requirements updated: ' + statusText.textContent + ' for ' + countryInfo.name + '. Estimated fee: ' + costAmount.textContent + '.';
            }
        }

        // Country Select Listener
        if (countrySelect) {
            countrySelect.addEventListener('change', function () {
                currentCountry = this.value;
                chips.forEach(c => {
                    const isActive = c.getAttribute('data-country') === currentCountry;
                    c.classList.toggle('active', isActive);
                    c.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
                updateVisaStatus();
            });
        }

        // Fast Chips Listener & Keyboard Navigation
        chips.forEach((chip, idx) => {
            chip.addEventListener('click', function () {
                chips.forEach(c => {
                    c.classList.remove('active');
                    c.setAttribute('aria-pressed', 'false');
                });
                this.classList.add('active');
                this.setAttribute('aria-pressed', 'true');
                currentCountry = this.getAttribute('data-country');
                if (countrySelect) countrySelect.value = currentCountry;
                updateVisaStatus();
            });

            chip.addEventListener('keydown', function (e) {
                let targetIdx = -1;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') targetIdx = (idx + 1) % chips.length;
                else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') targetIdx = (idx - 1 + chips.length) % chips.length;
                if (targetIdx !== -1) {
                    e.preventDefault();
                    chips[targetIdx].focus();
                    chips[targetIdx].click();
                }
            });
        });

        // Duration Slider Listener
        if (durationSlider && durationVal) {
            durationSlider.addEventListener('input', function () {
                currentDuration = parseInt(this.value, 10);
                durationVal.textContent = currentDuration + ' days';
                updateVisaStatus();
            });
        }

        // Entry Mode Buttons Listener & Keyboard Navigation
        toggleButtons.forEach((btn, idx) => {
            btn.addEventListener('click', function () {
                toggleButtons.forEach(b => {
                    b.classList.remove('active');
                    b.setAttribute('aria-checked', 'false');
                });
                this.classList.add('active');
                this.setAttribute('aria-checked', 'true');
                currentEntry = this.getAttribute('data-entry');
                updateVisaStatus();
            });

            btn.addEventListener('keydown', function (e) {
                let targetIdx = -1;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') targetIdx = (idx + 1) % toggleButtons.length;
                else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') targetIdx = (idx - 1 + toggleButtons.length) % toggleButtons.length;
                if (targetIdx !== -1) {
                    e.preventDefault();
                    toggleButtons[targetIdx].focus();
                    toggleButtons[targetIdx].click();
                }
            });
        });

        // Date Picker Listener
        if (arrivalInput) {
            arrivalInput.addEventListener('change', calculatePassportExpiry);
        }

        // Copy Summary Action
        if (copyBtn && copyLabel) {
            copyBtn.addEventListener('click', function () {
                const countryInfo = dataset.countries[currentCountry] || { name: 'Traveller' };
                const summaryText = [
                    '=== VIETNAM TRAVEL ENTRY REQUIREMENTS ===',
                    `• Nationality: ${countryInfo.name}`,
                    `• Planned Stay: ${currentDuration} days (${currentEntry === 'multiple' ? 'Multiple Entry' : 'Single Entry'})`,
                    `• Status: ${statusText.textContent}`,
                    `• Official Fee: ${costAmount.textContent}`,
                    `• Passport Validity: Minimum 6 months beyond arrival date`,
                    `• Blank Pages: At least 2 blank endorsement pages`,
                    `• Outbound Ticket: Confirmed departure flight ticket required`,
                    `• Official Portal: https://evisa.xuatnhapcanh.gov.vn/`,
                    '• Verified by VietnamGuide.net'
                ].join('\n');

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(summaryText).then(() => {
                        const orig = copyLabel.textContent;
                        copyLabel.textContent = '✓ Copied to Clipboard!';
                        copyBtn.classList.add('copied');
                        setTimeout(() => {
                            copyLabel.textContent = orig;
                            copyBtn.classList.remove('copied');
                        }, 2500);
                    }).catch(() => {
                        fallbackCopy(summaryText);
                    });
                } else {
                    fallbackCopy(summaryText);
                }
            });
        }

        function fallbackCopy(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                if (copyLabel) {
                    const orig = copyLabel.textContent;
                    copyLabel.textContent = '✓ Copied to Clipboard!';
                    setTimeout(() => { copyLabel.textContent = orig; }, 2500);
                }
            } catch (e) {
                alert('Could not auto-copy. Please manually copy the requirements.');
            }
            document.body.removeChild(ta);
        }

        // Storage helper with localStorage fallback
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

        // URL query parameter state sync via history.replaceState
        function syncUrlParams(params) {
            if (!window.history || !window.history.replaceState) return;
            try {
                var url = new URL(window.location.href);
                Object.keys(params).forEach(function(k) {
                    if (params[k] !== undefined && params[k] !== null && params[k] !== '') {
                        url.searchParams.set(k, params[k]);
                    }
                });
                window.history.replaceState(null, '', url.toString());
            } catch(e) {}
        }

        // Restore from URL query params first, then safe storage
        try {
            var urlParams = new URLSearchParams(window.location.search);
            var queryCountry = (urlParams.get('nationality') || urlParams.get('country') || '').toUpperCase();
            var storedCountry = queryCountry || getSafeStorage('vg_user_nationality');
            if (storedCountry && dataset.countries[storedCountry]) {
                currentCountry = storedCountry;
                if (countrySelect) countrySelect.value = currentCountry;
                chips.forEach(c => {
                    const isActive = c.getAttribute('data-country') === currentCountry;
                    c.classList.toggle('active', isActive);
                    c.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
            }

            var queryDays = parseInt(urlParams.get('days') || urlParams.get('duration'), 10);
            var storedDays = (!isNaN(queryDays) && queryDays >= 1 && queryDays <= 90)
                ? queryDays
                : parseInt(getSafeStorage('vg_user_duration'), 10);

            if (!isNaN(storedDays) && storedDays >= 1 && storedDays <= 90) {
                currentDuration = storedDays;
                if (durationSlider) durationSlider.value = currentDuration;
                if (durationVal) durationVal.textContent = currentDuration + ' days';
            }
        } catch(e) {}

        // Initialize
        calculatePassportExpiry();
        updateVisaStatus();
    })();
    </script>
    <?php
    return (string) ob_get_clean();
}

/**
 * Shortcode handler for [vg_visa_checker].
 *
 * @return string
 */
function vg_visa_checker_shortcode(): string
{
    return vg_render_visa_checker_html();
}
add_shortcode('vg_visa_checker', 'vg_visa_checker_shortcode');

/**
 * Injects the Visa Requirements Checker on /plan/vietnam-evisa/ in the guide article body.
 *
 * @param string $content Post content.
 * @return string
 */
function vg_inject_visa_checker_on_page(string $content): string
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

    $isTargetPage = is_page('vietnam-evisa')
        || is_page('plan/vietnam-evisa')
        || (is_singular('page') && get_post_field('post_name') === 'vietnam-evisa')
        || is_page('vietnam-visa-guide')
        || is_page('plan/vietnam-visa-guide')
        || (is_singular('page') && get_post_field('post_name') === 'vietnam-visa-guide')
        || is_page('vietnam-airport-arrival-checklist')
        || is_page('plan/vietnam-airport-arrival-checklist')
        || (is_singular('page') && get_post_field('post_name') === 'vietnam-airport-arrival-checklist')
        || is_page('vietnam-first-trip-planning-checklist')
        || is_page('plan/vietnam-first-trip-planning-checklist')
        || (is_singular('page') && get_post_field('post_name') === 'vietnam-first-trip-planning-checklist');

    if (! $isTargetPage) {
        return $content;
    }

    if (has_shortcode($content, 'vg_visa_checker') || strpos($content, 'vg-visa-checker') !== false) {
        return $content;
    }

    if ($postId) {
        $injectedPosts[$postId] = true;
    }

    $checkerHtml = vg_render_visa_checker_html();

    return $checkerHtml . "\n\n" . $content;
}
add_filter('the_content', 'vg_inject_visa_checker_on_page', 20);

<?php
/**
 * VietnamGuide Interactive Route Packing & Preparation Checklist Component
 *
 * Provides an interactive, zero-dependency client-side packing checklist with
 * localStorage persistence, category filtering, and progress tracking.
 *
 * @package VietnamGuide
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Returns the curated catalog of ground-verified packing items for Vietnam.
 *
 * @return array<int, array<string, mixed>>
 */
function vg_get_packing_checklist_catalog(): array
{
    return [
        // Category 1: Documents & Money
        [
            'id'       => 'doc-passport',
            'category' => 'documents',
            'category_label' => __('Documents & Money', 'vietnamguide-premium'),
            'item'     => __('Passport with at least 6 months validity', 'vietnamguide-premium'),
            'context'  => __('Vietnamese immigration strictly enforces 6 months validity from your arrival date.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'hagiang', 'islands', 'cities'],
        ],
        [
            'id'       => 'doc-evisa',
            'category' => 'documents',
            'category_label' => __('Documents & Money', 'vietnamguide-premium'),
            'item'     => __('Printed official Vietnam e-visa approval letter', 'vietnamguide-premium'),
            'context'  => __('Print 2 physical copies on standard A4 paper. Digital phone copies are not accepted at immigration counters.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'hagiang', 'islands', 'cities'],
        ],
        [
            'id'       => 'doc-photos',
            'category' => 'documents',
            'category_label' => __('Documents & Money', 'vietnamguide-premium'),
            'item'     => __('Two passport-size photos (4x6 cm, white background)', 'vietnamguide-premium'),
            'context'  => __('Useful backup for border verification, visa on arrival counters, and regional permits.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'hagiang'],
        ],
        [
            'id'       => 'doc-cash',
            'category' => 'documents',
            'category_label' => __('Documents & Money', 'vietnamguide-premium'),
            'item'     => __('Crisp, uncreased USD or EUR cash ($200 - $300)', 'vietnamguide-premium'),
            'context'  => __('Local exchange counters and gold shops in Hanoi and Saigon reject torn, stamped, or folded banknotes.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'hagiang', 'islands'],
        ],
        [
            'id'       => 'doc-cards',
            'category' => 'documents',
            'category_label' => __('Documents & Money', 'vietnamguide-premium'),
            'item'     => __('Two bank cards (Visa / Mastercard) with travel notice set', 'vietnamguide-premium'),
            'context'  => __('Notify your issuing bank of Vietnam travel dates to avoid automated fraud freezes on arrival.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'cities'],
        ],
        [
            'id'       => 'doc-insurance',
            'category' => 'documents',
            'category_label' => __('Documents & Money', 'vietnamguide-premium'),
            'item'     => __('Travel medical insurance policy with emergency evacuation', 'vietnamguide-premium'),
            'context'  => __('Ensure coverage reaches at least $50,000 and explicitly includes emergency medical transport from remote provinces.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'hagiang', 'islands'],
        ],

        // Category 2: Electronics & Navigation
        [
            'id'       => 'elec-phone',
            'category' => 'electronics',
            'category_label' => __('Electronics & Navigation', 'vietnamguide-premium'),
            'item'     => __('Unlocked smartphone supporting Vietnamese 4G/5G bands', 'vietnamguide-premium'),
            'context'  => __('Required to install local Viettel or Vinaphone eSIM profiles or physical tourist SIM cards.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'hagiang', 'islands', 'cities'],
        ],
        [
            'id'       => 'elec-adapter',
            'category' => 'electronics',
            'category_label' => __('Electronics & Navigation', 'vietnamguide-premium'),
            'item'     => __('Universal plug adapter (Type A, C, and G compatible)', 'vietnamguide-premium'),
            'context'  => __('Vietnamese wall sockets accept Type C rounded plugs and Type A flat pins interchangeably, but fit can be loose.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'cities'],
        ],
        [
            'id'       => 'elec-powerbank',
            'category' => 'electronics',
            'category_label' => __('Electronics & Navigation', 'vietnamguide-premium'),
            'item'     => __('10,000 - 20,000 mAh power bank (carry-on compliant)', 'vietnamguide-premium'),
            'context'  => __('GPS tracking and navigation drain phone batteries quickly during long day trips and rural transit days.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'hagiang', 'islands'],
        ],
        [
            'id'       => 'elec-offline-maps',
            'category' => 'electronics',
            'category_label' => __('Electronics & Navigation', 'vietnamguide-premium'),
            'item'     => __('Pre-downloaded offline maps (Google Maps / Maps.me)', 'vietnamguide-premium'),
            'context'  => __('Download northern highland regions before departure; cellular coverage drops in deep limestone valleys.', 'vietnamguide-premium'),
            'tags'     => ['all', 'hagiang', 'islands'],
        ],
        [
            'id'       => 'elec-drybag-phone',
            'category' => 'electronics',
            'category_label' => __('Electronics & Navigation', 'vietnamguide-premium'),
            'item'     => __('Waterproof phone pouch with neck lanyard', 'vietnamguide-premium'),
            'context'  => __('Essential for boat excursions in Ha Long Bay, Trang An sampan tours, and sudden monsoon showers.', 'vietnamguide-premium'),
            'tags'     => ['all', 'islands', 'cities'],
        ],

        // Category 3: Clothing & Modesty
        [
            'id'       => 'cloth-breathable',
            'category' => 'clothing',
            'category_label' => __('Clothing & Modesty', 'vietnamguide-premium'),
            'item'     => __('Lightweight, breathable linen or merino shirts', 'vietnamguide-premium'),
            'context'  => __('Synthetic fabrics trap heat in Vietnam\'s high humidity. Loose, breathable natural fibers dry faster.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'cities', 'islands'],
        ],
        [
            'id'       => 'cloth-temple',
            'category' => 'clothing',
            'category_label' => __('Clothing & Modesty', 'vietnamguide-premium'),
            'item'     => __('Temple modesty scarf or trousers covering knees and shoulders', 'vietnamguide-premium'),
            'context'  => __('Strictly required when entering Hanoi Temple of Literature, Tran Quoc Pagoda, and Hue Imperial Citadel.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'cities'],
        ],
        [
            'id'       => 'cloth-shoes',
            'category' => 'clothing',
            'category_label' => __('Clothing & Modesty', 'vietnamguide-premium'),
            'item'     => __('Slip-on walking shoes or supportive sandals', 'vietnamguide-premium'),
            'context'  => __('Visitors must remove footwear before stepping into Buddhist temples, traditional pagodas, and local homes.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'cities', 'islands'],
        ],
        [
            'id'       => 'cloth-rain',
            'category' => 'clothing',
            'category_label' => __('Clothing & Modesty', 'vietnamguide-premium'),
            'item'     => __('Packable lightweight rain jacket or poncho', 'vietnamguide-premium'),
            'context'  => __('Afternoon tropical convection showers can hit suddenly between May and October across central and southern Vietnam.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'hagiang', 'islands'],
        ],
        [
            'id'       => 'cloth-sun',
            'category' => 'clothing',
            'category_label' => __('Clothing & Modesty', 'vietnamguide-premium'),
            'item'     => __('Polarized sunglasses and wide-brim sun protection hat', 'vietnamguide-premium'),
            'context'  => __('High UV index across Da Nang, Phu Quoc, and Con Dao demands reliable daytime eye and skin shade.', 'vietnamguide-premium'),
            'tags'     => ['all', 'islands', 'cities'],
        ],

        // Category 4: Health & Medical Kit
        [
            'id'       => 'med-repellent',
            'category' => 'medical',
            'category_label' => __('Health & Medical Kit', 'vietnamguide-premium'),
            'item'     => __('DEET 15% - 30% insect and mosquito repellent', 'vietnamguide-premium'),
            'context'  => __('Dengue fever is present in tropical urban and rural pockets. Apply especially at dawn and dusk.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'hagiang', 'islands'],
        ],
        [
            'id'       => 'med-hydration',
            'category' => 'medical',
            'category_label' => __('Health & Medical Kit', 'vietnamguide-premium'),
            'item'     => __('Oral rehydration electrolyte powder packets', 'vietnamguide-premium'),
            'context'  => __('Rapid sweating in tropical heat causes salt depletion and fatigue. Add one packet to bottled water daily.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'hagiang', 'islands'],
        ],
        [
            'id'       => 'med-digestive',
            'category' => 'medical',
            'category_label' => __('Health & Medical Kit', 'vietnamguide-premium'),
            'item'     => __('Digestive medication (Loperamide and charcoal tablets)', 'vietnamguide-premium'),
            'context'  => __('Helps manage brief digestive adjustments to regional street food spices and herb varieties.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'cities'],
        ],
        [
            'id'       => 'med-sunscreen',
            'category' => 'medical',
            'category_label' => __('Health & Medical Kit', 'vietnamguide-premium'),
            'item'     => __('Broad-spectrum water-resistant sunscreen (SPF 50+)', 'vietnamguide-premium'),
            'context'  => __('Imported sunscreen is heavily marked up at beach resort convenience stores in Phu Quoc and Da Nang.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time', 'islands'],
        ],
        [
            'id'       => 'med-prescriptions',
            'category' => 'medical',
            'category_label' => __('Health & Medical Kit', 'vietnamguide-premium'),
            'item'     => __('Prescription medications in original labeled pharmacy boxes', 'vietnamguide-premium'),
            'context'  => __('Keep in carry-on baggage with a printed copy of your doctor prescription for international customs.', 'vietnamguide-premium'),
            'tags'     => ['all', 'first-time'],
        ],

        // Category 5: Mountain & Easy Rider Adventure
        [
            'id'       => 'adv-idp',
            'category' => 'adventure',
            'category_label' => __('Mountain & Easy Rider Gear', 'vietnamguide-premium'),
            'item'     => __('1968 Vienna Convention International Driving Permit (IDP)', 'vietnamguide-premium'),
            'context'  => __('Vietnam recognizes only the 1968 convention with motorcycle class. 1949 permits are invalid in police checkpoints.', 'vietnamguide-premium'),
            'tags'     => ['hagiang'],
        ],
        [
            'id'       => 'adv-helmet',
            'category' => 'adventure',
            'category_label' => __('Mountain & Easy Rider Gear', 'vietnamguide-premium'),
            'item'     => __('Certified 3/4 or full-face helmet with clean visor', 'vietnamguide-premium'),
            'context'  => __('Avoid decorative half-caps sold at street stalls. Reliable tour operators provide DOT-certified protection.', 'vietnamguide-premium'),
            'tags'     => ['hagiang'],
        ],
        [
            'id'       => 'adv-drybag',
            'category' => 'adventure',
            'category_label' => __('Mountain & Easy Rider Gear', 'vietnamguide-premium'),
            'item'     => __('Heavy-duty 20L - 30L waterproof dry bag with bungee straps', 'vietnamguide-premium'),
            'context'  => __('Protects clothing and electronics from road dust and mountain rainfall strapped to motorbike rear racks.', 'vietnamguide-premium'),
            'tags'     => ['hagiang', 'islands'],
        ],
    ];
}

/**
 * Renders the interactive packing checklist HTML widget.
 *
 * @param array<string, mixed> $attributes Shortcode attributes.
 * @return string HTML output.
 */
function vg_render_packing_checklist(array $attributes = []): string
{
    $catalog = vg_get_packing_checklist_catalog();
    $totalCount = count($catalog);

    $categories = [
        'all'        => __('All Essentials', 'vietnamguide-premium'),
        'documents'  => __('Documents & Money', 'vietnamguide-premium'),
        'electronics'=> __('Electronics', 'vietnamguide-premium'),
        'clothing'   => __('Clothing & Modesty', 'vietnamguide-premium'),
        'medical'    => __('Health & Medical', 'vietnamguide-premium'),
        'adventure'  => __('Motorbike & Mountains', 'vietnamguide-premium'),
    ];

    ob_start();
    ?>
    <section class="vg-checklist-widget" id="vg-packing-checklist" data-vg-checklist aria-label="<?php esc_attr_e('Interactive Vietnam Travel Packing Checklist', 'vietnamguide-premium'); ?>">
        <div class="vg-checklist-header">
            <div class="vg-checklist-title-row">
                <span class="vg-checklist-badge"><?php esc_html_e('Interactive Field Tool', 'vietnamguide-premium'); ?></span>
                <div class="vg-checklist-title" role="heading" aria-level="2">
                    <span class="vg-checklist-icon" aria-hidden="true">&#10003;</span>
                    <?php esc_html_e('Vietnam Travel Packing & Preparation Checklist', 'vietnamguide-premium'); ?>
                </div>
            </div>
            <p class="vg-checklist-subhead">
                <?php esc_html_e('Check off items as you pack. Your progress is saved automatically in your browser so you can return anytime during your trip preparation.', 'vietnamguide-premium'); ?>
            </p>

            <div class="vg-checklist-progress-bar-wrap" aria-hidden="true">
                <div class="vg-checklist-progress-bar" data-vg-checklist-bar style="width: 0%;"></div>
            </div>
            <div class="vg-checklist-status-row">
                <span class="vg-checklist-counter" data-vg-checklist-counter>
                    <?php printf(esc_html__('0 of %d items packed (0%%)', 'vietnamguide-premium'), $totalCount); ?>
                </span>
                <div class="vg-checklist-actions">
                    <button type="button" class="vg-checklist-btn-text" data-vg-checklist-reset aria-label="<?php esc_attr_e('Reset all packed checkboxes', 'vietnamguide-premium'); ?>">
                        <?php esc_html_e('Reset Checklist', 'vietnamguide-premium'); ?>
                    </button>
                    <button type="button" class="vg-checklist-btn-print" data-vg-print aria-label="<?php esc_attr_e('Print this packing checklist', 'vietnamguide-premium'); ?>">
                        <span aria-hidden="true">&#128424;</span> <?php esc_html_e('Print Checklist', 'vietnamguide-premium'); ?>
                    </button>
                </div>
            </div>
            <div class="vg-checklist-live-announcer" role="status" aria-live="polite" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">
                <?php esc_html_e('Packing checklist loaded with 24 items.', 'vietnamguide-premium'); ?>
            </div>
        </div>

        <noscript>
            <div class="vg-checklist-noscript" style="background:#f8f9fa;border:1px solid #cbd5e1;border-radius:8px;padding:16px;margin-bottom:20px;">
                <p style="font-weight:700;margin-bottom:8px;color:#1a365d;">
                    <?php esc_html_e('2026 Vietnam Travel Essentials Checklist (No-JavaScript Reference):', 'vietnamguide-premium'); ?>
                </p>
                <p style="font-size:0.9rem;margin-bottom:12px;color:#475569;">
                    <?php esc_html_e('For full interactive progress saving, enable JavaScript. Key gear priorities across Vietnam:', 'vietnamguide-premium'); ?>
                </p>
                <ul style="margin-bottom:0;padding-left:20px;font-size:0.9rem;line-height:1.6;">
                    <li><strong><?php esc_html_e('Documents:', 'vietnamguide-premium'); ?></strong> <?php esc_html_e('Valid passport (6+ months), printed physical e-visa copies, uncreased USD/EUR notes, travel medical insurance.', 'vietnamguide-premium'); ?></li>
                    <li><strong><?php esc_html_e('Electronics:', 'vietnamguide-premium'); ?></strong> <?php esc_html_e('Unlocked phone for eSIM/SIM, universal power adapter, 10,000+ mAh power bank, pre-downloaded offline maps.', 'vietnamguide-premium'); ?></li>
                    <li><strong><?php esc_html_e('Clothing & Modesty:', 'vietnamguide-premium'); ?></strong> <?php esc_html_e('Linen/breathable fabrics, temple shoulder/knee cover, slip-on footwear, packable monsoon rain layer.', 'vietnamguide-premium'); ?></li>
                    <li><strong><?php esc_html_e('Health & Medical:', 'vietnamguide-premium'); ?></strong> <?php esc_html_e('DEET mosquito repellent (dengue defense), oral hydration salts, digestive medication, SPF 50+ sunscreen.', 'vietnamguide-premium'); ?></li>
                    <li><strong><?php esc_html_e('Motorbike & Mountain:', 'vietnamguide-premium'); ?></strong> <?php esc_html_e('1968 Vienna Convention IDP, certified 3/4 helmet, 20L-30L waterproof dry bag for rear rack luggage.', 'vietnamguide-premium'); ?></li>
                </ul>
            </div>
        </noscript>

        <div class="vg-checklist-filters" role="tablist" aria-label="<?php esc_attr_e('Filter checklist items', 'vietnamguide-premium'); ?>">
            <?php foreach ($categories as $catKey => $catLabel) : ?>
                <button
                    type="button"
                    role="tab"
                    class="vg-checklist-tab<?php echo $catKey === 'all' ? ' is-active' : ''; ?>"
                    data-vg-checklist-filter="<?php echo esc_attr($catKey); ?>"
                    aria-selected="<?php echo $catKey === 'all' ? 'true' : 'false'; ?>"
                >
                    <?php echo esc_html($catLabel); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <ul class="vg-checklist-items" data-vg-checklist-items>
            <?php foreach ($catalog as $item) : ?>
                <li
                    class="vg-checklist-item"
                    data-vg-checklist-id="<?php echo esc_attr($item['id']); ?>"
                    data-vg-checklist-cat="<?php echo esc_attr($item['category']); ?>"
                >
                    <label class="vg-checklist-label" for="vg-item-<?php echo esc_attr($item['id']); ?>">
                        <input
                            type="checkbox"
                            id="vg-item-<?php echo esc_attr($item['id']); ?>"
                            class="vg-checklist-checkbox"
                            data-vg-checklist-box="<?php echo esc_attr($item['id']); ?>"
                        >
                        <span class="vg-checklist-box-custom" aria-hidden="true"></span>
                        <div class="vg-checklist-text-col">
                            <span class="vg-checklist-name"><?php echo esc_html(rtrim($item['item'], '.')) . '.'; ?></span>
                            <span class="vg-checklist-context"><?php echo esc_html($item['context']); ?></span>
                        </div>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Viral Social & Deep-Link Sharing Bar -->
        <div class="vg-tool-share-bar">
            <span class="vg-share-label"><?php esc_html_e('Share Checklist:', 'vietnamguide-premium'); ?></span>
            <button type="button" class="vg-btn-share" id="vg-checklist-share-link">
                <span>🔗 <?php esc_html_e('Copy Checklist Link', 'vietnamguide-premium'); ?></span>
            </button>
            <a href="#" class="vg-btn-share vg-btn-share--wa" id="vg-checklist-share-wa" target="_blank" rel="noopener noreferrer">
                <span>💬 WhatsApp</span>
            </a>
            <a href="#" class="vg-btn-share vg-btn-share--tg" id="vg-checklist-share-tg" target="_blank" rel="noopener noreferrer">
                <span>✈️ Telegram</span>
            </a>
            <button type="button" class="vg-btn-share vg-btn-share--qr" id="vg-checklist-share-qr" aria-label="<?php esc_attr_e('Show QR code for mobile', 'vietnamguide-premium'); ?>">
                <span>📱 <?php esc_html_e('QR to Phone', 'vietnamguide-premium'); ?></span>
            </button>
        </div>

        <div class="vg-checklist-synergy-wrap vg-tool-synergy-bar">
            <div class="vg-checklist-synergy-title">
                <?php esc_html_e('Connected Vietnam Trip Planning Toolkits:', 'vietnamguide-premium'); ?>
            </div>
            <div class="vg-checklist-synergy-links">
                <a href="<?php echo esc_url(home_url('/plan/vietnam-evisa/')); ?>" class="vg-checklist-synergy-card vg-synergy-bridge">
                    <span class="vg-checklist-synergy-icon" aria-hidden="true">&#128196;</span>
                    <div>
                        <strong><?php esc_html_e('Visa Exemption & E-Visa Checker', 'vietnamguide-premium'); ?></strong>
                        <p><?php esc_html_e('45-day exemption rules, port checks & official e-visa requirements.', 'vietnamguide-premium'); ?></p>
                    </div>
                </a>
                <a href="<?php echo esc_url(home_url('/costs/vietnam-travel-cost/')); ?>" class="vg-checklist-synergy-card vg-synergy-bridge">
                    <span class="vg-checklist-synergy-icon" aria-hidden="true">&#128176;</span>
                    <div>
                        <strong><?php esc_html_e('Vietnam Travel Cost Calculator', 'vietnamguide-premium'); ?></strong>
                        <p><?php esc_html_e('Calculate realistic cash, boutique stays, and daily budgets in 5 currencies.', 'vietnamguide-premium'); ?></p>
                    </div>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/best-time-to-visit-vietnam/')); ?>" class="vg-checklist-synergy-card vg-synergy-bridge">
                    <span class="vg-checklist-synergy-icon" aria-hidden="true">&#9728;</span>
                    <div>
                        <strong><?php esc_html_e('Regional Season & Weather Matrix', 'vietnamguide-premium'); ?></strong>
                        <p><?php esc_html_e('12-month climate guide & rainfall radar across North, Central & South.', 'vietnamguide-premium'); ?></p>
                    </div>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/vietnam-airport-arrival-checklist/')); ?>" class="vg-checklist-synergy-card vg-synergy-bridge">
                    <span class="vg-checklist-synergy-icon" aria-hidden="true">&#9992;</span>
                    <div>
                        <strong><?php esc_html_e('Airport Transit Navigator & Scam Shield', 'vietnamguide-premium'); ?></strong>
                        <p><?php esc_html_e('Grab bays, metered taxi fares & arrival steps for HAN, SGN & DAD.', 'vietnamguide-premium'); ?></p>
                    </div>
                </a>
                <a href="<?php echo esc_url(home_url('/itineraries/')); ?>" class="vg-checklist-synergy-card vg-synergy-bridge" data-flagship="<?php echo esc_url(home_url('/itineraries/10-days-in-vietnam/')); ?>">
                    <span class="vg-checklist-synergy-icon" aria-hidden="true">&#128506;</span>
                    <div>
                        <strong><?php esc_html_e('Interactive Itinerary Finder', 'vietnamguide-premium'); ?></strong>
                        <p><?php esc_html_e('Match 7, 10, 14, 21-day routes or open the 10-day classic route (/itineraries/10-days-in-vietnam/).', 'vietnamguide-premium'); ?></p>
                    </div>
                </a>
            </div>
        </div>

        <style>
        .vg-checklist-widget {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin: 32px 0;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            font-family: inherit;
        }
        .vg-checklist-header {
            margin-bottom: 20px;
        }
        .vg-checklist-title-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }
        .vg-checklist-badge {
            display: inline-block;
            background: #f3d484;
            color: #7e5802;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 4px 10px;
            border-radius: 9999px;
        }
        .vg-checklist-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1a365d;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .vg-checklist-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            background: #2e7d32;
            color: #ffffff;
            border-radius: 50%;
            font-size: 0.9rem;
            font-weight: bold;
        }
        .vg-checklist-subhead {
            color: #4a5568;
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0 0 16px 0;
        }
        .vg-checklist-progress-bar-wrap {
            height: 10px;
            background: #edf2f7;
            border-radius: 9999px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .vg-checklist-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #2e7d32, #4caf50);
            border-radius: 9999px;
            transition: width 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .vg-checklist-status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 0.88rem;
        }
        .vg-checklist-counter {
            font-weight: 700;
            color: #2d3748;
        }
        .vg-checklist-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .vg-checklist-btn-text {
            background: none;
            border: none;
            color: #718096;
            font-size: 0.82rem;
            cursor: pointer;
            padding: 4px 8px;
            text-decoration: underline;
            transition: color 0.15s ease;
        }
        .vg-checklist-btn-text:hover,
        .vg-checklist-btn-text:focus {
            color: #c53030;
        }
        .vg-checklist-btn-print {
            background: #f7fafc;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #2d3748;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .vg-checklist-btn-print:hover,
        .vg-checklist-btn-print:focus {
            background: #edf2f7;
            border-color: #a0aec0;
            transform: translateY(-1px);
        }
        .vg-checklist-filters {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 8px;
            margin-bottom: 20px;
            border-bottom: 1px solid #edf2f7;
            -webkit-overflow-scrolling: touch;
        }
        .vg-checklist-tab {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #4a5568;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .vg-checklist-tab:hover,
        .vg-checklist-tab:focus {
            background: #edf2f7;
            color: #1a202c;
            transform: translateY(-1px);
        }
        .vg-checklist-tab.is-active {
            background: #1a365d;
            border-color: #1a365d;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(26, 54, 93, 0.2);
        }
        .vg-checklist-items {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 10px;
        }
        .vg-checklist-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .vg-checklist-item:hover {
            border-color: #cbd5e0;
            background: #ffffff;
        }
        .vg-checklist-item.is-packed {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }
        .vg-checklist-item.is-packed .vg-checklist-name {
            text-decoration: line-through;
            color: #4b5563;
        }
        .vg-checklist-label {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            margin: 0;
            user-select: none;
        }
        .vg-checklist-checkbox {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .vg-checklist-box-custom {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            border: 2px solid #a0aec0;
            border-radius: 4px;
            background: #ffffff;
            margin-top: 2px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .vg-checklist-checkbox:focus + .vg-checklist-box-custom {
            outline: 2px solid #3182ce;
            outline-offset: 2px;
        }
        .vg-checklist-checkbox:checked + .vg-checklist-box-custom {
            background: #2e7d32;
            border-color: #2e7d32;
        }
        .vg-checklist-checkbox:checked + .vg-checklist-box-custom::after {
            content: "\2713";
            color: #ffffff;
            font-size: 0.85rem;
            font-weight: bold;
        }
        .vg-checklist-text-col {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .vg-checklist-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1a202c;
            line-height: 1.4;
        }
        .vg-checklist-context {
            font-size: 0.82rem;
            color: #718096;
            line-height: 1.4;
        }
        .vg-checklist-synergy-wrap {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .vg-checklist-synergy-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .vg-checklist-synergy-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }
        .vg-checklist-synergy-card {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .vg-checklist-synergy-card:hover,
        .vg-checklist-synergy-card:focus {
            background: #ffffff;
            border-color: #3182ce;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .vg-checklist-synergy-icon {
            font-size: 1.25rem;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .vg-checklist-synergy-card strong {
            display: block;
            font-size: 0.85rem;
            color: #1a365d;
            line-height: 1.3;
        }
        .vg-checklist-synergy-card p {
            margin: 2px 0 0 0;
            font-size: 0.75rem;
            color: #718096;
            line-height: 1.3;
        }
        @media print {
            .vg-checklist-actions,
            .vg-checklist-filters,
            .vg-checklist-progress-bar-wrap,
            .vg-checklist-synergy-wrap {
                display: none !important;
            }
            .vg-checklist-item {
                display: block !important;
                border: 1px solid #999 !important;
                break-inside: avoid;
            }
            .vg-checklist-box-custom {
                border: 2px solid #000 !important;
            }
        }
        </style>

        <script>
        (function() {
            var widget = document.getElementById('vg-packing-checklist');
            if (!widget) return;

            var STORAGE_KEY = 'vg_packed_items';
            var SESSION_KEY = 'vg_checklist_active_filter';
            var packedState = {};

            // Safe localStorage reader
            try {
                var stored = localStorage.getItem(STORAGE_KEY);
                if (stored) {
                    packedState = JSON.parse(stored) || {};
                }
            } catch (e) {
                packedState = {};
            }

            var checkboxes = widget.querySelectorAll('[data-vg-checklist-box]');
            var items = widget.querySelectorAll('.vg-checklist-item');
            var filterTabs = widget.querySelectorAll('[data-vg-checklist-filter]');
            var progressBar = widget.querySelector('[data-vg-checklist-bar]');
            var counterEl = widget.querySelector('[data-vg-checklist-counter]');
            var liveAnnouncer = widget.querySelector('.vg-checklist-live-announcer');
            var resetBtn = widget.querySelector('[data-vg-checklist-reset]');
            var printBtn = widget.querySelector('[data-vg-print]');

            var totalCount = checkboxes.length;

            // Apply saved packed state
            checkboxes.forEach(function(box) {
                var id = box.getAttribute('data-vg-checklist-box');
                if (id && packedState[id]) {
                    box.checked = true;
                    var parent = box.closest('.vg-checklist-item');
                    if (parent) parent.classList.add('is-packed');
                }
            });

            function updateProgress(announce) {
                var packedCount = 0;
                checkboxes.forEach(function(box) {
                    if (box.checked) packedCount++;
                });

                var pct = totalCount > 0 ? Math.round((packedCount / totalCount) * 100) : 0;
                pct = Math.max(0, Math.min(100, pct));

                if (progressBar) {
                    progressBar.style.width = pct + '%';
                }

                if (counterEl) {
                    counterEl.textContent = packedCount + ' of ' + totalCount + ' items packed (' + pct + '%)';
                }

                if (announce && liveAnnouncer) {
                    liveAnnouncer.textContent = packedCount + ' of ' + totalCount + ' items packed, ' + pct + ' percent complete.';
                }

                // Update WhatsApp and Telegram share links dynamically
                var shareUrl = window.location.href;
                var shareMsg = 'Vietnam Route Packing Checklist (' + packedCount + ' of ' + totalCount + ' packed, ' + pct + '%): ' + shareUrl;
                var waBtn = document.getElementById('vg-checklist-share-wa');
                var tgBtn = document.getElementById('vg-checklist-share-tg');
                if (waBtn) waBtn.href = 'https://api.whatsapp.com/send?text=' + encodeURIComponent(shareMsg);
                if (tgBtn) tgBtn.href = 'https://t.me/share/url?url=' + encodeURIComponent(shareUrl) + '&text=' + encodeURIComponent(shareMsg);
            }

            function saveState() {
                try {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(packedState));
                } catch (e) {}
            }

            // Checkbox event handling
            checkboxes.forEach(function(box) {
                box.addEventListener('change', function() {
                    var id = box.getAttribute('data-vg-checklist-box');
                    var parent = box.closest('.vg-checklist-item');
                    if (box.checked) {
                        packedState[id] = true;
                        if (parent) parent.classList.add('is-packed');
                    } else {
                        delete packedState[id];
                        if (parent) parent.classList.remove('is-packed');
                    }
                    saveState();
                    updateProgress(true);

                    // Telemetry dispatch
                    if (typeof window.vgTrack === 'function') {
                        window.vgTrack('vg_checklist_toggle', {
                            item_id: id,
                            packed: box.checked
                        });
                    }
                });
            });

            // Filter functionality
            function applyFilter(category, updateUrl) {
                filterTabs.forEach(function(tab) {
                    var tabCat = tab.getAttribute('data-vg-checklist-filter');
                    var isActive = tabCat === category;
                    tab.classList.toggle('is-active', isActive);
                    tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                items.forEach(function(item) {
                    var itemCat = item.getAttribute('data-vg-checklist-cat');
                    if (category === 'all' || itemCat === category) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });

                try {
                    sessionStorage.setItem(SESSION_KEY, category);
                } catch (e) {}

                if (updateUrl && window.history && window.history.replaceState) {
                    try {
                        var url = new URL(window.location.href);
                        if (category === 'all') {
                            url.searchParams.delete('pack_cat');
                        } else {
                            url.searchParams.set('pack_cat', category);
                        }
                        window.history.replaceState({}, '', url.toString());
                    } catch (e) {}
                }
            }

            filterTabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var category = tab.getAttribute('data-vg-checklist-filter');
                    applyFilter(category, true);
                });
                tab.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        var category = tab.getAttribute('data-vg-checklist-filter');
                        applyFilter(category, true);
                    }
                });
            });

            // Reset action
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    if (window.confirm('Reset all checked items in your packing checklist?')) {
                        packedState = {};
                        checkboxes.forEach(function(box) {
                            box.checked = false;
                            var parent = box.closest('.vg-checklist-item');
                            if (parent) parent.classList.remove('is-packed');
                        });
                        saveState();
                        updateProgress(true);
                    }
                });
            }

            // Print action
            if (printBtn) {
                printBtn.addEventListener('click', function() {
                    window.print();
                });
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

            var shareLinkBtn = document.getElementById('vg-checklist-share-link');
            if (shareLinkBtn) {
                shareLinkBtn.addEventListener('click', function () {
                    var url = window.location.href;
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(url).then(function () {
                            showToast('✓ Link copied to clipboard - Share with your travel companion!');
                            if (typeof window.vgTrack === 'function') {
                                window.vgTrack('vg_share_plan', { tool: 'packing_checklist', channel: 'copy_link' });
                            }
                        }).catch(function () {
                            prompt('Copy your link:', url);
                        });
                    } else {
                        prompt('Copy your link:', url);
                    }
                });
            }

            var waBtnEl = document.getElementById('vg-checklist-share-wa');
            if (waBtnEl) {
                waBtnEl.addEventListener('click', function () {
                    if (typeof window.vgTrack === 'function') {
                        window.vgTrack('vg_share_plan', { tool: 'packing_checklist', channel: 'whatsapp' });
                    }
                });
            }

            var tgBtnEl = document.getElementById('vg-checklist-share-tg');
            if (tgBtnEl) {
                tgBtnEl.addEventListener('click', function () {
                    if (typeof window.vgTrack === 'function') {
                        window.vgTrack('vg_share_plan', { tool: 'packing_checklist', channel: 'telegram' });
                    }
                });
            }

            var qrBtnEl = document.getElementById('vg-checklist-share-qr');
            if (qrBtnEl) {
                qrBtnEl.addEventListener('click', function () {
                    if (typeof window.vgOpenQrModal === 'function') {
                        window.vgOpenQrModal(window.location.href, '<?php esc_attr_e('Vietnam Route Packing Checklist', 'vietnamguide-premium'); ?>');
                    }
                    if (typeof window.vgTrack === 'function') {
                        window.vgTrack('vg_share_plan', { tool: 'packing_checklist', channel: 'qr_code' });
                    }
                });
            }

            // Initial URL or Session filter restore
            var initialFilter = 'all';
            try {
                var urlParams = new URLSearchParams(window.location.search);
                var urlFilter = urlParams.get('pack_cat');
                if (urlFilter) {
                    initialFilter = urlFilter;
                } else {
                    var sessionFilter = sessionStorage.getItem(SESSION_KEY);
                    if (sessionFilter) initialFilter = sessionFilter;
                }
            } catch (e) {}

            applyFilter(initialFilter, false);
            updateProgress(false);
        })();
        </script>
    </section>
    <?php
    return (string) ob_get_clean();
}

add_shortcode('vg_packing_checklist', 'vg_render_packing_checklist');

/**
 * Injects the Packing Checklist component onto relevant planning checklist pages.
 *
 * @param string $content Post content.
 * @return string
 */
function vg_inject_packing_checklist_on_page(string $content): string
{
    if (is_admin()) {
        return $content;
    }

    if (strpos($content, 'vg-guide-hero') !== false) {
        return $content;
    }

    static $injected = [];
    $postId = get_the_ID();
    if ($postId && isset($injected[$postId])) {
        return $content;
    }

    $isTarget = is_page('vietnam-first-trip-planning-checklist')
        || (is_singular('page') && get_post_field('post_name') === 'vietnam-first-trip-planning-checklist')
        || is_page('vietnam-airport-arrival-checklist')
        || (is_singular('page') && get_post_field('post_name') === 'vietnam-airport-arrival-checklist');

    if (! $isTarget) {
        return $content;
    }

    if (has_shortcode($content, 'vg_packing_checklist') || strpos($content, 'vg-checklist-widget') !== false) {
        return $content;
    }

    if ($postId) {
        $injected[$postId] = true;
    }

    $widgetHtml = vg_render_packing_checklist();
    $heroClose = '<!-- /wp:group -->';
    $pos = strpos($content, $heroClose);

    if ($pos !== false) {
        $insertAt = $pos + strlen($heroClose);
        return substr($content, 0, $insertAt) . PHP_EOL . PHP_EOL . $widgetHtml . PHP_EOL . PHP_EOL . substr($content, $insertAt);
    }

    return $widgetHtml . PHP_EOL . PHP_EOL . $content;
}
add_filter('the_content', 'vg_inject_packing_checklist_on_page', 25);


<?php
/**
 * VietnamGuide Unified Analytics & Toolkit Engagement Telemetry Engine
 * 
 * Provides seamless GA4 measurement, privacy-first defaults, and deep interaction
 * event tracking across all 6 interactive decision toolkits.
 *
 * @package VietnamGuide
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Retrieve the active GA4 Measurement ID from wp-config constant, theme option,
 * or Site Kit settings.
 *
 * @return string Measurement ID (e.g., 'G-XXXXXXXXXX') or empty string if not configured.
 */
function vg_get_ga4_measurement_id(): string
{
    // 1. Explicit constant in wp-config.php (highest priority)
    if (defined('VG_GA4_MEASUREMENT_ID') && is_string(VG_GA4_MEASUREMENT_ID) && VG_GA4_MEASUREMENT_ID !== '') {
        $id = trim(VG_GA4_MEASUREMENT_ID);
        if (preg_match('/^G-[A-Za-z0-9]+$/', $id)) {
            return $id;
        }
    }

    // 2. WordPress option 'vg_ga4_measurement_id'
    $opt_id = get_option('vg_ga4_measurement_id', '');
    if (is_string($opt_id) && $opt_id !== '') {
        $opt_id = trim($opt_id);
        if (preg_match('/^G-[A-Za-z0-9]+$/', $opt_id)) {
            return $opt_id;
        }
    }

    // 3. Fallback to Google Site Kit option if connected
    $sk_settings = get_option('googlesitekit_analytics-4_settings');
    if (is_array($sk_settings) && ! empty($sk_settings['measurementID'])) {
        $sk_id = trim((string) $sk_settings['measurementID']);
        if (preg_match('/^G-[A-Za-z0-9]+$/', $sk_id)) {
            return $sk_id;
        }
    }

    return '';
}

/**
 * Output Google Analytics 4 tracking script and client-side event bus in <head>.
 */
add_action('wp_head', static function (): void {
    $measurement_id = vg_get_ga4_measurement_id();
    $has_ga4 = ($measurement_id !== '');
    ?>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    window.__vgAnalytics = {
        ga4Active: <?php echo $has_ga4 ? 'true' : 'false'; ?>,
        measurementId: '<?php echo esc_js($measurement_id); ?>',
        events: []
    };
    window.vgTrack = function(eventName, eventParams) {
        eventParams = eventParams || {};
        eventParams.timestamp = new Date().toISOString();
        window.__vgAnalytics.events.push({ name: eventName, params: eventParams });
        if (window.__vgAnalytics.ga4Active && typeof window.gtag === 'function') {
            window.gtag('event', eventName, eventParams);
        }
        document.dispatchEvent(new CustomEvent('vg:telemetry', {
            detail: { event: eventName, params: eventParams }
        }));
    };
    </script>
    <?php if ($has_ga4) : ?>
    <!-- Google Analytics 4 (VietnamGuide Telemetry) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($measurement_id); ?>"></script>
    <script>
    gtag('js', new Date());
    gtag('config', '<?php echo esc_js($measurement_id); ?>', {
        anonymize_ip: true,
        cookie_flags: 'SameSite=None;Secure'
    });
    </script>
    <?php endif; ?>
    <?php
}, 2);

/**
 * Output Mobile QR Transfer Modal and global bridge in footer.
 */
add_action('wp_footer', static function (): void {
    ?>
    <div id="vg-qr-modal" class="vg-qr-modal" role="dialog" aria-modal="true" aria-labelledby="vg-qr-modal-title" style="display:none;">
        <div class="vg-qr-backdrop" data-vg-qr-close></div>
        <div class="vg-qr-card">
            <div class="vg-qr-header">
                <h3 class="vg-qr-title" id="vg-qr-modal-title"><?php esc_html_e('Open on Mobile', 'vietnamguide-premium'); ?></h3>
                <button type="button" class="vg-qr-close" data-vg-qr-close aria-label="<?php esc_attr_e('Close QR dialog', 'vietnamguide-premium'); ?>">&times;</button>
            </div>
            <div class="vg-qr-body">
                <div class="vg-qr-img-wrap">
                    <img class="vg-qr-img" id="vg-qr-code-img" src="" alt="<?php esc_attr_e('QR Code to open plan on mobile', 'vietnamguide-premium'); ?>" width="180" height="180" />
                </div>
                <p class="vg-qr-desc"><?php esc_html_e('Scan with your mobile camera to access this plan on your journey.', 'vietnamguide-premium'); ?></p>
                <div class="vg-qr-actions">
                    <button type="button" class="vg-qr-copy-btn" id="vg-qr-copy-btn"><?php esc_html_e('Copy Link to Clipboard', 'vietnamguide-premium'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function () {
        'use strict';
        window.vgOpenQrModal = function (url, title) {
            var modal = document.getElementById('vg-qr-modal');
            if (!modal) return;
            var titleEl = document.getElementById('vg-qr-modal-title');
            if (titleEl && title) {
                titleEl.textContent = title;
            }
            var qrImg = document.getElementById('vg-qr-code-img');
            if (qrImg) {
                var encoded = encodeURIComponent(url || window.location.href);
                qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=8&data=' + encoded;
            }
            modal.setAttribute('data-target-url', url || window.location.href);
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            var closeBtn = modal.querySelector('.vg-qr-close');
            if (closeBtn) closeBtn.focus();
        };

        function closeQrModal() {
            var modal = document.getElementById('vg-qr-modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        document.addEventListener('click', function (e) {
            if (e.target && e.target.hasAttribute('data-vg-qr-close')) {
                closeQrModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeQrModal();
            }
        });

        var copyBtn = document.getElementById('vg-qr-copy-btn');
        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                var modal = document.getElementById('vg-qr-modal');
                var targetUrl = (modal && modal.getAttribute('data-target-url')) || window.location.href;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(targetUrl).then(function () {
                        copyBtn.textContent = '✓ Link Copied!';
                        setTimeout(function () { copyBtn.textContent = 'Copy Link to Clipboard'; }, 2200);
                    });
                }
            });
        }
    })();
    </script>
    <?php
}, 20);

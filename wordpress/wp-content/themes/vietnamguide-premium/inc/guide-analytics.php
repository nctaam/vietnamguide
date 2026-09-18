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

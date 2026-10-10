<?php
if (! defined('ABSPATH')) {
    exit;
}
$footer_data = vg_homepage_data();
?>
<footer class="vg-site-footer">
    <div class="vg-site-footer__inner">
        <div class="vg-site-footer__brand">
            <a class="vg-wordmark" href="<?php echo esc_url(home_url('/')); ?>">VietnamGuide.net</a>
            <p><?php esc_html_e('Independent travel intelligence for Vietnam.', 'vietnamguide-premium'); ?></p>
        </div>
        <nav class="vg-site-footer__col" aria-label="<?php esc_attr_e('Footer navigation', 'vietnamguide-premium'); ?>">
            <span class="vg-footer-heading"><?php esc_html_e('Quick Access', 'vietnamguide-premium'); ?></span>
            <?php
            wp_nav_menu([
                'theme_location' => 'footer',
                'container'      => false,
                'menu_class'     => 'vg-footer-links',
                'fallback_cb'    => static function () use ($footer_data): void {
                    echo '<ul class="vg-footer-links">';
                    foreach ($footer_data['navigation'] as $item) {
                        printf(
                            '<li><a href="%1$s">%2$s</a></li>',
                            esc_url($item['url']),
                            esc_html($item['label'])
                        );
                    }
                    echo '</ul>';
                },
            ]);
            ?>
        </nav>
        <nav class="vg-site-footer__col" aria-label="<?php esc_attr_e('Editorial standards and legal', 'vietnamguide-premium'); ?>">
            <span class="vg-footer-heading"><?php esc_html_e('Standards & Legal', 'vietnamguide-premium'); ?></span>
            <ul class="vg-footer-links vg-footer-links--legal">
                <li><a href="<?php echo esc_url(vg_home_url('about')); ?>"><?php esc_html_e('About', 'vietnamguide-premium'); ?></a></li>
                <li><a href="<?php echo esc_url(vg_home_url('contact')); ?>"><?php esc_html_e('Contact', 'vietnamguide-premium'); ?></a></li>
                <li><a href="<?php echo esc_url(vg_home_url('privacy-policy')); ?>"><?php esc_html_e('Privacy policy', 'vietnamguide-premium'); ?></a></li>
                <li><a href="<?php echo esc_url(vg_home_url('terms-of-service')); ?>"><?php esc_html_e('Terms of service', 'vietnamguide-premium'); ?></a></li>
                <li><a href="<?php echo esc_url(vg_home_url('affiliate-disclosure')); ?>"><?php esc_html_e('Affiliate disclosure', 'vietnamguide-premium'); ?></a></li>
                <li><a href="<?php echo esc_url(vg_home_url('source-update-policy')); ?>"><?php esc_html_e('Source & Update Policy', 'vietnamguide-premium'); ?></a></li>
            </ul>
        </nav>
    </div>
    <p class="vg-site-footer__copyright">
        &copy; <?php echo esc_html(wp_date('Y')); ?> VietnamGuide.net
    </p>
</footer>
<button class="vg-back-to-top" type="button" aria-label="<?php esc_attr_e('Back to top', 'vietnamguide-premium'); ?>" data-vg-back-to-top>
    <span aria-hidden="true">&uarr;</span>
</button>
<div id="vg-cookie-consent" class="vg-cookie-banner" role="dialog" aria-label="<?php esc_attr_e('Cookie and privacy notice', 'vietnamguide-premium'); ?>" style="display:none;">
    <div class="vg-cookie-banner__inner">
        <p class="vg-cookie-banner__text">
            <?php esc_html_e('We use essential cookies and advertising cookies to support independent travel research and measure site usage in accordance with our', 'vietnamguide-premium'); ?>
            <a href="<?php echo esc_url(vg_home_url('privacy-policy')); ?>" class="vg-cookie-banner__link"><?php esc_html_e('Privacy Policy', 'vietnamguide-premium'); ?></a>.
        </p>
        <div class="vg-cookie-banner__actions">
            <button id="vg-cookie-accept" type="button" class="vg-cookie-banner__btn vg-cookie-banner__btn--accept">
                <?php esc_html_e('Accept all', 'vietnamguide-premium'); ?>
            </button>
            <button id="vg-cookie-decline" type="button" class="vg-cookie-banner__btn vg-cookie-banner__btn--decline">
                <?php esc_html_e('Essential only', 'vietnamguide-premium'); ?>
            </button>
        </div>
    </div>
</div>
<script>
(function() {
    try {
        var consent = localStorage.getItem('vg_cookie_consent');
        var b = document.getElementById('vg-cookie-consent');
        if (!consent && b) {
            b.style.display = 'block';
            var acceptBtn = document.getElementById('vg-cookie-accept');
            var declineBtn = document.getElementById('vg-cookie-decline');
            function closeBanner() {
                b.style.opacity = '0';
                b.style.transform = 'translateY(16px)';
                setTimeout(function() { b.style.display = 'none'; }, 300);
            }
            if (acceptBtn) {
                acceptBtn.addEventListener('click', function() {
                    localStorage.setItem('vg_cookie_consent', 'accepted');
                    if (typeof gtag === 'function') {
                        gtag('consent', 'update', {
                            'ad_storage': 'granted',
                            'ad_user_data': 'granted',
                            'ad_personalization': 'granted',
                            'analytics_storage': 'granted'
                        });
                    }
                    document.dispatchEvent(new CustomEvent('vg:consent', { detail: { status: 'accepted' } }));
                    closeBanner();
                });
            }
            if (declineBtn) {
                declineBtn.addEventListener('click', function() {
                    localStorage.setItem('vg_cookie_consent', 'declined');
                    if (typeof gtag === 'function') {
                        gtag('consent', 'update', {
                            'ad_storage': 'denied',
                            'ad_user_data': 'denied',
                            'ad_personalization': 'denied',
                            'analytics_storage': 'denied'
                        });
                    }
                    document.dispatchEvent(new CustomEvent('vg:consent', { detail: { status: 'declined' } }));
                    closeBanner();
                });
            }
        }
    } catch(e) {}
})();
</script>
<?php wp_footer(); ?>
</body>
</html>

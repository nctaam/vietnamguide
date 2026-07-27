<?php $footer_data = vg_homepage_data(); ?>
<footer class="vg-site-footer">
    <div class="vg-site-footer__inner">
        <div class="vg-site-footer__brand">
            <a class="vg-wordmark" href="<?php echo esc_url(home_url('/')); ?>">VietnamGuide.net</a>
            <p><?php esc_html_e('Choose Vietnam well.', 'vietnamguide-premium'); ?></p>
        </div>
        <nav aria-label="<?php esc_attr_e('Footer navigation', 'vietnamguide-premium'); ?>">
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
        <nav aria-label="<?php esc_attr_e('Trust and legal', 'vietnamguide-premium'); ?>">
            <ul class="vg-footer-links vg-footer-links--legal">
                <li><a href="<?php echo esc_url(vg_home_url('about')); ?>"><?php esc_html_e('About', 'vietnamguide-premium'); ?></a></li>
                <li><a href="<?php echo esc_url(vg_home_url('contact')); ?>"><?php esc_html_e('Contact', 'vietnamguide-premium'); ?></a></li>
                <li><a href="<?php echo esc_url(vg_home_url('affiliate-disclosure')); ?>"><?php esc_html_e('Affiliate disclosure', 'vietnamguide-premium'); ?></a></li>
                <li><a href="<?php echo esc_url(vg_home_url('source-policy')); ?>"><?php esc_html_e('Source policy', 'vietnamguide-premium'); ?></a></li>
            </ul>
        </nav>
    </div>
    <p class="vg-site-footer__copyright">
        &copy; <?php echo esc_html(wp_date('Y')); ?> VietnamGuide.net
    </p>
</footer>
<?php wp_footer(); ?>
</body>
</html>

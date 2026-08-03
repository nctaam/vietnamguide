<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('vg-site'); ?>>
<?php wp_body_open(); ?>
<a class="vg-skip-link" href="#main"><?php esc_html_e('Skip to content', 'vietnamguide-premium'); ?></a>
<header class="vg-site-header" data-vg-header>
    <div class="vg-site-header__inner">
        <a class="vg-wordmark" href="<?php echo esc_url(home_url('/')); ?>" rel="home">VietnamGuide.net</a>
        <button
            class="vg-menu-toggle"
            type="button"
            aria-controls="vg-primary-navigation"
            aria-expanded="false"
            data-vg-menu-toggle
        >
            <span class="vg-menu-toggle__label"><?php esc_html_e('Menu', 'vietnamguide-premium'); ?></span>
            <span class="vg-menu-toggle__icon" aria-hidden="true"></span>
        </button>
        <nav
            id="vg-primary-navigation"
            class="vg-primary-navigation"
            aria-label="<?php esc_attr_e('Primary navigation', 'vietnamguide-premium'); ?>"
            data-vg-navigation
        >
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'vg-nav-list',
                'fallback_cb'    => 'vg_primary_menu_fallback',
            ]);
            ?>
        </nav>
        <a class="vg-header-action" href="<?php echo esc_url(vg_home_url('plan')); ?>">
            <?php esc_html_e('Plan your trip', 'vietnamguide-premium'); ?>
        </a>
    </div>
</header>

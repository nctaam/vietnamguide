<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="main" tabindex="-1">
    <section class="vg-section">
        <div class="vg-shell">
            <div class="vg-empty-state">
                <header class="vg-section-heading">
                    <h1><?php esc_html_e('Page not found', 'vietnamguide-premium'); ?></h1>
                </header>
                <p>
                    <?php esc_html_e('The travel guide or route you requested may have moved or no longer exists. Search directly or browse our verified planning hubs below.', 'vietnamguide-premium'); ?>
                </p>
                <div class="vg-empty-state__actions">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="vg-button">
                        <?php esc_html_e('Return to Home', 'vietnamguide-premium'); ?>
                    </a>
                    <a href="<?php echo esc_url(home_url('/destinations/')); ?>" class="vg-button vg-button--secondary">
                        <?php esc_html_e('Explore Destinations', 'vietnamguide-premium'); ?>
                    </a>
                    <a href="<?php echo esc_url(home_url('/itineraries/')); ?>" class="vg-button vg-button--secondary">
                        <?php esc_html_e('Browse Itineraries', 'vietnamguide-premium'); ?>
                    </a>
                </div>
                <?php get_search_form(); ?>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>

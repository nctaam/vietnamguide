<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="main" tabindex="-1">
    <section class="vg-section">
        <div class="vg-shell">
            <div class="vg-empty-state vg-404-container">
                <header class="vg-section-heading">
                    <p class="vg-kicker"><?php esc_html_e('Cartographic Exception • HTTP 404', 'vietnamguide-premium'); ?></p>
                    <h1><?php esc_html_e('Route Uncharted: The coordinates you requested have moved.', 'vietnamguide-premium'); ?></h1>
                </header>
                <p class="vg-404-lead">
                    <?php esc_html_e('The destination dossier, route segment, or field note you requested is not currently mapped at this address. Search directly or navigate to our verified planning hubs and decision engines below.', 'vietnamguide-premium'); ?>
                </p>

                <div class="vg-404-search-wrap">
                    <?php get_search_form(); ?>
                </div>

                <div class="vg-404-recovery-grid">
                    <a href="<?php echo esc_url(home_url('/destinations/')); ?>" class="vg-404-card">
                        <span class="vg-404-card__badge"><?php esc_html_e('Gazetteer', 'vietnamguide-premium'); ?></span>
                        <h2 class="vg-404-card__title"><?php esc_html_e('Destinations & City Bases', 'vietnamguide-premium'); ?></h2>
                        <p class="vg-404-card__desc"><?php esc_html_e('Explore 282 curated routes, regional bases, and terrain dossiers across North, Central, and South Vietnam.', 'vietnamguide-premium'); ?></p>
                        <span class="vg-404-card__cta"><?php esc_html_e('Explore Destinations', 'vietnamguide-premium'); ?> &rarr;</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/itineraries/')); ?>" class="vg-404-card">
                        <span class="vg-404-card__badge"><?php esc_html_e('Route Frameworks', 'vietnamguide-premium'); ?></span>
                        <h2 class="vg-404-card__title"><?php esc_html_e('Signature Itineraries', 'vietnamguide-premium'); ?></h2>
                        <p class="vg-404-card__desc"><?php esc_html_e('Paced 7, 10, 14, and 21-day routes structured around rest buffers and zero backtracks.', 'vietnamguide-premium'); ?></p>
                        <span class="vg-404-card__cta"><?php esc_html_e('Browse Itineraries', 'vietnamguide-premium'); ?> &rarr;</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/compare/')); ?>" class="vg-404-card">
                        <span class="vg-404-card__badge"><?php esc_html_e('Decision Matrices', 'vietnamguide-premium'); ?></span>
                        <h2 class="vg-404-card__title"><?php esc_html_e('Base & Route Dilemmas', 'vietnamguide-premium'); ?></h2>
                        <p class="vg-404-card__desc"><?php esc_html_e('Decisive side-by-side trade-offs: Da Nang vs Hoi An, Ha Long vs Lan Ha Bay, Sapa vs Ha Giang.', 'vietnamguide-premium'); ?></p>
                        <span class="vg-404-card__cta"><?php esc_html_e('Compare Bases', 'vietnamguide-premium'); ?> &rarr;</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/plan/')); ?>" class="vg-404-card">
                        <span class="vg-404-card__badge"><?php esc_html_e('Decision Engines', 'vietnamguide-premium'); ?></span>
                        <h2 class="vg-404-card__title"><?php esc_html_e('Planning & Toolkits', 'vietnamguide-premium'); ?></h2>
                        <p class="vg-404-card__desc"><?php esc_html_e('Visa eligibility checker, daily cost calculator, regional season matrix, and airport scam shield.', 'vietnamguide-premium'); ?></p>
                        <span class="vg-404-card__cta"><?php esc_html_e('Open Toolkits', 'vietnamguide-premium'); ?> &rarr;</span>
                    </a>
                </div>

                <div class="vg-empty-state__actions">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="vg-button">
                        <?php esc_html_e('Return to Home', 'vietnamguide-premium'); ?>
                    </a>
                    <a href="<?php echo esc_url(home_url('/plan/vietnam-evisa/')); ?>" class="vg-button vg-button--secondary">
                        <?php esc_html_e('Check Visa Rules', 'vietnamguide-premium'); ?>
                    </a>
                    <a href="<?php echo esc_url(home_url('/costs/vietnam-travel-cost/')); ?>" class="vg-button vg-button--secondary">
                        <?php esc_html_e('Calculate Budget', 'vietnamguide-premium'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>

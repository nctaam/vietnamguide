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
                    <p class="vg-kicker"><?php esc_html_e('Page Not Found • Error 404', 'vietnamguide-premium'); ?></p>
                    <h1><?php esc_html_e('Route Uncharted: The guide you requested has moved.', 'vietnamguide-premium'); ?></h1>
                </header>
                <p class="vg-404-lead">
                    <?php esc_html_e('The travel guide, route overview, or planning toolkit you requested is no longer located at this address. Search directly or consult our verified planning resources and guides below.', 'vietnamguide-premium'); ?>
                </p>

                <div class="vg-404-search-wrap">
                    <?php get_search_form(); ?>
                </div>

                <div class="vg-404-recovery-grid">
                    <a href="<?php echo esc_url(home_url('/destinations/')); ?>" class="vg-404-card">
                        <span class="vg-404-card__badge"><?php esc_html_e('Destinations', 'vietnamguide-premium'); ?></span>
                        <h2 class="vg-404-card__title"><?php esc_html_e('Destinations & City Bases', 'vietnamguide-premium'); ?></h2>
                        <p class="vg-404-card__desc"><?php esc_html_e('Explore 282 curated routes, regional bases, and practical guides across North, Central, and South Vietnam.', 'vietnamguide-premium'); ?></p>
                        <span class="vg-404-card__cta"><?php esc_html_e('Explore Destinations', 'vietnamguide-premium'); ?> &rarr;</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/itineraries/')); ?>" class="vg-404-card">
                        <span class="vg-404-card__badge"><?php esc_html_e('Itineraries', 'vietnamguide-premium'); ?></span>
                        <h2 class="vg-404-card__title"><?php esc_html_e('Signature Itineraries', 'vietnamguide-premium'); ?></h2>
                        <p class="vg-404-card__desc"><?php esc_html_e('Paced 7, 10, 14, and 21-day routes structured around rest buffers and zero backtracks.', 'vietnamguide-premium'); ?></p>
                        <span class="vg-404-card__cta"><?php esc_html_e('Browse Itineraries', 'vietnamguide-premium'); ?> &rarr;</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/compare/')); ?>" class="vg-404-card">
                        <span class="vg-404-card__badge"><?php esc_html_e('Route Comparisons', 'vietnamguide-premium'); ?></span>
                        <h2 class="vg-404-card__title"><?php esc_html_e('Base & Route Dilemmas', 'vietnamguide-premium'); ?></h2>
                        <p class="vg-404-card__desc"><?php esc_html_e('Decisive side-by-side trade-offs: Da Nang vs Hoi An, Ha Long vs Lan Ha Bay, Sa Pa vs Ha Giang.', 'vietnamguide-premium'); ?></p>
                        <span class="vg-404-card__cta"><?php esc_html_e('Compare Bases', 'vietnamguide-premium'); ?> &rarr;</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/plan/')); ?>" class="vg-404-card">
                        <span class="vg-404-card__badge"><?php esc_html_e('Planning Tools', 'vietnamguide-premium'); ?></span>
                        <h2 class="vg-404-card__title"><?php esc_html_e('Planning & Toolkits', 'vietnamguide-premium'); ?></h2>
                        <p class="vg-404-card__desc"><?php esc_html_e('Visa eligibility checker, daily cost calculator, regional season matrix, and airport scam shield.', 'vietnamguide-premium'); ?></p>
                        <span class="vg-404-card__cta"><?php esc_html_e('Open Toolkits', 'vietnamguide-premium'); ?> &rarr;</span>
                    </a>
                </div>

                <div class="vg-empty-state__actions">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="vg-button">
                        <?php esc_html_e('Back to Homepage', 'vietnamguide-premium'); ?>
                    </a>
                    <a href="<?php echo esc_url(home_url('/destinations/')); ?>" class="vg-button vg-button--secondary">
                        <?php esc_html_e('Browse All Destinations', 'vietnamguide-premium'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>

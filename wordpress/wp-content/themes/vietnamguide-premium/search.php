<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="main" tabindex="-1">
    <section class="vg-section">
        <div class="vg-shell">
            <header class="vg-section-heading">
                <h1>
                    <?php
                    printf(
                        esc_html__('Search results for: %s', 'vietnamguide-premium'),
                        esc_html(get_search_query(false))
                    );
                    ?>
                </h1>
            </header>

            <?php if (have_posts()) : ?>
                <div class="vg-post-list">
                    <?php while (have_posts()) : ?>
                        <?php the_post(); ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                            <header>
                                <time class="vg-post-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                    <?php echo esc_html(get_the_date()); ?>
                                </time>
                                <h2>
                                    <a href="<?php echo esc_url(get_permalink()); ?>">
                                        <?php echo esc_html(get_the_title()); ?>
                                    </a>
                                </h2>
                            </header>
                            <div class="vg-entry-summary">
                                <?php the_excerpt(); ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

                <?php
                the_posts_pagination([
                    'mid_size'  => 1,
                    'prev_text' => esc_html__('Previous', 'vietnamguide-premium'),
                    'next_text' => esc_html__('Next', 'vietnamguide-premium'),
                ]);
                ?>
            <?php else : ?>
                <div class="vg-empty-state">
                    <p>
                        <?php esc_html_e('No travel guides matched your search. Try another query or browse our core sections below.', 'vietnamguide-premium'); ?>
                    </p>
                    <div class="vg-empty-state__actions">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="vg-button">
                            <?php esc_html_e('Return to Home', 'vietnamguide-premium'); ?>
                        </a>
                        <a href="<?php echo esc_url(home_url('/destinations/')); ?>" class="vg-button vg-button--secondary">
                            <?php esc_html_e('All Destinations', 'vietnamguide-premium'); ?>
                        </a>
                        <a href="<?php echo esc_url(home_url('/itineraries/')); ?>" class="vg-button vg-button--secondary">
                            <?php esc_html_e('All Itineraries', 'vietnamguide-premium'); ?>
                        </a>
                    </div>
                    <?php get_search_form(); ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php get_footer(); ?>

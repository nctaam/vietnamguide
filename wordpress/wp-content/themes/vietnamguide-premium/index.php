<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="main" tabindex="-1">
    <?php if (have_posts()) : ?>
        <?php if (is_singular()) : ?>
            <section class="vg-section">
                <div class="vg-shell">
                    <?php while (have_posts()) : ?>
                        <?php the_post(); ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                            <header class="vg-section-heading">
                                <h1><?php echo esc_html(get_the_title()); ?></h1>
                                <?php if (is_single()) : ?>
                                    <div class="vg-post-meta">
                                        <time class="vg-post-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                            <?php echo esc_html(get_the_date()); ?>
                                        </time>
                                        <span class="vg-meta-separator" aria-hidden="true">•</span>
                                        <span class="vg-author-byline"><?php esc_html_e('VietnamGuide Editorial Desk', 'vietnamguide-premium'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </header>
                            <div class="vg-entry-content">
                                <?php the_content(); ?>
                                <?php
                                wp_link_pages([
                                    'before' => '<nav class="vg-page-links" aria-label="' . esc_attr__('Page navigation', 'vietnamguide-premium') . '">',
                                    'after'  => '</nav>',
                                ]);
                                ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php else : ?>
            <section class="vg-section">
                <div class="vg-shell">
                    <header class="vg-section-heading">
                        <h1>
                            <?php if (is_search()) : ?>
                                <?php
                                printf(
                                    esc_html__('Search results for: %s', 'vietnamguide-premium'),
                                    esc_html(get_search_query(false))
                                );
                                ?>
                            <?php elseif (is_archive()) : ?>
                                <?php echo wp_kses_post(get_the_archive_title()); ?>
                            <?php elseif (is_home() && ! is_front_page() && single_post_title('', false)) : ?>
                                <?php echo esc_html(single_post_title('', false)); ?>
                            <?php else : ?>
                                <?php esc_html_e('Latest articles', 'vietnamguide-premium'); ?>
                            <?php endif; ?>
                        </h1>
                    </header>

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
                </div>
            </section>
        <?php endif; ?>
    <?php else : ?>
        <section class="vg-section">
            <div class="vg-shell">
                <div class="vg-empty-state">
                    <header class="vg-section-heading">
                        <h1>
                            <?php if (is_404()) : ?>
                                <?php esc_html_e('Page not found', 'vietnamguide-premium'); ?>
                            <?php elseif (is_search()) : ?>
                                <?php esc_html_e('No search results', 'vietnamguide-premium'); ?>
                            <?php else : ?>
                                <?php esc_html_e('Nothing found', 'vietnamguide-premium'); ?>
                            <?php endif; ?>
                        </h1>
                    </header>
                    <p>
                        <?php if (is_404()) : ?>
                            <?php esc_html_e('The page you requested may have moved or no longer exists.', 'vietnamguide-premium'); ?>
                        <?php elseif (is_search()) : ?>
                            <?php esc_html_e('Try another search term or explore the latest travel guidance.', 'vietnamguide-premium'); ?>
                        <?php else : ?>
                            <?php esc_html_e('There is no published travel guidance here yet.', 'vietnamguide-premium'); ?>
                        <?php endif; ?>
                    </p>
                    <div class="vg-empty-state__actions">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="vg-button">
                            <?php esc_html_e('Return to Home', 'vietnamguide-premium'); ?>
                        </a>
                        <a href="<?php echo esc_url(home_url('/#itineraries')); ?>" class="vg-button vg-button--secondary">
                            <?php esc_html_e('Browse Itineraries', 'vietnamguide-premium'); ?>
                        </a>
                    </div>
                    <?php get_search_form(); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php get_footer(); ?>

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
            </div>
        </section>
    <?php endif; ?>
</main>
<?php get_footer(); ?>

<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

global $wp_query;
$search_query = get_search_query(false);
$found_count  = (int) $wp_query->found_posts;
?>
<main id="main" tabindex="-1">
    <section class="vg-section vg-search-section">
        <div class="vg-shell">
            <header class="vg-section-heading vg-search-heading">
                <h1>
                    <?php
                    printf(
                        esc_html__('Search results for: %s', 'vietnamguide-premium'),
                        '&ldquo;' . esc_html($search_query) . '&rdquo;'
                    );
                    ?>
                </h1>
                <div class="vg-search-meta">
                    <?php if ($found_count > 0) : ?>
                        <span class="vg-search-count">
                            <?php
                            printf(
                                esc_html(_n('Found %d curated travel guide', 'Found %d curated travel guides', $found_count, 'vietnamguide-premium')),
                                $found_count
                            );
                            ?>
                        </span>
                    <?php else : ?>
                        <span class="vg-search-count vg-search-count--empty">
                            <?php esc_html_e('No travel guides matched your query', 'vietnamguide-premium'); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="vg-search-form-top">
                    <?php get_search_form(); ?>
                </div>

                <div class="vg-search-chips" aria-label="<?php esc_attr_e('Popular travel searches', 'vietnamguide-premium'); ?>">
                    <span class="vg-search-chips__title"><?php esc_html_e('Popular searches:', 'vietnamguide-premium'); ?></span>
                    <div class="vg-search-chips__list">
                        <a href="<?php echo esc_url(add_query_arg('s', 'Hanoi', home_url('/'))); ?>" class="vg-search-chip">Hanoi</a>
                        <a href="<?php echo esc_url(add_query_arg('s', 'Da Nang', home_url('/'))); ?>" class="vg-search-chip">Da Nang</a>
                        <a href="<?php echo esc_url(add_query_arg('s', 'Ha Long', home_url('/'))); ?>" class="vg-search-chip">Ha Long</a>
                        <a href="<?php echo esc_url(add_query_arg('s', 'Hoi An', home_url('/'))); ?>" class="vg-search-chip">Hoi An</a>
                        <a href="<?php echo esc_url(add_query_arg('s', 'Sa Pa', home_url('/'))); ?>" class="vg-search-chip">Sa Pa</a>
                        <a href="<?php echo esc_url(add_query_arg('s', 'Ninh Binh', home_url('/'))); ?>" class="vg-search-chip">Ninh Binh</a>
                        <a href="<?php echo esc_url(add_query_arg('s', 'Ho Chi Minh', home_url('/'))); ?>" class="vg-search-chip">Ho Chi Minh City</a>
                        <a href="<?php echo esc_url(add_query_arg('s', 'Phu Quoc', home_url('/'))); ?>" class="vg-search-chip">Phu Quoc</a>
                    </div>
                </div>
            </header>

            <?php if (have_posts()) : ?>
                <div class="vg-post-list vg-search-results">
                    <?php while (have_posts()) : ?>
                        <?php the_post(); ?>
                        <?php
                        $post_obj = get_post();
                        $guide_type = function_exists('vg_get_guide_type') ? vg_get_guide_type($post_obj) : null;
                        if (! $guide_type && function_exists('vg_classify_guide_path')) {
                            $uri = $post_obj instanceof WP_Post ? trim((string) get_page_uri($post_obj), '/') : '';
                            $guide_type = vg_classify_guide_path($uri);
                        }

                        $type_labels = [
                            'destination' => __('Destination Guide', 'vietnamguide-premium'),
                            'itinerary'   => __('Itinerary', 'vietnamguide-premium'),
                            'comparison'  => __('Route Comparison', 'vietnamguide-premium'),
                            'practical'   => __('Planning Guide', 'vietnamguide-premium'),
                        ];
                        $badge_label = $type_labels[$guide_type] ?? __('Travel Guide', 'vietnamguide-premium');
                        $badge_class = 'vg-search-badge--' . ($guide_type ?: 'general');
                        ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class('vg-search-item'); ?>>
                            <header class="vg-search-item__header">
                                <div class="vg-search-item__meta">
                                    <span class="vg-search-badge <?php echo esc_attr($badge_class); ?>">
                                        <?php echo esc_html($badge_label); ?>
                                    </span>
                                    <time class="vg-post-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                        <?php echo esc_html(get_the_date()); ?>
                                    </time>
                                </div>
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
                <div class="vg-empty-state vg-search-empty-state">
                    <p>
                        <?php esc_html_e('No travel guides matched your search. Try another query above or browse our core sections below.', 'vietnamguide-premium'); ?>
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
                        <a href="<?php echo esc_url(home_url('/plan/vietnam-travel-guide/')); ?>" class="vg-button vg-button--secondary">
                            <?php esc_html_e('Planning Guide', 'vietnamguide-premium'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php get_footer(); ?>

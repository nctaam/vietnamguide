<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<section class="vg-section">
    <div class="vg-shell">
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="vg-section-heading">
                <h1><?php echo esc_html(get_the_title()); ?></h1>
            </header>
            <div class="vg-entry-content">
                <?php the_content(); ?>
                <?php
                wp_link_pages([
                    'before' => '<nav class="vg-page-links" aria-label="' . esc_attr__('Page navigation', 'vietnamguide-premium') . '">',
                    'after' => '</nav>',
                ]);
                ?>
            </div>
        </article>
    </div>
</section>

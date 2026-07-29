<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="main" tabindex="-1">
    <?php while (have_posts()) : ?>
        <?php
        the_post();
        $post = get_post();
        $guideContext = null;

        if ($post instanceof WP_Post && vg_is_guide_experience_page($post)) {
            $guideContext = vg_build_guide_context($post);
        }

        if (is_array($guideContext) && vg_is_valid_guide_context($guideContext)) {
            get_template_part('template-parts/guide', 'page', $guideContext);
        } else {
            get_template_part('template-parts/content', 'page');
        }
        ?>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>

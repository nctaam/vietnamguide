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
        $guideFunctionsReady = function_exists('vg_is_guide_experience_page')
            && function_exists('vg_build_guide_context')
            && function_exists('vg_is_valid_guide_context');

        if ($post instanceof WP_Post && $guideFunctionsReady && vg_is_guide_experience_page($post)) {
            $guideContext = vg_build_guide_context($post);
        }

        if ($guideFunctionsReady && is_array($guideContext) && vg_is_valid_guide_context($guideContext)) {
            get_template_part('template-parts/guide', 'page', $guideContext);
        } else {
            if (function_exists('vg_record_route_fallback')) {
                $path = function_exists('vg_get_guide_path') && $post instanceof WP_Post ? vg_get_guide_path($post) : '';
                $reason = ! ($post instanceof WP_Post)
                    ? 'invalid_post'
                    : (! $guideFunctionsReady
                        ? 'functions_not_ready'
                        : (! vg_is_guide_experience_page($post)
                            ? 'non_guide_experience'
                            : 'invalid_guide_context'));
                vg_record_route_fallback($path, $reason);
            }
            get_template_part('template-parts/content', 'page');
        }
        ?>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>

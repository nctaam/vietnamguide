<?php
if (! defined('ABSPATH')) {
    exit;
}

$content = get_the_content();
$content = apply_filters('the_content', $content);
$content = str_replace(']]>', ']]&gt;', $content);

$hasContentH1 = false;
if (class_exists('WP_HTML_Tag_Processor')) {
    $processor = new WP_HTML_Tag_Processor($content);
    while ($processor->next_token()) {
        if ($processor->is_tag_closer()) {
            continue;
        }
        if ('H1' === $processor->get_token_name()) {
            $hasContentH1 = true;
            break;
        }
    }
    if ($processor->paused_at_incomplete_token()) {
        $hasContentH1 = false;
    }
}
?>
<section class="vg-section">
    <div class="vg-shell">
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <?php if (! $hasContentH1) : ?>
                <header class="vg-section-heading">
                    <h1><?php echo esc_html(get_the_title()); ?></h1>
                </header>
            <?php endif; ?>
            <div class="vg-entry-content">
                <?php echo $content; ?>
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

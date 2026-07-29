<?php
if (! defined('ABSPATH') || ! is_array($args ?? null) || ! vg_is_valid_guide_context($args)) {
    return;
}

$type = (string) $args['type'];
$typeLabels = [
    'destination' => __('Destination', 'vietnamguide-premium'),
    'itinerary' => __('Itinerary', 'vietnamguide-premium'),
    'comparison' => __('Comparison', 'vietnamguide-premium'),
    'practical' => __('Practical', 'vietnamguide-premium'),
];
$typeLabel = $typeLabels[$type];
$headings = is_array($args['headings'] ?? null) ? $args['headings'] : [];
$reviewed = (string) ($args['reviewed_at'] ?? '');
$readingTime = (int) ($args['reading_time'] ?? 0);
$sourceCount = (int) ($args['source_count'] ?? 0);
$bestFor = (string) ($args['best_for'] ?? '');
$skipIf = (string) ($args['skip_if'] ?? '');
$relatedRoutes = is_array($args['related_routes'] ?? null) ? $args['related_routes'] : [];
?>
<article
    id="post-<?php the_ID(); ?>"
    <?php post_class('vg-guide-experience vg-guide-experience--' . sanitize_html_class($type)); ?>
    data-vg-guide
    data-vg-guide-type="<?php echo esc_attr($type); ?>"
>
    <?php echo $args['hero_html']; ?>

    <div class="vg-guide-meta" role="list" aria-label="<?php esc_attr_e('Guide details', 'vietnamguide-premium'); ?>">
        <span role="listitem"><?php echo esc_html($typeLabel); ?></span>
        <?php if ($readingTime > 0) : ?>
            <span role="listitem"><?php echo esc_html(sprintf(_n('%d minute read', '%d minutes read', $readingTime, 'vietnamguide-premium'), $readingTime)); ?></span>
        <?php endif; ?>
        <?php if ($reviewed !== '') : ?>
            <span role="listitem"><?php echo esc_html(sprintf(__('Reviewed %s', 'vietnamguide-premium'), $reviewed)); ?></span>
        <?php endif; ?>
        <?php if ($sourceCount > 0) : ?>
            <span role="listitem"><?php echo esc_html(sprintf(_n('%d source', '%d sources', $sourceCount, 'vietnamguide-premium'), $sourceCount)); ?></span>
        <?php endif; ?>
    </div>

    <?php if (count($headings) >= 2) : ?>
        <?php echo vg_render_guide_toc($headings, 'vg-guide-jump'); ?>
    <?php endif; ?>

    <div class="vg-guide-spine">
        <div class="vg-guide-spine__toc">
            <?php echo (string) ($args['toc_html'] ?? ''); ?>
        </div>

        <div class="vg-guide-article">
            <?php echo $args['body_html']; ?>
            <?php
            wp_link_pages([
                'before' => '<nav class="vg-page-links" aria-label="' . esc_attr__('Page navigation', 'vietnamguide-premium') . '">',
                'after' => '</nav>',
            ]);
            ?>
        </div>

        <?php if ($bestFor !== '' || $skipIf !== '' || $reviewed !== '' || $sourceCount > 0) : ?>
            <aside class="vg-guide-trust" aria-label="<?php esc_attr_e('Guide context', 'vietnamguide-premium'); ?>">
                <?php if ($bestFor !== '') : ?><div><strong><?php esc_html_e('Best for', 'vietnamguide-premium'); ?></strong><span><?php echo esc_html($bestFor); ?></span></div><?php endif; ?>
                <?php if ($skipIf !== '') : ?><div><strong><?php esc_html_e('Skip if', 'vietnamguide-premium'); ?></strong><span><?php echo esc_html($skipIf); ?></span></div><?php endif; ?>
                <?php if ($reviewed !== '') : ?><div><strong><?php esc_html_e('Last reviewed', 'vietnamguide-premium'); ?></strong><span><?php echo esc_html($reviewed); ?></span></div><?php endif; ?>
                <?php if ($sourceCount > 0) : ?><div><strong><?php esc_html_e('Sources checked', 'vietnamguide-premium'); ?></strong><span><?php echo esc_html((string) $sourceCount); ?></span></div><?php endif; ?>
            </aside>
        <?php endif; ?>
    </div>

    <?php if ($relatedRoutes !== []) : ?>
        <nav class="vg-guide-related vg-shell" aria-label="<?php esc_attr_e('Related routes', 'vietnamguide-premium'); ?>">
            <p class="vg-kicker"><?php esc_html_e('Continue planning', 'vietnamguide-premium'); ?></p>
            <h2><?php esc_html_e('Related Vietnam guides', 'vietnamguide-premium'); ?></h2>
            <ul>
                <?php foreach ($relatedRoutes as $route) : ?>
                    <li><a href="<?php echo esc_url((string) $route['url']); ?>"><?php echo esc_html((string) $route['title']); ?><span aria-hidden="true">&rarr;</span></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    <?php endif; ?>
</article>

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
            <div class="vg-share-bar">
                <span class="vg-share-label"><?php esc_html_e('Share this guide:', 'vietnamguide-premium'); ?></span>
                <button type="button" class="vg-copy-link" data-vg-copy-link aria-label="<?php esc_attr_e('Copy guide link to clipboard', 'vietnamguide-premium'); ?>">
                    <span class="vg-copy-link__icon" aria-hidden="true">&#128279;</span>
                    <span class="vg-copy-link__text"><?php esc_html_e('Copy link', 'vietnamguide-premium'); ?></span>
                </button>
                <button type="button" class="vg-print-guide" data-vg-print aria-label="<?php esc_attr_e('Print or save field guide as PDF', 'vietnamguide-premium'); ?>">
                    <span class="vg-print-guide__icon" aria-hidden="true">&#128424;</span>
                    <span class="vg-print-guide__text"><?php esc_html_e('Print Field Guide', 'vietnamguide-premium'); ?></span>
                </button>
            </div>
        </div>

        <?php if ($bestFor !== '' || $skipIf !== '' || $reviewed !== '' || $sourceCount > 0) : ?>
            <aside class="vg-guide-trust" aria-label="<?php esc_attr_e('Guide context', 'vietnamguide-premium'); ?>">
                <?php if ($bestFor !== '') : ?><div><strong><?php esc_html_e('Best for', 'vietnamguide-premium'); ?></strong><span><?php echo esc_html($bestFor); ?></span></div><?php endif; ?>
                <?php if ($skipIf !== '') : ?><div><strong><?php esc_html_e('Skip if', 'vietnamguide-premium'); ?></strong><span><?php echo esc_html($skipIf); ?></span></div><?php endif; ?>
                <?php if ($reviewed !== '') : ?><div><strong><?php esc_html_e('Last reviewed', 'vietnamguide-premium'); ?></strong><span><a href="<?php echo esc_url(home_url('/editorial-policy/')); ?>" style="color:inherit;text-decoration:underline;text-underline-offset:2px;"><?php echo esc_html($reviewed); ?></a></span></div><?php endif; ?>
                <?php if ($sourceCount > 0) : ?><div><strong><?php esc_html_e('Sources checked', 'vietnamguide-premium'); ?></strong><span><a href="<?php echo esc_url(home_url('/source-update-policy/')); ?>" style="color:inherit;text-decoration:underline;text-underline-offset:2px;"><?php echo esc_html((string) $sourceCount); ?> <?php esc_html_e('verified sources', 'vietnamguide-premium'); ?></a></span></div><?php endif; ?>
                <div><strong><?php esc_html_e('Standards', 'vietnamguide-premium'); ?></strong><span><a href="<?php echo esc_url(home_url('/affiliate-review-policy/')); ?>" style="color:inherit;text-decoration:underline;text-underline-offset:2px;"><?php esc_html_e('Zero Sponsored Bias', 'vietnamguide-premium'); ?></a></span></div>
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

    <!-- Reading Progress Bar & Floating Tactical Dock -->
    <div id="vg-reading-progress" class="vg-reading-progress" role="progressbar" aria-label="<?php esc_attr_e('Reading progress', 'vietnamguide-premium'); ?>" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>

    <aside id="vg-floating-dock" class="vg-floating-dock" aria-label="<?php esc_attr_e('Reading navigation dock', 'vietnamguide-premium'); ?>" style="display:none;">
        <div class="vg-dock-inner">
            <button type="button" class="vg-dock-btn vg-dock-btn--top" id="vg-dock-top" aria-label="<?php esc_attr_e('Scroll to top of guide', 'vietnamguide-premium'); ?>" title="<?php esc_attr_e('Back to top', 'vietnamguide-premium'); ?>">
                <span aria-hidden="true">↑</span>
                <span class="vg-dock-label"><?php esc_html_e('Top', 'vietnamguide-premium'); ?></span>
            </button>
            <?php if (count($headings) >= 2) : ?>
                <button type="button" class="vg-dock-btn vg-dock-btn--toc" id="vg-dock-toc" aria-label="<?php esc_attr_e('Jump to Table of Contents', 'vietnamguide-premium'); ?>" title="<?php esc_attr_e('Table of Contents', 'vietnamguide-premium'); ?>">
                    <span aria-hidden="true">📋</span>
                    <span class="vg-dock-label"><?php esc_html_e('Sections', 'vietnamguide-premium'); ?></span>
                </button>
            <?php endif; ?>
            <button type="button" class="vg-dock-btn vg-dock-btn--tools" id="vg-dock-tools" aria-expanded="false" aria-haspopup="dialog" aria-label="<?php esc_attr_e('Open travel planning tools', 'vietnamguide-premium'); ?>">
                <span aria-hidden="true">🧳</span>
                <span class="vg-dock-label"><?php esc_html_e('Tools', 'vietnamguide-premium'); ?></span>
            </button>
            <div class="vg-dock-progress-indicator" id="vg-dock-pct" aria-hidden="true">0%</div>
        </div>

        <div id="vg-dock-popover" class="vg-dock-popover" role="dialog" aria-label="<?php esc_attr_e('Vietnam Travel Planning Toolkits', 'vietnamguide-premium'); ?>" style="display:none;">
            <div class="vg-dock-popover-header">
                <strong><?php esc_html_e('Travel Planning Toolkits', 'vietnamguide-premium'); ?></strong>
                <button type="button" id="vg-dock-close" class="vg-dock-close" aria-label="<?php esc_attr_e('Close tools menu', 'vietnamguide-premium'); ?>">&times;</button>
            </div>
            <nav class="vg-dock-tools-grid">
                <a href="<?php echo esc_url(home_url('/costs/vietnam-travel-cost/')); ?>" class="vg-dock-tool-link">
                    <span class="vg-dock-tool-icon">💰</span>
                    <span class="vg-dock-tool-name"><?php esc_html_e('Budget Calculator', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/vietnam-evisa/')); ?>" class="vg-dock-tool-link">
                    <span class="vg-dock-tool-icon">🛂</span>
                    <span class="vg-dock-tool-name"><?php esc_html_e('Visa Checker', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/best-time-to-visit-vietnam/')); ?>" class="vg-dock-tool-link">
                    <span class="vg-dock-tool-icon">☀️</span>
                    <span class="vg-dock-tool-name"><?php esc_html_e('Season & Weather', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/vietnam-airport-arrival-checklist/')); ?>" class="vg-dock-tool-link">
                    <span class="vg-dock-tool-icon">✈️</span>
                    <span class="vg-dock-tool-name"><?php esc_html_e('Airport Transit', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/itineraries/')); ?>" class="vg-dock-tool-link">
                    <span class="vg-dock-tool-icon">🗺️</span>
                    <span class="vg-dock-tool-name"><?php esc_html_e('Itinerary Finder', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/vietnam-first-trip-planning-checklist/')); ?>" class="vg-dock-tool-link">
                    <span class="vg-dock-tool-icon">🎒</span>
                    <span class="vg-dock-tool-name"><?php esc_html_e('Packing Checklist', 'vietnamguide-premium'); ?></span>
                </a>
            </nav>
        </div>
    </aside>

    <script>
    (function () {
        'use strict';
        var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var progressBar = document.getElementById('vg-reading-progress');
        var dock = document.getElementById('vg-floating-dock');
        var dockPct = document.getElementById('vg-dock-pct');
        var topBtn = document.getElementById('vg-dock-top');
        var tocBtn = document.getElementById('vg-dock-toc');
        var toolsBtn = document.getElementById('vg-dock-tools');
        var popover = document.getElementById('vg-dock-popover');
        var closeBtn = document.getElementById('vg-dock-close');

        if (!progressBar && !dock) return;

        var ticking = false;
        function updateProgress() {
            var docElem = document.documentElement;
            var maxScroll = docElem.scrollHeight - window.innerHeight;
            var pct = maxScroll > 0 ? (window.scrollY / maxScroll) * 100 : 0;
            var clamped = Math.min(100, Math.max(0, Math.round(pct)));

            if (progressBar) {
                progressBar.style.width = clamped + '%';
                progressBar.setAttribute('aria-valuenow', String(clamped));
            }
            if (dockPct) {
                dockPct.textContent = clamped + '%';
            }
            if (dock) {
                dock.style.display = window.scrollY > 280 ? 'block' : 'none';
            }
            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (!ticking) {
                window.requestAnimationFrame(updateProgress);
                ticking = true;
            }
        }, { passive: true });
        updateProgress();

        if (topBtn) {
            topBtn.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'smooth' });
            });
        }

        if (tocBtn) {
            tocBtn.addEventListener('click', function () {
                var toc = document.querySelector('.vg-guide-spine__toc, .vg-guide-jump');
                if (toc) {
                    toc.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
                }
            });
        }

        function toggleTools(forceState) {
            if (!popover || !toolsBtn) return;
            var isOpen = forceState !== undefined ? forceState : toolsBtn.getAttribute('aria-expanded') !== 'true';
            toolsBtn.setAttribute('aria-expanded', String(isOpen));
            popover.style.display = isOpen ? 'block' : 'none';
        }

        if (toolsBtn) {
            toolsBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleTools();
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleTools(false);
            });
        }

        document.addEventListener('click', function (e) {
            if (popover && popover.style.display === 'block') {
                if (!popover.contains(e.target) && (!toolsBtn || !toolsBtn.contains(e.target))) {
                    toggleTools(false);
                }
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && popover && popover.style.display === 'block') {
                toggleTools(false);
                if (toolsBtn) toolsBtn.focus();
            }
        });
    })();
    </script>
</article>

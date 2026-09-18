<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

global $wp_query;
$search_query = get_search_query(false);
$found_count  = (int) $wp_query->found_posts;

$matched_toolkit = null;
$q_clean = strtolower(trim((string) $search_query));
if ($q_clean !== '') {
    if (str_contains($q_clean, 'visa') || str_contains($q_clean, 'evisa') || str_contains($q_clean, 'passport') || str_contains($q_clean, 'entry')) {
        $matched_toolkit = [
            'type'        => 'visa',
            'badge'       => __('Interactive Decision Engine', 'vietnamguide-premium'),
            'title'       => __('Vietnam Visa Eligibility Checker', 'vietnamguide-premium'),
            'description' => __('Check your passport nationality for 45-day visa-free exemptions, official 90-day e-visa requirements, and entry checkpoints in 5 seconds.', 'vietnamguide-premium'),
            'url'         => home_url('/plan/vietnam-evisa/'),
            'cta'         => __('Launch Visa Checker', 'vietnamguide-premium'),
        ];
    } elseif (str_contains($q_clean, 'cost') || str_contains($q_clean, 'budget') || str_contains($q_clean, 'price') || str_contains($q_clean, 'money') || str_contains($q_clean, 'expensive') || str_contains($q_clean, 'dong') || str_contains($q_clean, 'vnd')) {
        $matched_toolkit = [
            'type'        => 'cost',
            'badge'       => __('Interactive Budget Engine', 'vietnamguide-premium'),
            'title'       => __('Vietnam Travel Cost Calculator', 'vietnamguide-premium'),
            'description' => __('Calculate realistic daily travel expenses across accommodation, transport, meals, and activities tailored to your trip style and group size.', 'vietnamguide-premium'),
            'url'         => home_url('/costs/vietnam-travel-cost/'),
            'cta'         => __('Calculate Your Budget', 'vietnamguide-premium'),
        ];
    } elseif (str_contains($q_clean, 'weather') || str_contains($q_clean, 'rain') || str_contains($q_clean, 'season') || str_contains($q_clean, 'climate') || str_contains($q_clean, 'monsoon') || str_contains($q_clean, 'typhoon') || str_contains($q_clean, 'when to')) {
        $matched_toolkit = [
            'type'        => 'weather',
            'badge'       => __('Interactive Seasonal Engine', 'vietnamguide-premium'),
            'title'       => __('Vietnam Season & Weather Guide', 'vietnamguide-premium'),
            'description' => __('Compare regional climate patterns month-by-month across North, Central, and South Vietnam to choose your optimal travel window.', 'vietnamguide-premium'),
            'url'         => home_url('/plan/best-time-to-visit-vietnam/'),
            'cta'         => __('Explore Weather Guide', 'vietnamguide-premium'),
        ];
    } elseif (str_contains($q_clean, 'pack') || str_contains($q_clean, 'luggage') || str_contains($q_clean, 'bag') || str_contains($q_clean, 'checklist') || str_contains($q_clean, 'cloth') || str_contains($q_clean, 'gear') || str_contains($q_clean, 'jacket') || str_contains($q_clean, 'prep')) {
        $matched_toolkit = [
            'type'        => 'packing',
            'badge'       => __('Interactive Preparation Engine', 'vietnamguide-premium'),
            'title'       => __('Vietnam Route Packing & Preparation Checklist', 'vietnamguide-premium'),
            'description' => __('Interactive 24-item travel preparation checklist covering official documents, electronics, clothing, medical essentials, and motorbike gear with browser state persistence.', 'vietnamguide-premium'),
            'url'         => home_url('/plan/vietnam-first-trip-planning-checklist/'),
            'cta'         => __('Open Packing Checklist', 'vietnamguide-premium'),
        ];
    }
}
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
                        <a href="<?php echo esc_url(add_query_arg('s', 'Ha Giang Loop', home_url('/'))); ?>" class="vg-search-chip">Ha Giang Loop</a>
                        <a href="<?php echo esc_url(add_query_arg('s', 'Mekong Delta', home_url('/'))); ?>" class="vg-search-chip">Mekong Delta</a>
                        <a href="<?php echo esc_url(add_query_arg('s', 'Visa', home_url('/'))); ?>" class="vg-search-chip">Visa</a>
                        <a href="<?php echo esc_url(add_query_arg('s', 'Budget', home_url('/'))); ?>" class="vg-search-chip">Budget &amp; Cost</a>
                        <a href="<?php echo esc_url(add_query_arg('s', 'Weather', home_url('/'))); ?>" class="vg-search-chip">Weather</a>
                        <a href="<?php echo esc_url(add_query_arg('s', 'Packing', home_url('/'))); ?>" class="vg-search-chip">Packing Checklist</a>
                        <a href="<?php echo esc_url(add_query_arg('s', '10 Days', home_url('/'))); ?>" class="vg-search-chip">10-Day Itinerary</a>
                    </div>
                </div>
            </header>

            <?php if ($matched_toolkit !== null) : ?>
                <aside class="vg-search-tool-banner vg-search-tool-banner--<?php echo esc_attr($matched_toolkit['type']); ?>" aria-label="<?php esc_attr_e('Featured Travel Planning Tool', 'vietnamguide-premium'); ?>">
                    <div class="vg-search-tool-banner__body">
                        <span class="vg-search-tool-banner__badge">
                            <?php echo esc_html($matched_toolkit['badge']); ?>
                        </span>
                        <h2 class="vg-search-tool-banner__title">
                            <?php echo esc_html($matched_toolkit['title']); ?>
                        </h2>
                        <p class="vg-search-tool-banner__desc">
                            <?php echo esc_html($matched_toolkit['description']); ?>
                        </p>
                    </div>
                    <div class="vg-search-tool-banner__action">
                        <a href="<?php echo esc_url($matched_toolkit['url']); ?>" class="vg-button vg-button--primary">
                            <?php echo esc_html($matched_toolkit['cta']); ?> &rarr;
                        </a>
                    </div>
                </aside>
            <?php endif; ?>

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
                    <p class="vg-search-empty-lead">
                        <?php esc_html_e('No travel guides matched your search query. Try a different query above, explore our interactive travel planning engines, or choose a popular route below.', 'vietnamguide-premium'); ?>
                    </p>

                    <div class="vg-search-empty-block">
                        <h2 class="vg-search-empty-block__title">
                            <?php esc_html_e('Interactive Travel Planning Engines', 'vietnamguide-premium'); ?>
                        </h2>
                        <div class="vg-search-toolkit-grid">
                            <a href="<?php echo esc_url(home_url('/vietnam-visa-checker/')); ?>" class="vg-search-toolkit-card">
                                <span class="vg-search-toolkit-card__badge"><?php esc_html_e('Decision Tool', 'vietnamguide-premium'); ?></span>
                                <h3 class="vg-search-toolkit-card__title"><?php esc_html_e('Visa Eligibility Checker', 'vietnamguide-premium'); ?></h3>
                                <p class="vg-search-toolkit-card__desc"><?php esc_html_e('Verify visa exemption rules and 90-day e-visa entry requirements for your passport nationality in seconds.', 'vietnamguide-premium'); ?></p>
                                <span class="vg-search-toolkit-card__cta"><?php esc_html_e('Check Visa Rules', 'vietnamguide-premium'); ?> &rarr;</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/vietnam-travel-cost/')); ?>" class="vg-search-toolkit-card">
                                <span class="vg-search-toolkit-card__badge"><?php esc_html_e('Budget Tool', 'vietnamguide-premium'); ?></span>
                                <h3 class="vg-search-toolkit-card__title"><?php esc_html_e('Travel Cost Calculator', 'vietnamguide-premium'); ?></h3>
                                <p class="vg-search-toolkit-card__desc"><?php esc_html_e('Estimate realistic daily costs across budget, mid-range, and luxury tiers with itemized breakdowns.', 'vietnamguide-premium'); ?></p>
                                <span class="vg-search-toolkit-card__cta"><?php esc_html_e('Calculate Budget', 'vietnamguide-premium'); ?> &rarr;</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/vietnam-season-weather/')); ?>" class="vg-search-toolkit-card">
                                <span class="vg-search-toolkit-card__badge"><?php esc_html_e('Seasonal Tool', 'vietnamguide-premium'); ?></span>
                                <h3 class="vg-search-toolkit-card__title"><?php esc_html_e('Season & Weather Guide', 'vietnamguide-premium'); ?></h3>
                                <p class="vg-search-toolkit-card__desc"><?php esc_html_e('Compare month-by-month temperature, rainfall, and typhoon patterns across North, Central, and South Vietnam.', 'vietnamguide-premium'); ?></p>
                                <span class="vg-search-toolkit-card__cta"><?php esc_html_e('View Weather Guide', 'vietnamguide-premium'); ?> &rarr;</span>
                            </a>
                        </div>
                    </div>

                    <div class="vg-search-empty-block">
                        <h2 class="vg-search-empty-block__title">
                            <?php esc_html_e('Essential Route Itineraries', 'vietnamguide-premium'); ?>
                        </h2>
                        <div class="vg-search-itinerary-pills">
                            <a href="<?php echo esc_url(home_url('/itineraries/10-days-in-vietnam/')); ?>" class="vg-search-itinerary-pill">
                                <span class="vg-search-itinerary-pill__days">10 Days</span>
                                <span class="vg-search-itinerary-pill__route"><?php esc_html_e('Classic Route: Hanoi, Ha Long Bay, Hoi An, and Ho Chi Minh City', 'vietnamguide-premium'); ?></span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/itineraries/14-days-in-vietnam/')); ?>" class="vg-search-itinerary-pill">
                                <span class="vg-search-itinerary-pill__days">14 Days</span>
                                <span class="vg-search-itinerary-pill__route"><?php esc_html_e('Comprehensive Circuit: Adding Ninh Binh, Hue, and Mekong Delta', 'vietnamguide-premium'); ?></span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/itineraries/21-days-in-vietnam/')); ?>" class="vg-search-itinerary-pill">
                                <span class="vg-search-itinerary-pill__days">21 Days</span>
                                <span class="vg-search-itinerary-pill__route"><?php esc_html_e('Grand Expedition: Including Sa Pa terraces, Ha Giang Loop, and tropical islands', 'vietnamguide-premium'); ?></span>
                            </a>
                        </div>
                    </div>

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
                        <a href="<?php echo esc_url(home_url('/compare/')); ?>" class="vg-button vg-button--secondary">
                            <?php esc_html_e('Route Comparisons', 'vietnamguide-premium'); ?>
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

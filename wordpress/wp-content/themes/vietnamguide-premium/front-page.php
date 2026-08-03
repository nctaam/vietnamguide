<?php
get_header();
$data = vg_homepage_data();
?>
<main id="main" tabindex="-1">
    <section class="vg-hero">
        <picture class="vg-hero__media">
            <source
                type="image/webp"
                srcset="<?php echo esc_url(get_theme_file_uri('/assets/images/home-hero-640.webp')); ?> 640w, <?php echo esc_url(get_theme_file_uri('/assets/images/home-hero-960.webp')); ?> 960w, <?php echo esc_url(get_theme_file_uri('/assets/images/home-hero.webp')); ?> 1376w"
                sizes="100vw"
            >
            <img
                src="<?php echo esc_url(get_theme_file_uri('/assets/images/home-hero.jpg')); ?>"
                srcset="<?php echo esc_url(get_theme_file_uri('/assets/images/home-hero-640.jpg')); ?> 640w, <?php echo esc_url(get_theme_file_uri('/assets/images/home-hero-960.jpg')); ?> 960w, <?php echo esc_url(get_theme_file_uri('/assets/images/home-hero.jpg')); ?> 1376w"
                sizes="100vw"
                width="1376"
                height="768"
                alt="<?php esc_attr_e('Misty limestone karsts in Ha Long Bay at sunrise', 'vietnamguide-premium'); ?>"
                fetchpriority="high"
            >
        </picture>
        <div class="vg-hero__shade" aria-hidden="true"></div>
        <div class="vg-shell vg-hero__content" data-vg-reveal>
            <p class="vg-hero__brand">VietnamGuide.net</p>
            <h1>Vietnam for travelers who choose well.</h1>
            <p class="vg-hero__copy">Curated routes, refined stays, and practical guidance for planning Vietnam with confidence.</p>
            <div class="vg-actions">
                <a class="vg-button" href="<?php echo esc_url(vg_home_url('plan')); ?>" data-vg-event="hero_start_planning">Start planning</a>
                <a class="vg-button vg-button--ghost" href="<?php echo esc_url(vg_home_url('itineraries')); ?>" data-vg-event="hero_see_itineraries">See itineraries</a>
            </div>
        </div>
    </section>

    <section class="vg-section vg-planning-paths" aria-labelledby="vg-planning-title">
        <div class="vg-shell" data-vg-reveal>
            <p class="vg-kicker">Build the right trip</p>
            <h2 id="vg-planning-title">Start with time. Then choose how you want Vietnam to feel.</h2>
            <div class="vg-planning-grid">
                <div>
                    <h3>Choose your trip length</h3>
                    <ul class="vg-link-list">
                        <?php foreach ($data['trip_lengths'] as $item) : ?>
                            <li><a href="<?php echo esc_url($item['url']); ?>" data-vg-event="route_selector_click"><?php echo esc_html($item['label']); ?><span aria-hidden="true">&rarr;</span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h3>Choose your travel style</h3>
                    <ul class="vg-link-list">
                        <?php foreach ($data['travel_styles'] as $item) : ?>
                            <li><a href="<?php echo esc_url($item['url']); ?>" data-vg-event="route_selector_click"><?php echo esc_html($item['label']); ?><span aria-hidden="true">&rarr;</span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="vg-section vg-itineraries" aria-labelledby="vg-itineraries-title">
        <div class="vg-shell">
            <div class="vg-section-heading" data-vg-reveal>
                <p class="vg-kicker">Signature itineraries</p>
                <h2 id="vg-itineraries-title">Routes built around pace, not a checklist.</h2>
            </div>
            <ol class="vg-editorial-rows">
                <?php foreach ($data['itineraries'] as $index => $item) : ?>
                    <li data-vg-reveal>
                        <a href="<?php echo esc_url($item['url']); ?>" data-vg-event="itinerary_click">
                            <span class="vg-editorial-rows__number" aria-hidden="true"><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span>
                            <span><small><?php echo esc_html($item['eyebrow']); ?></small><strong><?php echo esc_html($item['title']); ?></strong></span>
                            <span><?php echo esc_html($item['fit']); ?></span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>

    <section class="vg-section vg-destinations" aria-labelledby="vg-destinations-title">
        <div class="vg-shell vg-destinations__layout">
            <picture class="vg-destinations__media" data-vg-reveal>
                <source
                    type="image/webp"
                    srcset="<?php echo esc_url(get_theme_file_uri('/assets/images/home-editorial-720.webp')); ?> 720w, <?php echo esc_url(get_theme_file_uri('/assets/images/home-editorial.webp')); ?> 1408w"
                    sizes="(max-width: 760px) calc(100vw - 64px), (max-width: 960px) calc(100vw - 96px), min(48vw, 656px)"
                >
                <img
                    src="<?php echo esc_url(get_theme_file_uri('/assets/images/home-editorial.jpg')); ?>"
                    srcset="<?php echo esc_url(get_theme_file_uri('/assets/images/home-editorial-720.jpg')); ?> 720w, <?php echo esc_url(get_theme_file_uri('/assets/images/home-editorial.jpg')); ?> 1408w"
                    sizes="(max-width: 760px) calc(100vw - 64px), (max-width: 960px) calc(100vw - 96px), min(48vw, 656px)"
                    width="1408"
                    height="768"
                    alt="<?php esc_attr_e('Lanterns reflected on the river in Hoi An at night', 'vietnamguide-premium'); ?>"
                    loading="lazy"
                >
            </picture>
            <div>
                <p class="vg-kicker">Destination edit</p>
                <h2 id="vg-destinations-title">Choose places for the trip you actually want.</h2>
                <ul class="vg-destination-list">
                    <?php foreach ($data['destinations'] as $item) : ?>
                        <li data-vg-reveal>
                            <a href="<?php echo esc_url($item['url']); ?>">
                                <strong><?php echo esc_html($item['title']); ?></strong>
                                <span><b>Best for:</b> <?php echo esc_html($item['best_for']); ?></span>
                                <span><b>Skip if:</b> <?php echo esc_html($item['skip_if']); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </section>

    <section class="vg-section vg-comparisons" aria-labelledby="vg-comparisons-title">
        <div class="vg-shell">
            <p class="vg-kicker">Decision guides</p>
            <h2 id="vg-comparisons-title">Make the difficult choices quickly.</h2>
            <ul class="vg-comparison-list">
                <?php foreach ($data['comparisons'] as $item) : ?>
                    <li data-vg-reveal><a href="<?php echo esc_url($item['url']); ?>" data-vg-event="decision_guide_click"><strong><?php echo esc_html($item['title']); ?></strong><span><?php echo esc_html($item['verdict']); ?></span><span aria-hidden="true">&rarr;</span></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="vg-section vg-essentials" aria-labelledby="vg-essentials-title">
        <div class="vg-shell">
            <p class="vg-kicker">Practical essentials</p>
            <h2 id="vg-essentials-title">Handle the details before they become problems.</h2>
            <ul class="vg-essential-grid">
                <?php foreach ($data['essentials'] as $item) : ?>
                    <li data-vg-reveal><a href="<?php echo esc_url($item['url']); ?>"><strong><?php echo esc_html($item['title']); ?></strong><span><?php echo esc_html($item['meta']); ?></span><span aria-hidden="true">&rarr;</span></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="vg-section vg-newsletter" aria-labelledby="vg-newsletter-title">
        <div class="vg-shell vg-newsletter__inner" data-vg-reveal>
            <div><p class="vg-kicker">First-trip checklist</p><h2 id="vg-newsletter-title">Plan the trip once. Travel it with confidence.</h2></div>
            <p>Get a concise Vietnam planning checklist covering route, entry, transport, money, connectivity, and common mistakes.</p>
            <a class="vg-button vg-button--light" href="<?php echo esc_url(vg_home_url('newsletter')); ?>" data-vg-event="newsletter_signup">Get the checklist</a>
        </div>
    </section>
</main>
<?php get_footer(); ?>

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
            <div class="vg-hero__telemetry" aria-label="<?php esc_attr_e('Vietnam Ground Telemetry', 'vietnamguide-premium'); ?>">
                <div class="vg-hero__telemetry-item">
                    <span class="vg-hero__telemetry-pulse" aria-hidden="true"></span>
                    <span class="vg-hero__telemetry-label"><?php esc_html_e('TELEMETRY:', 'vietnamguide-premium'); ?></span>
                    <span class="vg-hero__telemetry-val"><?php esc_html_e('ACTIVE (ICT UTC+7)', 'vietnamguide-premium'); ?></span>
                </div>
                <span class="vg-hero__telemetry-sep" aria-hidden="true">•</span>
                <div class="vg-hero__telemetry-item">
                    <span class="vg-hero__telemetry-label"><?php esc_html_e('MONSOON:', 'vietnamguide-premium'); ?></span>
                    <span class="vg-hero__telemetry-val vg-hero__telemetry-val--gold"><?php esc_html_e('Central dry transition', 'vietnamguide-premium'); ?></span>
                </div>
                <span class="vg-hero__telemetry-sep" aria-hidden="true">•</span>
                <div class="vg-hero__telemetry-item">
                    <span class="vg-hero__telemetry-label"><?php esc_html_e('FX:', 'vietnamguide-premium'); ?></span>
                    <span class="vg-hero__telemetry-val"><?php esc_html_e('1 USD ≈ 25,420 VND', 'vietnamguide-premium'); ?></span>
                </div>
                <span class="vg-hero__telemetry-sep" aria-hidden="true">•</span>
                <div class="vg-hero__telemetry-item">
                    <span class="vg-hero__telemetry-label"><?php esc_html_e('VISA:', 'vietnamguide-premium'); ?></span>
                    <span class="vg-hero__telemetry-val vg-hero__telemetry-val--gold"><?php esc_html_e('90-Day Operational', 'vietnamguide-premium'); ?></span>
                </div>
            </div>

            <p class="vg-hero__brand">VietnamGuide.net</p>
            <h1>Vietnam for travelers who choose well.</h1>
            <p class="vg-hero__copy">Curated routes, refined stays, and practical guidance for planning Vietnam with confidence.</p>

            <form class="vg-hero__search-barometer" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <div class="vg-hero__search-wrap">
                    <span class="vg-hero__search-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input
                        type="search"
                        name="s"
                        class="vg-hero__search-field"
                        placeholder="<?php esc_attr_e('Filter corridor, province, or route (e.g. Ha Long, Hoi An, 10 Days)...', 'vietnamguide-premium'); ?>"
                        value="<?php echo esc_attr(get_search_query(false)); ?>"
                        aria-label="<?php esc_attr_e('Search field dossiers and guides', 'vietnamguide-premium'); ?>"
                        autocomplete="off"
                    />
                    <button type="submit" class="vg-hero__search-button" data-vg-event="hero_search_submit">
                        <span><?php esc_html_e('Filter', 'vietnamguide-premium'); ?></span>
                        <span class="vg-hero__search-arrow" aria-hidden="true">&rarr;</span>
                    </button>
                </div>
            </form>

            <div class="vg-hero__quick-chips" aria-label="<?php esc_attr_e('Direct Access Topics', 'vietnamguide-premium'); ?>">
                <span class="vg-hero__quick-chips-title"><?php esc_html_e('Direct Access:', 'vietnamguide-premium'); ?></span>
                <a href="<?php echo esc_url(vg_home_url('itineraries/10-days-in-vietnam')); ?>" class="vg-hero__chip" data-vg-event="hero_chip_click"><?php esc_html_e('10 Days Spine', 'vietnamguide-premium'); ?></a>
                <a href="<?php echo esc_url(vg_home_url('compare/ha-long-bay-vs-lan-ha-bay')); ?>" class="vg-hero__chip" data-vg-event="hero_chip_click"><?php esc_html_e('Ha Long vs Lan Ha', 'vietnamguide-premium'); ?></a>
                <a href="<?php echo esc_url(vg_home_url('destinations/best-things-to-do-in-hoi-an')); ?>" class="vg-hero__chip" data-vg-event="hero_chip_click"><?php esc_html_e('Hoi An Base', 'vietnamguide-premium'); ?></a>
                <a href="<?php echo esc_url(vg_home_url('plan/vietnam-evisa')); ?>" class="vg-hero__chip" data-vg-event="hero_chip_click"><?php esc_html_e('E-Visa 90-Day Rules', 'vietnamguide-premium'); ?></a>
            </div>

            <div class="vg-actions">
                <a class="vg-button" href="<?php echo esc_url(vg_home_url('plan')); ?>" data-vg-event="hero_start_planning">Start planning</a>
                <a class="vg-button vg-button--ghost" href="<?php echo esc_url(vg_home_url('itineraries')); ?>" data-vg-event="hero_see_itineraries">See itineraries</a>
            </div>
        </div>
    </section>

    <section class="vg-section vg-toolkits-showcase" id="decision-engines" aria-labelledby="vg-decision-engines-title">
        <div class="vg-shell">
            <div class="vg-section-heading" data-vg-reveal>
                <p class="vg-kicker"><?php esc_html_e('Logistics Before Departure', 'vietnamguide-premium'); ?></p>
                <h2 id="vg-decision-engines-title"><?php esc_html_e('Decision Engines & Field Toolkits', 'vietnamguide-premium'); ?></h2>
                <p class="vg-toolkits-showcase__lead"><?php esc_html_e('Empirical tools built on street-verified tariffs, official regulatory protocols, and microclimate intelligence to eliminate planning friction.', 'vietnamguide-premium'); ?></p>
            </div>
            <div class="vg-toolkits-showcase-grid">
                <!-- Card 1: Visa Eligibility Checker -->
                <article class="vg-toolkit-card" data-vg-reveal>
                    <div class="vg-toolkit-card__top">
                        <span class="vg-toolkit-card__index">ENGINE 01 // IMMIGRATION</span>
                        <span class="vg-toolkit-card__tag vg-toolkit-card__tag--gold">Official 90-Day</span>
                    </div>
                    <h3 class="vg-toolkit-card__heading"><?php esc_html_e('Visa Eligibility Checker', 'vietnamguide-premium'); ?></h3>
                    <p class="vg-toolkit-card__text"><?php esc_html_e('Verify bilateral visa exemptions (up to 45 days), e-visa protocols, and official tariffs ($25–$50 USD) without commercial broker markups.', 'vietnamguide-premium'); ?></p>
                    <div class="vg-toolkit-card__stats">
                        <div class="vg-toolkit-card__stat">
                            <span class="vg-toolkit-card__stat-label"><?php esc_html_e('Official Fee', 'vietnamguide-premium'); ?></span>
                            <span class="vg-toolkit-card__stat-value">$25 / $50 USD</span>
                        </div>
                        <div class="vg-toolkit-card__stat">
                            <span class="vg-toolkit-card__stat-label"><?php esc_html_e('Standard SLA', 'vietnamguide-premium'); ?></span>
                            <span class="vg-toolkit-card__stat-value">3 Business Days</span>
                        </div>
                    </div>
                    <div class="vg-toolkit-card__cta-wrap">
                        <a href="<?php echo esc_url(vg_home_url('plan/vietnam-evisa')); ?>" class="vg-button vg-button--secondary vg-toolkit-card__btn" data-vg-event="toolkit_visa_click">
                            <span><?php esc_html_e('Launch Visa Guide', 'vietnamguide-premium'); ?></span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </article>

                <!-- Card 2: Daily Travel Cost Calculator -->
                <article class="vg-toolkit-card" data-vg-reveal>
                    <div class="vg-toolkit-card__top">
                        <span class="vg-toolkit-card__index">ENGINE 02 // TARIFF MATRIX</span>
                        <span class="vg-toolkit-card__tag vg-toolkit-card__tag--emerald">Street-Verified</span>
                    </div>
                    <h3 class="vg-toolkit-card__heading"><?php esc_html_e('Daily Travel Cost Calculator', 'vietnamguide-premium'); ?></h3>
                    <p class="vg-toolkit-card__text"><?php esc_html_e('Model accurate daily budgets across Backpack, Smart Comfort, and Boutique Heritage tiers based on actual street-tested tariffs and transit metrics.', 'vietnamguide-premium'); ?></p>
                    <div class="vg-toolkit-card__stats">
                        <div class="vg-toolkit-card__stat">
                            <span class="vg-toolkit-card__stat-label"><?php esc_html_e('Comfort Benchmark', 'vietnamguide-premium'); ?></span>
                            <span class="vg-toolkit-card__stat-value">~$65 – $95 / day</span>
                        </div>
                        <div class="vg-toolkit-card__stat">
                            <span class="vg-toolkit-card__stat-label"><?php esc_html_e('Recon Accuracy', 'vietnamguide-premium'); ?></span>
                            <span class="vg-toolkit-card__stat-value">94.8% Empirical</span>
                        </div>
                    </div>
                    <div class="vg-toolkit-card__cta-wrap">
                        <a href="<?php echo esc_url(vg_home_url('costs/vietnam-travel-cost')); ?>" class="vg-button vg-button--secondary vg-toolkit-card__btn" data-vg-event="toolkit_cost_click">
                            <span><?php esc_html_e('Open Cost Calculator', 'vietnamguide-premium'); ?></span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </article>

                <!-- Card 3: Regional Weather & Climate Matrix -->
                <article class="vg-toolkit-card" data-vg-reveal>
                    <div class="vg-toolkit-card__top">
                        <span class="vg-toolkit-card__index">ENGINE 03 // METEOROLOGY</span>
                        <span class="vg-toolkit-card__tag vg-toolkit-card__tag--navy">3 Microclimates</span>
                    </div>
                    <h3 class="vg-toolkit-card__heading"><?php esc_html_e('Regional Weather & Season Matrix', 'vietnamguide-premium'); ?></h3>
                    <p class="vg-toolkit-card__text"><?php esc_html_e('Vietnam spans three distinct climate systems simultaneously. Calibrate your timing against rainfall curves, typhoon windows, and sea visibility.', 'vietnamguide-premium'); ?></p>
                    <div class="vg-toolkit-card__stats">
                        <div class="vg-toolkit-card__stat">
                            <span class="vg-toolkit-card__stat-label"><?php esc_html_e('Optimal General', 'vietnamguide-premium'); ?></span>
                            <span class="vg-toolkit-card__stat-value">Nov – Apr</span>
                        </div>
                        <div class="vg-toolkit-card__stat">
                            <span class="vg-toolkit-card__stat-label"><?php esc_html_e('Spatial Corridors', 'vietnamguide-premium'); ?></span>
                            <span class="vg-toolkit-card__stat-value">North / Center / South</span>
                        </div>
                    </div>
                    <div class="vg-toolkit-card__cta-wrap">
                        <a href="<?php echo esc_url(vg_home_url('plan/best-time-to-visit-vietnam')); ?>" class="vg-button vg-button--secondary vg-toolkit-card__btn" data-vg-event="toolkit_weather_click">
                            <span><?php esc_html_e('Explore Season Matrix', 'vietnamguide-premium'); ?></span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </article>
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

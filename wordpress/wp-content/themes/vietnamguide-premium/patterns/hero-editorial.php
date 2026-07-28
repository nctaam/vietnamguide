<?php
/**
 * Title: Editorial hero
 * Slug: vietnamguide/hero-editorial
 * Categories: vietnamguide
 * Inserter: true
 * Note: The page template owns the h1; this reusable hero intentionally uses h2.
 */
?>
<!-- wp:group {"align":"full","className":"vg-guide-pattern vg-pattern-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vg-guide-pattern vg-pattern-hero"><!-- wp:html -->
<picture class="vg-pattern-hero__media">
    <source srcset="<?php echo esc_url(get_theme_file_uri('/assets/images/home-hero.webp')); ?>" type="image/webp">
    <img src="<?php echo esc_url(get_theme_file_uri('/assets/images/home-hero.jpg')); ?>" width="1376" height="768" alt="Sunrise over limestone karsts and boats on Ha Long Bay, Vietnam" loading="eager" decoding="async" fetchpriority="high">
</picture>
<!-- /wp:html --><!-- wp:group {"className":"vg-pattern-hero__content","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-pattern-hero__content"><!-- wp:paragraph {"className":"vg-pattern-kicker"} -->
<p class="vg-pattern-kicker">Vietnam field guide</p>
<!-- /wp:paragraph --><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Choose a route that leaves room to notice Vietnam.</h2>
<!-- /wp:heading --><!-- wp:paragraph {"className":"vg-pattern-hero__lede"} -->
<p class="vg-pattern-hero__lede">Clear itineraries, practical evidence, and considered recommendations for a trip that moves at the right pace.</p>
<!-- /wp:paragraph --><!-- wp:buttons {"className":"vg-pattern-actions"} -->
<div class="wp-block-buttons vg-pattern-actions"><!-- wp:button {"className":"vg-pattern-button"} -->
<div class="wp-block-button vg-pattern-button"><a class="wp-block-button__link wp-element-button" href="/plan/">Plan your trip</a></div>
<!-- /wp:button --><!-- wp:button {"className":"vg-pattern-button vg-pattern-button--ghost"} -->
<div class="wp-block-button vg-pattern-button vg-pattern-button--ghost"><a class="wp-block-button__link wp-element-button" href="/itineraries/">Browse itineraries</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

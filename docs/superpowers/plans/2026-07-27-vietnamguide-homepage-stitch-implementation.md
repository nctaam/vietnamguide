# VietnamGuide Stitch Homepage Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a lightweight, responsive WordPress homepage theme for VietnamGuide.net using the approved Stitch desktop/mobile references and deep optimization spec.

**Architecture:** Implement a self-contained custom theme in `wordpress/wp-content/themes/vietnamguide-premium`. Keep homepage content in one PHP data provider, render semantic sections through `front-page.php`, and use progressive CSS/JavaScript for responsive layout, menu state, header state, and restrained reveal motion. Add a static PowerShell contract test plus a browser-renderable HTML fixture because this workspace does not currently provide PHP, WP-CLI, Docker, or a local WordPress runtime.

**Tech Stack:** WordPress PHP templates, `theme.json`, CSS custom properties, vanilla JavaScript, PowerShell static verification, generated WebP/JPEG imagery, browser-based responsive QA.

---

## Scope Check

This plan delivers the homepage foundation only. It includes the global header/footer, homepage data model, homepage sections, reusable block patterns, visual assets, accessibility, performance safeguards, and local static QA. Destination, itinerary, comparison, cost, and article templates remain separate follow-up plans.

## File Map

```text
wordpress/wp-content/themes/vietnamguide-premium/
  style.css                         Theme metadata and minimal root styles.
  theme.json                        Editor/runtime design tokens.
  functions.php                     Theme support, assets, menus, pattern registration.
  inc/homepage-data.php             Stable homepage content and URL helpers.
  header.php                        Accessible responsive site header.
  footer.php                        Trust-focused footer and WordPress footer hook.
  front-page.php                    Semantic homepage composition.
  assets/css/homepage.css           Homepage layout, responsive rules, states, motion.
  assets/js/homepage.js             Mobile menu, header state, reveal enhancement.
  assets/images/home-hero.jpg        Hero fallback image.
  assets/images/home-hero.webp       Optimized hero image.
  assets/images/home-editorial.jpg   Destination section fallback image.
  assets/images/home-editorial.webp  Optimized destination fallback image.
  assets/images/README.md            Image provenance and generation prompts.
  patterns/planning-paths.php        Reusable planning-link block pattern.
  patterns/editorial-itineraries.php Reusable itinerary-row block pattern.
  patterns/decision-guides.php       Reusable comparison-link block pattern.
  patterns/practical-essentials.php  Reusable practical-link block pattern.
ops/
  verify-homepage-theme.ps1          Static theme contract verification.
  build-homepage-images.py           Deterministic image conversion.
qa/
  homepage-preview.html              Browser-renderable responsive fixture.
```

### Task 1: Add the failing theme contract test

**Files:**
- Create: `ops/verify-homepage-theme.ps1`

- [ ] **Step 1: Create the verifier**

```powershell
$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$themeRoot = Join-Path $repoRoot 'wordpress/wp-content/themes/vietnamguide-premium'
$failures = New-Object System.Collections.Generic.List[string]

function Require-File([string] $RelativePath) {
    $path = Join-Path $repoRoot $RelativePath
    if (-not (Test-Path -LiteralPath $path)) {
        $script:failures.Add("missing file: $RelativePath")
    }
}

function Require-Contains([string] $RelativePath, [string] $Needle) {
    $path = Join-Path $repoRoot $RelativePath
    if (-not (Test-Path -LiteralPath $path)) {
        $script:failures.Add("cannot inspect missing file: $RelativePath")
        return
    }

    $content = Get-Content -Raw -LiteralPath $path
    if (-not $content.Contains($Needle)) {
        $script:failures.Add("$RelativePath missing: $Needle")
    }
}

$requiredFiles = @(
    'wordpress/wp-content/themes/vietnamguide-premium/style.css',
    'wordpress/wp-content/themes/vietnamguide-premium/theme.json',
    'wordpress/wp-content/themes/vietnamguide-premium/functions.php',
    'wordpress/wp-content/themes/vietnamguide-premium/inc/homepage-data.php',
    'wordpress/wp-content/themes/vietnamguide-premium/header.php',
    'wordpress/wp-content/themes/vietnamguide-premium/footer.php',
    'wordpress/wp-content/themes/vietnamguide-premium/front-page.php',
    'wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css',
    'wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js',
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero.webp',
    'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-editorial.webp',
    'wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php',
    'wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php',
    'wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php',
    'wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php',
    'qa/homepage-preview.html'
)

$requiredFiles | ForEach-Object { Require-File $_ }

Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/style.css' 'Theme Name: VietnamGuide Premium'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/functions.php' "require_once get_theme_file_path('/inc/homepage-data.php');"
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/functions.php' "register_nav_menus"
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/inc/homepage-data.php' 'function vg_homepage_data(): array'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/header.php' 'data-vg-menu-toggle'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/header.php' 'aria-expanded="false"'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'Vietnam for travelers who choose well.'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/front-page.php' 'data-vg-event="route_selector_click"'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css' '@media (prefers-reduced-motion: reduce)'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css' ':focus-visible'
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js' "matchMedia('(prefers-reduced-motion: reduce)')"
Require-Contains 'wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js' "event.key === 'Escape'"
Require-Contains 'qa/homepage-preview.html' 'VietnamGuide.net'

if ($failures.Count -gt 0) {
    $failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output 'VietnamGuide homepage theme checks passed.'
```

- [ ] **Step 2: Run the verifier and confirm the red state**

Run:

```powershell
powershell -ExecutionPolicy Bypass -File ops/verify-homepage-theme.ps1
```

Expected: exit code `1` with missing-file failures beginning with `style.css`, `theme.json`, and `functions.php`.

- [ ] **Step 3: Commit the failing contract test**

```powershell
git add -- ops/verify-homepage-theme.ps1
git commit -m "test: define homepage theme contract"
```

### Task 2: Build the theme bootstrap and design tokens

**Files:**
- Create: `wordpress/wp-content/themes/vietnamguide-premium/style.css`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/theme.json`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/functions.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/inc/homepage-data.php`

- [ ] **Step 1: Create `style.css`**

```css
/*
Theme Name: VietnamGuide Premium
Theme URI: https://vietnamguide.net
Author: VietnamGuide.net
Description: Editorial travel-planning theme based on the approved Stitch design system.
Version: 0.1.0
Requires at least: 6.6
Requires PHP: 8.1
Text Domain: vietnamguide-premium
*/

html {
  scroll-behavior: smooth;
}

body {
  margin: 0;
}
```

- [ ] **Step 2: Create `theme.json` with the approved tokens**

```json
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "settings": {
    "appearanceTools": true,
    "layout": {
      "contentSize": "760px",
      "wideSize": "1280px"
    },
    "color": {
      "defaultPalette": false,
      "palette": [
        { "slug": "ink", "name": "Ink", "color": "#101417" },
        { "slug": "soft-paper", "name": "Soft Paper", "color": "#F7F4ED" },
        { "slug": "limestone", "name": "Limestone", "color": "#E5DED1" },
        { "slug": "river-jade", "name": "River Jade", "color": "#0E6F5C" },
        { "slug": "deep-river-blue", "name": "Deep River Blue", "color": "#173B56" },
        { "slug": "quiet-gold", "name": "Quiet Gold", "color": "#B98739" },
        { "slug": "clay-coral", "name": "Clay Coral", "color": "#C95F4A" },
        { "slug": "white", "name": "White", "color": "#FFFFFF" }
      ]
    },
    "spacing": {
      "spacingSizes": [
        { "slug": "xs", "name": "XS", "size": "8px" },
        { "slug": "sm", "name": "SM", "size": "16px" },
        { "slug": "md", "name": "MD", "size": "24px" },
        { "slug": "lg", "name": "LG", "size": "40px" },
        { "slug": "xl", "name": "XL", "size": "72px" },
        { "slug": "xxl", "name": "XXL", "size": "112px" }
      ]
    },
    "typography": {
      "fluid": true,
      "fontFamilies": [
        {
          "slug": "display",
          "name": "Source Serif 4",
          "fontFamily": "'Source Serif 4', Georgia, serif"
        },
        {
          "slug": "body",
          "name": "Hanken Grotesk",
          "fontFamily": "'Hanken Grotesk', 'Segoe UI', sans-serif"
        }
      ]
    }
  },
  "styles": {
    "color": {
      "background": "#F7F4ED",
      "text": "#101417"
    },
    "typography": {
      "fontFamily": "'Hanken Grotesk', 'Segoe UI', sans-serif",
      "fontSize": "18px",
      "lineHeight": "1.65"
    }
  }
}
```

- [ ] **Step 3: Create `functions.php`**

```php
<?php
/**
 * VietnamGuide Premium theme bootstrap.
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once get_theme_file_path('/inc/homepage-data.php');

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_editor_style('assets/css/homepage.css');

    register_nav_menus([
        'primary' => __('Primary navigation', 'vietnamguide-premium'),
        'footer'  => __('Footer navigation', 'vietnamguide-premium'),
    ]);
});

add_action('wp_enqueue_scripts', static function (): void {
    $version = wp_get_theme()->get('Version');

    wp_enqueue_style(
        'vietnamguide-homepage',
        get_theme_file_uri('/assets/css/homepage.css'),
        [],
        $version
    );

    wp_enqueue_script(
        'vietnamguide-homepage',
        get_theme_file_uri('/assets/js/homepage.js'),
        [],
        $version,
        true
    );
});

add_action('init', static function (): void {
    register_block_pattern_category(
        'vietnamguide',
        ['label' => __('VietnamGuide', 'vietnamguide-premium')]
    );
});

function vg_primary_menu_fallback(): void
{
    echo '<ul class="vg-nav-list">';
    foreach (vg_homepage_data()['navigation'] as $item) {
        printf(
            '<li><a href="%1$s">%2$s</a></li>',
            esc_url($item['url']),
            esc_html($item['label'])
        );
    }
    echo '</ul>';
}
```

- [ ] **Step 4: Create `inc/homepage-data.php`**

```php
<?php
/**
 * Curated homepage content kept separate from the templates.
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_home_url(string $path): string
{
    return home_url('/' . trim($path, '/') . '/');
}

function vg_homepage_data(): array
{
    return [
        'navigation' => [
            ['label' => 'Plan', 'url' => vg_home_url('plan')],
            ['label' => 'Destinations', 'url' => vg_home_url('destinations')],
            ['label' => 'Itineraries', 'url' => vg_home_url('itineraries')],
            ['label' => 'Compare', 'url' => vg_home_url('compare')],
            ['label' => 'Costs', 'url' => vg_home_url('costs')],
        ],
        'trip_lengths' => [
            ['label' => '7 days', 'url' => vg_home_url('itineraries/7-days-in-vietnam')],
            ['label' => '10 days', 'url' => vg_home_url('itineraries/10-days-in-vietnam')],
            ['label' => '14 days', 'url' => vg_home_url('itineraries/14-days-in-vietnam')],
            ['label' => '21 days', 'url' => vg_home_url('itineraries/21-days-in-vietnam')],
        ],
        'travel_styles' => [
            ['label' => 'First trip', 'url' => vg_home_url('plan/vietnam-for-first-time-visitors')],
            ['label' => 'Food', 'url' => vg_home_url('itineraries/vietnam-food-itinerary')],
            ['label' => 'Beach', 'url' => vg_home_url('itineraries/vietnam-beach-itinerary')],
            ['label' => 'Family', 'url' => vg_home_url('itineraries/vietnam-family-itinerary')],
            ['label' => 'Premium', 'url' => vg_home_url('itineraries/vietnam-luxury-itinerary')],
        ],
        'itineraries' => [
            ['eyebrow' => 'First journey', 'title' => '10 days: north, center, south', 'fit' => 'Best for a first trip that needs one coherent national overview.', 'url' => vg_home_url('itineraries/10-days-in-vietnam')],
            ['eyebrow' => 'More breathing room', 'title' => '14 days: Vietnam at a calmer pace', 'fit' => 'Best for travelers who want stronger place depth and fewer rushed transfers.', 'url' => vg_home_url('itineraries/14-days-in-vietnam')],
            ['eyebrow' => 'Landscape-led', 'title' => 'Northern Vietnam', 'fit' => 'Best for mountain roads, limestone country, Hanoi, and the bays.', 'url' => vg_home_url('itineraries/northern-vietnam-itinerary')],
            ['eyebrow' => 'Refined comfort', 'title' => 'Premium Vietnam', 'fit' => 'Best for couples and families who want stronger stays and easier logistics.', 'url' => vg_home_url('itineraries/vietnam-luxury-itinerary')],
        ],
        'destinations' => [
            ['title' => 'Hanoi', 'best_for' => 'Food, history, and a confident first arrival.', 'skip_if' => 'You want a quiet coastal base.', 'url' => vg_home_url('destinations/hanoi')],
            ['title' => 'Hoi An', 'best_for' => 'Walkable evenings, food, and a slower center.', 'skip_if' => 'You want a major-city itinerary.', 'url' => vg_home_url('destinations/hoi-an')],
            ['title' => 'Ninh Binh', 'best_for' => 'Karst landscapes without an overnight cruise.', 'skip_if' => 'You dislike early starts and rural transfers.', 'url' => vg_home_url('destinations/ninh-binh')],
            ['title' => 'Lan Ha Bay', 'best_for' => 'A calmer bay experience with Cat Ba access.', 'skip_if' => 'You need the most iconic Ha Long checklist.', 'url' => vg_home_url('destinations/lan-ha-bay')],
            ['title' => 'Ha Giang', 'best_for' => 'High-impact mountain scenery and road journeys.', 'skip_if' => 'You have limited time or dislike long road days.', 'url' => vg_home_url('destinations/ha-giang')],
            ['title' => 'Phu Quoc', 'best_for' => 'An easy beach finish with resort choice.', 'skip_if' => 'You want a culture-first final stop.', 'url' => vg_home_url('destinations/phu-quoc')],
        ],
        'comparisons' => [
            ['title' => 'Ha Long Bay vs Lan Ha Bay', 'verdict' => 'Choose icon value or choose a calmer route.', 'url' => vg_home_url('compare/ha-long-bay-vs-lan-ha-bay')],
            ['title' => 'Sapa vs Ha Giang', 'verdict' => 'Choose easier access or choose the stronger road journey.', 'url' => vg_home_url('compare/sapa-vs-ha-giang')],
            ['title' => 'Hanoi vs Ho Chi Minh City', 'verdict' => 'Choose layered history or choose southern energy.', 'url' => vg_home_url('compare/hanoi-vs-ho-chi-minh-city')],
        ],
        'essentials' => [
            ['title' => 'Vietnam e-visa', 'meta' => 'Entry rules and common application mistakes.', 'url' => vg_home_url('plan/vietnam-evisa')],
            ['title' => 'Best time to visit', 'meta' => 'Plan around regions, not one national forecast.', 'url' => vg_home_url('plan/best-time-to-visit-vietnam')],
            ['title' => 'Travel cost', 'meta' => 'Realistic budget ranges for different travel styles.', 'url' => vg_home_url('costs/vietnam-travel-cost')],
            ['title' => 'SIM and eSIM', 'meta' => 'Stay connected from arrival without overspending.', 'url' => vg_home_url('plan/sim-esim-vietnam')],
            ['title' => 'Getting around', 'meta' => 'Flights, trains, buses, transfers, and local transport.', 'url' => vg_home_url('plan/getting-around-vietnam')],
            ['title' => 'Safety', 'meta' => 'Practical risk management without alarmism.', 'url' => vg_home_url('plan/is-vietnam-safe')],
        ],
    ];
}
```

- [ ] **Step 5: Run the verifier**

Run:

```powershell
powershell -ExecutionPolicy Bypass -File ops/verify-homepage-theme.ps1
```

Expected: still fails because templates, CSS, JavaScript, images, patterns, and QA fixture do not exist; bootstrap-related failures disappear.

- [ ] **Step 6: Commit the bootstrap**

```powershell
git add -- wordpress/wp-content/themes/vietnamguide-premium/style.css wordpress/wp-content/themes/vietnamguide-premium/theme.json wordpress/wp-content/themes/vietnamguide-premium/functions.php wordpress/wp-content/themes/vietnamguide-premium/inc/homepage-data.php
git commit -m "feat: scaffold VietnamGuide homepage theme"
```

### Task 3: Add semantic header, footer, and homepage templates

**Files:**
- Create: `wordpress/wp-content/themes/vietnamguide-premium/header.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/footer.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/front-page.php`

- [ ] **Step 1: Create `header.php`**

```php
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('vg-site'); ?>>
<?php wp_body_open(); ?>
<a class="vg-skip-link" href="#main"><?php esc_html_e('Skip to content', 'vietnamguide-premium'); ?></a>
<header class="vg-site-header" data-vg-header>
    <div class="vg-site-header__inner">
        <a class="vg-wordmark" href="<?php echo esc_url(home_url('/')); ?>" rel="home">VietnamGuide.net</a>
        <button
            class="vg-menu-toggle"
            type="button"
            aria-controls="vg-primary-navigation"
            aria-expanded="false"
            data-vg-menu-toggle
        >
            <span class="vg-menu-toggle__label"><?php esc_html_e('Menu', 'vietnamguide-premium'); ?></span>
            <span class="vg-menu-toggle__icon" aria-hidden="true"></span>
        </button>
        <nav
            id="vg-primary-navigation"
            class="vg-primary-navigation"
            aria-label="<?php esc_attr_e('Primary navigation', 'vietnamguide-premium'); ?>"
            data-vg-navigation
        >
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'vg-nav-list',
                'fallback_cb'    => 'vg_primary_menu_fallback',
            ]);
            ?>
        </nav>
        <a class="vg-header-action" href="<?php echo esc_url(vg_home_url('plan')); ?>">
            <?php esc_html_e('Plan your trip', 'vietnamguide-premium'); ?>
        </a>
    </div>
</header>
```

- [ ] **Step 2: Create `footer.php`**

```php
<?php $footer_data = vg_homepage_data(); ?>
<footer class="vg-site-footer">
    <div class="vg-site-footer__inner">
        <div class="vg-site-footer__brand">
            <a class="vg-wordmark" href="<?php echo esc_url(home_url('/')); ?>">VietnamGuide.net</a>
            <p><?php esc_html_e('Choose Vietnam well.', 'vietnamguide-premium'); ?></p>
        </div>
        <nav aria-label="<?php esc_attr_e('Footer navigation', 'vietnamguide-premium'); ?>">
            <ul class="vg-footer-links">
                <?php foreach ($footer_data['navigation'] as $item) : ?>
                    <li><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <nav aria-label="<?php esc_attr_e('Trust and legal', 'vietnamguide-premium'); ?>">
            <ul class="vg-footer-links vg-footer-links--legal">
                <li><a href="<?php echo esc_url(vg_home_url('about')); ?>"><?php esc_html_e('About', 'vietnamguide-premium'); ?></a></li>
                <li><a href="<?php echo esc_url(vg_home_url('contact')); ?>"><?php esc_html_e('Contact', 'vietnamguide-premium'); ?></a></li>
                <li><a href="<?php echo esc_url(vg_home_url('affiliate-disclosure')); ?>"><?php esc_html_e('Affiliate disclosure', 'vietnamguide-premium'); ?></a></li>
                <li><a href="<?php echo esc_url(vg_home_url('source-policy')); ?>"><?php esc_html_e('Source policy', 'vietnamguide-premium'); ?></a></li>
            </ul>
        </nav>
    </div>
    <p class="vg-site-footer__copyright">
        &copy; <?php echo esc_html(wp_date('Y')); ?> VietnamGuide.net
    </p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
```

- [ ] **Step 3: Create `front-page.php`**

```php
<?php
get_header();
$data = vg_homepage_data();
?>
<main id="main">
    <section class="vg-hero">
        <picture class="vg-hero__media">
            <source srcset="<?php echo esc_url(get_theme_file_uri('/assets/images/home-hero.webp')); ?>" type="image/webp">
            <img
                src="<?php echo esc_url(get_theme_file_uri('/assets/images/home-hero.jpg')); ?>"
                width="2400"
                height="1350"
                alt="<?php esc_attr_e('Rice terraces, river, and limestone mountains in northern Vietnam at dawn', 'vietnamguide-premium'); ?>"
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
                            <span class="vg-editorial-rows__number"><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span>
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
                <source srcset="<?php echo esc_url(get_theme_file_uri('/assets/images/home-editorial.webp')); ?>" type="image/webp">
                <img
                    src="<?php echo esc_url(get_theme_file_uri('/assets/images/home-editorial.jpg')); ?>"
                    width="1800"
                    height="1350"
                    alt="<?php esc_attr_e('A wooden boat moving between limestone karsts in Lan Ha Bay', 'vietnamguide-premium'); ?>"
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
```

- [ ] **Step 4: Run the verifier**

Run:

```powershell
powershell -ExecutionPolicy Bypass -File ops/verify-homepage-theme.ps1
```

Expected: template and required-copy failures disappear; CSS, JavaScript, image, pattern, and QA fixture failures remain.

- [ ] **Step 5: Commit templates**

```powershell
git add -- wordpress/wp-content/themes/vietnamguide-premium/header.php wordpress/wp-content/themes/vietnamguide-premium/footer.php wordpress/wp-content/themes/vietnamguide-premium/front-page.php
git commit -m "feat: add semantic homepage templates"
```

### Task 4: Add responsive styling and progressive enhancement

**Files:**
- Create: `wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js`

- [ ] **Step 1: Create the CSS token layer and layout**

The stylesheet must define these root tokens exactly:

```css
:root {
  --vg-ink: #101417;
  --vg-paper: #f7f4ed;
  --vg-limestone: #e5ded1;
  --vg-jade: #0e6f5c;
  --vg-blue: #173b56;
  --vg-gold: #b98739;
  --vg-coral: #c95f4a;
  --vg-white: #ffffff;
  --vg-line: rgba(16, 20, 23, .16);
  --vg-display: 'Source Serif 4', Georgia, serif;
  --vg-body: 'Hanken Grotesk', 'Segoe UI', sans-serif;
  --vg-wide: 1280px;
  --vg-reading: 760px;
  --vg-header-height: 76px;
}
```

Continue the file with this implementation:

```css
*,
*::before,
*::after {
  box-sizing: border-box;
}

html {
  scroll-behavior: smooth;
}

body {
  margin: 0;
  color: var(--vg-ink);
  background: var(--vg-paper);
  font-family: var(--vg-body);
  font-size: 18px;
  line-height: 1.65;
}

body.vg-menu-open {
  overflow: hidden;
}

img {
  display: block;
  max-width: 100%;
  height: auto;
}

a {
  color: inherit;
}

a:focus-visible,
button:focus-visible,
input:focus-visible {
  outline: 3px solid var(--vg-gold);
  outline-offset: 4px;
}

.vg-skip-link {
  position: fixed;
  z-index: 1000;
  top: 12px;
  left: 12px;
  padding: 10px 14px;
  background: var(--vg-white);
  color: var(--vg-ink);
  transform: translateY(-160%);
}

.vg-skip-link:focus {
  transform: translateY(0);
}

.vg-shell {
  width: min(calc(100% - 48px), var(--vg-wide));
  margin-inline: auto;
}

.vg-section {
  padding-block: clamp(72px, 9vw, 112px);
}

.vg-section h2 {
  max-width: 780px;
  margin: 0;
  font-family: var(--vg-display);
  font-size: clamp(38px, 5vw, 72px);
  font-weight: 650;
  line-height: 1.02;
  letter-spacing: -.025em;
}

.vg-kicker {
  margin: 0 0 18px;
  color: var(--vg-gold);
  font-size: 12px;
  font-weight: 800;
  letter-spacing: .12em;
  text-transform: uppercase;
}

.vg-site-header {
  position: fixed;
  z-index: 50;
  inset: 0 0 auto;
  height: var(--vg-header-height);
  color: var(--vg-ink);
  background: rgba(247, 244, 237, .96);
  border-bottom: 1px solid var(--vg-line);
  transition: color 180ms ease, background 180ms ease, border-color 180ms ease;
}

.home .vg-site-header {
  color: var(--vg-white);
  background: transparent;
  border-bottom-color: rgba(255, 255, 255, .22);
}

.home .vg-site-header.is-scrolled {
  color: var(--vg-ink);
  background: rgba(247, 244, 237, .96);
  border-bottom-color: var(--vg-line);
  backdrop-filter: blur(16px);
}

.vg-site-header__inner {
  width: min(calc(100% - 48px), var(--vg-wide));
  height: 100%;
  margin-inline: auto;
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 28px;
}

.vg-wordmark {
  font-family: var(--vg-display);
  font-size: 26px;
  font-weight: 700;
  text-decoration: none;
}

.vg-primary-navigation {
  justify-self: center;
}

.vg-nav-list,
.vg-footer-links {
  display: flex;
  gap: 24px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.vg-nav-list a,
.vg-header-action {
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
}

.vg-header-action {
  padding: 10px 15px;
  border: 1px solid currentColor;
}

.vg-menu-toggle {
  display: none;
}

.vg-hero {
  position: relative;
  min-height: 100svh;
  display: grid;
  align-items: end;
  overflow: hidden;
  color: var(--vg-white);
  background: var(--vg-ink);
}

.vg-hero__media,
.vg-hero__media img,
.vg-hero__shade {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.vg-hero__media img {
  object-fit: cover;
}

.vg-hero__shade {
  background: linear-gradient(90deg, rgba(7, 16, 13, .82) 0%, rgba(7, 16, 13, .44) 48%, rgba(7, 16, 13, .16) 72%, rgba(7, 16, 13, .4) 100%);
}

.vg-hero__content {
  position: relative;
  z-index: 2;
  padding-block: calc(var(--vg-header-height) + 96px) 68px;
}

.vg-hero__brand {
  margin: 0 0 26px;
  font-family: var(--vg-display);
  font-size: clamp(24px, 3vw, 40px);
}

.vg-hero h1 {
  max-width: 920px;
  margin: 0;
  font-family: var(--vg-display);
  font-size: clamp(54px, 8vw, 118px);
  font-weight: 650;
  line-height: .9;
  letter-spacing: -.045em;
}

.vg-hero__copy {
  max-width: 590px;
  margin: 28px 0 32px;
  font-size: clamp(17px, 2vw, 22px);
}

.vg-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.vg-button {
  display: inline-flex;
  min-height: 48px;
  align-items: center;
  justify-content: center;
  padding: 11px 20px;
  border: 1px solid transparent;
  background: var(--vg-jade);
  color: var(--vg-white);
  font-size: 15px;
  font-weight: 800;
  text-decoration: none;
  transition: background 160ms ease, color 160ms ease, transform 160ms ease;
}

.vg-button:hover {
  transform: translateY(-2px);
}

.vg-button--ghost {
  background: rgba(255, 255, 255, .08);
  border-color: rgba(255, 255, 255, .7);
}

.vg-button--light {
  background: var(--vg-white);
  color: var(--vg-jade);
}

.vg-planning-paths {
  background: var(--vg-white);
}

.vg-planning-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: clamp(48px, 8vw, 120px);
  margin-top: 64px;
}

.vg-planning-grid h3 {
  margin: 0 0 18px;
  font-size: 15px;
  text-transform: uppercase;
  letter-spacing: .08em;
}

.vg-link-list,
.vg-editorial-rows,
.vg-destination-list,
.vg-comparison-list,
.vg-essential-grid {
  margin: 0;
  padding: 0;
  list-style: none;
}

.vg-link-list li {
  border-top: 1px solid var(--vg-line);
}

.vg-link-list li:last-child {
  border-bottom: 1px solid var(--vg-line);
}

.vg-link-list a {
  display: flex;
  justify-content: space-between;
  padding: 16px 0;
  font-size: clamp(20px, 2.5vw, 30px);
  text-decoration: none;
}

.vg-itineraries {
  background: var(--vg-paper);
}

.vg-section-heading {
  margin-bottom: 56px;
}

.vg-editorial-rows li {
  border-top: 1px solid var(--vg-line);
}

.vg-editorial-rows li:last-child {
  border-bottom: 1px solid var(--vg-line);
}

.vg-editorial-rows a {
  display: grid;
  grid-template-columns: 72px minmax(240px, 1fr) minmax(280px, .8fr) auto;
  gap: 28px;
  align-items: center;
  padding: 28px 0;
  text-decoration: none;
}

.vg-editorial-rows__number {
  color: var(--vg-gold);
  font-family: var(--vg-display);
  font-size: 38px;
}

.vg-editorial-rows small,
.vg-editorial-rows strong {
  display: block;
}

.vg-editorial-rows small {
  margin-bottom: 5px;
  text-transform: uppercase;
  letter-spacing: .08em;
}

.vg-editorial-rows strong {
  font-family: var(--vg-display);
  font-size: 28px;
}

.vg-destinations {
  background: var(--vg-limestone);
}

.vg-destinations__layout {
  display: grid;
  grid-template-columns: minmax(0, .92fr) minmax(0, 1.08fr);
  gap: clamp(48px, 8vw, 112px);
  align-items: start;
}

.vg-destinations__media {
  position: sticky;
  top: calc(var(--vg-header-height) + 28px);
  min-height: 680px;
  overflow: hidden;
}

.vg-destinations__media img {
  width: 100%;
  height: 100%;
  min-height: 680px;
  object-fit: cover;
}

.vg-destination-list {
  margin-top: 48px;
}

.vg-destination-list li {
  border-top: 1px solid rgba(16, 20, 23, .22);
}

.vg-destination-list a {
  display: grid;
  grid-template-columns: 150px 1fr;
  gap: 8px 26px;
  padding: 22px 0;
  text-decoration: none;
}

.vg-destination-list strong {
  grid-row: 1 / 3;
  font-family: var(--vg-display);
  font-size: 27px;
}

.vg-destination-list span {
  font-size: 15px;
}

.vg-comparisons {
  color: var(--vg-white);
  background: var(--vg-ink);
}

.vg-comparison-list {
  margin-top: 58px;
}

.vg-comparison-list li {
  border-top: 1px solid rgba(255, 255, 255, .22);
}

.vg-comparison-list li:last-child {
  border-bottom: 1px solid rgba(255, 255, 255, .22);
}

.vg-comparison-list a {
  display: grid;
  grid-template-columns: minmax(260px, 1fr) minmax(260px, 1fr) auto;
  gap: 32px;
  padding: 28px 0;
  text-decoration: none;
}

.vg-comparison-list strong {
  font-family: var(--vg-display);
  font-size: 30px;
}

.vg-essential-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin-top: 56px;
  border-top: 1px solid var(--vg-line);
}

.vg-essential-grid li {
  border-bottom: 1px solid var(--vg-line);
}

.vg-essential-grid li:nth-child(odd) {
  border-right: 1px solid var(--vg-line);
}

.vg-essential-grid a {
  display: grid;
  min-height: 190px;
  padding: 28px;
  align-content: space-between;
  text-decoration: none;
}

.vg-essential-grid strong {
  font-family: var(--vg-display);
  font-size: 29px;
}

.vg-newsletter {
  color: var(--vg-white);
  background: var(--vg-jade);
}

.vg-newsletter__inner {
  display: grid;
  grid-template-columns: 1.2fr .8fr auto;
  gap: 48px;
  align-items: end;
}

.vg-newsletter h2 {
  max-width: 680px;
}

.vg-site-footer {
  padding: 64px 24px 28px;
  color: var(--vg-white);
  background: #08110e;
}

.vg-site-footer__inner {
  width: min(100%, var(--vg-wide));
  margin-inline: auto;
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 56px;
}

.vg-footer-links {
  flex-direction: column;
  gap: 8px;
}

.vg-footer-links a {
  text-decoration: none;
}

.vg-site-footer__copyright {
  width: min(100%, var(--vg-wide));
  margin: 48px auto 0;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, .16);
  font-size: 13px;
}

[data-vg-reveal] {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 520ms ease, transform 520ms ease;
}

[data-vg-reveal].is-visible {
  opacity: 1;
  transform: translateY(0);
}

@media (max-width: 960px) {
  .vg-header-action {
    display: none;
  }

  .vg-site-header__inner {
    grid-template-columns: auto auto;
    justify-content: space-between;
  }

  .vg-menu-toggle {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    border: 0;
    background: transparent;
    color: inherit;
    font: inherit;
    font-weight: 800;
  }

  .vg-menu-toggle__icon,
  .vg-menu-toggle__icon::before,
  .vg-menu-toggle__icon::after {
    width: 22px;
    height: 2px;
    display: block;
    background: currentColor;
    content: '';
  }

  .vg-menu-toggle__icon::before {
    transform: translateY(-7px);
  }

  .vg-menu-toggle__icon::after {
    transform: translateY(5px);
  }

  .vg-primary-navigation {
    position: fixed;
    inset: var(--vg-header-height) 0 auto;
    display: none;
    padding: 24px;
    color: var(--vg-ink);
    background: var(--vg-paper);
    border-bottom: 1px solid var(--vg-line);
  }

  .vg-primary-navigation.is-open {
    display: block;
  }

  .vg-nav-list {
    flex-direction: column;
  }

  .vg-editorial-rows a {
    grid-template-columns: 54px 1fr auto;
  }

  .vg-editorial-rows a > span:nth-child(3) {
    grid-column: 2 / 4;
  }

  .vg-destinations__layout,
  .vg-newsletter__inner,
  .vg-site-footer__inner {
    grid-template-columns: 1fr;
  }

  .vg-destinations__media {
    position: relative;
    top: auto;
    min-height: 480px;
  }

  .vg-destinations__media img {
    min-height: 480px;
  }
}

@media (max-width: 760px) {
  :root {
    --vg-header-height: 66px;
  }

  body {
    font-size: 16px;
  }

  .vg-shell,
  .vg-site-header__inner {
    width: min(calc(100% - 32px), var(--vg-wide));
  }

  .vg-section {
    padding-block: 72px;
  }

  .vg-hero {
    min-height: 760px;
  }

  .vg-hero__content {
    padding-bottom: 54px;
  }

  .vg-hero h1 {
    font-size: clamp(52px, 17vw, 76px);
  }

  .vg-actions,
  .vg-actions .vg-button {
    width: 100%;
  }

  .vg-planning-grid,
  .vg-essential-grid {
    grid-template-columns: 1fr;
  }

  .vg-essential-grid li:nth-child(odd) {
    border-right: 0;
  }

  .vg-editorial-rows a {
    grid-template-columns: 44px 1fr auto;
    gap: 14px;
  }

  .vg-editorial-rows strong {
    font-size: 23px;
  }

  .vg-destination-list a {
    grid-template-columns: 1fr;
  }

  .vg-destination-list strong {
    grid-row: auto;
  }

  .vg-comparison-list a {
    grid-template-columns: 1fr auto;
  }

  .vg-comparison-list a > span:nth-child(2) {
    grid-column: 1 / 3;
  }
}

@media (prefers-reduced-motion: reduce) {
  html {
    scroll-behavior: auto;
  }

  *,
  *::before,
  *::after {
    animation-duration: .01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: .01ms !important;
    transition-delay: 0ms !important;
  }

  [data-vg-reveal] {
    opacity: 1;
    transform: none;
  }
}
```

- [ ] **Step 2: Create `homepage.js`**

```javascript
(() => {
  const header = document.querySelector('[data-vg-header]');
  const toggle = document.querySelector('[data-vg-menu-toggle]');
  const navigation = document.querySelector('[data-vg-navigation]');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const closeMenu = () => {
    if (!toggle || !navigation) return;
    toggle.setAttribute('aria-expanded', 'false');
    navigation.classList.remove('is-open');
    document.body.classList.remove('vg-menu-open');
  };

  if (toggle && navigation) {
    toggle.addEventListener('click', () => {
      const open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open));
      navigation.classList.toggle('is-open', !open);
      document.body.classList.toggle('vg-menu-open', !open);
    });

    navigation.addEventListener('click', (event) => {
      if (event.target.closest('a')) closeMenu();
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeMenu();
        toggle.focus();
      }
    });
  }

  const updateHeader = () => {
    if (header) header.classList.toggle('is-scrolled', window.scrollY > 32);
  };

  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });

  const revealItems = document.querySelectorAll('[data-vg-reveal]');
  if (reduceMotion || !('IntersectionObserver' in window)) {
    revealItems.forEach((item) => item.classList.add('is-visible'));
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.16 });

  revealItems.forEach((item) => observer.observe(item));
})();
```

- [ ] **Step 3: Run the verifier**

Run:

```powershell
powershell -ExecutionPolicy Bypass -File ops/verify-homepage-theme.ps1
```

Expected: CSS/JavaScript failures disappear; image, pattern, and QA fixture failures remain.

- [ ] **Step 4: Commit styling and behavior**

```powershell
git add -- wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js
git commit -m "feat: style and animate Stitch homepage"
```

### Task 5: Produce optimized editorial imagery

**Files:**
- Create: `wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero.png`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-editorial.png`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero.jpg`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero.webp`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-editorial.jpg`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-editorial.webp`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/assets/images/README.md`
- Create: `ops/build-homepage-images.py`

- [ ] **Step 1: Generate the hero image with the image generation skill**

Use this exact prompt:

```text
Wide cinematic editorial travel photograph of northern Vietnam at dawn, layered limestone mountains and rice terraces with a narrow river leading into the distance, subtle human scale from one small traditional boat, calm deep forest green and warm harvest-gold light, premium travel magazine realism, natural atmosphere, large quiet shadow area on the left for white website headline text, no typography, no logos, no collage, no UI, 16:9 landscape.
```

Save the result to:

```text
wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero.png
```

- [ ] **Step 2: Generate the editorial image**

Use this exact prompt:

```text
Editorial travel photograph of a wooden boat moving through Lan Ha Bay in Vietnam, limestone karsts, soft overcast morning light, restrained deep green and sandstone palette, authentic premium magazine photography, calm open water with strong depth, no text, no logos, no collage, no UI, 4:3 landscape.
```

Save the result to:

```text
wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-editorial.png
```

- [ ] **Step 3: Create the deterministic converter**

```python
from pathlib import Path
from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
IMAGE_DIR = ROOT / "wordpress/wp-content/themes/vietnamguide-premium/assets/images"

for stem, max_width in (("home-hero", 2400), ("home-editorial", 1800)):
    source = IMAGE_DIR / f"{stem}.png"
    image = Image.open(source).convert("RGB")
    if image.width > max_width:
        height = round(image.height * max_width / image.width)
        image = image.resize((max_width, height), Image.Resampling.LANCZOS)
    image.save(IMAGE_DIR / f"{stem}.jpg", quality=88, optimize=True, progressive=True)
    image.save(IMAGE_DIR / f"{stem}.webp", format="WEBP", quality=84, method=6)
```

- [ ] **Step 4: Convert the images**

Run:

```powershell
python ops/build-homepage-images.py
```

Expected: four optimized files are created and each has a non-zero file size.

- [ ] **Step 5: Document image provenance**

Create `assets/images/README.md` containing the two prompts, generation date `2026-07-27`, the tool name used, filenames, and the note `No embedded text, logo, or third-party trademark.`

- [ ] **Step 6: Wire the images into `front-page.php`**

Use a `<picture>` element for the hero with WebP first and JPEG fallback. Set `width="2400"`, the actual converted height, `fetchpriority="high"`, and alt text `Rice terraces, river, and limestone mountains in northern Vietnam at dawn`.

Use the editorial image in the destination section with WebP/JPEG sources, `loading="lazy"`, explicit dimensions, and alt text `A wooden boat moving between limestone karsts in Lan Ha Bay`.

- [ ] **Step 7: Commit image assets**

```powershell
git add -- ops/build-homepage-images.py wordpress/wp-content/themes/vietnamguide-premium/assets/images wordpress/wp-content/themes/vietnamguide-premium/front-page.php
git commit -m "feat: add optimized Vietnam editorial imagery"
```

### Task 6: Add reusable WordPress patterns

**Files:**
- Create: `wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php`

- [ ] **Step 1: Create pattern headers**

Use these exact WordPress pattern headers.

```php
<?php
/**
 * Title: Planning paths
 * Slug: vietnamguide/planning-paths
 * Categories: vietnamguide
 * Inserter: true
 */
?>
```

```php
<?php
/**
 * Title: Editorial itineraries
 * Slug: vietnamguide/editorial-itineraries
 * Categories: vietnamguide
 * Inserter: true
 */
?>
```

```php
<?php
/**
 * Title: Decision guides
 * Slug: vietnamguide/decision-guides
 * Categories: vietnamguide
 * Inserter: true
 */
?>
```

```php
<?php
/**
 * Title: Practical essentials
 * Slug: vietnamguide/practical-essentials
 * Categories: vietnamguide
 * Inserter: true
 */
?>
```

- [ ] **Step 2: Add block markup**

After the PHP header, use these exact pattern bodies.

`planning-paths.php`:

```html
<!-- wp:group {"className":"vg-section vg-planning-paths","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-section vg-planning-paths"><!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Build the right trip</p>
<!-- /wp:paragraph --><!-- wp:heading -->
<h2 class="wp-block-heading">Start with time. Then choose how you want Vietnam to feel.</h2>
<!-- /wp:heading --><!-- wp:columns {"className":"vg-planning-grid"} -->
<div class="wp-block-columns vg-planning-grid"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Choose your trip length</h3><!-- /wp:heading --><!-- wp:list {"className":"vg-link-list"} -->
<ul class="vg-link-list"><li><a href="/itineraries/7-days-in-vietnam/">7 days</a></li><li><a href="/itineraries/10-days-in-vietnam/">10 days</a></li><li><a href="/itineraries/14-days-in-vietnam/">14 days</a></li><li><a href="/itineraries/21-days-in-vietnam/">21 days</a></li></ul>
<!-- /wp:list --></div><!-- /wp:column --><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Choose your travel style</h3><!-- /wp:heading --><!-- wp:list {"className":"vg-link-list"} -->
<ul class="vg-link-list"><li><a href="/plan/vietnam-for-first-time-visitors/">First trip</a></li><li><a href="/itineraries/vietnam-food-itinerary/">Food</a></li><li><a href="/itineraries/vietnam-beach-itinerary/">Beach</a></li><li><a href="/itineraries/vietnam-family-itinerary/">Family</a></li><li><a href="/itineraries/vietnam-luxury-itinerary/">Premium</a></li></ul>
<!-- /wp:list --></div><!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
```

`editorial-itineraries.php`:

```html
<!-- wp:group {"className":"vg-section vg-itineraries","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-section vg-itineraries"><!-- wp:paragraph {"className":"vg-kicker"} --><p class="vg-kicker">Signature itineraries</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Routes built around pace, not a checklist.</h2><!-- /wp:heading --><!-- wp:list {"ordered":true,"className":"vg-editorial-rows"} -->
<ol class="vg-editorial-rows"><li><a href="/itineraries/10-days-in-vietnam/"><strong>10 days: north, center, south</strong><span>Best for a first trip that needs one coherent national overview.</span></a></li><li><a href="/itineraries/14-days-in-vietnam/"><strong>14 days: Vietnam at a calmer pace</strong><span>Best for travelers who want stronger place depth and fewer rushed transfers.</span></a></li><li><a href="/itineraries/northern-vietnam-itinerary/"><strong>Northern Vietnam</strong><span>Best for mountain roads, limestone country, Hanoi, and the bays.</span></a></li><li><a href="/itineraries/vietnam-luxury-itinerary/"><strong>Premium Vietnam</strong><span>Best for stronger stays and easier logistics.</span></a></li></ol>
<!-- /wp:list --></div>
<!-- /wp:group -->
```

`decision-guides.php`:

```html
<!-- wp:group {"className":"vg-section vg-comparisons","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-section vg-comparisons"><!-- wp:paragraph {"className":"vg-kicker"} --><p class="vg-kicker">Decision guides</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Make the difficult choices quickly.</h2><!-- /wp:heading --><!-- wp:list {"className":"vg-comparison-list"} -->
<ul class="vg-comparison-list"><li><a href="/compare/ha-long-bay-vs-lan-ha-bay/"><strong>Ha Long Bay vs Lan Ha Bay</strong><span>Choose icon value or choose a calmer route.</span></a></li><li><a href="/compare/sapa-vs-ha-giang/"><strong>Sapa vs Ha Giang</strong><span>Choose easier access or choose the stronger road journey.</span></a></li><li><a href="/compare/hanoi-vs-ho-chi-minh-city/"><strong>Hanoi vs Ho Chi Minh City</strong><span>Choose layered history or choose southern energy.</span></a></li></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->
```

`practical-essentials.php`:

```html
<!-- wp:group {"className":"vg-section vg-essentials","layout":{"type":"constrained"}} -->
<div class="wp-block-group vg-section vg-essentials"><!-- wp:paragraph {"className":"vg-kicker"} --><p class="vg-kicker">Practical essentials</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Handle the details before they become problems.</h2><!-- /wp:heading --><!-- wp:list {"className":"vg-essential-grid"} -->
<ul class="vg-essential-grid"><li><a href="/plan/vietnam-evisa/"><strong>Vietnam e-visa</strong><span>Entry rules and application mistakes.</span></a></li><li><a href="/plan/best-time-to-visit-vietnam/"><strong>Best time to visit</strong><span>Plan around regions, not one forecast.</span></a></li><li><a href="/costs/vietnam-travel-cost/"><strong>Travel cost</strong><span>Realistic ranges for different travel styles.</span></a></li><li><a href="/plan/sim-esim-vietnam/"><strong>SIM and eSIM</strong><span>Stay connected from arrival.</span></a></li><li><a href="/plan/getting-around-vietnam/"><strong>Getting around</strong><span>Flights, trains, buses, and local transport.</span></a></li><li><a href="/plan/is-vietnam-safe/"><strong>Safety</strong><span>Practical risk management without alarmism.</span></a></li></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->
```

- [ ] **Step 3: Run the verifier**

Run:

```powershell
powershell -ExecutionPolicy Bypass -File ops/verify-homepage-theme.ps1
```

Expected: pattern failures disappear; only the QA fixture failure remains.

- [ ] **Step 4: Commit patterns**

```powershell
git add -- wordpress/wp-content/themes/vietnamguide-premium/patterns
git commit -m "feat: add reusable homepage block patterns"
```

### Task 7: Build and inspect the browser QA fixture

**Files:**
- Create: `qa/homepage-preview.html`

- [ ] **Step 1: Create a static fixture**

```html
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>VietnamGuide.net Homepage Preview</title>
  <link rel="stylesheet" href="../wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css">
  <script defer src="../wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js"></script>
</head>
<body class="home vg-site">
  <a class="vg-skip-link" href="#main">Skip to content</a>
  <header class="vg-site-header" data-vg-header>
    <div class="vg-site-header__inner">
      <a class="vg-wordmark" href="#">VietnamGuide.net</a>
      <button class="vg-menu-toggle" type="button" aria-controls="vg-primary-navigation" aria-expanded="false" data-vg-menu-toggle><span class="vg-menu-toggle__label">Menu</span><span class="vg-menu-toggle__icon" aria-hidden="true"></span></button>
      <nav id="vg-primary-navigation" class="vg-primary-navigation" aria-label="Primary navigation" data-vg-navigation><ul class="vg-nav-list"><li><a href="#planning">Plan</a></li><li><a href="#destinations">Destinations</a></li><li><a href="#itineraries">Itineraries</a></li><li><a href="#comparisons">Compare</a></li><li><a href="#essentials">Costs</a></li></ul></nav>
      <a class="vg-header-action" href="#planning">Plan your trip</a>
    </div>
  </header>
  <main id="main">
    <section class="vg-hero">
      <picture class="vg-hero__media"><source srcset="../wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero.webp" type="image/webp"><img src="../wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero.jpg" width="2400" height="1350" alt="Rice terraces, river, and limestone mountains in northern Vietnam at dawn"></picture>
      <div class="vg-hero__shade" aria-hidden="true"></div>
      <div class="vg-shell vg-hero__content" data-vg-reveal><p class="vg-hero__brand">VietnamGuide.net</p><h1>Vietnam for travelers who choose well.</h1><p class="vg-hero__copy">Curated routes, refined stays, and practical guidance for planning Vietnam with confidence.</p><div class="vg-actions"><a class="vg-button" href="#planning">Start planning</a><a class="vg-button vg-button--ghost" href="#itineraries">See itineraries</a></div></div>
    </section>
    <section id="planning" class="vg-section vg-planning-paths"><div class="vg-shell" data-vg-reveal><p class="vg-kicker">Build the right trip</p><h2>Start with time. Then choose how you want Vietnam to feel.</h2><div class="vg-planning-grid"><div><h3>Choose your trip length</h3><ul class="vg-link-list"><li><a href="#">7 days <span>&rarr;</span></a></li><li><a href="#">10 days <span>&rarr;</span></a></li><li><a href="#">14 days <span>&rarr;</span></a></li><li><a href="#">21 days <span>&rarr;</span></a></li></ul></div><div><h3>Choose your travel style</h3><ul class="vg-link-list"><li><a href="#">First trip <span>&rarr;</span></a></li><li><a href="#">Food <span>&rarr;</span></a></li><li><a href="#">Beach <span>&rarr;</span></a></li><li><a href="#">Family <span>&rarr;</span></a></li><li><a href="#">Premium <span>&rarr;</span></a></li></ul></div></div></div></section>
    <section id="itineraries" class="vg-section vg-itineraries"><div class="vg-shell"><div class="vg-section-heading" data-vg-reveal><p class="vg-kicker">Signature itineraries</p><h2>Routes built around pace, not a checklist.</h2></div><ol class="vg-editorial-rows"><li data-vg-reveal><a href="#"><span class="vg-editorial-rows__number">01</span><span><small>First journey</small><strong>10 days: north, center, south</strong></span><span>Best for a first trip that needs one coherent national overview.</span><span>&rarr;</span></a></li><li data-vg-reveal><a href="#"><span class="vg-editorial-rows__number">02</span><span><small>More breathing room</small><strong>14 days: Vietnam at a calmer pace</strong></span><span>Best for travelers who want stronger place depth.</span><span>&rarr;</span></a></li></ol></div></section>
    <section id="destinations" class="vg-section vg-destinations"><div class="vg-shell vg-destinations__layout"><picture class="vg-destinations__media" data-vg-reveal><source srcset="../wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-editorial.webp" type="image/webp"><img src="../wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-editorial.jpg" width="1800" height="1350" alt="A wooden boat moving between limestone karsts in Lan Ha Bay"></picture><div><p class="vg-kicker">Destination edit</p><h2>Choose places for the trip you actually want.</h2><ul class="vg-destination-list"><li data-vg-reveal><a href="#"><strong>Hanoi</strong><span><b>Best for:</b> Food, history, and a confident first arrival.</span><span><b>Skip if:</b> You want a quiet coastal base.</span></a></li><li data-vg-reveal><a href="#"><strong>Lan Ha Bay</strong><span><b>Best for:</b> A calmer bay experience.</span><span><b>Skip if:</b> You need the iconic checklist.</span></a></li><li data-vg-reveal><a href="#"><strong>Ha Giang</strong><span><b>Best for:</b> High-impact mountain scenery.</span><span><b>Skip if:</b> You dislike long road days.</span></a></li></ul></div></div></section>
    <section id="comparisons" class="vg-section vg-comparisons"><div class="vg-shell"><p class="vg-kicker">Decision guides</p><h2>Make the difficult choices quickly.</h2><ul class="vg-comparison-list"><li data-vg-reveal><a href="#"><strong>Ha Long Bay vs Lan Ha Bay</strong><span>Choose icon value or choose a calmer route.</span><span>&rarr;</span></a></li><li data-vg-reveal><a href="#"><strong>Sapa vs Ha Giang</strong><span>Choose easier access or the stronger road journey.</span><span>&rarr;</span></a></li></ul></div></section>
    <section id="essentials" class="vg-section vg-essentials"><div class="vg-shell"><p class="vg-kicker">Practical essentials</p><h2>Handle the details before they become problems.</h2><ul class="vg-essential-grid"><li data-vg-reveal><a href="#"><strong>Vietnam e-visa</strong><span>Entry rules and application mistakes.</span><span>&rarr;</span></a></li><li data-vg-reveal><a href="#"><strong>Best time to visit</strong><span>Plan around regions, not one forecast.</span><span>&rarr;</span></a></li><li data-vg-reveal><a href="#"><strong>Travel cost</strong><span>Realistic ranges by travel style.</span><span>&rarr;</span></a></li><li data-vg-reveal><a href="#"><strong>Getting around</strong><span>Flights, trains, buses, and transfers.</span><span>&rarr;</span></a></li></ul></div></section>
    <section class="vg-section vg-newsletter"><div class="vg-shell vg-newsletter__inner" data-vg-reveal><div><p class="vg-kicker">First-trip checklist</p><h2>Plan the trip once. Travel it with confidence.</h2></div><p>Get a concise planning checklist covering route, entry, transport, money, connectivity, and common mistakes.</p><a class="vg-button vg-button--light" href="#">Get the checklist</a></div></section>
  </main>
  <footer class="vg-site-footer"><div class="vg-site-footer__inner"><div class="vg-site-footer__brand"><a class="vg-wordmark" href="#">VietnamGuide.net</a><p>Choose Vietnam well.</p></div><ul class="vg-footer-links"><li><a href="#planning">Plan</a></li><li><a href="#destinations">Destinations</a></li><li><a href="#itineraries">Itineraries</a></li></ul><ul class="vg-footer-links vg-footer-links--legal"><li><a href="#">About</a></li><li><a href="#">Contact</a></li><li><a href="#">Source policy</a></li></ul></div><p class="vg-site-footer__copyright">&copy; 2026 VietnamGuide.net</p></footer>
</body>
</html>
```

- [ ] **Step 2: Run the full static verifier**

Run:

```powershell
powershell -ExecutionPolicy Bypass -File ops/verify-homepage-theme.ps1
```

Expected:

```text
VietnamGuide homepage theme checks passed.
```

- [ ] **Step 3: Start a local static server**

Run:

```powershell
python -m http.server 4173
```

Expected: the server listens on `http://localhost:4173/`.

- [ ] **Step 4: Inspect with the in-app browser**

Open:

```text
http://localhost:4173/qa/homepage-preview.html
```

Check widths `1440`, `1024`, `768`, `430`, and `390` pixels. Confirm hero/header fit, navigation open/close, one H1, visible focus, no horizontal overflow, destination image crop, readable comparison rows, and newsletter layout.

- [ ] **Step 5: Check reduced motion and keyboard behavior**

Enable reduced motion in browser emulation. Confirm reveal content is immediately visible. Use Tab, Shift+Tab, Enter, and Escape; confirm menu focus remains usable and Escape closes the menu.

- [ ] **Step 6: Commit the QA fixture**

```powershell
git add -- qa/homepage-preview.html
git commit -m "test: add homepage responsive preview"
```

### Task 8: Final verification and handoff

**Files:**
- Modify if required by QA: theme and QA files from Tasks 2-7

- [ ] **Step 1: Run whitespace and contract checks**

```powershell
git diff --check
powershell -ExecutionPolicy Bypass -File ops/verify-homepage-theme.ps1
$jsBytes = (Get-Item 'wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js').Length
$heroBytes = (Get-Item 'wordpress/wp-content/themes/vietnamguide-premium/assets/images/home-hero.webp').Length
if ($jsBytes -gt 25600) { throw "homepage.js exceeds 25 KB: $jsBytes bytes" }
if ($heroBytes -gt 716800) { throw "home-hero.webp exceeds 700 KB: $heroBytes bytes" }
Write-Output "homepage.js bytes: $jsBytes"
Write-Output "home-hero.webp bytes: $heroBytes"
```

Expected: `git diff --check` prints no errors, the verifier prints `VietnamGuide homepage theme checks passed.`, JavaScript stays at or below 25 KB, and the optimized hero stays at or below 700 KB.

- [ ] **Step 2: Confirm no secrets or generated source PNGs are staged unintentionally**

```powershell
git status --short
git diff --cached --name-only
```

Expected: no `.codex/config.toml`, credentials, API keys, database dumps, or server archives are staged. Keep the two PNG source files only if the user wants editable generation sources; otherwise commit the optimized JPEG/WebP files and the provenance document.

- [ ] **Step 3: Record environment limits**

Handoff must state that PHP syntax and live WordPress rendering remain unverified locally because PHP, WP-CLI, Docker, and a WordPress runtime are unavailable in this workspace. Provide exact deployment checks:

```bash
php -l wp-content/themes/vietnamguide-premium/functions.php
php -l wp-content/themes/vietnamguide-premium/inc/homepage-data.php
php -l wp-content/themes/vietnamguide-premium/header.php
php -l wp-content/themes/vietnamguide-premium/footer.php
php -l wp-content/themes/vietnamguide-premium/front-page.php
wp theme activate vietnamguide-premium
```

- [ ] **Step 4: Commit QA fixes if any**

```powershell
git add -- wordpress/wp-content/themes/vietnamguide-premium qa/homepage-preview.html ops/verify-homepage-theme.ps1 ops/build-homepage-images.py
git commit -m "fix: complete homepage QA"
```

Skip this commit when Task 7 produced no follow-up changes.

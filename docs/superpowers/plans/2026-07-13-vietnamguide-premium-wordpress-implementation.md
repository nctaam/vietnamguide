# VietnamGuide Premium WordPress Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the first premium, SEO-ready WordPress release for VietnamGuide.net using the selected Quiet Luxury Concierge direction.

**Architecture:** The live VPS already has raw WordPress installed. Implementation should add a lightweight premium theme layer, a small site-specific functionality plugin, reusable block patterns, SEO/performance/security configuration, a premium homepage, four reusable guide templates, and a launch verification workflow. Keep secrets out of Git and keep generated or environment-specific state out of the repository.

**Tech Stack:** WordPress, PHP 8.3+, MariaDB/MySQL, Nginx or Apache, WP-CLI, GeneratePress parent theme, custom child theme, site-specific mu-plugin, ACF, Rank Math, LiteSpeed Cache/WP Rocket equivalent, Cloudflare, GA4, Google Search Console, Bing Webmaster Tools.

---

## Scope Check

This is one integrated launch plan for the first premium WordPress release. It covers infrastructure hardening, theme/plugin setup, homepage build, block patterns, page templates, SEO launch, analytics, and verification. It intentionally does not cover writing all 100 planned articles, custom booking, multilingual publishing, advanced itinerary filtering, or a full custom WordPress theme.

## Required Inputs Before Execution

- Domain registrar access for DNS updates.
- VPS access through a secure password manager or SSH key.
- WordPress administrator access through a secure password manager.
- Decision on parent theme: use **GeneratePress** unless there is an existing paid Kadence/Blocksy license.
- Decision on SEO plugin: use **Rank Math** unless the site owner strongly prefers Yoast.
- Decision on cache plugin based on server: use **LiteSpeed Cache** only if the server runs LiteSpeed/OpenLiteSpeed; otherwise use WP Rocket or a lean server-level cache.

Do not write operational credentials into any file, command transcript, screenshot, issue, or commit.

## File Structure

Create and maintain the following repository structure:

```text
D:/Documents/vietnamguide/
  docs/
    superpowers/
      specs/
        2026-07-13-vietnamguide-wordpress-development-design.md
        2026-07-13-vietnamguide-premium-wordpress-design-plan.md
      plans/
        2026-07-13-vietnamguide-premium-wordpress-implementation.md
  ops/
    server-profile.example.md
    launch-checklist.md
    verification-log.md
  wordpress/
    wp-content/
      themes/
        vietnamguide-premium/
          style.css
          functions.php
          theme.json
          assets/
            css/
              vietnamguide-premium.css
            js/
              vietnamguide-premium.js
          patterns/
            hero-editorial.php
            route-selector.php
            quick-verdict.php
            at-a-glance.php
            decision-table.php
            itinerary-timeline.php
            source-block.php
            recommendation-row.php
            newsletter-capture.php
            homepage-sections.php
      mu-plugins/
        vietnamguide-core.php
```

Files that must not be committed:

```text
ops/server-profile.md
ops/*.secret.md
*.sql
*.tar
*.tar.gz
*.zip
.env
```

Add these patterns to `.gitignore` before implementation work starts.

## Naming Conventions

- Child theme slug: `vietnamguide-premium`
- Site-specific mu-plugin name: `VietnamGuide Core`
- CSS prefix: `.vg-`
- Pattern namespace: `vietnamguide`
- ACF field group prefix: `vg_`
- WordPress menus:
  - `Primary Navigation`
  - `Footer Plan`
  - `Footer Destinations`
  - `Footer Legal`

## Phase 1: Server, DNS, and WordPress Foundation

### Task 1: Create Operations Safety Files

**Files:**
- Modify: `.gitignore`
- Create: `ops/server-profile.example.md`
- Create: `ops/launch-checklist.md`
- Create: `ops/verification-log.md`

- [ ] **Step 1: Extend `.gitignore`**

Add:

```gitignore
.superpowers/
ops/server-profile.md
ops/*.secret.md
*.sql
*.tar
*.tar.gz
*.zip
.env
```

- [ ] **Step 2: Create `ops/server-profile.example.md`**

```markdown
# Server Profile Example

Copy this file to `ops/server-profile.md` during execution. Do not commit the copied file.

## WordPress

- Site URL:
- WordPress path:
- PHP version:
- Database engine/version:
- Web server:
- Active parent theme:
- Active child theme:
- Active SEO plugin:
- Active cache plugin:

## DNS

- Registrar:
- Nameservers:
- Apex record:
- WWW record:
- Cloudflare status:

## Verification Dates

- DNS verified:
- SSL verified:
- Backup restore verified:
- Search Console verified:
```

- [ ] **Step 3: Create `ops/launch-checklist.md`**

```markdown
# VietnamGuide Launch Checklist

## Foundation

- [ ] DNS resolves for apex domain.
- [ ] DNS resolves for www.
- [ ] HTTPS works for apex and www.
- [ ] WordPress admin password has been rotated after setup.
- [ ] Two-factor authentication is enabled for admin users.
- [ ] Backup job is configured.
- [ ] Restore test is complete.

## WordPress

- [ ] Permalink structure is `/%category%/%postname%/` or page-based evergreen URLs.
- [ ] Parent theme is installed.
- [ ] `vietnamguide-premium` child theme is active.
- [ ] Required plugins are installed.
- [ ] Unused default plugins and themes are removed.

## SEO

- [ ] Site title and tagline configured.
- [ ] XML sitemap available.
- [ ] Search results pages noindexed.
- [ ] Thin tag archives noindexed.
- [ ] Breadcrumbs enabled.
- [ ] Homepage metadata configured.

## Performance

- [ ] Page cache enabled.
- [ ] WebP or AVIF image optimization enabled.
- [ ] Homepage mobile PageSpeed tested.
- [ ] No obvious CLS in homepage hero.

## Content

- [ ] Homepage published.
- [ ] Sample destination page published.
- [ ] Sample itinerary page published.
- [ ] Sample comparison page published.
- [ ] Sample practical guide page published.
```

- [ ] **Step 4: Create `ops/verification-log.md`**

```markdown
# Verification Log

Record commands and outcomes. Do not paste secrets.

## DNS

## SSL

## WordPress Health

## Theme

## SEO

## Performance

## Accessibility
```

- [ ] **Step 5: Verify no ignored profile file is staged**

Run:

```powershell
git status --short
git check-ignore -v ops/server-profile.md
```

Expected:

```text
.gitignore:2:ops/server-profile.md  ops/server-profile.md
```

- [ ] **Step 6: Commit**

```powershell
git add .gitignore ops/server-profile.example.md ops/launch-checklist.md ops/verification-log.md
git commit -m "chore: add operations launch files"
```

### Task 2: Discover Live Server and WordPress State

**Files:**
- Create local untracked file during execution: `ops/server-profile.md`
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Copy server profile template**

Run:

```powershell
Copy-Item -LiteralPath ops/server-profile.example.md -Destination ops/server-profile.md
```

Expected: `ops/server-profile.md` exists and is ignored by Git.

- [ ] **Step 2: Verify SSH access without exposing credentials**

Run from the local machine using the secure connection details stored outside Git:

```powershell
ssh -p $env:VG_SSH_PORT "$env:VG_SSH_USER@$env:VG_SSH_HOST" "whoami && hostname && pwd"
```

Expected:

```text
remote user name
server hostname
remote login directory
```

Do not paste passwords into the terminal command.

- [ ] **Step 3: Locate the WordPress path**

Run on the VPS:

```bash
find /var/www /home /usr/share/nginx -maxdepth 5 -name wp-config.php 2>/dev/null
```

Expected: one path ending in `wp-config.php`.

Record the parent directory as `WordPress path` in `ops/server-profile.md`.

- [ ] **Step 4: Verify WP-CLI availability**

Run on the VPS from the WordPress path:

```bash
cd "$WP_PATH"
wp core version --allow-root
wp option get siteurl --allow-root
wp option get home --allow-root
```

Expected:

```text
<wordpress-version>
https://vietnamguide.net
https://vietnamguide.net
```

If WP-CLI is not installed, install it with:

```bash
cd /tmp
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
php wp-cli.phar --info
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
wp --info
```

Expected:

```text
WP-CLI version:
```

- [ ] **Step 5: Record WordPress health information**

Run:

```bash
cd "$WP_PATH"
wp --info --allow-root
wp theme list --allow-root
wp plugin list --allow-root
```

Expected: output lists PHP version, WordPress path, installed themes, and installed plugins.

Summarize non-secret findings in `ops/verification-log.md`.

- [ ] **Step 6: Commit verification log**

```powershell
git add ops/verification-log.md
git commit -m "docs: record initial WordPress discovery"
```

### Task 3: DNS and SSL Verification

**Files:**
- Modify: `ops/launch-checklist.md`
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Verify DNS records locally**

Run:

```powershell
Resolve-DnsName vietnamguide.net
Resolve-DnsName www.vietnamguide.net
```

Expected:

```text
vietnamguide.net resolves to the VPS IP or Cloudflare proxy.
www.vietnamguide.net resolves to the VPS IP, Cloudflare proxy, or CNAME to apex.
```

- [ ] **Step 2: If DNS is missing, set records**

At the DNS provider:

```text
Type: A
Name: @
Value: <VPS IPv4>
TTL: Automatic or 300

Type: CNAME
Name: www
Value: vietnamguide.net
TTL: Automatic or 300
```

If Cloudflare is used, keep proxy disabled until SSL is confirmed, then enable proxy after origin HTTPS works.

- [ ] **Step 3: Verify HTTP response**

Run:

```powershell
curl.exe -I http://vietnamguide.net
curl.exe -I https://vietnamguide.net
curl.exe -I https://www.vietnamguide.net
```

Expected:

```text
HTTP redirects to HTTPS.
HTTPS returns 200 or 301 to canonical domain.
www redirects to canonical apex or is consistently canonicalized.
```

- [ ] **Step 4: Configure SSL if missing**

On the VPS, if using Nginx/Apache with Certbot:

```bash
sudo certbot --nginx -d vietnamguide.net -d www.vietnamguide.net
```

or:

```bash
sudo certbot --apache -d vietnamguide.net -d www.vietnamguide.net
```

Expected: certificate issued and automatic renewal configured.

- [ ] **Step 5: Record outcome**

Update `ops/verification-log.md` with:

```markdown
## DNS

- Apex resolves:
- WWW resolves:
- Canonical host:

## SSL

- HTTPS status:
- Certificate issuer:
- Renewal method:
```

- [ ] **Step 6: Commit DNS/SSL verification notes**

```powershell
git add ops/launch-checklist.md ops/verification-log.md
git commit -m "docs: record DNS and SSL verification"
```

### Task 4: WordPress and VPS Hardening

**Files:**
- Modify: `ops/launch-checklist.md`
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Rotate WordPress admin password**

In WordPress admin:

```text
Users > Profile > Set New Password > Save
```

Expected: login works with the new password stored in the password manager.

- [ ] **Step 2: Create named admin account**

In WordPress admin:

```text
Users > Add New > Role: Administrator
```

Expected: named account exists. Avoid shared day-to-day admin use.

- [ ] **Step 3: Remove unused default themes and plugins**

Run:

```bash
cd "$WP_PATH"
wp theme list --allow-root
wp plugin list --allow-root
wp plugin delete hello akismet --allow-root
```

Expected: unused plugins removed. Keep Akismet only if comments will be enabled and configured.

- [ ] **Step 4: Disable XML-RPC if not needed**

If server is Nginx, add to the site server block:

```nginx
location = /xmlrpc.php {
    deny all;
    access_log off;
    log_not_found off;
}
```

If Apache, add to `.htaccess` above WordPress rules:

```apache
<Files xmlrpc.php>
  Require all denied
</Files>
```

Expected:

```powershell
curl.exe -I https://vietnamguide.net/xmlrpc.php
```

returns `403`.

- [ ] **Step 5: Install login protection and 2FA plugin**

Use either Wordfence, Solid Security, or a host-level security suite.

Run:

```bash
cd "$WP_PATH"
wp plugin install wordfence --activate --allow-root
```

Expected: plugin active. Configure firewall and 2FA in WordPress admin.

- [ ] **Step 6: Configure backups**

Install UpdraftPlus if host-level backups are not already in place:

```bash
cd "$WP_PATH"
wp plugin install updraftplus --activate --allow-root
```

Expected: daily database and weekly file backups configured to off-server storage.

- [ ] **Step 7: Perform a restore test**

Use a staging directory or temporary database. Record:

```markdown
## Backup Restore Test

- Backup source:
- Restore target:
- Database restored:
- Files restored:
- Login verified:
- Date:
```

- [ ] **Step 8: Commit hardening notes**

```powershell
git add ops/launch-checklist.md ops/verification-log.md
git commit -m "docs: record WordPress hardening baseline"
```

## Phase 2: Theme, Plugin, and Design System Setup

### Task 5: Create the Child Theme Scaffold

**Files:**
- Create: `wordpress/wp-content/themes/vietnamguide-premium/style.css`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/functions.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/theme.json`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/assets/css/vietnamguide-premium.css`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/assets/js/vietnamguide-premium.js`

- [ ] **Step 1: Create child theme `style.css`**

```css
/*
Theme Name: VietnamGuide Premium
Theme URI: https://vietnamguide.net
Description: Premium child theme for VietnamGuide.net, built around Quiet Luxury Concierge editorial travel planning.
Author: VietnamGuide.net
Template: generatepress
Version: 0.1.0
Text Domain: vietnamguide-premium
*/
```

- [ ] **Step 2: Create `functions.php`**

```php
<?php
/**
 * VietnamGuide Premium child theme bootstrap.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'vietnamguide-premium',
        get_stylesheet_directory_uri() . '/assets/css/vietnamguide-premium.css',
        [],
        '0.1.0'
    );

    wp_enqueue_script(
        'vietnamguide-premium',
        get_stylesheet_directory_uri() . '/assets/js/vietnamguide-premium.js',
        [],
        '0.1.0',
        true
    );
});

add_action('after_setup_theme', function (): void {
    add_theme_support('editor-styles');
    add_editor_style('assets/css/vietnamguide-premium.css');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('wp-block-styles');

    register_nav_menus([
        'primary' => __('Primary Navigation', 'vietnamguide-premium'),
        'footer_plan' => __('Footer Plan', 'vietnamguide-premium'),
        'footer_destinations' => __('Footer Destinations', 'vietnamguide-premium'),
        'footer_legal' => __('Footer Legal', 'vietnamguide-premium'),
    ]);
});

add_action('init', function (): void {
    register_block_pattern_category(
        'vietnamguide',
        ['label' => __('VietnamGuide', 'vietnamguide-premium')]
    );
});
```

- [ ] **Step 3: Create `theme.json`**

```json
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "settings": {
    "color": {
      "palette": [
        { "slug": "ink", "color": "#101417", "name": "Ink" },
        { "slug": "soft-paper", "color": "#F7F4ED", "name": "Soft Paper" },
        { "slug": "limestone", "color": "#E5DED1", "name": "Limestone" },
        { "slug": "river-jade", "color": "#0E6F5C", "name": "River Jade" },
        { "slug": "deep-river-blue", "color": "#173B56", "name": "Deep River Blue" },
        { "slug": "quiet-gold", "color": "#B98739", "name": "Quiet Gold" },
        { "slug": "clay-coral", "color": "#C95F4A", "name": "Clay Coral" },
        { "slug": "white", "color": "#FFFFFF", "name": "White" }
      ]
    },
    "layout": {
      "contentSize": "760px",
      "wideSize": "1180px"
    },
    "spacing": {
      "spacingScale": {
        "steps": 0
      },
      "spacingSizes": [
        { "slug": "xs", "size": "8px", "name": "XS" },
        { "slug": "sm", "size": "16px", "name": "SM" },
        { "slug": "md", "size": "24px", "name": "MD" },
        { "slug": "lg", "size": "40px", "name": "LG" },
        { "slug": "xl", "size": "72px", "name": "XL" },
        { "slug": "xxl", "size": "112px", "name": "XXL" }
      ]
    },
    "typography": {
      "fontFamilies": [
        {
          "slug": "display",
          "name": "Display Serif",
          "fontFamily": "Georgia, 'Times New Roman', serif"
        },
        {
          "slug": "body",
          "name": "Body Sans",
          "fontFamily": "Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
        }
      ]
    }
  }
}
```

- [ ] **Step 4: Create `vietnamguide-premium.css`**

```css
:root {
  --vg-ink: #101417;
  --vg-soft-paper: #f7f4ed;
  --vg-limestone: #e5ded1;
  --vg-river-jade: #0e6f5c;
  --vg-deep-river-blue: #173b56;
  --vg-quiet-gold: #b98739;
  --vg-clay-coral: #c95f4a;
  --vg-white: #ffffff;
  --vg-border: rgba(16, 20, 23, .14);
  --vg-shadow: 0 28px 80px rgba(16, 20, 23, .12);
  --vg-radius: 8px;
  --vg-content: 760px;
  --vg-wide: 1180px;
}

body {
  color: var(--vg-ink);
  background: var(--vg-soft-paper);
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  letter-spacing: 0;
}

.vg-display {
  font-family: Georgia, "Times New Roman", serif;
  letter-spacing: 0;
}

.vg-section {
  padding: clamp(56px, 8vw, 112px) 24px;
}

.vg-wrap {
  width: min(100% - 48px, var(--vg-wide));
  margin-inline: auto;
}

.vg-content {
  width: min(100% - 48px, var(--vg-content));
  margin-inline: auto;
}

.vg-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 44px;
  padding: 11px 18px;
  border-radius: var(--vg-radius);
  border: 1px solid transparent;
  background: var(--vg-river-jade);
  color: var(--vg-white);
  font-weight: 750;
  text-decoration: none;
}

.vg-button:hover,
.vg-button:focus-visible {
  background: #0a5b4c;
  color: var(--vg-white);
}

.vg-button-secondary {
  background: transparent;
  color: var(--vg-ink);
  border-color: rgba(16, 20, 23, .25);
}

.vg-button-secondary:hover,
.vg-button-secondary:focus-visible {
  background: var(--vg-ink);
  color: var(--vg-white);
}

.vg-kicker {
  color: var(--vg-quiet-gold);
  font-size: 12px;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.vg-hero {
  min-height: min(760px, 100svh);
  position: relative;
  display: grid;
  align-items: end;
  color: var(--vg-white);
  overflow: hidden;
}

.vg-hero::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, rgba(0, 0, 0, .66), rgba(0, 0, 0, .18) 62%, rgba(0, 0, 0, .42));
}

.vg-hero-media {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.vg-hero-inner {
  position: relative;
  z-index: 1;
  width: min(100% - 48px, 1180px);
  margin-inline: auto;
  padding: 120px 0 72px;
}

.vg-hero-brand {
  margin: 0 0 18px;
  font-size: clamp(42px, 7vw, 104px);
  line-height: .92;
  font-family: Georgia, "Times New Roman", serif;
}

.vg-hero-copy {
  max-width: 560px;
  margin: 0 0 28px;
  font-size: clamp(17px, 2vw, 22px);
  line-height: 1.45;
}

.vg-action-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.vg-quick-verdict,
.vg-source-block,
.vg-at-a-glance {
  border: 1px solid var(--vg-border);
  border-radius: var(--vg-radius);
  background: var(--vg-white);
  padding: 24px;
}

.vg-quick-verdict h2,
.vg-source-block h2,
.vg-at-a-glance h2 {
  margin-top: 0;
}

.vg-decision-table {
  width: 100%;
  border-collapse: collapse;
  background: var(--vg-white);
}

.vg-decision-table th,
.vg-decision-table td {
  padding: 14px 16px;
  border-bottom: 1px solid var(--vg-border);
  text-align: left;
  vertical-align: top;
}

.vg-timeline {
  display: grid;
  gap: 18px;
}

.vg-timeline-item {
  display: grid;
  grid-template-columns: 72px minmax(0, 1fr);
  gap: 18px;
  padding-bottom: 18px;
  border-bottom: 1px solid var(--vg-border);
}

.vg-day {
  color: var(--vg-river-jade);
  font-weight: 850;
}

@media (max-width: 760px) {
  .vg-wrap,
  .vg-content,
  .vg-hero-inner {
    width: min(100% - 32px, var(--vg-wide));
  }

  .vg-hero {
    min-height: 640px;
  }

  .vg-timeline-item {
    grid-template-columns: 1fr;
  }

  .vg-decision-table,
  .vg-decision-table tbody,
  .vg-decision-table tr,
  .vg-decision-table th,
  .vg-decision-table td {
    display: block;
    width: 100%;
  }
}
```

- [ ] **Step 5: Create `vietnamguide-premium.js`**

```javascript
(() => {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  document.documentElement.classList.add('vg-motion-ready');

  const revealItems = document.querySelectorAll('[data-vg-reveal]');
  if (!('IntersectionObserver' in window) || revealItems.length === 0) return;

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

- [ ] **Step 6: Lint PHP**

Run:

```powershell
php -l wordpress/wp-content/themes/vietnamguide-premium/functions.php
```

Expected:

```text
No syntax errors detected in wordpress/wp-content/themes/vietnamguide-premium/functions.php
```

- [ ] **Step 7: Commit child theme scaffold**

```powershell
git add wordpress/wp-content/themes/vietnamguide-premium
git commit -m "feat: add VietnamGuide premium child theme scaffold"
```

### Task 6: Create the Site-Specific Core Plugin

**Files:**
- Create: `wordpress/wp-content/mu-plugins/vietnamguide-core.php`

- [ ] **Step 1: Create mu-plugin**

```php
<?php
/**
 * Plugin Name: VietnamGuide Core
 * Description: Site-specific functionality for VietnamGuide.net.
 * Version: 0.1.0
 * Author: VietnamGuide.net
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_register_pattern_category(): void
{
    if (function_exists('register_block_pattern_category')) {
        register_block_pattern_category(
            'vietnamguide',
            ['label' => __('VietnamGuide', 'vietnamguide-core')]
        );
    }
}
add_action('init', 'vg_register_pattern_category');

function vg_add_affiliate_link_attributes(string $content): string
{
    if (is_admin() || ! is_singular()) {
        return $content;
    }

    return preg_replace_callback(
        '/<a\s+([^>]*class=["\'][^"\']*vg-affiliate-link[^"\']*["\'][^>]*)>/i',
        static function (array $matches): string {
            $attrs = $matches[1];

            if (stripos($attrs, ' rel=') === false) {
                $attrs .= ' rel="sponsored nofollow"';
            }

            return '<a ' . $attrs . '>';
        },
        $content
    ) ?? $content;
}
add_filter('the_content', 'vg_add_affiliate_link_attributes', 20);

function vg_register_image_sizes(): void
{
    add_image_size('vg-hero', 1920, 1080, true);
    add_image_size('vg-editorial-wide', 1440, 900, true);
    add_image_size('vg-card', 720, 540, true);
}
add_action('after_setup_theme', 'vg_register_image_sizes');
```

- [ ] **Step 2: Lint PHP**

Run:

```powershell
php -l wordpress/wp-content/mu-plugins/vietnamguide-core.php
```

Expected:

```text
No syntax errors detected in wordpress/wp-content/mu-plugins/vietnamguide-core.php
```

- [ ] **Step 3: Commit core plugin**

```powershell
git add wordpress/wp-content/mu-plugins/vietnamguide-core.php
git commit -m "feat: add VietnamGuide core mu-plugin"
```

### Task 7: Install and Configure Parent Theme and Required Plugins

**Files:**
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Install parent theme**

Run on the VPS:

```bash
cd "$WP_PATH"
wp theme install generatepress --activate --allow-root
```

Expected:

```text
Success: Installed 1 of 1 themes.
Success: Switched to 'GeneratePress' theme.
```

- [ ] **Step 2: Deploy child theme and mu-plugin**

From local machine:

```powershell
scp -P $env:VG_SSH_PORT -r wordpress/wp-content/themes/vietnamguide-premium "$env:VG_SSH_USER@$env:VG_SSH_HOST:`$WP_PATH/wp-content/themes/"
scp -P $env:VG_SSH_PORT wordpress/wp-content/mu-plugins/vietnamguide-core.php "$env:VG_SSH_USER@$env:VG_SSH_HOST:`$WP_PATH/wp-content/mu-plugins/"
```

Expected: files are copied to the WordPress install.

- [ ] **Step 3: Activate child theme**

Run on VPS:

```bash
cd "$WP_PATH"
wp theme activate vietnamguide-premium --allow-root
wp theme status vietnamguide-premium --allow-root
```

Expected:

```text
Status: Active
```

- [ ] **Step 4: Install required plugins**

Run:

```bash
cd "$WP_PATH"
wp plugin install advanced-custom-fields seo-by-rank-math redirection updraftplus wordfence google-site-kit --activate --allow-root
```

Expected: each plugin installs and activates.

- [ ] **Step 5: Install cache plugin based on server**

If LiteSpeed is detected:

```bash
cd "$WP_PATH"
wp plugin install litespeed-cache --activate --allow-root
```

If LiteSpeed is not detected and WP Rocket license is available, install it manually through WordPress admin.

Expected: exactly one page cache plugin is active.

- [ ] **Step 6: Verify active theme and plugins**

Run:

```bash
cd "$WP_PATH"
wp theme list --status=active --allow-root
wp plugin list --status=active --allow-root
```

Expected:

```text
vietnamguide-premium is active.
Rank Math or chosen SEO plugin is active.
ACF is active.
One cache plugin is active.
Security plugin is active.
Backup plugin or host backup is configured.
```

- [ ] **Step 7: Record results**

Add to `ops/verification-log.md`:

```markdown
## Theme and Plugins

- Parent theme:
- Child theme:
- SEO plugin:
- Cache plugin:
- ACF status:
- Backup plugin/status:
- Security plugin/status:
```

- [ ] **Step 8: Commit verification note**

```powershell
git add ops/verification-log.md
git commit -m "docs: record theme and plugin setup"
```

## Phase 3: Reusable Block Patterns and Templates

### Task 8: Add Core Block Patterns

**Files:**
- Create: `wordpress/wp-content/themes/vietnamguide-premium/patterns/hero-editorial.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/patterns/route-selector.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/patterns/quick-verdict.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/patterns/at-a-glance.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-table.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/patterns/itinerary-timeline.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/patterns/source-block.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/patterns/recommendation-row.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/patterns/newsletter-capture.php`

- [ ] **Step 1: Create `hero-editorial.php`**

```php
<?php
/**
 * Title: Editorial Hero
 * Slug: vietnamguide/hero-editorial
 * Categories: vietnamguide
 */
?>
<!-- wp:group {"align":"full","className":"vg-hero"} -->
<div class="wp-block-group alignfull vg-hero">
  <img class="vg-hero-media" src="https://vietnamguide.net/wp-content/uploads/hero-vietnam-guide.jpg" alt="Vietnam landscape at golden hour">
  <div class="vg-hero-inner">
    <p class="vg-kicker">VietnamGuide.net</p>
    <h1 class="vg-hero-brand">Vietnam for travelers who choose well.</h1>
    <p class="vg-hero-copy">Curated routes, refined stays, and practical guidance for planning your trip with confidence.</p>
    <div class="vg-action-row">
      <a class="vg-button" href="/plan/">Start planning</a>
      <a class="vg-button vg-button-secondary" href="/itineraries/">See itineraries</a>
    </div>
  </div>
</div>
<!-- /wp:group -->
```

- [ ] **Step 2: Create `route-selector.php`**

```php
<?php
/**
 * Title: Route Selector
 * Slug: vietnamguide/route-selector
 * Categories: vietnamguide
 */
?>
<!-- wp:group {"className":"vg-section vg-route-selector"} -->
<div class="wp-block-group vg-section vg-route-selector">
  <div class="vg-wrap">
    <p class="vg-kicker">Choose your route</p>
    <h2 class="vg-display">Start with the number of days you have.</h2>
    <div class="vg-action-row">
      <a class="vg-button vg-button-secondary" href="/itineraries/7-days-in-vietnam/">7 days</a>
      <a class="vg-button vg-button-secondary" href="/itineraries/10-days-in-vietnam/">10 days</a>
      <a class="vg-button vg-button-secondary" href="/itineraries/14-days-in-vietnam/">14 days</a>
      <a class="vg-button vg-button-secondary" href="/itineraries/21-days-in-vietnam/">21 days</a>
    </div>
  </div>
</div>
<!-- /wp:group -->
```

- [ ] **Step 3: Create `quick-verdict.php`**

```php
<?php
/**
 * Title: Quick Verdict
 * Slug: vietnamguide/quick-verdict
 * Categories: vietnamguide
 */
?>
<!-- wp:group {"className":"vg-quick-verdict"} -->
<div class="wp-block-group vg-quick-verdict">
  <p class="vg-kicker">Quick verdict</p>
  <h2>Best for first-time visitors who want a balanced Vietnam route.</h2>
  <p>Choose this guide if you want a practical route with culture, food, scenery, and comfortable travel times. Skip it if you only want beaches or nightlife.</p>
</div>
<!-- /wp:group -->
```

- [ ] **Step 4: Create `at-a-glance.php`**

```php
<?php
/**
 * Title: At A Glance
 * Slug: vietnamguide/at-a-glance
 * Categories: vietnamguide
 */
?>
<!-- wp:group {"className":"vg-at-a-glance"} -->
<div class="wp-block-group vg-at-a-glance">
  <p class="vg-kicker">At a glance</p>
  <ul>
    <li><strong>Best months:</strong> March, April, October, November</li>
    <li><strong>Days needed:</strong> 10-14</li>
    <li><strong>Budget:</strong> Mid-range to premium</li>
    <li><strong>Best entry:</strong> Hanoi or Ho Chi Minh City</li>
  </ul>
</div>
<!-- /wp:group -->
```

- [ ] **Step 5: Create `decision-table.php`**

```php
<?php
/**
 * Title: Decision Table
 * Slug: vietnamguide/decision-table
 * Categories: vietnamguide
 */
?>
<!-- wp:html -->
<table class="vg-decision-table">
  <thead>
    <tr>
      <th>Choose this</th>
      <th>If you want</th>
      <th>Watch out for</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Lan Ha Bay</td>
      <td>Quieter scenery and a calmer cruise experience</td>
      <td>Fewer iconic Ha Long viewpoints</td>
    </tr>
    <tr>
      <td>Ha Long Bay</td>
      <td>The classic first-time Vietnam image</td>
      <td>More crowds and variable cruise quality</td>
    </tr>
  </tbody>
</table>
<!-- /wp:html -->
```

- [ ] **Step 6: Create `itinerary-timeline.php`**

```php
<?php
/**
 * Title: Itinerary Timeline
 * Slug: vietnamguide/itinerary-timeline
 * Categories: vietnamguide
 */
?>
<!-- wp:html -->
<div class="vg-timeline">
  <div class="vg-timeline-item">
    <div class="vg-day">Day 1</div>
    <div>
      <h3>Arrive in Hanoi</h3>
      <p>Settle in near the Old Quarter, keep the first evening light, and save major sightseeing for the next morning.</p>
    </div>
  </div>
  <div class="vg-timeline-item">
    <div class="vg-day">Day 2</div>
    <div>
      <h3>Hanoi food and culture</h3>
      <p>Plan one guided food walk, one museum or temple, and a calm coffee stop to recover from jet lag.</p>
    </div>
  </div>
</div>
<!-- /wp:html -->
```

- [ ] **Step 7: Create `source-block.php`**

```php
<?php
/**
 * Title: Source Block
 * Slug: vietnamguide/source-block
 * Categories: vietnamguide
 */
?>
<!-- wp:group {"className":"vg-source-block"} -->
<div class="wp-block-group vg-source-block">
  <p class="vg-kicker">Sources checked</p>
  <h2>Official sources and update notes</h2>
  <ul>
    <li><a href="https://vietnam.travel/plan-your-trip/visa-requirements">Vietnam.travel visa requirements</a></li>
    <li><a href="https://evisa.gov.vn/">Official Vietnam e-visa portal</a></li>
  </ul>
  <p><strong>Last reviewed:</strong> July 2026</p>
</div>
<!-- /wp:group -->
```

- [ ] **Step 8: Create `recommendation-row.php`**

```php
<?php
/**
 * Title: Premium Recommendation Row
 * Slug: vietnamguide/recommendation-row
 * Categories: vietnamguide
 */
?>
<!-- wp:group {"className":"vg-recommendation-row"} -->
<div class="wp-block-group vg-recommendation-row">
  <p class="vg-kicker">Recommended next step</p>
  <h2>Book the smoother option if comfort matters.</h2>
  <p>Choose a private transfer when arriving late, traveling with family, or connecting directly to a hotel after a long flight.</p>
  <a class="vg-button vg-affiliate-link" href="#">Compare transfer options</a>
</div>
<!-- /wp:group -->
```

- [ ] **Step 9: Create `newsletter-capture.php`**

```php
<?php
/**
 * Title: Newsletter Capture
 * Slug: vietnamguide/newsletter-capture
 * Categories: vietnamguide
 */
?>
<!-- wp:group {"className":"vg-section vg-newsletter"} -->
<div class="wp-block-group vg-section vg-newsletter">
  <div class="vg-content">
    <p class="vg-kicker">First trip checklist</p>
    <h2 class="vg-display">Get the calm version of planning Vietnam.</h2>
    <p>A short checklist covering route order, visa, money, SIM, weather, and common first-trip mistakes.</p>
    <p><a class="vg-button" href="/newsletter/">Get the checklist</a></p>
  </div>
</div>
<!-- /wp:group -->
```

- [ ] **Step 10: Lint all pattern files**

Run:

```powershell
Get-ChildItem wordpress/wp-content/themes/vietnamguide-premium/patterns/*.php | ForEach-Object { php -l $_.FullName }
```

Expected: every file reports no syntax errors.

- [ ] **Step 11: Commit patterns**

```powershell
git add wordpress/wp-content/themes/vietnamguide-premium/patterns
git commit -m "feat: add premium guide block patterns"
```

### Task 9: Deploy Patterns and Verify in WordPress Editor

**Files:**
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Deploy updated theme**

Run:

```powershell
scp -P $env:VG_SSH_PORT -r wordpress/wp-content/themes/vietnamguide-premium "$env:VG_SSH_USER@$env:VG_SSH_HOST:`$WP_PATH/wp-content/themes/"
```

Expected: theme files on server match local files.

- [ ] **Step 2: Clear cache**

Run on VPS:

```bash
cd "$WP_PATH"
wp cache flush --allow-root
```

Expected:

```text
Success: The cache was flushed.
```

- [ ] **Step 3: Verify patterns in editor**

In WordPress admin:

```text
Pages > Add New > Block Inserter > Patterns > VietnamGuide
```

Expected: all VietnamGuide patterns appear.

- [ ] **Step 4: Record verification**

Add to `ops/verification-log.md`:

```markdown
## Block Patterns

- Hero Editorial visible:
- Route Selector visible:
- Quick Verdict visible:
- At A Glance visible:
- Decision Table visible:
- Itinerary Timeline visible:
- Source Block visible:
- Recommendation Row visible:
- Newsletter Capture visible:
```

- [ ] **Step 5: Commit verification**

```powershell
git add ops/verification-log.md
git commit -m "docs: verify premium block patterns"
```

## Phase 4: Homepage Build

### Task 10: Build Homepage Content Structure

**Files:**
- Create: `wordpress/wp-content/themes/vietnamguide-premium/patterns/homepage-sections.php`
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Create `homepage-sections.php`**

```php
<?php
/**
 * Title: Homepage Premium Sections
 * Slug: vietnamguide/homepage-sections
 * Categories: vietnamguide
 */
?>
<!-- wp:pattern {"slug":"vietnamguide/hero-editorial"} /-->
<!-- wp:pattern {"slug":"vietnamguide/route-selector"} /-->

<!-- wp:group {"className":"vg-section vg-begin"} -->
<div class="wp-block-group vg-section vg-begin">
  <div class="vg-wrap">
    <p class="vg-kicker">Begin with the right decision</p>
    <h2 class="vg-display">Three ways to plan Vietnam well.</h2>
    <div class="wp-block-columns">
      <div class="wp-block-column">
        <h3>Plan essentials</h3>
        <p>Visa, weather, money, transport, and the decisions to make before booking.</p>
        <p><a href="/plan/">Start with essentials</a></p>
      </div>
      <div class="wp-block-column">
        <h3>Choose a route</h3>
        <p>Use 7, 10, 14, and 21 day itineraries to avoid rushing the wrong places.</p>
        <p><a href="/itineraries/">See itineraries</a></p>
      </div>
      <div class="wp-block-column">
        <h3>Compare destinations</h3>
        <p>Choose between similar places with clear trade-offs and travel-time context.</p>
        <p><a href="/compare/">Compare places</a></p>
      </div>
    </div>
  </div>
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-section vg-signature"} -->
<div class="wp-block-group vg-section vg-signature">
  <div class="vg-wrap">
    <p class="vg-kicker">Signature itineraries</p>
    <h2 class="vg-display">Routes that match your pace.</h2>
    <ul>
      <li><a href="/itineraries/10-days-in-vietnam/">10 days in Vietnam</a></li>
      <li><a href="/itineraries/14-days-in-vietnam/">14 days in Vietnam</a></li>
      <li><a href="/itineraries/northern-vietnam-itinerary/">Northern Vietnam itinerary</a></li>
      <li><a href="/itineraries/vietnam-luxury-itinerary/">Premium Vietnam itinerary</a></li>
    </ul>
  </div>
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vg-section vg-practical"} -->
<div class="wp-block-group vg-section vg-practical">
  <div class="vg-wrap">
    <p class="vg-kicker">Practical essentials</p>
    <h2 class="vg-display">The details that make the trip smoother.</h2>
    <ul>
      <li><a href="/plan/vietnam-evisa/">Vietnam e-visa guide</a></li>
      <li><a href="/plan/best-time-to-visit-vietnam/">Best time to visit Vietnam</a></li>
      <li><a href="/costs/vietnam-travel-cost/">Vietnam travel cost</a></li>
      <li><a href="/plan/sim-esim-vietnam/">SIM and eSIM in Vietnam</a></li>
      <li><a href="/plan/is-vietnam-safe/">Is Vietnam safe?</a></li>
    </ul>
  </div>
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"vietnamguide/newsletter-capture"} /-->
```

- [ ] **Step 2: Lint homepage pattern**

Run:

```powershell
php -l wordpress/wp-content/themes/vietnamguide-premium/patterns/homepage-sections.php
```

Expected:

```text
No syntax errors detected in wordpress/wp-content/themes/vietnamguide-premium/patterns/homepage-sections.php
```

- [ ] **Step 3: Commit homepage pattern**

```powershell
git add wordpress/wp-content/themes/vietnamguide-premium/patterns/homepage-sections.php
git commit -m "feat: add premium homepage block pattern"
```

### Task 11: Create and Publish the Homepage

**Files:**
- Modify: `ops/launch-checklist.md`
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Deploy theme updates**

Run:

```powershell
scp -P $env:VG_SSH_PORT -r wordpress/wp-content/themes/vietnamguide-premium "$env:VG_SSH_USER@$env:VG_SSH_HOST:`$WP_PATH/wp-content/themes/"
```

Expected: updated pattern files are on the server.

- [ ] **Step 2: Create homepage page**

In WordPress admin:

```text
Pages > Add New
Title: Home
Insert pattern: Homepage Premium Sections
Publish
```

Expected: Home page is published.

- [ ] **Step 3: Set static front page**

Run:

```bash
cd "$WP_PATH"
HOME_ID=$(wp post list --post_type=page --name=home --field=ID --allow-root)
wp option update show_on_front page --allow-root
wp option update page_on_front "$HOME_ID" --allow-root
wp option get page_on_front --allow-root
```

Expected: output is the Home page ID.

- [ ] **Step 4: Configure menus**

Run:

```bash
cd "$WP_PATH"
wp menu create "Primary Navigation" --allow-root
wp menu item add-custom "Primary Navigation" "Plan" "/plan/" --allow-root
wp menu item add-custom "Primary Navigation" "Destinations" "/destinations/" --allow-root
wp menu item add-custom "Primary Navigation" "Itineraries" "/itineraries/" --allow-root
wp menu item add-custom "Primary Navigation" "Compare" "/compare/" --allow-root
wp menu item add-custom "Primary Navigation" "Costs" "/costs/" --allow-root
```

Expected: primary menu exists with five items.

- [ ] **Step 5: Visually verify homepage**

Open:

```text
https://vietnamguide.net/
```

Expected:

- Brand visible in first viewport.
- Hero image loads.
- CTAs visible.
- Trip selector visible below hero.
- No obvious layout overlap on mobile.

- [ ] **Step 6: Record homepage verification**

Update `ops/verification-log.md`:

```markdown
## Homepage

- Static homepage ID:
- Hero verified desktop:
- Hero verified mobile:
- Primary navigation verified:
- CTAs verified:
```

- [ ] **Step 7: Commit notes**

```powershell
git add ops/launch-checklist.md ops/verification-log.md
git commit -m "docs: verify premium homepage setup"
```

## Phase 5: Page Templates and Sample Pages

### Task 12: Create Four Sample Guide Pages

**Files:**
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Create destination sample page**

In WordPress admin:

```text
Pages > Add New
Title: Hoi An Travel Guide
Slug: /destinations/hoi-an/
Insert patterns:
- Quick Verdict
- At A Glance
- Source Block
Publish as draft or private until content is complete
```

Expected: page exists at `/destinations/hoi-an/`.

- [ ] **Step 2: Create itinerary sample page**

```text
Pages > Add New
Title: 14 Days in Vietnam
Slug: /itineraries/14-days-in-vietnam/
Insert patterns:
- Quick Verdict
- Itinerary Timeline
- Source Block
Publish as draft or private until content is complete
```

Expected: page exists at `/itineraries/14-days-in-vietnam/`.

- [ ] **Step 3: Create comparison sample page**

```text
Pages > Add New
Title: Ha Long Bay vs Lan Ha Bay
Slug: /compare/ha-long-bay-vs-lan-ha-bay/
Insert patterns:
- Quick Verdict
- Decision Table
- Source Block
Publish as draft or private until content is complete
```

Expected: page exists at `/compare/ha-long-bay-vs-lan-ha-bay/`.

- [ ] **Step 4: Create practical guide sample page**

```text
Pages > Add New
Title: Vietnam E-Visa Guide
Slug: /plan/vietnam-evisa/
Insert patterns:
- Quick Verdict
- Source Block
Publish as draft or private until content is complete
```

Expected: page exists at `/plan/vietnam-evisa/`.

- [ ] **Step 5: Verify sample URLs**

Run:

```powershell
curl.exe -I https://vietnamguide.net/destinations/hoi-an/
curl.exe -I https://vietnamguide.net/itineraries/14-days-in-vietnam/
curl.exe -I https://vietnamguide.net/compare/ha-long-bay-vs-lan-ha-bay/
curl.exe -I https://vietnamguide.net/plan/vietnam-evisa/
```

Expected: each returns `200` if public, or WordPress preview/admin-only state if kept draft/private.

- [ ] **Step 6: Record template verification**

Update `ops/verification-log.md`:

```markdown
## Sample Templates

- Destination sample:
- Itinerary sample:
- Comparison sample:
- Practical guide sample:
- Mobile layout checked:
- Desktop layout checked:
```

- [ ] **Step 7: Commit notes**

```powershell
git add ops/verification-log.md
git commit -m "docs: verify sample guide templates"
```

## Phase 6: SEO Launch Configuration

### Task 13: Configure WordPress SEO Basics

**Files:**
- Modify: `ops/launch-checklist.md`
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Configure permalink structure**

Run:

```bash
cd "$WP_PATH"
wp rewrite structure '/%category%/%postname%/' --allow-root
wp rewrite flush --allow-root
```

Expected:

```text
Success: Rewrite structure set.
Success: Rewrite rules flushed.
```

For page-based evergreen URLs, verify each parent page slug is created manually: `/plan/`, `/destinations/`, `/itineraries/`, `/compare/`, `/costs/`.

- [ ] **Step 2: Configure site title and tagline**

Run:

```bash
cd "$WP_PATH"
wp option update blogname "VietnamGuide.net" --allow-root
wp option update blogdescription "Premium practical Vietnam travel planning for international visitors." --allow-root
```

Expected: options update successfully.

- [ ] **Step 3: Configure SEO plugin wizard**

In WordPress admin:

```text
Rank Math > Setup Wizard
Site type: Travel Blog or Small Business Site
Organization name: VietnamGuide.net
Logo: upload official logo when available
Sitemap: enabled
Noindex empty category/tag archives: enabled
```

Expected: sitemap is available.

- [ ] **Step 4: Verify sitemap**

Run:

```powershell
curl.exe -I https://vietnamguide.net/sitemap_index.xml
```

Expected: `200`.

- [ ] **Step 5: Verify robots.txt**

Run:

```powershell
curl.exe https://vietnamguide.net/robots.txt
```

Expected includes:

```text
Sitemap: https://vietnamguide.net/sitemap_index.xml
```

- [ ] **Step 6: Configure indexation rules**

In SEO plugin:

```text
Noindex:
- Internal search pages
- Empty/thin tag archives
- Date archives
- Author archives until real author pages exist
Index:
- Pages
- Posts
- Categories only when used as content hubs
```

Expected: thin archive pages are not indexable.

- [ ] **Step 7: Record SEO setup**

Update `ops/verification-log.md`:

```markdown
## SEO Setup

- SEO plugin:
- Sitemap URL:
- Robots.txt checked:
- Noindex rules:
- Permalink structure:
```

- [ ] **Step 8: Commit notes**

```powershell
git add ops/launch-checklist.md ops/verification-log.md
git commit -m "docs: record SEO launch configuration"
```

### Task 14: Configure Analytics and Webmaster Tools

**Files:**
- Modify: `ops/launch-checklist.md`
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Create GA4 property**

In Google Analytics:

```text
Property name: VietnamGuide.net
Industry: Travel
Reporting time zone: Asia/Saigon or primary business time zone
Data stream: https://vietnamguide.net
```

Expected: GA4 measurement ID exists.

- [ ] **Step 2: Install GA4 tag**

Use Site Kit or manual tag insertion through the theme/header integration.

Expected: GA4 Realtime shows a test visit.

- [ ] **Step 3: Verify Search Console**

In Google Search Console:

```text
Property type: Domain property preferred
Domain: vietnamguide.net
Verification: DNS TXT
```

Expected: ownership verified.

- [ ] **Step 4: Submit sitemap**

In Search Console:

```text
Sitemaps > Add sitemap > sitemap_index.xml
```

Expected: submitted successfully.

- [ ] **Step 5: Configure Bing Webmaster Tools**

Import from Google Search Console or verify manually.

Expected: Bing can access sitemap.

- [ ] **Step 6: Configure tracked events**

Use GA4 or GTM to track:

```text
affiliate_click
newsletter_signup
pdf_download
internal_search
route_selector_click
```

Expected: events appear in GA4 DebugView or Realtime during testing.

- [ ] **Step 7: Record analytics setup**

Update `ops/verification-log.md`:

```markdown
## Analytics and Webmaster Tools

- GA4 installed:
- Search Console verified:
- Sitemap submitted:
- Bing verified:
- Event tracking tested:
```

- [ ] **Step 8: Commit notes**

```powershell
git add ops/launch-checklist.md ops/verification-log.md
git commit -m "docs: record analytics and webmaster setup"
```

## Phase 7: Performance, Accessibility, and Launch Verification

### Task 15: Configure Performance Baseline

**Files:**
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Enable page cache**

In cache plugin:

```text
Page cache: enabled
Browser cache: enabled
Object cache: enabled only if Redis/Memcached is configured
CSS minify: enabled only after visual check
JS defer/delay: enabled carefully, excluding admin and critical scripts
```

Expected: anonymous homepage responses are cached.

- [ ] **Step 2: Enable image optimization**

In image plugin or server config:

```text
WebP or AVIF: enabled
Lazy-load images: enabled
Set image dimensions: enabled
```

Expected: uploaded images generate optimized versions.

- [ ] **Step 3: Test homepage headers**

Run:

```powershell
curl.exe -I https://vietnamguide.net/
```

Expected:

```text
HTTP/2 200 or HTTP/1.1 200
cache header from cache layer or CDN present
content-type: text/html
```

- [ ] **Step 4: Run PageSpeed**

Open:

```text
https://pagespeed.web.dev/
```

Test:

```text
https://vietnamguide.net/
```

Expected initial target:

```text
Mobile performance: 75+
Desktop performance: 90+
No severe CLS issue
LCP element identified and acceptable for first launch
```

- [ ] **Step 5: Record performance**

Update `ops/verification-log.md`:

```markdown
## Performance

- Homepage mobile score:
- Homepage desktop score:
- LCP:
- INP/TBT lab proxy:
- CLS:
- Cache status:
- Image optimization status:
```

- [ ] **Step 6: Commit notes**

```powershell
git add ops/verification-log.md
git commit -m "docs: record performance baseline"
```

### Task 16: Accessibility and Responsive QA

**Files:**
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Desktop visual check**

Test these widths:

```text
1440px
1280px
1024px
```

Expected:

- Header does not overlap hero text.
- Hero text contrast is readable.
- CTAs are visible.
- No card-inside-card layout.
- Tables do not overflow.

- [ ] **Step 2: Mobile visual check**

Test these widths:

```text
390px
430px
768px
```

Expected:

- Menu opens and closes.
- Search remains reachable.
- Hero text fits.
- Buttons have at least 44px tap height.
- Tables stack or scroll cleanly.

- [ ] **Step 3: Keyboard navigation check**

Use keyboard:

```text
Tab
Shift+Tab
Enter
Escape for menu/search if supported
```

Expected:

- Focus is visible.
- Navigation order is logical.
- Menu/search controls are reachable.

- [ ] **Step 4: Reduced motion check**

In browser dev tools or OS settings, enable reduced motion.

Expected:

- Entrance/reveal animations do not run.
- Content remains visible.

- [ ] **Step 5: Record accessibility results**

Update `ops/verification-log.md`:

```markdown
## Accessibility

- Desktop responsive check:
- Mobile responsive check:
- Keyboard check:
- Reduced motion check:
- Contrast issues:
```

- [ ] **Step 6: Commit notes**

```powershell
git add ops/verification-log.md
git commit -m "docs: record responsive and accessibility QA"
```

### Task 17: Launch Gate Verification

**Files:**
- Modify: `ops/launch-checklist.md`
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Verify public URLs**

Run:

```powershell
curl.exe -I https://vietnamguide.net/
curl.exe -I https://vietnamguide.net/plan/
curl.exe -I https://vietnamguide.net/destinations/
curl.exe -I https://vietnamguide.net/itineraries/
curl.exe -I https://vietnamguide.net/compare/
curl.exe -I https://vietnamguide.net/costs/
```

Expected: each public URL returns `200` or a deliberate `301` to its canonical URL.

- [ ] **Step 2: Verify no accidental staging indexation**

Run:

```powershell
curl.exe -s https://vietnamguide.net/ | Select-String -Pattern "noindex"
```

Expected: no `noindex` on homepage after launch.

- [ ] **Step 3: Verify sitemap contains core URLs**

Run:

```powershell
curl.exe -s https://vietnamguide.net/sitemap_index.xml
```

Expected: sitemap index references page/post sitemaps.

- [ ] **Step 4: Verify affiliate disclosure page**

Create page if missing:

```text
Title: Affiliate Disclosure
Slug: /affiliate-disclosure/
Content: Explain that VietnamGuide.net may earn commissions from some links, at no extra cost to readers, and that recommendations are editorially selected.
```

Expected: `/affiliate-disclosure/` exists before any affiliate links go live.

- [ ] **Step 5: Complete launch checklist**

Mark completed items in `ops/launch-checklist.md`.

- [ ] **Step 6: Commit launch verification**

```powershell
git add ops/launch-checklist.md ops/verification-log.md
git commit -m "docs: complete launch gate verification"
```

## Phase 8: First Content Release

### Task 18: Publish the First Five Foundation Pages

**Files:**
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Publish `Vietnam Travel Guide`**

Page settings:

```text
Title: Vietnam Travel Guide
Slug: /plan/vietnam-travel-guide/
Template patterns: Quick Verdict, At A Glance, Source Block
Primary internal links: Best Time, E-Visa, 10 Days, 14 Days
```

Expected: page published and linked from homepage or Plan hub.

- [ ] **Step 2: Publish `Vietnam E-Visa Guide`**

```text
Title: Vietnam E-Visa Guide
Slug: /plan/vietnam-evisa/
Template patterns: Quick Verdict, Source Block
Source links: Vietnam.travel visa requirements, official e-visa portal
Review cadence: Monthly
```

Expected: page includes visible last reviewed date.

- [ ] **Step 3: Publish `Best Time to Visit Vietnam`**

```text
Title: Best Time to Visit Vietnam
Slug: /plan/best-time-to-visit-vietnam/
Template patterns: Quick Verdict, Decision Table, Source Block
Internal links: Vietnam Rainy Season, Vietnam in December, 10 Days in Vietnam
```

Expected: page answers the main question in the first 150 words.

- [ ] **Step 4: Publish `10 Days in Vietnam`**

```text
Title: 10 Days in Vietnam
Slug: /itineraries/10-days-in-vietnam/
Template patterns: Quick Verdict, Itinerary Timeline, Source Block
Internal links: Hanoi, Hoi An, Ho Chi Minh City, Ninh Binh
```

Expected: route summary and day-by-day structure are visible.

- [ ] **Step 5: Publish `Vietnam Travel Cost`**

```text
Title: Vietnam Travel Cost
Slug: /costs/vietnam-travel-cost/
Template patterns: Quick Verdict, Decision Table, Source Block
Internal links: Cash and ATMs, SIM/eSIM, 10 Days in Vietnam
```

Expected: budget ranges are clear and dated.

- [ ] **Step 6: Request indexing for priority pages**

Use Search Console URL Inspection for each page.

Expected: pages are crawlable and submitted.

- [ ] **Step 7: Record first release**

Update `ops/verification-log.md`:

```markdown
## First Content Release

- Vietnam Travel Guide:
- Vietnam E-Visa Guide:
- Best Time to Visit Vietnam:
- 10 Days in Vietnam:
- Vietnam Travel Cost:
- Search Console inspection completed:
```

- [ ] **Step 8: Commit release notes**

```powershell
git add ops/verification-log.md
git commit -m "docs: record first foundation content release"
```

## Final Acceptance Criteria

The first implementation pass is complete when:

- DNS and HTTPS are working for canonical domain.
- WordPress admin is hardened.
- Backups are configured and restore-tested.
- Parent theme, child theme, and site-specific mu-plugin are active.
- Premium design tokens and base CSS are deployed.
- Core VietnamGuide block patterns are visible in the editor.
- Homepage is live and premium direction is recognizable.
- Four guide template samples exist.
- SEO plugin, sitemap, robots, and indexation rules are configured.
- GA4, Search Console, and Bing Webmaster Tools are configured.
- Homepage passes initial performance and responsive checks.
- First five foundation pages are published or ready for final editorial review.

## Execution Notes

- Commit after each task.
- Do not commit credentials, database dumps, backup archives, uploaded media, or server profile files.
- Prefer server snapshots before theme/plugin changes.
- If a cache or optimization setting breaks layout, disable that setting and record it in `ops/verification-log.md`.
- If a plugin adds heavy scripts globally, remove it unless it is essential for launch.
- If the parent theme differs from GeneratePress, update `style.css` `Template:` and record the decision in `ops/verification-log.md`.



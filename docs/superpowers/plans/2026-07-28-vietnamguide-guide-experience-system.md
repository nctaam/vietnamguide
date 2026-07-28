# VietnamGuide Guide Experience System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Launch the approved Stitch + Editorial Spine experience on four allowlisted VietnamGuide pages without rewriting production content, while preserving a safe fallback for every other page.

**Architecture:** Add a conventional `page.php` router backed by focused routing, content, and context providers. The guide template extracts only a recognized leading hero block, renders the remaining Gutenberg content through normal WordPress filters, generates a server-side H2 table of contents, and progressively enhances navigation with scoped CSS and JavaScript. A contract/mutation suite and live/public verifiers gate a guarded four-page production rollout.

**Tech Stack:** WordPress 6.6+, PHP 8.1, Gutenberg block parsing, PowerShell contract tests, vanilla JavaScript, CSS Grid, WordPress admin deployment, browser-based responsive QA.

---

## Scope Check

This plan implements one coherent page-experience system. The guide types share routing, context, rendering, assets, fallbacks, and verification; their differences are expressed by a type class and existing semantic content modules rather than separate page-template forks.

## File Structure

### New files

- `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php`
  - Pilot allowlist, path normalization, guide classification, and enablement.
- `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php`
  - Leading hero extraction, standard content filtering, H2 normalization, and TOC rendering.
- `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php`
  - Normalized context, reading time, trust values, source count, and related-route fallbacks.
- `wordpress/wp-content/themes/vietnamguide-premium/page.php`
  - Page-specific WordPress router.
- `wordpress/wp-content/themes/vietnamguide-premium/template-parts/content-page.php`
  - Existing default page presentation moved out of `index.php` behavior.
- `wordpress/wp-content/themes/vietnamguide-premium/template-parts/guide-page.php`
  - Shared Stitch + Editorial Spine shell.
- `wordpress/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css`
  - Scoped guide layout and compatibility styling.
- `wordpress/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js`
  - Active TOC and reading-progress enhancement.
- `ops/verify-guide-experience.ps1`
  - Local source contract.
- `ops/verify-guide-experience-mutations.ps1`
  - Required negative/mutation coverage.
- `ops/verify-guide-experience-live.php`
  - WordPress runtime contract.
- `ops/verify-guide-experience-public.ps1`
  - Public HTTP and rendered-HTML gate.

### Modified files

- `wordpress/wp-content/themes/vietnamguide-premium/functions.php`
  - Load providers and enqueue guide assets only on enabled pages.
- `wordpress/wp-content/themes/vietnamguide-premium/footer.php`
  - Correct the published Source policy URL.
- `ops/verification-log.md`
  - Record local and production evidence.

## Baseline Constraints

- Local PHP CLI is unavailable. Local TDD therefore uses source contracts, negative mutations, JavaScript syntax checks, and rendered public checks.
- Real PHP behavior is verified on the live WordPress runtime before production acceptance.
- Existing untracked roadmap and content-operation files in the main checkout are user-owned and must not be staged or modified.
- Production SSH at `66.42.48.146:2209` is expected to remain closed or filtered. Use the guarded WordPress admin deployment route unless a fresh connection check proves SSH available.

---

### Task 1: Add Guide Routing and Pilot Feature Gate

**Files:**
- Create: `ops/verify-guide-experience.ps1`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/functions.php:1-3`

- [ ] **Step 1: Write the failing routing contract**

Create `ops/verify-guide-experience.ps1` with this initial contract:

```powershell
param(
    [string]$RepoRootOverride = ''
)

$ErrorActionPreference = 'Stop'
$RepoRoot = if ($RepoRootOverride) { $RepoRootOverride } else { Split-Path -Parent $PSScriptRoot }
$ThemeRoot = 'wordpress/wp-content/themes/vietnamguide-premium'
$Failures = [System.Collections.Generic.List[string]]::new()

function Get-RepoContent {
    param([string]$RelativePath)
    $FullPath = Join-Path $RepoRoot $RelativePath
    if (-not (Test-Path -LiteralPath $FullPath -PathType Leaf)) { return $null }
    $Content = Get-Content -LiteralPath $FullPath -Raw
    if ($null -eq $Content) { return '' }
    return $Content
}

function Require-File {
    param([string]$RelativePath)
    if ($null -eq (Get-RepoContent $RelativePath)) { $Failures.Add("Missing file: $RelativePath") }
}

function Require-Contains {
    param([string]$RelativePath, [string]$Needle)
    $Content = Get-RepoContent $RelativePath
    if ($null -ne $Content -and -not $Content.Contains($Needle)) {
        $Failures.Add("Missing substring in ${RelativePath}: $Needle")
    }
}

function Require-NotContains {
    param([string]$RelativePath, [string]$Needle)
    $Content = Get-RepoContent $RelativePath
    if ($null -ne $Content -and $Content.Contains($Needle)) {
        $Failures.Add("Unexpected substring in ${RelativePath}: $Needle")
    }
}

$Routing = "$ThemeRoot/inc/guide-routing.php"
$Functions = "$ThemeRoot/functions.php"

Require-File $Routing
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-routing.php');"
Require-Contains $Routing 'function vg_guide_pilot_paths(): array'
Require-Contains $Routing 'function vg_classify_guide_path(string $path): ?string'
Require-Contains $Routing 'function vg_get_guide_path(?WP_Post $post = null): string'
Require-Contains $Routing 'function vg_get_guide_type(?WP_Post $post = null): ?string'
Require-Contains $Routing 'function vg_is_guide_experience_page(?WP_Post $post = null): bool'
Require-Contains $Routing "'destinations/ho-chi-minh-city-travel-guide'"
Require-Contains $Routing "'itineraries/10-days-in-vietnam'"
Require-Contains $Routing "'compare/ha-long-bay-vs-lan-ha-bay'"
Require-Contains $Routing "'plan/vietnam-evisa'"
Require-Contains $Routing "'destinations' => 'destination'"
Require-Contains $Routing "'itineraries' => 'itinerary'"
Require-Contains $Routing "'compare' => 'comparison'"
Require-Contains $Routing "'plan' => 'practical'"
Require-Contains $Routing 'if (! in_array($path, vg_guide_pilot_paths(), true)) {'

if ($Failures.Count -gt 0) {
    $Failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output 'VietnamGuide guide experience checks passed.'
```

- [ ] **Step 2: Run the routing contract and verify RED**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
```

Expected: exit `1` with `Missing file: wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php`.

- [ ] **Step 3: Implement the routing provider**

Create `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php`:

```php
<?php
if (! defined('ABSPATH')) {
    exit;
}

function vg_guide_pilot_paths(): array
{
    return [
        'destinations/ho-chi-minh-city-travel-guide',
        'itineraries/10-days-in-vietnam',
        'compare/ha-long-bay-vs-lan-ha-bay',
        'plan/vietnam-evisa',
    ];
}

function vg_classify_guide_path(string $path): ?string
{
    $normalized = trim($path, '/');
    if ($normalized === '') {
        return null;
    }

    $segments = explode('/', $normalized);
    $types = [
        'destinations' => 'destination',
        'itineraries' => 'itinerary',
        'compare' => 'comparison',
        'plan' => 'practical',
    ];

    return $types[$segments[0]] ?? null;
}

function vg_get_guide_path(?WP_Post $post = null): string
{
    $post = $post ?: get_post();
    if (! $post instanceof WP_Post || $post->post_type !== 'page') {
        return '';
    }

    return trim((string) get_page_uri($post), '/');
}

function vg_get_guide_type(?WP_Post $post = null): ?string
{
    $post = $post ?: get_post();
    $path = vg_get_guide_path($post);
    $type = vg_classify_guide_path($path);
    $filtered = apply_filters('vg_guide_type', $type, $post, $path);

    return in_array($filtered, ['destination', 'itinerary', 'comparison', 'practical'], true)
        ? $filtered
        : null;
}

function vg_is_guide_experience_page(?WP_Post $post = null): bool
{
    $post = $post ?: get_post();
    if (! $post instanceof WP_Post || ! is_page($post)) {
        return false;
    }

    $path = vg_get_guide_path($post);
    if (! in_array($path, vg_guide_pilot_paths(), true)) {
        return false;
    }

    return vg_get_guide_type($post) !== null;
}
```

- [ ] **Step 4: Load the routing provider**

Add directly after the homepage data require in `functions.php`:

```php
require_once get_theme_file_path('/inc/guide-routing.php');
```

- [ ] **Step 5: Run the routing contract and existing theme contracts**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-homepage-theme.ps1
```

Expected:

```text
VietnamGuide guide experience checks passed.
VietnamGuide homepage theme checks passed.
```

- [ ] **Step 6: Commit routing**

```powershell
git add ops/verify-guide-experience.ps1 wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php wordpress/wp-content/themes/vietnamguide-premium/functions.php
git commit -m "feat: gate pilot guide experience pages"
```

---

### Task 2: Extract Guide Hero and Generate Server-Side TOC

**Files:**
- Create: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/functions.php:1-5`
- Modify: `ops/verify-guide-experience.ps1`

- [ ] **Step 1: Extend the contract for content preparation**

Append these checks before the failure block in `ops/verify-guide-experience.ps1`:

```powershell
$ContentProvider = "$ThemeRoot/inc/guide-content.php"
Require-File $ContentProvider
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-content.php');"
Require-Contains $ContentProvider 'function vg_split_guide_blocks(string $postContent): ?array'
Require-Contains $ContentProvider 'function vg_prepare_guide_headings(string $html): array'
Require-Contains $ContentProvider 'function vg_render_guide_toc(array $headings, string $className): string'
Require-Contains $ContentProvider 'function vg_prepare_guide_content(WP_Post $post): ?array'
Require-Contains $ContentProvider "'hero_html'"
Require-Contains $ContentProvider "'body_html'"
Require-Contains $ContentProvider "'headings'"
Require-Contains $ContentProvider 'vg-guide-hero'
Require-Contains $ContentProvider '<h2\b'
Require-Contains $ContentProvider "preg_match_all('/<h1\\b/i', `$heroSource) !== 1"
Require-Contains $ContentProvider 'data-vg-toc="false"'
Require-Contains $ContentProvider 'sanitize_title'
Require-Contains $ContentProvider 'serialize_blocks'
Require-Contains $ContentProvider "apply_filters('the_content'"
```

- [ ] **Step 2: Run the contract and verify RED**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
```

Expected: exit `1` with the missing `inc/guide-content.php` failure.

- [ ] **Step 3: Implement block extraction and heading normalization**

Create `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php`:

```php
<?php
if (! defined('ABSPATH')) {
    exit;
}

function vg_is_empty_freeform_block(array $block): bool
{
    return ($block['blockName'] ?? null) === null
        && trim((string) ($block['innerHTML'] ?? '')) === '';
}

function vg_split_guide_blocks(string $postContent): ?array
{
    $blocks = parse_blocks($postContent);
    while ($blocks !== [] && vg_is_empty_freeform_block($blocks[0])) {
        array_shift($blocks);
    }

    if ($blocks === []) {
        return null;
    }

    $heroBlock = $blocks[0];
    $heroSource = serialize_block($heroBlock);
    if (strpos($heroSource, 'vg-guide-hero') === false) {
        return null;
    }

    if (preg_match_all('/<h1\b/i', $heroSource) !== 1) {
        return null;
    }

    array_shift($blocks);
    $bodySource = serialize_blocks($blocks);
    if (preg_match('/<h1\b/i', $bodySource)) {
        return null;
    }

    return [
        'hero_source' => $heroSource,
        'body_source' => $bodySource,
    ];
}

function vg_prepare_guide_headings(string $html): array
{
    $headings = [];
    $usedIds = [];
    $pattern = '/<h2\b([^>]*)>(.*?)<\/h2>/is';

    $normalized = preg_replace_callback(
        $pattern,
        static function (array $matches) use (&$headings, &$usedIds): string {
            $attributes = $matches[1];
            $innerHtml = $matches[2];

            if (preg_match('/data-vg-toc=(?:"false"|\'false\')/i', $attributes)) {
                return $matches[0];
            }

            $label = trim(wp_strip_all_tags($innerHtml));
            if ($label === '') {
                return $matches[0];
            }

            $id = '';
            if (preg_match('/\bid=(?:"([^\"]+)"|\'([^\']+)\')/i', $attributes, $idMatch)) {
                $id = (string) ($idMatch[1] !== '' ? $idMatch[1] : $idMatch[2]);
            }

            $base = sanitize_title($id !== '' ? $id : $label);
            if ($base === '') {
                $base = 'section';
            }

            $candidate = $base;
            $suffix = 2;
            while (isset($usedIds[$candidate])) {
                $candidate = $base . '-' . $suffix;
                $suffix++;
            }
            $usedIds[$candidate] = true;

            if ($id !== '') {
                $attributes = preg_replace(
                    '/\bid=(?:"[^\"]*"|\'[^\']*\')/i',
                    'id="' . esc_attr($candidate) . '"',
                    $attributes,
                    1
                );
            } else {
                $attributes .= ' id="' . esc_attr($candidate) . '"';
            }

            $headings[] = ['id' => $candidate, 'label' => $label];

            return '<h2' . $attributes . '>' . $innerHtml . '</h2>';
        },
        $html
    );

    return [
        'html' => is_string($normalized) ? $normalized : $html,
        'headings' => $headings,
    ];
}

function vg_render_guide_toc(array $headings, string $className = 'vg-guide-toc'): string
{
    if (count($headings) < 2) {
        return '';
    }

    $items = '';
    foreach ($headings as $heading) {
        $items .= sprintf(
            '<li><a href="#%1$s">%2$s</a></li>',
            esc_attr((string) $heading['id']),
            esc_html((string) $heading['label'])
        );
    }

    return sprintf(
        '<nav class="%1$s" aria-label="%2$s"><ol>%3$s</ol></nav>',
        esc_attr($className),
        esc_attr__('On this page', 'vietnamguide-premium'),
        $items
    );
}

function vg_prepare_guide_content(WP_Post $post): ?array
{
    $split = vg_split_guide_blocks((string) $post->post_content);
    if ($split === null) {
        return null;
    }

    $heroHtml = apply_filters('the_content', $split['hero_source']);
    $bodyHtml = apply_filters('the_content', $split['body_source']);
    $prepared = vg_prepare_guide_headings((string) $bodyHtml);

    return [
        'hero_html' => (string) $heroHtml,
        'body_html' => $prepared['html'],
        'headings' => $prepared['headings'],
        'toc_html' => vg_render_guide_toc($prepared['headings']),
    ];
}
```

- [ ] **Step 4: Load the content provider**

Add after the guide routing require in `functions.php`:

```php
require_once get_theme_file_path('/inc/guide-content.php');
```

- [ ] **Step 5: Run guide, homepage, and whitespace checks**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-homepage-theme.ps1
git diff --check
```

Expected: both verifier success messages and no `git diff --check` output.

- [ ] **Step 6: Commit content preparation**

```powershell
git add ops/verify-guide-experience.ps1 wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php wordpress/wp-content/themes/vietnamguide-premium/functions.php
git commit -m "feat: prepare guide content and table of contents"
```

---

### Task 3: Build Normalized Guide Context and Related Routes

**Files:**
- Create: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/functions.php:1-6`
- Modify: `ops/verify-guide-experience.ps1`

- [ ] **Step 1: Add failing context contracts**

Append before the failure block:

```powershell
$ContextProvider = "$ThemeRoot/inc/guide-context.php"
Require-File $ContextProvider
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-context.php');"
Require-Contains $ContextProvider 'function vg_estimate_guide_reading_time(string $html): int'
Require-Contains $ContextProvider 'function vg_count_guide_sources(string $html): int'
Require-Contains $ContextProvider 'function vg_get_related_routes(WP_Post $post, bool $hasExisting): array'
Require-Contains $ContextProvider 'function vg_build_guide_context(WP_Post $post): ?array'
Require-Contains $ContextProvider "'_vg_reviewed_at'"
Require-Contains $ContextProvider 'vg-related-routes'
Require-Contains $ContextProvider 'if ($hasExisting) {'
Require-Contains $ContextProvider "'has_existing_related_routes'"
Require-Contains $ContextProvider "'reading_time'"
Require-Contains $ContextProvider "'source_count'"
Require-Contains $ContextProvider "'best_for'"
Require-Contains $ContextProvider "'skip_if'"
Require-Contains $ContextProvider "'related_routes'"
```

- [ ] **Step 2: Run the contract and verify RED**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
```

Expected: missing `inc/guide-context.php` failure.

- [ ] **Step 3: Implement the context provider**

Create `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php`:

```php
<?php
if (! defined('ABSPATH')) {
    exit;
}

function vg_estimate_guide_reading_time(string $html): int
{
    $words = str_word_count(wp_strip_all_tags($html));
    return max(1, (int) ceil($words / 220));
}

function vg_extract_guide_data_value(string $html, string $attribute): string
{
    $attributePattern = preg_quote($attribute, '/');
    if (! preg_match('/\b' . $attributePattern . '=(?:"([^\"]*)"|\'([^\']*)\')/i', $html, $match)) {
        return '';
    }

    return sanitize_text_field((string) ($match[1] !== '' ? $match[1] : $match[2]));
}

function vg_count_guide_sources(string $html): int
{
    if (! class_exists('DOMDocument') || ! class_exists('DOMXPath')) {
        return 0;
    }

    $previous = libxml_use_internal_errors(true);
    $document = new DOMDocument();
    $loaded = $document->loadHTML(
        '<?xml encoding="utf-8" ?><div id="vg-source-root">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (! $loaded) {
        return 0;
    }

    $xpath = new DOMXPath($document);
    $anchors = $xpath->query(
        '//*[@id="vg-source-root"]//*['
        . 'contains(@class, "vg-pattern-source-block") or '
        . 'contains(@class, "source-diversity") or '
        . 'contains(@class, "source-trail")'
        . ']//a[@href]'
    );
    if (! $anchors instanceof DOMNodeList) {
        return 0;
    }

    $homeHost = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $sources = [];
    foreach ($anchors as $anchor) {
        if (! $anchor instanceof DOMElement) {
            continue;
        }

        $href = trim($anchor->getAttribute('href'));
        $host = strtolower((string) wp_parse_url($href, PHP_URL_HOST));
        if ($href !== '' && $host !== '' && $host !== $homeHost) {
            $sources[$href] = true;
        }
    }

    return count($sources);
}

function vg_get_related_routes(WP_Post $post, bool $hasExisting): array
{
    if ($hasExisting) {
        return [];
    }

    $routes = [];
    if ($post->post_parent > 0) {
        $siblings = get_pages([
            'parent' => $post->post_parent,
            'post_status' => 'publish',
            'sort_column' => 'menu_order,post_title',
            'sort_order' => 'ASC',
        ]);

        foreach ($siblings as $sibling) {
            if (! $sibling instanceof WP_Post || $sibling->ID === $post->ID) {
                continue;
            }

            $routes[] = [
                'title' => get_the_title($sibling),
                'url' => get_permalink($sibling),
            ];

            if (count($routes) === 3) {
                break;
            }
        }
    }

    if ($routes === [] && $post->post_parent > 0) {
        $routes[] = [
            'title' => get_the_title($post->post_parent),
            'url' => get_permalink($post->post_parent),
        ];
    }

    return array_values(array_filter($routes, static function (array $route): bool {
        return $route['title'] !== '' && is_string($route['url']) && $route['url'] !== '';
    }));
}

function vg_build_guide_context(WP_Post $post): ?array
{
    $content = vg_prepare_guide_content($post);
    $type = vg_get_guide_type($post);
    if ($content === null || $type === null) {
        return null;
    }

    $reviewed = trim((string) get_post_meta($post->ID, '_vg_reviewed_at', true));
    if ($reviewed === '') {
        $reviewed = get_the_modified_date('F j, Y', $post);
    }

    $hasExistingRelated = strpos($content['body_html'], 'vg-related-routes') !== false;

    return [
        'post_id' => $post->ID,
        'type' => $type,
        'title' => get_the_title($post),
        'permalink' => get_permalink($post),
        'hero_html' => $content['hero_html'],
        'body_html' => $content['body_html'],
        'headings' => $content['headings'],
        'toc_html' => $content['toc_html'],
        'reviewed_at' => $reviewed,
        'reading_time' => vg_estimate_guide_reading_time($content['body_html']),
        'source_count' => vg_count_guide_sources($content['body_html']),
        'best_for' => vg_extract_guide_data_value($content['body_html'], 'data-vg-best-for'),
        'skip_if' => vg_extract_guide_data_value($content['body_html'], 'data-vg-skip-if'),
        'related_routes' => vg_get_related_routes($post, $hasExistingRelated),
        'has_existing_related_routes' => $hasExistingRelated,
    ];
}
```

- [ ] **Step 4: Load the context provider**

Add after `guide-content.php` in `functions.php`:

```php
require_once get_theme_file_path('/inc/guide-context.php');
```

- [ ] **Step 5: Run local contracts**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-block-patterns.ps1
git diff --check
```

Expected: both verifier success messages and no whitespace errors.

- [ ] **Step 6: Commit context provider**

```powershell
git add ops/verify-guide-experience.ps1 wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php wordpress/wp-content/themes/vietnamguide-premium/functions.php
git commit -m "feat: normalize guide trust and related context"
```

---

### Task 4: Route Pages Through the Shared Editorial Spine Template

**Files:**
- Create: `wordpress/wp-content/themes/vietnamguide-premium/page.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/template-parts/content-page.php`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/template-parts/guide-page.php`
- Modify: `ops/verify-guide-experience.ps1`

- [ ] **Step 1: Add failing template contracts**

Append before the failure block:

```powershell
$PageTemplate = "$ThemeRoot/page.php"
$DefaultPart = "$ThemeRoot/template-parts/content-page.php"
$GuidePart = "$ThemeRoot/template-parts/guide-page.php"
Require-File $PageTemplate
Require-File $DefaultPart
Require-File $GuidePart
Require-Contains $PageTemplate 'vg_is_guide_experience_page($post)'
Require-Contains $PageTemplate 'vg_build_guide_context($post)'
Require-Contains $PageTemplate "get_template_part('template-parts/guide', 'page', `$guideContext);"
Require-Contains $PageTemplate "get_template_part('template-parts/content', 'page');"
Require-Contains $DefaultPart '<h1>'
Require-Contains $DefaultPart 'the_content();'
Require-Contains $GuidePart 'data-vg-guide'
Require-Contains $GuidePart 'data-vg-guide-type'
Require-Contains $GuidePart 'vg-guide-jump'
Require-Contains $GuidePart 'vg-guide-spine'
Require-Contains $GuidePart 'vg-guide-article'
Require-Contains $GuidePart 'vg-guide-trust'
Require-Contains $GuidePart 'vg-guide-related'
Require-NotContains $GuidePart '<h1'
```

- [ ] **Step 2: Run the contract and verify RED**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
```

Expected: missing `page.php` and template-part failures.

- [ ] **Step 3: Add the conventional page router**

Create `wordpress/wp-content/themes/vietnamguide-premium/page.php`:

```php
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

        if ($post instanceof WP_Post && vg_is_guide_experience_page($post)) {
            $guideContext = vg_build_guide_context($post);
        }

        if (is_array($guideContext)) {
            get_template_part('template-parts/guide', 'page', $guideContext);
        } else {
            get_template_part('template-parts/content', 'page');
        }
        ?>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>
```

- [ ] **Step 4: Preserve the default page presentation**

Create `wordpress/wp-content/themes/vietnamguide-premium/template-parts/content-page.php`:

```php
<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<section class="vg-section">
    <div class="vg-shell">
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="vg-section-heading">
                <h1><?php echo esc_html(get_the_title()); ?></h1>
            </header>
            <div class="vg-entry-content">
                <?php the_content(); ?>
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
```

- [ ] **Step 5: Implement the shared guide shell**

Create `wordpress/wp-content/themes/vietnamguide-premium/template-parts/guide-page.php`:

```php
<?php
if (! defined('ABSPATH') || ! isset($args['type'], $args['hero_html'], $args['body_html'])) {
    return;
}

$type = (string) $args['type'];
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

    <div class="vg-guide-meta" aria-label="<?php esc_attr_e('Guide details', 'vietnamguide-premium'); ?>">
        <span><?php echo esc_html(ucfirst($type)); ?></span>
        <?php if ($readingTime > 0) : ?>
            <span><?php echo esc_html(sprintf(_n('%d minute read', '%d minute read', $readingTime, 'vietnamguide-premium'), $readingTime)); ?></span>
        <?php endif; ?>
        <?php if ($reviewed !== '') : ?>
            <span><?php echo esc_html(sprintf(__('Reviewed %s', 'vietnamguide-premium'), $reviewed)); ?></span>
        <?php endif; ?>
        <?php if ($sourceCount > 0) : ?>
            <span><?php echo esc_html(sprintf(_n('%d source', '%d sources', $sourceCount, 'vietnamguide-premium'), $sourceCount)); ?></span>
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
```

- [ ] **Step 6: Run template and existing contracts**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-homepage-theme.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-block-patterns.ps1
git diff --check
```

Expected: all three success messages and no whitespace errors.

- [ ] **Step 7: Commit templates**

```powershell
git add ops/verify-guide-experience.ps1 wordpress/wp-content/themes/vietnamguide-premium/page.php wordpress/wp-content/themes/vietnamguide-premium/template-parts/content-page.php wordpress/wp-content/themes/vietnamguide-premium/template-parts/guide-page.php
git commit -m "feat: render pilot pages with editorial spine"
```

---

### Task 5: Add Scoped Stitch Styling and Progressive Navigation

**Files:**
- Create: `wordpress/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css`
- Create: `wordpress/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/functions.php:17-22`
- Modify: `ops/verify-guide-experience.ps1`

- [ ] **Step 1: Add failing asset contracts**

Append before the failure block:

```powershell
$GuideCss = "$ThemeRoot/assets/css/guide-experience.css"
$GuideJs = "$ThemeRoot/assets/js/guide-experience.js"
Require-File $GuideCss
Require-File $GuideJs
Require-Contains $Functions "if (vg_is_guide_experience_page())"
Require-Contains $Functions "wp_enqueue_style('vietnamguide-guide-experience'"
Require-Contains $Functions "wp_enqueue_script('vietnamguide-guide-experience'"
Require-Contains $GuideCss '.vg-guide-spine'
Require-Contains $GuideCss 'grid-template-columns: minmax(148px, 190px) minmax(0, 760px) minmax(190px, 240px)'
Require-Contains $GuideCss '.vg-guide-jump'
Require-Contains $GuideCss '.vg-guide-trust'
Require-Contains $GuideCss '.vg-decision-table'
Require-Contains $GuideCss '.vg-timeline'
Require-Contains $GuideCss '@media (max-width: 960px)'
Require-Contains $GuideCss '@media (prefers-reduced-motion: reduce)'
Require-Contains $GuideJs "document.querySelector('[data-vg-guide]')"
Require-Contains $GuideJs 'IntersectionObserver'
Require-Contains $GuideJs "style.setProperty('--vg-guide-progress'"
Require-NotContains $GuideJs 'preventDefault()'
```

- [ ] **Step 2: Run the contract and verify RED**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
```

Expected: missing CSS and JavaScript files.

- [ ] **Step 3: Create the scoped guide stylesheet**

Create `assets/css/guide-experience.css` with the following code:

```css
.vg-guide-experience {
  --vg-guide-progress: 0;
  --vg-muted: #5e625e;
  --vg-green: var(--vg-jade);
  --vg-green-dark: #012d1d;
  color: var(--vg-ink);
  background: var(--vg-paper);
}

.vg-guide-experience::before {
  position: fixed;
  z-index: 120;
  top: 0;
  left: 0;
  width: calc(var(--vg-guide-progress) * 1%);
  height: 3px;
  content: "";
  pointer-events: none;
  background: var(--vg-gold);
}

.vg-guide-experience .vg-guide-hero {
  margin-block: 0;
}

.vg-guide-experience .vg-guide-hero-cover {
  min-height: clamp(520px, 70svh, 780px);
  display: flex;
  align-items: flex-end;
}

.vg-guide-experience .vg-guide-hero-inner {
  width: min(calc(100% - 96px), 1280px);
  margin-inline: auto;
  padding-block: clamp(72px, 10vw, 132px) clamp(52px, 8vw, 96px);
}

.vg-guide-experience .vg-guide-title {
  max-width: 920px;
  margin: 0;
  color: #fff;
  font-family: var(--vg-display);
  font-size: clamp(48px, 7vw, 96px);
  line-height: .96;
  letter-spacing: -.045em;
  text-wrap: balance;
}

.vg-guide-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 24px;
  width: min(calc(100% - 96px), 1280px);
  margin-inline: auto;
  padding-block: 18px;
  border-bottom: 1px solid rgba(16, 20, 23, .16);
  color: var(--vg-muted);
  font-size: 13px;
  font-weight: 700;
  letter-spacing: .06em;
  text-transform: uppercase;
}

.vg-guide-meta span + span::before {
  margin-right: 24px;
  content: "\00b7";
  color: var(--vg-gold);
}

.vg-guide-jump {
  display: none;
}

.vg-guide-spine {
  display: grid;
  grid-template-columns: minmax(148px, 190px) minmax(0, 760px) minmax(190px, 240px);
  gap: clamp(28px, 4vw, 64px);
  justify-content: center;
  width: min(calc(100% - 96px), 1280px);
  margin-inline: auto;
  padding-block: clamp(64px, 8vw, 112px);
}

.vg-guide-spine__toc .vg-guide-toc {
  position: sticky;
  top: 108px;
  max-height: calc(100svh - 140px);
  overflow: auto;
  border-left: 2px solid var(--vg-gold);
  padding-left: 18px;
}

.vg-guide-toc ol,
.vg-guide-jump ol {
  margin: 0;
  padding: 0;
  list-style: none;
}

.vg-guide-toc a {
  display: block;
  padding-block: 7px;
  color: var(--vg-muted);
  font-size: 14px;
  line-height: 1.35;
  text-decoration: none;
}

.vg-guide-toc a:hover,
.vg-guide-toc a:focus-visible,
.vg-guide-toc a.is-active {
  color: var(--vg-green);
}

.vg-guide-article {
  min-width: 0;
}

.vg-guide-article > :first-child {
  margin-top: 0;
}

.vg-guide-article h2 {
  margin: clamp(64px, 8vw, 104px) 0 24px;
  color: var(--vg-green);
  font-family: var(--vg-display);
  font-size: clamp(34px, 4vw, 56px);
  line-height: 1.05;
  letter-spacing: -.03em;
}

.vg-guide-article h3 {
  margin: 44px 0 16px;
  font-family: var(--vg-display);
  font-size: clamp(25px, 3vw, 34px);
  line-height: 1.15;
}

.vg-guide-article p,
.vg-guide-article li {
  font-size: clamp(17px, 1.45vw, 19px);
  line-height: 1.75;
}

.vg-guide-article a {
  color: var(--vg-green);
  text-decoration-color: rgba(14, 111, 92, .38);
  text-underline-offset: .18em;
}

.vg-guide-article .alignfull {
  width: 100vw;
  max-width: none;
  margin-inline: calc(50% - 50vw);
}

.vg-guide-article .alignwide {
  width: min(1080px, calc(100vw - 96px));
  max-width: none;
  margin-inline: max(calc((760px - min(1080px, calc(100vw - 96px))) / 2), calc(50% - 50vw + 48px));
}

.vg-guide-trust {
  position: sticky;
  top: 108px;
  align-self: start;
  display: grid;
  gap: 12px;
}

.vg-guide-trust > div {
  display: grid;
  gap: 5px;
  padding: 17px 18px;
  border-top: 3px solid var(--vg-gold);
  background: #fff;
  box-shadow: 0 12px 36px rgba(1, 45, 29, .07);
}

.vg-guide-trust strong {
  color: var(--vg-green);
  font-size: 12px;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.vg-guide-trust span {
  color: var(--vg-muted);
  font-size: 14px;
  line-height: 1.45;
}

.vg-guide-article .vg-concierge-verdict,
.vg-guide-article .vg-at-a-glance,
.vg-guide-article .vg-field-note,
.vg-guide-article .vg-related-routes {
  margin-block: clamp(36px, 6vw, 72px);
  padding: clamp(24px, 4vw, 40px);
  border: 1px solid rgba(16, 20, 23, .12);
  background: #fff;
}

.vg-guide-article .vg-decision-table {
  width: 100%;
  margin-block: 36px;
  overflow-x: auto;
  border: 1px solid rgba(16, 20, 23, .14);
  background: #fff;
  -webkit-overflow-scrolling: touch;
}

.vg-guide-article .vg-decision-table table {
  width: 100%;
  min-width: 680px;
  border-collapse: collapse;
}

.vg-guide-article .vg-decision-table th,
.vg-guide-article .vg-decision-table td {
  padding: 16px;
  border: 1px solid rgba(16, 20, 23, .12);
  text-align: left;
  vertical-align: top;
}

.vg-guide-article .vg-decision-table th {
  color: #fff;
  background: var(--vg-green-dark);
}

.vg-guide-article .vg-timeline {
  display: grid;
  gap: 0;
  margin-block: 40px;
  border-left: 2px solid var(--vg-gold);
}

.vg-guide-article .vg-timeline-item,
.vg-guide-article .vg-day {
  position: relative;
  padding: 0 0 38px 30px;
}

.vg-guide-article .vg-timeline-item::before,
.vg-guide-article .vg-day::before {
  position: absolute;
  top: .55em;
  left: -7px;
  width: 12px;
  height: 12px;
  border: 2px solid var(--vg-paper);
  border-radius: 50%;
  content: "";
  background: var(--vg-gold);
  box-shadow: 0 0 0 1px var(--vg-gold);
}

.vg-guide-article .vg-guide-photo,
.vg-guide-article .vg-guide-photo-feature,
.vg-guide-article .vg-guide-photo-copy {
  margin-block: 40px;
}

.vg-guide-article .vg-guide-photo-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20px;
  margin-block: 44px;
}

.vg-guide-article .vg-guide-photo-grid img,
.vg-guide-article .vg-guide-photo-feature img,
.vg-guide-article .vg-guide-photo img {
  width: 100%;
  border-radius: 4px;
}

.vg-guide-article .vg-image-credit {
  margin-top: 8px;
  color: var(--vg-muted);
  font-size: 12px;
  line-height: 1.4;
}

.vg-guide-article .vg-check-list,
.vg-guide-article .vg-feature-list,
.vg-guide-article .vg-faq-list {
  display: grid;
  gap: 14px;
  margin-block: 32px;
  padding: 0;
  list-style: none;
}

.vg-guide-article .vg-check-list > li,
.vg-guide-article .vg-feature-list > li,
.vg-guide-article .vg-faq-list > li {
  padding: 18px 20px;
  border-left: 3px solid var(--vg-gold);
  background: rgba(255, 255, 255, .72);
}

.vg-guide-article .vg-travel-guide-flow,
.vg-guide-article .vg-travel-route-family,
.vg-guide-article .vg-related-routes-context {
  margin-block: 44px;
}

.vg-guide-related {
  padding-block: clamp(64px, 8vw, 104px);
  border-top: 1px solid rgba(16, 20, 23, .14);
}

.vg-guide-related h2 {
  max-width: 760px;
  margin: 0 0 32px;
  color: var(--vg-green);
  font-family: var(--vg-display);
  font-size: clamp(36px, 5vw, 64px);
  line-height: 1;
}

.vg-guide-related ul {
  margin: 0;
  padding: 0;
  border-top: 1px solid rgba(16, 20, 23, .14);
  list-style: none;
}

.vg-guide-related a {
  display: flex;
  justify-content: space-between;
  gap: 24px;
  padding-block: 20px;
  border-bottom: 1px solid rgba(16, 20, 23, .14);
  color: var(--vg-ink);
  font-family: var(--vg-display);
  font-size: clamp(22px, 3vw, 34px);
  text-decoration: none;
}

@media (max-width: 1100px) {
  .vg-guide-spine {
    grid-template-columns: minmax(136px, 170px) minmax(0, 1fr);
  }

  .vg-guide-trust {
    position: static;
    grid-column: 2;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 960px) {
  .vg-guide-experience .vg-guide-hero-cover {
    min-height: calc(100svh - 72px);
  }

  .vg-guide-experience .vg-guide-hero-inner,
  .vg-guide-meta,
  .vg-guide-spine,
  .vg-guide-related {
    width: calc(100% - 40px);
  }

  .vg-guide-jump {
    display: block;
    width: 100%;
    overflow-x: auto;
    border-bottom: 1px solid rgba(16, 20, 23, .12);
    background: rgba(248, 249, 250, .96);
    scrollbar-width: none;
  }

  .vg-guide-jump ol {
    display: flex;
    gap: 8px;
    width: max-content;
    padding: 12px 20px;
  }

  .vg-guide-jump a {
    display: block;
    padding: 9px 13px;
    border: 1px solid rgba(1, 45, 29, .2);
    border-radius: 999px;
    color: var(--vg-green-dark);
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
  }

  .vg-guide-spine {
    display: block;
    padding-block: 52px 80px;
  }

  .vg-guide-spine__toc {
    display: none;
  }

  .vg-guide-trust {
    position: static;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin-top: 48px;
  }

  .vg-guide-article .alignwide {
    width: 100vw;
    margin-inline: calc(50% - 50vw);
    padding-inline: 20px;
  }
}

@media (max-width: 620px) {
  .vg-guide-experience .vg-guide-title {
    font-size: clamp(42px, 13vw, 64px);
  }

  .vg-guide-meta {
    gap: 8px 14px;
    font-size: 11px;
  }

  .vg-guide-meta span + span::before {
    margin-right: 14px;
  }

  .vg-guide-trust {
    grid-template-columns: 1fr;
  }

  .vg-guide-article .vg-guide-photo-grid {
    grid-template-columns: 1fr;
  }

  .vg-guide-article .vg-decision-table {
    width: calc(100vw - 20px);
    margin-left: -10px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .vg-guide-experience,
  .vg-guide-experience * {
    scroll-behavior: auto !important;
    transition-duration: .01ms !important;
    animation-duration: .01ms !important;
    animation-iteration-count: 1 !important;
  }
}
```

- [ ] **Step 4: Create the progressive enhancement script**

Create `assets/js/guide-experience.js`:

```javascript
(function () {
  'use strict';

  var guide = document.querySelector('[data-vg-guide]');
  if (!guide) {
    return;
  }

  var links = Array.prototype.slice.call(
    guide.querySelectorAll('.vg-guide-toc a[href^="#"], .vg-guide-jump a[href^="#"]')
  );
  var sections = links.map(function (link) {
    var id = link.getAttribute('href').slice(1);
    return document.getElementById(id);
  }).filter(Boolean);

  function setActive(id) {
    links.forEach(function (link) {
      link.classList.toggle('is-active', link.getAttribute('href') === '#' + id);
    });
  }

  if ('IntersectionObserver' in window && sections.length > 0) {
    var observer = new IntersectionObserver(function (entries) {
      var visible = entries.filter(function (entry) { return entry.isIntersecting; });
      if (visible.length > 0) {
        visible.sort(function (a, b) { return a.boundingClientRect.top - b.boundingClientRect.top; });
        setActive(visible[0].target.id);
      }
    }, { rootMargin: '-20% 0px -68% 0px', threshold: [0, 1] });

    sections.forEach(function (section) { observer.observe(section); });
  }

  var ticking = false;
  function updateProgress() {
    var rect = guide.getBoundingClientRect();
    var total = Math.max(1, rect.height - window.innerHeight);
    var progress = Math.max(0, Math.min(100, (-rect.top / total) * 100));
    guide.style.setProperty('--vg-guide-progress', progress.toFixed(2));
    ticking = false;
  }

  window.addEventListener('scroll', function () {
    if (!ticking) {
      window.requestAnimationFrame(updateProgress);
      ticking = true;
    }
  }, { passive: true });

  updateProgress();
}());
```

- [ ] **Step 5: Enqueue guide assets only on enabled pages**

Inside the existing `wp_enqueue_scripts` callback in `functions.php`, after the homepage script enqueue, add:

```php
if (vg_is_guide_experience_page()) {
    wp_enqueue_style(
        'vietnamguide-guide-experience',
        get_theme_file_uri('/assets/css/guide-experience.css'),
        ['vietnamguide-guide-patterns'],
        $version
    );
    wp_enqueue_script(
        'vietnamguide-guide-experience',
        get_theme_file_uri('/assets/js/guide-experience.js'),
        [],
        $version,
        true
    );
}
```

- [ ] **Step 6: Run asset and syntax verification**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
node --check .\wordpress\wp-content\themes\vietnamguide-premium\assets\js\guide-experience.js
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-homepage-theme.ps1
git diff --check
```

Expected: verifier success, no JavaScript syntax output, homepage checks pass, and no whitespace errors.

- [ ] **Step 7: Commit guide assets**

```powershell
git add ops/verify-guide-experience.ps1 wordpress/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css wordpress/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js wordpress/wp-content/themes/vietnamguide-premium/functions.php
git commit -m "feat: style and enhance editorial guide pages"
```

---

### Task 6: Add Negative Coverage and Live/Public Verification

**Files:**
- Create: `ops/verify-guide-experience-mutations.ps1`
- Create: `ops/verify-guide-experience-live.php`
- Create: `ops/verify-guide-experience-public.ps1`
- Modify: `ops/verify-guide-experience.ps1`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/footer.php:33`

- [ ] **Step 1: Add the footer contract and verify RED**

Append before the failure block in `verify-guide-experience.ps1`:

```powershell
$Footer = "$ThemeRoot/footer.php"
Require-Contains $Footer "vg_home_url('source-update-policy')"
Require-NotContains $Footer "vg_home_url('source-policy')"
```

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
```

Expected: failure for the missing `source-update-policy` URL and forbidden old URL.

- [ ] **Step 2: Correct the footer URL**

Change the Source policy link to:

```php
<li><a href="<?php echo esc_url(vg_home_url('source-update-policy')); ?>"><?php esc_html_e('Source policy', 'vietnamguide-premium'); ?></a></li>
```

- [ ] **Step 3: Create the mutation runner**

Create `ops/verify-guide-experience-mutations.ps1`:

```powershell
$ErrorActionPreference = 'Stop'
$RepoRoot = Split-Path -Parent $PSScriptRoot
$Verifier = Join-Path $PSScriptRoot 'verify-guide-experience.ps1'
$TempRoot = Join-Path ([System.IO.Path]::GetTempPath()) ('vg-guide-mutations-' + [guid]::NewGuid().ToString('N'))

function Copy-ContractTree {
    New-Item -ItemType Directory -Force -Path (Join-Path $TempRoot 'wordpress/wp-content/themes/vietnamguide-premium/inc') | Out-Null
    New-Item -ItemType Directory -Force -Path (Join-Path $TempRoot 'wordpress/wp-content/themes/vietnamguide-premium/template-parts') | Out-Null
    New-Item -ItemType Directory -Force -Path (Join-Path $TempRoot 'wordpress/wp-content/themes/vietnamguide-premium/assets/css') | Out-Null
    New-Item -ItemType Directory -Force -Path (Join-Path $TempRoot 'wordpress/wp-content/themes/vietnamguide-premium/assets/js') | Out-Null

    $files = @(
        'functions.php', 'footer.php', 'page.php',
        'inc/guide-routing.php', 'inc/guide-content.php', 'inc/guide-context.php',
        'template-parts/content-page.php', 'template-parts/guide-page.php',
        'assets/css/guide-experience.css', 'assets/js/guide-experience.js'
    )
    foreach ($file in $files) {
        $source = Join-Path $RepoRoot ('wordpress/wp-content/themes/vietnamguide-premium/' + $file)
        $target = Join-Path $TempRoot ('wordpress/wp-content/themes/vietnamguide-premium/' + $file)
        Copy-Item -LiteralPath $source -Destination $target -Force
    }
}

function Invoke-RejectedMutation {
    param([string]$Name, [string]$File, [string]$Old, [string]$New)
    Copy-ContractTree
    $target = Join-Path $TempRoot ('wordpress/wp-content/themes/vietnamguide-premium/' + $File)
    $content = Get-Content -LiteralPath $target -Raw
    if (-not $content.Contains($Old)) { throw "Mutation source not found: $Name" }
    Set-Content -LiteralPath $target -Value ($content.Replace($Old, $New)) -NoNewline
    & powershell -NoProfile -ExecutionPolicy Bypass -File $Verifier -RepoRootOverride $TempRoot *> $null
    if ($LASTEXITCODE -eq 0) { throw "Mutation was not rejected: $Name" }
    Write-Output "Mutation rejected: $Name"
}

try {
    Invoke-RejectedMutation 'unconditional pilot enablement' 'inc/guide-routing.php' 'if (! in_array($path, vg_guide_pilot_paths(), true)) {' 'if (false) {'
    Invoke-RejectedMutation 'missing hero H1 guard' 'inc/guide-content.php' "if (preg_match_all('/<h1\\b/i', `$heroSource) !== 1) {" 'if (false) {'
    Invoke-RejectedMutation 'duplicate guide H1' 'template-parts/guide-page.php' '<article' '<h1>Duplicate</h1><article'
    Invoke-RejectedMutation 'global guide assets' 'functions.php' 'if (vg_is_guide_experience_page()) {' 'if (true) {'
    Invoke-RejectedMutation 'duplicate related routes' 'inc/guide-context.php' 'if ($hasExisting) {' 'if (false) {'
    Invoke-RejectedMutation 'TOC includes H1' 'inc/guide-content.php' "`$pattern = '/<h2\\b([^>]*)>(.*?)<\\/h2>/is';" "`$pattern = '/<h[12]\\b([^>]*)>(.*?)<\\/h[12]>/is';"
} finally {
    if (Test-Path -LiteralPath $TempRoot) {
        Remove-Item -LiteralPath $TempRoot -Recurse -Force
    }
}

Write-Output 'VietnamGuide guide experience mutation checks passed.'
```

- [ ] **Step 4: Create the live WordPress verifier**

Create `ops/verify-guide-experience-live.php`:

```php
<?php
if (! defined('ABSPATH')) {
    fwrite(STDERR, "FAIL: Run with WordPress loaded.\n");
    exit(1);
}

$failures = [];
$requiredFunctions = [
    'vg_guide_pilot_paths', 'vg_classify_guide_path', 'vg_get_guide_path',
    'vg_get_guide_type', 'vg_is_guide_experience_page', 'vg_split_guide_blocks',
    'vg_prepare_guide_headings', 'vg_render_guide_toc', 'vg_prepare_guide_content',
    'vg_estimate_guide_reading_time', 'vg_count_guide_sources',
    'vg_get_related_routes', 'vg_build_guide_context',
];

foreach ($requiredFunctions as $functionName) {
    if (! function_exists($functionName)) {
        $failures[] = "Missing function: {$functionName}";
    }
}

$pilots = [
    'destinations/ho-chi-minh-city-travel-guide' => 'destination',
    'itineraries/10-days-in-vietnam' => 'itinerary',
    'compare/ha-long-bay-vs-lan-ha-bay' => 'comparison',
    'plan/vietnam-evisa' => 'practical',
];

foreach ($pilots as $path => $expectedType) {
    $page = get_page_by_path($path, OBJECT, 'page');
    if (! $page instanceof WP_Post) {
        $failures[] = "Missing pilot page: {$path}";
        continue;
    }

    $context = vg_build_guide_context($page);
    if (! is_array($context)) {
        $failures[] = "Context failed: {$path}";
        continue;
    }

    if (($context['type'] ?? null) !== $expectedType) {
        $failures[] = "Wrong type for {$path}";
    }
    if (preg_match_all('/<h1\b/i', (string) $context['hero_html']) !== 1) {
        $failures[] = "Hero must contain one H1: {$path}";
    }
    if (preg_match('/<h1\b/i', (string) $context['body_html'])) {
        $failures[] = "Body contains H1: {$path}";
    }
    if (count((array) $context['headings']) < 2) {
        $failures[] = "Insufficient TOC headings: {$path}";
    }
    if ((int) $context['reading_time'] < 1) {
        $failures[] = "Invalid reading time: {$path}";
    }
}

if ($failures !== []) {
    $message = "VietnamGuide guide runtime verification failed:\n- " . implode("\n- ", $failures);
    if (class_exists('WP_CLI')) { WP_CLI::error($message); }
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

if (class_exists('WP_CLI')) {
    WP_CLI::success('VietnamGuide guide runtime verification passed.');
} else {
    fwrite(STDOUT, "SUCCESS: VietnamGuide guide runtime verification passed.\n");
}
```

- [ ] **Step 5: Create the public rendered-page verifier**

Create `ops/verify-guide-experience-public.ps1`:

```powershell
param([string]$BaseUrl = 'https://vietnamguide.net')
$ErrorActionPreference = 'Stop'
$Failures = [System.Collections.Generic.List[string]]::new()
$Paths = @(
    '/destinations/ho-chi-minh-city-travel-guide/',
    '/itineraries/10-days-in-vietnam/',
    '/compare/ha-long-bay-vs-lan-ha-bay/',
    '/plan/vietnam-evisa/'
)

foreach ($Path in $Paths) {
    $Url = $BaseUrl.TrimEnd('/') + $Path
    $Response = Invoke-WebRequest -UseBasicParsing -Uri $Url -TimeoutSec 30
    $Html = [string]$Response.Content
    if ([int]$Response.StatusCode -ne 200) { $Failures.Add("HTTP $($Response.StatusCode): $Url") }
    $H1Count = [regex]::Matches($Html, '<h1\b', 'IgnoreCase').Count
    if ($H1Count -ne 1) { $Failures.Add("Expected one H1, found ${H1Count}: $Url") }
    if ($Html -notmatch 'vg-guide-experience') { $Failures.Add("Missing guide shell: $Url") }
    if ($Html -notmatch 'guide-experience\.css') { $Failures.Add("Missing guide CSS: $Url") }
    if ($Html -notmatch 'guide-experience\.js') { $Failures.Add("Missing guide JS: $Url") }
    if ($Html -notmatch 'class="vg-guide-(?:toc|jump)"') { $Failures.Add("Missing TOC: $Url") }
    if ($Html -match '(Fatal error|Uncaught Error|There has been a critical error)') { $Failures.Add("Fatal text: $Url") }
}

$Home = Invoke-WebRequest -UseBasicParsing -Uri ($BaseUrl.TrimEnd('/') + '/') -TimeoutSec 30
if ([string]$Home.Content -notmatch '/source-update-policy/') {
    $Failures.Add('Homepage/footer does not link to /source-update-policy/.')
}

if ($Failures.Count -gt 0) {
    $Failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output 'VietnamGuide public guide experience checks passed.'
```

- [ ] **Step 6: Verify the public contract is RED before deployment**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-public.ps1
```

Expected: exit `1`; current production reports two H1 elements and lacks the new guide shell/assets.

- [ ] **Step 7: Run local and mutation verification**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-mutations.ps1
node --check .\wordpress\wp-content\themes\vietnamguide-premium\assets\js\guide-experience.js
git diff --check
```

Expected: contract passes, six named mutations are rejected, JavaScript syntax passes, and no whitespace errors appear.

- [ ] **Step 8: Commit verification infrastructure**

```powershell
git add ops/verify-guide-experience.ps1 ops/verify-guide-experience-mutations.ps1 ops/verify-guide-experience-live.php ops/verify-guide-experience-public.ps1 wordpress/wp-content/themes/vietnamguide-premium/footer.php
git commit -m "test: verify guide experience rollout"
```

---

### Task 7: Guarded Production Deployment and Pilot Acceptance

**Files:**
- Modify: `ops/verification-log.md`

- [ ] **Step 1: Run the full local gate from a clean worktree**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-mutations.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-block-patterns.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-mu-plugin.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-homepage-theme.ps1
python .\ops\test-homepage-images.py
node --check .\wordpress\wp-content\themes\vietnamguide-premium\assets\js\homepage.js
node --check .\wordpress\wp-content\themes\vietnamguide-premium\assets\js\guide-experience.js
Get-Content -Raw .\wordpress\wp-content\themes\vietnamguide-premium\theme.json | ConvertFrom-Json | Out-Null
git diff --check
git status --short
```

Expected: every verifier passes; mutation suite rejects all six mutations; image tests report `Ran 5 tests` and `OK`; JavaScript and JSON checks emit no errors; worktree is clean.

- [ ] **Step 2: Recheck SSH without relying on it**

Run:

```powershell
Test-NetConnection 66.42.48.146 -Port 2209 -InformationLevel Detailed
```

Expected: if `TcpTestSucceeded` is `False`, record `CLOSED_OR_FILTERED` and continue through WordPress admin. Do not repeatedly retry SSH.

- [ ] **Step 3: Create production backups before replacement**

Through the guarded WordPress deployment helper, copy every production target that will be replaced into a timestamped backup directory outside the active theme. Record byte size and SHA256 for:

```text
functions.php
footer.php
page.php (or a missing-state record)
inc/guide-routing.php (or a missing-state record)
inc/guide-content.php (or a missing-state record)
inc/guide-context.php (or a missing-state record)
template-parts/content-page.php (or a missing-state record)
template-parts/guide-page.php (or a missing-state record)
assets/css/guide-experience.css (or a missing-state record)
assets/js/guide-experience.js (or a missing-state record)
```

Expected: each backup or missing-state record has an explicit path, size, and SHA256.

- [ ] **Step 4: Deploy only the reviewed theme files**

Use a temporary, administrator-only WordPress deployment plugin that:

1. Verifies the active theme is `vietnamguide-premium`.
2. Verifies every uploaded source SHA256 against the reviewed local file.
3. Runs server-side `php -l` on all PHP uploads before installation.
4. Writes files with the existing theme owner/group and mode `0644`.
5. Runs `php -l` again after installation.
6. Purges the theme cache, LiteSpeed cache, and WordPress object cache.
7. Records a short-lived success status option.
8. Removes itself and deletes its status option after verification.

Expected: installed hashes equal local hashes and no content, post meta, menus, or site settings change.

- [ ] **Step 5: Run live WordPress runtime verification**

Execute `ops/verify-guide-experience-live.php` in the loaded WordPress runtime using the temporary verifier route or `wp eval-file` if SSH unexpectedly works.

Expected:

```text
SUCCESS: VietnamGuide guide runtime verification passed.
```

- [ ] **Step 6: Run the public verifier and verify GREEN**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-public.ps1
```

Expected:

```text
VietnamGuide public guide experience checks passed.
```

- [ ] **Step 7: Perform browser QA at desktop and mobile widths**

For each pilot URL, verify at 1440px and 390px:

```text
- One visible H1 in the cinematic hero.
- Sticky TOC remains contained on desktop.
- Mobile jump navigation scrolls horizontally without page overflow.
- Trust rail becomes inline on mobile.
- Decision tables scroll inside their container.
- Full-bleed media does not widen the document.
- Keyboard focus is visible.
- Reduced-motion mode removes transform animation.
- No guide-related console errors.
- Existing content sections, source links, and related routes remain present.
```

Expected: all checks pass on all four pilot pages.

- [ ] **Step 8: Verify non-pilot pages remain unchanged**

Check at least:

```text
https://vietnamguide.net/destinations/hanoi-travel-guide/
https://vietnamguide.net/itineraries/14-days-in-vietnam/
https://vietnamguide.net/compare/da-nang-vs-hoi-an/
https://vietnamguide.net/plan/sim-esim-vietnam/
```

Expected: HTTP 200, no `vg-guide-experience` class, and no `guide-experience.css` or `guide-experience.js` asset.

- [ ] **Step 9: Clean deployment helpers**

Delete the temporary deployer/verifier plugin and short-lived options, then verify the WordPress plugin inventory contains no VietnamGuide deployment helper.

Expected: no helper plugin, helper file, or deployment status option remains.

- [ ] **Step 10: Record production evidence**

Append this completed section to `ops/verification-log.md` with exact values:

```markdown
## Guide Experience Pilot

- Deployment route:
- Backup directory and hashes:
- Local guide contract:
- Mutation checks:
- Live WordPress runtime:
- Public four-page gate:
- Desktop 1440px QA:
- Mobile 390px QA:
- Reduced-motion and keyboard QA:
- Non-pilot isolation:
- Footer Source policy URL:
- Cache purge:
- Temporary helper cleanup:
```

- [ ] **Step 11: Commit verification evidence**

```powershell
git add ops/verification-log.md
git commit -m "docs: verify guide experience pilot"
```

---

## Final Acceptance Gate

Before merging the feature branch:

1. Run every command from Task 7 Step 1 again from the final branch HEAD.
2. Confirm the production public verifier still passes after cache expiry.
3. Confirm `git status --short` is empty in the feature worktree.
4. Request a final code review covering the complete branch diff against `master`.
5. Fix all spec-compliance and code-quality findings.
6. Re-run the full local and public gate after the last fix.

The branch is ready to merge only when the four pilots are green, non-pilot pages remain isolated, deployment helpers are removed, and all verification evidence is committed.

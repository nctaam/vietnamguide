# VietnamGuide Live Packaging Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Package already-published VietnamGuide.net pages so public HTML has one H1, homepage links only hit sitemap URLs, schema author is never `Administrator`, Gutenberg source-trail snapshots look like evidence modules, and Guide Experience covers published comparison then destination then plan pages.

**Architecture:** Theme and MU-plugin only. No `post_content` rewrites and no unpublished `post` apply-scripts. `content-page.php` prints a title H1 only when filtered content has no H1. `homepage-data.php` plus matching block patterns remap Stitch cards to live sitemap URLs. MU-plugin replaces schema author `Administrator` with `VietnamGuide editorial team`. CSS in `guide-patterns.css` styles `.vg-source-snapshot` / `.vg-source-diversity`. Heading planner treats chrome regions as TOC opt-out. `vg_guide_pilot_paths()` expands hub-by-hub; every inventory copy in local, live, public, and mutation verifiers updates in the same commit as the allowlist.

**Tech Stack:** WordPress 6.6+ / PHP 8.1+, `WP_HTML_Tag_Processor`, Rank Math JSON-LD filters, existing PowerShell/PHP verifier family, LiteSpeed cache purge on deploy.

**Spec:** `docs/superpowers/specs/2026-08-30-vietnamguide-live-packaging-design.md`

## File map

- Modify: `wordpress/wp-content/themes/vietnamguide-premium/template-parts/content-page.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/homepage-data.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php`
- Modify: `ops/verify-homepage-theme.ps1`
- Modify: `wordpress/wp-content/mu-plugins/vietnamguide-core.php`
- Modify: `ops/verify-core-mu-plugin.ps1`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/assets/css/guide-patterns.css`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php`
- Modify: `ops/verify-guide-experience.ps1`
- Modify: `ops/verify-guide-experience-live.php`
- Modify: `ops/verify-guide-experience-public.ps1`
- Modify: `ops/verify-guide-experience-mutations.ps1`
- Modify: `ops/verification-log.md` after production

Do not modify apply-scripts, comparison-rollout files, or unpublished post drafts.

## Locked inventories

### Retired homepage paths

These strings must not remain in `homepage-data.php` or the four homepage patterns:

- `plan/vietnam-for-first-time-visitors`
- `itineraries/vietnam-food-itinerary`
- `itineraries/vietnam-beach-itinerary`
- `itineraries/vietnam-family-itinerary`
- `itineraries/vietnam-luxury-itinerary`
- `itineraries/northern-vietnam-itinerary`
- `destinations/hanoi` (short slug only; keep `destinations/hanoi-travel-guide`)
- `destinations/hoi-an`
- `destinations/ninh-binh`
- `destinations/lan-ha-bay`
- `destinations/ha-giang`
- `destinations/phu-quoc` (short slug only; keep `destinations/phu-quoc-travel-guide`)
- `compare/sapa-vs-ha-giang`
- `compare/hanoi-vs-ho-chi-minh-city`
- `plan/getting-around-vietnam`
- `plan/is-vietnam-safe`

### Stage 0 allowlist

```
destinations/ho-chi-minh-city-travel-guide
itineraries/10-days-in-vietnam
itineraries/7-days-in-vietnam
itineraries/14-days-in-vietnam
itineraries/21-days-in-vietnam
itineraries/hanoi-in-2-days
compare/ha-long-bay-vs-lan-ha-bay
plan/vietnam-evisa
```

### Stage A additions (append after stage 0, this order)

```
compare/cu-chi-tunnels-vs-mekong-delta-day-trip
compare/da-nang-vs-hoi-an
compare/hoi-an-vs-hue
compare/mui-ne-vs-nha-trang
compare/ninh-binh-day-trip-vs-overnight
compare/north-central-south-vietnam
compare/old-quarter-vs-french-quarter-vs-west-lake
compare/phu-quoc-vs-nha-trang
compare/trang-an-vs-tam-coc
```

### Stage B additions (append after stage A; do not duplicate HCMC)

```
destinations/unesco-heritage-sites-vietnam
destinations/best-beaches-in-vietnam
destinations/best-places-to-visit-vietnam
destinations/best-things-to-do-in-hanoi
destinations/best-things-to-do-in-hoi-an
destinations/best-things-to-do-in-hue
destinations/ninh-binh-travel-guide
destinations/ha-long-bay-travel-guide
destinations/cat-ba-travel-guide
destinations/bai-tu-long-bay-guide
destinations/da-nang-travel-guide
destinations/best-islands-in-vietnam
destinations/phu-quoc-travel-guide
destinations/con-dao-travel-guide
destinations/nha-trang-travel-guide
destinations/quy-nhon-travel-guide
destinations/cham-islands-travel-guide
destinations/ly-son-travel-guide
destinations/mekong-delta-travel-guide
destinations/best-day-trips-from-ho-chi-minh-city
destinations/where-to-stay-in-ho-chi-minh-city
destinations/hanoi-travel-guide
destinations/where-to-stay-in-hanoi
destinations/best-day-trips-from-hanoi
destinations/where-to-stay-in-ninh-binh
destinations/tam-coc-travel-guide
```

### Stage C additions (append after stage B; do not duplicate e-visa)

```
plan/best-time-to-visit-vietnam
plan/vietnam-travel-guide
plan/transport-within-vietnam
plan/money-cash-cards-atms
plan/sim-esim-vietnam
plan/safety-scams-vietnam
plan/health-travel-insurance-vietnam
plan/hanoi-airport-to-old-quarter
plan/hanoi-to-ninh-binh-transport
plan/ninh-binh-to-ha-long-bay-transfer
```

### Public non-pilot quartet (exactly four strings; mutation suite depends on that)

- Stage 0: `destinations/hanoi-travel-guide`, `compare/da-nang-vs-hoi-an`, `plan/sim-esim-vietnam`, `plan/transport-within-vietnam`
- After A: `destinations/hanoi-travel-guide`, `plan/sim-esim-vietnam`, `plan/transport-within-vietnam`, `compare`
- After B: `plan/sim-esim-vietnam`, `plan/transport-within-vietnam`, `compare`, `destinations`
- After C: `compare`, `destinations`, `plan`, `itineraries`

Permanent off-list for Guide Experience: `/`, the five hubs, about, contact, newsletter, privacy, editorial-policy, source-update-policy, affiliate-disclosure, affiliate-review-policy.

## Shared local regression

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-homepage-theme.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-mu-plugin.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-block-patterns.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-mutations.ps1
git diff --check
```

Expected: exit `0` on every command. Do not run public/live verifiers until that stage is deployed.


### Task 1: Single H1 on the default template

**Files:**
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/template-parts/content-page.php`
- Test: `ops/verify-guide-experience.ps1` (already requires `content-page.php` and `page.php` fallback)

The entire public double-H1 bug is this template always printing a title H1 in front of Gutenberg heroes that already contain an H1. REST `content.rendered` already has one H1 on 55 pages and zero on 13 hub/legal pages.

- [ ] **Step 1: Replace `content-page.php` with an H1-aware default template**

Replace the file with:

```php
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
```

Do not call `the_content()` after this. The filtered HTML must be printed once.

- [ ] **Step 2: Confirm `page.php` still fail-closes to this template**

`page.php` must still contain:

```php
get_template_part('template-parts/content', 'page');
```

and must still require `vg_is_valid_guide_context()` before `guide-page.php`. Do not change that gate.

- [ ] **Step 3: Run local guide + homepage verifiers**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-homepage-theme.ps1
```

Expected: exit `0`. If `verify-guide-experience.ps1` later grows a `content-page.php` substring check for a bare `<h1><?php echo esc_html(get_the_title()); ?></h1>` outside the `if`, keep that H1 inside the `if (! $hasContentH1)` branch.

- [ ] **Step 4: Commit**

```powershell
git add wordpress/wp-content/themes/vietnamguide-premium/template-parts/content-page.php
git commit -m "fix: print default title H1 only when content has none"
```

### Task 2: Remap Stitch homepage cards to published URLs

**Files:**
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/homepage-data.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php`
- Modify: `ops/verify-homepage-theme.ps1`
- Do not modify `front-page.php` section structure.

- [ ] **Step 1: Replace `vg_homepage_data()` body**

Keep `vg_home_url()` and the `navigation` array unchanged. Replace the remaining keys with:

```php
        'trip_lengths' => [
            ['label' => '7 days', 'url' => vg_home_url('itineraries/7-days-in-vietnam')],
            ['label' => '10 days', 'url' => vg_home_url('itineraries/10-days-in-vietnam')],
            ['label' => '14 days', 'url' => vg_home_url('itineraries/14-days-in-vietnam')],
            ['label' => '21 days', 'url' => vg_home_url('itineraries/21-days-in-vietnam')],
        ],
        'travel_styles' => [
            ['label' => 'First trip', 'url' => vg_home_url('plan/vietnam-travel-guide')],
            ['label' => 'Food', 'url' => vg_home_url('destinations/best-things-to-do-in-hoi-an')],
            ['label' => 'Beach', 'url' => vg_home_url('destinations/best-beaches-in-vietnam')],
            ['label' => 'Family', 'url' => vg_home_url('itineraries/14-days-in-vietnam')],
            ['label' => 'Premium', 'url' => vg_home_url('destinations/con-dao-travel-guide')],
        ],
        'itineraries' => [
            ['eyebrow' => 'First journey', 'title' => '10 days: north, center, south', 'fit' => 'Best for a first trip that needs one coherent national overview.', 'url' => vg_home_url('itineraries/10-days-in-vietnam')],
            ['eyebrow' => 'More breathing room', 'title' => '14 days: Vietnam at a calmer pace', 'fit' => 'Best for travelers who want stronger place depth and fewer rushed transfers.', 'url' => vg_home_url('itineraries/14-days-in-vietnam')],
            ['eyebrow' => 'Landscape-led', 'title' => 'North, center, or south', 'fit' => 'Best for choosing one region job before stitching the whole country.', 'url' => vg_home_url('compare/north-central-south-vietnam')],
            ['eyebrow' => 'Island finish', 'title' => 'Con Dao, when the route can slow down', 'fit' => 'Best for couples and families who can protect a quieter island chapter.', 'url' => vg_home_url('destinations/con-dao-travel-guide')],
        ],
        'destinations' => [
            ['title' => 'Hanoi', 'best_for' => 'Food, history, and a first arrival.', 'skip_if' => 'You want a quiet coastal base.', 'url' => vg_home_url('destinations/hanoi-travel-guide')],
            ['title' => 'Hoi An', 'best_for' => 'Walkable evenings, food, and a slower center.', 'skip_if' => 'You need a major-city itinerary.', 'url' => vg_home_url('destinations/best-things-to-do-in-hoi-an')],
            ['title' => 'Ninh Binh', 'best_for' => 'Karst landscapes without an overnight cruise.', 'skip_if' => 'You dislike early starts and rural transfers.', 'url' => vg_home_url('destinations/ninh-binh-travel-guide')],
            ['title' => 'Cat Ba', 'best_for' => 'A bay base with island time and Lan Ha access.', 'skip_if' => 'You only want the iconic Ha Long cruise checklist.', 'url' => vg_home_url('destinations/cat-ba-travel-guide')],
            ['title' => 'Ha Long Bay', 'best_for' => 'The classic northern seascape decision.', 'skip_if' => 'You already know you want a quieter bay or a skip.', 'url' => vg_home_url('destinations/ha-long-bay-travel-guide')],
            ['title' => 'Phu Quoc', 'best_for' => 'An easy beach finish with resort choice.', 'skip_if' => 'You want a culture-first final stop.', 'url' => vg_home_url('destinations/phu-quoc-travel-guide')],
        ],
        'comparisons' => [
            ['title' => 'Ha Long Bay vs Lan Ha Bay', 'verdict' => 'Choose icon value or choose a calmer route.', 'url' => vg_home_url('compare/ha-long-bay-vs-lan-ha-bay')],
            ['title' => 'Da Nang vs Hoi An', 'verdict' => 'Choose airport-and-beach logistics or choose a slower heritage base.', 'url' => vg_home_url('compare/da-nang-vs-hoi-an')],
            ['title' => 'North vs Central vs South', 'verdict' => 'Choose one region job before stitching the whole country.', 'url' => vg_home_url('compare/north-central-south-vietnam')],
        ],
        'essentials' => [
            ['title' => 'Vietnam e-visa', 'meta' => 'Entry rules and common application mistakes.', 'url' => vg_home_url('plan/vietnam-evisa')],
            ['title' => 'Best time to visit', 'meta' => 'Plan around regions, not one national forecast.', 'url' => vg_home_url('plan/best-time-to-visit-vietnam')],
            ['title' => 'Travel cost', 'meta' => 'Realistic budget ranges for different travel styles.', 'url' => vg_home_url('costs/vietnam-travel-cost')],
            ['title' => 'SIM and eSIM', 'meta' => 'Stay connected from arrival without overspending.', 'url' => vg_home_url('plan/sim-esim-vietnam')],
            ['title' => 'Transport', 'meta' => 'Flights, trains, buses, transfers, and local transport.', 'url' => vg_home_url('plan/transport-within-vietnam')],
            ['title' => 'Safety and scams', 'meta' => 'Practical risk management without alarmism.', 'url' => vg_home_url('plan/safety-scams-vietnam')],
        ],
```

Family reuses the 14-day URL. That duplicate is required by the spec.

- [ ] **Step 2: Mirror the same URLs and labels in the four homepage patterns**

`planning-paths.php` travel-style list must become:

```html
<li><a href="/plan/vietnam-travel-guide/">First trip<span aria-hidden="true">&rarr;</span></a></li>
<li><a href="/destinations/best-things-to-do-in-hoi-an/">Food<span aria-hidden="true">&rarr;</span></a></li>
<li><a href="/destinations/best-beaches-in-vietnam/">Beach<span aria-hidden="true">&rarr;</span></a></li>
<li><a href="/itineraries/14-days-in-vietnam/">Family<span aria-hidden="true">&rarr;</span></a></li>
<li><a href="/destinations/con-dao-travel-guide/">Premium<span aria-hidden="true">&rarr;</span></a></li>
```

`editorial-itineraries.php` items 03 and 04 must become north/center/south and Con Dao with the titles from step 1. Keep 01/02 as 10-day and 14-day. Keep four `<li>` and four `&rarr;` spans.

`decision-guides.php` three links must be Ha Long vs Lan Ha, Da Nang vs Hoi An, North vs Central vs South, with the locked verdicts.

`practical-essentials.php` last two links must be `/plan/transport-within-vietnam/` labeled Transport and `/plan/safety-scams-vietnam/` labeled Safety and scams. Keep six `<a href=`.

- [ ] **Step 3: Point homepage verifier at the new URLs**

In `ops/verify-homepage-theme.ps1`, replace the retired `Require-Contains` needles for those four pattern files. Required new needles include:

- `/plan/vietnam-travel-guide/`
- `/destinations/best-things-to-do-in-hoi-an/`
- `/destinations/best-beaches-in-vietnam/`
- `/destinations/con-dao-travel-guide/`
- `/compare/north-central-south-vietnam/`
- `/compare/da-nang-vs-hoi-an/`
- `/destinations/hanoi-travel-guide/`
- `/destinations/ninh-binh-travel-guide/`
- `/destinations/cat-ba-travel-guide/`
- `/destinations/ha-long-bay-travel-guide/`
- `/destinations/phu-quoc-travel-guide/`
- `/plan/transport-within-vietnam/`
- `/plan/safety-scams-vietnam/`

Add `Require-NotContains` for every retired path listed above against:

- `wordpress/wp-content/themes/vietnamguide-premium/inc/homepage-data.php`
- the four pattern files
- `wordpress/wp-content/themes/vietnamguide-premium/front-page.php`

Do not require `front-page.php` to hardcode destination URLs; it reads `vg_homepage_data()`. The NotContains check is the safety net.

- [ ] **Step 4: Run homepage + block-pattern verifiers**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-homepage-theme.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-block-patterns.ps1
```

Expected: exit `0`. If `verify-core-block-patterns.ps1` still requires an old URL, update that needle in this same commit.

- [ ] **Step 5: Commit**

```powershell
git add wordpress/wp-content/themes/vietnamguide-premium/inc/homepage-data.php wordpress/wp-content/themes/vietnamguide-premium/patterns/planning-paths.php wordpress/wp-content/themes/vietnamguide-premium/patterns/editorial-itineraries.php wordpress/wp-content/themes/vietnamguide-premium/patterns/decision-guides.php wordpress/wp-content/themes/vietnamguide-premium/patterns/practical-essentials.php ops/verify-homepage-theme.ps1 ops/verify-core-block-patterns.ps1
git commit -m "fix: point homepage cards at published sitemap URLs"
```


### Task 3: Replace schema author `Administrator`

**Files:**
- Modify: `wordpress/wp-content/mu-plugins/vietnamguide-core.php`
- Modify: `ops/verify-core-mu-plugin.ps1`
- Production dashboard write (not git): WordPress user 1 display name

Locked public string: `VietnamGuide editorial team`.

- [ ] **Step 1: Add schema author rewrite helpers before `vg_register_pattern_category`**

Append to `vietnamguide-core.php` (still inside the ABSPATH-guarded file):

```php
function vg_public_editorial_author_name(): string
{
    return 'VietnamGuide editorial team';
}

function vg_is_forbidden_public_author_name(string $name): bool
{
    $normalized = strtolower(trim($name));
    return $normalized === '' || $normalized === 'administrator' || $normalized === 'admin';
}

function vg_rewrite_schema_author_value(mixed $value): mixed
{
    if (is_string($value)) {
        return vg_is_forbidden_public_author_name($value)
            ? vg_public_editorial_author_name()
            : $value;
    }

    if (! is_array($value)) {
        return $value;
    }

    if (array_is_list($value)) {
        return array_map('vg_rewrite_schema_author_value', $value);
    }

    $type = $value['@type'] ?? null;
    $is_person = $type === 'Person' || (is_array($type) && in_array('Person', $type, true));
    if ($is_person && array_key_exists('name', $value) && is_string($value['name']) && vg_is_forbidden_public_author_name($value['name'])) {
        $value['name'] = vg_public_editorial_author_name();
    }

    if (array_key_exists('author', $value)) {
        $value['author'] = vg_rewrite_schema_author_value($value['author']);
    }
    if (array_key_exists('@graph', $value) && is_array($value['@graph'])) {
        $value['@graph'] = vg_rewrite_schema_author_value($value['@graph']);
    }

    return $value;
}

function vg_filter_rank_math_json_ld(array $data): array
{
    $rewritten = vg_rewrite_schema_author_value($data);
    return is_array($rewritten) ? $rewritten : $data;
}
add_filter('rank_math/json_ld', 'vg_filter_rank_math_json_ld', 99);
```

Do not add an author archive. Do not change shortcode copy.

- [ ] **Step 2: Extend `ops/verify-core-mu-plugin.ps1`**

In the existing contract file, add contains checks for:

- `function vg_public_editorial_author_name`
- `VietnamGuide editorial team`
- `function vg_is_forbidden_public_author_name`
- `rank_math/json_ld`
- `vg_filter_rank_math_json_ld`

And a not-contains check that the plugin does not print a public `Administrator` string as a display name assignment.

- [ ] **Step 3: Run MU-plugin verifier**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-core-mu-plugin.ps1
```

Expected: exit `0`.

- [ ] **Step 4: Commit**

```powershell
git add wordpress/wp-content/mu-plugins/vietnamguide-core.php ops/verify-core-mu-plugin.ps1
git commit -m "fix: replace Administrator in public schema author"
```

- [ ] **Step 5: Production identity write (same deploy as this MU-plugin)**

In wp-admin: Users → user ID 1 → set Display Name / Nickname to `VietnamGuide editorial team`. Rank Math → Titles & Meta / Local SEO: Organization name remains VietnamGuide.net; person name must not be Administrator. Purge LiteSpeed after deploy. Public JSON-LD sampled on `/itineraries/10-days-in-vietnam/` must not contain `"name":"Administrator"`.

### Task 4: Style Gutenberg source snapshots and exclude them from TOC

**Files:**
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/assets/css/guide-patterns.css`
- Modify: `wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php`
- Test: `ops/verify-guide-experience.ps1`, `ops/verify-guide-experience-mutations.ps1`

- [ ] **Step 1: Add snapshot CSS at the end of `guide-patterns.css`, before the reduced-motion block if one exists at file end, otherwise append**

```css
.vg-source-snapshot,
.vg-source-diversity {
  margin-block: clamp(36px, 6vw, 72px);
  padding: clamp(24px, 4vw, 40px);
  border: 1px solid var(--vg-line);
  border-left: 4px solid var(--vg-gold);
  background: var(--vg-limestone);
}

.vg-source-snapshot > :first-child,
.vg-source-diversity > :first-child {
  margin-top: 0;
}

.vg-source-snapshot > :last-child,
.vg-source-diversity > :last-child {
  margin-bottom: 0;
}

.vg-source-snapshot .wp-block-heading,
.vg-source-diversity .wp-block-heading {
  font-family: var(--vg-body);
  font-size: 12px;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--vg-jade);
}

.vg-source-snapshot a,
.vg-source-diversity a {
  text-decoration: underline;
  text-underline-offset: 2px;
}
```

Do not add animation. Do not force `target=_blank`.

- [ ] **Step 2: Track chrome regions while collecting H2s**

In `vg_collect_guide_heading_plan()`, initialize `$chromeStack = [];` next to `$currentHeading`.

Inside the existing token loop, after `$tokenName` and `$isTagCloser` are set, maintain a tag-name stack. Do not use a depth counter; closer tags often have no class.

```php
        if (is_string($tokenName) && isset($tokenName[0]) && $tokenName[0] !== '#') {
            if (! $isTagCloser) {
                if (
                    $processor->has_class('vg-source-snapshot')
                    || $processor->has_class('vg-source-diversity')
                    || $processor->has_class('vg-related-routes')
                ) {
                    $chromeStack[] = $tokenName;
                }
            } elseif ($chromeStack !== [] && end($chromeStack) === $tokenName) {
                array_pop($chromeStack);
            }
        }
```

Keep every other heading-plan rule identical. An H2 opener is chrome when `$chromeStack !== []`.

When creating `$currentHeading` for an H2 opener, set:

```php
                    'opt_out' => (is_string($tocAttribute) && strcasecmp(trim($tocAttribute), 'false') === 0)
                        || $chromeStack !== [],
```

Keep every other heading-plan rule identical. Do not edit post content. Do not remove Gutenberg related bands. Theme related-nav suppression in `vg_guide_body_has_related_routes()` stays.

- [ ] **Step 3: Add verifier contains checks for the new CSS selectors and chrome stack**

In `ops/verify-guide-experience.ps1`, `Require-Contains` on `guide-patterns.css` for `.vg-source-snapshot` and `vg-source-diversity`. `Require-Contains` on `guide-content.php` for `vg-source-snapshot` inside `vg_collect_guide_heading_plan`.

If the mutation suite snapshots exact heading-plan source, add a mutation that removes the chromeStack opt-out (`|| $chromeStack !== []`) and expect rejection.

- [ ] **Step 4: Run**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-mutations.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-homepage-theme.ps1
```

Expected: exit `0`. Mutation count may increase by one; update any hard-coded expected mutation total in `verify-guide-experience.ps1` in this commit.

- [ ] **Step 5: Commit**

```powershell
git add wordpress/wp-content/themes/vietnamguide-premium/assets/css/guide-patterns.css wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php ops/verify-guide-experience.ps1 ops/verify-guide-experience-mutations.ps1
git commit -m "feat: style Gutenberg source snapshots and keep them out of TOC"
```

### Task 5: Inventory helper for later stages

Every later stage copies the same four arrays. When changing allowlists, update all of these in the same commit, same order:

1. `vg_guide_pilot_paths()` in `inc/guide-routing.php`
2. `$ExpectedPublicPilotPaths` in `ops/verify-guide-experience.ps1`
3. `$ExpectedPilotPaths` in `ops/verify-guide-experience.ps1`
4. `$ExpectedLivePilotTypeMappings` in `ops/verify-guide-experience.ps1`
5. `$pilot_types` in `ops/verify-guide-experience-live.php` (keys must `=== array_keys` of routing, same order)
6. `$PilotPaths` in `ops/verify-guide-experience-public.ps1`
7. `$NonPilotPaths` in `ops/verify-guide-experience-public.ps1` and the `Require-ExactOrdinalSet` copy in `verify-guide-experience.ps1`
8. Mutation `Find` strings that still quote a path that moved from non-pilot to pilot
9. Live verifier message currently `'eight expected paths'` — change to `'the expected paths'` in Task 6 so later counts do not lie. Do that once in Task 6, not in Task 5.

Live `$pilot_types` values:

- path starting `destinations/` → `'destination'`
- `itineraries/` → `'itinerary'`
- `compare/` → `'comparison'`
- `plan/` → `'practical'`

Do not allowlist hubs.

### Task 6: Stage A — remaining comparisons

**Files:** routing + the four verifier files listed in Task 5.

- [ ] **Step 1: Append the nine comparison paths to `vg_guide_pilot_paths()` after `plan/vietnam-evisa`**

Keep the original eight first and in the current order.

- [ ] **Step 2: Sync verifier inventories to the 17-path list**

Public non-pilot quartet becomes:

```
destinations/hanoi-travel-guide
plan/sim-esim-vietnam
plan/transport-within-vietnam
compare
```

In `verify-guide-experience-live.php` replace `'eight expected paths'` with `'the expected paths'` in both the fail message and the `Require-Contains` needle in `verify-guide-experience.ps1`. Add `Require-NotContains $LiveVerifier 'eight expected paths'`.

Update mutation Finds that quote `'compare/da-nang-vs-hoi-an'` as a non-pilot path. Point those Finds at `'plan/sim-esim-vietnam'` or `'compare'` as appropriate so mutations still have an exact string to edit.

- [ ] **Step 3: Local regression**

Run the shared local regression block. Expected exit `0`.

- [ ] **Step 4: Commit**

```powershell
git add wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php ops/verify-guide-experience.ps1 ops/verify-guide-experience-live.php ops/verify-guide-experience-public.ps1 ops/verify-guide-experience-mutations.ps1
git commit -m "feat: enable guide experience on remaining comparison pages"
```

- [ ] **Step 5: Deploy theme routing + verifiers, purge cache, run public verifier**

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-public.ps1
```

Expected: each of the 17 paths HTTP 200, one H1, guide shell, guide CSS/JS. Hub `/compare/` has no guide shell. If a comparison fail-closes (no valid hero split), it must still have exactly one H1 from Task 1. Record any fail-closed path in `ops/verification-log.md`. Do not weaken `vg_split_guide_blocks()`.

### Task 7: Stage B — destinations

- [ ] **Step 1: Append the 26 destination paths from the locked inventory**

Keep HCMC in stage-0 position. Do not add `destinations`.

- [ ] **Step 2: Sync inventories**

Public non-pilot quartet:

```
plan/sim-esim-vietnam
plan/transport-within-vietnam
compare
destinations
```

Move mutation Finds off `destinations/hanoi-travel-guide` because that path is now a pilot. Use `plan/sim-esim-vietnam` as the remaining deep non-pilot Find target until Task 8.

- [ ] **Step 3: Local regression, then commit**

```powershell
git commit -m "feat: enable guide experience on published destination pages"
```

- [ ] **Step 4: Deploy, purge, public verifier**

Expected: destination children on the allowlist show guide shell or fail-closed one H1. `/destinations/` remains default template. Previously green comparison and itinerary URLs stay green.

### Task 8: Stage C — practical plan pages

- [ ] **Step 1: Append the 10 plan paths from the locked inventory**

Keep `plan/vietnam-evisa` in stage-0 position. Do not add `plan`.

- [ ] **Step 2: Sync inventories**

Public non-pilot quartet:

```
compare
destinations
plan
itineraries
```

All remaining mutation Finds that targeted `plan/sim-esim-vietnam` or `plan/transport-within-vietnam` as non-pilots must retarget `compare` or `plan` hub strings.

- [ ] **Step 3: Local regression, then commit**

```powershell
git commit -m "feat: enable guide experience on published plan pages"
```

- [ ] **Step 4: Deploy, purge, public verifier**

Also sample JSON-LD on `/plan/sim-esim-vietnam/` and `/destinations/hanoi-travel-guide/` for author name `Administrator` — must be absent. Homepage crawl: every `https://vietnamguide.net/...` content link from `/` returns 200 with matching canonical (ignore `#main` and `wp-json`).

### Task 9: Production evidence

- [ ] **Step 1: Append a Live Packaging section to `ops/verification-log.md`** with: deploy route, backup path, public verifier output, fail-closed destination/plan paths if any, homepage link crawl, schema author sample URLs, cache purge.

- [ ] **Step 2: Commit the log only**

```powershell
git add ops/verification-log.md
git commit -m "docs: record live packaging production evidence"
```

## Final acceptance

Complete only when:

1. Homepage internal content links are HTTP 200 with matching canonicals.
2. No published page has two H1s on a public GET.
3. Sampled JSON-LD has no Person/author `Administrator`.
4. `.vg-source-snapshot` is visually distinct on pages that already contain it.
5. Stage A/B/C allowlists are live; hubs and legal pages are not.
6. Local contract, mutation, and public verifiers pass.
7. No unpublished post drafts were created.
8. `git diff --check` is clean.

## Out of scope (do not do in this plan)

- Comparison evidence HMAC / `ops/comparison-rollout/`
- 29 unpublished `apply-*-post.php` files
- Newsletter provider
- Font files
- Wikimedia image localization
- Rewriting Gutenberg prose

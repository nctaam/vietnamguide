<?php
/**
 * Runtime verification for the VietnamGuide guide experience system.
 *
 * Run with: wp eval-file /path/to/verify-guide-experience-live.php
 */

if (! defined('ABSPATH')) {
    $message = "FAIL: WordPress is not loaded. Run this file with wp eval-file or a loaded verifier route.\n";
    if (defined('STDERR')) {
        fwrite(STDERR, $message);
    } else {
        echo $message;
    }
    exit(1);
}

$failures = [];
$fail = static function (string $message) use (&$failures): void {
    $failures[] = $message;
};
$check = static function (bool $condition, string $fixture, string $detail) use ($fail): void {
    if (! $condition) {
        $fail(sprintf('%s: %s', $fixture, $detail));
    }
};

$required_functions = [
    'vg_guide_pilot_paths',
    'vg_classify_guide_path',
    'vg_get_guide_path',
    'vg_get_guide_type',
    'vg_is_guide_experience_page',
    'vg_split_guide_blocks',
    'vg_inspect_guide_html',
    'vg_is_valid_guide_heading_id',
    'vg_allocate_guide_heading_id',
    'vg_collect_guide_heading_plan',
    'vg_apply_guide_heading_plan',
    'vg_prepare_guide_headings',
    'vg_render_guide_toc',
    'vg_prepare_guide_content',
    'vg_estimate_guide_reading_time',
    'vg_extract_guide_data_value',
    'vg_count_guide_sources',
    'vg_normalize_guide_route_url',
    'vg_get_related_routes',
    'vg_guide_body_has_related_routes',
    'vg_is_valid_guide_context',
    'vg_build_guide_context',
    'vg_eeat_get_field',
    'vg_eeat_lines',
    'vg_eeat_related_route_items',
];

foreach ($required_functions as $function_name) {
    if (! function_exists($function_name)) {
        $fail(sprintf('Required guide function is unavailable: %s.', $function_name));
    }
}

if (! class_exists('WP_HTML_Tag_Processor')) {
    $fail('WP_HTML_Tag_Processor is unavailable.');
}

$runtime_ready = $failures === [];

$with_page_state = static function (WP_Post $post, callable $callback) {
    $had_query = array_key_exists('wp_query', $GLOBALS);
    $original_query = $GLOBALS['wp_query'] ?? null;
    $had_post = array_key_exists('post', $GLOBALS);
    $original_post = $GLOBALS['post'] ?? null;

    $query = new WP_Query();
    $query->is_page = true;
    $query->is_singular = true;
    $query->queried_object = $post;
    $query->queried_object_id = $post->ID;
    $GLOBALS['wp_query'] = $query;
    $GLOBALS['post'] = $post;

    try {
        return $callback();
    } finally {
        if ($had_query) {
            $GLOBALS['wp_query'] = $original_query;
        } else {
            unset($GLOBALS['wp_query']);
        }

        if ($had_post) {
            $GLOBALS['post'] = $original_post;
        } else {
            unset($GLOBALS['post']);
        }
    }
};

$inspect_semantic_html = static function (string $html): ?array {
    $processor = new WP_HTML_Tag_Processor($html);
    $h1_count = 0;
    $h2_ids = [];
    $all_ids = [];
    $has_hero_class = false;

    while ($processor->next_token()) {
        if ('#tag' !== $processor->get_token_type() || $processor->is_tag_closer()) {
            continue;
        }

        $token_name = $processor->get_token_name();
        $id = $processor->get_attribute('id');
        if (is_string($id)) {
            $all_ids[] = $id;
        }
        if ('H1' === $token_name) {
            $h1_count++;
        }
        if ('H2' === $token_name) {
            $id = $processor->get_attribute('id');
            $h2_ids[] = is_string($id) ? $id : '';
        }
        if (true === $processor->has_class('vg-guide-hero') || true === $processor->has_class('vg-guide-hero-cover')) {
            $has_hero_class = true;
        }
    }

    if ($processor->paused_at_incomplete_token()) {
        return null;
    }

    return [
        'h1_count' => $h1_count,
        'h2_ids' => $h2_ids,
        'all_ids' => $all_ids,
        'has_hero_class' => $has_hero_class,
    ];
};

$pilot_types = [
    'destinations/ho-chi-minh-city-travel-guide' => 'destination',
    'itineraries/10-days-in-vietnam' => 'itinerary',
    'itineraries/7-days-in-vietnam' => 'itinerary',
    'itineraries/14-days-in-vietnam' => 'itinerary',
    'itineraries/21-days-in-vietnam' => 'itinerary',
    'itineraries/hanoi-in-2-days' => 'itinerary',
    'compare/ha-long-bay-vs-lan-ha-bay' => 'comparison',
    'plan/vietnam-evisa' => 'practical',
    'compare/cu-chi-tunnels-vs-mekong-delta-day-trip' => 'comparison',
    'compare/da-nang-vs-hoi-an' => 'comparison',
    'compare/hoi-an-vs-hue' => 'comparison',
    'compare/mui-ne-vs-nha-trang' => 'comparison',
    'compare/ninh-binh-day-trip-vs-overnight' => 'comparison',
    'compare/north-central-south-vietnam' => 'comparison',
    'compare/old-quarter-vs-french-quarter-vs-west-lake' => 'comparison',
    'compare/phu-quoc-vs-nha-trang' => 'comparison',
    'compare/trang-an-vs-tam-coc' => 'comparison',
    'destinations/unesco-heritage-sites-vietnam' => 'destination',
    'destinations/best-beaches-in-vietnam' => 'destination',
    'destinations/best-places-to-visit-vietnam' => 'destination',
    'destinations/best-things-to-do-in-hanoi' => 'destination',
    'destinations/best-things-to-do-in-hoi-an' => 'destination',
    'destinations/best-things-to-do-in-hue' => 'destination',
    'destinations/ninh-binh-travel-guide' => 'destination',
    'destinations/ha-long-bay-travel-guide' => 'destination',
    'destinations/cat-ba-travel-guide' => 'destination',
    'destinations/bai-tu-long-bay-guide' => 'destination',
    'destinations/da-nang-travel-guide' => 'destination',
    'destinations/best-islands-in-vietnam' => 'destination',
    'destinations/phu-quoc-travel-guide' => 'destination',
    'destinations/con-dao-travel-guide' => 'destination',
    'destinations/nha-trang-travel-guide' => 'destination',
    'destinations/quy-nhon-travel-guide' => 'destination',
    'destinations/cham-islands-travel-guide' => 'destination',
    'destinations/ly-son-travel-guide' => 'destination',
    'destinations/mekong-delta-travel-guide' => 'destination',
    'destinations/best-day-trips-from-ho-chi-minh-city' => 'destination',
    'destinations/where-to-stay-in-ho-chi-minh-city' => 'destination',
    'destinations/hanoi-travel-guide' => 'destination',
    'destinations/where-to-stay-in-hanoi' => 'destination',
    'destinations/best-day-trips-from-hanoi' => 'destination',
    'destinations/where-to-stay-in-ninh-binh' => 'destination',
    'destinations/tam-coc-travel-guide' => 'destination',
    'plan/best-time-to-visit-vietnam' => 'practical',
    'plan/vietnam-travel-guide' => 'practical',
    'plan/transport-within-vietnam' => 'practical',
    'plan/money-cash-cards-atms' => 'practical',
    'plan/sim-esim-vietnam' => 'practical',
    'plan/safety-scams-vietnam' => 'practical',
    'plan/health-travel-insurance-vietnam' => 'practical',
    'plan/hanoi-airport-to-old-quarter' => 'practical',
    'plan/hanoi-to-ninh-binh-transport' => 'practical',
    'plan/ninh-binh-to-ha-long-bay-transfer' => 'practical',
    'destinations/ha-giang-loop-planning-guide' => 'destination',
    'destinations/sapa-travel-guide' => 'destination',
    'plan/hanoi-to-ha-giang-transport' => 'practical',
    'plan/hanoi-to-sapa-transport' => 'practical',
    'compare/sapa-vs-ha-giang' => 'comparison',
    'destinations/hoi-an-ancient-town-guide' => 'destination',
    'destinations/hue-imperial-city-guide' => 'destination',
    'destinations/da-nang-beaches-guide' => 'destination',
    'destinations/phong-nha-travel-guide' => 'destination',
    'compare/hanoi-vs-ho-chi-minh-city' => 'comparison',
    'compare/mekong-delta-overnight-vs-day-trip' => 'comparison',
    'plan/best-vietnam-routes-first-time-visitors' => 'practical',
    'plan/vietnam-in-december' => 'practical',
    'plan/vietnam-in-january' => 'practical',
    'plan/vietnam-in-february' => 'practical',
    'plan/tet-in-vietnam-travel-guide' => 'practical',
    'plan/vietnam-rainy-season-flexible-route' => 'practical',
    'plan/vietnam-first-trip-planning-checklist' => 'practical',
    'plan/what-to-pack-for-vietnam-region-season' => 'practical',
    'plan/vietnam-airport-arrival-checklist' => 'practical',
    'plan/vietnam-food-safety-street-food-etiquette' => 'practical',
    'plan/where-to-stay-in-vietnam-base-decisions' => 'practical',
    'plan/hanoi-first-time-visitor-mistakes' => 'practical',
    'plan/ninh-binh-without-rushing' => 'practical',
    'plan/ha-long-bay-cruise-questions-before-booking' => 'practical',
    'plan/best-vietnam-cities-for-first-time-visitors' => 'practical',
];
$pilot_posts = [];

if ($runtime_ready) {
    try {
    $check(
        vg_guide_pilot_paths() === array_keys($pilot_types),
        'pilot strict context',
        'the runtime pilot allowlist does not match the expected paths in order'
    );

    foreach ($pilot_types as $path => $expected_type) {
        $post = get_page_by_path($path, OBJECT, 'page');
        if (! $post instanceof WP_Post) {
            $fail(sprintf('pilot strict context: published page lookup failed for %s.', $path));
            continue;
        }

        $pilot_posts[$path] = $post;
        $check($post->post_type === 'page', 'pilot strict context', sprintf('%s is not a page', $path));
        $check($post->post_status === 'publish', 'pilot strict context', sprintf('%s is not published', $path));
        $check(vg_get_guide_path($post) === $path, 'pilot strict context', sprintf('%s has a non-canonical guide path', $path));
        $check(vg_get_guide_type($post) === $expected_type, 'pilot strict context', sprintf('%s did not classify strictly as %s', $path, $expected_type));

        $result = $with_page_state($post, static function () use ($post): array {
            return [
                'enabled' => vg_is_guide_experience_page($post),
                'context' => vg_build_guide_context($post),
            ];
        });
        $check($result['enabled'] === true, 'pilot strict context', sprintf('%s did not enable the guide experience', $path));
        $check(
            is_array($result['context']) && vg_is_valid_guide_context($result['context']),
            'pilot strict context',
            sprintf('%s did not produce a valid strict context', $path)
        );

        if (is_array($result['context'])) {
            $check(
                $result['context']['type'] === $expected_type,
                'pilot strict context',
                sprintf('%s context type did not remain %s', $path, $expected_type)
            );
            $hero_stats = $inspect_semantic_html($result['context']['hero_html']);
            $body_stats = $inspect_semantic_html($result['context']['body_html']);
            $prepared_body = vg_prepare_guide_headings($result['context']['body_html']);
            $check(
                $hero_stats !== null
                    && $body_stats !== null
                    && $hero_stats['h1_count'] === 1
                    && $body_stats['h1_count'] === 0
                    && $hero_stats['has_hero_class'],
                'rendered hero/body H1 contract',
                sprintf('%s must render one real hero H1, zero body H1s, and the real hero class', $path)
            );
            $check(
                $prepared_body['html'] === $result['context']['body_html']
                    && $prepared_body['headings'] === $result['context']['headings']
                    && $result['context']['toc_html'] === vg_render_guide_toc($result['context']['headings'])
                    && $result['context']['reading_time'] >= 1,
                'canonical heading and TOC relationship',
                sprintf('%s context is not canonical or has invalid reading time', $path)
            );
        }
    }
    } catch (Throwable $throwable) {
        $fail(sprintf('Pilot runtime fixtures threw %s: %s', get_class($throwable), $throwable->getMessage()));
    }
}

if ($runtime_ready) {
    $fixture_type_filter = null;
    try {
    if ($pilot_posts !== []) {
        $fixture_post = clone reset($pilot_posts);
    } else {
        // Keep in-memory fixtures runnable even when a pilot page lookup fails.
        $fixture_post = new WP_Post((object) [
            'ID' => 9000001,
            'post_author' => 0,
            'post_date' => '2026-01-01 00:00:00',
            'post_date_gmt' => '2026-01-01 00:00:00',
            'post_content' => '',
            'post_title' => 'Runtime fixture guide',
            'post_excerpt' => '',
            'post_status' => 'publish',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_password' => '',
            'post_name' => 'runtime-fixture-guide',
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => '2026-01-01 00:00:00',
            'post_modified_gmt' => '2026-01-01 00:00:00',
            'post_content_filtered' => '',
            'post_parent' => 0,
            'guid' => '',
            'menu_order' => 0,
            'post_type' => 'page',
            'post_mime_type' => '',
            'comment_count' => 0,
            'filter' => 'raw',
        ]);
        $fixture_type_filter = static function (?string $type, WP_Post $post) use ($fixture_post): ?string {
            return $post->ID === $fixture_post->ID ? 'practical' : $type;
        };
        add_filter('vg_guide_type', $fixture_type_filter, 999, 2);
    }
    $fixture_post->post_password = '';
    $fixture_post->post_content = <<<'HTML'
<!-- wp:html -->
<section class="vg-guide-hero"><h1>Runtime fixture guide</h1></section>
<!-- /wp:html -->
<!-- wp:html -->
<div data-vg-best-for="careful planners" data-vg-skip-if="you need a beach holiday"><h2 id="arrival">Arrival notes</h2><p>Useful details for a careful Vietnam itinerary.</p><h2>Local transport</h2><p>Practical transport details and timing advice.</p></div>
<!-- /wp:html -->
HTML;

    $fixture_context = $with_page_state($fixture_post, static function () use ($fixture_post) {
        return vg_build_guide_context($fixture_post);
    });
    $check(
        is_array($fixture_context) && vg_is_valid_guide_context($fixture_context),
        'rendered hero/body H1 contract',
        'the in-memory guide fixture did not produce a valid context'
    );

    $baseline_split = vg_split_guide_blocks($fixture_post->post_content);
    $comment_marker_post = clone $fixture_post;
    $comment_marker_post->post_content = " \n<!-- vg-hcmc-hero:v1 -->\n\t" . $fixture_post->post_content;
    $whitespace_marker_post = clone $fixture_post;
    $whitespace_marker_post->post_content = " \n\t\n" . $fixture_post->post_content;
    $meaningful_text_post = clone $fixture_post;
    $meaningful_text_post->post_content = "Meaningful introduction.\n" . $fixture_post->post_content;
    $meaningful_html_post = clone $fixture_post;
    $meaningful_html_post->post_content = '<!-- editorial note --><p>Meaningful introduction.</p>' . $fixture_post->post_content;

    $comment_marker_split = vg_split_guide_blocks($comment_marker_post->post_content);
    $whitespace_marker_split = vg_split_guide_blocks($whitespace_marker_post->post_content);
    $check(
        is_array($baseline_split)
            && $comment_marker_split === $baseline_split
            && $whitespace_marker_split === $baseline_split,
        'leading comment-only and whitespace-only freeform markers',
        'leading freeform markers were not ignored without changing the hero/body split'
    );
    $check(
        vg_split_guide_blocks($meaningful_text_post->post_content) === null
            && vg_split_guide_blocks($meaningful_html_post->post_content) === null,
        'meaningful leading freeform rejection',
        'meaningful text or HTML before the hero did not fail closed'
    );

    $generated_h1_filter = static function (string $html): string {
        if (str_contains($html, 'VG_FILTER_H1_FIXTURE')) {
            return $html . '<h1>Injected by a content filter</h1>';
        }
        return $html;
    };
    $filter_fixture = clone $fixture_post;
    $filter_fixture->post_content = str_replace(
        'Practical transport details',
        'VG_FILTER_H1_FIXTURE Practical transport details',
        $filter_fixture->post_content
    );
    add_filter('the_content', $generated_h1_filter, 999);
    try {
        $filtered_content = $with_page_state($filter_fixture, static function () use ($filter_fixture) {
            return vg_prepare_guide_content($filter_fixture);
        });
        $check(
            $filtered_content === null,
            'filter-generated H1 fails closed',
            'an H1 introduced by the_content in the rendered body was accepted'
        );
    } finally {
        remove_filter('the_content', $generated_h1_filter, 999);
    }

    $pseudo_markup = '<script>window.fake = "<h1>script heading</h1>";</script>'
        . '<!-- <h1>comment heading</h1> -->';
    $pseudo_html = '<section class="vg-guide-hero"><h1>Real heading</h1>' . $pseudo_markup . '</section>';
    $pseudo_inspection = vg_inspect_guide_html($pseudo_html);
    $pseudo_stats = $inspect_semantic_html($pseudo_html);
    $check(
        $pseudo_inspection !== null
            && $pseudo_inspection['has_hero_class']
            && $pseudo_inspection['h1_count'] === 1
            && $pseudo_stats !== null
            && $pseudo_stats['h1_count'] === 1,
        'script and comment pseudo-headings ignored',
        'semantic H1 inspection counted script or comment text'
    );

    $pseudo_post = clone $fixture_post;
    $pseudo_post->post_content = <<<'HTML'
<!-- wp:html -->
<section class="vg-guide-hero"><h1>Runtime pseudo-heading fixture</h1><span>VG_PSEUDO_H1_FIXTURE</span></section>
<!-- /wp:html -->
<!-- wp:html -->
<div><h2 id="pseudo-first">First section</h2><p>Production parsing fixture body.</p><h2>Second section</h2><p>More fixture detail.</p></div>
<!-- /wp:html -->
HTML;
    $pseudo_filter = static function (string $html) use ($pseudo_markup): string {
        return str_replace('<span>VG_PSEUDO_H1_FIXTURE</span>', $pseudo_markup, $html);
    };
    add_filter('the_content', $pseudo_filter, 999);
    try {
        $pseudo_content = $with_page_state($pseudo_post, static function () use ($pseudo_post) {
            return vg_prepare_guide_content($pseudo_post);
        });
        $pseudo_context = $with_page_state($pseudo_post, static function () use ($pseudo_post) {
            return vg_build_guide_context($pseudo_post);
        });
        $pseudo_hero_stats = is_array($pseudo_content)
            ? vg_inspect_guide_html((string) $pseudo_content['hero_html'])
            : null;
        $check(
            is_array($pseudo_content)
                && $pseudo_hero_stats !== null
                && $pseudo_hero_stats['h1_count'] === 1
                && $pseudo_hero_stats['has_hero_class']
                && is_array($pseudo_context)
                && vg_is_valid_guide_context($pseudo_context),
            'script and comment pseudo-headings ignored',
            'production content preparation rejected pseudo-H1 strings or produced an invalid context'
        );
    } finally {
        remove_filter('the_content', $pseudo_filter, 999);
    }

    $collision_html = '<h2 id="keep">Keep</h2><h2 id="keep">Duplicate</h2><h2>Keep</h2>';
    $collision_result = vg_prepare_guide_headings($collision_html);
    $collision_stats = $inspect_semantic_html($collision_result['html']);
    $check(
        $collision_stats !== null
            && $collision_stats['h2_ids'] === ['keep', 'keep-2', 'keep-3']
            && array_column($collision_result['headings'], 'id') === ['keep', 'keep-2', 'keep-3'],
        'authored heading IDs and deterministic collisions',
        'authored IDs were not preserved and duplicate IDs were not disambiguated deterministically'
    );

    $non_h2_collision_html = '<div id="arrival"><h3 id="local-transport">Reserved elements</h3></div><h2>Arrival</h2><h2>Local transport</h2>';
    $non_h2_collision_result = vg_prepare_guide_headings($non_h2_collision_html);
    $non_h2_collision_stats = $inspect_semantic_html($non_h2_collision_result['html']);
    $check(
        $non_h2_collision_stats !== null
            && $non_h2_collision_stats['all_ids'] === ['arrival', 'local-transport', 'arrival-2', 'local-transport-2']
            && array_column($non_h2_collision_result['headings'], 'id') === ['arrival-2', 'local-transport-2'],
        'non-H2 element IDs reserve heading slugs',
        'generated H2 IDs collided with valid IDs already used by non-H2 body elements'
    );

    $authored_non_h2_collision_html = '<div id="arrival"></div><h2 id="arrival">Arrival</h2>';
    $authored_non_h2_collision_result = vg_prepare_guide_headings($authored_non_h2_collision_html);
    $authored_non_h2_collision_stats = $inspect_semantic_html($authored_non_h2_collision_result['html']);
    $check(
        $authored_non_h2_collision_stats !== null
            && $authored_non_h2_collision_stats['all_ids'] === ['arrival', 'arrival-2']
            && array_column($authored_non_h2_collision_result['headings'], 'id') === ['arrival-2'],
        'authored H2 IDs avoid non-H2 collisions',
        'an authored H2 ID was preserved even though a non-H2 element already owned it'
    );

    $opt_out_html = '<h2 data-vg-toc="false" id="reserved">Hidden</h2><h2>Reserved</h2><h2>Visible</h2>';
    $opt_out_result = vg_prepare_guide_headings($opt_out_html);
    $opt_out_stats = $inspect_semantic_html($opt_out_result['html']);
    $check(
        $opt_out_stats !== null
            && $opt_out_stats['h2_ids'] === ['reserved', 'reserved-2', 'visible']
            && array_column($opt_out_result['headings'], 'id') === ['reserved-2', 'visible'],
        'opted-out headings excluded without collisions',
        'data-vg-toc=false did not reserve its ID while remaining excluded from the TOC'
    );

    $incomplete_html = '<section class="vg-guide-hero"><h1>Complete</h1><div';
    $incomplete_headings = '<h2>Complete</h2><div';
    $incomplete_prepared = vg_prepare_guide_headings($incomplete_headings);
    $check(
        vg_inspect_guide_html($incomplete_html) === null
            && vg_collect_guide_heading_plan($incomplete_headings) === null
            && $incomplete_prepared === ['html' => $incomplete_headings, 'headings' => []],
        'incomplete markup fails closed',
        'an incomplete token stream was accepted or rewritten'
    );

    $eeat_meta_filter = static function ($value, int $object_id, string $meta_key, bool $single, string $meta_type) use ($fixture_post) {
        if ($meta_type !== 'post' || $object_id !== $fixture_post->ID) {
            return $value;
        }

        $fixtures = [
            'vg_eeat_last_meaningful_update' => 'Canonical review date',
            'vg_eeat_sources_checked' => "Canonical source A\nCanonical source B",
            'vg_eeat_related_routes' => '',
            '_vg_reviewed_at' => 'Legacy review date',
        ];
        if (! array_key_exists($meta_key, $fixtures)) {
            return $value;
        }

        return $single ? $fixtures[$meta_key] : [$fixtures[$meta_key]];
    };

    add_filter('get_post_metadata', $eeat_meta_filter, 1, 5);
    try {
        $eeat_context = $with_page_state($fixture_post, static function () use ($fixture_post) {
            return vg_build_guide_context($fixture_post);
        });
        $check(
            is_array($eeat_context)
                && $eeat_context['reviewed_at'] === 'Canonical review date'
                && $eeat_context['source_count'] === 2,
            'canonical EEAT metadata precedence',
            'legacy or computed metadata took precedence over canonical EEAT fields'
        );
    } finally {
        remove_filter('get_post_metadata', $eeat_meta_filter, 1);
    }

    $valid_urls = [
        '/plan/vietnam-evisa/' => '/plan/vietnam-evisa/',
        'https://example.com/route' => 'https://example.com/route',
        'http://example.com/route' => 'http://example.com/route',
        '//cdn.example.com/route' => '//cdn.example.com/route',
    ];
    foreach ($valid_urls as $input => $expected) {
        $check(
            vg_normalize_guide_route_url($input) === $expected,
            'curated route URL normalization',
            sprintf('valid URL did not normalize canonically: %s', $input)
        );
    }
    foreach ([
        '',
        'plan/vietnam-evisa/',
        'javascript:alert(1)',
        'data:text/html,bad',
        'ftp://example.com/route',
        'https:///missing-host',
        'https://example.com/bad path',
        'https://example.com\\bad',
    ] as $invalid_url) {
        $check(
            vg_normalize_guide_route_url($invalid_url) === '',
            'curated route URL normalization',
            sprintf('invalid URL shape was accepted: %s', $invalid_url)
        );
    }

    $route_meta_filter = static function ($value, int $object_id, string $meta_key, bool $single, string $meta_type) use ($fixture_post) {
        if ($meta_type === 'post' && $object_id === $fixture_post->ID && $meta_key === 'vg_eeat_related_routes') {
            $routes = "Valid local|/plan/vietnam-evisa/\n"
                . "Bad script|javascript:alert(1)\n"
                . "Bad relative|plan/not-rooted\n"
                . "Valid web|https://example.com/guide";
            return $single ? $routes : [$routes];
        }
        return $value;
    };

    add_filter('get_post_metadata', $route_meta_filter, 1, 5);
    try {
        $curated_routes = vg_get_related_routes($fixture_post, false);
        $check(
            $curated_routes === [
                ['title' => 'Valid local', 'url' => '/plan/vietnam-evisa/'],
                ['title' => 'Valid web', 'url' => 'https://example.com/guide'],
            ],
            'curated route URL normalization',
            'malformed curated route items or URLs were not rejected'
        );
    } finally {
        remove_filter('get_post_metadata', $route_meta_filter, 1);
    }

    $protected_post = clone $fixture_post;
    $protected_post->post_password = 'runtime-fixture-password';
    $multipage_post = clone $fixture_post;
    $multipage_post->post_content .= "\n<!--nextpage-->\n<p>Page two</p>";
    $protected_context = $with_page_state($protected_post, static function () use ($protected_post) {
        return vg_build_guide_context($protected_post);
    });
    $multipage_context = $with_page_state($multipage_post, static function () use ($multipage_post) {
        return vg_build_guide_context($multipage_post);
    });
    $check(
        $protected_context === null && $multipage_context === null,
        'protected and multipage fallback',
        'protected or <!--nextpage--> content did not return null for the default template fallback'
    );

    if (is_array($fixture_context)) {
        $malformed = $fixture_context;
        unset($malformed['type']);
        $check(! vg_is_valid_guide_context($malformed), 'malformed context rejection', 'a missing type was accepted');

        $malformed = $fixture_context;
        $malformed['headings'][0] = 'not-an-array';
        $check(! vg_is_valid_guide_context($malformed), 'malformed context rejection', 'a malformed heading entry was accepted');

        $malformed = $fixture_context;
        $malformed['headings'][1]['id'] = $malformed['headings'][0]['id'];
        $check(! vg_is_valid_guide_context($malformed), 'malformed context rejection', 'duplicate heading IDs were accepted');

        $malformed = $fixture_context;
        $malformed['body_html'] = str_replace('Arrival notes', 'Changed arrival notes', $malformed['body_html']);
        $check(! vg_is_valid_guide_context($malformed), 'canonical heading and TOC relationship', 'a body/headings mismatch was accepted');

        $malformed = $fixture_context;
        $malformed['toc_html'] = '<nav>stale TOC</nav>';
        $check(! vg_is_valid_guide_context($malformed), 'canonical heading and TOC relationship', 'a TOC/headings mismatch was accepted');

        $malformed = $fixture_context;
        $malformed['related_routes'] = [['url' => '/plan/vietnam-evisa/']];
        $check(! vg_is_valid_guide_context($malformed), 'malformed context rejection', 'a malformed route item was accepted');

        $malformed = $fixture_context;
        $malformed['related_routes'] = [['title' => 'Bad route', 'url' => 'javascript:alert(1)']];
        $check(! vg_is_valid_guide_context($malformed), 'malformed context rejection', 'an invalid route URL was accepted');

        $malformed = $fixture_context;
        $malformed['body_html'] .= '<div class="vg-related-routes">Embedded routes</div>';
        $malformed['has_existing_related_routes'] = true;
        $malformed['related_routes'] = [['title' => 'Generated route', 'url' => '/plan/vietnam-evisa/']];
        $check(
            ! vg_is_valid_guide_context($malformed) && vg_get_related_routes($fixture_post, true) === [],
            'embedded related-route conflict rejection',
            'embedded related routes did not suppress or conflict with generated routes'
        );
    }
    } catch (Throwable $throwable) {
        $fail(sprintf('Behavioral runtime fixtures threw %s: %s', get_class($throwable), $throwable->getMessage()));
    } finally {
        if ($fixture_type_filter !== null) {
            remove_filter('vg_guide_type', $fixture_type_filter, 999);
        }
    }
}

if ($failures !== []) {
    $message = "VietnamGuide guide experience live verification failed:\n- " . implode("\n- ", $failures);
    if (class_exists('WP_CLI')) {
        WP_CLI::error($message);
    }

    $output = "FAIL: {$message}\n";
    if (defined('STDERR')) {
        fwrite(STDERR, $output);
    } else {
        echo $output;
    }
    exit(1);
}

$success = 'VietnamGuide guide experience live verification passed.';
if (class_exists('WP_CLI')) {
    WP_CLI::success($success);
} else {
    echo "SUCCESS: {$success}\n";
}

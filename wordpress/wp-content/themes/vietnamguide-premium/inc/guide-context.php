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
    $processor = new WP_HTML_Tag_Processor($html);
    while ($processor->next_token()) {
        if ($processor->is_tag_closer()) {
            continue;
        }

        $value = $processor->get_attribute($attribute);
        if ($value !== null) {
            return sanitize_text_field((string) $value);
        }
    }

    return '';
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
        . 'contains(concat(" ", normalize-space(@class), " "), " vg-pattern-source-block ") or '
        . 'contains(concat(" ", normalize-space(@class), " "), " source-diversity ") or '
        . 'contains(concat(" ", normalize-space(@class), " "), " source-trail ")'
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

    $processor = new WP_HTML_Tag_Processor($content['body_html']);
    $hasExistingRelated = false;
    while ($processor->next_token()) {
        if ($processor->is_tag_closer()) {
            continue;
        }

        if (true === $processor->has_class('vg-related-routes')) {
            $hasExistingRelated = true;
            break;
        }
    }

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

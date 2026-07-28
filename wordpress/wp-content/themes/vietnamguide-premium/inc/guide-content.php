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

            if (preg_match('/data-vg-toc="false"|data-vg-toc=\'false\'/i', $attributes)) {
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

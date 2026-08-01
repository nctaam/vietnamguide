<?php
if (! defined('ABSPATH')) {
    exit;
}

function vg_is_empty_freeform_block(array $block): bool
{
    if (($block['blockName'] ?? null) !== null) {
        return false;
    }

    $html = (string) ($block['innerHTML'] ?? '');
    if (trim($html) === '') {
        return true;
    }

    $without_comments = preg_replace('/<!--[\s\S]*?-->/', '', $html);
    return is_string($without_comments) && trim($without_comments) === '';
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

function vg_inspect_guide_html(string $html): ?array
{
    $processor = new WP_HTML_Tag_Processor($html);
    $hasHeroClass = false;
    $h1Count = 0;

    while ($processor->next_token()) {
        $tokenName = $processor->get_token_name();
        if ($processor->is_tag_closer()) {
            continue;
        }

        if ('H1' === $tokenName) {
            $h1Count++;
        }

        if (true === $processor->has_class('vg-guide-hero')) {
            $hasHeroClass = true;
        }
    }

    if ($processor->paused_at_incomplete_token()) {
        return null;
    }

    return [
        'has_hero_class' => $hasHeroClass,
        'h1_count' => $h1Count,
    ];
}

function vg_is_valid_guide_heading_id(string $id): bool
{
    return $id !== '' && preg_match('/\s/u', $id) === 0;
}

function vg_allocate_guide_heading_id(string $base, array $reservedIds, array $assignedIds): string
{
    $candidate = $base;
    $suffix = 2;
    while (isset($reservedIds[$candidate]) || isset($assignedIds[$candidate])) {
        $candidate = $base . '-' . $suffix;
        $suffix++;
    }

    return $candidate;
}

function vg_collect_guide_heading_plan(string $html): ?array
{
    $processor = new WP_HTML_Tag_Processor($html);
    $headings = [];
    $currentHeading = null;

    while ($processor->next_token()) {
        $tokenName = $processor->get_token_name();

        if ('H2' === $tokenName) {
            if ($processor->is_tag_closer()) {
                if ($currentHeading === null) {
                    return null;
                }

                $label = preg_replace('/\s+/u', ' ', implode('', $currentHeading['label_parts']));
                if (! is_string($label)) {
                    return null;
                }

                $currentHeading['label'] = trim($label);
                unset($currentHeading['label_parts']);
                $headings[] = $currentHeading;
                $currentHeading = null;
            } else {
                if ($currentHeading !== null) {
                    return null;
                }

                $tocAttribute = $processor->get_attribute('data-vg-toc');
                $idAttribute = $processor->get_attribute('id');
                $currentHeading = [
                    'original_id' => is_string($idAttribute) ? $idAttribute : null,
                    'opt_out' => is_string($tocAttribute) && strcasecmp(trim($tocAttribute), 'false') === 0,
                    'label_parts' => [],
                ];
            }

            continue;
        }

        if ($currentHeading === null) {
            continue;
        }

        if ('#text' === $tokenName) {
            $currentHeading['label_parts'][] = $processor->get_modifiable_text();
        } elseif ('BR' === $tokenName && ! $processor->is_tag_closer()) {
            $currentHeading['label_parts'][] = ' ';
        }
    }

    if ($processor->paused_at_incomplete_token() || $currentHeading !== null) {
        return null;
    }

    $reservedIds = [];
    foreach ($headings as $heading) {
        $originalId = $heading['original_id'];
        if (is_string($originalId) && vg_is_valid_guide_heading_id($originalId)) {
            $reservedIds[$originalId] = true;
        }
    }

    $assignedIds = [];
    foreach ($headings as $index => $heading) {
        $originalId = $heading['original_id'];
        $label = $heading['label'];
        $eligible = ! $heading['opt_out'] && $label !== '';
        $plannedId = null;

        if (is_string($originalId) && vg_is_valid_guide_heading_id($originalId)) {
            if (! isset($assignedIds[$originalId])) {
                $plannedId = $originalId;
            } else {
                $plannedId = vg_allocate_guide_heading_id($originalId, $reservedIds, $assignedIds);
            }
        } elseif ($eligible) {
            $base = sanitize_title($label);
            if ($base === '') {
                $base = 'section';
            }
            $plannedId = vg_allocate_guide_heading_id($base, $reservedIds, $assignedIds);
        }

        if ($plannedId !== null) {
            $assignedIds[$plannedId] = true;
        }

        $headings[$index]['eligible'] = $eligible;
        $headings[$index]['planned_id'] = $plannedId;
    }

    return $headings;
}

function vg_apply_guide_heading_plan(string $html, array $plan): ?string
{
    $processor = new WP_HTML_Tag_Processor($html);
    $headingIndex = 0;

    while ($processor->next_tag('H2')) {
        if (! isset($plan[$headingIndex])) {
            return null;
        }

        $plannedId = $plan[$headingIndex]['planned_id'];
        $idAttribute = $processor->get_attribute('id');
        $currentId = is_string($idAttribute) ? $idAttribute : null;

        if (is_string($plannedId)) {
            if ($plannedId !== $currentId) {
                if (! $processor->set_attribute('id', $plannedId)) {
                    return null;
                }
            }
        }

        $headingIndex++;
    }

    if ($processor->paused_at_incomplete_token() || $headingIndex !== count($plan)) {
        return null;
    }

    return $processor->get_updated_html();
}

function vg_prepare_guide_headings(string $html): array
{
    $fallback = [
        'html' => $html,
        'headings' => [],
    ];

    $plan = vg_collect_guide_heading_plan($html);
    if ($plan === null) {
        return $fallback;
    }

    $normalized = vg_apply_guide_heading_plan($html, $plan);
    if ($normalized === null) {
        return $fallback;
    }

    $headings = [];
    foreach ($plan as $heading) {
        if (! $heading['eligible']) {
            continue;
        }

        $headings[] = [
            'id' => (string) $heading['planned_id'],
            'label' => (string) $heading['label'],
        ];
    }

    return [
        'html' => $normalized,
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
    $heroStats = vg_inspect_guide_html((string) $heroHtml);
    $bodyStats = vg_inspect_guide_html((string) $bodyHtml);
    if (
        $heroStats === null
        || $bodyStats === null
        || ! $heroStats['has_hero_class']
        || $heroStats['h1_count'] !== 1
        || $bodyStats['h1_count'] !== 0
    ) {
        return null;
    }

    $prepared = vg_prepare_guide_headings((string) $bodyHtml);

    return [
        'hero_html' => (string) $heroHtml,
        'body_html' => $prepared['html'],
        'headings' => $prepared['headings'],
        'toc_html' => vg_render_guide_toc($prepared['headings']),
    ];
}

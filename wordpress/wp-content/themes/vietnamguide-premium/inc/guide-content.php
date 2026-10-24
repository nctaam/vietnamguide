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

function vg_extract_balanced_hero_container(string $rawHtml): ?array
{
    if (! preg_match('/\A(?:\s|<!--[\s\S]*?-->)*<(?P<tag>section|div)\b[^>]*\bclass\s*=\s*["\'][^"\']*vg-guide-hero[^"\']*["\'][^>]*>/i', $rawHtml, $openMatch, PREG_OFFSET_CAPTURE)) {
        return null;
    }

    $rootTag = strtolower((string) $openMatch['tag'][0]);
    $openTagFull = (string) $openMatch[0][0];
    $openTagEnd = (int) $openMatch[0][1] + strlen($openTagFull);

    $depth = 1;
    $heroEnd = null;
    $tokenPattern = '/<!--[\s\S]*?-->|<(?P<closer>\/)?(?P<name>div|section)\b[^>]*>/i';
    if (preg_match_all($tokenPattern, $rawHtml, $tokens, PREG_OFFSET_CAPTURE, $openTagEnd)) {
        $tokenCount = count($tokens[0]);
        for ($i = 0; $i < $tokenCount; $i++) {
            $rawToken = (string) $tokens[0][$i][0];
            $tokenOffset = (int) $tokens[0][$i][1];
            if (str_starts_with($rawToken, '<!--')) {
                continue;
            }

            $tagName = strtolower((string) $tokens['name'][$i][0]);
            if ($tagName !== $rootTag) {
                continue;
            }

            $isCloser = ((string) $tokens['closer'][$i][0]) === '/';
            if (! $isCloser && str_ends_with(rtrim($rawToken), '/>')) {
                continue;
            }

            if ($isCloser) {
                $depth--;
                if ($depth === 0) {
                    $heroEnd = $tokenOffset + strlen($rawToken);
                    break;
                }
            } else {
                $depth++;
            }
        }
    }

    if ($heroEnd === null) {
        return null;
    }

    $heroSource = trim(substr($rawHtml, 0, $heroEnd));
    $bodySource = trim(substr($rawHtml, $heroEnd));
    if ($bodySource === '' || preg_match_all('/<h1\b/i', $heroSource) !== 1 || preg_match('/<h1\b/i', $bodySource)) {
        return null;
    }

    return [
        'hero_source' => $heroSource,
        'body_source' => $bodySource,
    ];
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

    // Branch A: Standard multi-block Gutenberg structure (pilot routes)
    if (count($blocks) > 1) {
        $heroBlock = $blocks[0];
        $heroSource = serialize_block($heroBlock);
        if (strpos($heroSource, 'vg-guide-hero') === false || preg_match_all('/<h1\b/i', $heroSource) !== 1) {
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

    // Branch B: Raw HTML or single freeform block with balanced vg-guide-hero container (legacy routes)
    return vg_extract_balanced_hero_container($postContent);
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

        if (true === $processor->has_class('vg-guide-hero') || true === $processor->has_class('vg-guide-hero-cover')) {
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
    $reservedIds = [];
    $chromeStack = [];

    while ($processor->next_token()) {
        $tokenName = $processor->get_token_name();
        $isTagCloser = $processor->is_tag_closer();

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

        if (! $isTagCloser && 'H2' !== $tokenName) {
            $elementId = null;
            if (is_string($tokenName) && isset($tokenName[0]) && $tokenName[0] !== '#') {
                $elementId = $processor->get_attribute('id');
            }
            if (is_string($elementId) && vg_is_valid_guide_heading_id($elementId)) {
                $reservedIds[$elementId] = true;
            }
        }

        if ('H2' === $tokenName) {
            if ($isTagCloser) {
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
                    'opt_out' => (is_string($tocAttribute) && strcasecmp(trim($tocAttribute), 'false') === 0)
                        || $chromeStack !== [],
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
        } elseif ('BR' === $tokenName && ! $isTagCloser) {
            $currentHeading['label_parts'][] = ' ';
        }
    }

    if ($processor->paused_at_incomplete_token() || $currentHeading !== null) {
        return null;
    }

    $assignedIds = [];
    foreach ($headings as $index => $heading) {
        $originalId = $heading['original_id'];
        $label = $heading['label'];
        $eligible = ! $heading['opt_out'] && $label !== '';
        $plannedId = null;

        if (is_string($originalId) && vg_is_valid_guide_heading_id($originalId)) {
            if (! isset($reservedIds[$originalId]) && ! isset($assignedIds[$originalId])) {
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

function vg_render_inline_markdown_fallback(string $text): string
{
    $escaped = esc_html($text);

    $escaped = (string) preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped);
    $escaped = (string) preg_replace_callback(
        '/\[([^\]]+)\]\(([^)]+)\)/',
        static function (array $m): string {
            return sprintf('<a href="%1$s">%2$s</a>', esc_url(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8')), $m[1]);
        },
        $escaped
    );
    $escaped = (string) preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $escaped);
    $escaped = (string) preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/', '<em>$1</em>', $escaped);

    return $escaped;
}

function vg_resolve_repository_markdown_path(WP_Post $post): ?string
{
    $candidateRoots = [];
    if (defined('ABSPATH')) {
        $candidateRoots[] = rtrim((string) ABSPATH, '/\\') . '/../content';
    }
    if (function_exists('get_template_directory')) {
        $candidateRoots[] = dirname((string) get_template_directory(), 4) . '/content';
    }

    $routeCandidates = [];
    if (function_exists('vg_get_post_route_path')) {
        $rp = trim((string) vg_get_post_route_path($post), '/');
        if ($rp !== '') {
            $routeCandidates[] = $rp;
        }
    }
    if (function_exists('get_page_uri')) {
        $uri = trim((string) get_page_uri($post), '/');
        if ($uri !== '') {
            $routeCandidates[] = $uri;
        }
    }

    $slug = trim((string) ($post->post_name ?? ''), '/');
    if ($slug !== '') {
        foreach (['destinations', 'itineraries', 'compare', 'plan'] as $cat) {
            $routeCandidates[] = $cat . '/' . $slug;
        }
    }

    foreach (array_unique($routeCandidates) as $relRoute) {
        if (! preg_match('#^(destinations|itineraries|compare|plan)/[a-z0-9-]+$#', $relRoute)) {
            continue;
        }
        foreach ($candidateRoots as $root) {
            $fullPath = $root . '/' . $relRoute . '.md';
            if (is_file($fullPath) && is_readable($fullPath)) {
                return $fullPath;
            }
        }
    }

    return null;
}

function vg_try_load_repository_markdown_guide(WP_Post $post): string
{
    $mdPath = vg_resolve_repository_markdown_path($post);
    if ($mdPath === null) {
        return '';
    }

    $raw = @file_get_contents($mdPath);
    if (! is_string($raw) || trim($raw) === '') {
        return '';
    }

    $frontmatter = [];
    $body = $raw;
    if (preg_match('/\A---\R([\s\S]*?)\R---\R?([\s\S]*)\z/', $raw, $fmMatch)) {
        $fmLines = preg_split('/\R/', (string) $fmMatch[1]) ?: [];
        foreach ($fmLines as $fmLine) {
            if (preg_match('/^\s*([a-zA-Z0-9_]+)\s*:\s*(.+?)\s*$/', (string) $fmLine, $kv)) {
                $val = trim((string) $kv[2]);
                if (
                    (str_starts_with($val, '"') && str_ends_with($val, '"'))
                    || (str_starts_with($val, "'") && str_ends_with($val, "'"))
                ) {
                    $val = substr($val, 1, -1);
                }
                $frontmatter[(string) $kv[1]] = $val;
            }
        }
        $body = (string) $fmMatch[2];
    }

    $lines = preg_split('/\R/', $body) ?: [];
    $extractedH1 = null;
    $htmlBlocks = [];
    $lineCount = count($lines);
    $i = 0;

    while ($i < $lineCount) {
        $trimmed = trim((string) $lines[$i]);
        if ($trimmed === '') {
            $i++;
            continue;
        }

        if (preg_match('/^-{3,}$|^\*{3,}$|^_{3,}$/', $trimmed)) {
            $htmlBlocks[] = '<hr class="wp-block-separator has-alpha-channel-opacity"/>';
            $i++;
            continue;
        }

        if (str_starts_with($trimmed, '```')) {
            $codeLines = [];
            $i++;
            while ($i < $lineCount && ! str_starts_with(trim((string) $lines[$i]), '```')) {
                $codeLines[] = esc_html((string) $lines[$i]);
                $i++;
            }
            $i++;
            $htmlBlocks[] = '<pre class="wp-block-code"><code>' . implode("\n", $codeLines) . '</code></pre>';
            continue;
        }

        if (preg_match('/^(#{1,6})\s+(.+)$/', $trimmed, $hMatch)) {
            $level = strlen((string) $hMatch[1]);
            $rawTitle = trim((string) $hMatch[2]);
            if ($level === 1 && $extractedH1 === null) {
                $extractedH1 = $rawTitle;
            } else {
                $effectiveLevel = $level === 1 ? 2 : min($level, 6);
                $hTag = 'h' . (string) $effectiveLevel;
                $htmlBlocks[] = sprintf(
                    '<%1$s>%2$s</%1$s>',
                    $hTag,
                    vg_render_inline_markdown_fallback($rawTitle)
                );
            }
            $i++;
            continue;
        }

        if (str_starts_with($trimmed, '|') && str_ends_with($trimmed, '|')) {
            $tableLines = [];
            while ($i < $lineCount && str_starts_with(trim((string) $lines[$i]), '|')) {
                $tableLines[] = trim((string) $lines[$i]);
                $i++;
            }
            if (count($tableLines) >= 2) {
                $headers = array_map('trim', explode('|', trim($tableLines[0], '|')));
                $bodyStart = preg_match('/^\|?\s*[-:]+[-| :]*\s*\|?$/', $tableLines[1]) ? 2 : 1;
                $thead = '<thead><tr>';
                foreach ($headers as $cell) {
                    $thead .= '<th>' . vg_render_inline_markdown_fallback($cell) . '</th>';
                }
                $thead .= '</tr></thead>';
                $tbody = '<tbody>';
                $rowTotal = count($tableLines);
                for ($r = $bodyStart; $r < $rowTotal; $r++) {
                    $cells = array_map('trim', explode('|', trim($tableLines[$r], '|')));
                    $tbody .= '<tr>';
                    foreach ($cells as $cell) {
                        $tbody .= '<td>' . vg_render_inline_markdown_fallback($cell) . '</td>';
                    }
                    $tbody .= '</tr>';
                }
                $tbody .= '</tbody>';
                $htmlBlocks[] = '<figure class="wp-block-table"><table>' . $thead . $tbody . '</table></figure>';
            }
            continue;
        }

        if (str_starts_with($trimmed, '>')) {
            $bqLines = [];
            while ($i < $lineCount && str_starts_with(trim((string) $lines[$i]), '>')) {
                $bqLines[] = (string) preg_replace('/^>\s?/', '', trim((string) $lines[$i]));
                $i++;
            }
            $firstLine = isset($bqLines[0]) ? trim($bqLines[0]) : '';
            if (preg_match('/^\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]\s*(.*)$/i', $firstLine, $alertMatch)) {
                $calloutType = strtolower((string) $alertMatch[1]);
                $remainder = trim((string) $alertMatch[2]);
                $inner = '<p class="vg-callout__title"><strong>' . esc_html(strtoupper($calloutType)) . '</strong></p>';
                if ($remainder !== '') {
                    $inner .= '<p>' . vg_render_inline_markdown_fallback($remainder) . '</p>';
                }
                $bqCount = count($bqLines);
                for ($b = 1; $b < $bqCount; $b++) {
                    if (trim($bqLines[$b]) !== '') {
                        $inner .= '<p>' . vg_render_inline_markdown_fallback(trim($bqLines[$b])) . '</p>';
                    }
                }
                $htmlBlocks[] = sprintf(
                    '<blockquote class="wp-block-quote vg-callout vg-callout-%1$s">%2$s</blockquote>',
                    esc_attr($calloutType),
                    $inner
                );
            } else {
                $inner = '';
                foreach ($bqLines as $bqLine) {
                    if (trim($bqLine) !== '') {
                        $inner .= '<p>' . vg_render_inline_markdown_fallback(trim($bqLine)) . '</p>';
                    }
                }
                $htmlBlocks[] = '<blockquote class="wp-block-quote">' . $inner . '</blockquote>';
            }
            continue;
        }

        if (preg_match('/^[-*+]\s+/', $trimmed)) {
            $listHtml = '<ul>';
            while ($i < $lineCount && preg_match('/^\s*[-*+]\s+(.+)$/', (string) $lines[$i], $liMatch)) {
                $listHtml .= '<li>' . vg_render_inline_markdown_fallback(trim((string) $liMatch[1])) . '</li>';
                $i++;
            }
            $listHtml .= '</ul>';
            $htmlBlocks[] = $listHtml;
            continue;
        }

        if (preg_match('/^\d+\.\s+/', $trimmed)) {
            $listHtml = '<ol>';
            while ($i < $lineCount && preg_match('/^\s*\d+\.\s+(.+)$/', (string) $lines[$i], $liMatch)) {
                $listHtml .= '<li>' . vg_render_inline_markdown_fallback(trim((string) $liMatch[1])) . '</li>';
                $i++;
            }
            $listHtml .= '</ol>';
            $htmlBlocks[] = $listHtml;
            continue;
        }

        $paraLines = [];
        while ($i < $lineCount) {
            $cur = trim((string) $lines[$i]);
            if (
                $cur === ''
                || str_starts_with($cur, '#')
                || str_starts_with($cur, '>')
                || str_starts_with($cur, '|')
                || str_starts_with($cur, '```')
                || preg_match('/^[-*+]\s+/', $cur)
                || preg_match('/^\d+\.\s+/', $cur)
                || preg_match('/^-{3,}$|^\*{3,}$|^_{3,}$/', $cur)
            ) {
                break;
            }
            $paraLines[] = $cur;
            $i++;
        }
        if ($paraLines !== []) {
            $htmlBlocks[] = '<p>' . vg_render_inline_markdown_fallback(implode(' ', $paraLines)) . '</p>';
        }
    }

    $title = (string) ($frontmatter['title'] ?? $extractedH1 ?? $post->post_title ?? '');
    $description = (string) ($frontmatter['description'] ?? '');
    $heroHtml = sprintf(
        '<div class="wp-block-group alignfull vg-guide-hero"><div class="vg-guide-hero__inner"><h1>%1$s</h1>%2$s</div></div>',
        esc_html($title),
        $description !== '' ? '<p class="vg-guide-lead">' . esc_html($description) . '</p>' : ''
    );

    return $heroHtml . "\n" . implode("\n", $htmlBlocks);
}

function vg_prepare_guide_content(WP_Post $post): ?array
{
    $split = vg_split_guide_blocks((string) $post->post_content);
    if ($split === null) {
        $fallbackMarkdownHtml = vg_try_load_repository_markdown_guide($post);
        if ($fallbackMarkdownHtml !== '') {
            $split = vg_split_guide_blocks($fallbackMarkdownHtml);
        }
    }
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

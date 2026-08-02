<?php

declare(strict_types=1);

if ($argc !== 3 || ! is_file($argv[1]) || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $argv[2])) {
    fwrite(STDERR, "Usage: php verify-guide-experience-tokenizer.php <source.php> <variable-name>\n");
    exit(2);
}

$source = file_get_contents($argv[1]);
if (! is_string($source)) {
    fwrite(STDERR, "Unable to read PHP source.\n");
    exit(2);
}

$tokenText = static fn($token): string => is_array($token) ? $token[1] : $token;
$tokenId = static fn($token): ?int => is_array($token) ? $token[0] : null;
$isTrivia = static function ($token) use ($tokenId): bool {
    return in_array($tokenId($token), [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
};
$previousSignificant = static function (array $tokens, int $index) use ($isTrivia) {
    for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
        if (! $isTrivia($tokens[$cursor])) {
            return $tokens[$cursor];
        }
    }
    return null;
};
$nextSignificantIndex = static function (array $tokens, int $index) use ($isTrivia): ?int {
    for ($cursor = $index + 1, $count = count($tokens); $cursor < $count; $cursor++) {
        if (! $isTrivia($tokens[$cursor])) {
            return $cursor;
        }
    }
    return null;
};

try {
    $tokens = token_get_all($source, TOKEN_PARSE);
} catch (Throwable $throwable) {
    fwrite(STDERR, 'PHP tokenization failed: ' . $throwable->getMessage() . "\n");
    exit(1);
}

$variableText = '$' . $argv[2];
$assignments = [];
$errors = [];
$stringDelimiter = null;
$inHeredoc = false;
$braceDepth = 0;
$parenthesisDepth = 0;
$bracketDepth = 0;
$alternativeOpeners = [
    T_IF => T_ENDIF,
    T_FOR => T_ENDFOR,
    T_FOREACH => T_ENDFOREACH,
    T_WHILE => T_ENDWHILE,
    T_SWITCH => T_ENDSWITCH,
    T_DECLARE => T_ENDDECLARE,
];
$alternativeBlocks = [];
$pendingAlternativeHeader = null;

for ($index = 0, $count = count($tokens); $index < $count; $index++) {
    $token = $tokens[$index];
    $id = $tokenId($token);
    $text = $tokenText($token);

    if ($id === T_START_HEREDOC) {
        $inHeredoc = true;
        continue;
    }
    if ($id === T_END_HEREDOC) {
        $inHeredoc = false;
        continue;
    }
    if (! is_array($token) && ($text === '"' || $text === '`')) {
        $stringDelimiter = $stringDelimiter === null ? $text : ($stringDelimiter === $text ? null : $stringDelimiter);
        continue;
    }
    if ($inHeredoc || $stringDelimiter !== null) {
        continue;
    }

    $headerJustOpened = false;
    $headerJustClosed = false;
    if ($braceDepth === 0 && $id !== null && array_key_exists($id, $alternativeOpeners)) {
        $pendingAlternativeHeader = [
            'end_token' => $alternativeOpeners[$id],
            'parenthesis_depth' => null,
            'ready' => false,
        ];
        $headerJustOpened = true;
    }
    if ($braceDepth === 0 && $id !== null && in_array($id, array_values($alternativeOpeners), true)) {
        $expectedEndToken = array_pop($alternativeBlocks);
        if ($expectedEndToken !== $id) {
            $errors[] = 'Alternative-syntax control block nesting is invalid';
        }
        $pendingAlternativeHeader = null;
    }

    if ($id === T_ATTRIBUTE) {
        $bracketDepth++;
    } elseif (! is_array($token)) {
        if ($text === '{') {
            $braceDepth++;
        } elseif ($text === '}') {
            $braceDepth--;
        } elseif ($text === '(') {
            $parenthesisDepth++;
            if ($pendingAlternativeHeader !== null && $pendingAlternativeHeader['parenthesis_depth'] === null) {
                $pendingAlternativeHeader['parenthesis_depth'] = $parenthesisDepth;
            }
        } elseif ($text === ')') {
            $parenthesisDepth--;
            if (
                $pendingAlternativeHeader !== null
                && $pendingAlternativeHeader['parenthesis_depth'] !== null
                && $parenthesisDepth === $pendingAlternativeHeader['parenthesis_depth'] - 1
            ) {
                $pendingAlternativeHeader['ready'] = true;
                $headerJustClosed = true;
            }
        } elseif ($text === '[') {
            $bracketDepth++;
        } elseif ($text === ']') {
            $bracketDepth--;
        }
    }

    if (
        $pendingAlternativeHeader !== null
        && ! $isTrivia($token)
        && ! $headerJustOpened
        && ! $headerJustClosed
    ) {
        if ($pendingAlternativeHeader['parenthesis_depth'] === null) {
            if ($text !== '(') {
                $pendingAlternativeHeader = null;
            }
        } elseif ($pendingAlternativeHeader['ready']) {
            if ($text === ':') {
                $alternativeBlocks[] = $pendingAlternativeHeader['end_token'];
            }
            $pendingAlternativeHeader = null;
        }
    }

    if ($id !== T_VARIABLE || $text !== $variableText) {
        continue;
    }

    $previous = $previousSignificant($tokens, $index);
    $previousId = $previous === null ? null : $tokenId($previous);
    $previousText = $previous === null ? null : $tokenText($previous);
    if ($previousText === '$' || in_array($previousId, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON], true)) {
        continue;
    }
    $isStatementBoundary = $previous === null || $previousId === T_OPEN_TAG || $previousText === ';' || $previousText === '}';
    if ($braceDepth !== 0 || $parenthesisDepth !== 0 || $bracketDepth !== 0 || $alternativeBlocks !== [] || ! $isStatementBoundary) {
        continue;
    }

    $equalsIndex = $nextSignificantIndex($tokens, $index);
    if ($equalsIndex === null || $tokenText($tokens[$equalsIndex]) !== '=') {
        continue;
    }
    $openIndex = $nextSignificantIndex($tokens, $equalsIndex);
    if ($openIndex === null || $tokenText($tokens[$openIndex]) !== '[') {
        $assignments[] = ['body_base64' => null];
        $errors[] = "Executable {$variableText} assignment must use bracket-array syntax";
        continue;
    }

    $depth = 0;
    $body = '';
    $closeIndex = null;
    for ($cursor = $openIndex; $cursor < $count; $cursor++) {
        $cursorText = $tokenText($tokens[$cursor]);
        if ($cursorText === '[') {
            $depth++;
            if ($depth > 1) {
                $body .= $cursorText;
            }
            continue;
        }
        if ($cursorText === ']') {
            $depth--;
            if ($depth === 0) {
                $closeIndex = $cursor;
                break;
            }
            $body .= $cursorText;
            continue;
        }
        $body .= $cursorText;
    }
    if ($closeIndex === null) {
        $assignments[] = ['body_base64' => null];
        $errors[] = "Executable {$variableText} array is not closed";
        continue;
    }

    $terminatorIndex = $nextSignificantIndex($tokens, $closeIndex);
    if ($terminatorIndex === null || $tokenText($tokens[$terminatorIndex]) !== ';') {
        $errors[] = "Executable {$variableText} array must end with a semicolon";
    }
    $assignments[] = ['body_base64' => base64_encode($body)];
}

echo json_encode(
    ['assignments' => $assignments, 'errors' => $errors],
    JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
);

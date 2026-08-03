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
$matchingDelimiterIndex = static function (
    array $tokens,
    int $openIndex,
    string $openText,
    string $closeText
) use ($tokenId, $tokenText): ?int {
    $depth = 0;
    for ($cursor = $openIndex, $count = count($tokens); $cursor < $count; $cursor++) {
        $cursorToken = $tokens[$cursor];
        if ($openText === '[' && $tokenId($cursorToken) === T_ATTRIBUTE) {
            $depth++;
            continue;
        }
        if (is_array($cursorToken)) {
            continue;
        }
        $cursorText = $tokenText($cursorToken);
        if ($cursorText === $openText) {
            $depth++;
        } elseif ($cursorText === $closeText) {
            $depth--;
            if ($depth === 0) {
                return $cursor;
            }
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
$globalBraceDepth = 0;
$lexicalDepth = 0;
$parenthesisDepth = 0;
$bracketDepth = 0;
$braceStack = [];
$parenthesisStack = [];
$bracketStack = [];
$pendingLexicalDeclarations = [];
$pendingArrowFunctions = [];
$arrowLexicalScopes = [];
$lexicalGlobalScopes = [];
$globalDeclarationVariableIndexes = [];
$lexicalDeclarationTokens = [T_FUNCTION, T_CLASS, T_TRAIT, T_INTERFACE];
if (defined('T_ENUM')) {
    $lexicalDeclarationTokens[] = constant('T_ENUM');
}
$assignmentOperatorTokens = [
    T_PLUS_EQUAL,
    T_MINUS_EQUAL,
    T_MUL_EQUAL,
    T_DIV_EQUAL,
    T_CONCAT_EQUAL,
    T_MOD_EQUAL,
    T_AND_EQUAL,
    T_OR_EQUAL,
    T_XOR_EQUAL,
    T_SL_EQUAL,
    T_SR_EQUAL,
];
foreach (['T_POW_EQUAL', 'T_COALESCE_EQUAL'] as $optionalAssignmentToken) {
    if (defined($optionalAssignmentToken)) {
        $assignmentOperatorTokens[] = constant($optionalAssignmentToken);
    }
}
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

    $startsArrowBody = false;
    if ($id === T_DOUBLE_ARROW && $pendingArrowFunctions !== []) {
        $pendingArrow = $pendingArrowFunctions[count($pendingArrowFunctions) - 1];
        $startsArrowBody = $parenthesisDepth === $pendingArrow['parenthesis_depth']
            && $bracketDepth === $pendingArrow['bracket_depth']
            && $braceDepth === $pendingArrow['brace_depth'];
    }

    if (! $isTrivia($token)) {
        while ($arrowLexicalScopes !== []) {
            $arrowScopeIndex = count($arrowLexicalScopes) - 1;
            $arrowScope = $arrowLexicalScopes[$arrowScopeIndex];
            $atArrowBase = $parenthesisDepth === $arrowScope['parenthesis_depth']
                && $bracketDepth === $arrowScope['bracket_depth']
                && $braceDepth === $arrowScope['brace_depth'];
            if ($atArrowBase && $text === ':' && $arrowScope['ternary_depth'] > 0) {
                $arrowLexicalScopes[$arrowScopeIndex]['ternary_depth']--;
                break;
            }
            $closesArrowContext = ! is_array($token)
                && (
                    ($text === ')' && $parenthesisDepth === $arrowScope['parenthesis_depth'])
                    || ($text === ']' && $bracketDepth === $arrowScope['bracket_depth'])
                    || ($text === '}' && $braceDepth === $arrowScope['brace_depth'])
                );
            if (
                (
                    $atArrowBase
                    && (
                        $text === ','
                        || $text === ';'
                        || $text === ':'
                        || $id === T_CLOSE_TAG
                        || ($id === T_DOUBLE_ARROW && ! $startsArrowBody)
                    )
                )
                || (
                    $closesArrowContext
                    && $parenthesisDepth === $arrowScope['parenthesis_depth']
                    && $bracketDepth === $arrowScope['bracket_depth']
                    && $braceDepth === $arrowScope['brace_depth']
                )
            ) {
                array_pop($arrowLexicalScopes);
                continue;
            }
            break;
        }
        if ($arrowLexicalScopes !== [] && ! is_array($token) && $text === '?') {
            $arrowScopeIndex = count($arrowLexicalScopes) - 1;
            $arrowScope = $arrowLexicalScopes[$arrowScopeIndex];
            if (
                $parenthesisDepth === $arrowScope['parenthesis_depth']
                && $bracketDepth === $arrowScope['bracket_depth']
                && $braceDepth === $arrowScope['brace_depth']
            ) {
                $arrowLexicalScopes[$arrowScopeIndex]['ternary_depth']++;
            }
        }
    }

    if ($id === T_FN) {
        $pendingArrowFunctions[] = [
            'parenthesis_depth' => $parenthesisDepth,
            'bracket_depth' => $bracketDepth,
            'brace_depth' => $braceDepth,
        ];
    } elseif ($startsArrowBody) {
        $pendingArrow = array_pop($pendingArrowFunctions);
        $pendingArrow['ternary_depth'] = 0;
        $arrowLexicalScopes[] = $pendingArrow;
    }

    $declarationPrevious = $previousSignificant($tokens, $index);
    $declarationPreviousId = $declarationPrevious === null ? null : $tokenId($declarationPrevious);
    $isLexicalDeclaration = $id !== null && in_array($id, $lexicalDeclarationTokens, true);
    if ($id === T_CLASS && $declarationPreviousId === T_DOUBLE_COLON) {
        $isLexicalDeclaration = false;
    }
    if ($isLexicalDeclaration) {
        $pendingLexicalDeclarations[] = $id;
    }

    $headerJustOpened = false;
    $headerJustClosed = false;
    if ($lexicalDepth === 0 && $braceDepth === 0 && $id !== null && array_key_exists($id, $alternativeOpeners)) {
        $pendingAlternativeHeader = [
            'end_token' => $alternativeOpeners[$id],
            'parenthesis_depth' => null,
            'ready' => false,
        ];
        $headerJustOpened = true;
    }
    if ($lexicalDepth === 0 && $braceDepth === 0 && $id !== null && in_array($id, array_values($alternativeOpeners), true)) {
        $expectedEndToken = array_pop($alternativeBlocks);
        if ($expectedEndToken !== $id) {
            $errors[] = 'Alternative-syntax control block nesting is invalid';
        }
        $pendingAlternativeHeader = null;
    }

    if ($id === T_ATTRIBUTE) {
        $bracketDepth++;
        $bracketStack[] = ['index' => $index, 'type' => 'attribute'];
    } elseif (! is_array($token)) {
        if ($text === '{') {
            $isLexicalBrace = $pendingLexicalDeclarations !== [];
            if ($isLexicalBrace) {
                array_pop($pendingLexicalDeclarations);
                $lexicalDepth++;
                $lexicalGlobalScopes[] = [];
                $braceStack[] = 'lexical';
            } else {
                $globalBraceDepth++;
                $braceStack[] = 'global';
            }
            $braceDepth++;
        } elseif ($text === '}') {
            $braceType = array_pop($braceStack);
            if ($braceType === 'lexical') {
                if (array_pop($lexicalGlobalScopes) === null) {
                    $errors[] = 'PHP lexical global-scope nesting is invalid';
                }
                $lexicalDepth--;
            } elseif ($braceType === 'global') {
                $globalBraceDepth--;
            } else {
                $errors[] = 'Brace nesting is invalid';
            }
            $braceDepth--;
        } elseif ($text === '(') {
            $parenthesisDepth++;
            $parenthesisStack[] = $index;
            if ($pendingAlternativeHeader !== null && $pendingAlternativeHeader['parenthesis_depth'] === null) {
                $pendingAlternativeHeader['parenthesis_depth'] = $parenthesisDepth;
            }
        } elseif ($text === ')') {
            if (array_pop($parenthesisStack) === null) {
                $errors[] = 'Parenthesis nesting is invalid';
            }
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
            $bracketStack[] = ['index' => $index, 'type' => 'bracket'];
        } elseif ($text === ']') {
            if (array_pop($bracketStack) === null) {
                $errors[] = 'Bracket nesting is invalid';
            }
            $bracketDepth--;
        } elseif ($text === ';' && $pendingLexicalDeclarations !== []) {
            array_pop($pendingLexicalDeclarations);
        }
    }

    if ($braceDepth < 0 || $globalBraceDepth < 0 || $lexicalDepth < 0 || $parenthesisDepth < 0 || $bracketDepth < 0) {
        $errors[] = 'PHP delimiter nesting is invalid';
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

    if ($id === T_GLOBAL && $lexicalDepth !== 0 && $arrowLexicalScopes === []) {
        $lexicalScopeIndex = count($lexicalGlobalScopes) - 1;
        for ($cursor = $index + 1; $cursor < $count; $cursor++) {
            $declarationToken = $tokens[$cursor];
            if (! is_array($declarationToken) && $tokenText($declarationToken) === ';') {
                break;
            }
            if ($tokenId($declarationToken) !== T_VARIABLE) {
                continue;
            }

            $globalDeclarationVariableIndexes[$cursor] = true;
            $declarationPrevious = $previousSignificant($tokens, $cursor);
            $declarationPreviousId = $declarationPrevious === null ? null : $tokenId($declarationPrevious);
            $declarationPreviousText = $declarationPrevious === null ? null : $tokenText($declarationPrevious);
            $isPlainGlobalVariable = $declarationPreviousId === T_GLOBAL || $declarationPreviousText === ',';
            if (
                $isPlainGlobalVariable
                && $tokenText($declarationToken) === $variableText
                && $lexicalScopeIndex >= 0
            ) {
                $lexicalGlobalScopes[$lexicalScopeIndex][$variableText] = true;
            }
        }
    }

    if ($id !== T_VARIABLE) {
        continue;
    }
    if (isset($globalDeclarationVariableIndexes[$index])) {
        unset($globalDeclarationVariableIndexes[$index]);
        continue;
    }

    $previous = $previousSignificant($tokens, $index);
    $previousId = $previous === null ? null : $tokenId($previous);
    $previousText = $previous === null ? null : $tokenText($previous);
    if ($previousText === '$' || in_array($previousId, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON], true)) {
        continue;
    }
    if ($text === '$GLOBALS') {
        $aliasOpenIndex = $nextSignificantIndex($tokens, $index);
        $aliasKeyIndex = $aliasOpenIndex === null ? null : $nextSignificantIndex($tokens, $aliasOpenIndex);
        $aliasCloseIndex = $aliasKeyIndex === null ? null : $nextSignificantIndex($tokens, $aliasKeyIndex);
        $aliasKeyText = $aliasKeyIndex === null ? null : $tokenText($tokens[$aliasKeyIndex]);
        if (
            $aliasOpenIndex !== null
            && $tokenText($tokens[$aliasOpenIndex]) === '['
            && $aliasKeyIndex !== null
            && in_array($aliasKeyText, ["'{$argv[2]}'", "\"{$argv[2]}\""], true)
            && $aliasCloseIndex !== null
            && $tokenText($tokens[$aliasCloseIndex]) === ']'
        ) {
            $aliasOperatorIndex = $nextSignificantIndex($tokens, $aliasCloseIndex);
            while ($aliasOperatorIndex !== null && $tokenText($tokens[$aliasOperatorIndex]) === '[') {
                $nestedCloseIndex = $matchingDelimiterIndex($tokens, $aliasOperatorIndex, '[', ']');
                if ($nestedCloseIndex === null) {
                    $errors[] = "Global \$GLOBALS['{$argv[2]}'] offset is not closed";
                    continue 2;
                }
                $aliasOperatorIndex = $nextSignificantIndex($tokens, $nestedCloseIndex);
            }
            $isUnsetArgument = false;
            if (in_array($previousText, ['(', ','], true)) {
                for ($stackIndex = count($parenthesisStack) - 1; $stackIndex >= 0; $stackIndex--) {
                    $unsetOpenIndex = $parenthesisStack[$stackIndex];
                    $unsetPrevious = $previousSignificant($tokens, $unsetOpenIndex);
                    if ($unsetPrevious !== null && $tokenId($unsetPrevious) === T_UNSET) {
                        $isUnsetArgument = true;
                        break;
                    }
                }
            }
            if ($aliasOperatorIndex !== null) {
                $aliasOperator = $tokens[$aliasOperatorIndex];
                $aliasOperatorId = $tokenId($aliasOperator);
                $aliasOperatorText = $tokenText($aliasOperator);
                if (
                    ($isUnsetArgument && in_array($aliasOperatorText, [')', ','], true))
                    || $aliasOperatorText === '='
                    || in_array($aliasOperatorId, $assignmentOperatorTokens, true)
                    || in_array($aliasOperatorId, [T_INC, T_DEC], true)
                    || in_array($previousId, [T_INC, T_DEC], true)
                ) {
                    $errors[] = "Global \$GLOBALS['{$argv[2]}'] write is not allowed";
                }
            }
        }
        continue;
    }
    if ($text !== $variableText) {
        continue;
    }

    $lexicalScopeIndex = count($lexicalGlobalScopes) - 1;
    $isExplicitGlobalVariable = $lexicalDepth !== 0
        && $lexicalScopeIndex >= 0
        && isset($lexicalGlobalScopes[$lexicalScopeIndex][$variableText]);
    if (
        $arrowLexicalScopes !== []
        || $pendingLexicalDeclarations !== []
        || $pendingArrowFunctions !== []
        || ($lexicalDepth !== 0 && ! $isExplicitGlobalVariable)
    ) {
        continue;
    }

    $isUnsetArgument = false;
    if (in_array($previousText, ['(', ','], true)) {
        for ($stackIndex = count($parenthesisStack) - 1; $stackIndex >= 0; $stackIndex--) {
            $unsetOpenIndex = $parenthesisStack[$stackIndex];
            $unsetPrevious = $previousSignificant($tokens, $unsetOpenIndex);
            if ($unsetPrevious !== null && $tokenId($unsetPrevious) === T_UNSET) {
                $isUnsetArgument = true;
                break;
            }
        }
    }

    $isGlobalDestructuringWrite = false;
    for ($stackIndex = count($bracketStack) - 1; $stackIndex >= 0; $stackIndex--) {
        $bracketEntry = $bracketStack[$stackIndex];
        if ($bracketEntry['type'] !== 'bracket') {
            continue;
        }
        $openIndex = $bracketEntry['index'];
        $openPrevious = $previousSignificant($tokens, $openIndex);
        $openPreviousId = $openPrevious === null ? null : $tokenId($openPrevious);
        $openPreviousText = $openPrevious === null ? null : $tokenText($openPrevious);
        $isIndexAccess = in_array(
            $openPreviousId,
            [T_VARIABLE, T_STRING, T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_DNUMBER],
            true
        ) || in_array($openPreviousText, [']', ')', '}'], true);
        if ($isIndexAccess) {
            continue;
        }
        $closeIndex = $matchingDelimiterIndex($tokens, $openIndex, '[', ']');
        if ($closeIndex === null) {
            $errors[] = "Global {$variableText} destructuring bracket is not closed";
            $isGlobalDestructuringWrite = true;
            break;
        }
        $afterCloseIndex = $nextSignificantIndex($tokens, $closeIndex);
        if ($afterCloseIndex !== null && $tokenText($tokens[$afterCloseIndex]) === '=') {
            $isGlobalDestructuringWrite = true;
            break;
        }
    }
    if (! $isGlobalDestructuringWrite) {
        for ($stackIndex = count($parenthesisStack) - 1; $stackIndex >= 0; $stackIndex--) {
            $openIndex = $parenthesisStack[$stackIndex];
            $openPrevious = $previousSignificant($tokens, $openIndex);
            if ($openPrevious === null || $tokenId($openPrevious) !== T_LIST) {
                continue;
            }
            $closeIndex = $matchingDelimiterIndex($tokens, $openIndex, '(', ')');
            if ($closeIndex === null) {
                $errors[] = "Global {$variableText} list destructuring parenthesis is not closed";
                $isGlobalDestructuringWrite = true;
                break;
            }
            $afterCloseIndex = $nextSignificantIndex($tokens, $closeIndex);
            if ($afterCloseIndex !== null && $tokenText($tokens[$afterCloseIndex]) === '=') {
                $isGlobalDestructuringWrite = true;
                break;
            }
        }
    }
    if ($isGlobalDestructuringWrite) {
        $errors[] = "Global {$variableText} destructuring write is not allowed";
        continue;
    }

    if ($isUnsetArgument) {
        $unsetOperatorIndex = $nextSignificantIndex($tokens, $index);
        $hasUnsetOffset = false;
        while ($unsetOperatorIndex !== null && $tokenText($tokens[$unsetOperatorIndex]) === '[') {
            $hasUnsetOffset = true;
            $unsetCloseIndex = $matchingDelimiterIndex($tokens, $unsetOperatorIndex, '[', ']');
            if ($unsetCloseIndex === null) {
                $errors[] = "Global {$variableText} unset array offset is not closed";
                continue 2;
            }
            $unsetOperatorIndex = $nextSignificantIndex($tokens, $unsetCloseIndex);
        }
        if (
            $unsetOperatorIndex !== null
            && in_array($tokenText($tokens[$unsetOperatorIndex]), [')', ','], true)
        ) {
            $unsetTarget = $hasUnsetOffset ? 'array-offset unset' : 'unset';
            $errors[] = "Global {$variableText} {$unsetTarget} is not allowed";
            continue;
        }
    }

    $operatorIndex = $nextSignificantIndex($tokens, $index);
    if ($operatorIndex === null) {
        continue;
    }

    $operatorToken = $tokens[$operatorIndex];
    $operatorId = $tokenId($operatorToken);
    $operatorText = $tokenText($operatorToken);
    if (in_array($operatorId, [T_INC, T_DEC], true) || in_array($previousId, [T_INC, T_DEC], true)) {
        $errors[] = "Global {$variableText} increment/decrement is not allowed";
        continue;
    }

    if ($operatorText === '[') {
        while ($operatorText === '[') {
            $offsetDepth = 0;
            $offsetCloseIndex = null;
            for ($cursor = $operatorIndex; $cursor < $count; $cursor++) {
                if (is_array($tokens[$cursor])) {
                    continue;
                }
                $cursorText = $tokenText($tokens[$cursor]);
                if ($cursorText === '[') {
                    $offsetDepth++;
                } elseif ($cursorText === ']') {
                    $offsetDepth--;
                    if ($offsetDepth === 0) {
                        $offsetCloseIndex = $cursor;
                        break;
                    }
                }
            }
            if ($offsetCloseIndex === null) {
                $errors[] = "Global {$variableText} array offset is not closed";
                continue 2;
            }
            $operatorIndex = $nextSignificantIndex($tokens, $offsetCloseIndex);
            if ($operatorIndex === null) {
                continue 2;
            }
            $operatorToken = $tokens[$operatorIndex];
            $operatorId = $tokenId($operatorToken);
            $operatorText = $tokenText($operatorToken);
        }
        if ($operatorText === '=' || in_array($operatorId, $assignmentOperatorTokens, true) || in_array($operatorId, [T_INC, T_DEC], true)) {
            $errors[] = "Global {$variableText} array-offset write is not allowed";
        }
        continue;
    }

    if ($operatorText !== '=' && ! in_array($operatorId, $assignmentOperatorTokens, true)) {
        continue;
    }

    $isStatementBoundary = $previous === null || $previousId === T_OPEN_TAG || $previousText === ';' || $previousText === '}';
    $isCanonicalAssignment = $operatorText === '='
        && $lexicalDepth === 0
        && $globalBraceDepth === 0
        && $parenthesisDepth === 0
        && $bracketDepth === 0
        && $alternativeBlocks === []
        && $isStatementBoundary;
    if (! $isCanonicalAssignment) {
        $errors[] = "Global {$variableText} write must be one standalone top-level direct assignment";
        continue;
    }

    $openIndex = $nextSignificantIndex($tokens, $operatorIndex);
    if ($openIndex === null || $tokenText($tokens[$openIndex]) !== '[') {
        $assignments[] = ['body_base64' => null];
        $errors[] = "Executable {$variableText} assignment must use bracket-array syntax";
        continue;
    }

    $depth = 0;
    $body = '';
    $closeIndex = null;
    for ($cursor = $openIndex; $cursor < $count; $cursor++) {
        $cursorToken = $tokens[$cursor];
        $cursorText = $tokenText($cursorToken);
        if (! is_array($cursorToken) && $cursorText === '[') {
            $depth++;
            if ($depth > 1) {
                $body .= $cursorText;
            }
            continue;
        }
        if (! is_array($cursorToken) && $cursorText === ']') {
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

if (
    $braceDepth !== 0
    || $globalBraceDepth !== 0
    || $lexicalDepth !== 0
    || $parenthesisDepth !== 0
    || $bracketDepth !== 0
    || $braceStack !== []
    || $parenthesisStack !== []
    || $bracketStack !== []
    || $pendingLexicalDeclarations !== []
    || $pendingArrowFunctions !== []
    || $arrowLexicalScopes !== []
    || $lexicalGlobalScopes !== []
    || $globalDeclarationVariableIndexes !== []
    || $alternativeBlocks !== []
    || $pendingAlternativeHeader !== null
) {
    $errors[] = 'PHP lexical or delimiter nesting did not close cleanly';
}

echo json_encode(
    ['assignments' => $assignments, 'errors' => $errors],
    JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
);

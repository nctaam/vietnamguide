param(
    [string]$RepoRootOverride = '',
    [string]$PhpExecutable = ''
)

$ErrorActionPreference = 'Stop'
$RepoRoot = if ($RepoRootOverride) { $RepoRootOverride } else { Split-Path -Parent $PSScriptRoot }
$ThemeRoot = 'wordpress/wp-content/themes/vietnamguide-premium'
$HomepageCss = "$ThemeRoot/assets/css/homepage.css"
$GuideCss = "$ThemeRoot/assets/css/guide-experience.css"
$GuideJs = "$ThemeRoot/assets/js/guide-experience.js"
$GuideJsRuntimeVerifier = 'ops/verify-guide-experience-js-runtime.js'
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

function Require-Matches {
    param([string]$RelativePath, [string]$Pattern, [string]$Description)
    $Content = Get-RepoContent $RelativePath
    if ($null -ne $Content -and -not [regex]::IsMatch($Content, $Pattern)) {
        $Failures.Add("Missing pattern in ${RelativePath}: $Description")
    }
}

function Require-NotMatches {
    param([string]$RelativePath, [string]$Pattern, [string]$Description)
    $Content = Get-RepoContent $RelativePath
    if ($null -ne $Content -and [regex]::IsMatch($Content, $Pattern)) {
        $Failures.Add("Unexpected pattern in ${RelativePath}: $Description")
    }
}

function Get-CssBlockContent {
    param([string]$RelativePath, [string]$Marker)
    $Content = Get-RepoContent $RelativePath
    if ($null -eq $Content) { return $null }

    $MarkerIndex = $Content.IndexOf($Marker, [System.StringComparison]::Ordinal)
    if ($MarkerIndex -lt 0) { return $null }

    $OpenBrace = $Content.IndexOf('{', $MarkerIndex)
    if ($OpenBrace -lt 0) { return $null }

    $Depth = 0
    for ($Index = $OpenBrace; $Index -lt $Content.Length; $Index++) {
        if ($Content[$Index] -eq '{') {
            $Depth++
            continue
        }

        if ($Content[$Index] -eq '}') {
            $Depth--
            if ($Depth -eq 0) {
                return $Content.Substring($OpenBrace + 1, $Index - $OpenBrace - 1)
            }
        }
    }

    return $null
}

function Require-CssBlockContains {
    param([string]$RelativePath, [string]$Marker, [string]$Needle)
    $BlockContent = Get-CssBlockContent $RelativePath $Marker
    if ($null -eq $BlockContent) {
        $Failures.Add("Missing CSS block in ${RelativePath}: $Marker")
    } elseif (-not $BlockContent.Contains($Needle)) {
        $Failures.Add("Missing substring in CSS block ${Marker}: $Needle")
    }
}

function Get-UnscopedGuideCssSelectors {
    param([string]$Content)
    $UnscopedSelectors = [System.Collections.Generic.List[string]]::new()
    $SelectorMatches = [regex]::Matches($Content, '(?ms)(?:^|[{}])\s*([^{};][^{};]*)\{')
    foreach ($SelectorMatch in $SelectorMatches) {
        foreach ($SelectorValue in $SelectorMatch.Groups[1].Value.Split(',')) {
            $Selector = $SelectorValue.Trim()
            if ($Selector -eq '' -or $Selector.StartsWith('@')) { continue }
            if ($Selector -match '^(from|to|(?:[0-9]+(?:\.[0-9]+)?|\.[0-9]+)%)$') { continue }
            if ($Selector -notmatch '^\.vg-[A-Za-z0-9_-]+') {
                $UnscopedSelectors.Add($Selector)
            }
        }
    }

    return $UnscopedSelectors.ToArray()
}

function Require-GuideCssScoped {
    param([string]$RelativePath)
    $Content = Get-RepoContent $RelativePath
    if ($null -eq $Content) { return }

    foreach ($Selector in (Get-UnscopedGuideCssSelectors $Content)) {
        $Failures.Add("Unscoped guide CSS selector in ${RelativePath}: $Selector")
    }
}

function Require-GuideCssScopeSelfTest {
    $KeyframeFixture = '@keyframes vg-scope-check { from { opacity: 0; } 50%, .5% { opacity: .5; } to { opacity: 1; } } .vg-scope-check { opacity: 1; }'
    $KeyframeFailures = @(Get-UnscopedGuideCssSelectors $KeyframeFixture)
    if ($KeyframeFailures.Count -ne 0) {
        $Failures.Add("Guide CSS scope helper rejected keyframe selectors: $($KeyframeFailures -join ', ')")
    }

    $LeakFixture = 'body { overflow-x: hidden; } .vg-scope-check { display: block; }'
    $LeakFailures = @(Get-UnscopedGuideCssSelectors $LeakFixture)
    if ($LeakFailures -notcontains 'body') {
        $Failures.Add('Guide CSS scope helper failed to reject an unscoped body selector')
    }
}

function Get-FunctionContent {
    param([string]$RelativePath, [string]$FunctionName)
    $Content = Get-RepoContent $RelativePath
    if ($null -eq $Content) { return $null }

    $StartMarker = "function $FunctionName"
    $Start = $Content.IndexOf($StartMarker, [System.StringComparison]::Ordinal)
    if ($Start -lt 0) { return $null }

    $SearchStart = $Start + $StartMarker.Length
    $Remaining = $Content.Substring($SearchStart)
    $NextFunction = [regex]::Match($Remaining, '(?m)^function\s+')
    $End = if ($NextFunction.Success) { $SearchStart + $NextFunction.Index } else { $Content.Length }

    return $Content.Substring($Start, $End - $Start)
}

function Require-FunctionContains {
    param([string]$RelativePath, [string]$FunctionName, [string]$Needle)
    $FunctionContent = Get-FunctionContent $RelativePath $FunctionName
    if ($null -ne $FunctionContent -and -not $FunctionContent.Contains($Needle)) {
        $Failures.Add("Missing substring in ${FunctionName}(): $Needle")
    }
}

function Require-FunctionNotContains {
    param([string]$RelativePath, [string]$FunctionName, [string]$Needle)
    $FunctionContent = Get-FunctionContent $RelativePath $FunctionName
    if ($null -ne $FunctionContent -and $FunctionContent.Contains($Needle)) {
        $Failures.Add("Unexpected substring in ${FunctionName}(): $Needle")
    }
}

function Require-FunctionMatches {
    param([string]$RelativePath, [string]$FunctionName, [string]$Pattern, [string]$Description)
    $FunctionContent = Get-FunctionContent $RelativePath $FunctionName
    if ($null -ne $FunctionContent -and -not [regex]::IsMatch($FunctionContent, $Pattern)) {
        $Failures.Add("Missing pattern in ${FunctionName}(): $Description")
    }
}

function Require-FunctionOrder {
    param([string]$RelativePath, [string]$FunctionName, [string]$First, [string]$Second)
    $FunctionContent = Get-FunctionContent $RelativePath $FunctionName
    if ($null -eq $FunctionContent) { return }

    $FirstIndex = $FunctionContent.IndexOf($First, [System.StringComparison]::Ordinal)
    $SecondIndex = $FunctionContent.IndexOf($Second, [System.StringComparison]::Ordinal)
    if ($FirstIndex -lt 0 -or $SecondIndex -lt 0 -or $FirstIndex -ge $SecondIndex) {
        $Failures.Add("Expected order in ${FunctionName}(): $First before $Second")
    }
}

function Require-ExactSet {
    param([string]$Label, [string[]]$Actual, [string[]]$Expected)
    if ($Actual.Count -ne $Expected.Count) {
        $Failures.Add("Expected $Label count $($Expected.Count), found $($Actual.Count)")
    }

    foreach ($ExpectedItem in $Expected) {
        if ($Actual -notcontains $ExpectedItem) {
            $Failures.Add("Missing ${Label}: $ExpectedItem")
        }
    }

    foreach ($ActualItem in $Actual) {
        if ($Expected -notcontains $ActualItem) {
            $Failures.Add("Unexpected ${Label}: $ActualItem")
        }
    }
}

function Require-ExactOrdinalSet {
    param([string]$Label, [string[]]$Actual, [string[]]$Expected)

    $ActualSet = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::Ordinal)
    foreach ($ActualItem in $Actual) {
        if (-not $ActualSet.Add($ActualItem)) {
            $Failures.Add("Duplicate ${Label}: $ActualItem")
        }
    }

    $ExpectedSet = [System.Collections.Generic.HashSet[string]]::new($Expected, [System.StringComparer]::Ordinal)
    if ($Actual.Count -ne $Expected.Count) {
        $Failures.Add("Expected $Label count $($Expected.Count), found $($Actual.Count)")
    }
    foreach ($ExpectedItem in $Expected) {
        if (-not $ActualSet.Contains($ExpectedItem)) {
            $Failures.Add("Missing ${Label}: $ExpectedItem")
        }
    }
    foreach ($ActualItem in $Actual) {
        if (-not $ExpectedSet.Contains($ActualItem)) {
            $Failures.Add("Unexpected ${Label}: $ActualItem")
        }
    }
}

function Get-TopLevelLiteralStringArray {
    param(
        [System.Management.Automation.Language.ScriptBlockAst]$Ast,
        [string]$VariableName,
        [string]$Label
    )

    $Writes = @($Ast.FindAll({
        param($Node)

        $TargetExpression = if ($Node -is [System.Management.Automation.Language.AssignmentStatementAst]) {
            $Node.Left
        } elseif (
            $Node -is [System.Management.Automation.Language.UnaryExpressionAst] -and
            $Node.TokenKind -in @(
                [System.Management.Automation.Language.TokenKind]::PlusPlus,
                [System.Management.Automation.Language.TokenKind]::MinusMinus,
                [System.Management.Automation.Language.TokenKind]::PostfixPlusPlus,
                [System.Management.Automation.Language.TokenKind]::PostfixMinusMinus
            )
        ) {
            $Node.Child
        } elseif ($Node -is [System.Management.Automation.Language.ForEachStatementAst]) {
            $Node.Variable
        } else {
            return $false
        }

        $TargetExpressions = [System.Collections.Generic.Stack[object]]::new()
        $TargetExpressions.Push([pscustomobject]@{
            Expression = $TargetExpression
            Indexed = $false
        })
        $MatchesTarget = $false
        $HasScriptScopedTarget = $false
        $HasUnqualifiedIndexedTarget = $false
        while ($TargetExpressions.Count -ne 0) {
            $TargetCandidate = $TargetExpressions.Pop()
            $Candidate = $TargetCandidate.Expression
            if ($Candidate -is [System.Management.Automation.Language.IndexExpressionAst]) {
                $TargetExpressions.Push([pscustomobject]@{
                    Expression = $Candidate.Target
                    Indexed = $true
                })
                continue
            }
            if ($Candidate -is [System.Management.Automation.Language.ArrayLiteralAst]) {
                foreach ($Element in $Candidate.Elements) {
                    $TargetExpressions.Push([pscustomobject]@{
                        Expression = $Element
                        Indexed = $TargetCandidate.Indexed
                    })
                }
                continue
            }
            if ($Candidate -isnot [System.Management.Automation.Language.VariableExpressionAst]) {
                continue
            }

            $UserPath = $Candidate.VariablePath.UserPath
            if ($UserPath -ieq $VariableName -or $UserPath -ilike "*:$VariableName") {
                $MatchesTarget = $true
                if ($UserPath.StartsWith('script:', [System.StringComparison]::OrdinalIgnoreCase)) {
                    $HasScriptScopedTarget = $true
                }
                if ($UserPath -ieq $VariableName -and $TargetCandidate.Indexed) {
                    $HasUnqualifiedIndexedTarget = $true
                }
            }
        }
        if (-not $MatchesTarget) { return $false }

        $LexicalContainer = $null
        $Ancestor = $Node.Parent
        while ($null -ne $Ancestor -and $Ancestor -ne $Ast) {
            if (
                $null -eq $LexicalContainer -and
                (
                    $Ancestor -is [System.Management.Automation.Language.FunctionDefinitionAst] -or
                    $Ancestor -is [System.Management.Automation.Language.ScriptBlockExpressionAst]
                )
            ) {
                $LexicalContainer = $Ancestor
            }
            $Ancestor = $Ancestor.Parent
        }
        if ($Ancestor -ne $Ast) { return $false }

        if (-not $HasScriptScopedTarget -and $null -ne $LexicalContainer) {
            if (-not $HasUnqualifiedIndexedTarget) { return $false }

            $Parameters = if ($LexicalContainer -is [System.Management.Automation.Language.FunctionDefinitionAst]) {
                @($LexicalContainer.Parameters) + @($LexicalContainer.Body.ParamBlock.Parameters)
            } else {
                @($LexicalContainer.ScriptBlock.ParamBlock.Parameters)
            }
            foreach ($Parameter in $Parameters) {
                if ($Parameter.Name.VariablePath.UserPath -ieq $VariableName) {
                    return $false
                }
            }

            $BindingSearchRoot = if ($LexicalContainer -is [System.Management.Automation.Language.FunctionDefinitionAst]) {
                $LexicalContainer.Body
            } else {
                $LexicalContainer.ScriptBlock
            }
            $EarlierBareBindings = @($BindingSearchRoot.FindAll({
                param($BindingNode)
                $BindingNode -is [System.Management.Automation.Language.AssignmentStatementAst] -and
                $BindingNode.Extent.StartOffset -lt $Node.Extent.StartOffset -and
                $BindingNode.Left -is [System.Management.Automation.Language.VariableExpressionAst] -and
                $BindingNode.Left.VariablePath.UserPath -ieq $VariableName
            }, $true))
            foreach ($Binding in $EarlierBareBindings) {
                $BindingContainer = $null
                $BindingAncestor = $Binding.Parent
                while ($null -ne $BindingAncestor -and $null -eq $BindingContainer) {
                    if (
                        $BindingAncestor -is [System.Management.Automation.Language.FunctionDefinitionAst] -or
                        $BindingAncestor -is [System.Management.Automation.Language.ScriptBlockExpressionAst]
                    ) {
                        $BindingContainer = $BindingAncestor
                    }
                    $BindingAncestor = $BindingAncestor.Parent
                }
                if ($BindingContainer -ne $LexicalContainer) { continue }

                $IsDirectContainerBinding =
                    $Binding.Parent -is [System.Management.Automation.Language.NamedBlockAst] -and
                    $Binding.Parent.Parent -eq $BindingSearchRoot

                $IsGuaranteedTrueBranchBinding = $false
                if ($Binding.Parent -is [System.Management.Automation.Language.StatementBlockAst]) {
                    $IfStatement = $Binding.Parent.Parent
                    if (
                        $IfStatement -is [System.Management.Automation.Language.IfStatementAst] -and
                        $IfStatement.Parent -is [System.Management.Automation.Language.NamedBlockAst] -and
                        $IfStatement.Parent.Parent -eq $BindingSearchRoot -and
                        $IfStatement.Clauses.Count -ne 0 -and
                        [object]::ReferenceEquals($IfStatement.Clauses[0].Item2, $Binding.Parent) -and
                        $IfStatement.Clauses[0].Item1.Extent.Text.Trim() -ceq '$true' -and
                        $Binding.Parent.Statements.Count -eq 1 -and
                        [object]::ReferenceEquals($Binding.Parent.Statements[0], $Binding)
                    ) {
                        $IsGuaranteedTrueBranchBinding = $true
                    }
                }

                if ($IsDirectContainerBinding -or $IsGuaranteedTrueBranchBinding) {
                    return $false
                }
            }
        }
        return $true
    }, $true))
    if ($Writes.Count -ne 1) {
        $Failures.Add("Expected exactly one root-executable $Label assignment/write, found $($Writes.Count)")
        return @()
    }

    $Assignment = $Writes[0]
    if ($Assignment -isnot [System.Management.Automation.Language.AssignmentStatementAst]) {
        $Failures.Add("$Label must use one top-level simple `$$VariableName = assignment")
        return @()
    }
    $IsTopLevel = $Assignment.Parent -is [System.Management.Automation.Language.NamedBlockAst] -and $Assignment.Parent.Parent -eq $Ast
    $IsExactVariable = $Assignment.Left -is [System.Management.Automation.Language.VariableExpressionAst] -and $Assignment.Left.VariablePath.UserPath -ceq $VariableName
    if (-not $IsTopLevel -or -not $IsExactVariable -or $Assignment.Operator -ne [System.Management.Automation.Language.TokenKind]::Equals) {
        $Failures.Add("$Label must use one top-level simple `$$VariableName = assignment")
        return @()
    }

    if (
        $Assignment.Right -isnot [System.Management.Automation.Language.CommandExpressionAst] -or
        $Assignment.Right.Expression -isnot [System.Management.Automation.Language.ArrayExpressionAst]
    ) {
        $Failures.Add("$Label assignment must use a literal @() array")
        return @()
    }

    $Values = [System.Collections.Generic.List[string]]::new()
    $StatementBlock = $Assignment.Right.Expression.SubExpression
    if ($StatementBlock.Traps.Count -ne 0) {
        $Failures.Add("$Label assignment contains non-literal statements")
        return @()
    }
    foreach ($Statement in $StatementBlock.Statements) {
        if ($Statement -isnot [System.Management.Automation.Language.PipelineAst] -or $Statement.PipelineElements.Count -ne 1) {
            $Failures.Add("$Label assignment contains a pipeline or command")
            return @()
        }

        $Element = $Statement.PipelineElements[0]
        if ($Element -isnot [System.Management.Automation.Language.CommandExpressionAst] -or $Element.Redirections.Count -ne 0) {
            $Failures.Add("$Label assignment contains a command or redirection")
            return @()
        }

        $Expression = $Element.Expression
        $LiteralExpressions = if ($Expression -is [System.Management.Automation.Language.ArrayLiteralAst]) {
            @($Expression.Elements)
        } else {
            @($Expression)
        }
        foreach ($LiteralExpression in $LiteralExpressions) {
            if ($LiteralExpression -isnot [System.Management.Automation.Language.StringConstantExpressionAst]) {
                $Failures.Add("$Label assignment contains a non-literal or expandable value")
                return @()
            }
            if ($LiteralExpression.StringConstantType -notin @(
                [System.Management.Automation.Language.StringConstantType]::SingleQuoted,
                [System.Management.Automation.Language.StringConstantType]::DoubleQuoted
            )) {
                $Failures.Add("$Label assignment contains a non-literal string")
                return @()
            }
            $Values.Add($LiteralExpression.Value)
        }
    }

    $Seen = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::Ordinal)
    foreach ($Value in $Values) {
        if (-not $Seen.Add($Value)) {
            $Failures.Add("Duplicate ${Label} value: $Value")
        }
    }
    return $Values.ToArray()
}

function Resolve-PhpExecutable {
    param([string]$Candidate)

    foreach ($Value in @($Candidate, $env:VG_PHP_CLI, 'php')) {
        if ([string]::IsNullOrWhiteSpace($Value)) { continue }
        if (Test-Path -LiteralPath $Value -PathType Leaf) { return [System.IO.Path]::GetFullPath($Value) }
        $Command = Get-Command $Value -ErrorAction SilentlyContinue
        if ($null -ne $Command) { return $Command.Source }
    }
    return $null
}

function Invoke-PhpVariableArrayTokenizer {
    param([string]$PhpPath, [string]$HelperPath, [string]$SourcePath, [string]$VariableName)

    $PreviousErrorActionPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $Output = & $PhpPath $HelperPath $SourcePath $VariableName 2>&1
        $ExitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $PreviousErrorActionPreference
    }
    if ($ExitCode -ne 0) {
        return [pscustomobject]@{ Assignments = @(); Errors = @("PHP tokenizer failed: $($Output -join ' ')") }
    }

    try {
        $Decoded = ($Output -join "`n") | ConvertFrom-Json
        $Utf8Strict = [System.Text.UTF8Encoding]::new($false, $true)
        $Assignments = @($Decoded.assignments | ForEach-Object {
            $Body = if ($null -eq $_.body_base64) { $null } else { $Utf8Strict.GetString([Convert]::FromBase64String([string]$_.body_base64)) }
            [pscustomobject]@{ Body = $Body }
        })
        return [pscustomobject]@{ Assignments = $Assignments; Errors = @($Decoded.errors) }
    } catch {
        return [pscustomobject]@{ Assignments = @(); Errors = @("PHP tokenizer returned invalid JSON: $($_.Exception.Message)") }
    }
}

function Test-PhpVariableArrayTokenizerFixtures {
    param([string]$PhpPath, [string]$HelperPath)

    $FixtureRoot = Join-Path ([System.IO.Path]::GetTempPath()) ('vietnamguide-php-tokenizer-' + [guid]::NewGuid().ToString('N'))
    try {
        $null = New-Item -ItemType Directory -Path $FixtureRoot
        $FixturePath = Join-Path $FixtureRoot 'fixture.php'
        $Fixture = @'
<?php
$heredoc = <<<TEXT
    $pilot_types = [
        'heredoc' => 'ignored',
    ];
    TEXT;
$nowdocs = [
    <<<'ROW'
    $pilot_types = [
        'nowdoc' => 'ignored',
    ];
    ROW,
];
$command = `echo "$pilot_types = [];"`;
$single_quoted = '$pilot_types = [];';
$double_quoted = "read $pilot_types without assigning it";
// $pilot_types = ['line-comment' => 'ignored'];
# $pilot_types = ['hash-comment' => 'ignored'];
/*
$pilot_types = [
    'block-comment' => 'ignored',
];
*/
$lookup = [$pilot_types => 'ignored'];
$read = $pilot_types;
$call = is_array($pilot_types);
$$pilot_types = [];
$object->$pilot_types = [];
class PilotInventoryFixture {
    public $pilot_types = [
        'property' => 'ignored',
    ];

    public function write_local_inventory(): void {
        $pilot_types = [
            'class-method-local' => 'ignored',
        ];
        ($pilot_types = [
            'class-method-parenthesized' => 'ignored',
        ]);
        $pilot_types['class-method-offset'] = 'ignored';
        $pilot_types += ['class-method-compound' => 'ignored'];
    }
}
function pilot_inventory_fixture($pilot_types = []) {
    global $fixture_other_inventory;
    $pilot_types = [
        'nested' => 'ignored',
    ];
    ($pilot_types = [
        'function-parenthesized' => 'ignored',
    ]);
    $pilot_types['function-offset'] = 'ignored';
    $pilot_types += ['function-compound' => 'ignored'];
    [$pilot_types] = [['function-short-destructure' => 'ignored']];
    [[$pilot_types]] = [[['function-nested-short-destructure' => 'ignored']]];
    list($pilot_types) = [['function-list-destructure' => 'ignored']];
    unset($pilot_types['function-unset-offset']);
    unset($pilot_types);
    return $pilot_types = [
        'return' => 'ignored',
    ];
}
function pilot_inventory_unrelated_variable_global_fixture($pilot_types = []) {
    global $$fixture_other_inventory;
    $pilot_types += ['unrelated-variable-global-local' => 'ignored'];
}
function pilot_inventory_target_variable_global_fixture($pilot_types = []) {
    global $$pilot_types;
    $pilot_types += ['target-variable-global-local' => 'ignored'];
}
function pilot_inventory_braced_variable_global_fixture($pilot_types = []) {
    global ${$pilot_types};
    $pilot_types += ['braced-variable-global-local' => 'ignored'];
}
$closure = static function () use ($pilot_types): void {
    $pilot_types = [
        'closure-local' => 'ignored',
    ];
    ($pilot_types = [
        'closure-parenthesized' => 'ignored',
    ]);
    $pilot_types['closure-offset'] = 'ignored';
    $pilot_types += ['closure-compound' => 'ignored'];
    unset($pilot_types['closure-unset-offset']);
    unset($pilot_types);
};
$arrow = static fn(array $input, $pilot_types = []): array => [
    'direct' => ($pilot_types = [
        'arrow-direct' => 'ignored',
    ]),
    'offset' => ($pilot_types['arrow-offset'] = ($input['value'] ?? 'ignored')),
    'compound' => ($pilot_types += [
        'arrow-compound' => ['nested' => 'ignored'],
    ]),
];
$expression_read = ($pilot_types);
if (true):
    $if_marker = true;
    $if_read = $pilot_types;
endif;
foreach ([1] as $fixture_item):
    $foreach_marker = true;
    $foreach_read = $pilot_types;
endforeach;
if ((static function () {
    if (true):
        return true;
    endif;
})()):
    $closure_header_marker = true;
    $closure_header_read = $pilot_types;
endif;
$pilot_types = [
    'executable' => 'captured',
];
'@
        [System.IO.File]::WriteAllText($FixturePath, $Fixture, [System.Text.UTF8Encoding]::new($false))
        $Result = Invoke-PhpVariableArrayTokenizer $PhpPath $HelperPath $FixturePath 'pilot_types'
        if ($Result.Errors.Count -ne 0) {
            $script:Failures.Add("PHP tokenizer fixture errors: $($Result.Errors -join ', ')")
        } elseif ($Result.Assignments.Count -ne 1 -or $null -eq $Result.Assignments[0].Body -or $Result.Assignments[0].Body.Trim() -ne "'executable' => 'captured',") {
            $script:Failures.Add('PHP tokenizer fixture did not isolate the one executable direct pilot_types assignment')
        }
    } finally {
        if (Test-Path -LiteralPath $FixtureRoot -PathType Container) {
            Remove-Item -LiteralPath $FixtureRoot -Recurse -Force
        }
    }
}

function Test-PublicNonPilotInventoryReorderFixture {
    $Fixture = @'
$NonPilotPaths = @(
    'plan/transport-within-vietnam'
    'destinations/hanoi-travel-guide'
    'plan/sim-esim-vietnam'
    'compare/da-nang-vs-hoi-an'
)
'@
    $Tokens = $null
    $ParseErrors = $null
    $Ast = [System.Management.Automation.Language.Parser]::ParseInput($Fixture, [ref]$Tokens, [ref]$ParseErrors)
    if ($ParseErrors.Count -ne 0) {
        $script:Failures.Add("Non-pilot reorder fixture did not parse: $($ParseErrors[0].Message)")
        return
    }

    $NonPilotPaths = @(Get-TopLevelLiteralStringArray $Ast 'NonPilotPaths' 'public non-pilot reorder fixture path inventory')
    Require-ExactOrdinalSet 'public non-pilot reorder fixture path' $NonPilotPaths @(
        'destinations/hanoi-travel-guide'
        'compare/da-nang-vs-hoi-an'
        'plan/sim-esim-vietnam'
        'plan/transport-within-vietnam'
    )
}

function Test-PublicPilotInventoryLocalAssignmentFixture {
    $Fixture = @'
function Set-LocalPilotInventory {
    $PilotPaths = @(
        'local/function-decoy'
    )
    $PilotPaths[0] = 'local/function-indexed-decoy'
}
function Set-ParameterPilotInventory {
    param([string[]]$PilotPaths)
    $PilotPaths[0] = 'local/function-parameter-indexed-decoy'
}
& {
    $PilotPaths = @(
        'local/scriptblock-decoy'
    )
    $PilotPaths[0] = 'local/scriptblock-indexed-decoy'
    $PilotPaths += @(
        'local/scriptblock-compound-decoy'
    )
}
& {
    param([string[]]$PilotPaths)
    $PilotPaths[0] = 'local/scriptblock-parameter-indexed-decoy'
} @('local/scriptblock-parameter-decoy')
$PilotPaths = @(
    'top-level/captured'
)
'@
    $Tokens = $null
    $ParseErrors = $null
    $Ast = [System.Management.Automation.Language.Parser]::ParseInput($Fixture, [ref]$Tokens, [ref]$ParseErrors)
    if ($ParseErrors.Count -ne 0) {
        $script:Failures.Add("Pilot local-assignment fixture did not parse: $($ParseErrors[0].Message)")
        return
    }

    $PilotPaths = @(Get-TopLevelLiteralStringArray $Ast 'PilotPaths' 'public pilot local-assignment fixture inventory')
    if ($PilotPaths.Count -ne 1 -or $PilotPaths[0] -cne 'top-level/captured') {
        $script:Failures.Add("Pilot local-assignment fixture did not isolate the top-level inventory; found: $($PilotPaths -join ', ')")
    }
}

function Test-PublicPilotInventoryFilterLocalAssignmentFixture {
    $Fixture = @'
filter Set-LocalPilotInventoryFilter {
    $PilotPaths = @(
        'local/filter-decoy'
    )
    $PilotPaths[0] = 'local/filter-indexed-decoy'
    $PilotPaths += @(
        'local/filter-compound-decoy'
    )
}
$PilotPaths = @(
    'top-level/captured'
)
'@
    $Tokens = $null
    $ParseErrors = $null
    $Ast = [System.Management.Automation.Language.Parser]::ParseInput($Fixture, [ref]$Tokens, [ref]$ParseErrors)
    if ($ParseErrors.Count -ne 0) {
        $script:Failures.Add("Pilot filter-local fixture did not parse: $($ParseErrors[0].Message)")
        return
    }

    $PilotPaths = @(Get-TopLevelLiteralStringArray $Ast 'PilotPaths' 'public pilot filter-local fixture inventory')
    if ($PilotPaths.Count -ne 1 -or $PilotPaths[0] -cne 'top-level/captured') {
        $script:Failures.Add("Pilot filter-local fixture did not isolate the top-level inventory; found: $($PilotPaths -join ', ')")
    }
}

function Test-PublicPilotInventoryGuaranteedConditionalLocalAssignmentFixture {
    $Fixture = @'
function Set-GuaranteedConditionalLocalPilotInventory {
    if ($true) {
        $PilotPaths = @(
            'local/guaranteed-conditional-decoy'
        )
    }
    $PilotPaths[0] = 'local/guaranteed-conditional-indexed-decoy'
}
$PilotPaths = @(
    'top-level/captured'
)
Set-GuaranteedConditionalLocalPilotInventory
'@
    $Tokens = $null
    $ParseErrors = $null
    $Ast = [System.Management.Automation.Language.Parser]::ParseInput($Fixture, [ref]$Tokens, [ref]$ParseErrors)
    if ($ParseErrors.Count -ne 0) {
        $script:Failures.Add("Pilot guaranteed-conditional local-assignment fixture did not parse: $($ParseErrors[0].Message)")
        return
    }

    $PilotPaths = @(Get-TopLevelLiteralStringArray $Ast 'PilotPaths' 'public pilot guaranteed-conditional local-assignment fixture inventory')
    if ($PilotPaths.Count -ne 1 -or $PilotPaths[0] -cne 'top-level/captured') {
        $script:Failures.Add("Pilot guaranteed-conditional local-assignment fixture did not isolate the top-level inventory; found: $($PilotPaths -join ', ')")
    }
}

$Routing = "$ThemeRoot/inc/guide-routing.php"
$ContentProvider = "$ThemeRoot/inc/guide-content.php"
$ContextProvider = "$ThemeRoot/inc/guide-context.php"
$Functions = "$ThemeRoot/functions.php"
$PageTemplate = "$ThemeRoot/page.php"
$DefaultPart = "$ThemeRoot/template-parts/content-page.php"
$GuidePart = "$ThemeRoot/template-parts/guide-page.php"
$Footer = "$ThemeRoot/footer.php"
$MutationVerifier = 'ops/verify-guide-experience-mutations.ps1'
$LiveVerifier = 'ops/verify-guide-experience-live.php'
$PublicVerifier = 'ops/verify-guide-experience-public.ps1'
$PhpTokenizer = 'ops/verify-guide-experience-tokenizer.php'

Require-File $Routing
Require-File $ContentProvider
Require-File $ContextProvider
Require-File $Functions
Require-File $PageTemplate
Require-File $DefaultPart
Require-File $GuidePart
Require-File $Footer
Require-File $MutationVerifier
Require-File $LiveVerifier
Require-File $PublicVerifier
Require-File $PhpTokenizer
Require-File $GuideJsRuntimeVerifier
Require-Contains $PhpTokenizer 'token_get_all'
Require-Contains $PhpTokenizer 'TOKEN_PARSE'
Require-Contains $PhpTokenizer 'T_VARIABLE'
Require-Contains $PhpTokenizer 'T_START_HEREDOC'
Require-Contains $PhpTokenizer 'T_END_HEREDOC'
Require-Contains $PhpTokenizer 'T_OBJECT_OPERATOR'
Require-Contains $PhpTokenizer 'T_NULLSAFE_OBJECT_OPERATOR'
Require-Contains $MutationVerifier "'ops/verify-guide-experience-tokenizer.php'"
$PhpCommandPath = Resolve-PhpExecutable $PhpExecutable
if ($null -eq $PhpCommandPath) {
    $Failures.Add('PHP CLI is required for executable live-verifier token inspection')
} else {
    Test-PhpVariableArrayTokenizerFixtures $PhpCommandPath (Join-Path $RepoRoot $PhpTokenizer)
}
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-routing.php');"
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-content.php');"
Require-Contains $Functions "require_once get_theme_file_path('/inc/guide-context.php');"
Require-Contains $Routing 'function vg_guide_pilot_paths(): array'
Require-Contains $Routing 'function vg_classify_guide_path(string $path): ?string'
Require-Contains $Routing 'function vg_get_guide_path(?WP_Post $post = null): string'
Require-Contains $Routing 'function vg_get_guide_type(?WP_Post $post = null): ?string'
Require-Contains $Routing 'function vg_is_guide_experience_page(?WP_Post $post = null): bool'
Require-Contains $ContentProvider 'function vg_is_empty_freeform_block(array $block): bool'
Require-Contains $ContentProvider 'function vg_split_guide_blocks(string $postContent): ?array'
Require-Contains $ContentProvider 'function vg_inspect_guide_html(string $html): ?array'
Require-Contains $ContentProvider 'function vg_is_valid_guide_heading_id(string $id): bool'
Require-Contains $ContentProvider 'function vg_allocate_guide_heading_id(string $base, array $reservedIds, array $assignedIds): string'
Require-Contains $ContentProvider 'function vg_collect_guide_heading_plan(string $html): ?array'
Require-Contains $ContentProvider 'function vg_apply_guide_heading_plan(string $html, array $plan): ?string'
Require-Contains $ContentProvider 'function vg_prepare_guide_headings(string $html): array'
Require-Contains $ContentProvider 'function vg_render_guide_toc(array $headings, string $className = ''vg-guide-toc''): string'
Require-Contains $ContentProvider 'function vg_prepare_guide_content(WP_Post $post): ?array'
Require-Contains $ContentProvider "'hero_html'"
Require-Contains $ContentProvider "'body_html'"
Require-Contains $ContentProvider "'headings'"
Require-Contains $ContentProvider 'vg-guide-hero'
Require-Contains $ContentProvider "preg_match_all('/<h1\b/i', `$heroSource) !== 1"
Require-Contains $ContentProvider 'data-vg-toc'
Require-Contains $ContentProvider 'sanitize_title'
Require-Contains $ContentProvider 'serialize_blocks'
Require-Contains $ContentProvider "apply_filters('the_content'"
Require-NotContains $ContentProvider '<h2\b'
Require-Contains $ContextProvider 'function vg_estimate_guide_reading_time(string $html): int'
Require-Contains $ContextProvider 'function vg_extract_guide_data_value(string $html, string $attribute): string'
Require-Contains $ContextProvider 'function vg_count_guide_sources(string $html): int'
Require-Contains $ContextProvider 'function vg_normalize_guide_route_url(string $url): string'
Require-Contains $ContextProvider 'function vg_get_related_routes(WP_Post $post, bool $hasExisting): array'
Require-Contains $ContextProvider 'function vg_guide_body_has_related_routes(string $html): bool'
Require-Contains $ContextProvider 'function vg_is_valid_guide_context(array $context): bool'
Require-Contains $ContextProvider 'function vg_build_guide_context(WP_Post $post): ?array'
Require-Matches $ContextProvider '\A<\?php\s+if\s*\(!\s*defined\(''ABSPATH''\)\s*\)\s*\{\s*exit;\s*\}' 'ABSPATH guard at the start of the context provider'
Require-Contains $ContextProvider "'_vg_reviewed_at'"
Require-Contains $ContextProvider 'vg-related-routes'
Require-Contains $ContextProvider 'if ($hasExisting) {'
Require-Contains $ContextProvider "'has_existing_related_routes'"
Require-Contains $ContextProvider "'reading_time'"
Require-Contains $ContextProvider "'source_count'"
Require-Contains $ContextProvider "'best_for'"
Require-Contains $ContextProvider "'skip_if'"
Require-Contains $ContextProvider "'related_routes'"
Require-Matches $PageTemplate '\A<\?php\s+if\s*\(!\s*defined\(''ABSPATH''\)\s*\)\s*\{\s*exit;\s*\}' 'ABSPATH guard at the start of the page template'
Require-Contains $PageTemplate 'vg_is_guide_experience_page($post)'
Require-Contains $PageTemplate 'vg_build_guide_context($post)'
Require-Contains $PageTemplate '$guideContext = null;'
Require-Contains $PageTemplate '$guideFunctionsReady = function_exists(''vg_is_guide_experience_page'')'
Require-Contains $PageTemplate "&& function_exists('vg_build_guide_context')"
Require-Contains $PageTemplate "&& function_exists('vg_is_valid_guide_context');"
Require-Contains $PageTemplate '$post instanceof WP_Post && $guideFunctionsReady && vg_is_guide_experience_page($post)'
Require-Contains $PageTemplate '$guideFunctionsReady && is_array($guideContext) && vg_is_valid_guide_context($guideContext)'
Require-Matches $PageTemplate '(?s)\$guideFunctionsReady\s*=.*?if\s*\(\$post\s+instanceof\s+WP_Post\s+&&\s+\$guideFunctionsReady\s+&&\s+vg_is_guide_experience_page\(\$post\)' 'guide function availability is checked before guide routing'
Require-Matches $PageTemplate '(?s)\$guideFunctionsReady\s*=.*?if\s*\(\$guideFunctionsReady\s+&&\s+is_array\(\$guideContext\)\s+&&\s+vg_is_valid_guide_context\(\$guideContext\)' 'guide function availability is checked before context validation'
Require-NotContains $PageTemplate 'if (is_array($guideContext)) {'
Require-Contains $PageTemplate "get_template_part('template-parts/guide', 'page', `$guideContext);"
Require-Contains $PageTemplate "get_template_part('template-parts/content', 'page');"
Require-Matches $DefaultPart '\A<\?php\s+if\s*\(!\s*defined\(''ABSPATH''\)\s*\)\s*\{\s*exit;\s*\}' 'ABSPATH guard at the start of the default page template part'
Require-Contains $DefaultPart '<h1>'
Require-Contains $DefaultPart 'get_the_content();'
Require-Contains $DefaultPart 'echo $content;'
Require-Contains $DefaultPart 'WP_HTML_Tag_Processor'
Require-Contains $DefaultPart "'H1'"
Require-Contains $GuidePart '! is_array($args ?? null)'
Require-Contains $GuidePart '! vg_is_valid_guide_context($args)'
Require-Contains $GuidePart 'return;'
Require-Contains $GuidePart 'data-vg-guide'
Require-Contains $GuidePart 'data-vg-guide-type'
Require-Contains $GuidePart 'role="list"'
Require-Contains $GuidePart 'role="listitem"'
Require-Contains $GuidePart "'destination' => __('Destination', 'vietnamguide-premium')"
Require-Contains $GuidePart "'itinerary' => __('Itinerary', 'vietnamguide-premium')"
Require-Contains $GuidePart "'comparison' => __('Comparison', 'vietnamguide-premium')"
Require-Contains $GuidePart "'practical' => __('Practical', 'vietnamguide-premium')"
Require-Contains $GuidePart "_n('%d minute read', '%d minutes read', `$readingTime, 'vietnamguide-premium')"
Require-NotContains $GuidePart 'ucfirst('
Require-Contains $GuidePart 'vg-guide-jump'
Require-Contains $GuidePart 'vg-guide-spine'
Require-Contains $GuidePart 'vg-guide-article'
Require-Contains $GuidePart 'vg-guide-trust'
Require-Contains $GuidePart 'vg-guide-related'
Require-NotContains $GuidePart '<h1'
Require-Contains $Footer "esc_url(vg_home_url('source-update-policy'))"
Require-NotContains $Footer "vg_home_url('source-policy')"

Require-Contains $MutationVerifier '$RequiredContractPaths = @('
Require-Contains $MutationVerifier "[guid]::NewGuid().ToString('N')"
Require-Contains $MutationVerifier '$ValidatedTempRoot = (Resolve-Path -LiteralPath $TempRoot).Path'
Require-Contains $MutationVerifier '$MutationArguments'
Require-Contains $MutationVerifier "'-RepoRootOverride', `$MutationRoot"
Require-Contains $MutationVerifier "'-PhpExecutable', `$PhpExecutable"
Require-Contains $MutationVerifier 'if ($LASTEXITCODE -eq 0) {'
Require-Contains $MutationVerifier 'finally {'
Require-Contains $MutationVerifier 'Remove-Item -LiteralPath $ValidatedTempRoot -Recurse -Force'
Require-Contains $MutationVerifier 'pilot allowlist bypass'
Require-Contains $MutationVerifier 'itinerary pilot allowlist entry regression'
Require-Contains $MutationVerifier 'live itinerary pilot type inventory regression'
Require-Contains $MutationVerifier 'live itinerary pilot type residue regression'
Require-Contains $MutationVerifier 'live pilot type executable decoy regression'
Require-Contains $MutationVerifier 'live pilot type prefix increment regression'
Require-Contains $MutationVerifier 'live pilot type indexed postfix decrement regression'
Require-Contains $MutationVerifier 'live pilot type array-offset write regression'
Require-Contains $MutationVerifier 'live pilot type compound assignment regression'
Require-Contains $MutationVerifier 'live pilot type global braced control assignment regression'
Require-Contains $MutationVerifier 'public pilot compound assignment regression'
Require-Contains $MutationVerifier 'public pilot second assignment regression'
Require-Contains $MutationVerifier 'public pilot expression RHS regression'
Require-Contains $MutationVerifier 'public pilot indexed assignment regression'
Require-Contains $MutationVerifier 'public pilot parenthesized assignment regression'
Require-Contains $MutationVerifier 'public pilot global braced assignment regression'
Require-Contains $MutationVerifier 'public pilot called function script-scope compound assignment regression'
Require-Contains $MutationVerifier 'public pilot invoked scriptblock script-scope indexed assignment regression'
Require-Contains $MutationVerifier 'public pilot called function inherited indexed assignment regression'
Require-Contains $MutationVerifier 'public pilot called function false-conditional inherited indexed assignment regression'
Require-Contains $MutationVerifier 'public pilot invoked scriptblock inherited indexed assignment regression'
Require-Contains $MutationVerifier 'public pilot multiple-assignment regression'
Require-Contains $MutationVerifier 'public pilot foreach target assignment regression'
Require-Contains $MutationVerifier 'public non-pilot required quoted-comment regression'
Require-Contains $MutationVerifier 'routing pilot order regression'
Require-Contains $MutationVerifier 'public pilot path case regression'
Require-Contains $MutationVerifier 'public non-pilot compound assignment regression'
Require-Contains $MutationVerifier 'public non-pilot path case regression'
Require-Contains $MutationVerifier 'public non-pilot path duplicate regression'
Require-Contains $MutationVerifier 'public non-pilot indexed assignment regression'
Require-Contains $MutationVerifier 'live pilot type short destructuring assignment regression'
Require-Contains $MutationVerifier 'live pilot type nested short destructuring assignment regression'
Require-Contains $MutationVerifier 'live pilot type list destructuring assignment regression'
Require-Contains $MutationVerifier 'live pilot type arrow ternary boundary regression'
Require-Contains $MutationVerifier 'live pilot type GLOBALS alias assignment regression'
Require-Contains $MutationVerifier 'live pilot type called function GLOBALS compound assignment regression'
Require-Contains $MutationVerifier 'live pilot type GLOBALS unset regression'
Require-Contains $MutationVerifier 'live pilot type called function GLOBALS indexed unset regression'
Require-Contains $MutationVerifier 'live pilot type called function explicit global compound assignment regression'
Require-Contains $MutationVerifier 'live pilot type unset regression'
Require-Contains $MutationVerifier 'live pilot type indexed unset regression'
Require-Contains $MutationVerifier '$ExpectedMutationCount = 112'
Require-Contains $MutationVerifier '$DuplicateMutationNames.Count -ne 0'
Require-Contains $MutationVerifier 'page guide function availability guard removal'
Require-Contains $MutationVerifier 'global reduced-motion scroll override removal'
Require-Contains $MutationVerifier 'guide fragment heading offset removal'
Require-Contains $MutationVerifier 'active guide aria-current assignment removal'
Require-Contains $MutationVerifier 'inactive guide aria-current cleanup removal'
Require-Contains $MutationVerifier 'non-H2 ID reservation removal'
Require-Contains $MutationVerifier 'authored H2 non-H2 collision guard removal'
Require-Contains $MutationVerifier 'public guide asset status guard removal'
Require-Contains $MutationVerifier 'public semantic H1 guard removal'
Require-Contains $MutationVerifier 'public semantic guide shell guard removal'
Require-Contains $MutationVerifier 'public semantic guide navigation guard removal'
Require-Contains $MutationVerifier 'public DOM snapshot reuse removal'
Require-Contains $MutationVerifier 'public inert raw-text tokenizer state removal'
Require-Contains $MutationVerifier 'public script double-escaped state removal'
Require-Contains $MutationVerifier 'public HTML tag-name delimiter validation removal'
Require-Contains $MutationVerifier 'public opening self-close delimiter rejection'
Require-Contains $MutationVerifier 'public HTML tag-prefix grammar relaxation'
Require-Contains $MutationVerifier 'public custom-origin fixture isolation removal'
Require-Contains $MutationVerifier 'public semantic H1 inventory removal'
Require-Contains $MutationVerifier 'public semantic guide shell inventory removal'
Require-Contains $MutationVerifier 'public semantic guide navigation inventory removal'
Require-Contains $MutationVerifier 'public semantic stylesheet element inventory removal'
Require-Contains $MutationVerifier 'public semantic script element inventory removal'
Require-Contains $MutationVerifier 'public semantic stylesheet relation guard removal'
Require-Contains $MutationVerifier 'public asset exact origin guard removal'
Require-Contains $MutationVerifier 'public asset credentials guard removal'
Require-Contains $MutationVerifier 'public asset exact path guard removal'
Require-Contains $MutationVerifier 'public asset cache query guard removal'
Require-Contains $MutationVerifier 'public asset fragment guard removal'
Require-Contains $MutationVerifier 'public asset redirect rejection removal'
Require-Contains $MutationVerifier 'public page status guard removal'
Require-Contains $MutationVerifier 'public page response URI guard removal'
Require-Contains $MutationVerifier 'public asset MIME guard removal'
Require-Contains $MutationVerifier 'public asset response URI guard removal'
Require-Contains $MutationVerifier 'public asset SHA-256 parity guard removal'
Require-Contains $MutationVerifier 'public local asset fail-closed guard removal'
Require-Contains $MutationVerifier 'public guide fragment target guard removal'
Require-Contains $MutationVerifier 'leading comment-only freeform rejection'
Require-Contains $MutationVerifier 'meaningful leading freeform acceptance'
Require-Contains $MutationVerifier 'rendered hero H1 guard removal'
Require-Contains $MutationVerifier 'rendered body H1 guard removal'
Require-Contains $MutationVerifier 'required hero class guard removal'
Require-Contains $MutationVerifier 'heading HTML round-trip guard removal'
Require-Contains $MutationVerifier 'heading list round-trip guard removal'
Require-Contains $MutationVerifier 'unique heading ID guard removal'
Require-Contains $MutationVerifier 'opted-out heading inclusion'
Require-Contains $MutationVerifier 'reserved heading collision guard removal'
Require-Contains $MutationVerifier 'assigned heading collision guard removal'
Require-Contains $MutationVerifier 'incomplete rendered HTML guard removal'
Require-Contains $MutationVerifier 'incomplete heading-plan guard removal'
Require-Contains $MutationVerifier 'heading application count guard removal'
Require-Contains $MutationVerifier 'duplicate literal guide H1'
Require-Contains $MutationVerifier 'global guide asset enqueue'
Require-Contains $MutationVerifier 'homepage CSS shared theme version regression'
Require-Contains $MutationVerifier 'guide patterns CSS shared theme version regression'
Require-Contains $MutationVerifier 'homepage JavaScript shared theme version regression'
Require-Contains $MutationVerifier 'guide CSS shared theme version regression'
Require-Contains $MutationVerifier 'guide JavaScript shared theme version regression'
Require-Contains $MutationVerifier 'legacy table wrapper removal'
Require-Contains $MutationVerifier 'legacy table idempotence guard removal'
Require-Contains $MutationVerifier 'legacy table focus guard removal'
Require-Contains $MutationVerifier 'legacy table preventDefault regression'
Require-Contains $MutationVerifier 'canonical TOC relationship removal'
Require-Contains $MutationVerifier 'EEAT reviewed precedence inversion'
Require-Contains $MutationVerifier 'invalid curated URL shape guard removal'
Require-Contains $MutationVerifier 'invalid curated route item guard removal'
Require-Contains $MutationVerifier 'existing related-route suppression removal'
Require-Contains $MutationVerifier 'existing related-route context invariant removal'
Require-Contains $MutationVerifier 'password-protected fallback removal'
Require-Contains $MutationVerifier 'multipage fallback removal'
Require-NotContains $MutationVerifier "<h2\b"
Require-FunctionContains $MutationVerifier 'Copy-ContractTree' 'Copy-Item -LiteralPath $SourcePath -Destination $DestinationPath'
Require-FunctionContains $MutationVerifier 'Set-ExactReplacement' '$SecondIndex'
Require-FunctionContains $MutationVerifier 'Set-ExactReplacement' '[System.IO.File]::WriteAllText'
Require-FunctionOrder $MutationVerifier 'Set-ExactReplacement' '$SecondIndex' '[System.IO.File]::WriteAllText'
Require-Matches $MutationVerifier '(?s)try\s*\{.*?\}\s*finally\s*\{.*?Remove-Item\s+-LiteralPath\s+\$ValidatedTempRoot\s+-Recurse\s+-Force' 'validated mutation temp cleanup in finally'

Require-Matches $LiveVerifier '(?s)\A<\?php\s+/\*\*.*?if\s*\(!\s*defined\(''ABSPATH''\)\s*\)\s*\{.*?WordPress is not loaded' 'live verifier ABSPATH failure guard'
Require-Contains $LiveVerifier "'vg_guide_pilot_paths'"
Require-Contains $LiveVerifier "'vg_get_guide_type'"
Require-Contains $LiveVerifier "'vg_is_guide_experience_page'"
Require-Contains $LiveVerifier "'vg_prepare_guide_content'"
Require-Contains $LiveVerifier "'vg_prepare_guide_headings'"
Require-Contains $LiveVerifier "'vg_render_guide_toc'"
Require-Contains $LiveVerifier "'vg_normalize_guide_route_url'"
Require-Contains $LiveVerifier "'vg_get_related_routes'"
Require-Contains $LiveVerifier "'vg_is_valid_guide_context'"
Require-Contains $LiveVerifier "'vg_build_guide_context'"
Require-Matches $LiveVerifier '(?s)\$required_functions\s*=\s*\[.*?\];' 'required live function inventory array declaration'
Require-Contains $LiveVerifier 'WP_HTML_Tag_Processor'
Require-Contains $LiveVerifier "`$result['context']['type'] === `$expected_type"
Require-Contains $LiveVerifier 'destinations/ho-chi-minh-city-travel-guide'
Require-Contains $LiveVerifier 'itineraries/10-days-in-vietnam'
Require-Contains $LiveVerifier 'compare/ha-long-bay-vs-lan-ha-bay'
Require-Contains $LiveVerifier 'plan/vietnam-evisa'
Require-Contains $LiveVerifier 'the expected paths'
Require-NotContains $LiveVerifier 'eight expected paths'
Require-NotContains $LiveVerifier 'four expected paths'
Require-Matches $LiveVerifier '\$pseudo_inspection\s*=\s*vg_inspect_guide_html\s*\(\s*\$pseudo_html\s*\)\s*;' 'pseudo inspection assignment uses the semantic HTML inspector'
Require-Matches $LiveVerifier '(?:(?<!\s)(?<!->)(?<!::)(?<!\\)\s+|(?<![A-Za-z0-9_\\>:\s]))vg_prepare_guide_content\s*\(\s*\$pseudo_post\s*\)\s*;' 'pseudo content fixture calls production content preparation'
Require-Matches $LiveVerifier '(?:(?<!\s)(?<!->)(?<!::)(?<!\\)\s+|(?<![A-Za-z0-9_\\>:\s]))vg_is_valid_guide_context\s*\(\s*\$pseudo_context\s*\)' 'pseudo context fixture validates the built guide context'
foreach ($FixtureLabel in @(
    'pilot strict context'
    'rendered hero/body H1 contract'
    'leading comment-only and whitespace-only freeform markers'
    'meaningful leading freeform rejection'
    'filter-generated H1 fails closed'
    'script and comment pseudo-headings ignored'
    'authored heading IDs and deterministic collisions'
    'non-H2 element IDs reserve heading slugs'
    'authored H2 IDs avoid non-H2 collisions'
    'opted-out headings excluded without collisions'
    'incomplete markup fails closed'
    'canonical EEAT metadata precedence'
    'curated route URL normalization'
    'protected and multipage fallback'
    'malformed context rejection'
    'canonical heading and TOC relationship'
    'embedded related-route conflict rejection'
)) {
    Require-Contains $LiveVerifier $FixtureLabel
}
Require-Contains $LiveVerifier "add_filter('get_post_metadata'"
Require-Contains $LiveVerifier "remove_filter('get_post_metadata'"
Require-Contains $LiveVerifier 'finally {'
Require-Contains $LiveVerifier 'catch (Throwable $throwable)'
Require-Matches $LiveVerifier "(?s)add_filter\('get_post_metadata'.*?try\s*\{.*?\}\s*finally\s*\{\s*remove_filter\('get_post_metadata'" 'metadata filters restored in finally'
Require-NotMatches $LiveVerifier 'if\s*\(\s*(?:function_exists\s*\(\s*''vg_eeat_get_field''\s*\)\s*&&\s*function_exists\s*\(\s*''vg_eeat_lines''\s*\)|function_exists\s*\(\s*''vg_eeat_lines''\s*\)\s*&&\s*function_exists\s*\(\s*''vg_eeat_get_field''\s*\))\s*\)\s*\{' 'conditional vg_eeat_lines helper guard'
Require-NotMatches $LiveVerifier 'if\s*\(\s*(?:function_exists\s*\(\s*''vg_eeat_get_field''\s*\)\s*&&\s*function_exists\s*\(\s*''vg_eeat_related_route_items''\s*\)|function_exists\s*\(\s*''vg_eeat_related_route_items''\s*\)\s*&&\s*function_exists\s*\(\s*''vg_eeat_get_field''\s*\))\s*\)\s*\{' 'conditional vg_eeat_related_route_items helper guard'
Require-Contains $LiveVerifier '<script>window.fake = "<h1>script heading</h1>";</script>'
Require-Contains $LiveVerifier '<!-- <h1>comment heading</h1> -->'
Require-Contains $LiveVerifier '<!-- vg-hcmc-hero:v1 -->'
Require-Contains $LiveVerifier '<p>Meaningful introduction.</p>'
Require-Contains $LiveVerifier "'#tag' !== `$processor->get_token_type()"
Require-Contains $LiveVerifier "'all_ids'"
Require-Contains $LiveVerifier '<div id="arrival"><h3 id="local-transport">'
Require-Contains $LiveVerifier '<div id="arrival"></div><h2 id="arrival">Arrival</h2>'
Require-Contains $LiveVerifier "['arrival', 'arrival-2']"
Require-Contains $LiveVerifier "vg_inspect_guide_html((string) `$pseudo_content['hero_html'])"
foreach ($EeatFunction in @(
    'vg_eeat_get_field'
    'vg_eeat_lines'
    'vg_eeat_related_route_items'
)) {
    Require-Matches $LiveVerifier "(?s)\`$required_functions\s*=\s*\[.*?'$EeatFunction'.*?\]" "required live EEAT helper inventory includes $EeatFunction"
}
Require-NotContains $LiveVerifier 'if ($runtime_ready && $pilot_posts !== []) {'
Require-Contains $LiveVerifier 'WP_CLI::error'
Require-Contains $LiveVerifier 'VietnamGuide guide experience live verification passed.'
Require-NotContains $LiveVerifier 'wp_insert_post('
Require-NotContains $LiveVerifier 'wp_update_post('
Require-NotContains $LiveVerifier 'update_post_meta('
Require-NotContains $LiveVerifier 'delete_post_meta('

Require-Contains $PublicVerifier "[string]`$BaseUrl = 'https://vietnamguide.net'"
Require-Contains $PublicVerifier '[switch]$FixturesOnly'
Require-Contains $PublicVerifier 'Invoke-WebRequest'
Require-Contains $PublicVerifier '-TimeoutSec'
Require-Contains $PublicVerifier 'try {'
Require-Contains $PublicVerifier 'catch {'
Require-Contains $PublicVerifier 'destinations/ho-chi-minh-city-travel-guide'
Require-Contains $PublicVerifier 'itineraries/10-days-in-vietnam'
Require-Contains $PublicVerifier 'itineraries/7-days-in-vietnam'
Require-Contains $PublicVerifier 'itineraries/14-days-in-vietnam'
Require-Contains $PublicVerifier 'itineraries/21-days-in-vietnam'
Require-Contains $PublicVerifier 'itineraries/hanoi-in-2-days'
Require-Contains $PublicVerifier 'compare/ha-long-bay-vs-lan-ha-bay'
Require-Contains $PublicVerifier 'plan/vietnam-evisa'
Require-Contains $PublicVerifier 'StatusCode -ne 200'
Require-Contains $PublicVerifier 'data-vg-guide'
Require-Contains $PublicVerifier 'guide-experience.css'
Require-Contains $PublicVerifier 'guide-experience.js'
Require-Contains $PublicVerifier 'vg-guide-toc'
Require-Contains $PublicVerifier 'fatal error'
Require-Contains $PublicVerifier 'source-update-policy'
Require-Contains $PublicVerifier 'function Get-PublicResource'
Require-Contains $PublicVerifier 'function Get-PublicAssetUrl'
Require-Contains $PublicVerifier 'function Get-PublicDomSnapshot'
Require-Contains $PublicVerifier 'function Find-PublicHtmlTagEnd'
Require-Contains $PublicVerifier 'function Test-PublicHtmlTagNameDelimiter'
Require-Contains $PublicVerifier 'function Find-PublicScriptEnd'
Require-Contains $PublicVerifier 'function Convert-PublicHtmlForMshtml'
Require-Contains $PublicVerifier 'function Get-NormalizedOriginKey'
Require-Contains $PublicVerifier 'function Test-PublicAssetUri'
Require-Contains $PublicVerifier 'function Test-PublicAssetResponse'
Require-Contains $PublicVerifier 'function Test-PublicPageResponse'
Require-Contains $PublicVerifier 'function Require-GuideFragmentTargets'
Require-Contains $PublicVerifier 'destinations/hanoi-travel-guide'
Require-Contains $PublicVerifier 'itineraries/14-days-in-vietnam'
Require-Contains $PublicVerifier 'compare/da-nang-vs-hoi-an'
Require-Contains $PublicVerifier 'plan/sim-esim-vietnam'
Require-NotContains $PublicVerifier "Get-PublicPage -Path 'about'"
Require-Contains $PublicVerifier "expected exactly one target ID"
Require-Contains $PublicVerifier "guide CSS asset"
Require-Contains $PublicVerifier "guide JavaScript asset"
Require-FunctionContains $PublicVerifier 'Get-PublicPage' 'Invoke-WebRequest'
Require-FunctionContains $PublicVerifier 'Get-PublicPage' '-TimeoutSec $RequestTimeoutSeconds'
Require-FunctionContains $PublicVerifier 'Get-PublicPage' '-MaximumRedirection 0'
Require-FunctionContains $PublicVerifier 'Get-PublicPage' '$RequestedUri = [uri]$Url'
Require-FunctionContains $PublicVerifier 'Get-PublicPage' '$Response.BaseResponse.ResponseUri'
Require-FunctionContains $PublicVerifier 'Get-PublicPage' 'Test-PublicPageResponse'
Require-FunctionContains $PublicVerifier 'Get-PublicPage' 'Dom = $Dom'
Require-FunctionContains $PublicVerifier 'Get-PublicPage' 'catch {'
Require-FunctionContains $PublicVerifier 'Require-NoFatalText' "'fatal error'"
Require-FunctionContains $PublicVerifier 'Get-PublicResource' 'Invoke-WebRequest'
Require-FunctionContains $PublicVerifier 'Get-PublicResource' '-MaximumRedirection 0'
Require-FunctionContains $PublicVerifier 'Get-PublicResource' 'RawContentStream'
Require-FunctionContains $PublicVerifier 'Get-PublicResource' 'Test-PublicAssetResponse'
Require-FunctionContains $PublicVerifier 'Get-NormalizedOriginKey' 'UserInfo'
Require-FunctionContains $PublicVerifier 'Get-NormalizedOriginKey' 'IsDefaultPort'
Require-FunctionContains $PublicVerifier 'Get-PublicExpectedAssets' 'if (-not (Test-Path -LiteralPath $LocalPath -PathType Leaf)) {'
Require-FunctionContains $PublicVerifier 'Get-PublicExpectedAssets' '$Specs[$Kind].Version = $Specs[$Kind].Sha256'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetUri' 'if (-not [string]::IsNullOrEmpty($Resolved.UserInfo)) {'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetUri' '(Get-NormalizedOriginKey $Resolved) -ne $BaseOriginKey'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetUri' '$Resolved.AbsolutePath -cne $ExpectedAsset.Path'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetUri' '$ExpectedQuery = ''?ver='' + [string]$ExpectedAsset.Version'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetUri' '$Resolved.Query -cne $ExpectedQuery'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetUri' '$Resolved.Fragment -ne '''''
Require-FunctionContains $PublicVerifier 'Test-PublicAssetResponse' '$StatusCode -ne 200'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetResponse' '$AllowedContentTypes -notcontains $ContentType'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetResponse' '$ActualHash.Equals($ExpectedAsset.Sha256'
Require-FunctionContains $PublicVerifier 'Test-PublicAssetResponse' 'if ($ResponseUri.AbsoluteUri -ne $RequestedUri.AbsoluteUri) {'
Require-FunctionContains $PublicVerifier 'Test-PublicPageResponse' 'if (200 -ne $StatusCode) {'
Require-FunctionContains $PublicVerifier 'Test-PublicPageResponse' 'response URI evidence was missing'
Require-FunctionContains $PublicVerifier 'Test-PublicPageResponse' 'elseif ($ResponseUri.AbsoluteUri -cne $RequestedUri.AbsoluteUri) {'
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' 'New-Object -ComObject HTMLFile'
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' 'Convert-PublicHtmlForMshtml'
Require-FunctionContains $PublicVerifier 'Find-PublicRawTextEnd' 'Test-PublicHtmlTagNameDelimiter'
Require-FunctionContains $PublicVerifier 'Find-PublicRawTextEnd' 'Find-PublicScriptEnd'
Require-FunctionContains $PublicVerifier 'Find-PublicScriptEnd' "`$State = 'double-escaped'"
Require-FunctionContains $PublicVerifier 'Find-PublicScriptEnd' "`$State = 'escaped'"
Require-FunctionContains $PublicVerifier 'Find-PublicScriptEnd' "`$State = 'data'"
Require-FunctionContains $PublicVerifier 'Find-PublicScriptEnd' 'Test-PublicHtmlTagNameDelimiter'
Require-FunctionContains $PublicVerifier 'Convert-PublicHtmlForMshtml' 'Test-PublicHtmlTagNameDelimiter'
Require-FunctionContains $PublicVerifier 'Convert-PublicHtmlForMshtml' "'^<(?<closing>/)?(?<name>[A-Za-z][A-Za-z0-9:-]*)'"
Require-FunctionNotContains $PublicVerifier 'Get-PublicDomSnapshot' "[regex]::Replace(`$Html, '(?is)<(?:template|noscript)"
Require-FunctionNotContains $PublicVerifier 'Get-PublicDomSnapshot' "[regex]::Replace(`$RenderableHtml, '(?is)<nav"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "getElementsByTagName('*')"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "getElementsByTagName('div')"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "getAttribute('data-vg-dom-nav')"
Require-FunctionContains $PublicVerifier 'Convert-PublicHtmlForMshtml' "`$TagName -in @('template', 'noscript')"
Require-FunctionContains $PublicVerifier 'Convert-PublicHtmlForMshtml' "`$TagName -eq 'nav'"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' 'vg-guide-(?:toc|jump)'
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "H1Count = @(`$Document.getElementsByTagName('h1')).Count"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "if (`$null -ne `$Element.getAttributeNode('data-vg-guide')) {"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "foreach (`$Navigation in `$Document.getElementsByTagName('div')) {"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "foreach (`$Link in `$Document.getElementsByTagName('link')) {"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "foreach (`$Script in `$Document.getElementsByTagName('script')) {"
Require-FunctionContains $PublicVerifier 'Get-PublicDomSnapshot' "`$RelTokens -contains 'stylesheet'"
Require-FunctionContains $PublicVerifier 'Require-GuideFragmentTargets' 'HtmlDecode'
Require-FunctionContains $PublicVerifier 'Require-GuideFragmentTargets' 'UnescapeDataString'
Require-FunctionContains $PublicVerifier 'Require-GuideFragmentTargets' '$TargetCount -ne 1'
Require-FunctionContains $PublicVerifier 'Require-GuideFragmentTargets' '$Snapshot = $Page.Dom'
Require-FunctionNotContains $PublicVerifier 'Require-GuideFragmentTargets' 'Get-PublicDomSnapshot -Html $Page.Content'
Require-Contains $PublicVerifier 'if ($Page.Dom.H1Count -ne 1) {'
Require-Contains $PublicVerifier 'if (-not $Page.Dom.HasGuideShell) {'
Require-Contains $PublicVerifier 'if (-not $Page.Dom.HasGuideNavigation) {'
Require-Contains $PublicVerifier 'DOM parser fixture accepted inert pseudo guide markup'
Require-Contains $PublicVerifier 'inert raw-text fixture exposed template descendants'
Require-Contains $PublicVerifier 'script double-escaped fixture exposed inert guide markup'
Require-Contains $PublicVerifier 'ordinary script close fixture did not expose reviewed guide markup'
Require-Contains $PublicVerifier 'escaped script close fixture did not expose reviewed guide markup'
Require-Contains $PublicVerifier 'double-escaped script comment close did not return to script data'
Require-Contains $PublicVerifier 'malformed template closing delimiter exposed inert descendants'
Require-Contains $PublicVerifier 'malformed raw-text closing delimiter exposed inert descendants'
Require-Contains $PublicVerifier 'valid whitespace closing delimiter was rejected'
Require-Contains $PublicVerifier 'malformed template tag prefix exposed inert descendants'
Require-Contains $PublicVerifier 'malformed raw-text tag prefix exposed inert descendants'
Require-Contains $PublicVerifier 'malformed opening tag prefix was treated as an inert tag'
Require-Contains $PublicVerifier 'malformed raw-text opening tag changed semantic guide inventory'
Require-Contains $PublicVerifier 'valid opening self-close slash delimiter was rejected'
Require-Contains $PublicVerifier "`$OpeningSelfCloseProbe = '<vg-probe/>'"
Require-Contains $PublicVerifier '$FixtureOrigin = $BaseUri.GetLeftPart([System.UriPartial]::Authority).TrimEnd(''/'')'
Require-Contains $PublicVerifier 'asset URI fixture accepted external host'
Require-Contains $PublicVerifier 'asset URI fixture accepted scheme or port mismatch'
Require-Contains $PublicVerifier 'asset URI fixture accepted credentials'
Require-Contains $PublicVerifier 'asset URI fixture accepted wrong theme path'
Require-Contains $PublicVerifier 'asset URI fixture accepted invalid cache query or fragment'
Require-Contains $PublicVerifier 'asset URI fixture rejected correct content version'
Require-Contains $PublicVerifier 'asset URI fixture accepted stale theme version'
Require-Contains $PublicVerifier 'asset URI fixture accepted wrong content version'
Require-Contains $PublicVerifier 'asset response fixture accepted redirect status'
Require-Contains $PublicVerifier 'asset response fixture accepted HTML error body or MIME'
Require-Contains $PublicVerifier 'asset response fixture accepted wrong SHA-256'
Require-Contains $PublicVerifier 'page response fixture accepted redirect status'
Require-Contains $PublicVerifier 'page response fixture accepted mismatched final URI'
Require-Contains $PublicVerifier 'page response fixture rejected canonical response'
Require-Contains $PublicVerifier "Join-Path `$PSScriptRoot '..\wordpress\wp-content\themes\vietnamguide-premium\assets'"
Require-Contains $PublicVerifier 'local reviewed asset is missing'
Require-Contains $PublicVerifier 'VietnamGuide public verifier fixtures passed for $BaseOriginKey.'
Require-NotContains $PublicVerifier '$H1Count = [regex]::Matches'
Require-NotContains $PublicVerifier "Require-PublicContains `$Page 'data-vg-guide'"
Require-NotContains $PublicVerifier "Require-PublicContains `$Page 'guide-experience.css'"
Require-NotContains $PublicVerifier "Require-PublicContains `$Page 'guide-experience.js'"
Require-NotContains $PublicVerifier "`$Page.Content.Contains('vg-guide-jump')"

$PublicFixtureOrigins = @(
    'https://vietnamguide.net'
    'http://staging.example:8081'
    'https://staging.example:444'
)
foreach ($PublicFixtureOrigin in $PublicFixtureOrigins) {
    $PreviousErrorActionPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $PublicFixtureOutput = & powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $RepoRoot $PublicVerifier) -BaseUrl $PublicFixtureOrigin -FixturesOnly 2>&1
        $PublicFixtureExitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $PreviousErrorActionPreference
    }
    if ($PublicFixtureExitCode -ne 0) {
        $Failures.Add("Public verifier fixtures failed for ${PublicFixtureOrigin}: $($PublicFixtureOutput -join ' ')")
    }
}

Test-PublicNonPilotInventoryReorderFixture
Test-PublicPilotInventoryLocalAssignmentFixture
Test-PublicPilotInventoryFilterLocalAssignmentFixture
Test-PublicPilotInventoryGuaranteedConditionalLocalAssignmentFixture

$PublicVerifierContent = Get-RepoContent $PublicVerifier
if ($null -ne $PublicVerifierContent) {
    $PublicVerifierTokens = $null
    $PublicVerifierParseErrors = $null
    $PublicVerifierAst = [System.Management.Automation.Language.Parser]::ParseInput(
        $PublicVerifierContent,
        [ref]$PublicVerifierTokens,
        [ref]$PublicVerifierParseErrors
    )
    if ($PublicVerifierParseErrors.Count -ne 0) {
        foreach ($ParseError in $PublicVerifierParseErrors) {
            $Failures.Add("Public verifier PowerShell parse error: $($ParseError.Message)")
        }
    } else {
        $PublicPilotPaths = @(Get-TopLevelLiteralStringArray $PublicVerifierAst 'PilotPaths' 'public pilot path inventory')
        $ExpectedPublicPilotPaths = @(
            'destinations/ho-chi-minh-city-travel-guide'
            'itineraries/10-days-in-vietnam'
            'itineraries/7-days-in-vietnam'
            'itineraries/14-days-in-vietnam'
            'itineraries/21-days-in-vietnam'
            'itineraries/hanoi-in-2-days'
            'compare/ha-long-bay-vs-lan-ha-bay'
            'plan/vietnam-evisa'
            'compare/cu-chi-tunnels-vs-mekong-delta-day-trip'
            'compare/da-nang-vs-hoi-an'
            'compare/hoi-an-vs-hue'
            'compare/mui-ne-vs-nha-trang'
            'compare/ninh-binh-day-trip-vs-overnight'
            'compare/north-central-south-vietnam'
            'compare/old-quarter-vs-french-quarter-vs-west-lake'
            'compare/phu-quoc-vs-nha-trang'
            'compare/trang-an-vs-tam-coc'
            'destinations/unesco-heritage-sites-vietnam'
            'destinations/best-beaches-in-vietnam'
            'destinations/best-places-to-visit-vietnam'
            'destinations/best-things-to-do-in-hanoi'
            'destinations/best-things-to-do-in-hoi-an'
            'destinations/best-things-to-do-in-hue'
            'destinations/ninh-binh-travel-guide'
            'destinations/ha-long-bay-travel-guide'
            'destinations/cat-ba-travel-guide'
            'destinations/bai-tu-long-bay-guide'
            'destinations/da-nang-travel-guide'
            'destinations/best-islands-in-vietnam'
            'destinations/phu-quoc-travel-guide'
            'destinations/con-dao-travel-guide'
            'destinations/nha-trang-travel-guide'
            'destinations/quy-nhon-travel-guide'
            'destinations/cham-islands-travel-guide'
            'destinations/ly-son-travel-guide'
            'destinations/mekong-delta-travel-guide'
            'destinations/best-day-trips-from-ho-chi-minh-city'
            'destinations/where-to-stay-in-ho-chi-minh-city'
            'destinations/hanoi-travel-guide'
            'destinations/where-to-stay-in-hanoi'
            'destinations/best-day-trips-from-hanoi'
            'destinations/where-to-stay-in-ninh-binh'
            'destinations/tam-coc-travel-guide'
            'plan/best-time-to-visit-vietnam'
            'plan/vietnam-travel-guide'
            'plan/transport-within-vietnam'
            'plan/money-cash-cards-atms'
            'plan/sim-esim-vietnam'
            'plan/safety-scams-vietnam'
            'plan/health-travel-insurance-vietnam'
            'plan/hanoi-airport-to-old-quarter'
            'plan/hanoi-to-ninh-binh-transport'
            'plan/ninh-binh-to-ha-long-bay-transfer'
            'destinations/ha-giang-loop-planning-guide'
            'destinations/sapa-travel-guide'
            'plan/hanoi-to-ha-giang-transport'
            'plan/hanoi-to-sapa-transport'
            'compare/sapa-vs-ha-giang'
        )
        if (($PublicPilotPaths -join "`n") -cne ($ExpectedPublicPilotPaths -join "`n")) {
            $Failures.Add("Expected exact ordered public pilot paths; found: $($PublicPilotPaths -join ', ')")
        }

        $NonPilotPaths = @(Get-TopLevelLiteralStringArray $PublicVerifierAst 'NonPilotPaths' 'public non-pilot path inventory')
        Require-ExactOrdinalSet 'public non-pilot path' $NonPilotPaths @(
            'compare'
            'destinations'
            'plan'
            'itineraries'
        )
    }
}

$LiveVerifierContent = Get-RepoContent $LiveVerifier
if ($null -ne $LiveVerifierContent -and $null -ne $PhpCommandPath) {
    $LivePilotTypeScan = Invoke-PhpVariableArrayTokenizer `
        $PhpCommandPath `
        (Join-Path $RepoRoot $PhpTokenizer) `
        (Join-Path $RepoRoot $LiveVerifier) `
        'pilot_types'
    foreach ($LivePilotTypeScanError in $LivePilotTypeScan.Errors) { $Failures.Add($LivePilotTypeScanError) }
    if ($LivePilotTypeScan.Assignments.Count -ne 1) {
        $Failures.Add("Expected exactly one executable live pilot type declaration, found $($LivePilotTypeScan.Assignments.Count)")
    } elseif ($null -ne $LivePilotTypeScan.Assignments[0].Body) {
        $LivePilotTypeBody = $LivePilotTypeScan.Assignments[0].Body
        $LivePilotTypeEntryPattern = '(?m)^[ \t]*''(?<key>[^''\r\n]+)''[ \t]*=>[ \t]*''(?<value>[^''\r\n]+)''[ \t]*,[ \t]*\r?$'
        $LivePilotTypeMatches = @([regex]::Matches($LivePilotTypeBody, $LivePilotTypeEntryPattern))
        $LivePilotTypeResidue = [regex]::Replace($LivePilotTypeBody, $LivePilotTypeEntryPattern, '')
        if (-not [string]::IsNullOrWhiteSpace($LivePilotTypeResidue)) {
            $Failures.Add('Unexpected syntax or residue in live pilot type inventory')
        }

        $LivePilotTypeMappings = @($LivePilotTypeMatches | ForEach-Object { "$($_.Groups['key'].Value)=$($_.Groups['value'].Value)" })
        $ExpectedLivePilotTypeMappings = @(
            'destinations/ho-chi-minh-city-travel-guide=destination'
            'itineraries/10-days-in-vietnam=itinerary'
            'itineraries/7-days-in-vietnam=itinerary'
            'itineraries/14-days-in-vietnam=itinerary'
            'itineraries/21-days-in-vietnam=itinerary'
            'itineraries/hanoi-in-2-days=itinerary'
            'compare/ha-long-bay-vs-lan-ha-bay=comparison'
            'plan/vietnam-evisa=practical'
            'compare/cu-chi-tunnels-vs-mekong-delta-day-trip=comparison'
            'compare/da-nang-vs-hoi-an=comparison'
            'compare/hoi-an-vs-hue=comparison'
            'compare/mui-ne-vs-nha-trang=comparison'
            'compare/ninh-binh-day-trip-vs-overnight=comparison'
            'compare/north-central-south-vietnam=comparison'
            'compare/old-quarter-vs-french-quarter-vs-west-lake=comparison'
            'compare/phu-quoc-vs-nha-trang=comparison'
            'compare/trang-an-vs-tam-coc=comparison'
            'destinations/unesco-heritage-sites-vietnam=destination'
            'destinations/best-beaches-in-vietnam=destination'
            'destinations/best-places-to-visit-vietnam=destination'
            'destinations/best-things-to-do-in-hanoi=destination'
            'destinations/best-things-to-do-in-hoi-an=destination'
            'destinations/best-things-to-do-in-hue=destination'
            'destinations/ninh-binh-travel-guide=destination'
            'destinations/ha-long-bay-travel-guide=destination'
            'destinations/cat-ba-travel-guide=destination'
            'destinations/bai-tu-long-bay-guide=destination'
            'destinations/da-nang-travel-guide=destination'
            'destinations/best-islands-in-vietnam=destination'
            'destinations/phu-quoc-travel-guide=destination'
            'destinations/con-dao-travel-guide=destination'
            'destinations/nha-trang-travel-guide=destination'
            'destinations/quy-nhon-travel-guide=destination'
            'destinations/cham-islands-travel-guide=destination'
            'destinations/ly-son-travel-guide=destination'
            'destinations/mekong-delta-travel-guide=destination'
            'destinations/best-day-trips-from-ho-chi-minh-city=destination'
            'destinations/where-to-stay-in-ho-chi-minh-city=destination'
            'destinations/hanoi-travel-guide=destination'
            'destinations/where-to-stay-in-hanoi=destination'
            'destinations/best-day-trips-from-hanoi=destination'
            'destinations/where-to-stay-in-ninh-binh=destination'
            'destinations/tam-coc-travel-guide=destination'
            'plan/best-time-to-visit-vietnam=practical'
            'plan/vietnam-travel-guide=practical'
            'plan/transport-within-vietnam=practical'
            'plan/money-cash-cards-atms=practical'
            'plan/sim-esim-vietnam=practical'
            'plan/safety-scams-vietnam=practical'
            'plan/health-travel-insurance-vietnam=practical'
            'plan/hanoi-airport-to-old-quarter=practical'
            'plan/hanoi-to-ninh-binh-transport=practical'
            'plan/ninh-binh-to-ha-long-bay-transfer=practical'
            'destinations/ha-giang-loop-planning-guide=destination'
            'destinations/sapa-travel-guide=destination'
            'plan/hanoi-to-ha-giang-transport=practical'
            'plan/hanoi-to-sapa-transport=practical'
            'compare/sapa-vs-ha-giang=comparison'
        )
        if (($LivePilotTypeMappings -join "`n") -cne ($ExpectedLivePilotTypeMappings -join "`n")) {
            $Failures.Add("Expected exact ordered live pilot type mappings; found: $($LivePilotTypeMappings -join ', ')")
        }
    }
}

$PilotFunction = Get-FunctionContent $Routing 'vg_guide_pilot_paths'
if ($null -ne $PilotFunction) {
    $PilotArray = [regex]::Match($PilotFunction, 'return\s*\[(?<items>.*?)\];', [System.Text.RegularExpressions.RegexOptions]::Singleline)
    if (-not $PilotArray.Success) {
        $Failures.Add('Missing pilot path array in vg_guide_pilot_paths()')
    } else {
        $PilotItems = @($PilotArray.Groups['items'].Value -split ',' | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne '' })
        $PilotPaths = @($PilotItems | ForEach-Object {
            $ItemMatch = [regex]::Match($_, '^[''"](?<value>[^''"]+)[''"]$')
            if ($ItemMatch.Success) { $ItemMatch.Groups['value'].Value } else { "invalid:$_" }
        })
        $ExpectedPilotPaths = @(
            'destinations/ho-chi-minh-city-travel-guide'
            'itineraries/10-days-in-vietnam'
            'itineraries/7-days-in-vietnam'
            'itineraries/14-days-in-vietnam'
            'itineraries/21-days-in-vietnam'
            'itineraries/hanoi-in-2-days'
            'compare/ha-long-bay-vs-lan-ha-bay'
            'plan/vietnam-evisa'
            'compare/cu-chi-tunnels-vs-mekong-delta-day-trip'
            'compare/da-nang-vs-hoi-an'
            'compare/hoi-an-vs-hue'
            'compare/mui-ne-vs-nha-trang'
            'compare/ninh-binh-day-trip-vs-overnight'
            'compare/north-central-south-vietnam'
            'compare/old-quarter-vs-french-quarter-vs-west-lake'
            'compare/phu-quoc-vs-nha-trang'
            'compare/trang-an-vs-tam-coc'
            'destinations/unesco-heritage-sites-vietnam'
            'destinations/best-beaches-in-vietnam'
            'destinations/best-places-to-visit-vietnam'
            'destinations/best-things-to-do-in-hanoi'
            'destinations/best-things-to-do-in-hoi-an'
            'destinations/best-things-to-do-in-hue'
            'destinations/ninh-binh-travel-guide'
            'destinations/ha-long-bay-travel-guide'
            'destinations/cat-ba-travel-guide'
            'destinations/bai-tu-long-bay-guide'
            'destinations/da-nang-travel-guide'
            'destinations/best-islands-in-vietnam'
            'destinations/phu-quoc-travel-guide'
            'destinations/con-dao-travel-guide'
            'destinations/nha-trang-travel-guide'
            'destinations/quy-nhon-travel-guide'
            'destinations/cham-islands-travel-guide'
            'destinations/ly-son-travel-guide'
            'destinations/mekong-delta-travel-guide'
            'destinations/best-day-trips-from-ho-chi-minh-city'
            'destinations/where-to-stay-in-ho-chi-minh-city'
            'destinations/hanoi-travel-guide'
            'destinations/where-to-stay-in-hanoi'
            'destinations/best-day-trips-from-hanoi'
            'destinations/where-to-stay-in-ninh-binh'
            'destinations/tam-coc-travel-guide'
            'plan/best-time-to-visit-vietnam'
            'plan/vietnam-travel-guide'
            'plan/transport-within-vietnam'
            'plan/money-cash-cards-atms'
            'plan/sim-esim-vietnam'
            'plan/safety-scams-vietnam'
            'plan/health-travel-insurance-vietnam'
            'plan/hanoi-airport-to-old-quarter'
            'plan/hanoi-to-ninh-binh-transport'
            'plan/ninh-binh-to-ha-long-bay-transfer'
            'destinations/ha-giang-loop-planning-guide'
            'destinations/sapa-travel-guide'
            'plan/hanoi-to-ha-giang-transport'
            'plan/hanoi-to-sapa-transport'
            'compare/sapa-vs-ha-giang'
        )
        if (($PilotPaths -join "`n") -cne ($ExpectedPilotPaths -join "`n")) {
            $Failures.Add("Expected exact ordered pilot paths; found: $($PilotPaths -join ', ')")
        }
    }
}

$ClassifierFunction = Get-FunctionContent $Routing 'vg_classify_guide_path'
if ($null -ne $ClassifierFunction) {
    $ClassifierArray = [regex]::Match($ClassifierFunction, '\$types\s*=\s*\[(?<items>.*?)\];', [System.Text.RegularExpressions.RegexOptions]::Singleline)
    if (-not $ClassifierArray.Success) {
        $Failures.Add('Missing classifier mapping array in vg_classify_guide_path()')
    } else {
        $ClassifierItems = @($ClassifierArray.Groups['items'].Value -split ',' | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne '' })
        $ClassifierMappings = @($ClassifierItems | ForEach-Object {
            $ItemMatch = [regex]::Match($_, '^[''"](?<key>[^''"]+)[''"]\s*=>\s*[''"](?<value>[^''"]+)[''"]$')
            if ($ItemMatch.Success) { "$($ItemMatch.Groups['key'].Value)=$($ItemMatch.Groups['value'].Value)" } else { "invalid:$_" }
        })
        Require-ExactSet 'classifier mapping' $ClassifierMappings @(
            'destinations=destination'
            'itineraries=itinerary'
            'compare=comparison'
            'plan=practical'
        )
    }
}

$GuideTypeFunction = Get-FunctionContent $Routing 'vg_get_guide_type'
if ($null -ne $GuideTypeFunction) {
    $FilteredValidation = [regex]::Match($GuideTypeFunction, 'in_array\s*\(\s*\$filtered\s*,\s*\[(?<items>.*?)\](?<tail>.*?)\)', [System.Text.RegularExpressions.RegexOptions]::Singleline)
    if (-not $FilteredValidation.Success) {
        $Failures.Add('Missing filtered type validation in vg_get_guide_type()')
    } else {
        $FilteredTypeItems = @($FilteredValidation.Groups['items'].Value -split ',' | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne '' })
        $FilteredTypes = @($FilteredTypeItems | ForEach-Object {
            $ItemMatch = [regex]::Match($_, '^[''"](?<value>[^''"]+)[''"]$')
            if ($ItemMatch.Success) { $ItemMatch.Groups['value'].Value } else { "invalid:$_" }
        })
        Require-ExactSet 'filtered guide type' $FilteredTypes @(
            'destination'
            'itinerary'
            'comparison'
            'practical'
        )

        if (-not [regex]::IsMatch($FilteredValidation.Groups['tail'].Value, '^\s*,\s*true\s*$')) {
            $Failures.Add('Expected strict true validation for filtered guide types in vg_get_guide_type()')
        }
    }
}

Require-FunctionMatches $Routing 'vg_get_guide_type' 'if\s*\(!\s*\$post\s+instanceof\s+WP_Post\s+\|\|\s+\$post->post_type\s*!==\s*''page''\s*\)\s*\{\s*return\s+null;\s*\}' 'non-page guard returning null'
Require-FunctionOrder $Routing 'vg_get_guide_type' "if (! `$post instanceof WP_Post || `$post->post_type !== 'page') {" "apply_filters('vg_guide_type'"
Require-FunctionContains $Routing 'vg_is_guide_experience_page' 'is_page($post->ID)'
Require-FunctionOrder $Routing 'vg_is_guide_experience_page' 'if (! in_array($path, vg_guide_pilot_paths(), true)) {' 'vg_get_guide_type($post)'

Require-FunctionContains $ContentProvider 'vg_is_empty_freeform_block' "(`$block['blockName'] ?? null) !== null"
Require-FunctionContains $ContentProvider 'vg_is_empty_freeform_block' "trim(`$html) === ''"
Require-FunctionContains $ContentProvider 'vg_is_empty_freeform_block' "preg_replace('/<!--[\s\S]*?-->/', '', `$html)"
Require-FunctionContains $ContentProvider 'vg_is_empty_freeform_block' "is_string(`$without_comments) && trim(`$without_comments) === ''"
Require-FunctionOrder $ContentProvider 'vg_is_empty_freeform_block' "(`$block['blockName'] ?? null) !== null" "preg_replace('/<!--[\s\S]*?-->/', '', `$html)"
Require-FunctionOrder $ContentProvider 'vg_is_empty_freeform_block' "preg_replace('/<!--[\s\S]*?-->/', '', `$html)" "is_string(`$without_comments) && trim(`$without_comments) === ''"
Require-FunctionContains $ContentProvider 'vg_split_guide_blocks' 'parse_blocks($postContent)'
Require-FunctionContains $ContentProvider 'vg_split_guide_blocks' 'vg_is_empty_freeform_block($blocks[0])'
Require-FunctionContains $ContentProvider 'vg_split_guide_blocks' "preg_match_all('/<h1\b/i', `$heroSource) !== 1"
Require-FunctionContains $ContentProvider 'vg_split_guide_blocks' 'serialize_blocks($blocks)'
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' 'WP_HTML_Tag_Processor'
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' 'next_token()'
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' 'get_token_name()'
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' 'is_tag_closer()'
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' "has_class('vg-guide-hero')"
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' 'paused_at_incomplete_token()'
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' "'H1' === `$tokenName"
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' "'has_hero_class'"
Require-FunctionContains $ContentProvider 'vg_inspect_guide_html' "'h1_count'"
Require-FunctionContains $ContentProvider 'vg_is_valid_guide_heading_id' "preg_match('/\s/u', `$id) === 0"
Require-FunctionContains $ContentProvider 'vg_allocate_guide_heading_id' 'isset($reservedIds[$candidate])'
Require-FunctionContains $ContentProvider 'vg_allocate_guide_heading_id' 'isset($assignedIds[$candidate])'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'next_token()'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'get_token_name()'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'is_tag_closer()'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' '$isTagCloser = $processor->is_tag_closer();'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'if (! $isTagCloser && ''H2'' !== $tokenName) {'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' '$elementId = $processor->get_attribute(''id'');'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'vg_is_valid_guide_heading_id($elementId)'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'get_modifiable_text()'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'paused_at_incomplete_token()'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' "get_attribute('data-vg-toc')"
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' "get_attribute('id')"
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'strcasecmp(trim('
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' "preg_replace('/\s+/u'"
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'if ($processor->paused_at_incomplete_token() || $currentHeading !== null) {'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'vg_is_valid_guide_heading_id($originalId)'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' '$reservedIds'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' '$assignedIds'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' '$plannedId = $originalId;'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'if (! isset($reservedIds[$originalId]) && ! isset($assignedIds[$originalId])) {'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' 'sanitize_title($label)'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' '$eligible = ! $heading[''opt_out''] && $label !== '''';'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' '$chromeStack = [];'
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' "has_class('vg-source-snapshot')"
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' "has_class('vg-source-diversity')"
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' "has_class('vg-related-routes')"
Require-FunctionContains $ContentProvider 'vg_collect_guide_heading_plan' '|| $chromeStack !== []'
Require-Contains "$ThemeRoot/assets/css/guide-patterns.css" '.vg-source-snapshot'
Require-Contains "$ThemeRoot/assets/css/guide-patterns.css" '.vg-source-diversity'
Require-FunctionContains $ContentProvider 'vg_apply_guide_heading_plan' "next_tag('H2')"
Require-FunctionContains $ContentProvider 'vg_apply_guide_heading_plan' "get_attribute('id')"
Require-FunctionContains $ContentProvider 'vg_apply_guide_heading_plan' 'if ($plannedId !== $currentId) {'
Require-FunctionContains $ContentProvider 'vg_apply_guide_heading_plan' "set_attribute('id', `$plannedId)"
Require-FunctionContains $ContentProvider 'vg_apply_guide_heading_plan' 'paused_at_incomplete_token()'
Require-FunctionContains $ContentProvider 'vg_apply_guide_heading_plan' '$headingIndex !== count($plan)'
Require-FunctionContains $ContentProvider 'vg_apply_guide_heading_plan' 'get_updated_html()'
Require-FunctionContains $ContentProvider 'vg_prepare_guide_headings' 'vg_collect_guide_heading_plan($html)'
Require-FunctionContains $ContentProvider 'vg_prepare_guide_headings' 'vg_apply_guide_heading_plan($html, $plan)'
Require-FunctionNotContains $ContentProvider 'vg_prepare_guide_headings' '<h2\b'
Require-FunctionNotContains $ContentProvider 'vg_prepare_guide_headings' 'preg_replace_callback('
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' "apply_filters('the_content', `$split['hero_source'])"
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' "apply_filters('the_content', `$split['body_source'])"
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' 'vg_inspect_guide_html((string) $heroHtml)'
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' 'vg_inspect_guide_html((string) $bodyHtml)'
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' "`$heroStats['has_hero_class']"
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' "`$heroStats['h1_count'] !== 1"
Require-FunctionContains $ContentProvider 'vg_prepare_guide_content' "`$bodyStats['h1_count'] !== 0"
Require-FunctionOrder $ContentProvider 'vg_prepare_guide_content' "apply_filters('the_content', `$split['hero_source'])" 'vg_inspect_guide_html((string) $heroHtml)'
Require-FunctionOrder $ContentProvider 'vg_prepare_guide_content' "apply_filters('the_content', `$split['body_source'])" 'vg_inspect_guide_html((string) $bodyHtml)'

Require-FunctionContains $ContextProvider 'vg_estimate_guide_reading_time' 'str_word_count(wp_strip_all_tags($html))'
Require-FunctionContains $ContextProvider 'vg_estimate_guide_reading_time' 'max(1, (int) ceil($words / 220))'
Require-FunctionContains $ContextProvider 'vg_extract_guide_data_value' 'WP_HTML_Tag_Processor'
Require-FunctionContains $ContextProvider 'vg_extract_guide_data_value' 'next_token()'
Require-FunctionContains $ContextProvider 'vg_extract_guide_data_value' 'is_tag_closer()'
Require-FunctionContains $ContextProvider 'vg_extract_guide_data_value' 'get_attribute($attribute)'
Require-FunctionContains $ContextProvider 'vg_extract_guide_data_value' 'sanitize_text_field'
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' "class_exists('DOMDocument')"
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' "class_exists('DOMXPath')"
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' 'contains(concat(" ", normalize-space(@class), " "), " vg-pattern-source-block ")'
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' 'contains(concat(" ", normalize-space(@class), " "), " source-diversity ")'
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' 'contains(concat(" ", normalize-space(@class), " "), " source-trail ")'
Require-FunctionNotContains $ContextProvider 'vg_count_guide_sources' 'contains(@class,'
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' 'wp_parse_url(home_url(''/'')'
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' '$scheme = strtolower((string) wp_parse_url($href, PHP_URL_SCHEME));'
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' "in_array(`$scheme, ['http', 'https'], true)"
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' "str_starts_with(`$href, '//')"
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' '$isAllowedScheme'
Require-FunctionContains $ContextProvider 'vg_count_guide_sources' '$sources[$href] = true;'
Require-FunctionOrder $ContextProvider 'vg_count_guide_sources' '$scheme = strtolower((string) wp_parse_url($href, PHP_URL_SCHEME));' '$isAllowedScheme'
Require-FunctionOrder $ContextProvider 'vg_count_guide_sources' '$isAllowedScheme' '$sources[$href] = true;'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' 'if ($url === '''') {'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "str_contains(`$url, '\\')"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "preg_match('/[\x00-\x20\x7F]/', `$url) === 1"
Require-FunctionNotContains $ContextProvider 'vg_normalize_guide_route_url' '$url = trim($url);'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' '$parts = wp_parse_url($url);'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' 'if (! is_array($parts)) {'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$scheme = strtolower((string) (`$parts['scheme'] ?? ''));"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$host = trim((string) (`$parts['host'] ?? ''));"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$isRootRelative = str_starts_with(`$url, '/') && ! str_starts_with(`$url, '//') && `$scheme === '' && `$host === '';"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$isProtocolRelative = str_starts_with(`$url, '//') && `$scheme === '' && `$host !== '';"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$isAbsoluteWeb = in_array(`$scheme, ['http', 'https'], true) && `$host !== '';"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' 'if (! $isRootRelative && ! $isProtocolRelative && ! $isAbsoluteWeb) {'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "esc_url_raw(`$url, ['http', 'https'])"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' 'if ($sanitized === '''') {'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' '$sanitizedParts = wp_parse_url($sanitized);'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' 'if (! is_array($sanitizedParts)) {'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$sanitizedScheme = strtolower((string) (`$sanitizedParts['scheme'] ?? ''));"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$sanitizedHost = trim((string) (`$sanitizedParts['host'] ?? ''));"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$sanitizedIsRootRelative = str_starts_with(`$sanitized, '/') && ! str_starts_with(`$sanitized, '//') && `$sanitizedScheme === '' && `$sanitizedHost === '';"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$sanitizedIsProtocolRelative = str_starts_with(`$sanitized, '//') && `$sanitizedScheme === '' && `$sanitizedHost !== '';"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' "`$sanitizedIsAbsoluteWeb = in_array(`$sanitizedScheme, ['http', 'https'], true) && `$sanitizedHost !== '';"
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' '$hasMatchingShape'
Require-FunctionContains $ContextProvider 'vg_normalize_guide_route_url' 'return $sanitized;'
Require-FunctionNotContains $ContextProvider 'vg_normalize_guide_route_url' 'javascript'
Require-FunctionNotContains $ContextProvider 'vg_normalize_guide_route_url' 'data:'
Require-FunctionNotContains $ContextProvider 'vg_normalize_guide_route_url' 'ftp'
Require-FunctionOrder $ContextProvider 'vg_normalize_guide_route_url' "str_contains(`$url, '\\')" '$parts = wp_parse_url($url);'
Require-FunctionOrder $ContextProvider 'vg_normalize_guide_route_url' "preg_match('/[\x00-\x20\x7F]/', `$url) === 1" '$parts = wp_parse_url($url);'
Require-FunctionOrder $ContextProvider 'vg_normalize_guide_route_url' '$isAbsoluteWeb' "esc_url_raw(`$url, ['http', 'https'])"
Require-FunctionOrder $ContextProvider 'vg_normalize_guide_route_url' "esc_url_raw(`$url, ['http', 'https'])" '$sanitizedParts = wp_parse_url($sanitized);'
Require-FunctionOrder $ContextProvider 'vg_normalize_guide_route_url' '$sanitizedParts = wp_parse_url($sanitized);' '$sanitizedIsRootRelative'
Require-FunctionOrder $ContextProvider 'vg_normalize_guide_route_url' '$sanitizedIsAbsoluteWeb' '$hasMatchingShape'
Require-FunctionOrder $ContextProvider 'vg_normalize_guide_route_url' '$hasMatchingShape' 'return $sanitized;'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' 'if ($hasExisting) {'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "function_exists('vg_eeat_get_field')"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "function_exists('vg_eeat_related_route_items')"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "vg_eeat_get_field(`$post->ID, 'related_routes')"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' 'vg_eeat_related_route_items('
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "`$title = trim((string) (`$item['label'] ?? ''));"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "`$rawUrl = `$item['url'] ?? '';"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$url = is_string($rawUrl) ? vg_normalize_guide_route_url($rawUrl) : '''';'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' 'if ($title === '''' || $url === '''') {'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$curatedRoutes[] = ['
Require-FunctionContains $ContextProvider 'vg_get_related_routes' 'if ($curatedRoutes !== []) {'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' 'return array_values($curatedRoutes);'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' 'if ($post->post_parent >= 0) {'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "'post_status' => 'publish'"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$title = get_the_title($sibling);'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$url = get_permalink($sibling);'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "if (`$title === '' || ! is_string(`$url) || `$url === '') {"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' 'if (count($routes) === 3) {'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$post->post_parent'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$parent = get_post($post->post_parent);'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$parent instanceof WP_Post'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$parent->post_type === ''page'''
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$parent->post_status === ''publish'''
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$parentTitle = get_the_title($parent);'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' '$parentUrl = get_permalink($parent);'
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "'title' => `$parentTitle"
Require-FunctionContains $ContextProvider 'vg_get_related_routes' "'url' => `$parentUrl"
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' 'if ($hasExisting) {' "function_exists('vg_eeat_get_field')"
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' "vg_eeat_get_field(`$post->ID, 'related_routes')" 'get_pages(['
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$url = is_string($rawUrl) ? vg_normalize_guide_route_url($rawUrl) : '''';' 'if ($title === '''' || $url === '''') {'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' 'if ($title === '''' || $url === '''') {' '$curatedRoutes[] = ['
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$curatedRoutes[] = [' 'if ($curatedRoutes !== []) {'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' 'if ($curatedRoutes !== []) {' 'return array_values($curatedRoutes);'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' 'return array_values($curatedRoutes);' '$routes = [];'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$curatedRoutes[] = [' 'return array_values($curatedRoutes);'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$title = get_the_title($sibling);' "if (`$title === '' || ! is_string(`$url) || `$url === '') {"
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' "if (`$title === '' || ! is_string(`$url) || `$url === '') {" '$routes[] = ['
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$routes[] = [' 'if (count($routes) === 3) {'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' 'if (count($routes) === 3) {' 'if ($routes === [] && $post->post_parent > 0) {'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$parent = get_post($post->post_parent);' '$parent->post_status === ''publish'''
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$parent->post_status === ''publish''' '$parentTitle = get_the_title($parent);'
Require-FunctionOrder $ContextProvider 'vg_get_related_routes' '$parentTitle = get_the_title($parent);' "'title' => `$parentTitle"
Require-FunctionContains $ContextProvider 'vg_guide_body_has_related_routes' 'WP_HTML_Tag_Processor'
Require-FunctionContains $ContextProvider 'vg_guide_body_has_related_routes' 'next_token()'
Require-FunctionContains $ContextProvider 'vg_guide_body_has_related_routes' 'is_tag_closer()'
Require-FunctionContains $ContextProvider 'vg_guide_body_has_related_routes' "has_class('vg-related-routes')"
Require-FunctionContains $ContextProvider 'vg_guide_body_has_related_routes' 'return true;'
Require-FunctionContains $ContextProvider 'vg_guide_body_has_related_routes' 'return false;'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "array_key_exists('post_id', `$context)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "is_int(`$context['post_id'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$context['post_id'] <= 0"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "in_array(`$context['type'], ['destination', 'itinerary', 'comparison', 'practical'], true)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "array_key_exists('hero_html', `$context)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "is_string(`$context['hero_html'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "trim(`$context['hero_html']) === ''"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '$stringFields = ['
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "'body_html'"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "'toc_html'"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "'reviewed_at'"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "'best_for'"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "'skip_if'"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "'title'"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "'permalink'"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' 'foreach ($stringFields as $field) {'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '! array_key_exists($field, $context)'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '! is_string($context[$field])'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$heroStats = vg_inspect_guide_html(`$context['hero_html']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$bodyStats = vg_inspect_guide_html(`$context['body_html']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '$heroStats === null'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '$bodyStats === null'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! `$heroStats['has_hero_class']"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$heroStats['h1_count'] !== 1"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$bodyStats['h1_count'] !== 0"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' '! is_string($context[$field])' "`$heroStats = vg_inspect_guide_html(`$context['hero_html']);"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' '$heroStats === null' "! `$heroStats['has_hero_class']"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' "! `$heroStats['has_hero_class']" "`$heroStats['h1_count'] !== 1"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "array_key_exists('headings', `$context)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "is_array(`$context['headings'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '$headingIds = [];'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "foreach (`$context['headings'] as `$heading) {"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '! is_array($heading)'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! array_key_exists('id', `$heading)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! array_key_exists('label', `$heading)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! is_string(`$heading['id'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! is_string(`$heading['label'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$headingId = `$heading['id'];"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$headingLabel = trim(`$heading['label']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '! vg_is_valid_guide_heading_id($headingId)'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$headingLabel === ''"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' 'isset($headingIds[$headingId])'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '$headingIds[$headingId] = true;'
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' "foreach (`$context['headings'] as `$heading) {" '$headingIds[$headingId] = true;'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$preparedBody = vg_prepare_guide_headings(`$context['body_html']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$preparedBody['html'] !== `$context['body_html']"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$preparedBody['headings'] !== `$context['headings']"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' '$headingIds[$headingId] = true;' "`$preparedBody = vg_prepare_guide_headings(`$context['body_html']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "if (`$context['toc_html'] !== vg_render_guide_toc(`$context['headings'])) {"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' "`$preparedBody['headings'] !== `$context['headings']" "if (`$context['toc_html'] !== vg_render_guide_toc(`$context['headings'])) {"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' '$headingIds[$headingId] = true;' "if (`$context['toc_html'] !== vg_render_guide_toc(`$context['headings'])) {"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "array_key_exists('reading_time', `$context)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "is_int(`$context['reading_time'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$context['reading_time'] < 1"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "array_key_exists('source_count', `$context)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "is_int(`$context['source_count'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$context['source_count'] < 0"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "array_key_exists('related_routes', `$context)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "is_array(`$context['related_routes'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "array_key_exists('has_existing_related_routes', `$context)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "is_bool(`$context['has_existing_related_routes'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "foreach (`$context['related_routes'] as `$route) {"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' '! is_array($route)'
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! array_key_exists('title', `$route)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! array_key_exists('url', `$route)"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! is_string(`$route['title'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "! is_string(`$route['url'])"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "trim(`$route['title']) === ''"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "trim(`$route['url']) === ''"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$normalizedRouteUrl = vg_normalize_guide_route_url(`$route['url']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$normalizedRouteUrl === ''"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$route['url'] !== `$normalizedRouteUrl"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' "trim(`$route['url']) === ''" "`$normalizedRouteUrl = vg_normalize_guide_route_url(`$route['url']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$hasExistingRelated = vg_guide_body_has_related_routes(`$context['body_html']);"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$context['has_existing_related_routes'] !== `$hasExistingRelated"
Require-FunctionContains $ContextProvider 'vg_is_valid_guide_context' "`$hasExistingRelated && `$context['related_routes'] !== []"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' "`$route['url'] !== `$normalizedRouteUrl" "`$hasExistingRelated = vg_guide_body_has_related_routes(`$context['body_html']);"
Require-FunctionOrder $ContextProvider 'vg_is_valid_guide_context' "foreach (`$context['related_routes'] as `$route) {" 'return true;'
Require-FunctionContains $ContextProvider 'vg_build_guide_context' 'vg_prepare_guide_content($post)'
Require-FunctionContains $ContextProvider 'vg_build_guide_context' 'vg_get_guide_type($post)'
Require-FunctionContains $ContextProvider 'vg_build_guide_context' 'post_password_required($post)'
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "`$post->post_password !== ''"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "preg_match('/<!--\s*nextpage\s*-->/i', `$post->post_content) === 1"
Require-FunctionOrder $ContextProvider 'vg_build_guide_context' 'post_password_required($post)' 'vg_prepare_guide_content($post)'
Require-FunctionOrder $ContextProvider 'vg_build_guide_context' "`$post->post_password !== ''" 'vg_prepare_guide_content($post)'
Require-FunctionOrder $ContextProvider 'vg_build_guide_context' "preg_match('/<!--\s*nextpage\s*-->/i', `$post->post_content) === 1" 'vg_prepare_guide_content($post)'
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "function_exists('vg_eeat_get_field')"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "vg_eeat_get_field(`$post->ID, 'last_meaningful_update')"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "get_post_meta(`$post->ID, '_vg_reviewed_at', true)"
Require-FunctionOrder $ContextProvider 'vg_build_guide_context' "vg_eeat_get_field(`$post->ID, 'last_meaningful_update')" "get_post_meta(`$post->ID, '_vg_reviewed_at', true)"
Require-FunctionOrder $ContextProvider 'vg_build_guide_context' "get_post_meta(`$post->ID, '_vg_reviewed_at', true)" "get_the_modified_date('F j, Y', `$post)"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "function_exists('vg_eeat_lines')"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "vg_eeat_lines(vg_eeat_get_field(`$post->ID, 'sources_checked'))"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' '$sourceCount = count($sourcesChecked);'
Require-FunctionContains $ContextProvider 'vg_build_guide_context' 'if ($sourceCount === 0) {'
Require-FunctionOrder $ContextProvider 'vg_build_guide_context' "vg_eeat_lines(vg_eeat_get_field(`$post->ID, 'sources_checked'))" 'vg_count_guide_sources($content[''body_html''])'
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "`$hasExistingRelated = vg_guide_body_has_related_routes(`$content['body_html']);"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "vg_extract_guide_data_value(`$content['body_html'], 'data-vg-best-for')"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "vg_extract_guide_data_value(`$content['body_html'], 'data-vg-skip-if')"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'post_id' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'type' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'title' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'permalink' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'hero_html' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'body_html' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'headings' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'toc_html' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'reviewed_at' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'reading_time' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'source_count' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'best_for' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'skip_if' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'related_routes' =>"
Require-FunctionContains $ContextProvider 'vg_build_guide_context' "'has_existing_related_routes' =>"

Require-File $HomepageCss
Require-File $GuideCss
Require-File $GuideJs
Require-Contains $Functions 'if (vg_is_guide_experience_page())'
Require-Matches $Functions "(?s)if\s*\(\s*vg_is_guide_experience_page\(\)\s*\)\s*\{[^{}]*wp_enqueue_style\s*\(\s*'vietnamguide-guide-experience'[^{}]*wp_enqueue_script\s*\(\s*'vietnamguide-guide-experience'[^{}]*\}" 'guide assets conditionally enqueued for guide experience pages'
Require-Matches $Functions "wp_enqueue_style\s*\(\s*'vietnamguide-guide-experience'" 'guide experience style handle'
Require-Matches $Functions "wp_enqueue_script\s*\(\s*'vietnamguide-guide-experience'" 'guide experience script handle'
Require-Contains $Functions 'function vg_theme_asset_version(string $relativePath): string'
Require-FunctionContains $Functions 'vg_theme_asset_version' 'static $versions = [];'
Require-FunctionContains $Functions 'vg_theme_asset_version' "str_replace('\\', '/', `$relativePath)"
Require-FunctionContains $Functions 'vg_theme_asset_version' "'assets/css/homepage.css'"
Require-FunctionContains $Functions 'vg_theme_asset_version' "'assets/css/guide-patterns.css'"
Require-FunctionContains $Functions 'vg_theme_asset_version' "'assets/js/homepage.js'"
Require-FunctionContains $Functions 'vg_theme_asset_version' "'assets/css/guide-experience.css'"
Require-FunctionContains $Functions 'vg_theme_asset_version' "'assets/js/guide-experience.js'"
Require-FunctionContains $Functions 'vg_theme_asset_version' "get_theme_file_path('/' . `$normalizedPath)"
Require-FunctionContains $Functions 'vg_theme_asset_version' "hash_file('sha256', `$assetPath)"
Require-FunctionContains $Functions 'vg_theme_asset_version' "wp_get_theme()->get('Version')"
Require-FunctionContains $Functions 'vg_theme_asset_version' 'array_key_exists($normalizedPath, $versions)'
Require-FunctionContains $Functions 'vg_theme_asset_version' 'preg_match(''/\A[a-f0-9]{64}\z/'''
Require-NotContains $Functions "`$version = wp_get_theme()->get('Version');"
Require-Matches $Functions '(?s)wp_enqueue_style\s*\(\s*''vietnamguide-homepage''\s*,\s*get_theme_file_uri\(\s*''/assets/css/homepage\.css''\s*\)\s*,\s*\[\s*\]\s*,\s*vg_theme_asset_version\(\s*''/assets/css/homepage\.css''\s*\)\s*\)' 'homepage style content-derived version'
Require-Matches $Functions '(?s)wp_enqueue_style\s*\(\s*''vietnamguide-guide-patterns''\s*,\s*get_theme_file_uri\(\s*''/assets/css/guide-patterns\.css''\s*\)\s*,\s*\[\s*''vietnamguide-homepage''\s*\]\s*,\s*vg_theme_asset_version\(\s*''/assets/css/guide-patterns\.css''\s*\)\s*\)' 'guide patterns style content-derived version'
Require-Matches $Functions '(?s)wp_enqueue_script\s*\(\s*''vietnamguide-homepage''\s*,\s*get_theme_file_uri\(\s*''/assets/js/homepage\.js''\s*\)\s*,\s*\[\s*\]\s*,\s*vg_theme_asset_version\(\s*''/assets/js/homepage\.js''\s*\)\s*,\s*true\s*\)' 'homepage script content-derived version and footer loading'
Require-Matches $Functions '(?s)wp_enqueue_style\s*\(\s*''vietnamguide-guide-experience''\s*,\s*get_theme_file_uri\(\s*''/assets/css/guide-experience\.css''\s*\)\s*,\s*\[\s*''vietnamguide-guide-patterns''\s*\]\s*,\s*vg_theme_asset_version\(\s*''/assets/css/guide-experience\.css''\s*\)\s*\)' 'guide style dependency and content-derived version'
Require-Matches $Functions '(?s)wp_enqueue_script\s*\(\s*''vietnamguide-guide-experience''\s*,\s*get_theme_file_uri\(\s*''/assets/js/guide-experience\.js''\s*\)\s*,\s*\[\s*\]\s*,\s*vg_theme_asset_version\(\s*''/assets/js/guide-experience\.js''\s*\)\s*,\s*true\s*\)' 'guide script empty dependencies, content-derived version, and footer loading'

$KeyGuideSelectors = @(
    '.vg-guide-experience::before',
    '.vg-guide-experience .vg-guide-hero-cover',
    '.vg-guide-experience .vg-guide-hero-inner',
    '.vg-guide-experience .vg-guide-title',
    '.vg-guide-meta',
    '.vg-guide-jump',
    '.vg-guide-spine',
    '.vg-guide-trust',
    '.vg-guide-article h2',
    '.vg-guide-article p',
    '.vg-guide-related',
    '.vg-guide-article .vg-concierge-verdict',
    '.vg-guide-article .vg-at-a-glance',
    '.vg-guide-article .vg-field-note',
    '.vg-guide-article .vg-related-cards',
    '.vg-guide-article .vg-decision-table__scroll',
    '.vg-guide-article .vg-timeline',
    '.vg-guide-article .vg-guide-photo-grid',
    '.vg-guide-article .vg-check-list',
    '.vg-guide-article .vg-feature-list',
    '.vg-guide-article .vg-faq-list',
    '.vg-guide-article .vg-travel-guide-flow',
    '.vg-guide-article .vg-travel-route-family'
)
foreach ($Selector in $KeyGuideSelectors) {
    Require-Contains $GuideCss $Selector
}

Require-Contains $GuideCss 'grid-template-columns: minmax(148px, 190px) minmax(0, 760px) minmax(190px, 240px)'
Require-Contains $GuideCss '@media (max-width: 1100px)'
Require-Contains $GuideCss '@media (max-width: 960px)'
Require-Contains $GuideCss '@media (max-width: 620px)'
Require-Contains $GuideCss '@media (prefers-reduced-motion: reduce)'
Require-Contains $GuideCss ':focus-visible'
Require-CssBlockContains $HomepageCss '@media (prefers-reduced-motion: reduce)' 'scroll-behavior: auto !important;'
Require-Matches $HomepageCss '(?s)@media\s*\(prefers-reduced-motion:\s*reduce\)\s*\{.*?html\s*\{\s*scroll-behavior:\s*auto\s*!important;' 'explicit reduced-motion override for root smooth scrolling'
Require-CssBlockContains $GuideCss '.vg-guide-experience {' 'overflow-x: clip;'
Require-CssBlockContains $GuideCss '.vg-guide-experience::before {' 'height: 3px;'
Require-CssBlockContains $GuideCss '.vg-guide-experience::before {' 'background: var(--vg-gold);'
Require-CssBlockContains $GuideCss '.vg-guide-experience .vg-guide-hero-cover {' 'align-items: flex-end;'
Require-CssBlockContains $GuideCss '.vg-guide-experience .vg-guide-hero-cover {' 'min-height: clamp(520px, 70svh, 780px);'
Require-CssBlockContains $GuideCss '.vg-guide-meta {' 'text-transform: uppercase;'
Require-CssBlockContains $GuideCss '.vg-guide-article h2 {' 'font-size: clamp(34px, 4vw, 56px);'
Require-CssBlockContains $GuideCss '.vg-guide-article h2 {' 'scroll-margin-top: calc(var(--vg-header-height) + 24px);'
Require-CssBlockContains $GuideCss '.vg-guide-related h2 {' 'font-size: clamp(36px, 5vw, 64px);'
Require-CssBlockContains $GuideCss '.vg-guide-experience a:focus-visible,' 'outline: 3px solid var(--vg-gold);'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll,' 'overflow-x: auto;'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll,' 'border: 1px solid var(--vg-line);'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll,' 'margin-block: 36px;'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll:focus-visible,' 'outline: 3px solid var(--vg-gold);'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll > table,' 'width: 100%;'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll > table,' 'min-width: 680px;'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll > table,' 'margin: 0;'
Require-CssBlockContains $GuideCss '.vg-guide-article .vg-decision-table__scroll > table,' 'border-collapse: collapse;'
Require-NotContains $GuideCss '.vg-guide-article .vg-decision-table,'
Require-CssBlockContains $GuideCss '@media (max-width: 1100px)' 'grid-template-columns: minmax(136px, 170px) minmax(0, 1fr);'
Require-CssBlockContains $GuideCss '@media (max-width: 1100px)' 'grid-column: 2;'
Require-CssBlockContains $GuideCss '@media (max-width: 960px)' '.vg-guide-spine__toc'
Require-CssBlockContains $GuideCss '@media (max-width: 960px)' 'grid-template-columns: minmax(0, 1fr);'
Require-CssBlockContains $GuideCss '@media (max-width: 620px)' '.vg-guide-article .vg-guide-photo-grid'
Require-CssBlockContains $GuideCss '@media (max-width: 620px)' 'grid-template-columns: 1fr;'
Require-CssBlockContains $GuideCss '@media (prefers-reduced-motion: reduce)' 'scroll-behavior: auto !important;'
Require-CssBlockContains $GuideCss '@media (prefers-reduced-motion: reduce)' 'transition: none !important;'
Require-CssBlockContains $GuideCss '@media (prefers-reduced-motion: reduce)' 'animation: none !important;'
Require-CssBlockContains $GuideCss '@media (prefers-reduced-motion: reduce)' 'transform: none;'
Require-GuideCssScoped $GuideCss
Require-GuideCssScopeSelfTest

$HomepageCssContent = Get-RepoContent $HomepageCss
$GuideCssContent = Get-RepoContent $GuideCss
if ($null -ne $HomepageCssContent -and $null -ne $GuideCssContent) {
    $DesktopHeaderMatch = [regex]::Match($HomepageCssContent, '(?s):root\s*\{.*?--vg-header-height:\s*(?<height>\d+)px;')
    $MobileHeaderMatch = [regex]::Match($HomepageCssContent, '(?s)@media\s*\(max-width:\s*760px\)\s*\{.*?:root\s*\{.*?--vg-header-height:\s*(?<height>\d+)px;')
    $HeadingOffsetMatch = [regex]::Match($GuideCssContent, '(?s)\.vg-guide-article h2\s*\{.*?scroll-margin-top:\s*calc\(var\(--vg-header-height\)\s*\+\s*(?<gap>\d+)px\);')
    if (-not $DesktopHeaderMatch.Success -or -not $MobileHeaderMatch.Success -or -not $HeadingOffsetMatch.Success) {
        $Failures.Add('Guide fragment offset fixture could not resolve desktop, mobile, and heading offset values')
    } else {
        $DesktopHeader = [int]$DesktopHeaderMatch.Groups['height'].Value
        $MobileHeader = [int]$MobileHeaderMatch.Groups['height'].Value
        $HeadingGap = [int]$HeadingOffsetMatch.Groups['gap'].Value
        if ($HeadingGap -lt 16 -or ($DesktopHeader + $HeadingGap) -le $DesktopHeader -or ($MobileHeader + $HeadingGap) -le $MobileHeader) {
            $Failures.Add('Guide fragment offset fixture did not clear the sticky header with sufficient breathing room')
        }
    }
}

Require-Contains $GuideJs "document.querySelector('[data-vg-guide]')"
Require-Contains $GuideJs "guide.querySelectorAll('table.vg-decision-table')"
Require-Contains $GuideJs "table.parentElement.classList.contains('vg-decision-table__scroll')"
Require-Matches $GuideJs "(?s)if\s*\(\s*table.parentElement\s*&&\s*table.parentElement.classList.contains\('vg-decision-table__scroll'\)\s*\)\s*\{\s*return;\s*\}" 'already wrapped legacy tables are skipped'
Require-Contains $GuideJs "document.createElement('div')"
Require-Contains $GuideJs "wrapper.className = 'vg-decision-table__scroll';"
Require-Contains $GuideJs "wrapper.setAttribute('tabindex', '0');"
Require-Contains $GuideJs 'table.parentNode.insertBefore(wrapper, table);'
Require-Contains $GuideJs 'wrapper.appendChild(table);'
Require-Contains $GuideJs "guide.querySelectorAll('.vg-decision-table__scroll, .wp-block-table')"
Require-Contains $GuideJs "scrollContainer.hasAttribute('tabindex')"
Require-Contains $GuideJs "scrollContainer.setAttribute('tabindex', '0');"
Require-Contains $GuideJs "guide.querySelectorAll('.vg-guide-toc a[href^=`"#`"], .vg-guide-jump a[href^=`"#`"]')"
Require-Contains $GuideJs 'document.getElementById(id)'
Require-Contains $GuideJs 'IntersectionObserver'
Require-Contains $GuideJs "classList.toggle('is-active'"
Require-Contains $GuideJs "setAttribute('aria-current', 'location')"
Require-Contains $GuideJs "removeAttribute('aria-current')"
Require-Contains $GuideJs 'var activeId = null;'
Require-Contains $GuideJs 'if (id === activeId) {'
Require-Contains $GuideJs 'var lastSection = sections.length > 0 ? sections[sections.length - 1] : null;'
Require-Contains $GuideJs 'var nearGuideEnd = false;'
Require-Contains $GuideJs 'progress >= 99.5'
Require-Contains $GuideJs 'rect.bottom <= window.innerHeight * 1.15'
Require-Contains $GuideJs 'setActive(lastSection.id);'
Require-Contains $GuideJs 'Math.max(0, Math.min(100'
Require-Contains $GuideJs 'window.requestAnimationFrame(updateProgress)'
Require-Contains $GuideJs '{ passive: true }'
Require-Contains $GuideJs "style.setProperty('--vg-guide-progress'"
Require-Contains $GuideJs 'updateProgress();'
Require-NotContains $GuideJs 'preventDefault()'
Require-NotContains $GuideJs 'innerHTML'
Require-NotContains $GuideJs 'outerHTML'
Require-NotContains $GuideJs 'cloneNode'

$NodeCommand = Get-Command node -ErrorAction SilentlyContinue
if ($null -eq $NodeCommand) {
    $Failures.Add('Node.js is required for the guide JavaScript runtime fixture')
} else {
    $PreviousErrorActionPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $GuideJsRuntimeOutput = & $NodeCommand.Source (Join-Path $RepoRoot $GuideJsRuntimeVerifier) (Join-Path $RepoRoot $GuideJs) 2>&1
        $GuideJsRuntimeExitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $PreviousErrorActionPreference
    }
    if ($GuideJsRuntimeExitCode -ne 0) {
        $Failures.Add("Guide JavaScript runtime fixture failed: $($GuideJsRuntimeOutput -join ' ')")
    }
}

if ($Failures.Count -gt 0) {
    $Failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output 'VietnamGuide guide experience checks passed.'

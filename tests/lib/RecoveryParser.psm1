Set-StrictMode -Version 2.0

function New-RecoveryParserDiagnostic {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)]
        [string]$Code,

        [Parameter(Mandatory = $true)]
        [string]$Message,

        [Parameter(Mandatory = $true)]
        [string]$Language,

        [Parameter(Mandatory = $true)]
        [int]$SourceLine,

        [Parameter(Mandatory = $true)]
        [int]$SourceColumn,

        [string]$FenceId = $null
    )

    $fenceIdValue = if ($PSBoundParameters.ContainsKey('FenceId')) { $FenceId } else { $null }

    [pscustomobject]@{
        Code = $Code
        Message = $Message
        Language = $Language
        SourceLine = $SourceLine
        SourceColumn = $SourceColumn
        FenceId = $fenceIdValue
    }
}

function New-RecoveryExecutableEvent {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)]
        [string]$Kind,

        [Parameter(Mandatory = $true)]
        [string]$Language,

        [Parameter(Mandatory = $true)]
        [string]$Text,

        [string]$NormalizedCommand = $null,

        [Parameter(Mandatory = $true)]
        [int]$SourceLine,

        [Parameter(Mandatory = $true)]
        [int]$SourceColumn,

        [string]$SectionId = $null,

        [Parameter(Mandatory = $true)]
        [string]$FenceId,

        [Parameter(Mandatory = $true)]
        [string]$StatementId,

        [hashtable]$Metadata = @{}
    )

    $normalizedCommandValue = if ($PSBoundParameters.ContainsKey('NormalizedCommand')) { $NormalizedCommand } else { $null }
    $sectionIdValue = if ($PSBoundParameters.ContainsKey('SectionId')) { $SectionId } else { $null }

    [pscustomobject]@{
        Kind = $Kind
        Language = $Language
        Text = $Text
        NormalizedCommand = $normalizedCommandValue
        SourceLine = $SourceLine
        SourceColumn = $SourceColumn
        SectionId = $sectionIdValue
        FenceId = $FenceId
        StatementId = $StatementId
        Metadata = $Metadata
    }
}

function New-RecoveryParseResult {
    [CmdletBinding()]
    param(
        [object[]]$Events = @(),

        [object[]]$Diagnostics = @()
    )

    $filteredEvents = @($Events | Where-Object { $null -ne $_ })
    $filteredDiagnostics = @($Diagnostics | Where-Object { $null -ne $_ })

    [pscustomobject]@{
        IsValid = ($filteredDiagnostics.Count -eq 0)
        Events = $filteredEvents
        Diagnostics = $filteredDiagnostics
    }
}

function ConvertTo-RecoveryEventLfText {
    param(
        [AllowNull()]
        [string]$Text
    )

    if ($null -eq $Text) {
        return ''
    }

    # Event queries compare ordinal text after normalizing CRLF and CR to LF.
    return $Text.Replace("`r`n", "`n").Replace("`r", "`n")
}

function Find-RecoveryExecutableEvents {
    [CmdletBinding()]
    param(
        [AllowNull()]
        [object[]]$Events,

        [AllowNull()]
        [string]$ExactText,

        [AllowNull()]
        [string]$Language = $null
    )

    $normalizedExactText = ConvertTo-RecoveryEventLfText -Text $ExactText
    $hasLanguageFilter = $PSBoundParameters.ContainsKey('Language') -and -not [string]::IsNullOrEmpty($Language)
    $matches = [System.Collections.Generic.List[object]]::new()
    foreach ($candidate in @($Events)) {
        if ($null -eq $candidate) {
            continue
        }
        if (
            $hasLanguageFilter -and
            -not [string]::Equals([string]$candidate.Language, $Language, [System.StringComparison]::Ordinal)
        ) {
            continue
        }

        $candidateText = ConvertTo-RecoveryEventLfText -Text ([string]$candidate.Text)
        if ([string]::Equals($candidateText, $normalizedExactText, [System.StringComparison]::Ordinal)) {
            $matches.Add($candidate)
        }
    }

    return $matches.ToArray()
}

function Test-RecoveryEventSequence {
    [CmdletBinding()]
    param(
        [AllowNull()]
        [object[]]$Events,

        [AllowNull()]
        [string[]]$ExpectedTexts,

        [switch]$RequireUnique
    )

    $eventArray = @($Events | Where-Object { $null -ne $_ })
    $expectedTextArray = if ($null -eq $ExpectedTexts) { @() } else { @($ExpectedTexts) }
    $cursor = 0
    foreach ($expectedText in $expectedTextArray) {
        if ($RequireUnique -and @(Find-RecoveryExecutableEvents -Events $eventArray -ExactText $expectedText).Count -ne 1) {
            return $false
        }

        $normalizedExpectedText = ConvertTo-RecoveryEventLfText -Text $expectedText
        $found = $false
        while ($cursor -lt $eventArray.Count) {
            $candidateText = ConvertTo-RecoveryEventLfText -Text ([string]$eventArray[$cursor].Text)
            $cursor++
            if ([string]::Equals($candidateText, $normalizedExpectedText, [System.StringComparison]::Ordinal)) {
                $found = $true
                break
            }
        }
        if (-not $found) {
            return $false
        }
    }

    return $true
}

function Get-RecoveryPowerShellConfiguredCommands {
    param(
        [Parameter(Mandatory = $true)]
        [AllowEmptyCollection()]
        [string[]]$NativeCommandNames
    )

    $configured = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::OrdinalIgnoreCase)
    foreach ($commandName in @($NativeCommandNames)) {
        if ([string]::IsNullOrWhiteSpace($commandName)) {
            continue
        }

        $leafName = [System.IO.Path]::GetFileName($commandName).ToLowerInvariant()
        if ($leafName.EndsWith('.exe', [System.StringComparison]::OrdinalIgnoreCase)) {
            $leafName = $leafName.Substring(0, $leafName.Length - 4)
        }
        if (-not [string]::IsNullOrWhiteSpace($leafName)) {
            [void]$configured.Add($leafName)
        }
    }

    return ,$configured
}

function Test-RecoveryPowerShellOrdinaryVariablePath {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.VariablePath]$VariablePath
    )

    if (-not $VariablePath.IsUnqualified) {
        return $false
    }

    return @(
        '$',
        '?',
        '^',
        '_',
        'args',
        'confirmpreference',
        'consolefilename',
        'debugpreference',
        'error',
        'erroractionpreference',
        'errorview',
        'event',
        'eventargs',
        'eventsubscriber',
        'executioncontext',
        'false',
        'foreach',
        'formatenumerationlimit',
        'home',
        'host',
        'informationpreference',
        'input',
        'lastexitcode',
        'logcommandhealthevent',
        'logcommandlifecycleevent',
        'logenginehealthevent',
        'logenginelifecycleevent',
        'logproviderhealthevent',
        'logproviderlifecycleevent',
        'matches',
        'maximumaliascount',
        'maximumdrivecount',
        'maximumerrorcount',
        'maximumfunctioncount',
        'maximumhistorycount',
        'maximumvariablecount',
        'myinvocation',
        'nestedpromptlevel',
        'null',
        'ofs',
        'outputencoding',
        'pid',
        'profile',
        'progresspreference',
        'psboundparameters',
        'pscmdlet',
        'pscommandpath',
        'psculture',
        'psdebugcontext',
        'psdefaultparametervalues',
        'psedition',
        'psemailserver',
        'pshome',
        'psitem',
        'psmoduleautoloadingpreference',
        'pssenderinfo',
        'psscriptroot',
        'pssessionapplicationname',
        'pssessionconfigurationname',
        'pssessionoption',
        'psuiculture',
        'psversiontable',
        'pwd',
        'shellid',
        'stacktrace',
        'switch',
        'this',
        'transcript',
        'true',
        'verbosepreference',
        'warningpreference',
        'whatifpreference'
    ) -inotcontains $VariablePath.UserPath
}

function Get-RecoveryPowerShellLiteralCommandResolution {
    param(
        [Parameter(Mandatory = $true)]
        [string]$LiteralCommandName,

        [Parameter(Mandatory = $true)]
        [AllowEmptyCollection()]
        [System.Collections.Generic.HashSet[string]]$ConfiguredCommands
    )

    $leafName = [System.IO.Path]::GetFileName($LiteralCommandName).ToLowerInvariant()
    if (
        $leafName -eq 'cmd' -or
        $leafName -eq 'cmd.exe' -or
        $leafName.EndsWith('.cmd', [System.StringComparison]::OrdinalIgnoreCase) -or
        $leafName.EndsWith('.bat', [System.StringComparison]::OrdinalIgnoreCase) -or
        $leafName.EndsWith('.com', [System.StringComparison]::OrdinalIgnoreCase)
    ) {
        return [pscustomobject]@{ Classification = 'wrapper'; NormalizedCommand = $null; LeafName = $leafName }
    }

    $normalizedCommand = $leafName
    if ($normalizedCommand.EndsWith('.exe', [System.StringComparison]::OrdinalIgnoreCase)) {
        $normalizedCommand = $normalizedCommand.Substring(0, $normalizedCommand.Length - 4)
    }
    if ($ConfiguredCommands.Contains($normalizedCommand)) {
        return [pscustomobject]@{ Classification = 'native'; NormalizedCommand = $normalizedCommand; LeafName = $leafName }
    }

    return [pscustomobject]@{ Classification = 'ignore'; NormalizedCommand = $null; LeafName = $leafName }
}

function Get-RecoveryPowerShellCanonicalCommandLeaf {
    param(
        [AllowNull()]
        [string]$LiteralCommandName
    )

    if ([string]::IsNullOrWhiteSpace($LiteralCommandName)) {
        return $null
    }

    $leafName = ([regex]::Replace($LiteralCommandName, '^.*[\\/]', '')).ToLowerInvariant()
    $builtInAliases = @{
        'sal' = 'set-alias'
        'nal' = 'new-alias'
        'ipal' = 'import-alias'
        'si' = 'set-item'
        'ni' = 'new-item'
        'copy' = 'copy-item'
        'cp' = 'copy-item'
        'cpi' = 'copy-item'
        'mi' = 'move-item'
        'move' = 'move-item'
        'mv' = 'move-item'
        'ren' = 'rename-item'
        'rni' = 'rename-item'
        'cli' = 'clear-item'
        'del' = 'remove-item'
        'erase' = 'remove-item'
        'rd' = 'remove-item'
        'ri' = 'remove-item'
        'rm' = 'remove-item'
        'rmdir' = 'remove-item'
        'sc' = 'set-content'
        'gi' = 'get-item'
        'gv' = 'get-variable'
        'set' = 'set-variable'
        'sv' = 'set-variable'
        'nv' = 'new-variable'
        'clv' = 'clear-variable'
        'cv' = 'clear-variable'
        'rv' = 'remove-variable'
    }
    if ($builtInAliases.ContainsKey($leafName)) {
        return $builtInAliases[$leafName]
    }

    return $leafName
}

function Test-RecoveryPowerShellCommandUsesProvider {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.CommandAst]$Command,

        [Parameter(Mandatory = $true)]
        [string[]]$ProviderNames
    )

    foreach ($element in @($Command.CommandElements | Select-Object -Skip 1)) {
        if (
            $element -isnot [System.Management.Automation.Language.StringConstantExpressionAst] -and
            $element -isnot [System.Management.Automation.Language.ExpandableStringExpressionAst]
        ) {
            continue
        }
        foreach ($providerName in $ProviderNames) {
            if ([string]$element.Value -match ('^{0}:[\\/]*' -f [regex]::Escape($providerName))) {
                return $true
            }
        }
    }

    return $false
}

function Test-RecoveryPowerShellNewItemDirectory {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.CommandAst]$Command
    )

    $elements = @($Command.CommandElements)
    for ($index = 1; $index -lt $elements.Count; $index++) {
        $element = $elements[$index]
        if (
            $element -isnot [System.Management.Automation.Language.CommandParameterAst] -or
            $element.ParameterName -ine 'ItemType'
        ) {
            continue
        }

        $argument = $element.Argument
        if ($null -eq $argument -and ($index + 1) -lt $elements.Count) {
            $argument = $elements[$index + 1]
        }
        return (
            $argument -is [System.Management.Automation.Language.StringConstantExpressionAst] -and
            $argument.Value -ieq 'Directory'
        )
    }

    return $false
}

function Test-RecoveryPowerShellConfiguredCommandLiteral {
    param(
        [Parameter(Mandatory = $true)]
        [string]$LiteralCommandName,

        [Parameter(Mandatory = $true)]
        [AllowEmptyCollection()]
        [System.Collections.Generic.HashSet[string]]$ConfiguredCommands
    )

    $resolution = Get-RecoveryPowerShellLiteralCommandResolution -LiteralCommandName $LiteralCommandName -ConfiguredCommands $ConfiguredCommands
    return $resolution.Classification -eq 'native'
}

function Get-RecoveryPowerShellShadowingNodes {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.ScriptBlockAst]$Ast,

        [Parameter(Mandatory = $true)]
        [AllowEmptyCollection()]
        [System.Collections.Generic.HashSet[string]]$ConfiguredCommands
    )

    $shadowingNodes = [System.Collections.Generic.List[object]]::new()
    $candidates = @($Ast.FindAll({
        param($node)
        $node -is [System.Management.Automation.Language.CommandAst] -or
        $node -is [System.Management.Automation.Language.FunctionDefinitionAst] -or
        $node -is [System.Management.Automation.Language.AssignmentStatementAst]
    }, $true) | Sort-Object -Property @{ Expression = { $_.Extent.StartOffset }; Ascending = $true })

    $hasLiteralConfiguredNativeEvents = $false
    $hasDynamicPhpExecutableEvent = $false
    foreach ($candidate in $candidates) {
        if ($candidate -isnot [System.Management.Automation.Language.CommandAst]) {
            continue
        }

        $literalCommandName = $candidate.GetCommandName()
        $elements = @($candidate.CommandElements)
        if (
            $literalCommandName -and
            (Test-RecoveryPowerShellConfiguredCommandLiteral -LiteralCommandName $literalCommandName -ConfiguredCommands $ConfiguredCommands)
        ) {
            $hasLiteralConfiguredNativeEvents = $true
        }
        if (
            $candidate.InvocationOperator -eq [System.Management.Automation.Language.TokenKind]::Ampersand -and
            $elements.Count -gt 0 -and
            $elements[0] -is [System.Management.Automation.Language.VariableExpressionAst] -and
            $elements[0].VariablePath.UserPath -ieq 'PhpExecutable' -and
            (Test-RecoveryPowerShellOrdinaryVariablePath -VariablePath $elements[0].VariablePath)
        ) {
            $hasDynamicPhpExecutableEvent = $true
        }
    }
    $hasConfiguredNativeEvents = $hasLiteralConfiguredNativeEvents -or $hasDynamicPhpExecutableEvent

    foreach ($candidate in $candidates) {
        if ($candidate -is [System.Management.Automation.Language.AssignmentStatementAst]) {
            $providerVariables = @($candidate.Left.FindAll({
                param($node)
                $node -is [System.Management.Automation.Language.VariableExpressionAst] -and
                @('Alias', 'Function') -icontains $node.VariablePath.DriveName
            }, $true))
            foreach ($providerVariable in $providerVariables) {
                $providerTarget = [string]$providerVariable.VariablePath.UserPath
                $providerTarget = $providerTarget.Substring($providerTarget.IndexOf(':') + 1)
                $providerTarget = [regex]::Replace($providerTarget, '^[\\/]+', '')
                $providerTarget = [regex]::Replace($providerTarget, '^(?i:global|local|private|script):', '')
                if (Test-RecoveryPowerShellConfiguredCommandLiteral -LiteralCommandName $providerTarget -ConfiguredCommands $ConfiguredCommands) {
                    $shadowingNodes.Add($candidate)
                    break
                }
            }
            continue
        }

        if ($candidate -is [System.Management.Automation.Language.FunctionDefinitionAst]) {
            $functionName = [string]$candidate.Name
            $functionName = [regex]::Replace($functionName, '^(?i:global|local|private|script):', '')
            if (Test-RecoveryPowerShellConfiguredCommandLiteral -LiteralCommandName $functionName -ConfiguredCommands $ConfiguredCommands) {
                $shadowingNodes.Add($candidate)
            }
            continue
        }

        if (-not $hasConfiguredNativeEvents) {
            continue
        }

        $commandName = Get-RecoveryPowerShellCanonicalCommandLeaf -LiteralCommandName $candidate.GetCommandName()
        if (@('set-alias', 'new-alias', 'import-alias') -icontains $commandName) {
            $shadowingNodes.Add($candidate)
            continue
        }
        if ($commandName -ieq 'new-item') {
            if (
                $hasLiteralConfiguredNativeEvents -and
                -not (Test-RecoveryPowerShellNewItemDirectory -Command $candidate)
            ) {
                $shadowingNodes.Add($candidate)
            }
            continue
        }
        if (
            $commandName -ieq 'set-content' -and
            (Test-RecoveryPowerShellCommandUsesProvider -Command $candidate -ProviderNames @('Alias', 'Function'))
        ) {
            $shadowingNodes.Add($candidate)
            continue
        }
        if (
            @('set-item', 'copy-item', 'move-item', 'rename-item') -icontains $commandName -and
            ($hasLiteralConfiguredNativeEvents -or
                (Test-RecoveryPowerShellCommandUsesProvider -Command $candidate -ProviderNames @('Alias', 'Function')))
        ) {
            $shadowingNodes.Add($candidate)
            continue
        }
        if (
            @('clear-item', 'remove-item') -icontains $commandName -and
            (Test-RecoveryPowerShellCommandUsesProvider -Command $candidate -ProviderNames @('Alias', 'Function'))
        ) {
            $shadowingNodes.Add($candidate)
        }
    }

    return $shadowingNodes.ToArray()
}

function Test-RecoveryPowerShellAssignmentTargetsPhpExecutable {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.AssignmentStatementAst]$Assignment
    )

    return @($Assignment.Left.FindAll({
        param($node)
        $node -is [System.Management.Automation.Language.VariableExpressionAst] -and
        $node.VariablePath.UserPath -ieq 'PhpExecutable'
    }, $true)).Count -gt 0
}

function Test-RecoveryPowerShellPhpExecutableMutationCommand {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.CommandAst]$Command
    )

    $commandName = Get-RecoveryPowerShellCanonicalCommandLeaf -LiteralCommandName $Command.GetCommandName()
    if (@('get-variable', 'set-variable', 'new-variable', 'clear-variable', 'remove-variable') -icontains $commandName) {
        return $true
    }

    if (@('set-item', 'copy-item', 'move-item', 'rename-item') -icontains $commandName) {
        return $true
    }
    if ($commandName -ieq 'new-item') {
        return -not (Test-RecoveryPowerShellNewItemDirectory -Command $Command)
    }
    if (@('get-item', 'clear-item', 'remove-item') -inotcontains $commandName) {
        return $false
    }
    return Test-RecoveryPowerShellCommandUsesProvider -Command $Command -ProviderNames @('Variable')
}

function Test-RecoveryPowerShellAssignmentTargetsValueMember {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.AssignmentStatementAst]$Assignment
    )

    return @($Assignment.Left.FindAll({
        param($node)
        $node -is [System.Management.Automation.Language.MemberExpressionAst] -and
        $node.Member -is [System.Management.Automation.Language.StringConstantExpressionAst] -and
        $node.Member.Value -ieq 'Value'
    }, $true)).Count -gt 0
}

function Test-RecoveryPowerShellPhpExecutableReference {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.ConvertExpressionAst]$Expression
    )

    return (
        $Expression.Type.TypeName.FullName -ieq 'ref' -and
        $Expression.Child -is [System.Management.Automation.Language.VariableExpressionAst] -and
        $Expression.Child.VariablePath.UserPath -ieq 'PhpExecutable'
    )
}

function Get-RecoveryPowerShellScriptBlockScope {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.Ast]$Ast
    )

    $ancestor = $Ast
    while ($null -ne $ancestor -and $ancestor -isnot [System.Management.Automation.Language.ScriptBlockAst]) {
        $ancestor = $ancestor.Parent
    }

    return $ancestor
}

function Test-RecoveryPowerShellAssignmentDominatesCommand {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.AssignmentStatementAst]$Assignment,

        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.CommandAst]$Command
    )

    [System.Management.Automation.Language.Ast]$current = $Command
    while ($null -ne $current.Parent) {
        [System.Management.Automation.Language.Ast]$statement = $current
        while (
            $null -ne $statement.Parent -and
            $statement.Parent -isnot [System.Management.Automation.Language.NamedBlockAst] -and
            $statement.Parent -isnot [System.Management.Automation.Language.StatementBlockAst]
        ) {
            $statement = $statement.Parent
        }
        if ($null -eq $statement.Parent) {
            return $false
        }

        $statements = @($statement.Parent.Statements)
        $assignmentIndex = -1
        $statementIndex = -1
        for ($index = 0; $index -lt $statements.Count; $index++) {
            if ([object]::ReferenceEquals($statements[$index], $Assignment)) {
                $assignmentIndex = $index
            }
            if ([object]::ReferenceEquals($statements[$index], $statement)) {
                $statementIndex = $index
            }
        }
        if ($assignmentIndex -ge 0) {
            return $statementIndex -ge 0 -and $assignmentIndex -lt $statementIndex
        }

        $current = $statement.Parent
    }

    return $false
}

function Get-RecoveryPowerShellPhpExecutableAssignmentState {
    param(
        [Parameter(Mandatory = $true)]
        [AllowEmptyCollection()]
        [object[]]$Assignments,

        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.CommandAst]$Command
    )

    $commandScope = Get-RecoveryPowerShellScriptBlockScope -Ast $Command
    $ancestor = $Command.Parent
    while ($null -ne $ancestor) {
        if ($ancestor -is [System.Management.Automation.Language.LoopStatementAst]) {
            foreach ($assignment in $Assignments) {
                if (
                    [object]::ReferenceEquals((Get-RecoveryPowerShellScriptBlockScope -Ast $assignment), $commandScope) -and
                    $assignment.Extent.StartOffset -ge $ancestor.Extent.StartOffset -and
                    $assignment.Extent.EndOffset -le $ancestor.Extent.EndOffset
                ) {
                    return [pscustomobject]@{ IsStatic = $false; LiteralCommandName = $null }
                }
            }
        }
        $ancestor = $ancestor.Parent
    }

    $latestAssignment = $null
    foreach ($assignment in $Assignments) {
        if ($assignment.Extent.StartOffset -ge $Command.Extent.StartOffset) {
            break
        }
        if (-not [object]::ReferenceEquals((Get-RecoveryPowerShellScriptBlockScope -Ast $assignment), $commandScope)) {
            continue
        }
        $latestAssignment = $assignment
    }
    if ($null -eq $latestAssignment) {
        return [pscustomobject]@{ IsStatic = $false; LiteralCommandName = $null }
    }

    $rightExpression = if ($latestAssignment.Right -is [System.Management.Automation.Language.CommandExpressionAst]) {
        $latestAssignment.Right.Expression
    }
    else {
        $null
    }
    if (
        $latestAssignment.Operator -eq [System.Management.Automation.Language.TokenKind]::Equals -and
        $latestAssignment.Left -is [System.Management.Automation.Language.VariableExpressionAst] -and
        $latestAssignment.Left.VariablePath.UserPath -ieq 'PhpExecutable' -and
        (Test-RecoveryPowerShellOrdinaryVariablePath -VariablePath $latestAssignment.Left.VariablePath) -and
        $rightExpression -is [System.Management.Automation.Language.StringConstantExpressionAst] -and
        (Test-RecoveryPowerShellAssignmentDominatesCommand -Assignment $latestAssignment -Command $Command)
    ) {
        return [pscustomobject]@{ IsStatic = $true; LiteralCommandName = [string]$rightExpression.Value }
    }

    return [pscustomobject]@{ IsStatic = $false; LiteralCommandName = $null }
}

function Get-RecoveryPowerShellCommandResolution {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.CommandAst]$Command,

        [Parameter(Mandatory = $true)]
        [AllowEmptyCollection()]
        [System.Collections.Generic.HashSet[string]]$ConfiguredCommands,

        $PhpExecutableAssignment = $null
    )

    $invocationOperator = $Command.InvocationOperator
    $commandElements = @($Command.CommandElements)
    $firstElement = if ($commandElements.Count -gt 0) { $commandElements[0] } else { $null }
    if (
        $invocationOperator -ne [System.Management.Automation.Language.TokenKind]::Unknown -and
        $invocationOperator -ne [System.Management.Automation.Language.TokenKind]::Ampersand
    ) {
        return [pscustomobject]@{ Classification = 'dynamic'; NormalizedCommand = $null; LeafName = $null }
    }

    if (
        $invocationOperator -eq [System.Management.Automation.Language.TokenKind]::Ampersand -and
        $firstElement -is [System.Management.Automation.Language.VariableExpressionAst]
    ) {
        if (
            $firstElement.VariablePath.UserPath -ine 'PhpExecutable' -or
            -not (Test-RecoveryPowerShellOrdinaryVariablePath -VariablePath $firstElement.VariablePath) -or
            $null -eq $PhpExecutableAssignment -or
            -not $PhpExecutableAssignment.IsStatic
        ) {
            return [pscustomobject]@{ Classification = 'dynamic'; NormalizedCommand = $null; LeafName = $null }
        }

        $assignmentResolution = Get-RecoveryPowerShellLiteralCommandResolution -LiteralCommandName $PhpExecutableAssignment.LiteralCommandName -ConfiguredCommands $ConfiguredCommands
        if ($assignmentResolution.Classification -eq 'ignore') {
            return [pscustomobject]@{ Classification = 'dynamic'; NormalizedCommand = $null; LeafName = $assignmentResolution.LeafName }
        }
        return $assignmentResolution
    }

    $literalCommandName = $Command.GetCommandName()
    if (
        -not $literalCommandName -and
        $invocationOperator -eq [System.Management.Automation.Language.TokenKind]::Ampersand -and
        $firstElement -is [System.Management.Automation.Language.StringConstantExpressionAst]
    ) {
        $literalCommandName = [string]$firstElement.Value
    }
    if (-not $literalCommandName) {
        return [pscustomobject]@{ Classification = 'dynamic'; NormalizedCommand = $null; LeafName = $null }
    }

    return Get-RecoveryPowerShellLiteralCommandResolution -LiteralCommandName $literalCommandName -ConfiguredCommands $ConfiguredCommands
}

function Get-RecoveryPowerShellStatementContext {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.CommandAst]$Command
    )

    [System.Management.Automation.Language.Ast]$statement = $Command
    while (
        $null -ne $statement.Parent -and
        $statement.Parent -isnot [System.Management.Automation.Language.NamedBlockAst] -and
        $statement.Parent -isnot [System.Management.Automation.Language.StatementBlockAst]
    ) {
        $statement = $statement.Parent
    }
    if ($null -eq $statement.Parent) {
        return $null
    }

    $statements = @($statement.Parent.Statements)
    for ($index = 0; $index -lt $statements.Count; $index++) {
        if ([object]::ReferenceEquals($statements[$index], $statement)) {
            return [pscustomobject]@{
                Statement = $statement
                Statements = $statements
                Index = $index
            }
        }
    }

    return $null
}

function Test-RecoveryPowerShellNativeStatementShape {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.CommandAst]$Command,

        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.Ast]$Statement,

        [Parameter(Mandatory = $true)]
        [string]$CommandName
    )

    $ancestor = $Command.Parent
    while ($null -ne $ancestor) {
        if (
            $ancestor -is [System.Management.Automation.Language.SubExpressionAst] -or
            $ancestor -is [System.Management.Automation.Language.ScriptBlockExpressionAst] -or
            $ancestor -is [System.Management.Automation.Language.ArrayExpressionAst] -or
            $ancestor -is [System.Management.Automation.Language.FunctionDefinitionAst]
        ) {
            return $false
        }
        $ancestor = $ancestor.Parent
    }

    $pipeline = $Command.Parent
    if (
        $pipeline -isnot [System.Management.Automation.Language.PipelineAst] -or
        @($pipeline.PipelineElements).Count -ne 1
    ) {
        return $false
    }
    if ([object]::ReferenceEquals($pipeline, $Statement)) {
        return $true
    }
    if (
        $Statement -isnot [System.Management.Automation.Language.AssignmentStatementAst] -or
        $CommandName -ine 'git' -or
        $Statement.Operator -ne [System.Management.Automation.Language.TokenKind]::Equals -or
        $Statement.Left -isnot [System.Management.Automation.Language.VariableExpressionAst] -or
        -not (Test-RecoveryPowerShellOrdinaryVariablePath -VariablePath $Statement.Left.VariablePath)
    ) {
        return $false
    }
    if ([object]::ReferenceEquals($Statement.Right, $pipeline)) {
        return $true
    }

    $parenthesized = $pipeline.Parent
    $memberCall = if ($null -ne $parenthesized) { $parenthesized.Parent } else { $null }
    $commandExpression = if ($null -ne $memberCall) { $memberCall.Parent } else { $null }
    return (
        $parenthesized -is [System.Management.Automation.Language.ParenExpressionAst] -and
        $memberCall -is [System.Management.Automation.Language.InvokeMemberExpressionAst] -and
        $memberCall.Member.Value -ceq 'Trim' -and
        $null -eq $memberCall.Arguments -and
        $commandExpression -is [System.Management.Automation.Language.CommandExpressionAst] -and
        [object]::ReferenceEquals($commandExpression.Parent, $Statement) -and
        [object]::ReferenceEquals($Statement.Right, $commandExpression)
    )
}

function Test-RecoveryPowerShellEnclosingFailureCanContinue {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.CommandAst]$Command
    )

    $ancestor = $Command.Parent
    while ($null -ne $ancestor) {
        if (
            ($ancestor -is [System.Management.Automation.Language.StatementBlockAst] -or
                $ancestor -is [System.Management.Automation.Language.NamedBlockAst]) -and
            $null -ne $ancestor.Traps
        ) {
            return $true
        }
        if (
            $ancestor -is [System.Management.Automation.Language.TryStatementAst] -and
            $ancestor.CatchClauses.Count -gt 0
        ) {
            return $true
        }
        $ancestor = $ancestor.Parent
    }

    return $false
}

function Test-RecoveryPowerShellCondition {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.PipelineBaseAst]$Condition,

        [Parameter(Mandatory = $true)]
        [string]$VariableName,

        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.TokenKind]$Operator,

        [Parameter(Mandatory = $true)]
        [int]$Value
    )

    if ($Condition -isnot [System.Management.Automation.Language.PipelineAst]) {
        return $false
    }
    $pipelineElements = @($Condition.PipelineElements)
    if (
        $pipelineElements.Count -ne 1 -or
        $pipelineElements[0] -isnot [System.Management.Automation.Language.CommandExpressionAst]
    ) {
        return $false
    }

    $expression = $pipelineElements[0].Expression
    return (
        $expression -is [System.Management.Automation.Language.BinaryExpressionAst] -and
        $expression.Operator -eq $Operator -and
        $expression.Left -is [System.Management.Automation.Language.VariableExpressionAst] -and
        $expression.Left.VariablePath.UserPath -ieq $VariableName -and
        $expression.Right -is [System.Management.Automation.Language.ConstantExpressionAst] -and
        $expression.Right.Value -eq $Value
    )
}

function Test-RecoveryPowerShellIntegerValue {
    param($Value)

    return (
        $Value -is [byte] -or
        $Value -is [sbyte] -or
        $Value -is [int16] -or
        $Value -is [uint16] -or
        $Value -is [int32] -or
        $Value -is [uint32] -or
        $Value -is [int64] -or
        $Value -is [uint64]
    )
}

function Test-RecoveryPowerShellBlockingStatementBlock {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.StatementBlockAst]$StatementBlock,

        [string]$CapturedExitVariableName = $null
    )

    $statements = @($StatementBlock.Statements)
    if ($null -ne $StatementBlock.Traps -or $statements.Count -ne 1) {
        return $false
    }
    if ($statements[0] -is [System.Management.Automation.Language.ThrowStatementAst]) {
        return $true
    }
    if ($statements[0] -isnot [System.Management.Automation.Language.ExitStatementAst]) {
        return $false
    }

    $pipeline = $statements[0].Pipeline
    if ($null -eq $pipeline) {
        return $false
    }
    $expression = $pipeline.GetPureExpression()
    if (
        $CapturedExitVariableName -and
        $expression -is [System.Management.Automation.Language.VariableExpressionAst] -and
        $expression.VariablePath.UserPath -ieq $CapturedExitVariableName
    ) {
        return $true
    }
    if ($expression -is [System.Management.Automation.Language.ConstantExpressionAst]) {
        return (Test-RecoveryPowerShellIntegerValue -Value $expression.Value) -and $expression.Value -ne 0
    }
    if (
        $expression -isnot [System.Management.Automation.Language.UnaryExpressionAst] -or
        @(
            [System.Management.Automation.Language.TokenKind]::Minus,
            [System.Management.Automation.Language.TokenKind]::Plus
        ) -notcontains $expression.TokenKind -or
        $expression.Child -isnot [System.Management.Automation.Language.ConstantExpressionAst]
    ) {
        return $false
    }

    return (
        (Test-RecoveryPowerShellIntegerValue -Value $expression.Child.Value) -and
        $expression.Child.Value -ne 0
    )
}

function Get-RecoveryPowerShellCapturedExitVariableName {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.StatementAst]$Statement
    )

    if (
        $Statement -isnot [System.Management.Automation.Language.AssignmentStatementAst] -or
        $Statement.Operator -ne [System.Management.Automation.Language.TokenKind]::Equals -or
        $Statement.Left -isnot [System.Management.Automation.Language.VariableExpressionAst] -or
        $Statement.Right -isnot [System.Management.Automation.Language.CommandExpressionAst] -or
        $Statement.Right.Expression -isnot [System.Management.Automation.Language.VariableExpressionAst] -or
        $Statement.Right.Expression.VariablePath.UserPath -ine 'LASTEXITCODE'
    ) {
        return $null
    }

    if (-not (Test-RecoveryPowerShellOrdinaryVariablePath -VariablePath $Statement.Left.VariablePath)) {
        return $null
    }

    return $Statement.Left.VariablePath.UserPath
}

function Get-RecoveryPowerShellStandardGuardAnalysis {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.StatementAst]$Statement
    )

    if (
        $Statement -isnot [System.Management.Automation.Language.IfStatementAst] -or
        @($Statement.Clauses).Count -ne 1 -or
        $null -ne $Statement.ElseClause -or
        -not (Test-RecoveryPowerShellCondition -Condition $Statement.Clauses[0].Item1 -VariableName 'LASTEXITCODE' -Operator ([System.Management.Automation.Language.TokenKind]::Ine) -Value 0)
    ) {
        return [pscustomobject]@{ Matched = $false; Blocking = $false }
    }

    return [pscustomobject]@{
        Matched = $true
        Blocking = Test-RecoveryPowerShellBlockingStatementBlock -StatementBlock $Statement.Clauses[0].Item2
    }
}

function Get-RecoveryPowerShellCapturedGuardAnalysis {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.StatementAst]$Statement,

        [Parameter(Mandatory = $true)]
        [string]$CapturedExitVariableName
    )

    if ($Statement -isnot [System.Management.Automation.Language.IfStatementAst]) {
        return [pscustomobject]@{ Matched = $false; Blocking = $false }
    }

    if (
        @($Statement.Clauses).Count -eq 1 -and
        $null -eq $Statement.ElseClause -and
        (Test-RecoveryPowerShellCondition -Condition $Statement.Clauses[0].Item1 -VariableName $CapturedExitVariableName -Operator ([System.Management.Automation.Language.TokenKind]::Ine) -Value 0)
    ) {
        return [pscustomobject]@{
            Matched = $true
            Blocking = Test-RecoveryPowerShellBlockingStatementBlock -StatementBlock $Statement.Clauses[0].Item2 -CapturedExitVariableName $CapturedExitVariableName
        }
    }

    if (
        @($Statement.Clauses).Count -eq 2 -and
        $null -ne $Statement.ElseClause -and
        (Test-RecoveryPowerShellCondition -Condition $Statement.Clauses[0].Item1 -VariableName $CapturedExitVariableName -Operator ([System.Management.Automation.Language.TokenKind]::Ieq) -Value 0) -and
        (Test-RecoveryPowerShellCondition -Condition $Statement.Clauses[1].Item1 -VariableName $CapturedExitVariableName -Operator ([System.Management.Automation.Language.TokenKind]::Ieq) -Value 1)
    ) {
        return [pscustomobject]@{
            Matched = $true
            Blocking = Test-RecoveryPowerShellBlockingStatementBlock -StatementBlock $Statement.ElseClause
        }
    }

    return [pscustomobject]@{ Matched = $false; Blocking = $false }
}

function Get-RecoveryPowerShellImmediateGuardAnalysis {
    param(
        [Parameter(Mandatory = $true)]
        [object[]]$Statements,

        [Parameter(Mandatory = $true)]
        [int]$CommandIndex
    )

    $nextIndex = $CommandIndex + 1
    if ($nextIndex -ge $Statements.Count) {
        return [pscustomobject]@{ Matched = $false; Blocking = $false }
    }

    $standardGuard = Get-RecoveryPowerShellStandardGuardAnalysis -Statement $Statements[$nextIndex]
    if ($standardGuard.Matched) {
        return $standardGuard
    }

    $capturedExitVariableName = Get-RecoveryPowerShellCapturedExitVariableName -Statement $Statements[$nextIndex]
    $guardIndex = $nextIndex + 1
    if (-not $capturedExitVariableName -or $guardIndex -ge $Statements.Count) {
        return [pscustomobject]@{ Matched = $false; Blocking = $false }
    }

    return Get-RecoveryPowerShellCapturedGuardAnalysis -Statement $Statements[$guardIndex] -CapturedExitVariableName $capturedExitVariableName
}

function New-RecoveryPowerShellExtentDiagnostic {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Code,

        [Parameter(Mandatory = $true)]
        [string]$Message,

        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Language.IScriptExtent]$Extent,

        [Parameter(Mandatory = $true)]
        $Fence
    )

    $bodyLine = [Math]::Max(1, [int]$Extent.StartLineNumber)
    $bodyColumn = [Math]::Max(1, [int]$Extent.StartColumnNumber)
    New-RecoveryParserDiagnostic -Code $Code -Message $Message -Language 'powershell' -SourceLine ([int]$Fence.StartLine + $bodyLine) -SourceColumn $bodyColumn -FenceId $Fence.Id
}

function ConvertFrom-RecoveryPowerShellFence {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)]
        $Fence,

        [Parameter(Mandatory = $true)]
        [ValidateNotNullOrEmpty()]
        [ValidatePattern('\S')]
        [string[]]$NativeCommandNames
    )

    $tokens = $null
    $parseErrors = $null
    $ast = [System.Management.Automation.Language.Parser]::ParseInput(
        [string]$Fence.Body,
        [ref]$tokens,
        [ref]$parseErrors
    )
    if (@($parseErrors).Count -gt 0) {
        $parseDiagnostics = [System.Collections.Generic.List[object]]::new()
        foreach ($parseError in @($parseErrors)) {
            $parseDiagnostics.Add((New-RecoveryPowerShellExtentDiagnostic -Code 'PS_PARSE_ERROR' -Message $parseError.Message -Extent $parseError.Extent -Fence $Fence))
        }
        return New-RecoveryParseResult -Diagnostics $parseDiagnostics.ToArray()
    }

    $configuredCommands = Get-RecoveryPowerShellConfiguredCommands -NativeCommandNames $NativeCommandNames
    $events = [System.Collections.Generic.List[object]]::new()
    $diagnostics = [System.Collections.Generic.List[object]]::new()
    $phpExecutableAssignments = @($ast.FindAll({
        param($node)
        $node -is [System.Management.Automation.Language.AssignmentStatementAst] -and
        (Test-RecoveryPowerShellAssignmentTargetsPhpExecutable -Assignment $node)
    }, $true) | Sort-Object -Property @{ Expression = { $_.Extent.StartOffset }; Ascending = $true })
    $commands = @($ast.FindAll({
        param($node)
        $node -is [System.Management.Automation.Language.CommandAst]
    }, $true) | Sort-Object -Property @{ Expression = { $_.Extent.StartOffset }; Ascending = $true }, @{ Expression = { $_.Extent.EndOffset }; Ascending = $true })

    $shadowingNodes = @(Get-RecoveryPowerShellShadowingNodes -Ast $ast -ConfiguredCommands $configuredCommands)
    if ($shadowingNodes.Count -gt 0) {
        foreach ($shadowingNode in $shadowingNodes) {
            $diagnostics.Add((New-RecoveryPowerShellExtentDiagnostic -Code 'PS_NATIVE_STATEMENT_AMBIGUOUS' -Message 'PowerShell native command can be shadowed within the same fence.' -Extent $shadowingNode.Extent -Fence $Fence))
        }
        return New-RecoveryParseResult -Diagnostics $diagnostics.ToArray()
    }

    $phpExecutableInvocations = @($commands | Where-Object {
        $elements = @($_.CommandElements)
        $_.InvocationOperator -eq [System.Management.Automation.Language.TokenKind]::Ampersand -and
        $elements.Count -gt 0 -and
        $elements[0] -is [System.Management.Automation.Language.VariableExpressionAst] -and
        $elements[0].VariablePath.UserPath -ieq 'PhpExecutable' -and
        (Test-RecoveryPowerShellOrdinaryVariablePath -VariablePath $elements[0].VariablePath)
    })
    $phpExecutableMutationCommands = @($commands | Where-Object {
        Test-RecoveryPowerShellPhpExecutableMutationCommand -Command $_
    })
    $phpExecutableForeachVariables = @($ast.FindAll({
        param($node)
        $node -is [System.Management.Automation.Language.ForEachStatementAst] -and
        $node.Variable.VariablePath.UserPath -imatch '^(?:[^:]+:)?PhpExecutable$'
    }, $true))
    $phpExecutableIndirectMutationNodes = @($ast.FindAll({
        param($node)
        ($node -is [System.Management.Automation.Language.AssignmentStatementAst] -and
            (Test-RecoveryPowerShellAssignmentTargetsValueMember -Assignment $node)) -or
        ($node -is [System.Management.Automation.Language.ConvertExpressionAst] -and
            (Test-RecoveryPowerShellPhpExecutableReference -Expression $node))
    }, $true))
    if (
        $phpExecutableInvocations.Count -gt 0 -and
        ($phpExecutableAssignments.Count -ne 1 -or
            $phpExecutableMutationCommands.Count -gt 0 -or
            $phpExecutableForeachVariables.Count -gt 0 -or
            $phpExecutableIndirectMutationNodes.Count -gt 0)
    ) {
        foreach ($phpExecutableInvocation in $phpExecutableInvocations) {
            $diagnostics.Add((New-RecoveryPowerShellExtentDiagnostic -Code 'PS_DYNAMIC_NATIVE_UNSUPPORTED' -Message 'PowerShell dynamic native invocation is unsupported.' -Extent $phpExecutableInvocation.Extent -Fence $Fence))
        }
        return New-RecoveryParseResult -Diagnostics $diagnostics.ToArray()
    }

    foreach ($command in $commands) {
        $phpExecutableAssignment = Get-RecoveryPowerShellPhpExecutableAssignmentState -Assignments $phpExecutableAssignments -Command $command
        $resolution = Get-RecoveryPowerShellCommandResolution -Command $command -ConfiguredCommands $configuredCommands -PhpExecutableAssignment $phpExecutableAssignment
        if ($resolution.Classification -eq 'ignore') {
            continue
        }
        if ($resolution.Classification -eq 'wrapper') {
            $diagnostics.Add((New-RecoveryPowerShellExtentDiagnostic -Code 'PS_UNSUPPORTED_NATIVE_WRAPPER' -Message "PowerShell native wrapper '$($resolution.LeafName)' is unsupported." -Extent $command.Extent -Fence $Fence))
            continue
        }
        if ($resolution.Classification -eq 'dynamic') {
            $diagnostics.Add((New-RecoveryPowerShellExtentDiagnostic -Code 'PS_DYNAMIC_NATIVE_UNSUPPORTED' -Message 'PowerShell dynamic native invocation is unsupported.' -Extent $command.Extent -Fence $Fence))
            continue
        }

        $context = Get-RecoveryPowerShellStatementContext -Command $command
        if (
            $null -eq $context -or
            -not (Test-RecoveryPowerShellNativeStatementShape -Command $command -Statement $context.Statement -CommandName $resolution.NormalizedCommand)
        ) {
            $diagnostics.Add((New-RecoveryPowerShellExtentDiagnostic -Code 'PS_NATIVE_STATEMENT_AMBIGUOUS' -Message 'PowerShell native command statement shape is ambiguous.' -Extent $command.Extent -Fence $Fence))
            continue
        }

        $guardAnalysis = Get-RecoveryPowerShellImmediateGuardAnalysis -Statements $context.Statements -CommandIndex $context.Index
        if (-not $guardAnalysis.Matched) {
            $diagnostics.Add((New-RecoveryPowerShellExtentDiagnostic -Code 'PS_NATIVE_GUARD_MISSING' -Message 'PowerShell native command is missing an immediate blocking guard.' -Extent $command.Extent -Fence $Fence))
            continue
        }
        if (
            -not $guardAnalysis.Blocking -or
            (Test-RecoveryPowerShellEnclosingFailureCanContinue -Command $command)
        ) {
            $diagnostics.Add((New-RecoveryPowerShellExtentDiagnostic -Code 'PS_NATIVE_GUARD_NONBLOCKING' -Message 'PowerShell native command guard does not block failure.' -Extent $command.Extent -Fence $Fence))
            continue
        }

        $eventText = $command.Extent.Text.Trim().Replace("`r`n", "`n").Replace("`r", "`n")
        $eventParameters = @{
            Kind = 'command'
            Language = 'powershell'
            Text = $eventText
            NormalizedCommand = $resolution.NormalizedCommand
            SourceLine = [int]$Fence.StartLine + [int]$command.Extent.StartLineNumber
            SourceColumn = [int]$command.Extent.StartColumnNumber
            FenceId = $Fence.Id
            StatementId = '{0}-statement-{1:D4}' -f $Fence.Id, ($events.Count + 1)
            Metadata = @{}
        }
        if ($null -ne $Fence.SectionId) {
            $eventParameters.SectionId = $Fence.SectionId
        }
        $events.Add((New-RecoveryExecutableEvent @eventParameters))
    }

    return New-RecoveryParseResult -Events $events.ToArray() -Diagnostics $diagnostics.ToArray()
}

function Get-RecoveryBashFirstContentColumn {
    param(
        [Parameter(Mandatory = $true)]
        [AllowEmptyString()]
        [string]$Line
    )

    for ($index = 0; $index -lt $Line.Length; $index++) {
        if (-not [char]::IsWhiteSpace($Line[$index])) {
            return $index + 1
        }
    }

    return 1
}

function Get-RecoveryBashPhysicalLineAnalysis {
    param(
        [Parameter(Mandatory = $true)]
        [AllowEmptyString()]
        [string]$Line,

        [bool]$InitialCommentEligible = $true
    )

    $inSingleQuote = $false
    $inDoubleQuote = $false
    $commentEligible = $InitialCommentEligible
    $index = 0
    while ($index -lt $Line.Length) {
        $character = $Line[$index]
        if ($inSingleQuote) {
            if ($character -ceq "'") {
                $inSingleQuote = $false
            }
            $index++
            continue
        }

        if ($inDoubleQuote) {
            if ($character -ceq '\' -and ($index + 1) -lt $Line.Length) {
                $index += 2
                continue
            }
            if ($character -ceq '"') {
                $inDoubleQuote = $false
            }
            $index++
            continue
        }

        if ($character -ceq "'") {
            $inSingleQuote = $true
            $commentEligible = $false
            $index++
            continue
        }
        if ($character -ceq '"') {
            $inDoubleQuote = $true
            $commentEligible = $false
            $index++
            continue
        }
        if ($character -ceq '\') {
            if (($index + 1) -eq $Line.Length) {
                return [pscustomobject]@{
                    HasContinuation = $true
                    ContinuationColumn = $index + 1
                    CommentEligible = $commentEligible
                }
            }

            $commentEligible = $false
            $index += 2
            continue
        }
        if ($character -ceq '#' -and $commentEligible) {
            break
        }
        if ([char]::IsWhiteSpace($character) -or ';|&()<>'.Contains([string]$character)) {
            $commentEligible = $true
        }
        else {
            $commentEligible = $false
        }
        $index++
    }

    return [pscustomobject]@{
        HasContinuation = $false
        ContinuationColumn = 0
        CommentEligible = $commentEligible
    }
}

function Get-RecoveryBashArithmeticEnd {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Line,

        [Parameter(Mandatory = $true)]
        [int]$ContentIndex
    )

    $depth = 1
    $index = $ContentIndex
    while ($index -lt $Line.Length) {
        $character = $Line[$index]
        if ($character -ceq '\' -and ($index + 1) -lt $Line.Length) {
            $index += 2
            continue
        }
        if ($character -ceq '(') {
            $depth++
            $index++
            continue
        }
        if ($character -ceq ')') {
            if ($depth -eq 1 -and ($index + 1) -lt $Line.Length -and $Line[$index + 1] -ceq ')') {
                return $index + 2
            }

            $depth--
            if ($depth -lt 1) {
                return -1
            }
        }
        $index++
    }

    return -1
}

function New-RecoveryBashStatementFailure {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Code,

        [Parameter(Mandatory = $true)]
        [string]$Message,

        [Parameter(Mandatory = $true)]
        [int]$ErrorIndex
    )

    [pscustomobject]@{
        IsValid = $false
        Code = $Code
        Message = $Message
        ErrorIndex = $ErrorIndex
        Redirections = @()
    }
}

function Get-RecoveryBashStatementAnalysis {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Line
    )

    $redirections = [System.Collections.Generic.List[object]]::new()
    $inSingleQuote = $false
    $singleQuoteIndex = -1
    $inDoubleQuote = $false
    $doubleQuoteIndex = -1
    $commentEligible = $true
    $index = 0
    while ($index -lt $Line.Length) {
        $character = $Line[$index]
        if ($inSingleQuote) {
            if ($character -ceq "'") {
                $inSingleQuote = $false
            }
            $index++
            continue
        }

        if ($inDoubleQuote) {
            if ($character -ceq '\' -and ($index + 1) -lt $Line.Length) {
                $index += 2
                continue
            }
            if ($character -ceq '$' -and ($index + 2) -lt $Line.Length -and
                $Line[$index + 1] -ceq '(' -and $Line[$index + 2] -ceq '(') {
                $arithmeticEnd = Get-RecoveryBashArithmeticEnd -Line $Line -ContentIndex ($index + 3)
                if ($arithmeticEnd -lt 0) {
                    return New-RecoveryBashStatementFailure -Code 'BASH_INVALID_ARITHMETIC' -Message 'Bash arithmetic expansion is not balanced.' -ErrorIndex $index
                }

                $index = $arithmeticEnd
                continue
            }
            if ($character -ceq '"') {
                $inDoubleQuote = $false
            }
            $index++
            continue
        }

        if ($character -ceq "'") {
            $inSingleQuote = $true
            $singleQuoteIndex = $index
            $commentEligible = $false
            $index++
            continue
        }
        if ($character -ceq '"') {
            $inDoubleQuote = $true
            $doubleQuoteIndex = $index
            $commentEligible = $false
            $index++
            continue
        }
        if ($character -ceq '\') {
            if (($index + 1) -lt $Line.Length) {
                $commentEligible = $false
                $index += 2
                continue
            }

            return New-RecoveryBashStatementFailure -Code 'BASH_UNFINISHED_CONTINUATION' -Message 'Bash statement ends with an unfinished unquoted continuation.' -ErrorIndex $index
        }
        if ($character -ceq '#' -and $commentEligible) {
            break
        }
        if ($character -ceq '$' -and ($index + 2) -lt $Line.Length -and
            $Line[$index + 1] -ceq '(' -and $Line[$index + 2] -ceq '(') {
            $arithmeticEnd = Get-RecoveryBashArithmeticEnd -Line $Line -ContentIndex ($index + 3)
            if ($arithmeticEnd -lt 0) {
                return New-RecoveryBashStatementFailure -Code 'BASH_INVALID_ARITHMETIC' -Message 'Bash arithmetic expansion is not balanced.' -ErrorIndex $index
            }

            $commentEligible = $false
            $index = $arithmeticEnd
            continue
        }
        if ($character -ceq '(' -and ($index + 1) -lt $Line.Length -and $Line[$index + 1] -ceq '(') {
            $arithmeticEnd = Get-RecoveryBashArithmeticEnd -Line $Line -ContentIndex ($index + 2)
            if ($arithmeticEnd -lt 0) {
                return New-RecoveryBashStatementFailure -Code 'BASH_INVALID_ARITHMETIC' -Message 'Bash arithmetic command is not balanced.' -ErrorIndex $index
            }

            $commentEligible = $false
            $index = $arithmeticEnd
            continue
        }
        if ($character -ceq '<') {
            $runLength = 1
            while (($index + $runLength) -lt $Line.Length -and $Line[$index + $runLength] -ceq '<') {
                $runLength++
            }

            if ($runLength -ge 4) {
                return New-RecoveryBashStatementFailure -Code 'BASH_AMBIGUOUS_REDIRECTION' -Message 'Bash redirection contains an ambiguous run of less-than characters.' -ErrorIndex $index
            }
            if ($runLength -eq 3) {
                $commentEligible = $true
                $index += 3
                continue
            }
            if ($runLength -eq 2) {
                $delimiterIndex = $index + 2
                $stripTabs = $false
                if ($delimiterIndex -lt $Line.Length -and $Line[$delimiterIndex] -ceq '-') {
                    $stripTabs = $true
                    $delimiterIndex++
                }
                while ($delimiterIndex -lt $Line.Length -and [char]::IsWhiteSpace($Line[$delimiterIndex])) {
                    $delimiterIndex++
                }

                $delimiter = [System.Text.StringBuilder]::new()
                $delimiterQuote = [char]0
                $delimiterQuoteIndex = -1
                $delimiterWordStarted = $false
                while ($delimiterIndex -lt $Line.Length) {
                    $delimiterCharacter = $Line[$delimiterIndex]
                    if ($delimiterQuote -ne [char]0) {
                        if ($delimiterCharacter -ceq $delimiterQuote) {
                            $delimiterQuote = [char]0
                            $delimiterIndex++
                            continue
                        }
                        if ($delimiterQuote -ceq '"' -and $delimiterCharacter -ceq '\' -and ($delimiterIndex + 1) -lt $Line.Length) {
                            $nextDelimiterCharacter = $Line[$delimiterIndex + 1]
                            if ($nextDelimiterCharacter -ceq '$' -or
                                $nextDelimiterCharacter -ceq ([char]96) -or
                                $nextDelimiterCharacter -ceq '"' -or
                                $nextDelimiterCharacter -ceq '\') {
                                [void]$delimiter.Append($nextDelimiterCharacter)
                                $delimiterIndex += 2
                                continue
                            }

                            [void]$delimiter.Append($delimiterCharacter)
                            $delimiterIndex++
                            continue
                        }

                        [void]$delimiter.Append($delimiterCharacter)
                        $delimiterIndex++
                        continue
                    }

                    if ($delimiterCharacter -ceq "'" -or $delimiterCharacter -ceq '"') {
                        $delimiterQuote = $delimiterCharacter
                        $delimiterQuoteIndex = $delimiterIndex
                        $delimiterWordStarted = $true
                        $delimiterIndex++
                        continue
                    }
                    if ($delimiterCharacter -ceq '\') {
                        if (($delimiterIndex + 1) -ge $Line.Length) {
                            return New-RecoveryBashStatementFailure -Code 'BASH_AMBIGUOUS_REDIRECTION' -Message 'Bash heredoc delimiter ends with an unfinished escape.' -ErrorIndex $delimiterIndex
                        }

                        $delimiterIndex++
                        [void]$delimiter.Append($Line[$delimiterIndex])
                        $delimiterWordStarted = $true
                        $delimiterIndex++
                        continue
                    }
                    if ($delimiterCharacter -ceq '#' -and -not $delimiterWordStarted) {
                        return New-RecoveryBashStatementFailure -Code 'BASH_AMBIGUOUS_REDIRECTION' -Message 'Bash heredoc redirection is followed by a comment instead of a delimiter.' -ErrorIndex $index
                    }
                    if ([char]::IsWhiteSpace($delimiterCharacter) -or ';|&()<>'.Contains([string]$delimiterCharacter)) {
                        break
                    }

                    [void]$delimiter.Append($delimiterCharacter)
                    $delimiterWordStarted = $true
                    $delimiterIndex++
                }

                if ($delimiterQuote -ne [char]0) {
                    $code = if ($delimiterQuote -ceq "'") { 'BASH_UNTERMINATED_SINGLE_QUOTE' } else { 'BASH_UNTERMINATED_DOUBLE_QUOTE' }
                    $message = if ($delimiterQuote -ceq "'") { 'Bash heredoc delimiter has an unterminated single quote.' } else { 'Bash heredoc delimiter has an unterminated double quote.' }
                    return New-RecoveryBashStatementFailure -Code $code -Message $message -ErrorIndex $delimiterQuoteIndex
                }
                if ($delimiter.Length -eq 0) {
                    return New-RecoveryBashStatementFailure -Code 'BASH_AMBIGUOUS_REDIRECTION' -Message 'Bash heredoc redirection has an empty delimiter.' -ErrorIndex $index
                }

                $redirections.Add([pscustomobject]@{
                    Delimiter = $delimiter.ToString()
                    StripTabs = $stripTabs
                    OperatorIndex = $index
                })
                $commentEligible = $false
                $index = [Math]::Max($delimiterIndex, $index + 2)
                continue
            }

            $commentEligible = $true
            $index++
            continue
        }

        if ([char]::IsWhiteSpace($character) -or ';|&()<>'.Contains([string]$character)) {
            $commentEligible = $true
        }
        else {
            $commentEligible = $false
        }
        $index++
    }

    if ($inSingleQuote) {
        return New-RecoveryBashStatementFailure -Code 'BASH_UNTERMINATED_SINGLE_QUOTE' -Message 'Bash statement has an unterminated single quote.' -ErrorIndex $singleQuoteIndex
    }
    if ($inDoubleQuote) {
        return New-RecoveryBashStatementFailure -Code 'BASH_UNTERMINATED_DOUBLE_QUOTE' -Message 'Bash statement has an unterminated double quote.' -ErrorIndex $doubleQuoteIndex
    }

    return [pscustomobject]@{
        IsValid = $true
        Code = $null
        Message = $null
        ErrorIndex = -1
        Redirections = $redirections.ToArray()
    }
}

function Get-RecoveryBashMappedPosition {
    param(
        [Parameter(Mandatory = $true)]
        [System.Collections.Generic.List[object]]$Positions,

        [Parameter(Mandatory = $true)]
        [int]$Index,

        [Parameter(Mandatory = $true)]
        [int]$FallbackLine,

        [Parameter(Mandatory = $true)]
        [int]$FallbackColumn
    )

    if ($Index -ge 0 -and $Index -lt $Positions.Count) {
        return $Positions[$Index]
    }

    [pscustomobject]@{
        SourceLine = $FallbackLine
        SourceColumn = $FallbackColumn
    }
}

function ConvertFrom-RecoveryBashFence {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)]
        $Fence
    )

    $normalizedBody = ([string]$Fence.Body).Replace("`r`n", "`n").Replace("`r", "`n")
    $physicalLines = @($normalizedBody -split "`n", -1)
    $events = [System.Collections.Generic.List[object]]::new()
    $diagnostics = [System.Collections.Generic.List[object]]::new()
    $heredocQueue = [System.Collections.Generic.List[object]]::new()
    $logicalLine = [System.Text.StringBuilder]::new()
    $logicalPositions = [System.Collections.Generic.List[object]]::new()
    $logicalStartLine = 0
    $logicalStartColumn = 1
    $logicalCommentEligible = $true
    $continuationLine = 0
    $continuationColumn = 0
    $heredocQueueLine = 0
    $heredocQueueColumn = 0
    $stopParsing = $false

    for ($lineIndex = 0; $lineIndex -lt $physicalLines.Count; $lineIndex++) {
        $line = $physicalLines[$lineIndex]
        $sourceLine = [int]$Fence.StartLine + $lineIndex + 1

        if ($heredocQueue.Count -gt 0) {
            $activeHeredoc = $heredocQueue[0]
            $terminator = if ($activeHeredoc.StripTabs) { $line.TrimStart([char[]]@([char]9)) } else { $line }
            if ($terminator -ceq $activeHeredoc.Delimiter) {
                $heredocQueue.RemoveAt(0)
            }
            continue
        }

        if ($logicalStartLine -eq 0) {
            $logicalStartLine = $sourceLine
            $logicalStartColumn = Get-RecoveryBashFirstContentColumn -Line $line
        }

        $physicalAnalysis = Get-RecoveryBashPhysicalLineAnalysis -Line $line -InitialCommentEligible $logicalCommentEligible
        $fragmentLength = if ($physicalAnalysis.HasContinuation) { $line.Length - 1 } else { $line.Length }
        if ($fragmentLength -gt 0) {
            $fragment = $line.Substring(0, $fragmentLength)
            [void]$logicalLine.Append($fragment)
            for ($columnIndex = 0; $columnIndex -lt $fragmentLength; $columnIndex++) {
                $logicalPositions.Add([pscustomobject]@{
                    SourceLine = $sourceLine
                    SourceColumn = $columnIndex + 1
                })
            }
        }

        if ($physicalAnalysis.HasContinuation) {
            $logicalCommentEligible = $physicalAnalysis.CommentEligible
            $continuationLine = $sourceLine
            $continuationColumn = $physicalAnalysis.ContinuationColumn
            continue
        }

        $logicalRaw = $logicalLine.ToString()
        $trimStart = 0
        while ($trimStart -lt $logicalRaw.Length -and [char]::IsWhiteSpace($logicalRaw[$trimStart])) {
            $trimStart++
        }
        $trimEnd = $logicalRaw.Length - 1
        while ($trimEnd -ge $trimStart -and [char]::IsWhiteSpace($logicalRaw[$trimEnd])) {
            $trimEnd--
        }
        $statement = if ($trimEnd -ge $trimStart) { $logicalRaw.Substring($trimStart, $trimEnd - $trimStart + 1) } else { '' }

        if (-not [string]::IsNullOrWhiteSpace($statement) -and -not $statement.StartsWith('#', [System.StringComparison]::Ordinal)) {
            $statementAnalysis = Get-RecoveryBashStatementAnalysis -Line $statement
            if (-not $statementAnalysis.IsValid) {
                $errorPosition = Get-RecoveryBashMappedPosition -Positions $logicalPositions -Index ($trimStart + $statementAnalysis.ErrorIndex) -FallbackLine $logicalStartLine -FallbackColumn $logicalStartColumn
                $diagnostics.Add((New-RecoveryParserDiagnostic -Code $statementAnalysis.Code -Message $statementAnalysis.Message -Language 'bash' -SourceLine $errorPosition.SourceLine -SourceColumn $errorPosition.SourceColumn -FenceId $Fence.Id))
                $stopParsing = $true
            }
            else {
                $statementId = '{0}-statement-{1:D4}' -f $Fence.Id, ($events.Count + 1)
                $eventParameters = @{
                    Kind = 'command'
                    Language = 'bash'
                    Text = $statement
                    SourceLine = $logicalStartLine
                    SourceColumn = $logicalStartColumn
                    FenceId = $Fence.Id
                    StatementId = $statementId
                    Metadata = @{}
                }
                if ($null -ne $Fence.SectionId) {
                    $eventParameters.SectionId = $Fence.SectionId
                }
                $events.Add((New-RecoveryExecutableEvent @eventParameters))

                foreach ($redirection in @($statementAnalysis.Redirections)) {
                    $operatorPosition = Get-RecoveryBashMappedPosition -Positions $logicalPositions -Index ($trimStart + $redirection.OperatorIndex) -FallbackLine $logicalStartLine -FallbackColumn $logicalStartColumn
                    if ($heredocQueue.Count -eq 0) {
                        $heredocQueueLine = $operatorPosition.SourceLine
                        $heredocQueueColumn = $operatorPosition.SourceColumn
                    }
                    $heredocQueue.Add([pscustomobject]@{
                        Delimiter = $redirection.Delimiter
                        StripTabs = $redirection.StripTabs
                    })
                }
            }
        }

        [void]$logicalLine.Clear()
        $logicalPositions.Clear()
        $logicalStartLine = 0
        $logicalStartColumn = 1
        $logicalCommentEligible = $true
        $continuationLine = 0
        $continuationColumn = 0
        if ($stopParsing) {
            break
        }
    }

    if (-not $stopParsing -and $logicalStartLine -ne 0) {
        $diagnostics.Add((New-RecoveryParserDiagnostic -Code 'BASH_UNFINISHED_CONTINUATION' -Message 'Bash fence ends with an unfinished unquoted continuation.' -Language 'bash' -SourceLine $continuationLine -SourceColumn $continuationColumn -FenceId $Fence.Id))
    }
    elseif (-not $stopParsing -and $heredocQueue.Count -gt 0) {
        $diagnostics.Add((New-RecoveryParserDiagnostic -Code 'BASH_UNBALANCED_HEREDOC_QUEUE' -Message 'Bash fence ends before all heredoc terminators are present.' -Language 'bash' -SourceLine $heredocQueueLine -SourceColumn $heredocQueueColumn -FenceId $Fence.Id))
    }

    return New-RecoveryParseResult -Events $events.ToArray() -Diagnostics $diagnostics.ToArray()
}

function Get-RecoveryMarkdownFenceMatch {
    param(
        [Parameter(Mandatory = $true)]
        [AllowEmptyString()]
        [string]$Line
    )

    $match = [regex]::Match($Line, '^(?<indent> {0,3})(?<marker>`{3,}|~{3,})(?<info>.*)$')
    if (-not $match.Success) {
        return $null
    }

    [pscustomobject]@{
        Marker = $match.Groups['marker'].Value
        RawInfo = $match.Groups['info'].Value.Trim()
        SourceColumn = $match.Groups['indent'].Value.Length + 1
    }
}

function Get-RecoveryMarkdownVisibleLine {
    param(
        [Parameter(Mandatory = $true)]
        [AllowEmptyString()]
        [string]$Line,

        [Parameter(Mandatory = $true)]
        [int]$LineNumber,

        [Parameter(Mandatory = $true)]
        [ref]$InHtmlComment,

        [Parameter(Mandatory = $true)]
        [ref]$CommentStartLine,

        [Parameter(Mandatory = $true)]
        [ref]$CommentStartColumn
    )

    $visible = $Line.ToCharArray()
    $cursor = 0
    while ($cursor -lt $Line.Length) {
        if ($InHtmlComment.Value) {
            $commentEnd = $Line.IndexOf('-->', $cursor, [System.StringComparison]::Ordinal)
            $commentEndExclusive = if ($commentEnd -lt 0) { $Line.Length } else { $commentEnd + 3 }
            for ($index = $cursor; $index -lt $commentEndExclusive; $index++) {
                $visible[$index] = ' '
            }

            if ($commentEnd -lt 0) {
                break
            }

            $InHtmlComment.Value = $false
            $cursor = $commentEndExclusive
            continue
        }

        $commentStart = $Line.IndexOf('<!--', $cursor, [System.StringComparison]::Ordinal)
        if ($commentStart -lt 0) {
            break
        }

        $InHtmlComment.Value = $true
        $CommentStartLine.Value = $LineNumber
        $CommentStartColumn.Value = $commentStart + 1
        $cursor = $commentStart
    }

    return (-join $visible)
}

function Resolve-RecoveryMarkdownLanguage {
    param(
        [Parameter(Mandatory = $true)]
        [AllowEmptyString()]
        [string]$RawInfo,

        [hashtable]$LanguageAliases = @{}
    )

    if ([string]::IsNullOrWhiteSpace($RawInfo)) {
        return [pscustomobject]@{
            Language = ''
            IsSupported = $true
        }
    }

    $token = ($RawInfo -split '\s+', 2)[0].ToLowerInvariant()
    if ($null -eq $LanguageAliases -or $LanguageAliases.Count -eq 0) {
        return [pscustomobject]@{
            Language = $token
            IsSupported = $true
        }
    }

    $canonicalKeys = @($LanguageAliases.Keys | ForEach-Object { [string]$_ } | Sort-Object)
    foreach ($canonicalKey in $canonicalKeys) {
        $canonicalLanguage = $canonicalKey.ToLowerInvariant()
        $aliases = @($canonicalKey) + @($LanguageAliases[$canonicalKey])
        foreach ($alias in $aliases) {
            if ($null -ne $alias -and $token -ieq ([string]$alias).Trim()) {
                return [pscustomobject]@{
                    Language = $canonicalLanguage
                    IsSupported = $true
                }
            }
        }
    }

    return [pscustomobject]@{
        Language = $token
        IsSupported = $false
    }
}

function ConvertFrom-RecoveryMarkdown {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)]
        [string]$Text,

        [string[]]$RequiredSections = @(),

        [hashtable]$RequiredFenceLanguages = @{},

        [hashtable]$LanguageAliases = @{}
    )

    $normalizedText = $Text.Replace("`r`n", "`n").Replace("`r", "`n")
    $lines = @($normalizedText -split "`n", -1)
    $sections = [System.Collections.Generic.List[object]]::new()
    $fences = [System.Collections.Generic.List[object]]::new()
    $diagnostics = [System.Collections.Generic.List[object]]::new()
    $inHtmlComment = $false
    $commentStartLine = 0
    $commentStartColumn = 0
    $currentSection = $null
    $openFence = $null

    for ($lineIndex = 0; $lineIndex -lt $lines.Count; $lineIndex++) {
        $line = $lines[$lineIndex]
        $lineNumber = $lineIndex + 1

        if ($null -ne $openFence) {
            $closingFence = Get-RecoveryMarkdownFenceMatch -Line $line
            if ($null -ne $closingFence -and
                $closingFence.Marker[0] -ceq $openFence.Marker[0] -and
                $closingFence.Marker.Length -ge $openFence.Marker.Length -and
                $closingFence.RawInfo -eq '') {
                $fences.Add([pscustomobject][ordered]@{
                    Id = $openFence.Id
                    SectionId = $openFence.SectionId
                    Language = $openFence.Language
                    RawInfo = $openFence.RawInfo
                    Body = [string]::Join("`n", $openFence.BodyLines.ToArray())
                    StartLine = $openFence.StartLine
                    EndLine = $lineNumber
                })
                $openFence = $null
                continue
            }

            $openFence.BodyLines.Add($line)
            continue
        }

        $visibleLine = Get-RecoveryMarkdownVisibleLine -Line $line -LineNumber $lineNumber -InHtmlComment ([ref]$inHtmlComment) -CommentStartLine ([ref]$commentStartLine) -CommentStartColumn ([ref]$commentStartColumn)
        $openingFence = Get-RecoveryMarkdownFenceMatch -Line $visibleLine
        if ($null -ne $openingFence) {
            $fenceId = 'fence-{0:D4}' -f ($fences.Count + 1)
            $languageResult = Resolve-RecoveryMarkdownLanguage -RawInfo $openingFence.RawInfo -LanguageAliases $LanguageAliases
            $sectionId = if ($null -ne $currentSection) { $currentSection.Id } else { $null }
            $openFence = [pscustomobject]@{
                Id = $fenceId
                SectionId = $sectionId
                Marker = $openingFence.Marker
                Language = $languageResult.Language
                RawInfo = $openingFence.RawInfo
                StartLine = $lineNumber
                SourceColumn = $openingFence.SourceColumn
                BodyLines = [System.Collections.Generic.List[string]]::new()
            }

            if (-not $languageResult.IsSupported) {
                $diagnostics.Add((New-RecoveryParserDiagnostic -Code 'MD_UNSUPPORTED_FENCE_LANGUAGE' -Message "Unsupported Markdown fence language '$($languageResult.Language)'." -Language 'markdown' -SourceLine $lineNumber -SourceColumn $openingFence.SourceColumn -FenceId $fenceId))
            }
            continue
        }

        $headingMatch = [regex]::Match($visibleLine, '^ {0,3}(?<heading>##(?:[ \t]+.*)?)[ \t]*$')
        if ($headingMatch.Success) {
            if ($null -ne $currentSection) {
                $currentSection.EndLine = $lineNumber - 1
            }

            $heading = $headingMatch.Groups['heading'].Value.TrimEnd()
            $currentSection = [pscustomobject][ordered]@{
                Id = 'section-{0:D4}' -f ($sections.Count + 1)
                Heading = $heading
                StartLine = $lineNumber
                EndLine = $lines.Count
            }
            $sections.Add($currentSection)
        }
    }

    if ($null -ne $openFence) {
        $fences.Add([pscustomobject][ordered]@{
            Id = $openFence.Id
            SectionId = $openFence.SectionId
            Language = $openFence.Language
            RawInfo = $openFence.RawInfo
            Body = [string]::Join("`n", $openFence.BodyLines.ToArray())
            StartLine = $openFence.StartLine
            EndLine = $lines.Count
        })
        $diagnostics.Add((New-RecoveryParserDiagnostic -Code 'MD_UNCLOSED_FENCE' -Message 'Markdown fence is not closed.' -Language 'markdown' -SourceLine $openFence.StartLine -SourceColumn $openFence.SourceColumn -FenceId $openFence.Id))
    }

    if ($null -ne $currentSection) {
        $currentSection.EndLine = $lines.Count
    }

    if ($inHtmlComment) {
        $diagnostics.Add((New-RecoveryParserDiagnostic -Code 'MD_UNCLOSED_HTML_COMMENT' -Message 'Markdown HTML comment is not closed.' -Language 'markdown' -SourceLine $commentStartLine -SourceColumn $commentStartColumn))
    }

    $reportedSections = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::Ordinal)
    foreach ($requiredSection in @($RequiredSections)) {
        if ($null -eq $requiredSection -or -not $reportedSections.Add($requiredSection)) {
            continue
        }

        $matchingSections = @($sections | Where-Object { $_.Heading -ceq $requiredSection })
        if ($matchingSections.Count -eq 0) {
            $diagnostics.Add((New-RecoveryParserDiagnostic -Code 'MD_REQUIRED_SECTION_MISSING' -Message "Required Markdown section '$requiredSection' is missing." -Language 'markdown' -SourceLine 1 -SourceColumn 1))
        }
    }

    $reportedFenceRequirements = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::Ordinal)
    $requiredFenceHeadings = if ($null -eq $RequiredFenceLanguages) { @() } else { @($RequiredFenceLanguages.Keys | ForEach-Object { [string]$_ } | Sort-Object) }
    foreach ($requiredFenceHeading in $requiredFenceHeadings) {
        $matchingSections = @($sections | Where-Object { $_.Heading -ceq $requiredFenceHeading })
        $matchingSectionIds = @($matchingSections | ForEach-Object { $_.Id })
        foreach ($requiredLanguageValue in @($RequiredFenceLanguages[$requiredFenceHeading])) {
            if ($null -eq $requiredLanguageValue) {
                continue
            }

            $requiredLanguage = ([string]$requiredLanguageValue).Trim().ToLowerInvariant()
            $requirementKey = $requiredFenceHeading + "`0" + $requiredLanguage
            if (-not $reportedFenceRequirements.Add($requirementKey)) {
                continue
            }

            $hasRequiredFence = @($fences | Where-Object {
                $matchingSectionIds -contains $_.SectionId -and $_.Language -ieq $requiredLanguage
            }).Count -gt 0
            if (-not $hasRequiredFence) {
                $sourceLine = if ($matchingSections.Count -gt 0) { $matchingSections[0].StartLine } else { 1 }
                $diagnostics.Add((New-RecoveryParserDiagnostic -Code 'MD_REQUIRED_FENCE_MISSING' -Message "Required Markdown fence language '$requiredLanguage' is missing from section '$requiredFenceHeading'." -Language 'markdown' -SourceLine $sourceLine -SourceColumn 1))
            }
        }
    }

    $diagnosticArray = @($diagnostics | Where-Object { $null -ne $_ })
    [pscustomobject][ordered]@{
        IsValid = ($diagnosticArray.Count -eq 0)
        Sections = @($sections)
        Fences = @($fences)
        Diagnostics = $diagnosticArray
    }
}

Export-ModuleMember -Function New-RecoveryParserDiagnostic, New-RecoveryExecutableEvent, New-RecoveryParseResult, Find-RecoveryExecutableEvents, Test-RecoveryEventSequence, ConvertFrom-RecoveryMarkdown, ConvertFrom-RecoveryBashFence, ConvertFrom-RecoveryPowerShellFence

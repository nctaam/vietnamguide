[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$SnapshotRoot,
    [string]$RepositoryRoot = '',
    [string[]]$AdditionalGitStageEntry = @()
)

$ErrorActionPreference = 'Stop'
Import-Module "$PSScriptRoot\lib\RecoveryParser.psm1" -Force -ErrorAction Stop

if ([string]::IsNullOrWhiteSpace($RepositoryRoot)) {
    $RepositoryRoot = Split-Path -Parent $PSScriptRoot
}
$repoRoot = [System.IO.Path]::GetFullPath($RepositoryRoot)
$failures = [System.Collections.Generic.List[string]]::new()

function Add-Failure {
    param([string]$Message)

    $script:failures.Add($Message)
}

function Get-RecoveryParserSectionEvents {
    param(
        [object[]]$Sections,
        [object[]]$Events,
        [string]$Heading
    )

    $sectionIds = @($Sections | Where-Object { $_.Heading -ceq $Heading } | ForEach-Object { $_.Id })
    return @($Events | Where-Object { $sectionIds -contains $_.SectionId })
}

function Test-RecoveryParserEventCoverage {
    param(
        [string]$Label,
        [object[]]$Events,
        [string[]]$ExpectedTexts,
        [string]$Language = $null,
        [switch]$RequireUnique
    )

    foreach ($expectedText in @($ExpectedTexts)) {
        $queryParameters = @{
            Events = $Events
            ExactText = $expectedText
        }
        if (-not [string]::IsNullOrEmpty($Language)) {
            $queryParameters.Language = $Language
        }
        $matchCount = @(Find-RecoveryExecutableEvents @queryParameters).Count
        if (($RequireUnique -and $matchCount -ne 1) -or (-not $RequireUnique -and $matchCount -eq 0)) {
            Add-Failure "$Label parser shadow failed: executable event count $matchCount for: $expectedText"
            return $false
        }
    }

    return $true
}

function Test-RecoveryParserEventSequenceContract {
    param(
        [string]$Label,
        [object[]]$Events,
        [string[]]$ExpectedTexts,
        [switch]$RequireUnique
    )

    $sequenceParameters = @{
        Events = $Events
        ExpectedTexts = $ExpectedTexts
    }
    if ($RequireUnique) {
        $sequenceParameters.RequireUnique = $true
    }
    if (-not (Test-RecoveryEventSequence @sequenceParameters)) {
        Add-Failure "$Label parser shadow failed: executable event sequence is missing, duplicated, or out of order."
        return $false
    }

    return $true
}

function Test-RecoveryParserConsecutiveEventWindow {
    param(
        [object[]]$Events,
        [string[]]$ExpectedTexts
    )

    $eventArray = if ($null -eq $Events) { @() } else { @($Events) }
    $expectedTextArray = if ($null -eq $ExpectedTexts) { @() } else { @($ExpectedTexts) }
    if ($expectedTextArray.Count -eq 0 -or $eventArray.Count -lt $expectedTextArray.Count) {
        return $false
    }

    $validStartCount = 0
    for ($startIndex = 0; $startIndex -le ($eventArray.Count - $expectedTextArray.Count); $startIndex++) {
        $window = @($eventArray[$startIndex..($startIndex + $expectedTextArray.Count - 1)])
        if (Test-RecoveryEventSequence -Events $window -ExpectedTexts $expectedTextArray) {
            $validStartCount++
        }
    }

    return $validStartCount -eq 1
}

function Test-RecoveryParserContiguousEventBlock {
    param(
        [object[]]$Events,
        [string[]]$ExpectedTexts
    )

    $eventArray = if ($null -eq $Events) { @() } else { @($Events) }
    $expectedTextArray = if ($null -eq $ExpectedTexts) { @() } else { @($ExpectedTexts) }
    if ($expectedTextArray.Count -eq 0 -or $eventArray.Count -lt $expectedTextArray.Count) {
        return $false
    }

    $validStartCount = 0
    for ($startIndex = 0; $startIndex -le ($eventArray.Count - $expectedTextArray.Count); $startIndex++) {
        $window = @($eventArray[$startIndex..($startIndex + $expectedTextArray.Count - 1)])
        if (Test-RecoveryEventSequence -Events $window -ExpectedTexts $expectedTextArray) {
            $validStartCount++
        }
    }

    return $validStartCount -gt 0
}

function Test-RecoveryParserUniqueEventFenceBeforeFirstEvents {
    param(
        [object[]]$Events,
        [object[]]$Fences,
        [string]$ExactText,
        [string]$Language,
        [string[]]$BeforeEventTexts
    )

    $eventArray = @($Events | Where-Object { $null -ne $_ })
    $fenceArray = @($Fences | Where-Object { $null -ne $_ })
    $publicEvents = @(Find-RecoveryExecutableEvents -Events $eventArray -ExactText $ExactText -Language $Language)
    if ($publicEvents.Count -ne 1) {
        return $false
    }

    $publicEvent = $publicEvents[0]
    $publicFences = @($fenceArray | Where-Object { $_.Id -ceq $publicEvent.FenceId })
    if (
        $publicFences.Count -ne 1 -or
        [int]$publicEvent.SourceLine -le 0 -or
        [int]$publicEvent.SourceColumn -le 0 -or
        [int]$publicFences[0].StartLine -le 0 -or
        [int]$publicFences[0].StartLine -gt [int]$publicEvent.SourceLine
    ) {
        return $false
    }

    foreach ($beforeEventText in @($BeforeEventTexts)) {
        $beforeEvents = @(Find-RecoveryExecutableEvents -Events $eventArray -ExactText $beforeEventText | Sort-Object -Property SourceLine, SourceColumn)
        if ($beforeEvents.Count -eq 0) {
            return $false
        }

        $beforeEvent = $beforeEvents[0]
        $beforeFences = @($fenceArray | Where-Object { $_.Id -ceq $beforeEvent.FenceId })
        if (
            $beforeFences.Count -ne 1 -or
            [int]$beforeEvent.SourceLine -le 0 -or
            [int]$beforeEvent.SourceColumn -le 0 -or
            [int]$beforeFences[0].StartLine -le 0 -or
            [int]$beforeFences[0].StartLine -gt [int]$beforeEvent.SourceLine -or
            [int]$publicFences[0].StartLine -ge [int]$beforeFences[0].StartLine
        ) {
            return $false
        }
    }

    return $true
}

function ConvertTo-LfText {
    param([string]$Text)

    return $Text.Replace("`r`n", "`n").Replace("`r", "`n")
}

function Test-ContainsNormalizedText {
    param(
        [string]$Text,
        [string]$Expected
    )

    return (ConvertTo-LfText -Text $Text).Contains((ConvertTo-LfText -Text $Expected))
}

function Get-MarkdownLineRecords {
    param([string]$Text)

    $records = [System.Collections.Generic.List[object]]::new()
    $offset = 0
    foreach ($rawLine in @($Text -split "`n", -1)) {
        $line = $rawLine.TrimEnd("`r")
        $records.Add([pscustomobject]@{
            Text = $line
            RawText = $rawLine
            Offset = $offset
        })
        $offset += $rawLine.Length + 1
    }

    return @($records)
}

function Get-MarkdownFenceMatch {
    param([string]$Line)

    $match = [regex]::Match($Line, '^(?: {0,3})(?<marker>`{3,}|~{3,})(?<info>.*)$')
    if (-not $match.Success) {
        return $null
    }

    [pscustomobject]@{
        Marker = $match.Groups['marker'].Value
        Info = $match.Groups['info'].Value.Trim()
    }
}

function Get-MarkdownVisibleLine {
    param(
        [string]$Line,
        [ref]$InHtmlComment
    )

    $visible = $Line.ToCharArray()
    $cursor = 0
    while ($cursor -lt $Line.Length) {
        if ($InHtmlComment.Value) {
            $commentEnd = $Line.IndexOf('-->', $cursor, [System.StringComparison]::Ordinal)
            $commentEndExclusive = if ($commentEnd -lt 0) { $Line.Length } else { $commentEnd + 3 }
            for ($commentIndex = $cursor; $commentIndex -lt $commentEndExclusive; $commentIndex++) {
                $visible[$commentIndex] = ' '
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
        $cursor = $commentStart
    }

    return -join $visible
}

function Get-MarkdownSectionText {
    param(
        [string]$Text,
        [string]$Heading
    )

    $records = @(Get-MarkdownLineRecords -Text $Text)
    $inFence = $false
    $fenceMarker = ''
    $fenceLength = 0
    $inHtmlComment = $false
    $sectionStart = -1
    $sectionEnd = $Text.Length

    foreach ($record in $records) {
        $line = $record.Text
        if ($inFence) {
            $fence = Get-MarkdownFenceMatch -Line $line
            if ($null -ne $fence -and
                $fence.Marker[0] -ceq $fenceMarker -and
                $fence.Marker.Length -ge $fenceLength -and
                $fence.Info -eq '') {
                $inFence = $false
            }
            continue
        }

        $line = Get-MarkdownVisibleLine -Line $line -InHtmlComment ([ref]$inHtmlComment)

        $fence = Get-MarkdownFenceMatch -Line $line
        if ($null -ne $fence) {
            $inFence = $true
            $fenceMarker = $fence.Marker[0]
            $fenceLength = $fence.Marker.Length
            continue
        }

        if ($sectionStart -lt 0) {
            if ($line -ceq $Heading) {
                $sectionStart = $record.Offset
            }
            continue
        }

        if ($line -match '^##\s+') {
            $sectionEnd = $record.Offset
            break
        }
    }

    if ($sectionStart -lt 0) {
        Add-Failure "Recovery execution addendum section missing: $Heading"
        return ''
    }

    return $Text.Substring($sectionStart, $sectionEnd - $sectionStart)
}

function Get-MarkdownFencedBlocks {
    param(
        [string]$Text,
        [string]$Language
    )

    $blocks = [System.Collections.Generic.List[object]]::new()
    $records = @(Get-MarkdownLineRecords -Text $Text)
    $openFence = $null
    $inHtmlComment = $false
    foreach ($record in $records) {
        $line = $record.Text
        if ($null -ne $openFence) {
            $fence = Get-MarkdownFenceMatch -Line $line
            if ($null -eq $fence -or
                $fence.Marker[0] -cne $openFence.Marker[0] -or
                $fence.Marker.Length -lt $openFence.Marker.Length -or
                $fence.Info -ne '') {
                continue
            }

            $bodyStart = $openFence.Offset + $openFence.RawText.Length + 1
            $bodyLength = $record.Offset - $bodyStart
            if ($bodyLength -lt 0) {
                $bodyLength = 0
            }
            $languageToken = if ($openFence.Info) { ($openFence.Info -split '\s+')[0] } else { '' }
            if ($languageToken -ieq $Language) {
                $textLength = ($record.Offset + $record.RawText.Length) - $openFence.Offset
                $blocks.Add([pscustomobject]@{
                    Index = $openFence.Offset
                    Text = $Text.Substring($openFence.Offset, $textLength)
                    Body = $Text.Substring($bodyStart, $bodyLength)
                })
            }
            $openFence = $null
            continue
        }

        $line = Get-MarkdownVisibleLine -Line $line -InHtmlComment ([ref]$inHtmlComment)

        $fence = Get-MarkdownFenceMatch -Line $line
        if ($null -ne $fence) {
            $openFence = [pscustomobject]@{
                Marker = $fence.Marker
                Info = $fence.Info
                Offset = $record.Offset
                RawText = $record.RawText
            }
        }
    }

    return @($blocks)
}

function Test-OrderedMarkers {
    param(
        [string]$Label,
        [string]$Text,
        [string[]]$Markers
    )

    $cursor = -1
    foreach ($marker in $Markers) {
        $position = $Text.IndexOf($marker, $cursor + 1, [System.StringComparison]::Ordinal)
        if ($position -lt 0) {
            Add-Failure "$Label failed: missing or out-of-order marker: $marker"
            return $false
        }
        $cursor = $position
    }

    return $true
}

function Get-BashArithmeticExpansionEnd {
    param(
        [string]$Line,
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

function Get-BashHeredocRedirections {
    param([string]$Line)

    $redirections = [System.Collections.Generic.List[object]]::new()
    $index = 0
    $inSingleQuote = $false
    $inDoubleQuote = $false
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
            $index++
            continue
        }
        if ($character -ceq '"') {
            $inDoubleQuote = $true
            $index++
            continue
        }
        if ($character -ceq '\' -and ($index + 1) -lt $Line.Length) {
            $index += 2
            continue
        }
        if ($character -ceq '#') {
            $previous = if ($index -gt 0) { $Line[$index - 1] } else { [char]0 }
            if ($index -eq 0 -or [char]::IsWhiteSpace($previous) -or ';|&()<>'.Contains([string]$previous)) {
                break
            }
        }
        if ($character -ceq '$' -and ($index + 2) -lt $Line.Length -and
            $Line[$index + 1] -ceq '(' -and $Line[$index + 2] -ceq '(') {
            $arithmeticEnd = Get-BashArithmeticExpansionEnd -Line $Line -ContentIndex ($index + 3)
            if ($arithmeticEnd -lt 0) {
                return [pscustomobject]@{ IsValid = $false; Redirections = @() }
            }
            $index = $arithmeticEnd
            continue
        }
        if ($character -ceq '(' -and ($index + 1) -lt $Line.Length -and $Line[$index + 1] -ceq '(') {
            $arithmeticEnd = Get-BashArithmeticExpansionEnd -Line $Line -ContentIndex ($index + 2)
            if ($arithmeticEnd -lt 0) {
                return [pscustomobject]@{ IsValid = $false; Redirections = @() }
            }
            $index = $arithmeticEnd
            continue
        }
        if ($character -ceq '<' -and ($index + 3) -lt $Line.Length -and
            $Line[$index + 1] -ceq '<' -and $Line[$index + 2] -ceq '<' -and $Line[$index + 3] -ceq '<') {
            return [pscustomobject]@{ IsValid = $false; Redirections = @() }
        }
        if ($character -ceq '<' -and ($index + 2) -lt $Line.Length -and
            $Line[$index + 1] -ceq '<' -and $Line[$index + 2] -ceq '<') {
            $index += 3
            continue
        }
        if ($character -cne '<' -or ($index + 1) -ge $Line.Length -or $Line[$index + 1] -cne '<' -or
            (($index + 2) -lt $Line.Length -and $Line[$index + 2] -ceq '<')) {
            $index++
            continue
        }

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
                    if (
                        $nextDelimiterCharacter -ceq '$' -or
                        $nextDelimiterCharacter -ceq ([char]96) -or
                        $nextDelimiterCharacter -ceq '"' -or
                        $nextDelimiterCharacter -ceq '\'
                    ) {
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
                $delimiterIndex++
                continue
            }
            if ($delimiterCharacter -ceq '\' -and ($delimiterIndex + 1) -lt $Line.Length) {
                $delimiterIndex++
                [void]$delimiter.Append($Line[$delimiterIndex])
                $delimiterIndex++
                continue
            }
            if ([char]::IsWhiteSpace($delimiterCharacter) -or ';|&()<>'.Contains([string]$delimiterCharacter)) {
                break
            }
            [void]$delimiter.Append($delimiterCharacter)
            $delimiterIndex++
        }

        if ($delimiter.Length -eq 0 -or $delimiterQuote -ne [char]0) {
            return [pscustomobject]@{ IsValid = $false; Redirections = @() }
        }
        $redirections.Add([pscustomobject]@{
            Delimiter = $delimiter.ToString()
            StripTabs = $stripTabs
        })
        $index = [Math]::Max($delimiterIndex, $index + 2)
    }

    if ($inSingleQuote -or $inDoubleQuote) {
        return [pscustomobject]@{ IsValid = $false; Redirections = @() }
    }

    return [pscustomobject]@{
        IsValid = $true
        Redirections = $redirections.ToArray()
    }
}

function Test-BashUnquotedLineContinuation {
    param([string]$Line)

    $inSingleQuote = $false
    $inDoubleQuote = $false
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
            $index++
            continue
        }
        if ($character -ceq '"') {
            $inDoubleQuote = $true
            $index++
            continue
        }
        if ($character -ceq '\') {
            if (($index + 1) -eq $Line.Length) {
                return $true
            }
            $index += 2
            continue
        }
        $index++
    }

    return $false
}

function Get-ExecutableBashLines {
    param([string]$Text)

    $executableLines = [System.Collections.Generic.List[string]]::new()
    $fences = @(Get-MarkdownFencedBlocks -Text $Text -Language 'bash')
    foreach ($fence in $fences) {
        $heredocQueue = [System.Collections.Generic.List[object]]::new()
        $continuedLine = [System.Text.StringBuilder]::new()
        $continuedPhysicalLines = [System.Collections.Generic.List[string]]::new()
        foreach ($record in @(Get-MarkdownLineRecords -Text $fence.Body)) {
            $line = $record.Text
            if ($record.Offset -eq $fence.Body.Length -and $line -eq '') {
                continue
            }
            if ($heredocQueue.Count -gt 0) {
                $activeHeredoc = $heredocQueue[0]
                $terminator = if ($activeHeredoc.StripTabs) { $line.TrimStart("`t") } else { $line }
                if ($terminator -ceq $activeHeredoc.Delimiter) {
                    $heredocQueue.RemoveAt(0)
                }
                continue
            }

            $isContinued = Test-BashUnquotedLineContinuation -Line $line
            $lineFragment = if ($isContinued) { $line.Substring(0, $line.Length - 1) } else { $line }
            [void]$continuedLine.Append($lineFragment)
            $continuedPhysicalLines.Add($line.Trim())
            if ($isContinued) {
                continue
            }

            $logicalTrimmed = $continuedLine.ToString().Trim()
            if (-not [string]::IsNullOrWhiteSpace($logicalTrimmed) -and -not $logicalTrimmed.StartsWith('#')) {
                foreach ($physicalLine in $continuedPhysicalLines) {
                    if (-not [string]::IsNullOrWhiteSpace($physicalLine)) {
                        $executableLines.Add($physicalLine)
                    }
                }
            }

            $continuedLine.Clear() | Out-Null
            $continuedPhysicalLines.Clear()
            if ([string]::IsNullOrWhiteSpace($logicalTrimmed) -or $logicalTrimmed.StartsWith('#')) {
                continue
            }

            $heredocParse = Get-BashHeredocRedirections -Line $logicalTrimmed
            if (-not $heredocParse.IsValid) {
                Add-Failure "Bash heredoc parsing failed: ambiguous redirection: $logicalTrimmed"
                return @()
            }
            foreach ($heredoc in @($heredocParse.Redirections)) {
                $heredocQueue.Add($heredoc)
            }
        }

        if ($continuedPhysicalLines.Count -gt 0) {
            Add-Failure 'Bash heredoc parsing failed: unfinished line continuation.'
            return @()
        }

        if ($heredocQueue.Count -gt 0) {
            Add-Failure 'Bash heredoc parsing failed: unbalanced heredoc queue.'
            return @()
        }
    }

    return @($executableLines)
}

function Get-ExecutablePowerShellLines {
    param([string]$Text)

    $executableLines = [System.Collections.Generic.List[string]]::new()
    $inHereString = $false
    $hereStringTerminator = ''
    $inBlockComment = $false
    $stringQuote = [char]0
    foreach ($record in @(Get-MarkdownLineRecords -Text $Text)) {
        $line = $record.Text
        if ($inHereString) {
            if ($line.StartsWith($hereStringTerminator, [System.StringComparison]::Ordinal) -and
                [string]::IsNullOrWhiteSpace($line.Substring($hereStringTerminator.Length))) {
                $inHereString = $false
                $hereStringTerminator = ''
            }
            continue
        }

        $executable = [System.Text.StringBuilder]::new()
        $index = 0
        $suppressLeadingStringContent = $stringQuote -ne [char]0
        while ($index -lt $line.Length) {
            if ($inBlockComment) {
                $commentEnd = $line.IndexOf('#>', $index, [System.StringComparison]::Ordinal)
                if ($commentEnd -lt 0) {
                    $index = $line.Length
                    break
                }
                $inBlockComment = $false
                $index = $commentEnd + 2
                [void]$executable.Append(' ')
                continue
            }

            $character = $line[$index]
            if ($stringQuote -ne [char]0) {
                if (-not $suppressLeadingStringContent) {
                    [void]$executable.Append($character)
                }
                if ($character -ceq '`' -and ($index + 1) -lt $line.Length) {
                    if (-not $suppressLeadingStringContent) {
                        [void]$executable.Append($line[$index + 1])
                    }
                    $index += 2
                    continue
                }
                if ($character -ceq $stringQuote) {
                    if (($index + 1) -lt $line.Length -and $line[$index + 1] -ceq $stringQuote) {
                        if (-not $suppressLeadingStringContent) {
                            [void]$executable.Append($line[$index + 1])
                        }
                        $index += 2
                        continue
                    }
                    $stringQuote = [char]0
                    $suppressLeadingStringContent = $false
                }
                $index++
                continue
            }

            if ($character -ceq '#') {
                break
            }
            if ($character -ceq '<' -and ($index + 1) -lt $line.Length -and $line[$index + 1] -ceq '#') {
                $inBlockComment = $true
                $index += 2
                [void]$executable.Append(' ')
                continue
            }
            if ($character -ceq '`' -and ($index + 1) -lt $line.Length) {
                [void]$executable.Append($character)
                [void]$executable.Append($line[$index + 1])
                $index += 2
                continue
            }
            if ($character -ceq "'" -or $character -ceq '"') {
                $stringQuote = $character
                [void]$executable.Append($character)
                $index++
                continue
            }
            if ($character -ceq '@' -and ($index + 1) -lt $line.Length -and
                ($line[$index + 1] -ceq "'" -or $line[$index + 1] -ceq '"') -and
                [string]::IsNullOrWhiteSpace($line.Substring($index + 2))) {
                [void]$executable.Append($line.Substring($index))
                $hereStringTerminator = $line[$index + 1] + '@'
                $inHereString = $true
                $index = $line.Length
                break
            }

            [void]$executable.Append($character)
            $index++
        }

        $trimmed = $executable.ToString().Trim()
        if ([string]::IsNullOrWhiteSpace($trimmed) -or $trimmed.StartsWith('#')) {
            continue
        }
        $executableLines.Add($trimmed)
    }

    return @($executableLines)
}

function Test-ConsecutiveExecutableBashLines {
    param(
        [string]$Label,
        [string[]]$Lines,
        [string[]]$Expected
    )

    $startIndexes = @(
        for ($index = 0; $index -lt $Lines.Count; $index++) {
            if ($Lines[$index] -ceq $Expected[0]) {
                $index
            }
        }
    )
    if ($startIndexes.Count -ne 1) {
        Add-Failure "$Label failed: expected exactly one executable boundary start."
        return $false
    }

    $startIndex = $startIndexes[0]
    if (($startIndex + $Expected.Count) -gt $Lines.Count) {
        Add-Failure "$Label failed: executable boundary is incomplete."
        return $false
    }

    for ($offset = 0; $offset -lt $Expected.Count; $offset++) {
        if ($Lines[$startIndex + $offset] -cne $Expected[$offset]) {
            Add-Failure "$Label failed: rollback failure gate is not immediate."
            return $false
        }
    }

    return $true
}

function Test-OrderedUniqueExecutableLines {
    param(
        [string]$Label,
        [string[]]$Lines,
        [string[]]$Markers
    )

    $cursor = -1
    foreach ($marker in $Markers) {
        $markerIndexes = @(
            for ($index = 0; $index -lt $Lines.Count; $index++) {
                if ($Lines[$index] -ceq $marker) {
                    $index
                }
            }
        )
        if ($markerIndexes.Count -ne 1 -or $markerIndexes[0] -le $cursor) {
            Add-Failure "$Label failed: missing, duplicate, or out-of-order executable marker: $marker"
            return $false
        }
        $cursor = $markerIndexes[0]
    }

    return $true
}

function Get-PowerShellNativeCommandName {
    param([System.Management.Automation.Language.CommandAst]$Command)

    $commandName = $Command.GetCommandName()
    if ($commandName) {
        $leafName = [System.IO.Path]::GetFileName($commandName)
        $nativeMatch = [regex]::Match(
            $leafName,
            '^(?<name>powershell|pwsh|node|php|git|ssh|scp)(?:\.exe)?$',
            [System.Text.RegularExpressions.RegexOptions]::IgnoreCase
        )
        if ($nativeMatch.Success) {
            return $nativeMatch.Groups['name'].Value.ToLowerInvariant()
        }
    }

    $commandElements = @($Command.CommandElements)
    if (
        $Command.InvocationOperator -eq [System.Management.Automation.Language.TokenKind]::Ampersand -and
        $commandElements.Count -gt 0 -and
        $commandElements[0] -is [System.Management.Automation.Language.VariableExpressionAst] -and
        $commandElements[0].VariablePath.UserPath -ieq 'PhpExecutable'
    ) {
        return '$PhpExecutable'
    }

    if ($Command.InvocationOperator -eq [System.Management.Automation.Language.TokenKind]::Ampersand) {
        return '<dynamic>'
    }

    return $null
}

function Get-PowerShellStatementContext {
    param([System.Management.Automation.Language.CommandAst]$Command)

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

function Test-PowerShellNativeStatementShape {
    param(
        [System.Management.Automation.Language.CommandAst]$Command,
        [System.Management.Automation.Language.Ast]$Statement,
        [string]$CommandName
    )

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
    if ($Statement -isnot [System.Management.Automation.Language.AssignmentStatementAst] -or $CommandName -ine 'git') {
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
        $memberCall.Arguments.Count -eq 0 -and
        $commandExpression -is [System.Management.Automation.Language.CommandExpressionAst] -and
        [object]::ReferenceEquals($commandExpression.Parent, $Statement) -and
        [object]::ReferenceEquals($Statement.Right, $commandExpression)
    )
}

function Test-PowerShellCondition {
    param(
        [System.Management.Automation.Language.PipelineBaseAst]$Condition,
        [string]$VariableName,
        [System.Management.Automation.Language.TokenKind]$Operator,
        [int]$Value
    )

    if ($Condition -isnot [System.Management.Automation.Language.PipelineAst]) {
        return $false
    }
    $pipelineElements = @($Condition.PipelineElements)
    if ($pipelineElements.Count -ne 1 -or $pipelineElements[0] -isnot [System.Management.Automation.Language.CommandExpressionAst]) {
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

function Test-PowerShellBlockingStatementBlock {
    param([System.Management.Automation.Language.StatementBlockAst]$StatementBlock)

    $statements = @($StatementBlock.Statements)
    if ($statements.Count -ne 1) {
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
    if ($expression -is [System.Management.Automation.Language.ConstantExpressionAst]) {
        return (
            $expression.Value -is [byte] -or
            $expression.Value -is [sbyte] -or
            $expression.Value -is [int16] -or
            $expression.Value -is [uint16] -or
            $expression.Value -is [int32] -or
            $expression.Value -is [uint32] -or
            $expression.Value -is [int64] -or
            $expression.Value -is [uint64]
        ) -and $expression.Value -ne 0
    }
    if ($expression -isnot [System.Management.Automation.Language.UnaryExpressionAst] -or
        @(
            [System.Management.Automation.Language.TokenKind]::Minus,
            [System.Management.Automation.Language.TokenKind]::Plus
        ) -notcontains $expression.TokenKind -or
        $expression.Child -isnot [System.Management.Automation.Language.ConstantExpressionAst]) {
        return $false
    }

    return (
        $expression.Child.Value -is [byte] -or
        $expression.Child.Value -is [sbyte] -or
        $expression.Child.Value -is [int16] -or
        $expression.Child.Value -is [uint16] -or
        $expression.Child.Value -is [int32] -or
        $expression.Child.Value -is [uint32] -or
        $expression.Child.Value -is [int64] -or
        $expression.Child.Value -is [uint64]
    ) -and $expression.Child.Value -ne 0
}

function Test-PowerShellStandardNativeGuard {
    param([System.Management.Automation.Language.StatementAst]$Statement)

    if ($Statement -isnot [System.Management.Automation.Language.IfStatementAst] -or
        @($Statement.Clauses).Count -ne 1 -or $null -ne $Statement.ElseClause) {
        return $false
    }
    return (
        (Test-PowerShellCondition -Condition $Statement.Clauses[0].Item1 -VariableName 'LASTEXITCODE' -Operator ([System.Management.Automation.Language.TokenKind]::Ine) -Value 0) -and
        (Test-PowerShellBlockingStatementBlock -StatementBlock $Statement.Clauses[0].Item2)
    )
}

function Get-PowerShellCapturedExitVariableName {
    param([System.Management.Automation.Language.StatementAst]$Statement)

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

    return $Statement.Left.VariablePath.UserPath
}

function Test-PowerShellCapturedNativeGuard {
    param(
        [System.Management.Automation.Language.StatementAst]$CaptureStatement,
        [System.Management.Automation.Language.StatementAst]$GuardStatement
    )

    $capturedExitName = Get-PowerShellCapturedExitVariableName -Statement $CaptureStatement
    if (
        -not $capturedExitName -or
        $GuardStatement -isnot [System.Management.Automation.Language.IfStatementAst] -or
        @($GuardStatement.Clauses).Count -ne 2 -or
        $null -eq $GuardStatement.ElseClause
    ) {
        return $false
    }
    return (
        (Test-PowerShellCondition -Condition $GuardStatement.Clauses[0].Item1 -VariableName $capturedExitName -Operator ([System.Management.Automation.Language.TokenKind]::Ieq) -Value 0) -and
        (Test-PowerShellCondition -Condition $GuardStatement.Clauses[1].Item1 -VariableName $capturedExitName -Operator ([System.Management.Automation.Language.TokenKind]::Ieq) -Value 1) -and
        (Test-PowerShellBlockingStatementBlock -StatementBlock $GuardStatement.ElseClause)
    )
}

function Test-PowerShellImmediateNativeGuard {
    param(
        [object[]]$Statements,
        [int]$CommandIndex
    )

    $guardIndex = $CommandIndex + 1
    if ($guardIndex -ge $Statements.Count) {
        return $false
    }
    if (Test-PowerShellStandardNativeGuard -Statement $Statements[$guardIndex]) {
        return $true
    }

    $branchIndex = $guardIndex + 1
    return (
        $branchIndex -lt $Statements.Count -and
        (Test-PowerShellCapturedNativeGuard -CaptureStatement $Statements[$guardIndex] -GuardStatement $Statements[$branchIndex])
    )
}

function Test-PowerShellNativeFailFast {
    param(
        [string]$Label,
        [string]$Text
    )

    $fences = @(Get-MarkdownFencedBlocks -Text $Text -Language 'powershell')
    foreach ($fence in $fences) {
        $tokens = $null
        $parseErrors = $null
        $ast = [System.Management.Automation.Language.Parser]::ParseInput(
            $fence.Body,
            [ref]$tokens,
            [ref]$parseErrors
        )
        if ($parseErrors.Count -ne 0) {
            Add-Failure "$Label native fail-fast contract failed: PowerShell parse error."
            return $false
        }

        $commands = @($ast.FindAll({
            param($node)
            $node -is [System.Management.Automation.Language.CommandAst]
        }, $true))
        foreach ($command in $commands) {
            $commandName = Get-PowerShellNativeCommandName -Command $command
            if (-not $commandName) {
                continue
            }

            $context = Get-PowerShellStatementContext -Command $command
            if (
                $null -eq $context -or
                -not (Test-PowerShellNativeStatementShape -Command $command -Statement $context.Statement -CommandName $commandName) -or
                -not (Test-PowerShellImmediateNativeGuard -Statements $context.Statements -CommandIndex $context.Index)
            ) {
                Add-Failure "$Label native fail-fast contract failed: $($command.Extent.Text.Trim())"
                return $false
            }
        }
    }

    return $true
}

function Get-RelativeFileMap {
    param(
        [string]$Root,
        [scriptblock]$Include = { $true }
    )

    $map = @{}
    if (-not (Test-Path -LiteralPath $Root -PathType Container)) {
        Add-Failure "Missing directory: $Root"
        return $map
    }

    Get-ChildItem -LiteralPath $Root -Recurse -File -Force |
        Where-Object $Include |
        ForEach-Object {
            $relative = $_.FullName.Substring($Root.Length).TrimStart('\').Replace('\', '/')
            $map[$relative] = $_.FullName
        }

    return $map
}

function Compare-FileTree {
    param(
        [string]$Label,
        [string]$SourceRoot,
        [string]$DestinationRoot,
        [scriptblock]$SourceInclude = { $true },
        [string[]]$AllowedDestinationExtras = @()
    )

    $source = Get-RelativeFileMap -Root $SourceRoot -Include $SourceInclude
    $destination = Get-RelativeFileMap -Root $DestinationRoot

    foreach ($relative in $source.Keys) {
        if (-not $destination.ContainsKey($relative)) {
            Add-Failure "$Label missing file: $relative"
            continue
        }

        $sourceHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $source[$relative]).Hash
        $destinationHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $destination[$relative]).Hash
        if ($sourceHash -ne $destinationHash) {
            Add-Failure "$Label hash mismatch: $relative"
        }
    }

    foreach ($relative in $destination.Keys) {
        if (-not $source.ContainsKey($relative) -and $relative -notin $AllowedDestinationExtras) {
            Add-Failure "$Label unexpected file: $relative"
        }
    }

    [pscustomobject]@{
        Label = $Label
        SourceFiles = $source.Count
        DestinationFiles = $destination.Count
    }
}

function Test-ExcludedRelativePath {
    param(
        [string]$RelativePath,
        [string[]]$Exclude
    )

    foreach ($pattern in $Exclude) {
        if ($RelativePath -like $pattern) {
            return $true
        }
    }

    return $false
}

function Resolve-ContainedManifestPath {
    param(
        [string]$Root,
        [string]$RelativePath,
        [string]$Label
    )

    try {
        $components = @($RelativePath -split '[\\/]')
        if ([string]::IsNullOrWhiteSpace($RelativePath) -or [System.IO.Path]::IsPathRooted($RelativePath) -or '..' -in $components) {
            Add-Failure "Recovery source manifest path is unsafe: $Label"
            return $null
        }

        $rootFull = [System.IO.Path]::GetFullPath($Root).TrimEnd([char[]]'\/')
        $candidate = [System.IO.Path]::GetFullPath((Join-Path $rootFull ($RelativePath.Replace('/', '\'))))
        $rootPrefix = $rootFull + [System.IO.Path]::DirectorySeparatorChar
        if (-not $candidate.StartsWith($rootPrefix, [System.StringComparison]::OrdinalIgnoreCase)) {
            Add-Failure "Recovery source manifest path is unsafe: $Label"
            return $null
        }

        return $candidate
    } catch {
        Add-Failure "Recovery source manifest path is unsafe: $Label"
        return $null
    }
}

function Get-CanonicalSectionDigest {
    param(
        [string]$Path,
        [string[]]$Exclude = @()
    )

    if (-not (Test-Path -LiteralPath $Path)) {
        Add-Failure "Manifest path missing: $Path"
        return [pscustomobject]@{ Count = 0; Digest = ''; Files = @() }
    }

    $resolved = (Resolve-Path -LiteralPath $Path).Path
    if (Test-Path -LiteralPath $resolved -PathType Leaf) {
        $root = Split-Path -Parent $resolved
        $files = @(Get-Item -LiteralPath $resolved -Force)
    } else {
        $root = $resolved.TrimEnd('\')
        $files = @(Get-ChildItem -LiteralPath $resolved -Recurse -File -Force)
    }

    $lines = [System.Collections.Generic.List[string]]::new()
    $includedFiles = [System.Collections.Generic.List[string]]::new()
    foreach ($file in $files) {
        $relative = $file.FullName.Substring($root.Length).TrimStart('\').Replace('\', '/')
        if (Test-ExcludedRelativePath -RelativePath $relative -Exclude $Exclude) {
            continue
        }

        $fileHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $file.FullName).Hash.ToLowerInvariant()
        $lines.Add("$relative`t$fileHash")
        $includedFiles.Add($file.FullName)
    }

    $canonicalLines = $lines.ToArray()
    [Array]::Sort($canonicalLines, [System.StringComparer]::Ordinal)
    $bytes = [System.Text.UTF8Encoding]::new($false).GetBytes(($canonicalLines -join "`n"))
    $sha256 = [System.Security.Cryptography.SHA256]::Create()
    try {
        $digest = ([BitConverter]::ToString($sha256.ComputeHash($bytes))).Replace('-', '').ToLowerInvariant()
    } finally {
        $sha256.Dispose()
    }

    [pscustomobject]@{
        Count = $canonicalLines.Count
        Digest = $digest
        Files = $includedFiles.ToArray()
    }
}

function Test-PrivateKeyHeader {
    param([string]$Path)

    $stream = [System.IO.File]::Open($Path, [System.IO.FileMode]::Open, [System.IO.FileAccess]::Read, [System.IO.FileShare]::ReadWrite)
    try {
        $length = [int][Math]::Min(8192, $stream.Length)
        if ($length -eq 0) {
            return $false
        }
        $buffer = New-Object byte[] $length
        $read = $stream.Read($buffer, 0, $length)
    } finally {
        $stream.Dispose()
    }

    $leadingText = [System.Text.Encoding]::ASCII.GetString($buffer, 0, $read)
    return [regex]::IsMatch(
        $leadingText,
        '-----BEGIN (?:[A-Z0-9][A-Z0-9 -]* )?PRIVATE KEY-----',
        [System.Text.RegularExpressions.RegexOptions]::CultureInvariant
    )
}

function Get-GitPathLines {
    param(
        [string[]]$Arguments,
        [string]$Label
    )

    $previousOutputEncoding = [Console]::OutputEncoding
    try {
        [Console]::OutputEncoding = [System.Text.UTF8Encoding]::new($false)
        $output = @(& git -c core.quotePath=false -C $repoRoot @Arguments)
        $exitCode = $LASTEXITCODE
    } finally {
        [Console]::OutputEncoding = $previousOutputEncoding
    }

    if ($exitCode -ne 0) {
        Add-Failure "$Label failed."
        return @()
    }

    return @($output)
}

function Resolve-ContainedRepositoryPath {
    param(
        [string]$RelativePath,
        [string]$Label
    )

    try {
        $normalizedPath = $RelativePath.Replace('\', '/')
        $components = @($normalizedPath -split '/')
        if (
            [string]::IsNullOrWhiteSpace($normalizedPath) -or
            [System.IO.Path]::IsPathRooted($normalizedPath) -or
            '' -in $components -or
            '.' -in $components -or
            '..' -in $components
        ) {
            Add-Failure "$Label is unsafe: $RelativePath"
            return $null
        }

        $rootFull = $repoRoot.TrimEnd([char[]]'\/')
        $candidate = [System.IO.Path]::GetFullPath((Join-Path $rootFull ($normalizedPath.Replace('/', '\'))))
        $rootPrefix = $rootFull + [System.IO.Path]::DirectorySeparatorChar
        if (-not $candidate.StartsWith($rootPrefix, [System.StringComparison]::OrdinalIgnoreCase)) {
            Add-Failure "$Label is unsafe: $RelativePath"
            return $null
        }

        return $candidate
    } catch {
        Add-Failure "$Label is unsafe: $RelativePath"
        return $null
    }
}

function Assert-NoReparsePoint {
    param(
        [string]$Label,
        [string]$Path
    )

    if (-not (Test-Path -LiteralPath $Path)) {
        return
    }

    $items = @(Get-Item -LiteralPath $Path -Force)
    if (Test-Path -LiteralPath $Path -PathType Container) {
        $items += @(Get-ChildItem -LiteralPath $Path -Recurse -Force)
    }

    foreach ($item in $items) {
        if (($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) {
            Add-Failure "$Label reparse point rejected: $($item.FullName)"
        }
    }
}

function Read-ValidatedLocalArtifactManifest {
    param(
        [string]$ManifestPath,
        [string]$Label,
        [object[]]$ExpectedEntries
    )

    $validated = [System.Collections.Generic.List[object]]::new()
    if (-not (Test-Path -LiteralPath $ManifestPath -PathType Leaf)) {
        Add-Failure "$Label manifest missing: $ManifestPath"
        return $validated.ToArray()
    }

    try {
        $manifest = Get-Content -Raw -LiteralPath $ManifestPath | ConvertFrom-Json
    } catch {
        Add-Failure "$Label manifest parse failed: $($_.Exception.Message)"
        return $validated.ToArray()
    }

    $rootKeys = @($manifest.PSObject.Properties.Name)
    $expectedRootKeys = @('schemaVersion', 'entries')
    if (@(Compare-Object -ReferenceObject $expectedRootKeys -DifferenceObject $rootKeys).Count -ne 0) {
        Add-Failure "$Label manifest root shape is invalid."
        return $validated.ToArray()
    }
    if ($manifest.schemaVersion -isnot [int] -or $manifest.schemaVersion -ne 1) {
        Add-Failure "$Label manifest schema version is invalid."
        return $validated.ToArray()
    }
    if ($manifest.entries -isnot [System.Array]) {
        Add-Failure "$Label manifest entries shape is invalid."
        return $validated.ToArray()
    }

    $entries = @($manifest.entries)
    $expectedByPath = [System.Collections.Generic.Dictionary[string, object]]::new([System.StringComparer]::Ordinal)
    foreach ($expected in $ExpectedEntries) {
        $expectedByPath.Add([string]$expected.relativePath, $expected)
    }

    $actualByPath = [System.Collections.Generic.Dictionary[string, object]]::new([System.StringComparer]::Ordinal)
    $shapeValid = $true
    foreach ($entry in $entries) {
        $entryKeys = @($entry.PSObject.Properties.Name)
        $expectedEntryKeys = @('relativePath', 'length', 'sha256')
        if (@(Compare-Object -ReferenceObject $expectedEntryKeys -DifferenceObject $entryKeys).Count -ne 0) {
            Add-Failure "$Label manifest entry shape is invalid."
            $shapeValid = $false
            continue
        }
        if ($entry.relativePath -isnot [string] -or [string]::IsNullOrWhiteSpace($entry.relativePath) -or
            ($entry.length -isnot [int] -and $entry.length -isnot [long]) -or $entry.length -lt 0 -or
            $entry.sha256 -isnot [string] -or $entry.sha256 -notmatch '^[0-9a-f]{64}$') {
            Add-Failure "$Label manifest entry shape is invalid."
            $shapeValid = $false
            continue
        }

        $components = @($entry.relativePath -split '[\\/]')
        $unsafe = [System.IO.Path]::IsPathRooted($entry.relativePath) -or
            $entry.relativePath -match '^[A-Za-z]:' -or
            $entry.relativePath.Contains('\') -or
            $entry.relativePath.StartsWith('/') -or
            $entry.relativePath.EndsWith('/') -or
            '.' -in $components -or '..' -in $components -or '' -in $components
        if ($unsafe) {
            Add-Failure "$Label manifest path is unsafe: $($entry.relativePath)"
            $shapeValid = $false
            continue
        }
        if ($actualByPath.ContainsKey($entry.relativePath)) {
            Add-Failure "$Label manifest duplicate relative path: $($entry.relativePath)"
            $shapeValid = $false
            continue
        }
        $actualByPath.Add($entry.relativePath, $entry)
    }

    $setValid = $shapeValid -and $actualByPath.Count -eq $expectedByPath.Count
    if ($setValid) {
        foreach ($expectedPath in $expectedByPath.Keys) {
            if (-not $actualByPath.ContainsKey($expectedPath)) {
                $setValid = $false
                break
            }
        }
    }
    if (-not $setValid) {
        Add-Failure "$Label manifest entry set is invalid."
        return $validated.ToArray()
    }

    $rootPrefix = $repoRoot.TrimEnd([char[]]'\/') + [System.IO.Path]::DirectorySeparatorChar
    foreach ($relativePath in $expectedByPath.Keys) {
        $entry = $actualByPath[$relativePath]
        $expected = $expectedByPath[$relativePath]
        if ($entry.length -ne $expected.length) {
            Add-Failure "$Label manifest length mismatch: $relativePath"
            continue
        }
        if ($entry.sha256 -cne $expected.sha256) {
            Add-Failure "$Label manifest SHA-256 mismatch: $relativePath"
            continue
        }

        try {
            $fullPath = [System.IO.Path]::GetFullPath((Join-Path $repoRoot $relativePath.Replace('/', '\')))
        } catch {
            Add-Failure "$Label manifest path is unsafe: $relativePath"
            continue
        }
        if (-not $fullPath.StartsWith($rootPrefix, [System.StringComparison]::OrdinalIgnoreCase)) {
            Add-Failure "$Label manifest path is unsafe: $relativePath"
            continue
        }
        if (-not (Test-Path -LiteralPath $fullPath -PathType Leaf)) {
            Add-Failure "$Label file missing: $relativePath"
            continue
        }

        $reparseRejected = $false
        $walkPath = $repoRoot
        foreach ($component in @($relativePath -split '/')) {
            $walkPath = Join-Path $walkPath $component
            $item = Get-Item -LiteralPath $walkPath -Force
            if (($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) {
                Add-Failure "$Label reparse point rejected: $relativePath"
                $reparseRejected = $true
                break
            }
        }
        if ($reparseRejected) {
            continue
        }

        $file = Get-Item -LiteralPath $fullPath -Force
        if ($file.Length -ne [int64]$entry.length) {
            Add-Failure "$Label byte length mismatch: $relativePath"
            continue
        }
        $hash = (Get-FileHash -Algorithm SHA256 -LiteralPath $fullPath).Hash.ToLowerInvariant()
        if ($hash -cne $entry.sha256) {
            Add-Failure "$Label SHA-256 mismatch: $relativePath"
            continue
        }

        $validated.Add([pscustomobject]@{
            RelativePath = $relativePath
            FullPath = $fullPath
        })
    }

    return $validated.ToArray()
}

$themeSource = Join-Path $SnapshotRoot 'live-theme\vietnamguide-premium'
$muSource = Join-Path $SnapshotRoot 'live-mu-plugins\mu-plugins\vietnamguide-core.php'
$docsSource = Join-Path $SnapshotRoot 'project-webroot\docs'
$opsSource = Join-Path $SnapshotRoot 'project-webroot\ops'

$approvedTargetFiles = [System.Collections.Generic.List[string]]::new()
$localDocsExtras = [System.Collections.Generic.List[string]]::new()
$localOpsExtras = [System.Collections.Generic.List[string]]::new()
$validatedLocalOpsRelativePaths = [System.Collections.Generic.List[string]]::new()
$localHistoryManifestPath = Join-Path $repoRoot 'tests\fixtures\recovery-local-history-manifest.json'
$localHistoryExpected = @(
    [pscustomobject]@{
        relativePath = 'docs/superpowers/specs/2026-08-03-vietnamguide-comparison-diversity-rollout-design.md'
        length = 56809
        sha256 = '1a2dd7f387bb03f2b23a53a655f3db390f13e299cb468f171e88b2557f418ded'
    },
    [pscustomobject]@{
        relativePath = 'docs/superpowers/plans/2026-08-03-vietnamguide-comparison-evidence-decision-rollout.md'
        length = 63426
        sha256 = 'a1a874d51fd3ccd43c24c0ada5d0a16008acc54400cbfdced5297ca6877b9af3'
    }
)
$validatedLocalHistory = @(Read-ValidatedLocalArtifactManifest -ManifestPath $localHistoryManifestPath -Label 'Recovery local-history' -ExpectedEntries $localHistoryExpected)
foreach ($entry in $validatedLocalHistory) {
    $approvedTargetFiles.Add($entry.FullPath)
    $localDocsExtras.Add($entry.RelativePath.Substring('docs/'.Length))
}

$localAuthoredManifestPath = Join-Path $repoRoot 'tests\fixtures\recovery-local-authored-manifest.json'
$localAuthoredExpected = @(
    [pscustomobject]@{
        relativePath = 'docs/superpowers/plans/2026-08-03-vietnamguide-comparison-recovery-execution-addendum.md'
        length = 44884
        sha256 = '0381ad940343495f0d8b7c95d488adb8fcede2b57caf94bf3d81d08c19fe9737'
    }
)
$validatedLocalAuthored = @(Read-ValidatedLocalArtifactManifest -ManifestPath $localAuthoredManifestPath -Label 'Recovery local-authored' -ExpectedEntries $localAuthoredExpected)
foreach ($entry in $validatedLocalAuthored) {
    $approvedTargetFiles.Add($entry.FullPath)
    $localDocsExtras.Add($entry.RelativePath.Substring('docs/'.Length))
}

if ($validatedLocalAuthored.Count -eq 1) {
    $executionAddendumPath = $validatedLocalAuthored[0].FullPath
    $executionAddendumText = [System.IO.File]::ReadAllText($executionAddendumPath)
    $normalizedAddendumText = ConvertTo-LfText -Text $executionAddendumText
    $requiredRecoveryFenceLanguages = @{
        '## Task 0: Recover the Missing Verifier Stack' = @('powershell')
        '## Local Build and Release Preparation' = @('powershell')
        '## Artifact Upload' = @('powershell')
        '## Production Shell Initialization' = @('powershell', 'bash')
        '## Verify and Atomically Install the Release' = @('bash')
        '## Command Wrapper and Run-ID Rules' = @('bash')
        '## Canary Validate, Dry-Run, Apply, and Activate' = @('bash', 'powershell')
        '## Canary Observation, Compatibility Sync, and Close' = @('bash')
        '## Canary Failure and Rollback' = @('bash')
        '## Stage 2 Validate, Apply, Activate, and Close' = @('bash', 'powershell')
        '## Stage 2 Failure and Rollback' = @('bash')
        '## Isolated Fixture-Only Rollback Drill' = @('bash', 'powershell')
        '## Reconnect After the Isolated Drill' = @('powershell', 'bash')
        '## Final Local Integration' = @('powershell')
    }
    $recoveryMarkdownResult = ConvertFrom-RecoveryMarkdown -Text $executionAddendumText -RequiredFenceLanguages $requiredRecoveryFenceLanguages
    $recoveryParserEvents = [System.Collections.Generic.List[object]]::new()
    $recoveryParserDiagnostics = [System.Collections.Generic.List[object]]::new()
    foreach ($diagnostic in @($recoveryMarkdownResult.Diagnostics)) {
        $recoveryParserDiagnostics.Add($diagnostic)
    }

    $recoveryParserNativeCommandNames = @('powershell', 'pwsh', 'node', 'php', 'git', 'ssh', 'scp')
    $parsedRecoveryFenceCount = 0
    foreach ($fence in @($recoveryMarkdownResult.Fences)) {
        if ($fence.Language -ceq 'bash') {
            $fenceResult = ConvertFrom-RecoveryBashFence -Fence $fence
        } elseif ($fence.Language -ceq 'powershell') {
            $fenceResult = ConvertFrom-RecoveryPowerShellFence -Fence $fence -NativeCommandNames $recoveryParserNativeCommandNames
        } else {
            continue
        }

        $parsedRecoveryFenceCount++
        foreach ($event in @($fenceResult.Events)) {
            $recoveryParserEvents.Add($event)
        }
        foreach ($diagnostic in @($fenceResult.Diagnostics)) {
            $recoveryParserDiagnostics.Add($diagnostic)
        }
    }

    $recoveryBashFenceCount = @($recoveryMarkdownResult.Fences | Where-Object { $_.Language -ceq 'bash' }).Count
    $recoveryPowerShellFenceCount = @($recoveryMarkdownResult.Fences | Where-Object { $_.Language -ceq 'powershell' }).Count
    if (
        $recoveryBashFenceCount -ne 13 -or
        $recoveryPowerShellFenceCount -ne 11 -or
        $parsedRecoveryFenceCount -ne ($recoveryBashFenceCount + $recoveryPowerShellFenceCount)
    ) {
        Add-Failure 'Recovery parser executable fence coverage failed.'
    }
    foreach ($diagnostic in $recoveryParserDiagnostics) {
        Add-Failure "Parser diagnostic [$($diagnostic.Code)] line $($diagnostic.SourceLine):$($diagnostic.SourceColumn): $($diagnostic.Message)"
    }

    $requiredPostDrillMarkers = @(
        '## Reconnect After the Isolated Drill',
        "VG_ARTIFACT_HASH='<same-lowercase-artifact-sha256>'",
        "VG_RUN_ID='<same-closed-full-run-id>'",
        'test -f "$DRILL_SENTINEL_DIR/production.before.json"'
    )
    foreach ($marker in $requiredPostDrillMarkers) {
        if (-not $executionAddendumText.Contains($marker)) {
            Add-Failure "Recovery execution addendum missing copy-safe post-drill marker: $marker"
        }
    }

    $nativeFailFastSections = @(
        [pscustomobject]@{ Label = 'Recovered verifier block'; Heading = '## Task 0: Recover the Missing Verifier Stack' },
        [pscustomobject]@{ Label = 'Local build block'; Heading = '## Local Build and Release Preparation' },
        [pscustomobject]@{ Label = 'Artifact upload block'; Heading = '## Artifact Upload' },
        [pscustomobject]@{ Label = 'Production SSH entry'; Heading = '## Production Shell Initialization' },
        [pscustomobject]@{ Label = 'Canary public verification block'; Heading = '## Canary Validate, Dry-Run, Apply, and Activate' },
        [pscustomobject]@{ Label = 'Stage 2 public verification block'; Heading = '## Stage 2 Validate, Apply, Activate, and Close' },
        [pscustomobject]@{ Label = 'Fixture drill block'; Heading = '## Isolated Fixture-Only Rollback Drill' },
        [pscustomobject]@{ Label = 'Reconnect SSH block'; Heading = '## Reconnect After the Isolated Drill' },
        [pscustomobject]@{ Label = 'Final integration block'; Heading = '## Final Local Integration' }
    )
    foreach ($sectionSpec in $nativeFailFastSections) {
        $sectionText = Get-MarkdownSectionText -Text $normalizedAddendumText -Heading $sectionSpec.Heading
        if ($sectionText) {
            [void](Test-PowerShellNativeFailFast -Label $sectionSpec.Label -Text $sectionText)
        }
    }

    $canaryActivationSection = Get-MarkdownSectionText -Text $normalizedAddendumText -Heading '## Canary Validate, Dry-Run, Apply, and Activate'
    $baselinePilotBlock = @'
BASELINE_PILOT_PATHS=(
  'destinations/ho-chi-minh-city-travel-guide'
  'itineraries/10-days-in-vietnam'
  'itineraries/7-days-in-vietnam'
  'itineraries/14-days-in-vietnam'
  'itineraries/21-days-in-vietnam'
  'itineraries/hanoi-in-2-days'
  'compare/ha-long-bay-vs-lan-ha-bay'
  'plan/vietnam-evisa'
)
'@
    $canaryPilotBlock = @'
CANARY_PATHS=(
  'compare/old-quarter-vs-french-quarter-vs-west-lake'
  'compare/ninh-binh-day-trip-vs-overnight'
  'compare/north-central-south-vietnam'
)
'@
    $stage2PilotBlock = @'
STAGE2_PATHS=(
  'compare/cu-chi-tunnels-vs-mekong-delta-day-trip'
  'compare/da-nang-vs-hoi-an'
  'compare/hoi-an-vs-hue'
  'compare/mui-ne-vs-nha-trang'
  'compare/phu-quoc-vs-nha-trang'
  'compare/trang-an-vs-tam-coc'
)
'@
    $permanentControlBlock = @'
PERMANENT_CONTROL_PATHS=(
  'compare'
  'destinations/hanoi-travel-guide'
  'plan/sim-esim-vietnam'
  'plan/transport-within-vietnam'
)
'@
    $requiredInventoryMarkers = @(
        "  'destinations/ho-chi-minh-city-travel-guide'",
        "  'itineraries/10-days-in-vietnam'",
        "  'itineraries/7-days-in-vietnam'",
        "  'itineraries/14-days-in-vietnam'",
        "  'itineraries/21-days-in-vietnam'",
        "  'itineraries/hanoi-in-2-days'",
        "  'compare/ha-long-bay-vs-lan-ha-bay'",
        "  'plan/vietnam-evisa'",
        "  'compare/old-quarter-vs-french-quarter-vs-west-lake'",
        "  'compare/ninh-binh-day-trip-vs-overnight'",
        "  'compare/north-central-south-vietnam'",
        "  'compare/cu-chi-tunnels-vs-mekong-delta-day-trip'",
        "  'compare/da-nang-vs-hoi-an'",
        "  'compare/hoi-an-vs-hue'",
        "  'compare/mui-ne-vs-nha-trang'",
        "  'compare/phu-quoc-vs-nha-trang'",
        "  'compare/trang-an-vs-tam-coc'",
        'ACTIVE_PILOT_PATHS=("${BASELINE_PILOT_PATHS[@]}" "${CANARY_PATHS[@]}" "${STAGE2_PATHS[@]}")',
        'test "${#ACTIVE_PILOT_PATHS[@]}" -eq 17',
        'test "$(printf ''%s\n'' "${ACTIVE_PILOT_PATHS[@]}" | sort -u | wc -l)" -eq 17',
        'test "${#PERMANENT_CONTROL_PATHS[@]}" -eq 4',
        'test "$(printf ''%s\n'' "${PERMANENT_CONTROL_PATHS[@]}" | sort -u | wc -l)" -eq 4'
    )
    $inventoryInvalid = $false
    foreach ($inventoryBlock in @($baselinePilotBlock, $canaryPilotBlock, $stage2PilotBlock, $permanentControlBlock)) {
        if (-not (Test-ContainsNormalizedText -Text $canaryActivationSection -Expected $inventoryBlock)) {
            $inventoryInvalid = $true
            break
        }
    }
    foreach ($marker in $requiredInventoryMarkers) {
        if (-not $canaryActivationSection.Contains($marker)) {
            $inventoryInvalid = $true
            break
        }
    }
    if ($inventoryInvalid) {
        Add-Failure 'Stage inventory contract failed.'
    }

    $browserMatrixMarkers = @(
        'BROWSER_MATRIX_PATHS=("${CANARY_PATHS[@]}" "${STAGE2_PATHS[@]}")',
        'BROWSER_MATRIX_VIEWPORTS=(''desktop:1280x900'' ''mobile:390x844'')',
        'BROWSER_MATRIX_RUNS_EXPECTED=18',
        'test "${#BROWSER_MATRIX_PATHS[@]}" -eq 9',
        'test "${#BROWSER_MATRIX_VIEWPORTS[@]}" -eq 2',
        'test "$(( ${#BROWSER_MATRIX_PATHS[@]} * ${#BROWSER_MATRIX_VIEWPORTS[@]} ))" -eq "$BROWSER_MATRIX_RUNS_EXPECTED"',
        "CANARY_TABLET_VIEWPORT='768x1024'",
        'CANARY_TABLET_RUNS_EXPECTED=3',
        'CANARY_REDUCED_MOTION_RUNS_EXPECTED=3',
        'CANARY_FORCED_COLORS_RUNS_EXPECTED=3'
    )
    foreach ($marker in $browserMatrixMarkers) {
        if (-not $canaryActivationSection.Contains($marker)) {
            Add-Failure "Stage 2 browser matrix inventory contract failed: $marker"
            break
        }
    }

    $canaryObservationSection = Get-MarkdownSectionText -Text $normalizedAddendumText -Heading '## Canary Observation, Compatibility Sync, and Close'
    $canaryRenewalMarkers = @(
        'LOCK_RENEWAL_INTERVAL_SECONDS=240',
        'CANARY_OBSERVATION_ROUNDS=3',
        'CANARY_OBSERVATION_MIN_SECONDS=600',
        'for round in 1 2 3; do',
        'sleep "$LOCK_RENEWAL_INTERVAL_SECONDS"',
        'run_rollout recovery-audit canary --action=renew-lock --ttl-seconds=900',
        'sleep "$((300 - LOCK_RENEWAL_INTERVAL_SECONDS))"',
        'test "$((VG_OBSERVE_END_EPOCH - VG_OBSERVE_START_EPOCH))" -ge "$CANARY_OBSERVATION_MIN_SECONDS"'
    )
    $canaryRenewalInvalid = $false
    foreach ($marker in $canaryRenewalMarkers) {
        if (-not $normalizedAddendumText.Contains($marker)) {
            $canaryRenewalInvalid = $true
            break
        }
    }
    if (([regex]::Matches($canaryObservationSection, '(?m)^\s*run_rollout recovery-audit canary --action=renew-lock --ttl-seconds=900$')).Count -lt 5) {
        $canaryRenewalInvalid = $true
    }
    $canaryExecutableLines = @(Get-ExecutableBashLines -Text $canaryObservationSection)
    $canarySleepLines = @($canaryExecutableLines | Where-Object { $_ -match '^sleep(?:\s|$)' })
    $approvedCanarySleeps = @(
        'sleep "$LOCK_RENEWAL_INTERVAL_SECONDS"',
        'sleep "$((300 - LOCK_RENEWAL_INTERVAL_SECONDS))"'
    )
    if (
        $canarySleepLines.Count -ne 2 -or
        $canarySleepLines[0] -cne $approvedCanarySleeps[0] -or
        $canarySleepLines[1] -cne $approvedCanarySleeps[1]
    ) {
        $canaryRenewalInvalid = $true
    } else {
        $resolvedCanarySleepSeconds = @(240, 60)
        $canaryGapCount = 3 - 1
        if (
            @($resolvedCanarySleepSeconds | Where-Object { $_ -ge 300 }).Count -ne 0 -or
            (($resolvedCanarySleepSeconds | Measure-Object -Sum).Sum * $canaryGapCount) -lt 600
        ) {
            $canaryRenewalInvalid = $true
        }

        $firstSleepIndex = [array]::IndexOf($canaryExecutableLines, $approvedCanarySleeps[0])
        $secondSleepIndex = [array]::IndexOf($canaryExecutableLines, $approvedCanarySleeps[1])
        $renewalBetweenSleeps = $false
        if ($firstSleepIndex -ge 0 -and $secondSleepIndex -gt $firstSleepIndex) {
            for ($index = $firstSleepIndex + 1; $index -lt $secondSleepIndex; $index++) {
                if ($canaryExecutableLines[$index] -ceq 'run_rollout recovery-audit canary --action=renew-lock --ttl-seconds=900') {
                    $renewalBetweenSleeps = $true
                    break
                }
            }
        }
        if (-not $renewalBetweenSleeps) {
            $canaryRenewalInvalid = $true
        }
    }
    if ($canaryRenewalInvalid) {
        Add-Failure 'Canary lock renewal contract failed.'
    }

    $canaryCompatibilityPosition = $canaryObservationSection.IndexOf('run_rollout compatibility-sync canary', [System.StringComparison]::Ordinal)
    foreach ($gate in @(
        'verify_rollout public-inventory canary',
        'verify_rollout browser-matrix canary',
        'verify_rollout performance-budgets canary',
        'verify_rollout cache-budgets canary',
        'verify_rollout log-observation canary',
        'verify_rollout permanent-controls canary'
    )) {
        $gatePosition = $canaryObservationSection.IndexOf($gate, [System.StringComparison]::Ordinal)
        if ($gatePosition -lt 0 -or $canaryCompatibilityPosition -lt 0 -or $gatePosition -gt $canaryCompatibilityPosition) {
            Add-Failure "Canary gate ordering contract failed: $gate"
            break
        }
    }
    [void](Test-OrderedMarkers -Label 'Canary gate ordering contract' -Text $canaryObservationSection -Markers @(
        'verify_rollout permanent-controls canary',
        'run_rollout recovery-audit canary --action=renew-lock --ttl-seconds=900',
        'run_rollout compatibility-sync canary',
        'verify_rollout compatibility-equivalence canary',
        'run_rollout recovery-audit canary --action=close-ledger --require-final-event=compatibility-sync',
        'test ! -e "$STATE_DIR/lock.json"'
    ))

    $canaryRollbackSection = Get-MarkdownSectionText -Text $normalizedAddendumText -Heading '## Canary Failure and Rollback'
    $canaryRollbackGate = @'
if [ "$ROLLBACK_EXIT" -ne 0 ]; then
  printf '%s\n' 'Canary rollback failed; lock and evidence preserved for recovery audit.' >&2
  exit "$ROLLBACK_EXIT"
fi
'@
    $canaryRollbackExecutableLines = @(Get-ExecutableBashLines -Text $canaryRollbackSection)
    [void](Test-ConsecutiveExecutableBashLines -Label 'Canary rollback ordering contract' -Lines $canaryRollbackExecutableLines -Expected @(
        'run_rollout rollback canary',
        'ROLLBACK_EXIT=$?',
        'set -e',
        'if [ "$ROLLBACK_EXIT" -ne 0 ]; then',
        "printf '%s\n' 'Canary rollback failed; lock and evidence preserved for recovery audit.' >&2",
        'exit "$ROLLBACK_EXIT"',
        'fi'
    ))
    if (-not (Test-ContainsNormalizedText -Text $canaryRollbackSection -Expected $canaryRollbackGate)) {
        Add-Failure 'Canary rollback ordering contract failed: immediate nonzero gate is missing.'
    }
    [void](Test-OrderedUniqueExecutableLines -Label 'Canary rollback ordering contract' -Lines $canaryRollbackExecutableLines -Markers @(
        'run_rollout rollback canary',
        'ROLLBACK_EXIT=$?',
        'set -e',
        'if [ "$ROLLBACK_EXIT" -ne 0 ]; then',
        'wp --path="$WP_ROOT" --allow-root cache flush',
        'verify_rollout baseline-hashes canary',
        'run_rollout recovery-audit canary --action=close-ledger --require-final-event=rollback',
        'test ! -e "$STATE_DIR/lock.json"'
    ))
    if ($canaryRollbackSection.Contains('test "$ROLLBACK_EXIT" -eq 0')) {
        Add-Failure 'Canary rollback ordering contract failed: success gate occurs after rollback work.'
    }

    $stage2Section = Get-MarkdownSectionText -Text $normalizedAddendumText -Heading '## Stage 2 Validate, Apply, Activate, and Close'
    $requiredBudgetMarkers = @(
        'MAX_HTML_GROWTH_BYTES=20480',
        'MAX_DOM_NODES=180',
        'MAX_SCOPED_CSS_BYTES=6144',
        'MAX_WARM_QUERIES=0',
        'MAX_COLD_QUERIES=1',
        'MAX_PHP_P95_MS=8',
        "MAX_CLS='0.10'",
        'MAX_LCP_REGRESSION_PERCENT=10',
        'MAX_QA_BATCH_SECONDS=240',
        'test "$MAX_QA_BATCH_SECONDS" -lt 300'
    )
    foreach ($marker in $requiredBudgetMarkers) {
        if (-not $normalizedAddendumText.Contains($marker)) {
            Add-Failure "Stage 2 gate ordering contract failed: missing budget marker: $marker"
            break
        }
    }

    $stage2ExecutableLines = @(Get-ExecutableBashLines -Text $stage2Section)
    $stage2CompatibilityIndexes = @(
        for ($index = 0; $index -lt $stage2ExecutableLines.Count; $index++) {
            if ($stage2ExecutableLines[$index] -ceq 'run_rollout compatibility-sync full') {
                $index
            }
        }
    )
    $firstStage2CompatibilityIndex = if ($stage2CompatibilityIndexes.Count -gt 0) { $stage2CompatibilityIndexes[0] } else { -1 }
    $requiredStage2Gates = @(
        'verify_rollout cache-warm full',
        'verify_rollout public-inventory full --expected-active-pilots=17 --expected-permanent-controls=4',
        'verify_rollout browser-matrix full',
        'verify_rollout tablet-canary full --viewport="$CANARY_TABLET_VIEWPORT" --expected-runs="$CANARY_TABLET_RUNS_EXPECTED"',
        'verify_rollout reduced-motion-canary full --expected-runs="$CANARY_REDUCED_MOTION_RUNS_EXPECTED"',
        'verify_rollout forced-colors-canary full --expected-runs="$CANARY_FORCED_COLORS_RUNS_EXPECTED"',
        'verify_rollout keyboard-zoom-focus-overflow full',
        'verify_rollout console-h1-module-content full',
        'verify_rollout performance-budgets full \',
        'verify_rollout cache-budgets full --max-warm-queries="$MAX_WARM_QUERIES" --max-cold-queries="$MAX_COLD_QUERIES"',
        'verify_rollout log-observation full',
        'verify_rollout permanent-controls full'
    )
    $stage2GateOrderValid = Test-OrderedUniqueExecutableLines -Label 'Stage 2 gate ordering contract' -Lines $stage2ExecutableLines -Markers $requiredStage2Gates
    if ($stage2GateOrderValid) {
        $lastStage2GateIndex = [array]::IndexOf($stage2ExecutableLines, $requiredStage2Gates[$requiredStage2Gates.Count - 1])
        if ($firstStage2CompatibilityIndex -le $lastStage2GateIndex) {
            Add-Failure 'Stage 2 gate ordering contract failed: compatibility sync precedes a required executable gate.'
        }
    }

    $stage2PublicVerifierLines = @(
        'powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout-public.ps1 `',
        '-Stage full `',
        '-Origin ''https://vietnamguide.net''',
        'if ($LASTEXITCODE -ne 0) { throw ''Full-stage public HTTP verification failed.'' }'
    )
    $stage2PowerShellFences = @(Get-MarkdownFencedBlocks -Text $stage2Section -Language 'powershell')
    $stage2PublicVerifierMatches = [System.Collections.Generic.List[object]]::new()
    foreach ($fence in $stage2PowerShellFences) {
        $powerShellLines = @(Get-ExecutablePowerShellLines -Text $fence.Body)
        for ($startIndex = 0; $startIndex -le ($powerShellLines.Count - $stage2PublicVerifierLines.Count); $startIndex++) {
            $sequenceMatches = $true
            for ($offset = 0; $offset -lt $stage2PublicVerifierLines.Count; $offset++) {
                if ($powerShellLines[$startIndex + $offset] -cne $stage2PublicVerifierLines[$offset]) {
                    $sequenceMatches = $false
                    break
                }
            }
            if ($sequenceMatches) {
                $stage2PublicVerifierMatches.Add([pscustomobject]@{
                    Fence = $fence
                    Index = $startIndex
                })
            }
        }
    }
    $stage2PublicVerifierInvalid = $stage2PublicVerifierMatches.Count -ne 1
    if (-not $stage2PublicVerifierInvalid) {
        $publicVerifierFence = $stage2PublicVerifierMatches[0].Fence
        [void](Test-PowerShellNativeFailFast -Label 'Stage 2 public verification block' -Text $publicVerifierFence.Text)

        $stage2BashFences = @(Get-MarkdownFencedBlocks -Text $stage2Section -Language 'bash')
        $firstCompatibilityFenceIndex = -1
        $closeFenceIndex = -1
        foreach ($fence in $stage2BashFences) {
            $fenceExecutableLines = @(Get-ExecutableBashLines -Text $fence.Text)
            if ($firstCompatibilityFenceIndex -lt 0 -and @($fenceExecutableLines | Where-Object { $_ -ceq 'run_rollout compatibility-sync full' }).Count -gt 0) {
                $firstCompatibilityFenceIndex = $fence.Index
            }
            if ($closeFenceIndex -lt 0 -and @($fenceExecutableLines | Where-Object { $_ -ceq 'run_rollout recovery-audit full --action=close-ledger --require-final-event=compatibility-sync' }).Count -gt 0) {
                $closeFenceIndex = $fence.Index
            }
        }
        if (
            $firstCompatibilityFenceIndex -lt 0 -or
            $closeFenceIndex -lt 0 -or
            $publicVerifierFence.Index -ge $firstCompatibilityFenceIndex -or
            $publicVerifierFence.Index -ge $closeFenceIndex
        ) {
            $stage2PublicVerifierInvalid = $true
        }
    }
    if ($stage2PublicVerifierInvalid) {
        Add-Failure 'Stage 2 public HTTP verification contract failed.'
    }

    $stage2BrowserBatchInvalid = (
        ([regex]::Matches($stage2Section, '(?m)^BROWSER_QA_BATCH_COUNT=3$')).Count -ne 1 -or
        ([regex]::Matches($stage2Section, '(?m)^BROWSER_QA_RUNS_PER_BATCH=6$')).Count -ne 1 -or
        -not $stage2Section.Contains('test "$((BROWSER_QA_BATCH_COUNT * BROWSER_QA_RUNS_PER_BATCH))" -eq "$BROWSER_MATRIX_RUNS_EXPECTED"')
    )
    if (-not (Test-ConsecutiveExecutableBashLines -Label 'Stage 2 browser QA renewal contract' -Lines $stage2ExecutableLines -Expected @(
        'for qa_batch in 1 2 3; do',
        'run_rollout recovery-audit full --action=renew-lock --ttl-seconds=900',
        'verify_rollout browser-matrix-batch full --batch="$qa_batch" --expected-runs="$BROWSER_QA_RUNS_PER_BATCH" --max-duration-seconds="$MAX_QA_BATCH_SECONDS"',
        'run_rollout recovery-audit full --action=renew-lock --ttl-seconds=900',
        'done'
    ))) {
        $stage2BrowserBatchInvalid = $true
    }
    if ($stage2BrowserBatchInvalid) {
        Add-Failure 'Stage 2 browser QA renewal contract failed.'
    }

    $stage2Tail = @(
        'verify_rollout permanent-controls full',
        'run_rollout compatibility-sync full',
        'run_rollout compatibility-sync full',
        'verify_rollout compatibility-equivalence full',
        'run_rollout recovery-audit full --action=close-ledger --require-final-event=compatibility-sync',
        'test ! -e "$STATE_DIR/lock.json"',
        'verify_rollout closed full'
    )
    $stage2TailCursor = -1
    foreach ($marker in $stage2Tail) {
        $markerIndex = -1
        for ($index = $stage2TailCursor + 1; $index -lt $stage2ExecutableLines.Count; $index++) {
            if ($stage2ExecutableLines[$index] -ceq $marker) {
                $markerIndex = $index
                break
            }
        }
        if ($markerIndex -lt 0) {
            Add-Failure "Stage 2 gate ordering contract failed: missing or out-of-order executable gate: $marker"
            break
        }
        $stage2TailCursor = $markerIndex
    }
    if (
        $stage2CompatibilityIndexes.Count -ne 2 -or
        @($stage2ExecutableLines | Where-Object { $_ -ceq 'run_rollout recovery-audit full --action=close-ledger --require-final-event=compatibility-sync' }).Count -ne 1 -or
        @($stage2ExecutableLines | Where-Object { $_ -ceq 'run_rollout recovery-audit full --action=renew-lock --ttl-seconds=900' }).Count -lt 5
    ) {
        Add-Failure 'Stage 2 gate ordering contract failed: sync, close, or lock-renewal count is unsafe.'
    }

    $stage2RollbackSection = Get-MarkdownSectionText -Text $normalizedAddendumText -Heading '## Stage 2 Failure and Rollback'
    $stage2RollbackGate = @'
if [ "$ROLLBACK_EXIT" -ne 0 ]; then
  printf '%s\n' 'Stage 2 rollback failed; lock and evidence preserved for recovery audit.' >&2
  exit "$ROLLBACK_EXIT"
fi
'@
    $stage2RollbackExecutableLines = @(Get-ExecutableBashLines -Text $stage2RollbackSection)
    [void](Test-ConsecutiveExecutableBashLines -Label 'Stage 2 rollback ordering contract' -Lines $stage2RollbackExecutableLines -Expected @(
        'run_rollout rollback full',
        'ROLLBACK_EXIT=$?',
        'set -e',
        'if [ "$ROLLBACK_EXIT" -ne 0 ]; then',
        "printf '%s\n' 'Stage 2 rollback failed; lock and evidence preserved for recovery audit.' >&2",
        'exit "$ROLLBACK_EXIT"',
        'fi'
    ))
    if (-not (Test-ContainsNormalizedText -Text $stage2RollbackSection -Expected $stage2RollbackGate)) {
        Add-Failure 'Stage 2 rollback ordering contract failed: immediate nonzero gate is missing.'
    }
    [void](Test-OrderedUniqueExecutableLines -Label 'Stage 2 rollback ordering contract' -Lines $stage2RollbackExecutableLines -Markers @(
        'run_rollout rollback full',
        'ROLLBACK_EXIT=$?',
        'set -e',
        'if [ "$ROLLBACK_EXIT" -ne 0 ]; then',
        'wp --path="$WP_ROOT" --allow-root cache flush',
        'verify_rollout baseline-hashes full',
        'run_rollout recovery-audit full --action=close-ledger --require-final-event=rollback',
        'test ! -e "$STATE_DIR/lock.json"'
    ))
    if ($stage2RollbackSection.Contains('test "$ROLLBACK_EXIT" -eq 0')) {
        Add-Failure 'Stage 2 rollback ordering contract failed: success gate occurs after rollback work.'
    }

    $releasePublicationSection = Get-MarkdownSectionText -Text $normalizedAddendumText -Heading '## Verify and Atomically Install the Release'
    $publicationExecutableLines = @(Get-ExecutableBashLines -Text $releasePublicationSection)
    $allExecutableBashLines = @(Get-ExecutableBashLines -Text $normalizedAddendumText)
    if (
        $releasePublicationSection.Contains('test ! -e "$RELEASE_DIR"') -or
        $releasePublicationSection.Contains('mv -- "$INSTALL_ROOT" "$RELEASE_DIR"')
    ) {
        Add-Failure 'Atomic release publication contract failed: non-atomic final-directory test and move detected.'
    }
    [void](Test-OrderedUniqueExecutableLines -Label 'Atomic release publication contract' -Lines $publicationExecutableLines -Markers @(
        'mkdir -m 0750 "$RELEASE_DIR"',
        'RELEASE_PAYLOAD_DIR="$RELEASE_DIR/payload"',
        'test ! -e "$RELEASE_PAYLOAD_DIR"',
        'mv -T -- "$INSTALL_ROOT" "$RELEASE_PAYLOAD_DIR"'
    ))
    [void](Test-OrderedUniqueExecutableLines -Label 'Post-publication release identity contract' -Lines $publicationExecutableLines -Markers @(
        'mv -T -- "$INSTALL_ROOT" "$RELEASE_PAYLOAD_DIR"',
        'test -f "$RELEASE_PAYLOAD_DIR/.vietnamguide-release-sha256"',
        'test "$(cat "$RELEASE_PAYLOAD_DIR/.vietnamguide-release-sha256")" = "$VG_ARTIFACT_HASH"',
        'test -f "$RELEASE_PAYLOAD_DIR/payload-manifest.json"',
        'test -f "$RELEASE_PAYLOAD_DIR/ops/comparison-rollout/artifact.json"',
        'php "$RELEASE_PAYLOAD_DIR/ops/install-comparison-rollout-release.php" verify-payload \',
        'php "$RELEASE_PAYLOAD_DIR/ops/install-comparison-rollout-release.php" install \'
    ))
    $publicationMoveLines = @($allExecutableBashLines | Where-Object { $_ -match '^mv(?:\s|$)' -and $_.Contains('$INSTALL_ROOT') })
    if ($publicationMoveLines.Count -ne 1 -or $publicationMoveLines[0] -cne 'mv -T -- "$INSTALL_ROOT" "$RELEASE_PAYLOAD_DIR"') {
        Add-Failure 'Atomic release publication contract failed: exact no-target-directory move is missing.'
    }
    if (
        $normalizedAddendumText.Contains('$RELEASE_DIR/ops/') -or
        -not $releasePublicationSection.Contains('php "$RELEASE_PAYLOAD_DIR/ops/install-comparison-rollout-release.php" install \') -or
        -not $releasePublicationSection.Contains('--release-root="$RELEASE_PAYLOAD_DIR"')
    ) {
        Add-Failure 'Release payload execution-root contract failed.'
    }
    $releaseInstallInvocations = @(
        $allExecutableBashLines | Where-Object {
            $_ -match '^php\b.*install-comparison-rollout-release\.php(?:"|''|\s).*\sinstall(?:\s|$)'
        }
    )
    if (
        $releaseInstallInvocations.Count -ne 1 -or
        $releaseInstallInvocations[0] -cne 'php "$RELEASE_PAYLOAD_DIR/ops/install-comparison-rollout-release.php" install \'
    ) {
        Add-Failure 'Release installer invocation contract failed.'
    }

    $recoveryParserEventArray = $recoveryParserEvents.ToArray()
    $canaryActivationParserEvents = @(Get-RecoveryParserSectionEvents -Sections @($recoveryMarkdownResult.Sections) -Events $recoveryParserEventArray -Heading '## Canary Validate, Dry-Run, Apply, and Activate')
    $canaryObservationParserEvents = @(Get-RecoveryParserSectionEvents -Sections @($recoveryMarkdownResult.Sections) -Events $recoveryParserEventArray -Heading '## Canary Observation, Compatibility Sync, and Close')
    $canaryRollbackParserEvents = @(Get-RecoveryParserSectionEvents -Sections @($recoveryMarkdownResult.Sections) -Events $recoveryParserEventArray -Heading '## Canary Failure and Rollback')
    $stage2ParserEvents = @(Get-RecoveryParserSectionEvents -Sections @($recoveryMarkdownResult.Sections) -Events $recoveryParserEventArray -Heading '## Stage 2 Validate, Apply, Activate, and Close')
    $stage2RollbackParserEvents = @(Get-RecoveryParserSectionEvents -Sections @($recoveryMarkdownResult.Sections) -Events $recoveryParserEventArray -Heading '## Stage 2 Failure and Rollback')
    $releasePublicationParserEvents = @(Get-RecoveryParserSectionEvents -Sections @($recoveryMarkdownResult.Sections) -Events $recoveryParserEventArray -Heading '## Verify and Atomically Install the Release')
    $postDrillParserEvents = @(Get-RecoveryParserSectionEvents -Sections @($recoveryMarkdownResult.Sections) -Events $recoveryParserEventArray -Heading '## Reconnect After the Isolated Drill')

    $requiredPostDrillEventMarkers = @($requiredPostDrillMarkers | Select-Object -Skip 1)
    [void](Test-RecoveryParserEventCoverage -Label 'Recovery execution addendum post-drill marker contract' -Events $postDrillParserEvents -ExpectedTexts $requiredPostDrillEventMarkers)

    $requiredInventoryEventMarkers = @($requiredInventoryMarkers | ForEach-Object { $_.TrimStart() })
    [void](Test-RecoveryParserEventCoverage -Label 'Stage inventory contract' -Events $canaryActivationParserEvents -ExpectedTexts $requiredInventoryEventMarkers)
    $baselinePilotParserBlock = @(
        'BASELINE_PILOT_PATHS=(',
        "'destinations/ho-chi-minh-city-travel-guide'",
        "'itineraries/10-days-in-vietnam'",
        "'itineraries/7-days-in-vietnam'",
        "'itineraries/14-days-in-vietnam'",
        "'itineraries/21-days-in-vietnam'",
        "'itineraries/hanoi-in-2-days'",
        "'compare/ha-long-bay-vs-lan-ha-bay'",
        "'plan/vietnam-evisa'",
        ')'
    )
    $canaryPilotParserBlock = @(
        'CANARY_PATHS=(',
        "'compare/old-quarter-vs-french-quarter-vs-west-lake'",
        "'compare/ninh-binh-day-trip-vs-overnight'",
        "'compare/north-central-south-vietnam'",
        ')'
    )
    $stage2PilotParserBlock = @(
        'STAGE2_PATHS=(',
        "'compare/cu-chi-tunnels-vs-mekong-delta-day-trip'",
        "'compare/da-nang-vs-hoi-an'",
        "'compare/hoi-an-vs-hue'",
        "'compare/mui-ne-vs-nha-trang'",
        "'compare/phu-quoc-vs-nha-trang'",
        "'compare/trang-an-vs-tam-coc'",
        ')'
    )
    $permanentControlParserBlock = @(
        'PERMANENT_CONTROL_PATHS=(',
        "'compare'",
        "'destinations/hanoi-travel-guide'",
        "'plan/sim-esim-vietnam'",
        "'plan/transport-within-vietnam'",
        ')'
    )
    if (-not (Test-RecoveryParserContiguousEventBlock -Events $canaryActivationParserEvents -ExpectedTexts $baselinePilotParserBlock)) {
        Add-Failure 'Stage inventory parser shadow failed: baseline pilot block is incomplete or noncontiguous.'
    }
    if (-not (Test-RecoveryParserContiguousEventBlock -Events $canaryActivationParserEvents -ExpectedTexts $canaryPilotParserBlock)) {
        Add-Failure 'Stage inventory parser shadow failed: canary pilot block is incomplete or noncontiguous.'
    }
    if (-not (Test-RecoveryParserContiguousEventBlock -Events $canaryActivationParserEvents -ExpectedTexts $stage2PilotParserBlock)) {
        Add-Failure 'Stage inventory parser shadow failed: Stage 2 pilot block is incomplete or noncontiguous.'
    }
    if (-not (Test-RecoveryParserContiguousEventBlock -Events $canaryActivationParserEvents -ExpectedTexts $permanentControlParserBlock)) {
        Add-Failure 'Stage inventory parser shadow failed: permanent-control block is incomplete or noncontiguous.'
    }
    [void](Test-RecoveryParserEventCoverage -Label 'Stage 2 browser matrix inventory contract' -Events $canaryActivationParserEvents -ExpectedTexts $browserMatrixMarkers)
    [void](Test-RecoveryParserEventCoverage -Label 'Stage 2 budget marker contract' -Events $recoveryParserEventArray -ExpectedTexts $requiredBudgetMarkers)
    [void](Test-RecoveryParserEventCoverage -Label 'Canary lock renewal contract' -Events $recoveryParserEventArray -ExpectedTexts $canaryRenewalMarkers)

    $requiredCanaryParserGates = @(
        'verify_rollout public-inventory canary --expected-active-pilots=11 --expected-permanent-controls=4',
        'verify_rollout browser-matrix canary',
        'verify_rollout performance-budgets canary   --max-html-growth-bytes="$MAX_HTML_GROWTH_BYTES"   --max-dom-nodes="$MAX_DOM_NODES"   --max-scoped-css-bytes="$MAX_SCOPED_CSS_BYTES"   --max-php-p95-ms="$MAX_PHP_P95_MS"   --max-cls="$MAX_CLS"   --max-lcp-regression-percent="$MAX_LCP_REGRESSION_PERCENT"',
        'verify_rollout cache-budgets canary --max-warm-queries="$MAX_WARM_QUERIES" --max-cold-queries="$MAX_COLD_QUERIES"',
        'verify_rollout log-observation canary',
        'verify_rollout permanent-controls canary'
    )
    $canaryCompatibilityEvents = @(Find-RecoveryExecutableEvents -Events $canaryObservationParserEvents -ExactText 'run_rollout compatibility-sync canary' -Language 'bash')
    $firstCanaryCompatibilityIndex = if ($canaryCompatibilityEvents.Count -gt 0) { [array]::IndexOf($canaryObservationParserEvents, $canaryCompatibilityEvents[0]) } else { -1 }
    foreach ($gate in $requiredCanaryParserGates) {
        $gateEvents = @(Find-RecoveryExecutableEvents -Events $canaryObservationParserEvents -ExactText $gate -Language 'bash')
        $firstGateIndex = if ($gateEvents.Count -gt 0) { [array]::IndexOf($canaryObservationParserEvents, $gateEvents[0]) } else { -1 }
        if ($firstGateIndex -lt 0 -or $firstCanaryCompatibilityIndex -lt 0 -or $firstGateIndex -gt $firstCanaryCompatibilityIndex) {
            Add-Failure "Canary gate ordering parser shadow failed: $gate"
            break
        }
    }
    [void](Test-RecoveryParserEventSequenceContract -Label 'Canary gate tail contract' -Events $canaryObservationParserEvents -ExpectedTexts @(
        'verify_rollout permanent-controls canary',
        'run_rollout recovery-audit canary --action=renew-lock --ttl-seconds=900',
        'run_rollout compatibility-sync canary',
        'verify_rollout compatibility-equivalence canary',
        'run_rollout recovery-audit canary --action=close-ledger --require-final-event=compatibility-sync',
        'test ! -e "$STATE_DIR/lock.json"'
    ))

    $canaryRollbackImmediateParserSequence = @(
        'run_rollout rollback canary',
        'ROLLBACK_EXIT=$?',
        'set -e',
        'if [ "$ROLLBACK_EXIT" -ne 0 ]; then',
        "printf '%s\n' 'Canary rollback failed; lock and evidence preserved for recovery audit.' >&2",
        'exit "$ROLLBACK_EXIT"',
        'fi'
    )
    $canaryRollbackParserMarkers = @(
        'run_rollout rollback canary',
        'ROLLBACK_EXIT=$?',
        'set -e',
        'if [ "$ROLLBACK_EXIT" -ne 0 ]; then',
        'wp --path="$WP_ROOT" --allow-root cache flush',
        'verify_rollout baseline-hashes canary',
        'run_rollout recovery-audit canary --action=close-ledger --require-final-event=rollback',
        'test ! -e "$STATE_DIR/lock.json"'
    )
    if (-not (Test-RecoveryParserConsecutiveEventWindow -Events $canaryRollbackParserEvents -ExpectedTexts $canaryRollbackImmediateParserSequence)) {
        Add-Failure 'Canary rollback immediate gate parser shadow failed: sequence is missing, duplicated, or noncontiguous.'
    }
    [void](Test-RecoveryParserEventSequenceContract -Label 'Canary rollback ordering contract' -Events $canaryRollbackParserEvents -ExpectedTexts $canaryRollbackParserMarkers -RequireUnique)

    $requiredStage2ParserGates = @(
        'verify_rollout cache-warm full',
        'verify_rollout public-inventory full --expected-active-pilots=17 --expected-permanent-controls=4',
        'verify_rollout browser-matrix full',
        'verify_rollout tablet-canary full --viewport="$CANARY_TABLET_VIEWPORT" --expected-runs="$CANARY_TABLET_RUNS_EXPECTED"',
        'verify_rollout reduced-motion-canary full --expected-runs="$CANARY_REDUCED_MOTION_RUNS_EXPECTED"',
        'verify_rollout forced-colors-canary full --expected-runs="$CANARY_FORCED_COLORS_RUNS_EXPECTED"',
        'verify_rollout keyboard-zoom-focus-overflow full',
        'verify_rollout console-h1-module-content full',
        'verify_rollout performance-budgets full   --max-html-growth-bytes="$MAX_HTML_GROWTH_BYTES"   --max-dom-nodes="$MAX_DOM_NODES"   --max-scoped-css-bytes="$MAX_SCOPED_CSS_BYTES"   --max-php-p95-ms="$MAX_PHP_P95_MS"   --max-cls="$MAX_CLS"   --max-lcp-regression-percent="$MAX_LCP_REGRESSION_PERCENT"',
        'verify_rollout cache-budgets full --max-warm-queries="$MAX_WARM_QUERIES" --max-cold-queries="$MAX_COLD_QUERIES"',
        'verify_rollout log-observation full',
        'verify_rollout permanent-controls full'
    )
    [void](Test-RecoveryParserEventSequenceContract -Label 'Stage 2 gate ordering contract' -Events $stage2ParserEvents -ExpectedTexts $requiredStage2ParserGates -RequireUnique)

    $stage2PublicVerifierEventText = [string]::Join("`n", @(
        'powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\ops\verify-comparison-rollout-public.ps1 `',
        '    -Stage full `',
        '    -Origin ''https://vietnamguide.net'''
    ))
    if (-not (Test-RecoveryParserUniqueEventFenceBeforeFirstEvents -Events $stage2ParserEvents -Fences @($recoveryMarkdownResult.Fences) -ExactText $stage2PublicVerifierEventText -Language 'powershell' -BeforeEventTexts @(
        'run_rollout compatibility-sync full',
        'run_rollout recovery-audit full --action=close-ledger --require-final-event=compatibility-sync'
    ))) {
        Add-Failure 'Stage 2 public HTTP verification parser shadow failed: typed event or fence ordering is unsafe.'
    }

    $stage2BrowserBatchMarkers = @(
        'BROWSER_QA_BATCH_COUNT=3',
        'BROWSER_QA_RUNS_PER_BATCH=6',
        'test "$((BROWSER_QA_BATCH_COUNT * BROWSER_QA_RUNS_PER_BATCH))" -eq "$BROWSER_MATRIX_RUNS_EXPECTED"'
    )
    $stage2BrowserBatchParserSequence = @(
        'for qa_batch in 1 2 3; do',
        'run_rollout recovery-audit full --action=renew-lock --ttl-seconds=900',
        'verify_rollout browser-matrix-batch full --batch="$qa_batch" --expected-runs="$BROWSER_QA_RUNS_PER_BATCH" --max-duration-seconds="$MAX_QA_BATCH_SECONDS"',
        'run_rollout recovery-audit full --action=renew-lock --ttl-seconds=900',
        'done'
    )
    [void](Test-RecoveryParserEventCoverage -Label 'Stage 2 browser QA marker contract' -Events $stage2ParserEvents -ExpectedTexts @($stage2BrowserBatchMarkers | Select-Object -First 2) -RequireUnique)
    [void](Test-RecoveryParserEventCoverage -Label 'Stage 2 browser QA marker contract' -Events $stage2ParserEvents -ExpectedTexts @($stage2BrowserBatchMarkers | Select-Object -Last 1))
    if (-not (Test-RecoveryParserConsecutiveEventWindow -Events $stage2ParserEvents -ExpectedTexts $stage2BrowserBatchParserSequence)) {
        Add-Failure 'Stage 2 browser QA renewal parser shadow failed: sequence is missing, duplicated, or noncontiguous.'
    }
    [void](Test-RecoveryParserEventSequenceContract -Label 'Stage 2 gate tail contract' -Events $stage2ParserEvents -ExpectedTexts $stage2Tail)

    $stage2RollbackImmediateParserSequence = @(
        'run_rollout rollback full',
        'ROLLBACK_EXIT=$?',
        'set -e',
        'if [ "$ROLLBACK_EXIT" -ne 0 ]; then',
        "printf '%s\n' 'Stage 2 rollback failed; lock and evidence preserved for recovery audit.' >&2",
        'exit "$ROLLBACK_EXIT"',
        'fi'
    )
    $stage2RollbackParserMarkers = @(
        'run_rollout rollback full',
        'ROLLBACK_EXIT=$?',
        'set -e',
        'if [ "$ROLLBACK_EXIT" -ne 0 ]; then',
        'wp --path="$WP_ROOT" --allow-root cache flush',
        'verify_rollout baseline-hashes full',
        'run_rollout recovery-audit full --action=close-ledger --require-final-event=rollback',
        'test ! -e "$STATE_DIR/lock.json"'
    )
    if (-not (Test-RecoveryParserConsecutiveEventWindow -Events $stage2RollbackParserEvents -ExpectedTexts $stage2RollbackImmediateParserSequence)) {
        Add-Failure 'Stage 2 rollback immediate gate parser shadow failed: sequence is missing, duplicated, or noncontiguous.'
    }
    [void](Test-RecoveryParserEventSequenceContract -Label 'Stage 2 rollback ordering contract' -Events $stage2RollbackParserEvents -ExpectedTexts $stage2RollbackParserMarkers -RequireUnique)

    $releasePublicationParserMarkers = @(
        'mkdir -m 0750 "$RELEASE_DIR"',
        'RELEASE_PAYLOAD_DIR="$RELEASE_DIR/payload"',
        'test ! -e "$RELEASE_PAYLOAD_DIR"',
        'mv -T -- "$INSTALL_ROOT" "$RELEASE_PAYLOAD_DIR"'
    )
    $postPublicationParserMarkers = @(
        'mv -T -- "$INSTALL_ROOT" "$RELEASE_PAYLOAD_DIR"',
        'test -f "$RELEASE_PAYLOAD_DIR/.vietnamguide-release-sha256"',
        'test "$(cat "$RELEASE_PAYLOAD_DIR/.vietnamguide-release-sha256")" = "$VG_ARTIFACT_HASH"',
        'test -f "$RELEASE_PAYLOAD_DIR/payload-manifest.json"',
        'test -f "$RELEASE_PAYLOAD_DIR/ops/comparison-rollout/artifact.json"',
        'php "$RELEASE_PAYLOAD_DIR/ops/install-comparison-rollout-release.php" verify-payload   --release-root="$RELEASE_PAYLOAD_DIR"   --payload="$RELEASE_PAYLOAD_DIR/payload-manifest.json"   --archive-sha256="$VG_ARTIFACT_HASH"',
        'php "$RELEASE_PAYLOAD_DIR/ops/install-comparison-rollout-release.php" install   --release-root="$RELEASE_PAYLOAD_DIR"   --wordpress-root="$WP_ROOT"   --state-dir="$STATE_DIR"   --run-id="$VG_RUN_ID"'
    )
    [void](Test-RecoveryParserEventSequenceContract -Label 'Atomic release publication contract' -Events $releasePublicationParserEvents -ExpectedTexts $releasePublicationParserMarkers -RequireUnique)
    [void](Test-RecoveryParserEventSequenceContract -Label 'Post-publication release identity contract' -Events $releasePublicationParserEvents -ExpectedTexts $postPublicationParserMarkers -RequireUnique)

    $canaryRenewalEventCount = @(Find-RecoveryExecutableEvents -Events $canaryObservationParserEvents -ExactText 'run_rollout recovery-audit canary --action=renew-lock --ttl-seconds=900' -Language 'bash').Count
    $firstCanarySleepEventCount = @(Find-RecoveryExecutableEvents -Events $canaryObservationParserEvents -ExactText $approvedCanarySleeps[0] -Language 'bash').Count
    $secondCanarySleepEventCount = @(Find-RecoveryExecutableEvents -Events $canaryObservationParserEvents -ExactText $approvedCanarySleeps[1] -Language 'bash').Count
    if ($canaryRenewalEventCount -lt 5 -or $firstCanarySleepEventCount -ne 1 -or $secondCanarySleepEventCount -ne 1) {
        Add-Failure 'Canary lock renewal parser shadow failed: executable event counts are unsafe.'
    } else {
        $firstCanarySleepEvent = @(Find-RecoveryExecutableEvents -Events $canaryObservationParserEvents -ExactText $approvedCanarySleeps[0] -Language 'bash')[0]
        $secondCanarySleepEvent = @(Find-RecoveryExecutableEvents -Events $canaryObservationParserEvents -ExactText $approvedCanarySleeps[1] -Language 'bash')[0]
        $firstCanarySleepIndex = [array]::IndexOf($canaryObservationParserEvents, $firstCanarySleepEvent)
        $secondCanarySleepIndex = [array]::IndexOf($canaryObservationParserEvents, $secondCanarySleepEvent)
        $renewalBetweenCanarySleeps = $false
        if ($firstCanarySleepIndex -ge 0 -and $secondCanarySleepIndex -gt $firstCanarySleepIndex) {
            for ($index = $firstCanarySleepIndex + 1; $index -lt $secondCanarySleepIndex; $index++) {
                if ($canaryObservationParserEvents[$index].Text -ceq 'run_rollout recovery-audit canary --action=renew-lock --ttl-seconds=900') {
                    $renewalBetweenCanarySleeps = $true
                    break
                }
            }
        }
        if (-not $renewalBetweenCanarySleeps) {
            Add-Failure 'Canary lock renewal parser shadow failed: sleeps are out of order or lack an intervening renewal.'
        }
    }

    $stage2CompatibilityEventCount = @(Find-RecoveryExecutableEvents -Events $stage2ParserEvents -ExactText 'run_rollout compatibility-sync full' -Language 'bash').Count
    $stage2CloseEventCount = @(Find-RecoveryExecutableEvents -Events $stage2ParserEvents -ExactText 'run_rollout recovery-audit full --action=close-ledger --require-final-event=compatibility-sync' -Language 'bash').Count
    $stage2RenewalEventCount = @(Find-RecoveryExecutableEvents -Events $stage2ParserEvents -ExactText 'run_rollout recovery-audit full --action=renew-lock --ttl-seconds=900' -Language 'bash').Count
    if ($stage2CompatibilityEventCount -ne 2 -or $stage2CloseEventCount -ne 1 -or $stage2RenewalEventCount -lt 5) {
        Add-Failure 'Stage 2 gate parser shadow failed: sync, close, or lock-renewal event count is unsafe.'
    }

    $publicationMoveEventCount = @(Find-RecoveryExecutableEvents -Events $recoveryParserEventArray -ExactText 'mv -T -- "$INSTALL_ROOT" "$RELEASE_PAYLOAD_DIR"' -Language 'bash').Count
    $releaseInstallEventCount = @(Find-RecoveryExecutableEvents -Events $recoveryParserEventArray -ExactText $postPublicationParserMarkers[-1] -Language 'bash').Count
    if ($publicationMoveEventCount -ne 1 -or $releaseInstallEventCount -ne 1) {
        Add-Failure 'Release publication parser shadow failed: move or installer event count is unsafe.'
    }
}

$localOpsManifestPath = Join-Path $repoRoot 'tests\fixtures\recovery-local-ops-manifest.json'
$localOpsExpected = @(
    [pscustomobject]@{
        relativePath = 'ops/verify-core-block-patterns.ps1'
        length = 14206
        sha256 = '30f4be5818b15e4cd8c3ad9b616aeddebd77ff97aa4922a3c89dfaaa35b23c85'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-core-mu-plugin.ps1'
        length = 30539
        sha256 = '1334d35e895a8eac7d5122b482a188f19072ae5b636c46ce44f6257fd1a1419d'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-core-mu-plugin-live.php'
        length = 9743
        sha256 = '280424139dc8b29ba2911d7d3caf1a203499c742efd6d6a243b0d98a7dc8e842'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-homepage-theme.ps1'
        length = 30042
        sha256 = '0c58f228806da1d121bce622d991f738f3d4552c4bf9dbbb3a8640dbb474558b'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-guide-experience.ps1'
        length = 121518
        sha256 = '60f2446f513dae8ff9ea84b6a90823bb8ab35b30dd3699602ca65d41f95b21ec'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-guide-experience-mutations.ps1'
        length = 44511
        sha256 = '8bb44777a33bf89478e54189f7377155b4e17eba7de71f60fae27c88563d6108'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-guide-experience-tokenizer.php'
        length = 25434
        sha256 = '9b65e83952e5d82dc835d6c4d56e6f93c396d9334e877117f7def4bbc99c25f4'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-guide-experience-public.ps1'
        length = 55723
        sha256 = '03b018f463abe474d06b7006a5cdc952d757cd26825d0a1c9700f20f24873543'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-guide-experience-live.php'
        length = 26254
        sha256 = '33bf8f3791bd6b8e5cc6aff1a7cc2dbfa8a7df8df9d639ba262f431879f30643'
    },
    [pscustomobject]@{
        relativePath = 'ops/verify-guide-experience-js-runtime.js'
        length = 3703
        sha256 = '031feabe4d0934a13085f9066e42088c941d5dd60be0e649b2b89e2c67eeeb4b'
    }
)
$validatedLocalOps = @(Read-ValidatedLocalArtifactManifest -ManifestPath $localOpsManifestPath -Label 'Recovery local-ops' -ExpectedEntries $localOpsExpected)
if ($validatedLocalOps.Count -eq $localOpsExpected.Count) {
    foreach ($entry in $validatedLocalOps) {
        $approvedTargetFiles.Add($entry.FullPath)
        $validatedLocalOpsRelativePaths.Add($entry.RelativePath)
        $localOpsExtras.Add($entry.RelativePath.Substring('ops/'.Length))
    }
}

$manifestPath = Join-Path $repoRoot 'tests\fixtures\recovery-source-manifest.json'
if (-not (Test-Path -LiteralPath $manifestPath -PathType Leaf)) {
    Add-Failure "Recovery source manifest missing: $manifestPath"
} else {
    try {
        $manifest = Get-Content -Raw -LiteralPath $manifestPath | ConvertFrom-Json
        $rootKeys = @($manifest.PSObject.Properties.Name)
        $expectedRootKeys = @('schemaVersion', 'canonicalFormat', 'sections')
        if (@(Compare-Object -ReferenceObject $expectedRootKeys -DifferenceObject $rootKeys).Count -ne 0) {
            Add-Failure 'Recovery source manifest has unexpected root shape.'
        }
        if ($manifest.schemaVersion -ne 1 -or $manifest.canonicalFormat -ne 'sha256-utf8-lf-ordinal-relative-path-tab-lowercase-file-sha256') {
            Add-Failure 'Recovery source manifest schema or canonical format is invalid.'
        }

        $expectedSectionNames = @('theme', 'mu-plugin', 'docs', 'ops')
        $actualSectionNames = @($manifest.sections | ForEach-Object name)
        if ($manifest.sections.Count -ne 4 -or @(Compare-Object -ReferenceObject $expectedSectionNames -DifferenceObject $actualSectionNames).Count -ne 0) {
            Add-Failure 'Recovery source manifest section set is invalid.'
        }

        foreach ($section in $manifest.sections) {
            $sectionKeys = @($section.PSObject.Properties.Name)
            $expectedSectionKeys = @('name', 'source', 'target', 'count', 'digest', 'exclude')
            if (@(Compare-Object -ReferenceObject $expectedSectionKeys -DifferenceObject $sectionKeys).Count -ne 0) {
                Add-Failure "Recovery source manifest section shape is invalid: $($section.name)"
                continue
            }
            if ($section.count -isnot [int] -or $section.count -lt 1 -or $section.digest -notmatch '^[0-9a-f]{64}$') {
                Add-Failure "Recovery source manifest count or digest is invalid: $($section.name)"
                continue
            }
            $sourcePath = Resolve-ContainedManifestPath -Root $SnapshotRoot -RelativePath $section.source -Label "$($section.name) source"
            $targetPath = Resolve-ContainedManifestPath -Root $repoRoot -RelativePath $section.target -Label "$($section.name) target"
            if (-not $sourcePath -or -not $targetPath) {
                continue
            }

            Assert-NoReparsePoint -Label "$($section.name) source" -Path $sourcePath
            Assert-NoReparsePoint -Label "$($section.name) repository" -Path $targetPath

            $sourceDigest = Get-CanonicalSectionDigest -Path $sourcePath -Exclude @($section.exclude)
            $targetExclude = @($section.exclude)
            if ($section.name -eq 'docs') {
                $targetExclude += @($localDocsExtras)
            } elseif ($section.name -eq 'ops') {
                $targetExclude += @($localOpsExtras)
            }
            $targetDigest = Get-CanonicalSectionDigest -Path $targetPath -Exclude $targetExclude
            if ($sourceDigest.Count -ne $section.count -or $sourceDigest.Digest -ne $section.digest) {
                Add-Failure "$($section.name) source manifest digest mismatch."
            }
            $targetDigestValid = $targetDigest.Count -eq $section.count -and $targetDigest.Digest -eq $section.digest
            if (-not $targetDigestValid) {
                Add-Failure "$($section.name) repository manifest digest mismatch."
            }
            if ($targetDigestValid) {
                foreach ($file in $targetDigest.Files) {
                    $approvedTargetFiles.Add($file)
                }
            }
        }
    } catch {
        Add-Failure "Recovery source manifest parse failed: $($_.Exception.Message)"
    }
}

$results = @()
$results += Compare-FileTree -Label 'theme' -SourceRoot $themeSource -DestinationRoot (Join-Path $repoRoot 'wordpress\wp-content\themes\vietnamguide-premium')
$results += Compare-FileTree -Label 'docs' -SourceRoot $docsSource -DestinationRoot (Join-Path $repoRoot 'docs') -AllowedDestinationExtras (@('RECOVERY.md') + @($localDocsExtras))
$results += Compare-FileTree -Label 'ops' -SourceRoot $opsSource -DestinationRoot (Join-Path $repoRoot 'ops') -SourceInclude {
    $_.FullName -notlike "$(Join-Path $opsSource 'backups')*" -and $_.Extension -ne '.sql'
} -AllowedDestinationExtras @($localOpsExtras)

$muDestination = Join-Path $repoRoot 'wordpress\wp-content\mu-plugins\vietnamguide-core.php'
if (-not (Test-Path -LiteralPath $muSource -PathType Leaf)) {
    Add-Failure "Missing source MU plugin: $muSource"
} elseif (-not (Test-Path -LiteralPath $muDestination -PathType Leaf)) {
    Add-Failure "Missing recovered MU plugin: $muDestination"
} elseif ((Get-FileHash -Algorithm SHA256 -LiteralPath $muSource).Hash -ne (Get-FileHash -Algorithm SHA256 -LiteralPath $muDestination).Hash) {
    Add-Failure 'MU plugin hash mismatch.'
}

$gitignorePath = Join-Path $repoRoot '.gitignore'
$requiredIgnoreRules = @(
    '.superpowers/',
    '.worktrees/',
    '.codex/config.toml',
    '*.sql',
    '*.wxr',
    '*.wpress',
    '**/backups/',
    '**/snapshots/',
    '**/exports/',
    '**/secret/',
    '**/secrets/',
    '**/credential/',
    '**/credentials/',
    'wordpress/wp-content/uploads/',
    '*production-snapshot*/',
    '*recovery-export*/',
    '.env',
    '.env.*',
    'wp-config.php',
    '*.pem',
    '*.key',
    '*.p12',
    '*.pfx',
    '*.jks',
    '*.keystore'
)

if (-not (Test-Path -LiteralPath $gitignorePath -PathType Leaf)) {
    Add-Failure 'Missing .gitignore.'
} else {
    $ignoreLines = Get-Content -LiteralPath $gitignorePath
    foreach ($rule in $requiredIgnoreRules) {
        if ($rule -notin $ignoreLines) {
            Add-Failure ".gitignore missing rule: $rule"
        }
    }
}

$gitattributesPath = Join-Path $repoRoot '.gitattributes'
$requiredAttributeRules = @(
    'wordpress/** -text',
    'ops/** -text',
    'docs/editorial/** -text',
    'docs/superpowers/** -text'
)
if (-not (Test-Path -LiteralPath $gitattributesPath -PathType Leaf)) {
    Add-Failure 'Missing .gitattributes.'
} else {
    $attributeRules = Get-Content -LiteralPath $gitattributesPath
    foreach ($rule in $requiredAttributeRules) {
        if ($rule -notin $attributeRules) {
            Add-Failure ".gitattributes missing rule: $rule"
        }
    }
}

$ignoreProbePaths = @(
    '.superpowers/state.json',
    '.worktrees/check/file',
    '.codex/config.toml',
    'database.sql',
    'export.wxr',
    'backup.wpress',
    'ops/backups/file.php',
    'wordpress/wp-content/uploads/file.jpg',
    'vietnamguide-production-snapshot-test/file',
    'recovery-export-test/file',
    '.env',
    'private.key',
    'leaked.key',
    'private.pem',
    'identity.p12',
    'identity.pfx',
    'identity.jks',
    'identity.keystore',
    'snapshots/artifact.bin',
    'nested/exports/artifact.bin',
    'nested/secret/artifact.bin',
    'nested/deeper/secrets/artifact.bin',
    'nested/credential/artifact.bin',
    'nested/deeper/credentials/artifact.bin'
)
$ignoredProbeCount = 0

foreach ($probe in $ignoreProbePaths) {
    & git -C $repoRoot check-ignore --no-index -q -- $probe
    if ($LASTEXITCODE -eq 0) {
        $ignoredProbeCount++
    } else {
        Add-Failure ".gitignore probe not ignored: $probe"
    }
}

Write-Host "Ignore probes: $ignoredProbeCount/$($ignoreProbePaths.Count)"

$recoveryParserInfrastructurePaths = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::Ordinal)
[void]$recoveryParserInfrastructurePaths.Add('tests/lib/RecoveryParser.psm1')
[void]$recoveryParserInfrastructurePaths.Add('tests/verify-recovery-parser.ps1')
$candidatePaths = [System.Collections.Generic.List[string]]::new()
$recoveryRepositoryPaths = [System.Collections.Generic.List[string]]::new()
$candidateFullPaths = [System.Collections.Generic.Dictionary[string, string]]::new([System.StringComparer]::Ordinal)
foreach ($candidateEntry in @(Get-GitPathLines -Arguments @('ls-files', '--cached', '--others', '--exclude-standard', '--') -Label 'Git candidate path enumeration')) {
    if ([string]::IsNullOrWhiteSpace($candidateEntry)) {
        continue
    }

    $candidateRelative = $candidateEntry.Replace('\', '/')
    $candidateFullPath = Resolve-ContainedRepositoryPath -RelativePath $candidateRelative -Label 'Candidate repository path'
    if ($null -eq $candidateFullPath) {
        continue
    }

    $candidatePaths.Add($candidateRelative)
    if (-not $recoveryParserInfrastructurePaths.Contains($candidateRelative)) {
        $recoveryRepositoryPaths.Add($candidateRelative)
    }
    $candidateFullPaths[$candidateRelative] = $candidateFullPath
}

foreach ($parserInfrastructurePath in $recoveryParserInfrastructurePaths) {
    if (-not $candidateFullPaths.ContainsKey($parserInfrastructurePath)) {
        Add-Failure "Recovery parser infrastructure path missing from repository candidates: $parserInfrastructurePath"
    }
}
if (
    $recoveryParserInfrastructurePaths.Count -ne 2 -or
    ($candidatePaths.Count - $recoveryRepositoryPaths.Count) -ne 2 -or
    $recoveryRepositoryPaths.Count -ne 231
) {
    Add-Failure "Recovery repository inventory count mismatch: expected 231; received $($recoveryRepositoryPaths.Count)."
}

$gitStageEntries = @(Get-GitPathLines -Arguments @('ls-files', '--stage', '--') -Label 'Git index path enumeration') + @($AdditionalGitStageEntry)
$indexEntries = @{}
foreach ($entry in $gitStageEntries) {
    if ($entry -match '^(\d{6})\s+([0-9a-f]{40,64})\s+\d+\s+(.+)$') {
        $mode = $Matches[1]
        $objectId = $Matches[2]
        $path = $Matches[3].Replace('\', '/')
        $fullPath = Resolve-ContainedRepositoryPath -RelativePath $path -Label 'Git index path'
        if ($null -eq $fullPath) {
            continue
        }

        $indexEntries[$path] = [pscustomobject]@{ Mode = $mode; ObjectId = $objectId; FullPath = $fullPath }
        if ($mode -eq '120000') {
            Add-Failure "Git symlink mode 120000 rejected: $path"
        }
    }
}

foreach ($parserInfrastructurePath in $recoveryParserInfrastructurePaths) {
    if (-not $indexEntries.ContainsKey($parserInfrastructurePath)) {
        Add-Failure "Recovery parser infrastructure path missing from Git index: $parserInfrastructurePath"
    }
}

$repoPrefix = $repoRoot.TrimEnd('\') + '\'
$approvedTargetPaths = @($approvedTargetFiles | ForEach-Object {
    $_.Substring($repoPrefix.Length).Replace('\', '/')
} | Sort-Object -Unique)
$bytePreservedIndexPaths = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::Ordinal)

if ($approvedTargetPaths.Count -gt 0) {
    $attributeResults = @(git -C $repoRoot check-attr text -- $approvedTargetPaths)
    $bytePreservedAttributePaths = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::Ordinal)
    if ($attributeResults.Count -ne $approvedTargetPaths.Count) {
        Add-Failure 'Unable to verify .gitattributes for every recovered file.'
    } else {
        foreach ($result in $attributeResults) {
            if ($result -notmatch ': text: unset$') {
                Add-Failure ".gitattributes does not preserve recovered bytes: $result"
            } else {
                $attributePath = $result.Substring(0, $result.Length - ': text: unset'.Length)
                $null = $bytePreservedAttributePaths.Add($attributePath)
            }
        }
    }

    $workingObjectIds = @(git -C $repoRoot hash-object --no-filters -- $approvedTargetPaths)
    if ($workingObjectIds.Count -ne $approvedTargetPaths.Count) {
        Add-Failure 'Unable to hash every recovered working-tree file.'
    } else {
        for ($index = 0; $index -lt $approvedTargetPaths.Count; $index++) {
            $path = $approvedTargetPaths[$index]
            if (-not $indexEntries.ContainsKey($path)) {
                Add-Failure "Recovered file missing from Git index: $path"
            } elseif ($indexEntries[$path].ObjectId -ne $workingObjectIds[$index]) {
                Add-Failure "Git index blob mismatch: $path"
            } elseif ($bytePreservedAttributePaths.Contains($path)) {
                $null = $bytePreservedIndexPaths.Add($path)
            }
        }
    }
}

foreach ($relative in $candidatePaths) {
    $fullPath = $candidateFullPaths[$relative]
    $isTracked = $indexEntries.ContainsKey($relative)
    $reparseRejected = $false
    $walkPath = $repoRoot
    foreach ($component in @($relative -split '/')) {
        $walkPath = Join-Path $walkPath $component
        if (-not (Test-Path -LiteralPath $walkPath)) {
            break
        }

        $item = Get-Item -LiteralPath $walkPath -Force
        if (($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) {
            if ($isTracked) {
                Add-Failure "Tracked reparse point rejected: $relative"
            } else {
                Add-Failure "Candidate reparse point rejected: $relative"
            }
            $reparseRejected = $true
            break
        }
    }
    if ($reparseRejected -or -not (Test-Path -LiteralPath $fullPath -PathType Leaf)) {
        continue
    }

    if (Test-PrivateKeyHeader -Path $fullPath) {
        if ($isTracked) {
            Add-Failure "Private key signature detected in tracked file: $relative"
        } else {
            Add-Failure "Private key signature detected in candidate file: $relative"
        }
    }
}

$forbiddenPathPatterns = @(
    '(^|/)wp-config\.php$',
    '\.(sql|sqlite|sqlite3|db|dump)$',
    '\.wxr$',
    '\.wpress$',
    '\.(pem|key|p12|pfx|jks|keystore)$',
    '(^|/)uploads/',
    '(^|/)backups?/',
    '(^|/)(secret|secrets|credential|credentials)/',
    '(^|/)\.codex/config\.toml$',
    'production-snapshot',
    'recovery-export'
)

foreach ($path in $candidatePaths) {
    foreach ($pattern in $forbiddenPathPatterns) {
        if ($path -match $pattern) {
            Add-Failure "Forbidden repository path: $path"
            break
        }
    }
}

$textExtensions = @('.css', '.env', '.html', '.htm', '.ini', '.js', '.json', '.key', '.md', '.pem', '.php', '.ps1', '.svg', '.toml', '.txt', '.xml', '.yaml', '.yml')
$secretPatterns = @(
    ('X-' + 'Goog-' + 'Api-' + 'Key'),
    'BEGIN(?: [A-Z0-9]+)* PRIVATE KEY',
    'AKIA[0-9A-Z]{16}',
    'gh[pousr]_[A-Za-z0-9]{20,}',
    '(?i)\b(password|passwd|pwd|token)\b\s*(?::|=(?!>))\s*(?:["''][^"'']+["'']|[A-Za-z0-9._-]{8,})(?=\s*[,;#\r\n]|$)',
    '(?i)(api[_-]?key|secret|token)\s*[:=]\s*["''][A-Za-z0-9_-]{12,}["'']',
    '(?i)\b[a-z][a-z0-9+.-]*://[^/\s:@]+:[^/\s@]+@'
)
$authorizedCredentialFixturePath = 'ops/verify-guide-experience-public.ps1'
$authorizedCredentialFixtureHash = '03b018f463abe474d06b7006a5cdc952d757cd26825d0a1c9700f20f24873543'
$authorizedCredentialFixtureLine = '$CredentialFixtureBuilder.' + 'Password' + " = 'pass'"
$authorizedCredentialFixtureMatchValue = 'Password' + " = 'pass'"

foreach ($relative in $candidatePaths) {
    $fullPath = $candidateFullPaths[$relative]
    if (-not (Test-Path -LiteralPath $fullPath -PathType Leaf)) {
        continue
    }

    $extension = [System.IO.Path]::GetExtension($fullPath).ToLowerInvariant()
    if ($extension -notin $textExtensions -and [System.IO.Path]::GetFileName($fullPath) -ne '.gitignore') {
        continue
    }

    $secretMatchLines = @(Select-String -LiteralPath $fullPath -Pattern $secretPatterns -AllMatches -ErrorAction SilentlyContinue)
    if ($secretMatchLines.Count -eq 0) {
        continue
    }

    # Permit only the restored credential test fixture, never the whole verifier file.
    $isAuthorizedCredentialFixtureFile = (
        $relative -ceq $authorizedCredentialFixturePath -and
        $validatedLocalOpsRelativePaths.Contains($relative) -and
        $bytePreservedIndexPaths.Contains($relative) -and
        (Get-FileHash -Algorithm SHA256 -LiteralPath $fullPath).Hash.ToLowerInvariant() -ceq $authorizedCredentialFixtureHash
    )
    $hasUnauthorizedSecretMatch = $false
    foreach ($secretMatchLine in $secretMatchLines) {
        foreach ($secretMatch in @($secretMatchLine.Matches)) {
            $isAuthorizedCredentialFixtureMatch = (
                $isAuthorizedCredentialFixtureFile -and
                $secretMatchLine.LineNumber -eq 935 -and
                $secretMatchLine.Line -ceq $authorizedCredentialFixtureLine -and
                $secretMatch.Index -eq 26 -and
                $secretMatch.Value -ceq $authorizedCredentialFixtureMatchValue
            )
            if (-not $isAuthorizedCredentialFixtureMatch) {
                $hasUnauthorizedSecretMatch = $true
                break
            }
        }
        if ($hasUnauthorizedSecretMatch) {
            break
        }
    }

    if ($hasUnauthorizedSecretMatch) {
        Add-Failure "Candidate secret pattern in: $relative"
    }
}

$results | Format-Table -AutoSize
if ($failures.Count -gt 0) {
    $failures | ForEach-Object { Write-Error $_ -ErrorAction Continue }
    exit 1
}

Write-Host "Recovery baseline verification passed for $($recoveryRepositoryPaths.Count) repository files."

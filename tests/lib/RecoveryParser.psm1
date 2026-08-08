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

Export-ModuleMember -Function New-RecoveryParserDiagnostic, New-RecoveryExecutableEvent, New-RecoveryParseResult, ConvertFrom-RecoveryMarkdown

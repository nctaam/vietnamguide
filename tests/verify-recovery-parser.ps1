[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

Import-Module (Join-Path $PSScriptRoot 'lib\RecoveryParser.psm1') -Force

$results = @()

function Add-ParserResult {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Name,

        [Parameter(Mandatory = $true)]
        [scriptblock]$Test
    )

    try {
        & $Test
        $script:results += [pscustomobject]@{
            Name = $Name
            Passed = $true
            Error = ''
        }
    }
    catch {
        $script:results += [pscustomobject]@{
            Name = $Name
            Passed = $false
            Error = $_.Exception.Message
        }
    }
}

function Assert-ParserEqual {
    param(
        [Parameter(Mandatory = $true)]
        [AllowNull()]
        $Actual,

        [Parameter(Mandatory = $true)]
        [AllowNull()]
        $Expected,

        [Parameter(Mandatory = $true)]
        [string]$Message
    )

    if ($Actual -ne $Expected) {
        throw "$Message Expected '$Expected'; received '$Actual'."
    }
}

function Assert-ParserProperties {
    param(
        [Parameter(Mandatory = $true)]
        [pscustomobject]$Value,

        [Parameter(Mandatory = $true)]
        [string[]]$Expected
    )

    $actual = @($Value.PSObject.Properties.Name)
    if (($actual -join '|') -ne ($Expected -join '|')) {
        throw "Unexpected properties. Expected '$($Expected -join ', ')'; received '$($actual -join ', ')'."
    }
}

function Assert-ParserDiagnosticCodes {
    param(
        [Parameter(Mandatory = $true)]
        [object[]]$Diagnostics,

        [Parameter(Mandatory = $true)]
        [string[]]$Expected
    )

    $actual = @($Diagnostics | ForEach-Object { $_.Code })
    if (($actual -join '|') -ne ($Expected -join '|')) {
        throw "Unexpected diagnostic codes. Expected '$($Expected -join ', ')'; received '$($actual -join ', ')'."
    }
}

Add-ParserResult -Name 'Diagnostic constructor returns the typed diagnostic contract' -Test {
    $diagnostic = New-RecoveryParserDiagnostic -Code 'RP001' -Message 'Example diagnostic' -Language 'powershell' -SourceLine 12 -SourceColumn 4

    Assert-ParserProperties -Value $diagnostic -Expected @('Code', 'Message', 'Language', 'SourceLine', 'SourceColumn', 'FenceId')
    Assert-ParserEqual -Actual $diagnostic.Code -Expected 'RP001' -Message 'Diagnostic code mismatch.'
    Assert-ParserEqual -Actual $diagnostic.FenceId -Expected $null -Message 'Diagnostic fence identifier should default to null.'
}

Add-ParserResult -Name 'Executable event constructor returns the typed event contract' -Test {
    $event = New-RecoveryExecutableEvent -Kind 'Command' -Language 'powershell' -Text 'Get-Date' -SourceLine 8 -SourceColumn 1 -FenceId 'fence-1' -StatementId 'statement-1'

    Assert-ParserProperties -Value $event -Expected @('Kind', 'Language', 'Text', 'NormalizedCommand', 'SourceLine', 'SourceColumn', 'SectionId', 'FenceId', 'StatementId', 'Metadata')
    Assert-ParserEqual -Actual $event.Kind -Expected 'Command' -Message 'Event kind mismatch.'
    Assert-ParserEqual -Actual $event.NormalizedCommand -Expected $null -Message 'Normalized command should default to null.'
    Assert-ParserEqual -Actual $event.SectionId -Expected $null -Message 'Section identifier should default to null.'
    Assert-ParserEqual -Actual $event.Metadata.GetType().FullName -Expected 'System.Collections.Hashtable' -Message 'Metadata should default to a hashtable.'
}

Add-ParserResult -Name 'Parse result filters null entries and derives validity from diagnostics' -Test {
    $event = New-RecoveryExecutableEvent -Kind 'Command' -Language 'powershell' -Text 'Get-Date' -NormalizedCommand 'Get-Date' -SourceLine 8 -SourceColumn 1 -SectionId 'section-1' -FenceId 'fence-1' -StatementId 'statement-1' -Metadata @{}
    $diagnostic = New-RecoveryParserDiagnostic -Code 'RP001' -Message 'Example diagnostic' -Language 'powershell' -SourceLine 12 -SourceColumn 4 -FenceId 'fence-1'
    $valid = New-RecoveryParseResult -Events @($null, $event) -Diagnostics @($null)
    $invalid = New-RecoveryParseResult -Events @($null) -Diagnostics @($diagnostic, $null)

    Assert-ParserProperties -Value $valid -Expected @('IsValid', 'Events', 'Diagnostics')
    Assert-ParserEqual -Actual $valid.Events.Count -Expected 1 -Message 'Valid result event count mismatch.'
    Assert-ParserEqual -Actual $valid.Diagnostics.Count -Expected 0 -Message 'Valid result diagnostic count mismatch.'
    Assert-ParserEqual -Actual $valid.IsValid -Expected $true -Message 'Valid result should be valid.'
    Assert-ParserEqual -Actual $invalid.Events.Count -Expected 0 -Message 'Invalid result event count mismatch.'
    Assert-ParserEqual -Actual $invalid.Diagnostics.Count -Expected 1 -Message 'Invalid result diagnostic count mismatch.'
    Assert-ParserEqual -Actual $invalid.IsValid -Expected $false -Message 'Invalid result should not be valid.'
}

Add-ParserResult -Name 'Markdown parser treats a same-info fence line as body content' -Test {
    $text = [string]::Join("`n", @(
        '## Target',
        '```bash',
        'echo visible',
        '```bash',
        '## Hidden heading',
        'echo hidden',
        '```',
        '## Next'
    ))

    $result = ConvertFrom-RecoveryMarkdown -Text $text -RequiredSections @('## Target', '## Next') -RequiredFenceLanguages @{ '## Target' = @('bash') }
    $sections = @($result.Sections)
    $fences = @($result.Fences)

    Assert-ParserProperties -Value $result -Expected @('IsValid', 'Sections', 'Fences', 'Diagnostics')
    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Markdown result should be valid.'
    Assert-ParserEqual -Actual $sections.Count -Expected 2 -Message 'Visible section count mismatch.'
    Assert-ParserProperties -Value $sections[0] -Expected @('Id', 'Heading', 'StartLine', 'EndLine')
    Assert-ParserEqual -Actual $sections[0].Id -Expected 'section-0001' -Message 'First section identifier mismatch.'
    Assert-ParserEqual -Actual $sections[0].Heading -Expected '## Target' -Message 'First section heading mismatch.'
    Assert-ParserEqual -Actual $sections[0].StartLine -Expected 1 -Message 'First section start line mismatch.'
    Assert-ParserEqual -Actual $sections[0].EndLine -Expected 7 -Message 'First section end line mismatch.'
    Assert-ParserEqual -Actual $sections[1].Id -Expected 'section-0002' -Message 'Second section identifier mismatch.'
    Assert-ParserEqual -Actual $sections[1].Heading -Expected '## Next' -Message 'Second section heading mismatch.'
    Assert-ParserEqual -Actual $sections[1].StartLine -Expected 8 -Message 'Second section start line mismatch.'
    Assert-ParserEqual -Actual $sections[1].EndLine -Expected 8 -Message 'Second section end line mismatch.'
    Assert-ParserEqual -Actual $fences.Count -Expected 1 -Message 'Fence count mismatch.'
    Assert-ParserProperties -Value $fences[0] -Expected @('Id', 'SectionId', 'Language', 'RawInfo', 'Body', 'StartLine', 'EndLine')
    Assert-ParserEqual -Actual $fences[0].Id -Expected 'fence-0001' -Message 'Fence identifier mismatch.'
    Assert-ParserEqual -Actual $fences[0].SectionId -Expected 'section-0001' -Message 'Fence section identifier mismatch.'
    Assert-ParserEqual -Actual $fences[0].Language -Expected 'bash' -Message 'Fence language mismatch.'
    Assert-ParserEqual -Actual $fences[0].RawInfo -Expected 'bash' -Message 'Fence raw info mismatch.'
    Assert-ParserEqual -Actual $fences[0].Body -Expected ([string]::Join("`n", @('echo visible', '```bash', '## Hidden heading', 'echo hidden'))) -Message 'Fence body mismatch.'
    Assert-ParserEqual -Actual $fences[0].StartLine -Expected 2 -Message 'Fence start line mismatch.'
    Assert-ParserEqual -Actual $fences[0].EndLine -Expected 7 -Message 'Fence end line mismatch.'
}

Add-ParserResult -Name 'Markdown parser ignores required headings inside HTML comments' -Test {
    $text = [string]::Join("`n", @(
        '<!-- ## Target -->',
        '```bash',
        'echo hidden',
        '```'
    ))

    $result = ConvertFrom-RecoveryMarkdown -Text $text -RequiredSections @('## Target')

    Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message 'Comment-hidden section should be invalid.'
    Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('MD_REQUIRED_SECTION_MISSING')
}

Add-ParserResult -Name 'Markdown parser reports a missing required fence language' -Test {
    $result = ConvertFrom-RecoveryMarkdown -Text "## Target`nplain text" -RequiredFenceLanguages @{ '## Target' = @('bash') }

    Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message 'Missing required fence should be invalid.'
    Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('MD_REQUIRED_FENCE_MISSING')
}

Add-ParserResult -Name 'Markdown parser accepts tilde fences' -Test {
    $text = [string]::Join("`n", @('## Target', '~~~PowerShell details', 'Get-Date', '~~~'))
    $result = ConvertFrom-RecoveryMarkdown -Text $text -RequiredFenceLanguages @{ '## Target' = @('powershell') }
    $fence = @($result.Fences)[0]

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Tilde fence should be valid.'
    Assert-ParserEqual -Actual $fence.Language -Expected 'powershell' -Message 'Tilde fence language mismatch.'
    Assert-ParserEqual -Actual $fence.RawInfo -Expected 'PowerShell details' -Message 'Tilde fence raw info mismatch.'
    Assert-ParserEqual -Actual $fence.Body -Expected 'Get-Date' -Message 'Tilde fence body mismatch.'
}

Add-ParserResult -Name 'Markdown parser accepts longer closing fences with trailing whitespace' -Test {
    $text = [string]::Join("`n", @('## Target', '```bash', 'echo ok', '`````   '))
    $result = ConvertFrom-RecoveryMarkdown -Text $text
    $fence = @($result.Fences)[0]

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Long closing fence should be valid.'
    Assert-ParserEqual -Actual $fence.EndLine -Expected 4 -Message 'Long closing fence end line mismatch.'
    Assert-ParserEqual -Actual $fence.Body -Expected 'echo ok' -Message 'Long closing fence body mismatch.'
}

Add-ParserResult -Name 'Markdown parser preserves blank lines in empty-info fences' -Test {
    $text = [string]::Join("`n", @('## Target', '', '```', '', 'plain text', '```'))
    $result = ConvertFrom-RecoveryMarkdown -Text $text
    $fence = @($result.Fences)[0]

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Empty-info fence should be valid.'
    Assert-ParserEqual -Actual $fence.Language -Expected '' -Message 'Empty fence info should yield an empty language.'
    Assert-ParserEqual -Actual $fence.RawInfo -Expected '' -Message 'Empty fence info should yield empty raw info.'
    Assert-ParserEqual -Actual $fence.Body -Expected "`nplain text" -Message 'Blank fence body line should be preserved.'
}

Add-ParserResult -Name 'Markdown parser handles stateful multiline HTML comments' -Test {
    $text = [string]::Join("`n", @(
        '<!--',
        '## Hidden',
        '```bash',
        'echo hidden',
        '```',
        '-->## Target',
        '```bash',
        'echo visible',
        '```'
    ))

    $result = ConvertFrom-RecoveryMarkdown -Text $text -RequiredSections @('## Target') -RequiredFenceLanguages @{ '## Target' = @('bash') }
    $sections = @($result.Sections)
    $fences = @($result.Fences)

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Content after a multiline comment should remain structural.'
    Assert-ParserEqual -Actual $sections.Count -Expected 1 -Message 'Comment-hidden heading should not create a section.'
    Assert-ParserEqual -Actual $sections[0].Heading -Expected '## Target' -Message 'Visible heading after comment mismatch.'
    Assert-ParserEqual -Actual $sections[0].StartLine -Expected 6 -Message 'Visible heading source line mismatch.'
    Assert-ParserEqual -Actual $fences.Count -Expected 1 -Message 'Comment-hidden fence should not be parsed.'
    Assert-ParserEqual -Actual $fences[0].StartLine -Expected 7 -Message 'Visible fence source line mismatch.'
}

Add-ParserResult -Name 'Markdown parser does not splice comment-hidden fence markers' -Test {
    $text = [string]::Join("`n", @('## Target', '`<!-- gap -->``bash', 'echo visible'))
    $result = ConvertFrom-RecoveryMarkdown -Text $text -RequiredFenceLanguages @{ '## Target' = @('bash') }

    Assert-ParserEqual -Actual @($result.Fences).Count -Expected 0 -Message 'Comment-spliced marker should not create a fence.'
    Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('MD_REQUIRED_FENCE_MISSING')
}

Add-ParserResult -Name 'Markdown parser reports an unclosed fence at the opening marker' -Test {
    $text = [string]::Join("`n", @('## Target', '  ```bash', 'echo unfinished'))
    $result = ConvertFrom-RecoveryMarkdown -Text $text
    $diagnostic = @($result.Diagnostics)[0]
    $fence = @($result.Fences)[0]

    Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('MD_UNCLOSED_FENCE')
    Assert-ParserEqual -Actual $diagnostic.Language -Expected 'markdown' -Message 'Unclosed fence diagnostic language mismatch.'
    Assert-ParserEqual -Actual $diagnostic.SourceLine -Expected 2 -Message 'Unclosed fence diagnostic source line mismatch.'
    Assert-ParserEqual -Actual $diagnostic.SourceColumn -Expected 3 -Message 'Unclosed fence diagnostic source column mismatch.'
    Assert-ParserEqual -Actual $diagnostic.FenceId -Expected 'fence-0001' -Message 'Unclosed fence diagnostic fence identifier mismatch.'
    Assert-ParserEqual -Actual $fence.StartLine -Expected 2 -Message 'Unclosed fence start line mismatch.'
    Assert-ParserEqual -Actual $fence.EndLine -Expected 3 -Message 'Unclosed fence end line mismatch.'
}

Add-ParserResult -Name 'Markdown parser reports an unclosed HTML comment at its opening marker' -Test {
    $text = [string]::Join("`n", @('## Target', '  <!-- hidden', 'still hidden'))
    $result = ConvertFrom-RecoveryMarkdown -Text $text
    $diagnostic = @($result.Diagnostics)[0]

    Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('MD_UNCLOSED_HTML_COMMENT')
    Assert-ParserEqual -Actual $diagnostic.Language -Expected 'markdown' -Message 'Unclosed comment diagnostic language mismatch.'
    Assert-ParserEqual -Actual $diagnostic.SourceLine -Expected 2 -Message 'Unclosed comment diagnostic source line mismatch.'
    Assert-ParserEqual -Actual $diagnostic.SourceColumn -Expected 3 -Message 'Unclosed comment diagnostic source column mismatch.'
    Assert-ParserEqual -Actual $diagnostic.FenceId -Expected $null -Message 'Unclosed comment diagnostic fence identifier should be null.'
}

Add-ParserResult -Name 'Markdown parser normalizes language aliases to canonical keys' -Test {
    $text = [string]::Join("`n", @('## Target', '```SH', 'echo ok', '```'))
    $result = ConvertFrom-RecoveryMarkdown -Text $text -RequiredFenceLanguages @{ '## Target' = @('bash') } -LanguageAliases @{ bash = @('bash', 'sh') }
    $fence = @($result.Fences)[0]

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Declared language alias should satisfy the canonical requirement.'
    Assert-ParserEqual -Actual $fence.Language -Expected 'bash' -Message 'Fence alias should normalize to its canonical key.'
}

Add-ParserResult -Name 'Markdown parser reports unsupported fence languages when aliases are declared' -Test {
    $text = [string]::Join("`n", @('## Target', '  ```PyThOn details', 'print(1)', '  ```'))
    $result = ConvertFrom-RecoveryMarkdown -Text $text -LanguageAliases @{ bash = @('bash', 'sh') }
    $diagnostic = @($result.Diagnostics)[0]
    $fence = @($result.Fences)[0]

    Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('MD_UNSUPPORTED_FENCE_LANGUAGE')
    Assert-ParserEqual -Actual $fence.Language -Expected 'python' -Message 'Unsupported fence language should remain deterministic and lowercase.'
    Assert-ParserEqual -Actual $diagnostic.SourceLine -Expected 2 -Message 'Unsupported language diagnostic source line mismatch.'
    Assert-ParserEqual -Actual $diagnostic.SourceColumn -Expected 3 -Message 'Unsupported language diagnostic source column mismatch.'
    Assert-ParserEqual -Actual $diagnostic.FenceId -Expected 'fence-0001' -Message 'Unsupported language diagnostic fence identifier mismatch.'
}

Add-ParserResult -Name 'Markdown parser normalizes mixed line endings without losing source lines' -Test {
    $text = "## Target`r`n" + '```bash' + "`recho one`r`necho two`n" + '```'
    $result = ConvertFrom-RecoveryMarkdown -Text $text
    $section = @($result.Sections)[0]
    $fence = @($result.Fences)[0]

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Mixed line endings should parse successfully.'
    Assert-ParserEqual -Actual $section.StartLine -Expected 1 -Message 'Normalized section start line mismatch.'
    Assert-ParserEqual -Actual $section.EndLine -Expected 5 -Message 'Normalized section end line mismatch.'
    Assert-ParserEqual -Actual $fence.StartLine -Expected 2 -Message 'Normalized fence start line mismatch.'
    Assert-ParserEqual -Actual $fence.EndLine -Expected 5 -Message 'Normalized fence end line mismatch.'
    Assert-ParserEqual -Actual $fence.Body -Expected "echo one`necho two" -Message 'Normalized fence body mismatch.'
}

$results | Format-Table -AutoSize | Out-Host

$failed = @($results | Where-Object { -not $_.Passed })
if ($failed.Count -gt 0) {
    throw "Recovery parser verification failed: $($failed.Count)/$($results.Count) failed."
}

Write-Host "Recovery parser verification passed: $($results.Count)/$($results.Count)"
